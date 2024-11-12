<?php
# alt_display.php
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to display agent screen information outside of the agent screen
# To use this script, you must set the options.php setting $alt_display_enabled	= '1';
#
# required variables:
#  - $user
#
# Available ACTIONs:
# - top_panel = static agent dashboard
# - top_panel_realtime = agent dashboard reloading every second
# - agent_status = data-only compressed details for logged-in agent status
# - notification_data = data-only, designed to check for and send ALT_DISPLAY recipient notification data only for this agent for outside display
#
# CHANGELOG:
# 200827-1157 - First build
# 200920-0906 - Added agent-lag-time to agent_status action
# 201026-1504 - Fix for LIVE call issue in top_panel
# 210426-0138 - Added calls_inqueue_count_ campaign settings options, and calls_in_queue_option=CAMPAIGN setting
# 210428-2156 - Added calls_in_queue_display setting
# 210616-1907 - Added optional CORS support, see options.php for details
# 220220-0940 - Added allow_web_debug system setting
# 231214-0912 - Added notification_data action
# 240320-1047 - Added input filtering for error output
# 240805-0121 - Updates to notification_data function
#

$version = '2.14-10';
$build = '240805-0121';
$php_script = 'alt_display.php';
$mel=1;					# Mysql Error Log enabled = 1
$mysql_log_count=11;
$one_mysql_log=0;
$DB=0;
$VD_login=0;
$SSagent_debug_logging=0;
$pause_to_code_jump=0;
$startMS = microtime();
$STACKED_left=80;

require_once("dbconnect_mysqli.php");
require_once("functions.php");

### If you have globals turned off uncomment these lines
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["stage"]))						{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))				{$stage=$_POST["stage"];}
if (isset($_GET["ACTION"]))						{$ACTION=$_GET["ACTION"];}
	elseif (isset($_POST["ACTION"]))			{$ACTION=$_POST["ACTION"];}
if (isset($_GET["calls_in_queue_option"]))				{$calls_in_queue_option=$_GET["calls_in_queue_option"];}
	elseif (isset($_POST["calls_in_queue_option"]))		{$calls_in_queue_option=$_POST["calls_in_queue_option"];}
if (isset($_GET["calls_in_queue_display"]))				{$calls_in_queue_display=$_GET["calls_in_queue_display"];}
	elseif (isset($_POST["calls_in_queue_display"]))	{$calls_in_queue_display=$_POST["calls_in_queue_display"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}

# default optional vars if not set
if (!isset($stage))   {$stage="default";}
if (!isset($ACTION))   {$ACTION="top_panel";}

$user = preg_replace("/\'|\"|\\\\|;/","",$user);
$stage = preg_replace("/[^-_0-9a-zA-Z]/","",$stage);
$ACTION = preg_replace('/[^-_0-9a-zA-Z]/','',$ACTION);
$calls_in_queue_option = preg_replace('/[^-_0-9a-zA-Z]/','',$calls_in_queue_option);
$calls_in_queue_display = preg_replace('/[^-_0-9a-zA-Z]/','',$calls_in_queue_display);
$DB = preg_replace('/[^-_0-9a-zA-Z]/','',$DB);
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

$alt_display_enabled	= '0';	# set to 1 to allow the alt_display.php script to be used

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

if ($alt_display_enabled < 1)
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	echo "ERROR: Alt Display script disabled: |$user|$alt_display_enabled|\n";
	exit;
	}
if (strlen($user) < 1)
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	echo "ERROR: user not defined: |$user|\n";
	exit;
	}

$txt = '.txt';
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$SQLdate = $NOW_TIME;
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$MT[0]='';
$agents='@agents';
$US='_';
while (strlen($CIDdate) > 9) {$CIDdate = substr("$CIDdate", 1);}
$check_time = ($StarTtime - 86400);

$secX = date("U");
$epoch = $secX;
$hour = date("H");
$min = date("i");
$sec = date("s");
$mon = date("m");
$mday = date("d");
$year = date("Y");
$isdst = date("I");
$Shour = date("H");
$Smin = date("i");
$Ssec = date("s");
$Smon = date("m");
$Smday = date("d");
$Syear = date("Y");

### Grab Server GMT value from the database
$stmt="SELECT local_gmt FROM servers where active='Y' limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09001',$user,$server_ip,$session_name,$one_mysql_log);}
$gmt_recs = mysqli_num_rows($rslt);
if ($gmt_recs > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$DBSERVER_GMT		=		$row[0];
	if (strlen($DBSERVER_GMT)>0)	{$SERVER_GMT = $DBSERVER_GMT;}
	if ($isdst) {$SERVER_GMT++;} 
	}
else
	{
	$SERVER_GMT = date("O");
	$SERVER_GMT = preg_replace("/\+/i","",$SERVER_GMT);
	$SERVER_GMT = ($SERVER_GMT + 0);
	$SERVER_GMT = ($SERVER_GMT / 100);
	}

$LOCAL_GMT_OFF = $SERVER_GMT;
$LOCAL_GMT_OFF_STD = $SERVER_GMT;


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,timeclock_end_of_day,agentonly_callback_campaign_lock,alt_log_server_ip,alt_log_dbname,alt_log_login,alt_log_pass,tables_use_alt_log_db,qc_features_active,allow_emails,callback_time_24hour,enable_languages,language_method,agent_debug_logging,default_language,active_modules,allow_chats,default_phone_code,user_new_lead_limit,sip_event_logging,call_quota_lead_ranking,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09002',$user,$server_ip,$session_name,$one_mysql_log);}
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =							$row[0];
	$timeclock_end_of_day =					$row[1];
	$agentonly_callback_campaign_lock =		$row[2];
	$alt_log_server_ip =					$row[3];
	$alt_log_dbname =						$row[4];
	$alt_log_login =						$row[5];
	$alt_log_pass =							$row[6];
	$tables_use_alt_log_db =				$row[7];
	$qc_features_active =					$row[8];
	$allow_emails =							$row[9];
	$callback_time_24hour =					$row[10];
	$SSenable_languages =					$row[11];
	$SSlanguage_method =					$row[12];
	$SSagent_debug_logging =				$row[13];
	$SSdefault_language =					$row[14];
	$active_modules =						$row[15];
	$allow_chats =							$row[16];
	$default_phone_code =					$row[17];
	$SSuser_new_lead_limit =				$row[18];
	$SSsip_event_logging =					$row[19];
	$SScall_quota_lead_ranking =			$row[20];
	$SSallow_web_debug =					$row[21];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	}
else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	}

if (strlen($SSagent_debug_logging) > 1)
	{
	if ($SSagent_debug_logging == "$user")
		{$SSagent_debug_logging=1;}
	else
		{$SSagent_debug_logging=0;}
	}

$VUselected_language = $SSdefault_language;
$VUuser_new_lead_limit='-1';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09003',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}
else
	{
	echo _QXZ("ERROR: user does not exist").": |$user|\n";
	exit;
	}

if ($DB > 0)
	{
	echo "<html>\n";
	echo "<head>\n";
	echo "<!-- VERSION: $version     BUILD: $build    USER: $user -->\n";
	echo "<title>"._QXZ("Alt Agent Display Script");
	echo "</title>\n";
	echo "</head>\n";
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	}


################################################################################
### top_panel - shows a static display of the top panel dashboard for the agent
################################################################################
if ($ACTION == 'top_panel')
	{
	$live_call = 'NONE';
	$stmt="SELECT server_ip,status,lead_id,campaign_id,callerid,last_update_time,closer_campaigns,calls_today,pause_code,UNIX_TIMESTAMP(last_call_time),UNIX_TIMESTAMP(last_state_change),preview_lead_id from vicidial_live_agents where user='$user';";
	$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09004',$user,$server_ip,$session_name,$one_mysql_log);}
	$cl_user_ct = mysqli_num_rows($rslt);
	if ($cl_user_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$VLAserver_ip =						$row[0];
		$VLAstatus =						$row[1];
		$VLAlead_id =						$row[2];
		$VLAcampaign_id =					$row[3];
		$VLAcallerid =						$row[4];
		$VLAlast_update_time =				$row[5];
		$VLAcloser_campaigns =				$row[6];
		$VLAcalls_today =					$row[7];
		$VLApause_code =					$row[8];
		$VLAlast_call_time_epoch =			$row[9];
		$VLAlast_state_change_epoch =		$row[10];
		$VLApreview_lead_id =				($row[11] + 0);
		if ($VLAstatus == 'INCALL') {$live_call = 'DEAD';}
		if ( ($VLAstatus == 'PAUSED') and ($VLApreview_lead_id > 0) ) {$live_call = 'PREVIEW';}
		}
	else
		{
		echo _QXZ("ERROR: user is not logged in").": $user\n";
		exit;
		}

	### BEGIN Live Call Display section ###
	if (strlen($VLAcallerid) > 5)
		{
		$stmt="SELECT server_ip,status,lead_id,campaign_id,callerid,call_time,call_type,UNIX_TIMESTAMP(call_time) from vicidial_auto_calls where callerid='$VLAcallerid';";
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09005',$user,$server_ip,$session_name,$one_mysql_log);}
		$cl_calls_ct = mysqli_num_rows($rslt);
		if ($cl_calls_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$VACserver_ip =						$row[0];
			$VACstatus =						$row[1];
			$VAClead_id =						$row[2];
			$VACcampaign_id =					$row[3];
			$VACcallerid =						$row[4];
			$VACcall_time =						$row[5];
			$VACcall_type =						$row[6];
			$VACcalls_today =					$row[7];
			$VACcall_time_epoch =				$row[8];
			$live_call = 'LIVE';
			}
		}

	if ( ($live_call != 'NONE') and ($live_call != 'PREVIEW') )
		{
		if ($live_call == 'LIVE')
			{
			echo "<font color=\"green\" size=2 face=\"arial,helvetica\"><b>"._QXZ("LIVE CALL")."</font></b> &nbsp; &nbsp; \n";
			$call_length = ($StarTtime - $VLAlast_state_change_epoch);
			$minutes = floor($call_length / 60);
			$seconds = $call_length - ($minutes * 60);
			$CLtime = sprintf('%s:%s',$minutes,str_pad($seconds,2,'0',STR_PAD_LEFT));
			echo "<font color=\"black\" size=2 face=\"arial,helvetica\">"._QXZ("Call Timer").": $CLtime</font> &nbsp; &nbsp; \n";
			$stage = "LIVE $CLtime";
			$STACKED_left = ($STACKED_left + 140);
			}
		if ($live_call == 'DEAD')
			{
			echo "<font color=\"red\" size=2 face=\"arial,helvetica\"><b>"._QXZ("DEAD CALL")."</font></b> &nbsp; \n";

			$call_length = ($VLAlast_state_change_epoch - $VLAlast_call_time_epoch);
			$minutes = floor($call_length / 60);
			$seconds = $call_length - ($minutes * 60);
			$CLtime = sprintf('%s:%s',$minutes,str_pad($seconds,2,'0',STR_PAD_LEFT));

			$hangup_length = ($StarTtime - $VLAlast_state_change_epoch);
			$minutes = floor($hangup_length / 60);
			$seconds = $hangup_length - ($minutes * 60);
			$HUtime = sprintf('%s:%s',$minutes,str_pad($seconds,2,'0',STR_PAD_LEFT));

			echo "<font color=\"black\" size=2 face=\"arial,helvetica\">"._QXZ("Call Time").": $CLtime</font> &nbsp; \n";
			echo "<font color=\"black\" size=2 face=\"arial,helvetica\">"._QXZ("Dead Timer").": $HUtime</font> &nbsp; \n";
			$stage = "DEAD $CLtime $HUtime";
			$STACKED_left = ($STACKED_left + 230);
			}
		}
	else
		{
		if ($live_call == 'PREVIEW')
			{
			echo "<font color=\"gold\" size=2 face=\"arial,helvetica\"><b>"._QXZ("PREVIEW")."</font></b> &nbsp; &nbsp; \n";
			$stage = "PREVIEW";
			}
		else
			{
			echo "<font color=\"grey\" size=2 face=\"arial,helvetica\"><b>"._QXZ("NO CALL")."</font></b> &nbsp; &nbsp; \n";
			$stage = "NONE";
			}
		}
	### END Live Call Display section ###


	### BEGIN Calls in Queue section ###
	$CIQcount=0;
	$AccampSQL = preg_replace('/\s\-/','', $VLAcloser_campaigns);
	$AccampSQL = preg_replace('/\s/',"','", $AccampSQL);
	if (preg_match('/AGENTDIRECT/i', $AccampSQL))
		{
		$AccampSQL = preg_replace('/AGENTDIRECT/i','', $AccampSQL);
		$ADsql = "or ( (campaign_id LIKE \"%AGENTDIRECT%\") and (agent_only='$user') )";
		}

	### BEGIN check for calls_inqueue_count_ settings containers and calculate calls in queue ###
	$RingCallsOne='';
	$RingCallsTwo='';
	$Acampaign = $VLAcampaign_id;
	if ($calls_in_queue_option == 'CAMPAIGN')
		{
		$calls_inqueue_count_one='';
		$calls_inqueue_count_two='';
		$stmt = "SELECT calls_inqueue_count_one,calls_inqueue_count_two FROM vicidial_campaigns where campaign_id='$Acampaign';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$vcc_conf_ct = mysqli_num_rows($rslt);
		if ($vcc_conf_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$calls_inqueue_count_one =	$row[0];
			$calls_inqueue_count_two =	$row[1];
			}

		if ( ( ($calls_inqueue_count_one != '') and ($calls_inqueue_count_one != 'DISABLED') ) or ( ($calls_inqueue_count_two != '') and ($calls_inqueue_count_two != 'DISABLED') ) )
			{
			# gather calls_inqueue_count_one settings container
			$stmt = "SELECT container_entry FROM vicidial_settings_containers where container_id='$calls_inqueue_count_one';";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}
			if ($DB) {echo "$stmt\n";}
			$SCinfo_ct = mysqli_num_rows($rslt);
			if ($SCinfo_ct > 0)
				{
				$calls_inqueue_count_one_heading='';
				$row=mysqli_fetch_row($rslt);
				$SAcontainer_entry =	$row[0];
				$SAcontainer_entry = preg_replace("/\r|\t|\'|\"/",'',$SAcontainer_entry);
				$calls_inqueue_count_one_settings = explode("\n",$SAcontainer_entry);
				$calls_inqueue_count_one_settings_ct = count($calls_inqueue_count_one_settings);
				$calls_inqueue_count_one_output='';
				$calls_inqueue_count_one_groups_SQL='';
				$except_container_id_output='';
				$cic_excpt_stmt='';
				$sea=0;
				while ($calls_inqueue_count_one_settings_ct >= $sea)
					{
					if (preg_match("/^HEADING => |^HEADING=>/",$calls_inqueue_count_one_settings[$sea]))
						{
						$calls_inqueue_count_one_heading = preg_replace("/^HEADING => |^HEADING=>/i",'',$calls_inqueue_count_one_settings[$sea]);
						}
					else
						{
						if ( (!preg_match("/^;/",$calls_inqueue_count_one_settings[$sea])) and (strlen($calls_inqueue_count_one_settings[$sea]) > 0) )
							{
							$cic_one_stmt='';
							if ($calls_inqueue_count_one_settings[$sea] == '--ALL-CALLS--')
								{
								$cic_one_stmt="from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) $ADsql)";
								}
							else
								{
								if (preg_match("/^--ALL-IN-GROUP-CALLS-/",$calls_inqueue_count_one_settings[$sea]))
									{
									$cic_one_stmt="from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id IN('$AccampSQL')) $ADsql)";
									}
								if (preg_match("/^--ALL-CAMPAIGN-CALLS-/",$calls_inqueue_count_one_settings[$sea]))
									{
									$cic_one_stmt="from vicidial_auto_calls where status IN('LIVE') and (campaign_id='$Acampaign')";
									}
								if (preg_match("/^--ALL-CALLS-/",$calls_inqueue_count_one_settings[$sea]))
									{
									$cic_one_stmt="from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) $ADsql)";
									}
								if (preg_match("/-CALLS-EXCEPT=/",$calls_inqueue_count_one_settings[$sea]))
									{
									$except_container_id = preg_replace("/.*-CALLS-EXCEPT=/",'',$calls_inqueue_count_one_settings[$sea]);

									# BEGIN gather EXCEPTION settings container
									$stmt = "SELECT container_entry FROM vicidial_settings_containers where container_id='$except_container_id';";
									$rslt=mysql_to_mysqli($stmt, $link);
										if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}
									if ($DB) {echo "EX|$stmt\n";}
									$SCinfo_ct = mysqli_num_rows($rslt);
									if ($SCinfo_ct > 0)
										{
										$row=mysqli_fetch_row($rslt);
										$SAcontainer_entry =	$row[0];
										$SAcontainer_entry = preg_replace("/\r|\t|\'|\"/",'',$SAcontainer_entry);
										$except_container_id_settings = explode("\n",$SAcontainer_entry);
										$except_container_id_settings_ct = count($except_container_id_settings);
										$except_container_id_groups_SQL='';
										$seaEX=0;
										while ($except_container_id_settings_ct >= $seaEX)
											{
											if ( (!preg_match("/^;|^HEADING => |^HEADING=>/i",$except_container_id_settings[$seaEX])) and (strlen($except_container_id_settings[$seaEX]) > 0) )
												{
												if ($except_container_id_settings[$seaEX] == '--ALL-CALLS--')
													{
													$cic_excpt_stmt=" and (campaign_id!='$Acampaign') and (campaign_id NOT IN('$AccampSQL'))";
													}
												else
													{
													if (preg_match("/^--ALL-IN-GROUP-CALLS-/",$except_container_id_settings[$seaEX]))
														{
														$cic_excpt_stmt=" and (campaign_id NOT IN('$AccampSQL'))";
														}
													if (preg_match("/^--ALL-CAMPAIGN-CALLS-/",$except_container_id_settings[$seaEX]))
														{
														$cic_excpt_stmt=" and (campaign_id!='$Acampaign')";
														}
													if (preg_match("/^--ALL-CALLS-/",$except_container_id_settings[$seaEX]))
														{
														$cic_excpt_stmt=" and (campaign_id!='$Acampaign') and (campaign_id NOT IN('$AccampSQL'))";
														}
													if (strlen($cic_excpt_stmt) < 10)
														{
														$except_container_id_settings[$seaEX] = preg_replace('/[^-_0-9\p{L}]/u','',$except_container_id_settings[$seaEX]);
														if (strlen($except_container_id_settings[$seaEX]) > 0)
															{
															if (strlen($except_container_id_groups_SQL) > 1) {$except_container_id_groups_SQL .= ",";}
															$except_container_id_groups_SQL .= "'$except_container_id_settings[$seaEX]'";
															}
														}
													}
												}
											$seaEX++;
											}
										if ( (strlen($cic_excpt_stmt) < 10) and (strlen($except_container_id_groups_SQL) > 2) )
											{
											$cic_excpt_stmt="and (campaign_id NOT IN($except_container_id_groups_SQL))";
											}
										}
									# END gather EXCEPTION settings container
									}
								if (strlen($cic_one_stmt) < 10)
									{
									$calls_inqueue_count_one_settings[$sea] = preg_replace('/[^-_0-9\p{L}]/u','',$calls_inqueue_count_one_settings[$sea]);
									if (strlen($calls_inqueue_count_one_settings[$sea]) > 0)
										{
										if (strlen($calls_inqueue_count_one_groups_SQL) > 1) {$calls_inqueue_count_one_groups_SQL .= ",";}
										$calls_inqueue_count_one_groups_SQL .= "'$calls_inqueue_count_one_settings[$sea]'";
										}
									}
								}
							}
						}
					$sea++;
					}
				if ( (strlen($cic_one_stmt) < 10) and (strlen($calls_inqueue_count_one_groups_SQL) > 2) )
					{
					$cic_one_stmt="from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id IN($calls_inqueue_count_one_groups_SQL)) $ADsql)";
					}
				if (strlen($cic_one_stmt) > 10)
					{
					$TEMPcic_one_stmt = "SELECT count(*) $cic_one_stmt $cic_excpt_stmt;";
					if ($DB) {echo "CIC ONE|$TEMPcic_one_stmt|\n";}
					$rslt=mysql_to_mysqli($TEMPcic_one_stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$TEMPcic_one_stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}
					$row=mysqli_fetch_row($rslt);
					$RingCallsOne=$row[0];

					if ($RingCallsOne > 0)
						{
						$stage .= " Waiting $RingCallsOne";
						$RingCallsOne = "<font color=\"red\" size=2 face=\"arial,helvetica\"><b>$calls_inqueue_count_one_heading: $RingCallsOne</font></b> &nbsp; &nbsp; \n";

						### grab time of oldest call waiting in queue for this agent's campaign and selected in-groups
						$stmt="SELECT UNIX_TIMESTAMP(call_time) $cic_one_stmt $cic_excpt_stmt order by call_time limit 1;";
						if ($DB) {echo "CIC ONETIME|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09XXX',$user,$server_ip,$session_name,$one_mysql_log);}
						$cl_calls_ct = mysqli_num_rows($rslt);
						if ($cl_calls_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$CIQcall_time_epoch =		$row[0];

							$wait_length = ($StarTtime - $CIQcall_time_epoch);
							$minutes = floor($wait_length / 60);
							$seconds = $wait_length - ($minutes * 60);
							$WLtime = sprintf('%s:%s',$minutes,str_pad($seconds,2,'0',STR_PAD_LEFT));

							$RingCallsOne .= "<font color=\"red\" size=2 face=\"arial,helvetica\">"._QXZ("Hold Time").": $WLtime</font> &nbsp; &nbsp; \n";
							$stage .= " $WLtime";
							}
						}
					else
						{$RingCallsOne = "<font color=\"lightgrey\" size=2 face=\"arial,helvetica\"><b> &nbsp; &nbsp; $calls_inqueue_count_one_heading: 0</font></b> &nbsp; &nbsp; \n";}
					}
				}

			# gather calls_inqueue_count_two settings container
			$stmt = "SELECT container_entry FROM vicidial_settings_containers where container_id='$calls_inqueue_count_two';";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$two_mysql_log);}
			if ($DB) {echo "$stmt\n";}
			$SCinfo_ct = mysqli_num_rows($rslt);
			if ($SCinfo_ct > 0)
				{
				$calls_inqueue_count_two_heading='';
				$row=mysqli_fetch_row($rslt);
				$SAcontainer_entry =	$row[0];
				$SAcontainer_entry = preg_replace("/\r|\t|\'|\"/",'',$SAcontainer_entry);
				$calls_inqueue_count_two_settings = explode("\n",$SAcontainer_entry);
				$calls_inqueue_count_two_settings_ct = count($calls_inqueue_count_two_settings);
				$calls_inqueue_count_two_output='';
				$calls_inqueue_count_two_groups_SQL='';
				$except_container_id_output='';
				$cic_excpt_stmt='';
				$sea=0;
				while ($calls_inqueue_count_two_settings_ct >= $sea)
					{
					if (preg_match("/^HEADING => |^HEADING=>/",$calls_inqueue_count_two_settings[$sea]))
						{
						$calls_inqueue_count_two_heading = preg_replace("/^HEADING => |^HEADING=>/i",'',$calls_inqueue_count_two_settings[$sea]);
						}
					else
						{
						if ( (!preg_match("/^;/",$calls_inqueue_count_two_settings[$sea])) and (strlen($calls_inqueue_count_two_settings[$sea]) > 0) )
							{
							$cic_two_stmt='';
							if ($calls_inqueue_count_two_settings[$sea] == '--ALL-CALLS--')
								{
								$cic_two_stmt="from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) $ADsql)";
								}
							else
								{
								if (preg_match("/^--ALL-IN-GROUP-CALLS-/",$calls_inqueue_count_two_settings[$sea]))
									{
									$cic_two_stmt="from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id IN('$AccampSQL')) $ADsql)";
									}
								if (preg_match("/^--ALL-CAMPAIGN-CALLS-/",$calls_inqueue_count_two_settings[$sea]))
									{
									$cic_two_stmt="from vicidial_auto_calls where status IN('LIVE') and (campaign_id='$Acampaign')";
									}
								if (preg_match("/^--ALL-CALLS-/",$calls_inqueue_count_two_settings[$sea]))
									{
									$cic_two_stmt="from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) $ADsql)";
									}
								if (preg_match("/-CALLS-EXCEPT=/",$calls_inqueue_count_two_settings[$sea]))
									{
									$except_container_id = preg_replace("/.*-CALLS-EXCEPT=/",'',$calls_inqueue_count_two_settings[$sea]);

									# BEGIN gather EXCEPTION settings container
									$stmt = "SELECT container_entry FROM vicidial_settings_containers where container_id='$except_container_id';";
									$rslt=mysql_to_mysqli($stmt, $link);
										if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$two_mysql_log);}
									if ($DB) {echo "EX|$stmt\n";}
									$SCinfo_ct = mysqli_num_rows($rslt);
									if ($SCinfo_ct > 0)
										{
										$row=mysqli_fetch_row($rslt);
										$SAcontainer_entry =	$row[0];
										$SAcontainer_entry = preg_replace("/\r|\t|\'|\"/",'',$SAcontainer_entry);
										$except_container_id_settings = explode("\n",$SAcontainer_entry);
										$except_container_id_settings_ct = count($except_container_id_settings);
										$except_container_id_groups_SQL='';
										$seaEX=0;
										while ($except_container_id_settings_ct >= $seaEX)
											{
											if ( (!preg_match("/^;|^HEADING => |^HEADING=>/i",$except_container_id_settings[$seaEX])) and (strlen($except_container_id_settings[$seaEX]) > 0) )
												{
												if ($except_container_id_settings[$seaEX] == '--ALL-CALLS--')
													{
													$cic_excpt_stmt=" and (campaign_id!='$Acampaign') and (campaign_id NOT IN('$AccampSQL'))";
													}
												else
													{
													if (preg_match("/^--ALL-IN-GROUP-CALLS-/",$except_container_id_settings[$seaEX]))
														{
														$cic_excpt_stmt=" and (campaign_id NOT IN('$AccampSQL'))";
														}
													if (preg_match("/^--ALL-CAMPAIGN-CALLS-/",$except_container_id_settings[$seaEX]))
														{
														$cic_excpt_stmt=" and (campaign_id!='$Acampaign')";
														}
													if (preg_match("/^--ALL-CALLS-/",$except_container_id_settings[$seaEX]))
														{
														$cic_excpt_stmt=" and (campaign_id!='$Acampaign') and (campaign_id NOT IN('$AccampSQL'))";
														}
													if (strlen($cic_excpt_stmt) < 10)
														{
														$except_container_id_settings[$seaEX] = preg_replace('/[^-_0-9\p{L}]/u','',$except_container_id_settings[$seaEX]);
														if (strlen($except_container_id_settings[$seaEX]) > 0)
															{
															if (strlen($except_container_id_groups_SQL) > 1) {$except_container_id_groups_SQL .= ",";}
															$except_container_id_groups_SQL .= "'$except_container_id_settings[$seaEX]'";
															}
														}
													}
												}
											$seaEX++;
											}
										if ( (strlen($cic_excpt_stmt) < 10) and (strlen($except_container_id_groups_SQL) > 2) )
											{
											$cic_excpt_stmt="and (campaign_id NOT IN($except_container_id_groups_SQL))";
											}
										}
									# END gather EXCEPTION settings container
									}
								if (strlen($cic_two_stmt) < 10)
									{
									$calls_inqueue_count_two_settings[$sea] = preg_replace('/[^-_0-9\p{L}]/u','',$calls_inqueue_count_two_settings[$sea]);
									if (strlen($calls_inqueue_count_two_settings[$sea]) > 0)
										{
										if (strlen($calls_inqueue_count_two_groups_SQL) > 1) {$calls_inqueue_count_two_groups_SQL .= ",";}
										$calls_inqueue_count_two_groups_SQL .= "'$calls_inqueue_count_two_settings[$sea]'";
										}
									}
								}
							}
						}
					$sea++;
					}
				if ( (strlen($cic_two_stmt) < 10) and (strlen($calls_inqueue_count_two_groups_SQL) > 2) )
					{
					$cic_two_stmt="from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id IN($calls_inqueue_count_two_groups_SQL)) $ADsql)";
					}
				if (strlen($cic_two_stmt) > 10)
					{
					$TEMPcic_two_stmt = "SELECT count(*) $cic_two_stmt $cic_excpt_stmt;";
					if ($DB) {echo "CIC TWO|$TEMPcic_two_stmt|\n";}
					$rslt=mysql_to_mysqli($TEMPcic_two_stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$TEMPcic_two_stmt,'03XXX',$user,$server_ip,$session_name,$two_mysql_log);}
					$row=mysqli_fetch_row($rslt);
					$RingCallsTwo=$row[0];

					if ($RingCallsTwo > 0)
						{
						$stage .= " Waiting $RingCallsTwo";
						$RingCallsTwo = "<font color=\"red\" size=2 face=\"arial,helvetica\"><b>$calls_inqueue_count_two_heading: $RingCallsTwo</font></b> &nbsp; &nbsp; \n";

						### grab time of oldest call waiting in queue for this agent's campaign and selected in-groups
						$stmt="SELECT UNIX_TIMESTAMP(call_time) $cic_two_stmt $cic_excpt_stmt order by call_time limit 1;";
						if ($DB) {echo "CIC TWOTIME|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09XXX',$user,$server_ip,$session_name,$one_mysql_log);}
						$cl_calls_ct = mysqli_num_rows($rslt);
						if ($cl_calls_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$CIQcall_time_epoch =		$row[0];

							$wait_length = ($StarTtime - $CIQcall_time_epoch);
							$minutes = floor($wait_length / 60);
							$seconds = $wait_length - ($minutes * 60);
							$WLtime = sprintf('%s:%s',$minutes,str_pad($seconds,2,'0',STR_PAD_LEFT));

							$RingCallsTwo .= "<font color=\"red\" size=2 face=\"arial,helvetica\">"._QXZ("Hold Time").": $WLtime</font> &nbsp; &nbsp; \n";
							$stage .= " $WLtime";
							}
						}
					else
						{$RingCallsTwo = "<font color=\"lightgrey\" size=2 face=\"arial,helvetica\"><b> &nbsp; &nbsp; $calls_inqueue_count_two_heading: 0</font></b> &nbsp; &nbsp; \n";}
					}
				}
			if ( (strlen($RingCallsOne) > 10) or (strlen($RingCallsTwo) > 10) )
				{
				if ( (strlen($RingCallsOne) > 10) and (strlen($RingCallsTwo) > 10) )
					{
					if ($calls_in_queue_display == 'STACKED')
						{
						echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
						echo "<span style=\"position:absolute;left:".$STACKED_left."px;top:0px;z-index:100;\" id=\"CallsInQueue1\">$RingCallsOne</span>";
						echo "<span style=\"position:absolute;left:".$STACKED_left."px;top:12px;z-index:101;\" id=\"CallsInQueue2\">$RingCallsTwo</span>";
						}
					else
						{echo "$RingCallsOne &nbsp; &nbsp; $RingCallsTwo";}
					}
				else
					{echo "$RingCallsOne$RingCallsTwo";}
				}
			}
		}
	### END check for calls_inqueue_count_ settings containers and calculate calls in queue ###
	if ( (strlen($RingCallsOne) < 10) and (strlen($RingCallsTwo) < 10) )
		{
		### grab the number of calls waiting in queue for this agent's campaign and selected in-groups
		$stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$VLAcampaign_id') or (campaign_id IN('$AccampSQL')) $ADsql);";
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09006',$user,$server_ip,$session_name,$one_mysql_log);}
		$cl_calls_ct = mysqli_num_rows($rslt);
		if ($cl_calls_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$CIQcount =						$row[0];
			}

		if ($CIQcount > 0)
			{
			echo "<font color=\"red\" size=2 face=\"arial,helvetica\"><b>"._QXZ("Calls Waiting").": $CIQcount</font></b> &nbsp; &nbsp; \n";
			$stage .= " Waiting $CIQcount";

			### grab the number of calls waiting in queue for this agent's campaign and selected in-groups
			$stmt="SELECT UNIX_TIMESTAMP(call_time) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$VLAcampaign_id') or (campaign_id IN('$AccampSQL')) $ADsql) order by call_time limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09007',$user,$server_ip,$session_name,$one_mysql_log);}
			$cl_calls_ct = mysqli_num_rows($rslt);
			if ($cl_calls_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$CIQcall_time_epoch =		$row[0];

				$wait_length = ($StarTtime - $CIQcall_time_epoch);
				$minutes = floor($wait_length / 60);
				$seconds = $wait_length - ($minutes * 60);
				$WLtime = sprintf('%s:%s',$minutes,str_pad($seconds,2,'0',STR_PAD_LEFT));

				echo "<font color=\"red\" size=2 face=\"arial,helvetica\">"._QXZ("Hold Time").": $WLtime</font> &nbsp; &nbsp; \n";
				$stage .= " $WLtime";
				}
			}
		else
			{echo "<font color=\"lightgrey\" size=2 face=\"arial,helvetica\"><b> &nbsp; &nbsp; "._QXZ("no calls waiting")."</font></b> &nbsp; &nbsp; \n";}
		}
	### END Calls in Queue section ###

	if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
	exit;
	}
################################################################################
### END top_panel - shows a static display of the top panel dashboard for the agent
################################################################################




################################################################################
### top_panel_realtime - constant refresh of the top_panel dashboard every second
################################################################################
if ($ACTION == 'top_panel_realtime')
	{
	echo "<html>\n";
	echo "<head>\n";
#	echo "<link href='https://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>\n";
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("Agent Screen Dashboard Realtime")."</TITLE>\n";
	?>
	<STYLE type="text/css">
	body,table,tr,th,td 
		{
		font-family: 'Arial', Sans-Serif;
		}
	</STYLE>

	<script language="Javascript">

	var self_url = '<?php echo $PHP_SELF ?>';
	var top_panel_content='';

	function begin_realtime() 
		{
		// AJAX code to gather the top_panel content every X seconds and update the screen
		var xmlhttp=false;
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		// JScript gives us Conditional compilation, we can cope with old IE versions.
		// and security blocked creation of the objects.
		 try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		 } catch (e) {
		  try {
		   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		  } catch (E) {
		   xmlhttp = false;
		  }
		 }
		@end @*/
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp) 
			{ 
			top_panel_query = "user=<?php echo $user ?>&ACTION=top_panel&calls_in_queue_option=<?php echo $calls_in_queue_option ?>&calls_in_queue_display=<?php echo $calls_in_queue_display ?>";
			xmlhttp.open('POST', 'alt_display.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(top_panel_query); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
					var recheck_incoming = null;
					top_panel_content = xmlhttp.responseText;
				//	alert(top_panel_query);
				//	alert(xmlhttp.responseText);
					document.getElementById("top_panel_span").innerHTML = top_panel_content;
					}
				}
			delete xmlhttp;
			}

		setTimeout(begin_realtime, 1000);
		}
	</script>
	</head>
	<?php
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onload=\"begin_realtime()\">\n";
	echo "<span id='top_panel_span'><font color=\"lightgrey\" face=\"Arial\" size=2>loading...</font></span>\n";
	echo "</BODY></html>\n";

	if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
	exit;
	}
################################################################################
### END top_panel_realtime - constant refresh of the top_panel dashboard every second
################################################################################




################################################################################
### agent_status - data-only compressed details for logged-in agent status
###                will output these fields: agent-status|call-time|dead_time|pause-code|calls-today|calls-waiting|waiting-time|agent-lag-time
###                   example:     PAUSED|0|0|BREAK|12|0|0|1
################################################################################
if ($ACTION == 'agent_status')
	{
	$agent_status = 'NONE';
	$call_length=0;
	$hangup_length=0;
	$CIQcount=0;
	$wait_length=0;

	$stmt="SELECT server_ip,status,lead_id,campaign_id,callerid,last_update_time,closer_campaigns,calls_today,pause_code,UNIX_TIMESTAMP(last_call_time),UNIX_TIMESTAMP(last_state_change),preview_lead_id,pause_code,UNIX_TIMESTAMP(last_update_time) from vicidial_live_agents where user='$user';";
	$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09008',$user,$server_ip,$session_name,$one_mysql_log);}
	$cl_user_ct = mysqli_num_rows($rslt);
	if ($cl_user_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$VLAserver_ip =						$row[0];
		$VLAstatus =						$row[1];
		$VLAlead_id =						$row[2];
		$VLAcampaign_id =					$row[3];
		$VLAcallerid =						$row[4];
		$VLAlast_update_time =				$row[5];
		$VLAcloser_campaigns =				$row[6];
		$VLAcalls_today =					$row[7];
		$VLApause_code =					$row[8];
		$VLAlast_call_time_epoch =			$row[9];
		$VLAlast_state_change_epoch =		$row[10];
		$VLApreview_lead_id =				($row[11] + 0);
		$VLApause_code =					$row[12];
		$VLAlast_update_time_epoch =		$row[13];
		if ( ($VLAstatus == 'INCALL') or ($VLAstatus == 'QUEUE') or ($VLAstatus == 'MQUEUE') ) {$agent_status = 'DEAD';}
		if ( ($VLAstatus == 'PAUSED') and ($VLAlead_id > 0) ) {$agent_status = 'DISPO';}
		if ( ($VLAstatus == 'PAUSED') and ($VLApreview_lead_id > 0) ) {$agent_status = 'PREVIEW';}
		if ( ($VLAstatus == 'PAUSED') and ($VLApause_code == 'LAGGED') ) {$agent_status = 'LAGGED';}
		if ( ($VLAstatus == 'PAUSED') and ($agent_status == 'NONE') ) {$agent_status = 'PAUSED';}
		if ($VLAstatus == 'READY') {$agent_status = 'READY';}
		if ($VLAstatus == 'CLOSER') {$agent_status = 'CLOSER';}
		}
	else
		{
		echo _QXZ("ERROR: user is not logged in").": $user\n";
		$stage = "user is not logged in";
		exit;
		}

	if (strlen($VLAcallerid) > 15)
		{
		### BEGIN Live Call Display section ###
		$stmt="SELECT server_ip,status,lead_id,campaign_id,callerid,call_time,call_type,UNIX_TIMESTAMP(call_time) from vicidial_auto_calls where callerid='$VLAcallerid';";
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09009',$user,$server_ip,$session_name,$one_mysql_log);}
		$cl_calls_ct = mysqli_num_rows($rslt);
		if ($cl_calls_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$VACserver_ip =						$row[0];
			$VACstatus =						$row[1];
			$VAClead_id =						$row[2];
			$VACcampaign_id =					$row[3];
			$VACcallerid =						$row[4];
			$VACcall_time =						$row[5];
			$VACcall_type =						$row[6];
			$VACcalls_today =					$row[7];
			$VACcall_time_epoch =				$row[8];
			$agent_status = 'LIVE';
			}

		if ( ($agent_status != 'NONE') and ($agent_status != 'PREVIEW') )
			{
			if ($agent_status == 'LIVE')
				{
				$call_length = ($StarTtime - $VLAlast_state_change_epoch);
				}
			if ($agent_status == 'DEAD')
				{
				$call_length = ($VLAlast_state_change_epoch - $VLAlast_call_time_epoch);
				$hangup_length = ($StarTtime - $VLAlast_state_change_epoch);
				}
			}
		### END Live Call Display section ###
		}

	### BEGIN Calls in Queue section ###
	$AccampSQL = preg_replace('/\s\-/','', $VLAcloser_campaigns);
	$AccampSQL = preg_replace('/\s/',"','", $AccampSQL);
	if (preg_match('/AGENTDIRECT/i', $AccampSQL))
		{
		$AccampSQL = preg_replace('/AGENTDIRECT/i','', $AccampSQL);
		$ADsql = "or ( (campaign_id LIKE \"%AGENTDIRECT%\") and (agent_only='$user') )";
		}

	### grab the number of calls waiting in queue for this agent's campaign and selected in-groups
	$stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$VLAcampaign_id') or (campaign_id IN('$AccampSQL')) $ADsql);";
	$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09010',$user,$server_ip,$session_name,$one_mysql_log);}
	$cl_calls_ct = mysqli_num_rows($rslt);
	if ($cl_calls_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$CIQcount =						$row[0];
		}

	if ($CIQcount > 0)
		{
		### grab the number of calls waiting in queue for this agent's campaign and selected in-groups
		$stmt="SELECT UNIX_TIMESTAMP(call_time) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$VLAcampaign_id') or (campaign_id IN('$AccampSQL')) $ADsql) order by call_time limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'09011',$user,$server_ip,$session_name,$one_mysql_log);}
		$cl_calls_ct = mysqli_num_rows($rslt);
		if ($cl_calls_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$CIQcall_time_epoch =		$row[0];
			$wait_length = ($StarTtime - $CIQcall_time_epoch);
			}
		}
	if ($VLAlast_update_time_epoch > 10)
			{
			$lagged_length = ($StarTtime - $VLAlast_update_time_epoch);
			}

	### END Calls in Queue section ###
	$stage="$agent_status|$call_length|$hangup_length|$VLApause_code|$VLAcalls_today|$CIQcount|$wait_length|$lagged_length";
	echo "$stage\n";

	if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
	exit;
	}
################################################################################
### END agent_status - data-only compressed details for logged-in agent status
################################################################################





################################################################################
### notification_data - data-only, designed to check for and send notification data for this agent for outside display
###                will output these fields:	"rows|notification_text|text_size|text_font|text_color|text_weight|show_confetti|duration,maxParticleCount,particleSpeed";
###                   example:     1|message-here|14|Arial|red|bold|Y|3,2450,60
################################################################################
if ($ACTION == 'notification_data')
	{
	$notif_queued_ct=0;
	$notif_sent_ct=0;
	$notif_ids='';
	# gather only ALT_DISPLAY READY notifications to be triggered, for this $user only
	$alert_stmt="select * from vicidial_agent_notifications where notification_status='READY' and (recipient_type='ALT_DISPLAY' and recipient='$user') order by notification_date asc limit 1;";
	$alert_rslt=mysql_to_mysqli($alert_stmt, $link);
	while ($alert_row=mysqli_fetch_array($alert_rslt))
		{
		$notification_id=$alert_row["notification_id"];
		$recipient=$alert_row["recipient"];
		$recipient_type=$alert_row["recipient_type"];

		$upd_stmt="update vicidial_agent_notifications set notification_status='SENT' where notification_id='$notification_id'";
		$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
		if (mysqli_affected_rows($link)>0)
			{
			$column="user"; $recipient_str="$recipient";
			$agent_stmt="select user from vicidial_live_agents where $column in ('$recipient_str')";
			$agent_rslt=mysql_to_mysqli($agent_stmt, $link);
			while($agent_row=mysqli_fetch_row($agent_rslt))
				{
				$ins_stmt="INSERT INTO vicidial_agent_notifications_queue(notification_id, user) VALUES('$notification_id', '$agent_row[0]')";
				$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
				$notif_queued_ct++;
				}
			}
		}

	$alert_check_stmt="select * from vicidial_agent_notifications_queue where user='$user' order by queue_date asc limit 1";
	$alert_check_rslt=mysql_to_mysqli($alert_check_stmt, $link);
	if (mysqli_num_rows($alert_check_rslt)>0)
		{
		$acr_rows=mysqli_num_rows($alert_check_rslt);
		$acr_row=mysqli_fetch_array($alert_check_rslt);
		$notification_id=$acr_row["notification_id"];
		$queue_id=$acr_row["queue_id"];

		$notification_stmt="select * from vicidial_agent_notifications where notification_id='$notification_id' limit 1";
		$notification_rslt=mysql_to_mysqli($notification_stmt, $link);
		while($notif_row=mysqli_fetch_array($notification_rslt))
			{
			$notif_sent_ct++;
			$notif_ids .= "-$notification_id";
			echo "$acr_rows|$notif_row[notification_text]|$notif_row[text_size]|$notif_row[text_font]|$notif_row[text_color]|$notif_row[text_weight]|$notif_row[show_confetti]|$notif_row[confetti_options]";
			}

		$del_stmt="delete from vicidial_agent_notifications_queue where queue_id='$queue_id'";
		$del_rslt=mysql_to_mysqli($del_stmt, $link);
		}

	### END Calls in Queue section ###
	$stage="$notif_queued_ct|$notif_sent_ct|$notif_ids|";
	echo "$stage\n";

	if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
	exit;
	}
################################################################################
### END notification_data - data-only, designed to check for and send notification data for this agent for outside display
################################################################################



if ($DB > 0) 
	{
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $StarTtime);
	echo "\n<!-- script runtime: $RUNtime seconds -->";
	echo "\n</body>\n</html>\n";
	}

if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
exit; 

?>
