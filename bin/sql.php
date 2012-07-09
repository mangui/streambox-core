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

function sqlgettableentry($table, $type, $user)
{
        $info = "error";

        $mysqli = sqlconnect();
        if (!$mysqli)
                return "SQL connection failed";

        $result = $mysqli->query("SELECT * FROM " .$table ." WHERE username ='" .$user ."'");
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

function sqlsettableentry($table, $type, $user, $value)
{
        $mysqli = sqlconnect();
        if (!$mysqli)
		return "SQL connection failed";

	$result = $mysqli->query("UPDATE " .$table ." SET " .$type ."='" .$value ."' WHERE username ='" .$user ."'");
	if (!$result)
	{
		$mysqli->close();
		return "SQL command failed";
	}

        $mysqli->close();

	return "OK";
}


function sqlgetuserinfo($type, $user)
{
	return sqlgettableentry("users", $type, $user);
}

function sqlgetuserstat($type, $user)
{
        return sqlgettableentry("statistics", $type, $user);
}

function sqlsetuserstat($type, $user, $value)
{
	sqlsettableentry("statistics", $type, $user, $value);
}

?>
