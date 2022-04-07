<?php 
# AST_timeonVDAD.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# live real-time stats for the VICIDIAL Auto-Dialer
#
# CHANGES
#
# 60620-1037 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 61114-2004 - Changed to display CLOSER and DEFAULT, added trunk shortage
# 80422-0305 - Added phone login to display, lower font size to 2
# 81013-2227 - Fixed Remote Agent display bug
# 90310-1945 - Admin header
# 90508-0644 - Changed to PHP long tags
# 130610-1128 - Finalized changing of all ereg instances to preg
# 130620-2317 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2009 - Changed to mysqli PHP functions
# 141114-0721 - Finalized adding QXZ translation to all admin files
# 141230-1418 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 220303-1606 - Added allow_web_debug system setting
#

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["reset_counter"]))			{$reset_counter=$_GET["reset_counter"];}
	elseif (isset($_POST["reset_counter"]))	{$reset_counter=$_POST["reset_counter"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["closer_display"]))				{$closer_display=$_GET["closer_display"];}
	elseif (isset($_POST["closer_display"]))	{$closer_display=$_POST["closer_display"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
$epochSIXhoursAGO = ($STARTtime - 21600);
$timeSIXhoursAGO = date("Y-m-d H:i:s",$epochSIXhoursAGO);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin = 				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$SSallow_web_debug =		$row[3];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$server_ip = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$server_ip);
$reset_counter = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$reset_counter);
$closer_display = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$closer_display);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);

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

$reset_counter++;

if ($reset_counter > 7)
	{
	$reset_counter=0;

	$stmt="update park_log set status='HUNGUP' where hangup_time is not null;";
#	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}

	if ($DB)
		{	
		$stmt="delete from park_log where grab_time < '$timeSIXhoursAGO' and (hangup_time is null or hangup_time='');";
#		$rslt=mysql_to_mysqli($stmt, $link);
		 echo "$stmt\n";
		}
	}

?>

<HTML>
<HEAD>
<?php
echo "<STYLE type=\"text/css\">\n";
echo "<!--\n";

if ($closer_display>0)
{
	$stmt="select group_id,group_color from vicidial_inbound_groups;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$groups_to_print = mysqli_num_rows($rslt);
		if ($groups_to_print > 0)
		{
		$g=0;
		while ($g < $groups_to_print)
			{
			$row=mysqli_fetch_row($rslt);
			$group_id[$g] = $row[0];
			$group_color[$g] = $row[1];
			echo "   .$group_id[$g] {color: black; background-color: $group_color[$g]}\n";
			$g++;
			}
		}
}
?>
   .DEAD       {color: white; background-color: black}
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}
   .yellow {color: black; background-color: yellow}
-->
 </STYLE>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo"<META HTTP-EQUIV=Refresh CONTENT=\"4; URL=$PHP_SELF?server_ip=$server_ip&DB=$DB&reset_counter=$reset_counter&closer_display=$closer_display\">\n";
echo "<TITLE>"._QXZ("Server-Specific Real-Time Report")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

require("admin_header.php");

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

echo "<PRE><FONT SIZE=2>";

###################################################################################
###### SERVER INFORMATION
###################################################################################

$stmt="select sum(local_trunk_shortage) from vicidial_campaign_server_stats where server_ip='" . mysqli_real_escape_string($link, $server_ip) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$balanceSHORT = $row[0];

echo _QXZ("SERVER").": $server_ip\n";



###################################################################################
###### TIME ON SYSTEM
###################################################################################

if ($closer_display>0) {$closer_display_reverse=0;   $closer_reverse_link=_QXZ("DEFAULT");}
else {$closer_display_reverse=1;   $closer_reverse_link=_QXZ("CLOSER");}

echo _QXZ("Agents Time On Calls")."           $NOW_TIME    <a href=\"$PHP_SELF?server_ip=$server_ip&DB=$DB&reset_counter=$reset_counter&closer_display=$closer_display_reverse\">$closer_reverse_link</a> | <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>\n\n";

if ($closer_display>0)
{
echo "+------------+------------+--------+-----------+---------------------+--------+----------+---------+--------------+--------+\n";
echo "| "._QXZ("STATION",10)." | "._QXZ("PHONE",10)." | "._QXZ("USER",6)." | "._QXZ("SESSIONID",9)." | "._QXZ("CHANNEL",19)." | "._QXZ("STATUS",6)." | "._QXZ("CALLTIME",8)." | "._QXZ("MINUTES",7)." | "._QXZ("CAMPAIGN",12)." | "._QXZ("FRONT",6)." |\n";
echo "+------------+------------+--------+-----------+---------------------+--------+----------+---------+--------------+--------+\n";
}
else
{
echo "+------------+------------+--------+-----------+---------------------+--------+----------+---------+\n";
echo "| "._QXZ("STATION",10)." | "._QXZ("PHONE",10)." | "._QXZ("USER",6)." | "._QXZ("SESSIONID",9)." | "._QXZ("CHANNEL",19)." | "._QXZ("STATUS",6)." | "._QXZ("CALLTIME",8)." | "._QXZ("MINUTES",7)." |\n";
echo "+------------+------------+--------+-----------+---------------------+--------+----------+---------+\n";
}

$stmt="select extension,user,conf_exten,channel,status,last_call_time,UNIX_TIMESTAMP(last_call_time),UNIX_TIMESTAMP(last_call_finish),uniqueid,lead_id from vicidial_live_agents where status NOT IN('PAUSED') and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' order by extension;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$talking_to_print = mysqli_num_rows($rslt);
	if ($talking_to_print > 0)
	{
	$i=0;
	while ($i < $talking_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$Sextension[$i] =		$row[0];
		$Suser[$i] =			$row[1];
		$Ssessionid[$i] =		$row[2];
		$Schannel[$i] =			$row[3];
		$Sstatus[$i] =			$row[4];
		$Sstart_time[$i] =		$row[5];
		$Scall_time[$i] =		$row[6];
		$Sfinish_time[$i] =		$row[7];
		$Suniqueid[$i] =		$row[8];
		$Slead_id[$i] =			$row[9];
		$i++;
		}

	$i=0;
	while ($i < $talking_to_print)
		{
		$phone[$i]='          ';
		if (preg_match("/R\//i",$Sextension[$i])) 
			{
			$protocol = 'EXTERNAL';
			$dialplan = preg_replace('/R\//i', '',$Sextension[$i]);
			$dialplan = preg_replace('/\@.*/i', '',$dialplan);
			$exten = "dialplan_number='$dialplan'";
			}
		if (preg_match("/Local\//i",$Sextension[$i])) 
			{
			$protocol = 'EXTERNAL';
			$dialplan = preg_replace('/Local\//i', '',$Sextension[$i]);
			$dialplan = preg_replace('/\@.*/i', '',$dialplan);
			$exten = "dialplan_number='$dialplan'";
			}
		if (preg_match('/SIP\//i',$Sextension[$i])) 
			{
			$protocol = 'SIP';
			$dialplan = preg_replace('/SIP\//i', '',$Sextension[$i]);
			$dialplan = preg_replace('/\-.*/i', '',$dialplan);
			$exten = "extension='$dialplan'";
			}
		if (preg_match('/IAX2\//i',$Sextension[$i])) 
			{
			$protocol = 'IAX2';
			$dialplan = preg_replace('/IAX2\//i', '',$Sextension[$i]);
			$dialplan = preg_replace('/\-.*/i', '',$dialplan);
			$exten = "extension='$dialplan'";
			}
		if (preg_match('/Zap\//i',$Sextension[$i])) 
			{
			$protocol = 'Zap';
			$dialplan = preg_replace('/Zap\/|DAHDI\//i', '',$Sextension[$i]);
			$exten = "extension='$dialplan'";
			}

		$stmt="select login from phones where server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' and $exten and protocol='$protocol';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$login = $row[0];

		$phone[$i] =			sprintf("%-10s", $login);

		if (preg_match("/READY|PAUSED|CLOSER/i",$Sstatus[$i]))
			{
			$Schannel[$i]='';
			$Sstart_time[$i]='- WAIT -';
			$Scall_time[$i]=$Sfinish_time[$i];
			}
		$extension[$i] = preg_replace('/Local\//i', '',$Sextension[$i]);
		$extension[$i] =		sprintf("%-10s", $extension[$i]);
			while(strlen($extension[$i])>10) {$extension[$i] = substr("$extension[$i]", 0, -1);}
		$user[$i] =				sprintf("%-6s", $Suser[$i]);
		$sessionid[$i] =		sprintf("%-9s", $Ssessionid[$i]);
		$channel[$i] =			sprintf("%-19s", $Schannel[$i]);
			$cc[$i]=0;
		while ( (strlen($channel[$i]) > 19) and ($cc[$i] < 100) )
			{
			$channel[$i] = preg_replace('/.$/i', '',$channel[$i]);   
			$cc[$i]++;
			if (strlen($channel[$i]) <= 19) {$cc[$i]=101;}
			}
		$status[$i] =			sprintf("%-6s", $Sstatus[$i]);
		$start_time[$i] =		sprintf("%-8s", $Sstart_time[$i]);
			$cd[$i]=0;
		while ( (strlen($start_time[$i]) > 8) and ($cd[$i] < 100) )
			{
			$start_time[$i] = preg_replace('/^./i', '',$start_time[$i]);   
			$cd[$i]++;
			if (strlen($start_time[$i]) <= 8) {$cd[$i]=101;}
			}
		$uniqueid[$i] =			$Suniqueid[$i];
		$lead_id[$i] =			$Slead_id[$i];
		$closer[$i] =			$Suser[$i];
		$call_time_S[$i] = ($STARTtime - $Scall_time[$i]);

		$call_time_M[$i] = ($call_time_S[$i] / 60);
		$call_time_M[$i] = round($call_time_M[$i], 2);
		$call_time_M_int[$i] = intval("$call_time_M[$i]");
		$call_time_SEC[$i] = ($call_time_M[$i] - $call_time_M_int[$i]);
		$call_time_SEC[$i] = ($call_time_SEC[$i] * 60);
		$call_time_SEC[$i] = round($call_time_SEC[$i], 0);
		if ($call_time_SEC[$i] < 10) {$call_time_SEC[$i] = "0$call_time_SEC[$i]";}
		$call_time_MS[$i] = "$call_time_M_int[$i]:$call_time_SEC[$i]";
		$call_time_MS[$i] =		sprintf("%7s", $call_time_MS[$i]);

		if ($closer_display<1)
			{
			$G = '';		$EG = '';
			if ($call_time_M_int[$i] >= 5) {$G='<SPAN class="blue"><B>'; $EG='</B></SPAN>';}
			if ($call_time_M_int[$i] >= 10) {$G='<SPAN class="purple"><B>'; $EG='</B></SPAN>';}
			if (preg_match("/PAUSED/i",$Sstatus[$i])) 
				{
				if ($call_time_M_int >= 1) 
					{$i++; continue;} 
				else
					{$G='<SPAN class="yellow"><B>'; $EG='</B></SPAN>';}
				}
			$agentcount++;
			echo "| $G$extension[$i]$EG | $G$phone[$i]$EG | $G$user[$i]$EG | $G$sessionid[$i]$EG | $G$channel[$i]$EG | $G$status[$i]$EG | $G$start_time[$i]$EG | $G$call_time_MS[$i]$EG |\n";
			}
		$i++;
		}

		if ($closer_display>0)
		{

			$ext_count = $i;
			$i=0;
		while ($i < $ext_count)
			{

			$stmt="select campaign_id from vicidial_auto_calls where lead_id='$lead_id[$i]' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$camp_to_print = mysqli_num_rows($rslt);
			if ($camp_to_print > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$campaign = sprintf("%-12s", $row[0]);
				$camp_color = $row[0];
				}
			else
				{$campaign = _QXZ("DEAD",12);   	$camp_color = 'DEAD';}
			if (preg_match("/READY|PAUSED|CLOSER/i",$status[$i]))
				{$campaign = '            ';   	$camp_color = '';}

			$stmt="select user from vicidial_xfer_log where lead_id='$lead_id[$i]' and closer='$closer[$i]' order by call_date desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$xfer_to_print = mysqli_num_rows($rslt);
			if ($xfer_to_print > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$fronter = sprintf("%-6s", $row[0]);
				}
			else
				{$fronter = '      ';}

			$G = '';		$EG = '';
			$G="<SPAN class=\"$camp_color\"><B>"; $EG='</B></SPAN>';
		#	if ($call_time_M_int[$i] >= 5) {$G='<SPAN class="blue"><B>'; $EG='</B></SPAN>';}
		#	if ($call_time_M_int[$i] >= 10) {$G='<SPAN class="purple"><B>'; $EG='</B></SPAN>';}

			echo "| $G$extension[$i]$EG | $G$phone[$i]$EG | $G$user[$i]$EG | $G$sessionid[$i]$EG | $G$channel[$i]$EG | $G$status[$i]$EG | $G$start_time[$i]$EG | $G$call_time_MS[$i]$EG | $G$campaign$EG | $G$fronter$EG |\n";

			$i++;
			}
		echo "+------------+------------+--------+-----------+---------------------+--------+----------+---------+--------------+--------+\n";
		echo "  $i "._QXZ("agents logged in on server")." $server_ip\n\n";
	#	echo "  <SPAN class=\"blue\"><B>          </SPAN> - 5 minutes or more on call</B>\n";
	#	echo "  <SPAN class=\"purple\"><B>          </SPAN> - Over 10 minutes on call</B>\n";
		}
	else
		{
		echo "+------------+------------+--------+-----------+---------------------+--------+----------+---------+\n";
		echo "  $agentcount "._QXZ("agents logged in on server")." $server_ip\n\n";

		echo "  <SPAN class=\"yellow\"><B>          </SPAN> - "._QXZ("Paused agents")."</B>\n";
		echo "  <SPAN class=\"blue\"><B>          </SPAN> - "._QXZ("5 minutes or more on call")."</B>\n";
		echo "  <SPAN class=\"purple\"><B>          </SPAN> - "._QXZ("Over 10 minutes on call")."</B>\n";
		}

	}
	else
	{
	echo "**************************************************************************************\n";
	echo "**************************************************************************************\n";
	echo "********************************* "._QXZ("NO AGENTS ON CALLS",18)." *********************************\n";
	echo "**************************************************************************************\n";
	echo "**************************************************************************************\n";
	}


###################################################################################
###### OUTBOUND CALLS
###################################################################################
#echo "\n\n";
echo "----------------------------------------------------------------------------------------";
echo "\n\n";
echo _QXZ("Server-Specific Real-Time Report",39)." "._QXZ("TRUNK SHORT",11).": $balanceSHORT          $NOW_TIME\n\n";
echo "+---------------------+--------+--------------+--------------------+----------+---------+\n";
echo "| "._QXZ("CHANNEL",19)." | "._QXZ("STATUS",6)." | "._QXZ("CAMPAIGN",12)." | "._QXZ("PHONE NUMBER",18)." | "._QXZ("CALLTIME",8)." | "._QXZ("MINUTES",7)." |\n";
echo "+---------------------+--------+--------------+--------------------+----------+---------+\n";

$stmt="select channel,status,campaign_id,phone_code,phone_number,call_time,UNIX_TIMESTAMP(call_time) from vicidial_auto_calls where status NOT IN('XFER') and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' order by auto_call_id desc;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$parked_to_print = mysqli_num_rows($rslt);
	if ($parked_to_print > 0)
	{
	$i=0;
	while ($i < $parked_to_print)
		{
		$row=mysqli_fetch_row($rslt);

		$channel =			sprintf("%-19s", $row[0]);
			$cc=0;
		while ( (strlen($channel) > 19) and ($cc < 100) )
			{
			$channel = preg_replace('/.$/i', '',$channel);   
			$cc++;
			if (strlen($channel) <= 19) {$cc=101;}
			}
		$start_time =		sprintf("%-8s", $row[5]);
			$cd=0;
		while ( (strlen($start_time) > 8) and ($cd < 100) )
			{
			$start_time = preg_replace('/^./i', '',$start_time);   
			$cd++;
			if (strlen($start_time) <= 8) {$cd=101;}
			}
		$status =			sprintf("%-6s", $row[1]);
		$campaign =			sprintf("%-12s", $row[2]);
			$all_phone = "$row[3]$row[4]";
		$number_dialed =	sprintf("%-18s", $all_phone);
		$call_time_S = ($STARTtime - $row[6]);

		$call_time_M = ($call_time_S / 60);
		$call_time_M = round($call_time_M, 2);
		$call_time_M_int = intval("$call_time_M");
		$call_time_SEC = ($call_time_M - $call_time_M_int);
		$call_time_SEC = ($call_time_SEC * 60);
		$call_time_SEC = round($call_time_SEC, 0);
		if ($call_time_SEC < 10) {$call_time_SEC = "0$call_time_SEC";}
		$call_time_MS = "$call_time_M_int:$call_time_SEC";
		$call_time_MS =		sprintf("%7s", $call_time_MS);
		$G = '';		$EG = '';
		if (preg_match("/LIVE/i",$status)) {$G='<SPAN class="green"><B>'; $EG='</B></SPAN>';}
	#	if ($call_time_M_int >= 6) {$G='<SPAN class="red"><B>'; $EG='</B></SPAN>';}

		echo "| $G$channel$EG | $G$status$EG | $G$campaign$EG | $G$number_dialed$EG | $G$start_time$EG | $G$call_time_MS$EG |\n";

		$i++;
		}

		echo "+---------------------+--------+--------------+--------------------+----------+---------+\n";
		echo "  $i "._QXZ("calls being placed on server")." $server_ip\n\n";

		echo "  <SPAN class=\"green\"><B>          </SPAN> - "._QXZ("LIVE CALL WAITING")."</B>\n";
	#	echo "  <SPAN class=\"red\"><B>          </SPAN> - Over 5 minutes on hold</B>\n";

		}
	else
	{
	echo "***************************************************************************************\n";
	echo "***************************************************************************************\n";
	echo "******************************* "._QXZ("NO LIVE CALLS WAITING",21)." *********************************\n";
	echo "***************************************************************************************\n";
	echo "***************************************************************************************\n";
	}


?>
</PRE>
</TD></TR></TABLE>

</BODY></HTML>