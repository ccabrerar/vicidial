<?php
# dispo_change_status.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the "Dispo URL" field of a campaign
# or in-group. It can update the status of a lead to a new status if the lead 
# has been dispositioned as a specified status a set number of times
#
# This script is part of the API group and any modifications of data are
# logged to the vicidial_api_log table.
#
# Example of what to put in the Dispo URL field:
# VARhttp://192.168.1.1/agc/dispo_change_status.php?lead_id=--A--lead_id--B--&user=--A--user--B--&pass=--A--pass--B--&logged_status=NI&logged_count=3&new_status=NI3&days_search=0&archive_search=N&log_to_file=1
# 
# Definable Fields: (other fields should be left as they are)
# - logged_status -	(NI) the status that is to be counted in the logs, REQUIRED
# - logged_count -	(3) the number of times a lead was set to the above status that will trigger the change in status, REQUIRED
# - new_status -	(NI3) the new status that the lead will be set to if the above criteria is met, REQUIRED
# - days_search -	(0) the number of days in the past to look back in the logs for the matching log entries, 0 for no date restriction, default is 0
# - archive_search - (Y,N) whether to search in the archived call logs for log entries, default is N
# - in_out_search - (IN,OUT,BOTH) whether to search in the inbound or outbound or both call logs for log entries, default is BOTH
# - log_to_file -	(0,1) if set to 1, will create a log file in the agc directory, default is 0
#
# CHANGES
# 171127-1736 - First Build
#

$api_script = 'dispo_change_status';

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
if (isset($_GET["logged_status"]))			{$logged_status=$_GET["logged_status"];}
	elseif (isset($_POST["logged_status"]))	{$logged_status=$_POST["logged_status"];}
if (isset($_GET["logged_count"]))			{$logged_count=$_GET["logged_count"];}
	elseif (isset($_POST["logged_count"]))	{$logged_count=$_POST["logged_count"];}
if (isset($_GET["new_status"]))				{$new_status=$_GET["new_status"];}
	elseif (isset($_POST["new_status"]))	{$new_status=$_POST["new_status"];}
if (isset($_GET["days_search"]))			{$days_search=$_GET["days_search"];}
	elseif (isset($_POST["days_search"]))	{$days_search=$_POST["days_search"];}
if (isset($_GET["archive_search"]))				{$archive_search=$_GET["archive_search"];}
	elseif (isset($_POST["archive_search"]))	{$archive_search=$_POST["archive_search"];}
if (isset($_GET["in_out_search"]))			{$in_out_search=$_GET["in_out_search"];}
	elseif (isset($_POST["in_out_search"]))	{$in_out_search=$_POST["in_out_search"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
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
#$logged_status = "$TD$logged_status$TD";
$search_value='';
$match_found=0;
$k=0;

# filter variables
$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);
$lead_id = preg_replace('/[^0-9]/','',$lead_id);
$logged_status = preg_replace('/[^-_0-9a-zA-Z]/','',$logged_status);
$logged_count = preg_replace('/[^0-9]/','',$logged_count);
$new_status = preg_replace('/[^-_0-9a-zA-Z]/','',$new_status);
$days_search = preg_replace('/[^0-9]/','',$days_search);
$archive_search = preg_replace('/[^-_0-9a-zA-Z]/','',$archive_search);
$in_out_search = preg_replace('/[^-_0-9a-zA-Z]/','',$in_out_search);
$log_to_file = preg_replace('/[^0-9]/','',$log_to_file);

# set defaults for variables not set
if (strlen($days_search) < 1)
	{$days_search = 0;}
if ( ($archive_search != 'Y') and ($archive_search != 'N') )
	{$archive_search = 'N';}
if ( ($in_out_search != 'IN') and ($in_out_search != 'OUT') and ($in_out_search != 'BOTH') )
	{$in_out_search = 'BOTH';}

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

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-_0-9a-zA-Z]/","",$pass);
	}

if ($DB>0) {echo "$lead_id|$dispo|$logged_status|$logged_count|$new_status|$days_search|$archive_search|$in_out_search|$user|$pass|$DB|$log_to_file|\n";}

if ( (strlen($logged_status) > 0) and (strlen($logged_count) > 0) and (strlen($new_status) > 0) )
	{$match_found=1;}

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

	if (strlen($lead_id) > 0)
		{
		$days_searchSQL='';
		$days_search_date='';
		if ($days_search > 0)
			{
			$days_search_date = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-$days_search,date("Y")));
			$days_searchSQL="and call_date > \"$days_search_date\"";
			}
		$VL_count=0;
		$VLA_count=0;
		$VCL_count=0;
		$VCLA_count=0;
		$search_count=0;
		if ( ($in_out_search == 'OUT') or ($in_out_search == 'BOTH') )
			{
			$stmt = "SELECT count(*) FROM vicidial_log where lead_id='$lead_id' and status='$logged_status' $days_searchSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$sc_ct = mysqli_num_rows($rslt);
			if ($sc_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$VL_count = $row[0];
				}
			if ($archive_search == 'Y')
				{
				$stmt = "SELECT count(*) FROM vicidial_log_archive where lead_id='$lead_id' and status='$logged_status' $days_searchSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$sc_ct = mysqli_num_rows($rslt);
				if ($sc_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$VLA_count = $row[0];
					}
				}
			}
		if ( ($in_out_search == 'IN') or ($in_out_search == 'BOTH') )
			{
			$stmt = "SELECT count(*) FROM vicidial_closer_log where lead_id='$lead_id' and status='$logged_status' $days_searchSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$sc_ct = mysqli_num_rows($rslt);
			if ($sc_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$VCL_count = $row[0];
				}
			if ($archive_search == 'Y')
				{
				$stmt = "SELECT count(*) FROM vicidial_closer_log_archive where lead_id='$lead_id' and status='$logged_status' $days_searchSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$sc_ct = mysqli_num_rows($rslt);
				if ($sc_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$VCLA_count = $row[0];
					}
				}
			}

		$search_count = ($VL_count + $VLA_count + $VCL_count + $VCLA_count=0);

		if ($search_count >= $logged_count)
			{
			$stmt="UPDATE vicidial_list SET status='$new_status' where lead_id='$lead_id';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);

			$SQL_log = "$stmt|";
			$SQL_log = preg_replace('/;/','',$SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='$api_script',value='$lead_id',result='$affected_rows',result_reason='$new_status',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
			$rslt=mysql_to_mysqli($stmt, $link);

			$MESSAGE = _QXZ("DONE: %1s x %2s match found, %3s lead_id %4s changed to %5s status",0,'',$search_count,$logged_status,$affected_rows,$lead_id,$new_status);
			echo "$MESSAGE\n";
			}
		else
			{
			$MESSAGE = _QXZ("DONE: no change required, not enough matching log entries %1s",0,'',$search_count);
			echo "$MESSAGE\n";
			}
		}
	else
		{
		$MESSAGE = _QXZ("DONE: lead_id is not defined: %1s",0,'',$lead_id);
		echo "$MESSAGE\n";
		}
	}
else
	{
	$MESSAGE = _QXZ("DONE: Not all required variables have been set: %1s,%2s,%3s",0,'',$logged_status,$logged_count,$new_status);
	echo "$MESSAGE\n";
	}

if ($log_to_file > 0)
	{
	$fp = fopen ("./$api_script.txt", "a");
	fwrite ($fp, "$NOW_TIME|$k|$lead_id|$dispo|$logged_status|$logged_count($search_count)|$new_status|$days_search($days_search_date)|$archive_search|$in_out_search|$user|XXXX|$DB|$log_to_file|$MESSAGE|\n");
	fclose($fp);
	}
