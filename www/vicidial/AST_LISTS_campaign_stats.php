<?php 
# AST_LISTS_campaign_stats.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This is a list inventory report, not a calling report. This report will show
# statistics for all of the lists in the selected campaigns
#
# CHANGES
# 100916-0928 - First build
# 110703-1815 - Added download option
# 120224-0910 - Added HTML display option with bar graphs
# 120524-1754 - Fixed status categories issue
# 130221-1928 - small change to remove nested SQL query
# 130414-0127 - Added report logging
# 130424-2039 - Added lines for new status categories of scheduled callbacks and completed
# 130610-1001 - Finalized changing of all ereg instances to preg
# 130621-0735 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2025 - Changed to mysqli PHP functions
# 130926-0720 - Added link to lists view of report
# 140108-0715 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0828 - Finalized adding QXZ translation to all admin files
# 141230-1444 - Added code for on-the-fly language translations display
# 150516-1302 - Fixed Javascript element problem, Issue #857
# 151229-2009 - Added archive search option
# 160227-1043 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170227-1719 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180507-2315 - Added new help display
# 191013-0819 - Fixes for PHP7
#

$startMS = microtime();

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["report_display_type"]))				{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$report_name = 'Lists Campaign Statuses Report';
$db_source = 'M';

$JS_text="<script language='Javascript'>\n";
$JS_text.="function openNewWindow(url)\n";
$JS_text.="  {\n";
$JS_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$JS_text.="  }\n";
$JS_onload="onload = function() {\n";

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
$table_name="vicidial_list";
$archive_table_name=use_archive_table($table_name);
if ($archive_table_name!=$table_name) {$archives_available="Y";}

if ($search_archived_data) 
	{
	$vicidial_list_table=use_archive_table("vicidial_list");
	}
else
	{
	$vicidial_list_table="vicidial_list";
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
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
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
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
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

$stmt="select campaign_id,campaign_name from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
$group_names=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =		$row[0];
	$group_names[$i] =	$row[1];
	if (preg_match('/\-ALL/',$group_string) )
		{$group[$i] = $groups[$i];}
	$i++;
	}

$rollover_groups_count=0;
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
if (strlen($group_drop_SQL) < 2)
	{$group_drop_SQL = "''";}
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) or (strlen($group_string) < 2) )
	{
	$group_SQL = "$LOGallowed_campaignsSQL";
	}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$both_group_SQLand = "and ( (campaign_id IN($group_drop_SQL)) or (campaign_id IN($group_SQL)) )";
	$both_group_SQL = "where ( (campaign_id IN($group_drop_SQL)) or (campaign_id IN($group_SQL)) )";
	$group_SQLand = "and campaign_id IN($group_SQL)";
	$group_SQL = "where campaign_id IN($group_SQL)";
	}

# Get lists to query to avoid using a nested query
$lists_id_str="";
$list_stmt="SELECT list_id from vicidial_lists where active IN('Y','N') $group_SQLand";
if ($DB) {echo "$list_stmt\n";}
$list_rslt=mysql_to_mysqli($list_stmt, $link);
$lists_id_str="'',";
while ($lrow=mysqli_fetch_row($list_rslt)) {
	$lists_id_str.="'$lrow[0]',";
}
$lists_id_str=substr($lists_id_str,0,-1);

$stmt="select vsc_id,vsc_name from vicidial_status_categories;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statcats_to_print = mysqli_num_rows($rslt);
$i=0;
$vsc_id=array();
$vsc_name=array();
$vsc_count=array();
while ($i < $statcats_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$vsc_id[$i] =	$row[0];
	$vsc_name[$i] =	$row[1];

	$category_statuses="'',";
	$status_stmt="select distinct status from vicidial_statuses where category='$row[0]' UNION select distinct status from vicidial_campaign_statuses where category='$row[0]' $group_SQLand";
	if ($DB) {echo "$status_stmt\n";}
	$status_rslt=mysql_to_mysqli($status_stmt, $link);
	while ($status_row=mysqli_fetch_row($status_rslt)) 
		{
		$category_statuses.="'$status_row[0]',";
        }
	$category_statuses=substr($category_statuses, 0, -1);

	$category_stmt="select count(*) from ".$vicidial_list_table." where status in ($category_statuses) and list_id IN($lists_id_str)";
	if ($DB) {echo "$category_stmt\n";}
	$category_rslt=mysql_to_mysqli($category_stmt, $link);
	$category_row=mysqli_fetch_row($category_rslt);
	$vsc_count[$i] = $category_row[0];
	$i++;
	}



### BEGIN gather all statuses that are in status flags  ###
$human_answered_statuses='';
$sale_statuses='';
$dnc_statuses='';
$customer_contact_statuses='';
$not_interested_statuses='';
$unworkable_statuses='';
$stmt="select status,human_answered,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,status_name from vicidial_statuses;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
$statname_list=array();
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$temp_status = $row[0];
	$statname_list["$temp_status"] = "$row[9]";
	if ($row[1]=='Y') {$human_answered_statuses .= "'$temp_status',";}
	if ($row[2]=='Y') {$sale_statuses .= "'$temp_status',";}
	if ($row[3]=='Y') {$dnc_statuses .= "'$temp_status',";}
	if ($row[4]=='Y') {$customer_contact_statuses .= "'$temp_status',";}
	if ($row[5]=='Y') {$not_interested_statuses .= "'$temp_status',";}
	if ($row[6]=='Y') {$unworkable_statuses .= "'$temp_status',";}
	if ($row[7]=='Y') {$scheduled_callback_statuses .= "'$temp_status',";}
	if ($row[8]=='Y') {$completed_statuses .= "'$temp_status',";}
	$i++;
	}
$stmt="select status,human_answered,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,status_name from vicidial_campaign_statuses where selectable IN('Y','N') $group_SQLand;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$temp_status = $row[0];
	$statname_list["$temp_status"] = "$row[9]";
	if ( ($row[1]=='Y') and (!preg_match("/'$temp_status'/",$human_answered_statuses)) ) {$human_answered_statuses .= "'$temp_status',";}
	if ($row[2]=='Y') {$sale_statuses .= "'$temp_status',";}
	if ($row[3]=='Y') {$dnc_statuses .= "'$temp_status',";}
	if ($row[4]=='Y') {$customer_contact_statuses .= "'$temp_status',";}
	if ($row[5]=='Y') {$not_interested_statuses .= "'$temp_status',";}
	if ($row[6]=='Y') {$unworkable_statuses .= "'$temp_status',";}
	if ($row[7]=='Y') {$scheduled_callback_statuses .= "'$temp_status',";}
	if ($row[8]=='Y') {$completed_statuses .= "'$temp_status',";}
	$i++;
	}
if (strlen($human_answered_statuses)>2)		{$human_answered_statuses = substr("$human_answered_statuses", 0, -1);}
else {$human_answered_statuses="''";}
if (strlen($sale_statuses)>2)				{$sale_statuses = substr("$sale_statuses", 0, -1);}
else {$sale_statuses="''";}
if (strlen($dnc_statuses)>2)				{$dnc_statuses = substr("$dnc_statuses", 0, -1);}
else {$dnc_statuses="''";}
if (strlen($customer_contact_statuses)>2)	{$customer_contact_statuses = substr("$customer_contact_statuses", 0, -1);}
else {$customer_contact_statuses="''";}
if (strlen($not_interested_statuses)>2)		{$not_interested_statuses = substr("$not_interested_statuses", 0, -1);}
else {$not_interested_statuses="''";}
if (strlen($unworkable_statuses)>2)			{$unworkable_statuses = substr("$unworkable_statuses", 0, -1);}
else {$unworkable_statuses="''";}
if (strlen($scheduled_callback_statuses)>2)			{$scheduled_callback_statuses = substr("$scheduled_callback_statuses", 0, -1);}
else {$scheduled_callback_statuses="''";}
if (strlen($completed_statuses)>2)			{$completed_statuses = substr("$completed_statuses", 0, -1);}
else {$completed_statuses="''";}

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HEADER.="<script src='chart/Chart.js'></script>\n"; 
$HEADER.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#LISTS_campaign_stats$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP>";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";

$MAIN.="</TD><TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$group_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
	{
	if (preg_match("/$groups[$o]\|/i",$group_string)) {$MAIN.="<option selected value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";}
	  else {$MAIN.="<option value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>\n<BR>\n<a href=\"AST_LISTS_stats.php?DB=$DB\">"._QXZ("SWITCH TO LISTS")."</a>";
$MAIN.="</TD><TD VALIGN=TOP>";
$MAIN.=_QXZ("Display as").":<BR/>";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>&nbsp; ";

if ($archives_available=="Y") 
	{
	$MAIN.="<BR><BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

$MAIN.="<BR><BR><INPUT style='background-color:#$SSbutton_color' type=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";
$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
if (strlen($group[0]) > 1)
	{
	$MAIN.=" <a href=\"./admin.php?ADD=34&campaign_id=$group[0]\">"._QXZ("MODIFY")."</a> | \n";
	$MAIN.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
	}
else
	{
	$MAIN.=" <a href=\"./admin.php?ADD=10\">"._QXZ("CAMPAIGNS")."</a> | \n";
	$MAIN.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
	}
$MAIN.="</TD></TR></TABLE>";
$MAIN.="</FORM>\n\n";

$MAIN.="<PRE><FONT SIZE=2>\n\n";


if (strlen($group[0]) < 1)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT A CAMPAIGN AND DATE ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$OUToutput = '';
	$OUToutput .= _QXZ("Lists Campaign Status Stats",55)." $NOW_TIME\n";

	$OUToutput .= "\n";

	##############################
	#########  LIST ID BREAKDOWN STATS

	$TOTALleads = 0;

	$OUToutput .= "\n";
	$OUToutput .= "---------- "._QXZ("LIST ID SUMMARY",19)." <a href=\"$PHP_SELF?DB=$DB$groupQS&SUBMIT=$SUBMIT&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
	$OUToutput .= "+------------------------------------------+------------+----------+\n";
	$OUToutput .= "| "._QXZ("LIST",40)." | "._QXZ("LEADS",10)." | "._QXZ("ACTIVE",8)." |\n";
	$OUToutput .= "+------------------------------------------+------------+----------+\n";

	$CSV_text1.="\""._QXZ("LIST ID SUMMARY")."\"\n";
	$CSV_text1.="\""._QXZ("LIST")."\",\""._QXZ("LEADS")."\",\""._QXZ("ACTIVE")."\"\n";

	$max_calls=1; 
	$graph_stats=array();
	$lists_id_str="";
	$list_stmt="SELECT list_id from vicidial_lists where active IN('Y','N') $group_SQLand";
	$list_rslt=mysql_to_mysqli($list_stmt, $link);
	while ($lrow=mysqli_fetch_row($list_rslt)) {
		$lists_id_str.="'$lrow[0]',";
	}
	$lists_id_str=substr($lists_id_str,0,-1);

	$stmt="select count(*),list_id from ".$vicidial_list_table." where list_id IN($lists_id_str) group by list_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$listids_to_print = mysqli_num_rows($rslt);
	$i=0;
	$LISTIDcalls=array();
	$LISTIDlists=array();
	while ($i < $listids_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTIDcalls[$i] =	$row[0];
		$LISTIDlists[$i] =	$row[1];
		$list_id_SQL .=		"'$row[1]',";
		if ($row[0]>$max_calls) {$max_calls=$row[0];}
		$graph_stats[$i][0]=$row[0];
		$graph_stats[$i][1]=$row[1];
		$i++;
		}
	if (strlen($list_id_SQL)>2)		{$list_id_SQL = substr("$list_id_SQL", 0, -1);}
	else {$list_id_SQL="''";}

	$i=0;
	$LISTIDlist_names=array();
	$LISTIDlist_active=array();
	while ($i < $listids_to_print)
		{
		$stmt="select list_name,active from vicidial_lists where list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$list_name_to_print = mysqli_num_rows($rslt);
		if ($list_name_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$LISTIDlist_names[$i] =	$row[0];
			$graph_stats[$i][1].=" - $row[0]";
			if ($row[1]=='Y')
				{$LISTIDlist_active[$i] = _QXZ("ACTIVE",8); $graph_stats[$i][1].=" ("._QXZ("ACTIVE").")";}
			else
				{$LISTIDlist_active[$i] = _QXZ("INACTIVE",8); $graph_stats[$i][1].=" ("._QXZ("INACTIVE").")";}
			}

		$TOTALleads = ($TOTALleads + $LISTIDcalls[$i]);

		$LISTIDcount =	sprintf("%10s", $LISTIDcalls[$i]);while(strlen($LISTIDcount)>10) {$LISTIDcount = substr("$LISTIDcount", 0, -1);}
		$LISTIDname =	sprintf("%-40s", "$LISTIDlists[$i] - $LISTIDlist_names[$i]");while(strlen($LISTIDname)>40) {$LISTIDname = substr("$LISTIDname", 0, -1);}

		$OUToutput .= "| $LISTIDname | $LISTIDcount | $LISTIDlist_active[$i] |\n";
		$CSV_text1.="\"$LISTIDname\",\"$LISTIDcount\",\"$LISTIDlist_active[$i]\"\n";

		$i++;
		}

	$TOTALleads =		sprintf("%10s", $TOTALleads);

	$OUToutput .= "+------------------------------------------+------------+----------+\n";
	$OUToutput .= "| "._QXZ("TOTAL:",40)." | $TOTALleads |\n";
	$OUToutput .= "+------------------------------------------+------------+\n";
	$CSV_text1.="\""._QXZ("TOTAL")."\",\"$TOTALleads\"\n";

	#########
	$graph_array=array("LID_SUMMARYdata|||integer|");
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
			$labels.="\"".$graph_stats[$d][1]."\",";
			$data.="\"".$graph_stats[$d][0]."\",";
			$current_graph_total+=$graph_stats[$d][0];
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
	$graph_title=_QXZ("LIST ID SUMMARY");
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH.=$graphCanvas;


	##############################
	#########  STATUS FLAGS STATS

	$HA_count=0;
	$HA_percent=0;
	$SALE_count=0;
	$SALE_percent=0;
	$DNC_count=0;
	$DNC_percent=0;
	$CC_count=0;
	$CC_percent=0;
	$NI_count=0;
	$NI_percent=0;
	$UW_count=0;
	$UW_percent=0;
	$SC_count=0;
	$SC_percent=0;
	$COMP_count=0;
	$COMP_percent=0;

	$max_calls=1; $graph_stats=array();
	$stmt="select count(*) from ".$vicidial_list_table." where status IN($human_answered_statuses) and list_id IN($list_id_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$HA_results = mysqli_num_rows($rslt);
	if ($HA_results > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$HA_count = $row[0];
		$flag_count+=$row[0];
		if ($HA_count>$max_calls) {$max_calls=$HA_count;}
		$HA_percent = ( MathZDC($HA_count, $TOTALleads) * 100);
		}
	$stmt="select count(*) from ".$vicidial_list_table." where status IN($sale_statuses) and list_id IN($list_id_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$SALE_results = mysqli_num_rows($rslt);
	if ($SALE_results > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$SALE_count = $row[0];
		$flag_count+=$row[0];
		if ($SALE_count>$max_calls) {$max_calls=$SALE_count;}
		$SALE_percent = ( MathZDC($SALE_count, $TOTALleads) * 100);
		}
	$stmt="select count(*) from ".$vicidial_list_table." where status IN($dnc_statuses) and list_id IN($list_id_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$DNC_results = mysqli_num_rows($rslt);
	if ($DNC_results > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$DNC_count = $row[0];
		$flag_count+=$row[0];
		if ($DNC_count>$max_calls) {$max_calls=$DNC_count;}
		$DNC_percent = ( MathZDC($DNC_count, $TOTALleads) * 100);
		}
	$stmt="select count(*) from ".$vicidial_list_table." where status IN($customer_contact_statuses) and list_id IN($list_id_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$CC_results = mysqli_num_rows($rslt);
	if ($CC_results > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$CC_count = $row[0];
		$flag_count+=$row[0];
		if ($C_count>$max_calls) {$max_calls=$CC_count;}
		$CC_percent = ( MathZDC($CC_count, $TOTALleads) * 100);
		}
	$stmt="select count(*) from ".$vicidial_list_table." where status IN($not_interested_statuses) and list_id IN($list_id_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$NI_results = mysqli_num_rows($rslt);
	if ($NI_results > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$NI_count = $row[0];
		$flag_count+=$row[0];
		if ($NI_count>$max_calls) {$max_calls=$NI_count;}
		$NI_percent = ( MathZDC($NI_count, $TOTALleads) * 100);
		}
	$stmt="select count(*) from ".$vicidial_list_table." where status IN($unworkable_statuses) and list_id IN($list_id_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$UW_results = mysqli_num_rows($rslt);
	if ($UW_results > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$UW_count = $row[0];
		$flag_count+=$row[0];
		if ($UW_count>$max_calls) {$max_calls=$UW_count;}
		$UW_percent = ( MathZDC($UW_count, $TOTALleads) * 100);
		}
	$stmt="select count(*) from ".$vicidial_list_table." where status IN($scheduled_callback_statuses) and list_id IN($list_id_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$SC_results = mysqli_num_rows($rslt);
	if ($SC_results > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$SC_count = $row[0];
		$flag_count+=$row[0];
		if ($SC_count>$max_calls) {$max_calls=$SC_count;}
		$SC_percent = ( MathZDC($SC_count, $TOTALleads) * 100);
		}
	$stmt="select count(*) from ".$vicidial_list_table." where status IN($completed_statuses) and list_id IN($list_id_SQL);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$COMP_results = mysqli_num_rows($rslt);
	if ($COMP_results > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$COMP_count = $row[0];
		$flag_count+=$row[0];
		if ($COMP_count>$max_calls) {$max_calls=$COMP_count;}
		$COMP_percent = ( MathZDC($COMP_count, $TOTALleads) * 100);
		}

	$HA_percent =	sprintf("%6.2f", "$HA_percent"); while(strlen($HA_percent)>6) {$HA_percent = substr("$HA_percent", 0, -1);}
	$SALE_percent =	sprintf("%6.2f", "$SALE_percent"); while(strlen($SALE_percent)>6) {$SALE_percent = substr("$SALE_percent", 0, -1);}
	$DNC_percent =	sprintf("%6.2f", "$DNC_percent"); while(strlen($DNC_percent)>6) {$DNC_percent = substr("$DNC_percent", 0, -1);}
	$CC_percent =	sprintf("%6.2f", "$CC_percent"); while(strlen($CC_percent)>6) {$CC_percent = substr("$CC_percent", 0, -1);}
	$NI_percent =	sprintf("%6.2f", "$NI_percent"); while(strlen($NI_percent)>6) {$NI_percent = substr("$NI_percent", 0, -1);}
	$UW_percent =	sprintf("%6.2f", "$UW_percent"); while(strlen($UW_percent)>6) {$UW_percent = substr("$UW_percent", 0, -1);}
	$SC_percent =	sprintf("%6.2f", "$SC_percent"); while(strlen($SC_percent)>6) {$SC_percent = substr("$SC_percent", 0, -1);}
	$COMP_percent =	sprintf("%6.2f", "$COMP_percent"); while(strlen($COMP_percent)>6) {$COMP_percent = substr("$COMP_percent", 0, -1);}

	$HA_count =	sprintf("%10s", "$HA_count"); while(strlen($HA_count)>10) {$HA_count = substr("$HA_count", 0, -1);}
	$SALE_count =	sprintf("%10s", "$SALE_count"); while(strlen($SALE_count)>10) {$SALE_count = substr("$SALE_count", 0, -1);}
	$DNC_count =	sprintf("%10s", "$DNC_count"); while(strlen($DNC_count)>10) {$DNC_count = substr("$DNC_count", 0, -1);}
	$CC_count =	sprintf("%10s", "$CC_count"); while(strlen($CC_count)>10) {$CC_count = substr("$CC_count", 0, -1);}
	$NI_count =	sprintf("%10s", "$NI_count"); while(strlen($NI_count)>10) {$NI_count = substr("$NI_count", 0, -1);}
	$UW_count =	sprintf("%10s", "$UW_count"); while(strlen($UW_count)>10) {$UW_count = substr("$UW_count", 0, -1);}
	$SC_count =	sprintf("%10s", "$SC_count"); while(strlen($SC_count)>10) {$SC_count = substr("$SC_count", 0, -1);}
	$COMP_count =	sprintf("%10s", "$COMP_count"); while(strlen($COMP_count)>10) {$COMP_count = substr("$COMP_count", 0, -1);}

	$graph_stats2=array(array("$HA_count", "Human Answer"), array("$SALE_count", "Sale"), array("$DNC_count", "DNC"), array("$CC_count", "Customer Contact"), array("$NI_count", "Not Interested"), array("$UW_count", "Unworkable"), array("$SC_count", "Scheduled Callbacks"), array("$COMP_count", "Completed"));

	$OUToutput .= "\n";
	$OUToutput .= "\n";
	$OUToutput .= "---------- "._QXZ("STATUS FLAGS SUMMARY:",24)." ("._QXZ("and % of leads in selected lists").")     <a href=\"$PHP_SELF?DB=$DB$groupQS&SUBMIT=$SUBMIT&file_download=2&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
	$OUToutput .= "+------------------+------------+----------+\n";
	$OUToutput .= "| "._QXZ("Human Answer",16)." | $HA_count |  $HA_percent% |\n";
	$OUToutput .= "| "._QXZ("Sale",16)." | $SALE_count |  $SALE_percent% |\n";
	$OUToutput .= "| "._QXZ("DNC",16)." | $DNC_count |  $DNC_percent% |\n";
	$OUToutput .= "| "._QXZ("Customer Contact",16)." | $CC_count |  $CC_percent% |\n";
	$OUToutput .= "| "._QXZ("Not Interested",16)." | $NI_count |  $NI_percent% |\n";
	$OUToutput .= "| "._QXZ("Unworkable",16)." | $UW_count |  $UW_percent% |\n";
	$OUToutput .= "| "._QXZ("Sched Callbacks",16)." | $SC_count |  $SC_percent% |\n";
	$OUToutput .= "| "._QXZ("Completed",16)." | $COMP_count |  $COMP_percent% |\n";
	$OUToutput .= "+------------------+------------+----------+\n";
	$OUToutput .= "\n";

	$CSV_text2.="\""._QXZ("STATUS FLAGS SUMMARY").":\"\n";
	$CSV_text2 .= "\""._QXZ("Human Answer")."\",\"$HA_count\",\"$HA_percent%\"\n";
	$CSV_text2 .= "\""._QXZ("Sale")."\",\"$SALE_count\",\"$SALE_percent%\"\n";
	$CSV_text2 .= "\""._QXZ("DNC")."\",\"$DNC_count\",\"$DNC_percent%\"\n";
	$CSV_text2 .= "\""._QXZ("Customer Contact")."\",\"$CC_count\",\"$CC_percent%\"\n";
	$CSV_text2 .= "\""._QXZ("Not Interested")."\",\"$NI_count\",\"$NI_percent%\"\n";
	$CSV_text2 .= "\""._QXZ("Unworkable")."\",\"$UW_count\",\"$UW_percent%\"\n";
	$CSV_text2 .= "\""._QXZ("Scheduled Callbacks")."\",\"$SC_count\",\"$SC_percent%\"\n";
	$CSV_text2 .= "\""._QXZ("Completed")."\",\"$COMP_count\",\"$COMP_percent%\"\n";

		#########
		$graph_array=array("APDdata|||intpct|");
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
			for ($d=0; $d<count($graph_stats2); $d++) {
				$labels.="\"".$graph_stats2[$d][1]."\",";
				$data.="\"".$graph_stats2[$d][0]."\",";
				$current_graph_total+=$graph_stats2[$d][0];
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
		$graph_title=_QXZ("STATUS FLAG SUMMARY");
		include("graphcanvas.inc");
		$HEADER.=$HTML_graph_head;
		$GRAPH.=$graphCanvas;
	# $OUToutput.=$GRAPH;

	##############################
	#########  STATUS CATEGORY STATS

	$OUToutput .= "\n";
	$OUToutput .= "---------- "._QXZ("CUSTOM STATUS CATEGORY STATS",32)." <a href=\"$PHP_SELF?DB=$DB$groupQS&SUBMIT=$SUBMIT&file_download=3&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
	$OUToutput .= "+----------------------+------------+--------------------------------+\n";
	$OUToutput .= "| "._QXZ("CATEGORY",20)." | "._QXZ("CALLS",10)." | "._QXZ("DESCRIPTION",30)." |\n";
	$OUToutput .= "+----------------------+------------+--------------------------------+\n";

	$CSV_text3.="\""._QXZ("CUSTOM STATUS CATEGORY STATS")."\"\n";
	$CSV_text3.="\""._QXZ("CATEGORY")."\",\""._QXZ("CALLS")."\",\""._QXZ("DESCRIPTION")."\"\n";

	$max_calls=1; 
	$graph_stats=array();

	$TOTCATcalls=0;
	$r=0; $i=0;
	while ($r < $statcats_to_print)
		{
		if ($vsc_id[$r] != 'UNDEFINED')
			{
			$TOTCATcalls = ($TOTCATcalls + $vsc_count[$r]);
			$category =	sprintf("%-20s", $vsc_id[$r]); while(strlen($category)>20) {$category = substr("$category", 0, -1);}
			$CATcount =	sprintf("%10s", $vsc_count[$r]); while(strlen($CATcount)>10) {$CATcount = substr("$CATcount", 0, -1);}
			$CATname =	sprintf("%-30s", $vsc_name[$r]); while(strlen($CATname)>30) {$CATname = substr("$CATname", 0, -1);}

			if ($vsc_count[$r]>$max_calls) {$max_calls=$vsc_count[$r];}
			$graph_stats[$i][0]=$vsc_count[$r];
			$graph_stats[$i][1]=$vsc_id[$r];
			$i++;

			$OUToutput .= "| $category | $CATcount | $CATname |\n";
			$CSV_text3.="\"$category\",\"$CATcount\",\"$CATname\"\n";
			}
		$r++;
		}

	$TOTCATcalls =	sprintf("%10s", $TOTCATcalls); while(strlen($TOTCATcalls)>10) {$TOTCATcalls = substr("$TOTCATcalls", 0, -1);}

	$OUToutput .= "+----------------------+------------+--------------------------------+\n";
	$OUToutput .= "| "._QXZ("TOTAL",20)." | $TOTCATcalls |\n";
	$OUToutput .= "+----------------------+------------+\n";
	$CSV_text3.="\""._QXZ("TOTAL")."\",\"$TOTCATcalls\"\n";

	#########
	$graph_array=array("CSCSdata|||integer|");
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
			$labels.="\"".$graph_stats[$d][1]."\",";
			$data.="\"".$graph_stats[$d][0]."\",";
			$current_graph_total+=$graph_stats[$d][0];
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
	$graph_title=_QXZ("CUSTOM STATUS CATEGORY STATS");
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH.=$graphCanvas;

	#$OUToutput.=$GRAPH;


	##############################
	#########  PER LIST DETAIL STATS


	$TOTALleads = 0;
	$OUToutput .= "\n";
	$OUToutput .= "---------- "._QXZ("PER LIST DETAIL STATS",25)." <a href=\"$PHP_SELF?DB=$DB$groupQS&SUBMIT=$SUBMIT&file_download=4&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";
	$OUToutput .= "\n";

	$CSV_text4.="\""._QXZ("PER LIST DETAIL STATS")."\"\n\n";

	$i=0;
	while ($i < $listids_to_print)
		{
		$TOTALleads=0;
		$header_list_id = "$LISTIDlists[$i] - $LISTIDlist_names[$i]";
		$header_list_id =	sprintf("%-51s", $header_list_id); while(strlen($header_list_id)>51) {$header_list_id = substr("$header_list_id", 0, -1);}
		$header_list_count =	sprintf("%10s", $LISTIDcalls[$i]); while(strlen($header_list_count)>10) {$header_list_count = substr("$header_list_count", 0, -1);}

		$OUToutput .= "\n";
		$OUToutput .= "+--------------------------------------------------------------+\n";
		$OUToutput .= "| $header_list_id $LISTIDlist_active[$i] |\n";
		$OUToutput .= "| "._QXZ("TOTAL LEADS",14,"r").": $header_list_count                                   |\n";
		$OUToutput .= "+--------------------------------------------------------------+\n";

		$max_flags=1; 
		$max_status=1;
		$graph_stats=array();
		$CSV_text4.="\""._QXZ("LIST ID").": $LISTIDlists[$i]\",\"$LISTIDlist_names[$i]\",\"$LISTIDlist_active[$i]\"\n";
		$CSV_text4.="\""._QXZ("TOTAL LEADS").":\",\"$header_list_count\"\n\n";

		$HA_count=0;
		$HA_percent=0;
		$SALE_count=0;
		$SALE_percent=0;
		$DNC_count=0;
		$DNC_percent=0;
		$CC_count=0;
		$CC_percent=0;
		$NI_count=0;
		$NI_percent=0;
		$UW_count=0;
		$UW_percent=0;
		$SC_count=0;
		$SC_percent=0;
		$COMP_count=0;
		$COMP_percent=0;

		$stmt="select count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' and status IN($human_answered_statuses);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_results = mysqli_num_rows($rslt);
		if ($HA_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$HA_count = $row[0];
			$HA_percent = ( MathZDC($HA_count, $LISTIDcalls[$i]) * 100);
			}
		if ($HA_count>$max_flags) {$max_flags=$HA_count;}
		$stmt="select count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' and status IN($sale_statuses);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SALE_results = mysqli_num_rows($rslt);
		if ($SALE_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$SALE_count = $row[0];
			$SALE_percent = ( MathZDC($SALE_count, $LISTIDcalls[$i]) * 100);
			}
		if ($SALE_count>$max_flags) {$max_flags=$SALE_count;}
		$stmt="select count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' and status IN($dnc_statuses);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DNC_results = mysqli_num_rows($rslt);
		if ($DNC_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$DNC_count = $row[0];
			$DNC_percent = ( MathZDC($DNC_count, $LISTIDcalls[$i]) * 100);
			}
		if ($DNC_count>$max_flags) {$max_flags=$DNC_count;}
		$stmt="select count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' and status IN($customer_contact_statuses);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_results = mysqli_num_rows($rslt);
		if ($CC_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$CC_count = $row[0];
			$CC_percent = ( MathZDC($CC_count, $LISTIDcalls[$i]) * 100);
			}
		if ($CC_count>$max_flags) {$max_flags=$CC_count;}
		$stmt="select count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' and status IN($not_interested_statuses);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$NI_results = mysqli_num_rows($rslt);
		if ($NI_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$NI_count = $row[0];
			$NI_percent = ( MathZDC($NI_count, $LISTIDcalls[$i]) * 100);
			}
		if ($NI_count>$max_flags) {$max_flags=$NI_count;}
		$stmt="select count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' and status IN($unworkable_statuses);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_results = mysqli_num_rows($rslt);
		if ($UW_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$UW_count = $row[0];
			$UW_percent = ( MathZDC($UW_count, $LISTIDcalls[$i]) * 100);
			}
		if ($UW_count>$max_flags) {$max_flags=$UW_count;}
		$stmt="select count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' and status IN($scheduled_callback_statuses);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SC_results = mysqli_num_rows($rslt);
		if ($SC_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$SC_count = $row[0];
			$SC_percent = ( MathZDC($SC_count, $LISTIDcalls[$i]) * 100);
			}
		if ($SC_count>$max_flags) {$max_flags=$SC_count;}
		$stmt="select count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' and status IN($completed_statuses);";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$COMP_results = mysqli_num_rows($rslt);
		if ($COMP_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$COMP_count = $row[0];
			$COMP_percent = ( MathZDC($COMP_count, $LISTIDcalls[$i]) * 100);
			}
		if ($COMP_count>$max_flags) {$max_flags=$COMP_count;}

		$graph_stats2=array(array("$HA_count", "Human Answer"), array("$SALE_count", "Sale"), array("$DNC_count", "DNC"), array("$CC_count", "Customer Contact"), array("$NI_count", "Not Interested"), array("$UW_count", "Unworkable"), array("$SC_count", "Scheduled Callbacks"), array("$COMP_count", "Completed"));

		$HA_percent =	sprintf("%6.2f", "$HA_percent"); while(strlen($HA_percent)>6) {$HA_percent = substr("$HA_percent", 0, -1);}
		$SALE_percent =	sprintf("%6.2f", "$SALE_percent"); while(strlen($SALE_percent)>6) {$SALE_percent = substr("$SALE_percent", 0, -1);}
		$DNC_percent =	sprintf("%6.2f", "$DNC_percent"); while(strlen($DNC_percent)>6) {$DNC_percent = substr("$DNC_percent", 0, -1);}
		$CC_percent =	sprintf("%6.2f", "$CC_percent"); while(strlen($CC_percent)>6) {$CC_percent = substr("$CC_percent", 0, -1);}
		$NI_percent =	sprintf("%6.2f", "$NI_percent"); while(strlen($NI_percent)>6) {$NI_percent = substr("$NI_percent", 0, -1);}
		$UW_percent =	sprintf("%6.2f", "$UW_percent"); while(strlen($UW_percent)>6) {$UW_percent = substr("$UW_percent", 0, -1);}
		$SC_percent =	sprintf("%6.2f", "$SC_percent"); while(strlen($SC_percent)>6) {$SC_percent = substr("$SC_percent", 0, -1);}
		$COMP_percent =	sprintf("%6.2f", "$COMP_percent"); while(strlen($COMP_percent)>6) {$COMP_percent = substr("$COMP_percent", 0, -1);}

		$HA_count =	sprintf("%9s", "$HA_count"); while(strlen($HA_count)>9) {$HA_count = substr("$HA_count", 0, -1);}
		$SALE_count =	sprintf("%9s", "$SALE_count"); while(strlen($SALE_count)>9) {$SALE_count = substr("$SALE_count", 0, -1);}
		$DNC_count =	sprintf("%9s", "$DNC_count"); while(strlen($DNC_count)>9) {$DNC_count = substr("$DNC_count", 0, -1);}
		$CC_count =	sprintf("%9s", "$CC_count"); while(strlen($CC_count)>9) {$CC_count = substr("$CC_count", 0, -1);}
		$NI_count =	sprintf("%9s", "$NI_count"); while(strlen($NI_count)>9) {$NI_count = substr("$NI_count", 0, -1);}
		$UW_count =	sprintf("%9s", "$UW_count"); while(strlen($UW_count)>9) {$UW_count = substr("$UW_count", 0, -1);}
		$SC_count =	sprintf("%9s", "$SC_count"); while(strlen($SC_count)>9) {$SC_count = substr("$SC_count", 0, -1);}
		$COMP_count =	sprintf("%9s", "$COMP_count"); while(strlen($COMP_count)>9) {$COMP_count = substr("$COMP_count", 0, -1);}

		$OUToutput .= "| "._QXZ("STATUS FLAGS BREAKDOWN",22).":  "._QXZ("(and % of total leads in the list)",35)." |\n";
		$OUToutput .= "|   "._QXZ("Human Answer:",19)." $HA_count    $HA_percent%                   |\n";
		$OUToutput .= "|   "._QXZ("Sale:",19)." $SALE_count    $SALE_percent%                   |\n";
		$OUToutput .= "|   "._QXZ("DNC:",19)." $DNC_count    $DNC_percent%                   |\n";
		$OUToutput .= "|   "._QXZ("Customer Contact:",19)." $CC_count    $CC_percent%                   |\n";
		$OUToutput .= "|   "._QXZ("Not Interested:",19)." $NI_count    $NI_percent%                   |\n";
		$OUToutput .= "|   "._QXZ("Unworkable:",19)." $UW_count    $UW_percent%                   |\n";
		$OUToutput .= "|   "._QXZ("Sched Callbacks:",19)." $SC_count    $SC_percent%                   |\n";
		$OUToutput .= "|   "._QXZ("Completed:",19)." $COMP_count    $COMP_percent%                   |\n";
		$OUToutput .= "+----+--------------------------------------------+------------+\n";
		$OUToutput .= "     | "._QXZ("STATUS BREAKDOWN",19,"r").":                       | "._QXZ("COUNT",8,"r")."   |\n";
		$OUToutput .= "     +--------+-----------------------------------+------------+\n";

		$FLAGS_graph.="  <tr><td class='chart_td first'>"._QXZ("HUMAN ANSWER")."</td><td nowrap class='chart_td value first'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$HA_count, $max_flags))."' height='16' />$HA_count ($HA_percent%)</td></tr>";
		$FLAGS_graph.="  <tr><td class='chart_td'>"._QXZ("SALE")."</td><td nowrap class='chart_td value'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$SALE_count, $max_flags))."' height='16' />$SALE_count ($SALE_percent%)</td></tr>";
		$FLAGS_graph.="  <tr><td class='chart_td'>"._QXZ("DNC")."</td><td nowrap class='chart_td value'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$DNC_count, $max_flags))."' height='16' />$DNC_count ($DNC_percent%)</td></tr>";
		$FLAGS_graph.="  <tr><td class='chart_td'>"._QXZ("CUSTOMER CONTACT")."</td><td nowrap class='chart_td value'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$CC_count, $max_flags))."' height='16' />$CC_count ($CC_percent%)</td></tr>";
		$FLAGS_graph.="  <tr><td class='chart_td'>"._QXZ("NOT INTERESTED")."</td><td nowrap class='chart_td value'><img src='images/bar.png' alt='' width='".round(MathZDC(400*$NI_count, $max_flags))."' height='16' />$NI_count ($NI_percent%)</td></tr>";
		$FLAGS_graph.="  <tr><td class='chart_td'>"._QXZ("UNWORKABLE")."</td><td nowrap class='chart_td value last><img src='images/bar.png' alt='' width='".round(MathZDC(400*$UW_count, $max_flags))."' height='16' />$UW_count ($UW_percent%)</td></tr>";
		$FLAGS_graph.="  <tr><td class='chart_td'>"._QXZ("SCHEDULED CALLBACKS")."</td><td nowrap class='chart_td value last><img src='images/bar.png' alt='' width='".round(MathZDC(400*$SC_count, $max_flags))."' height='16' />$SC_count ($SC_percent%)</td></tr>";
		$FLAGS_graph.="  <tr><td class='chart_td last'>"._QXZ("COMPLETED")."</td><td nowrap class='chart_td value last><img src='images/bar.png' alt='' width='".round(MathZDC(400*$COMP_count, $max_flags))."' height='16' />$COMP_count ($COMP_percent%)</td></tr>";

		$CSV_text4.="\""._QXZ("STATUS FLAGS BREAKDOWN").":\",\"("._QXZ("and % of total leads in the list").")\"\n";
		$CSV_text4.="\""._QXZ("Human Answer").":\",\"$HA_count\",\"$HA_percent%\"\n";
		$CSV_text4.="\""._QXZ("Sale").":\",\"$SALE_count\",\"$SALE_percent%\"\n";
		$CSV_text4.="\""._QXZ("DNC").":\",\"$DNC_count\",\"$DNC_percent%\"\n";
		$CSV_text4.="\""._QXZ("Customer Contact").":\",\"$CC_count\",\"$CC_percent%\"\n";
		$CSV_text4.="\""._QXZ("Not Interested").":\",\"$NI_count\",\"$NI_percent%\"\n";
		$CSV_text4.="\""._QXZ("Unworkable").":\",\"$UW_count\",\"$UW_percent%\"\n\n";
		$CSV_text4.="\""._QXZ("Scheduled Callbacks").":\",\"$SC_count\",\"$SC_percent%\"\n\n";
		$CSV_text4.="\""._QXZ("Completed").":\",\"$COMP_count\",\"$COMP_percent%\"\n\n";
		$CSV_text4.="\""._QXZ("STATUS BREAKDOWN").":\",\"\",\""._QXZ("COUNT")."\"\n";

		$stmt="select status,count(*) from ".$vicidial_list_table." where list_id='$LISTIDlists[$i]' group by status order by status;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$liststatussum_to_print = mysqli_num_rows($rslt);
		$r=0;
		while ($r < $liststatussum_to_print)
			{
			$row=mysqli_fetch_row($rslt);
			$LISTIDstatus[$r] =	$row[0];
			$LISTIDcounts[$r] =	$row[1];
			$graph_stats[$r][0]=$row[0];
			$graph_stats[$r][1]=$row[1];
			if ($row[1]>$max_status) {$max_status=$row[1];}
				if ($DB) {$MAIN.="$r|$LISTIDstatus[$r]|$LISTIDcounts[$r]|    |$row[0]|$row[1]|<BR>\n";}
			$r++;
			}

		$r=0;
		while ($r < $liststatussum_to_print)
			{
			$LIDstatus = $LISTIDstatus[$r];
			$LIDstatus_format = sprintf("%6s", $LIDstatus);
			$TOTALleads = ($TOTALleads + $LISTIDcounts[$r]);

			$LISTID_status_count =	sprintf("%10s", $LISTIDcounts[$r]); while(strlen($LISTID_status_count)>10) {$LISTID_status_count = substr("$LISTID_status_count", 0, -1);}
			$LISTIDname =	sprintf("%-42s", "$LIDstatus_format | $statname_list[$LIDstatus]"); while(strlen($LISTIDname)>42) {$LISTIDname = substr("$LISTIDname", 0, -1);}
			$graph_stats[$r][0].=" - $statname_list[$LIDstatus]";

			$OUToutput .= "     | $LISTIDname | $LISTID_status_count |\n";
			$CSV_text4.="\"".trim($LIDstatus_format)."\",\"$statname_list[$LIDstatus]\",\"$LISTID_status_count\"\n";
			$r++;
			}
		$TOTALleads =		sprintf("%10s", $TOTALleads);

		$OUToutput .= "     +--------+-----------------------------------+------------+\n";
		$OUToutput .= "     | "._QXZ("TOTAL",41,"r").": | $TOTALleads |\n";
		$OUToutput .= "     +--------------------------------------------+------------+\n";

		$CSV_text4.="\""._QXZ("TOTAL").":\",\"\",\"$TOTALleads\"\n\n\n";

		# USE THIS FOR COMBINED graphs, use pipe-delimited array elements, dataset_name|index|link_name|graph_override
		# You have to hard code the graph name in where it is overridden and mind the data indices.  No other way to do it.
		$multigraph_text="";
		$graph_id++;
		$graph_array=array("ALCS_STATUSFLAG$LISTIDlists[$i]data|0|STATUS FLAG BREAKDOWN|intpct|graph_stats2", "ALCS_STATUS$LISTIDlists[$i]data|1|STATUS BREAKDOWN|integer|");
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
			$graph_override=$graph_info[4];

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

			if ($graph_override) {
				for ($d=0; $d<count($graph_stats2); $d++) {
					$labels.="\"".preg_replace('/ +/', ' ', $graph_stats2[$d][1])."\",";
					$data.="\"".$graph_stats2[$d][$dataset_index]."\",";
					$current_graph_total+=$graph_stats2[$d][$dataset_index];
					$bgcolor=$backgroundColor[($d%count($backgroundColor))];
					$hbgcolor=$hoverBackgroundColor[($d%count($hoverBackgroundColor))];
					$hbcolor=$hoverBorderColor[($d%count($hoverBorderColor))];
					$graphConstantsA.="\"$bgcolor\",";
					$graphConstantsB.="\"$hbgcolor\",";
					$graphConstantsC.="\"$hbcolor\",";
				}	
			} else {
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
		$graph_title="$LISTIDlists[$i]  - $LISTIDlist_names[$i] ($LISTIDlist_active[$i])";
		include("graphcanvas.inc");
		$HEADER.=$HTML_graph_head;
		$GRAPH.=$graphCanvas;

		# $OUToutput.=$GRAPH;

		$i++;
		}




	if ($report_display_type=="HTML")
		{
		$MAIN.=$GRAPH;
		}
	else
		{
		$MAIN.="$OUToutput";
		}



	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
	$MAIN.="</PRE>\n";
	$MAIN.="</TD></TR></TABLE>\n";
	$MAIN.="</BODY></HTML>\n";

	}

	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_LISTS_campaign_stats_$US$FILE_TIME.csv";
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

	} else {
		$JS_onload.="}\n";
		if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
		$JS_text.="</script>\n";

		echo $HEADER;
		require("admin_header.php");
		echo $MAIN;
		if ($report_display_type=="HTML") {echo $JS_text;}
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
