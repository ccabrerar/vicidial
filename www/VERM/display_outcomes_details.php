<?php
# display_outcomes_details.php - Vicidial Enhanced Reporting outcomes details page
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1610 - First build
# 240801-1130 - Code updates for PHP8 compatibility
#
$subreport_name="VERM Reports";
$report_display_type="display_outcomes_details.php";
$startMS=microtime();

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["download_rpt"]))			{$download_rpt=$_GET["download_rpt"];}
	elseif (isset($_POST["download_rpt"]))	{$download_rpt=$_POST["download_rpt"];}
if (isset($_GET["user"]))			{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))	{$user=$_POST["user"];}
if (isset($_GET["campaign_id"]))			{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))	{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["users"]))			{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))	{$users=$_POST["users"];}
if (isset($_GET["teams"]))			{$teams=$_GET["teams"];}
	elseif (isset($_POST["teams"]))	{$teams=$_POST["teams"];}
if (isset($_GET["user_group"]))			{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["location"]))			{$location=$_GET["location"];}
	elseif (isset($_POST["location"]))	{$location=$_POST["location"];}
if (isset($_GET["status"]))			{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))	{$status=$_POST["status"];}
if (isset($_GET["status_name"]))			{$status_name=$_GET["status_name"];}
	elseif (isset($_POST["status_name"]))	{$status_name=$_POST["status_name"];}
if (isset($_GET["vicidial_queue_groups"]))			{$vicidial_queue_groups=$_GET["vicidial_queue_groups"];}
	elseif (isset($_POST["vicidial_queue_groups"]))	{$vicidial_queue_groups=$_POST["vicidial_queue_groups"];}
if (isset($_GET["start_datetime"]))			{$start_datetime=$_GET["start_datetime"];}
	elseif (isset($_POST["start_datetime"]))	{$start_datetime=$_POST["start_datetime"];}
if (isset($_GET["end_datetime"]))			{$end_datetime=$_GET["end_datetime"];}
	elseif (isset($_POST["end_datetime"]))	{$end_datetime=$_POST["end_datetime"];}
if (isset($_GET["sort_answered_details"]))			{$sort_answered_details=$_GET["sort_answered_details"];}
	elseif (isset($_POST["sort_answered_details"]))	{$sort_answered_details=$_POST["sort_answered_details"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,agent_whisper_enabled,report_default_format,enable_pause_code_limits,allow_web_debug,admin_screen_colors,admin_web_directory FROM system_settings;";
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
	$agent_whisper_enabled =		$row[6];
	$SSreport_default_format =		$row[7];
	$SSenable_pause_code_limits =	$row[8];
	$SSallow_web_debug =			$row[9];
	$SSadmin_screen_colors =		$row[10];
	$SSadmin_web_directory =		$row[11];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$start_datetime=preg_replace('/[^\s\-0-9\:]/', '', $start_datetime);
$end_datetime=preg_replace('/[^\s\-0-9\:]/', '', $end_datetime);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);

	$download_rpt = preg_replace('/[^\._0-9a-zA-Z]/','',$download_rpt);
	$user = preg_replace('/[^\-_0-9a-zA-Z]/','',$user);
	$users = preg_replace('/[^\-_0-9a-zA-Z]/','',$users);
	$teams = preg_replace('/[^\-_0-9a-zA-Z]/','',$teams);
	$user_group = preg_replace('/[^\-_0-9a-zA-Z]/','',$user_group);
	$campaign_id = preg_replace('/[^\-_0-9a-zA-Z]/','',$campaign_id);
	$location = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$location); 
	$status = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$status);
	$status_name = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$status_name);
	$vicidial_queue_groups = preg_replace('/[^-_0-9a-zA-Z]/','',$vicidial_queue_groups);
	$sort_answered_details = preg_replace('/[^\s-_0-9a-zA-Z]/','',$sort_answered_details);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);

	$download_rpt = preg_replace('/[^\._0-9\p{L}]/u','',$download_rpt);
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$users = preg_replace('/[^-_0-9\p{L}]/u','',$users);
	$teams = preg_replace('/[^-_0-9\p{L}]/u','',$teams);
	$user_group = preg_replace('/[^-_0-9\p{L}/u','',$user_group);
	$campaign_id = preg_replace('/[^-_0-9\p{L}/u','',$campaign_id);
	$location = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$location); 
	$status = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$status);
	$status_name = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$status_name);
	$vicidial_queue_groups = preg_replace('/[^-_0-9\p{L}]/u','',$vicidial_queue_groups);
	$sort_answered_details = preg_replace('/[^\s-_0-9\p{L}]/u','',$sort_answered_details);
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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',0,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

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

require("VERM_global_vars.inc"); # Must be after user_auth for sanitization purposes

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$start_datetime, $end_datetime, $user, $campaign_id, $users, $teams, $location, $user_group, $status_name |', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

if (!$start_datetime || !$end_datetime) {echo "Missing information - needs start date/time, end date/time"; $die=1;}

function RemoveEmptyArrayStrings($array) 
	{
	if (is_array($array))
		{
		for ($i=0; $i<count($array); $i++)
				{
				if ($array[$i]=="") {unset($array[$i]);}
				}
		}
	return $array;
	}


#### CLAUSE GENERATION ####

$where_call_date_sql="where call_date>='$start_datetime' and call_date<='$end_datetime'";
# 6/7/22 - changed because clause takes 100X longer
# $where_event_time_sql="where event_time+INTERVAL (pause_sec+wait_sec) SECOND>='$start_datetime' and event_time+INTERVAL (pause_sec+wait_sec) SECOND<='$end_datetime'";
$where_event_time_sql="where event_time>='$start_datetime' and event_time<='$end_datetime'";

#### QUEUE GROUPS ####
$vicidial_queue_groups=preg_replace('/[^-_0-9\p{L}]/u','',$vicidial_queue_groups);
$vicidial_queue_groups=RemoveEmptyArrayStrings($vicidial_queue_groups);

if ($vicidial_queue_groups)
	{
	$vqg_stmt="select included_campaigns, included_inbound_groups from vicidial_queue_groups where queue_group='$vicidial_queue_groups'";
	$vqg_rslt=mysql_to_mysqli($vqg_stmt, $link);
	if(mysqli_num_rows($vqg_rslt)>0)
		{
		$vqg_row=mysqli_fetch_array($vqg_rslt);
		$included_campaigns=trim(preg_replace('/\s\-$/', '', $vqg_row["included_campaigns"]));
		$included_campaigns_clause="and campaign_id in ('".preg_replace('/\s/', "', '", $included_campaigns)."')";
		$included_inbound_groups=trim(preg_replace('/\s\-$/', '', $vqg_row["included_inbound_groups"]));
		$included_inbound_groups_clause="and group_id in ('".preg_replace('/\s/', "', '", $included_inbound_groups)."')";
		$where_included_inbound_groups_clause="where group_id in ('".preg_replace('/\s/', "', '", $included_inbound_groups)."')";
		}
	}

# Get final list of 'atomic queues'
$atomic_queue_str="";

$atomic_queue_campaigns_str="";
$campaign_id_stmt="select campaign_id, campaign_name from vicidial_campaigns where campaign_id is not null $included_campaigns_clause order by campaign_id"; # $LOGallowed_campaignsSQL, removed for now per Matt's assurances
$campaign_id_rslt=mysql_to_mysqli($campaign_id_stmt, $link);
while($campaign_id_row=mysqli_fetch_array($campaign_id_rslt))
	{
	$atomic_queue_str.=$campaign_id_row["campaign_name"];
	$atomic_queue_str.=" <i>[".$campaign_id_row["campaign_id"]."]</i>,";
	$atomic_queue_campaigns_str.="$campaign_id_row[campaign_id]', '";
	}
$and_atomic_queue_campaigns_clause="and campaign_id in ('".$atomic_queue_campaigns_str."')";

# Check if queue settings override user group settings
$closer_campaigns_stmt="select closer_campaigns from vicidial_campaigns where closer_campaigns is not null $LOGallowed_campaignsSQL"; #  $included_campaigns_clause
$closer_campaigns_rslt=mysql_to_mysqli($closer_campaigns_stmt, $link); 
$allowed_ingroups_array=array();
while ($closer_campaigns_row=mysqli_fetch_array($closer_campaigns_rslt))
	{
	$closer_campaigns_array=explode(" ", trim(preg_replace('/\s\-$/', '', $closer_campaigns_row["closer_campaigns"])));
	for ($i=0; $i<count($closer_campaigns_array); $i++)
		{
		if (!in_array($closer_campaigns_array[$i], $allowed_ingroups_array))
			{
			array_push($allowed_ingroups_array, $closer_campaigns_array[$i]);
			}
		}
	}

$atomic_queue_ingroups_str="";
$ingroups_id_stmt="select group_id, group_name from vicidial_inbound_groups $where_included_inbound_groups_clause"; #where group_id in ('".implode("', '", $allowed_ingroups_array)."') $included_inbound_groups_clause
$ingroups_id_rslt=mysql_to_mysqli($ingroups_id_stmt, $link);
while($ingroups_id_row=mysqli_fetch_array($ingroups_id_rslt))
	{
	$atomic_queue_str.=$ingroups_id_row["group_name"];
	$atomic_queue_str.=" <i>[".$ingroups_id_row["group_id"]."]</i>,";
	$atomic_queue_ingroups_str.="$ingroups_id_row[group_id]', '";
	}
$and_atomic_queue_ingroups_clause="and campaign_id in ('".$atomic_queue_ingroups_str."')";
$atomic_queue_str=preg_replace('/,$/', '', $atomic_queue_str);

if (strlen($atomic_queue_str)==0)
	{
	$atomic_queue_str="NONE";
	}

$vicidial_log_SQL.="$and_atomic_queue_campaigns_clause";
$vicidial_closer_log_SQL.="$and_atomic_queue_ingroups_clause";

#### END QUEUE GROUPS ####

# For calls from the AGENT SESSIONS report
if ($user)
	{
	$user=preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$and_user_sql=" and user='$user'";
	$where_user_sql=" where user='$user'";

	$vicidial_log_SQL.=$and_user_sql;
	$vicidial_closer_log_SQL.=$and_user_sql;
	}

# For calls from the "QUEUE" SESSIONS report
if ($campaign_id)
	{
	$campaign_id=preg_replace('/[^-_0-9\p{L}]/u','',$campaign_id);
	$and_campaign_id_sql=" and campaign_id='$campaign_id'";
	$where_campaign_id_sql=" where campaign_id='$campaign_id'";

	# For closer log - how's this work?  Query for closer_campaigns?  Or by uniqueids?

	$vicidial_log_SQL.=$and_campaign_id_sql;
	$vicidial_closer_log_SQL.=$and_campaign_id_sql;
	}

if ($users)
	{
	$users=preg_replace('/[^-_0-9\p{L}]/u','',$users);
	$users=RemoveEmptyArrayStrings($users);
	$users_str=is_array($users) ? implode("', '", $users) : "$users";
	$and_user_sql=" and user in ('$users_str')";
	$where_user_sql=" where user in ('$users_str')";

	$vicidial_log_SQL.=$and_user_sql;
	$vicidial_closer_log_SQL.=$and_user_sql;
	}

if ($teams)
	{
	$teams=preg_replace('/[^-_0-9\p{L}]/u','',$teams);
	$teams=RemoveEmptyArrayStrings($teams);
	$teams_str=is_array($teams) ? implode("', '", $teams) : "$teams";
	
	$team_users="";
	$team_stmt="select user from vicidial_users where user_group_two in ('$teams_str')";
	$team_rslt=mysql_to_mysqli($team_stmt, $link);
	while ($team_row=mysqli_fetch_array($team_rslt))
		{
		$team_users.="'$team_row[user]', ";
		}
	$team_users=preg_replace('/, $/', "", $team_users);
	$and_team_sql=" and user in ('$team_users')";
	$where_team_sql=" where user in ('$team_users')";

	$vicidial_log_SQL.=$and_team_sql;
	$vicidial_closer_log_SQL.=$and_team_sql;
	}


if ($location)
	{
	$location=preg_replace('/[^-_0-9\p{L}]/u','',$location);
	$location=RemoveEmptyArrayStrings($location);
	$location_str=is_array($location) ? implode("', '", $location) : "$location";
	$and_location_sql.=" and user_location in ('$location_str')";
	$where_location_sql.=" where user_location in ('$location_str')";

	# Compile list of additional users based on location
	$user_location_stmt="select user from vicidial_users where user is not null $LOGadmin_viewable_groupsSQL $and_location_sql";
	$user_location_rslt=mysql_to_mysqli($user_location_stmt, $link);
	$users_by_location=array();
	while ($user_location_row=mysqli_fetch_row($user_location_rslt))
		{
		array_push($users_by_location, $user_location_row[0]);
		}

	# Combine location with selected user
	if ($users)
		{
		array_push($users_by_location, $users);
		}
	
	$users_by_location_str=implode("', '", $users_by_location);
	$and_users_by_location_sql=" and user in ('$users_by_location_str')";
	$where_users_by_location_sql=" where user in ('$users_by_location_str')";

	$vicidial_agent_log_SQL.=$and_users_by_location_sql;
	$vicidial_log_SQL.=$and_users_by_location_sql;
	$vicidial_closer_log_SQL.=$and_users_by_location_sql;
	}

if ($user_group)
	{
	$user_group=preg_replace('/[^-_0-9\p{L}]/u','',$user_group);
	$user_group=RemoveEmptyArrayStrings($user_group);
	$user_group_str=is_array($user_group) ? implode("', '", $user_group) : "$user_group";
	$and_user_group_sql.=" and user_group in ('$user_group_str')";
	$where_user_group_sql.=" where user_group in ('$user_group_str')";

	$vicidial_agent_log_SQL.=$and_user_group_sql;
	$vicidial_log_SQL.=$and_user_group_sql;
	$vicidial_closer_log_SQL.=$and_user_group_sql;
	}

if ($status_name) // shouldn't ever have status and status name
	{
	$status_str="";
	$status_stmt="select status from vicidial_campaign_statuses where status_name='$status_name' UNION select status from vicidial_statuses where status_name='$status_name'";
	# echo $status_stmt."<BR>";
	$status_rslt=mysql_to_mysqli($status_stmt, $link);
	while($status_row=mysqli_fetch_row($status_rslt))
		{
		$status_str.="'$status_row[0]', ";
		}

	# Query settings containers because the status name may be a long version from there...
	$container_stmt="select * from vicidial_settings_containers where container_id='VERM_STATUS_NAMES_OVERRIDE'";
	$container_rslt=mysql_to_mysqli($container_stmt, $link);
	if (mysqli_num_rows($container_rslt)>0)
		{
		while($container_row=mysqli_fetch_array($container_rslt))
			{
			$container_entry=$container_row["container_entry"];
			$container_array=explode("\n", $container_entry);
			for ($i=0; $i<count($container_array); $i++)
				{
				# Assumed format of status|status_name
				if (!preg_match('/^;/', $container_array[$i]))
					{
					$new_status=explode("|", $container_array[$i]);
					if (trim($status_name)==trim($new_status[1]))
						{
						$status_str.="'$new_status[0]', ";
						}
					}
				}
			}
		}
	$status_str=preg_replace('/, $/', "", $status_str);


	if (strlen($status_str)>0)
		{
		$and_status_sql.=" and status in ($status_str) ";
		}
	else
		{
		$and_status_sql.=" and status ='' ";
		}
	$vicidial_agent_log_SQL.=$and_status_sql;
	$vicidial_log_SQL.=$and_status_sql;
	$vicidial_closer_log_SQL.=$and_status_sql;
	}
else
	{
	#if ($status)  - COmmented this out for when they want to look up no-status calls
	#	{
	
	# Without this if clause requests coming from the AGENTS page will return zero results
	if (!$user)
		{
		$status=preg_replace('/[^-_0-9\p{L}]/u','',$status);
		$and_status_sql.=" and status='$status' ";

		$vicidial_agent_log_SQL.=$and_status_sql;
		$vicidial_log_SQL.=$and_status_sql;
		$vicidial_closer_log_SQL.=$and_status_sql;
		}
	#	}
	}

$vicidial_agent_log_SQL="$where_event_time_sql".$vicidial_log_SQL;
$vicidial_log_SQL="$where_call_date_sql".$vicidial_log_SQL;
$vicidial_closer_log_SQL="$where_call_date_sql".$vicidial_closer_log_SQL;

$selected_columns=array(
"Date" => "call_date",
"Caller" => "phone_number",
"Queue" => "campaign_id",
"IVR" => "ivr_duration",
"Wait" => "wait",
"Duration" => "duration",
"Pos." => "queue_position",
"Disconnection" => "term_reason",
"Handled by" => "user",
"Attempts" => "attempts",
"Code" => "status",
"uniqueid" => "detail_id"
);

$sort_char="";
$sort_index="";

$sort_clause=" order by call_date";
$sort_index=preg_replace('/ desc/', '', $sort_answered_details);
if (preg_match('/ desc$/', $sort_answered_details)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_answered_details);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_answered_details." desc";
	}
$sort_answered_details_preg=preg_replace('/ desc$/', '', $sort_answered_details);

### ALL CALLS FROM vicidial_log AND vicidial_closer_log TABLES - CONTAINS ALL INFO NEEDED BUT DISPO IS UNRELIABLE IN VoxQ
$stmt="select call_date, phone_number, campaign_id, 0 as ivr, '0:00' as wait, sec_to_time(round(length_in_sec)) as duration, '1' as queue_position, CAST(term_reason AS CHAR) as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as did, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, uniqueid as detail_id, 'O' as direction from vicidial_log $vicidial_log_SQL and user not in ('VDAD') UNION select call_date, phone_number, campaign_id, 0 as ivr, sec_to_time(round(queue_seconds)) as wait, sec_to_time(round(if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds))) as duration, queue_position, CAST(term_reason AS CHAR) as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as did, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, CONCAT(uniqueid, '|', closecallid) as detail_id, 'I' as direction from vicidial_closer_log $vicidial_closer_log_SQL and user not in ('VDCL') $sort_clause";
# print $stmt;

### ALL CALLS FROM vicidial_agent_log - DISPO COUNTS RELIABLE, NEED TO FILL GAPS IN REPORT WITH ADDITIONAL QUERIES
# print date("U")."<BR>";
$stmt="select event_time+INTERVAL(pause_sec+wait_sec) SECOND as call_date, lead_id, campaign_id, 0 as ivr, '0:00' as wait, sec_to_time(round(talk_sec)) as duration, '1' as queue_position, '' as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as did, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, uniqueid as detail_id, if(comments='INBOUND', 'I', 'O') as direction from vicidial_agent_log $vicidial_agent_log_SQL and lead_id is not null";

$rslt=mysql_to_mysqli($stmt, $link);
# print $stmt.date("U")."<BR>";

#echo "<table border=0 width='100%' bgcolor='#FFF'><tr>";
#echo "<td>";

## echo "<h2 class='rpt_header'>Call Detail:</h2>";
#echo "<div align='right' style='position:fixed; top: 1em; right: 5em'><a onClick='HideOutcomesDetails()'><h2 class='rpt_header'>[X]</h2></a></div></td></tr>";
#echo "<tr><td>";

$HTML_output="<html>\n";
$HTML_output.="<title>"._QXZ("$report_name")." - "._QXZ("Outcomes detail display")."</title>";
$HTML_output.="<HEAD>\n";
$HTML_output.="<script language=\"JavaScript\" src=\"VERM_custom_form_functions.php\"></script>\n";
$HTML_output.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_output.="<script language=\"JavaScript\" src=\"VERM_functions.js\"></script>\n";
$HTML_output.="</head>\n";
$HTML_output.="<body>\n";
$HTML_output.="$slave_output<form action='$PHP_SELF' method='POST'>\n";

$HTML_output.="<input type='hidden' name='vicidial_queue_groups' id='vicidial_queue_groups' value='$vicidial_queue_groups'>\n";
$HTML_output.="<input type='hidden' name='user' id='user' value='$user'>\n";
$HTML_output.="<input type='hidden' name='users' id='users' value='$users'>\n";
$HTML_output.="<input type='hidden' name='user_group' id='user_group' value='$user_group'>\n";
$HTML_output.="<input type='hidden' name='campaign_id' id='campaign_id' value='$campaign_id'>\n";
$HTML_output.="<input type='hidden' name='location' id='location' value='$location'>\n";
$HTML_output.="<input type='hidden' name='status' id='status' value='$status'>\n";
$HTML_output.="<input type='hidden' name='status_name' id='status_name' value='$status_name'>\n";
$HTML_output.="<input type='hidden' name='start_datetime' id='start_datetime' value='$start_datetime'>\n";
$HTML_output.="<input type='hidden' name='end_datetime' id='end_datetime' value='$end_datetime'>\n";
$HTML_output.="<input type='hidden' name='report_type' id='report_type' value='$report_type'>\n";
$HTML_output.="<input type='hidden' name='download_rpt' id='download_rpt' value='$download_rpt'>\n";
$HTML_output.="<input type='hidden' name='sort_answered_details' id='sort_answered_details' value='$sort_answered_details'>\n";


$HTML_output.="<table border=0 width='100%' bgcolor='#FFF'><tr>";
$HTML_output.="<td align='left'>";
#$HTML_output.="<div style='position:fixed; top: -5; right: -30;'><a onClick='HideAgentDetails()'><h2 class='rpt_header'>[X]</h2></a></div>";
#$HTML_output.="<div align='left'><h2 class='rpt_header'>Agent Detail: $user</h2></div>";
$HTML_output.="<h2 class='rpt_header'>"._QXZ("Statuses Detail").": $NWB#VERM_display_outcomes_detail$NWE</h2>";
# $HTML_output.="\n<!-- $stmt //-->\n";
$HTML_output.="</td>";
$HTML_output.="<td align='right'>";
$HTML_output.="<a onClick='HideOutcomesDetails()'><h2 class='rpt_header'>[X]</h2></a>";
$HTML_output.="</td>";
$HTML_output.="</tr>";

if ($die) {$HTML_output.="</table>\n"; die;}

$HTML_output.="<tr><td colspan='2'>";

$HTML_output.="<span id='all_outcomes_details' style='display:block; overflow-y:auto; height: 70vh;'>\n";

$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' align='left'><B><a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&page_no=1#page_anchor' alt='First page of results'>|<</a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&page_no=".($page_no-1)."#page_anchor' alt='Prev page of results'><<</a></B></td>";


## $HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns))."'>Export as... &nbsp;&nbsp;&nbsp;&nbsp;<a  href=\"".$PHP_SELF."?download_rpt=answer_details&sort_answered_details=".$sort_answered_details."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>"; # &nbsp;&nbsp;&nbsp;&nbsp;Current page: $page_no / $total_pages
$HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns))."'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('OUTCOMES', 'outcomes_details')\" title=\"Export as a CSV file\" value='CSV'></td>";


# $HTML_output.="<td class='export_row_cell' align='right'><B><a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&page_no=".($page_no+1)."#page_anchor' alt='Next page of results'>>></a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&page_no=".($total_pages-1)."#page_anchor' alt='Last page of results'>>|</a></B></td>";
$HTML_output.="</tr>";

$HTML_output.="<tr>";
$CSV_output="";
foreach ($selected_columns as $display_name => $column_name)
	{
	if ($display_name!="uniqueid")
		{
		$CSV_output.="\"$display_name\",";
		# $HTML_output.="<th><a class='header_link' name='call_detail_sort_".$column_name."' id='call_detail_sort_".$column_name."' href='".$PHP_SELF."?sort_answered_details=".($column_name==$sort_answered_details_preg ? "$reverse_link" : "$column_name")."&page_no=".$page_no."#call_detail_sort_".$column_name."'>".$display_name.($column_name==$sort_answered_details_preg ? " $sort_char" : "")."</a></th>";
		$HTML_output.="<th class='header_link'>".$display_name.($column_name==$sort_answered_details_preg ? " $sort_char" : "")."</th>";

		# $HTML_output.="<th><input type='button' class='sort_button' value='".$display_name.($column_name==$sort_answered_details_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_answered_details').value='".($column_name==$sort_answered_details_preg ? "$reverse_link" : "$column_name")."'; this.form.submit()\"></th>\n";		
		}
	}
$CSV_output.="\n";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>";

$output_array=array();
$i=0;
while ($row=mysqli_fetch_array($rslt))
	{
	$row["caller_code"]="";
	$row["ivr"]="00:00";
	$row["ivr_duration"]="00:00";
	$row["ivr_path"]="";
	$row["did"]="";


	$call_info_array=GetCallInfo($row["uniqueid"], $row["lead_id"], $row["call_date"], $row["user"], $row["status"]);
	$row["phone_number"]=$call_info_array[0];
	$row["term_reason"]=$call_info_array[1];
	$row["queue_position"]=$call_info_array[2];

	$uniqueid=$row["uniqueid"];
	$row["campaign_id"]=$queue_names["$row[campaign_id]"];
	if ($status_names["$row[status]"]) {$row["status"]=$status_names["$row[status]"];}
	$row["user"]=$fullname_info["$row[user]"];


	$row["caller_code"]=GetCallerCode($row["uniqueid"]);
	if ($row["direction"]=="I")
		{
		$ivr_info_array=GetIVRInfo($row["uniqueid"], $row["call_date"]);
		$row["ivr"]=$ivr_info_array[0];
		$row["ivr_duration"]=$ivr_info_array[1];
		$row["ivr_path"]=$ivr_info_array[2];
		$row["call_date"]=$ivr_info_array[3];
		$row["did"]=GetDID($row["uniqueid"]);
		}

#	if ($i>=$ll && $i<$ul || $download_rpt)
#		{
		$current_row=array();
		foreach ($selected_columns as $display_name => $column_name)
			{
			$current_row["$column_name"]=$row["$column_name"];
			}

		array_push($output_array, $current_row);
#		}
	$i++;
	}

if ($sort_index)
	{
	#$sort_index=8;
	# print "***** $sort_index ********\n";
	# array_multisort($output_array[$sort_index], SORT_ASC, SORT_STRING);

	foreach ($output_array as $sorting_array) {
        $sort_array_holder[] = $sorting_array["$sort_index"];
    }
	if (preg_match('/ desc$/', $sort_answered_details))
		{
		array_multisort($sort_array_holder,SORT_DESC, SORT_STRING,$output_array);
		}
	else
		{
		array_multisort($sort_array_holder,SORT_ASC, SORT_STRING,$output_array);
		}

	#usort($output_array, function($a, $b){
    #        return strcmp($a[$sort_index], $b[$sort_index]);
	#});
	}

foreach ($output_array as $data_row)
	{
	$HTML_output.="<tr>";
	foreach ($data_row as $key => $value)
		{
		if ($key=="call_date")
			{
			$myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
			$value = $myDateTime->format('m/d - H:i:s');
			}
		if ($key!="detail_id")
			{
			$HTML_output.="<td>".$value."</td>";
			$CSV_output.="\"".$value."\",";
			}
		else
			{
			if ($value && $value!="")
				{
				$HTML_output.="<td><a onClick=\"ShowCallDetails('$value', 'answered', '80', '70')\"><svg width='20' height='20' viewBox='0 0 20 20' data-svg='search'><circle fill='none' stroke='#000' stroke-width='1.1' cx='9' cy='9' r='7'></circle><path fill='none' stroke='#000' stroke-width='1.1' d='M14,14 L18,18 L14,14 Z'></path></svg></a></td>";
				}
			else
				{
				$HTML_output.="<td>-</td>";
				}
			# $HTML_output.="<td><IMG SRC=\"images/glass.png\" onClick=\"ShowCallDetails('$value')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></td>";
			}
		}
	$CSV_output.="\n";

	$HTML_output.="</tr>";
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' align='left'><B><a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&page_no=1#page_anchor' alt='First page of results'>|<</a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&page_no=".($page_no-1)."#page_anchor' alt='Prev page of results'><<</a></B></td>";

# $HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns))."'>Export as... &nbsp;&nbsp;&nbsp;&nbsp;<a  href=\"".$PHP_SELF."?download_rpt=answer_details&sort_answered_details=".$sort_answered_details."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>"; # &nbsp;&nbsp;&nbsp;&nbsp;Current page: $page_no / $total_pages
$HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns))."'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('OUTCOMES', 'outcomes_details')\" title=\"Export as a CSV file\" value='CSV'></td>";

# $HTML_output.="<td class='export_row_cell' align='right'><B><a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&page_no=".($page_no+1)."#page_anchor' alt='Next page of results'>>></a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&page_no=".($total_pages-1)."#page_anchor' alt='Last page of results'>>|</a></B></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if ($download_rpt)
	{
	$data_to_download=$CSV_output;

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "OUTCOMES_RPT_".$download_rpt."_".$FILE_TIME.".csv";
	header('Content-type: application/octet-stream');

	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$data_to_download";

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

	#	$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
	#	if ($DB) {echo "|$stmt|\n";}
	#	$rslt=mysql_to_mysqli($stmt, $link);

	exit;

	}

echo $HTML_output; 

echo "</span>\n";

echo "</td></tr></table>";

echo "</form></body></html>";
# echo "Download_rpt: $download_rpt";
?>

<?php
function GetCallerCode($uniqueid)
	{
	global $link, $DB;
	$caller_code="";
	$cc_stmt="select caller_code from vicidial_log_extended where uniqueid='$uniqueid'";
	$cc_rslt=mysqli_query($link, $cc_stmt);
	$cc_row=mysqli_fetch_array($cc_rslt);
	$caller_code=$cc_row[0];
	return $caller_code;
	}

function GetCallInfo($uniqueid, $lead_id, $call_date, $user, $status)
	{
	global $link, $DB;
	$phone_number="";
	$term_reason="";
	$queue_position="";

	if (!$uniqueid)
		{
		$ph_stmt="select phone_number, term_reason, queue_position, '1' as priority from vicidial_closer_log where lead_id='$lead_id' and user='$user' and status='$status' UNION 
		select phone_number, term_reason, '1' as queue_position, '2' as priority from vicidial_log where lead_id='$lead_id' and user='$user' and status='$status' UNION 
		select phone_number, '' as term_reason, '1' as queue_position, '3' as priority from vicidial_list where lead_id='$lead_id' UNION 
		select phone_number, '' as term_reason, '1' as queue_position, '4' as priority from vicidial_list_archive where lead_id='$lead_id' order by priority asc limit 1";
		}
	else
		{
		$ph_stmt="select phone_number, term_reason, '1' as queue_position, if(lead_id='$lead_id', 2, 0) as priority from vicidial_log where uniqueid='$uniqueid' UNION select phone_number, term_reason, queue_position, if(lead_id='$lead_id', 3, 1) as priority from vicidial_closer_log where uniqueid='$uniqueid' order by priority desc limit 1";
		}
	$ph_rslt=mysqli_query($link, $ph_stmt);
	$ph_row=mysqli_fetch_array($ph_rslt);
	$phone_number=$ph_row[0];
	$term_reason=$ph_row[1];
	$queue_position=$ph_row[2];
	# print $ph_stmt.date("U")."<BR>";

	# return $phone_number;
	return array("$phone_number", "$term_reason", "$queue_position");
	}


function GetTermReason($uniqueid)
	{
	global $link, $DB;
	$term_reason="NONE";
	$term_stmt="select term_reason from vicidial_log where uniqueid='$uniqueid' UNION select term_reason from vicidial_closer_log where uniqueid='$uniqueid'";
	$term_rslt=mysqli_query($link, $term_stmt);
	if (mysqli_num_rows($term_rslt)>0)
		{
		$term_row=mysqli_fetch_array($term_rslt);
		$term_reason=$term_row[0];
		}
	return $term_reason;
	}

function GetIVRInfo($uniqueid, $actual_start_time)
	{
	global $link, $DB;

	$ivr_path="";
	$ivr_length=0;
	$ivr_duration=0;

	$ivr_stmt="select extension,start_time,comment_a,comment_b,comment_d,UNIX_TIMESTAMP(start_time),phone_ext from live_inbound_log where uniqueid='$uniqueid' and comment_a IN('CALLMENU') order by start_time";
	$ivr_rslt=mysqli_query($link, $ivr_stmt);
	$ivr_paths=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
	$ivr_counts=array();
	while ($ivr_row=mysqli_fetch_array($ivr_rslt))
		{
		if(!$prev_time) {$prev_time=$ivr_row[5];}
		if(!$ivr_start_time) 
			{
			$actual_start_time=$ivr_row[1];
			$ivr_start_time=$ivr_row[5];
			}
		$ivrpath.=$ivr_row["comment_b"];

		$ivr_duration=$ivr_row[5]-$ivr_start_time;
		$ivr_length+=$ivr_duration;
		$prev_time=$ivr_row[5];
		}

	$ivr_duration_fmt=($ivr_duration>=3600 ? floor($ivr_duration/3600).":" : "").gmdate("i:s", $ivr_duration);
	$ivr_length_fmt=($ivr_length>=3600 ? floor($ivr_length/3600).":" : "").gmdate("i:s", $ivr_length);

	return array("$ivr_length_fmt", "$ivr_duration_fmt", "$ivr_path", "$actual_start_time");
	}

function GetDID($uniqueid)
	{
	global $link, $DB, $did_id_info, $did_pattern_info;

	$did_str="";

	$did_stmt="select extension, did_id from vicidial_did_log where uniqueid in ('$uniqueid')";
	$did_rslt=mysqli_query($link, $did_stmt);
	$did_row=mysqli_fetch_array($did_rslt);
	$did_str=$did_row["extension"]." - ".$did_id_info["$did_row[extension]"];

	return $did_str;
	}
?>
