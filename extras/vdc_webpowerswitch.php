<?php
# vdc_webpowerswitch.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to send a URL command to turn off or on an outlet on
# the WebPowerSwitch appliance
#
# !!! IMPORTANT !!! You must change the "$command" line below to match the IP and user/pass for your webpowerswitch
#
# CHANGELOG:
# 180515-1711 - First build of script
#

$version = '2.14-1';
$build = '180515-1711';

require("dbconnect_mysqli.php");

$query_string = getenv("QUERY_STRING");

if (isset($_GET["outlet"]))				{$outlet=$_GET["outlet"];}
	elseif (isset($_POST["outlet"]))	{$outlet=$_POST["outlet"];}
if (isset($_GET["stage"]))				{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))		{$stage=$_POST["stage"];}


header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

$txt = '.txt';
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$MT[0]='';

$outlet=preg_replace("/[^-_0-9a-zA-Z]/","",$outlet);
$stage=preg_replace("/[^-_0-9a-zA-Z]/","",$stage);

if ( (strlen($outlet) < 1) or (strlen($stage) < 2) )
	{
	echo "ERROR: invalid outlet or stage: |$outlet|$stage|\n";
	}
else
	{
	$command = "/usr/bin/curl -s -o /tmp/Xtest http://cycle:test@192.168.1.157:80/outlet\?".$outlet.'='.$stage;
	exec($command);
	echo "Command sent: |$outlet|$stage|";
	}

exit;

?>
