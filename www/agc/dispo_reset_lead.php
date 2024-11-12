<?php
# dispo_reset_lead.php
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the "Dispo URL" field of a campaign
# or in-group (although it can also be used in the "No Agent Call URL" field). 
# It should take in the lead_id to check for the same lead_id
# in order to change it's called_since_last_reset to 'N'. The
# sale_status field is a list of statuses separated by three dashes each '---'
# which contain the statuses for which the process should be run.
#
# This script is part of the API group and any modifications of data are
# logged to the vicidial_api_log table.
#
# This script limits the number of altered leads to 1 per instance and it will
# not run if the search field of the lead is empty.
#
# Example of what to put in the Dispo URL field:
# VARhttp://192.168.1.1/agc/dispo_reset_lead.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&user=--A--user--B--&pass=--A--pass--B--9&sale_status=SALE---SSALE---XSALE&log_to_file=1
# 
# Another example of what to put in the Dispo URL field(using status exclude with talk trigger):
# VARhttp://192.168.1.1/agc/dispo_reset_lead.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&called_count=--A--called_count--B--&user=--A--user--B--&pass=--A--pass--B--&sale_status=DNC---BILLNW---POST&exclude_status=Y&called_count_trigger=1-3&log_to_file=1
#
# Example of what to put in the No Agent Call URL field:
# (IMPORTANT: user needs to be NOAGENTURL and pass needs to be set to the call_id)
# VARhttp://192.168.1.1/agc/dispo_reset_lead.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&user=NOAGENTURL&pass=--A--call_id--B--&sale_status=SALE---SSALE---XSALE&log_to_file=1
# 
# Definable Fields: (other fields should be left as they are)
# - log_to_file -	(0,1) if set to 1, will create a log file in the agc directory
# - sale_status -	(SALE---XSALE) a triple-dash "---" delimited list of the statuses that are to be moved
# - exclude_status -	(Y,N) if set to Y, will trigger for all statuses EXCEPT for those listed in sale_status, default is N
# - called_count_trigger -	(1,2,3,1-4,...) if set to number or range greater than 0, will only trigger for called_count at or above set number or within the range ddefined, default is 0

#
# CHANGES
# 230204-0812 - First Build
#

$api_script = 'resetlead';
$php_script = 'dispo_reset_lead.php';

require_once("dbconnect_mysqli.php");
require_once("functions.php");

$filedate = date("Ymd");
$filetime = date("H:i:s");
$IP = getenv ("REMOTE_ADDR");
$BR = getenv ("HTTP_USER_AGENT");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["sale_status"]))			{$sale_status=$_GET["sale_status"];}
	elseif (isset($_POST["sale_status"]))	{$sale_status=$_POST["sale_status"];}
if (isset($_GET["exclude_status"]))				{$exclude_status=$_GET["exclude_status"];}
	elseif (isset($_POST["exclude_status"]))	{$exclude_status=$_POST["exclude_status"];}
if (isset($_GET["dispo"]))					{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))			{$dispo=$_POST["dispo"];}
if (isset($_GET["called_count"]))				{$called_count=$_GET["called_count"];}
	elseif (isset($_POST["called_count"]))		{$called_count=$_POST["called_count"];}
if (isset($_GET["called_count_trigger"]))			{$called_count_trigger=$_GET["called_count_trigger"];}
	elseif (isset($_POST["called_count_trigger"]))	{$called_count_trigger=$_POST["called_count_trigger"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["pass"]))					{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))			{$pass=$_POST["pass"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["log_to_file"]))			{$log_to_file=$_GET["log_to_file"];}
	elseif (isset($_POST["log_to_file"]))	{$log_to_file=$_POST["log_to_file"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#$DB = '1';	# DEBUG override
$US = '_';
$TD = '---';
$STARTtime = date("U");
$NOW_TIME = date("Y-m-d H:i:s");
$original_sale_status = $sale_status;
$sale_status = "$TD$sale_status$TD";
$search_value='';
$match_found=0;
$primary_match_found=0;
$age_trigger=0;
$k=0;

# filter variables
$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

header ("Content-type: text/html; charset=utf-8");

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$SSallow_web_debug =		$row[3];
	}
if ($SSallow_web_debug < 1) {$DB=0;}

$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}
##### END SETTINGS LOOKUP #####
###########################################

$lead_id = preg_replace('/[^_0-9]/', '', $lead_id);
$log_to_file = preg_replace('/[^-_0-9a-zA-Z]/', '', $log_to_file);
$called_count = preg_replace('/[^-_0-9a-zA-Z]/', '', $called_count);
$called_count_trigger = preg_replace('/[^-_0-9a-zA-Z]/', '', $called_count_trigger);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$exclude_status = preg_replace('/[^-_0-9a-zA-Z]/', '', $exclude_status);
	$sale_status = preg_replace('/[^-_0-9a-zA-Z]/', '', $sale_status);
	$dispo = preg_replace('/[^-_0-9a-zA-Z]/', '', $dispo);
	}
else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$exclude_status = preg_replace('/[^-_0-9\p{L}]/u', '', $exclude_status);
	$sale_status = preg_replace('/[^-_0-9\p{L}]/u', '', $sale_status);
	$dispo = preg_replace('/[^-_0-9\p{L}]/u', '', $dispo);
	}

if ($DB>0) {echo "$lead_id|$sale_status|$exclude_status|$dispo|$user|$pass|$DB|$log_to_file|$called_count|$called_count_trigger|\n";}

$called_count_trigger_range_check=0;
$called_count_trigger_range_match=0;
$called_count_trigger_minimum_check=0;
$called_count_trigger_minimum_match=0;
if (preg_match("/\d-\d/",$called_count_trigger))
	{
	$called_count_trigger_range_check=1;
	$called_count_triggerARY = explode('-',$called_count_trigger);
	if ( (strlen($called_count) > 0) and ($called_count >= $called_count_triggerARY[0]) and ($called_count <= $called_count_triggerARY[1]) )
		{$called_count_trigger_range_match=1;}
	}
else
	{
	if (strlen($called_count_trigger) > 0)
		{
		$called_count_trigger_minimum_check=1;
		if ( ($called_count >= $called_count_trigger) or ($called_count_trigger < 1) )
			{$called_count_trigger_minimum_match=1;}
		}
	}

if ( ($called_count_trigger_range_check < 1) or ( ($called_count_trigger_range_check > 0) and ($called_count_trigger_range_match > 0) ) )
	{
	if ( ($called_count_trigger_minimum_check < 1) or ( ($called_count_trigger_minimum_check > 0) and ($called_count_trigger_minimum_match > 0) ) )
		{
		if ( ( (preg_match("/$TD$dispo$TD/",$sale_status)) and ($exclude_status!='Y') ) or ( (!preg_match("/$TD$dispo$TD/",$sale_status)) and ($exclude_status=='Y') ) )
			{
			$primary_match_found=1;
			if ($DB>0) {echo "primary match found: |$primary_match_found|\n";}
			}
		}
	}
$first_pass_vars = "$sale_status|$exclude_status$called_count_trigger|";


if ($primary_match_found > 0)
	{$match_found=1;}
else
	{
	$sale_status='';
	$exclude_status='';
	$called_count_trigger='';
	}
if ($match_found > 0)
	{
	if ($non_latin < 1)
		{
		$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	#	$pass=preg_replace("/[^-_0-9a-zA-Z]/","",$pass);
		}

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
		$auth_message = user_authorization($user,$pass,'',0,0,0,0,'dispo_move_list');
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

	if (strlen($lead_id) > 0)
		{
		$search_count=0;
		$stmt = "SELECT count(*) FROM vicidial_list where lead_id='$lead_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$sc_ct = mysqli_num_rows($rslt);
		if ($sc_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$search_count = $row[0];
			}

		if ($search_count > 0)
			{
			$stmt="UPDATE vicidial_list SET called_since_last_reset='N' where lead_id='$lead_id' limit 1;";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);

			$SQL_log = "$stmt|$affected_rows|";
			$SQL_log = preg_replace('/;/','',$SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='lead_reset',value='$lead_id',result='$affected_rows',result_reason='$lead_id',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
			$rslt=mysql_to_mysqli($stmt, $link);

			$MESSAGE = _QXZ("DONE: %1s match found, %2s update to lead %3s",0,'',$search_count,$affected_rows,$lead_id);
			echo "$MESSAGE\n";
			}
		else
			{
			$MESSAGE = _QXZ("DONE: no match found for lead %1s",0,'',$lead_id);
			echo "$MESSAGE\n";
			}
		}
	else
		{
		$MESSAGE = _QXZ("DONE: lead %1s is not populated",0,'',$lead_id);
		echo "$MESSAGE\n";
		}
	}
else
	{
	$MESSAGE = _QXZ("DONE: no conditional match found(%1s): %2s",0,'',$original_sale_status,$dispo);
	echo "$MESSAGE\n";
	}

if ($log_to_file > 0)
	{
	$fp = fopen ("./dispo_reset_lead.txt", "w");
#	fwrite ($fp, "$NOW_TIME|$k|$lead_id|$dispo|$user|XXXX|$DB|$log_to_file|$called_count|$first_pass_vars|$original_sale_status|$exclude_status|$called_count_trigger|$MESSAGE|\n");
	fwrite ($fp, "$NOW_TIME|\n");
	fclose($fp);
	}
