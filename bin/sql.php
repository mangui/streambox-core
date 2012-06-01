<?php

function sqlconnect()
{
	global $sqlserver, $sqluser, $sqlpassword, $sqldatabase;

	$mysqli = new mysqli($sqlserver, $sqluser, $sqlpassword, $sqldatabase);
	if ($mysqli->connect_errno) {
		echo "SQL connect failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
		addlog("SQL: connect failed: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		return NULL;
	}

	return $mysqli;
}

function sqlgetuserinfo($type, $user)
{
	$info = "invalid";

	$mysqli = sqlconnect();
	if (!$mysqli)
		return array(0, 0, 0);

	$result = $mysqli->query("SELECT * FROM users WHERE username ='" .$user ."'");
	if ($result)
	{
		if ($result->num_rows == 1)
		{
			$row = $result->fetch_assoc();
			$info = $row[$type];
		}
		else
			addlog("SQL: ERROR inexistant or multiple entries for user " .$user);

		$result->close();
	}

	$mysqli->close();

	return $info;
}

?>
