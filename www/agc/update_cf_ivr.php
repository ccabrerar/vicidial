<?php
# update_cf_ivr.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is part of the API group and any modifications of data are
# logged to the vicidial_api_log table.
#
# This script is used by park call IVR AGI scripts to alter custom field data
#
# CHANGES
# 150814-1441 - First Build
# 170526-2319 - Added additional variable filtering
#

$api_script = 'update_cf_ivr';

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
if (isset($_GET["caller_id"]))				{$caller_id=$_GET["caller_id"];}
	elseif (isset($_POST["caller_id"]))		{$caller_id=$_POST["caller_id"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["field"]))					{$field=$_GET["field"];}
	elseif (isset($_POST["field"]))			{$field=$_POST["field"];}
if (isset($_GET["value"]))					{$value=$_GET["value"];}
	elseif (isset($_POST["value"]))			{$value=$_POST["value"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
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
$caller_id = preg_replace('/[^-_0-9a-zA-Z]/', '', $caller_id);
$lead_id = preg_replace('/[^_0-9]/', '', $lead_id);
$list_id = preg_replace('/[^_0-9]/', '', $list_id);
$field = preg_replace('/[^-_0-9a-zA-Z]/', '', $field);
$value = preg_replace("/\'|\"|\\\\|;| /","",$value);

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

$stmt = "SELECT use_non_latin,enable_languages,language_method,active_modules FROM system_settings;";
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
	$active_modules =			$row[3];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	}

if ($DB>0) {echo "$lead_id|$caller_id|$list_id|$field|$value|$user|$DB|$log_to_file|\n";}


$PADlead_id = sprintf("%010s", $lead_id);
if ( (strlen($caller_id) > 15) and (preg_match("/$PADlead_id$/",$caller_id)) )
	{
	$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));

	$stmt="SELECT count(*) from vicidial_auto_calls where callerid='$caller_id' and last_update_time > \"$four_hours_ago\";";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$authlive=$row[0];
	if ($authlive < 1)
		{
		echo _QXZ("Call Not Found:")." 2|$user|$caller_id|$authlive|\n";
		exit;
		}
	}
else
	{
	echo _QXZ("Invalid Call ID:")." 1|$user|$caller_id|$PADlead_id|\n";
	exit;
	}

if ( (strlen($field) > 0) and (strlen($value) > 0) and ($list_id > 0) )
	{
	$enc_field='';
	$stmt = "SELECT field_encrypt from vicidial_lists_fields where field_label='$field' and list_id='$list_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$enc_field_ct = mysqli_num_rows($rslt);
	if ($enc_field_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$enc_field =	$row[0];
		if ( (preg_match("/cf_encrypt/",$active_modules)) and ($enc_field == 'Y') )
			{
			$valueENC = base64_encode($value);
			exec("./aes.pl --encrypt --text=$valueENC", $field_enc);
			$field_enc_ct = count($field_enc);
			$k=0;
			while ($field_enc_ct > $k)
				{
				$field_enc_all .= $field_enc[$k];
				$k++;
				}
			$value = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
			}

		$lead_cf_count=0;
		$stmt = "SELECT count(*) from custom_$list_id where lead_id=$lead_id;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$cfield_ct = mysqli_num_rows($rslt);
		if ($cfield_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$lead_cf_count =	$row[0];
			}

		if ($lead_cf_count > 0)
			{$stmt="UPDATE custom_$list_id set $field='$value' where lead_id=$lead_id;";   $updatetype='updated';}
		else
			{$stmt="INSERT INTO custom_$list_id set $field='$value', lead_id=$lead_id;";   $updatetype='inserted';}
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);

		$SQL_log = "$stmt|$stmtB|$CBaffected_rows|";
		$SQL_log = preg_replace('/;/','',$SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$user',function='update_cf',value='$field',result='$affected_rows',result_reason='$lead_id',source='vdc',data='$SQL_log',api_date='$NOW_TIME',api_script='$api_script';";
		$rslt=mysql_to_mysqli($stmt, $link);

		$MESSAGE = _QXZ("DONE: %1s found, %2s CF record %3s",0,'',$field,$affected_rows,$updatetype);
		echo "$MESSAGE\n";
		}
	else
		{
		$MESSAGE = _QXZ("DONE: no custom field found %1s",0,'',$field);
		echo "$MESSAGE\n";
		}
	}
else
	{
	$MESSAGE = _QXZ("DONE: %1s, %2s or %3s are invalid",0,'',$field,$value,$list_id);
	echo "$MESSAGE\n";
	}

if ($log_to_file > 0)
	{
	$fp = fopen ("./update_cf.txt", "a");
	fwrite ($fp, "$NOW_TIME|$k|$lead_id|$caller_id|$list_id|$field|$value|$user|XXXX|$DB|$log_to_file|$MESSAGE|\n");
	fclose($fp);
	}
