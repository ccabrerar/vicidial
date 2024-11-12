<?php
# whiteboard_reports.php
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# A PHP file that is for generating the stats that are displayed in the whiteboard report.  Returns values.
#
# 171027-2352 - First build
# 190302-1707 - Added code to exclude active calls from being counted with some stats
# 200427-2225 - Added use of slave database, if activated, fixes Issue #1207
# 210823-0948 - Fix for security issue
# 220221-1452 - Added allow_web_debug system setting
# 240801-1130 - Code updates for PHP8 compatibility
#

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
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_time"]))				{$query_time=$_GET["query_time"];}
	elseif (isset($_POST["query_time"]))	{$query_time=$_POST["query_time"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["end_time"]))				{$end_time=$_GET["end_time"];}
	elseif (isset($_POST["end_time"]))		{$end_time=$_POST["end_time"];}
if (isset($_GET["hourly_display"]))				{$hourly_display=$_GET["hourly_display"];}
	elseif (isset($_POST["hourly_display"]))	{$hourly_display=$_POST["hourly_display"];}
if (isset($_GET["commission_rates"]))			{$commission_rates=$_GET["commission_rates"];}
	elseif (isset($_POST["commission_rates"]))	{$commission_rates=$_POST["commission_rates"];}
if (isset($_GET["campaigns"]))				{$campaigns=$_GET["campaigns"];}
	elseif (isset($_POST["campaigns"]))		{$campaigns=$_POST["campaigns"];}
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["user_groups"]))			{$user_groups=$_GET["user_groups"];}
	elseif (isset($_POST["user_groups"]))	{$user_groups=$_POST["user_groups"];}
if (isset($_GET["groups"]))					{$groups=$_GET["groups"];}
	elseif (isset($_POST["groups"]))		{$groups=$_POST["groups"];}
if (isset($_GET["dids"]))					{$dids=$_GET["dids"];}
	elseif (isset($_POST["dids"]))			{$dids=$_POST["dids"];}
if (isset($_GET["status_flags"]))			{$status_flags=$_GET["status_flags"];}
	elseif (isset($_POST["status_flags"]))	{$status_flags=$_POST["status_flags"];}
if (isset($_GET["target_gross"]))			{$target_gross=$_GET["target_gross"];}
	elseif (isset($_POST["target_gross"]))	{$target_gross=$_POST["target_gross"];}
if (isset($_GET["target_per_agent"]))			{$target_per_agent=$_GET["target_per_agent"];}
	elseif (isset($_POST["target_per_agent"]))	{$target_per_agent=$_POST["target_per_agent"];}
if (isset($_GET["target_per_team"]))			{$target_per_team=$_GET["target_per_team"];}
	elseif (isset($_POST["target_per_team"]))	{$target_per_team=$_POST["target_per_team"];}
if (isset($_GET["rpt_type"]))					{$rpt_type=$_GET["rpt_type"];}
	elseif (isset($_POST["rpt_type"]))			{$rpt_type=$_POST["rpt_type"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

if (!$query_date) {$query_date=date("Y-m-d");}
if (!$query_time) {$query_time="08:00:00";}

if (!$end_date) {$end_date=date("Y-m-d");}
if (!$end_time) {$end_time=date("H:i:00");}

if (!is_array($campaigns)) {$campaigns=array();}
if (!is_array($users)) {$users=array();}
if (!is_array($groups)) {$groups=array();}
if (!is_array($user_groups)) {$user_groups=array();}
if (!is_array($status_flags)) {$status_flags=array();}
if (!is_array($dids)) {$dids=array();}

$report_name="Real-Time Whiteboard Report";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,admin_screen_colors,allow_web_debug FROM system_settings;";
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
	$admin_screen_colors =			$row[6];
	$SSallow_web_debug =			$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$query_date=preg_replace("/[^0-9\-]/", "", $query_date);
$end_date=preg_replace("/[^0-9\-]/", "", $end_date);
$query_time=preg_replace("/[^0-9\:]/", "", $query_time);
$end_time=preg_replace("/[^0-9\:]/", "", $end_time);
$rpt_type=preg_replace('/[^-_0-9\p{L}]/u', "", $rpt_type);
$hourly_display=preg_replace("/[^0-9]/", "", $hourly_display);
$target_gross=preg_replace("/[^0-9]/", "", $target_gross);
$target_per_agent=preg_replace("/[^0-9]/", "", $target_per_agent);
$target_per_team=preg_replace("/[^0-9]/", "", $target_per_team);
$commission_rates = preg_replace('/[^-\._0-9\p{L}]/u',"",$commission_rates);
$campaigns=preg_replace('/[^-_0-9\p{L}]/u','',$campaigns);
$users=preg_replace('/[^-_0-9\p{L}]/u','',$users);
$user_groups=preg_replace('/[^-_0-9\p{L}]/u','',$user_groups);
$groups=preg_replace('/[^-_0-9\p{L}]/u','',$groups);
$dids=preg_replace('/[^-_0-9\p{L}]/u','',$dids);
$status_flags=preg_replace('/[^-_0-9\p{L}]/u','',$status_flags);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$PHP_AUTH_PW);
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

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

if ($hourly_display)
	{
	$query_date=date("Y-m-d", date("U")-(3600*$hourly_display));
	$query_time=date("H:i:00", date("U")-(3600*$hourly_display));
	$end_date=date("Y-m-d");
	$end_time=date("H:i:00");
	}

$rpt_string="";

$exclude_statuses=array("INCALL", "DISPO", "QUEUE", "DONEM");
$exc_status_SQL=" and status not in ('".implode("','", $exclude_statuses)."') ";


if (preg_match("/status_performance/", $rpt_type)) {
	if (!is_array($campaigns) || in_array("--ALL--", $campaigns)) {
		$campaign_id_SQL="";
	} else {
		$campaign_id_SQL=" and campaign_id in ('".implode("','", $campaigns)."')";
	}
	if (!is_array($groups) || in_array("--ALL--", $groups)) {
		if ($campaign_id_SQL=="") {
			$group_SQL="";
		} else {
			$ingroup_stmt="SELECT closer_campaigns from vicidial_campaigns where campaign_id in ('".implode("','", $campaigns)."')";
			$ingroup_rslt=mysql_to_mysqli($ingroup_stmt, $link);
			$group_str="";
			while ($ig_row=mysqli_fetch_row($ingroup_rslt)) {
				$group_str.=$ig_row[0];
				$group_str=preg_replace('/ -$/', "", $group_str);
			}
			$group_str=trim($group_str);
			$groups=explode(" ", $group_str);
			$group_SQL=" and campaign_id in ('".implode("','", $groups)."')";
		}
	} else {
		$group_SQL=" and campaign_id in ('".implode("','", $groups)."')";
	}
	if (!is_array($users) || in_array("--ALL--", $users)) {
		$user_SQL="";
	} else {
		$user_SQL=" and user in ('".implode("','", $users)."')";
	}
	if (!is_array($status_flags) || in_array("--ALL--", $status_flags)) {
		$status_SQL="";
	} else {
		$flag_SQL="";
		for ($s=0; $s<count($status_flags); $s++) {
			$flag_SQL.=$status_flags[$s]."='Y' or ";
		}
		$flag_SQL=preg_replace('/ or $/', "", $flag_SQL);

		$status_stmt="SELECT distinct status from vicidial_statuses where ($flag_SQL) UNION SELECT distinct status from vicidial_campaign_statuses where ($flag_SQL) $campaign_id_SQL";
		$status_rslt=mysql_to_mysqli($status_stmt, $link);
		$status_str="";
		while ($status_row=mysqli_fetch_row($status_rslt)) {
			$status_str.="'$status_row[0]',";
		}
		$status_str=preg_replace('/,$/', "", $status_str);
		$status_SQL=" and status in ($status_str) ";
	}

	$sale_stmt="SELECT distinct status from vicidial_statuses where sale='Y' $status_SQL $exc_status_SQL UNION SELECT distinct status from vicidial_campaign_statuses where sale='Y' $campaign_id_SQL $status_SQL $exc_status_SQL";
	$sale_rslt=mysql_to_mysqli($sale_stmt, $link);
	$sale_dispos=array();
	if ($DB) {$rpt_string.=$sale_stmt."<BR>\n";}
	while($sale_row=mysqli_fetch_row($sale_rslt)) {
		array_push($sale_dispos, $sale_row[0]);
	}

	$shift_start="$query_date $query_time";
	$shift_end="$end_date $end_time";

	$outbound_max_time_stmt="SELECT if(min(call_date) is null, 0, min(call_date)), if(max(call_date) is null, 0, max(call_date)) From vicidial_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $campaign_id_SQL $user_SQL $status_SQL $exc_status_SQL";
	$rslt=mysql_to_mysqli($outbound_max_time_stmt, $link);
	$row=mysqli_fetch_row($rslt);
	if ($row[0]>0) {$shift_start=$row[0];}
	if ($row[1]>0) {$shift_end=$row[1];}

	$inbound_max_time_stmt="SELECT if(min(call_date) is null, 0, min(call_date)), if(max(call_date) is null, 0, max(call_date)) From vicidial_closer_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $group_SQL $user_SQL $status_SQL $exc_status_SQL";
	$rslt=mysql_to_mysqli($inbound_max_time_stmt, $link);
	$row=mysqli_fetch_row($rslt);
	if ($row[0]>0 && preg_replace("/[^0-9]/", "", $row[0])<preg_replace("/[^0-9]/", "", $shift_start)) {$shift_start=$row[0];}
	if (preg_replace("/[^0-9]/", "", $row[1])>preg_replace("/[^0-9]/", "", $shift_end)) {$shift_end=$row[1];}

	$time_stmt="SELECT TIMESTAMPDIFF(SECOND,'$shift_start','$shift_end')";
	$time_rslt=mysql_to_mysqli($time_stmt, $link);
	$time_row=mysqli_fetch_row($time_rslt);
	$shift_duration=$time_row[0];

	$outbound_stmt="SELECT status, count(*) From vicidial_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' and end_epoch is not null $campaign_id_SQL $user_SQL $status_SQL $exc_status_SQL group by status order by status";
	$status_counts=array();
	$rslt=mysql_to_mysqli($outbound_stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[1]!="") {
			$status_counts["$row[0]"][0]+=$row[1];
			if (in_array($row[0], $sale_dispos)) {
				$status_counts["$row[0]"][1]+=$row[1];
			}
		}
		$status_counts["$row[0]"][2]+=$row[2];
	}

	$inbound_stmt="SELECT status, count(*) from vicidial_closer_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' and end_epoch is not null $group_SQL $user_SQL $status_SQL $exc_status_SQL group by status order by status";
	$rslt=mysql_to_mysqli($inbound_stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[1]!="") {
			$status_counts["$row[0]"][0]+=$row[1];
			if (in_array($row[0], $sale_dispos)) {
				$status_counts["$row[0]"][1]+=$row[1];
			}
		}
		$status_counts["$row[0]"][2]+=$row[2];
	}

#	while (list($key, $val)=each($status_counts)) {
	foreach($status_counts as $key => $val) {
		$kstatus_counts[]=array('status' => $key, 'counts' => $val[0], 'sales' => $val[1], 'dead' => $val[2]);
	}

	foreach ($kstatus_counts as $key2 => $row2) {
		$status_ary[$key2]  = $row2['status'];
		$counts_ary[$key2]  = $row2['counts'];
		$sales_ary[$key2]  = $row2['sales'];
		$dead_ary[$key2]  = $row2['dead'];
	}

	if (count($status_counts)==0) {
		$rpt_string=_QXZ("REPORT RETURNED NO RESULTS");
	} else {
#		while(list($key, $val)=each($status_counts)) {

		array_multisort($status_ary, SORT_ASC, $counts_ary, SORT_ASC, $sales_ary, SORT_ASC, $dead_ary, SORT_ASC, $kstatus_counts);
		
		foreach ($kstatus_counts as $row) {
			$key=$row['status'];
			$val0=$row['counts'];
			$val1=$row['sales'];

			$val0+=0;
			$val1+=0;
			$total_calls+=$val0;
			$total_sales+=$val1;


			$conv_rate=sprintf("%.2f", 100*MathZDC($val[1], $val[0])); # Conversion rate
			$cph=sprintf("%.2f", (MathZDC($val[0], ($shift_duration/3600)))); # SPH - this is based on total time since start_time.  Switch to $val[2] if you want it based on the individual agent's total time in the dialer.
			$sph=sprintf("%.2f", (MathZDC($val[1], ($shift_duration/3600)))); # SPH - this is based on total time since start_time.  Switch to $val[2] if you want it based on the individual agent's total time in the dialer.
			$rpt_string.="$key|$full_name|$val0|$val1|$shift_duration|$conv_rate|$cph|$sph|$total_calls|$total_sales|$inbound_stmt - $outbound_stmt\n";
			# Agent/user group, total calls, total sales, total time, conversion rate, sales per hour
		}
	}
}

if (preg_match("/(agent|team)_performance/", $rpt_type)) {

	if (!is_array($campaigns) || in_array("--ALL--", $campaigns)) {
		$campaign_id_SQL="";
	} else {
		$campaign_id_SQL=" and campaign_id in ('".implode("','", $campaigns)."')";
	}
	if (!is_array($users) || in_array("--ALL--", $users)) {
		$user_SQL="";
	} else {
		$user_SQL=" and user in ('".implode("','", $users)."')";
	}
	if (!is_array($groups) || in_array("--ALL--", $groups)) {
		$group_SQL="";
	} else {
		$group_SQL=" and campaign_id in ('".implode("','", $groups)."')";
	}
	if (!is_array($user_groups) || in_array("--ALL--", $user_groups)) {
		$user_group_SQL="";
	} else {
		$user_group_SQL=" and user_group in ('".implode("','", $user_groups)."')";
	}
	if (!is_array($status_flags) || in_array("--ALL--", $status_flags)) {
		$status_SQL="";
	} else {
		$flag_SQL="";
		for ($s=0; $s<count($status_flags); $s++) {
			$flag_SQL.=$status_flags[$s]."='Y' or ";
		}
		$flag_SQL=preg_replace('/ or $/', "", $flag_SQL);

		$status_stmt="SELECT distinct status from vicidial_statuses where ($flag_SQL) UNION SELECT distinct status from vicidial_campaign_statuses where ($flag_SQL) $campaign_id_SQL";
		$status_rslt=mysql_to_mysqli($status_stmt, $link);
		$status_str="";
		while ($status_row=mysqli_fetch_row($status_rslt)) {
			$status_str.="'$status_row[0]',";
		}
		$status_str=preg_replace('/,$/', "", $status_str);
		$status_SQL=" and status in ($status_str) ";
	}


	$time_stmt="SELECT TIMESTAMPDIFF(SECOND,'$query_date $query_time',IF(UNIX_TIMESTAMP(now())<UNIX_TIMESTAMP('$end_date $end_time'),now(),'$end_date $end_time'))";
	$time_rslt=mysql_to_mysqli($time_stmt, $link);
	$time_row=mysqli_fetch_row($time_rslt);
	$shift_duration=$time_row[0];

	$sale_stmt="SELECT distinct status from vicidial_statuses where sale='Y' $status_SQL $exc_status_SQL UNION SELECT distinct status from vicidial_campaign_statuses where sale='Y' $campaign_id_SQL $status_SQL $exc_status_SQL";
	$sale_rslt=mysql_to_mysqli($sale_stmt, $link);
	$sale_dispos=array();
	if ($DB) {$rpt_string.=$sale_stmt."<BR>\n";}
	while($sale_row=mysqli_fetch_row($sale_rslt)) {
		array_push($sale_dispos, $sale_row[0]);
	}

	if (preg_match("/agent_performance/", $rpt_type)) {
		$stmt="SELECT user, status, sum(pause_sec+wait_sec+talk_sec+dispo_sec), count(*) from vicidial_agent_log where event_time>='$query_date $query_time' and event_time<='$end_date $end_time' $campaign_id_SQL $user_SQL $user_group_SQL $status_SQL $exc_status_SQL group by user, status order by user, status";
	} else if (preg_match("/team_performance/", $rpt_type)) {
		$stmt="SELECT user_group, status, sum(pause_sec+wait_sec+talk_sec+dispo_sec), count(*) from vicidial_agent_log where event_time>='$query_date $query_time' and event_time<='$end_date $end_time' $campaign_id_SQL $user_SQL $user_group_SQL $status_SQL $exc_status_SQL group by user_group, status order by user_group, status";
	}

	if ($DB) {$rpt_string.=$stmt."<BR>\n";}

	$agent_counts=array();
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[1]!="") {
			$agent_counts["$row[0]"][0]+=$row[3];
			if (in_array($row[1], $sale_dispos)) {
				$agent_counts["$row[0]"][1]+=$row[3];
			}
		}
		$agent_counts["$row[0]"][2]+=$row[2];
	}

	if (count($agent_counts)==0) {
		$rpt_string=_QXZ("REPORT RETURNED NO RESULTS");
	} else {
#		while(list($key, $val)=each($agent_counts)) {
		foreach($agent_counts as $key => $val) {
			$full_name="";
			if (preg_match('/team_performance/', $rpt_type)) {
				$user_stmt="select group_name from vicidial_user_groups where user_group='$key'";
			} else {
				$user_stmt="SELECT full_name from vicidial_users where user='$key'";
			}
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			while ($user_row=mysqli_fetch_row($user_rslt)) {
				$full_name=$user_row[0];
			}
			if (!$full_name) {$full_name="N/A";}
			$val[0]+=0;
			$val[1]+=0;
			$total_calls+=$val[0];
			$total_sales+=$val[1];


			$conv_rate=sprintf("%.2f", 100*MathZDC($val[1], $val[0])); # Conversion rate
			$cph=sprintf("%.2f", (MathZDC($val[0], ($shift_duration/3600)))); # SPH - this is based on total time since start_time.  Switch to $val[2] if you want it based on the individual agent's total time in the dialer.
			$sph=sprintf("%.2f", (MathZDC($val[1], ($shift_duration/3600)))); # SPH - this is based on total time since start_time.  Switch to $val[2] if you want it based on the individual agent's total time in the dialer.
			$rpt_string.="$key|$full_name|$val[0]|$val[1]|$shift_duration|$conv_rate|$cph|$sph|$total_calls|$total_sales\n";
			# Agent/user group, total calls, total sales, total time, conversion rate, sales per hour
		}
	}
}

if (preg_match("/floor_performance/", $rpt_type)) {
	if ($hourly_display) {
		$query_date=date("Y-m-d", date("U")-(3600*$hourly_display));
		$query_time=date("H:i:00", date("U")-(3600*$hourly_display));
		$start_epoch=date("U")-(3600*$hourly_display);
	} else {
		$start_epoch=strtotime("$query_date $query_time");
	}

	$end_date=date("Y-m-d");
	$end_time=date("H:i:00");
	$end_epoch=date("U");

	# Create ungodly array to store counts
	$call_counts=array();
	while($start_epoch<$end_epoch) {
		$key=date("Y-m-d H:i", $start_epoch);
		$call_counts["$key"][0]=0; # Calls
		$call_counts["$key"][1]=0; # Sales
		$call_counts["$key"][2]=0; # Cumulative calls
		$call_counts["$key"][3]=0; # Cumulative sales
		$start_epoch+=60;
	}

	if (!is_array($campaigns) || in_array("--ALL--", $campaigns)) {
		$campaign_id_SQL="";
	} else {
		$campaign_id_SQL=" and vicidial_agent_log.campaign_id in ('".implode("','", $campaigns)."')";
	}
	if (!is_array($status_flags) || in_array("--ALL--", $status_flags)) {
		$status_SQL="";
	} else {
		$flag_SQL="";
		for ($s=0; $s<count($status_flags); $s++) {
			$flag_SQL.=$status_flags[$s]."='Y' or ";
		}
		$flag_SQL=preg_replace('/ or $/', "", $flag_SQL);

		$status_stmt="SELECT distinct status from vicidial_statuses where ($flag_SQL) UNION SELECT distinct status from vicidial_campaign_statuses where ($flag_SQL) $campaign_id_SQL";
		$status_rslt=mysql_to_mysqli($status_stmt, $link);
		$status_str="";
		while ($status_row=mysqli_fetch_row($status_rslt)) {
			$status_str.="'$status_row[0]',";
		}
		$status_str=preg_replace('/,$/', "", $status_str);
		$status_SQL=" and status in ($status_str) ";
	}

	$sale_stmt="SELECT distinct status from vicidial_statuses where sale='Y' $status_SQL $exc_status_SQL UNION SELECT distinct status from vicidial_campaign_statuses where sale='Y' and campaign_id in ('".implode("','", $campaigns)."') $status_SQL $exc_status_SQL";
	# $rpt_string.="$sale_stmt\n";
	$sale_rslt=mysql_to_mysqli($sale_stmt, $link);
	$sale_dispos=array();
	while($sale_row=mysqli_fetch_row($sale_rslt)) {
		array_push($sale_dispos, $sale_row[0]);
	}

	$stmt="SELECT substr(event_time+INTERVAL (pause_sec+wait_sec+talk_sec+dispo_sec) SECOND, 1, 16) as call_end_time_min, status, count(*) from vicidial_agent_log where event_time>='$query_date $query_time' and event_time<='$end_date $end_time' $campaign_id_SQL $user_SQL $user_group_SQL $status_SQL $exc_status_SQL group by call_end_time_min, status order by call_end_time_min, status";
	# $rpt_string.="$stmt\n";
	if ($DB) {$rpt_string.=$stmt."<BR>\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$total_calls=0; $total_sales=0;
	while ($row=mysqli_fetch_row($rslt)) {
		$key=$row[0];
		$status=$row[1];
		$calls=$row[2];
		$total_calls+=$calls;
		$call_counts["$key"][0]+=$calls;
		$call_counts["$key"][2]=$total_calls;
		if (in_array($status, $sale_dispos)) {
			$total_sales+=$calls;
			$call_counts["$key"][1]+=$calls;
		}
		$call_counts["$key"][3]=$total_sales;
	}

	if (count($call_counts)==0) {
		$rpt_string=_QXZ("REPORT RETURNED NO RESULTS");
	} else {
		$mins=0;
		ksort($call_counts);
		$prev_calls=0;
		$prev_sales=0;
#		while(list($key, $val)=each($call_counts)) {
		foreach($call_counts as $key => $val) {
		#	if($val[2]>0) { // With time ranges, trim off the starting time in case person running report sets it before they start dialing.
				$mins++;
				$shift_duration=$mins*60;
				$hours=$mins/60;
				$val[0]+=0;
				$val[1]+=0;
				if ($val[2]==0) {$val[2]=$prev_calls;}
				if ($val[3]==0) {$val[3]=$prev_sales;}
				$conv=sprintf("%.2f", (100*MathZDC($val[3],$val[2])));
				$cph=sprintf("%.2f", ($val[2]/$hours));
				$sph=sprintf("%.2f", ($val[3]/$hours));
				$rpt_string.="$key||$val[0]|$val[1]|$shift_duration|$conv|$cph|$sph|$val[2]|$val[3]\n";
				$prev_calls=$val[2];
				$prev_sales=$val[3];
		#	}
		}
	}
}

if (preg_match("/(did|ingroup)_performance/", $rpt_type)) {
	if (!is_array($campaigns) || in_array("--ALL--", $campaigns)) {
		$campaign_id_SQL="";
	} else {
		$campaign_id_SQL=" and campaign_id in ('".implode("','", $campaigns)."')";
	}
	if (!is_array($groups) || in_array("--ALL--", $groups)) {
		$group_SQL="";
		$ingroup_SQL="";
	} else {
		$group_SQL=" and campaign_id in ('".implode("','", $groups)."')";
		$ingroup_SQL=" and group_id in ('".implode("','", $groups)."')";
	}
	if (!is_array($user_groups) || in_array("--ALL--", $user_groups)) {
		$user_group_SQL="";
	} else {
		$user_group_SQL=" and user_group in ('".implode("','", $user_groups)."')";
	}
	if (!is_array($dids) || in_array("--ALL--", $dids)) {
		$did_SQL="";
	} else {
		$did_SQL=" and vid.did_id in ('".implode("','", $dids)."')";
	}
	if (!is_array($status_flags) || in_array("--ALL--", $status_flags)) {
		$status_SQL="";
	} else {
		$flag_SQL="";
		for ($s=0; $s<count($status_flags); $s++) {
			$flag_SQL.=$status_flags[$s]."='Y' or ";
		}
		$flag_SQL=preg_replace('/ or $/', "", $flag_SQL);

		$status_stmt="SELECT distinct status from vicidial_statuses where ($flag_SQL) UNION SELECT distinct status from vicidial_campaign_statuses where ($flag_SQL) $campaign_id_SQL";
		$status_rslt=mysql_to_mysqli($status_stmt, $link);
		$status_str="";
		while ($status_row=mysqli_fetch_row($status_rslt)) {
			$status_str.="'$status_row[0]',";
		}
		$status_str=preg_replace('/,$/', "", $status_str);
		$status_SQL=" and status in ($status_str) ";
	}

	# print_r($groups);

	# Get all ingroups, and later all DIDs if necessary, for the selected campaigns
	if (in_array("--ALL--", $campaigns) || $campaign_id_SQL!="") {
		$ingrp_stmt="SELECT closer_campaigns from vicidial_campaigns where active='Y' $campaign_id_SQL";
		if ($DB) {$rpt_string.=$ingrp_stmt."<BR>\n";}
		$ingrp_rslt=mysql_to_mysqli($ingrp_stmt, $link);
		# append to existing did array
		while ($ingrp_row=mysqli_fetch_row($ingrp_rslt)) {
			$ingrp_row[0]=preg_replace("/ -$/", "", $ingrp_row[0]);
			$ingrp_array=explode(" ", $ingrp_row[0]);
			$groups=array_merge($groups, $ingrp_array);
		}
		$groups=array_unique($groups);
		$group_SQL=" and campaign_id in ('".implode("','", $groups)."')";
		$ingroup_SQL=" and group_id in ('".implode("','", $groups)."')";
	}

	# DID level only
	if (preg_match("/did_performance/", $rpt_type)) {
		if (in_array("--ALL--", $groups) || $user_group_SQL!="") {
			$did_stmt="SELECT did_id from vicidial_inbound_dids where did_route='IN_GROUP' $ingroup_SQL";
			if ($DB) {$rpt_string.=$did_stmt."<BR>\n";}
			$did_rslt=mysql_to_mysqli($did_stmt, $link);
			# append to existing did array
			while ($did_row=mysqli_fetch_row($did_rslt)) {
				array_push($dids, $did_row[0]);
			}
			$dids=array_unique($dids);
			$did_SQL=" and vid.did_id in ('".implode("','", $dids)."')";
		}
	}

	$sale_stmt="SELECT distinct status from vicidial_statuses where sale='Y' $status_SQL $exc_status_SQL UNION SELECT distinct status from vicidial_campaign_statuses where sale='Y' $campaign_id_SQL $status_SQL $exc_status_SQL"; # Leave it for all campaigns, given the nature.
	# $rpt_string.="$sale_stmt\n";
	$sale_rslt=mysql_to_mysqli($sale_stmt, $link);
	$sale_dispos=array();
	while($sale_row=mysqli_fetch_row($sale_rslt)) {
		array_push($sale_dispos, $sale_row[0]);
	}

	if (preg_match("/did_performance/", $rpt_type)) {
		# $stmt="SELECT user, status, sum(pause_sec+wait_sec+talk_sec+dispo_sec), count(*) from vicidial_agent_log where event_time>='$query_date $query_time' and event_time<='$end_date $end_time' $campaign_id_SQL $user_SQL $user_group_SQL group by user, status order by user, status";
		$stmt="SELECT vid.did_pattern, vcl.status, sum(vcl.length_in_sec-vcl.queue_seconds) as call_length, count(*) From vicidial_closer_log vcl, vicidial_did_log vdl, vicidial_inbound_dids vid where vcl.call_date>='$query_date $query_time' and vcl.call_date<='$end_date $end_time' and vcl.uniqueid=vdl.uniqueid and vdl.did_id=vid.did_id $did_SQL $status_SQL $exc_status_SQL group by did_pattern, did_description, status order by did_pattern, status";
	} else if (preg_match("/ingroup_performance/", $rpt_type)) {
		# $stmt="SELECT user_group, status, sum(pause_sec+wait_sec+talk_sec+dispo_sec), count(*) from vicidial_agent_log where event_time>='$query_date $query_time' and event_time<='$end_date $end_time' $campaign_id_SQL $user_SQL $user_group_SQL group by user_group, status order by user_group, status";
		$stmt="SELECT campaign_id, status, sum(length_in_sec-queue_seconds), count(*) from vicidial_closer_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $group_SQL $status_SQL $exc_status_SQL group by campaign_id, status order by campaign_id, status";
	}

	if ($DB) {$rpt_string.=$stmt."<BR>\n";}

	$inbound_counts=array();
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[1]!="") {
			$inbound_counts["$row[0]"][0]+=$row[3];
			if (in_array($row[1], $sale_dispos)) {
				$inbound_counts["$row[0]"][1]+=$row[3];
			}
		}
		$inbound_counts["$row[0]"][2]+=$row[2];
	}

	$total_calls=0; $total_sales=0;

	if (count($inbound_counts)==0) {
		$rpt_string=_QXZ("REPORT RETURNED NO RESULTS");
	} else {
#		while(list($key, $val)=each($inbound_counts)) {
		foreach($inbound_counts as $key => $val) {

			if (preg_match("/did_performance/", $rpt_type)) {
				$user_stmt="SELECT did_description from vicidial_inbound_dids where did_pattern='$key'";
			} else {
				$user_stmt="SELECT group_name from vicidial_inbound_groups where group_id='$key'";
			}
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			while ($user_row=mysqli_fetch_row($user_rslt)) {
				$full_name=$user_row[0];
			}
			if (!$full_name) {$full_name="N/A";}
			$val[0]+=0;
			$val[1]+=0;
			$total_calls+=$val[0];
			$total_sales+=$val[1];

			$shift_duration=$val[2];
			$conv_rate=sprintf("%.2f", 100*MathZDC($val[1], $val[0])); # Conversion rate
			$cph=sprintf("%.2f", (MathZDC($val[0], ($shift_duration/3600)))); # SPH - this is based on total time since start_time.  Switch to $val[2] if you want it based on the individual agent's total time in the dialer.
			$sph=sprintf("%.2f", (MathZDC($val[1], ($shift_duration/3600)))); # SPH - this is based on total time since start_time.  Switch to $val[2] if you want it based on the individual agent's total time in the dialer.
			$rpt_string.="$key|$full_name|$val[0]|$val[1]|$shift_duration|$conv_rate|$cph|$sph|$total_calls|$total_sales\n";
			# Agent/user group, total calls, total sales, total time, conversion rate, sales per hour
		}
	}
}

if ($rpt_type=="floor_performance_hourly") {
	$rpt_string="";
	if (!is_array($campaigns) || in_array("--ALL--", $campaigns)) {
		$campaign_id_SQL="";
	} else {
		$campaign_id_SQL=" and vicidial_agent_log.campaign_id in ('".implode("','", $campaigns)."')";
	}
	if (!is_array($status_flags) || in_array("--ALL--", $status_flags)) {
		$status_SQL="";
	} else {
		$flag_SQL="";
		for ($s=0; $s<count($status_flags); $s++) {
			$flag_SQL.=$status_flags[$s]."='Y' or ";
		}
		$flag_SQL=preg_replace('/ or $/', "", $flag_SQL);

		$status_stmt="SELECT distinct status from vicidial_statuses where ($flag_SQL) UNION SELECT distinct status from vicidial_campaign_statuses where ($flag_SQL) $campaign_id_SQL";
		$status_rslt=mysql_to_mysqli($status_stmt, $link);
		$status_str="";
		while ($status_row=mysqli_fetch_row($status_rslt)) {
			$status_str.="'$status_row[0]',";
		}
		$status_str=preg_replace('/,$/', "", $status_str);
		$status_SQL=" and status in ($status_str) ";
	}

	$sale_stmt="SELECT distinct status from vicidial_statuses where sale='Y' $status_SQL $exc_status_SQL UNION SELECT distinct status from vicidial_campaign_statuses where sale='Y' and campaign_id in ('".implode("','", $campaigns)."') $status_SQL $exc_status_SQL";
	# $rpt_string.="$sale_stmt\n";
	$sale_rslt=mysql_to_mysqli($sale_stmt, $link);
	$sale_dispos=array();
	while($sale_row=mysqli_fetch_row($sale_rslt)) {
		array_push($sale_dispos, $sale_row[0]);
	}

	$stmt="SELECT substr(event_time+INTERVAL (pause_sec+wait_sec+talk_sec+dispo_sec) SECOND, 1, 13) as call_end_time_hour, count(*) from vicidial_agent_log where event_time>='$query_date $query_time' and event_time<='$end_date $end_time' and status in ('".implode("','", $sale_dispos)."')  $campaign_id_SQL $status_SQL $exc_status_SQL group by call_end_time_hour order by call_end_time_hour";
	$rpt_string.="$stmt\n";
	if ($DB) {$rpt_string.=$stmt."<BR>\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$cumulative_sale_counts=array();
	$total_sales=0;
	while ($row=mysqli_fetch_row($rslt)) {
		$total_sales+=$row[1];
		$cumulative_sale_counts["$row[0]"]=$total_sales;
	}

	$start_unix=strtotime("$query_date $query_time");
	$end_unix=strtotime("$end_date $end_time");
	#if ($end_unix>=date("U")) {$end_unix=date("U");}
	$cumulative_hours_array=array();
	while($start_unix<=$end_unix) {
		$array_date=date("Y-m-d H", $start_unix);
		$cumulative_hours_array["$array_date"]=0;
		if ($cumulative_sale_counts["$array_date"]>0) {$total_sales=$cumulative_sale_counts["$array_date"];}

		# Don't continue displaying sales beyond the current time
		if ($start_unix<=date("U")) {$cumulative_hours_array["$array_date"]=$total_sales;}
		$start_unix+=3600;
	}

	if (count($cumulative_hours_array)==0) {
		$rpt_string=_QXZ("REPORT RETURNED NO RESULTS");
	} else {
#		while(list($key, $val)=each($cumulative_hours_array)) {
		foreach($cumulative_hours_array as $key => $val) {
			$rpt_string.="$key|$val\n";
		}
	}
}

	echo trim($rpt_string);

?>
