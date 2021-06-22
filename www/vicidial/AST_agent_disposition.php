<?php 
# AST_agent_disposition.php
#
# Date Range - Agent/Campaign Disposition (Perfect Network Corporation)
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 70201-1213 - First build - from Marin Blu
# 90508-0644 - Changed to PHP long tags
# 130610-1136 - Finalized changing of all ereg instances to preg
# 130621-0828 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0746 - Changed to mysqli PHP functions
# 141114-0911 - Finalized adding QXZ translation to all admin files
# 141230-1525 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))		{$query_date=$_POST["query_date"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	}
##### END SETTINGS LOOKUP #####
###########################################

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
$group = preg_replace("/'|\"|\\\\|;/","",$group);

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
	
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = '';}
if (!isset($begin_date)) {$begin_date =  $NOW_DATE;}
if (!isset($end_date)) {$end_date =  $NOW_DATE;}

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
echo "<TITLE>"._QXZ("VICIDIAL: Agent Disposition")."</TITLE></HEAD><BODY BGCOLOR=WHITE>\n";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Date - From").": <input type=text name=begin_date value=\"$begin_date\" size=10 maxsize=10> "._QXZ("to")." \n";
echo "<input type=text name=end_date value=\"$end_date\" size=10 maxsize=10> &nbsp;\n";
echo "<SELECT SIZE=1 NAME=group>\n";
	$o=0;
	while ($campaigns_to_print > $o)
	{
		if ($groups[$o] == $group) {echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
		  else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
		$o++;
	}

echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=34&campaign_id=$group\">"._QXZ("MODIFY")."</a> | <a href=\"./server_stats.php\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n";


if (!$group)
{
echo "\n";
echo _QXZ("PLEASE SELECT A SERVER AND DATE ABOVE AND CLICK SUBMIT")."\n";
}

else
{

echo _QXZ("VICIDIAL: Agent Disposition",55)." $NOW_TIME\n";

echo _QXZ("Time range").": $begin_date "._QXZ("to")." $end_date \n\n";
echo "---------- "._QXZ("Disposition Details")." -------------\n\n";

echo "+-----------------+------------+--------+--------+--------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+\n";
echo "| "._QXZ("USER NAME",15)." | "._QXZ("ID",10)." | "._QXZ("CALLS",6)." | "._QXZ("TALK",6)." | "._QXZ("TALKAVG",7)."| A    | B    |CALLBK|CBHOLD| DEC  | DC   | DNC  | DROP |INCALL| LB   | N    | NA   | NI   | NP   | NQ   |QUEUE | SALE | XFER |\n";
echo "+-----------------+------------+--------+--------+--------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+\n";

$stmt="select count(*) as calls,sum(length_in_sec) as talk,full_name,vicidial_users.user,avg(length_in_sec) from vicidial_users,vicidial_log where call_date >= '$begin_date 00:00:01' and call_date <= '$end_date 23:59:59'  and vicidial_users.user=vicidial_log.user and campaign_id='" . mysqli_real_escape_string($link, $group) . "' group by full_name order by calls desc limit 1000;";
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
	$full_name[$i]=	sprintf("%-15s", $row[2]); while(strlen($full_name[$i])>15) {$full_name[$i] = substr("$full_name[$i]", 0, -1);}
	$user[$i] =		sprintf("%-10s", $row[3]); while(strlen($user[$i])>10) {$user[$i] = substr("$user[$i]", 0, -1);}
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
	$ctA[$k]="0   "; $ctB[$k]="0   "; $ctDC[$k]="0   "; $ctDNC[$k]="0   "; $ctN[$k]="0   "; $ctNI[$k]="0   "; $ctSALE[$k]="0   ";  $ctINCALL[$k]="0   ";  $ctDROP[$k]="0   ";  $ctQUEUE[$k]="0   ";  $ctNP[$k]="0   ";   $ctDEC[$k]="0   ";  $ctNQ[$k]="0   ";  $ctNA[$k]="0   ";   $ctXFER[$k]="0   "; $ctLB[$k]="0   ";  $ctCALLBK[$k]="0   "; $ctCBHOLD[$k]="0   ";
	$stmt="select count(*),status from vicidial_log where call_date >= '$begin_date 00:00:01' and call_date <= '$end_date 23:59:59'  and user='$user[$k]' and campaign_id='" . mysqli_real_escape_string($link, $group) . "' group by status;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
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
		if ($row[1] == 'SALE') {$ctSALE[$k]=sprintf("%-4s", $row[0]);	$TOT_SALE = ($TOT_SALE + $row[0]);}
		if ($row[1] == 'INCALL') {$ctINCALL[$k]=sprintf("%-4s", $row[0]);	$TOT_INCALL = ($TOT_INCALL + $row[0]);}
		if ($row[1] == 'DROP') {$ctDROP[$k]=sprintf("%-4s", $row[0]);	$TOT_DROP = ($TOT_DROP + $row[0]);}
		if ($row[1] == 'QUEUE') {$ctQUEUE[$k]=sprintf("%-4s", $row[0]);	$TOT_QUEUE = ($TOT_QUEUE + $row[0]);}
		if ($row[1] == 'NP') {$ctNP[$k]=sprintf("%-4s", $row[0]);	$TOT_NP = ($TOT_NP + $row[0]);}
		if ($row[1] == 'DEC') {$ctDEC[$k]=sprintf("%-4s", $row[0]);	$TOT_DEC = ($TOT_DEC + $row[0]);}
		if ($row[1] == 'NQ') {$ctNQ[$k]=sprintf("%-4s", $row[0]);	$TOT_NQ = ($TOT_NQ + $row[0]);}
		if ($row[1] == 'NA') {$ctNA[$k]=sprintf("%-4s", $row[0]);	$TOT_NA = ($TOT_NA + $row[0]);}
		if ($row[1] == 'XFER') {$ctXFER[$k]=sprintf("%-4s", $row[0]);	$TOT_XFER = ($TOT_XFER + $row[0]);}
		if ($row[1] == 'LB') {$ctLB[$k]=sprintf("%-4s", $row[0]);	$TOT_LB = ($TOT_LB + $row[0]);}
		if ($row[1] == 'CALLBK') {$ctCALLBK[$k]=sprintf("%-4s", $row[0]);	$TOT_CALLBK = ($TOT_CALLBK + $row[0]);}
		if ($row[1] == 'CBHOLD') {$ctCBHOLD[$k]=sprintf("%-4s", $row[0]);	$TOT_CBHOLD = ($TOT_CBHOLD + $row[0]);}
		$m++;
		}
	echo "| $full_name[$k] | $user[$k] | $calls[$k] | $pfUSERtotTALK_MS[$k] | $pfUSERavgTALK_MS[$k] | $ctA[$k] | $ctB[$k] | $ctCALLBK[$k] | $ctCBHOLD[$k] | $ctDEC[$k] | $ctDC[$k] | $ctDNC[$k] | $ctDROP[$k] | $ctINCALL[$k] | $ctLB[$k] | $ctN[$k] | $ctNA[$k] | $ctNI[$k] | $ctNP[$k] | $ctNQ[$k] | $ctQUEUE[$k] | $ctSALE[$k] | $ctXFER[$k] | \n";

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
	$TOT_INCALL = sprintf("%-5s", $TOT_INCALL);
	$TOT_DROP = sprintf("%-5s", $TOT_DROP); 
	$TOT_QUEUE = sprintf("%-5s", $TOT_QUEUE);
	$TOT_NP = sprintf("%-5s", $TOT_NP);
	$TOT_DEC = sprintf("%-5s", $TOT_DEC);
	$TOT_NQ = sprintf("%-5s", $TOT_NQ);
	$TOT_NA = sprintf("%-5s", $TOT_NA);
	$TOT_XFER = sprintf("%-5s", $TOT_XFER);
	$TOT_LB= sprintf("%-5s", $TOT_LB);
	$TOT_CALLBK = sprintf("%-5s", $TOT_CALLBK);
	$TOT_CBHOLD = sprintf("%-5s", $TOT_CBHOLD);

echo "+-----------------+------------+--------+--------+--------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+\n";
echo "|  "._QXZ("TOTALS",27)." | $TOTcalls| $TOTtotTALK_MS|        | $TOT_A| $TOT_B| $TOT_CALLBK| $TOT_CBHOLD| $TOT_DEC| $TOT_DC| $TOT_DNC| $TOT_DROP| $TOT_INCALL| $TOT_LB| $TOT_N| $TOT_NA| $TOT_NI| $TOT_NP| $TOT_NQ| $TOT_QUEUE| $TOT_SALE| $TOT_XFER|\n";
echo "+-----------------+------------+--------+--------+--------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+------+\n";
echo "\n";

}
?>
</BODY></HTML>
