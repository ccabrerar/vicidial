<?php 
# AST_campaign_status_list_report.php
#
# This report is designed to show the breakdown by list_id of the calls and 
# their statuses for all lists within a campaign for a set time period
#
# Copyright (C) 2019  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 110815-2138 - First build
# 120224-0910 - Added HTML display option with bar graphs
# 130414-0129 - Added report logging
# 130425-2113 - Added status flag summaries and other formatting cleanup
# 130425-2353 - Fixed bug with subtracting unsigned columns in SQL
# 130621-2016 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0743 - Changed to mysqli PHP functions
# 140108-0749 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0848 - Finalized adding QXZ translation to all admin files
# 141230-1516 - Added code for on-the-fly language translations display
# 150516-1312 - Fixed Javascript element problem, Issue #857
# 151219-0107 - Added option for searching archived data
# 160227-1059 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170227-1720 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180807-1204 - Fixed log query issue
# 191013-0832 - Fixes for PHP7
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
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$report_name="Campaign Status List Report";
$NOW_DATE = date("Y-m-d");
if (!isset($group)) {$group=array();}
if (!isset($query_date_D)) {$query_date_D=$NOW_DATE;}
if (!isset($end_date_D)) {$end_date_D=$NOW_DATE;}
if (!isset($query_date_T)) {$query_date_T="00:00:00";}
if (!isset($end_date_T)) {$end_date_T="23:59:59";}

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
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_log", "vicidial_closer_log", "vicidial_agent_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_log_table=use_archive_table("vicidial_log");
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
	$vicidial_log_table="vicidial_log";
	$vicidial_closer_log_table="vicidial_closer_log";
	$vicidial_agent_log_table="vicidial_agent_log";
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
	$HTML_text.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns = $row[0];
$LOGallowed_reports =	$row[1];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match("/-ALL/",$LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
	}

$stmt="SELECT campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	if (preg_match("/-ALL/",$group_string) )
		{$group[$i] = $groups[$i];}
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

if ( (preg_match("/--ALL--/",$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	$group_SQL = preg_replace("/,\$/",'',$group_SQL);
	$group_SQL_str=$group_SQL;
	$group_SQL = "and campaign_id IN($group_SQL)";
	}

$query_date="$query_date_D $query_date_T";
$end_date="$end_date_D $end_date_T";

require("screen_colors.php");

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

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

$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_head.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HTML_head.="<script src='chart/Chart.js'></script>\n"; 
$HTML_head.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>$group_S\n";
$HTML_head.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$short_header=1;

#	require("admin_header.php");

$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";
$HTML_text.="<b>"._QXZ("$report_name")."</b> $NWB#campaign_status_list_report$NWE\n";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Dates").":<BR>";
$HTML_text.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=query_date ID=query_date VALUE=\"$query_date\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=end_date ID=end_date VALUE=\"$end_date\">\n";
$HTML_text.="<INPUT TYPE=TEXT NAME=query_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$query_date_D\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="  {\n";
$HTML_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="  }\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'query_date_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$HTML_text.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$end_date_D\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'end_date_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=end_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$end_date_T\">";

$HTML_text.="</TD><TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match("/--ALL--/",$group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
	{
	if (preg_match("/$groups[$o]\|/",$group_string)) {$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	else {$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";

$HTML_text.="</TD><TD VALIGN=TOP>&nbsp;\n";
$HTML_text.="</TD><TD VALIGN=TOP>\n";
$HTML_text.=_QXZ("Display as").":<BR>";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";

if ($archives_available=="Y") 
	{
	$HTML_text.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR><BR>\n";
	}

$HTML_text.="<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$HTML_text.="</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";

$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
$HTML_text.="<a href=\"$PHP_SELF?DB=$DB&query_date=$query_date&end_date=$end_date&query_date_D=$query_date_D&query_date_T=$query_date_T&end_date_D=$end_date_D&end_date_T=$end_date_T$groupQS&file_download=1&SUBMIT=$SUBMIT&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a> |";
$HTML_text.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$HTML_text.="</FONT>\n";
$HTML_text.="</TD></TR></TABLE>";
$HTML_text.="</FORM>\n\n";

$HTML_text.="<PRE>";
$i=0;
$group_string='|';
$group_ct = count($group);
$JS_text.="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

while($i < $group_ct)
	{
	$stmt="SELECT status, status_name, human_answered, sale, dnc, customer_contact, not_interested, unworkable, scheduled_callback, completed from vicidial_campaign_statuses where campaign_id='$group[$i]' UNION SELECT status, status_name, human_answered, sale, dnc, customer_contact, not_interested, unworkable, scheduled_callback, completed from vicidial_statuses order by status, status_name";
	$rslt=mysql_to_mysqli($stmt, $link);
	$status_ary=array();
	$HA_ary=array();
	$SALE_ary=array();
	$DNC_ary=array();
	$CC_ary=array();
	$NI_ary=array();
	$UW_ary=array();
	$SC_ary=array();
	$COMP_ary=array();
	while ($row=mysqli_fetch_row($rslt)) 
		{
		$status_ary[$row[0]] = " - $row[1]";
		$HA_ary[$row[0]] =		$row[2];
		$SALE_ary[$row[0]] =	$row[3];
		$DNC_ary[$row[0]] =		$row[4];
		$CC_ary[$row[0]] =		$row[5];
		$NI_ary[$row[0]] =		$row[6];
		$UW_ary[$row[0]] =		$row[7];
		$SC_ary[$row[0]] =		$row[8];
		$COMP_ary[$row[0]] =	$row[9];
		}
	$ASCII_text.="<B>"._QXZ("CAMPAIGN").": $group[$i]</B>\n";
	$GRAPH.="<B>"._QXZ("CAMPAIGN").": $group[$i]</B>\n";
	$CSV_text.="\""._QXZ("CAMPAIGN").": $group[$i]\"\n";

	$stmt="SELECT closer_campaigns from vicidial_campaigns where campaign_id='$group[$i]'";
	$rslt=mysql_to_mysqli($stmt, $link);
	if (mysqli_num_rows($rslt)>0) 
		{
		$row=mysqli_fetch_row($rslt);
		$inbound_groups=preg_replace('/ -$/', '', trim($row[0]));
		if (strlen($inbound_groups)>0) 
			{
			$inbound_groups=preg_replace("/\s/", "', '", $inbound_groups);
			$inbound_SQL="and ".$vicidial_closer_log_table.".campaign_id in ('$inbound_groups')";
			} 
		else 
			{
			$inbound_SQL="";
			}
		}

	$stmt="SELECT distinct list_id, list_name, active from vicidial_lists where campaign_id='$group[$i]' order by list_id, list_name asc";
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) 
		{
		$list_id=$row[0]; $list_name=$row[1]; $list_active=$row[2];
		$HA_count=0;
		$SALE_count=0;
		$DNC_count=0;
		$CC_count=0;
		$NI_count=0;
		$UW_count=0;
		$SC_count=0;
		$COMP_count=0;

		$dispo_ary=array();
		$ASCII_text.="<FONT SIZE=2><B>"._QXZ("List ID")." #$list_id: $list_name</B>\n";
		$CSV_text.="\""._QXZ("List ID")." #$list_id: $list_name\"\n";


		$stat_stmt="SELECT ".$vicidial_log_table.".status, ".$vicidial_log_table.".uniqueid, ".$vicidial_log_table.".length_in_sec as duration, cast(".$vicidial_agent_log_table.".talk_sec as signed)-cast(".$vicidial_agent_log_table.".dead_sec as signed) as handle_time from ".$vicidial_log_table." LEFT OUTER JOIN ".$vicidial_agent_log_table." on ".$vicidial_log_table.".lead_id=".$vicidial_agent_log_table.".lead_id and ".$vicidial_log_table.".uniqueid=".$vicidial_agent_log_table.".uniqueid and ".$vicidial_log_table.".user=".$vicidial_agent_log_table.".user where ".$vicidial_log_table.".call_date>='$query_date' and ".$vicidial_log_table.".call_date<='$end_date' and ".$vicidial_log_table.".list_id='$list_id' UNION SELECT ".$vicidial_closer_log_table.".status, ".$vicidial_closer_log_table.".uniqueid, ".$vicidial_closer_log_table.".length_in_sec as duration, cast(".$vicidial_agent_log_table.".talk_sec as signed)-cast(".$vicidial_agent_log_table.".dead_sec as signed) as handle_time from ".$vicidial_closer_log_table." LEFT OUTER JOIN ".$vicidial_agent_log_table." on ".$vicidial_closer_log_table.".lead_id=".$vicidial_agent_log_table.".lead_id and ".$vicidial_closer_log_table.".uniqueid=".$vicidial_agent_log_table.".uniqueid and ".$vicidial_closer_log_table.".user=".$vicidial_agent_log_table.".user where call_date>='$query_date' and call_date<='$end_date' and list_id='$list_id' order by status";
		if ($DB) {$HTML_text.="|$stat_stmt|\n";}
		# $ASCII_text.=$stat_stmt."\n";
		$stat_rslt=mysql_to_mysqli($stat_stmt, $link);
		if (mysqli_num_rows($stat_rslt)>0) 
			{

			$total_calls=0; $total_handle_time=0; $total_duration=0;

			$graph_stats=array();
			$max_calls=1;
			$max_duration=1;
			$max_handletime=1;
			
			$ASCII_text.="+------------------------------------------+--------+------------+-------------+\n";
			$ASCII_text.="| "._QXZ("DISPOSITION",40)." | "._QXZ("CALLS",6)." | "._QXZ("DURATION",10)." | "._QXZ("HANDLE TIME",11)." |\n";
			$ASCII_text.="+------------------------------------------+--------+------------+-------------+\n";
			$CSV_text.="\""._QXZ("DISPOSITION")."\",\""._QXZ("CALLS")."\",\""._QXZ("DURATION")."\",\""._QXZ("HANDLE TIME")."\"\n";
			while ($stat_row=mysqli_fetch_row($stat_rslt)) 
				{
				#if ($stat_row[0]=="") {$stat_row[0]="(no dispo)";}
				#$handle_time=sec_convert(($stat_row[4]-$stat_row[6]), 'H');
				#$duration=sec_convert(($stat_row[3]+$stat_row[4]+$stat_row[5]), 'H');
				#$total_handle_time+=($stat_row[4]-$stat_row[6]);
				#$total_duration+=($stat_row[3]+$stat_row[4]+$stat_row[5]);
				$dispo_ary["$stat_row[0]"][0]++;
				$dispo_ary["$stat_row[0]"][1]+=$stat_row[2];
				$dispo_ary["$stat_row[0]"][2]+=$stat_row[3];
				$total_calls++;
				$total_duration+=$stat_row[2];
				$total_handle_time+=$stat_row[3];
				if ($HA_ary["$stat_row[0]"]=="Y") {$HA_count++;}
				if ($SALE_ary["$stat_row[0]"]=="Y") {$SALE_count++;}
				if ($DNC_ary["$stat_row[0]"]=="Y") {$DNC_count++;}
				if ($CC_ary["$stat_row[0]"]=="Y") {$CC_count++;}
				if ($NI_ary["$stat_row[0]"]=="Y") {$NI_count++;}
				if ($UW_ary["$stat_row[0]"]=="Y") {$UW_count++;}
				if ($SC_ary["$stat_row[0]"]=="Y") {$SC_count++;}
				if ($COMP_ary["$stat_row[0]"]=="Y") {$COMP_count++;}
				}

			$d=0;
#			while (list($key, $val)=each($dispo_ary)) 
			foreach($dispo_ary as $key => $val)
				{
				$ASCII_text.="| ".sprintf("%-40s", $key.$status_ary[$key]);
				$ASCII_text.=" | ".sprintf("%6s", $val[0]);
				$ASCII_text.=" | ".sprintf("%10s", sec_convert($val[1], 'H'));
				$ASCII_text.=" | ".sprintf("%11s", sec_convert($val[2], 'H'))." |\n";
				$CSV_text.="\"".$key.$status_ary[$key]."\",\"$val[0]\",\"".sec_convert($val[1], 'H')."\",\"".sec_convert($val[2], 'H')."\"\n";

				if ($val[0]>$max_calls) {$max_calls=$val[0];}
				if ($val[1]>$max_duration) {$max_duration=$val[1];}
				if ($val[2]>$max_handletime) {$max_handletime=$val[2];}
				$graph_stats[$d][0]=$key.$status_ary[$key];
				$graph_stats[$d][1]=$val[0];
				$graph_stats[$d][2]=$val[1];
				$graph_stats[$d][3]=$val[2];
				$d++;
				}
			$ASCII_text.="+------------------------------------------+--------+------------+-------------+\n";
			$ASCII_text.="| "._QXZ("TOTALS:",40,"r");
			$ASCII_text.=" | ".sprintf("%6s", $total_calls);
			$ASCII_text.=" | ".sprintf("%10s", sec_convert($total_duration, 'H'));
			$ASCII_text.=" | ".sprintf("%11s", sec_convert($total_handle_time, 'H'))." |\n";
			$ASCII_text.="+------------------------------------------+--------+------------+-------------+\n";
			$CSV_text.="\""._QXZ("TOTALS").":\",\"$total_calls\",\"".sec_convert($total_duration, 'H')."\",\"".sec_convert($total_handle_time, 'H')."\"\n\n";


			$HA_percent =	sprintf("%6.2f", MathZDC(100*$HA_count, $total_calls)); while(strlen($HA_percent)>6) {$HA_percent = substr("$HA_percent", 0, -1);}
			$SALE_percent =	sprintf("%6.2f", MathZDC(100*$SALE_count, $total_calls)); while(strlen($SALE_percent)>6) {$SALE_percent = substr("$SALE_percent", 0, -1);}
			$DNC_percent =	sprintf("%6.2f", MathZDC(100*$DNC_count, $total_calls)); while(strlen($DNC_percent)>6) {$DNC_percent = substr("$DNC_percent", 0, -1);}
			$CC_percent =	sprintf("%6.2f", MathZDC(100*$CC_count, $total_calls)); while(strlen($CC_percent)>6) {$CC_percent = substr("$CC_percent", 0, -1);}
			$NI_percent =	sprintf("%6.2f", MathZDC(100*$NI_count, $total_calls)); while(strlen($NI_percent)>6) {$NI_percent = substr("$NI_percent", 0, -1);}
			$UW_percent =	sprintf("%6.2f", MathZDC(100*$UW_count, $total_calls)); while(strlen($UW_percent)>6) {$UW_percent = substr("$UW_percent", 0, -1);}
			$SC_percent =	sprintf("%6.2f", MathZDC(100*$SC_count, $total_calls)); while(strlen($SC_percent)>6) {$SC_percent = substr("$SC_percent", 0, -1);}
			$COMP_percent =	sprintf("%6.2f", MathZDC(100*$COMP_count, $total_calls)); while(strlen($COMP_percent)>6) {$COMP_percent = substr("$COMP_percent", 0, -1);}

			$HA_count =	sprintf("%9s", "$HA_count"); while(strlen($HA_count)>9) {$HA_count = substr("$HA_count", 0, -1);}
			$SALE_count =	sprintf("%9s", "$SALE_count"); while(strlen($SALE_count)>9) {$SALE_count = substr("$SALE_count", 0, -1);}
			$DNC_count =	sprintf("%9s", "$DNC_count"); while(strlen($DNC_count)>9) {$DNC_count = substr("$DNC_count", 0, -1);}
			$CC_count =	sprintf("%9s", "$CC_count"); while(strlen($CC_count)>9) {$CC_count = substr("$CC_count", 0, -1);}
			$NI_count =	sprintf("%9s", "$NI_count"); while(strlen($NI_count)>9) {$NI_count = substr("$NI_count", 0, -1);}
			$UW_count =	sprintf("%9s", "$UW_count"); while(strlen($UW_count)>9) {$UW_count = substr("$UW_count", 0, -1);}
			$SC_count =	sprintf("%9s", "$SC_count"); while(strlen($SC_count)>9) {$SC_count = substr("$SC_count", 0, -1);}
			$COMP_count =	sprintf("%9s", "$COMP_count"); while(strlen($COMP_count)>9) {$COMP_count = substr("$COMP_count", 0, -1);}

			if ($list_active=='Y') {$list_active = _QXZ('ACTIVE', 8);} else {$list_active = _QXZ('INACTIVE', 8);}
			$header_list_id = "$list_id - $list_name";
			$header_list_id =	sprintf("%-51s", $header_list_id); while(strlen($header_list_id)>51) {$header_list_id = substr("$header_list_id", 0, -1);}
			$header_list_count =	sprintf("%10s", $total_calls); while(strlen($header_list_count)>10) {$header_list_count = substr("$header_list_count", 0, -1);}
			$ASCII_text .= "\n";
			$ASCII_text .= "+--------------------------------------------------------------+\n";
			$ASCII_text .= "| $header_list_id $list_active |\n";
			$ASCII_text .= "| "._QXZ("TOTAL CALLS",14,"r").": $header_list_count                                   |\n";
			$ASCII_text .= "+--------------------------------------------------------------+\n";
			$ASCII_text .= "| "._QXZ("STATUS FLAGS BREAKDOWN",22).":  "._QXZ("(and % of total leads in the list)",34)."  |\n";
			$ASCII_text .= "|   "._QXZ("Human Answer:",19)." $HA_count    $HA_percent%                   |\n";
			$ASCII_text .= "|   "._QXZ("Sale:",19)." $SALE_count    $SALE_percent%                   |\n";
			$ASCII_text .= "|   "._QXZ("DNC:",19)." $DNC_count    $DNC_percent%                   |\n";
			$ASCII_text .= "|   "._QXZ("Customer Contact:",19)." $CC_count    $CC_percent%                   |\n";
			$ASCII_text .= "|   "._QXZ("Not Interested:",19)." $NI_count    $NI_percent%                   |\n";
			$ASCII_text .= "|   "._QXZ("Unworkable:",19)." $UW_count    $UW_percent%                   |\n";
			$ASCII_text .= "|   "._QXZ("Scheduled callbk:",19)." $SC_count    $SC_percent%                   |\n";
			$ASCII_text .= "|   "._QXZ("Completed:",19)." $COMP_count    $COMP_percent%                   |\n";
			$ASCII_text .= "+--------------------------------------------------------------+\n";

			# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
			$multigraph_text="";
			$graph_id++;
			$graph_array=array("CSL_CALLS".$list_id."data|1|CALLS|integer|", "CSL_DURATION".$list_id."data|2|DURATION|time|", "CSL_HANDLETIME".$list_id."data|3|HANDLE TIME|time|");
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
					$labels.="\"".preg_replace('/ +/', ' ', $graph_stats[$d][0])."\",";
					$data.="\"".$graph_stats[$d][$dataset_index]."\","; 
					$current_graph_total+=$graph_stats[$d][$dataset_index];
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
			$graph_title=""._QXZ("List ID")." #$list_id: $list_name";
			include("graphcanvas.inc");
			$HTML_head.=$HTML_graph_head;
			$GRAPH.=$graphCanvas;

			}
		else 
			{
			$ASCII_text.="<B>***"._QXZ("NO CALLS FOUND FROM")." $query_date "._QXZ("TO")." $end_date***</B>\n";
			$CSV_text.="\"***"._QXZ("NO CALLS FOUND FROM")." $query_date "._QXZ("TO")." $end_date***\"\n\n";
			$GRAPH.="<B>***"._QXZ("NO CALLS FOUND FROM")." $query_date "._QXZ("TO")." $end_date***</B>\n";
			}
		$ASCII_text.="</FONT>\n";
		$GRAPH.="</FONT>\n";
		}
	$i++;
	$ASCII_text.="\n\n";
	$GRAPH.="\n\n";
	$CSV_text.="\n\n";
	}
$JS_onload.="}\n";
if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
$JS_text.="</script>\n";

if ($report_display_type=="HTML")
	{
	$HTML_text.=$GRAPH.$JS_text;
	}
else
	{
	$HTML_text.=$ASCII_text;
	}

$HTML_text.="</PRE></BODY></HTML>";

if ($file_download>0) 
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_campaign_status_$US$FILE_TIME.csv";
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

	echo $HTML_head;
	require("admin_header.php");
	echo $HTML_text;
	flush();
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

exit;

?>
