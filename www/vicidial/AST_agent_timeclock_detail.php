<?php 
# AST_agent_timeclock_detail.php
# 
# Pulls all timeclock records for an agent
#
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 90602-2244 - First build
# 100301-1401 - Added popup date selector
# 100712-1324 - Added system setting slave server option and added user stats link dates
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 111104-1315 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 130414-0154 - Added report logging
# 130610-1024 - Finalized changing of all ereg instances to preg
# 130621-0810 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130704-0946 - Fixed issue #675
# 130901-0826 - Changed to mysqli PHP functions
# 140108-0750 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0905 - Finalized adding QXZ translation to all admin files
# 141230-1520 - Added code for on-the-fly language translations display
# 160227-1934 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170227-1726 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 191013-0833 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (strlen($shift)<2) {$shift='ALL';}
if (strlen($stage)<2) {$stage='ID';}

require("screen_colors.php");

$report_name = 'User Timeclock Detail Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
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
	$SSreport_default_format =		$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$user_group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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
#	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
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

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}

$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($user_group)) {$user_group = array();}
if (!isset($query_date)) {$query_date = "$NOW_DATE 00:00:00";}
if (!isset($end_date)) {$end_date = "$NOW_DATE 23:59:59";}
$query_dateURL = preg_replace('/\s/', '+', $query_date);
$end_dateURL = preg_replace('/\s/', '+', $end_date);

$query_dateARRAY = explode(" ",$query_date);
$query_date_D = $query_dateARRAY[0];
$query_date_T = $query_dateARRAY[1];
$end_dateARRAY = explode(" ",$end_date);
$end_date_D = $end_dateARRAY[0];
$end_date_T = $end_dateARRAY[1];

$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	$i++;
	}
$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$user_groups=array();
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	$i++;
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$group_SQL .= "'$group[$i]',";
	$groupQS .= "&group[]=$group[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_SQL = "and campaign_id IN($group_SQL)";
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
	{$user_group_SQL = "";}
else
	{
	$TCuser_group_SQL = $user_group_SQL;
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$user_group_SQL = "and vicidial_agent_log.user_group IN($user_group_SQL)";
	$TCuser_group_SQL = preg_replace('/,$/i', '',$TCuser_group_SQL);
	$TCuser_group_SQL = "and user_group IN($TCuser_group_SQL)";
	}

if ($DB) {echo "$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

$stmt="select distinct pause_code,pause_code_name from vicidial_pause_codes;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
$pause_code=array();
$pause_code_name=array();
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$pause_code[$i] =		"$row[0]";
	$pause_code_name[$i] =	"$row[1]";
	$i++;
	}

$LINKbase = "$PHP_SELF?query_date=$query_dateURL&end_date=$end_dateURL$groupQS$user_groupQS&shift=$shift&DB=$DB";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

if ($file_download < 1)
	{
	?>

	<HTML>
	<HEAD>
	<STYLE type="text/css">
	<!--
	   .yellow {color: white; background-color: yellow}
	   .red {color: white; background-color: red}
	   .blue {color: white; background-color: blue}
	   .purple {color: white; background-color: purple}
	-->
	 </STYLE>

	<script language="JavaScript" src="calendar_db.js"></script>
	<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
	<script language="JavaScript" src="help.js"></script>
	<link rel="stylesheet" href="calendar.css">
	<link rel="stylesheet" href="horizontalbargraph.css">
	<?php require("chart_button.php"); ?>
	<script src="chart/Chart.js"></script>
	<script language="JavaScript" src="vicidial_chart_functions.js"></script>

	<script language='Javascript'>
	function openNewWindow(url)
		{
window.open(url,"",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');
		}
	</script>
	<?php
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:21;'></div>";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\" id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";

	############################################################################
	##### BEGIN HTML form section
	############################################################################
	echo "<BR><BR>";
	echo "<b>"._QXZ("$report_name")."</b> $NWB#agent_timeclock_detail$NWE\n";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
	echo "<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Dates").":<BR>";
	echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
	echo "<INPUT TYPE=hidden NAME=query_date ID=query_date VALUE=\"$query_date\">\n";
	echo "<INPUT TYPE=hidden NAME=end_date ID=end_date VALUE=\"$end_date\">\n";
	echo "<INPUT TYPE=TEXT NAME=query_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$query_date_D\">";

	?>
	<script language="JavaScript">
	var o_cal = new tcal ({
		// form name
		'formname': 'vicidial_report',
		// input name
		'controlname': 'query_date_D'
	});
	o_cal.a_tpl.yearscroll = false;
	// o_cal.a_tpl.weekstart = 1; // Monday week start
	</script>
	<?php

	echo " &nbsp; <INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

	echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$end_date_D\">";

	?>
	<script language="JavaScript">
	var o_cal = new tcal ({
		// form name
		'formname': 'vicidial_report',
		// input name
		'controlname': 'end_date_D'
	});
	o_cal.a_tpl.yearscroll = false;
	// o_cal.a_tpl.weekstart = 1; // Monday week start
	</script>
	<?php

	echo " &nbsp; <INPUT TYPE=TEXT NAME=end_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$end_date_T\">";

	#	echo "</TD><TD VALIGN=TOP> Campaigns:<BR>";
	#	echo "<SELECT SIZE=5 NAME=group[] multiple>\n";
	#	if  (preg_match('/\-\-ALL\-\-/',$group_string))
	#		{echo "<option value=\"--ALL--\" selected>-- ALL CAMPAIGNS --</option>\n";}
	#	else
	#		{echo "<option value=\"--ALL--\">-- ALL CAMPAIGNS --</option>\n";}
	#	$o=0;
	#	while ($campaigns_to_print > $o)
	#	{
	#		if (preg_match("/$groups[$o]\|/i",$group_string)) {echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	#		  else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	#		$o++;
	#	}
	#	echo "</SELECT>\n";

	echo "</TD><TD VALIGN=TOP>"._QXZ("User Groups").":<BR>";
	echo "<SELECT SIZE=5 NAME=user_group[] multiple>\n";

	if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
		{echo "<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
	else
		{echo "<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
	$o=0;
	while ($user_groups_to_print > $o)
		{
		if  (preg_match("/\|$user_groups[$o]\|/i",$user_group_string)) {echo "<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
		  else {echo "<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
		$o++;
		}
	echo "</SELECT>\n";
	echo "</TD><TD VALIGN=TOP>";
	echo _QXZ("Display as").":&nbsp;&nbsp;&nbsp;<BR>";
	echo "<select name='report_display_type'>";
	if ($report_display_type) {echo "<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
	echo "<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
	echo "</TD><TD VALIGN=TOP>"._QXZ("Shift").":<BR>";
	echo "<SELECT SIZE=1 NAME=shift>\n";
	echo "<option selected value=\"$shift\">"._QXZ("$shift")."</option>\n";
	echo "<option value=\"\">--</option>\n";
	echo "<option value=\"AM\">"._QXZ("AM")."</option>\n";
	echo "<option value=\"PM\">"._QXZ("PM")."</option>\n";
	echo "<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
	echo "</SELECT><BR><BR>\n";

?>
	<SCRIPT LANGUAGE="JavaScript">

	function submit_form()
		{
		document.vicidial_report.end_date.value = document.vicidial_report.end_date_D.value + " " + document.vicidial_report.end_date_T.value;
		document.vicidial_report.query_date.value = document.vicidial_report.query_date_D.value + " " + document.vicidial_report.query_date_T.value;

		document.vicidial_report.submit();
		}

	</SCRIPT>

	<input <?php echo "style='background-color:#$SSbutton_color'"; ?> type=button value="<?php echo _QXZ("SUBMIT"); ?>" name=smt id=smt onClick="submit_form()">
<?php
	echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";

	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
	echo " <a href=\"$LINKbase&stage=$stage&file_download=1\">"._QXZ("DOWNLOAD")."</a> | \n";
	echo " <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
	echo "</FONT>\n";
	echo "</TD></TR></TABLE>";

	echo "</FORM>\n\n";
	############################################################################
	##### END HTML form section
	############################################################################
	

	echo "<span style=\"z-index:19;\" id=agent_status_stats>\n";
	echo "<PRE><FONT SIZE=2>\n";
	}

if (strlen($user_group[0]) < 1)
	{
	echo "\n";
	echo _QXZ("PLEASE SELECT A CAMPAIGN OR USER GROUP AND DATE-TIME ABOVE AND CLICK SUBMIT")."\n";
	echo " "._QXZ("NOTE: stats taken from shift specified")."\n";
	}

else
	{
	if ($shift == 'TEST') 
		{
		$time_BEGIN = "09:45:00";  
		$time_END = "10:00:00";
		}
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
	$query_date_BEGIN = "$query_date";   
	$query_date_END = "$end_date";

	if ($file_download < 1)
		{
		echo _QXZ("User Time-Clock Detail",42)." $NOW_TIME\n";

		echo _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}
	else
		{
		$file_output .= _QXZ("User Time-Clock Detail",42)." $NOW_TIME\n";
		$file_output .= _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}



	############################################################################
	##### BEGIN gathering information from the database section
	############################################################################

	### BEGIN gather user IDs and names for matching up later
	$stmt="select full_name,user,user_group from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user limit 100000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$users_to_print = mysqli_num_rows($rslt);
	$i=0;
	$ULname=array();
	$ULuser=array();
	$ULgroup=array();
	while ($i < $users_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$ULname[$i] =	$row[0];
		$ULuser[$i] =	$row[1];
		$ULgroup[$i] =	$row[2];
		$i++;
		}
	### END gather user IDs and names for matching up later


	### BEGIN gather timeclock time totals per agent
	$stmt="select user,sum(login_sec) from vicidial_timeclock_log where event IN('LOGIN','START') and event_date >= '$query_date_BEGIN' and event_date <= '$query_date_END' $TCuser_group_SQL group by user limit 10000000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$punches_to_print = mysqli_num_rows($rslt);
	$i=0;
	$TCuser=array();
	$TCtime=array();
	while ($i < $punches_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$TCuser[$i] =	$row[0];
		$TCtime[$i] =	$row[1];
		$uc++;
		$i++;
		}
	### END gather timeclock records per agent

	############################################################################
	##### END gathering information from the database section
	############################################################################




	##### BEGIN print the output to screen or put into file output variable
	if ($file_download < 1)
		{
		$ASCII_text.=_QXZ("AGENT TIME-CLOCK DETAIL").":\n";
		$ASCII_text.="+-----------------+----------+----------------------+------------+--------------------\n";
		$ASCII_text.="| <a href=\"$LINKbase&stage=NAME\">"._QXZ("USER NAME",15)."</a> | <a href=\"$LINKbase&stage=ID\">"._QXZ("ID",8)."</a> | <a href=\"$LINKbase&stage=GROUP\">"._QXZ("USER GROUP",20)."</a> | <a href=\"$LINKbase&stage=TCLOCK\">"._QXZ("TIME CLOCK",10)."</a> | "._QXZ("TIME CLOCK PUNCHES",18)."\n";
		$ASCII_text.="+-----------------+----------+----------------------+------------+--------------------\n";

		}
	else
		{
		$file_output .= _QXZ("USER,ID,GROUP,TIME CLOCK,TIME CLOCK PUNCHES")."\n";
		}
	##### END print the output to screen or put into file output variable





	############################################################################
	##### BEGIN formatting data for output section
	############################################################################

	##### BEGIN loop through each user formatting data for output
	$AUTOLOGOUTflag=0;
	$m=0;
	$max_time=1;
	$graph_stats=array();
	$q=0;
	$Suser=array();
	$Stime=array();
	$Sname=array();
	$Sgroup=array();
	$StimeTC=array();
	$TOPsort=array();
	$TOPsortTALLY=array();
	$TOPsorted_output=array();
	$TOPsorted_outputFILE=array();
	while ( ($m < $uc) and ($m < 50000) )
		{
		$TCdetail='';
		$rawTCdetail='';
		$n=0;
		$user_name_found=0;
		$RAWuser=$TCuser[$m];
		while ($n < $users_to_print)
			{
			if ($TCuser[$m] == "$ULuser[$n]")
				{
				$user_name_found++;
				$RAWname = $ULname[$n];
				$RAWgroup = $ULgroup[$n];
				$Sname[$m] = $ULname[$n];
				$Sgroup[$m] = $ULgroup[$n];
				}
			$n++;
			}
		if ($user_name_found < 1)
			{
			$RAWname =		_QXZ("NOT IN SYSTEM");
			$RAWgroup =		_QXZ("GROUP NOT IN SYSTEM");
			$Sname[$m] =	$RAWname;
			}

		$n=0;
		$punches_found=0;
		while ($n < $punches_to_print)
			{
			if ($RAWuser == "$TCuser[$n]")
				{
				$punches_found++;
				$RAWtimeTCsec =		$TCtime[$n];
				$TOTtimeTC =		($TOTtimeTC + $TCtime[$n]);
				$StimeTC[$m]=		sec_convert($TCtime[$n],'H'); 
				$RAWtimeTC =		$StimeTC[$m];
				if ($RAWtimeTCsec>$max_time) {$max_time=$RAWtimeTCsec;}
				$StimeTC[$m] =		sprintf("%10s", $StimeTC[$m]);
				}
			$n++;
			}
		if ($punches_found < 1)
			{
			$RAWtimeTCsec =		"0";
			$StimeTC[$m]=		"0:00"; 
			$RAWtimeTC =		$StimeTC[$m];
			$StimeTC[$m] =		sprintf("%10s", $StimeTC[$m]);
			}

		### Check if the user had an AUTOLOGOUT timeclock event during the time period
		$TCuserAUTOLOGOUT = ' ';
		$stmt="select event_epoch,event_date,login_sec,event,user_group from vicidial_timeclock_log where event_date <= '$query_date_END' and event_date >= '$query_date_BEGIN' and user='$TCuser[$m]' $TCuser_group_SQL order by event_date limit 10000000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$ASCII_text.="$stmt\n";}
		$TC_results = mysqli_num_rows($rslt);
		$k=0;
		while ($TC_results > $k)
			{
			$TCentryAUTOLOGOUT = ' ';
			$row=mysqli_fetch_row($rslt);
			$event_epoch =	$row[0];
			$event_date =	$row[1];
			$login_sec =	$row[2];
			$event =		$row[3];
			$user_group =	$row[4];
			$date_detail = explode(' ',$event_date);

			if ($event == 'AUTOLOGOUT')
				{
				$TCentryAUTOLOGOUT = '*';
				$TCuserAUTOLOGOUT =	'*';
				$AUTOLOGOUTflag++;
				}
			$TCdetail .= "$date_detail[1]$TCentryAUTOLOGOUT ";
			$rawTCdetail .= "$date_detail[1],";
			$k++;
			}

		if ($TC_results > 0)
			{$rawTCdetail = preg_replace('/,$/','',$rawTCdetail);}

		$Stime[$m] =	sprintf("%10s", $Stime[$m]); 
		$SORTname =	sprintf("%-20s", $Sname[$m]);
		$SORTgroup =	sprintf("%-20s", $Sgroup[$m]);
		$Sgroup[$m] =	sprintf("%-20s", $Sgroup[$m]); 
		$SORTgroup = preg_replace('/\s/', '0',$SORTgroup);
		$SORTname = preg_replace('/\s/', '0',$SORTname);

		if ($non_latin < 1)
			{
			$Sname[$m]=	sprintf("%-15s", $Sname[$m]); 
			while(strlen($Sname[$m])>15) {$Sname[$m] = substr("$Sname[$m]", 0, -1);}
			$Suser[$m] =		sprintf("%-8s", $TCuser[$m]);
			while(strlen($Suser[$m])>8) {$Suser[$m] = substr("$Suser[$m]", 0, -1);}
			}
		else
			{	
			$Sname[$m]=	sprintf("%-45s", $Sname[$m]); 
			while(mb_strlen($Sname[$m],'utf-8')>15) {$Sname[$m] = mb_substr("$Sname[$m]", 0, -1,'utf-8');}
			$Suser[$m] =	sprintf("%-24s", $TCuser[$m]);
			while(mb_strlen($Suser[$m],'utf-8')>8) {$Suser[$m] = mb_substr("$Suser[$m]", 0, -1,'utf-8');}
			}


		if ($file_download < 1)
			{
			$Toutput = "| $Sname[$m] | <a href=\"./user_stats.php?user=$RAWuser&begin_date=$query_date_D&end_date=$end_date_D\">$Suser[$m]</a> | $Sgroup[$m] | $StimeTC[$m]$TCuserAUTOLOGOUT| $TCdetail\n";
			$graph_stats[$q][0]=trim($Sname[$m])." - ".trim($Suser[$m])." / ".trim($Sgroup[$m]);
			$graph_stats[$q][1]="$RAWtimeTCsec";
			$graph_stats[$q][2]="$TCuserAUTOLOGOUT";
			$graph_stats[$q][3]="$TCdetail";
			$q++;
			}
		else
			{
			$fileToutput = "$RAWname,$RAWuser,$RAWgroup,$RAWtimeTC,$rawTCdetail\n";
			}

		$TOPsorted_output[$m] = $Toutput;
		$TOPsorted_outputFILE[$m] = $fileToutput;

		if ($stage == 'NAME')
			{
			$TOPsort[$m] =	'' . sprintf("%020s", $SORTname) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'ID')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWuser) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'TCLOCK')
			{
			$TOPsort[$m] =	'' . sprintf("%010s", $RAWtimeTCsec) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$RAWtimeTCsec;
			}
		if ($stage == 'GROUP')
			{
			$TOPsort[$m] =	'' . sprintf("%020s", $SORTgroup) . '-----' . $m . '-----' . sprintf("%020s", $RAWuser);
			$TOPsortTALLY[$m]=$SORTgroup;
			}
		if (!preg_match('/NAME|ID|TCLOCK|GROUP/',$stage))
			if ($file_download < 1)
				{
				$ASCII_text.="$Toutput";
				}
			else
				{$file_output .= "$fileToutput";}

		if ($TOPsortMAX < $TOPsortTALLY[$m]) {$TOPsortMAX = $TOPsortTALLY[$m];}

#		echo "$Suser[$m]|$Sname[$m]|$Swait[$m]|$Stalk[$m]|$Sdispo[$m]|$Spause[$m]|$Scalls[$m]\n";
		$m++;
		}
	##### END loop through each user formatting data for output

		$JS_text="<script language='Javascript'>\n";
		#########
		$graph_array=array("ATDdata|||time|");
		$graph_id++;
		$default_graph="bar"; # Graph that is initally displayed when page loads
		include("graph_color_schemas.inc"); 

		$graph_totals_array=array();
		$graph_totals_rawdata=array();
		for ($q=0; $q<count($graph_array); $q++) {
			$graph_info=explode("|", $graph_array[$q]); 
			$current_graph_total=0;
			$dataset_name=$graph_info[0];
			$dataset_index=$graph_info[1]; 
			$dataset_type=$graph_info[3];
			if ($q==0) {$preload_dataset=$dataset_name;}  # Used below to load initial graph

			$JS_text.="var $dataset_name = {\n";
			# $JS_text.="\ttype: \"\",\n";
			# $JS_text.="\t\tdata: {\n";
			$datasets="\t\tdatasets: [\n";
			$datasets.="\t\t\t{\n";
			$datasets.="\t\t\t\tlabel: \"\",\n";
			$datasets.="\t\t\t\tfill: false,\n";

			$labels="\t\tlabels:[";
			$data="\t\t\t\tdata: [";
			$graphConstantsA="\t\t\t\tbackgroundColor: [";
			$graphConstantsB="\t\t\t\thoverBackgroundColor: [";
			$graphConstantsC="\t\t\t\thoverBorderColor: [";
			for ($d=0; $d<count($graph_stats); $d++) {
				$labels.="\"".$graph_stats[$d][0]." ".$graph_stats[$d][2]."\",";
				$data.="\"".$graph_stats[$d][1]."\","; 
				$current_graph_total+=$graph_stats[$d][1];
				$bgcolor=$backgroundColor[($d%count($backgroundColor))];
				$hbgcolor=$hoverBackgroundColor[($d%count($hoverBackgroundColor))];
				$hbcolor=$hoverBorderColor[($d%count($hoverBorderColor))];
				$graphConstantsA.="\"$bgcolor\",";
				$graphConstantsB.="\"$hbgcolor\",";
				$graphConstantsC.="\"$hbcolor\",";
			}	
			$graphConstantsA.="],\n";
			$graphConstantsB.="],\n";
			$graphConstantsC.="],\n";
			$labels=preg_replace('/,$/', '', $labels)."],\n";
			$data=preg_replace('/,$/', '', $data)."],\n";
			
			$graph_totals_rawdata[$q]=$current_graph_total;
			switch($dataset_type) {
				case "time":
					$graph_totals_array[$q]="  <caption align=\"bottom\">"._QXZ("TOTAL")." - ".sec_convert($current_graph_total, 'H')." </caption>\n";
					$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = Math.round(data.datasets[0].data[tooltipItem.index]); return value.toHHMMSS();}}}, legend: { display: false }},";
					break;
				case "percent":
					$graph_totals_array[$q]="";
					$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = data.datasets[0].data[tooltipItem.index]; return value + '%';}}}, legend: { display: false }},";
					break;
				default:
					$graph_totals_array[$q]="  <caption align=\"bottom\">"._QXZ("TOTAL").": $current_graph_total</caption>\n";
					$chart_options="options: { legend: { display: false }},";
					break;
			}

			$datasets.=$data;
			$datasets.=$graphConstantsA.$graphConstantsB.$graphConstantsC.$graphConstants; # SEE TOP OF SCRIPT
			$datasets.="\t\t\t}\n";
			$datasets.="\t\t]\n";
			$datasets.="\t}\n";

			$JS_text.=$labels.$datasets;
			# $JS_text.="}\n";
			# $JS_text.="prepChart('$default_graph', $graph_id, $q, $dataset_name);\n";
			$JS_text.="var main_ctx = document.getElementById(\"CanvasID".$graph_id."_".$q."\");\n";
			$JS_text.="var GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: '$default_graph', $chart_options data: $dataset_name});\n";
		}

		$graph_count=count($graph_array);
		$graph_title=_QXZ("AGENT TIME-CLOCK DETAIL");
		include("graphcanvas.inc");
		echo $HTML_graph_head;
		$JS_text.="</script>\n";

	# SPECIAL EXCEPTION FOR THIS GRAPH
		$graphCanvas.="<table cellspacing=\"0\" cellpadding=\"1\" summary=\""._QXZ("TIME CLOCK PUNCHES")."\" class=\"horizontalgraph\" width=500>\n";
		$graphCanvas.="  <caption align=\"top\">"._QXZ("TIME CLOCK PUNCHES")."</caption>\n";
		$graphCanvas.="  <tr>\n";
		$graphCanvas.="	<th class=\"thgraph\" scope=\"col\">"._QXZ("USER/USER GROUP")."</th>\n";
		$graphCanvas.="	<th class=\"thgraph\" scope=\"col\">"._QXZ("TIME CLOCK PUNCHES")."</th>\n";
		$graphCanvas.="  </tr>\n";
		for ($q=0; $q<count($graph_stats); $q++) {
			if ($q==0) {$class=" first";} else if (($q+1)==count($graph_stats)) {$class=" last";} else {$class="";}
			$graphCanvas.="  <tr>\n";
			$graphCanvas.="	<th class=\"thgraph$class\" scope=\"col\">".$graph_stats[$q][0]."</th>\n";
			$graphCanvas.="	<th class=\"thgraph$class\" scope=\"col\">".$graph_stats[$q][3]."</th>\n";
			$graphCanvas.="  </tr>\n";
		}
		$graphCanvas.="</table>\n";
		
		$GRAPH_text.=$graphCanvas;


	$TOT_AGENTS = sprintf("%4s", $m);
	$k=$m;

	if ($DB) {$ASCII_text.=_QXZ("Done analyzing")."...   $TOTwait|$TOTtalk|$TOTdispo|$TOTpause|$TOTALtime|$TOTcalls|$uc|<BR>\n";}


	### BEGIN sort through output to display properly ###
	if ( (preg_match('/NAME|ID|TCLOCK|GROUP/',$stage)) and ($k > 0) )
		{
		if (preg_match('/ID/',$stage))
			{sort($TOPsort, SORT_NUMERIC);}
		if (preg_match('/TCLOCK/',$stage))
			{rsort($TOPsort, SORT_NUMERIC);}
		if (preg_match('/GROUP/',$stage))
			{sort($TOPsort, SORT_REGULAR);}
		if (preg_match('/NAME/',$stage))
			{sort($TOPsort, SORT_STRING);}

		$m=0;
		$sort_order=array();
		while ($m < $k)
			{
			$sort_split = explode("-----",$TOPsort[$m]);
			$i = $sort_split[1];
			$sort_order[$m] = "$i";
			if ($file_download < 1)
				{$ASCII_text.="$TOPsorted_output[$i]";}
			else
				{$file_output .= "$TOPsorted_outputFILE[$i]";}
			$m++;
			}
		}
	### END sort through output to display properly ###

	############################################################################
	##### END formatting data for output section
	############################################################################




	############################################################################
	##### BEGIN last line totals output section
	############################################################################

	### call function to calculate and print dialable leads
	$TOTtimeTC = sec_convert($TOTtimeTC,'H');

	$TOTtimeTC = sprintf("%11s", $TOTtimeTC);
	###### END LAST LINE TOTALS FORMATTING ##########



	if ($file_download < 1)
		{
		$ASCII_text.="+-----------------+----------+----------------------+------------+--------------------\n";
		$ASCII_text.="| "._QXZ("TOTALS",7,"r")." "._QXZ("AGENTS",13,"r").":$TOT_AGENTS |                      |$TOTtimeTC |\n";
		$ASCII_text.="+----------------------------+                      +------------+\n";
		if ($AUTOLOGOUTflag > 0)
			{
			$ASCII_text.="     * "._QXZ("denotes AUTOLOGOUT from timeclock")."\n";
			$GRAPH_text.="     * "._QXZ("denotes AUTOLOGOUT from timeclock")."\n";
			}
		$ASCII_text.="</PRE>";
		$GRAPH_text.="</PRE>";
		}
	else
		{
		$file_output .= _QXZ("TOTALS").",$TOT_AGENTS,,$TOTtimeTC\n";
		}
	}

	############################################################################
	##### END formatting data for output section
	############################################################################





if ($file_download > 0)
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AGENT_TIME$US$FILE_TIME.csv";

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$file_output";

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

if ($report_display_type=="HTML")
	{
	echo $GRAPH_text.$JS_text;
	}
else
	{
	echo $ASCII_text;
	}


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "<font size=1 color=white>$RUNtime|$db_source</font>\n";

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
