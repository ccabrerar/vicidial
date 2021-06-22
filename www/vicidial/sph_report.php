<?php 
# sph_report.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 80502-0857 - First build
# 80506-0228 - Added user field to search by
# 90508-0644 - Changed to PHP long tags
# 130414-0235 - Added report logging
# 130610-0942 - Finalized changing of all ereg instances to preg
# 130616-2045 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0817 - Changed to mysqli PHP functions
# 140108-0721 - Added webserver and hostname to report logging
# 141114-0031 - Finalized adding QXZ translation to all admin files
# 141230-0950 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
#

$startMS = microtime();

$report_name='SPH Report';

require("dbconnect_mysqli.php");
require("functions.php");

##### Pull values from posted form variables #####
$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["role"]))				{$role=$_GET["role"];}
	elseif (isset($_POST["role"]))		{$role=$_POST["role"];}
if (isset($_GET["order"]))				{$order=$_GET["order"];}
	elseif (isset($_POST["order"]))		{$order=$_POST["order"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

if (strlen($shift)<2) {$shift='ALL';}
if (strlen($role)<2) {$role='ALL';}
if (strlen($order)<2) {$order='sales_down';}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
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
$query_date = preg_replace("/'|\"|\\\\|;/","",$query_date);
$end_date = preg_replace("/'|\"|\\\\|;/","",$end_date);
$campaign = preg_replace("/'|\"|\\\\|;/","",$campaign);
$user_group = preg_replace("/'|\"|\\\\|;/","",$user_group);
$group = preg_replace("/'|\"|\\\\|;/","",$group);
$shift = preg_replace("/'|\"|\\\\|;/","",$shift);
$role = preg_replace("/'|\"|\\\\|;/","",$role);
$order = preg_replace("/'|\"|\\\\|;/","",$order);
$user = preg_replace("/'|\"|\\\\|;/","",$user);

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$campaign, $query_date, $end_date|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($campaign)) {$campaign = array();}
if (!isset($group)) {$group = array();}
if (!isset($user_group)) {$group = array();}
$campaign_ct = count($campaign);
$group_ct = count($group);
$user_group_ct = count($user_group);

if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$stmt="select campaign_id from vicidial_campaigns;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
	$LISTcampaigns[$i]='---NONE---';
	$i++;
	$campaigns_to_print++;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTcampaigns[$i] =$row[0];
	$i++;
	}

$stmt="select group_id from vicidial_inbound_groups;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
	$LISTgroups[$i]='---NONE---';
	$i++;
	$groups_to_print++;
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTgroups[$i] =$row[0];
	$i++;
	}

$stmt="select user_group from vicidial_user_groups;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
	$LISTuser_groups[$i]='---ALL---';
	$i++;
	$user_groups_to_print++;
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTuser_groups[$i] =$row[0];
	$i++;
	}

##### START HTML #####
?>

<HTML>
<HEAD>

<style type="text/css">
<!--
	div.scroll_callback {height: 300px; width: 620px; overflow: scroll;}
	div.scroll_list {height: 400px; width: 140px; overflow: scroll;}
	div.scroll_script {height: 331px; width: 600px; background: #FFF5EC; overflow: scroll; font-size: 12px;  font-family: sans-serif;}
	div.text_input {overflow: auto; font-size: 10px;  font-family: sans-serif;}
   .body_text {font-size: 13px;  font-family: sans-serif;}
   .preview_text {font-size: 13px;  font-family: sans-serif; background: #CCFFCC}
   .preview_text_red {font-size: 13px;  font-family: sans-serif; background: #FFCCCC}
   .body_small {font-size: 11px;  font-family: sans-serif;}
   .body_tiny {font-size: 10px;  font-family: sans-serif;}
   .log_text {font-size: 11px;  font-family: monospace;}
   .log_text_red {font-size: 11px;  font-family: monospace; font-weight: bold; background: #FF3333}
   .sd_text {font-size: 16px;  font-family: sans-serif; font-weight: bold;}
   .sh_text {font-size: 14px;  font-family: sans-serif; font-weight: bold;}
   .sb_text {font-size: 12px;  font-family: sans-serif;}
   .sk_text {font-size: 11px;  font-family: sans-serif;}
   .skb_text {font-size: 13px;  font-family: sans-serif; font-weight: bold;}
   .ON_conf {font-size: 11px;  font-family: monospace; color: black; background: #FFFF99}
   .OFF_conf {font-size: 11px;  font-family: monospace; color: black; background: #FFCC77}
   .cust_form {font-family: sans-serif; font-size: 10px; overflow: auto}

   .select_bold {font-size: 14px;  font-family: sans-serif; font-weight: bold;}
   .header_white {font-size: 14px;  font-family: sans-serif; font-weight: bold; color: white}
   .data_records {font-size: 12px;  font-family: sans-serif; color: black}
   .data_records_fix {font-size: 12px;  font-family: monospace; color: black}
   .data_records_fix_small {font-size: 9px;  font-family: monospace; color: black}

-->
</style>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<TITLE><?php echo _QXZ("$report_name") ?></TITLE>

</HEAD><BODY BGCOLOR=WHITE>

<?php
$campaign_ct = count($campaign);
$group_ct = count($group);
$user_group_ct = count($user_group);
$campaign_string='|';
$group_string='|';
$user_group_string='|';

$i=0;
while($i < $campaign_ct)
	{
	$campaign_string .= "$campaign[$i]|";
	$campaign_SQL .= "'$campaign[$i]',";
	$i++;
	}
if ( (preg_match('/\s\-\-NONE\-\-\s/',$campaign_string) ) or ($campaign_ct < 1) )
	{
	$campaign_SQL = "campaign_id IN('')";
	}
else
	{
	$campaign_SQL = preg_replace('/,$/i', '',$campaign_SQL);
	$campaign_SQL = "campaign_id IN($campaign_SQL)";
	}

$i=0;
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$group_SQL .= "'$group[$i]',";
	$i++;
	}
if ( (preg_match('/\s\-\-NONE\-\-\s/',$group_string) ) or ($group_ct < 1) )
	{
	$group_SQL = "''";
#	$group_SQL = "group_id IN('')";
	}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
#	$group_SQL = "group_id IN($group_SQL)";
	}

$i=0;
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$user_group_SQL .= "'$user_group[$i]',";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{
	$user_group_SQL = "";
	}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$user_group_SQL = "user_group_id IN($user_group_SQL)";
	}

if ($role == "ALL")
	{
	$role_SQL = "";
	}
else
	{
	$role_SQL = "and role='$role'";
	}


if ($DB > 0)
	{
	echo "<BR>\n";
	echo "$campaign_ct|$campaign_string|$campaign_SQL\n";
	echo "<BR>\n";
	echo "$group_ct|$group_string|$group_SQL\n";
	echo "<BR>\n";
	echo "$user_group_ct|$user_group_string|$user_group_SQL\n";
	echo "<BR>\n";
	echo "$role|$role_SQL\n";
	echo "<BR>\n";

	}

echo "<CENTER>\n";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">";
echo "<TABLE BORDER=0 CELLSPACING=6><TR><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";

echo "<font class=\"select_bold\"><B>"._QXZ("Date Range").":</B></font><BR><CENTER>\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
echo "<BR>"._QXZ("to")."<BR>\n";
echo "<INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">\n";
/*
echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=2>\n";
echo "<font class=\"select_bold\"><B>Campaigns:</B></font><BR><CENTER>\n";
echo "<SELECT SIZE=5 NAME=campaign[] multiple>\n";
	$o=0;
	while ($campaigns_to_print > $o)
	{
		if (preg_match("/\|$LISTcampaigns[$o]\|/",$campaign_string)) 
			{echo "<option selected value=\"$LISTcampaigns[$o]\">$LISTcampaigns[$o]</option>\n";}
		else 
			{echo "<option value=\"$LISTcampaigns[$o]\">$LISTcampaigns[$o]</option>\n";}
		$o++;
	}
echo "</SELECT>\n";
*/
echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
echo "<font class=\"select_bold\"><B>"._QXZ("Inbound Groups").":</B></font><BR><CENTER>\n";
echo "<SELECT SIZE=5 NAME=group[] multiple>\n";
	$o=0;
	while ($groups_to_print > $o)
	{
		if (preg_match("/\|$LISTgroups[$o]\|/",$group_string)) 
			{echo "<option selected value=\"$LISTgroups[$o]\">$LISTgroups[$o]</option>\n";}
		else
			{echo "<option value=\"$LISTgroups[$o]\">$LISTgroups[$o]</option>\n";}
		$o++;
	}
echo "</SELECT>\n";
echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
echo "<font class=\"select_bold\"><B>"._QXZ("User Groups").":</B></font><BR><CENTER>\n";
echo "<SELECT SIZE=5 NAME=user_group[] multiple>\n";
	$o=0;
	while ($user_groups_to_print > $o)
	{
		if (preg_match("/\|$LISTuser_groups[$o]\|/",$user_group_string)) 
			{echo "<option selected value=\"$LISTuser_groups[$o]\">$LISTuser_groups[$o]</option>\n";}
		else 
			{echo "<option value=\"$LISTuser_groups[$o]\">$LISTuser_groups[$o]</option>\n";}
		$o++;
	}
echo "</SELECT>\n";
echo "</TD><TD ALIGN=LEFT VALIGN=TOP>\n";
echo "<font class=\"select_bold\"><B>"._QXZ("Shift").":</B></font><BR>\n";
echo "<SELECT SIZE=1 NAME=shift>\n";
echo "<option selected value=\"$shift\">$shift</option>\n";
echo "<option value=\"\">--</option>\n";
echo "<option value=\"AM\">"._QXZ("AM")."</option>\n";
echo "<option value=\"PM\">"._QXZ("PM")."</option>\n";
echo "<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
echo "</SELECT>&nbsp;\n";

echo "</TD><TD ALIGN=LEFT VALIGN=TOP COLSPAN=2>\n";
echo "<font class=\"select_bold\"><B>"._QXZ("Role").":</B></font><BR>\n";
echo "<SELECT SIZE=1 NAME=role>\n";
echo "<option selected value=\"$role\">$role</option>\n";
echo "<option value=\"\">--</option>\n";
echo "<option value=\"FRONTER\">"._QXZ("FRONTER")."</option>\n";
echo "<option value=\"CLOSER\">"._QXZ("CLOSER")."</option>\n";
echo "<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
echo "</SELECT>&nbsp;\n";

echo "</TD><TD ALIGN=CENTER VALIGN=TOP ROWSPAN=3>\n";
echo "<FONT class=\"select_bold\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";

echo "</TD></TR><TR></TD><TD ALIGN=LEFT VALIGN=TOP COLSPAN=2>\n";
echo "<font class=\"select_bold\"><B>"._QXZ("Order").":</B></font><BR>\n";
echo "<SELECT SIZE=1 NAME=order>\n";
echo "<option selected value=\"$order\">$order</option>\n";
echo "<option value=\"\">--</option>\n";
echo "<option value='sph_up'>"._QXZ("sph_up")."</option>\n";
echo "<option value='sph_down'>"._QXZ("sph_down")."</option>\n";
echo "<option value='hours_up'>"._QXZ("hours_up")."</option>\n";
echo "<option value='hours_down'>"._QXZ("hours_down")."</option>\n";
echo "<option value='sales_up'>"._QXZ("sales_up")."</option>\n";
echo "<option value='sales_down'>"._QXZ("sales_down")."</option>\n";
echo "<option value='calls_up'>"._QXZ("calls_up")."</option>\n";
echo "<option value='calls_down'>"._QXZ("calls_down")."</option>\n";
echo "<option value='user_up'>"._QXZ("user_up")."</option>\n";
echo "<option value='user_down'>"._QXZ("user_down")."</option>\n";
echo "<option value='name_up'>"._QXZ("name_up")."</option>\n";
echo "<option value='name_down'>"._QXZ("name_down")."</option>\n";
echo "</SELECT><BR><CENTER>\n";

echo "</TD><TD ALIGN=LEFT VALIGN=TOP>\n";
echo "<font class=\"select_bold\"><B>"._QXZ("User").":</B></font><BR>\n";
echo "<INPUT TYPE=text NAME=user SIZE=7 MAXLENGTH=20 VALUE=\"$user\">\n";

echo "</TD></TR><TR></TD><TD ALIGN=LEFT VALIGN=TOP COLSPAN=3>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD></TR></TABLE>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=3>\n";


if ($group_ct < 1)
{
echo "\n";
echo _QXZ("PLEASE SELECT AN IN-GROUP AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
echo " "._QXZ("NOTE: stats taken from shift specified")."\n";
}

else
{
/*
if ($shift == 'AM') 
	{
	$time_BEGIN=$AM_shift_BEGIN;
	$time_END=$AM_shift_END;
	if (strlen($time_BEGIN) < 6) {$time_BEGIN = "03:45:00";}   
	if (strlen($time_END) < 6) {$time_END = "15:14:59";}
	}
if ($shift == 'PM') 
	{
	$time_BEGIN=$PM_shift_BEGIN;
	$time_END=$PM_shift_END;
	if (strlen($time_BEGIN) < 6) {$time_BEGIN = "15:15:00";}
	if (strlen($time_END) < 6) {$time_END = "23:15:00";}
	}
if ($shift == 'ALL') 
	{
	if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
	if (strlen($time_END) < 6) {$time_END = "23:59:59";}
	}
$query_date_BEGIN = "$query_date $time_BEGIN";   
$query_date_END = "$end_date $time_END";

if (strlen($user_group)>0) {$ugSQL="and vicidial_agent_log.user_group='$user_group'";}
else {$ugSQL='';}
*/

echo _QXZ("VICIDIAL: Agent SPH Report",50)." $NOW_TIME\n";

echo _QXZ("Time range").": $query_date "._QXZ("to")." $end_date\n\n";
echo "---------- "._QXZ("AGENTS SPH DETAILS")." -------------\n</PRE>\n";

echo "<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=3><TR BGCOLOR=BLACK>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">#</TD>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">&nbsp; "._QXZ("USER")." &nbsp;</TD>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">&nbsp; "._QXZ("NAME")." &nbsp;</TD>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">&nbsp; "._QXZ("ROLE")." &nbsp;</TD>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">&nbsp; "._QXZ("GROUP")." &nbsp;</TD>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">&nbsp; "._QXZ("CALLS")." &nbsp;</TD>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">&nbsp; "._QXZ("HOURS")." &nbsp;</TD>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">&nbsp; "._QXZ("SALES")." &nbsp;</TD>\n";
echo "<TD ALIGN=CENTER><FONT class=\"header_white\">&nbsp; "._QXZ("SPH")." &nbsp;</TD>\n";
echo "</TR>\n";

$order_SQL='';
if ($order == 'sph_up')		{$order_SQL = "order by full_name,campaign_group_id,role";}
if ($order == 'sph_down')	{$order_SQL = "order by full_name,campaign_group_id,role";}
if ($order == 'hours_up')	{$order_SQL = "order by login";}
if ($order == 'hours_down') {$order_SQL = "order by login desc";}
if ($order == 'sales_up')	{$order_SQL = "order by sales, sph desc";}
if ($order == 'sales_down') {$order_SQL = "order by sales desc, sph";}
if ($order == 'calls_up')	{$order_SQL = "order by calls";}
if ($order == 'calls_down') {$order_SQL = "order by calls desc";}
if ($order == 'user_up')	{$order_SQL = "order by vicidial_users.user";}
if ($order == 'user_down')	{$order_SQL = "order by vicidial_users.user desc";}
if ($order == 'name_up')	{$order_SQL = "order by full_name";}
if ($order == 'name_down')	{$order_SQL = "order by full_name desc";}

if (strlen($user) > 0)		{$user_SQL = "and vicidial_agent_sph.user='$user'";}
else {$user_SQL='';}

$stmt="select vicidial_users.user,full_name,role,campaign_group_id,sum(login_sec) as login,sum(calls) as calls,sum(sales) as sales,avg(sph) as sph from vicidial_users,vicidial_agent_sph where stat_date >= '$query_date' and stat_date <= '$end_date' and shift='$shift' and vicidial_users.user=vicidial_agent_sph.user and campaign_group_id IN($group_SQL) $role_SQL $user_SQL group by vicidial_users.user,campaign_group_id,role $order_SQL limit 100000;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$rows_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $rows_to_print)
	{
	$dbHOURS=0; $dbSPH=0; 
	$row=mysqli_fetch_row($rslt);
	$user_id[$i] =		$row[0];
	$full_name[$i] =	$row[1];
	$roleX[$i] =		$row[2];
	$group[$i] =		$row[3];
	$login_sec[$i] =	$row[4];	$TOTlogin_sec = ($TOTlogin_sec + $row[4]);
	$calls[$i] =		$row[5];	$TOTcalls = ($TOTcalls + $row[5]);
	$sales[$i] =		$row[6];	$TOTsales = ($TOTsales + $row[6]);

	if ($login_sec[$i] > 0)
		{
		$dbHOURS = ($login_sec[$i] / 3600);
		if ($sales[$i] > 0)
			{
			$dbSPH = ( $sales[$i] / $dbHOURS );
				$dbSPH = round($dbSPH, 2);
				$dbSPH = sprintf("%01.2f", $dbSPH);
			}
		else
			{$dbSPH='0.00';}
		$dbHOURS = round($dbHOURS, 2);
		$dbHOURS = sprintf("%01.2f", $dbHOURS);
		}
	else
		{$dbHOURS='0.00';}

	$sph[$i] =		$dbSPH;		
	$hours[$i] =	$dbHOURS;		

	$sphSORT[$i] =		"$dbSPH-----$i";		
	$hoursSORT[$i] =	"$dbHOURS-----$i";		

	$i++;
	}

### Sort by sph if selected
if ($order == 'sph_up')
	{
	sort($sphSORT, SORT_NUMERIC);
	}
if ($order == 'sph_down')
	{
	rsort($sphSORT, SORT_NUMERIC);
	}

$j=0;
while ($j < $rows_to_print)
	{

	$sph_split = explode("-----",$sphSORT[$j]);
	$i = $sph_split[1];

	if (preg_match("/1$|3$|5$|7$|9$/i", $j))
		{$bgcolor='bgcolor="#B9CBFD"';} 
	else
		{$bgcolor='bgcolor="#9BB9FB"';}

	echo "<TR $bgcolor>\n";
	echo "<TD ALIGN=LEFT><FONT class=\"data_records_fix_small\">$j</TD>\n";
	echo "<TD><FONT class=\"data_records\">$user_id[$i] </TD>\n";
	echo "<TD><FONT class=\"data_records\">$full_name[$i] </TD>\n";
	echo "<TD><FONT class=\"data_records\">$roleX[$i] </TD>\n";
	echo "<TD><FONT class=\"data_records\">$group[$i] </TD>\n";
	echo "<TD ALIGN=RIGHT><FONT class=\"data_records_fix\"> $calls[$i]</TD>\n";
	echo "<TD ALIGN=RIGHT><FONT class=\"data_records_fix\"> $hours[$i]</TD>\n";
	echo "<TD ALIGN=RIGHT><FONT class=\"data_records_fix\"> $sales[$i]</TD>\n";
	echo "<TD ALIGN=RIGHT><FONT class=\"data_records_fix\"> $sph[$i]</TD>\n";
	echo "</TR>\n";


	$j++;
	}


if ($TOTlogin_sec > 0)
	{
	$TOTdbHOURS = ($TOTlogin_sec / 3600);
	if ($TOTsales > 0)
		{
		$TOTdbSPH = ( $TOTsales / $TOTdbHOURS );
			$TOTdbSPH = round($TOTdbSPH, 2);
			$TOTdbSPH = sprintf("%01.2f", $TOTdbSPH);
		}
	else
		{$TOTdbSPH='0.00';}
	$TOTdbHOURS = round($TOTdbHOURS, 0);
	$TOTdbHOURS = sprintf("%01.0f", $TOTdbHOURS);
	}
else
	{$TOTdbHOURS='0.00';}

$TOTsph =	$TOTdbSPH;		
$TOThours =	$TOTdbHOURS;		


echo "<TR BGCOLOR=#E6E6E6>\n";
echo "<TD ALIGN=LEFT COLSPAN=5><FONT class=\"data_records\">"._QXZ("TOTALS")."</TD>\n";
echo "<TD ALIGN=RIGHT><FONT class=\"data_records_fix\"> $TOTcalls</TD>\n";
echo "<TD ALIGN=RIGHT><FONT class=\"data_records_fix\"> $TOThours</TD>\n";
echo "<TD ALIGN=RIGHT><FONT class=\"data_records_fix\"> $TOTsales</TD>\n";
echo "<TD ALIGN=RIGHT><FONT class=\"data_records_fix\"> $TOTsph</TD>\n";
echo "</TR>\n";

echo "</TABLE>\n";

/*
	$TOTavgWAIT_M = ( ($TOTtotWAIT / $TOTcalls) / 60);
	$TOTavgWAIT_M = round($TOTavgWAIT_M, 2);
	$TOTavgWAIT_M_int = intval("$TOTavgWAIT_M");
	$TOTavgWAIT_S = ($TOTavgWAIT_M - $TOTavgWAIT_M_int);
	$TOTavgWAIT_S = ($TOTavgWAIT_S * 60);
	$TOTavgWAIT_S = round($TOTavgWAIT_S, 0);
	if ($TOTavgWAIT_S < 10) {$TOTavgWAIT_S = "0$TOTavgWAIT_S";}
	$TOTavgWAIT_MS = "$TOTavgWAIT_M_int:$TOTavgWAIT_S";
	$TOTavgWAIT_MS =		sprintf("%6s", $TOTavgWAIT_MS);
		while(strlen($TOTavgWAIT_MS)>6) {$TOTavgWAIT_MS = substr("$TOTavgWAIT_MS", 0, -1);}
*/


echo "\n";


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


}


?>
</CENTER>
</BODY></HTML>
