<?php
		include_once('_functions.php');
		include_once('_cryptastic.php');
		include_once('_config.php');
		
		$currentdir 				= realpath(dirname(__FILE__));
		$timenow 					= date("YmdHis");
		$datesave 					= date("Ymd");

			// get the private key and the uniqueid from the rig
			$privatekey = $rig["privatekey"];
			$uniqueid 	= $rig["uniqueid"];

			if($debug){
				$myFile 		= $currentdir . "/log/cgmonitor-log-sync-v2-".$timenow.".log";
				$fh 			= fopen($myFile, 'a') or die("can't open file");
				debug_code($fh,"Debug started....");	
			};			
			
			// change the url to a good url
			if(substr($webserverurl, -6) == "/sync/") 	$webserverurl = substr($webserverurl,0,-6);
			if(substr($webserverurl, -1) == "/") 		$webserverurl = substr($webserverurl,0,-1);
			$url 					= $webserverurl ."/sync/sync_v2.php?uniqueid=". $uniqueid;

			// start a new cryptastic
			$cryptastic 			= new cryptastic;
			// for the first connection we use our uniqueid as salt (key)
			$key 					= $cryptastic->pbkdf2($privatekey, $uniqueid, 1000, 32);

			// generate a salt key for this session
			$salt 					= generateRandomString(40);
			
			// make the data and send it all to the server
			$data_key_send["key"]	= $salt;			
			$data_key_send["time"]	= $timenow;			

			$data_key_send			= $cryptastic->encrypt($data_key_send,$key,true);
			$data_key_send 			= "key=".urlencode($data_key_send);
			$result_key 			= secure_transfert_post($url, $data_key_send);
			if($debug) debug_code($fh,$result_key);

			// make the correct key to use for this session
			$key 					= $cryptastic->pbkdf2($privatekey, $salt, 1000, 32);

			// decrypt the answer from the server (commands)
			$result_key 			= $cryptastic->decrypt($result_key,$key,true);
			$result_key				= json_decode($result_key,TRUE);
			if($debug) debug_code($fh,$result_key);

			if($result_key["result"] != "key_good") exit;

			// look if there are commands and execute them
			if(isset($result_key["commands"])){
				$data_cmd_send["command"] 	= exec_command($result_key["commands"],$rig["ip"],$rig["port"]);
				$data_cmd_send["time"]		= $timenow;
				
				// sent them to the server
				$data_cmd_send				= json_encode($data_cmd_send);
				$data_cmd_send				= $cryptastic->encrypt($data_cmd_send,$key,true);
				$data_cmd_send				= "command=".urlencode($data_cmd_send);
				$result_key_command			= secure_transfert_post($url, $data_cmd_send);
				if($debug) debug_code($fh,$result_key_command);

				// decrypt the answer from the server (commands)
				$result_key_command 		= $cryptastic->decrypt($result_key_command,$key,true);
				$result_key_command			= json_decode($result_key_command,TRUE);
				if($debug) debug_code($fh,$result_key_command);

				if($result_key_command["result"] != "key_good") exit;
			};
			
			// for each api call make the call and put in data
			foreach ($result_key["api"] as $call) {
				$result = api($call,$rig["ip"],$rig["port"]);
				$result_back = $result;
				$data[$call] = $result_back;
				if($debug) debug_code($fh,$result_back);
			}
			
			// sent them to the server
			$data["time"]			= $timenow;			
			
			$data 					= json_encode($data);
			$data 					= $cryptastic->encrypt($data,$key,true);
			$data 					= "data=".urlencode($data);
			$result_key 			= secure_transfert_post($url, $data);
			if($debug) debug_code($fh,$result_key);
			
			$result_key 			= $cryptastic->decrypt($result_key,$key,true);
			$result_key				= json_decode($result_key,TRUE);
			if($debug) debug_code($fh,$result_key);
			if($result_key["result"] != "key_good") exit;
			
			// look if there are more commands and execute ( temp and fan speed intensity)
			if(isset($result_key["commands"])){
				$data_cmd_send["command"] 	= exec_command($result_key["commands"],$rig["ip"],$rig["port"]);
				$data_cmd_send["time"]		= $timenow;
				
				// sent them to the server
				$data_cmd_send				= json_encode($data_cmd_send);
				$data_cmd_send				= $cryptastic->encrypt($data_cmd_send,$key,true);
				$data_cmd_send				= "command=".urlencode($data_cmd_send);
				$result_key_command			= secure_transfert_post($url, $data_cmd_send);
				if($debug) debug_code($fh,$result_key_command);

				// decrypt the answer from the server (commands)
				$result_key_command 		= $cryptastic->decrypt($result_key_command,$key,true);
				$result_key_command			= json_decode($result_key_command,TRUE);
				if($debug) debug_code($fh,$result_key_command);

				if($result_key_command["result"] != "key_good") exit;

			};			
?>
