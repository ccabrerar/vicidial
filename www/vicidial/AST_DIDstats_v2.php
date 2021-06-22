<?php 
# AST_DIDstats_v2.php - DID sumary report
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 170828-2018 - First build, based on AST_DIDstats.php
# 170903-0941 - Added screen color settings
# 191013-0817 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["time_BEGIN"]))				{$time_BEGIN=$_GET["time_BEGIN"];}
	elseif (isset($_POST["time_BEGIN"]))	{$time_BEGIN=$_POST["time_BEGIN"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["time_END"]))				{$time_END=$_GET["time_END"];}
	elseif (isset($_POST["time_END"]))		{$time_END=$_POST["time_END"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if (strlen($shift)<2) {$shift='--';}

$report_name = 'Inbound DID Summary Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
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
	$SSreport_default_format =		$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$table_name="vicidial_did_log";
$archive_table_name=use_archive_table($table_name);
if ($archive_table_name!=$table_name) {$archives_available="Y";}

if ($search_archived_data) 
	{
	$vicidial_did_log_table=use_archive_table("vicidial_did_log");
	}
else
	{
	$vicidial_did_log_table="vicidial_did_log";
	}
#############

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
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (!isset($time_BEGIN)) {$time_BEGIN = "00:00:00";}
if (!isset($time_END)) {$time_END = "23:59:59";}

$stmt="select did_id,did_pattern,did_description from vicidial_inbound_dids $whereLOGadmin_viewable_groupsSQL order by did_pattern;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$groups_string='|';
$i=0;
$groups=array();
$group_patterns=array();
$group_names=array();
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =			$row[0];
	$group_patterns[$i] =	$row[1];
	$group_names[$i] =		$row[2];
	$groups_string .= "$groups[$i]|";
	$i++;
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	if ( (strlen($group[$i]) > 0) and (preg_match("/\|$group[$i]\|/",$groups_string)) )
		{
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$groupQS .= "&group[]=$group[$i]";
		}
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
if (strlen($group_SQL)<3) {$group_SQL="''";}


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

$AMP='&';
$QM='?';
$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='".$LOGfull_url."?DB=".$DB."&file_download=".$file_download."&query_date=".$query_date."&end_date=".$end_date."&shift=".$shift."&report_display_type=".$report_display_type."$groupQS', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

##### BEGIN Define colors and logo #####
require("screen_colors.php");

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: black; background-color: #99FF99}\n";
$HEADER.="   .red {color: black; background-color: #FF9999}\n";
$HEADER.="   .orange {color: black; background-color: #FFCC99}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<style type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.=".auraltext\n";
$HEADER.="	{\n";
$HEADER.="	position: absolute;\n";
$HEADER.="	font-size: 0;\n";
$HEADER.="	left: -1000px;\n";
$HEADER.="	}\n";
$HEADER.=".chart_td\n";
$HEADER.="	{background-image: url(images/gridline58.gif); background-repeat: repeat-x; background-position: left top; border-left: 1px solid #e5e5e5; border-right: 1px solid #e5e5e5; padding:0; border-bottom: 1px solid #e5e5e5; background-color:transparent;}\n";
$HEADER.="\n";
$HEADER.="-->\n";
$HEADER.="</style>\n";

$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HEADER.="<script src='chart/Chart.js'></script>\n"; 
$HEADER.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$short_header=1;

if ($DB > 0)
	{
	$MAIN.="<BR>\n";
	$MAIN.="$group_ct|$group_string|$group_SQL\n";
	$MAIN.="<BR>\n";
	$MAIN.="$shift|$query_date|$end_date\n";
	$MAIN.="<BR>\n";
	}

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#DIDsummary$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=POST name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD align=left valign=top>\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="function openNewWindow(url)\n";
$MAIN.="  {\n";
$MAIN.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$MAIN.="  }\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";
$MAIN.="<BR><INPUT TYPE=TEXT NAME=time_BEGIN SIZE=10 MAXLENGTH=8 VALUE=\"$time_BEGIN\">";


$MAIN.="<BR><div align='center'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("to")."</FONT></div><BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

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
$MAIN.="<BR><INPUT TYPE=TEXT NAME=time_END SIZE=10 MAXLENGTH=8 VALUE=\"$time_END\">";

$MAIN.="</TD><TD align=center valign=top>\n";
$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
$o=0;
while ($groups_to_print > $o)
	{
	if (preg_match("/\|$groups[$o]\|/",$group_string)) 
		{$MAIN.="<option selected value=\"$groups[$o]\">$group_patterns[$o] - $group_names[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$groups[$o]\">$group_patterns[$o] - $group_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n";
$MAIN.="</TD>";
$MAIN.="<TD align=left valign=top><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>\n";
$MAIN.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$MAIN.=_QXZ("Display as:")."</FONT><BR>";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
if ($archives_available=="Y") 
	{
	$MAIN.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR><BR>\n";
	}
$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF?query_date=$query_date&end_date=$end_date$groupQS&shift=$shift&DB=$DB&SUBMIT=$SUBMIT&file_download=1\">"._QXZ("DOWNLOAD")."</a> | <a href=\"./admin.php?ADD=3311&did_id=$group[0]\">"._QXZ("MODIFY")."</a> | <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$MAIN.="</TD></TR></TABLE>\n";
$MAIN.="</FORM>\n";

$MAIN.="<PRE><FONT SIZE=2>";

$JS_text="<script language='Javascript'>";

if (!$group)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT A DID AND DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";

	$SQdate_ARY =	explode(' ',$query_date_BEGIN);
	$SQday_ARY =	explode('-',$SQdate_ARY[0]);
	$SQtime_ARY =	explode(':',$SQdate_ARY[1]);
	$EQdate_ARY =	explode(' ',$query_date_END);
	$EQday_ARY =	explode('-',$EQdate_ARY[0]);
	$EQtime_ARY =	explode(':',$EQdate_ARY[1]);

	$SQepochDAY = mktime(0, 0, 0, $SQday_ARY[1], $SQday_ARY[2], $SQday_ARY[0]);
	$SQepoch = mktime($SQtime_ARY[0], $SQtime_ARY[1], $SQtime_ARY[2], $SQday_ARY[1], $SQday_ARY[2], $SQday_ARY[0]);
	$EQepoch = mktime($EQtime_ARY[0], $EQtime_ARY[1], $EQtime_ARY[2], $EQday_ARY[1], $EQday_ARY[2], $EQday_ARY[0]);

	$SQsec = ( ($SQtime_ARY[0] * 3600) + ($SQtime_ARY[1] * 60) + ($SQtime_ARY[2] * 1) );
	$EQsec = ( ($EQtime_ARY[0] * 3600) + ($EQtime_ARY[1] * 60) + ($EQtime_ARY[2] * 1) );

	$DURATIONsec = ($EQepoch - $SQepoch);
	$DURATIONday = intval( MathZDC($DURATIONsec, 86400) + 1 );

	if ( ($EQsec < $SQsec) and ($DURATIONday < 1) )
		{
		$EQepoch = ($SQepochDAY + ($EQsec + 86400) );
		$query_date_END = date("Y-m-d H:i:s", $EQepoch);
		$DURATIONday++;
		}

	$MAIN.=_QXZ("Inbound DID Report",40)." $NOW_TIME\n";
	$MAIN.="\n";
	$MAIN.=_QXZ("Time range")." $DURATIONday "._QXZ("days").": $query_date_BEGIN "._QXZ("to")." $query_date_END   $suffix\n\n";
	#$MAIN.="Time range day sec: $SQsec - $EQsec   Day range in epoch: $SQepoch - $EQepoch   Start: $SQepochDAY\n";

	$CSV_text1.="\""._QXZ("Inbound DID Report")."\",\"$NOW_TIME\"\n\n";
	$CSV_text1.="\""._QXZ("Time range")." $DURATIONday "._QXZ("days").":\",\"$query_date_BEGIN "._QXZ("to")." $query_date_END\",\"$suffix\"\n\n";

	$d=0;
	$daySTART=array();
	$dayEND=array();
	while ($d < $DURATIONday)
		{
		$dSQepoch = ($SQepoch + ($d * 86400) );
		$dEQepoch = ($SQepochDAY + ($EQsec + ($d * 86400) ) );

		if ($EQsec < $SQsec)
			{
			$dEQepoch = ($dEQepoch + 86400);
			}

		$daySTART[$d] = date("Y-m-d H:i:s", $dSQepoch);
		$dayEND[$d] = date("Y-m-d H:i:s", $dEQepoch);

		$d++;
		}

	### GRAB ALL RECORDS WITHIN RANGE FROM THE DATABASE ###
	$stmt="select UNIX_TIMESTAMP(call_date),extension,server_ip from ".$vicidial_did_log_table." where call_date >= '$query_date_BEGIN' and call_date <= '$query_date_END' and did_id IN($group_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$records_to_grab = mysqli_num_rows($rslt);
	$i=0;
	$extension[0]='';
	$did_server_ip[0]='';
	$dt=array();
	$ut=array();
	while ($i < $records_to_grab)
		{
		$row=mysqli_fetch_row($rslt);
		$dt[$i] =			0;
		$ut[$i] =			($row[0] - $SQepochDAY);
		$extension[$i] =	$row[1];
		while($ut[$i] >= 86400) 
			{
			$ut[$i] = ($ut[$i] - 86400);
			$dt[$i]++;
			}
		if ( ($ut[$i] <= $EQsec) and ($EQsec < $SQsec) )
			{
			$dt[$i] = ($dt[$i] - 1);
			}

		$i++;
		}

	### Find default route
	$default_route='';
	$stmt="select did_route from vicidial_inbound_dids where did_pattern='default';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$Drecords_to_grab = mysqli_num_rows($rslt);
	if ($Drecords_to_grab > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$default_route =	$row[0];
		}

	###################################################
	### TOTALS DID SUMMARY SECTION ###
	$qrtCALLS=array();
	$qrtCALLSavg=array();
	$qrtCALLSsec=array();
	if (strlen($extension[0]) > 0)
		{
		$ASCII_text.=_QXZ("DID Summary").":\n";
		$ASCII_text.="+---------+--------------------+--------------------------------+------------+------------+--------------------------------+--------------------------------+--------------------------------+--------------------------------+--------------------------------+---------------+\n";
		$ASCII_text.="| "._QXZ("DNIS",7)." | "._QXZ("DID",18)." | "._QXZ("SOURCE",30)." | "._QXZ("CALLS",10)." | "._QXZ("MINUTES",10)." | "._QXZ("COLUMN 1",30)." | "._QXZ("DESCRIPTION",30)." | "._QXZ("COLUMN 2",30)." | "._QXZ("COLUMN 3",30)." | "._QXZ("COLUMN 4",30)." | "._QXZ("ROUTE",13)." |\n";
		$ASCII_text.="+---------+--------------------+--------------------------------+------------+------------+--------------------------------+--------------------------------+--------------------------------+--------------------------------+--------------------------------+---------------+\n";

		$CSV_text1.="\""._QXZ("DID Summary").":\"\n";
		$CSV_text1.="\""._QXZ("DNIS")."\",\""._QXZ("DID")."\",\""._QXZ("SOURCE")."\",\""._QXZ("CALLS")."\",\""._QXZ("MINUTES")."\",\""._QXZ("COLUMN 1")."\",\""._QXZ("DESCRIPTION")."\",\""._QXZ("COLUMN 2")."\",\""._QXZ("COLUMN 3")."\",\""._QXZ("COLUMN 4")."\",\""._QXZ("ROUTE")."\"\n";

		$HTML_text.="</PRE>";

		$HTML_text.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
		$HTML_text.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML_text.="<th colspan='11'><font size='2'>"._QXZ("DID Summary").":</font></th>";
		$HTML_text.="</tr>\n";
		$HTML_text.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML_text.="<th><font size='2'>"._QXZ("DNIS")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("DID")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("SOURCE")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("CALLS")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("MINUTES")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("COLUMN 1")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("DESCRIPTION")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("COLUMN 2")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("COLUMN 3")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("COLUMN 4")."</font></th>";
		$HTML_text.="<th><font size='2'>"._QXZ("ROUTE")."</font></th>";
		$HTML_text.="</tr>\n";


		$stats_array = array_group_count($extension, 'desc');
		$stats_array_ct = count($stats_array);

		$d=0;
		$max_calls=1;
		$graph_stats=array();
		while ($d < $stats_array_ct)
			{
			$stat_description =		' *** default *** ';
			$stat_route =			$default_route;

			$stat_record_array = explode(' ',$stats_array[$d]);
			$stat_count = ($stat_record_array[0] + 0);
			$stat_pattern = $stat_record_array[1];
			if ($stat_count>$max_calls) {$max_calls=$stat_count;}
			$graph_stats[$d][0]=$stat_count;
			$graph_stats[$d][1]=$stat_pattern;

			$stmt="select did_description,did_route,did_id, did_carrier_description, custom_one, custom_two, custom_three, custom_four, custom_five from vicidial_inbound_dids where did_pattern='$stat_pattern';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$details_to_grab = mysqli_num_rows($rslt);
			$minutes=0;
			$dnis=substr($stat_pattern, -7);
			$description='';
			$carrier_desc='';
			$custom1='';
			$custom2='';
			$custom3='';
			$custom4='';
			$custom5='';
			if ($details_to_grab > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$stat_description =	$row[0];
				$stat_route =		$row[1];
				$did_id =			$row[2];
				$carrier_desc =		$row[3];
				$custom1 =			$row[4];
				$custom2 =			$row[5];
				$custom3 =			$row[6];
				$custom4 =			$row[7];
				$custom5 =			$row[8];

				$time_stmt="select sum(length_in_sec) from vicidial_closer_log vcl, vicidial_did_log vdl where vdl.did_id='$did_id' and vdl.call_date >= '$query_date_BEGIN' and vdl.call_date <= '$query_date_END' and vdl.uniqueid=vcl.uniqueid";
				$time_rslt=mysql_to_mysqli($time_stmt, $link);
				$time_row=mysqli_fetch_row($time_rslt);
				$minutes=$time_row[0];
				$grand_total_minutes+=$minutes;
				}
			$total_minutes=sprintf("%10.2f", ($minutes/60));

			$totCALLS =			($totCALLS + $stat_count);
			$dnis =	sprintf("%-7s", $dnis);
			$stat_pattern =		sprintf("%-18s", $stat_pattern);
			$stat_description =	sprintf("%-30s", $stat_description);
			$carrier_desc =	sprintf("%-30s", $carrier_desc);
			while (strlen($stat_description) > 30) {$stat_description = preg_replace('/.$/i', '',$stat_description);}
			$carrier_desc =	sprintf("%-30s", $carrier_desc);
			while (strlen($carrier_desc) > 30) {$carrier_desc = preg_replace('/.$/i', '',$carrier_desc);}
			$custom1 =	sprintf("%-30s", $custom1);
			while (strlen($custom1) > 30) {$custom1 = preg_replace('/.$/i', '',$custom1);}
			$custom2 =	sprintf("%-30s", $custom2);
			while (strlen($custom2) > 30) {$custom2 = preg_replace('/.$/i', '',$custom2);}
			$custom3 =	sprintf("%-30s", $custom3);
			while (strlen($custom3) > 30) {$custom3 = preg_replace('/.$/i', '',$custom3);}
			$custom4 =	sprintf("%-30s", $custom4);
			while (strlen($custom4) > 30) {$custom4 = preg_replace('/.$/i', '',$custom4);}
			$custom5 =	sprintf("%-30s", $custom5);
			while (strlen($custom5) > 30) {$custom5 = preg_replace('/.$/i', '',$custom5);}
			$stat_route =		sprintf("%-13s", $stat_route);
			$stat_count =		sprintf("%10s", $stat_count);

			$CSV_text1.="\"$dnis\",\"$stat_pattern\",\"$stat_description\",\"$stat_count\",\"$total_minutes\",\"$custom1\",\"$carrier_desc\",\"$custom2\",\"$custom3\",\"$custom4\",\"$stat_route\"\n";

			$stat_pattern = "<a href=\"admin.php?ADD=3311&did_pattern=$stat_pattern\">$stat_pattern</a>";

			$ASCII_text.="| $dnis | $stat_pattern | $stat_description | $stat_count | $total_minutes | $custom1 | $carrier_desc | $custom2 | $custom3 | $custom4 | $stat_route |\n";
			$d++;

			$HTML_text.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML_text.="<th><font size='2'>".trim($dnis)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($stat_pattern)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($stat_description)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($stat_count)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($total_minutes)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($custom1)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($carrier_desc)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($custom2)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($custom3)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($custom4)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($stat_route)."</font></th>";
			$HTML_text.="</tr>\n";

			}




			$FtotCALLS =	sprintf("%10s", $totCALLS);
			$FtotMINUTES =	sprintf("%10.2f", ($grand_total_minutes/60));

			$ASCII_text.="+---------+--------------------+--------------------------------+------------+------------+--------------------------------+--------------------------------+--------------------------------+--------------------------------+--------------------------------+---------------+\n";
			$ASCII_text.="| "._QXZ("TOTALS",61,"r")." | $FtotCALLS | ".sprintf("%10s", $FtotMINUTES)." | ".sprintf("%-178s", " ")." |\n";
			$ASCII_text.="+---------+--------------------+--------------------------------+------------+------------+--------------------------------+--------------------------------+--------------------------------+--------------------------------+--------------------------------+---------------+\n";
			$CSV_text1.="\"\",\"\",\""._QXZ("TOTALS")."\",\"$FtotCALLS\",\"$FtotMINUTES\"\n";


			$HTML_text.="<tr bgcolor='#".$SSstd_row1_background."'>";
			$HTML_text.="<td align='right' colspan='3'><font size='2'><B>"._QXZ("TOTALS").":</B></font></td>";
			$HTML_text.="<th><font size='2'>".trim($FtotCALLS)."</font></th>";
			$HTML_text.="<th><font size='2'>".trim($FtotMINUTES)."</font></th>";
			$HTML_text.="<th colspan='6'>&nbsp;</th>";
			$HTML_text.="</tr>\n";
			$HTML_text.="</table>\n";
	
		}

	if ($report_display_type=="HTML")
		{
		$MAIN.=$HTML_text;
		}
	else
		{
		$MAIN.=$ASCII_text;
		}


	## FORMAT OUTPUT ##
	$i=0;
	$hi_hour_count=0;
	$hi_hold_count=0;

	$qrtCALLS=array();
	$qrtCALLSavg=array();
	$qrtCALLSsec=array();
	while ($i < $TOTintervals)
		{
		$qrtCALLSavg[$i] = MathZDC($qrtCALLSsec[$i], $qrtCALLS[$i]);

		if ($qrtCALLS[$i] > $hi_hour_count) 
			{$hi_hour_count = $qrtCALLS[$i];}

		$i++;
		}

	$hour_multiplier = MathZDC(70, $hi_hour_count);
	$hold_multiplier = MathZDC(70, $hi_hold_count);

	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
	$MAIN.="</PRE>\n";
	$MAIN.="</TD></TR></TABLE>\n";

	$MAIN.="</BODY></HTML>\n";
	}

	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_DIDstats_$US$FILE_TIME.csv";
		$CSV_var="CSV_text".$file_download;
		$CSV_text=preg_replace('/^ +/', '', $$CSV_var);
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
	} else {
		echo $HEADER;
		require("admin_header.php");


		echo $MAIN;
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
