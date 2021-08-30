<?php
# AST_rt_whiteboard_rpt.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# Real-time report that allows users to create a customized, graphical display of various data sets
#
# 171027-2352 - First build
# 180129-1745 - Translation corrections, uses vicidial_state_report_functions.php instead of vicidial_state_report_functions.js
# 180507-2315 - Added new help display
# 180512-0000 - Fixed slave server capability
# 190927-1758 - Fixed PHP7 array issue
# 210827-1818 - Fix for security issue
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

if (file_exists('options.php'))
	{
	require('options.php');
	}

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
if (isset($_GET["campaigns"]))				{$campaigns=$_GET["campaigns"];}
	elseif (isset($_POST["campaigns"]))		{$campaigns=$_POST["campaigns"];}
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["user_groups"]))					{$user_groups=$_GET["user_groups"];}
	elseif (isset($_POST["user_groups"]))			{$user_groups=$_POST["user_groups"];}
if (isset($_GET["groups"]))					{$groups=$_GET["groups"];}
	elseif (isset($_POST["groups"]))			{$groups=$_POST["groups"];}
if (isset($_GET["dids"]))					{$dids=$_GET["dids"];}
	elseif (isset($_POST["dids"]))			{$dids=$_POST["dids"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

$query_date = preg_replace('/[^-0-9]/','',$query_date);
$end_date = preg_replace('/[^-0-9]/','',$end_date);
$query_time=preg_replace("/[^0-9\:]/", "", $query_time);
$end_time=preg_replace("/[^0-9\:]/", "", $end_time);
$campaigns=preg_replace('/[^-_0-9\p{L}]/u','',$campaigns);
$users=preg_replace('/[^-_0-9\p{L}]/u','',$users);
$user_groups=preg_replace('/[^-_0-9\p{L}]/u','',$user_groups);
$groups=preg_replace('/[^-_0-9\p{L}]/u','',$groups);
$dids=preg_replace('/[^-_0-9\p{L}]/u','',$dids);
$file_download = preg_replace('/[^0-9]/','',$file_download);
$submit=preg_replace('/[^-_0-9\p{L}]/u','',$submit);
$SUBMIT=preg_replace('/[^-_0-9\p{L}]/u','',$SUBMIT);
$DB = preg_replace('/[^0-9]/','',$DB);
$report_display_type=preg_replace('/[^_\p{L}]/u','',$report_display_type);

if (!$query_date) {$query_date=date("Y-m-d");}
if (!$end_date) {$end_date=date("Y-m-d");}
if (!$query_time) {$query_time="08:00:00";}
if (!$end_time) {$end_time="17:00:00";}

if (strlen($shift)<2) {$shift='ALL';}
$report_name="Real-Time Whiteboard Report";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,admin_screen_colors FROM system_settings;";
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
	$admin_screen_colors =			$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt = "SELECT local_gmt FROM servers where active='Y' limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$gmt_conf_ct = mysqli_num_rows($rslt);
$dst = date("I");
if ($gmt_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$local_gmt =		$row[0];
	$epoch_offset =		(($local_gmt + $dst) * 3600);
	}

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
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name,/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

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

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($groups)) {$groups = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$MAIN.="|$stmt|\n";}
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


if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

	
$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($campaigns)) {$campaigns = array();}
if (!isset($users)) {$users = array();}
if (!isset($user_groups)) {$user_groups = array();}
if (!isset($dids)) {$dids = array();}
if (!isset($groups)) {$groups = array();}
if (!isset($report_display_type)) {$report_display_type = "HTML";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}


$stmt="SELECT campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
if ($DB) {$MAIN.="$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$campaign_string='|';
$campaigns_selected=count($campaigns);
$campaign_list=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$campaign_list[$i] =		$row[0];
	$campaign_string .= "$campaign_list[$i]|";
	for ($j=0; $j<$campaigns_selected; $j++) {
		if ($campaigns[$j] && $campaign_list[$i]==$campaigns[$j]) {$campaign_name_str.="$campaign_list[$i] - $campaign_names[$i], ";}
		if ($campaigns[$j]=="--ALL--") {$campaigns_selected_str.="'$campaign_list[$i]', ";}
	}
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
$stmt="SELECT user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
$user_array=array(); # For quick full-name reference
$user_list=array();
$user_names=array();
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_list[$i]=$row[0];
	$user_names[$i]=$row[1];
	$user_array["$row[0]"]=$row[1];
	$i++;
	}


$i=0;
$user_groups_string='|';
$user_groups_ct = count($user_groups);
while($i < $user_groups_ct)
	{
	$user_groups_string .= "$user_groups[$i]|";
	$i++;
	}
$stmt="SELECT user_group, group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$user_group_list=array();
$user_group_names=array();
$user_group_array=array(); # For quick full-name reference
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_group_list[$i]=$row[0];
	$user_group_names[$i]=$row[1];
	$user_group_array["$row[0]"]=$row[1];
	$i++;
	}


$stmt="SELECT group_id,group_name from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
#$LISTgroups[$i]='---NONE---';
#$i++;
# $groups_to_print++;
$groups_string='|';
$LISTgroups=array();
$LISTgroup_names=array();
$LISTgroup_ids=array();
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTgroups[$i] =		$row[0];
	$LISTgroup_names[$i] =	$row[1];
	$LISTgroup_ids[$i] =	$row[2];
	$groups_string .= "$LISTgroups[$i]|";
	$i++;
	}


$i=0;
$group_string='|';
$group_ct = count($groups);
while($i < $group_ct)
	{
	if ( (strlen($groups[$i]) > 0) and (preg_match("/\|$groups[$i]\|/",$groups_string)) )
		{
		$group_string .= "$groups[$i]|";
		$group_SQL .= "'$groups[$i]',";
		$groupQS .= "&groups[]=$groups[$i]";
		}
	$i++;
	}
if ( (preg_match('/\s\-\-NONE\-\-\s/',$group_string) ) or ($group_ct < 1) )
	{
	$group_SQL = "''";
	}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	}
if (strlen($group_SQL)<3) {$group_SQL="''";}


$i=0;
$dids_string='|';
$dids_ct = count($dids);
while($i < $dids_ct)
	{
	$dids_string .= "$dids[$i]|";
	$i++;
	}
$stmt="SELECT did_pattern,did_description,did_id from vicidial_inbound_dids $whereLOGadmin_viewable_groupsSQL order by did_pattern;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$dids_to_print = mysqli_num_rows($rslt);
$i=0;
$did_pattern=array();
$did_description=array();
$did_ids=array();
$did_array=array(); # For quick full-name reference
while ($i < $dids_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$did_pattern[$i]=$row[0];
	$did_description[$i]=$row[1];
	$did_ids[$i]=$row[2];
	$did_array["$row[0]"]=$row[1];
	$i++;
	}

$i=0;
$campaigns_string='|';
$campaign_ct = count($campaigns);
while($i < $campaign_ct)
	{
	if (in_array("--ALL--", $campaigns))
		{
		$campaigns_string = "--ALL--";
		$campaign_SQL .= "'$campaigns[$i]',";
		$campaignQS = "&campaign[]=--ALL--";
		}
	else if ( (strlen($campaigns[$i]) > 0) and (!preg_match("/\|$campaigns[$i]\|/",$campaigns_string)) )
		{
		$campaigns_string .= "$campaigns[$i]|";
		$campaign_SQL .= "'$campaigns[$i]',";
		$campaignQS .= "&campaigns[]=$campaigns[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$campaign_string) ) or ($campaign_ct < 1) )
	{
	$campaign_SQL = "";
	}
else
	{
	$campaign_SQL = preg_replace('/,$/i', '',$campaign_SQL);
	$WHEREcampaign_SQL=" where campaign_id in ($campaign_SQL) ";
	$campaign_SQL=" and campaign_id in ($campaign_SQL) ";
	}
if (strlen($campaign_SQL)<3) 
	{
	$campaign_SQL="";
	} 

$i=0;
$users_string='|';
$user_ct = count($users);
while($i < $user_ct)
	{
	if (in_array("--ALL--", $users))
		{
		$users_string = "--ALL--";
		$user_SQL .= "'$users[$i]',";
		$userQS = "&users[]=--ALL--";
		}
	else if ( (strlen($users[$i]) > 0) and (!preg_match("/\|$users[$i]\|/",$users_string)) )
		{
		$users_string .= "$users[$i]|";
		$user_SQL .= "'$users[$i]',";
		$userQS .= "&users[]=$users[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$users_string) ) or ($user_ct < 1) )
	{
	$user_SQL = "";
	}
else
	{
	$user_SQL = preg_replace('/,$/i', '',$user_SQL);
	$user_SQL = "and agent_user IN($user_SQL)";
	}
if (strlen($user_SQL)<3) {$user_SQL="";}

$i=0;
$user_groups_string='|';
$user_group_ct = count($user_groups);
while($i < $user_group_ct)
	{
	if (in_array("--ALL--", $user_groups))
		{
		$user_groups_string = "--ALL--";
		$user_group_SQL .= "'$user_groups[$i]',";
		$user_groupQS = "&users[]=--ALL--";
		}
	else if ( (strlen($user_groups[$i]) > 0) and (!preg_match("/\|$user_groups[$i]\|/",$user_groups_string)) )
		{
		$user_groups_string .= "$user_groups[$i]|";
		$user_group_SQL .= "'$user_groups[$i]',";
		$user_groupQS .= "&users[]=$user_groups[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_groups_string) ) or ($user_group_ct < 1) )
	{
	$user_group_SQL = "";
	}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$user_group_SQL = "and user_group IN($user_group_SQL)";
	}
if (strlen($user_group_SQL)<3) {$user_group_SQL="";}

$i=0;
$groups_string='|';
$group_ct = count($groups);
while($i < $group_ct)
	{
	if (in_array("--ALL--", $groups))
		{
		$groups_string = "--ALL--";
		$group_SQL .= "'$groups[$i]',";
		$groupQS = "&users[]=--ALL--";
		}
	else if ( (strlen($groups[$i]) > 0) and (!preg_match("/\|$groups[$i]\|/",$groups_string)) )
		{
		$groups_string .= "$groups[$i]|";
		$group_SQL .= "'$groups[$i]',";
		$groupQS .= "&users[]=$groups[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$groups_string) ) or ($group_ct < 1) )
	{
	$group_SQL = "";
	}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_SQL = "and agent_user IN($group_SQL)";
	}
if (strlen($group_SQL)<3) {$group_SQL="";}



##### BEGIN Define colors and logo #####
$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='B9CBFD';
$SSstd_row3_background='8EBCFD';
$SSstd_row4_background='B6D3FC';
$SSstd_row5_background='FFFFFF';
$SSalt_row1_background='BDFFBD';
$SSalt_row2_background='99FF99';
$SSalt_row3_background='CCFFCC';

if ($admin_screen_colors != 'default')
	{
	$asc_stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$admin_screen_colors';";
	$asc_rslt=mysql_to_mysqli($stmt, $link);
	$qm_conf_ct = mysqli_num_rows($rslt);
	if ($qm_conf_ct > 0)
		{
		$asc_row=mysqli_fetch_row($asc_rslt);
		$SSmenu_background =            $asc_row[0];
		$SSframe_background =           $asc_row[1];
		$SSstd_row1_background =        $asc_row[2];
		$SSstd_row2_background =        $asc_row[3];
		$SSstd_row3_background =        $asc_row[4];
		$SSstd_row4_background =        $asc_row[5];
		$SSstd_row5_background =        $asc_row[6];
		$SSalt_row1_background =        $asc_row[7];
		$SSalt_row2_background =        $asc_row[8];
		$SSalt_row3_background =        $asc_row[9];
		$SSweb_logo =		           $asc_row[10];
		}
	}



# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$rerun_rpt_URL="$PHP_SELF?query_date=$query_date&end_date=$end_date&report_display_type=$report_display_type$campaignQS$userQS$managerQS&SUBMIT=$SUBMIT";

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";

$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
# $HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.css\" />\n";
$HEADER.="<script src='chart/Chart.js'></script>\n"; 
$HEADER.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#rt_whiteboard_report$NWE\n";

$MAIN.="<span id='report_control_panel' style='display:block'>";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=3 border='0' class=\"question_td\"><TR>";
$MAIN.="<TD VALIGN='TOP' width='300'>";
$MAIN.="<FONT class='embossed'>"._QXZ("Report Type").":</font> $NWB#rt_whiteboard_report-report_type$NWE<BR>";

$MAIN.="<SELECT NAME=report_type ID=report_type class='form_field sm_shadow round_corners' style='width:270px' onChange=HighlightRelatedFields(this.value)>\n";
$MAIN.="<option value='' selected>-- "._QXZ("Select a report")." --</option>";
$MAIN.="<option value='status_performance_total'>"._QXZ("Disposition Totals")."</option>";
$MAIN.="<option value='agent_performance_total'>"._QXZ("Agent Performance Totals")."</option>";
$MAIN.="<option value='agent_performance_rates'>"._QXZ("Agent Performance Rates")."</option>";
$MAIN.="<option value='team_performance_total'>"._QXZ("Team Performance Totals")."</option>";
$MAIN.="<option value='team_performance_rates'>"._QXZ("Team Performance Rates")."</option>";
$MAIN.="<option value='floor_performance_total'>"._QXZ("Floor Performance Totals (ticker)")."</option>";
$MAIN.="<option value='floor_performance_rates'>"._QXZ("Floor Performance Rates (ticker)")."</option>";
$MAIN.="<option value='ingroup_performance_total'>"._QXZ("Ingroup Performance Total")."</option>"; #  (ticker)
$MAIN.="<option value='ingroup_performance_rates'>"._QXZ("Ingroup Performance Rates")."</option>"; #  (ticker)
$MAIN.="<option value='did_performance_total'>"._QXZ("DID Performance Total")."</option>"; #  (ticker)
$MAIN.="<option value='did_performance_rates'>"._QXZ("DID Performance Rates")."</option>"; #  (ticker)
$MAIN.="</SELECT>\n";

$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP width='300'> <FONT class='embossed'>"._QXZ("Campaigns").":</font> $NWB#rt_whiteboard_report-parameters$NWE<BR>";
$MAIN.="<SELECT SIZE=5 NAME=campaigns ID=campaigns multiple class='form_field sm_shadow round_corners' style='width:270px'>\n";
#if  (preg_match('/\-\-ALL\-\-/',$campaign_string))
#	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
#else
#	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$MAIN.="<option value=\"--ALL--\"".(in_array("--ALL--", $campaigns) ? " selected" : "").">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";
$o=0;
# $campaign_SQL="";
while ($campaigns_to_print > $o)
{
	$selected="";
	if (in_array($campaign_list[$o], $campaigns) && !in_array("--ALL--", $campaigns)) {
		$selected="selected";
	}
	if (in_array($campaign_list[$o], $campaigns) || in_array("--ALL--", $campaigns)) {
# 		$campaign_SQL.="'$campaigns[$o]',";
	}
	$MAIN.="<option $selected value=\"$campaign_list[$o]\">$campaign_list[$o]</option>\n";
	$o++;
}
# $campaign_SQL=preg_replace("/,$/", "", $campaign_SQL);
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP ALIGN='LEFT' width='400'>";
$MAIN.="<FONT class='embossed'>"._QXZ("Status flags").":</font> $NWB#rt_whiteboard_report-parameters$NWE<BR>";
$MAIN.="<SELECT SIZE=5 NAME='status_flags' ID='status_flags' multiple class='form_field sm_shadow round_corners' style='width:270px'>\n";
$MAIN.="<option value='--ALL--'>"._QXZ("ALL FLAGS")."</option>\n";
$MAIN.="<option value='selectable'>"._QXZ("AGENT SELECTABLE")."</option>\n";
$MAIN.="<option value='human_answered'>"._QXZ("HUMAN ANSWERED")."</option>\n";
$MAIN.="<option value='sale'>"._QXZ("SALE")."</option>\n";
$MAIN.="<option value='dnc'>"._QXZ("DNC")."</option>\n";
$MAIN.="<option value='customer_contact'>"._QXZ("CUSTOMER CONTACT")."</option>\n";
$MAIN.="<option value='not_interested'>"._QXZ("NOT INTERESTED")."</option>\n";
$MAIN.="<option value='unworkable'>"._QXZ("UNWORKABLE")."</option>\n";
$MAIN.="<option value='scheduled_callback'>"._QXZ("SCHEDULED CALLBACK")."</option>\n";
$MAIN.="<option value='completed'>"._QXZ("COMPLETED CALL")."</option>\n";
$MAIN.="<option value='answering_machine'>"._QXZ("ANSWERING MACHINE")."</option>\n";
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";
$MAIN.="</TR>\n";
$MAIN.="<TR>";

$MAIN.="<TD VALIGN=TOP width='300'><FONT class='embossed'>"._QXZ("User Groups").":  $NWB#rt_whiteboard_report-parameters$NWE</font><BR>";
$MAIN.="<SELECT SIZE=5 NAME='user_groups' ID='user_groups' multiple class='form_field sm_shadow round_corners' style='width:270px'>\n";
if  (preg_match('/\-\-ALL\-\-/',$user_groups_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/$user_group_list[$o]\|/i",$user_groups_string)) {$MAIN.="<option selected value=\"$user_group_list[$o]\">$user_group_list[$o] - $user_group_names[$o]</option>\n";}
	  else {$MAIN.="<option value=\"$user_group_list[$o]\">$user_group_list[$o] - $user_group_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP width='300'><FONT class='embossed'>"._QXZ("Users").": $NWB#rt_whiteboard_report-parameters$NWE</font><BR>";
$MAIN.="<SELECT SIZE=5 NAME='users' ID='users' multiple class='form_field sm_shadow round_corners' style='width:270px'>\n";
if  (preg_match('/\-\-ALL\-\-/',$users_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USERS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL USERS")." --</option>\n";}
$o=0;
while ($users_to_print > $o)
	{
	if  (preg_match("/$user_list[$o]\|/i",$users_string)) {$MAIN.="<option selected value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	  else {$MAIN.="<option value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=MIDDLE ALIGN='LEFT' nowrap width='400'> <FONT class='embossed'>"._QXZ("Start date/time").":</font>";
$MAIN.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date ID=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\" class='form_field sm_shadow round_corners'>";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="function openNewWindow(url)\n";
$MAIN.="	{\n";
$MAIN.="	window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$MAIN.="	}\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

$MAIN.="&nbsp;&nbsp;<INPUT TYPE=TEXT NAME=query_time ID=query_time SIZE=10 MAXLENGTH=8 VALUE=\"$query_time\" class='form_field sm_shadow round_corners'> $NWB#rt_whiteboard_report-start_date$NWE";

/*
$MAIN.="<BR><FONT class='embossed'>"._QXZ("to")."</font><BR><INPUT TYPE=TEXT NAME=end_date ID=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\" class='form_field sm_shadow round_corners'>";
$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'end_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";
$MAIN.="&nbsp;&nbsp;<INPUT TYPE=TEXT NAME=end_time ID=end_time SIZE=10 MAXLENGTH=8 VALUE=\"$end_time\" class='form_field sm_shadow round_corners'>\n";
*/

$MAIN.="<BR><FONT class='embossed'>"._QXZ("OR")."</font><BR>";
$MAIN.="<FONT class='embossed'>"._QXZ("Show results for the past")." <input type='text' class='form_field sm_shadow round_corners' size='2' maxlength='2' name='hourly_display' id='hourly_display' value=''> "._QXZ("hours")."</font> $NWB#rt_whiteboard_report-show_results$NWE";
$MAIN.="</TD>\n";

$MAIN.="</TR>\n";
$MAIN.="<TR>";

$MAIN.="<TD VALIGN=TOP width='300'><FONT class='embossed'>"._QXZ("In-groups").": $NWB#rt_whiteboard_report-parameters$NWE</font><BR>";
$MAIN.="<SELECT SIZE=5 NAME=groups ID=groups multiple class='form_field sm_shadow round_corners' style='width:270px'>\n";
if  (preg_match('/\-\-ALL\-\-/',$groups_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL IN-GROUPS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL IN-GROUPS")." --</option>\n";}
$o=0;
while ($groups_to_print > $o)
	{
	if  (preg_match("/$LISTgroups[$o]\|/i",$groups_string)) {$MAIN.="<option selected value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroup_names[$o]</option>\n";}
	  else {$MAIN.="<option value=\"$LISTgroups[$o]\">$LISTgroups[$o] - $LISTgroup_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP width='300'><FONT class='embossed'>"._QXZ("DIDs").": $NWB#rt_whiteboard_report-parameters$NWE</font><BR>";
$MAIN.="<SELECT SIZE=5 NAME=dids ID=dids multiple class='form_field sm_shadow round_corners' style='width:270px'>\n";
if  (preg_match('/\-\-ALL\-\-/',$dids_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL DIDS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL DIDS")." --</option>\n";}
$o=0;
while ($dids_to_print > $o)
	{
	if  (preg_match("/$did_ids[$o]\|/i",$dids_string)) {$MAIN.="<option selected value=\"$did_ids[$o]\">$did_pattern[$o] - $did_description[$o]</option>\n";}
	  else {$MAIN.="<option value=\"$did_ids[$o]\">$did_pattern[$o] - $did_description[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN='TOP' ALIGN='CENTER' width='400'>";
$MAIN.="<table border='0' cellpadding='2' cellspacing='0'>";
$MAIN.="<tr>";
$MAIN.="<td align='right' class='panel_td'><FONT class='embossed'>"._QXZ("Target per unit").":</font></td>";
$MAIN.="<td align='left' class='panel_td'><input type='text' size='5' maxlength='5' name='target_per_agent' id='target_per_agent' value='$target_per_agent' class='form_field sm_shadow round_corners'> $NWB#rt_whiteboard_report-target_per_unit$NWE</td>";
$MAIN.="</tr><tr>";
$MAIN.="<td align='right' class='panel_td'><FONT class='embossed'>"._QXZ("Target gross sales").":</font></td>";
$MAIN.="<td align='left' class='panel_td'><input type='text' size='5' maxlength='5' name='target_gross' id='target_gross' value='$target_gross' class='form_field sm_shadow round_corners'> $NWB#rt_whiteboard_report-target_gross_sales$NWE</td>";
$MAIN.="</tr></table>";
$MAIN.="<BR><INPUT TYPE=button NAME='run_report' class='green_btn sm_shadow round_corners' style='width:150px' VALUE='"._QXZ("RUN REPORT")."' onClick='StartRefresh()'>&nbsp;&nbsp;&nbsp;<INPUT TYPE=button NAME='goto_reports' ID='goto_reports' class='red_btn sm_shadow round_corners' style='width:150px' VALUE='"._QXZ("REPORTS")."'>";
$MAIN.="</TD>\n";

$MAIN.="</TR></TABLE></span>\n";


########################


$MAIN.="<span id='report_display_panel' style='display:none'>";
$MAIN.="<TABLE CELLPADDING=0 border='0' CELLSPACING=3 BGCOLOR='#".$SSframe_background."' width='90%' height='*' class='question_td'><TR>";

$MAIN.="<TD ALIGN=left width='150' class='embossed'>";
$MAIN.="<input type='button' id='stop_report' name='stop_report' class='red_btn sm_shadow round_corners' style='width:140px' value='<<< "._QXZ("CONTROL PANEL")."' onClick='StopRefresh()'>";
$MAIN.="</TD>";

$MAIN.="<TD ALIGN=center width='*' class='embossed'>";
$MAIN.=_QXZ("Refresh rate").":&nbsp;";
$MAIN.="<select name='refresh_rate' id='refresh_rate' class='form_field sm_shadow round_corners'>";
$MAIN.="<option value='5'>5 "._QXZ("seconds")."</option>";
$MAIN.="<option value='10'>10 "._QXZ("seconds")."</option>";
$MAIN.="<option value='15'>15 "._QXZ("seconds")."</option>";
$MAIN.="<option value='20'>20 "._QXZ("seconds")."</option>";
$MAIN.="<option value='30'>30 "._QXZ("seconds")."</option>";
$MAIN.="<option value='45'>45 "._QXZ("seconds")."</option>";
$MAIN.="<option value='60'>60 "._QXZ("seconds")."</option>";
$MAIN.="</select>";
$MAIN.="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
$MAIN.=_QXZ("Start date/time").":&nbsp;";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date2 ID=query_date2 SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\" class='form_field sm_shadow round_corners'>";
$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="function openNewWindow(url)\n";
$MAIN.="	{\n";
$MAIN.="	window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$MAIN.="	}\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_time2 ID=query_time2 SIZE=10 MAXLENGTH=8 VALUE=\"$query_time\" class='form_field sm_shadow round_corners'>";

$MAIN.="&nbsp;"._QXZ("OR")."&nbsp;"._QXZ("Show results for the past")." <input type='text' class='form_field sm_shadow round_corners' size='2' maxlength='2' name='hourly_display2' id='hourly_display2' value=''> "._QXZ("hours");
$MAIN.="</TD>";

$MAIN.="<TD ALIGN=right width='150' class='embossed'>";
$MAIN.="<input type='button' id='adjust_report' name='adjust_report' class='green_btn sm_shadow round_corners' style='width:140px' value='"._QXZ("ADJUST RATE/TIME")."'>";
$MAIN.="</TD>";

$MAIN.="<TD VALIGN=TOP width='180' align='left' rowspan=3 class='embossed'><BR><BR><BR>";
$MAIN.="<div class='embossed border2px round_corners sm_shadow std_row1' style='width:160px'>"._QXZ("Total Calls").":<BR><span id='total_calls_div'></span>&nbsp;</div><BR>";
$MAIN.="<div class='embossed border2px round_corners sm_shadow std_row2' style='width:160px'>"._QXZ("Total Sales").":<BR><span id='total_sales_div'></span>&nbsp;</div><BR>";
$MAIN.="<div class='embossed border2px round_corners sm_shadow std_row3' style='width:160px'>"._QXZ("Total Conv Rate").":<BR><span id='total_conv_div'></span>&nbsp;</div><BR>";
$MAIN.="<div class='embossed border2px round_corners sm_shadow std_row4' style='width:160px'>"._QXZ("Total Time").":<BR><span id='total_time_div'></span>&nbsp;</div><BR>";
$MAIN.="<div class='embossed border2px round_corners sm_shadow std_row5' style='width:160px'>"._QXZ("Total CPH").":<BR><span id='total_cph_div'></span>&nbsp;</div><BR>";
$MAIN.="<div class='embossed border2px round_corners sm_shadow std_row1' style='width:160px'>"._QXZ("Total SPH").":<BR><span id='total_sph_div'></span>&nbsp;</div>";
$MAIN.="</TD>";
$MAIN.="</TR>";

$MAIN.="<TR>";
$MAIN.="<TD VALIGN=TOP colspan='3' width='*'>";
$MAIN.="<span id='top_10_display' style='display:none'>";
$MAIN.="</span>";
$MAIN.="<div align='center' id='loading_display' style='display:none'>";
$MAIN.="<BR><BR><BR><BR><BR><div align='center' class='embossed border2px round_corners sm_shadow' style='background-color:#FFF;width:250px;height:125px'><BR><BR>"._QXZ("LOADING.  PLEASE WAIT...")."<BR><BR><img src='/vicidial/images/loader.gif' width='220' height='19'></div>";
$MAIN.="</div>";
$MAIN.="<span id='graph_display' style='display:block'>";
$MAIN.="<canvas id='MainReportCanvas'></canvas>";
$MAIN.="<p align='center'><input type='button' class='green_btn sm_shadow round_corners' style='width:250px' value='"._QXZ("SHOW TOP 10 PERFORMERS")."' onClick=\"ToggleVisibility('top_10_display'); ToggleVisibility('graph_display');\"></p>";
$MAIN.="</span>";
$MAIN.="</TD>";
$MAIN.="</TR>";


$MAIN.="<TR>";
$MAIN.="<TD VALIGN=TOP colspan='3'>";
$MAIN.="<span id='parameters_span'></span>";
$MAIN.="</TD>";
$MAIN.="</TR></TABLE>";


$MAIN.="</span>";
$MAIN.="</TD></TR></TABLE>";
$MAIN.="<input type='hidden' size='50' name='hidden_display' id='hidden_display'>";
$MAIN.="</FORM>";


$MAIN.="</BODY>";
$MAIN.="<script language=\"JavaScript\" src=\"vicidial_whiteboard_functions.php\"></script>\n";
$MAIN.="</HTML>";

header("Content-type: text/html; charset=utf-8");

echo "$HEADER";
require("admin_header.php");
echo "$MAIN";
flush();

?>
