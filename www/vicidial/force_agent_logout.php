<?php 
# force_agent_logout.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# NOTE: It is required that you add a Settings Container with the ID of 'AGENT_LOGOUT_OPTIONS' for this utility to work
#
# Contents of the Settings Container:
# LOGOUT_USER_GROUPS=>105500,553101,553102,155110
#
# CHANGES
# 201025-2320 - First build
# 220124-2127 - Changed to allow user_level 7 users with the proper permissions to use this page
# 220223-2155 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='Force Agent Logout';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$ip = getenv("REMOTE_ADDR");
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["logout_agent"]))			{$logout_agent=$_GET["logout_agent"];}
	elseif (isset($_POST["logout_agent"]))	{$logout_agent=$_POST["logout_agent"];}
if (isset($_GET["list_id"]))			{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))	{$list_id=$_POST["list_id"];}
if (isset($_GET["server_ip"]))			{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method,pass_hash_enabled,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
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
	$SSpass_hash_enabled =			$row[6];
	$SSallow_web_debug =			$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$list_id = preg_replace('/[^0-9]/', '', $list_id);
$server_ip = preg_replace('/[^-._0-9a-zA-Z]/', '', $server_ip);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$logout_agent = preg_replace('/[^-_0-9a-zA-Z]/', '', $logout_agent);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$logout_agent = preg_replace('/[^-_0-9\p{L}]/u',"",$logout_agent);
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
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level >= 7 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level >= 7 and modify_users='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	if ($reports_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to logout agents");
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

$stmt="SELECT modify_users,user_group,user_level from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGmodify_users =	$row[0];
$LOGuser_group =	$row[1];
$LOGuser_level =	$row[2];

if ($LOGmodify_users < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify users").": |$PHP_AUTH_USER|\n";
	exit;
	}

$stmt="SELECT reports_header_override,admin_home_url from vicidial_user_groups where user_group='$LOGuser_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGreports_header_override	=	$row[0];
$LOGadmin_home_url =			$row[1];
if (strlen($LOGadmin_home_url) > 5) {$SSadmin_home_url = $LOGadmin_home_url;}


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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

if ($db_source == 'S')
	{
	mysqli_close($link);
	$use_slave_server=0;
	$db_source = 'M';
	require("dbconnect_mysqli.php");
	}


$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILEDATE = date("YmdHis");

$STARTtime = date("U");

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
echo "<TITLE>"._QXZ("Force Agent Logout")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

	require("admin_header.php");



$stmt = "SELECT count(*) FROM vicidial_settings_containers where container_id='AGENT_LOGOUT_OPTIONS';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$sc_ct = mysqli_num_rows($rslt);
if ($sc_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$SC_count = $row[0];
	}

if ($SC_count > 0)
	{
	$stmt = "SELECT container_entry FROM vicidial_settings_containers where container_id='AGENT_LOGOUT_OPTIONS';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$sc_ct = mysqli_num_rows($rslt);
	if ($sc_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$container_entry =	$row[0];
		$container_ARY = explode("\n",$container_entry);
		$p=0;
		$logout_user_groups='';
		$container_ct = count($container_ARY);
		while ($p <= $container_ct)
			{
			$line = $container_ARY[$p];
			$line = preg_replace("/\n|\r|\t|\#.*|;.*/",'',$line);
			if (preg_match("/^LOGOUT_USER_GROUPS/",$line))
				{$logout_user_groups = $line;   $logout_user_groups = trim(preg_replace("/.*=>/",'',$logout_user_groups));}

			$p++;
			}
		}
	else
		{
		echo _QXZ("ERROR: Missing settings")."\n";
		exit;
		}
	}
else
	{
	echo _QXZ("ERROR: Missing settings")."\n";
	exit;
	}

if (strlen($logout_user_groups) < 1) 
	{
	echo "ERROR: Missing Setting- logout_user_groups: $logout_user_groups \n";
	exit;
	}

if ($DB) {echo "DEBUG: |$logout_user_groups|\n";}

if ($logout_user_groups == '---ALL---')
	{$user_groupSQL='';}
else
	{
	$logout_user_groups = preg_replace("/,/","','",$logout_user_groups);
	$user_groupSQL = " and user_group IN('$logout_user_groups')";
	}


if (strlen($logout_agent) < 1)
	{
	$owner_menu="<option value=\"\">--- "._QXZ("SELECT AGENT HERE")." ---</option>\n";
	$list_idSQL='';
	$owners_to_print=0;
	if (strlen($list_id) > 1) {$list_idSQL = "and list_id='$list_id'";}
	$stmt="SELECT user,full_name from vicidial_users where user_level <= $LOGuser_level $user_groupSQL order by user;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	if ($rslt) {$owners_to_print = mysqli_num_rows($rslt);}
	$i=0;
	while ($i < $owners_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$owners[$i] =			$row[0];
		$owners_name[$i] =		$row[1];
		$i++;
		}

	$i=0;
	$live_agents_ct=0;
	while ($i < $owners_to_print)
		{
		$users_to_print=0;   $owners_count=0;
		$stmt="SELECT count(*) from vicidial_live_agents where user='$owners[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		if ($rslt) {$users_to_print = mysqli_num_rows($rslt);}
		if ($users_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$owners_count =	$row[0];
			}
		if ($owners_count > 0)
			{
			$owner_menu .= "<option value=\"$owners[$i]\">$owners[$i] - $owners_name[$i]</option>\n";
			$live_agents_ct++;
			}
		$i++;
		}
	if ($live_agents_ct < 1)
		{
		$owner_menu .= "<option SELECTED value=\"\"> -- NO AGENTS ARE LOGGED IN -- </option>\n";
		}

	echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
	echo _QXZ("Logout Agent").": <SELECT SIZE=1 NAME=logout_agent>\n";
	echo "$owner_menu";
	echo "</SELECT>\n";

	echo " &nbsp; &nbsp; \n";


	echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
	echo "</FORM>\n\n";

	echo "<PRE><FONT SIZE=2>\n\n";

	echo "\n\n";
	echo _QXZ("PLEASE SELECT AN AGENT AND CLICK SUBMIT")."\n";
	}

else
	{
	echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

	echo _QXZ("LOGOUT AGENT").": $logout_agent \n";
	$user = $logout_agent;

	$now_date_epoch = date('U');
	$inactive_epoch = ($now_date_epoch - 60);
	$stmt = "SELECT user,campaign_id,UNIX_TIMESTAMP(last_update_time),status,conf_exten,server_ip from vicidial_live_agents where user='$user';";
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

		$stmt="DELETE from vicidial_live_agents where user='$user';";
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
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='LOGOUT', record_id='$user', event_code='EMERGENCY LOGOUT FROM SUPERVISOR PAGE', event_sql=\"$SQL_log\", event_notes='agent_log_id: $VAL_agent_log_id';";
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

			$stmtB = "SELECT agent,time_id,data1 FROM queue_log where agent='Agent/$user' and verb IN('AGENTLOGIN','AGENTCALLBACKLOGIN') and time_id > $check_time order by time_id desc limit 1;";

			if ($queuemetrics_loginout == 'NONE')
				{
				$pause_typeSQL='';
				if ($queuemetrics_pause_type > 0)
					{$pause_typeSQL=",data5='ADMIN'";}
				$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$now_date_epoch',call_id='NONE',queue='NONE',agent='Agent/$user',verb='PAUSEREASON',serverid='$queuemetrics_log_id',data1='LOGOFF'$pause_typeSQL;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $linkB);
				$affected_rows = mysqli_affected_rows($linkB);

				$stmtB = "SELECT agent,time_id,data1 FROM queue_log where agent='Agent/$user' and verb IN('ADDMEMBER','ADDMEMBER2') and time_id > $check_time order by time_id desc limit 1;";
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

	echo "<br><br>\n";
	echo _QXZ("Process complete, records updated").":  $UPDATEaffected_rows <br>\n";
	echo "<a href=\"$PHP_SELF\">"._QXZ("Click here to go back to the logout agent screen")."</a> <br>\n";
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
