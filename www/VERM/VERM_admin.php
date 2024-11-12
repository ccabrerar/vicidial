<?php
# VERM_admin.php - Vicidial Enhanced Reporting administration page
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1609 - First build
# 240801-1130 - Code updates for PHP8 compatibility
#


$startMS = microtime();

$version = '2.14-873';
$build = '230127-1750';

$report_name = 'VERM Reports';

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");
require("VERM_options.php"); # Added cuz I'm sick of keeping track.

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

if (file_exists('options.php'))
	{
	require('options.php');
	}

#### CUSTOM REPORT VARIABLES ####
if (isset($_GET["vicidial_queue_groups"]))			{$vicidial_queue_groups=$_GET["vicidial_queue_groups"];}
	elseif (isset($_POST["vicidial_queue_groups"]))	{$vicidial_queue_groups=$_POST["vicidial_queue_groups"];}
if (isset($_GET["report_types_to_display"]))			{$report_types_to_display=$_GET["report_types_to_display"];}
	elseif (isset($_POST["report_types_to_display"]))	{$report_types_to_display=$_POST["report_types_to_display"];}
if (isset($_GET["start_date"]))			{$start_date=$_GET["start_date"];}
	elseif (isset($_POST["start_date"]))	{$start_date=$_POST["start_date"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}

### Force hard-coded variables
# $report_display_type='LIMITED';
# $ingroup_filter=array('_STAY','TEST_IN2','TEST_IN3');

if ( (strlen($group)>1) and (strlen($groups[0])<1) ) {$groups[0] = $group;}
else {$group = $groups[0];}
if (!is_array($groups)) {$groups=array();}
if (!is_array($user_group_filter)) {$user_group_filter=array();}
if (!is_array($ingroup_filter)) {$ingroup_filter=array();}

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

$start_date=preg_replace('/[^-0-9]/', '', $start_date);
$end_date=preg_replace('/[^-0-9]/', '', $end_date);

if ($non_latin < 1)
	{
	$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9a-zA-Z]/','',$group);
	$vicidial_queue_groups = preg_replace('/[^-_0-9a-zA-Z]/','',$vicidial_queue_groups);
	$user_group_filter = preg_replace('/[^-_0-9a-zA-Z]/','',$user_group_filter);
	$ingroup_filter = preg_replace('/[^-_0-9a-zA-Z]/','',$ingroup_filter);
	$report_types_to_display = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$report_types_to_display);
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
	$report_types_to_display = preg_replace('/[^\s\-_0-9\p{L}]/u','',$report_types_to_display);
	}

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

	$LOGallowed_queue_groupsSQL='';
	if ( (!preg_match('/\-ALL\-GROUPS\-/i',$LOGallowed_queue_groups)) and (strlen($LOGallowed_queue_groups) > 3) )
		{
		$rawLOGallowed_queue_groupsSQL = preg_replace("/ \-/",'',$LOGallowed_queue_groups);
		$rawLOGallowed_queue_groupsSQL = preg_replace("/ /","','",$rawLOGallowed_queue_groupsSQL);
		$LOGallowed_queue_groupsSQL = "and queue_group IN('---ALL---','$rawLOGallowed_queue_groupsSQL')";
		$whereLOGallowed_queue_groupsSQL = "where queue_group IN('---ALL---','$rawLOGallowed_queue_groupsSQL')";
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


if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|"._QXZ("$report_name")."|\n";
    exit;
	}


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

$NFB = '<b><font size=6 face="courier">';
$NFE = '</font></b>';
$F=''; $FG=''; $B=''; $BG='';


$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";


?>

<HTML>
<HEAD>
<script language="Javascript">
function LaunchReport(time_period, agent_flag)
	{
	if (!time_period) {return false;}
	
	var full_report_var_str="";

	var report_queues_value=$( "input[type=text][id=vicidial_queue_groups]" ).val();
	if (report_queues_value!="") 
		{
		var vicidial_queue_groups=$('#VERM_report_queues [value="' + report_queues_value + '"]').data('value');
		full_report_var_str+="&vicidial_queue_groups="+vicidial_queue_groups;
		}
	else
		{
		vicidial_queue_groups="<?php echo $VERM_default_report_queue; ?>";
		full_report_var_str+="&vicidial_queue_groups="+vicidial_queue_groups;
		}

	if (agent_flag)
		{
		full_report_var_str+="&report_types_to_display=All+reports";
		full_report_var_str+="&users="+agent_flag;
		}
	else
		{
		var report_types_to_display_value=$( "input[type=text][id=report_types_to_display]" ).val();
		if (report_types_to_display_value!="") 
			{
			var report_type=$('#VERM_reports [value="' + report_types_to_display_value + '"]').data('value');
			full_report_var_str+="&report_types_to_display="+report_type;
			}
		else
			{
			full_report_var_str+="&report_types_to_display=All+reports";
			}
		}

	full_report_var_str+="&time_period="+time_period;

/*
	if (start_date) {full_report_var_str+="&start_date="+start_date;}

	var start_time_hour="";
	var start_time_min="";
	if (start_time)
		{
		start_time_array=start_time.split(":");
		start_time_hour=start_time_array[0];
		start_time_min=start_time_array[1];
		}
	if (start_time_hour) {full_report_var_str+="&start_time_hour="+start_time_hour;}
	if (start_time_min) {full_report_var_str+="&start_time_min="+start_time_min;}

	if (end_date) {full_report_var_str+="&end_date="+end_date;}

	var end_time_hour="";
	var end_time_min="";
	if (end_time)
		{
		end_time_array=end_time.split(":");
		end_time_hour=end_time_array[0];
		end_time_min=end_time_array[1];
		}
	if (end_time_hour) {full_report_var_str+="&end_time_hour="+end_time_hour;}
	if (end_time_min) {full_report_var_str+="&end_time_min="+end_time_min;}
*/

	document.location.href=document.forms[0].action+"?"+full_report_var_str;
	// alert(document.forms[0].action+"?"+full_report_var_str);
	}
function LaunchWallboard(wallboard_id)
	{
	var report_queues_value=$( "input[type=text][id=vicidial_queue_groups]" ).val();
	if (report_queues_value=="") 
		{
		var vicidial_queue_groups="<?php echo $VERM_default_report_queue; ?>";
		}
	else
		{
		var vicidial_queue_groups=$('#VERM_report_queues [value="' + report_queues_value + '"]').data('value');
		}
	document.location.href="VERM_wallboards.php?action=run&wallboard_user=<?php echo $PHP_AUTH_USER; ?>&wallboard_id=main&vicidial_queue_group="+vicidial_queue_groups;
	}
</script>
<link rel="stylesheet" type="text/css" href="VERM_stylesheet.php">
<script src="jquery.min.js"></script>
<script language="JavaScript" src="help.js"></script>

<?php
$stmt = "select count(*) from vicidial_campaigns where active='Y' and campaign_allow_inbound='Y' $group_SQLand;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);
$campaign_allow_inbound = $row[0];

echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;
	$ADMIN="/$SSadmin_web_directory/admin.php";
	require("../$SSadmin_web_directory/admin_header.php");

	if (!$vicidial_queue_groups) {$vicidial_queue_groups=$VERM_default_report_queue;}
	if (!$report_types_to_display) {$report_types_to_display='All reports';}

	if (!$selected_report_type)
		{
		echo "<form action='VERM_main_report_page.php' method='POST'>";
		echo "<TABLE style='width:".($LOGuser_level==9 ? "1000" : "800")."px' id='admin_table' CELLPADDING=4 CELLSPACING=0><TR><TD>";
		echo "<TR>";
		echo "<TH colspan='3'>";
			echo "<table style='width:".($LOGuser_level==9 ? "1000" : "800")."px' align='left' border='0' cellpadding='2'>";

			echo "<tr>";
			echo "<td colspan='6' align='right' valign='middle' class='standard_font_small'><B>$PHP_AUTH_USER</B> | $LOGfull_name<BR><BR>";
			echo "<a class='header_link' href='javascript:window.location.reload()'>"._QXZ("RELOAD")." &#8635;</a> | <a class='header_link' href='javascript:if(window.print)window.print()'>"._QXZ("PRINT")." <img src='./images/print_icon.png' width='15' height='15'></a> | <a class='header_link' href='VERM_admin.php?start_date=$start_date&end_date=$end_date'>"._QXZ("NEW REPORT")."</a> | <a class='header_link' href='/".$SSadmin_web_directory."/admin.php?force_logout=1'>"._QXZ("LOG OUT")."</a>";
			echo "</td>\n";
			echo "</tr>";
			
			echo "<tr class='export_row'>";
			echo "<td align='right'>"._QXZ("Queue")."</td>";
			echo "<td align='left' width='320' nowrap>";
			echo "	<input list='VERM_report_queues' type='text' size='40' maxlength='255' class='VERM_form_field' id='vicidial_queue_groups' name='vicidial_queue_groups' onClick=\"javascript:this.value=''\" value=''>\n"; # $vicidial_queue_groups
			echo "	<datalist id=\"VERM_report_queues\" name=\"VERM_report_queues\">";
			echo $queue_groups_dropdown;
			echo "	</datalist>";
			echo "$NWB#VERM_admin-queue$NWE";
			echo "</td>";
			echo "<td align='right'>"._QXZ("Report").":</td>";
			echo "<td align='left' width='220' nowrap>";
#			echo "	<select id=\"report_types\" name=\"report_types\">";
			echo "	<input list='VERM_reports' type='text' class='VERM_form_field' id='report_types_to_display' name='report_types_to_display' value='$report_types_to_display' onMouseover=\"javascript:this.value=''\">\n"; #  onMouseover=\"javascript:this.value=''\"
			echo "	<datalist id='VERM_reports'>\n";
			echo "	<option data-value='All reports' value='"._QXZ("All reports")."'/>\n";
			echo "	<option data-value='Quick agents report' value='"._QXZ("Quick agents report")."'/>\n";
			echo "	<option data-value='Quick reports' value='"._QXZ("Quick reports")."'/>\n";
			echo "	</datalist>\n";
			echo "$NWB#VERM_admin-report$NWE";
#			echo "	</select>";
			echo "</td>";
			/*
			echo "<td align='right'>Supervision:</td>";
			echo "<td align='left'>";
			echo "	<select id=\"report_supervision\" name=\"report_supervision\">";
			echo "	<option value=''></option>\n";
			echo "	<option value='Y'>Yes</option>\n";
			echo "	<option value='N'>No</option>\n";
			echo "	</select>";
			echo "</td>";
			*/
			echo "<td align='left' valign='middle' width='160px'>";
			echo "<input type='button' class='actButton' value='-- "._QXZ("RUN REPORT")." -->' onClick=\"LaunchReport('LAST7DAYS')\">"; 
			echo "</td>";
			echo "<td align='left' valign='middle'>";
			# echo "<img src='images/refresh.png' border='0' onClick='javascript:window.reload()'>"; #input type='button' class='refreshButton' value='&#8635;'
			echo "<input type='button' class='refreshButton' value='&#8635;' onClick='javascript:document.forms[0].reset()'>";
			echo "</td>";
			echo "</tr>";
			echo "</table>";
		echo "</tH>";
		echo "</TR>";
		echo "<TR>";
		echo "<TD width='".($LOGuser_level==9 ? "33" : "50")."%' valign='top' class='admin_column'>";
			echo "<font class='rpt_header'>"._QXZ("Agent report")."$NWB#VERM_admin-agent$NWE</font><BR>";
			echo "	"._QXZ("Filtered for agent").":<BR><select id=\"users\" name=\"users\" class='VERM_form_field'>";
			echo "<option value=''> - </option>";
			$user_stmt="select user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by full_name";
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			while ($user_row=mysqli_fetch_array($user_rslt))
				{
				echo "<option value='$user_row[user]'>$user_row[full_name]</option>";
				}
			echo "	</select>";

			echo "<ul>";
			echo "<li><a class='report_link' onClick=\"LaunchReport('TODAY', document.getElementById('users').value)\">"._QXZ("Today")."</a></li>"; # href='VERM_main_report_page.php?report_type=AGENTS&time_period=TODAY'
			echo "<li><a class='report_link' onClick=\"LaunchReport('YESTERDAY', document.getElementById('users').value)\">"._QXZ("Yesterday")."</a></li>"; # href='VERM_main_report_page.php?report_type=AGENTS&start_date=".$YESTERDAY."&start_time=00:00:00&end_date=".$YESTERDAY."&end_time=23:59:59'
			echo "<li><a class='report_link' onClick=\"LaunchReport('DAYBEFORE', document.getElementById('users').value)\">"._QXZ("Day before yesterday")."</a></li>"; # href='VERM_main_report_page.php?report_type=AGENTS&start_date=".$DAY_BEFORE_YESTERDAY."&start_time=00:00:00&end_date=".$DAY_BEFORE_YESTERDAY."&end_time=23:59:59'
			echo "<li><a class='report_link' onClick=\"LaunchReport('LAST24HOURS', document.getElementById('users').value)\">"._QXZ("Last 24 hours")."</a></li>"; # href='VERM_main_report_page.php?report_type=AGENTS&start_date=".$YESTERDAY."&end_date=".$TODAY."'
			echo "<li><a class='report_link' onClick=\"LaunchReport('LAST7DAYS', document.getElementById('users').value)\">"._QXZ("Last 7 days")."</a></li>"; # href='VERM_main_report_page.php?report_type=AGENTS&start_date=".$SEVEN_DAYS."&end_date=".$TODAY."'
			echo "<li><a class='report_link' onClick=\"LaunchReport('LAST30DAYS', document.getElementById('users').value)\">"._QXZ("Last 30 days")."</a></li>"; # href='VERM_main_report_page.php?report_type=AGENTS&start_date=".$THIRTY_DAYS."&end_date=".$TODAY."'
			echo "<li><a class='report_link' onClick=\"LaunchReport('LAST90DAYS', document.getElementById('users').value)\">"._QXZ("Last 90 days")."</a></li>"; # href='VERM_main_report_page.php?report_type=AGENTS&start_date=".$NINETY_DAYS."&end_date=".$TODAY."'
			echo "</ul><BR><BR>";

			if ( ($LOGuser_level >= 9) and ( (preg_match("/VERM QA link/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) ) )
				{
				echo "<font class='rpt_header'>"._QXZ("Quality Assessment")."$NWB#VERM_admin-qa$NWE</font><BR>";
				echo "<a class='report_link' onClick=\"LaunchWallboard('main')\">"._QXZ("Start wallboard")."</a><BR><BR>";
				}
		echo "</TD>";
		echo "<TD width='".($LOGuser_level>=9 ? "34" : "50")."' valign='top' class='admin_column'>";
			echo "<font class='rpt_header'>"._QXZ("Real-time reports")."$NWB#VERM_admin-realtime_reports$NWE</font><BR>";
			echo "<a class='report_link' onClick=\"LaunchWallboard('main')\">"._QXZ("Start wallboard")."</a><BR>";
			echo "<a href='/".$SSadmin_web_directory."/realtime_report.php?report_display_type=HTML' class='report_link'>"._QXZ("Real-time report")."</a><BR><BR>";
			echo "<font class='rpt_header'>"._QXZ("Quick activity reports")."$NWB#VERM_admin-quick_reports$NWE</font><BR>";
			echo "<ul>";
			echo "<li><a class='report_link' onClick=\"LaunchReport('TODAY')\">"._QXZ("Today")."</a></li>"; #  href='VERM_main_report_page.php?report_type=ANSWERED&time_period=TODAY'
			echo "<li><a class='report_link' onClick=\"LaunchReport('YESTERDAY')\">"._QXZ("Yesterday")."</a></li>";
			echo "<li><a class='report_link' onClick=\"LaunchReport('DAYBEFORE')\">"._QXZ("Day before yesterday")."</a></li>"; #  href='VERM_main_report_page.php?report_type=ANSWERED&time_period=DAYBEFORE'
			echo "<li><a class='report_link' onClick=\"LaunchReport('LAST24HOURS')\">"._QXZ("Last 24 hours")."</a></li>"; # href='VERM_main_report_page.php?report_type=ANSWERED&time_period=LAST24HOURS'
			echo "<li><a class='report_link' onClick=\"LaunchReport('LAST7DAYS')\">"._QXZ("Last 7 days")."</a></li>"; # href='VERM_main_report_page.php?report_type=ANSWERED&time_period=LAST7DAYS'
			echo "<li><a class='report_link' onClick=\"LaunchReport('LAST30DAYS')\">"._QXZ("Last 30 days")."</a></li>"; # href='VERM_main_report_page.php?report_type=ANSWERED&time_period=LAST30DAYS'
			echo "<li><a class='report_link' onClick=\"LaunchReport('LAST90DAYS')\">"._QXZ("Last 90 days")."</a></li>"; # href='VERM_main_report_page.php?report_type=ANSWERED&time_period=LAST90DAYS'
			echo "</ul><BR><BR>";
			echo "<font class='rpt_header'>"._QXZ("Custom report")."$NWB#VERM_admin-custom_report$NWE</font><BR>";
			echo "<a href='VERM_custom_report.php".($start_date && $end_date ? "?start_date=$start_date&end_date=$end_date" : "")."' class='header_link'>"._QXZ("Run custom report")."</a><BR><BR>";
			echo "<font class='rpt_header'>"._QXZ("Wallboards")."$NWB#VERM_admin-wallboards$NWE</font><BR>";
			#$wb_stmt=
			# echo "<a href='VERM_wallboards.php?action=run&wallboard_user=".$PHP_AUTH_USER."&wallboard_id=' class='header_link'>Wallboard</a><BR><BR>";
			echo "<a class='report_link' onClick=\"LaunchWallboard('main')\">"._QXZ("Wallboard")."</a><BR><BR>";

		echo "</TD>";
	if ($LOGuser_level>=9)
		{
		echo "<TD width='33%' valign='top' class='admin_column'>";
			echo "<font class='rpt_header'>"._QXZ("Settings").":$NWB#VERM_admin-settings$NWE</font><BR>";
			echo "<ul>";
			echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=0A'>"._QXZ("Users")."</a></li>";
			echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=198000000000'>"._QXZ("Queues")."</a></li>";
			echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=100000'>"._QXZ("User groups")."</a></li>";
			echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=392111111111&container_id=USER_LOCATIONS_SYSTEM'>"._QXZ("Locations")."</a></li>";
			echo "<li>"._QXZ("Statuses/Outcomes")."";
			echo "<ul>";
				echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=32'>"._QXZ("Campaign")."</a></li>";
				echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=321111111111111'>"._QXZ("System")."</a></li>";
			echo "</ul>";
			echo "</li>";
			echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=37'>"._QXZ("Pause codes")."</a></li>";
			echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=1500'>"._QXZ("IVR/Call menus")."</a></li>";
			echo "<li><a class='report_link' href='/".$SSadmin_web_directory."/admin.php?ADD=1300'>"._QXZ("DID/DNIS lines")."</a></li>";
			echo "</ul>";
		echo "</TD>";
		}
		echo "</TR>";
		echo "</TABLE>";
		echo "</form>";
		}
if ($db_source == 'S')
	{
	mysqli_close($link);
	$use_slave_server=0;
	$db_source = 'M';
	require("dbconnect_mysqli.php");
	}

$endMS = microtime();
$startMSary = explode(" ",$startMS);
$endMSary = explode(" ",$endMS);
$runS = ($endMSary[0] - $startMSary[0]);
$runM = ($endMSary[1] - $startMSary[1]);
$TOTALrun = ($runS + $runM);

# $stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);


?>
</TD></TR></TABLE>
</FORM>
</BODY>
<script language="Javascript">
/*

$('vicidial_queue_groups').on('click', function() {
  $(this).val('');
});
$('vicidial_queue_groups').on('mouseleave', function() {
  if ($(this).val() == '') {
    $(this).val('apple');
  }
});
$('vicidial_queue_groups').click(function() {
   $('vicidial_queue_groups').click();
});
*/
</script>
</HTML>
