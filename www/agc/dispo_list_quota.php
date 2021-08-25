<?php
# dispo_list_quota.php
# 
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the "Dispo URL" field of a campaign
# or in-group. It can check to see if a list should be set to active=N if the 
# number of leads in that list matching a specific status are at or above the
# set threshold.
#
# This script is part of the API group and any modifications of data are
# logged to the vicidial_api_log table.
#
# Example of what to put in the Dispo URL field(or 'ALT' URL setting):
# VARhttp://192.168.1.1/agc/dispo_list_quota.php?lead_id=--A--lead_id--B--&list_id=--A--list_id--B--&dispo=--A--dispo--B--&user=--A--user--B--&pass=--A--pass--B--&quota_status=SALE---SVYXFR---SVYCLM&list_quota_field=xferconf_e_number&list_quota_count=100&clear_from_hopper=1&log_to_file=0
# 
# Definable Fields: (other fields should be left as they are)
# - quota_status -		(SALE---SVYXFR) a triple-dash "---" delimited list of the statuses that are to be counted to get current list quota tally, REQUIRED
# - list_quota_field -	(xferconf_e_number) the list config field to grab the quota count limit from, this is REQUIRED(or 'list_quota_count') if valid this is priority
# - list_quota_count -	(3) the new status that the lead will be set to if the above criteria is met, this is REQUIRED(or 'list_quota_field')
# - clear_from_hopper -	(0,1) if set to 1, will clear any leads in hopper from this list if list is to be deactivated
# - log_to_file -		(0,1) if set to 1, will create a log file in the agc directory, default is 0
#
# CHANGES
# 210813-1340 - First Build
#

$api_script = 'dispo_list_quota';
$php_script = 'dispo_list_quota.php';

require_once("dbconnect_mysqli.php");
require_once("functions.php");

$filedate = date("Ymd");
$filetime = date("H:i:s");
$IP = getenv ("REMOTE_ADDR");
$BR = getenv ("HTTP_USER_AGENT");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
if (isset($_GET["quota_status"]))			{$quota_status=$_GET["quota_status"];}
	elseif (isset($_POST["quota_status"]))	{$quota_status=$_POST["quota_status"];}
if (isset($_GET["logged_count"]))			{$logged_count=$_GET["logged_count"];}
	elseif (isset($_POST["logged_count"]))	{$logged_count=$_POST["logged_count"];}
if (isset($_GET["list_quota_field"]))			{$list_quota_field=$_GET["list_quota_field"];}
	elseif (isset($_POST["list_quota_field"]))	{$list_quota_field=$_POST["list_quota_field"];}
if (isset($_GET["list_quota_count"]))			{$list_quota_count=$_GET["list_quota_count"];}
	elseif (isset($_POST["list_quota_count"]))	{$list_quota_count=$_POST["list_quota_count"];}
if (isset($_GET["clear_from_hopper"]))			{$clear_from_hopper=$_GET["clear_from_hopper"];}
	elseif (isset($_POST["clear_from_hopper"]))	{$clear_from_hopper=$_POST["clear_from_hopper"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["dispo"]))					{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))			{$dispo=$_POST["dispo"];}
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
#$quota_status = "$TD$quota_status$TD";
$search_value='';
$match_found=0;
$k=0;

# filter variables
$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);

# set defaults for variables not set
if (strlen($days_search) < 1)
	{$days_search = 0;}
if ( ($archive_search != 'Y') and ($archive_search != 'N') )
	{$archive_search = 'N';}
if ( ($in_out_search != 'IN') and ($in_out_search != 'OUT') and ($in_out_search != 'BOTH') )
	{$in_out_search = 'BOTH';}

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require_once('options.php');
	}

header ("Content-type: text/html; charset=utf-8");

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
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

$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
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

$lead_id = preg_replace('/[^0-9]/','',$lead_id);
$list_id = preg_replace('/[^_0-9]/', '', $list_id);
$log_to_file = preg_replace('/[^0-9]/','',$log_to_file);
$list_quota_field = preg_replace('/[^-_0-9a-zA-Z]/', '', $list_quota_field);
$list_quota_count = preg_replace('/[^-_0-9a-zA-Z]/', '', $list_quota_count);
$clear_from_hopper = preg_replace('/[^0-9]/','',$clear_from_hopper);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-_0-9a-zA-Z]/","",$pass);
	$quota_status = preg_replace('/[^-_0-9a-zA-Z]/','',$quota_status);
	$dispo = preg_replace('/[^-_0-9a-zA-Z]/', '', $dispo);
	}
else
	{
	$quota_status = preg_replace('/[^-_0-9\p{L}]/u','',$quota_status);
	$dispo = preg_replace('/[^-_0-9\p{L}]/u','',$dispo);
	}

if ($DB>0) {echo "$lead_id|$list_id|$dispo|$quota_status|$list_quota_field|$list_quota_count|$clear_from_hopper|$user|$pass|$DB|$log_to_file|\n";}

if ( (strlen($quota_status) > 0) and (strlen($dispo) > 0) and ( (strlen($list_quota_field) > 0) or (strlen($list_quota_count) > 0) ) )
	{$match_found=1;}

# variables populated, process request
if ($match_found > 0)
	{
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
		$auth_message = user_authorization($user,$pass,'',0,0,0,0,$api_script);
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

	if (strlen($list_id) > 0)
		{
		# determine the quota limit
		$temp_list_quota_max='';
		$list_quota_max = $list_quota_count;
		if (strlen($list_quota_field) > 0)
			{
			$stmt="SELECT $list_quota_field from vicidial_lists where list_id='$list_id';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$vl_ct = mysqli_num_rows($rslt);
			if ($vl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$temp_list_quota_max = $row[0];
				$temp_list_quota_max = preg_replace('/[^0-9]/','',$temp_list_quota_max);
				}
			}
		if (strlen($temp_list_quota_max) > 0) {$list_quota_max = $temp_list_quota_max;}

		# find out how many leads in the list fit the quota statuses
		$list_quota_tally=0;
		$temp_quota_status = $quota_status;
		$temp_quota_status = preg_replace('/---/',"','",$temp_quota_status);
		$temp_quota_status = "'$temp_quota_status'";
		$stmt="SELECT count(*) from vicidial_list where list_id='$list_id' and status IN($temp_quota_status);";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$lq_ct = mysqli_num_rows($rslt);
		if ($lq_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$list_quota_tally = $row[0];
			}

		# if quota-tally is greater than or equal to the quota-max, then deactivate list
		if ($list_quota_tally >= $list_quota_max)
			{
			$stmt="UPDATE vicidial_lists SET active='N' where list_id='$list_id';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);

			$affected_rowsH=0;
			if ($clear_from_hopper > 0)
				{
				$stmtH="DELETE FROM vicidial_hopper where list_id='$list_id';";
				if ($DB) {echo "$stmtH\n";}
				$rslt=mysql_to_mysqli($stmtH, $link);
				$affected_rowsH = mysqli_affected_rows($link);
				}

			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|$stmtH|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$IP', event_section='LISTS', event_type='MODIFY', record_id='$list_id', event_code='DISPO QUOTA DEACTIVATE LIST', event_sql=\"$SQL_log\", event_notes='LIST: $list_id CHANGED: $affected_rows QUOTA: $list_quota_tally >= $list_quota_max QUOTA STATUS: $quota_status HOPPER CLEAR: $affected_rowsH';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);

			$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='$api_script',value='$list_id',result='$affected_rows',result_reason='$list_quota_tally,$list_quota_max,$quota_status',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
			$rslt=mysql_to_mysqli($stmt, $link);

			$MESSAGE = _QXZ("DONE: list quota status count %1s is at quota limit %2s. List %3s is deactivated: %4s",0,'',$list_quota_tally,$list_quota_max,$list_id,$affected_rows);
			echo "$MESSAGE\n";
			}
		else
			{
			$MESSAGE = _QXZ("DONE: no change required, list quota status count %1s is below quota limit %2s",0,'',$list_quota_tally,$list_quota_max);
			echo "$MESSAGE\n";
			}
		}
	else
		{
		$MESSAGE = _QXZ("DONE: list_id is not defined: %1s",0,'',$list_id);
		echo "$MESSAGE\n";
		}
	}
else
	{
	$MESSAGE = _QXZ("DONE: Not all required variables have been set: %1s,%2s,%3s,%4s",0,'',$dispo,$quota_status,$list_quota_field,$list_quota_count);
	echo "$MESSAGE\n";
	}

if ($log_to_file > 0)
	{
	$fp = fopen ("./$api_script.txt", "w");
#	fwrite ($fp, "$NOW_TIME|$k|$lead_id|$list_id|$dispo|$quota_status|$list_quota_field|$list_quota_count|$clear_from_hopper|$user|XXXX|$DB|$log_to_file|$MESSAGE|\n");
	fwrite ($fp, "$NOW_TIME|\n");
	fclose($fp);
	}
