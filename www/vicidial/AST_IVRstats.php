<?php 
# AST_IVRstats.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 81026-2026 - First build
# 81107-0341 - Added time range and option and 15-minute increment graph
# 81107-1148 - Added average times and totals
# 81108-0922 - Added no-callerID and unique caller counts
# 90310-2056 - Admin header
# 90508-0644 - Changed to PHP long tags
# 91112-0719 - Added in-group names to select list
# 100214-1421 - Sort menu alphabetically
# 100301-1401 - Added popup date selector
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110525-1907 - Added support for outbound log analysis
# 110703-1850 - Added download option
# 111103-2315 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 130114-0115 - Added report logging
# 130610-1004 - Finalized changing of all ereg instances to preg
# 130621-0738 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130704-0940 - Fixed issue #675
# 130901-0819 - Changed to mysqli PHP functions
# 140108-0737 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 140501-1900 - Fixed 15-min graph for inbound and outbound
# 141113-2319 - Finalized adding QXZ translation to all admin files
# 141230-1448 - Added code for on-the-fly language translations display
# 150516-1301 - Fixed Javascript element problem, Issue #857
# 151125-1643 - Added search archive option
# 151204-0544 - Added code to look for "CALL_MENU" and "XML_PULL" In-Group permissions for those list options
# 160227-1130 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170227-1712 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180507-2315 - Added new help display
# 191013-0835 - Fixes for PHP7
# 220221-0926 - Added allow_web_debug system setting
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["type"]))				{$type=$_GET["type"];}
	elseif (isset($_POST["type"]))		{$type=$_POST["type"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))		{$file_download=$_POST["file_download"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if (strlen($shift)<2) {$shift='ALL';}
if (strlen($type)<2) {$type='inbound';}
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = "$NOW_DATE 00:00:00";}
if (!isset($end_date)) {$end_date = "$NOW_DATE 23:23:59";}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

if ($type == 'inbound')
	{$report_name = 'Inbound IVR Report';}
else
	{$report_name = 'Outbound IVR Report';}
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$MAIN.="$stmt\n";}
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
	$SSreport_default_format =		$row[6];
	$SSallow_web_debug =			$row[7];
	}
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_outbound_ivr_log", "vicidial_closer_log", "live_inbound_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_outbound_ivr_log_table=use_archive_table("vicidial_outbound_ivr_log");
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	$live_inbound_log_table=use_archive_table("live_inbound_log");
	}
else
	{
	$vicidial_outbound_ivr_log_table="vicidial_outbound_ivr_log";
	$vicidial_closer_log_table="vicidial_closer_log";
	$live_inbound_log_table="live_inbound_log";
	}
#############

$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);
$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$query_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$end_date);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$type = preg_replace('/[^-_0-9a-zA-Z]/', '', $type);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

# Variables filtered further down in the code
# $group

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$shift = preg_replace('/[^-_0-9a-zA-Z]/', '', $shift);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$shift = preg_replace('/[^-_0-9\p{L}]/u', '', $shift);
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}


##### BEGIN Generate select list for which in-groups or campaigns to display #####

if ($type == 'inbound')
	{
	$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='CALL_MENU';";
	if ($DB) {$MAIN.="|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$CALL_MENUcount = $row[0];

	$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='XML_PULL';";
	if ($DB) {$MAIN.="|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$XML_PULLcount = $row[0];

	$CALL_MENUdisplay=1;
	$XML_PULLdisplay=1;
	if ($CALL_MENUcount > 0)
		{
		$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='CALL_MENU' $LOGadmin_viewable_groupsSQL;";
		if ($DB) {$MAIN.="|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$CALL_MENUdisplay = $row[0];
		}
	if ($XML_PULLcount > 0)
		{
		$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='XML_PULL' $LOGadmin_viewable_groupsSQL;";
		if ($DB) {$MAIN.="|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$XML_PULLdisplay = $row[0];
		}
	}

if ($type == 'inbound')
	{$stmt="select group_id,group_name from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";}
else
	{$stmt="select campaign_id,campaign_name from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
$LISTgroups=array();
$LISTgroups_names=array();
if ($type == 'inbound')
	{
	if ($CALL_MENUdisplay > 0)
		{
		$LISTgroups[$i]='CALLMENU';
		$LISTgroups_names[$i]=_QXZ('IVR');
		$i++;
		$groups_to_print++;
		}
	if ($XML_PULLdisplay > 0)
		{
		$LISTgroups[$i]='XMLPULL';
		$LISTgroups_names[$i]=_QXZ('Dynamic Application');
		$i++;
		$groups_to_print++;
		}
	}
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTgroups[$i] =		$row[0];
	$LISTgroups_names[$i] = $row[1];
	$i++;
	}

##### END Generate select list for which in-groups or campaigns to display #####


$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group[$i] = preg_replace('/[^-_0-9\p{L}]/u','',$group[$i]);
	$group_string .= "$group[$i]|";
	$group_SQL .= "'$group[$i]',";
	$groupQS .= "&group[]=$group[$i]";
	$i++;
	}
if ( (preg_match('/\s\-\-NONE\-\-\s/',$group_string) ) or ($group_ct < 1) )
	{
	$group_SQL = "''";
#	$group_SQL = "group_id IN('')";
	}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
#	$group_SQL = "group_id IN($group_SQL)";
	}
if (strlen($group_SQL)<3) {$group_SQL="''";}


$stmt="select vsc_id,vsc_name from vicidial_status_categories;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statcats_to_print = mysqli_num_rows($rslt);
$i=0;
$vsc_id=array();
$vsc_name=array();
$vsc_count=array();
while ($i < $statcats_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$vsc_id[$i] =	$row[0];
	$vsc_name[$i] =	$row[1];
	$vsc_count[$i] = 0;
	$i++;
	}

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";

if ($shift == 'RANGE') 
	{
	$query_date_BEGIN = "$query_date";   
	$query_date_END = "$end_date";
	}
else
	{
	$EXquery_date = explode(' ',$query_date);
	$query_date = "$EXquery_date[0]";   
	$EXend_date = explode(' ',$end_date);
	$end_date = "$EXend_date[0]";   

	if ($shift == 'AM') 
		{
		$time_BEGIN=$AM_shift_BEGIN;
		$time_END=$AM_shift_END;
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "03:45:00";}   
		if (strlen($time_END) < 6) {$time_END = "15:14:59";}
		}
	if ($shift == 'PM') 
		{
		$time_BEGIN=$PM_shift_BEGIN;
		$time_END=$PM_shift_END;
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "15:15:00";}
		if (strlen($time_END) < 6) {$time_END = "23:15:00";}
		}
	if ($shift == 'ALL') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
		if (strlen($time_END) < 6) {$time_END = "23:59:59";}
		}
	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";
	}

$query_dateARRAY = explode(" ",$query_date_BEGIN);
$query_date_D = $query_dateARRAY[0];
$query_date_T = $query_dateARRAY[1];
$end_dateARRAY = explode(" ",$query_date_END);
$end_date_D = $end_dateARRAY[0];
$end_date_T = $end_dateARRAY[1];



$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HEADER.="<script src='chart/Chart.js'></script>\n"; 
$HEADER.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

$JS_text="<script language='Javascript'>\n";

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#IVRstats$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

if ($DB > 0)
	{
	$MAIN.="<BR>\n";
	$MAIN.="$group_ct|$group_string|$group_SQL\n";
	$MAIN.="<BR>\n";
	$MAIN.="$shift|$query_date|$end_date\n";
	$MAIN.="<BR>\n";
	}

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN='TOP'>";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=type VALUE=\"$type\">\n";
$MAIN.=_QXZ("Date Range").":<BR>\n";

$MAIN.="<INPUT TYPE=hidden NAME=query_date ID=query_date VALUE=\"$query_date\">\n";
$MAIN.="<INPUT TYPE=hidden NAME=end_date ID=end_date VALUE=\"$end_date\">\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$query_date_D\">";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="function openNewWindow(url)\n";
$MAIN.="  {\n";
$MAIN.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$MAIN.="  }\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date_D'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

$MAIN.=" &nbsp; <INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$MAIN.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$end_date_D\">";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'end_date_D'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

$MAIN.=" &nbsp; <INPUT TYPE=TEXT NAME=end_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$end_date_T\">";


$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";

if ($type == 'inbound')
	{
	$MAIN.=_QXZ("Inbound Groups").": \n";
	$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
	$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
	$o=0;
		while ($groups_to_print > $o)
		{
		if (preg_match("/\|$LISTgroups[$o]\|/",$group_string)) 
			{$MAIN.="<option selected value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroups_names[$o]</option>\n";}
		else
			{$MAIN.="<option value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroups_names[$o]</option>\n";}
		$o++;
		}
	$MAIN.="</SELECT>\n";
	$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
	$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
	$MAIN.="<a href=\"$PHP_SELF?DB=$DB&type=$type&query_date=$query_date&end_date=$end_date&query_date_D=$query_date_D&query_date_T=$query_date_T&end_date_D=$end_date_D&end_date_T=$end_date_T$groupQS&shift=$shift&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a> | ";
	$MAIN.="<a href=\"./admin.php?ADD=3111&group_id=$group[0]\">"._QXZ("MODIFY")."</a> | ";
	$MAIN.="<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> | ";
	$MAIN.="<a href=\"./AST_CLOSERstats.php?query_date=$query_date&end_date=$end_date&shift=$shift$groupQS\">"._QXZ("CLOSER REPORT")."</a> \n";
	$MAIN.="</FONT>\n";
	}
else
	{
	$MAIN.=_QXZ("Campaigns").": \n";
	$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
	$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
	$o=0;
		while ($groups_to_print > $o)
		{
		if (preg_match("/\|$LISTgroups[$o]\|/",$group_string)) 
			{$MAIN.="<option selected value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroups_names[$o]</option>\n";}
		else
			{$MAIN.="<option value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroups_names[$o]</option>\n";}
		$o++;
		}
	$MAIN.="</SELECT>\n";
	$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
	$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
	$MAIN.="<a href=\"$PHP_SELF?DB=$DB&type=$type&query_date=$query_date&end_date=$end_date&query_date_D=$query_date_D&query_date_T=$query_date_T&end_date_D=$end_date_D&end_date_T=$end_date_T$groupQS&shift=$shift&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a> | ";
	$MAIN.="<a href=\"./admin.php?ADD=31&campaign_id=$group[0]\">"._QXZ("MODIFY")."</a> | ";
	$MAIN.="<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> | ";
	$MAIN.="<a href=\"./AST_VDADstats.php?query_date=$query_date&end_date=$end_date&shift=$shift$groupQS\">"._QXZ("OUTBOUND REPORT")."</a> \n";
	$MAIN.="</FONT>\n";
	}

$MAIN.="</TD></TR>\n";
$MAIN.="<TR><TD>\n";

#$MAIN.="<SELECT SIZE=1 NAME=group>\n";
#	$o=0;
#	while ($groups_to_print > $o)
#	{
#		if ($groups[$o] == $group) {$MAIN.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
#		  else {$MAIN.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
#		$o++;
#	}
#$MAIN.="</SELECT>\n";
$MAIN.=_QXZ("Shift").": <SELECT SIZE=1 NAME=shift>\n";
$MAIN.="<option selected value=\"$shift\">"._QXZ("$shift")."</option>\n";
$MAIN.="<option value=\"\">--</option>\n";
$MAIN.="<option value=\"AM\">"._QXZ("AM")."</option>\n";
$MAIN.="<option value=\"PM\">"._QXZ("PM")."</option>\n";
$MAIN.="<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
$MAIN.="<option value=\"RANGE\">"._QXZ("RANGE")."</option>\n";
$MAIN.="</SELECT>\n";

$MAIN.="<SCRIPT LANGUAGE=\"JavaScript\">\n";

$MAIN.="function submit_form()\n";
$MAIN.="	{\n";
$MAIN.="	document.vicidial_report.end_date.value = document.vicidial_report.end_date_D.value + \" \" + document.vicidial_report.end_date_T.value;\n";
$MAIN.="	document.vicidial_report.query_date.value = document.vicidial_report.query_date_D.value + \" \" + document.vicidial_report.query_date_T.value;\n";
$MAIN.="	document.vicidial_report.submit();\n";
$MAIN.="	}\n";

$MAIN.="</SCRIPT>\n";

$MAIN.="<BR>"._QXZ("Display as").":&nbsp; ";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR>";
if ($archives_available=="Y") 
	{
	$MAIN.="<BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR>\n";
	}
$MAIN.="&nbsp; &nbsp; <input style='background-color:#$SSbutton_color' type=button value=\""._QXZ("SUBMIT")."\" name=smt id=smt onClick=\"submit_form()\">\n";

$MAIN.="</TD></TR></TABLE>\n";
$MAIN.="</FORM>\n\n";

$MAIN.="<PRE><FONT SIZE=2>\n\n";


if ($groups_to_print < 1)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT AN IN-GROUP AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$MAIN.=_QXZ("IVR Stats Report").": $query_date_BEGIN "._QXZ("to")." $query_date_END               $NOW_TIME\n";
	$MAIN.="                  $group_string\n";

	$CSV_text.="\""._QXZ("IVR Stats Report").": $query_date_BEGIN "._QXZ("to")." $query_date_END\",\"$NOW_TIME\"\n";

	$TOTALcalls=0;
	$NOCALLERIDcalls=0;
	$UNIQUEcallers=0;
	$totFLOWivr_time=0;
	$totFLOWtotal_time=0;

	##### Grab all records for the IVR for the specified time period
	if ($type == 'inbound')
		{
		$stmt="select uniqueid,extension,start_time,comment_a,comment_b,comment_d,UNIX_TIMESTAMP(start_time),phone_ext from ".$live_inbound_log_table." where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a IN($group_SQL) order by uniqueid,start_time;";
		}
	else
		{
		$stmt="select uniqueid,caller_code,event_date,campaign_id,menu_id,menu_action,UNIX_TIMESTAMP(event_date),caller_code from ".$vicidial_outbound_ivr_log_table." where event_date >= '$query_date_BEGIN' and event_date <= '$query_date_END' and campaign_id IN($group_SQL) order by uniqueid,event_date,menu_action desc;";
		}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$logs_to_print = mysqli_num_rows($rslt);
	$p=0;
	$uniqueid=array();
	$extension=array();
	$start_time=array();
	$comment_a=array();
	$comment_b=array();
	$comment_d=array();
	$epoch=array();
	$phone_ext=array();
	while ($p < $logs_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$uniqueid[$p] =		$row[0];
		$extension[$p] =	$row[1];
		$start_time[$p] =	$row[2];
		$comment_a[$p] =	$row[3];
		$comment_b[$p] =	$row[4];
		$comment_d[$p] =	$row[5];
		$epoch[$p] =		$row[6];
		$phone_ext[$p] =	$row[7];
		$p++;
		}

	### create the call flow of all calls by uniqueid
	$last_uniqueid='';
	$first_epoch=array();
	$last_epoch=array();
	$unique_calls=array();
	$FLOWuniqueid=array();
	$p=0;
	$r=-1;
	while ($p < $logs_to_print)
		{
		if ($DB > 0) {$MAIN.="$p|$uniqueid[$p]|$comment_b[$p]";}
		if ($last_uniqueid === "$uniqueid[$p]")
			{
			$unique_calls[$r] .= "----------$comment_b[$p]";
			if ($DB > 0) {$MAIN.="   $r|$unique_calls[$r]\n";}
			$last_epoch[$r]=$epoch[$p];
			}
		else
			{
			$r++;
			$caller_id[$r]=$phone_ext[$p];
			if (strlen($phone_ext[$p])<2)
				{$NOCALLERIDcalls++;}
			else
				{
				if (!preg_match("/_$phone_ext[$p]_/",$unique_callerIDs))
					{
					$unique_callerIDs .= "_$phone_ext[$p]_";
					$UNIQUEcallers++;
					}
				}
			$first_epoch[$r]=$epoch[$p];
			$last_epoch[$r]=$epoch[$p];
			$unique_calls[$r] = $comment_b[$p];
			$FLOWuniqueid[$r] = "$uniqueid[$p]";
			$last_uniqueid = "$uniqueid[$p]";
			if ($DB > 0) {$MAIN.="   $r|$unique_calls[$r]\n";}
			}
		$p++;
		}

	### sort call flows for counting
	$RAWunique_calls = $unique_calls;
	if ($logs_to_print > 0)
		{sort($unique_calls);}


	### count each unique call flow
	$last_Suniqueid='';
	$p=-1;
	$s=0;
	$STunique_calls=array();
	$STunique_calls_count=array();
	while ($s <= $r)
		{
		if ($DB > 0) {$MAIN.="$s|$unique_calls[$s]\n";}
		if ($last_Suniqueid === "$unique_calls[$s]")
			{
			$STunique_calls_count[$p]++;
			}
		else
			{
			$p++;
			$STunique_calls[$p] = $unique_calls[$s];
			$last_Suniqueid = "$unique_calls[$s]";
			$STunique_calls_count[$p]=1;
			}
		$s++;
		}


	### put call flows and counts together for sorting again
	$TOTALcalls=0;
	$s=0;
	$FLOWunique_calls=array();
	while ($s <= $p)
		{
		$TOTALcalls = ($TOTALcalls + $STunique_calls_count[$s]);
		$STunique_calls_count[$s] = sprintf("%07s", $STunique_calls_count[$s]);
		$FLOWunique_calls[$s] = "$STunique_calls_count[$s]__________$STunique_calls[$s]";
		$s++;
		}

	#### PRINT TOTAL CALLS INTO THIS IVR
	$MAIN.="\n";
	$MAIN.=_QXZ("Calls taken into this IVR",25).":   $TOTALcalls\n";
	$MAIN.=_QXZ("Calls with no CallerID",25).":      $NOCALLERIDcalls\n";
	$MAIN.=_QXZ("Unique Callers",25).":              $UNIQUEcallers\n";
	$MAIN.="\n";

	$CSV_text.="\""._QXZ("Calls taken into this IVR").": $TOTALcalls\"\n";
	$CSV_text.="\""._QXZ("Calls with no CallerID").": $NOCALLERIDcalls\"\n";
	$CSV_text.="\""._QXZ("Unique Callers").": $UNIQUEcallers\"\n\n";

	### sort call flows for counting
	if ($p > 0)
		{rsort($FLOWunique_calls);}


	### put call flows and counts together for sorting again
	$RUC_ct = count($RAWunique_calls);
	$s=0;
	$FLOWivr_time=array();
	$FLOWunique_calls_list=array();
	while ($s <= $p)
		{
		$FLOWsummary = explode('__________',$FLOWunique_calls[$s]);
		$FLOWsummary[0] = ($FLOWsummary[0] + 0);

		$t=0;
		while ($t < $RUC_ct)
			{
			if ($FLOWsummary[1] === "$RAWunique_calls[$t]")
				{
				$FLOWunique_calls_list[$s] .= "'$FLOWuniqueid[$t]',";
				if ($last_epoch[$t] <= $first_epoch[$t]) {$last_epoch[$t] = ($first_epoch[$t] + 5);}
				else {$last_epoch[$t] = ($last_epoch[$t] + 10);}
				$FLOWivr_time[$s] = ($FLOWivr_time[$s] + ($last_epoch[$t] - $first_epoch[$t]));
				}
			$t++;
			}

		$s++;
		}


	### put call flows and counts together for sorting again
	$s=0;

	$ASCII_text.="+--------+--------+--------+--------+------+------+\n";
	$ASCII_text.="|        |        | "._QXZ("QUEUE",6)." | "._QXZ("QUEUE",6)." | "._QXZ("IVR",4)." | "._QXZ("TOTAL",5)."|\n";
	$ASCII_text.="| "._QXZ("IVR",6)." | "._QXZ("QUEUE",6)." | "._QXZ("DROP",6)." | "._QXZ("DROP",6)." | "._QXZ("AVG",4)." | "._QXZ("AVG",4)." |\n";
	$ASCII_text.="| "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("PERCENT",7)."| "._QXZ("TIME",4)." | "._QXZ("TIME",4)." | "._QXZ("CALL PATH")."\n";
	$ASCII_text.="+--------+--------+--------+--------+------+------+------------\n";

	######## GRAPHING #########
	$graph_stats=array();
	$max_ivr_calls=1;
	$max_queue_calls=1;
	$max_queue_drops=1;
	$max_queue_drops_percent=1;
	$max_ivr_avg=1;
	$max_total_avg=1;
	###########################

	$CSV_text.="\"\",\""._QXZ("IVR CALLS")."\",\""._QXZ("QUEUE CALLS")."\",\""._QXZ("QUEUE DROP CALLS")."\",\""._QXZ("QUEUE DROP PERCENT")."\",\""._QXZ("IVR AVG TIME")."\",\""._QXZ("TOTAL AVG TIME")."\",\""._QXZ("CALL PATH")."\"\n";

	$FLOWdrop=array();
	$FLOWtotal=array();
	$FLOWdropPCT=array();
	$FLOWclose_time=array();
	$FLOWtotal_time=array();
	$avgFLOWivr_time=array();
	$avgFLOWtotal_time=array();
	while ($s <= $p)
		{
		$FLOWdrop[$s]=0;
		$FLOWtotal[$s]=0;
		$FLOWdropPCT[$s]=0;
		$FLOWsummary = explode('__________',$FLOWunique_calls[$s]);
		$FLOWsummary[0] = ($FLOWsummary[0] + 0);
		$FLOWunique_calls_list[$s] = preg_replace("/,$/","",$FLOWunique_calls_list[$s]);


		if ($type == 'inbound')
			{
			##### Grab all records for the IVR for the specified time period
			$stmt="select status,length_in_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and uniqueid IN($FLOWunique_calls_list[$s]);";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {$ASCII_text.="$stmt\n";}
			$vcl_statuses_to_print = mysqli_num_rows($rslt);
			$w=0;
			$vcl_statuses = array();
			while ($w < $vcl_statuses_to_print)
				{
				$row=mysqli_fetch_row($rslt);
				$vcl_statuses[$w] =		$row[0];
				if ( (preg_match('/DROP/',$vcl_statuses[$w])) or (preg_match('/XDROP/',$vcl_statuses[$w])) )
					{$FLOWdrop[$s]++;}
				$FLOWclose_time[$s] = ($FLOWclose_time[$s] + $row[1]);
				$FLOWtotal[$s]++;
				$w++;
				}
			}
		
		$FLOWdropPCT[$s] = (MathZDC($FLOWdrop[$s], $FLOWtotal[$s]) * 100);
		$FLOWdropPCT[$s] = round($FLOWdropPCT[$s], 2);

		if ($FLOWsummary[0]>$max_ivr_calls) {$max_ivr_calls=$FLOWsummary[0];}
		if ($FLOWtotal[$s]>$max_queue_calls) {$max_queue_calls=$FLOWtotal[$s];}
		if ($FLOWdrop[$s]>$max_queue_drops) {$max_queue_drops=$FLOWdrop[$s];}
		if ($FLOWdropPCT[$s]>$max_queue_drops_percent) {$max_queue_drops_percent=$FLOWdropPCT[$s];}
		$graph_stats[$s][1]=$FLOWsummary[0];
		$graph_stats[$s][2]=$FLOWtotal[$s];
		$graph_stats[$s][3]=$FLOWdrop[$s];
		$graph_stats[$s][4]=$FLOWdropPCT[$s];

		$FLOWsummary[0] =	sprintf("%6s", $FLOWsummary[0]);
		$FLOWtotal[$s] =	sprintf("%6s", $FLOWtotal[$s]);
		$FLOWdrop[$s] =		sprintf("%6s", $FLOWdrop[$s]);
		$FLOWdropPCT[$s] =	sprintf("%6s", $FLOWdropPCT[$s]);
		$FLOWsummary[1] = preg_replace('/\-\-\-\-\-\-\-\-\-\-/', ' / ', $FLOWsummary[1]);
		$FLOWtotal_time[$s] = ($FLOWivr_time[$s] + $FLOWclose_time[$s]);

		$avgFLOWivr_time[$s] = MathZDC($FLOWivr_time[$s], $FLOWsummary[0]);
		$avgFLOWivr_time[$s] = round($avgFLOWivr_time[$s], 0);
		$avgFLOWivr_time[$s] = sprintf("%4s", $avgFLOWivr_time[$s]);
		$avgFLOWtotal_time[$s] = MathZDC($FLOWtotal_time[$s], $FLOWsummary[0]);
		$avgFLOWtotal_time[$s] = round($avgFLOWtotal_time[$s], 0);
		$avgFLOWtotal_time[$s] = sprintf("%4s", $avgFLOWtotal_time[$s]);

		if (trim($avgFLOWivr_time[$s])>$max_ivr_avg) {$max_ivr_avg=trim($avgFLOWivr_time[$s]);}
		if (trim($avgFLOWtotal_time[$s])>$max_total_avg) {$max_total_avg=trim($avgFLOWtotal_time[$s]);}
		$graph_stats[$s][0]=$FLOWsummary[1];
		$graph_stats[$s][5]=trim($avgFLOWivr_time[$s]);
		$graph_stats[$s][6]=trim($avgFLOWtotal_time[$s]);


		$totFLOWtotal_time = ($totFLOWtotal_time + $FLOWtotal_time[$s]);
		$totFLOWivr_time = ($totFLOWivr_time + $FLOWivr_time[$s]);
		$totFLOWtotal = ($totFLOWtotal + $FLOWtotal[$s]);
		$totFLOWdrop = ($totFLOWdrop + $FLOWdrop[$s]);

		$ASCII_text.="| $FLOWsummary[0] | $FLOWtotal[$s] | $FLOWdrop[$s] | $FLOWdropPCT[$s]%| $avgFLOWivr_time[$s] | $avgFLOWtotal_time[$s] | $FLOWsummary[1]\n";
		$CSV_text.="\"\",\"$FLOWsummary[0]\",\"$FLOWtotal[$s]\",\"$FLOWdrop[$s]\",\"$FLOWdropPCT[$s]%\",\"$avgFLOWivr_time[$s]\",\"$avgFLOWtotal_time[$s]\",\"$FLOWsummary[1]\"\n";

		$s++;
		}
	$TOTALcalls = sprintf("%6s", $TOTALcalls);
	$totFLOWtotal = sprintf("%6s", $totFLOWtotal);
	$totFLOWdrop = sprintf("%6s", $totFLOWdrop);
	$TavgFLOWivr_time = MathZDC($totFLOWivr_time, $TOTALcalls);
	$TavgFLOWivr_time = round($TavgFLOWivr_time, 0);
	$TavgFLOWivr_time = sprintf("%4s", $TavgFLOWivr_time);
	$TavgFLOWtotal_time = MathZDC($totFLOWtotal_time, $TOTALcalls);
	$TavgFLOWtotal_time = round($TavgFLOWtotal_time, 0);
	$TavgFLOWtotal_time = sprintf("%4s", $TavgFLOWtotal_time);
	$totFLOWdropPCT = (MathZDC($totFLOWdrop, $totFLOWtotal) * 100);
	$totFLOWdropPCT = round($totFLOWdropPCT, 0);
	$totFLOWdropPCT = sprintf("%5s", $totFLOWdropPCT);

	$ASCII_text.="+--------+--------+--------+--------+------+------+------------\n";
	$ASCII_text.="| $TOTALcalls | $totFLOWtotal | $totFLOWdrop | $totFLOWdropPCT% | $TavgFLOWivr_time | $TavgFLOWtotal_time |\n";
	$ASCII_text.="+--------+--------+--------+--------+------+------+\n";

	$CSV_text.="\"\",\"$TOTALcalls\",\"$totFLOWtotal\",\"$totFLOWdrop\",\"$totFLOWdropPCT%\",\"$TavgFLOWivr_time\",\"$TavgFLOWtotal_time\"\n";

	# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
	$multigraph_text="";
	$graph_id++;
	$graph_array=array("IVRSTATS_IVRCALLSdata|1|IVR CALLS|integer|", "IVRSTATS_QUEUECALLSdata|2|QUEUE CALLS|integer|", "IVRSTATS_QUEUEDROPCALLSdata|3|QUEUE DROP CALLS|integer|", "IVRSTATS_QUEUEDROPPERCENTdata|4|QUEUE DROP PERCENT|percent|", "IVRSTATS_IVRAVGTIMEdata|5|IVR AVG TIME|time|", "IVRSTATS_TOTALAVGTIMEdata|6|TOTAL AVG TIME|time|");
	$default_graph="bar"; # Graph that is initally displayed when page loads
	include("graph_color_schemas.inc"); 

	$graph_totals_array=array();
	$graph_totals_rawdata=array();
	for ($q=0; $q<count($graph_array); $q++) {
		$graph_info=explode("|", $graph_array[$q]); 
		$current_graph_total=0;
		$dataset_name=$graph_info[0];
		$dataset_index=$graph_info[1]; 
		$dataset_type=$graph_info[3];

		$JS_text.="var $dataset_name = {\n";
		# $JS_text.="\ttype: \"\",\n";
		# $JS_text.="\t\tdata: {\n";
		$datasets="\t\tdatasets: [\n";
		$datasets.="\t\t\t{\n";
		$datasets.="\t\t\t\tlabel: \"\",\n";
		$datasets.="\t\t\t\tfill: false,\n";

		$labels="\t\tlabels:[";
		$data="\t\t\t\tdata: [";
		$graphConstantsA="\t\t\t\tbackgroundColor: [";
		$graphConstantsB="\t\t\t\thoverBackgroundColor: [";
		$graphConstantsC="\t\t\t\thoverBorderColor: [";
		for ($d=0; $d<count($graph_stats); $d++) {
			$labels.="\"".preg_replace('/ +/', ' ', $graph_stats[$d][0])."\",";
			$data.="\"".$graph_stats[$d][$dataset_index]."\","; 
			$current_graph_total+=$graph_stats[$d][$dataset_index];
			$bgcolor=$backgroundColor[($d%count($backgroundColor))];
			$hbgcolor=$hoverBackgroundColor[($d%count($hoverBackgroundColor))];
			$hbcolor=$hoverBorderColor[($d%count($hoverBorderColor))];
			$graphConstantsA.="\"$bgcolor\",";
			$graphConstantsB.="\"$hbgcolor\",";
			$graphConstantsC.="\"$hbcolor\",";
		}	
		$graphConstantsA.="],\n";
		$graphConstantsB.="],\n";
		$graphConstantsC.="],\n";
		$labels=preg_replace('/,$/', '', $labels)."],\n";
		$data=preg_replace('/,$/', '', $data)."],\n";
	
		$graph_totals_rawdata[$q]=$current_graph_total;
		switch($dataset_type) {
			case "time":
				$graph_totals_array[$q]="  <caption align=\"bottom\">"._QXZ("TOTAL")." - ".sec_convert($current_graph_total, 'H')." </caption>\n";
				$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = Math.round(data.datasets[0].data[tooltipItem.index]); return value.toHHMMSS();}}}, legend: { display: false }},";
				break;
			case "percent":
				$graph_totals_array[$q]="";
				$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = data.datasets[0].data[tooltipItem.index]; return value + '%';}}}, legend: { display: false }},";
				break;
			default:
				$graph_totals_array[$q]="  <caption align=\"bottom\">"._QXZ("TOTAL").": $current_graph_total</caption>\n";
				$chart_options="options: { legend: { display: false }},";
				break;
		}

		$datasets.=$data;
		$datasets.=$graphConstantsA.$graphConstantsB.$graphConstantsC.$graphConstants; # SEE TOP OF SCRIPT
		$datasets.="\t\t\t}\n";
		$datasets.="\t\t]\n";
		$datasets.="\t}\n";

		$JS_text.=$labels.$datasets;
		# $JS_text.="}\n";
		# $JS_text.="prepChart('$default_graph', $graph_id, $q, $dataset_name);\n";
		$JS_text.="var main_ctx = document.getElementById(\"CanvasID".$graph_id."_".$q."\");\n";
		$JS_text.="var GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: '$default_graph', $chart_options data: $dataset_name});\n";
	}

	$graph_count=count($graph_array);
	$graph_title=_QXZ("CALL STATUS STATS");
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas;

	$JS_text.="</script>\n";

	if ($report_display_type=="HTML") 
		{
		$MAIN.=$GRAPH_text;
		}
	else 
		{
		$MAIN.=$ASCII_text;
		}
	
	##############################
	#########  TIME STATS

	$MAIN.="\n";
	$MAIN.="---------- "._QXZ("TIME STATS")."\n";

	$MAIN.="<FONT SIZE=0>\n";

	if ($type == 'inbound')
		{
		$inb_15min_array=array();
		$stmt="select uniqueid, SEC_TO_TIME((TIME_TO_SEC(min(start_time)) DIV 900) * 900) as stime from ".$live_inbound_log_table." where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a IN($group_SQL) group by uniqueid";
		$rslt=mysql_to_mysqli($stmt, $link);
		while ($row=mysqli_fetch_row($rslt)) 
			{
			$time_index=substr($row[1], 0, -2);
			$time_index=preg_replace('/[^0-9]/', '', $time_index);
			$inb_15min_array["$time_index"]++;
			}
		}
	else 
		{
		$inb_15min_array=array();
		$stmt="select uniqueid, SEC_TO_TIME((TIME_TO_SEC(min(event_date)) DIV 900) * 900) as stime from ".$vicidial_outbound_ivr_log_table." where event_date >= '$query_date_BEGIN' and event_date <= '$query_date_END' and campaign_id IN($group_SQL) and menu_action='' group by uniqueid";
		$rslt=mysql_to_mysqli($stmt, $link);
		while ($row=mysqli_fetch_row($rslt)) 
			{
			$time_index=substr($row[1], 0, -2);
			$time_index=preg_replace('/[^0-9]/', '', $time_index);
			$inb_15min_array["$time_index"]++;
			}
		}
			if ($DB) {$MAIN.="$stmt\n";}

	$hi_hour_count=0;
	$last_full_record=0;
	$i=0;
	$h=0;
	$total_calls=0;
	$hour_count=array();
	while ($i <= 96)
		{
		$time_index=substr("0$h", -2)."00";
		$hour_count[$i]=$inb_15min_array["$time_index"]+0;
		if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
		if ($hour_count[$i] > 0) {$last_full_record = $i;}
		$i++;


		$time_index=substr("0$h", -2)."15";
		$hour_count[$i]=$inb_15min_array["$time_index"]+0;
		if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
		if ($hour_count[$i] > 0) {$last_full_record = $i;}
		$i++;

		$time_index=substr("0$h", -2)."30";
		$hour_count[$i]=$inb_15min_array["$time_index"]+0;
		if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
		if ($hour_count[$i] > 0) {$last_full_record = $i;}
		$i++;

		$time_index=substr("0$h", -2)."45";
		$hour_count[$i]=$inb_15min_array["$time_index"]+0;
		if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
		if ($hour_count[$i] > 0) {$last_full_record = $i;}
		$i++;
		$h++;
		}

	
	$hour_multiplier = MathZDC(100, $hi_hour_count);

	$MAIN.="<!-- HICOUNT: $hi_hour_count|$hour_multiplier -->\n";
	$MAIN.=_QXZ("GRAPH IN 15 MINUTE INCREMENTS OF TOTAL CALLS TAKEN INTO THIS IVR")."\n";
	$CSV_text.="\n\""._QXZ("GRAPH IN 15 MINUTE INCREMENTS OF TOTAL CALLS TAKEN INTO THIS IVR")."\"\n";

	$k=1;
	$Mk=0;
	$call_scale = '0';
	while ($k <= 102) 
		{
		if ($Mk >= 5) 
			{
			$Mk=0;
			$scale_num=MathZDC($k, $hour_multiplier);
			$scale_num = round($scale_num, 0);
			$LENscale_num = (strlen($scale_num));
			$k = ($k + $LENscale_num);
			$call_scale .= "$scale_num";
			}
		else
			{
			$call_scale .= " ";
			$k++;   $Mk++;
			}
		}


	$MAIN.="+------+-------------------------------------------------------------------------------------------------------+-------+\n";
	#$MAIN.="| HOUR | GRAPH IN 15 MINUTE INCREMENTS OF TOTAL INCOMING CALLS FOR THIS GROUP                                  | TOTAL |\n";
	$MAIN.="| "._QXZ("HOUR",4)." |$call_scale| "._QXZ("TOTAL",5)." |\n";
	$MAIN.="+------+-------------------------------------------------------------------------------------------------------+-------+\n";
	$CSV_text.="\""._QXZ("HOUR")."\",\""._QXZ("TOTAL")."\"\n";

	$ZZ = '00';
	$i=0;
	$h=4;
	$hour= -1;
	$no_lines_yet=1;

	while ($i <= 96)
		{
		$char_counter=0;
		$time = '      ';
		if ($h >= 4) 
			{
			$hour++;
			$h=0;
			if ($hour < 10) {$hour = "0$hour";}
			$time = "+$hour$ZZ+";
			}
		if ($h == 1) {$time = "   15 ";}
		if ($h == 2) {$time = "   30 ";}
		if ($h == 3) {$time = "   45 ";}
		$Ghour_count = $hour_count[$i];
		if ($Ghour_count < 1) 
			{
			if ( ($no_lines_yet) or ($i > $last_full_record) )
				{
				$do_nothing=1;
				}
			else
				{
				$total_calls+=$hour_count[$i];
				$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);
				$MAIN.="|$time|";
				$k=0;   while ($k <= 102) {$MAIN.=" ";   $k++;}
				$MAIN.="| $hour_count[$i] |\n";
				$CSV_text.="\"\",\"$time\",\"0\"\n";
				}
			}
		else
			{
			$no_lines_yet=0;
			$Xhour_count = ($Ghour_count * $hour_multiplier);
			$Yhour_count = (99 - $Xhour_count);
			$total_calls+=$hour_count[$i];

			$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);

			$MAIN.="|$time|<SPAN class=\"green\">";
			$k=0;   while ($k <= $Xhour_count) {$MAIN.="*";   $k++;   $char_counter++;}
			$MAIN.="*X</SPAN>";   $char_counter++;
			$k=0;   while ($k <= $Yhour_count) {$MAIN.=" ";   $k++;   $char_counter++;}
				while ($char_counter <= 101) {$MAIN.=" ";   $char_counter++;}
			$MAIN.="| $hour_count[$i] |\n";
			$CSV_text.="\"\",\"$time\",\"$hour_count[$i]\"\n";

			}
		
		
		$i++;
		$h++;
		}


	$MAIN.="+------+-------------------------------------------------------------------------------------------------------+-------+\n";
	$MAIN.="|                                                                                                              + ".sprintf("%-5s", $total_calls)." +\n";
	$MAIN.="+------+-------------------------------------------------------------------------------------------------------+-------+\n\n";


	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
	$MAIN.="</PRE>\n";
	$MAIN.="</TD></TR></TABLE>\n";

	if ($report_display_type=="HTML") 
		{
		$MAIN.=$JS_text;
		}

	$MAIN.="</BODY></HTML>\n";
	}

	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_IVRstats_$US$FILE_TIME.csv";
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
	} else {

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



?>
