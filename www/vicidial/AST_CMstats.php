<?php 
# AST_CMstats.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 180724-2109 - First build
# 180829-1155 - Added totals
# 191013-0849 - Fixes for PHP7
# 220303-0822 - Added allow_web_debug system setting
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
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["callmenus"]))				{$callmenus=$_GET["callmenus"];}
	elseif (isset($_POST["callmenus"]))		{$callmenus=$_POST["callmenus"];}
if (isset($_GET["type"]))				{$type=$_GET["type"];}
	elseif (isset($_POST["type"]))		{$type=$_POST["type"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["report_type"]))			{$report_type=$_GET["report_type"];}
	elseif (isset($_POST["report_type"]))	{$report_type=$_POST["report_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($callmenus)) {$callmenus=array();}
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = "$NOW_DATE 00:00:00";}
if (!isset($end_date)) {$end_date = "$NOW_DATE 23:23:59";}

$report_name = 'Callmenu Survey Report';
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
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $end_date);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$report_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_type);
$type = preg_replace('/[^-_0-9a-zA-Z]/', '', $type);

# Variables filtered further down in the code
# $group
# $callmenus

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_outbound_ivr_log", "vicidial_closer_log", "live_inbound_log", "vicidial_agent_log");
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
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
	$vicidial_outbound_ivr_log_table="vicidial_outbound_ivr_log";
	$vicidial_closer_log_table="vicidial_closer_log";
	$live_inbound_log_table="live_inbound_log";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $shift, $file_download, $report_type|', url='$LOGfull_url', webserver='$webserver_id';";
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

$stmt="select menu_id, menu_name From vicidial_call_menu order by menu_id asc;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$callmenus_to_print = mysqli_num_rows($rslt);
$i=0;
$LISTcallmenus=array();
$LISTcallmenus_names=array();
while ($i < $callmenus_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTcallmenus[$i] =		$row[0];
	$LISTcallmenus_names[$i] = $row[1];
	$i++;
	}

$stmt="select campaign_id,campaign_name from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
$LISTgroups=array();
$LISTgroups_names=array();
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
	$group[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $group[$i]);
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


$i=0;
$callmenu_string='|';
$callmenu_ct = count($callmenus);
$selected_callmenus=$callmenus;
while($i < $callmenu_ct)
	{
	$callmenus[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $callmenus[$i]);
	$callmenu_string .= "$callmenus[$i]|";
	$callmenu_SQL .= "'$callmenus[$i]',";
	$callmenuQS .= "&callmenus[]=$callmenus[$i]";
	$i++;
	}
if ( (preg_match('/\s\-\-NONE\-\-\s/',$callmenu_string) ) or ($callmenu_ct < 1) )
	{
	$callmenu_SQL = "''";
#	$group_SQL = "group_id IN('')";
	}
else
	{
	$callmenu_SQL = preg_replace('/,$/i', '',$callmenu_SQL);
#	$group_SQL = "group_id IN($group_SQL)";
	}
if (strlen($callmenu_SQL)<3) {$callmenu_SQL="''";}


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

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#CMstats$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

if ($DB > 0)
	{
	$MAIN.="<BR>\n";
	$MAIN.="$callmenu_ct|$callmenu_string|$callmenu_SQL\n";
	$MAIN.="<BR>\n";
	$MAIN.="$shift|$query_date|$end_date\n";
	$MAIN.="<BR>\n";
	}

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0 border='0' BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN='TOP'>";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=type VALUE=\"$type\">\n";
$MAIN.=_QXZ("Date Range").":<BR>\n";

#$MAIN.="<INPUT TYPE=hidden NAME=query_date ID=query_date VALUE=\"$query_date\">\n";
#$MAIN.="<INPUT TYPE=hidden NAME=end_date ID=end_date VALUE=\"$end_date\">\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=11 MAXLENGTH=10 VALUE=\"$query_date\">";

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

$MAIN.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=11 MAXLENGTH=10 VALUE=\"$end_date\">";

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

/*
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
*/


	$MAIN.=_QXZ("Callmenu")." /<BR>"._QXZ("Tracking Group").": \n";
	$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
	$MAIN.="<SELECT SIZE=5 NAME=callmenus[] multiple>\n";
	$o=0;

	while ($callmenus_to_print > $o)
		{
		if (preg_match("/\|$LISTcallmenus[$o]\|/",$callmenu_string)) 
			{$MAIN.="<option value='$LISTcallmenus[$o]' selected>$LISTcallmenus[$o] - $LISTcallmenus_names[$o]</option>\n";}
		else
			{$MAIN.="<option value='$LISTcallmenus[$o]'>$LISTcallmenus[$o] - $LISTcallmenus_names[$o]</option>\n";}
		$o++;
		}

	$MAIN.="</SELECT>\n";
	$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
/*
	$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
	$MAIN.="<a href=\"$PHP_SELF?DB=$DB&type=$type&query_date=$query_date&end_date=$end_date&query_date_D=$query_date_D&query_date_T=$query_date_T&end_date_D=$end_date_D&end_date_T=$end_date_T$callmenuQS&shift=$shift&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a> | ";
	$MAIN.="<a href=\"./admin.php?ADD=3111&group_id=$group[0]\">"._QXZ("MODIFY")."</a> | ";
	$MAIN.="<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> | ";
	$MAIN.="<a href=\"./AST_CLOSERstats.php?query_date=$query_date&end_date=$end_date&shift=$shift$groupQS\">"._QXZ("CLOSER REPORT")."</a> \n";
	$MAIN.="</FONT>\n";

$MAIN.="<SCRIPT LANGUAGE=\"JavaScript\">\n";

$MAIN.="function submit_form()\n";
$MAIN.="	{\n";
$MAIN.="	document.vicidial_report.end_date.value = document.vicidial_report.end_date_D.value + \" \" + document.vicidial_report.end_date_T.value;\n";
$MAIN.="	document.vicidial_report.query_date.value = document.vicidial_report.query_date_D.value + \" \" + document.vicidial_report.query_date_T.value;\n";
$MAIN.="	document.vicidial_report.submit();\n";
$MAIN.="	}\n";

$MAIN.="</SCRIPT>\n";
*/

$MAIN.=_QXZ("Report type").":&nbsp; ";
$MAIN.="<select name='report_type'>";
if ($report_type) {$MAIN.="<option value='$report_type' selected>$report_type</option>";}
$MAIN.="<option value=''>"._QXZ("Select report type")."</option><option value='SINGLE_AGENT'>"._QXZ("SINGLE_AGENT")."</option><option value='ALL_AGENTS'>"._QXZ("ALL_AGENTS")."</option></select>\n<BR>";
$MAIN.="<BR>"._QXZ("User ID").": \n";
$MAIN.="<input type='text' name='user' value='$user' size='10' maxlength='10'><BR><font size='1'><B>("._QXZ("Single agent only").")</B></font>\n";

$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
$MAIN.="<a href=\"$PHP_SELF?DB=$DB&type=$type&query_date=$query_date&end_date=$end_date$callmenuQS&shift=$shift&file_download=1&search_archived_data=$search_archived_data&user=$user&report_type=$report_type&submit=1\">"._QXZ("DOWNLOAD")."</a> | ";
$MAIN.="<a href=\"./admin.php?ADD=31&campaign_id=$group[0]\">"._QXZ("MODIFY")."</a> | ";
$MAIN.="<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> | ";
$MAIN.="<a href=\"./AST_VDADstats.php?query_date=$query_date&end_date=$end_date&shift=$shift$groupQS\">"._QXZ("OUTBOUND REPORT")."</a> \n";
$MAIN.="<BR><BR>";
if ($archives_available=="Y") 
	{
	$MAIN.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR><BR>\n";
	}
$MAIN.="&nbsp; &nbsp; <input style='background-color:#$SSbutton_color' type=submit value=\""._QXZ("SUBMIT")."\" name=submit id=submit>\n";
$MAIN.="</FONT>\n";
$MAIN.="</TD></TR></TABLE>\n";
$MAIN.="</FORM>\n\n";

$MAIN.="<PRE><FONT SIZE=2>\n\n";


if ($callmenus_to_print < 1 || ($report_type=="SINGLE_AGENT" && !$user) || !$report_type || !$submit)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE ENTER A USER ID, SELECT CALLMENUS AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$MAIN.=_QXZ("Callmenu Report").": $query_date_BEGIN "._QXZ("to")." $query_date_END               $NOW_TIME\n";
	$MAIN.="                  "._QXZ("Report").": $report_type\n";
#	$MAIN.="               "._QXZ("Campaigns").": $group_string\n";
	$MAIN.="         "._QXZ("Tracking groups").": $callmenu_string\n";

	$CSV_text="\""._QXZ("Callmenu Report").": $query_date_BEGIN "._QXZ("to")." $query_date_END\",\"$NOW_TIME\"\n";

	$TOTALcalls=0;
	$NOCALLERIDcalls=0;
	$UNIQUEcallers=0;
	$totFLOWivr_time=0;
	$totFLOWtotal_time=0;

	##### Grab all records for the IVR for the specified time period
/*
#	$stmt="select ".$live_inbound_log_table.".uniqueid,extension,start_time,comment_a,comment_b,comment_d,UNIX_TIMESTAMP(start_time),phone_ext from ".$live_inbound_log_table.", ".$vicidial_agent_log_table." where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_b in ($callmenu_SQL) and ".$vicidial_agent_log_table.".uniqueid=".$live_inbound_log_table.".uniqueid and ".$vicidial_agent_log_table.".user='$user' and ".$vicidial_agent_log_table.".campaign_id in ($campaign_SQL) order by uniqueid,start_time;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$logs_to_print = mysqli_num_rows($rslt);
	$p=0;
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
*/

	if ($report_type=="SINGLE_AGENT") {
		$MAIN.="                   "._QXZ("Agent").": $user\n";
		$al_stmt="select uniqueid, event_time from ".$vicidial_agent_log_table." where uniqueid is not null and uniqueid!='' and event_time>='$query_date_BEGIN 00:00:00' and event_time<='$query_date_END 23:59:59' and user='$user' order by uniqueid asc";
		if ($DB) {$MAIN.="$al_stmt\n";}
		$al_rslt=mysql_to_mysqli($al_stmt, $link);
		$max_prompts=0;
		$prompts_array=array();
		$calls_array=array();
		$total_calls_array=array();
		$HTML_text="";
		while ($al_row=mysqli_fetch_row($al_rslt)) 
			{
			$uniqueid=$al_row[0];
			$start_time=$al_row[1];
			$vl_stmt="select phone_ext, comment_b, comment_d, start_time from ".$live_inbound_log_table." where uniqueid='$uniqueid' and comment_d like '%>%' order by start_time";
			if ($DB) {$MAIN.="$vl_stmt\n";}
			$vl_rslt=mysql_to_mysqli($vl_stmt, $link);
			$call_rslt="";
			$total_points=0;
			$sq=0;
			$prompts=0;
			while ($vl_row=mysqli_fetch_row($vl_rslt)) 
				{
				$phone_ext=$vl_row[0];
				$comment_b=$vl_row[1];
				$comment_d=$vl_row[2];
				# $start_time=$vl_row[3];
				
				if (preg_match('/\>[0-9]$/', $comment_d)) 
					{
					$qa_array=explode(">", $comment_d);
					if (in_array($qa_array[0], $selected_callmenus)) 
						{
						$calls_array["$uniqueid"]["start_time"]="$start_time";
						$calls_array["$uniqueid"]["phone"]="$phone_ext";
						$total_points+=$qa_array[1];
						$prompts_array["$qa_array[0]"]+=$qa_array[1];
						$calls_array["$uniqueid"]["$qa_array[0]"]=($qa_array[1]+0);
						$total_calls_array["$uniqueid"]++;
						}
					}
				}
			if ($prompts>$max_prompts) {$max_prompts=$prompts;}
			}
		$total_calls=count($total_calls_array);
		$CSV_text="\""._QXZ("PHONE")."\",\""._QXZ("START TIME")."\",";
		$CSV_text_end="\""._QXZ("TOT. CALLS").": $total_calls\",\""._QXZ("POINT TOTALS").":\",";

		$HTML_text="<table border='0' cellpadding='2' cellspacing='1'>";
		$HTML_text.="<tr bgcolor='".$SSmenu_background."'><th><font color='#FFF'>PHONE</font></th><th><font color='#FFF'>"._QXZ("START TIME")."</font></th>";
		$HTML_text_end="<tr bgcolor='".$SSmenu_background."'><th align='right'><font color='#FFF'>"._QXZ("TOT. CALLS").": $total_calls</font></th><th align='right'><font color='#FFF'>"._QXZ("POINT TOTALS").":</font></th>";

		ksort($prompts_array);
		$grand_total=0;
#		while (list($key, $val)=each($prompts_array)) 
		foreach($prompts_array as $key => $val)
			{
			$val+=0;
			$grand_total+=$val;
			$CSV_text.="\"$key\",";
			$CSV_text_end.="\"$val\",";
			$HTML_text.="<th><font color='#FFF'>$key</font></th>";
			$HTML_text_end.="<th><font color='#FFF'>$val</font></th>";
			}	
		$HTML_text_end.="<th><font color='#FFF'>$grand_total</font></th></tr>";
		$CSV_text_end.="\"$grand_total\"\n";
		$HTML_text.="<th><font color='#FFF'>"._QXZ("TOTAL POINTS").":</font></th></tr>";
		$CSV_text.="\""._QXZ("TOTAL POINTS")."\"\n";

		ksort($calls_array);
#		while (list($key, $val)=each($calls_array)) 
		foreach($calls_array as $key => $val)
			{
			$CSV_text.="\"".$calls_array[$key]["phone"]."\",\"".$calls_array[$key]["start_time"]."\",";
			$HTML_text.="<tr bgcolor='".$SSstd_row1_background."'><th>".$calls_array[$key]["phone"]."</th><th>".$calls_array[$key]["start_time"]."</th>";
			reset($prompts_array);
			$call_points=0;
#			while (list($key2, $val2)=each($prompts_array)) 
			foreach($prompts_array as $key2 => $val2)
				{
				$CSV_text.="\"".($calls_array[$key][$key2]+0)."\",";
				$HTML_text.="<th>".($calls_array[$key][$key2]+0)."</th>";
				$call_points+=($calls_array[$key][$key2]+0);
				}	
			$CSV_text.="\"".($call_points+0)."\"\n";
			$HTML_text.="<th>".($call_points+0)."</th></tr>";
			}

		$CSV_text.=$CSV_text_end;

		$HTML_text.=$HTML_text_end;
		$HTML_text.="</table>";
	} else {
		$al_stmt="select uniqueid,user from ".$vicidial_agent_log_table." where uniqueid is not null and uniqueid!='' and event_time>='$query_date_BEGIN 00:00:00' and event_time<='$query_date_END 23:59:59' order by uniqueid asc";
		if ($DB) {$MAIN.="$al_stmt\n";}
		$al_rslt=mysql_to_mysqli($al_stmt, $link);
		$max_prompts=0;
		$prompts_array=array();
		$agent_array=array();
		$calls_array=array();
		$total_calls_array=array();
		$HTML_text="";
		$CSV_text="";
		while ($al_row=mysqli_fetch_row($al_rslt)) 
			{
			$uniqueid=$al_row[0];
			$agent=$al_row[1];
			$user_stmt="select full_name from vicidial_users where user='$agent'";
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			if (mysqli_num_rows($user_rslt)>0) {
				$user_row=mysqli_fetch_row($user_rslt);
				$full_name=$user_row[0];
			} else {
				$full_name=$agent;
			}
			$vl_stmt="select phone_ext, comment_b, comment_d, start_time from ".$live_inbound_log_table." where uniqueid='$uniqueid' and comment_d like '%>%' order by start_time";
			if ($DB) {$MAIN.="$vl_stmt\n";}
			$vl_rslt=mysql_to_mysqli($vl_stmt, $link);
			$call_rslt="";
			$total_points=0;
			$sq=0;
			$prompts=0;
			while ($vl_row=mysqli_fetch_row($vl_rslt)) 
				{
				$phone_ext=$vl_row[0];
				$comment_b=$vl_row[1];
				$comment_d=$vl_row[2];
				$start_time=$vl_row[3];
				$calls_array["$uniqueid"]++;
				if (preg_match('/\>[0-9]$/', $comment_d)) 
					{
					$qa_array=explode(">", $comment_d);
					if (in_array($qa_array[0], $selected_callmenus)) 
						{
						$agent_array["$agent"]["AGENT"]=$full_name;
						$total_points+=$qa_array[1];
						$prompts_array["$qa_array[0]"]+=$qa_array[1];
						$agent_array["$agent"]["$qa_array[0]"]+=$qa_array[1];
						$agent_array["$agent"]["TOTAL"]+=$qa_array[1];
						$total_calls_array["$agent"]["$uniqueid"]++;
						}
					}
				}
			if ($prompts>$max_prompts) {$max_prompts=$prompts;}
			}
		# $total_calls=count($total_calls_array);

		$CSV_text="\""._QXZ("AGENT")."\",";
		$CSV_text_end="\""._QXZ("TOTALS").":\",";


		$HTML_text="<table border='0' cellpadding='2' cellspacing='1'>";
		$HTML_text.="<tr bgcolor='".$SSmenu_background."'><th><font color='#FFF'>"._QXZ("AGENT")."</font></th>";
		$HTML_text_end="<tr bgcolor='".$SSmenu_background."'><th align='right'><font color='#FFF'>"._QXZ("TOTALS").":</font></th>";


		
		ksort($prompts_array);
		$grand_total=0;
#		while (list($key, $val)=each($prompts_array)) 
		foreach($prompts_array as $key => $val)
			{
			$val+=0;
			$grand_total+=$val;
			$CSV_text.="\"$key\",";
			$CSV_text_end.="\"$val\",";
			$HTML_text.="<th><font color='#FFF'>$key</font></th>";
			$HTML_text_end.="<th><font color='#FFF'>$val</font></th>";
			}	
		$HTML_text.="<th><font color='#FFF'>"._QXZ("TOTAL POINTS").":</font></th>";
		$HTML_text.="<th><font color='#FFF'>"._QXZ("TOTAL CALLS").":</font></th></tr>";
		$CSV_text.="\""._QXZ("TOTAL POINTS")."\",";
		$CSV_text.="\""._QXZ("TOTAL CALLS")."\"\n";

		uasort($agent_array, function ($i, $j) 
			{
			$a = $i['TOTAL'];
			$b = $j['TOTAL'];
			if ($a == $b) return 0;
			elseif ($a > $b) return -1;
			else return 1;
			});

		$total_calls=0;
#		while (list($key, $val)=each($agent_array)) 
		foreach($agent_array as $key => $val)
			{
			$CSV_text.="\"".$agent_array[$key]["AGENT"]."\",";
			$HTML_text.="<tr bgcolor='".$SSstd_row1_background."'><th>".$agent_array[$key]["AGENT"]."</th>";
			reset($prompts_array);
#			while (list($key2, $val2)=each($prompts_array)) 
			foreach($prompts_array as $key2 => $val2)
				{
				$CSV_text.="\"".($agent_array[$key][$key2]+0)."\",";
				$HTML_text.="<th>".($agent_array[$key][$key2]+0)."</th>";
				$call_points+=($agent_array[$key][$key2]+0);
				}	
			$CSV_text.="\"".($agent_array[$key]["TOTAL"]+0)."\"";
			$CSV_text.="\"".(count($total_calls_array[$key])+0)."\"\n";
			$HTML_text.="<th>".($agent_array[$key]["TOTAL"]+0)."</th>";
			$HTML_text.="<th>".(count($total_calls_array[$key])+0)."</th></tr>";
			$total_calls+=count($total_calls_array[$key]);
			}
		$HTML_text_end.="<th><font color='#FFF'>$grand_total</font></th><th><font color='#FFF'>$total_calls</font></th></tr>";
		$CSV_text_end.="\"$grand_total\",\"$total_calls\"\n";


		$CSV_text.=$CSV_text_end;

		$HTML_text.=$HTML_text_end;
		$HTML_text.="</table>";

	}


	$MAIN.=$HTML_text;
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
	$MAIN.="</PRE>\n";
	$MAIN.="</TD></TR></TABLE>\n";

	$MAIN.="</BODY></HTML>\n";
	}

	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_CMstats_$US$FILE_TIME.csv";
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
