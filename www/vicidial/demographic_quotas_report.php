<?php 
# demographic_quotas_report.php
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 230515-0922 - First build
#

$startMS = microtime();

$report_name='Demographic Quotas Report';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method,allow_shared_dial,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$user_territories_active =		$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$SSallow_shared_dial =			$row[6];
	$SSallow_web_debug =			$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9a-zA-Z]/', '', $group);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9\p{L}]/u', '', $group);
	}

$stmt="SELECT selected_language,user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$LOGuser_group =			$row[1];
	}

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

$stmt="SELECT modify_campaigns,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGmodify_campaigns =	$row[0];
$LOGuser_group =		$row[1];

if ($LOGmodify_campaigns < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for campaign debugging").": |$PHP_AUTH_USER|\n";
	exit;
	}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

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

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo "You are not allowed to view this report: |$PHP_AUTH_USER|$report_name|\n";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url', webserver='$webserver_id';";
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

$stmt="select campaign_id,campaign_name from vicidial_campaigns order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$campaign_id[$i] =$row[0];
	$campaign_name[$i] =$row[1];
	$i++;
	}

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";
?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}
-->
 </STYLE>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;

	require("admin_header.php");

echo "<b>"._QXZ("$report_name")."</b> $NWB#DQreport$NWE\n";

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<SELECT SIZE=1 NAME=group>\n";
$o=0;
while ($campaigns_to_print > $o)
	{
	if ($campaign_id[$o] == $group) {echo "<option selected value=\"$campaign_id[$o]\">$campaign_id[$o] - $campaign_name[$o]</option>\n";}
	else {echo "<option value=\"$campaign_id[$o]\">$campaign_id[$o] - $campaign_name[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=31&campaign_id=$group\">"._QXZ("MODIFY")."</a> | <a href=\"./campaign_debug.php?group=$group\">"._QXZ("DQ Debug")."</a>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n\n";


if (!$group)
	{
	echo "\n\n";
	echo _QXZ("PLEASE SELECT A CAMPAIGN ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$stmt="select count(*) from vicidial_hopper where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$TOTALcalls =	sprintf("%10s", $row[0]);


	echo _QXZ("Report run time").":                       $NOW_TIME\n</PRE>";


	$campaign_activeSTATUS = "<font color=red><b>"._QXZ("INACTIVE")."</b></font>";
	$stmt="select demographic_quotas,demographic_quotas_container,demographic_quotas_rerank,demographic_quotas_list_resets,campaign_logindate,campaign_calldate,campaign_name,active,dial_statuses,hopper_level,demographic_quotas_last_rerank from vicidial_campaigns where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$camp_ct = mysqli_num_rows($rslt);
	if ($DB) {echo "$camp_ct|$stmt|\n";}
	if ($camp_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$demographic_quotas =				$row[0];
		$demographic_quotas_container =		$row[1];
		$demographic_quotas_rerank =		$row[2];
		$demographic_quotas_list_resets =	$row[3];
		$campaign_logindate =				$row[4];
		$campaign_calldate =				$row[5];
		$campaign_name = 					$row[6];
		$campaign_active = 					$row[7];
		$dial_statuses = 					$row[8];
		$hopper_level = 					$row[9];
		$demographic_quotas_last_rerank =	$row[10];
		if ($campaign_active == 'Y')
			{$campaign_activeSTATUS = "<font color=green><b>"._QXZ("ACTIVE")."</b></font>";}
		}

	$dialable_leads=0;
	$calls_today=0;
	$calls_hour=0;
	$calls_fivemin=0;
	$stmt="select dialable_leads,calls_today,calls_hour,calls_fivemin from vicidial_campaign_stats where campaign_id='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$camp_ct = mysqli_num_rows($rslt);
	if ($DB) {echo "$camp_ct|$stmt|\n";}
	if ($camp_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$dialable_leads =	$row[0];
		$calls_today =		$row[1];
		$calls_hour =		$row[2];
		$calls_fivemin =	$row[3];
		}

	if ($demographic_quotas == 'INVALID')
		{$DQdebug = " &nbsp; <font color=red><b>"._QXZ("DQ configuration invalid")."<b></font>";}
	if ($demographic_quotas == 'COMPLETE')
		{$DQdebug = " &nbsp; <b>"._QXZ("DQ goals have been met")."<b>$DQdebug";}

	echo "<table width=800 border=0 cellspacing=2 cellpadding=0> <tr bgcolor='#E6E6E6'><td>"._QXZ("Campaign").": </td><td>$group - $campaign_name &nbsp; $campaign_activeSTATUS</td></tr>\n";
	echo "<tr bgcolor='#E6E6E6'><td>"._QXZ("Total leads in hopper right now").": </td><td>$TOTALcalls &nbsp; <i>"._QXZ("level").": $hopper_level</i> &nbsp; "._QXZ("dialable").": $dialable_leads</td></tr>\n";
	echo "<tr bgcolor='#E6E6E6'><td>"._QXZ("Dial statuses").": </td><td>$dial_statuses</td></tr>\n";
	echo "<tr bgcolor='#E6E6E6'><td>"._QXZ("Campaign last call date").": </td><td>$campaign_calldate &nbsp; <i>"._QXZ("calls today").": $calls_today &nbsp; ("._QXZ("calls, last hour / five-min").": $calls_hour / $calls_fivemin)</i></td></tr>\n";
	echo "<tr bgcolor='#E6E6E6'><td>"._QXZ("Campaign last agent login date").": </td><td>$campaign_logindate</td></tr>\n";
	echo "<tr bgcolor='#E6E6E6'><td>"._QXZ("Demographic Quotas").": </td><td>$demographic_quotas &nbsp; $DQdebug</td></tr>\n";
	echo "<tr bgcolor='#E6E6E6'><td><a href=\"admin.php?ADD=392111111111&container_id=$demographic_quotas_container\">"._QXZ("Demographic Quotas Container")."</a>: </td><td>$demographic_quotas_container</td></tr>\n";
	echo "<tr bgcolor='#E6E6E6'><td>"._QXZ("Demographic Quotas Re-Rank").": </td><td>$demographic_quotas_rerank &nbsp; <i>"._QXZ("last re-rank").": $demographic_quotas_last_rerank</i></td></tr>\n";
	echo "<tr bgcolor='#E6E6E6'><td>"._QXZ("Demographic Quotas List Resets").": </td><td>$demographic_quotas_list_resets</td></tr>\n";

	echo "</table>\n";

	$DQgoals_content='';
	$active_goals_count=0;
	$filled_goals_count=0;
	$stmt="SELECT quota_field,quota_field_order,quota_value,quota_value_order,quota_goal,quota_count,quota_leads_total,quota_leads_active,quota_status,quota_modify_date from vicidial_demographic_quotas_goals where campaign_id='" . mysqli_real_escape_string($link, $group) . "' and demographic_quotas_container='$demographic_quotas_container' and quota_status!='ARCHIVE' order by quota_field_order,quota_value_order limit 200;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$debugs_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($debugs_to_print > $i)
		{
		$row=mysqli_fetch_row($rslt);
		$quota_field =			$row[0];
		$quota_field_order =	$row[1];
		$quota_value =			$row[2];
		$quota_value_order =	$row[3];
		$quota_goal =			$row[4];
		$quota_count =			$row[5];
		$quota_leads_total =	$row[6];
		$quota_leads_active =	$row[7];
		$quota_status =			$row[8];
		$quota_modify_date =	$row[9];

		$i++;

		$row_color='#CCCCCC';
		if (preg_match("/ACTIVE/",$quota_status)) {$row_color='#33FF33';   $active_goals_count++;}
		if (preg_match("/FILLED/",$quota_status)) {$row_color='#CC99CC';   $filled_goals_count++;}
		$DQgoals_content .= "<tr bgcolor='$row_color'>";
		$DQgoals_content .= "<td align=right>$i</td>\n";
		$DQgoals_content .= "<td align=right>$quota_field</td>\n";
		$DQgoals_content .= "<td align=right>$quota_field_order</td>\n";
		$DQgoals_content .= "<td align=right>$quota_value</td>\n";
		$DQgoals_content .= "<td align=right>$quota_value_order</td>\n";
		$DQgoals_content .= "<td align=right>$quota_goal</td>\n";
		$DQgoals_content .= "<td align=right>$quota_count</td>\n";
		$DQgoals_content .= "<td align=right>$quota_leads_total</td>\n";
		$DQgoals_content .= "<td align=right>$quota_leads_active</td>\n";
		$DQgoals_content .= "<td align=right>$quota_status</td>\n";
		$DQgoals_content .= "<td align=right>$quota_modify_date</td>\n";
		$DQgoals_content .= "</tr>\n";

		}

	if (strlen($DQgoals_content) > 10)
		{
		$DQgoals_header  = "<br><b>"._QXZ("Demographic Quota Goals")." - &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Active").": $active_goals_count &nbsp; "._QXZ("Filled").": $filled_goals_count</b>.";
		$DQgoals_header .= "<table width=900 border=0 cellspacing=2 cellpadding=0>";
		$DQgoals_header .= "<tr bgcolor='#999999'>";
		$DQgoals_header .= "<td align=right>#</td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Quota Field")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Field Order")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Value")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Value Order")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Goal")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Count")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Leads Total")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Leads Active")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Quota Status")." </td>\n";
		$DQgoals_header .= "<td align=right> "._QXZ("Last Update")." </td>\n";
		$DQgoals_header .= "</tr>\n";
		echo "$DQgoals_header$DQgoals_content</table>\n";
		}
	else
		{
		echo "<br><br><b>"._QXZ("No Goals found for this campaign")."</b>.";
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

echo "<br><br><font size=1>"._QXZ("report run time").": $TOTALrun</font>.";

?>

</BODY></HTML>
