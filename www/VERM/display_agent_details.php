<?php
# display_agent_details.php - Vicidial Enhanced Reporting agent details page
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1612 - First build
# 240801-1130 - Code updates for PHP8 compatibility
#
$subreport_name="VERM Reports";
$report_display_type="display_agent_details.php";
$startMS = microtime();

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

require("dbconnect_mysqli.php");
require("functions.php");
require("VERM_options.php");

if (isset($_GET["user"]))			{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))	{$user=$_POST["user"];}
if (isset($_GET["campaign_id"]))			{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))	{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["users"]))			{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))	{$users=$_POST["users"];}
if (isset($_GET["user_group"]))			{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["location"]))			{$location=$_GET["location"];}
	elseif (isset($_POST["location"]))	{$location=$_POST["location"];}
if (isset($_GET["status"]))			{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))	{$status=$_POST["status"];}
if (isset($_GET["vicidial_queue_groups"]))			{$vicidial_queue_groups=$_GET["vicidial_queue_groups"];}
	elseif (isset($_POST["vicidial_queue_groups"]))	{$vicidial_queue_groups=$_POST["vicidial_queue_groups"];}
if (isset($_GET["start_datetime"]))			{$start_datetime=$_GET["start_datetime"];}
	elseif (isset($_POST["start_datetime"]))	{$start_datetime=$_POST["start_datetime"];}
if (isset($_GET["end_datetime"]))			{$end_datetime=$_GET["end_datetime"];}
	elseif (isset($_POST["end_datetime"]))	{$end_datetime=$_POST["end_datetime"];}
if (isset($_GET["total_duration_His"]))			{$total_duration_His=$_GET["total_duration_His"];}
	elseif (isset($_POST["total_duration_His"]))	{$total_duration_His=$_POST["total_duration_His"];}
if (isset($_GET["total_pause_His"]))			{$total_pause_His=$_GET["total_pause_His"];}
	elseif (isset($_POST["total_pause_His"]))	{$total_pause_His=$_POST["total_pause_His"];}
if (isset($_GET["total_all_billable_His"]))			{$total_all_billable_His=$_GET["total_all_billable_His"];}
	elseif (isset($_POST["total_all_billable_His"]))	{$total_all_billable_His=$_POST["total_all_billable_His"];}
if (isset($_GET["total_billable_His"]))			{$total_billable_His=$_GET["total_billable_His"];}
	elseif (isset($_POST["total_billable_His"]))	{$total_billable_His=$_POST["total_billable_His"];}

 # $report_name="Vox Enhanced Reporting Module - Agent detail display";

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
$total_duration_His=preg_replace('/[^0-9\:]/', '', $total_duration_His);
$total_pause_His=preg_replace('/[^0-9\:]/', '', $total_pause_His);
$total_all_billable_His=preg_replace('/[^0-9\:]/', '', $total_all_billable_His);
$total_billable_His=preg_replace('/[^0-9\:]/', '', $total_billable_His);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);

	$download_rpt = preg_replace('/[^\._0-9a-zA-Z]/','',$download_rpt);
	$user = preg_replace('/[^\-_0-9a-zA-Z]/','',$user);
	$users = preg_replace('/[^\-_0-9a-zA-Z]/','',$users);
	$user_group = preg_replace('/[^\-_0-9a-zA-Z]/','',$user_group);
	$campaign_id = preg_replace('/[^\-_0-9a-zA-Z]/','',$campaign_id);
	$location = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$location); 
	$status = preg_replace('/[^\- \.\,\_0-9a-zA-Z]/','',$status);
	$vicidial_queue_groups = preg_replace('/[^-_0-9a-zA-Z]/','',$vicidial_queue_groups);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);

	$download_rpt = preg_replace('/[^\._0-9\p{L}]/u','',$download_rpt);
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$users = preg_replace('/[^-_0-9\p{L}]/u','',$users);
	$user_group = preg_replace('/[^-_0-9\p{L}/u','',$user_group);
	$campaign_id = preg_replace('/[^-_0-9\p{L}/u','',$campaign_id);
	$location = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$location); 
	$status = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$status);
	$vicidial_queue_groups = preg_replace('/[^-_0-9\p{L}]/u','',$vicidial_queue_groups);
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

require("VERM_global_vars.inc");  # Must be after user_authorization for sanitization/security

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$user, $campaign_id, $user_group, $status, $start_datetime, $end_datetime, $file_download|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

if ( (strlen($slave_db_server)>5) and (preg_match("/$subreport_name/",$reports_use_slave_db)) )
        {
        mysqli_close($link);
        $use_slave_server=1;
        $db_source = 'S';
        require("dbconnect_mysqli.php");
        echo "<!-- Using slave server $slave_db_server $db_source -->\n";
        }

if (!$user || !$start_datetime || !$end_datetime) {echo "Missing information - needs a minimum of: user, start date/time, end date/time"; die;}

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

$where_event_time_sql="where event_time>='$start_datetime' and event_time<='$end_datetime' ";
$where_event_date_sql="where event_date>='$start_datetime' and event_date<='$end_datetime' ";

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
# 01/26/2022 - Fix, maybe a little too lax?
if ($atomic_queue_campaigns_str)
	{
	$and_atomic_queue_campaigns_clause="and campaign_id in ('".$atomic_queue_campaigns_str."') ";
	}

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

$vicidial_agent_log_SQL.="$and_atomic_queue_campaigns_clause";
$vicidial_log_SQL.="$and_atomic_queue_campaigns_clause";
$vicidial_closer_log_SQL.="$and_atomic_queue_ingroups_clause";
$vicidial_user_log_SQL.="$and_atomic_queue_campaigns_clause";

#### END QUEUE GROUPS ####

# For calls from the AGENT SESSIONS report
if ($user)
	{
	$user=preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$and_user_sql=" and user='$user' ";
	$where_user_sql=" where user='$user' ";

	$vicidial_agent_log_SQL.=$and_user_sql;
	$vicidial_log_SQL.=$and_user_sql;
	$vicidial_closer_log_SQL.=$and_user_sql;
	$vicidial_user_log_SQL.=$and_user_sql;
	}

# For calls from the "QUEUE" SESSIONS report
if ($campaign_id)
	{
	$campaign_id=preg_replace('/[^-_0-9\p{L}]/u','',$campaign_id);
	$and_campaign_id_sql=" and campaign_id='$campaign_id' ";
	$where_campaign_id_sql=" where campaign_id='$campaign_id' ";

	# For closer log - how's this work?  Query for closer_campaigns?  Or by uniqueids?

	$vicidial_agent_log_SQL.=$and_campaign_id_sql;
	$vicidial_log_SQL.=$and_campaign_id_sql;
	$vicidial_closer_log_SQL.=$and_campaign_id_sql;
	$vicidial_user_log_SQL.=$and_campaign_id_sql;
	}

/* THIS PAGE IS FOR SPECIFIC AGENTS, NOT NECESSARY TO FILTER FOR THIS
if ($users)
	{
	$users=preg_replace('/[^-_0-9\p{L}]/u','',$users);
	$users=RemoveEmptyArrayStrings($users);
	$users_str=is_array($users) ? implode("', '", $users) : "$users";
	$and_users_sql=" and user in ('$users_str')";
	$where_users_sql=" where user in ('$users_str')";

	$vicidial_agent_log_SQL.=$and_users_sql;
	$vicidial_log_SQL.=$and_users_sql;
	$vicidial_closer_log_SQL.=$and_users_sql;
	}
*/

/* THIS PAGE IS FOR SPECIFIC AGENTS, NOT NECESSARY TO FILTER FOR THIS
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
*/

if ($user_group)
	{
	$user_group=preg_replace('/[^-_0-9\p{L}]/u','',$user_group);
	$user_group=RemoveEmptyArrayStrings($user_group);
	$user_group_str=is_array($user_group) ? implode("', '", $user_group) : "$user_group";
	$and_user_group_sql.=" and user_group in ('$user_group_str') ";
	$where_user_group_sql.=" where user_group in ('$user_group_str') ";

	$vicidial_agent_log_SQL.=$and_user_group_sql;
	$vicidial_log_SQL.=$and_user_group_sql;
	$vicidial_closer_log_SQL.=$and_user_group_sql;
	$vicidial_user_log_SQL.=$and_user_group_sql;
	}

if ($status)
	{
	$status=preg_replace('/[^-_0-9\p{L}]/u','',$status);
	$and_status_sql.=" and status='$status' ";

	$vicidial_agent_log_SQL.=$and_status_sql;
	$vicidial_log_SQL.=$and_status_sql;
	$vicidial_closer_log_SQL.=$and_status_sql;
	}

$vicidial_agent_log_SQL="$where_event_time_sql".$vicidial_agent_log_SQL;
$vicidial_log_SQL="$where_call_date_sql".$vicidial_log_SQL;
$vicidial_closer_log_SQL="$where_call_date_sql".$vicidial_closer_log_SQL;
$vicidial_user_log_SQL="$where_event_date_sql".$vicidial_user_log_SQL;

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


#echo "<div style='position:fixed;'>";
#echo "<table align='right' border=0 width='100%' bgcolor='#FFF'><tr><td align='right'>";
#echo "<a onClick='HideAgentDetails()'><h2 class='rpt_header'>[X]</h2></a>";
#echo "</td></tr></table>";
#echo "</div>";

echo "<table border=0 width='100%' bgcolor='#FFF'><tr>";
echo "<td align='left'>";
#echo "<div style='position:fixed; top: -5; right: -30;'><a onClick='HideAgentDetails()'><h2 class='rpt_header'>[X]</h2></a></div>";
#echo "<div align='left'><h2 class='rpt_header'>Agent Detail: $user</h2></div>";
echo "<h2 class='rpt_header'>"._QXZ("Agent Detail").": $user $NWB#VERM_display_agent_details$NWE</h2>";
echo "</td>";

echo "<td align='right'>";
echo "<a onClick='HideAgentDetails()'><h2 class='rpt_header'>[X]</h2></a>";
echo "</td>";
echo "</tr>";

echo "<tr><td colspan='2'>";
echo "<span id='all_agent_details' style='display:block; overflow-y:auto; height: 70vh;'>\n";

echo "<table id=\"rpt_table\">\n";
echo "	<tr>\n";
echo "		<td style=\"width:25vw\">"._QXZ("Total session time").":</th>\n";
echo "		<td style=\"width:75vw\">$total_duration_His</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td style=\"width:25vw\">"._QXZ("Total pause time").":</td>\n";
echo "		<td style=\"width:75vw\">$total_pause_His</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td style=\"width:25vw\">"._QXZ("Total billable time (b. pauses + talk + wait + dispo)").":</td>\n";
echo "		<td style=\"width:75vw\">$total_all_billable_His</td>\n";
echo "	</tr>\n";
echo "	<tr>\n";
echo "		<td style=\"width:25vw\">"._QXZ("Total billable pauses").":</td>\n";
echo "		<td style=\"width:75vw\">$total_billable_His</td>\n";
echo "	</tr>\n";
echo "</table>\n";

echo "<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";

### FUN STUFF
# Clause to deal with odd occurrence where agent's first record for the day is a LOGOUT (see Jamar Jenkins for 01/21/2022)
$event_date_clause_apx="";
if (!$overnight_agents)
	{
	$init_start_stmt="select min(event_date) from vicidial_user_log $vicidial_user_log_SQL and event='LOGIN' and user='$user'";
	# print $init_start_stmt."<BR>\n";
	$init_start_rslt=mysql_to_mysqli($init_start_stmt, $link);
	if (mysqli_num_rows($init_start_rslt)>0)
		{
		$isr_row=mysqli_fetch_row($init_start_rslt);
		$event_date_clause_apx=" and event_date>='$isr_row[0]' ";
		}
	}


$session_stmt="select * from vicidial_user_log $vicidial_user_log_SQL $event_date_clause_apx and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') and user='$user' order by event_date asc";
if ($DB) {$HTML_output.="<B>$session_stmt</B>";}
# print "$session_stmt<BR>\n";
$session_rslt=mysql_to_mysqli($session_stmt, $link);
$prev_date="";
$start_event_date="";

### ITERATE THROUGH ALL THE AGENTS SESSIONS MATCHING THE REPORT CRITERIA
$row_no=0; $agent_logged_in=0;
$agent_sessions_array=array();
while ($session_row=mysqli_fetch_array($session_rslt))
	{
	# Need this for when the report runs with a start date right in the middle of agents logged in
	$original_session_start_date="$start_datetime";
	$original_session_cutoff_date=substr($start_datetime, 10)." 00:00:00";
	$original_session_cutoff_eod=substr($start_datetime, 10)." 23:59:59";

	$user=$session_row["user"];
	$campaign_id=$session_row["campaign_id"];
	$extension=$session_row["extension"];
	$event=$session_row["event"];
	$event_epoch=$session_row["event_epoch"];
	$event_time=$session_row["event_date"];
	$event_date=substr($event_time, 0, 10);
	if (!$prev_date) {$prev_date=$event_date;}

	# END date in case agent is still logged in at that time
	if ($event_date!=$TODAY)
		{
		$event_date_eod="$event_date 23:59:59";
		}
	else
		{
		$event_date_eod="$event_date $NOW_TIME";
		}
	
	#### CHECK IF THE USER WAS LOGGED IN AT THE START DATE/TIME, BUT ONLY DO THIS FOR THE FIRST ROW RETURNED
	if ($row_no==0)
		{
		$override_login=0;
		$previous_interval_stmt="select * from vicidial_user_log where user='$user' $and_vicidial_user_log_SQL and event_date<='$original_session_start_date' and event_date>='$original_session_cutoff_date' and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') order by user, event_date desc limit 1";
		$previous_interval_rslt=mysql_to_mysqli($previous_interval_stmt, $link);
		# print "$previous_interval_stmt;\n";
		if (mysqli_num_rows($previous_interval_rslt)>0)
			{

			#### GO BACKWARDS, IF THE PRECEDING RECORD IS A LOGIN, THEN USE THE SESSION DATE/TIME AS THE START TIME FOR THIS INTERVAL. 
			$previous_interval_row=mysqli_fetch_array($previous_interval_rslt);
			if ($previous_interval_row["event"]=="LOGIN")
				{
				$override_login=1;
				$login_notes="PRIOR LOGIN";
				}
			else
				{
				#### IF THE LAST RECORD IS A LOGOUT, CHECK THAT THE NEXT RECORD CHRONOLOGICALLY IS ALSO NOT A LOGOUT
				#### BECAUSE IF SO, THE AGENT WAS LAGGED AND WAS STILL LOGGED IN (OR THE LOGIN WASN'T LOGGED)
				$next_interval_stmt="select * from vicidial_user_log where user='$user' $and_vicidial_user_log_SQL and event_date>='$original_session_start_date' and event_date<='$original_session_cutoff_eod' and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') order by user, event_date desc limit 1";
				# print "$next_interval_stmt;\n";
				$next_interval_rslt=mysql_to_mysqli($next_interval_stmt, $link);
				if (mysqli_num_rows($next_interval_rslt)>0)
					{
					$next_interval_row=mysqli_fetch_array($next_interval_rslt);
					if (preg_match('/LOGOUT/', $next_interval_row["event"]))
						{
						$override_login=1;
						$login_notes="DOUBLE LOGOUT";
						}
					}
				}

			if ($override_login==1)
				{
				$start_event_date=$original_session_start_date;
				$start_epoch_stmt="select unix_timestamp('$start_event_date')";
				$start_epoch_rslt=mysql_to_mysqli($start_epoch_stmt, $link);
				$start_epoch_row=mysqli_fetch_row($start_epoch_rslt);
				$override_event_epoch=$start_epoch_row[0];

				$agent_sessions_array["$start_event_date"]["start_hour"]=$override_event_epoch;
				$agent_sessions_array["$start_event_date"]["extension"]=$extension;
				$agent_sessions_array["$start_event_date"]["login_notes"]=$login_notes;


				$agent_logged_in=1;
				}
			}
		}
	$row_no++; # Keeps from doing an override check

	
	if ($event=="LOGIN")
		{
		#### IF AGENT IS NOT LOGGED IN, CREATE A NEW INTERVAL
		# 2/23/22 - agent page when back-to-back logins, first login is overwritten, see Dougwanna Brown 2/15/22-2/22/22.  Purge it here
		if ($agent_logged_in!=0)
			{
			unset($agent_sessions_array["$start_event_date"]);
			$agent_logged_in=0;
			}
		if ($agent_logged_in==0)
			{
			$start_event_date=$event_time;
			$agent_sessions_array["$start_event_date"]["user_group"]=$user_group;
			$agent_sessions_array["$start_event_date"]["start_hour"]=$event_epoch;
			$agent_sessions_array["$start_event_date"]["extension"]=$extension;
			$agent_sessions_array["$start_event_date"]["server_ip"]=$server_ip;

			}
		$agent_logged_in=1;
		}

	if (preg_match('/LOGOUT/', $event)) #  && $agent_logged_in==1, removed this due to back to back logouts (see user 105110 for 2022-01-10)
		{
# 2/23/22 - agent page when back-to-back logouts, second logout is ignored, see Dougwanna Brown 2/15/22-2/22/22. 
#		$agent_sessions_array["$start_event_date"]["end_hour"]=$event_epoch;
#		$agent_sessions_array["$start_event_date"]["end_date"]=$event_time;
#		$agent_sessions_array["$start_event_date"]["duration"]=($agent_sessions_array["$start_event_date"]["end_hour"]-$agent_sessions_array["$start_event_date"]["start_hour"]);

		if ($agent_logged_in==1)
			{
			$agent_sessions_array["$start_event_date"]["end_hour"]=$event_epoch;
			$agent_sessions_array["$start_event_date"]["end_date"]=$event_time;
			$agent_sessions_array["$start_event_date"]["duration"]=($agent_sessions_array["$start_event_date"]["end_hour"]-$agent_sessions_array["$start_event_date"]["start_hour"]);
			$agent_sessions_array["$start_event_date"]["notes"]="NORMAL LOGOUT";
			}
		else
			{
			$agent_sessions_array["$start_event_date"]["notes"]="REPEAT LOGOUT";
			}

		$agent_logged_in=0;
		}
	
	$override_login=0;
	$prev_date=$event_date;
	$prev_event=$event;
	$prev_campaign_id=$campaign_id;
	}

$TODAY=date("Y-m-d");
$NOW_TIME=date("H:i:s");

if($agent_logged_in)
	{ 
	if ($event_date!=$TODAY)
		{
		$event_date_eod="$event_date 23:59:59";
		$notes="EOD LOGOUT";
		}
	else
		{
		$event_date_eod="$event_date $NOW_TIME";
		$notes="STILL LOGGED IN";
		}

	$eod_epoch_stmt="select unix_timestamp('$start_event_date'), unix_timestamp('$event_date_eod')";
	$eod_epoch_rslt=mysql_to_mysqli($eod_epoch_stmt, $link);
	$eod_epoch_row=mysqli_fetch_row($eod_epoch_rslt);
	$agent_sessions_array["$start_event_date"]["start_hour"]=$eod_epoch_row[0];
	$agent_sessions_array["$start_event_date"]["end_hour"]=$eod_epoch_row[1];
	$agent_sessions_array["$start_event_date"]["end_date"]=$event_date_eod;
	$agent_sessions_array["$start_event_date"]["duration"]=($agent_sessions_array["$start_event_date"]["end_hour"]-$agent_sessions_array["$start_event_date"]["start_hour"]);
	$agent_sessions_array["$start_event_date"]["notes"]=$notes;

	$agent_logged_in=0;
	}

if ($DB) {print_r($agent_sessions_array);}

echo "<table id=\"details_table\">\n";
echo "<tr>\n";
echo "<th>"._QXZ("Agent")."</th>\n";
echo "<th>"._QXZ("Ext.")."</th>\n";
echo "<th>"._QXZ("Duration")."</th>\n";
echo "<th>"._QXZ("On pause")."</th>\n";
echo "<th>"._QXZ("Overlapping")."</th>\n";
echo "<th>"._QXZ("Activity")."</th>\n";
echo "<th> </th>\n";
echo "<th>"._QXZ("Start hour")."</th>\n";
echo "<th>"._QXZ("End hour")."</th>\n";
echo "</tr>\n";

foreach ($agent_sessions_array as $session_start_date => $session)
	{
	echo "<tr>\n";
	echo "<td class='small_text'>".$fullname_info["$user"]."</td>\n";
	echo "<td class='small_text'>".$session["extension"]."</td>\n";
	echo "<td class='small_text'>".sprintf('%02d', floor($session["duration"]/3600)).gmdate(":i:s", $session["duration"])."</td>\n";
	echo "<td class='small_text'>&nbsp;</td>\n";
	echo "<td class='small_text'>0:00</td>\n";
	echo "<td class='small_text'>-</td>\n";
	echo "<td class='small_text'> </td>\n";
	echo "<td class='small_text'>".$session_start_date."</td>\n";
	echo "<td class='small_text'>".$session["end_date"]."</td>\n";
	echo "</tr>\n";

	$agent_log_stmt="select sec_to_time(pause_sec) as pause_sec_fmt, event_time, event_time+INTERVAL pause_sec SECOND as end_time, if(sub_status is null or sub_status='BLANK', '-', sub_status) as pause_code, campaign_id from vicidial_agent_log where event_time>='$session_start_date' and event_time<='".$session["end_date"]."' and user='$user' order by event_time asc";
	# print $agent_log_stmt;
	$agent_log_rslt=mysql_to_mysqli($agent_log_stmt, $link);
	while ($agent_log_row=mysqli_fetch_array($agent_log_rslt))
		{
		$campaign_id=$agent_log_row["campaign_id"];

		$bp="N"; $pp="N";
		if (in_array($agent_log_row["pause_code"], $billable_pause_codes["$campaign_id"]) || in_array($agent_log_row["pause_code"], $billable_pause_codes["SYSTEM"]))
			{
			$is_billable="Yes"; $bp="";
			}
		if (!in_array($agent_log_row["pause_code"], $payable_pause_codes["$campaign_id"]) && in_array($agent_log_row["pause_code"], $payable_pause_codes["SYSTEM"]))
			{
			$pp="";
			}
		$billable_code=$bp."B".$pp."P";

		$pause_name_key=$campaign_id."-".$agent_log_row["pause_code"];

		echo "<tr>\n";
		echo "<td class='small_text'>&nbsp;</td>\n";
		echo "<td class='small_text'>&nbsp;</td>\n";
		echo "<td class='small_text'>&nbsp;</td>\n";
		echo "<td class='small_text'>".$agent_log_row["pause_sec_fmt"]."</td>\n";
		echo "<td class='small_text'>0:00</td>\n";
		echo "<td class='small_text'>".$pause_code_names["$pause_name_key"]."</td>\n";
		echo "<td class='small_text'>$billable_code</td>\n";
		echo "<td class='small_text'>".$agent_log_row["event_time"]."</td>\n";
		echo "<td class='small_text'>".$agent_log_row["end_time"]."</td>\n";
		echo "</tr>\n";
		}

	echo "<tr>\n";
	echo "<td class='small_text'>".$fullname_info["$user"]."</td>\n";
	echo "<td class='small_text'>".$session["extension"]."</td>\n";
	echo "<td class='small_text'>".sprintf('%02d', floor($session["duration"]/3600)).gmdate(":i:s", $session["duration"])."</td>\n";
	echo "<td class='small_text'>&nbsp;</td>\n";
	echo "<td class='small_text'>&nbsp;</td>\n";
	echo "<td class='small_text'>"._QXZ("Logout")."</td>\n";
	echo "<td class='small_text'>-</td>\n";
	echo "<td class='small_text'>".$session_start_date."</td>\n";
	echo "<td class='small_text'>".$session["end_date"]."</td>\n";
	echo "</tr>\n";
	echo "<tr>\n";
	echo "<td colspan='9'>&nbsp;</td>\n";
	echo "</tr>\n";

	}
echo "</table>";

echo "</span>\n";

echo "</td></tr></table>";
# print_r($agent_sessions_array);

if ($db_source == 'S')
        {
        mysqli_close($link);
        $use_slave_server=0;
        $db_source = 'M';
        require("dbconnect_mysqli.php");
	}

$ENDtime=date("U");
$endMS = microtime();
$startMSary = explode(" ",$startMS);
$endMSary = explode(" ",$endMS);
$runS = ($endMSary[0] - $startMSary[0]);
$runM = ($endMSary[1] - $startMSary[1]);
$TOTALrun = ($runS + $runM);

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

?>
