<?php 
# AST_inboundEXTstats.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 60421-1450 - check GET/POST vars lines with isset to not trigger PHP NOTICES
# 60620-1322 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 90508-0644 - Changed to PHP long tags
# 130610-1134 - Finalized changing of all ereg instances to preg
# 130621-0745 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0729 - Changed to mysqli PHP functions
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0836 - Finalized adding QXZ translation to all admin files
# 141230-1452 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 220303-1623 - Added allow_web_debug system setting
# 220812-0954 - Added User Group report permissions checking
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name='Inbound Extension Stats';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($server_ip)) {$server_ip = '10.10.11.20';}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$SSallow_web_debug =		$row[3];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$group = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$group);
$query_date = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$query_date);
$server_ip = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$server_ip);
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

$stmt="select extension,full_number,inbound_name from inbound_numbers where server_ip='" . mysqli_real_escape_string($link, $server_ip) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$inbound_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $inbound_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$inbound[$i] =$row[0];
	$fullnum[$i] =$row[1];
	$inbname[$i] =$row[2];
	$i++;
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
echo"<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
#echo"<META HTTP-EQUIV=Refresh CONTENT=\"7; URL=$PHP_SELF?server_ip=$server_ip&DB=$DB\">\n";
echo "<TITLE>"._QXZ("ASTERISK: Inbound Calls Stats")."</TITLE></HEAD><BODY BGCOLOR=WHITE>\n";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<INPUT TYPE=HIDDEN NAME=server_ip VALUE=\"$server_ip\">\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">\n";
echo "<SELECT SIZE=1 NAME=group>\n";
	$o=0;
	while ($inbound_to_print > $o)
	{
		if ($inbound[$o] == $group) {echo "<option selected value=\"$inbound[$o]\">$fullnum[$o] - $inbname[$o]</option>\n";}
		  else {echo "<option value=\"$inbound[$o]\">$fullnum[$o] - $inbname[$o]</option>\n";}
		$o++;
	}
echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n\n";


if (!$group)
{
echo "\n\n";
echo _QXZ("PLEASE SELECT A NUMBER AND DATE ABOVE AND CLICK SUBMIT")."\n";
}

else
{


echo _QXZ("ASTERISK: Inbound Calls Stats",50)." $NOW_TIME\n";

echo "\n";
echo "---------- "._QXZ("TOTALS")."\n";

$extenSQL = "and extension='" . mysqli_real_escape_string($link, $group) . "'";
if (preg_match("/\*/i",$group))
	{$extenSQL = "and extension LIKE \"%$group\"";}
$stmt="select count(*),sum(length_in_sec) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " 00:00:01' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " 23:59:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL ;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);

$TOTALcalls =	sprintf("%10s", $row[0]);
$average_hold_seconds = MathZDC($row[1], $row[0]);
$average_hold_seconds = round($average_hold_seconds, 0);
$average_hold_seconds =	sprintf("%10s", $average_hold_seconds);

echo _QXZ("Total Calls That came into this number:",45)." $TOTALcalls\n";
echo _QXZ("Average Call Length(seconds) for all Calls:",45)." $average_hold_seconds\n";

echo "\n";
echo "---------- "._QXZ("DROPS")."\n";

$stmt="select count(*),sum(length_in_sec) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " 00:00:01' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " 23:59:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL and (length_in_sec <= 10 or length_in_sec is null);";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);

$DROPcalls =	sprintf("%10s", $row[0]);
$DROPpercent = (MathZDC($DROPcalls, $TOTALcalls) * 100);
$DROPpercent = round($DROPpercent, 0);

if ($row[0])
	{
	$average_hold_seconds = MathZDC($row[1], $row[0]);
	$average_hold_seconds = round($average_hold_seconds, 0);
	$average_hold_seconds =	sprintf("%10s", $average_hold_seconds);
	}
else {$DROPpercent=0;   $average_hold_seconds=0;}
echo _QXZ("Total DROP Calls:   (less than 10 seconds)",45)." $DROPcalls  $DROPpercent%\n";
echo _QXZ("Average Call Length(seconds) for DROP Calls:",45)." $average_hold_seconds\n";


##############################
#########  CALLS STATS

echo "\n";
echo "---------- "._QXZ("CALL LISTINGS")."\n";
echo "+----------------------+----------------------+--------+---------------------+\n";
echo "| "._QXZ("CALLERID",20)." | "._QXZ("CALLERIDNAME",20)." | "._QXZ("LENGTH",6)." | "._QXZ("DATE TIME",19)." |\n";
echo "+----------------------+----------------------+--------+---------------------+\n";

$stmt="select number_dialed,caller_code,length_in_sec,start_time from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " 00:00:01' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " 23:59:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL ;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$CID =			sprintf("%-20s", $row[0]);
	$CIDname =		sprintf("%-20s", $row[1]); while(strlen($full_name)>15) {$full_name = substr("$full_name", 0, -1);}
	$datetime =		sprintf("%-19s", $row[3]);
	$USERavgTALK =	$row[2];

	$USERavgTALK_M = MathZDC($USERavgTALK, 60);
	$USERavgTALK_M = round($USERavgTALK_M, 2);
	$USERavgTALK_M_int = intval("$USERavgTALK_M");
	$USERavgTALK_S = ($USERavgTALK_M - $USERavgTALK_M_int);
	$USERavgTALK_S = ($USERavgTALK_S * 60);
	$USERavgTALK_S = round($USERavgTALK_S, 0);
	if ($USERavgTALK_S < 10) {$USERavgTALK_S = "0$USERavgTALK_S";}
	$USERavgTALK_MS = "$USERavgTALK_M_int:$USERavgTALK_S";
	$USERavgTALK_MS =		sprintf("%6s", $USERavgTALK_MS);

	echo "| $CID | $CIDname | $USERavgTALK_MS | $datetime |\n";

	$i++;
	}

echo "+----------------------+----------------------+--------+---------------------+\n";

##############################
#########  TIME STATS

if ($output == 'FULL')
	{

	echo "\n";
	echo "---------- "._QXZ("TIME STATS")."\n";

	echo "<FONT SIZE=0>\n";

	$hi_hour_count=0;
	$last_full_record=0;
	$i=0;
	$h=0;
	while ($i <= 96)
		{
		$stmt="select count(*) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " $h:00:00' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " $h:14:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL ;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$hour_count[$i] = $row[0];
		if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
		if ($hour_count[$i] > 0) {$last_full_record = $i;}
		$stmt="select count(*) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " $h:00:00' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " $h:14:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL and (length_in_sec <= 10 or length_in_sec is null);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$drop_count[$i] = $row[0];
		$i++;


		$stmt="select count(*) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " $h:15:00' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " $h:29:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL ;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$hour_count[$i] = $row[0];
		if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
		if ($hour_count[$i] > 0) {$last_full_record = $i;}
		$stmt="select count(*) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " $h:15:00' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " $h:29:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL and (length_in_sec <= 10 or length_in_sec is null);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$drop_count[$i] = $row[0];
		$i++;

		$stmt="select count(*) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " $h:30:00' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " $h:44:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL ;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$hour_count[$i] = $row[0];
		if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
		if ($hour_count[$i] > 0) {$last_full_record = $i;}
		$stmt="select count(*) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " $h:30:00' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " $h:44:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL and (length_in_sec <= 10 or length_in_sec is null);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$drop_count[$i] = $row[0];
		$i++;

		$stmt="select count(*) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " $h:45:00' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " $h:59:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL ;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$hour_count[$i] = $row[0];
		if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
		if ($hour_count[$i] > 0) {$last_full_record = $i;}
		$stmt="select count(*) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " $h:45:00' and start_time <= '" . mysqli_real_escape_string($link, $query_date) . " $h:59:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' $extenSQL and (length_in_sec <= 10 or length_in_sec is null);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);
		$drop_count[$i] = $row[0];
		$i++;
		$h++;
		}

	$hour_multiplier = MathZDC(100, $hi_hour_count);
	#$hour_multiplier = round($hour_multiplier, 0);

	echo "<!-- HICOUNT: $hi_hour_count|$hour_multiplier -->\n";
	echo _QXZ("GRAPH IN 15 MINUTE INCREMENTS OF TOTAL CALLS")."\n";

	$k=1;
	$Mk=0;
	$call_scale = '0';
	while ($k <= 102) 
		{
		if ($Mk >= 5) 
			{
			$Mk=0;
			$scale_num=MathZDC($k, $hour_multiplier);
			$scale_num = round($scale_num, 0);
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


	echo "+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n";
	#echo "| HOUR | GRAPH IN 15 MINUTE INCREMENTS OF TOTAL INCOMING CALLS FOR THIS GROUP                                  | DROPS | TOTAL |\n";
	echo "| "._QXZ("HOUR",4)." |$call_scale| "._QXZ("DROPS",5)." | "._QXZ("TOTAL",5)." |\n";
	echo "+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n";

	$ZZ = '00';
	$i=0;
	$h=4;
	$hour= -1;
	$no_lines_yet=1;

	while ($i <= 96)
		{
		$char_counter=0;
		$time = '      ';
		if ($h >= 4) 
			{
			$hour++;
			$h=0;
			if ($hour < 10) {$hour = "0$hour";}
			$time = "+$hour$ZZ+";
			}
		if ($h == 1) {$time = "   15 ";}
		if ($h == 2) {$time = "   30 ";}
		if ($h == 3) {$time = "   45 ";}
		$Ghour_count = $hour_count[$i];
		if ($Ghour_count < 1) 
			{
			if ( ($no_lines_yet) or ($i > $last_full_record) )
				{
				$do_nothing=1;
				}
			else
				{
				$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);
				echo "|$time|";
				$k=0;   while ($k <= 102) {echo " ";   $k++;}
				echo "| $hour_count[$i] |\n";
				}
			}
		else
			{
			$no_lines_yet=0;
			$Xhour_count = ($Ghour_count * $hour_multiplier);
			$Yhour_count = (99 - $Xhour_count);

			$Gdrop_count = $drop_count[$i];
			if ($Gdrop_count < 1) 
				{
				$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);

				echo "|$time|<SPAN class=\"green\">";
				$k=0;   while ($k <= $Xhour_count) {echo "*";   $k++;   $char_counter++;}
				echo "*X</SPAN>";   $char_counter++;
				$k=0;   while ($k <= $Yhour_count) {echo " ";   $k++;   $char_counter++;}
					while ($char_counter <= 101) {echo " ";   $char_counter++;}
				echo "| 0     | $hour_count[$i] |\n";

				}
			else
				{
				$Xdrop_count = ($Gdrop_count * $hour_multiplier);

			#	if ($Xdrop_count >= $Xhour_count) {$Xdrop_count = ($Xdrop_count - 1);}

				$XXhour_count = ( ($Xhour_count - $Xdrop_count) - 1 );

				$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);
				$drop_count[$i] =	sprintf("%-5s", $drop_count[$i]);

				echo "|$time|<SPAN class=\"red\">";
				$k=0;   while ($k <= $Xdrop_count) {echo ">";   $k++;   $char_counter++;}
				echo "D</SPAN><SPAN class=\"green\">";   $char_counter++;
				$k=0;   while ($k <= $XXhour_count) {echo "*";   $k++;   $char_counter++;}
				echo "X</SPAN>";   $char_counter++;
				$k=0;   while ($k <= $Yhour_count) {echo " ";   $k++;   $char_counter++;}
					while ($char_counter <= 102) {echo " ";   $char_counter++;}
				echo "| $drop_count[$i] | $hour_count[$i] |\n";
				}
			}
		
		
		$i++;
		$h++;
		}


	echo "+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n";


	}




}



?>
</PRE>

</BODY></HTML>

<?php
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