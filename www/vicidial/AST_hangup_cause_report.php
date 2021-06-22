<?php 
# AST_carrier_log_report.php
# 
# Copyright (C) 2019  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 120331-2301 - First build
# 130413-2348 - Added report logging
# 130419-2047 - Changed how menu lists are generated to speed up initial form load
# 132305-2305 - Finalized changing of all ereg instances to preg
# 130621-0749 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0731 - Changed to mysqli PHP functions
# 140108-0739 - Added webserver and hostname to report logging
# 140403-1830 - Fixed SIP hangup bug
# 141114-0837 - Finalized adding QXZ translation to all admin files
# 141230-1457 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 170818-2345 - Added HTML formatting
# 170829-0040 - Added screen color settings
# 191013-0858 - Fixes for PHP7
# 201218-1700 - Modified to include caller ID in results
#

$startMS = microtime();

$report_name='Hangup Cause Report';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_date_D"]))				{$query_date_D=$_GET["query_date_D"];}
	elseif (isset($_POST["query_date_D"]))	{$query_date_D=$_POST["query_date_D"];}
if (isset($_GET["query_date_T"]))				{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["server_ip"]))					{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))			{$server_ip=$_POST["server_ip"];}
if (isset($_GET["hangup_cause"]))					{$hangup_cause=$_GET["hangup_cause"];}
	elseif (isset($_POST["hangup_cause"]))			{$hangup_cause=$_POST["hangup_cause"];}
if (isset($_GET["sip_hangup_cause"]))					{$sip_hangup_cause=$_GET["sip_hangup_cause"];}
	elseif (isset($_POST["sip_hangup_cause"]))			{$sip_hangup_cause=$_POST["sip_hangup_cause"];}
if (isset($_GET["dial_status"]))					{$dial_status=$_GET["dial_status"];}
	elseif (isset($_POST["dial_status"]))			{$dial_status=$_POST["dial_status"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["lower_limit"]))			{$lower_limit=$_GET["lower_limit"];}
	elseif (isset($_POST["lower_limit"]))	{$lower_limit=$_POST["lower_limit"];}
if (isset($_GET["upper_limit"]))			{$upper_limit=$_GET["upper_limit"];}
	elseif (isset($_POST["upper_limit"]))	{$upper_limit=$_POST["upper_limit"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

$START_TIME=date("U");

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	}
##### END SETTINGS LOOKUP #####
###########################################

##### Hangup Cause Dictionary #####
$hangup_cause_dictionary = array(
0 => _QXZ("Unspecified. No other cause codes applicable."),
1 => _QXZ("Unallocated (unassigned) number."),
2 => _QXZ("No route to specified transit network (national use)."),
3 => _QXZ("No route to destination."),
6 => _QXZ("Channel unacceptable."),
7 => _QXZ("Call awarded, being delivered in an established channel."),
16 => _QXZ("Normal call clearing."),
17 => _QXZ("User busy."),
18 => _QXZ("No user responding."),
19 => _QXZ("No answer from user (user alerted)."),
20 => _QXZ("Subscriber absent."),
21 => _QXZ("Call rejected."),
22 => _QXZ("Number changed."),
23 => _QXZ("Redirection to new destination."),
25 => _QXZ("Exchange routing error."),
27 => _QXZ("Destination out of order."),
28 => _QXZ("Invalid number format (address incomplete)."),
29 => _QXZ("Facilities rejected."),
30 => _QXZ("Response to STATUS INQUIRY."),
31 => _QXZ("Normal, unspecified."),
34 => _QXZ("No circuit/channel available."),
38 => _QXZ("Network out of order."),
41 => _QXZ("Temporary failure."),
42 => _QXZ("Switching equipment congestion."),
43 => _QXZ("Access information discarded."),
44 => _QXZ("Requested circuit/channel not available."),
50 => _QXZ("Requested facility not subscribed."),
52 => _QXZ("Outgoing calls barred."),
54 => _QXZ("Incoming calls barred."),
57 => _QXZ("Bearer capability not authorized."),
58 => _QXZ("Bearer capability not presently available."),
63 => _QXZ("Service or option not available, unspecified."),
65 => _QXZ("Bearer capability not implemented."),
66 => _QXZ("Channel type not implemented."),
69 => _QXZ("Requested facility not implemented."),
79 => _QXZ("Service or option not implemented, unspecified."),
81 => _QXZ("Invalid call reference value."),
88 => _QXZ("Incompatible destination."),
95 => _QXZ("Invalid message, unspecified."),
96 => _QXZ("Mandatory information element is missing."),
97 => _QXZ("Message type non-existent or not implemented."),
98 => _QXZ("Message not compatible with call state or message type non-existent or not implemented."),
99 => _QXZ("Information element / parameter non-existent or not implemented."),
100 => _QXZ("Invalid information element contents."),
101 => _QXZ("Message not compatible with call state."),
102 => _QXZ("Recovery on timer expiry."),
103 => _QXZ("Parameter non-existent or not implemented - passed on (national use)."),
111 => _QXZ("Protocol error, unspecified."),
127 => _QXZ("Interworking, unspecified.")
);

#### SIP response code directory
$sip_response_directory = array(
	0 => "",
	100 => _QXZ("Trying"),
	180 => _QXZ("Ringing"),
	181 => _QXZ("Call is Being Forwarded"),
	182 => _QXZ("Queued"),
	183 => _QXZ("Session in Progress"),
	199 => _QXZ("Early Dialog Terminated"),
	200 => _QXZ("OK"),
	202 => _QXZ("Accepted"),
	204 => _QXZ("No Notification"),
	300 => _QXZ("Multiple Choices"),
	301 => _QXZ("Moved Permanently"),
	301 => _QXZ("Moved Temporarily"),
	302 => _QXZ("Moved Temporarily"),
	305 => _QXZ("Use Proxy"),
	380 => _QXZ("Alternative Service"),
	400 => _QXZ("Bad Request"),
	401 => _QXZ("Unauthorized"),
	402 => _QXZ("Payment Required"),
	403 => _QXZ("Forbidden"),
	404 => _QXZ("Not Found"),
	405 => _QXZ("Method Not Allowed"),
	406 => _QXZ("Not Acceptable"),
	407 => _QXZ("Proxy Authentication Required"),
	408 => _QXZ("Request Timeout"),
	409 => _QXZ("Conflict"),
	410 => _QXZ("Gone"),
	411 => _QXZ("Length Required"),
	412 => _QXZ("Conditional Request Failed"),
	413 => _QXZ("Request Entity Too Large"),
	414 => _QXZ("Request-URI Too Long"),
	415 => _QXZ("Unsupported Media Type"),
	416 => _QXZ("Unsupported URI Scheme"),
	417 => _QXZ("Unknown Resource-Priority"),
	420 => _QXZ("Bad Extension"),
	421 => _QXZ("Extension Required"),
	422 => _QXZ("Session Interval Too Small"),
	423 => _QXZ("Interval Too Brief"),
	424 => _QXZ("Bad Location Information"),
	428 => _QXZ("Use Identity Header"),
	429 => _QXZ("Provide Referrer Identity"),
	430 => _QXZ("Flow Failed"),
	433 => _QXZ("Anonymity Disallowed"),
	436 => _QXZ("Bad Identity-Info"),
	437 => _QXZ("Unsupported Certificate"),
	438 => _QXZ("Invalid Identity Header"),
	439 => _QXZ("First Hop Lacks Outbound Support"),
	470 => _QXZ("Consent Needed"),
	480 => _QXZ("Temporarily Unavailable"),
	481 => _QXZ("Call/Transaction Does Not Exist"),
	482 => _QXZ("Loop Detected."),
	483 => _QXZ("Too Many Hops"),
	484 => _QXZ("Address Incomplete"),
	485 => _QXZ("Ambiguous"),
	486 => _QXZ("Busy Here"),
	487 => _QXZ("Request Terminated"),
	488 => _QXZ("Not Acceptable Here"),
	489 => _QXZ("Bad Event"),
	491 => _QXZ("Request Pending"),
	493 => _QXZ("Undecipherable"),
	494 => _QXZ("Security Agreement Required"),
	500 => _QXZ("Server Internal Error"),
	501 => _QXZ("Not Implemented"),
	502 => _QXZ("Bad Gateway"),
	503 => _QXZ("Service Unavailable"),
	504 => _QXZ("Server Time-out"),
	505 => _QXZ("Version Not Supported"),
	513 => _QXZ("Message Too Large"),
	580 => _QXZ("Precondition Failure"),
	600 => _QXZ("Busy Everywhere"),
	603 => _QXZ("Decline"),
	604 => _QXZ("Does Not Exist Anywhere"),
	606 => _QXZ("Not Acceptable"),
);

$master_hangup_cause_array=array();
$i=0;
#while (list($key, $val)=each($hangup_cause_dictionary)) {
foreach($hangup_cause_dictionary as $key => $val) {
	$master_hangup_cause_array[$i]=$key;
	$i++;
}

$master_sip_response_directory=array();
$master_sip_response_verbiage_directory=array();
$i=0;
#while (list($key, $val)=each($sip_response_directory)) {
foreach($sip_response_directory as $key => $val) {
	$master_sip_response_directory[$i]=$key;
	$master_sip_response_verbiage_directory[$i]=$val;
	$i++;
}

$hangup_causes_to_print=count($master_hangup_cause_array);
$sip_responses_to_print=count($master_sip_response_directory);
$master_dialstatus_array=array(_QXZ("ANSWER"), _QXZ("BUSY"), _QXZ("NOANSWER"), _QXZ("CANCEL"), _QXZ("CONGESTION"), _QXZ("CHANUNAVAIL"), _QXZ("DONTCALL"), _QXZ("TORTURE"), _QXZ("INVALIDARGS"));
$dialstatuses_to_print=count($master_dialstatus_array);


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

if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}
$NOW_DATE = date("Y-m-d");
if (!isset($server_ip)) {$server_ip = array();}
if (!isset($hangup_cause)) {$hangup_cause = array();}
if (!isset($dial_status)) {$dial_status = array();}
if (!isset($sip_hangup_cause)) {$sip_hangup_cause = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}

$server_ip_string='|';
$server_ip_ct = count($server_ip);
$i=0;
while($i < $server_ip_ct)
	{
	$server_ip_string .= "$server_ip[$i]|";
	$i++;
	}

$server_stmt="SELECT server_ip,server_description from servers where active_asterisk_server='Y' order by server_ip asc";
if ($DB) {echo "|$server_stmt|\n";}
$server_rslt=mysql_to_mysqli($server_stmt, $link);
$servers_to_print=mysqli_num_rows($server_rslt);
$i=0;
$LISTserverIPs=array();
$LISTserver_names=array();
while ($i < $servers_to_print)
	{
	$row=mysqli_fetch_row($server_rslt);
	$LISTserverIPs[$i] =		$row[0];
	$LISTserver_names[$i] =	$row[1];
	if (preg_match('/\-ALL/',$server_ip_string) )
		{
		$server_ip[$i] = $LISTserverIPs[$i];
		}
	$i++;
	}

$i=0;
$server_ips_string='|';
$server_ip_ct = count($server_ip);
while($i < $server_ip_ct)
	{
	if ( (strlen($server_ip[$i]) > 0) and (preg_match("/\|$server_ip[$i]\|/",$server_ip_string)) )
		{
		$server_ips_string .= "$server_ip[$i]|";
		$server_ip_SQL .= "'$server_ip[$i]',";
		$server_ipQS .= "&server_ip[]=$server_ip[$i]";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$server_ip_string) ) or ($server_ip_ct < 1) )
	{
	$server_ip_SQL = "";
	$server_rpt_string="- ALL servers ";
	if (preg_match('/\-\-ALL\-\-/',$server_ip_string)) {$server_ipQS="&server_ip[]=--ALL--";}
	}
else
	{
	$server_ip_SQL = preg_replace('/,$/i', '',$server_ip_SQL);
	$server_ip_SQL = "and server_ip IN($server_ip_SQL)";
	$server_rpt_string="- server(s) ".preg_replace('/\|/', ", ", substr($server_ip_string, 1, -1));
	}
if (strlen($server_ip_SQL)<3) {$server_ip_SQL="";}

########### HANGUP CAUSES
$hangup_cause_string='|';
$dialstatus_string='|';
$sip_hangup_cause_string='|';

$hangup_cause_ct = count($hangup_cause);
$dial_status_ct = count($dial_status);
$sip_hangup_cause_ct = count($sip_hangup_cause);

$i=0;
while($i < $hangup_cause_ct)
	{
	$hangup_cause_string .= "$hangup_cause[$i]|";
	$i++;
	}

$j=0;
while($j < $dial_status_ct)
	{
	$dialstatus_string .= "$dial_status[$j]|";
	$j++;
	}

$i=0;
while($i < $sip_hangup_cause_ct)
	{
	$sip_hangup_cause_string .= "$sip_hangup_cause[$i]|";
	$i++;
	}

$i=0; $j=0;
$hangup_causes_string='|';
$dialstatuses_string='|';
$sip_hangup_causes_string='|';
while($i < $hangup_cause_ct)
	{
	if ( (strlen($hangup_cause[$i]) > 0) and (preg_match("/\|$hangup_cause[$i]\|/",$hangup_cause_string)) ) 
		{
		$hangup_causes_string .= "$hangup_cause[$i]|";
		$hangup_causeQS .= "&hangup_cause[]=$hangup_cause[$i]";
		}
	$i++;
	}

$i=0; 
$sip_hangup_cause_SQL="";
while($i < $sip_hangup_cause_ct)
	{
	if ( (strlen($sip_hangup_cause[$i]) > 0) and (preg_match("/\|$sip_hangup_cause[$i]\|/",$sip_hangup_cause_string)) ) 
		{
		$sip_hangup_causes_string .= "$sip_hangup_cause[$i]|";
		$sip_hangup_causeQS .= "&sip_hangup_cause[]=$sip_hangup_cause[$i]";
		$sip_hangup_cause_SQL.="$sip_hangup_cause[$i],";
		}
	$i++;
	}

while ($j < $dial_status_ct) 
	{
	if ( (strlen($dial_status[$j]) > 0) and (preg_match("/\|$dial_status[$j]\|/",$dialstatus_string)) ) 
		{
		$dialstatuses_string .= "$dial_status[$j]|";
		$dial_statusQS .= "&dial_status[]=$dial_status[$j]";
		}
	$j++;
	}

$i=0; 
while($i < $hangup_cause_ct)
	{
	$j=0;
	while ($j < $dial_status_ct) 
		{
		if ( (strlen($hangup_cause[$i]) > 0) and (preg_match("/\|$hangup_cause[$i]\|/",$hangup_cause_string)) and (strlen($dial_status[$j]) > 0) and (preg_match("/\|$dial_status[$j]\|/",$dialstatus_string)) )
			{
			if ( preg_match('/\-\-ALL\-\-/',$hangup_cause_string) ) {$HC_subclause="";} else {$HC_subclause="hangup_cause='$hangup_cause[$i]'";}
			if ( preg_match('/\-\-ALL\-\-/',$dialstatus_string) ) {$DS_subclause="";} else {$DS_subclause="dialstatus='$dial_status[$j]'";}
			if ($HC_subclause=="" || $DS_subclause=="") {$conjunction="";} else {$conjunction=" and ";}
			$hangup_cause_SQL .= "($HC_subclause$conjunction$DS_subclause) OR";
			$hangup_cause_SQL=preg_replace('/\(\) OR$/', '', $hangup_cause_SQL);
			#$hangup_cause_SQL .= "(hangup_cause='$hangup_cause[$i]' and dialstatus='$dial_status[$j]') OR";
			}
		$j++;
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$hangup_cause_string) ) or ($hangup_cause_ct < 1) )
	{
	$HC_rpt_string="- "._QXZ("ALL hangup causes")." ";
	if (preg_match('/\-\-ALL\-\-/',$hangup_cause_string)) {$hangup_causeQS="&hangup_cause[]=--ALL--";}
	}
else
	{
	$hangup_causes_string=preg_replace('/\!/', "-", $hangup_causes_string);
	$HC_rpt_string="AND hangup cause(s) ".preg_replace('/\|/', ", ", substr($hangup_causes_string, 1, -1));
	}


if ( (preg_match('/\-\-ALL\-\-/',$sip_hangup_cause_string) ) or ($sip_hangup_cause_ct < 1) )
	{
	$HC_rpt_string="- "._QXZ("ALL SIP hangup causes")." ";
	if (preg_match('/\-\-ALL\-\-/',$sip_hangup_cause_string)) 
		{
		$sip_hangup_causeQS="&sip_hangup_cause[]=--ALL--";
		$sip_hangup_cause_SQL="";
		}
	}
else
	{
	$sip_hangup_causes_string=preg_replace('/\!/', "-", $sip_hangup_causes_string);
	$HC_rpt_string=_QXZ("AND SIP hangup cause(s)")." ".preg_replace('/\|/', ", ", substr($sip_hangup_causes_string, 1, -1));
	}
$sip_hangup_cause_SQL = preg_replace('/,$/i', '',$sip_hangup_cause_SQL);
if (strlen($sip_hangup_cause_SQL)>0) {$sip_hangup_cause_SQL="and sip_hangup_cause in ($sip_hangup_cause_SQL)";}


if ( (preg_match('/\-\-ALL\-\-/',$dial_status_string) ) or ($dial_status_ct < 1) )
	{
	$dial_status_SQL = "";
	$DS_rpt_string="- "._QXZ("ALL dial statuses")." ";
	if (preg_match('/\-\-ALL\-\-/',$dial_status_string)) {$dial_statusQS="&dial_status[]=--ALL--";}
	}
else
	{
	#$hangup_cause_SQL=preg_replace('/ OR$/', '', $hangup_cause_SQL);
	#$hangup_cause_SQL = preg_replace('/,$/i', '',$hangup_cause_SQL);
	#$hangup_cause_SQL = "and ($hangup_cause_SQL)";
	$dialstatuses_string=preg_replace('/\!/', "-", $dialstatuses_string);
	$DS_rpt_string="AND dial status(es) ".preg_replace('/\|/', ", ", substr($dialstatuses_string, 1, -1));
	}
$hangup_cause_SQL=preg_replace('/ OR$/', '', $hangup_cause_SQL);
$hangup_cause_SQL = preg_replace('/,$/i', '',$hangup_cause_SQL);
$hangup_cause_SQL = "and ($hangup_cause_SQL)";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

require("screen_colors.php");

if (strlen($hangup_cause_SQL)<7) {$hangup_cause_SQL="";}

########################
$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"verticalbargraph.css\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"wz_jsgraphics.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"line.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$short_header=1;

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#hangup_cause_report$NWE\n";
$MAIN.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 cellspacing=5 cellpadding=5><TR><TD VALIGN=TOP align=center>\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.=_QXZ("Date").":\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

$MAIN.="<BR><BR><INPUT TYPE=TEXT NAME=query_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_D\">";

$MAIN.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>"._QXZ("Server IP").":<BR/>\n";
$MAIN.="<SELECT SIZE=5 NAME=server_ip[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$server_ip_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL SERVERS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL SERVERS")." --</option>\n";}
$o=0;
while ($servers_to_print > $o)
	{
	if (preg_match("/\|$LISTserverIPs[$o]\|/",$server_ip_string)) 
		{$MAIN.="<option selected value=\"$LISTserverIPs[$o]\">$LISTserverIPs[$o] - $LISTserver_names[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$LISTserverIPs[$o]\">$LISTserverIPs[$o] - $LISTserver_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT></TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("Hangup Cause").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=hangup_cause[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$hangup_causes_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL HANGUP CAUSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL HANGUP CAUSES")." --</option>\n";}

$o=0;
while ($hangup_causes_to_print > $o)
	{
	if (preg_match("/\|$master_hangup_cause_array[$o]\|/",$hangup_causes_string)) 
		{$MAIN.="<option selected value=\"$master_hangup_cause_array[$o]\">$master_hangup_cause_array[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$master_hangup_cause_array[$o]\">$master_hangup_cause_array[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("Dial status").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=dial_status[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$dialstatuses_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL DIAL STATUSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL DIAL STATUSES")." --</option>\n";}

$o=0;

while ($dialstatuses_to_print > $o)
	{
	if (preg_match("/\|$master_dialstatus_array[$o]\|/",$dialstatuses_string)) 
		{$MAIN.="<option selected value=\"$master_dialstatus_array[$o]\">$master_dialstatus_array[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$master_dialstatus_array[$o]\">$master_dialstatus_array[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("SIP Response").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=sip_hangup_cause[] multiple>\n";
if  (preg_match('/--ALL--/',$sip_hangup_causes_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL SIP CAUSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL SIP CAUSES")." --</option>\n";}

$o=0;
while ($sip_responses_to_print > $o)
	{
	if (preg_match("/\|$master_sip_response_directory[$o]\|/",$sip_hangup_causes_string)) 
		{$MAIN.="<option selected value=\"$master_sip_response_directory[$o]\">$master_sip_response_directory[$o] - $master_sip_response_verbiage_directory[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$master_sip_response_directory[$o]\">$master_sip_response_directory[$o] - $master_sip_response_verbiage_directory[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";


$MAIN.="<TD ROWSPAN=2 VALIGN=middle align=center>\n";
$MAIN.=_QXZ("Display as:")."<BR>";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR/><BR/>\n";
$MAIN.="</TD></TR></TABLE>\n";
if ($SUBMIT && $server_ip_ct>0) {
	$stmt="SELECT hangup_cause, dialstatus, count(*) as ct From vicidial_carrier_log where call_date>='$query_date $query_date_D' and call_date<='$query_date $query_date_T' $server_ip_SQL $hangup_cause_SQL group by hangup_cause, dialstatus order by hangup_cause, dialstatus";
	$rslt=mysql_to_mysqli($stmt, $link);
	$TEXT.="<PRE><font size=2>\n";
	if ($DB) {$TEXT.=$stmt."\n";}
	if (mysqli_num_rows($rslt)>0) {
		$TEXT.="--- "._QXZ("DIAL STATUS BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string\n";
		$TEXT.="+--------------+-------------+---------+\n";
		$TEXT.="| "._QXZ("HANGUP CAUSE",12)." | "._QXZ("DIAL STATUS",11)." |  "._QXZ("COUNT",6)." |\n";
		$TEXT.="+--------------+-------------+---------+\n";

		$HTML.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='3'><font size='2'>"._QXZ("DIAL STATUS BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string</font></th>";
		$HTML.="</tr>\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("HANGUP CAUSE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("DIAL STATUS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("COUNT")."</font></th>";
		$HTML.="</tr>\n";

		$total_count=0;
		while ($row=mysqli_fetch_array($rslt)) {
			$TEXT.="| ".sprintf("%-13s", $row["hangup_cause"]);
			$TEXT.="| ".sprintf("%-12s", $row["dialstatus"]);
			$TEXT.="| ".sprintf("%-8s", $row["ct"]);
			$TEXT.="|\n";
			$total_count+=$row["ct"];
			$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML.="<th><font size='2'>".$row["hangup_cause"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["dialstatus"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["ct"]."</font></th>";
			$HTML.="</tr>\n";
		}
		$TEXT.="+--------------+-------------+---------+\n";
		$TEXT.="| "._QXZ("TOTAL",26,"r")." | ".sprintf("%-8s", $total_count)."|\n";
		$TEXT.="+--------------+-------------+---------+\n\n\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='2'><font size='2'>"._QXZ("TOTAL")."</font></th>";
		$HTML.="<th><font size='2'>".$total_count."</font></th>";
		$HTML.="</tr></table><BR><BR>\n";

		$rpt_stmt="SELECT vicidial_carrier_log.*, vicidial_log.phone_number from vicidial_carrier_log left join vicidial_log on vicidial_log.uniqueid=vicidial_carrier_log.uniqueid where vicidial_carrier_log.call_date>='$query_date $query_date_D' and vicidial_carrier_log.call_date<='$query_date $query_date_T' $server_ip_SQL $hangup_cause_SQL $sip_hangup_cause_SQL order by vicidial_carrier_log.call_date asc";
		$rpt_rslt=mysql_to_mysqli($rpt_stmt, $link);
		if ($DB) {$TEXT.=$rpt_stmt."\n";}

		if (!$lower_limit) {$lower_limit=1;}
		if ($lower_limit+999>=mysqli_num_rows($rpt_rslt)) {$upper_limit=($lower_limit+mysqli_num_rows($rpt_rslt)%1000)-1;} else {$upper_limit=$lower_limit+999;}
		
		$TEXT.="--- "._QXZ("CARRIER LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, $HC_rpt_string, $DS_rpt_string\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit               <a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n";
		$carrier_rpt.="+----------------------+---------------------+-----------------+-----------+--------------+-------------+------------------------------------------+--------------+-----------+---------------+--------------+--------------+--------------------------------+\n";
		$carrier_rpt.="| "._QXZ("UNIQUE ID",20)." | "._QXZ("CALL DATE",19)." | "._QXZ("SERVER IP",15)." | "._QXZ("LEAD ID",9)." | "._QXZ("HANGUP CAUSE",12)." | "._QXZ("DIAL STATUS",11)." | "._QXZ("CHANNEL",40)." | "._QXZ("CALLER ID",12)." | "._QXZ("DIAL TIME",9)." | "._QXZ("ANSWERED TIME",13)." | "._QXZ("PHONE NUMBER",12)." | "._QXZ("SIP RESPONSE",12)." | "._QXZ("SIP REASON",30)." |\n";
		$carrier_rpt.="+----------------------+---------------------+-----------------+-----------+--------------+-------------+------------------------------------------+--------------+-----------+---------------+--------------+--------------+--------------------------------+\n";
		$CSV_text="\""._QXZ("UNIQUE ID")."\",\""._QXZ("CALL DATE")."\",\""._QXZ("SERVER IP")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("HANGUP CAUSE")."\",\""._QXZ("DIAL STATUS")."\",\""._QXZ("CHANNEL")."\",\""._QXZ("CALLER ID")."\",\""._QXZ("DIAL TIME")."\",\""._QXZ("ANSWERED TIME")."\",\""._QXZ("PHONE NUMBER")."\",\""._QXZ("SIP RESPONSE")."\",\""._QXZ("SIP REASON")."\"\n";

		$HTML.="<table border='0' cellpadding='3' cellspacing='1'>";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='11'><font size='2'>"._QXZ("CARRIER LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, $HC_rpt_string, $DS_rpt_string\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit</font></th>";
		$HTML.="<th colspan='2'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a></font></th>";
		$HTML.="</tr>\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("UNIQUE ID")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("CALL DATE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("SERVER IP")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("LEAD ID")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("HANGUP CAUSE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("DIAL STATUS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("CHANNEL")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("CALLER ID")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("DIAL TIME")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("ANSWERED TIME")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("PHONE NUMBER")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("SIP RESPONSE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("SIP REASON")."</font></th>";
		$HTML.="</tr>\n";

		for ($i=1; $i<=mysqli_num_rows($rpt_rslt); $i++) {
			$row=mysqli_fetch_array($rpt_rslt);
			$phone_number=""; $phone_note="";

			if (strlen($row["phone_number"])==0) {
				$stmt2="SELECT phone_number, alt_phone, address3 from vicidial_list where lead_id='$row[lead_id]'";
				$rslt2=mysql_to_mysqli($stmt2, $link);
				$channel=$row["channel"];
				while ($row2=mysqli_fetch_array($rslt2)) {
					if (strlen($row2["alt_phone"])>=7 && preg_match("/$row2[alt_phone]/", $channel)) {$phone_number=$row2["alt_phone"]; $phone_note="ALT";}
					else if (strlen($row2["address3"])>=7 && preg_match("/$row2[address3]/", $channel)) {$phone_number=$row2["address3"]; $phone_note="ADDR3";}
					else if (strlen($row2["phone_number"])>=7 && preg_match("/$row2[phone_number]/", $channel)) {$phone_number=$row2["phone_number"]; $phone_note="*";}
				}
			} else {
				$phone_number=$row["phone_number"];
			}

			$caller_id="";
			$log_stmt="select outbound_cid from vicidial_dial_log where lead_id='$row[lead_id]' and caller_code='$row[caller_code]'";
			# and call_date>='$row[call_date]'-INTERVAL 1 MINUTE and call_date<='$row[call_date]'+INTERVAL 1 MINUTE
			$log_rslt=mysql_to_mysqli($log_stmt, $link);
			while ($log_row=mysqli_fetch_array($log_rslt))
				{
				$outbound_cid=$log_row[0];
				preg_match('/\<[0-9]{7,}\>/', $outbound_cid, $cid_info);
				$caller_id=preg_replace('/[^0-9]/', '', $cid_info[0]);
				}

			$CSV_text.="\"$row[uniqueid]\",\"$row[call_date]\",\"$row[server_ip]\",\"$row[lead_id]\",\"$row[hangup_cause]\",\"$row[dialstatus]\",\"$row[channel]\",\"$caller_id\",\"$row[dial_time]\",\"$row[answered_time]\",\"$phone_number\",\"$row[sip_hangup_cause]\",\"$row[sip_hangup_reason]\"\n";
			if ($i>=$lower_limit && $i<=$upper_limit) {
				if (strlen($row["channel"])>37) {$row["channel"]=substr($row["channel"],0,37)."...";}
				$carrier_rpt.="| ".sprintf("%-21s", $row["uniqueid"]); 
				$carrier_rpt.="| ".sprintf("%-20s", $row["call_date"]); 
				$carrier_rpt.="| ".sprintf("%-16s", $row["server_ip"]); 
				$carrier_rpt.="| ".sprintf("%-10s", $row["lead_id"]); 
				$carrier_rpt.="| ".sprintf("%-13s", $row["hangup_cause"]); 
				$carrier_rpt.="| ".sprintf("%-12s", $row["dialstatus"]); 
				$carrier_rpt.="| ".sprintf("%-41s", $row["channel"]); 
				$carrier_rpt.="| ".sprintf("%-13s", $caller_id); 
				$carrier_rpt.="| ".sprintf("%-10s", $row["dial_time"]); 
				$carrier_rpt.="| ".sprintf("%-14s", $row["answered_time"]); 
				$carrier_rpt.="| ".sprintf("%-13s", $phone_number); 
				$carrier_rpt.="| ".sprintf("%-13s", $row["sip_hangup_cause"]); 
				$carrier_rpt.="| ".sprintf("%-31s", $row["sip_hangup_reason"])."|\n"; 

				$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
				$HTML.="<th><font size='2'>".$row["uniqueid"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["call_date"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["server_ip"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["lead_id"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["hangup_cause"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["dialstatus"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["channel"]."</font></th>";
				$HTML.="<th><font size='2'>".$caller_id."</font></th>";
				$HTML.="<th><font size='2'>".$row["dial_time"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["answered_time"]."</font></th>";
				$HTML.="<th><font size='2'>".$phone_number."</font></th>";
				$HTML.="<th><font size='2'>".$row["sip_hangup_cause"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["sip_hangup_reason"]."</font></th>";
				$HTML.="</tr>\n";
			}
		}
		$carrier_rpt.="+----------------------+---------------------+-----------------+-----------+--------------+-------------+------------------------------------------+--------------+-----------+---------------+--------------+--------------+--------------------------------+\n";

		$carrier_rpt_hf="";
		$ll=$lower_limit-1000;
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		if ($ll>=1) {
			$carrier_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$hangup_causeQS$sip_hangup_causeQS$dial_statusQS&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a>";
			$HTML.="<td align='left' colspan='7'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$hangup_causeQS$sip_hangup_causeQS$dial_statusQS&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a></font></th>";
		} else {
			$carrier_rpt_hf.=sprintf("%-23s", " ");
			$HTML.="<th colspan='7'>&nbsp;</th>";
		}
		$carrier_rpt_hf.=sprintf("%-145s", " ");
		if (($lower_limit+1000)<mysqli_num_rows($rpt_rslt)) {
			if ($upper_limit+1000>=mysqli_num_rows($rpt_rslt)) {$max_limit=mysqli_num_rows($rpt_rslt)-$upper_limit;} else {$max_limit=1000;}
			$carrier_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$hangup_causeQS$sip_hangup_causeQS$dial_statusQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a>";
			$HTML.="<td align='right' colspan='6'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$hangup_causeQS$sip_hangup_causeQS$dial_statusQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a></font></th>";
		} else {
			$carrier_rpt_hf.=sprintf("%23s", " ");
			$HTML.="<th colspan='6'>&nbsp;</th>";
		}
		$carrier_rpt_hf.="\n";
		$TEXT.=$carrier_rpt_hf.$carrier_rpt.$carrier_rpt_hf;
		$HTML.="</tr></table>";

	} else {
		$TEXT.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
		$HTML.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
	}
	$TEXT.="</font></PRE>\n";

	if ($report_display_type=="HTML") {
		$MAIN.=$HTML;
	} else {
		$MAIN.=$TEXT;
	}

	$MAIN.="</form></BODY></HTML>\n";


}
	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_hangup_cause_report_$US$FILE_TIME.csv";
		$CSV_text=preg_replace('/ +\"/', '"', $CSV_text);
		$CSV_text=preg_replace('/\" +/', '"', $CSV_text);
		// We'll be outputting a TXT file
		header('Content-type: application/octet-stream');

		// It will be called LIST_101_20090209-121212.txt
		header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		ob_clean();
		flush();

		echo "$CSV_text";

	} else {
		echo $HEADER;
		require("admin_header.php");
		echo $MAIN;
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

$END_TIME=date("U");

#print "Total run time: ".($END_TIME-$START_TIME);

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

exit;

?>
