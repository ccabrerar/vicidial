<?php
# deactivate_lead.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the "Dispo URL" field of a campaign
# or in-group. It should take in the campaign_id to check for the same source_id
# in order to change it's status to whatever duplicate_status is set to. The
# sale_status field is a list of statuses separated by three dashes each '---'
# which contain the statuses for which the process should be run.
#
# This script is part of the API group and any modifications of data are
# logged to the vicidial_api_log table.
#
# This script limits the number of altered leads to 100 per instance and it will
# not run if the search field of the lead is empty.
# http://server/agc/deactivate_lead.php?search_field=source_id&new_status=INACTIV&dispo=--A--dispo--B--&lead_id=--A--lead_id--B--&campaign_check=TEST2&user=--A--user--B--&pass=--A--pass--B--&sale_status=SALE---SSALE---XSALE&log_to_file=1
#
# Example of what to put in the Dispo URL field:
# VARhttp://192.168.1.1/agc/deactivate_lead.php?search_field=vendor_lead_code&new_status=INACTIV&dispo=--A--dispo--B--&lead_id=--A--lead_id--B--&campaign_check=TESTCAMP&user=--A--user--B--&pass=--A--pass--B--&sale_status=SALE---SSALE---XSALE&log_to_file=1
# VARhttp://192.168.1.1/agc/deactivate_lead.php?search_field=phone_number&new_status=--A--dispo--B--&dispo=--A--dispo--B--&lead_id=--A--lead_id--B--&campaign_check=TESTCAMP&user=--A--user--B--&pass=--A--pass--B--&sale_status=BI---CX&log_to_file=1
# 
#
# CHANGES
# 100304-0354 - First Build
# 120223-2124 - Removed logging of good login passwords if webroot writable is enabled
# 130328-0016 - Converted ereg to preg functions
# 130603-2217 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130802-1006 - Changed to PHP mysqli functions
# 140811-0845 - Changed to use QXZ function for echoing text
# 141118-1234 - Formatting changes for QXZ output
# 141216-2130 - Added language settings lookups and user/pass variable standardization
# 170526-2301 - Added additional variable filtering
#

$api_script = 'deactivate';

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
if (isset($_GET["search_field"]))			{$search_field=$_GET["search_field"];}
	elseif (isset($_POST["search_field"]))	{$search_field=$_POST["search_field"];}
if (isset($_GET["campaign_check"]))				{$campaign_check=$_GET["campaign_check"];}
	elseif (isset($_POST["campaign_check"]))	{$campaign_check=$_POST["campaign_check"];}
if (isset($_GET["sale_status"]))			{$sale_status=$_GET["sale_status"];}
	elseif (isset($_POST["sale_status"]))	{$sale_status=$_POST["sale_status"];}
if (isset($_GET["dispo"]))					{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))			{$dispo=$_POST["dispo"];}
if (isset($_GET["new_status"]))				{$new_status=$_GET["new_status"];}
	elseif (isset($_POST["new_status"]))	{$new_status=$_POST["new_status"];}
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

# filter variables
$user = preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass = preg_replace("/\'|\"|\\\\|;| /","",$pass);
$search_field = preg_replace("/\'|\"|\\\\|;| /","",$search_field);
$lead_id = preg_replace('/[^0-9]/','',$lead_id);
$campaign_check = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign_check);
$new_status = preg_replace('/[^-_0-9a-zA-Z]/','',$new_status);

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

if ($DB>0) {echo "$lead_id|$search_field|$campaign_check|$sale_status|$dispo|$new_status|$user|$pass|$DB|$log_to_file|\n";}

if (preg_match("/$TD$dispo$TD/",$sale_status))
	{
	if ($non_latin < 1)
		{
		$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
		}

	$auth=0;
	$auth_message = user_authorization($user,$pass,'',0,0,0,0);
	if ($auth_message == 'GOOD')
		{$auth=1;}

	if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0))
		{
		echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth_message|\n";
		exit;
		}

	$stmt = "SELECT $search_field FROM vicidial_list where lead_id='$lead_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$sv_ct = mysqli_num_rows($rslt);
	if ($sv_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$search_value = $row[0];
		}

	if (strlen($search_value) > 0)
		{
		$stmt="select list_id from vicidial_lists where campaign_id='$campaign_check';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$li_recs = mysqli_num_rows($rslt);
		if ($li_recs > 0)
			{
			$L=0;
			while ($li_recs > $L)
				{
				$row=mysqli_fetch_row($rslt);
				$duplicate_lists .=	"'$row[0]',";
				$L++;
				}
			$duplicate_lists = preg_replace('/,$/','',$duplicate_lists);
			if (strlen($duplicate_lists) < 2) {$duplicate_lists = "''";}
			}

		if (strlen($duplicate_lists) > 4)
			{
			$search_count=0;
			$stmt = "SELECT count(*) FROM vicidial_list where $search_field='$search_value' and list_id IN($duplicate_lists);";
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
				$stmt="UPDATE vicidial_list SET status='$new_status' where $search_field='$search_value' and list_id IN($duplicate_lists) limit 100;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);

				$SQL_log = "$stmt|";
				$SQL_log = preg_replace('/;/','',$SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='deactivate_lead',value='$lead_id',result='$affected_rows',result_reason='$search_field',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
				$rslt=mysql_to_mysqli($stmt, $link);

				$MESSAGE = _QXZ("DONE: %1s duplicates found,",0,'',$search_count)." $affected_rows updated to $new_status from $dispo";
				echo "$MESSAGE\n";
				}
			else
				{
				$MESSAGE = _QXZ("DONE: no duplicates found within")." $campaign_check     |$duplicate_lists|";
				echo "$MESSAGE\n";
				}
			}
		else
			{
			$MESSAGE = _QXZ("DONE: no lists in campaign_check")." $campaign_check";
			echo "$MESSAGE\n";
			}
		}
	else
		{
		$MESSAGE = _QXZ("DONE: %1s is empty for lead %2s",0,'',$search_field,$lead_id);
		echo "$MESSAGE\n";
		}
	}
else
	{
	$MESSAGE = _QXZ("DONE: dispo is not a sale status:")." $dispo";
	echo "$MESSAGE\n";
	}

if ($log_to_file > 0)
	{
	$fp = fopen ("./deactivate_lead.txt", "a");
	fwrite ($fp, "$NOW_TIME|$lead_id|$search_field|$search_value|$campaign_check|$sale_status|$dispo|$new_status|$user|XXXX|$DB|$log_to_file|$MESSAGE|\n");
	fclose($fp);
	}
