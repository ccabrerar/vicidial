<?php 
# AST_inbound_daily_report.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 111119-1234 - First build
# 120118-2116 - Changed headers on CSV download
# 120224-0910 - Added HTML display option with bar graphs
# 120601-2235 - Added group name to header, added status breakdown counts to page and CSV with option to display them
# 120611-2200 - Added ability to filter output by call time
# 120819-0118 - Formatting changes
# 121115-0621 - Changed to multi-select for in-group selection
# 130322-2008 - Added Unique Agents column
# 130414-0110 - Added report logging
# 130610-1008 - Finalized changing of all ereg instances to preg
# 130621-0747 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0730 - Changed to mysqli PHP functions
# 131217-1348 - Fixed several empty variable and array issues
# 140108-0706 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141113-2055 - Finalized adding QXZ translation to all admin files
# 141230-0921 - Added code for on-the-fly language translations display
# 150218-1142 - Fix for download issue
# 150516-1313 - Fixed Javascript element problem, Issue #857
# 151125-1629 - Added search archive option
# 160227-1142 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 160819-0054 - Fixed chart bugs caused by DST
# 170227-1710 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 191013-0902 - Fixes for PHP7
# 200701-1500 - Added option to exclude after-hours from abandons, list ID filtering
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

if (file_exists('options.php'))
	{
	require('options.php');
	}

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["list_ids"]))				{$list_ids=$_GET["list_ids"];}
	elseif (isset($_POST["list_ids"]))		{$list_ids=$_POST["list_ids"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["hourly_breakdown"]))			{$hourly_breakdown=$_GET["hourly_breakdown"];}
	elseif (isset($_POST["hourly_breakdown"]))	{$hourly_breakdown=$_POST["hourly_breakdown"];}
if (isset($_GET["show_disposition_statuses"]))			{$show_disposition_statuses=$_GET["show_disposition_statuses"];}
	elseif (isset($_POST["show_disposition_statuses"]))	{$show_disposition_statuses=$_POST["show_disposition_statuses"];}
if (isset($_GET["ignore_afterhours"]))			{$ignore_afterhours=$_GET["ignore_afterhours"];}
	elseif (isset($_POST["ignore_afterhours"]))	{$ignore_afterhours=$_POST["ignore_afterhours"];}
if (isset($_GET["exclude_afterhours"]))			{$exclude_afterhours=$_GET["exclude_afterhours"];}
	elseif (isset($_POST["exclude_afterhours"]))	{$exclude_afterhours=$_POST["exclude_afterhours"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Inbound Daily Report';
$db_source = 'M';

if ($ignore_afterhours=="checked") {$status_clause=" and status!='AFTHRS'";}

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
$table_name="vicidial_closer_log";
$archive_table_name=use_archive_table($table_name);
if ($archive_table_name!=$table_name) {$archives_available="Y";}

if ($search_archived_data) 
	{
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	}
else
	{
	$vicidial_closer_log_table="vicidial_closer_log";
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

$ag[0]='';
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}

$call_times=array();
$call_time_names=array();
if ($IDR_calltime_available==1)
	{
	$LOGadmin_viewable_call_timesSQL='';
	$whereLOGadmin_viewable_call_timesSQL='';
	if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
		{
		$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
		$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
		$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
		$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
		}

	$stmt="select call_time_id,call_time_name from vicidial_call_times $whereLOGadmin_viewable_call_timesSQL order by call_time_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$times_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $times_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$call_times[$i] =		$row[0];
		$call_time_names[$i] =	$row[1];
		$i++;
		}
	}
else 
	{
	$shift="24hours";
	}

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($list_ids)) {$list_ids = array(); $all_lists_selected="selected";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

###### INGROUPS ######
$groups_selected = count($group);
$group_name_str="";
$groups_selected_str="";
$groups_selected_URLstr="";
for ($i=0; $i<$groups_selected; $i++) 
	{
	$selected_group_URLstr.="&group[]=$group[$i]";
	if ($group[$i]=="--ALL--") 
		{
		$group=array("--ALL--");
		$groups_selected=1;
		$group_name_str.="-- ALL INGROUPS --";
		$all_selected="selected";
		}
	else 
		{
		$groups_selected_str.="'$group[$i]', ";
		}
	}

$stmt="select group_id,group_name from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
$group_names=array();
$groups_string='|';
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =		$row[0];
	$group_names[$i] =	$row[1];
	$groups_string .= "$groups[$i]|";
	for ($j=0; $j<$groups_selected; $j++) {
		if ($group[$j] && $groups[$i]==$group[$j]) {$group_name_str.="$groups[$i] - $group_names[$i], ";}
		if ($group[$j]=="--ALL--") {$groups_selected_str.="'$groups[$i]', ";}
	}
	$i++;
	}

$groups_selected_str=preg_replace('/, $/', '', $groups_selected_str);
$group_name_str=preg_replace('/, $/', '', $group_name_str);
######################

###### LISTS #########
$lists_selected = count($list_ids);
$list_name_str="";
$lists_selected_str="";
$lists_selected_URLstr="";

for ($i=0; $i<$lists_selected; $i++) 
	{
	$selected_group_URLstr.="&list_ids[]=$list_ids[$i]";
	if ($list_ids[$i]=="--ALL--") 
		{
		$list_ids=array("--ALL--");
		$lists_selected=1;
		$list_name_str.="-- ALL LISTS --";
		$all_lists_selected="selected";
		}
	else 
		{
		$lists_selected_str.="'$list_ids[$i]', ";
		}
	}

$stmt="select list_id,list_name from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$lists_to_print = mysqli_num_rows($rslt);
$i=0;
$lists=array();
$list_names=array();
$lists_string='|';
while ($i < $lists_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$lists[$i] =		$row[0];
	$list_names[$i] =	$row[1];
	$lists_string .= "$lists[$i]|";
	for ($j=0; $j<$lists_selected; $j++) 
		{
		if ($list_ids[$j] && $lists[$i]==$list_ids[$j]) {$list_name_str.="$lists[$i] - $list_names[$i], ";}
		if ($list_ids[$j]=="--ALL--") {$lists_selected_str.="'$lists[$i]', ";}
		}
	$i++;
	}

$lists_selected_str=preg_replace('/, $/', '', $lists_selected_str);
$list_name_str=preg_replace('/, $/', '', $list_name_str);
######################

$stmt="select call_time_id,call_time_name from vicidial_call_times $whereLOGadmin_viewable_call_timesSQL order by call_time_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$times_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $times_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$call_times[$i] =		$row[0];
	$call_time_names[$i] =	$row[1];
	$i++;
	}

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
#if (!preg_match("/\|$group\|/i",$groups_string))
#	{
#	$HEADER.="<!-- group not found: $group  $groups_string -->\n";
#	$group='';
#	}

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

require("screen_colors.php");

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#inbound_daily_report$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR valign='top'><TD>";

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
$MAIN.="</TD><TD rowspan=3>\n";

$MAIN.="<SELECT SIZE=5 NAME=list_ids[] multiple>\n";
$MAIN.="<option $all_lists_selected value=\"--ALL--\">--"._QXZ("ALL LISTS")."--</option>\n";
$o=0;
while ($lists_to_print > $o)
	{
	$selected="";
	for ($i=0; $i<$lists_selected; $i++) 
		{
		if ( ($file_download < 1) and ($DB) ) {echo "<!-- $lists[$o] == $list_ids[$i] //-->\n";}
		if ($lists[$o] == $list_ids[$i]) {$selected="selected";}
		}
	$MAIN.="<option $selected value=\"$lists[$o]\">$lists[$o] - $list_names[$o]</option>\n";
	$o++;
	}
$MAIN.="</SELECT>\n";

$MAIN.="</TD><TD rowspan=2>\n";
$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
$MAIN.="<option $all_selected value=\"--ALL--\">--"._QXZ("ALL INGROUPS")."--</option>\n";

$o=0;
while ($groups_to_print > $o)
	{
	$selected="";
	for ($i=0; $i<$groups_selected; $i++) 
		{
		if ( ($file_download < 1) and ($DB) ) {echo "<!-- $groups[$o] == $group[$i] //-->\n";}
		if ($groups[$o] == $group[$i]) {$selected="selected";}
		}
	$MAIN.="<option $selected value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.=" &nbsp;";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>&nbsp; ";


if ($IDR_calltime_available==1)
	{
	$MAIN.="<SELECT SIZE=1 NAME=shift>\n";
	$MAIN.="<option value=\"\">--</option>\n";
	$o=0;
	while ($times_to_print > $o)
		{
		if ($call_times[$o] == $shift) {$MAIN.="<option selected value=\"$call_times[$o]\">$call_times[$o] - $call_time_names[$o]</option>\n";}
		else {$MAIN.="<option value=\"$call_times[$o]\">$call_times[$o] - $call_time_names[$o]</option>\n";}
		$o++;
		}
	$MAIN.="</SELECT>\n";
	}

$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'></TD></TR>\n";
$MAIN.="<TR><TD align='left' rowspan=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><INPUT TYPE=checkbox NAME=hourly_breakdown VALUE='checked' $hourly_breakdown>"._QXZ("Show hourly results")."<BR><INPUT TYPE=checkbox NAME=show_disposition_statuses VALUE='checked' $show_disposition_statuses>"._QXZ("Show disposition statuses")."<BR><INPUT TYPE=checkbox NAME=ignore_afterhours VALUE='checked' $ignore_afterhours>"._QXZ("Ignore after-hours calls")."<BR><INPUT TYPE=checkbox NAME=exclude_afterhours VALUE='checked' $exclude_afterhours>"._QXZ("Exclude after-hours from abandons");
if ($archives_available=="Y") 
	{
	$MAIN.="<BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}
$MAIN.="</FONT></TD></TR><TR><TD align='right'><a href=\"$PHP_SELF?DB=$DB&query_date=$query_date&end_date=$end_date$selected_group_URLstr&shift=$shift&hourly_breakdown=$hourly_breakdown&show_disposition_statuses=$show_disposition_statuses&SUBMIT=$SUBMIT&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a> | <a href=\"./admin.php?ADD=3111&group_id=$group[0]\">"._QXZ("MODIFY")."</a> | <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a></TD></TR>\n";
$MAIN.="</TD></TR></TABLE>\n";
$MAIN.="<TR><TD colspan=2>";
$MAIN.="<PRE><FONT SIZE=2>\n\n";


if ($groups_selected==0)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT AN IN-GROUP AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	echo "$HEADER";
	require("admin_header.php");
	echo "$MAIN";
	}

else
	{
	### FOR SHIFTS IT IS BEST TO STICK TO 15-MINUTE INCREMENTS FOR START TIMES ###
	$start_time_ary=array();
	$stop_time_ary=array();
	if ($shift && $shift!="ALL") {
		# call_time_id | call_time_name              | call_time_comments | ct_default_start | ct_default_stop | ct_sunday_start | ct_sunday_stop | ct_monday_start | ct_monday_stop | ct_tuesday_start | ct_tuesday_stop | ct_wednesday_start | ct_wednesday_stop | ct_thursday_start | ct_thursday_stop | ct_friday_start | ct_friday_stop | ct_saturday_start | ct_saturday_stop
		$big_shift_time_SQL_clause ="";
		$shift_stmt="select * from vicidial_call_times where call_time_id='$shift'";
		$shift_rslt=mysql_to_mysqli($shift_stmt, $link);
		$shift_row=mysqli_fetch_array($shift_rslt);
		$default_start_time=substr("0000".$shift_row["ct_default_start"], -4);
		$default_stop_time=substr("0000".$shift_row["ct_default_stop"], -4);
		$ct_default_start=substr($default_start_time,0,2).":".substr($default_start_time,2,2).":00";
		$ct_default_stop=substr($default_stop_time,0,2).":".substr($default_stop_time,2,2).":00";

		if ($shift_row["ct_sunday_start"]!=$shift_row["ct_sunday_stop"]) {
			$sunday_start_time=substr("0000".$shift_row["ct_sunday_start"], -4);
			$sunday_stop_time=substr("0000".$shift_row["ct_sunday_stop"], -4);
			$ct_sunday_start=substr($sunday_start_time,0,2).":".substr($sunday_start_time,2,2).":00";
			$ct_sunday_stop=substr($sunday_stop_time,0,2).":".substr($sunday_stop_time,2,2).":00";
		} else if ($shift_row["ct_sunday_start"]!="2400") {
			$ct_sunday_start=$ct_default_start;
			$ct_sunday_stop=$ct_default_stop;
		} else {
			$ct_sunday_start="00:00:00";
			$ct_sunday_stop=1;
		}
		$stop_time_stmt="select TIMEDIFF('$ct_sunday_stop', 1)";  # subtract one second - don't allow the actual final time - this can cause an extra row to print
		$stop_time_rslt=mysql_to_mysqli($stop_time_stmt, $link);
		$strow=mysqli_fetch_row($stop_time_rslt);
		$ct_sunday_stop=$strow[0];
		$start_time_ary[0]=$ct_sunday_start;
		$stop_time_ary[0]=$ct_sunday_stop;
		$big_shift_time_SQL_clause.="(date_format(call_date, '%w')=0 and date_format(call_date, '%H:%i:%s')>='$ct_sunday_start' and date_format(call_date, '%H:%i:%s')<='$ct_sunday_stop') or ";

		if ($shift_row["ct_monday_start"]!=$shift_row["ct_monday_stop"]) {
			$monday_start_time=substr("0000".$shift_row["ct_monday_start"], -4);
			$monday_stop_time=substr("0000".$shift_row["ct_monday_stop"], -4);
			$ct_monday_start=substr($monday_start_time,0,2).":".substr($monday_start_time,2,2).":00";
			$ct_monday_stop=substr($monday_stop_time,0,2).":".substr($monday_stop_time,2,2).":00";
		} else if ($shift_row["ct_monday_start"]!="2400") {
			$ct_monday_start=$ct_default_start;
			$ct_monday_stop=$ct_default_stop;
		} else {
			$ct_monday_start="00:00:00";
			$ct_monday_stop=1;
		}
		$stop_time_stmt="select TIMEDIFF('$ct_monday_stop', 1)";  # subtract one second - don't allow the actual final time - this can cause an extra row to print
		$stop_time_rslt=mysql_to_mysqli($stop_time_stmt, $link);
		$strow=mysqli_fetch_row($stop_time_rslt);
		$ct_monday_stop=$strow[0];
		$start_time_ary[1]=$ct_monday_start;
		$stop_time_ary[1]=$ct_monday_stop;
		$big_shift_time_SQL_clause.="(date_format(call_date, '%w')=1 and date_format(call_date, '%H:%i:%s')>='$ct_monday_start' and date_format(call_date, '%H:%i:%s')<='$ct_monday_stop') or ";

		if ($shift_row["ct_tuesday_start"]!=$shift_row["ct_tuesday_stop"]) {
			$tuesday_start_time=substr("0000".$shift_row["ct_tuesday_start"], -4);
			$tuesday_stop_time=substr("0000".$shift_row["ct_tuesday_stop"], -4);
			$ct_tuesday_start=substr($tuesday_start_time,0,2).":".substr($tuesday_start_time,2,2).":00";
			$ct_tuesday_stop=substr($tuesday_stop_time,0,2).":".substr($tuesday_stop_time,2,2).":00";
		} else if ($shift_row["ct_tuesday_start"]!="2400") {
			$ct_tuesday_start=$ct_default_start;
			$ct_tuesday_stop=$ct_default_stop;
		} else {
			$ct_tuesday_start="00:00:00";
			$ct_tuesday_stop=1;
		}
		$stop_time_stmt="select TIMEDIFF('$ct_tuesday_stop', 1)";  # subtract one second - don't allow the actual final time - this can cause an extra row to print
		$stop_time_rslt=mysql_to_mysqli($stop_time_stmt, $link);
		$strow=mysqli_fetch_row($stop_time_rslt);
		$ct_tuesday_stop=$strow[0];
		$start_time_ary[2]=$ct_tuesday_start;
		$stop_time_ary[2]=$ct_tuesday_stop;
		$big_shift_time_SQL_clause.="(date_format(call_date, '%w')=2 and date_format(call_date, '%H:%i:%s')>='$ct_tuesday_start' and date_format(call_date, '%H:%i:%s')<='$ct_tuesday_stop') or ";

		if ($shift_row["ct_wednesday_start"]!=$shift_row["ct_wednesday_stop"]) {
			$wednesday_start_time=substr("0000".$shift_row["ct_wednesday_start"], -4);
			$wednesday_stop_time=substr("0000".$shift_row["ct_wednesday_stop"], -4);
			$ct_wednesday_start=substr($wednesday_start_time,0,2).":".substr($wednesday_start_time,2,2).":00";
			$ct_wednesday_stop=substr($wednesday_stop_time,0,2).":".substr($wednesday_stop_time,2,2).":00";
		} else if ($shift_row["ct_wednesday_start"]!="2400") {
			$ct_wednesday_start=$ct_default_start;
			$ct_wednesday_stop=$ct_default_stop;
		} else {
			$ct_wednesday_start="00:00:00";
			$ct_wednesday_stop=1;
		}
		$stop_time_stmt="select TIMEDIFF('$ct_wednesday_stop', 1)";  # subtract one second - don't allow the actual final time - this can cause an extra row to print
		$stop_time_rslt=mysql_to_mysqli($stop_time_stmt, $link);
		$strow=mysqli_fetch_row($stop_time_rslt);
		$ct_wednesday_stop=$strow[0];
		$start_time_ary[3]=$ct_wednesday_start;
		$stop_time_ary[3]=$ct_wednesday_stop;
		$big_shift_time_SQL_clause.="(date_format(call_date, '%w')=3 and date_format(call_date, '%H:%i:%s')>='$ct_wednesday_start' and date_format(call_date, '%H:%i:%s')<='$ct_wednesday_stop') or ";

		if ($shift_row["ct_thursday_start"]!=$shift_row["ct_thursday_stop"]) {
			$thursday_start_time=substr("0000".$shift_row["ct_thursday_start"], -4);
			$thursday_stop_time=substr("0000".$shift_row["ct_thursday_stop"], -4);
			$ct_thursday_start=substr($thursday_start_time,0,2).":".substr($thursday_start_time,2,2).":00";
			$ct_thursday_stop=substr($thursday_stop_time,0,2).":".substr($thursday_stop_time,2,2).":00";
		} else if ($shift_row["ct_thursday_start"]!="2400") {
			$ct_thursday_start=$ct_default_start;
			$ct_thursday_stop=$ct_default_stop;
		} else {
			$ct_thursday_start="00:00:00";
			$ct_thursday_stop=1;
		}
		$stop_time_stmt="select TIMEDIFF('$ct_thursday_stop', 1)";  # subtract one second - don't allow the actual final time - this can cause an extra row to print
		$stop_time_rslt=mysql_to_mysqli($stop_time_stmt, $link);
		$strow=mysqli_fetch_row($stop_time_rslt);
		$ct_thursday_stop=$strow[0];
		$start_time_ary[4]=$ct_thursday_start;
		$stop_time_ary[4]=$ct_thursday_stop;
		$big_shift_time_SQL_clause.="(date_format(call_date, '%w')=4 and date_format(call_date, '%H:%i:%s')>='$ct_thursday_start' and date_format(call_date, '%H:%i:%s')<='$ct_thursday_stop') or ";

		if ($shift_row["ct_friday_start"]!=$shift_row["ct_friday_stop"]) {
			$friday_start_time=substr("0000".$shift_row["ct_friday_start"], -4);
			$friday_stop_time=substr("0000".$shift_row["ct_friday_stop"], -4);
			$ct_friday_start=substr($friday_start_time,0,2).":".substr($friday_start_time,2,2).":00";
			$ct_friday_stop=substr($friday_stop_time,0,2).":".substr($friday_stop_time,2,2).":00";
		} else if ($shift_row["ct_friday_start"]!="2400") {
			$ct_friday_start=$ct_default_start;
			$ct_friday_stop=$ct_default_stop;
		} else {
			$ct_friday_start="00:00:00";
			$ct_friday_stop=1;
		}
		$stop_time_stmt="select TIMEDIFF('$ct_friday_stop', 1)";  # subtract one second - don't allow the actual final time - this can cause an extra row to print
		$stop_time_rslt=mysql_to_mysqli($stop_time_stmt, $link);
		$strow=mysqli_fetch_row($stop_time_rslt);
		$ct_friday_stop=$strow[0];
		$start_time_ary[5]=$ct_friday_start;
		$stop_time_ary[5]=$ct_friday_stop;
		$big_shift_time_SQL_clause.="(date_format(call_date, '%w')=5 and date_format(call_date, '%H:%i:%s')>='$ct_friday_start' and date_format(call_date, '%H:%i:%s')<='$ct_friday_stop') or ";

		if ($shift_row["ct_saturday_start"]!=$shift_row["ct_saturday_stop"]) {
			$saturday_start_time=substr("0000".$shift_row["ct_saturday_start"], -4);
			$saturday_stop_time=substr("0000".$shift_row["ct_saturday_stop"], -4);
			$ct_saturday_start=substr($saturday_start_time,0,2).":".substr($saturday_start_time,2,2).":00";
			$ct_saturday_stop=substr($saturday_stop_time,0,2).":".substr($saturday_stop_time,2,2).":00";
		} else if ($shift_row["ct_saturday_start"]!="2400") {
			$ct_saturday_start=$ct_default_start;
			$ct_saturday_stop=$ct_default_stop;
		} else {
			$ct_saturday_start="00:00:00";
			$ct_saturday_stop=1;
		}
		$stop_time_stmt="select TIMEDIFF('$ct_saturday_stop', 1)";  # subtract one second - don't allow the actual final time - this can cause an extra row to print
		$stop_time_rslt=mysql_to_mysqli($stop_time_stmt, $link);
		$strow=mysqli_fetch_row($stop_time_rslt);
		$ct_saturday_stop=$strow[0];
		$start_time_ary[6]=$ct_saturday_start;
		$stop_time_ary[6]=$ct_saturday_stop;
		$big_shift_time_SQL_clause.="(date_format(call_date, '%w')=6 and date_format(call_date, '%H:%i:%s')>='$ct_saturday_start' and date_format(call_date, '%H:%i:%s')<='$ct_saturday_stop') or ";

		$query_time_stmt="select date_format('$query_date', '%w'), date_format('$end_date', '%w')";
		$query_time_rslt=mysql_to_mysqli($query_time_stmt, $link);
		$qrow=mysqli_fetch_row($query_time_rslt);
		$time_BEGIN=$start_time_ary[$qrow[0]];
		$time_END=$stop_time_ary[$qrow[0]];
		#$time_BEGIN="00:00:00"; # Need this so the $SQepoch value can be tweaked per day (i.e. only adding the hours for the start time);

		$query_date_BEGIN = "$query_date ".$time_BEGIN;   
		$query_date_END = "$end_date ".$time_END;
		RecalculateHPD($query_date_BEGIN, $query_date_END, $time_BEGIN, $time_END); # Only calling it here to get the DURATIONday, EQepoch, and intial SQepoch
		# Will be called repeatedly for each day

		$big_shift_time_SQL_clause=preg_replace('/\sor\s$/', '', $big_shift_time_SQL_clause);
		$big_shift_time_SQL_clause=" and ($big_shift_time_SQL_clause)";
		#echo $big_shift_time_SQL_clause;
		#print_r($start_time_ary); echo "<BR>";
		#print_r($stop_time_ary); echo "<BR>";
	} else {

		if ($shift == 'ALL') 
			{
			if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
			if (strlen($time_END) < 6) {$time_END = "23:59:59";}
			}

		$query_date_BEGIN = "$query_date $time_BEGIN";   
		$query_date_END = "$end_date $time_END";

		RecalculateHPD($query_date_BEGIN, $query_date_END, $time_BEGIN, $time_END);
	}

	$MAIN.=_QXZ("Inbound Daily Report",40)." $NOW_TIME\n";
	$MAIN.=_QXZ("Selected in-groups").": $group_name_str\n";
	if ($shift && $IDR_calltime_available) {$MAIN.="Selected shift: $shift\n";}
	$MAIN.=_QXZ("Time range")." $DURATIONday "._QXZ("days").": $query_date_BEGIN "._QXZ("to")." $query_date_END";
	if ($shift && $IDR_calltime_available) {$MAIN.=_QXZ("for")." $shift "._QXZ("shift");}
	$MAIN.="\n\n";
	#echo "Time range day sec: $SQsec - $EQsec   Day range in epoch: $SQepoch - $EQepoch   Start: $SQepochDAY\n";
	$CSV_text.="\""._QXZ("Inbound Daily Report")."\",\"$NOW_TIME\"\n";
	$CSV_text.=_QXZ("Selected in-groups").": $group_name_str\n";
	if ($shift && $IDR_calltime_available) {$CSV_text.=_QXZ("Selected shift").": $shift\n";}
	$CSV_text.="\""._QXZ("Time range")." $DURATIONday "._QXZ("days").":\",\"$query_date_BEGIN "._QXZ("to")." $query_date_END\"";
	if ($shift && $IDR_calltime_available) {$CSV_text.=_QXZ("for")." $shift "._QXZ("shift");}
	$CSV_text.="\n\n";

	$status_array=array();
	if ($show_disposition_statuses) {
		$dispo_stmt="select distinct status from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id in (" . $groups_selected_str . ")  and list_id in (" . $lists_selected_str . ") $status_clause $big_shift_time_SQL_clause order by status;";
		#echo $dispo_stmt."<BR>";
		$dispo_rslt=mysql_to_mysqli($dispo_stmt, $link);
		$dispo_str="";
		$s=0;
		while($dispo_row=mysqli_fetch_row($dispo_rslt)) {
			$status_array[$s][0]="$dispo_row[0]";
			$status_array[$s][1]="";
			$dispo_str.="'$dispo_row[0]',";
			$stat_stmt="select distinct status, status_name from vicidial_statuses where status='$dispo_row[0]' UNION select distinct status, status_name from vicidial_campaign_statuses where status='$dispo_row[0]' order by status;";
			#echo $stat_stmt."<BR>";
			$stat_rslt=mysql_to_mysqli($stat_stmt, $link);
			while ($stat_row=mysqli_fetch_array($stat_rslt)) {
				$status_array[$s][1]=$stat_row["status_name"];
			}
			$s++;
		}
		$dispo_str=substr($dispo_str,0,-1);
		if ($status_array) {asort($status_array);}
		#print_r($status_array);
#		if (strlen($dispo_str)>0) {
#		}
	}

	$d=0; $q=0; $hr=0; $shift_hrs=0;
	$daySTART=array();
	$dayEND=array();
	while ($d < $DURATIONday)
		{
		$dSQepoch = ($SQepoch + ($d * 86400) + ($hr * 3600));
		
		if ($shift && $shift!="ALL" && $RECALC==1) 
			{
			# Need to get current day, hours for that day (hpd)
			$current_dayofweek=date("w", $dSQepoch);
			$time_BEGIN=$start_time_ary[$current_dayofweek];
			$time_END=$stop_time_ary[$current_dayofweek];
			HourDifference($time_BEGIN, $time_END); # new hpd
			$query_date_BEGIN = "$query_date $time_BEGIN";   
			$query_date_END = "$end_date $time_END";
			RecalculateEpochs($query_date_BEGIN, $query_date_END);
			$dSQepoch = ($SQepoch + ($d * 86400) + ($hr * 3600) + ($shift_hrs * 3600) );
			#echo " --- NEW DAY: $query_date_BEGIN / $query_date_END --- <BR>";
			$RECALC=0;
			}


		if ($hourly_breakdown) 
			{
			$dEQepoch = $dSQepoch+3599;
			}
			else
			{
			$dEQepoch = ($SQepochDAY + ($EQsec + ($d * 86400) + ($hr * 3600)) );
			if ($EQsec < $SQsec)
				{
				$dEQepoch = ($dEQepoch + 86400);
				}
			}

		#echo "$dSQepoch - $dEQepoch, ".date("Y-m-d H:i:s", $dSQepoch)." - ".date("Y-m-d H:i:s", $dEQepoch).", ".date("D", $dSQepoch)." - $hpd hours - shift starts at $shift_hrs:00:00<BR>";
		$daySTART[$q] = date("Y-m-d H:i:s", $dSQepoch);
		$dayEND[$q] = date("Y-m-d H:i:s", $dEQepoch);

		if ($hr>=($hpd-1) || !$hourly_breakdown) 
			{
			$d++;
			$hr=0;
			if (date("H:i:s", $dEQepoch)>$time_END) 
				{
				$dayEND[$q] = date("Y-m-d ", $dEQepoch).$time_END;
				}
			$RECALC=1;
			}
			else
			{
			$hr++;
			}
		#$MAIN.="$daySTART[$q] - $dayEND[$q] | $SQepochDAY,".date("Y-m-d H:i:s",$SQepochDAY)."\n";
		$q++;

		}
	$prev_week=$daySTART[0];
	$prev_month=$daySTART[0];
	$prev_qtr=$daySTART[0];
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

	$TOTintervals = $q;


	### GRAB ALL RECORDS WITHIN RANGE FROM THE DATABASE ###
	$stmt="select queue_seconds,UNIX_TIMESTAMP(call_date),length_in_sec,status,term_reason,call_date,user from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id in (" . $groups_selected_str . ") and list_id in (" . $lists_selected_str . ") $status_clause;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$records_to_grab = mysqli_num_rows($rslt);
	$i=0;
	$fTOTAL_agents=array();
	$qs=array();
	$dt=array();
	$ut=array();
	$ls=array();
	$st=array();
	$tr=array();
	$at=array();
	$ag=array();
	if($hourly_breakdown) {$epoch_interval=3600;} else {$epoch_interval=86400;}
	while ($i < $records_to_grab)
		{
		$row=mysqli_fetch_row($rslt);
		$qs[$i] = $row[0];
		$dt[$i] = 0;
		$ut[$i] = ($row[1] - $SQepochDAY);
		while($ut[$i] >= $epoch_interval) 
			{
			$ut[$i] = ($ut[$i] - $epoch_interval);
			$dt[$i]++;
			}
		if ( ($ut[$i] <= $EQsec) and ($EQsec < $SQsec) )
			{
			$dt[$i] = ($dt[$i] - 1);
			}
		$ls[$i] = $row[2];
		$st[$i] = $row[3];
		$tr[$i] = $row[4];
		$at[$i] = $row[5]; # Actual time
		if ($row[6]!="VDCL" && $row[6]!="") {$ag[$i] = $row[6];} # User
		# $fTOTAL_agents["$row[6]"]++;
		# $ASCII_text.= "$qs[$i] | $dt[$i] - $row[1] | $ut[$i] | $ls[$i] | $st[$i] | $tr[$i] | $at[$i]\n";

		$i++;
		}

	### PARSE THROUGH ALL RECORDS AND GENERATE STATS ###
	$MT[0]='0';
	$totCALLS=0;
	$totDROPS=0;
	$totQUEUE=0;
	$totCALLSsec=0;
	$totDROPSsec=0;
	$totQUEUEsec=0;
	$totCALLSmax=0;
	$totDROPSmax=0;
	$totQUEUEmax=0;
	$totCALLSdate=$MT;
	$totDROPSdate=$MT;
	$totQUEUEdate=$MT;
	$qrtCALLS=$MT;
	$qrtDROPS=$MT;
	$qrtQUEUE=$MT;
	$qrtCALLSsec=$MT;
	$qrtDROPSsec=$MT;
	$qrtQUEUEsec=$MT;
	$qrtCALLSavg=$MT;
	$qrtDROPSavg=$MT;
	$qrtQUEUEavg=$MT;
	$qrtCALLSmax=$MT;
	$qrtDROPSmax=$MT;
	$qrtQUEUEmax=$MT;

	$totABANDONSdate=$MT;
	$totAGENTSdate=array();
	$totANSWERSdate=$MT;

	$totANSWERS=0;
	$totAGENTS=0;
	$totABANDONS=0;
	$totANSWERSsec=0;
	$totABANDONSsec=0;
	$totANSWERSspeed=0;
	$totSTATUSES=array();

	$totABANDONSsecdate=array();
	$totANSWERSsecdate=array();
	$totCALLSsecDATE=array();
	$totDROPSsecDATE=array();
	$totQUEUEsecDATE=array();
	$totSTATUSESdate=array();
	$totANSWERSspeeddate=array();

	$FtotANSWERS=0;
	$FtotAGENTS=count(array_count_values($ag));
	$FtotABANDONS=0;
	$FtotANSWERSsec=0;
	$FtotABANDONSsec=0;
	$FtotANSWERSspeed=0;

	$j=0;
	while ($j < $TOTintervals)
		{
	#	$jd__0[$j]=0; $jd_20[$j]=0; $jd_40[$j]=0; $jd_60[$j]=0; $jd_80[$j]=0; $jd100[$j]=0; $jd120[$j]=0; $jd121[$j]=0;
	#	$Phd__0[$j]=0; $Phd_20[$j]=0; $Phd_40[$j]=0; $Phd_60[$j]=0; $Phd_80[$j]=0; $Phd100[$j]=0; $Phd120[$j]=0; $Phd121[$j]=0;
	#	$qrtCALLS[$j]=0; $qrtCALLSsec[$j]=0; $qrtCALLSmax[$j]=0;
	#	$qrtDROPS[$j]=0; $qrtDROPSsec[$j]=0; $qrtDROPSmax[$j]=0;
	#	$qrtQUEUE[$j]=0; $qrtQUEUEsec[$j]=0; $qrtQUEUEmax[$j]=0;
		$totABANDONSdate[$j]=0;
		$totABANDONSsecdate[$j]=0;
		$totANSWERSdate[$j]=0;
		$totANSWERSsecdate[$j]=0;
		$totANSWERSspeeddate[$j]=0;
		$i=0;
		$agents_array=array();
		while ($i < $records_to_grab)
			{
			if ( ($at[$i] >= $daySTART[$j]) and ($at[$i] <= $dayEND[$j]) )
				{
				$totCALLS++;
				$totCALLSsec = ($totCALLSsec + $ls[$i]);
				$totCALLSsecDATE[$j] = ($totCALLSsecDATE[$j] + $ls[$i]);
	#			$qrtCALLS[$j]++;
	#			$qrtCALLSsec[$j] = ($qrtCALLSsec[$j] + $ls[$i]);
	#			$dtt = $dt[$i];
				$totCALLSdate[$j]++;
				# $totAGENTSdate[$j]=$ag[$i];
				if ($ag[$i]!="VDCL" && $ag[$i]!="") {$totAGENTSdate[$j][$ag[$i]]++;}

				$totSTATUSES[$st[$i]]++;
				$totSTATUSESdate[$j][$st[$i]]++;

				if ($totCALLSmax < $ls[$i]) {$totCALLSmax = $ls[$i];}
				if ($qrtCALLSmax[$j] < $ls[$i]) {$qrtCALLSmax[$j] = $ls[$i];}
				$abandons_match_str='ABANDON|NOAGENT|QUEUETIMEOUT|AFTERHOURS|MAXCALLS';
				if (preg_match("/$abandons_match_str/", $tr[$i])) 
					{
					if (!$exclude_afterhours || !preg_match('/AFTERHOURS/', $tr[$i]))
						{
						$totABANDONSdate[$j]++;
						$totABANDONSsecdate[$j]+=$ls[$i];
						$FtotABANDONS++;
						$FtotABANDONSsec+=$ls[$i];
						}
					}
				else 
					{
					$totANSWERSdate[$j]++;
					if (($ls[$i]-$qs[$i]-15)>0) 
						{  ## Patch by Joe J - can cause negative time values if removed.
						$totANSWERSsecdate[$j]+=($ls[$i]-$qs[$i]-15);
						$FtotANSWERSsec+=($ls[$i]-$qs[$i]-15);
						}
					$totANSWERSspeeddate[$j]+=$qs[$i];
					$FtotANSWERS++;
					if ($DB) {print "<!-- $FtotANSWERSspeed+=$qs[$i] //-->\n";}
					$FtotANSWERSspeed+=$qs[$i];
					}
				if (preg_match('/DROP/',$st[$i])) 
					{
					$totDROPS++;
					$totDROPSsec = ($totDROPSsec + $ls[$i]);
					$totDROPSsecDATE[$j] = ($totDROPSsecDATE[$j] + $ls[$i]);
	#				$qrtDROPS[$j]++;
	#				$qrtDROPSsec[$j] = ($qrtDROPSsec[$j] + $ls[$i]);
					$totDROPSdate[$j]++;
	#				if ($totDROPSmax < $ls[$i]) {$totDROPSmax = $ls[$i];}
	#				if ($qrtDROPSmax[$j] < $ls[$i]) {$qrtDROPSmax[$j] = $ls[$i];}
					}
				if ($qs[$i] > 0) 
					{
					$totQUEUE++;
					$totQUEUEsec = ($totQUEUEsec + $qs[$i]);
					$totQUEUEsecDATE[$j] = ($totQUEUEsecDATE[$j] + $qs[$i]);
	#				$qrtQUEUE[$j]++;
	#				$qrtQUEUEsec[$j] = ($qrtQUEUEsec[$j] + $qs[$i]);
					$totQUEUEdate[$j]++;
	#				if ($totQUEUEmax < $qs[$i]) {$totQUEUEmax = $qs[$i];}
	#				if ($qrtQUEUEmax[$j] < $qs[$i]) {$qrtQUEUEmax[$j] = $qs[$i];}
					}
	/*
				if ($qs[$i] == 0) {$hd__0[$j]++;}
				if ( ($qs[$i] > 0) and ($qs[$i] <= 20) ) {$hd_20[$j]++;}
				if ( ($qs[$i] > 20) and ($qs[$i] <= 40) ) {$hd_40[$j]++;}
				if ( ($qs[$i] > 40) and ($qs[$i] <= 60) ) {$hd_60[$j]++;}
				if ( ($qs[$i] > 60) and ($qs[$i] <= 80) ) {$hd_80[$j]++;}
				if ( ($qs[$i] > 80) and ($qs[$i] <= 100) ) {$hd100[$j]++;}
				if ( ($qs[$i] > 100) and ($qs[$i] <= 120) ) {$hd120[$j]++;}
				if ($qs[$i] > 120) {$hd121[$j]++;}
	*/
				}
			
			$i++;
			}

		$j++;
		}


	###################################################
	### TOTALS SUMMARY SECTION ###
	$MAINH="+-------------------------------------------+---------+----------+----------+-----------+---------+---------+--------+--------+------------+------------+------------+";
	$MAIN1="|                                           | "._QXZ("TOTAL",7)." | "._QXZ("TOTAL",8)." | "._QXZ("TOTAL",8)." | "._QXZ("TOTAL",9)." | "._QXZ("TOTAL",7)." | "._QXZ("AVG",7)." | "._QXZ("AVG",6)." | "._QXZ("AVG",6)." | "._QXZ("TOTAL",10)." | "._QXZ("TOTAL",10)." | "._QXZ("TOTAL",10)." |";
	$MAIN2="| "._QXZ("SHIFT",41)." | "._QXZ("CALLS",7)." | "._QXZ("CALLS",8)." | "._QXZ("AGENTS",8)." | "._QXZ("CALLS",9)." | "._QXZ("ABANDON",7)." | "._QXZ("ABANDON",7)." | "._QXZ("ANSWER",6)." | "._QXZ("TALK",6)." | "._QXZ("TALK",10)." | "._QXZ("WRAP",10)." | "._QXZ("CALL",10)." |";
	$MAIN3="| "._QXZ("DATE-TIME RANGE",41)." | "._QXZ("OFFERED",7)." | "._QXZ("ANSWERED",8)." | "._QXZ("ANSWERED",8)." | "._QXZ("ABANDONED",9)." | "._QXZ("PERCENT",7)." | "._QXZ("TIME",7)." | "._QXZ("SPEED",6)." | "._QXZ("TIME",6)." | "._QXZ("TIME",10)." | "._QXZ("TIME",10)." | "._QXZ("TIME",10)." |";
	$CSV_text1="";
	$CSV_text2="";
	$CSV_text3="";

	for ($s=0; $s<count($status_array); $s++) {
		$status_name=explode(" ", $status_array[$s][1]);
		for ($j=2; $j<count($status_name); $j++) {
			$status_name[1].=" $status_name[$j]";
		}
		$MAINH.="------------+";
		$MAIN1.=" ".sprintf("%-10s", strtoupper($status_array[$s][0]))." |";
		$MAIN2.=" ".sprintf("%-10s", substr($status_name[0],0,10))." |";
		$MAIN3.=" ".sprintf("%-10s", substr($status_name[1],0,10))." |";
		$CSV_text1.=",\"".strtoupper($status_array[$s][0])."\"";
		$CSV_text2.=",\"$status_name[0]\"";
		$CSV_text3.=",\"$status_name[1]\"";
	}
	
	$ASCII_text.="$MAINH\n";
	$ASCII_text.="$MAIN1\n";
	$ASCII_text.="$MAIN2\n";
	$ASCII_text.="$MAIN3\n";
	$ASCII_text.="$MAINH\n";

	$CSV_text.="\"\",\""._QXZ("TOTAL")."\",\""._QXZ("TOTAL")."\",\""._QXZ("TOTAL")."\",\""._QXZ("TOTAL")."\",\""._QXZ("TOTAL")."\",\""._QXZ("AVG")."\",\""._QXZ("AVG")."\",\""._QXZ("AVG")."\",\""._QXZ("TOTAL")."\",\""._QXZ("TOTAL")."\",\""._QXZ("TOTAL")."\"$CSV_text1\n";
	$CSV_text.="\"\",\""._QXZ("CALLS")."\",\""._QXZ("CALLS")."\",\""._QXZ("AGENTS")."\",\""._QXZ("CALLS")."\",\""._QXZ("ABANDON")."\",\""._QXZ("ABANDON")."\",\""._QXZ("ANSWER")."\",\""._QXZ("TALK")."\",\""._QXZ("TALK")."\",\""._QXZ("WRAP")."\",\""._QXZ("CALL")."\"$CSV_text2\n";
	$CSV_text.="\""._QXZ("SHIFT DATE-TIME RANGE")."\",\""._QXZ("OFFERED")."\",\""._QXZ("ANSWERED")."\",\""._QXZ("ANSWERED")."\",\""._QXZ("ABANDONED")."\",\""._QXZ("PERCENT")."\",\""._QXZ("TIME")."\",\""._QXZ("SPEED")."\",\""._QXZ("TIME")."\",\""._QXZ("TIME")."\",\""._QXZ("TIME")."\",\""._QXZ("TIME")."\"$CSV_text3\n";

	##########################
	$JS_text="<script language='Javascript'>\n";
	$JS_onload="onload = function() {\n";
	$graph_stats=array();
	$mtd_graph_stats=array();
	$wtd_graph_stats=array();
	$qtd_graph_stats=array();
	$da=0; $wa=0; $ma=0; $qa=0;
	$max_offered=1;
	$max_answered=1;
	$max_agents=1;
	$max_abandoned=1;
	$max_abandonpct=1;
	$max_avgabandontime=1;
	$max_avganswerspeed=1;
	$max_avgtalktime=1;
	$max_totaltalktime=1;
	$max_totalwraptime=1;
	$max_totalcalltime=1;
	$max_wtd_offered=1;
	$max_wtd_answered=1;
	$max_wtd_agents=1;
	$max_wtd_abandoned=1;
	$max_wtd_abandonpct=1;
	$max_wtd_avgabandontime=1;
	$max_wtd_avganswerspeed=1;
	$max_wtd_avgtalktime=1;
	$max_wtd_totaltalktime=1;
	$max_wtd_totalwraptime=1;
	$max_wtd_totalcalltime=1;
	$max_mtd_offered=1;
	$max_mtd_answered=1;
	$max_mtd_abandoned=1;
	$max_mtd_agents=1;
	$max_mtd_abandonpct=1;
	$max_mtd_avgabandontime=1;
	$max_mtd_avganswerspeed=1;
	$max_mtd_avgtalktime=1;
	$max_mtd_totaltalktime=1;
	$max_mtd_totalwraptime=1;
	$max_mtd_totalcalltime=1;
	$max_qtd_offered=1;
	$max_qtd_answered=1;
	$max_qtd_abandoned=1;
	$max_qtd_agents=1;
	$max_qtd_abandonpct=1;
	$max_qtd_avgabandontime=1;
	$max_qtd_avganswerspeed=1;
	$max_qtd_avgtalktime=1;
	$max_qtd_totaltalktime=1;
	$max_qtd_totalwraptime=1;
	$max_qtd_totalcalltime=1;
	##########################

	$totCALLSwtd=0;
	$totANSWERSwtd=0;
	$totAGENTSwtd=0;  $AGENTS_wtd_array=array();
	$totANSWERSsecwtd=0;
	$totANSWERSspeedwtd=0;
	$totABANDONSwtd=0;
	$totABANDONSsecwtd=0;
	$totSTATUSESwtd=array();

	$totCALLSmtd=0;
	$totANSWERSmtd=0;
	$totAGENTSmtd=0;  $AGENTS_mtd_array=array();
	$totANSWERSsecmtd=0;
	$totANSWERSspeedmtd=0;
	$totABANDONSmtd=0;
	$totABANDONSsecmtd=0;
	$totSTATUSESmtd=array();

	$totCALLSqtd=0;
	$totANSWERSqtd=0;
	$totAGENTSqtd=0;  $AGENTS_qtd_array=array();
	$totANSWERSsecqtd=0;
	$totANSWERSspeedqtd=0;
	$totABANDONSqtd=0;
	$totABANDONSsecqtd=0;
	$totSTATUSESqtd=array();

	$totDROPSpctDATE=array();
	$totQUEUEpctDATE=array();
	$totDROPSavgDATE=array();
	$totQUEUEtotDATE=array();
	$totCALLSavgDATE=array();
	$totABANDONSpctDATE=array();
	$totABANDONSavgTIME=array();
	$totANSWERSavgspeedTIME=array();
	$totANSWERSavgTIME=array();
	$totANSWERStalkTIME=array();
	$totANSWERSwrapTIME=array();
	$totANSWERStotTIME=array();

	$d=0;
	while ($d < $TOTintervals)
		{
		if ($totDROPSdate[$d] < 1) {$totDROPSdate[$d]=0;}
		if ($totQUEUEdate[$d] < 1) {$totQUEUEdate[$d]=0;}
		if ($totCALLSdate[$d] < 1) {$totCALLSdate[$d]=0;}

		$totDROPSpctDATE[$d] = ( MathZDC($totDROPSdate[$d], $totCALLSdate[$d]) * 100);
		$totDROPSpctDATE[$d] = round($totDROPSpctDATE[$d], 2);
		$totQUEUEpctDATE[$d] = ( MathZDC($totQUEUEdate[$d], $totCALLSdate[$d]) * 100);
		$totQUEUEpctDATE[$d] = round($totQUEUEpctDATE[$d], 2);

		$totDROPSavgDATE[$d] = MathZDC($totDROPSsecDATE[$d], $totDROPSdate[$d]);
		$totQUEUEavgDATE[$d] = MathZDC($totQUEUEsecDATE[$d], $totQUEUEdate[$d]);
		$totQUEUEtotDATE[$d] = MathZDC($totQUEUEsecDATE[$d], $totCALLSdate[$d]);

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
		$totCALLSdate[$d] =	sprintf("%7s", $totCALLSdate[$d]);


		$totABANDONSpctDATE[$d] =	sprintf("%7.2f", (MathZDC(100*$totABANDONSdate[$d], $totCALLSdate[$d])));
		$totABANDONSavgTIME[$d] =	sprintf("%7s", date("i:s", mktime(0, 0, round(MathZDC($totABANDONSsecdate[$d], $totABANDONSdate[$d])))));
		if (round(MathZDC($totABANDONSsecdate[$d], $totABANDONSdate[$d]))>$max_avgabandontime) {$max_avgabandontime=round(MathZDC($totABANDONSsecdate[$d], $totABANDONSdate[$d]));}
		$graph_stats[$d][11]=round(MathZDC($totABANDONSsecdate[$d], $totABANDONSdate[$d]));

		$totANSWERSavgspeedTIME[$d] =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSspeeddate[$d], $totANSWERSdate[$d])))));
		$totANSWERSavgTIME[$d] =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSsecdate[$d], $totANSWERSdate[$d])))));
		if (round(MathZDC($totANSWERSspeeddate[$d], $totANSWERSdate[$d]))>$max_avganswerspeed) {$max_avganswerspeed=round(MathZDC($totANSWERSspeeddate[$d], $totANSWERSdate[$d]));}
		$graph_stats[$d][12]=round(MathZDC($totANSWERSspeeddate[$d], $totANSWERSdate[$d]));
		$graph_stats[$d][16]=round(MathZDC($totANSWERSsecdate[$d], $totANSWERSdate[$d]));

		$totANSWERStalkTIME[$d] =	sprintf("%10s", floor(MathZDC($totANSWERSsecdate[$d], 3600)).date(":i:s", mktime(0, 0, $totANSWERSsecdate[$d])));
		$totANSWERSwrapTIME[$d] =	sprintf("%10s", floor(MathZDC(($totANSWERSdate[$d]*15), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSdate[$d]*15))));
		if (($totANSWERSdate[$d]*15)>$max_totalwraptime) {$max_totalwraptime=($totANSWERSdate[$d]*15);}
		$graph_stats[$d][13]=($totANSWERSdate[$d]*15);
		$graph_stats[$d][14]=($totANSWERSsecdate[$d]+($totANSWERSdate[$d]*15));
		$graph_stats[$d][15]=$totANSWERSsecdate[$d];

		$totANSWERStotTIME[$d] =	sprintf("%10s", floor(MathZDC(($totANSWERSsecdate[$d]+($totANSWERSdate[$d]*15)), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSsecdate[$d]+($totANSWERSdate[$d]*15)))));
		$totANSWERSdate[$d] =	sprintf("%8s", $totANSWERSdate[$d]);
		$totABANDONSdate[$d] =	sprintf("%9s", $totABANDONSdate[$d]);

		if (date("w", strtotime($daySTART[$d]))==0 && date("w", strtotime($daySTART[$d-1]))!=0 && $d>0) 
			{  # 2nd date/"w" check is for DST
			$totAGENTSwtd=count(array_count_values($AGENTS_wtd_array));
			$totABANDONSpctwtd =	sprintf("%7.2f", (MathZDC(100*$totABANDONSwtd, $totCALLSwtd)));
			$totABANDONSavgTIMEwtd =	sprintf("%7s", date("i:s", mktime(0, 0, round(MathZDC($totABANDONSsecwtd, $totABANDONSwtd)))));
			if (round(MathZDC($totABANDONSsecwtd, $totABANDONSwtd))>$max_wtd_avgabandontime) {$max_wtd_avgabandontime=round(MathZDC($totABANDONSsecwtd, $totABANDONSwtd));}
			$wtd_graph_stats[$wa][11]=round(MathZDC($totABANDONSsecwtd, $totABANDONSwtd));
			$totANSWERSavgspeedTIMEwtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSspeedwtd, $totANSWERSwtd)))));
			$totANSWERSavgTIMEwtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSsecwtd, $totANSWERSwtd)))));
			if (round(MathZDC($totANSWERSspeedwtd, $totANSWERSwtd))>$max_wtd_avganswerspeed) {$max_wtd_avganswerspeed=round(MathZDC($totANSWERSspeedwtd, $totANSWERSwtd));}
			$wtd_graph_stats[$wa][12]=round(MathZDC($totANSWERSspeedwtd, $totANSWERSwtd));
			$wtd_graph_stats[$wa][16]=round(MathZDC($totANSWERSsecwtd, $totANSWERSwtd));
			$totANSWERStalkTIMEwtd =	sprintf("%10s", floor(MathZDC($totANSWERSsecwtd, 3600)).date(":i:s", mktime(0, 0, $totANSWERSsecwtd)));
			$totANSWERSwrapTIMEwtd =	sprintf("%10s", floor(MathZDC(($totANSWERSwtd*15), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSwtd*15))));
			if (($totANSWERSwtd*15)>$max_wtd_totalwraptime) {$max_wtd_totalwraptime=($totANSWERSwtd*15);}
			$wtd_graph_stats[$wa][13]=($totANSWERSwtd*15);
			$wtd_graph_stats[$wa][14]=($totANSWERSsecwtd+($totANSWERSwtd*15));
			$wtd_graph_stats[$wa][15]=$totANSWERSsecwtd;
			$totANSWERStotTIMEwtd =	sprintf("%10s", floor(MathZDC(($totANSWERSsecwtd+($totANSWERSwtd*15)), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSsecwtd+($totANSWERSwtd*15)))));
			# $totAGENTSwtd =	sprintf("%8s", $totAGENTSwtd);
			$totAGENTSwtd=count(array_count_values($AGENTS_wtd_array));
			$totAGENTSwtd =	sprintf("%8s", $totAGENTSwtd);
			$totANSWERSwtd =	sprintf("%8s", $totANSWERSwtd);
			$totABANDONSwtd =	sprintf("%9s", $totABANDONSwtd);
			$totCALLSwtd =	sprintf("%7s", $totCALLSwtd);		

			if (trim($totCALLSwtd)>$max_wtd_offered) {$max_wtd_offered=trim($totCALLSwtd);}
			if (trim($totANSWERSwtd)>$max_wtd_answered) {$max_wtd_answered=trim($totANSWERSwtd);}
			if (trim($totAGENTSwtd)>$max_wtd_agents) {$max_wtd_agents=trim($totAGENTSwtd);}
			if (trim($totABANDONSwtd)>$max_wtd_abandoned) {$max_wtd_abandoned=trim($totABANDONSwtd);}
			if (trim($totABANDONSpctwtd)>$max_wtd_abandonpct) {$max_wtd_abandonpct=trim($totABANDONSpctwtd);}

			if (round(MathZDC($totANSWERSsecwtd, $totANSWERSwtd))>$max_wtd_avgtalktime) {$max_wtd_avgtalktime=round(MathZDC($totANSWERSsecwtd, $totANSWERSwtd));}
			if (trim($totANSWERSsecwtd)>$max_wtd_totaltalktime) {$max_wtd_totaltalktime=trim($totANSWERSsecwtd);}
			if (trim($totANSWERSsecwtd+($totANSWERSwtd*15))>$max_wtd_totalcalltime) {$max_wtd_totalcalltime=trim($totANSWERSsecwtd+($totANSWERSwtd*15));}
			$week=date("W", strtotime($dayEND[$d-1]));
			$year=substr($dayEND[$d-1],0,4);
			$wtd_graph_stats[$wa][0]="Week $week, $year";
			$wtd_graph_stats[$wa][1]=trim($totCALLSwtd);
			$wtd_graph_stats[$wa][2]=trim($totANSWERSwtd);
			$wtd_graph_stats[$wa][3]=trim($totABANDONSwtd);
			$wtd_graph_stats[$wa][4]=trim($totABANDONSpctwtd);
			$wtd_graph_stats[$wa][5]=trim($totABANDONSavgTIMEwtd);
			$wtd_graph_stats[$wa][6]=trim($totANSWERSavgspeedTIMEwtd);
			$wtd_graph_stats[$wa][7]=trim($totANSWERSavgTIMEwtd);
			$wtd_graph_stats[$wa][8]=trim($totANSWERStalkTIMEwtd);
			$wtd_graph_stats[$wa][9]=trim($totANSWERSwrapTIMEwtd);
			$wtd_graph_stats[$wa][10]=trim($totANSWERStotTIMEwtd);
			$wtd_graph_stats[$wa][17]=trim($totAGENTSwtd);
			$wa++;

			$ASCII_text.="$MAINH\n";
			$ASCII_text.="| "._QXZ("Week to date",41,"r")." | $totCALLSwtd | $totANSWERSwtd | $totAGENTSwtd | $totABANDONSwtd | $totABANDONSpctwtd%| $totABANDONSavgTIMEwtd | $totANSWERSavgspeedTIMEwtd | $totANSWERSavgTIMEwtd | $totANSWERStalkTIMEwtd | $totANSWERSwrapTIMEwtd | $totANSWERStotTIMEwtd |";
			$CSV_text.="\""._QXZ("Week to date")."\",\"$totCALLSwtd\",\"$totANSWERSwtd\",\"$totAGENTSwtd\",\"$totABANDONSwtd\",\"$totABANDONSpctwtd%\",\"$totABANDONSavgTIMEwtd\",\"$totANSWERSavgspeedTIMEwtd\",\"$totANSWERSavgTIMEwtd\",\"$totANSWERStalkTIMEwtd\",\"$totANSWERSwrapTIMEwtd\",\"$totANSWERStotTIMEwtd\"";
			for ($s=0; $s<count($status_array); $s++) {
				$ASCII_text.=" ".sprintf("%10s", ($totSTATUSESwtd[$status_array[$s][0]]+0))." |";
				$CSV_text.=",\"".sprintf("%10s", ($totSTATUSESwtd[$status_array[$s][0]]+0))."\"";
			}
			$ASCII_text.="\n";
			$CSV_text.="\n";
			$ASCII_text.="$MAINH\n";

			$totCALLSwtd=0;
			$totANSWERSwtd=0;
			$AGENTS_wtd_array=array();
			$totANSWERSsecwtd=0;
			$totANSWERSspeedwtd=0;
			$totABANDONSwtd=0;
			$totABANDONSsecwtd=0;
			$totSTATUSESwtd=array();
			}

		if (date("d", strtotime($daySTART[$d]))==1 && $d>0 && date("d", strtotime($daySTART[$d-1]))!=1) 
			{
			$totAGENTSmtd=count(array_count_values($AGENTS_mtd_array));
			$totABANDONSpctmtd =	sprintf("%7.2f", (MathZDC(100*$totABANDONSmtd, $totCALLSmtd)));
			$totABANDONSavgTIMEmtd =	sprintf("%7s", date("i:s", mktime(0, 0, round(MathZDC($totABANDONSsecmtd, $totABANDONSmtd)))));
			if (round(MathZDC($totABANDONSsecmtd, $totABANDONSmtd))>$max_mtd_avgabandontime) {$max_mtd_avgabandontime=round(MathZDC($totABANDONSsecmtd, $totABANDONSmtd));}
			$mtd_graph_stats[$ma][11]=round(MathZDC($totABANDONSsecmtd, $totABANDONSmtd));
			$totANSWERSavgspeedTIMEmtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSspeedmtd, $totANSWERSmtd)))));
			$totANSWERSavgTIMEmtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSsecmtd, $totANSWERSmtd)))));
			if (round(MathZDC($totANSWERSspeedmtd, $totANSWERSmtd))>$max_mtd_avganswerspeed) {$max_mtd_avganswerspeed=round(MathZDC($totANSWERSspeedmtd, $totANSWERSmtd));}
			$mtd_graph_stats[$ma][12]=round(MathZDC($totANSWERSspeedmtd, $totANSWERSmtd));
			$mtd_graph_stats[$ma][16]=round(MathZDC($totANSWERSsecmtd, $totANSWERSmtd));
			$totANSWERStalkTIMEmtd =	sprintf("%10s", floor(MathZDC($totANSWERSsecmtd, 3600)).date(":i:s", mktime(0, 0, $totANSWERSsecmtd)));
			$totANSWERSwrapTIMEmtd =	sprintf("%10s", floor(MathZDC(($totANSWERSmtd*15), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSmtd*15))));
			if (($totANSWERSmtd*15)>$max_mtd_totalwraptime) {$max_mtd_totalwraptime=($totANSWERSmtd*15);}
			$mtd_graph_stats[$ma][13]=($totANSWERSmtd*15);
			$mtd_graph_stats[$ma][14]=($totANSWERSsecmtd+($totANSWERSmtd*15));
			$mtd_graph_stats[$ma][15]=$totANSWERSsecmtd;
			$totANSWERStotTIMEmtd =	sprintf("%10s", floor(MathZDC(($totANSWERSsecmtd+($totANSWERSmtd*15)), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSsecmtd+($totANSWERSmtd*15)))));
			$totAGENTSmtd=count(array_count_values($AGENTS_mtd_array));
			$totAGENTSmtd =	sprintf("%8s", $totAGENTSmtd);
			$totANSWERSmtd =	sprintf("%8s", $totANSWERSmtd);
			$totABANDONSmtd =	sprintf("%9s", $totABANDONSmtd);
			$totCALLSmtd =	sprintf("%7s", $totCALLSmtd);		

			if (trim($totCALLSmtd)>$max_mtd_offered) {$max_mtd_offered=trim($totCALLSmtd);}
			if (trim($totANSWERSmtd)>$max_mtd_answered) {$max_mtd_answered=trim($totANSWERSmtd);}
			if (trim($totABANDONSmtd)>$max_mtd_abandoned) {$max_mtd_abandoned=trim($totABANDONSmtd);}
			if (trim($totAGENTSmtd)>$max_mtd_agents) {$max_mtd_agents=trim($totAGENTSmtd);}
			if (trim($totABANDONSpctmtd)>$max_mtd_abandonpct) {$max_mtd_abandonpct=trim($totABANDONSpctmtd);}
			if (round(MathZDC($totANSWERSsecmtd, $totANSWERSmtd))>$max_mtd_avgtalktime) {$max_mtd_avgtalktime=round(MathZDC($totANSWERSsecmtd, $totANSWERSmtd));}
			if (trim($totANSWERSsecmtd)>$max_mtd_totaltalktime) {$max_mtd_totaltalktime=trim($totANSWERSsecmtd);}
			if (trim($totANSWERSsecmtd+($totANSWERSmtd*15))>$max_mtd_totalcalltime) {$max_mtd_totalcalltime=trim($totANSWERSsecmtd+($totANSWERSmtd*15));}
			# print $dayEND[$d-1]."\n";
			$month=date("F", strtotime($dayEND[$d-1])-3600);  ## ACCOUNT FOR DST IN LABELING
			$year=substr($dayEND[$d-1], 0, 4);
			$mtd_graph_stats[$ma][0]="$month $year";
			$mtd_graph_stats[$ma][1]=trim($totCALLSmtd);
			$mtd_graph_stats[$ma][2]=trim($totANSWERSmtd);
			$mtd_graph_stats[$ma][3]=trim($totABANDONSmtd);
			$mtd_graph_stats[$ma][4]=trim($totABANDONSpctmtd);
			$mtd_graph_stats[$ma][5]=trim($totABANDONSavgTIMEmtd);
			$mtd_graph_stats[$ma][6]=trim($totANSWERSavgspeedTIMEmtd);
			$mtd_graph_stats[$ma][7]=trim($totANSWERSavgTIMEmtd);
			$mtd_graph_stats[$ma][8]=trim($totANSWERStalkTIMEmtd);
			$mtd_graph_stats[$ma][9]=trim($totANSWERSwrapTIMEmtd);
			$mtd_graph_stats[$ma][10]=trim($totANSWERStotTIMEmtd);
			$mtd_graph_stats[$ma][17]=trim($totAGENTSmtd);
			$ma++;

			$ASCII_text.="$MAINH\n";
			$ASCII_text.="| "._QXZ("Month to date",41,"r")." | $totCALLSmtd | $totANSWERSmtd | $totAGENTSmtd | $totABANDONSmtd | $totABANDONSpctmtd%| $totABANDONSavgTIMEmtd | $totANSWERSavgspeedTIMEmtd | $totANSWERSavgTIMEmtd | $totANSWERStalkTIMEmtd | $totANSWERSwrapTIMEmtd | $totANSWERStotTIMEmtd |";
			$CSV_text.="\""._QXZ("Month to date")."\",\"$totCALLSmtd\",\"$totANSWERSmtd\",\"$totAGENTSmtd\",\"$totABANDONSmtd\",\"$totABANDONSpctmtd%\",\"$totABANDONSavgTIMEmtd\",\"$totANSWERSavgspeedTIMEmtd\",\"$totANSWERSavgTIMEmtd\",\"$totANSWERStalkTIMEmtd\",\"$totANSWERSwrapTIMEmtd\",\"$totANSWERStotTIMEmtd\"";
			for ($s=0; $s<count($status_array); $s++) {
				$ASCII_text.=" ".sprintf("%10s", ($totSTATUSESmtd[$status_array[$s][0]]+0))." |";
				$CSV_text.=",\"".sprintf("%10s", ($totSTATUSESmtd[$status_array[$s][0]]+0))."\"";
			}
			$ASCII_text.="\n";
			$CSV_text.="\n";
			$ASCII_text.="$MAINH\n";

			$totCALLSmtd=0;
			$totANSWERSmtd=0;
			$AGENTS_mtd_array=array();
			$totANSWERSsecmtd=0;
			$totANSWERSspeedmtd=0;
			$totABANDONSmtd=0;
			$totABANDONSsecmtd=0;
			$totSTATUSESmtd=array();

			if (date("m", strtotime($daySTART[$d]))==1 || date("m", strtotime($daySTART[$d]))==4 || date("m", strtotime($daySTART[$d]))==7 || date("m", strtotime($daySTART[$d]))==10) # Quarterly line
				{
				$totAGENTSqtd=count(array_count_values($AGENTS_qtd_array));
				$totABANDONSpctqtd =	sprintf("%7.2f", (MathZDC(100*$totABANDONSqtd, $totCALLSqtd)));
				$totABANDONSavgTIMEqtd =	sprintf("%7s", date("i:s", mktime(0, 0, round(MathZDC($totABANDONSsecqtd, $totABANDONSqtd)))));
				if (round(MathZDC($totABANDONSsecqtd, $totABANDONSqtd))>$max_qtd_avgabandontime) {$max_qtd_avgabandontime=round(MathZDC($totABANDONSsecqtd, $totABANDONSqtd));}
				$qtd_graph_stats[$qa][11]=round(MathZDC($totABANDONSsecqtd, $totABANDONSqtd));
				$totANSWERSavgspeedTIMEqtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSspeedqtd, $totANSWERSqtd)))));
				$totANSWERSavgTIMEqtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSsecqtd, $totANSWERSqtd)))));
				if (round(MathZDC($totANSWERSspeedqtd, $totANSWERSqtd))>$max_qtd_avganswerspeed) {$max_qtd_avganswerspeed=round(MathZDC($totANSWERSspeedqtd, $totANSWERSqtd));}
				$qtd_graph_stats[$qa][12]=round(MathZDC($totANSWERSspeedqtd, $totANSWERSqtd));
				$qtd_graph_stats[$qa][16]=round(MathZDC($totANSWERSsecqtd, $totANSWERSqtd));
				$totANSWERStalkTIMEqtd =	sprintf("%10s", floor(MathZDC($totANSWERSsecqtd, 3600)).date(":i:s", mktime(0, 0, $totANSWERSsecqtd)));
				$totANSWERSwrapTIMEqtd =	sprintf("%10s", floor(MathZDC(($totANSWERSqtd*15), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSqtd*15))));
				if (($totANSWERSqtd*15)>$max_qtd_totalwraptime) {$max_qtd_totalwraptime=($totANSWERSqtd*15);}
				$qtd_graph_stats[$qa][13]=($totANSWERSqtd*15);
				$qtd_graph_stats[$qa][14]=($totANSWERSsecqtd+($totANSWERSqtd*15));
				$qtd_graph_stats[$qa][15]=$totANSWERSsecqtd;
				$totANSWERStotTIMEqtd =	sprintf("%10s", floor(MathZDC(($totANSWERSsecqtd+($totANSWERSqtd*15)), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSsecqtd+($totANSWERSqtd*15)))));
				$totAGENTSqtd=count(array_count_values($AGENTS_qtd_array));
				$totAGENTSqtd =	sprintf("%8s", $totAGENTSqtd);
				$totANSWERSqtd =	sprintf("%8s", $totANSWERSqtd);
				$totABANDONSqtd =	sprintf("%9s", $totABANDONSqtd);
				$totCALLSqtd =	sprintf("%7s", $totCALLSqtd);		

				if (trim($totCALLSqtd)>$max_qtd_offered) {$max_qtd_offered=trim($totCALLSqtd);}
				if (trim($totANSWERSqtd)>$max_qtd_answered) {$max_qtd_answered=trim($totANSWERSqtd);}
				if (trim($totABANDONSqtd)>$max_qtd_abandoned) {$max_qtd_abandoned=trim($totABANDONSqtd);}
				if (trim($totAGENTSqtd)>$max_qtd_answers) {$max_qtd_answered=trim($totAGENTSqtd);}
				if (trim($totABANDONSpctqtd)>$max_qtd_abandonpct) {$max_qtd_abandonpct=trim($totABANDONSpctqtd);}
				if (round(MathZDC($totANSWERSsecqtd, $totANSWERSqtd))>$max_qtd_avgtalktime) {$max_qtd_avgtalktime=round(MathZDC($totANSWERSsecqtd, $totANSWERSqtd));}
				if (trim($totANSWERSsecqtd)>$max_qtd_totaltalktime) {$max_qtd_totaltalktime=trim($totANSWERSsecqtd);}
				if (trim($totANSWERSsecqtd+($totANSWERSqtd*15))>$max_qtd_totalcalltime) {$max_qtd_totalcalltime=trim($totANSWERSsecqtd+($totANSWERSqtd*15));}
				$month=date("m", strtotime($dayEND[$d]));
				$year=substr($dayEND[$d], 0, 4);
				$qtr4=array("01","02","03");
				$qtr1=array("04","05","06");
				$qtr2=array("07","08","09");
				$qtr3=array("10","11","12");
				if(in_array($month,$qtr1)) {
					$qtr="1st";
				} else if(in_array($month,$qtr2)) {
					$qtr="2nd";
				}  else if(in_array($month,$qtr3)) {
					$qtr="3rd";
				}  else if(in_array($month,$qtr4)) {
					$qtr="4th";
				}
				$qtd_graph_stats[$qa][0]="$qtr quarter, $year";
				$qtd_graph_stats[$qa][1]=trim($totCALLSqtd);
				$qtd_graph_stats[$qa][2]=trim($totANSWERSqtd);
				$qtd_graph_stats[$qa][3]=trim($totABANDONSqtd);
				$qtd_graph_stats[$qa][4]=trim($totABANDONSpctqtd);
				$qtd_graph_stats[$qa][5]=trim($totABANDONSavgTIMEqtd);
				$qtd_graph_stats[$qa][6]=trim($totANSWERSavgspeedTIMEqtd);
				$qtd_graph_stats[$qa][7]=trim($totANSWERSavgTIMEqtd);
				$qtd_graph_stats[$qa][8]=trim($totANSWERStalkTIMEqtd);
				$qtd_graph_stats[$qa][9]=trim($totANSWERSwrapTIMEqtd);
				$qtd_graph_stats[$qa][10]=trim($totANSWERStotTIMEqtd);
				$qtd_graph_stats[$qa][17]=trim($totAGENTSqtd);
				$qa++;

				$ASCII_text.="| "._QXZ("Quarter to date",41,"r")." | $totCALLSqtd | $totANSWERSqtd | $totAGENTSqtd | $totABANDONSqtd | $totABANDONSpctqtd%| $totABANDONSavgTIMEqtd | $totANSWERSavgspeedTIMEqtd | $totANSWERSavgTIMEqtd | $totANSWERStalkTIMEqtd | $totANSWERSwrapTIMEqtd | $totANSWERStotTIMEqtd |";
				$CSV_text.="\""._QXZ("Quarter to date")."\",\"$totCALLSqtd\",\"$totANSWERSqtd\",\"$totAGENTSqtd\",\"$totABANDONSqtd\",\"$totABANDONSpctqtd%\",\"$totABANDONSavgTIMEqtd\",\"$totANSWERSavgspeedTIMEqtd\",\"$totANSWERSavgTIMEqtd\",\"$totANSWERStalkTIMEqtd\",\"$totANSWERSwrapTIMEqtd\",\"$totANSWERStotTIMEqtd\"";
				for ($s=0; $s<count($status_array); $s++) {
					$ASCII_text.=" ".sprintf("%10s", ($totSTATUSESqtd[$status_array[$s][0]]+0))." |";
					$CSV_text.=",\"".sprintf("%10s", ($totSTATUSESqtd[$status_array[$s][0]]+0))."\"";
				}
				$ASCII_text.="\n";
				$CSV_text.="\n";
				$ASCII_text.="$MAINH\n";

				$totCALLSqtd=0;
				$totANSWERSqtd=0;
				$AGENTS_qtd_array=array();
				$totANSWERSsecqtd=0;
				$totANSWERSspeedqtd=0;
				$totABANDONSqtd=0;
				$totABANDONSsecqtd=0;
				$totSTATUSESqtd=array();
				}
			}

		if (!isset($totAGENTSdate[$d])) {$totAGENTSdate[$d]=array();}
		$totAGENTSdayCOUNT=count($totAGENTSdate[$d]);
		$totAGENTSday=sprintf("%8s", count($totAGENTSdate[$d]));

		if ($totAGENTSdayCOUNT > 0)
			{
			$temp_agent_array=array_keys($totAGENTSdate[$d]);
			for ($x=0; $x<count($temp_agent_array); $x++) 
				{
				if ($temp_agent_array[$x]!="") 
					{
					array_push($AGENTS_wtd_array, $temp_agent_array[$x]);
					array_push($AGENTS_mtd_array, $temp_agent_array[$x]);
					array_push($AGENTS_qtd_array, $temp_agent_array[$x]);
					}
				}
			}
		$totCALLSwtd+=$totCALLSdate[$d];
		$totANSWERSwtd+=$totANSWERSdate[$d];
		$totANSWERSsecwtd+=$totANSWERSsecdate[$d];
		$totANSWERSspeedwtd+=$totANSWERSspeeddate[$d];
		$totABANDONSwtd+=$totABANDONSdate[$d];
		$totABANDONSsecwtd+=$totABANDONSsecdate[$d];
		$totCALLSmtd+=$totCALLSdate[$d];
		$totANSWERSmtd+=$totANSWERSdate[$d];
		$totANSWERSsecmtd+=$totANSWERSsecdate[$d];
		$totANSWERSspeedmtd+=$totANSWERSspeeddate[$d];
		$totABANDONSmtd+=$totABANDONSdate[$d];
		$totABANDONSsecmtd+=$totABANDONSsecdate[$d];
		$totCALLSqtd+=$totCALLSdate[$d];
		$totANSWERSqtd+=$totANSWERSdate[$d];
		$totANSWERSsecqtd+=$totANSWERSsecdate[$d];
		$totANSWERSspeedqtd+=$totANSWERSspeeddate[$d];
		$totABANDONSqtd+=$totABANDONSdate[$d];
		$totABANDONSsecqtd+=$totABANDONSsecdate[$d];

		if (trim($totCALLSdate[$d])>$max_offered) {$max_offered=trim($totCALLSdate[$d]);}
		if (trim($totANSWERSdate[$d])>$max_answered) {$max_answered=trim($totANSWERSdate[$d]);}
		if (trim($totAGENTSday)>$max_agents) {$max_agents=trim($totAGENTSday);}
		if (trim($totABANDONSdate[$d])>$max_abandoned) {$max_abandoned=trim($totABANDONSdate[$d]);}
		if (trim($totABANDONSpctDATE[$d])>$max_abandonpct) {$max_abandonpct=trim($totABANDONSpctDATE[$d]);}

		if (round(MathZDC($totANSWERSsecdate[$d], $totANSWERSdate[$d]))>$max_avgtalktime) 
			{$max_avgtalktime=round(MathZDC($totANSWERSsecdate[$d], $totANSWERSdate[$d]));}

		if (trim($totANSWERSsecdate[$d])>$max_totaltalktime) {$max_totaltalktime=trim($totANSWERSsecdate[$d]);}
		if (trim($totANSWERSsecdate[$d]+($totANSWERSdate[$d]*15))>$max_totalcalltime) {$max_totalcalltime=trim($totANSWERSsecdate[$d]+($totANSWERSdate[$d]*15));}
		$graph_stats[$d][0]="$daySTART[$d] - $dayEND[$d]";
		$graph_stats[$d][1]=trim($totCALLSdate[$d]);
		$graph_stats[$d][2]=trim($totANSWERSdate[$d]);
		$graph_stats[$d][3]=trim($totABANDONSdate[$d]);
		$graph_stats[$d][4]=trim($totABANDONSpctDATE[$d]);
		$graph_stats[$d][5]=trim($totABANDONSavgTIME[$d]);
		$graph_stats[$d][6]=trim($totANSWERSavgspeedTIME[$d]);
		$graph_stats[$d][7]=trim($totANSWERSavgTIME[$d]);
		$graph_stats[$d][8]=trim($totANSWERStalkTIME[$d]);
		$graph_stats[$d][9]=trim($totANSWERSwrapTIME[$d]);
		$graph_stats[$d][10]=trim($totANSWERStotTIME[$d]);
		$graph_stats[$d][17]=trim($totAGENTSday);

		$ASCII_text.="| $daySTART[$d] - $dayEND[$d] | $totCALLSdate[$d] | $totANSWERSdate[$d] | $totAGENTSday | $totABANDONSdate[$d] | $totABANDONSpctDATE[$d]%| $totABANDONSavgTIME[$d] | $totANSWERSavgspeedTIME[$d] | $totANSWERSavgTIME[$d] | $totANSWERStalkTIME[$d] | $totANSWERSwrapTIME[$d] | $totANSWERStotTIME[$d] |";
		$CSV_text.="\"$daySTART[$d] - $dayEND[$d]\",\"$totCALLSdate[$d]\",\"$totANSWERSdate[$d]\",\"$totAGENTSday\",\"$totABANDONSdate[$d]\",\"$totABANDONSpctDATE[$d]%\",\"$totABANDONSavgTIME[$d]\",\"$totANSWERSavgspeedTIME[$d]\",\"$totANSWERSavgTIME[$d]\",\"$totANSWERStalkTIME[$d]\",\"$totANSWERSwrapTIME[$d]\",\"$totANSWERStotTIME[$d]\"";
		for ($s=0; $s<count($status_array); $s++) {
			$ASCII_text.=" ".sprintf("%10s", ($totSTATUSESdate[$d][$status_array[$s][0]]+0))." |";
			$CSV_text.=",\"".sprintf("%10s", ($totSTATUSESdate[$d][$status_array[$s][0]]+0))."\"";
			$totSTATUSESwtd[$status_array[$s][0]]+=$totSTATUSESdate[$d][$status_array[$s][0]];
			$totSTATUSESmtd[$status_array[$s][0]]+=$totSTATUSESdate[$d][$status_array[$s][0]];
			$totSTATUSESqtd[$status_array[$s][0]]+=$totSTATUSESdate[$d][$status_array[$s][0]];
		}
		$ASCII_text.="\n";
		$CSV_text.="\n";

		$d++;
		}

	$totDROPSpct = ( MathZDC($totDROPS, $totCALLS) * 100);
	$totDROPSpct = round($totDROPSpct, 2);
	$totQUEUEpct = ( MathZDC($totQUEUE, $totCALLS) * 100);
	$totQUEUEpct = round($totQUEUEpct, 2);

	$totDROPSavg = MathZDC($totDROPSsec, $totDROPS);
	$totQUEUEavg = MathZDC($totQUEUEsec, $totQUEUE);
	$totQUEUEtot = MathZDC($totQUEUEsec, $totCALLS);

	$totCALLSavg = MathZDC($totCALLSsec, $totCALLS);
	$totTIME_M = MathZDC($totCALLSsec, 60);
	$totTIME_M_int = round($totTIME_M, 2);
	$totTIME_M_int = intval("$totTIME_M");
	$totTIME_S = ($totTIME_M - $totTIME_M_int);
	$totTIME_S = ($totTIME_S * 60);
	$totTIME_S = round($totTIME_S, 0);
	if ($totTIME_S < 10) {$totTIME_S = "0$totTIME_S";}
	$totTIME_MS = "$totTIME_M_int:$totTIME_S";
	$totTIME_MS =		sprintf("%9s", $totTIME_MS);



		$FtotCALLSavg =	sprintf("%6.0f", $totCALLSavg);
		$FtotDROPSavg =	sprintf("%7.2f", $totDROPSavg);
		$FtotQUEUEavg =	sprintf("%7.2f", $totQUEUEavg);
		$FtotQUEUEtot =	sprintf("%7.2f", $totQUEUEtot);
		$FtotDROPSpct =	sprintf("%6.2f", $totDROPSpct);
		$FtotQUEUEpct =	sprintf("%6.2f", $totQUEUEpct);
		$FtotDROPS =	sprintf("%6s", $totDROPS);
		$FtotQUEUE =	sprintf("%6s", $totQUEUE);
		$FtotCALLS =	sprintf("%7s", $totCALLS);

		$FtotABANDONSpct =	sprintf("%7.2f", (MathZDC(100*$FtotABANDONS, $FtotCALLS)));
		$FtotABANDONSavgTIME =	sprintf("%7s", date("i:s", mktime(0, 0, round(MathZDC($FtotABANDONSsec, $FtotABANDONS)))));
		$FtotANSWERSavgspeedTIME =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($FtotANSWERSspeed, $FtotANSWERS)))));
		$FtotANSWERSavgTIME =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($FtotANSWERSsec, $FtotANSWERS)))));
		$FtotANSWERStalkTIME =	sprintf("%10s", floor(MathZDC($FtotANSWERSsec, 3600)).date(":i:s", mktime(0, 0, $FtotANSWERSsec)));
		$FtotANSWERSwrapTIME =	sprintf("%10s", floor(MathZDC(($FtotANSWERS*15), 3600)).date(":i:s", mktime(0, 0, ($FtotANSWERS*15))));
		$FtotANSWERStotTIME =	sprintf("%10s", floor(MathZDC(($FtotANSWERSsec+($FtotANSWERS*15)), 3600)).date(":i:s", mktime(0, 0, ($FtotANSWERSsec+($FtotANSWERS*15)))));
		$FtotANSWERS =	sprintf("%8s", $FtotANSWERS);
		$FtotABANDONS =	sprintf("%9s", $FtotABANDONS);

		if (date("w", strtotime($daySTART[$d]))>0) 
			{
			$totABANDONSpctwtd =	sprintf("%7.2f", (MathZDC(100*$totABANDONSwtd, $totCALLSwtd)));
			$totABANDONSavgTIMEwtd =	sprintf("%7s", date("i:s", mktime(0, 0, round(MathZDC($totABANDONSsecwtd, $totABANDONSwtd)))));
			if (round(MathZDC($totABANDONSsecwtd, $totABANDONSwtd))>$max_wtd_avgabandontime) {$max_wtd_avgabandontime=round(MathZDC($totABANDONSsecwtd, $totABANDONSwtd));}
			$wtd_graph_stats[$wa][11]=round(MathZDC($totABANDONSsecwtd, $totABANDONSwtd));
			$totANSWERSavgspeedTIMEwtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSspeedwtd, $totANSWERSwtd)))));
			$totANSWERSavgTIMEwtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSsecwtd, $totANSWERSwtd)))));
			if (round(MathZDC($totANSWERSspeedwtd, $totANSWERSwtd))>$max_wtd_avganswerspeed) {$max_wtd_avganswerspeed=round(MathZDC($totANSWERSspeedwtd, $totANSWERSwtd));}
			$wtd_graph_stats[$wa][12]=round(MathZDC($totANSWERSspeedwtd, $totANSWERSwtd));
			$wtd_graph_stats[$wa][16]=round(MathZDC($totANSWERSsecwtd, $totANSWERSwtd));
			$totANSWERStalkTIMEwtd =	sprintf("%10s", floor(MathZDC($totANSWERSsecwtd, 3600)).date(":i:s", mktime(0, 0, $totANSWERSsecwtd)));
			$totANSWERSwrapTIMEwtd =	sprintf("%10s", floor(MathZDC(($totANSWERSwtd*15), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSwtd*15))));
			if (($totANSWERSwtd*15)>$max_wtd_totalwraptime) {$max_wtd_totalwraptime=($totANSWERSwtd*15);}
			$wtd_graph_stats[$wa][13]=($totANSWERSwtd*15);
			$wtd_graph_stats[$wa][14]=($totANSWERSsecwtd+($totANSWERSwtd*15));
			$wtd_graph_stats[$wa][15]=$totANSWERSsecwtd;
			$totANSWERStotTIMEwtd =	sprintf("%10s", floor(MathZDC(($totANSWERSsecwtd+($totANSWERSwtd*15)), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSsecwtd+($totANSWERSwtd*15)))));
			$totAGENTSwtd=count(array_count_values($AGENTS_wtd_array));
			$totAGENTSwtd =	sprintf("%8s", $totAGENTSwtd);
			$totANSWERSwtd =	sprintf("%8s", $totANSWERSwtd);
			$totABANDONSwtd =	sprintf("%9s", $totABANDONSwtd);
			$totCALLSwtd =	sprintf("%7s", $totCALLSwtd);		

			if (trim($totCALLSwtd)>$max_wtd_offered) {$max_wtd_offered=trim($totCALLSwtd);}
			if (trim($totANSWERSwtd)>$max_wtd_answered) {$max_wtd_answered=trim($totANSWERSwtd);}
			if (trim($totAGENTSwtd)>$max_wtd_agents) {$max_wtd_agents=trim($totAGENTSwtd);}
			if (trim($totABANDONSwtd)>$max_wtd_abandoned) {$max_wtd_abandoned=trim($totABANDONSwtd);}
			if (trim($totABANDONSpctwtd)>$max_wtd_abandonpct) {$max_wtd_abandonpct=trim($totABANDONSpctwtd);}

			if (trim($totANSWERSavgTIMEwtd)>$max_wtd_avgtalktime) {$max_wtd_avgtalktime=trim($totANSWERSavgTIMEwtd);}
			if (trim($totANSWERSsecwtd)>$max_wtd_totaltalktime) {$max_wtd_totaltalktime=trim($totANSWERSsecwtd);}
			if (trim($totANSWERSsecwtd+($totANSWERSwtd*15))>$max_wtd_totalcalltime) {$max_wtd_totalcalltime=trim($totANSWERSsecwtd+($totANSWERSwtd*15));}

			$week=date("W", strtotime($dayEND[$d-1]));
			$year=substr($dayEND[$d-1], 0, 4);
			$wtd_graph_stats[$wa][0]=_QXZ("Week")." $week, $year";
			$wtd_graph_stats[$wa][1]=trim($totCALLSwtd);
			$wtd_graph_stats[$wa][2]=trim($totANSWERSwtd);
			$wtd_graph_stats[$wa][3]=trim($totABANDONSwtd);
			$wtd_graph_stats[$wa][4]=trim($totABANDONSpctwtd);
			$wtd_graph_stats[$wa][5]=trim($totABANDONSavgTIMEwtd);
			$wtd_graph_stats[$wa][6]=trim($totANSWERSavgspeedTIMEwtd);
			$wtd_graph_stats[$wa][7]=trim($totANSWERSavgTIMEwtd);
			$wtd_graph_stats[$wa][8]=trim($totANSWERStalkTIMEwtd);
			$wtd_graph_stats[$wa][9]=trim($totANSWERSwrapTIMEwtd);
			$wtd_graph_stats[$wa][10]=trim($totANSWERStotTIMEwtd);
			$wtd_graph_stats[$wa][17]=trim($totAGENTSwtd);

			# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
			$multigraph_text="";
			$graph_id++;
			$graph_array=array("IDR_wtd_TOTALCALLSOFFEREDdata|1|TOTAL CALLS OFFERED|integer|", "IDR_wtd_TOTALCALLSANSWEREDdata|2|TOTAL CALLS ANSWERED|integer|", "IDR_wtd_TOTALAGENTSANSWEREDdata|17|TOTAL AGENTS ANSWERED|integer|", "IDR_wtd_TOTALCALLSABANDONEDdata|3|TOTAL CALLS ABANDONED|integer|", "IDR_wtd_TOTALABANDONPERCENTdata|4|TOTAL ABANDON PERCENT|percent|", "IDR_wtd_AVGABANDONTIMEdata|11|AVG ABANDON TIME|time|", "IDR_wtd_AVGANSWERSPEEDdata|12|AVG ANSWER SPEED|time|", "IDR_wtd_AVGTALKTIMEdata|16|AVG TALK TIME|time|", "IDR_wtd_TOTALTALKTIMEdata|15|TOTAL TALK TIME|time|", "IDR_wtd_TOTALWRAPTIMEdata|13|TOTAL WRAP TIME|time|", "IDR_wtd_TOTALCALLTIMEdata|14|TOTAL CALL TIME|time|");
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
				for ($d=0; $d<count($wtd_graph_stats); $d++) {
					$labels.="\"".preg_replace('/ +/', ' ', $wtd_graph_stats[$d][0])."\",";
					$data.="\"".$wtd_graph_stats[$d][$dataset_index]."\",";
					$current_graph_total+=$wtd_graph_stats[$d][$dataset_index];
					$bgcolor=$backgroundColor[($d%count($backgroundColor))];
					$hbgcolor=$hoverBackgroundColor[($d%count($hoverBackgroundColor))];
					$hbcolor=$hoverBorderColor[($d%count($hoverBorderColor))];
					$graphConstantsA.="\"$bgcolor\",";
					$graphConstantsB.="\"$hbgcolor\",";
					$graphConstantsC.="\"$hbcolor\",";
				}	
				$graphConstantsA=substr($graphConstantsA,0,-1)."],\n";
				$graphConstantsB=substr($graphConstantsB,0,-1)."],\n";
				$graphConstantsC=substr($graphConstantsC,0,-1)."],\n";
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
			$graph_title=_QXZ("WEEKLY REPORT")." - $query_date_BEGIN "._QXZ("to")." $query_date_END";
			include("graphcanvas.inc");
			$HEADER.=$HTML_graph_head;
			$WTD_GRAPH=$graphCanvas;			

			$ASCII_text.="$MAINH\n";
			$ASCII_text.="| "._QXZ("Week to date",41,"r")." | $totCALLSwtd | $totANSWERSwtd | $totAGENTSwtd | $totABANDONSwtd | $totABANDONSpctwtd%| $totABANDONSavgTIMEwtd | $totANSWERSavgspeedTIMEwtd | $totANSWERSavgTIMEwtd | $totANSWERStalkTIMEwtd | $totANSWERSwrapTIMEwtd | $totANSWERStotTIMEwtd |";
			$CSV_text.="\""._QXZ("WTD")."\",\"$totCALLSwtd\",\"$totANSWERSwtd\",\"$totAGENTSwtd\",\"$totABANDONSwtd\",\"$totABANDONSpctwtd%\",\"$totABANDONSavgTIMEwtd\",\"$totANSWERSavgspeedTIMEwtd\",\"$totANSWERSavgTIMEwtd\",\"$totANSWERStalkTIMEwtd\",\"$totANSWERSwrapTIMEwtd\",\"$totANSWERStotTIMEwtd\"";
			for ($s=0; $s<count($status_array); $s++) {
				$ASCII_text.=" ".sprintf("%10s", ($totSTATUSESwtd[$status_array[$s][0]]+0))." |";
				$CSV_text.=",\"".sprintf("%10s", ($totSTATUSESwtd[$status_array[$s][0]]+0))."\"";
			}
			$ASCII_text.="\n";
			$CSV_text.="\n";

			$totCALLSwtd=0;
			$totANSWERSwtd=0;
			$AGENTS_wtd_array=array();
			$totANSWERSsecwtd=0;
			$totANSWERSspeedwtd=0;
			$totABANDONSwtd=0;
			$totABANDONSsecwtd=0;
			}

		if (date("d", strtotime($daySTART[$d]))!=1) 
			{
			$totAGENTSmtd=count(array_count_values($AGENTS_mtd_array));
			$totABANDONSpctmtd =	sprintf("%7.2f", (MathZDC(100*$totABANDONSmtd, $totCALLSmtd)));
			$totABANDONSavgTIMEmtd =	sprintf("%7s", date("i:s", mktime(0, 0, round(MathZDC($totABANDONSsecmtd, $totABANDONSmtd)))));
			if (round(MathZDC($totABANDONSsecmtd, $totABANDONSmtd))>$max_mtd_avgabandontime) {$max_mtd_avgabandontime=round(MathZDC($totABANDONSsecmtd, $totABANDONSmtd));}
			$mtd_graph_stats[$ma][11]=round(MathZDC($totABANDONSsecmtd, $totABANDONSmtd));
			$totANSWERSavgspeedTIMEmtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSspeedmtd, $totANSWERSmtd)))));
			$totANSWERSavgTIMEmtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSsecmtd, $totANSWERSmtd)))));
			if (round(MathZDC($totANSWERSspeedmtd, $totANSWERSmtd))>$max_mtd_avganswerspeed) {$max_mtd_avganswerspeed=round(MathZDC($totANSWERSspeedmtd, $totANSWERSmtd));}
			$mtd_graph_stats[$ma][12]=round(MathZDC($totANSWERSspeedmtd, $totANSWERSmtd));
			$mtd_graph_stats[$ma][16]=round(MathZDC($totANSWERSsecmtd, $totANSWERSmtd));
			$totANSWERStalkTIMEmtd =	sprintf("%10s", floor(MathZDC($totANSWERSsecmtd, 3600)).date(":i:s", mktime(0, 0, $totANSWERSsecmtd)));
			$totANSWERSwrapTIMEmtd =	sprintf("%10s", floor(MathZDC(($totANSWERSmtd*15), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSmtd*15))));
			if (($totANSWERSmtd*15)>$max_mtd_totalwraptime) {$max_mtd_totalwraptime=($totANSWERSmtd*15);}
			$mtd_graph_stats[$ma][13]=($totANSWERSmtd*15);
			$mtd_graph_stats[$ma][14]=($totANSWERSsecmtd+($totANSWERSmtd*15));
			$mtd_graph_stats[$ma][15]=$totANSWERSsecmtd;
			$totANSWERStotTIMEmtd =	sprintf("%10s", floor(MathZDC(($totANSWERSsecmtd+($totANSWERSmtd*15)), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSsecmtd+($totANSWERSmtd*15)))));
			$totAGENTSmtd=count(array_count_values($AGENTS_mtd_array));
			$totAGENTSmtd =	sprintf("%8s", $totAGENTSmtd);
			$totANSWERSmtd =	sprintf("%8s", $totANSWERSmtd);
			$totABANDONSmtd =	sprintf("%9s", $totABANDONSmtd);
			$totCALLSmtd =	sprintf("%7s", $totCALLSmtd);		

			if (trim($totCALLSmtd)>$max_mtd_offered) {$max_mtd_offered=trim($totCALLSmtd);}
			if (trim($totANSWERSmtd)>$max_mtd_answered) {$max_mtd_answered=trim($totANSWERSmtd);}
			if (trim($totAGENTSmtd)>$max_mtd_agents) {$max_mtd_agents=trim($totAGENTSmtd);}
			if (trim($totABANDONSmtd)>$max_mtd_abandoned) {$max_mtd_abandoned=trim($totABANDONSmtd);}
			if (trim($totABANDONSpctmtd)>$max_mtd_abandonpct) {$max_mtd_abandonpct=trim($totABANDONSpctmtd);}

			if (round(MathZDC($totANSWERSsecmtd, $totANSWERSmtd))>$max_mtd_avgtalktime)
				{$max_mtd_avgtalktime=round(MathZDC($totANSWERSsecmtd, $totANSWERSmtd));}

			if (trim($totANSWERSsecmtd)>$max_mtd_totaltalktime) {$max_mtd_totaltalktime=trim($totANSWERSsecmtd);}
			if (trim($totANSWERSsecmtd+($totANSWERSmtd*15))>$max_mtd_totalcalltime) {$max_mtd_totalcalltime=trim($totANSWERSsecmtd+($totANSWERSmtd*15));}

			$lastindex=count($dayEND)-1;
			$month=date("F", strtotime($dayEND[$lastindex]));
			#print_r($mtd_graph_stats);
			#echo "$month - $d - ".strtotime($daySTART[$d-1])."\n";
			$year=substr($dayEND[$lastindex], 0, 4);
			$mtd_graph_stats[$ma][0]="$month $year";
			$mtd_graph_stats[$ma][1]=trim($totCALLSmtd);
			$mtd_graph_stats[$ma][2]=trim($totANSWERSmtd);
			$mtd_graph_stats[$ma][3]=trim($totABANDONSmtd);
			$mtd_graph_stats[$ma][4]=trim($totABANDONSpctmtd);
			$mtd_graph_stats[$ma][5]=trim($totABANDONSavgTIMEmtd);
			$mtd_graph_stats[$ma][6]=trim($totANSWERSavgspeedTIMEmtd);
			$mtd_graph_stats[$ma][7]=trim($totANSWERSavgTIMEmtd);
			$mtd_graph_stats[$ma][8]=trim($totANSWERStalkTIMEmtd);
			$mtd_graph_stats[$ma][9]=trim($totANSWERSwrapTIMEmtd);
			$mtd_graph_stats[$ma][10]=trim($totANSWERStotTIMEmtd);
			$wtd_graph_stats[$ma][17]=trim($totAGENTSmtd);

			# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
			$multigraph_text="";
			$graph_id++;
			$graph_array=array("IDR_mtd_TOTALCALLSOFFEREDdata|1|TOTAL CALLS OFFERED|integer|", "IDR_mtd_TOTALCALLSANSWEREDdata|2|TOTAL CALLS ANSWERED|integer|", "IDR_mtd_TOTALAGENTSANSWEREDdata|17|TOTAL AGENTS ANSWERED|integer|", "IDR_mtd_TOTALCALLSABANDONEDdata|3|TOTAL CALLS ABANDONED|integer|", "IDR_mtd_TOTALABANDONPERCENTdata|4|TOTAL ABANDON PERCENT|percent|", "IDR_mtd_AVGABANDONTIMEdata|11|AVG ABANDON TIME|time|", "IDR_mtd_AVGANSWERSPEEDdata|12|AVG ANSWER SPEED|time|", "IDR_mtd_AVGTALKTIMEdata|16|AVG TALK TIME|time|", "IDR_mtd_TOTALTALKTIMEdata|15|TOTAL TALK TIME|time|", "IDR_mtd_TOTALWRAPTIMEdata|13|TOTAL WRAP TIME|time|", "IDR_mtd_TOTALCALLTIMEdata|14|TOTAL CALL TIME|time|");
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
				for ($d=0; $d<count($mtd_graph_stats); $d++) {
					$labels.="\"".preg_replace('/ +/', ' ', $mtd_graph_stats[$d][0])."\",";
					$data.="\"".$mtd_graph_stats[$d][$dataset_index]."\",";
					$current_graph_total+=$mtd_graph_stats[$d][$dataset_index];
					$bgcolor=$backgroundColor[($d%count($backgroundColor))];
					$hbgcolor=$hoverBackgroundColor[($d%count($hoverBackgroundColor))];
					$hbcolor=$hoverBorderColor[($d%count($hoverBorderColor))];
					$graphConstantsA.="\"$bgcolor\",";
					$graphConstantsB.="\"$hbgcolor\",";
					$graphConstantsC.="\"$hbcolor\",";
				}	
				$graphConstantsA=substr($graphConstantsA,0,-1)."],\n";
				$graphConstantsB=substr($graphConstantsB,0,-1)."],\n";
				$graphConstantsC=substr($graphConstantsC,0,-1)."],\n";
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
			$graph_title=_QXZ("MONTHLY REPORT")." - $query_date_BEGIN "._QXZ("to")." $query_date_END";
			include("graphcanvas.inc");
			$HEADER.=$HTML_graph_head;
			$MTD_GRAPH=$graphCanvas;			

			$ASCII_text.="$MAINH\n";
			$ASCII_text.="| "._QXZ("Month to date",41,"r")." | $totCALLSmtd | $totANSWERSmtd | $totAGENTSmtd | $totABANDONSmtd | $totABANDONSpctmtd%| $totABANDONSavgTIMEmtd | $totANSWERSavgspeedTIMEmtd | $totANSWERSavgTIMEmtd | $totANSWERStalkTIMEmtd | $totANSWERSwrapTIMEmtd | $totANSWERStotTIMEmtd |";
			$CSV_text.="\""._QXZ("Month to date")."\",\"$totCALLSmtd\",\"$totANSWERSmtd\",\"$totAGENTSmtd\",\"$totABANDONSmtd\",\"$totABANDONSpctmtd%\",\"$totABANDONSavgTIMEmtd\",\"$totANSWERSavgspeedTIMEmtd\",\"$totANSWERSavgTIMEmtd\",\"$totANSWERStalkTIMEmtd\",\"$totANSWERSwrapTIMEmtd\",\"$totANSWERStotTIMEmtd\"";
			for ($s=0; $s<count($status_array); $s++) {
				$ASCII_text.=" ".sprintf("%10s", ($totSTATUSESmtd[$status_array[$s][0]]+0))." |";
				$CSV_text.=",\"".sprintf("%10s", ($totSTATUSESmtd[$status_array[$s][0]]+0))."\"";
			}
			$ASCII_text.="\n";
			$CSV_text.="\n";
			
			$totCALLSmtd=0;
			$totANSWERSmtd=0;
			$AGENTS_mtd_array=array();
			$totANSWERSsecmtd=0;
			$totANSWERSspeedmtd=0;
			$totABANDONSmtd=0;
			$totABANDONSsecmtd=0;

	#		if (date("m", strtotime($daySTART[$d]))==1 || date("m", strtotime($daySTART[$d]))==4 || date("m", strtotime($daySTART[$d]))==7 || date("m", strtotime($daySTART[$d]))==10) # Quarterly line
	#			{
			$totAGENTSqtd=count(array_count_values($AGENTS_qtd_array));
			$totABANDONSpctqtd =	sprintf("%7.2f", (MathZDC(100*$totABANDONSqtd, $totCALLSqtd)));
			$totABANDONSavgTIMEqtd =	sprintf("%7s", date("i:s", mktime(0, 0, round(MathZDC($totABANDONSsecqtd, $totABANDONSqtd)))));
			if (round(MathZDC($totABANDONSsecqtd, $totABANDONSqtd))>$max_qtd_avgabandontime) {$max_qtd_avgabandontime=round(MathZDC($totABANDONSsecqtd, $totABANDONSqtd));}
			$qtd_graph_stats[$qa][11]=round(MathZDC($totABANDONSsecqtd, $totABANDONSqtd));
			$totANSWERSavgspeedTIMEqtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSspeedqtd, $totANSWERSqtd)))));
			$totANSWERSavgTIMEqtd =	sprintf("%6s", date("i:s", mktime(0, 0, round(MathZDC($totANSWERSsecqtd, $totANSWERSqtd)))));
			if (round(MathZDC($totANSWERSspeedqtd, $totANSWERSqtd))>$max_qtd_avganswerspeed) {$max_qtd_avganswerspeed=round(MathZDC($totANSWERSspeedqtd, $totANSWERSqtd));}
			$qtd_graph_stats[$qa][12]=round(MathZDC($totANSWERSspeedqtd, $totANSWERSqtd));
			$qtd_graph_stats[$qa][16]=round(MathZDC($totANSWERSsecqtd, $totANSWERSqtd));
			$totANSWERStalkTIMEqtd =	sprintf("%10s", floor(MathZDC($totANSWERSsecqtd, 3600)).date(":i:s", mktime(0, 0, $totANSWERSsecqtd)));
			$totANSWERSwrapTIMEqtd =	sprintf("%10s", floor(MathZDC(($totANSWERSqtd*15), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSqtd*15))));
			if (($totANSWERSqtd*15)>$max_qtd_totalwraptime) {$max_qtd_totalwraptime=($totANSWERSqtd*15);}
			$qtd_graph_stats[$qa][13]=($totANSWERSqtd*15);
			$qtd_graph_stats[$qa][14]=($totANSWERSsecqtd+($totANSWERSqtd*15));
			$qtd_graph_stats[$qa][15]=$totANSWERSsecqtd;
			$totANSWERStotTIMEqtd =	sprintf("%10s", floor(MathZDC(($totANSWERSsecqtd+($totANSWERSqtd*15)), 3600)).date(":i:s", mktime(0, 0, ($totANSWERSsecqtd+($totANSWERSqtd*15)))));
			$totAGENTSqtd=count(array_count_values($AGENTS_qtd_array));
			$totAGENTSqtd =	sprintf("%8s", $totAGENTSqtd);
			$totANSWERSqtd =	sprintf("%8s", $totANSWERSqtd);
			$totABANDONSqtd =	sprintf("%9s", $totABANDONSqtd);
			$totCALLSqtd =	sprintf("%7s", $totCALLSqtd);		

			if (trim($totCALLSqtd)>$max_qtd_offered) {$max_qtd_offered=trim($totCALLSqtd);}
			if (trim($totANSWERSqtd)>$max_qtd_answered) {$max_qtd_answered=trim($totANSWERSqtd);}
			if (trim($totAGENTSqtd)>$max_qtd_agents) {$max_qtd_agents=trim($totAGENTSqtd);}
			if (trim($totABANDONSqtd)>$max_qtd_abandoned) {$max_qtd_abandoned=trim($totABANDONSqtd);}
			if (trim($totABANDONSpctqtd)>$max_qtd_abandonpct) {$max_qtd_abandonpct=trim($totABANDONSpctqtd);}

			if ( (round(MathZDC($totANSWERSsecqtd, $totANSWERSqtd))>$max_qtd_avgtalktime) )
				{$max_qtd_avgtalktime=round(MathZDC($totANSWERSsecqtd, $totANSWERSqtd));}

			if (trim($totANSWERSsecqtd)>$max_qtd_totaltalktime) {$max_qtd_totaltalktime=trim($totANSWERSsecqtd);}
			if (trim($totANSWERSsecqtd+($totANSWERSqtd*15))>$max_qtd_totalcalltime) {$max_qtd_totalcalltime=trim($totANSWERSsecqtd+($totANSWERSqtd*15));}

			$lastindex=count($dayEND)-1;
			$month=date("m", strtotime($dayEND[$lastindex])-3600);  ## ACCOUNT FOR DST IN LABELING
			$year=substr($dayEND[$lastindex], 0, 4);

			$qtr1=array("01","02","03");
			$qtr2=array("04","05","06");
			$qtr3=array("07","08","09");
			$qtr4=array("10","11","12");
			if(in_array($month,$qtr1)) {
				$qtr=_QXZ("1st");
			} else if(in_array($month,$qtr2)) {
				$qtr=_QXZ("2nd");
			}  else if(in_array($month,$qtr3)) {
				$qtr=_QXZ("3rd");
			}  else if(in_array($month,$qtr4)) {
				$qtr=_QXZ("4th");
			}

			$qtd_graph_stats[$qa][0]="$qtr "._QXZ("quarter").", $year";
			$qtd_graph_stats[$qa][1]=trim($totCALLSqtd);
			$qtd_graph_stats[$qa][2]=trim($totANSWERSqtd);
			$qtd_graph_stats[$qa][3]=trim($totABANDONSqtd);
			$qtd_graph_stats[$qa][4]=trim($totABANDONSpctqtd);
			$qtd_graph_stats[$qa][5]=trim($totABANDONSavgTIMEqtd);
			$qtd_graph_stats[$qa][6]=trim($totANSWERSavgspeedTIMEqtd);
			$qtd_graph_stats[$qa][7]=trim($totANSWERSavgTIMEqtd);
			$qtd_graph_stats[$qa][8]=trim($totANSWERStalkTIMEqtd);
			$qtd_graph_stats[$qa][9]=trim($totANSWERSwrapTIMEqtd);
			$qtd_graph_stats[$qa][10]=trim($totANSWERStotTIMEqtd);
			$qtd_graph_stats[$qa][17]=trim($totAGENTSqtd);

			# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
			$multigraph_text="";
			$graph_id++;
			$graph_array=array("IDR_qtd_TOTALCALLSOFFEREDdata|1|TOTAL CALLS OFFERED|integer|", "IDR_qtd_TOTALCALLSANSWEREDdata|2|TOTAL CALLS ANSWERED|integer|", "IDR_qtd_TOTALAGENTSANSWEREDdata|17|TOTAL AGENTS ANSWERED|integer|", "IDR_qtd_TOTALCALLSABANDONEDdata|3|TOTAL CALLS ABANDONED|integer|", "IDR_qtd_TOTALABANDONPERCENTdata|4|TOTAL ABANDON PERCENT|percent|", "IDR_qtd_AVGABANDONTIMEdata|11|AVG ABANDON TIME|time|", "IDR_qtd_AVGANSWERSPEEDdata|12|AVG ANSWER SPEED|time|", "IDR_qtd_AVGTALKTIMEdata|16|AVG TALK TIME|time|", "IDR_qtd_TOTALTALKTIMEdata|15|TOTAL TALK TIME|time|", "IDR_qtd_TOTALWRAPTIMEdata|13|TOTAL WRAP TIME|time|", "IDR_qtd_TOTALCALLTIMEdata|14|TOTAL CALL TIME|time|");
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
				for ($d=0; $d<count($qtd_graph_stats); $d++) {
					$labels.="\"".preg_replace('/ +/', ' ', $qtd_graph_stats[$d][0])."\",";
					$data.="\"".$qtd_graph_stats[$d][$dataset_index]."\",";
					$current_graph_total+=$qtd_graph_stats[$d][$dataset_index];
					$bgcolor=$backgroundColor[($d%count($backgroundColor))];
					$hbgcolor=$hoverBackgroundColor[($d%count($hoverBackgroundColor))];
					$hbcolor=$hoverBorderColor[($d%count($hoverBorderColor))];
					$graphConstantsA.="\"$bgcolor\",";
					$graphConstantsB.="\"$hbgcolor\",";
					$graphConstantsC.="\"$hbcolor\",";
				}	
				$graphConstantsA=substr($graphConstantsA,0,-1)."],\n";
				$graphConstantsB=substr($graphConstantsB,0,-1)."],\n";
				$graphConstantsC=substr($graphConstantsC,0,-1)."],\n";
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
			$graph_title=_QXZ("QUARTERLY REPORT")." - $query_date_BEGIN "._QXZ("to")." $query_date_END";
			include("graphcanvas.inc");
			$HEADER.=$HTML_graph_head;
			$QTD_GRAPH=$graphCanvas;			


			$ASCII_text.="$MAINH\n";
			$ASCII_text.="| "._QXZ("Quarter to date",41,"r")." | $totCALLSqtd | $totANSWERSqtd | $totAGENTSqtd | $totABANDONSqtd | $totABANDONSpctqtd%| $totABANDONSavgTIMEqtd | $totANSWERSavgspeedTIMEqtd | $totANSWERSavgTIMEqtd | $totANSWERStalkTIMEqtd | $totANSWERSwrapTIMEqtd | $totANSWERStotTIMEqtd |";
			$CSV_text.="\""._QXZ("Quarter to date")."\",\"$totCALLSqtd\",\"$totANSWERSqtd\",\"$totAGENTSqtd\",\"$totABANDONSqtd\",\"$totABANDONSpctqtd%\",\"$totABANDONSavgTIMEqtd\",\"$totANSWERSavgspeedTIMEqtd\",\"$totANSWERSavgTIMEqtd\",\"$totANSWERStalkTIMEqtd\",\"$totANSWERSwrapTIMEqtd\",\"$totANSWERStotTIMEqtd\"";
			for ($s=0; $s<count($status_array); $s++) {
				$ASCII_text.=" ".sprintf("%10s", ($totSTATUSESqtd[$status_array[$s][0]]+0))." |";
				$CSV_text.=",\"".sprintf("%10s", ($totSTATUSESqtd[$status_array[$s][0]]+0))."\"";
			}
			$ASCII_text.="\n";
			$CSV_text.="\n";

			$totCALLSqtd=0;
			$totANSWERSqtd=0;
			$AGENTS_qtd_array=array();
			$totANSWERSsecqtd=0;
			$totANSWERSspeedqtd=0;
			$totABANDONSqtd=0;
			$totABANDONSsecqtd=0;
	#			}
		}

			# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
			$multigraph_text="";
			$graph_id++;
			$graph_array=array("IDR_TOTALCALLSOFFEREDdata|1|TOTAL CALLS OFFERED|integer|", "IDR_TOTALCALLSANSWEREDdata|2|TOTAL CALLS ANSWERED|integer|", "IDR_TOTALAGENTSANSWEREDdata|17|TOTAL AGENTS ANSWERED|integer|", "IDR_TOTALCALLSABANDONEDdata|3|TOTAL CALLS ABANDONED|integer|", "IDR_TOTALABANDONPERCENTdata|4|TOTAL ABANDON PERCENT|percent|", "IDR_AVGABANDONTIMEdata|11|AVG ABANDON TIME|time|", "IDR_AVGANSWERSPEEDdata|12|AVG ANSWER SPEED|time|", "IDR_AVGTALKTIMEdata|16|AVG TALK TIME|time|", "IDR_TOTALTALKTIMEdata|15|TOTAL TALK TIME|time|", "IDR_TOTALWRAPTIMEdata|13|TOTAL WRAP TIME|time|", "IDR_TOTALCALLTIMEdata|14|TOTAL CALL TIME|time|");
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
				$graphConstantsA=substr($graphConstantsA,0,-1)."],\n";
				$graphConstantsB=substr($graphConstantsB,0,-1)."],\n";
				$graphConstantsC=substr($graphConstantsC,0,-1)."],\n";
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
			$graph_title=_QXZ("DAILY REPORT")." - $query_date_BEGIN "._QXZ("to")." $query_date_END";
			include("graphcanvas.inc");
			$HEADER.=$HTML_graph_head;
			$GRAPH=$graphCanvas;			

			$FtotAGENTS =	sprintf("%8s", $FtotAGENTS);

	$ASCII_text.="$MAINH\n";
	$ASCII_text.="| "._QXZ("TOTALS",41,"r")." | $FtotCALLS | $FtotANSWERS | $FtotAGENTS | $FtotABANDONS | $FtotABANDONSpct%| $FtotABANDONSavgTIME | $FtotANSWERSavgspeedTIME | $FtotANSWERSavgTIME | $FtotANSWERStalkTIME | $FtotANSWERSwrapTIME | $FtotANSWERStotTIME |";
	$CSV_text.="\"TOTALS\",\"$FtotCALLS\",\"$FtotANSWERS\",\"$FtotAGENTS\",\"$FtotABANDONS\",\"$FtotABANDONSpct%\",\"$FtotABANDONSavgTIME\",\"$FtotANSWERSavgspeedTIME\",\"$FtotANSWERSavgTIME\",\"$FtotANSWERStalkTIME\",\"$FtotANSWERSwrapTIME\",\"$FtotANSWERStotTIME\"";
	for ($s=0; $s<count($status_array); $s++) {
		$ASCII_text.=" ".sprintf("%10s", ($totSTATUSES[$status_array[$s][0]]+0))." |";
		$CSV_text.=",\"".sprintf("%10s", ($totSTATUSES[$status_array[$s][0]]+0))."\"";
	}
	$ASCII_text.="\n";
	$CSV_text.="\n\n\n";
	$ASCII_text.="$MAINH\n\n\n";

	if ($show_disposition_statuses) 
		{
		$total_count=0;
		$ASCII_text.="+--------+----------------------+------------+\n";
		$ASCII_text.="| "._QXZ("STATUS",6)." | "._QXZ("DESCRIPTION",20)." | "._QXZ("CALLS",10)." |\n";
		$ASCII_text.="+--------+----------------------+------------+\n";
		$CSV_text.="\""._QXZ("STATUS")."\",\""._QXZ("DISPOSITION")."\",\""._QXZ("CALLS")."\"\n";
		for ($s=0; $s<count($status_array); $s++) {
			$status_code=$status_array[$s][0];
			$status_name=$status_array[$s][1];
			$status_count=$totSTATUSES[$status_array[$s][0]]+0;
			#$MAIN.=" ".sprintf("%8s", ($totSTATUSES[$status_array[$s][0]]+0))." |";
			#$CSV_text.=",\"".sprintf("%8s", ($totSTATUSES[$status_array[$s][0]]+0))."\"";
			$ASCII_text.="| ".sprintf("%-6s", substr($status_code,0,6))." | ".sprintf("%-20s", substr($status_name,0,20))." | ".sprintf("%10s", $status_count)." |\n";
			$CSV_text.="\"$status_code\",\"$status_name\",\"$status_count\"\n";
			$total_count+=$status_count;
		}
		$ASCII_text.="+--------+----------------------+------------+\n";
		$ASCII_text.="| "._QXZ("TOTAL:",29)." | ".sprintf("%10s", $total_count)." |\n";
		$ASCII_text.="+-------------------------------+------------+\n";
		$CSV_text.="\"\",\""._QXZ("TOTAL").":\",\"$total_count\"\n";
		}

	## FORMAT OUTPUT ##
	$i=0;
	$hi_hour_count=0;
	$hi_hold_count=0;

	while ($i < $TOTintervals)
		{
		$qrtCALLSavg[$i] = MathZDC($qrtCALLSsec[$i], $qrtCALLS[$i]);
		$qrtDROPSavg[$i] = MathZDC($qrtDROPSsec[$i], $qrtDROPS[$i]);
		$qrtQUEUEavg[$i] = MathZDC($qrtQUEUEsec[$i], $qrtQUEUE[$i]);

		if ($qrtCALLS[$i] > $hi_hour_count) {$hi_hour_count = $qrtCALLS[$i];}
		if ($qrtQUEUEavg[$i] > $hi_hold_count) {$hi_hold_count = $qrtQUEUEavg[$i];}

		$qrtQUEUEavg[$i] = round($qrtQUEUEavg[$i], 0);
		if (strlen($qrtQUEUEavg[$i])<1) {$qrtQUEUEavg[$i]=0;}
		$qrtQUEUEmax[$i] = round($qrtQUEUEmax[$i], 0);
		if (strlen($qrtQUEUEmax[$i])<1) {$qrtQUEUEmax[$i]=0;}

		$i++;
		}

	$JS_onload.="}\n";
	if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
	$JS_text.="</script>\n";

	if ($report_display_type=="HTML") 
		{
		$MAIN.=$GRAPH.$WTD_GRAPH.$MTD_GRAPH.$QTD_GRAPH.$JS_text;
		}
	else
		{
		$MAIN.=$ASCII_text;
		}

	$hour_multiplier = MathZDC(20, $hi_hour_count);
	$hold_multiplier = MathZDC(20, $hi_hold_count);


	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
	$MAIN.="</PRE>\n";
	$MAIN.="</TD></TR></TABLE>\n";
	$MAIN.="</FORM>\n\n";
	$MAIN.="</BODY></HTML>\n";

	if ($file_download > 0)
		{
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "Inbound_Daily_Report_$US$FILE_TIME.csv";
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
		echo "$HEADER";
		require("admin_header.php");
		echo "$MAIN";
		}
	}

if ($db_source == 'S')
	{
	mysqli_close($link);
	$use_slave_server=0;
	$db_source = 'M';
	require("dbconnect_mysqli.php");
	if ($file_download < 1) 
		{echo "<!-- Switching back to Master server to log report run time $VARDB_server $db_source -->\n";}
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





function HourDifference($time_BEGIN, $time_END) {
	global $hpd;
	$TB_array =	explode(':',$time_BEGIN);
	$TE_array =	explode(':',$time_END);
	$TBoffset=0;
	$TEoffset=0;

	if ($TB_array[0]>24) {
		while($TB_array[0]>24) {
			$TB_array[0]--;
			$TBoffset+=3600;
		}
		$time_BEGIN="$TB_array[0]:$TB_array[1]:$TB_array[2]";
	}
	if ($TE_array[0]>24) {
		while($TE_array[0]>24) {
			$TE_array[0]--;
			$TEoffset+=3600;
		}
		$time_END="$TE_array[0]:$TE_array[1]:$TE_array[2]";
	}
	$time1 = strtotime($time_BEGIN)+$TBoffset;
	$time2 = strtotime($time_END)+1+$TEoffset;
	$hpd = ceil(MathZDC(($time2 - $time1), 3600));
	if ($hpd<0) {$hpd+=24;}
}

function RecalculateEpochs($query_date_BEGIN, $query_date_END) {
	global $query_date_BEGIN, $query_date_END, $SQepoch, $EQepoch, $SQepochDAY, $SQsec, $EQsec;
	$SQdate_ARY =	explode(' ',$query_date_BEGIN);
	$SQday_ARY =	explode('-',$SQdate_ARY[0]);
	$SQtime_ARY =	explode(':',$SQdate_ARY[1]);
	#$EQdate_ARY =	explode(' ',$query_date_END);
	#$EQday_ARY =	explode('-',$EQdate_ARY[0]);
	#$EQtime_ARY =	explode(':',$EQdate_ARY[1]);

	$SQepochDAY = mktime(0, 0, 0, $SQday_ARY[1], $SQday_ARY[2], $SQday_ARY[0]);
	$SQepoch = mktime($SQtime_ARY[0], $SQtime_ARY[1], $SQtime_ARY[2], $SQday_ARY[1], $SQday_ARY[2], $SQday_ARY[0]);
	#$EQepoch = mktime($EQtime_ARY[0], $EQtime_ARY[1], $EQtime_ARY[2], $EQday_ARY[1], $EQday_ARY[2], $EQday_ARY[0]);

	$SQsec = ( ($SQtime_ARY[0] * 3600) + ($SQtime_ARY[1] * 60) + ($SQtime_ARY[2] * 1) );
	#$EQsec = ( ($EQtime_ARY[0] * 3600) + ($EQtime_ARY[1] * 60) + ($EQtime_ARY[2] * 1) );
}

function RecalculateHPD($query_date_BEGIN, $query_date_END, $time_BEGIN, $time_END) {
	global $hpd, $query_date_BEGIN, $query_date_END, $time_BEGIN, $time_END;
	global $DURATIONday, $SQepoch, $EQepoch, $SQepochDAY, $SQsec, $EQsec;

	$TB_array =	explode(':',$time_BEGIN);
	$TE_array =	explode(':',$time_END);
	$TBoffset=0;
	$TEoffset=0;

	if ($TB_array[0]>24) {
		while($TB_array[0]>24) {
			$TB_array[0]--;
			$TBoffset+=3600;
		}
		$time_BEGIN="$TB_array[0]:$TB_array[1]:$TB_array[2]";
	}
	if ($TE_array[0]>24) {
		while($TE_array[0]>24) {
			$TE_array[0]--;
			$TEoffset+=3600;
		}
		$time_END="$TE_array[0]:$TE_array[1]:$TE_array[2]";
	}
	$time1 = strtotime($time_BEGIN)+$TBoffset;
	$time2 = strtotime($time_END)+1+$TEoffset;
	$hpd = ceil(MathZDC(($time2 - $time1), 3600));
	if ($hpd<0) {$hpd+=24;}

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

	if (!$DURATIONday) 
		{
		$DURATIONsec = ($EQepoch - $SQepoch);
		$DURATIONday = intval( MathZDC($DURATIONsec, 86400) + 1 );

		if ( ($EQsec < $SQsec) and ($DURATIONday < 1) )
			{
			$EQepoch = ($SQepochDAY + ($EQsec + 86400) );
			$query_date_END = date("Y-m-d H:i:s", $EQepoch);
			$DURATIONday++;
			}
		}
}
?>
