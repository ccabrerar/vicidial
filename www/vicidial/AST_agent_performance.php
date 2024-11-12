<?php 
# AST_agent_performance.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60619-1711 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 70201-1203 - Added non_latin UTF8 output code, widened USER ID to 8 chars
# 90508-0644 - Changed to PHP long tags
# 130610-1135 - Finalized changing of all ereg instances to preg
# 130621-0825 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130704-0943 - Fixed issue #675
# 130901-0828 - Changed to mysqli PHP functions
# 141114-0909 - Finalized adding QXZ translation to all admin files
# 141230-1524 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 170527-0102 - Added variable filtering
# 220303-1628 - Added allow_web_debug system setting
# 220812-0950 - Added User Group report permissions checking
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name='Agent Performance Report';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
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
if (!isset($group)) {$group = '';}
if (!isset($query_date)) {$query_date = $NOW_DATE;}

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
$shift = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$shift);
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

$stmt="select campaign_id from vicidial_campaigns;";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
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
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>"._QXZ("VICIDIAL: Agent Performance")."</TITLE></HEAD><BODY BGCOLOR=WHITE>\n";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=19 MAXLENGTH=19 VALUE=\"$query_date\">\n";
echo "<SELECT SIZE=1 NAME=group>\n";
	$o=0;
	while ($campaigns_to_print > $o)
	{
		if ($groups[$o] == $group) {echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
		  else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
		$o++;
	}
echo "</SELECT>\n";
echo "<SELECT SIZE=1 NAME=shift>\n";
echo "<option selected value=\"AM\">"._QXZ("AM")."</option>\n";
echo "<option value=\"PM\">"._QXZ("PM")."</option>\n";
echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=34&campaign_id=$group\">"._QXZ("MODIFY")."</a> | <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n";


if (!$group)
{
echo "\n";
echo _QXZ("PLEASE SELECT A SERVER AND DATE-TIME ABOVE AND CLICK SUBMIT")."\n";
echo " "._QXZ("NOTE: stats taken from 6 hour shift specified")."\n";
}

else
{
if ($shift == 'AM') 
	{
	$query_date_BEGIN = "$query_date 08:45:00";   
	$query_date_END = "$query_date 15:32:59";
	$time_BEGIN = "08:45:00";   
	$time_END = "15:33:00";
	}
if ($shift == 'PM') 
	{
	$query_date_BEGIN = "$query_date 15:33:00";   
	$query_date_END = "$query_date 23:15:00";
	$time_BEGIN = "15:33:00";   
	$time_END = "23:15:00";
	}

echo _QXZ("VICIDIAL: Agent Performance",55)." $NOW_TIME\n";

echo _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
echo "---------- "._QXZ("AGENTS Details")." -------------\n\n";

echo "+-----------------+----------+--------+--------+--------+------+------+------+------+------+------+------+\n";
echo "| "._QXZ("USER NAME",15)." | "._QXZ("ID",8)." | "._QXZ("CALLS",6)." | "._QXZ("TALK",6)." | "._QXZ("TALKAVG",7)."| A    | B    | DC   | DNC  | N    | NI   | SALE |\n";
echo "+-----------------+----------+--------+--------+--------+------+------+------+------+------+------+------+\n";

$stmt="select count(*) as calls,sum(length_in_sec) as talk,full_name,vicidial_users.user,avg(length_in_sec) from vicidial_users,vicidial_log where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' and vicidial_users.user=vicidial_log.user and campaign_id='" . mysqli_real_escape_string($link, $group) . "' group by full_name order by calls desc limit 1000;";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$rows_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $rows_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$TOTcalls=($TOTcalls + $row[0]);
	$TOTtotTALK=($TOTtotTALK + $row[1]);
	$calls[$i] =	sprintf("%-6s", $row[0]);

	if ($non_latin < 1)
	{
   	 $full_name[$i]=	sprintf("%-15s", $row[2]); 
	 while(strlen($full_name[$i])>15) {$full_name[$i] = substr("$full_name[$i]", 0, -1);}

	 $user[$i] =		sprintf("%-6s", $row[3]);
        while(strlen($user[$i])>6) {$user[$i] = substr("$user[$i]", 0, -1);}
       }
	else
	{	
        $full_name[$i]=	sprintf("%-45s", $row[2]); 
	 while(mb_strlen($full_name[$i],'utf-8')>15) {$full_name[$i] = mb_substr("$full_name[$i]", 0, -1,'utf-8');}

 	 $user[$i] =		sprintf("%-18s", $row[3]);
	 while(mb_strlen($user[$i],'utf-8')>6) {$user[$i] = mb_substr("$user[$i]", 0, -1,'utf-8');}
	}

	$user[$i] =		sprintf("%-8s", $row[3]);
	$USERtotTALK =	$row[1];
	$USERavgTALK =	$row[4];

	$USERtotTALK_M = ($USERtotTALK / 60);
	$USERtotTALK_M = round($USERtotTALK_M, 2);
	$USERtotTALK_M_int = intval("$USERtotTALK_M");
	$USERtotTALK_S = ($USERtotTALK_M - $USERtotTALK_M_int);
	$USERtotTALK_S = ($USERtotTALK_S * 60);
	$USERtotTALK_S = round($USERtotTALK_S, 0);
	if ($USERtotTALK_S < 10) {$USERtotTALK_S = "0$USERtotTALK_S";}
	$USERtotTALK_MS = "$USERtotTALK_M_int:$USERtotTALK_S";
	$pfUSERtotTALK_MS[$i] =		sprintf("%6s", $USERtotTALK_MS);

	$USERavgTALK_M = ($USERavgTALK / 60);
	$USERavgTALK_M = round($USERavgTALK_M, 2);
	$USERavgTALK_M_int = intval("$USERavgTALK_M");
	$USERavgTALK_S = ($USERavgTALK_M - $USERavgTALK_M_int);
	$USERavgTALK_S = ($USERavgTALK_S * 60);
	$USERavgTALK_S = round($USERavgTALK_S, 0);
	if ($USERavgTALK_S < 10) {$USERavgTALK_S = "0$USERavgTALK_S";}
	$USERavgTALK_MS = "$USERavgTALK_M_int:$USERavgTALK_S";
	$pfUSERavgTALK_MS[$i] =		sprintf("%6s", $USERavgTALK_MS);
	$i++;
	}

$k=0;
while($k < $i)
	{
	$ctA[$k]="0   "; $ctB[$k]="0   "; $ctDC[$k]="0   "; $ctDNC[$k]="0   "; $ctN[$k]="0   "; $ctNI[$k]="0   "; $ctSALE[$k]="0   "; 
	$stmt="select count(*),status from vicidial_log where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' and user='$user[$k]' and campaign_id='" . mysqli_real_escape_string($link, $group) . "' group by status;";
	if ($non_latin > 0)
	{
	$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);
	}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$m=0;
	while ($m < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		if ($row[1] == 'A') {$ctA[$k]=sprintf("%-4s", $row[0]);		$TOT_A = ($TOT_A + $row[0]);}
		if ($row[1] == 'B') {$ctB[$k]=sprintf("%-4s", $row[0]);		$TOT_B = ($TOT_B + $row[0]);}
		if ($row[1] == 'DC') {$ctDC[$k]=sprintf("%-4s", $row[0]);	$TOT_DC = ($TOT_DC + $row[0]);}
		if ($row[1] == 'DNC') {$ctDNC[$k]=sprintf("%-4s", $row[0]);	$TOT_DNC = ($TOT_DNC + $row[0]);}
		if ($row[1] == 'N') {$ctN[$k]=sprintf("%-4s", $row[0]);		$TOT_N = ($TOT_N + $row[0]);}
		if ($row[1] == 'NI') {$ctNI[$k]=sprintf("%-4s", $row[0]);	$TOT_NI = ($TOT_NI + $row[0]);}
		if (($row[1] == 'SALE') || ($row[1] == 'XFER') ) {$ctSALE[$k]=sprintf("%-4s", $row[0]);	$TOT_SALE = ($TOT_SALE + $row[0]);}
		$m++;
		}
	echo "| $full_name[$k] | $user[$k] | $calls[$k] | $pfUSERtotTALK_MS[$k] | $pfUSERavgTALK_MS[$k] | $ctA[$k] | $ctB[$k] | $ctDC[$k] | $ctDNC[$k] | $ctN[$k] | $ctNI[$k] | $ctSALE[$k] |\n";



	$k++;
	}

	$TOTcalls =	sprintf("%-7s", $TOTcalls);

	$TOTtotTALK_M = ($TOTtotTALK / 60);
	$TOTtotTALK_M = round($TOTtotTALK_M, 2);
	$TOTtotTALK_M_int = intval("$TOTtotTALK_M");
	$TOTtotTALK_S = ($TOTtotTALK_M - $TOTtotTALK_M_int);
	$TOTtotTALK_S = ($TOTtotTALK_S * 60);
	$TOTtotTALK_S = round($TOTtotTALK_S, 0);
	if ($TOTtotTALK_S < 10) {$TOTtotTALK_S = "0$TOTtotTALK_S";}
	$TOTtotTALK_MS = "$TOTtotTALK_M_int:$TOTtotTALK_S";
	$TOTtotTALK_MS =		sprintf("%7s", $TOTtotTALK_MS);
		while(strlen($TOTtotTALK_MS)>7) {$TOTtotTALK_MS = substr("$TOTtotTALK_MS", 0, -1);}

	$TOT_A = sprintf("%-5s", $TOT_A);
	$TOT_B = sprintf("%-5s", $TOT_B);
	$TOT_DC = sprintf("%-5s", $TOT_DC);
	$TOT_DNC = sprintf("%-5s", $TOT_DNC);
	$TOT_N = sprintf("%-5s", $TOT_N);
	$TOT_NI = sprintf("%-5s", $TOT_NI);
	$TOT_SALE = sprintf("%-5s", $TOT_SALE);

echo "+-----------------+----------+--------+--------+--------+------+------+------+------+------+------+------+\n";
echo "|  "._QXZ("TOTALS",25)." | $TOTcalls| $TOTtotTALK_MS|        | $TOT_A| $TOT_B| $TOT_DC| $TOT_DNC| $TOT_N| $TOT_NI| $TOT_SALE|\n";
echo "+-----------------+----------+--------+--------+--------+------+------+------+------+------+------+------+\n";

echo "\n";

}



?>

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