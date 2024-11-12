<?php
# VERM_custom_report.php - Vicidial Enhanced Reporting custom report form page
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1606 - First build
# 240801-1130 - Code updates for PHP8 compatibility
#

$startMS = microtime();

$version = '2.14-873';
$build = '230127-1750';

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["start_date"]))			{$start_date=$_GET["start_date"];}
	elseif (isset($_POST["start_date"]))	{$start_date=$_POST["start_date"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["DB"]))			{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["custom_report_name"]))			{$custom_report_name=$_GET["custom_report_name"];}
	elseif (isset($_POST["custom_report_name"]))	{$custom_report_name=$_POST["custom_report_name"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,agent_whisper_enabled,report_default_format,enable_pause_code_limits,allow_web_debug,admin_screen_colors,admin_web_directory FROM system_settings;";
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
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

$db_source = 'M';

## $DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
## $start_date=preg_replace('/[^-0-9]/', '', $start_date);
## $end_date=preg_replace('/[^-0-9]/', '', $end_date);

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
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
# $dow = preg_replace('/[^0-9]/','',$dow);
$time_of_day_start = preg_replace('/[^\:0-9]/','',$time_of_day_start);
$time_of_day_end = preg_replace('/[^\:0-9]/','',$time_of_day_end);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9a-zA-Z]/','',$group);
	$vicidial_queue_groups = preg_replace('/[^-_0-9a-zA-Z]/','',$vicidial_queue_groups);
	$user_group_filter = preg_replace('/[^-_0-9a-zA-Z]/','',$user_group_filter);
	$ingroup_filter = preg_replace('/[^-_0-9a-zA-Z]/','',$ingroup_filter);

	$report_types = preg_replace('/[^\._a-zA-Z]/','',$report_types);
	$report_types_to_display = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$report_types_to_display);
	$report_type = preg_replace('/[^\._a-zA-Z]/','',$report_type);
	$time_period = preg_replace('/[^\s0-9a-zA-Z]/','',$time_period);
	$users = preg_replace('/[^\-_0-9a-zA-Z]/','',$users);
	$teams = preg_replace('/[^\-_0-9a-zA-Z]/','',$teams);
	$location = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$location); 
	$user_group = preg_replace('/[^\-_0-9a-zA-Z]/','',$user_group);
	$statuses = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$statuses);
	$asterisk_cid = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$asterisk_cid);
	$disconnection_cause = preg_replace('/[^\-_0-9a-zA-Z]/','',$disconnection_cause);
	$ivr_choice = preg_replace('/[^\-\_\#\*0-9A-Z]/','',$ivr_choice);
	$custom_report_name = preg_replace('/[^\-_0-9a-zA-Z]/','',$custom_report_name);
	}
else
	{
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
	$user_group = preg_replace('/[^-_0-9\p{L}/u','',$user_group);
	$statuses = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$statuses);
	$asterisk_cid = preg_replace('/[^\s\-_0-9\p{L}/u','',$asterisk_cid);
	$disconnection_cause = preg_replace('/[^-_0-9\p{L}]/u','',$disconnection_cause);
	$ivr_choice = preg_replace('/[^-\_\#\*0-9\p{L}]/u','',$ivr_choice);
	$custom_report_name = preg_replace('/[^-_0-9\p{L}/u','',$custom_report_name);
	}

if (file_exists('options.php'))
	{
	require('options.php');
	}


### Force hard-coded variables
# $report_display_type='LIMITED';
# $ingroup_filter=array('_STAY','TEST_IN2','TEST_IN3');

if ( (strlen($group)>1) and (strlen($groups[0])<1) ) {$groups[0] = $group;}
else {$group = $groups[0];}
if (!is_array($user_group_filter) || $user_group_filter=='') {$user_group_filter=array();}
if (!is_array($ingroup_filter) || $ingroup_filter=='') {$ingroup_filter=array();}

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
$webphone_content='';

/*
if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	}
 */

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
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

require("VERM_global_vars.inc");

##### Look up custom report parameters from prior use #####
$custom_rpt_stmt="select * from verm_custom_report_holder where user='$PHP_AUTH_USER' and ((report_name!='' and report_name='$custom_report_name') or modify_date>=now()-INTERVAL 8 HOUR) limit 1";
$custom_rpt_rslt=mysql_to_mysqli($custom_rpt_stmt, $link);
$dow=array();
while ($custom_rpt_row=mysqli_fetch_array($custom_rpt_rslt))
	{
	$custom_rpt_parameters=$custom_rpt_row["report_parameters"];
	$custom_rpt_array=explode("|", $custom_rpt_parameters);
	for ($i=0; $i<count($custom_rpt_array); $i++)
		{
		$var_info=explode("=", $custom_rpt_array[$i]);
		$var_name=$var_info[0];
		$var_value=$var_info[1];
		if (strlen($var_name)>0)
			{
			$var_value=preg_replace('/^undefined$/i', '', $var_value); 
			if (preg_match('/^dow/', $var_name))
				{
				array_push($dow, $var_value);
				}
			else
				{
				$$var_name=$var_value;
				}
			}
		}
	}	
###########################################################


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

##### END log visit to the vicidial_report_log table #####

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

if ($auth) 
	{
	$stmt="SELECT user_id,user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,shift_override_flag,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,callcard_admin,force_change_password,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,modify_contacts,modify_same_user_level, user_location from vicidial_users where user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$LOGfull_name				=$row[3];
	$LOGuser_level				=$row[4];
	$LOGuser_group				=$row[5];
	$LOGdelete_users			=$row[8];
	$LOGdelete_user_groups		=$row[9];
	$LOGdelete_lists			=$row[10];
	$LOGdelete_campaigns		=$row[11];
	$LOGdelete_ingroups			=$row[12];
	$LOGdelete_remote_agents	=$row[13];
	$LOGload_leads				=$row[14];
	$LOGcampaign_detail			=$row[15];
	$LOGast_admin_access		=$row[16];
	$LOGast_delete_phones		=$row[17];
	$LOGdelete_scripts			=$row[18];
	$LOGdelete_filters			=$row[29];
	$LOGalter_agent_interface	=$row[30];
	$LOGdelete_call_times		=$row[32];
	$LOGmodify_call_times		=$row[33];
	$LOGmodify_users			=$row[34];
	$LOGmodify_campaigns		=$row[35];
	$LOGmodify_lists			=$row[36];
	$LOGmodify_scripts			=$row[37];
	$LOGmodify_filters			=$row[38];
	$LOGmodify_ingroups			=$row[39];
	$LOGmodify_usergroups		=$row[40];
	$LOGmodify_remoteagents		=$row[41];
	$LOGmodify_servers			=$row[42];
	$LOGview_reports			=$row[43];
	$LOGmodify_dids				=$row[56];
	$LOGdelete_dids				=$row[57];
	$LOGmanager_shift_enforcement_override=$row[61];
	$LOGexport_reports			=$row[64];
	$LOGdelete_from_dnc			=$row[65];
	$LOGcallcard_admin			=$row[70];
	$LOGforce_change_password	=$row[71];
	$LOGmodify_shifts			=$row[72];
	$LOGmodify_phones			=$row[73];
	$LOGmodify_carriers			=$row[74];
	$LOGmodify_labels			=$row[75];
	$LOGmodify_statuses			=$row[76];
	$LOGmodify_voicemail		=$row[77];
	$LOGmodify_audiostore		=$row[78];
	$LOGmodify_moh				=$row[79];
	$LOGmodify_tts				=$row[80];
	$LOGmodify_contacts			=$row[81];
	$LOGmodify_same_user_level	=$row[82];
	$LOGuser_location			=$row[83];

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

	}

$stmt="SELECT allowed_campaigns,allowed_reports,webphone_url_override,webphone_dialpad_override,webphone_systemkey_override,allowed_custom_reports from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			"$row[1]$row[5]";
$webphone_url =					$row[2];
$webphone_dialpad_override =	$row[3];
$system_key =					$row[4];

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

/*
if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|"._QXZ("$report_name")."|\n";
    exit;
	}
*/

$allactivecampaigns='';
$stmt="select campaign_id,campaign_name from vicidial_campaigns where active='Y' $LOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$LISTgroups=array();
$LISTnames=array();
$i=0;
$LISTgroups[$i]='ALL-ACTIVE';
$i++;
$groups_to_print++;
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTgroups[$i] =$row[0];
	$LISTnames[$i] =$row[1];
	$allactivecampaigns .= "'$LISTgroups[$i]',";
	$i++;
	}
$allactivecampaigns .= "''";

$i=0;
$group_string='|';
if (!is_array($groups)) {$groups=array();}
$group_ct = count($groups);
while($i < $group_ct)
	{
	$groups[$i] = preg_replace('/[^-_0-9a-zA-Z]/', '', $groups[$i]);
	if ( (preg_match("/ $groups[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/ALL-/",$LOGallowed_campaigns)) )
		{
		$group_string .= "$groups[$i]|";
		$group_SQL .= "'$groups[$i]',";
		$groupQS .= "&groups[]=$groups[$i]";
		}

	$i++;
	}
$group_SQL = preg_replace('/,$/i', '',$group_SQL);

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group_filter);
while($i < $user_group_ct)
	{
	$user_group_filter[$i] = preg_replace('/[^-_0-9a-zA-Z]/', '', $user_group_filter[$i]);
#	if ( (preg_match("/ $user_group_filter[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/ALL-/",$LOGallowed_campaigns)) )
#		{
		$user_group_string .= "$user_group_filter[$i]|";
		$user_group_SQL .= "'$user_group_filter[$i]',";
		$usergroupQS .= "&user_group_filter[]=$user_group_filter[$i]";
#		}

	$i++;
	}
$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);

$i=0;
$ingroup_string='|';
$ingroup_ct = count($ingroup_filter);
while($i < $ingroup_ct)
	{
	$ingroup_filter[$i] = preg_replace('/[^-_0-9a-zA-Z]/', '', $ingroup_filter[$i]);
	$ingroup_string .= "$ingroup_filter[$i]|";
	$ingroup_SQL .= "'$ingroup_filter[$i]',";
	$usergroupQS .= "&ingroup_filter[]=$ingroup_filter[$i]";

	$i++;
	}
$ingroup_SQL = preg_replace('/,$/i', '',$ingroup_SQL);

### if no campaigns selected, display all
if ( ($group_ct < 1) or (strlen($group_string) < 2) )
	{
	$groups[0] = 'ALL-ACTIVE';
	$group_string = '|ALL-ACTIVE|';
	$group = 'ALL-ACTIVE';
	$groupQS .= "&groups[]=ALL-ACTIVE";
	}
### if no user groups selected, display all
$user_group_none=0;
if ( ($user_group_ct < 1) or (strlen($user_group_string) < 2) )
	{
	$user_group_filter[0] = 'ALL-GROUPS';
	$user_group_string = '|ALL-GROUPS|';
	$usergroupQS .= "&user_group_filter[]=ALL-GROUPS";
	$user_group_none=1;
	}
### if no ingroups selected, display all
$ingroup_none=0;
if ( ($ingroup_ct < 1) or (strlen($ingroup_string) < 2) )
	{
	$ingroup_filter[0] = 'ALL-INGROUPS';
	$ingroup_string = '|ALL-INGROUPS|';
	$ingroupQS .= "&ingroup_filter[]=ALL-INGROUPS";
	$ingroup_none=1;
	}

if ( (preg_match('/\s\-\-NONE\-\-\s/',$group_string) ) or ($group_ct < 1) )
	{
	$all_active = 0;
	$group_SQL = "''";
	$group_SQLand = "and FALSE";
	$group_SQLwhere = "where FALSE";
	}
elseif ( preg_match('/ALL\-ACTIVE/i',$group_string) )
	{
	$all_active = 1;
	$group_SQL = $allactivecampaigns;
	$group_SQLand = "and campaign_id IN($allactivecampaigns)";
	$group_SQLwhere = "where campaign_id IN($allactivecampaigns)";
	}
else
	{
	$all_active = 0;
	$group_SQLand = "and campaign_id IN($group_SQL)";
	$group_SQLwhere = "where campaign_id IN($group_SQL)";
	}

if ( (preg_match('/\s\-\-NONE\-\-\s/',$user_group_string) ) or ($user_group_ct < 1) )
	{
	$all_active_groups = 0;
	$user_group_SQL = "''";
#	$user_group_SQLand = "and FALSE";
#	$user_group_SQLwhere = "where FALSE";
	}
elseif ( preg_match('/ALL\-GROUPS/i',$user_group_string) )
	{
	$all_active_groups = 1;
#	$user_group_SQL = '';
	$user_group_SQL = "'$rawLOGadmin_viewable_groupsSQL'";
#	$group_SQLand = "and campaign_id IN($allactivecampaigns)";
#	$group_SQLwhere = "where campaign_id IN($allactivecampaigns)";
	}
else
	{
	$all_active_groups = 0;
#	$user_group_SQLand = "and user_group IN($user_group_SQL)";
#	$user_group_SQLwhere = "where user_group IN($user_group_SQL)";
	}


if ( (preg_match('/\s\-\-NONE\-\-\s/',$ingroup_string) ) or ($ingroup_ct < 1) )
	{
	$all_active_ingroups = 0;
	$ingroup_SQL = "''";
	}
elseif ( preg_match('/ALL\-INGROUPS/i',$ingroup_string) )
	{
	$all_active_ingroups = 1;
	$ingroup_SQL = "'$rawLOGadmin_viewable_groupsSQL'";
	}
else
	{
	$all_active_ingroups = 0;
	}


$stmt="select user_group, group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if (!isset($DB))   {$DB=0;}
if ($DB) {echo "$stmt\n";}
$usergroups_to_print = mysqli_num_rows($rslt);
$i=0;
$usergroups=array();
$usergroupnames=array();
$usergroups[$i]='ALL-GROUPS';
$usergroupnames[$i] = "All user groups";
$i++;
$usergroups_to_print++;
while ($i < $usergroups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$usergroups[$i] =$row[0];
	$usergroupnames[$i] =$row[1];
	$i++;
	}

$stmt="select group_id,group_name from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$ingroups_to_print = mysqli_num_rows($rslt);
$i=0;
$LISTingroups=array();
$LISTingroup_names=array();
$LISTingroups[$i]='ALL-INGROUPS';
$i++;
$ingroups_to_print++;
$ingroups_string='|';
while ($i < $ingroups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTingroups[$i] =		$row[0];
	$LISTingroup_names[$i] =	$row[1];
	$ingroups_string .= "$LISTingroups[$i]|";
	$i++;
	}


$stmt="select * from vicidial_queue_groups where active='Y' $LOGallowed_queue_groupsSQL order by queue_group, queue_group_name;";
$rslt=mysql_to_mysqli($stmt, $link);
# echo $stmt;
if ($DB) {$MAIN.="$stmt\n";}
$queue_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$LISTqueue_groups=array();
$LISTqueue_group_names=array();
$LISTqueue_groups[$i]='ALL-INGROUPS';
$i++;
$queue_groups_to_print++;
$queue_groups_string='|';
$queue_groups_dropdown="";
while ($i < $queue_groups_to_print)
	{
	$row=mysqli_fetch_array($rslt);
	$LISTqueue_groups[$i] =		$row["queue_group"];
	$LISTqueue_group_names[$i] =	$row["queue_group_name"];
	$queue_groups_string .= "$LISTqueue_groups[$i]|";
	$queue_groups_dropdown .= "\t<option data-value='$row[queue_group]' value='$row[queue_group_name]'/>\n";
	$i++;

	if (!$default_queue_group)  {$default_queue_group=$row["queue_group_name"];}
	}

# require("screen_colors.php");

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$NFB = '<b><font size=6 face="courier">';
$NFE = '</font></b>';
$F=''; $FG=''; $B=''; $BG='';

?>

<HTML>
<HEAD>
<link rel="stylesheet" type="text/css" href="VERM_stylesheet.php">
<script src="jquery.min.js"></script>
<script language="JavaScript" src="calendar_db.js"></script>
<script language="JavaScript" src="help.js"></script>
<script language="JavaScript" src="VERM_custom_form_functions.php"></script>
<link rel="stylesheet" href="calendar.css">

<?php
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>"._QXZ("$report_name")." - "._QXZ("Custom report form")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;
	$ADMIN="/$SSadmin_web_directory/admin.php";
	require("../$SSadmin_web_directory/admin_header.php");
?>

<h2 class='admin_header'><?php echo _QXZ("Custom report analysis"); ?>:</h2>

<form name='VERM_custom_report' action="VERM_main_report_page.php" method="get">
<table id='admin_table' style='width:900px'>
<tr>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("BACK TO HOME"); ?>' onClick="window.location.href='VERM_admin.php'"></th>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("RUN CUSTOM REPORT"); ?>' onClick='GoToCustomReport()'></th>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("REALTIME REPORT"); ?>' onClick="GoToCustomReport('realtime')"></th>
	<th width='25%'><input type='button' class='refreshButton' value='&#8635;' onClick="javascript:document.forms[0].reset(); document.getElementById('start_date').value='<?php echo $NOW_DAY; ?>'; document.getElementById('end_date').value='<?php echo $NOW_DAY; ?>'"></th>
</tr>
<tr>
	<td colspan='4'>

	<table style='width:900px' align='center'>
		<tr>
			<th><h2 class='admin_sub_header'><?php echo _QXZ("Report Details"); ?>:</h2></th>
			<th>&nbsp;</th>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Queue"); ?>: </td>
			<td align='left'>
<?php
			echo "	<input list='VERM_report_queues' type='text' size='40' maxlength='255' id='vicidial_queue_groups' name='vicidial_queue_groups' class='VERM_form_field' value='$vicidial_queue_groups'>\n"; 
			echo "	<datalist id=\"VERM_report_queues\" name=\"VERM_report_queues\">";
			echo $queue_groups_dropdown;
			echo "	</datalist>";
			echo "$NWB#VERM_custom_report_queue$NWE";
?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Report"); ?>: </td>
			<td align='left'>
<?php
			echo "	<input list='VERM_reports' type='text' id='report_types' name='report_types' class='VERM_form_field' value='$report_types'>\n"; 
			echo "	<datalist id='VERM_reports'>\n";
			echo "	<option data-value='' value='' />\n";
			echo "	<option data-value='ANSWERED' value='"._QXZ("ANSWERED")."' name='ANSWERED'/>\n";
			echo "	<option data-value='ANSWERED_DT' value='"._QXZ("ANS.DT.")."' name='ANS.DT.'/>\n";
			echo "	<option data-value='UNANSWERED' value='"._QXZ("UNANS").".' name='UNANS.'/>\n";
			echo "	<option data-value='UNANSWERED_DT' value='"._QXZ("UNANS.DT.")."' name='UNANS.DT.'/>\n";
			echo "	<option data-value='IVR' value='"._QXZ("IVR")."' name='IVR'/>\n";
			echo "	<option data-value='AREA' value='"._QXZ("AREA")."' name='AREA'/>\n";
			echo "	<option data-value='ATT' value='"._QXZ("ATT.")."' name='ATT.'/>\n";
			echo "	<option data-value='DAY' value='"._QXZ("DAY")."' name='DAY'/>\n";
			echo "	<option data-value='HOUR' value='"._QXZ("HR.")."' name='HR.'/>\n";
			echo "	<option data-value='DOW' value='"._QXZ("DOW")."' name='DOW'/>\n";
			echo "	<option data-value='AGENTS' value='"._QXZ("AGENTS")."' name='AGENTS'/>\n";
			echo "	<option data-value='AGENTS_DT' value='"._QXZ("AG.DT.")."' name='AG.DT.'/>\n";
			echo "	<option data-value='DISPO' value='"._QXZ("OUTCOMES")."' name='OUTCOMES'/>\n";
			echo "	</datalist>\n";
			echo "$NWB#VERM_custom_report_report$NWE";
?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Time period"); ?>: </td>
			<td align='left'>
<?php
			echo "	<input list='VERM_time_period' type='text' id='time_period' name='time_period' class='VERM_form_field' value='$time_period'>\n"; 
			echo "	<datalist id='VERM_time_period'>\n";
			echo "	<option data-value='TODAY' value='"._QXZ("Today")."'/>\n";
			echo "	<option data-value='YESTERDAY value='"._QXZ("Yesterday")."'/>\n";
			echo "	<option data-value='DAYBEFORE' value='"._QXZ("Day before yesterday")."'/>\n";
			echo "	<option data-value='LAST24HOURS' value='"._QXZ("Last 24 hours")."'/>\n";
			echo "	<option data-value='LAST7DAYS' value='"._QXZ("Last 7 days")."'/>\n";
			echo "	<option data-value='LAST30DAYS' value='"._QXZ("Last 30 days")."'/>\n";
			echo "	<option data-value='LAST90DAYS' value='"._QXZ("Last 90 days")."'/>\n";
			echo "	</datalist>\n";
			echo "$NWB#VERM_custom_report_time_period$NWE";
?>
			</td>
		</tr>

		<tr>
			<td align='right'><?php echo _QXZ("Call start date"); ?>:</td>
			<td align='left'>
			<INPUT TYPE=TEXT id='start_date' NAME='start_date' SIZE=10 MAXLENGTH=10 VALUE="<?php echo (!$start_date ? $NOW_DAY : $start_date); ?>" class='VERM_form_field'>
			<script language="JavaScript">
			var o_cal = new tcal ({
				// form name
				'formname': 'VERM_custom_report',
				// input name
				'controlname': 'start_date'
			});
			o_cal.a_tpl.yearscroll = false;
			</script>&nbsp;&nbsp;
			<INPUT list='start_time_hour_list' onclick="this.value=''" TYPE=TEXT id='start_time_hour' NAME='start_time_hour' class='VERM_form_field VERM_numeric_field' MAXLENGTH=2 VALUE="<?php echo $start_time_hour; ?>">
			<?php
			echo "<datalist id='start_time_hour_list'>\n";
			for ($i=0; $i<=23; $i++)
				{
				echo "<option data-value='".substr("0$i", -2)."' value='".substr("0$i", -2)."'/>\n";
				}
			echo "</datalist>\n";
			?>
			:
			<INPUT list='start_time_min_list' onclick="this.value=''" TYPE=TEXT id='start_time_min' NAME='start_time_min' class='VERM_form_field VERM_numeric_field' MAXLENGTH=2 VALUE="<?php echo $start_time_min; ?>">
			<?php
			echo "<datalist id='start_time_min_list'>\n";
			for ($i=0; $i<=59; $i++)
				{
				echo "<option data-value='".substr("0$i", -2)."' value='".substr("0$i", -2)."'/>\n";
				}
			echo "</datalist>\n";
			?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Call end date"); ?>:</td>
			<td align='left'>
			<INPUT TYPE=TEXT id='end_date' NAME='end_date' onclick="this.value=''" SIZE=10 MAXLENGTH=10 VALUE="<?php echo (!$end_date ? $NOW_DAY : $end_date); ?>" class='VERM_form_field'>
			<script language="JavaScript">
			var o_cal = new tcal ({
				// form name
				'formname': 'VERM_custom_report',
				// input name
				'controlname': 'end_date'
			});
			o_cal.a_tpl.yearscroll = false;
			</script>&nbsp;&nbsp;
			<INPUT list='end_time_hour_list' onclick="this.value=''" TYPE=TEXT id='end_time_hour' NAME='end_time_hour' MAXLENGTH=2 class='VERM_form_field VERM_numeric_field' VALUE="<?php echo $end_time_hour; ?>">
			<?php
			echo "<datalist id='end_time_hour_list'>\n";
			for ($i=0; $i<=23; $i++)
				{
				echo "<option data-value='".substr("0$i", -2)."' value='".substr("0$i", -2)."'/>\n";
				}
			echo "</datalist>\n";
			?>
			:
			<INPUT list='end_time_min_list' onclick="this.value=''" TYPE=TEXT id='end_time_min' NAME='end_time_min' MAXLENGTH=2 class='VERM_form_field VERM_numeric_field' VALUE="<?php echo $end_time_min; ?>">
			<?php
			echo "<datalist id='end_time_min_list'>\n";
			for ($i=0; $i<=59; $i++)
				{
				echo "<option data-value='".substr("0$i", -2)."' value='".substr("0$i", -2)."'/>\n";
				}
			echo "</datalist>\n";
			?>
			</td>
		</tr>
		<tr>
			<th><h2 class='admin_sub_header'><?php echo _QXZ("Preferences"); ?></h2></th>
			<th>&nbsp;</th>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Hourly slot (minutes)"); ?>: </td>
			<td align='left'>
<!--
			<input type='number' list='hourly_slot_list' id='hourly_slot' name='hourly_slot' class='VERM_form_field VERM_numeric_field' size='4' maxlength='3' value='30'>
			<datalist id='hourly_slot_list'>
//-->
			<select id='hourly_slot' name='hourly_slot' class='VERM_form_field VERM_numeric_field'>
			<option value='1'<?php echo ($hourly_slot==1 ? " selected" : ""); ?>>1</option>
			<option value='2'<?php echo ($hourly_slot==2 ? " selected" : ""); ?>>2</option>
			<option value='5'<?php echo ($hourly_slot==5 ? " selected" : ""); ?>>5</option>
			<option value='10'<?php echo ($hourly_slot==10 ? " selected" : ""); ?>>10</option>
			<option value='15'<?php echo ($hourly_slot==15 ? " selected" : ""); ?>>15</option>
			<option value='20'<?php echo ($hourly_slot==20 ? " selected" : ""); ?>>20</option>
			<option value='30'<?php echo (!$hourly_slot || $hourly_slot==30 ? " selected" : ""); ?>>30</option>
			<option value='60'<?php echo ($hourly_slot==60 ? " selected" : ""); ?>>60</option>
			<option value='90'<?php echo ($hourly_slot==90 ? " selected" : ""); ?>>90</option>
			<option value='120'<?php echo ($hourly_slot==120 ? " selected" : ""); ?>>120</option>
			<option value='240'<?php echo ($hourly_slot==240 ? " selected" : ""); ?>>240</option>
			<option value='480'<?php echo ($hourly_slot==480 ? " selected" : ""); ?>>480</option>
			</select>
			<?php echo "$NWB#VERM_custom_report_hourly_slot$NWE"; ?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("SLA initial period"); ?>: </td>
			<td align='left'><input type='number' id='SLA_initial_period' name='SLA_initial_period' class='VERM_form_field VERM_numeric_field' maxlength='3' value='<?php echo (!$SLA_initial_period ? "20" : $SLA_initial_period); ?>'><?php echo "$NWB#VERM_custom_report_SLA_initial_period$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("SLA initial interval"); ?>: </td>
			<td align='left'><input type='number' id='SLA_initial_interval' name='SLA_initial_interval' class='VERM_form_field VERM_numeric_field' maxlength='3' value='<?php echo (!$SLA_initial_interval ? "5" : $SLA_initial_interval); ?>'><?php echo "$NWB#VERM_custom_report_SLA_initial_interval$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("SLA max period"); ?>: </td>
			<td align='left'><input type='number' id='SLA_max_period' name='SLA_max_period' class='VERM_form_field VERM_numeric_field' maxlength='3' value='<?php echo (!$SLA_max_period ? "120" : $SLA_max_period); ?>''><?php echo "$NWB#VERM_custom_report_SLA_max_period$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("SLA interval"); ?>: </td>
			<td align='left'><input type='number' id='SLA_interval' name='SLA_interval' class='VERM_form_field VERM_numeric_field' maxlength='3' value='<?php echo (!$SLA_interval ? "10" : $SLA_interval); ?>'><?php echo "$NWB#VERM_custom_report_SLA_interval$NWE"; ?></td>
		</tr>

<!--
Removed 5/19/22
		<tr>
			<th><h2 class='admin_sub_header'>Short Calls</h2></th>
			<th>&nbsp;</th>
		</tr>
		<tr>
			<td align='right'>Short Call Wait Limit:</td>
			<td align='left'>
			<input list='short_call_wait_limit_list' type='text' name='short_call_wait_limit'  class='VERM_form_field VERM_numeric_field' maxlength='3' id='short_call_wait_limit'>
<?php
			echo "<datalist id='short_call_wait_limit_list'>\n";
			for ($i=0; $i<=29; $i++)
				{
				echo "<option data-value='$i' value='$i'/>\n";
				}
			for ($i=30; $i<=115; $i+=5)
				{
				echo "<option data-value='$i' value='$i'/>\n";
				}
			echo "</datalist>\n";
?>
			</td>
		</tr>
		<tr>
			<td align='right'>Short Call Talk Limit:</td>
			<td align='left'>
			<input list='short_call_talk_limit_list' type='text' name='short_call_talk_limit'  class='VERM_form_field VERM_numeric_field' maxlength='3' id='short_call_talk_limit'>
<?php
			echo "<datalist id='short_call_talk_limit_list'>\n";
			for ($i=0; $i<=29; $i++)
				{
				echo "<option data-value='$i' value='$i'/>\n";
				}
			for ($i=30; $i<=115; $i+=5)
				{
				echo "<option data-value='$i' value='$i'/>\n";
				}
			echo "</datalist>\n";
?>
			</td>
		</tr>
		<tr>
			<td align='right'>Short Attempt Wait Limit:</td>
			<td align='left'>
			<input list='short_attempt_wait_limit_list' type='text' name='short_attempt_wait_limit'  class='VERM_form_field VERM_numeric_field' maxlength='3' id='short_attempt_wait_limit'>
<?php
			echo "<datalist id='short_attempt_wait_limit_list'>\n";
			for ($i=0; $i<=29; $i++)
				{
				echo "<option data-value='$i' value='$i'/>\n";
				}
			for ($i=30; $i<=115; $i+=5)
				{
				echo "<option data-value='$i' value='$i'/>\n";
				}
			echo "</datalist>\n";
?>
			</td>
		</tr>
//-->

		<tr>
			<th><h2 class='admin_sub_header'><?php echo _QXZ("Call filtering criteria"); ?></h2></th>
			<th>&nbsp;</th>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Agent"); ?>: </td>
			<td align='left'>
			<input list='agent_filter_list' type='text' name='users' id='users' class='VERM_form_field' VALUE="<?php echo $users; ?>">
<?php
			echo "<datalist id='agent_filter_list'>\n";
			$user_stmt="select user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by full_name";
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			while ($user_row=mysqli_fetch_array($user_rslt))
				{
				echo "<option data-value='$user_row[user]' value='$user_row[full_name] ($user_row[user])'/>\n";
				}
			echo "</datalist>\n";
			echo "$NWB#VERM_custom_report_agent$NWE";
?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Supervisor/Team"); ?>: </td>
			<td align='left'>
			<input list='team_filter_list' type='text' name='teams' id='teams' class='VERM_form_field' VALUE="<?php echo $teams; ?>">
<?php
			echo "<datalist id='team_filter_list'>\n";
			$team_stmt="select distinct user_group_two from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user_group_two";
			$team_rslt=mysql_to_mysqli($team_stmt, $link);
			while ($team_row=mysqli_fetch_array($team_rslt))
				{
				echo "<option data-value='$team_row[user_group_two]' value='$team_row[user_group_two]'/>\n";
				}
			echo "</datalist>\n";
			echo "$NWB#VERM_custom_report_team$NWE";
?>
			</td>
		</tr>		<tr>
			<td align='right'><?php echo _QXZ("Location"); ?>: </td>
			<td align='left'>
			<input list='location_filter_list' type='text' name='location' id='location' class='VERM_form_field' VALUE="<?php echo $location; ?>">
<?php
			echo "<datalist id='location_filter_list'>\n";
			$user_stmt="select distinct user_location from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user_location";
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			while ($user_row=mysqli_fetch_array($user_rslt))
				{
				echo "<option data-value='$user_row[user_location]' value='$user_row[user_location]'/>\n";
				}
			echo "</datalist>\n";
			echo "$NWB#VERM_custom_report_location$NWE";
?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Agent Group"); ?>: </td>
			<td align='left'>
			<input list='user_group_filter_list' type='text' name='user_group' id='user_group' class='VERM_form_field' VALUE="<?php echo $user_group; ?>">
<?php
			echo "<datalist id='user_group_filter_list'>\n";
			$ug_stmt="select user_group, group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by group_name";
			$ug_rslt=mysql_to_mysqli($ug_stmt, $link);
			while ($ug_row=mysqli_fetch_array($ug_rslt))
				{
				echo "<option data-value='$ug_row[user_group]' value='$ug_row[group_name]'/>\n";
				}
			echo "</datalist>\n";
			echo "$NWB#VERM_custom_report_agent_group$NWE";
?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Outcome"); ?>: </td>
			<td align='left'>
			<input list='statuses_filter_list' type='text' name='statuses' id='statuses' class='VERM_form_field' VALUE="<?php echo $statuses; ?>">
<?php
			echo "<datalist id='statuses_filter_list'>\n";
			$status_stmt="select distinct status, status_name from vicidial_campaign_statuses $whereLOGallowed_campaignsSQL order by status_name";
			$status_rslt=mysql_to_mysqli($status_stmt, $link);
			$unique_statuses=array();
			while ($status_row=mysqli_fetch_array($status_rslt))
				{
				$status_row["status_name"]=($status_names["$status_row[status]"] ? $status_names["$status_row[status]"] : $status_row["status_name"]);
				$status_string=trim("$status_row[status] - $status_row[status_name]");
				if (!in_array($status_string, $unique_statuses))
					{
					echo "<option data-value='$status_row[status]' value='$status_row[status] - $status_row[status_name]'/>\n";
					array_push($unique_statuses, $status_string);							
					}
				}
			echo "</datalist>\n";
			echo "$NWB#VERM_custom_report_outcome$NWE";
?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Asterisk call-id"); ?>: </td>
			<td align='left'><input type='text' name='asterisk_cid' id='asterisk_cid' class='VERM_form_field' VALUE="<?php echo $asterisk_cid; ?>"><?php echo "$NWB#VERM_custom_report_asterisk_CID$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Caller"); ?>: </td>
			<td align='left'><input type='text' name='phone_number' id='phone_number' class='VERM_form_field' VALUE="<?php echo $phone_number; ?>"><?php echo "$NWB#VERM_custom_report_caller$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Wait duration"); ?>: </td>
			<td align='left'><?php echo _QXZ("Between"); ?> <input type='number' name='wait_sec_min' id='wait_sec_min' class='VERM_form_field VERM_numeric_field' maxlength='3' VALUE="<?php echo $wait_sec_min; ?>"> <?php echo _QXZ("and"); ?> <input type='number' name='wait_sec_max' id='wait_sec_max' class='VERM_form_field VERM_numeric_field' maxlength='3' VALUE="<?php echo $wait_sec_max; ?>"> <?php echo _QXZ("seconds"); ?><?php echo "$NWB#VERM_custom_report_wait_duration$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Call duration"); ?>: </td>
			<td align='left'><?php echo _QXZ("Between"); ?> <input type='number' name='length_in_sec_min' id='length_in_sec_min' class='VERM_form_field VERM_numeric_field' maxlength='3' VALUE="<?php echo $length_in_sec_min; ?>"> <?php echo _QXZ("and"); ?> <input type='number' name='length_in_sec_max' id='length_in_sec_max' class='VERM_form_field VERM_numeric_field' maxlength='3' VALUE="<?php echo $length_in_sec_max; ?>"> <?php echo _QXZ("seconds"); ?><?php echo "$NWB#VERM_custom_report_call_duration$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Disconnection cause"); ?>: </td>
			<td align='left'><input list='disconnection_cause_list' type='text' name='disconnection_cause' id='disconnection_cause' class='VERM_form_field' VALUE="<?php echo $disconnection_cause; ?>">
			<datalist id='disconnection_cause_list'>
			<option data-value='NONE' value='<?php echo _QXZ("NONE"); ?>'/>
			<option data-value='ABANDON' value='<?php echo _QXZ("ABANDON"); ?>'/>
			<option data-value='ACFILTER' value='<?php echo _QXZ("ACFILTER"); ?>'/>
			<option data-value='AFTERHOURS' value='<?php echo _QXZ("AFTERHOURS"); ?>'/>
			<option data-value='AGENT' value='<?php echo _QXZ("AGENT"); ?>'/>
			<option data-value='CALLER' value='<?php echo _QXZ("CALLER"); ?>'/>
			<option data-value='CLOSETIME' value='<?php echo _QXZ("CLOSETIME"); ?>'/>
			<option data-value='HOLDRECALLXFER' value='<?php echo _QXZ("HOLDRECALLXFER"); ?>'/>
			<option data-value='HOLDTIME' value='<?php echo _QXZ("HOLDTIME"); ?>'/>
			<option data-value='MAXCALLS' value='<?php echo _QXZ("MAXCALLS"); ?>'/>
			<option data-value='NOAGENT' value='<?php echo _QXZ("NOAGENT"); ?>'/>
			<option data-value='QUEUETIMEOUT' value='<?php echo _QXZ("QUEUETIMEOUT"); ?>'/>
			<option data-value='SYSTEM' value='<?php echo _QXZ("SYSTEM"); ?>'/>
			</datalist>
			<?php echo "$NWB#VERM_custom_report_disconnection$NWE"; ?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Enter position"); ?>: </td>
			<td align='left'><?php echo _QXZ("Between"); ?> <input type='number' name='queue_position_min' id='queue_position_min' class='VERM_form_field VERM_numeric_field' maxlength='3' VALUE="<?php echo $queue_position_min; ?>"> <?php echo _QXZ("and"); ?> <input type='number' name='queue_position_max' id='queue_position_max' class='VERM_form_field VERM_numeric_field' maxlength='3' VALUE="<?php echo $queue_position_max; ?>"><?php echo "$NWB#VERM_custom_report_enter_position$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Number of attempts (call count)"); ?>: </td>
			<td align='left'><?php echo _QXZ("Between"); ?> <input type='number' name='call_count_min' id='call_count_min' class='VERM_form_field VERM_numeric_field' maxlength='3' VALUE="<?php echo $call_count_min; ?>"> <?php echo _QXZ("and"); ?> <input type='text' name='call_count_max' id='call_count_max' maxlength='3' class='VERM_form_field VERM_numeric_field' VALUE="<?php echo $call_count_max; ?>"><?php echo "$NWB#VERM_custom_report_attempts$NWE"; ?></td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("DID"); ?>: </td>
			<td align='left'><input type='text' list='did_list' name='did' id='did' class='VERM_form_field' VALUE="<?php echo $did; ?>">
<?php
			echo "			<datalist id='did_list'>\n";
			$stmt="SELECT did_id,did_pattern,did_description from vicidial_inbound_dids where did_pattern!='did_system_filter' $LOGadmin_viewable_groupsSQL order by did_pattern asc;";
			$rslt=mysql_to_mysqli($stmt, $link);
			while($did_row = mysqli_fetch_array($rslt))
				{
				echo "<option data-value='".$did_row["did_id"]."' value='".$did_row["did_pattern"]." - ".$did_row["did_description"]."'/>\n";
				}
			echo "$NWB#VERM_custom_report_DID$NWE";
?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("IVR choice"); ?>: </td>
			<td align='left'><input type='text' name='ivr_choice' id='ivr_choice' class='VERM_form_field' VALUE="<?php echo $ivr_choice; ?>"><?php echo "$NWB#VERM_custom_report_IVR_choice$NWE"; ?></td>
		</tr>
<?php
if ($LOGuser_level==9) {
?>
		<tr>
			<td align='right'><?php echo _QXZ("Server"); ?>: </td>
			<td align='left'>
			<input list='server_list' type='text' name='server' id='server' class='VERM_form_field' VALUE="<?php echo $server; ?>">
<?php
			echo "<datalist id='server_list'>\n";
			$server_stmt="select server_id, server_ip from servers order by server_id asc";
			$server_rslt=mysql_to_mysqli($server_stmt, $link);
			while ($server_row=mysqli_fetch_array($server_rslt))
				{
				echo "<option data-value='".$server_row["server_ip"]."' value='".$server_row["server_id"]." - ".$server_row["server_ip"]."'/>\n";
				}
			echo "</datalist>\n";
			echo "$NWB#VERM_custom_report_server$NWE";
?>
			</td>
		</tr>
<?php 
} 
?>
		<tr>
			<th><h2 class='admin_sub_header'><?php echo _QXZ("Non-contiguous time"); ?>:</h2></th>
			<th>&nbsp;</th>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Day of week"); ?>: </td>
			<td align='left'>
			<select list='dow_list' type='text' size='8' multiple name='dow[]' id='dow[]' class='VERM_form_field'>
			<option value='ALL'<?php echo (in_array("ALL", $dow) ? " selected" : ""); ?>><?php echo _QXZ("ALL"); ?></option>
			<option value='1'<?php echo (in_array("1", $dow) ? " selected" : ""); ?>><?php echo _QXZ("Sunday"); ?></option>
			<option value='2'<?php echo (in_array("2", $dow) ? " selected" : ""); ?>><?php echo _QXZ("Monday"); ?></option>
			<option value='3'<?php echo (in_array("3", $dow) ? " selected" : ""); ?>><?php echo _QXZ("Tuesday"); ?></option>
			<option value='4'<?php echo (in_array("4", $dow) ? " selected" : ""); ?>><?php echo _QXZ("Wednesday"); ?></option>
			<option value='5'<?php echo (in_array("5", $dow) ? " selected" : ""); ?>><?php echo _QXZ("Thursday"); ?></option>
			<option value='6'<?php echo (in_array("6", $dow) ? " selected" : ""); ?>><?php echo _QXZ("Friday"); ?></option>
			<option value='7'<?php echo (in_array("7", $dow) ? " selected" : ""); ?>><?php echo _QXZ("Saturday"); ?></option>
			</select>
			<?php echo "$NWB#VERM_custom_report_day_of_week$NWE"; ?>
			</td>
		</tr>
		<tr>
			<td align='right'><?php echo _QXZ("Between"); ?>  </td>
			<td align='left'><input type='time' name='time_of_day_start' id='time_of_day_start' class='VERM_form_field' VALUE="<?php echo $time_of_day_start; ?>"> <?php echo _QXZ("and"); ?> <input type='time' name='time_of_day_end' id='time_of_day_end' class='VERM_form_field' VALUE="<?php echo $time_of_day_end; ?>"><?php echo "$NWB#VERM_custom_report_between$NWE"; ?></td>
		</tr>
	</table>

	</td>
</tr>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("BACK TO HOME"); ?>' onClick="window.location.href='VERM_admin.php'"></th>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("RUN CUSTOM REPORT"); ?>' onClick='GoToCustomReport()'></th>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("REALTIME REPORT"); ?>' onClick="GoToCustomReport('realtime')"></th>
	<th width='25%'><input type='button' class='refreshButton' value='&#8635;' onClick="javascript:document.forms[0].reset(); document.getElementById('start_date').value='<?php echo $NOW_DAY; ?>'; document.getElementById('end_date').value='<?php echo $NOW_DAY; ?>'"></th>
</table>
</form>

</BODY>
</HTML>
