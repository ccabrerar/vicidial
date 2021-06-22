<?php 
# campaign_summary_mobile_report.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# Back-end PHP script that generates text output to be interpreted by AST_timeonVDADallSUMMARY_mobile.php
#
# STOP=4000, SLOW=40, GO=4 seconds refresh interval
# 
# changes:
#
# 181212-2329 - Initial build
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
	if (in_array('LIST ALL CAMPAIGNS', $types))			{$campaign_typeSQL="";} 
	else if (in_array('AUTO-DIAL ONLY', $types))			{$campaign_typeSQL="and dial_method IN('RATIO','ADAPT_HARD_LIMIT','ADAPT_TAPERED','ADAPT_AVERAGE')";} 
	else if (in_array('MANUAL ONLY', $types))			{$campaign_typeSQL="and dial_method IN('MANUAL','INBOUND_MAN')";} 
	else if (in_array('INBOUND ONLY', $types))			{$campaign_typeSQL="and campaign_allow_inbound='Y'";} 
	else {$campaign_typeSQL="and campaign_id='".implode("', '", $types)."'";}
} else {
	if (!in_array('LIST ALL CAMPAIGNS', $types)) {
		$campaign_typeSQL='and (';
		if (in_array('AUTO-DIAL ONLY', $types)) 
			{
			$campaign_typeSQL.="dial_method IN('RATIO','ADAPT_HARD_LIMIT','ADAPT_TAPERED','ADAPT_AVERAGE') or "; #  
			$index = array_search('AUTO-DIAL ONLY', $types);
			unset($types[$index]);
			}
		if (in_array('MANUAL ONLY', $types)) 
			{
			$campaign_typeSQL.="dial_method IN('MANUAL','INBOUND_MAN') or ";
			$index = array_search('MANUAL ONLY', $types);
			unset($types[$index]);
			} #  
		if (in_array('INBOUND ONLY', $types)) 
			{
			$campaign_typeSQL.="campaign_allow_inbound='Y' or ";
			$index = array_search('INBOUND ONLY', $types);
			unset($types[$index]);
			} #  
		array_values($types);
		$campaign_typeSQL.="campaign_id in ('".implode("', '", $types)."')";
		$campaign_typeSQL.=')';
	}
}


$stmt="select campaign_id from vicidial_campaigns where active='Y' $LOGallowed_campaignsSQL $campaign_typeSQL order by campaign_id;";
# echo $stmt; die;
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

$output_text="";
$k=0;
while($k<$groups_to_print)
	{
	$group = $groups[$k];
	$output_text.="$group|";
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

	$k++;
	}
$output_text.="\n";

# Output all arrays
$n=0;
$DIALlev_output='';
$DIALstatusA_output='';
$DIALstatusB_output='';
$DIALstatusC_output='';
$DIALstatusD_output='';
$DIALstatusE_output='';
$DIALorder_output='';
$DIALfilter_output='';
$HOPlev_output='';
$DIALmethod_output='';
$maxDIALlev_output='';
$DROPmax_output='';
$targetDIFF_output='';
$ADAintense_output='';
$ADAavailonly_output='';
$TAPERtime_output='';
$CALLtime_output='';
$DIALtimeout_output='';
$DIALstatuses_output='';
$VDhop_output='';
$DAleads_output='';
$callsTODAY_output='';
$dropsTODAY_output='';
$drpctTODAY_output='';
$diffONEMIN_output='';
$agentsONEMIN_output='';
$balanceFILL_output='';
$answersTODAY_output='';
$VSCcat1_output='';
$VSCcat1tally_output='';
$VSCcat2_output='';
$VSCcat2tally_output='';
$VSCcat3_output='';
$VSCcat3tally_output='';
$VSCcat4_output='';
$VSCcat4tally_output='';
$VSCagentcalls_output='';
$VSCagentwait_output='';
$VSCagentcust_output='';
$VSCagentacw_output='';
$VSCagentpause_output='';
$diffpctONEMIN_ary_output='';
$balanceSHORT_output='';
$out_total_output='';
$out_ring_output='';
$out_live_output='';
$in_ivr_output='';
$agent_incall_output='';
$agent_ready_output='';
$agent_paused_output='';
$agent_total_output='';
$avgpauseTODAY_ary_output='';
$avgwaitTODAY_ary_output='';
$avgcustTODAY_ary_output='';
$avgacwTODAY_ary_output='';


while ($n<$groups_to_print) {
	# $DIALlev["$group"] =		$row[0];
	$DIALlev_output .=	$DIALlev[$groups[$n]]."|";
	$DIALstatusA_output .= $DIALstatusA[$groups[$n]]."|";
	$DIALstatusB_output .= $DIALstatusB[$groups[$n]]."|";
	$DIALstatusC_output .= $DIALstatusC[$groups[$n]]."|";
	$DIALstatusD_output .= $DIALstatusD[$groups[$n]]."|";
	$DIALstatusE_output .= $DIALstatusE[$groups[$n]]."|";
	$DIALorder_output .= $DIALorder[$groups[$n]]."|";
	$DIALfilter_output .= $DIALfilter[$groups[$n]]."|";
	$HOPlev_output .= $HOPlev[$groups[$n]]."|";
	$DIALmethod_output .= $DIALmethod[$groups[$n]]."|";
	$maxDIALlev_output .= $maxDIALlev[$groups[$n]]."|";
	$DROPmax_output .= $DROPmax[$groups[$n]]."|";
	$targetDIFF_output .= $targetDIFF[$groups[$n]]."|";
	$ADAintense_output .= $ADAintense[$groups[$n]]."|";
	$ADAavailonly_output .= $ADAavailonly[$groups[$n]]."|";
	$TAPERtime_output .= $TAPERtime[$groups[$n]]."|";
	$CALLtime_output .= $CALLtime[$groups[$n]]."|";
	$DIALtimeout_output .= $DIALtimeout[$groups[$n]]."|";
	$DIALstatuses_output .= $DIALstatuses[$groups[$n]]."|";
	$VDhop_output .= $VDhop[$groups[$n]]."|";
	$DAleads_output .= $DAleads[$groups[$n]]."|";
	$callsTODAY_output .= $callsTODAY[$groups[$n]]."|";
	$dropsTODAY_output .= $dropsTODAY[$groups[$n]]."|";
	$drpctTODAY_output .= $drpctTODAY[$groups[$n]]."|";
	$diffONEMIN_output .= $diffONEMIN[$groups[$n]]."|";
	$agentsONEMIN_output .= $agentsONEMIN[$groups[$n]]."|";
	$balanceFILL_output .= $balanceFILL[$groups[$n]]."|";
	$answersTODAY_output .= $answersTODAY[$groups[$n]]."|";
	$VSCcat1_output .= $VSCcat1[$groups[$n]]."|";
	$VSCcat1tally_output .= $VSCcat1tally[$groups[$n]]."|";
	$VSCcat2_output .= $VSCcat2[$groups[$n]]."|";
	$VSCcat2tally_output .= $VSCcat2tally[$groups[$n]]."|";
	$VSCcat3_output .= $VSCcat3[$groups[$n]]."|";
	$VSCcat3tally_output .= $VSCcat3tally[$groups[$n]]."|";
	$VSCcat4_output .= $VSCcat4[$groups[$n]]."|";
	$VSCcat4tally_output .= $VSCcat4tally[$groups[$n]]."|";
	$VSCagentcalls_output .= $VSCagentcalls[$groups[$n]]."|";
	$VSCagentwait_output .= $VSCagentwait[$groups[$n]]."|";
	$VSCagentcust_output .= $VSCagentcust[$groups[$n]]."|";
	$VSCagentacw_output .= $VSCagentacw[$groups[$n]]."|";
	$VSCagentpause_output .= $VSCagentpause[$groups[$n]]."|";
	$diffpctONEMIN_ary_output .= $diffpctONEMIN_ary[$groups[$n]]."|";
	$balanceSHORT_output .= $balanceSHORT[$groups[$n]]."|";
	$out_total_output .= $out_total[$groups[$n]]."|";
	$out_ring_output .= $out_ring[$groups[$n]]."|";
	$out_live_output .= $out_live[$groups[$n]]."|";
	$in_ivr_output .= $in_ivr[$groups[$n]]."|";
	$agent_incall_output .= $agent_incall[$groups[$n]]."|";
	$agent_ready_output .= $agent_ready[$groups[$n]]."|";
	$agent_paused_output .= $agent_paused[$groups[$n]]."|";
	$agent_total_output .= $agent_total[$groups[$n]]."|";
	$avgpauseTODAY_ary_output .= $avgpauseTODAY_ary[$groups[$n]]."|";
	$avgwaitTODAY_ary_output .= $avgwaitTODAY_ary[$groups[$n]]."|";
	$avgcustTODAY_ary_output .= $avgcustTODAY_ary[$groups[$n]]."|";
	$avgacwTODAY_ary_output .= $avgacwTODAY_ary[$groups[$n]]."|";
	$n++;
}

$DIALlev_output.="\n";
$DIALstatusA_output.="\n";
$DIALstatusB_output.="\n";
$DIALstatusC_output.="\n";
$DIALstatusD_output.="\n";
$DIALstatusE_output.="\n";
$DIALorder_output.="\n";
$DIALfilter_output.="\n";
$HOPlev_output.="\n";
$DIALmethod_output.="\n";
$maxDIALlev_output.="\n";
$DROPmax_output.="\n";
$targetDIFF_output.="\n";
$ADAintense_output.="\n";
$ADAavailonly_output.="\n";
$TAPERtime_output.="\n";
$CALLtime_output.="\n";
$DIALtimeout_output.="\n";
$DIALstatuses_output.="\n";
$VDhop_output.="\n";
$DAleads_output.="\n";
$callsTODAY_output.="\n";
$dropsTODAY_output.="\n";
$drpctTODAY_output.="\n";
$diffONEMIN_output.="\n";
$agentsONEMIN_output.="\n";
$balanceFILL_output.="\n";
$answersTODAY_output.="\n";
$VSCcat1_output.="\n";
$VSCcat1tally_output.="\n";
$VSCcat2_output.="\n";
$VSCcat2tally_output.="\n";
$VSCcat3_output.="\n";
$VSCcat3tally_output.="\n";
$VSCcat4_output.="\n";
$VSCcat4tally_output.="\n";
$VSCagentcalls_output.="\n";
$VSCagentwait_output.="\n";
$VSCagentcust_output.="\n";
$VSCagentacw_output.="\n";
$VSCagentpause_output.="\n";
$diffpctONEMIN_ary_output.="\n";
$balanceSHORT_output.="\n";
$out_total_output.="\n";
$out_ring_output.="\n";
$out_live_output.="\n";
$in_ivr_output.="\n";
$agent_incall_output.="\n";
$agent_ready_output.="\n";
$agent_paused_output.="\n";
$agent_total_output.="\n";
$avgpauseTODAY_ary_output.="\n";
$avgwaitTODAY_ary_output.="\n";
$avgcustTODAY_ary_output.="\n";
$avgacwTODAY_ary_output.="\n";

$output_text.=$DIALlev_output.$DIALstatusA_output.$DIALstatusB_output.$DIALstatusC_output.$DIALstatusD_output.$DIALstatusE_output.$DIALorder_output.$DIALfilter_output.$HOPlev_output.$DIALmethod_output.$maxDIALlev_output.$DROPmax_output.$targetDIFF_output.$ADAintense_output.$ADAavailonly_output.$TAPERtime_output.$CALLtime_output.$DIALtimeout_output.$DIALstatuses_output.$VDhop_output.$DAleads_output.$callsTODAY_output.$dropsTODAY_output.$drpctTODAY_output.$diffONEMIN_output.$agentsONEMIN_output.$balanceFILL_output.$answersTODAY_output.$VSCcat1_output.$VSCcat1tally_output.$VSCcat2_output.$VSCcat2tally_output.$VSCcat3_output.$VSCcat3tally_output.$VSCcat4_output.$VSCcat4tally_output.$VSCagentcalls_output.$VSCagentwait_output.$VSCagentcust_output.$VSCagentacw_output.$VSCagentpause_output.$diffpctONEMIN_ary_output.$balanceSHORT_output.$out_total_output.$out_ring_output.$out_live_output.$in_ivr_output.$agent_incall_output.$agent_ready_output.$agent_paused_output.$agent_total_output.$avgpauseTODAY_ary_output.$avgwaitTODAY_ary_output.$avgcustTODAY_ary_output.$avgacwTODAY_ary_output;
$output_text=preg_replace('/\|\n/', "\n", $output_text);


print $output_text;