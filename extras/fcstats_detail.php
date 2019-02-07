<?php 
# fcstats.php
# 
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 70813-1526 - First Build
# 71008-1436 - Added shift to be defined in dbconnect_mysqli.php
# 71217-1128 - Changed method for calculating stats
# 71228-1140 - added percentages, cross-day start/stop
# 80328-1139 - adapted for basic fronter/closer stats
# 90310-2132 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110703-1828 - Added download option
# 111104-1213 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 120705-2007 - Changed SALES to use sales status flag
# 130414-0126 - Added report logging
# 130610-0948 - Finalized changing of all ereg instances to preg
# 130619-2339 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-1930 - Changed to mysqli PHP functions
# 140108-0725 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

if(!function_exists('MathZDC')) {
	function MathZDC($dividend, $divisor, $quotient=0) {
		if ($divisor==0) {
			return $quotient;
		} else if ($dividend==0) {
			return 0;
		} else {
			return ($dividend/$divisor);
		}
	}
}

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_hour"]))				{$query_hour=$_GET["query_hour"];}
	elseif (isset($_POST["query_hour"]))	{$query_hour=$_POST["query_hour"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["end_hour"]))			{$end_hour=$_GET["end_hour"];}
	elseif (isset($_POST["end_hour"]))	{$end_hour=$_POST["end_hour"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

if (strlen($shift)<2) {$shift='ALL';}


$report_name = 'Fronter - Closer Report';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_text.="function ToggleAgentSpan(span_name) {\n";
$JS_text.="	if (document.getElementById(span_name).style.display=='none') {\n";
$JS_text.="		document.getElementById(span_name).style.display='block';\n";
$JS_text.="	} else {\n";
$JS_text.="		document.getElementById(span_name).style.display='none';\n";
$JS_text.="	}\n";
$JS_text.="}\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db FROM system_settings;";
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
$shift = preg_replace("/'|\"|\\\\|;/","",$shift);

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and view_reports > 0;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports > 0;";
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
	$VDdisplayMESSAGE = "Login incorrect, please try again";
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = "Too many login attempts, try again in 15 minutes";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group, $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
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

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = 'CL_TEST_L';}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$stmt="select group_id from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
$groups_string='|';
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	$groups_string .= "$groups[$i]|";
	$i++;
	}

$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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
	{
	$user_group_SQL = "";
	$VLuser_group_SQL = "";
	$VCLuser_group_SQL = "";
	}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$TCuser_group_SQL = $user_group_SQL;
	$VLuser_group_SQL = "and vicidial_log.user_group IN($user_group_SQL)";
	$VCLuser_group_SQL = "and vicidial_closer_log.user_group IN($user_group_SQL)";
	$user_group_SQL = "and vicidial_agent_log.user_group IN($user_group_SQL)";
	$TCuser_group_SQL = "and user_group IN($TCuser_group_SQL)";
	}

if ($DB) {echo "$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}


$HTML_head.="<HTML>\n";
$HTML_head.="<HEAD>\n";
$HTML_head.="<STYLE type=\"text/css\">\n";
$HTML_head.="<!--\n";
$HTML_head.="   .green {color: white; background-color: green}\n";
$HTML_head.="   .red {color: white; background-color: red}\n";
$HTML_head.="   .blue {color: white; background-color: blue}\n";
$HTML_head.="   .purple {color: white; background-color: purple}\n";
$HTML_head.="-->\n";
$HTML_head.=" </STYLE>\n";
$HTML_head.="\n";
$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";

#if (strlen($group) > 0)
#	{
#	if (preg_match("/\|$group\|/i",$groups_string))
#		{
#	#	$HTML_head.="<!-- group set: $group  $groups_string -->\n";
#		}
#	else
#		{
#	#	$HTML_head.="<!-- group not found: $group  $groups_string -->\n";
#		$group='';
#		}
#	}

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>$report_name</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

#	require("admin_header.php");

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

$HTML_text.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'query_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
$HTML_text.="<select name='query_hour'>\n";
if ($query_hour) {$HTML_text.="<option value='$query_hour' selected>$query_hour</option>\n";}
$HTML_text.="<option value=''>HR</option>\n";
for ($q=0; $q<24; $q++) {
	$q=substr("0$q", -2);
	$HTML_text.="<option value='$q'>$q</option>\n";
}
$HTML_text.="</select>:00:00\n";

$HTML_text.=" to <INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'end_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
$HTML_text.="<select name='end_hour'>\n";
if ($end_hour) {$HTML_text.="<option value='$end_hour' selected>$end_hour</option>\n";}
$HTML_text.="<option value=''>HR</option>\n";
for ($q=0; $q<24; $q++) {
	$q=substr("0$q", -2);
	$HTML_text.="<option value='$q'>$q</option>\n";
}
$HTML_text.="</select>:59:59\n";


$HTML_text.=" &nbsp;&nbsp;&nbsp; ";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>$report_display_type</option>";}
$HTML_text.="<option value='TEXT'>TEXT</option><option value='HTML'>HTML</option></select>\n";
$HTML_text.="<SELECT SIZE=1 NAME=shift>\n";
$HTML_text.="<option selected value=\"$shift\">$shift</option>\n";
$HTML_text.="<option value=\"\">--</option>\n";
$HTML_text.="<option value=\"AM\">AM</option>\n";
$HTML_text.="<option value=\"PM\">PM</option>\n";
$HTML_text.="<option value=\"ALL\">ALL</option>\n";
$HTML_text.="</SELECT>\n";
$HTML_text.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=submit NAME=SUBMIT VALUE=SUBMIT>\n";
$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  <a href=\"$PHP_SELF?query_date=$query_date&end_date=$end_date&group=$group$user_groupQS&shift=$shift&file_download=1\">DOWNLOAD</a> | <a href=\"./admin.php?ADD=3111&group_id=$group\">MODIFY</a> | <a href=\"./admin.php?ADD=999999\">REPORTS</a><BR/></FONT></td></tr>\n";

$HTML_text.="<tr><td valign='top'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>In-group: <SELECT SIZE=1 NAME=group>\n";
	$o=0;
	while ($groups_to_print > $o)
	{
		if ($groups[$o] == $group) {$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
		  else {$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
		$o++;
	}
$HTML_text.="</SELECT>&nbsp;&nbsp;&nbsp;\n";

$HTML_text.="User Groups: <SELECT NAME=user_group[] size=5 multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- ALL USER GROUPS --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- ALL USER GROUPS --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/$user_groups[$o]\|/i",$user_group_string)) {$HTML_text.="<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";


$HTML_text.="</font></td></tr></TABLE></FORM>\n\n";

$HTML_text.="<PRE><FONT SIZE=2>\n\n";


if (!$group)
{
$HTML_text.="\n\n";
$HTML_text.="PLEASE SELECT AN IN-GROUP AND DATE ABOVE THEN CLICK SUBMIT\n";
}

else
{
#	$time_BEGIN=$AM_shift_BEGIN;
#	$time_END=$AM_shift_END;
#$query_date_BEGIN = "$query_date $time_BEGIN";   
#$query_date_END = "$query_date $time_END";

$Cqdate = explode('-',$query_date);
$Eqdate = explode('-',$end_date);

if ($shift == 'AM') 
	{
	$query_date_BEGIN = date("Y-m-d H:i:s", mktime(1, 0, 0, $Cqdate[1], $Cqdate[2], $Cqdate[0]));
	$query_date_END = date("Y-m-d H:i:s", mktime(17, 45, 0, $Eqdate[1], $Eqdate[2], $Eqdate[0]));
	}
if ($shift == 'PM') 
	{
	$query_date_BEGIN = date("Y-m-d H:i:s", mktime(17, 45, 1, $Cqdate[1], $Cqdate[2], $Cqdate[0]));
	$query_date_END = date("Y-m-d H:i:s", mktime(24, 59, 59, $Eqdate[1], $Eqdate[2], $Eqdate[0]));
	}
if ($shift == 'ALL') 
	{
	$query_date_BEGIN = date("Y-m-d H:i:s", mktime(1, 0, 0, $Cqdate[1], $Cqdate[2], $Cqdate[0]));
	$query_date_END = date("Y-m-d H:i:s", mktime(24, 59, 59, $Eqdate[1], $Eqdate[2], $Eqdate[0]));
	}
if ($query_hour) 
	{
	$query_date_BEGIN = date("Y-m-d H:i:s", mktime($query_hour, 0, 0, $Cqdate[1], $Cqdate[2], $Cqdate[0]));
	}
if ($end_hour) 
	{
	$query_date_END = date("Y-m-d H:i:s", mktime($end_hour, 59, 59, $Eqdate[1], $Eqdate[2], $Eqdate[0]));
	}

$HTML_text.="In-Group Fronter-Closer Stats Report                      $NOW_TIME\n";

$HTML_text.="\n";
$HTML_text.="---------- TOTALS FOR $query_date_BEGIN to $query_date_END\n";

$fronter_campaign_stmt="select campaign_id from vicidial_campaigns where closer_campaigns like '%".mysqli_real_escape_string($link, $group)."%' $LOGallowed_campaignsSQL";
$fronter_campaign_rslt=mysql_to_mysqli($fronter_campaign_stmt, $link);
$fronter_campaigns_ct=mysqli_num_rows($fronter_campaign_rslt);
for ($q=0; $q<$fronter_campaigns_ct; $q++) {
	$campaign_row=mysqli_fetch_row($fronter_campaign_rslt);
	$fronter_campaigns.="'$campaign_row[0]',";
}
$fronter_campaigns=preg_replace('/,$/', '', $fronter_campaigns);

$sale_dispo_stmt="select distinct status from vicidial_campaign_statuses where sale='Y' and campaign_id in (SELECT campaign_id from vicidial_campaigns where closer_campaigns LIKE \"% " . mysqli_real_escape_string($link, $group) . " %\" $LOGallowed_campaignsSQL) UNION select distinct status from vicidial_statuses where sale='Y'";
if ($DB) {$HTML_text.="$sale_dispo_stmt\n";}
$sale_dispo_rslt=mysql_to_mysqli($sale_dispo_stmt, $link);
$sale_dispos="'SALE'"; $sale_dispo_str="|SALE";
while ($ssrow=mysqli_fetch_row($sale_dispo_rslt)) {
	$sale_dispos.=",'$ssrow[0]'";
	$sale_dispo_str.="|$ssrow[0]";
}
$sale_dispo_str.="|";
if ($DB) {$HTML_text.="Sale dispo string: $sale_dispo_str\n";}

$stmt="select count(*) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id='" . mysqli_real_escape_string($link, $group) . "' and status in ($sale_dispos) $VCLuser_group_SQL;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$row=mysqli_fetch_row($rslt);
$A1_points = ($row[0] * 1);
$A1_points =	sprintf("%10s", $A1_points);
$A1_tally =	sprintf("%10s", $row[0]);

$TOT_tally = ($A1_tally + $A2_tally + $A3_tally + $A4_tally);
$TOT_points = ($A1_points + $A2_points + $A3_points + $A4_points);
$TOT_tally =	sprintf("%10s", $TOT_tally);
$TOT_points =	sprintf("%10s", $TOT_points);

$HTML_text.="IN-GROUP:        $group\n";
$HTML_text.="USER GROUPS:     $user_group_string\n";
$HTML_text.="STATUS           CUSTOMERS\n";
$HTML_text.="SALES:          $A1_tally\n";

$HTML_text.="\n";









##############################
#########  FRONTER STATS

$TOTagents=0;
$TOTcalls=0;
$TOTsales=0;
$totA1=0;
$totDROP=0;
$totOTHER=0;
$TOTunder540=0;
$TOTover540=0;
$TOTagent_xfers=0;
$TOTagent_calls=0;

$CSV_fronter_header="\"TOTALS FOR $query_date_BEGIN to $query_date_END\"\n";
$CSV_fronter_header.="\"IN-GROUP:\",\"$group\"\n";
$CSV_fronter_header.="\"USER GROUPS:\",\"$user_group_string\"\n";
$CSV_fronter_header.="\"STATUS   CUSTOMERS\"\n";
$CSV_fronter_header.="\"SALES:   $A1_tally\"\n\n";
$CSV_fronter_header.="\"FRONTER STATS\"\n";
$CSV_fronter_header.="\"AGENT\",\"XFERS\",\"CALLS\",\"XFER %\",\"DROP\",\"OVER 540\",\"UNDER 539\",\"SALE\"\n";
$CSV_fronter_lines="";
$CSV_fronter_footer="";

$CSV_fronter_header2="\"TOTALS FOR $query_date_BEGIN to $query_date_END\"\n";
$CSV_fronter_header.="\"IN-GROUP:\",\"$group\"\n";
$CSV_fronter_header.="\"USER GROUPS:\",\"$user_group_string\"\n";
$CSV_fronter_header2.="\"SALES:   $A1_tally\"\n\n";
$CSV_fronter_header2.="\"FRONTER XFER DETAILS\"\n";

$CSV_fronter_header3="\"TOTALS FOR $query_date_BEGIN to $query_date_END\"\n";
$CSV_fronter_header.="\"IN-GROUP:\",\"$group\"\n";
$CSV_fronter_header.="\"USER GROUPS:\",\"$user_group_string\"\n";
$CSV_fronter_header3.="\"SALES:   $A1_tally\"\n\n";
$CSV_fronter_header3.="\"CLOSER XFER DETAILS\"\n";


$ASCII_text="\n";
$ASCII_text.="---------- FRONTER STATS      <a href=\"$PHP_SELF?query_date=$query_date&end_date=$end_date&group=$group$user_groupQS&shift=$shift&file_download=2\">[download details]</a>\n";
$ASCII_text.="+--------------------------+--------+--------+--------+------+------+------+------+\n";
$ASCII_text.="|                          |        |        |        |      | OVER |UNDER |      |\n";
$ASCII_text.="| AGENT (click for details)| XFERS  | CALLS  | XFER % | DROP |  540 | 540  | SALE |\n";
$ASCII_text.="+--------------------------+--------+--------+--------+------+------+------+------+\n";

######## GRAPHING #########
$graph_stats=array();
$max_success=1;
$max_xfers=1;
$max_success_pct=1;
$max_sales=1;
$max_drops=1;
$max_other=1;
$GRAPH="<a name='frontergraph'/><table border='0' cellpadding='0' cellspacing='2' width='800'>";
$GRAPH.="<tr><th width='16%' class='grey_graph_cell' id='frontergraph1'><a href='#' onClick=\"DrawFronterGraph('SUCCESS', '1'); return false;\">SUCCESS</a></th><th width='17%' class='grey_graph_cell' id='frontergraph2'><a href='#' onClick=\"DrawFronterGraph('XFERS', '2'); return false;\">XFERS</a></th><th width='17%' class='grey_graph_cell' id='frontergraph3'><a href='#' onClick=\"DrawFronterGraph('SUCCESSPCT', '3'); return false;\">SUCCESS %</a></th><th width='16%' class='grey_graph_cell' id='frontergraph4'><a href='#' onClick=\"DrawFronterGraph('SALE', '4'); return false;\">SALE</a></th><th width='17%' class='grey_graph_cell' id='frontergraph5'><a href='#' onClick=\"DrawFronterGraph('DROP', '5'); return false;\">DROP</a></th><th width='17%' class='grey_graph_cell' id='frontergraph6'><a href='#' onClick=\"DrawFronterGraph('OTHER', '6'); return false;\">OTHER</a></th></tr>";
$GRAPH.="<tr><td colspan='6' class='graph_span_cell'><span id='fronter_graph'><BR>&nbsp;<BR></span></td></tr></table><BR><BR>";
$graph_header="<table cellspacing='0' cellpadding='0' summary='STATUS' class='horizontalgraph'><caption align='top'>FRONTER STATS</caption><tr><th class='thgraph' scope='col'>AGENT</th>";
$SUCCESS_graph=$graph_header."<th class='thgraph' scope='col'>SUCCESS </th></tr>";
$XFERS_graph=$graph_header."<th class='thgraph' scope='col'>XFERS</th></tr>";
$SUCCESSPCT_graph=$graph_header."<th class='thgraph' scope='col'>SUCCESS %</th></tr>";
$SALE_graph=$graph_header."<th class='thgraph' scope='col'>SALE</th></tr>";
$DROP_graph=$graph_header."<th class='thgraph' scope='col'>DROP</th></tr>";
$OTHER_graph=$graph_header."<th class='thgraph' scope='col'>OTHER</th></tr>";
###########################

# GET ARRAY OF VICIDIAL USERS FOR LATER USE
$user_stmt="select user, full_name, user_group from vicidial_users where user_group is not null $TCuser_group_SQL";
if ($DB) {$ASCII_text.="$user_stmt\n";}
$user_rslt=mysql_to_mysqli($user_stmt, $link);
$user_array=array();
$user_id_array=array();
while ($user_row=mysqli_fetch_row($user_rslt)) {
	$user_array["$user_row[0]"]=$user_row[1];
	$user_id_array[]=$user_row[0];
}
$user_full_str="'".implode("','", $user_id_array)."'";

# $stmt="select vicidial_xfer_log.user,count(distinct vicidial_xfer_log.lead_id) from vicidial_xfer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id='" . mysqli_real_escape_string($link, $group) . "' and user in ($user_full_str) group by user;";
$stmt="select vicidial_xfer_log.user,count(*) from vicidial_xfer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id='" . mysqli_real_escape_string($link, $group) . "' and user in ($user_full_str) group by user;";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$TOTcalls = ($TOTcalls + $row[1]);

	$userRAW[$i]=$row[0];
	$user[$i] =	sprintf("%-6s", $row[0]);while(strlen($user[$i])>6) {$user[$i] = substr("$user[$i]", 0, -1);}
	$USERcallsRAW[$i] =	$row[1];
	$USERcalls[$i] =	sprintf("%6s", $row[1]);

	$i++;
	}

$i=0;
while ($i < $users_to_print)
	{
	$stmt="select full_name from vicidial_users where user='$userRAW[$i]' $LOGadmin_viewable_groupsSQL;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$names_to_print = mysqli_num_rows($rslt);
	if ($names_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		if ($non_latin < 1)
			{
			 $full_name[$i] =	sprintf("%-15s", $row[0]); while(strlen($full_name[$i])>15) {$full_name[$i] = substr("$full_name[$i]", 0, -1);}	
			}
		else
			{
			 $full_name[$i] =	sprintf("%-45s", $row[0]); while(mb_strlen($full_name[$i],'utf-8')>15) {$full_name[$i] = mb_substr("$full_name[$i]", 0, -1,'utf-8');}	
			}
		}
	else
		{$full_name[$i] = '               ';}

	$A1=0; $DROP=0; $OTHER=0; $sales=0; $under540=0; $over540=0; $agent_xfers=0; $agent_calls=0;

	# GET TOTAL CALLS
	$call_stmt="select count(*) from vicidial_agent_log where user='$userRAW[$i]' and campaign_id in ($fronter_campaigns) $user_group_SQL and event_time >= '$query_date_BEGIN' and event_time <= '$query_date_END' and lead_id is not null";
	if ($DB) {$ASCII_text.="$call_stmt\n";}
	$call_rslt=mysql_to_mysqli($call_stmt, $link);
	$crow=mysqli_fetch_row($call_rslt);
	$agent_calls+=$crow[0];

	# GET TOTAL TRANSFERS ON OUTBOUND CALLS
	# $stmt="select vicidial_list.phone_number, trim(concat(vicidial_list.first_name, ' ', vicidial_list.last_name)) as full_name, vicidial_list.lead_id, vicidial_log.length_in_sec, vicidial_xfer_log.user, vicidial_xfer_log.closer, (vicidial_closer_log.length_in_sec-vicidial_closer_log.queue_seconds) as talk_time, vicidial_xfer_log.call_date, vicidial_xfer_log.xfercallid from vicidial_list, vicidial_log, vicidial_xfer_log, vicidial_closer_log where vicidial_xfer_log.call_date >= '$query_date_BEGIN' and vicidial_xfer_log.call_date <= '$query_date_END' and vicidial_log.call_date >= '$query_date_BEGIN' and vicidial_log.call_date <= '$query_date_END' and vicidial_log.campaign_id in ($fronter_campaigns) $VLuser_group_SQL and vicidial_xfer_log.campaign_id='" . mysqli_real_escape_string($link, $group) . "' and vicidial_xfer_log.user='$userRAW[$i]' and vicidial_xfer_log.lead_id=vicidial_log.lead_id and vicidial_xfer_log.user=vicidial_log.user and vicidial_xfer_log.lead_id=vicidial_list.lead_id and vicidial_xfer_log.xfercallid=vicidial_closer_log.xfercallid $VCLuser_group_SQL order by vicidial_xfer_log.call_date asc";

	$stmt="select vicidial_list.phone_number, trim(concat(vicidial_list.first_name, ' ', vicidial_list.last_name)) as full_name, vicidial_list.lead_id, 0 as length_in_sec, vicidial_xfer_log.user, vicidial_xfer_log.closer, (vicidial_closer_log.length_in_sec-vicidial_closer_log.queue_seconds) as talk_time, vicidial_xfer_log.call_date, vicidial_xfer_log.xfercallid from vicidial_list, vicidial_xfer_log, vicidial_closer_log where vicidial_xfer_log.call_date >= '$query_date_BEGIN' and vicidial_xfer_log.call_date <= '$query_date_END' and vicidial_xfer_log.campaign_id='" . mysqli_real_escape_string($link, $group) . "' and vicidial_xfer_log.user='$userRAW[$i]' and vicidial_xfer_log.lead_id=vicidial_list.lead_id and vicidial_xfer_log.xfercallid=vicidial_closer_log.xfercallid $VCLuser_group_SQL order by vicidial_xfer_log.call_date asc";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$calls_to_print = mysqli_num_rows($rslt);

	$xfer_info_array=array();
	$xfercallid_str="";
	$xfercallid_SQL="";
	$agent_xfers=0;
	for($q=0; $q<$calls_to_print; $q++) {
		$frow=mysqli_fetch_row($rslt);
		$lead_id=$frow[2];
		$call_date=$frow[7];

		# Check if there was a call by this agent from the fronter logs with 30 minutes of the transfer.  If so, this was a transfer from an outbound call and should not be included in the later inbound call
		$sec_stmt="select length_in_sec from vicidial_log where user='$userRAW[$i]' and lead_id='$lead_id' and call_date<'$call_date' and call_date>='$call_date'-INTERVAL 30 MINUTE order by call_date desc limit 1";
		$sec_rslt=mysql_to_mysqli($sec_stmt, $link);
		if ($DB) {$ASCII_text.="$sec_stmt\n";}
		if (mysqli_num_rows($sec_rslt)>0) {
			$xfercallid=$frow[8];
			$array_key="$userRAW[$i]-$xfercallid";

			$sec_row=mysqli_fetch_row($sec_rslt);
			$agent_xfers++;
			if ($sec_row[0]>=540) {$over540++;} else {$under540++;}
			$xfercallid_str.="$xfercallid,";

			$xfer_info_array[$array_key][0]=$frow[0];
			$xfer_info_array[$array_key][1]=$frow[1];
			$xfer_info_array[$array_key][2]=$frow[2];
			$xfer_info_array[$array_key][3]=$sec_row[0];
			$xfer_info_array[$array_key][4]=$frow[4];
			$xfer_info_array[$array_key][5]=$frow[5];
			$xfer_info_array[$array_key][6]=$frow[6];
			$xfer_info_array[$array_key][7]=$frow[7]; # SORT BY THIS
			$xfer_info_array[$array_key][8]=$frow[8];

			#$hphone=sprintf("%-12s", $frow[0]);
			#if (strlen($frow[1]==0)) {$frow[1]=" ** No name on lead ** ";}
			#$hname=sprintf("%-30s", substr($frow[1],0,30));
			#$hlead_id=sprintf("%-10s", $frow[2]);
			#$closer=sprintf("%-6s", $frow[5]);
			#$closer_full_name=sprintf("%-15s", substr($user_array["$frow[5]"],0, 15));
			#$closer_talk_time=$frow[6];
			#$closer_talk_time = sec_convert($closer_talk_time,'H'); 
			#$closer_talk_time =	sprintf("%5s", $closer_talk_time);
		}
		
	}
	if (strlen($xfercallid_str)>0) {
		$xfercallid_str=substr($xfercallid_str,0,-1);
		$xfercallid_SQL=" and vicidial_xfer_log.xfercallid not in ($xfercallid_str) ";
	}

	# GET TOTAL TRANSFERS ON INBOUND CALLS
	$stmt="select vicidial_closer_log.phone_number, trim(concat(vicidial_list.first_name, ' ', vicidial_list.last_name)) as full_name, vicidial_closer_log.lead_id, vicidial_closer_log.length_in_sec, vicidial_xfer_log.user, vicidial_xfer_log.closer, (vicidial_closer_log.length_in_sec-vicidial_closer_log.queue_seconds) as talk_time, vicidial_closer_log.call_date, vicidial_xfer_log.xfercallid from vicidial_list, vicidial_xfer_log, vicidial_closer_log where vicidial_xfer_log.call_date >= '$query_date_BEGIN' and vicidial_xfer_log.call_date <= '$query_date_END' and vicidial_closer_log.call_date >= '$query_date_BEGIN' and vicidial_closer_log.call_date <= '$query_date_END' and  vicidial_closer_log.campaign_id='" . mysqli_real_escape_string($link, $group) . "' and vicidial_xfer_log.campaign_id='" . mysqli_real_escape_string($link, $group) . "' and vicidial_xfer_log.user='$userRAW[$i]' and vicidial_closer_log.lead_id=vicidial_xfer_log.lead_id and vicidial_closer_log.xfercallid=vicidial_xfer_log.xfercallid $xfercallid_SQL and vicidial_closer_log.lead_id=vicidial_list.lead_id order by vicidial_closer_log.call_date;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$calls_to_print = mysqli_num_rows($rslt);

	$CSV_fronter_header2.="\n\"$user[$i] - $full_name[$i] XFER STATS\"\n";
	$CSV_fronter_header2.="\"PHONE\",\"LEAD NAME\",\"LEAD ID\",\"CLOSER\",\"CLOSER CALL DATE\"\n";

	$hidden_span_txt="<span id='fronter_agent_$userRAW[$i]' name='fronter_agent_$userRAW[$i]' style='display: none;'>";
	if ($calls_to_print==0) {$hidden_span_txt.=": ".sprintf("%-59s", "*** NO TRANSFER INFORMATION TO REPORT ***")." :\n";}
	$agent_xfers+=$calls_to_print;
	for($q=0; $q<$calls_to_print; $q++) {
		$frow=mysqli_fetch_row($rslt);
		$array_key="$userRAW[$i]-$frow[8]";
		#$hphone=sprintf("%-12s", $frow[0]);
		#if (strlen($frow[1]==0)) {$frow[1]=" ** No name on lead ** ";}
		#$hname=sprintf("%-30s", substr($frow[1],0,30));
		#$hlead_id=sprintf("%-10s", $frow[2]);
		#$closer=sprintf("%-6s", $frow[5]);
		#$closer_full_name=sprintf("%-15s", substr($user_array["$frow[5]"],0, 15));
		#$closer_talk_time=$frow[6];
		#$closer_talk_time = sec_convert($closer_talk_time,'H'); 
		#$closer_talk_time =	sprintf("%5s", $closer_talk_time);

		if ($frow[3]>=540) {$over540++;} else {$under540++;}
		$xfer_info_array[$array_key][0]=$frow[0];
		$xfer_info_array[$array_key][1]=$frow[1];
		$xfer_info_array[$array_key][2]=$frow[2];
		$xfer_info_array[$array_key][3]=$frow[3];
		$xfer_info_array[$array_key][4]=$frow[4];
		$xfer_info_array[$array_key][5]=$frow[5];
		$xfer_info_array[$array_key][6]=$frow[6];
		$xfer_info_array[$array_key][7]=$frow[7]; # SORT BY THIS
		$xfer_info_array[$array_key][8]=$frow[8];
	

		/*
		$hidden_span_txt.=": $hphone : $hname : $hlead_id : $closer - $closer_full_name :\n";
		$closer_spans["$frow[5]"].=": $user[$i] - $full_name[$i] : $hphone :  $closer_talk_time  :\n";
		$closer_CSV_span["$frow[5]"].="\"$userRAW[$i] - $full_name[$i]\",\"$hphone\",\"$closer_talk_time\"\n";
		$CSV_fronter_header2.="\"$hphone\",\"$hname\",\"$hlead_id\",\"$closer - ".$user_array["$frow[5]"]."\"\n";
		*/
	}
	echo "<!--\n";
	print_r($xfer_info_array);
	echo "//-->\n";
	
	$new_xfer_info_array=array();
	while (list($key, $val)=each($xfer_info_array)) {
		$new_xfer_info_array[]=array('ID' => $key, 'phone' => $val[0], 'name' => $val[1], 'lead_id' => $val[2], 'length_in_sec' => $val[3], 'user' => $val[4], 'closer' => $val[5], 'talk_time' => $val[6], 'call_date' => $val[7], 'xfercallid' => $val[8]);
	}
	
	foreach ($new_xfer_info_array as $key2 => $row2) {
		$phone_ary[$key2]  = $row2['phone'];
		$name_ary[$key2]  = $row2['name'];
		$lead_id_ary[$key2]  = $row2['lead_id'];
		$length_in_sec_ary[$key2]  = $row2['length_in_sec'];
		$user_ary[$key2]  = $row2['user'];
		$closer_ary[$key2]  = $row2['closer'];
		$talk_time_ary[$key2]  = $row2['talk_time'];
		$call_date_ary[$key2]  = $row2['call_date'];
		$xfercallid_ary[$key2]  = $row2['xfercallid'];
	}

	// Sort the data with volume descending, edition ascending
	// Add $data as the last parameter, to sort by the common key
	array_multisort($call_date_ary, SORT_ASC, $xfercallid_ary, SORT_ASC, $lead_id_ary, SORT_ASC, $phone_ary, SORT_ASC, $name_ary, SORT_ASC, $length_in_sec_ary, SORT_ASC, $user_ary, SORT_ASC, $closer_ary, SORT_ASC, $talk_time_ary, SORT_ASC, $new_xfer_info_array);

	foreach ($new_xfer_info_array as $row) {

		$hphone=sprintf("%-12s", $row['phone']);
		if (strlen($row['name']==0)) {$row['name']=" ** No name on lead ** ";}
		$hname=sprintf("%-30s", substr($row['name'],0,30));
		$hlead_id=sprintf("%-10s", $row['lead_id']);
		$closer=sprintf("%-6s", $row['closer']);
		$closer_full_name=sprintf("%-15s", substr($user_array["$row[closer]"],0, 15));
		$closer_talk_time=$row['talk_time'];
		$closer_talk_time = sec_convert($closer_talk_time,'H'); 
		$closer_talk_time =	sprintf("%5s", $closer_talk_time);

		$hidden_span_txt.=": $hphone : $hname : $hlead_id : $closer - $closer_full_name : $row[call_date] :\n";
		$closer_spans["$row[closer]"].=": $user[$i] - $full_name[$i] : $hphone :  $closer_talk_time  :\n";
		$closer_CSV_span["$row[closer]"].="\"$userRAW[$i] - $full_name[$i]\",\"$hphone\",\"$closer_talk_time\",\"$row[call_date]\"\n";
		$CSV_fronter_header2.="\"$hphone\",\"$hname\",\"$hlead_id\",\"$closer - ".$user_array["$row[closer]"]."\"\n";
	}

	echo "<!--\n";
	print_r($closer_spans);
	echo "//-->\n";
	$hidden_span_txt.="</span>";

	$stmt="select vicidial_closer_log.status,count(distinct vicidial_closer_log.lead_id) from vicidial_xfer_log, vicidial_closer_log where vicidial_xfer_log.call_date >= '$query_date_BEGIN' and vicidial_xfer_log.call_date <= '$query_date_END' and vicidial_closer_log.call_date >= '$query_date_BEGIN' and vicidial_closer_log.call_date <= '$query_date_END' and  vicidial_closer_log.campaign_id='" . mysqli_real_escape_string($link, $group) . "' $vicidial_closer_logLuser_group_SQL and vicidial_xfer_log.campaign_id='" . mysqli_real_escape_string($link, $group) . "' and vicidial_xfer_log.user='$userRAW[$i]' and vicidial_closer_log.lead_id=vicidial_xfer_log.lead_id and vicidial_closer_log.xfercallid=vicidial_xfer_log.xfercallid group by vicidial_closer_log.status;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$lead_ids_to_print = mysqli_num_rows($rslt);
	$j=0;
	while ($j < $lead_ids_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$recL=0;
		if ( (preg_match("/\|$row[0]\|/", $sale_dispo_str)) and ($recL < 1) ) {$A1=$row[1]; $recL++; $sales=($sales + $row[1]);}
		if ( ($row[0]=='DROP') and ($recL < 1) ) {$DROP=$row[1]; $recL++;}
		if ($recL < 1) {$OTHER=($row[1] + $OTHER); $recL++;}
		$j++;
		}

	$totA1 = ($totA1 + $A1);
	$totDROP = ($totDROP + $DROP);
	$totOTHER = ($totOTHER + $OTHER);
	$TOTsales = ($TOTsales + $sales);
	$TOTunder540 = ($TOTunder540 + $under540);
	$TOTover540 = ($TOTover540 + $over540);
	$TOTagent_xfers = ($TOTagent_xfers + $agent_xfers);
	$TOTagent_calls = ($TOTagent_calls + $agent_calls);

	$Spct = MathZDC($sales, $USERcallsRAW[$i])*100;
	$Spct = round($Spct, 2);
	$Spct =	sprintf("%01.2f", $Spct);

	$Xpct = MathZDC($agent_xfers, $agent_calls)*100;
	$Xpct = round($Xpct, 2);
	$Xpct =	sprintf("%01.2f", $Xpct);
	
	if ($sales>$max_success) {$max_success=$sales;}
	if ($USERcalls[$i]>$max_xfers) {$max_xfers=$USERcalls[$i];}
	if ($Spct>$max_success_pct) {$max_success_pct=$Spct;}
	if ($A1>$max_sales) {$max_sales=$A1;}
	if ($DROP>$max_drops) {$max_drops=$DROP;}
	if ($OTHER>$max_other) {$max_other=$OTHER;}
	$graph_stats[$i][0]="$user[$i] - $full_name[$i]";
	$graph_stats[$i][1]=$sales;
	$graph_stats[$i][2]=$USERcalls[$i];
	$graph_stats[$i][3]=$Spct;
	$graph_stats[$i][4]=$A1;
	$graph_stats[$i][5]=$DROP;
	$graph_stats[$i][6]=$OTHER;

	$agent_xfers = sprintf("%6s", $agent_xfers);
	$agent_calls = sprintf("%6s", $agent_calls);
	$A1 =	sprintf("%4s", $A1);
	$DROP =	sprintf("%4s", $DROP);
	$OTHER =	sprintf("%4s", $OTHER);
	$sales =	sprintf("%4s", $sales);
	$Spct =	sprintf("%6s", $Spct);
	$Xpct =	sprintf("%6s", $Xpct);
	$under540 =	sprintf("%4s", $under540);
	$over540 =	sprintf("%4s", $over540);


	$ASCII_text.="<a name='ag".trim($user[$i])."'/>| <a href='#ag".trim($user[$i])."' onClick=\"ToggleAgentSpan('fronter_agent_".trim($user[$i])."')\">$user[$i] - $full_name[$i]</a> | $agent_xfers | $agent_calls | $Xpct%| $DROP | $over540 | $under540 | $sales |\n";
	$ASCII_text.=$hidden_span_txt;
	$CSV_fronter_lines.="\"$user[$i] - $full_name[$i]\",\"$agent_xfers\",\"$agent_calls\",\"$Xpct%\",\"$DROP\",\"$over540\",\"$under540\",\"$sales\"\n";

	$i++;
	}


$totSpct = MathZDC($TOTsales, $TOTcalls)*100;
$totSpct = round($totSpct, 2);
$totSpct =	sprintf("%01.2f", $totSpct);
$totSpct =	sprintf("%6s", $totSpct);

$totXpct = MathZDC($TOTagent_xfers, $TOTagent_calls)*100;
$totXpct = round($totXpct, 2);
$totXpct =	sprintf("%01.2f", $totXpct);
$totXpct =	sprintf("%6s", $totXpct);
	

$TOTagents =	sprintf("%6s", $i);
$TOTcalls =		sprintf("%6s", $TOTcalls);
$TOTsales =		sprintf("%5s", $TOTsales);
$totA1 =		sprintf("%5s", $totA1);
$totDROP =		sprintf("%5s", $totDROP);
$totOTHER =		sprintf("%5s", $totOTHER);
$TOTunder540 =	sprintf("%4s", $TOTunder540);
$TOTover540 =	sprintf("%4s", $TOTover540);
$TOTagent_xfers = sprintf("%6s", $TOTagent_xfers);
$TOTagent_calls = sprintf("%6s", $TOTagent_calls);


$stmt="select avg(queue_seconds) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and campaign_id='" . mysqli_real_escape_string($link, $group) . "' $VCLuser_group_SQL;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$AVGwait = $row[0];
$AVGwait_M = MathZDC($AVGwait, 60);
$AVGwait_M = round($AVGwait_M, 2);
$AVGwait_M_int = intval("$AVGwait_M");
$AVGwait_S = ($AVGwait_M - $AVGwait_M_int);
$AVGwait_S = ($AVGwait_S * 60);
$AVGwait_S = round($AVGwait_S, 0);
if ($AVGwait_S < 10) {$AVGwait_S = "0$AVGwait_S";}
$AVGwait_MS = "$AVGwait_M_int:$AVGwait_S";
$AVGwait =		sprintf("%6s", $AVGwait_MS);

$ASCII_text.="+--------------------------+--------+--------+--------+------+------+------+------+\n";
$ASCII_text.="| TOTAL FRONTERS: $TOTagents   | $TOTagent_xfers | $TOTagent_calls | $totXpct%|$totDROP | $TOTover540 | $TOTunder540 |$TOTsales |\n";
$ASCII_text.="+--------------------------+--------+--------+--------+------+------+------+------+\n";
$ASCII_text.="|                                  Average time in Queue for customers:    $AVGwait |\n";
$ASCII_text.="+--------------------------+--------+--------+--------+------+------+------+------+\n";

$CSV_fronter_footer.="\"TOTAL FRONTERS: $TOTagents\",\"$TOTagent_xfers\",\"$TOTagent_calls\",\"$totXpct%\",\"$totDROP\",\"$TOTunder540\",\"$TOTunder540\",\"$TOTsales\"\n";
$CSV_fronter_footer.="\"Average time in Queue for customers:    $AVGwait\"\n\n\n";

	for ($d=0; $d<count($graph_stats); $d++) {
		if ($d==0) {$class=" first";} else if (($d+1)==count($graph_stats)) {$class=" last";} else {$class="";}
		$SUCCESS_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][1], $max_success))."' height='16' />".$graph_stats[$d][1]."</td></tr>";
		$XFERS_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][2], $max_xfers))."' height='16' />".$graph_stats[$d][2]."</td></tr>";
		$SUCCESSPCT_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][3], $max_success_pct))."' height='16' />".$graph_stats[$d][3]."</td></tr>";
		$SALE_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][4], $max_sales))."' height='16' />".$graph_stats[$d][4]."</td></tr>";
		$DROP_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][5], $max_drops))."' height='16' />".$graph_stats[$d][5]."</td></tr>";
		$OTHER_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][5] ,$max_other))."' height='16' />".$graph_stats[$d][5]."</td></tr>";
	}
	$SUCCESS_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($TOTsales)."</th></tr></table>";
	$XFERS_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($TOTcalls)."</th></tr></table>";
	$SUCCESSPCT_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($totSpct)."%</th></tr></table>";
	$SALE_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($totA1)."</th></tr></table>";
	$DROP_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($totDROP)."</th></tr></table>";
	$OTHER_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($totOTHER)."</th></tr></table>";
	$JS_onload.="\tDrawFronterGraph('SUCCESS', '1');\n"; 
	$JS_text.="function DrawFronterGraph(graph, th_id) {\n";
	$JS_text.="	var SUCCESS_graph=\"$SUCCESS_graph\";\n";
	$JS_text.="	var XFERS_graph=\"$XFERS_graph\";\n";
	$JS_text.="	var SUCCESSPCT_graph=\"$SUCCESSPCT_graph\";\n";
	$JS_text.="	var SALE_graph=\"$SALE_graph\";\n";
	$JS_text.="	var DROP_graph=\"$DROP_graph\";\n";
	$JS_text.="	var OTHER_graph=\"$OTHER_graph\";\n";
	$JS_text.="\n";
	$JS_text.="	for (var i=1; i<=6; i++) {\n";
	$JS_text.="		var cellID=\"frontergraph\"+i;\n";
	$JS_text.="		document.getElementById(cellID).style.backgroundColor='#DDDDDD';\n";
	$JS_text.="	}\n";
	$JS_text.="	var cellID=\"frontergraph\"+th_id;\n";
	$JS_text.="	document.getElementById(cellID).style.backgroundColor='#999999';\n";
	$JS_text.="	var graph_to_display=eval(graph+\"_graph\");\n";
	$JS_text.="	document.getElementById('fronter_graph').innerHTML=graph_to_display;\n";
	$JS_text.="}\n";
	$GRAPH_text.=$GRAPH;

##############################
#########  CLOSER STATS

$TOTagents=0;
$TOTcalls=0;
$totA1=0;
$totA2=0;
$totA3=0;
$totA4=0;
$totA5=0;
$totA6=0;
$totA7=0;
$totA8=0;
$totA9=0;
$totDROP=0;
$totOTHER=0;
$TOTsales=0;
$TOTunder90=0;

$CSV_closer_header="";
$CSV_closer_lines="";
$CSV_closer_footer="";

$ASCII_text.="\n";
$ASCII_text.="---------- CLOSER STATS      <a href=\"$PHP_SELF?query_date=$query_date&end_date=$end_date&group=$group$user_groupQS&shift=$shift&file_download=3\">[download details]</a>\n";
$ASCII_text.="+--------------------------+--------+------+------+------+-------+-------+\n";
$ASCII_text.="|                          |        |      | UNDER|      |  AVG  |       |\n";
$ASCII_text.="| AGENT (click for details)| CALLS  | SALE |  90  | DROP | TIME  | CONV %|\n";
$ASCII_text.="+--------------------------+--------+------+------+------+-------+-------+\n";

$CSV_closer_header="\"CLOSER STATS\"\n";
$CSV_closer_header.="\"AGENT\",\"CALLS\",\"SALE\",\"UNDER 90 SEC\",\"DROP\",\"SALE\",\"CONV %\"\n";

######## GRAPHING #########
$max_calls=1;
$max_sales=1;
$max_drops=1;
$max_other=1;
$max_sales2=1;
$max_conv_pct=1;
$GRAPH="<BR><BR><a name='closergraph'/><table border='0' cellpadding='0' cellspacing='2' width='800'>";
$GRAPH.="<tr><th width='16%' class='grey_graph_cell' id='closergraph1'><a href='#' onClick=\"DrawCloserGraph('CALLS', '1'); return false;\">CALLS</a></th><th width='17%' class='grey_graph_cell' id='closergraph2'><a href='#' onClick=\"DrawCloserGraph('SALES', '2'); return false;\">SALES</a></th><th width='17%' class='grey_graph_cell' id='closergraph3'><a href='#' onClick=\"DrawCloserGraph('DROP', '3'); return false;\">DROP</a></th><th width='16%' class='grey_graph_cell' id='closergraph4'><a href='#' onClick=\"DrawCloserGraph('OTHER', '4'); return false;\">OTHER</a></th><th width='17%' class='grey_graph_cell' id='closergraph5'><a href='#' onClick=\"DrawCloserGraph('SALES2', '5'); return false;\">SALES</a></th><th width='17%' class='grey_graph_cell' id='closergraph6'><a href='#' onClick=\"DrawCloserGraph('CONVPCT', '6'); return false;\">CONV %</a></th></tr>";
$GRAPH.="<tr><td colspan='6' class='graph_span_cell'><span id='closer_graph'><BR>&nbsp;<BR></span></td></tr></table><BR><BR>";
$graph_header="<table cellspacing='0' cellpadding='0' summary='STATUS' class='horizontalgraph'><caption align='top'>CLOSER STATS</caption><tr><th class='thgraph' scope='col'>AGENT</th>";
$CALLS_graph=$graph_header."<th class='thgraph' scope='col'>CALLS </th></tr>";
$SALES_graph=$graph_header."<th class='thgraph' scope='col'>SALES</th></tr>";
$DROP_graph=$graph_header."<th class='thgraph' scope='col'>DROP</th></tr>";
$OTHER_graph=$graph_header."<th class='thgraph' scope='col'>OTHER</th></tr>";
$SALES2_graph=$graph_header."<th class='thgraph' scope='col'>SALES</th></tr>";
$CONVPCT_graph=$graph_header."<th class='thgraph' scope='col'>CONV %</th></tr>";
###########################

$stmt="select user,count(*) from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id='" . mysqli_real_escape_string($link, $group) . "'  $VCLuser_group_SQL and user is not null group by user;";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$ASCII_text.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$TOTcalls = ($TOTcalls + $row[1]);
	$userRAW[$i]=$row[0];
	$user[$i] =	sprintf("%-6s", $row[0]);while(strlen($user[$i])>6) {$user[$i] = substr("$user[$i]", 0, -1);}
	$USERcalls[$i] =	sprintf("%6s", $row[1]);
	$USERcallsRAW[$i] =	$row[1];



	$i++;
	}

$i=0;
$TOT_TALK_TIME=0;
while ($i < $users_to_print)
	{
	$stmt="select full_name from vicidial_users where user='$userRAW[$i]' $LOGadmin_viewable_groupsSQL;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$names_to_print = mysqli_num_rows($rslt);
	if ($names_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		if ($non_latin < 1)
			{
			 $full_name[$i] =	sprintf("%-15s", $row[0]); while(strlen($full_name[$i])>15) {$full_name[$i] = substr("$full_name[$i]", 0, -1);}	
			}
		else
			{
			 $full_name[$i] =	sprintf("%-45s", $row[0]); while(mb_strlen($full_name[$i],'utf-8')>15) {$full_name[$i] = mb_substr("$full_name[$i]", 0, -1,'utf-8');}	
			}
		}
	else
		{$full_name[$i] = '               ';}

	$A1=0; $A2=0; $A3=0; $A4=0; $A5=0; $A6=0; $A7=0; $A8=0; $A9=0; $DROP=0; $OTHER=0; $sales=0; $uTOP=0; $uBOT=0; $points=0; 
	$under90=0; $AVG_TALK_TIME=0; $TALK_TIME=0;
	$stmt="select status,count(*),sum(length_in_sec-queue_seconds) as talk_time,sum(if((length_in_sec-queue_seconds)<90, 1, 0)) as under90 from vicidial_closer_log where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and  campaign_id='" . mysqli_real_escape_string($link, $group) . "' $VCLuser_group_SQL and user='$userRAW[$i]' group by status;";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$ASCII_text.="$stmt\n";}
	$lead_ids_to_print = mysqli_num_rows($rslt);
	$j=0;
	
	while ($j < $lead_ids_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$recL=0;
		if ( preg_match("/\|$row[0]\|/", $sale_dispo_str) and ($recL < 1) ) 
			{
			$A1=$row[1]; $recL++; 
			$sales=($sales + $row[1]);
			$points = ($points + ($row[1] * 1) );
			}
		if ( ($row[0]=='DROP') and ($recL < 1) ) {$DROP=$row[1]; $recL++;}
		if ($recL < 1) {$OTHER=($row[1] + $OTHER); $recL++;}

		$TALK_TIME+=$row[2];
		$under90+=$row[3];
		
		$j++;
		}

	$totA1 = ($totA1 + $A1);	$TOTsales = ($TOTsales + $A1);
	$totA2 = ($totA2 + $A2);	$TOTsales = ($TOTsales + $A2);	$totTOP = ($totTOP + $A2);
	$totA3 = ($totA3 + $A3);	$TOTsales = ($TOTsales + $A3);	$totBOT = ($totBOT + $A3);
	$totA4 = ($totA4 + $A4);	$TOTsales = ($TOTsales + $A4);	$totTOP = ($totTOP + $A4);	$totBOT = ($totBOT + $A4);
	$totA5 = ($totA5 + $A5);
	$totA6 = ($totA6 + $A6);
	$totA7 = ($totA7 + $A7);
	$totA8 = ($totA8 + $A8);
	$totA9 = ($totA9 + $A9);
	$totDROP = ($totDROP + $DROP);
	$totOTHER = ($totOTHER + $OTHER);
	$totPOINTS = ($totPOINTS + $points);
	$TOT_TALK_TIME+=$TALK_TIME;
	$TOTunder90+=$under90;

	$Cpct = MathZDC($sales, ( ($USERcallsRAW[$i] - 0) - $DROP) )*100;
	$Cpct = round($Cpct, 2);
	$Cpct =	sprintf("%01.2f", $Cpct);
	$Cpct =	sprintf("%6s", $Cpct);

	$AVG_TIME = MathZDC($TALK_TIME, ($USERcallsRAW[$i] - 0));
	$AVG_TIME = round($AVG_TIME);
	$AVG_TIME = sec_convert($AVG_TIME,'H'); 
	$AVG_TIME =	sprintf("%5s", $AVG_TIME);

	$Spct = MathZDC($sales, ($USERcallsRAW[$i] - 0) )*100;
	$Spct = round($Spct, 2);
	$Spct =	sprintf("%01.2f", $Spct);
	$Spct =	sprintf("%6s", $Spct);

	$TOP = MathZDC($uTOP, $sales)*100;
	$TOP = round($TOP, 0);
	$TOP =	sprintf("%01.0f", $TOP);
	$TOP =	sprintf("%3s", $TOP);

	$BOT = MathZDC($uBOT, $sales)*100;
	$BOT = round($BOT, 0);
	$BOT =	sprintf("%01.0f", $BOT);
	$BOT =	sprintf("%3s", $BOT);

	$ppc = MathZDC($points, ( ($USERcallsRAW[$i] - 0) - $DROP) );
	$ppc = round($ppc, 2);
	$ppc =	sprintf("%01.2f", $ppc);
	$ppc =	sprintf("%4s", $ppc);

	if ($USERcalls[$i]>$max_calls) {$max_calls=$USERcalls[$i];}
	if ($A1>$max_sales) {$max_sales=$A1;}
	if ($DROP>$max_drops) {$max_drops=$DROP;}
	if ($OTHER>$max_other) {$max_other=$OTHER;}
	if ($sales>$max_sales2) {$max_sales2=$sales;}
	if ($Cpct>$max_conv_pct) {$max_conv_pct=$Cpct;}
	$graph_stats[$i][0]="$user[$i] - $full_name[$i]";
	$graph_stats[$i][1]=$USERcalls[$i];
	$graph_stats[$i][2]=$A1;
	$graph_stats[$i][3]=$DROP;
	$graph_stats[$i][4]=$OTHER;
	$graph_stats[$i][5]=$sales;
	$graph_stats[$i][6]=$Cpct;

	$A1 =	sprintf("%4s", $A1);
	$A2 =	sprintf("%4s", $A2);
	$A3 =	sprintf("%4s", $A3);
	$A4 =	sprintf("%4s", $A4);
	$A5 =	sprintf("%4s", $A5);
	$A6 =	sprintf("%4s", $A6);
	$A7 =	sprintf("%4s", $A7);
	$A8 =	sprintf("%4s", $A8);
	$A9 =	sprintf("%4s", $A9);
	$DROP =	sprintf("%4s", $DROP);
	$OTHER =	sprintf("%4s", $OTHER);
	$sales =	sprintf("%4s", $sales);
	$under90 =	sprintf("%4s", $under90);

	$ASCII_text.="<a name='ag".trim($userRAW[$i])."'/>| <a href='#ag".trim($userRAW[$i])."' onClick=\"ToggleAgentSpan('closer_agent_".trim($userRAW[$i])."')\">$user[$i] - $full_name[$i]</a> | $USERcalls[$i] | $sales | $under90 | $DROP | $AVG_TIME |$Cpct%|\n";
	$ASCII_text.="<span id='closer_agent_$userRAW[$i]' name='closer_agent_$userRAW[$i]' style='display: none;'>";
	$ASCII_text.=$closer_spans["$userRAW[$i]"];
	$ASCII_text.="</span>";

	$CSV_fronter_header3.="\n\"$user[$i] - $full_name[$i] XFER STATS\"\n";
	$CSV_fronter_header3.="\"FRONTER NAME\",\"PHONE\",\"TALK TIME\"\n";
	$CSV_fronter_header3.=$closer_CSV_span["$frow[5]"];

	$CSV_closer_lines.="\"$user[$i] - $full_name[$i]\",\"$USERcalls[$i]\",\"$A1\",\"$under90\",\"$DROP\",\"$AVG_TIME\",\"$Cpct%\"\n";

	$i++;
	}


$totCpct = MathZDC($TOTsales, ( ($TOTcalls - 0) - $totDROP) )*100;
$totCpct = round($totCpct, 2);
$totCpct =	sprintf("%01.2f", $totCpct);
$totCpct =	sprintf("%6s", $totCpct);
		
$totSpct = MathZDC($TOTsales, ($TOTcalls - 0) )*100;
$totSpct = round($totSpct, 2);
$totSpct =	sprintf("%01.2f", $totSpct);
$totSpct =	sprintf("%6s", $totSpct);
		
$TOT_AVG_TIME = MathZDC($TOT_TALK_TIME, ($TOTcalls - 0));
$TOT_AVG_TIME = round($TOT_AVG_TIME);
$TOT_AVG_TIME = sec_convert($TOT_AVG_TIME,'H'); 
$TOT_AVG_TIME =	sprintf("%5s", $TOT_AVG_TIME);

$ppc = MathZDC($totPOINTS, ( ($TOTcalls - $totOTHER) - $totDROP) );
$ppc = round($ppc, 2);
$ppc =	sprintf("%01.2f", $ppc);
$ppc =	sprintf("%4s", $ppc);
		
$TOP = MathZDC($totTOP, $TOTsales)*100;
$TOP = round($TOP, 0);
$TOP =	sprintf("%01.0f", $TOP);
$TOP =	sprintf("%3s", $TOP);

$BOT = MathZDC($totBOT, $TOTsales)*100;
$BOT = round($BOT, 0);
$BOT =	sprintf("%01.0f", $BOT);
$BOT =	sprintf("%3s", $BOT);

$TOTagents =	sprintf("%6s", $i);
$TOTcalls =		sprintf("%6s", $TOTcalls);
$totA1 =		sprintf("%5s", $totA1);
$totA2 =		sprintf("%5s", $totA2);
$totA3 =		sprintf("%5s", $totA3);
$totA4 =		sprintf("%5s", $totA4);
$totA5 =		sprintf("%5s", $totA5);
$totA6 =		sprintf("%5s", $totA6);
$totA7 =		sprintf("%5s", $totA7);
$totA8 =		sprintf("%5s", $totA8);
$totA9 =		sprintf("%5s", $totA9);
$totDROP =		sprintf("%5s", $totDROP);
$totOTHER =		sprintf("%5s", $totOTHER);
$TOTsales =		sprintf("%5s", $TOTsales);
$TOTunder90 =		sprintf("%4s", $TOTunder90);

$ASCII_text.="+--------------------------+--------+------+------+------+-------+-------+\n";
$ASCII_text.="| TOTAL CLOSERS:  $TOTagents   | $TOTcalls |$totA1 | $TOTunder90 |$totDROP | $TOT_AVG_TIME |$totCpct%|\n";
$ASCII_text.="+--------------------------+--------+------+------+------+-------+-------+\n";

$CSV_closer_footer.="\"TOTAL CLOSERS:  $TOTagents\",\"$TOTcalls\",\"$totA1\",\"$TOTunder90\",\"$totDROP\",\"$TOT_AVG_TIME\",\"$totCpct%\"\n";

	for ($d=0; $d<count($graph_stats); $d++) {
		if ($d==0) {$class=" first";} else if (($d+1)==count($graph_stats)) {$class=" last";} else {$class="";}
		$CALLS_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][1], $max_calls))."' height='16' />".$graph_stats[$d][1]."</td></tr>";
		$SALES_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][2], $max_sales))."' height='16' />".$graph_stats[$d][2]."</td></tr>";
		$DROP_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][3], $max_drops))."' height='16' />".$graph_stats[$d][3]."</td></tr>";
		$OTHER_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][4], $max_other))."' height='16' />".$graph_stats[$d][4]."</td></tr>";
		$SALES2_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][5], $max_sales2))."' height='16' />".$graph_stats[$d][5]."</td></tr>";
		$CONVPCT_graph.="  <tr><td class='chart_td$class'>".$graph_stats[$d][0]."</td><td nowrap class='chart_td value$class'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$graph_stats[$d][6], $max_conv_pct))."' height='16' />".$graph_stats[$d][6]."%</td></tr>";
	}
	$CALLS_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($TOTcalls)."</th></tr></table>";
	$SALES_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($totA1)."</th></tr></table>";
	$DROP_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($totDROP)."</th></tr></table>";
	$OTHER_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($totOTHER)."</th></tr></table>";
	$SALES2_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($TOTsales)."</th></tr></table>";
	$CONVPCT_graph.="<tr><th class='thgraph' scope='col'>TOTAL:</th><th class='thgraph' scope='col'>".trim($totCpct)."%</th></tr></table>";
	$JS_onload.="\tDrawCloserGraph('CALLS', '1');\n";
	$JS_text.="function DrawCloserGraph(graph, th_id) {\n";
	$JS_text.="	var CALLS_graph=\"$CALLS_graph\";\n";
	$JS_text.="	var SALES_graph=\"$SALES_graph\";\n";
	$JS_text.="	var DROP_graph=\"$DROP_graph\";\n";
	$JS_text.="	var OTHER_graph=\"$OTHER_graph\";\n";
	$JS_text.="	var SALES2_graph=\"$SALES2_graph\";\n";
	$JS_text.="	var CONVPCT_graph=\"$CONVPCT_graph\";\n";
	$JS_text.="\n";
	$JS_text.="	for (var i=1; i<=6; i++) {\n";
	$JS_text.="		var cellID=\"closergraph\"+i;\n";
	$JS_text.="		document.getElementById(cellID).style.backgroundColor='#DDDDDD';\n";
	$JS_text.="	}\n";
	$JS_text.="	var cellID=\"closergraph\"+th_id;\n";
	$JS_text.="	document.getElementById(cellID).style.backgroundColor='#999999';\n";
	$JS_text.="	var graph_to_display=eval(graph+\"_graph\");\n";
	$JS_text.="	document.getElementById('closer_graph').innerHTML=graph_to_display;\n";
	$JS_text.="}\n";
	$GRAPH_text.=$GRAPH;


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);

if ($report_display_type=="HTML") {
	$HTML_text.=$GRAPH_text;
	} 
else 
	{
	$HTML_text.=$ASCII_text;
	}

if ($DB) {$HTML_text.="\nRun Time: $RUNtime seconds|$db_source\n";}

$HTML_text.="</PRE>\n";
$HTML_text.="</TD></TR></TABLE>\n";

$HTML_text.="</BODY></HTML>\n";

if ($file_download > 0)
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "FCSTATS_$US$FILE_TIME.csv";
	if ($file_download==1) {
		$CSV_text=$CSV_fronter_header.$CSV_fronter_lines.$CSV_fronter_footer.$CSV_closer_header.$CSV_closer_lines.$CSV_closer_footer;
	} else if ($file_download==2) {
		$CSV_text=$CSV_fronter_header2;
	} else if ($file_download==3) {
		$CSV_text=$CSV_fronter_header3;
	}
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
	$JS_onload.="}\n";
	$JS_text.=$JS_onload;
	$JS_text.="</script>\n";

	echo $HTML_head;
	echo $JS_text;
	require("admin_header.php");
	echo $HTML_text;
	}

#$CSV_report=fopen("fcstats.csv", "w");
#$CSV_head=preg_replace('/\s+,/', ',', $CSV_fronter_header.$CSV_fronter_lines.$CSV_fronter_footer.$CSV_closer_header.$CSV_closer_lines.$CSV_closer_footer);
#fwrite($CSV_report, $CSV_head);
#fclose($CSV_report);




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

exit;

}


?>
