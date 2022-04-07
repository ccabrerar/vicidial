<?php 
# AST_agentDIDstats.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 161207-1952 - First build
# 170409-1550 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 191013-0836 - Fixes for PHP7
# 220303-1422 - Added allow_web_debug system setting
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
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
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
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if ($file_download>0) {$DB="";}
if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Agent DID Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,allow_web_debug FROM system_settings;";
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
	$SSallow_web_debug =			$row[6];
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
$table_name="vicidial_did_log";
$archive_table_name=use_archive_table($table_name);
if ($archive_table_name!=$table_name) {$archives_available="Y";}

if ($search_archived_data) 
	{
	$vicidial_did_log_table=use_archive_table("vicidial_did_log");
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	}
else
	{
	$vicidial_did_log_table="vicidial_did_log";
	$vicidial_closer_log_table="vicidial_closer_log";
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
	$group[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $group[$i]);
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
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
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

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#agentDIDstats$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=POST name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD align=center valign=top>\n";
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


$MAIN.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

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
$MAIN.="</TD>";
$MAIN.="<TD align=left valign=top><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>\n";
/*
$MAIN.="<SELECT SIZE=1 NAME=shift>\n";
$MAIN.="<option selected value=\"$shift\">$shift</option>\n";
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
*/

if ($archives_available=="Y") 
	{
	$MAIN.="<BR><BR><BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

$MAIN.="</FONT></TD><TD align=left valign=top>\n";
$MAIN.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$MAIN.=" &nbsp;";
#$MAIN.="<select name='report_display_type'>";
#if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>$report_display_type</option>";}
#$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n";
$MAIN.="<BR><BR>";
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

	if ($shift == 'AM') 
		{
	#	$time_BEGIN=$AM_shift_BEGIN;
	#	$time_END=$AM_shift_END;
	#	if (strlen($time_BEGIN) < 6) {$time_BEGIN = "03:45:00";}   
	#	if (strlen($time_END) < 6) {$time_END = "15:15:00";}
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}   
		if (strlen($time_END) < 6) {$time_END = "11:59:59";}
	#	if (strlen($time_BEGIN) < 6) {$time_BEGIN = "12:00:00";}   
	#	if (strlen($time_END) < 6) {$time_END = "11:59:59";}
		}
	if ($shift == 'PM') 
		{
	#	$time_BEGIN=$PM_shift_BEGIN;
	#	$time_END=$PM_shift_END;
	#	if (strlen($time_BEGIN) < 6) {$time_BEGIN = "15:15:00";}
	#	if (strlen($time_END) < 6) {$time_END = "23:15:00";}
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


	$MAIN.=_QXZ("Inbound DID Report",40)." $NOW_TIME\n";
	$MAIN.="\n";
	$MAIN.=_QXZ("Time range")." $DURATIONday "._QXZ("days").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
	#$MAIN.="Time range day sec: $SQsec - $EQsec   Day range in epoch: $SQepoch - $EQepoch   Start: $SQepochDAY\n";

	$CSV_text.="\""._QXZ("Inbound DID Report")."\",\"$NOW_TIME\"\n\n";
	$CSV_text.="\""._QXZ("Time range")." $DURATIONday "._QXZ("days").":\",\"$query_date_BEGIN "._QXZ("to")." $query_date_END\"\n\n";

	$query_date_array=createDateRangeArray("$query_date_BEGIN", "$query_date_END");

	# Generate header - each date is going to use the same header rather than have a dynamic header per date depending on what DIDs received calls

	$header_stmt="select distinct did_id from ".$vicidial_did_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and did_id IN($group_SQL);";
	if ($DB) {echo "$header_stmt\n";}
	$header_rslt=mysql_to_mysqli($header_stmt, $link);
	$ASCII_headerA="+----------------------+--------------------------------+";
	$ASCII_headerB="| Agent ID             | Full Name                      |";
	$CSV_header="\"Agent ID\",\"Full Name\",";
	$did_array=array();
	while ($header_row=mysqli_fetch_row($header_rslt)) {
		array_push($did_array, "$header_row[0]");
	}

	if (count($did_array)>0) {
		$did_stmt="select substr(did_description, 1, 30), did_id from vicidial_inbound_dids where did_id in (".implode(",", $did_array).") order by did_description asc";
		if ($DB) {echo "$did_stmt\n";}
		$did_rslt=mysql_to_mysqli($did_stmt, $link);
		$did_description_array=array();
		while ($did_row=mysqli_fetch_row($did_rslt)) {
			$ASCII_headerA.="--------------------------------+";
			$ASCII_headerB.=" ".sprintf("%30s", $did_row[0])." |";
			$CSV_header.="\"".$did_row[0]."\",";
			$did_description_array["$did_row[1]"]=$did_row[0];
		}
	}

	$ASCII_headerA.="-------+\n";
	$ASCII_headerB.=" TOTAL |\n";
	$CSV_header.="\"TOTAL\"\n";
	$ASCII_text="";

	if (count($did_array)>0) {
		$sunday_array=array();
		$saturday_array=array();
		$q=1;
		foreach ($query_date_array as &$current_date) {
			$user_array=array();
			$start_date = "$current_date $time_BEGIN";   
			$end_date = "$current_date $time_END";
			if (date("w", strtotime($current_date))==0 || $q==1) {
				array_push($sunday_array, $current_date);
			}
			if (date("w", strtotime($current_date))==6 || $q==count($query_date_array)) {
				array_push($saturday_array, $current_date);
			}

			$stmt="select did_id, uniqueid from vicidial_did_log where call_date>='$start_date' and call_date<='$end_date' and did_id in (".implode(",", $did_array).") order by did_id asc";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if (mysqli_num_rows($rslt)>0) {
				while ($row=mysqli_fetch_array($rslt)) {
					$agent_stmt="select vicidial_users.user, vicidial_users.full_name from ".$vicidial_closer_log_table.", vicidial_users where uniqueid='$row[uniqueid]' and ".$vicidial_closer_log_table.".user=vicidial_users.user";
					if ($DB) {echo "$agent_stmt\n";}
					$agent_rslt=mysql_to_mysqli($agent_stmt, $link);
					while($agent_row=mysqli_fetch_array($agent_rslt)) {
						$user_array["$agent_row[0]|$agent_row[1]"][$row[0]]++;
					}
				}

				$ASCII_text.=" $current_date\n";
				$ASCII_text.=$ASCII_headerA.$ASCII_headerB.$ASCII_headerA;

				$CSV_text.="\"$current_date\"\n";
				$CSV_text.=$CSV_header;
				#while (list($key, $val)=each($user_array)) {
				foreach($user_array as $key => $val) {
					$agent_total=0;
					$agent_info=explode("|", $key);
					$ASCII_text.="| ".sprintf("%-20s", $agent_info[0]);
					$ASCII_text.=" | ".sprintf("%-30s", $agent_info[1]);
					$CSV_text.="\"$agent_info[0]\",\"$agent_info[1]\",";
					# while (list($key2, $val2)=each($did_description_array)) {
					foreach($did_description_array as $key2 => $val2) {
						$ASCII_text.=" | ".sprintf("%30s", ($user_array["$key"][$key2]+0));
						$CSV_text.="\"".($user_array["$key"][$key2]+0)."\",";
						$agent_total+=($user_array["$key"][$key2]+0);
					}
					reset($did_description_array);
					$ASCII_text.=" | ".sprintf("%5s", $agent_total)." |\n";
					$CSV_text.="\"$agent_total\"\n";
					$date_total+=$agent_total;
				}
				$ASCII_text.=$ASCII_headerA."\n\n";
				$CSV_text.="\n\n";
			}

			$q++;
		}

		for ($q=0; $q<count($sunday_array); $q++) {
			$user_array=array();
			$start_date = "$sunday_array[$q] $time_BEGIN";   
			$end_date = "$saturday_array[$q] $time_END";
			$date_total=0;

			$stmt="select did_id, uniqueid from vicidial_did_log where call_date>='$start_date' and call_date<='$end_date' and did_id in (".implode(",", $did_array).") order by did_id asc";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if (mysqli_num_rows($rslt)>0) {
				while ($row=mysqli_fetch_array($rslt)) {
					$agent_stmt="select vicidial_users.user, vicidial_users.full_name from ".$vicidial_closer_log_table.", vicidial_users where uniqueid='$row[uniqueid]' and ".$vicidial_closer_log_table.".user=vicidial_users.user";
					if ($DB) {echo "$agent_stmt\n";}
					$agent_rslt=mysql_to_mysqli($agent_stmt, $link);
					while($agent_row=mysqli_fetch_array($agent_rslt)) {
						$user_array["$agent_row[0]|$agent_row[1]"][$row[0]]++;
					}
				}

				$ASCII_text.=" Week of $sunday_array[$q] thru $saturday_array[$q]\n";
				$ASCII_text.=$ASCII_headerA.$ASCII_headerB.$ASCII_headerA;
				$CSV_text.="\"Week of $sunday_array[$q] thru $saturday_array[$q]\"\n";
				$CSV_text.=$CSV_header;
				#while (list($key, $val)=each($user_array)) {
				foreach($user_array as $key => $val) {
					$agent_total=0;
					$agent_info=explode("|", $key);
					$ASCII_text.="| ".sprintf("%-20s", $agent_info[0]);
					$ASCII_text.=" | ".sprintf("%-30s", $agent_info[1]);
					$CSV_text.="\"$agent_info[0]\",\"$agent_info[1]\",";
					#while (list($key2, $val2)=each($did_description_array)) {
					foreach($did_description_array as $key2 => $val2) {
						$ASCII_text.=" | ".sprintf("%30s", ($user_array["$key"][$key2]+0));
						$CSV_text.="\"".($user_array["$key"][$key2]+0)."\",";
						$agent_total+=($user_array["$key"][$key2]+0);
					}
					reset($did_description_array);
					$ASCII_text.=" | ".sprintf("%5s", $agent_total)." |\n";
					$CSV_text.="\"$agent_total\"\n";
					$date_total+=$agent_total;
				}
				$ASCII_text.=$ASCII_headerA."\n\n";
				$CSV_text.="\n\n";
			}
		}
	}
	if ($report_display_type=="HTML")
		{
		$MAIN.=$GRAPH_text;
		}
	else
		{
		$MAIN.=$ASCII_text;
		}



	# $CSV_text.="\""._QXZ("TOTAL")."\",\"$totCALLS\"\n";

	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
	$MAIN.="</PRE>\n";
	$MAIN.="</TD></TR></TABLE>\n";

	$MAIN.="</BODY></HTML>\n";
	}

	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_agentDIDstats_$US$FILE_TIME.csv";
		$CSV_text=preg_replace('/^ +/', '', $CSV_text);
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
