<?php
# AST_chat_log_report.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# This is the report page where you can view report information of any of the dialer's chats.  The web page will 
# display the information about the chat, including the start time and participants, and will also provide links 
# allowing you to download transcripts of any chat you wish, based on your account permissions.
#
# CHANGES
#
# 150608-0647 - First build
# 160108-2300 - Changed some mysqli_query to mysql_to_mysqli for consistency
# 161217-0820 - Added chat-type to allow for multi-user internal chat sessions
# 170409-1550 - Added IP List validation code
# 191013-0857 - Fixes for PHP7
#

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
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["inbound_group"]))				{$inbound_group=$_GET["inbound_group"];}
	elseif (isset($_POST["inbound_group"]))	{$inbound_group=$_POST["inbound_group"];}
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["text_search"]))				{$text_search=$_GET["text_search"];}
	elseif (isset($_POST["text_search"]))	{$text_search=$_POST["text_search"];}
if (isset($_GET["report_display_type"]))				{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["chat_log_type"]))				{$chat_log_type=$_GET["chat_log_type"];}
	elseif (isset($_POST["chat_log_type"]))	{$chat_log_type=$_POST["chat_log_type"];}
if (isset($_GET["download_chat_id"]))				{$download_chat_id=$_GET["download_chat_id"];}
	elseif (isset($_POST["download_chat_id"]))	{$download_chat_id=$_POST["download_chat_id"];}
if (isset($_GET["download_chat_subid"]))				{$download_chat_subid=$_GET["download_chat_subid"];}
	elseif (isset($_POST["download_chat_subid"]))	{$download_chat_subid=$_POST["download_chat_subid"];}
if (isset($_GET["download_user"]))				{$download_user=$_GET["download_user"];}
	elseif (isset($_POST["download_user"]))	{$download_user=$_POST["download_user"];}
if (isset($_GET["download_manager"]))				{$download_manager=$_GET["download_manager"];}
	elseif (isset($_POST["download_manager"]))	{$download_manager=$_POST["download_manager"];}


if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Agent-Manager Chat Log';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and view_reports > 0;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports > 0;";
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
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
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

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($inbound_group)) {$inbound_group = array();}
if (!isset($user_group)) {$user_group = array();}
if (!isset($users)) {$users = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}


$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
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
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $users_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$i++;
	}

$i=0;
$inbound_group_string='|';
$inbound_group_ct = count($inbound_group);
while($i < $users_ct)
	{
	$inbound_group_string .= "$inbound_group[$i]|";
	$i++;
	}

$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	#if (preg_match('/\-ALL/',$group_string) )
	#	{$group[$i] = $groups[$i];}
	$i++;
	}

$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$user_groups=array();
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	# if (preg_match('/\-ALL/',$user_group_string)) {$user_group[$i]=$row[0];}
	$i++;
	}

$stmt="select group_id from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL where group_handling='CHAT' order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$inbound_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$inbound_groups_string='|';
$inbound_groups=array();
while ($i < $inbound_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$inbound_groups[$i] =$row[0];
	$inbound_groups_string .= "$inbound_groups[$i]|";
	$i++;
	}

$stmt="select user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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


$i=0;
$user_string='|';
$user_ct = count($users);
while($i < $user_ct)
	{
	$user_string .= "$users[$i]|";
#	$user_SQL .= "'$users[$i]',";
	$user_SQL .= " selected_agents like '%|".$users[$i]."|%' or ";
	$customer_user_SQL .= "'".$users[$i]."',";
	$userQS .= "&users[]=$users[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_string) ) or ($user_ct < 1) )
	{$user_SQL = ""; $customer_user_SQL = "";}
else
	{
	# $user_string=preg_replace("/^\||\|$/", "", $user_string);
	$ASCII_rpt_header.="   Agents: ".preg_replace("/\|/", ", ", $user_string);
	$user_SQL = preg_replace('/ or $/i', '',$user_SQL);
	$customer_user_SQL = preg_replace('/,$/i', '',$customer_user_SQL);
#	$user_agent_log_SQL = "and vicidial_agent_log.user IN($user_SQL)";
	$user_SQL = "and ($user_SQL or selected_agents='|')";
	$customer_user_SQL = "and chat_creator in($customer_user_SQL)";
	$ASCII_border_header.="----------------------------------------------------+";
	$ASCII_header       .=" ".sprintf("%-50s", "SELECTED AGENTS")." |";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>SELECTED AGENTS</th>";
	}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
#	$user_group_SQL .= "'$user_group[$i]',";
	$user_group_SQL .= " selected_user_groups like '%|".$user_group[$i]."|%' or ";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	# $user_group_string=preg_replace("/^\||\|$/", "", $user_group_string);
	$ASCII_rpt_header.="   User groups: ".preg_replace("/\|/", ", ", $user_group_string);
	$user_group_SQL = preg_replace('/ or $/i', '',$user_group_SQL);
#	$user_group_agent_log_SQL = "and vicidial_agent_log.user_group IN($user_group_SQL)";
	$user_group_SQL = "and ($user_group_SQL or selected_user_groups='|')";
	$ASCII_border_header.="----------------------------------------------------+";
	$ASCII_header       .=" ".sprintf("%-50s", "SELECTED USER GROUPS")." |";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>SELECTED USER GROUPS</th>";
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	if ( (preg_match("/ $group[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$group_string .= "$group[$i]|";
#		$group_SQL .= "'$group[$i]',";
		$group_SQL .= " selected_campaigns like '%|".$group[$i]."|%' or ";
		$groupQS .= "&group[]=$group[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	# $group_string=preg_replace("/^\||\|$/", "", $group_string);
	$ASCII_rpt_header.="   "._QXZ("Campaigns", 9).": ".preg_replace("/\|/", ", ", $group_string);
	$group_SQL = preg_replace('/ or $/i', '',$group_SQL);
	$group_SQL = "and ($group_SQL or selected_campaigns='|')";
	$ASCII_border_header.="----------------------------------------------------+";
	$ASCII_header       .=" ".sprintf("%-50s", "SELECTED CAMPAIGNS")." |";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>"._QXZ("SELECTED CAMPAIGNS")."</th>";
	}

$i=0;
$inbound_group_string='|';
$inbound_group_ct = count($inbound_group);
while($i < $inbound_group_ct)
	{
	if ( (preg_match("/ $inbound_group[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$inbound_group_string .= "$inbound_group[$i]|";
		$inbound_group_SQL .= "'$inbound_group[$i]',";
		$inbound_groupQS .= "&inbound_group[]=$inbound_group[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$inbound_group_string) ) or ($inbound_group_ct < 1) )
	{$inbound_group_SQL = "";}
else
	{
	# $group_string=preg_replace("/^\||\|$/", "", $group_string);
	$ASCII_rpt_header.=_QXZ("Inbound groups", 22).": ".preg_replace("/\|/", ", ", $inbound_group_string);
	$inbound_group_SQL = preg_replace('/,$/i', '',$inbound_group_SQL);
	$inbound_group_SQL = "and group_id in ($inbound_group_SQL)";
	$ASCII_border_header.="----------------------------------------------------+";
	$ASCII_header       .=" ".sprintf("%-50s", "SELECTED INBOUND GROUPS")." |";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>"._QXZ("SELECTED INBOUND GROUPS")."</th>";
	}


# Gather chat IDs containing text search value
if ($text_search) {
	$matching_text_chats=array();
	if ($chat_log_type=="INTERNAL") {
		$text_stmt="select distinct manager_chat_id from vicidial_manager_chat_log_archive where message like '%".mysqli_real_escape_string($link, $text_search)."%'";
	} else {
		$text_stmt="select distinct chat_id from vicidial_chat_log_archive where message like '%".mysqli_real_escape_string($link, $text_search)."%'";
	}
	if ($DB) {echo "$text_stmt<BR>\n";}
	$text_rslt=mysql_to_mysqli($text_stmt, $link);
	while ($text_row=mysqli_fetch_row($text_rslt)) {
		array_push($matching_text_chats, $text_row[0]);
	}
	if ($chat_log_type=="INTERNAL") {
		$matching_text_SQL=" and manager_chat_id in ('".implode("','", $matching_text_chats)."') ";
	} else {
		$matching_text_SQL=" and chat_id in ('".implode("','", $matching_text_chats)."') ";
	}
	$matching_text_subid_SQL=" and message like '%".mysqli_real_escape_string($link, $text_search)."%' ";
}

if ($DB) {echo "$user_group_string|$user_group_ct|$user_groupQS|$i<BR>\n";}

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date$groupQS$user_groupQS&shift=$shift&DB=$DB&show_percentages=$show_percentages";

$NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
$NWE = "')\"><IMG SRC=\"help.gif\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

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


$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function ToggleSpan(spanID) {\n";
$HTML_text.="  if (document.getElementById(spanID).style.display == 'none') {\n";
$HTML_text.="    document.getElementById(spanID).style.display = 'block';\n";
$HTML_text.="  } else {\n";
$HTML_text.="    document.getElementById(spanID).style.display = 'none';\n";
$HTML_text.="  }\n";
$HTML_text.=" }\n";
$HTML_text.="</script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

#	require("admin_header.php");

$HTML_text.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLSPACING=3><TR><TD VALIGN=TOP> "._QXZ("Dates").":<BR>";
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

$HTML_text.="</TD><TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
	{
	if (preg_match("/$groups[$o]\|/i",$group_string)) {$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>";

$HTML_text.="<TD VALIGN=TOP>"._QXZ("Inbound Groups").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=inbound_group[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$inbound_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL INBOUND GROUPS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL INBOUND GROUPS")." --</option>\n";}
$o=0;
while ($inbound_groups_to_print > $o)
	{
	if  (preg_match("/$inbound_groups[$o]\|/i",$inbound_group_string)) {$HTML_text.="<option selected value=\"$inbound_groups[$o]\">$inbound_groups[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$inbound_groups[$o]\">$inbound_groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>";

$HTML_text.="<TD VALIGN=TOP>"._QXZ("User Groups").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=user_group[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/$user_groups[$o]\|/i",$user_group_string)) {$HTML_text.="<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD><TD VALIGN=TOP>"._QXZ("Users").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=users[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$users_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USERS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USERS")." --</option>\n";}
$o=0;
while ($users_to_print > $o)
	{
	if  (preg_match("/$user_list[$o]\|/i",$users_string)) {$HTML_text.="<option selected value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";
$HTML_text.="<TD VALIGN=TOP>";
$HTML_text.=_QXZ("Chat type").": <BR>";
$HTML_text.="<SELECT NAME='chat_log_type'>\n";
if ($chat_log_type) {
	$HTML_text.="<option value=\"$chat_log_type\" selected>-- "._QXZ("$chat_log_type")." --</option>";
}
$HTML_text.="<option value=\"INTERNAL\">-- "._QXZ("INTERNAL")." --</option>\n";
$HTML_text.="<option value=\"CUSTOMER\">-- "._QXZ("CUSTOMER")." --</option>\n";
$HTML_text.="</SELECT>\n<BR><BR>";
$HTML_text.=_QXZ("Chat text").":<BR>";
$HTML_text.="<textarea name='text_search' rows='3' cols='20'>$text_search</textarea><BR><BR>";
$HTML_text.=_QXZ("Display as").":<BR>";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select><BR><BR>\n";
$HTML_text.="<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>$NWB#agent_performance_detail$NWE\n";
$HTML_text.="</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";

$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
$HTML_text.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$HTML_text.="</FONT>\n";
$HTML_text.="</TD></TR></TABLE>";

$HTML_text.="</FORM>\n\n";


$HTML_text.="<PRE><FONT SIZE=2>\n";

if (!$group)
	{
	$HTML_text.="\n";
	$HTML_text.=_QXZ("PLEASE SELECT A CAMPAIGN AND DATE-TIME ABOVE AND CLICK SUBMIT")."\n";
	$HTML_text.=" "._QXZ("NOTE: stats taken from shift specified")."\n";
	}
else 
	{
	if ($shift == 'AM') {
		$time_BEGIN=$AM_shift_BEGIN;
		$time_END=$AM_shift_END;
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "03:45:00";}   
		if (strlen($time_END) < 6) {$time_END = "15:14:59";}
	}
	if ($shift == 'PM') {
		$time_BEGIN=$PM_shift_BEGIN;
		$time_END=$PM_shift_END;
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "15:15:00";}
		if (strlen($time_END) < 6) {$time_END = "23:15:00";}
	}
	if ($shift == 'ALL') {
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
		if (strlen($time_END) < 6) {$time_END = "23:59:59";}
	}
	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";

	$HTML_text.=_QXZ("Agent/Manager Chat Log Report",47)." $NOW_TIME\n";

	$HTML_text.=_QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
	$HTML_text.="---------- "._QXZ("$chat_log_type CHAT LOG Details")." -------------<a name='rpt_anchor'>\n\n";


	if ($chat_log_type=="INTERNAL") {
		$stmt="select * from vicidial_manager_chats_archive where chat_start_date>='$query_date 00:00:00' and chat_start_date<='$end_date 23:59:59' $user_SQL$matching_text_SQL order by chat_start_date asc";
		if ($DB) {echo "$stmt<BR>\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		if (mysqli_num_rows($rslt)>0) 
			{
			$ASCII_text.="$ASCII_rpt_header\n";
			if ($DB) {$ASCII_text.=$stmt."\n";}
			if ($DB) {$GRAPH_text.=$stmt."\n";}
			$ASCII_border="+---------+---------------------+-----------+------------+---------+$ASCII_border_header\n";
			$ASCII_header="| "._QXZ("CHAT ID",7)." | "._QXZ("CHAT START DATE",19)." | "._QXZ("CHAT TYPE",9)." | "._QXZ("STARTED BY",10)." | "._QXZ("REPLIES",7)." |$ASCII_header\n";
			$ASCII_text.=$ASCII_border.$ASCII_header.$ASCII_border;

			$GRAPH_text.="<BR><BR><a name='callstatsgraph'/><table border='0' cellpadding='0' cellspacing='2' width='800'>";
			$GRAPH_text.="<tr><th class='column_header grey_graph_cell'>"._QXZ("CHAT ID")."</th><th class='column_header grey_graph_cell'>"._QXZ("CHAT START DATE")."</th><th class='column_header grey_graph_cell'>"._QXZ("MANAGER")."</th><th class='column_header grey_graph_cell'>"._QXZ("REPLIES")."</th>$GRAPH_header<th class='thgraph'>&nbsp;</th><th class='thgraph'>&nbsp;</th></tr>";

			while ($row=mysqli_fetch_array($rslt)) 
				{
				$colspan='6';
				$ASCII_text.="| ".sprintf("%7s", $row["manager_chat_id"])." ";
				$ASCII_text.="| ".$row["chat_start_date"]." ";
				$ASCII_text.="| ".sprintf("%9s", _QXZ($row["internal_chat_type"], 9))." ";
				$ASCII_text.="| ".sprintf("%-10s", $row["manager"])." ";
				$ASCII_text.="|    ".$row["allow_replies"]."    ";
				$selected_agents=preg_replace('/^\|$/', "ALL", $row["selected_agents"]);
				$selected_user_groups=preg_replace('/^\|$/', "ALL", $row["selected_user_groups"]);
				$selected_campaigns=preg_replace('/^\|$/', "ALL", $row["selected_campaigns"]);
				$selected_agents=preg_replace('/^\||\|$/', '', $selected_agents);
				$selected_user_groups=preg_replace('/^\||\|$/', '', $selected_user_groups);
				$selected_campaigns=preg_replace('/^\||\|$/', '', $selected_campaigns);
				$selected_agents=preg_replace('/\|/', ', ', $selected_agents);
				$selected_user_groups=preg_replace('/\|/', ', ', $selected_user_groups);
				$selected_campaigns=preg_replace('/\|/', ', ', $selected_campaigns);

				$GRAPH_text.="<tr><th class='thgraph' scope='col'>$row[manager_chat_id]</th><th class='thgraph' scope='col' nowrap>$row[chat_start_date]</th><th class='thgraph' scope='col'>"._QXZ("$row[manager]")."</th><th class='thgraph' scope='col'>$row[allow_replies]</th>";
				if (!preg_match("/\-\-ALL\-\-/", $user_string)) {
					$ASCII_text.="| ".sprintf("%-50s", $selected_agents)." ";
					$GRAPH_text.="<th class='thgraph' scope='col'>$selected_agents</th>";
					$colspan++;
				}
				if (!preg_match("/\-\-ALL\-\-/", $user_group_string)) {
					$ASCII_text.="| ".sprintf("%-50s", $selected_user_groups)." ";
					$GRAPH_text.="<th class='thgraph' scope='col'>$selected_user_groups</th>";
					$colspan++;
				}
				if (!preg_match("/\-\-ALL\-\-/", $group_string)) {
					$ASCII_text.="| ".sprintf("%-50s", $selected_campaigns)." ";
					$GRAPH_text.="<th class='thgraph' scope='col'>$selected_campaigns</th>";
					$colspan++;
				}

				$ASCII_text.="| <a href='$LINKbase&file_download=1&chat_log_type=".$chat_log_type."&download_chat_id=".$row["manager_chat_id"]."&download_manager=".$row["manager"]."'>["._QXZ("DOWNLOAD FULL LOG")."]</a>  <a href='#rpt_anchor' onclick=\"ToggleSpan('ChatReport".$row["manager_chat_id"]."')\">["._QXZ("SHOW INDIVIDUAL CHATS")."]</a>\n";

				$GRAPH_text.="<th class='thgraph' scope='col' nowrap><a href='$LINKbase&file_download=1&chat_log_type=".$chat_log_type."&download_chat_id=".$row["manager_chat_id"]."&download_manager=".$row["manager"]."'>["._QXZ("DOWNLOAD FULL LOG")."]</a></th><th class='thgraph' scope='col' nowrap> <a href='#rpt_anchor' onclick=\"ToggleSpan('ChatReport".$row["manager_chat_id"]."')\">["._QXZ("SHOW INDIVIDUAL CHATS")."]</a></th></tr>";

				$sub_stmt="select manager_chat_subid, v.user, vu.full_name, count(*) from vicidial_manager_chat_log_archive v, vicidial_users vu where manager_chat_id='".$row["manager_chat_id"]."' and v.user!=v.manager and v.user=vu.user $matching_text_subid_SQL group by manager_chat_subid, user, full_name order by manager_chat_subid asc";
				if ($DB) {echo "$sub_stmt<BR>\n";}
				$sub_rslt=mysql_to_mysqli($sub_stmt, $link);

				$ASCII_text.="<span id='ChatReport".$row["manager_chat_id"]."' style='display: none;'>";
				$ASCII_text.="+---------+-------------+------------------------------------------+\n";
				$ASCII_text.="          | "._QXZ("CHAT SUB-ID",11)." | "._QXZ("AGENT",40)." |\n";
				$ASCII_text.="          +-------------+------------------------------------------+\n";

				$GRAPH_text.="<tr><td align='center' colspan='$colspan'><span id='ChatReport".$row["manager_chat_id"]."' style='display: none;'><table width='600'>";
				$GRAPH_text.="  <tr><th class='column_header grey_graph_cell'>"._QXZ("CHAT SUB-ID")."</th><th class='column_header grey_graph_cell'>"._QXZ("AGENT")."</th><th class='thgraph'>&nbsp;</th><th class='thgraph'>&nbsp;</th></tr>";
				while ($sub_row=mysqli_fetch_array($sub_rslt)) 
					{
					$ASCII_text.="          | ".sprintf("%-11s", $sub_row["manager_chat_subid"])." ";
					$ASCII_text.="| ".sprintf("%-40s", substr("$sub_row[manager_chat_subid] - $sub_row[full_name]", 0, 40))." | <a href='$LINKbase&file_download=1&chat_log_type=".$chat_log_type."&download_chat_id=".$row["manager_chat_id"]."&download_chat_subid=".$sub_row["manager_chat_subid"]."&download_manager=".$row["manager"]."&download_user=".$row["user"]."'>["._QXZ("DOWNLOAD CHAT LOG")."]</a>\n";
					$GRAPH_text.="  <tr><th class='thgraph' scope='col'>".$sub_row["manager_chat_subid"]."</th><th class='thgraph' scope='col'>$sub_row[manager_chat_subid] - $sub_row[full_name]</th><th class='thgraph'><a href='$LINKbase&file_download=1&chat_log_type=".$chat_log_type."&download_chat_id=".$row["manager_chat_id"]."&download_chat_subid=".$sub_row["manager_chat_subid"]."&download_manager=".$row["manager"]."&download_user=".$row["user"]."'>["._QXZ("DOWNLOAD CHAT LOG")."]</a></tr>";
					}
				$ASCII_text.="          +-------------+------------------------------------------+\n";
				$ASCII_text.="\n</span>";

				$GRAPH_text.="</table></span></td></tr>";

				}
			$ASCII_text.=$ASCII_border;
			$GRAPH_text.="</table>";
			}
	} else if ($chat_log_type=="CUSTOMER") {
		#	chat_id | chat_start_time     | status | chat_creator | group_id        | lead_id - vicidial_chat_archive
		$stmt="select vca.*, v.first_name, v.last_name from vicidial_chat_archive vca, vicidial_list v where chat_start_time>='$query_date 00:00:00' and chat_start_time<='$end_date 23:59:59' and vca.lead_id=v.lead_id $inbound_group_SQL$ingroup_SQL$customer_user_SQL$matching_text_SQL order by chat_start_time asc";
		if ($DB) {echo "**********$stmt<BR>\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		if (mysqli_num_rows($rslt)>0) 
			{
			$ASCII_text.="$ASCII_rpt_header\n";
			if ($DB) {$ASCII_text.=$stmt."\n";}
			if ($DB) {$GRAPH_text.=$stmt."\n";}
			$ASCII_border="+---------+---------------------+------------+----------------------------------------------------+$ASCII_border_header\n";
			$ASCII_header="| "._QXZ("CHAT ID",7)." | "._QXZ("CHAT START DATE",19)." | "._QXZ("AGENT",10)." | "._QXZ("CUSTOMER",50)." |$ASCII_header\n";
			$ASCII_text.=$ASCII_border.$ASCII_header.$ASCII_border;

			$GRAPH_text.="<BR><BR><a name='callstatsgraph'/><table border='0' cellpadding='0' cellspacing='2' width='800'>";
			$GRAPH_text.="<tr><th class='column_header grey_graph_cell'>"._QXZ("CHAT ID")."</th><th class='column_header grey_graph_cell'>"._QXZ("CHAT START DATE")."</th><th class='column_header grey_graph_cell'>"._QXZ("AGENT")."</th><th class='column_header grey_graph_cell'>"._QXZ("CUSTOMER")."</th>$GRAPH_header<th class='thgraph'>&nbsp;</th><th class='thgraph'>&nbsp;</th></tr>";

			while ($row=mysqli_fetch_array($rslt)) 
				{
				$colspan='5';
				$ASCII_text.="| ".sprintf("%7s", $row["chat_id"])." ";
				$ASCII_text.="| ".$row["chat_start_time"]." ";
				$ASCII_text.="| ".sprintf("%-10s", $row["chat_creator"])." ";
				$ASCII_text.="| ".sprintf("%-50s", "$row[first_name] $row[last_name]")." ";
				$selected_agents=preg_replace('/^\|$/', "ALL", $row["selected_agents"]);
				$selected_inbound_groups=preg_replace('/^\|$/', "ALL", $row["selected_inbound_groups"]);
				#$selected_campaigns=preg_replace('/^\|$/', "ALL", $row["selected_campaigns"]);
				$selected_agents=preg_replace('/^\||\|$/', '', $selected_agents);
				$selected_inbound_groups=preg_replace('/^\||\|$/', '', $selected_inbound_groups);
				#$selected_campaigns=preg_replace('/^\||\|$/', '', $selected_campaigns);
				$selected_agents=preg_replace('/\|/', ', ', $selected_agents);
				$selected_inbound_groups=preg_replace('/\|/', ', ', $selected_inbound_groups);
				#$selected_campaigns=preg_replace('/\|/', ', ', $selected_campaigns);

				$GRAPH_text.="<tr><th class='thgraph' scope='col'>$row[chat_id]</th><th class='thgraph' scope='col' nowrap>$row[chat_start_time]</th><th class='thgraph' scope='col'>$row[chat_creator]</th>";
				if (!preg_match("/\-\-ALL\-\-/", $user_string)) {
					$ASCII_text.="| ".sprintf("%-50s", $selected_agents)." ";
					$GRAPH_text.="<th class='thgraph' scope='col'>$selected_agents</th>";
					$colspan++;
				}
				if (!preg_match("/\-\-ALL\-\-/", $inbound_group_string)) {
					$ASCII_text.="| ".sprintf("%-50s", $selected_inbound_groups)." ";
					$GRAPH_text.="<th class='thgraph' scope='col'>$selected_inbound_groups</th>";
					$colspan++;
				}

				$ASCII_text.="| <a href='$LINKbase&file_download=1&download_chat_id=".$row["chat_id"]."&chat_log_type=".$chat_log_type."&download_manager=".$row["chat_creator"]."'>["._QXZ("DOWNLOAD FULL LOG")."]</a>  <a href='#rpt_anchor' onclick=\"ToggleSpan('ChatReport".$row["chat_id"]."')\">["._QXZ("SHOW CHAT")."]</a>\n";

				$GRAPH_text.="<th class='thgraph' scope='col' nowrap><a href='$LINKbase&file_download=1&download_chat_id=".$row["chat_id"]."&chat_log_type=".$chat_log_type."&download_manager=".$row["chat_creator"]."'>["._QXZ("DOWNLOAD FULL LOG")."]</a></th><th class='thgraph' scope='col' nowrap> <a href='#rpt_anchor' onclick=\"ToggleSpan('ChatReport".$row["chat_id"]."')\">["._QXZ("SHOW CHAT")."]</a></th></tr>";
				### YOU LEFT OFF HERE 4/19

		#message_row_id | chat_id | message | message_time        | poster           | chat_member_name | chat_level

				$sub_stmt="select message_time, chat_member_name, message, chat_id from vicidial_chat_log_archive where chat_id='".$row["chat_id"]."' $matching_text_subid_SQL order by message_time asc";
				if ($DB) {echo "$sub_stmt<BR>\n";}
				$sub_rslt=mysql_to_mysqli($sub_stmt, $link);

				$ASCII_text.="<span id='ChatReport".$row["chat_id"]."' style='display: none;'>";
				$ASCII_text.="+---------+---------------------+------------------------------+-------------------------------------------------------+\n";
				$ASCII_text.="          | "._QXZ("MESSAGE TIME",19)." | "._QXZ("CHAT MEMBER NAME",28)." | "._QXZ("MESSAGE",53)." |\n";
				$ASCII_text.="          +---------------------+------------------------------+-------------------------------------------------------+\n";

				$GRAPH_text.="<tr><td align='center' colspan='$colspan'><span id='ChatReport".$row["chat_id"]."' style='display: none;'><table width='600'>";
				$GRAPH_text.="  <tr><th class='column_header grey_graph_cell'>"._QXZ("MESSAGE TIME")."</th><th class='column_header grey_graph_cell'>"._QXZ("CHAT MEMBER NAME")."</th><th class='thgraph'>"._QXZ("MESSAGE")."</th><th class='thgraph'>&nbsp;</th></tr>";
				while ($sub_row=mysqli_fetch_array($sub_rslt)) 
					{
					$pos=strpos($sub_row["message"], "\n");
					if ($pos) 
						{
						$sub_row["message"]=substr($sub_row["message"],0,$pos)."...";
						}
					if (strlen($sub_row["message"])>50) {$message=substr($sub_row["message"],0,50)."...";} else {$message=$sub_row["message"];}
					$ASCII_text.="          | ".$sub_row["message_time"]." ";
					$ASCII_text.="| ".sprintf("%-28s", substr("$sub_row[chat_member_name]", 0, 28))." ";
					$ASCII_text.="| ".sprintf("%-53s", $message)." | \n";
					# <a href='$LINKbase&file_download=1&download_chat_id=".$sub_row["chat_id"]."&download_manager=".$row["manager"]."&chat_log_type=".$chat_log_type."&download_user=".$row["user"]."'>["._QXZ("DOWNLOAD CHAT LOG")."]</a>
					$GRAPH_text.="  <tr><th class='thgraph' scope='col'>$sub_row[message_time]</th><th class='thgraph' scope='col'>$sub_row[chat_member_name]</th><th class='thgraph' scope='col'>$message</th><th class='thgraph'>&nbsp;</tr>";
					}
				$ASCII_text.="          +---------------------+------------------------------+-------------------------------------------------------+\n";
				$ASCII_text.="\n</span>";

				$GRAPH_text.="</table></span></td></tr>";

				}

			$ASCII_text.=$ASCII_border;
			$GRAPH_text.="</table>";
			}
	}

	if ($file_download == 1)
		{
		$CSV_text.="\""._QXZ("$chat_log_type Chat Log Report")." $NOW_TIME\"\n";
		$CSV_text.="\""._QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\"\n";
		$ASCII_rpt_header=preg_replace('/\s\s\s/i', "\",\"", $ASCII_rpt_header);
		$CSV_text.="\"".$ASCII_rpt_header."\"\n";

		$mgr_stmt="select full_name from vicidial_users where user='$download_manager'";
		$mgr_rslt=mysql_to_mysqli($mgr_stmt, $link);
		$mgr_row=mysqli_fetch_row($mgr_rslt);
		$mgr_name=$mgr_row[0];

		if ($DB) {$CSV_text.="|$mgr_stmt|\n";}

		if ($chat_log_type=="INTERNAL") {
			$user_stmt="select vu.user, vu.full_name from vicidial_users vu, vicidial_manager_chat_log_archive v where manager_chat_id='$download_chat_id' and v.user=vu.user";
			if ($download_chat_subid) {$user_stmt.=" and manager_chat_subid='$download_chat_subid' ";}
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			$user_name=array();
			while ($user_row=mysqli_fetch_row($user_rslt)) {
				$user_name["$user_row[0]"]=$user_row[1];
			}

			$csv_stmt="select distinct message_date, message_posted_by, message from vicidial_manager_chat_log_archive where manager_chat_id='$download_chat_id' ";
			if ($download_chat_subid) {$csv_stmt.=" and manager_chat_subid='$download_chat_subid' ";}
			$csv_stmt.=" order by message_date asc";
			$csv_rslt=mysql_to_mysqli($csv_stmt, $link);
			if ($DB) {$CSV_text.="|$csv_stmt|\n";}

			$CSV_text.="\""._QXZ("MESSAGE DATE")."\",\""._QXZ("MESSAGE POSTED BY")."\",\""._QXZ("MESSAGE")."\"\n";

			while($csv_row=mysqli_fetch_array($csv_rslt)) 
				{
				if ($csv_row["message_posted_by"]==$download_manager) {$poster="$mgr_name";} else {$poster=$user_name["$csv_row[message_posted_by]"];}
				$CSV_text.="\"".$csv_row["message_date"]."\",\"".$poster."\",\"".$csv_row["message"]."\"\n";
				}
		} else if ($chat_log_type=="CUSTOMER") {
			$csv_stmt="select * from vicidial_chat_log_archive where chat_id='$download_chat_id'  order by message_time asc";
			$csv_rslt=mysql_to_mysqli($csv_stmt, $link);

			$CSV_text.="\""._QXZ("MESSAGE TIME")."\",\""._QXZ("MESSAGE POSTED BY")."\",\""._QXZ("MESSAGE")."\"\n";
			while($csv_row=mysqli_fetch_array($csv_rslt)) 
				{
				$CSV_text.="\"".$csv_row["message_time"]."\",\"".$csv_row["chat_member_name"]."\",\"".$csv_row["message"]."\"\n";
				}
		}
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_CHAT_LOG$US$FILE_TIME.csv";

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

	if ($report_display_type=="HTML")
		{
		$HTML_text.=$GRAPH_text;
		}
	else 
		{
		$HTML_text.=$ASCII_text;
		}

	$HTML_text.="\n\n<BR>$db_source";
	$HTML_text.="</TD></TR></TABLE>";

	$HTML_text.="</BODY></HTML>";
	}

	if ($file_download == 0 || !$file_download) 
		{
		echo $HTML_head;
		require("admin_header.php");
		echo $HTML_text;
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
?>
