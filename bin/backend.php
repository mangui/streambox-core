<?php

// Check PHP modules
if (function_exists('dl'))
{
	if (!extension_loaded('json'))
		dl(json.so);
	if (!extension_loaded('zlib'))
		dl(zlib.so);
}

if(!ob_start("ob_gzhandler"))
	ob_start();

header('Content-Type: application/json; charset: utf-8'); 
header('Content-Encoding: gzip');

if (file_exists('../config.php'))
	include ('../config.php');
else
	include ('../config_default.php');
include ('./debug.php');
include ('./auth.php');
include ('../getid3/getid3.php');
include ('./utils.php');
include ('./files.php');
include ('./streaminfo.php');
include ('./vdr.php');
include ('./session.php');
include ('./jsonapi.php');

// Set timezone
date_default_timezone_set(date_default_timezone_get());

$action=$_REQUEST['action'];

addlog("Executing action [" .$action ."]");

switch ($action)
        {
	        case ("getGlobals"):
			$tree = getGlobals();
			print $tree;
			break;
		case ("getRunningSessions"):
			$tree = getRunningSessions();
			print $tree;
			break;
		case ("getTvCat"):
			$tree =  getTvCat();
        	        print $tree;
			break;
		
		case ("getFullChanList"):
			$tree = getFullChanList();
			print $tree;
			break;
		
		case ("getTvChan"):
			$tree = GetTvChan($_REQUEST['cat']);
        	        print $tree;
			break;
		
		case ("getChanInfo"):
			$tree = getChanInfo($_REQUEST['chan']);
        	        print $tree;
			break;
		
		case ("getRecInfo"):
			$tree = getRecInfo(stripslashes($_REQUEST['rec']));
	       	        print $tree;
			break;
		
		case ("getVidInfo"):
			$tree = getVidInfo(stripslashes($_REQUEST['file']));
        	        print $tree;
			break;
		
		case ("getStreamInfo"):
			$tree = getStreamInfo($_REQUEST['session']);
			print $tree;
			break;
		
		case ("startBroadcast"):
			$tree = startBroadcast($_REQUEST['type'], stripslashes($_REQUEST['url']), $_REQUEST['mode']);
			print $tree;
			break;
		
		case ("stopBroadcast"):
			$tree = stopBroadcast($_REQUEST['session']);
			print $tree;
			break;
		
		case ("getStreamStatus"):
			$tree= getStreamStatus($_REQUEST['session'], $_REQUEST['msg']);
			print $tree;
			break;
		
		case ("getTimers"):
			$tree = getTimers();
                	print $tree;
			break;
		
		case ("editTimer"):
			$tree = editTimer($_REQUEST['id'], stripslashes($_REQUEST['name']), $_REQUEST['active'], $_REQUEST['channumber'], $_REQUEST['date'], $_REQUEST['starttime'], $_REQUEST['endtime']);
			print $tree;
			break;
		
		case ("delTimer"):
			$tree = delTimer($_REQUEST['id']);
	                print $tree;
			break;
		
		case ("browseFolder"):
			$tree = browseFolder(stripslashes($_REQUEST['path']));
			print $tree;
			break;
		
		case ("streamAudio"):
			$tree = streamAudio($_REQUEST['path'], $_REQUEST['file']);
			print $tree;
			break;

		case ("getEpg"):
			$tree = getEpg($_REQUEST['channel'], $_REQUEST['time'], $_REQUEST['day'], $_REQUEST['programs']);
			print $tree;
			break;
		case ("getEpgInfo"):
			$tree = getEpgInfo($_REQUEST['channel'], $_REQUEST['time'], $_REQUEST['day']);
			print $tree;
			break;
	}

?>
