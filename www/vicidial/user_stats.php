<?php
# user_stats.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60619-1743 - Added variable filtering to eliminate SQL injection attack threat
# 61201-1136 - Added recordings display and changed calls to time range with 10000 limit
# 70118-1605 - Added user group column to login/out and calls lists
# 70702-1231 - Added recording location link and truncation
# 80117-0316 - Added vicidial_user_closer_log entries to display
# 80501-0506 - Added Hangup Reason to logs display
# 80523-2012 - Added vicidial timeclock records display
# 80617-1402 - Fixed timeclock total logged-in time
# 81210-1634 - Added server recording display options
# 90208-0504 - Added link to multi-day report and fixed call status summary section
# 90305-1226 - Added user_call_log manual dial logs
# 90310-0734 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 90524-2009 - Changed time display to use functions.php
# 91130-2037 - Added user closer log manager flag display
# 100126-0847 - Added DID log display options
# 100203-1008 - Added agent activity log section
# 100216-0042 - Added popup date selector
# 100425-0115 - Added more login data
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100908-1205 - Added customer 3way hangup flags to user calls display
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110218-1523 - Added searches display
# 110703-1836 - Added download option
# 110718-1204 - Added skipped manual dial leads display
# 111103-1050 - Added admin_hide_phone_data and admin_hide_lead_data options
# 111106-1105 - Added user_group restrictions
# 120223-2135 - Removed logging of good login passwords if webroot writable is enabled
# 121222-2152 - Added email log display
# 130124-1740 - Added option to display first and last name of lead
# 130414-0146 - Added report logging
# 130610-0938 - Finalized changing of all ereg instances to preg
# 130616-0059 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130625-1341 - Added phone login and phone_ip display to login/logout section
# 130901-0836 - Changed to mysqli PHP functions
# 131016-2102 - Added checking for level 8 add-copy restriction
# 131122-0705 - Added pause_code_rpt option coming from Agent Time Detail report
# 140108-0709 - Added webserver and hostname to report logging
# 141114-0025 - Finalized adding QXZ translation to all admin files
# 141229-1836 - Added code for on-the-fly language translations display
# 150804-0749 - Added call status as option, Issue #883
# 151227-1839 - Added option to search archived data
# 160104-1226 - Added call type field to the agent activity table
# 160112-0759 - Added link to direct to recording logging page
# 160325-1430 - Changes for sidebar update
# 160508-0807 - Added colors features
# 161030-0829 - Fixed excess load Issue #963
# 170228-1626 - Change to display emergency manager logout note
# 170409-1555 - Added IP List validation code
# 170412-2150 - Added park_rpt display option
# 180323-2308 - Fix for user time calculation, subtracted queue_seconds
# 180410-1754 - Added Agent lead switch log and manager pause code approval log displays
# 190310-2206 - Added indication of muted recordings by agent
# 191013-0843 - Fixes for PHP7
# 200501-0811 - Added NVAuser option for NVA recordings user column
# 200702-1710 - Added ANI to INBOUND/CLOSER records for NVAuser, added secondary check to find lead ID for DIDs 
# 210317-0058 - Changed lead-modify page links to javascript because of Chrome
# 210319-1338 - Added HELP pop-ups for each section, added agent browser visibility log stats to agent activity section, reformatted most sections
# 220122-1701 - Added more variable filtering
# 220221-0916 - Added allow_web_debug system setting
# 220310-1427 - Fix for LOGOUT/LOGIN events sharing the same timedate
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name = 'User Stats';
$db_source = 'M';

$firstlastname_display_user_stats=0;
$add_copy_disabled=0;
if (file_exists('options.php'))
	{
	require('options.php');
	}

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["did_id"]))					{$did_id=$_GET["did_id"];}
	elseif (isset($_POST["did_id"]))		{$did_id=$_POST["did_id"];}
if (isset($_GET["did"]))					{$did=$_GET["did"];}
	elseif (isset($_POST["did"]))			{$did=$_POST["did"];}
if (isset($_GET["begin_date"]))				{$begin_date=$_GET["begin_date"];}
	elseif (isset($_POST["begin_date"]))	{$begin_date=$_POST["begin_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["call_status"]))			{$call_status=$_GET["call_status"];}
	elseif (isset($_POST["call_status"]))	{$call_status=$_POST["call_status"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))		{$file_download=$_POST["file_download"];}
if (isset($_GET["pause_code_rpt"]))				{$pause_code_rpt=$_GET["pause_code_rpt"];}
	elseif (isset($_POST["pause_code_rpt"]))	{$pause_code_rpt=$_POST["pause_code_rpt"];}
if (isset($_GET["park_rpt"]))				{$park_rpt=$_GET["park_rpt"];}
	elseif (isset($_POST["park_rpt"]))		{$park_rpt=$_POST["park_rpt"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["NVAuser"]))			{$NVAuser=$_GET["NVAuser"];}
	elseif (isset($_POST["NVAuser"]))	{$NVAuser=$_POST["NVAuser"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$STARTtime = date("U");
$TODAY = date("Y-m-d");

if ( (!isset($begin_date)) or (strlen($begin_date) < 10) ) {$begin_date = $TODAY;}
if ( (!isset($end_date)) or (strlen($end_date) < 10) ) {$end_date = $TODAY;}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,user_territories_active,webroot_writable,allow_emails,level_8_disable_add,enable_languages,language_method,log_recording_access,admin_screen_colors,mute_recordings,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSoutbound_autodial_active =	$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$user_territories_active =		$row[4];
	$webroot_writable =				$row[5];
	$allow_emails =					$row[6];
	$SSlevel_8_disable_add =		$row[7];
	$SSenable_languages =			$row[8];
	$SSlanguage_method =			$row[9];
	$log_recording_access =			$row[10];
	$SSadmin_screen_colors =		$row[11];
	$SSmute_recordings =			$row[12];
	$SSallow_web_debug =			$row[13];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_log", "vicidial_agent_log", "vicidial_closer_log", "vicidial_user_log", "vicidial_timeclock_log", "vicidial_user_closer_log", "vicidial_email_log", "call_log", "recording_log", "user_call_log", "vicidial_lead_search_log", "vicidial_agent_skip_log","vicidial_agent_visibility_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	$vicidial_user_log_table=use_archive_table("vicidial_user_log");
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	$vicidial_agent_visibility_log_table="vicidial_agent_visibility_log";
	$vicidial_timeclock_log_table=use_archive_table("vicidial_timeclock_log");
	$vicidial_user_closer_log_table=use_archive_table("vicidial_user_closer_log");
	$vicidial_email_log_table=use_archive_table("vicidial_email_log");
	$recording_log_table=use_archive_table("recording_log");
	$user_call_log_table=use_archive_table("user_call_log");
	$vicidial_lead_search_log_table=use_archive_table("vicidial_lead_search_log");
	$vicidial_agent_skip_log_table=use_archive_table("vicidial_agent_skip_log");
	$vicidial_agent_function_log=use_archive_table("vicidial_agent_function_log");
	$call_log_table=use_archive_table("call_log");
	$vicidial_log_table=use_archive_table("vicidial_log");
	}
else
	{
	$vicidial_closer_log_table="vicidial_closer_log";
	$vicidial_user_log_table="vicidial_user_log";
	$vicidial_agent_log_table="vicidial_agent_log";
	$vicidial_agent_visibility_log_table="vicidial_agent_visibility_log";
	$vicidial_timeclock_log_table="vicidial_timeclock_log";
	$vicidial_user_closer_log_table="vicidial_user_closer_log";
	$vicidial_email_log_table="vicidial_email_log";
	$recording_log_table="recording_log";
	$user_call_log_table="user_call_log";
	$vicidial_lead_search_log_table="vicidial_lead_search_log";
	$vicidial_agent_skip_log_table="vicidial_agent_skip_log";
	$vicidial_agent_function_log="vicidial_agent_function_log";
	$call_log_table="call_log";
	$vicidial_log_table="vicidial_log";
	}
#############

$did_id = preg_replace('/[^-\+\_0-9a-zA-Z]/',"",$did_id);
$did = preg_replace('/[^-\+\_0-9a-zA-Z]/',"",$did);
$begin_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$begin_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$end_date);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$pause_code_rpt = preg_replace('/[^-_0-9a-zA-Z]/', '', $pause_code_rpt);
$park_rpt = preg_replace('/[^-_0-9a-zA-Z]/', '', $park_rpt);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$NVAuser = preg_replace('/[^-_0-9a-zA-Z]/','',$NVAuser);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	$call_status = preg_replace('/[^-_0-9a-zA-Z]/', '', $call_status);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$NVAuser = preg_replace('/[^-_0-9\p{L}]/u','',$NVAuser);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	$call_status = preg_replace('/[^-_0-9\p{L}]/u', '', $call_status);
	}

if ($call_status != "") 
	{
	$query_call_status = "and status='$call_status'";
	$VLquery_call_status = "and vlog.status='$call_status'";
	}
else 
	{
	$query_call_status = '';
	$VLquery_call_status = '';
	}
$CS_vicidial_id_list='';
$CS_vicidial_id_list_SQL='';

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
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
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


$stmt="SELECT user_level from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_level				=$row[0];

if (($LOGuser_level < 9) and ($SSlevel_8_disable_add > 0))
	{$add_copy_disabled++;}


require("screen_colors.php");

$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;


$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

##### BEGIN log visit to the vicidial_report_log table #####
$LOGip = getenv("REMOTE_ADDR");
$LOGbrowser = getenv("HTTP_USER_AGENT");
$LOGscript_name = getenv("SCRIPT_NAME");
$LOGserver_name = getenv("SERVER_NAME");
$LOGserver_port = getenv("SERVER_PORT");
$LOGrequest_uri = getenv("REQUEST_URI");
$LOGhttp_referer = getenv("HTTP_REFERER");
$LOGbrowser=preg_replace("/\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/\'|\"|\\\\/","",$LOGhttp_referer);
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$user, $query_date, $end_date, $call_status, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT full_name,user_group,admin_hide_lead_data,admin_hide_phone_data from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGuser_group =			$row[1];
$LOGadmin_hide_lead_data =	$row[2];
$LOGadmin_hide_phone_data =	$row[3];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =		$row[0];
$LOGallowed_reports =		$row[1];
$LOGadmin_viewable_groups =	$row[2];

$LOGadmin_viewable_groupsSQL='';
$vuLOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vuLOGadmin_viewable_groupsSQL = "and vicidial_users.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";

	if (strlen($user) > 0)
		{
		if ($did > 0)
			{
			$stmt="SELECT count(*) from vicidial_inbound_dids where did_pattern='$user' $LOGadmin_viewable_groupsSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$allowed_count = $row[0];
			}
		else
			{
			$stmt="SELECT count(*) from vicidial_users where user='$user' $LOGadmin_viewable_groupsSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$allowed_count = $row[0];
			}

		if ($allowed_count < 1)
			{
			echo _QXZ("This user does not exist").": |$PHP_AUTH_USER|$user|\n";
			exit;
			}
		}
	}

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

if ($did > 0)
	{
	$stmt="SELECT did_description from vicidial_inbound_dids where did_pattern='$user' $LOGadmin_viewable_groupsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$full_name = $row[0];
	}
else
	{
	$stmt="SELECT full_name from vicidial_users where user='$user' $LOGadmin_viewable_groupsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$full_name = $row[0];
	}



$HEADER.="<html>\n";
$HEADER.="<head>\n";
$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";

$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>\n";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";

if ($did > 0)
	{$HEADER.="<title>"._QXZ("ADMINISTRATION: DID Call Stats");}
else
	{$HEADER.="<title>"._QXZ("ADMINISTRATION").": "._QXZ("$report_name");}



##### BEGIN Set variables to make header show properly #####
$ADD =					'3';
$hh =					'users';
$sh =					'stats';
$LOGast_admin_access =	'1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$users_color =		'#FFFF99';
$users_font =		'BLACK';
$users_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';

if ($did > 0)
	{
	$hh =	'ingroups';
	$sh =	'listdid';
	$ADD =	'3311';
	$ingroups_color =		'#FFFF99';
	$ingroups_font =		'BLACK';
	$ingroups_color =		'#E6E6E6';
	}
##### END Set variables to make header show properly #####

#require("admin_header.php");
$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";



$MAIN.="<TABLE WIDTH=970 BGCOLOR=#E6E6E6 cellpadding=2 cellspacing=0><TR BGCOLOR=#E6E6E6><TD ALIGN=LEFT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>\n";
if ($did > 0)
	{$MAIN.="<B> &nbsp; "._QXZ("DID Call Stats for")." $user";}
else
	{$MAIN.="<B> &nbsp; "._QXZ("User Stats for")." $user";}

$MAIN.="</TD><TD ALIGN=RIGHT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2> &nbsp; </TD></TR>\n";

$MAIN.="<TR BGCOLOR=\"#$SSframe_background\"><TD ALIGN=LEFT COLSPAN=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><B> &nbsp; \n";

$download_link="$PHP_SELF?DB=$DB&pause_code_rpt=$pause_code_rpt&park_rpt=$park_rpt&did_id=$did_id&did=$did&begin_date=$begin_date&end_date=$end_date&user=$user&submit=$submit&search_archived_data=$search_archived_data&NVAuser=$NVAuser\n";

$MAIN.="<form action=$PHP_SELF method=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<input type=hidden name=DB value=\"$DB\">\n";
$MAIN.="<input type=hidden name=NVAuser value=\"$NVAuser\">\n";
$MAIN.="<input type=hidden name=did_id value=\"$did_id\">\n";
$MAIN.="<input type=hidden name=did value=\"$did\">\n";
$MAIN.="<input type=hidden name=pause_code_rpt value=\"$pause_code_rpt\">\n";
$MAIN.="<input type=hidden name=park_rpt value=\"$park_rpt\">\n";
$MAIN.="<input type=text name=begin_date value=\"$begin_date\" size=10 maxsize=10>";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'begin_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

$MAIN.=" "._QXZ("to")." <input type=text name=end_date value=\"$end_date\" size=10 maxsize=10>";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'end_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script> $NWB#user_stats-date_field$NWE\n";

if (strlen($user)>1)
	{$MAIN.="<input type=hidden name=user value=\"$user\"> &nbsp; &nbsp; &nbsp; \n";}
else
	{$MAIN.="<input type=text name=user size=12 maxlength=10> &nbsp; &nbsp; &nbsp; \n";}
$MAIN.=_QXZ("Call status").": <input type=text name=call_status size=7 maxlength=6 value=\"$call_status\"> $NWB#user_stats-call_status$NWE\n";

if ($archives_available=="Y") 
	{
	$MAIN.=" &nbsp; <input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")." $NWB#user_stats-search_archived_data$NWE\n";
	}

$MAIN.=" &nbsp; <input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'>\n";


$MAIN.=" &nbsp; &nbsp; &nbsp; $user - $full_name<BR><BR>\n";

$MAIN.="<center>\n";
if ($did > 0)
	{
	$MAIN.="<a href=\"./AST_DIDstats.php?group[0]=$did_id&query_date=$begin_date&end_date=$end_date\">"._QXZ("DID traffic report")."</a>\n";
	$MAIN.=" | <a href=\"./admin.php?ADD=3311&did_id=$did_id\">"._QXZ("Modify DID")."</a>\n";
	}
else
	{
	$MAIN.="<a href=\"./AST_agent_time_sheet.php?agent=$user\">"._QXZ("Agent Time Sheet")."</a>\n";
	$MAIN.=" | <a href=\"./user_status.php?user=$user\">"._QXZ("User Status")."</a>\n";
	$MAIN.=" | <a href=\"./admin.php?ADD=3&user=$user\">"._QXZ("Modify User")."</a>\n";
	$MAIN.=" | <a href=\"./AST_agent_days_detail.php?user=$user&query_date=$begin_date&end_date=$end_date&group[]=--ALL--&shift=ALL\">"._QXZ("User multiple day status detail report")."</a>";
	}
$MAIN.="</center>\n";


$MAIN.="</B></TD></TR>\n";
$MAIN.="<TR><TD ALIGN=LEFT COLSPAN=2>\n";

$MAIN.="<br><center>\n";

if ($pause_code_rpt >= 1)
	{
	$stmt="SELECT ".$vicidial_agent_log_table.".campaign_id,event_time,talk_sec,pause_sec,sec_to_time(pause_sec) as pause_length,wait_sec,dispo_sec,dead_sec,sub_status, vicidial_users.user_group from vicidial_users,".$vicidial_agent_log_table.",vicidial_pause_codes where sub_status is not null and event_time <= '$end_date 23:59:59' and event_time >= '$begin_date 00:00:00' and ".$vicidial_agent_log_table.".user='$user' and ".$vicidial_agent_log_table.".user=vicidial_users.user and pause_sec<65000 and wait_sec<65000 and talk_sec<65000 and dispo_sec<65000 and ".$vicidial_agent_log_table.".sub_status=vicidial_pause_codes.pause_code and ".$vicidial_agent_log_table.".campaign_id=vicidial_pause_codes.campaign_id order by event_time asc limit 500000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Agent Pause Logs").": $NWB#user_stats-agent_pause_logs$NWE </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=11'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";

	$MAIN.="<center><TABLE width=600 cellspacing=0 cellpadding=1>\n";
	$MAIN.="<tr><td><font size=2>"._QXZ("EVENT TIME")."</td><td align=right><font size=2>"._QXZ("CAMPAIGN ID")."</td><td align=right><font size=2>"._QXZ("USER GROUP")."</td><td align=right><font size=2>"._QXZ("PAUSE CODE")."</td><td align=right><font size=2>"._QXZ("PAUSE LENGTH (HH:MM:SS)")."</td></tr>\n";

	$CSV_text11.="\""._QXZ("Agent Pause Logs")."\"\n";
	$CSV_text11.="\"\",\""._QXZ("EVENT TIME")."\",\""._QXZ("CAMPAIGN ID")."\",\""._QXZ("USER GROUP")."\",\""._QXZ("PAUSE CODE")."\",\""._QXZ("PAUSE LENGTH (HH:MM:SS)")."\"\n";

	$o=0; $total_pause_time=0;
	while ($pause_row=mysqli_fetch_array($rslt))
		{
		$total_pause_time+=$pause_row["pause_sec"];
		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
		else
			{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

		$MAIN.="<tr $bgcolor><td><font size=2>$pause_row[event_time]</td>";
		$MAIN.="<td align=right><font size=2> $pause_row[campaign_id]</td>\n";
		$MAIN.="<td align=right><font size=2> $pause_row[user_group]</td>\n";
		$MAIN.="<td align=right><font size=2> $pause_row[sub_status]</td>\n";
		$MAIN.="<td align=right><font size=2> $pause_row[pause_length]</td></tr>\n";
		$CSV_text11.="\"\",\"$pause_row[event_time]\",\"$pause_row[campaign_id]\",\"$pause_row[user_group]\",\"$pause_row[sub_status]\",\"$pause_row[pause_length]\"\n";

		$o++;
		}

	$total_pause_stmt="SELECT sec_to_time($total_pause_time)";
	$total_pause_rslt=mysql_to_mysqli($total_pause_stmt, $link);
	$total_pause_row=mysqli_fetch_row($total_pause_rslt);
	$total_pause_time=$total_pause_row[0];

	$MAIN.="<tr><td><font size=2>&nbsp; </td><td><font size=2>&nbsp; </td><TD><font size=2>&nbsp; </td><td align=right><font size=2> <B>"._QXZ("TOTAL").":</B></td><td align=right><font size=2> $total_pause_time</td></tr>\n";
	$CSV_text11.="\"\",\"\",\"\",\"\",\""._QXZ("TOTAL").":\",\"$total_pause_time\"\n";
	$MAIN.="</TABLE></center>\n";
	$MAIN.="<B><a href='user_stats.php?DB=$DB&user=$user&begin_date=$begin_date&end_date=$end_date'>["._QXZ("VIEW USER STATS")."]</a></B>\n";
	}
elseif ($park_rpt >= 1)
	{
	$stmt="SELECT parked_time,status,lead_id,parked_sec from park_log where parked_time <= '$end_date 23:59:59' and parked_time >= '$begin_date 00:00:00' and user='$user' order by parked_time asc limit 500000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Agent Parked Call Logs").": $NWB#user_stats-agent_parked_call_logs$NWE </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=12'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";

	$MAIN.="<center><TABLE width=600 cellspacing=0 cellpadding=1>\n";
	$MAIN.="<tr><td><font size=2>"._QXZ("PARKED TIME")."</td><td align=right><font size=2>"._QXZ("STATUS")."</td><td align=right><font size=2>"._QXZ("LEAD ID")."</td><td align=right><font size=2>"._QXZ("PARKED SEC")."</td></tr>\n";

	$CSV_text12.="\""._QXZ("Agent Parked Call Logs")."\"\n";
	$CSV_text12.="\"\",\""._QXZ("PARKED TIME")."\",\""._QXZ("STATUS")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("PARKED SEC")."\"\n";

	$o=0; $total_park_time=0;
	while ($park_row=mysqli_fetch_array($rslt))
		{
		$total_park_time+=$park_row["parked_sec"];
		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
		else
			{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}
		$MAIN.="<tr $bgcolor><td><font size=2>$park_row[parked_time]</td>";
		$MAIN.="<td align=right><font size=2> $park_row[status]</td>\n";
		$MAIN.="<td align=right><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$park_row[lead_id]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$park_row[lead_id]', '_blank');return false;\">$park_row[lead_id]</a></td>\n";
		$MAIN.="<td align=right><font size=2> $park_row[parked_sec]</td></tr>\n";
		$CSV_text12.="\"\",\"$park_row[parked_time]\",\"$park_row[status]\",\"$park_row[lead_id]\",\"$park_row[parked_sec]\"\n";

		$o++;
		}

	$total_park_stmt="SELECT sec_to_time($total_park_time)";
	$total_park_rslt=mysql_to_mysqli($total_park_stmt, $link);
	$total_park_row=mysqli_fetch_row($total_park_rslt);
	$total_park_time=$total_park_row[0];

	$MAIN.="<tr><td><font size=2>&nbsp; </td><td><font size=2>&nbsp; </td><td align=right><font size=2> <B>"._QXZ("TOTAL").":</B></td><td align=right><font size=2> $total_park_time</td></tr>\n";
	$CSV_text11.="\"\",\"\",\"\",\""._QXZ("TOTAL").":\",\"$total_park_time\"\n";
	$MAIN.="</TABLE></center>\n";
	$MAIN.="<B><a href='user_stats.php?DB=$DB&user=$user&begin_date=$begin_date&end_date=$end_date'>["._QXZ("VIEW USER STATS")."]</a></B>\n";
	}
else
	{
	if ($did < 1)
		{
		##### vicidial agent talk time and status #####
		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Agent Talk Time and Status").": $NWB#user_stats-agent_talk_time_and_status$NWE </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=1'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";

		$MAIN.="<center><TABLE width=300 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=2>"._QXZ("STATUS")."</td><td align=right><font size=2>"._QXZ("COUNT")."</td><td align=right><font size=2>"._QXZ("HOURS:MM:SS")."</td></tr>\n";

		$CSV_text1.="\""._QXZ("Agent Talk Time and Status")."\"\n";
		$CSV_text1.="\"\",\""._QXZ("STATUS")."\",\""._QXZ("COUNT")."\",\""._QXZ("HOURS:MM:SS")."\"\n";

		$stmt="SELECT count(*),status, sum(length_in_sec) from ".$vicidial_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and call_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and call_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' $query_call_status group by status order by status";
		$rslt=mysql_to_mysqli($stmt, $link);
		$VLstatuses_to_print = mysqli_num_rows($rslt);
		$total_calls=0;
		$o=0;   $p=0;
		$counts=array();
		$status=array();
		$call_sec=array();
		while ($VLstatuses_to_print > $o) 
			{
			$row=mysqli_fetch_row($rslt);
			$counts[$p] =		$row[0];
			$status[$p] =		$row[1];
			$call_sec[$p] =		$row[2];
			$p++;
			$o++;
			}

		$stmt="SELECT count(*),status, sum(length_in_sec-queue_seconds) from ".$vicidial_closer_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and call_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and call_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' $query_call_status group by status order by status";
		$rslt=mysql_to_mysqli($stmt, $link);
		$VCLstatuses_to_print = mysqli_num_rows($rslt);
		$o=0;
		while ($VCLstatuses_to_print > $o) 
			{
			$status_match=0;
			$r=0;
			$row=mysqli_fetch_row($rslt);
			while ($VLstatuses_to_print > $r) 
				{
				if ($status[$r] == $row[1])
					{
					$counts[$r] = ($counts[$r] + $row[0]);
					$call_sec[$r] = ($call_sec[$r] + $row[2]);
					$status_match++;
					}
				$r++;
				}
			if ($status_match < 1)
				{
				$counts[$p] =		$row[0];
				$status[$p] =		$row[1];
				$call_sec[$p] =		$row[2];
				$VLstatuses_to_print++;
				$p++;
				}
			$o++;
			}

		$o=0;
		$total_sec=0;
		while ($o < $p)
			{
			if (preg_match('/1$|3$|5$|7$|9$/i', $o))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$call_hours_minutes =		sec_convert($call_sec[$o],'H'); 

			$MAIN.="<tr $bgcolor><td><font size=2>$status[$o]</td>";
			$MAIN.="<td align=right><font size=2> $counts[$o]</td>\n";
			$MAIN.="<td align=right><font size=2> $call_hours_minutes</td></tr>\n";
			$CSV_text1.="\"\",\"$status[$o]\",\"$counts[$o]\",\"$call_hours_minutes\"\n";
			$total_calls = ($total_calls + $counts[$o]);
			$total_sec = ($total_sec + $call_sec[$o]);
			$call_seconds=0;
			$o++;
			}

		$call_hours_minutes =		sec_convert($total_sec,'H'); 

		$MAIN.="<tr><td><font size=2>"._QXZ("TOTAL CALLS")." </td><td align=right><font size=2> $total_calls</td><td align=right><font size=2> $call_hours_minutes</td></tr>\n";
		$CSV_text1.="\"\",\""._QXZ("TOTAL CALLS")."\",\"$total_calls\",\"$call_hours_minutes\"\n";
		$MAIN.="</TABLE></center>\n";


		##### Login and Logout time from vicidial agent interface #####

		$MAIN.="<br><br>\n";

		$MAIN.="<center>\n";

		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Agent Login and Logout Time").": $NWB#user_stats-agent_login_and_logout_time$NWE </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=2'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=900 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=2>"._QXZ("EVENT")." </td><td align=right><font size=2> "._QXZ("DATE")."</td><td align=right><font size=2> "._QXZ("CAMPAIGN")."</td><td align=right><font size=2> "._QXZ("GROUP")."</td><td align=right><font size=2>"._QXZ("SESSION")."<BR>"._QXZ("HOURS:MM:SS")."</td><td align=right><font size=2>"._QXZ("SERVER")."</td><td align=right><font size=2>"._QXZ("PHONE")."</td><td align=right><font size=2>"._QXZ("COMPUTER")."</td><td align=right><font size=2>"._QXZ("PHONE")."<BR>"._QXZ("LOGIN")."</td><td align=right><font size=2>"._QXZ("PHONE IP")."</td></tr>\n";

		$CSV_text2.="\""._QXZ("Agent Login and Logout Time")."\"\n";
		$CSV_text2.="\"\",\""._QXZ("EVENT")."\",\""._QXZ("DATE")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("GROUP")."\",\""._QXZ("HOURS:MM:SS")."\",\""._QXZ("SESSION")."\",\""._QXZ("SERVER")."\",\""._QXZ("PHONE")."\",\""._QXZ("COMPUTER")."\",\""._QXZ("PHONE_LOGIN")."\",\""._QXZ("PHONE_IP")."\"\n";

		$stmt="SELECT event,event_epoch,event_date,campaign_id,user_group,session_id,server_ip,extension,computer_ip,phone_login,phone_ip,if(event='LOGOUT' or event='TIMEOUTLOGOUT', 1, 0) as LOGpriority from ".$vicidial_user_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and event_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and event_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' order by event_date, LOGpriority asc;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$events_to_print = mysqli_num_rows($rslt);

		$total_calls=0;
		$o=0;
		$event_start_seconds='';
		$event_stop_seconds='';
		while ($events_to_print > $o) 
			{
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/LOGIN/i", $row[0]))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			if (preg_match('/LOGIN/', $row[0]))
				{
				if ($row[10]=='LOOKUP')
					{$row[10]='';}
				$event_start_seconds = $row[1];
				$MAIN.="<tr $bgcolor><td><font size=2>$row[0] </td>";
				$MAIN.="<td align=right><font size=2> $row[2] </td>\n";
				$MAIN.="<td align=right><font size=2> $row[3] </td>\n";
				$MAIN.="<td align=right><font size=2> $row[4] </td>\n";
				$MAIN.="<td align=right><font size=2> $row[5] </td>\n";
				$MAIN.="<td align=right><font size=2> $row[6] </td>\n";
				$MAIN.="<td align=right><font size=2> $row[7] </td>\n";
				$MAIN.="<td align=right><font size=2> $row[8] </td>\n";
				$MAIN.="<td align=right><font size=2> $row[9] </td>\n";
				$MAIN.="<td align=right><font size=2> $row[10] </td>\n";
				$MAIN.="</tr>\n";
				$CSV_text2.="\"\",\"$row[0]\",$row[2]\",\"$row[3]\",\"$row[4]\",\"\",\"$row[5]\",\"$row[6]\",\"$row[7]\",\"$row[8]\",\"$row[9]\",\"$row[10]\"\n";
				}
			if (preg_match('/LOGOUT/', $row[0]))
				{
				if ($event_start_seconds)
					{

					$event_stop_seconds = $row[1];
					$event_seconds = ($event_stop_seconds - $event_start_seconds);
					$total_login_time = ($total_login_time + $event_seconds);
					$event_hours_minutes =		sec_convert($event_seconds,'H'); 

					$MAIN.="<tr $bgcolor><td><font size=2>$row[0]</td>";
					$MAIN.="<td align=right><font size=2> $row[2]</td>\n";
					$MAIN.="<td align=right><font size=2> $row[3]</td>\n";
					$MAIN.="<td align=right><font size=2> $row[4]</td>\n";
					$MAIN.="<td align=right><font size=2> $event_hours_minutes &nbsp;</td>\n";
					$MAIN.="<td align=center colspan=2><font size=2>&nbsp; $row[7]</td>\n";
					$MAIN.="<td align=right colspan=3><font size=2> &nbsp;</td></tr>\n";
					$event_start_seconds='';
					$event_stop_seconds='';
					$CSV_text2.="\"\",\"$row[0]\",\"$row[2]\",\"$row[3]\",\"$row[4]\",\"$event_hours_minutes\"\n";
					}
				else
					{
					$MAIN.="<tr $bgcolor><td><font size=2>$row[0]</td>";
					$MAIN.="<td align=right><font size=2> $row[2]</td>\n";
					$MAIN.="<td align=right><font size=2> $row[3]</td>\n";
					$MAIN.="<td align=right><font size=2> </td>\n";
					$MAIN.="<td align=right colspan=5><font size=2> &nbsp;</td></tr>\n";
					$CSV_text2.="\"\",\"$row[0]\",\"$row[2]\",\"$row[3]\"\n";
					}
				}

			$total_calls++;

			$call_seconds=0;
			$o++;
			}

		$total_login_hours_minutes =		sec_convert($total_login_time,'H'); 

		$MAIN.="<tr><td><font size=2>"._QXZ("TOTAL")."</td>";
		$MAIN.="<td align=right><font size=2> </td>\n";
		$MAIN.="<td align=right><font size=2> </td>\n";
		$MAIN.="<td align=right><font size=2> </td>\n";
		$MAIN.="<td align=right><font size=2> $total_login_hours_minutes</td></tr>\n";
		$CSV_text2.="\"\",\""._QXZ("TOTAL")."\",\"\",\"\",\"\",\"$total_login_hours_minutes\"\n";

		$MAIN.="</TABLE></center>\n";





		##### vicidial_timeclock log records for user #####

		$total_login_time=0;
		$SQday_ARY =	explode('-',$begin_date);
		$EQday_ARY =	explode('-',$end_date);
		$SQepoch = mktime(0, 0, 0, $SQday_ARY[1], $SQday_ARY[2], $SQday_ARY[0]);
		$EQepoch = mktime(23, 59, 59, $EQday_ARY[1], $EQday_ARY[2], $EQday_ARY[0]);

		$MAIN.="<br><br>\n";

		$MAIN.="<center>\n";

		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Timeclock Login and Logout Time").": $NWB#user_stats-timeclock_login_and_logout_time$NWE </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=3'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=550 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=2>"._QXZ("ID")." </td><td><font size=2>"._QXZ("EDIT")." </td><td align=right><font size=2>"._QXZ("EVENT")." </td><td align=right><font size=2> "._QXZ("DATE")."</td><td align=right><font size=2> "._QXZ("IP ADDRESS")."</td><td align=right><font size=2> "._QXZ("GROUP")."</td><td align=right><font size=2>"._QXZ("HOURS:MM:SS")."</td></tr>\n";
		$CSV_text3.="\""._QXZ("Timeclock Login and Logout Time")."\"\n";
		$CSV_text3.="\"\",\""._QXZ("ID")."\",\""._QXZ("EDIT")."\",\""._QXZ("EVENT")."\",\""._QXZ("DATE")."\",\""._QXZ("IPADDRESS")."\",\""._QXZ("GROUP")."\",\""._QXZ("HOURS:MM:SS")."\"\n";

		$stmt="SELECT event,event_epoch,user_group,login_sec,ip_address,timeclock_id,manager_user from ".$vicidial_timeclock_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and event_epoch >= '$SQepoch'  and event_epoch <= '$EQepoch';";
		if ($DB>0) {$MAIN.="|$stmt|";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$events_to_print = mysqli_num_rows($rslt);

		$total_logs=0;
		$o=0;
		while ($events_to_print > $o) {
			$row=mysqli_fetch_row($rslt);
			if ( ($row[0]=='START') or ($row[0]=='LOGIN') )
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$TC_log_date = date("Y-m-d H:i:s", $row[1]);

			$manager_edit='';
			if (strlen($row[6])>0) {$manager_edit = ' * ';}

			if (preg_match('/LOGIN/', $row[0]))
				{
				$login_sec='';
				$MAIN.="<tr $bgcolor><td><font size=2><A HREF=\"./timeclock_edit.php?timeclock_id=$row[5]\">$row[5]</A></td>";
				$MAIN.="<td align=right><font size=2>$manager_edit</td>";
				$MAIN.="<td align=right><font size=2>$row[0]</td>";
				$MAIN.="<td align=right><font size=2> $TC_log_date</td>\n";
				$MAIN.="<td align=right><font size=2> $row[4]</td>\n";
				$MAIN.="<td align=right><font size=2> $row[2]</td>\n";
				$MAIN.="<td align=right><font size=2> </td></tr>\n";
				$CSV_text3.="\"\",\"$row[5]\",\"$manager_edit\",\"$row[0]\",\"$TC_log_date\",\"$row[4]\",\"$row[2]\"\n";
				}
			if (preg_match('/LOGOUT/', $row[0]))
				{
				$login_sec = $row[3];
				$total_login_time = ($total_login_time + $login_sec);
				$event_hours_minutes =		sec_convert($login_sec,'H'); 

				$MAIN.="<tr $bgcolor><td><font size=2><A HREF=\"./timeclock_edit.php?timeclock_id=$row[5]\">$row[5]</A></td>";
				$MAIN.="<td align=right><font size=2>$manager_edit</td>";
				$MAIN.="<td align=right><font size=2>$row[0]</td>";
				$MAIN.="<td align=right><font size=2> $TC_log_date</td>\n";
				$MAIN.="<td align=right><font size=2> $row[4]</td>\n";
				$MAIN.="<td align=right><font size=2> $row[2]</td>\n";
				$MAIN.="<td align=right><font size=2> $event_hours_minutes";
				if ($DB) {$MAIN.=" - $total_login_time - $login_sec";}
				$MAIN.="</td></tr>\n";
				$CSV_text3.="\"\",\"$row[5]\",\"$manager_edit\",\"$row[0]\",\"$TC_log_date\",\"$row[4]\",\"$row[2]\",\"$event_hours_minutes\"\n";
				}
			$o++;
			}
		if (strlen($login_sec)<1)
			{
			$login_sec = ($EQepoch - $row[1]);
			$total_login_time = ($total_login_time + $login_sec);
				if ($DB) {$MAIN.="LOGIN ONLY - $total_login_time - $login_sec";}
			}
		$total_login_hours_minutes =		sec_convert($total_login_time,'H'); 

		if ($DB) {$MAIN.=" - $total_login_time - $login_sec";}

		$MAIN.="<tr><td align=right><font size=2> </td>";
		$MAIN.="<td align=right><font size=2> </td>\n";
		$MAIN.="<td align=right><font size=2> </td>\n";
		$MAIN.="<td align=right><font size=2> </td>\n";
		$MAIN.="<td align=right><font size=2><font size=2>"._QXZ("TOTAL")." </td>\n";
		$MAIN.="<td align=right><font size=2> $total_login_hours_minutes  </td></tr>\n";
		$CSV_text3.="\"\",\"\",\"\",\"\",\"\",\""._QXZ("TOTAL")."\",\"$total_login_hours_minutes\"\n";
		$MAIN.="</TABLE></center>\n";



		##### closer in-group selection logs #####

		$MAIN.="<br><br>\n";

		$MAIN.="<center>\n";

		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Closer In-Group Selection Logs").": $NWB#user_stats-closer_ingroup_selection_logs$NWE </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=4'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=900 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2> "._QXZ("CAMPAIGN")."</td><td align=left><font size=2>"._QXZ("BLEND")."</td><td align=left><font size=2> "._QXZ("GROUPS")."</td><td align=left><font size=2> "._QXZ("MANAGER")."</td></tr>\n";

		$CSV_text4.="\""._QXZ("Closer In-Group Selection Logs")."\"\n";
		$CSV_text4.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("BLEND")."\",\""._QXZ("GROUPS")."\",\""._QXZ("MANAGER")."\"\n";

		$stmt="SELECT user,campaign_id,event_date,blended,closer_campaigns,manager_change from ".$vicidial_user_closer_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and event_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and event_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' order by event_date desc limit 1000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		$u=0;
		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$u++;
			$MAIN.="<tr $bgcolor>";
			$MAIN.="<td><font size=1>$u</td>";
			$MAIN.="<td><font size=2>$row[2]</td>";
			$MAIN.="<td align=left><font size=2> $row[1]</td>\n";
			$MAIN.="<td align=left><font size=2> $row[3]</td>\n";
			$MAIN.="<td align=left><font size=2> $row[4] </td>\n";
			$MAIN.="<td align=left><font size=2> $row[5]</td>\n";
			$CSV_text4.="\"\",\"$u\",\"$row[2]\",\"$row[1]\",\"$row[3]\",\"$row[4]\",\"$row[5] \"\n";
			$MAIN.="</tr>\n";
			}


		$MAIN.="</TABLE><BR><BR>\n";


		##### vicidial agent outbound calls for this time period #####

		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Outbound Calls for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-outbound_calls$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=5'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=700 cellspacing=0 cellpadding=1>\n";
		if ($firstlastname_display_user_stats > 0)
			{
			$MAIN.="<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("LENGTH")."</td><td align=left><font size=2> "._QXZ("STATUS")."</td><td align=left><font size=2> "._QXZ("PHONE")."</td><td align=right><font size=2> "._QXZ("CAMPAIGN")."</td><td align=right><font size=2> "._QXZ("GROUP")."</td><td align=right><font size=2> "._QXZ("LIST")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td><td align=right><font size=2> "._QXZ("NAME")."</td><td align=right><font size=2> "._QXZ("HANGUP REASON")."</td></tr>\n";
			}
		else
			{
			$MAIN.="<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("LENGTH")."</td><td align=left><font size=2> "._QXZ("STATUS")."</td><td align=left><font size=2> "._QXZ("PHONE")."</td><td align=right><font size=2> "._QXZ("CAMPAIGN")."</td><td align=right><font size=2> "._QXZ("GROUP")."</td><td align=right><font size=2> "._QXZ("LIST")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td><td align=right><font size=2> "._QXZ("HANGUP REASON")."</td></tr>\n";
			}
		$CSV_text5.="\""._QXZ("Outbound Calls for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
		if ($firstlastname_display_user_stats > 0)
			{$CSV_text5.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("LENGTH")."\",\""._QXZ("STATUS")."\",\""._QXZ("PHONE")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("GROUP")."\",\""._QXZ("LIST")."\",\""._QXZ("LEAD")."\",\""._QXZ("NAME")."\",\""._QXZ("HANGUP REASON")."\"\n";}
		else
			{$CSV_text5.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("LENGTH")."\",\""._QXZ("STATUS")."\",\""._QXZ("PHONE")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("GROUP")."\",\""._QXZ("LIST")."\",\""._QXZ("LEAD")."\",\""._QXZ("HANGUP REASON")."\"\n";}

		$stmt="SELECT uniqueid,lead_id,list_id,campaign_id,call_date,start_epoch,end_epoch,length_in_sec,status,phone_code,phone_number,user,comments,processed,user_group,term_reason,alt_dial from ".$vicidial_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and call_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and call_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' $query_call_status order by call_date desc limit 10000;";
		if ($firstlastname_display_user_stats > 0)
			{
			$stmt="SELECT uniqueid,vlog.lead_id,vlog.list_id,campaign_id,call_date,start_epoch,end_epoch,length_in_sec,vlog.status,vlog.phone_code,vlog.phone_number,vlog.user,vlog.comments,processed,user_group,term_reason,alt_dial,first_name,last_name from ".$vicidial_log_table." vlog, vicidial_list vlist where vlog.user='" . mysqli_real_escape_string($link, $user) . "' and call_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and call_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' and vlog.lead_id=vlist.lead_id $VLquery_call_status order by call_date desc limit 10000;";
			}
		if ($DB) {$MAIN.="outbound calls|$stmt|";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		$u=0;
		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$u++;

			if ($LOGadmin_hide_phone_data != '0')
				{
				if ($DB > 0) {echo "HIDEPHONEDATA|$row[10]|$LOGadmin_hide_phone_data|\n";}
				$phone_temp = $row[10];
				if (strlen($phone_temp) > 0)
					{
					if ($LOGadmin_hide_phone_data == '4_DIGITS')
						{$row[10] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);}
					elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
						{$row[10] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);}
					elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
						{$row[10] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);}
					else
						{$row[10] = preg_replace("/./",'X',$phone_temp);}
					}
				}
			$MAIN.="<tr $bgcolor>";
			$MAIN.="<td><font size=1>$u</td>";
			$MAIN.="<td><font size=2>$row[4]</td>";
			$MAIN.="<td align=left><font size=2> $row[7]</td>\n";
			$MAIN.="<td align=left><font size=2> $row[8]</td>\n";
			$MAIN.="<td align=left><font size=2> $row[10] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[3] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[14] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[2] </td>\n";
			$MAIN.="<td align=right><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$row[1]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$row[1]', '_blank');return false;\">$row[1]</A> </td>\n";
			if ($firstlastname_display_user_stats > 0)
				{
				$MAIN.="<td align=right><font size=2> $row[17] $row[18] </td>\n";
				}
			$MAIN.="<td align=right><font size=2> $row[15] </td></tr>\n";
			if ($firstlastname_display_user_stats > 0)
				{
				$CSV_text5.="\"\",\"$u\",\"$row[4]\",\"$row[7]\",\"$row[8]\",\"$row[10]\",\"$row[3]\",\"$row[14]\",\"$row[2]\",\"$row[1]\",\"$row[17] $row[18]\",\"$row[15]\"\n";
				}
			else
				{
				$CSV_text5.="\"\",\"$u\",\"$row[4]\",\"$row[7]\",\"$row[8]\",\"$row[10]\",\"$row[3]\",\"$row[14]\",\"$row[2]\",\"$row[1]\",\"$row[15]\"\n";
				}
			if (strlen($query_call_status) > 5)
				{
				$CS_vicidial_id_list .= "'$row[0]',";
				}
			}


		$MAIN.="</TABLE><BR><BR>\n";
		}

		##### vicidial agent outbound emails for this time period #####

		if ($allow_emails>0) 
			{
			$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Outbound Emails for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-outbound_emails$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=5'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
			$MAIN.="<TABLE width=900 cellspacing=0 cellpadding=3>\n";
			$MAIN.="<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2> "._QXZ("CAMPAIGN")."</td><td align=left><font size=2> "._QXZ("EMAIL TO")."</td><td align=right><font size=2> "._QXZ("ATTACHMENTS")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td></tr>\n";
			$CSV_text5.="\""._QXZ("Outbound Emails for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
			$CSV_text5.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("USER")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("EMAIL TO")."\",\""._QXZ("ATTACHMENT")."\",\""._QXZ("LEAD")."\",\""._QXZ("MESSAGE")."\"\n";

			$stmt="SELECT email_log_id,email_row_id,lead_id,email_date,user,email_to,message,campaign_id,attachments from ".$vicidial_email_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and email_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and email_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' order by email_date desc limit 10000;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$logs_to_print = mysqli_num_rows($rslt);

			$u=0;
			while ($logs_to_print > $u) 
				{
				$row=mysqli_fetch_row($rslt);
				if (preg_match("/1$|3$|5$|7$|9$/i", $u))
					{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
				else
					{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}
				if (strlen($row[6])>400) {$row[6]=substr($row[6],0,400)."...";}
				$row[8]=preg_replace('/\|/', ', ', $row[8]);
				$row[8]=preg_replace('/,\s+$/', '', $row[8]);
				$u++;

				$MAIN .= "<tr $bgcolor>";
				$MAIN .= "<td><font size=1>$u</td>";
				$MAIN .= "<td align=left><font size=2> &nbsp; $row[3]</td>";
				$MAIN .= "<td align=left><font size=2> &nbsp; $row[7]</td>\n";
				$MAIN .= "<td align=left><font size=2> &nbsp; $row[5]</td>\n";
				$MAIN .= "<td align=left><font size=2> &nbsp; $row[8] </td>\n";
				$MAIN .= "<td align=right><font size=2> &nbsp;  <A HREF=\"admin_modify_lead.php?lead_id=$row[2]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$row[2]', '_blank');return false;\">$row[2]</A> </td>\n";
				$MAIN .= "</tr>\n";
				$MAIN .= "<tr>";
				$MAIN .= "<td><font size=1> &nbsp; </td>\n";
				$MAIN .= "<td align=left colspan=5 $bgcolor><font size=1> "._QXZ("MESSAGE").": $row[6] </td>\n";
				$MAIN .= "</tr>\n";

				$CSV_text5.="\"\",\"$u\",\"$row[3]\",\"$row[4]\",\"$row[7]\",\"$row[5]\",\"$row[8]\",\"$row[2]\",\"$row[6]\"\n";
				}


			$MAIN.="</TABLE><BR><BR>\n";
			}

	##### vicidial agent inbound calls for this time period #####

	$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Inbound Closer Calls for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-inbound_calls$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=6'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
	$MAIN.="<TABLE width=900 cellspacing=0 cellpadding=1>\n";
	if ($firstlastname_display_user_stats > 0)
		{
		$MAIN.="<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("LENGTH")."</td><td align=left><font size=2> "._QXZ("STATUS")."</td><td align=left><font size=2> "._QXZ("PHONE")."</td><td align=right><font size=2> "._QXZ("IN-GROUP")."</td><td align=right><font size=2> "._QXZ("WAIT (S)")."</td><td align=right><font size=2> "._QXZ("AGENT (S)")."</td><td align=right><font size=2> "._QXZ("LIST")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td><td align=right><font size=2> "._QXZ("NAME")."</td><td align=right><font size=2> "._QXZ("HANGUP REASON")."</td></tr>\n";
		}
	else
		{
		$MAIN.="<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("LENGTH")."</td><td align=left><font size=2> "._QXZ("STATUS")."</td><td align=left><font size=2> "._QXZ("PHONE")."</td><td align=right><font size=2> "._QXZ("IN-GROUP")."</td><td align=right><font size=2> "._QXZ("WAIT (S)")."</td><td align=right><font size=2> "._QXZ("AGENT (S)")."</td><td align=right><font size=2> "._QXZ("LIST")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td><td align=right><font size=2> "._QXZ("HANGUP REASON")."</td></tr>\n";
		}
	$CSV_text6.="\""._QXZ("Inbound Closer Calls for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
	if ($firstlastname_display_user_stats > 0)
		{
		$CSV_text6.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("LENGTH")."\",\""._QXZ("STATUS")."\",\""._QXZ("PHONE")."\",\""._QXZ("IN-GROUP")."\",\""._QXZ("WAIT(S)")."\",\""._QXZ("AGENT(S)")."\",\""._QXZ("LIST")."\",\""._QXZ("LEAD")."\",\""._QXZ("NAME")."\",\""._QXZ("HANGUP REASON")."\"\n";
		}
	else
		{
		$CSV_text6.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("LENGTH")."\",\""._QXZ("STATUS")."\",\""._QXZ("PHONE")."\",\""._QXZ("IN-GROUP")."\",\""._QXZ("WAIT(S)")."\",\""._QXZ("AGENT(S)")."\",\""._QXZ("LIST")."\",\""._QXZ("LEAD")."\",\""._QXZ("HANGUP REASON")."\"\n";
		}

	$stmt="SELECT call_date,length_in_sec,status,phone_number,campaign_id,queue_seconds,list_id,lead_id,term_reason,closecallid from ".$vicidial_closer_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and call_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and call_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' $query_call_status order by call_date desc limit 10000;";
	if ($did > 0)
		{
		$stmt="SELECT start_time,length_in_sec,0,caller_code,0,0,0,extension,0,0,uniqueid from ".$call_log_table." where channel_group='DID_INBOUND' and number_dialed='" . mysqli_real_escape_string($link, $user) . "' and start_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and start_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' order by start_time desc limit 10000;";
		}
	else
		{
		if ($firstlastname_display_user_stats > 0)
			{
			$stmt="SELECT call_date,length_in_sec,vlog.status,vlog.phone_number,campaign_id,queue_seconds,vlog.list_id,vlog.lead_id,term_reason,closecallid,first_name,last_name from ".$vicidial_closer_log_table." vlog, vicidial_list vlist where vlog.user='" . mysqli_real_escape_string($link, $user) . "' and call_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and call_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' and vlog.lead_id=vlist.lead_id $VLquery_call_status order by call_date desc limit 10000;";
			}
		}
	if ($DB) {$MAIN.="inbound calls|$stmt|";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$logs_to_print = mysqli_num_rows($rslt);

	$u=0;
	$TOTALinSECONDS=0;
	$TOTALagentSECONDS=0;
	while ($logs_to_print > $u) 
		{
		$row=mysqli_fetch_row($rslt);
		if (preg_match("/1$|3$|5$|7$|9$/i", $u))
			{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
		else
			{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

		if ($did > 0)
			{
			if (strlen($row[7]) > 17)
				{
				$row[7] = substr($row[7], -9);
				$row[7] = ($row[7] + 0);
				}
			else
				{
				$row[7]='0';
				$lead_id_stmt="select lead_id from vicidial_closer_log where uniqueid='$row[10]'";
				$lead_id_rslt=mysql_to_mysqli($lead_id_stmt, $link);
				if (mysqli_num_rows($lead_id_rslt)>0) 
					{
					$lead_id_row=mysqli_fetch_row($lead_id_rslt);
					$row[7]=$lead_id_row[0];
					}
				}
			}
		$TOTALinSECONDS = ($TOTALinSECONDS + $row[1]);
		$AGENTseconds = ($row[1] - $row[5]);
		if ($AGENTseconds < 0)
			{$AGENTseconds=0;}

		$TOTALagentSECONDS = ($TOTALagentSECONDS + $AGENTseconds);

		if ($LOGadmin_hide_phone_data != '0')
			{
			if ($DB > 0) {echo "HIDEPHONEDATA|$row[10]|$LOGadmin_hide_phone_data|\n";}
			$phone_temp = $row[3];
			if (strlen($phone_temp) > 0)
				{
				if ($LOGadmin_hide_phone_data == '4_DIGITS')
					{$row[3] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);}
				elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
					{$row[3] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);}
				elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
					{$row[3] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);}
				else
					{$row[3] = preg_replace("/./",'X',$phone_temp);}
				}
			}

		$u++;
		$MAIN.="<tr $bgcolor>";
		$MAIN.="<td><font size=1>$u</td>";
		$MAIN.="<td><font size=2>$row[0]</td>";
		$MAIN.="<td align=left><font size=2> $row[1]</td>\n";
		$MAIN.="<td align=left><font size=2> $row[2]</td>\n";
		$MAIN.="<td align=left><font size=2> $row[3] </td>\n";
		$MAIN.="<td align=right><font size=2> $row[4] </td>\n";
		$MAIN.="<td align=right><font size=2> $row[5] </td>\n";
		$MAIN.="<td align=right><font size=2> $AGENTseconds </td>\n";
		$MAIN.="<td align=right><font size=2> $row[6] </td>\n";
		$MAIN.="<td align=right><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$row[7]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$row[7]', '_blank');return false;\">$row[7]</A> </td>\n";
		if ($firstlastname_display_user_stats > 0)
			{$MAIN.="<td align=right><font size=2> $row[10] $row[11] </td>\n";}
		$MAIN.="<td align=right><font size=2> $row[8] </td></tr>\n";
		if ($firstlastname_display_user_stats > 0)
			{
			$CSV_text6.="\"\",\"$u\",\"$row[0]\",\"$row[1]\",\"$row[2]\",\"$row[3]\",\"$row[4]\",\"$row[5]\",\"$AGENTseconds\",\"$row[6]\",\"$row[7]\",\"$row[9] $row[10]\",\"$row[8]\"\n";
			}
		else
			{
			$CSV_text6.="\"\",\"$u\",\"$row[0]\",\"$row[1]\",\"$row[2]\",\"$row[3]\",\"$row[4]\",\"$row[5]\",\"$AGENTseconds\",\"$row[6]\",\"$row[7]\",\"$row[8]\"\n";
			}
		if (strlen($query_call_status) > 5)
			{
			$CS_vicidial_id_list .= "'$row[9]',";
			}
		}

	$MAIN.="<tr bgcolor=white>";
	$MAIN.="<td colspan=2><font size=2>"._QXZ("TOTALS")."</td>";
	$MAIN.="<td align=left><font size=2> $TOTALinSECONDS</td>\n";
	$MAIN.="<td colspan=4><font size=2> &nbsp; </td>\n";
	$MAIN.="<td align=right><font size=2> $TOTALagentSECONDS</td>\n";
	$MAIN.="<td colspan=3><font size=2> &nbsp; </td></tr>\n";
	$MAIN.="</TABLE></center><BR><BR>\n";
	$CSV_text6.="\"\",\"\",\""._QXZ("TOTALS")."\",\"$TOTALinSECONDS\",\"\",\"\",\"\",\"\",\"$TOTALagentSECONDS\"\n";


	##### vicidial agent activity records for this time period #####
	if ($did < 1)
		{
		$Aevent_time=array();
		$Alead_id=array();
		$Acampaign_id=array();
		$Apause_sec=array();
		$Await_sec=array();
		$Atalk_sec=array();
		$Adispo_sec=array();
		$Adead_sec=array();
		$Astatus=array();
		$Apause_code=array();
		$Auser_group=array();
		$Acomments=array();
		$Acustomer_sec=array();
		$Aagent_log_id=array();

		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Agent Activity for this time period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-agent_activity$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=7'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=900 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td colspan=2><font size=1> &nbsp; </td><td colspan=8 align=center bgcolor=white><font size=1>"._QXZ("these fields are in seconds")." </td><td colspan=4><font size=1> &nbsp; </td></tr>\n";
		$MAIN.="<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("PAUSE")."</td><td align=left><font size=2> "._QXZ("WAIT")."</td><td align=left><font size=2> "._QXZ("TALK")."</td><td align=right><font size=2> "._QXZ("DISPO")."</td><td align=right><font size=2> "._QXZ("DEAD")."</td><td align=right><font size=2> "._QXZ("CUSTOMER")."</td><td align=right><font size=2> "._QXZ("VISIBLE")."</td><td align=right><font size=2> "._QXZ("HIDDEN")."</td><td align=right><font size=2> "._QXZ("STATUS")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td><td align=right><font size=2> "._QXZ("TYPE")."</td><td align=right><font size=2> "._QXZ("CAMPAIGN")."</td><td align=right><font size=2> "._QXZ("PAUSE CODE")."</td></tr>\n";
		$CSV_text7.="\""._QXZ("Agent Activity for this time period").": ("._QXZ("10000 record limit").")\"\n";
		$CSV_text7.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("PAUSE")."\",\""._QXZ("WAIT")."\",\""._QXZ("TALK")."\",\""._QXZ("DISPO")."\",\""._QXZ("DEAD")."\",\""._QXZ("CUSTOMER")."\",\""._QXZ("VISIBLE")."\",\""._QXZ("HIDDEN")."\",\""._QXZ("STATUS")."\",\""._QXZ("LEAD")."\",\""._QXZ("TYPE")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("PAUSE CODE")."\"\n";

		$stmt="SELECT event_time,lead_id,campaign_id,pause_sec,wait_sec,talk_sec,dispo_sec,dead_sec,status,sub_status,user_group,comments,agent_log_id from ".$vicidial_agent_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and event_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and event_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' and ( (pause_sec > 0) or (wait_sec > 0) or (talk_sec > 0) or (dispo_sec > 0) ) $query_call_status order by event_time desc limit 10000;";
		if ($DB) {$MAIN.="agent activity|$stmt|";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		$u=0;
		$TOTALpauseSECONDS=0;
		$TOTALwaitSECONDS=0;
		$TOTALtalkSECONDS=0;
		$TOTALdispoSECONDS=0;
		$TOTALdeadSECONDS=0;
		$TOTALcustomerSECONDS=0;
		$TOT_HIDDEN=0;
		$TOT_VISIBLE=0;
		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			$Aevent_time[$u] =	$row[0];
			$Alead_id[$u] =		$row[1];
			$Acampaign_id[$u] =	$row[2];
			$Apause_sec[$u] =	$row[3];
			$Await_sec[$u] =	$row[4];
			$Atalk_sec[$u] =	$row[5];
			$Adispo_sec[$u] =	$row[6];
			$Adead_sec[$u] =	$row[7];
			$Astatus[$u] =		$row[8];
			$Apause_code[$u] =	$row[9];
			$Auser_group[$u] =	$row[10];
			$Acomments[$u] =	$row[11];
			$Aagent_log_id[$u] = $row[12];
			$Acustomer_sec[$u] = ($Atalk_sec[$u] - $Adead_sec[$u]);
			if ($Acustomer_sec[$u] < 0)
				{$Acustomer_sec[$u]=0;}
			$u++;
			}
		$u=0;
		while ($logs_to_print > $u) 
			{
			$event_time =	$Aevent_time[$u];
			$lead_id =		$Alead_id[$u];
			$campaign_id =	$Acampaign_id[$u];
			$pause_sec = 	$Apause_sec[$u];
			$wait_sec =		$Await_sec[$u];
			$talk_sec =		$Atalk_sec[$u];
			$dispo_sec =	$Adispo_sec[$u];
			$dead_sec =		$Adead_sec[$u];
			$status =		$Astatus[$u];
			$pause_code =	$Apause_code[$u];
			$user_group =	$Auser_group[$u];
			$comments =		$Acomments[$u];
			$agent_log_id =	$Aagent_log_id[$u];
			$customer_sec =	$Acustomer_sec[$u];

			$HIDDEN_sec=0;   $VISIBLE_sec=0;
			$stmt="select count(*),sum(length_in_sec),visibility from ".$vicidial_agent_visibility_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and db_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01' and db_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' and visibility IN('HIDDEN','VISIBLE') and agent_log_id='$agent_log_id' group by visibility;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$visibility_results = mysqli_num_rows($rslt);
			if ($DB) {echo "$visibility_results|$stmt\n";}
			$v_ct=0;
			while ($visibility_results > $v_ct)
				{
				$row=mysqli_fetch_row($rslt);
				if ($row[2] == 'HIDDEN') {$HIDDEN_sec = $row[1];}
				if ($row[2] == 'VISIBLE') {$VISIBLE_sec = $row[1];}
				$v_ct++;
				}
			$TOT_HIDDEN =	($TOT_HIDDEN + $HIDDEN_sec);
			$TOT_VISIBLE =	($TOT_VISIBLE + $VISIBLE_sec);
			if ($HIDDEN_sec < 1) {$HIDDEN_sec='';}
			if ($VISIBLE_sec < 1) {$VISIBLE_sec='';}

			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';}
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$TOTALpauseSECONDS = ($TOTALpauseSECONDS + $pause_sec);
			$TOTALwaitSECONDS = ($TOTALwaitSECONDS + $wait_sec);
			$TOTALtalkSECONDS = ($TOTALtalkSECONDS + $talk_sec);
			$TOTALdispoSECONDS = ($TOTALdispoSECONDS + $dispo_sec);
			$TOTALdeadSECONDS = ($TOTALdeadSECONDS + $dead_sec);
			$TOTALcustomerSECONDS = ($TOTALcustomerSECONDS + $customer_sec);

			if ($DB > 0)
				{
				$DBtotal_sec = ($pause_sec + $wait_sec + $talk_sec + $dispo_sec);
				$DBdatetime = explode(" ",$event_time);
				$DBdate = explode("-",$DBdatetime[0]);
				$DBtime = explode(":",$DBdatetime[1]);
		
				$DBcall_end_sec = mktime($DBtime[0], $DBtime[1], ($DBtime[2] + $DBtotal_sec), $DBdate[1], $DBdate[2], $DBdate[0]);
				$DBcall_end = date("Y-m-d H:i:s",$DBcall_end_sec);
				$MAIN.="<tr $bgcolor>";
				$MAIN.="<td><font size=1> &nbsp;</td>";
				$MAIN.="<td><font size=2>$DBcall_end</td>";
				$MAIN.="<td align=right><font size=2> $DBtotal_sec </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
				$MAIN.="<td align=right><font size=2> &nbsp; </td></tr>\n";
				}

			$u++;
			$MAIN.="<tr $bgcolor>";
			$MAIN.="<td><font size=1>$u</td>";
			$MAIN.="<td><font size=2>$event_time</td>";
			$MAIN.="<td align=right><font size=2> $pause_sec</td>\n";
			$MAIN.="<td align=right><font size=2> $wait_sec</td>\n";
			$MAIN.="<td align=right><font size=2> $talk_sec </td>\n";
			$MAIN.="<td align=right><font size=2> $dispo_sec </td>\n";
			$MAIN.="<td align=right><font size=2> $dead_sec </td>\n";
			$MAIN.="<td align=right><font size=2> $customer_sec </td>\n";
			$MAIN.="<td align=right><font size=2> $VISIBLE_sec </td>\n";
			$MAIN.="<td align=right><font size=2> $HIDDEN_sec </td>\n";
			$MAIN.="<td align=right><font size=2> $status </td>\n";
			$MAIN.="<td align=right><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$lead_id\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$lead_id', '_blank');return false;\">$lead_id</A> </td>\n";
			$call_type='';
			if (strlen($lead_id) > 0)
				{
				$call_type='A';
				if ($comments == 'INBOUND') {$call_type='I';}
				if ($comments == 'EMAIL') {$call_type='E';}
				if ($comments == 'CHAT') {$call_type='C';}
				if ($comments == 'MANUAL') {$call_type='M';}
				$MAIN.="<td align=center><font size=2> $call_type </td>\n";
				}
			else
				{$MAIN.="<td align=right><font size=2> &nbsp;</td>\n";}
			$MAIN.="<td align=right><font size=2> $campaign_id </td>\n";
			$MAIN.="<td align=right><font size=2> $pause_code </td></tr>\n";
			$CSV_text7.="\"\",\"$u\",\"$event_time\",\"$pause_sec\",\"$wait_sec\",\"$talk_sec\",\"$dispo_sec\",\"$dead_sec\",\"$customer_sec\",\"$VISIBLE_sec\",\"$HIDDEN_sec\",\"$status\",\"$lead_id\",\"$call_type\",\"$campaign_id\",\"$pause_code \"\n";
			}

		$MAIN.="<tr bgcolor=white>";
		$MAIN.="<td colspan=2><font size=2>"._QXZ("TOTALS")."</td>";
		$MAIN.="<td align=right><font size=2> $TOTALpauseSECONDS</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALwaitSECONDS</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALtalkSECONDS</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALdispoSECONDS</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALdeadSECONDS</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALcustomerSECONDS</td>\n";
		$MAIN.="<td align=right><font size=2> $TOT_VISIBLE</td>\n";
		$MAIN.="<td align=right><font size=2> $TOT_HIDDEN</td>\n";
		$MAIN.="<td colspan=5><font size=2> &nbsp; </td></tr>\n";
		$CSV_text7.="\"\",\"\",\""._QXZ("TOTALS")."\",\"$TOTALpauseSECONDS\",\"$TOTALwaitSECONDS\",\"$TOTALtalkSECONDS\",\"$TOTALdispoSECONDS\",\"$TOTALdeadSECONDS\",\"$TOTALcustomerSECONDS\",\"$TOT_VISIBLE\",\"$TOT_HIDDEN\"\n";

		$TOTALpauseSECONDShh =	sec_convert($TOTALpauseSECONDS,'H'); 
		$TOTALwaitSECONDShh =	sec_convert($TOTALwaitSECONDS,'H'); 
		$TOTALtalkSECONDShh =	sec_convert($TOTALtalkSECONDS,'H'); 
		$TOTALdispoSECONDShh =	sec_convert($TOTALdispoSECONDS,'H'); 
		$TOTALdeadSECONDShh =	sec_convert($TOTALdeadSECONDS,'H'); 
		$TOTALcustomerSECONDShh =	sec_convert($TOTALcustomerSECONDS,'H'); 
		$TOTALvisibleSECONDShh =	sec_convert($TOT_VISIBLE,'H'); 
		$TOTALhiddenSECONDShh =	sec_convert($TOT_HIDDEN,'H'); 

		$MAIN.="<tr bgcolor=white>";
		$MAIN.="<td colspan=2><font size=1>"._QXZ("(in HH:MM:SS)")."</td>";
		$MAIN.="<td align=right><font size=2> $TOTALpauseSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALwaitSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALtalkSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALdispoSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALdeadSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALcustomerSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALvisibleSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALhiddenSECONDShh</td>\n";
		$MAIN.="<td colspan=5><font size=2> &nbsp; </td></tr>\n";
		$CSV_text7.="\"\",\"\",\""._QXZ("(in HH:MM:SS)")."\",\"$TOTALpauseSECONDShh\",\"$TOTALwaitSECONDShh\",\"$TOTALtalkSECONDShh\",\"$TOTALdispoSECONDShh\",\"$TOTALdeadSECONDShh\",\"$TOTALcustomerSECONDShh\",\"$TOTALvisibleSECONDShh\",\"$TOTALhiddenSECONDShh\"\n";

		$MAIN.="</TABLE><BR><BR>\n";
		}


	##### vicidial recordings for this time period #####

	$mute_column='';   $mute_column_csv='';
	$agent_column='';   $agent_column_csv='';
	$ANI_column='';   $ANI_column_csv='';
	if ($SSmute_recordings > 0)
		{
		$mute_column = "<td align=center><font size=2>"._QXZ("MUTE")." &nbsp; </td>";
		$mute_column_csv = ",\""._QXZ("MUTE")."\"";
		}
	if ($NVAuser > 0)
		{
		$agent_column = "<td align=center><font size=2>"._QXZ("AGENT")." &nbsp; </td>";
		$agent_column_csv = ",\""._QXZ("AGENT")."\"";
		$ANI_column = "<td align=center><font size=2>"._QXZ("ANI")." &nbsp; </td>";
		$ANI_column_csv = ",\""._QXZ("ANI")."\"";
		}
	$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Recordings for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-recordings$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=8'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
	$MAIN.="<TABLE width=900 cellspacing=0 cellpadding=1>\n";
	$MAIN.="<tr><td><font size=1># </td>$agent_column<td align=left><font size=2> "._QXZ("LEAD")."</td>$ANI_column<td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("SECONDS")." </td><td align=left><font size=2> &nbsp; "._QXZ("RECID")."</td><td align=center><font size=2>"._QXZ("FILENAME")."</td><td align=center><font size=2>"._QXZ("LOCATION")." &nbsp; </td>$mute_column</tr>\n";
	$CSV_text8.="\""._QXZ("Recordings for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
	$CSV_text8.="\"\",\"#\"$agent_column_csv,\""._QXZ("LEAD")."\",\"$ANI_column_csv\",\""._QXZ("DATE/TIME")."\",\""._QXZ("SECONDS")."\",\""._QXZ("RECID")."\",\""._QXZ("FILENAME")."\",\""._QXZ("LOCATION")."\"$mute_column_csv\n";

	if (strlen($query_call_status) > 5)
		{
		$CS_vicidial_id_list .= "'X'";
		$CS_vicidial_id_list_SQL = "and vicidial_id IN($CS_vicidial_id_list)";
		}

	$stmt="SELECT recording_id,channel,server_ip,extension,start_time,start_epoch,end_time,end_epoch,length_in_sec,length_in_min,filename,location,lead_id,user,vicidial_id from ".$recording_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and start_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and start_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' $CS_vicidial_id_list_SQL order by recording_id desc limit 10000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$logs_to_print = mysqli_num_rows($rslt);
	if ($DB) {$MAIN.="agent activity|$stmt|";}

	$u=0;
	while ($logs_to_print > $u) 
		{
		$row=mysqli_fetch_row($rslt);
		if (preg_match("/1$|3$|5$|7$|9$/i", $u))
			{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
		else
			{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

		$location = $row[11];
		$CSV_location=$row[11];
		$vicidial_id=$row[14];

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
		if ($SSmute_recordings > 0)
			{
			$mute_events=0;
			$stmt="SELECT count(*) from ".$vicidial_agent_function_log." where user='" . mysqli_real_escape_string($link, $user) . "' and event_time >= '$row[4]' and event_time <= '$row[6]' and function='mute_rec' and lead_id='$row[12]' and stage='on';";
			$rsltx=mysql_to_mysqli($stmt, $link);
			$flogs_to_print = mysqli_num_rows($rsltx);
			if ($flogs_to_print > 0) 
				{
				$rowx=mysqli_fetch_row($rsltx);
				$mute_events = $rowx[0];
				}
			}
		if ($NVAuser > 0)
			{
			$agent_user='';
			$stmt="SELECT user from ".$vicidial_agent_log_table." where lead_id='$row[12]' and event_time <= '$row[4]'  and event_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01' order by event_time;";
			$rsltx=mysql_to_mysqli($stmt, $link);
			$valogs_to_print = mysqli_num_rows($rsltx);
			if ($valogs_to_print > 0) 
				{
				$rowx=mysqli_fetch_row($rsltx);
				$agent_user = $rowx[0];
				}

			$ANI='';
			$ANI_stmt="SELECT caller_code from call_log where uniqueid='$vicidial_id' order by start_time;";
			$ANI_rslt=mysql_to_mysqli($ANI_stmt, $link);
			$ANI_logs_to_print = mysqli_num_rows($ANI_rslt);
			if ($ANI_logs_to_print > 0) 
				{
				$ANI_row=mysqli_fetch_row($ANI_rslt);
				$ANI = $ANI_row[0];
				}

			}

		if (strlen($location)>30)
			{$locat = substr($location,0,27);  $locat = "$locat...";}
		else
			{$locat = $location;}
		if ( (preg_match('/ftp/i',$location)) or (preg_match('/http/i',$location)) )
			{
			if ($log_recording_access<1) 
				{$location = "<a href=\"$location\">$locat</a>";}
			else
				{$location = "<a href=\"recording_log_redirect.php?recording_id=$row[0]&lead_id=$row[12]&search_archived_data=$search_archived_data\">$locat</a>";}
			}
		else
			{$location = $locat;}
		$u++;
		$mute_csv_record='';
		$NVAuser_csv_record='';
		$MAIN.="<tr $bgcolor>";
		$MAIN.="<td><font size=1>$u</td>";
		if ($NVAuser > 0)
			{
			$MAIN.="<td align=right><font size=2> $agent_user &nbsp; </td>\n";
			$NVAuser_csv_record=",\"$agent_user\"";
			}
		$MAIN.="<td align=left><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$row[12]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$row[12]', '_blank');return false;\">$row[12]</A> </td>";
		if ($NVAuser > 0)
			{
			$MAIN.="<td align=right><font size=2> $ANI &nbsp; </td>\n";
			$NVAuser_csv_record=",\"$ANI\"";
			}
		$MAIN.="<td align=left><font size=2> $row[4] </td>\n";
		$MAIN.="<td align=left><font size=2> $row[8] </td>\n";
		$MAIN.="<td align=left><font size=2> $row[0] </td>\n";
		$MAIN.="<td align=center><font size=2> $row[10] </td>\n";
		$MAIN.="<td align=right><font size=2> $location &nbsp; </td>\n";
		if ($SSmute_recordings > 0)
			{
			if ($mute_events < 1) {$mute_events='';}
			$MAIN.="<td align=right><font size=2> $mute_events &nbsp; </td>\n";
			$mute_csv_record=",\"$mute_events\"";
			}
		$MAIN.="</tr>\n";
		$CSV_text8.="\"\",\"$u\"$NVAuser_csv_record,\"$row[12]\",\"$row[4]\",\"$row[8]\",\"$row[0]\",\"$row[10]\",\"$CSV_location\"$mute_csv_record\n";
		}

	$MAIN.="</TABLE><BR><BR>\n";


	if ($did < 1)
		{
		##### vicidial agent outbound user manual calls for this time period #####

		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Manual Outbound Calls for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-manual_calls$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=9'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=900 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2> "._QXZ("CALL TYPE")."</td><td align=left><font size=2> "._QXZ("SERVER")."</td><td align=left><font size=2> "._QXZ("PHONE")."</td><td align=right><font size=2> "._QXZ("DIALED")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td><td align=right><font size=2> "._QXZ("CALLERID")."</td><td align=right><font size=2> "._QXZ("ALIAS")."</td><td align=right><font size=2> "._QXZ("PRESET")."</td><td align=right><font size=2>"._QXZ("C3HU")."</td></tr>\n";
		$CSV_text9.="\""._QXZ("Manual Outbound Calls for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
		$CSV_text9.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("CALL TYPE")."\",\""._QXZ("SERVER")."\",\""._QXZ("PHONE")."\",\""._QXZ("DIALED")."\",\""._QXZ("LEAD")."\",\""._QXZ("CALLERID")."\",\""._QXZ("ALIAS")."\",\""._QXZ("PRESET")."\",\""._QXZ("C3HU")."\"\n";

		$stmt="SELECT call_date,call_type,server_ip,phone_number,number_dialed,lead_id,callerid,group_alias_id,preset_name,customer_hungup,customer_hungup_seconds from ".$user_call_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and call_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and call_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' order by call_date desc limit 10000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		$u=0;
		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$C3HU='';
			if ($row[9]=='BEFORE_CALL') {$row[9]='BC';}
			if ($row[9]=='DURING_CALL') {$row[9]='DC';}
			if (strlen($row[9]) > 1)
				{$C3HU = "$row[9] $row[10]";}

		if ($LOGadmin_hide_phone_data != '0')
			{
			if ($DB > 0) {echo "HIDEPHONEDATA|$row[10]|$LOGadmin_hide_phone_data|\n";}
			$phone_temp = $row[3];
			$dialed_temp = $row[4];
			if ($LOGadmin_hide_phone_data == '4_DIGITS')
				{
				if (strlen($phone_temp) > 0)
					{$row[3] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);}
				if (strlen($dialed_temp) > 0)
					{$row[4] = str_repeat("X", (strlen($dialed_temp) - 4)) . substr($dialed_temp,-4,4);}
				}
			elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
				{
				if (strlen($phone_temp) > 0)
					{$row[3] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);}
				if (strlen($dialed_temp) > 0)
					{$row[4] = str_repeat("X", (strlen($dialed_temp) - 3)) . substr($dialed_temp,-3,3);}
				}
			elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
				{
				if (strlen($phone_temp) > 0)
					{$row[3] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);}
				if (strlen($dialed_temp) > 0)
					{$row[4] = str_repeat("X", (strlen($dialed_temp) - 2)) . substr($dialed_temp,-2,2);}
				}
			else
				{
				if (strlen($phone_temp) > 0)
					{$row[3] = preg_replace("/./",'X',$phone_temp);}
				if (strlen($dialed_temp) > 0)
					{$row[4] = preg_replace("/./",'X',$dialed_temp);}
				}
			}

			$u++;
			$MAIN.="<tr $bgcolor>";
			$MAIN.="<td><font size=1>$u</td>";
			$MAIN.="<td><font size=2>$row[0]</td>";
			$MAIN.="<td align=left><font size=2> $row[1]</td>\n";
			$MAIN.="<td align=left><font size=2> $row[2]</td>\n";
			$MAIN.="<td align=left><font size=2> $row[3] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[4] </td>\n";
			$MAIN.="<td align=right><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$row[5]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$row[5]', '_blank');return false;\">$row[5]</A> </td>\n";
			$MAIN.="<td align=right><font size=2> $row[6] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[7] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[8] </td>\n";
			$MAIN.="<td align=right NOWRAP><font size=2> $C3HU </td></tr>\n";
			$CSV_text9.="\"\",\"$u\",\"$row[0]\",\"$row[1]\",\"$row[2]\",\"$row[3]\",\"$row[4]\",\"$row[5]\",\"$row[6]\",\"$row[7]\",\"$row[8]\",\"$C3HU\"\n";
			}
		$MAIN.="</TABLE><BR><BR>\n";
		}

	if ($did < 1)
		{
		##### vicidial lead searches for this time period #####

		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Lead Searches for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-lead_searches$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=10'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=900 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=1># </td><td NOWRAP><font size=2>"._QXZ("DATE/TIME")." &nbsp; </td><td align=left NOWRAP><font size=2> "._QXZ("TYPE")." &nbsp; </td><td align=left NOWRAP><font size=2> "._QXZ("RESULTS")." &nbsp; </td><td align=left NOWRAP><font size=2> "._QXZ("SEC")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("QUERY")."</td></tr>\n";
		$CSV_text10.="\""._QXZ("Lead Searches for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
		$CSV_text10.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("TYPE")."\",\""._QXZ("RESULTS")."\",\""._QXZ("SEC")."\",\""._QXZ("QUERY")."\"\n";

		$stmt="SELECT event_date,source,results,seconds,search_query from ".$vicidial_lead_search_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and event_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and event_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' order by event_date desc limit 10000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		$u=0;
		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$row[4] = preg_replace("/SELECT count\(\*\) from vicidial_list where/",'',$row[4]);
			$row[4] = preg_replace('/SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner from vicidial_list where /','',$row[4]);

			if (strlen($row[4]) > 100)
				{$row[4] = substr($row[4], 0, 100);}

			$u++;
			$MAIN.="<tr $bgcolor>";
			$MAIN.="<td><font size=1>$u</td>";
			$MAIN.="<td><font size=2>$row[0]</td>";
			$MAIN.="<td align=center><font size=2> $row[1] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[2] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[3] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[4] </td></tr>\n";
			$CSV_text10.="\"\",\"$u\",\"$row[0]\",\"$row[1]\",\"$row[2]\",\"$row[3]\",\"$row[4]\"\n";
			}
		$MAIN.="</TABLE><BR><BR>\n";
		}

	if ($did < 1)
		{
		##### vicidial agent manual dial lead preview skips for this time period #####

		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Preview Lead Skips for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-preview_skips$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=11'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=750 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=1># </td><td NOWRAP><font size=2>"._QXZ("DATE/TIME")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("LEAD ID")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("STATUS")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("COUNT")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("CAMPAIGN")."</td></tr>\n";
		$CSV_text11.="\""._QXZ("Preview Lead Skips for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
		$CSV_text11.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("STATUS")."\",\""._QXZ("COUNT")."\",\""._QXZ("CAMPAIGN")."\"\n";

		$stmt="SELECT user,event_date,lead_id,campaign_id,previous_status,previous_called_count from ".$vicidial_agent_skip_log_table." where user='" . mysqli_real_escape_string($link, $user) . "' and event_date >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and event_date <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' order by event_date desc limit 10000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		$u=0;
		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$u++;
			$MAIN.="<tr $bgcolor>";
			$MAIN.="<td><font size=1>$u</td>";
			$MAIN.="<td><font size=2>$row[1]</td>";
			$MAIN.="<td align=right><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$row[2]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$row[2]', '_blank');return false;\">$row[2]</A> </td>\n";
			$MAIN.="<td align=right><font size=2> $row[4] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[5] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[3] </td></tr>\n";
			$CSV_text11.="\"\",\"$u\",\"$row[1]\",\"$row[2]\",\"$row[4]\",\"$row[5]\",\"$row[3]\"\n";
			}
		$MAIN.="</TABLE><BR><BR>\n";

	##### BEGIN switch lead log entries #####
		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Agent Lead Switches for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-switch_leads$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=13'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=750 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=1># </td><td NOWRAP><font size=2>"._QXZ("DATE/TIME")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("FROM LEAD ID")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("TO LEAD ID")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("CALL ID")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("UNIQUEID")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("PHONE")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("CAMPAIGN")."</td></tr>\n";
		$CSV_text13.="\""._QXZ("Agent Lead Switches for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
		$CSV_text13.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("FROM LEAD ID")."\",\""._QXZ("TO LEAD ID")."\",\""._QXZ("CALL ID")."\",\""._QXZ("UNIQUEID")."\",\""._QXZ("PHONE")."\",\""._QXZ("CAMPAIGN")."\"\n";

		$stmt="SELECT event_time,lead_id,stage,caller_code,uniqueid,comments,campaign_id from ".$vicidial_agent_function_log." where user='" . mysqli_real_escape_string($link, $user) . "' and event_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and event_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' and function='switch_lead' order by event_time desc limit 10000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		$u=0;
		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$u++;
			$MAIN.="<tr $bgcolor>";
			$MAIN.="<td><font size=1>$u</td>";
			$MAIN.="<td><font size=2>$row[0]</td>";
			$MAIN.="<td align=center><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$row[1]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$row[1]', '_blank');return false;\">$row[1]</A> </td>\n";
			$MAIN.="<td align=center><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$row[2]\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$row[2]', '_blank');return false;\">$row[2]</A> </td>\n";
			$MAIN.="<td><font size=2>$row[3]</td>";
			$MAIN.="<td align=right><font size=2> $row[4] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[5] </td>\n";
			$MAIN.="<td align=right><font size=2> $row[6] </td></tr>\n";
			$CSV_text13.="\"\",\"$u\",\"$row[0]\",\"$row[1]\",\"$row[2]\",\"$row[3]\",\"$row[4]\",\"$row[5]\",\"$row[6]\"\n";
			}
		$MAIN.="</TABLE><BR><BR>\n";

	##### BEGIN manager pause code approval entries #####
		$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" SIZE=3><B>"._QXZ("Manager Pause Code Approvals for this Time Period").": </FONT><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2>("._QXZ("10000 record limit").") $NWB#user_stats-pause_code_approvals$NWE &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$download_link&file_download=14'>["._QXZ("DOWNLOAD")."]</a></B></FONT>\n";
		$MAIN.="<TABLE width=750 cellspacing=0 cellpadding=1>\n";
		$MAIN.="<tr><td><font size=1># </td><td NOWRAP><font size=2>"._QXZ("DATE/TIME")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("AGENT")." &nbsp; </td><td align=center NOWRAP><font size=2> "._QXZ("AGENT USER GROUP")." &nbsp; </td><td align=center NOWRAP><font size=2> "._QXZ("CAMPAIGN")." &nbsp; </td><td align=right NOWRAP><font size=2> "._QXZ("PAUSE CODE")." &nbsp; </td></tr>\n";
		$CSV_text14.="\""._QXZ("Manager Pause Code Approvals for this Time Period").": ("._QXZ("10000 record limit").")\"\n";
		$CSV_text14.="\"\",\"#\",\""._QXZ("DATE/TIME")."\",\""._QXZ("AGENT")."\",\""._QXZ("AGENT USER GROUP")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("PAUSE CODE")."\"\n";

		$stmt="SELECT event_time,user,user_group,campaign_id,comments from ".$vicidial_agent_function_log." where stage='" . mysqli_real_escape_string($link, $user) . "' and event_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and event_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' and function='mgrapr_pause_code' order by event_time desc limit 10000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		$u=0;
		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$u++;
			$MAIN.="<tr $bgcolor>";
			$MAIN.="<td><font size=1>$u</td>";
			$MAIN.="<td><font size=2>$row[0]</td>";
			$MAIN.="<td align=center><font size=2> <A HREF=\"$PHP_SELF?user=$row[1]\" target=\"_blank\">$row[1]</A> </td>\n";
			$MAIN.="<td align=center><font size=2>$row[2]</td>";
			$MAIN.="<td align=center><font size=2>$row[3]</td>";
			$MAIN.="<td align=right><font size=2> $row[4] </td>\n";
			$CSV_text14.="\"\",\"$u\",\"$row[0]\",\"$row[1]\",\"$row[2]\",\"$row[3]\",\"$row[4]\"\n";
			}
		$MAIN.="</TABLE><BR><BR>\n";
		}
	}

$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

$MAIN.="\n\n\n<br><br><br>\n\n";


$MAIN.="<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."|$db_source</font>";

$MAIN.="</TD></TR><TABLE>";
$MAIN.="</body>";
$MAIN.="</html>";


if ($file_download>0) 
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "user_stats_$US$FILE_TIME.csv";
	$CSV_var="CSV_text".$file_download;
	$CSV_text=preg_replace('/^\s+/', '', $$CSV_var);
	$CSV_text=preg_replace('/\n\s+,/', ',', $CSV_text);
	$CSV_text=preg_replace('/ +\"/', '"', $CSV_text);
	$CSV_text=preg_replace('/\" +/', '"', $CSV_text);
	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$CSV_text";
	}
else
	{
	header ("Content-type: text/html; charset=utf-8");
	echo $HEADER;
	require("admin_header.php");
	echo $MAIN;
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

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

exit;

?>

