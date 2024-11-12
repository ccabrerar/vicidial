<?php
# dygraph_functions.php
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>     LICENSE: AGPLv2
#
# CHANGES
# 230508-0247 - First build
#

require("dbconnect_mysqli.php");
require("functions.php");

$php_script = 'dygraph_functions.php';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["log_date"]))					{$log_date=$_GET["log_date"];}
	elseif (isset($_POST["log_date"]))			{$log_date=$_POST["log_date"];}
if (isset($_GET["web_ip"]))						{$web_ip=$_GET["web_ip"];}
	elseif (isset($_POST["web_ip"]))			{$web_ip=$_POST["web_ip"];}
if (isset($_GET["ACTION"]))						{$ACTION=$_GET["ACTION"];}
	elseif (isset($_POST["ACTION"]))			{$ACTION=$_POST["ACTION"];}


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$VUselected_language = '';
$stmt = "SELECT use_non_latin,enable_languages,language_method,default_language,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
        if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =			$row[0];
	$SSenable_languages =	$row[1];
	$SSlanguage_method =	$row[2];
	$SSdefault_language =	$row[3];
	$SSallow_web_debug =	$row[4];
	}
$VUselected_language = $SSdefault_language;
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$DB = preg_replace('/[^-_0-9a-zA-Z]/', '', $DB);
$log_date = preg_replace('/[^-_0-9a-zA-Z]/', '', $log_date);
$web_ip = preg_replace('/[^-:\._0-9a-zA-Z]/', '', $web_ip);
$ACTION = preg_replace('/[^-_0-9a-zA-Z]/', '', $ACTION);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

$stmt="SELECT modify_campaigns,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGmodify_campaigns =	$row[0];
$LOGuser_group =		$row[1];

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

$admin_viewable_groupsALL=0;
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}
$regexLOGadmin_viewable_groups = " $LOGadmin_viewable_groups ";


# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

if ($ACTION=="latency_by_user" && $user && $log_date && $web_ip)
	{
	if ($log_date!=date("Y-m-d")) {$latency_tbl="vicidial_agent_latency_log_archive";} else {$latency_tbl="vicidial_agent_latency_log";}
	if (!preg_match('/\-\-\-ALL\-\-\-/', $web_ip))
		{
		$ip_clause="and web_ip='$web_ip'";
		}
	else
		{
		$ip_clause="";
		}

	if (!preg_match('/\-\-\-ALL\-\-\-/', $user))
		{
		$user_clause="and user='$user'";
		}
	else
		{
		$user_clause="";
		}
	
	$stmt="select log_date, latency, unix_timestamp(log_date) from ".$latency_tbl." where date(log_date)='$log_date' $LOGadmin_viewable_groupsSQL $user_clause $ip_clause order by log_date asc";
	$rslt=mysql_to_mysqli($stmt, $link);
	$output="Date/Time,Latency\n";
	while ($row=mysqli_fetch_row($rslt))
		{
		if ($prev_ldate && $row[2]>($prev_ldate+1))
			{
			$dummy_ldate=date("Y-m-d H:i:s", floor(($row[2]+$prev_ldate)/2));
			$output.="$dummy_ldate,\n";
			}
		$output.="$row[0],$row[1]\n";
		$prev_ldate=$row[2];
		}
	echo $output; 	
	exit;
	}

if ($ACTION=="all_agent_latency" && $log_date)
	{
	if ($log_date!=date("Y-m-d")) {$latency_tbl="vicidial_agent_latency_log_archive";} else {$latency_tbl="vicidial_agent_latency_log";}

	$group_array=array();
	$user_array=array();

	$full_name_array=array();
	$fn_stmt="select user, full_name from vicidial_users where user>0 $LOGadmin_viewable_groupsSQL";
	$fn_rslt=mysql_to_mysqli($fn_stmt, $link);
	while ($fn_row=mysqli_fetch_row($fn_rslt))
		{
		# $user_split=explode("_", $fn_row[1]);
		$full_name_array["$fn_row[0]"]="$fn_row[0] - $fn_row[1]";
		}

	$stmt="select log_date, latency, user, unix_timestamp(log_date) from ".$latency_tbl." where date(log_date)='$log_date' $LOGadmin_viewable_groupsSQL order by user, log_date asc";
	$rslt=mysql_to_mysqli($stmt, $link);
	$output="";
	while ($row=mysqli_fetch_row($rslt))
		{
		$user_array["$row[2]"]++;
		$group_array["$row[0]"]["$row[2]"]=$row[1];
		}
	
	$output="Date/Time";
	foreach ($user_array as $user => $lags)
		{
		$output.=",".$full_name_array["$user"];
		}
	$output.="\n";

	foreach($group_array as $log_date => $agents)
		{

		if ($prev_ldate && strtotime($log_date)>($prev_ldate+1))
			{
			$dummy_ldate=date("Y-m-d H:i:s", floor((strtotime($log_date)+$prev_ldate)/2));
			$output.="$dummy_ldate";
			foreach ($user_array as $user => $lags)
				{
				$output.=",";
				}
			$output.="\n";
			}

		$output.="$log_date";
		foreach ($user_array as $user => $lags)
			{
			$output.=",".$group_array["$log_date"]["$user"];
			}
		$output.="\n";

		$prev_ldate=strtotime($log_date);
		}

	echo $output;
	}

if ($ACTION=="latency_gaps" && $log_date)
	{
	if ($archive_flag) {$latency_tbl="vicidial_latency_gaps_archive";} else {$latency_tbl="vicidial_latency_gaps";}

	$group_array=array();
	$user_array=array();

	$full_name_array=array();
	$fn_stmt="select user, full_name from vicidial_users where user>0 $LOGadmin_viewable_groupsSQL";
	if($DB){echo $fn_stmt."<BR>\n";}
	$fn_rslt=mysql_to_mysqli($fn_stmt, $link);
	while ($fn_row=mysqli_fetch_row($fn_rslt))
		{
		# $user_split=explode("_", $fn_row[1]);
		$full_name_array["$fn_row[0]"]="$fn_row[0] - $fn_row[1]";
		}

	if ($user && !preg_match('/\-\-\-ALL\-\-\-/', $user))
		{
		$user_SQL="and user='$user'";
		}

	$stmt="select gap_date, gap_length, user, unix_timestamp(gap_date) from ".$latency_tbl." where date(gap_date)='$log_date' $user_SQL $LOGadmin_viewable_groupsSQL order by gap_date, user asc";
	if($DB){echo $stmt."<BR>\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$output="";
	while ($row=mysqli_fetch_row($rslt))
		{
		$user_array["$row[2]"]++;
		$group_array["$row[0]"]["$row[2]"]=$row[1];
		}
	
	$output="Date/Time";
	foreach ($user_array as $user => $lags)
		{
		$output.=",".$full_name_array["$user"];
		}
	$output.="\n";

	foreach($group_array as $gap_date => $agents)
		{

		if ($prev_ldate && strtotime($gap_date)>($prev_ldate+1))
			{
			$dummy_ldate=date("Y-m-d H:i:s", floor((strtotime($gap_date)+$prev_ldate)/2));
			$output.="$dummy_ldate";
			foreach ($user_array as $user => $lags)
				{
				$output.=",";
				}
			$output.="\n";
			}

		$output.="$gap_date";
		foreach ($user_array as $user => $lags)
			{
			$output.=",".$group_array["$gap_date"]["$user"];
			}
		$output.="\n";

		$prev_ldate=strtotime($gap_date);
		}

	echo $output;
	}
