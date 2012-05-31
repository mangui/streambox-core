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

function addstreaminglog($log)
{
        global $debug, $debugfile, $monitoring, $monitoringfile;

	if (!$monitoring)
		return;

        $newlog = date("Y/m/d H:i:s -> ") .$log ."\n";

        $debughandle = fopen($monitoringfile, 'a');
        if (!$debughandle)
                return;
        fwrite($debughandle, $newlog);

        fclose($debughandle);
}


?>
