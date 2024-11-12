<?php
# VERM_wallboards.php - Vicidial Enhanced Reporting wallboard main page
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1600 - First build
#
$report_name="VERM reports";

$startMS = microtime();

#$version = '2.14-873';
#$build = '230127-1750';

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");
require("VERM_options.php"); # Get $VERM_default_report_queue from here

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
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}


# $report_name = 'Vicidial Enhanced Reporting Module';
require("../$SSadmin_web_directory/screen_colors.php");

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
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
$queue_dropdown_array=array();
while ($row=mysqli_fetch_array($rslt))
	{
	$queue_dropdown_array["$row[queue_group]"]=$row["queue_group_name"];
	}	
########################################

if (isset($_GET["widget_type"]))			{$widget_type=$_GET["widget_type"];}
	elseif (isset($_POST["widget_type"]))	{$widget_type=$_POST["widget_type"];}
if (isset($_GET["widget_id"]))			{$widget_id=$_GET["widget_id"];}
	elseif (isset($_POST["widget_id"]))	{$widget_id=$_POST["widget_id"];}
if (isset($_GET["wallboard_report_id"]))			{$wallboard_report_id=$_GET["wallboard_report_id"];}
	elseif (isset($_POST["wallboard_report_id"]))	{$wallboard_report_id=$_POST["wallboard_report_id"];}
if (isset($_GET["vicidial_queue_group"]))			{$vicidial_queue_group=$_GET["vicidial_queue_group"];}
	elseif (isset($_POST["vicidial_queue_group"]))	{$vicidial_queue_group=$_POST["vicidial_queue_group"];}

### QUEUE GROUP CAMPAIGNS/INGROUPS - COMPILED FOR DROP-DOWNS
# $vicidial_queue_group=RemoveEmptyArrayStrings($vicidial_queue_group);

if ($non_latin < 1)
	{
	$widget_type=preg_replace('/[^-_0-9a-zA-Z]/','',$widget_type);
	$widget_id=preg_replace('/[^-_0-9a-zA-Z]/','',$widget_id);
	$wallboard_report_id=preg_replace('/[^-_0-9a-zA-Z]/','',$wallboard_report_id);
	$vicidial_queue_group=preg_replace('/[^-_0-9a-zA-Z]/','',$vicidial_queue_group);
	}
else
	{
	$widget_type=preg_replace('/[^-_0-9\p{L}]/u','',$widget_type);
	$widget_id=preg_replace('/[^-_0-9\p{L}]/u','',$widget_id);
	$wallboard_report_id=preg_replace('/[^-_0-9\p{L}]/u','',$wallboard_report_id);
	$vicidial_queue_group=preg_replace('/[^-_0-9\p{L}]/u','',$vicidial_queue_group);
	}
if (!$vicidial_queue_group) {$vicidial_queue_group=$VERM_default_report_queue;}

if (!$wallboard_report_id) {$wallboard_report_id="AGENTS_AND_QUEUES";}

# FILL IN DEFAULT WIDGETS IF THEY DON'T EXIST IN THE MAIN QUEUE REPORT.
$queue_dropdown_array["$vicidial_queue_group"]="$vicidial_queue_group";
if ($VERM_default_outb_widget_queue && !$queue_dropdown_array["$VERM_default_outb_widget_queue"]) 
	{$queue_dropdown_array["$VERM_default_outb_widget_queue"]="$VERM_default_outb_widget_queue";}
if ($VERM_default_inb_widget_queue1 && !$queue_dropdown_array["$VERM_default_inb_widget_queue1"]) 
	{$queue_dropdown_array["$VERM_default_inb_widget_queue1"]="$VERM_default_inb_widget_queue1";}
if ($VERM_default_inb_widget_queue2 && !$queue_dropdown_array["$VERM_default_inb_widget_queue2"]) 
	{$queue_dropdown_array["$VERM_default_inb_widget_queue2"]="$VERM_default_inb_widget_queue2";}

if ($vicidial_queue_group)
	{
	$vqg_stmt="select included_campaigns, included_inbound_groups, queue_group_name from vicidial_queue_groups where queue_group='$vicidial_queue_group'";
	$vqg_rslt=mysql_to_mysqli($vqg_stmt, $link);
	if(mysqli_num_rows($vqg_rslt)>0)
		{
		$vqg_row=mysqli_fetch_array($vqg_rslt);
		$queue_group_name=$vqg_row["queue_group_name"];
		$included_campaigns=trim(preg_replace('/\s\-$/', '', $vqg_row["included_campaigns"]));
		$included_campaigns_array=explode(" ", $included_campaigns);
		sort($included_campaigns_array);
		$included_campaigns_ct=count($included_campaigns_array);
		$included_campaigns_clause="and campaign_id in ('".preg_replace('/\s/', "', '", $included_campaigns)."')";
		$included_inbound_groups=trim(preg_replace('/\s\-$/', '', $vqg_row["included_inbound_groups"]));
		$included_inbound_groups_array=explode(" ", $included_inbound_groups);
		sort($included_inbound_groups_array);
		$included_inbound_groups_ct=count($included_inbound_groups_array);
		$included_inbound_groups_clause="and group_id in ('".preg_replace('/\s/', "', '", $included_inbound_groups)."')";
		$where_included_inbound_groups_clause="where group_id in ('".preg_replace('/\s/', "', '", $included_inbound_groups)."')";
		}
	}

/* Commenting this out - unnecessary
$campaign_id_stmt="select campaign_id, campaign_name from vicidial_campaigns where campaign_id is not null $included_campaigns_clause order by campaign_name"; # $LOGallowed_campaignsSQL, removed for now per Matt's assurances
$campaign_id_rslt=mysql_to_mysqli($campaign_id_stmt, $link);
if (mysqli_num_rows($campaign_id_rslt)>0) {$queue_dropdown_array["ALL-OUTBOUND"]="ALL OUTBOUND IN QUEUE GROUP";}
while($campaign_id_row=mysqli_fetch_array($campaign_id_rslt))
	{
	$queue_dropdown_array["$campaign_id_row[campaign_id]"]=$campaign_id_row["campaign_name"];
	$atomic_queue_str.=$campaign_id_row["campaign_name"];
	}

$ingroups_id_stmt="select group_id, group_name from vicidial_inbound_groups $where_included_inbound_groups_clause order by group_name"; #where group_id in ('".implode("', '", $allowed_ingroups_array)."') $included_inbound_groups_clause
$ingroups_id_rslt=mysql_to_mysqli($ingroups_id_stmt, $link);
if (mysqli_num_rows($ingroups_id_rslt)>0) {$queue_dropdown_array["ALL-INBOUND"]="ALL INBOUND IN QUEUE GROUP";}
while($ingroups_id_row=mysqli_fetch_array($ingroups_id_rslt))
	{
	$queue_dropdown_array["$ingroups_id_row[group_id]"]=$ingroups_id_row["group_name"];
	$atomic_queue_str.=$ingroups_id_row["group_name"];
	}
*/

/*
$report_stmt="select * from wallboard_reports where wallboard_report_id='$wallboard_report_id'"
$report_rslt=mysql_to_mysqli($report_stmt, $link);
if(mysqli_num_rows($report_rslt)>0)
	{
	
	}
else
	{
	echo "Invalid report ID"; die;
	}

$widgets_stmt=
*/

#### DEFINE THE WIDGET TYPES, MORE FOR REFERENCE ATP ####
$text_widgets_array=array("CLOCK", "TEXT", "OFFERED_CALLS", "LONGEST_WAIT", "LOST_CALLS", "ANSWERED_CALLS");
$circle_widgets_array=array("N_WAITING_CALLS", "LOST_CALLS_GRAPH", "N_ANSWERED_CALLS", "N_OFFERED_CALLS", "N_LONGEST_WAIT", "N_AGENTS_ON_CALL", "LOST_PCT", "ANSWERED_PCT", "AGENTS_READY", "SLA_LEVEL_PCT");
$table_widgets_array=array("LIVE_AGENTS", "LIVE_CALLS", "LIVE_QUEUES");
$bar_chart_widgets_array=array("BAR_GRAPH_SLA");
$mixed_widgets_array=array("LIVE_AGENT_INFO", "LIVE_QUEUE_INFO", "AVG_QUEUE_INFO");
$other_widgets_array=array("LOGO");

# Specific to each page/view

$wallboard_views=array();

$wallboard_views[0]=array(
	"view_name" => "Queues",
	"view_id" => "queues",
	"data_refresh_rate" => 10,
	"columns" => 8
);
 $wallboard_views[1]=array(
	"view_name" => "Agents",
	"view_id" => "agents",
	"data_refresh_rate" => 10,
	"columns" => 7
);

$view_widgets=array(
# widget_width (colspan) = X
# widget_is_row = Y
# widget_type = 
# widget_queue = 
# widget_order = 
);
$widget_id=$view_id."_widget_".$widget_order;

$view_widgets["queues"][0]=array(
	"widget_id" => "queues_widget_0",
	"widget_type" => "LOGO",
	"widget_width" => 2,
	"widget_title" => "",
	"widget_order" => 1,
	"widget_is_row" => "N"
);
$view_widgets["queues"][1]=array(
	"widget_id" => "queues_widget_1",
	"widget_type" => "TEXT",
	"widget_width" => 5,
	"widget_title" => "",
	"widget_order" => 2,
	"widget_is_row" => "N"
);
$view_widgets["queues"][2]=array(
	"widget_id" => "queues_widget_2",
	"widget_type" => "SLA_LEVEL_PCT",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("SLA Level %"), 0, 20),
	"widget_order" => 3,
	"widget_is_row" => "N"
);

$view_widgets["queues"][3]=array(
	"widget_id" => "queues_widget_3",
	"widget_type" => "LIVE_QUEUE_INFO",
	"widget_queue" => "$VERM_default_outb_widget_queue",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("$VERM_default_outb_widget_queue_name"), 0, 20),
	"widget_order" => 4,
	"widget_is_row" => "N"
);
$view_widgets["queues"][4]=array(
	"widget_id" => "queues_widget_4",
	"widget_type" => "LIVE_QUEUE_INFO",
	"widget_queue" => "$VERM_default_inb_widget_queue1",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("$VERM_default_inb_widget_queue1_name"), 0, 20),
	"widget_order" => 5,
	"widget_is_row" => "N"
);
$view_widgets["queues"][5]=array(
	"widget_id" => "queues_widget_5",
	"widget_type" => "LIVE_QUEUE_INFO",
	"widget_queue" => "$VERM_default_inb_widget_queue2",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("$VERM_default_inb_widget_queue2"), 0, 20),
	"widget_order" => 6,
	"widget_is_row" => "N"
);
$view_widgets["queues"][6]=array(
	"widget_id" => "queues_widget_6",
	"widget_type" => "N_WAITING_CALLS",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("N Waiting Calls"), 0, 20),
	"widget_order" => 7,
	"widget_is_row" => "N"
);

 $view_widgets["queues"][7]=array(
	"widget_id" => "queues_widget_7",
	"widget_type" => "OFFERED_CALLS",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Offered Calls"), 0, 20),
	"widget_order" => 8,
	"widget_is_row" => "N"
);
$view_widgets["queues"][8]=array(
	"widget_id" => "queues_widget_8",
	"widget_type" => "ANSWERED_CALLS",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Answered Calls"), 0, 20),
	"widget_order" => 9,
	"widget_is_row" => "N"
);
$view_widgets["queues"][9]=array(
	"widget_id" => "queues_widget_9",
	"widget_type" => "LOST_CALLS",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Lost Calls"), 0, 20),
	"widget_order" => 10,
	"widget_is_row" => "N"
);
$view_widgets["queues"][10]=array(
	"widget_id" => "queues_widget_10",
	"widget_type" => "LONGEST_WAIT",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Longest Wait"), 0, 20),
	"widget_order" => 11,
	"widget_is_row" => "N"
);

$view_widgets["queues"][11]=array(
	"widget_id" => "queues_widget_11",
	"widget_type" => "LIVE_QUEUES",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Live Campaigns"), 0, 20),
	"widget_order" => 12,
	"widget_is_row" => "Y"
);
$view_widgets["queues"][12]=array(
	"widget_id" => "queues_widget_12",
	"widget_type" => "LIVE_CALLS",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Live Calls"), 0, 20),
	"widget_order" => 13,
	"widget_is_row" => "Y",
	"widget_rowspan" => 2
);


##########
$view_widgets["agents"][0]=array(
	"widget_id" => "agent_widget_0",
	"widget_type" => "LOGO",
	"widget_width" => 2,
	"widget_title" => "",
	"widget_order" => 1,
	"widget_is_row" => "N"
);
$view_widgets["agents"][1]=array(
	"widget_id" => "agent_widget_1",
	"widget_type" => "N_WAITING_CALLS",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("N Waiting Calls"), 0, 20),
	"widget_order" => 2,
	"widget_is_row" => "N"
);
$view_widgets["agents"][2]=array(
	"widget_id" => "agent_widget_2",
	"widget_type" => "AGENTS_READY",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Agents Ready"), 0, 20),
	"widget_order" => 3,
	"widget_is_row" => "N"
);
$view_widgets["agents"][3]=array(
	"widget_id" => "agent_widget_3",
	"widget_type" => "N_AGENTS_ON_CALL",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Agents On Call"), 0, 20),
	"widget_order" => 4,
	"widget_is_row" => "N"
);
$view_widgets["agents"][4]=array(
	"widget_id" => "agent_widget_4",
	"widget_type" => "N_ANSWERED_CALLS",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("N Answered Calls"), 0, 20),
	"widget_order" => 5,
	"widget_is_row" => "N"
);
$view_widgets["agents"][5]=array(
	"widget_id" => "agent_widget_5",
	"widget_type" => "CLOCK",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Clock"), 0, 20),
	"widget_order" => 6,
	"widget_is_row" => "N"
);
$view_widgets["agents"][6]=array(
	"widget_id" => "agent_widget_6",
	"widget_type" => "LIVE_AGENTS",
	"widget_width" => 1,
	"widget_title" => substr(_QXZ("Live Agents"), 0, 20),
	"widget_order" => 7,
	"widget_is_row" => "Y",
	"widget_rowspan" => 3
);

# These should come from the previous lines;
$report_name="Agents and Queues";
$report_views=count($view_widgets); # "Views" = pages
$current_page="Queues";
$page_change_rate="30";
$view_name="";
$data_refresh_rate="10";

#$current_view
?>
<html>
<title><?php echo _QXZ("$report_name"); ?> - Wallboard</title>
<head>
<link rel="stylesheet" type="text/css" href="VERM_wallboard_stylesheet.php">
<script src="pureknob.js"></script>
<script language="Javascript">
// MAIN STATIC VARIABLES - MOST IMPORTANT
var LongestWaitMarker=0;
var LongestWaitOffset=0;
var LongestWaitTime=0;
<?php
echo "var TotalKnobScreenWidthLarge=Math.round(screen.width*0.88);\n";
echo "var TotalKnobScreenWidthSmall=Math.round(screen.width*0.65);\n";
echo "var WallboardReportID=\"$wallboard_report_id\";\n";
echo "var MasterReportQueue=\"$vicidial_queue_group\";\n";
echo "var ViewCount=".count($wallboard_views).";\n";
echo "var rpt_color=\"#".$SSalt_row2_background."\";\n";
$JS_view_names_array="var view_names=[";
$JS_data_refresh_array="var data_refresh_rates=[";
for ($v=0; $v<count($wallboard_views); $v++)
	{
	$JS_view_names_array.="'".$wallboard_views[$v]["view_name"]."', ";	
	$JS_data_refresh_array.="".$wallboard_views[$v]["data_refresh_rate"].", ";	
	}
$JS_view_names_array=preg_replace('/, $/', '', $JS_view_names_array);
$JS_data_refresh_array=preg_replace('/, $/', '', $JS_data_refresh_array);

$JS_view_names_array.="];\n";
$JS_data_refresh_array.="];\n";

echo $JS_view_names_array;
echo $JS_data_refresh_array;

###########

$JS_widget_ids_array="var all_widget_ids=[";
$JS_widget_types_array="var all_widget_types=[";
$JS_widget_data_array="var all_widget_data=[";
$JS_widget_scales_array="var all_widget_scales=[";
$JS_draw_graphs="";
$JS_update_graphs="";
$j=0;
foreach($view_widgets as $view => $widgets)
	{
	for ($i=0; $i<count($widgets); $i++)
		{
		$JS_widget_ids_array.="'".$widgets[$i]["widget_id"]."', ";
		$JS_widget_types_array.="'".$widgets[$i]["widget_type"]."', ";
		$JS_widget_data_array.="'0', ";
		$JS_widget_scales_array.="'0', ";

		$widget_id=$widgets[$i]["widget_id"];


		# Draw large and small knobs 
		$knob_ct=0;
		if (in_array($widgets[$i]["widget_type"], $circle_widgets_array) || in_array($widgets[$i]["widget_type"], $mixed_widgets_array))
			{
			for ($v=0; $v<count($wallboard_views); $v++)
				{
				if ($wallboard_views[$v]["view_id"]==$view)
					{
					$columns=$wallboard_views[$v]["columns"];
					}
				}
			if (in_array($widgets[$i]["widget_type"], $circle_widgets_array))
				{
				if (preg_match('/PCT/', $widgets[$i]["widget_type"]))
					{
					echo "\nvar ".$widget_id."_knob = pureknob.createKnob(Math.round(TotalKnobScreenWidthLarge/$columns), Math.round(TotalKnobScreenWidthLarge/$columns), 'percent');\n";
					}
				else
					{
					echo "\nvar ".$widget_id."_knob = pureknob.createKnob(Math.round(TotalKnobScreenWidthLarge/$columns), Math.round(TotalKnobScreenWidthLarge/$columns));\n";
					}
				}
			else
				{
				echo "var ".$widget_id."_knob = pureknob.createKnob(Math.round(TotalKnobScreenWidthSmall/$columns), Math.round(TotalKnobScreenWidthSmall/$columns));\n";
				}
			echo $widget_id."_knob.setProperty('angleStart', -1 * Math.PI);\n";
			echo $widget_id."_knob.setProperty('angleEnd', 1 * Math.PI);\n";
			echo $widget_id."_knob.setProperty('angleOffset', 0.5 * Math.PI);\n";
			echo $widget_id."_knob.setProperty('colorBG', '#dddddd');\n";
			echo $widget_id."_knob.setProperty('colorFG', rpt_color);\n";
			echo $widget_id."_knob.setProperty('trackWidth', 0.3);\n";
			echo $widget_id."_knob.setProperty('valMin', 0);\n";
			echo $widget_id."_knob.setProperty('valMax', 100);\n";
			echo "// IMPORTANT !!!\n";
			echo "var ".$widget_id."_knob_dataIndex=$j;\n\n";

			# if (in_array($widgets[$i]["widget_type"], $circle_widgets_array))
			if ($widgets[$i]["widget_type"]=="SLA_LEVEL_PCT" || $widgets[$i]["widget_type"]=="N_ANSWERED_CALLS" || $widgets[$i]["widget_type"]=="N_AGENTS_ON_CALL" || $widgets[$i]["widget_type"]=="AGENTS_READY" || $widgets[$i]["widget_type"]=="N_WAITING_CALLS")
				{
				$JS_draw_graphs.="var node".$j." = ".$widget_id."_knob.node();\n";
				$JS_draw_graphs.="var elem1 = document.getElementById('".$widget_id."');\n";
				$JS_draw_graphs.="elem1.appendChild(node".$j.");\n\n";

				$JS_update_graphs.="\tif (all_widget_data[$j]<0) {document.getElementById('$widget_id').innerHTML=\"<div class='centered_text' style='text-overflow: ellipsis;'><font class='wallboard_large_text bold centered_text'>N/A</font></div>\";}\n";
				$JS_update_graphs.="\t else {";
				if (!preg_match('/PCT/', $widgets[$i]["widget_type"]))
					{$JS_update_graphs.=$widget_id."_knob.setProperty('valMax', all_widget_scales[$j]);";}
				$JS_update_graphs.=$widget_id."_knob.setValue(all_widget_data[$j]);}\n"; #  console.log('Updating graph ".$widgets[$i]["widget_type"]." to:'+all_widget_data[$j]+' scale '+all_widget_scales[$j]);

				}
			else
				{
				if ($widgets[$i]["widget_type"]=="LIVE_QUEUE_INFO")
					{
					$JS_draw_graphs.="var node".$j." = ".$widget_id."_knob.node();\n";
					$JS_draw_graphs.="var elem1 = document.getElementById('".$widget_id."_graph');\n";
					$JS_draw_graphs.="elem1.appendChild(node".$j.");\n\n";

					$JS_update_graphs.="\tif (all_widget_data[$j]<0) {document.getElementById('".$widget_id."_graph').innerHTML=\"<div class='centered_text' style='text-overflow: ellipsis;'><font class='wallboard_large_text bold centered_text'><BR>N/A<BR><BR></font></div>\";}\n";
					$JS_update_graphs.="\t else {";
					if (!preg_match('/PCT/', $widgets[$i]["widget_type"]))
						{$JS_update_graphs.=$widget_id."_knob.setProperty('valMax', all_widget_scales[$j]);";}
					$JS_update_graphs.=$widget_id."_knob.setValue(all_widget_data[$j]);}\n"; #  console.log('Updating graph ".$widgets[$i]["widget_type"]." to:'+all_widget_data[$j]+' scale '+all_widget_scales[$j]);
					}
				}
			}
		$j++;
		}
	}
$JS_widget_ids_array=preg_replace('/, $/', '', $JS_widget_ids_array);
$JS_widget_types_array=preg_replace('/, $/', '', $JS_widget_types_array);
$JS_widget_data_array=preg_replace('/, $/', '', $JS_widget_data_array);
$JS_widget_scales_array=preg_replace('/, $/', '', $JS_widget_scales_array);

$JS_widget_ids_array.="];\n";
$JS_widget_types_array.="];\n";
$JS_widget_data_array.="];\n";
$JS_widget_scales_array.="];\n";

echo $JS_widget_ids_array;
echo $JS_widget_types_array;
echo $JS_widget_data_array;
echo $JS_widget_scales_array;
?>

// var rpt_color="#FF0000";
// if (drpctTODAY[g]<=greenZone) {rpt_color="#009900";}
// else if (drpctTODAY[g]>greenZone && drpctTODAY[g]<=yellowZone) {rpt_color="#DDDD00";}
// DIV
// <div id='".$widget_id."_CIRCLE'></div>

var myKnob1 = pureknob.createKnob('125', '125');
myKnob1.setProperty('angleStart', -1 * Math.PI);
myKnob1.setProperty('angleEnd', 1 * Math.PI);
myKnob1.setProperty('angleOffset', 0.5 * Math.PI);
myKnob1.setProperty('colorBG', '#dddddd');
myKnob1.setProperty('colorFG', rpt_color);
myKnob1.setProperty('trackWidth', 0.3);
myKnob1.setProperty('valMin', 0);
myKnob1.setProperty('valMax', 60000);

//	console.log(Object.values(myKnob1));

var IE = document.all?true:false
// If NS -- that is, !IE -- then set up for mouse capture
if (!IE) document.captureEvents(Event.MOUSEMOVE)
var ViewTicker=2;

function ClockTime()
	{
	var hour_offset=0;
	var CurrentDateTime=new Date().toLocaleString();
	// alert(CurrentTime);
	// 1/15/2022, 2:10:50 PM
	var DateTimeArray=CurrentDateTime.split(" ");
	var CurrentDate=DateTimeArray[0].replace(/,/, "");
	var YMD=CurrentDate.split("/");
	YMD[0]="0"+YMD[0];
	YMD[1]="0"+YMD[1];
	var CurrentDay=YMD[1].substring(YMD[1].length-2);
	var CurrentMonth=YMD[0].substring(YMD[0].length-2);
	var CurrentYear=YMD[2];
	var AMPM=DateTimeArray[2];

	var CurrentTime=DateTimeArray[1];
	var HIS=CurrentTime.split(":");
	var CurrentHour=parseInt(HIS[0]);
	if (AMPM=="PM" && CurrentHour<12)
		{
		CurrentHour+=12;
		}
	else
		{
		HIS[0]="0"+HIS[0];
		CurrentHour=HIS[0].substring(HIS[0].length-2);
		}
	var CurrentMinute=HIS[1];
	var CurrentSecond=HIS[2];

	var YMDHIS_time="<font class='wallboard_large_text'>"+CurrentHour+":"+CurrentMinute+"</font><BR><BR><font class='wallboard_medium_text'>"+CurrentYear+"-"+CurrentMonth+"-"+CurrentDay+"</font>"; // 

	// sweep through all elements, look for "_clock" in ID.
	// var all_clocks = document.querySelectorAll('[id$="_CLOCK"]');
	//for (var j=0; j<all_clocks.length; j++)
	for (var i=0; i<all_widget_ids.length; i++)
		{
		if (all_widget_types[i]=="CLOCK")
			{
			var ClockWidget=all_widget_ids[i];
			document.getElementById(ClockWidget).innerHTML="<div class='centered_text'>"+YMDHIS_time+"</div>";
			}
		}

	
	var parseSeconds=parseInt(HIS[2]);

	/*
	yellowZone=30;
	redZone=45;
	if (parseSeconds>yellowZone && parseSeconds>=redZone) {rpt_color="#FF0000";}
	else if (parseSeconds>=yellowZone) {rpt_color="#DDDD00";}
	else {rpt_color="#009900";}

	myKnob1.setValue((parseSeconds*1000));
	myKnob1.setProperty('colorFG', rpt_color);
	var node1 = myKnob1.node();
	var elem1 = document.getElementById('test_gauge');
	while (elem1.hasChildNodes()) {
		elem1.removeChild(elem1.lastChild);
	}
	elem1.appendChild(node1);
	*/

	// console.log(typeof(myKnob1));
	}

function DrawGraphs()
	{
	<?php echo $JS_draw_graphs; ?>
	}

 function UpdateGraphs()
	{
	<?php echo $JS_update_graphs; ?>
	}

function TestKnob(widget_id, knobName) {
	parseSeconds=Math.floor(Math.random() * 60);
	myKnob1.setValue(parseSeconds);
	var elem1 = document.getElementById(widget_id);
	while (elem1.hasChildNodes()) {
		elem1.removeChild(elem1.lastChild);
	}
}

// View Only
function StartReportRefresh(view_refresh_rate) {
	// Set stop button
	document.getElementById('wallboard_play_pause').value="\u25a0";
	document.getElementById('wallboard_play_pause').className="stop_button";
	console.log("Starting view reports...");

	if (!view_refresh_rate) {view_refresh_rate=30; SwitchView();} 
	viewInt=window.setInterval(function() {SwitchView()}, (view_refresh_rate*1000));
}

function StopReportRefresh() {
	// Set start button
	document.getElementById('wallboard_play_pause').value="\u25b6";
	document.getElementById('wallboard_play_pause').className="play_button";
	console.log("Stopping view cycling...");

	clearInterval(viewInt);
	// clearInterval(dataInt);
	// clearInterval(lwaitInt);
}

// Start data refresh cycle, uninterruptable
function StartDataRefresh(data_refresh_rate)
	{
	// Draw the graphs;
	DrawGraphs(); 
	
	// One time load of text and logo fields
	LoadTextAndLogo();

	clockInt=window.setInterval(function() {ClockTime()}, 1000);
	
	if (!data_refresh_rate) {data_refresh_rate=10; RefreshData();} 

	dataInt=window.setInterval(function() {RefreshData()}, (data_refresh_rate*1000));
	lwaitInt=window.setInterval(function() {RefreshLongestWait()}, 1000);
	}

function RefreshLongestWait() {
	CurrentEpoch=Math.floor(Date.now() / 1000);
	var LongestWaitOffset=CurrentEpoch-LongestWaitMarker;
	// console.log("Current offset (added to lwt): "+LongestWaitOffset);

	for (var i=0; i<all_widget_ids.length; i++)
		{
		if (all_widget_types[i]=="LONGEST_WAIT" && typeof LongestWaitTime==="number")
			{
			intToHMS((LongestWaitTime+LongestWaitOffset), all_widget_ids[i]);
			}	
		}
}

// Done outside of timed interval - no need to query repeatedly.
function LoadTextAndLogo() {
	for (var i=0; i<all_widget_ids.length; i++)
		{
		if (all_widget_types[i]=="TEXT" || all_widget_types[i]=="LOGO")
			{
			RefreshWidget(all_widget_ids[i], all_widget_types[i]);
			}	
		}
}

function RefreshData() 
	{
	/*
	var all_settings = document.querySelectorAll('[id$="_settings"]');
	var crap_str="";
	for (var j=0; j<all_settings.length; j++)
		{
		var widget_name=all_settings[j].id.replace("_settings", "");
		crap_str+=widget_name+"\n";
		}
	*/
	
	// Reset LongestWaitMarker
	// console.log("Resetting LongestWaitMarker to: "+LongestWaitMarker);

	for (var i=0; i<all_widget_ids.length; i++)
		{
		if (all_widget_types[i]=="TEXT" || all_widget_types[i]=="LOGO" || all_widget_types[i]=="OFFERED_CALLS" || all_widget_types[i]=="LONGEST_WAIT" || all_widget_types[i]=="LOST_CALLS" || all_widget_types[i]=="ANSWERED_CALLS")
			{
			RefreshWidget(all_widget_ids[i], all_widget_types[i]);
			}	

		if (all_widget_types[i]=="LIVE_QUEUE_INFO" || all_widget_types[i]=="AVG_QUEUE_INFO")
			{
			RefreshWidget(all_widget_ids[i], all_widget_types[i], "QUEUE");
			}	

		if (all_widget_types[i]=="LIVE_AGENT_INFO")
			{
			RefreshWidget(all_widget_ids[i], all_widget_types[i], "AGENT");
			}	

		if (all_widget_types[i]=="LIVE_AGENTS" || all_widget_types[i]=="LIVE_CALLS" || all_widget_types[i]=="LIVE_QUEUES")
			{
			RefreshWidget(all_widget_ids[i], all_widget_types[i], "TABLE");
			}	

		
		if (all_widget_types[i]=="N_WAITING_CALLS" || all_widget_types[i]=="LOST_CALLS_GRAPH" || all_widget_types[i]=="N_ANSWERED_CALLS" || all_widget_types[i]=="N_OFFERED_CALLS" || all_widget_types[i]=="N_LONGEST_WAIT" || all_widget_types[i]=="N_AGENTS_ON_CALL" || all_widget_types[i]=="LOST_PCT" || all_widget_types[i]=="ANSWERED_PCT" || all_widget_types[i]=="AGENTS_READY" || all_widget_types[i]=="SLA_LEVEL_PCT")
			{
			RefreshWidget(all_widget_ids[i], all_widget_types[i]);
			}	

		}
	}


function RefreshWidget(widget_id, widget_type, output_flag)
	{
	if (!widget_id || !widget_type) {console.log("Missing info to run widget update"); return false;}

	var widget_query="wallboard_report_id="+WallboardReportID+"&vicidial_queue_group="+MasterReportQueue+"&widget_id="+widget_id+"&widget_type="+widget_type;

	if (output_flag=="QUEUE")
		{
		var queue_setting=widget_id+"_settings_queue"; 
		if (document.getElementById(queue_setting))
			{
			// console.log("Widget "+queue_setting);
			var qField=document.getElementById(queue_setting);
			var lq=qField.selectedIndex;
			var queue_override=qField.options[qField.selectedIndex].value;
			var queue_override_name=qField.options[qField.selectedIndex].text;
			widget_query+="&live_queue="+queue_override+"&live_queue_name="+queue_override_name+"&debug="+lq;
			}
		else
			{
			console.log("Widget "+queue_setting+" does not exist.");
			}
		}

	// if (output_flag=="QUEUE")
	// {console.log(widget_query);}

	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp)
		{
		xmlhttp.open('POST', 'VERM_wallboard_widgets.php');
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(widget_query);
		xmlhttp.onreadystatechange = function()
			{
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
				{
				WidgetResults = xmlhttp.responseText;

				// FORMAT OUTPUT IN SPECIAL CASES
				if (widget_type=="LONGEST_WAIT")
					{
					if (WidgetResults!="--")
						{
						LongestWaitTime=parseInt(WidgetResults);
						}
					else
						{
						LongestWaitTime=WidgetResults;
						}
					LongestWaitMarker=Math.floor(Date.now() / 1000);
					// console.log("Longest Wait Timer is "+LongestWaitTime+", type "+typeof LongestWaitTime);
					intToHMS(LongestWaitTime, widget_id);
					}
				else if (widget_type=="SLA_LEVEL_PCT" || widget_type=="N_ANSWERED_CALLS" || widget_type=="N_AGENTS_ON_CALL" || widget_type=="AGENTS_READY" || widget_type=="N_WAITING_CALLS" || widget_type=="LIVE_QUEUE_INFO")
					{
					var graphRevision=WidgetResults.split("|");
					var graphIndex=parseInt(all_widget_ids.indexOf(widget_id));
					if (graphIndex>=0)
						{
						// console.log("Found "+widget_id+" at index "+graphIndex);
						// console.log(widget_query+" resulted in "+WidgetResults);						
						if (widget_type=="SLA_LEVEL_PCT") 
							{
							// Don't parse INT - you lose the decimal
							all_widget_data[graphIndex]=graphRevision[0];
							} 
						else 
							{
							all_widget_data[graphIndex]=parseInt(graphRevision[0]);
							}
						all_widget_scales[graphIndex]=parseInt(graphRevision[1]);
						if (widget_type=="LIVE_QUEUE_INFO")
							{
							var lost_pct=graphRevision[2];
							var queue_title=graphRevision[3];
							var widget_footer=widget_id+"_footer";
							var widget_title=widget_id+"_title";
							// console.log("SPECIAL EXCEPTION!!!!  "+widget_footer+", "+lost_pct+" %");
							if (document.getElementById(widget_footer) && lost_pct)
								{
								document.getElementById(widget_footer).innerHTML="<font class='wallboard_medium_text centered_text'>"+lost_pct+" %</font><BR><font class='wallboard_tiny_text italics centered_text'><?php echo _QXZ("Lost"); ?></font>";
								}
							if (document.getElementById(widget_title) && queue_title)
								{
								console.log("Setting title of widget "+widget_title+" to: "+queue_title);
								document.getElementById(widget_title).innerHTML="<font class='wallboard_small_text centered_text'>"+queue_title+"</font>";
								}
							else
								{
								console.log("Widget "+widget_title+" does not exist.");
								}
							}
						// console.log("Current data: "+all_widget_data);
						// console.log("Current scale: "+all_widget_scales);
						UpdateGraphs();
						}
					}
				else
					{
					document.getElementById(widget_id).innerHTML=WidgetResults;
					}
				}
			}
		delete xmlhttp;
		}

	}

// function RedrawGraph

function intToHMS(sec, widget_id) {
	var lwt=sec;
	if (sec && typeof sec==="number")
		{
		if (sec<600) {lwt=new Date(sec * 1000).toISOString().substr(15, 4);}
		else if (sec<3600) {lwt=new Date(sec * 1000).toISOString().substr(14, 5);}
		else if (sec<36000) {lwt=new Date(sec * 1000).toISOString().substr(12, 7);}
		else {lwt=new Date(sec * 1000).toISOString().substr(11, 8);}
		}
	else if (!sec)
		{
		lwt="0:00";
		}
	document.getElementById(widget_id).innerHTML="<div class='centered_text'><font class='wallboard_extra_large_text bold centered_text'>"+lwt+"</font></div>";
}

function SwitchView()
	{
	var ActivePage=ViewTicker%ViewCount;
	GoToView(ActivePage);
	ViewTicker++;
	}

function GoToView(CurrentPage, clickbutton)
	{
	// radio button to check
	document.getElementById('displayed_view_name').innerHTML=view_names[CurrentPage];
	ActiveRadio="toggler_view_"+CurrentPage;
	if (document.getElementById(ActiveRadio))
		{
		document.getElementById(ActiveRadio).checked=true;
		}

	// By "Go", really it's just making the active span visible and hiding the other(s)
	for (var i=0; i<ViewCount; i++)
		{
		var span_ID="wallboard_view_"+i;
		if (i==CurrentPage)
			{
			document.getElementById(span_ID).style.display='block';
			}
		else
			{
			document.getElementById(span_ID).style.display='none';
			}
		}
	// If this is triggered from someone changing the page...
	if (clickbutton) {StopReportRefresh();}
	}

function ShowWidgetSettings(e, widget_id, vw) 
	{
	widget_id+="_settings_window";
	if (!document.getElementById(widget_id))
		{
		alert("<?php echo _QXZ("ERROR: contact your administrator - settings for"); ?> "+widget_id+" <?php echo _QXZ("does not exist"); ?>.");
		// return false;
		}

	StopReportRefresh();
	document.getElementById(widget_id).style.display="block";
	document.getElementById(widget_id).style.left = "35vw";
	document.getElementById(widget_id).style.top = "30vh";
	document.getElementById(widget_id).style.width = "30vw";
	document.getElementById(widget_id).style.height = "30vh";

/*
	pixelWidth=Math.round((window.innerWidth*vw)/100);
	// alert(pixelWidth);

	

	if (IE) { // grab the x-y pos.s if browser is IE
		tempX = event.clientX + document.body.scrollLeft+250
		tempY = event.clientY + document.body.scrollTop
	} else {  // grab the x-y pos.s if browser is NS
		tempX = e.pageX
		tempY = e.pageY
	}  
	// catch possible negative values in NS4
	if (tempX < 0){tempX = 0}
	if (tempY < 0){tempY = 0}  
	// show the position values in the form named Show
	// in the text fields named MouseX and MouseY

	//tempX+=20;
	tempX-=pixelWidth;
	if (tempX<0) {tempX=0;}

	document.getElementById(widget_id).style.display="block";
	document.getElementById(widget_id).style.left = tempX + "px";
	document.getElementById(widget_id).style.top = tempY + "px";
	// alert(widget_id+"\n\n("+tempX+", "+tempY+")");
*/
	}

function SaveChanges() 
	{
	}

function UpdateWidgetSettings(formName)
	{
	var update_query="wallboard_report_id="+WallboardReportID+"&widget_type=UPDATE_WIDGET";
	var widget_alarms="";
	var widget_id=formName.replace(/_FORM$/, "");
	update_query+="&widget_id="+widget_id;

	var all_settings=document.getElementById(formName).elements;
	console.log("Widget ID settings "+widget_id+" contains:");
	for (var i=0; i<all_settings.length; i++) 
		{
		var fieldName=all_settings[i].id;
		var fieldValue=all_settings[i].value.replace(",", "");
		// fieldRegExp="/^"+widget_id+"/";
		var fieldPrefix=widget_id+"_settings_";
		fieldRegExp=new RegExp(fieldPrefix);
		if (fieldName.match(fieldRegExp)) // 
			{
			var VarName=fieldName.replace(fieldPrefix, "");

			var alarmStr="(red|yellow)_alarm$";
			var alarmRegExp=new RegExp(alarmStr, "gi");
			if (VarName.match(alarmRegExp))
				{
				widget_alarms+=VarName+","+fieldValue+"|";
				}
			else
				{
				VarName="widget_"+VarName;
				console.log(VarName+" = "+fieldValue);
				update_query+="&"+VarName+"="+fieldValue;
				}
			}
		}
	update_query+="&widget_alarms="+widget_alarms;
	console.log("VERM_wallboard_widgets.php?"+update_query); 

	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp)
		{
		xmlhttp.open('POST', 'VERM_wallboard_widgets.php');
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(update_query);
		xmlhttp.onreadystatechange = function()
			{
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
				{
				var UpdateWidgetResults = xmlhttp.responseText;
				}
			}
		delete xmlhttp;
		}

	// Reload widget
	/*
	if (!UpdateWidgetResults || UpdateWidgetResults==0)
		{
		alert("Update failed - no changes made");
		return false;
		}
	else if (UpdateWidgetResults>1)
		{
		alert("Update affected multiple ("+UpdateWidgetResults+") widgets - contact admin");
		return false;
		}
	else
		{
		for (var i=0; i<all_widget_ids.length; i++)
			{
			if (all_widget_ids[i]==widget_id)
				{
				console.log("Refreshing widget "+widget_id);
				RefreshWidget(all_widget_ids[i], all_widget_types[i]);
				}
			}
		}
	*/
	}

function StopStart(b_value)
	{
	console.log(b_value);
	if (b_value=="play_button")
		{
		//document.getElementById('wallboard_play_pause').value="\u25a0";
		//document.getElementById('wallboard_play_pause').className="stop_button";
		StartReportRefresh();
		}
	else
		{
		//document.getElementById('wallboard_play_pause').value="\u25b6";
		//document.getElementById('wallboard_play_pause').className="play_button";
		StopReportRefresh();
		}
	// console.log(document.getElementById('wallboard_play_pause').className);
	}

/*
function ToggleVisibility(span_name) {
	var span_vis = document.getElementById(span_name).style;
	if (span_vis.display=='none') { span_vis.display = 'block'; } else { span_vis.display = 'none'; }
}
*/
</script>
</head>
<BODY onLoad='StartDataRefresh(); StartReportRefresh();' BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<table style="width:100vw" border='0' cellpadding='0' cellspacing='0'>
<tr class='wallboard_title_row' valign='middle'>
	<td align='left' style="width:50vw" class='wallboard_title_cell'>&nbsp;&nbsp;&nbsp;
		<?php
		echo "$queue_group_name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		for ($i=0; $i<$report_views; $i++) 
			{
			echo "<input type='radio' name='view_toggler' id='toggler_view_".$i."' class='view_button' onClick=\"GoToView($i, 'stop refreshing')\"".($i==0 ? " checked" : "").">&nbsp;";
			}
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='button' id='wallboard_play_pause' class='stop_button' onClick='StopStart(this.className)' value='&#9632;'>";
		?>
	</td>
	<td align='right' style="width:50vw">
	<?php
	echo "$report_name - <span id='displayed_view_name' style='position:relative'>$current_page</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='VERM_admin.php'>[X]</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
	#echo "<img src='images/open_folder_icon.png'>"
	# echo "<a href='VERM_admin.php?vicidial_queue_group='$vicidial_queue_group'><img src='images/close_wallboard.png'></a>";
	?>
	</td>
</tr>
<tr>
	<td colspan='2'>
<!-- ALL VIEWS/PAGES GO HERE //-->
<?php
$settings_HTML="";
for ($v=0; $v<count($wallboard_views); $v++)
	{
	if ($v==0) {$vdisplay="block"; $zIndex="1";} else {$vdisplay="none"; $zIndex="0";}
	echo "<span id='wallboard_view_".$v."' style='display:".$vdisplay.";z-index:".$zIndex."'>\n";

	$table_width=100;
	echo "<table style='width:".$table_width."vw' border='0'>";

	# Make header/footer column to define widths
	echo "<tr height='1'>";
	$total_width=0;
	$columns=$wallboard_views[$v]["columns"]; 
	for ($p=1; $p<=$columns; $p++)
		{
		$current_column_width=round($table_width*$p/$columns)-$total_width;
		echo "<td style='width:".$current_column_width."vw'></td>\n";
		$total_width+=$current_column_width;
		}
	echo "</tr>\n";

	$view_id=$wallboard_views[$v]["view_id"];
	if ($view_widgets["$view_id"])
		{
		# print_r($view_widgets["$view_id"]);
		$current_cell_position=0;
		$ins_stmts="<!--\n";
		foreach($view_widgets["$view_id"] as $widget_index => $widget_params)
			{
			$widget_id=$widget_params["widget_id"];
			$widget_width=$widget_params["widget_width"];
			$widget_type=$widget_params["widget_type"];
			$widget_title=$widget_params["widget_title"];
			$widget_is_row=$widget_params["widget_is_row"];
			$widget_queue=$widget_params["widget_queue"];
			$widget_rowspan=$widget_params["widget_rowspan"];
			$widget_order=$widget_params["widget_order"];
			$widget_sla_level=$widget_params["widget_sla_level"];
			if (!$widget_rowspan) {$widget_rowspan=1;}

			$ins_stmts.="INSERT INTO wallboard_widgets(widget_id, view_id, widget_width, widget_type, widget_title, widget_is_row, widget_queue, widget_rowspan, widget_sla_level, widget_colors, widget_colors2, widget_order) VALUES ('$widget_id', '$view_id', '$widget_width', '$widget_type', '$widget_title', '$widget_is_row', '$widget_queue', '$widget_rowspan', '$widget_sla_level', '$widget_colors', '$widget_colors2', '$widget_order');\n";

			if (!$widget_title) {$widget_title="&nbsp;";}
			#if (!$widget_title && !preg_match('/CLOCK|TEXT|LOGO/', $widget_type))
			#	{
			#	$widget_title=ucwords(strtolower(preg_replace('/_/', ' ', $widget_type)));
			#	}

			if ($widget_is_row=="Y") 
				{
				$widget_width=$columns;
				$widget_height=($widget_rowspan*200)."px";
				}
			else
				{
				$widget_height="200px";
				}
			if ($widget_width>$columns) {$widget_width=$columns;} # Just in case.

			if ($current_cell_position+$widget_width>$columns)
				{
				# End table row, fill out remaining cells, start new row, if next widget will cause the row to go over
				if($current_cell_position<$columns)
					{
					echo "<td colspan='".($columns-$current_cell_position)."'>&nbsp;</td>\n";
					}
				echo "</tr>\n";
				$current_cell_position=0;
				}
			
			if ($current_cell_position==0) {echo "<tr>";}

			### IMPORTANT PART - THE ACTUAL DATA DISPLAY
			echo "<td class='widget_cell' colspan='".$widget_width."' style='height:".$widget_height."'>";
			echo "<table class='widget_contents".(preg_match('/^OFFERED_CALLS$|^LONGEST_WAIT$|^LOST_CALLS$|^ANSWERED_CALLS$/', $widget_type) ? " shaded" : "")." topalign' width='100%' height='100%' border='0'>";
			echo "	<tr class='widget_cell_title_bar' height='20'>\n";
			echo "		<th width='*'>$widget_title</th>";
			echo "		<td width='25'><input type='button' class='widget_edit_button' value='&#9998;' onClick=\"ShowWidgetSettings(event, '$widget_id', ".$current_column_width.")\"></td>"; # <img onClick=\"ShowWidgetSettings(event, '$widget_id', ".$current_column_width.")\" src='images/edit_widget.png' width='20' height='20'>
			echo "	</tr>\n";
			echo "	<tr>";
			echo "		<td id='".$widget_id."' class='widget_contents".(preg_match('/^LIVE_CALLS$|^LIVE_AGENTS$/', $widget_type) ? " topalign" : "")."' colspan='2'>";
			if (preg_match('/^LIVE_AGENT_INFO$|^LIVE_QUEUE_INFO$|^AVG_QUEUE_INFO$/', $widget_type))
				{
				echo "<div id='".$widget_id."_title' class='centered_text' style='text-overflow: ellipsis;'><font class='wallboard_small_text centered_text'>$widget_queue</font></div>\n";
				echo "<div id='".$widget_id."_graph' class='centered_text'></div>";
				echo "<div id='".$widget_id."_footer' class='centered_text'></div>";
				}
			else
				{
				echo "&nbsp;";
				}
			# LIVE_QUEUE_INFO
			# echo "<span id='".$widget_id."_".$widget_type."'></span>";
			echo "		</td>";
			echo "	</tr>\n";
			echo "</table>";
			echo "</td>";

			# Generate each widget's settings
			# Add save option
			$settings_HTML.="<div class='widget_settings' id='".$widget_id."_settings_window' style='display:none; width:".$current_column_width."vw; height:30vh'>";
			$settings_HTML.="<form name='".$widget_id."_FORM' id='".$widget_id."_FORM'>";
			$settings_HTML.="<table width='100%'>";
			$settings_HTML.="<tr>";
			$settings_HTML.="<th class='widget_settings_title_cell'>$widget_title</th>";
			$settings_HTML.="<td align='right'><a onClick=\"javascript:document.getElementById('".$widget_id."_settings_window').style='none'\">[X]</a></td>";
			$settings_HTML.="<tr>\n";
			$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Title")."</td>\n";
			$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_title' id='".$widget_id."_settings_title' size='15' class='widget_settings_form_field' value='$widget_title'></td>\n";
			$settings_HTML.="</tr>\n";

			/* Forget this for now - just follow dialer's logo logic based on system color settings
			if ($widget_type=="LOGO")
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>Image</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_logo' id='".$widget_id."_settings_logo' size='15' class='widget_settings_form_field' value='$widget_title'></td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell' colspan='2'>** needs to be uploaded to your webserver in the images directory, and the filename needs to begin with vicidial_admin_web_logo</td>\n";
				$settings_HTML.="</tr>\n";
				}
			*/

			if ($widget_type=="TEXT")
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Text")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_text' id='".$widget_id."_settings_text' size='15' class='widget_settings_form_field' value='$widget_title'></td>\n";
				$settings_HTML.="</tr>\n";
				}
			
			##### Color section.  Shoot me.
			if (preg_match('/^OFFERED_CALLS$|^LONGEST_WAIT$|^LOST_CALLS$|^ANSWERED_CALLS$|^N_WAITING_CALLS$|^LOST_CALLS_GRAPH$|^N_ANSWERED_CALLS$|^N_OFFERED_CALLS$|^N_LONGEST_WAIT$|^N_AGENTS_ON_CALL$|^LOST_PCT$|^ANSWERED_PCT$|^AGENTS_READY$|^SLA_LEVEL_PCT$|^LIVE_QUEUE_INFO$|^AVG_QUEUE_INFO$/', $widget_type))
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_yellow_alarm' id='".$widget_id."_settings_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_red_alarm' id='".$widget_id."_settings_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("COLOR")."</td>\n";
				$settings_HTML.="<td align='left'><input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row1_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color').value='$SSstd_row1_background'\">&nbsp;";
				$settings_HTML.="<input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row2_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color').value='$SSstd_row2_background'\">&nbsp;";
				$settings_HTML.="<input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row3_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color').value='$SSstd_row3_background'\">&nbsp;";
				$settings_HTML.="<input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row4_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color').value='$SSstd_row4_background'\">&nbsp;";
				$settings_HTML.="<input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row5_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color').value='$SSstd_row5_background'\">";
				$settings_HTML.="<input type='hidden' name='".$widget_id."_settings_color' id='".$widget_id."_settings_color'>";
				$settings_HTML.="</td>\n";
				$settings_HTML.="</tr>\n";
				}
			
			if(preg_match('/^LIVE_QUEUE_INFO$/', $widget_type))
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("2nd COLOR")."</td>\n";
				$settings_HTML.="<td align='left'><input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row1_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color2').value='$SSstd_row1_background'\">&nbsp;";
				$settings_HTML.="<input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row2_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color2').value='$SSstd_row2_background'\">&nbsp;";
				$settings_HTML.="<input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row3_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color2').value='$SSstd_row3_background'\">&nbsp;";
				$settings_HTML.="<input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row4_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color2').value='$SSstd_row4_background'\">&nbsp;";
				$settings_HTML.="<input type='button' class='color_swatch_button' style='background-color:#".$SSstd_row5_background."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_color2').value='$SSstd_row5_background'\">";
				$settings_HTML.="<input type='hidden' name='".$widget_id."_settings_color2' id='".$widget_id."_settings_color2'>";
				$settings_HTML.="</td>\n";
				$settings_HTML.="</tr>\n";
				}
			
			if(preg_match('/^LIVE_AGENTS$/', $widget_type)) # |^LIVE_CALLS$|^LIVE_QUEUES$
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Pause yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_pause_yellow_alarm' id='".$widget_id."_settings_pause_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Pause red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_pause_red_alarm' id='".$widget_id."_settings_pause_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Call yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_call_yellow_alarm' id='".$widget_id."_settings_call_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Call red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_call_red_alarm' id='".$widget_id."_settings_call_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				}
			
			if(preg_match('/^LIVE_CALLS$/', $widget_type)) # |^LIVE_CALLS$|^LIVE_QUEUES$
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Wait yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_wait_yellow_alarm' id='".$widget_id."_settings_wait_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Wait red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_wait_red_alarm' id='".$widget_id."_settings_wait_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Call yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_call_yellow_alarm' id='".$widget_id."_settings_call_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Call red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_call_red_alarm' id='".$widget_id."_settings_call_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				}

			if(preg_match('/^LIVE_QUEUES$/', $widget_type)) # |^LIVE_CALLS$|^LIVE_QUEUES$
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents on queue yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_on_queue_yellow_alarm' id='".$widget_id."_settings_agents_on_queue_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents on queue red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_on_queue_red_alarm' id='".$widget_id."_settings_agents_on_queue_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents ready yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_ready_yellow_alarm' id='".$widget_id."_settings_wait_agents_ready_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents ready red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_ready_red_alarm' id='".$widget_id."_settings_agents_ready_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents on pause yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_on_pause_yellow_alarm' id='".$widget_id."_settings_wait_agents_on_pause_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents on pause red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_on_pause_red_alarm' id='".$widget_id."_settings_agents_on_pause_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents busy yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_busy_yellow_alarm' id='".$widget_id."_settings_wait_agents_busy_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents busy red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_busy_red_alarm' id='".$widget_id."_settings_agents_busy_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents unknown yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_unknown_yellow_alarm' id='".$widget_id."_settings_wait_agents_unknown_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Agents unknown red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_agents_unknown_red_alarm' id='".$widget_id."_settings_agents_unknown_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Waiting calls yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_waiting_calls_yellow_alarm' id='".$widget_id."_settings_wait_waiting_calls_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Waiting calls red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_waiting_calls_red_alarm' id='".$widget_id."_settings_waiting_calls_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Inbound agents yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_inbound_agents_yellow_alarm' id='".$widget_id."_settings_wait_inbound_agents_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Inbound agents red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_inbound_agents_red_alarm' id='".$widget_id."_settings_inbound_agents_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Outbound agents yellow alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_outbound_agents_yellow_alarm' id='".$widget_id."_settings_wait_outbound_agents_yellow_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Outbound agents red alarm")."</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><input type='text' name='".$widget_id."_settings_outbound_agents_red_alarm' id='".$widget_id."_settings_outbound_agents_red_alarm' size='15' class='widget_settings_form_field'></td>\n";
				$settings_HTML.="</tr>\n";
				}
			#### END COLOR

			# if ($widget_queue) { - Might be easier
			if (preg_match('/^LIVE_QUEUE_INFO$|^AVG_QUEUE_INFO$/', $widget_type)) 
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Queue").":</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><select name='".$widget_id."_settings_queue' id='".$widget_id."_settings_queue' class='widget_settings_form_field'>\n";
				foreach ($queue_dropdown_array as $queue_id => $queue_name)
					{
					if ($widget_queue==$queue_id) {$selected="selected";} else {$selected="";}
					$settings_HTML.="<option value='$queue_id'$selected>$queue_name</option>\n";
					}
				$settings_HTML.="</select></td>\n";
				$settings_HTML.="</tr>\n";
				}
			if (preg_match('/^LIVE_AGENT_INFO$/', $widget_type)) 
				{
				$settings_HTML.="<tr>\n";
				$settings_HTML.="<td align='right' class='widget_settings_cell'>"._QXZ("Queue").":</td>\n";
				$settings_HTML.="<td align='left' class='widget_settings_cell'><select name='".$widget_id."_settings_agent' id='".$widget_id."_settings_agent' class='widget_settings_form_field'>\n";
				foreach ($all_viewable_agents as $agent_id => $agent_name)
					{
					if ($widget_agent==$agent_id) {$selected="selected";} else {$selected="";}
					$settings_HTML.="<option value='$agent_id'$selected>$agent_name</option>\n";
					}
				$settings_HTML.="</select></td>\n";
				$settings_HTML.="</tr>\n";
				}
			$settings_HTML.="<tr>\n";
			$settings_HTML.="<th colspan='2'><input class='widget_form_button' type='button' value='"._QXZ("SAVE")."' onClick=\"UpdateWidgetSettings('".$widget_id."_FORM')\">&nbsp;&nbsp;<input class='widget_form_button' type='button' value='"._QXZ("CLOSE")."' onClick=\"javascript:document.getElementById('".$widget_id."_settings_window').style='none'\"></th>\n";
			$settings_HTML.="</tr>\n";
			$settings_HTML.="</table>";
			$settings_HTML.="</form>";
			$settings_HTML.="</div>\n";


			$current_cell_position+=$widget_width;
			}
		
		$ins_stmts.="//-->\n";

		while($current_cell_position<$columns)
			{
			echo "<td class='nothing'>&nbsp;</td>";
			$current_cell_position++;
			}
		
		echo "</tr>";
		}
	
	#"widget_type" => "LOGO",
	#"widget_width" => 2,
	#"widget_title" => "",
	#"widget_order" => 1,
	#"widget_is_row" => "N"

	# QUERY WIDGETS TABLE USING WALLBOARD_ID and VIEW_ID
	# echo 
	echo "</table>\n";
echo $ins_stmts;
	echo "</span>\n";
	}
?>
	
	</td>
</tr>
</table>
<?php echo $settings_HTML; ?>
<div style='display:block' id='test_gauge'></div>

</body>

</html>
