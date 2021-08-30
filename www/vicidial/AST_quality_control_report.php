<?php 
# AST_quality_control_report.php
# 
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# Report for QC activity within VICIdial
#
# changes:
# 210306-1556 - First Build
# 210827-1818 - Fix for security issue
#

$admin_version = '2.14-1';
$build = '210306-1556';


$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");


$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["query_time"]))				{$query_time=$_GET["query_time"];}
	elseif (isset($_POST["query_time"]))	{$query_time=$_POST["query_time"];}
if (isset($_GET["end_time"]))				{$end_time=$_GET["end_time"];}
	elseif (isset($_POST["end_time"]))		{$end_time=$_POST["end_time"];}
if (isset($_GET["qc_finish_start_date"]))				{$qc_finish_start_date=$_GET["qc_finish_start_date"];}
	elseif (isset($_POST["qc_finish_start_date"]))	{$qc_finish_start_date=$_POST["qc_finish_start_date"];}
if (isset($_GET["qc_finish_end_date"]))				{$qc_finish_end_date=$_GET["qc_finish_end_date"];}
	elseif (isset($_POST["qc_finish_end_date"]))		{$qc_finish_end_date=$_POST["qc_finish_end_date"];}
if (isset($_GET["qc_finish_start_time"]))				{$qc_finish_start_time=$_GET["qc_finish_start_time"];}
	elseif (isset($_POST["qc_finish_start_time"]))	{$qc_finish_start_time=$_POST["qc_finish_start_time"];}
if (isset($_GET["qc_finish_end_time"]))				{$qc_finish_end_time=$_GET["qc_finish_end_time"];}
	elseif (isset($_POST["qc_finish_end_time"]))		{$qc_finish_end_time=$_POST["qc_finish_end_time"];}

if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["user_groups"]))				{$user_groups=$_GET["user_groups"];}
	elseif (isset($_POST["user_groups"]))	{$user_groups=$_POST["user_groups"];}
if (isset($_GET["statuses"]))					{$statuses=$_GET["statuses"];}
	elseif (isset($_POST["statuses"]))			{$statuses=$_POST["statuses"];}
if (isset($_GET["QCusers"]))					{$QCusers=$_GET["QCusers"];}
	elseif (isset($_POST["QCusers"]))			{$QCusers=$_POST["QCusers"];}
if (isset($_GET["QCuser_groups"]))				{$QCuser_groups=$_GET["QCuser_groups"];}
	elseif (isset($_POST["QCuser_groups"]))	{$QCuser_groups=$_POST["QCuser_groups"];}
if (isset($_GET["QCstatuses"]))					{$QCstatuses=$_GET["QCstatuses"];}
	elseif (isset($_POST["QCstatuses"]))			{$QCstatuses=$_POST["QCstatuses"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["show_percentages"]))					{$show_percentages=$_GET["show_percentages"];}
	elseif (isset($_POST["show_percentages"]))			{$show_percentages=$_POST["show_percentages"];}
if (isset($_GET["search_archived_data"]))					{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))			{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}

$report_name = 'Quality Control Report';
$db_source = 'M';
$report_display_type="HTML";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format,qc_features_active,log_recording_access FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
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
	$SSreport_default_format =		$row[6];
	$SSqc_features_active =			$row[7];
	$log_recording_access =			$row[8];
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("quality_control_queue", "quality_control_checkpoint_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$quality_control_queue_table=use_archive_table("quality_control_queue");
	$quality_control_checkpoint_log_table=use_archive_table("quality_control_checkpoint_log");
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
	$quality_control_queue_table="quality_control_queue";
	$quality_control_checkpoint_log_table="quality_control_checkpoint_log";
	$vicidial_agent_log_table="vicidial_agent_log";
	}
#############

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

$stmt="SELECT selected_language,qc_enabled from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$qc_auth =					$row[1];
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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
	$HTML_text.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

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

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}



$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group) || !is_array($group)) {$group = array();}
if (!isset($statuses) || !is_array($statuses)) {$statuses = array();}
if (!isset($users) || !is_array($users)) {$users = array();}
if (!isset($user_groups) || !is_array($user_groups)) {$user_groups = array();}
if (!isset($QCusers) || !is_array($QCusers)) {$QCusers = array();}
if (!isset($QCstatuses) || !is_array($QCstatuses)) {$QCstatuses = array();}
if (!isset($QCuser_groups) || !is_array($QCuser_groups)) {$QCuser_groups = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (!isset($query_time)) {$query_time = "00:00:00";}
if (!isset($end_time)) {$end_time = "23:59:59";}
#if (!isset($qc_finish_start_date)) {$qc_finish_start_date = $NOW_DATE;}
#if (!isset($qc_finish_end_date)) {$qc_finish_end_date = $NOW_DATE;}
#if (!isset($qc_finish_start_time)) {$qc_finish_start_time = "00:00:00";}
#if (!isset($qc_finish_end_time)) {$qc_finish_end_time = "23:59:59";}
$all_QC_statuses=array("CLAIMED", "FINISHED");
$QC_statuses_to_print=count($all_QC_statuses);

$group=preg_replace('/[^-_0-9\p{L}]/u','',$group);
$campaign=preg_replace('/[^-_0-9\p{L}]/u','',$campaign);
$query_date = preg_replace('/[^-0-9]/','',$query_date);
$end_date = preg_replace('/[^-0-9]/','',$end_date);
$query_time=preg_replace("/[^0-9\:]/", "", $query_time);
$end_time=preg_replace("/[^0-9\:]/", "", $end_time);
$qc_finish_start_date = preg_replace('/[^-0-9]/','',$qc_finish_start_date);
$qc_finish_end_date = preg_replace('/[^-0-9]/','',$qc_finish_end_date);
$qc_finish_start_time=preg_replace("/[^0-9\:]/", "", $qc_finish_start_time);
$qc_finish_end_time=preg_replace("/[^0-9\:]/", "", $qc_finish_end_time);

$users=preg_replace('/[^-_0-9\p{L}]/u','',$users);
$user_groups=preg_replace('/[^-_0-9\p{L}]/u','',$user_groups);
$statuses=preg_replace('/[^-_0-9\p{L}]/u','',$statuses);
$QCusers=preg_replace('/[^-_0-9\p{L}]/u','',$QCusers);
$QCuser_groups=preg_replace('/[^-_0-9\p{L}]/u','',$QCuser_groups);
$QCstatuses=preg_replace('/[^-_0-9\p{L}]/u','',$QCstatuses);
$DB = preg_replace('/[^0-9]/','',$DB);
$SUBMIT=preg_replace('/[^-_0-9\p{L}]/u','',$SUBMIT);
$show_percentages=preg_replace('/[^-_0-9\p{L}]/u','',$show_percentages);
$file_download = preg_replace('/[^0-9]/','',$file_download);


$i=0;
$campaign_string='|';
$campaigns_ct = count($group);
while($i < $campaigns_ct)
	{
	$campaign_string .= "$group[$i]|";
	$i++;
	}

$i=0;
$users_string='|';
$users_ct = count($users);
while($i < $users_ct)
	{
	$users_string .= "$users[$i]|";
	$i++;
	}

$i=0;
$QCusers_string='|';
$QCusers_ct = count($QCusers);
while($i < $QCusers_ct)
	{
	$QCusers_string .= "$QCusers[$i]|";
	$i++;
	}

$i=0;
$QCstatuses_string='|';
$QCstatuses_ct = count($QCstatuses);
while($i < $QCstatuses_ct)
	{
	$QCstatuses_string .= "$QCstatuses[$i]|";
	$i++;
	}

$stmt="SELECT campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	if (preg_match('/\-ALL/',$group_string) )
		{$group[$i] = $groups[$i];}
	$i++;
	}
#for ($i=0; $i<count($user_group); $i++)
#	{
#	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1; $user_group="";}
#	}

$stmt="SELECT user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$allowed_user_groups=array();
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$allowed_user_groups[$i] =$row[0];
	if ($all_user_groups) {$user_group[$i]=$row[0];}
	$i++;
	}

$stmt="SELECT user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
$user_list=array();
$user_names=array();
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_list[$i]=$row[0];
	$user_names[$i]=$row[1];
	if ($all_users) {$user_list[$i]=$row[0];}
	$i++;
	}


$stmt="SELECT user, full_name from vicidial_users where qc_enabled>0 $LOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$QCusers_to_print = mysqli_num_rows($rslt);
$i=0;
$QCuser_list=array();
$QCuser_names=array();
while ($i < $QCusers_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$QCuser_list[$i]=$row[0];
	$QCuser_names[$i]=$row[1];
	if ($all_users) {$QCuser_list[$i]=$row[0];}
	$i++;
	}

/*
$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$user_group_SQL .= "'$user_group[$i]',";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{
	$i=0;
	$user_group_SQL = "";
	$user_groupQS = "";
	$user_groups_ct = count($allowed_user_groups);
	while($i < $user_groups_ct)
		{
		# $user_group_string .= "$user_groups[$i]|";
		$user_group_SQL .= "'$allowed_user_groups[$i]',";
		$user_groupQS .= "&user_group[]=$allowed_user_groups[$i]";
		$i++;
		}
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$user_group_agent_log_SQL = "and ".$quality_control_queue_table.".user_group IN($user_group_SQL)";
	$user_group_SQL = "and vicidial_users.user_group IN($user_group_SQL)";
	}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$user_group_agent_log_SQL = "and ".$quality_control_queue_table.".user_group IN($user_group_SQL)";
	$user_group_SQL = "and vicidial_users.user_group IN($user_group_SQL)";
	}


$i=0;
$QCuser_group_string='|';
$QCuser_group_ct = count($QCuser_group);
while($i < $QCuser_group_ct)
	{
	$QCuser_group_string .= "$QCuser_group[$i]|";
	$QCuser_group_SQL .= "'$QCuser_group[$i]',";
	$QCuser_groupQS .= "&QCuser_group[]=$QCuser_group[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$QCuser_group_string) ) or ($QCuser_group_ct < 1) )
	{
	$i=0;
	$QCuser_group_SQL = "";
	$QCuser_groupQS = "";
	$QCuser_groups_ct = count($allowed_user_groups);
	while($i < $QCuser_groups_ct)
		{
		# $QCuser_group_string .= "$QCuser_groups[$i]|";
		$QCuser_group_SQL .= "'$allowed_user_groups[$i]',";
		$QCuser_groupQS .= "&QCuser_group[]=$allowed_QCuser_groups[$i]";
		$i++;
		}
	$QCuser_group_SQL = preg_replace('/,$/i', '',$QCuser_group_SQL);
	$QCuser_group_agent_log_SQL = "and ".$quality_control_queue_table.".user_group IN($QCuser_group_SQL)";
	$QCuser_group_SQL = "and vicidial_users.user_group IN($QCuser_group_SQL)";
	}
else
	{
	$QCuser_group_SQL = preg_replace('/,$/i', '',$QCuser_group_SQL);
	$QCuser_group_agent_log_SQL = "and ".$quality_control_queue_table.".user_group IN($QCuser_group_SQL)";
	$QCuser_group_SQL = "and vicidial_users.user_group IN($QCuser_group_SQL)";
	}
*/

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	if ( (preg_match("/ $group[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_SQL = "and ".$quality_control_queue_table.".campaign_id IN($group_SQL)";
	}

$i=0;
$user_string='|';
$user_ct = count($users);
while($i < $user_ct)
	{
	$user_string .= "$users[$i]|";
	$user_SQL .= "'$users[$i]',";
	$userQS .= "&users[]=$users[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_string) ) or ($user_ct < 1) )
	{$user_SQL = "";}
else
	{
	$user_SQL = preg_replace('/,$/i', '',$user_SQL);
	$user_QC_log_SQL = "and ".$quality_control_queue_table.".user IN($user_SQL)";
	$user_SQL = "and vicidial_users.user IN($user_SQL)";
	}

$i=0;
$QCuser_string='|';
$QCuser_ct = count($QCusers);
while($i < $QCuser_ct)
	{
	$QCuser_string .= "$QCusers[$i]|";
	$QCuser_SQL .= "'$QCusers[$i]',";
	$QCuserQS .= "&QCusers[]=$QCusers[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$QCuser_string) ) or ($QCuser_ct < 1) )
	{$QCuser_SQL = "";}
else
	{
	$QCuser_SQL = preg_replace('/,$/i', '',$QCuser_SQL);
	$QCuser_QC_log_SQL = "and ".$quality_control_queue_table.".user IN($QCuser_SQL)";
	$QCuser_SQL = "and vicidial_users.user IN($QCuser_SQL)";
	}

$i=0;
$QCstatus_string='|';
$QCstatus_ct = count($QCstatuses);
while($i < $QCstatus_ct)
	{
	$QCstatus_string .= "$QCstatuses[$i]|";
	$QCstatus_SQL .= "'$QCstatuses[$i]',";
	$QCstatusQS .= "&QCstatuses[]=$QCstatuses[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$QCstatus_string) ) or ($QCstatus_ct < 1) )
	{$QCstatus_SQL = "";}
else
	{
	$QCstatus_SQL = preg_replace('/,$/i', '',$QCstatus_SQL);
	$QCstatus_QC_log_SQL = "and ".$quality_control_queue_table.".qc_status IN($QCstatus_SQL)";
	$QCstatus_SQL = "and vicidial_status.status IN($QCstatus_SQL)";
	}


if ($DB) {$HTML_text.="$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date&query_time=$query_time&end_time=$end_time&qc_finish_start_date=$qc_finish_start_date&qc_finish_end_date=$qc_finish_end_date&qc_finish_start_time=$qc_finish_start_time&qc_finish_end_time=$qc_finish_end_time$groupQS$user_groupQS$userQS$QCuserQS$QCstatusQS&DB=$DB&search_archived_data=$search_archived_data&show_percentages=$show_percentages&file_download=1&SUBMIT=SUBMIT";

require("screen_colors.php");

$NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
$NWE = "')\"><IMG SRC=\"help.gif\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";


$HTML_head.="<HTML>\n";
$HTML_head.="<HEAD>\n";
$HTML_head.="<STYLE type=\"text/css\">\n";
$HTML_head.="<!--\n";
$HTML_head.="   .green {color: white; background-color: green}\n";
$HTML_head.="   .red {color: white; background-color: red}\n";
$HTML_head.="   .blue {color: white; background-color: blue}\n";
$HTML_head.="   .purple {color: white; background-color: purple}\n";
$HTML_head.="-->\n";
$HTML_head.=" </STYLE>\n";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function GoToRecordingTimestamp(qc_log_id, timestamp, recording_id, lead_id)\n";
$HTML_text.="	{\n";
$HTML_text.="	var timestamp_sec=hmsToSecondsOnly(timestamp);\n";
# $HTML_text.="	alert(timestamp_sec); return false;\n";
$HTML_text.="	var recording_object_ID=\"QC_recording_id_\"+qc_log_id;\n";
$HTML_text.="	document.getElementById(recording_object_ID).currentTime=timestamp_sec;\n";
$HTML_text.="	document.getElementById(recording_object_ID).play();\n";
# $HTML_text.="	LogAudioRecordingAccess($log_recording_access, recording_id, lead_id, recording_object_ID);\n";
$HTML_text.="	}\n";
$HTML_text.="function hmsToSecondsOnly(str) {\n";
$HTML_text.="    var p = str.split(':'),\n";
$HTML_text.="        s = 0, m = 1;\n";
$HTML_text.="\n";
$HTML_text.="    while (p.length > 0) {\n";
$HTML_text.="        s += m * parseInt(p.pop(), 10);\n";
$HTML_text.="        m *= 60;\n";
$HTML_text.="    }\n";
$HTML_text.="\n";
$HTML_text.="    return s;\n";
$HTML_text.="}\n";

$HTML_text.="function LogAudioRecordingAccess(log_active, recording_id, lead_id, recording_object_ID)\n";
$HTML_text.="		{\n";
$HTML_text.="		if (log_active)\n";
$HTML_text.="			{\n";
$HTML_text.="			var xmlhttp=false;\n";
$HTML_text.="			try {\n";
$HTML_text.="				xmlhttp = new ActiveXObject(\"Msxml2.XMLHTTP\");\n";
$HTML_text.="			} catch (e) {\n";
$HTML_text.="				try {\n";
$HTML_text.="					xmlhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
$HTML_text.="				} catch (E) {\n";
$HTML_text.="					xmlhttp = false;\n";
$HTML_text.="				}\n";
$HTML_text.="			}\n";
$HTML_text.="			if (!xmlhttp && typeof XMLHttpRequest!='undefined') {\n";
$HTML_text.="				xmlhttp = new XMLHttpRequest();\n";
$HTML_text.="			}\n";
$HTML_text.="			if (xmlhttp) { \n";
$HTML_text.="				var log_query = \"&no_redirect=1&recording_id=\"+recording_id+\"&lead_id=\"+lead_id;\n";
# $HTML_text.="				alert(log_query);\n";
$HTML_text.="				xmlhttp.open('POST', 'recording_log_redirect.php'); \n";
$HTML_text.="				xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');\n";
$HTML_text.="				xmlhttp.send(log_query); \n";
$HTML_text.="				xmlhttp.onreadystatechange = function() { \n";
$HTML_text.="					if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {\n";
$HTML_text.="						var response = xmlhttp.responseText;\n";
$HTML_text.="						if (response!=\"OK\")\n";
$HTML_text.="							{\n";
$HTML_text.="							document.getElementById(recording_object_ID).pause();\n";
$HTML_text.="							alert(response);\n";
$HTML_text.="							}\n";
$HTML_text.="					}\n";
$HTML_text.="				}\n";
$HTML_text.="				delete xmlhttp;\n";
$HTML_text.="			}\n";
$HTML_text.="			}\n";
# $HTML_text.="		document.getElementById(recording_object_ID).play();\n";
$HTML_text.="		}\n";

$HTML_text.="</script>\n";

$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
$HTML_head.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;

#	require("admin_header.php");

$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";
$HTML_text.="<b>"._QXZ("$report_name")."</b> ".$NWB."quality_control_report".$NWE."\n";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLSPACING=5 border='0' BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP nowrap> "._QXZ("Call dates").":<BR>";
$HTML_text.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="  {\n";
$HTML_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="  }\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'query_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" &nbsp;<INPUT TYPE=TEXT NAME=query_time SIZE=10 MAXLENGTH=10 VALUE=\"$query_time\">";

$HTML_text.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'end_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" &nbsp;<INPUT TYPE=TEXT NAME=end_time SIZE=10 MAXLENGTH=10 VALUE=\"$end_time\">";
$HTML_text.="</TD>";

$HTML_text.="<TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
{
	if (preg_match("/$groups[$o]\|/i",$group_string) && !preg_match('/\-\-ALL\-\-/',$group_string)) {$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>";

/*
$HTML_text.="<TD VALIGN=TOP>"._QXZ("User Groups").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=user_group[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/$allowed_user_groups[$o]\|/i",$user_group_string)) {$HTML_text.="<option selected value=\"$allowed_user_groups[$o]\">$allowed_user_groups[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$allowed_user_groups[$o]\">$allowed_user_groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>";
*/

$HTML_text.="<TD VALIGN=TOP>"._QXZ("Users").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=users[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$users_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USERS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USERS")." --</option>\n";}
$o=0;
while ($users_to_print > $o)
	{
	if  (preg_match("/$user_list[$o]\|/i",$users_string) && !preg_match('/\-\-ALL\-\-/',$users_string)) {$HTML_text.="<option selected value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>";

$HTML_text.="<TD VALIGN=TOP ROWSPAN=2>&nbsp; &nbsp; &nbsp; &nbsp; ";
$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
$HTML_text.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT><BR><BR>\n";
$HTML_text.="<input type='checkbox' name='show_percentages' value='checked' $show_percentages>"._QXZ("Show %s")."<BR>\n";
$HTML_text.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR>\n";
$HTML_text.="<BR><BR>\n";
$HTML_text.=" &nbsp; &nbsp; &nbsp; <INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$HTML_text.="</TD>";

$HTML_text.="</TR>";

$HTML_text.="<TR><TD VALIGN=TOP>"._QXZ("Finish date").":<BR>";
$HTML_text.="<INPUT TYPE=TEXT NAME='qc_finish_start_date' SIZE=10 MAXLENGTH=10 VALUE=\"$qc_finish_start_date\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="  {\n";
$HTML_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="  }\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'qc_finish_start_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" &nbsp;<INPUT TYPE=TEXT NAME='qc_finish_start_time' SIZE=10 MAXLENGTH=10 VALUE=\"$qc_finish_start_time\">";

$HTML_text.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME='qc_finish_end_date' SIZE=10 MAXLENGTH=10 VALUE=\"$qc_finish_end_date\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'qc_finish_end_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" &nbsp;<INPUT TYPE=TEXT NAME='qc_finish_end_time' SIZE=10 MAXLENGTH=10 VALUE=\"$qc_finish_end_time\">";
$HTML_text.="</TD>";

$HTML_text.="<TD VALIGN=TOP>"._QXZ("QC Status").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=QCstatuses[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$QCstatus_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL QC STATUSES")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL QC STATUSES")." --</option>\n";}
$o=0;
while ($QC_statuses_to_print > $o)
	{
	if  (preg_match("/$all_QC_statuses[$o]\|/i",$QCstatus_string) && !preg_match('/\-\-ALL\-\-/',$QCstatus_string)) {$HTML_text.="<option selected value=\"$all_QC_statuses[$o]\">$all_QC_statuses[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$all_QC_statuses[$o]\">$all_QC_statuses[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";

/*
$HTML_text.="<TD VALIGN=TOP>"._QXZ("QC User Groups").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=QCuser_group[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$QCuser_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/$allowed_user_groups[$o]\|/i",$user_group_string)) {$HTML_text.="<option selected value=\"$allowed_user_groups[$o]\">$allowed_user_groups[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$allowed_user_groups[$o]\">$allowed_user_groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>";
*/

$HTML_text.="<TD VALIGN=TOP>"._QXZ("QC Users").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=QCusers[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$QCusers_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USERS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USERS")." --</option>\n";}
$o=0;
while ($QCusers_to_print > $o)
	{
	if  (preg_match("/$QCuser_list[$o]\|/i",$QCusers_string) && !preg_match('/\-\-ALL\-\-/',$QCusers_string)) {$HTML_text.="<option selected value=\"$QCuser_list[$o]\">$QCuser_list[$o] - $QCuser_names[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$QCuser_list[$o]\">$QCuser_list[$o] - $QCuser_names[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>";

$HTML_text.="</TR>";

$HTML_text.="</TABLE>";

$HTML_text.="</FORM>\n\n";


if ($SUBMIT && ($QCuser_ct>0 || $user_ct>0 || $group_ct>0 || $QCstatus_ct>0 || ($qc_finish_start_date && $qc_finish_end_date)) )
	{
	if ($qc_finish_start_date && $qc_finish_end_date) 
		{
		if (!$qc_finish_start_time) {$qc_finish_start_time = "00:00:00";}
		if (!$qc_finish_end_time) {$qc_finish_end_time = "23:59:59";}
		$finish_date_clause=" and date_completed>='$qc_finish_start_date $qc_finish_start_time' and date_completed<='$qc_finish_end_date $qc_finish_end_time'";
		}

	$qc_stmt="select * from ".$quality_control_queue_table." where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $finish_date_clause $group_SQL $QCstatus_QC_log_SQL $user_QC_log_SQL $QCuser_QC_log_SQL";
	if ($DB) {$HTML_text.=$qc_stmt."<BR><BR>";}

	$qc_rslt=mysql_to_mysqli($qc_stmt, $link);

	if(mysqli_num_rows($qc_rslt)>0)
		{
		$HTML_text.="<table border=0 width='1000' cellpadding=2 cellspacing=1>";
		$HTML_text.="<tr><td align='right' colspan='12' class='small_standard_bold'><a href='$LINKbase'>[DOWNLOAD]</a></td></tr>";
		$HTML_text.="<tr bgcolor='#000' nowrap>";
		$HTML_text.="<th class='small_standard_bold white_text'>Call date</th>";
		$HTML_text.="<th class='small_standard_bold white_text' nowrap>Call agent</th>";
		$HTML_text.="<th class='small_standard_bold white_text'>Lead ID</th>";
		$HTML_text.="<th class='small_standard_bold white_text'>Campaign ID</th>";
		$HTML_text.="<th class='small_standard_bold white_text'>Status</th>";
		$HTML_text.="<th class='small_standard_bold white_text'>Scorecard</th>";
		$HTML_text.="<th class='small_standard_bold white_text' nowrap>QC agent</th>";
		$HTML_text.="<th class='small_standard_bold white_text' nowrap>Claim date</th>";
		$HTML_text.="<th class='small_standard_bold white_text' nowrap>Finish date</th>";
		$HTML_text.="<th class='small_standard_bold white_text' nowrap>Score</th>";
		$HTML_text.="<th class='small_standard_bold white_text'>Instant fail</th>";
		$HTML_text.="<th class='small_standard_bold white_text'>Result</th>";
		$HTML_text.="<th class='small_standard_bold white_text'>QC status</th>";
		$HTML_text.="</tr>";

		$CSV_header="\"Call date\",\"Call agent\",\"Lead ID\",\"Campaign ID\",\"Status\",\"Scorecard\",\"QC agent\",\"Claim date\",\"Finish date\",\"Score\",\"Instant fail\",\"Result\",\"QC status\"";
		$CSV_text="";

		$x=0;
		$max_questions=0;
		while ($qc_row=mysqli_fetch_array($qc_rslt))
			{
			$qc_recording_id=$qc_row["recording_id"];
			if ($x%2==0) {$bgcolor=$SSstd_row2_background;} else {$bgcolor=$SSstd_row1_background;}
			$HTML_text.="<tr bgcolor='$bgcolor'>";
			$HTML_text.="<td class='small_standard_bold'><A name='QC_anchor_".$qc_row["qc_log_id"]."'/>".$qc_row["call_date"]."</td>";
			$HTML_text.="<td class='small_standard_bold' nowrap>".GetFullName($qc_row["user"])."</td>";
			$HTML_text.="<td class='small_standard_bold'>".$qc_row["lead_id"]."</td>";
			$HTML_text.="<td class='small_standard_bold'>".$qc_row["campaign_id"]."</td>";
			$HTML_text.="<td class='small_standard_bold'>".$qc_row["status"]."</td>";
			$HTML_text.="<td class='small_standard_bold'>".$qc_row["qc_scorecard_id"]."</td>";
			$HTML_text.="<td class='small_standard_bold' nowrap>".GetFullName($qc_row["qc_agent"])."</td>";
			$HTML_text.="<td class='small_standard_bold' nowrap>".$qc_row["date_claimed"]."</td>";
			$HTML_text.="<td class='small_standard_bold' nowrap>".$qc_row["date_completed"]."</td>";
			$HTML_text.="<td class='small_standard_bold' nowrap>".GetScore($qc_row["qc_log_id"], "")."</td>";
			$instant_fail=GetFail($qc_row["qc_log_id"]);
			$HTML_text.="<td class='small_standard_bold' align='center'>".$instant_fail."</td>";
			$HTML_text.="<td class='small_standard_bold' align='center'>".($instant_fail=="Y" ? "FAILED" : GetResult($qc_row["qc_log_id"]))."</td>";
			$HTML_text.="<td class='small_standard_bold'>".$qc_row["qc_status"]."</td>";
			$HTML_text.="</tr>";

			$CSV_text.="\"".$qc_row["call_date"]."\",\"".GetFullName($qc_row["user"])."\",\"".$qc_row["lead_id"]."\",\"".$qc_row["campaign_id"]."\",\"".$qc_row["status"]."\",\"".$qc_row["qc_scorecard_id"]."\",\"".GetFullName($qc_row["qc_agent"])."\",\"".$qc_row["date_claimed"]."\",\"".$qc_row["date_completed"]."\",\"".GetScore($qc_row["qc_log_id"], "")."\",\"".$instant_fail."\",\"".($instant_fail=="Y" ? "FAILED" : GetResult($qc_row["qc_log_id"]))."\",\"".$qc_row["qc_status"]."\"";

			$checkpoint_stmt="select * from ".$quality_control_checkpoint_log_table." where qc_log_id='".$qc_row["qc_log_id"]."' order by checkpoint_rank asc";
			if ($DB) {$HTML_text.=$checkpoint_stmt."<BR>";}
			$checkpoint_rslt=mysql_to_mysqli($checkpoint_stmt, $link);
			$question_count=mysqli_num_rows($checkpoint_rslt);
			if ($question_count>$max_questions) {$max_questions=$question_count;}
			while($crow=mysqli_fetch_array($checkpoint_rslt))
				{
				$checkpoint_comment=preg_replace("/((\d{1,2})?\:?\d?\d\:\d{2})/", "<a href='#QC_anchor_$qc_row[qc_log_id]' onClick=\"GoToRecordingTimestamp('$qc_row[qc_log_id]', '$1', $qc_recording_id, ".$qc_row["lead_id"].")\">$1</a>", $crow["checkpoint_comment_agent"]);
				$HTML_text.="<tr bgcolor='$bgcolor'>";
				$HTML_text.="<td class='small_standard' align='left' colspan='4' nowrap>&nbsp;&nbsp;&nbsp;&nbsp;".$crow["checkpoint_rank"].") ".$crow["checkpoint_text"]."</td>";
				$HTML_text.="<td class='small_standard' align='left' colspan='5' nowrap>".$checkpoint_comment."</td>";
				$HTML_text.="<td class='small_standard' nowrap>".GetScore($qc_row["qc_log_id"], $crow["qc_checkpoint_log_id"])."</td>";
				$HTML_text.="<td class='small_standard' align='center'>".$crow["instant_fail_value"]."</td>";
				$HTML_text.="<td class='small_standard' colspan='2'>&nbsp;</td>";
				$HTML_text.="<tr>";
				$CSV_text.=",\"".$crow["checkpoint_rank"].") ".$crow["checkpoint_text"]."\",\"".$crow["checkpoint_comment_agent"]."\",\"".GetScore($qc_row["qc_log_id"], $crow["qc_checkpoint_log_id"])."\",\"".$crow["instant_fail_value"]."\"";
				}

			# RECORDING
			$rec_stmt="select location from recording_log where recording_id='" . mysqli_real_escape_string($link, $qc_recording_id) . "' order by recording_id desc limit 500;";
			$rec_rslt=mysql_to_mysqli($rec_stmt, $link);
			$rec_row=mysqli_fetch_row($rec_rslt);
				
			$location = $rec_row[0];

			if (strlen($location)>2)
				{
				$URLserver_ip = $location;
				$URLserver_ip = preg_replace('/http:\/\//i', '',$URLserver_ip);
				$URLserver_ip = preg_replace('/https:\/\//i', '',$URLserver_ip);
				$URLserver_ip = preg_replace('/\/.*/i', '',$URLserver_ip);
				$stmt="select count(*) from servers where server_ip='$URLserver_ip';";
				$rsltx=mysql_to_mysqli($stmt, $link);
				$rowx=mysqli_fetch_row($rsltx);

				if ($rowx[0] > 0)
					{
					$stmt="select recording_web_link,alt_server_ip,external_server_ip from servers where server_ip='$URLserver_ip';";
					$rsltx=mysql_to_mysqli($stmt, $link);
					$rowx=mysqli_fetch_row($rsltx);

					if (preg_match("/ALT_IP/i",$rowx[0]))
						{
						$location = preg_replace("/$URLserver_ip/i", "$rowx[1]", $location);
						}
					if (preg_match("/EXTERNAL_IP/i",$rowx[0]))
						{
						$location = preg_replace("/$URLserver_ip/i", "$rowx[2]", $location);
						}
					}
				}

			if (strlen($location)>30)
				{$locat = substr($location,0,27);  $locat = "$locat...";}
			else
				{$locat = $location;}
			$play_audio='';
			if ( (preg_match('/ftp/i',$location)) or (preg_match('/http/i',$location)) )
				{
				$play_audio = "<audio id='QC_recording_id_".$qc_row["qc_log_id"]."' controls preload=\"none\" onplay='LogAudioRecordingAccess($log_recording_access, $qc_recording_id, ".$qc_row["lead_id"].", this.id)'> <source src ='$location' type='audio/wav' > <source src ='$location' type='audio/mpeg' >"._QXZ("No browser audio playback support")."</audio>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;\n";
				$location = "<a href=\"recording_log_redirect.php?recording_id=".$qc_recording_id."&lead_id=".$qc_row["lead_id"]."&search_archived_data=0\">$locat</a>";
				}
			else
				{$location = $locat;}
			$HTML_text.="<tr bgcolor='$bgcolor' valign='middle'>";
			$HTML_text.="<td class='small_standard_bold' align='right' colspan='4' nowrap>"._QXZ("RECORDING").":</td>";
			$HTML_text.="<td class='small_standard_bold' align='left' colspan='9' nowrap>$play_audio &nbsp; $location</td>";
			$HTML_text.="<tr>";
			###########

			$CSV_text.="\n";

			$x++;
			}

		for ($i=1; $i<=$max_questions; $i++)
			{
			$CSV_header.=",\"Checkpoint $i\",\"Comments $i\",\"Score $i\",\"Instant fail $i\"";
			}
		$CSV_header.="\n";

		$HTML_text.="</table>";

		}
	else
		{
		$HTML_text.="*** NO RESULTS FOUND ***";
		}

	}

$HTML_text.="\n\n<BR>";
$HTML_text.="</TD></TR></TABLE>";

$HTML_text.="</BODY></HTML>";









if ($file_download == 0 || !$file_download) 
	{
	echo $HTML_head;
	require("admin_header.php");
	echo $HTML_text;
	echo "<!--\n$CSV_header$CSV_text\n//-->\n";
	}
else
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "Quality_control_rpt_$US$FILE_TIME.csv";
	$CSV_text="$CSV_header$CSV_text";
	$CSV_text=preg_replace('/^\s+/', '', $CSV_text);
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

#$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
#if ($DB) {echo "|$stmt|\n";}
#$rslt=mysql_to_mysqli($stmt, $link);

function GetFullName($full_name)
	{
	global $link, $DB;
	$name_stmt="select full_name from vicidial_users where user='$full_name'";
	$name_rslt=mysql_to_mysqli($name_stmt, $link);
	while ($name_row=mysqli_fetch_array($name_rslt))
		{
		$full_name=$name_row["full_name"];
		}
	return $full_name;
	}

function GetFail($QCID)
	{
	global $link, $DB;
	$fail_stmt="select count(*) From quality_control_checkpoint_log where qc_log_id='$QCID' and instant_fail_value='Y'";
	$fail_rslt=mysql_to_mysqli($fail_stmt, $link);
	while ($fail_row=mysqli_fetch_row($fail_rslt))
		{
		$fail_count=$fail_row[0];
		}
	if ($fail_count>0) {return "Y";} else {return "N";}
	}

function GetResult($QCID)
	{
	global $link, $DB, $show_percentages;
	$passing_score=0;
	$total_score=0;

	$score_stmt="select qc_scorecard_id, sum(checkpoint_points_earned) from quality_control_checkpoint_log where qc_log_id='$QCID'";
	if ($DB) {echo $score_stmt."<BR>\n";}
	$score_rslt=mysql_to_mysqli($score_stmt, $link);
	while ($score_row=mysqli_fetch_array($score_rslt))
		{
		$scorecard_id=$score_row[0];
		$total_score=$score_row[1];
		$result_stmt="select passing_score from quality_control_scorecards where qc_scorecard_id='$scorecard_id'";
		$result_rslt=mysql_to_mysqli($result_stmt, $link);
		$result_row=mysqli_fetch_array($result_rslt);
		$passing_score=$result_row[0];
		}

	return ($total_score>=$passing_score ? "PASSED" : "FAILED");
	
	}

function GetScore($QCID, $checkpointID)
	{
	global $link, $DB, $show_percentages;
	$score="--";

	$checkpoint_clause="";
	if ($checkpointID) {$checkpoint_clause=" and qc_checkpoint_log_id='$checkpointID'";}

	$score_stmt="select sum(checkpoint_points), sum(checkpoint_points_earned) from quality_control_checkpoint_log where qc_log_id='$QCID' $checkpoint_clause";
	if ($DB) {echo $score_stmt."<BR>\n";}
	$score_rslt=mysql_to_mysqli($score_stmt, $link);
	$new_lead_IDs=array();
	while ($score_row=mysqli_fetch_array($score_rslt))
		{
		if ($show_percentages)
			{
			$score=sprintf("%.2f", (100*($score_row[1]/$score_row[0])))." %";
			}
		else
			{
			$score=$score_row[1]." / ".$score_row[0];
			}
		}
	return $score;
	}


exit;
?>

