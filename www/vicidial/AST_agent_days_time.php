<?php 
# AST_agent_days_time.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 150114-2158 - First build based on AST_agent_days_detail.php
# 151227-1718 - Added option to search archive records instead of main
# 160121-2217 - Added report title header, default report format, cleaned up formatting
# 170409-1538 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 191013-0812 - Fixes for PHP7
# 220303-1510 - Added allow_web_debug system setting
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
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Single Agent Daily Time';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format,allow_web_debug FROM system_settings;";
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
$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);

# Variables filtered further down in the code
# $group

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$shift = preg_replace('/[^-_0-9a-zA-Z]/', '', $shift);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$shift = preg_replace('/[^-_0-9\p{L}]/u', '', $shift);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	}

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
if ($DB) {echo "$stmt\n";}
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
	$group_SQL = "and campaign_id IN($group_SQL)";
	}

$customer_interactive_statuses='';
$stmt="select status from vicidial_statuses where human_answered='Y';";
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
$stmt="select status from vicidial_campaign_statuses where human_answered='Y';";
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

#$customer_interactive_statuses = '|NI|DNC|CALLBK|AP|SALE|COMP|HAP1|HAP2|HBED|DIED|';
#$customer_interactive_statuses = '|NI|DNC|CALLBK|XFER|C2|B7|B8|C1|';

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date&shift=$shift&DB=$DB&user=$user$groupQS&search_archived_data=$search_archived_data&report_display_type=$report_display_type";

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

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:21;'></div>";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\"  id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";
	echo "<span style=\"position:absolute;left:3px;top:30px;z-index:19;\"  id=agent_status_stats>\n";
	echo "<b>"._QXZ("$report_name")."</b> $NWB#single_agent_time$NWE\n";
	echo "<PRE><FONT SIZE=2>";
	}

if (strlen($group[0]) < 1)
	{
	echo "\n";
	echo _QXZ("PLEASE SELECT A USER AND DATE-TIME BELOW AND CLICK SUBMIT")."\n";
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
		$ASCII_text.=_QXZ("Agent Days Time Report",24).": $user                     $NOW_TIME ($db_source)\n";
		$ASCII_text.=_QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		$GRAPH_text.=_QXZ("Agent Days Time Report",24).": $user                     $NOW_TIME ($db_source)\n";
		$GRAPH_text.=_QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}
	else
		{
		$file_output .= _QXZ("Agent Days Time Report",24).": $user                     $NOW_TIME ($db_source)\n";
		$file_output .= _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}

	$statuses='-';
	$statusesTXT='';
	$statusesHEAD='';
	$statusesHTML='';
	$statusesFILE='';
	$statusesARY=array();
	$statusesARY[0]='';
	$j=0;
	$calls=array();
	$date=array();
	$dates='-';
	$datesARY=array();
	$datesARY[0]='';
	$date_namesARY=array();
	$date_namesARY[0]='';
	$k=0;

	$stmt="select date_format(event_time, '%Y-%m-%d') as date,count(*) as calls from vicidial_users,".$vicidial_agent_log_table." where event_time <= '$query_date_END' and event_time >= '$query_date_BEGIN' and vicidial_users.user=".$vicidial_agent_log_table.".user and ".$vicidial_agent_log_table.".user='$user' $group_SQL $user_group_SQL $vuLOGadmin_viewable_groupsSQL group by date order by date desc limit 500000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);

		if ( ($row[1] > 0) and (strlen($row[0]) > 0) )
			{
			$date[$i] =			$row[0];
			$calls[$i] =		$row[1];
			if (!preg_match("/\-$date[$i]\-/i", $dates))
				{
				$dates .= "$date[$i]-";
				$datesARY[$k] = $date[$i];
				$k++;
				}
			}
		$i++;
		}


	$MAIN.="";
	$MAIN.="<TABLE width=750 cellspacing=0 cellpadding=1>\n";
	$MAIN.="<tr><td><font size=2>"._QXZ("DATE")." </td><td align=left><font size=2>"._QXZ("PAUSE")."</td><td align=left><font size=2> "._QXZ("WAIT")."</td><td align=left><font size=2> "._QXZ("TALK")."</td><td align=right><font size=2> "._QXZ("DISPO")."</td><td align=right><font size=2> "._QXZ("DEAD")."</td><td align=right><font size=2> "._QXZ("CUSTOMER")."</td><td align=right><font size=2> "._QXZ("TOTAL")."</td></tr>\n";
	$MAINprintALL .= $MAIN;

	$i=0;
	while ($i < $rows_to_print)
		{
		$MAIN='';
		$VALstart_time = "$date[$i] 00:00:00";
		$VALstop_time = "$date[$i] 23:59:59";

		$stmt="select event_time,lead_id,campaign_id,pause_sec,wait_sec,talk_sec,dispo_sec,dead_sec,status,sub_status,user_group from ".$vicidial_agent_log_table." where user='$user' and event_time >= '$VALstart_time'  and event_time <= '$VALstop_time' and ( (pause_sec > 0) or (wait_sec > 0) or (talk_sec > 0) or (dispo_sec > 0) ) order by event_time desc limit 10000;";
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
		$TOTALtotalSECONDS=0;

		while ($logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			$event_time =	$row[0];
			$lead_id =		$row[1];
			$campaign_id =	$row[2];
			$pause_sec =	$row[3];
			$wait_sec =		$row[4];
			$talk_sec =		$row[5];
			$dispo_sec =	$row[6];
			$dead_sec =		$row[7];
			$status =		$row[8];
			$pause_code =	$row[9];
			$user_group =	$row[10];
			$customer_sec = ($talk_sec - $dead_sec);
			if ($customer_sec < 0)
				{$customer_sec=0;}

			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#B9CBFD"';}
			else
				{$bgcolor='bgcolor="#9BB9FB"';}

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
		#		$MAIN.="<tr $bgcolor>";
		#		$MAIN.="<td><font size=1> &nbsp;</td>";
		#		$MAIN.="<td><font size=2>$DBcall_end</td>";
		#		$MAIN.="<td align=right><font size=2> $DBtotal_sec </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td>\n";
		#		$MAIN.="<td align=right><font size=2> &nbsp; </td></tr>\n";
				}

			$u++;
		#	$MAIN.="<tr $bgcolor>";
		#	$MAIN.="<td><font size=1>$u</td>";
		#	$MAIN.="<td><font size=2>$event_time</td>";
		#	$MAIN.="<td align=right><font size=2> $pause_sec</td>\n";
		#	$MAIN.="<td align=right><font size=2> $wait_sec</td>\n";
		#	$MAIN.="<td align=right><font size=2> $talk_sec </td>\n";
		#	$MAIN.="<td align=right><font size=2> $dispo_sec </td>\n";
		#	$MAIN.="<td align=right><font size=2> $dead_sec </td>\n";
		#	$MAIN.="<td align=right><font size=2> $customer_sec </td>\n";
		#	$MAIN.="<td align=right><font size=2> $status </td>\n";
		#	$MAIN.="<td align=right><font size=2> <A HREF=\"admin_modify_lead.php?lead_id=$lead_id\" target=\"_blank\">$lead_id</A> </td>\n";
		#	$MAIN.="<td align=right><font size=2> $campaign_id </td>\n";
		#	$MAIN.="<td align=right><font size=2> $pause_code </td></tr>\n";
		#	$CSV_text7.="\"\",\"$u\",\"$event_time\",\"$pause_sec\",\"$wait_sec\",\"$talk_sec\",\"$dispo_sec\",\"$dead_sec\",\"$customer_sec\",\"$status\",\"$lead_id\",\"$campaign_id\",\"$pause_code \"\n";
			}

	#	$MAIN.="<tr bgcolor=white>";
	#	$MAIN.="<td colspan=2><font size=2>"._QXZ("TOTALS")."</td>";
	#	$MAIN.="<td align=right><font size=2> $TOTALpauseSECONDS</td>\n";
	#	$MAIN.="<td align=right><font size=2> $TOTALwaitSECONDS</td>\n";
	#	$MAIN.="<td align=right><font size=2> $TOTALtalkSECONDS</td>\n";
	#	$MAIN.="<td align=right><font size=2> $TOTALdispoSECONDS</td>\n";
	#	$MAIN.="<td align=right><font size=2> $TOTALdeadSECONDS</td>\n";
	#	$MAIN.="<td align=right><font size=2> $TOTALcustomerSECONDS</td>\n";
	#	$MAIN.="<td colspan=4><font size=2> &nbsp; </td></tr>\n";
	#	$CSV_text7.="\"\",\"\",\""._QXZ("TOTALS")."\",\"$TOTALpauseSECONDS\",\"$TOTALwaitSECONDS\",\"$TOTALtalkSECONDS\",\"$TOTALdispoSECONDS\",\"$TOTALdeadSECONDS\",\"$TOTALcustomerSECONDS\"\n";

		$TOTALpauseSECONDShh =	sec_convert($TOTALpauseSECONDS,'H'); 
		$TOTALwaitSECONDShh =	sec_convert($TOTALwaitSECONDS,'H'); 
		$TOTALtalkSECONDShh =	sec_convert($TOTALtalkSECONDS,'H'); 
		$TOTALdispoSECONDShh =	sec_convert($TOTALdispoSECONDS,'H'); 
		$TOTALdeadSECONDShh =	sec_convert($TOTALdeadSECONDS,'H'); 
		$TOTALcustomerSECONDShh =	sec_convert($TOTALcustomerSECONDS,'H'); 
		$TOTALtotalSECONDS = ($TOTALpauseSECONDS + $TOTALwaitSECONDS + $TOTALtalkSECONDS + $TOTALdispoSECONDS);
		$TOTALtotalSECONDShh =	sec_convert($TOTALtotalSECONDS,'H'); 

		$MAIN.="<tr bgcolor=white>";
		$MAIN.="<td align=center><font size=2><a href=\"./user_stats.php?user=$user&begin_date=$date[$i]&end_date=$date[$i]\">$date[$i]</a></td>";
		$MAIN.="<td align=right><font size=2> $TOTALpauseSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALwaitSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALtalkSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALdispoSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALdeadSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALcustomerSECONDShh</td>\n";
		$MAIN.="<td align=right><font size=2> $TOTALtotalSECONDShh</td>\n";
		$MAIN.="</tr>\n";

		$MAINprintALL .= $MAIN;
		$MAIN='';

	#	$CSV_text7.="\"\",\"\",\""._QXZ("(in HH:MM:SS)")."\",\"$TOTALpauseSECONDShh\",\"$TOTALwaitSECONDShh\",\"$TOTALtalkSECONDShh\",\"$TOTALdispoSECONDShh\",\"$TOTALdeadSECONDShh\",\"$TOTALcustomerSECONDShh\"\n";

		$i++;
		}

	$MAIN.="</TABLE></center><BR><BR>\n";
	}

$MAINprintALL .= $MAIN;

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
if  (preg_match('/\-\-ALL\-\-/',$group_string))
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
echo "</TD><TD VALIGN=TOP>";
#echo _QXZ("Display as").":&nbsp;&nbsp;&nbsp;<BR>";
#echo "<select name='report_display_type'>";
#if ($report_display_type) {echo "<option value='$report_display_type' selected>$report_display_type</option>";}
#echo "<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
echo "</TD><TD VALIGN=TOP>"._QXZ("User").":<BR>";
echo "<INPUT TYPE=TEXT SIZE=10 NAME=user value=\"$user\">\n";
echo "</TD><TD VALIGN=TOP>"._QXZ("Shift").":<BR>";
echo "<SELECT SIZE=1 NAME=shift>\n";
echo "<option selected value=\"$shift\">"._QXZ("$shift")."</option>\n";
echo "<option value=\"\">--</option>\n";
echo "<option value=\"AM\">"._QXZ("AM")."</option>\n";
echo "<option value=\"PM\">"._QXZ("PM")."</option>\n";
echo "<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
echo "</SELECT>\n";

if ($archives_available=="Y") 
	{
	echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

echo "<BR><BR><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
if (strlen($user) > 1)
	{
	echo " <a href=\"./admin.php?ADD=3&user=$user\">"._QXZ("USER")."</a> | \n";
	echo " <a href=\"./user_stats.php?user=$user&begin_date=$query_date&end_date=$end_date\">"._QXZ("USER STATS")."</a> | \n";
	}
else
	{echo " <a href=\"./admin.php?ADD=0A\">"._QXZ("USERS")."</a> | \n";}
echo "<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</TD></TR></TABLE>";

echo "</FORM>";

echo $MAINprintALL;

echo "</span>\n";

if ($report_display_type=="TEXT" || !$report_display_type) 
	{
	echo "<span style=\"position:absolute;left:3px;top:3px;z-index:18;\"  id=agent_status_bars>\n";
	echo "<PRE><FONT SIZE=2>\n\n\n\n\n\n\n\n\n\n";

	$m=0;
	$sort_order=array();
	while ($m < $k)
		{
		$sort_split = explode("-----",$TOPsort[$m]);
		$i = $sort_split[1];
		$sort_order[$m] = "$i";

		if ( ($TOPsortTALLY[$i] < 1) or ($TOPsortMAX < 1) )
			{echo "              \n";}
		else
			{
			echo "              <SPAN class=\"yellow\">";
			$TOPsortPLOT = ( MathZDC($TOPsortTALLY[$i], $TOPsortMAX) * 120 );
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
