<?php
# AST_rt_monitor_log_report.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 170513-0040 - First build
# 170822-2231 - Modified to use screen colors
# 180507-2315 - Added new help display
# 180712-1508 - Fix for rare allowed reports issue
# 191013-0906 - Fixes for PHP7
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
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["managers"]))					{$managers=$_GET["managers"];}
	elseif (isset($_POST["managers"]))			{$managers=$_POST["managers"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["order_by"]))				{$order_by=$_GET["order_by"];}
	elseif (isset($_POST["order_by"]))	{$order_by=$_POST["order_by"];}
if (isset($_GET["agent"]))			{$agent=$_GET["agent"];}
	elseif (isset($_POST["agent"]))	{$agent=$_POST["agent"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["report_display_type"]))				{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

if (strlen($shift)<2) {$shift='ALL';}
$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($campaign)) {$campaign = array();}
if (!isset($managers)) {$managers = array();}
if (!isset($users)) {$users = array();}
if (!isset($report_display_type)) {$report_display_type = "HTML";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (!isset($order_by)) {$order_by="monitor_start_time-asc";} 

$report_name = 'Real-Time Monitoring Log Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method FROM system_settings;";
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
		$VDdisplayMESSAGE = "You are not allowed to view reports";
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
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
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

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

$stmt="SELECT campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
if ($DB) {$MAIN.="$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$campaigns_string='|';
$campaigns_selected=count($campaign);
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$campaigns[$i] =		$row[0];
	$campaigns_string .= "$campaigns[$i]|";
	for ($j=0; $j<$campaigns_selected; $j++) {
		if ($campaign[$j] && $campaigns[$i]==$campaign[$j]) {$campaign_name_str.="$campaigns[$i] - $campaign_names[$i], ";}
		if ($campaign[$j]=="--ALL--") {$campaigns_selected_str.="'$campaigns[$i]', ";}
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
	if ($all_users) {$user_list[$i]=$row[0];}
	$i++;
	}


$i=0;
$managers_string='|';
$managers_ct = count($managers);
while($i < $managers_ct)
	{
	$managers_string .= "$managers[$i]|";
	$i++;
	}

$stmt="SELECT user, full_name from vicidial_users where user_level>=8 $LOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$managers_to_print = mysqli_num_rows($rslt);
$i=0;
$manager_array=array(); # For quick full-name reference
$manager_list=array();
$manager_names=array();
while ($i < $managers_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$manager_list[$i]=$row[0];
	$manager_names[$i]=$row[1];
	$manager_array["$row[0]"]=$row[1];
	if ($all_managers) {$manager_list[$i]=$row[0];}
	$i++;
	}

$i=0;
$campaign_string='|';
$campaign_ct = count($campaign);
while($i < $campaign_ct)
	{
	if (in_array("--ALL--", $campaign))
		{
		$campaign_string = "--ALL--";
		$campaign_SQL .= "'$campaign[$i]',";
		$campaignQS = "&campaign[]=--ALL--";
		}
	else if ( (strlen($campaign[$i]) > 0) and (preg_match("/\|$campaign[$i]\|/",$campaigns_string)) )
		{
		$campaign_string .= "$campaign[$i]|";
		$campaign_SQL .= "'$campaign[$i]',";
		$campaignQS .= "&campaign[]=$campaign[$i]";
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
$manager_string='|';
$manager_ct = count($managers);
while($i < $manager_ct)
	{
	$manager_string .= "$managers[$i]|";
	$manager_SQL .= "'$managers[$i]',";
	$managerQS .= "&managers[]=$managers[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$manager_string) ) or ($manager_ct < 1) )
	{$manager_SQL = "";}
else
	{
	$manager_SQL = preg_replace('/,$/i', '',$manager_SQL);
	$manager_SQL = "and manager_user IN($manager_SQL)";
	}

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$rerun_rpt_URL="$PHP_SELF?query_date=$query_date&end_date=$end_date&report_display_type=$report_display_type$campaignQS$userQS$managerQS&SUBMIT=$SUBMIT";

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

$screen_color_stmt="select admin_screen_colors from system_settings";
$screen_color_rslt=mysql_to_mysqli($screen_color_stmt, $link);
$screen_color_row=mysqli_fetch_row($screen_color_rslt);
$agent_screen_colors="$screen_color_row[0]";

if ($agent_screen_colors != 'default')
	{
	$asc_stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$agent_screen_colors';";
	$asc_rslt=mysql_to_mysqli($asc_stmt, $link);
	$qm_conf_ct = mysqli_num_rows($asc_rslt);
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

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: black; background-color: #99FF99}\n";
$HEADER.="   .red {color: black; background-color: #FF9999}\n";
$HEADER.="   .orange {color: black; background-color: #FFCC99}\n";
$HEADER.=".records_list_x\n";
$HEADER.="	{\n";
$HEADER.="	background-color: #B9CBFD;\n";
$HEADER.="	}\n";
$HEADER.=".records_list_x:hover{background-color: #E6E6E6;}\n";
$HEADER.="\n";
$HEADER.=".records_list_y\n";
$HEADER.="	{\n";
$HEADER.="	background-color: #9BB9FB;\n";
$HEADER.="	}\n";
$HEADER.=".records_list_y:hover{background-color: #E6E6E6;}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";

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
$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#rt_monitor_log_report$NWE\n";

#$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR BGCOLOR=\"#".$SSframe_background."\"><TD VALIGN=TOP> <b>"._QXZ("Dates").":</b><BR>";
$MAIN.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";


$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

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

$MAIN.="<BR><b>"._QXZ("to")."</b><BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

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
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP> <b>"._QXZ("Campaigns").":</b> <BR>";
$MAIN.="<SELECT SIZE=5 NAME=campaign[] multiple>\n";
#if  (preg_match('/\-\-ALL\-\-/',$campaign_string))
#	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
#else
#	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$MAIN.="<option value=\"--ALL--\"".(in_array("--ALL--", $campaign) ? " selected" : "").">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";
$o=0;
# $campaign_SQL="";
while ($campaigns_to_print > $o)
{
	$selected="";
	if (in_array($campaigns[$o], $campaign) && !in_array("--ALL--", $campaign)) {
		$selected="selected";
	}
	if (in_array($campaigns[$o], $campaign) || in_array("--ALL--", $campaign)) {
# 		$campaign_SQL.="'$campaigns[$o]',";
	}
	$MAIN.="<option $selected value=\"$campaigns[$o]\">$campaigns[$o]</option>\n";
	$o++;
}
# $campaign_SQL=preg_replace("/,$/", "", $campaign_SQL);
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP><B>"._QXZ("Managers").": </B><BR>";
$MAIN.="<SELECT SIZE=5 NAME=managers[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$managers_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL MANAGERS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL MANAGERS")." --</option>\n";}
$o=0;
while ($managers_to_print > $o)
	{
	if  (preg_match("/$manager_list[$o]\|/i",$managers_string)) {$MAIN.="<option selected value=\"$manager_list[$o]\">$manager_list[$o] - $manager_names[$o]</option>\n";}
	  else {$MAIN.="<option value=\"$manager_list[$o]\">$manager_list[$o] - $manager_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP><B>"._QXZ("Users").": </B><BR>";
$MAIN.="<SELECT SIZE=5 NAME=users[] multiple>\n";
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

$MAIN.="<TD VALIGN=TOP>";
$MAIN.="<B>"._QXZ("Display as").":</B><BR><select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>&nbsp; ";
$MAIN.="<BR><BR><INPUT TYPE=submit NAME='SUBMIT' VALUE='"._QXZ("SUBMIT")."'><BR><BR><FONT FACE=\"Arial,Helvetica\" size=2><a href=\"$rerun_rpt_URL&order_by=$order_by&file_download=1\">"._QXZ("DOWNLOAD")."</a></FONT> | <FONT FACE=\"Arial,Helvetica\" size=2><a href=\"./admin.php?ADD=3111&group_id=$group[0]\">"._QXZ("MODIFY")."</a></FONT> | <FONT FACE=\"Arial,Helvetica\" size=2><a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a></FONT></TD></TR></TABLE>\n";
# $MAIN.="<TR><TD colspan='5'>";
$MAIN.="<PRE><FONT SIZE=2>\n\n";

if ($agents_selected==0 && $campaigns_selected==0)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT AN IN-GROUP AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	echo "$HEADER";
	require("admin_header.php");
	echo "$MAIN";
	}
else
	{
	$campaign_group_stmt="select closer_campaigns from vicidial_campaigns $WHEREcampaign_SQL";
	if ($DB) {echo "|$campaign_group_stmt|\n";}
	$campaign_group_rslt=mysql_to_mysqli($campaign_group_stmt, $link);
	$campaign_group_SQL="";
	while ($cg_row=mysqli_fetch_row($campaign_group_rslt)) {
		if (strlen(trim($cg_row[0]))>0) {
			$cg_row[0]=preg_replace("/^\s+|\s\-/", "", $cg_row[0]);
			$campaign_group_SQL.="'".preg_replace("/\s/", "','", $cg_row[0])."',";
		}		
	}
	$campaign_group_SQL=$group_SQL.",".$campaign_group_SQL;
	$campaign_group_SQL=preg_replace("/^,|,$/", "", $campaign_group_SQL);

	$campaign_string=preg_replace("/^\||\|$/", "", $campaign_string);
	$users_string=preg_replace("/^\||\|$/", "", $users_string);
	$manager_string=preg_replace("/^\||\|$/", "", $manager_string);

	$campaign_string=preg_replace("/\-\-ALL\-\-/", "--"._QXZ("ALL")."--", $campaign_string);
	$users_string=preg_replace("/\-\-ALL\-\-/", "--"._QXZ("ALL")."--", $users_string);
	$manager_string=preg_replace("/\-\-ALL\-\-/", "--"._QXZ("ALL")."--", $manager_string);

	$MAIN.=" "._QXZ("Date range", 11).":  $query_date "._QXZ("to")." $end_date\n";
	$MAIN.=" "._QXZ("Campaigns", 11).":  ".preg_replace("/\|/", ", ", $campaign_string)."\n";
	$MAIN.=" "._QXZ("Managers", 11).":  ".preg_replace("/\|/", ", ", $manager_string)."\n";
	$MAIN.=" "._QXZ("Agents", 11).":  ".preg_replace("/\|/", ", ", $users_string)."\n\n";

	$CSV_text.="\""._QXZ("Date range").":\",\"$query_date "._QXZ("to")." $end_date\"\n";
	$CSV_text.="\""._QXZ("Campaigns").":\",\"".preg_replace("/\|/", ", ", $campaign_string)."\"\n";
	$CSV_text.="\""._QXZ("Managers").":\",\"".preg_replace("/\|/", ", ", $manager_string)."\"\n";
	$CSV_text.="\""._QXZ("Agents").":\",\"".preg_replace("/\|/", ", ", $users_string)."\"\n\n";

	$order_by=preg_replace('/\-/', ' ', $order_by);

	$log_stmt="select * from vicidial_rt_monitor_log where monitor_start_time>='$query_date 00:00:00' and monitor_start_time<='$end_date 23:59:59' $campaign_SQL $manager_SQL $user_SQL order by $order_by";
	$log_rslt=mysql_to_mysqli($log_stmt, $link);
	if ($DB) {$MAIN.=$log_stmt."\n";}

	if (mysqli_num_rows($log_rslt)>0) {

		$ASCII_header="+---------------------+--------------------------------+-----------------+---------------+-----------------+--------------------------------+-----------------+--------------+---------------+---------+----------+---------------------+----------+---------+\n";
		$ASCII_text2="| <a href='$rerun_rpt_URL&order_by=monitor_start_time-asc'>"._QXZ("START TIME", 19)."</a> | <a href='$rerun_rpt_URL&order_by=manager_user-asc'>"._QXZ("MANAGER", 30)."</a> | <a href='$rerun_rpt_URL&order_by=manager_server_ip-asc'>"._QXZ("MANAGER SERVER", 15)."</a> | <a href='$rerun_rpt_URL&order_by=manager_phone-asc'>"._QXZ("MANAGER PHONE", 13)."</a> | <a href='$rerun_rpt_URL&order_by=manager_ip-asc'>"._QXZ("MANAGER IP", 15)."</a> | <a href='$rerun_rpt_URL&order_by=agent_user-asc'>"._QXZ("AGENT MONITORED", 30)."</a> | <a href='$rerun_rpt_URL&order_by=agent_server_ip-asc'>"._QXZ("AGENT SERVER", 15)."</a> | <a href='$rerun_rpt_URL&order_by=agent_status-asc'>"._QXZ("AGENT STATUS", 12)."</a> | <a href='$rerun_rpt_URL&order_by=agent_session-asc'>"._QXZ("AGENT SESSION", 13)."</a> | <a href='$rerun_rpt_URL&order_by=lead_id-asc'>"._QXZ("LEAD ID", 7)."</a> | <a href='$rerun_rpt_URL&order_by=campaign_id-asc'>"._QXZ("CAMPAIGN", 8)."</a> | <a href='$rerun_rpt_URL&order_by=monitor_end_time-asc'>"._QXZ("END TIME", 19)."</a> | <a href='$rerun_rpt_URL&order_by=monitor_sec-asc'>"._QXZ("LENGTH", 8)."</a> | <a href='$rerun_rpt_URL&order_by=monitor_type-asc'>"._QXZ("TYPE", 7)."</a> |\n";

		$CSV_text.="\""._QXZ("START TIME")."\",\""._QXZ("MANAGER")."\",\""._QXZ("MANAGER SERVER")."\",\""._QXZ("MANAGER PHONE")."\",\""._QXZ("MANAGER IP")."\",\""._QXZ("AGENT MONITORED")."\",\""._QXZ("AGENT SERVER")."\",\""._QXZ("AGENT STATUS")."\",\""._QXZ("AGENT SESSION")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("END TIME")."\",\""._QXZ("LENGTH")."\",\""._QXZ("TYPE")."\"\n";

		$ASCII_text=$ASCII_header.$ASCII_text2.$ASCII_header;

		$HTML_text ="<table width='1200' border='0' cellpadding='2' cellspacing='0'>";
		$HTML_text.="<TR BGCOLOR=BLACK>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=monitor_start_time-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("START TIME")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=manager_user-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("MANAGER")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=manager_server_ip-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("MANAGER SERVER")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=manager_phone-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("MANAGER PHONE")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=manager_ip-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("MANAGER IP")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=agent_user-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("AGENT MONITORED")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=agent_server_ip-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("AGENT SERVER")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=agent_status-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("AGENT STATUS")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=agent_session-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("AGENT SESSION")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=lead_id-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("LEAD ID")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=campaign_id-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("CAMPAIGN")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=monitor_end_time-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("END TIME")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=monitor_sec-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("LENGTH")."</a></FONT></B></TD>\n";
		$HTML_text.="<TD><B><a href='$rerun_rpt_URL&order_by=monitor_type-asc'><FONT FACE=\"Arial,Helvetica\" color=white size=1>"._QXZ("TYPE")."</a></FONT></B></TD>\n";
		$HTML_text.="</TR>\n";

		$q=0;
		while ($log_row=mysqli_fetch_array($log_rslt)) {
			if ($q%2==0) {$tdclass="$SSstd_row1_background";} else {$tdclass="$SSstd_row2_background";}
			$HTML_text.="<tr bgcolor='#".$tdclass."'>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["monitor_start_time"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1><a href='/vicidial/user_stats.php?user=".$log_row["manager_user"]."'>".$log_row["manager_user"]." - ".$manager_array["$log_row[manager_user]"]."</a></FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["manager_server_ip"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["manager_phone"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["manager_ip"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1><a href='/vicidial/user_stats.php?user=".$log_row["agent_user"]."'>".$log_row["agent_user"]." - ".$user_array["$log_row[agent_user]"]."</a></FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["agent_server_ip"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>"._QXZ($log_row["agent_status"])."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["agent_session"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["lead_id"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["campaign_id"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["monitor_end_time"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>".$log_row["monitor_sec"]."</FONT></B></TD>\n";
			$HTML_text.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1>"._QXZ("$log_row[monitor_type]")."</FONT></B></TD>\n";
			$HTML_text.="</TR>\n";
			$q++;

			$ASCII_text.="| ".sprintf("%-19s", $log_row["monitor_start_time"])." ";
			$ASCII_text.="| <a href='/vicidial/user_stats.php?user=".$log_row["manager_user"]."'>".sprintf("%-30s", substr($log_row["manager_user"]." - ".$manager_array["$log_row[manager_user]"], 0, 30))."</a> ";
			$ASCII_text.="| ".sprintf("%-15s", $log_row["manager_server_ip"])." ";
			$ASCII_text.="| ".sprintf("%-13s", $log_row["manager_phone"])." ";
			$ASCII_text.="| ".sprintf("%-15s", $log_row["manager_ip"])." ";
			$ASCII_text.="| <a href='/vicidial/user_stats.php?user=".$log_row["agent_user"]."'>".sprintf("%-30s", substr($log_row["agent_user"]." - ".$user_array["$log_row[agent_user]"], 0, 30))."</a> ";
			$ASCII_text.="| ".sprintf("%-15s", $log_row["agent_server_ip"])." ";
			$ASCII_text.="| ".sprintf("%-12s", _QXZ($log_row["agent_status"]))." ";
			$ASCII_text.="| ".sprintf("%-13s", $log_row["agent_session"])." ";
			$ASCII_text.="| ".sprintf("%-7s", $log_row["lead_id"])." ";
			$ASCII_text.="| ".sprintf("%-8s", $log_row["campaign_id"])." ";
			$ASCII_text.="| ".sprintf("%-19s", $log_row["monitor_end_time"])." ";
			$ASCII_text.="| ".sprintf("%-8s", $log_row["monitor_sec"])." ";
			$ASCII_text.="| ".sprintf("%-7s", _QXZ("$log_row[monitor_type]", 7))." |\n";

			$CSV_text.="\"".$log_row["monitor_start_time"]."\",";
			$CSV_text.="\"".substr($log_row["manager_user"]." - ".$manager_array["$log_row[manager_user]"], 0, 30)."\",";
			$CSV_text.="\"".$log_row["manager_server_ip"]."\",";
			$CSV_text.="\"".$log_row["manager_phone"]."\",";
			$CSV_text.="\"".$log_row["manager_ip"]."\",";
			$CSV_text.="\"".substr($log_row["agent_user"]." - ".$user_array["$log_row[agent_user]"], 0, 30)."\",";
			$CSV_text.="\"".$log_row["agent_server_ip"]."\",";
			$CSV_text.="\"".$log_row["agent_status"]."\",";
			$CSV_text.="\"".$log_row["agent_session"]."\",";
			$CSV_text.="\"".$log_row["lead_id"]."\",";
			$CSV_text.="\"".$log_row["campaign_id"]."\",";
			$CSV_text.="\"".$log_row["monitor_end_time"]."\",";
			$CSV_text.="\"".$log_row["monitor_sec"]."\",";
			$CSV_text.="\""._QXZ("$log_row[monitor_type]")."\"\n";

		}

		$HTML_text.="</TABLE>\n";

		$ASCII_text.=$ASCII_header;
	} else {
		$msg.=" *** NO RECORDS FOUND ***";
		$MAIN.=$msg;
	}

	if ($report_display_type=="HTML")
		{
		$MAIN.=$HTML_text.$HTML_text2;
		}
	else 
		{
		$MAIN.=$ASCII_text;
		}

	$MAIN.="</PRE></FONT>";

	$MAIN.="</FORM>";

	if ($file_download>0) 
		{
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_RT_monitor_log_report_$US$FILE_TIME.csv";
		$CSV_text=preg_replace('/\n +,/', ',', $CSV_text);
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
	else 
		{
		header("Content-type: text/html; charset=utf-8");

		echo "$HEADER";
		require("admin_header.php");
		echo "$MAIN";
		flush();
		}

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


?>

</BODY></HTML>
