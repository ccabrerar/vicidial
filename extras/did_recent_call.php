<?php
# did_recent_call.php
# 
# Copyright (C) 2013  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script searches the did log for a matching phone number and returns 
# the number of matches, it was designed to be used with the ViciDial Inbound 
# DID Filter Phone Group feature in the URL search type with the following URL:
#  VARhttp://server/vicidial/did_recent_call.php?phone=--A--phone_number--B--&did=--A--did_pattern--B--&hours=48
#
# OPTIONS:
# did - you can specify the DID, use the did_pattern or leave blank to search all DIDs
# hours - number of hours you want to search back to find matches or leave blank for no limit
#
# CHANGES
# 131108-0630 - First Build (based on vtiger_phone_match.php)
#

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["phone"]))				{$phone=$_GET["phone"];}
	elseif (isset($_POST["phone"]))		{$phone=$_POST["phone"];}
if (isset($_GET["did"]))				{$did=$_GET["did"];}
	elseif (isset($_POST["did"]))		{$did=$_POST["did"];}
if (isset($_GET["hours"]))				{$hours=$_GET["hours"];}
	elseif (isset($_POST["hours"]))		{$hours=$_POST["hours"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

###############################################################
##### START SYSTEM_SETTINGS INFO LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
$ss_conf_ct = mysqli_num_rows($rslt);
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	}
##### END SYSTEM_SETTINGS INFO LOOKUP #####
#############################################################

if ($non_latin < 1)
	{
	$phone=preg_replace('/[^-_0-9a-zA-Z]/','',$phone);
	$did=preg_replace('/[^-_0-9a-zA-Z]/','',$did);
	}
else
	{
	$phone = preg_replace("/'|\"|\\\\|;/","",$phone);
	$did = preg_replace("/'|\"|\\\\|;/","",$did);
	}
$hours=preg_replace('/[^0-9]/','',$hours);


$phone_count=0;

if (strlen($phone) > 6)
	{
	if ( ($did == 'ALL') or ($did == '') )
		{$didSQL='';}
	else
		{$didSQL=" and extension='$did'";}
	if ( ($hours == 'ALL') or ($hours == '') )
		{$hoursSQL=' and (call_date < (NOW() - INTERVAL 10 SECOND))';}
	else
		{$hoursSQL=" and (call_date < (NOW() - INTERVAL 10 SECOND) and call_date > (NOW() - INTERVAL $hours HOUR))";}

	$stmt = "SELECT count(*) FROM vicidial_did_log where caller_id_number='$phone' $didSQL $hoursSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$did_call_ct = mysqli_num_rows($rslt);
	if ($did_call_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$phone_count =	$row[0];
		}
	if ( ($DB > 0) and ($webroot_writable > 0) )
		{
		$fp = fopen ("./did_recent_call_log.txt", "a");
		fwrite ($fp, "$date|$phone_count|$did_call_ct|$stmt\n");
		fclose($fp);
		}
	}

echo "$phone_count\n";

?>
