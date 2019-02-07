<?php 
# AST_timeonVDADall.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# live real-time stats for the VICIDIAL Auto-Dialer all servers
#
# STOP=4000, SLOW=40, GO=4 seconds refresh interval
# 
# CHANGELOG:
# 50406-0920 - Added Paused agents < 1 min
# 51130-1218 - Modified layout and info to show all servers in a vicidial system
# 60421-1043 - check GET/POST vars lines with isset to not trigger PHP NOTICES
# 60511-1343 - Added leads and drop info at the top of the screen
# 60608-1539 - Fixed CLOSER tallies for active calls
# 60619-1658 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 60626-1453 - Added display of system load to bottom (Angelito Manansala)
# 60901-1123 - Changed display elements at the top of the screen
# 60905-1342 - Fixed non INCALL|QUEUE timer column
# 61002-1642 - Added TRUNK SHORT/FILL stats
# 61101-1318 - Added SIP and IAX Listen and Barge links option
# 61101-1647 - Added Usergroup column and user name option as well as sorting
# 61102-1155 - Made display of columns more modular, added ability to hide server info
# 61215-1131 - Added answered calls and drop percent taken from answered calls
# 70111-1600 - Added ability to use BLEND/INBND/*_C/*_B/*_I as closer campaigns
# 70123-1151 - Added non_latin options for substr in display variables, thanks Marin Blu
# 70206-1140 - Added call-type statuses to display(A-Auto, M-Manual, I-Inbound/Closer)
# 70619-1339 - Added Status Category tally display
# 71029-1900 - Changed CLOSER-type to not require campaign_id restriction
# 80227-0418 - Added priority to waiting calls display
# 80311-1550 - Added calls_today on all agents and wait time/in-group for inbound calls
# 80422-0033 - Added phonediaplay option, allow for toggle-sorting on sortable fields
# 80422-1001 - Fixed sort by phone login
# 80424-0515 - Added non_latin lookup from system_settings
# 80525-1040 - Added IVR status display and summary for inbound calls
# 80619-2047 - Added DISPO status for post-call-work while paused
# 80704-0543 - Added DEAD status for agents INCALL with no live call
# 80822-1222 - Added option for display of customer phone number
# 81011-0335 - Fixed remote agent display bug
# 81022-1500 - Added inbound call stats display option
# 81029-1023 - Changed drop percent calculation for multi-stat reports
# 81029-1706 - Added pause code display if enabled per campaign
# 81108-2337 - Added inbound-only section
# 90105-1153 - Changed monitor links to use 0 prefix instead of 6
# 90202-0108 - Changed options to pop-out frame, added outbound_autodial_active option
# 90310-0906 - Added admin header
# 90428-0727 - Changed listen and barge to use the API and manager must enter phone
# 90508-0623 - Changed to PHP long tags
# 90518-0930 - Fixed $CALLSdisplay static assignment bug for some links(bug #210)
# 90524-2231 - Changed to use functions.php for seconds to HH:MM:SS conversion
# 90602-0405 - Added list mix display in statuses and order if active
# 90603-1845 - Fixed color coding bug
# 90627-0608 - Some Formatting changes, added in-group name display
# 90701-0657 - Fixed inbound=No calculation issues
# 90808-0212 - Fixed inbound only non-ALL bug, changed times to use agent last_state_change
# 90907-0915 - Added PARK status
# 90914-1154 - Added AgentOnly display column to waiting calls section
# 91102-2013 - Changed in-group color styles for incoming calls waiting
# 91204-1548 - Added ability to change agent in-groups and blended
# 100214-1127 - Added no-dialable-leads alert and in-groups stats option
# 100301-1229 - Added 3-WAY status for consultative transfer agents
# 100303-0930 - Added carrier stats display option
# 100424-0943 - Added realtime_block_user_info option
# 100709-1054 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation and allowed campaigns restrictions
# 100805-0704 - Fixed minor bug in campaigns restrictions
# 100815-0002 - Added optional display of preset dials if presets are enabled in the campaign
# 100912-0839 - Changed several stats to limit to 2 or 3 decimal spaces
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 101024-0832 - Added Agent time stats option and agents-in-dispo counter
# 101109-1448 - Added Auto Hopper Level display (MikeC)
# 101216-1358 - Added functions to work with new realtime_report.php script
# 110218-1037 - Fixed query that was causing load spikes on systems with millions of log entries
# 110303-2125 - Added agent on-hook phone indication and RING status and color
# 110314-1735 - Fixed another query that was causing load spikes on systems with millions of log entries
# 111103-1220 - Added admin_hide_phone_data and admin_hide_lead_data options
# 120223-1934 - Added user group options
# 120612-2150 - Added percentages to counts for carrier stats and TOTAL line to carrier display stats as well
# 121222-2151 - Added email status
# 130214-1323 - Added link to in-group selected users report for in-queue inbound calls
# 130424-1357 - Fixed issue with pause codes display
# 130610-0905 - Finalized changing of all ereg instances to preg
# 130620-2303 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2008 - Changed to mysqli PHP functions
# 131120-1543 - Fixed small display bug when customer phone view is enabled
# 140213-1705 - Fixed division by zero bug
# 140328-0006 - Converted division calculations to use MathZDC function
# 140624-1424 - Added droppedOFtotal options.php option
# 140918-1614 - Added QXZ function formatting of output
# 141128-0857 - Code cleanup for QXZ functions
# 141230-0030 - Added code for on-the-fly language translations display
# 150211-2342 - Added hopper link, issue #825
# 150307-0825 - small cosmetic fix
# 150804-0956 - Added WHISPER option agent monitoring
# 150919-0238 - Added display for chats in queue
# 150925-2223 - Added User option to hide users from real-time report
# 160104-1059 - Added detection of dead chat sessions
# 160227-1031 - Added INGROUPcolorOVERRIDE option
# 160327-1259 - Added report_display_type option and several design changes
# 160331-1947 - Fix for non-pausecode display in HTML format
# 160406-1858 - Added WALL options for report_display_type
# 160413-2004 - Added WALL_4 option
# 160418-2141 - Fixed issue with WALL displays
# 160515-1300 - Added UK OFCOM feature
# 160803-1901 - Fixed issue with ERROR in campaign/ingroup name
# 170321-1145 - Added pause code time limits colors
# 170409-1556 - Added IP List validation code
# 170615-0023 - Added DIAL status for manual dial agent calls that have not been answered
# 180204-1538 - Added display of LIVE Inbound Callback Queue Calls
#

$version = '2.14-96';
$build = '180204-1538';

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["server_ip"]))			{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
if (isset($_GET["RR"]))					{$RR=$_GET["RR"];}
	elseif (isset($_POST["RR"]))		{$RR=$_POST["RR"];}
if (isset($_GET["inbound"]))			{$inbound=$_GET["inbound"];}
	elseif (isset($_POST["inbound"]))	{$inbound=$_POST["inbound"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["groups"]))				{$groups=$_GET["groups"];}
	elseif (isset($_POST["groups"]))	{$groups=$_POST["groups"];}
if (isset($_GET["usergroup"]))			{$usergroup=$_GET["usergroup"];}
	elseif (isset($_POST["usergroup"]))	{$usergroup=$_POST["usergroup"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["adastats"]))			{$adastats=$_GET["adastats"];}
	elseif (isset($_POST["adastats"]))	{$adastats=$_POST["adastats"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["SIPmonitorLINK"]))				{$SIPmonitorLINK=$_GET["SIPmonitorLINK"];}
	elseif (isset($_POST["SIPmonitorLINK"]))	{$SIPmonitorLINK=$_POST["SIPmonitorLINK"];}
if (isset($_GET["IAXmonitorLINK"]))				{$IAXmonitorLINK=$_GET["IAXmonitorLINK"];}
	elseif (isset($_POST["IAXmonitorLINK"]))	{$IAXmonitorLINK=$_POST["IAXmonitorLINK"];}
if (isset($_GET["UGdisplay"]))			{$UGdisplay=$_GET["UGdisplay"];}
	elseif (isset($_POST["UGdisplay"]))	{$UGdisplay=$_POST["UGdisplay"];}
if (isset($_GET["UidORname"]))			{$UidORname=$_GET["UidORname"];}
	elseif (isset($_POST["UidORname"]))	{$UidORname=$_POST["UidORname"];}
if (isset($_GET["orderby"]))			{$orderby=$_GET["orderby"];}
	elseif (isset($_POST["orderby"]))	{$orderby=$_POST["orderby"];}
if (isset($_GET["SERVdisplay"]))			{$SERVdisplay=$_GET["SERVdisplay"];}
	elseif (isset($_POST["SERVdisplay"]))	{$SERVdisplay=$_POST["SERVdisplay"];}
if (isset($_GET["CALLSdisplay"]))			{$CALLSdisplay=$_GET["CALLSdisplay"];}
	elseif (isset($_POST["CALLSdisplay"]))	{$CALLSdisplay=$_POST["CALLSdisplay"];}
if (isset($_GET["PHONEdisplay"]))			{$PHONEdisplay=$_GET["PHONEdisplay"];}
	elseif (isset($_POST["PHONEdisplay"]))	{$PHONEdisplay=$_POST["PHONEdisplay"];}
if (isset($_GET["CUSTPHONEdisplay"]))			{$CUSTPHONEdisplay=$_GET["CUSTPHONEdisplay"];}
	elseif (isset($_POST["CUSTPHONEdisplay"]))	{$CUSTPHONEdisplay=$_POST["CUSTPHONEdisplay"];}
if (isset($_GET["NOLEADSalert"]))			{$NOLEADSalert=$_GET["NOLEADSalert"];}
	elseif (isset($_POST["NOLEADSalert"]))	{$NOLEADSalert=$_POST["NOLEADSalert"];}
if (isset($_GET["DROPINGROUPstats"]))			{$DROPINGROUPstats=$_GET["DROPINGROUPstats"];}
	elseif (isset($_POST["DROPINGROUPstats"]))	{$DROPINGROUPstats=$_POST["DROPINGROUPstats"];}
if (isset($_GET["ALLINGROUPstats"]))			{$ALLINGROUPstats=$_GET["ALLINGROUPstats"];}
	elseif (isset($_POST["ALLINGROUPstats"]))	{$ALLINGROUPstats=$_POST["ALLINGROUPstats"];}
if (isset($_GET["with_inbound"]))			{$with_inbound=$_GET["with_inbound"];}
	elseif (isset($_POST["with_inbound"]))	{$with_inbound=$_POST["with_inbound"];}
if (isset($_GET["monitor_active"]))				{$monitor_active=$_GET["monitor_active"];}
	elseif (isset($_POST["monitor_active"]))	{$monitor_active=$_POST["monitor_active"];}
if (isset($_GET["monitor_phone"]))				{$monitor_phone=$_GET["monitor_phone"];}
	elseif (isset($_POST["monitor_phone"]))		{$monitor_phone=$_POST["monitor_phone"];}
if (isset($_GET["CARRIERstats"]))			{$CARRIERstats=$_GET["CARRIERstats"];}
	elseif (isset($_POST["CARRIERstats"]))	{$CARRIERstats=$_POST["CARRIERstats"];}
if (isset($_GET["PRESETstats"]))			{$PRESETstats=$_GET["PRESETstats"];}
	elseif (isset($_POST["PRESETstats"]))	{$PRESETstats=$_POST["PRESETstats"];}
if (isset($_GET["AGENTtimeSTATS"]))				{$AGENTtimeSTATS=$_GET["AGENTtimeSTATS"];}
	elseif (isset($_POST["AGENTtimeSTATS"]))	{$AGENTtimeSTATS=$_POST["AGENTtimeSTATS"];}
if (isset($_GET["INGROUPcolorOVERRIDE"]))			{$INGROUPcolorOVERRIDE=$_GET["INGROUPcolorOVERRIDE"];}
	elseif (isset($_POST["INGROUPcolorOVERRIDE"]))	{$INGROUPcolorOVERRIDE=$_POST["INGROUPcolorOVERRIDE"];}
if (isset($_GET["RTajax"]))				{$RTajax=$_GET["RTajax"];}
	elseif (isset($_POST["RTajax"]))	{$RTajax=$_POST["RTajax"];}
if (isset($_GET["RTuser"]))				{$RTuser=$_GET["RTuser"];}
	elseif (isset($_POST["RTuser"]))	{$RTuser=$_POST["RTuser"];}
if (isset($_GET["RTpass"]))				{$RTpass=$_GET["RTpass"];}
	elseif (isset($_POST["RTpass"]))	{$RTpass=$_POST["RTpass"];}
if (isset($_GET["user_group_filter"]))				{$user_group_filter=$_GET["user_group_filter"];}
	elseif (isset($_POST["user_group_filter"]))	{$user_group_filter=$_POST["user_group_filter"];}
if (isset($_GET["droppedOFtotal"]))				{$droppedOFtotal=$_GET["droppedOFtotal"];}
	elseif (isset($_POST["droppedOFtotal"]))	{$droppedOFtotal=$_POST["droppedOFtotal"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}


$report_name = 'Real-Time Main Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,agent_whisper_enabled,allow_chats,cache_carrier_stats_realtime,report_default_format,ofcom_uk_drop_calc,enable_pause_code_limits FROM system_settings;";
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
	$allow_chats =					$row[7];
	$cache_carrier_stats_realtime = $row[8];
	$SSreport_default_format =		$row[9];
	$SSofcom_uk_drop_calc =			$row[10];
	$SSenable_pause_code_limits =	$row[11];
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

if (!isset($DB))			{$DB=0;}
if (!isset($RR))			{$RR=40;}
if (!isset($group))			{$group='ALL-ACTIVE';}
if (!isset($groups))		{$groups=array();}
if (!isset($user_group_filter))		{$user_group_filter=array();}
if (!isset($usergroup))		{$usergroup='';}
if (!isset($UGdisplay))		{$UGdisplay=0;}	# 0=no, 1=yes
if (!isset($UidORname))		{$UidORname=1;}	# 0=id, 1=name
if (!isset($orderby))		{$orderby='timeup';}
if (!isset($SERVdisplay))	{$SERVdisplay=0;}	# 0=no, 1=yes
if (!isset($CALLSdisplay))	{$CALLSdisplay=1;}	# 0=no, 1=yes
if (!isset($PHONEdisplay))	{$PHONEdisplay=0;}	# 0=no, 1=yes
if (!isset($CUSTPHONEdisplay))	{$CUSTPHONEdisplay=0;}	# 0=no, 1=yes
if (!isset($PAUSEcodes))	{$PAUSEcodes='N';}  # 0=no, 1=yes
if (!isset($with_inbound))	
	{
	if ($outbound_autodial_active > 0)
		{$with_inbound='Y';}  # N=no, Y=yes, O=only
	else
		{$with_inbound='O';}  # N=no, Y=yes, O=only
	}
$ingroup_detail='';

if ( (strlen($group)>1) and (strlen($groups[0])<1) ) {$groups[0] = $group;  $RR=40;}
else {$group = $groups[0];}

function get_server_load($windows = false) 
	{
	$os = strtolower(PHP_OS);
	if(strpos($os, "win") === false) 
		{
		if(file_exists("/proc/loadavg")) 
			{
			$load = file_get_contents("/proc/loadavg");
			$load = explode(' ', $load);
			return $load[0] . ' ' . $load[1] . ' ' . $load[2];
			}
		elseif(function_exists("shell_exec")) 
			{
			$load = explode(' ', `uptime`);
			return $load[count($load)-3] . ' ' . $load[count($load)-2] . ' ' . $load[count($load)-1];
			}
		else 
			{
		return false;
			}
		}
	elseif($windows) 
		{
		if(class_exists("COM")) 
			{
			$wmi = new COM("WinMgmts:\\\\.");
			$cpus = $wmi->InstancesOf("Win32_Processor");

			$cpuload = 0;
			$i = 0;
			while ($cpu = $cpus->Next()) 
				{
				$cpuload += $cpu->LoadPercentage;
				$i++;
				}

			$cpuload = round(MathZDC($cpuload, $i), 2);
			return "$cpuload%";
			}
		else 
			{
			return false;
			}
		}
	}

$load_ave = get_server_load(true);

$NOW_TIME = date("Y-m-d H:i:s");
$NOW_DAY = date("Y-m-d");
$NOW_HOUR = date("H:i:s");
$STARTtime = date("U");
$epochONEminuteAGO = ($STARTtime - 60);
$timeONEminuteAGO = date("Y-m-d H:i:s",$epochONEminuteAGO);
$epochFIVEminutesAGO = ($STARTtime - 300);
$timeFIVEminutesAGO = date("Y-m-d H:i:s",$epochFIVEminutesAGO);
$epochFIFTEENminutesAGO = ($STARTtime - 900);
$timeFIFTEENminutesAGO = date("Y-m-d H:i:s",$epochFIFTEENminutesAGO);
$epochONEhourAGO = ($STARTtime - 3600);
$timeONEhourAGO = date("Y-m-d H:i:s",$epochONEhourAGO);
$epochSIXhoursAGO = ($STARTtime - 21600);
$timeSIXhoursAGO = date("Y-m-d H:i:s",$epochSIXhoursAGO);
$epochTWENTYFOURhoursAGO = ($STARTtime - 86400);
$timeTWENTYFOURhoursAGO = date("Y-m-d H:i:s",$epochTWENTYFOURhoursAGO);

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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',0,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

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

$stmt="SELECT user_id,user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,shift_override_flag,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,callcard_admin,force_change_password,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,modify_contacts,modify_same_user_level from vicidial_users where user='$PHP_AUTH_USER';";
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

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

$LOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}

#  and (preg_match("/MONITOR|BARGE|HIJACK|WHISPER/",$monitor_active) ) )
if ( (!isset($monitor_phone)) or (strlen($monitor_phone)<1) )
	{
	$stmt="SELECT phone_login from vicidial_users where user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$monitor_phone = $row[0];
	}

$stmt="SELECT realtime_block_user_info,user_group,admin_hide_lead_data,admin_hide_phone_data from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$realtime_block_user_info = $row[0];
$LOGuser_group =			$row[1];
$LOGadmin_hide_lead_data =	$row[2];
$LOGadmin_hide_phone_data =	$row[3];

$stmt="SELECT allowed_campaigns,allowed_reports from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns = $row[0];
$LOGallowed_reports =	$row[1];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|"._QXZ("$report_name")."|\n";
    exit;
	}

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

$allactivecampaigns='';
$stmt="SELECT campaign_id,campaign_name from vicidial_campaigns where active='Y' $LOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
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
	$groups[$i] = preg_replace("/'|\"|\\\\|;/","",$groups[$i]);
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
	$user_group_filter[$i] = preg_replace("/'|\"|\\\\|;/","",$user_group_filter[$i]);
#	if ( (preg_match("/ $user_group_filter[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/ALL-/",$LOGallowed_campaigns)) )
#		{
		$user_group_string .= "$user_group_filter[$i]|";
		$user_group_SQL .= "'$user_group_filter[$i]',";
		$usergroupQS .= "&user_group_filter[]=$user_group_filter[$i]";
#		}
	$i++;
	}
$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);

### if no campaigns selected, display all
if ( ($group_ct < 1) or (strlen($group_string) < 2) )
	{
	$groups[0] = 'ALL-ACTIVE';
	$group_string = '|ALL-ACTIVE|';
	$group = 'ALL-ACTIVE';
	$groupQS .= "&groups[]=ALL-ACTIVE";
	}
### if no user groups selected, display all
if ( ($user_group_ct < 1) or (strlen($user_group_string) < 2) )
	{
	$user_group_filter[0] = 'ALL-GROUPS';
	$user_group_string = '|ALL-GROUPS|';
	$usergroupQS .= "&user_group_filter[]=ALL-GROUPS";
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
### USER GROUP STUFF
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


$stmt="SELECT user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if (!isset($DB))   {$DB=0;}
if ($DB) {echo "$stmt\n";}
$usergroups_to_print = mysqli_num_rows($rslt);
$i=0;
$usergroups[$i]='ALL-GROUPS';
$usergroupnames[$i] = 'All user groups';
$i++;
$usergroups_to_print++;
while ($i < $usergroups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$usergroups[$i] =$row[0];
	$i++;
	}

if (!isset($RR))   {$RR=4;}

$NFB = '<b><font size=6 face="courier">';
$NFE = '</font></b>';
$F=''; $FG=''; $B=''; $BG='';

$select_list = "<TABLE WIDTH=700 CELLPADDING=5 BGCOLOR=\"#D9E6FE\"><TR><TD VALIGN=TOP>"._QXZ("Select Campaigns").": <BR>";
$select_list .= "<SELECT SIZE=15 NAME=groups[] multiple>";
$o=0;
while ($groups_to_print > $o)
	{
	if (preg_match("/\|$LISTgroups[$o]\|/",$group_string)) 
		{$select_list .= "<option selected value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTnames[$o]</option>";}
	else
		{$select_list .= "<option value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTnames[$o]</option>";}
	$o++;
	}
$select_list .= "</SELECT>";
$select_list .= "<BR><font class=\"top_settings_val\">"._QXZ("(To select more than 1 campaign, hold down the Ctrl key and click)")."<font>";

$select_list .= "<BR><BR>"._QXZ("Select User Groups").": <BR>";
$select_list .= "<SELECT SIZE=8 NAME=user_group_filter[] ID=user_group_filter[] multiple>";
$o=0;
while ($o < $usergroups_to_print)
	{
	if (preg_match("/\|$usergroups[$o]\|/",$user_group_filter_string)) 
		{$select_list .= "<option selected value=\"$usergroups[$o]\">$usergroups[$o] - $usergroupnames[$o]</option>";}
	else
		{$select_list .= "<option value=\"$usergroups[$o]\">$usergroups[$o] - $usergroupnames[$o]</option>";}
	$o++;
	}
$select_list .= "</SELECT>";

$select_list .= "</TD><TD VALIGN=TOP ALIGN=CENTER>";
$select_list .= "<a href=\"#\" onclick=\"closeDiv(\'campaign_select_list\');\">"._QXZ("Close Panel")."</a><BR><BR>";
$select_list .= "<TABLE CELLPADDING=2 CELLSPACING=2 BORDER=0>";

$select_list .= "<TR><TD align=right>";
$select_list .= _QXZ("Inbound").":  </TD><TD align=left><SELECT SIZE=1 NAME=with_inbound>";
$select_list .= "<option value=\"N\"";
	if ($with_inbound=='N') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("No")."</option>";
$select_list .= "<option value=\"Y\"";
	if ($with_inbound=='Y') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("Yes")."</option>";
$select_list .= "<option value=\"O\"";
	if ($with_inbound=='O') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("Only")."</option>";
$select_list .= "</SELECT></TD></TR>";

$select_list .= "<TR><TD align=right>";
$select_list .= _QXZ("Monitor").":  </TD><TD align=left><SELECT SIZE=1 NAME=monitor_active>";
$select_list .= "<option value=\"\"";
	if (strlen($monitor_active) < 2) {$select_list .= " selected";} 
$select_list .= ">"._QXZ("NONE")."</option>";
$select_list .= "<option value=\"MONITOR\"";
	if ($monitor_active=='MONITOR') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("MONITOR")."</option>";
$select_list .= "<option value=\"BARGE\"";
	if ($monitor_active=='BARGE') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("BARGE")."</option>";
if ($agent_whisper_enabled == '1')
	{
	$select_list .= "<option value='WHISPER'";
		if ($monitor_active=='WHISPER') {$select_list .= " selected";} 
	$select_list .= ">"._QXZ("WHISPER")."</option>";
	}
#$select_list .= "<option value=\"HIJACK\"";
#	if ($monitor_active=='HIJACK') {$select_list .= " selected";} 
#$select_list .= ">HIJACK</option>";
$select_list .= "</SELECT></TD></TR>";

$select_list .= "<TR><TD align=right>";
$select_list .= _QXZ("Phone").":  </TD><TD align=left>";
$select_list .= "<INPUT type=text size=10 maxlength=20 NAME=monitor_phone VALUE=\"$monitor_phone\">";
$select_list .= "</TD></TR>";
$select_list .= "<TR><TD align=center COLSPAN=2> &nbsp; </TD></TR>";

if ($UGdisplay > 0)
	{
	$select_list .= "<TR><TD align=right>";
	$select_list .= _QXZ("Select User Group").":  </TD><TD align=left>";
	$select_list .= "<SELECT SIZE=1 NAME=usergroup>";
	$select_list .= "<option value=\"\">"._QXZ("ALL USER GROUPS")."</option>";
	$o=0;
	while ($usergroups_to_print > $o)
		{
		if ($usergroups[$o] == $usergroup) {$select_list .= "<option selected value=\"$usergroups[$o]\">$usergroups[$o]</option>";}
		else {$select_list .= "<option value=\"$usergroups[$o]\">$usergroups[$o]</option>";}
		$o++;
		}
	$select_list .= "</SELECT></TD></TR>";
	}

$select_list .= "<TR><TD align=right>";
$select_list .= _QXZ("Dialable Leads Alert").":  </TD><TD align=left><SELECT SIZE=1 NAME=NOLEADSalert>";
$select_list .= "<option value=\"\"";
	if (strlen($NOLEADSalert) < 2) {$select_list .= " selected";} 
$select_list .= ">"._QXZ("NO")."</option>";
$select_list .= "<option value=\"YES\"";
	if ($NOLEADSalert=='YES') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("YES")."</option>";
$select_list .= "</SELECT></TD></TR>";

$select_list .= "<TR><TD align=right>";
$select_list .= _QXZ("Show Drop In-Group Row").":  </TD><TD align=left><SELECT SIZE=1 NAME=DROPINGROUPstats>";
$select_list .= "<option value=\"0\"";
	if ($DROPINGROUPstats < 1) {$select_list .= " selected";} 
$select_list .= ">"._QXZ("NO")."</option>";
$select_list .= "<option value=\"1\"";
	if ($DROPINGROUPstats=='1') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("YES")."</option>";
$select_list .= "</SELECT></TD></TR>";

$select_list .= "<TR><TD align=right>";
$select_list .= _QXZ("Show Carrier Stats").":  </TD><TD align=left><SELECT SIZE=1 NAME=CARRIERstats>";
$select_list .= "<option value=\"0\"";
	if ($CARRIERstats < 1) {$select_list .= " selected";} 
$select_list .= ">"._QXZ("NO")."</option>";
$select_list .= "<option value=\"1\"";
	if ($CARRIERstats=='1') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("YES")."</option>";
$select_list .= "</SELECT></TD></TR>";

## find if any selected campaigns have presets enabled
$presets_enabled=0;
$stmt="SELECT count(*) from vicidial_campaigns where enable_xfer_presets='ENABLED' $group_SQLand;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$OUToutput .= "$stmt\n";}
$presets_enabled_count = mysqli_num_rows($rslt);
if ($presets_enabled_count > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$presets_enabled = $row[0];
	}
if ($presets_enabled > 0)
	{
	$select_list .= "<TR><TD align=right>";
	$select_list .= _QXZ("Show Presets Stats").":  </TD><TD align=left><SELECT SIZE=1 NAME=PRESETstats>";
	$select_list .= "<option value=\"0\"";
		if ($PRESETstats < 1) {$select_list .= " selected";} 
	$select_list .= ">"._QXZ("NO")."</option>";
	$select_list .= "<option value=\"1\"";
		if ($PRESETstats=='1') {$select_list .= " selected";} 
	$select_list .= ">"._QXZ("YES")."</option>";
	$select_list .= "</SELECT></TD></TR>";
	}

$select_list .= "<TR><TD align=right>";
$select_list .= _QXZ("Agent Time Stats").":  </TD><TD align=left><SELECT SIZE=1 NAME=AGENTtimeSTATS>";
$select_list .= "<option value=\"0\"";
	if ($AGENTtimeSTATS < 1) {$select_list .= " selected";} 
$select_list .= ">"._QXZ("NO")."</option>";
$select_list .= "<option value=\"1\"";
	if ($AGENTtimeSTATS=='1') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("YES")."</option>";
$select_list .= "</SELECT></TD></TR>";

$select_list .= "<TR><TD align=right>";
$select_list .= _QXZ("Display as").":  </TD><TD align=left><SELECT SIZE=1 NAME=report_display_type ID=report_display_type>";
$select_list .= "<option value='TEXT'";
	if ($report_display_type=='TEXT') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("TEXT")."</option>";
$select_list .= "<option value='HTML'";
	if ($report_display_type=='HTML') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("HTML")."</option>";
$select_list .= "<option value='WALL_1'";
	if ($report_display_type=='WALL_1') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("WALL_1")."</option>";
$select_list .= "<option value='WALL_2'";
	if ($report_display_type=='WALL_2') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("WALL_2")."</option>";
$select_list .= "<option value='WALL_3'";
	if ($report_display_type=='WALL_3') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("WALL_3")."</option>";
$select_list .= "<option value='WALL_4'";
	if ($report_display_type=='WALL_4') {$select_list .= " selected";} 
$select_list .= ">"._QXZ("WALL_4")."</option>";
$select_list .= "</SELECT></TD></TR>";

$select_list .= "</TABLE><BR>";
$select_list .= "<INPUT type=hidden name=droppedOFtotal value=\"$droppedOFtotal\">";
$select_list .= "<INPUT type=submit NAME=SUBMIT VALUE=SUBMIT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; ";
$select_list .= "</TD></TR>";
$select_list .= "<TR><TD ALIGN=CENTER>";
$select_list .= "<font class=\"top_settings_val\"> &nbsp; </font>";
$select_list .= "</TD>";
$select_list .= "<TD NOWRAP align=right>";
$select_list .= "<font class=\"top_settings_val\">"._QXZ("VERSION").": $version &nbsp; "._QXZ("BUILD").": $build</font>";
$select_list .= "</TD></TR></TABLE>";

$open_list = '<TABLE WIDTH=250 CELLPADDING=0 CELLSPACING=0 BGCOLOR=\'#D9E6FE\'><TR><TD ALIGN=CENTER><a href=\'#\' onclick=\\"openDiv(\'campaign_select_list\');\\"><font class=\'top_settings_val\'>'._QXZ("Choose Report Display Options").'</a></TD></TR></TABLE>';

?>

<HTML>
<HEAD>

<?php 

if ($RTajax > 0)
	{
	echo "<!-- ajax-mode -->\n";
	}
else
	{
	?>
	<script language="Javascript">

	window.onload = startup;

	// function to detect the XY position on the page of the mouse
	function startup() 
		{
		hide_ingroup_info();
		if (window.Event) 
			{
			document.captureEvents(Event.MOUSEMOVE);
			}
		document.onmousemove = getCursorXY;
		}

	function getCursorXY(e) 
		{
		document.getElementById('cursorX').value = (window.Event) ? e.pageX : event.clientX + (document.documentElement.scrollLeft ? document.documentElement.scrollLeft : document.body.scrollLeft);
		document.getElementById('cursorY').value = (window.Event) ? e.pageY : event.clientY + (document.documentElement.scrollTop ? document.documentElement.scrollTop : document.body.scrollTop);
		}

	var select_list = "<?php echo $select_list ?>";
	var open_list = "<?php echo $open_list ?>";
	var monitor_phone = '<?php echo $monitor_phone ?>';
	var user = '<?php echo $PHP_AUTH_USER ?>';
	var pass = '<?php echo $PHP_AUTH_PW ?>';

	// functions to hide and show different DIVs
	function openDiv(divvar) 
		{
		document.getElementById(divvar).innerHTML = select_list;
		document.getElementById(divvar).style.left = 0;
		}
	function closeDiv(divvar)
		{
		document.getElementById(divvar).innerHTML = open_list;
		document.getElementById(divvar).style.left = 160;
		}
	function closeAlert(divvar)
		{
		document.getElementById(divvar).innerHTML = '';
		}
	// function to launch monitoring calls

	function send_monitor(session_id,server_ip,stage)
		{
		//	alert(session_id + "|" + server_ip + "|" + monitor_phone + "|" + stage + "|" + user);
		var xmlhttp=false;
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		// JScript gives us Conditional compilation, we can cope with old IE versions.
		// and security blocked creation of the objects.
		 try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		 } catch (e) {
		  try {
		   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		  } catch (E) {
		   xmlhttp = false;
		  }
		 }
		@end @*/
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp) 
			{
			var monitorQuery = "source=realtime&function=blind_monitor&user=" + user + "&pass=" + pass + "&phone_login=" + monitor_phone + "&session_id=" + session_id + '&server_ip=' + server_ip + '&stage=' + stage;
			xmlhttp.open('POST', 'non_agent_api.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(monitorQuery); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
				//	alert(xmlhttp.responseText);
					var Xoutput = null;
					Xoutput = xmlhttp.responseText;
					var regXFerr = new RegExp("ERROR:","g");
					var regXFscs = new RegExp("SUCCESS:","g");
					if (Xoutput.match(regXFerr))
						{alert(xmlhttp.responseText);}
					if (Xoutput.match(regXFscs))
						{alert("<?php echo _QXZ("SUCCESS: calling"); ?> " + monitor_phone);}
					}
				}
			delete xmlhttp;
			}
		}

	// function to change in-groups selected for a specific agent
	function submit_ingroup_changes(temp_agent_user)
		{
		var temp_ingroup_add_remove_changeIndex = document.getElementById("ingroup_add_remove_change").selectedIndex;
		var temp_ingroup_add_remove_change =  document.getElementById('ingroup_add_remove_change').options[temp_ingroup_add_remove_changeIndex].value;

		var temp_set_as_defaultIndex = document.getElementById("set_as_default").selectedIndex;
		var temp_set_as_default =  document.getElementById('set_as_default').options[temp_set_as_defaultIndex].value;

		var temp_blendedIndex = document.getElementById("blended").selectedIndex;
		var temp_blended =  document.getElementById('blended').options[temp_blendedIndex].value;

		var temp_ingroup_choices = '';
		var txtSelectedValuesObj = document.getElementById('txtSelectedValues');
		var selectedArray = new Array();
		var selObj = document.getElementById('ingroup_new_selections');
		var i;
		var count = 0;
		for (i=0; i<selObj.options.length; i++) 
			{
			if (selObj.options[i].selected) 
				{
			//	selectedArray[count] = selObj.options[i].value;
				temp_ingroup_choices = temp_ingroup_choices + '+' + selObj.options[i].value;
				count++;
				}
			}

		temp_ingroup_choices = temp_ingroup_choices + '+-';

		//	alert(session_id + "|" + server_ip + "|" + monitor_phone + "|" + stage + "|" + user);
		var xmlhttp=false;
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		// JScript gives us Conditional compilation, we can cope with old IE versions.
		// and security blocked creation of the objects.
		 try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		 } catch (e) {
		  try {
		   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		  } catch (E) {
		   xmlhttp = false;
		  }
		 }
		@end @*/
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp) 
			{
			var changeQuery = "source=realtime&function=change_ingroups&user=" + user + "&pass=" + pass + "&agent_user=" + temp_agent_user + "&value=" + temp_ingroup_add_remove_change + '&set_as_default=' + temp_set_as_default + '&blended=' + temp_blended + '&ingroup_choices=' + temp_ingroup_choices;
			xmlhttp.open('POST', '../agc/api.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(changeQuery); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
				//	alert(changeQuery);
					var Xoutput = null;
					Xoutput = xmlhttp.responseText;
					var regXFerr = new RegExp("ERROR:","g");
					if (Xoutput.match(regXFerr))
						{alert(xmlhttp.responseText);}
					else
						{
						alert(xmlhttp.responseText);
						hide_ingroup_info();
						}
					}
				}
			delete xmlhttp;
			}
		}

	// function to display in-groups selected for a specific agent
	function ingroup_info(agent_user,count)
		{
		var cursorheight = (document.REALTIMEform.cursorY.value - 0);
		var newheight = (cursorheight + 10);
		document.getElementById("agent_ingroup_display").style.top = newheight;
		//	alert(session_id + "|" + server_ip + "|" + monitor_phone + "|" + stage + "|" + user);
		var xmlhttp=false;
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		// JScript gives us Conditional compilation, we can cope with old IE versions.
		// and security blocked creation of the objects.
		 try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		 } catch (e) {
		  try {
		   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		  } catch (E) {
		   xmlhttp = false;
		  }
		 }
		@end @*/
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp) 
			{
			var monitorQuery = "source=realtime&function=agent_ingroup_info&stage=change&user=" + user + "&pass=" + pass + "&agent_user=" + agent_user;
			xmlhttp.open('POST', 'non_agent_api.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(monitorQuery); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
				//	alert(xmlhttp.responseText);
					var Xoutput = null;
					Xoutput = xmlhttp.responseText;
					var regXFerr = new RegExp("ERROR:","g");
					if (Xoutput.match(regXFerr))
						{alert(xmlhttp.responseText);}
					else
						{
						document.getElementById("agent_ingroup_display").visibility = "visible";
						document.getElementById("agent_ingroup_display").innerHTML = Xoutput;
						}
					}
				}
			delete xmlhttp;
			}
		}

	// function to display in-groups selected for a specific agent
	function hide_ingroup_info()
		{
		document.getElementById("agent_ingroup_display").visibility = "hidden";
		document.getElementById("agent_ingroup_display").innerHTML = '';
		}



	</script>

	<STYLE type="text/css">
	<!--
		.green {color: white; background-color: green}
		.red {color: white; background-color: red}
		.lightblue {color: black; background-color: #ADD8E6}
		.blue {color: white; background-color: blue}
		.midnightblue {color: white; background-color: #191970}
		.purple {color: white; background-color: purple}
		.violet {color: black; background-color: #EE82EE} 
		.thistle {color: black; background-color: #D8BFD8} 
		.olive {color: white; background-color: #808000}
		.lime {color: white; background-color: #006600}
		.yellow {color: black; background-color: yellow}
		.khaki {color: black; background-color: #F0E68C}
		.orange {color: black; background-color: orange}
		.black {color: white; background-color: black}
		.salmon {color: white; background-color: #FA8072}
		.darkred {color: white; background-color: #990000}

		.r1 {color: black; background-color: #FFCCCC}
		.r2 {color: black; background-color: #FF9999}
		.r3 {color: black; background-color: #FF6666}
		.r4 {color: white; background-color: #FF0000}
		.b1 {color: black; background-color: #CCCCFF}
		.b2 {color: black; background-color: #9999FF}
		.b3 {color: black; background-color: #6666FF}
		.b4 {color: white; background-color: #0000FF}

		.Hfb1 {color: white; background-color: #015b91; font-family: HELVETICA; font-size: 18; font-weight: bold;}
		.Hfr1 {color: black; background-color: #FFCCCC; font-family: HELVETICA; font-size: 18; font-weight: bold;}
		.Hfr2 {color: black; background-color: #FF9999; font-family: HELVETICA; font-size: 18; font-weight: bold;}
		.Hfr3 {color: black; background-color: #FF6666; font-family: HELVETICA; font-size: 18; font-weight: bold;}
		.Hfr4 {color: white; background-color: #FF0000; font-family: HELVETICA; font-size: 18; font-weight: bold;}

	<?php
		$stmt="SELECT group_id,group_color from vicidial_inbound_groups;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$INgroups_to_print = mysqli_num_rows($rslt);
			if ($INgroups_to_print > 0)
			{
			$g=0;
			while ($g < $INgroups_to_print)
				{
				$row=mysqli_fetch_row($rslt);
				$group_id[$g] = $row[0];
				$group_color[$g] = $row[1];
				echo "   .csc$group_id[$g] {color: black; background-color: $group_color[$g]}\n";
				$g++;
				}
			}
	?>
	-->
	</STYLE>\n";

	<?php
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo"<META HTTP-EQUIV=Refresh CONTENT=\"$RR; URL=$PHP_SELF?RR=$RR&DB=$DB$groupQS&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">\n";
	echo "<TITLE>$report_name: $group</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

		$short_header=1;

		require("admin_header.php");

	}

$stmt = "SELECT count(*) from vicidial_campaigns where active='Y' and campaign_allow_inbound='Y' $group_SQLand;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);
$campaign_allow_inbound = $row[0];


if ($RTajax < 1)
	{
	echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET NAME=REALTIMEform ID=REALTIMEform>\n";
	echo "<INPUT TYPE=HIDDEN NAME=RR VALUE=\"$RR\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=cursorX ID=cursorX>\n";
	echo "<INPUT TYPE=HIDDEN NAME=cursorY ID=cursorY>\n";
	echo "<INPUT TYPE=HIDDEN NAME=adastats VALUE=\"$adastats\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=SIPmonitorLINK VALUE=\"$SIPmonitorLINK\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=IAXmonitorLINK VALUE=\"$IAXmonitorLINK\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=usergroup VALUE=\"$usergroup\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=UGdisplay VALUE=\"$UGdisplay\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=UidORname VALUE=\"$UidORname\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=orderby VALUE=\"$orderby\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=SERVdisplay VALUE=\"$SERVdisplay\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=CALLSdisplay VALUE=\"$CALLSdisplay\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=PHONEdisplay VALUE=\"$PHONEdisplay\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=CUSTPHONEdisplay VALUE=\"$CUSTPHONEdisplay\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=DROPINGROUPstats VALUE=\"$DROPINGROUPstats\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=ALLINGROUPstats VALUE=\"$ALLINGROUPstats\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=CARRIERstats VALUE=\"$CARRIERstats\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=PRESETstats VALUE=\"$PRESETstats\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=AGENTtimeSTATS VALUE=\"$AGENTtimeSTATS\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=INGROUPcolorOVERRIDE VALUE=\"$INGROUPcolorOVERRIDE\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=droppedOFtotal VALUE=\"$droppedOFtotal\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=report_display_type VALUE=\"$report_display_type\">\n";
	echo _QXZ("$report_name")." &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; \n";
	echo "<span style=\"position:absolute;left:160px;top:27px;z-index:19;\" id=campaign_select_list>\n";
	echo "<TABLE WIDTH=250 CELLPADDING=0 CELLSPACING=0 BGCOLOR=\"#D9E6FE\"><TR><TD ALIGN=CENTER>\n";
	echo "<a href=\"#\" onclick=\"openDiv('campaign_select_list');\">"._QXZ("Choose Report Display Options")."</a>";
	echo "</TD></TR></TABLE>\n";
	echo "</span>\n";
	echo "<span style=\"position:absolute;left:10px;top:120px;z-index:18;\" id=agent_ingroup_display>\n";
	echo " &nbsp; ";
	echo "</span>\n";
	echo "<a href=\"$PHP_SELF?RR=4000$groupQS&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("STOP")."</a> | ";
	echo "<a href=\"$PHP_SELF?RR=40$groupQS&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("SLOW")."</a> | ";
	echo "<a href=\"$PHP_SELF?RR=4$groupQS&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("GO")."</a>";
	if (preg_match('/ALL\-ACTIVE/i',$group_string))
		{
		echo " &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=10\">"._QXZ("MODIFY")."</a> | \n";
		}
	else
		{
		echo " &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=34&campaign_id=$group\">"._QXZ("MODIFY")."</a> | \n";
		}
	echo "<a href=\"./AST_timeonVDADallSUMMARY.php?RR=$RR&DB=$DB&adastats=$adastats\">"._QXZ("SUMMARY")."</a> </FONT>\n";
	echo "\n\n";
	}

if (!$group) 
	{echo "<BR><BR>"._QXZ("please select a campaign from the pulldown above")."</FORM>\n"; exit;}
else
{
$multi_drop=0;
### Gather list of all Closer group ids for exclusion from stats
$stmt = "SELECT group_id from vicidial_inbound_groups;";
$rslt=mysql_to_mysqli($stmt, $link);
$ingroups_to_print = mysqli_num_rows($rslt);
$c=0;
while ($ingroups_to_print > $c)
	{
	$row=mysqli_fetch_row($rslt);
	$ALLcloser_campaignsSQL .= "'$row[0]',";
	$c++;
	}
$ALLcloser_campaignsSQL = preg_replace("/,$/","",$ALLcloser_campaignsSQL);
if (strlen($ALLcloser_campaignsSQL)<2)
	{$ALLcloser_campaignsSQL="''";}
if ($DB > 0) {echo "\n|$ALLcloser_campaignsSQL|$stmt|\n";}


##### INBOUND #####
if ( ( preg_match('/Y/',$with_inbound) or preg_match('/O/',$with_inbound) ) and ($campaign_allow_inbound > 0) )
	{
	$closer_campaignsSQL = "";
	### Gather list of Closer group ids
	$stmt = "SELECT closer_campaigns from vicidial_campaigns where active='Y' $group_SQLand;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$ccamps_to_print = mysqli_num_rows($rslt);
	$c=0;
	while ($ccamps_to_print > $c)
		{
		$row=mysqli_fetch_row($rslt);
		$closer_campaigns = $row[0];
		$closer_campaigns = preg_replace("/^ | -$/","",$closer_campaigns);
		$closer_campaigns = preg_replace("/ /","','",$closer_campaigns);
		$closer_campaignsSQL .= "'$closer_campaigns',";
		$c++;
		}
	$closer_campaignsSQL = preg_replace("/,$/","",$closer_campaignsSQL);
	}
if (strlen($closer_campaignsSQL)<2)
	{$closer_campaignsSQL="''";}

if ($DB > 0) {echo "\n|$closer_campaigns|$closer_campaignsSQL|$stmt|\n";}

$answersSQL = 'sum(answers_today)';
$answers_singleSQL = 'answers_today';
$answers_text =  _QXZ("ANSWERED");
if ($droppedOFtotal > 0)
	{
	$answersSQL = 'sum(calls_today)';
	$answers_singleSQL = 'calls_today';
	$answers_text = _QXZ("TOTAL").'   ';
	}

##### SHOW IN-GROUP STATS OR INBOUND ONLY WITH VIEW-MORE ###
if ( ($ALLINGROUPstats > 0) or ( (preg_match('/O/',$with_inbound)) and ($adastats > 1) ) )
	{
	$stmtB="SELECT campaign_id from vicidial_campaign_stats where campaign_id IN ($closer_campaignsSQL) order by campaign_id;";
	if ($DB > 0) {echo "\n|$stmtB|\n";}
	$r=0;
	$rslt=mysql_to_mysqli($stmtB, $link);
	$ingroups_to_print = mysqli_num_rows($rslt);
	if ($ingroups_to_print > 0)
		{$ingroup_detail .= "<table cellpadding=0 cellspacing=0>";}
	while ($ingroups_to_print > $r)
		{
		$row=mysqli_fetch_row($rslt);
		$ARYingroupdetail[$r] =			$row[0];
		$r++;
		}

	$r=0;
	while ($ingroups_to_print > $r)
		{
		$stmtB="SELECT calls_today,drops_today,$answers_singleSQL,status_category_1,status_category_count_1,status_category_2,status_category_count_2,status_category_3,status_category_count_3,status_category_4,status_category_count_4,hold_sec_stat_one,hold_sec_stat_two,hold_sec_answer_calls,hold_sec_drop_calls,hold_sec_queue_calls,campaign_id from vicidial_campaign_stats where campaign_id='$ARYingroupdetail[$r]';";

		if ($DB > 0) {echo "\n|$stmtB|\n";}

		$rslt=mysql_to_mysqli($stmtB, $link);
		$ingroup_to_print = mysqli_num_rows($rslt);
		if ($ingroup_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$callsTODAY =				$row[0];
			$dropsTODAY =				$row[1];
			$answersTODAY =				$row[2];
			$VSCcat1 =					$row[3];
			$VSCcat1tally =				$row[4];
			$VSCcat2 =					$row[5];
			$VSCcat2tally =				$row[6];
			$VSCcat3 =					$row[7];
			$VSCcat3tally =				$row[8];
			$VSCcat4 =					$row[9];
			$VSCcat4tally =				$row[10];
			$hold_sec_stat_one =		$row[11];
			$hold_sec_stat_two =		$row[12];
			$hold_sec_answer_calls =	$row[13];
			$hold_sec_drop_calls =		$row[14];
			$hold_sec_queue_calls =		$row[15];
			$ingroupdetail =			$row[16];
			}

		$drpctTODAY = ( MathZDC($dropsTODAY, $answersTODAY) * 100);
		$drpctTODAY = round($drpctTODAY, 2);
		$drpctTODAY = sprintf("%01.2f", $drpctTODAY);

		$AVGhold_sec_queue_calls = MathZDC($hold_sec_queue_calls, $callsTODAY);
		$AVGhold_sec_queue_calls = round($AVGhold_sec_queue_calls, 0);

		$AVGhold_sec_drop_calls = MathZDC($hold_sec_drop_calls, $dropsTODAY);
		$AVGhold_sec_drop_calls = round($AVGhold_sec_drop_calls, 0);

		$PCThold_sec_stat_one = ( MathZDC($hold_sec_stat_one, $answersTODAY) * 100);
		$PCThold_sec_stat_one = round($PCThold_sec_stat_one, 2);
		$PCThold_sec_stat_one = sprintf("%01.2f", $PCThold_sec_stat_one);
		$PCThold_sec_stat_two = ( MathZDC($hold_sec_stat_two, $answersTODAY) * 100);
		$PCThold_sec_stat_two = round($PCThold_sec_stat_two, 2);
		$PCThold_sec_stat_two = sprintf("%01.2f", $PCThold_sec_stat_two);
		$AVGhold_sec_answer_calls = MathZDC($hold_sec_answer_calls, $answersTODAY);
		$AVGhold_sec_answer_calls = round($AVGhold_sec_answer_calls, 0);
		$AVG_ANSWERagent_non_pause_sec = (MathZDC($answersTODAY, $agent_non_pause_sec) * 60);
		$AVG_ANSWERagent_non_pause_sec = round($AVG_ANSWERagent_non_pause_sec, 2);
		$AVG_ANSWERagent_non_pause_sec = sprintf("%01.2f", $AVG_ANSWERagent_non_pause_sec);

		$Dicbq_stmt="SELECT count(*) from vicidial_inbound_callback_queue where icbq_status='LIVE' and group_id='$ARYingroupdetail[$r]';";
		$Dicbq_rslt=mysql_to_mysqli($Dicbq_stmt, $link);
		$Dicbq_ct = mysqli_num_rows($Dicbq_rslt);
		$Dicbq_live=0;
		if ($Dicbq_ct > 0)
			{
			$Dicbq_ARY=mysqli_fetch_row($Dicbq_rslt);
			if ($Dicbq_ARY[0] > 0)
				{
				$Dicbq_live = "<font color=red><b>$Dicbq_ARY[0]</b></font>";
				}
			}

		if (preg_match('/0$|2$|4$|6$|8$/',$r)) {$bgcolor='#E6E6E6';}
		else {$bgcolor='white';}
		$ingroup_detail .= "<TR bgcolor=\"$bgcolor\">";
		$ingroup_detail .= "<TD ALIGN=RIGHT bgcolor=white><font class='top_settings_val'> &nbsp; &nbsp; &nbsp; &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>$ingroupdetail &nbsp; </font></TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("CALLS TODAY").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $callsTODAY&nbsp; &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("TMA")." 1</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $PCThold_sec_stat_one% &nbsp; &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("Average Hold time for Answered Calls").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $AVGhold_sec_answer_calls &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'> &nbsp; "._QXZ("LIVE Inbound Callback Queue Calls").":</font><font class='top_settings_val'>&nbsp; $Dicbq_live &nbsp; </TD>";
		$ingroup_detail .= "</TR>";
		$ingroup_detail .= "<TR bgcolor=\"$bgcolor\">";
		$ingroup_detail .= "<TD ALIGN=RIGHT bgcolor=white><font class='top_settings_val'></TD><TD ALIGN=LEFT><font class='top_settings_val'></TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("DROPS TODAY").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $dropsTODAY&nbsp; &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("TMA")." 2:</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $PCThold_sec_stat_two% &nbsp; &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("Average Hold time for Dropped Calls").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $AVGhold_sec_drop_calls &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT bgcolor=white><font class='top_settings_val'></TD><TD ALIGN=LEFT><font class='top_settings_val'></TD>";
		$ingroup_detail .= "</TR>";
		$ingroup_detail .= "<TR bgcolor=\"$bgcolor\">";
		$ingroup_detail .= "<TD ALIGN=RIGHT bgcolor=white><font class='top_settings_val'></TD><TD ALIGN=LEFT><font class='top_settings_val'></TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("ANSWERS TODAY").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $answersTODAY&nbsp; &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("DROP PERCENT").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $drpctTODAY%&nbsp; &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("Average Hold time for All Calls").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $AVGhold_sec_queue_calls &nbsp; </TD>";
		$ingroup_detail .= "<TD ALIGN=RIGHT bgcolor=white><font class='top_settings_val'></TD><TD ALIGN=LEFT><font class='top_settings_val'></TD>";
		$ingroup_detail .= "</TR>";

		$r++;
		}

	if ($ingroups_to_print > 0)
		{$ingroup_detail .= "</table>";}
	}


##### DROP IN-GROUP ONLY TOTALS ROW ###
$DROPINGROUPstatsHTML='';
if ( ($DROPINGROUPstats > 0) and (!preg_match("/ALL-ACTIVE/",$group_string)) )
	{
	$DIGcampaigns='';
	$stmtB="SELECT drop_inbound_group from vicidial_campaigns where campaign_id IN($group_SQL) and drop_inbound_group NOT IN('---NONE---','');";
	if ($DB > 0) {echo "\n|$stmtB|\n";}
	$rslt=mysql_to_mysqli($stmtB, $link);
	$dig_to_print = mysqli_num_rows($rslt);
	$dtp=0;
	while ($dig_to_print > $dtp)
		{
		$row=mysqli_fetch_row($rslt);
		$DIGcampaigns .=		"'$row[0]',";
		$dtp++;
		}
	$DIGcampaigns = preg_replace("/,$/",'',$DIGcampaigns);
	if (strlen($DIGcampaigns) < 2) {$DIGcampaigns = "''";}

	$stmtB="SELECT sum(calls_today),sum(drops_today),$answersSQL from vicidial_campaign_stats where campaign_id IN($DIGcampaigns);";
	if ($DB > 0) {echo "\n|$stmtB|\n";}

	$rslt=mysql_to_mysqli($stmtB, $link);
	$row=mysqli_fetch_row($rslt);
	$callsTODAY =				$row[0];
	$dropsTODAY =				$row[1];
	$answersTODAY =				$row[2];
	$drpctTODAY = ( MathZDC($dropsTODAY, $callsTODAY) * 100);
	$drpctTODAY = round($drpctTODAY, 2);
	$drpctTODAY = sprintf("%01.2f", $drpctTODAY);

	$DROPINGROUPstatsHTML .= "<TR BGCOLOR=\"#E6E6E6\">";
	$DROPINGROUPstatsHTML .= "<TD ALIGN=RIGHT COLSPAN=2><font class='top_settings_key'>"._QXZ("DROP IN-GROUP STATS")." -</font></TD>";
	$DROPINGROUPstatsHTML .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("DROP PERCENT").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $drpctTODAY% &nbsp; &nbsp; </TD>";
	$DROPINGROUPstatsHTML .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("CALLS").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $callsTODAY &nbsp; &nbsp; </TD>";
	$DROPINGROUPstatsHTML .= "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("DROPS/ANSWERS").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $dropsTODAY / $answersTODAY &nbsp; &nbsp; </TD>";
	$DROPINGROUPstatsHTML .= "</TR>";
	}


##### CARRIER STATS TOTALS ###
$CARRIERstatsHTML='';
if ($CARRIERstats > 0)
	{
	if ($cache_carrier_stats_realtime > 0)
		{
		$stmtB="SELECT stats_html from vicidial_html_cache_stats where stats_type='carrier_stats' and stats_id='ALL';";
		if ($DB > 0) {echo "\n|$stmtB|\n";}
		$rslt=mysql_to_mysqli($stmtB, $link);
		$csh_to_print = mysqli_num_rows($rslt);
		if ($csh_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$CARRIERstatsHTML =	$row[0];
			}
		}
	else
		{
		$stmtB="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeTWENTYFOURhoursAGO\" group by dialstatus;";
		if ($DB > 0) {echo "\n|$stmtB|\n";}
		$rslt=mysql_to_mysqli($stmtB, $link);
		$car_to_print = mysqli_num_rows($rslt);
		$ctp=0;
		while ($car_to_print > $ctp)
			{
			$row=mysqli_fetch_row($rslt);
			$TFhour_status[$ctp] =	$row[0];
			$TFhour_count[$ctp] =	$row[1];
			$TFhour_total+=$row[1];
			$dialstatuses .=		"'$row[0]',";
			$ctp++;
			}
		$dialstatuses = preg_replace("/,$/",'',$dialstatuses);

		$CARRIERstatsHTML .= "<TR BGCOLOR=white><TD ALIGN=left COLSPAN=8>";
		$CARRIERstatsHTML .= "<TABLE CELLPADDING=1 CELLSPACING=1 BORDER=0 BGCOLOR=white>";
		$CARRIERstatsHTML .= "<TR BGCOLOR=\"#E6E6E6\">";
		$CARRIERstatsHTML .= "<TD ALIGN=LEFT><font class='top_settings_key'>"._QXZ("CARRIER STATS").": &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </font></TD>";
		$CARRIERstatsHTML .= "<TD ALIGN=LEFT><font class='top_settings_key'>&nbsp; "._QXZ("HANGUP STATUS")." &nbsp; </font></TD>";
		$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font class='top_settings_key'>&nbsp; "._QXZ("24 HOURS")." &nbsp; </font></TD>";
		$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font class='top_settings_key'>&nbsp; "._QXZ("6 HOURS")." &nbsp; </font></TD>";
		$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font class='top_settings_key'>&nbsp; "._QXZ("1 HOUR")." &nbsp; </font></TD>";
		$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font class='top_settings_key'>&nbsp; "._QXZ("15 MIN")." &nbsp; </font></TD>";
		$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font class='top_settings_key'>&nbsp; "._QXZ("5 MIN")." &nbsp; </font></TD>";
		$CARRIERstatsHTML .= "<TD ALIGN=CENTER><font class='top_settings_key'>&nbsp; "._QXZ("1 MIN")." &nbsp; </font></TD>";
		$CARRIERstatsHTML .= "</TR>";

		if (strlen($dialstatuses) > 1)
			{
			$stmtB="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeSIXhoursAGO\" group by dialstatus;";
			if ($DB > 0) {echo "\n|$stmtB|\n";}
			$rslt=mysql_to_mysqli($stmtB, $link);
			$scar_to_print = mysqli_num_rows($rslt);
			$print_sctp=0;
			while ($scar_to_print > $print_sctp)
				{
				$row=mysqli_fetch_row($rslt);
				$SIXhour_total+=$row[1];
				$print_ctp=0;
				while ($print_ctp < $ctp)
					{
					if ($TFhour_status[$print_ctp] == $row[0])
						{$SIXhour_count[$print_ctp] = $row[1];}
					$print_ctp++;
					}
				$print_sctp++;
				}

			$stmtB="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeONEhourAGO\" group by dialstatus;";
			if ($DB > 0) {echo "\n|$stmtB|\n";}
			$rslt=mysql_to_mysqli($stmtB, $link);
			$scar_to_print = mysqli_num_rows($rslt);
			$print_sctp=0;
			while ($scar_to_print > $print_sctp)
				{
				$row=mysqli_fetch_row($rslt);
				$ONEhour_total+=$row[1];
				$print_ctp=0;
				while ($print_ctp < $ctp)
					{
					if ($TFhour_status[$print_ctp] == $row[0])
						{$ONEhour_count[$print_ctp] = $row[1];}
					$print_ctp++;
					}
				$print_sctp++;
				}

			$stmtB="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeFIFTEENminutesAGO\" group by dialstatus;";
			if ($DB > 0) {echo "\n|$stmtB|\n";}
			$rslt=mysql_to_mysqli($stmtB, $link);
			$scar_to_print = mysqli_num_rows($rslt);
			$print_sctp=0;
			while ($scar_to_print > $print_sctp)
				{
				$row=mysqli_fetch_row($rslt);
				$FTminute_total+=$row[1];
				$print_ctp=0;
				while ($print_ctp < $ctp)
					{
					if ($TFhour_status[$print_ctp] == $row[0])
						{$FTminute_count[$print_ctp] = $row[1];}
					$print_ctp++;
					}
				$print_sctp++;
				}

			$stmtB="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeFIVEminutesAGO\" group by dialstatus;";
			if ($DB > 0) {echo "\n|$stmtB|\n";}
			$rslt=mysql_to_mysqli($stmtB, $link);
			$scar_to_print = mysqli_num_rows($rslt);
			$print_sctp=0;
			while ($scar_to_print > $print_sctp)
				{
				$row=mysqli_fetch_row($rslt);
				$FIVEminute_total+=$row[1];
				$print_ctp=0;
				while ($print_ctp < $ctp)
					{
					if ($TFhour_status[$print_ctp] == $row[0])
						{$FIVEminute_count[$print_ctp] = $row[1];}
					$print_ctp++;
					}
				$print_sctp++;
				}

			$stmtB="SELECT dialstatus,count(*) from vicidial_carrier_log where call_date >= \"$timeONEminuteAGO\" group by dialstatus;";
			if ($DB > 0) {echo "\n|$stmtB|\n";}
			$rslt=mysql_to_mysqli($stmtB, $link);
			$scar_to_print = mysqli_num_rows($rslt);
			$print_sctp=0;
			while ($scar_to_print > $print_sctp)
				{
				$row=mysqli_fetch_row($rslt);
				$ONEminute_total+=$row[1];
				$print_ctp=0;
				while ($print_ctp < $ctp)
					{
					if ($TFhour_status[$print_ctp] == $row[0])
						{$ONEminute_count[$print_ctp] = $row[1];}
					$print_ctp++;
					}
				$print_sctp++;
				}


			$print_ctp=0;
			while ($print_ctp < $ctp)
				{
				if (strlen($TFhour_count[$print_ctp])<1) {$TFhour_count[$print_ctp]=0;}
				if (strlen($SIXhour_count[$print_ctp])<1) {$SIXhour_count[$print_ctp]=0;}
				if (strlen($ONEhour_count[$print_ctp])<1) {$ONEhour_count[$print_ctp]=0;}
				if (strlen($FTminute_count[$print_ctp])<1) {$FTminute_count[$print_ctp]=0;}
				if (strlen($FIVEminute_count[$print_ctp])<1) {$FIVEminute_count[$print_ctp]=0;}
				if (strlen($ONEminute_count[$print_ctp])<1) {$ONEminute_count[$print_ctp]=0;}
				
				$TFhour_pct = (100*MathZDC($TFhour_count[$print_ctp], $TFhour_total));
				$SIXhour_pct = (100*MathZDC($SIXhour_count[$print_ctp], $SIXhour_total));
				$ONEhour_pct = (100*MathZDC($ONEhour_count[$print_ctp], $ONEhour_total));
				$TFminute_pct = (100*MathZDC($FTminute_count[$print_ctp], $FTminute_total));
				$FIVEminute_pct = (100*MathZDC($FIVEminute_count[$print_ctp], $FIVEminute_total));
				$ONEminute_pct = (100*MathZDC($ONEminute_count[$print_ctp], $ONEminute_total));

				$CARRIERstatsHTML .= "<TR>";
				$CARRIERstatsHTML .= "<TD BGCOLOR=white><font class='top_settings_val'>&nbsp;</TD>";
				$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=LEFT><font class='top_settings_val'>&nbsp; &nbsp; $TFhour_status[$print_ctp] </TD>";
				$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_val'> $TFhour_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000'>".sprintf("%01.1f", $TFhour_pct)."%</font></TD>";
				$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_val'> $SIXhour_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000'>".sprintf("%01.1f", $SIXhour_pct)."%</font></TD>";
				$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_val'> $ONEhour_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000'>".sprintf("%01.1f", $ONEhour_pct)."%</font></TD>";
				$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_val'> $FTminute_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000'>".sprintf("%01.1f", $TFminute_pct)."%</font></TD>";
				$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_val'> $FIVEminute_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000'>".sprintf("%01.1f", $FIVEminute_pct)."%</font></TD>";
				$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_val'> $ONEminute_count[$print_ctp] </font>&nbsp;<font size=1 color='#990000'>".sprintf("%01.1f", $ONEminute_pct)."%</font></TD>";
				$CARRIERstatsHTML .= "</TR>";
				$print_ctp++;
				}
			$CARRIERstatsHTML .= "<TR>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=white><font class='top_settings_val'>&nbsp;</TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=LEFT><font class='top_settings_key'>&nbsp; &nbsp; "._QXZ("TOTALS")."</font></TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_key'> ".($TFhour_total+0)."</font> </TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_key'> ".($SIXhour_total+0)."</font> </TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_key'> ".($ONEhour_total+0)."</font> </TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_key'> ".($FTminute_total+0)."</font> </TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_key'> ".($FIVEminute_total+0)."</font> </TD>";
			$CARRIERstatsHTML .= "<TD BGCOLOR=\"#E6E6E6\" ALIGN=CENTER><font class='top_settings_key'> ".($ONEminute_total+0)."</font> </TD>";
			$CARRIERstatsHTML .= "</TR>";

			}
		else
			{
			$CARRIERstatsHTML .= "<TR><TD BGCOLOR=white colspan=7><font class='top_settings_val'>"._QXZ("no log entries")."</TD></TR>";
			}
		$CARRIERstatsHTML .= "</TABLE>";
		$CARRIERstatsHTML .= "</TD></TR>";
		}
	}


##### PRESET STATS TOTALS ###
$PRESETstatsHTML='';
if ($PRESETstats > 0)
	{
	$PRESETstatsHTML .= "<TR BGCOLOR=white><TD ALIGN=left COLSPAN=8>";
	$PRESETstatsHTML .= "<TABLE CELLPADDING=1 CELLSPACING=1 BORDER=0 BGCOLOR=white>";
	$PRESETstatsHTML .= "<TR BGCOLOR=\"#E6E6E6\">";
	$PRESETstatsHTML .= "<TD ALIGN=LEFT><font class='top_settings_key'> &nbsp; "._QXZ("AGENT DIAL PRESETS").": &nbsp; </font></TD>";
	$PRESETstatsHTML .= "<TD ALIGN=LEFT><font class='top_settings_key'> &nbsp; "._QXZ("PRESET NAMES")." &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </font></TD>";
	$PRESETstatsHTML .= "<TD ALIGN=LEFT><font class='top_settings_key'> &nbsp; "._QXZ("CALLS")." &nbsp; </font></TD>";
	$PRESETstatsHTML .= "</TR>";
	$stmtB="SELECT preset_name,xfer_count from vicidial_xfer_stats where preset_name!='' and preset_name is not NULL  $group_SQLand order by preset_name;";
	if ($DB > 0) {echo "\n|$stmtB|\n";}
	$rslt=mysql_to_mysqli($stmtB, $link);
	$pre_to_print = mysqli_num_rows($rslt);
	$ctp=0;
	while ($pre_to_print > $ctp)
		{
		$row=mysqli_fetch_row($rslt);
		$PRESETstatsHTML .= "<TR>";
		$PRESETstatsHTML .= "<TD><font class='top_settings_val'> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </TD>";
		$PRESETstatsHTML .= "<TD ALIGN=LEFT BGCOLOR=\"#E6E6E6\"><font class='top_settings_key'> &nbsp; $row[0] &nbsp; </font></TD>";
		$PRESETstatsHTML .= "<TD ALIGN=RIGHT BGCOLOR=\"#E6E6E6\"><font class='top_settings_val'> &nbsp; $row[1] &nbsp; </TD>";
		$PRESETstatsHTML .= "</TR>";
		$ctp++;
		}
	if ($ctp < 1)
		{
		$PRESETstatsHTML .= "<TR><TD BGCOLOR=white colspan=2><font class='top_settings_val'>"._QXZ("no log entries")."</TD></TR>";
		}
	$PRESETstatsHTML .= "</TABLE>";
	$PRESETstatsHTML .= "</TD></TR>";
	}

#	http://server/vicidial/AST_timeonVDADall.php?&groups[]=ALL-ACTIVE&RR=4000&DB=0&adastats=&SIPmonitorLINK=&IAXmonitorLINK=&usergroup=&UGdisplay=1&UidORname=1&orderby=timeup&SERVdisplay=0&CALLSdisplay=1&PHONEdisplay=0&CUSTPHONEdisplay=0&with_inbound=Y&monitor_active=&monitor_phone=350a&ALLINGROUPstats=1&DROPINGROUPstats=0&NOLEADSalert=&CARRIERstats=1

##### INBOUND ONLY ###
if (preg_match('/O/',$with_inbound))
	{
	$multi_drop++;

	$stmt="SELECT count(*) from vicidial_campaigns where agent_pause_codes_active!='N' $group_SQLand;";

	$stmtB="SELECT sum(calls_today),sum(drops_today),$answersSQL,max(status_category_1),sum(status_category_count_1),max(status_category_2),sum(status_category_count_2),max(status_category_3),sum(status_category_count_3),max(status_category_4),sum(status_category_count_4),sum(hold_sec_stat_one),sum(hold_sec_stat_two),sum(hold_sec_answer_calls),sum(hold_sec_drop_calls),sum(hold_sec_queue_calls) from vicidial_campaign_stats where campaign_id IN ($closer_campaignsSQL);";

	if (preg_match('/ALL\-ACTIVE/i',$group_string))
		{
		$inboundSQL = "where campaign_id IN ($closer_campaignsSQL)";
		$stmtB="SELECT sum(calls_today),sum(drops_today),$answersSQL,max(status_category_1),sum(status_category_count_1),max(status_category_2),sum(status_category_count_2),max(status_category_3),sum(status_category_count_3),max(status_category_4),sum(status_category_count_4),sum(hold_sec_stat_one),sum(hold_sec_stat_two),sum(hold_sec_answer_calls),sum(hold_sec_drop_calls),sum(hold_sec_queue_calls) from vicidial_campaign_stats $inboundSQL;";
		}

	$stmtC="SELECT agent_non_pause_sec from vicidial_campaign_stats $group_SQLwhere;";


	if ($DB > 0) {echo "\n|$stmt|$stmtB|$stmtC|\n";}

	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$agent_pause_codes_active = $row[0];

	$rslt=mysql_to_mysqli($stmtC, $link);
	$row=mysqli_fetch_row($rslt);
	$agent_non_pause_sec = $row[0];

	$rslt=mysql_to_mysqli($stmtB, $link);
	$row=mysqli_fetch_row($rslt);
	$callsTODAY =				$row[0];
	$dropsTODAY =				$row[1];
	$answersTODAY =				$row[2];
	$VSCcat1 =					$row[3];
	$VSCcat1tally =				$row[4];
	$VSCcat2 =					$row[5];
	$VSCcat2tally =				$row[6];
	$VSCcat3 =					$row[7];
	$VSCcat3tally =				$row[8];
	$VSCcat4 =					$row[9];
	$VSCcat4tally =				$row[10];
	$hold_sec_stat_one =		$row[11];
	$hold_sec_stat_two =		$row[12];
	$hold_sec_answer_calls =	$row[13];
	$hold_sec_drop_calls =		$row[14];
	$hold_sec_queue_calls =		$row[15];
	$drpctTODAY = ( MathZDC($dropsTODAY, $answersTODAY) * 100);
	$drpctTODAY = round($drpctTODAY, 2);
	$drpctTODAY = sprintf("%01.2f", $drpctTODAY);

	$AVGhold_sec_queue_calls = MathZDC($hold_sec_queue_calls, $callsTODAY);
	$AVGhold_sec_queue_calls = round($AVGhold_sec_queue_calls, 0);

	$AVGhold_sec_drop_calls = MathZDC($hold_sec_drop_calls, $dropsTODAY);
	$AVGhold_sec_drop_calls = round($AVGhold_sec_drop_calls, 0);

	$PCThold_sec_stat_one = ( MathZDC($hold_sec_stat_one, $answersTODAY) * 100);
	$PCThold_sec_stat_one = round($PCThold_sec_stat_one, 2);
	$PCThold_sec_stat_one = sprintf("%01.2f", $PCThold_sec_stat_one);
	$PCThold_sec_stat_two = ( MathZDC($hold_sec_stat_two, $answersTODAY) * 100);
	$PCThold_sec_stat_two = round($PCThold_sec_stat_two, 2);
	$PCThold_sec_stat_two = sprintf("%01.2f", $PCThold_sec_stat_two);
	$AVGhold_sec_answer_calls = MathZDC($hold_sec_answer_calls, $answersTODAY);
	$AVGhold_sec_answer_calls = round($AVGhold_sec_answer_calls, 0);
	$AVG_ANSWERagent_non_pause_sec = (MathZDC($answersTODAY, $agent_non_pause_sec) * 60);
	$AVG_ANSWERagent_non_pause_sec = round($AVG_ANSWERagent_non_pause_sec, 2);
	$AVG_ANSWERagent_non_pause_sec = sprintf("%01.2f", $AVG_ANSWERagent_non_pause_sec);

	if ( ($report_display_type!='WALL_1') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
		{
		echo "<BR><table cellpadding=0 cellspacing=0><TR>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("CALLS TODAY").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $callsTODAY&nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("TMA")." 1:</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $PCThold_sec_stat_one% &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("Average Hold time for Answered Calls").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $AVGhold_sec_answer_calls &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'> "._QXZ("TIME").":</font> &nbsp; </TD><TD ALIGN=LEFT><font class='top_settings_val'> $NOW_TIME </TD>";
		echo "";
		echo "</TR>";
		echo "<TR>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("DROPS TODAY").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $dropsTODAY&nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("TMA")." 2:</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $PCThold_sec_stat_two% &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("Average Hold time for Dropped Calls").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $AVGhold_sec_drop_calls &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_val'> </TD><TD ALIGN=LEFT><font class='top_settings_val'> </TD>";
		echo "";
		echo "</TR>";
		echo "<TR>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("ANSWERS TODAY").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $answersTODAY&nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT COLSPAN=2><font class='top_settings_key'>("._QXZ("Agent non-pause time / Answers").")</font></TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("Average Hold time for All Calls").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $AVGhold_sec_queue_calls &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_val'> </TD><TD ALIGN=LEFT><font class='top_settings_val'> </TD>";
		echo "";
		echo "</TR>";
		echo "<TR>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("DROP PERCENT").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $drpctTODAY%&nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_key'>"._QXZ("PRODUCTIVITY").":</font></TD><TD ALIGN=LEFT><font class='top_settings_val'>&nbsp; $AVG_ANSWERagent_non_pause_sec &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_val'></TD><TD ALIGN=LEFT><font class='top_settings_val'></TD>";
		echo "<TD ALIGN=RIGHT><font class='top_settings_val'></TD><TD ALIGN=LEFT><font class='top_settings_val'></TD>";
		echo "";
		echo "</TR>";
		}
	else
		{echo "<BR>\n";}
	}

##### NOT INBOUND ONLY ###
else
	{
	if (preg_match('/ALL\-ACTIVE/i',$group_string))
		{
		$non_inboundSQL='';
		if (preg_match('/N/',$with_inbound))
			{$non_inboundSQL = "and campaign_id NOT IN($ALLcloser_campaignsSQL)";}
		else
			{$non_inboundSQL = "and campaign_id IN($group_SQL,$closer_campaignsSQL)";}
		$multi_drop++;
		$stmt="SELECT avg(auto_dial_level),min(dial_status_a),min(dial_status_b),min(dial_status_c),min(dial_status_d),min(dial_status_e),min(lead_order),min(lead_filter_id),sum(hopper_level),min(dial_method),avg(adaptive_maximum_level),avg(adaptive_dropped_percentage),avg(adaptive_dl_diff_target),avg(adaptive_intensity),min(available_only_ratio_tally),min(adaptive_latest_server_time),min(local_call_time),avg(dial_timeout),min(dial_statuses),max(agent_pause_codes_active),max(list_order_mix),max(auto_hopper_level),max(ofcom_uk_drop_calc) from vicidial_campaigns where active='Y' $group_SQLand;";

		$stmtB="SELECT sum(dialable_leads),sum(calls_today),sum(drops_today),avg(drops_answers_today_pct),avg(differential_onemin),avg(agents_average_onemin),sum(balance_trunk_fill),$answersSQL,max(status_category_1),sum(status_category_count_1),max(status_category_2),sum(status_category_count_2),max(status_category_3),sum(status_category_count_3),max(status_category_4),sum(status_category_count_4),sum(agent_calls_today),sum(agent_wait_today),sum(agent_custtalk_today),sum(agent_acw_today),sum(agent_pause_today),sum(agenthandled_today) from vicidial_campaign_stats where calls_today > -1 $non_inboundSQL;";

		$stmtC="SELECT count(*) from vicidial_campaigns where agent_pause_codes_active!='N' and active='Y' $group_SQLand;";
		}
	else
		{
		if ($DB > 0) {echo "\n|$with_inbound|$campaign_allow_inbound|\n";}

		if ( (preg_match('/Y/',$with_inbound)) and ($campaign_allow_inbound > 0) )
			{
			$multi_drop++;
			if ($DB) {echo "with_inbound|$with_inbound|$campaign_allow_inbound\n";}

			$stmt="SELECT auto_dial_level,dial_status_a,dial_status_b,dial_status_c,dial_status_d,dial_status_e,lead_order,lead_filter_id,hopper_level,dial_method,adaptive_maximum_level,adaptive_dropped_percentage,adaptive_dl_diff_target,adaptive_intensity,available_only_ratio_tally,adaptive_latest_server_time,local_call_time,dial_timeout,dial_statuses,agent_pause_codes_active,list_order_mix,auto_hopper_level,ofcom_uk_drop_calc from vicidial_campaigns where campaign_id IN ($group_SQL,$closer_campaignsSQL);";

			$stmtB="SELECT sum(dialable_leads),sum(calls_today),sum(drops_today),avg(drops_answers_today_pct),avg(differential_onemin),avg(agents_average_onemin),sum(balance_trunk_fill),$answersSQL,max(status_category_1),sum(status_category_count_1),max(status_category_2),sum(status_category_count_2),max(status_category_3),sum(status_category_count_3),max(status_category_4),sum(status_category_count_4),sum(agent_calls_today),sum(agent_wait_today),sum(agent_custtalk_today),sum(agent_acw_today),sum(agent_pause_today),sum(agenthandled_today) from vicidial_campaign_stats where campaign_id IN ($group_SQL,$closer_campaignsSQL);";

			$stmtC="SELECT count(*) from vicidial_campaigns where agent_pause_codes_active!='N' and active='Y' and campaign_id IN ($group_SQL,$closer_campaignsSQL);";
			}
		else
			{
			$stmt="SELECT avg(auto_dial_level),max(dial_status_a),max(dial_status_b),max(dial_status_c),max(dial_status_d),max(dial_status_e),max(lead_order),max(lead_filter_id),max(hopper_level),max(dial_method),max(adaptive_maximum_level),avg(adaptive_dropped_percentage),avg(adaptive_dl_diff_target),avg(adaptive_intensity),max(available_only_ratio_tally),max(adaptive_latest_server_time),max(local_call_time),max(dial_timeout),max(dial_statuses),max(agent_pause_codes_active),max(list_order_mix),max(auto_hopper_level),max(ofcom_uk_drop_calc) from vicidial_campaigns where campaign_id IN($group_SQL);";

			$stmtB="SELECT sum(dialable_leads),sum(calls_today),sum(drops_today),avg(drops_answers_today_pct),avg(differential_onemin),avg(agents_average_onemin),sum(balance_trunk_fill),$answersSQL,max(status_category_1),sum(status_category_count_1),max(status_category_2),sum(status_category_count_2),max(status_category_3),sum(status_category_count_3),max(status_category_4),sum(status_category_count_4),sum(agent_calls_today),sum(agent_wait_today),sum(agent_custtalk_today),sum(agent_acw_today),sum(agent_pause_today),sum(agenthandled_today) from vicidial_campaign_stats where campaign_id IN($group_SQL);";

			$stmtC="SELECT count(*) from vicidial_campaigns where agent_pause_codes_active!='N' and active='Y' and campaign_id IN($group_SQL);";
			}
		}
	if ($DB > 0) {echo "\n|$stmt|$stmtB|\n";}

	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$DIALlev =		$row[0];
	$DIALstatusA =	$row[1];
	$DIALstatusB =	$row[2];
	$DIALstatusC =	$row[3];
	$DIALstatusD =	$row[4];
	$DIALstatusE =	$row[5];
	$DIALorder =	$row[6];
	$DIALfilter =	$row[7];
	$HOPlev =		$row[8];
	$DIALmethod =	$row[9];
	$maxDIALlev =	$row[10];
	$DROPmax =		$row[11];
	$targetDIFF =	$row[12];
	$ADAintense =	$row[13];
	$ADAavailonly =	$row[14];
	$TAPERtime =	$row[15];
	$CALLtime =		$row[16];
	$DIALtimeout =	$row[17];
	$DIALstatuses =	$row[18];
	$DIALmix =		$row[20];
	$AHOPlev =      $row[21];
	$UKocDROP =		$row[22];

	$rslt=mysql_to_mysqli($stmtC, $link);
	$row=mysqli_fetch_row($rslt);
	$agent_pause_codes_active = $row[0];

	$stmt="SELECT count(*) from vicidial_hopper $group_SQLwhere;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$VDhop = $row[0];

	$rslt=mysql_to_mysqli($stmtB, $link);
	$row=mysqli_fetch_row($rslt);
	$DAleads =		$row[0];
	$callsTODAY =	$row[1];
	$dropsTODAY =	$row[2];
	$drpctTODAY =	$row[3];
	$diffONEMIN =	$row[4];
	$agentsONEMIN = $row[5];
	$balanceFILL =	$row[6];
	$answersTODAY = $row[7];
	$VSCcat1 =		$row[8];
	$VSCcat1tally = $row[9];
	$VSCcat2 =		$row[10];
	$VSCcat2tally = $row[11];
	$VSCcat3 =		$row[12];
	$VSCcat3tally = $row[13];
	$VSCcat4 =		$row[14];
	$VSCcat4tally = $row[15];
	$VSCagentcalls =	$row[16];
	$VSCagentwait =		$row[17];
	$VSCagentcust =		$row[18];
	$VSCagentacw =		$row[19];
	$VSCagentpause =	$row[20];
	$AGNTansTODAY =		$row[21];
	if ($multi_drop > 0)
		{
		if ( ($SSofcom_uk_drop_calc > 0) and ($UKocDROP == 'Y') )
			{
			$temp_AGNTansPLUSdrop = ($AGNTansTODAY + $dropsTODAY);
			$drpctTODAY = ( MathZDC($dropsTODAY, $temp_AGNTansPLUSdrop) * 100);
			$drpctTODAY = round($drpctTODAY, 2);
			$drpctTODAY = sprintf("%01.2f", $drpctTODAY);
			}
		else
			{
			$drpctTODAY = ( MathZDC($dropsTODAY, $answersTODAY) * 100);
			$drpctTODAY = round($drpctTODAY, 2);
			$drpctTODAY = sprintf("%01.2f", $drpctTODAY);
			}
		}

	$diffpctONEMIN = ( MathZDC($diffONEMIN, $agentsONEMIN) * 100);
	$diffpctONEMIN = sprintf("%01.2f", $diffpctONEMIN);

	$stmt="SELECT sum(local_trunk_shortage) from vicidial_campaign_server_stats $group_SQLwhere;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$balanceSHORT = $row[0];

	if (preg_match('/DISABLED/',$DIALmix))
		{
		$DIALstatuses = (preg_replace("/ -$|^ /","",$DIALstatuses));
		$DIALstatuses = (preg_replace('/\s/', ', ', $DIALstatuses));
		}
	else
		{
		$stmt="SELECT vcl_id from vicidial_campaigns_list_mix where status='ACTIVE' $group_SQLand limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$Lmix_to_print = mysqli_num_rows($rslt);
		if ($Lmix_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$DIALstatuses = _QXZ("List Mix").": $row[0]";
			$DIALorder =	_QXZ("List Mix").": $row[0]";
			}
		}
	$DIALlev = sprintf("%01.3f", $DIALlev);
	$agentsONEMIN = sprintf("%01.2f", $agentsONEMIN);
	$diffONEMIN = sprintf("%01.2f", $diffONEMIN);

	if ( ($report_display_type!='WALL_1') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
		{
		echo "<BR><table cellpadding=0 cellspacing=0><TR>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DIAL LEVEL").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $DIALlev&nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("TRUNK SHORT/FILL").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $balanceSHORT / $balanceFILL &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("FILTER").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $DIALfilter &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\"> "._QXZ("TIME").": &nbsp; </font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\"> $NOW_TIME </TD>";
		echo "";
		echo "</TR>";

		if ($adastats>1)
			{
			$min_link='<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=4&DB=$DB&adastats=1\"><font class=\"top_settings_val\">- min </font></a>';
			if ($RTajax > 0)
				{$min_link='';}

			echo "<TR BGCOLOR=\"#CCCCCC\">";
			echo "<TD ALIGN=RIGHT>$min_link<font class=\"top_settings_val\">&nbsp; <B>"._QXZ("MAX LEVEL").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $maxDIALlev &nbsp; </TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DROPPED MAX").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $DROPmax% &nbsp; &nbsp;</TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("TARGET DIFF").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $targetDIFF &nbsp; &nbsp; </TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("INTENSITY").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $ADAintense &nbsp; &nbsp; </TD>";
			echo "</TR>";

			echo "<TR BGCOLOR=\"#CCCCCC\">";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DIAL TIMEOUT").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $DIALtimeout &nbsp;</TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("TAPER TIME").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $TAPERtime &nbsp;</TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("LOCAL TIME").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $CALLtime &nbsp;</TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("AVAIL ONLY").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $ADAavailonly &nbsp;</TD>";
			echo "</TR>";
			}

		echo "<TR>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DIALABLE LEADS").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $DAleads &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("CALLS TODAY").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $callsTODAY &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("AVG AGENTS").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $agentsONEMIN &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DIAL METHOD").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $DIALmethod &nbsp; &nbsp; </TD>";
		echo "</TR>";

		echo "<TR>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("HOPPER")." <font class=\"top_settings_val\">( "._QXZ("min/auto")." )</font>:</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $HOPlev / $AHOPlev &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DROPPED")." / $answers_text:</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $dropsTODAY / $answersTODAY &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DL DIFF").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $diffONEMIN &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("STATUSES").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $DIALstatuses &nbsp; &nbsp; </TD>";
		echo "</TR>";

		echo "<TR>";
		if( 1 == count(explode(',' , $group_SQL)))
			{ 
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\"><a href=\"./AST_VICIDIAL_hopperlist.php?group=".str_replace("'","",$group_SQL)."\">"._QXZ("LEADS IN HOPPER")."</a>:</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $VDhop &nbsp; &nbsp; </TD>";
			}
		else 
			{
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("LEADS IN HOPPER").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $VDhop &nbsp; &nbsp; </TD>";
			}
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DROPPED PERCENT");

		if ( ($SSofcom_uk_drop_calc > 0) and ($UKocDROP == 'Y') )
			{echo "<font color=blue>(uk)</font>";}
		echo ":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; ";

		
		if ($drpctTODAY >= $DROPmax)
			{echo "<font color=red><B>$drpctTODAY%</font>";}
		else
			{echo "$drpctTODAY%";}
		echo " &nbsp; &nbsp;</font></TD>";


		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("DIFF").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $diffpctONEMIN% &nbsp; &nbsp; </TD>";
		echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("ORDER").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $DIALorder &nbsp; &nbsp; </TD>";
		echo "</TR>";

		if ($AGENTtimeSTATS>0)
			{
			$avgpauseTODAY = MathZDC($VSCagentpause, $VSCagentcalls);
			$avgpauseTODAY = round($avgpauseTODAY, 0);
			$avgpauseTODAY = sprintf("%01.0f", $avgpauseTODAY);

			$avgwaitTODAY = MathZDC($VSCagentwait, $VSCagentcalls);
			$avgwaitTODAY = round($avgwaitTODAY, 0);
			$avgwaitTODAY = sprintf("%01.0f", $avgwaitTODAY);

			$avgcustTODAY = MathZDC($VSCagentcust, $VSCagentcalls);
			$avgcustTODAY = round($avgcustTODAY, 0);
			$avgcustTODAY = sprintf("%01.0f", $avgcustTODAY);

			$avgacwTODAY = MathZDC($VSCagentacw, $VSCagentcalls);
			$avgacwTODAY = round($avgacwTODAY, 0);
			$avgacwTODAY = sprintf("%01.0f", $avgacwTODAY);

			echo "<TR>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("AGENT AVG WAIT").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $avgwaitTODAY &nbsp;</TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("AVG CUSTTIME").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $avgcustTODAY &nbsp;</TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("AVG ACW").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $avgacwTODAY &nbsp;</TD>";
			echo "<TD ALIGN=RIGHT><font class=\"top_settings_key\">"._QXZ("AVG PAUSE").":</font></TD><TD ALIGN=LEFT><font class=\"top_settings_val\">&nbsp; $avgpauseTODAY &nbsp;</TD>";
			echo "</TR>";
			}

		echo "$DROPINGROUPstatsHTML\n";
		echo "$CARRIERstatsHTML\n";
		echo "$PRESETstatsHTML\n";
		}
	else
		{echo "<BR>\n";}
	}

if ( ($report_display_type!='WALL_1') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
	{
	echo "<TR>";
	echo "<TD ALIGN=LEFT COLSPAN=8>";
	if ( (!preg_match('/NULL/i',$VSCcat1)) and (strlen($VSCcat1)>0) )
		{echo "<font class=\"top_settings_key\">$VSCcat1:</font><font class=\"top_settings_val\"> &nbsp; $VSCcat1tally &nbsp;  &nbsp;  &nbsp; </font>\n";}
	if ( (!preg_match('/NULL/i',$VSCcat2)) and (strlen($VSCcat2)>0) )
		{echo "<font class=\"top_settings_key\">$VSCcat2:</font><font class=\"top_settings_val\"> &nbsp; $VSCcat2tally &nbsp;  &nbsp;  &nbsp; </font>\n";}
	if ( (!preg_match('/NULL/i',$VSCcat3)) and (strlen($VSCcat3)>0) )
		{echo "<font class=\"top_settings_key\">$VSCcat3:</font><font class=\"top_settings_val\"> &nbsp; $VSCcat3tally &nbsp;  &nbsp;  &nbsp; </font>\n";}
	if ( (!preg_match('/NULL/i',$VSCcat4)) and (strlen($VSCcat4)>0) )
		{echo "<font class=\"top_settings_key\">$VSCcat4:</font><font class=\"top_settings_val\"> &nbsp; $VSCcat4tally &nbsp;  &nbsp;  &nbsp; </font>\n";}
	echo "</TD></TR>";
	echo "<TR>";
	echo "<TD ALIGN=LEFT COLSPAN=8>";

	echo "$ingroup_detail";

	if ($RTajax < 1)
		{
		if ($adastats<2)
			{
			echo "<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=2&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">+ "._QXZ("VIEW MORE")."</font></a>";
			}
		else
			{
			echo "<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=1&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">- "._QXZ("VIEW LESS")."</font></a>";
			}
		if ($UGdisplay>0)
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=0&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("HIDE USER GROUP")."</font></a>";
			}
		else
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=1&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("VIEW USER GROUP")."</font></a>";
			}
		if ($SERVdisplay>0)
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=0&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("HIDE SERVER INFO")."</font></a>";
			}
		else
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=1&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("SHOW SERVER INFO")."</font></a>";
			}
		if ($CALLSdisplay>0)
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=0&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("HIDE WAITING CALLS")."</font></a>";
			}
		else
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=1&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("SHOW WAITING CALLS")."</font></a>";
			}

		if ($ALLINGROUPstats>0)
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=0&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("HIDE IN-GROUP STATS")."</font></a>";
			}
		else
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=1&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("SHOW IN-GROUP STATS")."</font></a>";
			}
		if ($PHONEdisplay>0)
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=0&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("HIDE PHONES")."</font></a>";
			}
		else
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=1&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("SHOW PHONES")."</font></a>";
			}
		if ($CUSTPHONEdisplay>0)
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=0&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("HIDE CUSTPHONES")."</font></a>";
			}
		else
			{
			echo " &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=1&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class=\"top_settings_val\">"._QXZ("SHOW CUSTPHONES")."</font></a>";
			}
		}

	echo "</TD>";
	echo "</TR>";
	echo "</TABLE>";
	echo "</FORM>\n\n";
	}

##### check for campaigns with no dialable leads if enabled #####
if ( ($with_inbound != 'O') and ($NOLEADSalert == 'YES') )
	{
	$NDLcampaigns='';
	$stmtB="SELECT campaign_id from vicidial_campaign_stats where campaign_id IN($group_SQL) and dialable_leads < 1 order by campaign_id;";
	if ($DB > 0) {echo "\n|$stmt|$stmtB|\n";}
	$rslt=mysql_to_mysqli($stmtB, $link);
	$campaigns_to_print = mysqli_num_rows($rslt);
	$ctp=0;
	while ($campaigns_to_print > $ctp)
		{
		$row=mysqli_fetch_row($rslt);
		$NDLcampaigns .=		" <a href=\"./admin.php?ADD=34&campaign_id=$row[0]\">$row[0]</a> &nbsp; ";
		$ctp++;
		if (preg_match("/0$|5$/",$ctp))
			{$NDLcampaigns .= "<BR>";}
		}
	if ($ctp > 0)
		{
		echo "<span style=\"position:absolute;left:0px;top:47px;z-index:15;\" id=no_dialable_leads_span>\n";
		echo "<TABLE WIDTH=700 CELLPADDING=0 CELLSPACING=0 BGCOLOR=\"#E9E6EE\"><TR><TD ALIGN=CENTER>\n";
		echo "<BR><BR><BR><BR><a href=\"#\" onclick=\"closeAlert('no_dialable_leads_span');\">"._QXZ("Close Alert")."</a>";
		echo "<BR><BR><BR><BR><BR><b>"._QXZ("Campaigns with no dialable leads").":<BR><BR>$NDLcampaigns<b><BR>";
		echo "<BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR> &nbsp; ";
		echo "</TD></TR></TABLE>\n";
		echo "</span>\n";
		}
	}
}



###################################################################################
###### INBOUND/OUTBOUND CALLS
###################################################################################
if ($campaign_allow_inbound > 0)
	{
	if (preg_match('/ALL\-ACTIVE/i',$group_string)) 
		{
		$stmt="SELECT closer_campaigns from vicidial_campaigns where active='Y' $group_SQLand";
		$rslt=mysql_to_mysqli($stmt, $link);
		$closer_campaigns="";
		while ($row=mysqli_fetch_row($rslt)) 
			{
			$closer_campaigns.="$row[0]";
			}
		$closer_campaigns = preg_replace("/^ | -$/","",$closer_campaigns);
		$closer_campaigns = preg_replace("/ - /"," ",$closer_campaigns);
		$closer_campaigns = preg_replace("/ /","','",$closer_campaigns);
		$closer_campaignsSQL = "'$closer_campaigns'";
		}	
	$stmtB="from vicidial_auto_calls where status NOT IN('XFER') and ( (call_type='IN' and campaign_id IN($closer_campaignsSQL)) or (call_type IN('OUT','OUTBALANCE') $group_SQLand) ) order by queue_priority desc,campaign_id,call_time;";
	}
else
	{
	$stmtB="from vicidial_auto_calls where status NOT IN('XFER') $group_SQLand order by queue_priority desc,campaign_id,call_time;";
	}


if ($CALLSdisplay > 0)
	{
	$stmtA = "SELECT status,campaign_id,phone_number,server_ip,UNIX_TIMESTAMP(call_time),call_type,queue_priority,agent_only";

	## JCJ Display chats
	if ($allow_chats) 
		{
		$chat_stmtA="SELECT vlc.status,group_id as campaign_id,if(length(v.phone_number)=0 or v.phone_number is null, v.phone_number, '**NO PHONE**') as phone_number, '' as server_ip,UNIX_TIMESTAMP(chat_start_time), 'CHAT' as call_type, '' as queue_priority, '' as agent_only";
		}
	}
else
	{
	$stmtA = "SELECT status";

	## JCJ Display chats
	if ($allow_chats) 
		{
		$chat_stmtA="SELECT vlc.status";
		}
	}


$k=0;
$agentonlycount=0;
$out_total=0;
$out_ring=0;
$out_live=0;
$in_ivr=0;
$D_active_calls=0;
$stmt = "$stmtA $stmtB";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$parked_to_print = mysqli_num_rows($rslt);
if ($parked_to_print > 0)
	{
	$i=0;
	$out_total=0;
	$out_ring=0;
	$out_live=0;
	$in_ivr=0;
	while ($i < $parked_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		if ($LOGadmin_hide_phone_data != '0')
			{
			$phone_temp = $row[2];
			if ($DB > 0) {echo "HIDEPHONEDATA|$row[2]|$LOGadmin_hide_phone_data|\n";}
			if (strlen($phone_temp) > 0)
				{
				if ($LOGadmin_hide_phone_data == '4_DIGITS')
					{$row[2] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);}
				elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
					{$row[2] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);}
				elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
					{$row[2] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);}
				else
					{$row[2] = preg_replace("/./",'X',$phone_temp);}
				}
			}

		if (preg_match("/LIVE/i",$row[0])) 
			{
			$out_live++;

			if ($CALLSdisplay > 0)
				{
				$CDstatus[$k] =			$row[0];
				$CDcampaign_id[$k] =	$row[1];
				$CDphone_number[$k] =	$row[2];
				$CDserver_ip[$k] =		$row[3];
				$CDcall_time[$k] =		$row[4];
				$CDcall_type[$k] =		$row[5];
				$CDqueue_priority[$k] =	$row[6];
				$CDagent_only[$k] =		$row[7];
				if (strlen($CDagent_only[$k]) > 0) {$agentonlycount++;}
				$k++;
				}
			}
		else
			{
			if (preg_match("/IVR/i",$row[0])) 
				{
				$in_ivr++;

				if ($CALLSdisplay > 0)
					{
					$CDstatus[$k] =			$row[0];
					$CDcampaign_id[$k] =	$row[1];
					$CDphone_number[$k] =	$row[2];
					$CDserver_ip[$k] =		$row[3];
					$CDcall_time[$k] =		$row[4];
					$CDcall_type[$k] =		$row[5];
					$CDqueue_priority[$k] =	$row[6];
					$CDagent_only[$k] =		$row[7];
					if (strlen($CDagent_only[$k]) > 0) {$agentonlycount++;}
					$k++;
					}
				}
			if (preg_match("/CLOSER/i",$row[0])) 
				{$nothing=1;}
			else 
				{$out_ring++;}
			}

		$out_total++;
		$i++;
		}

	##### MIDI alert audio file test #####
	#	$test_midi=1;
	#	if ($test_midi > 0)
	#		{
	#	#	echo "<bgsound src=\"../vicidial/up_down.mid\" loop=\"-1\">";
	#	#	echo "<embed src=\"../vicidial/up_down.mid\" loop=\"-1\">";
	#		echo "<object type=\"audio/x-midi\" data=\"../vicidial/up_down.mid\" width=200 height=20>";
	#		echo "  <param name=\"src\" value=\"../vicidial/up_down.mid\">";
	#		echo "  <param name=\"autoplay\" value=\"true\">";
	#		echo "  <param name=\"autoStart\" value=\"1\">";
	#		echo "  <param name=\"loop\" value=\"1\">";
	#		echo "	alt : <a href=\"../vicidial/up_down.mid\">test.mid</a>";
	#		echo "</object>";
	#		}

	if ($out_live > 0) {$F='<FONT class="r1">'; $FG='</FONT>';}
	if ($out_live > 4) {$F='<FONT class="r2">'; $FG='</FONT>';}
	if ($out_live > 9) {$F='<FONT class="r3">'; $FG='</FONT>';}
	if ($out_live > 14) {$F='<FONT class="r4">'; $FG='</FONT>';}

	$D_active_calls=1;
	if ( ($report_display_type!='HTML') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
		{
		if ($report_display_type=='WALL_1')
			{
			if ($campaign_allow_inbound > 0)
				{echo "$NFB$out_total$NFE "._QXZ("current active calls")."&nbsp; &nbsp; &nbsp; \n";}
			else
				{echo "$NFB$out_total$NFE "._QXZ("calls being placed")." &nbsp; &nbsp; &nbsp; \n";}
			
			echo "$NFB$out_ring$NFE "._QXZ("calls ringing")." &nbsp; &nbsp; &nbsp; &nbsp; \n";
			echo "$NFB$F &nbsp;$out_live $FG$NFE "._QXZ("calls waiting for agents")." &nbsp; &nbsp; &nbsp; \n";
			echo "$NFB &nbsp;$in_ivr$NFE "._QXZ("calls in IVR")." &nbsp; &nbsp; &nbsp; \n";
			}
		else
			{
			if ($campaign_allow_inbound > 0)
				{echo "$NFB$out_total$NFE "._QXZ("current active calls")."&nbsp; &nbsp; &nbsp; \n";}
			else
				{echo "$NFB$out_total$NFE "._QXZ("calls being placed")." &nbsp; &nbsp; &nbsp; \n";}
			
			echo "$NFB$out_ring$NFE "._QXZ("calls ringing")." &nbsp; &nbsp; &nbsp; &nbsp; \n";
			echo "$NFB$F &nbsp;$out_live $FG$NFE "._QXZ("calls waiting for agents")." &nbsp; &nbsp; &nbsp; \n";
			echo "$NFB &nbsp;$in_ivr$NFE "._QXZ("calls in IVR")." &nbsp; &nbsp; &nbsp; \n";
			}
		}
	}
else
	{
	$D_active_calls=0;
	if ( ($report_display_type!='HTML') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
		{
		echo _QXZ(" NO LIVE CALLS WAITING")." \n";
		}
	}

$D_active_chats=0;
$chats_waiting=0;
if ($allow_chats) 
	{
	$chat_stmtB=" from vicidial_live_chats vlc, vicidial_list v where vlc.status='WAITING' and group_id IN($closer_campaignsSQL) and vlc.lead_id=v.lead_id ";
	$chat_stmt="$chat_stmtA $chat_stmtB";
	#$stmt="$chat_stmt UNION $stmtA $stmtB";
	$chat_rslt=mysql_to_mysqli($chat_stmt, $link);
	$waiting_to_chat = mysqli_num_rows($chat_rslt);
	if ($waiting_to_chat > 0)
		{
		$i=0;
		while ($i < $waiting_to_chat)
			{
			$chat_row=mysqli_fetch_row($chat_rslt);
			$chats_waiting++;
			if ($CALLSdisplay > 0)
				{
				$CDstatus[$k] =			$chat_row[0];
				$CDcampaign_id[$k] =	$chat_row[1];
				$CDphone_number[$k] =	$chat_row[2];
				$CDserver_ip[$k] =		$chat_row[3];
				$CDcall_time[$k] =		$chat_row[4];
				$CDcall_type[$k] =		$chat_row[5];
				$CDqueue_priority[$k] =	$chat_row[6];
				$CDagent_only[$k] =		$chat_row[7];
				if (strlen($CDagent_only[$k]) > 0) {$agentonlycount++;}
				$k++;
				}
			$i++;
			}
		if ($chats_waiting > 0) {$F='<FONT class="r1">'; $FG='</FONT>';}
		if ($chats_waiting > 4) {$F='<FONT class="r2">'; $FG='</FONT>';}
		if ($chats_waiting > 9) {$F='<FONT class="r3">'; $FG='</FONT>';}
		if ($chats_waiting > 14) {$F='<FONT class="r4">'; $FG='</FONT>';}
		$D_active_chats=1;
		if ( ($report_display_type!='HTML') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
			{
			echo " &nbsp; &nbsp; &nbsp; $NFB$F &nbsp;$chats_waiting $FG$NFE "._QXZ("chats waiting for agents")." &nbsp; &nbsp; &nbsp; \n";
			}
		}
	else
		{
		$D_active_chats=0;
		if ( ($report_display_type!='HTML') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
			{
			echo _QXZ(" NO LIVE CHATS WAITING ")." \n";
			}
		}
	}

$icbq_stmt="SELECT count(*) from vicidial_inbound_callback_queue where icbq_status='LIVE' and group_id IN($closer_campaignsSQL);";
$icbq_rslt=mysql_to_mysqli($icbq_stmt, $link);
$icbq_ct = mysqli_num_rows($icbq_rslt);
if ($icbq_ct > 0)
	{
	$icbq_ARY=mysqli_fetch_row($icbq_rslt);
	$icbq_live = $icbq_ARY[0];
	}

##### BEGIN HTML output for calls #####
if ( ($report_display_type=='HTML') or ($report_display_type=='WALL_2') )
	{
	$section_width=860;
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	echo "<TABLE width=$section_width cellpadding=3 cellspacing=0 border=0>\n";
	echo "<tr>";
#	echo "<td width=5 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_calls.png\" width=42 height=42></td>";
	if ($campaign_allow_inbound > 0)
		{
		echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("current active calls")."</font></td>";
		}
	else
		{
		echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("calls being placed")."</font></td>";
		}
	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_ringing.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("calls ringing")."</font></td>";
	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_callswaiting.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("calls waiting for agents")."</font></td>";
	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_callsinivr.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("calls in IVR")."</font></td>";
	echo "<td width=6 rowspan=2> &nbsp; </td>";

	if ($allow_chats) 
		{
		echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_chatswaiting.png\" width=42 height=42></td>";
		echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("chats waiting for agents")."</font></td>";
		}
	else
		{
		echo "<td align='center' valign='middle' rowspan=2>&nbsp;</td>";
		echo "<td align='center' valign='middle'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">&nbsp;</font></td>";
		}
#	echo "<td align='center' valign='middle' rowspan=2>&nbsp;</td>";


	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_callswaiting.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("callback queue calls")."</font></td>";

	echo "</tr>";
	echo "<tr>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$out_total</font></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$out_ring</font></td>";

	$calls_waiting_td='bgcolor="#015b91"';   $calls_waiting_font='style="color: white; font-family: HELVETICA; font-size: 18; font-weight: bold;"';
	if ($out_live > 0) {$calls_waiting_td='bgcolor="#FFCCCC"';   $calls_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
	if ($out_live > 4) {$calls_waiting_td='bgcolor="#FF9999"';   $calls_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
	if ($out_live > 9) {$calls_waiting_td='bgcolor="#FF6666"';   $calls_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
	if ($out_live > 14) {$calls_waiting_td='bgcolor="#FF0000"';  $calls_waiting_font='class="color: white; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
	echo "<td align='center' valign='middle' $calls_waiting_td><font $calls_waiting_font>$out_live</font></td>";

	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$in_ivr</font></td>";
	if ($allow_chats) 
		{
		$chats_waiting_td='bgcolor="#015b91"';   $chats_waiting_font='style="color: white; font-family: HELVETICA; font-size: 18; font-weight: bold;"';
		if ($chats_waiting > 0) {$chats_waiting_td='bgcolor="#FFCCCC"';   $chats_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
		if ($chats_waiting > 4) {$chats_waiting_td='bgcolor="#FF9999"';   $chats_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
		if ($chats_waiting > 9) {$chats_waiting_td='bgcolor="#FF6666"';   $chats_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
		if ($chats_waiting > 14) {$chats_waiting_td='bgcolor="#FF0000"';  $chats_waiting_font='class="color: white; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
		echo "<td align='center' valign='middle' $chats_waiting_td><font $chats_waiting_font>$chats_waiting</font></td>";
		}
	else
		{
		echo "<td align='center' valign='middle'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">&nbsp;</font></td>";
		}
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$icbq_live</font></td>";
	echo "</tr>";
	echo "<tr><td colspan=17><font style=\"font-family:HELVETICA;font-size:6;color:white;\">&nbsp;</font></td></tr>";
#	echo "</TABLE>";
	}
##### END HTML output for calls #####





###################################################################################
###### CALLS WAITING
###################################################################################
$agentonlyheader = '';
if ($agentonlycount > 0)
	{$agentonlyheader = _QXZ("AGENTONLY");}

if ($report_display_type=='TEXT')
	{
	$Cecho = '';
	$Cecho .= "VICIDIAL: Calls Waiting                      $NOW_TIME\n";
	$Cecho .= "+---------+----------------------+--------------+-----------------+---------+------------+----------+\n";
	$Cecho .= "| "._QXZ("STATUS",7)." | "._QXZ("CAMPAIGN",20)." | "._QXZ("PHONE NUMBER",12)." | "._QXZ("SERVER IP",15)." | "._QXZ("DIALTIME",8)."| "._QXZ("CALL TYPE",10)." | "._QXZ("PRIORITY",8)." | $agentonlyheader\n";
	$Cecho .= "+---------+----------------------+--------------+-----------------+---------+------------+----------+\n";
	}
if ($report_display_type=='HTML')
	{
	$Cecho = '';
	$Cecho .= "<table cellpadding=1 cellspacing=1 border=0 width=680>";
	$Cecho .= "<tr>";
	$Cecho .= "<td colspan=5><font class='top_head_key'>&nbsp;"._QXZ("Calls Waiting").":</font></td>";
	$Cecho .= "<td colspan=2><font class='top_head_val'>$NOW_TIME</font></td>";
	$Cecho .= "</tr>\n";
	$Cecho .= "<tr bgcolor='#C6C6C6'>";
	$Cecho .= "<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("STATUS")." </font></td>";
	$Cecho .= "<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("CAMPAIGN")." </font></td>";
	$Cecho .= "<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("PHONE NUMBER")." </font></td>";
	$Cecho .= "<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("SERVER IP")." </font></td>";
	$Cecho .= "<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("DIALTIME")." </font></td>";
	$Cecho .= "<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("CALL TYPE")." </font></td>";
	$Cecho .= "<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("PRIORITY")." </font></td>";
	$Cecho .= "<td NOWRAP>$agentonlyheader</td>";
	$Cecho .= "</tr>\n";
	}

$p=0;
while($p<$k)
	{
	$Cstatus =			sprintf("%-7s", _QXZ("$CDstatus[$p]",6)); #TRANSLATE
	$Ccampaign_id =		sprintf("%-20s", $CDcampaign_id[$p]); # Do not translate
	$Cphone_number =	sprintf("%-12s", $CDphone_number[$p]); # Do not translate
	$Cserver_ip =		sprintf("%-15s", $CDserver_ip[$p]); # Do not translate
	$Ccall_type =		sprintf("%-10s", _QXZ("$CDcall_type[$p]",10)); #TRANSLATE
	$Cqueue_priority =	sprintf("%8s", $CDqueue_priority[$p]); # Do not translate
	$Cagent_only =		sprintf("%8s", $CDagent_only[$p]);

	$Ccall_time_S = ($STARTtime - $CDcall_time[$p]);
	$Ccall_time_MS =		sec_convert($Ccall_time_S,'M'); 
	$Ccall_time_MS =		sprintf("%7s", $Ccall_time_MS);

	$G = '';		$EG = '';
	if ($CDcall_type[$p] == 'IN' || $CDcall_type[$p] == 'CHAT')
		{
		$G="<SPAN class=\"csc$CDcampaign_id[$p]\"><B>"; $EG='</B></SPAN>';
		$Ccampaign_id="<a href=\"AST_VICIDIAL_ingrouplist.php?group=$CDcampaign_id[$p]&SUBMIT=SUBMIT\">$Ccampaign_id</a>";
		}
	if (strlen($CDagent_only[$p]) > 0)
		{$Gcalltypedisplay = "$G$Cagent_only$EG";}
	else
		{$Gcalltypedisplay = '';}

	if ($report_display_type=='TEXT')
		{
		$Cecho .= "| $G$Cstatus$EG | $G$Ccampaign_id$EG | $G$Cphone_number$EG | $G$Cserver_ip$EG | $G$Ccall_time_MS$EG | $G$Ccall_type$EG | $G$Cqueue_priority$EG | $Gcalltypedisplay \n";
		}
	else
		{
		$Cecho .= "<tr class=\"csc$CDcampaign_id[$p]\">";
		$Cecho .= "<td NOWRAP align=center>$G$Cstatus$EG</td>";
		$Cecho .= "<td NOWRAP align=right>$G$Ccampaign_id$EG</td>";
		$Cecho .= "<td NOWRAP align=right>$G$Cphone_number$EG</td>";
		$Cecho .= "<td NOWRAP align=center>$G$Cserver_ip$EG</td>";
		$Cecho .= "<td NOWRAP align=right>$G$Ccall_time_MS$EG</td>";
		$Cecho .= "<td NOWRAP align=center>$G$Ccall_type$EG</td>";
		$Cecho .= "<td NOWRAP align=center>$G$Cqueue_priority$EG</td>";
		$Cecho .= "<td NOWRAP align=center>$Gcalltypedisplay</td>";
		$Cecho .= "</tr>\n";
		}

	$p++;
	}
if ($report_display_type=='TEXT')
	{
	$Cecho .= "+---------+----------------------+--------------+-----------------+---------+------------+----------+\n";
	}
else
	{
	$Cecho .= "</table>\n";
	}

if ($p<1)
	{$Cecho='';}





###################################################################################
###### AGENT TIME ON SYSTEM
###################################################################################

$agent_incall=0;
$agent_indial=0;
$agent_ready=0;
$agent_paused=0;
$agent_dispo=0;
$agent_dead=0;
$agent_total=0;

$phoneord=$orderby;
$userord=$orderby;
$groupord=$orderby;
$timeord=$orderby;
$campaignord=$orderby;

if ($phoneord=='phoneup') {$phoneord='phonedown';}
  else {$phoneord='phoneup';}
if ($userord=='userup') {$userord='userdown';}
  else {$userord='userup';}
if ($groupord=='groupup') {$groupord='groupdown';}
  else {$groupord='groupup';}
if ($timeord=='timeup') {$timeord='timedown';}
  else {$timeord='timeup';}
if ($campaignord=='campaignup') {$campaignord='campaigndown';}
  else {$campaignord='campaignup';}

if ($report_display_type=='TEXT')
	{
	$Aecho = '';
	$Aecho .= ""._QXZ("Agents Time On Calls Campaign").": $group_string            $NOW_TIME\n";
	}
if ($report_display_type=='HTML')
	{
	$Aecho = '';
	$Aecho .= "<table cellpadding=1 cellspacing=1 border=0 width=860>";
	$Aecho .= "<tr>";
	$Aecho .= "<td colspan=4><font class='top_head_key'>&nbsp;"._QXZ("Agents Time On Calls Campaign").":</font></td>";
	$Aecho .= "<td colspan=3><font class='top_settings_val'>&nbsp;</font></td>";
	$Aecho .= "<td colspan=4><font class='top_head_val'>$NOW_TIME\n</font></td>";
	$Aecho .= "</tr>\n";
#	$Aecho .= "<tr bgcolor='#C6C6C6'>";
#	$Aecho .= "<td><font class='top_head_key'>"._QXZ("STATUS")."</font></td>";
#	$Aecho .= "<td><font class='top_head_key'>"._QXZ("CAMPAIGN")."</font></td>";
#	$Aecho .= "<td><font class='top_head_key'>"._QXZ("PHONE NUMBER")."</font></td>";
#	$Aecho .= "<td><font class='top_head_key'>"._QXZ("SERVER IP")."</font></td>";
#	$Aecho .= "<td><font class='top_head_key'>"._QXZ("DIALTIME")."</font></td>";
#	$Aecho .= "<td><font class='top_head_key'>"._QXZ("CALL TYPE")."</font></td>";
#	$Aecho .= "<td><font class='top_head_key'>"._QXZ("PRIORITY")."</font></td>";
#	$Aecho .= "<td>$agentonlyheader</td>";
#	$Aecho .= "</tr>\n";
	}

if ($report_display_type=='TEXT')
	{
	##### BEGIN TEXT Output generation #####
	$HDbegin =			"+";
	$HTbegin =			"|";
	$HDstation =		"----------------+";
	$HTstation =		" "._QXZ("STATION", 14)." |";
	$HDphone =		"-------------+";
	$HTphone =		" <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$phoneord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("PHONE",11)."</a> |";
	if ($RTajax > 0)
		{$HTphone =		" <a href=\"#\" onclick=\"update_variables('orderby','phone');\">"._QXZ("PHONE",11)."</a>       |";}
	$HDuser =			"------------------------+";

	$HTuser =			" <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$userord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("USER",5)."</a> ";
	if ($RTajax > 0)
		{$HTuser =	" <a href=\"#\" onclick=\"update_variables('orderby','user');\">"._QXZ("USER",5)."</a> ";}
	if ($UidORname>0)
		{
		if ($RTajax > 0)
			{$HTuser .=	"<a href=\"#\" onclick=\"update_variables('UidORname','');\">"._QXZ("SHOW ID",8)."</a> ";}
		else
			{
			$HTuser .= "<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=0&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("SHOW ID",8)."</a> ";
			}
		}
	else
		{
		if (isset($RTajax) and $RTajax > 0)
			{$HTuser .=	"<a href=\"#\" onclick=\"update_variables('UidORname','');\">"._QXZ("SHOW NAME",9)."</a>";}
		else
			{
			$HTuser .= "<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=1&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("SHOW NAME",9)."</a>";
			}
		}
	$HTuser .= " "._QXZ("INFO",6)." |";

	$HDusergroup =		"--------------+";
	$HTusergroup =		" <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$groupord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("USER GROUP",12)."</a> |";
	if ($RTajax > 0)
		{$HTusergroup =	" <a href=\"#\" onclick=\"update_variables('orderby','group');\">"._QXZ("USER GROUP",12)."</a> |";}
	$HDsessionid =		"------------------+";
	$HTsessionid =		" "._QXZ("SESSIONID",16)." |";
	$HDbarge =			"-------+";
	$HTbarge =			" "._QXZ("BARGE",5)." |";
	$HDwhisper =		"---------+";
	$HTwhisper =		" "._QXZ("WHISPER",7)." |";
	$HDstatus =			"----------+";
	$HTstatus =			" "._QXZ("STATUS",8)." |";
	$HDcustphone =		"-------------+";
	$HTcustphone =		" "._QXZ("CUST PHONE",11)." |";
	$HDserver_ip =		"-----------------+";
	$HTserver_ip =		" "._QXZ("SERVER IP",15)." |";
	$HDcall_server_ip =	"-----------------+";
	$HTcall_server_ip =	" "._QXZ("CALL SERVER IP",15)." |";
	$HDtime =			"---------+";
	$HTtime =			" <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$timeord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">MM:SS</a>   |";
	if ($RTajax > 0)
		{$HTtime =	" <a href=\"#\" onclick=\"update_variables('orderby','time');\">MM:SS</a>   |";}
	$HDcampaign =		"------------+";
	$HTcampaign =		" <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$campaignord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\">"._QXZ("CAMPAIGN",10)."</a> |";
	if ($RTajax > 0)
		{$HTcampaign =	" <a href=\"#\" onclick=\"update_variables('orderby','campaign');\">"._QXZ("CAMPAIGN",10)."</a> |";}
	$HDcalls =			"-------+";
	$HTcalls =			" "._QXZ("CALLS",5)." |";
	$HDpause =	'';
	$HTpause =	'';
	$HDigcall =			"------+------------------";
	$HTigcall =			" "._QXZ("HOLD",4)." | "._QXZ("IN-GROUP",8)." ";

	if ($agent_pause_codes_active > 0)
		{
		$HDstatus =			"----------";
		$HTstatus =			" "._QXZ("STATUS",8)." ";
		$HDpause =			"-------+";
		$HTpause =			" "._QXZ("PAUSE",5)." |";
		}
	if ( ($SIPmonitorLINK<1) and ($IAXmonitorLINK<1) and (!preg_match("/MONITOR|BARGE|WHISPER/",$monitor_active) ) ) 
		{
		$HDsessionid =	"-----------+";
		$HTsessionid =	" "._QXZ("SESSIONID",9)." |";
		}
	##### END TEXT Output generation #####
	}
if ($report_display_type=='HTML')
	{
	##### BEGIN HTML Output generation #####
	$HDbegin =			"";
	$HTbegin =			"<tr bgcolor='#C6C6C6'>";
	$HDstation =		"";
	$HTstation =		"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("STATION")." </td>";
	$HDphone =		"";
	$HTphone =		"<td NOWRAP>&nbsp;<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$phoneord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class='top_head_key'>"._QXZ("PHONE")."</a>&nbsp;</td>";
	if ($RTajax > 0)
		{$HTphone =		"<td NOWRAP>&nbsp;<a href=\"#\" onclick=\"update_variables('orderby','phone');\"><font class='top_head_key'>"._QXZ("PHONE")."</a>&nbsp;</td>";}
	$HDuser =			"";

	$HTuser =			"<td NOWRAP>&nbsp;<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$userord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class='top_head_key'>"._QXZ("USER")."</a> ";
	if ($RTajax > 0)
		{$HTuser =	"<td NOWRAP>&nbsp;<a href=\"#\" onclick=\"update_variables('orderby','user');\"><font class='top_head_key'>&nbsp; "._QXZ("USER")."</a> ";}
	if ($UidORname>0)
		{
		if ($RTajax > 0)
			{$HTuser .=	"&nbsp;<a href=\"#\" onclick=\"update_variables('UidORname','');\"><font class='top_head_key'>"._QXZ("SHOW ID")."</a> ";}
		else
			{
			$HTuser .= "&nbsp;<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=0&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class='top_head_key'>"._QXZ("SHOW ID")."</a> ";
			}
		}
	else
		{
		if (isset($RTajax) and $RTajax > 0)
			{$HTuser .=	"&nbsp;<a href=\"#\" onclick=\"update_variables('UidORname','');\"><font class='top_head_key'>"._QXZ("SHOW NAME")."</a> ";}
		else
			{
			$HTuser .= "&nbsp; <a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=1&orderby=$orderby&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class='top_head_key'>"._QXZ("SHOW NAME")."</a> ";
			}
		}
	$HTuser .= "&nbsp;<font class='top_head_key'>"._QXZ("INFO",6)."&nbsp;</td>";

	$HDusergroup =		"";
	$HTusergroup =		"<td NOWRAP>&nbsp;<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$groupord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class='top_head_key'>"._QXZ("USER GROUP")."</a>&nbsp;</td>";
	if ($RTajax > 0)
		{$HTusergroup =	"<td NOWRAP>&nbsp;<a href=\"#\" onclick=\"update_variables('orderby','group');\"><font class='top_head_key'>"._QXZ("USER GROUP")."</a>&nbsp;</td>";}
	$HDsessionid =		"";
	$HTsessionid =		"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("SESSIONID")." </td>";
	$HDbarge =			"";
	$HTbarge =			"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("BARGE")." </td>";
	$HDwhisper =		"";
	$HTwhisper =		"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("WHISPER")." </td>";
	$HDstatus =			"";
	$HTstatus =			"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("STATUS")." </td><td><font class='top_head_key'>&nbsp;</td>";
	$HDcustphone =		"";
	$HTcustphone =		"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("CUST PHONE")." </td>";
	$HDserver_ip =		"";
	$HTserver_ip =		"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("SERVER IP")." </td>";
	$HDcall_server_ip =	"";
	$HTcall_server_ip =	"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("CALL SERVER IP")." </td>";
	$HDtime =			"";
	$HTtime =			"<td NOWRAP>&nbsp;<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$timeord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class='top_head_key'>MM:SS</a>&nbsp;</td>";
	if ($RTajax > 0)
		{$HTtime =	"<td NOWRAP>&nbsp;<a href=\"#\" onclick=\"update_variables('orderby','time');\"><font class='top_head_key'>MM:SS</a>&nbsp;</td>";}
	$HDcampaign =		"";
	$HTcampaign =		"<td NOWRAP>&nbsp;<a href=\"$PHP_SELF?$usergroupQS$groupQS&RR=$RR&DB=$DB&adastats=$adastats&SIPmonitorLINK=$SIPmonitorLINK&IAXmonitorLINK=$IAXmonitorLINK&usergroup=$usergroup&UGdisplay=$UGdisplay&UidORname=$UidORname&orderby=$campaignord&SERVdisplay=$SERVdisplay&CALLSdisplay=$CALLSdisplay&PHONEdisplay=$PHONEdisplay&CUSTPHONEdisplay=$CUSTPHONEdisplay&with_inbound=$with_inbound&monitor_active=$monitor_active&monitor_phone=$monitor_phone&ALLINGROUPstats=$ALLINGROUPstats&DROPINGROUPstats=$DROPINGROUPstats&NOLEADSalert=$NOLEADSalert&CARRIERstats=$CARRIERstats&PRESETstats=$PRESETstats&AGENTtimeSTATS=$AGENTtimeSTATS&INGROUPcolorOVERRIDE=$INGROUPcolorOVERRIDE&droppedOFtotal=$droppedOFtotal&report_display_type=$report_display_type\"><font class='top_head_key'>"._QXZ("CAMPAIGN")."</a>&nbsp;</td>";
	if ($RTajax > 0)
		{$HTcampaign =	"<td NOWRAP>&nbsp;<a href=\"#\" onclick=\"update_variables('orderby','campaign');\"><font class='top_head_key'>"._QXZ("CAMPAIGN",10)."</a>&nbsp;</td>";}
	$HDcalls =			"";
	$HTcalls =			"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("CALLS")." </td>";
	$HDpause =	'';
	$HTpause =	'';
	$HDigcall =			"";
	$HTigcall =			"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("HOLD")." </td><td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("IN-GROUP",8)." </td></tr>";

	if ($agent_pause_codes_active > 0)
		{
		$HDstatus =			"";
		$HTstatus =			"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("STATUS")." </td><td><font class='top_head_key'>&nbsp;</td>";
		$HDpause =			"";
		$HTpause =			"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("PAUSE")." </td>";
		}
	if ( ($SIPmonitorLINK<1) and ($IAXmonitorLINK<1) and (!preg_match("/MONITOR|BARGE|WHISPER/",$monitor_active) ) ) 
		{
		$HDsessionid =	"";
		$HTsessionid =	"<td NOWRAP><font class='top_head_key'>&nbsp; "._QXZ("SESSIONID")." </td>";
		}
	##### END HTML Output generation #####
	}


if ($PHONEdisplay < 1)
	{
	$HDphone =	'';
	$HTphone =	'';
	}
if ($CUSTPHONEdisplay < 1)
	{
	$HDcustphone =	'';
	$HTcustphone =	'';
	}
if ($UGdisplay < 1)
	{
	$HDusergroup =	'';
	$HTusergroup =	'';
	}
if ( ($SIPmonitorLINK<2) and ($IAXmonitorLINK<2) and (!preg_match("/BARGE/",$monitor_active) ) ) 
	{
	$HDbarge =		'';
	$HTbarge =		'';
	}
if ( ($SIPmonitorLINK<2) and ($IAXmonitorLINK<2) and (!preg_match("/WHISPER/",$monitor_active) ) ) 
	{
	$HDwhisper =		'';
	$HTwhisper =		'';
	}
if ($SERVdisplay < 1)
	{
	$HDserver_ip =		'';
	$HTserver_ip =		'';
	$HDcall_server_ip =	'';
	$HTcall_server_ip =	'';
	}


if ($realtime_block_user_info > 0)
	{
	$Aline  = "$HDbegin$HDusergroup$HDsessionid$HDbarge$HDwhisper$HDstatus$HDpause$HDcustphone$HDserver_ip$HDcall_server_ip$HDtime$HDcampaign$HDcalls$HDigcall\n";
	$Bline  = "$HTbegin$HTusergroup$HTsessionid$HTbarge$HTwhisper$HTstatus$HTpause$HTcustphone$HTserver_ip$HTcall_server_ip$HTtime$HTcampaign$HTcalls$HTigcall\n";
	}
else
	{
	$Aline  = "$HDbegin$HDstation$HDphone$HDuser$HDusergroup$HDsessionid$HDbarge$HDwhisper$HDstatus$HDpause$HDcustphone$HDserver_ip$HDcall_server_ip$HDtime$HDcampaign$HDcalls$HDigcall\n";
	$Bline  = "$HTbegin$HTstation$HTphone$HTuser$HTusergroup$HTsessionid$HTbarge$HTwhisper$HTstatus$HTpause$HTcustphone$HTserver_ip$HTcall_server_ip$HTtime$HTcampaign$HTcalls$HTigcall\n";
	}
$Aecho .= "$Aline";
$Aecho .= "$Bline";
$Aecho .= "$Aline";

if ($orderby=='timeup') {$orderSQL='vicidial_live_agents.status,last_call_time';}
if ($orderby=='timedown') {$orderSQL='vicidial_live_agents.status desc,last_call_time desc';}
if ($orderby=='campaignup') {$orderSQL='vicidial_live_agents.campaign_id,vicidial_live_agents.status,last_call_time';}
if ($orderby=='campaigndown') {$orderSQL='vicidial_live_agents.campaign_id desc,vicidial_live_agents.status desc,last_call_time desc';}
if ($orderby=='groupup') {$orderSQL='user_group,vicidial_live_agents.status,last_call_time';}
if ($orderby=='groupdown') {$orderSQL='user_group desc,vicidial_live_agents.status desc,last_call_time desc';}
if ($orderby=='phoneup') {$orderSQL='extension,server_ip';}
if ($orderby=='phonedown') {$orderSQL='extension desc,server_ip desc';}
if ($UidORname > 0)
	{
	if ($orderby=='userup') {$orderSQL='full_name,status,last_call_time';}
	if ($orderby=='userdown') {$orderSQL='full_name desc,status desc,last_call_time desc';}
	}
else
	{
	if ($orderby=='userup') {$orderSQL='vicidial_live_agents.user';}
	if ($orderby=='userdown') {$orderSQL='vicidial_live_agents.user desc';}
	}

if ( !preg_match("/ALL-/",$LOGallowed_campaigns) ) {$UgroupSQL = " and vicidial_live_agents.campaign_id IN($group_SQL)";}
else if ( (preg_match('/ALL\-ACTIVE/i',$group_string)) and (strlen($group_SQL) < 3) ) {$UgroupSQL = '';}
else {$UgroupSQL = " and vicidial_live_agents.campaign_id IN($group_SQL)";}

if (strlen($usergroup)<1) {$usergroupSQL = '';}
else {$usergroupSQL = " and user_group='" . mysqli_real_escape_string($link, $usergroup) . "'";}

if ( (preg_match('/ALL\-GROUPS/i',$user_group_string)) and (strlen($user_group_SQL) < 3) ) {$user_group_filter_SQL = '';}
else {$user_group_filter_SQL = " and vicidial_users.user_group IN($user_group_SQL)";}

$ring_agents=0;
$stmt="SELECT extension,vicidial_live_agents.user,conf_exten,vicidial_live_agents.status,vicidial_live_agents.server_ip,UNIX_TIMESTAMP(last_call_time),UNIX_TIMESTAMP(last_call_finish),call_server_ip,vicidial_live_agents.campaign_id,vicidial_users.user_group,vicidial_users.full_name,vicidial_live_agents.comments,vicidial_live_agents.calls_today,vicidial_live_agents.callerid,lead_id,UNIX_TIMESTAMP(last_state_change),on_hook_agent,ring_callerid,agent_log_id from vicidial_live_agents,vicidial_users where vicidial_live_agents.user=vicidial_users.user and vicidial_users.user_hide_realtime='0' $UgroupSQL $usergroupSQL $user_group_filter_SQL order by $orderSQL;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$talking_to_print = mysqli_num_rows($rslt);
if ($talking_to_print > 0)
	{
	$i=0;
	while ($i < $talking_to_print)
		{
		$row=mysqli_fetch_row($rslt);

		$Aextension[$i] =		$row[0];
		$Auser[$i] =			$row[1];
		$Asessionid[$i] =		$row[2];
		$Astatus[$i] =			$row[3];
		$Aserver_ip[$i] =		$row[4];
		$Acall_time[$i] =		$row[5];
		$Acall_finish[$i] =		$row[6];
		$Acall_server_ip[$i] =	$row[7];
		$Acampaign_id[$i] =		$row[8];
		$Auser_group[$i] =		$row[9];
		$Afull_name[$i] =		$row[10];
		$Acomments[$i] = 		$row[11];
		$Acalls_today[$i] =		$row[12];
		$Acallerid[$i] =		$row[13];
		$Alead_id[$i] =			$row[14];
		$Astate_change[$i] =	$row[15];
		$Aon_hook_agent[$i] =	$row[16];
		$Aring_callerid[$i] =	$row[17];
		$Aagent_log_id[$i] =	$row[18];
		$Aring_note[$i] =		' ';

		if ($Aon_hook_agent[$i] == 'Y')
			{
			$Aring_note[$i] = '*';
			$ring_agents++;
			if (strlen($Aring_callerid[$i]) > 18)
				{$Astatus[$i]="RING";}
			}


		### 3-WAY Check ###
		if ($Alead_id[$i]!=0) 
			{
			$threewaystmt="SELECT UNIX_TIMESTAMP(last_call_time) from vicidial_live_agents where lead_id='$Alead_id[$i]' and status='INCALL' order by UNIX_TIMESTAMP(last_call_time) desc";
			$threewayrslt=mysql_to_mysqli($threewaystmt, $link);
			if (mysqli_num_rows($threewayrslt)>1) 
				{
				$Astatus[$i]="3-WAY";
				$srow=mysqli_fetch_row($threewayrslt);
				$Acall_mostrecent[$i]=$srow[0];
				}
			}
		### END 3-WAY Check ###

		$i++;
		}

	$callerids='';
	$pausecode='';
	$stmt="SELECT callerid,lead_id,phone_number from vicidial_auto_calls;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$calls_to_list = mysqli_num_rows($rslt);
	if ($calls_to_list > 0)
		{
		$i=0;
		while ($i < $calls_to_list)
			{
			$row=mysqli_fetch_row($rslt);
			if ($LOGadmin_hide_phone_data != '0')
				{
				if ($DB > 0) {echo "HIDEPHONEDATA|$row[2]|$LOGadmin_hide_phone_data|\n";}
				$phone_temp = $row[2];
				if (strlen($phone_temp) > 0)
					{
					if ($LOGadmin_hide_phone_data == '4_DIGITS')
						{$row[2] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);}
					elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
						{$row[2] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);}
					elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
						{$row[2] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);}
					else
						{$row[2] = preg_replace("/./",'X',$phone_temp);}
					}
				}
			$callerids .=	"$row[0]|";
			$VAClead_ids[$i] =	$row[1];
			$VACphones[$i] =	$row[2];
			$i++;
			}
		}

	### Lookup phone logins
	$i=0;
	while ($i < $talking_to_print)
		{
		if (preg_match("/R\//i",$Aextension[$i])) 
			{
			$protocol = 'EXTERNAL';
			$dialplan = preg_replace('/R\//i', '',$Aextension[$i]);
			$dialplan = preg_replace('/\@.*/i', '',$dialplan);
			$exten = "dialplan_number='$dialplan'";
			}
		if (preg_match("/Local\//i",$Aextension[$i])) 
			{
			$protocol = 'EXTERNAL';
			$dialplan = preg_replace('/Local\//i', '',$Aextension[$i]);
			$dialplan = preg_replace('/\@.*/i', '',$dialplan);
			$exten = "dialplan_number='$dialplan'";
			}
		if (preg_match('/SIP\//i',$Aextension[$i])) 
			{
			$protocol = 'SIP';
			$dialplan = preg_replace('/SIP\//i', '',$Aextension[$i]);
			$dialplan = preg_replace('/\-.*/i', '',$dialplan);
			$exten = "extension='$dialplan'";
			}
		if (preg_match('/IAX2\//i',$Aextension[$i])) 
			{
			$protocol = 'IAX2';
			$dialplan = preg_replace('/IAX2\//i', '',$Aextension[$i]);
			$dialplan = preg_replace('/\-.*/i', '',$dialplan);
			$exten = "extension='$dialplan'";
			}
		if (preg_match('/Zap\//i',$Aextension[$i])) 
			{
			$protocol = 'Zap';
			$dialplan = preg_replace('/Zap\//i', '',$Aextension[$i]);
			$exten = "extension='$dialplan'";
			}
		if (preg_match('/DAHDI\//i',$Aextension[$i])) 
			{
			$protocol = 'Zap';
			$dialplan = preg_replace('/DAHDI\//i', '',$Aextension[$i]);
			$exten = "extension='$dialplan'";
			}

		$stmt="SELECT login from phones where server_ip='$Aserver_ip[$i]' and $exten and protocol='$protocol';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$phones_to_print = mysqli_num_rows($rslt);
		if ($phones_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$Alogin[$i] = "$row[0]-----$i";
			}
		else
			{
			$Alogin[$i] = "$Aextension[$i]-----$i";
			}
		$i++;
		}

### Sort by phone if selected
	if ($orderby=='phoneup')
		{
		sort($Alogin);
		}
	if ($orderby=='phonedown')
		{
		rsort($Alogin);
		}

### Run through the loop to display agents
	$j=0;
	$agentcount=0;
	while ($j < $talking_to_print)
		{
		$n=0;
		$custphone='';
		while ($n < $calls_to_list)
			{
			if ( (preg_match("/$VAClead_ids[$n]/", $Alead_id[$j])) and (strlen($VAClead_ids[$n]) == strlen($Alead_id[$j])) and (strlen($VAClead_ids[$n] > 1)) )
				{$custphone = $VACphones[$n];}
			$n++;
			}

		$phone_split = explode("-----",$Alogin[$j]);
		$i = $phone_split[1];

		if (preg_match("/READY|PAUSED/i",$Astatus[$i]))
			{
			$Acall_time[$i]=$Astate_change[$i];

			if ($Alead_id[$i] > 0)
				{
				$Astatus[$i] =	'DISPO';
				$Lstatus =		'DISPO';
				$status =		' DISPO';
				}
			}
		if ($non_latin < 1)
			{
			$extension = preg_replace('/Local\//i', '',$Aextension[$i]);
			if ($report_display_type!='HTML')
				{
				$extension =		sprintf("%-14s", $extension);
				while(strlen($extension)>14) {$extension = substr("$extension", 0, -1);}
				}
			}
		else
			{
			$extension = preg_replace('/Local\//i', '',$Aextension[$i]);
			if ($report_display_type!='HTML')
				{
				$extension =		sprintf("%-48s", $extension);
				while(mb_strlen($extension, 'utf-8')>14) {$extension = mb_substr("$extension", 0, -1,'UTF8');}
				}
			}

		$phone =			sprintf("%-12s", $phone_split[0]);
		$custphone =		sprintf("%-11s", $custphone);
		$Luser =			$Auser[$i];
		$user =				sprintf("%-20s", $Auser[$i]);
		$Lsessionid =		$Asessionid[$i];
		$sessionid =		sprintf("%-9s", $Asessionid[$i]);
		$Lstatus =			$Astatus[$i];
		$status =			sprintf("%-6s", $Astatus[$i]);
		$Lserver_ip =		$Aserver_ip[$i];
		$server_ip =		sprintf("%-15s", $Aserver_ip[$i]);
		$call_server_ip =	sprintf("%-15s", $Acall_server_ip[$i]);
		$campaign_id =	sprintf("%-10s", $Acampaign_id[$i]);
		$comments=		$Acomments[$i];
		$calls_today =	sprintf("%-5s", $Acalls_today[$i]);

		if ($agent_pause_codes_active > 0)
			{
			$pausecode='       ';
			}
		else
			{$pausecode='';}

		if (preg_match("/INCALL/i",$Lstatus)) 
			{
			$stmtP="SELECT count(*) from parked_channels where channel_group='$Acallerid[$i]';";
			if ($DB) {echo "$stmtP\n";}
			$rsltP=mysql_to_mysqli($stmtP,$link);
			$rowP=mysqli_fetch_row($rsltP);
			$parked_channel = $rowP[0];

			if ($parked_channel > 0)
				{
				$Astatus[$i] =	'PARK';
				$Lstatus =		'PARK';
				$status =		' PARK ';
				}
			else
				{
				if (!preg_match("/$Acallerid[$i]\|/",$callerids) && !preg_match("/EMAIL/i",$comments) && !preg_match("/CHAT/i",$comments))
					{
					$Acall_time[$i]=$Astate_change[$i];

					$Astatus[$i] =	'DEAD';
					$Lstatus =		'DEAD';
					$status =		' DEAD ';
					}
				else
					{
					if ($Acomments[$i] == 'MANUAL')
						{
						$stmt="SELECT uniqueid,channel from vicidial_auto_calls where callerid='$Acallerid[$i]' LIMIT 1;";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$mandial_to_check = mysqli_num_rows($rslt);
							if ($mandial_to_check > 0)
							{
							$row=mysqli_fetch_row($rslt);
							if ( (strlen($row[0])<5) and (strlen($row[1])<5) )
								{
								$Astatus[$i] =	'DIAL';
								$Lstatus =		'DIAL';
								$status =		' DIAL ';
								}
							}
						}
					}
				if (preg_match("/CHAT/i",$comments))
					{
					$stmtCT="SELECT chat_id from vicidial_live_chats where chat_creator='$Auser[$i]' and lead_id='$Alead_id[$i]' order by chat_start_time desc limit 1;";
					if ($DB) {echo "$stmtCT\n";}
					$rsltCT=mysql_to_mysqli($stmtCT,$link);
					$chatting_to_print = mysqli_num_rows($rslt);
					if ($chatting_to_print > 0)
						{
						$rowCT=mysqli_fetch_row($rsltCT);
						$Achat_id = $rowCT[0];

						$stmtCL="SELECT count(*) from vicidial_chat_log where chat_id='$Achat_id' and message LIKE \"%has left chat\";";
						if ($DB) {echo "$stmtCL\n";}
						$rsltCL=mysql_to_mysqli($stmtCL,$link);
						$rowCL=mysqli_fetch_row($rsltCL);
						$left_chat = $rowCL[0];

						if ($left_chat > 0)
							{
							$Acall_time[$i]=$Astate_change[$i];

							$Astatus[$i] =	'DEAD';
							$Lstatus =		'DEAD';
							$status =		'DEAD C';
							}
						}
					}
				}

			if ( (preg_match("/AUTO/i",$comments)) or (strlen($comments)<1) )
				{$CM='A';}
			else
				{
				if (preg_match("/INBOUND/i",$comments)) 
					{$CM='I';}
				else if (preg_match("/EMAIL/i",$comments)) 
					{$CM='E';}
				else
					{$CM='M';}
				}
			}
		else {$CM=' ';}

		if ($UGdisplay > 0)
			{
			if ($non_latin < 1)
				{
				$user_group =		sprintf("%-12s", $Auser_group[$i]);
				if ($report_display_type!='HTML')
					{
					while(strlen($user_group)>12) {$user_group = substr("$user_group", 0, -1);}
					}
				}
			else
				{
				$user_group =		sprintf("%-40s", $Auser_group[$i]);
				if ($report_display_type!='HTML')
					{
					while(mb_strlen($user_group, 'utf-8')>12) {$user_group = mb_substr("$user_group", 0, -1,'UTF8');}
					}
				}
			}
		if ($UidORname > 0)
			{
			if ($non_latin < 1)
				{
				$user =		sprintf("%-20s", $Afull_name[$i]);
				if ($report_display_type!='HTML')
					{
					while(strlen($user)>20) {$user = substr("$user", 0, -1);}
					}
				}
			else
				{
				$user =		sprintf("%-60s", $Afull_name[$i]);
				if ($report_display_type!='HTML')
					{
					while(mb_strlen($user, 'utf-8')>20) {$user = mb_substr("$user", 0, -1,'UTF8');}
					}
				}
			}
		if (!preg_match("/INCALL|DIAL|QUEUE|PARK|3-WAY/i",$Astatus[$i]))
			{$call_time_S = ($STARTtime - $Astate_change[$i]);}
		else if (preg_match("/3-WAY/i",$Astatus[$i]))
			{$call_time_S = ($STARTtime - $Acall_mostrecent[$i]);}
		else
			{$call_time_S = ($STARTtime - $Acall_time[$i]);}

		$call_time_MS =		sec_convert($call_time_S,'M'); 
		$call_time_MS =		sprintf("%7s", $call_time_MS);
		$call_time_MS =		" $call_time_MS";
		$G = '<SPAN class="blank">'; $EG = '</SPAN>'; $tr_class='TRblank';
		if ( ($Lstatus=='INCALL') or ($Lstatus=='PARK') )
			{
			if ($comments == 'CHAT') {$G='<SPAN class="red"><B>'; $EG='</B></SPAN>'; $status='CHAT'; $tr_class='TRred';}
			else if ($comments == 'EMAIL') {$G='<SPAN class="orange"><B>'; $EG='</B></SPAN>'; $status='EMAIL'; $tr_class='TRorange';}
			else 
				{
				if ($call_time_S >= 10) {$G='<SPAN class="thistle"><B>'; $EG='</B></SPAN>'; $tr_class='TRthistle';}
				if ($call_time_S >= 60) {$G='<SPAN class="violet"><B>'; $EG='</B></SPAN>'; $tr_class='TRviolet';}
				if ($call_time_S >= 300) {$G='<SPAN class="purple"><B>'; $EG='</B></SPAN>'; $tr_class='TRpurple';}
	#			if ($call_time_S >= 600) {$G='<SPAN class="purple"><B>'; $EG='</B></SPAN>'; $tr_class='TRpurple';}
				}
			}
		if ($Lstatus=='3-WAY')
			{
			if ($call_time_S >= 10) {$G='<SPAN class="lime"><B>'; $EG='</B></SPAN>'; $tr_class='TRlime';}
			}
		if ($Lstatus=='DEAD')
			{
			if ($call_time_S >= 21600) 
				{$j++; continue;} 
			else
				{
				$agent_dead++;  $agent_total++;
				$G = '<SPAN class="blank">'; $EG = '</SPAN>'; $tr_class='TRblank';
				if ($call_time_S >= 10) {$G='<SPAN class="black"><B>'; $EG='</B></SPAN>'; $tr_class='TRblack';}
				}
			}
		if ($Lstatus=='DISPO')
			{
			if ($call_time_S >= 21600) 
				{$j++; continue;} 
			else
				{
				$agent_dispo++;  $agent_total++;
				$G = '<SPAN class="blank">'; $EG = '</SPAN>'; $tr_class='TRblank';
				if ($call_time_S >= 10) {$G='<SPAN class="khaki"><B>'; $EG='</B></SPAN>'; $tr_class='TRkhaki';}
				if ($call_time_S >= 60) {$G='<SPAN class="yellow"><B>'; $EG='</B></SPAN>'; $tr_class='TRyellow';}
				if ($call_time_S >= 300) {$G='<SPAN class="olive"><B>'; $EG='</B></SPAN>'; $tr_class='TRolive';}
				}
			}
		if ($Lstatus=='PAUSED') 
			{
			if ($agent_pause_codes_active > 0)
				{
				$twentyfour_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-24,date("i"),date("s"),date("m"),date("d"),date("Y")));
				$stmtC="SELECT sub_status from vicidial_agent_log where agent_log_id >= \"$Aagent_log_id[$i]\" and user='$Luser' order by agent_log_id desc limit 1;";
				$rsltC=mysql_to_mysqli($stmtC,$link);
				$rowC=mysqli_fetch_row($rsltC);
				$pausecode = sprintf("%-6s", $rowC[0]);
				$pausecode="$pausecode ";
				if ($SSenable_pause_code_limits > 0)
					{
					$pause_limit_stmt="SELECT time_limit from vicidial_pause_codes where campaign_id='$Acampaign_id[$i]' and pause_code='$rowC[0]'";
					$pause_limit_rslt=mysql_to_mysqli($pause_limit_stmt, $link);
					if (mysqli_num_rows($pause_limit_rslt)>0) 
						{
						$pause_limit_row=mysqli_fetch_row($pause_limit_rslt);
						$pause_limit=$pause_limit_row[0];
						}
					else
						{
						$pause_limit="999999";
						}
					}
				else
					{$pause_limit="999999";}
				}
			else
				{$pausecode=''; $pause_limit="999999";}

			if ($call_time_S >= 21600) 
				{$j++; continue;} 
			else
				{
				$agent_paused++;  $agent_total++;
				$G = '<SPAN class="blank">'; $EG = '</SPAN>'; $tr_class='TRblank';
				if ($call_time_S >= 10) {$G='<SPAN class="khaki"><B>'; $EG='</B></SPAN>'; $tr_class='TRkhaki';}
				if ($call_time_S >= 60) {$G='<SPAN class="yellow"><B>'; $EG='</B></SPAN>'; $tr_class='TRyellow';}
				if ($call_time_S >= 300) {$G='<SPAN class="olive"><B>'; $EG='</B></SPAN>'; $tr_class='TRolive';}
				if ($call_time_S >= $pause_limit) {$G='<SPAN class="darkred"><B>'; $EG='</B></SPAN>'; $tr_class='TRdarkred';}
				}
			}
#		if ( (strlen($Acall_server_ip[$i])> 4) and ($Acall_server_ip[$i] != "$Aserver_ip[$i]") )
#				{$G='<SPAN class="orange"><B>'; $EG='</B></SPAN>';}

		if ( (preg_match("/INCALL|DIAL/i",$status)) or (preg_match("/QUEUE/i",$status))  or (preg_match("/3-WAY/i",$status)) or (preg_match('/PARK/i',$status))) {$agent_incall++;  $agent_total++;}
		if (preg_match("/DIAL/i",$status)) {$agent_indial++;}
		if ( (preg_match("/READY/i",$status)) or (preg_match("/CLOSER/i",$status)) ) {$agent_ready++;  $agent_total++;}
		if ( (preg_match("/READY/i",$status)) or (preg_match("/CLOSER/i",$status)) ) 
			{
			$G='<SPAN class="lightblue"><B>'; $EG='</B></SPAN>';  $tr_class='TRlightblue';
			if ($call_time_S >= 60) {$G='<SPAN class="blue"><B>'; $EG='</B></SPAN>'; $tr_class='TRblue';}
			if ($call_time_S >= 300) {$G='<SPAN class="midnightblue"><B>'; $EG='</B></SPAN>'; $tr_class='TRmidnightblue';}
			}

		if ($Astatus[$i] == 'RING')
			{
			$agent_total++;
			$G = '<SPAN class="blank">'; $EG = '</SPAN>'; $tr_class='TRblank';
			if ($call_time_S >= 0) {$G='<SPAN class="salmon"><B>'; $EG='</B></SPAN>'; $tr_class='TRsalmon';}
			}

		$L='';
		$R='';


		if ($report_display_type=='TEXT')
			{
			if ($SIPmonitorLINK>0) {$L=" <a href=\"sip:0$Lsessionid@$server_ip\">"._QXZ("LISTEN",6)."</a>";   $R='';}
			if ($IAXmonitorLINK>0) {$L=" <a href=\"iax:0$Lsessionid@$server_ip\">"._QXZ("LISTEN",6)."</a>";   $R='';}
			if ($SIPmonitorLINK>1) {$R=" | <a href=\"sip:$Lsessionid@$server_ip\">"._QXZ("BARGE",5)."</a>";}
			if ($IAXmonitorLINK>1) {$R=" | <a href=\"iax:$Lsessionid@$server_ip\">BARGE</a>";}
			if ( (strlen($monitor_phone)>1) and (preg_match("/MONITOR|BARGE|WHISPER/",$monitor_active) ) )
				{$L=" <a href=\"javascript:send_monitor('$Lsessionid','$Lserver_ip','MONITOR');\">"._QXZ("LISTEN",6)."</a>";   $R='';}
			if ( (strlen($monitor_phone)>1) and (preg_match("/BARGE|WHISPER/",$monitor_active) ) )
				{$R=" | <a href=\"javascript:send_monitor('$Lsessionid','$Lserver_ip','BARGE');\">"._QXZ("BARGE",5)."</a>";}
			if ($SIPmonitorLINK>1) {$R=" | <a href=\"sip:47378218$Lsessionid@$server_ip\">WHISPER</a>";}
			if ($IAXmonitorLINK>1) {$R=" | <a href=\"iax:47378218$Lsessionid@$server_ip\">WHISPER</a>";}
			if ( (strlen($monitor_phone)>1) and (preg_match("/WHISPER/",$monitor_active) ) )
				{$R=" | <a href=\"javascript:send_monitor('$Lsessionid','$Lserver_ip','WHISPER');\">WHISPER</a>";}

			if ($CUSTPHONEdisplay > 0)	{$CP = " $G$custphone$EG |";}
			else	{$CP = "";}

			if ($UGdisplay > 0)	{$UGD = " $G$user_group$EG |";}
			else	{$UGD = "";}

			if ($SERVdisplay > 0)	{$SVD = " $G$server_ip$EG | $G$call_server_ip$EG |";}
			else	{$SVD = "";}

			if ($PHONEdisplay > 0)	{$phoneD = "$G$phone$EG | ";}
			else	{$phoneD = " ";}

			$vac_stage='';
			$vac_campaign='';
			$INGRP='';
			if ($CM == 'I') 
				{
				$stmt="SELECT vac.campaign_id,vac.stage,vig.group_name,vig.group_id from vicidial_auto_calls vac,vicidial_inbound_groups vig where vac.callerid='$Acallerid[$i]' and vac.campaign_id=vig.group_id LIMIT 1;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$ING=$G; $INEG=$EG;
				$ingrp_to_print = mysqli_num_rows($rslt);
					if ($ingrp_to_print > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$vac_campaign =	sprintf("%-20s", "$row[0] - $row[2]");
					$row[1] = preg_replace('/.*\-/i', '',$row[1]);
					$vac_stage =	sprintf("%-4s", $row[1]);
					
					if ( ($INGROUPcolorOVERRIDE>0) or ($INGROUPcolorOVERRIDE=='YES') )
						{
						$ING='<SPAN class="csc'.$row[3].'"><B>'; $INEG='</B></SPAN>';
						}
					}
				$INGRP = " $ING$vac_stage$INEG | $ING$vac_campaign$INEG ";
				}

			$agentcount++;

			if ($realtime_block_user_info > 0)
				{
				$Aecho .= "|$UGD $G$sessionid$EG$L$R$Aring_note[$i]| $G"._QXZ("$status",6)."$EG $CM $pausecode|$CP$SVD$G$call_time_MS$EG | $G$campaign_id$EG | $G$calls_today$EG |$INGRP\n";
				}
			if ($realtime_block_user_info < 1)
				{
				$Aecho .= "| $G$extension$EG$Aring_note[$i]|$phoneD<a href=\"./user_status.php?user=$Luser\" target=\"_blank\">$G$user$EG</a> <a href=\"javascript:ingroup_info('$Luser','$j');\">+</a> |$UGD $G$sessionid$EG$L$R | $G"._QXZ("$status",6)."$EG $CM $pausecode|$CP$SVD$G$call_time_MS$EG | $G$campaign_id$EG | $G$calls_today$EG |$INGRP\n";
				}
			}
		if ($report_display_type=='HTML')
			{
			$G = preg_replace("/SPAN class=\"/",'SPAN class="H',$G);
			if ($SIPmonitorLINK>0) {$L="<td NOWRAP> <a href=\"sip:0$Lsessionid@$server_ip\">"._QXZ("LISTEN")."</a></td>";   $R='';}
			if ($IAXmonitorLINK>0) {$L="<td NOWRAP> <a href=\"iax:0$Lsessionid@$server_ip\">"._QXZ("LISTEN")."</a></td>";   $R='';}
			if ($SIPmonitorLINK>1) {$R=" <td NOWRAP> <a href=\"sip:$Lsessionid@$server_ip\">"._QXZ("BARGE")."</a></td>";}
			if ($IAXmonitorLINK>1) {$R=" <td NOWRAP> <a href=\"iax:$Lsessionid@$server_ip\">BARGE</a></td>";}
			if ( (strlen($monitor_phone)>1) and (preg_match("/MONITOR|BARGE|WHISPER/",$monitor_active) ) )
				{$L="<td NOWRAP> <a href=\"javascript:send_monitor('$Lsessionid','$Lserver_ip','MONITOR');\">"._QXZ("LISTEN")."</a></td>";   $R='';}
			if ( (strlen($monitor_phone)>1) and (preg_match("/BARGE|WHISPER/",$monitor_active) ) )
				{$R=" <td NOWRAP> <a href=\"javascript:send_monitor('$Lsessionid','$Lserver_ip','BARGE');\">"._QXZ("BARGE")."</a></td>";}
			if ($SIPmonitorLINK>1) {$R=" </td><td NOWRAP> <a href=\"sip:47378218$Lsessionid@$server_ip\">WHISPER</a></td>";}
			if ($IAXmonitorLINK>1) {$R=" </td><td NOWRAP> <a href=\"iax:47378218$Lsessionid@$server_ip\">WHISPER</a></td>";}
			if ( (strlen($monitor_phone)>1) and (preg_match("/WHISPER/",$monitor_active) ) )
				{$R=" <td NOWRAP> <a href=\"javascript:send_monitor('$Lsessionid','$Lserver_ip','WHISPER');\">WHISPER</a></td>";}

			if ($CUSTPHONEdisplay > 0)	{$CP = " $G$custphone$EG </td><td NOWRAP align=right>";}
			else	{$CP = "";}

			if ($UGdisplay > 0)	{$UGD = " $G$user_group$EG </td><td NOWRAP align=right>";}
			else	{$UGD = "";}

			if ($SERVdisplay > 0)	{$SVD = " $G$server_ip$EG </td><td NOWRAP align=right> $G$call_server_ip$EG </td><td NOWRAP align=right>";}
			else	{$SVD = "";}

			if ($PHONEdisplay > 0)	{$phoneD = "$G$phone$EG </td><td NOWRAP>";}
			else	{$phoneD = " ";}

			if ($agent_pause_codes_active > 0)	{$pausecodeHTML = "<td bgcolor=white align=center><font class='Hblank'>$pausecode</td>";}
			else	{$pausecodeHTML = "";}

			$vac_stage='';
			$vac_campaign='';
			$INGRP='';
			if ($CM == 'I') 
				{
				$stmt="SELECT vac.campaign_id,vac.stage,vig.group_name,vig.group_id from vicidial_auto_calls vac,vicidial_inbound_groups vig where vac.callerid='$Acallerid[$i]' and vac.campaign_id=vig.group_id LIMIT 1;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$ING=$G; $INEG=$EG;
				$ingrp_to_print = mysqli_num_rows($rslt);
					if ($ingrp_to_print > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$vac_campaign =	sprintf("%-20s", "$row[0] - $row[2]");
					$row[1] = preg_replace('/.*\-/i', '',$row[1]);
					$vac_stage =	sprintf("%-4s", $row[1]);
					
					if ( ($INGROUPcolorOVERRIDE>0) or ($INGROUPcolorOVERRIDE=='YES') )
						{
						$ING='<SPAN class="csc'.$row[3].'"><B>'; $INEG='</B></SPAN>';
						}
					}
				$INGRP = "<td NOWRAP align=right> $ING$vac_stage$INEG </td><td NOWRAP> $ING$vac_campaign$INEG </td>";
				}

			$agentcount++;

			if ($realtime_block_user_info > 0)
				{
				$Aecho .= "<tr class='$tr_class'><td NOWRAP>$UGD $G$sessionid$EG$L$R$Aring_note[$i]</td><td NOWRAP align=center> $G"._QXZ("$status",6)."$EG</td><td bgcolor=white align=center><font class='Hblank'>$CM</td>$pausecodeHTML<td NOWRAP align=right>$CP$SVD$G$call_time_MS$EG </td><td NOWRAP align=right> $G$campaign_id$EG </td><td NOWRAP align=right> $G$calls_today$EG </td>$INGRP</tr>\n";
				}
			if ($realtime_block_user_info < 1)
				{
				$Aecho .= "<tr class='$tr_class'><td NOWRAP> $G$extension$EG$Aring_note[$i]</td><td NOWRAP>$phoneD<a href=\"./user_status.php?user=$Luser\" target=\"_blank\">$G$user$EG</a> <a href=\"javascript:ingroup_info('$Luser','$j');\">$G+$EG</a> </td><td NOWRAP align=right>$UGD $G$sessionid$EG$L$R </td><td NOWRAP align=center> $G"._QXZ("$status",6)."$EG</td><td bgcolor=white align=center><font class='Hblank'>$CM</td>$pausecodeHTML<td NOWRAP align=right>$CP$SVD$G$call_time_MS$EG </td><td NOWRAP align=right> $G$campaign_id$EG </td><td NOWRAP align=right> $G$calls_today$EG </td>$INGRP</tr>\n";
				}
			}
		$j++;
		}
	if ($report_display_type=='HTML')
		{$Aecho .= "</table>\n";}

	$Aecho .= "$Aline";
	$Aecho .= "  $agentcount "._QXZ("agents logged in on all servers")."\n";
	$Aecho .= "  "._QXZ("System Load Average").": $load_ave  &nbsp; $db_source\n\n";

#	$Aecho .= "  <SPAN class=\"orange\"><B>          </SPAN> - "._QXZ("Balanced call")."</B>\n";
	$Aecho .= "  <SPAN class=\"red\"><B>          </SPAN> - "._QXZ("Agent chatting")."</B>\n";
	$Aecho .= "  <SPAN class=\"orange\"><B>          </SPAN> - "._QXZ("Agent in email")."</B>\n";
	$Aecho .= "  <SPAN class=\"lightblue\"><B>          </SPAN> - "._QXZ("Agent waiting for call")."</B>\n";
	$Aecho .= "  <SPAN class=\"blue\"><B>          </SPAN> - "._QXZ("Agent waiting for call > 1 minute")."</B>\n";
	$Aecho .= "  <SPAN class=\"midnightblue\"><B>          </SPAN> - "._QXZ("Agent waiting for call > 5 minutes")."</B>\n";
	$Aecho .= "  <SPAN class=\"thistle\"><B>          </SPAN> - "._QXZ("Agent on call > 10 seconds")."</B>\n";
	$Aecho .= "  <SPAN class=\"violet\"><B>          </SPAN> - "._QXZ("Agent on call > 1 minute")."</B>\n";
	$Aecho .= "  <SPAN class=\"purple\"><B>          </SPAN> - "._QXZ("Agent on call > 5 minutes")."</B>\n";
	$Aecho .= "  <SPAN class=\"khaki\"><B>          </SPAN> - "._QXZ("Agent Paused > 10 seconds")."</B>\n";
	$Aecho .= "  <SPAN class=\"yellow\"><B>          </SPAN> - "._QXZ("Agent Paused > 1 minute")."</B>\n";
	$Aecho .= "  <SPAN class=\"olive\"><B>          </SPAN> - "._QXZ("Agent Paused > 5 minutes")."</B>\n";
	$Aecho .= "  <SPAN class=\"lime\"><B>          </SPAN> - "._QXZ("Agent in 3-WAY > 10 seconds")."</B>\n";
	$Aecho .= "  <SPAN class=\"black\"><B>          </SPAN> - "._QXZ("Agent on a dead call")."</B>\n";
	if ($SSenable_pause_code_limits > 0)
		{
		$Aecho .= "  <SPAN class=\"darkred\"><B>          </SPAN> - "._QXZ("Agent over pause limit")."</B>\n";
		}

	if ($ring_agents > 0)
		{
		$Aecho .= "  <SPAN class=\"salmon\"><B>          </SPAN> - "._QXZ("Agent phone ringing")."</B>\n";
		$Aecho .= "  <SPAN><B>* "._QXZ("Denotes on-hook agent")."</B></SPAN>\n";
		}

	if ($agent_ready > 0) {$B='<FONT class="b1">'; $BG='</FONT>';}
	if ($agent_ready > 4) {$B='<FONT class="b2">'; $BG='</FONT>';}
	if ($agent_ready > 9) {$B='<FONT class="b3">'; $BG='</FONT>';}
	if ($agent_ready > 14) {$B='<FONT class="b4">'; $BG='</FONT>';}

	$AIDct='';   $AIDtx='';
	if ($agent_indial > 0) {$AIDct = "/$agent_indial";   $AIDtx = ' / '._QXZ("dials");}

	echo "\n<BR>\n";

	if ( ($report_display_type=='TEXT') or ($report_display_type=='WALL_1') )
		{
		echo "$NFB$agent_total$NFE "._QXZ("agents logged in")." &nbsp; &nbsp; &nbsp; &nbsp; \n";
		echo "$NFB$agent_incall$AIDct$NFE "._QXZ("agents in calls")."$AIDtx &nbsp; &nbsp; &nbsp; \n";
		echo "$NFB$B &nbsp;$agent_ready $BG$NFE "._QXZ("agents waiting")." &nbsp; &nbsp; &nbsp; \n";
		echo "$NFB$agent_paused$NFE "._QXZ("paused agents")." &nbsp; &nbsp; &nbsp; \n";
		echo "$NFB$agent_dead$NFE "._QXZ("agents in dead calls")."&nbsp; &nbsp; &nbsp; \n";
		echo "$NFB$agent_dispo$NFE "._QXZ("agents in dispo")."&nbsp; &nbsp; &nbsp; \n";
		if ($report_display_type=='TEXT')
			{
			echo "<PRE><FONT SIZE=2>";
			echo "";
			echo "$Cecho";
			echo "$Aecho";
			}
		}
	}
else
	{
	if ( ($report_display_type!='HTML') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
		{
		echo " "._QXZ("NO AGENTS ON CALLS")." \n";
		echo "<PRE>$Cecho";
		}
	}

if ( ($report_display_type!='HTML') and ($report_display_type!='WALL_2') and ($report_display_type!='WALL_3') and ($report_display_type!='WALL_4') )
	{
	echo "</PRE>";
	}



##### BEGIN HTML output for agents summary #####
if ( ($report_display_type=='HTML') or ($report_display_type=='WALL_2') )
	{
	$AIDct='';   $AIDtx='';
	if ($agent_indial > 0) {$AIDct = " / $agent_indial";   $AIDtx = ' / '._QXZ("dials");}

#	echo "<TABLE width=$section_width cellpadding=3 cellspacing=0>\n";
	echo "<tr>";
#	echo "<td width=5 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_users.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("agents logged in")."</font></td>";
	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_agentsincalls.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("agents in calls")."$AIDtx</font></td>";
	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91' rowspan=2><img src=\"images/icon_agentswaiting.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("agents waiting")."</font></td>";
	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#808000' rowspan=2><img src=\"images/icon_agentspaused.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#808000'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("paused agents")."</font></td>";
	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='black' rowspan=2><img src=\"images/icon_agentsindeadcalls.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='black'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("agents in dead calls")."</font></td>";
	echo "<td width=6 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#808000' rowspan=2><img src=\"images/icon_agentsindispo.png\" width=42 height=42></td>";
	echo "<td align='center' valign='middle' bgcolor='#808000'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("agents in dispo")."</font></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$agent_total</font></td>";
	echo "<td align='center' valign='middle' bgcolor='#015b91'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$agent_incall$AIDct</font></td>";

	$agents_waiting_td='bgcolor="#015b91"';   $agents_waiting_font='style="color: white; font-family: HELVETICA; font-size: 18; font-weight: bold;"';
	if ($agent_ready > 0) {$agents_waiting_td='bgcolor="#CCFFCC"';   $agents_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
	if ($agent_ready > 4) {$agents_waiting_td='bgcolor="#99FF99"';   $agents_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
	if ($agent_ready > 9) {$agents_waiting_td='bgcolor="#00FF00"';   $agents_waiting_font='style="color: black; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
	if ($agent_ready > 14) {$agents_waiting_td='bgcolor="#00CC00"';  $agents_waiting_font='class="color: white; font-family: HELVETICA; font-size: 18; font-weight: bold;"';}
	echo "<td align='center' valign='middle' $agents_waiting_td><font $agents_waiting_font>$agent_ready</font></td>";

	echo "<td align='center' valign='middle' bgcolor='#808000'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$agent_paused</font></td>";
	echo "<td align='center' valign='middle' bgcolor='black'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$agent_dead</font></td>";
	echo "<td align='center' valign='middle' bgcolor='#808000'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$agent_dispo</font></td>";
	echo "</tr>";
	echo "</TABLE>";
	echo "\n";

	if ($report_display_type=='HTML')
		{
		echo "<PRE><FONT SIZE=2>";
		echo "";
		echo "$Cecho";
		echo "$Aecho";
		}
	}
##### END HTML output for agents summary #####



##### BEGIN custom WALL_3 display option #####
if ($report_display_type=='WALL_3')
	{
	$AIDct='';   $AIDtx='';
	if ($agent_indial > 0) {$AIDct = " / $agent_indial";   $AIDtx = ' / '._QXZ("dials");}
	echo "<font style=\"font-size:48; color:black; font-weight: bold; background-color:green\">"._QXZ("agents waiting")."</font>\n";
	echo "<font style=\"font-size:72; color:black; font-weight: bold; background-color:green\"> &nbsp; $agent_ready</font><br>\n";
	echo "<font style=\"font-size:48; color:black; font-weight: bold; background-color:yellow\">"._QXZ("paused agents")."</font>\n";
	echo "<font style=\"font-size:72; color:black; font-weight: bold; background-color:yellow\"> &nbsp; $agent_paused</font><br>\n";
	echo "<font style=\"font-size:48; color:black; font-weight: bold; background-color:green\">"._QXZ("agents in calls")."$AIDtx</font>\n";
	echo "<font style=\"font-size:72; color:black; font-weight: bold; background-color:green\"> &nbsp; $agent_incall$AIDct</font><br>\n";
	echo "<font style=\"font-size:48; color:black; font-weight: bold; background-color:red\">"._QXZ("calls waiting")."</font>\n";
	echo "<font style=\"font-size:72; color:black; font-weight: bold; background-color:red\"> &nbsp; $out_live</font><br>\n";
	echo "<font style=\"font-size:36; color:black; font-weight: bold;\">"._QXZ("dropped/total calls")."</font>\n";
	echo "<font style=\"font-size:48; color:black; font-weight: bold;\"> &nbsp; $dropsTODAY / $callsTODAY</font> $drpctTODAY %<br>\n";
	}
##### END custom WALL_3 display option #####


##### BEGIN custom WALL_4 display option #####
if ($report_display_type=='WALL_4')
	{
	$AIDct='';   $AIDtx='';
	if ($agent_indial > 0) {$AIDct = " / $agent_indial";   $AIDtx = ' / '._QXZ("dials");}
	echo "<center><table border=0 cellpadding=3 cellspacing=3>\n";
	echo "<tr>\n";
	echo "<td><font style=\"font-size:48; color:black; font-weight: bold; background-color:green\">"._QXZ("agents waiting")."</font></td>";
	echo "<td><font style=\"font-size:72; color:black; font-weight: bold; background-color:green\"> &nbsp; $agent_ready</font></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td><font style=\"font-size:48; color:black; font-weight: bold; background-color:yellow\">"._QXZ("paused agents")."</font></td>";
	echo "<td><font style=\"font-size:72; color:black; font-weight: bold; background-color:yellow\"> &nbsp; $agent_paused</font></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td><font style=\"font-size:48; color:black; font-weight: bold; background-color:green\">"._QXZ("agents in calls")."$AIDtx</font></td>";
	echo "<td><font style=\"font-size:72; color:black; font-weight: bold; background-color:green\"> &nbsp; $agent_incall$AIDct</font></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td><font style=\"font-size:48; color:black; font-weight: bold; background-color:red\">"._QXZ("calls waiting")."</font></td>";
	echo "<td><font style=\"font-size:72; color:black; font-weight: bold; background-color:red\"> &nbsp; $out_live</font></td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td><font style=\"font-size:36; color:black; font-weight: bold;\">"._QXZ("dropped/total calls")."</font>\n";
	echo "<font style=\"font-size:48; color:black; font-weight: bold;\"> &nbsp; $dropsTODAY / $callsTODAY</font></td>\n";
	echo "<td><font style=\"font-size:18; color:black; font-weight: bold;\"> &nbsp;  $drpctTODAY %</td>\n";
	echo "</tr>\n";
	echo "</table></center>\n";
	}
##### END custom WALL_4 display option #####


if ($RTajax < 1)
	{
	echo "</TD></TR></TABLE>";
	}
?>
<!-- <?php echo $group_string; ?> -->
</BODY></HTML>
