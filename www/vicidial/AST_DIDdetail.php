<?php 
# AST_DIDdetail.php
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 220510-0945 - First build
# 240801-1130 - Code updates for PHP8 compatibility
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["route"]))					{$route=$_GET["route"];}
	elseif (isset($_POST["route"]))			{$route=$_POST["route"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["time_BEGIN"]))				{$time_BEGIN=$_GET["time_BEGIN"];}
	elseif (isset($_POST["time_BEGIN"]))	{$time_BEGIN=$_POST["time_BEGIN"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["time_END"]))				{$time_END=$_GET["time_END"];}
	elseif (isset($_POST["time_END"]))		{$time_END=$_POST["time_END"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!is_array($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (!isset($time_BEGIN)) {$time_BEGIN = "00:00:00";}
if (!isset($time_END)) {$time_END = "23:59:59";}
if (strlen($shift)<2) {$shift='--';}

$report_name = 'Inbound DID Detail Report';
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
if (strlen($report_display_type)<2) {$report_display_type = 'TEXT';}
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $end_date);
$time_BEGIN = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $time_BEGIN);
$time_END = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $time_END);
$route = preg_replace('/[^-_0-9a-zA-Z]/', '', $route);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);

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
$log_tables_array=array("vicidial_did_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_did_log_table=use_archive_table("vicidial_did_log");
	}
else
	{
	$vicidial_did_log_table="vicidial_did_log";
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

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
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


$stmt="select did_id,did_pattern,did_description from vicidial_inbound_dids $whereLOGadmin_viewable_groupsSQL order by did_pattern;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$groups_string='|';
$i=0;
$groups=array();
$group_patterns=array();
$group_names=array();
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =			$row[0];
	$group_patterns[$i] =	$row[1];
	$group_names[$i] =		$row[2];
	$groups_string .= "$groups[$i]|";
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

$AMP='&';
$QM='?';
$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $shift, $file_download, $report_display_type|', url='".$LOGfull_url."?DB=".$DB."&file_download=".$file_download."&query_date=".$query_date."&end_date=".$end_date."&shift=".$shift."&report_display_type=".$report_display_type."$groupQS', webserver='$webserver_id';";
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

require("screen_colors.php");

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: black; background-color: #99FF99}\n";
$HEADER.="   .red {color: black; background-color: #FF9999}\n";
$HEADER.="   .orange {color: black; background-color: #FFCC99}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<style type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.=".auraltext\n";
$HEADER.="	{\n";
$HEADER.="	position: absolute;\n";
$HEADER.="	font-size: 0;\n";
$HEADER.="	left: -1000px;\n";
$HEADER.="	}\n";
$HEADER.=".chart_td\n";
$HEADER.="	{background-image: url(images/gridline58.gif); background-repeat: repeat-x; background-position: left top; border-left: 1px solid #e5e5e5; border-right: 1px solid #e5e5e5; padding:0; border-bottom: 1px solid #e5e5e5; background-color:transparent;}\n";
$HEADER.="\n";
$HEADER.="-->\n";
$HEADER.="</style>\n";

$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HEADER.="<script src='chart/Chart.js'></script>\n"; 
$HEADER.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$short_header=1;

if ($DB > 0)
	{
	$MAIN.="<BR>\n";
	$MAIN.="$group_ct|$group_string|$group_SQL\n";
	$MAIN.="<BR>\n";
	$MAIN.="$shift|$query_date|$end_date\n";
	$MAIN.="<BR>\n";
	}

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#DIDdetail$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=POST name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD align=left valign=top>\n";
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
$MAIN.="<BR><INPUT TYPE=TEXT NAME=time_BEGIN SIZE=10 MAXLENGTH=8 VALUE=\"$time_BEGIN\">";


$MAIN.="<BR><div align='center'> "._QXZ("to")." </div><BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

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
$MAIN.="<BR><INPUT TYPE=TEXT NAME=time_END SIZE=10 MAXLENGTH=8 VALUE=\"$time_END\">";

$MAIN.="</TD><TD align=center valign=top>\n";
$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
$o=0;
while ($groups_to_print > $o)
	{
	if (preg_match("/\|$groups[$o]\|/",$group_string)) 
		{$MAIN.="<option selected value=\"$groups[$o]\">$group_patterns[$o] - $group_names[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$groups[$o]\">$group_patterns[$o] - $group_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD><TD align=left valign=top><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Shift").":<BR>\n";
$MAIN.="<SELECT SIZE=1 NAME=shift>\n";
$MAIN.="<option selected value=\"$shift\">"._QXZ("$shift")."</option>\n";
$MAIN.="<option value=\"\">--</option>\n";
$MAIN.="<option value=\"AM\">"._QXZ("AM")."</option>\n";
$MAIN.="<option value=\"PM\">"._QXZ("PM")."</option>\n";
$MAIN.="<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
$MAIN.="<option value=\"DAYTIME\">"._QXZ("DAYTIME")."</option>\n";
$MAIN.="<option value=\"10AM-5PM\">"._QXZ("10AM-5PM")."</option>\n";
$MAIN.="<option value=\"10AM-6PM\">"._QXZ("10AM-6PM")."</option>\n";
$MAIN.="<option value=\"9AM-10PM\">"._QXZ("9AM-10PM")."</option>\n";
$MAIN.="<option value=\"9AM-11PM\">"._QXZ("9AM-11PM")."</option>\n";
$MAIN.="<option value=\"9AM-1AM\">"._QXZ("9AM-1AM")."</option>\n";
$MAIN.="<option value=\"845-1745\">845-1745</option>\n";
$MAIN.="<option value=\"1745-100\">1745-100</option>\n";
$MAIN.="</SELECT>\n";

$MAIN.="<BR>"._QXZ("Call Route").":<BR>\n";
$MAIN.="<SELECT SIZE=1 NAME=route>\n";
$MAIN.="<option selected value=\"$route\">"._QXZ("$route")."</option>\n";
$MAIN.="<option value=\"\">--ALL--</option>\n";
$MAIN.="<option>AGENT</option>\n";
$MAIN.="<option>CALLMENU</option>\n";
$MAIN.="<option>EXTEN</option>\n";
$MAIN.="<option>IN_GROUP</option>\n";
$MAIN.="<option>PHONE</option>\n";
$MAIN.="<option>VOICEMAIL</option>\n";
$MAIN.="<option>VMAIL_NO_INST</option>\n";
$MAIN.="<option>F_CALLMENU</option>\n";
$MAIN.="<option>F_EXTEN</option>\n";
$MAIN.="<option>MQ_EXTEN</option>\n";
$MAIN.="<option>NA_EXTEN</option>\n";
$MAIN.="<option>F_DID</option>\n";
$MAIN.="<option>PF_DID</option>\n";
$MAIN.="<option>PFR_DID</option>\n";
$MAIN.="<option>NA_VOICEMAIL</option>\n";
$MAIN.="</SELECT>\n";

if ($archives_available=="Y") 
	{
	$MAIN.="<BR><BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

$MAIN.="</FONT></TD><TD align=left valign=top>\n";
$MAIN.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$MAIN.=" &nbsp;";
$MAIN.="<INPUT TYPE=hidden name='report_display_type' value='TEXT'><BR><BR>";
$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&DB=$DB&SUBMIT=$SUBMIT&file_download=1\">"._QXZ("DOWNLOAD")."</a> | <a href=\"./admin.php?ADD=3311&did_id=$group[0]\">"._QXZ("MODIFY")."</a> | <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$MAIN.="</TD></TR></TABLE>\n";
$MAIN.="</FORM>\n";

$MAIN.="<PRE><FONT SIZE=2>";

$JS_text="<script language='Javascript'>";

if (!$group)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT A DID AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	### FOR SHIFTS IT IS BEST TO STICK TO 15-MINUTE INCREMENTS FOR START TIMES ###

	$suffix=" SHIFT: $shift ";
	switch($shift) {
		case "AM":
			$time_BEGIN = "00:00:00";
			$time_END = "11:59:59";
			break;
		case "PM":
			$time_BEGIN = "12:00:00";
			$time_END = "23:59:59";
			break;
		case "ALL":
			$time_BEGIN = "00:00:00";
			$time_END = "23:59:59";
			break;
		case "DAYTIME":
			$time_BEGIN = "08:45:00";
			$time_END = "00:59:59";
			break;
		case "10AM-5PM":
			$time_BEGIN = "10:00:00";
			$time_END = "16:59:59";
			break;
		case "10AM-6PM":
			$time_BEGIN = "10:00:00";
			$time_END = "17:59:59";
			break;
		case "9AM-11PM":
			$time_BEGIN = "09:00:00";
			$time_END = "22:59:59";
			break;
		case "9AM-10PM":
			$time_BEGIN = "09:00:00";
			$time_END = "21:59:59";
			break;
		case "9AM-1AM":
			$time_BEGIN = "09:00:00";
			$time_END = "00:59:59";
			break;
		case "845-1745":
			$time_BEGIN = "08:45:00";
			$time_END = "17:44:59";
			break;
		case "1745-100":
			$time_BEGIN = "17:45:00";
			$time_END = "00:59:59";
			break;
		default:
			$suffix="";
			break;
	}
/*
	if ($shift == 'AM') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}   
		if (strlen($time_END) < 6) {$time_END = "11:59:59";}
		}
	if ($shift == 'PM') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "12:00:00";}
		if (strlen($time_END) < 6) {$time_END = "23:59:59";}
		}
	if ($shift == 'ALL') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
		if (strlen($time_END) < 6) {$time_END = "23:59:59";}
		}
	if ($shift == 'DAYTIME') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "08:45:00";}
		if (strlen($time_END) < 6) {$time_END = "00:59:59";}
		}
	if ($shift == '10AM-5PM') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "10:00:00";}
		if (strlen($time_END) < 6) {$time_END = "16:59:59";}
		}
	if ($shift == '10AM-6PM') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "10:00:00";}
		if (strlen($time_END) < 6) {$time_END = "17:59:59";}
		}
	if ($shift == '9AM-11PM') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "09:00:00";}
		if (strlen($time_END) < 6) {$time_END = "22:59:59";}
		}
	if ($shift == '9AM-10PM') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "09:00:00";}
		if (strlen($time_END) < 6) {$time_END = "21:59:59";}
		}
	if ($shift == '9AM-1AM') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "09:00:00";}
		if (strlen($time_END) < 6) {$time_END = "00:59:59";}
		}
	if ($shift == '845-1745') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "08:45:00";}
		if (strlen($time_END) < 6) {$time_END = "17:44:59";}
		}
	if ($shift == '1745-100') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "17:45:00";}
		if (strlen($time_END) < 6) {$time_END = "00:59:59";}
		}
*/
	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";

	$SQdate_ARY =	explode(' ',$query_date_BEGIN);
	$SQday_ARY =	explode('-',$SQdate_ARY[0]);
	$SQtime_ARY =	explode(':',$SQdate_ARY[1]);
	$EQdate_ARY =	explode(' ',$query_date_END);
	$EQday_ARY =	explode('-',$EQdate_ARY[0]);
	$EQtime_ARY =	explode(':',$EQdate_ARY[1]);

	$SQepochDAY = mktime(0, 0, 0, $SQday_ARY[1], $SQday_ARY[2], $SQday_ARY[0]);
	$SQepoch = mktime($SQtime_ARY[0], $SQtime_ARY[1], $SQtime_ARY[2], $SQday_ARY[1], $SQday_ARY[2], $SQday_ARY[0]);
	$EQepoch = mktime($EQtime_ARY[0], $EQtime_ARY[1], $EQtime_ARY[2], $EQday_ARY[1], $EQday_ARY[2], $EQday_ARY[0]);

	$SQsec = ( ($SQtime_ARY[0] * 3600) + ($SQtime_ARY[1] * 60) + ($SQtime_ARY[2] * 1) );
	$EQsec = ( ($EQtime_ARY[0] * 3600) + ($EQtime_ARY[1] * 60) + ($EQtime_ARY[2] * 1) );

	$DURATIONsec = ($EQepoch - $SQepoch);
	$DURATIONday = intval( MathZDC($DURATIONsec, 86400) + 1 );

	if ( ($EQsec < $SQsec) and ($DURATIONday < 1) )
		{
		$EQepoch = ($SQepochDAY + ($EQsec + 86400) );
		$query_date_END = date("Y-m-d H:i:s", $EQepoch);
		$DURATIONday++;
		}

	$MAIN.=_QXZ("Inbound DID Detail Report",40)." $NOW_TIME\n";
	$MAIN.="\n";
	$MAIN.=_QXZ("Time range")." $DURATIONday "._QXZ("days").": $query_date_BEGIN "._QXZ("to")." $query_date_END   $suffix\n\n";
	#$MAIN.="Time range day sec: $SQsec - $EQsec   Day range in epoch: $SQepoch - $EQepoch   Start: $SQepochDAY\n";

	$CSV_text1.="\""._QXZ("Inbound DID Detail Report")."\",\"$NOW_TIME\"\n\n";
	$CSV_text1.="\""._QXZ("Time range")." $DURATIONday "._QXZ("days").":\",\"$query_date_BEGIN "._QXZ("to")." $query_date_END\",\"$suffix\"\n\n";

	$d=0;
	$daySTART=array();
	$dayEND=array();
	while ($d < $DURATIONday)
		{
		$dSQepoch = ($SQepoch + ($d * 86400) );
		$dEQepoch = ($SQepochDAY + ($EQsec + ($d * 86400) ) );

		if ($EQsec < $SQsec)
			{
			$dEQepoch = ($dEQepoch + 86400);
			}

		$daySTART[$d] = date("Y-m-d H:i:s", $dSQepoch);
		$dayEND[$d] = date("Y-m-d H:i:s", $dEQepoch);

		$d++;
		}

	### Gather all current DID details
	$default_route='';
	$stmt="select did_id,did_pattern,did_route from vicidial_inbound_dids order by did_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$Drecords_to_grab = mysqli_num_rows($rslt);
	$h=0;
	$Ddid_id=array();
	$Ddid_pattern=array();
	$Ddid_route=array();
	while ($Drecords_to_grab > $h)
		{
		$row=mysqli_fetch_row($rslt);
		$Ddid_id[$h] =				$row[0];
		$Ddid_pattern[$h] =			$row[1];
		$Ddid_route[$h] =			$row[2];
		$h++;
		}

	### GRAB ALL RECORDS WITHIN RANGE FROM THE DATABASE ###
	$routeSQL='';
	if (strlen($route) > 0) {$routeSQL = "and did_route='$route'";}
	$stmt="SELECT call_date,extension,server_ip,uniqueid,did_route,did_id,caller_id_number,caller_id_name,channel from ".$vicidial_did_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and did_id IN($group_SQL) $routeSQL order by call_date;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$records_to_grab = mysqli_num_rows($rslt);
	$i=0;
	$Lcall_date=array();
	$Lextension=array();
	$Lserver_ip=array();
	$Luniqueid=array();
	$Ldid_route=array();
	$Ldid_id=array();
	$Lcaller_id_number=array();
	$Lcaller_id_name=array();
	$Lchannel=array();
	$Ldid_pattern=array();
	$Ldid_route_current=array();

	while ($i < $records_to_grab)
		{
		$row=mysqli_fetch_row($rslt);
		$Lcall_date[$i] =			$row[0];
		$Lextension[$i] =			$row[1];
		$Lserver_ip[$i] =			$row[2];
		$Luniqueid[$i] =			$row[3];
		$Ldid_route[$i] =			$row[4];
		$Ldid_id[$i] =				$row[5];
		$Lcaller_id_number[$i] =	$row[6];
		$Lcaller_id_name[$i] =		$row[7];
		$Lchannel[$i] =				$row[8];
		$Ldid_pattern[$i] =			'-undefined-';
		$Ldid_route_current[$i] =	'-undefined-';

		$h=0;
		while ($Drecords_to_grab > $h)
			{
			if ($Ddid_id[$h] == "$Ldid_id[$i]")
				{
				$Ldid_pattern[$i] =			$Ddid_pattern[$h];
				$Ldid_route_current[$i] =	$Ddid_route[$h];
				}
			$h++;
			}
		$i++;
		}


	###################################################
	### DID DETAIL SECTION ###
	if ($i > 0)
		{
		$ASCII_text.=_QXZ("DID Detail").":\n";
		$ASCII_text.="+---------------------+--------------------+----------------------+-----------------+----------------------+----------------------+----------------------+---------+--------------------+----------------------+------------------------------------------------------------------------------------------------------+\n";
		$ASCII_text.="| "._QXZ("CALL DATE",19)." | "._QXZ("DID EXTEN",18)." | "._QXZ("DID PATTERN",20)." | "._QXZ("SERVER",15)." | "._QXZ("UNIQUEID", 20)." | "._QXZ("CALL ROUTE", 20)." | "._QXZ("DID ROUTE", 20)." | "._QXZ("DID ID", 7)." | "._QXZ("CID NUMBER", 18)." | "._QXZ("CID NAME", 20)." | "._QXZ("CHANNEL", 100)." |\n";
		$ASCII_text.="+---------------------+--------------------+----------------------+-----------------+----------------------+----------------------+----------------------+---------+--------------------+----------------------+------------------------------------------------------------------------------------------------------+\n";

	#	$ASCII_text.=-+---------------------+--------------------+----------------------+-----------------+----------------------+----------------------+----------------------+---------+--------------------+----------------------+------------------------------------------------------------------------------------------------------+
	#	$ASCII_text.=-| 2022-05-10 08:12:09 | 8135551124         | 8135551124           | 192.168.198.5   | 1652184729.5681      | EXTEN                | CALLMENU             | 97      | 3125551212         | station 352          | IAX2/cc352-1931                                                                                      |

		$CSV_text1.="\""._QXZ("DID Detail").":\"\n";
		$CSV_text1.="\""._QXZ("CALL DATE")."\",\""._QXZ("DID EXTEN")."\",\""._QXZ("DID PATTERN")."\",\""._QXZ("SERVER")."\",\""._QXZ("UNIQUEID")."\",\""._QXZ("CALL ROUTE")."\",\""._QXZ("DID CURRENT ROUTE")."\",\""._QXZ("DID ID")."\",\""._QXZ("CID NUMBER")."\",\""._QXZ("CID NAME")."\",\""._QXZ("CHANNEL")."\"\n";

		$i=0;
		while ($i < $records_to_grab)
			{
			$CSV_text1.="\"$Lcall_date[$i]\",\"$Lextension[$i]\",\"$Ldid_pattern[$i]\",\"$Lserver_ip[$i]\",\"$Luniqueid[$i]\",\"$Ldid_route[$i]\",\"$Ldid_route_current[$i]\",\"$Ldid_id[$i]\",\"$Lcaller_id_number[$i]\",\"$Lcaller_id_name[$i]\",\"$Lchannel[$i]\"\n";

			# ASCII variable formatting
			$Lcall_date[$i] =			sprintf("%-19s", $Lcall_date[$i]);
			$Lextension[$i] =			sprintf("%-18s", $Lextension[$i]);
			$Ldid_pattern[$i] =			sprintf("%-20s", $Ldid_pattern[$i]);
			$Lserver_ip[$i] =			sprintf("%-15s", $Lserver_ip[$i]);
			$Luniqueid[$i] =			sprintf("%-20s", $Luniqueid[$i]);
			$Ldid_route[$i] =			sprintf("%-20s", $Ldid_route[$i]);
			$Ldid_route_current[$i] =	sprintf("%-20s", $Ldid_route_current[$i]);
			$Ldid_id[$i] =				sprintf("%-7s", $Ldid_id[$i]);
			$Lcaller_id_number[$i] =	sprintf("%-18s", $Lcaller_id_number[$i]);
			$Lcaller_id_name[$i] =		sprintf("%-20s", $Lcaller_id_name[$i]);
			$Lchannel[$i] =				sprintf("%-100s", $Lchannel[$i]);

			$ASCII_text.="| $Lcall_date[$i] | $Lextension[$i] | $Ldid_pattern[$i] | $Lserver_ip[$i] | $Luniqueid[$i] | $Ldid_route[$i] | $Ldid_route_current[$i] | $Ldid_id[$i] | $Lcaller_id_number[$i] | $Lcaller_id_name[$i] | $Lchannel[$i] |\n";

			$i++;
			}

		$FtotCALLS =	sprintf("%18s", $i);

		$ASCII_text.="+---------------------+--------------------+----------------------+-----------------+----------------------+----------------------+----------------------+---------+--------------------+----------------------+------------------------------------------------------------------------------------------------------+\n";
		$ASCII_text.="| "._QXZ("TOTAL CALLS",19,"r")." | $FtotCALLS |\n";
		$ASCII_text.="+---------------------+--------------------+\n";
		$CSV_text1.="\"\",\"\",\""._QXZ("TOTALS")."\",\"$FtotCALLS\"\n";
		$MAIN.=$ASCII_text;
		}



	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
	$MAIN.="</PRE>\n";
	$MAIN.="</TD></TR></TABLE>\n";

	$MAIN.="</BODY></HTML>\n";
	}

	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_DIDstats_$US$FILE_TIME.csv";
		$CSV_var="CSV_text".$file_download;
		$CSV_text=preg_replace('/^ +/', '', $$CSV_var);
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
