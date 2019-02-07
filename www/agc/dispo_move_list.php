<?php
# dispo_move_list.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the "Dispo URL" field of a campaign
# or in-group (although it can also be used in the "No Agent Call URL" field). 
# It should take in the lead_id to check for the same lead_id
# in order to change it's list_id to whatever new_list_id is set to. The
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
# VARhttp://192.168.1.1/agc/dispo_move_list.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&user=--A--user--B--&pass=--A--pass--B--&new_list_id=10411099&sale_status=SALE---SSALE---XSALE&reset_dialed=Y&log_to_file=1
# 
# Another example of what to put in the Dispo URL field(using status exclude with talk trigger):
# VARhttp://192.168.1.1/agc/dispo_move_list.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&talk_time=--A--talk_time--B--&called_count=--A--called_count--B--&user=--A--user--B--&pass=--A--pass--B--&new_list_id=332&sale_status=DNC---BILLNW---POST&exclude_status=Y&talk_time_trigger=240&called_count_trigger=4&log_to_file=1
# 
# Another example of what to put in the Dispo URL field(using status exclude with lead age and called count triggers):
# VARhttp://192.168.1.1/agc/dispo_move_list.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&user=--A--user--B--&pass=--A--pass--B--&new_list_id=99105&called_count=--A--called_count--B--&called_count_trigger=9&lead_age=15&entry_date=--A--entry_date--B--&exclude_status=Y&sale_status=SALE---XFER&reset_dialed=Y&log_to_file=1
#
# Example of what to put in the No Agent Call URL field:
# (IMPORTANT: user needs to be NOAGENTURL and pass needs to be set to the call_id)
# VARhttp://192.168.1.1/agc/dispo_move_list.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&user=NOAGENTURL&pass=--A--call_id--B--&new_list_id=10411099&sale_status=SALE---SSALE---XSALE&reset_dialed=Y&log_to_file=1&list_id=--A--list_id--B--&list_id_trigger=101
# 
# Definable Fields: (other fields should be left as they are)
# - log_to_file -	(0,1) if set to 1, will create a log file in the agc directory
# - sale_status -	(SALE---XSALE) a triple-dash "---" delimited list of the statuses that are to be moved
# - exclude_status -	(Y,N) if set to Y, will trigger for all statuses EXCEPT for those listed in sale_status, default is N
# - talk_time_trigger -	(0,1,2,3,...) if set to number greater than 0, will only trigger for talk_time at or above set number, default is 0
# - called_count_trigger -	(1,2,3,...) if set to number greater than 0, will only trigger for called_count at or above set number, default is 0
# - list_id_trigger -	(101,...) if set to number greater than 99, will only trigger for list_id equal to the set number(NOTE: list_id must be sent), default is disabled
# - list_id -	(101,...) if you want to use list_id_trigger then this must be set: "list_id=--A--list_id--B--", default is disabled
# - lead_age - (1,2,3,...) if set to number greater than 0, will only trigger for a lead entry_date this number of days old or older, default is 0
# - new_list_id -	(999,etc...) the list_id that you want the matching status leads to be moved to
# - reset_dialed -	(Y,N) if set to Y, will reset the called_since_last_reset flag on the lead
# - populate_sp_old_list -	(Y,N) if set to Y, will populate the security_phrase field of the lead with the old list_id
# - populate_comm_old_date -	(Y,N) if set to Y, will populate the comments field of the lead with the date and time when the lead was last called
#    Multiple sets of statuses:
# - sale_status_1, new_list_id_1, reset_dialed_1, exclude_status_1, called_count_trigger_1 - adding an underscore and number(1-99) will allow for another set of statuses to check for and what to do with them
# - multi_trigger - (talk-age...) if set to 1 or more of "talk,age,list,count,status"(separated by '-') it will check for only one of included triggers to be met for the lead to be moved, (does not work with multiple sets)
#
# CHANGES
# 100915-1600 - First Build
# 110702-2020 - Added multiple sets of options
# 111005-1102 - Added check and update for scheduled callback entry
# 120223-2124 - Removed logging of good login passwords if webroot writable is enabled
# 130328-0015 - Converted ereg to preg functions
# 130603-2216 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130802-1007 - Changed to PHP mysqli functions
# 140811-0844 - Changed to use QXZ function for echoing text
# 141118-1235 - Formatting changes for QXZ output
# 141216-2110 - Added language settings lookups and user/pass variable standardization
# 150703-1453 - Added options so it would work with No-Agent Call URL
# 160309-1239 - Added talk_time_trigger and exclude_status options
# 160801-1032 - Added called_count_trigger options
# 160910-1354 - Added populate_... options
# 161021-1016 - Added lead_age option
# 170402-0906 - Added list_id_trigger option, cleaned up outputs
# 170526-2310 - Added additional variable filtering
# 180419-2257 - Added multi_trigger option
#

$api_script = 'movelist';

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
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["sale_status"]))			{$sale_status=$_GET["sale_status"];}
	elseif (isset($_POST["sale_status"]))	{$sale_status=$_POST["sale_status"];}
if (isset($_GET["exclude_status"]))				{$exclude_status=$_GET["exclude_status"];}
	elseif (isset($_POST["exclude_status"]))	{$exclude_status=$_POST["exclude_status"];}
if (isset($_GET["dispo"]))					{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))			{$dispo=$_POST["dispo"];}
if (isset($_GET["new_list_id"]))			{$new_list_id=$_GET["new_list_id"];}
	elseif (isset($_POST["new_list_id"]))	{$new_list_id=$_POST["new_list_id"];}
if (isset($_GET["reset_dialed"]))			{$reset_dialed=$_GET["reset_dialed"];}
	elseif (isset($_POST["reset_dialed"]))	{$reset_dialed=$_POST["reset_dialed"];}
if (isset($_GET["talk_time"]))				{$talk_time=$_GET["talk_time"];}
	elseif (isset($_POST["talk_time"]))		{$talk_time=$_POST["talk_time"];}
if (isset($_GET["talk_time_trigger"]))			{$talk_time_trigger=$_GET["talk_time_trigger"];}
	elseif (isset($_POST["talk_time_trigger"]))	{$talk_time_trigger=$_POST["talk_time_trigger"];}
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
if (isset($_GET["populate_sp_old_list"]))			{$populate_sp_old_list=$_GET["populate_sp_old_list"];}
	elseif (isset($_POST["populate_sp_old_list"]))	{$populate_sp_old_list=$_POST["populate_sp_old_list"];}
if (isset($_GET["populate_comm_old_date"]))				{$populate_comm_old_date=$_GET["populate_comm_old_date"];}
	elseif (isset($_POST["populate_comm_old_date"]))	{$populate_comm_old_date=$_POST["populate_comm_old_date"];}
if (isset($_GET["lead_age"]))				{$lead_age=$_GET["lead_age"];}
	elseif (isset($_POST["lead_age"]))		{$lead_age=$_POST["lead_age"];}
if (isset($_GET["entry_date"]))				{$entry_date=$_GET["entry_date"];}
	elseif (isset($_POST["entry_date"]))	{$entry_date=$_POST["entry_date"];}
if (isset($_GET["list_id"]))			{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))	{$list_id=$_POST["list_id"];}
if (isset($_GET["list_id_trigger"]))			{$list_id_trigger=$_GET["list_id_trigger"];}
	elseif (isset($_POST["list_id_trigger"]))	{$list_id_trigger=$_POST["list_id_trigger"];}
if (isset($_GET["multi_trigger"]))			{$multi_trigger=$_GET["multi_trigger"];}
	elseif (isset($_POST["multi_trigger"]))	{$multi_trigger=$_POST["multi_trigger"];}


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
$lead_age = preg_replace('/[^_0-9]/', '', $lead_age);
$lead_id = preg_replace('/[^_0-9]/', '', $lead_id);
$list_id = preg_replace('/[^_0-9]/', '', $list_id);
$new_list_id = preg_replace('/[^_0-9]/', '', $new_list_id);
$list_id_trigger = preg_replace('/[^_0-9]/', '', $list_id_trigger);
$multi_trigger=preg_replace("/\'|\"|\\\\|;| /","",$multi_trigger);

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02002',$user,$server_ip,$session_name,$one_mysql_log);}
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

if ($lead_age > 0)
	{
	if (strlen($entry_date) < 10)
		{if ($DB>0) {echo "Entry date not set: |$entry_date|\n";}}
	else
		{
		$entry_date_time = explode(' ',$entry_date);
		$entry_YYYYMMDD = explode('-',$entry_date_time[0]);
		$entry_HHMMSS = explode(':',$entry_date_time[1]);
		$entry_epoch = mktime($entry_YYYYMMDD[0], $entry_YYYYMMDD[1], $entry_YYYYMMDD[2], $entry_YYYYMMDD[1], $entry_YYYYMMDD[2]-1, $entry_YYYYMMDD[0]);
		$entry_age = (($STARTtime - $entry_epoch) / 86400);
		if ($lead_age < $entry_age)
			{$age_trigger=1;}
		if ($DB>0) {echo "Lead age debug: |$entry_date|$lead_age|$entry_age|$entry_YYYYMMDD[0]|$entry_YYYYMMDD[1]|$entry_YYYYMMDD[2]|$entry_HHMMSS[0]|$entry_HHMMSS[1]|$entry_HHMMSS[2]|($entry_epoch <> $STARTtime)|\n";}
		}
	}

if ($DB>0) {echo "$lead_id|$sale_status|$exclude_status|$dispo|$user|$pass|$DB|$log_to_file|$talk_time|$talk_time_trigger|$called_count|$called_count_trigger|$lead_age|$age_trigger|$list_id|$list_id_trigger|$multi_trigger|\n";}

if ( ( (strlen($list_id_trigger) > 2) and (strlen($list_id) > 2) and ($list_id == $list_id_trigger) ) or ( (strlen($list_id_trigger) < 3) or (strlen($list_id) < 3) ) )
	{
	if ( ( ($lead_age > 0) and ($age_trigger > 0) ) or ($lead_age < 1) or (strlen($lead_age) < 1) )
		{
		if ( ( (strlen($called_count_trigger)>0) and ($called_count >= $called_count_trigger) ) or (strlen($called_count_trigger)<1) or ($called_count_trigger < 1) )
			{
			if ( ( (strlen($talk_time_trigger)>0) and ($talk_time >= $talk_time_trigger) ) or (strlen($talk_time_trigger)<1) or ($talk_time_trigger < 1) )
				{
				if ( ( (preg_match("/$TD$dispo$TD/",$sale_status)) and ($exclude_status!='Y') ) or ( (!preg_match("/$TD$dispo$TD/",$sale_status)) and ($exclude_status=='Y') ) )
					{
					$primary_match_found=1;
					if ($DB>0) {echo "primary match found: |$primary_match_found|\n";}
					}
				}
			}
		}
	}
$first_pass_vars = "$new_list_id|$reset_dialed|$sale_status|$talk_time_trigger|$exclude_status$called_count_trigger|";


### BEGIN multi_trigger section (talk,age,list,count,status) ###
$multi_match=0;
$multi_nomatch=0;
if ( ($primary_match_found < 1) and (strlen($multi_trigger) > 2) )
	{
	$match_text='';
	if ($DB>0) {echo "starting multi_trigger check: |$multi_trigger|$primary_match_found|\n";}
	if (preg_match("/talk/",$multi_trigger))
		{
		if ( (strlen($talk_time_trigger)>0) and ($talk_time >= $talk_time_trigger) )
			{$multi_match++;   $match_text.="talk|";}
		else
			{$multi_nomatch++;}
		}
	if (preg_match("/age/",$multi_trigger))
		{
		if ( ($lead_age > 0) and ($age_trigger > 0) )
			{$multi_match++;   $match_text.="age|";}
		else
			{$multi_nomatch++;}
		}
	if (preg_match("/list/",$multi_trigger))
		{
		if ( (strlen($list_id_trigger) > 2) and (strlen($list_id) > 2) and ($list_id == $list_id_trigger) )
			{$multi_match++;   $match_text.="list|";}
		else
			{$multi_nomatch++;}
		}
	if (preg_match("/count/",$multi_trigger))
		{
		if ( (strlen($called_count_trigger)>0) and ($called_count >= $called_count_trigger) )
			{$multi_match++;   $match_text.="count|";}
		else
			{$multi_nomatch++;}
		}
	if (preg_match("/status/",$multi_trigger))
		{
		if ( ( (preg_match("/$TD$dispo$TD/",$sale_status)) and ($exclude_status!='Y') ) or ( (!preg_match("/$TD$dispo$TD/",$sale_status)) and ($exclude_status=='Y') ) )
			{$multi_match++;   $match_text.="status|";}
		else
			{$multi_nomatch++;}
		}
	
	if ($multi_match > 0)
		{
		$primary_match_found=1;
		if ($DB>0) {echo "multi_trigger good: |$multi_trigger|$match_text|$multi_match|$multi_nomatch|\n";}
		}
	}
### END multi_trigger section (talk,age,list,count) ###


if ($primary_match_found > 0)
	{$match_found=1;}
else
	{
	$sale_status='';
	$exclude_status='';
	$talk_time_trigger='';
	$called_count_trigger='';
	$new_list_id='';
	$reset_dialed='';
	while( ($match_found < 1) and ($k < 99) )
		{
		$k++;
		$sale_status='';
		$exclude_status='';
		$talk_time_trigger='';
		$called_count_trigger='';
		$lead_age=0;
		$statusfield = "sale_status_$k";
		$excludefield = "exclude_status_$k";
		$talktriggerfield = "talk_time_trigger_$k";
		$counttriggerfield = "called_count_trigger_$k";
		$agetriggerfield = "lead_age_$k";
		$listtriggerfield = "list_id_trigger_$k";
		if (isset($_GET["$excludefield"]))			{$exclude_status=$_GET["$excludefield"];}
			elseif (isset($_POST["$excludefield"]))	{$exclude_status=$_POST["$excludefield"];}
		if (isset($_GET["$talktriggerfield"]))			{$talk_time_trigger=$_GET["$talktriggerfield"];}
			elseif (isset($_POST["$talktriggerfield"]))	{$talk_time_trigger=$_POST["$talktriggerfield"];}
		if (isset($_GET["$counttriggerfield"]))				{$called_count_trigger=$_GET["$counttriggerfield"];}
			elseif (isset($_POST["$counttriggerfield"]))	{$called_count_trigger=$_POST["$counttriggerfield"];}
		if (isset($_GET["$agetriggerfield"]))			{$lead_age=$_GET["$agetriggerfield"];}
			elseif (isset($_POST["$agetriggerfield"]))	{$lead_age=$_POST["$agetriggerfield"];}
		if (isset($_GET["$statusfield"]))			{$sale_status=$_GET["$statusfield"];}
			elseif (isset($_POST["$statusfield"]))	{$sale_status=$_POST["$statusfield"];}
		if (isset($_GET["$listtriggerfield"]))			{$list_id_trigger=$_GET["$listtriggerfield"];}
			elseif (isset($_POST["$listtriggerfield"]))	{$list_id_trigger=$_POST["$listtriggerfield"];}
		$sale_status = "$TD$sale_status$TD";
		$lead_age = preg_replace('/[^_0-9]/', '', $lead_age);
		$list_id_trigger = preg_replace('/[^_0-9]/', '', $list_id_trigger);

		if ($lead_age > 0)
			{
			if (strlen($entry_date) < 10)
				{if ($DB>0) {echo "Entry date not set: |$entry_date|\n";}}
			else
				{
				$entry_date_time = explode(' ',$entry_date);
				$entry_YYYYMMDD = explode('-',$entry_date_time[0]);
				$entry_HHMMSS = explode(':',$entry_date_time[1]);
				$entry_epoch = mktime($entry_YYYYMMDD[0], $entry_YYYYMMDD[1], $entry_YYYYMMDD[2], $entry_YYYYMMDD[1], $entry_YYYYMMDD[2]-1, $entry_YYYYMMDD[0]);
				$entry_age = (($STARTtime - $entry_epoch) / 86400);
				if ($lead_age < $entry_age)
					{$age_trigger=1;}
				if ($DB>0) {echo "Lead age debug: |$entry_date|$lead_age|$entry_age|$entry_YYYYMMDD[0]|$entry_YYYYMMDD[1]|$entry_YYYYMMDD[2]|$entry_HHMMSS[0]|$entry_HHMMSS[1]|$entry_HHMMSS[2]|($entry_epoch <> $STARTtime)|\n";}
				}
			}

		if ($DB) {echo _QXZ("MULTI_MATCH CHECK:")." $k|$sale_status|$statusfield|$exclude_status|$excludefield|$talk_time_trigger|$talktriggerfield|$called_count_trigger|$counttriggerfield|$lead_age|$agetriggerfield|$list_id|$list_id_trigger|\n";}

		if ( ( (strlen($list_id_trigger) > 2) and (strlen($list_id) > 2) and ($list_id == $list_id_trigger) ) or ( (strlen($list_id_trigger) < 3) or (strlen($list_id) < 3) ) )
			{
			if ( ( ($lead_age > 0) and ($age_trigger > 0) ) or ($lead_age < 1) or (strlen($lead_age) < 1) )
				{
				if ( ( (strlen($called_count_trigger)>0) and ($called_count >= $called_count_trigger) ) or (strlen($called_count_trigger)<1) or ($called_count_trigger < 1) )
					{
					if ( ( (strlen($talk_time_trigger)>0) and ($talk_time >= $talk_time_trigger) ) or (strlen($talk_time_trigger)<1) or ($talk_time_trigger < 1) )
						{
						if (strlen($sale_status)>0)
							{
							if ( ( (preg_match("/$TD$dispo$TD/",$sale_status)) and ($exclude_status!='Y') ) or ( (!preg_match("/$TD$dispo$TD/",$sale_status)) and ($exclude_status=='Y') ) )
								{
								$match_found=1;
								$newlistfield = "new_list_id_$k";
								$resetfield = "reset_dialed_$k";
								if (isset($_GET["$newlistfield"]))			{$new_list_id=$_GET["$newlistfield"];}
									elseif (isset($_POST["$newlistfield"]))	{$new_list_id=$_POST["$newlistfield"];}
								if (isset($_GET["$resetfield"]))			{$reset_dialed=$_GET["$resetfield"];}
									elseif (isset($_POST["$resetfield"]))	{$reset_dialed=$_POST["$resetfield"];}
								$new_list_id = preg_replace('/[^_0-9]/', '', $new_list_id);
								if ($DB) {echo _QXZ("MULTI_MATCH:")." $k|$sale_status|$new_list_id|$reset_dialed|$exclude_status|$talk_time_trigger|$called_count_trigger|\n";}
								}
							}
						}
					}
				}
			}
		}
	}
if ($match_found > 0)
	{
	if ($non_latin < 1)
		{
		$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	#	$pass=preg_replace("/[^-_0-9a-zA-Z]/","",$pass);
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

	if ( (strlen($lead_id) > 0) and (strlen($new_list_id) > 2) )
		{
		$search_count=0;
		$stmt = "SELECT count(*) FROM vicidial_list where lead_id='$lead_id' and list_id!='$new_list_id';";
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
			$field_editSQL='';
			$reset_dialedSQL='';
			if ( ($populate_sp_old_list=='Y') or ($populate_comm_old_date=='Y') )
				{
				$stmtA = "SELECT list_id,last_local_call_time,comments FROM vicidial_list where lead_id='$lead_id';";
				$rslt=mysql_to_mysqli($stmtA, $link);
				if ($DB) {echo "$stmtA\n";}
				$vle_ct = mysqli_num_rows($rslt);
				if ($vle_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$old_list_id = ",security_phrase='$row[0]'";
					$old_call_time = ",comments='$row[1]'";
					if ($populate_sp_old_list=='Y')
						{$field_editSQL .= "$old_list_id";}
					if ($populate_comm_old_date=='Y')
						{$field_editSQL .= "$old_call_time";}
					}
				}

			if ($reset_dialed=='Y') {$reset_dialedSQL=", called_since_last_reset='N'";}
			$stmt="UPDATE vicidial_list SET list_id='$new_list_id' $reset_dialedSQL $field_editSQL where lead_id='$lead_id' limit 1;";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);

			$campaign_idSQL='';
			$stmtA = "SELECT campaign_id FROM vicidial_lists where list_id='$new_list_id';";
			$rslt=mysql_to_mysqli($stmtA, $link);
			if ($DB) {echo "$stmtA\n";}
			$vlc_ct = mysqli_num_rows($rslt);
			if ($vlc_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$campaign_idSQL = ",campaign_id='$row[0]'";
				}

			$stmtB="UPDATE vicidial_callbacks SET list_id='$new_list_id' $campaign_idSQL where lead_id='$lead_id' limit 1;";
			if ($DB) {echo "$stmtB\n";}
			$rslt=mysql_to_mysqli($stmtB, $link);
			$CBaffected_rows = mysqli_affected_rows($link);

			$SQL_log = "$stmt|$stmtB|$CBaffected_rows|";
			$SQL_log = preg_replace('/;/','',$SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='deactivate_lead',value='$lead_id',result='$affected_rows',result_reason='$lead_id',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
			$rslt=mysql_to_mysqli($stmt, $link);

			$MESSAGE = _QXZ("DONE: %1s match found, %2s updated to %3s with %4s status",0,'',$search_count,$affected_rows,$new_list_id,$dispo);
			echo "$MESSAGE\n";
			}
		else
			{
			$MESSAGE = _QXZ("DONE: no match found within %1s     %2s",0,'',$lead_id,$new_list_id);
			echo "$MESSAGE\n";
			}
		}
	else
		{
		$MESSAGE = _QXZ("DONE: new list %1s is empty for lead %2s",0,'',$new_list_id,$lead_id);
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
	$fp = fopen ("./dispo_move_list.txt", "a");
	fwrite ($fp, "$NOW_TIME|$k|$lead_id|$dispo|$user|XXXX|$DB|$log_to_file|$talk_time|$called_count|$first_pass_vars|$new_list_id|$original_sale_status|$talk_time_trigger|$exclude_status|$called_count_trigger|$list_id|$list_id_trigger|$multi_trigger|$MESSAGE|\n");
	fclose($fp);
	}
