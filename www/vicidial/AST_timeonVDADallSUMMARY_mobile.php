<?php 
# AST_timeonVDADallSUMMARY_mobile.php
# 
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# Mobile version of summary report for all campaigns live real-time stats for the VICIDIAL Auto-Dialer all servers
#
# STOP=4000, SLOW=40, GO=4 seconds refresh interval
# 
# changes:
#
# 190129-1258 - First release
# 200414-2000 - Minor display modifications, auto-selecting refresh rate onload
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["RR"]))					{$RR=$_GET["RR"];}
	elseif (isset($_POST["RR"]))		{$RR=$_POST["RR"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["adastats"]))			{$adastats=$_GET["adastats"];}
	elseif (isset($_POST["adastats"]))	{$adastats=$_POST["adastats"];}
if (isset($_GET["types"]))				{$types=$_GET["types"];}
	elseif (isset($_POST["types"]))		{$types=$_POST["types"];}
if (isset($_GET["current_displayed_report"]))				{$current_displayed_report=$_GET["current_displayed_report"];}
	elseif (isset($_POST["current_displayed_report"]))		{$current_displayed_report=$_POST["current_displayed_report"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["browser_dimension"]))	{$browser_dimension=$_GET["browser_dimension"];}
	elseif (isset($_POST["browser_dimension"]))	{$browser_dimension=$_POST["browser_dimension"];}

if (!isset($browser_dimension))			{$browser_dimension=800;}
if (!isset($types))			{$types='LIST ALL CAMPAIGNS';}
$cell_dimension=floor($browser_dimension/10);

$report_name = 'Real-Time Campaign Summary';
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

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

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

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns = $row[0];
$LOGallowed_reports =	$row[1];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|"._QXZ("$report_name")."|\n";
    exit;
	}

$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}

$campaign_typeSQL='';
if (count($types)<2) {
	if ($types == 'AUTO-DIAL ONLY')			{$campaign_typeSQL="and dial_method IN('RATIO','ADAPT_HARD_LIMIT','ADAPT_TAPERED','ADAPT_AVERAGE')";} 
	if ($types == 'MANUAL ONLY')			{$campaign_typeSQL="and dial_method IN('MANUAL','INBOUND_MAN')";} 
	if ($types == 'INBOUND ONLY')			{$campaign_typeSQL="and campaign_allow_inbound='Y'";} 
} else {
	if (!in_array('LIST ALL CAMPAIGNS', $types)) {
		$campaign_typeSQL='and (';
		if (in_array('AUTO-DIAL ONLY', $types)) {$campaign_typeSQL="dial_method IN('RATIO','ADAPT_HARD_LIMIT','ADAPT_TAPERED','ADAPT_AVERAGE') or ";} #  unset($types['AUTO-DIAL ONLY']);
		if (in_array('MANUAL ONLY', $types)) {$campaign_typeSQL="dial_method IN('MANUAL','INBOUND_MAN') or ";} #  unset($types['MANUAL ONLY']);
		if (in_array('INBOUND ONLY', $types)) {$campaign_typeSQL="campaign_allow_inbound='Y' or ";} #  unset($types['INBOUND ONLY']);
		$campaign_typeSQL='campaign_id in ('.implode("', '", $types).')';
		$campaign_typeSQL=')';
	}
}


$stmt="select campaign_id from vicidial_campaigns where active='Y' $LOGallowed_campaignsSQL $campaign_typeSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if (!isset($DB))   {$DB=0;}
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	$i++;
	}

## Display campaigns
$stmt="select campaign_id, campaign_name from vicidial_campaigns where active='Y' $LOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if (!isset($DB))   {$DB=0;}
if ($DB) {$MAIN.="$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$campaign_options="<option value=''>-- INDIVIDUAL CAMPAIGNS --</option>\n";
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$campaign_options.="<option onClick='ClearTypes(1)' value='$row[0]'>$row[0]</option>\n";
	$i++;
	}

$stop_button_class="android_offbutton_noshadow";
$slow_button_class="android_offbutton_noshadow";
$fast_button_class="android_offbutton_noshadow";
if (!isset($RR))   {
	if ($campaigns_to_print<=20) {$RR=4; $fast_button_class="android_onbutton_noshadow";}
	else if ($campaigns_to_print<=500) {$RR=40; $slow_button_class="android_onbutton_noshadow";}
	else {$RR=4000; $stop_button_class="android_offbutton_noshadow";}
}

require("screen_colors.php");


$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="	.green {color: white; background-color: green}\n";
$HEADER.="	.red {color: white; background-color: red}\n";
$HEADER.="	.lightblue {color: black; background-color: #ADD8E6}\n";
$HEADER.="	.blue {color: white; background-color: blue}\n";
$HEADER.="	.midnightblue {color: white; background-color: #191970}\n";
$HEADER.="	.purple {color: white; background-color: purple}\n";
$HEADER.="	.violet {color: black; background-color: #EE82EE} \n";
$HEADER.="	.thistle {color: black; background-color: #D8BFD8} \n";
$HEADER.="	.olive {color: white; background-color: #808000}\n";
$HEADER.="	.yellow {color: black; background-color: yellow}\n";
$HEADER.="	.khaki {color: black; background-color: #F0E68C}\n";
$HEADER.="	.orange {color: black; background-color: orange}\n";

$HEADER.="	.r1 {color: black; background-color: #FFCCCC}\n";
$HEADER.="	.r2 {color: black; background-color: #FF9999}\n";
$HEADER.="	.r3 {color: black; background-color: #FF6666}\n";
$HEADER.="	.r4 {color: white; background-color: #FF0000}\n";
$HEADER.="	.b1 {color: black; background-color: #CCCCFF}\n";
$HEADER.="	.b2 {color: black; background-color: #9999FF}\n";
$HEADER.="	.b3 {color: black; background-color: #6666FF}\n";
$HEADER.="	.b4 {color: white; background-color: #0000FF}\n";
$HEADER.="	a.nodec {text-decoration: none;}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HEADER.="<script src='chart/Chart.js'></script>\n"; 
$HEADER.="<script src=\"pureknob.js\"></script>\n";

$HEADER.="<TITLE>$report_name</TITLE></HEAD><BODY marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onLoad='StartRefresh()' onUnload='javascript:clearInterval(rInt)'>\n"; # BGCOLOR='#".$SSmenu_background."' 

$MAIN.="<FORM action=$PHP_SELF method=POST><TABLE CELLPADDING=4 CELLSPACING=0><TR><TD><B>$report_name</B><BR>";

$k=0;
$JS_text.="var CAMPAIGNS=[";
$JS_text2="var ALL_CAMPAIGNS=[";
$JS_knob="";
$sub_pct_display="";
while($k<$groups_to_print)
	{
	$NFB = '<b><font size=3 face="courier">';
	$NFE = '</font></b>';
	$F=''; $FG=''; $B=''; $BG='';

	$group = $groups[$k];
	$JS_text.="'$group',";
	$JS_text2.="'$group',";
	$sub_pct_display.="<span id='".$group."_SPD_span'><BR><table class='android_table' border='0'><tr><th class='android_auto_percent'>$group</th><th class='android_auto_percent'><div id='".$group."_SPD_drop'></div></th><th class='android_auto_percent'><div id='".$group."_SPD_diff'></th><th class='android_auto_percent'><div id='".$group."_SPD_diff1min'></th></tr>";
	$sub_pct_display.="</table></span>";

	$CSV_text.="\"$group\"\n";

	$stmt = "select count(*) from vicidial_campaigns where campaign_id='$group' and campaign_allow_inbound='Y';";
	$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$campaign_allow_inbound = $row[0];

	$stmt="select auto_dial_level,dial_status_a,dial_status_b,dial_status_c,dial_status_d,dial_status_e,lead_order,lead_filter_id,hopper_level,dial_method,adaptive_maximum_level,adaptive_dropped_percentage,adaptive_dl_diff_target,adaptive_intensity,available_only_ratio_tally,adaptive_latest_server_time,local_call_time,dial_timeout,dial_statuses from vicidial_campaigns where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$DIALlev["$group"] =		$row[0];
	$DIALstatusA["$group"] =	$row[1];
	$DIALstatusB["$group"] =	$row[2];
	$DIALstatusC["$group"] =	$row[3];
	$DIALstatusD["$group"] =	$row[4];
	$DIALstatusE["$group"] =	$row[5];
	$DIALorder["$group"] =	$row[6];
	$DIALfilter["$group"] =	$row[7];
	$HOPlev["$group"] =		$row[8];
	$DIALmethod["$group"] =	$row[9];
	$maxDIALlev["$group"] =	$row[10];
	$DROPmax["$group"] =		$row[11];
	$targetDIFF["$group"] =	$row[12];
	$ADAintense["$group"] =	$row[13];
	$ADAavailonly["$group"] =	$row[14];
	$TAPERtime["$group"] =	$row[15];
	$CALLtime["$group"] =		$row[16];
	$DIALtimeout["$group"] =	$row[17];
	$DIALstatuses["$group"] =	$row[18];
	$DIALstatuses["$group"] = (preg_replace("/ -$|^ /","",$DIALstatuses["$group"]));
	$DIALstatuses["$group"] = (preg_replace('/\s/', ', ', $DIALstatuses["$group"]));

	$stmt="select count(*) from vicidial_hopper where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$VDhop["$group"] = $row[0];

	$stmt="select dialable_leads,calls_today,drops_today,drops_answers_today_pct,differential_onemin,agents_average_onemin,balance_trunk_fill,answers_today,status_category_1,status_category_count_1,status_category_2,status_category_count_2,status_category_3,status_category_count_3,status_category_4,status_category_count_4,agent_calls_today,agent_wait_today,agent_custtalk_today,agent_acw_today,agent_pause_today from vicidial_campaign_stats where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$DAleads["$group"] =			$row[0];
	$callsTODAY["$group"] =		$row[1];
	$dropsTODAY["$group"] =		$row[2];
	$drpctTODAY["$group"] =		$row[3];
	$diffONEMIN["$group"] =		$row[4];
	$agentsONEMIN["$group"] =		$row[5];
	$balanceFILL["$group"] =		$row[6];
	$answersTODAY["$group"] =		$row[7];
	$VSCcat1["$group"] =			$row[8];
	$VSCcat1tally["$group"] =		$row[9];
	$VSCcat2["$group"] =			$row[10];
	$VSCcat2tally["$group"] =		$row[11];
	$VSCcat3["$group"] =			$row[12];
	$VSCcat3tally["$group"] =		$row[13];
	$VSCcat4["$group"] =			$row[14];
	$VSCcat4tally["$group"] =		$row[15];
	$VSCagentcalls["$group"] =	$row[16];
	$VSCagentwait["$group"] =		$row[17];
	$VSCagentcust["$group"] =		$row[18];
	$VSCagentacw["$group"] =		$row[19];
	$VSCagentpause["$group"] =	$row[20];

	$diffpctONEMIN = ( MathZDC($diffONEMIN["$group"], $agentsONEMIN["$group"]) * 100);
	$diffpctONEMIN = sprintf("%01.2f", $diffpctONEMIN);
	$diffpctONEMIN_ary["$group"] = $diffpctONEMIN;

	$stmt="select sum(local_trunk_shortage) from vicidial_campaign_server_stats where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$balanceSHORT["$group"] = $row[0];

	################################################################################
	### START calculating calls/agents
	################################################################################

	################################################################################
	###### OUTBOUND CALLS
	################################################################################
	if ($campaign_allow_inbound > 0)
		{
		$stmt="select closer_campaigns from vicidial_campaigns where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$closer_campaigns = preg_replace("/^ | -$/","",$row[0]);
		$closer_campaigns = preg_replace("/ /","','",$closer_campaigns);
		$closer_campaigns = "'$closer_campaigns'";

		$stmt="select status from vicidial_auto_calls where status NOT IN('XFER') and ( (call_type='IN' and campaign_id IN($closer_campaigns)) or (campaign_id='" . mysqli_real_escape_string($link, $group) . "' and call_type='OUT') );";
		}
	else
		{
		if ($group=='XXXX-ALL-ACTIVE-XXXX') {$groupSQL = '';}
		else {$groupSQL = " and campaign_id='" . mysqli_real_escape_string($link, $group) . "'";}

		$stmt="select status from vicidial_auto_calls where status NOT IN('XFER') $groupSQL;";
		}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$parked_to_print = mysqli_num_rows($rslt);
	if ($parked_to_print > 0)
		{
		$i=0;
		$out_total["$group"]=0;
		$out_ring["$group"]=0;
		$out_live["$group"]=0;
		$in_ivr["$group"]=0;
		while ($i < $parked_to_print)
			{
			$row=mysqli_fetch_row($rslt);

			if (preg_match("/LIVE/i",$row[0])) 
				{$out_live["$group"]++;}
			else
				{
				if (preg_match("/IVR/i",$row[0])) 
					{$in_ivr["$group"]++;}
				if (preg_match("/CLOSER/i",$row[0])) 
					{$nothing=1;}
				else 
					{$out_ring["$group"]++;}
				}
			$out_total["$group"]++;
			$i++;
			}

			if ($out_live > 0) {$F='<FONT class="r1">'; $FG='</FONT>';}
			if ($out_live > 4) {$F='<FONT class="r2">'; $FG='</FONT>';}
			if ($out_live > 9) {$F='<FONT class="r3">'; $FG='</FONT>';}
			if ($out_live > 14) {$F='<FONT class="r4">'; $FG='</FONT>';}

		}

	###################################################################################
	###### TIME ON SYSTEM
	###################################################################################

	$agent_incall["$group"]=0;
	$agent_ready["$group"]=0;
	$agent_paused["$group"]=0;
	$agent_total["$group"]=0;

	$stmt="select extension,user,conf_exten,status,server_ip,UNIX_TIMESTAMP(last_call_time),UNIX_TIMESTAMP(last_call_finish),call_server_ip,campaign_id from vicidial_live_agents where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$talking_to_print = mysqli_num_rows($rslt);
		if ($talking_to_print > 0)
		{
		$i=0;
		$agentcount=0;
		while ($i < $talking_to_print)
			{
			$row=mysqli_fetch_row($rslt);
				if (preg_match("/READY|PAUSED/i",$row[3]))
				{
				$row[5]=$row[6];
				}
			$Lstatus =			$row[3];
			$status =			sprintf("%-6s", $row[3]);
			if (!preg_match("/INCALL|QUEUE/i",$row[3]))
				{$call_time_S = ($STARTtime - $row[6]);}
			else
				{$call_time_S = ($STARTtime - $row[5]);}

			$call_time_M = MathZDC($call_time_S, 60);
			$call_time_M = round($call_time_M, 2);
			$call_time_M_int = intval("$call_time_M");
			$call_time_SEC = ($call_time_M - $call_time_M_int);
			$call_time_SEC = ($call_time_SEC * 60);
			$call_time_SEC = round($call_time_SEC, 0);
			if ($call_time_SEC < 10) {$call_time_SEC = "0$call_time_SEC";}
			$call_time_MS = "$call_time_M_int:$call_time_SEC";
			$call_time_MS =		sprintf("%7s", $call_time_MS);
			$G = '';		$EG = '';
			if (preg_match("/PAUSED/i",$row[3])) 
				{
				if ($call_time_M_int >= 30) 
					{$i++; continue;} 
				else
					{
					$agent_paused["$group"]++;  
					$agent_total["$group"]++;
					}
				}

			if ( (preg_match("/INCALL/i",$status)) or (preg_match("/QUEUE/i",$status)) ) {$agent_incall["$group"]++;  $agent_total["$group"]++;}
			if ( (preg_match("/READY/i",$status)) or (preg_match("/CLOSER/i",$status)) ) {$agent_ready["$group"]++;  $agent_total["$group"]++;}
			$agentcount++;
			$i++;

			}
		}

	################################################################################
	### END calculating calls/agents
	################################################################################

	if ($VSCagentcalls > 0)
		{
		$avgpauseTODAY = MathZDC($VSCagentpause["$group"], $VSCagentcalls["$group"]);
		$avgpauseTODAY = round($avgpauseTODAY, 0);
		$avgpauseTODAY = sprintf("%01.0f", $avgpauseTODAY);
		$avgpauseTODAY_ary["$group"]=$avgpauseTODAY;

		$avgwaitTODAY = MathZDC($VSCagentwait["$group"], $VSCagentcalls["$group"]);
		$avgwaitTODAY = round($avgwaitTODAY, 0);
		$avgwaitTODAY = sprintf("%01.0f", $avgwaitTODAY);
		$avgwaitTODAY_ary["$group"]=$avgwaitTODAY;

		$avgcustTODAY = MathZDC($VSCagentcust["$group"], $VSCagentcalls["$group"]);
		$avgcustTODAY = round($avgcustTODAY, 0);
		$avgcustTODAY = sprintf("%01.0f", $avgcustTODAY);
		$avgcustTODAY_ary["$group"]=$avgcustTODAY;

		$avgacwTODAY = MathZDC($VSCagentacw["$group"], $VSCagentcalls["$group"]);
		$avgacwTODAY = round($avgacwTODAY, 0);
		$avgacwTODAY = sprintf("%01.0f", $avgacwTODAY);
		$avgacwTODAY_ary["$group"]=$avgacwTODAY;
		}

#	$MAIN.="</div>\n\n<BR><BR>";



	$k++;
	}
$JS_text=preg_replace('/,$/', '', $JS_text);
$JS_text.="];\n";
$JS_text2=preg_replace('/,$/', '', $JS_text2);
$JS_text2.="];\n";
$JS_text.=$JS_text2;

# Output all arrays
$n=0;
$DIALlev_JS='var DIAL_lev=[';
$DIALstatusA_JS='var DIALstatusA=[';
$DIALstatusB_JS='var DIALstatusB=[';
$DIALstatusC_JS='var DIALstatusC=[';
$DIALstatusD_JS='var DIALstatusD=[';
$DIALstatusE_JS='var DIALstatusE=[';
$DIALorder_JS='var DIALorder=[';
$DIALfilter_JS='var DIALfilter=[';
$HOPlev_JS='var HOPlev=[';
$DIALmethod_JS='var DIALmethod=[';
$maxDIALlev_JS='var maxDIALlev=[';
$DROPmax_JS='var DROPmax=[';
$targetDIFF_JS='var targetDIFF=[';
$ADAintense_JS='var ADAintense=[';
$ADAavailonly_JS='var ADAavailonly=[';
$TAPERtime_JS='var TAPERtime=[';
$CALLtime_JS='var CALLtime=[';
$DIALtimeout_JS='var DIALtimeout=[';
$DIALstatuses_JS='var DIALstatuses=[';
$VDhop_JS='var VDhop=[';
$DAleads_JS='var DAleads=[';
$callsTODAY_JS='var callsTODAY=[';
$dropsTODAY_JS='var dropsTODAY=[';
$drpctTODAY_JS='var drpctTODAY=[';
$diffONEMIN_JS='var diffONEMIN=[';
$agentsONEMIN_JS='var agentsONEMIN=[';
$balanceFILL_JS='var balanceFILL=[';
$answersTODAY_JS='var answersTODAY=[';
$VSCcat1_JS='var VSCcat1=[';
$VSCcat1tally_JS='var VSCcat1tally=[';
$VSCcat2_JS='var VSCcat2=[';
$VSCcat2tally_JS='var VSCcat2tally=[';
$VSCcat3_JS='var VSCcat3=[';
$VSCcat3tally_JS='var VSCcat3tally=[';
$VSCcat4_JS='var VSCcat4=[';
$VSCcat4tally_JS='var VSCcat4tally=[';
$VSCagentcalls_JS='var VSCagentcalls=[';
$VSCagentwait_JS='var VSCagentwait=[';
$VSCagentcust_JS='var VSCagentcust=[';
$VSCagentacw_JS='var VSCagentacw=[';
$VSCagentpause_JS='var VSCagentpause=[';
$diffpctONEMIN_ary_JS='var diffpctONEMIN_ary=[';
$balanceSHORT_JS='var balanceSHORT=[';
$out_total_JS='var out_total=[';
$out_ring_JS='var out_ring=[';
$out_live_JS='var out_live=[';
$in_ivr_JS='var in_ivr=[';
$agent_incall_JS='var agent_incall=[';
$agent_ready_JS='var agent_ready=[';
$agent_paused_JS='var agent_paused=[';
$agent_total_JS='var agent_total=[';
$avgpauseTODAY_ary_JS='var avgpauseTODAY_ary=[';
$avgwaitTODAY_ary_JS='var avgwaitTODAY_ary=[';
$avgcustTODAY_ary_JS='var avgcustTODAY_ary=[';
$avgacwTODAY_ary_JS='var avgacwTODAY_ary=[';

while ($n<$groups_to_print) {
	# $DIALlev["$group"] =		$row[0];
	$DIALlev_JS .=	"'".$DIALlev[$groups[$n]]."',";
	$DIALstatusA_JS .= "'".$DIALstatusA[$groups[$n]]."',";
	$DIALstatusB_JS .= "'".$DIALstatusB[$groups[$n]]."',";
	$DIALstatusC_JS .= "'".$DIALstatusC[$groups[$n]]."',";
	$DIALstatusD_JS .= "'".$DIALstatusD[$groups[$n]]."',";
	$DIALstatusE_JS .= "'".$DIALstatusE[$groups[$n]]."',";
	$DIALorder_JS .= "'".$DIALorder[$groups[$n]]."',";
	$DIALfilter_JS .= "'".$DIALfilter[$groups[$n]]."',";
	$HOPlev_JS .= "'".$HOPlev[$groups[$n]]."',";
	$DIALmethod_JS .= "'".$DIALmethod[$groups[$n]]."',";
	$maxDIALlev_JS .= "'".$maxDIALlev[$groups[$n]]."',";
	$DROPmax_JS .= "'".$DROPmax[$groups[$n]]."',";
	$targetDIFF_JS .= "'".$targetDIFF[$groups[$n]]."',";
	$ADAintense_JS .= "'".$ADAintense[$groups[$n]]."',";
	$ADAavailonly_JS .= "'".$ADAavailonly[$groups[$n]]."',";
	$TAPERtime_JS .= "'".$TAPERtime[$groups[$n]]."',";
	$CALLtime_JS .= "'".$CALLtime[$groups[$n]]."',";
	$DIALtimeout_JS .= "'".$DIALtimeout[$groups[$n]]."',";
	$DIALstatuses_JS .= "'".$DIALstatuses[$groups[$n]]."',";
	$VDhop_JS .= "'".$VDhop[$groups[$n]]."',";
	$DAleads_JS .= "'".$DAleads[$groups[$n]]."',";
	$callsTODAY_JS .= "'".$callsTODAY[$groups[$n]]."',";
	$dropsTODAY_JS .= "'".$dropsTODAY[$groups[$n]]."',";
	$drpctTODAY_JS .= "'".$drpctTODAY[$groups[$n]]."',";
	$diffONEMIN_JS .= "'".$diffONEMIN[$groups[$n]]."',";
	$agentsONEMIN_JS .= "'".$agentsONEMIN[$groups[$n]]."',";
	$balanceFILL_JS .= "'".$balanceFILL[$groups[$n]]."',";
	$answersTODAY_JS .= "'".$answersTODAY[$groups[$n]]."',";
	$VSCcat1_JS .= "'".$VSCcat1[$groups[$n]]."',";
	$VSCcat1tally_JS .= "'".$VSCcat1tally[$groups[$n]]."',";
	$VSCcat2_JS .= "'".$VSCcat2[$groups[$n]]."',";
	$VSCcat2tally_JS .= "'".$VSCcat2tally[$groups[$n]]."',";
	$VSCcat3_JS .= "'".$VSCcat3[$groups[$n]]."',";
	$VSCcat3tally_JS .= "'".$VSCcat3tally[$groups[$n]]."',";
	$VSCcat4_JS .= "'".$VSCcat4[$groups[$n]]."',";
	$VSCcat4tally_JS .= "'".$VSCcat4tally[$groups[$n]]."',";
	$VSCagentcalls_JS .= "'".$VSCagentcalls[$groups[$n]]."',";
	$VSCagentwait_JS .= "'".$VSCagentwait[$groups[$n]]."',";
	$VSCagentcust_JS .= "'".$VSCagentcust[$groups[$n]]."',";
	$VSCagentacw_JS .= "'".$VSCagentacw[$groups[$n]]."',";
	$VSCagentpause_JS .= "'".$VSCagentpause[$groups[$n]]."',";
	$diffpctONEMIN_ary_JS .= "'".$diffpctONEMIN_ary[$groups[$n]]."',";
	$balanceSHORT_JS .= "'".$balanceSHORT[$groups[$n]]."',";
	$out_total_JS .= "'".$out_total[$groups[$n]]."',";
	$out_ring_JS .= "'".$out_ring[$groups[$n]]."',";
	$out_live_JS .= "'".$out_live[$groups[$n]]."',";
	$in_ivr_JS .= "'".$in_ivr[$groups[$n]]."',";
	$agent_incall_JS .= "'".$agent_incall[$groups[$n]]."',";
	$agent_ready_JS .= "'".$agent_ready[$groups[$n]]."',";
	$agent_paused_JS .= "'".$agent_paused[$groups[$n]]."',";
	$agent_total_JS .= "'".$agent_total[$groups[$n]]."',";
	$avgpauseTODAY_ary_JS .= "'".$avgpauseTODAY_ary[$groups[$n]]."',";
	$avgwaitTODAY_ary_JS .= "'".$avgwaitTODAY_ary[$groups[$n]]."',";
	$avgcustTODAY_ary_JS .= "'".$avgcustTODAY_ary[$groups[$n]]."',";
	$avgacwTODAY_ary_JS .= "'".$avgacwTODAY_ary[$groups[$n]]."',";
	$n++;
}

$DIALlev_JS.="];\n";
$DIALstatusA_JS.="];\n";
$DIALstatusB_JS.="];\n";
$DIALstatusC_JS.="];\n";
$DIALstatusD_JS.="];\n";
$DIALstatusE_JS.="];\n";
$DIALorder_JS.="];\n";
$DIALfilter_JS.="];\n";
$HOPlev_JS.="];\n";
$DIALmethod_JS.="];\n";
$maxDIALlev_JS.="];\n";
$DROPmax_JS.="];\n";
$targetDIFF_JS.="];\n";
$ADAintense_JS.="];\n";
$ADAavailonly_JS.="];\n";
$TAPERtime_JS.="];\n";
$CALLtime_JS.="];\n";
$DIALtimeout_JS.="];\n";
$DIALstatuses_JS.="];\n";
$VDhop_JS.="];\n";
$DAleads_JS.="];\n";
$callsTODAY_JS.="];\n";
$dropsTODAY_JS.="];\n";
$drpctTODAY_JS.="];\n";
$diffONEMIN_JS.="];\n";
$agentsONEMIN_JS.="];\n";
$balanceFILL_JS.="];\n";
$answersTODAY_JS.="];\n";
$VSCcat1_JS.="];\n";
$VSCcat1tally_JS.="];\n";
$VSCcat2_JS.="];\n";
$VSCcat2tally_JS.="];\n";
$VSCcat3_JS.="];\n";
$VSCcat3tally_JS.="];\n";
$VSCcat4_JS.="];\n";
$VSCcat4tally_JS.="];\n";
$VSCagentcalls_JS.="];\n";
$VSCagentwait_JS.="];\n";
$VSCagentcust_JS.="];\n";
$VSCagentacw_JS.="];\n";
$VSCagentpause_JS.="];\n";
$diffpctONEMIN_ary_JS.="];\n";
$balanceSHORT_JS.="];\n";
$out_total_JS.="];\n";
$out_ring_JS.="];\n";
$out_live_JS.="];\n";
$in_ivr_JS.="];\n";
$agent_incall_JS.="];\n"; 
$agent_ready_JS.="];\n";
$agent_paused_JS.="];\n";
$agent_total_JS.="];\n";
$avgpauseTODAY_ary_JS.="];\n";
$avgwaitTODAY_ary_JS.="];\n";
$avgcustTODAY_ary_JS.="];\n";
$avgacwTODAY_ary_JS.="];\n";

$JS_text.=$DIALlev_JS.$DIALstatusA_JS.$DIALstatusB_JS.$DIALstatusC_JS.$DIALstatusD_JS.$DIALstatusE_JS.$DIALorder_JS.$DIALfilter_JS.$HOPlev_JS.$DIALmethod_JS.$maxDIALlev_JS.$DROPmax_JS.$targetDIFF_JS.$ADAintense_JS.$ADAavailonly_JS.$TAPERtime_JS.$CALLtime_JS.$DIALtimeout_JS.$DIALstatuses_JS.$VDhop_JS.$DAleads_JS.$callsTODAY_JS.$dropsTODAY_JS.$drpctTODAY_JS.$diffONEMIN_JS.$agentsONEMIN_JS.$balanceFILL_JS.$answersTODAY_JS.$VSCcat1_JS.$VSCcat1tally_JS.$VSCcat2_JS.$VSCcat2tally_JS.$VSCcat3_JS.$VSCcat3tally_JS.$VSCcat4_JS.$VSCcat4tally_JS.$VSCagentcalls_JS.$VSCagentwait_JS.$VSCagentcust_JS.$VSCagentacw_JS.$VSCagentpause_JS.$diffpctONEMIN_ary_JS.$balanceSHORT_JS.$out_total_JS.$out_ring_JS.$out_live_JS.$in_ivr_JS.$agent_incall_JS.$agent_ready_JS.$agent_paused_JS.$agent_total_JS.$avgpauseTODAY_ary_JS.$avgwaitTODAY_ary_JS.$avgcustTODAY_ary_JS.$avgacwTODAY_ary_JS;
$JS_text=preg_replace('/,];/', '];', $JS_text);


$MAIN.="<span id='graph_display' style='display:block; background:white;'>";
$MAIN.="<table class='android_table' border='0' cellpadding='0' cellspacing='5'>";

$MAIN.="<tr valign='top'>";
$MAIN.="<th rowspan='2'><font class='android_campaign_header'>"._QXZ("CAMPAIGNS").": </font><BR><select name=types onBlur='RefreshReportWindow()' size='5' multiple class='form_field_android'>\n";
$MAIN.="<option onClick='ClearTypes(0)' value=\"LIST ALL CAMPAIGNS\"";
	if ($types == 'LIST ALL CAMPAIGNS') {$MAIN.=" selected";} 
$MAIN.=">"._QXZ("LIST ALL CAMPAIGNS")."</option>";
$MAIN.="<option onClick='ClearTypes(1)' value=\"AUTO-DIAL ONLY\"";
	if ($types == 'AUTO-DIAL ONLY') {$MAIN.=" selected";} 
$MAIN.=">"._QXZ("AUTO-DIAL ONLY")."</option>";
$MAIN.="<option onClick='ClearTypes(1)' value=\"MANUAL ONLY\"";
	if ($types == 'MANUAL ONLY') {$MAIN.=" selected";} 
$MAIN.=">"._QXZ("MANUAL ONLY")."</option>";
$MAIN.="<option onClick='ClearTypes(1)' value=\"INBOUND ONLY\"";
	if ($types == 'INBOUND ONLY') {$MAIN.=" selected";} 
$MAIN.=">"._QXZ("INBOUND ONLY")."</option>";
$MAIN.=$campaign_options;
$MAIN.="</select> \n";
# $MAIN.="<input type=button name=submit value='"._QXZ("SUBMIT")."' onClick='SwitchGraphs()'>\n";
$MAIN.="</th>";

$MAIN.="<th><ul class=\"dropdown_android\" style='list-style:none'><li><a class='nodec' onClick=\"ToggleVisibility('graph_display', 0); ToggleVisibility('settings_display', 1); ToggleVisibility('percentages_display', 0)\"><div id='settings_button' class='android_switchbutton'>"._QXZ("SETTINGS")."</div></a></li></ul></th>";
$MAIN.="<th><ul class=\"dropdown_android\" style='list-style:none'><li><a class='nodec' onClick=\"ToggleVisibility('graph_display', 0); ToggleVisibility('settings_display', 0); ToggleVisibility('percentages_display', 1)\"><div id='percentage_graph_button' class='android_switchbutton'>"._QXZ("PERCENTS")."</div></a></li></ul></th>";
$MAIN.="</tr>";
$MAIN.="<tr valign='top'>";

$MAIN.="<th>";
$MAIN.="<ul class=\"dropdown_android\" style='list-style:none'><li><div class='android_switchbutton_blue'><a class='nodec' href=\"#\">REPORT</a></div>\n";
	$MAIN.="<ul class=\"sub_menu\" style=\"z-index:1;list-style:none\">\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchGraphs('hopper'); document.getElementById('hopper_graph_button').className='android_onbutton_noshadow'\"><div id='hopper_graph_button' class='android_onbutton_noshadow'>"._QXZ("HOPPER")."</div></a></li>\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchGraphs('agent'); document.getElementById('agent_graph_button').className='android_onbutton_noshadow'\"><div id='agent_graph_button' class='android_offbutton_noshadow'>"._QXZ("AGENT")."</div></a></li>\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchGraphs('call'); document.getElementById('call_graph_button').className='android_onbutton_noshadow'\"><div id='call_graph_button' class='android_offbutton_noshadow'>"._QXZ("CALLS")."</div></a></li>\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchGraphs('dialable'); document.getElementById('dialable_graph_button').className='android_onbutton_noshadow'\"><div id='dialable_graph_button' class='android_offbutton_noshadow'>"._QXZ("DIALABLES")."</div></a></li>\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchGraphs('callsToday'); document.getElementById('callsToday_graph_button').className='android_onbutton_noshadow'\"><div id='callsToday_graph_button' class='android_offbutton_noshadow'>"._QXZ("TODAY")."</div></a></li>\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchGraphs('averages'); document.getElementById('average_graph_button').className='android_onbutton_noshadow'\"><div id='average_graph_button' class='android_offbutton_noshadow'>"._QXZ("AVERAGES")."</div></a></li>\n";
	$MAIN.="</ul>";
$MAIN.="</li></ul>";
$MAIN.="</th>";

$MAIN.="<th>";
$MAIN.="<ul class=\"dropdown_android\" style='list-style:none'><li><div class='android_switchbutton_blue'><a class='nodec' href=\"#\">REFRESH</a></div>\n";
	$MAIN.="<ul class=\"sub_menu\" style=\"z-index:1;list-style:none\">\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchIntervals(4000); document.getElementById('stop_button').className='android_onbutton_noshadow'\"><div id='stop_button' class='$stop_button_class'>"._QXZ("STOP")."</div></a></li>\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchIntervals(40); document.getElementById('slow_button').className='android_onbutton_noshadow'\"><div id='slow_button' class='$slow_button_class'>"._QXZ("SLOW")."</div></a>\n";
		$MAIN.="<li><a class='nodec' onClick=\"SwitchIntervals(4); document.getElementById('go_button').className='android_onbutton_noshadow'\"><div id='go_button' class='$fast_button_class'>"._QXZ("FAST")."</div></a></li>\n";
	$MAIN.="</ul>";
$MAIN.="</li></ul>";
$MAIN.="</th>";

# $MAIN.="<th><ul class=\"dropdown_android\" style='list-style:none'><li><a class='nodec' onClick=\"ToggleVisibility('graph_display', 0); ToggleVisibility('settings_display', 1); ToggleVisibility('percentages_display', 0)\"><div id='settings_button' class='android_switchbutton'>"._QXZ("SETTINGS")."</div></a></li></ul></th>";
# $MAIN.="<th><ul class=\"dropdown_android\" style='list-style:none'><li><a class='nodec' onClick=\"ToggleVisibility('graph_display', 0); ToggleVisibility('settings_display', 0); ToggleVisibility('percentages_display', 1)\"><div id='percentage_graph_button' class='android_switchbutton'>"._QXZ("PERCENTS")."</div></a></li></ul></th>";

$MAIN.="</tr>";

$MAIN.="</table>";

$MAIN.="<input type=hidden name=RR value=$RR>\n";
$MAIN.="<input type=hidden name=DB value=$DB>\n";
$MAIN.="<input type=hidden name=adastats value=$adastats>\n";
$MAIN.="<BR><BR>\n\n";

$MAIN.="<div id='canvas_container' style='height: 75vh; width: 90vw'>";
$MAIN.="<canvas id='AndroidReportCanvas'></canvas>";
# $MAIN.="<p align='center'><input type='button' class='green_btn sm_shadow round_corners' style='width:250px' value='"._QXZ("SHOW TOP 10 PERFORMERS")."' onClick=\"ToggleVisibility('top_10_display'); ToggleVisibility('graph_display');\"></p>";
$MAIN.="</div>";
$MAIN.="</span>";


$MAIN.="<span id='settings_display' style='display:none; background:white'>";
$MAIN.="</span>";

$MAIN.="<span id='percentages_display' style='display:none; background:white'>";

$MAIN.="<table class='android_table' border='0' cellpadding='0' cellspacing='5'>";
$MAIN.="<tr valign='top'><th><a class='nodec' onClick=\"ToggleVisibility('graph_display', 1); ToggleVisibility('settings_display', 0); ToggleVisibility('percentages_display', 0)\"><div id='settings_button' class='android_switchbutton'>"._QXZ("GRAPHS")."</div></a></th></tr>";
$MAIN.="<tr valign='bottom'><th><a class='nodec' onClick=\"ToggleVisibility('graph_display', 0); ToggleVisibility('settings_display', 1); ToggleVisibility('percentages_display', 0)\"><div id='percentage_graph_button' class='android_switchbutton'>"._QXZ("SETTINGS")."</div></a></th></tr>";
$MAIN.="<tr><td>";
## Gauge dimensions
$MAIN.="<span id='percentages_sub_display'>";
$MAIN.="<table class='android_table' border='0'><tr><th class='android_auto_percent'>"._QXZ("Campaign").":</th><th class='android_auto_percent'>"._QXZ("Drop")." %:</th><th class='android_auto_percent'>"._QXZ("Diff")." %:</th><th class='android_auto_percent'>"._QXZ("1-min diff").":</th></tr>";
$MAIN.="</table>";
$MAIN.=$sub_pct_display;
$MAIN.="</span>";
$MAIN.="</td></tr>";
$MAIN.="</table>";

$MAIN.="</span>";

$MAIN.="<input type='hidden' name='graph_type' id='graph_type' value='hopper'>";

$MAIN.="</FORM>\n\n<BR>";
$MAIN.="</PRE>\n";
#$MAIN.="</TD></TR></TABLE>\n";

$MAIN.="</BODY></HTML>\n";

# $MAIN.="$db_source\n";

	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_timeonVDADallSUMMARY_$US$FILE_TIME.csv";
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

		exit;
	} else {
		header ("Content-type: text/html; charset=utf-8");

		echo $HEADER;
		$android_header=1;
		require("admin_header.php");
		# print_r($VDhop);
		echo $MAIN;
		echo "<script language='Javascript'>\n";
		echo $JS_text;
		if (!$RR) {$RR=999999;}
?>

var graph_type=document.getElementById('graph_type').value;
var refresh_rate=<?php echo $RR; ?>;
var gauge_size = Math.floor(window.innerWidth/6) > 200 ? 200 : Math.floor(window.innerWidth/6) < 75 ? 75 : Math.floor(window.innerWidth/6);

var MainGraph, hopperChartData, agentChartData, callChartData, dialableChartData, callsTodayChartData, averagesChartData;

var percentagesText="<table class='android_table' border='0'><tr><th width='25%'><?php echo _QXZ("Campaign"); ?>:</th><th width='25%'><?php echo _QXZ("Drop"); ?> %:</th><th width='25%'><?php echo _QXZ("Diff"); ?> %:</th><th width='25%'><?php echo _QXZ("1-min diff"); ?>:</th></tr>";



function round(value, precision) {
    var multiplier = Math.pow(10, precision || 0);
    return Math.round(value * multiplier) / multiplier;
}

function StartRefresh(current_refresh_rate) {
	graph_type=document.getElementById('graph_type').value;

				hopperChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("Hopper Level"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 2,
							data: HOPlev
						}, {
							label: '<?php echo _QXZ("Leads in Hopper"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 2,
							data: VDhop
						}]

					};

				agentChartData = {
					labels: CAMPAIGNS,
					datasets: [{
//						label: '<?php echo _QXZ("LOGGED IN"); ?>',
//							backgroundColor: 'rgb(235, 162, 235)',
//							borderColor: 'rgb(235, 32, 235)',
//							borderWidth: 1,
//							stack: 'Stack 0',
//							data: agent_total
//						}, 
//						{
							label: '<?php echo _QXZ("INCALL"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 1,
							stack: 'Stack 1',
							data: agent_incall
						}, {
							label: '<?php echo _QXZ("WAITING"); ?>',
							backgroundColor: 'rgb(54, 235, 162)',
							borderColor: 'rgb(32, 128, 32)',
							borderWidth: 1,
							stack: 'Stack 1',
							data: agent_ready
						}, {
							label: '<?php echo _QXZ("PAUSED"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 1,
							stack: 'Stack 1',
							data: agent_paused
						}]

					};

				callChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("TOTAL"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 1,
							data: out_total
						}, {
							label: '<?php echo _QXZ("RINGING"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 1,
							data: out_ring
						}, {
							label: '<?php echo _QXZ("WAITING"); ?>',
							backgroundColor: 'rgb(54, 235, 162)',
							borderColor: 'rgb(32, 128, 32)',
							borderWidth: 1,
							data: out_live
						}, {
							label: '<?php echo _QXZ("IVR"); ?>',
							backgroundColor: 'rgb(235, 162, 235)',
							borderColor: 'rgb(235, 32, 235)',
							borderWidth: 1,
							data: in_ivr
						}]

					};

				dialableChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("DIALABLE LEADS"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 2,
							data: DAleads
						}]

					};

				callsTodayChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("CALLS TODAY"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 2,
							data: callsTODAY
						}, {
							label: '<?php echo _QXZ("ANSWERS TODAY"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 1,
							data: answersTODAY
						}, {
							label: '<?php echo _QXZ("DROPS TODAY"); ?>',
							backgroundColor: 'rgb(54, 235, 162)',
							borderColor: 'rgb(32, 128, 32)',
							borderWidth: 1,
							data: dropsTODAY
						}]

					};

				averagesChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("PAUSE"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 1,
							data: avgpauseTODAY_ary
						}, {
							label: '<?php echo _QXZ("AGENT WAIT"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 1,
							data: avgwaitTODAY_ary
						}, {
							label: '<?php echo _QXZ("CUSTTIME"); ?>',
							backgroundColor: 'rgb(54, 235, 162)',
							borderColor: 'rgb(32, 128, 32)',
							borderWidth: 1,
							data: avgcustTODAY_ary
						}, {
							label: '<?php echo _QXZ("ACW"); ?>',
							backgroundColor: 'rgb(235, 162, 235)',
							borderColor: 'rgb(235, 32, 235)',
							borderWidth: 1,
							data: avgacwTODAY_ary
						}]

					};

				var CanvasChartType='horizontalBar';
				var StackedX=false;
				if (graph_type=='hopper') {
					var currentChartData=hopperChartData;
					var titleText='<?php echo _QXZ("Hopper Level Report"); ?>';
				} else if (graph_type=='agent') {
					var currentChartData=agentChartData;
					var titleText='<?php echo _QXZ("Agent Status Report"); ?>';
					// StackedX=true;
					// CanvasChartType='bar';
				} else if (graph_type=='call') {
					var currentChartData=callChartData;
					var titleText='<?php echo _QXZ("Live Call Count Report"); ?>';
				} else if (graph_type=='dialable') {
					var currentChartData=dialableChartData;
					var titleText='<?php echo _QXZ("Dialable Leads Report"); ?>';
				} else if (graph_type=='callsToday') {
					var currentChartData=callsTodayChartData;
					var titleText='<?php echo _QXZ("Calls Today Report"); ?>';
				} else if (graph_type=='averages') {
					var currentChartData=averagesChartData;
					var titleText='<?php echo _QXZ("Averages Report"); ?>';
				}
					
				var ctx = document.getElementById('AndroidReportCanvas').getContext('2d');
				MainGraph = new Chart(ctx, {
					type: CanvasChartType,
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
							position: 'top',
						},
						title: {
							display: true,
							text: titleText
						}
					}
				});

			
			for (var g=0; g<CAMPAIGNS.length; g++) {

				var campaign_gauge_name=CAMPAIGNS[g];
				var droppct_gauge=campaign_gauge_name+"_SPD_drop";
				var diffpct_gauge=campaign_gauge_name+"_SPD_diff";
				var onemindiff_gauge=campaign_gauge_name+"_SPD_diff1min";

				// GAUGE 1
				var maxDROP=parseFloat(DROPmax[g]);
				var adjusted_maxValue=maxDROP*2;
				var greenZone=Math.round((maxDROP/2)*10)/10;
				var yellowZone=maxDROP;
				if (greenZone>40) {greenZone=40;}
				if (yellowZone>90) {yellowZone=90;}
				if (adjusted_maxValue>100) {adjusted_maxValue=100;}
				else if (parseFloat(drpctTODAY[g])>adjusted_maxValue) {adjusted_maxValue=parseFloat(drpctTODAY[g])};

				var rpt_color="#FF0000";
				if (drpctTODAY[g]<=greenZone) {rpt_color="#009900";}
				else if (drpctTODAY[g]>greenZone && drpctTODAY[g]<=yellowZone) {rpt_color="#DDDD00";}

				var myKnob1 = pureknob.createKnob(gauge_size, gauge_size, 'percent');
				myKnob1.setProperty('angleStart', -0.75 * Math.PI);
				myKnob1.setProperty('angleEnd', 0.75 * Math.PI);
				myKnob1.setProperty('colorFG', rpt_color);
				myKnob1.setProperty('trackWidth', 0.4);
				myKnob1.setProperty('valMin', 0);
				myKnob1.setProperty('valMax', adjusted_maxValue);
				myKnob1.setValue(drpctTODAY[g]);
				var node1 = myKnob1.node();
				var elem1 = document.getElementById(droppct_gauge);
				while (elem1.hasChildNodes()) {
					elem1.removeChild(elem1.lastChild);
				}
				elem1.appendChild(node1);
				// END GAUGE

				// GAUGE 2
				greenZone=10;
				yellowZone=25;

				var rpt_color="#FF0000";
				if (diffpctONEMIN_ary[g]<=greenZone) {rpt_color="#009900";}
				else if (diffpctONEMIN_ary[g]>greenZone && diffpctONEMIN_ary[g]<=yellowZone) {rpt_color="#DDDD00";}

				var myKnob2 = pureknob.createKnob(gauge_size, gauge_size, 'percent');
				myKnob2.setProperty('angleStart', -0.75 * Math.PI);
				myKnob2.setProperty('angleEnd', 0.75 * Math.PI);
				myKnob2.setProperty('colorFG', rpt_color);
				myKnob2.setProperty('trackWidth', 0.4);
				myKnob2.setProperty('valMin', 0);
				myKnob2.setProperty('valMax', 100);
				myKnob2.setValue(diffpctONEMIN_ary[g]);
				var node2 = myKnob2.node();
				var elem2 = document.getElementById(diffpct_gauge);
				while (elem2.hasChildNodes()) {
					elem2.removeChild(elem2.lastChild);
				}
				elem2.appendChild(node2);
				// END GAUGE

				// GAUGE 3
				var diff_target=parseFloat(targetDIFF[g]);
				diff_greenZonepos=diff_target+10;
				if (diff_greenZonepos>40) {
					diff_greenZonepos=40;
				}
				diff_greenZoneneg=diff_target-10;
				if (diff_greenZoneneg<-40) {
					diff_greenZoneneg=-40;
				}
				diff_yellowZonepos=diff_greenZonepos+10;
				if (diff_yellowZonepos>40) {
					diff_yellowZonepos=40;
				}
				diff_yellowZoneneg=diff_greenZoneneg-10;
				if (diff_yellowZoneneg<-40) {
					diff_yellowZoneneg=-40;
				}
				var rpt_color="#FF0000";
				if (drpctTODAY[g]>=diff_greenZoneneg && drpctTODAY[g]<=diff_greenZonepos) {rpt_color="#009900";}
				else if ((drpctTODAY[g]>=diff_yellowZoneneg && drpctTODAY[g]<diff_greenZoneneg) || (drpctTODAY[g]>diff_greenZonepos && drpctTODAY[g]<=diff_yellowZonepos)) {rpt_color="#DDDD00";}

				var myKnob3 = pureknob.createKnob(gauge_size, gauge_size, 'float');
				myKnob3.setProperty('angleStart', -0.75 * Math.PI);
				myKnob3.setProperty('angleEnd', 0.75 * Math.PI);
				myKnob3.setProperty('colorFG', rpt_color);
				myKnob3.setProperty('trackWidth', 0.4);
				myKnob3.setProperty('valMin', -40);
				myKnob3.setProperty('valMax', 40);
				myKnob3.setValue(diffONEMIN[g]);
				var node3 = myKnob3.node();
				var elem3 = document.getElementById(onemindiff_gauge);
				while (elem3.hasChildNodes()) {
					elem3.removeChild(elem3.lastChild);
				}
				elem3.appendChild(node3);
				// END GAUGE



			}


			// document.getElementById("percentages_sub_display").innerHTML=gaugesText;

	RefreshReportWindow();
	if (current_refresh_rate) {refresh_rate=current_refresh_rate;}
	rInt=window.setInterval(function() {RefreshReportWindow()}, (refresh_rate*1000));
}
function StopRefresh() {
	MainGraph.clear();
	clearInterval(rInt);
}
function SwitchGraphs(current_graph_type) {
	document.getElementById('hopper_graph_button').className='android_offbutton_noshadow';
	document.getElementById('agent_graph_button').className='android_offbutton_noshadow';
	document.getElementById('call_graph_button').className='android_offbutton_noshadow';
	document.getElementById('dialable_graph_button').className='android_offbutton_noshadow';
	document.getElementById('callsToday_graph_button').className='android_offbutton_noshadow';
	document.getElementById('average_graph_button').className='android_offbutton_noshadow';

	if (current_graph_type) 
		{
		document.getElementById('graph_type').value=current_graph_type;
		}
	MainGraph.destroy();
	clearInterval(rInt);
	StartRefresh();
}
function SwitchCampaigns() {
	MainGraph.destroy();
	clearInterval(rInt);
	StartRefresh();
}
function SwitchIntervals(current_refresh_rate) {
	document.getElementById('stop_button').className='android_offbutton_noshadow';
	document.getElementById('slow_button').className='android_offbutton_noshadow';
	document.getElementById('go_button').className='android_offbutton_noshadow';
	MainGraph.destroy();
	clearInterval(rInt);
	StartRefresh(current_refresh_rate);
}
function ToggleVisibility(span_name, visibility_flag) {
	var span_vis = document.getElementById(span_name).style;
	if (visibility_flag==1) { span_vis.display = 'block'; } else { span_vis.display = 'none'; }
}

function ClearTypes(sIndex) {
	var InvForm = document.forms[0];
	if (sIndex==0) 
		{
		for (x=1;x<InvForm.types.length;x++)
			{
			InvForm.types[x].selected=false;
			}
		}
	else 
		{
		InvForm.types[0].selected=false;
		}

	RefreshReportWindow();
}

function RefreshReportWindow() {
	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		var rpt_query="";
        var InvForm = document.forms[0];
		var types_str="";
        for (x=0;x<InvForm.types.length;x++)
			{
			if (InvForm.types[x].selected)
				{
				rpt_query +="&types[]="+InvForm.types[x].value;
				types_str+=InvForm.types[x].value+", ";
				}
			}

		// alert(rpt_query);
		xmlhttp.open('POST', 'campaign_summary_mobile_report.php'); 
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

				CAMPAIGNS=report_result_array[0].split("|");
				DIALlev=report_result_array[1].split("|");  // NOT GRAPHED, CAN BE
				DIALstatusA=report_result_array[2].split("|");  // NOT GRAPHABLE, NOT ON ORIG REPORT
				DIALstatusB=report_result_array[3].split("|");  // NOT GRAPHABLE, NOT ON ORIG REPORT
				DIALstatusC=report_result_array[4].split("|");  // NOT GRAPHABLE, NOT ON ORIG REPORT
				DIALstatusD=report_result_array[5].split("|");  // NOT GRAPHABLE, NOT ON ORIG REPORT
				DIALstatusE=report_result_array[6].split("|");  // NOT GRAPHABLE, NOT ON ORIG REPORT
				DIALorder=report_result_array[7].split("|");  // NOT GRAPHABLE
				DIALfilter=report_result_array[8].split("|");  // NOT GRAPHABLE
				HOPlev=report_result_array[9].split("|");  // USED
				DIALmethod=report_result_array[10].split("|");  // NOT GRAPHABLE
				maxDIALlev=report_result_array[11].split("|");  // NOT GRAPHABLE, CAN BE
				DROPmax=report_result_array[12].split("|");  // NOT GRAPHABLE, CAN BE WITH drpctTODAY
				targetDIFF=report_result_array[13].split("|");  // NOT GRAPHABLE, CAN BE
				ADAintense=report_result_array[14].split("|");  // NOT GRAPHABLE, CAN BE
				ADAavailonly=report_result_array[15].split("|");  // NOT GRAPHABLE
				TAPERtime=report_result_array[16].split("|");  // NOT GRAPHABLE
				CALLtime=report_result_array[17].split("|");  // NOT GRAPHABLE
				DIALtimeout=report_result_array[18].split("|");  // NOT GRAPHABLE
				DIALstatuses=report_result_array[19].split("|");  // NOT GRAPHABLE
				VDhop=report_result_array[20].split("|");  // USED
				DAleads=report_result_array[21].split("|");  // USED
				callsTODAY=report_result_array[22].split("|");  // USED
				dropsTODAY=report_result_array[23].split("|");  // USED
				drpctTODAY=report_result_array[24].split("|");   // GAUGE , CAN BE WITH DROPmax
				diffONEMIN=report_result_array[25].split("|"); // GAUGE**
				agentsONEMIN=report_result_array[26].split("|"); // GRAPHED**
				balanceFILL=report_result_array[27].split("|");   // NOT GRAPHABLE, CAN BE WITH BALANCE SHORT
				answersTODAY=report_result_array[28].split("|");  // USED
				VSCcat1=report_result_array[29].split("|");
				VSCcat1tally=report_result_array[30].split("|");
				VSCcat2=report_result_array[31].split("|");
				VSCcat2tally=report_result_array[32].split("|");
				VSCcat3=report_result_array[33].split("|");
				VSCcat3tally=report_result_array[34].split("|");
				VSCcat4=report_result_array[35].split("|");
				VSCcat4tally=report_result_array[36].split("|");
				VSCagentcalls=report_result_array[37].split("|");  // NOT USED IN REPORT
				VSCagentwait=report_result_array[38].split("|");  // NOT USED IN REPORT
				VSCagentcust=report_result_array[39].split("|");  // NOT USED IN REPORT
				VSCagentacw=report_result_array[40].split("|");  // NOT USED IN REPORT
				VSCagentpause=report_result_array[41].split("|");  // NOT USED IN REPORT
				diffpctONEMIN_ary=report_result_array[42].split("|"); // GAUGE**
				balanceSHORT=report_result_array[43].split("|");   // NOT GRAPHABLE, CAN BE WITH BALANCE FILL
				out_total=report_result_array[44].split("|");  // USED
				out_ring=report_result_array[45].split("|");  // USED
				out_live=report_result_array[46].split("|");  // USED
				in_ivr=report_result_array[47].split("|");  // USED
				agent_incall=report_result_array[48].split("|");  // USED
				agent_ready=report_result_array[49].split("|");  // USED
				agent_paused=report_result_array[50].split("|");  // USED
				agent_total=report_result_array[51].split("|");  // USED
				avgpauseTODAY_ary=report_result_array[52].split("|");  // USED
				avgwaitTODAY_ary=report_result_array[53].split("|");  // USED
				avgcustTODAY_ary=report_result_array[54].split("|");  // USED
				avgacwTODAY_ary=report_result_array[55].split("|");  // USED

				hopperChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("Hopper Level"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 2,
							data: HOPlev
						}, {
							label: '<?php echo _QXZ("Leads in Hopper"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 2,
							data: VDhop
						}]

					};

				agentChartData = {
					labels: CAMPAIGNS,
					datasets: [{
//						label: '<?php echo _QXZ("LOGGED IN"); ?>',
//							backgroundColor: 'rgb(235, 162, 235)',
//							borderColor: 'rgb(235, 32, 235)',
//							borderWidth: 1,
//							stack: 'Stack 0',
//							data: agent_total
//						}, 
//						{
							label: '<?php echo _QXZ("INCALL"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 1,
							stack: 'Stack 1',
							data: agent_incall
						}, {
							label: '<?php echo _QXZ("WAITING"); ?>',
							backgroundColor: 'rgb(54, 235, 162)',
							borderColor: 'rgb(32, 128, 32)',
							borderWidth: 1,
							stack: 'Stack 1',
							data: agent_ready
						}, {
							label: '<?php echo _QXZ("PAUSED"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 1,
							stack: 'Stack 1',
							data: agent_paused
						}]

					};

				callChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("TOTAL"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 1,
							data: out_total
						}, {
							label: '<?php echo _QXZ("RINGING"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 1,
							data: out_ring
						}, {
							label: '<?php echo _QXZ("WAITING"); ?>',
							backgroundColor: 'rgb(54, 235, 162)',
							borderColor: 'rgb(32, 128, 32)',
							borderWidth: 1,
							data: out_live
						}, {
							label: '<?php echo _QXZ("IVR"); ?>',
							backgroundColor: 'rgb(235, 162, 235)',
							borderColor: 'rgb(235, 32, 235)',
							borderWidth: 1,
							data: in_ivr
						}]

					};

				dialableChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("DIALABLE LEADS"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 2,
							data: DAleads
						}]

					};

				callsTodayChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("CALLS TODAY"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 2,
							data: callsTODAY
						}, {
							label: '<?php echo _QXZ("ANSWERS TODAY"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 1,
							data: answersTODAY
						}, {
							label: '<?php echo _QXZ("DROPS TODAY"); ?>',
							backgroundColor: 'rgb(54, 235, 162)',
							borderColor: 'rgb(32, 128, 32)',
							borderWidth: 1,
							data: dropsTODAY
						}]

					};

				averagesChartData = {
					labels: CAMPAIGNS,
					datasets: [{
						label: '<?php echo _QXZ("PAUSE"); ?>',
							backgroundColor: 'rgb(255, 99, 132)',
							borderColor: 'rgb(255, 32, 32)',
							borderWidth: 1,
							data: avgpauseTODAY_ary
						}, {
							label: '<?php echo _QXZ("AGENT WAIT"); ?>',
							backgroundColor: 'rgb(54, 162, 235)',
							borderColor: 'rgb(32, 32, 235)',
							borderWidth: 1,
							data: avgwaitTODAY_ary
						}, {
							label: '<?php echo _QXZ("CUSTTIME"); ?>',
							backgroundColor: 'rgb(54, 235, 162)',
							borderColor: 'rgb(32, 128, 32)',
							borderWidth: 1,
							data: avgcustTODAY_ary
						}, {
							label: '<?php echo _QXZ("ACW"); ?>',
							backgroundColor: 'rgb(235, 162, 235)',
							borderColor: 'rgb(235, 32, 235)',
							borderWidth: 1,
							data: avgacwTODAY_ary
						}]

					};

				var CanvasChartType='horizontalBar';
				var StackedX=false;
				if (graph_type=='hopper') {
					var currentChartData=hopperChartData;
					var titleText='<?php echo _QXZ("Hopper Level Report"); ?>';
				} else if (graph_type=='agent') {
					var currentChartData=agentChartData;
					var titleText='<?php echo _QXZ("Agent Status Report"); ?>';
					// StackedX=true;
					// CanvasChartType='bar';
				} else if (graph_type=='call') {
					var currentChartData=callChartData;
					var titleText='<?php echo _QXZ("Live Call Count Report"); ?>';
				} else if (graph_type=='dialable') {
					var currentChartData=dialableChartData;
					var titleText='<?php echo _QXZ("Dialable Leads Report"); ?>';
				} else if (graph_type=='callsToday') {
					var currentChartData=callsTodayChartData;
					var titleText='<?php echo _QXZ("Calls Today Report"); ?>';
				} else if (graph_type=='averages') {
					var currentChartData=averagesChartData;
					var titleText='<?php echo _QXZ("Averages Report"); ?>';
				}
				

				if (currentChartData.labels.length!=MainGraph.data.labels.length){ // New axis, destroy old graphs and gauges
					MainGraph.destroy();
					
					var ctx = document.getElementById('AndroidReportCanvas').getContext('2d');
					MainGraph = new Chart(ctx, {
						type: CanvasChartType,
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
								position: 'top',
							},
							title: {
								display: true,
								text: titleText
							}
						}
					});

				} else {
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

				for (var f=0; f<ALL_CAMPAIGNS.length; f++) {
					var campaign_span_name=ALL_CAMPAIGNS[f]+"_SPD_span";
					if (CAMPAIGNS.indexOf(ALL_CAMPAIGNS[f])>=0) {
						document.getElementById(campaign_span_name).style.display = 'block';
					} else {
						document.getElementById(campaign_span_name).style.display = 'none';
					}
				}

				for (var g=0; g<CAMPAIGNS.length; g++) {
					var campaign_gauge_name=CAMPAIGNS[g];
					var droppct_gauge=campaign_gauge_name+"_SPD_drop";
					var diffpct_gauge=campaign_gauge_name+"_SPD_diff";
					var onemindiff_gauge=campaign_gauge_name+"_SPD_diff1min";

					// GAUGE 1
					var maxDROP=parseFloat(DROPmax[g]);
					var adjusted_maxValue=maxDROP*2;
					var greenZone=Math.round((maxDROP/2)*10)/10;
					var yellowZone=maxDROP;
					if (greenZone>40) {greenZone=40;}
					if (yellowZone>90) {yellowZone=90;}
					if (adjusted_maxValue>100) {adjusted_maxValue=100;}
					else if (parseFloat(drpctTODAY[g])>adjusted_maxValue) {adjusted_maxValue=parseFloat(drpctTODAY[g])};

					var rpt_color="#FF0000";
					if (drpctTODAY[g]<=greenZone) {rpt_color="#009900";}
					else if (drpctTODAY[g]>greenZone && drpctTODAY[g]<=yellowZone) {rpt_color="#DDDD00";}

					var myKnob1 = pureknob.createKnob(gauge_size, gauge_size, 'percent');
					myKnob1.setProperty('angleStart', -0.75 * Math.PI);
					myKnob1.setProperty('angleEnd', 0.75 * Math.PI);
					myKnob1.setProperty('colorFG', rpt_color);
					myKnob1.setProperty('trackWidth', 0.4);
					myKnob1.setProperty('valMin', 0);
					myKnob1.setProperty('valMax', adjusted_maxValue);
					myKnob1.setValue(drpctTODAY[g]);
					var node1 = myKnob1.node();
					var elem1 = document.getElementById(droppct_gauge);
					while (elem1.hasChildNodes()) {
						elem1.removeChild(elem1.lastChild);
					}
					elem1.appendChild(node1);
					// END GAUGE

					// GAUGE 2
					greenZone=10;
					yellowZone=25;

					var rpt_color="#FF0000";
					if (diffpctONEMIN_ary[g]<=greenZone) {rpt_color="#009900";}
					else if (diffpctONEMIN_ary[g]>greenZone && diffpctONEMIN_ary[g]<=yellowZone) {rpt_color="#DDDD00";}

					var myKnob2 = pureknob.createKnob(gauge_size, gauge_size, 'percent');
					myKnob2.setProperty('angleStart', -0.75 * Math.PI);
					myKnob2.setProperty('angleEnd', 0.75 * Math.PI);
					myKnob2.setProperty('colorFG', rpt_color);
					myKnob2.setProperty('trackWidth', 0.4);
					myKnob2.setProperty('valMin', 0);
					myKnob2.setProperty('valMax', 100);
					myKnob2.setValue(diffpctONEMIN_ary[g]);
					var node2 = myKnob2.node();
					var elem2 = document.getElementById(diffpct_gauge);
					while (elem2.hasChildNodes()) {
						elem2.removeChild(elem2.lastChild);
					}
					elem2.appendChild(node2);
					// END GAUGE

					// GAUGE 3
					var diff_target=parseFloat(targetDIFF[g]);
					diff_greenZonepos=diff_target+10;
					if (diff_greenZonepos>40) {
						diff_greenZonepos=40;
					}
					diff_greenZoneneg=diff_target-10;
					if (diff_greenZoneneg<-40) {
						diff_greenZoneneg=-40;
					}
					diff_yellowZonepos=diff_greenZonepos+10;
					if (diff_yellowZonepos>40) {
						diff_yellowZonepos=40;
					}
					diff_yellowZoneneg=diff_greenZoneneg-10;
					if (diff_yellowZoneneg<-40) {
						diff_yellowZoneneg=-40;
					}
					var rpt_color="#FF0000";
					if (diffONEMIN[g]>=diff_greenZoneneg && diffONEMIN[g]<=diff_greenZonepos) {rpt_color="#009900";}
					else if ((diffONEMIN[g]>=diff_yellowZoneneg && diffONEMIN[g]<diff_greenZoneneg) || (diffONEMIN[g]>diff_greenZonepos && diffONEMIN[g]<=diff_yellowZonepos)) {rpt_color="#DDDD00";}

					var myKnob3 = pureknob.createKnob(gauge_size, gauge_size, 'float');
					myKnob3.setProperty('angleStart', -0.75 * Math.PI);
					myKnob3.setProperty('angleEnd', 0.75 * Math.PI);
					myKnob3.setProperty('colorFG', rpt_color);
					myKnob3.setProperty('trackWidth', 0.4);
					myKnob3.setProperty('valMin', -40);
					myKnob3.setProperty('valMax', 40);
					myKnob3.setValue(diffONEMIN[g]);
					var node3 = myKnob3.node();
					var elem3 = document.getElementById(onemindiff_gauge);
					while (elem3.hasChildNodes()) {
						elem3.removeChild(elem3.lastChild);
					}
					elem3.appendChild(node3);
					// END GAUGE



				}


				// window.innerWidth+", "+window.innerHeight+"\n<BR>\n"+gauge_size+"<BR>
				var settingsText="<table width='100%' border='0' cellpadding='0' cellspacing='5'><tr valign='top'><th><a class='nodec' onClick=\"ToggleVisibility('graph_display', 1); ToggleVisibility('settings_display', 0); ToggleVisibility('percentages_display', 0);\"><div class='android_switchbutton'><?php echo _QXZ("GRAPHS"); ?></div></a></th></tr><tr valign='bottom'><th><a class='nodec' onClick=\"ToggleVisibility('graph_display', 0); ToggleVisibility('settings_display', 0); ToggleVisibility('percentages_display', 1);\"><div class='android_switchbutton'><?php echo _QXZ("PERCENTS"); ?></div></a></th></tr><tr><td>";
				
				var d = new Date();
				var NOW_TIME=("00" + (d.getMonth() + 1)).slice(-2) + "/" + ("00" + d.getDate()).slice(-2) + "/" + d.getFullYear() + " " + ("00" + d.getHours()).slice(-2) + ":" + ("00" + d.getMinutes()).slice(-2) + ":" + ("00" + d.getSeconds()).slice(-2);
				for (var i=0; i<CAMPAIGNS.length; i++) {
					settingsText+="\n\n<BR><div class='android_settings_table'>";
					settingsText+="<font class='android_auto'><B>"+CAMPAIGNS[i]+":</B>";
					settingsText+="<table cellpadding=0 cellspacing=0 width='100%'>";	
					settingsText+="<TR BGCOLOR=\"#<?php echo $SSstd_row2_background; ?>\">";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("STATUSES"); ?>:</B></font></TD><TD ALIGN=LEFT colspan='3'><font class='android_auto_small'>&nbsp; "+DIALstatuses[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("HOPPER LEVEL"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+HOPlev[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="</TR>";
					settingsText+="<TR BGCOLOR=\"#<?php echo $SSstd_row3_background; ?>\">";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("ORDER"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+DIALorder[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("FILTER"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+DIALfilter[i]+" &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("DIAL METHOD"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+DIALmethod[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="</TR>";
					settingsText+="<TR BGCOLOR=\"#<?php echo $SSstd_row2_background; ?>\">";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("DIAL LEVEL"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+DIALlev[i]+"&nbsp; &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("AVAIL ONLY"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+ADAavailonly[i]+" &nbsp;</font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("DROPPED MAX"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+DROPmax[i]+"% &nbsp; &nbsp;</font></TD>";
					settingsText+="</TR>";
					settingsText+="<TR BGCOLOR=\"#<?php echo $SSstd_row3_background; ?>\">";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("MAX LEVEL"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+maxDIALlev[i]+" &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("TAPER TIME"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+TAPERtime[i]+" &nbsp;</font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("INTENSITY"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+ADAintense[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="</TR>";
					settingsText+="<TR BGCOLOR=\"#<?php echo $SSstd_row2_background; ?>\">";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("TARGET DIFF"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+targetDIFF[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("LOCAL TIME"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+CALLtime[i]+" &nbsp;</font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("DIAL TIMEOUT"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+DIALtimeout[i]+" &nbsp;</font></TD>";
					settingsText+="</TR>";
					settingsText+="</TABLE>\n";

					settingsText+="<table cellpadding=0 cellspacing=0 width='100%'>";	
					settingsText+="<TR BGCOLOR=\"#<?php echo $SSstd_row4_background; ?>\">";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("STATUS CATEGORIES"); ?>:</B></font></TD>";
					settingsText+="<TD ALIGN=LEFT COLSPAN=7>";
					if ( (VSCcat1[i].length>0) )
						{settingsText+="<font class='android_auto_small'><B>"+VSCcat1[i]+":</B> &nbsp; "+VSCcat1tally[i]+" &nbsp;  &nbsp;  &nbsp; </font>\n";}
					if ( (VSCcat2[i].length>0) )
						{settingsText+="<font class='android_auto_small'><B>"+VSCcat2[i]+":</B> &nbsp; "+VSCcat2tally[i]+" &nbsp;  &nbsp;  &nbsp; </font>\n";}
					if ( (VSCcat3[i].length>0) )
						{settingsText+="<font class='android_auto_small'><B>"+VSCcat3[i]+":</B> &nbsp; "+VSCcat3tally[i]+" &nbsp;  &nbsp;  &nbsp; </font>\n";}
					if ( (VSCcat4[i].length>0) )
						{settingsText+="<font class='android_auto_small'><B>"+VSCcat4[i]+":</B> &nbsp; "+VSCcat4tally[i]+" &nbsp;  &nbsp;  &nbsp; </font>\n";}
					settingsText+="</TD></TR>";

					settingsText+="<TR BGCOLOR=\"#<?php echo $SSstd_row4_background; ?>\">";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("TRUNK SHORT/FILL"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+balanceSHORT[i]+" / "+balanceFILL[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("DL DIFF"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+diffONEMIN[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B> <?php echo _QXZ("TIME"); ?>:</B></font> &nbsp; </TD><TD ALIGN=LEFT colspan='3'><font class='android_auto_small'> "+NOW_TIME+" </font></TD>";
					settingsText+="</TR>";

					settingsText+="<TR BGCOLOR=\"#<?php echo $SSstd_row4_background; ?>\">";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("AVG AGENTS"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+agentsONEMIN[i]+" &nbsp; &nbsp; </font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("DROPPED PERCENT"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; ";
					if (drpctTODAY[i] >= DROPmax[i])
						{settingsText+="<font color=red><B>"+drpctTODAY[i]+"%</B></font>";}
					else
						{settingsText+=drpctTODAY[i]+"%";}
					settingsText+=" &nbsp; &nbsp;</font></TD>";
					settingsText+="<TD ALIGN=RIGHT><font class='android_auto_small'><B><?php echo _QXZ("DIFF"); ?>:</B></font></TD><TD ALIGN=LEFT><font class='android_auto_small'>&nbsp; "+diffpctONEMIN_ary[i]+"% &nbsp; &nbsp; </font></TD>";
					settingsText+="</TR></table>";
					settingsText+="</div>";				
				}
				settingsText+="</td></tr></table>";
				document.getElementById('settings_display').innerHTML=settingsText;

				}
			}
		}
}

<?php
		echo "</script>";
	}

/*
				if (MainGraph) 
					{
					if (document.getElementById('graph_type').value!=graph_type) 
						{
						graph_type=document.getElementById('graph_type').value;
						MainGraph.destroy();
						} 
					else 
						{
						MainGraph.update();
						}
					}
*/
?>
