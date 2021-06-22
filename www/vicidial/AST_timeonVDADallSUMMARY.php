<?php 
# AST_timeonVDADallSUMMARY.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# Summary for all campaigns live real-time stats for the VICIDIAL Auto-Dialer all servers
#
# STOP=4000, SLOW=40, GO=4 seconds refresh interval
# 
# changes:
# 61102-1616 - first build
# 61215-1131 - added answered calls and drop percent taken from answered calls
# 70111-1600 - added ability to use BLEND/INBND/*_C/*_B/*_I as closer campaigns
# 70619-1339 - Added Status Category tally display
# 71029-1900 - Changed CLOSER-type to not require campaign_id restriction
# 80525-1040 - Added IVR status summary display for inbound calls
# 90310-2119 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 100709-1806 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation and allowed campaigns restrictions
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 101214-1142 - Added Agent time stats
# 110110-1327 - Changed campaign real-time link to the new realtime_report.php
# 110517-0059 - Added campaign type display option
# 110703-1854 - Added doanload option
# 130610-1120 - Finalized changing of all ereg instances to preg
# 130620-2256 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2006 - Changed to mysqli PHP functions
# 140328-0005 - Converted division calculations to use MathZDC function
# 141001-2200 - Finalized adding QXZ translation to all admin files
# 141230-0038 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 190927-1300 - Fixed PHP7 array issue
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
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}

if (!isset($RR))			{$gRRroup=4;}
if (!isset($types))			{$types='SHOW ALL CAMPAIGNS';}


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
if ($types == 'AUTO-DIAL ONLY')			{$campaign_typeSQL="and dial_method IN('RATIO','ADAPT_HARD_LIMIT','ADAPT_TAPERED','ADAPT_AVERAGE')";} 
if ($types == 'MANUAL ONLY')			{$campaign_typeSQL="and dial_method IN('MANUAL','INBOUND_MAN')";} 
if ($types == 'INBOUND ONLY')			{$campaign_typeSQL="and campaign_allow_inbound='Y'";} 

$stmt="select campaign_id from vicidial_campaigns where active='Y' $LOGallowed_campaignsSQL $campaign_typeSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if (!isset($DB))   {$DB=0;}
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$groups=array();
$i=0;
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	$i++;
	}

if (!isset($RR))   {$RR=4;}

require("screen_colors.php");

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
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
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";


$HEADER.="<META HTTP-EQUIV=Refresh CONTENT=\"$RR; URL=$PHP_SELF?RR=$RR&DB=$DB&adastats=$adastats&types=$types\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;

#	require("admin_header.php");

$MAIN.="<FORM action=$PHP_SELF method=POST><TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#CampaignSUMMARY$NWE &nbsp; \n";
$MAIN.="<a href=\"$PHP_SELF?group=$group&RR=4000&DB=$DB&adastats=$adastats&types=$types\">"._QXZ("STOP")."</a> | ";
$MAIN.="<a href=\"$PHP_SELF?group=$group&RR=40&DB=$DB&adastats=$adastats&types=$types\">"._QXZ("SLOW")."</a> | ";
$MAIN.="<a href=\"$PHP_SELF?group=$group&RR=4&DB=$DB&adastats=$adastats&types=$types\">"._QXZ("GO")."</a> ";
$MAIN.=" &nbsp; &nbsp; </FONT>\n";
if ($adastats<2)
	{
	$MAIN.="<a href=\"$PHP_SELF?group=$group&RR=$RR&DB=$DB&adastats=2&types=$types\"><font size=1>+ "._QXZ("VIEW MORE SETTINGS")."</font></a>";
	}
else
	{
	$MAIN.="<a href=\"$PHP_SELF?group=$group&RR=$RR&DB=$DB&adastats=1&types=$types\"><font size=1>- "._QXZ("VIEW LESS SETTINGS")."</font></a>";
	}
$MAIN.=" &nbsp; &nbsp; &nbsp;<a href=\"$PHP_SELF?group=$group&RR=$RR&DB=$DB&adastats=$adastats&types=$types&file_download=1\">"._QXZ("DOWNLOAD")."</a> | <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>";
$MAIN.="\n";
$MAIN.="<input type=hidden name=RR value=$RR>\n";
$MAIN.="<input type=hidden name=DB value=$DB>\n";
$MAIN.="<input type=hidden name=adastats value=$adastats>\n";
$MAIN.="<select size=1 name=types>\n";
$MAIN.="<option value=\"SHOW ALL CAMPAIGNS\"";
	if ($types == 'SHOW ALL CAMPAIGNS') {$MAIN.=" selected";} 
$MAIN.=">"._QXZ("SHOW ALL CAMPAIGNS")."</option>";
$MAIN.="<option value=\"AUTO-DIAL ONLY\"";
	if ($types == 'AUTO-DIAL ONLY') {$MAIN.=" selected";} 
$MAIN.=">"._QXZ("AUTO-DIAL ONLY")."</option>";
$MAIN.="<option value=\"MANUAL ONLY\"";
	if ($types == 'MANUAL ONLY') {$MAIN.=" selected";} 
$MAIN.=">"._QXZ("MANUAL ONLY")."</option>";
$MAIN.="<option value=\"INBOUND ONLY\"";
	if ($types == 'INBOUND ONLY') {$MAIN.=" selected";} 
$MAIN.=">"._QXZ("INBOUND ONLY")."</option>";
$MAIN.="</select> \n";
$MAIN.="<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("SUBMIT")."'>\n";
$MAIN.="<BR><BR>\n\n";

$k=0;
while($k<$groups_to_print)
{
$NFB = '<b><font size=3 face="courier">';
$NFE = '</font></b>';
$F=''; $FG=''; $B=''; $BG='';

$group = $groups[$k];
$MAIN.="<b><a href=\"./realtime_report.php?group=$group&RR=$RR&DB=$DB&adastats=$adastats\">$group</a></b> &nbsp; - &nbsp; ";
$MAIN.="<a href=\"./admin.php?ADD=34&campaign_id=$group\">"._QXZ("Modify")."</a>\n";
$CSV_text.="\"$group\"\n";

$stmt = "select count(*) from vicidial_campaigns where campaign_id='$group' and campaign_allow_inbound='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$campaign_allow_inbound = $row[0];

$stmt="select auto_dial_level,dial_status_a,dial_status_b,dial_status_c,dial_status_d,dial_status_e,lead_order,lead_filter_id,hopper_level,dial_method,adaptive_maximum_level,adaptive_dropped_percentage,adaptive_dl_diff_target,adaptive_intensity,available_only_ratio_tally,adaptive_latest_server_time,local_call_time,dial_timeout,dial_statuses from vicidial_campaigns where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$DIALlev =		$row[0];
$DIALstatusA =	$row[1];
$DIALstatusB =	$row[2];
$DIALstatusC =	$row[3];
$DIALstatusD =	$row[4];
$DIALstatusE =	$row[5];
$DIALorder =	$row[6];
$DIALfilter =	$row[7];
$HOPlev =		$row[8];
$DIALmethod =	$row[9];
$maxDIALlev =	$row[10];
$DROPmax =		$row[11];
$targetDIFF =	$row[12];
$ADAintense =	$row[13];
$ADAavailonly =	$row[14];
$TAPERtime =	$row[15];
$CALLtime =		$row[16];
$DIALtimeout =	$row[17];
$DIALstatuses =	$row[18];
	$DIALstatuses = (preg_replace("/ -$|^ /","",$DIALstatuses));
	$DIALstatuses = (preg_replace('/\s/', ', ', $DIALstatuses));

$stmt="select count(*) from vicidial_hopper where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$VDhop = $row[0];

$stmt="select dialable_leads,calls_today,drops_today,drops_answers_today_pct,differential_onemin,agents_average_onemin,balance_trunk_fill,answers_today,status_category_1,status_category_count_1,status_category_2,status_category_count_2,status_category_3,status_category_count_3,status_category_4,status_category_count_4,agent_calls_today,agent_wait_today,agent_custtalk_today,agent_acw_today,agent_pause_today from vicidial_campaign_stats where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$DAleads =			$row[0];
$callsTODAY =		$row[1];
$dropsTODAY =		$row[2];
$drpctTODAY =		$row[3];
$diffONEMIN =		$row[4];
$agentsONEMIN =		$row[5];
$balanceFILL =		$row[6];
$answersTODAY =		$row[7];
$VSCcat1 =			$row[8];
$VSCcat1tally =		$row[9];
$VSCcat2 =			$row[10];
$VSCcat2tally =		$row[11];
$VSCcat3 =			$row[12];
$VSCcat3tally =		$row[13];
$VSCcat4 =			$row[14];
$VSCcat4tally =		$row[15];
$VSCagentcalls =	$row[16];
$VSCagentwait =		$row[17];
$VSCagentcust =		$row[18];
$VSCagentacw =		$row[19];
$VSCagentpause =	$row[20];

$diffpctONEMIN = ( MathZDC($diffONEMIN, $agentsONEMIN) * 100);
$diffpctONEMIN = sprintf("%01.2f", $diffpctONEMIN);

$stmt="select sum(local_trunk_shortage) from vicidial_campaign_server_stats where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$balanceSHORT = $row[0];

$MAIN.="<BR><table cellpadding=0 cellspacing=0><TR>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DIAL LEVEL").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $DIALlev&nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("TRUNK SHORT/FILL").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $balanceSHORT / $balanceFILL &nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("FILTER").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $DIALfilter &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B> "._QXZ("TIME").":</B> &nbsp; </TD><TD ALIGN=LEFT><font size=2> $NOW_TIME </TD>";
$MAIN.="";
$MAIN.="</TR>";

$CSV_text.="\""._QXZ("DIAL LEVEL").":\",\"$DIALlev\",\""._QXZ("TRUNK SHORT/FILL").":\",\"$balanceSHORT / $balanceFILL\",\""._QXZ("FILTER").":\",\"$DIALfilter\",\""._QXZ("TIME").":\",\"$NOW_TIME\"\n";

if ($adastats>1)
	{
	$MAIN.="<TR BGCOLOR=\"#CCCCCC\">";
	$MAIN.="<TD ALIGN=RIGHT><font size=2>&nbsp; <B>"._QXZ("MAX LEVEL").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $maxDIALlev &nbsp; </TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DROPPED MAX").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $DROPmax% &nbsp; &nbsp;</TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("TARGET DIFF").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $targetDIFF &nbsp; &nbsp; </TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("INTENSITY").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $ADAintense &nbsp; &nbsp; </TD>";
	$MAIN.="</TR>";
	$CSV_text.="\""._QXZ("MAX LEVEL").":\",\"$maxDIALlev\",\""._QXZ("DROPPED MAX").":\",\"$DROPmax\",\""._QXZ("TARGET DIFF").":\",\"$targetDIFF\",\""._QXZ("INTENSITY").":\",\"$ADAintense\"\n";

	$MAIN.="<TR BGCOLOR=\"#CCCCCC\">";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DIAL TIMEOUT").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $DIALtimeout &nbsp;</TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("TAPER TIME").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $TAPERtime &nbsp;</TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("LOCAL TIME").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $CALLtime &nbsp;</TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("AVAIL ONLY").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $ADAavailonly &nbsp;</TD>";
	$MAIN.="</TR>";
	$CSV_text.="\""._QXZ("DIAL TIMEOUT").":\",\"$DIALtimeout\",\""._QXZ("TAPER TIME").":\",\"$TAPERtime\",\""._QXZ("LOCAL TIME").":\",\"$CALLtime\",\""._QXZ("AVAIL ONLY").":\",\"$ADAavailonly\"\n";
	}

$MAIN.="<TR>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DIALABLE LEADS").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $DAleads &nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("CALLS TODAY").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $callsTODAY &nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("AVG AGENTS").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $agentsONEMIN &nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DIAL METHOD").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $DIALmethod &nbsp; &nbsp; </TD>";
$MAIN.="</TR>";
$CSV_text.="\""._QXZ("DIALABLE LEADS").":\",\"$DAleads\",\""._QXZ("CALLS TODAY").":\",\"$callsTODAY\",\""._QXZ("AVG AGENTS").":\",\"$agentsONEMIN\",\""._QXZ("DIAL METHOD").":\",\"$DIALmethod\"\n";

$MAIN.="<TR>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("HOPPER LEVEL").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $HOPlev &nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DROPPED / ANSWERED").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $dropsTODAY / $answersTODAY &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DL DIFF").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $diffONEMIN &nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("STATUSES").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $DIALstatuses &nbsp; &nbsp; </TD>";
$MAIN.="</TR>";
$CSV_text.="\""._QXZ("HOPPER LEVEL").":\",\"$HOPlev\",\""._QXZ("DROPPED / ANSWERED").":\",\"$dropsTODAY / $answersTODAY\",\""._QXZ("DL DIFF").":\",\"$diffONEMIN\",\""._QXZ("STATUSES").":\",\"$DIALstatuses\"\n";

$MAIN.="<TR>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("LEADS IN HOPPER").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $VDhop &nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DROPPED PERCENT").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; ";
if ($drpctTODAY >= $DROPmax)
	{$MAIN.="<font color=red><B>$drpctTODAY%</B></font>";}
else
	{$MAIN.="$drpctTODAY%";}
$MAIN.=" &nbsp; &nbsp;</TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("DIFF").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $diffpctONEMIN% &nbsp; &nbsp; </TD>";
$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("ORDER").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $DIALorder &nbsp; &nbsp; </TD>";
$MAIN.="</TR>";
$CSV_text.="\""._QXZ("LEADS IN HOPPER").":\",\"$VDhop\",\""._QXZ("DROPPED PERCENT").":\",\"$drpctTODAY%\",\""._QXZ("DIFF").":\",\"$diffpctONEMIN%\",\""._QXZ("ORDER").":\",\"$DIALorder\"\n";

$MAIN.="<TR>";
$MAIN.="<TD ALIGN=LEFT COLSPAN=8>";
if ( (!preg_match('/NULL/i',$VSCcat1)) and (strlen($VSCcat1)>0) )
	{$MAIN.="<font size=2><B>$VSCcat1:</B> &nbsp; $VSCcat1tally &nbsp;  &nbsp;  &nbsp; \n";}
if ( (!preg_match('/NULL/i',$VSCcat2)) and (strlen($VSCcat2)>0) )
	{$MAIN.="<font size=2><B>$VSCcat2:</B> &nbsp; $VSCcat2tally &nbsp;  &nbsp;  &nbsp; \n";}
if ( (!preg_match('/NULL/i',$VSCcat3)) and (strlen($VSCcat3)>0) )
	{$MAIN.="<font size=2><B>$VSCcat3:</B> &nbsp; $VSCcat3tally &nbsp;  &nbsp;  &nbsp; \n";}
if ( (!preg_match('/NULL/i',$VSCcat4)) and (strlen($VSCcat4)>0) )
	{$MAIN.="<font size=2><B>$VSCcat4:</B> &nbsp; $VSCcat4tally &nbsp;  &nbsp;  &nbsp; \n";}
$MAIN.="</TD></TR>";
$CSV_text.="\"$VSCcat1:\",\"$VSCcat1tally\",\"$VSCcat2:\",\"$VSCcat2tally\",\"$VSCcat3:\",\"$VSCcat3tally\",\"$VSCcat4:\",\"$VSCcat4tally\"\n";

if ($VSCagentcalls > 0)
	{
	$avgpauseTODAY = MathZDC($VSCagentpause, $VSCagentcalls);
	$avgpauseTODAY = round($avgpauseTODAY, 0);
	$avgpauseTODAY = sprintf("%01.0f", $avgpauseTODAY);

	$avgwaitTODAY = MathZDC($VSCagentwait, $VSCagentcalls);
	$avgwaitTODAY = round($avgwaitTODAY, 0);
	$avgwaitTODAY = sprintf("%01.0f", $avgwaitTODAY);

	$avgcustTODAY = MathZDC($VSCagentcust, $VSCagentcalls);
	$avgcustTODAY = round($avgcustTODAY, 0);
	$avgcustTODAY = sprintf("%01.0f", $avgcustTODAY);

	$avgacwTODAY = MathZDC($VSCagentacw, $VSCagentcalls);
	$avgacwTODAY = round($avgacwTODAY, 0);
	$avgacwTODAY = sprintf("%01.0f", $avgacwTODAY);

	$MAIN.="<TR>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("AGENT AVG WAIT").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $avgwaitTODAY &nbsp;</TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("AVG CUSTTIME").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $avgcustTODAY &nbsp;</TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("AVG ACW").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $avgacwTODAY &nbsp;</TD>";
	$MAIN.="<TD ALIGN=RIGHT><font size=2><B>"._QXZ("AVG PAUSE").":</B></TD><TD ALIGN=LEFT><font size=2>&nbsp; $avgpauseTODAY &nbsp;</TD>";
	$MAIN.="</TR>";
	$CSV_text.="\""._QXZ("AGENT AVG WAIT").":\",\"$avgwaitTODAY\",\""._QXZ("AVG CUSTTIME").":\",\"$avgcustTODAY\",\""._QXZ("AVG ACW").":\",\"$avgacwTODAY\",\""._QXZ("AVG PAUSE").":\",\"$avgpauseTODAY\"\n";
	}

$MAIN.="<TR>";
$MAIN.="<TD ALIGN=LEFT COLSPAN=8>";

### Header finish





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
	$out_total=0;
	$out_ring=0;
	$out_live=0;
	$in_ivr=0;
	while ($i < $parked_to_print)
		{
		$row=mysqli_fetch_row($rslt);

		if (preg_match("/LIVE/i",$row[0])) 
			{$out_live++;}
		else
			{
			if (preg_match("/IVR/i",$row[0])) 
				{$in_ivr++;}
			if (preg_match("/CLOSER/i",$row[0])) 
				{$nothing=1;}
			else 
				{$out_ring++;}
			}
		$out_total++;
		$i++;
		}

		if ($out_live > 0) {$F='<FONT class="r1">'; $FG='</FONT>';}
		if ($out_live > 4) {$F='<FONT class="r2">'; $FG='</FONT>';}
		if ($out_live > 9) {$F='<FONT class="r3">'; $FG='</FONT>';}
		if ($out_live > 14) {$F='<FONT class="r4">'; $FG='</FONT>';}

		if ($campaign_allow_inbound > 0)
			{
			$MAIN.="$NFB$out_total$NFE "._QXZ("current active calls")."&nbsp; &nbsp; &nbsp; \n";
			$CSV_text.="\"$out_total "._QXZ("current active calls")."\",\"\"\n";
			}
		else
			{
			$MAIN.="$NFB$out_total$NFE "._QXZ("calls being placed")." &nbsp; &nbsp; &nbsp; \n";
			$CSV_text.="\"$NFB$out_total$NFE "._QXZ("calls being placed")."\",\"\"\n";
			}
		
		$MAIN.="$NFB$out_ring$NFE "._QXZ("calls ringing")." &nbsp; &nbsp; &nbsp; &nbsp; \n";
		$MAIN.="$NFB$F &nbsp;$out_live $FG$NFE "._QXZ("calls waiting for agents")." &nbsp; &nbsp; &nbsp; \n";
		$MAIN.="$NFB &nbsp;$in_ivr$NFE "._QXZ("calls in IVR")." &nbsp; &nbsp; &nbsp; \n";
		$CSV_text.="\"$out_ring "._QXZ("calls ringing")."\",\"$out_live "._QXZ("calls waiting for agents")."\",\"$in_ivr "._QXZ("calls in IVR")."\"\n";
		}
	else
	{
	$MAIN.=" "._QXZ("NO LIVE CALLS WAITING")." \n";
	$CSV_text.="\""._QXZ("NO LIVE CALLS WAITING")."\"\n";
	}


###################################################################################
###### TIME ON SYSTEM
###################################################################################

$agent_incall=0;
$agent_ready=0;
$agent_paused=0;
$agent_total=0;

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
				$agent_paused++;  $agent_total++;
				}
			}

		if ( (preg_match("/INCALL/i",$status)) or (preg_match("/QUEUE/i",$status)) ) {$agent_incall++;  $agent_total++;}
		if ( (preg_match("/READY/i",$status)) or (preg_match("/CLOSER/i",$status)) ) {$agent_ready++;  $agent_total++;}
		$agentcount++;


		$i++;
		}

		if ($agent_ready > 0) {$B='<FONT class="b1">'; $BG='</FONT>';}
		if ($agent_ready > 4) {$B='<FONT class="b2">'; $BG='</FONT>';}
		if ($agent_ready > 9) {$B='<FONT class="b3">'; $BG='</FONT>';}
		if ($agent_ready > 14) {$B='<FONT class="b4">'; $BG='</FONT>';}

		$MAIN.="\n<BR>\n";

		$MAIN.="$NFB$agent_total$NFE "._QXZ("agents logged in")." &nbsp; &nbsp; &nbsp; &nbsp; \n";
		$MAIN.="$NFB$agent_incall$NFE "._QXZ("agents in calls")." &nbsp; &nbsp; &nbsp; \n";
		$MAIN.="$NFB$B &nbsp;$agent_ready $BG$NFE "._QXZ("agents waiting")." &nbsp; &nbsp; &nbsp; \n";
		$MAIN.="$NFB$agent_paused$NFE "._QXZ("paused agents")." &nbsp; &nbsp; &nbsp; \n";
		$CSV_text.="\"$agent_total "._QXZ("agents logged in")."\",\"$agent_incall "._QXZ("agents in calls")."\",\"$agent_ready "._QXZ("agents waiting")."\",\"$agent_paused "._QXZ("paused agents")."\"\n\n";
				
		$MAIN.="<PRE><FONT SIZE=2>";
		$MAIN.="";
	}
	else
	{
	$MAIN.=" "._QXZ("NO AGENTS ON CALLS")."<BR>\n";
	$CSV_text.="\""._QXZ("NO AGENTS ON CALLS")."\"\n\n";
	}

################################################################################
### END calculating calls/agents
################################################################################





$MAIN.="</TD>";
$MAIN.="</TR>";
$MAIN.="</TABLE>\n\n<BR>";

$k++;
}
$MAIN.="</FORM>\n\n<BR>";
$MAIN.="</PRE>\n";
$MAIN.="</TD></TR></TABLE>\n";

$MAIN.="</BODY></HTML>\n";

$MAIN.="$db_source\n";

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
		require("admin_header.php");
		echo $MAIN;
	}

?>

