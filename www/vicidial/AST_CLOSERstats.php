<?php 
# AST_CLOSERstats.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES:
# 60619-1714 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 60905-1326 - Added queue time stats
# 71008-1436 - Added shift to be defined in dbconnect_mysqli.php
# 71025-0021 - Added status breakdown
# 71218-1155 - Added end_date for multi-day reports
# 80430-1920 - Added Customer hangup cause stats
# 80709-0331 - Added time stats to call statuses
# 80722-2149 - Added Status Category stats
# 81015-0705 - Added IVR calls count
# 81024-0037 - Added multi-select inbound-groups
# 81105-2118 - Added Answered calls 15-minute breakdown
# 81109-2340 - Added custom indicators section
# 90116-1040 - Rewrite of the 15-minute sections to speed it up and allow multi-day calculations
# 90310-2037 - Admin header
# 90508-0644 - Changed to PHP long tags
# 90524-2231 - Changed to use functions.php for seconds to HH:MM:SS conversion
# 90801-0921 - Added in-group name to pulldown
# 91214-0955 - Added INITIAL QUEUE POSITION BREAKDOWN
# 100206-1454 - Fixed TMR(service level) calculation
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100709-1809 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100913-1634 - Added DID option to select by DIDs instead of In-groups
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110703-1759 - Added download option
# 111103-0632 - Added MAXCAL as a drop status
# 111103-2003 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 120730-0724 - Small fix for HTML output
# 130124-1719 - Added email report support
# 130414-1429 - Added report logging
# 130610-1023 - Finalized changing of all ereg instances to preg
# 130621-0805 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130704-0936 - Fixed issue #675
# 130901-0816 - Changed to mysqli PHP functions
# 140108-0746 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0009 - Finalized adding QXZ translation to all admin files
# 141128-0858 - Code cleanup for QXZ functions
# 141230-0942 - Added code for on-the-fly language translations display
# 150516-1259 - Fixed Javascript element problem, Issue #857
# 150522-1304 - Fixed issue with missing calls from user stats section
# 150928-1234 - Separated User Group permissions for this report by in-group and by DID
# 151124-1236 - Changed bottom chart to pull all time segments
# 151125-1633 - Added search archive option
# 160227-1129 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 160819-0054 - Fixed chart bugs
# 170227-1715 - Fix for default HTML report format, issue #997
# 170324-0740 - Fix for daylight savings time issue
# 170409-1559 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180323-2306 - Fix for user time calculation, subtracted queue_seconds
# 180712-1508 - Fix for rare allowed reports issue
# 190508-1900 - Streamlined DID check to optimize page load
# 190930-1345 - Fixed PHP7 array issue
# 200924-0917 - Added two new drop calculations
# 210525-1715 - Fixed help display, modification for more call details
# 210821-1521 - Added AHT to CUSTOM INDICATOR section
# 210923-2248 - Added OCR, SL-1 & SL-2 stats to CUSTOM INDICATOR section
# 211022-0735 - Added IR_SLA_all_statuses options.php setting
# 212207-2217 - Added IQNANQ to drop SQL calculation queries
# 220303-0850 - Added allow_web_debug system setting
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

# Inbound reports, use all statuses for SLA calculation
$IR_SLA_all_statuses=0;
# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require_once('options.php');
	}

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
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DID"]))				{$DID=$_GET["DID"];}
	elseif (isset($_POST["DID"]))		{$DID=$_POST["DID"];}
if (isset($_GET["EMAIL"]))				{$EMAIL=$_GET["EMAIL"];}
	elseif (isset($_POST["EMAIL"]))		{$EMAIL=$_POST["EMAIL"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$MT[0]='0';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Inbound Report';
$db_source = 'M';
if ($DID=='Y')
	{$report_name = 'Inbound Report by DID';}

# $test_table_name="vicidial_closer_log";

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
if ($SSallow_web_debug < 1) {$DB=0;}
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $end_date);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);
$DID = preg_replace('/[^-_0-9a-zA-Z]/', '', $DID);
$EMAIL = preg_replace('/[^-_0-9a-zA-Z]/', '', $EMAIL);

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

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_did_log", "vicidial_closer_log", "live_inbound_log", "vicidial_agent_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_did_log_table=use_archive_table("vicidial_did_log");
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	$live_inbound_log_table=use_archive_table("live_inbound_log");
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
	$vicidial_did_log_table="vicidial_did_log";
	$vicidial_closer_log_table="vicidial_closer_log";
	$live_inbound_log_table="live_inbound_log";
	$vicidial_agent_log_table="vicidial_agent_log";
	}
#############

$stmt = "SELECT local_gmt FROM servers where active='Y' limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$gmt_conf_ct = mysqli_num_rows($rslt);
$dst = date("I");
if ($gmt_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$local_gmt =		$row[0];
	$epoch_offset =		(($local_gmt + $dst) * 3600);
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
$LOGallowed_reports =			"$row[1],";
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name,/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
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


$stmt="select group_id,group_name,8 from vicidial_inbound_groups where group_handling='PHONE' $LOGadmin_viewable_groupsSQL order by group_id;";
if ($DID=='Y')
	{
	$stmt="select did_pattern,did_description,did_id from vicidial_inbound_dids $whereLOGadmin_viewable_groupsSQL order by did_pattern;";
	}
if ($EMAIL=='Y')
	{
	$stmt="select email_account_id,email_account_name,email_account_id from vicidial_email_accounts $whereLOGadmin_viewable_groupsSQL order by email_account_id;";
	$stmt="select group_id,group_name,8 from vicidial_inbound_groups where group_handling='EMAIL' $LOGadmin_viewable_groupsSQL order by group_id;";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
$LISTgroups=array();
$LISTgroup_names=array();
$LISTgroup_ids=array();
$LISTgroups[$i]='---NONE---';
$LISTgroup_names[$i]=_QXZ("None selected");
$i++;
$groups_to_print++;
$groups_string='|';
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTgroups[$i] =		$row[0];
	$LISTgroup_names[$i] =	$row[1];
	$LISTgroup_ids[$i] =	$row[2];
	$groups_string .= "$LISTgroups[$i]|";
	$i++;
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $group[$i]);
	if ( (strlen($group[$i]) > 0) and (preg_match("/\|$group[$i]\|/",$groups_string)) )
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}
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

$AMP='&';
$QM='?';
$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $shift, $DID, $EMAIL, $file_download, $report_display_type|', url='".$LOGfull_url."?DB=".$DB."&DID=".$DID."&EMAIL=".$EMAIL."&query_date=".$query_date."&end_date=".$end_date."&shift=".$shift."&report_display_type=".$report_display_type."$groupQS', webserver='$webserver_id';";
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


$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"verticalbargraph.css\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"wz_jsgraphics.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"line.js\"></script>\n";
require("chart_button.php");
$HEADER.="<script src='chart/Chart.js'></script>\n"; 
$HEADER.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$JS_text="<script language=\"JavaScript\">\n";

$short_header=1;

require("screen_colors.php");

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#CLOSERstats$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

if ($DB > 0)
	{
	$MAIN.="<BR>\n";
	$MAIN.="$group_ct|$group_string|$group_SQL\n";
	$MAIN.="<BR>\n";
	$MAIN.="$shift|$query_date|$end_date\n";
	$MAIN.="<BR>\n";
	}

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=POST name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP>\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DID VALUE=\"$DID\">\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=EMAIL VALUE=\"$EMAIL\">\n";
$MAIN.=_QXZ("Date Range").":<BR>\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="function openNewWindow(url)\n";
$MAIN.="  {\n";
$MAIN.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$MAIN.="  }\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

$MAIN.=" "._QXZ("to")." <INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'end_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";


$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
if ($EMAIL=='Y')
	{$MAIN.=_QXZ("Email Accts").": \n";}
else if ($DID=='Y')
	{$MAIN.=_QXZ("Inbound DIDs").": \n";}
else
	{$MAIN.=_QXZ("Inbound Groups").": \n";}
$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
$o=0;
while ($groups_to_print > $o)
	{
	if (preg_match("/\|$LISTgroups[$o]\|/",$group_string)) 
		{$MAIN.="<option selected value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroup_names[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroup_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
if ($DID!='Y')
	{
	$MAIN.="<a href=\"./admin.php?ADD=3111&group_id=$group[0]\">"._QXZ("MODIFY")."</a> | ";
	$MAIN.="<a href=\"./AST_IVRstats.php?query_date=$query_date&end_date=$end_date&shift=$shift$groupQS\">"._QXZ("IVR REPORT")."</a> | \n";
	}
$MAIN.="<a href=\"./AST_CLOSERstats_v2.php?group[]=$group[0]\">"._QXZ("v2")."</a> | ";
$MAIN.="<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> | ";
$MAIN.="</FONT>\n";

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
$MAIN.="</SELECT>\n";
$MAIN.="<BR>"._QXZ("Display as").":&nbsp; ";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR>";
if ($archives_available=="Y") 
	{
	$MAIN.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR><BR>\n";
	}

$MAIN.=" &nbsp; <INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="</TD></TR></TABLE>\n";
$MAIN.="</FORM>\n\n";
$MAIN.="<PRE><FONT SIZE=2>\n\n";


if ($groups_to_print < 1)
	{
	$MAIN.="\n\n";
	if ($EMAIL=='Y')
		{$MAIN.=_QXZ("PLEASE SELECT AN EMAIL ACCOUNT AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";}
	if ($DID=='Y')
		{$MAIN.=_QXZ("PLEASE SELECT A DID AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";}
	else
		{$MAIN.=_QXZ("PLEASE SELECT AN IN-GROUP AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";}
	}

else
{
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

# Calculate first record in interval and last
$time_BEGIN_array=explode(":", $time_BEGIN);
$first_shift_record=(4*$time_BEGIN_array[0])+(ceil($time_BEGIN_array[1]/15));
$time_END_array=explode(":", $time_END);
$last_shift_record=(4*$time_END_array[0])+(ceil($time_END_array[1]/15));

if ($EMAIL=='Y') 
	{
	$MAIN.=_QXZ("Inbound Email Stats").": $group_string          $NOW_TIME        <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
	$CSV_text1.="\""._QXZ("Inbound Call Stats").":\",\"$group_string\",\"$NOW_TIME\"\n";
	}
else
	{
	$MAIN.=_QXZ("Inbound Call Stats").": $group_string          $NOW_TIME        <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
	$CSV_text1.="\""._QXZ("Inbound Call Stats").":\",\"$group_string\",\"$NOW_TIME\"\n";
	}


$did_id=array();
$did_calls=array();
if ($DID=='Y')
	{
	$stmt="select did_id from vicidial_inbound_dids where did_pattern IN($group_SQL) $LOGadmin_viewable_groupsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$dids_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $dids_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$did_id[$i] = $row[0];
		$did_SQL .= "'$row[0]',";
		$i++;
		}
	$did_SQL = preg_replace('/,$/i', '',$did_SQL);
	if (strlen($did_SQL)<3) {$did_SQL="''";}

	if ($dids_to_print>0) 
		{
		$stmt="select uniqueid, did_route from ".$vicidial_did_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and did_id IN($did_SQL);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$unids_to_print = mysqli_num_rows($rslt);
		$i=0;
		while ($i < $unids_to_print)
			{
			$row=mysqli_fetch_row($rslt);
			$unid_SQL .= "'$row[0]',";
			$i++;
			$did_calls["$row[0]"]="$row[1]";
			}
		}

	$unid_SQL = preg_replace('/,$/i', '',$unid_SQL);
	if (strlen($unid_SQL)<3) {$unid_SQL="''";}

	if ($DB > 0)
		{$MAIN.="|$did_SQL|$unid_SQL|\n";}

	}

$graph_data_ary=array();
$CALLStoDID=count($did_calls);

$TOTALsec=0;

if ($group_ct > 1)
	{
	$ASCII_text.="\n";
	$ASCII_text.="---------- "._QXZ("MULTI-GROUP BREAKDOWN").":\n";

	$CSV_text1.="\n\""._QXZ("MULTI-GROUP BREAKDOWN").":\"\n";

	if ($EMAIL=='Y')
		{
		$ASCII_text.="+----------------------+---------+---------+---------+---------+\n";
		$ASCII_text.="| "._QXZ("EMAIL",20)." | "._QXZ("EMAILS",7)." | "._QXZ("DROPS",7)." | "._QXZ("DROP",5)." % | "._QXZ("IVR",7)." |\n";
		$ASCII_text.="+----------------------+---------+---------+---------+---------+\n";
		$CSV_text1.="\""._QXZ("EMAIL")."\",\""._QXZ("CALLS")."\",\""._QXZ("DROPS")."\",\""._QXZ("DROP")." %\",\""._QXZ("IVR")."\"\n";
		}
	else if ($DID=='Y')
		{
		$ASCII_text.="+----------------------+---------+---------+---------+---------+\n";
		$ASCII_text.="| "._QXZ("DID",20)." | "._QXZ("CALLS",7)." | "._QXZ("DROPS",7)." | "._QXZ("DROP",5)." % | "._QXZ("IVR",7)." |\n";
		$ASCII_text.="+----------------------+---------+---------+---------+---------+\n";
		$CSV_text1.="\""._QXZ("DID")."\",\""._QXZ("CALLS")."\",\""._QXZ("DROPS")."\",\""._QXZ("DROP")." %\",\""._QXZ("IVR")."\"\n";
		}
	else
		{
		$ASCII_text.="+----------------------+---------+---------+---------+---------+\n";
		$ASCII_text.="| "._QXZ("IN-GROUP",20)." | "._QXZ("CALLS",7)." | "._QXZ("DROPS",7)." | "._QXZ("DROP",5)." % | "._QXZ("IVR",7)." |\n";
		$ASCII_text.="+----------------------+---------+---------+---------+---------+\n";
		$CSV_text1.="\""._QXZ("IN-GROUP")."\",\""._QXZ("CALLS")."\",\""._QXZ("DROPS")."\",\""._QXZ("DROP")." %\",\""._QXZ("IVR")."\"\n";
		}

	$i=0;
	while($i < $group_ct)
		{
		if ($DID=='Y') 
			{
			$did_id[$i]='0';
			$DIDunid_SQL='';
			$stmt="select did_id from vicidial_inbound_dids where did_pattern='$group[$i]';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {$ASCII_text.="$stmt\n";}
			$Sdids_to_print = mysqli_num_rows($rslt);
			if ($Sdids_to_print > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$did_id[$i] = $row[0];

				$stmt="select uniqueid from ".$vicidial_did_log_table." where did_id='$did_id[$i]';";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {$ASCII_text.="$stmt\n";}
				$DIDunids_to_print = mysqli_num_rows($rslt);
				$k=0;
				while ($k < $DIDunids_to_print)
					{
					$row=mysqli_fetch_row($rslt);
					$DIDunid_SQL .= "'$row[0]',";
					$k++;
					}
				}
			$DIDunid_SQL = preg_replace('/,$/i', '',$DIDunid_SQL);
			if (strlen($DIDunid_SQL)<3) {$DIDunid_SQL="''";}
			}

		$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id='$group[$i]';";
		if ($DID=='Y')
			{
			$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($DIDunid_SQL);";
			}
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$ASCII_text.="$stmt\n";}
		$row=mysqli_fetch_row($rslt);

		$stmt="select uniqueid, length_in_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id='$group[$i]';";
		if ($DID=='Y')
			{
			$stmt="select uniqueid, length_in_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($DIDunid_SQL);";
			}
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$ASCII_text.="$stmt\n";}
		$TOTALcalls=mysqli_num_rows($rslt);
		while ($row=mysqli_fetch_row($rslt))
			{
			unset($did_calls["$row[0]"]);
			$TOTALsec+=$row[1];
			}

		$stmt="select distinct uniqueid from ".$live_inbound_log_table." where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a='$group[$i]' and comment_b='START';";
		if ($DID=='Y')
			{
			$stmt="select distinct uniqueid from ".$live_inbound_log_table." where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and uniqueid IN($DIDunid_SQL);";
			}
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$ASCII_text.="6 $stmt\n";}
		$TOTALivrs=mysqli_num_rows($rslt);
		while ($rowx=mysqli_fetch_row($rslt))
			{
			unset($did_calls["$rowx[0]"]);
			}

		$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id='$group[$i]' and status IN('DROP','XDROP') and (length_in_sec <= 49999 or length_in_sec is null);";
		if ($DID=='Y')
			{
			$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status IN('DROP','XDROP') and (length_in_sec <= 49999 or length_in_sec is null) and uniqueid IN($DIDunid_SQL);";
			}
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$ASCII_text.="$stmt\n";}
		$rowy=mysqli_fetch_row($rslt);
		if ($TOTALcalls>$max_value) {$max_value=$TOTALcalls;}

		$groupDISPLAY =	sprintf("%20s", $group[$i]);
 		$gTOTALcalls =	sprintf("%7s", $TOTALcalls);
		$gIVRcalls =	sprintf("%7s", $TOTALivrs);
		$gDROPcalls =	sprintf("%7s", $rowy[0]);
		$gDROPpercent = (MathZDC($gDROPcalls, $gTOTALcalls) * 100);
		$gDROPpercent = round($gDROPpercent, 2);
		$gDROPpercent =	sprintf("%6s", $gDROPpercent);

		$ASCII_text.="| $groupDISPLAY | $gTOTALcalls | $gDROPcalls | $gDROPpercent% | $gIVRcalls |\n";
		$CSV_text1.="\"$groupDISPLAY\",\"$gTOTALcalls\",\"$gDROPcalls\",\"$gDROPpercent%\",\"$gIVRcalls\"\n";
		$graph_data_ary[$i][0]=$groupDISPLAY;
		$graph_data_ary[$i][1]=$gTOTALcalls;
		$graph_data_ary[$i][2]=$gDROPcalls;
		$graph_data_ary[$i][3]=$gDROPpercent;
		$graph_data_ary[$i][4]=$gIVRcalls;

		$i++;
		}

	$ASCII_text.="+----------------------+---------+---------+---------+---------+\n";

	}

	if ($report_display_type=="HTML") {
		$max_value=1;
		$graph_height=240;
		$scale = 1;
		$w=0;
		for ($i=0; $i<count($graph_data_ary); $i++) {
			if ($graph_data_ary[$i][1]>$max_value) {$max_value=$graph_data_ary[$i][1];}
			if ($graph_data_ary[$i][1]>0) {$w++;}
		}
		$scale = MathZDC($graph_height, $max_value);


		$GRAPH_text.="<table cellspacing='0' cellpadding='0'><caption align='top'>"._QXZ("MULTI-GROUP BREAKDOWN")."</caption><tr height='25' valign='top'><th class='thgraph' scope='col'>"._QXZ("GROUP")."</th><th class='thgraph' scope='col'>"._QXZ("IVRS")." <img src='./images/bar_green.png' width='10' height='10'> / "._QXZ("DROPS")." <img src='./images/bar_blue.png' width='10' height='10'> / "._QXZ("CALLS")." <img src='./images/bar.png' width='10' height='10'></th></tr>";
		for ($d=0; $d<count($graph_data_ary); $d++) {
			if (strlen(trim($graph_data_ary[$d][0]))>0) {
				$graph_data_ary[$d][0]=preg_replace('/\s/', "", $graph_data_ary[$d][0]); 
				$GRAPH_text.="  <tr><td class='chart_td' width='50'>".$graph_data_ary[$d][0]."<BR>".$graph_data_ary[$d][3]."% drops<BR>&nbsp;</td><td nowrap class='chart_td value' width='600' valign='top'>\n";
				if ($graph_data_ary[$d][1]>0) {
					$GRAPH_text.="<ul class='overlap_barGraph'><li class=\"p1\" style=\"height: 12px; left: 0px; width: ".round(MathZDC(600*$graph_data_ary[$d][1], $max_value))."px\"><font style='background-color: #900'>".$graph_data_ary[$d][1]."</font></li>";
					if ($graph_data_ary[$d][2]>0) {
						$GRAPH_text.="<li class=\"p2\" style=\"height: 12px; left: 0px; width: ".round(MathZDC(600*$graph_data_ary[$d][2], $max_value))."px\"><font style='background-color: #009'>".$graph_data_ary[$d][2]."</font></li>";
					}
					if ($graph_data_ary[$d][4]>0) {
						$GRAPH_text.="<li class=\"p3\" style=\"height: 12px; left: 0px; width: ".round(MathZDC(600*$graph_data_ary[$d][4], $max_value))."px\"><font style='background-color: #090'>".$graph_data_ary[$d][4]."</font></li>";
					}
					$GRAPH_text.="</ul>\n";
				} else {
					$GRAPH_text.="0";
				}
				$GRAPH_text.="</td></tr>\n";
			}
		}
		$GRAPH_text.="</table><BR/><BR/>";


		$MAIN.=$GRAPH_text;
		}
	else 
		{
		$MAIN.=$ASCII_text;
		}

$MAIN.="\n";
$MAIN.=_QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
$MAIN.="---------- "._QXZ("TOTALS")."\n";

$CSV_text1.="\n\""._QXZ("Time range").":\",\"$query_date_BEGIN\",\""._QXZ("to")."\",\"$query_date_END\"\n\n";

$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL);";
if ($DID=='Y')
	{
	$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL);";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$stmt="select count(*),sum(queue_seconds) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
if ($DID=='Y')
	{
	$stmt="select count(*),sum(queue_seconds) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') and uniqueid IN($unid_SQL);";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$rowy=mysqli_fetch_row($rslt);

$stmt="select count(*) from ".$live_inbound_log_table." where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a IN($group_SQL) and comment_b='START';";
if ($DID=='Y')
	{
	$stmt="select count(*) from ".$live_inbound_log_table." where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and uniqueid IN($unid_SQL);";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$rowx=mysqli_fetch_row($rslt);

$TOTALcalls =	sprintf("%10s", $row[0]);
$IVRcalls =	sprintf("%10s", $rowx[0]);

$UNSENTcalls=count($did_calls);
$ROUTEcounts=array_count_values($did_calls);

$average_call_seconds = MathZDC($TOTALsec, $row[0]);
$average_call_seconds = round($average_call_seconds, 0);
$average_call_seconds =	sprintf("%10s", $average_call_seconds);

$ANSWEREDcalls  =	sprintf("%10s", $rowy[0]);

$ANSWEREDpercent = (MathZDC($ANSWEREDcalls, $TOTALcalls) * 100);
$ANSWEREDpercent = round($ANSWEREDpercent, 0);

$average_answer_seconds = MathZDC($rowy[1], $rowy[0]);
$average_answer_seconds = round($average_answer_seconds, 2);
$average_answer_seconds =	sprintf("%10s", $average_answer_seconds);

if ($EMAIL=='Y')
	{
	$MAIN.=_QXZ("Total Emails taken in to this In-Group:",47)."$TOTALcalls\n";
	$MAIN.=_QXZ("Average Email Length for all Emails:",47)."$average_call_seconds "._QXZ("seconds")."\n";
	$MAIN.=_QXZ("Answered Emails:",47)."$ANSWEREDcalls  $ANSWEREDpercent%\n";
	$MAIN.=_QXZ("Average queue time for Answered Emails:",47)."$average_answer_seconds "._QXZ("seconds")."\n";
	$MAIN.=_QXZ("Emails taken into the IVR for this In-Group:",47)."$IVRcalls\n";

	$CSV_text1.="\""._QXZ("Total Emails taken in to this In-Group").":\",\"$TOTALcalls\"\n";
	$CSV_text1.="\""._QXZ("Average Email Length for all Emails").":\",\"$average_call_seconds "._QXZ("seconds")."\"\n";
	$CSV_text1.="\""._QXZ("Answered Emails").":\",\"$ANSWEREDcalls\",\"$ANSWEREDpercent%\"\n";
	$CSV_text1.="\""._QXZ("Average queue time for Answered Emails").":\",\"$average_answer_seconds "._QXZ("seconds")."\"\n";
	$CSV_text1.="\""._QXZ("Emails taken into the IVR for this In-Group").":\",\"$IVRcalls\"\n";
	}
else
	{
	if ($DID=='Y')
		{
		$CALLStoDID=sprintf("%10s", $CALLStoDID);
		$UNSENTcalls=sprintf("%10s", $UNSENTcalls);
		$MAIN.=_QXZ("Total calls to DIDs:",47)."$CALLStoDID\n";
		$MAIN.=_QXZ("Calls transferred or lost before routing:",47)."$UNSENTcalls\n";
		$CSV_text1.="\""._QXZ("Total calls to DIDs").":\",\"$CALLStoDID\"\n";
		$CSV_text1.="\""._QXZ("Calls transferred or lost before routing").":\",\"$CALLStoDID\"\n";

		if ($UNSENTcalls>0)
			{
			foreach ($ROUTEcounts as $route => $calls_to_route)
				{
				$calls_to_route=sprintf("%10s", $calls_to_route);
				$MAIN.=" - "._QXZ("Before reaching $route:",44)."$calls_to_route\n";
				$CSV_text1.="\" - "._QXZ("Calls transferred or lost before reaching $route").":\",\"$calls_to_route\"\n";
				}		
			$call_logUIDSQL="";
			foreach($did_calls as $uniqueid => $route)
				{
				$call_logUIDSQL.="'$uniqueid',";
				}
			$call_logUIDSQL=preg_replace('/,$/', '', $call_logUIDSQL);
			$call_log_stmt="select sum(length_in_sec), avg(length_in_sec) from call_log where uniqueid in ($call_logUIDSQL)";
			$call_log_rslt=mysql_to_mysqli($call_log_stmt, $link);
			if ($DB) {$MAIN.="8b $call_log_stmt\n";}
			$call_log_row=mysqli_fetch_row($call_log_rslt);
			$avg_lost_secs=round($call_log_row[1], 2);
			$avg_lost_secs=sprintf("%10s", $avg_lost_secs);
			$MAIN.=_QXZ("Average length of lost calls:",47)."$avg_lost_secs "._QXZ("seconds")."\n";
			$CSV_text1.="\""._QXZ("Average length of lost calls").":\",\"$avg_lost_secs "._QXZ("seconds")."\"\n";
			}
		$MAIN.="\n";
		$CSV_text1.="\n";
		}

	$MAIN.=_QXZ("Total calls routed:",47)."$TOTALcalls\n";
	$MAIN.=_QXZ("Average Call Length for routed Calls:",47)."$average_call_seconds "._QXZ("seconds")."\n";
	$MAIN.=_QXZ("Answered Calls:",47)."$ANSWEREDcalls  $ANSWEREDpercent%\n";
	$MAIN.=_QXZ("Average queue time for Answered Calls:",47)."$average_answer_seconds "._QXZ("seconds")."\n";
	$MAIN.=_QXZ("Calls taken into the IVR:",47)."$IVRcalls\n";

	$CSV_text1.="\""._QXZ("Total calls routed").":\",\"$TOTALcalls\"\n";
	$CSV_text1.="\""._QXZ("Average Call Length for routed Calls").":\",\"$average_call_seconds "._QXZ("seconds")."\"\n";
	$CSV_text1.="\""._QXZ("Answered Calls").":\",\"$ANSWEREDcalls\",\"$ANSWEREDpercent%\"\n";
	$CSV_text1.="\""._QXZ("Average queue time for Answered Calls").":\",\"$average_answer_seconds "._QXZ("seconds")."\"\n";
	$CSV_text1.="\""._QXZ("Calls taken into the IVR").":\",\"$IVRcalls\"\n";
	}

$MAIN.="\n";
$MAIN.="---------- "._QXZ("DROPS")."\n";

$CSV_text1.="\n\""._QXZ("DROPS")."\"\n";

# Calculate all dropped calls
$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and status IN('DROP','XDROP') and (length_in_sec <= 49999 or length_in_sec is null);";
if ($DID=='Y')
	{
	$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status IN('DROP','XDROP') and (length_in_sec <= 49999 or length_in_sec is null) and uniqueid IN($unid_SQL);";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$DROPcalls =	sprintf("%10s", $row[0]);
$DROPpercent = (MathZDC($DROPcalls, $TOTALcalls) * 100);
$DROPpercent = round($DROPpercent, 0);

$average_hold_seconds = MathZDC($row[1], $row[0]);
$average_hold_seconds = round($average_hold_seconds, 0);
$average_hold_seconds =	sprintf("%10s", $average_hold_seconds);

$DROP_ANSWEREDpercent = (MathZDC($DROPcalls, $ANSWEREDcalls) * 100);
$DROP_ANSWEREDpercent = round($DROP_ANSWEREDpercent, 0);


# Calculate dropped calls >= 5 seconds
$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and status IN('DROP','XDROP') and ( (length_in_sec <= 49999 and length_in_sec >= 5) or length_in_sec is null);";
if ($DID=='Y')
	{
	$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status IN('DROP','XDROP') and ((length_in_sec <= 49999 and length_in_sec >= 5) or length_in_sec is null) and uniqueid IN($unid_SQL);";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);
$DROPcalls5 =	sprintf("%10s", $row[0]);
$DROPpercent5 = (MathZDC($DROPcalls5, $TOTALcalls) * 100);
$DROPpercent5 = round($DROPpercent5, 0);

# Calculate dropped calls >= 10 seconds
$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and status IN('DROP','XDROP') and ( (length_in_sec <= 49999 and length_in_sec >= 10) or length_in_sec is null);";
if ($DID=='Y')
	{
	$stmt="select count(*),sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status IN('DROP','XDROP') and ((length_in_sec <= 49999 and length_in_sec >= 10) or length_in_sec is null) and uniqueid IN($unid_SQL);";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);
$DROPcalls10 =	sprintf("%10s", $row[0]);
$DROPpercent10 = (MathZDC($DROPcalls10, $TOTALcalls) * 100);
$DROPpercent10 = round($DROPpercent10, 0);


if ($EMAIL=='Y')
	{
	$MAIN.=_QXZ("Total DROP Emails:",47)."$DROPcalls  $DROPpercent%               "._QXZ("drop/answered").": $DROP_ANSWEREDpercent%\n";
	$MAIN.=_QXZ("Average hold time for DROP Emails:",47)."$average_hold_seconds "._QXZ("seconds")."\n";
	$MAIN.=_QXZ("Drop rate for emails >= 5 seconds:",47)."$DROPcalls5  $DROPpercent5% \n";
	$MAIN.=_QXZ("Drop rate for emails >= 10 seconds:",47)."$DROPcalls10  $DROPpercent10% \n";

	$CSV_text1.="\""._QXZ("Total DROP Emails").":\",\"$DROPcalls\",\"$DROPpercent%\",\""._QXZ("drop/answered").":\",\"$DROP_ANSWEREDpercent%\"\n";
	$CSV_text1.="\""._QXZ("Average hold time for DROP Emails").":\",\"$average_hold_seconds "._QXZ("seconds")."\"\n";
	$CSV_text1.="\""._QXZ("Drop rate for emails >= 5 seconds").":\",\"$DROPcalls5\",\"$DROPpercent5%\"\n";
	$CSV_text1.="\""._QXZ("Drop rate for emails >= 10 seconds").":\",\"$DROPcalls10\",\"$DROPpercent10%\"\n";
	}
else
	{
	$MAIN.=_QXZ("Total DROP Calls:",47)."$DROPcalls  $DROPpercent%               "._QXZ("drop/answered").": $DROP_ANSWEREDpercent%\n";
	$MAIN.=_QXZ("Average hold time for DROP Calls:",47)."$average_hold_seconds "._QXZ("seconds")."\n";
	$MAIN.=_QXZ("Drop rate for calls >= 5 seconds:",47)."$DROPcalls5  $DROPpercent5% \n";
	$MAIN.=_QXZ("Drop rate for calls >= 10 seconds:",47)."$DROPcalls10  $DROPpercent10% \n";

	$CSV_text1.="\""._QXZ("Total DROP Calls").":\",\"$DROPcalls\",\"$DROPpercent%\",\""._QXZ("drop/answered").":\",\"$DROP_ANSWEREDpercent%\"\n";
	$CSV_text1.="\""._QXZ("Average hold time for DROP Calls").":\",\"$average_hold_seconds "._QXZ("seconds")."\"\n";
	$CSV_text1.="\""._QXZ("Drop rate for calls >= 5 seconds").":\",\"$DROPcalls5\",\"$DROPpercent5%\"\n";
	$CSV_text1.="\""._QXZ("Drop rate for calls >= 10 seconds").":\",\"$DROPcalls10\",\"$DROPpercent10%\"\n";
	}

if (strlen($group_SQL)>3)
	{
	if ($DID!='Y')
		{
		$stmt = "SELECT answer_sec_pct_rt_stat_one,answer_sec_pct_rt_stat_two from vicidial_inbound_groups where group_id IN($group_SQL) order by answer_sec_pct_rt_stat_one desc limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$Sanswer_sec_pct_rt_stat_one = $row[0];
		$Sanswer_sec_pct_rt_stat_two = $row[1];

		$stmt = "SELECT count(*) from ".$vicidial_closer_log_table." where campaign_id IN($group_SQL) and call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and queue_seconds <= $Sanswer_sec_pct_rt_stat_one and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$answer_sec_pct_rt_stat_one = $row[0];
		$all_sec_pct_rt_stat_one = $row[0];

		$stmt = "SELECT count(*) from ".$vicidial_closer_log_table." where campaign_id IN($group_SQL) and call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and queue_seconds <= $Sanswer_sec_pct_rt_stat_two and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL');";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$answer_sec_pct_rt_stat_two = $row[0];
		$all_sec_pct_rt_stat_two = $row[0];

		$SL_numerator = 'Answered';

		if ($IR_SLA_all_statuses > 0)
			{
			$SL_numerator = 'All';

			$stmt = "SELECT count(*) from ".$vicidial_closer_log_table." where campaign_id IN($group_SQL) and call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' $calldate_call_time_clause and queue_seconds <= $Sanswer_sec_pct_rt_stat_one;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {$MAIN.="$stmt\n";}
			$row=mysqli_fetch_row($rslt);
			$all_sec_pct_rt_stat_one = $row[0];

			$stmt = "SELECT count(*) from ".$vicidial_closer_log_table." where campaign_id IN($group_SQL) and call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' $calldate_call_time_clause and queue_seconds <= $Sanswer_sec_pct_rt_stat_two;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {$MAIN.="$stmt\n";}
			$row=mysqli_fetch_row($rslt);
			$all_sec_pct_rt_stat_two = $row[0];
			}

		$PCTanswer_sec_pct_rt_stat_one = (MathZDC($answer_sec_pct_rt_stat_one, $ANSWEREDcalls) * 100);
		$PCTanswer_sec_pct_rt_stat_one = round($PCTanswer_sec_pct_rt_stat_one, 0);
		#$PCTanswer_sec_pct_rt_stat_one = sprintf("%10s", $PCTanswer_sec_pct_rt_stat_one);
		$PCTanswer_sec_pct_rt_stat_two = (MathZDC($answer_sec_pct_rt_stat_two, $ANSWEREDcalls) * 100);
		$PCTanswer_sec_pct_rt_stat_two = round($PCTanswer_sec_pct_rt_stat_two, 0);
		#$PCTanswer_sec_pct_rt_stat_two = sprintf("%10s", $PCTanswer_sec_pct_rt_stat_two);

		$PCTallcall_sec_pct_rt_stat_one = (MathZDC($all_sec_pct_rt_stat_one, $TOTALcalls) * 100);
		$PCTallcall_sec_pct_rt_stat_one = round($PCTallcall_sec_pct_rt_stat_one, 0);
		$PCTallcall_sec_pct_rt_stat_two = (MathZDC($all_sec_pct_rt_stat_two, $TOTALcalls) * 100);
		$PCTallcall_sec_pct_rt_stat_two = round($PCTallcall_sec_pct_rt_stat_two, 0);

		$stmt="SELECT uniqueid from ".$vicidial_closer_log_table." where campaign_id IN($group_SQL) and call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') and uniqueid!='';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$uniqueids_to_print = mysqli_num_rows($rslt);
		if ($DB) {$MAIN.="$uniqueids_to_print|$stmt\n";}
		$uip=0;   $uniqueidSQL='';
		while ($uip < $uniqueids_to_print)
			{
			$row=mysqli_fetch_row($rslt);
			if ($uip > 0) {$uniqueidSQL .= ",";}
			$uniqueidSQL .= "'$row[0]'";
			$uip++;
			}
		if (strlen($uniqueidSQL) < 2) {$uniqueidSQL="'X'";}

		$stmt = "SELECT count(*),sum(talk_sec + dispo_sec),sum(talk_sec + dispo_sec + wait_sec) from ".$vicidial_agent_log_table." where uniqueid IN($uniqueidSQL) and event_time >= '$query_date_BEGIN' and event_time <= '$query_date_END' and talk_sec < 65000 and dispo_sec < 65000 and wait_sec < 65000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$AHTcalls = $row[0];
		$AHTtime = $row[1];
		$AHTwait = $row[2];

		$AHTaverage = (MathZDC($AHTtime, $AHTcalls));
		$TALK_DISPO_HOLDseconds = round($AHTaverage, 0);

		$OCRaverage = (MathZDC($AHTtime, $AHTwait));
		$OCRate = round($OCRaverage, 2);
		}
	}

if ($EMAIL=='Y')
	{
	$MAIN.="\n";
	$MAIN.="---------- "._QXZ("CUSTOM INDICATORS")."\n";
	$MAIN.="GDE  "._QXZ("(Answered/Total emails taken in to this In-Group):",50)."  $ANSWEREDpercent%\n";
	$MAIN.="ACR  "._QXZ("(Dropped/Answered):",50)."  $DROP_ANSWEREDpercent%\n";
	$MAIN.="AHT  "._QXZ("(Agent-Answered emails / Handle Time sec):",50)."  $TALK_DISPO_HOLDseconds sec\n";
	$MAIN.="OCR  "._QXZ("Occupancy Rate = (Handle Time / Handle + Wait sec)",50).": $OCRate\n";
	$MAIN.="      *"._QXZ("Handle Time = (Talk+Hold+Dead+Dispo sec)",50)."\n";

	$CSV_text1.="\n\""._QXZ("CUSTOM INDICATORS")."\"\n";
	$CSV_text1.="\"GDE "._QXZ("(Answered/Total emails taken in to this In-Group)").":\",\"$ANSWEREDpercent%\"\n";
	$CSV_text1.="\"ACR "._QXZ("(Dropped/Answered)").":\",\"$DROP_ANSWEREDpercent%\"\n";
	$CSV_text1.="\"AHT "._QXZ("(Agent-Answered emails / Talk+Hold+Dead+Dispo time)").":\",\"$TALK_DISPO_HOLDseconds sec\"\n";
	$CSV_text1.="\"OCR "._QXZ("Occupancy Rate = (Handle Time / Handle + Wait sec)").":\",\"$OCRate\"\n";
	}
else
	{
	$MAIN.="\n";
	$MAIN.="---------- "._QXZ("CUSTOM INDICATORS")."\n";
	$MAIN.="GDE  "._QXZ("(Answered/Total calls taken in to this In-Group):",50)."  $ANSWEREDpercent%\n";
	$MAIN.="ACR  "._QXZ("(Dropped/Answered):",50)."  $DROP_ANSWEREDpercent%\n";
	$MAIN.="AHT  "._QXZ("(Agent-Answered calls / Handle Time sec):",50)."  $TALK_DISPO_HOLDseconds sec\n";
	$MAIN.="OCR  "._QXZ("Occupancy Rate = (Handle Time / Handle + Wait sec)",50).": $OCRate\n";
	$MAIN.="      *"._QXZ("Handle Time = (Talk+Hold+Dead+Dispo sec)",50)."\n";

	$CSV_text1.="\n\""._QXZ("CUSTOM INDICATORS")."\"\n";
	$CSV_text1.="\"GDE "._QXZ("(Answered/Total calls taken in to this In-Group)").":\",\"$ANSWEREDpercent%\"\n";
	$CSV_text1.="\"ACR "._QXZ("(Dropped/Answered)").":\",\"$DROP_ANSWEREDpercent%\"\n";
	$CSV_text1.="\"AHT "._QXZ("(Agent-Answered calls / Talk+Hold+Dead+Dispo time)").":\",\"$TALK_DISPO_HOLDseconds sec\"\n";
	$CSV_text1.="\"OCR "._QXZ("Occupancy Rate = (Handle Time / Handle + Wait sec)").":\",\"$OCRate\"\n";
	}

if ($DID!='Y')
	{
	$MAIN.="TMR1 "._QXZ("(Answered within %1s seconds/Answered):",50,'',$Sanswer_sec_pct_rt_stat_one)." $PCTanswer_sec_pct_rt_stat_one%\n";
	$MAIN.="TMR2 "._QXZ("(Answered within %1s seconds/Answered):",50,'',$Sanswer_sec_pct_rt_stat_two)." $PCTanswer_sec_pct_rt_stat_two%\n";
	$MAIN.="SL-1 "._QXZ("(%2s within %1s seconds/All Calls)",50,'',$Sanswer_sec_pct_rt_stat_one,$SL_numerator).": $PCTallcall_sec_pct_rt_stat_one%\n";
	$MAIN.="SL-2 "._QXZ("(%2s within %1s seconds/All Calls)",50,'',$Sanswer_sec_pct_rt_stat_two,$SL_numerator).": $PCTallcall_sec_pct_rt_stat_two%\n";

	$CSV_text1.="\"TMR1 "._QXZ("(Answered within %1s seconds/Answered)",0,'',$Sanswer_sec_pct_rt_stat_one).":\",\"$PCTanswer_sec_pct_rt_stat_one%\"\n";
	$CSV_text1.="\"TMR2 "._QXZ("(Answered within %1s seconds/Answered)",0,'',$Sanswer_sec_pct_rt_stat_two).":\",\"$PCTanswer_sec_pct_rt_stat_two%\"\n";
	$CSV_text1.="\"SL-1 "._QXZ("(%2s within %1s seconds/All Calls)",0,'',$Sanswer_sec_pct_rt_stat_one,$SL_numerator).":\",\"$PCTallcall_sec_pct_rt_stat_one%\"\n";
	$CSV_text1.="\"SL-2 "._QXZ("(%2s within %1s seconds/All Calls)",0,'',$Sanswer_sec_pct_rt_stat_two,$SL_numerator).":\",\"$PCTallcall_sec_pct_rt_stat_two%\"\n";
	}


# GET LIST OF ALL STATUSES and create SQL from human_answered statuses
$q=0;
$stmt = "SELECT status,status_name,human_answered,category from vicidial_statuses;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statuses_to_print = mysqli_num_rows($rslt);
$p=0;
$status=array();
$status_name=array();
$human_answered=array();
$category=array();
$statname_list=array();
$statcat_list=array();
while ($p < $statuses_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$status[$q] =			$row[0];
	$status_name[$q] =		$row[1];
	$human_answered[$q] =	$row[2];
	$category[$q] =			$row[3];
	$statname_list["$status[$q]"] = "$status_name[$q]";
	$statcat_list["$status[$q]"] = "$category[$q]";
	$q++;
	$p++;
	}
$stmt = "SELECT status,status_name,human_answered,category from vicidial_campaign_statuses;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statuses_to_print = mysqli_num_rows($rslt);
$p=0;
while ($p < $statuses_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$status[$q] =			$row[0];
	$status_name[$q] =		$row[1];
	$human_answered[$q] =	$row[2];
	$category[$q] =			$row[3];
	$statname_list["$status[$q]"] = "$status_name[$q]";
	$statcat_list["$status[$q]"] = "$category[$q]";
	$q++;
	$p++;
	}

##############################
#########  CALL QUEUE STATS
$MAIN.="\n";
$MAIN.="---------- "._QXZ("QUEUE STATS")."\n";

$CSV_text1.="\n\""._QXZ("QUEUE STATS")."\"\n";

$stmt="select count(*),sum(queue_seconds) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and (queue_seconds > 0);";
if ($DID=='Y')
	{
	$stmt="select count(*),sum(queue_seconds) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and (queue_seconds > 0) and uniqueid IN($unid_SQL);";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$QUEUEcalls =	sprintf("%10s", $row[0]);
$QUEUEpercent = (MathZDC($QUEUEcalls, $TOTALcalls) * 100);
$QUEUEpercent = round($QUEUEpercent, 0);

$average_queue_seconds = MathZDC($row[1], $row[0]);
$average_queue_seconds = round($average_queue_seconds, 2);
$average_queue_seconds = sprintf("%10.2f", $average_queue_seconds);

$average_total_queue_seconds = MathZDC($row[1], $TOTALcalls);
$average_total_queue_seconds = round($average_total_queue_seconds, 2);
$average_total_queue_seconds = sprintf("%10.2f", $average_total_queue_seconds);

if ($EMAIL=='Y')
	{
	$MAIN.=_QXZ("Total Emails That entered Queue:",46)." $QUEUEcalls  $QUEUEpercent%\n";
	$MAIN.=_QXZ("Average QUEUE Length for queue emails:",46)." $average_queue_seconds "._QXZ("seconds")."\n";
	$MAIN.=_QXZ("Average QUEUE Length across all emails:",46)." $average_total_queue_seconds "._QXZ("seconds")."\n";

	$CSV_text1.="\""._QXZ("Total Emails That entered Queue").":\",\"$QUEUEcalls\",\"$QUEUEpercent%\"\n";
	$CSV_text1.="\""._QXZ("Average QUEUE Length for queue emails").":\",\"$average_queue_seconds "._QXZ("seconds")."\"\n";
	$CSV_text1.="\""._QXZ("Average QUEUE Length across all emails").":\",\"$average_total_queue_seconds "._QXZ("seconds")."\"\n";
	}
else 
	{
	$MAIN.=_QXZ("Total Calls That entered Queue:",46)." $QUEUEcalls  $QUEUEpercent%\n";
	$MAIN.=_QXZ("Average QUEUE Length for queue calls:",46)." $average_queue_seconds "._QXZ("seconds")."\n";
	$MAIN.=_QXZ("Average QUEUE Length across all calls:",46)." $average_total_queue_seconds "._QXZ("seconds")."\n";

	$CSV_text1.="\""._QXZ("Total Calls That entered Queue").":\",\"$QUEUEcalls\",\"$QUEUEpercent%\"\n";
	$CSV_text1.="\""._QXZ("Average QUEUE Length for queue calls").":\",\"$average_queue_seconds "._QXZ("seconds")."\"\n";
	$CSV_text1.="\""._QXZ("Average QUEUE Length across all calls").":\",\"$average_total_queue_seconds "._QXZ("seconds")."\"\n";
	}

if ($EMAIL=='Y') {
	$rpt_type_verbiage=_QXZ("EMAIL",6);
	$rpt_type_verbiages=_QXZ("EMAILS",6);
} else {
	$rpt_type_verbiage=_QXZ("CALL",6);
	$rpt_type_verbiages=_QXZ("CALLS",6);
}

##############################
#########  CALL HOLD TIME BREAKDOWN IN SECONDS

$TOTALcalls = 0;

$ASCII_text="\n";
$ASCII_text.="---------- $rpt_type_verbiage "._QXZ("HOLD TIME BREAKDOWN IN SECONDS",36)." <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=2&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
$ASCII_text.="+-------------------------------------------------------------------------------------------+------------+\n";
$ASCII_text.="|     0     5    10    15    20    25    30    35    40    45    50    55    60    90   +90 | "._QXZ("TOTAL", 10)." |\n";
$ASCII_text.="+-------------------------------------------------------------------------------------------+------------+\n";

$CSV_text2.="\n\"$rpt_type_verbiage "._QXZ("HOLD TIME BREAKDOWN IN SECONDS")."\"\n";
$CSV_text2.="\"\",\"0\",\"5\",\"10\",\"15\",\"20\",\"25\",\"30\",\"35\",\"40\",\"45\",\"50\",\"55\",\"60\",\"90\",\"+90\",\""._QXZ("TOTAL")."\"\n";


$stmt="select count(*),queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) group by queue_seconds;";
if ($DID=='Y')
	{
	$stmt="select count(*),queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by queue_seconds;";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$reasons_to_print = mysqli_num_rows($rslt);
$i=0;
$hd_5=0; $hd10=0; $hd15=0; $hd20=0; $hd25=0; $hd30=0; $hd35=0; $hd40=0; $hd45=0; $hd50=0; $hd55=0; $hd60=0; $hd90=0; $hd99=0;

while ($i < $reasons_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$TOTALcalls = ($TOTALcalls + $row[0]);

	if ($row[1] == 0) {$hd_0 = ($hd_0 + $row[0]);}
	if ( ($row[1] > 0) and ($row[1] <= 5) ) {$hd_5 = ($hd_5 + $row[0]);}
	if ( ($row[1] > 5) and ($row[1] <= 10) ) {$hd10 = ($hd10 + $row[0]);}
	if ( ($row[1] > 10) and ($row[1] <= 15) ) {$hd15 = ($hd15 + $row[0]);}
	if ( ($row[1] > 15) and ($row[1] <= 20) ) {$hd20 = ($hd20 + $row[0]);}
	if ( ($row[1] > 20) and ($row[1] <= 25) ) {$hd25 = ($hd25 + $row[0]);}
	if ( ($row[1] > 25) and ($row[1] <= 30) ) {$hd30 = ($hd30 + $row[0]);}
	if ( ($row[1] > 30) and ($row[1] <= 35) ) {$hd35 = ($hd35 + $row[0]);}
	if ( ($row[1] > 35) and ($row[1] <= 40) ) {$hd40 = ($hd40 + $row[0]);}
	if ( ($row[1] > 40) and ($row[1] <= 45) ) {$hd45 = ($hd45 + $row[0]);}
	if ( ($row[1] > 45) and ($row[1] <= 50) ) {$hd50 = ($hd50 + $row[0]);}
	if ( ($row[1] > 50) and ($row[1] <= 55) ) {$hd55 = ($hd55 + $row[0]);}
	if ( ($row[1] > 55) and ($row[1] <= 60) ) {$hd60 = ($hd60 + $row[0]);}
	if ( ($row[1] > 60) and ($row[1] <= 90) ) {$hd90 = ($hd90 + $row[0]);}
	if ($row[1] > 90) {$hd99 = ($hd99 + $row[0]);}
	$i++;
	}

$hd_0 =	sprintf("%5s", $hd_0);
$hd_5 =	sprintf("%5s", $hd_5);
$hd10 =	sprintf("%5s", $hd10);
$hd15 =	sprintf("%5s", $hd15);
$hd20 =	sprintf("%5s", $hd20);
$hd25 =	sprintf("%5s", $hd25);
$hd30 =	sprintf("%5s", $hd30);
$hd35 =	sprintf("%5s", $hd35);
$hd40 =	sprintf("%5s", $hd40);
$hd45 =	sprintf("%5s", $hd45);
$hd50 =	sprintf("%5s", $hd50);
$hd55 =	sprintf("%5s", $hd55);
$hd60 =	sprintf("%5s", $hd60);
$hd90 =	sprintf("%5s", $hd90);
$hd99 =	sprintf("%5s", $hd99);

$TOTALcalls =		sprintf("%10s", $TOTALcalls);

$ASCII_text.="| $hd_0 $hd_5 $hd10 $hd15 $hd20 $hd25 $hd30 $hd35 $hd40 $hd45 $hd50 $hd55 $hd60 $hd90 $hd99 | $TOTALcalls |\n";
$ASCII_text.="+-------------------------------------------------------------------------------------------+------------+\n";

$CSV_text2.="\"\",\"$hd_0\",\"$hd_5\",\"$hd10\",\"$hd15\",\"$hd20\",\"$hd25\",\"$hd30\",\"$hd35\",\"$hd40\",\"$hd45\",\"$hd50\",\"$hd55\",\"$hd60\",\"$hd90\",\"$hd99\",\"$TOTALcalls\"\n";


if ($report_display_type=="HTML") {
	$graph_stats=array();
	$sec_ary=array();
	$stmt="select count(*),round(queue_seconds) as rd_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) group by rd_sec order by rd_sec asc;";
	$ms_stmt="select queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) order by queue_seconds desc limit 1;"; 
	$mc_stmt="select count(*) as ct from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) group by queue_seconds order by ct desc limit 1;";
	if ($DID=='Y')
		{
		$stmt="select count(*),round(queue_seconds) as rd_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by rd_sec;";
		$ms_stmt="select queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) order by queue_seconds desc limit 1;"; 
		$mc_stmt="select count(*) as ct from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by queue_seconds order by ct desc limit 1;";
		}
	if ($DB) {$GRAPH_text.=$stmt."\n";}
	$ms_rslt=mysql_to_mysqli($ms_stmt, $link);
	$ms_row=mysqli_fetch_row($ms_rslt);
	$max_seconds=$ms_row[0];
	if ($max_seconds>90) {$max_seconds=91;}
	for ($i=0; $i<=$max_seconds; $i++) {
		$sec_ary[$i]=0;
	}

	$mc_rslt=mysql_to_mysqli($mc_stmt, $link);
	$mc_row=mysqli_fetch_row($mc_rslt);
	$max_calls=$ms_row[0];
	if ($max_calls<=10) {
		while ($maxcalls%5!=0) {
			$maxcalls++;
		}
	} else if ($max_calls<=100) {
		while ($maxcalls%10!=0) {
			$maxcalls++;
		}
	} else if ($max_calls<=1000) {
		while ($maxcalls%50!=0) {
			$maxcalls++;
		}
	} else {
		while ($maxcalls%500!=0) {
			$maxcalls++;
		}
	}
	$rslt=mysql_to_mysqli($stmt, $link);

	$over90=0;
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[1]<=90) {
			$sec_ary[$row[1]]=$row[0];
		} else {
			$over90+=$row[0];
		}
	}
	$sec_ary[91]=$over90;
	
	for ($i=0; $i<=$max_seconds; $i++) {
		if ($i<=90) {
			if ($i%5==0) {$int=$i;} else {$int="";}
			$graph_stats[$i][0]=$sec_ary[$i];
			$graph_stats[$i][1]=$i;
		} else {
			$graph_stats[$i][0]=$sec_ary[$i];
			$graph_stats[$i][1]=91;
		}
	}

	$GRAPH_text="";	
	#########
	$graph_array=array("ACS_QUEUEseconds|||integer|");
	$graph_id++;
	$default_graph="line"; # Graph that is initally displayed when page loads
	include("graph_color_schemas.inc"); 

	$graph_totals_array=array();
	$graph_totals_rawdata=array();
	for ($q=0; $q<count($graph_array); $q++) {
		$graph_info=explode("|", $graph_array[$q]); 
		$current_graph_total=0;
		$dataset_name=$graph_info[0];
		$dataset_index=$graph_info[1];
		$dataset_type=$graph_info[3];
		if ($q==0) {$preload_dataset=$dataset_name;}  # Used below to load initial graph

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
			$labels.="\"".$graph_stats[$d][1]."\",";
			$data.="\"".$graph_stats[$d][0]."\",";
			$current_graph_total+=$graph_stats[$d][0];
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
	$graph_title=_QXZ("QUEUE SECONDS");
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas."<PRE>";
	$MAIN.=$GRAPH_text;
	}
else 
	{
	$MAIN.=$ASCII_text;
	}


##############################
#########  CALL DROP TIME BREAKDOWN IN SECONDS

$BDdropCALLS = 0;

$ASCII_text="\n";
$ASCII_text.="---------- $rpt_type_verbiage "._QXZ("DROP TIME BREAKDOWN IN SECONDS")."\n";
$ASCII_text.="+-------------------------------------------------------------------------------------------+------------+\n";
$ASCII_text.="|     0     5    10    15    20    25    30    35    40    45    50    55    60    90   +90 | "._QXZ("TOTAL",10)." |\n";
$ASCII_text.="+-------------------------------------------------------------------------------------------+------------+\n";

$CSV_text2.="\n\"$rpt_type_verbiage "._QXZ("DROP TIME BREAKDOWN IN SECONDS")."\"\n";
$CSV_text2.="\"\",\"0\",\"5\",\"10\",\"15\",\"20\",\"25\",\"30\",\"35\",\"40\",\"45\",\"50\",\"55\",\"60\",\"90\",\"+90\",\""._QXZ("TOTAL")."\"\n";

$stmt="select count(*),queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) and status IN('DROP','XDROP') group by queue_seconds;";
if ($DID=='Y')
	{
	$stmt="select count(*),queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and status IN('DROP','XDROP') group by queue_seconds;";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n"; $GRAPH_text.=$stmt."\n";}
$reasons_to_print = mysqli_num_rows($rslt);
$i=0;
$dd_0=0; $dd_5=0; $dd10=0; $dd15=0; $dd20=0; $dd25=0; $dd30=0; $dd35=0; $dd40=0; $dd45=0; $dd50=0; $dd55=0; $dd60=0; $dd90=0; $dd99=0;

while ($i < $reasons_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$BDdropCALLS = ($BDdropCALLS + $row[0]);

	if ($row[1] == 0) {$dd_0 = ($dd_0 + $row[0]);}
	if ( ($row[1] > 0) and ($row[1] <= 5) ) {$dd_5 = ($dd_5 + $row[0]);}
	if ( ($row[1] > 5) and ($row[1] <= 10) ) {$dd10 = ($dd10 + $row[0]);}
	if ( ($row[1] > 10) and ($row[1] <= 15) ) {$dd15 = ($dd15 + $row[0]);}
	if ( ($row[1] > 15) and ($row[1] <= 20) ) {$dd20 = ($dd20 + $row[0]);}
	if ( ($row[1] > 20) and ($row[1] <= 25) ) {$dd25 = ($dd25 + $row[0]);}
	if ( ($row[1] > 25) and ($row[1] <= 30) ) {$dd30 = ($dd30 + $row[0]);}
	if ( ($row[1] > 30) and ($row[1] <= 35) ) {$dd35 = ($dd35 + $row[0]);}
	if ( ($row[1] > 35) and ($row[1] <= 40) ) {$dd40 = ($dd40 + $row[0]);}
	if ( ($row[1] > 40) and ($row[1] <= 45) ) {$dd45 = ($dd45 + $row[0]);}
	if ( ($row[1] > 45) and ($row[1] <= 50) ) {$dd50 = ($dd50 + $row[0]);}
	if ( ($row[1] > 50) and ($row[1] <= 55) ) {$dd55 = ($dd55 + $row[0]);}
	if ( ($row[1] > 55) and ($row[1] <= 60) ) {$dd60 = ($dd60 + $row[0]);}
	if ( ($row[1] > 60) and ($row[1] <= 90) ) {$dd90 = ($dd90 + $row[0]);}
	if ($row[1] > 90) {$dd99 = ($dd99 + $row[0]);}
	$i++;
	}

$dd_0 =	sprintf("%5s", $dd_0);
$dd_5 =	sprintf("%5s", $dd_5);
$dd10 =	sprintf("%5s", $dd10);
$dd15 =	sprintf("%5s", $dd15);
$dd20 =	sprintf("%5s", $dd20);
$dd25 =	sprintf("%5s", $dd25);
$dd30 =	sprintf("%5s", $dd30);
$dd35 =	sprintf("%5s", $dd35);
$dd40 =	sprintf("%5s", $dd40);
$dd45 =	sprintf("%5s", $dd45);
$dd50 =	sprintf("%5s", $dd50);
$dd55 =	sprintf("%5s", $dd55);
$dd60 =	sprintf("%5s", $dd60);
$dd90 =	sprintf("%5s", $dd90);
$dd99 =	sprintf("%5s", $dd99);

$BDdropCALLS =		sprintf("%10s", $BDdropCALLS);

$ASCII_text.="| $dd_0 $dd_5 $dd10 $dd15 $dd20 $dd25 $dd30 $dd35 $dd40 $dd45 $dd50 $dd55 $dd60 $dd90 $dd99 | $BDdropCALLS |\n";
$ASCII_text.="+-------------------------------------------------------------------------------------------+------------+\n";


$CSV_text2.="\"\",\"$dd_0\",\"$dd_5\",\"$dd10\",\"$dd15\",\"$dd20\",\"$dd25\",\"$dd30\",\"$dd35\",\"$dd40\",\"$dd45\",\"$dd50\",\"$dd55\",\"$dd60\",\"$dd90\",\"$dd99\",\"$BDdropCALLS\"\n";

if ($report_display_type=="HTML") {
	$graph_stats=array();
	$stmt="select count(*),round(queue_seconds) as rd_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and status IN('DROP','XDROP') group by rd_sec order by rd_sec asc;";
	$ms_stmt="select queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and status IN('DROP','XDROP') order by queue_seconds desc limit 1;"; 
	$mc_stmt="select count(*) as ct from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) and status IN('DROP','XDROP') group by queue_seconds order by ct desc limit 1;";
	if ($DID=='Y')
		{
		$stmt="select count(*),round(queue_seconds) as rd_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and status IN('DROP','XDROP') group by rd_sec;";
		$ms_stmt="select queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and status IN('DROP','XDROP') order by queue_seconds desc limit 1;"; 
		$mc_stmt="select count(*) as ct from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and status IN('DROP','XDROP') group by queue_seconds order by ct desc limit 1;";
		}
	if ($DB) {$MAIN.="$stmt\n$ms_stmt\n$mc_stmt\n";}

	$ms_rslt=mysql_to_mysqli($ms_stmt, $link);
	$ms_row=mysqli_fetch_row($ms_rslt);
	$max_seconds=$ms_row[0];
	if ($max_seconds>90) {$max_seconds=91;}
	for ($i=0; $i<=$max_seconds; $i++) {
		$sec_ary[$i]=0;
	}

	$mc_rslt=mysql_to_mysqli($mc_stmt, $link);
	$mc_row=mysqli_fetch_row($mc_rslt);
	$max_calls=$ms_row[0];
	if ($max_calls<=10) {
		while ($maxcalls%5!=0) {
			$maxcalls++;
		}
	} else if ($max_calls<=100) {
		while ($maxcalls%10!=0) {
			$maxcalls++;
		}
	} else if ($max_calls<=1000) {
		while ($maxcalls%50!=0) {
			$maxcalls++;
		}
	} else {
		while ($maxcalls%500!=0) {
			$maxcalls++;
		}
	}
	$rslt=mysql_to_mysqli($stmt, $link);

	$over90=0;
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[1]<=90) {
			$sec_ary[$row[1]]=$row[0];
		} else {
			$over90+=$row[0];
		}
	}
	$sec_ary[91]=$over90;
	
	for ($i=0; $i<=$max_seconds; $i++) {
		if ($i<=90) {
			if ($i%5==0) {$int=$i;} else {$int="";}
			$graph_stats[$i][0]=$sec_ary[$i];
			$graph_stats[$i][1]=$i;
		} else {
			$graph_stats[$i][0]=$sec_ary[$i];
			$graph_stats[$i][1]=91;
		}
	}

	$GRAPH_text="";	
	#########
	$graph_array=array("ACS_DROPTIMEBREAKDOWNseconds|||integer|");
	$graph_id++;
	$default_graph="line"; # Graph that is initally displayed when page loads
	include("graph_color_schemas.inc"); 

	$graph_totals_array=array();
	$graph_totals_rawdata=array();
	for ($q=0; $q<count($graph_array); $q++) {
		$graph_info=explode("|", $graph_array[$q]); 
		$current_graph_total=0;
		$dataset_name=$graph_info[0];
		$dataset_index=$graph_info[1];
		$dataset_type=$graph_info[3];
		if ($q==0) {$preload_dataset=$dataset_name;}  # Used below to load initial graph

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
			$labels.="\"".$graph_stats[$d][1]."\",";
			$data.="\"".$graph_stats[$d][0]."\",";
			$current_graph_total+=$graph_stats[$d][0];
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
	$graph_title="DROP TIME BREAKDOWN IN SECONDS";
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas."<PRE>";

	$MAIN.=$GRAPH_text;
	}
else
	{
	$MAIN.=$ASCII_text;
	}



##############################
#########  CALL ANSWERED TIME AND PERCENT BREAKDOWN IN SECONDS

$BDansweredCALLS = 0;

$ASCII_text="\n";
$ASCII_text.="           $rpt_type_verbiage "._QXZ("ANSWERED TIME AND PERCENT BREAKDOWN IN SECONDS")."\n";
$ASCII_text.="          +-------------------------------------------------------------------------------------------+------------+\n";
$ASCII_text.="          |     0     5    10    15    20    25    30    35    40    45    50    55    60    90   +90 | "._QXZ("TOTAL",10)." |\n";
$ASCII_text.="----------+-------------------------------------------------------------------------------------------+------------+\n";

$CSV_text2.="\n\"$rpt_type_verbiage "._QXZ("ANSWERED TIME AND PERCENT BREAKDOWN IN SECONDS")."\"\n";
$CSV_text2.="\"\",\"0\",\"5\",\"10\",\"15\",\"20\",\"25\",\"30\",\"35\",\"40\",\"45\",\"50\",\"55\",\"60\",\"90\",\"+90\",\""._QXZ("TOTAL")."\"\n";

$stmt="select count(*),queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by queue_seconds;";
if ($DID=='Y')
	{
	$stmt="select count(*),queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by queue_seconds;";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$reasons_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $reasons_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$BDansweredCALLS = ($BDansweredCALLS + $row[0]);
	
	### Get interval totals
	if ($row[1] == 0) {$ad_0 = ($ad_0 + $row[0]);}
	if ( ($row[1] > 0) and ($row[1] <= 5) ) {$ad_5 = ($ad_5 + $row[0]);}
	if ( ($row[1] > 5) and ($row[1] <= 10) ) {$ad10 = ($ad10 + $row[0]);}
	if ( ($row[1] > 10) and ($row[1] <= 15) ) {$ad15 = ($ad15 + $row[0]);}
	if ( ($row[1] > 15) and ($row[1] <= 20) ) {$ad20 = ($ad20 + $row[0]);}
	if ( ($row[1] > 20) and ($row[1] <= 25) ) {$ad25 = ($ad25 + $row[0]);}
	if ( ($row[1] > 25) and ($row[1] <= 30) ) {$ad30 = ($ad30 + $row[0]);}
	if ( ($row[1] > 30) and ($row[1] <= 35) ) {$ad35 = ($ad35 + $row[0]);}
	if ( ($row[1] > 35) and ($row[1] <= 40) ) {$ad40 = ($ad40 + $row[0]);}
	if ( ($row[1] > 40) and ($row[1] <= 45) ) {$ad45 = ($ad45 + $row[0]);}
	if ( ($row[1] > 45) and ($row[1] <= 50) ) {$ad50 = ($ad50 + $row[0]);}
	if ( ($row[1] > 50) and ($row[1] <= 55) ) {$ad55 = ($ad55 + $row[0]);}
	if ( ($row[1] > 55) and ($row[1] <= 60) ) {$ad60 = ($ad60 + $row[0]);}
	if ( ($row[1] > 60) and ($row[1] <= 90) ) {$ad90 = ($ad90 + $row[0]);}
	if ($row[1] > 90) {$ad99 = ($ad99 + $row[0]);}
	$i++;
	}

### Calculate cumulative totals
$Cad_0 =$ad_0;
$Cad_5 =($Cad_0 + $ad_5);
$Cad10 =($Cad_5 + $ad10);
$Cad15 =($Cad10 + $ad15);
$Cad20 =($Cad15 + $ad20);
$Cad25 =($Cad20 + $ad25);
$Cad30 =($Cad25 + $ad30);
$Cad35 =($Cad30 + $ad35);
$Cad40 =($Cad35 + $ad40);
$Cad45 =($Cad40 + $ad45);
$Cad50 =($Cad45 + $ad50);
$Cad55 =($Cad50 + $ad55);
$Cad60 =($Cad55 + $ad60);
$Cad90 =($Cad60 + $ad90);
$Cad99 =($Cad90 + $ad99);

### Calculate interval percentages
$pad_0=0; $pad_5=0; $pad10=0; $pad15=0; $pad20=0; $pad25=0; $pad30=0; $pad35=0; $pad40=0; $pad45=0; $pad50=0; $pad55=0; $pad60=0; $pad90=0; $pad99=0; 
$pCad_0=0; $pCad_5=0; $pCad10=0; $pCad15=0; $pCad20=0; $pCad25=0; $pCad30=0; $pCad35=0; $pCad40=0; $pCad45=0; $pCad50=0; $pCad55=0; $pCad60=0; $pCad90=0; $pCad99=0; 
if ( ($BDansweredCALLS > 0) and ($TOTALcalls > 0) )
	{
	$pad_0 = (MathZDC($ad_0, $TOTALcalls) * 100);	$pad_0 = round($pad_0, 0);
	$pad_5 = (MathZDC($ad_5, $TOTALcalls) * 100);	$pad_5 = round($pad_5, 0);
	$pad10 = (MathZDC($ad10, $TOTALcalls) * 100);	$pad10 = round($pad10, 0);
	$pad15 = (MathZDC($ad15, $TOTALcalls) * 100);	$pad15 = round($pad15, 0);
	$pad20 = (MathZDC($ad20, $TOTALcalls) * 100);	$pad20 = round($pad20, 0);
	$pad25 = (MathZDC($ad25, $TOTALcalls) * 100);	$pad25 = round($pad25, 0);
	$pad30 = (MathZDC($ad30, $TOTALcalls) * 100);	$pad30 = round($pad30, 0);
	$pad35 = (MathZDC($ad35, $TOTALcalls) * 100);	$pad35 = round($pad35, 0);
	$pad40 = (MathZDC($ad40, $TOTALcalls) * 100);	$pad40 = round($pad40, 0);
	$pad45 = (MathZDC($ad45, $TOTALcalls) * 100);	$pad45 = round($pad45, 0);
	$pad50 = (MathZDC($ad50, $TOTALcalls) * 100);	$pad50 = round($pad50, 0);
	$pad55 = (MathZDC($ad55, $TOTALcalls) * 100);	$pad55 = round($pad55, 0);
	$pad60 = (MathZDC($ad60, $TOTALcalls) * 100);	$pad60 = round($pad60, 0);
	$pad90 = (MathZDC($ad90, $TOTALcalls) * 100);	$pad90 = round($pad90, 0);
	$pad99 = (MathZDC($ad99, $TOTALcalls) * 100);	$pad99 = round($pad99, 0);

	$pCad_0 = (MathZDC($Cad_0, $TOTALcalls) * 100);	$pCad_0 = round($pCad_0, 0);
	$pCad_5 = (MathZDC($Cad_5, $TOTALcalls) * 100);	$pCad_5 = round($pCad_5, 0);
	$pCad10 = (MathZDC($Cad10, $TOTALcalls) * 100);	$pCad10 = round($pCad10, 0);
	$pCad15 = (MathZDC($Cad15, $TOTALcalls) * 100);	$pCad15 = round($pCad15, 0);
	$pCad20 = (MathZDC($Cad20, $TOTALcalls) * 100);	$pCad20 = round($pCad20, 0);
	$pCad25 = (MathZDC($Cad25, $TOTALcalls) * 100);	$pCad25 = round($pCad25, 0);
	$pCad30 = (MathZDC($Cad30, $TOTALcalls) * 100);	$pCad30 = round($pCad30, 0);
	$pCad35 = (MathZDC($Cad35, $TOTALcalls) * 100);	$pCad35 = round($pCad35, 0);
	$pCad40 = (MathZDC($Cad40, $TOTALcalls) * 100);	$pCad40 = round($pCad40, 0);
	$pCad45 = (MathZDC($Cad45, $TOTALcalls) * 100);	$pCad45 = round($pCad45, 0);
	$pCad50 = (MathZDC($Cad50, $TOTALcalls) * 100);	$pCad50 = round($pCad50, 0);
	$pCad55 = (MathZDC($Cad55, $TOTALcalls) * 100);	$pCad55 = round($pCad55, 0);
	$pCad60 = (MathZDC($Cad60, $TOTALcalls) * 100);	$pCad60 = round($pCad60, 0);
	$pCad90 = (MathZDC($Cad90, $TOTALcalls) * 100);	$pCad90 = round($pCad90, 0);
	$pCad99 = (MathZDC($Cad99, $TOTALcalls) * 100);	$pCad99 = round($pCad99, 0);

	$ApCad_0 = (MathZDC($Cad_0, $BDansweredCALLS) * 100);	$ApCad_0 = round($ApCad_0, 0);
	$ApCad_5 = (MathZDC($Cad_5, $BDansweredCALLS) * 100);	$ApCad_5 = round($ApCad_5, 0);
	$ApCad10 = (MathZDC($Cad10, $BDansweredCALLS) * 100);	$ApCad10 = round($ApCad10, 0);
	$ApCad15 = (MathZDC($Cad15, $BDansweredCALLS) * 100);	$ApCad15 = round($ApCad15, 0);
	$ApCad20 = (MathZDC($Cad20, $BDansweredCALLS) * 100);	$ApCad20 = round($ApCad20, 0);
	$ApCad25 = (MathZDC($Cad25, $BDansweredCALLS) * 100);	$ApCad25 = round($ApCad25, 0);
	$ApCad30 = (MathZDC($Cad30, $BDansweredCALLS) * 100);	$ApCad30 = round($ApCad30, 0);
	$ApCad35 = (MathZDC($Cad35, $BDansweredCALLS) * 100);	$ApCad35 = round($ApCad35, 0);
	$ApCad40 = (MathZDC($Cad40, $BDansweredCALLS) * 100);	$ApCad40 = round($ApCad40, 0);
	$ApCad45 = (MathZDC($Cad45, $BDansweredCALLS) * 100);	$ApCad45 = round($ApCad45, 0);
	$ApCad50 = (MathZDC($Cad50, $BDansweredCALLS) * 100);	$ApCad50 = round($ApCad50, 0);
	$ApCad55 = (MathZDC($Cad55, $BDansweredCALLS) * 100);	$ApCad55 = round($ApCad55, 0);
	$ApCad60 = (MathZDC($Cad60, $BDansweredCALLS) * 100);	$ApCad60 = round($ApCad60, 0);
	$ApCad90 = (MathZDC($Cad90, $BDansweredCALLS) * 100);	$ApCad90 = round($ApCad90, 0);
	$ApCad99 = (MathZDC($Cad99, $BDansweredCALLS) * 100);	$ApCad99 = round($ApCad99, 0);
	}

### Format variables
$ad_0 = sprintf("%5s", $ad_0);
$ad_5 = sprintf("%5s", $ad_5);
$ad10 = sprintf("%5s", $ad10);
$ad15 = sprintf("%5s", $ad15);
$ad20 = sprintf("%5s", $ad20);
$ad25 = sprintf("%5s", $ad25);
$ad30 = sprintf("%5s", $ad30);
$ad35 = sprintf("%5s", $ad35);
$ad40 = sprintf("%5s", $ad40);
$ad45 = sprintf("%5s", $ad45);
$ad50 = sprintf("%5s", $ad50);
$ad55 = sprintf("%5s", $ad55);
$ad60 = sprintf("%5s", $ad60);
$ad90 = sprintf("%5s", $ad90);
$ad99 = sprintf("%5s", $ad99);
$Cad_0 = sprintf("%5s", $Cad_0);
$Cad_5 = sprintf("%5s", $Cad_5);
$Cad10 = sprintf("%5s", $Cad10);
$Cad15 = sprintf("%5s", $Cad15);
$Cad20 = sprintf("%5s", $Cad20);
$Cad25 = sprintf("%5s", $Cad25);
$Cad30 = sprintf("%5s", $Cad30);
$Cad35 = sprintf("%5s", $Cad35);
$Cad40 = sprintf("%5s", $Cad40);
$Cad45 = sprintf("%5s", $Cad45);
$Cad50 = sprintf("%5s", $Cad50);
$Cad55 = sprintf("%5s", $Cad55);
$Cad60 = sprintf("%5s", $Cad60);
$Cad90 = sprintf("%5s", $Cad90);
$Cad99 = sprintf("%5s", $Cad99);
$pad_0 = sprintf("%4s", $pad_0) . '%';
$pad_5 = sprintf("%4s", $pad_5) . '%';
$pad10 = sprintf("%4s", $pad10) . '%';
$pad15 = sprintf("%4s", $pad15) . '%';
$pad20 = sprintf("%4s", $pad20) . '%';
$pad25 = sprintf("%4s", $pad25) . '%';
$pad30 = sprintf("%4s", $pad30) . '%';
$pad35 = sprintf("%4s", $pad35) . '%';
$pad40 = sprintf("%4s", $pad40) . '%';
$pad45 = sprintf("%4s", $pad45) . '%';
$pad50 = sprintf("%4s", $pad50) . '%';
$pad55 = sprintf("%4s", $pad55) . '%';
$pad60 = sprintf("%4s", $pad60) . '%';
$pad90 = sprintf("%4s", $pad90) . '%';
$pad99 = sprintf("%4s", $pad99) . '%';
$pCad_0 = sprintf("%4s", $pCad_0) . '%';
$pCad_5 = sprintf("%4s", $pCad_5) . '%';
$pCad10 = sprintf("%4s", $pCad10) . '%';
$pCad15 = sprintf("%4s", $pCad15) . '%';
$pCad20 = sprintf("%4s", $pCad20) . '%';
$pCad25 = sprintf("%4s", $pCad25) . '%';
$pCad30 = sprintf("%4s", $pCad30) . '%';
$pCad35 = sprintf("%4s", $pCad35) . '%';
$pCad40 = sprintf("%4s", $pCad40) . '%';
$pCad45 = sprintf("%4s", $pCad45) . '%';
$pCad50 = sprintf("%4s", $pCad50) . '%';
$pCad55 = sprintf("%4s", $pCad55) . '%';
$pCad60 = sprintf("%4s", $pCad60) . '%';
$pCad90 = sprintf("%4s", $pCad90) . '%';
$pCad99 = sprintf("%4s", $pCad99) . '%';
$ApCad_0 = sprintf("%4s", $ApCad_0) . '%';
$ApCad_5 = sprintf("%4s", $ApCad_5) . '%';
$ApCad10 = sprintf("%4s", $ApCad10) . '%';
$ApCad15 = sprintf("%4s", $ApCad15) . '%';
$ApCad20 = sprintf("%4s", $ApCad20) . '%';
$ApCad25 = sprintf("%4s", $ApCad25) . '%';
$ApCad30 = sprintf("%4s", $ApCad30) . '%';
$ApCad35 = sprintf("%4s", $ApCad35) . '%';
$ApCad40 = sprintf("%4s", $ApCad40) . '%';
$ApCad45 = sprintf("%4s", $ApCad45) . '%';
$ApCad50 = sprintf("%4s", $ApCad50) . '%';
$ApCad55 = sprintf("%4s", $ApCad55) . '%';
$ApCad60 = sprintf("%4s", $ApCad60) . '%';
$ApCad90 = sprintf("%4s", $ApCad90) . '%';
$ApCad99 = sprintf("%4s", $ApCad99) . '%';

$BDansweredCALLS =		sprintf("%10s", $BDansweredCALLS);

### Format and output
$answeredTOTALs = "$ad_0 $ad_5 $ad10 $ad15 $ad20 $ad25 $ad30 $ad35 $ad40 $ad45 $ad50 $ad55 $ad60 $ad90 $ad99 | $BDansweredCALLS |";
$answeredCUMULATIVE = "$Cad_0 $Cad_5 $Cad10 $Cad15 $Cad20 $Cad25 $Cad30 $Cad35 $Cad40 $Cad45 $Cad50 $Cad55 $Cad60 $Cad90 $Cad99 | $BDansweredCALLS |";
$answeredINT_PERCENT = "$pad_0 $pad_5 $pad10 $pad15 $pad20 $pad25 $pad30 $pad35 $pad40 $pad45 $pad50 $pad55 $pad60 $pad90 $pad99 |            |";
$answeredCUM_PERCENT = "$pCad_0 $pCad_5 $pCad10 $pCad15 $pCad20 $pCad25 $pCad30 $pCad35 $pCad40 $pCad45 $pCad50 $pCad55 $pCad60 $pCad90 $pCad99 |            |";
$answeredCUM_ANS_PERCENT = "$ApCad_0 $ApCad_5 $ApCad10 $ApCad15 $ApCad20 $ApCad25 $ApCad30 $ApCad35 $ApCad40 $ApCad45 $ApCad50 $ApCad55 $ApCad60 $ApCad90 $ApCad99 |            |";
$ASCII_text.=_QXZ("INTERVAL",10)."| $answeredTOTALs\n";
$ASCII_text.=_QXZ("INT",8)." %| $answeredINT_PERCENT\n";
$ASCII_text.=_QXZ("CUMULATIVE",10)."| $answeredCUMULATIVE\n";
$ASCII_text.=_QXZ("CUM",8)." %| $answeredCUM_PERCENT\n";
$ASCII_text.=_QXZ("CUM ANS",8)." %| $answeredCUM_ANS_PERCENT\n";
$ASCII_text.="----------+-------------------------------------------------------------------------------------------+------------+\n";


$CSV_text2.="\""._QXZ("INTERVAL")."\",\"$ad_0\",\"$ad_5\",\"$ad10\",\"$ad15\",\"$ad20\",\"$ad25\",\"$ad30\",\"$ad35\",\"$ad40\",\"$ad45\",\"$ad50\",\"$ad55\",\"$ad60\",\"$ad90\",\"$ad99\",\"$BDansweredCALLS\"\n";
$CSV_text2.="\""._QXZ("INT")." %\",\"$pad_0\",\"$pad_5\",\"$pad10\",\"$pad15\",\"$pad20\",\"$pad25\",\"$pad30\",\"$pad35\",\"$pad40\",\"$pad45\",\"$pad50\",\"$pad55\",\"$pad60\",\"$pad90\",\"$pad99\"\n";
$CSV_text2.="\""._QXZ("CUMULATIVE")."\",\"$Cad_0\",\"$Cad_5\",\"$Cad10\",\"$Cad15\",\"$Cad20\",\"$Cad25\",\"$Cad30\",\"$Cad35\",\"$Cad40\",\"$Cad45\",\"$Cad50\",\"$Cad55\",\"$Cad60\",\"$Cad90\",\"$Cad99\",\"$BDansweredCALLS\"\n";
$CSV_text2.="\""._QXZ("CUM")." %\",\"$pCad_0\",\"$pCad_5\",\"$pCad10\",\"$pCad15\",\"$pCad20\",\"$pCad25\",\"$pCad30\",\"$pCad35\",\"$pCad40\",\"$pCad45\",\"$pCad50\",\"$pCad55\",\"$pCad60\",\"$pCad90\",\"$pCad99\"\n";
$CSV_text2.="\""._QXZ("CUM ANS")." %\",\"$ApCad_0\",\"$ApCad_5\",\"$ApCad10\",\"$ApCad15\",\"$ApCad20\",\"$ApCad25\",\"$ApCad30\",\"$ApCad35\",\"$ApCad40\",\"$ApCad45\",\"$ApCad50\",\"$ApCad55\",\"$ApCad60\",\"$ApCad90\",\"$ApCad99\"\n";

if ($report_display_type=="HTML") {
	$graph_stats=array();
	$stmt="select count(*),round(queue_seconds) as rd_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by rd_sec order by rd_sec asc;";
	$ms_stmt="select queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') order by queue_seconds desc limit 1;"; 
	$mc_stmt="select count(*) as ct from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by queue_seconds order by ct desc limit 1;";
	if ($DID=='Y')
		{
		$stmt="select count(*),round(queue_seconds) as rd_sec from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by rd_sec;";
		$ms_stmt="select queue_seconds from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') order by queue_seconds desc limit 1;"; 
		$mc_stmt="select count(*) as ct from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and status NOT IN('DROP','XDROP','HXFER','QVMAIL','HOLDTO','LIVE','QUEUE','TIMEOT','AFTHRS','NANQUE','IQNANQ','INBND','MAXCAL') group by queue_seconds order by ct desc limit 1;";
		}
	if ($DB) {$GRAPH_text.=$stmt."\n";}
	$ms_rslt=mysql_to_mysqli($ms_stmt, $link);
	$ms_row=mysqli_fetch_row($ms_rslt);
	$max_seconds=$ms_row[0];
	if ($max_seconds>90) {$max_seconds=91;}
	for ($i=0; $i<=$max_seconds; $i++) {
		$sec_ary[$i]=0;
	}

	$mc_rslt=mysql_to_mysqli($mc_stmt, $link);
	$mc_row=mysqli_fetch_row($mc_rslt);
	$max_calls=$ms_row[0];
	if ($max_calls<=10) {
		while ($maxcalls%5!=0) {
			$maxcalls++;
		}
	} else if ($max_calls<=100) {
		while ($maxcalls%10!=0) {
			$maxcalls++;
		}
	} else if ($max_calls<=1000) {
		while ($maxcalls%50!=0) {
			$maxcalls++;
		}
	} else {
		while ($maxcalls%500!=0) {
			$maxcalls++;
		}
	}
	$rslt=mysql_to_mysqli($stmt, $link);

	$over90=0;
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[1]<=90) {
			$sec_ary[$row[1]]=$row[0];
		} else {
			$over90+=$row[0];
		}
	}
	$sec_ary[91]=$over90;
	
	for ($i=0; $i<=$max_seconds; $i++) {
		if ($i<=90) {
			if ($i%5==0) {$int=$i;} else {$int="";}
			$graph_stats[$i][0]=$sec_ary[$i];
			$graph_stats[$i][1]=$i;
		} else {
			$graph_stats[$i][0]=$sec_ary[$i];
			$graph_stats[$i][1]=91;
		}
	}

	$GRAPH_text="";	
	#########
	$graph_array=array("ACS_CATBdata|||integer|");
	$graph_id++;
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
		if ($q==0) {$preload_dataset=$dataset_name;}  # Used below to load initial graph

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
			$labels.="\"".$graph_stats[$d][1]."\",";
			$data.="\"".$graph_stats[$d][0]."\",";
			$current_graph_total+=$graph_stats[$d][0];
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
	$graph_title="CALL ANSWERED TIME BREAKDOWN";
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas;

	$MAIN.=$GRAPH_text;
	}
else
	{
	$MAIN.=$ASCII_text;
	}

##############################
#########  CALL HANGUP REASON STATS

$TOTALcalls = 0;

$ASCII_text="\n";
$ASCII_text.="---------- $rpt_type_verbiage "._QXZ("HANGUP REASON STATS",25)." <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=3&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
$ASCII_text.="+----------------------+------------+\n";
$ASCII_text.="| "._QXZ("HANGUP REASON",20)." | $rpt_type_verbiages     |\n";
$ASCII_text.="+----------------------+------------+\n";

$CSV_text3.="\n\"$rpt_type_verbiage "._QXZ("HANGUP REASON STATS")."\"\n";
$CSV_text3.="\""._QXZ("HANGUP REASON")."\",\"$rpt_type_verbiages\"\n";

$stmt="select count(*),term_reason from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) group by term_reason order by term_reason;";
if ($DID=='Y')
	{
	$stmt="select count(*),term_reason from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by term_reason order by term_reason;";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$reasons_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $reasons_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$TOTALcalls = ($TOTALcalls + $row[0]);

	$REASONcount =	sprintf("%10s", $row[0]);
	while(strlen($REASONcount)>10) {$REASONcount = substr("$REASONcount", 0, -1);}
	$reason =	sprintf("%-20s", $row[1]);while(strlen($reason)>20) {$reason = substr("$reason", 0, -1);}
#	if (preg_match('/NONE/',$reason)) {$reason = 'NO ANSWER           ';}

	$ASCII_text.="| $reason | $REASONcount |\n";
	$CSV_text3.="\"$reason\",\"$REASONcount\"\n";

	$i++;
	}

$TOTALcalls =		sprintf("%10s", $TOTALcalls);

$ASCII_text.="+----------------------+------------+\n";
$ASCII_text.="| "._QXZ("TOTAL:",20). " | $TOTALcalls |\n";
$ASCII_text.="+----------------------+------------+\n";

$CSV_text3.="\""._QXZ("TOTAL").":\",\"$TOTALcalls\"\n";

if ($report_display_type=="HTML") 
	{
	$graph_stats=array();
	$rslt=mysql_to_mysqli($stmt, $link);
	$high_ct=0; $i=0;
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[0]>$high_ct) {$high_ct=$row[0];}
		$graph_stats[$i][0]=$row[0];
		$graph_stats[$i][1]=$row[1];
		$i++;
	}

	$GRAPH_text="";	
	#########
	$graph_array=array("ACS_HANGUPREASONdata||HANGUP REASON STATS|integer|");
	$graph_id++;
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
		if ($q==0) {$preload_dataset=$dataset_name;}  # Used below to load initial graph

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
			$labels.="\"".$graph_stats[$d][1]."\",";
			$data.="\"".$graph_stats[$d][0]."\",";
			$current_graph_total+=$graph_stats[$d][0];
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
	$graph_title=_QXZ("HANGUP REASON STATS");
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas;

	$MAIN.=$GRAPH_text;
	}
else
	{
	$MAIN.=$ASCII_text;
	}

##############################
#########  CALL STATUS STATS

$TOTALcalls = 0;

$ASCII_text="\n";
$ASCII_text.="---------- $rpt_type_verbiage "._QXZ("STATUS STATS",18)." <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=4&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
$ASCII_text.="+--------+----------------------+----------------------+------------+------------+----------+-----------+\n";
$ASCII_text.="| "._QXZ("STATUS",6)." | "._QXZ("DESCRIPTION",20)." | "._QXZ("CATEGORY",20)." | $rpt_type_verbiages     | "._QXZ("TOTAL TIME",10)." | "._QXZ("AVG TIME",8)." |$rpt_type_verbiages/"._QXZ("HOUR",4)."|\n";
$ASCII_text.="+--------+----------------------+----------------------+------------+------------+----------+-----------+\n";

$CSV_text4.="\n\"$rpt_type_verbiage "._QXZ("STATUS STATS")."\"\n";
$CSV_text4.="\""._QXZ("STATUS")."\",\""._QXZ("DESCRIPTION")."\",\""._QXZ("CATEGORY")."\",\"$rpt_type_verbiages\",\""._QXZ("TOTAL TIME")."\",\""._QXZ("AVG TIME")."\",\"$rpt_type_verbiages/HOUR\"\n";



## get counts and time totals for all statuses in this campaign
$stmt="select count(*),status,sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) group by status;";
if ($DID=='Y')
	{
	$stmt="select count(*),status,sum(length_in_sec) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by status;";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$statuses_to_print = mysqli_num_rows($rslt);
$i=0;

######## GRAPHING #########
$max_calls=1;
$max_total_time=1;
$max_avg_time=1;
$max_callshr=1;
$graph_stats=array();
###########################

while ($i < $statuses_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$STATUScount =	$row[0];
	$RAWstatus =	$row[1];
	$r=0;  $foundstat=0;
	while ($r < $statcats_to_print)
		{
		if ( ($statcat_list[$RAWstatus] == "$vsc_id[$r]") and ($foundstat < 1) )
			{
			$vsc_count[$r] = ($vsc_count[$r] + $STATUScount);
			}
		$r++;
		}

	$TOTALcalls =	($TOTALcalls + $row[0]);
	$STATUSrate =	(MathZDC($STATUScount, MathZDC($TOTALsec, 3600)) );
	$STATUSrate =	sprintf("%.2f", $STATUSrate);

	$STATUShours =		sec_convert($row[2],'H'); 
	$STATUSavg_sec =	MathZDC($row[2], $STATUScount); 
	$STATUSavg =		sec_convert($STATUSavg_sec,'H'); 

	if ($row[0]>$max_calls) {$max_calls=$row[0];}
	if ($row[2]>$max_total_time) {$max_total_time=$row[2];}
	if ($STATUSavg_sec>$max_avg_time) {$max_avg_time=$STATUSavg_sec;}
	if ($STATUSrate>$max_callshr) {$max_callshr=$STATUSrate;}
	$graph_stats[$i][1]=$row[0];
	$graph_stats[$i][2]=$row[2];
	$graph_stats[$i][3]=$STATUSavg_sec;
	$graph_stats[$i][4]=$STATUSrate;


	$STATUScount =	sprintf("%10s", $row[0]);while(strlen($STATUScount)>10) {$STATUScount = substr("$STATUScount", 0, -1);}
	$status =	sprintf("%-6s", $row[1]);while(strlen($status)>6) {$status = substr("$status", 0, -1);}
	$STATUShours =	sprintf("%10s", $STATUShours);while(strlen($STATUShours)>10) {$STATUShours = substr("$STATUShours", 0, -1);}
	$STATUSavg =	sprintf("%8s", $STATUSavg);while(strlen($STATUSavg)>8) {$STATUSavg = substr("$STATUSavg", 0, -1);}
	$STATUSrate =	sprintf("%8s", $STATUSrate);while(strlen($STATUSrate)>8) {$STATUSrate = substr("$STATUSrate", 0, -1);}

	if ($non_latin < 1)
		{
		$status_name =	sprintf("%-20s", $statname_list[$RAWstatus]); 
		while(strlen($status_name)>20) {$status_name = substr("$status_name", 0, -1);}	
		$statcat =	sprintf("%-20s", $statcat_list[$RAWstatus]); 
		while(strlen($statcat)>20) {$statcat = substr("$statcat", 0, -1);}	
		}
	else
		{
		$status_name =	sprintf("%-60s", $statname_list[$RAWstatus]); 
		while(mb_strlen($status_name,'utf-8')>20) {$status_name = mb_substr("$status_name", 0, -1,'utf-8');}	
		$statcat =	sprintf("%-60s", $statcat_list[$RAWstatus]); 
		while(mb_strlen($statcat,'utf-8')>20) {$statcat = mb_substr("$statcat", 0, -1,'utf-8');}	
		}
	$graph_stats[$i][0]="$status - $status_name - $statcat";


	$ASCII_text.="| $status | $status_name | $statcat | $STATUScount | $STATUShours | $STATUSavg | $STATUSrate |\n";
	$CSV_text4.="\"$status\",\"$status_name\",\"$statcat\",\"$STATUScount\",\"$STATUShours\",\"$STATUSavg\",\"$STATUSrate\"\n";

	$i++;
	}

if ($TOTALcalls < 1)
	{
	$TOTALhours =	'0:00:00';
	$TOTALavg =		'0:00:00';
	$TOTALrate =	'0.00';
	}
else
	{
	$TOTALrate =	MathZDC($TOTALcalls, MathZDC($TOTALsec, 3600) );
	$TOTALrate =	sprintf("%.2f", $TOTALrate);

	$TOTALhours =		sec_convert($TOTALsec,'H'); 
	$TOTALavg_sec =		MathZDC($TOTALsec, $TOTALcalls);
	$TOTALavg =			sec_convert($TOTALavg_sec,'H'); 
	}
$TOTALcalls =	sprintf("%10s", $TOTALcalls);
$TOTALhours =	sprintf("%10s", $TOTALhours);while(strlen($TOTALhours)>10) {$TOTALhours = substr("$TOTALhours", 0, -1);}
$TOTALavg =	sprintf("%8s", $TOTALavg);while(strlen($TOTALavg)>8) {$TOTALavg = substr("$TOTALavg", 0, -1);}
$TOTALrate =	sprintf("%9s", $TOTALrate);while(strlen($TOTALrate)>9) {$TOTALrate = substr("$TOTALrate", 0, -1);}

$ASCII_text.="+--------+----------------------+----------------------+------------+------------+----------+-----------+\n";
$ASCII_text.="| "._QXZ("TOTAL:",52)." | $TOTALcalls | $TOTALhours | $TOTALavg | $TOTALrate |\n";
$ASCII_text.="+------------------------------------------------------+------------+------------+----------+-----------+\n";

#######

	$GRAPH_text="";	
	# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
	$multigraph_text="";
	$graph_id++;
	$graph_array=array("ACS_CSSCALLSdata|1|CALLS|integer|", "ACS_CSSTOTALTIMEdata|2|TOTAL TIME|time|", "ACS_CSSAVGTIMEdata|3|AVERAGE TIME|time|", "ACS_CSSCALLSHRdata|4|CALLS/HR|decimal|");
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
		$graphConstantsA=preg_replace('/,$/', '', $graphConstantsA)."],\n";
		$graphConstantsB=preg_replace('/,$/', '', $graphConstantsB)."],\n";
		$graphConstantsC=preg_replace('/,$/', '', $graphConstantsC)."],\n";
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
	$GRAPH_text.=$graphCanvas."<PRE>";

########


$CSV_text4.="\""._QXZ("TOTAL").":\",\"\",\"\",\"$TOTALcalls\",\"$TOTALhours\",\"$TOTALavg\",\"$TOTALrate\"\n";
if ($report_display_type=="HTML")
	{
	$MAIN.=$GRAPH_text;
	}
else
	{
	$MAIN.=$ASCII_text;
	}


##############################
#########  STATUS CATEGORY STATS

$ASCII_text="\n";
$ASCII_text.="---------- "._QXZ("CUSTOM STATUS CATEGORY STATS",34)." <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=5&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
$ASCII_text.="+----------------------+------------+--------------------------------+\n";
$ASCII_text.="| "._QXZ("CATEGORY",20)." | $rpt_type_verbiages     | "._QXZ("DESCRIPTION",30)." |\n";
$ASCII_text.="+----------------------+------------+--------------------------------+\n";

$CSV_text5.="\n\""._QXZ("CUSTOM STATUS CATEGORY STATS")."\"\n";
$CSV_text5.="\""._QXZ("CATEGORY")."\",\"$rpt_type_verbiages\",\""._QXZ("DESCRIPTION")."\"\n";

$TOTCATcalls=0;
$r=0;
while ($r < $statcats_to_print)
	{
	if ($vsc_id[$r] != 'UNDEFINED')
		{
		$TOTCATcalls = ($TOTCATcalls + $vsc_count[$r]);
		$category =	sprintf("%-20s", $vsc_id[$r]); while(strlen($category)>20) {$category = substr("$category", 0, -1);}
		$CATcount =	sprintf("%10s", $vsc_count[$r]); while(strlen($CATcount)>10) {$CATcount = substr("$CATcount", 0, -1);}
		$CATname =	sprintf("%-30s", $vsc_name[$r]); while(strlen($CATname)>30) {$CATname = substr("$CATname", 0, -1);}

		$ASCII_text.="| $category | $CATcount | $CATname |\n";
		$CSV_text5.="\"$category\",\"$CATcount\",\"$CATname\"\n";
		}

	$r++;
	}

$TOTCATcalls =	sprintf("%10s", $TOTCATcalls); while(strlen($TOTCATcalls)>10) {$TOTCATcalls = substr("$TOTCATcalls", 0, -1);}

$ASCII_text.="+----------------------+------------+--------------------------------+\n";
$ASCII_text.="| "._QXZ("TOTAL",20)." | $TOTCATcalls |\n";
$ASCII_text.="+----------------------+------------+\n";

$CSV_text5.="\""._QXZ("TOTAL")."\",\"$TOTCATcalls\"\n";

if ($report_display_type=="HTML") 
	{
	$graph_stats=array();
	$r=0; $i=0;
	$high_ct=0;
	while ($r < $statcats_to_print)
	{
	if ($vsc_id[$r] != 'UNDEFINED')
		{
		if ($vsc_count[$r]>$high_ct) {$high_ct=$vsc_count[$r];}
		$graph_stats[$i][0]=$vsc_count[$r];
		$graph_stats[$i][1]=$vsc_id[$r]." - ".$vsc_name[$r];
		$i++;
		}
		$r++;
	}
	
	$GRAPH_text="";	
	#########
	$graph_array=array("ACS_CSCSdata||CUSTOM STATUS CATEGORY STATS|integer|");
	$graph_id++;
	$default_graph="bar"; # Graph that is initally displayed when page loads
	include("graph_color_schemas.inc"); 

	$graph_totals_array=array();
	$graph_totals_rawdata=array();
	for ($q=0; $q<count($graph_array); $q++) {
		$graph_info=explode("|", $graph_array[$q]); 
		$current_graph_total=0;
		$dataset_name=$graph_info[0];
		$dataset_index=$graph_info[1]; 
		$graph_title=$graph_info[2]; 
		$dataset_type=$graph_info[3];
		if ($q==0) {$preload_dataset=$dataset_name;}  # Used below to load initial graph

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
			$labels.="\"".$graph_stats[$d][1]."\",";
			$data.="\"".$graph_stats[$d][0]."\",";
			$current_graph_total+=$graph_stats[$d][0];
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
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas."<PRE>";

	$MAIN.=$GRAPH_text;
	}
else 
	{
	$MAIN.=$ASCII_text;
	}


##############################
#########  CALL INITIAL QUEUE POSITION BREAKDOWN

$TOTALcalls = 0;

$ASCII_text="\n";
$ASCII_text.="---------- $rpt_type_verbiage "._QXZ("INITIAL QUEUE POSITION BREAKDOWN",38)." <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=6&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
$ASCII_text.="+-------------------------------------------------------------------------------------+------------+\n";
$ASCII_text.="|     1     2     3     4     5     6     7     8     9    10    15    20    25   +25 | "._QXZ("TOTAL",10)." |\n";
$ASCII_text.="+-------------------------------------------------------------------------------------+------------+\n";

$CSV_text6.="\n\"$rpt_type_verbiage "._QXZ("INITIAL QUEUE POSITION BREAKDOWN")."\"\n";
$CSV_text6.="\"\",\"1\",\"2\",\"3\",\"4\",\"5\",\"6\",\"7\",\"8\",\"9\",\"10\",\"15\",\"20\",\"25\",\"+25\",\""._QXZ("TOTAL")."\"\n";


$stmt="select count(*),queue_position from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) group by queue_position;";
if ($DID=='Y')
	{
	$stmt="select count(*),queue_position from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by queue_position;";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$positions_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $positions_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$TOTALcalls = ($TOTALcalls + $row[0]);

	if ( ($row[1] > 0) and ($row[1] <= 1) ) {$qp_1 = ($qp_1 + $row[0]);}
	if ( ($row[1] > 1) and ($row[1] <= 2) ) {$qp_2 = ($qp_2 + $row[0]);}
	if ( ($row[1] > 2) and ($row[1] <= 3) ) {$qp_3 = ($qp_3 + $row[0]);}
	if ( ($row[1] > 3) and ($row[1] <= 4) ) {$qp_4 = ($qp_4 + $row[0]);}
	if ( ($row[1] > 4) and ($row[1] <= 5) ) {$qp_5 = ($qp_5 + $row[0]);}
	if ( ($row[1] > 5) and ($row[1] <= 6) ) {$qp_6 = ($qp_6 + $row[0]);}
	if ( ($row[1] > 6) and ($row[1] <= 7) ) {$qp_7 = ($qp_7 + $row[0]);}
	if ( ($row[1] > 7) and ($row[1] <= 8) ) {$qp_8 = ($qp_8 + $row[0]);}
	if ( ($row[1] > 8) and ($row[1] <= 9) ) {$qp_9 = ($qp_9 + $row[0]);}
	if ( ($row[1] > 9) and ($row[1] <= 10) ) {$qp10 = ($qp10 + $row[0]);}
	if ( ($row[1] > 10) and ($row[1] <= 15) ) {$qp15 = ($qp15 + $row[0]);}
	if ( ($row[1] > 15) and ($row[1] <= 20) ) {$qp20 = ($qp20 + $row[0]);}
	if ( ($row[1] > 20) and ($row[1] <= 25) ) {$qp25 = ($qp25 + $row[0]);}
	if ($row[1] > 25) {$qp99 = ($qp99 + $row[0]);}
	$i++;
	}

$qp_1 =	sprintf("%5s", $qp_1);
$qp_2 =	sprintf("%5s", $qp_2);
$qp_3=	sprintf("%5s", $qp_3);
$qp_4 =	sprintf("%5s", $qp_4);
$qp_5 =	sprintf("%5s", $qp_5);
$qp_6 =	sprintf("%5s", $qp_6);
$qp_7 =	sprintf("%5s", $qp_7);
$qp_8 =	sprintf("%5s", $qp_8);
$qp_9 =	sprintf("%5s", $qp_9);
$qp10 =	sprintf("%5s", $qp10);
$qp15 =	sprintf("%5s", $qp15);
$qp20 =	sprintf("%5s", $qp20);
$qp25 =	sprintf("%5s", $qp25);
$qp99 =	sprintf("%5s", $qp99);

$TOTALcalls =		sprintf("%10s", $TOTALcalls);

$ASCII_text.="| $qp_1 $qp_2 $qp_3 $qp_4 $qp_5 $qp_6 $qp_7 $qp_8 $qp_9 $qp10 $qp15 $qp20 $qp25 $qp99 | $TOTALcalls |\n";
$ASCII_text.="+-------------------------------------------------------------------------------------+------------+\n";

$CSV_text6.="\"\",\"$qp_1\",\"$qp_2\",\"$qp_3\",\"$qp_4\",\"$qp_5\",\"$qp_6\",\"$qp_7\",\"$qp_8\",\"$qp_9\",\"$qp10\",\"$qp15\",\"$qp20\",\"$qp25\",\"$qp99\",\"$TOTALcalls\"\n";

if ($report_display_type=="HTML") 
	{
	$graph_stats=array();
	$queue_position=array();
	$stmt="select count(*),queue_position as qp from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) group by qp order by qp asc;";
	$ms_stmt="select queue_position from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) order by queue_position desc limit 1;"; 
	$mc_stmt="select count(*) as ct from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL) group by queue_position order by ct desc limit 1;";
	if ($DID=='Y')
		{
		$stmt="select count(*),queue_position as qp from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by qp order by qp asc;";
		$ms_stmt="select queue_position from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) order by queue_position desc limit 1;"; 
		$mc_stmt="select count(*) as ct from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) group by queue_position order by ct desc limit 1;";
		}
	if ($DB) {$MAIN.="$stmt\n$ms_stmt\n$mc_stmt\n";}
	$ms_rslt=mysql_to_mysqli($ms_stmt, $link);
	$ms_row=mysqli_fetch_row($ms_rslt);
	$max_position=$ms_row[0];
	$max_position=26;
	for ($i=0; $i<=$max_position; $i++) {
		$queue_position[$i]=0;
	}

	$mc_rslt=mysql_to_mysqli($mc_stmt, $link);
	$mc_row=mysqli_fetch_row($mc_rslt);
	$max_calls=$ms_row[0];
	if ($max_calls<26) {$max_calls=26;}
#	if ($max_calls<=30) {
		while ($maxcalls%5!=0) {
			$maxcalls++;
		}
/*
	} else if ($max_calls<=100) {
		while ($maxcalls%10!=0) {
			$maxcalls++;
		}
	} else if ($max_calls<=1000) {
		while ($maxcalls%50!=0) {
			$maxcalls++;
		}
	} else {
		while ($maxcalls%500!=0) {
			$maxcalls++;
		}
	}
*/
	$rslt=mysql_to_mysqli($stmt, $link);

	$over25=0;
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[1]<=25) {
			$queue_position[$row[1]]=$row[0];
		} else {
			$over25+=$row[0];
		}
	}
	$queue_position[26]=$over25;

	for ($i=0; $i<=$max_calls; $i++) {
		if ($i<=25) {
			if ($i%5==0) {$int=$i;} else {$int="";}
			$graph_stats[$i][0]=$queue_position[$i];
			$graph_stats[$i][1]=$i;
		} else {
			$graph_stats[$i][0]=$queue_position[$i];
			$graph_stats[$i][1]=26;
		}
	}

	$GRAPH_text="";	
	#########
	$graph_array=array("ACS_CATBdata||CALL INITIAL QUEUE POSITION BREAKDOWN|integer|");
	$graph_id++;
	$default_graph="line"; # Graph that is initally displayed when page loads
	include("graph_color_schemas.inc"); 

	$graph_totals_array=array();
	$graph_totals_rawdata=array();
	for ($q=0; $q<count($graph_array); $q++) {
		$graph_info=explode("|", $graph_array[$q]); 
		$current_graph_total=0;
		$dataset_name=$graph_info[0];
		$dataset_index=$graph_info[1]; 
		$graph_title=$graph_info[2]; 
		$dataset_type=$graph_info[3];
		if ($q==0) {$preload_dataset=$dataset_name;}  # Used below to load initial graph

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
			$labels.="\"".$graph_stats[$d][1]."\",";
			$data.="\"".$graph_stats[$d][0]."\",";
			$current_graph_total+=$graph_stats[$d][0];
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
	$graph_title=_QXZ("CALL INITIAL QUEUE POSITION BREAKDOWN");
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas."<PRE>";

	$MAIN.=$GRAPH_text;
	}
else
	{
	$MAIN.=$ASCII_text;
	}

##############################
#########  USER STATS

$TOTagents=0;
$TOTcalls=0;
$TOTtime=0;
$TOTavg=0;

$ASCII_text="\n";
$ASCII_text.="---------- "._QXZ("AGENT STATS",17)." <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=7&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
$ASCII_text.="+--------------------------+------------+------------+--------+\n";
$ASCII_text.="| "._QXZ("AGENT",24)." | $rpt_type_verbiages     | "._QXZ("TIME H:M:S",10)." |"._QXZ("AVERAGE",8)."|\n";
$ASCII_text.="+--------------------------+------------+------------+--------+\n";

$CSV_text7.="\n\""._QXZ("AGENT STATS")."\"\n";
$CSV_text7.="\""._QXZ("AGENT")."\",\"$rpt_type_verbiages\",\""._QXZ("TIME H:M:S")."\",\""._QXZ("AVERAGE")."\"\n";


$max_calls=1;
$max_timehms=1;
$max_average=1;
$graph_stats=array();

$stmt="select ".$vicidial_closer_log_table.".user,full_name,count(*),sum(length_in_sec-queue_seconds),avg(length_in_sec-queue_seconds) from ".$vicidial_closer_log_table.",vicidial_users where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id IN($group_SQL) and ".$vicidial_closer_log_table.".user is not null and length_in_sec is not null and ".$vicidial_closer_log_table.".user=vicidial_users.user group by ".$vicidial_closer_log_table.".user;";
if ($DID=='Y')
	{
	$stmt="select ".$vicidial_closer_log_table.".user,full_name,count(*),sum(length_in_sec-queue_seconds),avg(length_in_sec-queue_seconds) from ".$vicidial_closer_log_table.",vicidial_users where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL) and ".$vicidial_closer_log_table.".user is not null and length_in_sec is not null and ".$vicidial_closer_log_table.".user=vicidial_users.user group by ".$vicidial_closer_log_table.".user;";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$TOTcalls = ($TOTcalls + $row[2]);
	$TOTtime = ($TOTtime + $row[3]);

	$user =			sprintf("%-6s", $row[0]);
	if ($non_latin < 1)
		{
		$full_name =	sprintf("%-15s", $row[1]); while(strlen($full_name)>15) {$full_name = substr("$full_name", 0, -1);}	
		}
	else
		{
		$full_name =	sprintf("%-45s", $row[1]); while(mb_strlen($full_name,'utf-8')>15) {$full_name = mb_substr("$full_name", 0, -1,'utf-8');}	
		}
	$USERcalls =	sprintf("%10s", $row[2]);
	$USERtotTALK =	$row[3];
	$USERavgTALK =	$row[4];

	if ($row[2]>$max_calls) {$max_calls=$row[2];}
	if ($row[3]>$max_timehms) {$max_timehms=$row[3];}
	if ($row[4]>$max_average) {$max_average=$row[4];}
	$graph_stats[$i][0]="$row[0] - $row[1]";
	$graph_stats[$i][1]=$row[2];
	$graph_stats[$i][2]=$row[3];
	$graph_stats[$i][3]=$row[4];

	$USERtotTALK_MS =	sec_convert($USERtotTALK,'H'); 
	$USERavgTALK_MS =	sec_convert($USERavgTALK,'H'); 

	$USERtotTALK_MS =	sprintf("%9s", $USERtotTALK_MS);
	$USERavgTALK_MS =	sprintf("%6s", $USERavgTALK_MS);

	$ASCII_text.="| $user - $full_name | $USERcalls |  $USERtotTALK_MS | $USERavgTALK_MS |\n";
	$CSV_text7.="\"$user - $full_name\",\"$USERcalls\",\"$USERtotTALK_MS\",\"$USERavgTALK_MS\"\n";

	$i++;
	}

$TOTavg = MathZDC($TOTtime, $TOTcalls);
$TOTavg_MS =	sec_convert($TOTavg,'H'); 
$TOTavg =		sprintf("%6s", $TOTavg_MS);

$TOTtime_MS =	sec_convert($TOTtime,'H'); 
$TOTtime =		sprintf("%10s", $TOTtime_MS);

$TOTagents =		sprintf("%10s", $i);
$TOTcalls =			sprintf("%10s", $TOTcalls);
$TOTtime =			sprintf("%8s", $TOTtime);
$TOTavg =			sprintf("%6s", $TOTavg);

$ASCII_text.="+--------------------------+------------+------------+--------+\n";
$ASCII_text.="| "._QXZ("TOTAL Agents:",13)." $TOTagents | $TOTcalls | $TOTtime | $TOTavg |\n";
$ASCII_text.="+--------------------------+------------+------------+--------+\n";

$GRAPH_text="";	
# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
$multigraph_text="";
$graph_id++;
$graph_array=array("ACS_AGENTSTATSCALLSdata|1|AGENT STATS - CALLS|integer|", "ACS_AGENTSTATSTIMEdata|2|AGENT STATS - TIME H:M:S|time|", "ACS_AGENTSTATSAVERAGEdata|3|AGENT STATS - AVERAGE TIME|time|");
$default_graph="bar"; # Graph that is initally displayed when page loads
include("graph_color_schemas.inc"); 

$graph_totals_array=array();
$graph_totals_rawdata=array();
for ($q=0; $q<count($graph_array); $q++) {
	$graph_info=explode("|", $graph_array[$q]); 
	$current_graph_total=0;
	$dataset_name=$graph_info[0];
	$dataset_index=$graph_info[1]; 
	$graph_title=$graph_info[2]; 
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
	$graphConstantsA=preg_replace('/,$/', '', $graphConstantsA)."],\n";
	$graphConstantsB=preg_replace('/,$/', '', $graphConstantsB)."],\n";
	$graphConstantsC=preg_replace('/,$/', '', $graphConstantsC)."],\n";
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
include("graphcanvas.inc");
$HEADER.=$HTML_graph_head;
$GRAPH_text.=$graphCanvas;			


if ($report_display_type=="HTML")
	{
	$MAIN.=$GRAPH_text;
	}
else
	{
	$MAIN.=$ASCII_text;
	}

$CSV_text7.="\""._QXZ("TOTAL Agents").": $TOTagents\",\"$TOTcalls\",\"$TOTtime\",\"$TOTavg\"\n";

##############################
#########  TIME STATS

$MAIN.="\n";
$MAIN.="---------- "._QXZ("TIME STATS",16)." <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=9&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";

$CSV_text9.="\""._QXZ("TIME STATS")."\"\n\n";

$MAIN.="<FONT SIZE=0>\n";


##############################
#########  15-minute increment breakdowns of total calls and drops, then answered table
$BDansweredCALLS = 0;
$stmt="SELECT status,queue_seconds,UNIX_TIMESTAMP(call_date),call_date from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id IN($group_SQL);";
if ($DID=='Y')
	{
	$stmt="SELECT status,queue_seconds,UNIX_TIMESTAMP(call_date),call_date from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN($unid_SQL);";
	}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$calls_to_print = mysqli_num_rows($rslt);
$j=0;
$Cstatus=array();
$Cqueue=array();
$Cepoch=array();
$Cdate=array();
$Crem=array();
while ($j < $calls_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$Cstatus[$j] =	$row[0];
	$Cqueue[$j] =	$row[1];
	$Cepoch[$j] =	$row[2];
	$Cdate[$j] =	$row[3];
	$dst=date("I", strtotime($Cdate[$j]));
	$epoch_offset =		(($local_gmt + $dst) * 3600);
	$Crem[$j] = ( ($Cepoch[$j] + $epoch_offset) % 86400); # find the remainder(Modulus) of seconds since start of the day
#	$MAIN.="|$Cepoch[$j]|$Crem[$j]|$Cdate[$j]|\n";
	$j++;
	}

### Loop through all call records and gather stats for total call/drop report and answered report
$j=0;
$Ftotal=array();
$Fdrop=array();
$adB_0=array();
$adB_5=array();
$adB10=array();
$adB15=array();
$adB20=array();
$adB25=array();
$adB30=array();
$adB35=array();
$adB40=array();
$adB45=array();
$adB50=array();
$adB55=array();
$adB60=array();
$adB90=array();
$adB99=array();
while ($j < $calls_to_print)
	{
	$i=0; $sec=0; $sec_end=900;
	while ($i <= 96)
		{
		if ( ($Crem[$j] >= $sec) and ($Crem[$j] < $sec_end) ) 
			{
			$Ftotal[$i]++;
			if (preg_match('/DROP/',$Cstatus[$j])) {$Fdrop[$i]++;}
			if (!preg_match('/DROP|XDROP|HXFER|QVMAIL|HOLDTO|LIVE|QUEUE|TIMEOT|AFTHRS|NANQUE|IQNANQ|INBND|MAXCAL/',$Cstatus[$j]))
				{
				$BDansweredCALLS++;
				$Fanswer[$i]++;

				if ($Cqueue[$j] == 0)								{$adB_0[$i]++;}
				if ( ($Cqueue[$j] > 0) and ($Cqueue[$j] <= 5) )		{$adB_5[$i]++;}
				if ( ($Cqueue[$j] > 5) and ($Cqueue[$j] <= 10) )	{$adB10[$i]++;}
				if ( ($Cqueue[$j] > 10) and ($Cqueue[$j] <= 15) )	{$adB15[$i]++;}
				if ( ($Cqueue[$j] > 15) and ($Cqueue[$j] <= 20) )	{$adB20[$i]++;}
				if ( ($Cqueue[$j] > 20) and ($Cqueue[$j] <= 25) )	{$adB25[$i]++;}
				if ( ($Cqueue[$j] > 25) and ($Cqueue[$j] <= 30) )	{$adB30[$i]++;}
				if ( ($Cqueue[$j] > 30) and ($Cqueue[$j] <= 35) )	{$adB35[$i]++;}
				if ( ($Cqueue[$j] > 35) and ($Cqueue[$j] <= 40) )	{$adB40[$i]++;}
				if ( ($Cqueue[$j] > 40) and ($Cqueue[$j] <= 45) )	{$adB45[$i]++;}
				if ( ($Cqueue[$j] > 45) and ($Cqueue[$j] <= 50) )	{$adB50[$i]++;}
				if ( ($Cqueue[$j] > 50) and ($Cqueue[$j] <= 55) )	{$adB55[$i]++;}
				if ( ($Cqueue[$j] > 55) and ($Cqueue[$j] <= 60) )	{$adB60[$i]++;}
				if ( ($Cqueue[$j] > 60) and ($Cqueue[$j] <= 90) )	{$adB90[$i]++;}
				if ($Cqueue[$j] > 90)								{$adB99[$i]++;}
				}

			}
		$sec = ($sec + 900);
		$sec_end = ($sec_end + 900);
		$i++;
		}
	$j++;
	}	##### END going through all records







##### 15-minute total and drops graph
$hi_hour_count=0;
$last_full_record=0;
$i=0;
$h=0;
$hour_count=array();
$drop_count=array();
while ($i <= 96)
	{
	$hour_count[$i] = $Ftotal[$i];
	if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
	if ($hour_count[$i] > 0) {$last_full_record = $i;}
	$drop_count[$i] = $Fdrop[$i];
	$i++;
	}

$hour_multiplier = MathZDC(100, $hi_hour_count);

$ASCII_text="<!-- HICOUNT: $hi_hour_count|$hour_multiplier -->\n";
$ASCII_text.=_QXZ("GRAPH IN 15 MINUTE INCREMENTS OF TOTAL")." $rpt_type_verbiages "._QXZ("TAKEN INTO THIS IN-GROUP")."\n";


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


$ASCII_text.="+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n";
#$ASCII_text.="| HOUR | GRAPH IN 15 MINUTE INCREMENTS OF TOTAL INCOMING CALLS FOR THIS GROUP                                  | DROPS | TOTAL |\n";
$ASCII_text.="| "._QXZ("HOUR",4)." |$call_scale| "._QXZ("DROPS",5)." | "._QXZ("TOTAL",5)." |\n";
$ASCII_text.="+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n";

$CSV_text9.="\""._QXZ("HOUR")."\",\""._QXZ("DROPS")."\",\""._QXZ("TOTAL")."\"\n";

$max_calls=1;
$graph_stats=array();
$GRAPH_text="<table cellspacing='0' cellpadding='0'><caption align='top'>"._QXZ("GRAPH IN 15 MINUTE INCREMENTS OF TOTAL INCOMING CALLS FOR THIS GROUP")."</caption><tr><th class='thgraph' scope='col'>"._QXZ("HOUR")."</th><th class='thgraph' scope='col'>"._QXZ("DROPS")." <img src='./images/bar_blue.png' width='10' height='10'> / "._QXZ("CALLS")." <img src='./images/bar.png' width='10' height='10'></th></tr>";


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
		#$CSV_text9.="$hour$ZZ,";
		}
	if ($h == 1) {$time = "   15 ";}
	if ($h == 2) {$time = "   30 ";}
	if ($h == 3) {$time = "   45 ";}
	$Ghour_count = $hour_count[$i];
	if ($i >= $first_shift_record && $i<=$last_shift_record) # May need to use lower.  Ask Matt.
#	if ($i >= $first_shift_record && $i<$last_shift_record)
		{
		if ($Ghour_count < 1) 
			{
	#		if ( ($no_lines_yet) or ($i > $last_full_record) )
	#			{
	#			$do_nothing=1;
	#			}
	#		else
	#			{
				$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);
				$ASCII_text.="|$time|";
				$CSV_text9.="\"$time\",";
				$k=0;   while ($k <= 102) {$ASCII_text.=" ";   $k++;}
				$ASCII_text.="| $hour_count[$i] |\n";
				$CSV_text9.="\"0\",\"0\"\n";

	#			}
			}
		else
			{
			$no_lines_yet=0;
			$Xhour_count = ($Ghour_count * $hour_multiplier);
			$Yhour_count = (99 - $Xhour_count);

			$Gdrop_count = $drop_count[$i];
			if ($Gdrop_count < 1) 
				{
				$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);

				$ASCII_text.="|$time|<SPAN class=\"green\">";
				$CSV_text9.="\"$time\",";
				$k=0;   while ($k <= $Xhour_count) {$ASCII_text.="*";   $k++;   $char_counter++;}
				$ASCII_text.="*X</SPAN>";   $char_counter++;
				$k=0;   while ($k <= $Yhour_count) {$ASCII_text.=" ";   $k++;   $char_counter++;}
					while ($char_counter <= 101) {$ASCII_text.=" ";   $char_counter++;}
				$ASCII_text.="| 0     | $hour_count[$i] |\n";
				$CSV_text9.="\"0\",\"$hour_count[$i]\"\n";

				}
			else
				{
				$Xdrop_count = ($Gdrop_count * $hour_multiplier);

			#	if ($Xdrop_count >= $Xhour_count) {$Xdrop_count = ($Xdrop_count - 1);}

				$XXhour_count = ( ($Xhour_count - $Xdrop_count) - 1 );

				$hour_count[$i]+=0;
				$drop_count[$i]+=0;

				$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);
				$drop_count[$i] =	sprintf("%-5s", $drop_count[$i]);

				$ASCII_text.="|$time|<SPAN class=\"red\">";
				$CSV_text9.="\"$time\",";
				$k=0;   while ($k <= $Xdrop_count) {$ASCII_text.=">";   $k++;   $char_counter++;}
				$ASCII_text.="D</SPAN><SPAN class=\"green\">";   $char_counter++;
				$k=0;   while ($k <= $XXhour_count) {$ASCII_text.="*";   $k++;   $char_counter++;}
				$ASCII_text.="X</SPAN>";   $char_counter++;
				$k=0;   while ($k <= $Yhour_count) {$ASCII_text.=" ";   $k++;   $char_counter++;}
					while ($char_counter <= 102) {$ASCII_text.=" ";   $char_counter++;}
				$ASCII_text.="| $drop_count[$i] | $hour_count[$i] |\n";
				$CSV_text9.="\"$drop_count[$i]\",\"$hour_count[$i]\"\n";

				}
			}
		}	
	$graph_stats[$i][0]="$time";
	$graph_stats[$i][1]=trim($hour_count[$i]);
	$graph_stats[$i][2]=trim($drop_count[$i]);
	if (trim($hour_count[$i])>$max_calls) {$max_calls=trim($hour_count[$i]);}
	
	$i++;
	$h++;
	}

$ASCII_text.="+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n\n";

for ($d=0; $d<count($graph_stats); $d++) {
	if (strlen(trim($graph_stats[$d][0]))) {
		$graph_stats[$d][0]=preg_replace('/\s/', "", $graph_stats[$d][0]); 
		$GRAPH_text.="  <tr><td class='chart_td' width='50'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value' width='600' valign='bottom'>\n";
		if ($graph_stats[$d][1]>0) {
			$GRAPH_text.="<ul class='overlap_barGraph'><li class=\"p1\" style=\"height: 12px; left: 0px; width: ".round(MathZDC(600*$graph_stats[$d][1], $max_calls))."px\"><font style='background-color: #900'>".$graph_stats[$d][1]."</font></li>";
			if ($graph_stats[$d][2]>0) {
				$GRAPH_text.="<li class=\"p2\" style=\"height: 12px; left: 0px; width: ".round(MathZDC(600*$graph_stats[$d][2], $max_calls))."px\"><font style='background-color: #009'>".$graph_stats[$d][2]."</font></li>";
			}
			$GRAPH_text.="</ul>\n";
		} else {
			$GRAPH_text.="0";
		}
		$GRAPH_text.="</td></tr>\n";
	}
}
$GRAPH_text.="</table><BR/><BR/>";


if ($report_display_type=="HTML")
	{
	$MAIN.=$GRAPH_text;
	}
else
	{
	$MAIN.=$ASCII_text;
	}




##### Answered wait time breakdown
$MAIN.="\n";
$MAIN.="---------- $rpt_type_verbiage "._QXZ("ANSWERED TIME BREAKDOWN IN SECONDS", 40)." <a href=\"$PHP_SELF?DB=$DB&DID=$DID&query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&SUBMIT=$SUBMIT&file_download=8&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
$MAIN.="+------+-------------------------------------------------------------------------------------------+------------+\n";
$MAIN.="| "._QXZ("HOUR",4)." |     0     5    10    15    20    25    30    35    40    45    50    55    60    90   +90 | "._QXZ("TOTAL",10)." |\n";
$MAIN.="+------+-------------------------------------------------------------------------------------------+------------+\n";

$CSV_text8.="\n\"$rpt_type_verbiage "._QXZ("ANSWERED TIME BREAKDOWN IN SECONDS")."\"\n";
$CSV_text8.="\""._QXZ("HOUR")."\",\"0\",\"5\",\"10\",\"15\",\"20\",\"25\",\"30\",\"35\",\"40\",\"45\",\"50\",\"55\",\"60\",\"90\",\"+90\",\""._QXZ("TOTAL")."\"\n";

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
		$SQLtime = "$hour:$ZZ:00";
		$SQLtimeEND = "$hour:15:00";
		}
	if ($h == 1) {$time = "   15 ";   $SQLtime = "$hour:15:00";   $SQLtimeEND = "$hour:30:00";}
	if ($h == 2) {$time = "   30 ";   $SQLtime = "$hour:30:00";   $SQLtimeEND = "$hour:45:00";}
	if ($h == 3) 
		{
		$time = "   45 ";
		$SQLtime = "$hour:45:00";
		$hourEND = ($hour + 1);
		if ($hourEND < 10) {$hourEND = "0$hourEND";}
		if ($hourEND > 23) {$SQLtimeEND = "23:59:59";}
		else {$SQLtimeEND = "$hourEND:00:00";}
		}


	if (strlen($adB_0[$i]) < 1)  {$adB_0[$i]='-';}
	if (strlen($adB_5[$i]) < 1)  {$adB_5[$i]='-';}
	if (strlen($adB10[$i]) < 1)  {$adB10[$i]='-';}
	if (strlen($adB15[$i]) < 1)  {$adB15[$i]='-';}
	if (strlen($adB20[$i]) < 1)  {$adB20[$i]='-';}
	if (strlen($adB25[$i]) < 1)  {$adB25[$i]='-';}
	if (strlen($adB30[$i]) < 1)  {$adB30[$i]='-';}
	if (strlen($adB35[$i]) < 1)  {$adB35[$i]='-';}
	if (strlen($adB40[$i]) < 1)  {$adB40[$i]='-';}
	if (strlen($adB45[$i]) < 1)  {$adB45[$i]='-';}
	if (strlen($adB50[$i]) < 1)  {$adB50[$i]='-';}
	if (strlen($adB55[$i]) < 1)  {$adB55[$i]='-';}
	if (strlen($adB60[$i]) < 1)  {$adB60[$i]='-';}
	if (strlen($adB90[$i]) < 1)  {$adB90[$i]='-';}
	if (strlen($adB99[$i]) < 1)  {$adB99[$i]='-';}
	if (strlen($Fanswer[$i]) < 1)  {$Fanswer[$i]='0';}

	$adB_0[$i] = sprintf("%5s", $adB_0[$i]);
	$adB_5[$i] = sprintf("%5s", $adB_5[$i]);
	$adB10[$i] = sprintf("%5s", $adB10[$i]);
	$adB15[$i] = sprintf("%5s", $adB15[$i]);
	$adB20[$i] = sprintf("%5s", $adB20[$i]);
	$adB25[$i] = sprintf("%5s", $adB25[$i]);
	$adB30[$i] = sprintf("%5s", $adB30[$i]);
	$adB35[$i] = sprintf("%5s", $adB35[$i]);
	$adB40[$i] = sprintf("%5s", $adB40[$i]);
	$adB45[$i] = sprintf("%5s", $adB45[$i]);
	$adB50[$i] = sprintf("%5s", $adB50[$i]);
	$adB55[$i] = sprintf("%5s", $adB55[$i]);
	$adB60[$i] = sprintf("%5s", $adB60[$i]);
	$adB90[$i] = sprintf("%5s", $adB90[$i]);
	$adB99[$i] = sprintf("%5s", $adB99[$i]);
	$Fanswer[$i] = sprintf("%10s", $Fanswer[$i]);

	$MAIN.="|$time| $adB_0[$i] $adB_5[$i] $adB10[$i] $adB15[$i] $adB20[$i] $adB25[$i] $adB30[$i] $adB35[$i] $adB40[$i] $adB45[$i] $adB50[$i] $adB55[$i] $adB60[$i] $adB90[$i] $adB99[$i] | $Fanswer[$i] |\n";
	$CSV_text8.="\"$time\",\"$adB_0[$i]\",\"$adB_5[$i]\",\"$adB10[$i]\",\"$adB15[$i]\",\"$adB20[$i]\",\"$adB25[$i]\",\"$adB30[$i]\",\"$adB35[$i]\",\"$adB40[$i]\",\"$adB45[$i]\",\"$adB50[$i]\",\"$adB55[$i]\",\"$adB60[$i]\",\"$adB90[$i]\",\"$adB99[$i]\",\"$Fanswer[$i]\"\n";

	$i++;
	$h++;
	}

$BDansweredCALLS =		sprintf("%10s", $BDansweredCALLS);

$MAIN.="+------+-------------------------------------------------------------------------------------------+------------+\n";
$MAIN.="|"._QXZ("TOTALS",6)."|                                                                                           | $BDansweredCALLS |\n";
$MAIN.="+------+-------------------------------------------------------------------------------------------+------------+\n";

$CSV_text8.="\""._QXZ("TOTALS")."\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\",\"$BDansweredCALLS\"\n";


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
$MAIN.="</PRE>";
$MAIN.="</TD></TR></TABLE>";

$MAIN.="</BODY></HTML>";

if ($file_download>0) {
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_CLOSERstats_$US$FILE_TIME.csv";
	$CSV_var="CSV_text".$file_download;
	$CSV_text=preg_replace('/^\s+/', '', $$CSV_var);
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

} else {
	echo $HEADER;
	require("admin_header.php");
	echo $MAIN;
	$JS_text.="</script>\n";
	if ($report_display_type=="HTML")
		{
		echo $JS_text;
		}
}

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
