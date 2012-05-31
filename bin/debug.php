<?php
function addlog($log)
{
	global $debug, $debugfile;

	if (!$debug)
		return ;

	$newlog = date("[Y/m/d H:i:s]  ") .$log ."\n";

	$debughandle = fopen($debugfile, 'a');
	if (!$debughandle)
		return;
	fwrite($debughandle, $newlog);

	fclose($debughandle);
}

?>
