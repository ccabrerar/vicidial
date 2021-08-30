<?php
# AST_inbound_forecasting.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 170428-1205 - First build
# 170504-2245 - Minor bug fixes
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180507-2315 - Added new help display
# 180712-1508 - Fix for rare allowed reports issue
# 191013-0856 - Fixes for PHP7
# 210823-0948 - Fix for security issue, added NONE option for campaigns
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

	function factorial($num) {
		$num=floor($num);
		if ($num>0) {
			$result=1;
			for ($x=1; $x<=$num; $x++) {
				$result*=$x;
			}
			return $result;
		} else {
			return 1;
		}
	}
	function erlsum($low, $limit, $erlangs) {
		$result=0;
		for ($n=$low; $n<=$limit; $n++) {
			$result+=pow($erlangs, $n)/(factorial($n));
		}
		return $result;
	}
	function adjustedGoS($erlangs, $GoS, $retry_rate, $lines) {
		$E[0]=$erlangs;
		$P[0]=$GoS;

		for ($q=0; $q<100; $q++) {
			$E[($q+1)]=$erlangs+($retry_rate/100)*$E[$q]*$P[$q];
			$P[($q+1)]=MathZDC((pow($E[($q+1)], $lines)/(factorial($lines))), (erlsum(0, $lines, $E[($q+1)])));

		}
		return $P[100];
	}

if (file_exists('options.php'))
	{
	require('options.php');
	}

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["drop_percent"]))				{$drop_percent=$_GET["drop_percent"];}
	elseif (isset($_POST["drop_percent"]))		{$drop_percent=$_POST["drop_percent"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["erlang_type"]))				{$erlang_type=$_GET["erlang_type"];}
	elseif (isset($_POST["erlang_type"]))	{$erlang_type=$_POST["erlang_type"];}
if (isset($_GET["actual_agents"]))				{$actual_agents=$_GET["actual_agents"];}
	elseif (isset($_POST["actual_agents"]))	{$actual_agents=$_POST["actual_agents"];}
if (isset($_GET["hourly_pay"]))				{$hourly_pay=$_GET["hourly_pay"];}
	elseif (isset($_POST["hourly_pay"]))	{$hourly_pay=$_POST["hourly_pay"];}
if (isset($_GET["revenue_per_sale"]))				{$revenue_per_sale=$_GET["revenue_per_sale"];}
	elseif (isset($_POST["revenue_per_sale"]))	{$revenue_per_sale=$_POST["revenue_per_sale"];}
if (isset($_GET["sale_chance"]))				{$sale_chance=$_GET["sale_chance"];}
	elseif (isset($_POST["sale_chance"]))	{$sale_chance=$_POST["sale_chance"];}
if (isset($_GET["retry_rate"]))				{$retry_rate=$_GET["retry_rate"];}
	elseif (isset($_POST["retry_rate"]))	{$retry_rate=$_POST["retry_rate"];}
if (isset($_GET["target_pqueue"]))			{$target_pqueue=$_GET["target_pqueue"];}
	elseif (isset($_POST["target_pqueue"]))	{$target_pqueue=$_POST["target_pqueue"];}
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

$report_name = 'Inbound Forecasting Report';
$db_source = 'M';

if ($ignore_afterhours=="checked") {$status_clause=" and status!='AFTHRS'";}

$HTML_text="";
$report_display_type="HTML";

# $actual_agents=preg_replace("/[^0-9.]/", "", $actual_agents);
# $actual_agents+=0;
# $hourly_pay=preg_replace("/[^0-9.]/", "", $hourly_pay);
# $hourly_pay+=0;
# $revenue_per_sale=preg_replace("/[^0-9.]/", "", $revenue_per_sale);
# $revenue_per_sale+=0;
# $sale_chance=preg_replace("/[^0-9.]/", "", $sale_chance);
# $sale_chance+=0;
# $retry_rate=preg_replace("/[^0-9.]/", "", $retry_rate);
# $retry_rate+=0;

switch ($erlang_type) {
	default:
	case "B":
		$erlB="checked";
		$erlC="";
		break;
	case "C":
		$erlB="";
		$erlC="checked";
		break;
}

$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

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
if ($DB) {$HTML_text.="$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$campaigns_string='|';
$campaigns_selected=count($campaign);
$campaigns=array();
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

$stmt="select group_id,group_name from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
$groups_string='|';
$groups_selected=count($group);
$groups=array();
$group_names=array();
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =		$row[0];
	$group_names[$i] =	$row[1];
	$groups_string .= "$groups[$i]|";
	for ($j=0; $j<$groups_selected; $j++) {
		if ($group[$j] && $groups[$i]==$group[$j]) {$group_name_str.="$groups[$i] - $group_names[$i], ";}
		if ($group[$j]=="--ALL--") {$groups_selected_str.="'$groups[$i]', "; $all_groups_selected="selected";}
	}
	$i++;
	}

$groups_selected_str=preg_replace('/, $/', '', $groups_selected_str);
$group_name_str=preg_replace('/, $/', '', $group_name_str);

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group) || !is_array($group)) {$group = array();}
if (!isset($drop_percent)) {$drop_percent = '3';}
if (!isset($campaign) || !is_array($campaign)) {$campaign = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$group=preg_replace('/[^-_0-9\p{L}]/u','',$group);
$campaign=preg_replace('/[^-_0-9\p{L}]/u','',$campaign);
$query_date = preg_replace('/[^-0-9]/','',$query_date);
$end_date = preg_replace('/[^-0-9]/','',$end_date);
$shift=preg_replace('/[^\p{L}]/u', '', $shift);
$erlang_type=preg_replace('/[^\p{L}]/u', '', $erlang_type);
$actual_agents=preg_replace("/[^0-9.]/", "", $actual_agents);
$hourly_pay=preg_replace("/[^0-9.]/", "", $hourly_pay);
$revenue_per_sale=preg_replace("/[^0-9.]/", "", $revenue_per_sale);
$sale_chance=preg_replace("/[^0-9.]/", "", $sale_chance);
$retry_rate=preg_replace("/[^0-9.]/", "", $retry_rate);
$target_pqueue=preg_replace("/[^0-9.]/", "", $target_pqueue);
$DB = preg_replace('/[^0-9]/','',$DB);
$submit=preg_replace('/[^-_0-9\p{L}]/u','',$submit);
$SUBMIT=preg_replace('/[^-_0-9\p{L}]/u','',$SUBMIT);
$file_download = preg_replace('/[^0-9]/','',$file_download);
$report_display_type=preg_replace('/[^_\p{L}]/u','',$report_display_type);

$drop_percent=preg_replace("/[^\.0-9]/", "", $drop_percent);
if ($drop_percent>100) {$drop_percent=100;}
$drop_rate=$drop_percent/100;
$pqueue_target=$target_pqueue/100;

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	if (in_array("--ALL--", $group))
		{
		$group_string = "--ALL--";
		$group_SQL .= "'$campaign[$i]',";
		$groupQS = "&group[]=--ALL--";
		}
	if ( (strlen($group[$i]) > 0) and (preg_match("/\|$group[$i]\|/",$groups_string)) )
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-NONE\-\-/',$group_string) ) or ($group_ct < 1) )
	{
	$group_SQL = "''";
	}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	}
if (strlen($group_SQL)<3) {$group_SQL="''";}

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
if ( (preg_match('/\-\-NONE\-\-/',$campaign_string) ) or ($campaign_ct < 1) )
	{
	$campaign_SQL = "''";
	}
else
	{
	$campaign_SQL = preg_replace('/,$/i', '',$campaign_SQL);
	}
if (strlen($campaign_SQL)<3) {$campaign_SQL="''";}

$stmt="select call_time_id,call_time_name from vicidial_call_times $whereLOGadmin_viewable_call_timesSQL order by call_time_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$times_to_print = mysqli_num_rows($rslt);
$i=0;
$call_times=array();
$call_time_names=array();
while ($i < $times_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$call_times[$i] =		$row[0];
	$call_time_names[$i] =	$row[1];
	$i++;
	}

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";


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
$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#inbound_forecasting$NWE\n";

#$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0 BORDER=0 WIDTH='1024'><TR BGCOLOR=\"#".$SSframe_background."\"><TD VALIGN=TOP> <b>"._QXZ("Dates").":</b><BR>";
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
$MAIN.="</script><BR><BR>";
$MAIN.="<b>"._QXZ("Target drop rate").":</b><BR><input type='text' size='3' maxlength='4' name='drop_percent' value='$drop_percent'>% ("._QXZ("optional").")";
# $MAIN.="<b>"._QXZ("Target queue prob.").":</b><input type='text' size='3' maxlength='4' name='target_pqueue' value='$target_pqueue'>% (C report)";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP> <b>"._QXZ("Campaigns").":</b> <BR>";
$MAIN.="<SELECT SIZE=5 NAME=campaign[] multiple>\n";
#if  (preg_match('/\-\-ALL\-\-/',$campaign_string))
#	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
#else
#	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$MAIN.="<option value=\"--ALL--\"".(in_array("--ALL--", $campaign) ? " selected" : "").">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";
$MAIN.="<option value=\"--NONE--\"".(in_array("--NONE--", $campaign) ? " selected" : "").">-- "._QXZ("NONE")." --</option>\n";
$o=0;
$campaign_SQL="";
while ($campaigns_to_print > $o)
{
	$selected="";
	if (in_array($campaigns[$o], $campaign) && !in_array("--ALL--", $campaign)) {
		$selected="selected";
	}
	if (in_array($campaigns[$o], $campaign) || in_array("--ALL--", $campaign)) {
		$campaign_SQL.="'$campaigns[$o]',";
	}
	$MAIN.="<option $selected value=\"$campaigns[$o]\">$campaigns[$o]</option>\n";
	$o++;
}
$campaign_SQL=preg_replace("/,$/", "", $campaign_SQL);
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";

$MAIN.="<TD VALIGN=TOP> <B>"._QXZ("In-groups").":</B><BR><SELECT SIZE=5 NAME=group[] multiple>\n";
$MAIN.="<option value=\"--ALL--\"".(in_array("--ALL--", $group) ? " selected" : "").">--"._QXZ("ALL INGROUPS")."--</option>\n";
$MAIN.="<option value=\"--NONE--\"".(in_array("--NONE--", $group) ? " selected" : "").">-- "._QXZ("NONE")." --</option>\n";

$o=0;
while ($groups_to_print > $o)
	{
	$selected="";
	if (in_array($groups[$o], $group) && !in_array("--ALL--", $group)) {$selected="selected";}

	$MAIN.="<option $selected value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD>\n";
# $MAIN.="<TD VALIGN=TOP><b>"._QXZ("Cost analysis stats (optional)").":</b><BR>";
# $MAIN.=_QXZ("Actual agents").":<BR><INPUT TYPE=TEXT NAME='actual_agents' SIZE=5 MAXLENGTH=5 VALUE=\"$actual_agents\"><BR>";
# $MAIN.=_QXZ("Hourly pay per agent").":<BR>\$<INPUT TYPE=TEXT NAME='hourly_pay' SIZE=5 MAXLENGTH=5 VALUE=\"$hourly_pay\"><BR>";
# $MAIN.=_QXZ("Revenue per sale").":<BR>\$<INPUT TYPE=TEXT NAME='revenue_per_sale' SIZE=5 MAXLENGTH=5 VALUE=\"$revenue_per_sale\"><BR>";
# $MAIN.=_QXZ("Chance of sale").":<BR><INPUT TYPE=TEXT NAME='sale_chance' SIZE=5 MAXLENGTH=5 VALUE=\"$sale_chance\">%<BR>";
# $MAIN.=_QXZ("Retry rate").":<BR><INPUT TYPE=TEXT NAME='retry_rate' SIZE=5 MAXLENGTH=5 VALUE=\"$retry_rate\">% (B "._QXZ("reports only").")";
# $MAIN.="</TD>\n";
$MAIN.="<TD VALIGN=TOP>";
$MAIN.="<input type='hidden' name='erlang_type' value='B'>";
$MAIN.="<input type='hidden' name='report_display_rate' value='HTML'>";

# $MAIN.="<b>"._QXZ("Erlang type").":</b><BR>";
# $MAIN.="<input type='radio' name='erlang_type' value='B' $erlB>B - "._QXZ("inbound/call loss stats")."<BR>";
# $MAIN.="<input type='radio' name='erlang_type' value='C' $erlC>C - "._QXZ("outbound/waiting time stats")."<BR><BR>";

# $MAIN.="<select name='report_display_type'>";
# if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
# $MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>&nbsp; ";


if ($IDR_calltime_available==1)
	{
	$MAIN.="<SELECT SIZE=1 NAME='shift'>\n";
	$MAIN.="<option value=\"\">--</option>\n";
	$o=0;
	while ($times_to_print > $o)
		{
		if ($call_times[$o] == $shift) {$MAIN.="<option selected value=\"$call_times[$o]\">$call_times[$o] - $call_time_names[$o]</option>\n";}
		else {$MAIN.="<option value=\"$call_times[$o]\">$call_times[$o] - $call_time_names[$o]</option>\n";}
		$o++;
		}
	$MAIN.="</SELECT>\n";
	}

$MAIN.="<BR><INPUT TYPE=submit NAME='SUBMIT' VALUE='"._QXZ("SUBMIT")."'><BR><BR><FONT FACE=\"Arial,Helvetica\" size=2><a href=\"./Erlang_report.php\">"._QXZ("Advanced Forecasting Report")."</a></FONT><BR><BR><FONT FACE=\"Arial,Helvetica\" size=2><a href=\"$PHP_SELF?query_date=$query_date&end_date=$end_date&drop_percent=$drop_percent&actual_agents=$actual_agents&hourly_pay=$hourly_pay&revenue_per_sale=$revenue_per_sale&sale_chance=$sale_chance&retry_rate=$retry_rate$campaignQS$groupQS&SUBMIT=$SUBMIT&file_download=1\">"._QXZ("DOWNLOAD")."</a></FONT> | <FONT FACE=\"Arial,Helvetica\" size=2><a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a></FONT></TD></TR></TABLE>\n";
# $MAIN.="<TR><TD colspan='5'>";
$MAIN.="<PRE><FONT SIZE=2>";

if ($groups_selected==0 && $campaigns_selected==0)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT AN IN-GROUP AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	echo "$HEADER";
	require("admin_header.php");
	echo "$MAIN";
	}
else
	{
	$campaign_group_stmt="select closer_campaigns from vicidial_campaigns where campaign_id in ($campaign_SQL)";
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
	$group_string=preg_replace("/^\||\|$/", "", $group_string);
	$ASCII_text =" Date range :  $query_date to $end_date ($groups_selected==0 && $campaigns_selected==0)\n";
	$ASCII_text.=" Campaigns  :  ".preg_replace("/\|/", ", ", $campaign_string)."\n";
	$ASCII_text.=" In-groups  :  ".preg_replace("/\|/", ", ", $group_string)."\n\n";
# 	$ASCII_text.=" Report type:  $erlang_type\n\n";

	$HTML_text.=" Date range :  $query_date to $end_date ($groups_selected==0 && $campaigns_selected==0)\n";
	$HTML_text.=" Campaigns  :  ".preg_replace("/\|/", ", ", $campaign_string)."\n";
	$HTML_text.=" In-groups  :  ".preg_replace("/\|/", ", ", $group_string)."";
# 	$HTML_text.=" Report type:  $erlang_type\n\n";
	
	$sale_stmt="select distinct status from vicidial_campaign_statuses where campaign_id in ($campaign_SQL) and sale='Y' UNION select status from vicidial_statuses where sale='Y'";
	if ($DB) {echo "|$sale_stmt|\n";}
	$sale_rslt=mysql_to_mysqli($sale_stmt, $link);
	$sales_array=array();
	while ($sale_row=mysqli_fetch_row($sale_rslt)) {
		array_push($sales_array, "$sale_row[0]");
	}

	if ($erlang_type=="B") {
		$hour_stmt="select closecallid, substr(call_date, 1, 13) as start_hour, length_in_sec, substr(call_date+INTERVAL length_in_sec second, 1, 13) as end_hour, if(DATE_FORMAT(call_date, '%Y-%m-%d %H:00:00')!=DATE_FORMAT(call_date+INTERVAL length_in_sec second, '%Y-%m-%d %H:00:00'), UNIX_TIMESTAMP(DATE_FORMAT(call_date+INTERVAL length_in_sec second, '%Y-%m-%d %H:00:00'))-UNIX_TIMESTAMP(call_date), length_in_sec) as length_up_to_next_hour, if(DATE_FORMAT(call_date, '%Y-%m-%d %H:00:00')!=DATE_FORMAT(call_date+INTERVAL length_in_sec second, '%Y-%m-%d %H:00:00'), UNIX_TIMESTAMP(call_date+INTERVAL length_in_sec second)-UNIX_TIMESTAMP(DATE_FORMAT(call_date+INTERVAL length_in_sec second, '%Y-%m-%d %H:00:00')), 0) as length_running_into_next_hour, status, uniqueid from vicidial_closer_log where length_in_sec is not null and campaign_id in ($campaign_group_SQL) and call_date>='$query_date 00:00:00' and call_date<='$end_date 23:59:59' and status!='AFTHRS'"; # *

		$avg_stmt="select avg(length_in_sec) as avg_length from vicidial_closer_log where length_in_sec is not null and campaign_id in ($campaign_group_SQL) and call_date>='$query_date 00:00:00' and call_date<='$end_date 23:59:59'"; # **

		$wrapup_stmt="select uniqueid from vicidial_closer_log where length_in_sec is not null and user!='VDCL' and campaign_id in ($campaign_group_SQL) and call_date>='$query_date 00:00:00' and call_date<='$end_date 23:59:59'"; # ***
	} else {
		$hour_stmt="select uniqueid, substr(call_date, 1, 13) as start_hour, length_in_sec, substr(call_date+INTERVAL length_in_sec second, 1, 13) as end_hour, if(DATE_FORMAT(call_date, '%Y-%m-%d %H:00:00')!=DATE_FORMAT(call_date+INTERVAL length_in_sec second, '%Y-%m-%d %H:00:00'), UNIX_TIMESTAMP(DATE_FORMAT(call_date+INTERVAL length_in_sec second, '%Y-%m-%d %H:00:00'))-UNIX_TIMESTAMP(call_date), length_in_sec) as length_up_to_next_hour, if(DATE_FORMAT(call_date, '%Y-%m-%d %H:00:00')!=DATE_FORMAT(call_date+INTERVAL length_in_sec second, '%Y-%m-%d %H:00:00'), UNIX_TIMESTAMP(call_date+INTERVAL length_in_sec second)-UNIX_TIMESTAMP(DATE_FORMAT(call_date+INTERVAL length_in_sec second, '%Y-%m-%d %H:00:00')), 0) as length_running_into_next_hour, status, uniqueid from vicidial_log where length_in_sec is not null and campaign_id in ($campaign_SQL) and call_date>='$query_date 00:00:00' and call_date<='$end_date 23:59:59' and status!='AFTHRS'"; # *

		$avg_stmt="select avg(length_in_sec) as avg_length from vicidial_log where length_in_sec is not null and campaign_id in ($campaign_SQL) and call_date>='$query_date 00:00:00' and call_date<='$end_date 23:59:59'"; # **

		$wrapup_stmt="select uniqueid from vicidial_log where length_in_sec is not null and user!='VDCL' and campaign_id in ($campaign_SQL) and call_date>='$query_date 00:00:00' and call_date<='$end_date 23:59:59'"; # ***
	}
	if ($DB) {echo "|$hour_stmt|\n";}
	if ($DB) {echo "|$avg_stmt|\n";}
	if ($DB) {echo "|$wrapup_stmt|\n";}

	# $ASCII_text.=$hour_stmt."<BR>\n";
	$hour_rslt=mysql_to_mysqli($hour_stmt, $link); # *
	$erlang_calls=mysqli_num_rows($hour_rslt);

	# PROVIDE ACTUAL STATS
	$avg_rslt=mysql_to_mysqli($avg_stmt, $link); # **
	$avg_row=mysqli_fetch_array($avg_rslt);
	$average_call_length=$avg_row["avg_length"];

	$wrapup_rslt=mysql_to_mysqli($wrapup_stmt, $link); # ***
	$wrapup_calls=mysqli_num_rows($wrapup_rslt);
	$uid_ct=0; 
	$uid_clause="";
	$dispo_secs=0;
	$talk_secs=0;
	while ($wrapup_row=mysqli_fetch_row($wrapup_rslt)) {
		$uid_ct++;
		$uid_clause.="'$wrapup_row[0]',";
		if ($uid_ct%100==0) {
			$uid_clause=preg_replace('/,$/', '', $uid_clause);
			$uid_stmt="select dispo_sec, talk_sec from vicidial_agent_log where uniqueid in ($uid_clause)";
			$uid_rslt=mysql_to_mysqli($uid_stmt, $link);
			while ($uid_row=mysqli_fetch_row($uid_rslt)) {
				$dispo_secs+=$uid_row[0];
				$talk_secs+=$uid_row[1];
			}
			$uid_clause="";
		}
	}
	if (strlen($uid_clause)>0) {
		$uid_clause=preg_replace('/,$/', '', $uid_clause);
		$uid_stmt="select dispo_sec, talk_sec from vicidial_agent_log where uniqueid in ($uid_clause)";
		$uid_rslt=mysql_to_mysqli($uid_stmt, $link);
		while ($uid_row=mysqli_fetch_row($uid_rslt)) {
			$dispo_secs+=$uid_row[0];
			$talk_secs+=$uid_row[1];
		}
		$uid_clause="";
	}
	$avg_dispo_sec=MathZDC($dispo_secs, $wrapup_calls);
	$avg_talk_sec=MathZDC($talk_secs, $wrapup_calls);
	#####


	$wrapup_stmt="select average(dispo_sec) from vicidial_agent_log";

	$erlang_array=array();
	$drops_blocks=0;
	$sales=0;
	while ($hour_row=mysqli_fetch_array($hour_rslt)) {
		$erlang_array["$hour_row[start_hour]"][0]+=$hour_row["length_up_to_next_hour"];
		$erlang_array["$hour_row[start_hour]"][2]++;
		
		if ($hour_row["length_running_into_next_hour"]>0) {
			$erlang_array["$hour_row[end_hour]"][0]+=$hour_row["length_running_into_next_hour"];
			$erlang_array["$hour_row[end_hour]"][2]++;
			if ($hour_row["status"]=="DROP") {
				$erlang_array["$hour_row[end_hour]"][3]++;
			}
		}

		if ($hour_row["status"]=="DROP") {
			$erlang_array["$hour_row[start_hour]"][1]+=$hour_row["length_up_to_next_hour"];
			$erlang_array["$hour_row[end_hour]"][1]+=$hour_row["length_running_into_next_hour"];
			$erlang_array["$hour_row[start_hour]"][3]++;
			$drops_blocks++;
		}

		if (in_array("$hour_row[status]", $sales_array)) {
			$erlang_array["$hour_row[start_hour]"][4]++;
			$sales++;
		}
	}
	$hours_active=count($erlang_array); # Used for giving total erlangs for day, need to divide by number of hours reported, not one hour
	$total_erlangs=MathZDC(($erlang_calls*$average_call_length),(3600*$hours_active));
	$total_blocking=MathZDC($drops_blocks, $erlang_calls);
	$sale_percent=MathZDC($sales, $erlang_calls);

	$rpt_header ="+-----------------+-------+-------+------------+----------+-------------+----------+---------+";
	if ($erlang_type=="B") {$rpt_header.="---------+";}
	if ($erlang_type=="C") {$rpt_header.="------------+------------+";}
	$rpt_header.="------------+------------+------------+----------+-----------+------------+------------+------------+\n";

	$ASCII_text.=$rpt_header;
	# $ASCII_text.=$hour_stmt."<BR>\n";

	# Damn fixed-space formatting, hate ASCII.  HATE.
	if ($erlang_type=="B") {
		if ($actual_agents>0) {
			$ASCII_text.="| ".sprintf("%70s", _QXZ("Actual agents: ")).sprintf("%-131s", "$actual_agents")." |\n";
		}
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Total calls: ")).sprintf("%-131s", "$erlang_calls")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Total drops: ")).sprintf("%-131s", "$drops_blocks")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Desired drop rate: ")).sprintf("%-5.2f", "$drop_percent").sprintf("%-126s", " %")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Total blocking/drop rate: ")).sprintf("%-5.2f", (100*$total_blocking)).sprintf("%-126s", " %")." |\n";  # sprintf("%-131.4f", "$total_blocking")
		# $ASCII_text.="| ".sprintf("%70s", _QXZ("Desired sale rate: ")).sprintf("%-5.2f", "$sale_chance").sprintf("%-126s", " %")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Sale rate: ")).sprintf("%-5.2f", (100*$sale_percent)).sprintf("%-126s", " %")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Average call duration: ")).sprintf("%-131.2f", "$average_call_length")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Erlangs: ")).sprintf("%-131.4f", $total_erlangs)." |\n";
	} 
	if ($erlang_type=="C") {
		if ($actual_agents>0) {
			$ASCII_text.="| ".sprintf("%70s", _QXZ("Actual agents: ")).sprintf("%-147s", "$actual_agents")." |\n";
		}
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Total calls: ")).sprintf("%-147s", "$erlang_calls")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Total drops: ")).sprintf("%-147s", "$drops_blocks")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Desired drop rate: ")).sprintf("%-5.2f", "$drop_percent").sprintf("%-142s", " %")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Total blocking/drop rate: ")).sprintf("%-5.2f", (100*$total_blocking)).sprintf("%-142s", " %")." |\n";  # sprintf("%-147.4f", "$total_blocking")
		# $ASCII_text.="| ".sprintf("%70s", _QXZ("Desired sale rate: ")).sprintf("%-5.2f", "$sale_chance").sprintf("%-142s", " %")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Sale rate: ")).sprintf("%-5.2f", (100*$sale_percent)).sprintf("%-142s", " %")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Average call duration: ")).sprintf("%-147.2f", "$average_call_length")." |\n";
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Erlangs: ")).sprintf("%-147.4f", $total_erlangs)." |\n";
	}

	$HTML_text.="<table width='1024' border='0' cellpadding='0' cellspacing='0'>";
	$HTML_text.="<tr><td width='900'>";
	
	$CSV_text="";
	if ($actual_agents>0) {
		$CSV_text.="\"Actual agents:\",\"$actual_agents\"\n";
	}
	$CSV_text.="\"Total calls:\",\"$erlang_calls\"\n";
	$CSV_text.="\"Total drops:\",\"$drops_blocks\"\n";
	$CSV_text.="\"Desired drop rate:\",\"".sprintf("%5.2f", "$drop_percent")." %\"\n";
	$CSV_text.="\"Total blocking/drop rate:\",\"".sprintf("%-5.2f", (100*$total_blocking))."\"\n";
	# $CSV_text.="\"Desired sale rate:\",\"".sprintf("%5.2f", "$sale_chance")." %\"\n";
	$CSV_text.="\"Sale rate:\",\"".sprintf("%-5.2f", (100*$sale_percent))."\"\n";
	$CSV_text.="\"Average call duration:\",\"".sprintf("%.2f", "$average_call_length")." seconds\"\n";
	$CSV_text.="\"Erlangs:\",\"".sprintf("%.4f", "$total_erlangs")."\"\n";

	
	# ESTIMATED AGENTS
	$lines=1;
	if ($total_blocking>0) {
		$GoS=MathZDC((pow($total_erlangs, $lines)/(factorial($lines))), (erlsum(0, $lines, $total_erlangs)));
		if ($GoS>$total_blocking) {
			$ASCII_text.="<!-- $lines \n";
			while ($GoS>$total_blocking) {
				$lines++;
				$GoS=MathZDC((pow($total_erlangs, $lines)/(factorial($lines))), (erlsum(0, $lines, $total_erlangs)));
				if ($retry_rate>0 && $erlang_type=="B") {$GoS=adjustedGoS($total_erlangs, $GoS, $retry_rate, $lines);} #"B" allows for retry rates, "C" does not.
				$ASCII_text.=" $GoS \n";
			}
			$ASCII_text.="\n-->";
		}
		$Pqueue=(pow($total_erlangs, $lines)/(factorial($lines)))/((pow($total_erlangs, $lines)/(factorial($lines)))+((1-MathZDC($total_erlangs, $lines))*erlsum(0, ($lines-1), $total_erlangs)));
		$ASA=MathZDC(($Pqueue*$average_call_length), ($lines-$total_erlangs));
	} else {
		$lines="N/A";
		$Pqueue=0;
		$ASA=0;
	}
	if ($Pqueue>1) {$Pqueue=1;}
	$est_GoS=$GoS;  # Need this here since we moved the output line further down & need to save it.
	$est_lines=$lines;  # Need this here since we moved the output line further down & need to save it.

	if ($erlang_type=="B") {
#		$ASCII_text.="| ".sprintf("%70s", _QXZ("Retry rate: ")).sprintf("%-131s", "$retry_rate %")." |\n"; # B
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Grade of service: ")).sprintf("%-131s", sprintf("%0.2f", (100*$GoS)." %"))." |\n"; # B
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Estimated agents fielding calls: ")).sprintf("%-131s", $lines)." |\n";
	} 
	if ($erlang_type=="C") {
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Estimated queue probability: ")).sprintf("%-5.2f", (100*$Pqueue)).sprintf("%-142s", " %")." |\n"; # C
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Average speed of answering: ")).sprintf("%-147s", sec_convert($ASA,'H'))." |\n"; # C
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Estimated agents fielding calls: ")).sprintf("%-147s", $lines)." |\n";
	}

	if ($erlang_type=="B") {
#		$CSV_text.="\""._QXZ("Retry rate: ")."\",\"$retry_rate %\"\n"; # B
		$CSV_text.="\""._QXZ("Grade of service: ")."\",\"".sprintf("%0.2f", (100*$GoS))." %\"\n"; # B
	}
	if ($erlang_type=="C") {
		$CSV_text.="\""._QXZ("Estimated queue probability: ")."\",\"".sprintf("%.2f", (100*$Pqueue))."%\"\n"; # C
		$CSV_text.="\""._QXZ("Average speed of answering: ")."\",\"".sec_convert($ASA,'H')."\"\n"; # C
	}
	$CSV_text.="\""._QXZ("Estimated agents fielding calls: ")."\",\"$lines\"\n";

	if ($erlang_type=="B") {
##		$HTML_text.="<tr class='records_list_x'><td><font size=1>"._QXZ("Retry rate: ")."</font></td><td><font size=1>$retry_rate %</font></td></tr>"; # B
#		$HTML_text.="<tr class='records_list_x'><td><font size=1>"._QXZ("Grade of service: ")."</font></td><td><font size=1>".sprintf("%0.2f", (100*$GoS))."%</font></td></tr>"; # B
	}
	if ($erlang_type=="C") {
		$HTML_text.="<tr class='records_list_x'><td><font size=1>"._QXZ("Estimated queue probability: ")."</font></td><td><font size=1>".sprintf("%.2f", (100*$Pqueue))."%</font></td></tr>"; # C
		$HTML_text.="<tr class='records_list_y'><td><font size=1>"._QXZ("Average speed of answering: ")."</font></td><td><font size=1>".sec_convert($ASA,'H')."</font></td></tr>"; # C
	}
#	$HTML_text.="<tr class='records_list_y'><td><font size=1>"._QXZ("Estimated agents fielding calls: ")."</font></td><td><font size=1>".$lines."</font></td></tr>";
	#############

	##### RECOMMENDED AGENTS
	$lines=1;
	if ($erlang_type=="B") {
		$GoS=MathZDC((pow($total_erlangs, $lines)/(factorial($lines))), (erlsum(0, $lines, $total_erlangs)));
		if ($GoS>$drop_rate) {
			$ASCII_text.="<!-- $lines \n";
			while ($GoS>$drop_rate) {
				$lines++;
				$GoS=MathZDC((pow($total_erlangs, $lines)/(factorial($lines))), (erlsum(0, $lines, $total_erlangs)));
				if ($retry_rate>0) {$GoS=adjustedGoS($total_erlangs, $GoS, $retry_rate, $lines);}
				$ASCII_text.=" $GoS \n";
			}
			$ASCII_text.="\n-->";
		}
	}
	if ($erlang_type=="C") {
		$Pqueue=(pow($total_erlangs, $lines)/(factorial($lines)))/((pow($total_erlangs, $lines)/(factorial($lines)))+((1-MathZDC($total_erlangs, $lines))*erlsum(0, ($lines-1), $total_erlangs)));
		if ($Pqueue>$pqueue_target) {
			$ASCII_text.="<!-- $lines \n";
			while ($Pqueue>$pqueue_target) {
				$lines++;
				$Pqueue=(pow($total_erlangs, $lines)/(factorial($lines)))/((pow($total_erlangs, $lines)/(factorial($lines)))+((1-MathZDC($total_erlangs, $lines))*erlsum(0, ($lines-1), $total_erlangs)));
				$ASCII_text.=" $GoS \n";
			}
			$ASCII_text.="\n-->";
		}
	}
	
	if ($erlang_type=="B") {
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Recommended agent count: ")).sprintf("%-131s", $lines)." |\n";
	} else {
		$ASCII_text.="| ".sprintf("%70s", _QXZ("Recommended agent count: ")).sprintf("%-147s", $lines)." |\n";
	}
	$CSV_text.="\"Recommended agent count:\",\"$lines\"\n";
	$CSV_text.="\n";	
	#############

	$ASCII_text.=$rpt_header;
	$ASCII_text.= "| "._QXZ("CALLING HOUR", 15)." | "._QXZ("CALLS", 5)." | "._QXZ("SALES", 5)." | "._QXZ("TOTAL TIME", 10)." | "._QXZ("AVG TIME", 8)." | "._QXZ("DROPPED HRS", 11)." | "._QXZ("BLOCKING", 8)." | "._QXZ("ERLANGS", 7);
	if ($erlang_type=="B") {$ASCII_text.=" | "._QXZ("GOS", 7);} # B
	if ($erlang_type=="C") {$ASCII_text.=" | "._QXZ("QUEUE PROB", 10)." | "._QXZ("AVG ANSWER", 10);} # C
	$ASCII_text.=" | "._QXZ("REC AGENTS", 10)." | "._QXZ("EST AGENTS", 10)." | "._QXZ("CALLS/AGNT", 10)." |\n"; #  ." | "._QXZ("REV/CALL", 8)." | "._QXZ("REV/AGENT", 9)." | "._QXZ("TOTAL REV", 10)." | "._QXZ("TOTAL COST", 10)." | "._QXZ("MARGIN", 10)
	$ASCII_text.=$rpt_header;
	$CSV_text.="\""._QXZ("CALLING HOUR")."\",\""._QXZ("CALLS")."\",\""._QXZ("SALES")."\",\""._QXZ("TOTAL TIME")."\",\""._QXZ("AVG TIME")."\",\""._QXZ("DROPPED HRS")."\",\""._QXZ("BLOCKING")."\",\""._QXZ("ERLANGS")."\",";
	if ($erlang_type=="B") {$CSV_text.="\""._QXZ("GRADE OF SERVICE")."\",";} # B
	if ($erlang_type=="C") {$CSV_text.="\""._QXZ("QUEUE PROBABILITY")."\",\""._QXZ("AVERAGE ANSWER SPEED")."\",";} # C
	$CSV_text.="\""._QXZ("REC AGENTS")."\",\""._QXZ("EST AGENTS")."\",\""._QXZ("CALLS/AGNT")."\"\n"; # ,\""._QXZ("REV/CALL")."\",\""._QXZ("REV/AGENT")."\",\""._QXZ("TOTAL REV")."\",\""._QXZ("TOTAL COST")."\",\""._QXZ("MARGIN")."\"
	ksort($erlang_array);

	$HTML_text2="</td><td valign='top'style=\"padding: 3px 0px 0px 10px;\">";
	$HTML_text2.="<BR><table width='130' border='0' cellpadding='2' cellspacing='0'>";
	$HTML_text2.="<tr bgcolor='#000'><td colspan='2' align='center'><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("INBOUND SUMMARY")."</FONT></B></td></tr>";
	if ($actual_agents>0) {
		$HTML_text2.="<tr class='records_list_y'><td><font size=1>"._QXZ("Actual agents: ")."</font></td><td nowrap><font size=1>".$actual_agents."</font></td></tr>";
	}
	$HTML_text2.="<tr class='records_list_x'><td><font size=1>"._QXZ("Total calls: ")."</font></td><td nowrap><font size=1>".$erlang_calls."</font></td></tr>";
	$HTML_text2.="<tr class='records_list_y'><td><font size=1>"._QXZ("Total drops: ")."</font></td><td nowrap><font size=1>".$drops_blocks."</font></td></tr>";
	$HTML_text2.="<tr class='records_list_x'><td><font size=1>"._QXZ("Desired drop rate: ")."</font></td><td nowrap><font size=1>".sprintf("%5.2f", "$drop_percent")." %</font></td></tr>";
	$HTML_text2.="<tr class='records_list_y'><td><font size=1>"._QXZ("Total blocking/drop rate: ")."</font></td><td nowrap><font size=1>".sprintf("%-5.2f", (100*$total_blocking))." %</font></td></tr>";
	# $HTML_text2.="<tr class='records_list_x'><td><font size=1>"._QXZ("Desired sale rate: ")."</font></td><td nowrap><font size=1>".sprintf("%5.2f", "$sale_chance")." %</font></td></tr>";
	$HTML_text2.="<tr class='records_list_x'><td><font size=1>"._QXZ("Sale rate: ")."</font></td><td nowrap><font size=1>".sprintf("%-5.2f", (100*$sale_percent))." %</font></td></tr>";
	$HTML_text2.="<tr class='records_list_y'><td><font size=1>"._QXZ("Average call duration: ")."</font></td><td nowrap><font size=1>".sprintf("%18.2f", "$average_call_length")."</font></td></tr>";
	$HTML_text2.="<tr class='records_list_x'><td><font size=1>"._QXZ("Erlangs: ")."</font></td><td nowrap><font size=1>".sprintf("%18.4f", $total_erlangs)."</font></td></tr>";
	$HTML_text2.="<tr class='records_list_y'><td><font size=1>"._QXZ("Grade of service: ")."</font></td><td nowrap><font size=1>".sprintf("%0.2f", (100*$est_GoS))."%</font></td></tr>"; # B
	$HTML_text2.="<tr class='records_list_x'><td><font size=1>"._QXZ("Estimated agents fielding calls: ")."</font></td><td nowrap><font size=1>".$est_lines."</font></td></tr>";
	$HTML_text2.="<tr class='records_list_y'><td><font size=1>"._QXZ("Recommended agent count: ")."</font></td><td nowrap><font size=1>".$lines."</font></td></tr>";
	$HTML_text2.="</table>";
	$HTML_text2.="</td>";
	$HTML_text2.="</tr></table>"; # For closing the table above that encompasses first report table and chart

	$HTML_text2.="<table width='1024' border='0' cellpadding='2' cellspacing='0'>";
	$HTML_text2.="<TR BGCOLOR=BLACK>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("CALLING HOUR")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("CALLS")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("SALES")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("TOTAL TIME")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("AVG TIME")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("DROPPED HRS")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("BLOCKING")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("ERLANGS")."</FONT></B></TD>\n";

	if ($erlang_type=="B") {
		$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("GRADE OF SERVICE")."</FONT></B></TD>\n"; # B
	}
	if ($erlang_type=="C") {
		$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("QUEUE PROB")."</FONT></B></TD>\n"; # C
		$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("AVG ANSWER")."</FONT></B></TD>\n"; # C
	}

	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("REC AGENTS")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("EST AGENTS")."</FONT></B></TD>\n";
	$HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("CALLS PER AGENT")."</FONT></B></TD>\n";
	# $HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("REVENUE PER CALL")."</FONT></B></TD>\n";
	# $HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("REVENUE PER AGENT")."</FONT></B></TD>\n";
	# $HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("TOTAL REVENUE")."</FONT></B></TD>\n";
	# $HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("TOTAL COST")."</FONT></B></TD>\n";
	# $HTML_text2.="<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("MARGIN")."</FONT></B></TD>\n";
	$HTML_text2.="</TR>\n";


	$bht=0;
	$graph_stats=array();
	$q=0;
#	while(list($key, $val)=each($erlang_array)) {
	foreach($erlang_array as $key => $val) {
		if ($val[0]>$bht) {$bht=$val[0];}
		if ($q%2==0) {$tdclass="records_list_x";} else {$tdclass="records_list_y";}
		$average_time=round(MathZDC($val[0], $val[2]));
		$blocking=MathZDC($val[3], $val[2]);
		$erlangs=$val[2]*MathZDC($average_time, 3600); # Row is call per hour, therefore call length average must be in hours.
		$sales=$val[4]+0;

		$ASCII_text.="| "; #.substr($key, 0, -2)." - ".substr($key, 5, 2)." ".substr($key, 8, 2)." ".substr($key, 0, 4);
		$ASCII_text.=date("Y-m-d ha", mktime(substr($key, -2), 0, 0, substr($key, 5, 2), substr($key, 8, 2), substr($key, 0, 4)));
		$ASCII_text.=" | ";
		$ASCII_text.=sprintf("%5s", $val[2]);
		$ASCII_text.=" | ";
		$ASCII_text.=sprintf("%5s", $sales);
		$ASCII_text.=" | ";
		$ASCII_text.=sprintf("%10s", sec_convert($val[0],'H'));
		$ASCII_text.=" | ";
		$ASCII_text.=sprintf("%8s", sec_convert($average_time,'H'));
		$ASCII_text.=" | ";
		$ASCII_text.=sprintf("%11s", sec_convert($val[1],'H'));
		$ASCII_text.=" | ";
		$ASCII_text.=sprintf("%8s", sprintf("%01.4f", $blocking));
		$ASCII_text.=" | ";
		$ASCII_text.=sprintf("%7s", sprintf("%01.4f", $erlangs)); 
		$ASCII_text.=" | ";

		$CSV_text.="\"".date("Y-m-d ha", mktime(substr($key, -2), 0, 0, substr($key, 5, 2), substr($key, 8, 2), substr($key, 0, 4)))."\",\"".$val[2]."\",\"".$sales."\",\"".sec_convert($val[0],'H')."\",\"".sec_convert($average_time,'H')."\",\"".sec_convert($val[1],'H')."\",\"".sprintf("%01.4f", $blocking)."\",\"".sprintf("%01.4f", $erlangs)."\",";

		$HTML_text2.="<tr class='$tdclass'>\n";
		$HTML_text2.="<td><font size=1>".date("Y-m-d ha", mktime(substr($key, -2), 0, 0, substr($key, 5, 2), substr($key, 8, 2), substr($key, 0, 4)))."</font></td>\n";
		$HTML_text2.="<td><font size=1>".$val[2]."</font></td>\n";
		$HTML_text2.="<td><font size=1>".$sales."</font></td>\n";
		$HTML_text2.="<td><font size=1>".sec_convert($val[0],'H')."</font></td>\n";
		$HTML_text2.="<td><font size=1>".sec_convert($average_time,'H')."</font></td>\n";
		$HTML_text2.="<td><font size=1>".sec_convert($val[1],'H')."</font></td>\n";
		$HTML_text2.="<td><font size=1>".sprintf("%01.4f", $blocking)."</font></td>\n";
		$HTML_text2.="<td><font size=1>".sprintf("%01.4f", $erlangs)."</font></td>\n";

		$graph_key=date("Y-m-d ha", mktime(substr($key, -2), 0, 0, substr($key, 5, 2), substr($key, 8, 2), substr($key, 0, 4)));
		$graph_stats["$graph_key"][0]=$val[2];
		$graph_stats["$graph_key"][1]=$val[0];
		$graph_stats["$graph_key"][2]=$average_time;
		$graph_stats["$graph_key"][3]=$val[1];
		$graph_stats["$graph_key"][4]=sprintf("%01.4f", $blocking);
		$graph_stats["$graph_key"][5]=sprintf("%01.4f", $erlangs);
	
		##### RECOMMENDED AGENTS
		$lines=1;
		if ($erlang_type=="B") {
			$GoS=MathZDC((pow($erlangs, $lines)/(factorial($lines))), (erlsum(0, $lines, $erlangs)));
			if ($GoS>$drop_rate) {
				$ASCII_text.="<!-- $lines \n";
				while ($GoS>$drop_rate) {
					$lines++;
					$GoS=MathZDC((pow($erlangs, $lines)/(factorial($lines))), (erlsum(0, $lines, $erlangs)));
					if ($retry_rate>0) {$GoS=adjustedGoS($erlangs, $GoS, $retry_rate, $lines);}
					$ASCII_text.=" $GoS \n";
				}
				$ASCII_text.="\n-->";
			}
		}
		if ($erlang_type=="C") {
			$Pqueue=(pow($erlangs, $lines)/(factorial($lines)))/((pow($erlangs, $lines)/(factorial($lines)))+((1-MathZDC($erlangs, $lines))*erlsum(0, ($lines-1), $erlangs)));
			if ($Pqueue>$pqueue_target) {
				$ASCII_text.="<!--  \n";
				while ($Pqueue>$pqueue_target) {
					$lines++;
					$Pqueue=(pow($erlangs, $lines)/(factorial($lines)))/((pow($erlangs, $lines)/(factorial($lines)))+((1-MathZDC($erlangs, $lines))*erlsum(0, ($lines-1), $erlangs)));
					$ASCII_text.=" $lines, $erlangs - $Pqueue \n";
				}
				$ASCII_text.="\n-->";
			}
		}
		$recommended_agents=$lines;

		##### ESTIMATED AGENTS
		$lines=1;
		if ($blocking>0) {
			$GoS=MathZDC((pow($erlangs, $lines)/(factorial($lines))), (erlsum(0, $lines, $erlangs)));
			if ($GoS>$blocking) {
				$ASCII_text.="<!-- $lines \n";
				while ($GoS>$blocking) {
					$lines++;
					$GoS=MathZDC((pow($erlangs, $lines)/(factorial($lines))), (erlsum(0, $lines, $erlangs)));
					if ($retry_rate>0 && $erlang_type=="B") {$GoS=adjustedGoS($erlangs, $GoS, $retry_rate, $lines);}  # "B" allows for retry rates, "C" does not
					$ASCII_text.=" $GoS \n";
				}
				$ASCII_text.="\n-->";
			}

			$Pqueue=(pow($erlangs, $lines)/(factorial($lines)))/((pow($erlangs, $lines)/(factorial($lines)))+((1-MathZDC($erlangs, $lines))*erlsum(0, ($lines-1), $erlangs)));
			$ASA=MathZDC(($Pqueue*$average_time), ($lines-$erlangs));
		} else {
			$lines="$recommended_agents";
			$Pqueue=0;
			$ASA=0;
		}
		if ($actual_agents>0) {
			$lines=$actual_agents;
			$Pqueue=0;
			$ASA=0;
		}
		if ($Pqueue>1) {$Pqueue=1;}

		if ($erlang_type=="B") {
			$ASCII_text.=sprintf("%7s", sprintf("%0.2f", (100*$GoS))."%"); 
			$ASCII_text.=" | ";
		}
		if ($erlang_type=="C") {
			$ASCII_text.=sprintf("%10s", sprintf("%.4f", (100*$Pqueue))."%"); 
			$ASCII_text.=" | ";
			$ASCII_text.=sprintf("%10s", sec_convert($ASA,'H')); 
			$ASCII_text.=" | ";
		}
		$ASCII_text.=sprintf("%10s", $recommended_agents);
		$ASCII_text.=" | ";
		if ($erlang_type=="B") {
			$CSV_text.="\"".sprintf("%0.2f", (100*$GoS))."%\","; # B
		}
		if ($erlang_type=="C") {
			$CSV_text.="\"".sprintf("%.6f", (100*$Pqueue))."%\","; # C
			$CSV_text.="\"".sec_convert($ASA,'H')."\","; # C
		}
		$CSV_text.="\"$recommended_agents\",";
		$graph_stats["$graph_key"][7]=$recommended_agents;
		if ($erlang_type=="B") {
			$HTML_text2.="<td><font size=1>".sprintf("%0.2f", (100*$GoS))."%</font></td>\n"; # B
		}
		if ($erlang_type=="C") {
			$HTML_text2.="<td><font size=1>".sprintf("%.4f", (100*$Pqueue))."%</font></td>\n"; # C
			$HTML_text2.="<td><font size=1>".sec_convert($ASA,'H')." </font></td>\n"; # C
		}
		$HTML_text2.="<td><font size=1>$recommended_agents</font></td>\n";

		$ASCII_text.=sprintf("%10s", $lines);
		$ASCII_text.=" | ";
		$ASCII_text.=sprintf("%-10.2f", MathZDC($val[2], $lines));
#		$ASCII_text.=" | ";
#		$ASCII_text.=sprintf("%-8s", "\$".number_format(MathZDC(($revenue_per_sale*$sales), $val[2]),2,".",","));
#		$ASCII_text.=" | ";
#		$ASCII_text.=sprintf("%-9s", "\$".number_format(MathZDC(($revenue_per_sale*$sales), $lines),2,".",","));
#		$ASCII_text.=" | ";
#		$ASCII_text.=sprintf("%-10s", "\$".number_format(($revenue_per_sale*$sales),2,".",","));
#		$ASCII_text.=" | ";
#		$ASCII_text.=sprintf("%-10s", "\$".number_format(($lines*$hourly_pay),2,".",","));
#		$ASCII_text.=" | ";
#		$ASCII_text.=sprintf("%-10s", "\$".number_format((($revenue_per_sale*$sales)-($lines*$hourly_pay)),2,".",","));
		$ASCII_text.=" |\n";

		$graph_stats["$graph_key"][8]=$drop_rate;


		$CSV_text.="\"$lines\",";
		$CSV_text.="\"".sprintf("%.2f", MathZDC($val[2], $lines))."\",";
#		$CSV_text.="\"$".number_format(MathZDC(($revenue_per_sale*$sales), $val[2]),2,".",",")."\",";
#		$CSV_text.="\"$".number_format(MathZDC(($revenue_per_sale*$sales), $lines),2,".",",")."\",";
#		$CSV_text.="\"$".number_format(($revenue_per_sale*$sales),2,".",",")."\",";
#		$CSV_text.="\"$".number_format(($lines*$hourly_pay),2,".",",")."\",";
#		$CSV_text.="\"$".number_format((($revenue_per_sale*$sales)-($lines*$hourly_pay)),2,".",",")."\"\n";

		$graph_stats["$graph_key"][6]=$lines;
		$graph_stats["$graph_key"][8]=$drop_rate;
		$graph_stats["$graph_key"][9]=$sales; 
		$graph_stats["$graph_key"][10]=number_format(MathZDC(($revenue_per_sale*$sales), $val[2]),2,".",""); # Revenue per call 
		$graph_stats["$graph_key"][11]=number_format(MathZDC(($revenue_per_sale*$sales), $lines),2,".",""); # Revenue per agent 
		$graph_stats["$graph_key"][12]=number_format(($revenue_per_sale*$sales),2,".",""); # Total revenue 
		$graph_stats["$graph_key"][13]=number_format(($lines*$hourly_pay),2,".",""); # Cost 
		$graph_stats["$graph_key"][14]=number_format((($revenue_per_sale*$sales)-($lines*$hourly_pay)),2,".",",");
		$graph_stats["$graph_key"][15]=$GoS; 
		$graph_stats["$graph_key"][16]=$Pqueue; # Cost 
		$graph_stats["$graph_key"][17]=$ASA; # Cost 

		$HTML_text2.="<td><font size=1>$lines</font></td>\n";
		$HTML_text2.="<td><font size=1>".sprintf("%.2f", ($val[2]/$lines))."</font></td>\n";
#		$HTML_text2.="<td><font size=1>\$".number_format(MathZDC(($revenue_per_sale*$sales), $val[2]),2,".",",")."</font></td>\n";
#		$HTML_text2.="<td><font size=1>\$".number_format(MathZDC(($revenue_per_sale*$sales), $lines),2,".",",")."</font></td>\n";
#		$HTML_text2.="<td><font size=1>\$".number_format(($revenue_per_sale*$sales),2,".",",")."</font></td>\n";
#		$HTML_text2.="<td><font size=1>\$".number_format(($lines*$hourly_pay),2,".",",")."</font></td>\n";
#		$HTML_text2.="<td><font size=1>\$".number_format((($revenue_per_sale*$sales)-($lines*$hourly_pay)),2,".",",")."</font></td>\n";
		$HTML_text2.="</tr>\n";
		$q++;
	}
	$ASCII_text.=$rpt_header;
	$ASCII_text.=" BHT:  ".sec_convert($bht,'H')." ($bht seconds)\n";

	$HTML_text2.="</table>";

	# USE THIS FOR COMBINED graphs, use pipe-delimited array elements, dataset_name|index|link_name|graph_override
	# You have to hard code the graph name in where it is overridden and mind the data indices.  No other way to do it.
	$multigraph_text="";
	$graph_id++;
	$graph_array=array("ERL_AGENTSdata|7,6|AGENTS|integer|Rec. Agents,Est. Agents", "ERL_BLOCKdata|4,8|BLOCKING/DROPS|percent|Blocking,Desired drop rate", "ERL_CALLSdata|0,9|CALLS|integer|Calls,Sales", "ERL_TIMEdata|2,3,1|TIMES|time|Average time,Dropped time,Total time", "ERL_ERLANGSdata|5|ERLANGS|integer|Erlangs"); # , "ERL_REVENUEdata|10,11|REVENUE|dollar|Revenue per call,Revenue per agent", "ERL_REVENUEdata|12,13|MARGINS|dollar|Total revenue,Total cost"
	if ($erlang_type=="B") {
		array_push($graph_array, "ERL_GOSdata|15|GoS|integer|Grade of Service");
	}
	if ($erlang_type=="C") {
		array_push($graph_array, "ERL_PQUEUEdata|16|QUEUE|percent|Call queue probability");
	}


	$default_graph="line"; # Graph that is initally displayed when page loads
	include("graph_color_schemas.inc"); 


	# CUSTOMIZING COLORS FOR LINE GRAPHS
	$graph_colors=array("216,0,0", "0,0,216", "0,216,0", "216,216,0", "216,0,216", "0,216,216");


	$graph_totals_array=array();
	$graph_totals_rawdata=array();
	for ($q=0; $q<count($graph_array); $q++) {
		$graph_info=explode("|", $graph_array[$q]); 
		$current_graph_total=0;
		$dataset_name=$graph_info[0];
		$dataset_indices=explode(",", $graph_info[1]); 
		$dataset_type=$graph_info[3];
		$dataset_labels=explode(",", $graph_info[4]); 

		$JS_text.="var $dataset_name = {\n";
		$datasets="\t\tdatasets: [\n";

		$labels="\t\tlabels:[";
#		while(list($key, $val)=each($graph_stats)) {
		foreach($graph_stats as $key => $val) {
			$labels.="\"".preg_replace('/ +/', ' ', $key)."\",";
		}
		reset($graph_stats);
		$labels=preg_replace('/,$/', '', $labels)."],\n";

		for ($d=0; $d<count($dataset_indices); $d++) {
			$dataset_index=$dataset_indices[$d];
			$datasets.="\t\t\t{\n";
			$datasets.="\t\t\t\tlabel: \"$dataset_labels[$d]\",\n";
			$datasets.="\t\t\t\tfill: true,\n";

			$data="\t\t\t\tdata: [";
			$graphConstantsA="\t\t\t\tbackgroundColor: \"rgba(".$graph_colors[($d%count($graph_colors))].",0.5)\",\n";
			$graphConstantsB="\t\t\t\tborderColor: \"rgba(".$graph_colors[($d%count($graph_colors))].",1)\",\n";
			$graphConstantsC="\t\t\t\tfillColor: \"rgba(".$graph_colors[($d%count($graph_colors))].",0.1)\"\n";

#			while(list($key, $val)=each($graph_stats)) {
			foreach($graph_stats as $key => $val) {
				$val[$dataset_index]=preg_replace("/N\/A/", "0", $val[$dataset_index]);
				$data.="\"".$val[$dataset_index]."\","; 
				$current_graph_total+=intval($val[$dataset_index]);
				$bgcolor=$backgroundColor[($d%count($backgroundColor))];
				$hbgcolor=$hoverBackgroundColor[($d%count($hoverBackgroundColor))];
				$hbcolor=$hoverBorderColor[($d%count($hoverBorderColor))];
			}
			reset($graph_stats);
			$data=preg_replace('/,$/', '', $data)."],\n";
			$datasets.=$data;
			$datasets.=$graphConstantsA.$graphConstantsB.$graphConstantsC; # SEE TOP OF SCRIPT
			$datasets.="\t\t\t},\n";
			$graph_totals_rawdata[$d]=$current_graph_total;
		}	
		$datasets=preg_replace('/,\n$/', "\n", $datasets);

		switch($dataset_type) {
			case "time":
				$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = Math.round(data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]); return data.datasets[tooltipItem.datasetIndex].label+\": \"+value.toHHMMSS();}}}, legend: { display: true }},";
				break;
			case "percent":
				$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]; return data.datasets[tooltipItem.datasetIndex].label+\": \"+ (value*100) + '%';}}}, legend: { display: true }},";
				break;
			case "dollar":
				$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index]; return data.datasets[tooltipItem.datasetIndex].label+\": \$\"+ value;}}}, legend: { display: true }},";
				break;
			default:
				$chart_options="options: { legend: { display: true}},";
				break;
		}

		$datasets.="\t\t]\n";
		$datasets.="\t}\n";

		$JS_text.=$labels.$datasets;
		$JS_text.="var main_ctx = document.getElementById(\"CanvasID".$graph_id."_".$q."\");\n";
		$JS_text.="var GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: '$default_graph', $chart_options data: $dataset_name});\n";
	}


	$graph_count=count($graph_array);
	$graph_title=_QXZ("FORECASTING REPORT");
	$hide_graph_choice="yes";
	$override_width=900; $override_height=500;
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH.=$graphCanvas;

	
	if ($report_display_type=="HTML")
		{
		$JS_onload.="}\n";
		$JS_text.=$JS_onload;
		$JS_text.="</script>\n";
		require("chart_button.php");

		$MAIN.=$HTML_text.$GRAPH.$HTML_text2.$JS_text;
		}
	else 
		{
		$MAIN.=$ASCII_text;
		}


	$MAIN.="</FORM>";

if ($file_download>0) 
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_Erlang_report_$US$FILE_TIME.csv";
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
#	echo $ASCII_text;
	}
?>