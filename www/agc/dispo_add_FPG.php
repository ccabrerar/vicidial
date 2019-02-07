<?php
# dispo_add_FPG.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the "Dispo URL" field of a campaign
# or in-group. It adds the phone_number of the call to a designated inbound 
# Filter Phone Group
#
# This script is part of the API group and any modifications of data are
# logged to the vicidial_api_log table.
#
# Example of what to put in the Dispo URL field:
# VARhttp://192.168.1.1/agc/dispo_add_FPG.php?phone_number=--A--dialed_number--B--&lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&user=--A--user--B--&pass=--A--pass--B--&FPG_id=BLOCKSALE&sale_status=SALE---SSALE---XSALE&log_to_file=1
# 
# Definable Fields: (other fields should be left as they are)
# - log_to_file -	(0,1) if set to 1, will create a log file in the agc directory
# - sale_status -	(SALE---XSALE) a triple-dash "---" delimited list of the statuses that are to be moved
# - FPG_id -		(999,etc...) the Filter Phone Group ID that you want the phone number to be inserted into
#
# CHANGES
# 150724-1657 - First Build
# 170526-2305 - Added additional variable filtering
#

$api_script = 'add_FPG';

header ("Content-type: text/html; charset=utf-8");

require_once("dbconnect_mysqli.php");
require_once("functions.php");

$filedate = date("Ymd");
$filetime = date("H:i:s");
$IP = getenv ("REMOTE_ADDR");
$BR = getenv ("HTTP_USER_AGENT");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["phone_number"]))			{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["sale_status"]))			{$sale_status=$_GET["sale_status"];}
	elseif (isset($_POST["sale_status"]))	{$sale_status=$_POST["sale_status"];}
if (isset($_GET["dispo"]))					{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))			{$dispo=$_POST["dispo"];}
if (isset($_GET["FPG_id"]))					{$FPG_id=$_GET["FPG_id"];}
	elseif (isset($_POST["FPG_id"]))		{$FPG_id=$_POST["FPG_id"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["pass"]))					{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))			{$pass=$_POST["pass"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["log_to_file"]))			{$log_to_file=$_GET["log_to_file"];}
	elseif (isset($_POST["log_to_file"]))	{$log_to_file=$_POST["log_to_file"];}


#$DB = '1';	# DEBUG override
$US = '_';
$TD = '---';
$STARTtime = date("U");
$NOW_TIME = date("Y-m-d H:i:s");
$sale_status = "$TD$sale_status$TD";
$search_value='';
$match_found=0;
$k=0;

# filter variables
$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);
$phone_number = preg_replace('/[^-_0-9a-zA-Z]/','',$phone_number);
$FPG_id = preg_replace("/\'|\"|\\\\|;| /","",$FPG_id);
$lead_id = preg_replace('/[^0-9]/','',$lead_id);

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02001',$user,$server_ip,$session_name,$one_mysql_log);}
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	}

if ($DB>0) {echo "$lead_id|$search_field|$campaign_check|$sale_status|$dispo|$new_status|$user|$pass|$DB|$log_to_file|\n";}

if (preg_match("/$TD$dispo$TD/",$sale_status))
	{$match_found=1;}

if ($match_found > 0)
	{
	if ($non_latin < 1)
		{
		$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
		$pass=preg_replace("/[^-_0-9a-zA-Z]/","",$pass);
		}

	$session_name = preg_replace("/\'|\"|\\\\|;/","",$session_name);
	$server_ip = preg_replace("/\'|\"|\\\\|;/","",$server_ip);

	if (preg_match("/NOAGENTURL/",$user))
		{
		$PADlead_id = sprintf("%010s", $lead_id);
		if ( (strlen($pass) > 15) and (preg_match("/$PADlead_id$/",$pass)) )
			{
			$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));

			$stmt="SELECT count(*) from vicidial_log_extended where caller_code='$pass' and call_date > \"$four_hours_ago\";";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$authlive=$row[0];
			$auth=$row[0];
			if ($authlive < 1)
				{
				echo _QXZ("Call Not Found:")." 2|$user|$pass|$authlive|\n";
				exit;
				}
			}
		else
			{
			echo _QXZ("Invalid Call ID:")." 1|$user|$pass|$PADlead_id|\n";
			exit;
			}
		}
	else
		{
		$auth=0;
		$auth_message = user_authorization($user,$pass,'',0,0,0,0);
		if ($auth_message == 'GOOD')
			{$auth=1;}

		$stmt="SELECT count(*) from vicidial_live_agents where user='$user';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$authlive=$row[0];
		}

	if ( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0) or ($authlive==0))
		{
		echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth|$authlive|$auth_message|\n";
		exit;
		}

	if ( (strlen($phone_number) > 2) and (strlen($FPG_id) > 0) )
		{
		$search_count=0;
		$stmt = "SELECT count(*) FROM vicidial_filter_phone_groups where filter_phone_group_id='$FPG_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$sc_ct = mysqli_num_rows($rslt);
		if ($sc_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$FPG_count = $row[0];
			}

		if ($FPG_count > 0)
			{
			$stmt="INSERT INTO vicidial_filter_phone_numbers SET filter_phone_group_id='$FPG_id', phone_number='$phone_number';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);

			$SQL_log = "$stmt|$stmtB|$CBaffected_rows|";
			$SQL_log = preg_replace('/;/','',$SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='dispo_add_FPG',value='$phone_number',result='$affected_rows',result_reason='$FPG_id',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
			$rslt=mysql_to_mysqli($stmt, $link);

			$MESSAGE = _QXZ("DONE: %1s match found, %2s inserted into %3s with %4s status",0,'',$FPG_count,$affected_rows,$FPG_id,$dispo);
			echo "$MESSAGE\n";
			}
		else
			{
			$MESSAGE = _QXZ("DONE: no filter phone group found %1s",0,'',$FPG_id);
			echo "$MESSAGE\n";
			}
		}
	else
		{
		$MESSAGE = _QXZ("DONE: %1s or %2s are invalid",0,'',$phone_number,$FPG_id);
		echo "$MESSAGE\n";
		}
	}
else
	{
	$MESSAGE = _QXZ("DONE: dispo is not a sale status: %1s",0,'',$dispo);
	echo "$MESSAGE\n";
	}

if ($log_to_file > 0)
	{
	$fp = fopen ("./add_FPG.txt", "a");
	fwrite ($fp, "$NOW_TIME|$k|$phone_number|$FPG_id|$sale_status|$dispo|$user|XXXX|$DB|$log_to_file|$MESSAGE|\n");
	fclose($fp);
	}
