<?php
/**
 * This file contain everything needed to communicate with a VDR instance
 * This is merely a rewrite of the original class which was written in
 * PHP4 and published some years ago.
 * I wasn't able to find the original author name in order to give him
 * credits. 
 *
 * PHP version 5.1+
 *
 * @author  Philippe Gaultier <pgaultier[at]gmail.com>
 * @license BSD License http://fr.wikipedia.org/wiki/Licence_BSD
 * @link    http://www.linuxtv.org/vdrwiki/index.php/Svdrp
 */
require_once ('firephp.php');
$a = new SVDRP ( null, null, null, false );
$a->connect ();
$a->listChannels ();
/**
 * SVDRP class
 *
 * This class provide access to VDR SVDRP service
 *
 * @package   utilities
 * @author    Philippe Gaultier <pgaultier[at]gmail.com>
 * @since     1.0
 */
class SVDRP {
	private $_server = "localhost";
	private $_port = 2001;
	private $_timeOut = 30;
	
	private $_handle = false;
	private $_debug = false;
	
	private $_firePhp = null;
	
	/**
	 * Class constructor
	 * 
	 * @param string  $server  the server to connect to (IP or name)
	 * @param integer $port    the port number
	 * @param integer $timeOut timeout before closing the connection
	 * @param boolean $debug   de/activate debug functions
	 * 
	 * @return SVDRP
	 */
	public function __construct($server = null, $port = null, $timeOut = null, $debug = null) {
		if ($server !== null) {
			$this->_server = $server;
		}
		if ($port !== null) {
			$this->_port = $port;
		}
		if ($timeOut !== null) {
			$this->_timeOut = $timeOut;
		}
		if ($debug !== null) {
			$this->_debug = $debug;
		}
		if (class_exists ( 'FirePHP' )) {
			$this->_firePhp = FirePHP::getInstance ( true );
			$this->_firePhp->setEnabled ( $this->_debug );
			$this->group ( 'new ' . __CLASS__ . '($server, $port, $timeOut, $debug)' );
			$this->info ( $this->_server, '$server' );
			$this->info ( $this->_port, '$port' );
			$this->info ( $this->_timeOut, '$timeOut' );
			$this->info ( $this->_debug, '$debug' );
			$this->trace ( 'Object created' );
			$this->groupEnd ();
		} else {
			$this->_debug = false;
		}
	}
	private function group($title) {
		if ($this->_debug === true) {
			$this->_firePhp->group ( $title );
		}
	}
	private function groupEnd() {
		if ($this->_debug === true) {
			$this->_firePhp->groupEnd ();
		}
	}
	private function trace($message) {
		if ($this->_debug === true) {
			$this->_firePhp->trace ( 'Class ' . __CLASS__ . ' Trace : ' . $message );
		}
	}
	private function dump($name, $var) {
		if ($this->_debug === true) {
			$this->_firePhp->dump ( $name, $var );
		}
	}
	private function log($message, $label = null) {
		if ($this->_debug === true) {
			$this->_firePhp->log ( $message, $label );
		}
	}
	private function info($message, $label = null) {
		if ($this->_debug === true) {
			$this->_firePhp->info ( $message, $label );
		}
	}
	private function warn($message, $label = null) {
		if ($this->_debug === true) {
			$this->_firePhp->warn ( $message, $label );
		}
	}
	private function error($message, $label = null) {
		if ($this->_debug === true) {
			$this->_firePhp->error ( $message, $label );
		}
	}
	public function debugMessage($msg) {
		if ($this->_debug === true) {
			//TODO: do a better debug (use firePHP or something else)
		// echo ($msg);
		}
	}
	
	public function connect() {
		$result = false;
		if ($this->_handle !== false) {
			$this->disconnect ();
		}
		$errno = 0;
		$errstr = "";
		// we use @ in order to avoid warning if server does not answer / allow the connection
		$this->_handle = @fsockopen ( $this->_server, $this->_port, &$errno, &$errstr, $this->_timeOut );
		$this->group ( __METHOD__ . '()' );
		if ($this->_handle !== false) {
			$this->info ( 'Handle created', 'SVDRP' );
			$input = fgets ( $this->_handle, 128 );
			
			if ((preg_match ( "/^220 /", $input ) === true) && ($input != "")) {
				$this->info ( 'Answer OK : ' . $input, 'SVDRP' );
				$result = true;
			} else {
				$this->warn ( 'Answer KO : ' . $input, 'SVDRP' );
				$this->disconnect ();
			}
		} else {
			$this->error ( 'fsockopen error ' . $errno . ' (' . $errstr . ')', 'SVDRP' );
		}
		$this->groupEnd ();
		return $result;
	}
	
	public function sendCommand($cmd) {
		$result = false;
		if ($this->_handle !== false) {
			$result = array ();
			$this->group ( __METHOD__ . '($cmd)' );
			$this->info ( $cmd, '$cmd' );
			fputs ( $this->_handle, $cmd . "\n" );
			$answer = "";
			$nbLines = 0;
			while ( $answer .= fgets ( $this->_handle, 2048 ) ) {
				$nbLines ++;
				$this->info ( $answer );
				$data = null;
				if (preg_match ( "/^(\\d{3})([ -])(.*)$/", $answer, $data ) === false) {
					continue;
				}
				$number = $data [1];
				$result [] = trim ( $data [3] );
				/**
			 	if(($data[2] !== "-") && ($nbLines === 1)) {
			 		$result =  trim($data[3]) ;
			 	}
				 **/
				if ($data [2] != "-") {
					break;
				}
				$answer = "";
			}
			$this->trace ( 'Command sent, answer received' );
			$this->groupEnd ();
		}
		return $result;
	}
	
	public function listChannels($channelNumberOrName = "") {
		$lines = $this->sendCommand ( 'LSTC ' . $channelNumberOrName );
		if ($lines !== false) {
			$channels = array ();
			foreach ( $lines as $l ) {
				list ( $fullName, $frequency, $parameters, $source, $symbolRate, $videoPid, $audioPid, $teletextPid, $conditionalAccess, $serviceId, $networkId, $transportId, $radioId ) = split ( ":", $l );
				list ( $shortName, $provider ) = split ( ";", $fullName );
				$channels [] = array ('shortName' => $shortName, 'provider' => $provider, 'frequency' => $frequency, 'parameters' => $parameters, 'source' => $source, 'symbolRate' => $symbolRate, 'videoPid' => $videoPid, 'audioPid' => $audioPid, 'teletextPid' => $teletextPid, 'conditionalAccess' => $conditionalAccess, 'serviceId' => $serviceId, 'networkId' => $networkId, 'transportId' => $transportId, 'radioId' => $radioId, 'group' => $provider, //XXX: Compatibility
'name' => $shortName );//XXX: Compatibility

			}
			$lines = $channels;
		}
		return $lines;
	}
	
	public function help() {
		return $this->sendCommand ( 'HELP' );
	}
	public function disconnect() {
		if ($this->_handle !== false) {
			$this->sendCommand ( 'QUIT' );
			fclose ( $this->_handle );
			$this->_handle = false;
			$this->debugMessage ( "disconnected" );
		}
	}
	public function clearEpg() {
		$result = $this->sendCommand ( 'CLRE' );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	public function switchUp() {
		return $this->switchChannel ( '+' );
	}
	public function switchDown() {
		return $this->switchChannel ( '-' );
	}
	public function switchChannel($channel) {
		$result = $this->sendCommand ( 'CHAN ' . $channel );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	public function deleteChannel($channelId) {
		$result = $this->sendCommand ( 'DELC ' . $channelId );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	public function deleteRecord($recordId) {
		$result = $this->sendCommand ( 'DELR ' . $recordId );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	
	public function grabImage($fileName, $type = 'jpeg', $quality = '', $width = '', $height) {
		$result = $this->sendCommand ( 'GRAB ' . $fileName . ' ' . $type . ' ' . $quality . ' ' . $width . ' ' . $height );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	public function hitKey($key) {
		$result = $this->sendCommand ( 'HITK ' . $key );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	public function powerOff() {
		return $this->hitKey ( 'HITK Power' );
	}
	public function getKeys() {
		$lines = $this->sendCommand ( 'HITK' );
		if ($lines !== false) {
			$keys = array ();
			foreach ( $lines as $l ) {
				if (preg_match ( "/^ {4}(.*)$/", $l, $m ) === false) {
					continue;
				}
				$keys [] = $m [1];
			}
			$lines = $keys;
		}
		return $lines;
	}
	public function listEPG($pStrChannel = "", $pStrTime = "") {
		$lines = $this->sendCommand ( "LSTE" );
		if ($lines !== false) {
			$epg = array ();
			$channel = array ();
			$event = array ();
			$channelname = "";
			foreach ( $lines as $line ) {
				if (preg_match ( "/^(.)\\s*(.*)$/", $line, $matches ) === true) {
					$type = $matches [1];
					$text = $matches [2];
					switch ($type) {
						case 'C' : // Channel
							list ( $channeldata, $channelname ) = explode ( ' ', $text, 2 );
							break;
						case 'E' : // new Event
							sscanf ( $text, "%u %ld %d %X", $event ["EventID"], $event ["StartTime"], $event ["Duration"], $event ["TableID"] );
							break;
						case 'T' : // Title
							$event ["Title"] = $text;
							break;
						case 'S' : // Short text
							$event ["Shottext"] = $text;
							break;
						case 'D' : // Description
							$event ["Desc"] = $text;
							break;
						case 'V' : // VPS
							$event ["VPS"] = $text;
							break;
						case 'e' : // Event end
							if ((trim ( $pStrTime ) != '') && (($event ['StartTime'] > $pStrTime) || ($event ['StartTime'] + $event ["Duration"] < $pStrTime))) {
								continue;
							}
							$channel [] = $event;
							$event = array ();
							break;
						case 'c' : // Channel end
							if ((trim ( $pStrChannel ) != '') && ($channelname != $pStrChannel)) {
								continue;
							}
							$epg [$channelname] = $channel;
							$channel = array ();
							break;
					}
				}
			}
			if ((trim ( $pStrTime ) == '') || (($event ['StartTime'] < $pStrTime) && ($event ['StartTime'] + $event ["Duration"] > $pStrTime))) {
				$channel [] = $event;
			}
			if ((trim ( $pStrChannel ) != '') || ($channelname == $pStrChannel)) {
				$epg [$channelname] = $channel;
			}
			$lines = $epg;
		}
		return $lines;
	}
	
	public function sendMessage($message) {
		$result = $this->sendCommand ( 'MESG ' . $message );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	/**
	 * Toggle volume
	 * 
	 * @return boolean
	 */
	public function toggleMute() {
		return $this->setVolume ( 'mute' );
	}
	/**
	 * Raise sound volume
	 * 
	 * @return boolean
	 */
	public function setVolumeUp() {
		return $this->setVolume ( '+' );
	}
	/**
	 * lower sound volume
	 * 
	 * @return boolean
	 */
	public function setVolumeDown() {
		return $this->setVolume ( '-' );
	}
	/**
	 * Set volume to a specific value
	 * 
	 * @param integer $volume new sound volume value
	 * 
	 * @return boolean
	 */
	public function setVolume($volume) {
		$result = $this->sendCommand ( 'VOLU ' . $volume );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	/**
	 * Get current volume value
	 * 
	 * @return boolean|integer
	 */
	public function getVolume() {
		$result = $this->sendCommand ( 'VOLU' );
		if ($result !== false) {
			$volumeData = array_shift ( $result );
			if ($volumeData == "Audio is mute") {
				$result = 0;
			} elseif (preg_match ( "/Audio volume is (.*)/", $volumeData, $matches ) === true) {
				$result = $matches [1];
			} else {
				$result = false;
			}
		}
		return $result;
	}
	/**
	 * Get current disk information
	 * 
	 * @return boolean|array
	 */
	public function getDiskInfo() {
		$res = $this->sendCommand ( 'STAT DISK' );
		$result = $this->sendCommand ( 'STAT DISK' );
		if ($result !== false) {
			$result = array_shift ( $result );
			sscanf ( $result, "%dMB %dMB %d%%", $data ['overall'], $data ['free'], $data ['percent'] );
			$data ['used'] = $data ['overall'] - $data ['free'];
			$result = $data;
		}
		return $result;
	}
	/**
	 * Force a new scan
	 * 
	 * @return boolean
	 */
	public function startScan() {
		$result = $this->sendCommand ( 'SCAN' );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	public function moveChannel($channelNumber, $target) {
		$result = $this->sendCommand ( 'MOVC ' . $channelNumber . ' ' . $target );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	
	public function deleteTimer($timerId) {
		$result = $this->sendCommand ( 'DELT ' . $timerId );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	
	public function moveTimer($timerNumber, $target) {
		$result = $this->sendCommand ( 'MOVT ' . $timerNumber . ' ' . $target );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	/**
	 * Change the status of a specific timer
	 * 
	 * @param string  $timerId timer id
	 * @param boolean $state   status to set (true : on / false : off)
	 * 
	 * @return boolean
	 */
	public function toggleTimer($timerId, $state = true) {
		switch ($state) {
			case false :
			case 'off' :
			case 0 :
				$state = 'off';
				break;
			default :
				$state = 'on';
				break;
		}
		$result = $this->sendCommand ( 'MODT ' . $timerId . ' ' . $state );
		if ($result !== false) {
			$result = true;
		}
		return $result;
	}
	
	/**
	 * List all timers
	 * 
	 * @return void
	 */
	public function listTimers() {
		//TODO: implement timer listing
	}
	
	/**
	 * List all recordings with specific data
	 * 
	 * @return array
	 */
	public function listRecords() {
		$lines = $this->sendCommand ( 'LSTR' );
		if ($lines !== false) {
			$records = array ();
			foreach ( $lines as $line ) {
				if (preg_match ( '/^(\\d)\s(\\d*)\\.(\\d*)\\.(\\d*) (\\d*)\\:(\\d*).\s(.*)$/', $line, $matches ) == false) {
					continue;
				}
				$records [$matches [1]] = array ('id' => $matches [1], 'day' => $matches [2], 'month' => $matches [3], 'year' => $matches [4], 'hour' => $matches [5], 'minute' => $matches [6], 'desc' => $matches [7] );
			}
			$lines = $records;
		}
		return $lines;
	}
	
	/**
	 * List recording by Id
	 * 
	 * @param string $recordId
	 * 
	 * @return string
	 */
	public function listRecord($recordId) {
		//TODO: perhaps better implementation and check if we do not need array_shift
		$lines = $this->sendCommand ( 'LSTR ' . $recordId );
		if ($lines !== false) {
			$lines = array_shift ( $lines );
			$lines = $lines [0];
		}
		return $lines;
	}
	
//TODO: Implement following commands:
/**
		LSTT
		MODT
		NEWT
		UPDT   
		MODC
		NEWC
		NEXT
		PUTE 
 **/
}

