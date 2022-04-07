<?php 
# AST_agent_inbound_status.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 170913-0853 - First build based upon AST_agent_status_detail.php
# 191013-0841 - Fixes for PHP7
# 220303-1503 - Added allow_web_debug system setting
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
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["show_defunct_users"]))				{$show_defunct_users=$_GET["show_defunct_users"];}
	elseif (isset($_POST["show_defunct_users"]))	{$show_defunct_users=$_POST["show_defunct_users"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($user_group)) {$user_group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Agent Inbound Status Summary';
$db_source = 'M';

$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,report_default_format,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$SSreport_default_format =		$row[4];
	$SSallow_web_debug =			$row[5];
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
$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);
$show_defunct_users = preg_replace('/[^-_0-9a-zA-Z]/', '', $show_defunct_users);

# Variables filtered further down in the code
# $group
# $user_group

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

if ($search_archived_data=="checked") {$closer_log_table="vicidial_closer_log_archive";} else {$closer_log_table="vicidial_closer_log";}

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
		$VDdisplayMESSAGE = "You are not allowed to view reports";
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
	$VDdisplayMESSAGE = "Login incorrect, please try again";
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = "Too many login attempts, try again in 15 minutes";
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
    echo "You are not allowed to view this report: |$PHP_AUTH_USER|$report_name|\n";
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


$stmt="select group_id,group_name from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
# $LISTgroups[$i]='---NONE---';
# $i++;
# $groups_to_print++;
$groups_string='|';
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTgroups[$i] =		$row[0];
	$LISTgroup_names[$i] =	$row[1];
#	$LISTgroup_ids[$i] =	$row[2];
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
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
if (strlen($group_SQL)<3) {$group_SQL="''";}
$group_SQL=" and campaign_id in ($group_SQL) ";
if (in_array('--ALL--', $group)) {
	$group=array("--ALL--");
	$group_string="--ALL--|";
	$group_SQL="";
	$groupQS="&group[]=--ALL--";
}

#for ($i=0; $i<count($user_group); $i++)
#	{
#	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1; $user_group="";}
#	}

$stmt="SELECT user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $user_group[$i]);
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
	$user_group_SQL = "and ".$closer_log_table.".user_group IN($user_group_SQL)";
	}

if ($DB) {echo "$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

$stmt="SELECT vsc_id,vsc_name from vicidial_status_categories;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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

$customer_interactive_statuses='';
$stmt="SELECT status from vicidial_statuses where human_answered='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$customer_interactive_statuses .= "|$row[0]";
	$i++;
	}
$stmt="SELECT status from vicidial_campaign_statuses where human_answered='Y' $LOGallowed_campaignsSQL;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$customer_interactive_statuses .= "|$row[0]";
	$i++;
	}
if (strlen($customer_interactive_statuses)>0)
	{$customer_interactive_statuses .= '|';}


$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date$groupQS$user_groupQS&shift=$shift&search_archived_data=$search_archived_data&show_defunct_users=$show_defunct_users&report_display_type=$report_display_type&DB=$DB";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
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
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
	require("chart_button.php");
	echo "<script src='chart/Chart.js'></script>\n"; 
	echo "<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:21;'></div>";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\" id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";
	echo "<span style=\"position:absolute;left:3px;top:30px;z-index:19;\" id=agent_status_stats>\n";
	echo "<b>"._QXZ("$report_name")."</b> $NWB#agent_status_detail$NWE\n";
	echo "<PRE><FONT SIZE=2>";
	}

if ( (strlen($group[0]) < 1) or (strlen($user_group[0]) < 1) )
	{
	echo "<PRE><FONT SIZE=2>";
	echo _QXZ("PLEASE SELECT A CAMPAIGN OR USER GROUP AND DATE-TIME BELOW AND CLICK SUBMIT")."\n";
	echo " "._QXZ("NOTE: stats taken from shift specified")."\n";
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

	if ($file_download < 1)
		{
		$ASCII_text.=_QXZ("$report_name")." "._QXZ("Report")."                     $NOW_TIME ($db_source)\n";
		$ASCII_text.=_QXZ("Time range").": $query_date_BEGIN to $query_date_END\n\n";
		$HTML_text.=_QXZ("$report_name")." "._QXZ("Report")."                     $NOW_TIME ($db_source)\n";
		$HTML_text.=_QXZ("Time range").": $query_date_BEGIN to $query_date_END\n\n";
		}
	else
		{
		$file_output .= _QXZ("$report_name")." "._QXZ("Report")."                     $NOW_TIME\n";
		$file_output .= _QXZ("Time range").": $query_date_BEGIN to $query_date_END\n\n";
		}

	$statuses='-';
	$statusesTXT='';
	$statusesHEAD='';
	$statusesHTML='';
	$statusesFILE='';
	$statusesARY=array();
	$statusesARY[0]='';
	$j=0;
	$users='-';
	$usersARY=array();
	$usersARY[0]='';
	$user_namesARY=array();
	$user_namesARY[0]='';
	$k=0;

	if ($show_defunct_users) 
		{
		$user_stmt="SELECT distinct '' as full_name, user from ".$closer_log_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN'  and status is not null $group_SQL $user_group_SQL order by user asc";
		}
	else
		{
		$user_stmt="SELECT distinct full_name,vicidial_users.user from vicidial_users,".$closer_log_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' and vicidial_users.user=".$closer_log_table.".user and status is not null $group_SQL $user_group_SQL order by full_name asc";
		}

		if ($DB) {echo "$user_stmt\n";}
	$user_rslt=mysql_to_mysqli($user_stmt, $link);
	$q=0;
	$calls=array();
	$full_name=array();
	$user=array();
	$status=array();
	while($q<mysqli_num_rows($user_rslt)) 
		{
		$user_row=mysqli_fetch_row($user_rslt);

		if ($show_defunct_users)
			{
			$defunct_user_stmt="SELECT full_name,user_group from vicidial_users where user='$user_row[1]'";
			$defunct_user_rslt=mysql_to_mysqli($defunct_user_stmt, $link);
			if (mysqli_num_rows($defunct_user_rslt)>0) 
				{
				$defunct_user_row=mysqli_fetch_row($defunct_user_rslt);
				$full_name_val=$defunct_user_row[0];
				} 
			else 
				{
				$full_name_val=$user_row[1];
				}
			}
		else 
			{
			$full_name_val=$user_row[0];
			}

		$full_name[$q] =	$full_name_val;
		$user[$q] =			$user_row[1];

		if (!preg_match("/\-$user[$q]\-/i", $users))
			{
			$users .= "$user[$q]-";
			$usersARY[$k] = $user[$q];
			$user_namesARY[$k] = $full_name[$q];
			$k++;
			}
		$q++;
		}

	if ($show_defunct_users) 
		{
		$status_stmt="SELECT distinct status from ".$closer_log_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' and status is not null $group_SQL $user_group_SQL order by status";
		}
	else
		{
		$status_stmt="SELECT distinct status from vicidial_users,".$closer_log_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' and vicidial_users.user=".$closer_log_table.".user $group_SQL $user_group_SQL order by status";
		}

		if ($DB) {echo "$status_stmt\n";}
	$status_rslt=mysql_to_mysqli($status_stmt, $link);
	$q=0;
	$status_rows_to_print=0; $sub_status_count=0;
	while($status_row=mysqli_fetch_row($status_rslt)) 
		{
		$current_status=$status_row[0];

		if ($show_defunct_users) 
			{
			$stmt="SELECT count(*) as calls,'' as full_name,user,status from ".$closer_log_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' and status='$current_status' $group_SQL $user_group_SQL group by user,full_name,status order by full_name,user,status desc limit 500000;";
			}
		else
			{
			$stmt="SELECT count(*) as calls,full_name,vicidial_users.user,status from vicidial_users,".$closer_log_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' and vicidial_users.user=".$closer_log_table.".user and status='$current_status' $group_SQL $user_group_SQL group by user,full_name,status order by full_name,user,status desc limit 500000;";
			}
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$status_rows_to_print = mysqli_num_rows($rslt);
		$rows_to_print+=$status_rows_to_print;
		$i=0; 
		while ($i < $status_rows_to_print)
			{
			$row=mysqli_fetch_row($rslt);

			if ($show_defunct_users)
				{
				$defunct_user_stmt="SELECT full_name,user_group from vicidial_users where user='$row[2]'";
				$defunct_user_rslt=mysql_to_mysqli($defunct_user_stmt, $link);
				if (mysqli_num_rows($defunct_user_rslt)>0) 
					{
					$defunct_user_row=mysqli_fetch_row($defunct_user_rslt);
					$full_name_val=$defunct_user_row[0];
					} 
				else 
					{
					$full_name_val=$row[2];
					}
				}
			else 
				{
				$full_name_val=$row[1];
				}


			if ( ($row[0] > 0) and (strlen($row[2]) > 0) and (strlen($row[3]) > 0) and (!preg_match("/NULL/i",$row[3])))
				{
				$calls[$q] =		$row[0];
				$full_name[$q] =	$full_name_val;
				$user[$q] =			$row[2];
				$status[$q] =		$row[3];
				if ( (!preg_match("/-$status[$q]-/i", $statuses)) and (strlen($status[$q])>0) )
					{
					$statusesTXT = sprintf("%8s", $status[$q]);
					$statusesHEAD .= "----------+";
					$statusesHTML .= " $statusesTXT |";
					$HTML_statuses .= "<th>&nbsp;<font size=2 face=\"Arial,Helvetica\">$statusesTXT&nbsp;</th>";
					$statusesFILE .= "$statusesTXT,";
					$statuses .= "$status[$q]-";
					$statusesARY[$j] = $status[$q];

					$sub_statusesARY[$sub_status_count] = $status[$q];
					$sub_status_count++;
					$max_varname="max_".$status[$q];
					$$max_varname=1;

					$j++;
					}
				if (!preg_match("/\-$user[$q]\-/i", $users))
					{
					$users .= "$user[$q]-";
					$usersARY[$k] = $user[$q];
					$user_namesARY[$k] = $full_name[$q];
					$k++;
					}
				}
			$i++;
			$q++;
			}
		}

	if ($file_download < 1)
		{
		$ASCII_text.=_QXZ("INBOUND CALLS STATS BREAKDOWN").":\n";
		$ASCII_text.="+---------------------------+----------+--------+--------+--------+$statusesHEAD\n";
		$ASCII_text.="| <a href=\"$LINKbase\">"._QXZ("USER NAME", 9)."</a>                 | <a href=\"$LINKbase&stage=ID\">"._QXZ("ID", 2)."</a>       | <a href=\"$LINKbase&stage=LEADS\">"._QXZ("CALLS", 5)."</a>  | <a href=\"$LINKbase&stage=CI\">"._QXZ("CIcalls", 7)."</a>| <a href=\"$LINKbase&stage=DNCCI\">DNC/CI%</a>|$statusesHTML\n";
		$ASCII_text.="+---------------------------+----------+--------+--------+--------+$statusesHEAD\n";

		$HTML_text.=_QXZ("INBOUND CALLS STATS BREAKDOWN").":</PRE>\n";
		$HTML_text.="<table border='0' cellpadding=2 cellspacing='0'>";
		$HTML_text.="<tr bgcolor='".$SSframe_background."'>";
		$HTML_text.="<th><font size=2 face=\"Arial,Helvetica\">&nbsp;<a href=\"$LINKbase\">"._QXZ("USER NAME")."</a>&nbsp;</th>";
		$HTML_text.="<th><font size=2 face=\"Arial,Helvetica\">&nbsp;<a href=\"$LINKbase&stage=ID\">"._QXZ("ID")."</a>&nbsp;</th>";
		$HTML_text.="<th><font size=2 face=\"Arial,Helvetica\">&nbsp;<a href=\"$LINKbase&stage=LEADS\">"._QXZ("CALLS")."</a>&nbsp;</th>";
		$HTML_text.="<th><font size=2 face=\"Arial,Helvetica\">&nbsp;<a href=\"$LINKbase&stage=CI\">"._QXZ("CIcalls")."</a>&nbsp;</th>";
		$HTML_text.="<th><font size=2 face=\"Arial,Helvetica\">&nbsp;<a href=\"$LINKbase&stage=DNCCI\">DNC/CI%</a>&nbsp;</th>";
		$HTML_text.=$HTML_statuses;
		$HTML_text.="</tr>";

		}
	else
		{
		$file_output .= _QXZ("USER").","._QXZ("ID").","._QXZ("CALLS").","._QXZ("CIcalls").",DNC-CI%,$statusesFILE\n";
		}


	### BEGIN loop through each user ###
	$m=0;
	$TOPsorted_output=array();
	$TOPsorted_outputFILE=array();
	$TOPsorted_outputHTML=array();
	$TOPsort=array();
	$TOPsortTALLY=array();
	$CIScountTOT=0;
	$DNCcountTOT=0;

	$graph_stats=array();
	$max_calls=1;
	$max_cicalls=1;
	$max_dncci=1;

	while ($m < $k)
		{
		$Suser=$usersARY[$m];
		$Sfull_name=$user_namesARY[$m];
		$Scalls=0;
		$SstatusesHTML='';
		$SstatusesHTMLpct='';
		$SHTML_statuses='';
		$SHTML_statusespct='';
		$SstatusesFILE='';
		$SstatusesFILEpct='';
		$CIScount=0;
		$DNCcount=0;

		### BEGIN loop through each status ###
		$n=0;
		while ($n < $j)
			{
			$Sstatus=$statusesARY[$n];
			$SstatusTXT='';
			$varname=$Sstatus."_graph";
			$$varname=$graph_header."<th class='thgraph' scope='col'>$Sstatus</th></tr>";
			$max_varname="max_".$Sstatus;
			### BEGIN loop through each stat line ###
			$i=0; $status_found=0;
			while ($i < $rows_to_print)
				{
				if ( ($Suser=="$user[$i]") and ($Sstatus=="$status[$i]") )
					{
					$Scalls =		($Scalls + $calls[$i]);
					if (preg_match("/\|$status[$i]\|/i",$customer_interactive_statuses))
						{
						$CIScount =	($CIScount + $calls[$i]);
						$CIScountTOT =	($CIScountTOT + $calls[$i]);
						}
					if (preg_match("/DNC/i", $status[$i]))
						{
						$DNCcount =	($DNCcount + $calls[$i]);
						$DNCcountTOT =	($DNCcountTOT + $calls[$i]);
						}

					if ($calls[$i]>$$max_varname) {$$max_varname=$calls[$i];}
					$graph_stats[$m][(4+$n)]=$calls[$i];					

					$SstatusTXT = sprintf("%8s", $calls[$i]);
					$SstatusesHTML .= " $SstatusTXT |";
					$SHTML_statuses .= "<td align='center'><font size=1 face=\"Arial,Helvetica\">$calls[$i]</td>";
					$SstatusesFILE .= "$calls[$i],";
					$status_found++;
					}
				$i++;
				}
			if ($status_found < 1)
				{
				$graph_stats[$m][(4+$n)]=0;
				$SstatusesHTML .= "        0 |";
				$SHTML_statuses .= "<td align='center'><font size=1 face=\"Arial,Helvetica\">0</td>";
				$SstatusesFILE .= "0,";
				}
			### END loop through each stat line ###
			$n++;
			}
		### END loop through each status ###
		$TOTcalls=($TOTcalls + $Scalls);

		$RAWuser = $Suser;
		$RAWcalls = $Scalls;
		$RAWcis = $CIScount;
		$Scalls =	sprintf("%6s", $Scalls);
		$CIScount =	sprintf("%6s", $CIScount);

		### BEGIN loop through each status again to get the damn percentages ###
		$n=0;
		while ($n < $j)
			{
			$Sstatus=$statusesARY[$n];
			### BEGIN loop through each stat line ###
			$i=0; $status_found=0;
			while ($i < $rows_to_print)
				{
				if ( ($Suser=="$user[$i]") and ($Sstatus=="$status[$i]") )
					{
					$SstatusTXTpct = sprintf("%6.1f", (100*($calls[$i]/$Scalls))); # 
					$SstatusesHTMLpct .= " $SstatusTXTpct % |";
					$SHTML_statusespct .= "<td align='center'><font size=1 face=\"Arial,Helvetica\">(".trim($SstatusTXTpct)."%)</td>";
					$SstatusesFILEpct .= trim($SstatusTXTpct)."%,";
					$status_found++;
					}
				$i++;
				}
			if ($status_found < 1)
				{
				$SstatusesHTMLpct .= "    0.0 % |";
				$SHTML_statusespct .= "<td align='center'><font size=1 face=\"Arial,Helvetica\">(0.0 %)</td>";
				$SstatusesFILEpct .= "0.0%,";
				}
			### END loop through each stat line ###
			$n++;
			}
		### END loop through each status ###

		if ($file_download<1) 
			{
			if ($non_latin < 1)
				{
				 $Sfull_name=	sprintf("%-25s", $Sfull_name); 
					while(strlen($Sfull_name)>25) {$Sfull_name = substr("$Sfull_name", 0, -1);}
				 $Suser =		sprintf("%-8s", $Suser);
					while(strlen($Suser)>8) {$Suser = substr("$Suser", 0, -1);}
				}
			else
				{	
					$Sfull_name=	sprintf("%-75s", $Sfull_name); 
				 while(mb_strlen($Sfull_name,'utf-8')>25) {$Sfull_name = mb_substr("$Sfull_name", 0, -1,'utf-8');}

					$Suser =	sprintf("%-24s", $Suser);
				 while(mb_strlen($Suser,'utf-8')>8) {$Suser = mb_substr("$Suser", 0, -1,'utf-8');}
				}
			}
		$DNCcountPCTs = ( MathZDC($DNCcount, $CIScount) * 100);
		$RAWdncPCT = $DNCcountPCTs;
	#	$DNCcountPCTs = round($DNCcountPCTs,2);
		$DNCcountPCTs = round($DNCcountPCTs);
		$rawDNCcountPCTs = $DNCcountPCTs;
	#	$DNCcountPCTs = sprintf("%3.2f", $DNCcountPCTs);
		$DNCcountPCTs = sprintf("%6s", $DNCcountPCTs);

		if (trim($Scalls)>$max_calls) {$max_calls=trim($Scalls);}
		if (trim($CIScount)>$max_cicalls) {$max_cicalls=trim($CIScount);}
		if (trim($DNCcountPCTs)>$max_dncci) {$max_dncci=trim($DNCcountPCTs);}
		$graph_stats[$m][1]=trim("$Scalls");
		$graph_stats[$m][2]=trim("$CIScount");
		$graph_stats[$m][3]=trim("$DNCcountPCTs");

		if ($file_download < 1)
			{
			$Toutput  = "| $Sfull_name | <a href=\"./user_stats.php?user=$RAWuser\">$Suser</a> | $Scalls | $CIScount | $DNCcountPCTs%|$SstatusesHTML\n";
			$Toutput .= "| ".sprintf("%-25s", " ")." | ".sprintf("%-8s", " ")." | ".sprintf("%-6s", " ")." | ".sprintf("%-6s", " ")." | ".sprintf("%-6s", " ")." |$SstatusesHTMLpct\n";
			$Toutput .= "+---------------------------+----------+--------+--------+--------+$statusesHEAD\n";

			if ($m%2==0) {$bgcolor=$SSstd_row2_background;} else {$bgcolor=$SSstd_row1_background;}
			$ToutputHTML  = "<tr bgcolor='$bgcolor'><td align='center'><font size=2 face=\"Arial,Helvetica\">$Sfull_name</td><td align='center'><a href=\"./user_stats.php?user=$RAWuser\"><font size=2 face=\"Arial,Helvetica\">$Suser</a></td><td align='center'><font size=2 face=\"Arial,Helvetica\">$Scalls</td><td align='center'><font size=2 face=\"Arial,Helvetica\">$CIScount</td><td align='center'><font size=2 face=\"Arial,Helvetica\">$DNCcountPCTs%</td>$SHTML_statuses</tr>\n";
			$ToutputHTML .= "<tr bgcolor='$bgcolor'><td colspan='5'><font size=1 face=\"Arial,Helvetica\">&nbsp;</td>$SHTML_statusespct</tr>\n";
			
			# $graph_stats[$m][0]=trim("$user_namesARY[$m] - $usersARY[$m]");
			}
		else
			{
			$fileToutput = "$Sfull_name,$RAWuser,$RAWcalls,$RAWcis,$rawDNCcountPCTs%,$SstatusesFILE\n";
			$fileToutput .= ",,,,,$SstatusesFILEpct\n";
			}

		$TOPsorted_output[$m] = $Toutput;
		$TOPsorted_outputFILE[$m] = $fileToutput;
		$TOPsorted_outputHTML[$m] = $ToutputHTML;

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
			$TOPsort[$m] =	'' . sprintf("%08s", $Stime) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$Stime;
			}
		if ($stage == 'CI')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWcis) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWcis;
			}
		if ($stage == 'DNCCI')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWdncPCT) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWdncPCT;
			}
		if (!preg_match('/ID|TIME|LEADS|CI|DNCCI/',$stage))
			if ($file_download < 1)
				{
				$ASCII_text.="$Toutput";
				$HTML_text.="$ToutputHTML";
				}
			else
				{$file_output .= "$fileToutput";}

		if ($TOPsortMAX < $TOPsortTALLY[$m]) {$TOPsortMAX = $TOPsortTALLY[$m];}

		$m++;
		}
	### END loop through each user ###

	$TOT_AGENTS = sprintf("%4s", $m);


	### BEGIN sort through output to display properly ###
	if (preg_match('/ID|TIME|LEADS|CI|DNCCI/',$stage))
		{
		if (preg_match('/ID/',$stage))
			{sort($TOPsort, SORT_NUMERIC);}
		if (preg_match('/TIME|LEADS|CI|DNCCI/',$stage))
			{rsort($TOPsort, SORT_NUMERIC);}

		$m=0;
		while ($m < $k)
			{
			$sort_split = explode("-----",$TOPsort[$m]);
			$i = $sort_split[1];
			$sort_order[$m] = "$i";
			if ($file_download < 1)
				{
				$ASCII_text.="$TOPsorted_output[$i]";
				$HTML_text.="$TOPsorted_outputHTML[$i]";
				}
			else
				{$file_output .= "$TOPsorted_outputFILE[$i]";}
			$m++;
			}
		}
	### END sort through output to display properly ###



	###### LAST LINE FORMATTING ##########
	### BEGIN loop through each status ###
	$SUMstatusesHTML='';
	$SUMstatusesFILE='';
	$SUMHTML_statuses='';
	$SUMstatusesHTMLpct='';
	$SUMstatusesFILEpct='';
	$SUMHTML_statusespct='';
	$n=0;
	while ($n < $j)
		{
		$Scalls=0;
		$Sstatus=$statusesARY[$n];
		$SUMstatusTXT='';
		$total_var=$Sstatus."_total";
		### BEGIN loop through each stat line ###
		$i=0; $status_found=0;
		while ($i < $rows_to_print)
			{
			if ($Sstatus=="$status[$i]")
				{
				$Scalls =		($Scalls + $calls[$i]);
				$status_found++;
				}
			$i++;
			}
		### END loop through each stat line ###
		if ($status_found < 1)
			{
			$SUMstatusesHTML .= "        0 |";
			$$total_var="0";
			}
		else
			{
			$SUMstatusTXT = sprintf("%8s", $Scalls);
			$SUMstatusesHTML .= " $SUMstatusTXT |";
			$SUMHTML_statuses .= "<th><font size=1 face=\"Arial,Helvetica\">$SUMstatusTXT</th>";
			$SUMstatusesFILE .= "$SUMstatusTXT,";
			$$total_var=$Scalls;
			}
		$n++;
		}
	### END loop through each status ###

	$n=0;
	while ($n < $j)
		{
		$Scalls=0;
		$Sstatus=$statusesARY[$n];
		### BEGIN loop through each stat line ###
		$i=0; $status_found=0;
		while ($i < $rows_to_print)
			{
			if ($Sstatus=="$status[$i]")
				{
				$Scalls =		($Scalls + $calls[$i]);
				$status_found++;
				}
			$i++;
			}
		### END loop through each stat line ###
		if ($status_found < 1)
			{
			$SUMstatusesHTMLpct .= "     0.0% |";
			$SUMstatusesFILEpct .= "0.0%,";
			$SUMHTML_statusespct .= "<th><font size=1 face=\"Arial,Helvetica\">(0.0%)</th>";
			}
		else
			{
			$SstatusTXTpct = sprintf("%6.1f", (100*($Scalls/$TOTcalls))); # 
			$SUMstatusesHTMLpct .= " $SstatusTXTpct % |";
			$SUMHTML_statusespct .= "<th><font size=1 face=\"Arial,Helvetica\">(".trim($SstatusTXTpct)."%)</th>";
			$SUMstatusesFILEpct .= trim($SstatusTXTpct)."%,";
			}
		$n++;
		}
	### END loop through each status ###

	$TOTcalls = sprintf("%7s", $TOTcalls);
	$CIScountTOT = sprintf("%7s", $CIScountTOT);
	$DNCcountPCT = ( MathZDC($DNCcountTOT, $CIScountTOT) * 100);
	$DNCcountPCT = round($DNCcountPCT,2);
	$DNCcountPCT = sprintf("%3.2f", $DNCcountPCT);
	$DNCcountPCT = ( MathZDC($DNCcountTOT, $CIScountTOT) * 100);
	#$DNCcountPCT = round($DNCcountPCT,2);
	$DNCcountPCT = round($DNCcountPCT);
	#$DNCcountPCT = sprintf("%3.2f", $DNCcountPCT);
	$DNCcountPCT = sprintf("%6s", $DNCcountPCT);

	if ($file_download < 1)
		{
		$ASCII_text.="+---------------------------+----------+--------+--------+--------+$statusesHEAD\n";
		$ASCII_text.="|  "._QXZ("TOTALS", 6)."        "._QXZ("AGENTS", 6).":$TOT_AGENTS           | $TOTcalls| $CIScountTOT| $DNCcountPCT%|$SUMstatusesHTML\n";
		$ASCII_text.="| ".sprintf("%-36s", " ")." | ".sprintf("%-6s", " ")." | ".sprintf("%-6s", " ")." | ".sprintf("%-6s", " ")." |$SUMstatusesHTMLpct\n";
		$ASCII_text.="+--------------------------------------+--------+--------+--------+$statusesHEAD\n";

		$ASCII_text.="\n\n</PRE>";

		$HTML_text.="<tr bgcolor='".$SSframe_background."'>";
		$HTML_text.="<th colspan='2'><font size=2 face=\"Arial,Helvetica\">"._QXZ("TOTALS")."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"._QXZ("AGENTS").":$TOT_AGENTS</th>";
		$HTML_text.="<th><font size=2 face=\"Arial,Helvetica\">$TOTcalls</th>";
		$HTML_text.="<th><font size=2 face=\"Arial,Helvetica\">$CIScountTOT</th>";
		$HTML_text.="<th><font size=2 face=\"Arial,Helvetica\">$DNCcountPCT%</th>";
		$HTML_text.=$SUMHTML_statuses;
		$HTML_text.="</tr>";
		$HTML_text.="<tr bgcolor='".$SSframe_background."'>";
		$HTML_text.="<th colspan='5'><font size=1 face=\"Arial,Helvetica\">&nbsp;</th>";
		$HTML_text.=$SUMHTML_statusespct;
		$HTML_text.="</tr>";
		$HTML_text.="</table>";

/*
		# USE THIS FOR COMBINED graphs, use pipe-delimited array elements, dataset_name|index|link_name|graph_override
		# You have to hard code the graph name in where it is overridden and mind the data indices.  No other way to do it.
		$multigraph_text="";
		$graph_id++;
		$graph_array=array("ASD_CALLSdata|1|CALLS|integer|", "ASD_CICALLSdata|2|CI/CALLS|integer|", "ASD_DNCCIdata|3|DNC/CI|percent|");

		for ($e=0; $e<count($sub_statusesARY); $e++) {
			$Sstatus=$sub_statusesARY[$e];
			$SstatusTXT=$Sstatus;
			if ($Sstatus=="") {$SstatusTXT="(blank)";}
			array_push($graph_array, "ASD_SUBSTATUS".$e."data|".($e+4)."|".$SstatusTXT."|integer|");
		}

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
		$graph_title=_QXZ("AGENT STATUS DETAIL REPORT");
		include("graphcanvas.inc");
		$GRAPH.=$graphCanvas;
*/
		}
	else
		{
		$file_output .= _QXZ("TOTALS").",$TOT_AGENTS,$TOTcalls,$CIScountTOT,$DNCcountPCT%,$SUMstatusesFILE\n";
		$file_output .= ",,,,,$SUMstatusesFILEpct\n";
		}
	}

if ($file_download > 0)
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AGENT_STATUS$US$FILE_TIME.csv";

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

echo "</TD><TD VALIGN=TOP> "._QXZ("Inbound groups").":<BR>";
echo "<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$group_string))
	{echo "<option value=\"--ALL--\" selected>-- "._QXZ("ALL groups")." --</option>\n";}
else
	{echo "<option value=\"--ALL--\">-- "._QXZ("ALL groups")." --</option>\n";}
$o=0;
while ($groups_to_print > $o)
{
	if (preg_match("/$LISTgroups[$o]\|/i",$group_string)) {echo "<option selected value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroup_names[$o]</option>\n";}
	  else {echo "<option value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroup_names[$o]</option>\n";}
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
echo "</SELECT><BR><BR>\n";
echo _QXZ("Display as").":<BR>";
echo "<select name='report_display_type'>";
if ($report_display_type) {echo "<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
echo "<option value='TEXT'>TEXT</option><option value='HTML'>HTML</option></select>\n<BR><BR>";
echo "</TD><TD VALIGN=TOP><BR>";
echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR>\n";
echo "<input type='checkbox' name='show_defunct_users' value='checked' $show_defunct_users>"._QXZ("Show defunct users")."<BR><BR>\n";
echo " &nbsp; &nbsp; &nbsp; <INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";

echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
echo " <a href=\"$LINKbase&stage=$stage&file_download=1\">"._QXZ("DOWNLOAD")."</a> | \n";
echo " <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>";
echo "</FONT>\n";
echo "</TD></TR></TABLE>";

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

if (!$report_display_type || $report_display_type=="TEXT")
	{
	echo "<span style=\"position:absolute;left:3px;top:3px;z-index:18;\"  id=agent_status_bars>\n";
	echo "<PRE><FONT SIZE=2>\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";

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

</BODY></HTML>
