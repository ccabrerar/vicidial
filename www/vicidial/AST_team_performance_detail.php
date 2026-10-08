<?php 
# AST_team_performance_detail.php
#
# This User-Group based report runs some very intensive SQL queries, so it is
# not recommended to run this on long time periods. This report depends on the
# QC statuses of QCFAIL, QCCANC and sales are defined by the Sale=Y status
# flags being set on those statuses.
#
# Copyright (C) 2024  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 110802-2041 - First build
# 110804-0049 - Added First Call Resolution
# 111104-1259 - Added user_group restrictions for selecting in-groups
# 120224-1424 - Added new colums and PRECAL to System Time
# 120307-1926 - Added additional statuses option and HTML display option
# 130414-0142 - Added report logging
# 130610-0958 - Finalized changing of all ereg instances to preg
# 130621-0723 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2011 - Changed to mysqli PHP functions
# 140108-0733 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0728 - Finalized adding QXZ translation to all admin files
# 141230-1424 - Added code for on-the-fly language translations display
# 150516-1315 - Fixed Javascript element problem, Issue #857
# 151219-0139 - Added option for searching archived data
# 160121-2214 - Added report title header, default report format, cleaned up formatting
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 160718-0054 - Fixed ChartJS bug
# 170409-1542 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180507-2315 - Added new help display
# 191013-0825 - Fixes for PHP7
# 200917-1720 - Modified for sale counts to be campaign-specific
# 210222-1508 - Added option to show all users
# 220301-2155 - Added allow_web_debug system setting
# 230407-1039 - Added include_sales_in_TPD_report option
# 240801-1130 - Code updates for PHP8 compatibility
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date_D"]))			{$query_date_D=$_GET["query_date_D"];}
	elseif (isset($_POST["query_date_D"]))	{$query_date_D=$_POST["query_date_D"];}
if (isset($_GET["end_date_D"]))				{$end_date_D=$_GET["end_date_D"];}
	elseif (isset($_POST["end_date_D"]))	{$end_date_D=$_POST["end_date_D"];}
if (isset($_GET["query_date_T"]))			{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["end_date_T"]))				{$end_date_T=$_GET["end_date_T"];}
	elseif (isset($_POST["end_date_T"]))	{$end_date_T=$_POST["end_date_T"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["call_status"]))			{$call_status=$_GET["call_status"];}
	elseif (isset($_POST["call_status"]))	{$call_status=$_POST["call_status"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["show_all_users"]))				{$show_all_users=$_GET["show_all_users"];}
	elseif (isset($_POST["show_all_users"]))	{$show_all_users=$_POST["show_all_users"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!is_array($group)) {$group = array();}
if (!is_array($user_group)) {$user_group = array();}
if (!is_array($call_status)) {$call_status = array();}
if (!is_array($call_statuses)) {$call_statuses = array();}
if (!isset($query_date_D)) {$query_date_D=$NOW_DATE;}
if (!isset($end_date_D)) {$end_date_D=$NOW_DATE;}
if (!isset($query_date_T)) {$query_date_T="00:00:00";}
if (!isset($end_date_T)) {$end_date_T="23:59:59";}

$report_name = 'Team Performance Detail';
$db_source = 'M';
$JS_text.="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$HTML_text.="$stmt\n";}
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

if (file_exists('options.php'))
	{
	require('options.php');
	}

$query_date_D = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date_D);
$query_date_T = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date_T);
$end_date_D = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $end_date_D);
$end_date_T = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $end_date_T);
$show_all_users = preg_replace('/[^-_0-9a-zA-Z]/', '', $show_all_users);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);

# Variables filtered further down in the code
# $group
# $user_group
# $call_status

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_list", "vicidial_agent_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_list_table=use_archive_table("vicidial_list");
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
	$vicidial_list_table="vicidial_list";
	$vicidial_agent_log_table="vicidial_agent_log";
	}
#############

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
	$HTML_text.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
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

######################################


$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $group[$i]);
	$group_string .= "$group[$i]|";
	$i++;
	}

$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	if (preg_match('/\-ALL/',$group_string) )
		{$group[$i] = $groups[$i];}
	$i++;
	}

#######################################
$i=0;
$call_status_string='|';
$call_status_ct = count($call_status);
while($i < $call_status_ct)
	{
	$call_status[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $call_status[$i]);
	$call_status_string .= "$call_status[$i]|";
	$i++;
	}
if (preg_match("/--NONE--/", $call_status_string))
	{
	$call_status_string="";
	$call_status=array();
	$call_status_ct=0;
	}

$stmt="select distinct status, status_name from vicidial_statuses ".(!$include_sales_in_TPD_report ? "where sale!='Y'" : "")." UNION select distinct status, status_name from vicidial_campaign_statuses ".(!$include_sales_in_TPD_report ? "where sale!='Y'" : "")." $LOGallowed_campaignsSQL order by status, status_name;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$call_statuses_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $call_statuses_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$call_statuses[$i] =$row[0];
	$call_statuses_names[$i] =$row[1];
#	if (preg_match('/\-ALL/',$call_status_string) )
#		{$call_status[$i] = $call_statuses[$i];}
	$i++;
	}

#######################################
for ($i=0; $i<count($user_group); $i++) 
	{
	$user_group[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $user_group[$i]);
	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1;}
	}

$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$user_groups=array();
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	if ($all_user_groups) {$user_group[$i]=$row[0];}
	$i++;
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $group[$i]);
	if ( (preg_match("/ $group[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_SQL_str=$group_SQL;
	$group_SQL = "and campaign_id IN($group_SQL)";
	}

$i=0;
$call_status_string='|';
$call_status_ct = count($call_status);
while($i < $call_status_ct)
	{
	$call_status_string .= "$call_status[$i]|";
	$call_status_SQL .= "'$call_status[$i]',";
	$CSVstatusheader.=",\"$call_status[$i]\"";
	$HTMLborderheader.="--------+";
	$HTMLstatusheader.=" ".sprintf("%6s", $call_status[$i])." |";
	$call_statusQS .= "&call_status[]=$call_status[$i]";
	$i++;
	}

if ( (preg_match('/\s\-\-NONE\-\-\s/',$call_status_string) ) or ($call_status_ct < 1) )
	{$call_status_SQL = "";}
else
	{
	$call_status_SQL = preg_replace('/,$/i', '',$call_status_SQL);
	$call_status_SQL_str=$call_status_SQL;
	$call_status_SQL = "and status IN($call_status_SQL)";
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
	$user_group_SQL = "and ".$vicidial_agent_log_table.".user_group IN($user_group_SQL)";
	}


######################################
if ($DB) {$HTML_text.="$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

###########################

$HTML_head.="<HTML>\n";
$HTML_head.="<HEAD>\n";
$HTML_head.="<STYLE type=\"text/css\">\n";
$HTML_head.="<!--\n";
$HTML_head.="   .green {color: white; background-color: green}\n";
$HTML_head.="   .red {color: white; background-color: red}\n";
$HTML_head.="   .blue {color: white; background-color: blue}\n";
$HTML_head.="   .purple {color: white; background-color: purple}\n";
$HTML_head.="-->\n";
$HTML_head.=" </STYLE>\n";

$query_date="$query_date_D $query_date_T";
$end_date="$end_date_D $end_date_T";

$HTML_head.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_head.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_head.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HTML_head.="<script src='chart/Chart.js'></script>\n"; 
$HTML_head.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>$group_S\n";

$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";
$HTML_text.="<b>"._QXZ("$report_name")."</b> $NWB#team_performance_detail$NWE\n";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLSPACING=3 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP>";
$HTML_text.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=HIDDEN NAME=type VALUE=\"$type\">\n";
$HTML_text.=_QXZ("Date Range").":<BR>\n";

$HTML_text.="<INPUT TYPE=hidden NAME=query_date ID=query_date VALUE=\"$query_date\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=end_date ID=end_date VALUE=\"$end_date\">\n";
$HTML_text.="<INPUT TYPE=TEXT NAME=query_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$query_date_D\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="  {\n";
$HTML_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="  }\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'query_date_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$HTML_text.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$end_date_D\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'end_date_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=end_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$end_date_T\">";

$HTML_text.="</TD><TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
	{
	if (preg_match("/$groups[$o]\|/i",$group_string)) 
		{$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	else 
		{$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";

$HTML_text.="</TD><TD VALIGN=TOP>"._QXZ("Teams/User Groups").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=user_group[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/\|$user_groups[$o]\|/i",$user_group_string)) 
		{$HTML_text.="<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	else 
		{$HTML_text.="<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";

$HTML_text.="<TD VALIGN=TOP> "._QXZ("Show additional statuses").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=call_status[] multiple>\n";
if (!$call_status || $call_status_ct==0) 
	{$HTML_text.="<option selected value=\"--NONE--\">-- "._QXZ("NO ADDITIONAL STATUSES")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--NONE--\">-- "._QXZ("NO ADDITIONAL STATUSES")." --</option>\n";}
$o=0;
while ($call_statuses_to_print > $o)
	{
	if (preg_match("/^$call_statuses[$o]\||\|$call_statuses[$o]\|/i",$call_status_string) && strlen($call_status_string)>0) 
		{$HTML_text.="<option selected value=\"$call_statuses[$o]\">$call_statuses[$o] - $call_statuses_names[$o]</option>\n";}
	else 
		{$HTML_text.="<option value=\"$call_statuses[$o]\">$call_statuses[$o] - $call_statuses_names[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT></TD>\n";


$HTML_text.="<TD VALIGN=TOP>\n";
$HTML_text.=_QXZ("Display as").":<BR>";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
if ($archives_available=="Y") 
	{
	$HTML_text.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR>\n";
	}
$HTML_text.="<input type='checkbox' value='checked' name='show_all_users' id='show_all_users' $show_all_users>Show all users<BR>";
$HTML_text.="<font size=1>(includes inactive agents/agents with no calls)";
$HTML_text.="</TD><TD VALIGN=TOP align='center'>";

$HTML_text.="<BR>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR><BR>\n";

$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>\n";
$HTML_text.="&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<a href=\"$PHP_SELF?DB=$DB&query_date=$query_date&end_date=$end_date&query_date_D=$query_date_D&query_date_T=$query_date_T&end_date_D=$end_date_D&end_date_T=$end_date_T$groupQS$user_groupQS$call_statusQS&file_download=1&search_archived_data=$search_archived_data&show_all_users=$show_all_users&SUBMIT=$SUBMIT\">"._QXZ("DOWNLOAD")."</a> |";
$HTML_text.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$HTML_text.="</FONT>\n";
$HTML_text.="</TD></TR></TABLE>";
$HTML_text.="</FORM>\n\n";

$report_sale_array=array();
$dispo_sale_stmt="select status, campaign_id from vicidial_campaign_statuses where sale='Y' $group_SQL UNION select status, 'VICIDIAL_SALE' from vicidial_statuses where sale='Y'";
$dispo_sale_rslt=mysql_to_mysqli($dispo_sale_stmt, $link);
while ($dispo_sale_row=mysqli_fetch_row($dispo_sale_rslt))
	{
	$status=$dispo_sale_row[0];
	$campaign=strtoupper($dispo_sale_row[1]);
	if (!$report_sale_array["$campaign"]) {$report_sale_array["$campaign"]="|";}
	$report_sale_array["$campaign"].="$status|";
	}
# print_r($report_sale_array);

if ( ($SUBMIT=="SUBMIT") or ($SUBMIT==_QXZ("SUBMIT")) )
	{
	# Sale counts per rep 

	$stmt="select max(event_time), ".$vicidial_agent_log_table.".user, ".$vicidial_agent_log_table.".lead_id, ".$vicidial_list_table.".status as current_status, ".$vicidial_agent_log_table.".campaign_id, ".$vicidial_agent_log_table.".status from ".$vicidial_agent_log_table.", ".$vicidial_list_table." where event_time>='$query_date' and event_time<='$end_date' $group_SQL and ".$vicidial_agent_log_table.".status in (select status from vicidial_campaign_statuses where sale='Y' $group_SQL UNION select status from vicidial_statuses where sale='Y') and ".$vicidial_agent_log_table.".lead_id=".$vicidial_list_table.".lead_id group by ".$vicidial_agent_log_table.".user, ".$vicidial_agent_log_table.".lead_id";
	if ($DB) {$ASCII_text.="$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$cancel_array=array();
	$incomplete_array=array();
	$sale_array=array();
	while ($row=mysqli_fetch_array($rslt)) 
		{
		$lead_id=$row["lead_id"];
		$user=$row["user"];
		$current_status=$row["current_status"];
		$campaign_id=strtoupper($row["campaign_id"]);
		$agent_status=$row["status"];
		if (preg_match("/QCCANC/i", $current_status)) 
			{
			$cancel_array[$row["user"]]++;
			} 
		else if (preg_match("/QCFAIL/i", $current_status)) 
			{
			$incomplete_array[$row["user"]]++;
			} 
		else 
			{
			if (preg_match("/\|$agent_status\|/i", $report_sale_array["$campaign_id"]) || preg_match("/\|$agent_status\|/i", $report_sale_array["VICIDIAL_SALE"]))
				{
			$sale_array[$row["user"]]++;

			# Get actual talk time for all calls made by the user for this particular lead. If cancelled and incomplete sales are to have their times 
			# counted towards sales talk time, move the below lines OUTSIDE the curly bracket below, so the query runs regardless of what "type" of 
			# sale it is.
				$sale_time_stmt="select sum(talk_sec)-sum(dead_sec) from ".$vicidial_agent_log_table." where user='$user' and lead_id=$lead_id $group_SQL";
			if ($DB) {$ASCII_text.="$sale_time_stmt\n";}
			$sale_time_rslt=mysql_to_mysqli($sale_time_stmt, $link);
			$sale_time_row=mysqli_fetch_row($sale_time_rslt);
			$sales_talk_time_array[$row["user"]]+=$sale_time_row[0];
			}
		}
		}

	$HTML_text.="<PRE><FONT SIZE=2>";
	$total_average_sale_time=0;
	$total_average_contact_time=0;
	$total_talk_time=0; 
	$total_system_time=0; 
	$total_calls=0;	
	$total_leads=0;
	$total_contacts=0;
	$total_sales=0;
	$total_inc_sales=0;
	$total_cnc_sales=0;
	$total_callbacks=0;
	$total_stcall=0;
	$call_status_totals_grand_total=array();
	for ($q=0; $q<count($call_status); $q++) {
		$call_status_totals_grand_total[$q]=0;
		$GRAPH2.="<th class='column_header grey_graph_cell' id='teamTotalgraph".($q+17)."'><a href='#' onClick=\"DrawTotalGraph('".$call_status[$q]."', '".($q+17)."'); return false;\">".$call_status[$q]."</a></th>";
	}
	$total_graph_stats=array();
	$max_totalcalls=1;
	$max_totalleads=1;
	$max_totalcontacts=1;
	$max_totalcontactratio=1;
	$max_totalsystemtime=1;
	$max_totaltalktime=1;
	$max_totalsales=1;
	$max_totalsalesleadsratio=1;
	$max_totalsalescontactsratio=1;
	$max_totalsalesperhour=1;
	$max_totalincsales=1;
	$max_totalcancelledsales=1;
	$max_totalcallbacks=1;
	$max_totalfirstcall=1;
	$max_totalavgsaletime=1;
	$max_totalavgcontacttime=1;

	for($i=0; $i<$user_group_ct; $i++) 
		{
		$group_average_sale_time=0;
		$group_average_contact_time=0;
		$group_talk_time=0; 
		$group_system_time=0; 
		$group_nonpause_time=0;
		$group_calls=0;	
		$group_leads=0;
		$group_contacts=0;
		$group_sales=0;
		$group_inc_sales=0;
		$group_cnc_sales=0;
		$group_callbacks=0;
		$group_stcall=0;
		$name_stmt="select group_name from vicidial_user_groups where user_group='$user_group[$i]'";
		$name_rslt=mysql_to_mysqli($name_stmt, $link);
		$name_row=mysqli_fetch_row($name_rslt);
		$group_name=$name_row[0];
		$call_status_group_totals=array();
		for ($q=0; $q<count($call_status); $q++) {
			$call_status_group_totals[$q]=0;
		}

		$ASCII_text.="--- <B>"._QXZ("TEAM").": $user_group[$i] - $group_name</B>\n";
		$CSV_text.="\"\",\""._QXZ("TEAM").": $user_group[$i] - $group_name\"\n";
		$GRAPH_text.="<B>"._QXZ("TEAM").": $user_group[$i] - $group_name</B>";

		#### USER COUNTS
		if ($show_all_users)
			{
			$user_stmt="select full_name, user from vicidial_users where user_group='$user_group[$i]' order by full_name, user";
			}
		else 
			{
			$user_stmt="select distinct vicidial_users.full_name, vicidial_users.user from vicidial_users, ".$vicidial_agent_log_table." where vicidial_users.user_group='$user_group[$i]' and vicidial_users.user=".$vicidial_agent_log_table.".user and ".$vicidial_agent_log_table.".user_group='$user_group[$i]'  and ".$vicidial_agent_log_table.".event_time>='$query_date' and ".$vicidial_agent_log_table.".event_time<='$end_date' and ".$vicidial_agent_log_table.".campaign_id in ($group_SQL_str) order by full_name, user";
			}
		if ($DB) {$ASCII_text.="$user_stmt\n";}
		$user_rslt=mysql_to_mysqli($user_stmt, $link);
		if (mysqli_num_rows($user_rslt)>0) 
			{
			$graph_stats=array();
			$max_calls=1;
			$max_leads=1;
			$max_contacts=1;
			$max_contactratio=1;
			$max_systemtime=1;
			$max_talktime=1;
			$max_sales=1;
			$max_salesleadsratio=1;
			$max_salescontactsratio=1;
			$max_salesperhour=1;
			$max_incsales=1;
			$max_cancelledsales=1;
			$max_callbacks=1;
			$max_firstcall=1;
			$max_avgsaletime=1;
			$max_avgcontacttime=1;
			$GRAPH="<BR><BR><a name='team".$user_group[$i]."graph'/><table border='0' cellpadding='0' cellspacing='2' width='800'>";
			$STATGRAPH="";
			for ($q=0; $q<count($call_status); $q++) {
				$STATGRAPH.="<th class='column_header grey_graph_cell' width='6%' id='team".$user_group[$i]."graph".($q+17)."'><a href='#' onClick=\"Draw".$user_group[$i]."Graph('".$call_status[$q]."', '".($q+17)."'); return false;\">".$call_status[$q]."</a></th>";
				$max_varname="max_".$call_status[$q];
				$$max_varname=1;
			}


			$j=0;
			$ASCII_text.="+------------------------------------------+------------+-------+-------+----------+---------------+---------------+-------------+-----------+-------+------------------------+----------------------+-------------------------+----------------+------------------+-----------------+-----------+-----------------------+-------------------+----------------------+$HTMLborderheader\n";
			$ASCII_text.="| "._QXZ("Agent Name",40)." | "._QXZ("Agent ID",10)." | "._QXZ("Calls",5)." | "._QXZ("Leads",5)." | "._QXZ("Contacts",8)." | "._QXZ("Contact Ratio",13)." | "._QXZ("Nonpause Time",13)." | "._QXZ("System Time",11)." | "._QXZ("Talk Time",9)." | "._QXZ("Sales",5)." | "._QXZ("Sales per Working Hour",22)." | "._QXZ("Sales to Leads Ratio",20)." | "._QXZ("Sales to Contacts Ratio",23)." | "._QXZ("Sales Per Hour",14)." | "._QXZ("Incomplete Sales",16)." | "._QXZ("Cancelled Sales",15)." | "._QXZ("Callbacks",9)." | "._QXZ("First Call Resolution",21)." | "._QXZ("Average Sale Time",17)." | "._QXZ("Average Contact Time",20)." |$HTMLstatusheader\n";
			$ASCII_text.="+------------------------------------------+------------+-------+-------+----------+---------------+---------------+-------------+-----------+-------+------------------------+----------------------+-------------------------+----------------+------------------+-----------------+-----------+-----------------------+-------------------+----------------------+$HTMLborderheader\n";
			$CSV_text.="\"\",\""._QXZ("Agent Name")."\",\""._QXZ("Agent ID")."\",\""._QXZ("Calls")."\",\""._QXZ("Leads")."\",\""._QXZ("Contacts")."\",\""._QXZ("Contact Ratio")."\",\""._QXZ("Nonpause Time")."\",\""._QXZ("System Time")."\",\""._QXZ("Talk Time")."\",\""._QXZ("Sales")."\",\""._QXZ("Sales per Working Hour")."\",\""._QXZ("Sales to Leads Ratio")."\",\""._QXZ("Sales to Contacts Ratio")."\",\""._QXZ("Sales Per Hour")."\",\""._QXZ("Incomplete Sales")."\",\""._QXZ("Cancelled Sales")."\",\""._QXZ("Callbacks")."\",\""._QXZ("First Call Resolution")."\",\""._QXZ("Average Sale Time")."\",\""._QXZ("Average Contact Time")."\"$CSVstatusheader\n";
			while ($user_row=mysqli_fetch_array($user_rslt)) 
				{
				# For each user
				$user=$user_row["user"];
				$sale_array[$user]+=0;  # For agents with no sales logged
				$incomplete_array[$user]+=0;  # For agents with no QCFAIL logged
				$cancel_array[$user]+=0;  # For agents with no QCCANC logged

				$j++;
				$contacts=0;
				$callbacks=0;
				$stcall=0;
				$calls=0;
				$leads=0;
				$system_time=0;
				$talk_time=0;
				$nonpause_time=0;

				# Leads 
				$lead_stmt="select count(distinct lead_id) from ".$vicidial_agent_log_table." where lead_id is not null and event_time>='$query_date' and event_time<='$end_date' $group_SQL and user='$user' and user_group='$user_group[$i]'";
				if ($DB) {$ASCII_text.="$lead_stmt\n";}
				$lead_rslt=mysql_to_mysqli($lead_stmt, $link);
				$lead_row=mysqli_fetch_row($lead_rslt);
				$leads=$lead_row[0];

				# Callbacks 
				$callback_stmt="select count(*) from vicidial_callbacks where status in ('ACTIVE', 'LIVE') $group_SQL and user='$user' and user_group='$user_group[$i]'";
				if ($DB) {$ASCII_text.="$callback_stmt\n";}
				$callback_rslt=mysql_to_mysqli($callback_stmt, $link);
				$callback_row=mysqli_fetch_row($callback_rslt);
				$callbacks=$callback_row[0];

				$stat_stmt="select val.status, val.sub_status, vs.customer_contact, sum(val.talk_sec), sum(val.pause_sec), sum(val.wait_sec), sum(val.dispo_sec), sum(val.dead_sec), count(*) from ".$vicidial_agent_log_table." val, vicidial_statuses vs where val.user='$user' and val.user_group='$user_group[$i]' and val.event_time>='$query_date' and val.event_time<='$end_date' and val.status=vs.status and vs.status in (select status from vicidial_statuses) and val.campaign_id in ($group_SQL_str) group by status, customer_contact UNION select val.status, val.sub_status, vs.customer_contact, sum(val.talk_sec), sum(val.pause_sec), sum(val.wait_sec), sum(val.dispo_sec), sum(val.dead_sec), count(*) from ".$vicidial_agent_log_table." val, vicidial_campaign_statuses vs where val.campaign_id in ($group_SQL_str) and val.user='$user' and val.user_group='$user_group[$i]' and val.event_time>='$query_date' and val.event_time<='$end_date' and val.status=vs.status and val.campaign_id=vs.campaign_id and vs.status in (select distinct status from vicidial_campaign_statuses where ".substr($group_SQL, 4).") group by status, customer_contact";
				if ($DB) {$ASCII_text.="$stat_stmt\n";}
				$stat_rslt=mysql_to_mysqli($stat_stmt, $link);
				while ($stat_row=mysqli_fetch_row($stat_rslt)) 
					{
					if ($stat_row[2]=="Y") 
						{
						$contacts+=$stat_row[8]; 
						$contact_talk_time+=($stat_row[3]-$stat_row[7]);

						$group_contact_talk_time+=($stat_row[3]-$stat_row[7]);
						}
					# if ($stat_row[2]=="Y") {$callbacks+=$stat_row[8];}
					$calls+=$stat_row[8];
					$talk_time+=($stat_row[3]-$stat_row[7]);
					$system_time+=($stat_row[3]+$stat_row[5]+$stat_row[6]);
					$nonpause_time+=($stat_row[3]+$stat_row[5]+$stat_row[6]);
					if ($stat_row[1]=="PRECAL") 
						{
						$nonpause_time+=$stat_row[4];
						}
					}
				$user_talk_time =		sec_convert($talk_time,'H'); 
				$group_talk_time+=$talk_time;
				$user_system_time =		sec_convert($system_time,'H'); 
				$talk_hours=MathZDC($talk_time, 3600);
				$group_system_time+=$system_time;
				$user_nonpause_time =		sec_convert($nonpause_time,'H'); 
				$group_nonpause_time+=$nonpause_time;

				if ($sale_array[$user]>0) {$average_sale_time=sec_convert(round(MathZDC($sales_talk_time_array[$user], $sale_array[$user])), 'H');} else {$average_sale_time="00:00";}
				$group_sales_talk_time+=$sales_talk_time_array[$user];
				if ($contacts>0) {$average_contact_time=sec_convert(round(MathZDC($contact_talk_time, $contacts)), 'H');} else {$average_contact_time="00:00";}

				$ASCII_text.="| ".sprintf("%-40s", $user_row["full_name"]);

				$ASCII_text.=" | <a href='user_stats.php?user=$user&begin_date=$query_date_D&end_date=$end_date_D'>".sprintf("%10s", substr("$user", 0, 10))."</a>";
				$ASCII_text.=" | ".sprintf("%5s", $calls);	$group_calls+=$calls;
				$ASCII_text.=" | ".sprintf("%5s", $leads);	$group_leads+=$leads;
				$ASCII_text.=" | ".sprintf("%8s", $contacts);  $group_contacts+=$contacts;
				$contact_ratio=sprintf("%.2f", MathZDC(100*$contacts,$leads));
				$ASCII_text.=" | ".sprintf("%12s", $contact_ratio)."%";
				$ASCII_text.=" | ".sprintf("%13s", $user_nonpause_time);
				$ASCII_text.=" | ".sprintf("%11s", $user_system_time);
				$ASCII_text.=" | ".sprintf("%9s", $user_talk_time);
				$ASCII_text.=" | ".sprintf("%5s", $sale_array[$user]);	$group_sales+=$sale_array[$user];
				$sales_per_working_hours=sprintf("%.2f", (MathZDC($sale_array[$user], MathZDC($nonpause_time, 3600))));
				$ASCII_text.=" | ".sprintf("%22s", $sales_per_working_hours);
				$sales_ratio=sprintf("%.2f", MathZDC(100*$sale_array[$user], $leads));
				$ASCII_text.=" | ".sprintf("%19s", $sales_ratio)."%";
				$sale_contact_ratio=sprintf("%.2f", MathZDC(100*$sale_array[$user], $contacts));
				$ASCII_text.=" | ".sprintf("%22s", $sale_contact_ratio)."%";
				$sales_per_hour=sprintf("%.2f", MathZDC($sale_array[$user], $talk_hours));
				$stcall=sprintf("%.2f", MathZDC($calls, $leads));

				$avg_sale_time=round(MathZDC($sales_talk_time_array[$user], $sale_array[$user]));
				$avg_contact_time=round(MathZDC($contact_talk_time, $contacts));
				$graph_stats[$j][0]=$user_row["full_name"]." - $user";
				$graph_stats[$j][1]=trim($calls);
				$graph_stats[$j][2]=trim($leads);
				$graph_stats[$j][3]=trim($contacts);
				$graph_stats[$j][4]=trim($contact_ratio);
				$graph_stats[$j][5]=trim($system_time);
				$graph_stats[$j][6]=trim($talk_time);
				$graph_stats[$j][7]=trim($sale_array[$user]);
				$graph_stats[$j][8]=trim($sales_ratio);
				$graph_stats[$j][9]=trim($sale_contact_ratio);
				$graph_stats[$j][10]=trim($sales_per_hour);
				$graph_stats[$j][11]=trim($incomplete_array[$user]);
				$graph_stats[$j][12]=trim($cancel_array[$user]);
				$graph_stats[$j][13]=trim($callbacks);
				$graph_stats[$j][14]=trim($stcall);
				$graph_stats[$j][15]=trim($avg_sale_time);
				$graph_stats[$j][16]=trim($avg_contact_time);

				if (trim($calls)>$max_calls) {$max_calls=trim($calls);}
				if (trim($leads)>$max_leads) {$max_leads=trim($leads);}
				if (trim($contacts)>$max_contacts) {$max_contacts=trim($contacts);}
				if (trim($contact_ratio)>$max_contactratio) {$max_contactratio=trim($contact_ratio);}
				if (trim($system_time)>$max_systemtime) {$max_systemtime=trim($system_time);}
				if (trim($talk_time)>$max_talktime) {$max_talktime=trim($talk_time);}
				if (trim($sale_array[$user])>$max_sales) {$max_sales=trim($sale_array[$user]);}
				if (trim($sales_ratio)>$max_salesleadsratio) {$max_salesleadsratio=trim($sales_ratio);}
				if (trim($sale_contact_ratio)>$max_salescontactsratio) {$max_salescontactsratio=trim($sale_contact_ratio);}
				if (trim($sales_per_hour)>$max_salesperhour) {$max_salesperhour=trim($sales_per_hour);}
				if (trim($incomplete_array[$user])>$max_incsales) {$max_incsales=trim($incomplete_array[$user]);}
				if (trim($cancel_array[$user])>$max_cancelledsales) {$max_cancelledsales=trim($cancel_array[$user]);}
				if (trim($callbacks)>$max_callbacks) {$max_callbacks=trim($callbacks);}
				if (trim($stcall)>$max_firstcall) {$max_firstcall=trim($stcall);}
				if (trim($avg_sale_time)>$max_avgsaletime) {$max_avgsaletime=trim($avg_sale_time);}
				if (trim($avg_contact_time)>$max_avgcontacttime) {$max_avgcontacttime=trim($avg_contact_time);}

				$ASCII_text.=" | ".sprintf("%14s", $sales_per_hour);
				$ASCII_text.=" | ".sprintf("%16s", $incomplete_array[$user]);  $group_inc_sales+=$incomplete_array[$user];
				$ASCII_text.=" | ".sprintf("%15s", $cancel_array[$user]);  $group_cnc_sales+=$cancel_array[$user];
				$ASCII_text.=" | ".sprintf("%9s", $callbacks);  $group_callbacks+=$callbacks;
				$ASCII_text.=" | ".sprintf("%21s", $stcall);	# first call resolution
				$ASCII_text.=" | ".sprintf("%17s", $average_sale_time);
				$ASCII_text.=" | ".sprintf("%20s", $average_contact_time)." |";

				$CSV_status_text="";
				for ($q=0; $q<count($call_status); $q++) {
					$stat_stmt="select sum(stat_ct) from (select count(distinct uniqueid) as stat_ct From ".$vicidial_agent_log_table." val, vicidial_statuses vs where val.user='$user' and val.user_group='$user_group[$i]' and val.event_time>='$query_date' and val.event_time<='$end_date' and val.status=vs.status and vs.status='$call_status[$q]' and val.campaign_id in ($group_SQL_str) UNION select count(distinct uniqueid) as stat_ct From ".$vicidial_agent_log_table." val, vicidial_campaign_statuses vs where val.user='$user' and val.user_group='$user_group[$i]' and val.event_time>='$query_date' and val.event_time<='$end_date' and val.status=vs.status and vs.status='$call_status[$q]' and val.campaign_id in ($group_SQL_str)) as counts";
					$stat_rslt=mysql_to_mysqli($stat_stmt, $link);
					$stat_row=mysqli_fetch_row($stat_rslt);
					$ASCII_text.=" ".sprintf("%6s", $stat_row[0])." |";
					$CSV_status_text.=",\"$stat_row[0]\"";
					$call_status_group_totals[$q]+=$stat_row[0];
					$graph_stats[$j][(17+$q)]=$stat_row[0];
					
					$varname=$Sstatus."_graph";
					$$varname=$graph_header."<th class='thgraph' scope='col'>$Sstatus</th></tr>";

					$max_varname="max_".$call_status[$q];
					if ($stat_row[0]>$$max_varname) {$$max_varname=$stat_row[0];}
				}
				$ASCII_text.="\n";

				$CSV_text.="\"$j\",\"$user_row[full_name]\",\"$user\",\"$calls\",\"$leads\",\"$contacts\",\"$contact_ratio %\",\"$user_nonpause_time\",\"$user_system_time\",\"$user_talk_time\",\"$sale_array[$user]\",\"$sales_per_working_hours\",\"$sales_ratio\",\"$sale_contact_ratio\",\"$sales_per_hour\",\"$incomplete_array[$user]\",\"$cancel_array[$user]\",\"$callbacks\",\"$stcall\",\"$average_sale_time\",\"$average_contact_time\"$CSV_status_text\n";
				}

			##### GROUP TOTALS #############
			$group_average_sale_time=sec_convert(round(MathZDC($group_sales_talk_time, $group_sales)), 'H');
			$group_average_contact_time=sec_convert(round(MathZDC($group_contact_talk_time, $group_contacts)), 'H');
			$group_talk_hours=MathZDC($group_talk_time, 3600);

			$GROUP_text.="| ".sprintf("%40s", "$group_name");
			$GROUP_text.=" | ".sprintf("%20s", "$user_group[$i]");
			$total_graph_stats[$i][0]="$user_group[$i] - $group_name";

			$ASCII_text.="+------------------------------------------+------------+-------+-------+----------+---------------+---------------+-------------+-----------+-------+------------------------+----------------------+-------------------------+----------------+------------------+-----------------+-----------+-----------------------+-------------------+----------------------+$HTMLborderheader\n";
			$ASCII_text.="| ".sprintf("%40s", "");
			$ASCII_text.=" | ".sprintf("%10s", _QXZ("TOTALS:",10));

			$TOTAL_text=" | ".sprintf("%5s", $group_calls);	
			$TOTAL_text.=" | ".sprintf("%5s", $group_leads);
			$TOTAL_text.=" | ".sprintf("%8s", $group_contacts);
			$group_contact_ratio=sprintf("%.2f", MathZDC(100*$group_contacts, $group_leads));
			$TOTAL_text.=" | ".sprintf("%12s", $group_contact_ratio)."%";
			$TOTAL_text.=" | ".sprintf("%13s", sec_convert($group_nonpause_time,'H'));
			$TOTAL_text.=" | ".sprintf("%11s", sec_convert($group_system_time,'H'));
			$TOTAL_text.=" | ".sprintf("%9s", sec_convert($group_talk_time,'H'));
			$TOTAL_text.=" | ".sprintf("%5s", $group_sales);
			$sales_per_working_hours=sprintf("%.2f", (MathZDC($group_sales, MathZDC($group_nonpause_time, 3600))));
			$TOTAL_text.=" | ".sprintf("%22s", $sales_per_working_hours);
			$group_sales_ratio=sprintf("%.2f", MathZDC(100*$group_sales, $group_leads));
			$TOTAL_text.=" | ".sprintf("%19s", $group_sales_ratio)."%";
			$group_sale_contact_ratio=sprintf("%.2f", MathZDC(100*$group_sales, $group_contacts));
			$TOTAL_text.=" | ".sprintf("%22s", $group_sale_contact_ratio)."%";
			$group_sales_per_hour=sprintf("%.2f", MathZDC($group_sales, $group_talk_hours));
			$group_stcall=sprintf("%.2f", MathZDC($group_calls, $group_leads));
			$TOTAL_text.=" | ".sprintf("%14s", $group_sales_per_hour);
			$TOTAL_text.=" | ".sprintf("%16s", $group_inc_sales);
			$TOTAL_text.=" | ".sprintf("%15s", $group_cnc_sales);
			$TOTAL_text.=" | ".sprintf("%9s", $group_callbacks);
			$TOTAL_text.=" | ".sprintf("%21s", $group_stcall); 	# first call resolution
			$TOTAL_text.=" | ".sprintf("%17s", $group_average_sale_time);
			$TOTAL_text.=" | ".sprintf("%20s", $group_average_contact_time)." |";

			$CSV_status_text="";
			for ($q=0; $q<count($call_status_group_totals); $q++) {
				$TOTAL_text.=" ".sprintf("%6s", $call_status_group_totals[$q])." |";
				$call_status_totals_grand_total[$q]+=$call_status_group_totals[$q];
				$CSV_status_text.=",\"$call_status_group_totals[$q]\"";
				$total_var=$call_status[$q]."_total";
				$$total_var=$call_status_group_totals[$q];
				$total_graph_stats[$i][(17+$q)]=$call_status_group_totals[$q];
				$max_varname="max_total".$call_status[$q];
				if ($call_status_group_totals[$q]>$$max_varname) {$$max_varname=$call_status_group_totals[$q];}
			}
			$TOTAL_text.="\n";

			if (trim($group_calls)>$max_totalcalls) {$max_totalcalls=trim($group_calls);}
			if (trim($group_leads)>$max_totalleads) {$max_totalleads=trim($group_leads);}
			if (trim($group_contacts)>$max_totalcontacts) {$max_totalcontacts=trim($group_contacts);}
			if (trim($group_contact_ratio)>$max_totalcontactratio) {$max_totalcontactratio=trim($group_contact_ratio);}
			if (trim($group_system_time)>$max_totalsystemtime) {$max_totalsystemtime=trim($group_system_time);}
			if (trim($group_talk_time)>$max_totaltalktime) {$max_totaltalktime=trim($group_talk_time);}
			if (trim($group_sales)>$max_totalsales) {$max_totalsales=trim($group_sales);}
			if (trim($group_sales_ratio)>$max_totalsalesleadsratio) {$max_totalsalesleadsratio=trim($group_sales_ratio);}
			if (trim($group_sale_contact_ratio)>$max_totalsalescontactsratio) {$max_totalsalescontactsratio=trim($group_sale_contact_ratio);}
			if (trim($group_sales_per_hour)>$max_totalsalesperhour) {$max_totalsalesperhour=trim($group_sales_per_hour);}
			if (trim($group_inc_sales)>$max_totalincsales) {$max_totalincsales=trim($group_inc_sales);}
			if (trim($group_cnc_sales)>$max_totalcancelledsales) {$max_totalcancelledsales=trim($group_cnc_sales);}
			if (trim($group_callbacks)>$max_totalcallbacks) {$max_totalcallbacks=trim($group_callbacks);}
			if (trim($group_stcall)>$max_totalfirstcall) {$max_totalfirstcall=trim($group_stcall);}
			if (trim($group_avg_sale_time)>$max_totalavgsaletime) {$max_totalavgsaletime=trim($group_avg_sale_time);}
			if (trim($group_avg_contact_time)>$max_totalavgcontacttime) {$max_totalavgcontacttime=trim($group_avg_contact_time);}
			$total_graph_stats[$i][1]=$group_calls;
			$total_graph_stats[$i][2]=$group_leads;
			$total_graph_stats[$i][3]=$group_contacts;
			$total_graph_stats[$i][4]=$group_contact_ratio;
			$total_graph_stats[$i][5]=$group_system_time;
			$total_graph_stats[$i][6]=$group_talk_time;
			$total_graph_stats[$i][7]=$group_sales;
			$total_graph_stats[$i][8]=$group_sales_ratio;
			$total_graph_stats[$i][9]=$group_sale_contact_ratio;
			$total_graph_stats[$i][10]=$group_sales_per_hour;
			$total_graph_stats[$i][11]=$group_inc_sales;
			$total_graph_stats[$i][12]=$group_cnc_sales;
			$total_graph_stats[$i][13]=$group_callbacks;
			$total_graph_stats[$i][14]=$group_stcall;
			$total_graph_stats[$i][15]=$group_avg_sale_time;
			$total_graph_stats[$i][16]=$group_avg_contact_time;

			$ASCII_text.=$TOTAL_text;
			$GROUP_text.=$TOTAL_text;

			$ASCII_text.="+------------------------------------------+------------+-------+-------+----------+---------------+---------------+-------------+-----------+-------+------------------------+----------------------+-------------------------+----------------+------------------+-----------------+-----------+-----------------------+-------------------+----------------------+$HTMLborderheader\n";
			$ASCII_text.="\n\n";

			$CSV_text.="\"\",\"\",\""._QXZ("TOTALS").":\",\"$group_calls\",\"$group_leads\",\"$group_contacts\",\"$group_contact_ratio %\",\"".sec_convert($group_nonpause_time,'H')."\",\"".sec_convert($group_system_time,'H')."\",\"".sec_convert($group_talk_time,'H')."\",\"$group_sales\",\"$sales_per_working_hours\",\"$group_sales_ratio\",\"$group_sale_contact_ratio\",\"$group_sales_per_hour\",\"$group_inc_sales\",\"$group_cnc_sales\",\"$group_callbacks\",\"$group_stcall\",\"$group_average_sale_time\",\"$group_average_contact_time\"$CSV_status_text\n";
			$GROUP_CSV_text.="\"$i\",\"$group_name\",\"$user_group[$i]\",\"$group_calls\",\"$group_leads\",\"$group_contacts\",\"$group_contact_ratio %\",\"".sec_convert($group_nonpause_time,'H')."\",\"".sec_convert($group_system_time,'H')."\",\"".sec_convert($group_talk_time,'H')."\",\"$group_sales\",\"$sales_per_working_hours\",\"$group_sales_ratio\",\"$group_sale_contact_ratio\",\"$group_sales_per_hour\",\"$group_inc_sales\",\"$group_cnc_sales\",\"$group_callbacks\",\"$group_stcall\",\"$group_average_sale_time\",\"$group_average_contact_time\"$CSV_status_text\n";
			$CSV_text.="\n\n";

			$total_calls+=$group_calls;
			$total_leads+=$group_leads;
			$total_contacts+=$group_contacts;
			$total_system_time+=$group_system_time;
			$total_nonpause_time+=$group_nonpause_time;
			$total_talk_time+=$group_talk_time;
			$total_sales+=$group_sales;
			$total_inc_sales+=$group_inc_sales;
			$total_cnc_sales+=$group_cnc_sales;
			$total_callbacks+=$group_callbacks;
			$total_stcall+=$group_stcall; 	# first call resolution
			$total_sales_talk_time+=$group_sales_talk_time;
			$total_contact_talk_time+=$group_contact_talk_time;

			# USE THIS FOR COMBINED graphs, use pipe-delimited array elements, dataset_name|index|link_name|graph_override
			# You have to hard code the graph name in where it is overridden and mind the data indices.  No other way to do it.
			$multigraph_text="";
			$graph_id++;
			$dataset_ID="_".$user_group[$i]; # VERY IMPORTANT
			$graph_array=array("ATPD_CALLSdata$dataset_ID|1|CALLS|integer|", "ATPD_LEADSdata$dataset_ID|2|LEADS|integer|", "ATPD_CONTACTSdata$dataset_ID|3|CONTACTS|integer|", "ATPD_CONTACTRATIOdata$dataset_ID|4|CONTACT RATIO|percent|", "ATPD_SYSTEMTIMEdata$dataset_ID|5|SYSTEM TIME|time|", "ATPD_TALKTIMEdata$dataset_ID|6|TALK TIME|time|", "ATPD_SALESdata$dataset_ID|7|SALES|integer|", "ATPD_SALESTOLEADSdata$dataset_ID|8|SALES TO LEADS RATIO|decimal|", "ATPD_SALESTOCONTACTSdata$dataset_ID|9|SALES TO CONTACTS RATIO|decimal|", "ATPD_SALESPERHOURdata$dataset_ID|10|SALES PER HOUR|decimal|", "ATPD_INCOMPLETESALESSdata$dataset_ID|11|INCOMPLETE SALES|integer|", "ATPD_CANCELLEDSALESdata$dataset_ID|12|CANCELLED SALES|integer|", "ATPD_CALLBACKSdata$dataset_ID|13|CALLBACKS|integer|", "ATPD_FIRSTCALLSdata$dataset_ID|14|FIRST CALLS|decimal|", "ATPD_AVGSALETIMEdata$dataset_ID|15|AVG SALE TIME|time|", "ATPD_AVGCONTACTTIMEdata$dataset_ID|16|AVG CONTACT TIME|time|");

			for ($e=0; $e<count($call_status); $e++) {
				$Sstatus=$call_status[$e];
				$SstatusTXT=$Sstatus;
				if ($Sstatus=="") {$SstatusTXT="(blank)";}
				array_push($graph_array, "ATPD_SUBSTATUS".$e."data|".($e+19)."|".$SstatusTXT."|integer|");
			}

			$default_graph="bar"; # Graph that is initally displayed when page loads
			include("graph_color_schemas.inc"); 
			$GRAPH="";
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
				for ($d=1; $d<=count($graph_stats); $d++) {
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
			$graph_title="$user_group[$i] - $group_name";
			include("graphcanvas.inc");
			$HTML_head.=$HTML_graph_head;
			$GRAPH.=$graphCanvas;


			$GRAPH_text.=$GRAPH;
			#flush();
			} 
		else 
			{
			if ($show_all_users) {$msg="NO AGENTS CURRENTLY IN THIS USER GROUP";} else {$msg="NO AGENTS FOUND UNDER THESE REPORT PARAMETERS";}
			$ASCII_text.="    **** "._QXZ("$msg")." ****\n\n";
			$CSV_text.="\"\",\"**** "._QXZ("$msg")." ****\"\n\n";
			$GRAPH_text.="    **** "._QXZ("$msg")." ****<BR/><BR/>\n\n";
			$total_graph_stats[$i][0]="$user_group[$i] - $group_name";
			}
		}

	$ASCII_text.="--- <B>CALL CENTER TOTAL</B>\n";
	$ASCII_text.="+------------------------------------------+----------------------+-------+-------+----------+---------------+---------------+-------------+-----------+-------+------------------------+----------------------+-------------------------+----------------+------------------+-----------------+-----------+-----------------------+-------------------+----------------------+$HTMLborderheader\n";
	$ASCII_text.="| "._QXZ("Team Name",40)." | "._QXZ("Team ID",20)." | "._QXZ("Calls",5)." | "._QXZ("Leads",5)." | "._QXZ("Contacts",8)." | "._QXZ("Contact Ratio",13)." | "._QXZ("Nonpause Time",13)." | "._QXZ("System Time",11)." | "._QXZ("Talk Time",9)." | "._QXZ("Sales",5)." | "._QXZ("Sales per Working Hour",22)." | "._QXZ("Sales to Leads Ratio",20)." | "._QXZ("Sales to Contacts Ratio",23)." | "._QXZ("Sales Per Hour",14)." | "._QXZ("Incomplete Sales",16)." | "._QXZ("Cancelled Sales",15)." | "._QXZ("Callbacks",9)." | "._QXZ("First Call Resolution",21)." | "._QXZ("Average Sale Time",17)." | "._QXZ("Average Contact Time",20)." |$HTMLstatusheader\n";
	$ASCII_text.="+------------------------------------------+----------------------+-------+-------+----------+---------------+---------------+-------------+-----------+-------+------------------------+----------------------+-------------------------+----------------+------------------+-----------------+-----------+-----------------------+-------------------+----------------------+$HTMLborderheader\n";
	$ASCII_text.=$GROUP_text;
	$ASCII_text.="+------------------------------------------+----------------------+-------+-------+----------+---------------+---------------+-------------+-----------+-------+------------------------+----------------------+-------------------------+----------------+------------------+-----------------+-----------+-----------------------+-------------------+----------------------+$HTMLborderheader\n";

		$total_average_sale_time=sec_convert(round(MathZDC($total_sales_talk_time,$total_sales)), 'H');
		$total_average_contact_time=sec_convert(round(MathZDC($total_contact_talk_time,$total_contacts)), 'H');
	$total_talk_hours=MathZDC($total_talk_time, 3600);

	$ASCII_text.="| ".sprintf("%40s", "");
	$ASCII_text.=" | ".sprintf("%20s", _QXZ("TOTALS:"));
	$ASCII_text.=" | ".sprintf("%5s", $total_calls);	
	$ASCII_text.=" | ".sprintf("%5s", $total_leads);
	$ASCII_text.=" | ".sprintf("%8s", $total_contacts);
	$total_contact_ratio=sprintf("%.2f", MathZDC(100*$total_contacts, $total_leads));
	$ASCII_text.=" | ".sprintf("%12s", $total_contact_ratio)."%";
	$ASCII_text.=" | ".sprintf("%13s", sec_convert($total_nonpause_time,'H'));
	$ASCII_text.=" | ".sprintf("%11s", sec_convert($total_system_time,'H'));
	$ASCII_text.=" | ".sprintf("%9s", sec_convert($total_talk_time,'H'));
	$ASCII_text.=" | ".sprintf("%5s", $total_sales);
	$sales_per_working_hours=sprintf("%.2f", MathZDC($total_sales, MathZDC($total_nonpause_time, 3600)));
	$ASCII_text.=" | ".sprintf("%22s", $sales_per_working_hours);
	$total_sales_ratio=sprintf("%.2f", MathZDC(100*$total_sales, $total_leads));
	$ASCII_text.=" | ".sprintf("%19s", $total_sales_ratio)."%";
	$total_sale_contact_ratio=sprintf("%.2f", MathZDC(100*$total_sales, $total_contacts));
	$ASCII_text.=" | ".sprintf("%22s", $total_sale_contact_ratio)."%";
	$total_sales_per_hour=sprintf("%.2f", MathZDC($total_sales, $total_talk_hours));
	$total_stcall=sprintf("%.2f", MathZDC($total_calls, $total_leads));
	$ASCII_text.=" | ".sprintf("%14s", $total_sales_per_hour);
	$ASCII_text.=" | ".sprintf("%16s", $total_inc_sales);
	$ASCII_text.=" | ".sprintf("%15s", $total_cnc_sales);
	$ASCII_text.=" | ".sprintf("%9s", $total_callbacks);
	$ASCII_text.=" | ".sprintf("%21s", $total_stcall); 	# first call resolution
	$ASCII_text.=" | ".sprintf("%17s", $total_average_sale_time);
	$ASCII_text.=" | ".sprintf("%20s", $total_average_contact_time)." |";

	$CSV_status_text="";
	for ($q=0; $q<count($call_status_totals_grand_total); $q++) {
		$ASCII_text.=" ".sprintf("%6s", $call_status_totals_grand_total[$q])." |";
		$CSV_status_text.=",\"$call_status_totals_grand_total[$q]\"";
	}
	$ASCII_text.="\n";


	$ASCII_text.="+------------------------------------------+----------------------+-------+-------+----------+---------------+---------------+-------------+-----------+-------+------------------------+----------------------+-------------------------+----------------+------------------+-----------------+-----------+-----------------------+-------------------+----------------------+$HTMLborderheader\n";
	$ASCII_text.="</FONT></PRE>";
	$ASCII_text.="</BODY>\n";
	$ASCII_text.="</HTML>\n";

	$CSV_text.="\"\",\""._QXZ("CALL CENTER TOTAL")."\"\n";
	$CSV_text.="\"\",\""._QXZ("Team Name")."\",\""._QXZ("Team ID")."\",\""._QXZ("Calls")."\",\""._QXZ("Leads")."\",\""._QXZ("Contacts")."\",\""._QXZ("Contact Ratio")."\",\""._QXZ("Nonpause Time")."\",\""._QXZ("System Time")."\",\""._QXZ("Talk Time")."\",\""._QXZ("Sales")."\",\""._QXZ("Sales per Working Hour")."\",\""._QXZ("Sales to Leads Ratio")."\",\""._QXZ("Sales to Contacts Ratio")."\",\""._QXZ("Sales Per Hour")."\",\""._QXZ("Incomplete Sales")."\",\""._QXZ("Cancelled Sales")."\",\""._QXZ("Callbacks")."\",\""._QXZ("First Call Resolution")."\",\""._QXZ("Average Sale Time")."\",\""._QXZ("Average Contact Time")."\"$CSVstatusheader\n";
	$CSV_text.=$GROUP_CSV_text;
	$CSV_text.="\"\",\"\",\""._QXZ("TOTALS").":\",\"$total_calls\",\"$total_leads\",\"$total_contacts\",\"$total_contact_ratio %\",\"".sec_convert($total_nonpause_time,'H')."\",\"".sec_convert($total_system_time,'H')."\",\"".sec_convert($total_talk_time,'H')."\",\"$total_sales\",\"$sales_per_working_hours\",\"$total_sales_ratio\",\"$total_sale_contact_ratio\",\"$total_sales_per_hour\",\"$total_inc_sales\",\"$total_cnc_sales\",\"$total_callbacks\",\"$total_stcall\",\"$total_average_sale_time\",\"$total_average_contact_time\"$CSV_status_text\n";

	# USE THIS FOR COMBINED graphs, use pipe-delimited array elements, dataset_name|index|link_name|graph_override
	# You have to hard code the graph name in where it is overridden and mind the data indices.  No other way to do it.
	$multigraph_text="";
	$graph_id++;
	$graph_array=array("ATPD_TOTALCALLSdata|1|CALLS|integer|", "ATPD_TOTALLEADSdata|2|LEADS|integer|", "ATPD_TOTALCONTACTSdata|3|CONTACTS|integer|", "ATPD_TOTALCONTACTRATIOdata|4|CONTACT RATIO|decimal|", "ATPD_TOTALSYSTEMTIMEdata|5|SYSTEM TIME|time|", "ATPD_TOTALTALKTIMEdata|6|TALK TIME|time|", "ATPD_TOTALSALESdata|7|SALES|integer|", "ATPD_TOTALSALESTOLEADSdata|8|SALES TO LEADS RATIO|decimal|", "ATPD_TOTALSALESTOCONTACTSdata|9|SALES TO CONTACTS RATIO|decimal|", "ATPD_TOTALSALESPERHOURdata|10|SALES PER HOUR|decimal|", "ATPD_TOTALINCOMPLETESALESSdata|11|INCOMPLETE SALES|integer|", "ATPD_TOTALCANCELLEDSALESdata|12|CANCELLED SALES|integer|", "ATPD_TOTALCALLBACKSdata|13|CALLBACKS|integer|", "ATPD_TOTALFIRSTCALLSdata|14|FIRST CALLS|decimal|", "ATPD_TOTALAVGSALETIMEdata|15|AVG SALE TIME|time|", "ATPD_TOTALAVGCONTACTTIMEdata|16|AVG CONTACT TIME|time|");

	for ($e=0; $e<count($call_status); $e++) {
		$Sstatus=$call_status[$e];
		$SstatusTXT=$Sstatus;
		if ($Sstatus=="") {$SstatusTXT="(blank)";}
		array_push($graph_array, "ATPD_SUBSTATUSTOTAL".$e."data|".($e+19)."|".$SstatusTXT."|time|");
	}

	$default_graph="bar"; # Graph that is initally displayed when page loads
	include("graph_color_schemas.inc"); 
	
	$GRAPH="";
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
		for ($d=0; $d<count($total_graph_stats); $d++) {
			$labels.="\"".preg_replace('/ +/', ' ', $total_graph_stats[$d][0])."\",";
			$data.="\"".$total_graph_stats[$d][$dataset_index]."\",";
			$current_graph_total+=$total_graph_stats[$d][$dataset_index];
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
	$graph_title="CALL CENTER TOTAL";
	include("graphcanvas.inc");
	$HTML_head.=$HTML_graph_head;
	$GRAPH.=$graphCanvas;


	$GRAPH_text.=$GRAPH;	
	$GRAPH_text.="</FONT></PRE>";
	$GRAPH_text.="</BODY>\n";
	$GRAPH_text.="</HTML>\n";
	
	}

if ($file_download>0) 
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_team_performance_detail_$US$FILE_TIME.csv";
	$CSV_text=preg_replace('/\n +,/', ',', $CSV_text);
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
	header("Content-type: text/html; charset=utf-8");
	$JS_onload.="}\n";
	if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
	$JS_text.="</script>\n";

	if ($report_display_type=="HTML")
		{
		$HTML_text.=$GRAPH_text;
		$HTML_text.=$JS_text;
		}
	else
		{
		$HTML_text.=$ASCII_text;
		}

	echo $HTML_head;
	$short_header=1;
	require("admin_header.php");
	echo $HTML_text;
	flush();
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
