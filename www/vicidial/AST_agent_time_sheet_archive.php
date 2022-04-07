<?php 
# AST_agent_time_sheet_archive.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60619-1721 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 90508-0644 - Changed to PHP long tags
# 130414-0156 - Added report logging
# 130610-1027 - Finalized changing of all ereg instances to preg
# 130621-0812 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0744 - Changed to mysqli PHP functions
# 140108-0717 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0906 - Finalized adding QXZ translation to all admin files
# 141230-1521 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 170711-1103 - Added screen colors and fixed default date variable
# 220303-1625 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='Agent Timesheet Archive';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["agent"]))				{$agent=$_GET["agent"];}
	elseif (isset($_POST["agent"]))		{$agent=$_POST["agent"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["calls_summary"]))			{$calls_summary=$_GET["calls_summary"];}
	elseif (isset($_POST["calls_summary"]))	{$calls_summary=$_POST["calls_summary"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if ( (!isset($query_date)) or (strlen($query_date) < 8) ) {$query_date = $NOW_DATE;}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,admin_screen_colors,report_default_format,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$SSadmin_screen_colors =	$row[3];
	$SSreport_default_format =	$row[4];
	$SSallow_web_debug =		$row[5];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$agent = preg_replace('/[^-_0-9a-zA-Z]/', '', $agent);
$query_date = preg_replace('/[^-_0-9a-zA-Z]/', '', $query_date);
$calls_summary = preg_replace('/[^-_0-9a-zA-Z]/', '', $calls_summary);
$submit = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$submit);
$SUBMIT = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$SUBMIT);

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


$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='B9CBFD';
$SSstd_row3_background='8EBCFD';
$SSstd_row4_background='B6D3FC';
$SSstd_row5_background='A3C3D6';
$SSalt_row1_background='BDFFBD';
$SSalt_row2_background='99FF99';
$SSalt_row3_background='CCFFCC';

if ($SSadmin_screen_colors != 'default')
	{
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$colors_ct = mysqli_num_rows($rslt);
	if ($colors_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$SSmenu_background =		$row[0];
		$SSframe_background =		$row[1];
		$SSstd_row1_background =	$row[2];
		$SSstd_row2_background =	$row[3];
		$SSstd_row3_background =	$row[4];
		$SSstd_row4_background =	$row[5];
		$SSstd_row5_background =	$row[6];
		$SSalt_row1_background =	$row[7];
		$SSalt_row2_background =	$row[8];
		$SSalt_row3_background =	$row[9];
		$SSweb_logo =				$row[10];
		}
	}
$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;

$selected_logo = "./images/vicidial_admin_web_logo.png";
$selected_small_logo = "./images/vicidial_admin_web_logo.png";
$logo_new=0;
$logo_old=0;
$logo_small_old=0;
if (file_exists('./images/vicidial_admin_web_logo.png')) {$logo_new++;}
if (file_exists('vicidial_admin_web_logo_small.gif')) {$logo_small_old++;}
if (file_exists('vicidial_admin_web_logo.gif')) {$logo_old++;}
if ($SSweb_logo=='default_new')
	{
	$selected_logo = "./images/vicidial_admin_web_logo.png";
	$selected_small_logo = "./images/vicidial_admin_web_logo.png";
	}
if ( ($SSweb_logo=='default_old') and ($logo_old > 0) )
	{
	$selected_logo = "./vicidial_admin_web_logo.gif";
	$selected_small_logo = "./vicidial_admin_web_logo_small.gif";
	}
if ( ($SSweb_logo!='default_new') and ($SSweb_logo!='default_old') )
	{
	if (file_exists("./images/vicidial_admin_web_logo$SSweb_logo")) 
		{
		$selected_logo = "./images/vicidial_admin_web_logo$SSweb_logo";
		$selected_small_logo = "./images/vicidial_admin_web_logo$SSweb_logo";
		}
	}

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
echo "<TITLE>"._QXZ("ADMIN: Agent Time Sheet")."</TITLE></HEAD><BODY BGCOLOR=WHITE>\n";
echo "<a href=\"./admin.php\">"._QXZ("ADMIN")."</a>: "._QXZ("Agent Time Sheet")."\n";
echo " - <a href=\"./user_stats.php?user=$agent\">"._QXZ("User Stats")."</a>\n";
echo " - <a href=\"./user_status.php?user=$agent\">"._QXZ("User Status")."</a>\n";
echo " - <a href=\"./admin.php?ADD=3&user=$agent\">"._QXZ("Modify User")."</a>\n";
echo "<BR>\n";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET> &nbsp; \n";
echo _QXZ("Date").": <INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=19 VALUE=\"$query_date\">\n";
echo _QXZ("User ID").": <INPUT TYPE=TEXT NAME=agent SIZE=20 MAXLENGTH=20 VALUE=\"$agent\">\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=3>\n";


if (!$agent)
{
echo "\n";
echo _QXZ("PLEASE SELECT AN AGENT ID AND DATE-TIME ABOVE AND CLICK SUBMIT")."\n";
echo " "._QXZ("NOTE: stats taken from available agent log data")."\n";
}

else
{
$query_date_BEGIN = "$query_date 00:00:00";   
$query_date_END = "$query_date 23:59:59";
$time_BEGIN = "00:00:00";   
$time_END = "23:59:59";

$stmt="select full_name from vicidial_users where user='$agent';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);
$full_name = $row[0];

echo _QXZ("ADMIN: Agent Time Sheet",51)." $NOW_TIME\n";

echo _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
echo "---------- "._QXZ("AGENT TIME SHEET").": $agent - $full_name -------------\n\n";

if ($calls_summary)
	{
	$stmt="select count(*) as calls,sum(talk_sec) as talk,avg(talk_sec),sum(pause_sec),avg(pause_sec),sum(wait_sec),avg(wait_sec),sum(dispo_sec),avg(dispo_sec) from vicidial_agent_log_archive where event_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and event_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and user='" . mysqli_real_escape_string($link, $agent) . "' and pause_sec<48800 and wait_sec<48800 and talk_sec<48800 and dispo_sec<48800 limit 1;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);

	$TOTAL_TIME = ($row[1] + $row[3] + $row[5] + $row[7]);

		$TOTAL_TIME_H = MathZDC($TOTAL_TIME, 3600);
		$TOTAL_TIME_H = round($TOTAL_TIME_H, 2);
		$TOTAL_TIME_H_int = intval("$TOTAL_TIME_H");
		$TOTAL_TIME_M = ($TOTAL_TIME_H - $TOTAL_TIME_H_int);
		$TOTAL_TIME_M = ($TOTAL_TIME_M * 60);
		$TOTAL_TIME_M = round($TOTAL_TIME_M, 2);
		$TOTAL_TIME_M_int = intval("$TOTAL_TIME_M");
		$TOTAL_TIME_S = ($TOTAL_TIME_M - $TOTAL_TIME_M_int);
		$TOTAL_TIME_S = ($TOTAL_TIME_S * 60);
		$TOTAL_TIME_S = round($TOTAL_TIME_S, 0);
		if ($TOTAL_TIME_S < 10) {$TOTAL_TIME_S = "0$TOTAL_TIME_S";}
		if ($TOTAL_TIME_M_int < 10) {$TOTAL_TIME_M_int = "0$TOTAL_TIME_M_int";}
		$TOTAL_TIME_HMS = "$TOTAL_TIME_H_int:$TOTAL_TIME_M_int:$TOTAL_TIME_S";
		$pfTOTAL_TIME_HMS =		sprintf("%8s", $TOTAL_TIME_HMS);

		$TALK_TIME_H = MathZDC($row[1], 3600);
		$TALK_TIME_H = round($TALK_TIME_H, 2);
		$TALK_TIME_H_int = intval("$TALK_TIME_H");
		$TALK_TIME_M = ($TALK_TIME_H - $TALK_TIME_H_int);
		$TALK_TIME_M = ($TALK_TIME_M * 60);
		$TALK_TIME_M = round($TALK_TIME_M, 2);
		$TALK_TIME_M_int = intval("$TALK_TIME_M");
		$TALK_TIME_S = ($TALK_TIME_M - $TALK_TIME_M_int);
		$TALK_TIME_S = ($TALK_TIME_S * 60);
		$TALK_TIME_S = round($TALK_TIME_S, 0);
		if ($TALK_TIME_S < 10) {$TALK_TIME_S = "0$TALK_TIME_S";}
		if ($TALK_TIME_M_int < 10) {$TALK_TIME_M_int = "0$TALK_TIME_M_int";}
		$TALK_TIME_HMS = "$TALK_TIME_H_int:$TALK_TIME_M_int:$TALK_TIME_S";
		$pfTALK_TIME_HMS =		sprintf("%8s", $TALK_TIME_HMS);

		$PAUSE_TIME_H = MathZDC($row[3], 3600);
		$PAUSE_TIME_H = round($PAUSE_TIME_H, 2);
		$PAUSE_TIME_H_int = intval("$PAUSE_TIME_H");
		$PAUSE_TIME_M = ($PAUSE_TIME_H - $PAUSE_TIME_H_int);
		$PAUSE_TIME_M = ($PAUSE_TIME_M * 60);
		$PAUSE_TIME_M = round($PAUSE_TIME_M, 2);
		$PAUSE_TIME_M_int = intval("$PAUSE_TIME_M");
		$PAUSE_TIME_S = ($PAUSE_TIME_M - $PAUSE_TIME_M_int);
		$PAUSE_TIME_S = ($PAUSE_TIME_S * 60);
		$PAUSE_TIME_S = round($PAUSE_TIME_S, 0);
		if ($PAUSE_TIME_S < 10) {$PAUSE_TIME_S = "0$PAUSE_TIME_S";}
		if ($PAUSE_TIME_M_int < 10) {$PAUSE_TIME_M_int = "0$PAUSE_TIME_M_int";}
		$PAUSE_TIME_HMS = "$PAUSE_TIME_H_int:$PAUSE_TIME_M_int:$PAUSE_TIME_S";
		$pfPAUSE_TIME_HMS =		sprintf("%8s", $PAUSE_TIME_HMS);

		$WAIT_TIME_H = MathZDC($row[5], 3600);
		$WAIT_TIME_H = round($WAIT_TIME_H, 2);
		$WAIT_TIME_H_int = intval("$WAIT_TIME_H");
		$WAIT_TIME_M = ($WAIT_TIME_H - $WAIT_TIME_H_int);
		$WAIT_TIME_M = ($WAIT_TIME_M * 60);
		$WAIT_TIME_M = round($WAIT_TIME_M, 2);
		$WAIT_TIME_M_int = intval("$WAIT_TIME_M");
		$WAIT_TIME_S = ($WAIT_TIME_M - $WAIT_TIME_M_int);
		$WAIT_TIME_S = ($WAIT_TIME_S * 60);
		$WAIT_TIME_S = round($WAIT_TIME_S, 0);
		if ($WAIT_TIME_S < 10) {$WAIT_TIME_S = "0$WAIT_TIME_S";}
		if ($WAIT_TIME_M_int < 10) {$WAIT_TIME_M_int = "0$WAIT_TIME_M_int";}
		$WAIT_TIME_HMS = "$WAIT_TIME_H_int:$WAIT_TIME_M_int:$WAIT_TIME_S";
		$pfWAIT_TIME_HMS =		sprintf("%8s", $WAIT_TIME_HMS);

		$WRAPUP_TIME_H = MathZDC($row[7], 3600);
		$WRAPUP_TIME_H = round($WRAPUP_TIME_H, 2);
		$WRAPUP_TIME_H_int = intval("$WRAPUP_TIME_H");
		$WRAPUP_TIME_M = ($WRAPUP_TIME_H - $WRAPUP_TIME_H_int);
		$WRAPUP_TIME_M = ($WRAPUP_TIME_M * 60);
		$WRAPUP_TIME_M = round($WRAPUP_TIME_M, 2);
		$WRAPUP_TIME_M_int = intval("$WRAPUP_TIME_M");
		$WRAPUP_TIME_S = ($WRAPUP_TIME_M - $WRAPUP_TIME_M_int);
		$WRAPUP_TIME_S = ($WRAPUP_TIME_S * 60);
		$WRAPUP_TIME_S = round($WRAPUP_TIME_S, 0);
		if ($WRAPUP_TIME_S < 10) {$WRAPUP_TIME_S = "0$WRAPUP_TIME_S";}
		if ($WRAPUP_TIME_M_int < 10) {$WRAPUP_TIME_M_int = "0$WRAPUP_TIME_M_int";}
		$WRAPUP_TIME_HMS = "$WRAPUP_TIME_H_int:$WRAPUP_TIME_M_int:$WRAPUP_TIME_S";
		$pfWRAPUP_TIME_HMS =		sprintf("%8s", $WRAPUP_TIME_HMS);

		$TALK_AVG_M = MathZDC($row[2], 60);
		$TALK_AVG_M = round($TALK_AVG_M, 2);
		$TALK_AVG_M_int = intval("$TALK_AVG_M");
		$TALK_AVG_S = ($TALK_AVG_M - $TALK_AVG_M_int);
		$TALK_AVG_S = ($TALK_AVG_S * 60);
		$TALK_AVG_S = round($TALK_AVG_S, 0);
		if ($TALK_AVG_S < 10) {$TALK_AVG_S = "0$TALK_AVG_S";}
		$TALK_AVG_MS = "$TALK_AVG_M_int:$TALK_AVG_S";
		$pfTALK_AVG_MS =		sprintf("%6s", $TALK_AVG_MS);

		$PAUSE_AVG_M = MathZDC($row[4], 60);
		$PAUSE_AVG_M = round($PAUSE_AVG_M, 2);
		$PAUSE_AVG_M_int = intval("$PAUSE_AVG_M");
		$PAUSE_AVG_S = ($PAUSE_AVG_M - $PAUSE_AVG_M_int);
		$PAUSE_AVG_S = ($PAUSE_AVG_S * 60);
		$PAUSE_AVG_S = round($PAUSE_AVG_S, 0);
		if ($PAUSE_AVG_S < 10) {$PAUSE_AVG_S = "0$PAUSE_AVG_S";}
		$PAUSE_AVG_MS = "$PAUSE_AVG_M_int:$PAUSE_AVG_S";
		$pfPAUSE_AVG_MS =		sprintf("%6s", $PAUSE_AVG_MS);

		$WAIT_AVG_M = MathZDC($row[6], 60);
		$WAIT_AVG_M = round($WAIT_AVG_M, 2);
		$WAIT_AVG_M_int = intval("$WAIT_AVG_M");
		$WAIT_AVG_S = ($WAIT_AVG_M - $WAIT_AVG_M_int);
		$WAIT_AVG_S = ($WAIT_AVG_S * 60);
		$WAIT_AVG_S = round($WAIT_AVG_S, 0);
		if ($WAIT_AVG_S < 10) {$WAIT_AVG_S = "0$WAIT_AVG_S";}
		$WAIT_AVG_MS = "$WAIT_AVG_M_int:$WAIT_AVG_S";
		$pfWAIT_AVG_MS =		sprintf("%6s", $WAIT_AVG_MS);

		$WRAPUP_AVG_M = MathZDC($row[8], 60);
		$WRAPUP_AVG_M = round($WRAPUP_AVG_M, 2);
		$WRAPUP_AVG_M_int = intval("$WRAPUP_AVG_M");
		$WRAPUP_AVG_S = ($WRAPUP_AVG_M - $WRAPUP_AVG_M_int);
		$WRAPUP_AVG_S = ($WRAPUP_AVG_S * 60);
		$WRAPUP_AVG_S = round($WRAPUP_AVG_S, 0);
		if ($WRAPUP_AVG_S < 10) {$WRAPUP_AVG_S = "0$WRAPUP_AVG_S";}
		$WRAPUP_AVG_MS = "$WRAPUP_AVG_M_int:$WRAPUP_AVG_S";
		$pfWRAPUP_AVG_MS =		sprintf("%6s", $WRAPUP_AVG_MS);

	echo _QXZ("TOTAL CALLS TAKEN").": $row[0]\n";
	echo _QXZ("TALK TIME:",24)." $pfTALK_TIME_HMS "._QXZ("AVERAGE",11,"r").": $pfTALK_AVG_MS\n";
	echo _QXZ("PAUSE TIME:",24)." $pfPAUSE_TIME_HMS "._QXZ("AVERAGE",11,"r").": $pfPAUSE_AVG_MS\n";
	echo _QXZ("WAIT TIME:",24)." $pfWAIT_TIME_HMS "._QXZ("AVERAGE",11,"r").": $pfWAIT_AVG_MS\n";
	echo _QXZ("WRAPUP TIME:",24)." $pfWRAPUP_TIME_HMS "._QXZ("AVERAGE",11,"r").": $pfWRAPUP_AVG_MS\n";
	echo "----------------------------------------------------------------\n";
	echo _QXZ("TOTAL ACTIVE AGENT TIME:",24)." $pfTOTAL_TIME_HMS\n";

	echo "\n";
	}
else
	{
	echo "<a href=\"$PHP_SELF?calls_summary=1&agent=$agent&query_date=$query_date\">"._QXZ("Call Activity Summary")."</a>\n\n";

	}

$stmt="select event_time,UNIX_TIMESTAMP(event_time) from vicidial_agent_log_archive where event_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and event_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and user='" . mysqli_real_escape_string($link, $agent) . "' order by event_time limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);

echo _QXZ("FIRST LOGIN:",21)." $row[0]\n";
$start = $row[1];

$stmt="select event_time,UNIX_TIMESTAMP(event_time) from vicidial_agent_log_archive where event_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and event_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and user='" . mysqli_real_escape_string($link, $agent) . "' order by event_time desc limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);

echo _QXZ("LAST LOG ACTIVITY:",21)." $row[0]\n";
$end = $row[1];

$login_time = ($end - $start);
	$LOGIN_TIME_H = MathZDC($login_time, 3600);
	$LOGIN_TIME_H = round($LOGIN_TIME_H, 2);
	$LOGIN_TIME_H_int = intval("$LOGIN_TIME_H");
	$LOGIN_TIME_M = ($LOGIN_TIME_H - $LOGIN_TIME_H_int);
	$LOGIN_TIME_M = ($LOGIN_TIME_M * 60);
	$LOGIN_TIME_M = round($LOGIN_TIME_M, 2);
	$LOGIN_TIME_M_int = intval("$LOGIN_TIME_M");
	$LOGIN_TIME_S = ($LOGIN_TIME_M - $LOGIN_TIME_M_int);
	$LOGIN_TIME_S = ($LOGIN_TIME_S * 60);
	$LOGIN_TIME_S = round($LOGIN_TIME_S, 0);
	if ($LOGIN_TIME_S < 10) {$LOGIN_TIME_S = "0$LOGIN_TIME_S";}
	if ($LOGIN_TIME_M_int < 10) {$LOGIN_TIME_M_int = "0$LOGIN_TIME_M_int";}
	$LOGIN_TIME_HMS = "$LOGIN_TIME_H_int:$LOGIN_TIME_M_int:$LOGIN_TIME_S";
	$pfLOGIN_TIME_HMS =		sprintf("%8s", $LOGIN_TIME_HMS);

echo "-----------------------------------------\n";
echo _QXZ("TOTAL LOGGED-IN TIME:",24)." $pfLOGIN_TIME_HMS\n";


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

}



?>

</BODY></HTML>