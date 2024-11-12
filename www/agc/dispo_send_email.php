<?php
# dispo_send_email.php
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the "Dispo URL" field of a campaign
# or in-group. It will send out an email to a fixed email address as defined
# in a Settings Container as defined in the Admin web interface
#
# This script is part of the API group and any modifications of data are
# logged to the vicidial_api_log table.
#
# Examples of what to put in the Dispo URL field:
# VARhttp://192.168.1.1/agc/dispo_send_email.php?container_id=TEST_CONTAINER&lead_id=--A--lead_id--B--&call_id=--A--call_id--B--&dispo=--A--dispo--B--&user=--A--user--B--&pass=--A--pass--B--&sale_status=SALE---SSALE---XSALE&log_to_file=1&dialed_number=--A--dialed_number--B--
# VARhttp://192.168.1.1/agc/dispo_send_email.php?container_id=TEST_CONTAINER&lead_id=--A--lead_id--B--&call_id=--A--call_id--B--&dispo=--A--dispo--B--&user=--A--user--B--&pass=--A--pass--B--&sale_status=ALL-STATUSES&called_count=--A--called_count--B--&called_count_trigger=40&log_to_file=1&dialed_number=--A--dialed_number--B--
#
#
# Example of what to put in Dead Trigger URL campaign setting field:
# VARhttp://192.168.1.1/agc/dispo_send_email.php?container_id=TEST_CONTAINER&lead_id=--A--lead_id--B--&call_id=--A--call_id--B--&dispo=DEAD&user=NOAGENTURL--A--user--B--&pass=--A--call_id--B--&sale_status=ALL-STATUSES&called_count=--A--called_count--B--&log_to_file=1
# 
# Definable Fields: (other fields should be left as they are)
# - log_to_file -	(0,1) if set to 1, will create a log file in the agc directory
# - sale_status -	(SALE---XSALE) a triple-dash "---" delimited list of the statuses that are to be moved, use ALL-STATUSES to trigger on all calls
# - container_id -	(999,etc...) the Settings Container ID that you want the phone number to be inserted into
# - called_count_trigger -	(1,2,3,...) if set to number greater than 0, will only trigger for called_count at or above set number, default is DISABLED
# - email_to - override of settings-container email setting
# - email_attachment_1,2,3,etc... - local file path to attachment
#
# CHANGES
# 150806-1424 - First Build
# 170329-2145 - Added DID variables and custom fields values
# 170526-2315 - Added additional variable filtering
# 171018-2310 - Added call_notes option
# 171120-0910 - Added ALL-STATUSES option and called_count_trigger option
# 171120-1535 - Added additional_notes option and email attachments options(1-5)
# 171207-0659 - Added option of up to 20 attachments
# 180611-1703 - Added instructions for Dead Trigger URL
# 180909-1907 - Added channel_group variable
# 190129-1855 - Added --A--RUSfullname--B-- special variable flag
# 190521-1715 - Added --A--dispo--B-- and --A--dispo_name--B-- to email_body
# 191013-2113 - Fixes for PHP7
# 200814-1829 - added email_body_html, email_body_utf8 flags
# 201117-2104 - Changes for better compatibility with non-latin data input
# 210615-1033 - Default security fixes, CVE-2021-28854
# 210616-2044 - Added optional CORS support, see options.php for details
# 210823-1623 - Fix for security issue, removed absolute path attachments, now must be in "agc/attachments" directory
# 210823-1947 - Fix to allow for legacy attachment file locations in "agc" directory
# 220127-0942 - Added email_header_attach and allow_sendmail_bypass options.php settings
# 220213-0849 - Added code for Pause Max Email functionality
# 220216-0027 - Fix for very large emails using allow_sendmail_bypass
# 220219-0135 - Added allow_web_debug system setting
# 230113-0839 - Added dialed_number & dialed_label to allowed variables in email subject and body
# 230420-1620 - Added email_display_name as a container option for the email sender
# 230518-1035 - Added in-group and campaign custom fields 1-5, for script/webform/dispo-call-url use
#

$api_script = 'send_email';
$php_script = 'dispo_send_email.php';

require_once("dbconnect_mysqli.php");
require_once("functions.php");

$filedate = date("Ymd");
$filetime = date("H:i:s");
$IP = getenv ("REMOTE_ADDR");
$BR = getenv ("HTTP_USER_AGENT");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
if (isset($_GET["call_id"]))				{$call_id=$_GET["call_id"];}
	elseif (isset($_POST["call_id"]))		{$call_id=$_POST["call_id"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["sale_status"]))			{$sale_status=$_GET["sale_status"];}
	elseif (isset($_POST["sale_status"]))	{$sale_status=$_POST["sale_status"];}
if (isset($_GET["dispo"]))					{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))			{$dispo=$_POST["dispo"];}
if (isset($_GET["container_id"]))			{$container_id=$_GET["container_id"];}
	elseif (isset($_POST["container_id"]))	{$container_id=$_POST["container_id"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["pass"]))					{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))			{$pass=$_POST["pass"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["call_notes"]))				{$call_notes=$_GET["call_notes"];}
	elseif (isset($_POST["call_notes"]))	{$call_notes=$_POST["call_notes"];}
if (isset($_GET["additional_notes"]))			{$additional_notes=$_GET["additional_notes"];}
	elseif (isset($_POST["additional_notes"]))	{$additional_notes=$_POST["additional_notes"];}
if (isset($_GET["log_to_file"]))			{$log_to_file=$_GET["log_to_file"];}
	elseif (isset($_POST["log_to_file"]))	{$log_to_file=$_POST["log_to_file"];}
if (isset($_GET["called_count"]))				{$called_count=$_GET["called_count"];}
	elseif (isset($_POST["called_count"]))		{$called_count=$_POST["called_count"];}
if (isset($_GET["called_count_trigger"]))			{$called_count_trigger=$_GET["called_count_trigger"];}
	elseif (isset($_POST["called_count_trigger"]))	{$called_count_trigger=$_POST["called_count_trigger"];}
if (isset($_GET["email_to"]))				{$email_to=$_GET["email_to"];}
	elseif (isset($_POST["email_to"]))		{$email_to=$_POST["email_to"];}
if (isset($_GET["channel_group"]))			{$channel_group=$_GET["channel_group"];}
	elseif (isset($_POST["channel_group"]))	{$channel_group=$_POST["channel_group"];}
if (isset($_GET["email_attachment_1"]))				{$email_attachment_1=$_GET["email_attachment_1"];}
	elseif (isset($_POST["email_attachment_1"]))	{$email_attachment_1=$_POST["email_attachment_1"];}
if (isset($_GET["email_attachment_2"]))				{$email_attachment_2=$_GET["email_attachment_2"];}
	elseif (isset($_POST["email_attachment_2"]))	{$email_attachment_2=$_POST["email_attachment_2"];}
if (isset($_GET["email_attachment_3"]))				{$email_attachment_3=$_GET["email_attachment_3"];}
	elseif (isset($_POST["email_attachment_3"]))	{$email_attachment_3=$_POST["email_attachment_3"];}
if (isset($_GET["email_attachment_4"]))				{$email_attachment_4=$_GET["email_attachment_4"];}
	elseif (isset($_POST["email_attachment_4"]))	{$email_attachment_4=$_POST["email_attachment_4"];}
if (isset($_GET["email_attachment_5"]))				{$email_attachment_5=$_GET["email_attachment_5"];}
	elseif (isset($_POST["email_attachment_5"]))	{$email_attachment_5=$_POST["email_attachment_5"];}
if (isset($_GET["email_attachment_6"]))				{$email_attachment_6=$_GET["email_attachment_6"];}
	elseif (isset($_POST["email_attachment_6"]))	{$email_attachment_6=$_POST["email_attachment_6"];}
if (isset($_GET["email_attachment_7"]))				{$email_attachment_7=$_GET["email_attachment_7"];}
	elseif (isset($_POST["email_attachment_7"]))	{$email_attachment_7=$_POST["email_attachment_7"];}
if (isset($_GET["email_attachment_8"]))				{$email_attachment_8=$_GET["email_attachment_8"];}
	elseif (isset($_POST["email_attachment_8"]))	{$email_attachment_8=$_POST["email_attachment_8"];}
if (isset($_GET["email_attachment_9"]))				{$email_attachment_9=$_GET["email_attachment_9"];}
	elseif (isset($_POST["email_attachment_9"]))	{$email_attachment_9=$_POST["email_attachment_9"];}
if (isset($_GET["email_attachment_10"]))			{$email_attachment_10=$_GET["email_attachment_10"];}
	elseif (isset($_POST["email_attachment_10"]))	{$email_attachment_10=$_POST["email_attachment_10"];}
if (isset($_GET["email_attachment_11"]))			{$email_attachment_11=$_GET["email_attachment_11"];}
	elseif (isset($_POST["email_attachment_11"]))	{$email_attachment_11=$_POST["email_attachment_11"];}
if (isset($_GET["email_attachment_12"]))			{$email_attachment_12=$_GET["email_attachment_12"];}
	elseif (isset($_POST["email_attachment_12"]))	{$email_attachment_12=$_POST["email_attachment_12"];}
if (isset($_GET["email_attachment_13"]))			{$email_attachment_13=$_GET["email_attachment_13"];}
	elseif (isset($_POST["email_attachment_13"]))	{$email_attachment_13=$_POST["email_attachment_13"];}
if (isset($_GET["email_attachment_14"]))			{$email_attachment_14=$_GET["email_attachment_14"];}
	elseif (isset($_POST["email_attachment_14"]))	{$email_attachment_14=$_POST["email_attachment_14"];}
if (isset($_GET["email_attachment_15"]))			{$email_attachment_15=$_GET["email_attachment_15"];}
	elseif (isset($_POST["email_attachment_15"]))	{$email_attachment_15=$_POST["email_attachment_15"];}
if (isset($_GET["email_attachment_16"]))			{$email_attachment_16=$_GET["email_attachment_16"];}
	elseif (isset($_POST["email_attachment_16"]))	{$email_attachment_16=$_POST["email_attachment_16"];}
if (isset($_GET["email_attachment_17"]))			{$email_attachment_17=$_GET["email_attachment_17"];}
	elseif (isset($_POST["email_attachment_17"]))	{$email_attachment_17=$_POST["email_attachment_17"];}
if (isset($_GET["email_attachment_18"]))			{$email_attachment_18=$_GET["email_attachment_18"];}
	elseif (isset($_POST["email_attachment_18"]))	{$email_attachment_18=$_POST["email_attachment_18"];}
if (isset($_GET["email_attachment_19"]))			{$email_attachment_19=$_GET["email_attachment_19"];}
	elseif (isset($_POST["email_attachment_19"]))	{$email_attachment_19=$_POST["email_attachment_19"];}
if (isset($_GET["email_attachment_20"]))			{$email_attachment_20=$_GET["email_attachment_20"];}
	elseif (isset($_POST["email_attachment_20"]))	{$email_attachment_20=$_POST["email_attachment_20"];}
if (isset($_GET["dialed_number"]))			{$dialed_number=$_GET["dialed_number"];}
	elseif (isset($_POST["dialed_number"]))	{$dialed_number=$_POST["dialed_number"];}
if (isset($_GET["dialed_label"]))			{$dialed_label=$_GET["dialed_label"];}
	elseif (isset($_POST["dialed_label"]))	{$dialed_label=$_POST["dialed_label"];}
if (isset($_GET["camp_custom_one"]))			{$camp_custom_one=$_GET["camp_custom_one"];}
	elseif (isset($_POST["camp_custom_one"]))	{$camp_custom_one=$_POST["camp_custom_one"];}
if (isset($_GET["camp_custom_two"]))			{$camp_custom_two=$_GET["camp_custom_two"];}
	elseif (isset($_POST["camp_custom_two"]))	{$camp_custom_two=$_POST["camp_custom_two"];}
if (isset($_GET["camp_custom_three"]))			{$camp_custom_three=$_GET["camp_custom_three"];}
	elseif (isset($_POST["camp_custom_three"]))	{$camp_custom_three=$_POST["camp_custom_three"];}
if (isset($_GET["camp_custom_four"]))			{$camp_custom_four=$_GET["camp_custom_four"];}
	elseif (isset($_POST["camp_custom_four"]))	{$camp_custom_four=$_POST["camp_custom_four"];}
if (isset($_GET["camp_custom_five"]))			{$camp_custom_five=$_GET["camp_custom_five"];}
	elseif (isset($_POST["camp_custom_five"]))	{$camp_custom_five=$_POST["camp_custom_five"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#$DB = '1';	# DEBUG override
$US = '_';
$TD = '---';
$STARTtime = date("U");
$NOW_TIME = date("Y-m-d H:i:s");
$sale_status = "$TD$sale_status$TD";
$search_value='';
$match_found=0;
$k=0;
$mel=1;					# Mysql Error Log enabled = 1
$mysql_log_count=20;
$email_format = 'TEXT';
$email_charset = 'iso-8859-1';
#$email_attachment_path = '/dev/null';
$email_attachment_path = './attachments';
$email_attachment_path_legacy = '.';
$email_header_attach='0';
$sendmail_bypass=0;
$allow_sendmail_bypass='';

# filter variables
$user=preg_replace("/\'|\"|\\\\|;| |\|/","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| |\|/","",$pass);

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

header ("Content-type: text/html; charset=utf-8");

$EHA='';
if ($email_header_attach > 0) {$EHA="\n\n";}

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60002',$user,$server_ip,$session_name,$one_mysql_log);}
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
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60001',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}
##### END SETTINGS LOOKUP #####
###########################################

$call_id = preg_replace('/[^-_0-9a-zA-Z]/', '', $call_id);
$lead_id = preg_replace('/[^_0-9a-zA-Z]/', '', $lead_id);
$call_notes=preg_replace("/\\\\/","",$call_notes);
$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);
$additional_notes=preg_replace("/\\\\/","",$additional_notes);

$email_attachment_1=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_1);
$email_attachment_2=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_2);
$email_attachment_3=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_3);
$email_attachment_4=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_4);
$email_attachment_5=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_5);
$email_attachment_6=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_6);
$email_attachment_7=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_7);
$email_attachment_8=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_8);
$email_attachment_9=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_9);
$email_attachment_10=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_10);
$email_attachment_11=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_11);
$email_attachment_12=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_12);
$email_attachment_13=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_13);
$email_attachment_14=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_14);
$email_attachment_15=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_15);
$email_attachment_16=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_16);
$email_attachment_17=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_17);
$email_attachment_18=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_18);
$email_attachment_19=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_19);
$email_attachment_20=preg_replace("/\\\\|^\/|\.+\/|;/","",$email_attachment_20);

$email_attachment_1=preg_replace("/etc\//","",$email_attachment_1);
$email_attachment_2=preg_replace("/etc\//","",$email_attachment_2);
$email_attachment_3=preg_replace("/etc\//","",$email_attachment_3);
$email_attachment_4=preg_replace("/etc\//","",$email_attachment_4);
$email_attachment_5=preg_replace("/etc\//","",$email_attachment_5);
$email_attachment_6=preg_replace("/etc\//","",$email_attachment_6);
$email_attachment_7=preg_replace("/etc\//","",$email_attachment_7);
$email_attachment_8=preg_replace("/etc\//","",$email_attachment_8);
$email_attachment_9=preg_replace("/etc\//","",$email_attachment_9);
$email_attachment_10=preg_replace("/etc\//","",$email_attachment_10);
$email_attachment_11=preg_replace("/etc\//","",$email_attachment_11);
$email_attachment_12=preg_replace("/etc\//","",$email_attachment_12);
$email_attachment_13=preg_replace("/etc\//","",$email_attachment_13);
$email_attachment_14=preg_replace("/etc\//","",$email_attachment_14);
$email_attachment_15=preg_replace("/etc\//","",$email_attachment_15);
$email_attachment_16=preg_replace("/etc\//","",$email_attachment_16);
$email_attachment_17=preg_replace("/etc\//","",$email_attachment_17);
$email_attachment_18=preg_replace("/etc\//","",$email_attachment_18);
$email_attachment_19=preg_replace("/etc\//","",$email_attachment_19);
$email_attachment_20=preg_replace("/etc\//","",$email_attachment_20);

$email_to = preg_replace('/[^-\.\:\/\@\_0-9\p{L}]/u','',$email_to);
$log_to_file = preg_replace('/[^-_0-9a-zA-Z]/', '', $log_to_file);
$called_count = preg_replace('/[^-_0-9a-zA-Z]/', '', $called_count);
$called_count_trigger = preg_replace('/[^-_0-9a-zA-Z]/', '', $called_count_trigger);
$dialed_number = preg_replace('/[^-_0-9a-zA-Z]/', '', $dialed_number);
$dialed_label = preg_replace('/[^-_0-9a-zA-Z]/', '', $dialed_label);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$container_id = preg_replace('/[^-_0-9a-zA-Z]/', '', $container_id);
	$sale_status = preg_replace('/[^-_0-9a-zA-Z]/', '', $sale_status);
	$dispo = preg_replace('/[^-_0-9a-zA-Z]/', '', $dispo);
	$channel_group = preg_replace('/[^-_0-9a-zA-Z]/', '', $channel_group);
	$camp_custom_one = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_one);
	$camp_custom_two = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_two);
	$camp_custom_three = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_three);
	$camp_custom_four = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_four);
	$camp_custom_five  = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_five );
	}
else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$container_id = preg_replace('/[^-_0-9\p{L}]/u', '', $container_id);
	$sale_status = preg_replace('/[^-_0-9\p{L}]/u', '', $sale_status);
	$dispo = preg_replace('/[^-_0-9\p{L}]/u', '', $dispo);
	$channel_group = preg_replace('/[^-_0-9\p{L}]/u', '', $channel_group);
	$camp_custom_one = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_one);
	$camp_custom_two = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_two);
	$camp_custom_three = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_three);
	$camp_custom_four = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_four);
	$camp_custom_five  = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_five );
	}


if ($DB>0) {echo "$lead_id|$container_id|$call_id|$sale_status|$dispo|$new_status|$called_count|$called_count_trigger|$user|$pass|$DB|$log_to_file|\n";}

if ( (preg_match("/$TD$dispo$TD/",$sale_status)) or (preg_match("/ALL-STATUSES/",$sale_status)) )
	{
	if ( ( (strlen($called_count_trigger)>0) and ($called_count >= $called_count_trigger) ) or (strlen($called_count_trigger)<1) or ($called_count_trigger < 1) )
		{
		$match_found=1;
		}
	}

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
		if (strlen($user) > 11) {$user = preg_replace("/NOAGENTURL/",'',$user);}
		$PADlead_id = sprintf("%010s", $lead_id);

		if ( (preg_match("/PAUSEMAX/",$lead_id)) and (preg_match("/$user$/",$pass)) )
			{
			$one_minute_ago = date("Y-m-d H:i:s", mktime(date("H"),date("i")-1,date("s"),date("m"),date("d"),date("Y")));

			$stmt="SELECT count(*) from vicidial_user_log where user='$user' and event_date > \"$one_minute_ago\" and event='TIMEOUTLOGOUT';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60015',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			$authlive=$row[0];
			$auth=$row[0];
			if ($authlive < 1)
				{
				echo _QXZ("User Not Found:")." 3|$user|$authlive|\n";
				exit;
				}
			}
		else
			{
			if ( (strlen($pass) > 15) and (preg_match("/$PADlead_id$/",$pass)) )
				{
				$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));

				$stmt="SELECT count(*) from vicidial_log_extended where caller_code='$pass' and call_date > \"$four_hours_ago\";";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60003',$user,$server_ip,$session_name,$one_mysql_log);}
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
		}
	else
		{
		$auth=0;
		$auth_message = user_authorization($user,$pass,'',0,0,0,0,'dispo_send_email');
		if ($auth_message == 'GOOD')
			{$auth=1;}

		if ($stage == 'offline')
			{$authlive=1;}
		else
			{
			$stmt="SELECT count(*) from vicidial_live_agents where user='$user';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60004',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			$authlive=$row[0];
			}
		}

	if ( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0) or ($authlive==0))
		{
		echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth|$authlive|$auth_message|\n";
		exit;
		}

	if ( (strlen($lead_id) > 0) and (strlen($container_id) > 1) )
		{
		$search_count=0;
		$stmt = "SELECT count(*) FROM vicidial_settings_containers where container_id='$container_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60005',$user,$server_ip,$session_name,$one_mysql_log);}
		if ($DB) {echo "$stmt\n";}
		$sc_ct = mysqli_num_rows($rslt);
		if ($sc_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$SC_count = $row[0];
			}

		if ($SC_count > 0)
			{
			$stmt = "SELECT container_entry FROM vicidial_settings_containers where container_id='$container_id';";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60006',$user,$server_ip,$session_name,$one_mysql_log);}
			if ($DB) {echo "$stmt\n";}
			$sc_ct = mysqli_num_rows($rslt);
			if ($sc_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$container_entry =	$row[0];
				$container_ARY = explode("\n",$container_entry);
				$email_body_gather=0;
				$p=0;
				$container_ct = count($container_ARY);
				while ($p <= $container_ct)
					{
					$line = $container_ARY[$p];
					if ($email_body_gather < 1)
						{
						$line = preg_replace("/>|\n|\r|\t|\#.*|;.*/",'',$line);
						if (strlen($email_to) < 6)
							{
							if (preg_match("/^email_to/",$line))
								{$email_to = $line;   $email_to = trim(preg_replace("/.*=/",'',$email_to));}
							}
						if (preg_match("/^email_from/",$line))
							{$email_from = $line;   $email_from = trim(preg_replace("/.*=/",'',$email_from));}
						if (preg_match("/^email_display_name/",$line))
							{$email_display_name = $line;   $email_display_name = trim(preg_replace("/.*=/",'',$email_display_name));}
						if (preg_match("/^email_subject/",$line))
							{$email_subject = $line;   $email_subject = trim(preg_replace("/.*=/",'',$email_subject));}
						if (preg_match("/^email_body_html/",$line))
							{$email_format = 'HTML';}
						if (preg_match("/^email_body_utf8/",$line))
							{$email_charset = 'utf-8';}
						if (preg_match("/^email_body_begin/",$line))
							{$email_body = $line;   $email_body = trim(preg_replace("/.*=/",'',$email_body)) . "\n";   $email_body_gather++;}
						if ( (preg_match("/^sendmail_bypass/",$line)) and (strlen($allow_sendmail_bypass) > 2) )
							{$sendmail_bypass=1;}
						}
					else
						{
						if (preg_match("/^email_body_end/",$line))
							{$email_body_gather=0;}
						else
							{$email_body .= $line;}
						}
					$p++;
					}

				if ( (strlen($email_to) > 5) and (strlen($email_from) > 5) and (strlen($email_subject) > 1) and (strlen($email_body) > 1) )
					{
					if ( (preg_match('/--A--/i',$email_subject)) or (preg_match('/--A--/i',$email_body)) or (preg_match('/--A--/i',$email_to)) or (preg_match('/--A--/i',$email_from)) or (preg_match('/--A--/i',$email_display_name)) )
						{
						##### grab the data from vicidial_list for the lead_id
						$stmt="SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id FROM vicidial_list where lead_id=$lead_id LIMIT 1;";
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60007',$user,$server_ip,$session_name,$one_mysql_log);}
						if ($DB) {echo "$stmt\n";}
						$list_lead_ct = mysqli_num_rows($rslt);
						if ($list_lead_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$entry_date		= urlencode(trim($row[1]));
							$dispo			= urlencode(trim($row[3]));
							$tsr			= urlencode(trim($row[4]));
							$vendor_id		= urlencode(trim($row[5]));
							$vendor_lead_code	= urlencode(trim($row[5]));
							$source_id		= urlencode(trim($row[6]));
							$list_id		= urlencode(trim($row[7]));
							$gmt_offset_now	= urlencode(trim($row[8]));
							$phone_code		= urlencode(trim($row[10]));
							$phone_number	= urlencode(trim($row[11]));
							$title			= urlencode(trim($row[12]));
							$first_name		= urlencode(trim($row[13]));
							$middle_initial	= urlencode(trim($row[14]));
							$last_name		= urlencode(trim($row[15]));
							$address1		= urlencode(trim($row[16]));
							$address2		= urlencode(trim($row[17]));
							$address3		= urlencode(trim($row[18]));
							$city			= urlencode(trim($row[19]));
							$state			= urlencode(trim($row[20]));
							$province		= urlencode(trim($row[21]));
							$postal_code	= urlencode(trim($row[22]));
							$country_code	= urlencode(trim($row[23]));
							$gender			= urlencode(trim($row[24]));
							$date_of_birth	= urlencode(trim($row[25]));
							$alt_phone		= urlencode(trim($row[26]));
							$email			= urlencode(trim($row[27]));
							$security_phrase	= urlencode(trim($row[28]));
							$comments		= urlencode(trim($row[29]));
							$called_count	= urlencode(trim($row[30]));
							$rank			= urlencode(trim($row[32]));
							$owner			= urlencode(trim($row[33]));
							$entry_list_id	= urlencode(trim($row[34]));
							}

						if ( (preg_match('/list_name--B--|list_description--B--/i',$email_subject)) or (preg_match('/list_name--B--|list_description--B--/i',$email_body)) )
							{
							$stmt = "SELECT list_name,list_description from vicidial_lists where list_id='$list_id' limit 1;";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60008',$user,$server_ip,$session_name,$one_mysql_log);}
							$VL_ln_ct = mysqli_num_rows($rslt);
							if ($VL_ln_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$list_name =	urlencode(trim($row[0]));
								$list_description = urlencode(trim($row[1]));
								}
							}

						if ( (preg_match('/--A--did_|--A--uniqueid/i',$email_subject)) or (preg_match('/--A--did_|--A--uniqueid/i',$email_body)) )
							{
							$uniqueid='';

							$stmt = "SELECT uniqueid from vicidial_log_extended where caller_code='$call_id' order by call_date desc limit 1;";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60009',$user,$server_ip,$session_name,$one_mysql_log);}
							$VDIDL_ct = mysqli_num_rows($rslt);
							if ($VDIDL_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$uniqueid	=	$row[0];
								}
							}

						if ( (preg_match('/--A--did_/i',$email_subject)) or (preg_match('/--A--did_/i',$email_body)) )
							{
							$DID_id='';
							$DID_extension='';
							$DID_pattern='';
							$DID_description='';
							$DID_carrier_description='';
							$DID_custom_one='';
							$DID_custom_two='';
							$DID_custom_three='';
							$DID_custom_four='';
							$DID_custom_five='';

							$stmt = "SELECT did_id,extension from vicidial_did_log where uniqueid='$uniqueid' order by call_date desc limit 1;";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60010',$user,$server_ip,$session_name,$one_mysql_log);}
							$VDIDL_ct = mysqli_num_rows($rslt);
							if ($VDIDL_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$DID_id	=			$row[0];
								$DID_extension	=	$row[1];

								$stmt = "SELECT did_pattern,did_description,did_carrier_description,custom_one,custom_two,custom_three,custom_four,custom_five from vicidial_inbound_dids where did_id='$DID_id' limit 1;";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60011',$user,$server_ip,$session_name,$one_mysql_log);}
								$VDIDL_ct = mysqli_num_rows($rslt);
								if ($VDIDL_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$DID_pattern =				$row[0];
									$DID_description =			$row[1];
									$DID_carrier_description =	$row[2];
									$DID_custom_one =			$row[3];
									$DID_custom_two=			$row[4];
									$DID_custom_three=			$row[5];
									$DID_custom_four=			$row[6];
									$DID_custom_five=			$row[7];
									}
								}
							}

						if ( (preg_match('/--A--camp_custom_|--A--campaign/i',$email_subject)) or (preg_match('/--A--camp_custom_|--A--campaign/i',$email_body)) or (preg_match('/--A--camp_custom_/i',$email_from)) or (preg_match('/--A--camp_custom_/i',$email_to)) or (preg_match('/--A--camp_custom_/i',$email_display_name)) )
							{
							$camp_custom_one='';
							$camp_custom_two='';
							$camp_custom_three='';
							$camp_custom_four='';
							$camp_custom_five='';

							$stmt = "SELECT campaign_id from vicidial_log where uniqueid='$uniqueid' order by call_date desc limit 1;";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60016',$user,$server_ip,$session_name,$one_mysql_log);}
							$VDIDL_ct = mysqli_num_rows($rslt);
							if ($VDIDL_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$campaign	=			$row[0];
								}
							else
								{
								$stmt = "SELECT campaign_id from vicidial_live_agents where user='$user' limit 1;";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60017',$user,$server_ip,$session_name,$one_mysql_log);}
								$VDIDL_ct = mysqli_num_rows($rslt);
								if ($VDIDL_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$campaign	=			$row[0];
									}
								}
							if (strlen($campaign) > 0)
								{
								$stmt = "SELECT custom_one,custom_two,custom_three,custom_four,custom_five from vicidial_campaigns where campaign_id='$campaign' limit 1;";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60018',$user,$server_ip,$session_name,$one_mysql_log);}
								$VDIDL_ct = mysqli_num_rows($rslt);
								if ($VDIDL_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$camp_custom_one =		$row[0];
									$camp_custom_two=		$row[1];
									$camp_custom_three=		$row[2];
									$camp_custom_four=		$row[3];
									$camp_custom_five=		$row[4];
									}
								}
							}

						if ( (preg_match('/--A--ig_custom_|--A--group_id/i',$email_subject)) or (preg_match('/--A--ig_custom_|--A--group_id/i',$email_body)) or (preg_match('/--A--ig_custom_/i',$email_from)) or (preg_match('/--A--ig_custom_/i',$email_to)) or (preg_match('/--A--ig_custom_/i',$email_display_name)) )
							{
							$ig_custom_one='';
							$ig_custom_two='';
							$ig_custom_three='';
							$ig_custom_four='';
							$ig_custom_five='';

							$stmt = "SELECT campaign_id from vicidial_closer_log where uniqueid='$uniqueid' order by call_date desc limit 1;";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60019',$user,$server_ip,$session_name,$one_mysql_log);}
							$VDIDL_ct = mysqli_num_rows($rslt);
							if ($VDIDL_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$temp_group_id	=			$row[0];

								$stmt = "SELECT custom_one,custom_two,custom_three,custom_four,custom_five from vicidial_inbound_groups where group_id='$temp_group_id' limit 1;";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60020',$user,$server_ip,$session_name,$one_mysql_log);}
								$VDIDL_ct = mysqli_num_rows($rslt);
								if ($VDIDL_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$ig_custom_one =		$row[0];
									$ig_custom_two=			$row[1];
									$ig_custom_three=		$row[2];
									$ig_custom_four=		$row[3];
									$ig_custom_five=		$row[4];
									}
								}
							}

						$stmt = "SELECT custom_one,custom_two,custom_three,custom_four,custom_five,full_name,user_group,email from vicidial_users where user='$user';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60012',$user,$server_ip,$session_name,$one_mysql_log);}
						$VUC_ct = mysqli_num_rows($rslt);
						if ($VUC_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$user_custom_one =		urlencode(trim($row[0]));
							$user_custom_two =		urlencode(trim($row[1]));
							$user_custom_three =	urlencode(trim($row[2]));
							$user_custom_four =		urlencode(trim($row[3]));
							$user_custom_five =		urlencode(trim($row[4]));
							$fullname =				urlencode(trim($row[5]));
							$user_group =			urlencode(trim($row[6]));
							$agent_email =			urlencode(trim($row[7]));
							$RUSfullname = preg_replace("/^.*_/",'',$fullname);
							}
						
						if ( (preg_match('/--A--CF_uses_custom_fields--B--/i',$email_subject)) or (preg_match('/--A--CF_uses_custom_fields--B--/i',$email_body)) )
							{
							### find the names of all custom fields, if any
							$stmt = "SELECT field_label,field_type FROM vicidial_lists_fields where list_id='$entry_list_id' and field_type NOT IN('SCRIPT','DISPLAY') and field_label NOT IN('entry_date','vendor_lead_code','source_id','list_id','gmt_offset_now','called_since_last_reset','phone_code','phone_number','title','first_name','middle_initial','last_name','address1','address2','address3','city','state','province','postal_code','country_code','gender','date_of_birth','alt_phone','email','security_phrase','comments','called_count','last_local_call_time','rank','owner');";
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60013',$user,$server_ip,$session_name,$one_mysql_log);}
							if ($DB) {echo "$stmt\n";}
							$cffn_ct = mysqli_num_rows($rslt);
							$field_name_id = array();
							$d=0;   $field_query_SQL='';
							while ($cffn_ct > $d)
								{
								$row=mysqli_fetch_row($rslt);
								$field_name_id[$d] = $row[0];
								$field_query_SQL .= "$row[0],";
								$d++;
								}
							if ($d > 0)
								{
								$field_query_SQL = preg_replace("/,$/",'',$field_query_SQL);
								$stmt="SELECT $field_query_SQL FROM custom_$entry_list_id where lead_id=$lead_id LIMIT 1;";
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'60014',$user,$server_ip,$session_name,$one_mysql_log);}
								if ($DB) {echo "$stmt\n";}
								$list_lead_ct = mysqli_num_rows($rslt);
								if ($list_lead_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$d=0;
									while ($cffn_ct > $d)
										{
										$form_field_value = $row[$d];
										$field_name_tag = "--A--" . $field_name_id[$d] . "--B--";
										$email_subject = preg_replace("/$field_name_tag/i","$form_field_value",$email_subject);
										$email_body = preg_replace("/$field_name_tag/i","$form_field_value",$email_body);
											if ($DB) {echo "$d|$field_name_id[$d]|$field_name_tag|$form_field_value|<br>\n";}
										$d++;
										}
									}
								}
							}
						}

					### populate variables in email_subject
					if (preg_match('/--A--/i',$email_subject))
						{
						$email_subject = preg_replace('/^VAR|--A--CF_uses_custom_fields--B--/','',$email_subject);
						$email_subject = preg_replace('/--A--lead_id--B--/i',"$lead_id",$email_subject);
						$email_subject = preg_replace('/--A--vendor_id--B--/i',"$vendor_id",$email_subject);
						$email_subject = preg_replace('/--A--vendor_lead_code--B--/i',"$vendor_lead_code",$email_subject);
						$email_subject = preg_replace('/--A--list_id--B--/i',"$list_id",$email_subject);
						$email_subject = preg_replace('/--A--list_name--B--/i',"$list_name",$email_subject);
						$email_subject = preg_replace('/--A--list_description--B--/i',"$list_description",$email_subject);
						$email_subject = preg_replace('/--A--gmt_offset_now--B--/i',"$gmt_offset_now",$email_subject);
						$email_subject = preg_replace('/--A--phone_code--B--/i',"$phone_code",$email_subject);
						$email_subject = preg_replace('/--A--phone_number--B--/i',"$phone_number",$email_subject);
						$email_subject = preg_replace('/--A--title--B--/i',"$title",$email_subject);
						$email_subject = preg_replace('/--A--first_name--B--/i',"$first_name",$email_subject);
						$email_subject = preg_replace('/--A--middle_initial--B--/i',"$middle_initial",$email_subject);
						$email_subject = preg_replace('/--A--last_name--B--/i',"$last_name",$email_subject);
						$email_subject = preg_replace('/--A--address1--B--/i',"$address1",$email_subject);
						$email_subject = preg_replace('/--A--address2--B--/i',"$address2",$email_subject);
						$email_subject = preg_replace('/--A--address3--B--/i',"$address3",$email_subject);
						$email_subject = preg_replace('/--A--city--B--/i',"$city",$email_subject);
						$email_subject = preg_replace('/--A--state--B--/i',"$state",$email_subject);
						$email_subject = preg_replace('/--A--province--B--/i',"$province",$email_subject);
						$email_subject = preg_replace('/--A--postal_code--B--/i',"$postal_code",$email_subject);
						$email_subject = preg_replace('/--A--country_code--B--/i',"$country_code",$email_subject);
						$email_subject = preg_replace('/--A--gender--B--/i',"$gender",$email_subject);
						$email_subject = preg_replace('/--A--date_of_birth--B--/i',"$date_of_birth",$email_subject);
						$email_subject = preg_replace('/--A--alt_phone--B--/i',"$alt_phone",$email_subject);
						$email_subject = preg_replace('/--A--email--B--/i',"$email",$email_subject);
						$email_subject = preg_replace('/--A--security_phrase--B--/i',"$security_phrase",$email_subject);
						$email_subject = preg_replace('/--A--comments--B--/i',"$comments",$email_subject);
						$email_subject = preg_replace('/--A--user--B--/i',"$user",$email_subject);
						$email_subject = preg_replace('/--A--pass--B--/i',"$orig_pass",$email_subject);
						$email_subject = preg_replace('/--A--original_phone_login--B--/i',"$original_phone_login",$email_subject);
						$email_subject = preg_replace('/--A--phone_pass--B--/i',"$phone_pass",$email_subject);
						$email_subject = preg_replace('/--A--fronter--B--/i',"$fronter",$email_subject);
						$email_subject = preg_replace('/--A--closer--B--/i',"$user",$email_subject);
						$email_subject = preg_replace('/--A--SQLdate--B--/i',"$NOW_TIME",$email_subject);
						$email_subject = preg_replace('/--A--epoch--B--/i',"$epoch",$email_subject);
						$email_subject = preg_replace('/--A--source_id--B--/i',"$source_id",$email_subject);
						$email_subject = preg_replace('/--A--rank--B--/i',"$rank",$email_subject);
						$email_subject = preg_replace('/--A--owner--B--/i',"$owner",$email_subject);
						$email_subject = preg_replace('/--A--entry_list_id--B--/i',"$entry_list_id",$email_subject);
						$email_subject = preg_replace('/--A--call_id--B--/i',urlencode(trim($call_id)),$email_subject);
						$email_subject = preg_replace('/--A--entry_date--B--/i',"$entry_date",$email_subject);
						$email_subject = preg_replace('/--A--fullname--B--/i',"$fullname",$email_subject);
						$email_subject = preg_replace('/--A--RUSfullname--B--/i',"$RUSfullname",$email_subject);
						$email_subject = preg_replace('/--A--user_custom_one--B--/i',"$user_custom_one",$email_subject);
						$email_subject = preg_replace('/--A--user_custom_two--B--/i',"$user_custom_two",$email_subject);
						$email_subject = preg_replace('/--A--user_custom_three--B--/i',"$user_custom_three",$email_subject);
						$email_subject = preg_replace('/--A--user_custom_four--B--/i',"$user_custom_four",$email_subject);
						$email_subject = preg_replace('/--A--user_custom_five--B--/i',"$user_custom_five",$email_subject);
						$email_subject = preg_replace('/--A--camp_custom_one--B--/i',"$camp_custom_one",$email_subject);
						$email_subject = preg_replace('/--A--camp_custom_two--B--/i',"$camp_custom_two",$email_subject);
						$email_subject = preg_replace('/--A--camp_custom_three--B--/i',"$camp_custom_three",$email_subject);
						$email_subject = preg_replace('/--A--camp_custom_four--B--/i',"$camp_custom_four",$email_subject);
						$email_subject = preg_replace('/--A--camp_custom_five--B--/i',"$camp_custom_five",$email_subject);
						$email_subject = preg_replace('/--A--user_group--B--/i',urlencode(trim($user_group)),$email_subject);
						$email_subject = preg_replace('/--A--agent_email--B--/i',"$agent_email",$email_subject);
						$email_subject = preg_replace('/--A--did_id--B--/i',"$DID_id",$email_subject);
						$email_subject = preg_replace('/--A--did_extension--B--/i',"$DID_extension",$email_subject);
						$email_subject = preg_replace('/--A--did_pattern--B--/i',"$DID_pattern",$email_subject);
						$email_subject = preg_replace('/--A--did_description--B--/i',"$DID_description",$email_subject);
						$email_subject = preg_replace('/--A--did_carrier_description--B--/i',"$DID_carrier_description",$email_subject);
						$email_subject = preg_replace('/--A--did_custom_one--B--/i',"$DID_custom_one",$email_subject);
						$email_subject = preg_replace('/--A--did_custom_two--B--/i',"$DID_custom_two",$email_subject);
						$email_subject = preg_replace('/--A--did_custom_three--B--/i',"$DID_custom_three",$email_subject);
						$email_subject = preg_replace('/--A--did_custom_four--B--/i',"$DID_custom_four",$email_subject);
						$email_subject = preg_replace('/--A--did_custom_five--B--/i',"$DID_custom_five",$email_subject);
						$email_subject = preg_replace('/--A--ig_custom_one--B--/i',"$ig_custom_one",$email_subject);
						$email_subject = preg_replace('/--A--ig_custom_two--B--/i',"$ig_custom_two",$email_subject);
						$email_subject = preg_replace('/--A--ig_custom_three--B--/i',"$ig_custom_three",$email_subject);
						$email_subject = preg_replace('/--A--ig_custom_four--B--/i',"$ig_custom_four",$email_subject);
						$email_subject = preg_replace('/--A--ig_custom_five--B--/i',"$ig_custom_five",$email_subject);
						$email_subject = preg_replace('/--A--uniqueid--B--/i',"$uniqueid",$email_subject);
						$email_subject = preg_replace('/--A--group--B--/i',"$channel_group",$email_subject);
						$email_subject = preg_replace('/--A--channel_group--B--/i',"$channel_group",$email_subject);
						$email_subject = preg_replace('/--A--dialed_number--B--/i',"$dialed_number",$email_subject);
						$email_subject = preg_replace('/--A--dialed_label--B--/i',"$dialed_label",$email_subject);
						$email_subject = preg_replace('/--A--campaign--B--/i',"$campaign",$email_subject);
						$email_subject = preg_replace('/--A--group_id--B--/i',"$temp_group_id",$email_subject);

						# not currently active
						$email_subject = preg_replace('/--A--phone_login--B--/i',"$phone_login",$email_subject);
						$email_subject = preg_replace('/--A--customer_zap_channel--B--/i',"$customer_zap_channel",$email_subject);
						$email_subject = preg_replace('/--A--customer_server_ip--B--/i',"$customer_server_ip",$email_subject);
						$email_subject = preg_replace('/--A--server_ip--B--/i',"$server_ip",$email_subject);
						$email_subject = preg_replace('/--A--SIPexten--B--/i',"$SIPexten",$email_subject);
						$email_subject = preg_replace('/--A--session_id--B--/i',"$session_id",$email_subject);
						$email_subject = preg_replace('/--A--phone--B--/i',"$phone",$email_subject);
						$email_subject = preg_replace('/--A--parked_by--B--/i',"$parked_by",$email_subject);
						$email_subject = preg_replace('/--A--camp_script--B--/i',"$camp_script",$email_subject);
						$email_subject = preg_replace('/--A--in_script--B--/i',"$in_script",$email_subject);
						$email_subject = preg_replace('/--A--dispo--B--/i',"$dispo",$email_subject);
						$email_subject = preg_replace('/--A--dispo_name--B--/i',"$dispo_name",$email_subject);
						$email_subject = preg_replace('/--A--talk_time--B--/i',"$talk_time",$email_subject);
						$email_subject = preg_replace('/--A--talk_time_ms--B--/i',"$talk_time_ms",$email_subject);
						$email_subject = preg_replace('/--A--talk_time_min--B--/i',"$talk_time_min",$email_subject);
						$email_subject = preg_replace('/--A--agent_log_id--B--/i',"$CALL_agent_log_id",$email_subject);
						$email_subject = preg_replace('/--A--closecallid--B--/i',urlencode(trim($INclosecallid)),$email_subject);
						$email_subject = preg_replace('/--A--xfercallid--B--/i',urlencode(trim($INxfercallid)),$email_subject);
						$email_subject = preg_replace('/--A--recording_id--B--/i',"$recording_id",$email_subject);
						$email_subject = preg_replace('/--A--recording_filename--B--/i',"$recording_filename",$email_subject);
						$email_subject = urldecode($email_subject);
						}

					### check for variables in email_body
					if (preg_match('/--A--/i',$email_body))
						{
						$email_body = preg_replace('/^VAR|--A--CF_uses_custom_fields--B--/','',$email_body);
						$email_body = preg_replace('/--A--lead_id--B--/i',"$lead_id",$email_body);
						$email_body = preg_replace('/--A--dispo--B--/i',"$dispo",$email_body);
						$email_body = preg_replace('/--A--dispo_name--B--/i',"$dispo_name",$email_body);
						$email_body = preg_replace('/--A--vendor_id--B--/i',"$vendor_id",$email_body);
						$email_body = preg_replace('/--A--vendor_lead_code--B--/i',"$vendor_lead_code",$email_body);
						$email_body = preg_replace('/--A--list_id--B--/i',"$list_id",$email_body);
						$email_body = preg_replace('/--A--list_name--B--/i',"$list_name",$email_body);
						$email_body = preg_replace('/--A--list_description--B--/i',"$list_description",$email_body);
						$email_body = preg_replace('/--A--gmt_offset_now--B--/i',"$gmt_offset_now",$email_body);
						$email_body = preg_replace('/--A--phone_code--B--/i',"$phone_code",$email_body);
						$email_body = preg_replace('/--A--phone_number--B--/i',"$phone_number",$email_body);
						$email_body = preg_replace('/--A--title--B--/i',"$title",$email_body);
						$email_body = preg_replace('/--A--first_name--B--/i',"$first_name",$email_body);
						$email_body = preg_replace('/--A--middle_initial--B--/i',"$middle_initial",$email_body);
						$email_body = preg_replace('/--A--last_name--B--/i',"$last_name",$email_body);
						$email_body = preg_replace('/--A--address1--B--/i',"$address1",$email_body);
						$email_body = preg_replace('/--A--address2--B--/i',"$address2",$email_body);
						$email_body = preg_replace('/--A--address3--B--/i',"$address3",$email_body);
						$email_body = preg_replace('/--A--city--B--/i',"$city",$email_body);
						$email_body = preg_replace('/--A--state--B--/i',"$state",$email_body);
						$email_body = preg_replace('/--A--province--B--/i',"$province",$email_body);
						$email_body = preg_replace('/--A--postal_code--B--/i',"$postal_code",$email_body);
						$email_body = preg_replace('/--A--country_code--B--/i',"$country_code",$email_body);
						$email_body = preg_replace('/--A--gender--B--/i',"$gender",$email_body);
						$email_body = preg_replace('/--A--date_of_birth--B--/i',"$date_of_birth",$email_body);
						$email_body = preg_replace('/--A--alt_phone--B--/i',"$alt_phone",$email_body);
						$email_body = preg_replace('/--A--email--B--/i',"$email",$email_body);
						$email_body = preg_replace('/--A--security_phrase--B--/i',"$security_phrase",$email_body);
						$email_body = preg_replace('/--A--comments--B--/i',"$comments",$email_body);
						$email_body = preg_replace('/--A--user--B--/i',"$user",$email_body);
						$email_body = preg_replace('/--A--pass--B--/i',"$orig_pass",$email_body);
						$email_body = preg_replace('/--A--original_phone_login--B--/i',"$original_phone_login",$email_body);
						$email_body = preg_replace('/--A--phone_pass--B--/i',"$phone_pass",$email_body);
						$email_body = preg_replace('/--A--fronter--B--/i',"$fronter",$email_body);
						$email_body = preg_replace('/--A--closer--B--/i',"$user",$email_body);
						$email_body = preg_replace('/--A--SQLdate--B--/i',"$NOW_TIME",$email_body);
						$email_body = preg_replace('/--A--epoch--B--/i',"$epoch",$email_body);
						$email_body = preg_replace('/--A--source_id--B--/i',"$source_id",$email_body);
						$email_body = preg_replace('/--A--rank--B--/i',"$rank",$email_body);
						$email_body = preg_replace('/--A--owner--B--/i',"$owner",$email_body);
						$email_body = preg_replace('/--A--entry_list_id--B--/i',"$entry_list_id",$email_body);
						$email_body = preg_replace('/--A--call_id--B--/i',urlencode(trim($call_id)),$email_body);
						$email_body = preg_replace('/--A--entry_date--B--/i',"$entry_date",$email_body);
						$email_body = preg_replace('/--A--fullname--B--/i',"$fullname",$email_body);
						$email_body = preg_replace('/--A--RUSfullname--B--/i',"$RUSfullname",$email_body);
						$email_body = preg_replace('/--A--user_custom_one--B--/i',"$user_custom_one",$email_body);
						$email_body = preg_replace('/--A--user_custom_two--B--/i',"$user_custom_two",$email_body);
						$email_body = preg_replace('/--A--user_custom_three--B--/i',"$user_custom_three",$email_body);
						$email_body = preg_replace('/--A--user_custom_four--B--/i',"$user_custom_four",$email_body);
						$email_body = preg_replace('/--A--user_custom_five--B--/i',"$user_custom_five",$email_body);
						$email_body = preg_replace('/--A--camp_custom_one--B--/i',"$camp_custom_one",$email_body);
						$email_body = preg_replace('/--A--camp_custom_two--B--/i',"$camp_custom_two",$email_body);
						$email_body = preg_replace('/--A--camp_custom_three--B--/i',"$camp_custom_three",$email_body);
						$email_body = preg_replace('/--A--camp_custom_four--B--/i',"$camp_custom_four",$email_body);
						$email_body = preg_replace('/--A--camp_custom_five--B--/i',"$camp_custom_five",$email_body);
						$email_body = preg_replace('/--A--user_group--B--/i',urlencode(trim($user_group)),$email_body);
						$email_body = preg_replace('/--A--agent_email--B--/i',"$agent_email",$email_body);
						$email_body = preg_replace('/--A--did_id--B--/i',"$DID_id",$email_body);
						$email_body = preg_replace('/--A--did_extension--B--/i',"$DID_extension",$email_body);
						$email_body = preg_replace('/--A--did_pattern--B--/i',"$DID_pattern",$email_body);
						$email_body = preg_replace('/--A--did_description--B--/i',"$DID_description",$email_body);
						$email_body = preg_replace('/--A--did_carrier_description--B--/i',"$DID_carrier_description",$email_body);
						$email_body = preg_replace('/--A--did_custom_one--B--/i',"$DID_custom_one",$email_body);
						$email_body = preg_replace('/--A--did_custom_two--B--/i',"$DID_custom_two",$email_body);
						$email_body = preg_replace('/--A--did_custom_three--B--/i',"$DID_custom_three",$email_body);
						$email_body = preg_replace('/--A--did_custom_four--B--/i',"$DID_custom_four",$email_body);
						$email_body = preg_replace('/--A--did_custom_five--B--/i',"$DID_custom_five",$email_body);
						$email_body = preg_replace('/--A--ig_custom_one--B--/i',"$ig_custom_one",$email_body);
						$email_body = preg_replace('/--A--ig_custom_two--B--/i',"$ig_custom_two",$email_body);
						$email_body = preg_replace('/--A--ig_custom_three--B--/i',"$ig_custom_three",$email_body);
						$email_body = preg_replace('/--A--ig_custom_four--B--/i',"$ig_custom_four",$email_body);
						$email_body = preg_replace('/--A--ig_custom_five--B--/i',"$ig_custom_five",$email_body);
						$email_body = preg_replace('/--A--uniqueid--B--/i',"$uniqueid",$email_body);
						$email_body = preg_replace('/--A--call_notes--B--/i',"$call_notes",$email_body);
						$email_body = preg_replace('/--A--additional_notes--B--/i',"$additional_notes",$email_body);
						$email_body = preg_replace('/--A--group--B--/i',"$channel_group",$email_body);
						$email_body = preg_replace('/--A--channel_group--B--/i',"$channel_group",$email_body);
						$email_body = preg_replace('/--A--dialed_number--B--/i',"$dialed_number",$email_body);
						$email_body = preg_replace('/--A--dialed_label--B--/i',"$dialed_label",$email_body);
						$email_body = preg_replace('/--A--campaign--B--/i',"$campaign",$email_body);
						$email_body = preg_replace('/--A--group_id--B--/i',"$temp_group_id",$email_body);
						$email_body = urldecode($email_body);
						}

					### check for variables in email_to
					if (preg_match('/--A--/i',$email_to))
						{
						$email_to = preg_replace('/^VAR/','',$email_to);
						$email_to = preg_replace('/--A--email--B--/i',"$email",$email_to);
						$email_to = preg_replace('/--A--customer_email--B--/i',"$email",$email_to);
						$email_to = preg_replace('/--A--agent_email--B--/i',"$agent_email",$email_to);
						$email_to = preg_replace('/--A--camp_custom_one--B--/i',"$camp_custom_one",$email_to);
						$email_to = preg_replace('/--A--camp_custom_two--B--/i',"$camp_custom_two",$email_to);
						$email_to = preg_replace('/--A--camp_custom_three--B--/i',"$camp_custom_three",$email_to);
						$email_to = preg_replace('/--A--camp_custom_four--B--/i',"$camp_custom_four",$email_to);
						$email_to = preg_replace('/--A--camp_custom_five--B--/i',"$camp_custom_five",$email_to);
						$email_to = preg_replace('/--A--ig_custom_one--B--/i',"$ig_custom_one",$email_to);
						$email_to = preg_replace('/--A--ig_custom_two--B--/i',"$ig_custom_two",$email_to);
						$email_to = preg_replace('/--A--ig_custom_three--B--/i',"$ig_custom_three",$email_to);
						$email_to = preg_replace('/--A--ig_custom_four--B--/i',"$ig_custom_four",$email_to);
						$email_to = preg_replace('/--A--ig_custom_five--B--/i',"$ig_custom_five",$email_to);
						$email_to = urldecode($email_to);
						}

					### check for variables in email_from
					if (preg_match('/--A--/i',$email_from))
						{
						$email_from = preg_replace('/^VAR/','',$email_from);
						$email_from = preg_replace('/--A--email--B--/i',"$email",$email_from);
						$email_from = preg_replace('/--A--customer_email--B--/i',"$email",$email_from);
						$email_from = preg_replace('/--A--agent_email--B--/i',"$agent_email",$email_from);
						$email_from = preg_replace('/--A--camp_custom_one--B--/i',"$camp_custom_one",$email_from);
						$email_from = preg_replace('/--A--camp_custom_two--B--/i',"$camp_custom_two",$email_from);
						$email_from = preg_replace('/--A--camp_custom_three--B--/i',"$camp_custom_three",$email_from);
						$email_from = preg_replace('/--A--camp_custom_four--B--/i',"$camp_custom_four",$email_from);
						$email_from = preg_replace('/--A--camp_custom_five--B--/i',"$camp_custom_five",$email_from);
						$email_from = preg_replace('/--A--ig_custom_one--B--/i',"$ig_custom_one",$email_from);
						$email_from = preg_replace('/--A--ig_custom_two--B--/i',"$ig_custom_two",$email_from);
						$email_from = preg_replace('/--A--ig_custom_three--B--/i',"$ig_custom_three",$email_from);
						$email_from = preg_replace('/--A--ig_custom_four--B--/i',"$ig_custom_four",$email_from);
						$email_from = preg_replace('/--A--ig_custom_five--B--/i',"$ig_custom_five",$email_from);
						$email_from = urldecode($email_from);
						}

					### check for variables in email_display_name
					if (preg_match('/--A--/i',$email_display_name))
						{
						$email_display_name = preg_replace('/^VAR/','',$email_display_name);
						$email_display_name = preg_replace('/--A--first_name--B--/i',"$first_name",$email_display_name);
						$email_display_name = preg_replace('/--A--last_name--B--/i',"$last_name",$email_display_name);
						$email_display_name = preg_replace('/--A--address1--B--/i',"$address1",$email_display_name);
						$email_display_name = preg_replace('/--A--address2--B--/i',"$address2",$email_display_name);
						$email_display_name = preg_replace('/--A--address3--B--/i',"$address3",$email_display_name);
						$email_display_name = preg_replace('/--A--city--B--/i',"$city",$email_display_name);
						$email_display_name = preg_replace('/--A--state--B--/i',"$state",$email_display_name);
						$email_display_name = preg_replace('/--A--province--B--/i',"$province",$email_display_name);
						$email_display_name = preg_replace('/--A--security_phrase--B--/i',"$security_phrase",$email_display_name);
						$email_display_name = preg_replace('/--A--comments--B--/i',"$comments",$email_display_name);
						$email_display_name = preg_replace('/--A--source_id--B--/i',"$source_id",$email_display_name);
						$email_display_name = preg_replace('/--A--fullname--B--/i',"$fullname",$email_display_name);
						$email_display_name = preg_replace('/--A--RUSfullname--B--/i',"$RUSfullname",$email_display_name);
						$email_display_name = preg_replace('/--A--user_custom_one--B--/i',"$user_custom_one",$email_display_name);
						$email_display_name = preg_replace('/--A--user_custom_two--B--/i',"$user_custom_two",$email_display_name);
						$email_display_name = preg_replace('/--A--user_custom_three--B--/i',"$user_custom_three",$email_display_name);
						$email_display_name = preg_replace('/--A--user_custom_four--B--/i',"$user_custom_four",$email_display_name);
						$email_display_name = preg_replace('/--A--user_custom_five--B--/i',"$user_custom_five",$email_display_name);
						$email_display_name = preg_replace('/--A--camp_custom_one--B--/i',"$camp_custom_one",$email_display_name);
						$email_display_name = preg_replace('/--A--camp_custom_two--B--/i',"$camp_custom_two",$email_display_name);
						$email_display_name = preg_replace('/--A--camp_custom_three--B--/i',"$camp_custom_three",$email_display_name);
						$email_display_name = preg_replace('/--A--camp_custom_four--B--/i',"$camp_custom_four",$email_display_name);
						$email_display_name = preg_replace('/--A--camp_custom_five--B--/i',"$camp_custom_five",$email_display_name);
						$email_display_name = preg_replace('/--A--did_description--B--/i',"$did_description",$email_display_name);
						$email_display_name = preg_replace('/--A--did_carrier_description--B--/i',"$did_carrier_description",$email_display_name);
						$email_display_name = preg_replace('/--A--did_custom_one--B--/i',"$did_custom_one",$email_display_name);
						$email_display_name = preg_replace('/--A--did_custom_two--B--/i',"$did_custom_two",$email_display_name);
						$email_display_name = preg_replace('/--A--did_custom_three--B--/i',"$did_custom_three",$email_display_name);
						$email_display_name = preg_replace('/--A--did_custom_four--B--/i',"$did_custom_four",$email_display_name);
						$email_display_name = preg_replace('/--A--did_custom_five--B--/i',"$did_custom_five",$email_display_name);
						$email_display_name = preg_replace('/--A--ig_custom_one--B--/i',"$ig_custom_one",$email_display_name);
						$email_display_name = preg_replace('/--A--ig_custom_two--B--/i',"$ig_custom_two",$email_display_name);
						$email_display_name = preg_replace('/--A--ig_custom_three--B--/i',"$ig_custom_three",$email_display_name);
						$email_display_name = preg_replace('/--A--ig_custom_four--B--/i',"$ig_custom_four",$email_display_name);
						$email_display_name = preg_replace('/--A--ig_custom_five--B--/i',"$ig_custom_five",$email_display_name);
						$email_display_name = urldecode($email_display_name);
						}

					// Generate an email boundary
					$boundary = md5(uniqid(time()));
					$attachment_1='';
					$attachment_2='';
					$attachment_3='';
					$attachment_4='';
					$attachment_5='';
					$attachment_6='';
					$attachment_7='';
					$attachment_8='';
					$attachment_9='';
					$attachment_10='';
					$attachment_11='';
					$attachment_12='';
					$attachment_13='';
					$attachment_14='';
					$attachment_15='';
					$attachment_16='';
					$attachment_17='';
					$attachment_18='';
					$attachment_19='';
					$attachment_20='';
					$attach_messages='';

					### check for valid attachments
					$valid_attachments=0;
					if ( (strlen($email_attachment_1) > 4) or (strlen($email_attachment_2) > 4) or (strlen($email_attachment_3) > 4) or (strlen($email_attachment_4) > 4) or (strlen($email_attachment_5) > 4) or (strlen($email_attachment_6) > 4) or (strlen($email_attachment_7) > 4) or (strlen($email_attachment_8) > 4) or (strlen($email_attachment_9) > 4) or (strlen($email_attachment_10) > 4) or (strlen($email_attachment_11) > 4) or (strlen($email_attachment_12) > 4) or (strlen($email_attachment_13) > 4) or (strlen($email_attachment_14) > 4) or (strlen($email_attachment_15) > 4) or (strlen($email_attachment_16) > 4) or (strlen($email_attachment_17) > 4) or (strlen($email_attachment_18) > 4) or (strlen($email_attachment_19) > 4) or (strlen($email_attachment_20) > 4) )
						{
						if (strlen($email_attachment_1) > 4)
							{
							$PATHemail_attachment_1 = "$email_attachment_path/$email_attachment_1";
							$PATHLEGemail_attachment_1 = "$email_attachment_path_legacy/$email_attachment_1";
							if ( (file_exists($PATHemail_attachment_1)) or (file_exists($PATHLEGemail_attachment_1)) )
								{
								if (!file_exists($PATHemail_attachment_1)) {$PATHemail_attachment_1 = $PATHLEGemail_attachment_1;}
								$filename_1 = basename($email_attachment_1);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_1);
								$handle = fopen($PATHemail_attachment_1, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_1 .= "Content-Type: application/xml; name=\"".$filename_1."\"".PHP_EOL;
								$attachment_1 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_1 .= "Content-Disposition: attachment; filename=\"".$filename_1."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_1 .= $content.PHP_EOL;
								$attachment_1 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_1: $filename_1($PATHemail_attachment_1) $file_size\n";}
								$attach_messages .= "attachment_1: $filename_1($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_1 does not exist: |$email_attachment_1|\n";}
								$attach_messages .= "attachment_1 does not exist: |$email_attachment_1|";
								$email_attachment_1='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_1: |$email_attachment_1|\n";}
							$email_attachment_1='';
							}

						if (strlen($email_attachment_2) > 4)
							{
							$PATHemail_attachment_2 = "$email_attachment_path/$email_attachment_2";
							$PATHLEGemail_attachment_2 = "$email_attachment_path_legacy/$email_attachment_2";
							if ( (file_exists($PATHemail_attachment_2)) or (file_exists($PATHLEGemail_attachment_2)) )
								{
								if (!file_exists($PATHemail_attachment_2)) {$PATHemail_attachment_2 = $PATHLEGemail_attachment_2;}
								$filename_2 = basename($email_attachment_2);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_2);
								$handle = fopen($PATHemail_attachment_2, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_2 .= "Content-Type: application/xml; name=\"".$filename_2."\"".PHP_EOL;
								$attachment_2 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_2 .= "Content-Disposition: attachment; filename=\"".$filename_2."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_2 .= $content.PHP_EOL;
								$attachment_2 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_2: $filename_2($PATHemail_attachment_2) $file_size\n";}
								$attach_messages .= "attachment_2: $filename_2($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_2 does not exist: |$email_attachment_2|\n";}
								$attach_messages .= "attachment_2 does not exist: |$email_attachment_2|";
								$email_attachment_2='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_2: |$email_attachment_2|\n";}
							$email_attachment_2='';
							}

						if (strlen($email_attachment_3) > 4)
							{
							$PATHemail_attachment_3 = "$email_attachment_path/$email_attachment_3";
							$PATHLEGemail_attachment_3 = "$email_attachment_path_legacy/$email_attachment_3";
							if ( (file_exists($PATHemail_attachment_3)) or (file_exists($PATHLEGemail_attachment_3)) )
								{
								if (!file_exists($PATHemail_attachment_3)) {$PATHemail_attachment_3 = $PATHLEGemail_attachment_3;}
								$filename_3 = basename($email_attachment_3);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_3);
								$handle = fopen($PATHemail_attachment_3, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_3 .= "Content-Type: application/xml; name=\"".$filename_3."\"".PHP_EOL;
								$attachment_3 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_3 .= "Content-Disposition: attachment; filename=\"".$filename_3."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_3 .= $content.PHP_EOL;
								$attachment_3 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_3: $filename_3($PATHemail_attachment_3) $file_size\n";}
								$attach_messages .= "attachment_3: $filename_3($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_3 does not exist: |$email_attachment_3|\n";}
								$attach_messages .= "attachment_3 does not exist: |$email_attachment_3|";
								$email_attachment_3='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_3: |$email_attachment_3|\n";}
							$email_attachment_3='';
							}

						if (strlen($email_attachment_4) > 4)
							{
							$PATHemail_attachment_4 = "$email_attachment_path/$email_attachment_4";
							$PATHLEGemail_attachment_4 = "$email_attachment_path_legacy/$email_attachment_4";
							if ( (file_exists($PATHemail_attachment_4)) or (file_exists($PATHLEGemail_attachment_4)) )
								{
								if (!file_exists($PATHemail_attachment_4)) {$PATHemail_attachment_4 = $PATHLEGemail_attachment_4;}
								$filename_4 = basename($email_attachment_4);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_4);
								$handle = fopen($PATHemail_attachment_4, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_4 .= "Content-Type: application/xml; name=\"".$filename_4."\"".PHP_EOL;
								$attachment_4 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_4 .= "Content-Disposition: attachment; filename=\"".$filename_4."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_4 .= $content.PHP_EOL;
								$attachment_4 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_4: $filename_4($PATHemail_attachment_4) $file_size\n";}
								$attach_messages .= "attachment_4: $filename_4($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_4 does not exist: |$email_attachment_4|\n";}
								$attach_messages .= "attachment_4 does not exist: |$email_attachment_4|";
								$email_attachment_4='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_4: |$email_attachment_4|\n";}
							$email_attachment_4='';
							}

						if (strlen($email_attachment_5) > 4)
							{
							$PATHemail_attachment_5 = "$email_attachment_path/$email_attachment_5";
							$PATHLEGemail_attachment_5 = "$email_attachment_path_legacy/$email_attachment_5";
							if ( (file_exists($PATHemail_attachment_5)) or (file_exists($PATHLEGemail_attachment_5)) )
								{
								if (!file_exists($PATHemail_attachment_5)) {$PATHemail_attachment_5 = $PATHLEGemail_attachment_5;}
								$filename_5 = basename($email_attachment_5);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_5);
								$handle = fopen($PATHemail_attachment_5, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_5 .= "Content-Type: application/xml; name=\"".$filename_5."\"".PHP_EOL;
								$attachment_5 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_5 .= "Content-Disposition: attachment; filename=\"".$filename_5."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_5 .= $content.PHP_EOL;
								$attachment_5 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_5: $filename_5($PATHemail_attachment_5) $file_size\n";}
								$attach_messages .= "attachment_5: $filename_5($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_5 does not exist: |$email_attachment_5|\n";}
								$attach_messages .= "attachment_5 does not exist: |$email_attachment_5|";
								$email_attachment_5='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_5: |$email_attachment_5|\n";}
							$email_attachment_5='';
							}

						if (strlen($email_attachment_6) > 4)
							{
							$PATHemail_attachment_6 = "$email_attachment_path/$email_attachment_6";
							$PATHLEGemail_attachment_6 = "$email_attachment_path_legacy/$email_attachment_6";
							if ( (file_exists($PATHemail_attachment_6)) or (file_exists($PATHLEGemail_attachment_6)) )
								{
								if (!file_exists($PATHemail_attachment_6)) {$PATHemail_attachment_6 = $PATHLEGemail_attachment_6;}
								$filename_6 = basename($email_attachment_6);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_6);
								$handle = fopen($PATHemail_attachment_6, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_6 .= "Content-Type: application/xml; name=\"".$filename_6."\"".PHP_EOL;
								$attachment_6 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_6 .= "Content-Disposition: attachment; filename=\"".$filename_6."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_6 .= $content.PHP_EOL;
								$attachment_6 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_6: $filename_6($PATHemail_attachment_6) $file_size\n";}
								$attach_messages .= "attachment_6: $filename_6($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_6 does not exist: |$email_attachment_6|\n";}
								$attach_messages .= "attachment_6 does not exist: |$email_attachment_6|";
								$email_attachment_6='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_6: |$email_attachment_6|\n";}
							$email_attachment_6='';
							}

						if (strlen($email_attachment_7) > 4)
							{
							$PATHemail_attachment_7 = "$email_attachment_path/$email_attachment_7";
							$PATHLEGemail_attachment_7 = "$email_attachment_path_legacy/$email_attachment_7";
							if ( (file_exists($PATHemail_attachment_7)) or (file_exists($PATHLEGemail_attachment_7)) )
								{
								if (!file_exists($PATHemail_attachment_7)) {$PATHemail_attachment_7 = $PATHLEGemail_attachment_7;}
								$filename_7 = basename($email_attachment_7);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_7);
								$handle = fopen($PATHemail_attachment_7, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_7 .= "Content-Type: application/xml; name=\"".$filename_7."\"".PHP_EOL;
								$attachment_7 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_7 .= "Content-Disposition: attachment; filename=\"".$filename_7."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_7 .= $content.PHP_EOL;
								$attachment_7 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_7: $filename_7($PATHemail_attachment_7) $file_size\n";}
								$attach_messages .= "attachment_7: $filename_7($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_7 does not exist: |$email_attachment_7|\n";}
								$attach_messages .= "attachment_7 does not exist: |$email_attachment_7|";
								$email_attachment_7='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_7: |$email_attachment_7|\n";}
							$email_attachment_7='';
							}

						if (strlen($email_attachment_8) > 4)
							{
							$PATHemail_attachment_8 = "$email_attachment_path/$email_attachment_8";
							$PATHLEGemail_attachment_8 = "$email_attachment_path_legacy/$email_attachment_8";
							if ( (file_exists($PATHemail_attachment_8)) or (file_exists($PATHLEGemail_attachment_8)) )
								{
								if (!file_exists($PATHemail_attachment_8)) {$PATHemail_attachment_8 = $PATHLEGemail_attachment_8;}
								$filename_8 = basename($email_attachment_8);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_8);
								$handle = fopen($PATHemail_attachment_8, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_8 .= "Content-Type: application/xml; name=\"".$filename_8."\"".PHP_EOL;
								$attachment_8 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_8 .= "Content-Disposition: attachment; filename=\"".$filename_8."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_8 .= $content.PHP_EOL;
								$attachment_8 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_8: $filename_8($PATHemail_attachment_8) $file_size\n";}
								$attach_messages .= "attachment_8: $filename_8($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_8 does not exist: |$email_attachment_8|\n";}
								$attach_messages .= "attachment_8 does not exist: |$email_attachment_8|";
								$email_attachment_8='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_8: |$email_attachment_8|\n";}
							$email_attachment_8='';
							}

						if (strlen($email_attachment_9) > 4)
							{
							$PATHemail_attachment_9 = "$email_attachment_path/$email_attachment_9";
							$PATHLEGemail_attachment_9 = "$email_attachment_path_legacy/$email_attachment_9";
							if ( (file_exists($PATHemail_attachment_9)) or (file_exists($PATHLEGemail_attachment_9)) )
								{
								if (!file_exists($PATHemail_attachment_9)) {$PATHemail_attachment_9 = $PATHLEGemail_attachment_9;}
								$filename_9 = basename($email_attachment_9);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_9);
								$handle = fopen($PATHemail_attachment_9, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_9 .= "Content-Type: application/xml; name=\"".$filename_9."\"".PHP_EOL;
								$attachment_9 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_9 .= "Content-Disposition: attachment; filename=\"".$filename_9."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_9 .= $content.PHP_EOL;
								$attachment_9 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_9: $filename_9($PATHemail_attachment_9) $file_size\n";}
								$attach_messages .= "attachment_9: $filename_9($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_9 does not exist: |$email_attachment_9|\n";}
								$attach_messages .= "attachment_9 does not exist: |$email_attachment_9|";
								$email_attachment_9='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_9: |$email_attachment_9|\n";}
							$email_attachment_9='';
							}

						if (strlen($email_attachment_10) > 4)
							{
							$PATHemail_attachment_10 = "$email_attachment_path/$email_attachment_10";
							$PATHLEGemail_attachment_10 = "$email_attachment_path_legacy/$email_attachment_10";
							if ( (file_exists($PATHemail_attachment_10)) or (file_exists($PATHLEGemail_attachment_10)) )
								{
								if (!file_exists($PATHemail_attachment_10)) {$PATHemail_attachment_10 = $PATHLEGemail_attachment_10;}
								$filename_10 = basename($email_attachment_10);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_10);
								$handle = fopen($PATHemail_attachment_10, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_10 .= "Content-Type: application/xml; name=\"".$filename_10."\"".PHP_EOL;
								$attachment_10 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_10 .= "Content-Disposition: attachment; filename=\"".$filename_10."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_10 .= $content.PHP_EOL;
								$attachment_10 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_10: $filename_10($PATHemail_attachment_10) $file_size\n";}
								$attach_messages .= "attachment_10: $filename_10($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_10 does not exist: |$email_attachment_10|\n";}
								$attach_messages .= "attachment_10 does not exist: |$email_attachment_10|";
								$email_attachment_10='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_10: |$email_attachment_10|\n";}
							$email_attachment_10='';
							}

						if (strlen($email_attachment_11) > 4)
							{
							$PATHemail_attachment_11 = "$email_attachment_path/$email_attachment_11";
							$PATHLEGemail_attachment_11 = "$email_attachment_path_legacy/$email_attachment_11";
							if ( (file_exists($PATHemail_attachment_11)) or (file_exists($PATHLEGemail_attachment_11)) )
								{
								if (!file_exists($PATHemail_attachment_11)) {$PATHemail_attachment_11 = $PATHLEGemail_attachment_11;}
								$filename_11 = basename($email_attachment_11);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_11);
								$handle = fopen($PATHemail_attachment_11, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_11 .= "Content-Type: application/xml; name=\"".$filename_11."\"".PHP_EOL;
								$attachment_11 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_11 .= "Content-Disposition: attachment; filename=\"".$filename_11."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_11 .= $content.PHP_EOL;
								$attachment_11 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_11: $filename_11($PATHemail_attachment_11) $file_size\n";}
								$attach_messages .= "attachment_11: $filename_11($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_11 does not exist: |$email_attachment_11|\n";}
								$attach_messages .= "attachment_11 does not exist: |$email_attachment_11|";
								$email_attachment_11='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_11: |$email_attachment_11|\n";}
							$email_attachment_11='';
							}

						if (strlen($email_attachment_12) > 4)
							{
							$PATHemail_attachment_12 = "$email_attachment_path/$email_attachment_12";
							$PATHLEGemail_attachment_12 = "$email_attachment_path_legacy/$email_attachment_12";
							if ( (file_exists($PATHemail_attachment_12)) or (file_exists($PATHLEGemail_attachment_12)) )
								{
								if (!file_exists($PATHemail_attachment_12)) {$PATHemail_attachment_12 = $PATHLEGemail_attachment_12;}
								$filename_12 = basename($email_attachment_12);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_12);
								$handle = fopen($PATHemail_attachment_12, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_12 .= "Content-Type: application/xml; name=\"".$filename_12."\"".PHP_EOL;
								$attachment_12 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_12 .= "Content-Disposition: attachment; filename=\"".$filename_12."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_12 .= $content.PHP_EOL;
								$attachment_12 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_12: $filename_12($PATHemail_attachment_12) $file_size\n";}
								$attach_messages .= "attachment_12: $filename_12($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_12 does not exist: |$email_attachment_12|\n";}
								$attach_messages .= "attachment_12 does not exist: |$email_attachment_12|";
								$email_attachment_12='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_12: |$email_attachment_12|\n";}
							$email_attachment_12='';
							}

						if (strlen($email_attachment_13) > 4)
							{
							$PATHemail_attachment_13 = "$email_attachment_path/$email_attachment_13";
							$PATHLEGemail_attachment_13 = "$email_attachment_path_legacy/$email_attachment_13";
							if ( (file_exists($PATHemail_attachment_13)) or (file_exists($PATHLEGemail_attachment_13)) )
								{
								if (!file_exists($PATHemail_attachment_13)) {$PATHemail_attachment_13 = $PATHLEGemail_attachment_13;}
								$filename_13 = basename($email_attachment_13);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_13);
								$handle = fopen($PATHemail_attachment_13, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_13 .= "Content-Type: application/xml; name=\"".$filename_13."\"".PHP_EOL;
								$attachment_13 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_13 .= "Content-Disposition: attachment; filename=\"".$filename_13."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_13 .= $content.PHP_EOL;
								$attachment_13 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_13: $filename_13($PATHemail_attachment_13) $file_size\n";}
								$attach_messages .= "attachment_13: $filename_13($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_13 does not exist: |$email_attachment_13|\n";}
								$attach_messages .= "attachment_13 does not exist: |$email_attachment_13|";
								$email_attachment_13='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_13: |$email_attachment_13|\n";}
							$email_attachment_13='';
							}

						if (strlen($email_attachment_14) > 4)
							{
							$PATHemail_attachment_14 = "$email_attachment_path/$email_attachment_14";
							$PATHLEGemail_attachment_14 = "$email_attachment_path_legacy/$email_attachment_14";
							if ( (file_exists($PATHemail_attachment_14)) or (file_exists($PATHLEGemail_attachment_14)) )
								{
								if (!file_exists($PATHemail_attachment_14)) {$PATHemail_attachment_14 = $PATHLEGemail_attachment_14;}
								$filename_14 = basename($email_attachment_14);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_14);
								$handle = fopen($PATHemail_attachment_14, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_14 .= "Content-Type: application/xml; name=\"".$filename_14."\"".PHP_EOL;
								$attachment_14 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_14 .= "Content-Disposition: attachment; filename=\"".$filename_14."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_14 .= $content.PHP_EOL;
								$attachment_14 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_14: $filename_14($PATHemail_attachment_14) $file_size\n";}
								$attach_messages .= "attachment_14: $filename_14($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_14 does not exist: |$email_attachment_14|\n";}
								$attach_messages .= "attachment_14 does not exist: |$email_attachment_14|";
								$email_attachment_14='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_14: |$email_attachment_14|\n";}
							$email_attachment_14='';
							}

						if (strlen($email_attachment_15) > 4)
							{
							$PATHemail_attachment_15 = "$email_attachment_path/$email_attachment_15";
							$PATHLEGemail_attachment_15 = "$email_attachment_path_legacy/$email_attachment_15";
							if ( (file_exists($PATHemail_attachment_15)) or (file_exists($PATHLEGemail_attachment_15)) )
								{
								if (!file_exists($PATHemail_attachment_15)) {$PATHemail_attachment_15 = $PATHLEGemail_attachment_15;}
								$filename_15 = basename($email_attachment_15);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_15);
								$handle = fopen($PATHemail_attachment_15, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_15 .= "Content-Type: application/xml; name=\"".$filename_15."\"".PHP_EOL;
								$attachment_15 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_15 .= "Content-Disposition: attachment; filename=\"".$filename_15."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_15 .= $content.PHP_EOL;
								$attachment_15 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_15: $filename_15($PATHemail_attachment_15) $file_size\n";}
								$attach_messages .= "attachment_15: $filename_15($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_15 does not exist: |$email_attachment_15|\n";}
								$attach_messages .= "attachment_15 does not exist: |$email_attachment_15|";
								$email_attachment_15='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_15: |$email_attachment_15|\n";}
							$email_attachment_15='';
							}

						if (strlen($email_attachment_16) > 4)
							{
							$PATHemail_attachment_16 = "$email_attachment_path/$email_attachment_16";
							$PATHLEGemail_attachment_16 = "$email_attachment_path_legacy/$email_attachment_16";
							if ( (file_exists($PATHemail_attachment_16)) or (file_exists($PATHLEGemail_attachment_16)) )
								{
								if (!file_exists($PATHemail_attachment_16)) {$PATHemail_attachment_16 = $PATHLEGemail_attachment_16;}
								$filename_16 = basename($email_attachment_16);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_16);
								$handle = fopen($PATHemail_attachment_16, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_16 .= "Content-Type: application/xml; name=\"".$filename_16."\"".PHP_EOL;
								$attachment_16 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_16 .= "Content-Disposition: attachment; filename=\"".$filename_16."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_16 .= $content.PHP_EOL;
								$attachment_16 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_16: $filename_16($PATHemail_attachment_16) $file_size\n";}
								$attach_messages .= "attachment_16: $filename_16($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_16 does not exist: |$email_attachment_16|\n";}
								$attach_messages .= "attachment_16 does not exist: |$email_attachment_16|";
								$email_attachment_16='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_16: |$email_attachment_16|\n";}
							$email_attachment_16='';
							}

						if (strlen($email_attachment_17) > 4)
							{
							$PATHemail_attachment_17 = "$email_attachment_path/$email_attachment_17";
							$PATHLEGemail_attachment_17 = "$email_attachment_path_legacy/$email_attachment_17";
							if ( (file_exists($PATHemail_attachment_17)) or (file_exists($PATHLEGemail_attachment_17)) )
								{
								if (!file_exists($PATHemail_attachment_17)) {$PATHemail_attachment_17 = $PATHLEGemail_attachment_17;}
								$filename_17 = basename($email_attachment_17);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_17);
								$handle = fopen($PATHemail_attachment_17, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_17 .= "Content-Type: application/xml; name=\"".$filename_17."\"".PHP_EOL;
								$attachment_17 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_17 .= "Content-Disposition: attachment; filename=\"".$filename_17."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_17 .= $content.PHP_EOL;
								$attachment_17 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_17: $filename_17($PATHemail_attachment_17) $file_size\n";}
								$attach_messages .= "attachment_17: $filename_17($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_17 does not exist: |$email_attachment_17|\n";}
								$attach_messages .= "attachment_17 does not exist: |$email_attachment_17|";
								$email_attachment_17='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_17: |$email_attachment_17|\n";}
							$email_attachment_17='';
							}

						if (strlen($email_attachment_18) > 4)
							{
							$PATHemail_attachment_18 = "$email_attachment_path/$email_attachment_18";
							$PATHLEGemail_attachment_18 = "$email_attachment_path_legacy/$email_attachment_18";
							if ( (file_exists($PATHemail_attachment_18)) or (file_exists($PATHLEGemail_attachment_18)) )
								{
								if (!file_exists($PATHemail_attachment_18)) {$PATHemail_attachment_18 = $PATHLEGemail_attachment_18;}
								$filename_18 = basename($email_attachment_18);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_18);
								$handle = fopen($PATHemail_attachment_18, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_18 .= "Content-Type: application/xml; name=\"".$filename_18."\"".PHP_EOL;
								$attachment_18 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_18 .= "Content-Disposition: attachment; filename=\"".$filename_18."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_18 .= $content.PHP_EOL;
								$attachment_18 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_18: $filename_18($PATHemail_attachment_18) $file_size\n";}
								$attach_messages .= "attachment_18: $filename_18($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_18 does not exist: |$email_attachment_18|\n";}
								$attach_messages .= "attachment_18 does not exist: |$email_attachment_18|";
								$email_attachment_18='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_18: |$email_attachment_18|\n";}
							$email_attachment_18='';
							}

						if (strlen($email_attachment_19) > 4)
							{
							$PATHemail_attachment_19 = "$email_attachment_path/$email_attachment_19";
							$PATHLEGemail_attachment_19 = "$email_attachment_path_legacy/$email_attachment_19";
							if ( (file_exists($PATHemail_attachment_19)) or (file_exists($PATHLEGemail_attachment_19)) )
								{
								if (!file_exists($PATHemail_attachment_19)) {$PATHemail_attachment_19 = $PATHLEGemail_attachment_19;}
								$filename_19 = basename($email_attachment_19);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_19);
								$handle = fopen($PATHemail_attachment_19, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_19 .= "Content-Type: application/xml; name=\"".$filename_19."\"".PHP_EOL;
								$attachment_19 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_19 .= "Content-Disposition: attachment; filename=\"".$filename_19."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_19 .= $content.PHP_EOL;
								$attachment_19 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_19: $filename_19($PATHemail_attachment_19) $file_size\n";}
								$attach_messages .= "attachment_19: $filename_19($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_19 does not exist: |$email_attachment_19|\n";}
								$attach_messages .= "attachment_19 does not exist: |$email_attachment_19|";
								$email_attachment_19='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_19: |$email_attachment_19|\n";}
							$email_attachment_19='';
							}

						if (strlen($email_attachment_20) > 4)
							{
							$PATHemail_attachment_20 = "$email_attachment_path/$email_attachment_20";
							$PATHLEGemail_attachment_20 = "$email_attachment_path_legacy/$email_attachment_20";
							if ( (file_exists($PATHemail_attachment_20)) or (file_exists($PATHLEGemail_attachment_20)) )
								{
								if (!file_exists($PATHemail_attachment_20)) {$PATHemail_attachment_20 = $PATHLEGemail_attachment_20;}
								$filename_20 = basename($email_attachment_20);
								// Read the file content
								$file_size = filesize($PATHemail_attachment_20);
								$handle = fopen($PATHemail_attachment_20, "r");
								$content = fread($handle, $file_size);
								fclose($handle);
								$content = chunk_split(base64_encode($content));

								// Edit content type for different file extensions
								$attachment_20 .= "Content-Type: application/xml; name=\"".$filename_20."\"".PHP_EOL;
								$attachment_20 .= "Content-Transfer-Encoding: base64".PHP_EOL;
								$attachment_20 .= "Content-Disposition: attachment; filename=\"".$filename_20."\"$EHA".PHP_EOL.PHP_EOL;
								$attachment_20 .= $content.PHP_EOL;
								$attachment_20 .= "--".$boundary;

								if ($DB > 1) {echo "valid attachment_20: $filename_20($PATHemail_attachment_20) $file_size\n";}
								$attach_messages .= "attachment_20: $filename_20($file_size)|";
								$valid_attachments++;
								}
							else
								{
								if ($DB > 1) {echo "attachment_20 does not exist: |$email_attachment_20|\n";}
								$attach_messages .= "attachment_20 does not exist: |$email_attachment_20|";
								$email_attachment_20='';
								}
							}
						else
							{
							if ($DB > 1) {echo "invalid attachment_20: |$email_attachment_20|\n";}
							$email_attachment_20='';
							}

						}
					if ($valid_attachments > 0)
						{
						// Email header
						# $header = "From: ".$email_from.PHP_EOL;
						if(isset($email_display_name)){$from_string = '"'.$email_display_name.'" <'.$email_from.'>';}else{$from_string = $email_from;}
						$header = "From: ".$from_string.PHP_EOL;
						$header .= "Reply-To: ".$email_from.PHP_EOL;
						$header .= "MIME-Version: 1.0".PHP_EOL;

						// Multipart wraps the Email Content and Attachment
						$header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"".PHP_EOL;
						$header .= "This is a multi-part message in MIME format.".PHP_EOL;
						$header .= "--".$boundary.PHP_EOL;

						// Attachment
						if (strlen($attachment_1) > 10)
							{$header .= $attachment_1.PHP_EOL;}
						if (strlen($attachment_2) > 10)
							{$header .= $attachment_2.PHP_EOL;}
						if (strlen($attachment_3) > 10)
							{$header .= $attachment_3.PHP_EOL;}
						if (strlen($attachment_4) > 10)
							{$header .= $attachment_4.PHP_EOL;}
						if (strlen($attachment_5) > 10)
							{$header .= $attachment_5.PHP_EOL;}
						if (strlen($attachment_6) > 10)
							{$header .= $attachment_6.PHP_EOL;}
						if (strlen($attachment_7) > 10)
							{$header .= $attachment_7.PHP_EOL;}
						if (strlen($attachment_8) > 10)
							{$header .= $attachment_8.PHP_EOL;}
						if (strlen($attachment_9) > 10)
							{$header .= $attachment_9.PHP_EOL;}
						if (strlen($attachment_10) > 10)
							{$header .= $attachment_10.PHP_EOL;}
						if (strlen($attachment_11) > 10)
							{$header .= $attachment_11.PHP_EOL;}
						if (strlen($attachment_12) > 10)
							{$header .= $attachment_12.PHP_EOL;}
						if (strlen($attachment_13) > 10)
							{$header .= $attachment_13.PHP_EOL;}
						if (strlen($attachment_14) > 10)
							{$header .= $attachment_14.PHP_EOL;}
						if (strlen($attachment_15) > 10)
							{$header .= $attachment_15.PHP_EOL;}
						if (strlen($attachment_16) > 10)
							{$header .= $attachment_16.PHP_EOL;}
						if (strlen($attachment_17) > 10)
							{$header .= $attachment_17.PHP_EOL;}
						if (strlen($attachment_18) > 10)
							{$header .= $attachment_18.PHP_EOL;}
						if (strlen($attachment_19) > 10)
							{$header .= $attachment_19.PHP_EOL;}
						if (strlen($attachment_20) > 10)
							{$header .= $attachment_20.PHP_EOL;}

						// Email content
						// Content-type can be text/plain or text/html, with encoding as 'iso-8859-1' or 'utf-8' charset
						if (preg_match("/HTML/",$email_format))
							{$header .= "Content-type:text/html; charset=$email_charset".PHP_EOL;}
						else
							{$header .= "Content-type:text/plain; charset=$email_charset".PHP_EOL;}
						$header .= "Content-Transfer-Encoding: 7bit".PHP_EOL.PHP_EOL;

						$header = preg_replace("/\n\n|\r\r|\r\n\r\n/","\n",$header);

						// Send email
						if ( ($sendmail_bypass > 0) and (strlen($allow_sendmail_bypass) > 2) )
							{
							#	passthru("$allow_sendmail_bypass -t -i <<END_MESSAGE_XYZ1838127361\nTo: $email_to\nSubject: $email_subject\n$header\n\n$email_body \r\n\r\nEND_MESSAGE_XYZ1838127361\n > /tmp/mail-debug");

							$command = "To: $email_to\nSubject: $email_subject\n$header\n\n$email_body \r\n\r\n";

							$filetimestamp = date("YmdHis");
							$random = (rand(1000000, 9999999) + 10000000);
							$temp_mail_file = "MAIL_" . $lead_id . '_' . $filetimestamp . '_' . $random . '.txt';
							$fp = fopen ("/tmp/$temp_mail_file", "w");
							fwrite ($fp, "$command");
							fclose($fp);

							$result = passthru("/usr/bin/cat /tmp/$temp_mail_file | $allow_sendmail_bypass -t -i");

							echo "Sent |$result|";
						#	echo "Sent |$result| /usr/bin/cat /tmp/$temp_mail_file | $allow_sendmail_bypass -t -i";
							}
						else
							{
							if (mail($email_to, $email_subject, $email_body, $header)) 
								{echo "Sent";} 
							else 
								{
								echo "Error";
								if ($DB) 
									{
									echo "\n";
								#	echo "email_to: $email_to \n";
								#	echo "email_subject: $email_subject \n";
								#	echo "headers:\n";
								#	echo "$header\n";
								#	echo "\n";
									}
								}
							}
						}
					else
						{
						// Email header
						# $header = "From: ".$email_from.PHP_EOL;
						if(isset($email_display_name)){$from_string = '"'.$email_display_name.'" <'.$email_from.'>';}else{$from_string = $email_from;}
						$header = "From: ".$from_string.PHP_EOL;
						$header .= "Reply-To: ".$email_from.PHP_EOL;
						$header .= "MIME-Version: 1.0".PHP_EOL;

						// Email content
						// Content-type can be text/plain or text/html, with encoding as 'iso-8859-1' or 'utf-8' charset
						if (preg_match("/HTML/",$email_format))
							{$header .= "Content-type:text/html; charset=$email_charset".PHP_EOL;}
						else
							{$header .= "Content-type:text/plain; charset=$email_charset".PHP_EOL;}
						$header .= "Content-Transfer-Encoding: 7bit".PHP_EOL.PHP_EOL;

						##### sending standard email with no attachments through PHP #####
						# mail("$email_to","$email_subject","$email_body", $header);
						mail("$email_to","$email_subject","$email_body", $header, "-f $email_from");
						}

					$SQL_log = "$stmt|$stmtB|$CBaffected_rows|$email_from|$email_to|$email_subject|";
					$SQL_log = preg_replace('/;/','',$SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='dispo_send_email',value='$call_id',result='$affected_rows',result_reason='$container_id   $attach_messages',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
					$rslt=mysql_to_mysqli($stmt, $link);

					$MESSAGE = _QXZ("DONE: %1s match found, %2s email sent using %3s with %4s status",0,'',$SC_count,$affected_rows,$container_id,$dispo);
					echo "$MESSAGE\n";
					}
				else
					{
					$MESSAGE = _QXZ("DONE: problem with settings %1s, not all required fields filled in",0,'',$container_id);
					echo "$MESSAGE\n";
					}
				}
			else
				{
				$MESSAGE = _QXZ("DONE: no settings container found %1s",0,'',$container_id);
				echo "$MESSAGE\n";
				}
			}
		else
			{
			$MESSAGE = _QXZ("DONE: no settings container found %1s",0,'',$container_id);
			echo "$MESSAGE\n";
			}
		}
	else
		{
		$MESSAGE = _QXZ("DONE: %1s or %2s are invalid",0,'',$lead_id,$container_id);
		echo "$MESSAGE\n";
		}
	}
else
	{
	$MESSAGE = _QXZ("DONE: dispo is not a sale status: %1s  Count: ",0,'',$dispo) . "$called_count|$called_count_trigger";
	echo "$MESSAGE\n";
	}

if ($log_to_file > 0)
	{
	$fp = fopen ("./send_email.txt", "w");
#	fwrite ($fp, "$NOW_TIME|$k|$lead_id|$call_id|$container_id|$sale_status|$dispo|$user|XXXX|$DB|$log_to_file|$MESSAGE|\n");
	fwrite ($fp, "$NOW_TIME|\n");
	fclose($fp);
	}
