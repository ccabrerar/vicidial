<?php
# VERM_wallboard_widgets.php - Vicidial Enhanced Reporting widget-generating script
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1601 - First build
# 240801-1130 - Code updates for PHP8 compatibility
#
$report_name="VERM reports";

$startMS = microtime();

#$version = '2.14-873';
#$build = '230127-1750';

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");
require("VERM_options.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))			{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}

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

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	}


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
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"/".$SSadmin_web_directory."/admin.php\">"._QXZ("Click here to log in")."</a>.\n";
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

/*
$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			"$row[1],";
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name,/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}
*/

if (isset($_GET["widget_type"]))			{$widget_type=$_GET["widget_type"];}
	elseif (isset($_POST["widget_type"]))	{$widget_type=$_POST["widget_type"];}
if (isset($_GET["widget_id"]))			{$widget_id=$_GET["widget_id"];}
	elseif (isset($_POST["widget_id"]))	{$widget_id=$_POST["widget_id"];}
if (isset($_GET["wallboard_report_id"]))			{$wallboard_report_id=$_GET["wallboard_report_id"];}
	elseif (isset($_POST["wallboard_report_id"]))	{$wallboard_report_id=$_POST["wallboard_report_id"];}
if (isset($_GET["vicidial_queue_group"]))			{$vicidial_queue_group=$_GET["vicidial_queue_group"];}
	elseif (isset($_POST["vicidial_queue_group"]))	{$vicidial_queue_group=$_POST["vicidial_queue_group"];}
if (isset($_GET["live_queue"]))			{$live_queue=$_GET["live_queue"];}
	elseif (isset($_POST["live_queue"]))	{$live_queue=$_POST["live_queue"];}
if (isset($_GET["live_queue_name"]))			{$live_queue_name=$_GET["live_queue_name"];}
	elseif (isset($_POST["live_queue_name"]))	{$live_queue_name=$_POST["live_queue_name"];}

if ($non_latin < 1)
	{
	$widget_type=preg_replace('/[^-_0-9a-zA-Z]/','',$widget_type);
	$widget_id=preg_replace('/[^-_0-9a-zA-Z]/','',$widget_id);
	$wallboard_report_id=preg_replace('/[^-_0-9a-zA-Z]/','',$wallboard_report_id);
	$vicidial_queue_group=preg_replace('/[^-_0-9a-zA-Z]/','',$vicidial_queue_group);
	$live_queue=preg_replace('/[^-_0-9a-zA-Z]/','',$live_queue);
	$live_queue_name=preg_replace('/[^\s\-_0-9a-zA-Z]/','',$live_queue_name);
	}
else
	{
	$widget_type=preg_replace('/[^-_0-9\p{L}]/u','',$widget_type);
	$widget_id=preg_replace('/[^-_0-9\p{L}]/u','',$widget_id);
	$wallboard_report_id=preg_replace('/[^-_0-9\p{L}]/u','',$wallboard_report_id);
	$vicidial_queue_group=preg_replace('/[^-_0-9\p{L}]/u','',$vicidial_queue_group);
	$live_queue=preg_replace('/[^-_0-9\p{L}]/u','',$live_queue);
	$live_queue_name=preg_replace('/[^\s\-_0-9\p{L}]/u','',$live_queue_name);
	}

#### UPDATE VARS ####
if (isset($_GET["widget_title"]))			{$widget_title=$_GET["widget_title"];}
	elseif (isset($_POST["widget_title"]))	{$widget_title=$_POST["widget_title"];}
if (isset($_GET["widget_text"]))			{$widget_text=$_GET["widget_text"];}
	elseif (isset($_POST["widget_text"]))	{$widget_text=$_POST["widget_text"];}
if (isset($_GET["widget_queue"]))			{$widget_queue=$_GET["widget_queue"];}
	elseif (isset($_POST["widget_queue"]))	{$widget_queue=$_POST["widget_queue"];}
if (isset($_GET["widget_agent"]))			{$widget_agent=$_GET["widget_agent"];}
	elseif (isset($_POST["widget_agent"]))	{$widget_agent=$_POST["widget_agent"];}
if (isset($_GET["widget_sla_level"]))			{$widget_sla_level=$_GET["widget_sla_level"];}
	elseif (isset($_POST["widget_sla_level"]))	{$widget_sla_level=$_POST["widget_sla_level"];}
#if (isset($_GET["widget_yellow"]))			{$widget_yellow=$_GET["widget_yellow"];}
#	elseif (isset($_POST["widget_yellow"]))	{$widget_yellow=$_POST["widget_yellow"];}
#if (isset($_GET["widget_red"]))			{$widget_red=$_GET["widget_red"];}
#	elseif (isset($_POST["widget_red"]))	{$widget_red=$_POST["widget_red"];}
if (isset($_GET["widget_alarms"]))			{$widget_alarms=$_GET["widget_alarms"];}
	elseif (isset($_POST["widget_alarms"]))	{$widget_alarms=$_POST["widget_alarms"];}
#if (isset($_GET["widget_colors2"]))			{$widget_colors2=$_GET["widget_colors2"];}
#	elseif (isset($_POST["widget_colors2"]))	{$widget_colors2=$_POST["widget_colors2"];}

$widget_title = preg_replace("/\n|\r|'|\"|\\\\|;|\|/","",$widget_title);
$widget_text = preg_replace("/\n|\r|'|\"|\\\\|;|\|/","",$widget_text);
$widget_sla_level = preg_replace("/[^\>\<\=\!0-9]/","",$widget_sla_level);

if ($non_latin < 1)
	{
	$widget_queue=preg_replace('/[^-_0-9a-zA-Z]/','',$widget_queue);
	$widget_agent = preg_replace('/[^-_0-9a-zA-Z]/','',$widget_agent);
	$widget_alarms = preg_replace('/[^,\|\-_0-9a-zA-Z]/','',$widget_alarms);
	}
else
	{
	$widget_queue=preg_replace('/[^-_0-9\p{L}]/u','',$widget_queue);
	$widget_agent = preg_replace('/[^-_0-9\p{L}]/u','',$widget_agent);
	$widget_alarms = preg_replace('/[^,\|\-_0-9\p{L}]/u','',$widget_alarms);
	}


#### $queue_group is the overriding group that the entire wallboard is based on.
$queue_group=$VERM_default_report_queue;
if (!$wallboard_report_id) {$wallboard_report_id="AGENTS_AND_QUEUES";}
if ($vicidial_queue_group) {$queue_group=$vicidial_queue_group;}
$TODAY=date("Y-m-d");


#### COMPILE QUEUE GROUPS SECTION ####
$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];


$stmt="SELECT allowed_queue_groups from vicidial_user_groups where user_group='$LOGuser_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_queue_groups =		$row[0];

$LOGallowed_queue_groupsSQL='';
if ( (!preg_match('/\-ALL\-GROUPS\-/i',$LOGallowed_queue_groups)) and (strlen($LOGallowed_queue_groups) > 3) )
	{
	$rawLOGallowed_queue_groupsSQL = preg_replace("/ \-/",'',$LOGallowed_queue_groups);
	$rawLOGallowed_queue_groupsSQL = preg_replace("/ /","','",$rawLOGallowed_queue_groupsSQL);
	$LOGallowed_queue_groupsSQL = "and queue_group IN('---ALL---','$rawLOGallowed_queue_groupsSQL')";
	$whereLOGallowed_queue_groupsSQL = "where queue_group IN('---ALL---','$rawLOGallowed_queue_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}

$stmt="select * from vicidial_queue_groups where active='Y' $LOGallowed_queue_groupsSQL order by queue_group, queue_group_name;";
$rslt=mysql_to_mysqli($stmt, $link);
$available_queue_groups=array();
while ($row=mysqli_fetch_array($rslt))
	{
	array_push($available_queue_groups, $row["queue_group"]);
	}	
########################################

#### START QUEUE GROUP CAMPAIGN/INGROUP DATA COMPILATION ####
# Gather queue group information for DEFAULT QUEUE 
$master_queue_group_campaigns=array();
$master_queue_group_ingroups=array();
if ($queue_group)
	{
	$vqg_stmt="select included_campaigns, included_inbound_groups from vicidial_queue_groups where queue_group='$queue_group'"; #  queue_group_name='$queue_group' or 
	if ($DB) {echo "$vqg_stmt<BR>\n";}
	$vqg_rslt=mysql_to_mysqli($vqg_stmt, $link);
	if(mysqli_num_rows($vqg_rslt)>0)
		{
		$vqg_row=mysqli_fetch_array($vqg_rslt);

		$included_campaigns=trim(preg_replace('/\s\-$/', '', $vqg_row["included_campaigns"]));
		$included_campaigns_groups_ct=count(explode(" ", $included_campaigns));
		$included_campaigns_str=preg_replace('/\s/', "', '", $included_campaigns);
		$included_campaigns_clause="and campaign_id in ('".$included_campaigns_str."')";
		$where_included_campaigns_clause="where campaign_id in ('".$included_campaigns_str."')";
		$included_campaigns_array=array_filter(explode(" ", $included_campaigns));
		$master_queue_group_campaigns=$included_campaigns_array;

		$included_inbound_groups=trim(preg_replace('/\s\-$/', '', $vqg_row["included_inbound_groups"]));
		$included_inbound_groups_ct=count(explode(" ", $included_inbound_groups));
		$included_inbound_groups_str=preg_replace('/\s/', "', '", $included_inbound_groups);
		$included_inbound_groups_clause="and group_id in ('".$included_inbound_groups_str."')";
		$where_included_inbound_groups_clause="where group_id in ('".$included_inbound_groups_str."')";
		$included_ingroups_array=array_filter(explode(" ", $included_inbound_groups));
		$master_queue_group_ingroups=$included_ingroups_array;
		}
	}

# Get final list of 'atomic queues'
$atomic_queue_campaigns_str="'', ";
$campaign_id_stmt="select campaign_id, campaign_name from vicidial_campaigns where campaign_id is not null $included_campaigns_clause order by campaign_id"; # $LOGallowed_campaignsSQL
$campaign_id_rslt=mysql_to_mysqli($campaign_id_stmt, $link);
while($campaign_id_row=mysqli_fetch_array($campaign_id_rslt))
	{
	$atomic_queue_str.=$campaign_id_row["campaign_name"];
	$atomic_queue_str.=" <i>[".$campaign_id_row["campaign_id"]."]</i>,";
	$atomic_queue_campaigns_str.="'$campaign_id_row[campaign_id]', ";
	}
$campaigns_str=preg_replace('/, $/', '', $atomic_queue_campaigns_str);
if ($campaigns_str!="''")
	{
	$and_campaigns_clause="and campaign_id in (".$campaigns_str.")";
	}

# Check if queue settings override user group settings - skipping this per Matt
/*
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
*/

$ingroups_str="'', ";
$vla_ingroups_clause="";
$ingroups_id_stmt="select group_id, group_name from vicidial_inbound_groups where group_id is not null $included_inbound_groups_clause"; #  where group_id in ('".implode("', '", $allowed_ingroups_array)."')
$ingroups_id_rslt=mysql_to_mysqli($ingroups_id_stmt, $link);
while($ingroups_id_row=mysqli_fetch_array($ingroups_id_rslt))
	{
	$atomic_queue_str.=$ingroups_id_row["group_name"];
	$atomic_queue_str.=" <i>[".$ingroups_id_row["group_id"]."]</i>,";
	$ingroups_str.="'$ingroups_id_row[group_id]', ";
	$vla_ingroups_clause.="vla.closer_campaigns like '% $ingroups_id_row[group_id] %' OR ";
	}
$ingroups_str=preg_replace('/, $/', '', $ingroups_str);
$and_ingroups_clause="and campaign_id in (".$ingroups_str.")";
if (strlen($vla_ingroups_clause)>0)
	{
	$vla_ingroups_clause=preg_replace('/ OR $/', '', $vla_ingroups_clause);
	$and_vla_ingroups_clause=" and ($vla_ingroups_clause) ";
	}
##############################

# $atomic_queue_str=preg_replace('/,$/', '', $atomic_queue_str);

if (strlen($atomic_queue_str)==0)
	{
	$atomic_queue_str="NONE";
	}
#### END QUEUE GROUPS ####

#### LIMIT THE WIDGET TYPES ####
$valid_widget_types=array("LOGO", "CLOCK", "TEXT", "OFFERED_CALLS", "LONGEST_WAIT", "LOST_CALLS", "ANSWERED_CALLS", "N_WAITING_CALLS", "LOST_CALLS_GRAPH", "N_ANSWERED_CALLS", "N_OFFERED_CALLS", "N_LONGEST_WAIT", "N_AGENTS_ON_CALL", "LOST_PCT", "ANSWERED_PCT", "AGENTS_READY", "SLA_LEVEL_PCT", "LIVE_AGENTS", "LIVE_CALLS", "LIVE_QUEUES", "BAR_GRAPH_SLA", "LIVE_AGENT_INFO", "LIVE_QUEUE_INFO", "AVG_QUEUE_INFO", "TEST_WIDGET");

#### DEFINE THE WIDGET TYPES, MORE FOR REFERENCE ATP ####
$text_widgets_array=array("CLOCK", "TEXT", "OFFERED_CALLS", "LONGEST_WAIT", "LOST_CALLS", "ANSWERED_CALLS");
$circle_widgets_array=array("N_WAITING_CALLS", "LOST_CALLS_GRAPH", "N_ANSWERED_CALLS", "N_OFFERED_CALLS", "N_LONGEST_WAIT", "N_AGENTS_ON_CALL", "LOST_PCT", "ANSWERED_PCT", "AGENTS_READY", "SLA_LEVEL_PCT");
$table_widgets_array=array("LIVE_AGENTS", "LIVE_CALLS", "LIVE_QUEUES");
$bar_chart_widgets_array=array("BAR_GRAPH_SLA");
$mixed_widgets_array=array("LIVE_AGENT_INFO", "LIVE_QUEUE_INFO", "AVG_QUEUE_INFO");
$other_widgets_array=array("LOGO");

# $widget_type="LOGO";
# $wallboard_report_id="TEST";

if(!$wallboard_report_id)
	{
	echo "ERROR: no report ID";
	exit;
	}

if ($widget_type=="UPDATE_WIDGET" && $widget_id)
	{
	$widget_alarms=preg_replace('/\|$/', "", $widget_alarms);
	$upd_stmt="update wallboard_widgets set widget_title='$widget_title', widget_text='$widget_text', widget_queue='$widget_queue', widget_sla_level='$widget_sla_level', widget_agent='$widget_agent', widget_alarms='$widget_alarms' where widget_id='$widget_id' and wallboard_report_id='$wallboard_report_id'";
	if ($DB) {echo "$upd_stmt\n";}
	$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
	echo mysqli_affected_rows($link);
	exit;
	}

if (!$widget_type || !in_array($widget_type, $valid_widget_types))
	{
	echo "ERROR: Not a valid widget type";
	exit;
	}

if ($widget_type=="TEST_WIDGET")
	{
	/*
	$matching_queue_campaigns=array();
	$matching_queue_ingroups=array();
	if (in_array($live_queue, $available_queue_groups)) # IS a queue in the database
		{
		$live_queue_group_campaigns=GetQueueCampaigns($live_queue);
		$live_queue_group_ingroups=GetQueueIngroups($live_queue);

		$matching_queue_campaigns=array_intersect($live_queue_group_campaigns,$master_queue_group_campaigns);
		$matching_queue_ingroups=array_intersect($live_queue_group_ingroups, $master_queue_group_ingroups);
		}
	print_r($master_queue_group_ingroups);
	echo "<BR>";
	print_r($matching_queue_campaigns);
	echo "<BR>";
	print_r($matching_queue_ingroups);
	*/

	echo "<div id='testme'></div>\n";
	echo "<script src=\"pureknob.js\"></script>\n";
	echo "<script language=\"Javascript\">\n";
	echo "var myKnob1 = pureknob.createKnob('200', '200');\n";
	echo "myKnob1.setProperty('angleStart', -1 * Math.PI);\n";
	echo "myKnob1.setProperty('angleEnd', 1 * Math.PI);\n";
	echo "myKnob1.setProperty('angleOffset', 0.5 * Math.PI);\n";
	echo "myKnob1.setProperty('trackWidth', 0.3);\n";
	echo "myKnob1.setProperty('valMin', 0);\n";
	echo "myKnob1.setProperty('valMax', 100);\n";
	echo "myKnob1.setProperty('colorFG', '#990000');\n";
	echo "var node1 = myKnob1.node();\n";
	echo "var test=Math.floor(Math.random() * 100);\n";
	echo "myKnob1.setValue(test);\n";
	echo "var elem1 = document.getElementById('testme');\n";
	echo "elem1.appendChild(node1);\n";
	echo "var intToSec=new Date(test * 1000).toISOString().substr(11, 8);\n";
	echo "console.log(test+\" converts to \"+intToSec);\n";
	echo "</script>";
	exit;
	}

if ($widget_type=="LIVE_QUEUE_INFO")
	{
	$total_matching_queues=0;

	if (in_array($live_queue, $available_queue_groups)) # IS a queue in the database
		{

		$matching_queue_campaigns=array();
		$matching_queue_ingroups=array();
		if (in_array($live_queue, $available_queue_groups)) # IS a queue in the database
			{
			$live_queue_group_campaigns=GetQueueCampaigns($live_queue);
			$live_queue_group_ingroups=GetQueueIngroups($live_queue);

			$matching_queue_campaigns=array_intersect($live_queue_group_campaigns,$master_queue_group_campaigns);
			$matching_queue_ingroups=array_intersect($live_queue_group_ingroups, $master_queue_group_ingroups);
			}
		
		if ($DB)
			{
			print_r($live_queue_group_campaigns);
			print_r($master_queue_group_campaigns);
			print_r($matching_queue_campaigns);
			print "<BR>\n<BR>\n";
			print_r($live_queue_group_ingroups);
			print_r($master_queue_group_ingroups);
			print_r($matching_queue_ingroups);
			}

		$total_matching_queues=count($matching_queue_campaigns)+count($matching_queue_ingroups);

		if(count($matching_queue_campaigns)>0)
			{
			sort($matching_queue_campaigns);
			$offered_calls_stmt_outb="select count(*) from vicidial_log where call_date>='$TODAY 00:00:00' and campaign_id in ('".implode("', '", $matching_queue_campaigns)."')  $exc_addtl_statuses";
			$lost_calls_stmt_outb="select count(*) from vicidial_log where user='VDAD' and status!='INCALL' and call_date>='$TODAY 00:00:00' and campaign_id in ('".implode("', '", $matching_queue_campaigns)."') $exc_addtl_statuses";
			}

		if(count($matching_queue_ingroups)>0)
			{
			sort($matching_queue_ingroups);
			$offered_calls_stmt_inb="select count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' and campaign_id in ('".implode("', '", $matching_queue_ingroups)."') $exc_addtl_statuses";
			$lost_calls_stmt_inb="select count(*) From vicidial_closer_log where user='VDCL' and status!='QUEUE' and call_date>='$TODAY 00:00:00' and campaign_id in ('".implode("', '", $matching_queue_ingroups)."') $exc_addtl_statuses";
			}
		}
	/* Removed - these are for non-queue entities like campaigns/ingroups, and... NO.  
	#  Make a queue group consisting of a single campaign/ingroup if that's what you want.
	else if (preg_match('/^ALL\-(OUT|IN)BOUND$/', $live_queue))
		{
		$live_queue_name=preg_replace('/\-/', '', $live_queue);
		if ($live_queue=="ALL-OUTBOUND") 
			{
			# $live_queue_clause=$and_campaigns_clause; 
			$direction="OUT";
			$offered_calls_stmt="select count(*) from vicidial_log where call_date>='$TODAY 00:00:00' $and_campaigns_clause";
			$lost_calls_stmt="select count(*) from vicidial_log where user='VDAD' and status!='INCALL' and call_date>='$TODAY 00:00:00' $and_campaigns_clause";
			}
		if ($live_queue=="ALL-INBOUND") 
			{
			# $live_queue_clause=$and_ingroups_clause; 
			$direction="IN";
			$offered_calls_stmt="select count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' $and_ingroups_clause";
			$lost_calls_stmt="select count(*) From vicidial_closer_log where user='VDCL' and status!='QUEUE' $exc_addtl_statuses and call_date>='$TODAY 00:00:00' $and_ingroups_clause";
			}
		}
	else
		{
		$live_queue_clause=" and campaign_id='$live_queue' "; 
		if (in_array($live_queue, $included_campaigns_array))
			{
			$direction="OUT";
			$offered_calls_stmt="select count(*) from vicidial_log where call_date>='$TODAY 00:00:00' $live_queue_clause";
			$lost_calls_stmt="select count(*) from vicidial_log where user='VDAD' and status!='INCALL' and call_date>='$TODAY 00:00:00' $live_queue_clause";
			}
		else if (in_array($live_queue, $included_ingroups_array))
			{
			$direction="IN";
			$offered_calls_stmt="select count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' $live_queue_clause";
			$lost_calls_stmt="select count(*) From vicidial_closer_log where user='VDCL' and status!='QUEUE' $exc_addtl_statuses and call_date>='$TODAY 00:00:00' $live_queue_clause";
			}
		}
	*/

	$offered_calls_stmt=$offered_calls_stmt_outb.(($offered_calls_stmt_outb && $offered_calls_stmt_inb) ? " UNION ALL " : "").$offered_calls_stmt_inb;
	$lost_calls_stmt=$lost_calls_stmt_outb.(($lost_calls_stmt_outb && $lost_calls_stmt_inb) ? " UNION ALL " : "").$lost_calls_stmt_inb;

	if (!$total_matching_queues || $total_matching_queues==0) 
		{
		echo "-1|-1|0.0|$live_queue";
		# echo "<div class='centered_text' style='text-overflow: ellipsis;'><font class='wallboard_small_text centered_text'>$live_queue_name</font><BR><font class='wallboard_large_text bold centered_text'>N/A</font><BR><font class='wallboard_medium_text centered_text'>&nbsp;</font><BR><font class='wallboard_tiny_text italics centered_text'>(no matching queues)</font></div><!--\n $offered_calls_stmt\n$lost_calls_stmt\n //-->"; 
		exit;
		}

	if ($DB) {echo "$offered_calls_stmt<BR>\n$lost_calls_stmt<BR>\n";}

	$offered_calls_rslt=mysql_to_mysqli($offered_calls_stmt, $link);
	$offered_calls=0;
	while ($offered_calls_row=mysqli_fetch_row($offered_calls_rslt))
		{
		$offered_calls+=$offered_calls_row[0];
		}

	$lost_calls_rslt=mysql_to_mysqli($lost_calls_stmt, $link);
	$lost_calls=0;
	while ($lost_calls_row=mysqli_fetch_row($lost_calls_rslt))
		{
		$lost_calls+=$lost_calls_row[0];
		}

	if ($offered_calls < 1) {$lost_calls_pct=0;}
	else {$lost_calls_pct=sprintf("%.1f", MathZDC((100*$lost_calls), $offered_calls));}

	echo "$offered_calls|$offered_calls|$lost_calls_pct|$live_queue";
	/*
	echo "<div class='centered_text' style='text-overflow: ellipsis;'>";
	echo "<font class='wallboard_small_text centered_text'>$live_queue_name</font><!--\n $offered_calls_stmt\n$lost_calls_stmt\n //-->";
	echo "<BR><font class='wallboard_large_text bold centered_text'>$offered_calls</font>";
	echo "<BR><font class='wallboard_medium_text centered_text'>$lost_calls_pct %</font>";
	echo "<BR><font class='wallboard_tiny_text italics centered_text'>Lost</font>";
	echo "</div>";
	*/
	exit;
	}

if ($widget_type=="LOGO")
	{
	$logo_stmt="select web_logo from vicidial_screen_colors, system_settings where colors_id=admin_screen_colors;";
	$logo_rslt=mysql_to_mysqli($logo_stmt, $link);
	if (mysqli_num_rows($logo_rslt)>0)
		{
		$logo_row=mysqli_fetch_row($logo_rslt);
		echo "<div class='total_center'><img style='display:block;max-height:100%; max-width:100%' src='/".$SSadmin_web_directory."/images/vicidial_admin_web_logo".($logo_row[0]=="default_new" ? ".png" : "$logo_row[0]")."' /></div>";
		}
	exit;
	}

if ($widget_type=="CLOCK")
	{
	$widget_output=date("H:i")."<BR><BR>".date("Y-m-d");
	echo $widget_output;
	exit;
	}

if ($widget_type=="TEXT")
	{
	$wstmt="select widget_text from wallboard_widgets where wallboard_report_id='$wallboard_report_id' and widget_id='$widget_id'";
	if ($DB) {echo "$wstmt<BR>";}
	$wrslt=mysql_to_mysqli($wstmt, $link);
	if (mysqli_num_rows($wrslt)>0)
		{
		$wallboard_text="";
		while ($wrow=mysqli_fetch_row($wrslt))
			{
			$wallboard_text.="$wrow[0]";
			}
		echo "<div class='centered_text'><font class='wallboard_extra_large_text bold centered_text'>$wallboard_text</font></div>";
		}
	exit;
	}

### PANEL OUTPUT - no graph
if ($widget_type=="OFFERED_CALLS")
	{
	$offered_calls_stmt="select count(*) from vicidial_log where call_date>='$TODAY 00:00:00' $and_campaigns_clause $exc_addtl_statuses UNION ALL select count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' $and_ingroups_clause $exc_addtl_statuses";
	$offered_calls_rslt=mysql_to_mysqli($offered_calls_stmt, $link);
	$offered_calls=0;
	while ($offered_calls_row=mysqli_fetch_row($offered_calls_rslt))
		{
		$offered_calls+=$offered_calls_row[0];
		}
	if ($DB) {echo "$offered_calls_stmt<BR>";}
	echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$offered_calls."</font><BR><BR><font class='wallboard_medium_text italics'>"._QXZ("Offered calls")."</font></div>";
	exit;
	}

if ($widget_type=="LONGEST_WAIT")
	{
	# $wait_stmt="select unix_timestamp()-UNIX_TIMESTAMP(last_state_change) as wait_time, vla.user, full_name from vicidial_live_agents vla, vicidial_users vu where vla.status in ('READY', 'CLOSER') and vla.user=vu.user $remote_agents_clause $and_campaigns_clause order by wait_time desc";
	$stmtA = "SELECT unix_timestamp()-UNIX_TIMESTAMP(call_time) as current_status_duration ";

	if ($included_inbound_groups_ct > 0)
		{
		$stmtB="from vicidial_auto_calls where status='LIVE' and ( (call_type='IN' $and_ingroups_clause) or (call_type IN('OUT','OUTBALANCE') $and_campaigns_clause) ) order by current_status_duration desc limit 1;";
		}
	else
		{
		$stmtB="from vicidial_auto_calls where status='LIVE' $and_campaigns_clause  order by current_status_duration desc limit 1;";
		}

	$wait_stmt = "$stmtA $stmtB";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}

	$wait_rslt=mysql_to_mysqli($wait_stmt, $link);
	$wait_time="--";
	if (mysqli_num_rows($wait_rslt)>0)
		{
		$wait_row=mysqli_fetch_array($wait_rslt);
		$wait_time=$wait_row["current_status_duration"];
		# $wait_time=floor($wait_row[0]/60).gmdate(":s", $wait_row[0]);
		}
	if ($DB) {echo "$wait_stmt<BR>";}
	#echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$wait_time."</font></div>"; # ."<BR><BR>$user";
	echo $wait_time; # ."<BR><BR>$user";
	exit;
	}

if ($widget_type=="LOST_CALLS")
	{
	$lost_calls_stmt="select count(*) from vicidial_log where user='VDAD' and status!='INCALL' and call_date>='$TODAY 00:00:00' $and_campaigns_clause UNION ALL select count(*) From vicidial_closer_log where user='VDCL' and status!='QUEUE' $exc_addtl_statuses and call_date>='$TODAY 00:00:00' $and_ingroups_clause";
	$lost_calls_rslt=mysql_to_mysqli($lost_calls_stmt, $link);
	$lost_calls=0;
	while ($lost_calls_row=mysqli_fetch_row($lost_calls_rslt))
		{
		$lost_calls+=$lost_calls_row[0];
		}
	if ($DB) {echo "$lost_calls_stmt<BR>";}
	echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$lost_calls."</font><BR><BR><font class='wallboard_medium_text italics'>"._QXZ("Lost calls")."</font></div>";
	exit;
	}

if ($widget_type=="ANSWERED_CALLS")
	{
	$answered_calls_stmt="select count(*) from vicidial_log where user!='VDAD' and call_date>='$TODAY 00:00:00' $and_campaigns_clause UNION ALL select count(*) From vicidial_closer_log where user!='VDCL' and call_date>='$TODAY 00:00:00' $and_ingroups_clause";
	$answered_calls_rslt=mysql_to_mysqli($answered_calls_stmt, $link);
	$answered_calls=0;
	while ($answered_calls_row=mysqli_fetch_row($answered_calls_rslt))
		{
		$answered_calls+=$answered_calls_row[0];
		}
	if ($DB) {echo "$answered_calls_stmt<BR>";}
	echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$answered_calls."</font><BR><BR><font class='wallboard_medium_text italics'>"._QXZ("Answered calls")."</font></div>";
	exit;
	}


#### GRAPH OUTPUT 
/*
if ($widget_type=="N_WAITING_CALLS")
	{
	if ($included_inbound_groups_ct > 0)
		{		
		$stmtB="from vicidial_auto_calls where status='LIVE' and ( (call_type='IN' and campaign_id IN('$included_inbound_groups_str')) or (call_type IN('OUT','OUTBALANCE') $included_campaigns_clause) ) order by queue_priority desc,campaign_id,call_time;";
		}
	else
		{
		$stmtB="from vicidial_auto_calls where status='LIVE' IN('XFER') $included_campaigns_clause order by queue_priority desc,campaign_id,call_time;";
		}


	$stmtA = "SELECT count(*)";

	$stmt = "$stmtA $stmtB";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$live_calls=mysqli_fetch_row($rslt);
	# echo $live_calls[0];
	echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$live_calls[0]."</font></div>";

	## JCJ Display chats - set aside for now
	#if ($allow_chats) 
	#	{
	#	$chat_stmtA="SELECT vlc.status";
	#	}
	exit;
	}	
*/

if ($widget_type=="N_WAITING_CALLS")
	{
	if ($included_inbound_groups_ct > 0)
		{
		# $waiting_clause="sum(if(status='LIVE' and ( (call_type='IN' and campaign_id IN('$included_inbound_groups_str')) or (call_type IN('OUT','OUTBALANCE') $included_campaigns_clause) ), 1, 0)";
		$waiting_clause="sum(if(status='LIVE' and ( (call_type='IN' and campaign_id IN('$included_inbound_groups_str')) or (call_type IN('OUT','OUTBALANCE') $included_campaigns_clause) ), 1, 0))";
		$stmtB="from vicidial_auto_calls where status='LIVE' and ( (call_type='IN' and campaign_id IN('$included_inbound_groups_str')) or (call_type IN('OUT','OUTBALANCE') $included_campaigns_clause) ) order by queue_priority desc,campaign_id,call_time;";
		}
	else
		{
		$waiting_clause="sum(if(status in ('LIVE', 'XFER') $included_campaigns_clause, 1, 0))";
		# sum(if(status='LIVE' IN('XFER') $included_campaigns_clause), 1, 0)
		$stmtB="from vicidial_auto_calls where status in ('LIVE', 'XFER') $included_campaigns_clause order by queue_priority desc,campaign_id,call_time;";
		}


	$stmtA = "SELECT count(*)";

	$stmt = "$stmtA $stmtB";

	$stmt = "SELECT $waiting_clause, count(*) From vicidial_auto_calls";

	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$live_calls=mysqli_fetch_row($rslt);
	echo ($live_calls[0]+0)."|".($live_calls[1]+0);
	# echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$live_calls[0]."</font></div>";

	## JCJ Display chats - set aside for now
	#if ($allow_chats) 
	#	{
	#	$chat_stmtA="SELECT vlc.status";
	#	}
	exit;
	}	


if ($widget_type=="N_ANSWERED_CALLS")
	{
	$answered_calls_stmt="select sum(if(user!='VDAD', 1, 0)), count(*) from vicidial_log where call_date>='$TODAY 00:00:00' $and_campaigns_clause UNION ALL select sum(if(user!='VDCL', 1, 0)), count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' $and_ingroups_clause";
	$answered_calls_rslt=mysql_to_mysqli($answered_calls_stmt, $link);
	$answered_calls=0;
	$total_calls=0;
	while ($answered_calls_row=mysqli_fetch_row($answered_calls_rslt))
		{
		$answered_calls+=$answered_calls_row[0];
		$total_calls+=$answered_calls_row[1];
		}
	if ($DB) {echo "$answered_calls_stmt<BR>";}
	echo $answered_calls."|".$total_calls;
	# echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$answered_calls."</font></div>";
	exit;
	}

if ($widget_type=="N_OFFERED_CALLS")
	{
	$offered_calls_stmt="select count(*) from vicidial_log where call_date>='$TODAY 00:00:00' $and_campaigns_clause UNION ALL select count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' $and_ingroups_clause";
	$offered_calls_rslt=mysql_to_mysqli($offered_calls_stmt, $link);
	$offered_calls=0;
	while ($offered_calls_row=mysqli_fetch_row($offered_calls_rslt))
		{
		$offered_calls+=$offered_calls_row[0];
		}
	if ($DB) {echo "$offered_calls_stmt<BR>";}

	echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$offered_calls."</font></div>";
	exit;
	}

if ($widget_type=="N_LONGEST_WAIT")
	{
	/*
	$offered_calls_stmt="select count(*) from vicidial_log where call_date>='$TODAY 00:00:00' $and_campaigns_clause UNION ALL select count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' $and_ingroups_clause";
	$offered_calls_rslt=mysql_to_mysqli($offered_calls_stmt, $link);
	$offered_calls=0;
	while ($offered_calls_row=mysqli_fetch_row($offered_calls_rslt))
		{
		$offered_calls+=$offered_calls_row[0];
		}
	if ($DB) {echo "$offered_calls_stmt<BR>";}
	*/

	$wait_stmt="select unix_timestamp()-UNIX_TIMESTAMP(last_state_change) as wait_time, vla.user, full_name from vicidial_live_agents vla, vicidial_users vu where vla.status in ('READY', 'CLOSER') and vla.user=vu.user $remote_agents_clause $and_campaigns_clause order by wait_time desc";
	$wait_rslt=mysql_to_mysqli($wait_stmt, $link);
	$wait_time="0";
	if (mysqli_num_rows($wait_rslt)>0)
		{
		$wait_row=mysqli_fetch_row($wait_rslt);
		$wait_time=$wait_row[0];
		}
	if ($DB) {echo "$wait_stmt<BR>";}
	echo $wait_time; # ."<BR><BR>$user";
	exit;
	}

if ($widget_type=="LOST_CALLS_GRAPH")
	{
	$lost_calls_stmt="select count(*) from vicidial_log where user='VDAD' and status!='INCALL' and call_date>='$TODAY 00:00:00' $and_campaigns_clause UNION ALL select count(*) From vicidial_closer_log where user='VDCL' and status!='QUEUE' $exc_addtl_statuses and call_date>='$TODAY 00:00:00' $and_ingroups_clause";
	$lost_calls_rslt=mysql_to_mysqli($lost_calls_stmt, $link);
	$lost_calls=0;
	while ($lost_calls_row=mysqli_fetch_row($lost_calls_rslt))
		{
		$lost_calls+=$lost_calls_row[0];
		}
	if ($DB) {echo "$lost_calls_stmt<BR>";}
	echo $lost_calls;
	exit;
	}


if ($widget_type=="N_AGENTS_ON_CALL")
	{
	$aoc_stmt="select sum(if(status in ('QUEUE', 'MQUEUE', 'INCALL'), 1, 0)), count(*) from vicidial_live_agents where user is not null $remote_agents_clause $and_campaigns_clause";
	$aoc_rslt=mysql_to_mysqli($aoc_stmt, $link);
	if ($DB) {echo "$aoc_stmt\n";}
	$incalls=mysqli_fetch_row($aoc_rslt);
	echo ($incalls[0]+0)."|".($incalls[1]+0);
	#echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$incalls[0]."</font></div>";
	exit;
	}

if ($widget_type=="LOST_PCT")
	{
	$lost_calls_stmt="select sum(if(user='VDAD' and status!='INCALL', 1, 0)) as lost_calls, count(*) from vicidial_log where call_date>='$TODAY 00:00:00' $and_campaigns_clause UNION ALL select sum(if(user='VDCL' and status!='QUEUE' $exc_addtl_statuses, 1, 0)) as lost_calls, count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' $and_ingroups_clause";
	$lost_calls_rslt=mysql_to_mysqli($lost_calls_stmt, $link);
	$lost_calls=0; $total_calls=0;
	while ($lost_calls_row=mysqli_fetch_row($lost_calls_rslt))
		{
		$lost_calls+=$lost_calls_row[0];
		$total_calls+=$lost_calls_row[1];
		}
	if ($DB) {echo "$lost_calls_stmt<BR>($lost_calls, $total_calls)";}
	$lost_calls_pct=sprintf("%.1f", (100*MathZDC($lost_calls, $total_calls, 0)));
	
	echo $lost_calls_pct;
	exit;
	}

if ($widget_type=="ANSWERED_PCT")
	{
	$answered_calls_stmt="select sum(if(user!='VDAD', 1, 0)) as lost_calls, count(*) from vicidial_log where call_date>='$TODAY 00:00:00' $and_campaigns_clause UNION ALL select sum(if(user!='VDCL', 1, 0)) as lost_calls, count(*) From vicidial_closer_log where call_date>='$TODAY 00:00:00' $and_ingroups_clause ";
	$answered_calls_rslt=mysql_to_mysqli($answered_calls_stmt, $link);
	$answered_calls=0; $total_calls=0;
	while ($answered_calls_row=mysqli_fetch_row($answered_calls_rslt))
		{
		$answered_calls+=$answered_calls_row[0];
		$total_calls+=$answered_calls_row[1];
		}
	if ($DB) {echo "$answered_calls_stmt<BR>($answered_calls, $total_calls)";}
	$answered_calls_pct=sprintf("%.1f", (100*$answered_calls/$total_calls));
	
	echo $answered_calls_pct;
	exit;
	}

if ($widget_type=="AGENTS_READY")
	{
	$ready_stmt="select sum(if(status in ('READY', 'CLOSER'), 1, 0)), count(*) from vicidial_live_agents where user is not null $remote_agents_clause $and_campaigns_clause";
	$ready_rslt=mysql_to_mysqli($ready_stmt, $link);
	if ($DB) {echo "$ready_stmt\n";}
	$agents_ready=mysqli_fetch_row($ready_rslt);
	echo ($agents_ready[0]+0)."|".($agents_ready[1]+0);
	# echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$agents_ready[0]."</font></div>";
	}

if ($widget_type=="SLA_LEVEL_PCT")
	{
	# Don't bother if the main report queue has no ingroups - just die.
	if(count($master_queue_group_ingroups)==0)
		{
		echo "-1"; exit;
		}

	$sla_clause=preg_replace('/[^\>\<0-9\.]/', '', $sla_clause);
	if (!$sla_clause || !preg_match('/[\>\<][0-9]+/', $sla_query))
		{
		$sla_clause="<=60";
		}
	$SLA_query.="sum(if(queue_seconds".$sla_clause.", 1, 0)), count(*)";
	$svc_lvl_stmt="select $SLA_query from vicidial_closer_log where call_date>='$TODAY 00:00:00' $and_ingroups_clause and (user!='VDCL') $SLA_LEVEL_PCT_clause $exc_addtl_statuses"; #  and campaign_id in ($ingroup_str) and user in ('VDCL', $user_str)
	$svc_lvl_rslt=mysql_to_mysqli($svc_lvl_stmt, $link);

	## Clause for N/A graphs
	if (mysqli_num_rows($svc_lvl_rslt)==0) 
		{
		echo "-1"; exit;
		}

	$svc_lvl_row=mysqli_fetch_row($svc_lvl_rslt);

	if ($DB) {echo $svc_lvl_stmt."<BR>$svc_lvl_row[0], $svc_lvl_row[1]<BR><BR>";}
	if ($svc_lvl_row[1]>0)
		{
		$svc_lvl_pct=sprintf("%.1f", (100*$svc_lvl_row[0]/$svc_lvl_row[1]));
		}
	else
		{
		$svc_lvl_pct="0.0";
		}
	# echo $svc_lvl_pct;
	echo "$svc_lvl_pct|100";
	# echo "<div class='centered_text'><font class='wallboard_large_text bold'>".$svc_lvl_pct." %</font></div>";


/*
	echo "<div id='".$widget_id."_knob'></div>\n";
	echo "<script src=\"pureknob.js\"></script>\n";
	echo "<script language=\"Javascript\">\n";
	echo "var myKnob1 = pureknob.createKnob('175', '175',  'percent');\n";
	echo "myKnob1.setProperty('angleStart', -1 * Math.PI);\n";
	echo "myKnob1.setProperty('angleEnd', 1 * Math.PI);\n";
	echo "myKnob1.setProperty('trackWidth', 0.1);\n";
	echo "myKnob1.setProperty('valMin', 0);\n";
	echo "myKnob1.setProperty('valMax', 100);\n";
	echo "myKnob1.setProperty('colorFG', '#009900');\n";
	echo "var node1 = myKnob1.node();\n";
	# echo "var test=Math.floor(Math.random() * 60);\n";
	echo "myKnob1.setValue('$svc_lvl_pct');\n";
	echo "var elem1 = document.getElementById('".$widget_id."_knob');\n";
	echo "elem1.appendChild(node1);\n";
	echo "</script>";
*/

	exit;
	}

if ($widget_type=="LIVE_AGENTS")
	{
	
	#### GENERATE PAUSE CODE ####
	$pause_code_stmt="select * from vicidial_pause_codes";
	$pause_code_rslt=mysql_to_mysqli($pause_code_stmt, $link);
	$billable_pause_codes=array();
	$payable_pause_codes=array();
	$pause_code_names=array();
	while ($pause_code_row=mysqli_fetch_array($pause_code_rslt))
		{
		$pause_code_campaign_id=$pause_code_row["campaign_id"];
		$pause_code=$pause_code_row["pause_code"];
		$pause_code_name=$pause_code_row["pause_code_name"];
		$billable=$pause_code_row["billable"];
		$pause_name_key=$pause_code_campaign_id."-".$pause_code;
		$pause_name_login=$pause_code_campaign_id."-LOGIN";
		$pause_name_lagged=$pause_code_campaign_id."-LAGGED";

		$pause_code_names["$pause_name_key"]=$pause_code_name;
		$pause_code_names["$pause_name_login"]="Login";
		$pause_code_names["$pause_name_lagged"]="Network Delay";
		}
	#############################


		# Agent, extension, on pause, code, current call, conversation, last completed call
	# Current call = phone number?  But then what is conversation?  Assuming means current call start time and time INCALL
	$live_agent_stmt="select comments, vu.full_name, vu.user, extension, if(vla.status='PAUSED', sec_to_time(timestampdiff(second, last_state_change, now())), '00:00:00') as on_pause, vla.status as code, if(vla.status in ('QUEUE', 'MQUEUE', 'INCALL'), last_call_time, '-') as current_call, if(vla.status in ('QUEUE', 'MQUEUE', 'INCALL'), sec_to_time(timestampdiff(second, last_state_change, now())), '00:00:00') as conversation, lead_id, last_call_finish, vla.campaign_id, vla.pause_code from vicidial_users vu, vicidial_live_agents vla where vla.user=vu.user $remote_agents_clause $and_campaigns_clause $and_vla_ingroups_clause";
	$live_agent_rslt=mysql_to_mysqli($live_agent_stmt, $link);

	if ($DB) {echo $live_agent_stmt;}
	# echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"VERM_wallboard_stylesheet.php\">";

	echo "<div class='top_center'>";
	echo "<table id='widget_table'>\n";
	echo "<tr class='widget_table_header'>";
	echo "<th>&nbsp;</th>";
	echo "<th>"._QXZ("Agent")."</th>";
	echo "<th>"._QXZ("Extension")."</th>";
	echo "<th>"._QXZ("On Pause")."</th>";
	echo "<th>"._QXZ("Code")."</th>";
	echo "<th>"._QXZ("Current Call")."</th>";
	echo "<th>"._QXZ("Conversation")."</th>";
	echo "<th>"._QXZ("Last Completed Call")."</th>";
	echo "</tr>\n";

	while ($live_agent_row=mysqli_fetch_array($live_agent_rslt))
		{
		$direction="";
		$conversation_comment="";
		$pcode=$live_agent_row["pause_code"];
		$campaign_id=$live_agent_row["campaign_id"];

		if(preg_match('/QUEUE|INCALL|3-WAY/', $live_agent_row["code"]))
			{
			if($live_agent_row["comments"]=="INBOUND")
				{
				$direction="<input type='button' class='inbound_marker_button' value='&#11013;'></th>";
				}
			else
				{
				$direction="<input type='button' class='outbound_marker_button' value='&#10145;'></th>";
				}
			$phone_stmt="select phone_number from vicidial_list where lead_id='".$live_agent_row["lead_id"]."'";
			$phone_rslt=mysql_to_mysqli($phone_stmt, $link);
			if(mysqli_num_rows($phone_rslt)>0)
				{
				$phone_row=mysqli_fetch_row($phone_rslt);
				$conversation_comment="$phone_row[0]";
				}
			}

		if(preg_match('/READY|CLOSER/', $live_agent_row["code"]))
			{
			$direction="<input type='button' class='ready_marker_button' value='&#128337;'></th>";
			}

		if ($live_agent_row["code"]=="PAUSED")
			{
			$direction="<input type='button' class='paused_marker_button' value='&#9881;'></th>";
			$pkey=$campaign_id."-".$pcode;
			if ($pause_code_names["$pkey"])
				{
				$code=$pause_code_names["$pkey"];
				}
			else
				{
				$code=$pcode;
				}
			}
		else
			{
			$code=$live_agent_row["code"];
			}

		echo "<tr>";
		echo "<th>$direction&nbsp;</th>";
		echo "<td>".$live_agent_row["full_name"]."</td>";
		echo "<td>".$live_agent_row["extension"]."</td>";
		echo "<th>".$live_agent_row["on_pause"]."</th>";
		echo "<th>".$code."</th>";
		echo "<th>".$conversation_comment."</th>";
		echo "<th>".$live_agent_row["conversation"]."</th>";
		echo "<th>".$live_agent_row["last_call_finish"]."</th>";
		echo "</tr>\n";
		}
	echo "</table>";
	echo "</div>";
	exit;
	}

if ($widget_type=="LIVE_CALLS")
	{

	# $live_calls=mysqli_fetch_row($rslt);

#  echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"VERM_wallboard_stylesheet.php\">";
	echo "<div class='top_center'>";
	echo "<table id='widget_table'>\n";
	echo "<tr class='widget_table_header'>";
	echo "<th>&nbsp;</th>";
	echo "<th>"._QXZ("Campaign/ingroup")."</th>";
	echo "<th>"._QXZ("Caller")."</th>";
	echo "<th>"._QXZ("IVR")."</th>";
	echo "<th>"._QXZ("Wait")."</th>";
	echo "<th>"._QXZ("Talk")."</th>";
	echo "<th>"._QXZ("Agent")."</th>";
	echo "</tr>\n";

	# IN CALL CALLS
	# $stmt="select vu.full_name, vla.user, vla.status";

	# WAITING CALLS
	$stmtA = "SELECT status,campaign_id,phone_number,server_ip,UNIX_TIMESTAMP(call_time),call_type,queue_priority,agent_only,uniqueid, sec_to_time(timestampdiff(second, last_update_time, now())) as current_status_duration ";

	if ($included_inbound_groups_ct > 0)
		{
		$stmtB="from vicidial_auto_calls where status NOT IN('XFER') and ( (call_type='IN' $and_ingroups_clause) or (call_type IN('OUT','OUTBALANCE') $and_campaigns_clause) ) order by queue_priority desc,campaign_id,call_time;";
		}
	else
		{
		$stmtB="from vicidial_auto_calls where status NOT IN('XFER') $and_campaigns_clause order by queue_priority desc,campaign_id,call_time;";
		}

	$stmt = "$stmtA $stmtB";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	while ($row=mysqli_fetch_array($rslt))
		{
		$ivr_time="0:00";
		$wait_time="0:00";
		$talk_time="0:00";
		$agent="-";

		#if (preg_match("/IVR/i",$row["uniqueid"]))
		#	{
		$ivr_time=GetIVRCallTime($row["uniqueid"], "0:00");
		#	}

		# Get wait sec if inbound, outbound has no wait time
		if ($row["call_type"]=="IN")
			{
			$wait_stmt="select round(sum(queue_seconds)) from vicidial_closer_log where uniqueid='".$row["uniqueid"]."'";
			$wait_rslt=mysql_to_mysqli($wait_stmt, $link);
			if (mysqli_num_rows($wait_rslt)>0)
				{
				$wait_row=mysqli_fetch_row($wait_rslt);
				$wait_time=($wait_row[0]>=3600 ? intval(floor($wait_row[0]/3600)).date(":i:s", $wait_row[0]) : intval(date("i", $wait_row[0])).":".date("s", $wait_row[0]));
				}

			$direction="&#10145;";
			$btn_class="outbound_marker_button";
			}
		else 
			{
			$direction="&#11013;";
			$btn_class="inbound_marker_button";
			}

		$talk_stmt="select timestampdiff(second, last_call_time, now()) as length_in_sec, vu.full_name from vicidial_users vu, vicidial_live_agents vla where uniqueid='".$row["uniqueid"]."' and vla.user=vu.user $remote_agents_clause";
		$talk_rslt=mysql_to_mysqli($talk_stmt, $link);
		if (mysqli_num_rows($talk_rslt)>0)
			{
			$talk_row=mysqli_fetch_row($talk_rslt);
			$talk_time=($talk_row[0]>=3600 ? intval(floor($talk_row[0]/3600)).date(":i:s", $talk_row[0]) : intval(date("i", $talk_row[0])).":".date("s", $talk_row[0]));
			$agent=$talk_row[1];
			}

		echo "<tr>";
		echo "<th><input type='button' class='$btn_class' value='$direction'></th>";
		echo "<th>".$row["campaign_id"]."</th>";
		echo "<th>".$row["phone_number"]."</th>";
		echo "<th>$ivr_time</th>";
		echo "<th>$wait_time</th>";
		echo "<th>$talk_time</th>";
		echo "<th>$agent</th>";
		echo "</tr>\n";
		}


	echo "</table>";
	echo "</div>";
	exit;
	}

if ($widget_type=="LIVE_QUEUES")
	{
	$vla_stmt="select vla.user, vla.status, vla.comments, vla.uniqueid, vla.campaign_id from vicidial_live_agents vla where user is not null $remote_agents_clause $and_campaigns_clause order by campaign_id";
	if ($DB) {echo "$vla_stmt\n";}
	$vla_rslt=mysql_to_mysqli($vla_stmt, $link);

	echo "<div class='top_center'>";
	echo "<table id='widget_table' class='topalign'>\n";
	echo "<tr class='widget_table_header'>";
	echo "<th>"._QXZ("Direction")."</th>";
	echo "<th>"._QXZ("Campaign")."</th>";
	echo "<th>"._QXZ("N. Of Agents")."</th>";
	echo "<th>"._QXZ("Ready")."</th>";
	echo "<th>"._QXZ("On Pause")."</th>";
	echo "<th>"._QXZ("Busy")."</th>";
	echo "<th>"._QXZ("Unknown")."</th>";
	echo "<th>"._QXZ("Calls Waiting")."</th>";
	echo "<th>"._QXZ("Agents on Inbound")."</th>";
	echo "<th>"._QXZ("Agents on Outbound")."</th>";
	echo "</tr>\n";
	
	$n_agents=mysqli_num_rows($vla_rslt);
	$ready=0;
	$paused=0;
	$busy=0;
	$unknown=0;
	$agents_inb=0;
	$agents_outb=0;
	$live_queue_rslts=array();
	while($vla_row=mysqli_fetch_array($vla_rslt))
		{
		$campaign_id=$vla_row["campaign_id"];
		$live_queue_rslts["$campaign_id"]["n_agents"]++;

		if(preg_match('/QUEUE|MQUEUE|INCALL/', $vla_row["status"]))
			{
			if ($vla_row["comments"]=="INBOUND")
				{
				$agents_inb++;
				$live_queue_rslts["$campaign_id"]["agents_inb"]++;
				}
			else
				{
				$agents_outb++;
				$live_queue_rslts["$campaign_id"]["agents_outb"]++;
				}
			}
		else if ($vla_row["status"]=="PAUSED")
			{
			$live_queue_rslts["$campaign_id"]["paused"]++;
			$paused++;
			}
		else if(preg_match('/READY|CLOSER/', $vla_row["status"]))
			{
			$live_queue_rslts["$campaign_id"]["ready"]++;
			$ready++;
			}
		else
			{
			$live_queue_rslts["$campaign_id"]["unknown"]++;
			$unknown++;
			}
		}


	$stmtA = "SELECT status,campaign_id,phone_number,server_ip,UNIX_TIMESTAMP(call_time),call_type,queue_priority,agent_only,uniqueid, sec_to_time(timestampdiff(second, last_update_time, now())) as current_status_duration ";

	if ($included_inbound_groups_ct > 0)
		{
		$stmtB="from vicidial_auto_calls where status NOT IN('XFER') and ( (call_type='IN' $and_ingroups_clause) or (call_type IN('OUT','OUTBALANCE') $and_campaigns_clause) ) order by queue_priority desc,campaign_id,call_time;";
		}
	else
		{
		$stmtB="from vicidial_auto_calls where status NOT IN('XFER') $and_campaigns_clause order by queue_priority desc,campaign_id,call_time;";
		}

	$stmt = "$stmtA $stmtB";
	$rslt=mysql_to_mysqli($stmt, $link);
	$calls_waiting=0;
	if ($DB) {echo "$stmt\n";}

	$live_uids=array();
	while ($row=mysqli_fetch_array($rslt))
		{
		$campaign_id=$row["campaign_id"];
		array_push($live_uids, $row["uniqueid"]);
		if ($row["status"]=="LIVE") 
			{
			$live_queue_rslts["$campaign_id"]["calls_waiting"]++;
			$calls_waiting++;
			}
		}

	# print_r($live_queue_rslts);

	foreach ($live_queue_rslts as $campaign_id => $rslts)
		{

		if (in_array($campaign_id, $included_ingroups_array))
			{
			$direction="&#11013;";
			$btn_class="inbound_marker_button";
			}
		else 
			{
			$direction="&#10145;";
			$btn_class="outbound_marker_button";
			}

		echo "<tr>";
		echo "<th><input type='button' class='$btn_class' value='$direction'></th>";
		# echo "<th>$vicidial_queue_group</th>";
		echo "<th>".$campaign_id."</th>";
		echo "<th>".($rslts["n_agents"]+0)."</th>";
		echo "<th>".($rslts["ready"]+0)."</th>";
		echo "<th>".($rslts["paused"]+0)."</th>";
		echo "<th>".($rslts["busy"]+0)."</th>";
		echo "<th>".($rslts["unknown"]+0)."</th>";
		echo "<th>".($rslts["calls_waiting"]+0)."</th>";
		echo "<th>".($rslts["agents_inb"]+0)."</th>";
		echo "<th>".($rslts["agents_outb"]+0)."</th>";
		echo "</tr>\n";
		}

	echo "</table>";
	echo "</div>";
	exit;
	}

# $valid_widget_types=array("", "", "LIVE_QUEUES", "BAR_GRAPH_SLA", "LIVE_AGENT_INFO", "LIVE_QUEUE_INFO", "AVG_QUEUE_INFO");
# $table_widgets_array=array("LIVE_AGENTS", "LIVE_CALLS", "LIVE_QUEUES");
function GetIVRCallTime($uniqueid, $default_value)
	{
	global $link, $DB;

	$CTODAY=date("Y-m-d");
	$ivr_time_stmt="select unix_timestamp()-unix_timestamp(min(start_time)) from live_inbound_log where uniqueid='$uniqueid' and start_time>='$CTODAY 00:00:00'";
	$ivr_time_rslt=mysql_to_mysqli($ivr_time_stmt, $link);
	if (mysqli_num_rows($ivr_time_rslt)>0)
		{
		while($ivr_row=mysqli_fetch_row($ivr_time_rslt))
			{
			$ivr=$ivr_row[0];
			}
		$ivr_time=($ivr>=3600 ? intval(floor($ivr/3600)).date(":i:s", $ivr) : intval(date("i", $ivr)).":".date("s", $ivr));
	
		return $ivr_time;
		}
	else
		{
		return $default_value;
		}
	
	exit;
	}

function GetQueueCampaigns($queue_id)
	{
	global $link, $DB;
	$vqg_stmt="select included_campaigns from vicidial_queue_groups where queue_group='$queue_id'";
	if ($DB) {echo $vqg_stmt."<BR>\n";}
	$vqg_rslt=mysql_to_mysqli($vqg_stmt, $link);
	$vqg_row=mysqli_fetch_array($vqg_rslt);

	$included_campaigns=trim(preg_replace('/\s\-$/', '', $vqg_row["included_campaigns"]));
	$included_campaigns_groups_ct=count(explode(" ", $included_campaigns));
	$included_campaigns_str=preg_replace('/\s/', "', '", $included_campaigns);
	$included_campaigns_clause="and campaign_id in ('".$included_campaigns_str."')";
	$where_included_campaigns_clause="where campaign_id in ('".$included_campaigns_str."')";
	$included_campaigns_array=explode(" ", $included_campaigns);

	return $included_campaigns_array;

	/*
	echo "<!-- \n$vqg_stmt\n //-->";

	# Get final list of 'atomic queues'
	$atomic_queue_campaigns_str="'', ";
	$campaign_id_stmt="select campaign_id, campaign_name from vicidial_campaigns where campaign_id is not null $included_campaigns_clause order by campaign_id"; # $LOGallowed_campaignsSQL
	$campaign_id_rslt=mysql_to_mysqli($campaign_id_stmt, $link);
	while($campaign_id_row=mysqli_fetch_array($campaign_id_rslt))
		{
		$atomic_queue_str.=$campaign_id_row["campaign_name"];
		$atomic_queue_str.=" <i>[".$campaign_id_row["campaign_id"]."]</i>,";
		$atomic_queue_campaigns_str.="'$campaign_id_row[campaign_id]', ";
		}
	$campaigns_str=preg_replace('/, $/', '', $atomic_queue_campaigns_str);
	$and_campaigns_clause="and campaign_id in (".$campaigns_str.")";

	echo "<!-- \n$campaign_id_stmt\n //-->";

	return $and_campaigns_clause;
	*/
	}

function GetQueueIngroups($queue_id)
	{
	global $link, $DB;

	$and_ingroups_clause="";

	$vqg_stmt="select included_inbound_groups from vicidial_queue_groups where queue_group='$queue_id'";
	if ($DB) {echo $vqg_stmt."<BR>\n";}
	$vqg_rslt=mysql_to_mysqli($vqg_stmt, $link);
	$vqg_row=mysqli_fetch_array($vqg_rslt);
	$included_inbound_groups=trim(preg_replace('/\s\-$/', '', $vqg_row["included_inbound_groups"]));
	$included_inbound_groups_ct=count(explode(" ", $included_inbound_groups));
	$included_inbound_groups_str=preg_replace('/\s/', "', '", $included_inbound_groups);
	$included_inbound_groups_clause="and group_id in ('".$included_inbound_groups_str."')";
	$where_included_inbound_groups_clause="where group_id in ('".$included_inbound_groups_str."')";
	$included_ingroups_array=explode(" ", $included_inbound_groups);
	if ($DB) {echo $included_inbound_groups."<BR>\n";}

	return $included_ingroups_array;

	/*
	echo "<!-- \n$vqg_stmt\n //-->";

	$ingroups_str="'', ";
	$ingroups_id_stmt="select group_id, group_name from vicidial_inbound_groups where group_id is not null $included_inbound_groups_clause"; #  where group_id in ('".implode("', '", $allowed_ingroups_array)."')
	$ingroups_id_rslt=mysql_to_mysqli($ingroups_id_stmt, $link);
	while($ingroups_id_row=mysqli_fetch_array($ingroups_id_rslt))
		{
		$atomic_queue_str.=$ingroups_id_row["group_name"];
		$atomic_queue_str.=" <i>[".$ingroups_id_row["group_id"]."]</i>,";
		$ingroups_str.="'$ingroups_id_row[group_id]', ";
		}
	$ingroups_str=preg_replace('/, $/', '', $ingroups_str);
	$and_ingroups_clause="and campaign_id in (".$ingroups_str.")";

	echo "<!-- \n$ingroups_id_stmt\n //-->";

	return $and_ingroups_clause;
	*/
	}

?>
