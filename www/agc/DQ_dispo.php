<?php
# DQ_dispo.php
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the "Dispo Call URL" field of a campaign
# It should take in the 'lead_id' and 'dispo' to check for the campaign's 
# Demographic Quotas settings, and if there is a match, increment the count
#
# This script is part of the API group and any modifications of data are
# logged to the vicidial_api_log table.
#
# This script is a required part of the Demographic Quotas feature, for more
# information on how it works, read the DEMOGRAPHIC_QUOTAS.txt document.
#
# Example of what to put in the Dispo URL field:
# VARhttp://192.168.1.1/agc/DQ_dispo.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&campaign_id=TESTCAMP&user=--A--user--B--&pass=--A--pass--B--&log_to_file=1
# 
# Example of what to put in the No Agent Call URL field:
# (IMPORTANT: user needs to be NOAGENTURL and pass needs to be set to the call_id)
# VARhttp://192.168.1.1/agc/DQ_dispo.php?lead_id=--A--lead_id--B--&dispo=--A--dispo--B--&campaign_id=TESTCAMP&user=NOAGENTURL&pass=--A--call_id--B--&log_to_file=1
# 
# Definable Fields: (other fields should be left as they are)
# - log_to_file -	(0,1) if set to 1, will create a log file in the agc directory
# - campaign_id -	(MUST BE SET TO THE CAMPAIGN ID)
#

#
# CHANGES
# 230511-1100 - First Build
#

$api_script = 'DQdispo';
$php_script = 'DQ_dispo.php';

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
if (isset($_GET["campaign_id"]))			{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))	{$campaign_id=$_POST["campaign_id"];}
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

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#$DB = '1';	# DEBUG override
$US = '_';
$TD = '---';
$STARTtime = date("U");
$NOW_TIME = date("Y-m-d H:i:s");
$k=0;
$DQupdates=0;
$DQupdate_notes='';
$DQgoals_reached=0;

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
$stmt = "SELECT use_non_latin,enable_languages,language_method,allow_web_debug,demographic_quotas FROM system_settings;";
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
	$SSdemographic_quotas = 	$row[4];
	}
if ($SSallow_web_debug < 1) {$DB=0;}

if ($SSdemographic_quotas < 1)
	{
	echo "demographic_quotas is disabled on this system: $SSdemographic_quotas \n";
	exit;
	}

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

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$dispo = preg_replace('/[^-_0-9a-zA-Z]/', '', $dispo);
	$campaign_id = preg_replace('/[^-_0-9a-zA-Z]/', '', $campaign_id);
	}
else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$dispo = preg_replace('/[^-_0-9\p{L}]/u', '', $dispo);
	$campaign_id = preg_replace('/[^-_0-9\p{L}]/u', '', $campaign_id);
	}

if ($DB>0) {echo "$lead_id|$dispo|$campaign_id|$user|$pass|$DB|$log_to_file|\n";}

if (strlen($dispo) < 1)
	{
	echo "no dispo value given, exiting: $dispo \n";
	exit;
	}
if (strlen($campaign_id) < 1)
	{
	echo "no campaign_id value given, exiting: $campaign_id \n";
	exit;
	}
if (strlen($lead_id) < 1)
	{
	echo "no lead_id value given, exiting: $lead_id \n";
	exit;
	}


# gather campaign details from campaign_id, and what the Demographic Quotas settings are
$DQcontainer='';
$stmt="SELECT demographic_quotas_container,demographic_quotas_rerank from vicidial_campaigns where campaign_id='$campaign_id' and active='Y' and demographic_quotas IN('ENABLED','COMPLETE');";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$DQcamprows = mysqli_num_rows($rslt);
if ($DQcamprows > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$DQcontainer = $row[0];
	$demographic_quotas_rerank = $row[1];
	}
if (strlen($DQcontainer) < 1)
	{
	echo _QXZ("demographic_quotas not enabled on campaign:")." |$campaign_id| \n";
	exit;
	}

$DQcontainer_entry='';
$stmt="SELECT container_entry from vicidial_settings_containers where container_id='$DQcontainer';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$DQcontrows = mysqli_num_rows($rslt);
if ($DQcontrows > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$DQcontainer_entry = $row[0];
	}
if (strlen($DQcontainer_entry) < 10)
	{
	echo _QXZ("demographic_quotas container is invalid:")." |$DQcontainer| \n";
	exit;
	}


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
	# go through DQ container, gather finished_statuses
	$DQcontainer_entry = preg_replace("/\r|\t|\'|\"/",'',$DQcontainer_entry);
	$DQ_settings = explode("\n",$DQcontainer_entry);
	$DQ_settings_ct = count($DQ_settings);
	$finished_statuses='';
	$finished_statusesSQL='';
	$sea=0;
	while ($DQ_settings_ct >= $sea)
		{
		if (preg_match("/^finished_statuses=>|^finished_statuses => /",$DQ_settings[$sea]))
			{
			$finished_statuses = $DQ_settings[$sea];
			$finished_statuses = preg_replace("/^finished_statuses=>|^finished_statuses => /i",'',$finished_statuses);
			$finished_statusesSQL = preg_replace("/,/","','",$finished_statuses);
			$finished_statusesSQL = "'$finished_statusesSQL'";
			if ($DB) {print "DEBUG finished_statuses defined: |$finished_statuses|$finished_statusesSQL| \n";}
			}
		$sea++;
		}
	if (strlen($finished_statuses) < 1)
		{
		echo _QXZ("No finished_statuses defined in container:")." |$DQcontainer|\n";
		exit;
		}

	if (preg_match("/'$dispo'/",$finished_statusesSQL))
		{
		if ($DB) {print "DEBUG finished_statuses match with dispo: |$dispo|$finished_statusesSQL| \n";}

		# confirm lead_id exists in the system, and gather lead field values for demographics
		$stmt="SELECT rank,vendor_lead_code,source_id,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,owner,list_id from vicidial_list where lead_id='$lead_id' limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$VLrows = mysqli_num_rows($rslt);
		if ($DB) {echo "$VLrows|$stmt|\n";}
		if ($VLrows > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$DQfields = array('rank','vendor_lead_code','source_id','title','first_name','middle_initial','last_name','address1','address2','address3','city','state','province','postal_code','country_code','gender','date_of_birth','alt_phone','email','security_phrase','owner');
			$DQfield_values = array("$row[0]","$row[1]","$row[2]","$row[3]","$row[4]","$row[5]","$row[6]","$row[7]","$row[8]","$row[9]","$row[10]","$row[11]","$row[12]","$row[13]","$row[14]","$row[15]","$row[16]","$row[17]","$row[18]","$row[19]","$row[20]");
			$last_list_id = $row[21];

			$DQc=0;
			while ($DQc <= 20)
				{
				$stmt="SELECT quota_goal,quota_count,quota_status from vicidial_demographic_quotas_goals where campaign_id='$campaign_id' and demographic_quotas_container='$DQcontainer' and quota_field='$DQfields[$DQc]' and quota_value='$DQfield_values[$DQc]' and quota_status!='ARCHIVE' limit 1;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$VDQGrows = mysqli_num_rows($rslt);
				if ($DB) {echo "$VDQGrows|$stmt|\n";}
				if ($VDQGrows > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$quota_goal =		$row[0];
					$quota_count =		$row[1];
					$quota_status =		$row[2];
					$new_quota_count =	($quota_count + 1);
					if ($DB) {print "DEBUG DQ field value match: |$DQfields[$DQc]|$DQfield_values[$DQc]|   |$quota_goal|$quota_count|$quota_status| \n";}

					if ($new_quota_count == $quota_goal)
						{
						$DQgoals_reached++;
						if ($DB) {print "DEBUG DQ goal reached: |$new_quota_count == $quota_goal| \n";}
						}

					$stmt="UPDATE vicidial_demographic_quotas_goals SET quota_count=(quota_count+1),last_lead_id='$lead_id',last_list_id='$last_list_id',last_call_date=NOW(),last_status='$dispo' where campaign_id='$campaign_id' and demographic_quotas_container='$DQcontainer' and quota_field='$DQfields[$DQc]' and quota_value='$DQfield_values[$DQc]' and quota_status!='ARCHIVE' limit 1;";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$affected_rows = mysqli_affected_rows($link);
					$DQupdates = ($DQupdates + $affected_rows);
					$DQupdate_notes .= "$DQfields[$DQc] = $DQfield_values[$DQc] update $affected_rows|";
					if ($DB) {print "DEBUG DQ quota goals updated: |$affected_rows|$last_list_id| \n";}
					}
				$DQc++;
				}
			}
		else
			{
			echo _QXZ("Lead ID not found in system:")." |$lead_id|$VLrows|\n";
			exit;
			}
		}

	if ($DQupdates > 0)
		{
		if ($DQgoals_reached > 0)
			{
			if ($demographic_quotas_rerank == 'HOUR')
				{$demographic_quotas_rerank == 'NOW_HOUR';}
			if ($demographic_quotas_rerank == 'NO')
				{$demographic_quotas_rerank == 'NOW';}
			# update campaign to force refresh of DQ leads if one of the goals reached
			$stmt="UPDATE vicidial_campaigns SET demographic_quotas_rerank='$demographic_quotas_rerank' where campaign_id='$campaign_id' limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);
			if ($DB) {echo "$affected_rows|$stmt|\n";}
			}

		$SQL_log = "$DQupdate_notes";
		$SQL_log = preg_replace('/;/','',$SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='DQdispo',value='$lead_id',result='$DQupdates',result_reason='$lead_id',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
		$rslt=mysql_to_mysqli($stmt, $link);

		$MESSAGE = _QXZ("DONE: %1s DQ updates made, for lead_id %2s goals reached %3s",0,'',$DQupdates,$lead_id,$DQgoals_reached);
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
	$MESSAGE = _QXZ("DONE: lead_id %1s is not populated",0,'',$lead_id);
	echo "$MESSAGE\n";
	}


if ($log_to_file > 0)
	{
	$fp = fopen ("./DQ_dispo.txt", "w");
#	fwrite ($fp, "$NOW_TIME|$k|$lead_id|$dispo|$user|$campaign_id|$DB|$log_to_file|$campaign_id|$MESSAGE|\n");
	fwrite ($fp, "$NOW_TIME|\n");
	fclose($fp);
	}
