<?php 
# AST_user_group_hourly_detail.php
#
# Copyright (C) 2019  Joseph Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# Gives hourly count of distinct agents per user group, with totals.
# For single days only
#
# CHANGES
# 160826-0054 - First build
# 170409-1542 - Added IP List validation code
# 170816-2026 - Added HTML formatting
# 170829-0040 - Added screen color settings
# 180507-2315 - Added new help display
# 191013-0855 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["start_hour"]))				{$start_hour=$_GET["start_hour"];}
	elseif (isset($_POST["start_hour"]))	{$start_hour=$_POST["start_hour"];}
if (isset($_GET["end_hour"]))				{$end_hour=$_GET["end_hour"];}
	elseif (isset($_POST["end_hour"]))	{$end_hour=$_POST["end_hour"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user_group"]))					{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))			{$user_group=$_POST["user_group"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'User Group Hourly Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
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
	$SSreport_default_format =		$row[6];
	}
##### END SETTINGS LOOKUP #####

###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_agent_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
	$vicidial_agent_log_table="vicidial_agent_log";
	}
#############

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
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
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
$vuLOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vuLOGadmin_viewable_groupsSQL = "and vicidial_users.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
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
if (!isset($start_hour)) {$start_hour = date("H");}
if (!isset($end_hour)) {$end_hour = date("H");}


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
if ( (preg_match("/--ALL--/",$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	$user_group_SQL = preg_replace("/,\$/",'',$user_group_SQL);
	$user_group_SQL_str=$user_group_SQL;
	$user_group_SQL = "and user_group IN($user_group_SQL)";
	}



$stmt="SELECT campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
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

if ( (preg_match("/--ALL--/",$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	$group_SQL = preg_replace("/,\$/",'',$group_SQL);
	$group_SQL_str=$group_SQL;
	$group_SQL = "and campaign_id IN($group_SQL)";
	}

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date&shift=$shift&DB=$DB&user=$user$groupQS&search_archived_data=$search_archived_data&report_display_type=$report_display_type";

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

if ($file_download < 1)
	{
	echo "<HTML>\n";
	echo "<HEAD>\n";
	echo "<STYLE type=\"text/css\">\n";
	echo "<!--\n";
	echo "   .yellow {color: white; background-color: yellow}\n";
	echo "   .red {color: white; background-color: red}\n";
	echo "   .blue {color: white; background-color: blue}\n";
	echo "   .purple {color: white; background-color: purple}\n";
	echo "-->\n";
	echo " </STYLE>\n";

	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:99;'></div>";

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
	# require("chart_button.php");
	# echo "<script src='chart/Chart.js'></script>\n"; 
	# echo "<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\"  id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";
	echo "<span style=\"position:absolute;left:3px;top:30px;z-index:19;\"  id=agent_status_stats>\n";
	echo "<b>"._QXZ("$report_name")." $NWB#user_group_hourly_detail$NWE</b>\n";
	echo "<PRE><FONT SIZE=2>";
	}



if ($SUBMIT && $query_date && $start_hour && $end_hour) {

	$CSV_text .= "\""._QXZ("$report_name",24).":  $NOW_TIME ($db_source)\"\n\n";
	$CSV_text .= "\""._QXZ("Time range").": $query_date $start_hour:00:00 "._QXZ("to")." $query_date $end_hour:59:59\"\n";
	$CSV_text .= "\""._QXZ("User groups").": ".preg_replace('/\-\-ALL\-\-/', "--"._QXZ("ALL")."--", implode(', ', $user_group))."\"\n";
	$CSV_text .= "\""._QXZ("Campaigns").": ".preg_replace('/\-\-ALL\-\-/', "--"._QXZ("ALL")."--", implode(', ', $group))."\"\n\n";

	$ASCII_text .= _QXZ("$report_name",24).": $user                     $NOW_TIME ($db_source)\n\n";
	$ASCII_text .= _QXZ("Time range").": $query_date $start_hour:00:00 "._QXZ("to")." $query_date $end_hour:59:59\n";
	$ASCII_text .= _QXZ("User groups").": ".preg_replace('/\-\-ALL\-\-/', "--"._QXZ("ALL")."--", implode(', ', $user_group))."\n";
	$ASCII_text .= _QXZ("Campaigns").": ".preg_replace('/\-\-ALL\-\-/', "--"._QXZ("ALL")."--", implode(', ', $group))."\n\n";

	$HTML_text .= _QXZ("$report_name",24).": $user                     $NOW_TIME ($db_source)\n\n";
	$HTML_text .= _QXZ("Time range").": $query_date $start_hour:00:00 "._QXZ("to")." $query_date $end_hour:59:59\n";
	$HTML_text .= _QXZ("User groups").": ".preg_replace('/\-\-ALL\-\-/', "--"._QXZ("ALL")."--", implode(', ', $user_group))."\n";
	$HTML_text .= _QXZ("Campaigns").": ".preg_replace('/\-\-ALL\-\-/', "--"._QXZ("ALL")."--", implode(', ', $group))."\n\n";

	$stmt="select user_group, substr(event_time, 12,2) as hour, count(distinct user) as ct from ".$vicidial_agent_log_table." where event_time>='$query_date $start_hour:00:00' and event_time<='$query_date $end_hour:59:59' $group_SQL $user_group_SQL group by user_group, hour order by hour, user_group";
	if ($DB) {$ASCII_text.=$stmt."\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	# select user_group, substr(event_time, 12,2) as hour, count(distinct user) as ct from ".$vicidial_agent_log_table." where ((event_time>='$query_date $start_hour:00:00' and event_time<='$query_date $end_hour:59:59') or  (event_time+INTERVAL (pause_sec+wait_sec+talk_sec+dispo_sec) SECOND>='$query_date $start_hour:00:00' and event_time+INTERVAL (pause_sec+wait_sec+talk_sec+dispo_sec) SECOND<='$query_date $end_hour:59:59')) $group_SQL $user_group_SQL group by user_group, hour order by user_group, hour

	$user_group_array=array();
	$hour_array=array();
	$hour_total_array=array();
	$usergroup_total_array=array();
	while($row=mysqli_fetch_array($rslt)) {
		$user_group_array[$row["user_group"]]+=$row["ct"];
		$hour_array[$row["hour"]]+=$row["ct"];
		$hour_total_array[$row["user_group"]][$row["hour"]]+=$row["ct"];  # For ASCII/CSV output
		$usergroup_total_array[$row["hour"]][$row["user_group"]]+=$row["ct"]; # For GRAPH output
	}

	$total_stmt="select user_group, count(distinct user) as ct from ".$vicidial_agent_log_table." where event_time>='$query_date $start_hour:00:00' and event_time<='$query_date $end_hour:59:59' $group_SQL $user_group_SQL group by user_group order by user_group";
	$total_rslt=mysql_to_mysqli($total_stmt, $link);
	$total_array=array();
	while ($total_row=mysqli_fetch_array($total_rslt)) {
		$total_array[$total_row["user_group"]]+=$total_row["ct"];
	}

	$grand_total_stmt="select distinct user from ".$vicidial_agent_log_table." where event_time>='$query_date $start_hour:00:00' and event_time<='$query_date $end_hour:59:59' $group_SQL $user_group_SQL";
	$grand_total_rslt=mysql_to_mysqli($grand_total_stmt, $link);
	$grand_total=mysqli_num_rows($grand_total_rslt);

	$ASCII_header ="+----------------------+--------+";
	$ASCII_title .="| "._QXZ("USER GROUP", 20)." | "._QXZ("AGENTS", 6)." |";
	$ASCII_total .="|               "._QXZ("TOTALS", 6)." | ".sprintf("%6s", $grand_total)." |";

	$table_columns=2;
	$HTML_text.="<table border='0' cellpadding='3' cellspacing='1'>";
	$HTML_text.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML_text.="<th><font size='2'>"._QXZ("USER GROUP")."</font></th>";
	$HTML_text.="<th><font size='2'>"._QXZ("AGENTS")."</font></th>";

	$HTML_text2.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML_text2.="<th><font size='2'>"._QXZ("TOTALS")."</font></th>";
	$HTML_text2.="<th><font size='2'>".$grand_total."</font></th>";

	$CSV_text.="\""._QXZ("USER GROUP")."\",\""._QXZ("AGENTS")."\"";
	$CSV_total.="\""._QXZ("TOTALS")."\",\"$grand_total\"";

	# while (list($key, $val)=each($hour_array)) {	
	foreach($hour_array as $key => $val) {
		$ASCII_title.=" ".date("ha", strtotime("$key:00"))." to ";
		$key1=$key+1;
		$ASCII_title.=date("ha", strtotime("$key1:00"))." |";
		$ASCII_header.="--------------+";
		$ASCII_total.=" ".sprintf("%12s", ($hour_array[$key]+0))." |";

		$HTML_text.="<th><font size='2'>".date("ha", strtotime("$key:00"))." to ".date("ha", strtotime("$key1:00"))."</font></th>";
		$HTML_text2.="<th><font size='2'>".($hour_array[$key]+0)."</font></th>";
		$table_columns++;

		$CSV_text.=",\"".date("ha", strtotime("$key:00"))." to ".date("ha", strtotime("$key1:00"))."\"";
		$CSV_total.=",\"".($hour_array[$key]+0)."\"";
	}
	reset($hour_array);
	reset($user_group_array);

	# print_r($user_group_array);
	# print_r($hour_array);
	# print_r($total_array);

	$ASCII_text.=$ASCII_header."\n";
	$ASCII_text.=$ASCII_title."\n";
	$ASCII_text.=$ASCII_header."\n";

	$HTML_text.="</tr>\n";

	$CSV_text.="\n";

	#while (list($key, $val)=each($user_group_array)) {
	foreach ($user_group_array as $key => $val) {
		$ASCII_text.="| ".sprintf("%20s", $key)." | ".sprintf("%6s", $total_array[$key])." |";
		$HTML_text.="<tr bgcolor='#".$SSstd_row2_background."'>";
		$HTML_text.="<td><font size='2'>".$key."&nbsp;</font></td>";
		$HTML_text.="<td><font size='2'>".$total_array[$key]."&nbsp;</font></td>";

		$CSV_text.="\"$key\",\"".$total_array[$key]."\"";
		# while (list($key2, $val2)=each($hour_array)) {	
		foreach($hour_array as $key2 => $val2) {
			$ASCII_text.=" ".sprintf("%12s", ($hour_total_array[$key][$key2]+0))." |";
			$HTML_text.="<td><font size='2'>".($hour_total_array[$key][$key2]+0)."</font></td>";
			$CSV_text.=",\"".($hour_total_array[$key][$key2]+0)."\"";
		}
		reset($hour_array);
		$CSV_text.="\n";
		$HTML_text.="</tr>\n";
		$ASCII_text.="\n";

	}

	$ASCII_text.=$ASCII_header."\n";
	$ASCII_text.=$ASCII_total."\n";
	$ASCII_text.=$ASCII_header."\n";

	$HTML_text2.="</tr>";
	$HTML_text2.="</table>";
	$HTML_text.=$HTML_text2;

	$CSV_text.=$CSV_total."\n";
}


if ($file_download > 0)
	{
	$US='_';
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "USER_GROUP_HOURLY_DETAIL_$US$FILE_TIME.csv";

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
	}


echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>";
echo "<TABLE CELLSPACING=3 CELLPADDING=3 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Date").":<BR>";
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

echo "<TD VALIGN=TOP ROWSPAN=2> "._QXZ("Campaigns").":<BR>";
echo "<SELECT SIZE=5 NAME=group[] multiple>\n";
if (is_array($group))
	{
	if  (in_array('--ALL--',$group))
		{echo "<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
	else
		{echo "<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
	}
	else
		{echo "<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
	{
	if (is_array($group))
		{
		if (in_array("$groups[$o]",$group)) {echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
		else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
		}
	else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";

echo "</TD><TD VALIGN=TOP ROWSPAN=2>"._QXZ("Teams/User Groups").":<BR>";
echo "<SELECT SIZE=5 NAME=user_group[] multiple>\n";

if (is_array($user_group))
	{
	if  (in_array('--ALL--',$user_group))
		{echo "<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
	else
		{echo "<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
	}
else
	{echo "<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if (is_array($user_group))
		{
		if  (in_array("$user_groups[$o]",$user_group)) 
			{echo "<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
		else 
			{echo "<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
		}
	else 
		{echo "<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";
echo "</TD>\n";

echo "<TD VALIGN=TOP ROWSPAN=2>\n";

echo _QXZ("Display as:")."<BR>";
echo "<select name='report_display_type'>";
if ($report_display_type) {echo "<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
echo "<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";

if ($archives_available=="Y") 
	{
	echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

echo "<BR><BR><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD><TD VALIGN=TOP ROWSPAN=2> &nbsp; &nbsp; &nbsp; &nbsp; ";

echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
echo "<a href=\"$PHP_SELF?DB=$DB&query_date=$query_date&start_hour=$start_hour&end_hour=$end_hour$groupQS$user_groupQS$call_statusQS&file_download=1&SUBMIT=$SUBMIT&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a> |";
echo " <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> | <a href=\"./AST_user_group_hourly_detail_v2.php?DB=$DB&query_date=$query_date&start_hour=$start_hour&end_hour=$end_hour$groupQS$user_groupQS$call_statusQS&SUBMIT=$SUBMIT&search_archived_data=$search_archived_data\">v2</a></FONT>\n";
echo "</FONT>\n";
echo "</TD></TR>";
echo "<TR><TD ALIGN=RIGHT>"._QXZ("Start time").": <select name='start_hour'>";
for ($h=0; $h<=23; $h++) {
	if ($h==$start_hour) {$s="selected";} else {$s="";}
	echo "<option value='".substr("0$h", -2)."' $s>".substr("0$h", -2)."</option>\n";
}
echo "</select>:00:00";

echo "<BR>"._QXZ("End time").": <select name='end_hour'>";
for ($h=0; $h<=23; $h++) {
	if ($h==$end_hour) {$s="selected";} else {$s="";}
	echo "<option value='".substr("0$h", -2)."' $s>".substr("0$h", -2)."</option>\n";
}
echo "</select>:59:59";
echo "</TD></TR>";
echo "</TABLE>";

echo "</FORM>";

if ($report_display_type=="HTML")
	{
	echo $HTML_text;
	}
else
	{
	echo $ASCII_text;
	}


echo "</span>\n";
?>
