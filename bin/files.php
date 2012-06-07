<?php

function mediagetinfostream($stream)
{
	$info = array();

	addlog("Requesting media info from " .$stream);

        // Get info
        $getid3 = new getID3;
        $fileinfo = $getid3->analyze($stream);

	$info['name'] = basename($stream);
	$info['desc'] = "";
	$info['duration'] = sec2hms($fileinfo['playtime_seconds']);
	if ($fileinfo['fileformat'])
		$info['format'] = $fileinfo['fileformat'];
	else
		$info['format'] = "unkown";
	if ($fileinfo['video']['codec'])
		$info['video'] = $fileinfo['video']['codec'];
	else
		$info['video'] = "unkown";
	if ($fileinfo['audio']['codec'])
		$info['audio'] = $fileinfo['audio']['codec'];
	else
		 $info['audio'] = "unkown";
	$info['resolution'] = $fileinfo['video']['resolution_x'] ."x" .$fileinfo['video']['resolution_y'];

	return $info;
}

function mediagentb($stream, $dest)
{
	global $ffmpegpath;

	addlog("Generating thumbnail for stream " .$stream ." to " .$dest);

	// Get info
	$getid3 = new getID3;
	$fileinfo = $getid3->analyze($stream);

	exec("rm " .$dest);
	$path = dirname($stream);

	if (file_exists(substr($stream, 0, -4) .".tbn"))
		$file = substr($stream, 0, -4) .".tbn";
	else if (file_exists($path ."/poster.jpg"))
		$file = $path ."/poster.jpg";
	else if (file_exists($path ."/folder.jpg"))
		$file = $path ."/folder.jpg";
	else
		$file = "";

	$resx = 180;
	$resy = 100;

	if ($file)
	{
		$getid3 = new getID3;
		$fileinfo = $getid3->analyze($file);
	}

	if ($fileinfo['video']['resolution_y'] && $fileinfo['video']['resolution_x'])
	{
		if ($fileinfo['video']['resolution_y'] < $fileinfo['video']['resolution_x'])
		{
			$resx = 180;
			$resy = round(($fileinfo['video']['resolution_y'] * 180) / $fileinfo['video']['resolution_x']);
		}
		else
		{
			$resx = round (($fileinfo['video']['resolution_x'] * 100) / $fileinfo['video']['resolution_y']);
			$resy = 100;
		}
	}

	if ($file)
		$cmd = "cp \"" .$file ."\" ../ram/stream-tb-tmp.jpg;  " .$ffmpegpath ." -y -i ../ram/stream-tb-tmp.jpg -s " .$resx ."x" .$resy ." " .$dest ." ; rm ../ram/stream-tb-tmp.jpg";
	else
	        $cmd = $ffmpegpath ." -y -i \"" .$stream ."\" -an -ss 00:00:05.00 -r 1 -vframes 1 -s " .$resx ."x" .$resy ." -f mjpeg " .$dest;

	addlog("Thumbnail generation command: " .$cmd);

	exec($cmd);

	if (!file_exists($dest))
		exec('cp ../logos/nologoMEDIA.png ' .$dest);
}

function filegettype($file)
{
	global $videotypes, $audiotypes, $vdrrecpath;

	// Get file extension
	$fileext = end(explode(".", $file));
	$file = str_replace("\\'", "'", $file);

	if (is_dir($file))
	{
		if (substr($file, 0, strlen($vdrrecpath)) == $vdrrecpath)
			return 'rec';
		else
			return 'folder';
	}
	else if (preg_match("$/$", $fileext))
		return 'none';
	else if (preg_match("/" .$fileext ." /", $videotypes))
		return 'video';
	else if (preg_match("/" .$fileext ." /", $audiotypes))
		return 'audio';
	else
		return 'unknown';
}

function mediagetmusicinfo($file)
{
	addlog("Getting info for music file: " .$file);

	// Get info
	$getid3 = new getID3;
	$fileinfo = $getid3->analyze($file);

	$name = $fileinfo['tags']['id3v2']['title'][0];
	if ($name == "")
	{
		$name = $fileinfo['tags']['id3v1']['title'][0];
		if ($name == "")
		{
			$name = $fileinfo['filename'];
			if ($name == "")
				$name = "unknown";
		}
	}

	if (!is_utf8($name))
		$name = utf8_encode($name);

	$duration = $fileinfo['playtime_string'];

	return array ($name, $duration);
}

function generatelogo($type, $name, $dest)
{
	addlog("Generating stream logo for file " .$name ." of type " .$type);

        switch ($type)
        {
                case 'tv':
                        $channoslash = preg_replace("$/$", " ", $name);
                        $logopath = "../logos/" .$channoslash .".png";
                        if (!file_exists($logopath))
                                $logopath = "../logos/nologoTV.png";
                        $cmd = "cp \"" .$logopath ."\" " .$dest;
			addlog("Executing generation cmd: " .$cmd);
			exec($cmd);
                        break;
                case 'rec':
                        $channoslash = preg_replace("$/$", " ", $name);
                        $logopath = "../logos/" .$channoslash .".png";
                        if (!file_exists($logopath))
                                $logopath = "../logos/nologoREC.png";
                        $cmd = "cp \"" .$logopath ."\" " .$dest;
			addlog("Executing generation cmd: " .$cmd);
			exec($cmd);
                        break;
                case 'vid':
                        // Generate TB
                        mediagentb($name, $dest);
                        break;
        }
}

function filesgetlisting($dir, $type)
{
	global $username, $vdrrecpath, $videosource, $audiosource;

	addlog("Listing for type " .$type ." dir: " .$dir);

	switch ($type)
	{
		case "rec":
			$predir = $vdrrecpath;
			break;
		case "vid":
			$predir = $videosource;
			break;
		case "aud":
			$predir = $audiosource;
			break;
		default:
			$predir = "";
			break;
	}

	$filelisting = array();
	$folderlisting = array();

	// Check dir
	if (!isurlvalid($dir, "media") && !isurlvalid($dir, "rec"))
		return array();

	// Dont allow ..
	if (preg_match("$\.\.$", $dir))
		return array();

	$dir_handle = @opendir($predir .$dir);
	if (!$dir_handle)
		return array();

	while ($medianame = readdir($dir_handle))
	{
		if($medianame == "." || $medianame == ".." || $medianame == 'lost+found')
			continue;

		$medianame_array[] = $medianame;
	}

	if ($medianame_array[0] == NULL)
		return array();

	// Alphabetical sorting
	sort($medianame_array);

	$number = 1;

	// List files and folders
	foreach($medianame_array as $value)
	{
		$type = filegettype($predir .$dir ."/" .$value);

		$newentry = array();
		$newentry['name'] = $value;
		$newentry['path'] = $dir .$value;
		$newentry['type'] = $type;

		switch ($type)
		{
			case 'audio':
				list($newentry['trackname'], $newentry['length']) = mediagetmusicinfo($predir .$dir ."/" .$value);
				$newentry['number'] = $number;
				$number++;
				$filelisting[] = $newentry;
				break;
			case 'video':
				$filelisting[] = $newentry;
				break;
			case 'folder':
				$newentry['path'] = $newentry['path'] .'/';
				// Skip emtpy dirs
				if (glob(quotemeta($newentry['path']) .'*'))
					$folderlisting[] = $newentry;
				break;
			case 'rec':

				if (    ( $dir == "./" && substr($value,  0, strlen($username)+1) != ($username ."_"))
				||      ( $dir != "./" && substr($dir,  2, strlen($username)+1) != ($username ."_"))
					)
					continue;

				if (end(explode(".", $value)) == "rec")
				{
					$date = preg_replace('/-/', '/', substr($value, 0, 10));
					$time = preg_replace('/\./', 'h', substr($value, 11, 5));
					$recnice = $date .' at ' .$time;
					$newentry['name'] = $recnice;
				}
				else
				{
					$newentry['name'] = substr($value, strlen($username)+1);
					$newentry['type'] = 'folder';
				}

				$newentry['path'] = $newentry['path'] .'/';

				$folderlisting[] = $newentry;
				break;
			default:
		}
	}

	return array_merge($folderlisting, $filelisting);
}

?>
