<?php 
# fcstats_detail.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 70813-1526 - First Build
# 71008-1436 - Added shift to be defined in dbconnect_mysqli.php
# 71217-1128 - Changed method for calculating stats
# 71228-1140 - added percentages, cross-day start/stop
# 80328-1139 - adapted for basic fronter/closer stats
# 90310-2132 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110703-1828 - Added download option
# 111104-1213 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 120705-2007 - Changed SALES to use sales status flag
# 130414-0126 - Added report logging
# 130610-0948 - Finalized changing of all ereg instances to preg
# 130619-2339 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-1930 - Changed to mysqli PHP functions
# 140108-0725 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0017 - Finalized adding QXZ translation to all admin files
# 141230-1345 - Added code for on-the-fly language translations display
# 150516-1317 - Fixed Javascript element problem, Issue #857
# 151125-1642 - Added search archive option
# 160211-2249 - Overhauled report calculations and labeling to make the report more accurate and "universal"
# 160227-1131 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 161021-1326 - Rewrote most of this report to show detail records
# 170227-1718 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170809-2115 - Added phone and province to detail output, fixed ASCII display issues
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180508-2215 - Added new help display
# 190216-0808 - Fix for user-group, in-group and campaign allowed/permissions matching issues
# 191013-0851 - Fixes for PHP7
# 220228-1955 - Added allow_web_debug system setting
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["show_summary"]))				{$show_summary=$_GET["show_summary"];}
	elseif (isset($_POST["show_summary"]))		{$show_summary=$_POST["show_summary"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
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
$HTML_text='';
if (!isset($user_group)) {$user_group = array();}
if (!isset($campaign)) {$campaign = array();}
if (!isset($users)) {$users = array();}
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $query_date;}
if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Fronter - Closer Detail Report';
$db_source = 'M';

$JS_text="<script language='Javascript'>\n";
$JS_text.="function openNewWindow(url)\n";
$JS_text.="  {\n";
$JS_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$JS_text.="  }\n";
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
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$shift = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$shift);
$show_summary = preg_replace('/[^-_0-9a-zA-Z]/', '', $show_summary);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);

# Variables filtered further down in the code
# $user_group
# $campaign
# $users
# $group

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_log", "vicidial_xfer_log", "vicidial_closer_log", "vicidial_agent_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_log_table=use_archive_table("vicidial_log");
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	$vicidial_xfer_log_table=use_archive_table("vicidial_xfer_log");
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	}
else
	{
	$vicidial_log_table="vicidial_log";
	$vicidial_agent_log_table="vicidial_agent_log";
	$vicidial_xfer_log_table="vicidial_xfer_log";
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


$stmt="select group_id from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
$groups_string='|';
$groups=array();
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	$groups_string .= "$groups[$i]|";
	$i++;
	}

$stmt="SELECT campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$campaigns=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$campaigns[$i] =$row[0];
	if (preg_match('/\-ALL/',$campaign_string) )
		{$campaign[$i] = $campaigns[$i];}
	$i++;
	}

for ($i=0; $i<count($user_group); $i++)
	{
	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1; $user_group=array("--ALL--");}
	}

$stmt="SELECT user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
#$user_groups[$i]="Auto-dial agents";
#$i++;
$user_groups=array();
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	#if ($all_user_groups) {$user_group[$i]=$row[0];}
	$i++;
	}

$stmt="SELECT user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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
$campaign_string='|';
$campaign_ct = count($campaign);
while($i < $campaign_ct)
	{
	$campaign[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $campaign[$i]);
	if ( (preg_match("/ $campaign[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$campaign_string .= "$campaign[$i]|";
		$campaign_SQL .= "'$campaign[$i]',";
		$campaignQS .= "&campaign[]=$campaign[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$campaign_string) ) or ($campaign_ct < 1) )
	{
	$campaign_SQL = "";
	$campaign_agent_log_SQL = "";
	$campaign_vicidial_log_SQL = "";
	$campaign_closer_log_SQL = "";
	}
else
	{
	$campaign_SQL = preg_replace('/,$/i', '',$campaign_SQL);
	$campaign_agent_log_SQL = "and ".$vicidial_agent_log_table.".campaign_id IN($campaign_SQL)";
	$campaign_vicidial_log_SQL = "and ".$vicidial_log_table.".campaign_id IN($campaign_SQL)";
	$campaign_closer_log_SQL = "and ".$vicidial_closer_log_table.".campaign_id IN($campaign_SQL)";
	$campaign_SQL = "and campaign_id IN($campaign_SQL)";
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
	{
	$user_group_SQL = "";
	$user_group_agent_log_SQL = "";
	$user_group_vicidial_log_SQL = "";
	$user_group_closer_log_SQL = "";
	}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
#	$user_group_SQL = preg_replace("/\'Auto\-dial agents\'/i", "null",$user_group_SQL);
	$user_group_agent_log_SQL = "and ".$vicidial_agent_log_table.".user_group IN($user_group_SQL)";
	$user_group_vicidial_log_SQL = "and ".$vicidial_log_table.".user_group IN($user_group_SQL)";
	$user_group_closer_log_SQL = "and ".$vicidial_closer_log_table.".user_group IN($user_group_SQL)";
	$user_group_SQL = "and user_group IN($user_group_SQL)";
	}

$i=0;
$users_string='|';
$user_ct = count($users);
while($i < $user_ct)
	{
	$users[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $users[$i]);
	$users_string .= "$users[$i]|";
	$user_SQL .= "'$users[$i]',";
	$userQS .= "&users[]=$users[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$users_string) ) or ($user_ct < 1) )
	{
	$user_SQL = "";
	$user_agent_log_SQL = "";
	$user_vicidial_log_SQL = "";
	$user_xfer_log_SQL = "";
	$user_closer_log_SQL = "";
	}
else
	{
	$user_SQL = preg_replace('/,$/i', '',$user_SQL);
	$user_SQL = preg_replace("/\'VDAD\',/", "'VDAD','VDCL',", $user_SQL);
	$user_agent_log_SQL = "and ".$vicidial_agent_log_table.".user IN($user_SQL)";
	$user_vicidial_log_SQL = "and ".$vicidial_log_table.".user IN($user_SQL)";
	$user_xfer_log_SQL = "and ".$vicidial_xfer_log_table.".user IN($user_SQL)";
	$user_closer_log_SQL = "and ".$vicidial_closer_log_table.".user IN($user_SQL)";
	$user_SQL = "and user IN($user_SQL)";
	}

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
$HTML_head.="\n";

$HTML_head.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_head.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_head.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HTML_head.="<script src='chart/Chart.js'>Chart.defaults.global.defaultFontSize = 10;</script>\n"; 
$HTML_head.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

#	require("admin_header.php");

$HTML_text.="<b>"._QXZ("$report_name")."</b> $NWB#fcstats$NWE\n";
$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD>"._QXZ("Date").":<BR>";

$HTML_text.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'query_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script> <BR>"._QXZ("to").":<BR>\n";

$HTML_text.="<INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";
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

$HTML_text.="<BR><BR>";
$HTML_text.=_QXZ("Display").": <select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select><BR><BR>\n";
$HTML_text.=_QXZ("Shift").": <SELECT SIZE=1 NAME=shift>\n";
$HTML_text.="<option selected value=\"$shift\">"._QXZ("$shift")."</option>\n";
$HTML_text.="<option value=\"\">--</option>\n";
$HTML_text.="<option value=\"AM\">"._QXZ("AM")."</option>\n";
$HTML_text.="<option value=\"PM\">"._QXZ("PM")."</option>\n";
$HTML_text.="<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
$HTML_text.="</SELECT>\n";

$HTML_text.="</td><td valign=TOP>";
$HTML_text.=_QXZ("Ingroup").": <BR><SELECT SIZE=5 NAME=group[] multiple>\n";
	$o=0;
	while ($groups_to_print > $o)
	{
		if (in_array($groups[$o], $group)) {$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
		  else {$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
		$o++;
	}
$HTML_text.="</SELECT>\n";

$HTML_text.="</td>";

$HTML_text.="<TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=campaign[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$campaign_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
{
	if (preg_match("/\|$campaigns[$o]\|/i",$campaign_string) && !preg_match('/\-\-ALL\-\-/',$campaign_string)) {$HTML_text.="<option selected value=\"$campaigns[$o]\">$campaigns[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$campaigns[$o]\">$campaigns[$o]</option>\n";}
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
	if  (preg_match("/\|$user_groups[$o]\|/i",$user_group_string) && !preg_match('/\-\-ALL\-\-/',$user_group_string)) {$HTML_text.="<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
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
	if  (preg_match("/\|$user_list[$o]\|/i",$users_string) && !preg_match('/\-\-ALL\-\-/',$users_string)) {$HTML_text.="<option selected value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>";

$HTML_text.="<TD align='center'><INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
if ($archives_available=="Y") 
	{
	$HTML_text.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR><BR>\n";
	}
$HTML_text.="<input type='checkbox' name='show_summary' value='checked' $show_summary>"._QXZ("Show fronter/closer summary")."<BR><BR>\n";
$HTML_text.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR><BR>\n";
$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><a href=\"$PHP_SELF?query_date=$query_date&end_date=$end_date&group[]=" . implode("&group[]=", array_map(array($link, 'real_escape_string'), $group)) . "&shift=$shift&file_download=1&search_archived_data=$search_archived_data&show_summary=$show_summary$campaignQS$user_groupQS$userQS\">"._QXZ("DOWNLOAD")."</a> | <a href=\"./admin.php?ADD=3111&group_id=$group\">"._QXZ("MODIFY")."</a> | <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a><BR/></FONT>\n";
$HTML_text.="</TD>";


$HTML_text.="</TR></TABLE>\n";
$HTML_text.="</FORM>\n\n";

$HTML_text.="<PRE><FONT SIZE=2>\n\n";


if (!$group)
{
$HTML_text.="\n\n";
$HTML_text.=_QXZ("PLEASE SELECT AN IN-GROUP AND DATE ABOVE THEN CLICK SUBMIT")."\n";
}

else
{
#	$time_BEGIN=$AM_shift_BEGIN;
#	$time_END=$AM_shift_END;
#$query_date_BEGIN = "$query_date $time_BEGIN";   
#$query_date_END = "$query_date $time_END";

$Cqdate = explode('-',$query_date);
$Cedate = explode('-',$end_date);

if ($shift == 'AM') 
	{
	$query_date_BEGIN = date("Y-m-d H:i:s", mktime(1, 0, 0, $Cqdate[1], $Cqdate[2], $Cqdate[0]));
	$query_date_END = date("Y-m-d H:i:s", mktime(17, 45, 0, $Cedate[1], $Cedate[2], $Cedate[0]));
	}
if ($shift == 'PM') 
	{
	$query_date_BEGIN = date("Y-m-d H:i:s", mktime(17, 45, 1, $Cqdate[1], $Cqdate[2], $Cqdate[0]));
	$query_date_END = date("Y-m-d H:i:s", mktime(24, 59, 59, $Cedate[1], $Cedate[2], $Cedate[0]));
	}
if ($shift == 'ALL') 
	{
	$query_date_BEGIN = date("Y-m-d H:i:s", mktime(1, 0, 0, $Cqdate[1], $Cqdate[2], $Cqdate[0]));
	$query_date_END = date("Y-m-d H:i:s", mktime(24, 59, 59, $Cedate[1], $Cedate[2], $Cedate[0]));
	}

### Combine campaign, date, user_group, and user to get list of available users based on new parameters
if ($campaign_ct>0 || $user_group_ct>0 || $user_ct>0) {
	$total_agent_stmt="select distinct user from ".$vicidial_agent_log_table." where event_time >= '$query_date_BEGIN' and event_time <= '$query_date_END' $campaign_agent_log_SQL $user_group_agent_log_SQL $user_group_agent_log_SQL order by user";
	$total_agent_rslt=mysql_to_mysqli($total_agent_stmt, $link);
	$total_user_SQL="";
	if ($DB) {echo "$total_agent_stmt\n";}
	while ($agent_row=mysqli_fetch_row($total_agent_rslt)) {
		$total_user_SQL .= "'$agent_row[0]',";
	}
	$total_user_SQL = preg_replace('/,$/i', '',$total_user_SQL);
	$summary_user_xfer_log_SQL = "and ".$vicidial_xfer_log_table.".user IN($total_user_SQL)";
	$summary_user_closer_log_SQL = "and ".$vicidial_closer_log_table.".user IN($total_user_SQL)";
}


#### LOG SECTION
if ($campaign_ct>0 || $user_group_ct>0 || $user_ct>0) {
	/*
	# xfers originating from outbound calls
	$xfer_stmt="select ".$vicidial_log_table.".call_date, ".$vicidial_log_table.".lead_id, ".$vicidial_log_table.".user, ".$vicidial_log_table.".user_group, ".$vicidial_log_table.".campaign_id, ".$vicidial_xfer_log_table.".campaign_id, ".$vicidial_agent_log_table.".campaign_id, ".$vicidial_closer_log_table.".campaign_id, ".$vicidial_closer_log_table.".user, ".$vicidial_closer_log_table.".user_group, ".$vicidial_closer_log_table.".call_date, ".$vicidial_closer_log_table.".uniqueid from ".$vicidial_log_table.", ".$vicidial_xfer_log_table.", ".$vicidial_agent_log_table.", ".$vicidial_closer_log_table." where ".$vicidial_log_table.".call_date>='$query_date_BEGIN' and ".$vicidial_log_table.".call_date<='$query_date_END' and ".$vicidial_log_table.".lead_id=".$vicidial_xfer_log_table.".lead_id and ".$vicidial_xfer_log_table.".campaign_id='" . mysqli_real_escape_string($link, $group) . "' and ".$vicidial_xfer_log_table.".call_date>='$query_date_BEGIN' and ".$vicidial_xfer_log_table.".call_date<='$query_date_END' and ".$vicidial_xfer_log_table.".xfercallid=".$vicidial_closer_log_table.".xfercallid and ".$vicidial_xfer_log_table.".lead_id=".$vicidial_closer_log_table.".lead_id and ".$vicidial_xfer_log_table.".closer=".$vicidial_closer_log_table.".user and ".$vicidial_closer_log_table.".call_date>='$query_date_BEGIN' and ".$vicidial_closer_log_table.".call_date<='$query_date_END' and ".$vicidial_closer_log_table.".uniqueid=".$vicidial_agent_log_table.".uniqueid and ".$vicidial_agent_log_table.".event_time>='$query_date_BEGIN' and ".$vicidial_agent_log_table.".event_time<='$query_date_END' and timediff(".$vicidial_xfer_log_table.".call_date, ".$vicidial_log_table.".call_date)<'00:30:00' and timediff(".$vicidial_xfer_log_table.".call_date, ".$vicidial_log_table.".call_date)>='0' $user_agent_log_SQL $user_vicidial_log_SQL $user_xfer_log_SQL $user_closer_log_SQL $user_group_agent_log_SQL $user_group_vicidial_log_SQL $user_group_closer_log_SQL $campaign_agent_log_SQL $campaign_vicidial_log_SQL";
	if ($DB) {echo "<B>$xfer_stmt</B>\n";}
	$xfer_rslt=mysql_to_mysqli($xfer_stmt, $link);
	while ($xfer_row=mysqli_fetch_row($xfer_rslt)) {
		array_push($complete_xfer_log, $xfer_row);
	}

	# xfers originating from inbound calls
	$xfer_stmt1="select ".$vicidial_closer_log_table.".call_date, ".$vicidial_closer_log_table.".lead_id, ".$vicidial_closer_log_table.".user, ".$vicidial_closer_log_table.".user_group, ".$vicidial_agent_log_table.".campaign_id, ".$vicidial_closer_log_table.".campaign_id as ingroup, ".$vicidial_xfer_log_table.".xfercallid from ".$vicidial_closer_log_table.", ".$vicidial_agent_log_table.", ".$vicidial_xfer_log_table." where ".$vicidial_xfer_log_table.".campaign_id='" . mysqli_real_escape_string($link, $group) . "' and ".$vicidial_closer_log_table.".call_date>='$query_date_BEGIN' and ".$vicidial_closer_log_table.".call_date<='$query_date_END' and ".$vicidial_closer_log_table.".lead_id=".$vicidial_xfer_log_table.".lead_id and ".$vicidial_closer_log_table.".xfercallid=0 and ".$vicidial_closer_log_table.".user=".$vicidial_xfer_log_table.".user and ".$vicidial_xfer_log_table.".call_date>='$query_date_BEGIN' and ".$vicidial_xfer_log_table.".call_date<='$query_date_END' and ".$vicidial_closer_log_table.".uniqueid=".$vicidial_agent_log_table.".uniqueid and ".$vicidial_agent_log_table.".event_time>='$query_date_BEGIN' and ".$vicidial_agent_log_table.".event_time<='$query_date_END' and timediff(".$vicidial_xfer_log_table.".call_date, ".$vicidial_closer_log_table.".call_date)<'00:30:00' and timediff(".$vicidial_xfer_log_table.".call_date, ".$vicidial_closer_log_table.".call_date)>='0' $user_agent_log_SQL $user_xfer_log_SQL $user_closer_log_SQL $user_group_agent_log_SQL $user_group_closer_log_SQL $campaign_agent_log_SQL";
	if ($DB) {echo "<B>$xfer_stmt1</B>\n";}
	$xfer_rslt1=mysql_to_mysqli($xfer_stmt1, $link);
	while ($xfer_row1=mysqli_fetch_row($xfer_rslt1)) {
		$temp_array=array();
		$xfercallid=$xfer_row1[6];
		array_push($temp_array, "$xfer_row1[0]", "$xfer_row1[1]", "$xfer_row1[2]", "$xfer_row1[3]", "$xfer_row1[4]", "$xfer_row1[5]");

		$xfer_stmt2="select ".$vicidial_agent_log_table.".campaign_id, ".$vicidial_closer_log_table.".campaign_id, ".$vicidial_closer_log_table.".user, ".$vicidial_closer_log_table.".user_group, ".$vicidial_closer_log_table.".call_date from ".$vicidial_agent_log_table.", ".$vicidial_xfer_log_table.", ".$vicidial_closer_log_table." where ".$vicidial_xfer_log_table.".xfercallid='$xfercallid' and ".$vicidial_xfer_log_table.".lead_id=".$vicidial_closer_log_table.".lead_id and ".$vicidial_xfer_log_table.".closer=".$vicidial_closer_log_table.".user and ".$vicidial_closer_log_table.".uniqueid=".$vicidial_agent_log_table.".uniqueid and ".$vicidial_closer_log_table.".xfercallid='$xfercallid' and ".$vicidial_agent_log_table.".event_time>='$query_date_BEGIN' and ".$vicidial_agent_log_table.".event_time<='$query_date_END'";
		if ($DB) {echo "<B>$xfer_stmt2</B>\n";}
		$xfer_rslt2=mysql_to_mysqli($xfer_stmt2, $link);
		while ($xfer_row2=mysqli_fetch_row($xfer_rslt2)) {
			array_push($temp_array, "$xfer_row2[0]", "$xfer_row2[1]", "$xfer_row2[2]", "$xfer_row2[3]", "$xfer_row2[4]");
		}

		array_push($complete_xfer_log, $temp_array);
	}
	*/

	$complete_xfer_log=array();
	$xfer_log_stmt="select * from vicidial_xfer_log where campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "') and call_date>='$query_date_BEGIN' and call_date<='$query_date_END' $user_SQL";
	if ($DB) {echo "<B>$xfer_log_stmt</B>\n";}
	$xfer_log_rslt=mysql_to_mysqli($xfer_log_stmt, $link);
	while ($xfer_row=mysqli_fetch_array($xfer_log_rslt)) {
		$temp_array=array();
		$xfercallid=$xfer_row["xfercallid"];
		$closer=$xfer_row["closer"];
		$campaign_id="";

		$lead_stmt="select * from vicidial_list where lead_id='$xfer_row[lead_id]'";
		$lead_rslt=mysql_to_mysqli($lead_stmt, $link);
		$lead_row=mysqli_fetch_array($lead_rslt);
		$phone_number=$lead_row["phone_number"];
		$province=$lead_row["province"];

		$fronter_log_stmt="select call_date, user_group, campaign_id from vicidial_log where lead_id='$xfer_row[lead_id]' and user='$xfer_row[user]' and call_date>='$xfer_row[call_date]'-INTERVAL 30 MINUTE and call_date<='$xfer_row[call_date]' $campaign_SQL $user_group_SQL order by call_date desc limit 1";
		$fronter_log_rslt=mysql_to_mysqli($fronter_log_stmt, $link);
		if ($DB) {echo "<B>$fronter_log_stmt</B>\n";}
		if (mysqli_num_rows($fronter_log_rslt)>0) {
			while ($fronter_row=mysqli_fetch_array($fronter_log_rslt)) {
				$call_date=$fronter_row["call_date"];
				$lead_id=$xfer_row["lead_id"];
				$user=$xfer_row["user"];
				$user_group=$fronter_row["user_group"];
				$campaign_id=$fronter_row["campaign_id"];
				$ingroup=$xfer_row["campaign_id"];

				#$uniqueid=$fronter_row["uniqueid"];
				#$agent_log_stmt="select * From vicidial_agent_log where uniqueid='$uniqueid' $campaignSQL";
				#$agent_log_rslt=mysql_to_mysqli($agent_log_stmt, $link);
				#if (mysqli_num_rows($agent_log_rslt)>0) {
				#	$fronter_row=mysqli_fetch_array($agent_log_rslt);
				#	break;
				#}
			}
		} else {
			$closer_log_stmt="select uniqueid, user_group, call_date from vicidial_closer_log where call_date>='$xfer_row[call_date]'-INTERVAL 30 MINUTE and call_date<='$xfer_row[call_date]' and lead_id='$xfer_row[lead_id]' and user='$xfer_row[user]' order by call_date desc limit 1";
			$closer_log_rslt=mysql_to_mysqli($closer_log_stmt, $link);
			if ($DB) {echo "<B>$closer_log_stmt</B>\n";}
			while ($closer_row=mysqli_fetch_array($closer_log_rslt)) {
				$call_date=$closer_row["call_date"];
				$lead_id=$xfer_row["lead_id"];
				$user=$xfer_row["user"];
				$user_group=$closer_row["user_group"];
				$ingroup=$xfer_row["campaign_id"];

				$uniqueid=$closer_row["uniqueid"];
				$agent_log_stmt="select campaign_id From vicidial_agent_log where uniqueid='$uniqueid' and event_time>='$query_date_BEGIN' and event_time<='$query_date_END' $campaign_SQL $user_group_SQL";
				$agent_log_rslt=mysql_to_mysqli($agent_log_stmt, $link);
				if (mysqli_num_rows($agent_log_rslt)>0) {
					$fronter_row=mysqli_fetch_array($agent_log_rslt);
					$campaign_id=$fronter_row["campaign_id"];
					break;
				}
			}
		}

		if ($campaign_id) {
			array_push($temp_array, "$call_date", "$lead_id", "$phone_number", "$province", "$user", "$user_group", "$campaign_id", "$ingroup");

			$closer_xfer_log_stmt="select uniqueid, campaign_id, call_date, status from vicidial_closer_log where campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "') and call_date>='$xfer_row[call_date]' and call_date<='$xfer_row[call_date]'+INTERVAL 1 HOUR and xfercallid='$xfercallid' and user='$closer' $user_SQL $user_group_SQL order by call_date asc limit 1";
			$closer_xfer_log_rslt=mysql_to_mysqli($closer_xfer_log_stmt, $link);
			if ($DB) {echo "<B>$closer_xfer_log_stmt</B>\n";}
			if (mysqli_num_rows($closer_xfer_log_rslt)>0) {
				$closer_xfer_row=mysqli_fetch_array($closer_xfer_log_rslt);
				$closer_uid=$closer_xfer_row["uniqueid"];
				$closer_ingroup=$closer_xfer_row["campaign_id"];
				$closer_call_date=$closer_xfer_row["call_date"];
				$status=$closer_xfer_row["status"];

				$closer_agent_log_stmt="select campaign_id, user_group from vicidial_agent_log where user='$closer' and uniqueid='$closer_uid' and event_time>='$query_date_BEGIN' and event_time<='$query_date_END' $user_SQL $user_group_SQL $campaign_SQL"; #$campaignSQL necessary?
				$closer_agent_log_rslt=mysql_to_mysqli($closer_agent_log_stmt, $link);
				if (mysqli_num_rows($closer_agent_log_rslt)>0) {
					$closer_agent_log_row=mysqli_fetch_array($closer_agent_log_rslt);
					$closer_campaign=$closer_agent_log_row["campaign_id"];
					$closer_user_group=$closer_agent_log_row["user_group"];

					array_push($temp_array, "$closer_campaign", "$closer_ingroup", "$closer", "$closer_user_group", "$closer_call_date", "$status");
					array_push($complete_xfer_log, $temp_array);
				}
			}
		}
	}

}

function cmp($a, $b)
	{
    return strcmp($a[0], $b[0]);
	}

if (count($complete_xfer_log)>0)
	{usort($complete_xfer_log, "cmp");}

if (count($complete_xfer_log)>0) {
	$output_header="+---------------------+-----------+----------------------+----------------------+----------------------+----------------------+------------+----------------------+------------+----------------------+----------------------+----------------------+---------------------+--------+\n";
	$HTML_text.="\n";
	$HTML_text.=$output_header;
	$HTML_text.="| "._QXZ("CALL DATE (INITIAL)", 19)." | "._QXZ("LEAD ID", 9)." | "._QXZ("PHONE NUMBER", 20)." | "._QXZ("PROVINCE", 20)." | "._QXZ("USER", 20)." | "._QXZ("USER GROUP - FRONTER", 20)." | "._QXZ("FRONT CAMP", 10)." | "._QXZ("INBOUND/XFER GROUP", 20)." | "._QXZ("RECVD CAMP", 10)." | "._QXZ("RCVD GROUP", 20)." | "._QXZ("USER", 20)." | "._QXZ("USER GROUP - CLOSER", 20)." | "._QXZ("RECEIPT DATE", 19)." | "._QXZ("STATUS", 6)." |\n";
	$CSV_text1="\""._QXZ("CALL DATE (INITIAL)")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("PHONE NUMBER")."\",\""._QXZ("PROVINCE")."\",\""._QXZ("USER")."\",\""._QXZ("USER GROUP - FRONTER")."\",\""._QXZ("FRONT CAMP")."\",\""._QXZ("INBOUND/XFER GROUP")."\",\""._QXZ("RECVD CAMP")."\",\""._QXZ("RCVD GROUP")."\",\""._QXZ("USER")."\",\""._QXZ("USER GROUP - CLOSER")."\",\""._QXZ("RECEIPT DATE")."\",\""._QXZ("STATUS")."\"\n";
	$HTML_text.=$output_header;
	for($i=0; $i<count($complete_xfer_log); $i++) {
		$HTML_text.="| ";
		$HTML_text.=sprintf("%-19s", $complete_xfer_log[$i][0])." | ";
		$HTML_text.="<a href='admin_modify_lead.php?lead_id=".$complete_xfer_log[$i][1]."'>".sprintf("%-9s", $complete_xfer_log[$i][1])."</a> | ";
		$HTML_text.=sprintf("%-20s", $complete_xfer_log[$i][2])." | ";
		$HTML_text.=sprintf("%-20s", $complete_xfer_log[$i][3])." | ";
		$HTML_text.=sprintf("%-20s", $complete_xfer_log[$i][4])." | ";
		$HTML_text.=sprintf("%-20s", $complete_xfer_log[$i][5])." | ";
		$HTML_text.=sprintf("%-10s", $complete_xfer_log[$i][6])." | ";
		$HTML_text.=sprintf("%-20s", $complete_xfer_log[$i][7])." | ";
		$HTML_text.=sprintf("%-10s", $complete_xfer_log[$i][8])." | ";
		$HTML_text.=sprintf("%-20s", $complete_xfer_log[$i][9])." | ";
		$HTML_text.=sprintf("%-20s", $complete_xfer_log[$i][10])." | ";
		$HTML_text.=sprintf("%-20s", $complete_xfer_log[$i][11])." | ";
		$HTML_text.=sprintf("%-19s", $complete_xfer_log[$i][12])." | ";
		$HTML_text.=sprintf("%-6s", $complete_xfer_log[$i][13])." |\n";
		for ($j=0; $j<=13; $j++) {
			$CSV_text1.="\"".$complete_xfer_log[$i][$j]."\",";
		}
		$CSV_text1=preg_replace("/,$/", "", $CSV_text1);
		$CSV_text1.="\n";
	}
	$HTML_text.=$output_header;
} else {
	$CSV_text1=count($complete_xfer_log);
}


if ($show_summary) {

$HTML_text.=_QXZ("In-Group Fronter-Closer Stats Report",57)." $NOW_TIME\n";

$HTML_text.="\n";
$HTML_text.="---------- "._QXZ("TOTALS FOR")." $query_date_BEGIN "._QXZ("to")." $query_date_END\n";

$SQL_group_id = preg_replace("/_/",'\\_',$group);
$sale_dispo_stmt="select distinct status from vicidial_campaign_statuses where sale='Y' and campaign_id in (SELECT campaign_id from vicidial_campaigns where closer_campaigns LIKE \"% " . implode(" %\" OR closer_campaigns LIKE \"% ", array_map(array($link, 'real_escape_string'), $SQL_group_id)) . " %\" $LOGallowed_campaignsSQL) UNION select distinct status from vicidial_statuses where sale='Y'";
if ($DB) {echo "$sale_dispo_stmt\n";}
$sale_dispo_rslt=mysql_to_mysqli($sale_dispo_stmt, $link);
$sale_dispos="'SALE'"; $sale_dispo_str="|SALE";
while ($ssrow=mysqli_fetch_row($sale_dispo_rslt)) {
	$sale_dispos.=",'$ssrow[0]'";
	$sale_dispo_str.="|$ssrow[0]";
}
$sale_dispo_str.="|";
if ($DB) {$HTML_text.=_QXZ("Sale dispo string").": $sale_dispo_str\n";}

$stmt="select count(*) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "') and status in ($sale_dispos);";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);

$HTML_text.=_QXZ("STATUS",8)." "._QXZ("CUSTOMERS")."\n";
$HTML_text.=_QXZ("SALES").":   $A1_tally\n";

$HTML_text.="\n";









##############################
#########  FRONTER STATS

$TOTagents=0;
$TOTcalls=0;
$TOTsales=0;
$totDROP=0;
$totOTHER=0;

$CSV_fronter_header="\""._QXZ("TOTALS FOR")." $query_date_BEGIN "._QXZ("to")." $query_date_END\"\n";
$CSV_fronter_header.="\""._QXZ("STATUS   CUSTOMERS")."\"\n";
$CSV_fronter_header.="\""._QXZ("SALES").":   $A1_tally\"\n\n";
$CSV_fronter_header.="\""._QXZ("FRONTER STATS")."\"\n";
$CSV_fronter_header.="\""._QXZ("AGENT")."\",\""._QXZ("XFERS")."\",\""._QXZ("SALE")."%\",\""._QXZ("SALE")."\",\""._QXZ("DROP")."\",\""._QXZ("OTHER")."\"\n";
$CSV_fronter_lines="";
$CSV_fronter_footer="";

$ASCII_text="\n";
$ASCII_text.="---------- "._QXZ("FRONTER STATS")."\n";
$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";
$ASCII_text.="| "._QXZ("AGENT",24)." | "._QXZ("XFERS",6)." | "._QXZ("SALE",7)."% | "._QXZ("SALE",7)." | "._QXZ("DROP",7)." | "._QXZ("OTHER",5)." |\n";
$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";

######## GRAPHING #########
$graph_stats=array();
$max_success=1;
$max_xfers=1;
$max_success_pct=1;
$max_sales=1;
$max_drops=1;
$max_other=1;
###########################

$stmt="select user,count(*) from ".$vicidial_xfer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "') and user is not null $summary_user_xfer_log_SQL group by user;";


if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
$userRAW=array();
$user=array();
$USERcallsRAW=array();
$USERcalls=array();
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$TOTcalls = ($TOTcalls + $row[1]);

	$userRAW[$i]=$row[0];
	$user[$i] =	sprintf("%-6s", $row[0]);while(strlen($user[$i])>6) {$user[$i] = substr("$user[$i]", 0, -1);}
	$USERcallsRAW[$i] =	$row[1];
	$USERcalls[$i] =	sprintf("%6s", $row[1]);

	$i++;
	}

$i=0;
$full_name=array();
while ($i < $users_to_print)
	{
	$stmt="select full_name from vicidial_users where user='$userRAW[$i]' $LOGadmin_viewable_groupsSQL;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$names_to_print = mysqli_num_rows($rslt);
	if ($names_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		if ($non_latin < 1)
			{
			 $full_name[$i] =	sprintf("%-15s", $row[0]); while(strlen($full_name[$i])>15) {$full_name[$i] = substr("$full_name[$i]", 0, -1);}	
			}
		else
			{
			 $full_name[$i] =	sprintf("%-45s", $row[0]); while(mb_strlen($full_name[$i],'utf-8')>15) {$full_name[$i] = mb_substr("$full_name[$i]", 0, -1,'utf-8');}	
			}
		}
	else
		{$full_name[$i] = '               ';}

	$DROP=0; $OTHER=0; $sales=0; 
	$stmt="select vc.status,count(*) from ".$vicidial_xfer_log_table." vx, ".$vicidial_closer_log_table." vc where vx.call_date >= '$query_date_BEGIN' and vx.call_date <= '$query_date_END' and vc.call_date >= '$query_date_BEGIN' and vc.call_date <= '$query_date_END' and  vc.campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "') and vx.campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "') and vx.user='$userRAW[$i]' and vc.lead_id=vx.lead_id and vc.xfercallid=vx.xfercallid group by vc.status;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$lead_ids_to_print = mysqli_num_rows($rslt);
	$j=0;
	while ($j < $lead_ids_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$recL=0;
		if ( (preg_match("/\|$row[0]\|/", $sale_dispo_str)) and ($recL < 1) ) {$A1=$row[1]; $recL++; $sales=($sales + $row[1]);}
		if ( ($row[0]=='DROP' || $row[0]=='TIMEOT') and ($recL < 1) ) {$DROP=($DROP+$row[1]); $recL++;}
		if ($recL < 1) {$OTHER=($row[1] + $OTHER); $recL++;}
		$j++;
		}

	$totDROP = ($totDROP + $DROP);
	$totOTHER = ($totOTHER + $OTHER);
	$TOTsales = ($TOTsales + $sales);

	$Spct = MathZDC($sales, $USERcallsRAW[$i])*100;
	$Spct = round($Spct, 2);
	$Spct =	sprintf("%01.2f", $Spct);
	
	if ($sales>$max_success) {$max_success=$sales;}
	if ($USERcalls[$i]>$max_xfers) {$max_xfers=$USERcalls[$i];}
	if ($Spct>$max_success_pct) {$max_success_pct=$Spct;}
	if ($DROP>$max_drops) {$max_drops=$DROP;}
	if ($OTHER>$max_other) {$max_other=$OTHER;}
	$graph_stats[$i][0]="$user[$i] - $full_name[$i]";
	$graph_stats[$i][1]=$USERcalls[$i];
	$graph_stats[$i][2]=$Spct;
	$graph_stats[$i][3]=$sales;
	$graph_stats[$i][4]=$DROP;
	$graph_stats[$i][5]=$OTHER;

	$DROP =	sprintf("%7s", $DROP);
	$OTHER =	sprintf("%5s", $OTHER);
	$sales =	sprintf("%7s", $sales);
	$Spct =	sprintf("%7s", $Spct);

	$ASCII_text.="| ".sprintf("%-24s", substr("$user[$i] - $full_name[$i]", 0, 24))." | $USERcalls[$i] | $Spct% | $sales | $DROP | $OTHER |\n";
	$CSV_fronter_lines.="\"$user[$i] - $full_name[$i]\",\"$USERcalls[$i]\",\"$Spct%\",\"$sales\",\"$DROP\",\"$OTHER\"\n";

	$i++;
	}


$totSpct = MathZDC($TOTsales, $TOTcalls)*100;
$totSpct = round($totSpct, 2);
$totSpct =	sprintf("%01.2f", $totSpct);
$totSpct =	sprintf("%7s", $totSpct);
	
$TOTagents =	sprintf("%6s", $i);
$TOTcalls =		sprintf("%6s", $TOTcalls);
$TOTsales =		sprintf("%7s", $TOTsales);
$totDROP =		sprintf("%7s", $totDROP);
$totOTHER =		sprintf("%5s", $totOTHER);


$stmt="select avg(queue_seconds) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "');";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$AVGwait = $row[0];
$AVGwait_M = MathZDC($AVGwait, 60);
$AVGwait_M = round($AVGwait_M, 2);
$AVGwait_M_int = intval("$AVGwait_M");
$AVGwait_S = ($AVGwait_M - $AVGwait_M_int);
$AVGwait_S = ($AVGwait_S * 60);
$AVGwait_S = round($AVGwait_S, 0);
if ($AVGwait_S < 10) {$AVGwait_S = "0$AVGwait_S";}
$AVGwait_MS = "$AVGwait_M_int:$AVGwait_S";
$AVGwait =		sprintf("%6s", $AVGwait_MS);


$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";
$ASCII_text.="| "._QXZ("TOTAL FRONTERS",14).": $TOTagents   | $TOTcalls | $totSpct% | $TOTsales | $totDROP | $totOTHER |\n";
$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";
$ASCII_text.="| "._QXZ("Average time in Queue for customers",61,"r").":    $AVGwait |\n";
$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";

$CSV_fronter_footer.="\""._QXZ("TOTAL FRONTERS").": $TOTagents\",\"$TOTcalls\",\"$totSpct%\",\"$TOTsales\",\"$totDROP\",\"$totOTHER\"\n";
$CSV_fronter_footer.="\""._QXZ("Average time in Queue for customers").":    $AVGwait\"\n\n\n";

	# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
	$multigraph_text="";
	$graph_id++;
	$graph_array=array("FCSF_XFERSdata|1|XFERS|integer|", "FCSF_SALEPCTdata|2|SALE %|percent|", "FCSF_SALESdata|3|SALES|integer|", "FCSF_DROPSdata|4|DROPS|integer|", "FCSF_OTHERdata|5|OTHER|integer|");
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
	$graph_title=_QXZ("FRONTER STATS");
	include("graphcanvas.inc");
	$HTML_head.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas;



##############################
#########  CLOSER STATS
$graph_stats=array();
$TOTagents=0;
$TOTcalls=0;
$totDROP=0;
$totOTHER=0;
$TOTsales=0;

$CSV_closer_header="";
$CSV_closer_lines="";
$CSV_closer_footer="";

$ASCII_text.="\n";
$ASCII_text.="---------- "._QXZ("CLOSER STATS")."\n";
$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";
$ASCII_text.="| "._QXZ("AGENT",24)." | "._QXZ("CALLS",6)." | "._QXZ("SALE",8)." | "._QXZ("DROP",7)." | "._QXZ("OTHER",7)." | "._QXZ("CONV",4)." %|\n";
$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";

$CSV_closer_header="\""._QXZ("CLOSER STATS")."\"\n";
$CSV_closer_header.="\""._QXZ("AGENT")."\",\""._QXZ("CALLS")."\",\""._QXZ("SALE")."\",\""._QXZ("DROP")."\",\""._QXZ("OTHER")."\",\""._QXZ("CONV")." %\"\n";

######## GRAPHING #########
$max_calls=1;
$max_sales=1;
$max_drops=1;
$max_other=1;
$max_sales2=1;
$max_conv_pct=1;
###########################

$stmt="select user,count(*) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "') and user is not null $summary_user_closer_log_SQL group by user;";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
$userRAW=array();
$user=array();
$USERcallsRAW=array();
$USERcalls=array();
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$TOTcalls = ($TOTcalls + $row[1]);
	$userRAW[$i]=$row[0];
	$user[$i] =	sprintf("%-6s", $row[0]);while(strlen($user[$i])>6) {$user[$i] = substr("$user[$i]", 0, -1);}
	$USERcalls[$i] =	sprintf("%6s", $row[1]);
	$USERcallsRAW[$i] =	$row[1];

	$i++;
	}

$i=0;
$full_name=array();
while ($i < $users_to_print)
	{
	$stmt="select full_name from vicidial_users where user='$userRAW[$i]' $LOGadmin_viewable_groupsSQL;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$names_to_print = mysqli_num_rows($rslt);
	if ($names_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		if ($non_latin < 1)
			{
			 $full_name[$i] =	sprintf("%-15s", $row[0]); while(strlen($full_name[$i])>15) {$full_name[$i] = substr("$full_name[$i]", 0, -1);}	
			}
		else
			{
			 $full_name[$i] =	sprintf("%-45s", $row[0]); while(mb_strlen($full_name[$i],'utf-8')>15) {$full_name[$i] = mb_substr("$full_name[$i]", 0, -1,'utf-8');}	
			}
		}
	else
		{$full_name[$i] = '               ';}

	$DROP=0; $OTHER=0; $sales=0; $uTOP=0; $uBOT=0; $points=0;
	$stmt="select status,count(*) from ".$vicidial_closer_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id in ('" . implode("','", array_map(array($link, 'real_escape_string'), $group)) . "') and user='$userRAW[$i]' group by status;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$lead_ids_to_print = mysqli_num_rows($rslt);
	$j=0;
	while ($j < $lead_ids_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$recL=0;
		if ( preg_match("/\|$row[0]\|/", $sale_dispo_str) and ($recL < 1) ) 
			{
			$A1=$row[1]; $recL++; 
			$sales=($sales + $row[1]);
			$points = ($points + ($row[1] * 1) );
			}
		if ( ($row[0]=='DROP') and ($recL < 1) ) {$DROP=$row[1]; $recL++;}
		if ($recL < 1) {$OTHER=($row[1] + $OTHER); $recL++;}
		
		$j++;
		}

	$TOTsales = ($TOTsales + $sales);
	$totDROP = ($totDROP + $DROP);
	$totOTHER = ($totOTHER + $OTHER);
	$totPOINTS = ($totPOINTS + $points);

	$Cpct = MathZDC($sales, ( ($USERcallsRAW[$i] - 0) - $DROP) )*100;
	$Cpct = round($Cpct, 2);
	$Cpct =	sprintf("%01.2f", $Cpct);
	$Cpct =	sprintf("%6s", $Cpct);

	$TOP = MathZDC($uTOP, $sales)*100;
	$TOP = round($TOP, 0);
	$TOP =	sprintf("%01.0f", $TOP);
	$TOP =	sprintf("%3s", $TOP);

	$BOT = MathZDC($uBOT, $sales)*100;
	$BOT = round($BOT, 0);
	$BOT =	sprintf("%01.0f", $BOT);
	$BOT =	sprintf("%3s", $BOT);

	$ppc = MathZDC($points, ( ($USERcallsRAW[$i] - 0) - $DROP) );
	$ppc = round($ppc, 2);
	$ppc =	sprintf("%01.2f", $ppc);
	$ppc =	sprintf("%4s", $ppc);

	if ($USERcalls[$i]>$max_calls) {$max_calls=$USERcalls[$i];}
	if ($A1>$max_sales) {$max_sales=$A1;}
	if ($DROP>$max_drops) {$max_drops=$DROP;}
	if ($OTHER>$max_other) {$max_other=$OTHER;}
	if ($sales>$max_sales2) {$max_sales2=$sales;}
	if ($Cpct>$max_conv_pct) {$max_conv_pct=$Cpct;}
	$graph_stats[$i][0]="$user[$i] - $full_name[$i]";
	$graph_stats[$i][1]=$USERcalls[$i];
	$graph_stats[$i][2]=$sales;
	$graph_stats[$i][3]=$DROP;
	$graph_stats[$i][4]=$OTHER;
	$graph_stats[$i][5]=$Cpct;

	$calls =	sprintf("%6s", $USERcalls[$i]);
	$DROP =	sprintf("%7s", $DROP);
	$OTHER =	sprintf("%7s", $OTHER);
	$sales =	sprintf("%8s", $sales);

	$ASCII_text.="| ".sprintf("%-24s", substr("$user[$i] - $full_name[$i]", 0, 24))." | $calls | $sales | $DROP | $OTHER |$Cpct%|\n";
	$CSV_closer_lines.="\"$user[$i] - $full_name[$i]\",\"$USERcalls[$i]\",\"$sales\",\"$DROP\",\"$OTHER\",\"$Cpct%\"\n";

	$i++;
	}


$totCpct = MathZDC($TOTsales, ( ($TOTcalls - 0) - $totDROP) )*100;
$totCpct = round($totCpct, 2);
$totCpct =	sprintf("%01.2f", $totCpct);
$totCpct =	sprintf("%5s", $totCpct);
		
$ppc = MathZDC($totPOINTS, ( ($TOTcalls - $totOTHER) - $totDROP) );
$ppc = round($ppc, 2);
$ppc =	sprintf("%01.2f", $ppc);
$ppc =	sprintf("%4s", $ppc);
		
$TOP = MathZDC($totTOP, $TOTsales)*100;
$TOP = round($TOP, 0);
$TOP =	sprintf("%01.0f", $TOP);
$TOP =	sprintf("%3s", $TOP);

$BOT = MathZDC($totBOT, $TOTsales)*100;
$BOT = round($BOT, 0);
$BOT =	sprintf("%01.0f", $BOT);
$BOT =	sprintf("%3s", $BOT);

$TOTagents =	sprintf("%6s", $i);
$TOTcalls =		sprintf("%6s", $TOTcalls);
$totDROP =		sprintf("%7s", $totDROP);
$totOTHER =		sprintf("%7s", $totOTHER);
$TOTsales =		sprintf("%8s", $TOTsales);

$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";
$ASCII_text.="| "._QXZ("TOTAL CLOSERS",13).":  $TOTagents   | $TOTcalls | $TOTsales | $totDROP | $totOTHER | $totCpct%|\n";
$ASCII_text.="+--------------------------+--------+----------+---------+---------+-------+\n";

$CSV_closer_footer.="\""._QXZ("TOTAL CLOSERS").":  $TOTagents\",\"$TOTcalls\",\"$totA1\",\"$totDROP\",\"$totOTHER\",\"$TOTsales\",\"$totCpct%\"\n";

	# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
	$multigraph_text="";
	$graph_id++;
	$graph_array=array("FCSC_CALLSdata|1|CALLS|integer|", "FCSC_SALESdata|2|SALES|integer|", "FCSC_DROPSdata|3|DROPS|integer|", "FCSC_OTHERdata|4|OTHER|integer|", "FCSC_CONVPCTdata|5|CONV %|percent|");
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
		echo "<!-- $JS_text //-->\n";
	}

	$graph_count=count($graph_array);
	$graph_title=_QXZ("CLOSER STATS");
	include("graphcanvas.inc");
	$HTML_head.=$HTML_graph_head;
	$GRAPH_text.=$graphCanvas;
}
}

$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);

if ($report_display_type=="HTML") {
	$HTML_text.=$GRAPH_text;
	} 
else 
	{
	$HTML_text.=$ASCII_text;
	}

if ($DB) {echo "\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";}

$HTML_text.="</PRE>\n";

$HTML_text.="</BODY></HTML>\n";

if ($file_download > 0)
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "FCSTATS_$US$FILE_TIME.csv";
	$CSV_text=$CSV_text1."\n\n".$CSV_fronter_header.$CSV_fronter_lines.$CSV_fronter_footer.$CSV_closer_header.$CSV_closer_lines.$CSV_closer_footer;
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

