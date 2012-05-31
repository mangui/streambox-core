<?php
 
// SVDRP is a class do communicate with a vdr via svdrp
class SVDRP
{
	var $cfgServer;
	var $cfgPort; 
	var $cfgTimeOut;

	var $handle;
	var $debug;
	
	function SVDRP($server = "localhost", $port=2001, $timeout = 30, $debug = 0)
	{	
		$this->cfgServer = $server;
		$this->cfgPort = $port;
		$this->cfgTimeOut = $timeout;	
		$this->debug = $debug;
		$this->handle = 0;
	} 	

	function DebugMessage($msg)
	{
		if($this->debug) echo ($msg);
	}

	function Connect()
	{
		if($this->handle) Disconnect();
		$errno = 0;
		$errstr = "";
		$this->handle = fsockopen($this->cfgServer, $this->cfgPort, $errno, $errstr, $this->cfgTimeOut);

		if(!$this->handle)
		{
			$this->DebugMessage("error $errno: $errstr");
			return false;
		}
		
		$this->DebugMessage("handle: $this->handle<br>\n");

		
		$input = fgets($this->handle,128);
		
		if(!preg_match("/^220 /", $input) || $input == "")
		{
			$this->DebugMessage("wrong welcome message: '$input'<br>\n");
			$this->Disconnect();
			return false;
		}
		
		
		$this->DebugMessage("Welcome message: $input<br><br>\n");
		
		return true;
	}

	function Command($cmd)
	{
		if(!$this->handle) return false;
		
		$ret = array();
		
		$this->DebugMessage("Kommando $cmd<br><pr"."e>");
		fputs($this->handle, $cmd . "\n");
		$s = "";
		$nline = 0;
		while(!feof($this->handle))
		{	
			 $s .= fgets($this->handle);
			 $nline++;
                         $this->DebugMessage($s);
			 if(!preg_match("/^(\\d{3})([ -])(.*)$/", $s, $data))
			 {
			 	continue;
			 }
			 
			 
			 	
			 
			 $number = $data[1];
			 // TODO: Fehlernummer bearbeiten
			 $ret[] = str_replace(array("\r", "\r\n", "\n"), '', $data[3]);
			 if($data[2] != "-" && $nline == 1) $ret =  $data[3] ;
 			 if($data[2] != "-") break; 
			 $s = "";
			 
		}
		
		$this->DebugMessage("</pr"."e>");
		return $ret;
	}

	function ListChannels($numberorname="")
	{
		if(!$this->handle) return false;
		$channels = array();	
		$lines = $this->Command("LSTC$numberorname");
		if(!$lines) return false;
		foreach($lines as $a => $l)
		{
			$a = split(":", $l);
			$name = $a[0];
			$freq = $a[1];
			$b = split(";", $name);
			$name = $b[0];
			if(!isset($b[1])) $b[1] = $name;
			$group = $b[1];

			
			$c["name"] = $name;
			$c["group"] = $group;
			$c["frequency"] = $freq;
	
			$channels[] = $c;
		
			
		}
		return $channels;
	}
	
	function Help()
	{
		return $this->Command("HELP");
	}
	function Disconnect()
	{
		if(!$this->handle) return;
		$this->Command("QUIT");
		
		fclose($this->handle);
		$this->handle = 0;
		$this->DebugMessage("disconnected");
	}
	function ClearEpg()
	{
		if(!$this->handle) return false;
		$this->Command("CLRE");
		return true;
	}
	function SwitchUp()
	{
		if(!$this->handle) return false;
		$this->Command("CHAN +");
		return true;
	}
	function SwitchDown()
	{
		if(!$this->handle) return false;
		$this->Command("CHAN -");
		return true;
	}
	function SwitchChannel($channel)
	{
		if(!$this->handle) return false;
		$this->Command("CHAN $channel");
		return true;
	}
	function DeleteChannel($id)
	{
		if(!$this->handle) return false;
		$this->Command("DELC $id");
		return true;
	}
	function DeleteRecord($id)
	{
		if(!$this->handle) return false;
		$this->Command("DELR $id");
		return true;
	}
	
	function GrabImage($filename, $type="jpeg", $quality="", $width="", $height)
	{
		if(!$this->handle) return false;
		$this->Command("GRAB $filename $type $quality $width $height");
		return true;
	}
	function HitKey($key)
	{
		if(!$this->handle) return false;
		$this->Command("HITK $key");
		return true;
	}
	function PowerOff()
	{	
		if(!$this->handle) return false;
		$this->Command("HITK Power");
		return true;
	
	}
	function GetKeys()
	{
		if(!$this->handle) return false;

		$lines = $this->Command("HITK");
		$keys = array();
		foreach($lines as $l)
		{
			if(!preg_match("/^ {4}(.*)$/", $l, $m)) continue;
			$keys[] = $m[1];
		}
		
		return $keys;
	}
	function ListEPG($pStrChannel="", $pStrTime="")
	{
		if(!$this->handle) return false;
		$lines = $this->Command("LSTE");

		$epg = array ();
		$channel = array();
		$event = array();

		$channelname = "";
		foreach($lines as $l)
		{
			preg_match("/^(.)\\s*(.*)$/", $l, $m);
			$type = $m[1];
			$text = $m[2];
			switch($type)
			{
			case 'C': // Channel
				list( $channeldata, $channelname ) = explode( ' ', $text, 2 );
				
				break;
			case 'E': // new Event
				sscanf($text, "%u %ld %d %X", $event["EventID"], $event["StartTime"], $event["Duration"], $event["TableID"]);
								
				break;
			case 'T': // Title
				$event["Title"] = $text;
				break;
			case 'S': // Short text
				$event["Shottext"] = $text;
				break;
			case 'D': // Description
				$event["Desc"] = $text;
				break;
			case 'V': // VPS
				$event["VPS"] = $text;
				break;
			case 'e': // Event end
				if ((trim($pStrTime) != '') && (( $event['StartTime'] > $pStrTime ) || ($event['StartTime'] + $event["Duration"] < $pStrTime)))
					continue;

				$channel[] = $event;
				$event = array();
				 
				break;
			case 'c': // Channel end
				if ((trim($pStrChannel) != '') && ($channelname != $pStrChannel))
					continue;

				$epg[$channelname] = $channel;
				$channel = array();
				
				break;
			}

		}
		if ((trim($pStrTime) == '') || (( $event['StartTime'] < $pStrTime ) && ($event['StartTime'] + $event["Duration"] > $pStrTime)))
			$channel[] = $event;
		 
		if ((trim($pStrChannel) != '') || ($channelname == $pStrChannel))
			$epg[$channelname] = $channel;

		return $epg;
	}

	function Message($msg)
	{
		if(!$this->handle) return false;
		$this->Command("MESG $msg");
		return true;
	}

	
	// Volume commands
	function ToggleMute()
	{
		if(!$this->handle) return false;
		$this->Command("VOLU mute");
		return true;
	}
	function VolumeUp()
	{
		if(!$this->handle) return false;
		$this->Command("VOLU +");
		return true;
	}
	function VolumeDown()
	{
		if(!$this->handle) return false;
		$this->Command("VOLU -");
		return true;
	}
	function SetVolume($v)
	{
		if(!$this->handle) return false;
		$this->Command("VOLU $v");
		return true;
	}
	function GetVolume()
	{
		if(!$this->handle) return false;
		$v = $this->Command("VOLU");
		if($v == "Audio is mute") return 0;
		if(!preg_match("/Audio volume is (.*)/", $v, $m)) return false;
		
		return $m[1];
	}
	function GetDiskStat()
	{
		if(!$this->handle) return false;
		$stat = $this->Command("STAT DISK");
		sscanf($stat, "%dMB %dMB %d%%", $FreeMUsedMB, $FreeMB, $Percent); 
		$ret["FreeMB + UsedMB"] = $FreeMUsedMB;
		$ret["FreeMB"] = $FreeMB;
		$ret["UsedMB"] = $FreeMUsedMB - $FreeMB;
		$ret["Percent"] = $Percent;
		return $ret;
	}
	function StartScan()
	{
		if(!$this->handle) return false;
		$this->Command("SCAN");
		return true;
	}
	function MoveChannel($number, $to)
	{
		if(!$this->handle) return false;
		$this->Command("MOVC $number $to");
		return true;
		
	}
	
	function DeleteTimer($id)
	{
		if(!$this->handle) return false;
		$this->Command("DELT $id");
		return true;
	}
	
	function MoveTimer($number, $to)
	{
		if(!$this->handle) return false;
		$this->Command("MOVT $number $to");
		return true;
	}
	
	function TimerOnOff($n, $state = "on")
	{
		if(!$this->handle) return false;
		//if($state == "1") $state = "on";
		//if($state == "0") $state = "off";
		//if($state == false) $state = "off";
		// if($state == true) $state = "on";
		switch($state)
		{
		case false:
		case "off":
		case "0":
		$state = "off";
		break;
		default:
		$state = "on";
		break;
		}
		
		return $this->Command("MODT $n $state");
	}	
	
	function ListTimers()
	{
		
	}
	
	function ShowMessage($msg = "")
	{
		if(!$this->handle) return false;
		return $this->Command("MESG $msg");
	}	
	
	function ListRecords()
	{
		if(!$this->handle) return false;
		
		$lines = $this->Command("LSTR");
		$records = array();
		foreach($lines as $l)
		{
		
			if(!preg_match("/^(\\d)\s(\\d*)\\.(\\d*)\\.(\\d*) (\\d*)\\:(\\d*).\s(.*)$/", $l, $m)) continue;
			$id = $m[1];
			$m["id"] 		= $m[1];
			$m["day"] 		= $m[2];
			$m["month"] 	= $m[3];
			$m["year"] 		= $m[4];
			$m["hour"] 		= $m[5];
			$m["minute"] 	= $m[6];
			$m["desc"] 		= $m[7];
			
			$records[$id] = $m;
		}
		
		return $records;

	}
	
	// TODO: perhaps better implementation
	function ListRecord($n)
	{
		if(!$this->handle) return false;
		$m = $this->Command("LSTR $n");
		return $m[0];
	}	
	
	
	
	//TODO: Implement following commands:
	/*
	
	  
	LSTT    MODT 	NEWT UPDT   
	MODC  NEWC
	    NEXT
	PUTE 
 
	*/
}

// Small Example
/*
echo "<pr"."e>";
$a = new SVDRP();
$a->Connect();
print_r($a->Help());
print_r($a->ListChannels());
$a->GetKeys();
print_r($a->GetVolume());
print_r($a->GetDiskStat());
$a->Disconnect();
*/
?>


