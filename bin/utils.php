<?php
function is_utf8($str)
{
	$c=0; $b=0;
	$bits=0;
	$len=strlen($str);
	for($i=0; $i<$len; $i++)
	{
		$c=ord($str[$i]);
		if($c > 128)
		{
			if(($c >= 254)) return false;
			elseif($c >= 252) $bits=6;
			elseif($c >= 248) $bits=5;
			elseif($c >= 240) $bits=4;
			elseif($c >= 224) $bits=3;
			elseif($c >= 192) $bits=2;
			else return false;
			if(($i+$bits) > $len) return false;
			while($bits > 1)
			{
				$i++;
				$b=ord($str[$i]);
				if($b < 128 || $b > 191) return false;
				$bits--;
			}
		}
	}
	return true;
}

function php2js ($var)
{
	if (is_array($var))
	{
		$array = array();
		
		foreach ($var as $a_var)
			$array[] = php2js($a_var);
		
		return str_replace("\"", "'", join(",", $array));
	
	
 	}

	elseif (is_bool($var))
		return ($var ? "true" : "false");

	elseif (is_int($var) || is_integer($var) || is_double($var) || is_float($var))
		return $var;

	elseif (is_string($var))
		return "\"" .$var . "\"";
	
	else
		return false;
}

function sec2hms ($sec, $padHours = false) 
{

    // holds formatted string
    $hms = "";
    
    // there are 3600 seconds in an hour, so if we
    // divide total seconds by 3600 and throw away
    // the remainder, we've got the number of hours
    $hours = intval(intval($sec) / 3600); 

    // add to $hms, with a leading 0 if asked for
    $hms .= ($padHours) 
          ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
          : $hours. ':';
     
    // dividing the total seconds by 60 will give us
    // the number of minutes, but we're interested in 
    // minutes past the hour: to get that, we need to 
    // divide by 60 again and keep the remainder
    $minutes = intval(($sec / 60) % 60); 

    // then add to $hms (with a leading 0 if needed)
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';

    // seconds are simple - just divide the total
    // seconds by 60 and keep the remainder
    $seconds = intval($sec % 60); 

    // add to $hms, again with a leading 0 if needed
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

    // done!
    return $hms;
    
}

function isurlvalid($url, $type)
{
	global $vdrstreamdev, $vdrrecpath, $videosource, $audiosource;

	switch ($type)
	{
		case 'tv':
			break;

		case 'rec':
			// Dont allow ..
			if (preg_match("$\.\.$", $url))
				return 0;

			break;
                
		case 'media';
		case 'vid':

			if (strncmp($videosource, $url, strlen($videosource)) && strncmp($audiosource, $url, strlen($audiosource)))
				return 0;
	
			// Dont allow ..
			if (preg_match("$\.\.$", $url))
				return 0;

			break;

		default:
			return 0;
        }

	return 1;
}

if (!function_exists('json_encode'))
{
  function json_encode($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }
 
      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
      return '{' . join(',', $result) . '}';
    }
  }
}

function is_pid_running($pidfile, $pidindex=1)
{
	// Check file
	if (!file_exists($pidfile))
		return 0;

	// Check if pid file has a pid inside
	exec('cat ' .$pidfile .' | head -n' .$pidindex .' | tail -n1', $output1);
	if (!is_numeric($output1[0]))
		return 0;

	// Check if pid is running
	exec('ps ' .$output1[0], $output2);
	if(count($output2) < 2)
		return 0;

	return $output1[0];
}

?>
