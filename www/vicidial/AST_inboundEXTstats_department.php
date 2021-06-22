<?php 
# AST_inboundEXTstats_department.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 70201-1710 - First Build
# 90508-0644 - Changed to PHP long tags
# 130610-1134 - Finalized changing of all ereg instances to preg
# 130621-0743 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2028 - Changed to mysqli PHP functions
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0835 - Finalized adding QXZ translation to all admin files
# 141230-1450 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
#

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
if (isset($_GET["end_query_date"]))				{$end_query_date=$_GET["end_query_date"];}
	elseif (isset($_POST["end_query_date"]))	{$end_query_date=$_POST["end_query_date"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
$i=0;
while ($i < $qm_conf_ct)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$i++;
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
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_query_date)) {$end_query_date = $NOW_DATE;}
if (!isset($server_ip)) {$server_ip = '10.10.11.20';}

$stmt="select distinct department from inbound_numbers;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$dept_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $dept_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$dept[$i] =$row[0];
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
echo "<TITLE>"._QXZ("ASTERISK: Inbound Calls Stats - By Department")."</TITLE></HEAD><BODY BGCOLOR=WHITE>\n";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<INPUT TYPE=HIDDEN NAME=server_ip VALUE=\"$server_ip\">\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">\n";
echo "<INPUT TYPE=TEXT NAME=end_query_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_query_date\">\n";
echo "<SELECT SIZE=1 NAME=group>\n";
	$o=0;
	while ($dept_to_print > $o)
	{
	if ($dept[$o] == $group) {echo "<option selected value=\"$dept[$o]\">$dept[$o]</option>\n";}
	  else {echo "<option value=\"$dept[$o]\">$dept[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n\n";


if (!$group)
	{
	echo "\n\n";
	echo _QXZ("PLEASE SELECT A DEPARTMENT AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$extSQL='';
	$stmt="select extension from inbound_numbers where department='$group';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$inbound_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $inbound_to_print)
		{
		if (strlen($extSQL)> 1) {$extSQL .= ",";}
		$row=mysqli_fetch_row($rslt);
		$extensions[$i] =$row[0];
		$extSQL .= "'$extensions[$i]'";
		$i++;
		}

	echo _QXZ("ASTERISK: Inbound Calls Stats For")." $group   "._QXZ("from")." $query_date "._QXZ("to")." $end_query_date\n";

	echo "\n";
	echo "---------- "._QXZ("TOTALS")."\n";
	echo "\n";

	echo "+----------------------+------------+------------+\n";
	echo "| "._QXZ("NUMBER",20)." | "._QXZ("CALLS",10)." | "._QXZ("AVG TIME",10)." |\n";
	echo "+----------------------+------------+------------+\n";

	$k=0;
	while ($k < $inbound_to_print)
		{
		$stmt="select count(*),sum(length_in_sec) from call_log where start_time >= '" . mysqli_real_escape_string($link, $query_date) . " 00:00:01' and start_time <= '" . mysqli_real_escape_string($link, $end_query_date) . " 23:59:59' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' and extension='$extensions[$k]' ;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$row=mysqli_fetch_row($rslt);

		$extensions[$k] = sprintf("%20s", $extensions[$k]);
		$TOTALcalls =	sprintf("%10s", $row[0]);
		if ( ($row[0]<1) or ($row[1]<1) )
			{
			$average_hold_seconds = "         0";
			}
		else
			{
			$average_hold_seconds = MathZDC($row[1], $row[0]);
			$average_hold_seconds = round($average_hold_seconds, 0);
			$average_hold_seconds =	sprintf("%10s", $average_hold_seconds);
			}

		$calls = ($TOTALcalls + $calls);
		$seconds = ($row[1] + $seconds);

		echo "| $extensions[$k] | $TOTALcalls | $average_hold_seconds |\n";
		$k++;
		}

	$calls =	sprintf("%10s", $calls);
	$seconds = MathZDC($seconds, $calls);
	$seconds = round($seconds, 0);
	$seconds =	sprintf("%5s", $seconds);
	echo "+----------------------+------------+------------+\n";
	echo "| "._QXZ("TOTALS",20)." | $calls | AVG: $seconds |\n";
	echo "+----------------------+------------+------------+\n";
	}


?>
</PRE>

</BODY></HTML>
