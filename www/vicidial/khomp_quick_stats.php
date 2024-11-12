<?php 
# khomp_quick_stats.php
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 241001-1138 - First build
#

$startMS = microtime();

$report_name='Khomp Quick Stats Report';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["stage"]))				{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))		{$stage=$_POST["stage"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$last_midnight = date("Y-m-d 00:00:00");
$STARTtime = date("U");

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method,allow_shared_dial,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$stage_territories_active =		$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$SSallow_shared_dial =			$row[6];
	$SSallow_web_debug =			$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);

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

$stmt="SELECT selected_language,user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$LOGuser_group =			$row[1];
	}

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

$stmt="SELECT modify_campaigns,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGmodify_campaigns =	$row[0];
$LOGuser_group =		$row[1];

if ($LOGmodify_campaigns < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for campaign debugging").": |$PHP_AUTH_USER|\n";
	exit;
	}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
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
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

$admin_viewable_groupsALL=0;
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}
$regexLOGadmin_viewable_groups = " $LOGadmin_viewable_groups ";

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo "You are not allowed to view this report: |$PHP_AUTH_USER|$report_name|\n";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url', webserver='$webserver_id';";
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

$khomp_log_ct=0;
$stmt="SELECT count(*) from vicidial_khomp_log;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$counts_to_print = mysqli_num_rows($rslt);
if ($i < $counts_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$khomp_log_ct =		$row[0];
	}

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";
?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}
-->
 </STYLE>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;

	require("admin_header.php");

echo "<b>"._QXZ("$report_name")."</b> $NWB#khomp_quick_stats$NWE\n";

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<SELECT SIZE=1 NAME=stage>\n";
echo "<option ";
if ( ($stage == '--COMPARE-TO-YESTERDAY--') or (strlen($stage) < 10) ) {echo "selected ";}
echo "value='--COMPARE-TO-YESTERDAY--'>"._QXZ("--COMPARE-TO-YESTERDAY--")."</option>\n";
echo "<option ";
if ($stage == '--COMPARE-TO-2-DAYS-AGO--') {echo "selected ";}
echo "value='--COMPARE-TO-2-DAYS-AGO--'>"._QXZ("--COMPARE-TO-2-DAYS-AGO--")."</option>\n";
echo "<option ";
if ($stage == '--COMPARE-TO-3-DAYS-AGO--') {echo "selected ";}
echo "value='--COMPARE-TO-3-DAYS-AGO--'>"._QXZ("--COMPARE-TO-3-DAYS-AGO--")."</option>\n";
echo "<option ";
if ($stage == '--COMPARE-TO-4-DAYS-AGO--') {echo "selected ";}
echo "value='--COMPARE-TO-4-DAYS-AGO--'>"._QXZ("--COMPARE-TO-4-DAYS-AGO--")."</option>\n";
echo "<option ";
if ($stage == '--COMPARE-TO-5-DAYS-AGO--') {echo "selected ";}
echo "value='--COMPARE-TO-5-DAYS-AGO--'>"._QXZ("--COMPARE-TO-5-DAYS-AGO--")."</option>\n";
echo "<option ";
if ($stage == '--COMPARE-TO-6-DAYS-AGO--') {echo "selected ";}
echo "value='--COMPARE-TO-6-DAYS-AGO--'>"._QXZ("--COMPARE-TO-6-DAYS-AGO--")."</option>\n";
echo "<option ";
if ($stage == '--COMPARE-TO-7-DAYS-AGO--') {echo "selected ";}
echo "value='--COMPARE-TO-7-DAYS-AGO--'>"._QXZ("--COMPARE-TO-7-DAYS-AGO--")."</option>\n";
echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n\n";

if ($khomp_log_ct < 1)
	{
	echo "\n\n";
	echo _QXZ("KHOMP IS NOT ACTIVE ON THIS SYSTEM")."\n";
	echo "</PRE></TD></TR></TABLE></BODY></HTML>\n";

	exit;
	}
if (!$stage)
	{
	echo "\n\n";
	echo _QXZ("PLEASE SELECT AN OPTION ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$today_date = date("Y-m-d", $STARTtime);
	$today_start = date("Y-m-d H:i:s", $STARTtime);
	$today_hour_ago = date("Y-m-d H:i:s", ($STARTtime - 3600));

	if ($stage == '--COMPARE-TO-YESTERDAY--')	{$YESTERDAYtime = ($STARTtime - 86400);}
	if ($stage == '--COMPARE-TO-2-DAYS-AGO--')	{$YESTERDAYtime = ($STARTtime - (86400 * 2));}
	if ($stage == '--COMPARE-TO-3-DAYS-AGO--')	{$YESTERDAYtime = ($STARTtime - (86400 * 3));}
	if ($stage == '--COMPARE-TO-4-DAYS-AGO--')	{$YESTERDAYtime = ($STARTtime - (86400 * 4));}
	if ($stage == '--COMPARE-TO-5-DAYS-AGO--')	{$YESTERDAYtime = ($STARTtime - (86400 * 5));}
	if ($stage == '--COMPARE-TO-6-DAYS-AGO--')	{$YESTERDAYtime = ($STARTtime - (86400 * 6));}
	if ($stage == '--COMPARE-TO-7-DAYS-AGO--')	{$YESTERDAYtime = ($STARTtime - (86400 * 7));}

	$yesterday_date = date("Y-m-d", $YESTERDAYtime);
	$yesterday_start = date("Y-m-d H:i:s", $YESTERDAYtime);
	$yesterday_hour_ago = date("Y-m-d H:i:s", ($YESTERDAYtime - 3600));

	# gather today stats
	$today_processed_calls=0;
	$today_max_sec=0;
	$today_avg_sec=0;
	$stmt="SELECT count(*),max(hangup_auth_time+hangup_query_time+route_auth_time+route_query_time),avg(hangup_auth_time+hangup_query_time+route_auth_time+route_query_time) from vicidial_khomp_log where start_date >= \"$today_hour_ago\" and start_date < \"$today_start\" and conclusion is not null;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	if ($rows_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$today_processed_calls =	$row[0];
		$today_max_sec =			sprintf("%1.2f", $row[1]);
		$today_avg_sec =			sprintf("%1.2f", $row[2]);
		}
	$today_unprocessed_calls=0;
	$stmt="SELECT count(*) from vicidial_khomp_log where start_date >= \"$today_hour_ago\" and start_date < \"$today_start\" and conclusion is null;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	if ($rows_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$today_unprocessed_calls =	$row[0];
		}
	$today_total_calls = ($today_processed_calls + $today_unprocessed_calls);
	$today_processed_pct = sprintf("%1.2f", (($today_processed_calls / $today_total_calls) * 100));

	# gather previous day stats
	$yesterday_processed_calls=0;
	$yesterday_max_sec=0;
	$yesterday_avg_sec=0;
	$stmt="SELECT count(*),max(hangup_auth_time+hangup_query_time+route_auth_time+route_query_time),avg(hangup_auth_time+hangup_query_time+route_auth_time+route_query_time) from vicidial_khomp_log where start_date >= \"$yesterday_hour_ago\" and start_date < \"$yesterday_start\" and conclusion is not null;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	if ($rows_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$yesterday_processed_calls =	$row[0];
		$yesterday_max_sec =			sprintf("%1.2f", $row[1]);
		$yesterday_avg_sec =			sprintf("%1.2f", $row[2]);
		}
	$yesterday_unprocessed_calls=0;
	$stmt="SELECT count(*) from vicidial_khomp_log where start_date >= \"$yesterday_hour_ago\" and start_date < \"$yesterday_start\" and conclusion is null;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	if ($rows_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$yesterday_unprocessed_calls =	$row[0];
		}
	$yesterday_total_calls = ($yesterday_processed_calls + $yesterday_unprocessed_calls);
	$yesterday_processed_pct = sprintf("%1.2f", (($yesterday_processed_calls / $yesterday_total_calls) * 100));

	$graph_calls='';
	$graph_processed='';
	$graph_processed_pct='';
	$graph_proc_max='';
	$graph_proc_avg='';

	# generate graph_calls
	$calls_max = $today_total_calls;
	$today_span='colspan=2';
	$yesterday_span='';
	$today_td='';
	$yesterday_td='<td></td>';
	if ($yesterday_total_calls > $calls_max) 
		{
		$calls_max = $yesterday_total_calls;
		$today_span='';
		$yesterday_span='colspan=2';
		$today_td='<td></td>';
		$yesterday_td='';
		}
	$today_ct = intval( ($today_total_calls / $calls_max) * 300);
	$yesterday_ct = intval( ($yesterday_total_calls / $calls_max) * 300);
	$graph_calls = "<table border=0 cellpadding=0 cellspacing=0>";
	$graph_calls .= "<tr><td width=$today_ct height=8 bgcolor=red $today_span><img src=\"images/blank.gif\" height=8 width=$today_ct></td>$today_td</tr>";
	$graph_calls .= "<tr><td width=$yesterday_ct height=8 bgcolor=blue $yesterday_span><img src=\"images/blank.gif\" height=8 width=$yesterday_ct></td>$yesterday_td</tr>";
	$graph_calls .= "</table>";

	# generate graph_processed
	$processed_max = $today_processed_calls;
	$today_span='colspan=2';
	$yesterday_span='';
	$today_td='';
	$yesterday_td='<td></td>';
	if ($yesterday_processed_calls > $processed_max) 
		{
		$processed_max = $yesterday_processed_calls;
		$today_span='';
		$yesterday_span='colspan=2';
		$today_td='<td></td>';
		$yesterday_td='';
		}
	$today_ct = intval( ($today_processed_calls / $calls_max) * 300);
	$yesterday_ct = intval( ($yesterday_processed_calls / $calls_max) * 300);
	$graph_processed = "<table border=0 cellpadding=0 cellspacing=0>";
	$graph_processed .= "<tr><td width=$today_ct height=8 bgcolor=red $today_span><img src=\"images/blank.gif\" height=8 width=$today_ct></td>$today_td</tr>";
	$graph_processed .= "<tr><td width=$yesterday_ct height=8 bgcolor=blue $yesterday_span><img src=\"images/blank.gif\" height=8 width=$yesterday_ct></td>$yesterday_td</tr>";
	$graph_processed .= "</table>";

	# generate graph_processed_pct
	$calls_max = $today_processed_pct;
	$today_span='colspan=2';
	$yesterday_span='';
	$today_td='';
	$yesterday_td='<td></td>';
	if ($yesterday_processed_pct > $calls_max) 
		{
		$calls_max = $yesterday_processed_pct;
		$today_span='';
		$yesterday_span='colspan=2';
		$today_td='<td></td>';
		$yesterday_td='';
		}
	$today_ct = intval( ($today_processed_pct / 100) * 300);
	$yesterday_ct = intval( ($yesterday_processed_pct / 100) * 300);
	$graph_processed_pct = "<table border=0 cellpadding=0 cellspacing=0>";
	$graph_processed_pct .= "<tr><td width=$today_ct height=8 bgcolor=red $today_span><img src=\"images/blank.gif\" height=8 width=$today_ct></td>$today_td</tr>";
	$graph_processed_pct .= "<tr><td width=$yesterday_ct height=8 bgcolor=blue $yesterday_span><img src=\"images/blank.gif\" height=8 width=$yesterday_ct></td>$yesterday_td</tr>";
	$graph_processed_pct .= "</table>";

	# generate graph_proc_max
	$calls_max = $today_max_sec;
	$today_span='colspan=2';
	$yesterday_span='';
	$today_td='';
	$yesterday_td='<td></td>';
	if ($yesterday_max_sec > $calls_max) 
		{
		$calls_max = $yesterday_max_sec;
		$today_span='';
		$yesterday_span='colspan=2';
		$today_td='<td></td>';
		$yesterday_td='';
		}
	$today_ct = intval( ($today_max_sec / $calls_max) * 300);
	$yesterday_ct = intval( ($yesterday_max_sec / $calls_max) * 300);
	$graph_proc_max = "<table border=0 cellpadding=0 cellspacing=0>";
	$graph_proc_max .= "<tr><td width=$today_ct height=8 bgcolor=red $today_span><img src=\"images/blank.gif\" height=8 width=$today_ct></td>$today_td</tr>";
	$graph_proc_max .= "<tr><td width=$yesterday_ct height=8 bgcolor=blue $yesterday_span><img src=\"images/blank.gif\" height=8 width=$yesterday_ct></td>$yesterday_td</tr>";
	$graph_proc_max .= "</table>";

	# generate graph_proc_avg
	$calls_avg = $today_avg_sec;
	$today_span='colspan=2';
	$yesterday_span='';
	$today_td='';
	$yesterday_td='<td></td>';
	if ($yesterday_avg_sec > $calls_avg) 
		{
		$calls_avg = $yesterday_avg_sec;
		$today_span='';
		$yesterday_span='colspan=2';
		$today_td='<td></td>';
		$yesterday_td='';
		}
	$today_ct = intval( ($today_avg_sec / $calls_max) * 300);
	$yesterday_ct = intval( ($yesterday_avg_sec / $calls_max) * 300);
	$graph_proc_avg = "<table border=0 cellpadding=0 cellspacing=0>";
	$graph_proc_avg .= "<tr><td width=$today_ct height=8 bgcolor=red $today_span><img src=\"images/blank.gif\" height=8 width=$today_ct></td>$today_td</tr>";
	$graph_proc_avg .= "<tr><td width=$yesterday_ct height=8 bgcolor=blue $yesterday_span><img src=\"images/blank.gif\" height=8 width=$yesterday_ct></td>$yesterday_td</tr>";
	$graph_proc_avg .= "</table>";


	$graph_key = "<table border=0 cellpadding=0 cellspacing=0><tr><td><font color=white face='Arial,Helvetica' size=1>"._QXZ("today").": </td><td><table border=0 cellpadding=0 cellspacing=0><tr><td width=10 height=8 bgcolor=red><img src=\"images/blank.gif\" height=8 width=10></td></tr></table></td><td><font color=white face='Arial,Helvetica' size=1> &nbsp; &nbsp; "._QXZ("past day").": </td><td><table border=0 cellpadding=0 cellspacing=0><tr><td width=10 height=8 bgcolor=blue><img src=\"images/blank.gif\" height=8 width=10></td></tr></table></td></tr></table>";

	echo "</PRE>\n";

	echo "<TABLE border=0>";
	echo "<tr bgcolor=black><td><b> &nbsp; <font color=white face='Arial,Helvetica'>"._QXZ("KHOMP LAST HOUR")."</font> &nbsp; </td><td><b> &nbsp; <font color=white face='Arial,Helvetica'>"._QXZ("TODAY").": $today_date</font> &nbsp; </td><td><b> &nbsp; <font color=white face='Arial,Helvetica'>$yesterday_date</font> &nbsp; </td><td align=center>$graph_key</td></tr>\n";
	echo "<tr><td>"._QXZ("CALLS").": </td><td align=right>$today_total_calls</td><td align=right><b>$yesterday_total_calls</td><td>$graph_calls</td></tr>\n";
	echo "<tr bgcolor=#E6E6E6><td>"._QXZ("PROCESSED").": </td><td align=right>$today_processed_calls</td><td align=right><b>$yesterday_processed_calls</td><td>$graph_processed</td></tr>\n";
	echo "<tr><td>"._QXZ("PROCESSED")." %: </td><td align=right>$today_processed_pct%</td><td align=right><b>$yesterday_processed_pct%</td><td>$graph_processed_pct</td></tr>\n";
	echo "<tr bgcolor=#E6E6E6><td>"._QXZ("MAX PROC SEC").": </td><td align=right>$today_max_sec</td><td align=right><b>$yesterday_max_sec</td><td>$graph_proc_max</td></tr>\n";
	echo "<tr><td>"._QXZ("AVG PROC SEC").": </td><td align=right>$today_avg_sec</td><td align=right><b>$yesterday_avg_sec</td><td>$graph_proc_avg</td></tr>\n";

	############################################################################
	### BEGIN loop through last 60 minutes and gather Khomp processing stats ###
	############################################################################
	echo "<tr><td>"._QXZ("per minute avg breakdown").": </td><td align=right></td><td align=right></td><td></td></tr>\n";

	$mct=0;   $tct=1;
	while ($mct < 60)
		{
		$bgcolor = "bgcolor=#E6E6E6";
		if (preg_match("/1$|3$|5$|7$|9$/",$mct)) {$bgcolor = "bgcolor=white";}
		$temp_start_offset = 0;
		if ($tct > 1)
			{$temp_start_offset = ($mct * 60);}
		$temp_finish_offset = ($tct * 60);

		$temp_today_name = date("H:i", ( ($STARTtime - 3600) + $temp_start_offset) );
		$temp_today_start = date("Y-m-d H:i:s", ( ($STARTtime - 3600) + $temp_start_offset) );
		$temp_today_finish = date("Y-m-d H:i:s", ( ($STARTtime - 3600) + $temp_finish_offset) );
		$temp_yesterday_start = date("Y-m-d H:i:s", ( ($YESTERDAYtime - 3600) + $temp_start_offset) );
		$temp_yesterday_finish = date("Y-m-d H:i:s", ( ($YESTERDAYtime - 3600) + $temp_finish_offset) );

		# gather today stats
		$today_processed_calls=0;
		$today_max_sec=0;
		$today_avg_sec=0;
		$stmt="SELECT count(*),max(hangup_auth_time+hangup_query_time+route_auth_time+route_query_time),avg(hangup_auth_time+hangup_query_time+route_auth_time+route_query_time) from vicidial_khomp_log where start_date >= \"$temp_today_start\" and start_date < \"$temp_today_finish\" and conclusion is not null;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$rows_to_print = mysqli_num_rows($rslt);
		if ($rows_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$today_processed_calls =	$row[0];
			$today_max_sec =			sprintf("%1.2f", $row[1]);
			$today_avg_sec =			sprintf("%1.2f", $row[2]);
			}

		# gather yesterday stats
		$yesterday_processed_calls=0;
		$yesterday_max_sec=0;
		$yesterday_avg_sec=0;
		$stmt="SELECT count(*),max(hangup_auth_time+hangup_query_time+route_auth_time+route_query_time),avg(hangup_auth_time+hangup_query_time+route_auth_time+route_query_time) from vicidial_khomp_log where start_date >= \"$temp_yesterday_start\" and start_date < \"$temp_yesterday_finish\" and conclusion is not null;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$rows_to_print = mysqli_num_rows($rslt);
		if ($rows_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$yesterday_processed_calls =	$row[0];
			$yesterday_max_sec =			sprintf("%1.2f", $row[1]);
			$yesterday_avg_sec =			sprintf("%1.2f", $row[2]);
			}

		# generate graph_proc_avg
		$calls_avg = $today_avg_sec;
		$today_span='colspan=2';
		$yesterday_span='';
		$today_td='';
		$yesterday_td='<td></td>';
		if ($yesterday_avg_sec > $calls_avg) 
			{
			$calls_avg = $yesterday_avg_sec;
			$today_span='';
			$yesterday_span='colspan=2';
			$today_td='<td></td>';
			$yesterday_td='';
			}
		$today_ct = intval( ($today_avg_sec / $calls_max) * 300);
		$yesterday_ct = intval( ($yesterday_avg_sec / $calls_max) * 300);
		$graph_proc_avg = "<table border=0 cellpadding=0 cellspacing=0>";
		$graph_proc_avg .= "<tr><td width=$today_ct height=8 bgcolor=red $today_span><img src=\"images/blank.gif\" height=8 width=$today_ct></td>$today_td</tr>";
		$graph_proc_avg .= "<tr><td width=$yesterday_ct height=8 bgcolor=blue $yesterday_span><img src=\"images/blank.gif\" height=8 width=$yesterday_ct></td>$yesterday_td</tr>";
		$graph_proc_avg .= "</table>";

		echo "<tr $bgcolor><td>$temp_today_name </td><td align=right>$today_avg_sec</td><td align=right>$yesterday_avg_sec</td><td>$graph_proc_avg</td></tr>\n";


		$mct++;   $tct++;
		}
	############################################################################
	### END loop through last 60 minutes and gather Khomp processing stats ###
	############################################################################

	echo "</TABLE>\n";
	echo "\n";
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

</PRE>

</TD></TR></TABLE>

</BODY></HTML>
