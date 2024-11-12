<?php
# VERM_main_report_page.php - Vicidial Enhanced Reporting main report display page
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220828-1402 - First build
# 230106-1332 - Added auto download variables, permissions, report logging
# 230116-1640 - Ingroup-related clauses no longer allow blank campaign_id values (double-dipping issue)
# 240801-1130 - Code updates for PHP8 compatibility
#

# NANQUE, not an unanswered call?
# TIMEOT, unanswered even though it was dropped into a call that was picked up
# Warm transfers count as two calls

$report_name = 'VERM Reports';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

$startMS = microtime();
$STARTtime = date("U");

require("dbconnect_mysqli.php");
require("functions.php");
require("VERM_options.php");

#if (isset($_GET["SUBMIT"]))			{$SUBMIT=$_GET["SUBMIT"];}
#	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["download_rpt"]))			{$download_rpt=$_GET["download_rpt"];}
	elseif (isset($_POST["download_rpt"]))	{$download_rpt=$_POST["download_rpt"];}

#### CUSTOM REPORT VARIABLES ####
if (isset($_GET["log_custom_report"]))			{$log_custom_report=$_GET["log_custom_report"];}
	elseif (isset($_POST["log_custom_report"]))	{$log_custom_report=$_POST["log_custom_report"];}
if (isset($_GET["custom_report_name"]))			{$custom_report_name=$_GET["custom_report_name"];}
	elseif (isset($_POST["custom_report_name"]))	{$custom_report_name=$_POST["custom_report_name"];}
if (isset($_GET["vicidial_queue_groups"]))			{$vicidial_queue_groups=$_GET["vicidial_queue_groups"];}
	elseif (isset($_POST["vicidial_queue_groups"]))	{$vicidial_queue_groups=$_POST["vicidial_queue_groups"];}
if (isset($_GET["report_types"]))			{$report_types=$_GET["report_types"];}
	elseif (isset($_POST["report_types"]))	{$report_types=$_POST["report_types"];}
if (isset($_GET["report_types_to_display"]))			{$report_types_to_display=$_GET["report_types_to_display"];}
	elseif (isset($_POST["report_types_to_display"]))	{$report_types_to_display=$_POST["report_types_to_display"];}
if (isset($_GET["report_type"]))			{$report_type=$_GET["report_type"];}
	elseif (isset($_POST["report_type"]))	{$report_type=$_POST["report_type"];}
if (isset($_GET["time_period"]))			{$time_period=$_GET["time_period"];}
	elseif (isset($_POST["time_period"]))	{$time_period=$_POST["time_period"];}
if (isset($_GET["start_date"]))			{$start_date=$_GET["start_date"];}
	elseif (isset($_POST["start_date"]))	{$start_date=$_POST["start_date"];}
if (isset($_GET["start_time_hour"]))			{$start_time_hour=$_GET["start_time_hour"];}
	elseif (isset($_POST["start_time_hour"]))	{$start_time_hour=$_POST["start_time_hour"];}
if (isset($_GET["start_time_min"]))			{$start_time_min=$_GET["start_time_min"];}
	elseif (isset($_POST["start_time_min"]))	{$start_time_min=$_POST["start_time_min"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["end_time_hour"]))			{$end_time_hour=$_GET["end_time_hour"];}
	elseif (isset($_POST["end_time_hour"]))	{$end_time_hour=$_POST["end_time_hour"];}
if (isset($_GET["end_time_min"]))			{$end_time_min=$_GET["end_time_min"];}
	elseif (isset($_POST["end_time_min"]))	{$end_time_min=$_POST["end_time_min"];}
if (isset($_GET["hourly_slot"]))			{$hourly_slot=$_GET["hourly_slot"];}
	elseif (isset($_POST["hourly_slot"]))	{$hourly_slot=$_POST["hourly_slot"];}
if (isset($_GET["SLA_initial_period"]))			{$SLA_initial_period=$_GET["SLA_initial_period"];}
	elseif (isset($_POST["SLA_initial_period"]))	{$SLA_initial_period=$_POST["SLA_initial_period"];}
if (isset($_GET["SLA_initial_interval"]))			{$SLA_initial_interval=$_GET["SLA_initial_interval"];}
	elseif (isset($_POST["SLA_initial_interval"]))	{$SLA_initial_interval=$_POST["SLA_initial_interval"];}
if (isset($_GET["SLA_max_period"]))			{$SLA_max_period=$_GET["SLA_max_period"];}
	elseif (isset($_POST["SLA_max_period"]))	{$SLA_max_period=$_POST["SLA_max_period"];}
if (isset($_GET["SLA_interval"]))			{$SLA_interval=$_GET["SLA_interval"];}
	elseif (isset($_POST["SLA_interval"]))	{$SLA_interval=$_POST["SLA_interval"];}
if (isset($_GET["short_call_wait_limit"]))			{$short_call_wait_limit=$_GET["short_call_wait_limit"];}
	elseif (isset($_POST["short_call_wait_limit"]))	{$short_call_wait_limit=$_POST["short_call_wait_limit"];}
if (isset($_GET["short_call_talk_limit"]))			{$short_call_talk_limit=$_GET["short_call_talk_limit"];}
	elseif (isset($_POST["short_call_talk_limit"]))	{$short_call_talk_limit=$_POST["short_call_talk_limit"];}
if (isset($_GET["short_attempt_wait_limit"]))			{$short_attempt_wait_limit=$_GET["short_attempt_wait_limit"];}
	elseif (isset($_POST["short_attempt_wait_limit"]))	{$short_attempt_wait_limit=$_POST["short_attempt_wait_limit"];}
if (isset($_GET["users"]))			{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))	{$users=$_POST["users"];}
if (isset($_GET["teams"]))			{$teams=$_GET["teams"];}
	elseif (isset($_POST["teams"]))	{$teams=$_POST["teams"];}
if (isset($_GET["location"]))			{$location=$_GET["location"];}
	elseif (isset($_POST["location"]))	{$location=$_POST["location"];}
if (isset($_GET["user_group"]))			{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["statuses"]))			{$statuses=$_GET["statuses"];}
	elseif (isset($_POST["statuses"]))	{$statuses=$_POST["statuses"];}
if (isset($_GET["asterisk_cid"]))			{$asterisk_cid=$_GET["asterisk_cid"];}
	elseif (isset($_POST["asterisk_cid"]))	{$asterisk_cid=$_POST["asterisk_cid"];}
if (isset($_GET["phone_number"]))			{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["wait_sec_min"]))			{$wait_sec_min=$_GET["wait_sec_min"];}
	elseif (isset($_POST["wait_sec_min"]))	{$wait_sec_min=$_POST["wait_sec_min"];}
if (isset($_GET["wait_sec_max"]))			{$wait_sec_max=$_GET["wait_sec_max"];}
	elseif (isset($_POST["wait_sec_max"]))	{$wait_sec_max=$_POST["wait_sec_max"];}
if (isset($_GET["length_in_sec_min"]))			{$length_in_sec_min=$_GET["length_in_sec_min"];}
	elseif (isset($_POST["length_in_sec_min"]))	{$length_in_sec_min=$_POST["length_in_sec_min"];}
if (isset($_GET["length_in_sec_max"]))			{$length_in_sec_max=$_GET["length_in_sec_max"];}
	elseif (isset($_POST["length_in_sec_max"]))	{$length_in_sec_max=$_POST["length_in_sec_max"];}
if (isset($_GET["disconnection_cause"]))			{$disconnection_cause=$_GET["disconnection_cause"];}
	elseif (isset($_POST["disconnection_cause"]))	{$disconnection_cause=$_POST["disconnection_cause"];}
if (isset($_GET["queue_position_min"]))			{$queue_position_min=$_GET["queue_position_min"];}
	elseif (isset($_POST["queue_position_min"]))	{$queue_position_min=$_POST["queue_position_min"];}
if (isset($_GET["queue_position_max"]))			{$queue_position_max=$_GET["queue_position_max"];}
	elseif (isset($_POST["queue_position_max"]))	{$queue_position_max=$_POST["queue_position_max"];}
if (isset($_GET["call_count_min"]))			{$call_count_min=$_GET["call_count_min"];}
	elseif (isset($_POST["call_count_min"]))	{$call_count_min=$_POST["call_count_min"];}
if (isset($_GET["call_count_max"]))			{$call_count_max=$_GET["call_count_max"];}
	elseif (isset($_POST["call_count_max"]))	{$call_count_max=$_POST["call_count_max"];}
if (isset($_GET["did"]))			{$did=$_GET["did"];}
	elseif (isset($_POST["did"]))	{$did=$_POST["did"];}
if (isset($_GET["ivr_choice"]))			{$ivr_choice=$_GET["ivr_choice"];}
	elseif (isset($_POST["ivr_choice"]))	{$ivr_choice=$_POST["ivr_choice"];}
if (isset($_GET["server"]))			{$server=$_GET["server"];}
	elseif (isset($_POST["server"]))	{$server=$_POST["server"];}
if (isset($_GET["dow"]))			{$dow=$_GET["dow"];}
	elseif (isset($_POST["dow"]))	{$dow=$_POST["dow"];}
if (isset($_GET["DB"]))			{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["time_of_day_start"]))			{$time_of_day_start=$_GET["time_of_day_start"];}
	elseif (isset($_POST["time_of_day_start"]))	{$time_of_day_start=$_POST["time_of_day_start"];}
if (isset($_GET["time_of_day_end"]))			{$time_of_day_end=$_GET["time_of_day_end"];}
	elseif (isset($_POST["time_of_day_end"]))	{$time_of_day_end=$_POST["time_of_day_end"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,agent_whisper_enabled,report_default_format,enable_pause_code_limits,allow_web_debug,admin_screen_colors,admin_web_directory,log_recording_access FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$agent_whisper_enabled =		$row[6];
	$SSreport_default_format =		$row[7];
	$SSenable_pause_code_limits =	$row[8];
	$SSallow_web_debug =			$row[9];
	$SSadmin_screen_colors =		$row[10];
	$SSadmin_web_directory =		$row[11];
	$log_recording_access =			$row[12];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$start_date=preg_replace('/[^-0-9]/', '', $start_date);
$start_time_hour=preg_replace('/[^0-9]/', '', $start_time_hour);
$start_time_min=preg_replace('/[^0-9]/', '', $start_time_min);
$end_date=preg_replace('/[^-0-9]/', '', $end_date);
$end_time_hour=preg_replace('/[^0-9]/', '', $end_time_hour);
$end_time_min=preg_replace('/[^0-9]/', '', $end_time_min);
$hourly_slot=preg_replace('/[^0-9]/', '', $hourly_slot);
$SLA_initial_period=preg_replace('/[^0-9]/', '', $SLA_initial_period);
$SLA_initial_interval=preg_replace('/[^0-9]/', '', $SLA_initial_interval);
$SLA_max_period=preg_replace('/[^0-9]/', '', $SLA_max_period);
$SLA_interval=preg_replace('/[^0-9]/', '', $SLA_interval);
$short_call_wait_limit=preg_replace('/[^0-9]/', '', $short_call_wait_limit);
$short_call_talk_limit=preg_replace('/[^0-9]/', '', $short_call_talk_limit);
$short_attempt_wait_limit=preg_replace('/[^0-9]/', '', $short_attempt_wait_limit);
$log_custom_report = preg_replace('/[^0-9]/','',$log_custom_report);	
$phone_number = preg_replace('/[^0-9]/','',$phone_number);	
$wait_sec_min = preg_replace('/[^0-9]/','',$wait_sec_min);
$wait_sec_max = preg_replace('/[^0-9]/','',$wait_sec_max);
$length_in_sec_min = preg_replace('/[^0-9]/','',$length_in_sec_min);
$length_in_sec_max = preg_replace('/[^0-9]/','',$length_in_sec_max);
$queue_position_min = preg_replace('/[^0-9]/','',$queue_position_min);
$queue_position_max = preg_replace('/[^0-9]/','',$queue_position_max);
$call_count_min = preg_replace('/[^0-9]/','',$call_count_min);
$call_count_max = preg_replace('/[^0-9]/','',$call_count_max);
$did = preg_replace('/[^0-9]/','',$did);
$server = preg_replace('/[^0-9\.]/','',$server);
$dow = preg_replace('/[^0-9,]/','',$dow);
$time_of_day_start = preg_replace('/[^\:0-9]/','',$time_of_day_start);
$time_of_day_end = preg_replace('/[^\:0-9]/','',$time_of_day_end);

if ($non_latin < 1)
	{
	$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9a-zA-Z]/','',$group);
	$vicidial_queue_groups = preg_replace('/[^-_0-9a-zA-Z]/','',$vicidial_queue_groups);
	$user_group_filter = preg_replace('/[^-_0-9a-zA-Z]/','',$user_group_filter);
	$ingroup_filter = preg_replace('/[^-_0-9a-zA-Z]/','',$ingroup_filter);

	$report_types = preg_replace('/[^\._a-zA-Z]/','',$report_types);
	$report_types_to_display = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$report_types_to_display);
	$report_type = preg_replace('/[^\._a-zA-Z]/','',$report_type);
	$time_period = preg_replace('/[^0-9a-zA-Z]/','',$time_period);
	$users = preg_replace('/[^\-_0-9a-zA-Z]/','',$users);
	$teams = preg_replace('/[^\-_0-9a-zA-Z]/','',$teams);
	$location = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$location); 
	$user_group = preg_replace('/[^\-_0-9a-zA-Z]/','',$user_group);
	$statuses = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$statuses);
	$asterisk_cid = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$asterisk_cid);
	$disconnection_cause = preg_replace('/[^\-_0-9a-zA-Z]/','',$disconnection_cause);
	$ivr_choice = preg_replace('/[^\-\_\#\*0-9A-Z]/','',$ivr_choice);
	$custom_report_name = preg_replace('/[^\-_0-9a-zA-Z]/','',$custom_report_name);
	$download_rpt = preg_replace('/[^\._0-9a-zA-Z]/','',$download_rpt);
	}
else
	{
	$DB=preg_replace("/[^0-9\p{L}]/u","",$DB);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9\p{L}]/u','',$group);
	$vicidial_queue_groups = preg_replace('/[^-_0-9\p{L}]/u','',$vicidial_queue_groups);
	$user_group_filter = preg_replace('/[^-_0-9\p{L}]/u','',$user_group_filter);
	$ingroup_filter = preg_replace('/[^-_0-9\p{L}]/u','',$ingroup_filter);

	$report_types = preg_replace('/[^\._\p{L}]/u','',$report_types);
	$report_types_to_display = preg_replace('/[^\s\-_0-9\p{L}]/u','',$report_types_to_display);
	$report_type = preg_replace('/[^\._\p{L}]/u','',$report_type);
	$time_period = preg_replace('/[^0-9\p{L}]/u','',$time_period);
	$users = preg_replace('/[^-_0-9\p{L}]/u','',$users);
	$teams = preg_replace('/[^-_0-9\p{L}]/u','',$teams);
	$location = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$location); 
	$user_group = preg_replace('/[^-_0-9\p{L}]/u','',$user_group);
	$statuses = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$statuses);
	$asterisk_cid = preg_replace('/[^\s\-_0-9\p{L}]/u','',$asterisk_cid);
	$disconnection_cause = preg_replace('/[^-_0-9\p{L}]/u','',$disconnection_cause);
	$ivr_choice = preg_replace('/[^-\_\#\*0-9\p{L}]/u','',$ivr_choice);
	$custom_report_name = preg_replace('/[^-_0-9\p{L}]/u','',$custom_report_name);
	$download_rpt = preg_replace('/[^\._0-9\p{L}]/u','',$download_rpt);
	}


$stmt="SELECT selected_language,user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$LOGuser_group =			$row[1];
	}

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"/".$SSadmin_web_directory."/admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	if ($reports_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to view reports");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ( ($reports_auth > 0) and ($admin_auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	}
else
	{
	$VDdisplayMESSAGE = _QXZ("Login incorrect, please try again");
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

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times,allowed_queue_groups,reports_header_override,admin_home_url from vicidial_user_groups where user_group='$LOGuser_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];
$LOGallowed_queue_groups =		$row[4];
$LOGreports_header_override =   $row[5]; 
$LOGadmin_home_url =            $row[6]; 
if (strlen($LOGadmin_home_url) > 5) {$SSadmin_home_url = $LOGadmin_home_url;}  

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match("/ALL-/",$LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

$LOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ \-/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|"._QXZ("$report_name")."|\n";
    exit;
	}

function RemoveEmptyArrayStrings($array) 
	{
	if (is_array($array))
		{
		for ($i=0; $i<count($array); $i++)
				{
				if ($array[$i]=="") {unset($array[$i]);}
				}
		}
	return $array;
	}

function GetUserLocation($user) 
	{
	global $link;
	$location_stmt="select user_location from vicidial_users where user='$user'";
	$location_rslt=mysql_to_mysqli($location_stmt, $link);
	while($location_row=mysqli_fetch_row($location_rslt))
		{
		$location=$location_row[0];
		}
	if ($location=="") {$location="NONE";}
	return $location;
	}

# Includes several lists of arrays for report displays, ALSO all user permissions
require("VERM_global_vars.inc");

#### SQL CLAUSES FROM CUSTOM REPORT REQUEST PAGE

### TIME PERIOD - OVERRIDES MANUALLY ENTERED DATES (FOR NOW) ###
$NOW_TIME = date("Y-m-d H:i:s");
$NOW_DAY = date("Y-m-d");
$NOW_HOUR = date("H:i:s");
$STARTtime = date("U");
$epoch_YESTERDAY = ($STARTtime - 86400);
$YESTERDAY = date("Y-m-d",$epoch_YESTERDAY);
$epoch_DAY_BEFORE_YESTERDAY = ($STARTtime - 86400*2);
$DAY_BEFORE_YESTERDAY = date("Y-m-d",$epoch_DAY_BEFORE_YESTERDAY);
$epoch_SEVEN_DAYS = ($STARTtime - 86400*7);
$SEVEN_DAYS = date("Y-m-d",$epoch_SEVEN_DAYS);
$epoch_THIRTY_DAYS = ($STARTtime - 86400*30);
$THIRTY_DAYS = date("Y-m-d",$epoch_THIRTY_DAYS);
$epoch_NINETY_DAYS = ($STARTtime - 86400*90);
$NINETY_DAYS = date("Y-m-d",$epoch_NINETY_DAYS);

switch($time_period)
	{
	case "TODAY":
		$start_date=date("Y-m-d");
		$start_time_hour="00";
		$start_time_min="00";
		$start_time_sec="00";
		$end_date=date("Y-m-d");
		$end_time_hour="23";
		$end_time_min="59";
		$end_time_sec="59";
		break;
	case "YESTERDAY":
		$start_date=$YESTERDAY;
		$start_time_hour="00";
		$start_time_min="00";
		$start_time_sec="00";
		$end_date=$YESTERDAY;
		$end_time_hour="23";
		$end_time_min="59";
		$end_time_sec="59";
		break;
	case "DAYBEFORE":
		$start_date=$DAY_BEFORE_YESTERDAY;
		$start_time_hour="00";
		$start_time_min="00";
		$start_time_sec="00";
		$end_date=$DAY_BEFORE_YESTERDAY;
		$end_time_hour="23";
		$end_time_min="59";
		$end_time_sec="59";
		break;
	case "LAST24HOURS":
		$start_date=$YESTERDAY;
		$start_time_hour=date("H");
		$start_time_min=date("i");
		$start_time_sec=date("s");
		$end_date=date("Y-m-d");
		$end_time_hour=date("H");
		$end_time_min=date("i");
		$end_time_sec=date("s");
		break;
	case "LAST7DAYS":
		$start_date=$SEVEN_DAYS;
		$start_time_hour=date("H");
		$start_time_min=date("i");
		$start_time_sec=date("s");
		$end_date=date("Y-m-d");
		$end_time_hour=date("H");
		$end_time_min=date("i");
		$end_time_sec=date("s");
		break;
	case "LAST30DAYS":
		$start_date=$THIRTY_DAYS;
		$start_time_hour=date("H");
		$start_time_min=date("i");
		$start_time_sec=date("s");
		$end_date=date("Y-m-d");
		$end_time_hour=date("H");
		$end_time_min=date("i");
		$end_time_sec=date("s");
		break;
	case "LAST90DAYS":
		$start_date=$NINETY_DAYS;
		$start_time_hour=date("H");
		$start_time_min=date("i");
		$start_time_sec=date("s");
		$end_date=date("Y-m-d");
		$end_time_hour=date("H");
		$end_time_min=date("i");
		$end_time_sec=date("s");
		break;
	}

if (!$start_date) 
	{
	#Default is 7 days ago
	$epoch_SEVEN_DAYS = (date("U") - 86400*7);
	$start_date = date("Y-m-d",$epoch_SEVEN_DAYS);
	}
if (!$start_time_hour) {$start_time_hour="00";}
if (!$start_time_min) {$start_time_min="00";}
if (!$start_time_sec) {$start_time_sec="00";}
$start_time=$start_time_hour.":".$start_time_min.":".$start_time_sec;

if (!$end_date) {$end_date=date("Y-m-d");}
if (!$end_time_hour) {$end_time_hour="23";}
if (!$end_time_min) {$end_time_min="59";}
if (!$end_time_sec) {$end_time_sec="59";}
$end_time=$end_time_hour.":".$end_time_min.":".$end_time_sec;

if (!$time_of_day_start) {$time_of_day_start="00:00:00";}
if (!$time_of_day_end) {$time_of_day_end="23:59:59";}

# $and_call_date_sql="and call_date>='$start_date $start_time' and call_date<='$end_date $end_time'";
$where_call_date_sql="where call_date>='$start_date $start_time' and call_date<='$end_date $end_time'";

# $and_event_time_sql="and event_time>='$start_date $start_time' and event_time<='$end_date $end_time'";
$where_event_time_sql="where event_time>='$start_date $start_time' and event_time<='$end_date $end_time'";

$where_start_time_sql="where start_time>='$start_date $start_time' and start_time<='$end_date $end_time'";

$where_parked_time_sql="where parked_time>='$start_date $start_time' and parked_time<='$end_date $end_time'";

$where_event_date_sql="where event_date>='$start_date $start_time' and event_date<='$end_date $end_time'";


# if (isset($_GET["short_call_wait_limit"]))			{$short_call_wait_limit=$_GET["short_call_wait_limit"];}
# if (isset($_GET["short_call_talk_limit"]))			{$short_call_talk_limit=$_GET["short_call_talk_limit"];}
# if (isset($_GET["short_attempt_wait_limit"]))			{$short_attempt_wait_limit=$_GET["short_attempt_wait_limit"];}
# if (isset($_GET["asterisk_cid"]))			{$asterisk_cid=$_GET["asterisk_cid"];}

# $vicidial_log_SQL="$where_call_date_sql".$vicidial_log_SQL;
# $vicidial_closer_log_SQL="$where_call_date_sql".$vicidial_closer_log_SQL;
# $vicidial_did_log_SQL="$where_call_date_sql".$vicidial_did_log_SQL;
# $vicidial_agent_log_SQL="$where_event_time_sql".$vicidial_agent_log_SQL;
# $live_inbound_log_SQL="$where_start_time_sql".$live_inbound_log_SQL;

$vicidial_log_SQL="";
$vicidial_xfer_log_SQL="";
$vicidial_closer_log_SQL="";
$vicidial_did_log_SQL="";
$vicidial_user_log_SQL="";
$vicidial_agent_log_SQL="";
$vicidial_agent_log_UID_SQL="";
$live_inbound_log_SQL="";
$park_log_SQL="";

#### QUEUE GROUPS ####
$vicidial_queue_groups=preg_replace('/[^-_0-9\p{L}]/u','',$vicidial_queue_groups);
$vicidial_queue_groups=RemoveEmptyArrayStrings($vicidial_queue_groups);

if ($vicidial_queue_groups)
	{
	$vqg_stmt="select included_campaigns, included_inbound_groups from vicidial_queue_groups where queue_group='$vicidial_queue_groups'";
	$vqg_rslt=mysql_to_mysqli($vqg_stmt, $link);
	if(mysqli_num_rows($vqg_rslt)>0)
		{
		$vqg_row=mysqli_fetch_array($vqg_rslt);
		
		$included_campaigns=trim(preg_replace('/\s\-$/', '', $vqg_row["included_campaigns"]));
		$included_campaigns_array=explode(" ", $included_campaigns);
		$included_campaigns_array=RemoveEmptyArrayStrings($included_campaigns_array);
		$included_campaigns_ct=count($included_campaigns_array);
		$included_campaigns_clause="and campaign_id in ('".preg_replace('/\s/', "', '", $included_campaigns)."')";

		$included_inbound_groups=trim(preg_replace('/\s\-$/', '', $vqg_row["included_inbound_groups"]));
		$included_inbound_groups_array=explode(" ", $included_inbound_groups);
		$included_inbound_groups_array=RemoveEmptyArrayStrings($included_inbound_groups_array);
		$included_inbound_groups_ct=count($included_inbound_groups_array);
		$included_inbound_groups_clause="and group_id in ('".preg_replace('/\s/', "', '", $included_inbound_groups)."')";
		$where_included_inbound_groups_clause="where group_id in ('".preg_replace('/\s/', "', '", $included_inbound_groups)."')";
		}
	else
		{
		$included_campaigns_clause="and campaign_id in ('')";
		$included_inbound_groups_clause="and group_id in ('')";
		$where_included_inbound_groups_clause="where group_id in ('')";

		$included_campaigns_array=array();
		$included_inbound_groups_array=array();
		}
	}
else
	{
	$included_campaigns_clause="and campaign_id in ('')";
	$included_inbound_groups_clause="and group_id in ('')";
	$where_included_inbound_groups_clause="where group_id in ('')";

	$included_campaigns_array=array();
	$included_inbound_groups_array=array();
	}


# Get final list of 'atomic queues'
$atomic_queue_str="";

$atomic_queue_campaigns_str="";
$campaign_id_stmt="select campaign_id, campaign_name from vicidial_campaigns where campaign_id is not null $included_campaigns_clause order by campaign_id"; # $LOGallowed_campaignsSQL, removed for now per Matt's assurances
$campaign_id_rslt=mysql_to_mysqli($campaign_id_stmt, $link);
while($campaign_id_row=mysqli_fetch_array($campaign_id_rslt))
	{
	$atomic_queue_str.=$campaign_id_row["campaign_name"];
	$atomic_queue_str.=" <i>[".$campaign_id_row["campaign_id"]."]</i>,";
	$atomic_queue_campaigns_str.="$campaign_id_row[campaign_id]', '";
	}
$and_atomic_queue_campaigns_clause="and campaign_id in ('".$atomic_queue_campaigns_str."')";

# Check if queue settings override user group settings
$closer_campaigns_stmt="select closer_campaigns from vicidial_campaigns where closer_campaigns is not null $LOGallowed_campaignsSQL"; #  $included_campaigns_clause
$closer_campaigns_rslt=mysql_to_mysqli($closer_campaigns_stmt, $link); 
$allowed_ingroups_array=array();
while ($closer_campaigns_row=mysqli_fetch_array($closer_campaigns_rslt))
	{
	$closer_campaigns_array=explode(" ", trim(preg_replace('/\s\-$/', '', $closer_campaigns_row["closer_campaigns"])));
	for ($i=0; $i<count($closer_campaigns_array); $i++)
		{
		if (!in_array($closer_campaigns_array[$i], $allowed_ingroups_array))
			{
			array_push($allowed_ingroups_array, $closer_campaigns_array[$i]);
			}
		}
	}

$atomic_queue_ingroups_str="";
$ingroups_id_stmt="select group_id, group_name from vicidial_inbound_groups $where_included_inbound_groups_clause"; #where group_id in ('".implode("', '", $allowed_ingroups_array)."') $included_inbound_groups_clause
$ingroups_id_rslt=mysql_to_mysqli($ingroups_id_stmt, $link);
while($ingroups_id_row=mysqli_fetch_array($ingroups_id_rslt))
	{
	$atomic_queue_str.=$ingroups_id_row["group_name"];
	$atomic_queue_str.=" <i>[".$ingroups_id_row["group_id"]."]</i>,";
	$atomic_queue_ingroups_str.="$ingroups_id_row[group_id]', '";
	}
#### IMPORTANT - 1/26/23 - added "and campaign_id!=''" - may need to add to and_atomic_queue_campaigns_clause as well.
$and_atomic_queue_ingroups_clause="and campaign_id in ('".$atomic_queue_ingroups_str."') and campaign_id!=''";
$atomic_queue_str=preg_replace('/,$/', '', $atomic_queue_str);

if (strlen($atomic_queue_str)==0)
	{
	$atomic_queue_str="NONE";
	}

$inbound_only_agents_array=array();
if (count($included_campaigns_array)==0 && count($included_inbound_groups_array)>0) # Need to get list of users for user log table/vicidial agent log table
	{
	$closer_campaigns_SQL="and (";
	for ($q=0; $q<count($included_inbound_groups_array); $q++)
		{
		$closer_campaigns_SQL.="closer_campaigns like '% ".$included_inbound_groups_array[$q]." %' OR ";
		}
	$closer_campaigns_SQL=preg_replace('/ OR $/', "", $closer_campaigns_SQL).")";
	$vucl_stmt="select * from vicidial_user_closer_log $where_event_date_sql $closer_campaigns_SQL";
	$vucl_rslt=mysql_to_mysqli($vucl_stmt, $link);
	while ($vucl_row=mysqli_fetch_array($vucl_rslt))
		{
		if (!in_array($vucl_row["user"], $inbound_only_agents_array))
			{
			$inbound_only_agents_array[]=$vucl_row["user"];
			}
		}
	$and_inbound_only_agents_clause=" and user in ('".implode("', '", $inbound_only_agents_array)."') ";
	}

# Ok to have campaign_id='' here because it will filter out outbound
$vicidial_log_SQL.="$and_atomic_queue_campaigns_clause";
$vicidial_xfer_log_SQL.="$and_atomic_queue_ingroups_clause";
$vicidial_closer_log_SQL.="$and_atomic_queue_ingroups_clause";
$park_log_SQL.="$and_atomic_queue_campaigns_clause";
# $vicidial_did_log_SQL.="$where_call_date_sql";

# If no campaigns are involved in the queue group, we need to use the $inbound_agents_only_array
if (count($included_campaigns_array)>0 || count($inbound_only_agents_array)==0)
	{
	$vicidial_user_log_SQL.="$and_atomic_queue_campaigns_clause";
	$vicidial_agent_log_SQL.="$and_atomic_queue_campaigns_clause";
	}
else
	{
	$vicidial_user_log_SQL.="$and_inbound_only_agents_clause";
	$vicidial_agent_log_SQL.="$and_inbound_only_agents_clause";
	# $debug_alert.="<B> *** INBOUND ONLY QUEUE *** </B>";
	}

#### END QUEUE GROUPS ####

if ($users)
	{
	$users=preg_replace('/[^-_0-9\p{L}]/u','',$users);
	$users=RemoveEmptyArrayStrings($users);
	$users_str=is_array($users) ? implode("', '", $users) : "$users";
	$and_user_sql=" and user in ('$users_str')";
	$and_user_fc_sql=" and (fronter in ('$users_str') or closer in ('$users_str'))";
	$where_user_sql=" where user in ('$users_str')";

	$vicidial_log_SQL.=$and_user_sql;
	$vicidial_xfer_log_SQL.=$and_user_fc_sql;
	$vicidial_user_log_SQL.=$and_user_sql;
	$vicidial_closer_log_SQL.=$and_user_sql;
#	$vicidial_did_log_SQL.=$and_user_sql;
	$vicidial_agent_log_SQL.=$and_user_sql;
	$vicidial_agent_log_UID_SQL.=$and_user_sql;
	$park_log_SQL.=$and_user_sql;
	}

if ($teams)
	{
	$teams=preg_replace('/[^-_0-9\p{L}]/u','',$teams);
	$teams=RemoveEmptyArrayStrings($teams);
	$teams_str=is_array($teams) ? implode("', '", $teams) : "$teams";
	
	$team_users="";
	$team_stmt="select user from vicidial_users where user_group_two in ('$teams_str')";
	$team_rslt=mysql_to_mysqli($team_stmt, $link);
	while ($team_row=mysqli_fetch_array($team_rslt))
		{
		$team_users.="'$team_row[user]', ";
		}
	$team_users=preg_replace('/, $/', "", $team_users);
	$and_team_sql=" and user in ($team_users)";
	$and_team_fc_sql=" and (fronter in ('$team_users') or closer in ('$team_users'))";
	$where_team_sql=" where user in ($team_users)";

	$vicidial_log_SQL.=$and_team_sql;
	$vicidial_xfer_log_SQL.=$and_team_fc_sql;
	$vicidial_user_log_SQL.=$and_team_sql;
	$vicidial_closer_log_SQL.=$and_team_sql;
#	$vicidial_did_log_SQL.=$and_team_sql;
	$vicidial_agent_log_SQL.=$and_team_sql;
	$vicidial_agent_log_UID_SQL.=$and_team_sql;
	$park_log_SQL.=$and_team_sql;
	}

if ($location)
	{
	$location=preg_replace('/[^-_0-9\p{L}]/u','',$location);
	$location=RemoveEmptyArrayStrings($location);
	$location_str=is_array($location) ? implode("', '", $location) : "$location";
	$and_location_sql.=" and user_location in ('$location_str')";
	$where_location_sql.=" where user_location in ('$location_str')";

	# Compile list of additional users based on location
	$user_location_stmt="select user from vicidial_users where user is not null $LOGadmin_viewable_groupsSQL $and_location_sql";
	if ($DB) {echo $user_location_stmt."<BR>";}
	$user_location_rslt=mysql_to_mysqli($user_location_stmt, $link);
	$users_by_location=array();
	while ($user_location_row=mysqli_fetch_row($user_location_rslt))
		{
		array_push($users_by_location, $user_location_row[0]);
		}

	# Combine location with selected user
	if ($users)
		{
		array_push($users_by_location, $users);
		}
	
	$users_by_location_str=implode("', '", $users_by_location);
	$and_users_by_location_sql=" and user in ('$users_by_location_str')";
	$and_users_fc_by_location_sql=" and (fronter in ('$users_by_location_str') or closer in ('$users_by_location_str'))";
	$where_users_by_location_sql=" where user in ('$users_by_location_str')";

	$vicidial_log_SQL.=$and_users_by_location_sql;
	$vicidial_xfer_log_SQL.=$and_users_fc_by_location_sql;
	$vicidial_user_log_SQL.=$and_users_by_location_sql;
	$vicidial_closer_log_SQL.=$and_users_by_location_sql;
#	$vicidial_did_log_SQL.=$and_user_sql;
	$vicidial_agent_log_SQL.=$and_users_by_location_sql;
	$vicidial_agent_log_UID_SQL.=$and_users_by_location_sql;
	$park_log_SQL.=$and_users_by_location_sql;
	}

if ($user_group)
	{
	$user_group=preg_replace('/[^-_0-9\p{L}]/u','',$user_group);
	$user_group=RemoveEmptyArrayStrings($user_group);
	$user_group_str=is_array($user_group) ? implode("', '", $user_group) : "$user_group";
	$and_user_group_sql.=" and user_group in ('$user_group_str')";
	$where_user_group_sql.=" where user_group in ('$user_group_str')";

	$vicidial_log_SQL.=$and_user_group_sql;
	$vicidial_user_log_SQL.=$and_user_group_sql;
	$vicidial_closer_log_SQL.=$and_user_group_sql;
#	$vicidial_did_log_SQL.=$and_user_sql;
	$vicidial_agent_log_SQL.=$and_user_group_sql;
	$vicidial_agent_log_UID_SQL.=$and_user_group_sql;
	}

if ($statuses)
	{
	$statuses=preg_replace('/[^-_0-9\p{L}]/u','',$statuses);
	$statuses=RemoveEmptyArrayStrings($statuses);
	$status_str=is_array($statuses) ? implode("', '", $statuses) : "$statuses";
	$and_status_sql.=" and status in ('$status_str') ";
	$where_status_sql.=" where status in ('$status_str') ";

	$vicidial_log_SQL.=$and_status_sql;
	$vicidial_closer_log_SQL.=$and_status_sql;
	$vicidial_agent_log_SQL.=$and_status_sql;
	$vicidial_agent_log_UID_SQL.=$and_status_sql;
	}

if ($phone_number)
	{
	$phone_number = preg_replace('/[^0-9]/','',$phone_number);
	$and_phone_number_sql.=" and phone_number='$phone_number' ";
	$where_phone_number_sql.=" where phone_number='$phone_number' ";


	$vicidial_log_SQL.=$and_phone_number_sql;
	$vicidial_xfer_log_SQL.=$and_phone_number_sql;
	$vicidial_closer_log_SQL.=$and_phone_number_sql;

	# Get uniqueids from log/closer_log for agent log table in AGENTS reports only to save load issue
	}


if ($wait_sec_min || $wait_sec_max)
	{
	$and_wait_sec_sql="";
	$and_agent_wait_sec_sql="";
	$and_closer_wait_sec_sql="";
	
	$wait_sec_min = preg_replace('/[^0-9]/','',$wait_sec_min);
	$wait_sec_max = preg_replace('/[^0-9]/','',$wait_sec_max);

	if ($wait_sec_min)
		{

		# Vicidial log - this is to filter out all vicidial_log records because there is no wait time on them.
		$and_wait_sec_sql.=" and length_in_sec<0 ";

		# Closer log only
		$and_closer_wait_sec_sql.=" and queue_seconds>='$wait_sec_min' ";

		# Agent log only - not sure if this should be used but have no other metric for agents
		$and_agent_wait_sec_sql.=" and wait_sec>='$wait_sec_min' ";
		}
	if ($wait_sec_max)
		{
		# Vicidial log - no clause because again, no wait time

		# Closer log only
		$and_closer_wait_sec_sql.=" and queue_seconds<='$wait_sec_max' ";

		# Agent log only - not sure if this should be used but have no other metric for agents
		$and_agent_wait_sec_sql.=" and wait_sec<='$wait_sec_max'  ";
		}

	$vicidial_log_SQL.=$and_wait_sec_sql;
	$vicidial_closer_log_SQL.=$and_closer_wait_sec_sql;
#	$vicidial_did_log_SQL.=$and_user_sql;
	$vicidial_agent_log_SQL.=$and_agent_wait_sec_sql;
	$vicidial_agent_log_UID_SQL.=$and_agent_wait_sec_sql;
	}


if ($length_in_sec_min || $length_in_sec_max)
	{
	$length_in_sec_min = preg_replace('/[^0-9]/','',$length_in_sec_min);
	$length_in_sec_max = preg_replace('/[^0-9]/','',$length_in_sec_max);

	$and_length_in_sec_sql="";
	$and_closer_length_in_sec_sql="";
	$and_agent_length_in_sec_sql="";

	if ($length_in_sec_min)
		{
		# Vicidial log
		$and_length_in_sec_sql.=" and length_in_sec>='$length_in_sec_min' ";

		# Closer log
		$and_closer_length_in_sec_sql.=" and if(comments='EMAIL', length_in_sec, (length_in_sec-queue_seconds))>='$length_in_sec_min' ";

		# Agent log
		$and_agent_length_in_sec_sql.=" and talk_sec>='$length_in_sec_min' ";
		}

	if ($length_in_sec_max)
		{
		# Vicidial log
		$and_length_in_sec_sql.=" and length_in_sec<='$length_in_sec_max' ";

		# Closer log
		$and_closer_length_in_sec_sql.=" and if(comments='EMAIL', length_in_sec, (length_in_sec-queue_seconds))<='$length_in_sec_max' ";

		# Agent log
		$and_agent_length_in_sec_sql.=" and talk_sec<='$length_in_sec_max' ";
		}

	$vicidial_log_SQL.=$and_length_in_sec_sql;
	$vicidial_closer_log_SQL.=$and_closer_length_in_sec_sql;
#	$vicidial_did_log_SQL.=$and_user_sql;
	$vicidial_agent_log_SQL.=$and_agent_length_in_sec_sql;	
	$vicidial_agent_log_UID_SQL.=$and_agent_length_in_sec_sql;
	}

if ($disconnection_cause)
	{
	$disconnection_cause = preg_replace('/[^-_0-9\p{L}]/u','',$disconnection_cause);
	$and_term_reason_sql.=" and term_reason='$disconnection_cause' ";
	$where_term_reason_sql.=" where term_reason='$disconnection_cause' ";

	$vicidial_log_SQL.=$and_term_reason_sql;
	$vicidial_closer_log_SQL.=$and_term_reason_sql;
#	$vicidial_did_log_SQL.=$and_user_sql;
#	$vicidial_agent_log_SQL.=$and_agent_length_in_sec_sql;	

	# Get uniqueids from the log tables based on the disconnection causes for the agent reports.  Do it in the agent reports to reduce load.
	}

if ($queue_position_min || $queue_position_max)
	{
	$and_queue_position_sql="";
	$and_closer_queue_position_sql="";
	$and_agent_queue_position_sql=" and (comments!='INBOUND' OR ";

	$queue_position_min = preg_replace('/[^0-9]/','',$queue_position_min);
	$queue_position_max = preg_replace('/[^0-9]/','',$queue_position_max);

	if ($queue_position_min)
		{
		if ($queue_position_min>1)
			{
			# Vicidial log only - put this in to remove outbound calls.  Outbound calls are assumed to have a 
			# queue position of '1' for purposes of this report
			$and_queue_position_sql.=" and length_in_sec<0 ";
			}

		# Closer log only
		$and_closer_queue_position_sql.=" and queue_position>='$queue_position_min' ";
		}
	if ($queue_position_max)
		{
		# Closer log only
		$and_closer_queue_position_sql.=" and queue_position<='$queue_position_max' ";
		}

	$vicidial_log_SQL.=$and_queue_position_sql;
	$vicidial_closer_log_SQL.=$and_closer_queue_position_sql;
#	$vicidial_did_log_SQL.=$and_user_sql;
#	$vicidial_agent_log_SQL.=$and_agent_len;	

	# For agents, get uniqueids for inbound calls and FINISH that queue_position clause.

	}

if ($call_count_min || $call_count_max)
	{
	$call_count_min = preg_replace('/[^0-9]/','',$call_count_min);
	$call_count_max = preg_replace('/[^0-9]/','',$call_count_max);

	$and_called_count_sql="";

	if ($call_count_min)
		{
		$and_called_count_sql.=" and called_count>='$call_count_min' ";
		$where_called_count_sql.=" where called_count>='$call_count_min' ";
		}
	if ($call_count_max)
		{
		$and_called_count_sql.=" and called_count<='$call_count_max' ";
		$where_called_count_sql.=" where called_count<='$call_count_max' ";
		}

	$vicidial_log_SQL.=$and_called_count_sql;
	$vicidial_closer_log_SQL.=$and_called_count_sql;
#	$vicidial_did_log_SQL.=$and_user_sql;
#	$vicidial_agent_log_SQL.=$and_agent_len;	

	# For agents, get uniqueids for inbound calls and FINISH that queue_position clause.

	}

# If DID is listed, need array of unique IDs from DID table to count calls
if ($did)
	{
	$did = preg_replace('/[^0-9]/','',$did);
	$and_did_sql=" and did_id='$did' ";
	$where_did_sql=" where did_id='$did' ";

	$did_stmt="select uniqueid from vicidial_did_log $where_call_date_sql $and_did_sql";
	$did_rslt=mysql_to_mysqli($did_stmt, $link);
	$did_uniqueid_array=array();
	while ($did_row=mysqli_fetch_row($did_rslt))
		{
		array_push($did_uniqueid_array, "$did_row[0]");
		}

	$and_did_uniqueid_sql=" and uniqueid in ('".implode("', '", $did_uniqueid_array)."') ";
	$and_did_fc_uniqueid_sql=" and (front_uniqueid in ('".implode("', '", $did_uniqueid_array)."') or close_uniqueid in ('".implode("', '", $did_uniqueid_array)."')) ";

	$vicidial_log_SQL.=$and_did_uniqueid_sql;
	$vicidial_xfer_log_SQL.=$and_did_fc_uniqueid_sql;
	$vicidial_closer_log_SQL.=$and_did_uniqueid_sql;
	$vicidial_did_log_SQL.=$and_did_uniqueid_sql;
	$live_inbound_log_SQL.=$and_did_uniqueid_sql;
	$vicidial_agent_log_SQL.=$and_did_uniqueid_sql;	
	$vicidial_agent_log_UID_SQL.=$and_did_uniqueid_sql;
	$park_log_SQL.=$and_did_uniqueid_sql;
	}

if ($server || $asterisk_cid)
	{
	$server = preg_replace('/[^0-9.]/','',$server);
	$asterisk_cid = preg_replace('/[^0-9A-Z]/','',$asterisk_cid);

	if ($server)
		{	
		$and_server_ip_sql=" and server_ip='$server' ";
		$where_server_ip_sql=" where server_ip='$server' ";
		}

	if ($asterisk_cid)
		{
		$and_caller_code_sql=" and caller_code='$asterisk_cid' ";
		$where_caller_code_sql=" where caller_code='$asterisk_cid' ";
		$and_extension_sql=" and extension='$asterisk_cid' "; # For park_log table
		}

	$vle_stmt="select uniqueid from vicidial_log_extended $where_call_date_sql $and_server_ip_sql $and_caller_code_sql";
	$vle_rslt=mysql_to_mysqli($vle_stmt, $link);
	$extended_log_uniqueid_array=array();
	while($vle_row=mysqli_fetch_row($vle_rslt))
		{
		array_push($extended_log_uniqueid_array, "$vle_row[0]");
		}

	$and_extended_uniqueid_sql=" and uniqueid in ('".implode("', '", $extended_log_uniqueid_array)."') ";
	$and_extended_fc_uniqueid_sql=" and (front_uniqueid in ('".implode("', '", $extended_log_uniqueid_array)."') or close_uniqueid in ('".implode("', '", $extended_log_uniqueid_array)."')) ";

	$vicidial_log_SQL.=$and_extended_uniqueid_sql;
	$vicidial_xfer_log_SQL.=$and_extended_fc_uniqueid_sql;
	$vicidial_closer_log_SQL.=$and_extended_uniqueid_sql;
	$vicidial_did_log_SQL.=$and_extended_uniqueid_sql;
	$live_inbound_log_SQL.=$and_extended_uniqueid_sql;
	$vicidial_agent_log_SQL.=$and_extended_uniqueid_sql;		
	$vicidial_agent_log_UID_SQL.=$and_extended_uniqueid_sql;
	$park_log_SQL.=$and_server_ip_sql.$and_extension_sql;
	}

if ($dow)
	{
	$dow = preg_replace('/[^0-9,]/','',$dow);
	$dow=RemoveEmptyArrayStrings($dow);
	$dow_str=is_array($dow) ? implode(",", $dow) : "$dow";
	$dow_str=preg_replace('/,{2,}/', ',', $dow_str);
	$dow_str=preg_replace('/^,|,$/', '', $dow_str);
	$and_call_date_DOWsql=" and dayofweek(call_date) in ($dow_str) ";
	$where_call_date_DOWsql=" where dayofweek(call_date) in ($dow_str) ";
	# Agent log
	$and_event_time_DOWsql=" and dayofweek(event_time) in ($dow_str) ";
	$where_event_time_DOWsql=" where dayofweek(event_time) in ($dow_str) ";
	# Live inbound log
	$and_start_time_DOWsql=" and dayofweek(start_time) in ($dow_str) ";
	$where_start_time_DOWsql=" where dayofweek(start_time) in ($dow_str) ";
	# Park log
	$and_parked_time_DOWsql=" and dayofweek(parked_time) in ($dow_str) ";
	$where_parked_time_DOWsql=" where dayofweek(parked_time) in ($dow_str) ";


	$vicidial_log_SQL.=$and_call_date_DOWsql;
	$vicidial_xfer_log_SQL.=$and_call_date_DOWsql;
	$vicidial_closer_log_SQL.=$and_call_date_DOWsql;
	$vicidial_did_log_SQL.=$and_call_date_DOWsql;
	$live_inbound_log_SQL.=$and_start_time_DOWsql;
	$vicidial_agent_log_SQL.=$and_event_time_DOWsql;			
	$vicidial_agent_log_UID_SQL.=$and_event_time_DOWsql;
	$park_log_SQL.=$and_parked_time_DOWsql;
	}
else
	{
	$and_call_date_DOWsql="";
	$where_call_date_DOWsql="";
	$and_event_time_DOWsql="";
	$where_event_time_DOWsql="";
	$and_start_time_DOWsql="";
	$where_start_time_DOWsql="";
	$and_parked_time_DOWsql="";
	$where_parked_time_DOWsql="";
	}

if ($time_of_day_start)
	{
	$and_call_date_TODSsql=" and time(call_date)>='$time_of_day_start' ";
	$where_call_date_TODSsql=" where time(call_date)>='$time_of_day_start' ";
	# Agent log
	$and_event_time_TODSsql=" and time(event_time)>='$time_of_day_start' ";
	$where_event_time_TODSsql=" where time(event_time)>='$time_of_day_start' ";
	# Live inbound log
	$and_start_time_TODSsql=" and time(start_time)>='$time_of_day_start' ";
	$where_start_time_TODSsql=" where time(start_time)>='$time_of_day_start' ";
	# Park log
	$and_parked_time_TODSsql=" and time(parked_time)>='$time_of_day_start' ";
	$where_parked_time_TODSsql=" where time(parked_time)>='$time_of_day_start' ";
	
	$vicidial_log_SQL.=$and_call_date_TODSsql;
	$vicidial_xfer_log_SQL.=$and_call_date_TODSsql;
	$vicidial_closer_log_SQL.=$and_call_date_TODSsql;
	$vicidial_did_log_SQL.=$and_call_date_TODSsql;
	$live_inbound_log_SQL.=$and_start_time_TODSsql;
	$vicidial_agent_log_SQL.=$and_event_time_TODSsql;
	$vicidial_agent_log_UID_SQL.=$and_event_time_TODSsql;
	$park_log_SQL.=$and_parked_time_TODSsql;
	}
else
	{
	$and_call_date_TODSsql="";
	$where_call_date_TODSsql="";
	$and_event_time_TODSsql="";
	$where_event_time_TODSsql="";
	$and_start_time_TODSsql="";
	$where_start_time_TODSsql="";
	$and_parked_time_TODSsql="";
	$where_parked_time_TODSsql="";	
	}

if ($time_of_day_end)
	{
	$and_call_date_TODEsql=" and time(call_date)<='$time_of_day_end' ";
	$where_call_date_TODEsql=" where time(call_date)<='$time_of_day_end' ";
	# Agent log
	$and_event_time_TODEsql=" and time(event_time)<='$time_of_day_end' ";
	$where_event_time_TODEsql=" where time(event_time)<='$time_of_day_end' ";
	# Live inbound log
	$and_start_time_TODEsql=" and time(start_time)<='$time_of_day_end' ";
	$where_start_time_TODEsql=" where time(start_time)<='$time_of_day_end' ";
	# Park log
	$and_parked_time_TODEsql=" and time(parked_time)<='$time_of_day_end' ";
	$where_parked_time_TODEsql=" where time(parked_time)<='$time_of_day_end' ";

	
	$vicidial_log_SQL.=$and_call_date_TODEsql;
	$vicidial_xfer_log_SQL.=$and_call_date_TODEsql;
	$vicidial_closer_log_SQL.=$and_call_date_TODEsql;
	$vicidial_did_log_SQL.=$and_call_date_TODEsql;
	$live_inbound_log_SQL.=$and_start_time_TODEsql;
	$vicidial_agent_log_SQL.=$and_event_time_TODEsql;
	$vicidial_agent_log_UID_SQL.=$and_event_time_TODEsql;
	$park_log_SQL.=$and_parked_time_TODEsql;	
	}
else
	{
	$and_call_date_TODEsql="";
	$where_call_date_TODEsql="";
	$and_event_time_TODEsql="";
	$where_event_time_TODEsql="";
	$and_start_time_TODEsql="";
	$where_start_time_TODEsql="";
	$and_parked_time_TODEsql="";
	$where_parked_time_TODEsql="";
	}

# Trim call time off because of Day/DOW/Hour reports
$and_vicidial_log_SQL=$vicidial_log_SQL;
$and_vicidial_xfer_log_SQL=$vicidial_xfer_log_SQL;
$and_vicidial_closer_log_SQL=$vicidial_closer_log_SQL;
$and_vicidial_did_log_SQL=$vicidial_did_log_SQL;
$and_vicidial_agent_log_SQL=$vicidial_agent_log_SQL;
$and_vicidial_agent_log_UID_SQL=$vicidial_agent_log_UID_SQL;
$and_vicidial_user_log_SQL=$vicidial_user_log_SQL;
$and_live_inbound_log_SQL=$live_inbound_log_SQL;
$and_park_log_SQL=$park_log_SQL;

$vicidial_log_SQL="$where_call_date_sql".$vicidial_log_SQL." and phone_number!='' "; # JCJ - added 3/23/22
$vicidial_xfer_log_SQL="$where_call_date_sql".$vicidial_xfer_log_SQL;
$vicidial_closer_log_SQL="$where_call_date_sql".$vicidial_closer_log_SQL;
$vicidial_did_log_SQL="$where_call_date_sql".$vicidial_did_log_SQL;
$vicidial_agent_log_SQL="$where_event_time_sql".$vicidial_agent_log_SQL;
$vicidial_agent_log_UID_SQL="$where_event_time_sql".$vicidial_agent_log_UID_SQL;
$vicidial_user_log_SQL="$where_event_date_sql".$vicidial_user_log_SQL;
$live_inbound_log_SQL="$where_start_time_sql".$live_inbound_log_SQL;
$park_log_SQL="$where_parked_time_sql".$park_log_SQL;

# Get closer log uniqueids for the agent log lookup
# 03/01/2022 - added $and_NANQUE_clause $exc_addtl_statuses clauses, also added 'if' clause to ensure
#              only answered call uniqueIDs are used in the OUTCOMES report, which is currently the only
#              report that uses it.
$vcl_uniqueid_stmt="select user, uniqueid From vicidial_closer_log $vicidial_closer_log_SQL $and_NANQUE_clause $exc_addtl_statuses";
# $HTML_output.="Unique IDs: $vcl_uniqueid_stmt<BR>";
$vcl_uniqueid_rslt=mysql_to_mysqli($vcl_uniqueid_stmt, $link);
$val_uniqueid_array=array();
while ($vcl_uniqueid_row=mysqli_fetch_row($vcl_uniqueid_rslt))
	{
	if (!preg_match('/VDAD|VDCL/', $vcl_uniqueid_row[0]))	
		{
		array_push($val_uniqueid_array, $vcl_uniqueid_row[1]);
		}
	}

# $DB=1; 

if (!$report_type) {$report_type="ANSWERED";}


##### BEGIN log visit to the vicidial_report_log table #####
$LOGip = getenv("REMOTE_ADDR");
$LOGbrowser = getenv("HTTP_USER_AGENT");
$LOGscript_name = getenv("SCRIPT_NAME");
$LOGserver_name = getenv("SERVER_NAME");
$LOGserver_port = getenv("SERVER_PORT");
$LOGrequest_uri = getenv("REQUEST_URI");
$LOGhttp_referer = getenv("HTTP_REFERER");
$LOGbrowser=preg_replace("/<|>|\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/<|>|\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/<|>|\'|\"|\\\\/","",$LOGhttp_referer);
if (preg_match("/443/i",$LOGserver_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) {$LOGserver_port='';}
else {$LOGserver_port = ":$LOGserver_port";}
$LOGfull_url = "$HTTPprotocol$LOGserver_name$LOGserver_port$LOGrequest_uri";

$LOGhostname = php_uname('n');
if (strlen($LOGhostname)<1) {$LOGhostname='X';}
if (strlen($LOGserver_name)<1) {$LOGserver_name='X';}

$stmt="SELECT webserver_id FROM vicidial_webservers where webserver='$LOGserver_name' and hostname='$LOGhostname' LIMIT 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$webserver_id_ct = mysqli_num_rows($rslt);
if ($webserver_id_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$webserver_id = $row[0];
	}
else
	{
	##### insert webserver entry
	$stmt="INSERT INTO vicidial_webservers (webserver,hostname) values('$LOGserver_name','$LOGhostname');";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	$webserver_id = mysqli_insert_id($link);
	}

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$user_group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

##### Log custom report parameters if report is custom #####
/*
$URL_referrer_array=explode("?", $LOGfull_url);
$report_params_str=$URL_referrer_array[1];
if ($log_custom_report)
	{
	$rpt_log_stmt="insert ignore into verm_custom_report_holder(user, report_name, report_parameters) values('$PHP_AUTH_USER', '$custom_report_name', '$LOGhttp_referer') ON DUPLICATE KEY UPDATE report_name='$custom_report_name', report_parameters='$report_params_str'";
	echo $rpt_log_stmt;
	$rpt_log_rslt=mysql_to_mysqli($rpt_log_stmt, $link);
	}
*/
############################################################

if (!$total_calls || !$total_unanswered_calls || !$total_answered_calls)
	{
	$total_calls=0; $total_unanswered_calls=0; $total_answered_calls=0;

	$calls_stmt="select user, status, uniqueid From vicidial_log $vicidial_log_SQL $and_NANQUE_clause $exc_addtl_statuses UNION select user, status, uniqueid From vicidial_closer_log $vicidial_closer_log_SQL $and_NANQUE_clause $exc_addtl_statuses";
	# $HTML_output.="CALLS: $calls_stmt<BR>\n";
	$calls_rslt=mysql_to_mysqli($calls_stmt, $link);
	while ($calls_row=mysqli_fetch_array($calls_rslt)) 
		{
		$total_calls++;
		if (preg_match('/VDAD|VDCL/', $calls_row["user"])) #  && preg_match('/^DROP$|TIMEOT|WAITTO|NANQUE/', $calls_row["status"])
			{
			$total_unanswered_calls++;
			}
		else
			{
			$total_answered_calls++;
			}
		}
		$answered_percentage=sprintf("%.1f", MathZDC((100*$total_answered_calls), $total_calls));
		$unanswered_percentage=sprintf("%.1f", MathZDC((100*$total_unanswered_calls), $total_calls));
	}

$CSV_output["header"]="\""._QXZ("Report Details").":\"\n";
$CSV_output["header"].="\""._QXZ("Report generated on").":\",\"".date("F j Y, G:i")."\"\n";
$CSV_output["header"].="\""._QXZ("Atomic queue(s) considered").":\",\"".strip_tags($atomic_queue_str)."\"\n";
$CSV_output["header"].="\""._QXZ("Period start date").":\",\"".date('F j Y, H:i', strtotime("$start_date $start_time"))."\"\n";
$CSV_output["header"].="\""._QXZ("Period end date").":\",\"".date('F j Y, H:i', strtotime("$end_date $end_time"))."\"\n";
$CSV_output["header"].="\""._QXZ("Total calls processed").":\",\"".$total_calls." (".$total_answered_calls." ans / ".$total_unanswered_calls." unans)"."\"\n";
$CSV_output["header"].="\""._QXZ("Ratio").":\",\"".$answered_percentage."% ans / ".$unanswered_percentage."% unans"."\"\n";

$report_log_count=0;

if (!$auto_download_limit) {$auto_download_limit=99999999999;}
if ($total_calls>$auto_download_limit) {$agents_dt_download_notice=1;}
if ($total_answered_calls>$auto_download_limit) {$answered_dt_download_notice=1;}
if ($total_unanswered_calls>$auto_download_limit) {$unanswered_dt_download_notice=1;}


if ( ($total_calls>$auto_download_limit && $report_type=="AGENTS_DT") || ($total_answered_calls>$auto_download_limit && $report_type=="ANSWERED_DT") || ($total_unanswered_calls>$auto_download_limit && $report_type=="UNANSWERED_DT") )
	{
	$download_rpt="auto_download";
	}

if (!$download_rpt)
	{
	require("VERM_header.inc"); # Contains form tag opener that is closed below, also html and body opener tags and form fields
	}

require("VERM_".$report_type."_rpt.inc");

echo "</form>";

$ENDtime=date("U");
$endMS = microtime();
$startMSary = explode(" ",$startMS);
$endMSary = explode(" ",$endMS);
$runS = ($endMSary[0] - $startMSary[0]);
$runM = ($endMSary[1] - $startMSary[1]);
$TOTALrun = ($runS + $runM);

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

echo "<font size='1'>Report execution time: ".($ENDtime-$STARTtime)." sec</font>";
?>
</body>
</html>
