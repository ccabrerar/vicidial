<?php 
# AST_VICIDIAL_ingrouplist.php
# 
# shows the agents logged into vicidial and set to take calls from in-group
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 100320-2102 - First Build
# 130414-0222 - Added report logging
# 130610-0954 - Finalized changing of all ereg instances to preg
# 130620-2217 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2002 - Changed to mysqli PHP functions
# 140108-0728 - Added webserver and hostname to report logging
# 141114-0658 - Finalized adding QXZ translation to all admin files
# 141230-0045 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 190216-0806 - Fix for user-group, in-group and campaign allowed/permissions matching issues
# 220301-1624 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='In-Group User List';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = '';}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($server_ip)) {$server_ip = '10.10.10.15';}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSenable_languages =			$row[1];
	$SSlanguage_method =			$row[2];
	$SSallow_web_debug =			$row[3];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9a-zA-Z]/', '', $group);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9\p{L}]/u', '', $group);
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

$stmt="select group_id,group_name from vicidial_inbound_groups order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$ingroups_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $ingroups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$group_id[$i] =$row[0];
	$group_name[$i] =$row[1];
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
echo "<TITLE>"._QXZ("Live In-Group Agent Report")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

	require("admin_header.php");

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<SELECT SIZE=1 NAME=group>\n";
$o=0;
while ($ingroups_to_print > $o)
	{
	if ($group_id[$o] == $group) 
		{
		echo "<option selected value=\"$group_id[$o]\">$group_id[$o] - $group_name[$o]</option>\n";
		$selected_name=$group_name[$o];
		}
	else 
		{
		echo "<option value=\"$group_id[$o]\">$group_id[$o] - $group_name[$o]</option>\n";
		}
	$o++;
	}
echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=3111&group_id=$group\">"._QXZ("MODIFY")."</a> \n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n\n";


if (!$group)
	{
	echo "\n\n";
	echo _QXZ("PLEASE SELECT A IN-GROUP ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	echo _QXZ("Live Current Agents logged in to take calls from")." $group - $selected_name         $NOW_TIME\n";

	echo "\n";

	$SQL_group_id = preg_replace("/_/",'\\_',$group);
	$stmt="select count(*) from vicidial_live_agents where closer_campaigns LIKE\"% $SQL_group_id %\";";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);

	$TOTALagents =	sprintf("%10s", $row[0]);

	echo _QXZ("Total agents").":       $TOTALagents\n";


	##############################
	#########  LIVE AGENT STATS
	$user_list='|';
	echo "\n";
	echo "---------- "._QXZ("LIVE AGENTS IN IN-GROUP")."\n";
	echo "+------+--------------------------------+----------------------+--------+---------------------+\n";
	echo "| #    | "._QXZ("USER",30)." | "._QXZ("CAMPAIGN",20)." | "._QXZ("STATUS",6)." | "._QXZ("LAST ACTIVITY",19)." |\n";
	echo "+------+--------------------------------+----------------------+--------+---------------------+\n";

	$stmt="select vla.user,vu.full_name,vla.campaign_id,vla.status,vla.last_state_change from vicidial_live_agents vla,vicidial_users vu where vla.closer_campaigns LIKE\"% " . mysqli_real_escape_string($link, $group) . " %\" and vla.user=vu.user order by vla.user limit 1000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$users_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $users_to_print)
		{
		$row=mysqli_fetch_row($rslt);

		$i++;

		$FMT_i =		sprintf("%-4s", $i);
		$user =			sprintf("%-30s", "$row[0] - $row[1]");
			while(strlen($user)>30) {$user = substr("$user", 0, -1);}
		$campaign_id =	sprintf("%-8s", $row[2]);
		$status =		sprintf("%-6s", $row[3]);
		$time =			sprintf("%-19s", $row[4]);
		$user_list .=	"$row[0]---$row[3]|";

		echo "| $FMT_i | <a href=\"./user_status.php?user=$row[0]\">$user</a> | <a href=\"./admin.php?ADD=34&campaign_id=$row[2]\">$campaign_id</a>   <a href=\"./AST_timeonVDADall.php?RR=4&DB=0&group=$row[2]\">"._QXZ("Real-Time",9)."</a> | $status | $time |\n";
		}

	echo "+------+--------------------------------+----------------------+--------+---------------------+\n";

	
	if ($DB) {echo "\n$user_list\n";}

	##############################
	#########  ALL AGENT STATS

	echo "\n";
	echo "---------- "._QXZ("DEFAULT AGENTS IN IN-GROUP")."\n";
	echo "+------+--------------------------------+-----------+\n";
	echo "| #    | "._QXZ("USER",30)." | "._QXZ("LOGGED IN",9)." |\n";
	echo "+------+--------------------------------+-----------+\n";

	$stmt="select vu.user,vu.full_name from vicidial_users vu where vu.closer_campaigns LIKE\"% " . mysqli_real_escape_string($link, $group) . " %\" order by vu.user limit 2000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$Xusers_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $Xusers_to_print)
		{
		$row=mysqli_fetch_row($rslt);

		$i++;

		$FMT_i =		sprintf("%-4s", $i);
		$user =			sprintf("%-30s", "$row[0] - $row[1]");
			while(strlen($user)>30) {$user = substr("$user", 0, -1);}
		if (preg_match("/\|$row[0]---/",$user_list))
			{
			$userstatusARY =	explode("|$row[0]---",$user_list);
			$userstatus =		explode('|',$userstatusARY[1]);
			$status_line =		sprintf("%-9s", $userstatus[0]);

			if ($DB) {echo "\n$user_list     |$row[0]---     $userstatusARY[1]     $userstatus[0]\n";}
			}
		else
			{$status_line = '         ';}

		echo "| $FMT_i | <a href=\"./user_status.php?user=$row[0]\">$user</a> | $status_line |\n";
		}

	echo "+------+--------------------------------+-----------+\n";
	
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
