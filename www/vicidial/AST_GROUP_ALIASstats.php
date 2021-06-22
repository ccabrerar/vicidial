<?php 
# AST_GROUP_ALIASstats.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# If you are going to run this report I would recommend adding the following in MySQL:
# CREATE INDEX extension on call_log (extension);
#
# CHANGES
#
# 90914-1003 - First build
# 130414-0214 - Added report logging
# 130610-1016 - Finalized changing of all ereg instances to preg
# 130621-0751 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0732 - Changed to mysqli PHP functions
# 140108-0740 - Added webserver and hostname to report logging
# 141114-0842 - Finalized adding QXZ translation to all admin files
# 141230-1502 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
#

$startMS = microtime();

$report_name='Group Alias Report';

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
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}

if (strlen($shift)<2) {$shift='ALL';}

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = "$NOW_DATE 00:00:00";}
if (!isset($end_date)) {$end_date = "$NOW_DATE 23:59:59";}


?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: black; background-color: #99FF99}
   .red {color: black; background-color: #FF9999}
   .orange {color: black; background-color: #FFCC99}
-->
 </STYLE>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>Group Alias Report</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

	require("admin_header.php");

if ($DB > 0)
	{
	echo "<BR>\n";
	echo "$query_date|$end_date\n";
	echo "<BR>\n";
	}

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2><TR><TD align=center valign=top>\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=20 MAXLENGTH=20 VALUE=\"$query_date\">\n";
echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=20 MAXLENGTH=20 VALUE=\"$end_date\">\n";
echo "</TD><TD align=center valign=top>\n";
echo "</TD><TD align=center valign=top>\n";
echo "</TD><TD align=center valign=top>\n";
echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
echo "<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</TD></TR></TABLE>\n";
echo "</FORM>\n";

echo "<PRE><FONT SIZE=2>";


if (!$query_date)
{
echo "\n\n";
echo "PLEASE SELECT A DATE RANGE ABOVE AND CLICK SUBMIT\n";
}

else
{
echo _QXZ("Group Alias Report",39)." $NOW_TIME\n";
echo "\n";
echo _QXZ("Time range")." $query_date "._QXZ("to")." $end_date\n\n";


### GRAB ALL RECORDS WITHIN RANGE FROM THE DATABASE ###
echo _QXZ("Group Alias Summary").":\n";
echo "+------------------------------------------------------------------------+------------+----------+\n";
echo "| "._QXZ("GROUP ALIAS",70)." | "._QXZ("MINUTES",10)." | "._QXZ("CALLS",8)." |\n";
echo "+------------------------------------------------------------------------+------------+----------+\n";

$stmt="SELECT count(*),ucl.group_alias_id,group_alias_name from user_call_log ucl,groups_alias ga where call_date >= '$query_date' and call_date <= '$end_date' and ucl.group_alias_id=ga.group_alias_id group by ucl.group_alias_id order by ucl.group_alias_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$records_to_grab = mysqli_num_rows($rslt);
$i=0;
while ($i < $records_to_grab)
	{
	$row=mysqli_fetch_row($rslt);
	$TOTcount = ($TOTcount + $row[0]);
	$count[$i] =	sprintf("%-8s", $row[0]);
	$group_alias_id[$i] =	$row[1];
	$group_alias_name[$i] =	sprintf("%-70s", "$row[1] - $row[2]");
	$i++;
	}

$total_sec=0;
$i=0;
while ($i < $records_to_grab)
	{
	$stmt="SELECT UNIX_TIMESTAMP(call_date),call_type,phone_number,number_dialed from user_call_log where group_alias_id='$group_alias_id[$i]' and call_date >= '$query_date' and call_date <= '$end_date';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$records_to_grabC = mysqli_num_rows($rslt);
	$k=0;
	while ($k < $records_to_grabC)
		{
		$row=mysqli_fetch_row($rslt);
		$call_dateS[$k] =		($row[0] - 5);
		$call_dateE[$k] =		($row[0] + 5);
		$call_type[$k] =		$row[1];
		$phone_number[$k] =		$row[2];
		$number_dialed[$k] =	$row[3];
		$k++;
		}

	$found_sec=0;
	$group_sec=0;
	$k=0;
	while ($k < $records_to_grabC)
		{
		$stmt="SELECT length_in_sec from call_log where extension='$phone_number[$k]'";
		if (preg_match("/MANUAL_OVERRIDE/i",$call_type[$k]))
			{$stmt="SELECT length_in_sec from call_log where extension='$phone_number[$k]'";}
		if (preg_match("/XFER_OVERRIDE/i",$call_type[$k]))
			{
			$number_dialed[$k] = preg_replace('/Local\//i', '',$number_dialed[$k]);
			$number_dialed[$k] = preg_replace('/\@default/i', '',$number_dialed[$k]);
			$stmt="SELECT length_in_sec from call_log where extension='$number_dialed[$k]'";
			}
		if (preg_match("/XFER_3WAY/i",$call_type[$k]))
			{
			$number_dialed[$k] = preg_replace('/Local\//i', '',$number_dialed[$k]);
			$number_dialed[$k] = preg_replace('/\@default/i', '',$number_dialed[$k]);
			$stmt="SELECT length_in_sec from call_log where extension='$number_dialed[$k]'";
			}
		if (preg_match("/MANUAL_DIALNOW/i",$call_type[$k]))
			{$stmt="SELECT length_in_sec from call_log where extension='$phone_number[$k]'";}
		if (preg_match("/MANUAL_DIALFAST/i",$call_type[$k]))
			{$stmt="SELECT length_in_sec from call_log where extension='$phone_number[$k]'";}

		$stmt .= " and start_epoch >= $call_dateS[$k] and start_epoch <= $call_dateE[$k];";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$records_to_grabCL = mysqli_num_rows($rslt);
		if ($records_to_grabCL > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$length_in_sec[$k] =		$row[0];
			$group_sec = ($group_sec + $row[0]);
			$total_sec = ($total_sec + $row[0]);
			$found_sec++;
			}
		$k++;
		}

	$Ccall_time_MS =		sec_convert($group_sec,'M'); 
	$Ccall_time_MS =		sprintf("%10s", $Ccall_time_MS);
	echo "| $group_alias_name[$i] | $Ccall_time_MS | $count[$i] |";
	if ($DB) {echo "$found_sec";}
	echo "\n";
	$i++;
	}

$TOTcount =	sprintf("%-8s", $TOTcount);

$Tcall_time_MS =		sec_convert($total_sec,'M'); 
$Tcall_time_MS =		sprintf("%10s", $Tcall_time_MS);

echo "+------------------------------------------------------------------------+------------+----------+\n";
echo "                                                                         | $Tcall_time_MS | $TOTcount |\n";
echo "                                                                         +------------+----------+\n";
echo "\n</PRE>\n";



$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "<BR><BR>\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."\n";

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

</TD></TR></TABLE>

</BODY></HTML>
