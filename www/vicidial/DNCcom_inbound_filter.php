<?php
# DNCcom_inbound_filter.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script searches DNC.COM's scrub service for incoming DID phone numbers
# and returns a blocking match of '1' that will send the call to the Filter route
# DID Filter URL feature in the URL search type with the following URL:
#  VARhttp://server/vicidial/DNCcom_inbound_filter.php?phone=--A--phone_number--B--
#
# This script REQUIRES a Settings Container in the system with ID of: DNCDOTCOM
# For inbound filtering, use the below variable in the settings container:
# USADNC will block federal DNC numbers
# PROJDNC will block internal project DNC numbers
# LITIGATOR will block the litigator list, if subscribed
# INVALID will block invalid numbers
#INBOUND_FILTER => USADNC,PROJDNC,LITIGATOR,INVALID
#
# to reduce load for redundant requests, you can enable
# searching of the recent query cache to DNC.com. Set this 
# to the number of days' results you want to search through.
# Default is 0 for disabled.
#INBOUND_CACHE => 0
#
# This code is tested against DNC.COM scrub utility version 2.12
#
# CHANGES
# 170923-1905 - First Build
# 170926-1016 - Added in_filter_override option and cache searching option
# 170927-2127 - Changed to log flags that are not used to filter, added in_cache_override option, added connect retry if rejected
#

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["phone"]))				{$phone=$_GET["phone"];}
	elseif (isset($_POST["phone"]))		{$phone=$_POST["phone"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["in_filter_override"]))				{$in_filter_override=$_GET["in_filter_override"];}
	elseif (isset($_POST["in_filter_override"]))	{$in_filter_override=$_POST["in_filter_override"];}
if (isset($_GET["in_cache_override"]))			{$in_cache_override=$_GET["in_cache_override"];}
	elseif (isset($_POST["in_cache_override"]))	{$in_cache_override=$_POST["in_cache_override"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$webroot_writable =			$row[1];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$phone=preg_replace('/[^-_0-9a-zA-Z]/','',$phone);
	}
else
	{
	$phone = preg_replace("/'|\"|\\\\|;/","",$phone);
	}
$in_filter_override=preg_replace('/[^-_0-9a-zA-Z]/','',$in_filter_override);
$in_cache_override=preg_replace('/[^-_0-9a-zA-Z]/','',$in_cache_override);
$filter_count=0;
$ENTRYdate = date("mdHis");

$stmt = "SELECT count(*) FROM vicidial_settings_containers where container_id='DNCDOTCOM';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$sc_ct = mysqli_num_rows($rslt);
if ($sc_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$SC_count = $row[0];
	}

if ($SC_count > 0)
	{
	$stmt = "SELECT container_entry FROM vicidial_settings_containers where container_id='DNCDOTCOM';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$sc_ct = mysqli_num_rows($rslt);
	if ($sc_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$container_entry =	$row[0];
		$container_ARY = explode("\n",$container_entry);
		$p=0;
		$scrub_url='';
		$flag_invalid=0;
		$flag_dnc=0;
		$flag_projdnc=0;
		$flag_litigator=0;
		$valid_pull=0;
		$pull_counter=0;
		$container_ct = count($container_ARY);
		while ($p <= $container_ct)
			{
			$line = $container_ARY[$p];
			$line = preg_replace("/\n|\r|\t|\#.*|;.*/",'',$line);
			if (preg_match("/^DNC_DOT_COM_URL/",$line))
				{$scrub_url = $line;   $scrub_url = trim(preg_replace("/.*=>/",'',$scrub_url));}
			if (preg_match("/^LOGIN_ID/",$line))
				{$login_id = $line;   $login_id = trim(preg_replace("/.*=>/",'',$login_id));}
			if (preg_match("/^PROJ_ID/",$line))
				{$project_id = $line;   $project_id = trim(preg_replace("/.*=>/",'',$project_id));}
			if (preg_match("/^CAMPAIGN_ID/",$line))
				{$campaign_id = $line;   $campaign_id = trim(preg_replace("/.*=>/",'',$campaign_id));}
			if (preg_match("/^INBOUND_FILTER/",$line))
				{$in_filter = $line;   $in_filter = trim(preg_replace("/.*=>/",'',$in_filter));}
			if (preg_match("/^INBOUND_CACHE/",$line))
				{$in_cache = $line;   $in_cache = trim(preg_replace("/.*=>/",'',$in_cache));}

			$p++;
			}
		if (strlen($in_filter_override)>5) 
			{
			if ($DB) {echo "FILTER OVERRIDE: $in_filter_override|$in_filter\n";}
			$in_filter = $in_filter_override;
			}
		if (strlen($in_cache_override)>0) 
			{
			if ($DB) {echo "CACHE OVERRIDE: $in_cache_override|$in_cache\n";}
			$in_cache = $in_cache_override;
			}
		$scrub_url .= "?version=2&loginId=" . $login_id . "&phoneList=" . $phone;
		if (strlen($project_id)>0) {$scrub_url .= "&projId=" . $project_id;}
		if (strlen($campaign_id)>0) {$scrub_url .= "&campaignId=" . $campaign_id;}

		### BEGIN if inbound cache check is enabled, search the scrub logs for this phone number within last X days
		$cache_found=0;
		if ( (strlen($in_cache)>0) and ($in_cache > 0) )
			{
			$stmt = "SELECT full_response,scrub_date FROM vicidial_dnccom_scrub_log where phone_number='$phone' and scrub_date > CONCAT(DATE_ADD(CURDATE(), INTERVAL -$in_cache DAY),' ',CURTIME()) order by scrub_date desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$vdsl_ct = mysqli_num_rows($rslt);
			if ($vdsl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$SCUfile_contents =	$row[0];
				$cache_found++;
				if ($DB) {echo "CACHE FOUND: $row[1]|$vdsl_ct|$stmt\n";}
				}
			}
		### END if inbound cache check is enabled, search the scrub logs for this phone number within last X days

		if ($cache_found < 1)
			{
			while ( ($valid_pull < 1) and ($pull_counter < 5) )
				{
				### insert a new url log entry
				$uniqueid = $ENTRYdate . '.' . $phone;
				$SQL_log = "$scrub_url";
				$SQL_log = preg_replace('/;|\n/','',$SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt = "INSERT INTO vicidial_url_log SET uniqueid='$uniqueid',url_date=NOW(),url_type='DNCcom',url='$SQL_log',url_response='';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);
				$url_id = mysqli_insert_id($link);

				$URLstart_sec = date("U");

				if ($DB > 0) {echo "$scrub_url<BR>\n";}
				$SCUfile = file("$scrub_url");
				if ( !($SCUfile) )
					{
					$error_array = error_get_last();
					$error_type = $error_array["type"];
					$error_message = $error_array["message"];
					$error_line = $error_array["line"];
					$error_file = $error_array["file"];
					}

				if ($DB > 0) {echo "$SCUfile[0]<BR>\n";}

				### update url log entry
				$URLend_sec = date("U");
				$URLdiff_sec = ($URLend_sec - $URLstart_sec);
				if ($SCUfile)
					{
					$SCUfile_contents = implode("", $SCUfile);
					$SCUfile_contents = preg_replace("/;|\n/",'',$SCUfile_contents);
					$SCUfile_contents = addslashes($SCUfile_contents);
					$valid_pull++;
					}
				else
					{
					$SCUfile_contents = "PHP ERROR: Type=$error_type - Message=$error_message - Line=$error_line - File=$error_file";
					if (!preg_match("/\d/",$phone))
						{$valid_pull++;}
					}
				$stmt = "UPDATE vicidial_url_log SET response_sec='$URLdiff_sec',url_response='$SCUfile_contents' where url_log_id='$url_id';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);

				$pull_counter++;
				}
			}

		$result_details = explode(',',$SCUfile_contents);
		if ($result_details[1] == 'D')
			{
			if (preg_match("/National \(USA\)/",$SCUfile_contents))
				{
				$flag_dnc++;
				if (preg_match("/USADNC/",$in_filter))
					{$filter_count++;}
				}
			if (preg_match("/Litigator/",$SCUfile_contents))
				{
				$flag_litigator++;
				if (preg_match("/LITIGATOR/",$in_filter))
					{$filter_count++;}
				}
			}
		if ($result_details[1] == 'P')
			{
			$flag_projdnc++;
			if (preg_match("/PROJDNC/",$in_filter))
				{$filter_count++;}
			}
		if ( ($result_details[1] == 'I') or (preg_match("/\D/",$phone)) or (preg_match("/not a valid number/",$SCUfile_contents)) )
			{
			$flag_invalid++;
			if (preg_match("/INVALID/",$in_filter))
				{$filter_count++;}
			}

		if ($cache_found < 1)
			{
			$stmt = "INSERT INTO vicidial_dnccom_scrub_log SET phone_number='$phone',scrub_date=NOW(),flag_invalid='$flag_invalid',flag_dnc='$flag_dnc',flag_projdnc='$flag_projdnc',flag_litigator='$flag_litigator',full_response='$SCUfile_contents';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);
			if ($DB) {echo "$affected_rows|$stmt\n";}
			}
		if ($DB) {echo "DEBUG: pulls - $pull_counter\n";}
		}
	}

echo "$filter_count\n";

?>
