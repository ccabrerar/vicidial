<?php
# manager_send.php    version 2.14
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed purely to insert records into the vicidial_manager table to signal Actions to an asterisk server
# This script depends on the server_ip being sent and also needs to have a valid user/pass from the vicidial_users table
# 
# required variables:
#  - $server_ip
#  - $session_name
#  - $user
#  - $pass
# optional variables:
#  - $ACTION - ('Originate','Redirect','Hangup','Command','Monitor','StopMonitor','SysCIDOriginate','SysCIDdtmfOriginate','RedirectName','RedirectNameVmail','MonitorConf','StopMonitorConf','RedirectXtra','RedirectXtraCX','RedirectVD','HangupConfDial','VolumeControl','OriginateVDRelogin')
#  - $queryCID - ('CN012345678901234567',...)
#  - $format - ('text','debug')
#  - $channel - ('Zap/41-1','SIP/test101-1jut','IAX2/iaxy@iaxy',...)
#  - $exten - ('1234','913125551212',...)
#  - $ext_context - ('default','demo',...)
#  - $ext_priority - ('1','2',...)
#  - $filename - ('20050406-125623_44444',...)
#  - $extenName - ('phone100',...)
#  - $parkedby - ('phone100',...)
#  - $extrachannel - ('Zap/41-1','SIP/test101-1jut','IAX2/iaxy@iaxy',...)
#  - $auto_dial_level - ('0','1','1.1',...)
#  - $campaign - ('CLOSER','TESTCAMP',...)
#  - $uniqueid - ('1120232758.2406800',...)
#  - $lead_id - ('1234',...)
#  - $seconds - ('32',...)
#  - $outbound_cid - ('3125551212','0000000000',...)
#  - $agent_log_id - ('123456',...)
#  - $call_server_ip - ('10.10.10.15',...)
#  - $CalLCID - ('VD01234567890123456',...)
#  - $stage - ('UP','DOWN','2NDXfeR')
#  - $session_id - ('8600051')
#  - $FROMvdc - ('YES','NO')
#  - $agentchannel - ('SIP/cc101-g7yr','Zap/1-1',...)
#  - $usegroupalias - ('0','1')
#  - $account - ('DEFAULT',...)
#  - $agent_dialed_number - ('1','')
#  - $agent_dialed_type - ('MANUAL_OVERRIDE','MANUAL_DIALNOW','MANUAL_PREVIEW',...)
#  - $nodeletevdac - ('0','1')
#  - $alertCID - ('0','1')
#  - $preset_name = ('TESTING PRESET',...)
#  - $call_variables = ('Variable: vendor_lead_code=1234|campaign=TESTCAMP|...')
#  - $log_campaign
#  - $qm_extension
#
# CHANGELOG:
# 50401-1002 - First build of script, Hangup function only
# 50404-1045 - Redirect basic function enabled
# 50406-1522 - Monitor basic function enabled
# 50407-1647 - Monitor and StopMonitor full functions enabled
# 50422-1120 - basic Originate function enabled
# 50428-1451 - basic SysCIDOriginate function enabled for checking voicemail
# 50502-1539 - basic RedirectName and RedirectNameVmail added
# 50503-1227 - added session_name checking for extra security
# 50523-1341 - added Conference call start/stop recording
# 50523-1421 - added OriginateName and OriginateNameVmail for local calls
# 50524-1602 - added RedirectToPark and RedirectFromPark
# 50531-1203 - added RedirecXtra for dual channel redirection
# 50630-1100 - script changed to not use HTTP login vars, user/pass instead
# 50804-1148 - Added RedirectVD for VICIDIAL blind redirection with logging
# 50815-1204 - Added NEXTAVAILABLE to RedirectXtra function
# 50903-2343 - Added HangupConfDial function to hangup in-dial channels in conf
# 50913-1057 - Added outbound_cid set if present to originate call
# 51020-1556 - Added agent_log_id framework for detailed agent activity logging
# 51118-1204 - Fixed Blind transfer bug from VICIDIAL when in manual dial mode
# 51129-1014 - Added ability to accept calls from other VICIDIAL servers
# 51129-1253 - Fixed Hangups of other agents channels in VICIDIAL AD
# 60310-2022 - Fixed NEXTAVAILABLE bug in leave-3way-call redirect function
# 60421-1413 - check GET/POST vars lines with isset to not trigger PHP NOTICES
# 60619-1158 - Added variable filters to close security holes for login form
# 60809-1544 - Added direct transfers to leave-3ways in consultative transfers
# 61004-1526 - Added parsing of volume control command and lookup or number
# 61130-1617 - Added lead_id to MonitorConf for recording_log
# 61201-1115 - Added user to MonitorConf for recording_log
# 70111-1600 - added ability to use BLEND/INBND/*_C/*_B/*_I as closer campaigns
# 70226-1251 - Added Mute/UnMute to conference volume control
# 70320-1502 - Added option to allow retry of leave-3way-call and debug logging
# 70322-1636 - Added sipsak display ability
# 80331-1433 - Added second transfer try for VICIDIAL transfers on manual dial calls
# 80402-0121 - Fixes for manual dial transfers on some systems
# 80424-0442 - Added non_latin lookup from system_settings
# 80707-2325 - Added vicidial_id to recording_log for tracking of vicidial or closer log to recording
# 80915-1755 - Rewrote leave-3way functions for external calling
# 81011-1404 - Fixed bugs in leave3way when transferring a manual dial call
# 81020-1459 - Fixed bugs in queue_log logging
# 81104-0203 - Added mysql error logging capability
# 90303-1144 - Fixed manual dial live hangup bug
# 90304-1334 - Added account and usegroupalias and user campaign/in-group specific variables
# 90305-1040 - Added agent_dialed_number and type for user_call_log feature
# 90508-0727 - Changed to PHP long tags
# 90511-1019 - Added restriction not allowing dialing into agent sessions from manual dial
# 90913-1410 - Fixed minor logging bug
# 90916-1830 - Added nodeletevdac
# 90924-1555 - Added am_message_exten_override  for list_id option
# 91112-1110 - Added CALLOUTBOUND value to QM entry lookup
# 91205-2103 - Code cleanup
# 91213-1208 - Added queue_position to queue_log COMPLETE... records
# 100327-0846 - Fix for list_id override answering machine message
# 100423-2304 - Added alertCID
# 100527-1014 - Added SysCIDdtmfOriginate function
# 100813-0833 - Added preset_name variable and logging
# 101004-1345 - Added Ivr park functions
# 101024-1638 - Added park_log logging for parked calls
# 101107-2331 - Added CALLERONHOLD/CALLEROFFHOLD queue_log entries
# 101125-1018 - Added call_variables Originate variables
# 110224-1710 - Added compatibility with QM phone environment logging
# 110626-2320 - Added qm_extension
# 120810-0030 - Added external_recording
# 120831-1458 - Added vicidial_dial_log outbound call logging
# 121120-0848 - Added QM socket-send functionality
# 130108-1641 - Change for Asterisk 1.8 compatibility
# 130328-0008 - Converted ereg to preg functions
# 130603-2205 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130705-1521 - Added optional encrypted passwords compatibility
# 130802-1021 - Changed to PHP mysqli functions
# 130926-1755 - Added queuemetrics_record_hold option
# 140215-2057 - Added several variable options for QM socket URL
# 140810-2125 - Changed to use QXZ function for echoing text
# 141118-1101 - Formatting changes for QXZ output
# 141128-0851 - Code cleanup for QXZ functions
# 141216-2107 - Added language settings lookups and user/pass variable standardization
# 150307-1837 - Added leave 3way custom sound context
# 150701-1208 - Modified mysqli_error() to mysqli_connect_error() where appropriate
# 150723-1643 - Added ajax logging
# 150915-2039 - Added option for RECID as variable in recording filename
# 151230-0910 - Fixed transfer of parked call logging issue #901
# 160101-1130 - Added code to handle routing initiated recordings
# 160912-2310 - Fixed StopMonitorConf bug involving agent-invoked commands to stop recording
# 161029-0845 - Added RedirectToParkXfer and RedirectFromParkXfer functions
# 161102-1043 - Fixed QM partition problem
# 161117-0622 - Fixes for recording_log issue where filename is non-unique
# 161217-0823 - Added parked_calls_recent table entries for better handling of parked calls
# 170317-2214 - Added more debugging output
# 170513-1616 - Added more debugging output
# 170526-2241 - Added additional variable filtering
# 170528-0850 - Fix for rare inbound logging issue #1017, Added additional variable filtering
# 170921-2009 - Fix for CALLID in beginning of recording filename
# 180522-1920 - Added more agent debug output for recordings
# 190222-1318 - Added recent session per-call logging
# 190310-1202 - Added MuteRecording function
# 191013-2114 - Fixes for PHP7
# 201107-2228 - Added campaign/in-group logging in park_log
# 201117-1751 - Changes for better compatibility with non-latin data input
# 210615-1016 - Default security fixes, CVE-2021-28854
# 210616-2051 - Added optional CORS support, see options.php for details
# 210823-0916 - Fix for security issue
# 210825-0911 - Fix for XSS security issue
# 220220-0904 - Added allow_web_debug system setting
# 220312-0936 - Added vicidial_dial_cid_log logging
# 221116-1051 - Fix for long in-group dialstring extensions
# 230418-1022 - Added dial_override_limit options.php setting
# 230726-0857 - Fix for rare vicidial_closer_log issue on Voicemail transfers
# 240420-2233 - ConfBridge code added
# 240430-1046 - Allow for park/grab of xfer line through API
# 240709-2010 - Changes to input variable filtering
#

$version = '2.14-104';
$build = '240709-2010';
$php_script = 'manager_send.php';
$mel=1;					# Mysql Error Log enabled = 1
$mysql_log_count=161;
$one_mysql_log=0;
$SSagent_debug_logging=0;
$startMS = microtime();
$dial_override_limit=6;
$ip = getenv("REMOTE_ADDR");

require_once("dbconnect_mysqli.php");
require_once("functions.php");

### These are variable assignments for PHP globals off
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["pass"]))					{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))			{$pass=$_POST["pass"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["session_name"]))			{$session_name=$_GET["session_name"];}
	elseif (isset($_POST["session_name"]))	{$session_name=$_POST["session_name"];}
if (isset($_GET["ACTION"]))					{$ACTION=$_GET["ACTION"];}
	elseif (isset($_POST["ACTION"]))		{$ACTION=$_POST["ACTION"];}
if (isset($_GET["queryCID"]))				{$queryCID=$_GET["queryCID"];}
	elseif (isset($_POST["queryCID"]))		{$queryCID=$_POST["queryCID"];}
if (isset($_GET["format"]))					{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))		{$format=$_POST["format"];}
if (isset($_GET["channel"]))				{$channel=$_GET["channel"];}
	elseif (isset($_POST["channel"]))		{$channel=$_POST["channel"];}
if (isset($_GET["exten"]))					{$exten=$_GET["exten"];}
	elseif (isset($_POST["exten"]))			{$exten=$_POST["exten"];}
if (isset($_GET["ext_context"]))			{$ext_context=$_GET["ext_context"];}
	elseif (isset($_POST["ext_context"]))	{$ext_context=$_POST["ext_context"];}
if (isset($_GET["ext_priority"]))			{$ext_priority=$_GET["ext_priority"];}
	elseif (isset($_POST["ext_priority"]))	{$ext_priority=$_POST["ext_priority"];}
if (isset($_GET["filename"]))				{$filename=$_GET["filename"];}
	elseif (isset($_POST["filename"]))		{$filename=$_POST["filename"];}
if (isset($_GET["extenName"]))				{$extenName=$_GET["extenName"];}
	elseif (isset($_POST["extenName"]))		{$extenName=$_POST["extenName"];}
if (isset($_GET["parkedby"]))				{$parkedby=$_GET["parkedby"];}
	elseif (isset($_POST["parkedby"]))		{$parkedby=$_POST["parkedby"];}
if (isset($_GET["extrachannel"]))			{$extrachannel=$_GET["extrachannel"];}
	elseif (isset($_POST["extrachannel"]))	{$extrachannel=$_POST["extrachannel"];}
if (isset($_GET["auto_dial_level"]))			{$auto_dial_level=$_GET["auto_dial_level"];}
	elseif (isset($_POST["auto_dial_level"]))	{$auto_dial_level=$_POST["auto_dial_level"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["uniqueid"]))				{$uniqueid=$_GET["uniqueid"];}
	elseif (isset($_POST["uniqueid"]))		{$uniqueid=$_POST["uniqueid"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["secondS"]))				{$secondS=$_GET["secondS"];}
	elseif (isset($_POST["secondS"]))		{$secondS=$_POST["secondS"];}
if (isset($_GET["outbound_cid"]))			{$outbound_cid=$_GET["outbound_cid"];}
	elseif (isset($_POST["outbound_cid"]))	{$outbound_cid=$_POST["outbound_cid"];}
if (isset($_GET["agent_log_id"]))			{$agent_log_id=$_GET["agent_log_id"];}
	elseif (isset($_POST["agent_log_id"]))	{$agent_log_id=$_POST["agent_log_id"];}
if (isset($_GET["call_server_ip"]))				{$call_server_ip=$_GET["call_server_ip"];}
	elseif (isset($_POST["call_server_ip"]))	{$call_server_ip=$_POST["call_server_ip"];}
if (isset($_GET["CalLCID"]))				{$CalLCID=$_GET["CalLCID"];}
	elseif (isset($_POST["CalLCID"]))		{$CalLCID=$_POST["CalLCID"];}
if (isset($_GET["phone_code"]))				{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))	{$phone_code=$_POST["phone_code"];}
if (isset($_GET["phone_number"]))			{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["extension"]))				{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))		{$extension=$_POST["extension"];}
if (isset($_GET["protocol"]))				{$protocol=$_GET["protocol"];}
	elseif (isset($_POST["protocol"]))		{$protocol=$_POST["protocol"];}
if (isset($_GET["phone_ip"]))				{$phone_ip=$_GET["phone_ip"];}
	elseif (isset($_POST["phone_ip"]))		{$phone_ip=$_POST["phone_ip"];}
if (isset($_GET["enable_sipsak_messages"]))				{$enable_sipsak_messages=$_GET["enable_sipsak_messages"];}
	elseif (isset($_POST["enable_sipsak_messages"]))	{$enable_sipsak_messages=$_POST["enable_sipsak_messages"];}
if (isset($_GET["allow_sipsak_messages"]))				{$allow_sipsak_messages=$_GET["allow_sipsak_messages"];}
	elseif (isset($_POST["allow_sipsak_messages"]))		{$allow_sipsak_messages=$_POST["allow_sipsak_messages"];}
if (isset($_GET["session_id"]))				{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))	{$session_id=$_POST["session_id"];}
if (isset($_GET["FROMvdc"]))				{$FROMvdc=$_GET["FROMvdc"];}
	elseif (isset($_POST["FROMvdc"]))		{$FROMvdc=$_POST["FROMvdc"];}
if (isset($_GET["agentchannel"]))			{$agentchannel=$_GET["agentchannel"];}
	elseif (isset($_POST["agentchannel"]))	{$agentchannel=$_POST["agentchannel"];}
if (isset($_GET["usegroupalias"]))			{$usegroupalias=$_GET["usegroupalias"];}
	elseif (isset($_POST["usegroupalias"]))	{$usegroupalias=$_POST["usegroupalias"];}
if (isset($_GET["account"]))				{$account=$_GET["account"];}
	elseif (isset($_POST["account"]))		{$account=$_POST["account"];}
if (isset($_GET["agent_dialed_number"]))			{$agent_dialed_number=$_GET["agent_dialed_number"];}
	elseif (isset($_POST["agent_dialed_number"]))	{$agent_dialed_number=$_POST["agent_dialed_number"];}
if (isset($_GET["agent_dialed_type"]))				{$agent_dialed_type=$_GET["agent_dialed_type"];}
	elseif (isset($_POST["agent_dialed_type"]))		{$agent_dialed_type=$_POST["agent_dialed_type"];}
if (isset($_GET["nodeletevdac"]))				{$nodeletevdac=$_GET["nodeletevdac"];}
	elseif (isset($_POST["nodeletevdac"]))		{$nodeletevdac=$_POST["nodeletevdac"];}
if (isset($_GET["alertCID"]))				{$alertCID=$_GET["alertCID"];}
	elseif (isset($_POST["alertCID"]))		{$alertCID=$_POST["alertCID"];}
if (isset($_GET["preset_name"]))			{$preset_name=$_GET["preset_name"];}
	elseif (isset($_POST["preset_name"]))	{$preset_name=$_POST["preset_name"];}
if (isset($_GET["call_variables"]))				{$call_variables=$_GET["call_variables"];}
	elseif (isset($_POST["call_variables"]))	{$call_variables=$_POST["call_variables"];}
if (isset($_GET["log_campaign"]))			{$log_campaign=$_GET["log_campaign"];}
	elseif (isset($_POST["log_campaign"]))	{$log_campaign=$_POST["log_campaign"];}
if (isset($_GET["qm_extension"]))			{$qm_extension=$_GET["qm_extension"];}
	elseif (isset($_POST["qm_extension"]))	{$qm_extension=$_POST["qm_extension"];}
if (isset($_GET["customerparked"]))				{$customerparked=$_GET["customerparked"];}
	elseif (isset($_POST["customerparked"]))	{$customerparked=$_POST["customerparked"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["group_id"]))			{$group_id=$_GET["group_id"];}
	elseif (isset($_POST["group_id"]))	{$group_id=$_POST["group_id"];}

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,allow_sipsak_messages,enable_languages,language_method,meetme_enter_login_filename,meetme_enter_leave3way_filename,agent_debug_logging,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02001',$user,$server_ip,$session_name,$one_mysql_log);}
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =						$row[0];
	$allow_sipsak_messages =			$row[1];
	$SSenable_languages =				$row[2];
	$SSlanguage_method =				$row[3];
	$meetme_enter_login_filename =		$row[4];
	$meetme_enter_leave3way_filename =	$row[5];
	$SSagent_debug_logging =			$row[6];
	$SSallow_web_debug =				$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

# filter variables
$session_name = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$session_name);
$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$server_ip);
$lead_id = preg_replace('/[^0-9]/','',$lead_id);
$session_id = preg_replace('/[^0-9]/','',$session_id);
$exten = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$exten);
$extension = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$extension);
$protocol = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$protocol);
$ACTION = preg_replace("/\'|\"|\\\\|;/","",$ACTION);
$CalLCID = preg_replace("/\'|\"|\\\\|;/","",$CalLCID);
$FROMvdc = preg_replace('/[^-_0-9a-zA-Z]/','',$FROMvdc);
$agent_log_id = preg_replace('/[^-_0-9a-zA-Z]/','',$agent_log_id);
$agentchannel = preg_replace("/\'|\"|\\\\/","",$agentchannel);
$auto_dial_level = preg_replace('/[^-\._0-9a-zA-Z]/','',$auto_dial_level);
$call_server_ip = preg_replace("/\'|\"|\\\\|;/","",$call_server_ip);
$call_variables = preg_replace("/\'|\"|\\\\|;/","",$call_variables);
$channel = preg_replace("/\'|\"|\\\\/","",$channel);
$customerparked = preg_replace('/[^0-9]/','',$customerparked);
$enable_sipsak_messages = preg_replace('/[^0-9]/','',$enable_sipsak_messages);
$ext_context = preg_replace('/[^-_0-9a-zA-Z]/','',$ext_context);
$ext_priority = preg_replace('/[^-_0-9a-zA-Z]/','',$ext_priority);
$exten = preg_replace("/\'|\"|\\\\|;/","",$exten);
$extenName = preg_replace("/\'|\"|\\\\|;/","",$extenName);
$extrachannel = preg_replace("/\'|\"|\\\\/","",$extrachannel);
$format = preg_replace('/[^-_0-9a-zA-Z]/','',$format);
$log_campaign = preg_replace("/\'|\"|\\\\|;/","",$log_campaign);
$nodeletevdac = preg_replace('/[^0-9]/','',$nodeletevdac);
$outbound_cid = preg_replace("/\'|\"|\\\\|;/","",$outbound_cid);
$parkedby = preg_replace("/\'|\"|\\\\|;/","",$parkedby);
$phone_code = preg_replace("/\s/","",$phone_code);
$preset_name = preg_replace("/\'|\"|\\\\|;/","",$preset_name);
$qm_extension = preg_replace("/\'|\"|\\\\|;/","",$qm_extension);
$secondS = preg_replace('/[^0-9]/','',$secondS);
$stage = preg_replace("/\'|\"|\\\\|;/","",$stage);
$usegroupalias = preg_replace('/[^0-9]/','',$usegroupalias);
$phone_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$phone_ip);
$allow_sipsak_messages = preg_replace('/[^-_0-9a-zA-Z]/','',$allow_sipsak_messages);
$alertCID = preg_replace('/[^-_0-9a-zA-Z]/','',$alertCID);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$campaign = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign);
	$phone_number = preg_replace('/[^-_0-9a-zA-Z]/','',$phone_number);
	$uniqueid = preg_replace('/[^-_\.0-9a-zA-Z]/','',$uniqueid);
	$user_group = preg_replace('/[^-_0-9a-zA-Z]/','',$user_group);
	$agent_dialed_number = preg_replace('/[^-_0-9a-zA-Z]/','',$agent_dialed_number);
	$agent_dialed_type = preg_replace('/[^-_0-9a-zA-Z]/','',$agent_dialed_type);
	$account = preg_replace('/[^-_0-9a-zA-Z]/','',$account);
	$group_id = preg_replace('/[^-_0-9a-zA-Z]/','',$group_id);
	$filename = preg_replace('/[^-\._0-9a-zA-Z]/','',$filename);
	$queryCID = preg_replace('/[^-\._0-9a-zA-Z]/','',$queryCID);
	}
else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$campaign = preg_replace('/[^-_0-9\p{L}]/u','',$campaign);
	$phone_number = preg_replace('/[^-_0-9\p{L}]/u','',$phone_number);
	$uniqueid = preg_replace('/[^-_\.0-9\p{L}]/u','',$uniqueid);
	$user_group = preg_replace('/[^-_0-9\p{L}]/u','',$user_group);
	$agent_dialed_number = preg_replace('/[^-_0-9\p{L}]/u','',$agent_dialed_number);
	$agent_dialed_type = preg_replace('/[^-_0-9\p{L}]/u','',$agent_dialed_type);
	$account = preg_replace('/[^-_0-9\p{L}]/u','',$account);
	$group_id = preg_replace('/[^-_0-9\p{L}]/u','',$group_id);
	$filename = preg_replace('/[^-\._0-9\p{L}]/u','',$filename);
	$queryCID = preg_replace('/[^-\._0-9\p{L}]/u','',$queryCID);
	}

# default optional vars if not set
if (!isset($ACTION))   {$ACTION="Originate";}
if (!isset($format))   {$format="alert";}
if (!isset($ext_priority))   {$ext_priority="1";}

$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$NOWnum = date("YmdHis");
if (!isset($query_date)) {$query_date = $NOW_DATE;}


#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02129',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

if (strlen($SSagent_debug_logging) > 1)
	{
	if ($SSagent_debug_logging == "$user")
		{$SSagent_debug_logging=1;}
	else
		{$SSagent_debug_logging=0;}
	}

$stmtA="SELECT conf_engine FROM servers WHERE server_ip='$server_ip';";
$rslt=mysql_to_mysqli($stmtA, $link);
$row=mysqli_fetch_row($rslt);
$conf_engine =  $row[0]; 

$threeway_context = $ext_context;
if (strlen($meetme_enter_leave3way_filename) > 0)
	{
	$threeway_context = 'meetme-enter-leave3way';
	if ($conf_engine == "CONFBRIDGE") { $threeway_context = 'confbridge-enter-leave3way'; }
	}

$auth=0;
$auth_message = user_authorization($user,$pass,'',0,1,0,0,'manager_send');
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
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02003',$user,$server_ip,$session_name,$one_mysql_log);}
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
	echo "<!-- VERSION: $version     BUILD: $build    ACTION: $ACTION   server_ip: $server_ip-->\n";
	echo "<title>"._QXZ("Manager Send: ");
	if ($ACTION=="Originate")		{echo "Originate";}
	if ($ACTION=="Redirect")		{echo "Redirect";}
	if ($ACTION=="RedirectName")	{echo "RedirectName";}
	if ($ACTION=="Hangup")			{echo "Hangup";}
	if ($ACTION=="Command")			{echo "Command";}
	if ($ACTION==99999)				{echo "HELP";}
	echo "</title>\n";
	echo "</head>\n";
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	}




######################
# ACTION=SysCIDdtmfOriginate  - prep the send dtmf command
######################
if ($ACTION=="SysCIDdtmfOriginate")
	{
	$stmt="UPDATE vicidial_live_agents SET external_dtmf='' where user='$user';";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02092',$user,$server_ip,$session_name,$one_mysql_log);}

	$ACTION="SysCIDOriginate";
	}



######################
# ACTION=SysCIDOriginate  - insert Originate Manager statement allowing small CIDs for system calls
######################
if ($ACTION=="SysCIDOriginate")
	{
	if ( (strlen($exten)<1) or (strlen($channel)<1) or (strlen($ext_context)<1) or (strlen($queryCID)<1) )
		{
		echo _QXZ("Exten %1s is not valid or queryCID %2s is not valid, Originate command not inserted",0,'',$exten,$queryCID)."\n";
		}
	else
		{
		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$queryCID','Channel: $channel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','Callerid: $queryCID','','','','','');";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02004',$user,$server_ip,$session_name,$one_mysql_log);}

		### log outbound call in the vicidial_user_dial_log
		$stmt = "INSERT INTO vicidial_user_dial_log SET caller_code='$queryCID',user='$user',call_date='$NOW_TIME',call_type='SYS',notes='$exten $ext_context $channel';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02149',$user,$server_ip,$session_name,$one_mysql_log);}

		echo _QXZ("Originate command sent for Exten %1s Channel %2s on %3s",0,'',$exten,$channel,$server_ip)."\n";
		}
	}



######################
# ACTION=Originate, OriginateName, OriginateNameVmail  - insert Originate Manager statement
######################
if ($ACTION=="OriginateName")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($extenName)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("extenName %1s must be set",0,'',$extenName)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("OriginateName Action not sent")."\n";
		}
	else
		{
		$stmt="SELECT dialplan_number FROM phones where server_ip = '$server_ip' and extension='$extenName';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02005',$user,$server_ip,$session_name,$one_mysql_log);}
		$name_count = mysqli_num_rows($rslt);
		if ($name_count>0)
			{
			$row=mysqli_fetch_row($rslt);
			$exten = $row[0];
			$ACTION="Originate";
			}
		}
	}

if ($ACTION=="OriginateNameVmail")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($extenName)<1)  or (strlen($exten)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("extenName %1s must be set",0,'',$extenName)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("OriginateNameVmail Action not sent")."\n";
		}
	else
		{
		$stmt="SELECT voicemail_id FROM phones where server_ip = '$server_ip' and extension='$extenName';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02006',$user,$server_ip,$session_name,$one_mysql_log);}
		$name_count = mysqli_num_rows($rslt);
		if ($name_count>0)
			{
			$row=mysqli_fetch_row($rslt);
			$exten = "$exten$row[0]";
			$ACTION="Originate";
			}
		}
	}

if ($ACTION=="OriginateVDRelogin")
	{
	if ( ($enable_sipsak_messages > 0) and ($allow_sipsak_messages > 0) and (preg_match("/SIP/i",$protocol)) )
		{
		$CIDdate = date("ymdHis");
		$DS='-';
		$SIPSAK_prefix = 'LIN-';
		$campaign = preg_replace('/[^-\._0-9\p{L}]/u',"",$campaign);
		$extension = preg_replace('/[^-\._0-9\p{L}]/u',"",$extension);
		$phone_ip = preg_replace('/[^-\._0-9\p{L}]/u',"",$phone_ip);

		print "<!-- sending login sipsak message: $SIPSAK_prefix$campaign -->\n";
		passthru("/usr/local/bin/sipsak -M -O desktop -B \"$SIPSAK_prefix$campaign\" -r 5060 -s sip:$extension@$phone_ip > /dev/null");
		$queryCID = "$SIPSAK_prefix$campaign$DS$CIDdate";
	}
	if ($conf_engine == "CONFBRIDGE") { $exten = "2$exten"; }

	$ACTION="Originate";
	}

if ($ACTION=="Originate")
	{
	if ( (strlen($exten)<1) or (strlen($channel)<1) or (strlen($ext_context)<1) or ( (strlen($queryCID)<10) and ($alertCID < 1) ) )
		{
		echo "ERROR"._QXZ(" Exten %1s is not valid or queryCID %2s is not valid, Originate command not inserted",0,'',$exten,$queryCID)."\n";
		}
	else
		{
		if ( (preg_match('/MANUAL/i',$agent_dialed_type)) and ( (preg_match("/^\d860\d\d\d\d$/i",$exten)) or (preg_match("/^860\d\d\d\d$/i",$exten)) ) )
			{
			echo "ERROR"._QXZ(" You are not allowed to dial into other agent sessions")." $exten\n";
			$stage .= " ERROR $exten OTHER SESSION";
			if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
			exit;
			}
		$call_type='';
		if ( (strlen($call_type) < 1) and (preg_match("/^M/i",$queryCID)) ) {$call_type='M';}
		if ( (strlen($call_type) < 1) and (preg_match("/^Y/i",$queryCID)) ) {$call_type='Y';}
		if ( (strlen($call_type) < 1) and (preg_match("/^S/i",$queryCID)) ) {$call_type='S';}
		if ( (strlen($call_type) < 1) and (preg_match("/^AC/i",$queryCID)) ) {$call_type='AC';}
		if ( (strlen($call_type) < 1) and (preg_match("/^DC/i",$queryCID)) ) {$call_type='DC';}
		if ( (strlen($call_type) < 1) and (preg_match("/^DV/i",$queryCID)) ) {$call_type='DV';}
		if ( (strlen($call_type) < 1) and (preg_match("/^DO/i",$queryCID)) ) {$call_type='DO';}
		if ( (strlen($call_type) < 1) and (preg_match("/^BM/i",$queryCID)) ) {$call_type='BM';}
		if ( (strlen($call_type) < 1) and (preg_match("/^BW/i",$queryCID)) ) {$call_type='BW';}
		if ( (strlen($call_type) < 1) and (preg_match("/^BB/i",$queryCID)) ) {$call_type='BB';}

		# check for too many dial override calls per minute and lock account if over the set limit
		if ( (preg_match("/^DO|^DV/i",$queryCID)) and ($dial_override_limit > 0) )
			{
			# first check to see if this queryCID has already been sent in the last 60 minutes
			$stmt="SELECT count(*) FROM vicidial_dial_log where caller_code='$queryCID' and call_date >= (NOW() - INTERVAL 60 MINUTE);";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02150',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				### log FAILED outbound call in the dial log
				$stmt = "INSERT INTO vicidial_dial_log SET caller_code='$queryCID',lead_id='$lead_id',server_ip='$server_ip',call_date='$NOW_TIME',extension='$exten',channel='$channel',timeout='0',outbound_cid='$outCID',context='$ext_context',sip_hangup_cause='999',sip_hangup_reason='$call_type DUPLICATE for $user';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02151',$user,$server_ip,$session_name,$one_mysql_log);}

				### log FAILED outbound call in the user dial log
				$stmt = "INSERT INTO vicidial_user_dial_log SET caller_code='$queryCID',user='$user',call_date='$NOW_TIME',call_type='$call_type',notes='DUPLICATE';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02152',$user,$server_ip,$session_name,$one_mysql_log);}

				if ($row[0] >= $dial_override_limit)
					{
					### lock user account after duplicate exceed limit
					$stmt="UPDATE vicidial_users set last_login_date=NOW(),failed_login_count=10,failed_last_ip_today='$ip',failed_login_attempts_today=(failed_login_attempts_today+1),failed_login_count_today=(failed_login_count_today+1),failed_last_type_today='04sLOCK' where user='$user';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02153',$user,$server_ip,$session_name,$one_mysql_log);}
					}

				$stage .= " DUP $exten $channel $server_ip $outbound_cid";
				if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
				exit; 
				}
			else
				{
				# check how many dial-override calls this user has already placed in the last 1 minute
				$stmt="SELECT count(*) FROM vicidial_user_dial_log where user='$user' and call_type IN('DO','DV') and call_date >= (NOW() - INTERVAL 1 MINUTE);";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02154',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				if ($row[0] >= $dial_override_limit)
					{
					### log FAILED outbound call in the dial log
					$stmt = "INSERT INTO vicidial_dial_log SET caller_code='$queryCID',lead_id='$lead_id',server_ip='$server_ip',call_date='$NOW_TIME',extension='$exten',channel='$channel',timeout='0',outbound_cid='$outCID',context='$ext_context',sip_hangup_cause='999',sip_hangup_reason='$call_type LIMIT for $user ($row[0] > $dial_override_limit)';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02155',$user,$server_ip,$session_name,$one_mysql_log);}

					### log FAILED outbound call in the user dial log
					$stmt = "INSERT INTO vicidial_user_dial_log SET caller_code='$queryCID',user='$user',call_date='$NOW_TIME',call_type='$call_type',notes='DO LIMIT ($row[0] > $dial_override_limit)';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02156',$user,$server_ip,$session_name,$one_mysql_log);}

					### lock user account after duplicate exceed limit
					$stmt="UPDATE vicidial_users set last_login_date=NOW(),failed_login_count=10,failed_last_ip_today='$ip',failed_login_attempts_today=(failed_login_attempts_today+1),failed_login_count_today=(failed_login_count_today+1),failed_last_type_today='05sLOCK' where user='$user';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02157',$user,$server_ip,$session_name,$one_mysql_log);}

					$stage .= " LIMIT $exten $channel $server_ip $outbound_cid";
					if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
					exit; 
					}
				}
			}

		if (strlen($outbound_cid)>1)
			{$outCID = "\"$queryCID\" <$outbound_cid>";}
		else
			{$outCID = "$queryCID";}
		if ( ($usegroupalias > 0) and (strlen($account)>1) )
			{
			$RAWaccount = $account;
			$account = "Account: $account";
			$variable = "Variable: _usegroupalias=1";
			if (strlen($call_variables)>9)
				{$variable = "Variable: _usegroupalias=1";}   # |$call_variables
			}
		else
			{$variable='';   $account="Variable: $call_variables";}

		$new_variable='';
		$new_channel=$channel;
		$new_exten=$exten;
		if ( (preg_match("/^900\d\d\*|^9900\d\d\*|^980\d\d\*|^9980\d\d\*|^8305888888888888\d90009|^8305888888888888\d98009/",$exten)) or (preg_match("/^Local\/900\d\d\*|^Local\/9900\d\d\*|^Local\/980\d\d\*|^Local\/9980\d\d\*|^Local\/8305888888888888\d90009|^Local\/8305888888888888\d98009/",$channel)) )
			{
			if (preg_match("/^900\d\d\*|^9900\d\d\*|^980\d\d\*|^9980\d\d\*|^8305888888888888\d90009|^8305888888888888\d98009/",$exten))
				{
				$new_source='manager_send_exten';
				$temp_exten = $exten;

				$stmt="INSERT INTO vicidial_long_extensions values('','$temp_exten',NOW(),'$new_source');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02144',$user,$server_ip,$session_name,$one_mysql_log);}
				$le_id = mysqli_insert_id($link);

				$new_exten = preg_replace("/\*.*/",'',$exten);
				$new_exten .= "**LEXTEN*$le_id";
				$new_variable = "Variable: _new_exten=$new_exten";
				}
			if (preg_match("/^Local\/900\d\d\*|^Local\/9900\d\d\*|^Local\/980\d\d\*|^Local\/9980\d\d\*|^Local\/8305888888888888\d90009|^Local\/8305888888888888\d98009/",$channel))
				{
				$new_source='manager_send_channel';
				$temp_exten = preg_replace("/^Local\//",'',$channel);
				$temp_context = preg_replace("/^.*\@/",'',$channel);

				$stmt="INSERT INTO vicidial_long_extensions values('','$temp_exten',NOW(),'$new_source');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02145',$user,$server_ip,$session_name,$one_mysql_log);}
				$le_id = mysqli_insert_id($link);

				$new_channel = preg_replace("/\*.*/",'',$temp_exten);
				$new_channel = "Local/$new_channel**LEXTEN*$le_id@$temp_context";
				$new_variable = "Variable: _new_channel=$new_channel";
				}
			}

		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$queryCID','Channel: $new_channel','Context: $ext_context','Exten: $new_exten','Priority: $ext_priority','Callerid: $outCID','$account','$variable','','','');";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02007',$user,$server_ip,$session_name,$one_mysql_log);}
		echo _QXZ("Originate command sent for Exten %1s Channel %2s on %3s",0,'',$exten,$channel,$server_ip)." |$account|$variable|\n";

		### log outbound call in the dial log
		$stmt = "INSERT INTO vicidial_dial_log SET caller_code='$queryCID',lead_id=$lead_id,server_ip='$server_ip',call_date='$NOW_TIME',extension='$exten',channel='$channel',timeout='0',outbound_cid='$outCID',context='$ext_context';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02119',$user,$server_ip,$session_name,$one_mysql_log);}

		### log outbound call in the dial cid log
		$stmt = "INSERT INTO vicidial_dial_cid_log SET caller_code='$queryCID',call_date='$NOW_TIME',call_type='MANUAL',call_alt='$agent_dialed_type', outbound_cid='$outbound_cid',outbound_cid_type='AGENT';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02146',$user,$server_ip,$session_name,$one_mysql_log);}

		### log outbound call in the vicidial_user_dial_log
		$stmt = "INSERT INTO vicidial_user_dial_log SET caller_code='$queryCID',user='$user',call_date='$NOW_TIME',call_type='$call_type',notes='';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02158',$user,$server_ip,$session_name,$one_mysql_log);}

		if ( (strlen($lead_id) > 0) and (strlen($session_id) > 0) and (preg_match("/^DC/",$queryCID)) )
			{
			$stmt="INSERT INTO vicidial_sessions_recent SET lead_id=$lead_id,server_ip='$server_ip',call_date=NOW(),user='$user',campaign_id='$campaign',conf_exten='$session_id',call_type='X';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02143',$user,$server_ip,$session_name,$one_mysql_log);}
			}

		if ($agent_dialed_number > 0)
			{
			if (strlen($lead_id)<1) {$lead_id='0';}
			$customer_hungup='';
			if ( ($stage > 0) and (preg_match("/3WAY/",$agent_dialed_type)) ) 
				{$customer_hungup = 'BEFORE_CALL';}
			$stmt = "INSERT INTO user_call_log (user,call_date,call_type,server_ip,phone_number,number_dialed,lead_id,callerid,group_alias_id,preset_name,campaign_id,customer_hungup) values('$user','$NOW_TIME','$agent_dialed_type','$server_ip','$exten','$channel',$lead_id,'$outbound_cid','$RAWaccount','$preset_name','$campaign','$customer_hungup')";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00192',$user,$server_ip,$session_name,$one_mysql_log);}

			if (strlen($preset_name) > 0)
				{
				$stmt = "SELECT count(*) from vicidial_xfer_stats where campaign_id='$campaign' and preset_name='$preset_name';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02093',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				if ($row[0] > 0)
					{
					$stmt = "UPDATE vicidial_xfer_stats SET xfer_count=(xfer_count+1) where campaign_id='$campaign' and preset_name='$preset_name';";
					}
				else
					{
					$stmt = "INSERT INTO vicidial_xfer_stats SET campaign_id='$campaign',preset_name='$preset_name',xfer_count='1';";
					}
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02094',$user,$server_ip,$session_name,$one_mysql_log);}
				}
			}
		$stage .= " $exten $channel $server_ip $outbound_cid";
		}
	}



######################
# ACTION=HangupConfDial  - find the Local channel that is in the conference and needs to be hung up
######################
if ($ACTION=="HangupConfDial")
	{
	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($exten)<3) or (strlen($queryCID)<15) or (strlen($ext_context)<1) )
		{
		$channel_live=0;
		echo _QXZ("conference %1s is not valid or ext_context %2s or queryCID %3s is not valid, Hangup command not inserted",0,'',$exten,$ext_context,$queryCID)."\n";
		}
	else
		{
		$local_DEF = 'Local/';
		$local_AMP = '@';
		$hangup_channel_prefix = "$local_DEF$exten$local_AMP$ext_context";

		$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$hangup_channel_prefix%\";";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02008',$user,$server_ip,$session_name,$one_mysql_log);}
		$row=mysqli_fetch_row($rslt);
		if ($row > 0)
			{
			$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$hangup_channel_prefix%\";";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02009',$user,$server_ip,$session_name,$one_mysql_log);}
			$rowx=mysqli_fetch_row($rslt);
			$channel=$rowx[0];
			$ACTION="Hangup";
			$queryCID = preg_replace("/^./i","G",$queryCID);  # GTvdcW...
			}
		}
	}



######################
# ACTION=Hangup  - insert Hangup Manager statement
######################
if ($ACTION=="Hangup")
	{
	$stmt="UPDATE vicidial_live_agents SET external_hangup='0' where user='$user';";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02010',$user,$server_ip,$session_name,$one_mysql_log);}

	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) )
		{
		$channel_live=0;
		echo _QXZ("Channel %1s is not valid or queryCID %2s is not valid, Hangup command not inserted",0,'',$channel,$queryCID)."\n";
		}
	else
		{
		if (strlen($call_server_ip)<7) {$call_server_ip = $server_ip;}

#		$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel';";
#			if ($format=='debug') {echo "\n<!-- $stmt -->";}
#		$rslt=mysql_to_mysqli($stmt, $link);
#		$row=mysqli_fetch_row($rslt);
#		if ($row[0]==0)
#			{
#			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$call_server_ip' and channel='$channel';";
#				if ($format=='debug') {echo "\n<!-- $stmt -->";}
#			$rslt=mysql_to_mysqli($stmt, $link);
#			$rowx=mysqli_fetch_row($rslt);
#			if ($rowx[0]==0)
#				{
#				$channel_live=0;
#				echo "Channel $channel is not live on $call_server_ip, Hangup command not inserted\n";
#				}	
#			}
		if ( ($auto_dial_level > 0) and (strlen($CalLCID)>2) and (strlen($exten)>2) and ($secondS > 0))
			{
			$stmt="SELECT count(*) FROM vicidial_auto_calls where channel='$channel' and callerid='$CalLCID';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02011',$user,$server_ip,$session_name,$one_mysql_log);}
			$rowx=mysqli_fetch_row($rslt);
			if ($rowx[0]==0)
				{
				echo _QXZ("Call %1s %2s is not live on %3s, Checking Live Channel",0,'',$CalLCID,$channel,$call_server_ip)."...\n";

				$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel' and extension LIKE \"%$exten\";";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02012',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				if ($row[0]==0)
					{
					$channel_live=0;
					echo _QXZ("Channel %1s is not live on %2s, Hangup command not inserted",0,'',$channel,$call_server_ip)." $rowx[0]\n$stmt\n";
					}
				else
					{
					echo "$stmt\n";
					}
				}
			}
		if ( ($auto_dial_level < 1) and (strlen($stage)>2) and (strlen($channel)>2) and (strlen($exten)>2) )
			{
			$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel' and extension NOT LIKE \"%$exten%\";";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02083',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$channel_live=0;
				echo _QXZ("Channel %1s in use by another agent on %2s, Hangup command not inserted",0,'',$channel,$call_server_ip)." $rowx[0]\n$stmt\n";
				if ($WeBRooTWritablE > 0)
					{
					$fp = fopen ("./vicidial_debug.txt", "w");
				#	fwrite ($fp, "$NOW_TIME|MDCHU|$user|$channel|$call_server_ip|$exten|\n");
					fwrite ($fp, "$NOW_TIME|MDCHU|\n");
					fclose($fp);
					}
				}
			else
				{
				echo "$stmt\n";
				}
			}

		if ($channel_live==1)
			{
			if ( (strlen($CalLCID)>15) and ($secondS > 0))
				{
				$stmt="SELECT count(*) FROM vicidial_auto_calls where callerid='$CalLCID';";
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02013',$user,$server_ip,$session_name,$one_mysql_log);}
				$rowx=mysqli_fetch_row($rslt);
				if ($format=='debug') {echo "\n<!-- $rowx[0]|$stmt -->";}
				if ($rowx[0] > 0)
					{
					#############################################
					##### START QUEUEMETRICS LOGGING LOOKUP #####
					$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_pe_phone_append,queuemetrics_socket,queuemetrics_socket_url FROM system_settings;";
					$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02014',$user,$server_ip,$session_name,$one_mysql_log);}
					if ($format=='debug') {echo "\n<!-- $rowx[0]|$stmt -->";}
					$qm_conf_ct = mysqli_num_rows($rslt);
					$i=0;
					while ($i < $qm_conf_ct)
						{
						$row=mysqli_fetch_row($rslt);
						$enable_queuemetrics_logging =	$row[0];
						$queuemetrics_server_ip	=		$row[1];
						$queuemetrics_dbname =			$row[2];
						$queuemetrics_login	=			$row[3];
						$queuemetrics_pass =			$row[4];
						$queuemetrics_log_id =			$row[5];
						$queuemetrics_pe_phone_append = $row[6];
						$queuemetrics_socket =			$row[7];
						$queuemetrics_socket_url =		$row[8];
						$i++;
						}
					##### END QUEUEMETRICS LOGGING LOOKUP #####
					###########################################
					if ($enable_queuemetrics_logging > 0)
						{
						$linkB=mysqli_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
						if (!$linkB) {die(_QXZ("Could not connect: ")."$queuemetrics_server_ip|$queuemetrics_login" . mysqli_connect_error());}
						mysqli_select_db($linkB, "$queuemetrics_dbname");

						$stmt="SELECT count(*) from queue_log where call_id='$CalLCID' and verb='CONNECT';";
						$rslt=mysql_to_mysqli($stmt, $linkB);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02015',$user,$server_ip,$session_name,$one_mysql_log);}
						$VAC_cn_ct = mysqli_num_rows($rslt);
						if ($VAC_cn_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$caller_connect	= $row[0];
							}
						if ($format=='debug') {echo "\n<!-- $caller_connect|$stmt -->";}
						if ($caller_connect > 0)
							{
							$CLqueue_position='1';
							### grab call lead information needed for QM logging
							$stmt="SELECT auto_call_id,lead_id,phone_number,status,campaign_id,phone_code,alt_dial,stage,callerid,uniqueid,queue_position from vicidial_auto_calls where callerid='$CalLCID' order by call_time limit 1;";
							$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02016',$user,$server_ip,$session_name,$one_mysql_log);}
							$VAC_qm_ct = mysqli_num_rows($rslt);
							if ($VAC_qm_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$auto_call_id =			$row[0];
								$CLlead_id =			$row[1];
								$CLphone_number =		$row[2];
								$CLstatus =				$row[3];
								$CLcampaign_id =		$row[4];
								$CLphone_code =			$row[5];
								$CLalt_dial =			$row[6];
								$CLstage =				$row[7];
								$CLcallerid =			$row[8];
								$CLuniqueid =			$row[9];
								$CLqueue_position =		$row[10];
								}
							if ($format=='debug') {echo "\n<!-- $CLcampaign_id|$stmt -->";}

							$CLstage = preg_replace("/.*-/",'',$CLstage);
							if (strlen($CLstage) < 1) {$CLstage=0;}

							$stmt="SELECT count(*) from queue_log where call_id='$CalLCID' and verb='COMPLETECALLER' and queue='$CLcampaign_id';";
							$rslt=mysql_to_mysqli($stmt, $linkB);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02017',$user,$server_ip,$session_name,$one_mysql_log);}
							$VAC_cc_ct = mysqli_num_rows($rslt);
							if ($VAC_cc_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$caller_complete	= $row[0];
								}
							if ($format=='debug') {echo "\n<!-- $caller_complete|$stmt -->";}

							if ($caller_complete < 1)
								{
								$time_id=0;
								$stmt="SELECT time_id from queue_log where call_id='$CalLCID' and verb IN('ENTERQUEUE','CALLOUTBOUND') and queue='$CLcampaign_id';";
								$rslt=mysql_to_mysqli($stmt, $linkB);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02018',$user,$server_ip,$session_name,$one_mysql_log);}
								$VAC_eq_ct = mysqli_num_rows($rslt);
								if ($VAC_eq_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$time_id	= $row[0];
									}
								$StarTtime = date("U");
								if ($time_id > 100000) 
									{$secondS = ($StarTtime - $time_id);}

								$data4SQL='';
								$data4SS='';
								$stmt="SELECT queuemetrics_phone_environment FROM vicidial_campaigns where campaign_id='$log_campaign' and queuemetrics_phone_environment!='';";
								$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02116',$user,$server_ip,$session_name,$one_mysql_log);}
								if ($DB) {echo "$stmt\n";}
								$cqpe_ct = mysqli_num_rows($rslt);
								if ($cqpe_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$pe_append='';
									if ( ($queuemetrics_pe_phone_append > 0) and (strlen($row[0])>0) )
										{$pe_append = "-$qm_extension";}
									$data4SQL = ",data4='$row[0]$pe_append'";
									$data4SS = "&data4=$row[0]$pe_append";
									}

								if ($format=='debug') {echo "\n<!-- $caller_complete|$stmt -->";}
								$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='$CalLCID',queue='$CLcampaign_id',agent='Agent/$user',verb='COMPLETEAGENT',data1='$CLstage',data2='$secondS',data3='$CLqueue_position',serverid='$queuemetrics_log_id' $data4SQL;";
								$rslt=mysql_to_mysqli($stmt, $linkB);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02019',$user,$server_ip,$session_name,$one_mysql_log);}
								$affected_rows = mysqli_affected_rows($linkB);
								if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}

								if ( ($queuemetrics_socket == 'CONNECT_COMPLETE') and (strlen($queuemetrics_socket_url) > 10) )
									{
									if (preg_match("/--A--/",$queuemetrics_socket_url))
										{
										##### grab the data from vicidial_list for the lead_id
										$stmt="SELECT vendor_lead_code,list_id,phone_code,phone_number,title,first_name,middle_initial,last_name,postal_code FROM vicidial_list where lead_id='$CLlead_id' LIMIT 1;";
										$rslt=mysql_to_mysqli($stmt, $link);
											if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02128',$user,$server_ip,$session_name,$one_mysql_log);}
										if ($DB) {echo "$stmt\n";}
										$list_lead_ct = mysqli_num_rows($rslt);
										if ($list_lead_ct > 0)
											{
											$row=mysqli_fetch_row($rslt);
											$vendor_id		= urlencode(trim($row[0]));
											$list_id		= urlencode(trim($row[1]));
											$phone_code		= urlencode(trim($row[2]));
											$phone_number	= urlencode(trim($row[3]));
											$title			= urlencode(trim($row[4]));
											$first_name		= urlencode(trim($row[5]));
											$middle_initial	= urlencode(trim($row[6]));
											$last_name		= urlencode(trim($row[7]));
											$postal_code	= urlencode(trim($row[8]));
											}
										$queuemetrics_socket_url = preg_replace('/^VAR/','',$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--lead_id--B--/i',"$lead_id",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--vendor_id--B--/i',"$vendor_id",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--vendor_lead_code--B--/i',"$vendor_id",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--list_id--B--/i',"$list_id",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--phone_number--B--/i',"$phone_number",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--title--B--/i',"$title",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--first_name--B--/i',"$first_name",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--middle_initial--B--/i',"$middle_initial",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--last_name--B--/i',"$last_name",$queuemetrics_socket_url);
										$queuemetrics_socket_url = preg_replace('/--A--postal_code--B--/i',"$postal_code",$queuemetrics_socket_url);
										}
									$socket_send_data_begin='?';
									$socket_send_data = "time_id=$StarTtime&call_id=$CalLCID&queue=$CLcampaign_id&agent=Agent/$user&verb=COMPLETEAGENT&data1=$CLstage&data2=$secondS&data3=$CLqueue_position$data4SS";
									if (preg_match("/\?/",$queuemetrics_socket_url))
										{$socket_send_data_begin='&';}
									### send queue_log data to the queuemetrics_socket_url ###
									if ($DB > 0) {echo "$queuemetrics_socket_url$socket_send_data_begin$socket_send_data<BR>\n";}
									$SCUfile = file("$queuemetrics_socket_url$socket_send_data_begin$socket_send_data");
									if ($DB > 0) {echo "$SCUfile[0]<BR>\n";}
									}
								}
							}
						}
					}
				}

			$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$call_server_ip','','Hangup','$queryCID','Channel: $channel','','','','','','','','','');";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02020',$user,$server_ip,$session_name,$one_mysql_log);}
			echo _QXZ("Hangup command sent for Channel %1s on %2s",0,'',$channel,$call_server_ip)."\n";
			}
		$stage .= " $channel $call_server_ip";
		}
	}



######################
# ACTION=Redirect, RedirectName, RedirectNameVmail, RedirectToPark, RedirectFromPark, RedirectVD, RedirectXtra, RedirectXtraCX
# - insert Redirect Manager statement using extensions name
######################
if (strlen($stage)<1) {$stage=$ACTION;}
if ($ACTION=="RedirectVD")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($campaign)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($uniqueid)<2) or (strlen($lead_id)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n";
		echo _QXZ("auto_dial_level %1s must be set",0,'',$auto_dial_level)."\n";
		echo _QXZ("campaign %1s must be set",0,'',$campaign)."\n";
		echo _QXZ("uniqueid %1s must be set",0,'',$uniqueid)."\n";
		echo _QXZ("lead_id %1s must be set",0,'',$lead_id)."\n\n";
		echo _QXZ("RedirectVD Action not sent")."\n";
		}
	else
		{
		if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
		$stmt = "SELECT count(*) from vicidial_campaigns where campaign_id='$campaign' and campaign_allow_inbound='Y';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02021',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "SELECT count(*) from vicidial_auto_calls where lead_id='$lead_id' and callerid LIKE \"Y%\";";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02161',$user,$server_ip,$session_name,$one_mysql_log);}
				$rowx=mysqli_fetch_row($rslt);
			if ($rowx[0] > 0)
				{
			$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));
			$stmt = "UPDATE vicidial_closer_log set end_epoch='$StarTtime', length_in_sec=(queue_seconds + $secondS),status='XFER' where lead_id=$lead_id and call_date > \"$four_hours_ago\" order by closecallid desc limit 1;";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02022',$user,$server_ip,$session_name,$one_mysql_log);}
			}
			}

		$stmt = "UPDATE vicidial_log set end_epoch='$StarTtime', length_in_sec='$secondS',status='XFER' where uniqueid='$uniqueid';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02023',$user,$server_ip,$session_name,$one_mysql_log);}

		if ($nodeletevdac < 1)
			{
			$stmt = "DELETE from vicidial_auto_calls where uniqueid='$uniqueid';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02024',$user,$server_ip,$session_name,$one_mysql_log);}
			}

		if ($customerparked > 0)
			{
			$parked_sec=0;
			$stmt = "SELECT UNIX_TIMESTAMP(parked_time) FROM park_log where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' and (parked_sec < 1 or grab_time is NULL) order by parked_time desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02133',$user,$server_ip,$session_name,$one_mysql_log);}
			$VAC_pl_ct = mysqli_num_rows($rslt);
			if ($VAC_pl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$parked_sec	= ($StarTtime - $row[0]);

				$stmt = "UPDATE park_log SET status='GRABBED',grab_time='$NOW_TIME',parked_sec='$parked_sec' where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' order by parked_time desc limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02134',$user,$server_ip,$session_name,$one_mysql_log);}
				}
			}

		if (strlen($preset_name) > 0)
			{
			$stmt = "INSERT INTO user_call_log (user,call_date,call_type,server_ip,phone_number,number_dialed,lead_id,preset_name,campaign_id) values('$user','$NOW_TIME','BLIND_XFER','$server_ip','$exten','$channel',$lead_id,'$preset_name','$campaign')";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02095',$user,$server_ip,$session_name,$one_mysql_log);}

			$stmt = "SELECT count(*) from vicidial_xfer_stats where campaign_id='$campaign' and preset_name='$preset_name';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02096',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "UPDATE vicidial_xfer_stats SET xfer_count=(xfer_count+1) where campaign_id='$campaign' and preset_name='$preset_name';";
				}
			else
				{
				$stmt = "INSERT INTO vicidial_xfer_stats SET campaign_id='$campaign',preset_name='$preset_name',xfer_count='1';";
				}
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02097',$user,$server_ip,$session_name,$one_mysql_log);}
			}

		$ACTION="Redirect";
		}
	}

if ($ACTION=="RedirectToPark")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($extenName)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($parkedby)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("extenName %1s must be set",0,'',$extenName)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n";
		echo _QXZ("parkedby %1s must be set",0,'',$parkedby)."\n\n";
		echo _QXZ("RedirectToPark Action not sent")."\n";
		$stage .= " ERROR $channel $queryCID $exten $ext_context $extenName $ext_priority $parkedby";
		}
	else
		{
		if ($stage!="2NDXfeR")
			{
			if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
			$stmt = "INSERT INTO parked_channels values('$channel','$server_ip','$CalLCID','$extenName','$parkedby','$NOW_TIME');";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02025',$user,$server_ip,$session_name,$one_mysql_log);}
			$ACTION="Redirect";


			$stmt = "INSERT INTO park_log SET uniqueid='$uniqueid',status='PARKED',channel='$channel',channel_group='$campaign',server_ip='$server_ip',parked_time='$NOW_TIME',parked_sec=0,extension='$CalLCID',user='$user',lead_id=$lead_id,campaign_id='$group_id';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02098',$user,$server_ip,$session_name,$one_mysql_log);}


			#############################################
			##### START QUEUEMETRICS LOGGING LOOKUP #####
			$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_record_hold FROM system_settings;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02099',$user,$server_ip,$session_name,$one_mysql_log);}
			if ($format=='debug') {echo "\n<!-- $rowx[0]|$stmt -->";}
			$qm_conf_ct = mysqli_num_rows($rslt);
			$i=0;
			while ($i < $qm_conf_ct)
				{
				$row=mysqli_fetch_row($rslt);
				$enable_queuemetrics_logging =	$row[0];
				$queuemetrics_server_ip	=		$row[1];
				$queuemetrics_dbname =			$row[2];
				$queuemetrics_login	=			$row[3];
				$queuemetrics_pass =			$row[4];
				$queuemetrics_log_id =			$row[5];
				$queuemetrics_record_hold =		$row[6];
				$i++;
				}
			##### END QUEUEMETRICS LOGGING LOOKUP #####
			###########################################
			if ($enable_queuemetrics_logging > 0)
				{
				$linkB=mysqli_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
				if (!$linkB) {die(_QXZ("Could not connect: ")."$queuemetrics_server_ip|$queuemetrics_login" . mysqli_connect_error());}
				mysqli_select_db($linkB, "$queuemetrics_dbname");

				$time_id=0;
				$secondS=0;
				$stmt="SELECT time_id,queue,agent from queue_log where call_id='$CalLCID' and verb='CONNECT' order by time_id desc limit 1;";
				$rslt=mysql_to_mysqli($stmt, $linkB);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02100',$user,$server_ip,$session_name,$one_mysql_log);}
				$VAC_eq_ct = mysqli_num_rows($rslt);
				if ($VAC_eq_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$time_id =	$row[0];
					$queue =	$row[1];
					$agent =	$row[2];
					}
				$StarTtime = date("U");
				if ($time_id > 100000) 
					{$secondS = ($StarTtime - $time_id);}

				if ($VAC_eq_ct > 0)
					{
					$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='$CalLCID',queue='$queue',agent='Agent/$user',verb='CALLERONHOLD',data1='PARK',serverid='$queuemetrics_log_id';";
					$rslt=mysql_to_mysqli($stmt, $linkB);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02101',$user,$server_ip,$session_name,$one_mysql_log);}
					$affected_rows = mysqli_affected_rows($linkB);
					if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}

					if ($queuemetrics_record_hold > 0)
						{
						$stmt = "INSERT INTO record_tags SET call_id='$CalLCID',record_title='Hold',visible='1',color='255',time='$secondS',duration='0',message='$user|$NOW_TIME';";
						$rslt=mysql_to_mysqli($stmt, $linkB);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02120',$user,$server_ip,$session_name,$one_mysql_log);}
						$affected_rows = mysqli_affected_rows($linkB);
						if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}
						}
					}
				}
			}
	#	$fp = fopen ("./vicidial_debug.txt", "a");
	#	fwrite ($fp, "$NOW_TIME|MS_LOG_0|$queryCID|$stmt|\n");
	#	fclose($fp);
		}

	$stmt="UPDATE vicidial_live_agents SET external_park='' where user='$user';";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02086',$user,$server_ip,$session_name,$one_mysql_log);}
	}

if ($ACTION=="RedirectFromPark")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("RedirectFromPark Action not sent")."\n";
		$stage .= " ERROR $channel $queryCID $exten $ext_context $ext_priority";
		}
	else
		{
		if ($stage!="2NDXfeR")
			{
			if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
			$stmt = "INSERT INTO parked_channels_recent SET server_ip='$server_ip', channel='$channel', channel_group='$CalLCID', park_end_time=NOW();";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02143',$user,$server_ip,$session_name,$one_mysql_log);}

			$stmt = "DELETE FROM parked_channels where server_ip='$server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02026',$user,$server_ip,$session_name,$one_mysql_log);}
			$ACTION="Redirect";

			$parked_sec=0;
			$stmt = "SELECT UNIX_TIMESTAMP(parked_time) FROM park_log where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' and (parked_sec < 1 or grab_time is NULL) order by parked_time desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02102',$user,$server_ip,$session_name,$one_mysql_log);}
			$VAC_pl_ct = mysqli_num_rows($rslt);
			if ($VAC_pl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$parked_sec	= ($StarTtime - $row[0]);

				$stmt = "UPDATE park_log SET status='GRABBED',grab_time='$NOW_TIME',parked_sec='$parked_sec' where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' order by parked_time desc limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02103',$user,$server_ip,$session_name,$one_mysql_log);}

				#############################################
				##### START QUEUEMETRICS LOGGING LOOKUP #####
				$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_record_hold FROM system_settings;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02104',$user,$server_ip,$session_name,$one_mysql_log);}
				if ($format=='debug') {echo "\n<!-- $rowx[0]|$stmt -->";}
				$qm_conf_ct = mysqli_num_rows($rslt);
				$i=0;
				while ($i < $qm_conf_ct)
					{
					$row=mysqli_fetch_row($rslt);
					$enable_queuemetrics_logging =	$row[0];
					$queuemetrics_server_ip	=		$row[1];
					$queuemetrics_dbname =			$row[2];
					$queuemetrics_login	=			$row[3];
					$queuemetrics_pass =			$row[4];
					$queuemetrics_log_id =			$row[5];
					$queuemetrics_record_hold =		$row[6];
					$i++;
					}
				##### END QUEUEMETRICS LOGGING LOOKUP #####
				###########################################
				if ($enable_queuemetrics_logging > 0)
					{
					$linkB=mysqli_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
					if (!$linkB) {die(_QXZ("Could not connect: ")."$queuemetrics_server_ip|$queuemetrics_login" . mysqli_connect_error());}
					mysqli_select_db($linkB, "$queuemetrics_dbname");

					$time_id=0;
					$secondS=0;
					$stmt="SELECT time_id,queue,agent from queue_log where call_id='$CalLCID' and verb='CONNECT' order by time_id desc limit 1;";
					$rslt=mysql_to_mysqli($stmt, $linkB);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02105',$user,$server_ip,$session_name,$one_mysql_log);}
					$VAC_eq_ct = mysqli_num_rows($rslt);
					if ($VAC_eq_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$time_id =	$row[0];
						$queue =	$row[1];
						$agent =	$row[2];
						}
					$StarTtime = date("U");
					if ($time_id > 100000) 
						{$secondS = ($StarTtime - $time_id);}

					if ($VAC_eq_ct > 0)
						{
						$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='$CalLCID',queue='$queue',agent='Agent/$user',verb='CALLEROFFHOLD',data1='$parked_sec',data2='PARK',serverid='$queuemetrics_log_id';";
						$rslt=mysql_to_mysqli($stmt, $linkB);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02106',$user,$server_ip,$session_name,$one_mysql_log);}
						$affected_rows = mysqli_affected_rows($linkB);
						if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}

						if ($queuemetrics_record_hold > 0)
							{
							$hold_start_time_id=0;
							$stmt="SELECT time from record_tags where call_id='$CalLCID' and record_title='Hold' and duration=0;";
							$rslt=mysql_to_mysqli($stmt, $linkB);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02121',$user,$server_ip,$session_name,$one_mysql_log);}
							$VAC_eq_ct = mysqli_num_rows($rslt);
							if ($VAC_eq_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$hold_start_time_id =	$row[0];
								}
							$total_hold_time = ($secondS - $hold_start_time_id);

							$stmt = "INSERT INTO record_tags SET call_id='$CalLCID',record_title='Unhold',visible='1',color='255',time='$secondS',duration='$total_hold_time',message='$user|$NOW_TIME';";
							$rslt=mysql_to_mysqli($stmt, $linkB);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02122',$user,$server_ip,$session_name,$one_mysql_log);}
							$affected_rows = mysqli_affected_rows($linkB);
							if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}

							$stmt = "UPDATE record_tags SET duration='$total_hold_time' where call_id='$CalLCID' and record_title='Hold' and duration=0;";
							$rslt=mysql_to_mysqli($stmt, $linkB);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02123',$user,$server_ip,$session_name,$one_mysql_log);}
							$affected_rows = mysqli_affected_rows($linkB);
							if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}
							}
						}
					}
				}
			}
		}

	$stmt="UPDATE vicidial_live_agents SET external_park='' where user='$user';";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02087',$user,$server_ip,$session_name,$one_mysql_log);}
	}

if ($ACTION=="RedirectToParkIVR")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($extenName)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($parkedby)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("extenName %1s must be set",0,'',$extenName)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n";
		echo _QXZ("parkedby %1s must be set",0,'',$parkedby)."\n";
		echo _QXZ("RedirectToPark Action not sent")."\n";
		$stage .= " ERROR $channel $queryCID $exten $ext_context $extenName $ext_priority $parkedby";
		}
	else
		{
		if ($stage!="2NDXfeR")
			{
			if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
			$stmt = "INSERT INTO parked_channels values('$channel','$server_ip','$CalLCID','$extenName','$parkedby','$NOW_TIME');";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02025',$user,$server_ip,$session_name,$one_mysql_log);}
			$ACTION="Redirect";

			$stmt = "UPDATE vicidial_auto_calls SET extension='PARK_IVR' where callerid='$CalLCID' limit 1;";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02088',$user,$server_ip,$session_name,$one_mysql_log);}

			$stmt = "INSERT INTO park_log SET uniqueid='$uniqueid',status='IVRPARKED',channel='$channel',channel_group='$campaign',server_ip='$server_ip',parked_time='$NOW_TIME',parked_sec=0,extension='$CalLCID',user='$user',lead_id=$lead_id,campaign_id='$group_id';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02107',$user,$server_ip,$session_name,$one_mysql_log);}

			#############################################
			##### START QUEUEMETRICS LOGGING LOOKUP #####
			$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_record_hold FROM system_settings;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02108',$user,$server_ip,$session_name,$one_mysql_log);}
			if ($format=='debug') {echo "\n<!-- $rowx[0]|$stmt -->";}
			$qm_conf_ct = mysqli_num_rows($rslt);
			$i=0;
			while ($i < $qm_conf_ct)
				{
				$row=mysqli_fetch_row($rslt);
				$enable_queuemetrics_logging =	$row[0];
				$queuemetrics_server_ip	=		$row[1];
				$queuemetrics_dbname =			$row[2];
				$queuemetrics_login	=			$row[3];
				$queuemetrics_pass =			$row[4];
				$queuemetrics_log_id =			$row[5];
				$queuemetrics_record_hold =		$row[6];
				$i++;
				}
			##### END QUEUEMETRICS LOGGING LOOKUP #####
			###########################################
			if ($enable_queuemetrics_logging > 0)
				{
				$linkB=mysqli_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
				if (!$linkB) {die(_QXZ("Could not connect: ")."$queuemetrics_server_ip|$queuemetrics_login" . mysqli_connect_error());}
				mysqli_select_db($linkB, "$queuemetrics_dbname");

				$time_id=0;
				$secondS=0;
				$stmt="SELECT time_id,queue,agent from queue_log where call_id='$CalLCID' and verb='CONNECT' order by time_id desc limit 1;";
				$rslt=mysql_to_mysqli($stmt, $linkB);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02109',$user,$server_ip,$session_name,$one_mysql_log);}
				$VAC_eq_ct = mysqli_num_rows($rslt);
				if ($VAC_eq_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$time_id =	$row[0];
					$queue =	$row[1];
					$agent =	$row[2];
					}
				$StarTtime = date("U");
				if ($time_id > 100000) 
					{$secondS = ($StarTtime - $time_id);}

				if ($VAC_eq_ct > 0)
					{
					$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='$CalLCID',queue='$queue',agent='Agent/$user',verb='CALLERONHOLD',data1='IVRPARK',serverid='$queuemetrics_log_id';";
					$rslt=mysql_to_mysqli($stmt, $linkB);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02110',$user,$server_ip,$session_name,$one_mysql_log);}
					$affected_rows = mysqli_affected_rows($linkB);
					if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}

					if ($queuemetrics_record_hold > 0)
						{
						$stmt = "INSERT INTO record_tags SET call_id='$CalLCID',record_title='Hold',visible='1',color='255',time='$secondS',duration='0',message='$user|$NOW_TIME';";
						$rslt=mysql_to_mysqli($stmt, $linkB);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02124',$user,$server_ip,$session_name,$one_mysql_log);}
						$affected_rows = mysqli_affected_rows($linkB);
						if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}
						}
					}
				}
		#	$fp = fopen ("./vicidial_debug.txt", "a");
		#	fwrite ($fp, "$NOW_TIME|MS_LOG_0|$queryCID|$stmt|\n");
		#	fclose($fp);
			}
		}

	$stmt="UPDATE vicidial_live_agents SET external_park='' where user='$user';";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02089',$user,$server_ip,$session_name,$one_mysql_log);}
	}

if ($ACTION=="RedirectFromParkIVR")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("RedirectFromPark Action not sent")."\n";
		$stage .= " ERROR $channel $queryCID $exten $ext_context $ext_priority";
		}
	else
		{
		if ($stage!="2NDXfeR")
			{
			if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
			$stmt = "INSERT INTO parked_channels_recent SET server_ip='$server_ip', channel='$channel', channel_group='$CalLCID', park_end_time=NOW();";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02144',$user,$server_ip,$session_name,$one_mysql_log);}

			$stmt = "DELETE FROM parked_channels where server_ip='$server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02026',$user,$server_ip,$session_name,$one_mysql_log);}
			$ACTION="Redirect";

			$stmt = "UPDATE vicidial_auto_calls SET extension='' where callerid='$CalLCID' limit 1;";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02090',$user,$server_ip,$session_name,$one_mysql_log);}

			$parked_sec=0;
			$stmt = "SELECT UNIX_TIMESTAMP(parked_time) FROM park_log where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' and (parked_sec < 1 or grab_time is NULL) order by parked_time desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02111',$user,$server_ip,$session_name,$one_mysql_log);}
			$VAC_pl_ct = mysqli_num_rows($rslt);
			if ($VAC_pl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$parked_sec	= ($StarTtime - $row[0]);

				$stmt = "UPDATE park_log SET status='GRABBEDIVR',grab_time='$NOW_TIME',parked_sec='$parked_sec' where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' order by parked_time desc limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02112',$user,$server_ip,$session_name,$one_mysql_log);}

				#############################################
				##### START QUEUEMETRICS LOGGING LOOKUP #####
				$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_record_hold FROM system_settings;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02113',$user,$server_ip,$session_name,$one_mysql_log);}
				if ($format=='debug') {echo "\n<!-- $rowx[0]|$stmt -->";}
				$qm_conf_ct = mysqli_num_rows($rslt);
				$i=0;
				while ($i < $qm_conf_ct)
					{
					$row=mysqli_fetch_row($rslt);
					$enable_queuemetrics_logging =	$row[0];
					$queuemetrics_server_ip	=		$row[1];
					$queuemetrics_dbname =			$row[2];
					$queuemetrics_login	=			$row[3];
					$queuemetrics_pass =			$row[4];
					$queuemetrics_log_id =			$row[5];
					$queuemetrics_record_hold =		$row[6];
					$i++;
					}
				##### END QUEUEMETRICS LOGGING LOOKUP #####
				###########################################
				if ($enable_queuemetrics_logging > 0)
					{
					$linkB=mysqli_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
					mysqli_select_db($linkB, "$queuemetrics_dbname");

					$time_id=0;
					$secondS=0;
					$stmt="SELECT time_id,queue,agent from queue_log where call_id='$CalLCID' and verb='CONNECT' order by time_id desc limit 1;";
					$rslt=mysql_to_mysqli($stmt, $linkB);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02114',$user,$server_ip,$session_name,$one_mysql_log);}
					$VAC_eq_ct = mysqli_num_rows($rslt);
					if ($VAC_eq_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$time_id =	$row[0];
						$queue =	$row[1];
						$agent =	$row[2];
						}
					$StarTtime = date("U");
					if ($time_id > 100000) 
						{$secondS = ($StarTtime - $time_id);}

					if ($VAC_eq_ct > 0)
						{
						$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='$CalLCID',queue='$queue',agent='Agent/$user',verb='CALLEROFFHOLD',data1='$parked_sec',data2='IVRPARK',serverid='$queuemetrics_log_id';";
						$rslt=mysql_to_mysqli($stmt, $linkB);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02115',$user,$server_ip,$session_name,$one_mysql_log);}
						$affected_rows = mysqli_affected_rows($linkB);
						if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}

						if ($queuemetrics_record_hold > 0)
							{
							$hold_start_time_id=0;
							$stmt="SELECT time from record_tags where call_id='$CalLCID' and record_title='Hold' and duration=0;";
							$rslt=mysql_to_mysqli($stmt, $linkB);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02125',$user,$server_ip,$session_name,$one_mysql_log);}
							$VAC_eq_ct = mysqli_num_rows($rslt);
							if ($VAC_eq_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$hold_start_time_id =	$row[0];
								}
							$total_hold_time = ($secondS - $hold_start_time_id);

							$stmt = "INSERT INTO record_tags SET call_id='$CalLCID',record_title='Unhold',visible='1',color='255',time='$secondS',duration='$total_hold_time',message='$user|$NOW_TIME';";
							$rslt=mysql_to_mysqli($stmt, $linkB);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02126',$user,$server_ip,$session_name,$one_mysql_log);}
							$affected_rows = mysqli_affected_rows($linkB);
							if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}

							$stmt = "UPDATE record_tags SET duration='$total_hold_time' where call_id='$CalLCID' and record_title='Hold' and duration=0;";
							$rslt=mysql_to_mysqli($stmt, $linkB);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$linkB,$mel,$stmt,'02127',$user,$server_ip,$session_name,$one_mysql_log);}
							$affected_rows = mysqli_affected_rows($linkB);
							if ($format=='debug') {echo "\n<!-- $affected_rows|$stmt -->";}
							}
						}
					}
				}
			}
		}

		$stmt="UPDATE vicidial_live_agents SET external_park='' where user='$user';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02091',$user,$server_ip,$session_name,$one_mysql_log);}
	}


if ($ACTION=="RedirectToParkXfer")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($extenName)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($parkedby)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("extenName %1s must be set",0,'',$extenName)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n";
		echo _QXZ("parkedby %1s must be set",0,'',$parkedby)."\n";
		echo _QXZ("RedirectToParkXfer Action not sent")."\n";
		$stage .= " ERROR $channel $queryCID $exten $ext_context $extenName $ext_priority $parkedby";
		}
	else
		{
		if ($stage!="2NDXfeR")
			{
			if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
			$stmt = "INSERT INTO parked_channels values('$channel','$server_ip','$CalLCID','$extenName','$parkedby','$NOW_TIME');";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02141',$user,$server_ip,$session_name,$one_mysql_log);}
			$ACTION="Redirect";

			$stmt="UPDATE vicidial_live_agents SET external_park='' where user='$user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02091',$user,$server_ip,$session_name,$one_mysql_log);}

		#	$fp = fopen ("./vicidial_debug.txt", "a");
		#	fwrite ($fp, "$NOW_TIME|MS_LOG_0|$queryCID|$stmt|\n");
		#	fclose($fp);
			}
		}
	}

if ($ACTION=="RedirectFromParkXfer")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($exten)<1) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("RedirectFromParkXfer Action not sent")."\n";
		$stage .= " ERROR $channel $queryCID $exten $ext_context $ext_priority";
		}
	else
		{
		if ($stage!="2NDXfeR")
			{
			if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
			$stmt = "INSERT INTO parked_channels_recent SET server_ip='$server_ip', channel='$channel', channel_group='$CalLCID', park_end_time=NOW();";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02145',$user,$server_ip,$session_name,$one_mysql_log);}

			$stmt = "DELETE FROM parked_channels where server_ip='$server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02142',$user,$server_ip,$session_name,$one_mysql_log);}
			$ACTION="Redirect";

			$stmt="UPDATE vicidial_live_agents SET external_park='' where user='$user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02091',$user,$server_ip,$session_name,$one_mysql_log);}
			}
		}
	}


if ($ACTION=="RedirectName")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($extenName)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("extenName %1s must be set",0,'',$extenName)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("RedirectName Action not sent")."\n";
		}
	else
		{
		if ($customerparked > 0)
			{
			$parked_sec=0;
			$stmt = "SELECT UNIX_TIMESTAMP(parked_time) FROM park_log where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' and (parked_sec < 1 or grab_time is NULL) order by parked_time desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02135',$user,$server_ip,$session_name,$one_mysql_log);}
			$VAC_pl_ct = mysqli_num_rows($rslt);
			if ($VAC_pl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$parked_sec	= ($StarTtime - $row[0]);

				$stmt = "UPDATE park_log SET status='GRABBED',grab_time='$NOW_TIME',parked_sec='$parked_sec' where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' order by parked_time desc limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02136',$user,$server_ip,$session_name,$one_mysql_log);}
				}
			}
		$stmt="SELECT dialplan_number FROM phones where server_ip = '$server_ip' and extension='$extenName';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02027',$user,$server_ip,$session_name,$one_mysql_log);}
		$name_count = mysqli_num_rows($rslt);
		if ($name_count>0)
			{
			$row=mysqli_fetch_row($rslt);
			$exten = $row[0];
			$ACTION="Redirect";
			}
		}
	}

if ($ACTION=="RedirectNameVmail")
	{
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($extenName)<1)  or (strlen($exten)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("extenName %1s must be set",0,'',$extenName)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("RedirectNameVmail Action not sent")."\n";
		}
	else
		{
		if ($customerparked > 0)
			{
			$parked_sec=0;
			$stmt = "SELECT UNIX_TIMESTAMP(parked_time) FROM park_log where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' and (parked_sec < 1 or grab_time is NULL) order by parked_time desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02137',$user,$server_ip,$session_name,$one_mysql_log);}
			$VAC_pl_ct = mysqli_num_rows($rslt);
			if ($VAC_pl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$parked_sec	= ($StarTtime - $row[0]);

				$stmt = "UPDATE park_log SET status='GRABBED',grab_time='$NOW_TIME',parked_sec='$parked_sec' where uniqueid='$uniqueid' and server_ip='$server_ip' and extension='$CalLCID' order by parked_time desc limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02138',$user,$server_ip,$session_name,$one_mysql_log);}
				}
			}
		$stmt="SELECT voicemail_id FROM phones where server_ip = '$server_ip' and extension='$extenName';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02028',$user,$server_ip,$session_name,$one_mysql_log);}
		$name_count = mysqli_num_rows($rslt);
		if ($name_count>0)
			{
			$row=mysqli_fetch_row($rslt);
			$exten = "$exten$row[0]";
			$ACTION="Redirect";
			}
		}
	}





if ($ACTION=="RedirectXtraCXNeW")
	{
	$DBout='';
	$row='';   $rowx='';
	$channel_liveX=1;
	$channel_liveY=1;
	$conf_table = 'vicidial_conferences';
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($session_id)<3) or ( ( (strlen($extrachannel)<3) or (strlen($exten)<1) ) and (!preg_match("/NEXTAVAILABLE/",$exten)) ) )
		{
		$channel_liveX=0;
		$channel_liveY=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("ExtraChannel %1s must be greater than 2 characters",0,'',$extrachannel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("Redirect Action not sent")."\n";
		if (preg_match("/SECOND|FIRST|DEBUG/",$filename))
			{
			if ($WeBRooTWritablE > 0)
				{
				$fp = fopen ("./vicidial_debug.txt", "w");
			#	fwrite ($fp, "$NOW_TIME|RDCXC|$filename|$user|$campaign|$channel|$extrachannel|$queryCID|$exten|$ext_context|ext_priority|\n");
				fwrite ($fp, "$NOW_TIME|RDCXC|\n");
				fclose($fp);
				}
			}
		}
	else
		{
		if (preg_match("/NEXTAVAILABLE/",$exten))
			{
			# figure out which table to look in
			$stmtA="SELECT conf_engine FROM servers WHERE server_ip='$server_ip';";
				if ($format=='debug') {echo "\n<!-- $stmtA -->";}
			$rslt=mysql_to_mysqli($stmtA, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] == "CONFBRIDGE") { $conf_table = 'vicidial_confbridges'; }

			$stmtA="SELECT count(*) FROM $conf_table where server_ip='$server_ip' and ((extension='') or (extension is null)) and conf_exten != '$session_id';";
				if ($format=='debug') {echo "\n<!-- $stmtA -->";}
			$rslt=mysql_to_mysqli($stmtA, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmtA,'02029',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 1)
				{
				$stmtB="UPDATE $conf_table set extension='$protocol/$extension$NOWnum', leave_3way='0' where server_ip='$server_ip' and ((extension='') or (extension is null)) and conf_exten != '$session_id' limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmtB -->";}
				$rslt=mysql_to_mysqli($stmtB, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmtB,'02030',$user,$server_ip,$session_name,$one_mysql_log);}

				$stmtC="SELECT conf_exten from $conf_table where server_ip='$server_ip' and extension='$protocol/$extension$NOWnum' and conf_exten != '$session_id';";
					if ($format=='debug') {echo "\n<!-- $stmtC -->";}
				$rslt=mysql_to_mysqli($stmtC, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmtC,'02031',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				$exten = $row[0];

				if ( (preg_match("/^8300/i",$extension)) and ($protocol == 'Local') )
					{
					$extension = "$extension$user";
					}

				$stmtD="UPDATE $conf_table set extension='$protocol/$extension' where server_ip='$server_ip' and conf_exten='$exten' limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmtD -->";}
				$rslt=mysql_to_mysqli($stmtD, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmtD,'02032',$user,$server_ip,$session_name,$one_mysql_log);}

				$stmtE="UPDATE $conf_table set leave_3way='1', leave_3way_datetime='$NOW_TIME', extension='3WAY_$user' where server_ip='$server_ip' and conf_exten='$session_id';";
					if ($format=='debug') {echo "\n<!-- $stmtE -->";}
				$rslt=mysql_to_mysqli($stmtE, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmtE,'02033',$user,$server_ip,$session_name,$one_mysql_log);}

				$queryCID = "CXAR24$NOWnum";
				$stmtF="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $agentchannel','Context: $threeway_context','Exten: $exten','Priority: 1','CallerID: $queryCID','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmtF -->";}
				$rslt=mysql_to_mysqli($stmtF, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmtF,'02034',$user,$server_ip,$session_name,$one_mysql_log);}

				$stmtG="UPDATE vicidial_live_agents set conf_exten='$exten' where server_ip='$server_ip' and user='$user';";
					if ($format=='debug') {echo "\n<!-- $stmtG -->";}
				$rslt=mysql_to_mysqli($stmtG, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmtG,'02035',$user,$server_ip,$session_name,$one_mysql_log);}

				if ($auto_dial_level < 1)
					{
					$stmtH = "DELETE from vicidial_auto_calls where lead_id=$lead_id and callerid LIKE \"M%\";";
						if ($format=='debug') {echo "\n<!-- $stmtH -->";}
					$rslt=mysql_to_mysqli($stmtH, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmtH,'02036',$user,$server_ip,$session_name,$one_mysql_log);}
					}

			//	$fp = fopen ("./vicidial_debug_3way.txt", "a");
			//	fwrite ($fp, "$NOW_TIME|$filename|\n|$stmtA|\n|$stmtB|\n|$stmtC|\n|$stmtD|\n|$stmtE|\n|$stmtF|\n|$stmtG|\n|$stmtH|\n\n");
			//	fclose($fp);

				echo "NeWSessioN|$exten|\n";
				echo "|$stmtG|\n";

				$stage .= "|OLD $session_id|NEW $exten|";
				if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
				exit;
				}
			else
				{
				$channel_liveX=0;
				echo _QXZ("Cannot find empty $conf_table on %1s, Redirect command not inserted",0,'',$server_ip)."\n|$stmt|";
				if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "Cannot find empty conference on $server_ip";}
				$stage .= "|ERROR $server_ip|";
				}
			}

		if (strlen($call_server_ip)<7) {$call_server_ip = $server_ip;}

		$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02037',$user,$server_ip,$session_name,$one_mysql_log);}
		$row=mysqli_fetch_row($rslt);
		if ($row[0]==0)
			{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$call_server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02038',$user,$server_ip,$session_name,$one_mysql_log);}
			$rowx=mysqli_fetch_row($rslt);
			if ($rowx[0]==0)
				{
				$channel_liveX=0;
				echo _QXZ("Channel %1s is not live on %2s, Redirect command not inserted",0,'',$channel,$call_server_ip)."\n";
				if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "$channel is not live on $call_server_ip";}
				}	
			}
		$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$extrachannel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02039',$user,$server_ip,$session_name,$one_mysql_log);}
		$row=mysqli_fetch_row($rslt);
		if ($row[0]==0)
			{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$extrachannel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02040',$user,$server_ip,$session_name,$one_mysql_log);}
			$rowx=mysqli_fetch_row($rslt);
			if ($rowx[0]==0)
				{
				$channel_liveY=0;
				echo _QXZ("Channel %1s is not live on %2s, Redirect command not inserted",0,'',$channel,$server_ip)."\n";
				if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "$channel is not live on $server_ip";}
				}	
			}
		if ( ($channel_liveX==1) and ($channel_liveY==1) )
			{
			$stmt="SELECT count(*) FROM vicidial_live_agents where lead_id=$lead_id and user!='$user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02041',$user,$server_ip,$session_name,$one_mysql_log);}
			$rowx=mysqli_fetch_row($rslt);
			if ($rowx[0] < 1)
				{
				$channel_liveY=0;
				echo _QXZ("No Local agent to send call to, Redirect command not inserted")."\n";
				if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "No Local agent to send call to";}
				}	
			else
				{
				$stmt="SELECT server_ip,conf_exten,user FROM vicidial_live_agents where lead_id=$lead_id and user!='$user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02042',$user,$server_ip,$session_name,$one_mysql_log);}
				$rowx=mysqli_fetch_row($rslt);
				$dest_server_ip = $rowx[0];
				$dest_session_id = $rowx[1];
				$dest_user = $rowx[2];
				$S='*';

				$D_s_ip = explode('.', $dest_server_ip);
				if (strlen($D_s_ip[0])<2) {$D_s_ip[0] = "0$D_s_ip[0]";}
				if (strlen($D_s_ip[0])<3) {$D_s_ip[0] = "0$D_s_ip[0]";}
				if (strlen($D_s_ip[1])<2) {$D_s_ip[1] = "0$D_s_ip[1]";}
				if (strlen($D_s_ip[1])<3) {$D_s_ip[1] = "0$D_s_ip[1]";}
				if (strlen($D_s_ip[2])<2) {$D_s_ip[2] = "0$D_s_ip[2]";}
				if (strlen($D_s_ip[2])<3) {$D_s_ip[2] = "0$D_s_ip[2]";}
				if (strlen($D_s_ip[3])<2) {$D_s_ip[3] = "0$D_s_ip[3]";}
				if (strlen($D_s_ip[3])<3) {$D_s_ip[3] = "0$D_s_ip[3]";}
				$dest_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S$D_s_ip[3]$S$dest_session_id$S$lead_id$S$dest_user$S$phone_code$S$phone_number$S$campaign$S";

				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$call_server_ip','','Redirect','$queryCID','Channel: $channel','Context: $ext_context','Exten: $dest_dialstring','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02043',$user,$server_ip,$session_name,$one_mysql_log);}

				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Hangup','$queryCID','Channel: $extrachannel','','','','','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02044',$user,$server_ip,$session_name,$one_mysql_log);}

				echo _QXZ("RedirectXtraCX command sent for Channel %1s on %2s and",0,'',$channel,$call_server_ip)."\n";
				echo _QXZ("Hungup %1s on %2s",0,'',$extrachannel,$server_ip)."\n";
				if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "$channel on $call_server_ip, Hungup $extrachannel on $server_ip";}
				}
			}
		else
			{
			if ($channel_liveX==1)
			{$ACTION="Redirect";   $server_ip = $call_server_ip;}
			if ($channel_liveY==1)
			{$ACTION="Redirect";   $channel=$extrachannel;}
			if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "Changed to Redirect: $channel on $server_ip";}
			}

		if (preg_match("/SECOND|FIRST|DEBUG/",$filename))
			{
			if ($WeBRooTWritablE > 0)
				{
				$fp = fopen ("./vicidial_debug.txt", "w");
			#	fwrite ($fp, "$NOW_TIME|RDCXC|$filename|$user|$campaign|$DBout|\n");
				fwrite ($fp, "$NOW_TIME|RDCXC|\n");
				fclose($fp);
				}
			}
		}
	}





if ($ACTION=="RedirectXtraNeW")
	{
	if ($channel=="$extrachannel")
	{$ACTION="Redirect";}
	else
		{
		$row='';   $rowx='';
		$channel_liveX=1;
		$channel_liveY=1;
		$conf_table = 'vicidial_conferences';
		if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($ext_context)<1) or (strlen($ext_priority)<1) or (strlen($session_id)<3) or ( ( (strlen($extrachannel)<3) or (strlen($exten)<1) ) and (!preg_match("/NEXTAVAILABLE/",$exten)) ) )
			{
			$channel_liveX=0;
			$channel_liveY=0;
			echo _QXZ("One of these variables is not valid:")."\n";
			echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
			echo _QXZ("ExtraChannel %1s must be greater than 2 characters",0,'',$extrachannel)."\n";
			echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
			echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
			echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
			echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n";
			echo _QXZ("session_id %1s must be set",0,'',$session_id)."\n\n";
			echo _QXZ("Redirect Action not sent")."\n";

			if (preg_match("/SECOND|FIRST|DEBUG/",$filename))
				{
				if ($WeBRooTWritablE > 0)
					{
					$fp = fopen ("./vicidial_debug.txt", "w");
				#	fwrite ($fp, "$NOW_TIME|RDX|$filename|$user|$campaign|$channel|$extrachannel|$queryCID|$exten|$ext_context|ext_priority|$session_id|\n");
					fwrite ($fp, "$NOW_TIME|RDX|\n");
					fclose($fp);
					}
				}
			}
		else
			{
			if (preg_match("/NEXTAVAILABLE/",$exten))
				{
				# figure out which table to look in
	                        $stmtA="SELECT conf_engine FROM servers WHERE server_ip='$server_ip';";
	                                if ($format=='debug') {echo "\n<!-- $stmtA -->";}
	                        $rslt=mysql_to_mysqli($stmtA, $link);
	                        $row=mysqli_fetch_row($rslt);
	                        if ($row[0] == "CONFBRIDGE") { $conf_table = 'vicidial_confbridges'; }

				$stmt="SELECT count(*) FROM $conf_table where server_ip='$server_ip' and ((extension='') or (extension is null)) and conf_exten != '$session_id';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02045',$user,$server_ip,$session_name,$one_mysql_log);}
				$row=mysqli_fetch_row($rslt);
				if ($row[0] > 1)
					{
					$stmt="UPDATE $conf_table set extension='$protocol/$extension$NOWnum', leave_3way='0' where server_ip='$server_ip' and ((extension='') or (extension is null)) and conf_exten != '$session_id' limit 1;";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02046',$user,$server_ip,$session_name,$one_mysql_log);}

					$stmt="SELECT conf_exten from $conf_table where server_ip='$server_ip' and extension='$protocol/$extension$NOWnum' and conf_exten != '$session_id';";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02047',$user,$server_ip,$session_name,$one_mysql_log);}
					$row=mysqli_fetch_row($rslt);
					$exten = $row[0];

					$stmt="UPDATE $conf_table set extension='$protocol/$extension' where server_ip='$server_ip' and conf_exten='$exten' limit 1;";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02048',$user,$server_ip,$session_name,$one_mysql_log);}

					$stmt="UPDATE $conf_table set leave_3way='1', leave_3way_datetime='$NOW_TIME', extension='3WAY_$user' where server_ip='$server_ip' and conf_exten='$session_id';";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02049',$user,$server_ip,$session_name,$one_mysql_log);}

					$queryCID = "CXAR23$NOWnum";
					$stmtB="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $agentchannel','Context: $threeway_context','Exten: $exten','Priority: 1','CallerID: $queryCID','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmtB, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02050',$user,$server_ip,$session_name,$one_mysql_log);}

					$stmt="UPDATE vicidial_live_agents set conf_exten='$exten' where server_ip='$server_ip' and user='$user';";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02051',$user,$server_ip,$session_name,$one_mysql_log);}

					if ($auto_dial_level < 1)
						{
						$stmt = "DELETE from vicidial_auto_calls where lead_id=$lead_id and callerid LIKE \"M%\";";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02052',$user,$server_ip,$session_name,$one_mysql_log);}
						}

					echo "NeWSessioN|$exten|\n";
					echo "|$stmtB|\n";
					
					$stage .= "|OLD $session_id|NEW $exten|";
					if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
					exit;
					}
				else
					{
					$channel_liveX=0;
					echo "Cannot find empty $conf_table on $server_ip, Redirect command not inserted\n|$stmt|";
					if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "Cannot find empty conference on $server_ip";}
					$stage .= "|ERROR $server_ip|";
					}
				}

			if (strlen($call_server_ip)<7) {$call_server_ip = $server_ip;}

			$stmt="SELECT count(*) FROM live_channels where server_ip = '$call_server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02053',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			if ( ($row[0]==0) and (!preg_match("/SECOND/",$filename)) )
				{
				$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$call_server_ip' and channel='$channel';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02054',$user,$server_ip,$session_name,$one_mysql_log);}
				$rowx=mysqli_fetch_row($rslt);
				if ($rowx[0]==0)
					{
					$channel_liveX=0;
					echo _QXZ("Channel %1s is not live on %2s, Redirect command not inserted",0,'',$channel,$call_server_ip)."\n";
					if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "$channel is not live on $call_server_ip";}
					}	
				}
			$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$extrachannel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02055',$user,$server_ip,$session_name,$one_mysql_log);}
			$row=mysqli_fetch_row($rslt);
			if ( ($row[0]==0) and (!preg_match("/SECOND/",$filename)) )
				{
				$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$extrachannel';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02056',$user,$server_ip,$session_name,$one_mysql_log);}
				$rowx=mysqli_fetch_row($rslt);
				if ($rowx[0]==0)
					{
					$channel_liveY=0;
					echo _QXZ("Channel %1s is not live on %2s, Redirect command not inserted",0,'',$channel,$server_ip)."\n";
					if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "$channel is not live on $server_ip";}
					}	
				}
			if ( ($channel_liveX==1) and ($channel_liveY==1) )
				{
				if ( ($server_ip=="$call_server_ip") or (strlen($call_server_ip)<7) )
					{
					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $channel','ExtraChannel: $extrachannel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','CallerID: $queryCID','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02057',$user,$server_ip,$session_name,$one_mysql_log);}

					echo _QXZ("RedirectXtra command sent for Channel %1s and",0,'',$channel)."\n";
					echo _QXZ("ExtraChannel %1s",0,'',$extrachannel)."\n";
					echo _QXZ(" to %1s on %2s",0,'',$exten,$server_ip)."\n";
					if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "$channel and $extrachannel to $exten on $server_ip";}
					}
				else
					{
					$S='*';
					$D_s_ip = explode('.', $server_ip);
					if (strlen($D_s_ip[0])<2) {$D_s_ip[0] = "0$D_s_ip[0]";}
					if (strlen($D_s_ip[0])<3) {$D_s_ip[0] = "0$D_s_ip[0]";}
					if (strlen($D_s_ip[1])<2) {$D_s_ip[1] = "0$D_s_ip[1]";}
					if (strlen($D_s_ip[1])<3) {$D_s_ip[1] = "0$D_s_ip[1]";}
					if (strlen($D_s_ip[2])<2) {$D_s_ip[2] = "0$D_s_ip[2]";}
					if (strlen($D_s_ip[2])<3) {$D_s_ip[2] = "0$D_s_ip[2]";}
					if (strlen($D_s_ip[3])<2) {$D_s_ip[3] = "0$D_s_ip[3]";}
					if (strlen($D_s_ip[3])<3) {$D_s_ip[3] = "0$D_s_ip[3]";}
					$dest_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S$D_s_ip[3]$S$exten";

					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$call_server_ip','','Redirect','$queryCID','Channel: $channel','Context: $ext_context','Exten: $dest_dialstring','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02058',$user,$server_ip,$session_name,$one_mysql_log);}

					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $extrachannel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02059',$user,$server_ip,$session_name,$one_mysql_log);}

					echo _QXZ("RedirectXtra command sent for Channel %1s on %2s and",0,'',$channel,$call_server_ip)."\n";
					echo _QXZ("ExtraChannel %1s",0,'',$extrachannel)."\n";
					echo _QXZ(" to %1s on %2s",0,'',$exten,$server_ip)."\n";
					if (preg_match("/SECOND|FIRST|DEBUG/",$filename)) {$DBout .= "$channel/$call_server_ip and $extrachannel/$server_ip to $exten";}
					}
				}
			else
				{
				if ($channel_liveX==1)
				{$ACTION="Redirect";   $server_ip = $call_server_ip;}
				if ($channel_liveY==1)
				{$ACTION="Redirect";   $channel=$extrachannel;}
				}

			if (preg_match("/SECOND|FIRST|DEBUG/",$filename))
				{
				if ($WeBRooTWritablE > 0)
					{
					$fp = fopen ("./vicidial_debug.txt", "w");
				#	fwrite ($fp, "$NOW_TIME|RDX|$filename|$user|$campaign|$DBout|\n");
					fwrite ($fp, "$NOW_TIME|RDX|\n");
					fclose($fp);
					}
				}
			}
		}
	}





if ($ACTION=="Redirect")
	{
	### for manual dial VICIDIAL calls send the second attempt to transfer the call
	if ($stage=="2NDXfeR")
		{
		$local_DEF = 'Local/';
		$local_AMP = '@';
		$hangup_channel_prefix = "$local_DEF$session_id$local_AMP$ext_context";

		$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$hangup_channel_prefix%\";";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02060',$user,$server_ip,$session_name,$one_mysql_log);}
		$row=mysqli_fetch_row($rslt);
		if ($row > 0)
			{
			$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$hangup_channel_prefix%\";";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02061',$user,$server_ip,$session_name,$one_mysql_log);}
			$rowx=mysqli_fetch_row($rslt);
			$channel=$rowx[0];
			$channel = preg_replace("/1$/i","2",$channel);
			$queryCID = preg_replace("/^./i","Q",$queryCID);
			}
		}

	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($channel)<3) or (strlen($queryCID)<15)  or (strlen($exten)<1)  or (strlen($ext_context)<1)  or (strlen($ext_priority)<1) )
		{
		$channel_live=0;
		echo _QXZ("One of these variables is not valid:")."\n";
		echo _QXZ("Channel %1s must be greater than 2 characters",0,'',$channel)."\n";
		echo _QXZ("queryCID %1s must be greater than 14 characters",0,'',$queryCID)."\n";
		echo _QXZ("exten %1s must be set",0,'',$exten)."\n";
		echo _QXZ("ext_context %1s must be set",0,'',$ext_context)."\n";
		echo _QXZ("ext_priority %1s must be set",0,'',$ext_priority)."\n\n";
		echo _QXZ("Redirect Action not sent")."\n";
		}
	else
		{
		if (strlen($call_server_ip)>6) {$server_ip = $call_server_ip;}
		$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$channel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02062',$user,$server_ip,$session_name,$one_mysql_log);}
		$row=mysqli_fetch_row($rslt);
		if ($row[0]==0)
			{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02063',$user,$server_ip,$session_name,$one_mysql_log);}
			$rowx=mysqli_fetch_row($rslt);
			if ($rowx[0]==0)
				{
				$channel_live=0;
				echo _QXZ("Channel %1s is not live on %2s, Redirect command not inserted",0,'',$channel,$server_ip)."\n";
				}	
			}
		if ($channel_live==1)
			{
			$new_variable='';
			$new_channel=$channel;
			$new_exten=$exten;
			if ( (preg_match("/^900\d\d\*|^9900\d\d\*|^980\d\d\*|^9980\d\d\*|^8305888888888888\d90009|^8305888888888888\d98009/",$exten)) or (preg_match("/^Local\/900\d\d\*|^Local\/9900\d\d\*|^Local\/980\d\d\*|^Local\/9980\d\d\*|^Local\/8305888888888888\d90009|^Local\/8305888888888888\d98009/",$channel)) )
				{
				if (preg_match("/^900\d\d\*|^9900\d\d\*|^980\d\d\*|^9980\d\d\*|^8305888888888888\d90009|^8305888888888888\d98009/",$exten))
					{
					$new_source='manager_send_exten';
					$temp_exten = $exten;

					$stmt="INSERT INTO vicidial_long_extensions values('','$temp_exten',NOW(),'$new_source');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02147',$user,$server_ip,$session_name,$one_mysql_log);}
					$le_id = mysqli_insert_id($link);

					$new_exten = preg_replace("/\*.*/",'',$exten);
					$new_exten .= "**LEXTEN*$le_id";
					$new_variable = "Variable: _new_exten=$new_exten";
					}
				if (preg_match("/^Local\/900\d\d\*|^Local\/9900\d\d\*|^Local\/980\d\d\*|^Local\/9980\d\d\*|^Local\/8305888888888888\d90009|^Local\/8305888888888888\d98009/",$channel))
					{
					$new_source='manager_send_channel';
					$temp_exten = preg_replace("/^Local\//",'',$channel);
					$temp_context = preg_replace("/^.*\@/",'',$channel);

					$stmt="INSERT INTO vicidial_long_extensions values('','$temp_exten',NOW(),'$new_source');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02148',$user,$server_ip,$session_name,$one_mysql_log);}
					$le_id = mysqli_insert_id($link);

					$new_channel = preg_replace("/\*.*/",'',$temp_exten);
					$new_channel = "Local/$new_channel**LEXTEN*$le_id@$temp_context";
					$new_variable = "Variable: _new_channel=$new_channel";
					}
				}

			$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$queryCID','Channel: $new_channel','Context: $ext_context','Exten: $new_exten','Priority: $ext_priority','CallerID: $queryCID','','','','','');";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02064',$user,$server_ip,$session_name,$one_mysql_log);}

			echo _QXZ("Redirect command sent for Channel %1s on %2s",0,'',$channel,$server_ip)."\n";
			}
		$stage .= " $exten $channel $server_ip";
		}
	}



######################
# ACTION=Monitor or Stop Monitor  - insert Monitor/StopMonitor Manager statement to start recording on a channel
######################
if ( ($ACTION=="Monitor") || ($ACTION=="StopMonitor") )
	{
	if ($ACTION=="StopMonitor")
		{$SQLfile = "";}
	else
		{$SQLfile = "File: $filename";}

	$row='';   $rowx='';
	$channel_live=1;
	if ( (strlen($channel)<3) or (strlen($queryCID)<15) or (strlen($filename)<8) )
		{
		$channel_live=0;
		echo _QXZ("Channel %1s is not valid or queryCID %2s is not valid or filename: %3s is not valid, %4s command not inserted",0,'',$channel,$queryCID,$filename,$ACTION)."\n";
		}
	else
		{
		$stmt="SELECT count(*) FROM live_channels where server_ip = '$server_ip' and channel='$channel';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02065',$user,$server_ip,$session_name,$one_mysql_log);}
		$row=mysqli_fetch_row($rslt);
		if ($row[0]==0)
			{
			$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel='$channel';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02066',$user,$server_ip,$session_name,$one_mysql_log);}
			$rowx=mysqli_fetch_row($rslt);
			if ($rowx[0]==0)
				{
				$channel_live=0;
				echo _QXZ("Channel %1s is not live on %2s, %3s command not inserted",0,'',$channel,$server_ip,$ACTION)."\n";
				}	
			}
		if ($channel_live==1)
			{
			if ( ($ACTION=="Monitor") and (preg_match("/RECID/",$filename) ) )
				{
				$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename,lead_id,user) values('$channel','$server_ip','$exten','$NOW_TIME','$StarTtime','$filename',$lead_id,'$user')";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02130',$user,$server_ip,$session_name,$one_mysql_log);}
				$recording_id = mysqli_insert_id($link);

				$filename = preg_replace("/RECID/","$recording_id",$filename);

				$stmt = "UPDATE recording_log SET filename='$filename' where recording_id='$recording_id';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02131',$user,$server_ip,$session_name,$one_mysql_log);}

				$SQLfile = "File: $filename";

				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','$ACTION','$queryCID','Channel: $channel','$SQLfile','','','','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02132',$user,$server_ip,$session_name,$one_mysql_log);}

				echo _QXZ("%1s command sent for Channel %2s on %3s",0,'',$ACTION,$channel,$server_ip)."\nFilename: $filename\nRecorDing_ID: $recording_id\n";
				}
			else
				{
				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','$ACTION','$queryCID','Channel: $channel','$SQLfile','','','','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02067',$user,$server_ip,$session_name,$one_mysql_log);}

				if ($ACTION=="Monitor")
					{
					$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename,lead_id,user) values('$channel','$server_ip','$exten','$NOW_TIME','$StarTtime','$filename',$lead_id,'$user')";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02068',$user,$server_ip,$session_name,$one_mysql_log);}

					$stmt="SELECT recording_id FROM recording_log where filename='$filename' order by start_epoch desc;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02069',$user,$server_ip,$session_name,$one_mysql_log);}
					if ($DB) {echo "$stmt\n";}
					$row=mysqli_fetch_row($rslt);
					$recording_id = $row[0];
					}
				else
					{
					$stmt="SELECT recording_id,start_epoch FROM recording_log where filename='$filename' order by start_epoch desc;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02070',$user,$server_ip,$session_name,$one_mysql_log);}
					if ($DB) {echo "$stmt\n";}
					$rec_count = mysqli_num_rows($rslt);
						if ($rec_count>0)
						{
						$row=mysqli_fetch_row($rslt);
						$recording_id = $row[0];
						$start_time = $row[1];
						$length_in_sec = ($StarTtime - $start_time);
						$length_in_min = ($length_in_sec / 60);
						$length_in_min = sprintf("%8.2f", $length_in_min);

						$stmt = "UPDATE recording_log set end_time='$NOW_TIME',end_epoch='$StarTtime',length_in_sec=$length_in_sec,length_in_min='$length_in_min' where filename='$filename' order by start_epoch desc;";
							if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02071',$user,$server_ip,$session_name,$one_mysql_log);}
						}
					}
				echo _QXZ("%1s command sent for Channel %2s on %3s",0,'',$ACTION,$channel,$server_ip)."\nFilename: $filename\nRecorDing_ID: $recording_id\n";
				}
			}
		}
	}






######################
# ACTION=MonitorConf or StopMonitorConf  - insert Monitor/StopMonitor Manager statement to start recording on a conference
######################
if ( ($ACTION=="MonitorConf") || ($ACTION=="StopMonitorConf") )
	{
	$row='';   $rowx='';
	$channel_live=1;
	$uniqueidSQL='';

	if ( (($ACTION=="MonitorConf") && ((strlen($exten)<3) or (strlen($channel)<4) or (strlen($filename)<8))) || (($ACTION=="StopMonitorConf") && ((strlen($exten)<3) or (strlen($channel)<4) or (strlen($filename)<4))) )
		{
		$channel_live=0;
		echo _QXZ("Channel %1s is not valid or exten %2s is not valid or filename: %3s is not valid, %4s command not inserted",0,'',$channel,$exten,$filename,$ACTION)."\n";
		$stage .= " REC-Invalid $exten $filename $channel";
		}
	else
		{
		$VDvicidial_id='';

		if ($ACTION=="MonitorConf")
			{
			$stmt="SELECT recording_id,filename FROM routing_initiated_recordings where user='$user' and processed='0' order by launch_time desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02139',$user,$server_ip,$session_name,$one_mysql_log);}
			if ($DB) {echo "$stmt\n";}
			$rir_ct = mysqli_num_rows($rslt);
			if ($rir_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$recording_id =	$row[0];
				$filename =		$row[1];

				$stmt = "UPDATE routing_initiated_recordings SET processed='1' where recording_id='$recording_id';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02140',$user,$server_ip,$session_name,$one_mysql_log);}

				$stage .= " RIR $recording_id";
				}
			else
				{
				if (preg_match("/RECID/",$filename) )
					{
					$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename,lead_id,user) values('$channel','$server_ip','$exten','$NOW_TIME','$StarTtime','$filename',$lead_id,'$user')";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02133',$user,$server_ip,$session_name,$one_mysql_log);}
					$RLaffected_rows = mysqli_affected_rows($link);
					if ($RLaffected_rows > 0)
						{
						$recording_id = mysqli_insert_id($link);
						}

					$filename = preg_replace("/RECID/","$recording_id",$filename);

					$stmt = "UPDATE recording_log SET filename='$filename' where recording_id='$recording_id';";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02134',$user,$server_ip,$session_name,$one_mysql_log);}

					$vmgr_callerid = substr($filename, 0, 17) . '...';
					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$vmgr_callerid','Channel: $channel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','Callerid: $filename','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02135',$user,$server_ip,$session_name,$one_mysql_log);}

					### log outbound call in the vicidial_user_dial_log
					$stmt = "INSERT INTO vicidial_user_dial_log SET caller_code='$filename',user='$user',call_date='$NOW_TIME',call_type='RC',notes='$exten $ext_context $channel';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02159',$user,$server_ip,$session_name,$one_mysql_log);}
					}
				else
					{
					$vmgr_callerid = substr($filename, 0, 17) . '...';
					$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$vmgr_callerid','Channel: $channel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','Callerid: $filename','','','','','');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02072',$user,$server_ip,$session_name,$one_mysql_log);}

					### log outbound call in the vicidial_user_dial_log
					$stmt = "INSERT INTO vicidial_user_dial_log SET caller_code='$filename',user='$user',call_date='$NOW_TIME',call_type='RC',notes='$exten $ext_context $channel';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
							if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02160',$user,$server_ip,$session_name,$one_mysql_log);}

					$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename,lead_id,user) values('$channel','$server_ip','$exten','$NOW_TIME','$StarTtime','$filename','$lead_id','$user')";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
						if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02073',$user,$server_ip,$session_name,$one_mysql_log);}
					$RLaffected_rows = mysqli_affected_rows($link);
					if ($RLaffected_rows > 0)
						{
						$recording_id = mysqli_insert_id($link);
						}
					}
				if ($FROMvdc=='YES')
					{
					##### update vla record with recording_id
					$stmt = "UPDATE vicidial_live_agents SET external_recording='$recording_id' where user='$user';";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02117',$user,$server_ip,$session_name,$one_mysql_log);}

					##### get call type from vicidial_live_agents table
					$VLA_inOUT='NONE';
					$stmt="SELECT comments FROM vicidial_live_agents where user='$user' order by last_update_time desc limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02074',$user,$server_ip,$session_name,$one_mysql_log);}
					if ($DB) {echo "$stmt\n";}
					$VLA_inOUT_ct = mysqli_num_rows($rslt);
					if ($VLA_inOUT_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$VLA_inOUT =		$row[0];
						}
					if ($VLA_inOUT == 'INBOUND')
						{
						$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));

						##### look for the vicidial ID in the vicidial_closer_log table
						$stmt="SELECT closecallid FROM vicidial_closer_log where lead_id=$lead_id and user='$user' and call_date > \"$four_hours_ago\" order by closecallid desc limit 1;";
						}
					else
						{
						##### look for the vicidial ID in the vicidial_log table
						$stmt="SELECT uniqueid FROM vicidial_log where uniqueid='$uniqueid' and lead_id=$lead_id;";
						}
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02075',$user,$server_ip,$session_name,$one_mysql_log);}
					if ($DB) {echo "$stmt\n";}
					$VM_mancall_ct = mysqli_num_rows($rslt);
					if ($VM_mancall_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$VDvicidial_id =	$row[0];

						$stmt = "UPDATE recording_log SET vicidial_id='$VDvicidial_id' where recording_id='$recording_id';";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
					if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02076',$user,$server_ip,$session_name,$one_mysql_log);}
						}
					}
				$stage .= " AIR $recording_id $filename";
				}
			}
		##### StopMonitorConf steps #####
		else
			{
			if ($uniqueid=='IN')
				{
				$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));

				### find the value to put in the vicidial_id field if this was an inbound call
				$stmt="SELECT closecallid from vicidial_closer_log where lead_id=$lead_id and call_date > \"$four_hours_ago\" order by closecallid desc limit 1;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02077',$user,$server_ip,$session_name,$one_mysql_log);}
				$VAC_qm_ct = mysqli_num_rows($rslt);
				if ($VAC_qm_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$uniqueidSQL	= ",vicidial_id='$row[0]'";
					}
				}
			else
				{
				if (strlen($uniqueid) > 8)
					{$uniqueidSQL	= ",vicidial_id='$uniqueid'";}
				}

			if ($FROMvdc=='YES')
				{
				##### update vla recording record to blank
				$stmt = "UPDATE vicidial_live_agents SET external_recording='' where user='$user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02118',$user,$server_ip,$session_name,$one_mysql_log);}
				}

			$rec_searchSQL = "filename='$filename'";
			if (preg_match("/^ID:/",$filename))
				{
				$recording_id = $filename;
				$recording_id = preg_replace("/^ID:/",'',$recording_id);
				$rec_searchSQL = "recording_id='$recording_id'";
				}

			$stmt="SELECT recording_id,start_epoch,filename FROM recording_log where $rec_searchSQL order by start_epoch desc;";
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02078',$user,$server_ip,$session_name,$one_mysql_log);}
			if ($DB) {echo "$stmt\n";}
			$rec_count = mysqli_num_rows($rslt);
			if ($rec_count>0)
				{
				$row=mysqli_fetch_row($rslt);
				$recording_id =		$row[0];
				$start_time =		$row[1];
				$filename =			$row[2];
				$length_in_sec = ($StarTtime - $start_time);
				$length_in_min = ($length_in_sec / 60);
				$length_in_min = sprintf("%8.2f", $length_in_min);

				$stmt = "UPDATE recording_log set end_time='$NOW_TIME',end_epoch='$StarTtime',length_in_sec=$length_in_sec,length_in_min='$length_in_min' $uniqueidSQL where $rec_searchSQL order by start_epoch desc;";
					if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02079',$user,$server_ip,$session_name,$one_mysql_log);}
				}

			# find and hang up all recordings going on in this conference # and extension = '$exten' 
			$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$channel%\" and (channel LIKE \"%,1\" or channel LIKE \"%;1\");";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02080',$user,$server_ip,$session_name,$one_mysql_log);}
		#	$rec_count = intval(mysqli_num_rows($rslt) / 2);
			$rec_count = mysqli_num_rows($rslt);
			$HUchannel = array();
			$h=0;
			while ($rec_count > $h)
				{
				$rowx=mysqli_fetch_row($rslt);
				$HUchannel[$h] = $rowx[0];
				$h++;
				}
			$i=0;
			while ($h>$i)
				{
				$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Hangup','RH12345$StarTtime$i','Channel: $HUchannel[$i]','','','','','','','','','');";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02081',$user,$server_ip,$session_name,$one_mysql_log);}
				$i++;
				}
			}
		echo _QXZ("%1s command sent for Channel %2s on %3s",0,'',$ACTION,$channel,$server_ip)."\nFilename: $filename\nRecorDing_ID: $recording_id\n RECORDING WILL LAST UP TO 60 MINUTES\n";
		}
	}





######################
# ACTION=VolumeControl  - raise or lower the volume of a meetme participant
######################
if ($ACTION=="VolumeControl")
	{
	if ( (strlen($exten)<1) or (strlen($channel)<1) or (strlen($stage)<1) or (strlen($queryCID)<1) )
		{
		echo _QXZ("Conference %1s, Stage %2s is not valid or queryCID %3s is not valid, Originate command not inserted",0,'',$exten,$stage,$queryCID)."\n";
		}
	else
		{
		$participant_number='XXYYXXYYXXYYXX';
		if (preg_match('/UP/i',$stage)) {$vol_prefix='4';}
		if (preg_match('/DOWN/i',$stage)) {$vol_prefix='3';}
		if (preg_match('/UNMUTE/i',$stage)) {$vol_prefix='2';}
		if (preg_match('/MUTING/i',$stage)) {$vol_prefix='1';}
		$local_DEF = 'Local/';
		$local_AMP = '@';
		$volume_local_channel = "$local_DEF$participant_number$vol_prefix$exten$local_AMP$ext_context";

		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$queryCID','Channel: $volume_local_channel','Context: $ext_context','Exten: 8300','Priority: 1','Callerid: $queryCID','','','','$channel','$exten');";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02082',$user,$server_ip,$session_name,$one_mysql_log);}
		echo _QXZ("Volume command sent for Conference %1s, Stage %2s Channel %3s on %4s",0,'',$exten,$stage,$channel,$server_ip)."\n";
		}
	}




######################
# ACTION=MuteRecording  - Mute/Unmute audio recording of call
######################
if ($ACTION=="MuteRecording")
	{
	if ( (strlen($channel)<1) or (strlen($stage)<1) or (strlen($queryCID)<1) )
		{
		echo _QXZ("Recording %1s, Muting %2s is not valid or queryCID %3s is not valid, Originate command not inserted",0,'',$channel,$stage,$queryCID)."\n";
		}
	else
		{
		### insert into the vicidial_agent_function_log table that the recording mute has happened
		$stmt = "INSERT INTO vicidial_agent_function_log set agent_log_id='$agent_log_id',user='$user',function='mute_rec',event_time=NOW(),campaign_id='$campaign',user_group='$user_group',lead_id=$lead_id,uniqueid='$uniqueid',caller_code='$queryCID',stage='$stage',comments='$channel';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02144',$user,$server_ip,$session_name,$one_mysql_log);}

		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','MuteAudio','$queryCID','Channel: $channel','Direction: all','State: $stage','Callerid: $queryCID','','','','','$channel','$stage');";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'02145',$user,$server_ip,$session_name,$one_mysql_log);}
		echo _QXZ("Mute command sent for Recording %1s, Muting %2s on %3s",0,'',$channel,$stage,$server_ip)."\n";
		}
	}






$ENDtime = date("U");
$RUNtime = ($ENDtime - $StarTtime);
if ($format=='debug') {echo "\n<!-- script runtime: $RUNtime seconds -->";}
if ($format=='debug') {echo "\n</body>\n</html>\n";}

if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
exit; 


?>
