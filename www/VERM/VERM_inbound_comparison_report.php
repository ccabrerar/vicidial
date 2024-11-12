<?php 

# VERM_inbound_comparison_report.php

# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2

# changes
# 230225-1531 - First build of script
# 240801-1139 - Code updates for PHP8 compatibility
#

$startMS = microtime();

$version = '2.14-42';
$build = '240801-1139';

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

require("VERM_options.php");

if (isset($_GET["start_date"]))			{$start_date=$_GET["start_date"];}
	elseif (isset($_POST["start_date"]))	{$start_date=$_POST["start_date"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["comparison_years"]))			{$comparison_years=$_GET["comparison_years"];}
	elseif (isset($_POST["comparison_years"]))	{$comparison_years=$_POST["comparison_years"];}
if (isset($_GET["comparison_months"]))			{$comparison_months=$_GET["comparison_months"];}
	elseif (isset($_POST["comparison_months"]))	{$comparison_months=$_POST["comparison_months"];}
if (isset($_GET["submit_report"]))			{$submit_report=$_GET["submit_report"];}
	elseif (isset($_POST["submit_report"]))	{$submit_report=$_POST["submit_report"];}
if (isset($_GET["DB"]))			{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}


$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

if ($non_latin < 1)
	{
	$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$DB=preg_replace("/[^0-9\p{L}]/u","",$DB);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	}

$report_name = 'Vox Enhanced Reporting Module';
$db_source = 'M';

$start=date("U");

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

# $PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
# $PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
# $PHP_SELF=$_SERVER['PHP_SELF'];
# $PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

if(!is_array($comparison_years)) {$comparison_years=array();}
if(!is_array($comparison_months)) {$comparison_months=array();}

$start_date=preg_replace('/[^-0-9]/', '', $start_date);
$end_date=preg_replace('/[^-0-9]/', '', $end_date);
$comparison_years=preg_replace('/[^0-9]/', '', $comparison_years);
$comparison_months=preg_replace('/[^0-9]/', '', $comparison_months);
$submit_report=preg_replace('/[^0-9]/', '', $submit_report);

$default_queue_groups=array("514915v_USA_Shared", "515915v_MLA_Shared", "001_ALL_USA_MLA_DED");
$default_queue_group_names=array("Onshore", "Offshore", "Onshore + Offshore");
$survey_ivr_groups=array("561403", "561402");
$survey_ivr_group_names=array("PRS-Survey-Overall-iVR", "PRS-Survey-NPS-iVR");
if (!$vicidial_queue_groups) {$vicidial_queue_groups=$default_queue_groups;}
if (!$vicidial_queue_group_names) {$vicidial_queue_group_names=$default_queue_group_names;}

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

$stmt="select *, if(queue_group in ('".implode("', '", $default_queue_groups)."'), 0, 1) as priority from vicidial_queue_groups where active='Y' $LOGallowed_queue_groupsSQL order by priority, queue_group, queue_group_name;";
$rslt=mysql_to_mysqli($stmt, $link);
# echo $stmt;
if ($DB) {$MAIN.="$stmt\n";}
$queue_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$LISTqueue_groups=array();
$LISTqueue_group_names=array();
#$LISTqueue_groups[$i]='ALL-INGROUPS';
#$i++;
$queue_groups_to_print++;
$queue_groups_string='|';
$queue_groups_dropdown="";
while ($i < $queue_groups_to_print)
	{
	$row=mysqli_fetch_array($rslt);
	$LISTqueue_groups[$i] =		$row["queue_group"];
	$LISTqueue_group_names[$i] =	$row["queue_group_name"];
	$queue_groups_string .= "$LISTqueue_groups[$i]|";
	
	if(in_array($row["queue_group"], $vicidial_queue_groups)) {$s=" selected";} else {$s="";}
	$queue_groups_dropdown .= "\t<option value='$row[queue_group]'$s>$row[queue_group_name]</option>\n";
	$i++;

	if (!$default_queue_group)  {$default_queue_group=$row["queue_group_name"];}
	}

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


#### QUEUE GROUPS ####
/*
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
		$included_campaigns_array=explode(" ", $included_campaigns);
		$included_campaigns_array=RemoveEmptyArrayStrings($included_campaigns_array);
		$included_campaigns_ct=count($included_campaigns_array);
		$included_campaigns_clause="and campaign_id in ('".preg_replace('/\s/', "', '", $included_campaigns)."')";

		$included_inbound_groups=trim(preg_replace('/\s\-$/', '', $vqg_row["included_inbound_groups"]));
		$included_inbound_groups_array=explode(" ", $included_inbound_groups);
		$included_inbound_groups_array=RemoveEmptyArrayStrings($included_inbound_groups_array);
		$included_inbound_groups_ct=count($included_inbound_groups_array);
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

$inbound_only_agents_array=array();
if (count($included_campaigns_array)==0 && count($included_inbound_groups_array)>0) # Need to get list of users for user log table/vicidial agent log table
	{
	$closer_campaigns_SQL="and (";
	for ($q=0; $q<count($included_inbound_groups_array); $q++)
		{
		$closer_campaigns_SQL.="closer_campaigns like '% ".$included_inbound_groups_array[$q]." %' OR ";
		}
	$closer_campaigns_SQL=preg_replace('/ OR $/', "", $closer_campaigns_SQL).")";
	$vucl_stmt="select * from vicidial_user_closer_log $where_event_date_sql $closer_campaigns_SQL";
	$vucl_rslt=mysql_to_mysqli($vucl_stmt, $link);
	while ($vucl_row=mysqli_fetch_array($vucl_rslt))
		{
		if (!in_array($vucl_row["user"], $inbound_only_agents_array))
			{
			$inbound_only_agents_array[]=$vucl_row["user"];
			}
		}
	$and_inbound_only_agents_clause=" and user in ('".implode("', '", $inbound_only_agents_array)."') ";
	}

# Ok to have campaign_id='' here because it will filter out outbound
$vicidial_log_SQL.="$and_atomic_queue_campaigns_clause";
$vicidial_closer_log_SQL.="$and_atomic_queue_ingroups_clause";
# $vicidial_did_log_SQL.="$where_call_date_sql";

# If no campaigns are involved in the queue group, we need to use the $inbound_agents_only_array
if (count($included_campaigns_array)>0 || count($inbound_only_agents_array)==0)
	{
	$vicidial_user_log_SQL.="$and_atomic_queue_campaigns_clause";
	$vicidial_agent_log_SQL.="$and_atomic_queue_campaigns_clause";
	}
else
	{
	$vicidial_user_log_SQL.="$and_inbound_only_agents_clause";
	$vicidial_agent_log_SQL.="$and_inbound_only_agents_clause";
	# $debug_alert.="<B> *** INBOUND ONLY QUEUE *** </B>";
	}
*/
#### END QUEUE GROUPS ####


$vicidial_queue_groups=preg_replace('/[^-_0-9\p{L}]/u','',$vicidial_queue_groups);
$vicidial_queue_groups=RemoveEmptyArrayStrings($vicidial_queue_groups);

if ($vicidial_queue_groups && $submit_report)
	{

	$HTML_output="";

	for ($y=0; $y<count($comparison_years); $y++)
		{
		$current_year=$comparison_years[$y];
		$section_rowspan=count($vicidial_queue_groups);
		$comparison_rslts=array();

		for ($i=0; $i<count($vicidial_queue_groups); $i++)
			{
			$current_queue_group=$vicidial_queue_groups[$i];
			$current_queue_group_name=$vicidial_queue_group_names[$i];

			$vicidial_log_SQL="where year(call_date)='$current_year' and month(call_date) in (".implode(", ", $comparison_months).") ";
			$vicidial_closer_log_SQL="where year(call_date)='$current_year' and month(call_date) in (".implode(", ", $comparison_months).") ";
			$vicidial_user_log_SQL="where year(event_date)='$current_year' and month(event_date) in (".implode(", ", $comparison_months).") ";
			$vicidial_agent_log_SQL="where year(event_time)='$current_year' and month(event_time) in (".implode(", ", $comparison_months).") ";
			$where_event_date_SQL="where year(event_date)='$current_year' and month(event_date) in (".implode(", ", $comparison_months).") ";
		
			for ($m=1; $m<=12; $m++)
				{
				if(in_array($m, $comparison_months))
					{
					$monthName = date("F", mktime(0, 0, 0, $m, 1, 2022));
					$key=$monthName;
					$comparison_rslts["$current_queue_group_name"]["$key"]["offered"]+=0;
					$comparison_rslts["$current_queue_group_name"]["$key"]["answered"]+=0;
					$comparison_rslts["$current_queue_group_name"]["$key"]["total_wait_time"]+=0;
					$comparison_rslts["$current_queue_group_name"]["$key"]["abandoned"]+=0;
					$comparison_rslts["$current_queue_group_name"]["$key"]["total_handle_time"]+=0;
					$comparison_rslts["$current_queue_group_name"]["$key"]["answered_inbound_calls"]+=0;
					$comparison_rslts["$current_queue_group_name"]["$key"]["sla_calls"]+=0;
					}
				}
	
			$vqg_stmt="select included_campaigns, included_inbound_groups from vicidial_queue_groups where queue_group='$current_queue_group'";
			$vqg_rslt=mysql_to_mysqli($vqg_stmt, $link);
			if(mysqli_num_rows($vqg_rslt)>0)
				{
				$vqg_row=mysqli_fetch_array($vqg_rslt);
				
				$included_campaigns=trim(preg_replace('/\s\-$/', '', $vqg_row["included_campaigns"]));
				$included_campaigns_array=explode(" ", $included_campaigns);
				$included_campaigns_array=RemoveEmptyArrayStrings($included_campaigns_array);
				$included_campaigns_ct=count($included_campaigns_array);
				$included_campaigns_clause="and campaign_id in ('".preg_replace('/\s/', "', '", $included_campaigns)."')";

				$included_inbound_groups=trim(preg_replace('/\s\-$/', '', $vqg_row["included_inbound_groups"]));
				$included_inbound_groups_array=explode(" ", $included_inbound_groups);
				$included_inbound_groups_array=RemoveEmptyArrayStrings($included_inbound_groups_array);
				$included_inbound_groups_ct=count($included_inbound_groups_array);
				$included_inbound_groups_clause="and group_id in ('".preg_replace('/\s/', "', '", $included_inbound_groups)."')";
				$where_included_inbound_groups_clause="where group_id in ('".preg_replace('/\s/', "', '", $included_inbound_groups)."')";
				}
			else
				{
				$included_campaigns_array=array();
				$included_inbound_groups_array=array();	
				}

			$atomic_queue_str="";

			$atomic_queue_campaigns_str="";
			$campaign_id_stmt="select campaign_id, campaign_name from vicidial_campaigns where campaign_id is not null $included_campaigns_clause order by campaign_id"; # $LOGallowed_campaignsSQL, removed for now per Matt's assurances
			$campaign_id_rslt=mysql_to_mysqli($campaign_id_stmt, $link);
			while($campaign_id_row=mysqli_fetch_array($campaign_id_rslt))
				{
				$atomic_queue_str.=$campaign_id_row["campaign_name"];
				$atomic_queue_str.=" <i>[".$campaign_id_row["campaign_id"]."]</i>,";
				$atomic_queue_campaigns_str.="'$campaign_id_row[campaign_id]', ";
				}
			$atomic_queue_campaigns_str=preg_replace('/, $/', '', $atomic_queue_campaigns_str);
			$and_atomic_queue_campaigns_clause="and campaign_id in (".$atomic_queue_campaigns_str.")";

			$atomic_queue_ingroups_str="";
			$ingroups_id_stmt="select group_id, group_name from vicidial_inbound_groups $where_included_inbound_groups_clause"; #where group_id in ('".implode("', '", $allowed_ingroups_array)."') $included_inbound_groups_clause
			$ingroups_id_rslt=mysql_to_mysqli($ingroups_id_stmt, $link);
			while($ingroups_id_row=mysqli_fetch_array($ingroups_id_rslt))
				{
				$atomic_queue_str.=$ingroups_id_row["group_name"];
				$atomic_queue_str.=" <i>[".$ingroups_id_row["group_id"]."]</i>,";
				$atomic_queue_ingroups_str.="'$ingroups_id_row[group_id]', ";
				}
			$atomic_queue_ingroups_str=preg_replace('/, $/', '', $atomic_queue_ingroups_str);
			$and_atomic_queue_ingroups_clause="and campaign_id in (".$atomic_queue_ingroups_str.")";
			$atomic_queue_str=preg_replace('/,$/', '', $atomic_queue_str);

			$inbound_only_agents_array=array();
			if (count($included_campaigns_array)==0 && count($included_inbound_groups_array)>0) # Need to get list of users for user log table/vicidial agent log table
				{
				$closer_campaigns_SQL="and (";
				for ($q=0; $q<count($included_inbound_groups_array); $q++)
					{
					$closer_campaigns_SQL.="closer_campaigns like '% ".$included_inbound_groups_array[$q]." %' OR ";
					}
				$closer_campaigns_SQL=preg_replace('/ OR $/', "", $closer_campaigns_SQL).")";
				$vucl_stmt="select * from vicidial_user_closer_log $where_event_date_SQL $closer_campaigns_SQL";
				$vucl_rslt=mysql_to_mysqli($vucl_stmt, $link);
				while ($vucl_row=mysqli_fetch_array($vucl_rslt))
					{
					if (!in_array($vucl_row["user"], $inbound_only_agents_array))
						{
						$inbound_only_agents_array[]=$vucl_row["user"];
						}
					}
				$and_inbound_only_agents_clause=" and user in ('".implode("', '", $inbound_only_agents_array)."') ";
				}			

			# Ok to have campaign_id='' here because it will filter out outbound
			$vicidial_log_SQL.="$and_atomic_queue_campaigns_clause";
			$vicidial_closer_log_SQL.="$and_atomic_queue_ingroups_clause";

			# If no campaigns are involved in the queue group, we need to use the $inbound_agents_only_array
			if (count($included_campaigns_array)>0 || count($inbound_only_agents_array)==0)
				{
				$vicidial_user_log_SQL.="$and_atomic_queue_campaigns_clause";
				$vicidial_agent_log_SQL.="$and_atomic_queue_campaigns_clause";
				}
			else
				{
				$vicidial_user_log_SQL.="$and_inbound_only_agents_clause";
				$vicidial_agent_log_SQL.="$and_inbound_only_agents_clause";
				}

			
			# $HTML_output.="<!--\n ($vqg_stmt)\n ($campaign_id_stmt)\n ($ingroups_id_stmt)\n ($vicidial_user_log_SQL)\n ($vucl_stmt) \n -->\n";

			$total_calls=0; $total_answered_calls=0; $total_unanswered_calls=0;
			# select call_date, call_date+INTERVAL (length_in_sec) SECOND as end_date, if(call_date+INTERVAL (length_in_sec) SECOND<='2021-01-31 23:59:59', '1', '0') as within_interval, campaign_id, user, length_in_sec, '0' as queue_seconds, 'O' as direction, '1' as queue_position, term_reason, comments, status, uniqueid From vicidial_log where call_date>='2021-01-01 00:00:00' and call_date<='2021-01-31 23:59:59'and campaign_id in ('') and time(call_date)>='00:00:00'  and time(call_date)<='23:59:59'  and phone_number!=''  and user!='VDAD' UNION 
			# select call_date, call_date+INTERVAL (length_in_sec) SECOND as end_date, if(call_date+INTERVAL (length_in_sec) SECOND<='2021-01-31 23:59:59', '1', '0') as within_interval, campaign_id, user, if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds) as length_in_sec, if(comments='EMAIL', '0', queue_seconds) as queue_seconds, 'I' as direction, queue_position, term_reason, comments, status, uniqueid From vicidial_closer_log where call_date>='2021-01-01 00:00:00' and call_date<='2021-01-31 23:59:59'and campaign_id in ('512101v', '') and time(call_date)>='00:00:00'  and time(call_date)<='23:59:59'  and (user!='VDCL')

			# $calls_stmt="select user, status, uniqueid From vicidial_log $vicidial_log_SQL $exc_addtl_statuses UNION select user, status, uniqueid From vicidial_closer_log $vicidial_closer_log_SQL $exc_addtl_statuses";
			
			# $calls_stmt="select monthname(call_date) as qmname, date_format(call_date, '%m') as qmonth, year(call_date) as qyear, sum(if(user in ('VDAD', 'VDCL'), 1, 0)) as unanswered_calls, count(*) as total_calls, sum(length_in_sec) as total_handle_time, 0 as total_wait_time, -1 as sla_calls FROM vicidial_log $vicidial_log_SQL $exc_addtl_statuses group by qmonth, qyear UNION select monthname(call_date) as qmname, date_format(call_date, '%m') as qmonth, year(call_date) as qyear, sum(if(user in ('VDAD', 'VDCL'), 1, 0)) as unanswered_calls, count(*) as total_calls, sum(length_in_sec-queue_seconds) as total_handle_time, sum(if(user in ('VDAD', 'VDCL') or comments='EMAIL', 0, queue_seconds)) as total_wait_time, sum(if(queue_seconds<=60, 1, 0)) as sla_calls FROM vicidial_closer_log $vicidial_closer_log_SQL $exc_addtl_statuses group by qmonth, qyear";

			$calls_stmt_outb=($atomic_queue_campaigns_str!="" ? "select monthname(call_date) as qmname, date_format(call_date, '%m') as qmonth, year(call_date) as qyear, sum(if(user in ('VDAD', 'VDCL'), 1, 0)) as unanswered_calls, count(*) as total_calls, sum(length_in_sec) as total_handle_time, 0 as total_wait_time, -1 as sla_calls FROM vicidial_log $vicidial_log_SQL $exc_addtl_statuses group by qmonth, qyear" : "");

			$calls_stmt_inb=($atomic_queue_ingroups_str!="" ? "select monthname(call_date) as qmname, date_format(call_date, '%m') as qmonth, year(call_date) as qyear, sum(if(user in ('VDAD', 'VDCL'), 1, 0)) as unanswered_calls, count(*) as total_calls, sum(if(user in ('VDAD', 'VDCL'), 0, if(comments='EMAIL', length_in_sec, if(length_in_sec-queue_seconds<0, 0, length_in_sec-queue_seconds)))) as total_handle_time, sum(if(user in ('VDAD', 'VDCL') or comments='EMAIL', 0, queue_seconds)) as total_wait_time, sum(if(queue_seconds<=60 and user NOT in ('VDAD', 'VDCL'), 1, 0)) as sla_calls FROM vicidial_closer_log $vicidial_closer_log_SQL $exc_addtl_statuses group by qmonth, qyear" : "");

			$calls_stmt=$calls_stmt_outb.(strlen($calls_stmt_outb)>0 && strlen($calls_stmt_inb)>0 ? " UNION " : "").$calls_stmt_inb;

			# $HTML_output.="<!--\n CALLS: $calls_stmt\n -->\n";
			$calls_rslt=mysql_to_mysqli($calls_stmt, $link);
			while ($calls_row=mysqli_fetch_array($calls_rslt)) 
				{
				$month=$calls_row["qmonth"];
				$month_name=$calls_row["qmname"];
				$total_calls=$calls_row["total_calls"];
				$total_abandons=$calls_row["unanswered_calls"];
				$total_wait_time=$calls_row["total_wait_time"];
				$total_handle_time=$calls_row["total_handle_time"];
				$sla_calls=$calls_row["sla_calls"];
				$total_answers=$total_calls-$total_abandons;

				# $average_wait_time=sprintf("%.1f", MathZDC($total_wait_time, $total_answers)); # Total calls instead?
				# $abandons_percentage=sprintf("%.1f", MathZDC((100*$total_abandons), $total_calls));

				# $key=$current_year.$month;
				$key=$month_name;
				$comparison_rslts["$current_queue_group_name"]["$key"]["offered"]+=$total_calls;
				$comparison_rslts["$current_queue_group_name"]["$key"]["answered"]+=$total_answers;
				$comparison_rslts["$current_queue_group_name"]["$key"]["total_wait_time"]+=$total_wait_time;
				$comparison_rslts["$current_queue_group_name"]["$key"]["abandoned"]+=$total_abandons;
				$comparison_rslts["$current_queue_group_name"]["$key"]["total_handle_time"]+=$total_handle_time;
				# $comparison_rslts["$current_queue_group_name"]["$key"]["abandoned_pct"]=$abandons_percentage;
				if ($sla_calls>=0)
					{
					$comparison_rslts["$current_queue_group_name"]["$key"]["answered_inbound_calls"]+=$total_answers;
					$comparison_rslts["$current_queue_group_name"]["$key"]["sla_calls"]+=$sla_calls;
					}
				}

			$sla_percent=sprintf("%.1f", MathZDC((100*$sla_calls), $total_answers)); # Total calls instead?
			$average_handle_time=sprintf("%.1f", MathZDC($total_handle_time, $total_answers)); # Total calls instead?
			$average_wait_time=sprintf("%.1f", MathZDC($total_wait_time, $total_answers)); # Total calls instead?
			$abandons_percentage=sprintf("%.1f", MathZDC((100*$total_abandons), $total_calls));


			#### OCCUPANCY ####
$agent_sessions_array=array();
$vicidial_user_stmt="select distinct user from vicidial_user_log $vicidial_user_log_SQL and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') order by user asc";
if ($DB) {$HTML_output.="<B>$vicidial_user_stmt</B>";}
$vicidial_user_rslt=mysql_to_mysqli($vicidial_user_stmt, $link);
# 			$HTML_output.="<!--\n ($vicidial_user_stmt) \n";

while ($vicidial_user_row=mysqli_fetch_row($vicidial_user_rslt))
	{
	$vicidial_user=$vicidial_user_row[0];

	$event_date_clause_apx="";
	if (!$overnight_agents)
		{
		$init_start_stmt="select min(event_date) from vicidial_user_log $vicidial_user_log_SQL and event='LOGIN' and user='$vicidial_user'";
		$init_start_rslt=mysql_to_mysqli($init_start_stmt, $link);
		if (mysqli_num_rows($init_start_rslt)>0)
			{
			$isr_row=mysqli_fetch_row($init_start_rslt);
			$event_date_clause_apx=" and event_date>='$isr_row[0]' ";
			}
		}


	$session_stmt="select *,if(event='LOGOUT' or event='TIMEOUTLOGOUT', 1, 0) as priority from vicidial_user_log $vicidial_user_log_SQL $event_date_clause_apx and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') and user='$vicidial_user' order by event_date, priority asc";
	if ($DB) {$HTML_output.="<B>$session_stmt</B>";}
	# $HTML_output.=" ($session_stmt) \n";
	# print "$session_stmt<BR>\n";
	$session_rslt=mysql_to_mysqli($session_stmt, $link);
	$prev_date="";
	$start_event_date="";

	### ITERATE THROUGH ALL THE AGENTS SESSIONS MATCHING THE REPORT CRITERIA
	$row_no=0; $agent_logged_in=0;
	while ($session_row=mysqli_fetch_array($session_rslt))
		{
		# Need this for when the report runs with a start date right in the middle of agents logged in
		$original_session_start_date="$start_date $start_time";
		$original_session_cutoff_date="$start_date 00:00:00";
		$original_session_cutoff_eod="$start_date 23:59:59";

		$full_name=$fullname_info["$session_row[user]"];
		$user=$session_row["user"];
		$campaign_id=$session_row["campaign_id"];
		$user_group=$session_row["user_group"];
		$extension=$session_row["extension"];
		$event=$session_row["event"];
		$notes=$session_row["extension"];
		$event_epoch=$session_row["event_epoch"];
		$event_time=$session_row["event_date"];
		$server_ip=$session_row["server_ip"];
		$event_date=substr($event_time, 0, 10);
		if (!$prev_date) {$prev_date=$event_date;}

		# $location=GetUserLocation($user);

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

					$agent_sessions_array["$user"]["$start_event_date"]["full_name"]=$full_name;
#na					$agent_sessions_array["$user"]["$start_event_date"]["user_group"]=$user_group;
					$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]=$override_event_epoch;
#na					$agent_sessions_array["$user"]["$start_event_date"]["extension"]=$extension;
#na					$agent_sessions_array["$user"]["$start_event_date"]["server_ip"]=$server_ip;
					$agent_sessions_array["$user"]["$start_event_date"]["login_notes"]=$login_notes;

					$agent_logged_in=1;
					}
				}
			}
		$row_no++; # Keeps from doing an override check

		if ($event=="LOGIN")
			{

			if ($agent_logged_in==0)
				{
				$start_event_date=$event_time;
				$agent_sessions_array["$user"]["$start_event_date"]["full_name"]=$full_name;
#na				$agent_sessions_array["$user"]["$start_event_date"]["user_group"]=$user_group;
				$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]=$event_epoch;
#na				$agent_sessions_array["$user"]["$start_event_date"]["extension"]=$extension;
#na				$agent_sessions_array["$user"]["$start_event_date"]["server_ip"]=$server_ip;
				}

			$agent_logged_in=1;
			}

		if (preg_match('/LOGOUT/', $event)) #  && $agent_logged_in==1, removed this due to back to back logouts (see user 105110 for 2022-01-10)
			{
			# 2/23/22 - agent page when back-to-back logouts, second logout is ignored, see Dougwanna Brown 2/15/22-2/22/22. 
			$agent_sessions_array["$user"]["$start_event_date"]["end_hour"]=$event_epoch;
#na			$agent_sessions_array["$user"]["$start_event_date"]["end_date"]=$event_time;
			$agent_sessions_array["$user"]["$start_event_date"]["duration"]=($agent_sessions_array["$user"]["$start_event_date"]["end_hour"]-$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]);
			
			if ($agent_logged_in==1)
				{
				$agent_sessions_array["$user"]["$start_event_date"]["notes"]="NORMAL LOGOUT";
				}
			else
				{
				$agent_sessions_array["$user"]["$start_event_date"]["notes"]="REPEAT LOGOUT";
				}

			$agent_logged_in=0;
			}
		
		$override_login=0;
		$prev_date=$event_date;
		$prev_event=$event;
		$prev_campaign_id=$campaign_id;
		}

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
		$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]=$eod_epoch_row[0];
		$agent_sessions_array["$user"]["$start_event_date"]["full_name"]=$full_name;
#na		$agent_sessions_array["$user"]["$start_event_date"]["user_group"]=$user_group;
		$agent_sessions_array["$user"]["$start_event_date"]["end_hour"]=$eod_epoch_row[1];
#na		$agent_sessions_array["$user"]["$start_event_date"]["end_date"]=$event_date_eod;
		$agent_sessions_array["$user"]["$start_event_date"]["duration"]=($agent_sessions_array["$user"]["$start_event_date"]["end_hour"]-$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]);
		$agent_sessions_array["$user"]["$start_event_date"]["notes"]=$notes;

		$agent_logged_in=0;
		}
	}
# $HTML_output.="-->\n";


# Do round of calculations for agents logged in
foreach ($agent_sessions_array as $user => $sessions)
	{
	# Purge agents with no start_hours (this is remote agents)
	if (count(array_count_values(array_column($sessions,'start_hour')))==0)
		{
		unset($agent_sessions_array["$user"]);
		}

	foreach($sessions as $start_event_date => $activity)
		{
		if ($agent_sessions_array["$user"]["$start_event_date"]["start_hour"]) #
			{
			# print_r($activity);
			$full_name=$agent_sessions_array["$user"]["$start_event_date"]["full_name"];

			$pause_stmt="select sec_to_time(sum(pause_sec)), sum(pause_sec), count(*) from vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and pause_sec>0";
			$pause_rslt=mysql_to_mysqli($pause_stmt, $link);
			$pause_row=mysqli_fetch_row($pause_rslt);
#na			$agent_sessions_array["$user"]["$start_event_date"]["pauses"]=$pause_row[2];
#na			$agent_sessions_array["$user"]["$start_event_date"]["pause_time"]=$pause_row[0];		
			$agent_sessions_array["$user"]["$start_event_date"]["pause_sec"]=$pause_row[1];		
#na			$agent_sessions_array["$user"]["$start_event_date"]["billable"]=0;  ## !!!!!!! THIS IS BILLABLE --> PAUSE <-- ONLY !!!!!!!!!!!
#na			$agent_sessions_array["$user"]["$start_event_date"]["calls"]=0;
			$agent_sessions_array["$user"]["$start_event_date"]["talk_sec"]=0;
#na			$agent_sessions_array["$user"]["$start_event_date"]["wait_sec"]=0;
#na			$agent_sessions_array["$user"]["$start_event_date"]["dispo_sec"]=0;

#na			$user_group=$agent_sessions_array["$user"]["$start_event_date"]["user_group"];
			# $location=GetUserLocation($user);

/* poached from AGENTS, not necessary
			# GET PAUSE TYPES AND PAUSE TIMES
			$billable_stmt="select campaign_id, sub_status, if(sum(pause_sec) is null, 0, sum(pause_sec)) as total_pause, count(*) as pauses From vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and pause_sec>0 group by campaign_id, sub_status order by campaign_id, sub_status";
			$billable_rslt=mysql_to_mysqli($billable_stmt, $link);
			while ($billable_row=mysqli_fetch_array($billable_rslt))
				{
				$campaign_id=$billable_row["campaign_id"];
				$sub_status=$billable_row["sub_status"];

				if (in_array($sub_status, $billable_pause_codes["$campaign_id"]) || in_array($sub_status, $billable_pause_codes["SYSTEM"]))
					{
					$agent_sessions_array["$user"]["$start_event_date"]["billable"]+=$billable_row["total_pause"];		
					}
				}
*/

			## GET CALLS, TALK AND WAIT TIME
			# 02/21/2022 - need to separate for outbound-only and inbound-only
			$call_stmt_outb="";
			if ($atomic_queue_campaigns_str!="")
				{
				$call_stmt_outb="select if(sum(talk_sec) is null, 0, sum(talk_sec)) as total_talk, if (sum(wait_sec) is null, 0, sum(wait_sec)) as total_wait, if (sum(dispo_sec) is null, 0, sum(dispo_sec)) as total_dispo, status, campaign_id, count(*) as calls, 'OUTBOUND' as direction From vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and lead_id>0 and lead_id is not null and talk_epoch is not null and comments!='INBOUND' $and_atomic_queue_campaigns_clause group by status, campaign_id ";
				}
			
			$call_stmt_inb="";
			if ($atomic_queue_ingroups_str!="")
				{
				$vcl_uniqueid_stmt="select uniqueid, status, length_in_sec-queue_seconds  from vicidial_closer_log $vicidial_closer_log_SQL and call_date+INTERVAL ceil(queue_seconds) SECOND>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and call_date+INTERVAL ceil(queue_seconds) SECOND<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and status!='AFTHRS'";
			
				$vcl_uniqueid_stmt.=" and call_date>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].")-INTERVAL 24 HOUR and call_date<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].")+INTERVAL 24 HOUR";
				# $DB=1;
				if ($DB) {print "\n\n<!--\n $vcl_uniqueid_stmt \n//-->\n\n";}
				
				$vcl_uniqueid_rslt=mysql_to_mysqli($vcl_uniqueid_stmt, $link);
				$val_uniqueid_array=array();
				while ($vcl_uniqueid_row=mysqli_fetch_row($vcl_uniqueid_rslt))
					{
					array_push($val_uniqueid_array, $vcl_uniqueid_row[0]);
					$status=$vcl_uniqueid_row[1];
					$talk_sec=$vcl_uniqueid_row[2];
					
#na					$agent_sessions_array["$user"]["$start_event_date"]["calls"]++;
					$agent_sessions_array["$user"]["$start_event_date"]["talk_sec"]+=$talk_sec;		

#na					$user_groups_array["$user_group"]["calls"]++;
#na					$user_groups_array["$user_group"]["talk_sec"]+=$talk_sec;

#na					$locations_array["$location"]["calls"]++;
#na					$locations_array["$location"]["talk_sec"]+=$talk_sec;

/* poached from AGENTS, not necessary
					# Moved from below due to agent log not carrying uniqueid over - stopgap because this may still have gaps in the talk/dispo time
					# due to uniqueid not carrying over
					# INBOUND CALL COUNTS FOR USERS AND USER GROUPS MUST BE TALLIED HERE BECAUSE THE CORRELATION BETWEEN
					if (in_array($status, $sale_array["$campaign_id"]) || in_array($status, $sale_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["sales"]++;		
						}
					if (in_array($status, $human_ans_array["$campaign_id"]) || in_array($status, $human_ans_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["human_answered"]++;		
						}
					if (in_array($status, $contact_array["$campaign_id"]) || in_array($status, $contact_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["contacts"]++;		
						}
*/					
					}


				$call_stmt_inb="select if(sum(talk_sec) is null, 0, sum(talk_sec)) as total_talk, if (sum(wait_sec) is null, 0, sum(wait_sec)) as total_wait, if (sum(dispo_sec) is null, 0, sum(dispo_sec)) as total_dispo, status, campaign_id, count(*) as calls, 'INBOUND' as direction From vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and lead_id>0 and lead_id is not null and talk_epoch is not null and comments='INBOUND' and uniqueid in ('".implode("', '", $val_uniqueid_array)."') group by status, campaign_id ";
				}

			$call_stmt=$call_stmt_outb.($call_stmt_outb!="" && $call_stmt_inb!="" ? " UNION " : "").$call_stmt_inb;
			
			$call_rslt=mysql_to_mysqli($call_stmt, $link);
			while($call_row=mysqli_fetch_array($call_rslt))
				{
				$call_direction=$call_row["direction"];

				# Commented out IN INBOUND because counts were not accurate, moved up
				if ($call_direction=="OUTBOUND")
					{
#na					$agent_sessions_array["$user"]["$start_event_date"]["calls"]+=$call_row["calls"];		
					$agent_sessions_array["$user"]["$start_event_date"]["talk_sec"]+=$call_row["total_talk"];		
					}
/* poached from AGENTS, not necessary
				$agent_sessions_array["$user"]["$start_event_date"]["wait_sec"]+=$call_row["total_wait"];		
				$agent_sessions_array["$user"]["$start_event_date"]["dispo_sec"]+=$call_row["total_dispo"];		

				$campaign_id=$call_row["campaign_id"];
				$status=$call_row["status"];
				$agent_sessions_array["$user"]["$start_event_date"]["sales"]+=0;		
				$agent_sessions_array["$user"]["$start_event_date"]["human_answered"]+=0;		
				$agent_sessions_array["$user"]["$start_event_date"]["contacts"]+=0;		

				# Moved up to atomic_queue_ingroups_str FOR INBOUND to try and ensure accurate call/status counts
				if ($call_direction=="OUTBOUND")
					{
					if (in_array($status, $sale_array["$campaign_id"]) || in_array($status, $sale_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["sales"]+=$call_row["calls"];		
						}
					if (in_array($status, $human_ans_array["$campaign_id"]) || in_array($status, $human_ans_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["human_answered"]+=$call_row["calls"];		
						}
					if (in_array($status, $contact_array["$campaign_id"]) || in_array($status, $contact_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["contacts"]+=$call_row["calls"];		
						}
					}
*/
				}


/*  poached from AGENTS, not necessary
			## POPULATE AGENT HOUR ARRAY
			$session_start_time=$agent_sessions_array["$user"]["$start_event_date"]["start_hour"];
			$session_end_time=$agent_sessions_array["$user"]["$start_event_date"]["end_hour"];

			while ($session_start_time<$session_end_time)
				{
				$hour_stmt="select date_format(from_unixtime($session_start_time), '%k'), date_format(from_unixtime($session_start_time), '%Y-%m-%d')";
				$hour_rslt=mysql_to_mysqli($hour_stmt, $link);
				$hour_row=mysqli_fetch_row($hour_rslt);
				$hour=$hour_row[0];
				$array_date=$hour_row[1];
				$agent_date_hour_array["$array_date"]["$hour"]["$user"]=1;

				$time_in_interval=(3600-($session_start_time%3600));
				if (($session_start_time+$time_in_interval)>$session_end_time)
					{
					$time_in_interval=$session_end_time-$session_start_time;
					}
				$session_start_time+=$time_in_interval;
				}
*/

/*  poached from AGENTS, not necessary
			## REMOVE NON-BILLABLE TIME FROM HOUR_ARRAY
			$nonbill_stmt="select sub_status, pause_epoch, pause_sec, event_time from vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and pause_sec>0 and sub_status is not null and sub_status not in ('BLANK')";
			$nonbill_rslt=mysql_to_mysqli($nonbill_stmt, $link);
			while ($nb_row=mysqli_fetch_row($nonbill_rslt))
				{
				if (!in_array($nb_row[0], $billable_pause_codes["$campaign_id"]) && !in_array($nb_row[0], $billable_pause_codes["SYSTEM"]))
					{
					$pause_start_time=$nb_row[1];
					$pause_end_time=$nb_row[1]+$nb_row[2];
					# print "$user - $nb_row[3] => $nb_row[0], $pause_start_time for $nb_row[2] seconds\n";
					while ($pause_start_time<$pause_end_time)
						{
						$hour_stmt="select date_format(from_unixtime($pause_start_time), '%k'), date_format(from_unixtime($pause_start_time), '%Y-%m-%d')";
						$hour_rslt=mysql_to_mysqli($hour_stmt, $link);
						$hour_row=mysqli_fetch_row($hour_rslt);
						$hour=$hour_row[0];

						$time_in_interval=(3600-($pause_start_time%3600));
						if (($pause_start_time+$time_in_interval)>$pause_end_time)
							{
							$time_in_interval=$pause_end_time-$pause_start_time;
							}
						$pause_start_time+=$time_in_interval;
						}
					}
				}
*/

/*  poached from AGENTS, not necessary
			## GET TOTAL TIME AND MAX TIMES FOR HEADER REPORT
			$total_agent_time+=$agent_sessions_array["$user"]["$start_event_date"]["duration"];
			if ($agent_sessions_array["$user"]["$start_event_date"]["duration"]<$min_agent_time || !$min_agent_time)
				{
				$min_agent_time=$agent_sessions_array["$user"]["$start_event_date"]["duration"];
				}

			if ($agent_sessions_array["$user"]["$start_event_date"]["duration"]>$max_agent_time || !$max_agent_time)
				{
				$max_agent_time=$agent_sessions_array["$user"]["$start_event_date"]["duration"];
				}

*/
			}
		}
	}

# print_r($agent_sessions_array);

$HTML_output.="<!--\n";
foreach ($agent_sessions_array as $user => $sessions)
	{	
	# print_r($sessions);
	foreach($sessions as $interval_date => $session)
		{
		$key=date("F", strtotime("$interval_date"));
		$HTML_output.=" $interval_date - $key\n";
		$comparison_rslts["$current_queue_group_name"]["$key"]["duration"]+=$session["duration"];
		$comparison_rslts["$current_queue_group_name"]["$key"]["pause_sec"]+=$session["pause_sec"];
		$comparison_rslts["$current_queue_group_name"]["$key"]["talk_sec"]+=$session["talk_sec"];
		}
	}
$HTML_output.="//-->\n";
			###################
	

			# $occupancy=sprintf("%.1f", 100*MathZDC($total_talk_time, ($total_duration-$total_pause_time)));

			# $svc_lvl_stmt="select sum(if(queue_seconds<=60, 1, 0)) as within_60 from vicidial_closer_log $vicidial_closer_log_SQL and (user!='VDCL')"; #  and campaign_id in ($ingroup_str) and user in ('VDCL', $user_str)
			# $svc_lvl_rslt=mysqli_query($link, $svc_lvl_stmt);

/*
			while ($calls_row=mysqli_fetch_array($calls_rslt)) 
				{
				$total_calls++;
				if (preg_match('/VDAD|VDCL/', $calls_row["user"])) #  && preg_match('/^DROP$|TIMEOT|WAITTO|NANQUE/', $calls_row["status"])
					{
					$total_unanswered_calls++;
					}
				else
					{
					$total_answered_calls++;
					}
				}
*/
			}

		$rpt_sections=array("Offered", "Answered", "Average Speed to Answer", "Average Handle Time", "Abandoned", "Abandoned %", "Occupancy", "SLA"); # 
		$rpt_styles=array("offered_td", "answered_td", "avg_speed_td", "avg_handle_td", "abandoned_td", "abandoned_pct_td", "occupancy_td", "sla_td"); # 

		if ($y>0) {$HTML_output.="<BR><HR><BR>";}
		$HTML_output.="<table id='report_table'>";
		$HTML_output.="<tr>";
		$HTML_output.="<th class='comparison_th'>PRS Onshore and Offshore</th>";
		$HTML_output.="<th class='comparison_th'>$current_year</th>";
		for ($m=1; $m<=12; $m++)
			{
		#	$dateObj   = DateTime::createFromFormat('!m', $m);
		#	$monthName = $dateObj->format('F');
			$monthName = date("F", mktime(0, 0, 0, $m, 1, 2022));
			$HTML_output.="<th class='comparison_th'>$monthName</th>";
			}
		$HTML_output.="<th class='comparison_th'>Total</th>";
		$HTML_output.="</tr>";

		for ($i=0; $i<count($rpt_sections); $i++)
			{

			$j=0;
			foreach ($comparison_rslts as $queue => $data)
				{
				
				$HTML_output.="<tr class='".$rpt_styles[$i]."'>";
				if ($j==0) 
					{
					$HTML_output.="<td rowspan='".($rpt_sections[$i]=="SLA" ? ($section_rowspan+1) : $section_rowspan)."'>".$rpt_sections[$i]."</td>";
					if ($rpt_sections[$i]=="SLA")
						{
						$HTML_output.="<td>Goal</td>";
						for ($m=1; $m<=12; $m++)
							{
							$HTML_output.="<td align='center'>".(in_array($m, $comparison_months) ? "80%" : "-")."</td>";
							}
						$HTML_output.="<td align='center'>80%</td>";
						$HTML_output.="</tr>";
						$HTML_output.="<tr class='".$rpt_styles[$i]."'>";
						}
					}

				$HTML_output.="<td>".$queue."</td>";
				$total_value=0;
				$total_numerator=0;
				$total_denominator=0;
				for ($m=1; $m<=12; $m++)
					{
					#$dateObj   = DateTime::createFromFormat('!m', $m);
					#$monthName = $dateObj->format('F');
					$monthName = date("F", mktime(0, 0, 0, $m, 1, 2022));
				
					$value="";
					if ($data["$monthName"])
						{
						switch($rpt_sections[$i])
							{
							case "Offered":
								$value=$data["$monthName"]["offered"];
								$total_value+=$value;
								break;
							case "Answered":
								$value=$data["$monthName"]["answered"];
								$total_value+=$value;
								break;
							case "Average Speed to Answer":
								$value=sprintf("%.1f", MathZDC($data["$monthName"]["total_wait_time"], $data["$monthName"]["answered"]))."s";
								$total_numerator+=$data["$monthName"]["total_wait_time"];
								$total_denominator+=$data["$monthName"]["answered"];
								break;
							case "Average Handle Time":
								$value=sprintf("%.1f", MathZDC($data["$monthName"]["total_handle_time"], $data["$monthName"]["answered"]))."s";
								$total_numerator+=$data["$monthName"]["total_handle_time"];
								$total_denominator+=$data["$monthName"]["answered"];
								break;
							case "Abandoned":
								$value=$data["$monthName"]["abandoned"];
								$total_value+=$value;
								break;
							case "Abandoned %":
								$value=sprintf("%.1f", MathZDC((100*$data["$monthName"]["abandoned"]), $data["$monthName"]["offered"]))."%";
								$total_numerator+=$data["$monthName"]["abandoned"];
								$total_denominator+=$data["$monthName"]["offered"];
								break;
							case "Occupancy":
								$value=sprintf("%.1f", 100*MathZDC($data["$monthName"]["talk_sec"], ($data["$monthName"]["duration"]-$data["$monthName"]["pause_sec"])))."%";
								$total_numerator+=$data["$monthName"]["talk_sec"];
								$total_denominator+=($data["$monthName"]["duration"]-$data["$monthName"]["pause_sec"]);
								# $total_value+=$value;
								break;
							case "SLA":
								$value=sprintf("%.1f", MathZDC((100*$data["$monthName"]["sla_calls"]), $data["$monthName"]["answered_inbound_calls"]))."%";
								$total_numerator+=$data["$monthName"]["sla_calls"];
								$total_denominator+=$data["$monthName"]["answered_inbound_calls"];
								break;
							}
						}
					$HTML_output.="<td align='center'>".($value ? $value : "-")."</td>";
					}

				switch($rpt_sections[$i])
					{
					case "Offered":
					case "Answered":
					case "Abandoned":
						$HTML_output.="<td align='center'>".($total_value ? $total_value : "-")."</td>";
						break;
					case "Average Speed to Answer":
					case "Average Handle Time":
						$total_value=sprintf("%.1f", MathZDC($total_numerator, $total_denominator))."s";
						$HTML_output.="<td align='center'>$total_value</td>";
						break;
					case "Abandoned %":
					case "Occupancy":
					case "SLA":
						$total_value=sprintf("%.1f", MathZDC((100*$total_numerator), $total_denominator))."%";
						$HTML_output.="<td align='center'>$total_value</td>";
						break;
					}

				$HTML_output.="</tr>";

				$j++;
				}
			# $HTML_output.="<td>".$rpt_sections[$i]."</td>";
			
		
			}
		$HTML_output.="</table>";

		### SURVEY SECTION
		$HTML_output.="<BR><table id='report_table'>";
		$HTML_output.="<tr>";
		$HTML_output.="<th class='comparison_th'>UHC Onshore and Offshore</th>";
		$HTML_output.="<th class='comparison_th'>$current_year</th>";
		$HTML_output.="<th class='comparison_th'>Survey Question</th>";
		for ($i=1; $i<=12; $i++)
			{
			#$dateObj   = DateTime::createFromFormat('!m', $i);
			#$monthName = $dateObj->format('F');
			$monthName = date("F", mktime(0, 0, 0, $i, 1, 2022));
			$HTML_output.="<th class='comparison_th'>$monthName</th>";
			}
		$HTML_output.="<th class='comparison_th'>Total</th>";
		$HTML_output.="</tr>";

		$survey_sections=array("NPS Survey Average Scores");
		$survey_styles=array("survey_td");
		$section_rowspan=count($survey_ivr_groups);

#$survey_ivr_groups=array("561402", "561403");
#$survey_ivr_group_names=array("UHC-Survey-NPS-iVR", "UHC-Survey-Overall-iVR");
		for ($i=0; $i<count($survey_sections); $i++)
			{
		
			$HTML_output.="<tr class='".$survey_styles[$i]."'>";
			if ($i==0) 
				{
				$HTML_output.="<td rowspan='".($survey_sections[$i]=="NPS Survey Average Scores" ? ($section_rowspan+1) : $section_rowspan)."'>".$survey_sections[$i]."</td>";
				if ($survey_sections[$i]=="NPS Survey Average Scores")
					{
					$HTML_output.="<td>Goal</td>";
					$HTML_output.="<td>&nbsp;</td>";
					for ($m=1; $m<=12; $m++)
						{
						$HTML_output.="<td align='center'>".(in_array($m, $comparison_months) ? "10" : "-")."</td>";
						}
					$HTML_output.="<td align='center'>10</td>";
					$HTML_output.="</tr>";
					$HTML_output.="<tr class='".$survey_styles[$i]."'>";
					$HTML_output.="<td rowspan='".$section_rowspan."'>Onshore + Offshore</td>";
					}
				}
			
			for ($j=0; $j<count($survey_ivr_groups); $j++)
				{
				$current_survey=$survey_ivr_groups[$j];
				$current_survey_name=$survey_ivr_group_names[$j];
				$HTML_output.="<td>".$current_survey." - ".$current_survey_name."</td>";
				for ($m=1; $m<=12; $m++)
					{
					#$dateObj   = DateTime::createFromFormat('!m', $m);
					#$monthName = $dateObj->format('F');
					$monthName = date("F", mktime(0, 0, 0, $m, 1, 2022));
					
					$value="";
					if (in_array($m, $comparison_months))
						{
						$survey_stmt="select avg(comment_b) from live_inbound_log where year(start_time)='$current_year' and month(start_time)='$m' and comment_b in ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10') and comment_d like '".$current_survey.">%'";
						$survey_rslt=mysql_to_mysqli($survey_stmt, $link);
						#	$HTML_output.=$survey_stmt;
						while ($survey_row=mysqli_fetch_array($survey_rslt)) 
							{
							$value=sprintf("%.2f", $survey_row[0]);
							}
						}
					$HTML_output.="<td align='center'>".($value ? $value : "-")."</td>";
					}
			
				$survey_stmt="select avg(comment_b) from live_inbound_log where year(start_time)='$current_year' and month(start_time) in (".implode(", ", $comparison_months).") and comment_b in ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10') and comment_d like '".$current_survey.">%'";
				$survey_rslt=mysql_to_mysqli($survey_stmt, $link);
				#	$HTML_output.=$survey_stmt;
				while ($survey_row=mysqli_fetch_array($survey_rslt)) 
					{
					$value=sprintf("%.2f", $survey_row[0]);
					}
				$HTML_output.="<td align='center'>".($value ? $value : "-")."</td>";
				
				$HTML_output.="</tr>";
				}
			}
		$HTML_output.="</table>";

		}

	}




# select user, status, uniqueid From vicidial_closer_log where call_date>='2021-01-01 00:00:00' and call_date<='2021-01-31 23:59:59'and campaign_id in ('512101v', '') and time(call_date)>='00:00:00'  and time(call_date)<='23:59:59'    and status not in ('AFTHRS', 'NANQUE', 'QUEUE', 'XFER') 

if ($DB) {$HTML_output.="<B>$calls_stmt</B>";}

if($calls_stmt)
	{
	$calls_rslt=mysqli_query($link, $calls_stmt);
	}


if (file_exists('options.php'))
	{
	require('options.php');
	}

$check_limit=2;

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$NFB = '<b><font size=6 face="courier">';
$NFE = '</font></b>';

/*
(These are the queues exactly as they are named in VERM):

 

PRS:

514915v_USA_Shared (Named as ‘Onshore’ in the report)
515915v_MLA_Shared (Named as ‘Offshore’ in the report)
001_ALL_USA_MLA_DED_IN (Named as ‘Onshore + Offshore’ in the report)

 

UHC:

512101v-USA-Ded-Inbound (Named as ‘Onshore’ in the report)
515101v-MLA-Ded-Inbound (Named as ‘Offshore’ in the report)
000001-ALL-USA-MLA-DED-IN (Named as ‘Onshore + Offshore’ in the report)

 

By default, all of the above queues will be displayed in the following table format every time a query is run for specific Years and/or Months:
*/
?>

<script language="Javascript">
function CheckboxLimit(box_group, box_id, max_boxes)
	{
	var checked_boxes=0;
	for (i = 0; i < document.getElementsByName(box_group).length; i++) 
		{
		if(document.getElementsByName(box_group)[i].checked == true)
			{
			checked_boxes++;
			}
		}
	if (checked_boxes>max_boxes)
		{
		document.getElementById(box_id).checked = false;
		// alert("You are not allowed to select more than two years for this report.");
		return false;
		}
	}

function SelectAll(box_group, master_box)
	{
	for (i = 0; i < document.getElementsByName(box_group).length; i++) 
		if(document.getElementById(master_box).checked == true)
			{
			document.getElementsByName(box_group)[i].checked = true;
			}
		else
			{
			document.getElementsByName(box_group)[i].checked = false;
			}
	}

function MultiSelectLimit(select_group, select_id, max_options) 
	{
	var fieldName = document.getElementById(select_group);
	var count = 0;

	for (var i=0, iLen=fieldName.options.length; i<iLen; i++)
		{
		fieldName.options[i].selected ? count++ : null;

		// Deselect the option.
		if (count > max_options) 
			{
			fieldName.options[i].selected = false;
			}
		}
	CountMonthsYears();
	}

function CountMonthsYears()
	{
	var months=0;
	var years=0;

	var year_menu = document.getElementById('comparison_years[]');
	for (var i=0; i<year_menu.options.length; i++) 
		{
		if (year_menu.options[i].selected) 
			{
			months++;
			}
		}

	var month_menu = document.getElementById('comparison_months[]');
	for (var i=0; i<month_menu.options.length; i++) 
		{
		if (month_menu.options[i].selected) 
			{
			years++;
			}
		}

	if (years>0 && months>0)
		{
		document.getElementById('estimated_time').innerHTML=(years*months*45)+" to "+(years*months*60);
		}
	}

function RunComparisonReport(realtime_override) 
	{
	var full_report_var_str="";
	if (realtime_override) {document.location.href='/vicidial/realtime_report.php'; exit;}

/*
	var queue_menu = document.getElementById('vicidial_queue_groups[]');
	for (var i=0; i<queue_menu.options.length; i++) 
		{
		if (queue_menu.options[i].selected) 
			{
			full_report_var_str+="&queue_groups[]="+queue_menu.options[i].value;
			}
		}
*/

	var year_menu = document.getElementById('comparison_years[]');
	for (var i=0; i<year_menu.options.length; i++) 
		{
		if (year_menu.options[i].selected) 
			{
			full_report_var_str+="&comparison_years[]="+year_menu.options[i].value;
			}
		}

	var month_menu = document.getElementById('comparison_months[]');
	for (var i=0; i<month_menu.options.length; i++) 
		{
		if (month_menu.options[i].selected) 
			{
			full_report_var_str+="&comparison_months[]="+month_menu.options[i].value;
			}
		}

	// alert(full_report_var_str);
	document.location.href=document.forms[0].action+"?submit_report=1&"+full_report_var_str;
	}
</script>

<html>
<head>
<link rel="stylesheet" type="text/css" href="VERM_stylesheet.php">
<style type="text/css">
#report_table {
	font-family: "Arial";
	font-size: 10pt; 
	border-collapse: collapse;
	width: 100%;
}
#report_table td, #report_table th {
	border: 1px;
	padding: 5px;
}

.comparison_th {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	font-weight: bold;
	background: #808080;
	color: #FFFFFF;
	vertical-align: middle;
}
.offered_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #e2efd9;
	color: #000000;
	vertical-align: middle;
}
.answered_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #deeaf6;
	color: #000000;
	vertical-align: middle;
}
.avg_speed_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #f2f2f2;
	color: #000000;
	vertical-align: middle;
}
.abandoned_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #fff2cc;
	color: #000000;
	vertical-align: middle;
}
.abandoned_pct_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #fbe4d5;
	color: #000000;
	vertical-align: middle;
}
.sla_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #f4b084;
	color: #000000;
	vertical-align: middle;
}
.avg_handle_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #dbdbdb;
	color: #000000;
	vertical-align: middle;
}
.occupancy_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #d0cece;
	color: #000000;
	vertical-align: middle;
}
.survey_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 10pt; 
	background: #FFFFFF;
	color: #000000;
	vertical-align: middle;
}
</style>
<script language="JavaScript" src="calendar_db.js"></script>
<!-- <script language="JavaScript" src="help.js"></script>
<script language="JavaScript" src="VERM_custom_form_functions.php"></script> //-->
<link rel="stylesheet" href="calendar.css">

<?php
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>"._QXZ("$report_name")." - "._QXZ("Custom report form")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onload=\"CountMonthsYears()\">\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;

	require("../$SSadmin_web_directory/admin_header.php");
?>


<form name='VERM_custom_report' action="<?php echo $PHP_SELF; ?>" method="get">
<table id='admin_table' style='width:900px'>
<tr>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("BACK TO HOME"); ?>' onClick="window.location.href='VERM_admin.php'"></th>
	<th width='25%'>&nbsp;</th>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("REALTIME REPORT"); ?>' onClick="RunComparisonReport('realtime')"></th>
	<th width='25%'><input type='button' class='refreshButton' value='&#8635;' onClick="javascript:document.forms[0].reset(); document.getElementById('start_date').value='<?php echo $NOW_DAY; ?>'; document.getElementById('end_date').value='<?php echo $NOW_DAY; ?>'"></th>
</tr>
<tr>
	<td colspan='4' align='center'>

	<table style='width:900px' align='center'>
		<tr>
			<th><h2 class='admin_sub_header'><?php echo _QXZ("Inbound Comparison Report"); ?>:</h2></th>
			<th colspan='3'>&nbsp;</th>
		</tr>
<!--
		<tr>
			<td align='right'><?php echo _QXZ("Queues"); ?>: </td>
			<td align='left'>
<?php
			echo "	<select multiple id='vicidial_queue_groups[]' name='vicidial_queue_groups[]' class='VERM_form_field' size=3>\n"; 
			echo $queue_groups_dropdown;
			echo "  </select>\n";
			echo "$NWB#VERM_custom_report_queue$NWE";
?>
			</td>
		</tr>
//-->
		<tr>
			<td align='right'><?php echo _QXZ("Please select no more than two years for comparison"); ?>: </td>
			<td align='left'>

<?php
		$min_year_stmt="select year(min(call_date)) from vicidial_log where call_date>0";
		$min_year_rslt=mysql_to_mysqli($min_year_stmt, $link);
		$min_year_row=mysqli_fetch_row($min_year_rslt);
		$min_year=$min_year_row[0];
		echo "\t\t\t<select multiple id='comparison_years[]' name='comparison_years[]' class='VERM_form_field' size='".(date("Y")-$min_year+1)."' onClick='MultiSelectLimit(this.name, this.id, $check_limit)'>"; 
		for ($i=$min_year; $i<=date("Y"); $i++)
			{
			echo "\n\t\t\t\t<option value='$i' ".(in_array($i, $comparison_years) ? "selected" : "").">$i</option>";
			#echo "<input onClick='CheckboxLimit(this.name, this.id, $check_limit)' type='checkbox' name='comparison_years' id='comparison_years_$i' value='$i'>$i";
			#echo ($i<date("Y") ? "<BR>\n" : "");
			}
		echo "\n\t\t\t</select>\n";
?>

			</td>
			<td align='right'><?php echo _QXZ("Please select months for comparison"); ?>: </td>
			<td align='left'>
			<!-- <input onClick="SelectAll('comparison_months', 'select_all_months')" type='checkbox' name='select_all_months' id='select_all_months'>Select/unselect all<BR> //-->


<?php
		echo "\t\t\t<select multiple id='comparison_months[]' name='comparison_months[]' class='VERM_form_field' size='12' onClick='MultiSelectLimit(this.name, this.id, 12)'>"; 
		for ($i=1; $i<=12; $i++)
			{
			$monthName = date('F', mktime(0,0,0,$i));
			#echo "<input type='checkbox' name='comparison_months' id='comparison_years_$i' value='$i'>$monthName";
			#echo ($i<12 ? "<BR>\n" : "");
			echo "\n\t\t\t\t<option value='$i'".(in_array($i, $comparison_months) ? "selected" : "").">$monthName</option>";
			}
		echo "\n\t\t\t</select>\n";
?>
			</td>
		</tr>
	</table>
	
	Estimated time to execute: <span id='estimated_time' name='estimated_time'>0</span> seconds
	<BR><BR><input type='button' class='actButton' value='<?php echo _QXZ("RUN COMPARISON REPORT"); ?>' onClick='RunComparisonReport()'>

	</td>
</tr>
<!--
<tr>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("BACK TO HOME"); ?>' onClick="window.location.href='VERM_admin.php'"></th>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("RUN COMPARISON REPORT"); ?>' onClick='RunComparisonReport()'></th>
	<th width='25%'><input type='button' class='actButton' value='<?php echo _QXZ("REALTIME REPORT"); ?>' onClick="RunComparisonReport('realtime')"></th>
	<th width='25%'><input type='button' class='refreshButton' value='&#8635;' onClick="javascript:document.forms[0].reset(); document.getElementById('start_date').value='<?php echo $NOW_DAY; ?>'; document.getElementById('end_date').value='<?php echo $NOW_DAY; ?>'"></th>
</tr>
//-->
</table>
<?php 
if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
$end=date("U");
# echo $HTML_output;

echo "<font size='1'>Executed in ".($end-$start)." sec</font>";
?>

</form>
</body>
</html>