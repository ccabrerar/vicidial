<?php 
# agent_sales_report_mobile.php
# 
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# Streamlined report for displaying sale-only statistics in a format easily readable on a mobile device
#
# CHANGELOG:
# 200309-1819 - First Build
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date_D"]))			{$query_date_D=$_GET["query_date_D"];}
	elseif (isset($_POST["query_date_D"]))	{$query_date_D=$_POST["query_date_D"];}
if (isset($_GET["end_date_D"]))				{$end_date_D=$_GET["end_date_D"];}
	elseif (isset($_POST["end_date_D"]))	{$end_date_D=$_POST["end_date_D"];}
if (isset($_GET["query_date_T"]))			{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["end_date_T"]))				{$end_date_T=$_GET["end_date_T"];}
	elseif (isset($_POST["end_date_T"]))	{$end_date_T=$_POST["end_date_T"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["call_status"]))			{$call_status=$_GET["call_status"];}
	elseif (isset($_POST["call_status"]))	{$call_status=$_POST["call_status"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["graph_type"]))			{$graph_type=$_GET["graph_type"];}
	elseif (isset($_POST["graph_type"]))	{$graph_type=$_POST["graph_type"];}
if (isset($_GET["top_agents"]))			{$top_agents=$_GET["top_agents"];}
	elseif (isset($_POST["top_agents"]))	{$top_agents=$_POST["top_agents"];}
if (isset($_GET["sort_by"]))			{$sort_by=$_GET["sort_by"];}
	elseif (isset($_POST["sort_by"]))	{$sort_by=$_POST["sort_by"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["refresh_rate"]))			{$refresh_rate=$_GET["refresh_rate"];}
	elseif (isset($_POST["refresh_rate"]))	{$refresh_rate=$_POST["refresh_rate"];}


$DB = preg_replace('/[^0-9]/','',$DB);
$end_date_D = preg_replace('/[^-0-9]/','',$end_date_D);
$end_date_T = preg_replace('/[^0-9\:]/','',$end_date_T);
$query_date_D = preg_replace('/[^-0-9]/','',$query_date_D);
$query_date_T = preg_replace('/[^0-9\:]/','',$query_date_T);
$refresh_rate = preg_replace('/[^0-9]/','',$refresh_rate);
$top_agents = preg_replace('/[^0-9]/','',$top_agents);
$graph_type=preg_replace('/[^_0-9\p{L}]/u','',$graph_type);
$report_display_type=preg_replace('/[^_\p{L}]/u','',$report_display_type);
$sort_by=preg_replace('/[^\p{L}]/u','',$sort_by);
$SUBMIT=preg_replace('/[^\p{L}]/u','',$SUBMIT);


$report_name = 'Agent Sales Report';
$db_source = 'M';
$JS_text.="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
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
	$SSreport_default_format =		$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################
# if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group[0], $query_date, $end_date, $shift, 0, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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
	$HTML_text.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
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
    echo "You are not allowed to view this report: |$PHP_AUTH_USER|$report_name|\n";
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

######################################

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($user_group)) {$user_group = array();}
if (!isset($query_date_D)) {$query_date_D=$NOW_DATE;}
if (!isset($query_date_T)) {$query_date_T="00:00:00";}
if (!isset($refresh_rate)) {$refresh_rate="1000000";}
if (!isset($graph_type)) {$graph_type = "sale_graph";}
if (!isset($top_agents)) {$top_agents=10;}
if (!isset($sort_by)) {$sort_by="sales";}

$query_date="$query_date_D $query_date_T";
$end_date="$end_date_D $end_date_T";

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
	}

$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	if (preg_match('/\-ALL/',$group_string) )
		{
		$group[$i] = $groups[$i];
		$all_groups=1;
		}
	$i++;
	}

#######################################
for ($i=0; $i<count($user_group); $i++) 
	{
	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1; $user_group=array("--ALL--");}
	}

$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) { echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	if ($all_user_groups) {$user_group[$i]=$row[0];}
	$i++;
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	if ( (preg_match("/ $group[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_SQL_str=$group_SQL;
	$group_SQL = "and campaign_id IN($group_SQL)";
	}
if (preg_match('/\-\-ALL\-\-/',$group_string) || $all_groups) {$groupQS="&group[]=--ALL--"; $group_string="--ALL--";}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$user_group_SQL .= "'$user_group[$i]',";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$user_group_SQL = "and user_group IN($user_group_SQL)";
	}
if ($all_user_groups) {$user_groupQS="&user_group[]=--ALL--"; $user_group_string="--ALL--";}

$rpt_params="SUBMIT=".$SUBMIT."&DB=".$DB."&query_date_D=".$query_date_D."&query_date_T=".$query_date_T."&report_display_type=".$report_display_type."&refresh_rate=".$refresh_rate."&top_agents=".$top_agents."&sort_by=".$sort_by.$user_groupQS.$groupQS;

if ($SUBMIT==_QXZ("SUBMIT") || $SUBMIT==_QXZ("REFRESH")) {
	$name_stmt="select user, full_name from vicidial_users where user_level>=1 $user_group_SQL";
	$name_rslt=mysql_to_mysqli($name_stmt, $link);
	$name_array=array();
	while ($name_row=mysqli_fetch_row($name_rslt)) {
		$name_array["$name_row[0]"]="$name_row[1]";
	}

	$status_stmt="select status from vicidial_statuses where sale='Y' order by status asc";
	$status_rslt=mysql_to_mysqli($status_stmt, $link);
	$system_sales=array();
	while ($status_row=mysqli_fetch_row($status_rslt)) {
		array_push($system_sales, "$status_row[0]");
	}

	$campaign_status_stmt="select campaign_id, status from vicidial_campaign_statuses where sale='Y' order by campaign_id, status";
	$campaign_status_rslt=mysql_to_mysqli($campaign_status_stmt, $link);
	$campaign_sales=array();
	while ($campaign_status_row=mysqli_fetch_row($campaign_status_rslt)) {
		if (!isset($campaign_sales["$campaign_status_row[0]"])) {$campaign_sales["$campaign_status_row[0]"]=array();}
		array_push($campaign_sales["$campaign_status_row[0]"], "$campaign_status_row[1]");
	}

	$agent_log_stmt="select user, status, campaign_id, event_time From vicidial_agent_log where lead_id is not null and event_time>='$query_date' $group_SQL $user_group_SQL";
	$agent_log_rslt=mysql_to_mysqli($agent_log_stmt, $link);
	$agent_results=array();
	if ($DB) {echo $agent_log_stmt;}
	while ($agent_log_row=mysqli_fetch_row($agent_log_rslt)) {
		$full_name=$name_array["$agent_log_row[0]"];
		$status=$agent_log_row[1];
		$campaign_id=$agent_log_row[2];

		$agent_results["$full_name"][0]++;
		if (in_array("$status", $system_sales) || in_array("$status", $campaign_sales["$campaign_id"])) {
			$agent_results["$full_name"][1]++;
		}
	}

	while (list($key, $val)=each($agent_results)) {
		$new_sales_ary[]=array('agent' => $key, 'calls' => ($val[0]+0), 'sales' => ($val[1]+0), 'conv' => (round(1000*$val[1]/$val[0])/10));
	}

	foreach ($new_sales_ary as $key2 => $row2) {
		$agent_ary[$key2]  = $row2['agent'];
		$calls_ary[$key2]  = $row2['calls'];
		$sales_ary[$key2]  = $row2['sales'];
		$conv_ary[$key2]  = $row2['conv'];

		$gtotals_array['calls']+=$row2['calls'];
		$gtotals_array['sales']+=$row2['sales'];
	}

	// Sort the data with volume descending, edition ascending
	// Add $data as the last parameter, to sort by the common key
	if (($sort_by=="conversions" && $report_display_type=="TABLE") || ($graph_type=="conversion_graph" && $report_display_type=="GRAPH")) {
		array_multisort($conv_ary, SORT_DESC, $sales_ary, SORT_DESC, $calls_ary, SORT_DESC, $agent_ary, SORT_ASC, $new_sales_ary);
	} else if (($sort_by=="calls" && $report_display_type=="TABLE") || ($graph_type=="call_graph" && $report_display_type=="GRAPH")) {
		array_multisort($calls_ary, SORT_DESC, $sales_ary, SORT_DESC, $conv_ary, SORT_DESC, $agent_ary, SORT_ASC, $new_sales_ary);
	} else {
		array_multisort($sales_ary, SORT_DESC, $conv_ary, SORT_DESC, $calls_ary, SORT_DESC, $agent_ary, SORT_ASC, $new_sales_ary);
	}

	$AGENTSjs='var AGENTS=[';
	$CALLSjs='var CALLS=[';
	$SALESjs='var SALES=[';
	$CONVERSION_RATEjs='var CONVERSION_RATE=[';
	$j=0;
	foreach ($new_sales_ary as $row) {
		if ($j<$top_agents) {
			$agent_str.=$row['agent']."|";
			$calls_str.=$row['calls']."|";
			$sales_str.=$row['sales']."|";
			$conv_str.=$row['conv']."|";

			$AGENTSjs .= "'".$row['agent']."',";
			$CALLSjs .= "'".$row['calls']."',";
			$SALESjs .= "'".$row['sales']."',";
			$CONVERSION_RATEjs .= "'".$row['conv']."',";
			$j++;
		}
	}
	$agent_str=preg_replace('/\|$/', "", $agent_str);
	$calls_str=preg_replace('/\|$/', "", $calls_str);
	$sales_str=preg_replace('/\|$/', "", $sales_str);
	$conv_str=preg_replace('/\|$/', "", $conv_str);

	$AGENTSjs=preg_replace('/,$/', "", $AGENTSjs);
	$CALLSjs=preg_replace('/,$/', "", $CALLSjs);
	$SALESjs=preg_replace('/,$/', "", $SALESjs);
	$CONVERSION_RATEjs=preg_replace('/,$/', "", $CONVERSION_RATEjs);

	$AGENTSjs.="];\n";
	$CALLSjs.="];\n";
	$SALESjs.="];\n";
	$CONVERSION_RATEjs.="];\n";
	$JS_arrays=$AGENTSjs.$CALLSjs.$SALESjs.$CONVERSION_RATEjs;

	if ($SUBMIT==_QXZ("REFRESH")) {
		echo $agent_str."\n".$calls_str."\n".$sales_str."\n".$conv_str;
		exit;
	}
}

######################################
if ($DB) {$HTML_text.="$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HTML_head="<HTML>\n";
$HTML_head.="<HEAD>\n";
if ($report_display_type=="TABLE") {
	$HTML_head.="<meta http-equiv=\"refresh\" content=\"".$refresh_rate.";url=".$PHP_SELF."?".$rpt_params."\">";
}
$HTML_head.="<STYLE type=\"text/css\">\n";
$HTML_head.="<!--\n";
$HTML_head.="   .green {color: white; background-color: green}\n";
$HTML_head.="   .red {color: white; background-color: red}\n";
$HTML_head.="   .blue {color: white; background-color: blue}\n";
$HTML_head.="   .purple {color: white; background-color: purple}\n";
$HTML_head.="	.admin_stats_table {width: 95vw; max-width: 800px; }\n";
$HTML_head.="	.graph_canvas {width: 95vw; max-width: 800px; height: 95vw; max-height: 800px;}\n";
$HTML_head.="	.top_settings_key {color: black; font-family: HELVETICA; font-size: calc(8px + (11 - 8) * ((100vw - 300px) / (1600 - 300))); font-weight: bold;}\n";
$HTML_head.="	.top_settings_val {color: black; font-family: HELVETICA; font-size: calc(8px + (11 - 8) * ((100vw - 300px) / (1600 - 300))); font-weight: bold;}\n";
$HTML_head.="	.top_settings_header {color: black; font-family: HELVETICA; font-size: calc(10px + (14 - 10) * ((100vw - 300px) / (1600 - 300)));}\n";
$HTML_head.="	.top_head_key {color: black; font-family: HELVETICA; font-size: calc(4px + (12 - 4) * ((100vw - 300px) / (1600 - 300))); font-weight: bold;}\n";
$HTML_head.="	.top_head_val {color: black; font-family: HELVETICA; font-size: calc(4px + (12 - 4) * ((100vw - 300px) / (1600 - 300)));}\n";
$HTML_head.="	.top_settings_input {color: black; font-family: HELVETICA; font-size: calc(4px + (11 - 4) * ((100vw - 300px) / (1600 - 300)));}\n";
$HTML_head.="-->\n";
$HTML_head.=" </STYLE>\n";

$HTML_head.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_head.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_head.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HTML_head.="<script src='chart/Chart.js'></script>\n"; 
$HTML_head.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR='#".$SSframe_background."' marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 ".($report_display_type=="GRAPH" ? "onload='StartRefresh()'" : "").">$group_S\n";

$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD class='top_settings_key'>";
# $HTML_text.="<b>"._QXZ("$report_name")."</b> $NWB#team_performance_detail$NWE\n";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLSPACING=3 BGCOLOR=\"#".$SSframe_background."\" class='admin_stats_table'><TR>\n";

$HTML_text.="<TD VALIGN=TOP class='android_standard bold'> "._QXZ("Campaigns").":<BR>";
$HTML_text.="<SELECT NAME=group[] multiple class='form_field_android sm_shadow round_corners' style='width:36vw;max-width:100%;'>\n";
if  (preg_match('/\-\-ALL\-\-/',$group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
	{
	if (preg_match("/$groups[$o]\|/i",$group_string)) 
		{$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	else 
		{$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT></TD>\n";

$HTML_text.="<TD VALIGN=TOP class='android_standard bold'>"._QXZ("Teams/User Groups").":<BR>";
$HTML_text.="<SELECT NAME=user_group[] multiple class='form_field_android sm_shadow round_corners' style='width:36vw;max-width:100%;'>\n";

if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/\|$user_groups[$o]\|/i",$user_group_string)) 
		{$HTML_text.="<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	else 
		{$HTML_text.="<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT></TD>\n";
$HTML_text.="</TR>\n";

$HTML_text.="<TR><TD VALIGN=TOP class='android_standard bold'>\n";
$HTML_text.=_QXZ("Start date/time").":<BR>";
$HTML_text.="<INPUT TYPE=TEXT NAME=query_date_D ID=query_date_D style='width:18vw;max-width:50%;' MAXLENGTH=10 VALUE=\"$query_date_D\" class='form_field_android sm_shadow round_corners'>&nbsp;";
/*
$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="	{\n";
$HTML_text.="	window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="	}\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'query_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
*/
$HTML_text.="<INPUT TYPE=TEXT NAME=query_date_T ID=query_date_T style='width:18vw;max-width:50%;' MAXLENGTH=8 VALUE=\"$query_date_T\" class='form_field_android sm_shadow round_corners'>";

# $HTML_text.="&nbsp;"._QXZ("OR")."&nbsp;"._QXZ("within the past")." <input type='text' class='form_field_android sm_shadow round_corners' size='2' maxlength='2' name='hourly_display' id='hourly_display' value='$hourly_display'> "._QXZ("hours");

$HTML_text.="<TD VALIGN=TOP class='android_standard bold'>";
$HTML_text.=_QXZ("Refresh rate").":<BR>";
$HTML_text.="<select name='refresh_rate' id='refresh_rate' class='form_field_android sm_shadow round_corners' style='width:36vw;max-width:100%;'>";
$HTML_text.="<option value='30'".($refresh_rate==30 ? " selected" : "").">30 "._QXZ("seconds")."</option>";
$HTML_text.="<option value='60'".($refresh_rate==60 ? " selected" : "").">60 "._QXZ("seconds")."</option>";
$HTML_text.="<option value='120'".($refresh_rate==120 ? " selected" : "").">2 "._QXZ("minutes")."</option>";
$HTML_text.="<option value='300'".($refresh_rate==300 ? " selected" : "").">5 "._QXZ("minutes")."</option>";
$HTML_text.="</select>";
$HTML_text.="</TD>\n";

$HTML_text.="</TR>";

$HTML_text.="<TR>";
$HTML_text.="<TD VALIGN=TOP class='android_standard bold'>";
$HTML_text.=_QXZ("Show results as").":<BR>";
$HTML_text.="<SELECT NAME=report_display_type class='form_field_android sm_shadow round_corners'>\n";
# $HTML_text.="<option selected value=\"$report_display_type\">$report_display_type</option>\n";
$HTML_text.="<option value=\"\">--</option>\n";
$HTML_text.="<option value=\"TABLE\">"._QXZ("TABLE")."</option>\n";
$HTML_text.="<option value=\"GRAPH\">"._QXZ("GRAPH")."</option>\n";
# $HTML_text.="<option value=\"VERTICAL\">"._QXZ("VERTICAL")."</option>\n";
$HTML_text.="</SELECT></TD>\n";
$HTML_text.="<TD VALIGN=TOP class='android_standard bold'>";
$HTML_text.=_QXZ("Top agents to display").":<BR>";
$HTML_text.="<INPUT TYPE=TEXT NAME=top_agents  style='width:12vw;max-width:50%;' MAXLENGTH=3 VALUE=\"$top_agents\" class='form_field_android sm_shadow round_corners'>";
$HTML_text.="</TD>\n";
$HTML_text.="</TR>";

$HTML_text.="<TR>";
$HTML_text.="<TD VALIGN=middle align='center' class='android_standard bold' colspan='2'>";
$HTML_text.="<INPUT TYPE=SUBMIT NAME=SUBMIT class='red_btn_mobile_sm' VALUE='"._QXZ("SUBMIT")."'>\n";
$HTML_text.="</TD></TR>";

$HTML_text.="</TABLE>\n";
$HTML_text.="</FORM>\n";

$HTML_text.="</FONT></PRE>";

$HTML_text.="</TD></TR></TABLE>";

if ($report_display_type=="TABLE") {
	$HTML_text="<BR><table class='admin_stats_table' align='center' cellspacing='0' cellpadding='2'>";
	$HTML_text.="<tr bgcolor='#".$SSstd_row1_background."'>\n";
	$HTML_text.="<th class='android_standard bold'>AGENT NAME</th>\n";
	$HTML_text.="<th class='android_standard bold'><a href='".$PHP_SELF."?".$rpt_params."&sort_by=calls'>CALLS</a></th>\n";
	$HTML_text.="<th class='android_standard bold'><a href='".$PHP_SELF."?".$rpt_params."&sort_by=sales'>SALES</a></th>\n";
	$HTML_text.="<th class='android_standard bold'><a href='".$PHP_SELF."?".$rpt_params."&sort_by=conversions'>CONV %</a></th>\n";
	$HTML_text.="</tr>";

	$i=0;
	foreach ($new_sales_ary as $row) {
		if ($i%2==0) {$bgcolor=$SSstd_row2_background;} else {$bgcolor=$SSstd_row3_background;}

		if ($i<$top_agents) {
			$HTML_text.="<tr bgcolor='#".$bgcolor."'>\n";
			$HTML_text.="<td class='android_standard bold' align='center'>".$row['agent']."</td>\n";
			$HTML_text.="<td class='android_standard bold' align='center'>".$row['calls']."</td>\n";
			$HTML_text.="<td class='android_standard bold' align='center'>".$row['sales']."</td>\n";
			$HTML_text.="<td class='android_standard bold' align='center'>".$row['conv']."%</td>\n";
			$HTML_text.="</tr>";
			$i++;
		}
	}

	$HTML_text.="<tr bgcolor='#".$SSstd_row1_background."'>\n";
	$HTML_text.="<th class='android_standard bold'>TOTALS:</th>\n";
	$HTML_text.="<th class='android_standard bold'>".$gtotals_array['calls']."</th>\n";
	$HTML_text.="<th class='android_standard bold'>".$gtotals_array['sales']."</th>\n";
	$HTML_text.="<th class='android_standard bold'>";
	if ($gtotals_array['calls']>0) {$HTML_text.=(round(1000*$gtotals_array['sales']/$gtotals_array['calls'])/10)."%";} else {$HTML_text.="0.0%";}
	$HTML_text.="</th>\n";
	$HTML_text.="</tr>";
	$HTML_text.="<tr bgcolor='#".$SSstd_row1_background."''>\n";
	$HTML_text.="<td class='android_standard bold' align='center' colspan='4'><INPUT TYPE=button class='red_btn_mobile_sm' VALUE='"._QXZ("BACK TO FORM")."' onClick=\"window.location.href='$PHP_SELF'\">";
	$HTML_text.="</td></tr></table>";
} else if ($report_display_type=="GRAPH") {
	$HTML_text="<BR><input type=hidden name='graph_type' id='graph_type' value='$graph_type'><table class='admin_stats_table' align='center' cellspacing='0' cellpadding='2'>";
	$HTML_text.="<tr bgcolor='#".$SSstd_row2_background."' class='top_settings_header'>\n";
	$HTML_text.="<td class='android_standard bold' align='center'><a href='#' onClick=\"javascript:document.getElementById('graph_type').value='call_graph'; StopRefresh(); StartRefresh();\">CALLS</a></td>";
	$HTML_text.="<td class='android_standard bold' align='center'><a href='#' onClick=\"javascript:document.getElementById('graph_type').value='sale_graph'; StopRefresh(); StartRefresh();\">SALES</a></td>";
	$HTML_text.="<td class='android_standard bold' align='center'><a href='#' onClick=\"javascript:document.getElementById('graph_type').value='conversion_graph'; StopRefresh(); StartRefresh();\">CONVERSION</a></td>";
	$HTML_text.="<td class='android_standard bold' align='center'><select name='refresh_rate' onChange='SwitchIntervals(this.value)' id='refresh_rate' class='form_field_android sm_shadow round_corners'>";
	$HTML_text.="<option value='30'".($refresh_rate==30 ? " selected" : "").">30 "._QXZ("seconds")."</option>";
	$HTML_text.="<option value='60'".($refresh_rate==60 ? " selected" : "").">60 "._QXZ("seconds")."</option>";
	$HTML_text.="<option value='120'".($refresh_rate==120 ? " selected" : "").">2 "._QXZ("minutes")."</option>";
	$HTML_text.="<option value='300'".($refresh_rate==300 ? " selected" : "").">5 "._QXZ("minutes")."</option>";
	$HTML_text.="</select></td>";
	$HTML_text.="</tr>";
	$HTML_text.="<tr bgcolor='#".$SSstd_row2_background."' class='top_settings_header'>\n";
	$HTML_text.="<td class='android_standard bold' align='center' colspan='4'><div class='graph_canvas'>";
	$HTML_text.="<canvas id='AndroidReportCanvas'></canvas></div>";
	$HTML_text.="</td></tr>";
	$HTML_text.="<tr bgcolor='#".$SSstd_row1_background."' class='top_settings_header'>\n";
	$HTML_text.="<td class='android_standard bold' align='center' colspan='4'><INPUT TYPE=button class='red_btn_mobile_sm' VALUE='"._QXZ("BACK TO FORM")."' onClick=\"window.location.href='$PHP_SELF'\">";
	$HTML_text.="</td></tr></table>";
}


$HTML_text.="</BODY>\n";
$HTML_text.="</HTML>\n";


header("Content-type: text/html; charset=utf-8");
$JS_text.="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";
$JS_onload.="}\n";
$JS_text.=$JS_onload;
# $JS_text.="</script>\n";


$HTML_text.=$GRAPH_text;
# $HTML_text.=$JS_text;



echo $HTML_head;
$android_header=1;
require("admin_header.php");
echo $HTML_text;
if ($report_display_type=="GRAPH") {
?>

<script language='Javascript'>
<?php echo $JS_arrays; ?>
var ctx = document.getElementById('AndroidReportCanvas').getContext('2d');
var refresh_rate=<?php echo $refresh_rate; ?>;
var sort_by="<?php echo $sort_by; ?>";
// var top_agents=<?php echo $top_agents; ?>;
var StackedX=false;

function StartRefresh(current_refresh_rate) {
	var graph_type=document.getElementById('graph_type').value;

	CallsChartData = {
		labels: AGENTS,
		datasets: [{
			label: '<?php echo _QXZ("Calls"); ?>',
			backgroundColor: 'rgb(255, 99, 132)',
			borderColor: 'rgb(255, 32, 32)',
			borderWidth: 2,
			data: CALLS
			}]

		};

	SalesChartData = {
		labels: AGENTS,
		datasets: [{
			label: '<?php echo _QXZ("Sales"); ?>',
			backgroundColor: 'rgb(54, 162, 235)',
			borderColor: 'rgb(32, 32, 235)',
			borderWidth: 2,
			data: SALES
			}]

		};

	ConvChartData = {
		labels: AGENTS,
		datasets: [{
			label: '<?php echo _QXZ("Conversion %"); ?>',
			backgroundColor: 'rgb(54, 235, 162)',
			borderColor: 'rgb(32, 128, 32)',
			borderWidth: 2,
			data: CONVERSION_RATE
			}]

		};

	if (graph_type=='call_graph') 
		{
		var currentChartData=CallsChartData;
		var titleText='<?php echo _QXZ("Call Count Report"); ?>';
		}
	else if (graph_type=='sale_graph') 
		{
		var currentChartData=SalesChartData;
		var titleText='<?php echo _QXZ("Sales Count Report"); ?>';
		} 
	else if (graph_type=='conversion_graph') 
		{
		var currentChartData=ConvChartData;
		var titleText='<?php echo _QXZ("Conversion % Report"); ?>';
		}

	var ctx = document.getElementById('AndroidReportCanvas').getContext('2d');
	MainGraph = new Chart(ctx, {
		type: "horizontalBar",
		data: currentChartData,
		options: {
			scales: {
				xAxes: [{
					display: true,
					stacked: StackedX,
					ticks: {
						beginAtZero: true,
						min: 0
					}
				}],
				yAxes: [{
					stacked: StackedX
				}]
			},
			elements: {
				rectangle: {
					borderWidth: 1,
				}
			},
			responsive: true,
			maintainAspectRatio: false,
			legend: {
				display: false
				// position: 'top',
			},
			title: {
				display: true,
				text: titleText
			}
		}
	});

	RefreshReportWindow();
	if (current_refresh_rate) {refresh_rate=current_refresh_rate;}
	rInt=window.setInterval(function() {RefreshReportWindow()}, (refresh_rate*1000));
}
function StopRefresh() {
	MainGraph.destroy();
	clearInterval(rInt);
}
function SwitchIntervals(current_refresh_rate) {
	MainGraph.destroy();
	clearInterval(rInt);
	StartRefresh(current_refresh_rate);
}
function ToggleVisibility(span_name, visibility_flag) {
	var span_vis = document.getElementById(span_name).style;
	if (visibility_flag==1) { span_vis.display = 'block'; } else { span_vis.display = 'none'; }
}
function RefreshReportWindow() {
	var xmlhttp=false;
	var graph_type=document.getElementById('graph_type').value;

	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		var SQL_query="<?php echo $rpt_params; ?>"+"&graph_type="+graph_type;
		var rpt_query=SQL_query.replace("SUBMIT=SUBMIT", "SUBMIT=<?php echo _QXZ("REFRESH"); ?>");

		// alert(rpt_query);
		xmlhttp.open('POST', 'agent_sales_report_mobile.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(rpt_query);
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				response_txt = null;
				response_txt = xmlhttp.responseText;
				// alert(response_txt); return false;
				var report_result_array=response_txt.split("\n");

				AGENTS=report_result_array[0].split("|");
				CALLS=report_result_array[1].split("|");  // NOT GRAPHED, CAN BE
				SALES=report_result_array[2].split("|");  // NOT GRAPHABLE, NOT ON ORIG REPORT
				CONVERSION_RATE=report_result_array[3].split("|");  // NOT GRAPHABLE, NOT ON ORIG REPORT

				CallsChartData = {
					labels: AGENTS,
					datasets: [{
						label: '<?php echo _QXZ("Calls"); ?>',
						backgroundColor: 'rgb(255, 99, 132)',
						borderColor: 'rgb(255, 32, 32)',
						borderWidth: 2,
						data: CALLS
						}]

					};

				SalesChartData = {
					labels: AGENTS,
					datasets: [{
						label: '<?php echo _QXZ("Sales"); ?>',
						backgroundColor: 'rgb(54, 162, 235)',
						borderColor: 'rgb(32, 32, 235)',
						borderWidth: 2,
						data: SALES
						}]

					};

				ConvChartData = {
					labels: AGENTS,
					datasets: [{
						label: '<?php echo _QXZ("Conversion %"); ?>',
						backgroundColor: 'rgb(54, 235, 162)',
						borderColor: 'rgb(32, 128, 32)',
						borderWidth: 2,
						data: CONVERSION_RATE
						}]

					};

				if (graph_type=='call_graph') 
					{
					var currentChartData=CallsChartData;
					var titleText='<?php echo _QXZ("Call Count Report"); ?>';
					}
				else if (graph_type=='sale_graph') 
					{
					var currentChartData=SalesChartData;
					var titleText='<?php echo _QXZ("Sales Count Report"); ?>';
					} 
				else if (graph_type=='conversion_graph') 
					{
					var currentChartData=ConvChartData;
					var titleText='<?php echo _QXZ("Conversion % Report"); ?>';
					}

				if (currentChartData.labels.length!=MainGraph.data.labels.length)
					{ // New axis, destroy old graphs and gauges
					MainGraph.destroy();
					
					var ctx = document.getElementById('AndroidReportCanvas').getContext('2d');
					MainGraph = new Chart(ctx, {
						type: "horizontalBar",
						data: currentChartData,
						options: {
							scales: {
								xAxes: [{
									display: true,
									stacked: StackedX,
									ticks: {
										beginAtZero: true,
										min: 0
									}
								}],
								yAxes: [{
									stacked: StackedX
								}]
							},
							elements: {
								rectangle: {
									borderWidth: 1,
								}
							},
							responsive: true,
							maintainAspectRatio: false,
							legend: {
								display: false
							//	position: 'top',
							},
							title: {
								display: true,
								text: titleText
							}
						}
					});

					} 
				else 
					{
					for (var j=0; j<currentChartData.datasets.length; j++) 
						{
						for (var k=0; k<currentChartData.datasets[j].data.length; k++) 
							{
							if (MainGraph.data.datasets[j].data[k]!=currentChartData.datasets[j].data[k]) 
								{
								MainGraph.data.datasets[j].data[k]=currentChartData.datasets[j].data[k];
								}
							}
						}
					MainGraph.data=currentChartData;
					MainGraph.update();	
				}
				

				}
			}
		}
}
</script>

<?php
}
flush();
?>