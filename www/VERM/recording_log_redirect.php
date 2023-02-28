<?php
# recording_log_redirect.php - audio recording access logging and redirect script
# 
# Copyright (C) 2022  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 160116-1349 - First Build
# 170409-1538 - Added IP List validation code
# 210401-1625 - Added no_redirect for logging when playing a Javascript audio object
# 220227-0858 - Added allow_web_debug system setting
#
 
require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$ip = getenv("REMOTE_ADDR");
if (isset($_GET["recording_id"]))			{$recording_id=$_GET["recording_id"];}
	elseif (isset($_POST["recording_id"]))	{$recording_id=$_POST["recording_id"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["no_redirect"]))				{$no_redirect=$_GET["no_redirect"];}
	elseif (isset($_POST["no_redirect"]))		{$no_redirect=$_POST["no_redirect"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,custom_fields_enabled,webroot_writable,allow_emails,enable_languages,language_method,active_modules,log_recording_access,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$custom_fields_enabled =	$row[1];
	$webroot_writable =			$row[2];
	$allow_emails =				$row[3];
	$SSenable_languages =		$row[4];
	$SSlanguage_method =		$row[5];
	$active_modules =			$row[6];
	$log_recording_access =		$row[7];
	$SSallow_web_debug =		$row[8];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$recording_id = preg_replace('/[^0-9]/','',$recording_id);
$lead_id = preg_replace('/[^0-9]/','',$lead_id);
$no_redirect = preg_replace('/[^-_0-9a-zA-Z]/','',$no_redirect);
$search_archived_data = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$search_archived_data);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	}

if ($search_archived_data) 
	{$recording_log_table=use_archive_table("recording_log");}
else
	{$recording_log_table="recording_log";}


$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth < 1)
	{
	$VDdisplayMESSAGE = _QXZ("Login incorrect, please try again");
	$log_stmt="insert into vicidial_recording_access_log(recording_id, lead_id, user, access_datetime, access_result, ip) VALUES('$recording_id', '$lead_id', '$PHP_AUTH_USER', now(), 'INVALID USER', '$ip');";
	$log_rslt=mysql_to_mysqli($log_stmt, $link);
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Too many login attempts, try again in 15 minutes");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ($auth_message == 'IPBLOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Your IP Address is not allowed") . ": $ip";
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}

if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rights_stmt = "SELECT access_recordings,selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$access_recordings =	$rights_row[0];
$VUselected_language =	$rights_row[1];

# check their permissions
if ( ( $access_recordings < 1 ) or ( $log_recording_access < 1 ) )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to access recordings")."\n";
	$log_stmt="insert into vicidial_recording_access_log(recording_id, lead_id, user, access_datetime, access_result, ip) VALUES('$recording_id', '$lead_id', '$PHP_AUTH_USER', now(), 'INVALID PERMISSIONS', '$ip')";
	$log_rslt=mysql_to_mysqli($log_stmt, $link);
	exit;
	}

if (!$recording_id) 
	{
	exit;
	} 
else 
	{
	$rec_lookup_stmt="select recording_id,channel,server_ip,extension,start_time,start_epoch,end_time,end_epoch,length_in_sec,length_in_min,filename,location,lead_id,user,vicidial_id from ".$recording_log_table." where recording_id='$recording_id'";
	$rec_lookup_rslt=mysql_to_mysqli($rec_lookup_stmt, $link);
	if (mysqli_num_rows($rec_lookup_rslt)==0) 
		{
		$log_stmt="insert into vicidial_recording_access_log(recording_id, user, access_datetime, access_result, ip, lead_id) VALUES ('$recording_id', '$PHP_AUTH_USER', now(), 'NO RECORDING', '$ip', '$lead_id');";
		$log_rslt=mysql_to_mysqli($log_stmt, $link);
		echo _QXZ("Not a valid recording")."\n";
		exit;
		}
	else 
		{
		$rec_row=mysqli_fetch_array($rec_lookup_rslt);
		$lead_id=$rec_row["lead_id"];
		$location=$rec_row["location"];

		if (strlen($location)>2)
			{
			$URLserver_ip = $location;
			$URLserver_ip = preg_replace('/http:\/\//i', '',$URLserver_ip);
			$URLserver_ip = preg_replace('/https:\/\//i', '',$URLserver_ip);
			$URLserver_ip = preg_replace('/\/.*/i', '',$URLserver_ip);
			$stmt="SELECT count(*) from servers where server_ip='$URLserver_ip';";
			$rsltx=mysql_to_mysqli($stmt, $link);
			$rowx=mysqli_fetch_row($rsltx);
		
			if ($rowx[0] > 0)
				{
				$stmt="SELECT recording_web_link,alt_server_ip,external_server_ip from servers where server_ip='$URLserver_ip';";
				$rsltx=mysql_to_mysqli($stmt, $link);
				$rowx=mysqli_fetch_row($rsltx);
			
				if (preg_match("/ALT_IP/i",$rowx[0]))
					{
					$location = preg_replace("/$URLserver_ip/i", "$rowx[1]", $location);
					}
				if (preg_match("/EXTERNAL_IP/i",$rowx[0]))
					{
					$location = preg_replace("/$URLserver_ip/i", "$rowx[2]", $location);
					}
				}
			}
		else
			{
			$log_stmt="insert into vicidial_recording_access_log(recording_id, lead_id, user, access_datetime, access_result, ip) VALUES ('$recording_id', '$lead_id', '$PHP_AUTH_USER', now(), 'RECORDING UNAVAILABLE', '$ip');";
			$log_rslt=mysql_to_mysqli($log_stmt, $link);
			echo _QXZ("Recording is not available yet.  Try again later.")."\n";
			exit;
			}
		}
	}

$log_stmt="insert into vicidial_recording_access_log(recording_id, lead_id, user, access_datetime, access_result, ip) VALUES('$recording_id', '$lead_id', '$PHP_AUTH_USER', now(), 'ACCESSED', '$ip')";
$log_rslt=mysql_to_mysqli($log_stmt, $link);

if (!$no_redirect)
	{
	header("Location: $location");
	}
else
	{
	echo "OK"; // DO NOT TRANSLATE THIS - IT'S A FLAG FOR JAVASCRIPT
	exit;
	}
?>
