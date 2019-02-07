<?php
# conf_exten_check.php    version 2.14
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed purely to send whether the meetme conference has live channels connected and which they are
# This script depends on the server_ip being sent and also needs to have a valid user/pass from the vicidial_users table
# 
# required variables:
#  - $server_ip
#  - $session_name
#  - $user
#  - $pass
# optional variables:
#  - $format - ('text','debug')
#  - $ACTION - ('refresh','register')
#  - $client - ('agc','vdc')
#  - $conf_exten - ('8600011',...)
#  - $exten - ('123test',...)
#  - $auto_dial_level - ('0','1','1.2',...)
#  - $campagentstdisp - ('YES',...)
# 

# changes
# 50509-1054 - First build of script
# 50511-1112 - Added ability to register a conference room
# 50610-1159 - Added NULL check on MySQL results to reduced errors
# 50706-1429 - script changed to not use HTTP login vars, user/pass instead
# 50706-1525 - Added date-time display for vicidial client display
# 50816-1500 - Added random update to vicidial_live_agents table for vdc users
# 51121-1353 - Altered echo statements for several small PHP speed optimizations
# 60410-1424 - Added ability to grab calls-being-placed and agent status
# 60421-1405 - check GET/POST vars lines with isset to not trigger PHP NOTICES
# 60619-1201 - Added variable filters to close security holes for login form
# 61128-2255 - Added update for manual dial vicidial_live_agents
# 70319-1542 - Added agent disabled display function
# 71122-0205 - Added vicidial_live_agent status output
# 80424-0442 - Added non_latin lookup from system_settings
# 80519-1425 - Added calls-in-queue tally
# 80703-1106 - Added API functionality for Hangup and Dispo
# 81104-0229 - Added mysql error logging capability
# 81104-1409 - Added multi-retry for some vicidial_live_agents table MySQL queries
# 90102-1402 - Added check for system and database time synchronization
# 90120-1720 - Added compatibility for API pause/resume and dial a number
# 90307-1855 - Added shift enforcement to send logout flag if outside of shift hours
# 90408-0020 - Added API vtiger specific callback activity record ability
# 90508-0727 - Changed to PHP long tags
# 90706-1430 - Fixed AGENTDIRECT calls in queue display count
# 90908-1037 - Added DEAD call logging
# 91130-2022 - Added code for manager override of in-group selection
# 91228-1341 - Added API fields update functions
# 100109-1337 - Fixed Manual dial live call detection
# 100527-0957 - Added send_dtmf, transfer_conference and park_call API functions
# 100727-2209 - Added timer actions for hangup, extension, callmenu and ingroup as well as destination
# 101123-1105 - Added api manual dial queue feature to external_dial function
# 101208-0308 - Moved the Calls in Queue count and other counts outside of the autodial section (issue 406)
# 110610-0059 - Small fix for manual dial calls lasting more than 100 minutes in real-time report
# 120809-2353 - Added external_recording function
# 121028-2305 - Added extra check on session_name to validate agent screen requests
# 130328-0011 - Converted ereg to preg functions
# 130603-2218 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130705-1524 - Added optional encrypted passwords compatibility
# 130802-1015 - Changed to use PHP mysqli functions
# 140126-0659 - Added external_pause_code function
# 140810-2136 - Changed to use QXZ function for echoing text
# 141118-1233 - Formatting changes for QXZ output
# 141128-0853 - Code cleanup for QXZ functions
# 141216-2111 - Added language settings lookups and user/pass variable standardization
# 141228-0053 - Found missing phrase for QXZ
# 150723-1708 - Added ajax logging and agent screen click logging
# 150904-2138 - Added SQL features for chats started via agent invite, modified output
# 160104-1232 - Added proper detection of dead chats, disabled dead detection of emails
# 160227-1007 - Fixed XSS security issue, issue #929
# 160303-2354 - Added code for chat transfers
# 160326-0942 - Fixed issue #933, variables
# 161029-2216 - Formatting and additional agent debug logging
# 161217-0822 - Addded agent debug logging of dead call trigger
# 170220-1307 - Added external_lead_id trigger for switch_lead API function
# 170526-2228 - Added additional variable filtering
# 170709-1017 - Added xfer dead call checking process
# 170817-0739 - Small change to xfer dead call checking process
# 180602-0149 - Changed SQL query for email queue count for accuracy
#

$version = '2.14-54';
$build = '170817-0739';
$php_script = 'conf_exten_check.php';
$mel=1;					# Mysql Error Log enabled = 1
$mysql_log_count=46;
$one_mysql_log=0;
$DB=0;
$VD_login=0;
$SSagent_debug_logging=0;
$startMS = microtime();

require_once("dbconnect_mysqli.php");
require_once("functions.php");

$bcrypt=1;

### If you have globals turned off uncomment these lines
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["pass"]))					{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))			{$pass=$_POST["pass"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["session_name"]))			{$session_name=$_GET["session_name"];}
	elseif (isset($_POST["session_name"]))	{$session_name=$_POST["session_name"];}
if (isset($_GET["format"]))					{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))		{$format=$_POST["format"];}
if (isset($_GET["ACTION"]))					{$ACTION=$_GET["ACTION"];}
	elseif (isset($_POST["ACTION"]))		{$ACTION=$_POST["ACTION"];}
if (isset($_GET["client"]))					{$client=$_GET["client"];}
	elseif (isset($_POST["client"]))		{$client=$_POST["client"];}
if (isset($_GET["conf_exten"]))				{$conf_exten=$_GET["conf_exten"];}
	elseif (isset($_POST["conf_exten"]))	{$conf_exten=$_POST["conf_exten"];}
if (isset($_GET["exten"]))					{$exten=$_GET["exten"];}
	elseif (isset($_POST["exten"]))			{$exten=$_POST["exten"];}
if (isset($_GET["auto_dial_level"]))			{$auto_dial_level=$_GET["auto_dial_level"];}
	elseif (isset($_POST["auto_dial_level"]))	{$auto_dial_level=$_POST["auto_dial_level"];}
if (isset($_GET["campagentstdisp"]))			{$campagentstdisp=$_GET["campagentstdisp"];}
	elseif (isset($_POST["campagentstdisp"]))	{$campagentstdisp=$_POST["campagentstdisp"];}
if (isset($_GET["bcrypt"]))					{$bcrypt=$_GET["bcrypt"];}
	elseif (isset($_POST["bcrypt"]))		{$bcrypt=$_POST["bcrypt"];}
if (isset($_GET["clicks"]))					{$clicks=$_GET["clicks"];}
	elseif (isset($_POST["clicks"]))		{$clicks=$_POST["clicks"];}
if (isset($_GET["customer_chat_id"]))			{$customer_chat_id=$_GET["customer_chat_id"];}
	elseif (isset($_POST["customer_chat_id"]))	{$customer_chat_id=$_POST["customer_chat_id"];}
if (isset($_GET["live_call_seconds"]))			{$live_call_seconds=$_GET["live_call_seconds"];}
	elseif (isset($_POST["live_call_seconds"]))	{$live_call_seconds=$_POST["live_call_seconds"];}
if (isset($_GET["xferchannel"]))			{$xferchannel=$_GET["xferchannel"];}
	elseif (isset($_POST["xferchannel"]))	{$xferchannel=$_POST["xferchannel"];}

if ($bcrypt == 'OFF')
	{$bcrypt=0;}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);


#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03040',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$stmt = "SELECT use_non_latin,enable_languages,language_method,agent_debug_logging FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03001',$user,$server_ip,$session_name,$one_mysql_log);}
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$SSagent_debug_logging =	$row[3];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	}

$session_name = preg_replace("/\'|\"|\\\\|;/","",$session_name);
$server_ip = preg_replace("/\'|\"|\\\\|;/","",$server_ip);
$conf_exten = preg_replace("/[^-_0-9a-zA-Z]/","",$conf_exten);
$exten = preg_replace("/\'|\"|\\\\|;/","",$exten);
$clicks = preg_replace("/\'|\"|\\\\|;/","",$clicks);
$customer_chat_id = preg_replace("/[^0-9a-zA-Z]/","",$customer_chat_id);

# default optional vars if not set
if (!isset($format))   {$format="text";}
if (!isset($ACTION))   {$ACTION="refresh";}
if (!isset($client))   {$client="agc";}
if (strlen($SSagent_debug_logging) > 1)
	{
	if ($SSagent_debug_logging == "$user")
		{$SSagent_debug_logging=1;}
	else
		{$SSagent_debug_logging=0;}
	}

$Alogin='N';
$RingCalls='N';
$DiaLCalls='N';

$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_TIME = date("Ymd_His");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
$random = (rand(1000000, 9999999) + 10000000);


$auth=0;
$auth_message = user_authorization($user,$pass,'',0,$bcrypt,0,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0))
	{
	echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth_message|\n";
	exit;
	}
else
	{
	if( (strlen($server_ip)<6) or (!isset($server_ip)) or ( (strlen($session_name)<12) or (!isset($session_name)) ) )
		{
		echo _QXZ("Invalid server_ip: %1s or Invalid session_name: %2s",0,'',$server_ip,$session_name)."\n";
		exit;
		}
	else
		{
		$stmt="SELECT count(*) from web_client_sessions where session_name='$session_name' and server_ip='$server_ip';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03002',$user,$server_ip,$session_name,$one_mysql_log);}
		$row=mysqli_fetch_row($rslt);
		$SNauth=$row[0];
		  if($SNauth==0)
			{
			echo _QXZ("Invalid session_name:")." |$session_name|$server_ip|\n";
			exit;
			}
		  else
			{
			# do nothing for now
			}
		}
	}

if ($format=='debug')
	{
	echo "<html>\n";
	echo "<head>\n";
	echo "<!-- VERSION: $version     BUILD: $build    MEETME: $conf_exten   server_ip: $server_ip-->\n";
	echo "<title>"._QXZ("Conf Extension Check");
	echo "</title>\n";
	echo "</head>\n";
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	}


################################################################################
### refresh - sends agent session data to agent screen every second
################################################################################
if ($ACTION == 'refresh')
	{
	$MT[0]='';
	$row='';   $rowx='';
	$channel_live=1;
	$DEADlog='';
	if (strlen($conf_exten)<1)
		{
		$channel_live=0;
		echo _QXZ("Conf Exten %1s is not valid",0,'',$conf_exten)."\n";
		exit;
		}
	else
		{
		if ($client == 'vdc')
			{
			$Acount=0;
			$Scount=0;
			$AexternalDEAD=0;
			$Aagent_log_id='';
			$Acallerid='';
			$DEADcustomer=0;
			$Astatus='';
			$Acampaign_id='';

			### see if the agent has a record in the vicidial_live_agents table
			$stmt="SELECT count(*) from vicidial_live_agents where user='$user' and server_ip='$server_ip';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03003',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			$Acount=$row[0];

			### see if the agent has a record in the vicidial_session_data table
			$stmt="SELECT count(*) from vicidial_session_data where user='$user' and server_ip='$server_ip' and session_name='$session_name';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03039',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			$Scount=$row[0];

			if ($Acount > 0)
				{
				$stmt="SELECT status,callerid,agent_log_id,campaign_id,lead_id,comments from vicidial_live_agents where user='$user' and server_ip='$server_ip';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03004',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				$Astatus =			$row[0];
				$Acallerid =		$row[1];
				$Aagent_log_id =	$row[2];
				$Acampaign_id =		$row[3];
				$Alead_id =			$row[4];
				$Acomments =		$row[5];

				$api_manual_dial='STANDARD';
				$stmt = "SELECT api_manual_dial FROM vicidial_campaigns where campaign_id='$Acampaign_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$vcc_conf_ct = mysqli_num_rows($rslt);
				if ($vcc_conf_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$api_manual_dial =	$row[0];
					}
				}
		#	### find out if external table shows agent should be disabled
		#	$stmt="SELECT count(*) from another_table where user='$user' and status='DEAD';";
		#	if ($DB) {echo "|$stmt|\n";}
		#	$rslt=mysql_to_mysqli($stmt, $link);
		#	$row=mysqli_fetch_row($rslt);
		#	$AexternalDEAD=$row[0];

			##### BEGIN check on calls in queue, number of active calls in the campaign
			if ($campagentstdisp == 'YES')
				{
				$ADsql='';
				### grab the status of this agent to display
				$stmt="SELECT status,campaign_id,closer_campaigns from vicidial_live_agents where user='$user' and server_ip='$server_ip';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03006',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				$Alogin=$row[0];
				$Acampaign=$row[1];
				$AccampSQL=$row[2];
				$AccampSQL = preg_replace('/\s\-/','', $AccampSQL);
				$AccampSQL = preg_replace('/\s/',"','", $AccampSQL);
				if (preg_match('/AGENTDIRECT/i', $AccampSQL))
					{
					$AccampSQL = preg_replace('/AGENTDIRECT/i','', $AccampSQL);
					$ADsql = "or ( (campaign_id LIKE \"%AGENTDIRECT%\") and (agent_only='$user') )";
					}

				### grab the number of calls being placed from this server and campaign
				$stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) $ADsql);";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03007',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				$RingCalls=$row[0];
				if ($RingCalls > 0) {$RingCalls = "<font class=\"queue_text_red\">"._QXZ("Calls in Queue").": $RingCalls</font>";}
				else {$RingCalls = "<font class=\"queue_text\">"._QXZ("Calls in Queue").": $RingCalls</font>";}

				### grab the number of calls being placed from this server and campaign
				$stmt="SELECT count(*) from vicidial_auto_calls where status NOT IN('XFER') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) );";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03008',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				$DiaLCalls=$row[0];
				}
			else
				{
				$Alogin='N';
				$RingCalls='N';
				$DiaLCalls='N';
				}
			##### END check on calls in queue, number of active calls in the campaign

			### see if chats/emails are enabled, and if so how many of each are waiting
			# 03041 and 03042 are the error logs for this
			
			$chat_email_stmt="select allow_chats, allow_emails from system_settings;";
			$chat_email_rslt=mysql_to_mysqli($chat_email_stmt, $link);
			$chat_email_row=mysqli_fetch_row($chat_email_rslt);

			# GET closer logs, in case they weren't grabbed above due to campagentstdisp!=YES
			if ($chat_email_row[0]!=0 || $chat_email_row[1]!=0) 
				{
				$stmt="SELECT status,campaign_id,closer_campaigns,comments from vicidial_live_agents where user='$user' and server_ip='$server_ip';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03043',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				$Alogin=$row[0];
				$Acampaign=$row[1];
				$AccampSQL=$row[2];
				$live_agents_comments=$row[3];
				$AccampSQL = preg_replace('/\s\-/','', $AccampSQL);
				$AccampSQL = preg_replace('/\s/',"','", $AccampSQL);
				if (preg_match('/AGENTDIRECT/i', $AccampSQL))
					{
					$AccampSQL = preg_replace('/AGENTDIRECT/i','', $AccampSQL);
					$ADsql = "or ( (campaign_id LIKE \"%AGENTDIRECT%\") and (agent_only='$user') )";
					}
				}

			if ($chat_email_row[0]==0) 
				{
				$WaitinGChats="N";
				} 
			else 
				{
				$chat_stmt="select count(*) from vicidial_live_chats where status='WAITING' and ((group_id IN('$AccampSQL') and (transferring_agent is null or transferring_agent!='$user')) or (group_id='AGENTDIRECT_CHAT' and user_direct='$user')) and chat_creator!='$user'";
				$chat_rslt=mysql_to_mysqli($chat_stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$chat_stmt,'03041',$user,$server_ip,$session_name,$one_mysql_log);}
				$chat_row=mysqli_fetch_row($chat_rslt);
				$WaitinGChats=$chat_row[0];
				# Chat alert priority: waiting chats = blink, then in-chat = on, then = off
				if ($WaitinGChats > 0) 
					{
					$WaitinGChats = "Y"; # Make CHAT button blink
					}
				else if ($Acomments=="CHAT")
					{
					$WaitinGChats = "C"; # in-chat, so make CHAT button display "ON";
					}
				else 
					{
					$WaitinGChats = "N"; # no chats waiting, not in chat, make CHAT display "OFF"
					}
				}
			if ($chat_email_row[1]==0) 
				{
				$WaitinGEmails="N";
				} 
			else 
				{
#				$email_stmt="select count(*) from vicidial_email_list, vicidial_xfer_log where vicidial_email_list.status='QUEUE' and vicidial_email_list.user='$user' and vicidial_xfer_log.xfercallid=vicidial_email_list.xfercallid and direction='INBOUND' and vicidial_xfer_log.campaign_id in ('$AccampSQL') and closer='EMAIL_XFER'";
				$email_stmt="select count(*) from vicidial_email_list, vicidial_xfer_log where vicidial_email_list.user!='$user' and vicidial_xfer_log.xfercallid=vicidial_email_list.xfercallid and direction='INBOUND' and vicidial_xfer_log.campaign_id in ('$AccampSQL') and closer='EMAIL_XFER'";
				$email_rslt=mysql_to_mysqli($email_stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$email_stmt,'03042',$user,$server_ip,$session_name,$one_mysql_log);}
				$email_row=mysqli_fetch_row($email_rslt);
				$WaitinGEmails=$email_row[0];
				if ($WaitinGEmails > 0) {$WaitinGEmails = "<font class=\"queue_text_red\">"._QXZ("Emails in Queue").": $WaitinGEmails</font>";}
				else {$WaitinGEmails = "<font class=\"queue_text\">"._QXZ("Emails in Queue").": $WaitinGEmails</font>";}
				}


			if ($auto_dial_level > 0)
				{
				### update the vicidial_live_agents every second with a new random number so it is shown to be alive
				$stmt="UPDATE vicidial_live_agents set random_id='$random' where user='$user' and server_ip='$server_ip';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03005',$user,$server_ip,$session_name,$one_mysql_log);}
				$retry_count=0;
				while ( ($errno > 0) and ($retry_count < 5) )
					{
					$rslt=mysql_to_mysqli($stmt, $link);
					$one_mysql_log=1;
					$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,"9305$retry_count",$user,$server_ip,$session_name,$one_mysql_log);
					$one_mysql_log=0;
					$retry_count++;
					}

				if ( ( ($Acomments != 'CHAT') and ($Acomments != 'EMAIL') ) or ($live_call_seconds > 4) )
					{
					##### BEGIN DEAD logging section #####
					if ( ( (strlen($customer_chat_id > 0) ) and ($customer_chat_id > 0) ) or ($Acomments == 'EMAIL') )
						{
						if ($Acomments == 'EMAIL')
							{$AcalleridCOUNT=1;}
						else
							{
							### find whether the call the agent is on is hung up
							$stmt="SELECT count(*) from vicidial_chat_log where chat_id='$customer_chat_id' and message LIKE \"%has left chat\";";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03044',$user,$server_ip,$session_name,$one_mysql_log);}
							$row=mysqli_fetch_row($rslt);
							$AchatendCOUNT=$row[0];
							$AcalleridCOUNT=1;
							if ($AchatendCOUNT > 0)
								{$AcalleridCOUNT=0;}
							}
						}
					else
						{
						### find whether the call the agent is on is hung up
						$stmt="SELECT count(*) from vicidial_auto_calls where callerid='$Acallerid';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03018',$user,$server_ip,$session_name,$one_mysql_log);}
						$row=mysqli_fetch_row($rslt);
						$AcalleridCOUNT=$row[0];
						}
					if ( ($AcalleridCOUNT > 0) and (preg_match("/INCALL/i",$Astatus)) and (preg_match("/^M/",$Acallerid)) )
						{
						$updateNOW_TIME = date("Y-m-d H:i:s");
						$stmt="UPDATE vicidial_auto_calls set last_update_time='$updateNOW_TIME' where callerid='$Acallerid';";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03038',$user,$server_ip,$session_name,$one_mysql_log);}
						}

					if ( ($AcalleridCOUNT < 1) and (preg_match("/INCALL/i",$Astatus)) and (strlen($Aagent_log_id) > 0) )
						{
						$DEADcustomer++;
						$DEADlog = "|   DEAD:$Acallerid|$Alead_id|$AcalleridCOUNT";
						### find whether the agent log record has already logged DEAD
						$stmt="SELECT count(*) from vicidial_agent_log where agent_log_id='$Aagent_log_id' and ( (dead_epoch IS NOT NULL) or (dead_epoch > 10000) );";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03019',$user,$server_ip,$session_name,$one_mysql_log);}
						$row=mysqli_fetch_row($rslt);
						$Aagent_log_idCOUNT=$row[0];
						
						if ($Aagent_log_idCOUNT < 1)
							{
							$NEWdead_epoch = date("U");
							$deadNOW_TIME = date("Y-m-d H:i:s");
							$stmt="UPDATE vicidial_agent_log set dead_epoch='$NEWdead_epoch' where agent_log_id='$Aagent_log_id';";
								if ($format=='debug') {echo "\n<!-- $stmt -->";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03020',$user,$server_ip,$session_name,$one_mysql_log);}

							$stmt="UPDATE vicidial_live_agents set last_state_change='$deadNOW_TIME' where agent_log_id='$Aagent_log_id';";
								if ($format=='debug') {echo "\n<!-- $stmt -->";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03021',$user,$server_ip,$session_name,$one_mysql_log);}
							}
						}
					}
				##### END DEAD logging section #####
				}
			else
				{
				### update the vicidial_live_agents every second with a new random number so it is shown to be alive
				$stmt="UPDATE vicidial_live_agents set random_id='$random' where user='$user' and server_ip='$server_ip';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03009',$user,$server_ip,$session_name,$one_mysql_log);}
				$retry_count=0;
				while ( ($errno > 0) and ($retry_count < 5) )
					{
					$rslt=mysql_to_mysqli($stmt, $link);
					$one_mysql_log=1;
					$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,"9309$retry_count",$user,$server_ip,$session_name,$one_mysql_log);
					$one_mysql_log=0;
					$retry_count++;
					}

				##### BEGIN DEAD logging section #####
				if ($Acomments != 'EMAIL')
					{
					### find whether the call the agent is on is hung up
					$stmt="SELECT count(*) from vicidial_auto_calls where callerid='$Acallerid';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03029',$user,$server_ip,$session_name,$one_mysql_log);}
					$row=mysqli_fetch_row($rslt);
					$AcalleridCOUNT=$row[0];

					if ( ($AcalleridCOUNT > 0) and (preg_match("/INCALL/i",$Astatus)) )
						{
						$updateNOW_TIME = date("Y-m-d H:i:s");
						$stmt="UPDATE vicidial_auto_calls set last_update_time='$updateNOW_TIME' where callerid='$Acallerid';";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03037',$user,$server_ip,$session_name,$one_mysql_log);}
						}

					if ( ($AcalleridCOUNT < 1) and (preg_match("/INCALL/i",$Astatus)) and (strlen($Aagent_log_id) > 0) )
						{
						$DEADcustomer++;
						$DEADlog = "|   DEAD:$Acallerid|$Alead_id|$AcalleridCOUNT";
						### find whether the agent log record has already logged DEAD
						$stmt="SELECT count(*) from vicidial_agent_log where agent_log_id='$Aagent_log_id' and ( (dead_epoch IS NOT NULL) or (dead_epoch > 10000) );";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03030',$user,$server_ip,$session_name,$one_mysql_log);}
						$row=mysqli_fetch_row($rslt);
						$Aagent_log_idCOUNT=$row[0];
						
						if ($Aagent_log_idCOUNT < 1)
							{
							$NEWdead_epoch = date("U");
							$deadNOW_TIME = date("Y-m-d H:i:s");
							$stmt="UPDATE vicidial_agent_log set dead_epoch='$NEWdead_epoch' where agent_log_id='$Aagent_log_id';";
								if ($format=='debug') {echo "\n<!-- $stmt -->";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03031',$user,$server_ip,$session_name,$one_mysql_log);}

							$stmt="UPDATE vicidial_live_agents set last_state_change='$deadNOW_TIME' where agent_log_id='$Aagent_log_id';";
								if ($format=='debug') {echo "\n<!-- $stmt -->";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03032',$user,$server_ip,$session_name,$one_mysql_log);}
							}
						}
					}
				##### END DEAD logging section #####
				}

			### grab the API hangup, API dispo and other Agent API fields in vicidial_live_agents
			$stmt="SELECT external_hangup,external_status,external_pause,external_dial,external_update_fields,external_update_fields_data,external_timer_action,external_timer_action_message,external_timer_action_seconds,external_dtmf,external_transferconf,external_park,external_timer_action_destination,external_recording,external_pause_code,external_lead_id from vicidial_live_agents where user='$user' and server_ip='$server_ip';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03010',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			$external_hangup =				$row[0];
			$external_status =				$row[1];
			$external_pause =				$row[2];
			$external_dial =				$row[3];
			$external_update_fields =		$row[4];
			$external_update_fields_data =	$row[5];
			$timer_action =					$row[6];
			$timer_action_message =			$row[7];
			$timer_action_seconds =			$row[8];
			$external_dtmf =				$row[9];
			$external_transferconf =		$row[10];
			$external_park =				$row[11];
			$timer_action_destination =		$row[12];
			$external_recording =			$row[13];
			$external_pause_code =			$row[14];
			$external_lead_id =				$row[15];

			$MDQ_count=0;
			if ( ($api_manual_dial=='QUEUE') or ($api_manual_dial=='QUEUE_AND_AUTOCALL') )
				{
				$stmt="SELECT count(*) FROM vicidial_manual_dial_queue where user='$user' and status='READY';";
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03033',$user,$server_ip,$session_name,$one_mysql_log);}
				if ($DB) {echo "$stmt\n";}
				$mdq_count_record_ct = mysqli_num_rows($rslt);
				if ($mdq_count_record_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$MDQ_count =			$row[0];
					}

				if ( ($MDQ_count > 0) and (strlen($external_dial) < 16) and ($Astatus=='PAUSED') and ($Alead_id < 1) )
					{
					$stmt="SELECT mdq_id,external_dial FROM vicidial_manual_dial_queue where user='$user' and status='READY' order by entry_time limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03034',$user,$server_ip,$session_name,$one_mysql_log);}
					if ($DB) {echo "$stmt\n";}
					$mdq_record_ct = mysqli_num_rows($rslt);
					if ($mdq_record_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$MDQ_mdq_id =			$row[0];
						$MDQ_external_dial =	$row[1];
						$external_dial = $MDQ_external_dial;

						$stmt="UPDATE vicidial_manual_dial_queue SET status='QUEUE' where mdq_id='$MDQ_mdq_id';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03035',$VD_login,$server_ip,$session_name,$one_mysql_log);}
						$UMDQaffected_rows_update = mysqli_affected_rows($link);

						if ($UMDQaffected_rows_update > 0)
							{
							$stmt="UPDATE vicidial_live_agents SET external_dial='$MDQ_external_dial' where user='$user' and server_ip='$server_ip';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03036',$VD_login,$server_ip,$session_name,$one_mysql_log);}
							$VLAMDQaffected_rows_update = mysqli_affected_rows($link);
							}
						}
					}
				}

			if (strlen($external_status)<1) {$external_status = '::::::::::';}

			$web_epoch = date("U");
			$stmt="SELECT UNIX_TIMESTAMP(last_update),UNIX_TIMESTAMP(db_time) from server_updater where server_ip='$server_ip';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03014',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			$server_epoch =	$row[0];
			$db_epoch =	$row[1];
			$time_diff = ($server_epoch - $db_epoch);
			$web_diff = ($db_epoch - $web_epoch);

			##### check for in-group change details
			$InGroupChangeDetails = '0|||';
			$manager_ingroup_set=0;
			$stmt="SELECT count(*) FROM vicidial_live_agents where user='$user' and manager_ingroup_set='SET';";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03022',$user,$server_ip,$session_name,$one_mysql_log);}
			if ($DB) {echo "$stmt\n";}
			$mis_record_ct = mysqli_num_rows($rslt);
			if ($mis_record_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$manager_ingroup_set =		$row[0];
				}
			if ($manager_ingroup_set > 0)
				{
				$stmt="UPDATE vicidial_live_agents SET closer_campaigns=external_ingroups, manager_ingroup_set='Y' where user='$user' and manager_ingroup_set='SET';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03023',$VD_login,$server_ip,$session_name,$one_mysql_log);}
				$VLAMISaffected_rows_update = mysqli_affected_rows($link);
				if ($VLAMISaffected_rows_update > 0)
					{
					$stmt="SELECT external_ingroups,external_blended,external_igb_set_user,outbound_autodial,dial_method FROM vicidial_live_agents vla, vicidial_campaigns vc where user='$user' and manager_ingroup_set='Y' and vla.campaign_id=vc.campaign_id;";
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03024',$user,$server_ip,$session_name,$one_mysql_log);}
					if ($DB) {echo "$stmt\n";}
					$migs_record_ct = mysqli_num_rows($rslt);
					if ($migs_record_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$external_ingroups =		$row[0];
						$external_blended =			$row[1];
						$external_igb_set_user =	$row[2];
						$outbound_autodial =		$row[3];
						$dial_method =				$row[4];

						$stmt="SELECT full_name FROM vicidial_users where user='$external_igb_set_user';";
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03025',$user,$server_ip,$session_name,$one_mysql_log);}
						if ($DB) {echo "$stmt\n";}
						$mign_record_ct = mysqli_num_rows($rslt);
						if ($mign_record_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$external_igb_set_name =		$row[0];
							}
						
						$NEWoutbound_autodial='N';
						if ( ($external_blended > 0) and ($dial_method != "INBOUND_MAN") and ($dial_method != "MANUAL") )
							{$NEWoutbound_autodial='Y';}

						$stmt="UPDATE vicidial_live_agents SET outbound_autodial='$NEWoutbound_autodial' where user='$user';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03026',$VD_login,$server_ip,$session_name,$one_mysql_log);}
						$VLAMIBaffected_rows_update = mysqli_affected_rows($link);

						$InGroupChangeDetails = "1|$external_blended|$external_igb_set_user|$external_igb_set_name";

						$stmt="INSERT INTO vicidial_user_closer_log set user='$user',campaign_id='$Acampaign_id',event_date='$NOW_TIME',blended='$external_blended',closer_campaigns='$external_ingroups',manager_change='$external_igb_set_user';";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03027',$user,$server_ip,$session_name,$one_mysql_log);}
						}
					}
				}

			##### grab the shift information the agent
			$stmt="SELECT user_group,agent_shift_enforcement_override from vicidial_users where user='$user';";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03015',$VD_login,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			$VU_user_group =						$row[0];
			$VU_agent_shift_enforcement_override =	$row[1];

			### Gather timeclock and shift enforcement restriction settings
			$stmt="SELECT shift_enforcement,group_shifts from vicidial_user_groups where user_group='$VU_user_group';";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03016',$VD_login,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			$shift_enforcement =		$row[0];
			$LOGgroup_shiftsSQL = preg_replace('/\s\s/','',$row[1]);
			$LOGgroup_shiftsSQL = preg_replace('/\s/',"','",$LOGgroup_shiftsSQL);
			$LOGgroup_shiftsSQL = "shift_id IN('$LOGgroup_shiftsSQL')";

			### CHECK TO SEE IF AGENT IS WITHIN THEIR SHIFT IF RESTRICTED, IF NOT, OUTPUT ERROR
			$Ashift_logout=0;
			if ( ( (preg_match("/ALL/",$shift_enforcement)) and (!preg_match("/OFF|START/",$VU_agent_shift_enforcement_override)) ) or (preg_match("/ALL/",$VU_agent_shift_enforcement_override)) )
				{
				$shift_ok=0;
				if (strlen($LOGgroup_shiftsSQL) < 3)
					{$Ashift_logout++;}
				else
					{
					$HHMM = date("Hi");
					$wday = date("w");

					$stmt="SELECT shift_id,shift_start_time,shift_length,shift_weekdays from vicidial_shifts where $LOGgroup_shiftsSQL order by shift_id";
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'3017',$user,$server_ip,$session_name,$one_mysql_log);}
					$shifts_to_print = mysqli_num_rows($rslt);

					$o=0;
					while ( ($shifts_to_print > $o) and ($shift_ok < 1) )
						{
						$rowx=mysqli_fetch_row($rslt);
						$shift_id =			$rowx[0];
						$shift_start_time =	$rowx[1];
						$shift_length =		$rowx[2];
						$shift_weekdays =	$rowx[3];

						if (preg_match("/$wday/i",$shift_weekdays))
							{
							$HHshift_length = substr($shift_length,0,2);
							$MMshift_length = substr($shift_length,3,2);
							$HHshift_start_time = substr($shift_start_time,0,2);
							$MMshift_start_time = substr($shift_start_time,2,2);
							$HHshift_end_time = ($HHshift_length + $HHshift_start_time);
							$MMshift_end_time = ($MMshift_length + $MMshift_start_time);
							if ($MMshift_end_time > 59)
								{
								$MMshift_end_time = ($MMshift_end_time - 60);
								$HHshift_end_time++;
								}
							if ($HHshift_end_time > 23)
								{$HHshift_end_time = ($HHshift_end_time - 24);}
							$HHshift_end_time = sprintf("%02s", $HHshift_end_time);	
							$MMshift_end_time = sprintf("%02s", $MMshift_end_time);	
							$shift_end_time = "$HHshift_end_time$MMshift_end_time";

							if ( 
								( ($HHMM >= $shift_start_time) and ($HHMM < $shift_end_time) ) or
								( ($HHMM < $shift_start_time) and ($HHMM < $shift_end_time) and ($shift_end_time <= $shift_start_time) ) or
								( ($HHMM >= $shift_start_time) and ($HHMM >= $shift_end_time) and ($shift_end_time <= $shift_start_time) )
							   )
								{$shift_ok++;}
							}
						$o++;
						}

					if ($shift_ok < 1)
						{$Ashift_logout++;}
					}
				}


			if ( ( ($time_diff > 8) or ($time_diff < -8) or ($web_diff > 8) or ($web_diff < -8) ) and (preg_match("/0\$/i",$StarTtime)) ) 
				{$Alogin='TIME_SYNC';}
			if ( ($Acount < 1) or ($Scount < 1) )
				{$Alogin='DEAD_VLA';}
			if ($AexternalDEAD > 0) 
				{$Alogin='DEAD_EXTERNAL';}
			if ($Ashift_logout > 0)
				{$Alogin='SHIFT_LOGOUT';}
			if ($external_pause == 'LOGOUT')
				{
				$Alogin='API_LOGOUT';
				$external_pause='';
				}

			//Check if xferchannel is active
			$DEADxfer=0;
			if ( (strlen($xferchannel) > 2) and ($DEADcustomer < 1) and (preg_match("/INCALL/i",$Astatus)) )
				{
				$stmt="SELECT count(*) FROM live_channels where channel ='$xferchannel' and server_ip='$server_ip';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03045',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				$live_channelCOUNT=$row[0];
				if ( ($live_channelCOUNT == 0) and (strlen($Alead_id) > 0) )
					{
					$DEADxfer++;
					$stmt="UPDATE user_call_log SET xfer_hungup='XFER_3WAYHangup', xfer_hungup_datetime=NOW() where lead_id='$Alead_id' and  user='$user' and call_type LIKE \"%3WAY%\" order by user_call_log_id desc limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03046',$user,$server_ip,$session_name,$one_mysql_log);}
					}
				}

			echo 'DateTime: ' . $NOW_TIME . '|UnixTime: ' . $StarTtime . '|Logged-in: ' . $Alogin . '|CampCalls: ' . $RingCalls . '|Status: ' . $Astatus . '|DiaLCalls: ' . $DiaLCalls . '|APIHanguP: ' . $external_hangup . '|APIStatuS: ' . $external_status . '|APIPausE: ' . $external_pause . '|APIDiaL: ' . $external_dial . '|DEADcall: ' . $DEADcustomer . '|InGroupChange: ' . $InGroupChangeDetails . '|APIFields: ' . $external_update_fields . '|APIFieldsData: ' . $external_update_fields_data . '|APITimerAction: ' . $timer_action . '|APITimerMessage: ' . $timer_action_message . '|APITimerSeconds: ' . $timer_action_seconds . '|APIdtmf: ' . $external_dtmf . '|APItransferconf: ' . $external_transferconf . '|APIpark: ' . $external_park . '|APITimerDestination: ' . $timer_action_destination . '|APIManualDialQueue: ' . $MDQ_count . '|APIRecording: ' . $external_recording . '|APIPaUseCodE: ' . $external_pause_code . '|WaitinGChats: ' . $WaitinGChats . '|WaitinGEmails: ' . $WaitinGEmails . '|LivEAgentCommentS: ' . $live_agents_comments . '|LeadIDSwitch: ' . $external_lead_id .'|DEADxfer: '.$DEADxfer. "\n";

			if (strlen($timer_action) > 3)
				{
				$stmt="UPDATE vicidial_live_agents SET external_timer_action='' where user='$user';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03028',$VD_login,$server_ip,$session_name,$one_mysql_log);}
				$VLAETAaffected_rows_update = mysqli_affected_rows($link);
				}
			}
		$total_conf=0;
		$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and extension = '$conf_exten';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03011',$user,$server_ip,$session_name,$one_mysql_log);}
		if ($rslt) {$sip_list = mysqli_num_rows($rslt);}
	#	echo "$sip_list|";
		$loop_count=0;
			while ($sip_list>$loop_count)
			{
			$loop_count++; $total_conf++;
			$row=mysqli_fetch_row($rslt);
			$ChannelA[$total_conf] = "$row[0]";
			if ($format=='debug') {echo "\n<!-- $row[0] -->";}
			}
		$stmt="SELECT channel FROM live_channels where server_ip = '$server_ip' and extension = '$conf_exten';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03012',$user,$server_ip,$session_name,$one_mysql_log);}
		if ($rslt) {$channels_list = mysqli_num_rows($rslt);}
	#	echo "$channels_list|";
		$loop_count=0;
		while ($channels_list>$loop_count)
			{
			$loop_count++; $total_conf++;
			$row=mysqli_fetch_row($rslt);
			$ChannelA[$total_conf] = "$row[0]";
			if ($format=='debug') {echo "\n<!-- $row[0] -->";}
			}
		}
	$channels_list = ($channels_list + $sip_list);
	echo "$channels_list|";

	$counter=0;
	$countecho='';
	while($total_conf > $counter)
		{
		$counter++;
		$countecho = "$countecho$ChannelA[$counter] ~";
	#	echo "$ChannelA[$counter] ~";
		}

	echo "$countecho\n";
	$stage = "$Astatus|$Aagent_log_id$DEADlog";
	}


################################################################################
### register - registers a conference to a phone
################################################################################
if ($ACTION == 'register')
	{
	$MT[0]='';
	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($conf_exten)<1) || (strlen($exten)<1) )
		{
		$channel_live=0;
		echo _QXZ("Conf Exten %1s is not valid or Exten %2s is not valid",0,'',$conf_exten,$exten)."\n";
		exit;
		}
	else
		{
		$stmt="UPDATE conferences set extension='$exten' where server_ip = '$server_ip' and conf_exten = '$conf_exten';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03013',$user,$server_ip,$session_name,$one_mysql_log);}
		}
	echo _QXZ("Conference %1s has been registered to %2s",0,'',$conf_exten,$exten)."\n";
	}



################################################################################
### DEBUG OUTPUT AND LOGGING
################################################################################
if ($format=='debug') 
	{
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $StarTtime);
	echo "\n<!-- script runtime: $RUNtime seconds -->";
	echo "\n</body>\n</html>\n";
	}

if ($SSagent_debug_logging > 0) 
	{
	vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);
	
	### log the clicks that are sent from the agent screen
	if (strlen($clicks) > 1)
		{
		$cd=0;
		$clicks = preg_replace("/\|$/",'',$clicks);
		$clicks_details = explode('|',$clicks);
		$clicks_details_ct = count($clicks_details);
		while($cd < $clicks_details_ct)
			{
			$click_data = explode('-----',$clicks_details[$cd]);
			$click_time = $click_data[0];
			$click_function_data = explode('---',$click_data[1]);
			$click_function = $click_function_data[0];
			$click_options = $click_function_data[1];

			$stmtA="INSERT INTO vicidial_ajax_log set user='$user',start_time='$click_time',db_time=NOW(),run_time='0',php_script='vicidial.php',action='$click_function',lead_id='$lead_id',stage='$cd|$click_options',session_name='$session_name',last_sql='';";
			$rslt=mysql_to_mysqli($stmtA, $link);

			$cd++;
			}
		}
	}
exit; 

?>
