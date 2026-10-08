<?php 
# AST_VCA_log_report.php
# 
# Copyright (C) 2025  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 190329-1852 - First build, based on AST_carrier_log_report.php
#

$startMS = microtime();

$report_name='VCA Log Report';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_date_D"]))			{$query_date_D=$_GET["query_date_D"];}
	elseif (isset($_POST["query_date_D"]))	{$query_date_D=$_POST["query_date_D"];}
if (isset($_GET["query_date_T"]))			{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["agent_status"]))				{$agent_status=$_GET["agent_status"];}
	elseif (isset($_POST["agent_status"]))		{$agent_status=$_POST["agent_status"];}
if (isset($_GET["text_search"]))				{$text_search=$_GET["text_search"];}
	elseif (isset($_POST["text_search"]))		{$text_search=$_POST["text_search"];}
	else {$text_search="";}
if (isset($_GET["amd_status"]))				{$amd_status=$_GET["amd_status"];}
	elseif (isset($_POST["amd_status"]))		{$amd_status=$_POST["amd_status"];}
if (isset($_GET["amd_cause"]))			{$amd_cause=$_GET["amd_cause"];}
	elseif (isset($_POST["amd_cause"]))	{$amd_cause=$_POST["amd_cause"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
	else {$file_download=0;}
if (isset($_GET["lower_limit"]))			{$lower_limit=$_GET["lower_limit"];}
	elseif (isset($_POST["lower_limit"]))	{$lower_limit=$_POST["lower_limit"];}
if (isset($_GET["upper_limit"]))			{$upper_limit=$_GET["upper_limit"];}
	elseif (isset($_POST["upper_limit"]))	{$upper_limit=$_POST["upper_limit"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
	else {$SUBMIT="";}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
	else {$report_display_type="TEXT";}
if (isset($_GET["rpt_output_type"]))			{$rpt_output_type=$_GET["rpt_output_type"];}
	elseif (isset($_POST["rpt_output_type"]))	{$rpt_output_type=$_POST["rpt_output_type"];}
	else {$rpt_output_type="SHOW LOGS";}

$START_TIME=date("U");
$NOW_DATE = date("Y-m-d");
if (!is_array($server_ip)) {$server_ip = array();}
if (!is_array($amd_status)) {$amd_status = array();}
if (!is_array($agent_status)) {$agent_status = array();}
if (!is_array($amd_cause)) {$amd_cause = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}
if (!isset($lower_limit)) {$lower_limit=1;}
if (!isset($upper_limit)) {$upper_limit=1000;}



#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$MAIN.="$stmt\n";}
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
	$SSallow_web_debug =			$row[6];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^-0-9]/', '', $query_date);
$query_date_D = preg_replace('/[^\:0-9]/', '', $query_date_D);
$query_date_T = preg_replace('/[^\:0-9]/', '', $query_date_T);
$file_download = preg_replace('/[^0-9]/', '', $file_download);
$lower_limit = preg_replace('/[^0-9]/', '', $lower_limit);
$upper_limit = preg_replace('/[^0-9]/', '', $upper_limit);

# Variables filtered further down in the code
# $server_ip
# $amd_status
# $amd_cause

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$text_search = preg_replace('/[^ 0-9a-zA-Z]/', '', $text_search);
	$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
	$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$text_search = preg_replace('/[^ 0-9\p{L}]/u', '', $text_search);
	$SUBMIT = preg_replace('/[^-_0-9\p{L}]/u', '', $SUBMIT);
	$report_display_type = preg_replace('/[^-_0-9\p{L}]/u', '', $report_display_type);
	}

$stmt="SELECT selected_language,user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$LOGuser_group =			$row[1];
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

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo "You are not allowed to view this report: |$PHP_AUTH_USER|$report_name|\n";
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
$LOGbrowser=preg_replace("/<|>|\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/<|>|\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/<|>|\'|\"|\\\\/","",$LOGhttp_referer);
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

# $master_amd_status_array=array("HANGUP", "HUMAN", "MACHINE", "NOTSURE");
# $master_amd_cause_array=array("HUMAN", "INITIALSILENCE", "LONGGREETING", "MAXWORDLENGTH", "MAXWORDS", "NOAUDIODATA", "TOOLONG");
$master_amd_status_array=array("FAS", "FAX", "HUMAN", "INTERCEPT", "MACHINE", "NOTSURE");
$master_amd_cause_array=array("ANS_SIG", "CNG_SIG", "CNG_ANS_SIG", "CONERROR", "HUMAN", "INITIALSILENCE", "LOWCONFIDENCE", "LOWSCORE", "MAXWORDS", "NOTHUMAN", "PATTERN", "RINGING", "SITTONES", "SOUNDTOOSHORT", "SIGNATURE","NOAUDIODATA");

$amd_statuses_to_print=count($master_amd_status_array);
$amd_causes_to_print=count($master_amd_cause_array);

$server_ip_string='|';
$server_ip_ct = count($server_ip);
$i=0;
while($i < $server_ip_ct)
	{
	$server_ip[$i] = preg_replace('/[^-\.\:\_0-9\p{L}]/u', '', $server_ip[$i]);
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
	$server_ip[$i] = preg_replace('/[^-\.\:\_0-9\p{L}]/u', '', $server_ip[$i]);
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

########### AMD STATUSES
$amd_status_string='|';
$amd_cause_string='|';

$amd_status_ct = count($amd_status);
$amd_cause_ct = count($amd_cause);

$i=0;
while($i < $amd_status_ct)
	{
	$amd_status[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $amd_status[$i]);
	$amd_status_string .= "$amd_status[$i]|";
	$i++;
	}

$j=0;
while($j < $amd_cause_ct)
	{
	$amd_cause[$j] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $amd_cause[$j]);
	$amd_cause_string .= "$amd_cause[$j]|";
	$j++;
	}

$i=0;

$i=0; $j=0;
$amd_statuses_string='|';
$amd_causes_string='|';
while($i < $amd_status_ct)
	{
	if ( (strlen($amd_status[$i]) > 0) and (preg_match("/\|$amd_status[$i]\|/",$amd_status_string)) ) 
		{
		$amd_statuses_string .= "$amd_status[$i]|";
		$amd_statusQS .= "&amd_status[]=$amd_status[$i]";
		}
	$i++;
	}

while ($j < $amd_cause_ct) 
	{
	if ( (strlen($amd_cause[$j]) > 0) and (preg_match("/\|$amd_cause[$j]\|/",$amd_cause_string)) ) 
		{
		$amd_causes_string .= "$amd_cause[$j]|";
		$amd_causeQS .= "&amd_cause[]=$amd_cause[$j]";
		}
	$j++;
	}

$i=0; 
while($i < $amd_status_ct)
	{
	$j=0;
	while ($j < $amd_cause_ct) 
		{
		if ( (strlen($amd_status[$i]) > 0) and (preg_match("/\|$amd_status[$i]\|/",$amd_status_string)) and (strlen($amd_cause[$j]) > 0) and (preg_match("/\|$amd_cause[$j]\|/",$amd_cause_string)) )
			{
			if ( preg_match('/\-\-ALL\-\-/',$amd_status_string) ) {$HC_subclause="";} else {$HC_subclause="amd_status='$amd_status[$i]'";}
			if ( preg_match('/\-\-ALL\-\-/',$amd_cause_string) ) {$DS_subclause="";} else {$DS_subclause="amd_cause='$amd_cause[$j]'";}
			if ($HC_subclause=="" || $DS_subclause=="") {$conjunction="";} else {$conjunction=" and ";}
			$amd_status_SQL .= "($HC_subclause$conjunction$DS_subclause) OR";
			$amd_status_SQL=preg_replace('/\(\) OR$/', '', $amd_status_SQL);
			#$amd_status_SQL .= "(amd_status='$amd_status[$i]' and amd_cause='$amd_cause[$j]') OR";
			}
		$j++;
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$amd_status_string) ) or ($amd_status_ct < 1) )
	{
	$HC_rpt_string=_QXZ("ALL AMD statuses")." ";
	if (preg_match('/\-\-ALL\-\-/',$amd_status_string)) {$amd_statusQS="&amd_status[]=--ALL--";}
	}
else
	{
	$amd_statuses_string=preg_replace('/\!/', "-", $amd_statuses_string);
	$HC_rpt_string="AND AMD status(es) ".preg_replace('/\|/', ", ", substr($amd_statuses_string, 1, -1));
	}

if ( (preg_match('/\-\-ALL\-\-/',$amd_cause_string) ) or ($amd_cause_ct < 1) )
	{
	$amd_cause_SQL = "";
	$DS_rpt_string=_QXZ("ALL AMD causes")." ";
	if (preg_match('/\-\-ALL\-\-/',$amd_cause_string)) {$amd_causeQS="&amd_cause[]=--ALL--";}
	}
else
	{
	#$amd_status_SQL=preg_replace('/ OR$/', '', $amd_status_SQL);
	#$amd_status_SQL = preg_replace('/,$/i', '',$amd_status_SQL);
	#$amd_status_SQL = "and ($amd_status_SQL)";
	$amd_causes_string=preg_replace('/\!/', "-", $amd_causes_string);
	$DS_rpt_string="AND AMD cause(es) ".preg_replace('/\|/', ", ", substr($amd_causes_string, 1, -1));
	}
$amd_status_SQL=preg_replace('/ OR$/', '', $amd_status_SQL);
$amd_status_SQL = preg_replace('/,$/i', '',$amd_status_SQL);
$amd_status_SQL = "and ($amd_status_SQL)";

if (in_array('--ALL--',$agent_status)) {$agent_status=array("--ALL--");}
$agent_statuses_array=array();
$agent_status_SQL='';
$status_stmt="select distinct status from vicidial_campaign_statuses UNION select distinct status from vicidial_statuses order by status";
$status_rslt=mysql_to_mysqli($status_stmt, $link);
$agent_statuses_to_print=mysqli_num_rows($status_rslt);
while($status_row=mysqli_fetch_row($status_rslt))
	{
	array_push($agent_statuses_array, $status_row[0]);
	if(in_array($status_row[0], $agent_status) || in_array("--ALL--", $agent_status))
		{
		$agent_status_SQL.="'$status_row[0]', ";
		}
	}
$agent_status_SQL=preg_replace('/,\s$/', '', $agent_status_SQL);
if (strlen($agent_status_SQL)>0) {$agent_status_SQL="and status in ($agent_status_SQL)";}
$status_rpt_string=", statuses: ".implode(', ', $agent_status);

$agent_statusQS='';
for ($i=0; $i<count($agent_status); $i++)
	{
	$agent_statusQS.="&agent_status[]=".$agent_status[$i];
	}

if (strlen($text_search)>0) {$text_search_SQL="and text like '%".$text_search."%'";} else {$text_search_SQL="";}

require("screen_colors.php");

if (strlen($amd_status_SQL)<7) {$amd_status_SQL="";}

########################
$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="   audio {";
$HEADER.="     width: 100px;";
$HEADER.="     height: 14;";
$HEADER.="   }";
$HEADER.="   audio::-webkit-media-controls-volume-slider, ";
$HEADER.="   audio::-webkit-media-controls-timeline-container, ";
$HEADER.="   audio::-webkit-media-controls-time-remaining-display,  ";
$HEADER.="   audio::-webkit-media-controls-timeline {";
$HEADER.="     display: none !important;";
$HEADER.="   }";
$HEADER.="   audio::-webkit-media-controls-panel {";
$HEADER.="     padding: 0 0 0 1px;";
$HEADER.="     justify-content: center; /*control panel elements are flex positioned*/";
$HEADER.="   }";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"verticalbargraph.css\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"wz_jsgraphics.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"line.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

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

$MAIN.="function playAudio(audioId) {\n";
$MAIN.="    const audioPlayer = document.getElementById(audioId);\n";
$MAIN.="    audioPlayer.play();\n";
$MAIN.="}\n";

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

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("AMD Status").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=amd_status[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$amd_statuses_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL AMD STATUSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL AMD STATUSES")." --</option>\n";}

$o=0;
while ($amd_statuses_to_print > $o)
	{
	if (preg_match("/\|$master_amd_status_array[$o]\|/",$amd_statuses_string)) 
		{$MAIN.="<option selected value=\"$master_amd_status_array[$o]\">"._QXZ("$master_amd_status_array[$o]")."</option>\n";}
	else
		{$MAIN.="<option value=\"$master_amd_status_array[$o]\">"._QXZ("$master_amd_status_array[$o]")."</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("AMD Cause").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=amd_cause[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$amd_causes_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL AMD CAUSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL AMD CAUSES")." --</option>\n";}

$o=0;
while ($amd_causes_to_print > $o)
	{
	if (preg_match("/\|$master_amd_cause_array[$o]\|/",$amd_causes_string)) 
		{$MAIN.="<option selected value=\"$master_amd_cause_array[$o]\">"._QXZ("$master_amd_cause_array[$o]")."</option>\n";}
	else
		{$MAIN.="<option value=\"$master_amd_cause_array[$o]\">"._QXZ("$master_amd_cause_array[$o]")."</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("Agent status").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=agent_status[] multiple>\n";
if (in_array('--ALL--',$agent_status))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL STATUSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL STATUSES")." --</option>\n";}

$o=0;
while ($agent_statuses_to_print > $o)
	{
	if (in_array("$agent_statuses_array[$o]",$agent_status)) 
		{$MAIN.="<option selected value=\"$agent_statuses_array[$o]\">"._QXZ("$agent_statuses_array[$o]")."</option>\n";}
	else
		{$MAIN.="<option value=\"$agent_statuses_array[$o]\">"._QXZ("$agent_statuses_array[$o]")."</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";


$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("Text search").":<BR/>";
$MAIN.="<input type='text' name='text_search' id='text_search' size=15 maxlength=200 value='$text_search'>\n";
$MAIN.="</TD>";


$MAIN.="<TD ROWSPAN=2 VALIGN=middle align=center>\n";

$MAIN.=_QXZ("Report type:")."<BR>";
$MAIN.="<select name='rpt_output_type'>";
if ($rpt_output_type) {$MAIN.="<option value='$rpt_output_type' selected>"._QXZ("$rpt_output_type")."</option>";}
$MAIN.="<option value='SHOW LOGS'>"._QXZ("SHOW LOGS")."</option><option value='CID COUNTS'>"._QXZ("CID COUNTS")."</option></select>\n<BR><BR>";

$MAIN.=_QXZ("Display as:")."<BR>";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";

$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR/><BR/>\n";
$MAIN.="</TD></TR></TABLE>\n";
if ($SUBMIT && $server_ip_ct>0) {
	$stmt="SELECT amd_status, amd_cause, count(*) as ct From vicidial_vca_log where analysis_date>='$query_date $query_date_D' and analysis_date<='$query_date $query_date_T' $server_ip_SQL $amd_status_SQL $text_search_SQL $amd_cause_SQL group by amd_status, amd_cause order by amd_status, amd_cause";

	$rslt=mysql_to_mysqli($stmt, $link);
	$TEXT.="<PRE><font size=2>\n";
	if ($DB) {$TEXT.=$stmt."\n";}
	$TEXT2="<PRE><font size=2>"; $HTML2="";
	if (mysqli_num_rows($rslt)>0) {
		$TEXT2.="--- "._QXZ("VCA BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string\n";
		$TEXT2.="+------------+----------------------+---------+\n";
		$TEXT2.="| "._QXZ("AMD STATUS",10)." | "._QXZ("AMD CAUSE",20)." |  "._QXZ("COUNT",6)." |\n";
		$TEXT2.="+------------+----------------------+---------+\n";

		$HTML2.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
		$HTML2.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML2.="<th colspan='3'><font size='2'>"._QXZ("AMD BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string</font></th>";
		$HTML2.="</tr>\n";
		$HTML2.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML2.="<th><font size='2'>"._QXZ("AMD STATUS")."</font></th>";
		$HTML2.="<th><font size='2'>"._QXZ("AMD CAUSE")."</font></th>";
		$HTML2.="<th><font size='2'>"._QXZ("COUNT")."</font></th>";
		$HTML2.="</tr>\n";

		# $total_count=0;
		$vca_breakdown=array();
		while ($row=mysqli_fetch_array($rslt)) {
			$array_key=$row["amd_status"]."|".$row["amd_cause"];
			$vca_breakdown["$array_key"]=$row["ct"];
			/*
			$TEXT.="| ".sprintf("%-11s", $row["amd_status"]);
			$TEXT.="| ".sprintf("%-21s", $row["amd_cause"]);
			$TEXT.="| ".sprintf("%-8s", $row["ct"]);
			$TEXT.="|\n";
			$total_count+=$row["ct"];
			$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML.="<th><font size='2'>".$row["amd_status"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["amd_cause"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["ct"]."</font></th>";
			$HTML.="</tr>\n";
			*/
		}
		/*
		$TEXT.="+------------+----------------------+---------+\n";
		$TEXT.="| "._QXZ("TOTAL",33,"r")." | ".sprintf("%-8s", $total_count)."|\n";
		$TEXT.="+------------+----------------------+---------+\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='2'><font size='2'>"._QXZ("TOTAL")."</font></th>";
		$HTML.="<th><font size='2'>".$total_count."</font></th>";
		$HTML.="</tr></table><BR><BR>\n";
		*/

		$cid_breakdown=array(); $no_CID_match=0;
		/*
		$cid_stmt="SELECT call_id, amd_status, amd_cause From vicidial_vca_log where analysis_date>='$query_date $query_date_D' and analysis_date<='$query_date $query_date_T' $server_ip_SQL $amd_status_SQL $text_search_SQL $amd_cause_SQL";
		$cid_rslt=mysql_to_mysqli($cid_stmt, $link);
		while ($cid_row=mysqli_fetch_row($cid_rslt))
			{
			$ocid_stmt="select outbound_cid from vicidial_dial_cid_log where analysis_date>='$query_date $query_date_D' and analysis_date<='$query_date $query_date_T' and caller_code='$cid_row[call_id]'";
			$ocid_rslt=mysql_to_mysqli($ocid_stmt, $link);
			while ($ocid_row=mysqli_fetch_array($ocid_rslt))
				{
				$ocid=$ocid_row["outbound_cid"];
				$amd_status=$ocid_row["amd_status"]
				$amd_cause=$ocid_row["amd_cause"]
				$cid_array_key=$amd_status."|".$amd_cause;
				$cid_breakdown["$ocid"]["$cid_array_key"]++;
				}
			}
		*/

		$rpt_stmt="SELECT DATE_FORMAT(analysis_date, '%H:%i:%s') as time, call_id, server_ip, channel, amd_status, amd_cause, text, fft_max_mean_ratio, total_detection_ms, total_collection_ms, collected_audio_ms, ROUND(time_to_decision_ms,2) as time_to_decision_ms, ROUND(last_silence_ms,2) as last_silence_ms, ROUND(nr_ms,2) as nr_ms, ROUND(fa_ms,2) as fa_ms, ROUND(asr_trans_ms,2) as asr_trans_ms, ROUND(asr_seg_ms,2) as asr_seg_ms, human_score, machine_score, dc_score, asr_confidence, asr_comp_ratio, rec_url, uniqueid from vicidial_vca_log where analysis_date>='$query_date $query_date_D' and analysis_date<='$query_date $query_date_T' $server_ip_SQL $amd_status_SQL $text_search_SQL $amd_cause_SQL order by analysis_date asc";
		$rpt_rslt=mysql_to_mysqli($rpt_stmt, $link);
		if ($DB) {$TEXT.=$rpt_stmt."\n";}

		if ($lower_limit+999>=mysqli_num_rows($rpt_rslt)) {$upper_limit=($lower_limit+mysqli_num_rows($rpt_rslt)%1000)-1;} else {$upper_limit=$lower_limit+999;}
		
		$TEXT.="\n\n--- "._QXZ("AMD LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string$status_rpt_string, $HC_rpt_string, $DS_rpt_string\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit               <a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$amd_statusQS$amd_causeQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n\n";


		# TEXT HEADER
		$carrier_rpt.="+----------+----------------------+-----------------+--------------------------+-----------+--------+----------+------------+----------------+------------------------------------------+-----------+-----------+------------+----------+--------+--------+--------+--------+------------+------------+------+------+------+----------+----------+------+---------------------------------------------------------------------------------+\n";
		$carrier_rpt.="| "._QXZ("TIME",9)."| "._QXZ("VICI CALL ID",20)." | "._QXZ("SERVER IP",15)." | "._QXZ("CHANNEL",24)." | "._QXZ("LEAD ID",9)." | "._QXZ("STATUS",6)." | "._QXZ("TALK SEC",8)." |"._QXZ("AMD STATUS",11)." | "._QXZ("AMD CAUSE",14)." | "._QXZ("TEXT",40)." | "._QXZ("FFT RATIO",9)." | "._QXZ("DETECT MS",9)." | "._QXZ("COLLECT MS",10)." | "._QXZ("AUDIO MS",8)." | "._QXZ("TTD MS",6)." | "._QXZ("LS MS",6)." | "._QXZ("NR MS",6)." | "._QXZ("FA MS",6)." | "._QXZ("ASR T MS",10)." | "._QXZ("ASR S MS",10)." | "._QXZ("HS",4)." | "._QXZ("MS",4)." | "._QXZ("DCS",4)." | "._QXZ("ASR CONF",8)." | "._QXZ("ASR COMP",8)." | "._QXZ("PLAY",4)." | "._QXZ("RECORDING",79)." |\n";
		$carrier_rpt.="+----------+----------------------+-----------------+--------------------------+-----------+--------+----------+------------+----------------+------------------------------------------+-----------+-----------+------------+----------+--------+--------+--------+--------+------------+------------+------+------+------+----------+----------+------+---------------------------------------------------------------------------------+\n";

		# CSV HEADER
		$CSV_text="\""._QXZ("TIME")."\",\""._QXZ("VICI CALL ID")."\",\""._QXZ("SERVER IP")."\",\""._QXZ("CHANNEL")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("STATUS")."\",\""._QXZ("TALK TIME")."\",\""._QXZ("AMD STATUS")."\",\""._QXZ("AMD CAUSE")."\",\""._QXZ("TEXT")."\",\""._QXZ("FFT RATIO")."\",\""._QXZ("DETECTION MS")."\",\""._QXZ("COLLECTION MS")."\",\""._QXZ("AUDIO MS")."\",\""._QXZ("TIME TILL DECISION MS")."\",\""._QXZ("LAST SILENCE MS")."\",\""._QXZ("NOISE REDUCTION MS")."\",\""._QXZ("FREQUENCY ANALYSIS MS")."\",\""._QXZ("ASR TRANSCRIPTION MS")."\",\""._QXZ("ASR SEGMENTS MS")."\",\""._QXZ("HUMAN SCORE")."\",\""._QXZ("MACHINE SCORE")."\",\""._QXZ("DC SCORE")."\",\""._QXZ("ASR CONF")."\",\""._QXZ("ASR COMP")."\",\""._QXZ("RECORDING")."\"\n";

		# HTML HEADER
		$HTML.="<table cellpadding='3' cellspacing='1' style='border: 1px solid black;'>";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='17'><font size='2'>"._QXZ("VCA LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string$status_rpt_string, $HC_rpt_string, $DS_rpt_string\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit</font></th>";
		$HTML.="<th colspan='2'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$amd_statusQS$amd_causeQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a></font></th>";
		$HTML.="</tr>\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("TIME")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("VICI CALL ID")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("SERVER IP")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("CHANNEL")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("LEAD ID")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("STATUS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("TALK SEC")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("AMD STATUS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("AMD CAUSE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("TEXT")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("FFT RATIO")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("DETECTION MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("COLLECTION MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("AUDIO MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("TTD MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("LAST SILENCE MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("NR MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("FA MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("ASR T MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("ASR S MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("HS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("MS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("DCS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("ASR CONF")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("ASR COMP")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("PLAY")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("REC URL")."</font></th>";
		$HTML.="</tr>\n";

		$j=1;
		for ($i=1; $i<=mysqli_num_rows($rpt_rslt); $i++) {
			$row=mysqli_fetch_array($rpt_rslt);

			$lead_id = 0;
			$pattern = "/(.{10})$/";
			if (preg_match($pattern, $row["call_id"], $matches)) 
				{
				$last_10_chars = $matches[1];
				$lead_id = intval($last_10_chars);
			}

			if (strlen($agent_status_SQL)>0)
				{
				$stmt2="SELECT status, talk_sec from vicidial_agent_log where lead_id = $lead_id  $agent_status_SQL and talk_epoch >= UNIX_TIMESTAMP('$query_date $query_date_D') and talk_epoch <= UNIX_TIMESTAMP('$query_date $query_date_T') and uniqueid=$row[uniqueid];";
				$rslt2=mysql_to_mysqli($stmt2, $link);
				$matching_row=mysqli_num_rows($rslt2);
				if ($DB) {$TEXT.=$stmt2." (".strlen($agent_status_SQL).", $matching_row)\n";}
				$channel=$row["channel"];
				$status=''; $talk_sec='';
				while ($row2=mysqli_fetch_array($rslt2)) 
					{
					$status=$row2["status"];
					$talk_sec=$row2["talk_sec"];
					}
				}
			else
				{
				$matching_row=0;
				}
			$valid_row=(strlen($agent_status_SQL)>0 && !in_array('--ALL--', $agent_status) && $matching_row==0 ? 0 : 1);

			if ($valid_row>0)
				{
				# CSV ROW
				if ($rpt_output_type=="CID COUNTS")
					{
					$ocid_stmt="select outbound_cid from vicidial_dial_cid_log where call_date>='$query_date $query_date_D' and call_date<='$query_date $query_date_T' and caller_code='$row[call_id]'";
					$ocid_rslt=mysql_to_mysqli($ocid_stmt, $link);
					if (mysqli_num_rows($ocid_rslt)==0) {$no_CID_match++;}
					while ($ocid_row=mysqli_fetch_array($ocid_rslt))
						{
						$ocid=$ocid_row["outbound_cid"];
						$cid_array_key=$row["amd_status"]."|".$row["amd_cause"];
						$cid_breakdown["$ocid"]["$cid_array_key"]++;
						}
					}
				else 
					{
					$CSV_text.="\"".$row["time"]."\",\"".$row["call_id"]."\",\"".$row["server_ip"]."\",\"".$row["channel"]."\",\"".$lead_id."\",\"".$status."\",\"".$talk_sec."\",\"".$row["amd_status"]."\",\"".$row["amd_cause"]."\",\"".$row["text"]."\",\"".$row["fft_max_mean_ratio"]."\",\"".$row["total_detection_ms"]."\",\"".$row["total_collection_ms"]."\",\"".$row["collected_audio_ms"]."\",\"".$row["time_to_decision_ms"]."\",\"".$row["last_silence_ms"]."\",\"".$row["nr_ms"]."\",\"".$row["fa_ms"]."\",\"".$row["asr_trans_ms"]."\",\"".$row["asr_seg_ms"]."\",\"".$row["human_score"]."\",\"".$row["machine_score"]."\",\"".$row["dc_score"]."\",\"".$row["asr_confidence"]."\",\"".$row["asr_comp_ratio"]."\",\"".$row["rec_url"]."\"\n";
					}
				}
			else
				{
				$array_key=$row["amd_status"]."|".$row["amd_cause"];
				$vca_breakdown["$array_key"]--;
				}

			if ($valid_row>0 && $j>=$lower_limit && $j<=$upper_limit && $rpt_output_type=="SHOW LOGS") {
				$rec_link=$row["rec_url"];
				if (strlen($row["channel"])>27) {$row["channel"]=substr($row["channel"],0,27)."...";}
				if (strlen($row["text"])>37) {$row["text"]=substr($row["text"],0,37)."...";}
				#if (strlen($row["rec_url"])>27) {$row["rec_url"]=substr($row["rec_url"],0,27)."...";}

				$playLink1 = "<a href='#' onclick='event.preventDefault();playAudio(\"".$row["call_id"]."\"); return false;'>";
                                $playLink2 = "</a><audio id='".$row["call_id"]."' src='".$rec_link."'></audio>";

                                $recLink1 = "<a href='$row[rec_url]' download>";
				$recLink2 = "</a>";

				$leadLink1 = "<A HREF=\"admin_modify_lead.php?lead_id=$lead_id\" onclick=\"javascript:window.open('admin_modify_lead.php?lead_id=$lead_id', '_blank');return false;\">";
				$leadLink2 = "</a>";

				# TEXT ROW
				$carrier_rpt.="| ".sprintf("%-9s", $row["time"]);
				$carrier_rpt.="| ".sprintf("%-21s", $row["call_id"]);
				$carrier_rpt.="| ".sprintf("%-16s", $row["server_ip"]); 
				$carrier_rpt.="| ".sprintf("%-25s", $row["channel"]);
				$carrier_rpt.="| ".$leadLink1.sprintf("%-10s", $lead_id).$leadLink2;
				$carrier_rpt.="| ".sprintf("%-7s", $status);
				$carrier_rpt.="| ".sprintf("%-9s", $talk_sec); 
				$carrier_rpt.="| ".sprintf("%-11s", $row["amd_status"]); 
				$carrier_rpt.="| ".sprintf("%-15s", $row["amd_cause"]); 
				$carrier_rpt.="| ".sprintf("%-41s", $row["text"]); 
				$carrier_rpt.="| ".sprintf("%-10s", $row["fft_max_mean_ratio"]); 
				$carrier_rpt.="| ".sprintf("%-10s", $row["total_detection_ms"]); 
				$carrier_rpt.="| ".sprintf("%-11s", $row["total_collection_ms"]); 
				$carrier_rpt.="| ".sprintf("%-9s", $row["collected_audio_ms"]); 
				$carrier_rpt.="| ".sprintf("%-7s", $row["time_to_decision_ms"]);
				$carrier_rpt.="| ".sprintf("%-7s", $row["last_silence_ms"]);
				$carrier_rpt.="| ".sprintf("%-7s", $row["nr_ms"]);
				$carrier_rpt.="| ".sprintf("%-7s", $row["fa_ms"]);
				$carrier_rpt.="| ".sprintf("%-11s", $row["asr_trans_ms"]);
				$carrier_rpt.="| ".sprintf("%-11s", $row["asr_seg_ms"]);
				$carrier_rpt.="| ".sprintf("%-5s", $row["human_score"]); 
				$carrier_rpt.="| ".sprintf("%-5s", $row["machine_score"]); 
				$carrier_rpt.="| ".sprintf("%-5s", $row["dc_score"]); 
				$carrier_rpt.="| ".sprintf("%-9s", $row["asr_confidence"]); 
				$carrier_rpt.="| ".sprintf("%-9s", $row["asr_comp_ratio"]); 
				$carrier_rpt.="| ".$playLink1.sprintf("%-4s", "PLAY").$playLink2.' '; 
				$carrier_rpt.="| ".$recLink1.sprintf("%-79s", $row["rec_url"]).$recLink2.' ';
				$carrier_rpt.="|\n";

				# HTML ROW
				$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
				$HTML.="<th><font size='2'>".$row["time"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["call_id"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["server_ip"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["channel"]."</font></th>";
				$HTML.="<th><font size='2'>".$leadLink1.$lead_id.$leadLink2."</font></th>";
				$HTML.="<th><font size='2'>".$status."</font></th>";
				$HTML.="<th><font size='2'>".$talk_sec."</font></th>";
				$HTML.="<th><font size='2'>".$row["amd_status"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["amd_cause"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["text"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["fft_max_mean_ratio"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["total_detection_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["total_collection_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["collected_audio_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["time_to_decision_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["last_silence_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["nr_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["fa_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["asr_trans_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["asr_seg_ms"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["human_score"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["machine_score"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["dc_score"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["asr_confidence"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["asr_comp_ratio"]."</font></th>";
				$HTML.="<th>".$playLink1."PLAY".$playLink2."</th>";
				$HTML.="<th>".$recLink1.$row["rec_url"].$recLink2."</th>";
				$HTML.="</tr>\n";
			}
			$j+=$valid_row;
		}

		if ($rpt_output_type=="SHOW LOGS")
			{
			$carrier_rpt.="+----------+----------------------+-----------------+--------------------------+-----------+--------+----------+------------+----------------+------------------------------------------+-----------+-----------+------------+----------+--------+--------+--------+--------+------------+------------+------+------+------+----------+----------+------+---------------------------------------------------------------------------------+\n";

			$total_count=0;
			foreach ($vca_breakdown as $status_cause => $ct)
				{
				if ($ct>0)
					{
					$sc_array=explode("|", $status_cause);
					$TEXT2.="| ".sprintf("%-11s", $sc_array[0]);
					$TEXT2.="| ".sprintf("%-21s", $sc_array[1]);
					$TEXT2.="| ".sprintf("%-8s", $ct);
					$TEXT2.="|\n";
					$total_count+=$ct;
					$HTML2.="<tr bgcolor='#".$SSstd_row2_background."'>";
					$HTML2.="<th><font size='2'>".$sc_array[0]."</font></th>";
					$HTML2.="<th><font size='2'>".$sc_array[1]."</font></th>";
					$HTML2.="<th><font size='2'>".$ct."</font></th>";
					$HTML2.="</tr>\n";
					}
				}
			$TEXT2.="+------------+----------------------+---------+\n";
			$TEXT2.="| "._QXZ("TOTAL",33,"r")." | ".sprintf("%-8s", $total_count)."|\n";
			$TEXT2.="+------------+----------------------+---------+\n";
			$TEXT2.="</font></PRE>\n";
			$HTML2.="<tr bgcolor='#".$SSstd_row1_background."'>";
			$HTML2.="<th colspan='2'><font size='2'>"._QXZ("TOTAL")."</font></th>";
			$HTML2.="<th><font size='2'>".$total_count."</font></th>";
			$HTML2.="</tr></table><BR><BR>\n";

			$carrier_rpt_hf="";
			$ll=$lower_limit-1000;
			$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
			if ($ll>=1) {
				$carrier_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$amd_statusQS$amd_causeQS$agent_statusQS&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a>";
				$HTML.="<td align='left' colspan='10'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$amd_statusQS$amd_causeQS$agent_statusQS&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a></font></th>";
			} else {
				$carrier_rpt_hf.=sprintf("%-23s", " ");
				$HTML.="<th colspan='10'>&nbsp;</th>";
			}
			$carrier_rpt_hf.=sprintf("%-264s", " ");
			if (($lower_limit+1000)<$total_count) { # mysqli_num_rows($rpt_rslt) -> $total_count
				if ($upper_limit+1000>=$total_count) {$max_limit=$total_count-$upper_limit;} else {$max_limit=1000;} # mysqli_num_rows($rpt_rslt) -> $total_count
				$carrier_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$amd_statusQS$amd_causeQS$agent_statusQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a>";
				$HTML.="<td align='right' colspan='9'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$amd_statusQS$amd_causeQS$agent_statusQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a></font></th>";
			} else {
				$carrier_rpt_hf.=sprintf("%23s", " ");
				$HTML.="<th colspan='9'>&nbsp;</th>";
			}
			$carrier_rpt_hf.="\n";
			$TEXT.=$carrier_rpt_hf.$carrier_rpt.$carrier_rpt_hf;
			$HTML.="</tr></table>";
			}
		else
			{
			$HTML=""; $TEXT="";
			$TEXT2="<PRE><font size=2>";
			$TEXT2.="--- "._QXZ("CID BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string\n";
			$TEXT2.="+------------+------------+----------------------+---------+\n";
			$TEXT2.="| "._QXZ("CALLER ID",10)." | "._QXZ("AMD STATUS",10)." | "._QXZ("AMD CAUSE",20)." |  "._QXZ("COUNT",6)." |\n";
			$TEXT2.="+------------+------------+----------------------+---------+\n";

			$HTML2="<BR><table border='0' cellpadding='3' cellspacing='1'>";
			$HTML2.="<tr bgcolor='#".$SSstd_row1_background."'>";
			$HTML2.="<th colspan='4'><font size='2'>"._QXZ("CID BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string</font></th>";
			$HTML2.="</tr>\n";
			$HTML2.="<tr bgcolor='#".$SSstd_row1_background."'>";
			$HTML2.="<th><font size='2'>"._QXZ("CALLER ID")."</font></th>";
			$HTML2.="<th><font size='2'>"._QXZ("AMD STATUS")."</font></th>";
			$HTML2.="<th><font size='2'>"._QXZ("AMD CAUSE")."</font></th>";
			$HTML2.="<th><font size='2'>"._QXZ("COUNT")."</font></th>";
			$HTML2.="</tr>\n";

			$total_count=0;
			ksort($cid_breakdown);

			foreach ($cid_breakdown as $caller_id => $amd_statuses)
				{
				ksort($amd_statuses);
				# $cid_breakdown["$ocid"]["$cid_array_key"]++;
				foreach ($amd_statuses as $code => $ct)
					{
					$cid_array=explode("|", $code);

					$TEXT2.="| ".sprintf("%-11s", $caller_id);
					$TEXT2.="| ".sprintf("%-11s", $cid_array[0]);
					$TEXT2.="| ".sprintf("%-21s", $cid_array[1]);
					$TEXT2.="| ".sprintf("%-8s", $ct);
					$TEXT2.="|\n";
					$total_count+=$ct;
					$HTML2.="<tr bgcolor='#".$SSstd_row2_background."'>";
					$HTML2.="<th><font size='2'>".$caller_id."</font></th>";
					$HTML2.="<th><font size='2'>".$cid_array[0]."</font></th>";
					$HTML2.="<th><font size='2'>".$cid_array[1]."</font></th>";
					$HTML2.="<th><font size='2'>".$ct."</font></th>";
					$HTML2.="</tr>\n";

					}
				}
			$TEXT2.="+------------+------------+----------------------+---------+\n";
			$TEXT2.="| "._QXZ("TOTAL",46,"r")." | ".sprintf("%-8s", $total_count)."|\n";
			$TEXT2.="+------------+------------+----------------------+---------+\n";
			$TEXT2.="</font></PRE>\n";
			$HTML2.="<tr bgcolor='#".$SSstd_row1_background."'>";
			$HTML2.="<th colspan='3'><font size='2'>"._QXZ("TOTAL")."</font></th>";
			$HTML2.="<th><font size='2'>".$total_count."</font></th>";
			$HTML2.="</tr></table><BR><BR>\n";
			}

	} else {
		$TEXT.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
		$HTML.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
	}
	$TEXT.="</font></PRE>\n";

	if ($report_display_type=="HTML") {
		$MAIN.=$HTML2.$HTML;
	} else {
		$MAIN.=$TEXT2.$TEXT;
	}

	$MAIN.="</form></BODY></HTML>\n";


}
	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_VCA_log_report_$US$FILE_TIME.csv";
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
