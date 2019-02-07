<?php
# phone_stats.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 
# changes:
# 60620-1333 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 60927-1548 - Changed to vicidial_users for authentication
# 90508-0644 - Changed to PHP long tags
# 120223-2135 - Removed logging of good login passwords if webroot writable is enabled
# 130610-1110 - Finalized changing of all ereg instances to preg
# 130617-2156 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0900 - Changed to mysqli PHP functions
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0034 - Finalized adding QXZ translation to all admin files
# 141128-0903 - Code cleanup for QXZ functions
# 141230-0911 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["begin_date"]))				{$begin_date=$_GET["begin_date"];}
	elseif (isset($_POST["begin_date"]))	{$begin_date=$_POST["begin_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["extension"]))				{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))		{$extension=$_POST["extension"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["full_name"]))				{$full_name=$_GET["full_name"];}
	elseif (isset($_POST["full_name"]))		{$full_name=$_POST["full_name"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$user_territories_active =		$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	}
##### END SETTINGS LOOKUP #####
###########################################

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$admin_page = './admin.php';
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

if (!isset($begin_date)) {$begin_date = $TODAY;}
if (!isset($end_date)) {$end_date = $TODAY;}

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
$extension = preg_replace("/'|\"|\\\\|;/", '', $extension);
$server_ip = preg_replace("/'|\"|\\\\|;/", '', $server_ip);
$begin_date = preg_replace("/'|\"|\\\\|;/","",$begin_date);
$end_date = preg_replace("/'|\"|\\\\|;/","",$end_date);

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

$stmt="SELECT full_name from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname=$row[0];

##### get server listing for dynamic pulldown
$stmt="SELECT fullname from phones where server_ip='$server_ip' and extension='$extension';";
$rsltx=mysql_to_mysqli($stmt, $link);
$rowx=mysqli_fetch_row($rsltx);
$fullname = $row[0];

?>
<html>
<head>
<title><?php echo _QXZ("ADMIN: Phone Stats"); ?></title>
</head>
<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<CENTER>
<TABLE WIDTH=620 BGCOLOR=#D9E6FE cellpadding=2 cellspacing=0><TR BGCOLOR=#015B91><TD ALIGN=LEFT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B> &nbsp; <?php echo _QXZ("ADMIN: Administration"); ?></TD><TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo date("l F j, Y G:i:s A") ?> &nbsp; </TD></TR>
<TR BGCOLOR=#F0F5FE><TD ALIGN=LEFT COLSPAN=2><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><B> &nbsp; <a href="<?php echo $admin_page ?>?ADD=10000000000"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><?php echo _QXZ("LIST ALL PHONES"); ?></a> | <a href="<?php echo $admin_page ?>?ADD=11111111111"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><?php echo _QXZ("ADD A NEW PHONE"); ?></a> | <a href="<?php echo $admin_page ?>?ADD=551"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><?php echo _QXZ("SEARCH FOR A PHONE"); ?></a> | <a href="<?php echo $admin_page ?>?ADD=111111111111"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><?php echo _QXZ("ADD A SERVER"); ?></a> | <a href="<?php echo $admin_page ?>?ADD=100000000000"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><?php echo _QXZ("LIST ALL SERVERS"); ?></a></TD></TR>
<TR BGCOLOR=#F0F5FE><TD ALIGN=LEFT COLSPAN=2><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><B> &nbsp; <a href="<?php echo $admin_page ?>?ADD=1000000000000"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><?php echo _QXZ("SHOW ALL CONFERENCES"); ?></a> | <a href="<?php echo $admin_page ?>?ADD=1111111111111"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><?php echo _QXZ("ADD A NEW CONFERENCE"); ?></a></TD></TR>


<?php 

echo "<TR BGCOLOR=\"#F0F5FE\"><TD ALIGN=LEFT COLSPAN=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><B> &nbsp; \n";

echo "<form action=$PHP_SELF method=POST>\n";
echo "<input type=hidden name=extension value=\"$extension\">\n";
echo "<input type=hidden name=server_ip value=\"$server_ip\">\n";
echo "<input type=text name=begin_date value=\"$begin_date\" size=10 maxsize=10> "._QXZ("to")." \n";
echo "<input type=text name=end_date value=\"$end_date\" size=10 maxsize=10> &nbsp;\n";
echo "<input type=submit name=submit value='"._QXZ("submit")."'>\n";


echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; $user - $full_name\n";

echo "</B></TD></TR>\n";
echo "<TR><TD ALIGN=LEFT COLSPAN=2>\n";


$stmt="SELECT count(*),channel_group, sum(length_in_sec) from call_log where extension='" . mysqli_real_escape_string($link, $extension) . "' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' and start_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and start_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' group by channel_group order by channel_group";
$rslt=mysql_to_mysqli($stmt, $link);
$statuses_to_print = mysqli_num_rows($rslt);
#	echo "|$stmt|\n";

echo "<br><center>\n";

echo "<B>"._QXZ("CALL TIME AND CHANNELS").":</B>\n";

echo "<center><TABLE width=300 cellspacing=0 cellpadding=1>\n";
echo "<tr><td><font size=2>"._QXZ("CHANNEL GROUP")." </td><td align=right><font size=2>"._QXZ("COUNT")."</td><td align=right><font size=2> "._QXZ("HOURS:MINUTES")."</td></tr>\n";

$total_calls=0;
$o=0;
while ($statuses_to_print > $o) 
	{
	$row=mysqli_fetch_row($rslt);
	if (preg_match('/1$|3$|5$|7$|9$/i', $o))
		{$bgcolor='bgcolor="#B9CBFD"';} 
	else
		{$bgcolor='bgcolor="#9BB9FB"';}

	$call_seconds = $row[2];
	$call_hours = MathZDC($call_seconds, 3600);
	$call_hours = round($call_hours, 2);
	$call_hours_int = intval("$call_hours");
	$call_minutes = ($call_hours - $call_hours_int);
	$call_minutes = ($call_minutes * 60);
	$call_minutes_int = round($call_minutes, 0);
	if ($call_minutes_int < 10) {$call_minutes_int = "0$call_minutes_int";}

	echo "<tr $bgcolor><td><font size=2>$row[1]</td>";
	echo "<td align=right><font size=2> $row[0]</td>\n";
	echo "<td align=right><font size=2> $call_hours_int:$call_minutes_int</td></tr>\n";
	$total_calls = ($total_calls + $row[0]);

	$call_seconds=0;
	$o++;
	}

	$stmt="SELECT sum(length_in_sec) from call_log where extension='" . mysqli_real_escape_string($link, $extension) . "' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' and start_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and start_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59'";
	$rslt=mysql_to_mysqli($stmt, $link);
	$counts_to_print = mysqli_num_rows($rslt);
		$row=mysqli_fetch_row($rslt);
	$call_seconds = $row[0];
	$call_hours = MathZDC($call_seconds, 3600);
	$call_hours = round($call_hours, 2);
	$call_hours_int = intval("$call_hours");
	$call_minutes = ($call_hours - $call_hours_int);
	$call_minutes = ($call_minutes * 60);
	$call_minutes_int = round($call_minutes, 0);
	if ($call_minutes_int < 10) {$call_minutes_int = "0$call_minutes_int";}
#	echo "|$stmt|\n";

echo "<tr><td><font size=2>"._QXZ("TOTAL CALLS")." </td><td align=right><font size=2> $total_calls</td><td align=right><font size=2> $call_hours_int:$call_minutes_int</td></tr>\n";
echo "</TABLE></center>\n";
echo "<br><br>\n";

echo "<center>\n";

echo "<B>"._QXZ("LAST 1000 CALLS FOR DATE RANGE").":</B>\n";
echo "<TABLE width=400 cellspacing=0 cellpadding=1>\n";
echo "<tr><td><font size=2>"._QXZ("NUMBER")." </td><td><font size=2>"._QXZ("CHANNEL GROUP")." </td><td align=right><font size=2> "._QXZ("DATE")."</td><td align=right><font size=2> "._QXZ("LENGTH(MIN.)")."</td></tr>\n";

$stmt="SELECT number_dialed,channel_group,start_time,length_in_min from call_log where extension='" . mysqli_real_escape_string($link, $extension) . "' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' and start_time >= '" . mysqli_real_escape_string($link, $begin_date) . " 0:00:01'  and start_time <= '" . mysqli_real_escape_string($link, $end_date) . " 23:59:59' LIMIT 1000";
$rslt=mysql_to_mysqli($stmt, $link);
$events_to_print = mysqli_num_rows($rslt);
#	echo "|$stmt|\n";

$total_calls=0;
$o=0;
$event_start_seconds='';
$event_stop_seconds='';
while ($events_to_print > $o) 
	{
	$row=mysqli_fetch_row($rslt);
	if (preg_match('/1$|3$|5$|7$|9$/i', $o))
		{$bgcolor='bgcolor="#B9CBFD"';} 
	else
		{$bgcolor='bgcolor="#9BB9FB"';}
	echo "<tr $bgcolor><td><font size=2>$row[0]</td>";
	echo "<td align=right><font size=2> $row[1]</td>\n";
	echo "<td align=right><font size=2> $row[2]</td>\n";
	echo "<td align=right><font size=2> $row[3]</td></tr>\n";

	$call_seconds=0;
	$o++;
	}

echo "</TABLE></center>\n";

$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "\n\n\n<br><br><br>\n\n";
echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";

?>


</TD></TR><TABLE>
</body>
</html>

<?php
	
exit; 

?>

