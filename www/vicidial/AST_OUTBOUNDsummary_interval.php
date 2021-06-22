<?php 
# AST_OUTBOUNDsummary_interval.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 091128-0311 - First build
# 091129-0017 - Added Sales-type and DNC-type tallies
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110703-1825 - Added download option
# 111104-1205 - Added user_group and calltime restrictions
# 120224-0910 - Added HTML display option with bar graphs
# 130414-0119 - Added report logging
# 130610-1000 - Finalized changing of all ereg instances to preg
# 130621-0730 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2024 - Changed to mysqli PHP functions
# 140108-0734 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0822 - Finalized adding QXZ translation to all admin files
# 141230-0912 - Added code for on-the-fly language translations display
# 150516-1305 - Fixed Javascript element problem, Issue #857
# 151125-1632 - Added search archive option
# 160227-1136 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170220-2040 - Fixed bug causing sale/dncs to be counted multiple times when dispos repeated between campaigns
# 170227-1709 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170829-0040 - Added screen color settings, fixed display bug
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180507-2315 - Added new help display
# 191013-0813 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["time_interval"]))			{$time_interval=$_GET["time_interval"];}
	elseif (isset($_POST["time_interval"]))	{$time_interval=$_POST["time_interval"];}
if (isset($_GET["print_calls"]))			{$print_calls=$_GET["print_calls"];}
	elseif (isset($_POST["print_calls"]))	{$print_calls=$_POST["print_calls"];}
if (isset($_GET["include_rollover"]))			{$include_rollover=$_GET["include_rollover"];}
	elseif (isset($_POST["include_rollover"]))	{$include_rollover=$_POST["include_rollover"];}
if (isset($_GET["bareformat"]))				{$bareformat=$_GET["bareformat"];}
	elseif (isset($_POST["bareformat"]))	{$bareformat=$_POST["bareformat"];}
if (isset($_GET["costformat"]))				{$costformat=$_GET["costformat"];}
	elseif (isset($_POST["costformat"]))	{$costformat=$_POST["costformat"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$MT[0]='0';
if (strlen($shift)<2) {$shift='ALL';}
if (strlen($include_rollover)<2) {$include_rollover='NO';}

$report_name = 'Outbound Summary Interval Report';
$db_source = 'M';

$JS_text="<script language='Javascript'>\n";
$JS_text.="function openNewWindow(url)\n";
$JS_text.="  {\n";
$JS_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$JS_text.="  }\n";
$JS_onload="onload = function() {\n";

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
$log_tables_array=array("vicidial_log", "vicidial_closer_log", "vicidial_agent_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_log_table=use_archive_table("vicidial_log");
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
	$vicidial_log_table="vicidial_log";
	$vicidial_closer_log_table="vicidial_closer_log";
	$vicidial_agent_log_table="vicidial_agent_log";
	}
#############

$stmt = "SELECT local_gmt FROM servers where active='Y' limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$gmt_conf_ct = mysqli_num_rows($rslt);
$dst = date("I");
if ($gmt_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$local_gmt =		$row[0];
	$epoch_offset =		(($local_gmt + $dst) * 3600);
	}

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

$stmt="SELECT full_name,user_level,user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$MAIN.="|$stmt|\n";}
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
$records_to_print = mysqli_num_rows($rslt);
if ($records_to_print > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$full_name =	$row[0];
	$user_level =	$row[1];
	$user_group =	$row[2];
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

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_call_times from vicidial_user_groups where user_group='$user_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$records_to_print = mysqli_num_rows($rslt);
if ($records_to_print > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$LOGallowed_reports =	$row[1];
	$LOGadmin_viewable_call_times =	$row[2];
	if ( (!preg_match("/ALL-CAMPAIGNS/i",$row[0])) )
		{
		$rawLOGallowed_campaignsSQL = preg_replace('/\s\-/i', '',$row[0]);
		$rawLOGallowed_campaignsSQL = preg_replace('/\s/i', '\',\'',$rawLOGallowed_campaignsSQL);
		$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
		$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
		}
	if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
		{
		echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
		exit;
		}
	}
else
	{
	echo _QXZ("Campaigns Permissions Error").": |$PHP_AUTH_USER|$user_group|\n";
	exit;
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

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
	}

$stmt="select campaign_id,campaign_name from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
$group_names=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =		$row[0];
	$group_names[$i] =	$row[1];
	if (preg_match('/\-\-ALL\-\-/i',$group_string))
		{$group[$i] =	$row[0];}
	$i++;
	}

if ($DB) {$MAIN.="$group_string|$i\n";}

$rollover_groups_count=0;
$i=0;
$group_string='|';
$group_ct = count($group);
$group_cname=array();
while($i < $group_ct)
	{
	$stmt="select campaign_name from vicidial_campaigns where campaign_id='$group[$i]' $LOGallowed_campaignsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$campaign_names_to_print = mysqli_num_rows($rslt);
	if ($campaign_names_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$group_cname[$i] =	$row[0];
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}

	if (preg_match("/YES/i",$include_rollover))
		{
		$stmt="select drop_inbound_group from vicidial_campaigns where campaign_id='$group[$i]' $LOGallowed_campaignsSQL and drop_inbound_group NOT LIKE \"%NONE%\" and drop_inbound_group is NOT NULL and drop_inbound_group != '';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$in_groups_to_print = mysqli_num_rows($rslt);
		if ($in_groups_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$group_drop_SQL .= "'$row[0]',";

			$rollover_groups_count++;
			}
		}

	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{
	$group_SQL = "";
	$group_drop_SQL = "";
	}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_drop_SQL = preg_replace('/,$/i', '',$group_drop_SQL);
	$both_group_SQLand = "and ( (campaign_id IN($group_drop_SQL)) or (campaign_id IN($group_SQL)) )";
	$both_group_SQL = "where ( (campaign_id IN($group_drop_SQL)) or (campaign_id IN($group_SQL)) )";
	$group_SQLand = "and campaign_id IN($group_SQL)";
	$group_SQL = "where campaign_id IN($group_SQL)";
	$group_drop_SQLand = "and campaign_id IN($group_drop_SQL)";
	$group_drop_SQL = "where campaign_id IN($group_drop_SQL)";
	}

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

$stmt="select call_time_id,call_time_name from vicidial_call_times $whereLOGadmin_viewable_call_timesSQL order by call_time_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$times_to_print = mysqli_num_rows($rslt);
$i=0;
$call_times=array();
$call_time_names=array();
while ($i < $times_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$call_times[$i] =		$row[0];
	$call_time_names[$i] =	$row[1];
	$i++;
	}

$customer_interactive_statuses='';
$stmt="select status from vicidial_statuses where human_answered='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$customer_interactive_statuses .= "'$row[0]',";
	$i++;
	}
$stmt="select status from vicidial_campaign_statuses where human_answered='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$customer_interactive_statuses .= "'$row[0]',";
	$i++;
	}
if (strlen($customer_interactive_statuses)>2)
	{$customer_interactive_statuses = substr("$customer_interactive_statuses", 0, -1);}
else
	{$customer_interactive_statuses="''";}

$stmt="select status from vicidial_statuses where sale='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statsale_to_print = mysqli_num_rows($rslt);
$i=0;
$sale_statusesLIST=array();
while ($i < $statsale_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	array_push($sale_statusesLIST, "$row[0]");
	$i++;
	}
$stmt="select status from vicidial_campaign_statuses where sale='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statsale_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statsale_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	array_push($sale_statusesLIST, "$row[0]");
	$i++;
	}
$sale_statusesLIST=array_values(array_unique($sale_statusesLIST));
$sale_ct=count($sale_statusesLIST);

$stmt="select status from vicidial_statuses where dnc='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statdnc_to_print = mysqli_num_rows($rslt);
$i=0;
$dnc_statusesLIST=array();
while ($i < $statdnc_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	array_push($dnc_statusesLIST, "$row[0]");
	$i++;
	}
$stmt="select status from vicidial_campaign_statuses where dnc='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statdnc_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statdnc_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	array_push($dnc_statusesLIST, "$row[0]");
	$i++;
	}
$dnc_statusesLIST=array_values(array_unique($dnc_statusesLIST));
$dnc_ct=count($dnc_statusesLIST);

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

$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HEADER.="<script src='chart/Chart.js'></script>\n"; 
$HEADER.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

if ($bareformat < 1)
	{
	$short_header=1;


	$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#OUTBOUNDsummary_interval$NWE\n";
	$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

	if ($DB > 0)
		{
		$MAIN.="<BR>\n";
		$MAIN.="$group_ct|$group_string|$group_SQL\n";
		$MAIN.="<BR>\n";
		$MAIN.="$shift|$query_date|$end_date\n";
		$MAIN.="<BR>\n";
		}


	$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
	$MAIN.="<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP>\n";
	$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
	$MAIN.="<INPUT TYPE=HIDDEN NAME=costformat VALUE=\"$costformat\">\n";
	$MAIN.="<INPUT TYPE=HIDDEN NAME=print_calls VALUE=\"$print_calls\">\n";
	$MAIN.=_QXZ("Date Range").":<BR>\n";
	$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
	$MAIN.="	<script language=\"JavaScript\">\n";
	$MAIN.="	var o_cal = new tcal ({\n";
	$MAIN.="		// form name\n";
	$MAIN.="		'formname': 'vicidial_report',\n";
	$MAIN.="		// input name\n";
	$MAIN.="		'controlname': 'query_date'\n";
	$MAIN.="	});\n";
	$MAIN.="	o_cal.a_tpl.yearscroll = false;\n";
	$MAIN.="	// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
	$MAIN.="	</script>\n";
	$MAIN.=" "._QXZ("to")." <INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";
	$MAIN.="	<script language=\"JavaScript\">\n";
	$MAIN.="	var o_cal = new tcal ({\n";
	$MAIN.="		// form name\n";
	$MAIN.="		'formname': 'vicidial_report',\n";
	$MAIN.="		// input name\n";
	$MAIN.="		'controlname': 'end_date'\n";
	$MAIN.="	});\n";
	$MAIN.="	o_cal.a_tpl.yearscroll = false;\n";
	$MAIN.="	// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
	$MAIN.="	</script>\n";

	$MAIN.="</TD><TD VALIGN=TOP ROWSPAN=2> "._QXZ("Campaigns").":<BR>";
	$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
	if  (preg_match('/\-\-ALL\-\-/',$group_string))
		{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
	else
		{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
	$o=0;
	while ($campaigns_to_print > $o)
		{
		if (preg_match("/$groups[$o]\|/i",$group_string)) {$MAIN.="<option selected value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";}
		  else {$MAIN.="<option value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";}
		$o++;
		}
	$MAIN.="</SELECT>\n";
	$MAIN.="</TD><TD VALIGN=TOP ROWSPAN=2>";
	$MAIN.=_QXZ("Include Drop")." &nbsp; <BR>"._QXZ("Rollover").":<BR>";
	$MAIN.="<SELECT SIZE=1 NAME=include_rollover>\n";
	$MAIN.="<option selected value=\"$include_rollover\">"._QXZ("$include_rollover")."</option>\n";
	$MAIN.="<option value=\"YES\">"._QXZ("YES")."</option>\n";
	$MAIN.="<option value=\"NO\">"._QXZ("NO")."</option>\n";
	$MAIN.="</SELECT><BR>\n";
	$MAIN.=_QXZ("Time Interval").":<BR>";
	$MAIN.="<SELECT SIZE=1 NAME=time_interval>\n";
	if ($time_interval <= 900)
		{
		$interval_count = 96;
		$hf=45;
		$MAIN.="<option selected value=\"900\">"._QXZ("15 Minutes")."</option>\n";
		}
	else
		{$MAIN.="<option value=\"900\">"._QXZ("15 Minutes")."</option>\n";}
	if ( ($time_interval > 900) and ($time_interval <= 1800) )
		{
		$interval_count = 48;
		$hf=30;
		$MAIN.="<option selected value=\"1800\">"._QXZ("30 Minutes")."</option>\n";
		}
	else
		{$MAIN.="<option value=\"1800\">"._QXZ("30 Minutes")."</option>\n";}
	if ($time_interval > 1800)
		{
		$interval_count = 24;
		$MAIN.="<option selected value=\"3600\">"._QXZ("1 Hour")."</option>\n";
		}
	else
		{$MAIN.="<option value=\"3600\">"._QXZ("1 Hour")."</option>\n";}


	$MAIN.="</SELECT>\n";
	$MAIN.="</TD><TD VALIGN=TOP ALIGN=LEFT ROWSPAN=2>\n";
	$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; ";
	$MAIN.="<a href=\"$PHP_SELF?DB=$DB&costformat=$costformat&print_calls=$print_calls&query_date=$query_date&end_date=$end_date$groupQS&include_rollover=$include_rollover&time_interval=$time_interval&SUBMIT=$SUBMIT&shift=$shift&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a> | ";
	$MAIN.="<a href=\"./admin.php?ADD=34&campaign_id=$group[0]\">"._QXZ("MODIFY")."</a> | ";
	$MAIN.="<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>";
	$MAIN.="</FONT><BR><BR>\n";
	$MAIN.=_QXZ("Display as").":<BR>";
	$MAIN.="<select name='report_display_type'>";
	if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
	$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR>";
	if ($archives_available=="Y") 
		{
		$MAIN.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
		}
	$MAIN.="<BR> &nbsp; &nbsp; &nbsp; &nbsp; ";
	$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";

	$MAIN.="</TD></TR>\n";
	$MAIN.="<TR><TD>\n";

	$MAIN.=_QXZ("Call Time").":<BR>\n";
	$MAIN.="<SELECT SIZE=1 NAME=shift>\n";
	$o=0;
	while ($times_to_print > $o)
		{
		if ($call_times[$o] == $shift) {$MAIN.="<option selected value=\"$call_times[$o]\">$call_times[$o] - $call_time_names[$o]</option>\n";}
		else {$MAIN.="<option value=\"$call_times[$o]\">$call_times[$o] - $call_time_names[$o]</option>\n";}
		$o++;
		}
	$MAIN.="</SELECT>\n";
	$MAIN.="</TD><TD>\n";
	$MAIN.="</TD></TR></TABLE>\n";
	$MAIN.="</FORM>\n\n";

	$MAIN.="<PRE><FONT SIZE=2>\n\n";
	}

if ($group_ct < 1)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT A CAMPAIGN AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	if ($shift == 'ALL') 
		{
		$Gct_default_start = "0";
		$Gct_default_stop = "2400";
		}
	else 
		{
		$stmt="SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times FROM vicidial_call_times where call_time_id='$shift';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$calltimes_to_print = mysqli_num_rows($rslt);
		if ($calltimes_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$Gct_default_start =	$row[3];
			$Gct_default_stop =		$row[4];
			$Gct_sunday_start =		$row[5];
			$Gct_sunday_stop =		$row[6];
			$Gct_monday_start =		$row[7];
			$Gct_monday_stop =		$row[8];
			$Gct_tuesday_start =	$row[9];
			$Gct_tuesday_stop =		$row[10];
			$Gct_wednesday_start =	$row[11];
			$Gct_wednesday_stop =	$row[12];
			$Gct_thursday_start =	$row[13];
			$Gct_thursday_stop =	$row[14];
			$Gct_friday_start =		$row[15];
			$Gct_friday_stop =		$row[16];
			$Gct_saturday_start =	$row[17];
			$Gct_saturday_stop =	$row[18];
			}
		else
			{
			$Gct_default_start = "0";
			$Gct_default_stop = "2400";
			}
		}
	$h=0;
	$hh=0;
	$Hcalltime=array();
	$Hcalltime_HHMM=array();
	while ($h < $interval_count)
		{
		if ($interval_count>=96)
			{
			if ($hf < 45)
				{
				$hf = ($hf + 15);
				}
			else
				{
				$hf = "00";
				if ($h > 0)
					{$hh++;}
				}
			$H_test = "$hh$hf";
			}
		if ($interval_count==48)
			{
			if ($hf < 30)
				{
				$hf = ($hf + 30);
				}
			else
				{
				$hf = "00";
				if ($h > 0)
					{$hh++;}
				}
			$H_test = "$hh$hf";
			}
		if ($interval_count<=24)
			{
			$H_test = $h . "00";
			}
		if ( ($H_test >= $Gct_default_start) and ($H_test <= $Gct_default_stop) )
			{
			$Hcalltime[$h]++;
			$Hcalltime_HHMM[$h] = "$H_test";
			}
		if ($DB)
			{$MAIN.="( ($H_test >= $Gct_default_start) and ($H_test <= $Gct_default_stop) ) $hh $hf\n";}
		$h++;
		}

	$query_date_BEGIN = "$query_date 00:00:00";   
	$query_date_END = "$end_date 23:59:59";


	$MAIN .= _QXZ("Outbound Summary Interval Report",32).": $group_string          $NOW_TIME\n";

	$CSV_main.="\""._QXZ("Outbound Summary Interval Report").":\"\n\"$group_string\"\n\"$NOW_TIME\"\n\n";


	##### Loop through each campaign and gether stats
	if ($group_ct > 0)
		{
		$ASCII_text .= "\n";
		$ASCII_text .= "---------- "._QXZ("MULTI-CAMPAIGN BREAKDOWN").":\n";
		$ASCII_text .= "+------------------------------------------+--------+--------+--------+--------+--------+--------+--------+------------+------------+\n";
		$ASCII_text .= "|                                          |        | "._QXZ("SYSTEM",6)." | "._QXZ("AGENT",6)." |        |        | "._QXZ("NO",6)." |        | "._QXZ("AGENT",10)." | "._QXZ("AGENT",10)." |\n";
		$ASCII_text .= "|                                          | "._QXZ("TOTAL",6)." | "._QXZ("RELEASE",7)."| "._QXZ("RELEASE",7)."| "._QXZ("SALE",6)." | "._QXZ("DNC",6)." | "._QXZ("ANSWER",6)." | "._QXZ("DROP",6)." | "._QXZ("LOGIN",10)." | "._QXZ("PAUSE",10)." |\n";
		$ASCII_text .= "| "._QXZ("CAMPAIGN",40)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("PERCENT",7)."| "._QXZ("PERCENT",7)."| "._QXZ("TIME(H:M:S)",11)."| "._QXZ("TIME(H:M:S)",11)."|\n";
		$ASCII_text .= "+------------------------------------------+--------+--------+--------+--------+--------+--------+--------+------------+------------+\n";

		$CSV_main.="\""._QXZ("MULTI-CAMPAIGN BREAKDOWN").":\"\n";
		$CSV_main.="\""._QXZ("CAMPAIGN")."\",\""._QXZ("TOTAL CALLS")."\",\""._QXZ("SYSTEM RELEASE CALLS")."\",\""._QXZ("AGENT RELEASE CALLS")."\",\""._QXZ("SALE CALLS")."\",\""._QXZ("DNC CALLS")."\",\""._QXZ("NO ANSWER PERCENT")."\",\""._QXZ("DROP PERCENT")."\",\""._QXZ("AGENT LOGIN TIME(H:M:S)")."\",\""._QXZ("AGENT PAUSE TIME(H:M:S)")."\"\n";
		$CSV_subreports="";
		
		######## GRAPHING #########
		$max_calls=1;
		$max_system_release=1;
		$max_agent_release=1;
		$max_sales=1;
		$max_dncs=1;
		$max_nas=1;
		$max_drops=1;
		$max_login_time=1;
		$max_pause_time=1;


		###########################

		$i=0;
		$TOTcalls_count=0;
		$TOTsystem_count=0;
		$TOTagent_count=0;
		$TOTptp_count=0;
		$TOTrtp_count=0;
		$TOTna_count=0;
		$TOTdrop_count=0;
		$TOTagent_login_sec=0;
		$TOTagent_pause_sec=0;
		$SUBoutput='';
		$ATcall_date=array();
		$ATepoch=array();
		$ATcampaign_id=array();
		$ATpause_sec=array();
		$ATagent_sec=array();
		$CPstatus=array();
		$CPlength_in_sec=array();
		$CPcall_date=array();
		$CPepoch=array();
		$CPphone_number=array();
		$CPcampaign_id=array();
		$CPvicidial_id=array();
		$CPlead_id=array();
		$TESTlead_id=array();
		$TESTuniqueid=array();
		$CPin_out=array();
		$length_in_sec=array();
		$queue_seconds=array();
		$agent_sec=array();
		$pause_sec=array();
		$talk_sec=array();
		$calls_count=array();
		$calls_count_IN=array();
		$drop_count=array();
		$drop_count_OUT=array();
		$system_count=array();
		$agent_count=array();
		$ptp_count=array();
		$rtp_count=array();
		$na_count=array();
		$answer_count=array();
		$max_queue_seconds=array();
		$Hlength_in_sec=array();
		$Hqueue_seconds=array();
		$Hagent_sec=array();
		$Hpause_sec=array();
		$Htalk_sec=array();
		$Hcalls_count=array();
		$Hcalls_count_IN=array();
		$Hdrop_count=array();
		$Hdrop_count_OUT=array();
		$Hsystem_count=array();
		$Hagent_count=array();
		$Hptp_count=array();
		$Hrtp_count=array();
		$Hna_count=array();
		$Hanswer_count=array();
		$Hmax_queue_seconds=array();
		$talk_avg=array();
		$queue_avg=array();
		$graph_stats=array();

		while($i < $group_ct)
			{
			$u=0;

			##### Gather Agent time records
			$stmt="select event_time,UNIX_TIMESTAMP(event_time),campaign_id,pause_sec,wait_sec,talk_sec,dispo_sec from ".$vicidial_agent_log_table." where event_time >= '$query_date_BEGIN' and event_time <= '$query_date_END' and campaign_id IN('$group_drop[$i]','$group[$i]');";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {$ASCII_text.="$stmt\n";}
			$AGENTtime_to_print = mysqli_num_rows($rslt);
			$s=0;
			while ($s < $AGENTtime_to_print)
				{
				$row=mysqli_fetch_row($rslt);
				$inTOTALsec =		($row[3] + $row[4] + $row[5] + $row[6]);	
				$ATcall_date[$s] =		$row[0];
				$ATepoch[$s] =			$row[1];
				$ATcampaign_id[$s] =	$row[2];
				$ATpause_sec[$s] =		$row[3];
				$ATagent_sec[$s] =		$inTOTALsec;
				$s++;
				}

			##### Gather outbound calls
			$stmt = "SELECT status,length_in_sec,call_date,UNIX_TIMESTAMP(call_date),phone_number,campaign_id,uniqueid,lead_id from ".$vicidial_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id='$group[$i]';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {$ASCII_text.="$stmt\n";}
			$calls_to_parse = mysqli_num_rows($rslt);
			$p=0;
			while ($p < $calls_to_parse)
				{
				$row=mysqli_fetch_row($rslt);
				$CPstatus[$u] =			$row[0];
				$CPlength_in_sec[$u] =	$row[1];
				$CPcall_date[$u] =		$row[2];
				$CPepoch[$u] =			$row[3];
				$CPphone_number[$u] =	$row[4];
				$CPcampaign_id[$u] =	$row[5];
				$CPvicidial_id[$u] =	$row[6];
				$CPlead_id[$u] =		$row[7];
				$TESTlead_id[$u] =		$row[7];
				$TESTuniqueid[$u] =		$row[6];
				$CPin_out[$u] =			_QXZ("OUT");
				$p++;
				$u++;
				}

			$group_drop[$i]='';
			if (preg_match("/YES/i",$include_rollover))
				{
				##### Gather inbound calls from drop inbound group if selected
				$stmt="select drop_inbound_group from vicidial_campaigns where campaign_id='$group[$i]' $LOGallowed_campaignsSQL and drop_inbound_group NOT LIKE \"%NONE%\" and drop_inbound_group is NOT NULL and drop_inbound_group != '';";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {$ASCII_text.="$stmt\n";}
				$in_groups_to_print = mysqli_num_rows($rslt);
				if ($in_groups_to_print > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$group_drop[$i] = $row[0];
					$rollover_groups_count++;
					}

				$length_in_secZ=0;
				$queue_secondsZ=0;
				$agent_alert_delayZ=0;
				$stmt="select status,length_in_sec,queue_seconds,agent_alert_delay,call_date,UNIX_TIMESTAMP(call_date),phone_number,campaign_id,closecallid,lead_id,uniqueid from ".$vicidial_closer_log_table.",vicidial_inbound_groups where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and group_id=campaign_id and campaign_id='$group_drop[$i]';";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {$ASCII_text.="$stmt\n";}
				$INallcalls_to_printZ = mysqli_num_rows($rslt);
				$y=0;
				while ($y < $INallcalls_to_printZ)
					{
					$row=mysqli_fetch_row($rslt);

					$k=0;
					$front_call_found=0;
					while($k < $p)
						{
						if ($TESTuniqueid[$k] == $row[10])
							{$front_call_found++;}
						$k++;
						}
					if ($front_call_found > 0)
						{
						$length_in_secZ = $row[1];
						$queue_secondsZ = $row[2];
						$agent_alert_delayZ = $row[3];

						$TOTALdelay =		round(MathZDC($agent_alert_delayZ, 1000));
						$thiscallsec = (($length_in_secZ - $queue_secondsZ) - $TOTALdelay);
						if ($thiscallsec < 0)
							{$thiscallsec = 0;}
						$inTOTALsec =	($inTOTALsec + $thiscallsec);	

						$CPstatus[$u] =			$row[0];
						$CPlength_in_sec[$u] =	$inTOTALsec;
						$CPcall_date[$u] =		$row[4];
						$CPepoch[$u] =			$row[5];
						$CPphone_number[$u] =	$row[6];
						$CPcampaign_id[$u] =	$row[7];
						$CPvicidial_id[$u] =	$row[8];
						$CPlead_id[$u] =		$row[9];
						$CPin_out[$u] =			'IN';
						$u++;
						}
					$y++;
					}
				}


			$out_of_call_time=0;
			$length_in_sec[$i]=0;
			$queue_seconds[$i]=0;
			$agent_sec[$i]=0;
			$pause_sec[$i]=0;
			$talk_sec[$i]=0;
			$calls_count[$i]=0;
			$calls_count_IN[$i]=0;
			$drop_count[$i]=0;
			$drop_count_OUT[$i]=0;
			$system_count[$i]=0;
			$agent_count[$i]=0;
			$ptp_count[$i]=0;
			$rtp_count[$i]=0;
			$na_count[$i]=0;
			$answer_count[$i]=0;
			$max_queue_seconds[$i]=0;
			$Hlength_in_sec=$MT;
			$Hqueue_seconds=$MT;
			$Hagent_sec=$MT;
			$Hpause_sec=$MT;
			$Htalk_sec=$MT;
			$Hcalls_count=$MT;
			$Hcalls_count_IN=$MT;
			$Hdrop_count=$MT;
			$Hdrop_count_OUT=$MT;
			$Hsystem_count=$MT;
			$Hagent_count=$MT;
			$Hptp_count=$MT;
			$Hrtp_count=$MT;
			$Hna_count=$MT;
			$Hanswer_count=$MT;
			$Hmax_queue_seconds=$MT;
			$hTOTALcalls =	0;
			$hANSWERcalls =	0;
			$hSUMagent =	0;
			$hSUMpause =	0;
			$hSUMtalk =		0;
			$hAVGtalk =		0;
			$hSUMqueue =	0;
			$hAVGqueue =	0;
			$hMAXqueue =	0;
			$hDROPcalls =	0;
			$hPRINT =		0;
			$hTOTcalls_count =			0;
			$hTOTsystem_count =			0;
			$hTOTagent_count =			0;
			$hTOTptp_count =			0;
			$hTOTrtp_count =			0;
			$hTOTna_count =				0;
			$hTOTanswer_count =			0;
			$hTOTagent_sec =			0;
			$hTOTpause_sec =			0;
			$hTOTtalk_sec =				0;
			$hTOTtalk_avg =				0;
			$hTOTqueue_seconds =		0;
			$hTOTqueue_avg =			0;
			$hTOTmax_queue_seconds =	0;
			$hTOTdrop_count =			0;

			##### Parse through the agent time records to tally the time
			$p=0;
			while ($p < $s)
				{
				$call_date = explode(" ", $ATcall_date[$p]);
				$call_time = preg_replace('/[^0-9]/','',$call_date[1]);
				$epoch = $ATepoch[$p];
				$Cwday = date("w", $epoch);

				$CTstart = $Gct_default_start . "00";
				$CTstop = $Gct_default_stop . "59";

				if ( ($Cwday == 0) and ( ($Gct_sunday_start > 0) and ($Gct_sunday_stop > 0) ) )
					{$CTstart = $Gct_sunday_start . "00";   $CTstop = $Gct_sunday_stop . "59";}
				if ( ($Cwday == 1) and ( ($Gct_monday_start > 0) and ($Gct_monday_stop > 0) ) )
					{$CTstart = $Gct_monday_start . "00";   $CTstop = $Gct_monday_stop . "59";}
				if ( ($Cwday == 2) and ( ($Gct_tuesday_start > 0) and ($Gct_tuesday_stop > 0) ) )
					{$CTstart = $Gct_tuesday_start . "00";   $CTstop = $Gct_tuesday_stop . "59";}
				if ( ($Cwday == 3) and ( ($Gct_wednesday_start > 0) and ($Gct_wednesday_stop > 0) ) )
					{$CTstart = $Gct_wednesday_start . "00";   $CTstop = $Gct_wednesday_stop . "59";}
				if ( ($Cwday == 4) and ( ($Gct_thursday_start > 0) and ($Gct_thursday_stop > 0) ) )
					{$CTstart = $Gct_thursday_start . "00";   $CTstop = $Gct_thursday_stop . "59";}
				if ( ($Cwday == 5) and ( ($Gct_friday_start > 0) and ($Gct_friday_stop > 0) ) )
					{$CTstart = $Gct_friday_start . "00";   $CTstop = $Gct_friday_stop . "59";}
				if ( ($Cwday == 6) and ( ($Gct_saturday_start > 0) and ($Gct_saturday_stop > 0) ) )
					{$CTstart = $Gct_saturday_start . "00";   $CTstop = $Gct_saturday_stop . "59";}

				$Chour = date("G", $epoch);
				$Cmin = date("i", $epoch);
				if ($interval_count==96)
					{
					$ChourX = ($Chour * 4);
					if ($Cmin < 15) {$Cmin = "00"; $CminX = 0;}
					if ( ($Cmin >= 15) and ($Cmin < 30) ) {$Cmin = "15"; $CminX = 1;}
					if ( ($Cmin >= 30) and ($Cmin < 45) ) {$Cmin = "30"; $CminX = 2;}
					if ($Cmin >= 45) {$Cmin = "45"; $CminX = 3;}
					$Chour = ($ChourX + $CminX);
					}
				if ($interval_count==48)
					{
					$ChourX = ($Chour * 2);
					if ($Cmin < 30) {$Cmin = "00"; $CminX = 0;}
					if ($Cmin >= 30) {$Cmin = "30"; $CminX = 1;}
					$Chour = ($ChourX + $CminX);
					}

				if ( ($call_time > $CTstart) and ($call_time < $CTstop) )
					{
					$agent_sec[$i] = ($agent_sec[$i] + $ATagent_sec[$p]);
					$Hagent_sec[$Chour] = ($Hagent_sec[$Chour] + $ATagent_sec[$p]);
					$pause_sec[$i] = ($pause_sec[$i] + $ATpause_sec[$p]);
					$Hpause_sec[$Chour] = ($Hpause_sec[$Chour] + $ATpause_sec[$p]);

					$Hcalltime[$Chour]++;

					if ($print_calls > 0)
						{
						$ASCII_text.="$row[5]\t$row[6]\t$TEMPtalk\n";
						$PCtemptalk = ($PCtemptalk + $TEMPtalk);
						}
					$q++;
					}
				else
					{$out_of_call_time++;}
				if ($DB)
					{$ASCII_text.="$Hcalltime[$Chour] | "._QXZ("AGENT").": $agent_sec[$i] "._QXZ("PAUSE").": $pause_sec[$i]\n";}
				$p++;
				}






			##### Parse through call records to tally the counts
			$p=0;
			while ($p < $u)
				{
				$call_date = explode(" ", $CPcall_date[$p]);
				$call_time = preg_replace('/[^0-9]/','',$call_date[1]);
				$epoch = $CPepoch[$p];
				$Cwday = date("w", $epoch);

				$CTstart = $Gct_default_start . "00";
				$CTstop = $Gct_default_stop . "59";

				if ( ($Cwday == 0) and ( ($Gct_sunday_start > 0) and ($Gct_sunday_stop > 0) ) )
					{$CTstart = $Gct_sunday_start . "00";   $CTstop = $Gct_sunday_stop . "59";}
				if ( ($Cwday == 1) and ( ($Gct_monday_start > 0) and ($Gct_monday_stop > 0) ) )
					{$CTstart = $Gct_monday_start . "00";   $CTstop = $Gct_monday_stop . "59";}
				if ( ($Cwday == 2) and ( ($Gct_tuesday_start > 0) and ($Gct_tuesday_stop > 0) ) )
					{$CTstart = $Gct_tuesday_start . "00";   $CTstop = $Gct_tuesday_stop . "59";}
				if ( ($Cwday == 3) and ( ($Gct_wednesday_start > 0) and ($Gct_wednesday_stop > 0) ) )
					{$CTstart = $Gct_wednesday_start . "00";   $CTstop = $Gct_wednesday_stop . "59";}
				if ( ($Cwday == 4) and ( ($Gct_thursday_start > 0) and ($Gct_thursday_stop > 0) ) )
					{$CTstart = $Gct_thursday_start . "00";   $CTstop = $Gct_thursday_stop . "59";}
				if ( ($Cwday == 5) and ( ($Gct_friday_start > 0) and ($Gct_friday_stop > 0) ) )
					{$CTstart = $Gct_friday_start . "00";   $CTstop = $Gct_friday_stop . "59";}
				if ( ($Cwday == 6) and ( ($Gct_saturday_start > 0) and ($Gct_saturday_stop > 0) ) )
					{$CTstart = $Gct_saturday_start . "00";   $CTstop = $Gct_saturday_stop . "59";}

				$Chour = date("G", $epoch);
				$Cmin = date("i", $epoch);
				if ($interval_count==96)
					{
					$ChourX = ($Chour * 4);
					if ($Cmin < 15) {$Cmin = "00"; $CminX = 0;}
					if ( ($Cmin >= 15) and ($Cmin < 30) ) {$Cmin = "15"; $CminX = 1;}
					if ( ($Cmin >= 30) and ($Cmin < 45) ) {$Cmin = "30"; $CminX = 2;}
					if ($Cmin >= 45) {$Cmin = "45"; $CminX = 3;}
					$Chour = ($ChourX + $CminX);
					}
				if ($interval_count==48)
					{
					$ChourX = ($Chour * 2);
					if ($Cmin < 30) {$Cmin = "00"; $CminX = 0;}
					if ($Cmin >= 30) {$Cmin = "30"; $CminX = 1;}
					$Chour = ($ChourX + $CminX);
					}

				if ( ($call_time > $CTstart) and ($call_time < $CTstop) )
					{
					$calls_count[$i]++;
					$length_in_sec[$i] =	($length_in_sec[$i] + $CPlength_in_sec[$p]);
					$Hlength_in_sec[$Chour] =	($Hlength_in_sec[$Chour] + $row[1]);
					$Hqueue_seconds[$Chour] =	($Hqueue_seconds[$Chour] + $row[2]);
					$TEMPtalk = $CPlength_in_sec[$p];
					if ($TEMPtalk < 0) {$TEMPtalk = 0;}
					$talk_sec[$i] =	($talk_sec[$i] + $TEMPtalk);
					$Htalk_sec[$Chour] =	($Htalk_sec[$Chour] + $TEMPtalk);

					$Hcalls_count[$Chour]++;
					if (preg_match("/DROP/i",$CPstatus[$p]))
						{
						if ($CPin_out[$p] == 'OUT')
							{
							$drop_count_OUT[$i]++;
							$Hdrop_count_OUT[$Chour]++;
							}
						$drop_count[$i]++;
						$Hdrop_count[$Chour]++;
						}
					else
						{
						$answer_count[$i]++;
						$Hanswer_count[$Chour]++;
						}
					if (preg_match("/\|$CPstatus[$p]\|/i",'|NA|NEW|QUEUE|INCALL|DROP|XDROP|AA|AM|AL|AFAX|AB|ADC|DNCL|DNCC|PU|PM|SVYEXT|SVYHU|SVYVM|SVYREC|QVMAIL|'))
						{
						$system_count[$i]++;
						$Hsystem_count[$Chour]++;
						}
					else
						{
						$agent_count[$i]++;
						$Hagent_count[$Chour]++;
						}
					if ($CPstatus[$p] == 'NA')
						{
						$na_count[$i]++;
						$Hna_count[$Chour]++;
						}
					if ($CPin_out[$p] == 'IN')
						{
						$calls_count_IN[$i]++;
						$Hcalls_count_IN[$Chour]++;
						}

					$k=0;
					while($k < $sale_ct)
						{
						if ($sale_statusesLIST[$k] == $CPstatus[$p])
							{
							$ptp_count[$i]++;
							$Hptp_count[$Chour]++;
							}
						$k++;
						}

					$k=0;
					while($k < $dnc_ct)
						{
						if ($dnc_statusesLIST[$k] == $CPstatus[$p])
							{
							$rtp_count[$i]++;
							$Hrtp_count[$Chour]++;
							}
						$k++;
						}

					$Hcalltime[$Chour]++;
					

					if ($print_calls > 0)
						{
						$ASCII_text.="$row[5]\t$row[6]\t$TEMPtalk\n";
						$PCtemptalk = ($PCtemptalk + $TEMPtalk);
						}
					$q++;
					}
				else
					{$out_of_call_time++;}
				if ($DB)
					{$ASCII_text.="$call_time > $CTstart | $call_time < $CTstop | $Cwday | $Chour | $Hcalltime[$Chour] | $talk_sec[$i]\n";}
				$p++;
				}


			$talk_avg[$i] = MathZDC($talk_sec[$i], $answer_count[$i]);
			$queue_avg[$i] = MathZDC($queue_seconds[$i], $calls_count[$i]);

			if ($print_calls > 0)
				{
				$PCtemptalkmin = MathZDC($PCtemptalk, 60);
				$ASCII_text.="$q\t$PCtemptalk\t$PCtemptalkmin\n";
				}

			if ( ($calls_count_IN[$i] > 0) and ($drop_count_OUT[$i] > 0) )
				{
				$drop_count[$i] = ($drop_count[$i] - $calls_count_IN[$i]);
				$calls_count[$i] = ($calls_count[$i] - $calls_count_IN[$i]);
				$system_count[$i] = ($system_count[$i] - $calls_count_IN[$i]);
				if ($drop_count[$i] < 0)
					{$drop_count[$i] = 0;}
				}
			$TOTcalls_count =			($TOTcalls_count + $calls_count[$i]);
			$TOTsystem_count =			($TOTsystem_count + $system_count[$i]);
			$TOTagent_count =			($TOTagent_count + $agent_count[$i]);
			$TOTptp_count =				($TOTptp_count + $ptp_count[$i]);
			$TOTrtp_count =				($TOTrtp_count + $rtp_count[$i]);
			$TOTna_count =				($TOTna_count + $na_count[$i]);
			$TOTanswer_count =			($TOTanswer_count + $answer_count[$i]);
			$TOTagent_sec =				($TOTagent_sec + $agent_sec[$i]);
			$TOTpause_sec =				($TOTpause_sec + $pause_sec[$i]);
			$TOTtalk_sec =				($TOTtalk_sec + $talk_sec[$i]);
			$TOTqueue_seconds =			($TOTqueue_seconds + $queue_seconds[$i]);
			$TOTdrop_count =			($TOTdrop_count + $drop_count[$i]);
			if ($max_queue_seconds[$i] > $TOTmax_queue_seconds)
				{$TOTmax_queue_seconds = $max_queue_seconds[$i];}

			if ($calls_count[$i]>$max_calls) {$max_calls=$calls_count[$i];}
			if ($system_count[$i]>$max_system_release) {$max_system_release=$system_count[$i];}
			if ($agent_count[$i]>$max_agent_release) {$max_agent_release=$agent_count[$i];}
			if ($ptp_count[$i]>$max_sales) {$max_sales=$ptp_count[$i];}
			if ($rtp_count[$i]>$max_dncs) {$max_dncs=$rtp_count[$i];}
			if ($agent_sec[$i]>$max_login_time) {$max_login_time=$agent_sec[$i];}
			if ($pause_sec[$i]>$max_pause_time) {$max_pause_time=$pause_sec[$i];}
			$graph_stats[$i][0]="$group[$i] - $group_cname[$i]";
			$graph_stats[$i][1]=$calls_count[$i];
			$graph_stats[$i][2]=$system_count[$i];
			$graph_stats[$i][3]=$agent_count[$i];
			$graph_stats[$i][4]=$ptp_count[$i];
			$graph_stats[$i][5]=$rtp_count[$i];
			$graph_stats[$i][8]=$agent_sec[$i];
			$graph_stats[$i][9]=$pause_sec[$i];

			$agent_sec[$i] =			sec_convert($agent_sec[$i],'H'); 
			$pause_sec[$i] =			sec_convert($pause_sec[$i],'H'); 
			$talk_sec[$i] =				sec_convert($talk_sec[$i],'H'); 
			$talk_avg[$i] =				sec_convert($talk_avg[$i],'H'); 
			$queue_seconds[$i] =		sec_convert($queue_seconds[$i],'H'); 
			$queue_avg[$i] =			sec_convert($queue_avg[$i],'H'); 
			$max_queue_seconds[$i] =	sec_convert($max_queue_seconds[$i],'H'); 


			$groupDISPLAY =	sprintf("%-40s", "$group[$i] - $group_cname[$i]");
			$gTOTALcalls =	sprintf("%6s", $calls_count[$i]);
			$gSYSTEMcalls =	sprintf("%6s", $system_count[$i]);
			$gAGENTcalls =	sprintf("%6s", $agent_count[$i]);
			$gPTPcalls =	sprintf("%6s", $ptp_count[$i]);
			$gRTPcalls =	sprintf("%6s", $rtp_count[$i]);
			$gNApercent = ( MathZDC($na_count[$i], $calls_count[$i]) * 100);
			$gNApercent =	sprintf("%6.2f",$gNApercent);
			$gNAcalls =		sprintf("%6s", $na_count[$i]);
			$gANSWERcalls =	sprintf("%6s", $answer_count[$i]);
			$gSUMagent =	sprintf("%10s", $agent_sec[$i]);
			$gSUMpause =	sprintf("%10s", $pause_sec[$i]);
			$gSUMtalk =		sprintf("%9s", $talk_sec[$i]);
			$gAVGtalk =		sprintf("%7s", $talk_avg[$i]);
			$gSUMqueue =	sprintf("%9s", $queue_seconds[$i]);
			$gAVGqueue =	sprintf("%7s", $queue_avg[$i]);
			$gMAXqueue =	sprintf("%7s", $max_queue_seconds[$i]);
			$gDROPpercent = ( MathZDC($drop_count[$i], $calls_count[$i]) * 100);
			$gDROPpercent =		sprintf("%6.2f",$gDROPpercent);
			$gDROPcalls =	sprintf("%6s", $drop_count[$i]);

			if (trim($gNApercent)>$max_nas) {$max_nas=trim($gNApercent);}
			if (trim($gDROPpercent)>$max_drops) {$max_drops=trim($gDROPpercent);}
			$graph_stats[$i][6]=trim($gNApercent);
			$graph_stats[$i][7]=trim($gDROPpercent);


			while(strlen($groupDISPLAY)>40) {$groupDISPLAY = substr("$groupDISPLAY", 0, -1);}

			$ASCII_text .= "| $groupDISPLAY | $gTOTALcalls | $gSYSTEMcalls | $gAGENTcalls | $gPTPcalls | $gRTPcalls | $gNApercent%| $gDROPpercent%| $gSUMagent | $gSUMpause |";
			$CSV_main.="\"$groupDISPLAY\",\"$gTOTALcalls\",\"$gSYSTEMcalls\",\"$gAGENTcalls\",\"$gPTPcalls\",\"$gRTPcalls\",\"$gNApercent%\",\"$gDROPpercent%\",\"$gSUMagent\",\"$gSUMpause\"\n";
			if ($DB) {$ASCII_text .= " $gDROPcalls($calls_count_IN[$i]/$drop_count_OUT[$i]) |";}
			$ASCII_text .= "<!-- OUT OF CALLTIME: $out_of_call_time -->\n";

			### hour by hour sumaries
			$SUB_ASCII_text .= "\n---------- $group[$i] - $group_cname[$i]\n"._QXZ("INTERVAL BREAKDOWN").":\n";
			$SUB_ASCII_text .= "+---------------------+--------+--------+--------+--------+--------+--------+--------+------------+------------+\n";
			$SUB_ASCII_text .= "|                     |        | "._QXZ("SYSTEM",6)." | "._QXZ("AGENT",6)." |        |        | "._QXZ("NO",6)." |        | "._QXZ("AGENT",10)." | "._QXZ("AGENT",10)." |\n";
			$SUB_ASCII_text .= "|                     | "._QXZ("TOTAL",6)." | "._QXZ("RELEASE",7)."| "._QXZ("RELEASE",7)."| "._QXZ("SALE",6)." | "._QXZ("DNC",6)." | "._QXZ("ANSWER",6)." | "._QXZ("DROP",6)." | "._QXZ("LOGIN",10)." | "._QXZ("PAUSE",10)." |\n";
			$SUB_ASCII_text .= "| "._QXZ("INTERVAL",19)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("CALLS",6)." | "._QXZ("PERCENT",7)."| "._QXZ("PERCENT",7)."| "._QXZ("TIME(H:M:S)",11)."| "._QXZ("TIME(H:M:S)",11)."|\n";
			$SUB_ASCII_text .= "+---------------------+--------+--------+--------+--------+--------+--------+--------+------------+------------+\n";

			$CSV_subreports.="\n\n\"$group[$i] - $group_cname[$i]\"\n\""._QXZ("INTERVAL BREAKDOWN").":\"\n";
			$CSV_subreports.="\""._QXZ("INTERVAL")."\",\""._QXZ("TOTAL CALLS")."\",\""._QXZ("SYSTEM RELEASE CALLS")."\",\""._QXZ("AGENT RELEASE CALLS")."\",\""._QXZ("SALE CALLS")."\",\""._QXZ("DNC CALLS")."\",\""._QXZ("NO ANSWER PERCENT")."\",\""._QXZ("DROP PERCENT")."\",\""._QXZ("AGENT LOGIN TIME (H:M:S)")."\",\""._QXZ("AGENT PAUSE TIME(H:M:S)")."\"\n";

			######## GRAPHING #########
			###########################

			$h=0; $z=0;
			$SUBgraph_stats=array();
			while ($h < $interval_count)
				{
				if ($Hcalltime[$h] > 0)
					{
					if (strlen($Hcalls_count[$h]) < 1)			{$Hcalls_count[$h] = 0;}
					if (strlen($Hsystem_count[$h]) < 1)			{$Hsystem_count[$h] = 0;}
					if (strlen($Hagent_count[$h]) < 1)			{$Hagent_count[$h] = 0;}
					if (strlen($Hptp_count[$h]) < 1)			{$Hptp_count[$h] = 0;}
					if (strlen($Hrtp_count[$h]) < 1)			{$Hrtp_count[$h] = 0;}
					if (strlen($Hna_count[$h]) < 1)				{$Hna_count[$h] = 0;}
					if (strlen($Hanswer_count[$h]) < 1)			{$Hanswer_count[$h] = 0;}
					if (strlen($Hagent_sec[$h]) < 1)			{$Hagent_sec[$h] = 0;}
					if (strlen($Hpause_sec[$h]) < 1)			{$Hpause_sec[$h] = 0;}
					if (strlen($Htalk_sec[$h]) < 1)				{$Htalk_sec[$h] = 0;}
					if (strlen($Hqueue_seconds[$h]) < 1)		{$Hqueue_seconds[$h] = 0;}
					if (strlen($Hmax_queue_seconds[$h]) < 1)	{$Hmax_queue_seconds[$h] = 0;}
					if (strlen($Hdrop_count[$h]) < 1)			{$Hdrop_count[$h] = 0;}

					if ( ($Hcalls_count_IN[$h] > 0) and ($Hdrop_count_OUT[$h] > 0) )
						{
						$Hdrop_count[$h] = ($Hdrop_count[$h] - $Hcalls_count_IN[$h]);
						$Hcalls_count[$h] = ($Hcalls_count[$h] - $Hcalls_count_IN[$h]);
						$Hsystem_count[$h] = ($Hsystem_count[$h] - $Hcalls_count_IN[$h]);
						if ($Hdrop_count[$h] < 0)
							{$Hdrop_count[$h] = 0;}
						}
					$hTOTcalls_count =			($hTOTcalls_count + $Hcalls_count[$h]);
					$hTOTsystem_count =			($hTOTsystem_count + $Hsystem_count[$h]);
					$hTOTagent_count =			($hTOTagent_count + $Hagent_count[$h]);
					$hTOTptp_count =			($hTOTptp_count + $Hptp_count[$h]);
					$hTOTrtp_count =			($hTOTrtp_count + $Hrtp_count[$h]);
					$hTOTna_count =				($hTOTna_count + $Hna_count[$h]);
					$hTOTanswer_count =			($hTOTanswer_count + $Hanswer_count[$h]);
					$hTOTagent_sec =			($hTOTagent_sec + $Hagent_sec[$h]);
					$hTOTpause_sec =			($hTOTpause_sec + $Hpause_sec[$h]);
					$hTOTtalk_sec =				($hTOTtalk_sec + $Htalk_sec[$h]);
					$hTOTqueue_seconds =		($hTOTqueue_seconds + $Hqueue_seconds[$h]);
					$hTOTdrop_count =			($hTOTdrop_count + $Hdrop_count[$h]);
					if ($Hmax_queue_seconds[$h] > $hTOTmax_queue_seconds)
						{$hTOTmax_queue_seconds = $Hmax_queue_seconds[$h];}

					$Htalk_avg[$h] = MathZDC($Htalk_sec[$h], $Hanswer_count[$h]);
					$Hqueue_avg[$h] = MathZDC($Hqueue_seconds[$h], $Hcalls_count[$h]);

					if ($Hcalls_count[$h]>$SUBmax_calls) {$SUBmax_calls=$Hcalls_count[$h];}
					if ($Hsystem_count[$h]>$SUBmax_system_release) {$SUBmax_system_release=$Hsystem_count[$h];}
					if ($Hagent_count[$h]>$SUBmax_agent_release) {$SUBmax_agent_release=$Hagent_count[$h];}
					if ($Hptp_count[$h]>$SUBmax_sales) {$SUBmax_sales=$Hptp_count[$h];}
					if ($Hrtp_count[$h]>$SUBmax_dncs) {$SUBmax_dncs=$Hrtp_count[$h];}
					if (trim($hNApercent)>$SUBmax_nas) {$SUBmax_nas=trim($hNApercent);}
					if (trim($hDROPpercent)>$SUBmax_drops) {$SUBmax_drops=trim($hDROPpercent);}
					if ($Hagent_sec[$h]>$SUBmax_login_time) {$SUBmax_login_time=$Hagent_sec[$h];}
					if ($Hpause_sec[$h]>$SUBmax_pause_time) {$SUBmax_pause_time=$Hpause_sec[$h];}
					$SUBgraph_stats[$z][0]="$Hcalltime_HHMM[$h]";
					$SUBgraph_stats[$z][1]=$Hcalls_count[$h];
					$SUBgraph_stats[$z][2]=$Hsystem_count[$h];
					$SUBgraph_stats[$z][3]=$Hagent_count[$h];
					$SUBgraph_stats[$z][4]=$Hptp_count[$h];
					$SUBgraph_stats[$z][5]=$Hrtp_count[$h];
					$SUBgraph_stats[$z][8]=$Hagent_sec[$h];
					$SUBgraph_stats[$z][9]=$Hpause_sec[$h];

					$Hagent_sec[$h] =			sec_convert($Hagent_sec[$h],'H'); 
					$Hpause_sec[$h] =			sec_convert($Hpause_sec[$h],'H'); 
					$Htalk_sec[$h] =			sec_convert($Htalk_sec[$h],'H'); 
					$Htalk_avg[$h] =			sec_convert($Htalk_avg[$h],'H'); 
					$Hqueue_seconds[$h] =		sec_convert($Hqueue_seconds[$h],'H'); 
					$Hqueue_avg[$h] =			sec_convert($Hqueue_avg[$h],'H'); 
					$Hmax_queue_seconds[$h] =	sec_convert($Hmax_queue_seconds[$h],'H');
					
					$hTOTALcalls =	sprintf("%6s", $Hcalls_count[$h]);
					$hSYSTEMcalls =	sprintf("%6s", $Hsystem_count[$h]);
					$hAGENTcalls =	sprintf("%6s", $Hagent_count[$h]);
					$hPTPcalls =	sprintf("%6s", $Hptp_count[$h]);
					$hRTPcalls =	sprintf("%6s", $Hrtp_count[$h]);
					$hNApercent = ( MathZDC($Hna_count[$h], $Hcalls_count[$h]) * 100);
					$hNApercent =		sprintf("%6.2f",$hNApercent);
					$hNAcalls =		sprintf("%6s", $Hna_count[$h]);
					$hANSWERcalls =	sprintf("%6s", $Hanswer_count[$h]);
					$hSUMagent =	sprintf("%10s", $Hagent_sec[$h]);
					$hSUMpause =	sprintf("%10s", $Hpause_sec[$h]);
					$hSUMtalk =		sprintf("%9s", $Htalk_sec[$h]);
					$hAVGtalk =		sprintf("%7s", $Htalk_avg[$h]);
					$hSUMqueue =	sprintf("%9s", $Hqueue_seconds[$h]);
					$hAVGqueue =	sprintf("%7s", $Hqueue_avg[$h]);
					$hMAXqueue =	sprintf("%7s", $Hmax_queue_seconds[$h]);
					$hDROPpercent = ( MathZDC($Hdrop_count[$h], $Hcalls_count[$h]) * 100);
					$hDROPpercent =		sprintf("%6.2f",$hDROPpercent);
					$hDROPcalls =	sprintf("%6s", $Hdrop_count[$h]);
					$hPRINT =		sprintf("%19s", $Hcalltime_HHMM[$h]);

					$SUB_ASCII_text .= "| $hPRINT | $hTOTALcalls | $hSYSTEMcalls | $hAGENTcalls | $hPTPcalls | $hRTPcalls | $hNApercent%| $hDROPpercent%| $hSUMagent | $hSUMpause |\n";
					$CSV_subreports.="\"$hPRINT\",\"$hTOTALcalls\",\"$hSYSTEMcalls\",\"$hAGENTcalls\",\"$hPTPcalls\",\"$hRTPcalls\",\"$hNApercent%\",\"$hDROPpercent%\",\"$hSUMagent\",\"$hSUMpause\"\n";
					if ($DB) {$SUB_ASCII_text .= " $hDROPcalls($Hcalls_count_IN[$h]/$Hdrop_count_OUT[$h]) |\n";}

					$SUBgraph_stats[$z][6]=trim($hNApercent);
					$SUBgraph_stats[$z][7]=trim($hDROPpercent);
					$z++;
					}

				$h++;
				}

			$hTOTtalk_avg = MathZDC($hTOTtalk_sec, $hTOTanswer_count);
			$hTOTqueue_avg = MathZDC($hTOTqueue_seconds, $hTOTcalls_count);

			$hTOTagent_sec =			sec_convert($hTOTagent_sec,'H'); 
			$hTOTpause_sec =			sec_convert($hTOTpause_sec,'H'); 
			$hTOTtalk_sec =				sec_convert($hTOTtalk_sec,'H'); 
			$hTOTtalk_avg =				sec_convert($hTOTtalk_avg,'H'); 
			$hTOTqueue_seconds =		sec_convert($hTOTqueue_seconds,'H'); 
			$hTOTqueue_avg =			sec_convert($hTOTqueue_avg,'H'); 
			$hTOTmax_queue_seconds =	sec_convert($hTOTmax_queue_seconds,'H'); 

			$hTOTcalls_count =			sprintf("%6s", $hTOTcalls_count);
			$hTOTsystem_count =			sprintf("%6s", $hTOTsystem_count);
			$hTOTagent_count =			sprintf("%6s", $hTOTagent_count);
			$hTOTptp_count =			sprintf("%6s", $hTOTptp_count);
			$hTOTrtp_count =			sprintf("%6s", $hTOTrtp_count);
			$hTOTna_percent = ( MathZDC($hTOTna_count, $hTOTcalls_count) * 100);
			$hTOTna_percent =			sprintf("%6.2f",$hTOTna_percent);
			$hTOTna_count =				sprintf("%6s", $hTOTna_count);
			$hTOTanswer_count =			sprintf("%6s", $hTOTanswer_count);
			$hTOTagent_sec =			sprintf("%10s", $hTOTagent_sec);
			$hTOTpause_sec =			sprintf("%10s", $hTOTpause_sec);
			$hTOTtalk_sec =				sprintf("%9s", $hTOTtalk_sec);
			$hTOTtalk_avg =				sprintf("%7s", $hTOTtalk_avg);
			$hTOTqueue_seconds =		sprintf("%9s", $hTOTqueue_seconds);
			$hTOTqueue_avg =			sprintf("%7s", $hTOTqueue_avg);
			$hTOTmax_queue_seconds =	sprintf("%7s", $hTOTmax_queue_seconds);
			$hTOTdrop_percent = ( MathZDC($hTOTdrop_count, $hTOTcalls_count) * 100);
			$hTOTdrop_percent =			sprintf("%6.2f",$hTOTdrop_percent);
			$hTOTdrop_count =			sprintf("%6s", $hTOTdrop_count);

			$SUB_ASCII_text .= "+---------------------+--------+--------+--------+--------+--------+--------+--------+------------+------------+\n";
			$SUB_ASCII_text .= "| "._QXZ("TOTALS",19)." | $hTOTcalls_count | $hTOTsystem_count | $hTOTagent_count | $hTOTptp_count | $hTOTrtp_count | $hTOTna_percent%| $hTOTdrop_percent%| $hTOTagent_sec | $hTOTpause_sec |\n";
			$SUB_ASCII_text .= "+---------------------+--------+--------+--------+--------+--------+--------+--------+------------+------------+\n";
			$CSV_subreports.="\""._QXZ("TOTALS")."\",\"$hTOTcalls_count\",\"$hTOTsystem_count\",\"$hTOTagent_count\",\"$hTOTptp_count\",\"$hTOTrtp_count\",\"$hTOTna_percent%\",\"$hTOTdrop_percent%\",\"$hTOTagent_sec\",\"$hTOTpause_sec\"\n";

			# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
			$multigraph_text="";
			$graph_id++;

			$graph_array=array("OSI_CALLSdata$group[$i]|1|CALLS|integer|", "OSI_SYSTEMRELEASEdata$group[$i]|2|SYSTEM RELEASE CALLS|integer|", "OSI_AGENTRELEASEdata$group[$i]|3|AGENT RELEASE CALLS|integer|", "OSI_SALECALLSdata$group[$i]|4|SALE CALLS|integer|", "OSI_DNCCALLSdata$group[$i]|5|DNC CALLS|integer|", "OSI_NOANSWERPERCENTdata$group[$i]|6|NO ANSWER PERCENT|percent|", "OSI_DROPPERCENTdata$group[$i]|7|DROP PERCENT|percent|", "OSI_AGENTLOGINdata$group[$i]|8|AGENT LOGIN TIME|time|", "OSI_AGENTPAUSEdata$group[$i]|9|AGENT PAUSE TIME|time|");
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
				for ($d=0; $d<count($SUBgraph_stats); $d++) {
					$labels.="\"".preg_replace('/ +/', ' ', $SUBgraph_stats[$d][0])."\",";
					$data.="\"".$SUBgraph_stats[$d][$dataset_index]."\",";
					$current_graph_total+=$SUBgraph_stats[$d][$dataset_index];
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
			$graph_title="$group[$i] - $group_cname[$i] "._QXZ("INTERVAL BREAKDOWN");
			include("graphcanvas.inc");
			$HEADER.=$HTML_graph_head;
			$SUB_HTML_text.=$graphCanvas;

			$i++;
		}
		$rawTOTtalk_sec = $TOTtalk_sec;
		$rawTOTtalk_min = round(MathZDC($rawTOTtalk_sec, 60));

		$TOTtalk_avg = MathZDC($TOTtalk_sec, $TOTanswer_count);
		$TOTqueue_avg = MathZDC($TOTqueue_seconds, $TOTcalls_count);

		$TOTagent_sec =			sec_convert($TOTagent_sec,'H'); 
		$TOTpause_sec =			sec_convert($TOTpause_sec,'H'); 
		$TOTtalk_sec =			sec_convert($TOTtalk_sec,'H'); 
		$TOTtalk_avg =			sec_convert($TOTtalk_avg,'H'); 
		$TOTqueue_seconds =		sec_convert($TOTqueue_seconds,'H'); 
		$TOTqueue_avg =			sec_convert($TOTqueue_avg,'H'); 
		$TOTmax_queue_seconds =	sec_convert($TOTmax_queue_seconds,'H'); 

		$i =					sprintf("%4s", $i);
		$TOTcalls_count =		sprintf("%6s", $TOTcalls_count);
		$TOTsystem_count =		sprintf("%6s", $TOTsystem_count);
		$TOTagent_count =		sprintf("%6s", $TOTagent_count);
		$TOTptp_count =			sprintf("%6s", $TOTptp_count);
		$TOTrtp_count =			sprintf("%6s", $TOTrtp_count);
		$TOTna_percent = ( MathZDC($TOTna_count, $TOTcalls_count) * 100);
		$TOTna_percent =		sprintf("%6.2f",$TOTna_percent);
		$TOTna_count =			sprintf("%6s", $TOTna_count);
		$TOTanswer_count =		sprintf("%6s", $TOTanswer_count);
		$TOTagent_sec =			sprintf("%10s", $TOTagent_sec);
		$TOTpause_sec =			sprintf("%10s", $TOTpause_sec);
		$TOTtalk_sec =			sprintf("%9s", $TOTtalk_sec);
		$TOTtalk_avg =			sprintf("%7s", $TOTtalk_avg);
		$TOTqueue_seconds =		sprintf("%9s", $TOTqueue_seconds);
		$TOTqueue_avg =			sprintf("%7s", $TOTqueue_avg);
		$TOTmax_queue_seconds =	sprintf("%7s", $TOTmax_queue_seconds);
		$TOTdrop_percent = ( MathZDC($TOTdrop_count, $TOTcalls_count) * 100);
		$TOTdrop_percent =		sprintf("%6.2f",$TOTdrop_percent);
		$TOTdrop_count =		sprintf("%6s", $TOTdrop_count);

		$ASCII_text .= "+------------------------------------------+--------+--------+--------+--------+--------+--------+--------+------------+------------+\n";
		$ASCII_text .= "| "._QXZ("TOTALS",12)." "._QXZ("Campaigns",9).": $i             | $TOTcalls_count | $TOTsystem_count | $TOTagent_count | $TOTptp_count | $TOTrtp_count | $TOTna_percent%| $TOTdrop_percent%| $TOTagent_sec | $TOTpause_sec |\n";
		$ASCII_text .= "+------------------------------------------+--------+--------+--------+--------+--------+--------+--------+------------+------------+\n";
		$CSV_main.="\""._QXZ("TOTALS")."       "._QXZ("Campaigns").": $i\",\"$TOTcalls_count\",\"$TOTsystem_count\",\"$TOTagent_count\",\"$TOTptp_count\",\"$TOTrtp_count\",\"$TOTna_percent%\",\"$TOTdrop_percent%\",\"$TOTagent_sec\",\"$TOTpause_sec\"\n";
		}

	if ($costformat > 0)
		{
		$ASCII_text.="</PRE>\n<B>";
		$inbound_cost = ($rawTOTtalk_min * $inbound_rate);
		$inbound_cost =		sprintf("%8.2f", $inbound_cost);

		$ASCII_text.=_QXZ("INBOUND")." $query_date "._QXZ("to")." $end_date, &nbsp; $rawTOTtalk_min "._QXZ("minutes at")." \$$inbound_rate = \$$inbound_cost\n";

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

	# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
	$multigraph_text="";
	$graph_id++;

	$graph_array=array("OSI_CALLSdata|1|CALLS|integer|", "OSI_SYSTEMRELEASEdata|2|SYSTEM RELEASE CALLS|integer|", "OSI_AGENTRELEASEdata|3|AGENT RELEASE CALLS|integer|", "OSI_SALECALLSdata|4|SALE CALLS|integer|", "OSI_DNCCALLSdata|5|DNC CALLS|integer|", "OSI_NOANSWERPERCENTdata|6|NO ANSWER PERCENT|percent|", "OSI_DROPPERCENTdata|7|DROP PERCENT|percent|", "OSI_AGENTLOGINdata|8|AGENT LOGIN TIME|time|", "OSI_AGENTPAUSEdata|9|AGENT PAUSE TIME|time|");
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
	$graph_title=_QXZ("MULTI-CAMPAIGN BREAKDOWN");
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas;

	}

	if ($file_download>0) {
		$CSVfilename = "AST_OUTBOUNDsummary_interval$US$FILE_TIME.csv";
		$CSV_text=$CSV_main.$CSV_subreports;
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
		$JS_onload.="}\n";
		if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
		$JS_text.="</script>\n";
		if ($report_display_type=="HTML")
			{
			$MAIN.=$GRAPH_text;
			$SUBoutput.=$SUB_HTML_text;
			$SUBoutput.=$JS_text;
			}
		else
			{
			$MAIN.=$ASCII_text;
			$SUBoutput.=$SUB_ASCII_text;
			}

		echo "$HEADER";
		require("admin_header.php");
		echo "$MAIN";
		echo "$SUBoutput";
		$ENDtime = date("U");
		$RUNtime = ($ENDtime - $STARTtime);
		echo "\n\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
		echo "</PRE>";
		echo "</TD></TR></TABLE>";

		echo "</BODY></HTML>";
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
