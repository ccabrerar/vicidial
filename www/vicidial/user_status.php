<?php
# user_status.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60619-1738 - Added variable filtering to eliminate SQL injection attack threat
# 80603-1452 - Added manager timeclock force login/logout of user
# 81118-1034 - Disabled change campaign because it does not work
# 90208-0511 - Added link to user multi-day status report
# 90310-0741 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 91012-0536 - Added selected territories display
# 91130-2039 - Added user closer log manager flag display
# 91212-0656 - Added more complete logging of Emergency Logout process
# 100309-0544 - Added queuemetrics_loginout option
# 101108-0032 - Added ADDMEMBER queue_log code
# 110124-1134 - Small query fix for large queue_log tables
# 110224-2350 - Small QM logout fix
# 110420-0344 - Added DEAD/PARK agent statuses
# 120223-2135 - Removed logging of good login passwords if webroot writable is enabled
# 130414-0252 - Added report logging
# 130610-0937 - Finalized changing of all ereg instances to preg
# 130616-0052 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0835 - Changed to mysqli PHP functions
# 131016-2101 - Added checking for level 8 add-copy restriction
# 131208-2156 - Added user log TIMEOUTLOGOUT event status
# 140108-0707 - Added webserver and hostname to report logging
# 140305-0905 - Bug fix for issue #744, emergency logout
# 140425-1314 - Added pause_type field to logout
# 140429-0750 - Fixed issue with queue_log if login/out logging is disabled
# 141007-2214 - Finalized adding QXZ translation to all admin files
# 141114-1702 - Fixed issue #800
# 141229-1829 - Added code for on-the-fly language translations display
# 150701-1250 - Modified mysqli_error() to mysqli_connect_error() where appropriate
# 151211-1458 - Added chat visibility
# 160325-1433 - Changes for sidebar update
# 161102-1041 - Fixed QM partition problem
# 170217-1213 - Fixed non-latin auth issue #995
# 170228-1623 - Changed emergency logout to hangup all agent session calls, and more logging
# 170409-1555 - Added IP List validation code
# 170912-1704 - Removed non-functional change-campaign feature
# 220221-0953 - Added allow_web_debug system setting
#

$startMS = microtime();

header ("Content-type: text/html; charset=utf-8");

$report_name='User Status';

$add_copy_disabled=0;

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["begin_date"]))				{$begin_date=$_GET["begin_date"];}
	elseif (isset($_POST["begin_date"]))	{$begin_date=$_POST["begin_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["stage"]))				{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))		{$stage=$_POST["stage"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$StarTtimE = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$ip = getenv("REMOTE_ADDR");
$check_time = ($StarTtimE - 86400);
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
if (!isset($begin_date)) {$begin_date = $TODAY;}
if (!isset($end_date)) {$end_date = $TODAY;}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,level_8_disable_add,enable_languages,language_method,allow_chats,admin_screen_colors,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =						$row[0];
	$webroot_writable =					$row[1];
	$SSoutbound_autodial_active =		$row[2];
	$user_territories_active =			$row[3];
	$SSlevel_8_disable_add =			$row[4];
	$SSenable_languages =				$row[5];
	$SSlanguage_method =				$row[6];
	$SSallow_chats =					$row[7];
	$SSadmin_screen_colors =			$row[8];
	$SSallow_web_debug =				$row[9];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);
$begin_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$begin_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$end_date);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9a-zA-Z]/','',$group);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9\p{L}]/u','',$group);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	}


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
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
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
		}
	}
$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;


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
		$VDdisplayMESSAGE = ("Too many login attempts, try again in 15 minutes");
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


$stmt="SELECT user_level from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_level				=$row[0];

if (($LOGuser_level < 9) and ($SSlevel_8_disable_add > 0))
	{$add_copy_disabled++;}


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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$user, $stage, $group|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

$stmt="SELECT full_name,change_agent_campaign,modify_timeclock_log from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$change_agent_campaign =	$row[1];
$modify_timeclock_log =		$row[2];

$stmt="SELECT full_name,user_group from vicidial_users where user='" . mysqli_real_escape_string($link, $user) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$full_name = $row[0];
$user_group = $row[1];

$stmt="SELECT live_agent_id,user,server_ip,conf_exten,extension,status,lead_id,campaign_id,uniqueid,callerid,channel,random_id,last_call_time,last_update_time,last_call_finish,closer_campaigns,call_server_ip,user_level,comments,campaign_weight,calls_today,external_hangup,external_status,external_pause,external_dial,agent_log_id,last_state_change,agent_territories,outbound_autodial,manager_ingroup_set,external_igb_set_user from vicidial_live_agents where user='" . mysqli_real_escape_string($link, $user) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$agents_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $agents_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$Aserver_ip =				$row[2];
	$Asession_id =				$row[3];
	$Aextension =				$row[4];
	$Astatus =					$row[5];
	$Acampaign =				$row[7];
	$Acallerid =				$row[9];
	$Alast_call =				$row[14];
	$Acl_campaigns =			$row[15];
	$agent_territories = 		$row[27];
	$outbound_autodial = 		$row[28];
	$manager_ingroup_set =		$row[29];
	$external_igb_set_user =	$row[30];
	$i++;
	}

if ($SSallow_chats > 0)
	{
	$stmt="SELECT chat_id,chat_start_time,status,chat_creator,group_id,lead_id from vicidial_live_chats where status='LIVE' and chat_creator='$user'";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$active_chats=mysqli_num_rows($rslt);
	if($active_chats>0) 
		{
		$Achats="";
		while ($row=mysqli_fetch_row($rslt)) 
			{
			$chat_id=$row[0];
			$Achats.="$chat_id, ";
			}
		$Achats=preg_replace('/, $/', "", $Achats);
		}
	else 
		{
		$Achats=_QXZ("NONE");
		}
	}

$stmt="SELECT event_date,status,ip_address from vicidial_timeclock_status where user='" . mysqli_real_escape_string($link, $user) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$tc_logs_to_print = mysqli_num_rows($rslt);
if ($tc_logs_to_print > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$Tevent_date =		$row[0];
	$Tstatus =			$row[1];
	$Tip_address =		$row[2];
	$i++;
	}

if ($Astatus == 'INCALL')
	{
	$stmtP="select count(*) from parked_channels where channel_group='$Acallerid';";
	$rsltP=mysql_to_mysqli($stmtP,$link);
	$rowP=mysqli_fetch_row($rsltP);
	$parked_channel = $rowP[0];

	if ($parked_channel > 0)
		{
		$Astatus =	'PARK';
		}
	else
		{
		$stmtP="select count(*) from vicidial_auto_calls where callerid='$Acallerid';";
		$rsltP=mysql_to_mysqli($stmtP,$link);
		$rowP=mysqli_fetch_row($rsltP);
		$live_channel = $rowP[0];

		if ($live_channel < 1)
			{
			$Astatus =	'DEAD';
			}
		}
	}


$stmt="select campaign_id from vicidial_campaigns;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	$i++;
	}



?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: User Status"); ?>
<?php


##### BEGIN Set variables to make header show properly #####
$ADD =					'3';
$hh =					'users';
$sh =					'status';
$LOGast_admin_access =	'1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$users_color =		'#FFFF99';
$users_font =		'BLACK';
$users_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");



?>
<TABLE WIDTH=<?php echo $page_width ?> BGCOLOR=#<?php echo $SSframe_background; ?> cellpadding=2 cellspacing=0><TR BGCOLOR=#<?php echo $SSframe_background; ?>><TD ALIGN=LEFT><FONT FACE="ARIAL,HELVETICA" SIZE=2><B> &nbsp; <?php echo _QXZ("User Status for"); ?> <?php echo $user ?></TD><TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA" SIZE=2><B> &nbsp; </TD></TR>




<?php 

echo "<TR BGCOLOR=\"#$SSstd_row2_background\"><TD ALIGN=LEFT COLSPAN=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3><B> &nbsp; \n";

##### EMERGENCY CAMPAIGN CHANGE FOR AN AGENT #####
if ($stage == "live_campaign_change")
	{
	$stmt="UPDATE vicidial_live_agents set campaign_id='" . mysqli_real_escape_string($link, $group) . "' where user='" . mysqli_real_escape_string($link, $user) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);

	echo _QXZ("Agent")." $user - $full_name "._QXZ("changed to")." $group "._QXZ("campaign")."<BR>\n";
	
	exit;
	}

##### EMERGENCY LOGOUT OF AN AGENT #####
if ($stage == "log_agent_out")
	{
	$now_date_epoch = date('U');
	$inactive_epoch = ($now_date_epoch - 60);
	$stmt = "SELECT user,campaign_id,UNIX_TIMESTAMP(last_update_time),status,conf_exten,server_ip from vicidial_live_agents where user='" . mysqli_real_escape_string($link, $user) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "<BR>$stmt\n";}
	$vla_ct = mysqli_num_rows($rslt);
	if ($vla_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$VLA_user =					$row[0];
		$VLA_campaign_id =			$row[1];
		$VLA_update_time =			$row[2];
		$VLA_status =				$row[3];
		$VLA_conf_exten =			$row[4];
		$VLA_server_ip =			$row[5];

		if ($VLA_update_time > $inactive_epoch)
			{
			$lead_active=0;
			$stmt = "SELECT agent_log_id,user,server_ip,event_time,lead_id,campaign_id,pause_epoch,pause_sec,wait_epoch,wait_sec,talk_epoch,talk_sec,dispo_epoch,dispo_sec,status,user_group,comments,sub_status,dead_epoch,dead_sec from vicidial_agent_log where user='$VLA_user' order by agent_log_id desc LIMIT 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "<BR>$stmt\n";}
			$val_ct = mysqli_num_rows($rslt);
			if ($val_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$VAL_agent_log_id =		$row[0];
				$VAL_user =				$row[1];
				$VAL_server_ip =		$row[2];
				$VAL_event_time =		$row[3];
				$VAL_lead_id =			$row[4];
				$VAL_campaign_id =		$row[5];
				$VAL_pause_epoch =		$row[6];
				$VAL_pause_sec =		$row[7];
				$VAL_wait_epoch =		$row[8];
				$VAL_wait_sec =			$row[9];
				$VAL_talk_epoch =		$row[10];
				$VAL_talk_sec =			$row[11];
				$VAL_dispo_epoch =		$row[12];
				$VAL_dispo_sec =		$row[13];
				$VAL_status =			$row[14];
				$VAL_user_group =		$row[15];
				$VAL_comments =			$row[16];
				$VAL_sub_status =		$row[17];
				$VAL_dead_epoch =		$row[18];
				$VAL_dead_sec =			$row[19];

				if ($DB) {echo "\n<BR>"._QXZ("VAL VALUES").": $VAL_agent_log_id|$VAL_status|$VAL_lead_id\n";}

				if ( ($VAL_wait_epoch < 1) or ( (preg_match('/PAUSE/', $VLA_status)) and ($VAL_dispo_epoch < 1) ) )
					{
					$VAL_pause_sec = ( ($now_date_epoch - $VAL_pause_epoch) + $VAL_pause_sec);
					$stmt = "UPDATE vicidial_agent_log SET wait_epoch='$now_date_epoch', pause_sec='$VAL_pause_sec', pause_type='ADMIN' where agent_log_id='$VAL_agent_log_id';";
					}
				else
					{
					if ($VAL_talk_epoch < 1)
						{
						$VAL_wait_sec = ( ($now_date_epoch - $VAL_wait_epoch) + $VAL_wait_sec);
						$stmt = "UPDATE vicidial_agent_log SET talk_epoch='$now_date_epoch', wait_sec='$VAL_wait_sec' where agent_log_id='$VAL_agent_log_id';";
						}
					else
						{
						$lead_active++;
						$status_update_SQL='';
						if ( ( (strlen($VAL_status) < 1) or ($VAL_status == 'NULL') ) and ($VAL_lead_id > 0) )
							{
							$status_update_SQL = ", status='PU'";
							$stmt="UPDATE vicidial_list SET status='PU' where lead_id='$VAL_lead_id';";
							if ($DB) {echo "<BR>$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							}
						if ($VAL_dispo_epoch < 1)
							{
							$VAL_talk_sec = ($now_date_epoch - $VAL_talk_epoch);
							$stmt = "UPDATE vicidial_agent_log SET dispo_epoch='$now_date_epoch', talk_sec='$VAL_talk_sec'$status_update_SQL where agent_log_id='$VAL_agent_log_id';";
							}
						else
							{
							if ($VAL_dispo_sec < 1)
								{
								$VAL_dispo_sec = ($now_date_epoch - $VAL_dispo_epoch);
								$stmt = "UPDATE vicidial_agent_log SET dispo_sec='$VAL_dispo_sec' where agent_log_id='$VAL_agent_log_id';";
								}
							}
						}
					}

				if ($DB) {echo "<BR>$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			}

		$stmt="DELETE from vicidial_live_agents where user='" . mysqli_real_escape_string($link, $user) . "';";
		if ($DB) {echo "<BR>$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		if (strlen($VAL_user_group) < 1)
			{
			$stmt = "SELECT user_group FROM vicidial_users where user='$VLA_user';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "<BR>$stmt\n";}
			$val_ct = mysqli_num_rows($rslt);
			if ($val_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$VAL_user_group =		$row[0];
				}
			}

		$local_DEF = 'Local/5555';
		$local_AMP = '@';
		$ext_context = 'default';
		$kick_local_channel = "$local_DEF$VLA_conf_exten$local_AMP$ext_context";
		$queryCID = "ULGH3457$StarTtimE";

		$stmtC="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$VLA_server_ip','','Originate','$queryCID','Channel: $kick_local_channel','Context: $ext_context','Exten: 8300','Priority: 1','Callerid: $queryCID','','','','$channel','$exten');";
		if ($DB) {echo "<BR>$stmtC\n";}
		$rslt=mysql_to_mysqli($stmtC, $link);

		$stmtB = "INSERT INTO vicidial_user_log (user,event,campaign_id,event_date,event_epoch,user_group,extension) values('$VLA_user','LOGOUT','$VLA_campaign_id','$NOW_TIME','$now_date_epoch','$VAL_user_group','MGR LOGOUT: $PHP_AUTH_USER');";
		if ($DB) {echo "<BR>$stmtB\n";}
		$rslt=mysql_to_mysqli($stmtB, $link);

		### Add a record to the vicidial_admin_log
		$SQL_log = "$stmt|$stmtB|$stmtC|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='LOGOUT', record_id='$user', event_code='EMERGENCY LOGOUT FROM STATUS PAGE', event_sql=\"$SQL_log\", event_notes='agent_log_id: $VAL_agent_log_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- NEW vicidial_admin_log record inserted for $PHP_AUTH_USER:   |$affected_rows| -->\n";


		#############################################
		##### START QUEUEMETRICS LOGGING LOOKUP #####
		$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_loginout,queuemetrics_addmember_enabled,queuemetrics_pe_phone_append,queuemetrics_pause_type FROM system_settings;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "<BR>$stmt\n";}
		$qm_conf_ct = mysqli_num_rows($rslt);
		if ($qm_conf_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$enable_queuemetrics_logging =		$row[0];
			$queuemetrics_server_ip	=			$row[1];
			$queuemetrics_dbname =				$row[2];
			$queuemetrics_login	=				$row[3];
			$queuemetrics_pass =				$row[4];
			$queuemetrics_log_id =				$row[5];
			$queuemetrics_loginout =			$row[6];
			$queuemetrics_addmember_enabled =	$row[7];
			$queuemetrics_pe_phone_append =		$row[8];
			$queuemetrics_pause_type =			$row[9];
			}
		##### END QUEUEMETRICS LOGGING LOOKUP #####
		###########################################
		if ($enable_queuemetrics_logging > 0)
			{
			$QM_LOGOFF = 'AGENTLOGOFF';
			if ($queuemetrics_loginout=='CALLBACK')
				{$QM_LOGOFF = 'AGENTCALLBACKLOGOFF';}

			#$linkB=mysql_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
			#mysql_select_db("$queuemetrics_dbname", $linkB);
			$linkB=mysqli_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass", "$queuemetrics_dbname");
			if (!$linkB) {die(_QXZ("Could not connect: ")."$queuemetrics_server_ip|$queuemetrics_login" . mysqli_connect_error());}

			$agents='@agents';
			$agent_logged_in='';
			$time_logged_in='0';

			$stmtB = "SELECT agent,time_id,data1 FROM queue_log where agent='Agent/" . mysqli_real_escape_string($link, $user) . "' and verb IN('AGENTLOGIN','AGENTCALLBACKLOGIN') and time_id > $check_time order by time_id desc limit 1;";

			if ($queuemetrics_loginout == 'NONE')
				{
				$pause_typeSQL='';
				if ($queuemetrics_pause_type > 0)
					{$pause_typeSQL=",data5='ADMIN'";}
				$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$now_date_epoch',call_id='NONE',queue='NONE',agent='Agent/" . mysqli_real_escape_string($link, $user) . "',verb='PAUSEREASON',serverid='$queuemetrics_log_id',data1='LOGOFF'$pause_typeSQL;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $linkB);
				$affected_rows = mysqli_affected_rows($linkB);

				$stmtB = "SELECT agent,time_id,data1 FROM queue_log where agent='Agent/" . mysqli_real_escape_string($link, $user) . "' and verb IN('ADDMEMBER','ADDMEMBER2') and time_id > $check_time order by time_id desc limit 1;";
				}

			$rsltB=mysql_to_mysqli($stmtB, $linkB);
			if ($DB) {echo "<BR>$stmtB\n";}
			$qml_ct = mysqli_num_rows($rsltB);
			if ($qml_ct > 0)
				{
				$row=mysqli_fetch_row($rsltB);
				$agent_logged_in =		$row[0];
				$time_logged_in =		$row[1];
				$RAWtime_logged_in =	$row[1];
				$phone_logged_in =		$row[2];
				}

			$time_logged_in = ($now_date_epoch - $time_logged_in);
			if ($time_logged_in > 1000000) {$time_logged_in=1;}

			if ($queuemetrics_addmember_enabled > 0)
				{
				$queuemetrics_phone_environment='';
				$stmt = "SELECT queuemetrics_phone_environment FROM vicidial_campaigns where campaign_id='$VLA_campaign_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "<BR>$stmt\n";}
				$cqpe_ct = mysqli_num_rows($rslt);
				if ($cqpe_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$queuemetrics_phone_environment =	$row[0];
					}

				$stmt = "SELECT distinct queue FROM queue_log where time_id >= $RAWtime_logged_in and agent='$agent_logged_in' and verb IN('ADDMEMBER','ADDMEMBER2') and queue != '$VLA_campaign_id' order by time_id desc;";
				$rslt=mysql_to_mysqli($stmt, $linkB);
				if ($DB) {echo "$stmt\n";}
				$amq_conf_ct = mysqli_num_rows($rslt);
				$i=0;
				while ($i < $amq_conf_ct)
					{
					$row=mysqli_fetch_row($rslt);
					$AMqueue[$i] =	$row[0];
					$i++;
					}

				### add the logged-in campaign as well
				$AMqueue[$i] = $VLA_campaign_id;
				$i++;
				$amq_conf_ct++;

				$i=0;
				while ($i < $amq_conf_ct)
					{
					$pe_append='';
					if ( ($queuemetrics_pe_phone_append > 0) and (strlen($queuemetrics_phone_environment)>0) )
						{
						$qm_extension = explode('/',$phone_logged_in);
						$pe_append = "-$qm_extension[1]";
						}
					$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$now_date_epoch',call_id='NONE',queue='$AMqueue[$i]',agent='$agent_logged_in',verb='REMOVEMEMBER',data1='$phone_logged_in',serverid='$queuemetrics_log_id',data4='$queuemetrics_phone_environment$pe_append';";
					$rslt=mysql_to_mysqli($stmt, $linkB);
					$affected_rows = mysqli_affected_rows($linkB);
					$i++;
					}
				}

			if ($queuemetrics_loginout != 'NONE')
				{
				$stmtB = "INSERT INTO queue_log SET `partition`='P01',time_id='$now_date_epoch',call_id='NONE',queue='NONE',agent='$agent_logged_in',verb='$QM_LOGOFF',serverid='$queuemetrics_log_id',data1='$phone_logged_in',data2='$time_logged_in';";
				if ($DB) {echo "<BR>$stmtB\n";}
				$rsltB=mysql_to_mysqli($stmtB, $linkB);
				}
			}

		echo _QXZ("Agent")." $user - $full_name "._QXZ("has been emergency logged out, all calls in their session have been hung up, make sure they close their web browser")."<BR>\n";
		}
	else
		{
		echo _QXZ("Agent")." $user "._QXZ("is not logged in")."<BR>\n";
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

	exit;
	}





##### BEGIN TIMECLOCK LOGOUT OF A USER #####
if ( ( ($stage == "tc_log_user_OUT") or ($stage == "tc_log_user_IN") ) and ($modify_timeclock_log > 0) )
	{
	### get vicidial_timeclock_status record count for this user
	$stmt="SELECT count(*) from vicidial_timeclock_status where user='$user';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$vts_count =	$row[0];

	$LOG_run=0;
	$last_action_sec=99;

	if ($vts_count > 0)
		{
		### vicidial_timeclock_status record found, grab status and date of last activity
		$stmt="SELECT status,event_epoch from vicidial_timeclock_status where user='$user';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$status =		$row[0];
		$event_epoch =	$row[1];
		$last_action_date = date("Y-m-d H:i:s", $event_epoch);
		$last_action_sec = ($StarTtimE - $event_epoch);

		if ($last_action_sec > 0)
			{
			$totTIME_H = ($last_action_sec / 3600);
			$totTIME_H_int = round($totTIME_H, 2);
			$totTIME_H_int = intval("$totTIME_H");
			$totTIME_M = ($totTIME_H - $totTIME_H_int);
			$totTIME_M = ($totTIME_M * 60);
			$totTIME_M_int = round($totTIME_M, 2);
			$totTIME_M_int = intval("$totTIME_M");
			$totTIME_S = ($totTIME_M - $totTIME_M_int);
			$totTIME_S = ($totTIME_S * 60);
			$totTIME_S = round($totTIME_S, 0);
			if (strlen($totTIME_H_int) < 1) {$totTIME_H_int = "0";}
			if ($totTIME_M_int < 10) {$totTIME_M_int = "0$totTIME_M_int";}
			if ($totTIME_S < 10) {$totTIME_S = "0$totTIME_S";}
			$totTIME_HMS = "$totTIME_H_int:$totTIME_M_int:$totTIME_S";
			}
		else 
			{
			$totTIME_HMS='0:00:00';
			}
		}

	else
		{
		### No vicidial_timeclock_status record found, insert one
		$stmt="INSERT INTO vicidial_timeclock_status set status='START', user='$user', user_group='$user_group', event_epoch='$StarTtimE', ip_address='$ip';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$status='START';
		$totTIME_HMS='0:00:00';
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- NEW vicidial_timeclock_status record inserted for $user:   |$affected_rows| -->\n";
		}


	##### Run timeclock login queries #####
	if ( ( ($status=='AUTOLOGOUT') or ($status=='START') or ($status=='LOGOUT') or ($status=='TIMEOUTLOGOUT') ) and ($stage == "tc_log_user_IN") )
		{
		### Add a record to the timeclock log
		$stmtA="INSERT INTO vicidial_timeclock_log set event='LOGIN', user='$user', user_group='$user_group', event_epoch='$StarTtimE', ip_address='$ip', event_date='$NOW_TIME', manager_user='$PHP_AUTH_USER', manager_ip='$ip', notes='Manager LOGIN of user from user status page';";
		if ($DB) {echo "$stmtA\n";}
		$rslt=mysql_to_mysqli($stmtA, $link);
		$affected_rows = mysqli_affected_rows($link);
		$timeclock_id = mysqli_insert_id($link);
		print "<!-- NEW vicidial_timeclock_log record inserted for $user:   |$affected_rows|$timeclock_id| -->\n";

		### Update the user's timeclock status record
		$stmtB="UPDATE vicidial_timeclock_status set status='LOGIN', user_group='$user_group', event_epoch='$StarTtimE', ip_address='$ip' where user='$user';";
		if ($DB) {echo "$stmtB\n";}
		$rslt=mysql_to_mysqli($stmtB, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- vicidial_timeclock_status record updated for $user:   |$affected_rows| -->\n";

		### Add a record to the timeclock audit log
		$stmtC="INSERT INTO vicidial_timeclock_audit_log set timeclock_id='$timeclock_id', event='LOGIN', user='$user', user_group='$user_group', event_epoch='$StarTtimE', ip_address='$ip', event_date='$NOW_TIME';";
		if ($DB) {echo "$stmtC\n";}
		$rslt=mysql_to_mysqli($stmtC, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- NEW vicidial_timeclock_audit_log record inserted for $user:   |$affected_rows| -->\n";

		### Add a record to the vicidial_admin_log
		$SQL_log = "$stmtA|$stmtB|$stmtC|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='TIMECLOCK', event_type='LOGIN', record_id='$user', event_code='USER FORCED LOGIN FROM STATUS PAGE', event_sql=\"$SQL_log\", event_notes='Timeclock ID: $timeclock_id|';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- NEW vicidial_admin_log record inserted for $PHP_AUTH_USER:   |$affected_rows| -->\n";

		$LOG_run++;
		$VDdisplayMESSAGE = _QXZ("You have now logged-in the user").": $user - $full_name";
		}

	##### Run timeclock logout queries #####
	if ( ( ($status=='LOGIN') or ($status=='START') ) and ($stage == "tc_log_user_OUT") )
		{
		### Add a record to the timeclock log
		$stmtA="INSERT INTO vicidial_timeclock_log set event='LOGOUT', user='$user', user_group='$user_group', event_epoch='$StarTtimE', ip_address='$ip', login_sec='$last_action_sec', event_date='$NOW_TIME', manager_user='$PHP_AUTH_USER', manager_ip='$ip', notes='Manager LOGOUT of user from user status page';";
		if ($DB) {echo "$stmtA\n";}
		$rslt=mysql_to_mysqli($stmtA, $link);
		$affected_rows = mysqli_affected_rows($link);
		$timeclock_id = mysqli_insert_id($link);
		print "<!-- NEW vicidial_timeclock_log record inserted for $user:   |$affected_rows|$timeclock_id| -->\n";

		### Update last login record in the timeclock log
		$stmtB="UPDATE vicidial_timeclock_log set login_sec='$last_action_sec',tcid_link='$timeclock_id' where event='LOGIN' and user='$user' order by timeclock_id desc limit 1;";
		if ($DB) {echo "$stmtB\n";}
		$rslt=mysql_to_mysqli($stmtB, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- vicidial_timeclock_log record updated for $user:   |$affected_rows| -->\n";

		### Update the user's timeclock status record
		$stmtC="UPDATE vicidial_timeclock_status set status='LOGOUT', user_group='$user_group', event_epoch='$StarTtimE', ip_address='$ip' where user='$user';";
		if ($DB) {echo "$stmtC\n";}
		$rslt=mysql_to_mysqli($stmtC, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- vicidial_timeclock_status record updated for $user:   |$affected_rows| -->\n";

		### Add a record to the timeclock audit log
		$stmtD="INSERT INTO vicidial_timeclock_audit_log set timeclock_id='$timeclock_id', event='LOGOUT', user='$user', user_group='$user_group', event_epoch='$StarTtimE', ip_address='$ip', login_sec='$last_action_sec', event_date='$NOW_TIME';";
		if ($DB) {echo "$stmtD\n";}
		$rslt=mysql_to_mysqli($stmtD, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- NEW vicidial_timeclock_audit_log record inserted for $user:   |$affected_rows| -->\n";

		### Update last login record in the timeclock audit log
		$stmtE="UPDATE vicidial_timeclock_audit_log set login_sec='$last_action_sec',tcid_link='$timeclock_id' where event='LOGIN' and user='$user' order by timeclock_id desc limit 1;";
		if ($DB) {echo "$stmtE\n";}
		$rslt=mysql_to_mysqli($stmtE, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- vicidial_timeclock_audit_log record updated for $user:   |$affected_rows| -->\n";

		### Add a record to the vicidial_admin_log
		$SQL_log = "$stmtA|$stmtB|$stmtC|$stmtD|$stmtE|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='TIMECLOCK', event_type='LOGOUT', record_id='$user', event_code='USER FORCED LOGOUT FROM STATUS PAGE', event_sql=\"$SQL_log\", event_notes='User login time: $last_action_sec|Timeclock ID: $timeclock_id|';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		print "<!-- NEW vicidial_admin_log record inserted for $PHP_AUTH_USER:   |$affected_rows| -->\n";

		$LOG_run++;
		$VDdisplayMESSAGE = _QXZ("You have now logged-out the user").": $user - $full_name<BR>"._QXZ("Amount of time user was logged-in").": $totTIME_HMS";
		}
	
	if ($LOG_run < 1)
		{$VDdisplayMESSAGE = _QXZ("ERROR: timeclock log problem, could not process").": $status|$stage";}

	echo "$VDdisplayMESSAGE\n";
	
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

	exit;
	}

##### END TIMECLOCK LOGOUT OF A USER #####

if ($agents_to_print > 0)
	{
	echo "<BR>\n";
	echo "$user - $full_name \n";
	echo " &nbsp; &nbsp; &nbsp; "._QXZ("GROUP").": $user_group <BR>\n";

	echo "<TABLE CELLPADDING=0 CELLSPACING=0>";
	echo "<TR><TD ALIGN=RIGHT>"._QXZ("Agent Logged in at server").":</TD><TD ALIGN=LEFT> &nbsp; $Aserver_ip</TD></TR>\n";
	echo "<TR><TD ALIGN=RIGHT>"._QXZ("in session").":</TD><TD ALIGN=LEFT> &nbsp; $Asession_id</TD></TR>\n";
	echo "<TR><TD ALIGN=RIGHT>"._QXZ("from phone").":</TD><TD ALIGN=LEFT> &nbsp; $Aextension</TD></TR>\n";
	echo "<TR><TD ALIGN=RIGHT>"._QXZ("Agent is in campaign").":</TD><TD ALIGN=LEFT> &nbsp; $Acampaign</TD></TR>\n";
	echo "<TR><TD ALIGN=RIGHT>"._QXZ("status").":</TD><TD ALIGN=LEFT> &nbsp; $Astatus</TD></TR>\n";
	echo "<TR><TD ALIGN=RIGHT>"._QXZ("hungup last call at").":</TD><TD ALIGN=LEFT> &nbsp; $Alast_call</TD></TR>\n";
	echo "<TR><TD ALIGN=RIGHT>"._QXZ("Closer groups").":</TD><TD ALIGN=LEFT> &nbsp; $Acl_campaigns</TD></TR>\n";
	if ($manager_ingroup_set != 'N')
		{echo "<TR><TD ALIGN=RIGHT>"._QXZ("Manager InGroup Select").":</TD><TD ALIGN=LEFT> &nbsp; "._QXZ("YES, by")." $external_igb_set_user</TD></TR>\n";}
	if ($outbound_autodial == 'Y')
		{echo "<TR><TD ALIGN=RIGHT>"._QXZ("Outbound Auto-Dial").":</TD><TD ALIGN=LEFT> &nbsp; YES</TD></TR>\n";}
	if ($user_territories_active > 0)
		{echo "<TR><TD ALIGN=RIGHT>"._QXZ("Selected Territories").":</TD><TD ALIGN=LEFT> &nbsp; $agent_territories</TD></TR>\n";}
	echo "</TABLE>\n<BR>\n";

	if ($change_agent_campaign > 0)
		{
/*
		echo "<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<input type=hidden name=user value=\"$user\">\n";
		echo "<input type=hidden name=stage value=\"live_campaign_change\">\n";
		echo _QXZ("Current Campaign").": <SELECT SIZE=1 NAME=group>\n";
		$o=0;
		while ($groups_to_print > $o)
			{
			if ($groups[$o] == "$Acampaign") {echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
			else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
			$o++;
			}
		echo "</SELECT>\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("CHANGE")."' disabled><BR></form>\n";
*/

		echo "<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<input type=hidden name=user value=\"$user\">\n";
		echo "<input type=hidden name=stage value=\"log_agent_out\">\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value=\""._QXZ("EMERGENCY LOG AGENT OUT")."\"> &nbsp; "._QXZ("NOTE: this will hang up all calls in the agent session")."<BR></form>\n";
		}

	}

else
	{
	echo _QXZ("Agent is not logged in")."\n<BR>";
	}

if ($SSallow_chats > 0)
	{
	echo "<BR>\n";
	echo "<TABLE CELLPADDING=0 CELLSPACING=0>";
	echo "<TR><TD ALIGN=RIGHT>"._QXZ("Currently active in chats").":</TD><TD ALIGN=LEFT> &nbsp; $Achats</TD></TR>\n";
	echo "</TABLE>\n<BR>\n";
	}

echo "\n<BR>";

if ( ($Tstatus == "LOGIN") or ($Tstatus == "START") )
	{
	echo _QXZ("User")." $user($full_name) - "._QXZ("is logged in to the timeclock").". <BR>"._QXZ("Login time").": $Tevent_date "._QXZ("from")." $Tip_address<BR>\n";
	$TC_log_change_stage =	'tc_log_user_OUT';
	$TC_log_change_button = _QXZ("TIMECLOCK LOG THIS USER OUT");
	}
else
	{
	echo _QXZ("User")." $user($full_name) - "._QXZ("is NOT logged in to the timeclock").". <BR>"._QXZ("Last logout time").": $Tevent_date "._QXZ("from")." $Tip_address<BR>\n";
	$TC_log_change_stage =	'tc_log_user_IN';
	$TC_log_change_button = _QXZ("TIMECLOCK LOG THIS USER IN");
	}

if ($modify_timeclock_log > 0)
	{
	echo "<BR><BR>\n";
	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=hidden name=user value=\"$user\">\n";
	echo "<input type=hidden name=stage value=\"$TC_log_change_stage\">\n";
	echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value=\"$TC_log_change_button\"><BR></form>\n";
	echo "<BR><BR>\n";
	}


$REPORTdate = date("Y-m-d");
echo "<center>\n";
echo "<a href=\"./AST_agent_time_sheet.php?agent=$user\">"._QXZ("Agent Time Sheet")."</a>\n";
echo " | <a href=\"./user_stats.php?user=$user\">"._QXZ("User Stats")."</a>\n";
echo " | <a href=\"./admin.php?ADD=3&user=$user\">"._QXZ("Modify User")."</a>\n";
echo " | <a href=\"./AST_agent_days_detail.php?user=$user&query_date=$REPORTdate&end_date=$REPORTdate&group[]=--ALL--&shift=ALL\">"._QXZ("User multiple day status detail report")."</a>";
echo "</center>\n";

echo "</B></TD></TR>\n";
echo "<TR><TD ALIGN=LEFT COLSPAN=2>\n";


$ENDtime = date("U");

$RUNtime = ($ENDtime - $StarTtimE);

echo "\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";

echo "|$stage|$group|";

?>


</TD></TR><TABLE>
</body>
</html>

<?php
	

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

exit; 

?>
