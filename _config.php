<?php

// your weburl where you need to login to the site (without index.php exp: http://demo.cg-monitor.com/)
$webserverurl 			= "<your_web_url>";

$secret_passphrase		= "<your_secret_passphrase_from_www_configfile>";

// all your rigs, begin with 1 (you can also install on every rig, better)
$rig["ip"] 				= "127.0.0.1";
$rig["port"] 			= "4028";
$rig["uniqueid"] 		= '<your_rigs_uniqueid>';
$rig["privatekey"] 		= '<your_rigs_privatekey>';

// to change algorithms you need to add here you referer from the web portal

$algorithm[1]["exec"] 	= "/com-linux/algo-1-start.sh";
$algorithm[2]["exec"] 	= "/com-linux/algo-2-start.sh";
$algorithm[3]["exec"] 	= "/com-linux/algo-3-start.sh";

// same for windows, change the bat files to the correct place

// $algorithm[1]["exec"] 	= "/com-windows/algo-1-start.vbs";
// $algorithm[2]["exec"] 	= "/com-windows/algo-2-start.vbs";
// $algorithm[3]["exec"] 	= "/com-windows/algo-3-start.vbs";

// if you want to reboot you rig here you can change the code (windows or linux is the same command, just change to .bat)
$actions["reboot"]["exec"]		= "/com-linux/reboot.sh";

// for windows remove // before this line
// $actions["reboot"]["exec"]	= "/com-windows/reboot.bat";

// set this action to true or false to get debug information
$debug = false;

// set this action to true or false to get commands to you cgminer
$commands_active = true;