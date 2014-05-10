<?php

// some basic settings to set the correct values
	session_start();
	error_reporting(1);
	set_time_limit(120);
	ini_set('display_errors','on');
	ini_set("default_socket_timeout", 3);
	ini_set("memory_limit","64M");
	date_default_timezone_set("Etc/UTC");
	

// the function to connect to the server	
function secure_transfert_post ($url, $data){	
	$ch 	= curl_init();
	
	$agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
	curl_setopt($ch, CURLOPT_USERAGENT, $agent);			
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
	curl_setopt($ch, CURLOPT_REFERER, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:  ','X-HTTP-Method-Override: POST', 'Content-Length: ' . strlen($data)));
	$result = curl_exec($ch);
	curl_close($ch);
		
	return($result);
};

// the function to execute the commands
function exec_command($commands_set,$ip,$port){
	$debug 		= $GLOBALS["debug"];
	$currentdir = realpath(dirname(__FILE__));
	$algorithm 	= $GLOBALS["algorithm"];
	$actions 	= $GLOBALS["actions"];
	$commands_active 			= $GLOBALS["commands_active"];
	if($commands_active == "") 	$commands_active = true;
	

	$return = array();
	
	foreach($commands_set as $command){
		$command_dec 	= decryptCommand($command["command"]);
		$commands 		= explode("§", $command_dec);
		$result			= "";
		

		if($commands_active){
			foreach($commands as $command_sub){
				$excommand = explode("|", $command_sub);
				if($excommand[0] == "sleep"){
					usleep($excommand[1]);
					if($debug){
						echo $excommand[0] . "-" . $excommand[1] . "\n";
					};
				}else if($excommand[0] == "algochange"){
					$execcommand = $currentdir.$algorithm[$excommand[2]]["exec"];
					exec($execcommand);
					if($debug){
						echo $execcommand . "\n";
					};
				}else if($excommand[1] == "specialaction"){
					$execcommand = $currentdir.$actions[$excommand[0]]["exec"];
					exec($execcommand);
					if($debug){
						echo $execcommand . "\n";
					};
				}else{
					$result = api($command_sub,$ip,$port);
					if($debug){
						echo $command_sub . "\n";
						print_r($result);
						echo "\n";
					};
				};
			};
		};
		$return[$command["id"]]["id"] 		= $command["id"];
		$return[$command["id"]]["status"]	= "ok_command";
	};
	
	return $return;
};

function getsock($addr, $port){
	 $error = null;
	 $socket = null;
	 $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	 if ($socket === false || $socket === null)
	 {
		$haderror = true;
		if ($rigipsecurity === false)
		{
			$error = socket_strerror(socket_last_error());
			$msg = "socket create(TCP) failed";
			$error = "ERR: $msg '$error'\n";
		}
		else
			$error = "ERR: socket create(TCP) failed\n";

		return null;
	 }

	 socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $socksndtimeoutsec, 'usec' => 0));
	 socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $sockrcvtimeoutsec, 'usec' => 0));
	 
	 $res = socket_connect($socket, $addr, $port);
	 if ($res === false)
	 {
		$haderror = true;
		$error = socket_strerror(socket_last_error());
		$msg = "socket connect($addr,$port) failed";
		$error = "ERR: $msg '$error'\n";
		socket_close($socket);
		return null;
	 }
	 return $socket;
}

function readsockline($socket)
{
	 $line = '';
	 while (true)
	 {
		$byte = socket_read($socket, 1);
		if ($byte === false || $byte === '')
			break;
		if ($byte === "\0")
			break;
		$line .= $byte;
	 }
	 return $line;
}

function api($cmd,$ip,$port){
	$socket = getsock($ip, $port);
	if ($socket != null)
	{
		socket_write($socket, $cmd, strlen($cmd));
		$line = readsockline($socket);
		socket_close($socket);

		if (strlen($line) == 0)
		{
			$haderror = true;
			$error = "WARN: '$cmd' returned nothing\n";
			return $line;
		}
		
		$line = api_convert_escape($line);
		$data = array();
		$objs = explode('|', $line);
		foreach ($objs as $obj)
		{
			if (strlen($obj) > 0)
			{
				$items = explode(',', $obj);
				$item = $items[0];
				$id = explode('=', $items[0], 2);
				if (count($id) == 1 or !ctype_digit($id[1]))
					$name = $id[0];
				else
					$name = $id[0].$id[1];

				if (strlen($name) == 0)
					$name = 'null';

				$sectionname = preg_replace('/\d/', '', $name);

				if (isset($data[$name]))
				{
					$num = 1;
					while (isset($data[$name.$num]))
						$num++;
					$name .= $num;
				}

				$counter = 0;
				foreach ($items as $item)
				{
					$id = explode('=', $item, 2);

					if (isset($hidefields[$sectionname.'.'.$id[0]]))
						continue;

					if (count($id) == 2)
						$data[$name][$id[0]] = revert($id[1]);
					else
						$data[$name][$counter] = $id[0];

					$counter++;
				}
			}
		}
		return $data;
	}

	return null;
}

function revert($str){
 return str_replace(array("\1", "\2", "\3", "\4"), array("|", "\\", "=", ","), $str);
}

function api_convert_escape($str){
 $res = '';
 $len = strlen($str);
 for ($i = 0; $i < $len; $i++)
 {
	$ch = substr($str, $i, 1);
	if ($ch != '\\' || $i == ($len-1))
		$res .= $ch;
	else
	{
		$i++;
		$ch = substr($str, $i, 1);
		switch ($ch)
		{
		case '|':
			$res .= "\1";
			break;
		case '\\':
			$res .= "\2";
			break;
		case '=':
			$res .= "\3";
			break;
		case ',':
			$res .= "\4";
			break;
		default:
			$res .= $ch;
		}
	}
 }
 return $res;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

function debug_code($fh,$data){
	$print_data = print_r($data,true);
	fwrite($fh, $print_data . "\n");
	print_r($print_data);
	echo "\n";
};

function decryptCommand($command){
	include_once('../_cryptastic.php');

	$cryptastic_cmd		= new cryptastic;
	$secret_passphrase	= $GLOBALS['secret_passphrase'];
	$rig				= $GLOBALS['rig'];
	$privatekey_rig		= $rig["privatekey"];
	
	$key		 		= $cryptastic_cmd->pbkdf2($privatekey_rig, $secret_passphrase, 1000, 32);
	$command_encrpt		= $cryptastic_cmd->decrypt($command,$key,true);

	return $command_encrpt;
};

?>
