<?php 
# AST_performance_comparison_report.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com    LICENSE: AGPLv2
#
# CHANGES
#
# 140408-1813 - First build
# 140414-1712 - Sales count bug fix
# 140418-1830 - Call count bug fix
# 141113-2058 - Finalized adding QXZ translation to all admin files
# 141128-0905 - Code cleanup for QXZ functions
# 141230-0939 - Added code for on-the-fly language translations display
# 150516-1314 - Fixed Javascript element problem, Issue #857
# 151227-1746 - Added option for searching archived data
# 160121-2215 - Added report title header, default report format, cleaned up formatting
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170409-1542 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180507-2315 - Added new help display
# 191013-0900 - Fixes for PHP7
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
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["show_percentages"]))			{$show_percentages=$_GET["show_percentages"];}
	elseif (isset($_POST["show_percentages"]))	{$show_percentages=$_POST["show_percentages"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Performance Comparison Report';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
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

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($user_group)) {$user_group = array();}
if (!isset($users)) {$users = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$today=$query_date;
$date_ary=explode("-", $today);
$yesterday=date("Y-m-d", mktime(0, 0, 0, $date_ary[1], $date_ary[2]-1, $date_ary[0]));
$twodaysago=date("Y-m-d", mktime(0, 0, 0, $date_ary[1], $date_ary[2]-2, $date_ary[0]));
$threedaysago=date("Y-m-d", mktime(0, 0, 0, $date_ary[1], $date_ary[2]-3, $date_ary[0]));
$fivedaysago=date("Y-m-d", mktime(0, 0, 0, $date_ary[1], $date_ary[2]-5, $date_ary[0]));
$tendaysago=date("Y-m-d", mktime(0, 0, 0, $date_ary[1], $date_ary[2]-10, $date_ary[0]));
$thirtydaysago=date("Y-m-d", mktime(0, 0, 0, $date_ary[1], $date_ary[2]-30, $date_ary[0]));


$rpt_date_array=array();
$rpt_subtitle_array=array();
array_push($rpt_date_array, "$today", "$yesterday", "$twodaysago", "$threedaysago", "$fivedaysago", "$tendaysago", "$thirtydaysago");
array_push($rpt_subtitle_array, _QXZ("TODAY"), _QXZ("YESTERDAY"), _QXZ("2 DAYS AGO"), _QXZ("3 DAYS AGO"), _QXZ("5 DAYS AGO"), _QXZ("10 DAYS AGO"), _QXZ("30 DAYS AGO"));


$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
	}

$i=0;
$users_string='|';
$users_ct = count($users);
while($i < $users_ct)
	{
	$users_string .= "$users[$i]|";
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
#for ($i=0; $i<count($user_group); $i++)
#	{
#	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1; $user_group="";}
#	}

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

$stmt="select user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
$user_list=array();
$user_names=array();
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_list[$i]=$row[0];
	$user_names[$i]=$row[1];
	if ($all_users) {$user_list[$i]=$row[0];}
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
	{$group_SQL = "";}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_SQL = "and campaign_id IN($group_SQL)";
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
	$user_group_agent_log_SQL = "and ".$vicidial_agent_log_table.".user_group IN($user_group_SQL)";
	$user_group_SQL = "and vicidial_users.user_group IN($user_group_SQL)";
	}

$i=0;
$user_string='|';
$user_ct = count($users);
while($i < $user_ct)
	{
	$user_string .= "$users[$i]|";
	$user_SQL .= "'$users[$i]',";
	$userQS .= "&users[]=$users[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_string) ) or ($user_ct < 1) )
	{$user_SQL = "";}
else
	{
	$user_SQL = preg_replace('/,$/i', '',$user_SQL);
	$user_agent_log_SQL = "and ".$vicidial_agent_log_table.".user IN($user_SQL)";
	$user_SQL = "and vicidial_users.user IN($user_SQL)";
	}

if ($DB) {$HTML_text.="$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date$groupQS$user_groupQS&shift=$shift&DB=$DB&show_percentages=$show_percentages&search_archived_data=$search_archived_data&report_display_type=$report_display_type";

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

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
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

#	require("admin_header.php");

$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";
$HTML_text.="<b>"._QXZ("$report_name")."</b> $NWB#performance_comparison_report$NWE\n";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLSPACING=3 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Date").":<BR>";
$HTML_text.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="  {\n";
$HTML_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="  }\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'query_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
/*
$HTML_text.="<BR> to <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'end_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
*/

$HTML_text.="</TD><TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
	{
	if (preg_match("/$groups[$o]\|/i",$group_string)) {$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	else {$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD><TD VALIGN=TOP>"._QXZ("User Groups").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=user_group[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/$user_groups[$o]\|/i",$user_group_string)) {$HTML_text.="<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	else {$HTML_text.="<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD><TD VALIGN=TOP>"._QXZ("Users").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=users[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$users_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USERS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USERS")." --</option>\n";}
$o=0;
while ($users_to_print > $o)
	{
	if  (preg_match("/$user_list[$o]\|/i",$users_string)) {$HTML_text.="<option selected value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	else {$HTML_text.="<option value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD><TD VALIGN=TOP>"._QXZ("Shift").":<BR>";
$HTML_text.="<SELECT SIZE=1 NAME=shift>\n";
$HTML_text.="<option selected value=\"$shift\">"._QXZ("$shift")."</option>\n";
$HTML_text.="<option value=\"\">--</option>\n";
$HTML_text.="<option value=\"AM\">"._QXZ("AM")."</option>\n";
$HTML_text.="<option value=\"PM\">"._QXZ("PM")."</option>\n";
$HTML_text.="<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD><TD VALIGN=TOP>";
$HTML_text.=_QXZ("Display as").":<BR>";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n";

if ($archives_available=="Y") 
	{
	$HTML_text.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

$HTML_text.="<BR><BR><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>$NWB#agent_performance_detail$NWE\n";
$HTML_text.="</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";

$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
$HTML_text.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$HTML_text.="</FONT>\n";
$HTML_text.="</TD></TR></TABLE>";

$HTML_text.="</FORM>\n";


$HTML_text.="<PRE><FONT SIZE=2>";


if (!$group)
	{
#	$HTML_text.="\n";
	$HTML_text.=_QXZ("PLEASE SELECT A CAMPAIGN AND DATE-TIME ABOVE AND CLICK SUBMIT")."\n";
	$HTML_text.=" "._QXZ("NOTE: stats taken from shift specified")."\n";
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


	#########################



	$HTML_text.=_QXZ("Agent Performance Comparison",50)." $NOW_TIME\n";
	$HTML_text.=""._QXZ("Starting date",15,"r").": $query_date                         <a href=\"$LINKbase&stage=$stage&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n\n";

	$CSV_header1.="\""._QXZ("Agent Performance Comparison",50)." $NOW_TIME\"\n";
	$CSV_header1.="\""._QXZ("Starting date",15,"r").": $query_date\"\n\n";



	# Get full list of agents for the past 30 days
	$initial_user_stmt="select distinct ".$vicidial_agent_log_table.".user, full_name from ".$vicidial_agent_log_table.", vicidial_users where event_time <= '$query_date $time_END' and event_time >= '$thirtydaysago $time_BEGIN' $group_SQL $user_group_SQL $user_SQL and ".$vicidial_agent_log_table.".user=vicidial_users.user order by full_name asc";
	if ($DB) {echo $initial_user_stmt;}
	$initial_user_rslt=mysql_to_mysqli($initial_user_stmt, $link);
	$agent_performance_array=array();
	while ($user_row=mysqli_fetch_array($initial_user_rslt)) 
		{
		$agent_performance_array[$user_row[0]][0]=$user_row[1];
		}

	# Get full list of sale dispositions for selected campaigns/system
	$sale_stmt="select distinct status from vicidial_campaign_statuses where sale='Y' $group_SQL UNION select distinct status from vicidial_statuses where sale='Y' order by status asc";
	$sale_rslt=mysql_to_mysqli($sale_stmt, $link);
	$sale_status_str="|";
	while ($sale_row=mysqli_fetch_row($sale_rslt)) 
		{
		$sale_status_str.="$sale_row[0]|";
		}

	$CSV_header1.='"","",';
	$CSV_header2.='"'._QXZ("USER NAME").'","'._QXZ("ID").'",';

	$ASCII_header1.="|                            |";
	$ASCII_header2.="+-----------------+----------+";
	$ASCII_header3.="| <a href=\"$LINKbase\">"._QXZ("USER NAME",15)."</a> | <a href=\"$LINKbase&stage=ID\">"._QXZ("ID",8)."</a> |";


	$master_graph_array=array();
	for ($q=0; $q<count($rpt_date_array); $q++) 
		{
		$rpt_subtitle=$rpt_subtitle_array[$q];
		$rpt_date=$rpt_date_array[$q];
		$rpt_date_numeric=preg_replace('/[^0-9]/', '', $rpt_date_array[$q]);
		$array_offset=($q*5)+1;
		$master_graph_array[$q]=array("APC_CALLSdata$rpt_date_numeric|1|CALLS|integer|", "APC_SALESdata$rpt_date_numeric|2|SALES|integer|", "APC_SALECONVdata$rpt_date_numeric|3|SALE CONV %|percent|", "APC_SPHdata$rpt_date_numeric|4|SALES PER HOUR|decimal|", "APC_TIMEdata$rpt_date_numeric|5|TIME|time|");
		}
	
	$TOTALS_array=array();
	$graph_TOTALS_array=array();
	$TOTALS_array[0]=_QXZ("TOTALS");
	$graph_TOTALS_array[0]=_QXZ("TOTALS");

	$GRAPH="";
	for ($y=0; $y<count($rpt_date_array); $y++) 
		{
		$rpt_subtitle=$rpt_subtitle_array[$y];
		$rpt_date=$rpt_date_array[$y];
		$rpt_date_numeric=preg_replace('/[^0-9]/', '', $rpt_date_array[$y]);
		$array_offset=($y*5)+1;

		$CSV_header1.="\"$rpt_subtitle\",\"\",\"\",\"\",\"\",";
		$CSV_header2.='"'._QXZ("CALLS").'","'._QXZ("SALES").'","'._QXZ("CONVERSION RATE TO CALLS").'","'._QXZ("SALES PER HOUR").'","'._QXZ("TIME").'",';
		$ASCII_header1.="| ".sprintf("%-54s", $rpt_subtitle)." |";
		$ASCII_header2.="+-------+-------+-------------+--------------+-----------+";
		$ASCII_header3.="| "._QXZ("CALLS",5)." | "._QXZ("SALES",5)." | "._QXZ("SALE CONV",9)." % | "._QXZ("SALES PER HR",12)." | "._QXZ("TIME",9)." |";

		##########

		$graph_stats=array();
		$max_stats=array();
		for ($k=1; $k<6; $k++) 
			{
			$max_stats[$k]=0;
			}


		#########

		$stmt="select count(*) as calls,sum(talk_sec) as talk,full_name,vicidial_users.user,sum(pause_sec),sum(wait_sec),sum(dispo_sec),status,sum(dead_sec), vicidial_users.user_group, sub_status from vicidial_users,".$vicidial_agent_log_table." where event_time <= '$query_date $time_END' and event_time >= '$rpt_date $time_BEGIN' and vicidial_users.user=".$vicidial_agent_log_table.".user and pause_sec<65000 and wait_sec<65000 and talk_sec<65000 and dispo_sec<65000 $group_SQL $user_group_SQL $user_SQL group by user,full_name,user_group,status, sub_status order by full_name,user,status, sub_status desc limit 500000;";
		if ($DB) {print "<!-- $stmt //-->\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$rows_to_print = mysqli_num_rows($rslt);
		$i=0;

		while ($i < $rows_to_print)
			{
			$row=mysqli_fetch_row($rslt);
			if ($row[7]!="") {$agent_performance_array[$row[3]][$array_offset]+=$row[0];} # CALLS FOR TIME RANGE, MUST HAVE DISPO TO COUNT AS A CALL
			if(preg_match("/\|$row[7]\|/", $sale_status_str)) 
				{
				$agent_performance_array[$row[3]][($array_offset+1)]+=$row[0]; # SALES FOR TIME RANGE
				}
			# if ($row[10]=="BREAK") {$row[4]=0;}
			$agent_performance_array[$row[3]][($array_offset+4)]+=($row[4]+$row[1]+$row[6]+$row[5]); # TIME - pause, talk, disp, wait
			$i++;
			}

		$j=0;
#		while (list($key, $val)=each($agent_performance_array)) { # CYCLE THROUGH EACH USER
		foreach ($agent_performance_array as $key => $val) {
			for ($k=0; $k<2; $k++) 
				{
				$agent_performance_array[$key][($array_offset+$k)]+=0; # Add zero so there are no null values;
				}
			$agent_performance_array[$key][($array_offset+2)]=sprintf("%0.2f", MathZDC(100*$agent_performance_array[$key][($array_offset+1)], $agent_performance_array[$key][$array_offset]));
			$agent_performance_array[$key][($array_offset+3)]=sprintf("%0.2f", MathZDC($agent_performance_array[$key][($array_offset+1)], MathZDC($agent_performance_array[$key][($array_offset+4)], 3600)));
			$agent_performance_array[$key][($array_offset+4)]+=0;

			$graph_stats[$j][0]="$key - ".$val[0];
			for ($k=0; $k<5; $k++) 
				{
				$graph_stats[$j][($k+1)]=$agent_performance_array[$key][($array_offset+$k)];
				}
			for ($k=0; $k<5; $k++) 
				{ # Cycle through and check for max values
				if ($agent_performance_array[$key][($array_offset+$k)]>$max_stats[($k+1)]) {$max_stats[($k+1)]=$agent_performance_array[$key][($array_offset+$k)];}
				$graph_TOTALS_array[($array_offset+$k)]+=$agent_performance_array[$key][($array_offset+$k)];
				}
			$j++;
			}
		reset($agent_performance_array);

		$multigraph_text="";
		$graph_id++;
		$graph_array=$master_graph_array[$y];
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
		$graph_title="$rpt_subtitle, $rpt_date";
		include("graphcanvas.inc");
		$HTML_head.=$HTML_graph_head;
		$GRAPH.=$graphCanvas;

		}



	$CSV_header1.="\n";
	$CSV_header2.="\n";
	$CSV_text=$CSV_header1.$CSV_header2;

	$ASCII_header1.="|\n";
	$ASCII_header2.="|\n";
	$ASCII_header3.="|\n";
	$ASCII_text.=$ASCII_header2.$ASCII_header1.$ASCII_header2.$ASCII_header3;

	$CSV_lines='';

	# PRINT OUT RESULTS
	#while (list($key, $val)=each($agent_performance_array)) {
	foreach ($agent_performance_array as $key => $val) {
		$user=$key;
		$full_name=$val[0];
		$ASCII_text.="|";

		if ($non_latin < 1)
			{
			$full_name=	sprintf("%-15s", $full_name); 
			while(strlen($full_name)>15) {$full_name = substr("$full_name", 0, -1);}
			$user =		sprintf("%-8s", $user);
			while(strlen($user)>8) {$user = substr("$user", 0, -1);}
			}
		else
			{	
			$full_name=	sprintf("%-45s", $full_name); 
			while(mb_strlen($full_name,'utf-8')>15) {$full_name = mb_substr("$full_name", 0, -1,'utf-8');}
			$user =	sprintf("%-24s", $user);
			while(mb_strlen($user,'utf-8')>8) {$user = mb_substr("$user", 0, -1,'utf-8');}
			}
		$CSV_text.="\"$full_name\",\"$user\",";
		$ASCII_text.=" ".$full_name." | ".$user." ||";

		for ($q=0; $q<count($rpt_date_array); $q++) 
			{
			$x=($q*5)+1;
			$CSV_text.="\"$val[$x]\",";
			$ASCII_text.=" ".sprintf("%5s", $val[$x])." |";
			$TOTALS_array[$x]+=$val[$x];

			$x++;
			$CSV_text.="\"$val[$x]\",";
			$ASCII_text.=" ".sprintf("%5s", $val[$x])." |";
			$TOTALS_array[$x]+=$val[$x];

			$x++;
			$CSV_text.="\"$val[$x] %\",";
			$ASCII_text.=" ".sprintf("%10s", $val[$x])."% |";
			$TOTALS_array[$x]=sprintf("%0.2f", MathZDC(100*$TOTALS_array[($x-1)], $TOTALS_array[($x-2)]));

			$x++;
			$CSV_text.="\"$val[$x]\",";
			$ASCII_text.=" ".sprintf("%12s", $val[$x])." |";

			$x++;
			$CSV_text.="\"".sec_convert($val[$x], 'H')."\",";
			$ASCII_text.=" ".sprintf("%9s", sec_convert($val[$x], 'H'))." ||";
			$TOTALS_array[$x]+=$val[$x];

			$TOTALS_array[$x-1]=sprintf("%0.2f", MathZDC($TOTALS_array[($x-3)], MathZDC($TOTALS_array[$x], 3600))); # Go back to get last result
			}
		$ASCII_text.="\n";
		$CSV_text.="\n";
		}
	$ASCII_text.=$ASCII_header2;

	$CSV_text.='"","'._QXZ("TOTALS").'",';
	$ASCII_text.="| ".sprintf("%26s", $TOTALS_array[0])." ||";
	$GRAPH_text.=$GRAPH;

	for ($i=1; $i<count($TOTALS_array); $i++) 
		{
		$CSV_text.="\"$TOTALS_array[$i]\",";
		switch($i%5) 
			{
			case "1":
			case "2":
				$ASCII_text.=" ".sprintf("%5s", $TOTALS_array[$i])." |";
				break;
			case "3":
				$ASCII_text.=" ".sprintf("%10s", $TOTALS_array[$i])."% |";
				break;
			case "4":
				$ASCII_text.=" ".sprintf("%12s", $TOTALS_array[$i])." |";
				break;
			case "0":
				$ASCII_text.=" ".sprintf("%9s", sec_convert($TOTALS_array[$i], 'H'))." ||";
				break;
			}
		}
$ASCII_text.="\n";
$CSV_text.="\n";

$ASCII_text.=$ASCII_header2;

$FILE_TIME = date("Ymd-His");
$CSVfilename = "AGENT_PERFORMACE_DETAIL$US$FILE_TIME.csv";

if ($file_download == 1)
	{
	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$CSV_text$CSV_total";

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

#$CSV_report=fopen($CSVfilename, "w");
#fwrite($CSV_report, $CSV_header);
#fwrite($CSV_report, $CSV_lines);
#fwrite($CSV_report, $CSV_total);
#fclose($CSV_report);

$ASCII_text.="\n\n";



if ($report_display_type=="HTML")
	{
	$HTML_text.=$GRAPH_text;
	}
else 
	{
	$HTML_text.=$ASCII_text;
	}

$HTML_text.="\n\n<BR>$db_source";
$HTML_text.="</TD></TR></TABLE>";

$HTML_text.="</BODY></HTML>";


}
if ($file_download == 0 || !$file_download) 
	{
	$JS_onload.="}\n";
	if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
	$JS_text.="</script>\n";

	echo $HTML_head;
	require("admin_header.php");
	echo $HTML_text;
	if ($report_display_type=='HTML') {echo $JS_text;}
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
