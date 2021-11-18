<?php 
# AST_agent_time_detail.php
# 
# Pulls time stats per agent selectable by campaign or user group
# should be most accurate agent stats of all of the reports
#
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 90522-0723 - First build
# 90908-1103 - Added DEAD time stats
# 100203-1147 - Added CUSTOMER time statistics
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 101207-1634 - Changed limits on seconds to 65000 from 30000 in vicidial_agent_log
# 101207-1719 - Fixed download file formatting bugs(issue 394)
# 101208-0320 - Fixed issue 404
# 110307-1057 - Added user_case setting in options.php
# 110623-0728 - Fixed user group selection bug
# 110708-1727 - Added options.php setting for time precision
# 111104-1248 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 121130-0957 - Fix for user group permissions issue #588
# 130414-0137 - Added report logging
# 220414-2111 - Added counts on parked(hold) calls
# 130610-0849 - Finalized changing of all ereg instances to preg
# 130621-0821 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130704-0945 - Fixed issue #675
# 130829-2012 - Changed to mysqli PHP functions
# 131122-0657 - Added options.php atdr_login_logout_user_link option
# 131129-1020 - Fixed division by zero bug in HTML mode
# 140108-0708 - Added webserver and hostname to report logging
# 140314-0852 - Fixed several division by zero bugs
# 140328-0005 - Converted division calculations to use MathZDC function
# 141113-1124 - Finalized adding QXZ translation to all admin files
# 141125-0950 - Changed AGENT TIME to LOGIN TIME for uniform headers with other reports, issue #427
# 141204-0548 - Fix for download headers, issue #805
# 141230-0929 - Added code for on-the-fly language translations display
# 150210-1356 - Added option to show time in seconds
# 150422-1643 - Added two new shift options
# 150516-1311 - Fixed Javascript element problem, Issue #857
# 150529-1921 - Sub statuses are now sorted in alphabetical order
# 151110-1612 - Changed download function to always export time with hours
# 151112-1335 - Added option to search archived tables
# 160121-2037 - Added report title header, default report format, cleaned up formatting
# 160301-2051 - Expanded full name to 25 characters on text display
# 160330-0649 - Fixed issue with names and non-latin setting
# 160411-1958 - Fixed issue with download field order
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170409-1542 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180502-2215 - Added new help display, added CONNECTED column
# 191013-0846 - Fixes for PHP7
# 210318-1558 - Added agent screen visibility stats
# 210324-2010 - Added agent visibility stats to HTML graphs
# 211115-1513 - Fix for case-mismatch on pause code statuses for multi-campaign selects
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$atdr_login_logout_user_link=0;
if (file_exists('options.php'))
	{
	require('options.php');
	}

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["show_parks"]))			{$show_parks=$_GET["show_parks"];}
	elseif (isset($_POST["show_parks"]))	{$show_parks=$_POST["show_parks"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["time_in_sec"]))			{$time_in_sec=$_GET["time_in_sec"];}
	elseif (isset($_POST["time_in_sec"]))	{$time_in_sec=$_POST["time_in_sec"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if (strlen($shift)<2) {$shift='ALL';}
if (strlen($stage)<2) {$stage='NAME';}

if ($search_archived_data=="checked") 
	{
	$agent_log_table="vicidial_agent_log_archive";
	$vicidial_agent_visibility_log_table="vicidial_agent_visibility_log_archive";
	$table_check_stmt="show tables like 'vicidial_timeclock_log_archive'";
	$table_check_rslt=mysql_to_mysqli($table_check_stmt, $link);
	if (mysqli_num_rows($table_check_rslt)>0) 
		{
		$timeclock_log_table="vicidial_timeclock_log_archive";
		}
	else
		{
		$timeclock_log_table="vicidial_timeclock_log";
		}
	} 
else 
	{
	$agent_log_table="vicidial_agent_log";
	$vicidial_agent_visibility_log_table="vicidial_agent_visibility_log";
	$timeclock_log_table="vicidial_timeclock_log";
	}

$report_name = 'Agent Time Detail';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

$user_case = '';
$TIME_agenttimedetail = '';
if (file_exists('options.php'))
	{
	require('options.php');
	}
if ($user_case == '1')
	{$userSQL = 'ucase(user)';}
if ($user_case == '2')
	{$userSQL = 'lcase(user)';}
if (strlen($userSQL)<2)
	{$userSQL = 'user';}
if (strlen($TIME_agenttimedetail)<1)
	{$TIME_agenttimedetail = 'H';}
if ($file_download == 1)
	{
	$TIME_agenttimedetail = 'HF';
	}
if ($time_in_sec)
	{
	$TIME_agenttimedetail = 'S';
	}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "|$stmt\n";}
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
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

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

$query_date = preg_replace('/[^-_0-9a-zA-Z]/', '', $query_date);
$end_date = preg_replace('/[^-_0-9a-zA-Z]/', '', $end_date);


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
if ($DB) {echo "|$stmt\n";}
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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
#	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

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

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($user_group)) {$user_group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}



$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
	}

$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "|$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	if (preg_match('/\-ALL/',$group_string) )
		{$group[$i] = $groups[$i];}
	$i++;
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	if ( (preg_match("/ $group[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{$group_SQL = ""; $park_log_SQL = "";}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$park_log_SQL = "and channel_group in ($group_SQL)";
	$group_SQL = "and campaign_id IN($group_SQL)";
	}

#for ($i=0; $i<count($user_group); $i++)
#	{
#	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1; $user_group="";}
#	}
$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "|$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$user_groups=array();
$i=0;
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	if ($all_user_groups) {$user_group[$i]=$row[0];}
	$i++;
	}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$user_group_SQL .= "'$user_group[$i]',";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$TCuser_group_SQL = $user_group_SQL;
	$user_group_SQL = "and ".$agent_log_table.".user_group IN($user_group_SQL)";
	$TCuser_group_SQL = "and user_group IN($TCuser_group_SQL)";
	}

if ($DB) {echo "|$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

$stmt="select distinct pause_code,pause_code_name from vicidial_pause_codes;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "|$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$pause_code[$i] =		"$row[0]";
	$pause_code_name[$i] =	"$row[1]";
	$i++;
	}

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date$groupQS$user_groupQS&shift=$shift&show_parks=$show_parks&time_in_sec=$time_in_sec&search_archived_data=$search_archived_data&report_display_type=$report_display_type&DB=$DB";

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.gif\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.gif\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

if ($file_download < 1)
	{
	?>

	<HTML>
	<HEAD>
	<STYLE type="text/css">
	<!--
	   .yellow {color: white; background-color: yellow}
	   .red {color: white; background-color: red}
	   .blue {color: white; background-color: blue}
	   .purple {color: white; background-color: purple}
	-->
	 </STYLE>

	<?php

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
	require("chart_button.php");
	echo "<script src='chart/Chart.js'></script>\n"; 
	echo "<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:99;'></div>";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\" id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";
	echo "<span style=\"position:absolute;left:3px;top:30px;z-index:19;\" id=agent_status_stats>\n";
	echo "<b>"._QXZ("$report_name")."</b> $NWB#agent_time_detail$NWE\n";
	echo "<PRE><FONT SIZE=2>";
	}

if ( (strlen($group[0]) < 1) or (strlen($user_group[0]) < 1) )
	{
#	echo "\n";
	echo _QXZ("PLEASE SELECT A CAMPAIGN OR USER GROUP AND DATE-TIME BELOW AND CLICK SUBMIT")."\n";
	echo _QXZ(" NOTE: stats taken from shift specified")."\n";
	}

else
	{
	if ($shift == 'TEST') 
		{
		$time_BEGIN = "09:45:00";  
		$time_END = "10:00:00";
		}
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
	if ($shift == '9AM-5PM') 
		{
		$time_BEGIN = "09:00:00";
		$time_END = "16:59:59";
		}
	if ($shift == '5PM-MIDNIGHT') 
		{
		$time_BEGIN = "17:00:00";
		$time_END = "23:59:59";
		}
	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";

	if ($file_download < 1)
		{
		$ASCII_text.=""._QXZ("$report_name",40)." $NOW_TIME ($db_source)\n";
		$ASCII_text.=_QXZ("Time range").": $query_date_BEGIN to $query_date_END\n\n";

		$GRAPH.=_QXZ("$report_name",40)." $NOW_TIME\n";
		$GRAPH.=_QXZ("Time range").": $query_date_BEGIN to $query_date_END\n\n";
		}
	else
		{
		$file_output .= _QXZ("$report_name",40)." $NOW_TIME\n";
		$file_output .= _QXZ("Time range").": $query_date_BEGIN to $query_date_END\n\n";
		}



	############################################################################
	##### BEGIN gathering information from the database section
	############################################################################

	### BEGIN gather user IDs and names for matching up later
	$stmt="select full_name,$userSQL from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user limit 100000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "|$stmt\n";}
	$users_to_print = mysqli_num_rows($rslt);
	$i=0;
	$graph_stats=array();
	$max_calls=1;
	$max_timeclock=1;
	$max_agenttime=1;
	$max_wait=1;
	$max_talk=1;
	$max_dispo=1;
	$max_pause=1;
	$max_dead=1;
	$max_customer=1;
	$TOT_HIDDEN=0;
	$TOT_VISIBLE=0;

	while ($i < $users_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$ULname[$i] =	$row[0];
		$ULuser[$i] =	$row[1];
		$i++;
		}
	### END gather user IDs and names for matching up later


	### BEGIN gather timeclock records per agent
	$stmt="select $userSQL,sum(login_sec) from ".$timeclock_log_table." where event IN('LOGIN','START') and event_date >= '$query_date_BEGIN' and event_date <= '$query_date_END' $TCuser_group_SQL group by user limit 10000000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$punches_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $punches_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$TCuser[$i] =	$row[0];
		$TCtime[$i] =	$row[1];
		$i++;
		}
	### END gather timeclock records per agent


	### BEGIN gather pause code information by user IDs
	$sub_statuses='-';
	$sub_statusesTXT='';
	$sub_statusesHEAD='';
	$sub_statusesHTML='';
	$sub_statusesFILE='';
	$sub_statusesARY=$MT;
	$sub_status_count=0;
	$PCusers='-';
	$PCusersARY=$MT;
	$PCuser_namesARY=$MT;
	$user_count=0;

	if ($show_parks) 
		{
		$park_HEADER=" "._QXZ("PARKS",8)." | "._QXZ("PARK TIME",10)." | "._QXZ("AVG PARK",10)." | "._QXZ("PARKS/CALL",10)." |";
		$park_HEADER_DIV="+----------+------------+------------+------------";
		$park_HEADER_CSV=",PARKS,PARK TIME,AVG PARK,PARKS/CALL";
		}

	# Displays park info - need to run regardless so HTML view will populate
	$park_array=array();
	$park_stmt="select user, count(*), sum(parked_sec) From park_log where parked_time <= '$query_date_END' and parked_time >= '$query_date_BEGIN' $park_log_SQL group by user";
	if ($DB) {$ASCII_text.= "$park_stmt\n";}
	$park_rslt=mysql_to_mysqli($park_stmt, $link);
	while ($park_row=mysqli_fetch_row($park_rslt)) 
		{
		$park_array[$park_row[0]][0]=$park_row[1];
		$park_array[$park_row[0]][1]=$park_row[2];
		}

	# Grab a list of sub_statuses sorted in order so they will appear in order in the resulting report - otherwise they could appear in any order
	$ss_stmt="select distinct sub_status from ".$agent_log_table." where event_time <= '$query_date_END' and event_time >= '$query_date_BEGIN' and pause_sec > 0 and pause_sec < 65000 $group_SQL $user_group_SQL order by sub_status asc limit 10000000;";
	$ss_rslt=mysql_to_mysqli($ss_stmt, $link);
	$j=0;
	while($ss_row=mysqli_fetch_row($ss_rslt)) 
		{
		$current_ss=$ss_row[0];

		$stmt="select $userSQL,sum(pause_sec),sub_status from ".$agent_log_table." where event_time <= '$query_date_END' and event_time >= '$query_date_BEGIN' and pause_sec > 0 and pause_sec < 65000 and sub_status='$current_ss' $group_SQL $user_group_SQL group by user,sub_status order by user,sub_status desc limit 10000000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		
		if ($DB) {$ASCII_text.= "$stmt\n";}
		$sub_subs_to_print = mysqli_num_rows($rslt);
		$subs_to_print+=$sub_subs_to_print;
		$i=0; 
		while ($i < $sub_subs_to_print)
			{
			$row=mysqli_fetch_row($rslt);
			$PCuser[$j] =		$row[0];
			$PCpause_sec[$j] =	$row[1];
			$sub_status[$j] =	$row[2];

			if (!preg_match("/\-$sub_status[$j]\-/i", $sub_statuses))
				{
				$sub_statusesTXT = sprintf("%10s", $sub_status[$j]);
				$sub_statusesHEAD .= "------------+";
				$sub_statusesHTML .= " $sub_statusesTXT |";
				$sub_statusesFILE .= ",$sub_status[$j]";
				$sub_statuses .= "$sub_status[$j]-";
				$sub_statusesARY[$sub_status_count] = $sub_status[$j];
				$sub_status_count++;

				$max_varname="max_".$sub_status[$j];
				$$max_varname=1;
				}
			if (!preg_match("/\-$PCuser[$j]\-/i", $PCusers))
				{
				$PCusers .= "$PCuser[$j]-";
				$PCusersARY[$user_count] = $PCuser[$j];
				$user_count++;
				}
			$i++;
			$j++;
			}
		}
	### END gather pause code information by user IDs


	##### BEGIN Gather all agent time records and parse through them in PHP to save on DB load
	$stmt="select $userSQL,wait_sec,talk_sec,dispo_sec,pause_sec,lead_id,status,dead_sec from ".$agent_log_table." where event_time <= '$query_date_END' and event_time >= '$query_date_BEGIN' $group_SQL $user_group_SQL limit 10000000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.= "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$i=0;
	$j=0;
	$k=0;
	$uc=0;
	while ($i < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$user =			$row[0];
		$wait =			$row[1];
		$talk =			$row[2];
		$dispo =		$row[3];
		$pause =		$row[4];
		$lead =			$row[5];
		$status =		$row[6];
		$dead =			$row[7];
		if ($wait > 65000) {$wait=0;}
		if ($talk > 65000) {$talk=0;}
		if ($dispo > 65000) {$dispo=0;}
		if ($pause > 65000) {$pause=0;}
		if ($dead > 65000) {$dead=0;}
		$customer =		($talk - $dead);
		$connected =		($talk + $wait);
		if ($customer < 1)
			{$customer=0;}
		$TOTwait =	($TOTwait + $wait);
		$TOTtalk =	($TOTtalk + $talk);
		$TOTdispo =	($TOTdispo + $dispo);
		$TOTpause =	($TOTpause + $pause);
		$TOTdead =	($TOTdead + $dead);
		$TOTcustomer =	($TOTcustomer + $customer);
		$TOTconnected =	($TOTconnected + $connected);
		$TOTALtime = ($TOTALtime + $pause + $dispo + $talk + $wait);
		if ( ($lead > 0) and ((!preg_match("/NULL/i",$status)) and (strlen($status) > 0)) ) {$TOTcalls++;}
		
		$user_found=0;
		if ($uc < 1) 
			{
			$Suser[$uc] = $user;
			$uc++;
			}
		$m=0;
		while ( ($m < $uc) and ($m < 50000) )
			{
			if ($user == "$Suser[$m]")
				{
				$user_found++;

				$Swait[$m] =	($Swait[$m] + $wait);
				$Stalk[$m] =	($Stalk[$m] + $talk);
				$Sdispo[$m] =	($Sdispo[$m] + $dispo);
				$Spause[$m] =	($Spause[$m] + $pause);
				$Sdead[$m] =	($Sdead[$m] + $dead);
				$Scustomer[$m] =	($Scustomer[$m] + $customer);
				$Sconnected[$m] =	($Sconnected[$m] + $connected);
				if ( ($lead > 0) and ((!preg_match("/NULL/i",$status)) and (strlen($status) > 0)) ) {$Scalls[$m]++;}
				}
			$m++;
			}
		if ($user_found < 1)
			{
			$Scalls[$uc] =	0;
			$Suser[$uc] =	$user;
			$Swait[$uc] =	$wait;
			$Stalk[$uc] =	$talk;
			$Sdispo[$uc] =	$dispo;
			$Spause[$uc] =	$pause;
			$Sdead[$uc] =	$dead;
			$Scustomer[$uc] =	$customer;
			$Sconnected[$uc] =	$connected;
			if ($lead > 0) {$Scalls[$uc]++;}
			$uc++;
			}

		$i++;
		}
	if ($DB) {echo "Done gathering $i records, analyzing...<BR>\n";}
	##### END Gather all agent time records and parse through them in PHP to save on DB load

	############################################################################
	##### END gathering information from the database section
	############################################################################




	##### BEGIN print the output to screen or put into file output variable
	if ($file_download < 1)
		{
		$ASCII_text.=_QXZ("AGENT TIME BREAKDOWN").":\n";
		$ASCII_text.="+---------------------------+----------+----------+------------+------------$park_HEADER_DIV+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+   +$sub_statusesHEAD   +-------------+-------------+\n";
		$ASCII_text.="| <a href=\"$LINKbase&stage=NAME\">"._QXZ("USER NAME",25)."</a> | <a href=\"$LINKbase&stage=ID\">"._QXZ("ID",8)."</a> | <a href=\"$LINKbase&stage=LEADS\">"._QXZ("CALLS",8)."</a> | <a href=\"$LINKbase&stage=TCLOCK\">"._QXZ("TIME CLOCK",10)."</a> | <a href=\"$LINKbase&stage=TIME\">"._QXZ("LOGIN TIME",10)."</a> |$park_HEADER "._QXZ("WAIT",10)." | "._QXZ("WAIT",8)." % | "._QXZ("TALK",10)." | "._QXZ("TALK TIME",9)." %| "._QXZ("DISPO",10)." | "._QXZ("DISPOTIME",9)." %| "._QXZ("PAUSE",10)." | "._QXZ("PAUSETIME",9)." %| "._QXZ("DEAD",10)." | "._QXZ("DEAD TIME",9)." %| "._QXZ("CUSTOMER",10)." | "._QXZ("CONNECTED",10)." |   |$sub_statusesHTML   | "._QXZ("VISIBLE",11)." | "._QXZ("HIDDEN",11)." |\n";
		$ASCII_text.="+---------------------------+----------+----------+------------+------------$park_HEADER_DIV+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+   +$sub_statusesHEAD   +-------------+-------------+\n";
		}
	else
		{
		$file_output .= _QXZ("USER").","._QXZ("ID").","._QXZ("CALLS").","._QXZ("TIME CLOCK").","._QXZ("LOGIN TIME")."$park_HEADER_CSV,"._QXZ("WAIT").","._QXZ("WAIT %").","._QXZ("TALK").","._QXZ("TALK TIME %").","._QXZ("DISPO").","._QXZ("DISPOTIME %").","._QXZ("PAUSE").","._QXZ("PAUSETIME %").","._QXZ("DEAD").","._QXZ("DEAD TIME %").","._QXZ("CUSTOMER").","._QXZ("CONNECTED")."$sub_statusesFILE,"._QXZ("VISIBLE").","._QXZ("HIDDEN")."\n";
		}
	##### END print the output to screen or put into file output variable





	############################################################################
	##### BEGIN formatting data for output section
	############################################################################

	##### BEGIN loop through each user formatting data for output
	$AUTOLOGOUTflag=0;
	$m=0;
	while ( ($m < $uc) and ($m < 50000) )
		{
		$SstatusesHTML='';
		$SstatusesFILE='';
		$Stime[$m] = ($Swait[$m] + $Stalk[$m] + $Sdispo[$m] + $Spause[$m]);
		$Swaitpct[$m]=MathZDC(100*$Swait[$m], $Stime[$m]);
		$Stalkpct[$m]=MathZDC(100*$Stalk[$m], $Stime[$m]);
		$Sdispopct[$m]=MathZDC(100*$Sdispo[$m], $Stime[$m]);
		$Spausepct[$m]=MathZDC(100*$Spause[$m], $Stime[$m]);
		$Sdeadpct[$m]=MathZDC(100*$Sdead[$m], $Stime[$m]);
		$RAWuser = $Suser[$m];
		$RAWcalls = $Scalls[$m];
		$RAWtimeSEC = $Stime[$m];

		if (trim($Scalls[$m])>$max_calls) {$max_calls=trim($Scalls[$m]);}
		if (trim($Stime[$m])>$max_agenttime) {$max_agenttime=trim($Stime[$m]);}
		if (trim($Swait[$m])>$max_wait) {$max_wait=trim($Swait[$m]);}
		if (trim($Stalk[$m])>$max_talk) {$max_talk=trim($Stalk[$m]);}
		if (trim($Sdispo[$m])>$max_dispo) {$max_dispo=trim($Sdispo[$m]);}
		if (trim($Spause[$m])>$max_pause) {$max_pause=trim($Spause[$m]);}
		if (trim($Sdead[$m])>$max_dead) {$max_dead=trim($Sdead[$m]);}
		if (trim($Scustomer[$m])>$max_customer) {$max_customer=trim($Scustomer[$m]);}
		if (trim($Sconnected[$m])>$max_connected) {$max_connected=trim($Sconnected[$m]);}
		if (trim($Swaitpct[$m])>$max_waitpct) {$max_waitpct=trim($Swaitpct[$m]);}
		if (trim($Stalkpct[$m])>$max_talkpct) {$max_talkpct=trim($Stalkpct[$m]);}
		if (trim($Sdispopct[$m])>$max_dispopct) {$max_dispopct=trim($Sdispopct[$m]);}
		if (trim($Spausepct[$m])>$max_pausepct) {$max_pausepct=trim($Spausepct[$m]);}
		if (trim($Sdeadpct[$m])>$max_deadpct) {$max_deadpct=trim($Sdeadpct[$m]);}
		$graph_stats[$m][1]=trim($Scalls[$m]);
		$graph_stats[$m][3]=trim($Stime[$m]);
		$graph_stats[$m][4]=trim($Swait[$m]);
		$graph_stats[$m][5]=trim($Stalk[$m]);
		$graph_stats[$m][6]=trim($Sdispo[$m]);
		$graph_stats[$m][7]=trim($Spause[$m]);
		$graph_stats[$m][8]=trim($Sdead[$m]);
		$graph_stats[$m][9]=trim($Scustomer[$m]);
		$graph_stats[$m][10]=trim($Sconnected[$m]);
		$graph_stats[$m][11]=trim(sprintf("%01.2f", $Stalkpct[$m]));
		$graph_stats[$m][12]=trim(sprintf("%01.2f", $Sdispopct[$m]));
		$graph_stats[$m][13]=trim(sprintf("%01.2f", $Spausepct[$m]));
		$graph_stats[$m][14]=trim(sprintf("%01.2f", $Sdeadpct[$m]));
		$graph_stats[$m][15]=trim(sprintf("%01.2f", $Swaitpct[$m]));

		$Swaitpct[$m]=	sprintf("%01.2f", $Swaitpct[$m]);
		$Stalkpct[$m]=	sprintf("%01.2f", $Stalkpct[$m]);
		$Sdispopct[$m]=	sprintf("%01.2f", $Sdispopct[$m]);
		$Spausepct[$m]=	sprintf("%01.2f", $Spausepct[$m]);
		$Sdeadpct[$m]=	sprintf("%01.2f", $Sdeadpct[$m]);
		$Swait[$m]=		sec_convert($Swait[$m],$TIME_agenttimedetail);
		$Stalk[$m]=		sec_convert($Stalk[$m],$TIME_agenttimedetail);
		$Sdispo[$m]=	sec_convert($Sdispo[$m],$TIME_agenttimedetail);
		$Spause[$m]=	sec_convert($Spause[$m],$TIME_agenttimedetail);
		$Sdead[$m]=	sec_convert($Sdead[$m],$TIME_agenttimedetail);
		$Scustomer[$m]=	sec_convert($Scustomer[$m],$TIME_agenttimedetail);
		$Sconnected[$m]=	sec_convert($Sconnected[$m],$TIME_agenttimedetail);
		$Stime[$m]=		sec_convert($Stime[$m],$TIME_agenttimedetail);

		$RAWtime = $Stime[$m];
		$RAWwait = $Swait[$m];
		$RAWtalk = $Stalk[$m];
		$RAWdispo = $Sdispo[$m];
		$RAWpause = $Spause[$m];
		$RAWdead = $Sdead[$m];
		$RAWcustomer = $Scustomer[$m];
		$RAWconnected = $Sconnected[$m];
		$RAWwaitpct = $Swaitpct[$m];
		$RAWtalkpct = $Stalkpct[$m];
		$RAWdispopct = $Sdispopct[$m];
		$RAWpausepct = $Spausepct[$m];
		$RAWdeadpct = $Sdeadpct[$m];

		$n=0;
		$user_name_found=0;
		while ($n < $users_to_print)
			{
			if ($Suser[$m] == "$ULuser[$n]")
				{
				$user_name_found++;
				$RAWname = $ULname[$n];
				$Sname[$m] = $ULname[$n];
				}
			$n++;
			}
		if ($user_name_found < 1)
			{
			$RAWname =		"NOT IN SYSTEM";
			$Sname[$m] =	$RAWname;
			}

		$n=0;
		$punches_found=0;
		while ($n < $punches_to_print)
			{
			if ($Suser[$m] == "$TCuser[$n]")
				{
				$punches_found++;
				$RAWtimeTCsec =		$TCtime[$n];
				$TOTtimeTC =		($TOTtimeTC + $TCtime[$n]);

				if (trim($RAWtimeTCsec)>$max_timeclock) {$max_timeclock=trim($RAWtimeTCsec);}
				$graph_stats[$m][2]=trim($RAWtimeTCsec);

				$StimeTC[$m]=		sec_convert($TCtime[$n],$TIME_agenttimedetail);
				$RAWtimeTC =		$StimeTC[$m];
				$StimeTC[$m] =		sprintf("%10s", $StimeTC[$m]);
				}
			$n++;
			}
		if ($punches_found < 1)
			{
			$RAWtimeTCsec =		"0";

			$graph_stats[$m][2]=0;
			
			$StimeTC[$m] =		"0:00"; 
			if ($TIME_agenttimedetail == 'HF')
				{$StimeTC[$m] =		"0:00:00";}
			if ($TIME_agenttimedetail == 'S')
				{$StimeTC[$m] =		"0";}
			$RAWtimeTC =		$StimeTC[$m];
			$StimeTC[$m] =		sprintf("%10s", $StimeTC[$m]);
			}

		### Check if the user had an AUTOLOGOUT timeclock event during the time period
		$TCuserAUTOLOGOUT = ' ';
		$stmt="select count(*) from ".$timeclock_log_table." where event='AUTOLOGOUT' and user='$Suser[$m]' and event_date >= '$query_date_BEGIN' and event_date <= '$query_date_END';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$autologout_results = mysqli_num_rows($rslt);
		if ($autologout_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$TCuserAUTOLOGOUT =	'*';
				$AUTOLOGOUTflag++;
				}
			}

		### Gather agent screen visibility data from during the time period
		$HIDDEN_sec=0;   $VISIBLE_sec=0;
		$stmt="select count(*),sum(length_in_sec),visibility from ".$vicidial_agent_visibility_log_table." where user='$Suser[$m]' and db_time >= '$query_date_BEGIN' and db_time <= '$query_date_END' and visibility IN('HIDDEN','VISIBLE') group by visibility;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$visibility_results = mysqli_num_rows($rslt);
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
		$USER_HIDDEN_sec =		sec_convert($HIDDEN_sec,$TIME_agenttimedetail);
		$pfUSER_HIDDEN_sec =	sprintf("%11s", $USER_HIDDEN_sec);
		$USER_VISIBLE_sec =		sec_convert($VISIBLE_sec,$TIME_agenttimedetail);
		$pfUSER_VISIBLE_sec =	sprintf("%11s", $USER_VISIBLE_sec);


		### BEGIN loop through each status ###
		$n=0;
		$sub_status_array=array();
		while ($n < $sub_status_count)
			{
			$Sstatus=$sub_statusesARY[$n];
			$SstatusTXT='';
			$varname=$Sstatus."_graph";
			$$varname=$graph_header."<th class='thgraph' scope='col'>$Sstatus</th></tr>";
			array_push($sub_status_array, "ATD_SUBSTATUS".$n."data|$n|$Sstatus|sub_status_array");
			$max_varname="max_".$Sstatus;
			### BEGIN loop through each stat line ###
			$i=0; $status_found=0;
			while ( ($i < $subs_to_print) and ($status_found < 1) )
				{
				if ( ($Suser[$m]=="$PCuser[$i]") and (preg_match("/^$sub_status[$i]$/i",$Sstatus)) )
					{
					$USERcodePAUSE_MS =		sec_convert($PCpause_sec[$i],$TIME_agenttimedetail);
					if (strlen($USERcodePAUSE_MS)<1) {$USERcodePAUSE_MS='0';}
					$pfUSERcodePAUSE_MS =	sprintf("%10s", $USERcodePAUSE_MS);
					
					if ($PCpause_sec[$i]>$$max_varname) {$$max_varname=$PCpause_sec[$i];}
					$graph_stats_sub[$m][$n]=$PCpause_sec[$i];					

					$n1=($n+21); # THIS IS FOR THE GRAPHING FURTHER DOWN; COULDN'T THINK OF A BETTER WAY TO DO THIS.
					$graph_stats[$m][$n1]=$PCpause_sec[$i];					

					$SstatusTXT = sprintf("%10s", $pfUSERcodePAUSE_MS);
					$SstatusesHTML .= " $SstatusTXT |";
					$SstatusesFILE .= ",$USERcodePAUSE_MS";
					$status_found++;
					}
				$i++;
				}
			if ($status_found < 1)
				{
				if ($TIME_agenttimedetail == 'HF')
					{
					$SstatusesHTML .= "    0:00:00 |";
					$SstatusesFILE .= ",0:00:00";
					}
				else
					{
					if ($TIME_agenttimedetail == 'S')
						{
						$SstatusesHTML .= "          0 |";
						$SstatusesFILE .= ",0";
						}
					else
						{
						$SstatusesHTML .= "       0:00 |";
						$SstatusesFILE .= ",0:00";
						}
					}
				}
			### END loop through each stat line ###
			$n++;
			}
		### END loop through each status ###


		$Swaitpct[$m]=		sprintf("%9s", $Swaitpct[$m])."%";
		$Stalkpct[$m]=		sprintf("%9s", $Stalkpct[$m])."%";
		$Sdispopct[$m]=		sprintf("%9s", $Sdispopct[$m])."%";
		$Spausepct[$m]=		sprintf("%9s", $Spausepct[$m])."%";
		$Sdeadpct[$m]=		sprintf("%9s", $Sdeadpct[$m])."%";
		$Swait[$m]=		sprintf("%10s", $Swait[$m]); 
		$Stalk[$m]=		sprintf("%10s", $Stalk[$m]); 
		$Sdispo[$m]=	sprintf("%10s", $Sdispo[$m]); 
		$Spause[$m]=	sprintf("%10s", $Spause[$m]); 
		$Sdead[$m]=		sprintf("%10s", $Sdead[$m]); 
		$Scustomer[$m]=		sprintf("%10s", $Scustomer[$m]);
		$Sconnected[$m]=		sprintf("%10s", $Sconnected[$m]);
		$Scalls[$m]=	sprintf("%8s", $Scalls[$m]); 
		$Stime[$m]=		sprintf("%10s", $Stime[$m]); 

		# PARK INFO
		if ($show_parks) 
			{
			$user_holds=$park_array[$Suser[$m]][0]+0;
			$total_hold_time=$park_array[$Suser[$m]][1]+0;
			$TOTuser_holds+=$park_array[$Suser[$m]][0];
			$TOTtotal_hold_time+=$park_array[$Suser[$m]][1];

			$avg_hold_time=round(MathZDC($total_hold_time, $user_holds));
			$user_hpc=sprintf("%.2f", MathZDC($user_holds, $Scalls[$m]));

			$total_hold_time=sec_convert($total_hold_time,$TIME_agenttimedetail);
			$avg_hold_time=sec_convert($avg_hold_time,$TIME_agenttimedetail);
			$park_AGENT_INFO_CSV="$user_holds,$total_hold_time,$avg_hold_time,$user_hpc,";

			$user_holds=	sprintf("%8s", $user_holds); 
			$total_hold_time=	sprintf("%10s", $total_hold_time); 
			$avg_hold_time=	sprintf("%10s", $avg_hold_time); 
			$user_hpc=	sprintf("%10s", $user_hpc); 
			$park_AGENT_INFO=" $user_holds | $total_hold_time | $avg_hold_time | $user_hpc |";
			}

		if (trim($user_holds)>$max_user_holds) {$max_user_holds=trim($user_holds);}
		if (trim($park_array[$Suser[$m]][1]+0)>$max_total_hold_time) {$max_total_hold_time=trim($park_array[$Suser[$m]][1]+0);}
		if (trim(round(MathZDC(($park_array[$Suser[$m]][1]+0), $user_holds)))>$max_avg_hold_time) {$max_avg_hold_time=trim(round(MathZDC(($park_array[$Suser[$m]][1]+0), $user_holds)));}
		if (trim($user_hpc)>$max_user_hpc) {$max_user_hpc=trim($user_hpc);}
		$graph_stats[$m][16]=trim($user_holds);
		$graph_stats[$m][17]=trim($park_array[$Suser[$m]][1]+0);
		$graph_stats[$m][18]=trim(round(MathZDC(($park_array[$Suser[$m]][1]+0), $user_holds)));
		$graph_stats[$m][19]=trim($user_hpc);
		$graph_stats[$m][20]=trim($VISIBLE_sec);
		$graph_stats[$m][21]=trim($HIDDEN_sec);

		if ($file_download<1) 
			{
			if ($non_latin < 1)
				{
				$Sname[$m]=	sprintf("%-25s", $Sname[$m]); 
				while(strlen($Sname[$m])>25) {$Sname[$m] = substr("$Sname[$m]", 0, -1);}
				$Suser[$m] =		sprintf("%-8s", $Suser[$m]);
				while(strlen($Suser[$m])>8) {$Suser[$m] = substr("$Suser[$m]", 0, -1);}
				}
			else
				{	
				$Sname[$m]=	sprintf("%-75s", $Sname[$m]); 
				while(mb_strlen($Sname[$m],'utf-8')>25) {$Sname[$m] = mb_substr("$Sname[$m]", 0, -1,'utf-8');}
				$Suser[$m] =	sprintf("%-24s", $Suser[$m]);
				while(mb_strlen($Suser[$m],'utf-8')>8) {$Suser[$m] = mb_substr("$Suser[$m]", 0, -1,'utf-8');}
				}
			}

		if ($file_download < 1)
			{
			if ($atdr_login_logout_user_link > 0)
				{
				$Toutput = "| $Sname[$m] | <a href=\"./user_stats.php?pause_code_rpt=1&begin_date=$query_date&end_date=$end_date&user=$RAWuser\">$Suser[$m]</a> | $Scalls[$m] | $StimeTC[$m]$TCuserAUTOLOGOUT| $Stime[$m] |$park_AGENT_INFO $Swait[$m] | $Swaitpct[$m] | $Stalk[$m] | $Stalkpct[$m] | $Sdispo[$m] | $Sdispopct[$m] | $Spause[$m] | $Spausepct[$m] | $Sdead[$m] | $Sdeadpct[$m] | $Scustomer[$m] | $Sconnected[$m] |   |$SstatusesHTML   | $pfUSER_VISIBLE_sec | $pfUSER_HIDDEN_sec |\n";
				}
			else
				{
				$Toutput = "| $Sname[$m] | <a href=\"./user_stats.php?user=$RAWuser&begin_date=$query_date&end_date\">$Suser[$m]</a> | $Scalls[$m] | $StimeTC[$m]$TCuserAUTOLOGOUT| $Stime[$m] |$park_AGENT_INFO $Swait[$m] | $Swaitpct[$m] | $Stalk[$m] | $Stalkpct[$m] | $Sdispo[$m] | $Sdispopct[$m] | $Spause[$m] | $Spausepct[$m] | $Sdead[$m] | $Sdeadpct[$m] | $Scustomer[$m] | $Sconnected[$m] |   |$SstatusesHTML   | $pfUSER_VISIBLE_sec | $pfUSER_HIDDEN_sec |\n";
				}
				
			$graph_stats[$m][0]=trim("$Suser[$m] - $Sname[$m]");
			$user_IDs[$m]=$Suser[$m];
			}
		else
			{
			if (strlen($RAWtime)<1) {$RAWtime='0';}
			if (strlen($RAWwait)<1) {$RAWwait='0';}
			if (strlen($RAWwaitpct)<0) {$RAWwaitpct='0.0%';}
			if (strlen($RAWtalk)<1) {$RAWtalk='0';}
			if (strlen($RAWtalkpct)<0) {$RAWtalkpct='0.0%';}
			if (strlen($RAWdispo)<1) {$RAWdispo='0';}
			if (strlen($RAWdispopct)<0) {$RAWdispopct='0.0%';}
			if (strlen($RAWpause)<1) {$RAWpause='0';}
			if (strlen($RAWpausepct)<0) {$RAWpausepct='0.0%';}
			if (strlen($RAWdead)<1) {$RAWdead='0';}
			if (strlen($RAWdeadpct)<0) {$RAWdeadpct='0.0%';}
			if (strlen($RAWcustomer)<1) {$RAWcustomer='0';}
			if (strlen($RAWconnected)<1) {$RAWconnected='0';}
			$user_holds=trim($user_holds); 
			$total_hold_time=trim($total_hold_time); 
			$avg_hold_time=trim($avg_hold_time); 
			$user_hpc=trim($user_hpc); 

			$fileToutput = "$RAWname,$RAWuser,$RAWcalls,$RAWtimeTC,$RAWtime,$park_AGENT_INFO_CSV$RAWwait,$RAWwaitpct %,$RAWtalk,$RAWtalkpct %,$RAWdispo,$RAWdispopct %,$RAWpause,$RAWpausepct %,$RAWdead,$RAWdeadpct %,$RAWcustomer,$RAWconnected$SstatusesFILE,$USER_VISIBLE_sec,$USER_HIDDEN_sec\n";
			}

		$TOPsorted_output[$m] = $Toutput;
		$TOPsorted_outputFILE[$m] = $fileToutput;

		if ($stage == 'NAME')
			{
			$TOPsort[$m] =	'' . sprintf("%020s", $RAWname) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'ID')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWuser) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'LEADS')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWcalls) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'TIME')
			{
			$TOPsort[$m] =	'' . sprintf("%010s", $RAWtimeSEC) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWtimeSEC;
			}
		if ($stage == 'TCLOCK')
			{
			$TOPsort[$m] =	'' . sprintf("%010s", $RAWtimeTCsec) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWtimeTCsec;
			}
		if (!preg_match('/NAME|ID|TIME|LEADS|TCLOCK/',$stage))
			if ($file_download < 1)
				{$ASCII_text.="$Toutput";}
			else
				{$file_output .= "$fileToutput";}

		if ($TOPsortMAX < $TOPsortTALLY[$m]) {$TOPsortMAX = $TOPsortTALLY[$m];}

#		echo "$Suser[$m]|$Sname[$m]|$Swait[$m]|$Stalk[$m]|$Sdispo[$m]|$Spause[$m]|$Scalls[$m]\n";
		$m++;
		}
	##### END loop through each user formatting data for output


	$TOT_AGENTS = $m;
	$hTOT_AGENTS = sprintf("%5s", $TOT_AGENTS);
	$k=$m;

	if ($DB) {echo "Done analyzing...   $TOTwait|$TOTtalk|$TOTdispo|$TOTpause|$TOTdead|$TOTcustomer|$TOTconnected|$TOTALtime|$TOTcalls|$uc|<BR>\n";}


	### BEGIN sort through output to display properly ###
	if ( ($TOT_AGENTS > 0) and (preg_match('/NAME|ID|TIME|LEADS|TCLOCK/',$stage)) )
		{
		if (preg_match('/ID/',$stage))
			{sort($TOPsort, SORT_NUMERIC);}
		if (preg_match('/TIME|LEADS|TCLOCK/',$stage))
			{rsort($TOPsort, SORT_NUMERIC);}
		if (preg_match('/NAME/',$stage))
			{rsort($TOPsort, SORT_STRING);}

		$m=0;
		while ($m < $k)
			{
			$sort_split = explode("-----",$TOPsort[$m]);
			$i = $sort_split[1];
			$sort_order[$m] = "$i";
			if ($file_download < 1)
				{$ASCII_text.="$TOPsorted_output[$i]";}
			else
				{$file_output .= "$TOPsorted_outputFILE[$i]";}
			$m++;
			}
		}
	### END sort through output to display properly ###

	############################################################################
	##### END formatting data for output section
	############################################################################




	############################################################################
	##### BEGIN last line totals output section
	############################################################################
	$SUMstatusesHTML='';
	$SUMstatusesFILE='';
	$TOTtotPAUSE=0;
	$n=0;
	while ($n < $sub_status_count)
		{
		$Scalls=0;
		$Sstatus=$sub_statusesARY[$n];
		$SUMstatusTXT='';
		$total_var=$Sstatus."_total";
		### BEGIN loop through each stat line ###
		$i=0; $status_found=0;
		while ($i < $subs_to_print)
			{
			if (preg_match("/^$sub_status[$i]$/i",$Sstatus))
				{
				$Scalls =		($Scalls + $PCpause_sec[$i]);
				$status_found++;
				}
			$i++;
			}
		### END loop through each stat line ###
		if ($status_found < 1)
			{
			$SUMstatusesHTML .= "          0 |";
			$$total_var="0";
			}
		else
			{
			$TOTtotPAUSE = ($TOTtotPAUSE + $Scalls);

			$USERsumstatPAUSE_MS =		sec_convert($Scalls,$TIME_agenttimedetail);
			$pfUSERsumstatPAUSE_MS =	sprintf("%11s", $USERsumstatPAUSE_MS);
			$$total_var="$pfUSERsumstatPAUSE_MS";

			$SUMstatusTXT = sprintf("%10s", $pfUSERsumstatPAUSE_MS);
			$SUMstatusesHTML .= "$SUMstatusTXT |";
			$SUMstatusesFILE .= ",$USERsumstatPAUSE_MS";
			}
		$n++;
		}
	### END loop through each status ###

	### call function to calculate and print dialable leads
	$TOTwaitpct=sprintf("%01.2f", MathZDC(100*$TOTwait, $TOTALtime));
	$TOTtalkpct=sprintf("%01.2f", MathZDC(100*$TOTtalk, $TOTALtime));
	$TOTdispopct=sprintf("%01.2f", MathZDC(100*$TOTdispo, $TOTALtime));
	$TOTpausepct=sprintf("%01.2f", MathZDC(100*$TOTpause, $TOTALtime));
	$TOTdeadpct=sprintf("%01.2f", MathZDC(100*$TOTdead, $TOTALtime));

	$TOTwait = sec_convert($TOTwait,$TIME_agenttimedetail);
	$TOTtalk = sec_convert($TOTtalk,$TIME_agenttimedetail);
	$TOTdispo = sec_convert($TOTdispo,$TIME_agenttimedetail);
	$TOTpause = sec_convert($TOTpause,$TIME_agenttimedetail);
	$TOTdead = sec_convert($TOTdead,$TIME_agenttimedetail);
	$TOTcustomer = sec_convert($TOTcustomer,$TIME_agenttimedetail);
	$TOTconnected = sec_convert($TOTconnected,$TIME_agenttimedetail);
	$TOTALtime = sec_convert($TOTALtime,$TIME_agenttimedetail);
	$TOTtimeTC = sec_convert($TOTtimeTC,$TIME_agenttimedetail);

	if ($show_parks) 
		{
		$TOTavg_hold_time=round(MathZDC($TOTtotal_hold_time, $TOTuser_holds));
		$TOTuser_hpc=sprintf("%.2f", MathZDC($TOTuser_holds, $TOTcalls));
		$TOTtotal_hold_time=sec_convert($TOTtotal_hold_time,$TIME_agenttimedetail);
		$TOTavg_hold_time=sec_convert($TOTavg_hold_time,$TIME_agenttimedetail);
		$hTOTuser_holds=	sprintf("%8s", $TOTuser_holds); 
		$hTOTtotal_hold_time=	sprintf("%10s", $TOTtotal_hold_time); 
		$hTOTavg_hold_time=	sprintf("%10s", $TOTavg_hold_time); 
		$hTOTuser_hpc=	sprintf("%10s", $TOTuser_hpc); 
		$park_TOTALS=" $hTOTuser_holds | $hTOTtotal_hold_time | $hTOTavg_hold_time | $hTOTuser_hpc |";
		$park_TOTALS_CSV="$TOTuser_holds,$TOTtotal_hold_time,$TOTavg_hold_time,$TOTuser_hpc,";
		}

	$hTOTwaitpct =	sprintf("%10s", $TOTwaitpct)."%";
	$hTOTtalkpct =	sprintf("%10s", $TOTtalkpct)."%";
	$hTOTdispopct =	sprintf("%10s", $TOTdispopct)."%";
	$hTOTpausepct =	sprintf("%10s", $TOTpausepct)."%";
	$hTOTdeadpct =	sprintf("%10s", $TOTdeadpct)."%";
	$hTOTcalls = sprintf("%8s", $TOTcalls);
	$hTOTwait =	sprintf("%11s", $TOTwait);
	$hTOTtalk =	sprintf("%11s", $TOTtalk);
	$hTOTdispo =	sprintf("%11s", $TOTdispo);
	$hTOTpause =	sprintf("%11s", $TOTpause);
	$hTOTdead =	sprintf("%11s", $TOTdead);
	$hTOTcustomer =	sprintf("%11s", $TOTcustomer);
	$hTOTconnected =	sprintf("%11s", $TOTconnected);
	$hTOTALtime = sprintf("%11s", $TOTALtime);
	$hTOTtimeTC = sprintf("%11s", $TOTtimeTC);
	$TOT_HIDDEN_HTML =		sec_convert($TOT_HIDDEN,$TIME_agenttimedetail);
	$pfTOT_HIDDEN_sec =		sprintf("%11s", $TOT_HIDDEN_HTML);
	$TOT_VISIBLE_HTML =		sec_convert($TOT_VISIBLE,$TIME_agenttimedetail);
	$pfTOT_VISIBLE_sec =	sprintf("%11s", $TOT_VISIBLE_HTML);

	###### END LAST LINE TOTALS FORMATTING ##########


 
	if ($file_download < 1)
		{
		$ASCII_text.="+---------------------------+----------+----------+------------+------------$park_HEADER_DIV+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+   +$sub_statusesHEAD   +-------------+-------------+\n";
		$ASCII_text.="| "._QXZ("TOTALS",10)." "._QXZ("AGENTS",9,"r").":$hTOT_AGENTS           | $hTOTcalls |$hTOTtimeTC |$hTOTALtime |$park_TOTALS$hTOTwait |$hTOTwaitpct |$hTOTtalk |$hTOTtalkpct |$hTOTdispo |$hTOTdispopct |$hTOTpause |$hTOTpausepct |$hTOTdead |$hTOTdeadpct |$hTOTcustomer |$hTOTconnected |   |$SUMstatusesHTML   | $pfTOT_VISIBLE_sec | $pfTOT_HIDDEN_sec |\n";
		$ASCII_text.="+--------------------------------------+----------+------------+------------$park_HEADER_DIV+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+------------+   +$sub_statusesHEAD   +-------------+-------------+\n";
		if ($AUTOLOGOUTflag > 0)
			{
			$ASCII_text.= "     * "._QXZ("denotes AUTOLOGOUT from timeclock")."\n";
			$HTML_text.= "     * "._QXZ("denotes AUTOLOGOUT from timeclock")."\n";
			}
		$ASCII_text.="\n\n</PRE>";

		for ($e=0; $e<count($sub_statusesARY); $e++) 
			{
			$Sstatus=$sub_statusesARY[$e];
			$SstatusTXT=$Sstatus;
			if ($Sstatus=="") {$SstatusTXT="(blank)";}
			}

		# USE THIS FOR COMBINED graphs, use pipe-delimited array elements, dataset_name|index|link_name|graph_override
		# You have to hard code the graph name in where it is overridden and mind the data indices.  No other way to do it.
		$multigraph_text="";
		$graph_id++;
		$graph_array=array("ATD_CALLSdata|1|CALLS|integer|", "ATD_TIMECLOCKdata|2|TIME CLOCK|time|", "ATD_LOGINTIMEdata|3|LOGIN TIME|time|");
		if ($show_parks) 
			{
			array_push($graph_array, "ATD_PARKSdata|16|PARKS|integer|", "ATD_PARKTIMEdata|17|PARKTIME|time|", "ATD_AVGPARKdata|18|AVGPARK|time|", "ATD_PARKSCALLdata|19|PARKS/CALL|decimal|");
			}
		array_push($graph_array, "ATD_WAITdata|4|WAIT|time|", "ATD_WAITPCTdata|15|WAIT %|percent|", "ATD_TALKdata|5|TALK|time|", "ATD_TALKTIMEPCTdata|11|TALKTIME%|percent|", "ATD_DISPOdata|6|DISPO|time|", "ATD_DISPOTIMEPCTdata|12|DISPOTIME%|percent|", "ATD_PAUSEdata|7|PAUSE|time|", "ATD_PAUSETIMEPCTdata|13|PAUSETIME%|percent|", "ATD_DEADdata|8|DEAD|time|", "ATD_DEADTIMEPCTdata|14|DEADTIME%|percent|", "ATD_CUSTOMERdata|9|CUSTOMER|time|", "ATD_CONNECTEDdata|10|CONNECTED|time|", "ATD_USERVISIBLEdata|20|VISIBLE|time|", "ATD_USERHIDDENdata|21|HIDDEN|time|");

		for ($e=0; $e<count($sub_statusesARY); $e++) 
			{
			$Sstatus=$sub_statusesARY[$e];
			$SstatusTXT=$Sstatus;
			if ($Sstatus=="") {$SstatusTXT="(blank)";}
			array_push($graph_array, "ATD_SUBSTATUS".$e."data|".($e+21)."|".$SstatusTXT."|time|");
			}

		$default_graph="bar"; # Graph that is initally displayed when page loads
		include("graph_color_schemas.inc"); 

		$graph_totals_array=array();
		$graph_totals_rawdata=array();
		for ($q=0; $q<count($graph_array); $q++) 
			{
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
			for ($d=0; $d<count($graph_stats); $d++) 
				{
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
			switch($dataset_type) 
				{
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
		$graph_title=_QXZ("AGENT TIME DETAIL REPORT");
		include("graphcanvas.inc");
		echo $HTML_graph_head;
		$GRAPH.=$graphCanvas;

		}
	else
		{
		$file_output .= _QXZ("TOTALS").",$TOT_AGENTS,$TOTcalls,$TOTtimeTC,$TOTALtime,$park_TOTALS_CSV$TOTwait,$TOTwaitpct %,$TOTtalk,$TOTtalkpct %,$TOTdispo,$TOTdispopct %,$TOTpause,$TOTpausepct %,$TOTdead,$TOTdeadpct  %,$TOTcustomer,$TOTconnected$SUMstatusesFILE,$TOT_VISIBLE_HTML,$TOT_HIDDEN_HTML\n";
		}
	}

	############################################################################
	##### END formatting data for output section
	############################################################################





if ($file_download > 0)
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AGENT_TIME$US$FILE_TIME.csv";

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$file_output";

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
	}

############################################################################
##### BEGIN HTML form section
############################################################################
$JS_onload.="}\n";
if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
$JS_text.="</script>\n";

echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>";
echo "<TABLE CELLSPACING=3 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Dates").":<BR>";
echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

?>
<script language="JavaScript">
function openNewWindow(url)
	{
	window.open (url,"",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');
	}

var o_cal = new tcal ({
	// form name
	'formname': 'vicidial_report',
	// input name
	'controlname': 'query_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Monday week start
</script>
<?php

echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

?>
<script language="JavaScript">
var o_cal = new tcal ({
	// form name
	'formname': 'vicidial_report',
	// input name
	'controlname': 'end_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Monday week start
</script>
<?php


echo "</TD><TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
echo "<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match('/--ALL--/',$group_string))
	{echo "<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{echo "<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
	{
	if (preg_match("/$groups[$o]\|/i",$group_string)) {echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	  else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";
echo "</TD><TD VALIGN=TOP>"._QXZ("User Groups").":<BR>";
echo "<SELECT SIZE=5 NAME=user_group[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{echo "<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{echo "<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/$user_groups[$o]\|/i",$user_group_string)) {echo "<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	  else {echo "<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";
echo "</TD><TD VALIGN=TOP>"._QXZ("Shift").":<BR>";
echo "<SELECT SIZE=1 NAME=shift>\n";
echo "<option selected value=\"$shift\">"._QXZ("$shift")."</option>\n";
echo "<option value=\"\">--</option>\n";
echo "<option value=\"AM\">"._QXZ("AM")."</option>\n";
echo "<option value=\"PM\">"._QXZ("PM")."</option>\n";
echo "<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
echo "<option value=\"9AM-5PM\">"._QXZ("9AM-5PM")."</option>\n";
echo "<option value=\"5PM-MIDNIGHT\">"._QXZ("5PM-MIDNIGHT")."</option>\n";
echo "</SELECT><BR>\n";
echo "<input type='checkbox' name='show_parks' value='checked' $show_parks>"._QXZ("Show parks-holds")."<BR>";
echo "<input type='checkbox' name='time_in_sec' value='checked' $time_in_sec>"._QXZ("Time in seconds")."<BR>";
echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR>\n";
echo "</TD><TD VALIGN=TOP>"._QXZ("Display as").":<BR>";
echo "<select name='report_display_type'>";
if ($report_display_type) {echo "<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
echo "<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";

echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
echo " <a href=\"$LINKbase&stage=$stage&file_download=1\">"._QXZ("DOWNLOAD")."</a> | \n";
echo " <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</FONT>\n";
echo "</TD></TR></TABLE>";

echo "</FORM>";
############################################################################
##### END HTML form section
############################################################################

if ($report_display_type=="HTML")
	{
	echo $GRAPH.$GRAPH2.$GRAPH3.$max;
	echo $JS_text;
	}
else
	{
	echo $ASCII_text;
	}


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "<font size=1 color=white>$RUNtime</font>\n";


##### BEGIN horizontal yellow transparent bar graph overlay on top of agent stats
echo "</span>\n";
echo "<span style=\"position:absolute;left:3px;top:30px;z-index:18;\"  id=agent_status_bars>\n";
echo "<PRE><FONT SIZE=2>\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";

if ($stage == 'NAME') {$k=0;}
$m=0;
while ($m < $k)
	{
	$sort_split = explode("-----",$TOPsort[$m]);
	$i = $sort_split[1];
	$sort_order[$m] = "$i";

	if ( ($TOPsortTALLY[$i] < 1) or ($TOPsortMAX < 1) )
		{echo "                              \n";}
	else
		{
		echo "                              <SPAN class=\"yellow\">";
		$TOPsortPLOT = ( MathZDC($TOPsortTALLY[$i], $TOPsortMAX) * 110 );
		$h=0;
		while ($h <= $TOPsortPLOT)
			{
			echo " ";
			$h++;
			}
		echo "</SPAN>\n";
		}
	$m++;
	}

echo "</span>\n";
##### END horizontal yellow transparent bar graph overlay on top of agent stats

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

if ($DB > 0) {echo "$TOTALrun\n";}
?>

</BODY></HTML>
