<?php
# dispo_move_list_SC.php
# 
# Copyright (C) 2025  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
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
# not run if the search field of the lead is empty. If there is a match, the
# script will exist after moving the lead to the new list.
#
# This script has fewer options than the dispo_move_list_SC.php script, but it
# Allows for configuration through a Settings Container with many more parameters
#
#
# Example of what to put in the Dispo URL field:
# VARhttp://192.168.1.1/agc/dispo_move_list.php?lead_id=--A--lead_id--B--&list_id=--A--list_id--B--&called_count=--A--called_count--B--&dispo=--A--dispo--B--&user=--A--user--B--&pass=--A--pass--B--&container_id=Test_DML_container&log_to_file=1
#
# Example of what to put in the No Agent Call URL field:
# (IMPORTANT: user needs to be NOAGENTURL and pass needs to be set to the call_id)
# VARhttp://192.168.1.1/agc/dispo_move_list.php?lead_id=--A--lead_id--B--&list_id=--A--list_id--B--&called_count=--A--called_count--B--&dispo=--A--dispo--B--&user=NOAGENTURL&pass=--A--call_id--B--&container_id=Test_DML_container&log_to_file=1
# 
# Example Settings Container to use with the above example:
# ; dispo_move_list_SC.php container fields(all fields must be defined) separated by a pipe '|':
# ; 1 - list_ids => list of list_ids separated by one dash each to restrict this move directive to. For all lists set to "---ALL---"
# ; 2 - called_count => This move directive will be triggered for this called_count and higher, REQUIRED, numbers only
# ; 3 - statuses => This move directive will be triggered for these statuses only(separated by commas). For all statuses set to "---ALL---"
# ; 4 - status_exclude => This move directive will be triggered for all statuses NOT defined in previous field only. 'N' or 'Y' only.
# ; 5 - reset_lead => If the move directive is triggered, also reset the called_since_last_reset field of the lead. 'N' or 'Y' only.
# ; 6 - new_list_id => If the move directive is triggered, this is the list_id the lead will be moved to.
# ;
# ; list_ids,called_count,statuses,status_exclude,reset_lead,new_list_id
# 130-230|4|ACT,Age,Bootc,CBsale,CLIENT,DNC,DNC1,DNQ2,DNQB,DNQU90,LCR1,LEAD1,NoBank,NoEng,NoInco,NR,QT,Sold,Sold1,SoldGI,TEST1,TIMEOT,WN,XQT|N|N|131
# ---ALL---|10|---ALL---|N|N|8000
# 
# 
# Definable Fields: (other fields should be left as they are)
# - container_id -	The settings container that will have the parameters for this script in it
# - log_to_file -	(0,1) if set to 1, will create a log file in the agc directory
#
# CHANGES
# 250701-1357 - First Build
#

$api_script = 'mvlistSC';
$php_script = 'dispo_move_list_SC.php';

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
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["dispo"]))					{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))			{$dispo=$_POST["dispo"];}
if (isset($_GET["called_count"]))			{$called_count=$_GET["called_count"];}
	elseif (isset($_POST["called_count"]))	{$called_count=$_POST["called_count"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["pass"]))					{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))			{$pass=$_POST["pass"];}
if (isset($_GET["container_id"]))			{$container_id=$_GET["container_id"];}
	elseif (isset($_POST["container_id"]))	{$container_id=$_POST["container_id"];}
if (isset($_GET["log_to_file"]))			{$log_to_file=$_GET["log_to_file"];}
	elseif (isset($_POST["log_to_file"]))	{$log_to_file=$_POST["log_to_file"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["talk_time"]))				{$talk_time=$_GET["talk_time"];}
	elseif (isset($_POST["talk_time"]))		{$talk_time=$_POST["talk_time"];}
if (isset($_GET["entry_date"]))				{$entry_date=$_GET["entry_date"];}
	elseif (isset($_POST["entry_date"]))	{$entry_date=$_POST["entry_date"];}
if (isset($_GET["populate_sp_old_list"]))			{$populate_sp_old_list=$_GET["populate_sp_old_list"];}
	elseif (isset($_POST["populate_sp_old_list"]))	{$populate_sp_old_list=$_POST["populate_sp_old_list"];}
if (isset($_GET["populate_comm_old_date"]))				{$populate_comm_old_date=$_GET["populate_comm_old_date"];}
	elseif (isset($_POST["populate_comm_old_date"]))	{$populate_comm_old_date=$_POST["populate_comm_old_date"];}

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
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02001',$user,$server_ip,$session_name,$one_mysql_log);}
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
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02002',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}
##### END SETTINGS LOOKUP #####
###########################################

$lead_id = preg_replace('/[^_0-9]/', '', $lead_id);
$list_id = preg_replace('/[^_0-9]/', '', $list_id);
$log_to_file = preg_replace('/[^-_0-9a-zA-Z]/', '', $log_to_file);
$called_count = preg_replace('/[^-_0-9a-zA-Z]/', '', $called_count);
$talk_time = preg_replace('/[^-_0-9a-zA-Z]/', '', $talk_time);
$entry_date = preg_replace('/[^- \:_0-9a-zA-Z]/', '', $entry_date);
$populate_sp_old_list = preg_replace('/[^-_0-9a-zA-Z]/', '', $populate_sp_old_list);
$populate_comm_old_date = preg_replace('/[^-_0-9a-zA-Z]/', '', $populate_comm_old_date);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$dispo = preg_replace('/[^-_0-9a-zA-Z]/', '', $dispo);
	$container_id = preg_replace('/[^-_0-9a-zA-Z]/', '', $container_id);
	}
else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$dispo = preg_replace('/[^-_0-9\p{L}]/u', '', $dispo);
	$container_id = preg_replace('/[^-_0-9\p{L}]/u', '', $container_id);
	}

$DMLcontainer_entry='';
$stmt="SELECT container_entry from vicidial_settings_containers where container_id='$container_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$DMLcontrows = mysqli_num_rows($rslt);
if ($DMLcontrows > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$DMLcontainer_entry = $row[0];
	}
if (strlen($DMLcontainer_entry) < 10)
	{
	echo _QXZ("DML container is invalid:")." |$container_id| \n";
	exit;
	}

# if all call variables are not defined, exit with errors:
if (strlen($lead_id) < 1)
	{
	echo _QXZ("DML lead_id is invalid:")." |$lead_id| \n";
	exit;
	}
if (strlen($list_id) < 2)
	{
	echo _QXZ("DML list_id is invalid:")." |$list_id| \n";
	exit;
	}
if (strlen($called_count) < 1)
	{
	echo _QXZ("DML called_count is invalid:")." |$called_count| \n";
	exit;
	}
if (strlen($dispo) < 1)
	{
	echo _QXZ("DML dispo is invalid:")." |$dispo| \n";
	exit;
	}

##### BEGIN user auth check #####
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
##### END user auth check #####



##### BEGIN go through settings container lines and look for matches with current call #####
$DMLcontainer_entry = preg_replace("/\r|\t|\'|\"| /",'',$DMLcontainer_entry);
$DMLsettings = explode("\n",$DMLcontainer_entry);
$DMLsettings_ct = count($DMLsettings);
$finished_statuses='';
$finished_statusesSQL='';
$sea=0;
$DMLrecords=0;
$lead_moved=0;
$search_count=0;
while ($DMLsettings_ct >= $sea)
	{
	if (strlen($DMLsettings[$sea]) > 7)
		{
		$DMLrecord = explode('|',$DMLsettings[$sea]);
		$temp_list_ids =		'-'.$DMLrecord[0].'-';
		$temp_called_count =	$DMLrecord[1];
		$temp_statuses =		','.$DMLrecord[2].',';
		$temp_status_exclude =	$DMLrecord[3];
		$temp_reset_lead =		$DMLrecord[4];
		$temp_new_list_id =		$DMLrecord[5];

		$temp_list_ids = preg_replace('/[^-0-9]/', '', $temp_list_ids);
		$temp_called_count = preg_replace('/[^0-9]/', '', $temp_called_count);
		$temp_new_list_id = preg_replace('/[^0-9]/', '', $temp_new_list_id);

		if ($DB) {print "DEBUG DML record found: $sea|$temp_list_ids|$temp_called_count|$temp_statuses|$temp_status_exclude|$temp_reset_lead|$temp_new_list_id| \n";}

		$max_score=0;
		$temp_score=0;
		# test for list_id first:
		if (strlen($temp_list_ids) > 1)
			{
			$max_score++;
			if ( (preg_match("/-$list_id-/",$temp_list_ids)) or ($temp_list_ids == '---ALL---') )
				{
				if ($DB) {print "DEBUG DML list_id match: |$temp_list_ids|$list_id| \n";}
				$temp_score++;
				}
			else
				{
				if ($DB) {print "DEBUG DML list_id NO match: |$temp_list_ids|$list_id| \n";}
				}
			}
		# test for called_count next:
		if (strlen($temp_called_count) > 0)
			{
			$max_score++;
			if ($called_count >= $temp_called_count)
				{
				if ($DB) {print "DEBUG DML called_count match: |$temp_called_count|$called_count| \n";}
				$temp_score++;
				}
			else
				{
				if ($DB) {print "DEBUG DML called_count NO match: |$temp_called_count|$called_count| \n";}
				}
			}
		# test for statuses next:
		if (strlen($temp_statuses) > 0)
			{
			$max_score++;
			if ($temp_status_exclude == 'Y')
				{
				if ( (!preg_match("/,$dispo,/",$temp_statuses)) or ($temp_statuses != ',---ALL---,') )
					{
					if ($DB) {print "DEBUG DML dispo exclude match: |$temp_statuses|$dispo| \n";}
					$temp_score++;
					}
				else
					{
					if ($DB) {print "DEBUG DML dispo exclude NO match: |$temp_statuses|$dispo| \n";}
					}
				}
			else
				{
				if ( (preg_match("/,$dispo,/",$temp_statuses)) or ($temp_statuses == ',---ALL---,') )
					{
					if ($DB) {print "DEBUG DML dispo match: |$temp_statuses|$dispo| \n";}
					$temp_score++;
					}
				else
					{
					if ($DB) {print "DEBUG DML dispo NO match: |$temp_statuses|$dispo| \n";}
					}
				}
			}
		# test that new list_id is defined
		if (strlen($temp_new_list_id) > 1)
			{
			$max_score++;
			if ($DB) {print "DEBUG DML temp_new_list_id defined: |$temp_new_list_id| \n";}
			$temp_score++;
			}

		# if each test is a match, then move lead to new list
		if ($temp_score >= $max_score)
			{
			if ($DB) {print "DEBUG DML match test PASSED, moving lead: |$temp_score|$max_score| \n";}

			$stmt = "SELECT count(*) FROM vicidial_list where lead_id='$lead_id' and list_id!='$temp_new_list_id';";
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
				$reset_dialedSQL='';
				if ($temp_reset_lead=='Y') {$reset_dialedSQL=", called_since_last_reset='N'";}
				$stmt="UPDATE vicidial_list SET list_id='$temp_new_list_id' $reset_dialedSQL where lead_id='$lead_id' limit 1;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);
				$lead_moved = ($lead_moved + $affected_rows);

				$campaign_idSQL='';
				$stmtA = "SELECT campaign_id FROM vicidial_lists where list_id='$temp_new_list_id';";
				$rslt=mysql_to_mysqli($stmtA, $link);
				if ($DB) {echo "$stmtA\n";}
				$vlc_ct = mysqli_num_rows($rslt);
				if ($vlc_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$campaign_idSQL = ",campaign_id='$row[0]'";
					}

				$stmtB="UPDATE vicidial_callbacks SET list_id='$temp_new_list_id' $campaign_idSQL where lead_id='$lead_id' limit 1;";
				if ($DB) {echo "$stmtB\n";}
				$rslt=mysql_to_mysqli($stmtB, $link);
				$CBaffected_rows = mysqli_affected_rows($link);

				$SQL_log = "$stmt|$stmtB|$CBaffected_rows|";
				$SQL_log = preg_replace('/;/','',$SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='lead_move_list',value='$lead_id',result='$affected_rows',result_reason='$lead_id',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
				$rslt=mysql_to_mysqli($stmt, $link);

				$MESSAGE = _QXZ("DONE: %1s match found, %2s updated to %3s with %4s status",0,'',$search_count,$affected_rows,$temp_new_list_id,$dispo);
				echo "$MESSAGE\n";

				if ($log_to_file > 0)
					{
					if ($DB) {print "DEBUG DML writing to log file... \n";}
				#	$fp = fopen ("./dispo_move_listSC.txt", "a");
					$fp = fopen ("./dispo_move_listSC.txt", "w");
				#	fwrite ($fp, "$NOW_TIME|$lead_id|$list_id|$dispo|$called_count|$user|$search_count|$lead_moved|$temp_new_list_id|$container_id|$DMLrecords|$temp_score|$max_score|$MESSAGE|\n");
					fwrite ($fp, "$NOW_TIME|\n");
					fclose($fp);
					}
				exit;
				}
			else
				{
				$MESSAGE = _QXZ("DONE: no lead found for ID %1s outside of list %2s",0,'',$lead_id,$temp_new_list_id);
				echo "$MESSAGE\n";
				}
			}
		else
			{
			if ($DB) {print "DEBUG DML match test FAILED, NOT moving lead: |$temp_score|$max_score| \n";}
			}
		$DMLrecords++;
		}
	else
		{
		if ($DB) {print "DEBUG DML no record on this line, NOT moving lead: |$sea|$DMLsettings[$sea]| \n";}
		}
	if ($DB) {print "\n";}
	$sea++;
	}
##### END go through settings container lines and look for matches with current call #####
if ($search_count < 1)
	{
	$MESSAGE = _QXZ("DONE: no matches found for this call against %1s rules for lead %2s",0,'',$DMLrecords,$lead_id);
	echo "$MESSAGE\n";
	}
