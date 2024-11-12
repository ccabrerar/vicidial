<?php
# conf_exten_check.php    version 2.14
#
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
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
# 190730-0927 - Added campaign SIP Actions processing
# 190925-1348 - Added logtable SIP Action
# 191013-2105 - Fixes for PHP7
# 200825-2343 - Added option for manual-only sip actions
# 201111-2140 - Fix for AGENTDIRECT selected in-groups issue #1241
# 210317-1935 - Added visibility logging
# 210328-1013 - Fix for emails-in-queue count query, Issue #1170
# 210425-2357 - Added calls_inqueue_count_ calculation
# 210616-1905 - Added optional CORS support, see options.php for details
# 210825-0907 - Fix for XSS security issue
# 220219-2328 - Added allow_web_debug system setting
# 220310-0934 - Added more time-sync detailed logging
# 230220-1759 - Fix for In-Group manual dial issue
# 230412-1020 - Added code for send_notification API function
# 230420-2020 - Added latency logging
# 230616-1810 - Added dead_count checking and 1-second delay in DEAD call logging and dead call log reversal
#

$version = '2.14-70';
$build = '230616-1810';
$php_script = 'conf_exten_check.php';
$mel=1;					# Mysql Error Log enabled = 1
$mysql_log_count=51;
$one_mysql_log=0;
$DB=0;
$VD_login=0;
$SSagent_debug_logging=0;
$dead_logging_version=1;
$startMS = microtime();
$ip = getenv("REMOTE_ADDR");

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
if (isset($_GET["check_for_answer"]))			{$check_for_answer=$_GET["check_for_answer"];}
	elseif (isset($_POST["check_for_answer"]))	{$check_for_answer=$_POST["check_for_answer"];}
if (isset($_GET["MDnextCID"]))				{$MDnextCID=$_GET["MDnextCID"];}
	elseif (isset($_POST["MDnextCID"]))		{$MDnextCID=$_POST["MDnextCID"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["phone_number"]))			{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["visibility"]))				{$visibility=$_GET["visibility"];}
	elseif (isset($_POST["visibility"]))	{$visibility=$_POST["visibility"];}
if (isset($_GET["active_ingroup_dial"]))			{$active_ingroup_dial=$_GET["active_ingroup_dial"];}
	elseif (isset($_POST["active_ingroup_dial"]))	{$active_ingroup_dial=$_POST["active_ingroup_dial"];}
if (isset($_GET["latency"]))			{$latency=$_GET["latency"];}
	elseif (isset($_POST["latency"]))	{$latency=$_POST["latency"];}
if (isset($_GET["dead_count"]))				{$dead_count=$_GET["dead_count"];}
	elseif (isset($_POST["dead_count"]))	{$dead_count=$_POST["dead_count"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

# default optional vars if not set
if (!isset($format))   {$format="text";}
if (!isset($ACTION))   {$ACTION="refresh";}
if (!isset($client))   {$client="agc";}
if ($bcrypt == 'OFF')  {$bcrypt=0;}

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);


#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,agent_debug_logging,allow_web_debug,agent_notifications FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03001',$user,$server_ip,$session_name,$one_mysql_log);}
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$SSagent_debug_logging =	$row[3];
	$SSallow_web_debug =		$row[4];
	$SSagent_notifications =	$row[5];
	}
if ($SSallow_web_debug < 1) {$DB=0;   $format='text';}

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
##### END SETTINGS LOOKUP #####
###########################################

$session_name = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$session_name);
$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$server_ip);
$conf_exten = preg_replace("/[^-_0-9a-zA-Z]/","",$conf_exten);
$exten = preg_replace("/\'|\"|\\\\|;/","",$exten);
$clicks = preg_replace("/\'|\"|\\\\|;/","",$clicks);
$customer_chat_id = preg_replace("/[^0-9a-zA-Z]/","",$customer_chat_id);
$visibility = preg_replace("/\'|\"|\\\\|;/","",$visibility);
$MDnextCID = preg_replace("/[^-_0-9a-zA-Z]/","",$MDnextCID);
$live_call_seconds = preg_replace("/[^-_0-9a-zA-Z]/","",$live_call_seconds);
$bcrypt = preg_replace("/[^-_0-9a-zA-Z]/","",$bcrypt);
$format = preg_replace("/[^-_0-9a-zA-Z]/","",$format);
$ACTION = preg_replace("/[^-_0-9a-zA-Z]/","",$ACTION);
$auto_dial_level = preg_replace("/[^-\._0-9a-zA-Z]/","",$auto_dial_level);
$check_for_answer = preg_replace("/[^-_0-9a-zA-Z]/","",$check_for_answer);
$client = preg_replace("/[^-_0-9a-zA-Z]/","",$client);
$campagentstdisp = preg_replace("/[^-_0-9a-zA-Z]/","",$campagentstdisp);
$phone_number = preg_replace("/[^-_0-9a-zA-Z]/","",$phone_number);
$xferchannel = preg_replace("/\'|\"|\\\\|;/","",$xferchannel);
$latency = preg_replace("/[^-_0-9a-zA-Z]/","",$latency);
$dead_count = preg_replace("/[^-_0-9a-zA-Z]/","",$dead_count);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$campaign = preg_replace("/[^-_0-9a-zA-Z]/","",$campaign);
	$active_ingroup_dial = preg_replace("/[^-_0-9a-zA-Z]/","",$active_ingroup_dial);
	}
	else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$campaign = preg_replace('/[^-_0-9\p{L}]/u', '', $campaign);
	$active_ingroup_dial = preg_replace('/[^-_0-9\p{L}]/u',"",$active_ingroup_dial);
	}

if (strlen($SSagent_debug_logging) > 1)
	{
	if ($SSagent_debug_logging == "$user") {$SSagent_debug_logging=1;}
	else {$SSagent_debug_logging=0;}
	}

$Alogin='N';
$Alogin_notes='';
$RingCalls='N';
$DiaLCalls='N';

$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_TIME = date("Ymd_His");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
$random = (rand(1000000, 9999999) + 10000000);


$auth=0;
$auth_message = user_authorization($user,$pass,'',0,$bcrypt,0,0,'conf_exten_check');
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
	if ($DB > 0)
		{
		echo "|session_name: $session_name|server_ip: $server_ip|conf_exten: $conf_exten|exten: $exten|clicks: $clicks|customer_chat_id: $customer_chat_id|visibility: $visibility|MDnextCID: $MDnextCID|live_call_seconds: $live_call_seconds|bcrypt: $bcrypt|format: $format|ACTION: $ACTION|auto_dial_level: $auto_dial_level|check_for_answer: $check_for_answer|client: $client|campagentstdisp: $campagentstdisp|phone_number: $phone_number|xferchannel: $xferchannel\n";
	}
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
				}

			$api_manual_dial='STANDARD';
			$calls_inqueue_count_one='';
			$calls_inqueue_count_two='';
			$stmt = "SELECT api_manual_dial,calls_inqueue_count_one,calls_inqueue_count_two FROM vicidial_campaigns where campaign_id='$Acampaign_id';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vcc_conf_ct = mysqli_num_rows($rslt);
			if ($vcc_conf_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$api_manual_dial =			$row[0];
				$calls_inqueue_count_one =	$row[1];
				$calls_inqueue_count_two =	$row[2];
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
					$ADsql = "or ( (campaign_id IN('$AccampSQL')) and (agent_only='$user') )";
					$AccampSQL = preg_replace('/AGENTDIRECT/i','', $AccampSQL);
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

				### BEGIN check for calls_inqueue_count_ settings containers and calculate calls in queue ###
				if ( ( ($calls_inqueue_count_one != '') and ($calls_inqueue_count_one != 'DISABLED') ) or ( ($calls_inqueue_count_two != '') and ($calls_inqueue_count_two != 'DISABLED') ) )
					{
					$RingCallsOne='';
					$RingCallsTwo='';
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
									if ($calls_inqueue_count_one_settings[$sea] == '--ALL-CALLS--')
										{
										if ($RingCalls > 0) {$calls_inqueue_count_one_output = "<font class=\"queue_text_red\">$calls_inqueue_count_one_heading: $RingCalls</font>";}
										else {$calls_inqueue_count_one_output = "<font class=\"queue_text\">$calls_inqueue_count_one_heading: $RingCalls</font>";}
										}
									else
										{
										$cic_one_stmt='';
										if (preg_match("/^--ALL-IN-GROUP-CALLS-/",$calls_inqueue_count_one_settings[$sea]))
											{
											$cic_one_stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id IN('$AccampSQL')) $ADsql)";
											}
										if (preg_match("/^--ALL-CAMPAIGN-CALLS-/",$calls_inqueue_count_one_settings[$sea]))
											{
											$cic_one_stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and (campaign_id='$Acampaign')";
											}
										if (preg_match("/^--ALL-CALLS-/",$calls_inqueue_count_one_settings[$sea]))
											{
											$cic_one_stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) $ADsql)";
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
							$cic_one_stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id IN($calls_inqueue_count_one_groups_SQL)) $ADsql)";
							}
						if (strlen($cic_one_stmt) > 10)
							{
							$cic_one_stmt .= " $cic_excpt_stmt;";
							if ($DB) {echo "CIC ONE|$cic_one_stmt|\n";}
							$rslt=mysql_to_mysqli($cic_one_stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$cic_one_stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}
							$row=mysqli_fetch_row($rslt);
							$RingCallsOne=$row[0];
							if ($RingCallsOne > 0) {$RingCallsOne = "<font class=\"queue_text_red\">$calls_inqueue_count_one_heading: $RingCallsOne</font>";}
							else {$RingCallsOne = "<font class=\"queue_text\">$calls_inqueue_count_one_heading: $RingCallsOne</font>";}
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
									if ($calls_inqueue_count_two_settings[$sea] == '--ALL-CALLS--')
										{
										if ($RingCalls > 0) {$calls_inqueue_count_two_output = "<font class=\"queue_text_red\">$calls_inqueue_count_two_heading: $RingCalls</font>";}
										else {$calls_inqueue_count_two_output = "<font class=\"queue_text\">$calls_inqueue_count_two_heading: $RingCalls</font>";}
										}
									else
										{
										$cic_two_stmt='';
										if (preg_match("/^--ALL-IN-GROUP-CALLS-/",$calls_inqueue_count_two_settings[$sea]))
											{
											$cic_two_stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id IN('$AccampSQL')) $ADsql)";
											}
										if (preg_match("/^--ALL-CAMPAIGN-CALLS-/",$calls_inqueue_count_two_settings[$sea]))
											{
											$cic_two_stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and (campaign_id='$Acampaign')";
											}
										if (preg_match("/^--ALL-CALLS-/",$calls_inqueue_count_two_settings[$sea]))
											{
											$cic_two_stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) $ADsql)";
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
							$cic_two_stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id IN($calls_inqueue_count_two_groups_SQL)) $ADsql)";
							}
						if (strlen($cic_two_stmt) > 10)
							{
							$cic_two_stmt .= " $cic_excpt_stmt;";
							if ($DB) {echo "CIC TWO|$cic_two_stmt|\n";}
							$rslt=mysql_to_mysqli($cic_two_stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$cic_two_stmt,'03XXX',$user,$server_ip,$session_name,$two_mysql_log);}
							$row=mysqli_fetch_row($rslt);
							$RingCallsTwo=$row[0];
							if ($RingCallsTwo > 0) {$RingCallsTwo = "<font class=\"queue_text_red\">$calls_inqueue_count_two_heading: $RingCallsTwo</font>";}
							else {$RingCallsTwo = "<font class=\"queue_text\">$calls_inqueue_count_two_heading: $RingCallsTwo</font>";}
							}
						}
					if ( (strlen($RingCallsOne) > 10) or (strlen($RingCallsTwo) > 10) )
						{
						if ( (strlen($RingCallsOne) > 10) and (strlen($RingCallsTwo) > 10) )
							{$RingCalls = "$RingCallsOne &nbsp; &nbsp; $RingCallsTwo";}
						else
							{$RingCalls = "$RingCallsOne$RingCallsTwo";}
						}
					}
				### END check for calls_inqueue_count_ settings containers and calculate calls in queue ###
				}
			else
				{
				$Alogin='N';
				$Alogin_notes='';
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
				$email_stmt="select count(*) from vicidial_email_list, vicidial_xfer_log where vicidial_email_list.user!='$user' and NOT ISNULL(vicidial_email_list.xfercallid) and vicidial_xfer_log.xfercallid=vicidial_email_list.xfercallid and direction='INBOUND' and vicidial_xfer_log.campaign_id in ('$AccampSQL') and closer='EMAIL_XFER';";
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

				### update the vicidial_live_agents_details record
				$stmt="UPDATE vicidial_live_agents_details set latency='$latency',web_ip='$ip',update_date=NOW() where user='$user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}

				### insert a vicidial_agent_latency_log record
				$stmt="INSERT INTO vicidial_agent_latency_log SET latency='$latency',web_ip='$ip',user='$user',log_date=NOW();";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}

				if ( ( ($Acomments != 'CHAT') and ($Acomments != 'EMAIL') and (strlen($active_ingroup_dial) < 1) ) or ($live_call_seconds > 4) )
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
						if ($dead_count > 0)
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
								$tempACTION = 'dead_log';
								$TEMPstage = "DEAD call logged A|$dead_count|";
								vicidial_ajax_log($NOW_TIME,$startMS,$link,$tempACTION,$php_script,$user,$TEMPstage,$lead_id,$session_name,$stmt);
							}
						}
						else
							{
							$tempACTION = 'dead_log';
							$TEMPstage = "DEAD call first detect A|$dead_count|";
							vicidial_ajax_log($NOW_TIME,$startMS,$link,$tempACTION,$php_script,$user,$TEMPstage,$lead_id,$session_name,$stmt);
					}
						$dead_count++;
						}
					else
						{
						if ($dead_count > 0)
							{
							$unDEADaffected_rows=0;
							### find whether the agent log record has already logged DEAD
							$stmt="SELECT count(*) from vicidial_agent_log where agent_log_id='$Aagent_log_id' and ( (dead_epoch IS NOT NULL) or (dead_epoch > 10000) ) and (dispo_epoch IS NULL);";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03030',$user,$server_ip,$session_name,$one_mysql_log);}
							$row=mysqli_fetch_row($rslt);
							$Aagent_log_idCOUNT=$row[0];

							### if dead logged already on this call, but call is no longer dead, reverse thee dead logging
							if ( ($Aagent_log_idCOUNT  > 0) and ($dead_logging_version >= 2) )
								{
								$undeadNOW_TIME = date("Y-m-d H:i:s");
								$stmt="UPDATE vicidial_agent_log set dead_epoch=NULL where agent_log_id='$Aagent_log_id';";
									if ($format=='debug') {echo "\n<!-- $stmt -->";}
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}
								$unDEADaffected_rows = mysqli_affected_rows($link);

								$stmt="UPDATE vicidial_live_agents set last_state_change='$undeadNOW_TIME' where agent_log_id='$Aagent_log_id';";
									if ($format=='debug') {echo "\n<!-- $stmt -->";}
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}
								}
							$tempACTION = 'dead_log';
							$TEMPstage = "UNDEAD call reversal A: |$Aagent_log_idCOUNT|$dead_count|$unDEADaffected_rows|$dead_logging_version|";
							vicidial_ajax_log($NOW_TIME,$startMS,$link,$tempACTION,$php_script,$user,$TEMPstage,$lead_id,$session_name,$stmt);
							$dead_count=0;
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

				### update the vicidial_live_agents_details record
				$stmt="UPDATE vicidial_live_agents_details set latency='$latency',web_ip='$ip',update_date=NOW() where user='$user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}

				### insert a vicidial_agent_latency_log record
				$stmt="INSERT INTO vicidial_agent_latency_log SET latency='$latency',web_ip='$ip',user='$user',log_date=NOW();";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}

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
						if ($dead_count > 0)
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
								$tempACTION = 'dead_log';
								$TEMPstage = "DEAD call logged B|$dead_count|";
								vicidial_ajax_log($NOW_TIME,$startMS,$link,$tempACTION,$php_script,$user,$TEMPstage,$lead_id,$session_name,$stmt);
							}
						}
						else
							{
							$tempACTION = 'dead_log';
							$TEMPstage = "DEAD call first detect B|$dead_count|";
							vicidial_ajax_log($NOW_TIME,$startMS,$link,$tempACTION,$php_script,$user,$TEMPstage,$lead_id,$session_name,$stmt);
					}
						$dead_count++;
						}
					else
						{
						if ($dead_count > 0)
							{
							$unDEADaffected_rows=0;
							### find whether the agent log record has already logged DEAD
							$stmt="SELECT count(*) from vicidial_agent_log where agent_log_id='$Aagent_log_id' and ( (dead_epoch IS NOT NULL) or (dead_epoch > 10000) ) and (dispo_epoch IS NULL);";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03030',$user,$server_ip,$session_name,$one_mysql_log);}
							$row=mysqli_fetch_row($rslt);
							$Aagent_log_idCOUNT=$row[0];

							### if dead logged already on this call, but call is no longer dead, reverse thee dead logging
							if ( ($Aagent_log_idCOUNT  > 0) and ($dead_logging_version >= 2) )
								{
								$undeadNOW_TIME = date("Y-m-d H:i:s");
								$stmt="UPDATE vicidial_agent_log set dead_epoch=NULL where agent_log_id='$Aagent_log_id';";
									if ($format=='debug') {echo "\n<!-- $stmt -->";}
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}
								$unDEADaffected_rows = mysqli_affected_rows($link);

								$stmt="UPDATE vicidial_live_agents set last_state_change='$undeadNOW_TIME' where agent_log_id='$Aagent_log_id';";
									if ($format=='debug') {echo "\n<!-- $stmt -->";}
								$rslt=mysql_to_mysqli($stmt, $link);
									if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03XXX',$user,$server_ip,$session_name,$one_mysql_log);}
								}
							$tempACTION = 'dead_log';
							$TEMPstage = "UNDEAD call reversal B: |$Aagent_log_idCOUNT|$dead_count|$unDEADaffected_rows|$dead_logging_version|";
							vicidial_ajax_log($NOW_TIME,$startMS,$link,$tempACTION,$php_script,$user,$TEMPstage,$lead_id,$session_name,$stmt);
							$dead_count=0;
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
				{$Alogin='TIME_SYNC';		$Alogin_notes="SERVER-DB-DIFF-- $time_diff = ($server_epoch - $db_epoch) DB-WEB-DIFF-- $web_diff = ($db_epoch - $web_epoch)";}
			if ( ($Acount < 1) or ($Scount < 1) )
				{$Alogin='DEAD_VLA';		$Alogin_notes="$Scount";}
			if ($AexternalDEAD > 0)
				{$Alogin='DEAD_EXTERNAL';	$Alogin_notes="$AexternalDEAD";}
			if ($Ashift_logout > 0)
				{$Alogin='SHIFT_LOGOUT';	$Alogin_notes="$Ashift_logout";}
			if ($external_pause == 'LOGOUT')
				{
				$Alogin='API_LOGOUT';		$Alogin_notes="$external_pause";
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

			//Check for call answer
			$CHANanswer='0-----';
			if ( ($check_for_answer > 0) and (strlen($MDnextCID) > 18) )
				{
				$dial_time = 0;
				$sip_event_action_output='';
				$stmt = "SELECT invite_date,UNIX_TIMESTAMP(first_180_date),UNIX_TIMESTAMP(first_183_date),UNIX_TIMESTAMP(200_date),TIMESTAMPDIFF(MICROSECOND,invite_date,200_date) as dial,TIMESTAMPDIFF(MICROSECOND,invite_date,first_180_date) as prog,TIMESTAMPDIFF(MICROSECOND,invite_date,first_183_date) as pdd from vicidial_sip_event_recent where caller_code='$MDnextCID' LIMIT 1;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03047',$user,$server_ip,$session_name,$one_mysql_log);}
				$VSER_ct = mysqli_num_rows($rslt);
				if ($VSER_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$invite_date = 		$row[0];
					$first_180_date = 	$row[1];
					$first_183_date = 	$row[2];
					$sip200_date = 		$row[3];
					$dial_time = 		$row[4];
					$time_to_progress = $row[5];
					$time_to_ring = 	$row[6];
					if ( ($first_180_date > 0) and ($first_180_date != 'NULL') and ($first_183_date > 0) and ($first_183_date != 'NULL'))
						{if ($first_180_date > $first_183_date) {$time_to_progress=$time_to_ring;}}

					if ( ($dial_time > 0) and ($dial_time != 'NULL') )
						{
						if ( ($time_to_progress > 0) and ($time_to_progress != 'NULL') )
							{
							if ( ($dial_time <= 0) or ($dial_time == 'NULL') )
								{$dial_time = $time_to_progress;}
							$invite_to_ring = $time_to_progress;
							$ring_to_final = ($dial_time - $invite_to_ring);
							}
						else
							{
							if ( ($time_to_ring > 0) and ($time_to_ring != 'NULL') )
								{
								if ( ($dial_time <= 0) or ($dial_time == 'NULL') )
									{$dial_time = $time_to_ring;}
								$invite_to_ring = $time_to_ring;
								$ring_to_final = ($dial_time - $invite_to_ring);
								}
							else
								{
								$invite_to_ring = 0;
								$ring_to_final = 0;
								}
							}

						if ($invite_to_ring != '0') {$invite_to_ring = ($invite_to_ring / 1000000);}
						if ($ring_to_final != '0') {$ring_to_final = ($ring_to_final / 1000000);}
						if ($dial_time != '0') {$dial_time = ($dial_time / 1000000);}

						# insert a record into the vicidial_log_extended_sip table for this call
						$stmt = "INSERT INTO vicidial_log_extended_sip SET call_date='$invite_date', caller_code='$MDnextCID', invite_to_ring='$invite_to_ring', ring_to_final='$ring_to_final', invite_to_final='$dial_time', last_event_code='200';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03048',$user,$server_ip,$session_name,$one_mysql_log);}
						$affected_rowsX = mysqli_affected_rows($link);

						# flag the vicidial_sip_event_recent record as processed
						$stmt = "UPDATE vicidial_sip_event_recent set processed='Y' where caller_code='$MDnextCID' LIMIT 1;";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03049',$user,$server_ip,$session_name,$one_mysql_log);}
						$affected_rowsX = mysqli_affected_rows($link);


						### BEGIN check for SIP event log actions ###
						$CAMPsip_event_logging='DISABLED';
						$invite_to_final='';
						$stmt = "SELECT sip_event_logging FROM vicidial_campaigns where campaign_id='$campaign';";
						$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03050',$user,$server_ip,$session_name,$one_mysql_log);}
						if ($DB) {echo "$stmt\n";}
						$csel_ct = mysqli_num_rows($rslt);
						if ($csel_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$CAMPsip_event_logging = 	$row[0];
							}

						if ( (strlen($CAMPsip_event_logging) > 0) and ($CAMPsip_event_logging != 'DISABLED') )
							{
							# gather Sip event settings container
							$stmt = "SELECT container_entry FROM vicidial_settings_containers where container_id='$CAMPsip_event_logging';";
							$rslt=mysql_to_mysqli($stmt, $link);
								if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03051',$user,$server_ip,$session_name,$one_mysql_log);}
							if ($DB) {echo "$stmt\n";}
							$SCinfo_ct = mysqli_num_rows($rslt);
							if ($SCinfo_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$SAcontainer_entry =	$row[0];
								$SAcontainer_entry = preg_replace("/\r|\t|\'|\"/",'',$SAcontainer_entry);
								$sip_action_settings = explode("\n",$SAcontainer_entry);
								$sip_action_settings_ct = count($sip_action_settings);
								$sea=0;
								while ($sip_action_settings_ct >= $sea)
									{
									if ( (preg_match("/^invite_to_final => /",$sip_action_settings[$sea])) and (!preg_match("/auto-only/i",$sip_action_settings[$sea])) )
										{
										# invite_to_final => 0.0,1.0,hangup-dispo-message,FAS,Auto Hangup and Dispo of False Answer Call
										$sip_action_settings[$sea] = preg_replace("/invite_to_final => /",'',$sip_action_settings[$sea]);
										$invite_to_finalARY = explode(",",$sip_action_settings[$sea]);
										$T_dial_time =	floatval($dial_time);
										$itf_begin =	floatval($invite_to_finalARY[0]);
										$itf_end =		floatval($invite_to_finalARY[1]);
										$itf_actions =	$invite_to_finalARY[2];
										$itf_dispo =	$invite_to_finalARY[3];
										$itf_message =	$invite_to_finalARY[4];
										if ( ($T_dial_time >= $itf_begin) and ($T_dial_time <= $itf_end) and (strlen($itf_actions) > 4) )
											{
											if (preg_match("/logtable/i",$itf_actions))
												{
												##### insert record into vicidial_sip_action_log
												$stmt="INSERT INTO vicidial_sip_action_log set call_date='$invite_date',caller_code='$MDnextCID',lead_id=$lead_id,phone_number='$phone_number',user='$user',result='$itf_dispo';";
												if ($DB) {echo "$stmt\n";}
												$rslt=mysql_to_mysqli($stmt, $link);
													if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'03052',$user,$server_ip,$session_name,$one_mysql_log);}
												$affected_rowsX = mysqli_affected_rows($link);
												}
											if (preg_match("/hangup|dispo|message/i",$itf_actions))
												{
											#	$call_output = "$uniqueid\n$channel\nERROR\n" . $hangup_cause_msg . "\n<br>" . $sip_hangup_cause_msg;
												$sip_event_action_output = "SIP ACTION-----" . $itf_actions . "-----" . $itf_dispo . "-----" . $itf_message;
												}
											}
										}
									$sea++;
									}
								}
							}
						### END check for SIP event log actions ###
						$CHANanswer = "1-----" . $sip_event_action_output;

							# SIP event debug logging
						#	$fp = fopen ("./SELdebug_log.txt", "a");
						#	fwrite ($fp, "$NOW_TIME SEL-CCC-Debug 1, chan-check: $check_for_answer|$MDnextCID|$invite_date|$sip200_date|$dial_time|$time_to_progress|$time_to_ring|\n");
						#	fclose($fp);
						}
					}
				}

			if ($SSagent_notifications > 0)
				{
				// Check for alerts
				// Activate notifications that are ready to be sent systemwide
				$upd_stmt="update vicidial_agent_notifications set notification_status='READY' where notification_date<=now() and notification_status='QUEUED';";
				$upd_rslt=mysql_to_mysqli($upd_stmt, $link);

				# gather all READY notifications to be triggered, limit with $user, $VU_user_group, $campaign
				$alert_stmt="select * from vicidial_agent_notifications where notification_status='READY' and ( (recipient_type='USER' and recipient='$user') or (recipient_type='USER_GROUP' and recipient='$VU_user_group') or (recipient_type='CAMPAIGN' and recipient='$campaign') ) order by notification_date asc limit 1;";
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
						if ($recipient_type=="CAMPAIGN") {$column="campaign_id"; $recipient_str="$recipient";}
						else if ($recipient_type=="USER_GROUP") 
							{
							$column="user";
							$ug_stmt="select user from vicidial_users where user_group='$recipient'";
							$ug_rslt=mysql_to_mysqli($ug_stmt, $link);
							$recipient_str="";
							while ($ug_row=mysqli_fetch_row($ug_rslt))
								{
								$recipient_str.="$ug_row[0]', '";
								}
							}
						else {$column="user"; $recipient_str="$recipient";}
						
						$agent_stmt="select user from vicidial_live_agents where $column in ('$recipient_str')";
						$agent_rslt=mysql_to_mysqli($agent_stmt, $link);
						while($agent_row=mysqli_fetch_row($agent_rslt))
							{
							$ins_stmt="INSERT INTO vicidial_agent_notifications_queue(notification_id, user) VALUES('$notification_id', '$agent_row[0]')";
							$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
							}
						}
					}
				}



			echo 'DateTime: ' . $NOW_TIME . '|UnixTime: ' . $StarTtime . '|Logged-in: ' . $Alogin . '|CampCalls: ' . $RingCalls . '|Status: ' . $Astatus . '|DiaLCalls: ' . $DiaLCalls . '|APIHanguP: ' . $external_hangup . '|APIStatuS: ' . $external_status . '|APIPausE: ' . $external_pause . '|APIDiaL: ' . $external_dial . '|DEADcall: ' . $DEADcustomer . ',' . $dead_count . '|InGroupChange: ' . $InGroupChangeDetails . '|APIFields: ' . $external_update_fields . '|APIFieldsData: ' . $external_update_fields_data . '|APITimerAction: ' . $timer_action . '|APITimerMessage: ' . $timer_action_message . '|APITimerSeconds: ' . $timer_action_seconds . '|APIdtmf: ' . $external_dtmf . '|APItransferconf: ' . $external_transferconf . '|APIpark: ' . $external_park . '|APITimerDestination: ' . $timer_action_destination . '|APIManualDialQueue: ' . $MDQ_count . '|APIRecording: ' . $external_recording . '|APIPaUseCodE: ' . $external_pause_code . '|WaitinGChats: ' . $WaitinGChats . '|WaitinGEmails: ' . $WaitinGEmails . '|LivEAgentCommentS: ' . $live_agents_comments . '|LeadIDSwitch: ' . $external_lead_id .'|DEADxfer: '.$DEADxfer .'|CHANanswer: '.$CHANanswer .'|Alogin_notes: '.$Alogin_notes. "\n";

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
		$ChannelA = array();
		$loop_count=0;
		while ($sip_list > $loop_count)
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
		while ($channels_list > $loop_count)
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
	while ($total_conf > $counter)
		{
		$counter++;
		$countecho = "$countecho$ChannelA[$counter] ~";
	#	echo "$ChannelA[$counter] ~";
		}

	echo "$countecho\n";
	$stage = "$Astatus|$Aagent_log_id|".$latency."ms|$DEADlog";

	}

################################################################################
### AlertDisplay - send alerts to agents
################################################################################
if ($ACTION == 'AlertDisplay')
	{
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
			echo "$acr_rows|$notif_row[notification_text]|$notif_row[text_size]|$notif_row[text_font]|$notif_row[text_color]|$notif_row[text_weight]|$notif_row[show_confetti]|$notif_row[confetti_options]";
			}

		$del_stmt="delete from vicidial_agent_notifications_queue where queue_id='$queue_id'";
		$del_rslt=mysql_to_mysqli($del_stmt, $link);
		}
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

### log the visibility changes sent from the agent screen
if (strlen($visibility) > 1)
	{
	$vc=0;
	$visibility_details = explode('|',$visibility);
	$visibility_details_ct = count($visibility_details);
	while($vc < $visibility_details_ct)
		{
		$visibility_data = explode(' ',$visibility_details[$vc]);
		$visibility_type = $visibility_data[0];
		$visibility_length = $visibility_data[1];
		$visibility_start_epoch = $visibility_data[2];
		$visibility_end_epoch = $visibility_data[3];
		#$visibility_length = ($StarTtime - $visibility_data[2]);
		$agent_log_id = $visibility_data[4];

		if (strlen($visibility_type) > 1)
			{
			$stmtA="INSERT INTO vicidial_agent_visibility_log set db_time=NOW(),event_start_epoch='$visibility_start_epoch',event_end_epoch='$visibility_end_epoch',user='$user',length_in_sec='$visibility_length',visibility='$visibility_type',agent_log_id='$agent_log_id';";
			$rslt=mysql_to_mysqli($stmtA, $link);
			}

		$vc++;
		}
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

			$stmtA="INSERT INTO vicidial_ajax_log set user='$user',start_time='$click_time',db_time=NOW(),run_time='0',php_script='vicidial.php',action='$click_function',lead_id=$lead_id,stage='$cd|$click_options',session_name='$session_name',last_sql='';";
			$rslt=mysql_to_mysqli($stmtA, $link);

			$cd++;
			}
		}
	}

# log the display of agent-disabled and time-sync messages
if (strlen($clicks) > 1)
	{
	if (preg_match("/-----agent_disabled---|-----system_disabled---/",$clicks))
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

			if ( ($click_function == 'agent_disabled') or ($click_function == 'system_disabled') )
				{
				$stmtA="INSERT INTO vicidial_sync_log set user='$user',start_time='$click_time',db_time=NOW(),run_time='0',php_script='vicidial.php',action='$click_function',lead_id='$lead_id',stage='$cd|$click_options',session_name='$session_name',last_sql='';";
				$rslt=mysql_to_mysqli($stmtA, $link);
				}

			$cd++;
			}
		}
	}

exit;

?>
