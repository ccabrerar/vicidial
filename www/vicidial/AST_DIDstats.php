<?php 
# AST_DIDstats.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 90601-1443 - First build
# 100116-0620 - Bug fixes
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110703-1809 - Added download option
# 111104-1133 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 130414-0113 - Added report logging
# 130610-1017 - Finalized changing of all ereg instances to preg
# 130621-0758 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0734 - Changed to mysqli PHP functions
# 140108-0742 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 140502-1208 - Added 9am-11pm option
# 140808-1036 - Added server breakdown section
# 141020-0848 - Added 9am-10pm and 10am-6pm options
# 141113-2040 - Finalized adding QXZ translation to all admin files
# 141230-1508 - Added code for on-the-fly language translations display
# 151125-1614 - Added search archive option
# 160227-1150 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 160819-0054 - Fixed bug causing TEXT report to repeat data
# 170217-2115 - Added time entry option for date ranges
# 170227-1717 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 191013-0850 - Fixes for PHP7
# 210525-1715 - Fixed help display, modification for more call details
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

if (strlen($shift)<2) {$shift='--';}

$report_name = 'Inbound DID Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
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
$log_tables_array=array("vicidial_did_log", "vicidial_closer_log", "live_inbound_log");
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
	}
else
	{
	$vicidial_did_log_table="vicidial_did_log";
	$vicidial_closer_log_table="vicidial_closer_log";
	$live_inbound_log_table="live_inbound_log";
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

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (!isset($time_BEGIN)) {$time_BEGIN = "00:00:00";}
if (!isset($time_END)) {$time_END = "23:59:59";}

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
$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='".$LOGfull_url."?DB=".$DB."&file_download=".$file_download."&query_date=".$query_date."&end_date=".$end_date."&shift=".$shift."&report_display_type=".$report_display_type."$groupQS', webserver='$webserver_id';";
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

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#DIDstats$NWE\n";
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

if ($archives_available=="Y") 
	{
	$MAIN.="<BR><BR><BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

$MAIN.="</FONT></TD><TD align=left valign=top>\n";
$MAIN.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$MAIN.=" &nbsp;";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
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

	$MAIN.=_QXZ("Inbound DID Report",40)." $NOW_TIME\n";
	$MAIN.="\n";
	$MAIN.=_QXZ("Time range")." $DURATIONday "._QXZ("days").": $query_date_BEGIN "._QXZ("to")." $query_date_END   $suffix\n\n";
	#$MAIN.="Time range day sec: $SQsec - $EQsec   Day range in epoch: $SQepoch - $EQepoch   Start: $SQepochDAY\n";

	$CSV_text1.="\""._QXZ("Inbound DID Report")."\",\"$NOW_TIME\"\n\n";
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

	##########################################################################
	#########  CALCULATE ALL OF THE 15-MINUTE PERIODS NEEDED FOR ALL DAYS ####

	### BUILD HOUR:MIN DISPLAY ARRAY ###
	$i=0;
	$h=4;
	$j=0;
	$Zhour=1;
	$active_time=0;
	$hour =		($SQtime_ARY[0] - 1);
	$startSEC = ($SQsec - 900);
	$endSEC =	($SQsec - 1);
	if ($SQtime_ARY[1] > 14) 
		{
		$h=1;
		$hour++;
		if ($hour < 10) {$hour = "0$hour";}
		}
	if ($SQtime_ARY[1] > 29) {$h=2;}
	if ($SQtime_ARY[1] > 44) {$h=3;}

	$HMdisplay=array();
	$HMstart=array();
	$HMend=array();
	$HMSepoch=array();
	$HMEepoch=array();
	while ($i < 96)
		{
		$startSEC = ($startSEC + 900);
		$endSEC = ($endSEC + 900);
		$time = '      ';
		if ($h >= 4)
			{
			$hour++;
			if ($Zhour == '00') 
				{
				$startSEC=0;
				$endSEC=899;
				}
			$h=0;
			if ($hour < 10) {$hour = "0$hour";}
			$Stime="$hour:00";
			$Etime="$hour:15";
			$time = "+$Stime-$Etime+";
			}
		if ($h == 1)
			{
			$Stime="$hour:15";
			$Etime="$hour:30";
			$time = " $Stime-$Etime ";
			}
		if ($h == 2)
			{
			$Stime="$hour:30";
			$Etime="$hour:45";
			$time = " $Stime-$Etime ";
			}
		if ($h == 3)
			{
			$Zhour=$hour;
			$Zhour++;
			if ($Zhour < 10) {$Zhour = "0$Zhour";}
			if ($Zhour == 24) {$Zhour = "00";}
			$Stime="$hour:45";
			$Etime="$Zhour:00";
			$time = " $Stime-$Etime ";
			if ($Zhour == '00') 
				{$hour = ($Zhour - 1);}
			}

		if ( ( ($startSEC >= $SQsec) and ($endSEC <= $EQsec) and ($EQsec > $SQsec) ) or 
			( ($startSEC >= $SQsec) and ($EQsec < $SQsec) ) or 
			( ($endSEC <= $EQsec) and ($EQsec < $SQsec) ) )
			{
			$HMdisplay[$j] =	$time;
			$HMstart[$j] =		$Stime;
			$HMend[$j] =		$Etime;
			$HMSepoch[$j] =		$startSEC;
			$HMEepoch[$j] =		$endSEC;

			$j++;
			}

		$h++;
		$i++;
		}

	$TOTintervals = $j;


	### GRAB ALL RECORDS WITHIN RANGE FROM THE DATABASE ###
	$stmt="select UNIX_TIMESTAMP(call_date),extension,server_ip,uniqueid,did_route,did_id from ".$vicidial_did_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and did_id IN($group_SQL) order by extension;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$records_to_grab = mysqli_num_rows($rslt);
	$i=0;
	$ut=array();
	$dt=array();
	$extension=array();
	$did_server_ip=array();
	$unique_ids=array();
	$extension[0]='';
	$did_server_ip[0]='';
	while ($i < $records_to_grab)
		{
		$row=mysqli_fetch_row($rslt);
		$dt[$i] =			0;
		$ut[$i] =			($row[0] - $SQepochDAY);
		$extension[$i] =	$row[1];
		$did_server_ip[$i] =$row[2];

		$unique_ids["$row[1]"]["$row[4]"].="$row[3]|";

		while($ut[$i] >= 86400) 
			{
			$ut[$i] = ($ut[$i] - 86400);
			$dt[$i]++;
			}
		if ( ($ut[$i] <= $EQsec) and ($EQsec < $SQsec) )
			{
			$dt[$i] = ($dt[$i] - 1);
			}

		$i++;
		}

	### Find default route
	$default_route='';
	$stmt="select did_route from vicidial_inbound_dids where did_pattern='default';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$Drecords_to_grab = mysqli_num_rows($rslt);
	if ($Drecords_to_grab > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$default_route =	$row[0];
		}

	###################################################
	### TOTALS DID SUMMARY SECTION ###
	if (strlen($extension[0]) > 0)
		{
		$ASCII_text.=_QXZ("DID Summary").":\n";
		$ASCII_text.="+--------------------+--------------------------------+----------------------+------------+------------+------------+------------+\n";
		$ASCII_text.="| "._QXZ("DID",18)." | "._QXZ("DESCRIPTION",30)." | "._QXZ("CURRENT ROUTE",20)." | "._QXZ("CALLS",10)." | "._QXZ("LOST", 10)." | "._QXZ("TRANSFER", 10)." | "._QXZ("ROUTED", 10)." |\n";
		$ASCII_text.="+--------------------+--------------------------------+----------------------+------------+------------+------------+------------+\n";

		$CSV_text1.="\""._QXZ("DID Summary").":\"\n";
		$CSV_text1.="\""._QXZ("DID")."\",\""._QXZ("DESCRIPTION")."\",\""._QXZ("CURRENT ROUTE")."\",\""._QXZ("CALLS")."\",\""._QXZ("LOST")."\",\""._QXZ("TRANSFER")."\",\""._QXZ("ROUTED")."\"\n";

		$GRAPH_text.="</PRE>";

		$stats_array = array_group_count($extension, 'desc');
		$stats_array_ct = count($stats_array);

		$d=0;
		$max_calls=1;
		$graph_stats=array();
		while ($d < $stats_array_ct)
			{
			$stat_description =		' *** default *** ';
			$stat_route =			$default_route;
			$lost_calls=0;
			$transferred_calls=0;
			$routed_calls=0;

			$stat_record_array = explode(' ',$stats_array[$d]);
			$stat_count = ($stat_record_array[0] + 0);
			$stat_pattern = $stat_record_array[1];
			if ($stat_count>$max_calls) {$max_calls=$stat_count;}
			$graph_stats[$d][0]=$stat_pattern;
			$graph_stats[$d][1]=$stat_count;

			$stmt="select did_description,did_route from vicidial_inbound_dids where did_pattern='$stat_pattern';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$details_to_grab = mysqli_num_rows($rslt);
			if ($details_to_grab > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$stat_description =		$row[0];
				$stat_route =			$row[1];
				
				$did_activity=$unique_ids["$stat_pattern"];
				# print_r($did_activity);
				foreach ($did_activity as $keyroute => $uid_str)
					{
					$uid_str=preg_replace('/\|$/', '', $uid_str);
					$did_activity_uid_array=explode("|", $uid_str);
					$did_activity_call_count=count($did_activity_uid_array);

					if (preg_match('/EXTEN|PHONE|VOICEMAIL/', $keyroute)) 
						{
						$transferred_calls+=$did_activity_call_count;
						}
					else
						{
						if (preg_match('/CALLMENU/', $keyroute)) 
							{
							$did_lookup_stmt="select count(distinct uniqueid) from ".$live_inbound_log_table." where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and uniqueid IN('".implode("','", $did_activity_uid_array)."');";
							}
						else
							{
							$did_lookup_stmt="select count(distinct uniqueid) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and uniqueid IN('".implode("','", $did_activity_uid_array)."')";
							}
						$did_lookup_rslt=mysql_to_mysqli($did_lookup_stmt, $link);
						if ($DB) {$MAIN.="$did_lookup_stmt<BR>\n";}
						$did_lookup_row=mysqli_fetch_row($did_lookup_rslt);
						$routed_calls+=$did_lookup_row[0];
						}
					}
				}
			$lost_calls=$stat_count-$transferred_calls-$routed_calls;

			$graph_stats[$d][2]=$lost_calls;
			$graph_stats[$d][3]=$transferred_calls;
			$graph_stats[$d][4]=$routed_calls;

			$totCALLS =			($totCALLS + $stat_count);
			$totLOSTCALLS =			($totLOSTCALLS + $lost_calls);
			$totTRANSFERREDCALLS =			($totTRANSFERREDCALLS + $transferred_calls);
			$totROUTEDCALLS =			($totROUTEDCALLS + $routed_calls);
			$stat_pattern =		sprintf("%-18s", $stat_pattern);
			$stat_description =	sprintf("%-30s", $stat_description);
			while (strlen($stat_description) > 30) {$stat_description = preg_replace('/.$/i', '',$stat_description);}
			$stat_route =		sprintf("%-20s", $stat_route);
			$stat_count =		sprintf("%10s", $stat_count);
			$lost_calls =		sprintf("%10s", $lost_calls);
			$transferred_calls =		sprintf("%10s", $transferred_calls);
			$routed_calls =		sprintf("%10s", $routed_calls);

			$CSV_text1.="\"$stat_pattern\",\"$stat_description\",\"$stat_route\",\"$stat_count\"\n";

			$stat_pattern = "<a href=\"admin.php?ADD=3311&did_pattern=$stat_pattern\">$stat_pattern</a>";

			$ASCII_text.="| $stat_pattern | $stat_description | $stat_route | $stat_count | $lost_calls | $transferred_calls | $routed_calls |\n";
			$d++;
			}

			#########
			$graph_array=array("DIDSummarydata|||integer|");
			$graph_id++;
			$default_graph="bar"; # Graph that is initally displayed when page loads
			include("graph_color_schemas.inc"); 

			$graph_totals_array=array();
			$graph_totals_rawdata=array();
			for ($q=0; $q<count($graph_array); $q++) 
				{
				$graph_info=explode("|", $graph_array[$q]); 
				$current_graph_total=0;
				$dataset_name=$graph_info[0];
				$dataset_index=$graph_info[1]; $dataset_type=$graph_info[3];
				if ($q==0) {$preload_dataset=$dataset_name;}  # Used below to load initial graph

				$JS_text.="var $dataset_name = {\n";
				# $JS_text.="\ttype: \"\",\n";
				# $JS_text.="\t\tdata: {\n";
				$datasets="\t\tdatasets: [\n";

				$graph_labels=array("", "CALLS", "LOST", "TRANSFER", "ROUTED");
				for ($n=1; $n<count($graph_stats[0]); $n++)
					{

					$datasets.="\t\t\t{\n";
					$datasets.="\t\t\t\tlabel: \"".$graph_labels[$n]."\",\n";
					$datasets.="\t\t\t\tfill: false,\n";

					$labels="\t\tlabels:[";
					$data="\t\t\t\tdata: [";
					$graphConstantsA="\t\t\t\tbackgroundColor: [";
					$graphConstantsB="\t\t\t\thoverBackgroundColor: [";
					$graphConstantsC="\t\t\t\thoverBorderColor: [";
					for ($d=0; $d<count($graph_stats); $d++) 
						{
						$labels.="\"".$graph_stats[$d][0]."\",";
						$data.="\"".$graph_stats[$d][$n]."\",";
						$current_graph_total+=$graph_stats[$d][$n];
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
					$datasets.="\t\t\t},\n";
	
					}

				$datasets.="\t\t]\n";
				$datasets.="\t}\n";

				$JS_text.=$labels.$datasets;
				# $JS_text.="}\n";
				# $JS_text.="prepChart('$default_graph', $graph_id, $q, $dataset_name);\n";
				$JS_text.="var main_ctx = document.getElementById(\"CanvasID".$graph_id."_".$q."\");\n";
				$JS_text.="var GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: '$default_graph', $chart_options data: $dataset_name});\n";
				}

			$graph_count=count($graph_array);
			$graph_title=_QXZ("DID Summary");
			include("graphcanvas.inc");
			$HEADER.=$HTML_graph_head;
			$GRAPH_text.=$graphCanvas."<PRE>";


			$FtotCALLS =	sprintf("%10s", $totCALLS);
			$FtotLOSTCALLS =	sprintf("%10s", $totLOSTCALLS);
			$FtotTRANSFERREDCALLS =	sprintf("%10s", $totTRANSFERREDCALLS);
			$FtotROUTEDCALLS =	sprintf("%10s", $totROUTEDCALLS);

		$ASCII_text.="+--------------------+--------------------------------+----------------------+------------+------------+------------+------------+\n";
		$ASCII_text.="| "._QXZ("TOTALS",74,"r")." | $FtotCALLS | $FtotLOSTCALLS | $FtotTRANSFERREDCALLS | $FtotROUTEDCALLS |\n";
		$ASCII_text.="+----------------------------------------------------------------------------+------------+------------+------------+------------+\n";
		$CSV_text1.="\"\",\"\",\""._QXZ("TOTALS")."\",\"$FtotCALLS\",\"$FtotLOSTCALLS\",\"$FtotTRANSFERREDCALLS\",\"$FtotROUTEDCALLS\"\n";
		#$MAIN.=$GRAPH;
		}



	### PARSE THROUGH ALL RECORDS AND GENERATE STATS ###
	$MT[0]='0';
	$totCALLS=0;
	$totCALLSmax=0;
	$totCALLSdate=$MT;
	$qrtCALLS=$MT;
	$qrtCALLSavg=$MT;
	$qrtCALLSmax=$MT;
	$qrtCALLSsec=array();
	$qrtDROPS=array();
	$qrtQUEUEavg=$MT;
	$qrtQUEUEmax=$MT;
	$qs=array();
	$j=0;
	while ($j < $TOTintervals)
		{
		$jd__0[$j]=0; $jd_20[$j]=0; $jd_40[$j]=0; $jd_60[$j]=0; $jd_80[$j]=0; $jd100[$j]=0; $jd120[$j]=0; $jd121[$j]=0;
		$Phd__0[$j]=0; $Phd_20[$j]=0; $Phd_40[$j]=0; $Phd_60[$j]=0; $Phd_80[$j]=0; $Phd100[$j]=0; $Phd120[$j]=0; $Phd121[$j]=0;
		$qrtCALLS[$j]=0; $qrtCALLSmax[$j]=0;
		$i=0;
		while ($i < $records_to_grab)
			{
			if ( ($ut[$i] >= $HMSepoch[$j]) and ($ut[$i] <= $HMEepoch[$j]) )
				{
				$totCALLS++;
				$qrtCALLS[$j]++;
				$dtt = $dt[$i];
				$totCALLSdate[$dtt]++;
				if ($totCALLSmax < $ls[$i]) {$totCALLSmax = $ls[$i];}
				if ($qrtCALLSmax[$j] < $ls[$i]) {$qrtCALLSmax[$j] = $ls[$i];}

				if ($qs[$i] == 0) {$hd__0[$j]++;}
				if ( ($qs[$i] > 0) and ($qs[$i] <= 20) ) {$hd_20[$j]++;}
				if ( ($qs[$i] > 20) and ($qs[$i] <= 40) ) {$hd_40[$j]++;}
				if ( ($qs[$i] > 40) and ($qs[$i] <= 60) ) {$hd_60[$j]++;}
				if ( ($qs[$i] > 60) and ($qs[$i] <= 80) ) {$hd_80[$j]++;}
				if ( ($qs[$i] > 80) and ($qs[$i] <= 100) ) {$hd100[$j]++;}
				if ( ($qs[$i] > 100) and ($qs[$i] <= 120) ) {$hd120[$j]++;}
				if ($qs[$i] > 120) {$hd121[$j]++;}
				}
			
			$i++;
			}

		$j++;
		}



	###################################################
	### TOTALS SERVER IP SUMMARY SECTION ###
	if (strlen($did_server_ip[0]) > 0)
		{
		$ASCII_text.=_QXZ("Server Summary").":\n";
		$ASCII_text.="+----------------------------+------------+\n";
		$ASCII_text.="| "._QXZ("SERVER",26)." | "._QXZ("CALLS",10)." |\n";
		$ASCII_text.="+----------------------------+------------+\n";

		$CSV_text1.="\""._QXZ("Server Summary").":\"\n";
		$CSV_text1.="\""._QXZ("Server")."\",\""._QXZ("CALLS")."\"\n";


		$SVstats_array = array_group_count($did_server_ip, 'desc');
		$SVstats_array_ct = count($SVstats_array);

		$d=0;
		$max_calls=1;
		$SVgraph_stats=array();
		while ($d < $SVstats_array_ct)
			{
			$stat_description =		' *** default *** ';
			$stat_route =			$default_route;

			$stat_record_array = explode(' ',$SVstats_array[$d]);
			$stat_count = ($stat_record_array[0] + 0);
			$stat_pattern = $stat_record_array[1];
			if ($stat_count>$max_calls) {$max_calls=$stat_count;}
			$SVgraph_stats[$d][0]=$stat_count;
			$SVgraph_stats[$d][1]=$stat_pattern;

			$stmt="select server_id from servers where server_ip='$stat_pattern';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$details_to_grab = mysqli_num_rows($rslt);
			if ($details_to_grab > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$server_id =		$row[0];
				}

			$SVtotCALLS =		($SVtotCALLS + $stat_count);
			$stat_pattern =		sprintf("%-26s", "$stat_pattern $server_id");
			$stat_count =		sprintf("%10s", $stat_count);

			$CSV_text1.="\"$stat_pattern\",\"$stat_count\"\n";
			$ASCII_text.="| $stat_pattern | $stat_count |\n";
			$d++;
			}

			#########
			$graph_array=array("ServerSummarydata|||integer|");
			$graph_id++;
			$default_graph="bar"; # Graph that is initally displayed when page loads
			include("graph_color_schemas.inc"); 

			$graph_totals_array=array();
			$graph_totals_rawdata=array();
			for ($q=0; $q<count($graph_array); $q++) {
				$graph_info=explode("|", $graph_array[$q]); 
				$current_graph_total=0;
				$dataset_name=$graph_info[0];
				$dataset_index=$graph_info[1]; $dataset_type=$graph_info[3];
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
				for ($d=0; $d<count($SVgraph_stats); $d++) {
					$labels.="\"".$SVgraph_stats[$d][1]."\",";
					$data.="\"".$SVgraph_stats[$d][0]."\",";
					$current_graph_total+=$SVgraph_stats[$d][0];
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
			$graph_title=_QXZ("Server Summary");
			include("graphcanvas.inc");
			$HEADER.=$HTML_graph_head;
			$GRAPH_text.=$graphCanvas."<PRE>";

			$FtotCALLS =	sprintf("%10s", $SVtotCALLS);

		$ASCII_text.="+----------------------------+------------+\n";
		$ASCII_text.="| "._QXZ("TOTALS",26,"r")." | $FtotCALLS |\n";
		$ASCII_text.="+----------------------------+------------+\n";
		$CSV_text1.=_QXZ("TOTALS")."\",\"$FtotCALLS\"\n";
		#$MAIN.=$GRAPH;
		}



	###################################################
	### TOTALS DATE SUMMARY SECTION ###
	$ASCII_text.="\n"._QXZ("Date Summary").":\n";
	$ASCII_text.="+-------------------------------------------+--------+\n";
	$ASCII_text.="| "._QXZ("SHIFT",41)." |        |\n";
	$ASCII_text.="| "._QXZ("DATE-TIME RANGE",41)." | "._QXZ("CALLS",6)." |\n";
	$ASCII_text.="+-------------------------------------------+--------+\n";

	$CSV_text1.="\n\""._QXZ("Date Summary").":\"\n";
	$CSV_text1.="\""._QXZ("SHIFT DATE-TIME RANGE")."\",\""._QXZ("CALLS")."\"\n";
	$d=0;
	$max_calls=1;
	$graph_stats=array();
	$totCALLSavgDATE=array();
	while ($d < $DURATIONday)
		{
		if ($totCALLSdate[$d] < 1) {$totCALLSdate[$d]=0;}
		
		$totCALLSavgDATE[$d] = MathZDC($totCALLSsecDATE[$d], $totCALLSdate[$d]);

		$totTIME_M = MathZDC($totCALLSsecDATE[$d], 60);
		$totTIME_M_int = round($totTIME_M, 2);
		$totTIME_M_int = intval("$totTIME_M");
		$totTIME_S = ($totTIME_M - $totTIME_M_int);
		$totTIME_S = ($totTIME_S * 60);
		$totTIME_S = round($totTIME_S, 0);
		if ($totTIME_S < 10) {$totTIME_S = "0$totTIME_S";}
		$totTIME_MS = "$totTIME_M_int:$totTIME_S";
		$totTIME_MS =		sprintf("%8s", $totTIME_MS);


		if ($totCALLSdate[$d]>$max_calls) {$max_calls=$totCALLSdate[$d];}
		$graph_stats[$d][0]=$totCALLSdate[$d];
		$graph_stats[$d][1]=substr($daySTART[$d],0,10);

		if ($totCALLSdate[$d] < 1) 
			{$totCALLSdate[$d]='';}
		$totCALLSdate[$d] =	sprintf("%6s", $totCALLSdate[$d]);

		$ASCII_text.="| $daySTART[$d] - $dayEND[$d] | $totCALLSdate[$d] |\n";
		$CSV_text1.="\"$daySTART[$d]\",\"$dayEND[$d]\",\"$totCALLSdate[$d]\"\n";
		$d++;
		}

		#########
		$graph_array=array("DateSummarydata|||integer|");
		$graph_id++;
		$default_graph="line"; # Graph that is initally displayed when page loads
		include("graph_color_schemas.inc"); 

		$graph_totals_array=array();
		$graph_totals_rawdata=array();
		for ($q=0; $q<count($graph_array); $q++) {
			$graph_info=explode("|", $graph_array[$q]); 
			$current_graph_total=0;
			$dataset_name=$graph_info[0];
			$dataset_index=$graph_info[1]; $dataset_type=$graph_info[3];
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
		$graph_title=_QXZ("Date Summary");
		include("graphcanvas.inc");
		$HEADER.=$HTML_graph_head;
		$GRAPH_text.=$graphCanvas."<PRE>";


	$FtotCALLS =	sprintf("%6s", $totCALLS);

	$ASCII_text.="+-------------------------------------------+--------+\n";
	$ASCII_text.="| "._QXZ("TOTALS",41,"r")." | $FtotCALLS |\n";
	$ASCII_text.="+-------------------------------------------+--------+\n";
	$CSV_text1.="\""._QXZ("TOTALS")."\",\"$FtotCALLS\"\n";
	#$MAIN.=$GRAPH;

	if ($report_display_type=="HTML")
		{
		$MAIN.=$GRAPH_text;
		}
	else
		{
		$MAIN.=$ASCII_text;
		}


	## FORMAT OUTPUT ##
	$i=0;
	$hi_hour_count=0;
	$hi_hold_count=0;

	while ($i < $TOTintervals)
		{
		$qrtCALLSavg[$i] = MathZDC($qrtCALLSsec[$i], $qrtCALLS[$i]);

		if ($qrtCALLS[$i] > $hi_hour_count) 
			{$hi_hour_count = $qrtCALLS[$i];}

		$i++;
		}

	$hour_multiplier = MathZDC(70, $hi_hour_count);
	$hold_multiplier = MathZDC(70, $hi_hold_count);




	###################################################################
	#########  HOLD TIME, CALL AND DROP STATS 15-MINUTE INCREMENTS ####

	$MAIN.="\n";
	$ASCII_text="---------- "._QXZ("HOLD TIME, CALL AND DROP STATS")."\n";

	$CSV_text1.="\n\""._QXZ("HOLD TIME, CALL AND DROP STATS")."\"\n";

	$ASCII_text.="<FONT SIZE=0>";

	$ASCII_text.="<!-- HICOUNT CALLS: $hi_hour_count|$hour_multiplier -->";
	$ASCII_text.="<!-- HICOUNT HOLD:  $hi_hold_count|$hold_multiplier -->\n";
	$ASCII_text.=_QXZ("GRAPH IN 15 MINUTE INCREMENTS OF AVERAGE HOLD TIME FOR CALLS TAKEN INTO THIS IN-GROUP")."\n";

	$k=1;
	$Mk=0;
	$call_scale = '0';
	while ($k <= 72) 
		{
		$TMPscale_num=MathZDC(73, $hour_multiplier);
		$TMPscale_num = round($TMPscale_num, 0);
		$scale_num=MathZDC($k, $hour_multiplier);
		$scale_num = round($scale_num, 0);
		$tmpscl = "$call_scale$TMPscale_num";

		if ( ($Mk >= 4) or (strlen($tmpscl)==73) )
			{
			$Mk=0;
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
	$k=1;
	$Mk=0;
	$hold_scale = '0';
	while ($k <= 72) 
		{
		$TMPscale_num=MathZDC(73, $hold_multiplier);
		$TMPscale_num = round($TMPscale_num, 0);
		$scale_num=MathZDC($k, $hold_multiplier);
		$scale_num = round($scale_num, 0);
		$tmpscl = "$hold_scale$TMPscale_num";

		if ( ($Mk >= 4) or (strlen($tmpscl)==73) )
			{
			$Mk=0;
			$LENscale_num = (strlen($scale_num));
			$k = ($k + $LENscale_num);
			$hold_scale .= "$scale_num";
			}
		else
			{
			$hold_scale .= " ";
			$k++;   $Mk++;
			}
		}


	$ASCII_text.="+-------------+-------------------------------------------------------------------------+-------+\n";
	$ASCII_text.="| "._QXZ("TIME",11)." | "._QXZ("CALLS HANDLED",71)." |       |\n";
	$ASCII_text.="| "._QXZ("15 MIN INT",11)." |$call_scale| "._QXZ("TOTAL",5)." |\n";
	$ASCII_text.="+-------------+-------------------------------------------------------------------------+-------+\n";

	$CSV_text1.="\""._QXZ("TIME 15-MIN INT")."\",\""._QXZ("TOTAL")."\"\n";



	$max_calls=1;
	$graph_stats=array();
	$GRAPH_text="<table cellspacing='0' cellpadding='0'><caption align='top'>"._QXZ("HOLD TIME, CALL AND DROP STATS")."</caption><tr><th class='thgraph' scope='col'>"._QXZ("TIME 15-MIN INT")."</th><th class='thgraph' scope='col'>"._QXZ("DROPS")." <img src='./images/bar_blue.png' width='10' height='10'> / "._QXZ("CALLS")." <img src='./images/bar.png' width='10' height='10'></th></tr>";


	$i=0;
	while ($i < $TOTintervals)
		{
		$char_counter=0;
		### BEGIN HOLD TIME TOTALS GRAPH ###
			$Ghour_count = $qrtCALLS[$i];
		if ($Ghour_count > 0) {$no_lines_yet=0;}

		$Gavg_hold = $qrtQUEUEavg[$i];
		if ($Gavg_hold < 1) 
			{
			if ($i < 0)
				{
				$do_nothing=1;
				}
			else
				{
				$TOT_lines++;
				$qrtQUEUEavg[$i] =	sprintf("%5s", $qrtQUEUEavg[$i]);
				$qrtQUEUEmax[$i] =	sprintf("%5s", $qrtQUEUEmax[$i]);
				$ASCII_text.="|$HMdisplay[$i]|";
				$CSV_text1.="\"$HMdisplay[$i]\",";
			#	$k=0;   while ($k <= 22) {$ASCII_text.=" ";   $k++;}
			#	$ASCII_text.="| $qrtQUEUEavg[$i] | $qrtQUEUEmax[$i] |";
				}
			}
		else
			{
			$TOT_lines++;
			$no_lines_yet=0;
			$Xavg_hold = ($Gavg_hold * $hold_multiplier);
			$Yavg_hold = (19 - $Xavg_hold);

			$qrtQUEUEavg[$i] =	sprintf("%5s", $qrtQUEUEavg[$i]);
			$qrtQUEUEmax[$i] =	sprintf("%5s", $qrtQUEUEmax[$i]);

		#	$ASCII_text.="|$HMdisplay[$i]|<SPAN class=\"orange\">";
			$ASCII_text.="|$HMdisplay[$i]|";
			$CSV_text1.="\"$HMdisplay[$i]\",\"\"";
		#	$k=0;   while ($k <= $Xavg_hold) {$ASCII_text.="*";   $k++;   $char_counter++;}
		#	if ($char_counter >= 22) {$ASCII_text.="H</SPAN>";   $char_counter++;}
		#	else {$ASCII_text.="*H</SPAN>";   $char_counter++;   $char_counter++;}
		#	$k=0;   while ($k <= $Yavg_hold) {$ASCII_text.=" ";   $k++;   $char_counter++;}
		#		while ($char_counter <= 22) {$ASCII_text.=" ";   $char_counter++;}
		#	$ASCII_text.="| $qrtQUEUEavg[$i] | $qrtQUEUEmax[$i] |";
			}
		### END HOLD TIME TOTALS GRAPH ###

		$char_counter=0;
		### BEGIN CALLS TOTALS GRAPH ###
		$Ghour_count = $qrtCALLS[$i];
		if ($Ghour_count < 1) 
			{
			if ($i < 0)
				{
				$do_nothing=1;
				}
			else
				{
				if ($qrtCALLS[$i] < 1) {$qrtCALLS[$i]='';}
				$qrtCALLS[$i] =	sprintf("%5s", $qrtCALLS[$i]);
			#	$ASCII_text.="  |";
				$k=0;   while ($k <= 72) {$ASCII_text.=" ";   $k++;}
				$ASCII_text.="| $qrtCALLS[$i] |\n";
				$CSV_text1.="\"0\"\n";
				}
			}
		else
			{
			$no_lines_yet=0;
			$Xhour_count = ($Ghour_count * $hour_multiplier);
			$Yhour_count = (69 - $Xhour_count);

			$Gdrop_count = $qrtDROPS[$i];
			if ($Gdrop_count < 1) 
				{
				if ($qrtCALLS[$i] < 1) {$qrtCALLS[$i]='';}
				$qrtCALLS[$i] =	sprintf("%5s", $qrtCALLS[$i]);

				$ASCII_text.="<SPAN class=\"green\">";
				$k=0;   while ($k <= $Xhour_count) {$ASCII_text.="*";   $k++;   $char_counter++;}
				if ($char_counter > 71) {$ASCII_text.="C</SPAN>";   $char_counter++;}
				else {$ASCII_text.="*C</SPAN>";   $char_counter++;   $char_counter++;}
				$k=0;   while ($k <= $Yhour_count) {$ASCII_text.=" ";   $k++;   $char_counter++;}
					while ($char_counter <= 72) {$ASCII_text.=" ";   $char_counter++;}
				$ASCII_text.="| $qrtCALLS[$i] |\n";
				$CSV_text1.="\"$qrtCALLS[$i]\"\n";
				}
			else
				{
				$Xdrop_count = ($Gdrop_count * $hour_multiplier);

			#	if ($Xdrop_count >= $Xhour_count) {$Xdrop_count = ($Xdrop_count - 1);}

				$XXhour_count = ( ($Xhour_count - $Xdrop_count) - 1 );

				if ($qrtCALLS[$i] < 1) {$qrtCALLS[$i]='';}
				$qrtCALLS[$i] =	sprintf("%5s", $qrtCALLS[$i]);
				$qrtDROPS[$i] =	sprintf("%5s", $qrtDROPS[$i]);

				$ASCII_text.="<SPAN class=\"red\">";
				$k=0;   while ($k <= $Xdrop_count) {$ASCII_text.=">";   $k++;   $char_counter++;}
				$ASCII_text.="D</SPAN><SPAN class=\"green\">";   $char_counter++;
				$k=0;   while ($k <= $XXhour_count) {$ASCII_text.="*";   $k++;   $char_counter++;}
				$ASCII_text.="C</SPAN>";   $char_counter++;
				$k=0;   while ($k <= $Yhour_count) {$ASCII_text.=" ";   $k++;   $char_counter++;}
				while ($char_counter <= 72) {$ASCII_text.=" ";   $char_counter++;}

				$ASCII_text.="| $qrtCALLS[$i] |\n";
				$CSV_text1.="\"$qrtCALLS[$i]\"\n";
				}
			}
		### END CALLS TOTALS GRAPH ###
		$graph_stats[$i][0]=$HMdisplay[$i];
		$graph_stats[$i][1]=trim($qrtCALLS[$i]);
		$graph_stats[$i][2]=trim($qrtDROPS[$i]);
		if (trim($qrtCALLS[$i])>$max_calls) {$max_calls=$qrtCALLS[$i];}
		$i++;
		}


	$totQUEUEavgRAW = MathZDC($totCALLS, $totQUEUEsec);
	$totQUEUEavg =	sprintf("%5s", $totQUEUEavg); 
	while (strlen($totQUEUEavg)>5) {$totQUEUEavg = preg_replace('/.$/', '', $totQUEUEavg);}
	$totQUEUEmax =	sprintf("%5s", $totQUEUEmax);
	while (strlen($totQUEUEmax)>5) {$totQUEUEmax = preg_replace('/.$/', '', $totQUEUEmax);}
	$totDROPS =	sprintf("%5s", $totDROPS);
	$totCALLS =	sprintf("%5s", $totCALLS);


	$ASCII_text.="+-------------+-------------------------------------------------------------------------+-------+\n";
	$ASCII_text.="| "._QXZ("TOTAL",11)." |                                                                         | $totCALLS |\n";
	$ASCII_text.="+-------------+-------------------------------------------------------------------------+-------+\n";


	for ($d=0; $d<count($graph_stats); $d++) {
		$graph_stats[$d][0]=preg_replace('/\s/', "", $graph_stats[$d][0]); 
		$GRAPH_text.="  <tr><td class='chart_td' width='50'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value' width='600' valign='bottom'>\n";
		if ($graph_stats[$d][1]>0) {
			$GRAPH_text.="<ul class='overlap_barGraph'><li class=\"p1\" style=\"height: 12px; left: 0px; width: ".round(MathZDC(600*$graph_stats[$d][1], $max_calls))."px\">".$graph_stats[$d][1]."</li>";
			if ($graph_stats[$d][2]>0) {
				$GRAPH_text.="<li class=\"p2\" style=\"height: 12px; left: 0px; width: ".round(MathZDC(600*$graph_stats[$d][2], $max_calls))."px\">".$graph_stats[$d][2]."</li>";
			}
			$GRAPH_text.="</ul>\n";
		} else {
			$GRAPH_text.="0";
		}
		$GRAPH_text.="</td></tr>\n";
	}
	$GRAPH_text.="<tr><th class='thgraph' scope='col'>"._QXZ("TOTALS").":</th><th class='thgraph' scope='col'>".trim($totCALLS)."</th></tr></table>";

	$JS_text.="</script>";

	if ($report_display_type=="HTML")
		{
		$MAIN.=$GRAPH_text.$JS_text;
		}
	else
		{
		$MAIN.=$ASCII_text;
		}


	$CSV_text1.="\""._QXZ("TOTAL")."\",\"$totCALLS\"\n";

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
