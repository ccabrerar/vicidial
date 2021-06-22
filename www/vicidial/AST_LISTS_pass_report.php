<?php 
# AST_LISTS_pass_report.php
# 
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This is a list inventory report, not a calling report. This report will show
# statistics for all of the lists in the selected campaigns
#
# NOTE! This report can cause high database load on even moderate-sized systems. We do not recommend running it during production hours.
#
# CHANGES
# 140116-0839 - First build based upon AST_LISTS_campaign_stats.php
# 140121-0707 - Fixed small issue in List select mode
# 140331-2122 - Converted division calculations to use MathZDC function, added HTML view
# 141114-0827 - Finalized adding QXZ translation to all admin files
# 141230-0919 - Added code for on-the-fly language translations display
# 150516-1303 - Fixed Javascript element problem, Issue #857
# 151125-1640 - Added search archive option
# 160227-1138 - Uniform form format
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170227-1711 - Fix for default HTML report format, issue #997
# 170409-1555 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 171012-2015 - Fixed javascript/apache errors with graphs
# 180507-2315 - Added new help display
# 191013-0828 - Fixes for PHP7
# 210330-1659 - Added extra warnings and forced confirmation before running report
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
if (isset($_GET["confirm_run"]))					{$confirm_run=$_GET["confirm_run"];}
	elseif (isset($_POST["confirm_run"]))		{$confirm_run=$_POST["confirm_run"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["use_lists"]))			{$use_lists=$_GET["use_lists"];}
	elseif (isset($_POST["use_lists"]))	{$use_lists=$_POST["use_lists"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}


$report_name = 'Lists Pass Report';
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
$table_name="vicidial_log";
$archive_table_name=use_archive_table($table_name);
if ($archive_table_name!=$table_name) {$archives_available="Y";}

if ($search_archived_data) 
	{
	$vicidial_log_table=use_archive_table("vicidial_log");
	}
else
	{
	$vicidial_log_table="vicidial_log";
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

$groups=array();
$group_names=array();
if ($use_lists < 1)
	{
	$stmt="select campaign_id,campaign_name from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$campaigns_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $campaigns_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$groups[$i] =		$row[0];
		$group_names[$i] =	$row[1];
		if (preg_match('/\-ALL/',$group_string) )
			{$group[$i] = $groups[$i];}
		$i++;
		}
	}
else
	{
	$stmt="select list_id,list_name from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$campaigns_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $campaigns_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$groups[$i] =		$row[0];
		$group_names[$i] =	$row[1];
		if (preg_match('/\-ALL/',$group_string) )
			{$group[$i] = $groups[$i];}
		$i++;
		}
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

if ($use_lists < 1)
	{
	if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) or (strlen($group_string) < 2) )
		{
		$group_SQL = "$LOGallowed_campaignsSQL";
		}
	else
		{
		$group_SQL = preg_replace('/,$/i', '',$group_SQL);
		$group_SQLand = "and campaign_id IN($group_SQL)";
		$group_SQL = "where campaign_id IN($group_SQL)";
		}
	}
else
	{
	if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) or (strlen($group_string) < 2) )
		{
		$group_SQL = "where list_id IN($group_SQL)";
		}
	else
		{
		$group_SQL = preg_replace('/,$/i', '',$group_SQL);
		$group_SQLand = "and list_id IN($group_SQL)";
		$group_SQL = "where list_id IN($group_SQL)";
		}

	}

# Get lists to query to avoid using a nested query
$lists_id_str="";
$list_stmt="SELECT list_id from vicidial_lists where active IN('Y','N') $group_SQLand";
$list_rslt=mysql_to_mysqli($list_stmt, $link);
while ($lrow=mysqli_fetch_row($list_rslt)) 
	{
	$lists_id_str.="'$lrow[0]',";
	}
$lists_id_str=substr($lists_id_str,0,-1);


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
$stmt="select status,human_answered,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,status_name from vicidial_campaign_statuses where selectable IN('Y','N');";
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

if ($DB) {echo "<!-- SALE statuses: $sale_statuses -->";}

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

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#LISTS_pass_report$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\">";
$MAIN.="<TR><TD colspan='4' class='small_standard'>** Due to the complexity of this report, it is strongly recommended that it not be run during production as it can interfere with dialing. **</td>";
$MAIN.="<TR><TD VALIGN=TOP>";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=use_lists VALUE=\"$use_lists\">\n";

if ($use_lists > 0)
	{
	$MAIN.="</TD><TD VALIGN=TOP class='standard'> "._QXZ("Lists").":<BR>";
	$MAIN.="<SELECT SIZE=5 NAME=group[] multiple>\n";
	if  (preg_match('/\-\-ALL\-\-/',$group_string))
		{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL LISTS")." --</option>\n";}
	else
		{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL LISTS")." --</option>\n";}
	$o=0;
	while ($campaigns_to_print > $o)
		{
		if (preg_match("/$groups[$o]\|/i",$group_string)) {$MAIN.="<option selected value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";}
		  else {$MAIN.="<option value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";}
		$o++;
		}
	$MAIN.="</SELECT>\n<BR>\n";
	$MAIN.="<a href=\"$PHP_SELF?use_lists=0&DB=$DB\">"._QXZ("SWITCH TO CAMPAIGNS")."</a>";
	}
else
	{
	$MAIN.="</TD><TD VALIGN=TOP class='standard'> "._QXZ("Campaigns").":<BR>";
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
	$MAIN.="</SELECT>\n<BR>\n";
	$MAIN.="<a href=\"$PHP_SELF?use_lists=1&DB=$DB\">"._QXZ("SWITCH TO LISTS")."</a>";
	}
$MAIN.="</TD><TD VALIGN=TOP class='standard'>";
$MAIN.=_QXZ("Display as").":<BR/>";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>&nbsp; ";
if ($archives_available=="Y") 
	{
	$MAIN.="<BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}
$MAIN.="<BR><BR>\n";
$MAIN.="<INPUT style='background-color:#$SSbutton_color' type=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="</TD><TD VALIGN=TOP class='standard'> &nbsp; &nbsp; &nbsp; &nbsp; ";
if ($use_lists > 0)
	{
	if (strlen($group[0]) > 1)
		{
		$MAIN.=" <a href=\"./admin.php?ADD=311&list_id=$group[0]\">"._QXZ("MODIFY")."</a> | \n";
		$MAIN.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>\n";
		}
	else
		{
		$MAIN.=" <a href=\"./admin.php?ADD=100\">"._QXZ("LISTS")."</a> | \n";
		$MAIN.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>\n";
		}
	}
else
	{
	if (strlen($group[0]) > 1)
		{
		$MAIN.=" <a href=\"./admin.php?ADD=34&campaign_id=$group[0]\">"._QXZ("MODIFY")."</a> | \n";
		$MAIN.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>\n";
		}
	else
		{
		$MAIN.=" <a href=\"./admin.php?ADD=10\">"._QXZ("CAMPAIGNS")."</a> | \n";
		$MAIN.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>\n";
		}
	}
$MAIN.="</TD></TR></TABLE>";
$MAIN.="</FORM>\n\n";

$MAIN.="<PRE><FONT SIZE=2>\n\n";


if (strlen($group[0]) < 1)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT A CAMPAIGN ABOVE AND CLICK SUBMIT")."\n";
	}
else if (strlen($group[0]) >= 1 && !$confirm_run)
	{
	$MAIN.="<font color='#900' size='3'><B>"._QXZ("REMINDER - THIS REPORT CAN TAKE A LONG TIME TO RUN AND WILL INTERFERE WITH DIALING IF EXECUTED DURING PRODUCTION.")."<BR>"._QXZ("IF YOU ARE SURE YOU WOULD LIKE TO RUN THE REPORT AT THIS TIME")." <a href=\"$PHP_SELF?DB=$DB$groupQS&SUBMIT=$SUBMIT&confirm_run=1&search_archived_data=$search_archived_data\">"._QXZ("CLICK HERE")."</a></B></font>";
	}
else
	{
	$OUToutput = '';
	$OUToutput .= _QXZ("Lists Pass Report",45)." $NOW_TIME\n";

	$OUToutput .= "\n";

	##############################
	#########  LIST ID BREAKDOWN STATS

	$TOTALleads = 0;

	$OUToutput .= "\n";
	$OUToutput .= "---------- "._QXZ("LIST ID SUMMARY",19)." <a href=\"$PHP_SELF?DB=$DB$groupQS&SUBMIT=$SUBMIT&file_download=1&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a>\n";

	$OUToutput .= "+------------+------------------------------------------+----------+------------+----------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "\n";

	$OUToutput .= "|   "._QXZ("FIRST",8)." |                                          |          | "._QXZ("LEAD",10)." |          |";
	$OUToutput .= _QXZ("CONTACTS",9,"r")."|"._QXZ("CONTACTS",9,"r")."|"._QXZ("CONTACTS",9,"r")."|"._QXZ("CONTACTS",9,"r")."|"._QXZ("CONTACTS",9,"r")."|"._QXZ("CONTACTS",9,"r")."|";
	$OUToutput .= _QXZ("CNT RATE",9,"r")."|"._QXZ("CNT RATE",9,"r")."|"._QXZ("CNT RATE",9,"r")."|"._QXZ("CNT RATE",9,"r")."|"._QXZ("CNT RATE",9,"r")."|"._QXZ("CNT RATE",9,"r")."|";
	$OUToutput .= _QXZ("SALES",8,"r")." |"._QXZ("SALES",8,"r")." |"._QXZ("SALES",8,"r")." |"._QXZ("SALES",8,"r")." |"._QXZ("SALES",8,"r")." |"._QXZ("SALES",8,"r")." |";
	$OUToutput .= _QXZ("CONV RATE",9,"r")."|"._QXZ("CONV RATE",9,"r")."|"._QXZ("CONV RATE",9,"r")."|"._QXZ("CONV RATE",9,"r")."|"._QXZ("CONV RATE",9,"r")."|"._QXZ("CONV RATE",9,"r")."|";
	$OUToutput .= _QXZ("  DNC",8)." | "._QXZ(" DNC",7)." | "._QXZ(" DNC",7)." | "._QXZ(" DNC",7)." | "._QXZ(" DNC",7)." | "._QXZ(" DNC",7)." |";
	$OUToutput .= _QXZ("DNC RATE",9,"r")."|"._QXZ("DNC RATE",9,"r")."|"._QXZ("DNC RATE",9,"r")."|"._QXZ("DNC RATE",9,"r")."|"._QXZ("DNC RATE",9,"r")."|"._QXZ("DNC RATE",9,"r")."|";
	$OUToutput .= _QXZ("CUST CONT",9,"r")."|"._QXZ("CUST CONT",9,"r")."|"._QXZ("CUST CONT",9,"r")."|"._QXZ("CUST CONT",9,"r")."|"._QXZ("CUST CONT",9,"r")."|"._QXZ("CUST CONT",9,"r")."|";
	$OUToutput .= _QXZ("CUCT RATE",9,"r")."|"._QXZ("CUCT RATE",9,"r")."|"._QXZ("CUCT RATE",9,"r")."|"._QXZ("CUCT RATE",9,"r")."|"._QXZ("CUCT RATE",9,"r")."|"._QXZ("CUCT RATE",9,"r")."|";
	$OUToutput .= _QXZ("UNWORKABL",9,"r")."|"._QXZ("UNWORKABL",9,"r")."|"._QXZ("UNWORKABL",9,"r")."|"._QXZ("UNWORKABL",9,"r")."|"._QXZ("UNWORKABL",9,"r")."|"._QXZ("UNWORKABL",9,"r")."|";
	$OUToutput .= _QXZ("UNWK RATE",9,"r")."|"._QXZ("UNWK RATE",9,"r")."|"._QXZ("UNWK RATE",9,"r")."|"._QXZ("UNWK RATE",9,"r")."|"._QXZ("UNWK RATE",9,"r")."|"._QXZ("UNWK RATE",9,"r")."|";
	$OUToutput .= _QXZ("SCHEDL CB",9,"r")."|"._QXZ("SCHEDL CB",9,"r")."|"._QXZ("SCHEDL CB",9,"r")."|"._QXZ("SCHEDL CB",9,"r")."|"._QXZ("SCHEDL CB",9,"r")."|"._QXZ("SCHEDL CB",9,"r")."|";
	$OUToutput .= _QXZ("SHCB RATE",9,"r")."|"._QXZ("SHCB RATE",9,"r")."|"._QXZ("SHCB RATE",9,"r")."|"._QXZ("SHCB RATE",9,"r")."|"._QXZ("SHCB RATE",9,"r")."|"._QXZ("SHCB RATE",9,"r")."|";
	$OUToutput .= _QXZ("COMPLETED",9,"r")."|"._QXZ("COMPLETED",9,"r")."|"._QXZ("COMPLETED",9,"r")."|"._QXZ("COMPLETED",9,"r")."|"._QXZ("COMPLETED",9,"r")."|"._QXZ("COMPLETED",9,"r")."|";
	$OUToutput .= _QXZ("COMP RATE",9,"r")."|"._QXZ("COMP RATE",9,"r")."|"._QXZ("COMP RATE",9,"r")."|"._QXZ("COMP RATE",9,"r")."|"._QXZ("COMP RATE",9,"r")."|"._QXZ("COMP RATE",9,"r")."|";
	$OUToutput .= "\n";

	$OUToutput .= "| "._QXZ("LOAD DATE",10,"r")." | "._QXZ("LIST ID and NAME",40)." | "._QXZ("CAMPAIGN",8)." | "._QXZ("COUNT",10)." | "._QXZ("ACTIVE",8)." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= " "._QXZ("1st PASS",8,"r")."| "._QXZ("2nd PASS",8,"r")."| "._QXZ("3rd PASS",8,"r")."| "._QXZ("4th PASS",8,"r")."| "._QXZ("5th PASS",8,"r")."| "._QXZ("LIFE",7,"r")." |";
	$OUToutput .= "\n";

	$OUToutput .= "+------------+------------------------------------------+----------+------------+----------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "\n";


	$CSV_text1.="\""._QXZ("LIST ID SUMMARY")."\"\n";
	$CSV_text1.="\""._QXZ("FIRST LOAD DATE")."\",\""._QXZ("LIST")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("LEADS")."\",\""._QXZ("ACTIVE")."\"";
	$CSV_text1.=",\""._QXZ("CONTACTS 1st PASS")."\",\""._QXZ("CONTACTS 2nd PASS")."\",\""._QXZ("CONTACTS 3rd PASS")."\",\""._QXZ("CONTACTS 4th PASS")."\",\""._QXZ("CONTACTS 5th PASS")."\",\""._QXZ("CONTACTS LIFE")."\"";
	$CSV_text1.=",\""._QXZ("CNT RATE 1st PASS")."\",\""._QXZ("CNT RATE 2nd PASS")."\",\""._QXZ("CNT RATE 3rd PASS")."\",\""._QXZ("CNT RATE 4th PASS")."\",\""._QXZ("CNT RATE 5th PASS")."\",\""._QXZ("CNT RATE LIFE")."\"";
	$CSV_text1.=",\""._QXZ("SALES 1st PASS")."\",\""._QXZ("SALES 2nd PASS")."\",\""._QXZ("SALES 3rd PASS")."\",\""._QXZ("SALES 4th PASS")."\",\""._QXZ("SALES 5th PASS")."\",\""._QXZ("SALES LIFE")."\"";
	$CSV_text1.=",\""._QXZ("CONV RATE 1st PASS")."\",\""._QXZ("CONV RATE 2nd PASS")."\",\""._QXZ("CONV RATE 3rd PASS")."\",\""._QXZ("CONV RATE 4th PASS")."\",\""._QXZ("CONV RATE 5th PASS")."\",\""._QXZ("CONV RATE LIFE")."\"";
	$CSV_text1.=",\""._QXZ("DNC 1st PASS")."\",\""._QXZ("DNC 2nd PASS")."\",\""._QXZ("DNC 3rd PASS")."\",\""._QXZ("DNC 4th PASS")."\",\""._QXZ("DNC 5th PASS")."\",\""._QXZ("DNC LIFE")."\"";
	$CSV_text1.=",\""._QXZ("DNC RATE 1st PASS")."\",\""._QXZ("DNC RATE 2nd PASS")."\",\""._QXZ("DNC RATE 3rd PASS")."\",\""._QXZ("DNC RATE 4th PASS")."\",\""._QXZ("DNC RATE 5th PASS")."\",\""._QXZ("DNC RATE LIFE")."\"";
	$CSV_text1.=",\""._QXZ("CUSTOMER CONTACT 1st PASS")."\",\""._QXZ("CUSTOMER CONTACT 2nd PASS")."\",\""._QXZ("CUSTOMER CONTACT 3rd PASS")."\",\""._QXZ("CUSTOMER CONTACT 4th PASS")."\",\""._QXZ("CUSTOMER CONTACT 5th PASS")."\",\""._QXZ("CUSTOMER CONTACT LIFE")."\"";
	$CSV_text1.=",\""._QXZ("CUSTOMER CONTACT RATE 1st PASS")."\",\""._QXZ("CUSTOMER CONTACT RATE 2nd PASS")."\",\""._QXZ("CUSTOMER CONTACT RATE 3rd PASS")."\",\""._QXZ("CUSTOMER CONTACT RATE 4th PASS")."\",\""._QXZ("CUSTOMER CONTACT RATE 5th PASS")."\",\""._QXZ("CUSTOMER CONTACT RATE LIFE")."\"";
	$CSV_text1.=",\""._QXZ("UNWORKABLE 1st PASS")."\",\""._QXZ("UNWORKABLE 2nd PASS")."\",\""._QXZ("UNWORKABLE 3rd PASS")."\",\""._QXZ("UNWORKABLE 4th PASS")."\",\""._QXZ("UNWORKABLE 5th PASS")."\",\""._QXZ("UNWORKABLE LIFE")."\"";
	$CSV_text1.=",\""._QXZ("UNWORKABLE RATE 1st PASS")."\",\""._QXZ("UNWORKABLE RATE 2nd PASS")."\",\""._QXZ("UNWORKABLE RATE 3rd PASS")."\",\""._QXZ("UNWORKABLE RATE 4th PASS")."\",\""._QXZ("UNWORKABLE RATE 5th PASS")."\",\""._QXZ("UNWORKABLE RATE LIFE")."\"";
	$CSV_text1.=",\""._QXZ("SCHEDULED CALLBACK 1st PASS")."\",\""._QXZ("SCHEDULED CALLBACK 2nd PASS")."\",\""._QXZ("SCHEDULED CALLBACK 3rd PASS")."\",\""._QXZ("SCHEDULED CALLBACK 4th PASS")."\",\""._QXZ("SCHEDULED CALLBACK 5th PASS")."\",\""._QXZ("SCHEDULED CALLBACK LIFE")."\"";
	$CSV_text1.=",\""._QXZ("SCHEDULED CALLBACK RATE 1st PASS")."\",\""._QXZ("SCHEDULED CALLBACK RATE 2nd PASS")."\",\""._QXZ("SCHEDULED CALLBACK RATE 3rd PASS")."\",\""._QXZ("SCHEDULED CALLBACK RATE 4th PASS")."\",\""._QXZ("SCHEDULED CALLBACK RATE 5th PASS")."\",\""._QXZ("SCHEDULED CALLBACK RATE LIFE")."\"";
	$CSV_text1.=",\""._QXZ("COMPLETED 1st PASS")."\",\""._QXZ("COMPLETED 2nd PASS")."\",\""._QXZ("COMPLETED 3rd PASS")."\",\""._QXZ("COMPLETED 4th PASS")."\",\""._QXZ("COMPLETED 5th PASS")."\",\""._QXZ("COMPLETED LIFE")."\"";
	$CSV_text1.=",\""._QXZ("COMPLETED RATE 1st PASS")."\",\""._QXZ("COMPLETED RATE 2nd PASS")."\",\""._QXZ("COMPLETED RATE 3rd PASS")."\",\""._QXZ("COMPLETED RATE 4th PASS")."\",\""._QXZ("COMPLETED RATE 5th PASS")."\",\""._QXZ("COMPLETED RATE LIFE")."\"";
	$CSV_text1.="\n";

	$graph_stats=array();
	$graph_stats2=array();
	$max_stats2=array();
	$totals2=array();
	$lists_id_str="";
	$list_stmt="SELECT list_id from vicidial_lists where active IN('Y','N') $group_SQLand";
	$list_rslt=mysql_to_mysqli($list_stmt, $link);
	while ($lrow=mysqli_fetch_row($list_rslt)) 
		{
		$lists_id_str.="'$lrow[0]',";
		}
	$lists_id_str=substr($lists_id_str,0,-1);
	if (strlen($lists_id_str)<3) {$lists_id_str="''";}

	$stmt="select count(*),list_id from vicidial_list where list_id IN($lists_id_str) group by list_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$listids_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $listids_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTIDcalls[$i] =	$row[0];
		$LISTIDlists[$i] =	$row[1];
		$list_id_SQL .=		"'$row[1]',";
		if ($row[0]>$max_calls) {$max_calls=$row[0];}
		$graph_stats[$i][0]=$row[0];
		$graph_stats[$i][1]=$row[1];
		$graph_stats2[$i][0]=$row[1];
		$i++;
		}
	if (strlen($list_id_SQL)>2)		{$list_id_SQL = substr("$list_id_SQL", 0, -1);}
	else {$list_id_SQL="''";}

	$i=0;
	while ($i < $listids_to_print)
		{
		$stmt="select list_name,active,campaign_id from vicidial_lists where list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$list_name_to_print = mysqli_num_rows($rslt);
		if ($list_name_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$LISTIDlist_names[$i] =	$row[0];
			$LISTIDcampaign[$i] =	$row[2];
			$graph_stats[$i][1].=" - $row[0]";
			$graph_stats2[$i][0].=" - $row[0]";
			if ($row[1]=='Y')
				{$LISTIDlist_active[$i] = 'ACTIVE  '; $graph_stats[$i][1].=" (ACTIVE)"; $graph_stats2[$i][0].=" (ACTIVE)";}
			else
				{$LISTIDlist_active[$i] = 'INACTIVE'; $graph_stats[$i][1].=" (INACTIVE)"; $graph_stats2[$i][0].=" (INACTIVE)";}
			}

		$LISTIDentry_date[$i]='';
		$stmt="select entry_date from vicidial_list where list_id='$LISTIDlists[$i]' order by entry_date limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$list_name_to_print = mysqli_num_rows($rslt);
		if ($list_name_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$LISTIDentry_date[$i] =	$row[0];
			}

		$TOTALleads = ($TOTALleads + $LISTIDcalls[$i]);
		$LISTIDentry_dateS =	sprintf("%10s", $LISTIDentry_date[$i]); while(strlen($LISTIDentry_dateS)>10) {$LISTIDentry_dateS = substr("$LISTIDentry_dateS", 0, -1);}
		$LISTIDcampaignS =	sprintf("%8s", $LISTIDcampaign[$i]); while(strlen($LISTIDcampaignS)>8) {$LISTIDcampaignS = substr("$LISTIDcampaignS", 0, -1);}
		$LISTIDname =	sprintf("%-40s", "$LISTIDlists[$i] - $LISTIDlist_names[$i]"); while(strlen($LISTIDname)>40) {$LISTIDname = substr("$LISTIDname", 0, -1);}
		$LISTIDcount =	sprintf("%10s", $LISTIDcalls[$i]); while(strlen($LISTIDcount)>10) {$LISTIDcount = substr("$LISTIDcount", 0, -1);}



		########################################################
		########## BEGIN CONTACTS (Human-Answer flag) ##########

		$HA_count=0; $HA_one_count=0; $HA_two_count=0; $HA_three_count=0; $HA_four_count=0; $HA_five_count=0; $HA_all_count=0;

		$stmt="select count(*) from vicidial_list where status IN($human_answered_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_results = mysqli_num_rows($rslt);
		if ($HA_results > 0)
			{$row=mysqli_fetch_row($rslt); $HA_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=1 and status IN($human_answered_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_one_results = mysqli_num_rows($rslt);
		if ($HA_one_results > 0)
			{$row=mysqli_fetch_row($rslt); $HA_one_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=2 and status IN($human_answered_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_two_results = mysqli_num_rows($rslt);
		if ($HA_two_results > 0)
			{$row=mysqli_fetch_row($rslt); $HA_two_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=3 and status IN($human_answered_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_three_results = mysqli_num_rows($rslt);
		if ($HA_three_results > 0)
			{$row=mysqli_fetch_row($rslt); $HA_three_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count='4' and status IN($human_answered_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_four_results = mysqli_num_rows($rslt);
		if ($HA_four_results > 0)
			{$row=mysqli_fetch_row($rslt); $HA_four_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count='5' and status IN($human_answered_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_five_results = mysqli_num_rows($rslt);
		if ($HA_five_results > 0)
			{$row=mysqli_fetch_row($rslt); $HA_five_count = $row[0];}
		$stmt="select count(distinct lead_id) from ".$vicidial_log_table." where status IN($human_answered_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_all_results = mysqli_num_rows($rslt);
		if ($HA_all_results > 0)
			{$row=mysqli_fetch_row($rslt); $HA_all_count = $row[0];}
		if ($HA_all_count > $HA_count) {$HA_count = $HA_all_count;}

		$HA_countS =	sprintf("%7s", $HA_count); while(strlen($HA_countS)>7) {$HA_countS = substr("$HA_countS", 0, -1);}
		$HA_one_countS =	sprintf("%7s", $HA_one_count); while(strlen($HA_one_countS)>7) {$HA_one_countS = substr("$HA_one_countS", 0, -1);}
		$HA_two_countS =	sprintf("%7s", $HA_two_count); while(strlen($HA_two_countS)>7) {$HA_two_countS = substr("$HA_two_countS", 0, -1);}
		$HA_three_countS =	sprintf("%7s", $HA_three_count); while(strlen($HA_three_countS)>7) {$HA_three_countS = substr("$HA_three_countS", 0, -1);}
		$HA_four_countS =	sprintf("%7s", $HA_four_count); while(strlen($HA_four_countS)>7) {$HA_four_countS = substr("$HA_four_countS", 0, -1);}
		$HA_five_countS =	sprintf("%7s", $HA_five_count); while(strlen($HA_five_countS)>7) {$HA_five_countS = substr("$HA_five_countS", 0, -1);}

		$HA_count_tot =	($HA_count + $HA_count_tot);
		$HA_one_count_tot =	($HA_one_count + $HA_one_count_tot);
		$HA_two_count_tot =	($HA_two_count + $HA_two_count_tot);
		$HA_three_count_tot =	($HA_three_count + $HA_three_count_tot);
		$HA_four_count_tot =	($HA_four_count + $HA_four_count_tot);
		$HA_five_count_tot =	($HA_five_count + $HA_five_count_tot);

		########## END CONTACTS (Human-Answer flag) ##########
		########################################################


		########################################################
		########## BEGIN CONTACT RATIO (Human-Answer flag out of total leads percentage) ##########

		$HR_count=$HA_count; 
		$HR_one_count=$HA_one_count;
		$HR_two_count=$HA_two_count;
		$HR_three_count=$HA_three_count;
		$HR_four_count=$HA_four_count;
		$HR_five_count=$HA_five_count;
		$HR_all_count=$HA_all_count;

		$HR_count_pct=0;
		$HR_one_count_pct=0;
		$HR_two_count_pct=0;
		$HR_three_count_pct=0;
		$HR_four_count_pct=0;
		$HR_five_count_pct=0;
		$HR_count_pct = (MathZDC($HR_count, $LISTIDcalls[$i]) * 100);
		$HR_one_count_pct = (MathZDC($HR_one_count, $LISTIDcalls[$i]) * 100);
		$HR_two_count_pct = (MathZDC($HR_two_count, $LISTIDcalls[$i]) * 100);
		$HR_three_count_pct = (MathZDC($HR_three_count, $LISTIDcalls[$i]) * 100);
		$HR_four_count_pct = (MathZDC($HR_four_count, $LISTIDcalls[$i]) * 100);
		$HR_five_count_pct = (MathZDC($HR_five_count, $LISTIDcalls[$i]) * 100);

		$HR_countS =	sprintf("%6.2f", $HR_count_pct); while(strlen($HR_countS)>7) {$HR_countS = substr("$HR_countS", 0, -1);}
		$HR_one_countS =	sprintf("%6.2f", $HR_one_count_pct); while(strlen($HR_one_countS)>7) {$HR_one_countS = substr("$HR_one_countS", 0, -1);}
		$HR_two_countS =	sprintf("%6.2f", $HR_two_count_pct); while(strlen($HR_two_countS)>7) {$HR_two_countS = substr("$HR_two_countS", 0, -1);}
		$HR_three_countS =	sprintf("%6.2f", $HR_three_count_pct); while(strlen($HR_three_countS)>7) {$HR_three_countS = substr("$HR_three_countS", 0, -1);}
		$HR_four_countS =	sprintf("%6.2f", $HR_four_count_pct); while(strlen($HR_four_countS)>7) {$HR_four_countS = substr("$HR_four_countS", 0, -1);}
		$HR_five_countS =	sprintf("%6.2f", $HR_five_count_pct); while(strlen($HR_five_countS)>7) {$HR_five_countS = substr("$HR_five_countS", 0, -1);}

		$HR_count_tot =	($HR_count + $HR_count_tot);
		$HR_one_count_tot =	($HR_one_count + $HR_one_count_tot);
		$HR_two_count_tot =	($HR_two_count + $HR_two_count_tot);
		$HR_three_count_tot =	($HR_three_count + $HR_three_count_tot);
		$HR_four_count_tot =	($HR_four_count + $HR_four_count_tot);
		$HR_five_count_tot =	($HR_five_count + $HR_five_count_tot);

		########## END  CONTACT RATIO (Human-Answer flag out of total leads percentage) ##########
		########################################################


		########################################################
		########## BEGIN SALES (Sales flag) ##########

		$SA_count=0; $SA_one_count=0; $SA_two_count=0; $SA_three_count=0; $SA_four_count=0; $SA_five_count=0; $SA_all_count=0;

		$stmt="select count(*) from vicidial_list where status IN($sale_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SA_results = mysqli_num_rows($rslt);
		if ($SA_results > 0)
			{$row=mysqli_fetch_row($rslt); $SA_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=1 and status IN($sale_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SA_one_results = mysqli_num_rows($rslt);
		if ($SA_one_results > 0)
			{$row=mysqli_fetch_row($rslt); $SA_one_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=2 and status IN($sale_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SA_two_results = mysqli_num_rows($rslt);
		if ($SA_two_results > 0)
			{$row=mysqli_fetch_row($rslt); $SA_two_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=3 and status IN($sale_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SA_three_results = mysqli_num_rows($rslt);
		if ($SA_three_results > 0)
			{$row=mysqli_fetch_row($rslt); $SA_three_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=4 and status IN($sale_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SA_four_results = mysqli_num_rows($rslt);
		if ($SA_four_results > 0)
			{$row=mysqli_fetch_row($rslt); $SA_four_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=5 and status IN($sale_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SA_five_results = mysqli_num_rows($rslt);
		if ($SA_five_results > 0)
			{$row=mysqli_fetch_row($rslt); $SA_five_count = $row[0];}
		$stmt="select count(distinct lead_id) from ".$vicidial_log_table." where status IN($sale_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SA_all_results = mysqli_num_rows($rslt);
		if ($SA_all_results > 0)
			{$row=mysqli_fetch_row($rslt); $SA_all_count = $row[0];}
		if ($SA_all_count > $SA_count) {$SA_count = $SA_all_count;}

		$SA_countS =	sprintf("%7s", $SA_count); while(strlen($SA_countS)>7) {$SA_countS = substr("$SA_countS", 0, -1);}
		$SA_one_countS =	sprintf("%7s", $SA_one_count); while(strlen($SA_one_countS)>7) {$SA_one_countS = substr("$SA_one_countS", 0, -1);}
		$SA_two_countS =	sprintf("%7s", $SA_two_count); while(strlen($SA_two_countS)>7) {$SA_two_countS = substr("$SA_two_countS", 0, -1);}
		$SA_three_countS =	sprintf("%7s", $SA_three_count); while(strlen($SA_three_countS)>7) {$SA_three_countS = substr("$SA_three_countS", 0, -1);}
		$SA_four_countS =	sprintf("%7s", $SA_four_count); while(strlen($SA_four_countS)>7) {$SA_four_countS = substr("$SA_four_countS", 0, -1);}
		$SA_five_countS =	sprintf("%7s", $SA_five_count); while(strlen($SA_five_countS)>7) {$SA_five_countS = substr("$SA_five_countS", 0, -1);}

		$SA_count_tot =	($SA_count + $SA_count_tot);
		$SA_one_count_tot =	($SA_one_count + $SA_one_count_tot);
		$SA_two_count_tot =	($SA_two_count + $SA_two_count_tot);
		$SA_three_count_tot =	($SA_three_count + $SA_three_count_tot);
		$SA_four_count_tot =	($SA_four_count + $SA_four_count_tot);
		$SA_five_count_tot =	($SA_five_count + $SA_five_count_tot);

		########## END SALES (Sales flag) ##########
		########################################################


		########################################################
		########## BEGIN CONV SALES RATIO (Sales flag out of total leads percentage) ##########

		$SR_count=$SA_count; 
		$SR_one_count=$SA_one_count;
		$SR_two_count=$SA_two_count;
		$SR_three_count=$SA_three_count;
		$SR_four_count=$SA_four_count;
		$SR_five_count=$SA_five_count;
		$SR_all_count=$SA_all_count;

		$SR_count_pct=0;
		$SR_one_count_pct=0;
		$SR_two_count_pct=0;
		$SR_three_count_pct=0;
		$SR_four_count_pct=0;
		$SR_five_count_pct=0;
		$SR_count_pct = (MathZDC($SR_count, $LISTIDcalls[$i]) * 100);
		$SR_one_count_pct = (MathZDC($SR_one_count, $LISTIDcalls[$i]) * 100);
		$SR_two_count_pct = (MathZDC($SR_two_count, $LISTIDcalls[$i]) * 100);
		$SR_three_count_pct = (MathZDC($SR_three_count, $LISTIDcalls[$i]) * 100);
		$SR_four_count_pct = (MathZDC($SR_four_count, $LISTIDcalls[$i]) * 100);
		$SR_five_count_pct = (MathZDC($SR_five_count, $LISTIDcalls[$i]) * 100);

		$SR_countS =	sprintf("%6.2f", $SR_count_pct); while(strlen($SR_countS)>7) {$SR_countS = substr("$SR_countS", 0, -1);}
		$SR_one_countS =	sprintf("%6.2f", $SR_one_count_pct); while(strlen($SR_one_countS)>7) {$SR_one_countS = substr("$SR_one_countS", 0, -1);}
		$SR_two_countS =	sprintf("%6.2f", $SR_two_count_pct); while(strlen($SR_two_countS)>7) {$SR_two_countS = substr("$SR_two_countS", 0, -1);}
		$SR_three_countS =	sprintf("%6.2f", $SR_three_count_pct); while(strlen($SR_three_countS)>7) {$SR_three_countS = substr("$SR_three_countS", 0, -1);}
		$SR_four_countS =	sprintf("%6.2f", $SR_four_count_pct); while(strlen($SR_four_countS)>7) {$SR_four_countS = substr("$SR_four_countS", 0, -1);}
		$SR_five_countS =	sprintf("%6.2f", $SR_five_count_pct); while(strlen($SR_five_countS)>7) {$SR_five_countS = substr("$SR_five_countS", 0, -1);}

		$SR_count_tot =	($SR_count + $SR_count_tot);
		$SR_one_count_tot =	($SR_one_count + $SR_one_count_tot);
		$SR_two_count_tot =	($SR_two_count + $SR_two_count_tot);
		$SR_three_count_tot =	($SR_three_count + $SR_three_count_tot);
		$SR_four_count_tot =	($SR_four_count + $SR_four_count_tot);
		$SR_five_count_tot =	($SR_five_count + $SR_five_count_tot);

		########## END   CONV SALES RATIO (Sales flag out of total leads percentage) ##########
		########################################################


		########################################################
		########## BEGIN DNC (DNC flag) ##########

		$DN_count=0; $DN_one_count=0; $DN_two_count=0; $DN_three_count=0; $DN_four_count=0; $DN_five_count=0; $DN_all_count=0;

		$stmt="select count(*) from vicidial_list where status IN($dnc_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DN_results = mysqli_num_rows($rslt);
		if ($DN_results > 0)
			{$row=mysqli_fetch_row($rslt); $DN_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=1 and status IN($dnc_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DN_one_results = mysqli_num_rows($rslt);
		if ($DN_one_results > 0)
			{$row=mysqli_fetch_row($rslt); $DN_one_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=2 and status IN($dnc_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DN_two_results = mysqli_num_rows($rslt);
		if ($DN_two_results > 0)
			{$row=mysqli_fetch_row($rslt); $DN_two_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=3 and status IN($dnc_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DN_three_results = mysqli_num_rows($rslt);
		if ($DN_three_results > 0)
			{$row=mysqli_fetch_row($rslt); $DN_three_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=4 and status IN($dnc_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DN_four_results = mysqli_num_rows($rslt);
		if ($DN_four_results > 0)
			{$row=mysqli_fetch_row($rslt); $DN_four_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=5 and status IN($dnc_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DN_five_results = mysqli_num_rows($rslt);
		if ($DN_five_results > 0)
			{$row=mysqli_fetch_row($rslt); $DN_five_count = $row[0];}
		$stmt="select count(distinct lead_id) from ".$vicidial_log_table." where status IN($dnc_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DN_all_results = mysqli_num_rows($rslt);
		if ($DN_all_results > 0)
			{$row=mysqli_fetch_row($rslt); $DN_all_count = $row[0];}
		if ($DN_all_count > $DN_count) {$DN_count = $DN_all_count;}

		$DN_countS =	sprintf("%7s", $DN_count); while(strlen($DN_countS)>7) {$DN_countS = substr("$DN_countS", 0, -1);}
		$DN_one_countS =	sprintf("%7s", $DN_one_count); while(strlen($DN_one_countS)>7) {$DN_one_countS = substr("$DN_one_countS", 0, -1);}
		$DN_two_countS =	sprintf("%7s", $DN_two_count); while(strlen($DN_two_countS)>7) {$DN_two_countS = substr("$DN_two_countS", 0, -1);}
		$DN_three_countS =	sprintf("%7s", $DN_three_count); while(strlen($DN_three_countS)>7) {$DN_three_countS = substr("$DN_three_countS", 0, -1);}
		$DN_four_countS =	sprintf("%7s", $DN_four_count); while(strlen($DN_four_countS)>7) {$DN_four_countS = substr("$DN_four_countS", 0, -1);}
		$DN_five_countS =	sprintf("%7s", $DN_five_count); while(strlen($DN_five_countS)>7) {$DN_five_countS = substr("$DN_five_countS", 0, -1);}

		$DN_count_tot =	($DN_count + $DN_count_tot);
		$DN_one_count_tot =	($DN_one_count + $DN_one_count_tot);
		$DN_two_count_tot =	($DN_two_count + $DN_two_count_tot);
		$DN_three_count_tot =	($DN_three_count + $DN_three_count_tot);
		$DN_four_count_tot =	($DN_four_count + $DN_four_count_tot);
		$DN_five_count_tot =	($DN_five_count + $DN_five_count_tot);

		########## END DNC (DNC flag) ##########
		########################################################


		########################################################
		########## BEGIN CONV DNC RATIO (DNC flag out of total leads percentage) ##########

		$DR_count=$DN_count; 
		$DR_one_count=$DN_one_count;
		$DR_two_count=$DN_two_count;
		$DR_three_count=$DN_three_count;
		$DR_four_count=$DN_four_count;
		$DR_five_count=$DN_five_count;
		$DR_all_count=$DN_all_count;

		$DR_count_pct=0;
		$DR_one_count_pct=0;
		$DR_two_count_pct=0;
		$DR_three_count_pct=0;
		$DR_four_count_pct=0;
		$DR_five_count_pct=0;
		$DR_count_pct = (MathZDC($DR_count, $LISTIDcalls[$i]) * 100);
		$DR_one_count_pct = (MathZDC($DR_one_count, $LISTIDcalls[$i]) * 100);
		$DR_two_count_pct = (MathZDC($DR_two_count, $LISTIDcalls[$i]) * 100);
		$DR_three_count_pct = (MathZDC($DR_three_count, $LISTIDcalls[$i]) * 100);
		$DR_four_count_pct = (MathZDC($DR_four_count, $LISTIDcalls[$i]) * 100);
		$DR_five_count_pct = (MathZDC($DR_five_count, $LISTIDcalls[$i]) * 100);

		$DR_countS =	sprintf("%6.2f", $DR_count_pct); while(strlen($DR_countS)>7) {$DR_countS = substr("$DR_countS", 0, -1);}
		$DR_one_countS =	sprintf("%6.2f", $DR_one_count_pct); while(strlen($DR_one_countS)>7) {$DR_one_countS = substr("$DR_one_countS", 0, -1);}
		$DR_two_countS =	sprintf("%6.2f", $DR_two_count_pct); while(strlen($DR_two_countS)>7) {$DR_two_countS = substr("$DR_two_countS", 0, -1);}
		$DR_three_countS =	sprintf("%6.2f", $DR_three_count_pct); while(strlen($DR_three_countS)>7) {$DR_three_countS = substr("$DR_three_countS", 0, -1);}
		$DR_four_countS =	sprintf("%6.2f", $DR_four_count_pct); while(strlen($DR_four_countS)>7) {$DR_four_countS = substr("$DR_four_countS", 0, -1);}
		$DR_five_countS =	sprintf("%6.2f", $DR_five_count_pct); while(strlen($DR_five_countS)>7) {$DR_five_countS = substr("$DR_five_countS", 0, -1);}

		$DR_count_tot =	($DR_count + $DR_count_tot);
		$DR_one_count_tot =	($DR_one_count + $DR_one_count_tot);
		$DR_two_count_tot =	($DR_two_count + $DR_two_count_tot);
		$DR_three_count_tot =	($DR_three_count + $DR_three_count_tot);
		$DR_four_count_tot =	($DR_four_count + $DR_four_count_tot);
		$DR_five_count_tot =	($DR_five_count + $DR_five_count_tot);

		########## END   CONV DNC RATIO (DNC flag out of total leads percentage) ##########
		########################################################


		########################################################
		########## BEGIN CUSTOMER CONTACT (Customer Contact flag) ##########

		$CC_count=0; $CC_one_count=0; $CC_two_count=0; $CC_three_count=0; $CC_four_count=0; $CC_five_count=0; $CC_all_count=0;

		$stmt="select count(*) from vicidial_list where status IN($customer_contact_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_results = mysqli_num_rows($rslt);
		if ($CC_results > 0)
			{$row=mysqli_fetch_row($rslt); $CC_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=1 and status IN($customer_contact_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_one_results = mysqli_num_rows($rslt);
		if ($CC_one_results > 0)
			{$row=mysqli_fetch_row($rslt); $CC_one_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=2 and status IN($customer_contact_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_two_results = mysqli_num_rows($rslt);
		if ($CC_two_results > 0)
			{$row=mysqli_fetch_row($rslt); $CC_two_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=3 and status IN($customer_contact_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_three_results = mysqli_num_rows($rslt);
		if ($CC_three_results > 0)
			{$row=mysqli_fetch_row($rslt); $CC_three_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=4 and status IN($customer_contact_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_four_results = mysqli_num_rows($rslt);
		if ($CC_four_results > 0)
			{$row=mysqli_fetch_row($rslt); $CC_four_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=5 and status IN($customer_contact_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_five_results = mysqli_num_rows($rslt);
		if ($CC_five_results > 0)
			{$row=mysqli_fetch_row($rslt); $CC_five_count = $row[0];}
		$stmt="select count(distinct lead_id) from ".$vicidial_log_table." where status IN($customer_contact_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_all_results = mysqli_num_rows($rslt);
		if ($CC_all_results > 0)
			{$row=mysqli_fetch_row($rslt); $CC_all_count = $row[0];}
		if ($CC_all_count > $CC_count) {$CC_count = $CC_all_count;}

		$CC_countS =	sprintf("%7s", $CC_count); while(strlen($CC_countS)>7) {$CC_countS = substr("$CC_countS", 0, -1);}
		$CC_one_countS =	sprintf("%7s", $CC_one_count); while(strlen($CC_one_countS)>7) {$CC_one_countS = substr("$CC_one_countS", 0, -1);}
		$CC_two_countS =	sprintf("%7s", $CC_two_count); while(strlen($CC_two_countS)>7) {$CC_two_countS = substr("$CC_two_countS", 0, -1);}
		$CC_three_countS =	sprintf("%7s", $CC_three_count); while(strlen($CC_three_countS)>7) {$CC_three_countS = substr("$CC_three_countS", 0, -1);}
		$CC_four_countS =	sprintf("%7s", $CC_four_count); while(strlen($CC_four_countS)>7) {$CC_four_countS = substr("$CC_four_countS", 0, -1);}
		$CC_five_countS =	sprintf("%7s", $CC_five_count); while(strlen($CC_five_countS)>7) {$CC_five_countS = substr("$CC_five_countS", 0, -1);}

		$CC_count_tot =	($CC_count + $CC_count_tot);
		$CC_one_count_tot =	($CC_one_count + $CC_one_count_tot);
		$CC_two_count_tot =	($CC_two_count + $CC_two_count_tot);
		$CC_three_count_tot =	($CC_three_count + $CC_three_count_tot);
		$CC_four_count_tot =	($CC_four_count + $CC_four_count_tot);
		$CC_five_count_tot =	($CC_five_count + $CC_five_count_tot);

		########## END CUSTOMER CONTACT (Customer Contact flag) ##########
		########################################################


		########################################################
		########## BEGIN CUSTOMER CONTACT RATIO (Customer Contact flag out of total leads percentage) ##########

		$CR_count=$CC_count; 
		$CR_one_count=$CC_one_count;
		$CR_two_count=$CC_two_count;
		$CR_three_count=$CC_three_count;
		$CR_four_count=$CC_four_count;
		$CR_five_count=$CC_five_count;
		$CR_all_count=$CC_all_count;

		$CR_count_pct=0;
		$CR_one_count_pct=0;
		$CR_two_count_pct=0;
		$CR_three_count_pct=0;
		$CR_four_count_pct=0;
		$CR_five_count_pct=0;
		$CR_count_pct = (MathZDC($CR_count, $LISTIDcalls[$i]) * 100);
		$CR_one_count_pct = (MathZDC($CR_one_count, $LISTIDcalls[$i]) * 100);
		$CR_two_count_pct = (MathZDC($CR_two_count, $LISTIDcalls[$i]) * 100);
		$CR_three_count_pct = (MathZDC($CR_three_count, $LISTIDcalls[$i]) * 100);
		$CR_four_count_pct = (MathZDC($CR_four_count, $LISTIDcalls[$i]) * 100);
		$CR_five_count_pct = (MathZDC($CR_five_count, $LISTIDcalls[$i]) * 100);

		$CR_countS =	sprintf("%6.2f", $CR_count_pct); while(strlen($CR_countS)>7) {$CR_countS = substr("$CR_countS", 0, -1);}
		$CR_one_countS =	sprintf("%6.2f", $CR_one_count_pct); while(strlen($CR_one_countS)>7) {$CR_one_countS = substr("$CR_one_countS", 0, -1);}
		$CR_two_countS =	sprintf("%6.2f", $CR_two_count_pct); while(strlen($CR_two_countS)>7) {$CR_two_countS = substr("$CR_two_countS", 0, -1);}
		$CR_three_countS =	sprintf("%6.2f", $CR_three_count_pct); while(strlen($CR_three_countS)>7) {$CR_three_countS = substr("$CR_three_countS", 0, -1);}
		$CR_four_countS =	sprintf("%6.2f", $CR_four_count_pct); while(strlen($CR_four_countS)>7) {$CR_four_countS = substr("$CR_four_countS", 0, -1);}
		$CR_five_countS =	sprintf("%6.2f", $CR_five_count_pct); while(strlen($CR_five_countS)>7) {$CR_five_countS = substr("$CR_five_countS", 0, -1);}

		$CR_count_tot =	($CR_count + $CR_count_tot);
		$CR_one_count_tot =	($CR_one_count + $CR_one_count_tot);
		$CR_two_count_tot =	($CR_two_count + $CR_two_count_tot);
		$CR_three_count_tot =	($CR_three_count + $CR_three_count_tot);
		$CR_four_count_tot =	($CR_four_count + $CR_four_count_tot);
		$CR_five_count_tot =	($CR_five_count + $CR_five_count_tot);

		########## END   CUSTOMER CONTACT RATIO (Customer Contact flag out of total leads percentage) ##########
		########################################################


		########################################################
		########## BEGIN UNWORKABLE (Unworkable flag) ##########

		$UW_count=0; $UW_one_count=0; $UW_two_count=0; $UW_three_count=0; $UW_four_count=0; $UW_five_count=0; $UW_all_count=0;

		$stmt="select count(*) from vicidial_list where status IN($unworkable_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_results = mysqli_num_rows($rslt);
		if ($UW_results > 0)
			{$row=mysqli_fetch_row($rslt); $UW_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=1 and status IN($unworkable_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_one_results = mysqli_num_rows($rslt);
		if ($UW_one_results > 0)
			{$row=mysqli_fetch_row($rslt); $UW_one_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=2 and status IN($unworkable_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_two_results = mysqli_num_rows($rslt);
		if ($UW_two_results > 0)
			{$row=mysqli_fetch_row($rslt); $UW_two_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=3 and status IN($unworkable_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_three_results = mysqli_num_rows($rslt);
		if ($UW_three_results > 0)
			{$row=mysqli_fetch_row($rslt); $UW_three_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=4 and status IN($unworkable_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_four_results = mysqli_num_rows($rslt);
		if ($UW_four_results > 0)
			{$row=mysqli_fetch_row($rslt); $UW_four_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=5 and status IN($unworkable_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_five_results = mysqli_num_rows($rslt);
		if ($UW_five_results > 0)
			{$row=mysqli_fetch_row($rslt); $UW_five_count = $row[0];}
		$stmt="select count(distinct lead_id) from ".$vicidial_log_table." where status IN($unworkable_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_all_results = mysqli_num_rows($rslt);
		if ($UW_all_results > 0)
			{$row=mysqli_fetch_row($rslt); $UW_all_count = $row[0];}
		if ($UW_all_count > $UW_count) {$UW_count = $UW_all_count;}

		$UW_countS =	sprintf("%7s", $UW_count); while(strlen($UW_countS)>7) {$UW_countS = substr("$UW_countS", 0, -1);}
		$UW_one_countS =	sprintf("%7s", $UW_one_count); while(strlen($UW_one_countS)>7) {$UW_one_countS = substr("$UW_one_countS", 0, -1);}
		$UW_two_countS =	sprintf("%7s", $UW_two_count); while(strlen($UW_two_countS)>7) {$UW_two_countS = substr("$UW_two_countS", 0, -1);}
		$UW_three_countS =	sprintf("%7s", $UW_three_count); while(strlen($UW_three_countS)>7) {$UW_three_countS = substr("$UW_three_countS", 0, -1);}
		$UW_four_countS =	sprintf("%7s", $UW_four_count); while(strlen($UW_four_countS)>7) {$UW_four_countS = substr("$UW_four_countS", 0, -1);}
		$UW_five_countS =	sprintf("%7s", $UW_five_count); while(strlen($UW_five_countS)>7) {$UW_five_countS = substr("$UW_five_countS", 0, -1);}

		$UW_count_tot =	($UW_count + $UW_count_tot);
		$UW_one_count_tot =	($UW_one_count + $UW_one_count_tot);
		$UW_two_count_tot =	($UW_two_count + $UW_two_count_tot);
		$UW_three_count_tot =	($UW_three_count + $UW_three_count_tot);
		$UW_four_count_tot =	($UW_four_count + $UW_four_count_tot);
		$UW_five_count_tot =	($UW_five_count + $UW_five_count_tot);

		########## END UNWORKABLE (Unworkable flag) ##########
		########################################################


		########################################################
		########## BEGIN UNWORKABLE RATIO (Unworkable flag out of total leads percentage) ##########

		$UR_count=$UW_count; 
		$UR_one_count=$UW_one_count;
		$UR_two_count=$UW_two_count;
		$UR_three_count=$UW_three_count;
		$UR_four_count=$UW_four_count;
		$UR_five_count=$UW_five_count;
		$UR_all_count=$UW_all_count;

		$UR_count_pct=0;
		$UR_one_count_pct=0;
		$UR_two_count_pct=0;
		$UR_three_count_pct=0;
		$UR_four_count_pct=0;
		$UR_five_count_pct=0;
		$UR_count_pct = (MathZDC($UR_count, $LISTIDcalls[$i]) * 100);
		$UR_one_count_pct = (MathZDC($UR_one_count, $LISTIDcalls[$i]) * 100);
		$UR_two_count_pct = (MathZDC($UR_two_count, $LISTIDcalls[$i]) * 100);
		$UR_three_count_pct = (MathZDC($UR_three_count, $LISTIDcalls[$i]) * 100);
		$UR_four_count_pct = (MathZDC($UR_four_count, $LISTIDcalls[$i]) * 100);
		$UR_five_count_pct = (MathZDC($UR_five_count, $LISTIDcalls[$i]) * 100);

		$UR_countS =	sprintf("%6.2f", $UR_count_pct); while(strlen($UR_countS)>7) {$UR_countS = substr("$UR_countS", 0, -1);}
		$UR_one_countS =	sprintf("%6.2f", $UR_one_count_pct); while(strlen($UR_one_countS)>7) {$UR_one_countS = substr("$UR_one_countS", 0, -1);}
		$UR_two_countS =	sprintf("%6.2f", $UR_two_count_pct); while(strlen($UR_two_countS)>7) {$UR_two_countS = substr("$UR_two_countS", 0, -1);}
		$UR_three_countS =	sprintf("%6.2f", $UR_three_count_pct); while(strlen($UR_three_countS)>7) {$UR_three_countS = substr("$UR_three_countS", 0, -1);}
		$UR_four_countS =	sprintf("%6.2f", $UR_four_count_pct); while(strlen($UR_four_countS)>7) {$UR_four_countS = substr("$UR_four_countS", 0, -1);}
		$UR_five_countS =	sprintf("%6.2f", $UR_five_count_pct); while(strlen($UR_five_countS)>7) {$UR_five_countS = substr("$UR_five_countS", 0, -1);}

		$UR_count_tot =	($UR_count + $UR_count_tot);
		$UR_one_count_tot =	($UR_one_count + $UR_one_count_tot);
		$UR_two_count_tot =	($UR_two_count + $UR_two_count_tot);
		$UR_three_count_tot =	($UR_three_count + $UR_three_count_tot);
		$UR_four_count_tot =	($UR_four_count + $UR_four_count_tot);
		$UR_five_count_tot =	($UR_five_count + $UR_five_count_tot);

		########## END   UNWORKABLE RATIO (Unworkable flag out of total leads percentage) ##########
		########################################################


		########################################################
		########## BEGIN SCHEDULED CALLBACK (Scheduled Callback flag) ##########

		$BA_count=0; $BA_one_count=0; $BA_two_count=0; $BA_three_count=0; $BA_four_count=0; $BA_five_count=0; $BA_all_count=0;

		$stmt="select count(*) from vicidial_list where status IN($scheduled_callback_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$BA_results = mysqli_num_rows($rslt);
		if ($BA_results > 0)
			{$row=mysqli_fetch_row($rslt); $BA_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=1 and status IN($scheduled_callback_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$BA_one_results = mysqli_num_rows($rslt);
		if ($BA_one_results > 0)
			{$row=mysqli_fetch_row($rslt); $BA_one_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=2 and status IN($scheduled_callback_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$BA_two_results = mysqli_num_rows($rslt);
		if ($BA_two_results > 0)
			{$row=mysqli_fetch_row($rslt); $BA_two_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=3 and status IN($scheduled_callback_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$BA_three_results = mysqli_num_rows($rslt);
		if ($BA_three_results > 0)
			{$row=mysqli_fetch_row($rslt); $BA_three_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=4 and status IN($scheduled_callback_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$BA_four_results = mysqli_num_rows($rslt);
		if ($BA_four_results > 0)
			{$row=mysqli_fetch_row($rslt); $BA_four_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=5 and status IN($scheduled_callback_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$BA_five_results = mysqli_num_rows($rslt);
		if ($BA_five_results > 0)
			{$row=mysqli_fetch_row($rslt); $BA_five_count = $row[0];}
		$stmt="select count(distinct lead_id) from ".$vicidial_log_table." where status IN($scheduled_callback_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$BA_all_results = mysqli_num_rows($rslt);
		if ($BA_all_results > 0)
			{$row=mysqli_fetch_row($rslt); $BA_all_count = $row[0];}
		if ($BA_all_count > $BA_count) {$BA_count = $BA_all_count;}

		$BA_countS =	sprintf("%7s", $BA_count); while(strlen($BA_countS)>7) {$BA_countS = substr("$BA_countS", 0, -1);}
		$BA_one_countS =	sprintf("%7s", $BA_one_count); while(strlen($BA_one_countS)>7) {$BA_one_countS = substr("$BA_one_countS", 0, -1);}
		$BA_two_countS =	sprintf("%7s", $BA_two_count); while(strlen($BA_two_countS)>7) {$BA_two_countS = substr("$BA_two_countS", 0, -1);}
		$BA_three_countS =	sprintf("%7s", $BA_three_count); while(strlen($BA_three_countS)>7) {$BA_three_countS = substr("$BA_three_countS", 0, -1);}
		$BA_four_countS =	sprintf("%7s", $BA_four_count); while(strlen($BA_four_countS)>7) {$BA_four_countS = substr("$BA_four_countS", 0, -1);}
		$BA_five_countS =	sprintf("%7s", $BA_five_count); while(strlen($BA_five_countS)>7) {$BA_five_countS = substr("$BA_five_countS", 0, -1);}

		$BA_count_tot =	($BA_count + $BA_count_tot);
		$BA_one_count_tot =	($BA_one_count + $BA_one_count_tot);
		$BA_two_count_tot =	($BA_two_count + $BA_two_count_tot);
		$BA_three_count_tot =	($BA_three_count + $BA_three_count_tot);
		$BA_four_count_tot =	($BA_four_count + $BA_four_count_tot);
		$BA_five_count_tot =	($BA_five_count + $BA_five_count_tot);

		########## END SCHEDULED CALLBACK (Scheduled Callback flag) ##########
		########################################################


		########################################################
		########## BEGIN SCHEDULED CALLBACK RATIO (Scheduled Callback flag out of total leads percentage) ##########

		$BR_count=$BA_count; 
		$BR_one_count=$BA_one_count;
		$BR_two_count=$BA_two_count;
		$BR_three_count=$BA_three_count;
		$BR_four_count=$BA_four_count;
		$BR_five_count=$BA_five_count;
		$BR_all_count=$BA_all_count;

		$BR_count_pct=0;
		$BR_one_count_pct=0;
		$BR_two_count_pct=0;
		$BR_three_count_pct=0;
		$BR_four_count_pct=0;
		$BR_five_count_pct=0;
		$BR_count_pct = (MathZDC($BR_count, $LISTIDcalls[$i]) * 100);
		$BR_one_count_pct = (MathZDC($BR_one_count, $LISTIDcalls[$i]) * 100);
		$BR_two_count_pct = (MathZDC($BR_two_count, $LISTIDcalls[$i]) * 100);
		$BR_three_count_pct = (MathZDC($BR_three_count, $LISTIDcalls[$i]) * 100);
		$BR_four_count_pct = (MathZDC($BR_four_count, $LISTIDcalls[$i]) * 100);
		$BR_five_count_pct = (MathZDC($BR_five_count, $LISTIDcalls[$i]) * 100);

		$BR_countS =	sprintf("%6.2f", $BR_count_pct); while(strlen($BR_countS)>7) {$BR_countS = substr("$BR_countS", 0, -1);}
		$BR_one_countS =	sprintf("%6.2f", $BR_one_count_pct); while(strlen($BR_one_countS)>7) {$BR_one_countS = substr("$BR_one_countS", 0, -1);}
		$BR_two_countS =	sprintf("%6.2f", $BR_two_count_pct); while(strlen($BR_two_countS)>7) {$BR_two_countS = substr("$BR_two_countS", 0, -1);}
		$BR_three_countS =	sprintf("%6.2f", $BR_three_count_pct); while(strlen($BR_three_countS)>7) {$BR_three_countS = substr("$BR_three_countS", 0, -1);}
		$BR_four_countS =	sprintf("%6.2f", $BR_four_count_pct); while(strlen($BR_four_countS)>7) {$BR_four_countS = substr("$BR_four_countS", 0, -1);}
		$BR_five_countS =	sprintf("%6.2f", $BR_five_count_pct); while(strlen($BR_five_countS)>7) {$BR_five_countS = substr("$BR_five_countS", 0, -1);}

		$BR_count_tot =	($BR_count + $BR_count_tot);
		$BR_one_count_tot =	($BR_one_count + $BR_one_count_tot);
		$BR_two_count_tot =	($BR_two_count + $BR_two_count_tot);
		$BR_three_count_tot =	($BR_three_count + $BR_three_count_tot);
		$BR_four_count_tot =	($BR_four_count + $BR_four_count_tot);
		$BR_five_count_tot =	($BR_five_count + $BR_five_count_tot);

		########## END   SCHEDULED CALLBACK RATIO (Scheduled Callback flag out of total leads percentage) ##########
		########################################################


		########################################################
		########## BEGIN COMPLETED (Completed flag) ##########

		$MP_count=0; $MP_one_count=0; $MP_two_count=0; $MP_three_count=0; $MP_four_count=0; $MP_five_count=0; $MP_all_count=0;

		$stmt="select count(*) from vicidial_list where status IN($completed_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$MP_results = mysqli_num_rows($rslt);
		if ($MP_results > 0)
			{$row=mysqli_fetch_row($rslt); $MP_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=1 and status IN($completed_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$MP_one_results = mysqli_num_rows($rslt);
		if ($MP_one_results > 0)
			{$row=mysqli_fetch_row($rslt); $MP_one_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=2 and status IN($completed_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$MP_two_results = mysqli_num_rows($rslt);
		if ($MP_two_results > 0)
			{$row=mysqli_fetch_row($rslt); $MP_two_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=3 and status IN($completed_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$MP_three_results = mysqli_num_rows($rslt);
		if ($MP_three_results > 0)
			{$row=mysqli_fetch_row($rslt); $MP_three_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=4 and status IN($completed_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$MP_four_results = mysqli_num_rows($rslt);
		if ($MP_four_results > 0)
			{$row=mysqli_fetch_row($rslt); $MP_four_count = $row[0];}
		$stmt="select count(*) from ".$vicidial_log_table." where called_count=5 and status IN($completed_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$MP_five_results = mysqli_num_rows($rslt);
		if ($MP_five_results > 0)
			{$row=mysqli_fetch_row($rslt); $MP_five_count = $row[0];}
		$stmt="select count(distinct lead_id) from ".$vicidial_log_table." where status IN($completed_statuses) and list_id='$LISTIDlists[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$MP_all_results = mysqli_num_rows($rslt);
		if ($MP_all_results > 0)
			{$row=mysqli_fetch_row($rslt); $MP_all_count = $row[0];}
		if ($MP_all_count > $MP_count) {$MP_count = $MP_all_count;}

		$MP_countS =	sprintf("%7s", $MP_count); while(strlen($MP_countS)>7) {$MP_countS = substr("$MP_countS", 0, -1);}
		$MP_one_countS =	sprintf("%7s", $MP_one_count); while(strlen($MP_one_countS)>7) {$MP_one_countS = substr("$MP_one_countS", 0, -1);}
		$MP_two_countS =	sprintf("%7s", $MP_two_count); while(strlen($MP_two_countS)>7) {$MP_two_countS = substr("$MP_two_countS", 0, -1);}
		$MP_three_countS =	sprintf("%7s", $MP_three_count); while(strlen($MP_three_countS)>7) {$MP_three_countS = substr("$MP_three_countS", 0, -1);}
		$MP_four_countS =	sprintf("%7s", $MP_four_count); while(strlen($MP_four_countS)>7) {$MP_four_countS = substr("$MP_four_countS", 0, -1);}
		$MP_five_countS =	sprintf("%7s", $MP_five_count); while(strlen($MP_five_countS)>7) {$MP_five_countS = substr("$MP_five_countS", 0, -1);}

		$MP_count_tot =	($MP_count + $MP_count_tot);
		$MP_one_count_tot =	($MP_one_count + $MP_one_count_tot);
		$MP_two_count_tot =	($MP_two_count + $MP_two_count_tot);
		$MP_three_count_tot =	($MP_three_count + $MP_three_count_tot);
		$MP_four_count_tot =	($MP_four_count + $MP_four_count_tot);
		$MP_five_count_tot =	($MP_five_count + $MP_five_count_tot);

		########## END COMPLETED (Completed Callback flag) ##########
		########################################################


		########################################################
		########## BEGIN COMPLETED RATIO (Completed flag out of total leads percentage) ##########

		$MR_count=$MP_count; 
		$MR_one_count=$MP_one_count;
		$MR_two_count=$MP_two_count;
		$MR_three_count=$MP_three_count;
		$MR_four_count=$MP_four_count;
		$MR_five_count=$MP_five_count;
		$MR_all_count=$MP_all_count;

		$MR_count_pct=0;
		$MR_one_count_pct=0;
		$MR_two_count_pct=0;
		$MR_three_count_pct=0;
		$MR_four_count_pct=0;
		$MR_five_count_pct=0;
		$MR_count_pct = (MathZDC($MR_count, $LISTIDcalls[$i]) * 100);
		$MR_one_count_pct = (MathZDC($MR_one_count, $LISTIDcalls[$i]) * 100);
		$MR_two_count_pct = (MathZDC($MR_two_count, $LISTIDcalls[$i]) * 100);
		$MR_three_count_pct = (MathZDC($MR_three_count, $LISTIDcalls[$i]) * 100);
		$MR_four_count_pct = (MathZDC($MR_four_count, $LISTIDcalls[$i]) * 100);
		$MR_five_count_pct = (MathZDC($MR_five_count, $LISTIDcalls[$i]) * 100);

		$MR_countS =	sprintf("%6.2f", $MR_count_pct); while(strlen($MR_countS)>7) {$MR_countS = substr("$MR_countS", 0, -1);}
		$MR_one_countS =	sprintf("%6.2f", $MR_one_count_pct); while(strlen($MR_one_countS)>7) {$MR_one_countS = substr("$MR_one_countS", 0, -1);}
		$MR_two_countS =	sprintf("%6.2f", $MR_two_count_pct); while(strlen($MR_two_countS)>7) {$MR_two_countS = substr("$MR_two_countS", 0, -1);}
		$MR_three_countS =	sprintf("%6.2f", $MR_three_count_pct); while(strlen($MR_three_countS)>7) {$MR_three_countS = substr("$MR_three_countS", 0, -1);}
		$MR_four_countS =	sprintf("%6.2f", $MR_four_count_pct); while(strlen($MR_four_countS)>7) {$MR_four_countS = substr("$MR_four_countS", 0, -1);}
		$MR_five_countS =	sprintf("%6.2f", $MR_five_count_pct); while(strlen($MR_five_countS)>7) {$MR_five_countS = substr("$MR_five_countS", 0, -1);}

		$MR_count_tot =	($MR_count + $MR_count_tot);
		$MR_one_count_tot =	($MR_one_count + $MR_one_count_tot);
		$MR_two_count_tot =	($MR_two_count + $MR_two_count_tot);
		$MR_three_count_tot =	($MR_three_count + $MR_three_count_tot);
		$MR_four_count_tot =	($MR_four_count + $MR_four_count_tot);
		$MR_five_count_tot =	($MR_five_count + $MR_five_count_tot);

		########## END   COMPLETED RATIO (Completed flag out of total leads percentage) ##########
		########################################################



		$OUToutput .= "| $LISTIDentry_dateS | $LISTIDname | $LISTIDcampaignS | $LISTIDcount | $LISTIDlist_active[$i] ";
		$OUToutput .= "| $HA_one_countS | $HA_two_countS | $HA_three_countS | $HA_four_countS | $HA_five_countS | $HA_countS ";
		$OUToutput .= "| $HR_one_countS% | $HR_two_countS% | $HR_three_countS% | $HR_four_countS% | $HR_five_countS% | $HR_countS% ";
		$OUToutput .= "| $SA_one_countS | $SA_two_countS | $SA_three_countS | $SA_four_countS | $SA_five_countS | $SA_countS ";
		$OUToutput .= "| $SR_one_countS% | $SR_two_countS% | $SR_three_countS% | $SR_four_countS% | $SR_five_countS% | $SR_countS% ";
		$OUToutput .= "| $DN_one_countS | $DN_two_countS | $DN_three_countS | $DN_four_countS | $DN_five_countS | $DN_countS ";
		$OUToutput .= "| $DR_one_countS% | $DR_two_countS% | $DR_three_countS% | $DR_four_countS% | $DR_five_countS% | $DR_countS% ";
		$OUToutput .= "| $CC_one_countS | $CC_two_countS | $CC_three_countS | $CC_four_countS | $CC_five_countS | $CC_countS ";
		$OUToutput .= "| $CR_one_countS% | $CR_two_countS% | $CR_three_countS% | $CR_four_countS% | $CR_five_countS% | $CR_countS% ";
		$OUToutput .= "| $UW_one_countS | $UW_two_countS | $UW_three_countS | $UW_four_countS | $UW_five_countS | $UW_countS ";
		$OUToutput .= "| $UR_one_countS% | $UR_two_countS% | $UR_three_countS% | $UR_four_countS% | $UR_five_countS% | $UR_countS% ";
		$OUToutput .= "| $BA_one_countS | $BA_two_countS | $BA_three_countS | $BA_four_countS | $BA_five_countS | $BA_countS ";
		$OUToutput .= "| $BR_one_countS% | $BR_two_countS% | $BR_three_countS% | $BR_four_countS% | $BR_five_countS% | $BR_countS% ";
		$OUToutput .= "| $MP_one_countS | $MP_two_countS | $MP_three_countS | $MP_four_countS | $MP_five_countS | $MP_countS ";
		$OUToutput .= "| $MR_one_countS% | $MR_two_countS% | $MR_three_countS% | $MR_four_countS% | $MR_five_countS% | $MR_countS% ";
		$OUToutput .= "|\n";

		$CSV_text1.="\"$LISTIDentry_dateS\",\"$LISTIDname\",\"$LISTIDcampaignS\",\"$LISTIDcount\",\"$LISTIDlist_active[$i]\"";
		$CSV_text1.=",\"$HA_one_countS\",\"$HA_two_countS\",\"$HA_three_countS\",\"$HA_four_countS\",\"$HA_five_countS\",\"$HA_countS\"";
		$CSV_text1.=",\"$HR_one_countS%\",\"$HR_two_countS%\",\"$HR_three_countS%\",\"$HR_four_countS%\",\"$HR_five_countS%\",\"$HR_countS%\"";
		$CSV_text1.=",\"$SA_one_countS\",\"$SA_two_countS\",\"$SA_three_countS\",\"$SA_four_countS\",\"$SA_five_countS\",\"$SA_countS\"";
		$CSV_text1.=",\"$SR_one_countS%\",\"$SR_two_countS%\",\"$SR_three_countS%\",\"$SR_four_countS%\",\"$SR_five_countS%\",\"$SR_countS%\"";
		$CSV_text1.=",\"$DN_one_countS\",\"$DN_two_countS\",\"$DN_three_countS\",\"$DN_four_countS\",\"$DN_five_countS\",\"$DN_countS\"";
		$CSV_text1.=",\"$DR_one_countS%\",\"$DR_two_countS%\",\"$DR_three_countS%\",\"$DR_four_countS%\",\"$DR_five_countS%\",\"$DR_countS%\"";
		$CSV_text1.=",\"$CC_one_countS\",\"$CC_two_countS\",\"$CC_three_countS\",\"$CC_four_countS\",\"$CC_five_countS\",\"$CC_countS\"";
		$CSV_text1.=",\"$CR_one_countS%\",\"$CR_two_countS%\",\"$CR_three_countS%\",\"$CR_four_countS%\",\"$CR_five_countS%\",\"$CR_countS%\"";
		$CSV_text1.=",\"$UW_one_countS\",\"$UW_two_countS\",\"$UW_three_countS\",\"$UW_four_countS\",\"$UW_five_countS\",\"$UW_countS\"";
		$CSV_text1.=",\"$UR_one_countS%\",\"$UR_two_countS%\",\"$UR_three_countS%\",\"$UR_four_countS%\",\"$UR_five_countS%\",\"$UR_countS%\"";
		$CSV_text1.=",\"$BA_one_countS\",\"$BA_two_countS\",\"$BA_three_countS\",\"$BA_four_countS\",\"$BA_five_countS\",\"$BA_countS\"";
		$CSV_text1.=",\"$BR_one_countS%\",\"$BR_two_countS%\",\"$BR_three_countS%\",\"$BR_four_countS%\",\"$BR_five_countS%\",\"$BR_countS%\"";
		$CSV_text1.=",\"$MP_one_countS\",\"$MP_two_countS\",\"$MP_three_countS\",\"$MP_four_countS\",\"$MP_five_countS\",\"$MP_countS\"";
		$CSV_text1.=",\"$MR_one_countS%\",\"$MR_two_countS%\",\"$MR_three_countS%\",\"$MR_four_countS%\",\"$MR_five_countS%\",\"$MR_countS%\"";
		$CSV_text1.="\n";
			
		$graph_stats2[$i][1]=$HA_one_countS;
		$graph_stats2[$i][2]=$HA_two_countS;
		$graph_stats2[$i][3]=$HA_three_countS;
		$graph_stats2[$i][4]=$HA_four_countS;
		$graph_stats2[$i][5]=$HA_five_countS;
		$graph_stats2[$i][6]=$HA_countS;
		$graph_stats2[$i][7]=$HR_one_countS;
		$graph_stats2[$i][8]=$HR_two_countS;
		$graph_stats2[$i][9]=$HR_three_countS;
		$graph_stats2[$i][10]=$HR_four_countS;
		$graph_stats2[$i][11]=$HR_five_countS;
		$graph_stats2[$i][12]=$HR_countS;
		$graph_stats2[$i][13]=$SA_one_countS;
		$graph_stats2[$i][14]=$SA_two_countS;
		$graph_stats2[$i][15]=$SA_three_countS;
		$graph_stats2[$i][16]=$SA_four_countS;
		$graph_stats2[$i][17]=$SA_five_countS;
		$graph_stats2[$i][18]=$SA_countS;
		$graph_stats2[$i][19]=$SR_one_countS;
		$graph_stats2[$i][20]=$SR_two_countS;
		$graph_stats2[$i][21]=$SR_three_countS;
		$graph_stats2[$i][22]=$SR_four_countS;
		$graph_stats2[$i][23]=$SR_five_countS;
		$graph_stats2[$i][24]=$SR_countS;
		$graph_stats2[$i][25]=$DN_one_countS;
		$graph_stats2[$i][26]=$DN_two_countS;
		$graph_stats2[$i][27]=$DN_three_countS;
		$graph_stats2[$i][28]=$DN_four_countS;
		$graph_stats2[$i][29]=$DN_five_countS;
		$graph_stats2[$i][30]=$DN_countS;
		$graph_stats2[$i][31]=$DR_one_countS;
		$graph_stats2[$i][32]=$DR_two_countS;
		$graph_stats2[$i][33]=$DR_three_countS;
		$graph_stats2[$i][34]=$DR_four_countS;
		$graph_stats2[$i][35]=$DR_five_countS;
		$graph_stats2[$i][36]=$DR_countS;
		$graph_stats2[$i][37]=$CC_one_countS;
		$graph_stats2[$i][38]=$CC_two_countS;
		$graph_stats2[$i][39]=$CC_three_countS;
		$graph_stats2[$i][40]=$CC_four_countS;
		$graph_stats2[$i][41]=$CC_five_countS;
		$graph_stats2[$i][42]=$CC_countS;
		$graph_stats2[$i][43]=$CR_one_countS;
		$graph_stats2[$i][44]=$CR_two_countS;
		$graph_stats2[$i][45]=$CR_three_countS;
		$graph_stats2[$i][46]=$CR_four_countS;
		$graph_stats2[$i][47]=$CR_five_countS;
		$graph_stats2[$i][48]=$CR_countS;
		$graph_stats2[$i][49]=$UW_one_countS;
		$graph_stats2[$i][50]=$UW_two_countS;
		$graph_stats2[$i][51]=$UW_three_countS;
		$graph_stats2[$i][52]=$UW_four_countS;
		$graph_stats2[$i][53]=$UW_five_countS;
		$graph_stats2[$i][54]=$UW_countS;
		$graph_stats2[$i][55]=$UR_one_countS;
		$graph_stats2[$i][56]=$UR_two_countS;
		$graph_stats2[$i][57]=$UR_three_countS;
		$graph_stats2[$i][58]=$UR_four_countS;
		$graph_stats2[$i][59]=$UR_five_countS;
		$graph_stats2[$i][60]=$UR_countS;
		$graph_stats2[$i][61]=$BA_one_countS;
		$graph_stats2[$i][62]=$BA_two_countS;
		$graph_stats2[$i][63]=$BA_three_countS;
		$graph_stats2[$i][64]=$BA_four_countS;
		$graph_stats2[$i][65]=$BA_five_countS;
		$graph_stats2[$i][66]=$BA_countS;
		$graph_stats2[$i][67]=$BR_one_countS;
		$graph_stats2[$i][68]=$BR_two_countS;
		$graph_stats2[$i][69]=$BR_three_countS;
		$graph_stats2[$i][70]=$BR_four_countS;
		$graph_stats2[$i][71]=$BR_five_countS;
		$graph_stats2[$i][72]=$BR_countS;
		$graph_stats2[$i][73]=$MP_one_countS;
		$graph_stats2[$i][74]=$MP_two_countS;
		$graph_stats2[$i][75]=$MP_three_countS;
		$graph_stats2[$i][76]=$MP_four_countS;
		$graph_stats2[$i][77]=$MP_five_countS;
		$graph_stats2[$i][78]=$MP_countS;
		$graph_stats2[$i][79]=$MR_one_countS;
		$graph_stats2[$i][80]=$MR_two_countS;
		$graph_stats2[$i][81]=$MR_three_countS;
		$graph_stats2[$i][82]=$MR_four_countS;
		$graph_stats2[$i][83]=$MR_five_countS;
		$graph_stats2[$i][84]=$MR_countS;


		$i++;
		}

	// CYCLE THROUGH ARRAY TO SEE IF ANY NEW MAX VARS
	for ($q=0; $q<count($graph_stats2); $q++) {
		for ($x=1; $x<count($graph_stats2[$q]); $x++) {
			$graph_stats2[$q][$x]=trim($graph_stats2[$q][$x]);
			if ($graph_stats2[$q][$x]>$max_stats2[$x]) {$max_stats2[$x]=$graph_stats2[$q][$x];}
		}
	}


	$HA_count_totS =	sprintf("%7s", $HA_count_tot); while(strlen($HA_count_totS)>7) {$HA_count_totS = substr("$HA_count_totS", 0, -1);}
	$HA_one_count_totS =	sprintf("%7s", $HA_one_count_tot); while(strlen($HA_one_count_totS)>7) {$HA_one_count_totS = substr("$HA_one_count_totS", 0, -1);}
	$HA_two_count_totS =	sprintf("%7s", $HA_two_count_tot); while(strlen($HA_two_count_totS)>7) {$HA_two_count_totS = substr("$HA_two_count_totS", 0, -1);}
	$HA_three_count_totS =	sprintf("%7s", $HA_three_count_tot); while(strlen($HA_three_count_totS)>7) {$HA_three_count_totS = substr("$HA_three_count_totS", 0, -1);}
	$HA_four_count_totS =	sprintf("%7s", $HA_four_count_tot); while(strlen($HA_four_count_totS)>7) {$HA_four_count_totS = substr("$HA_four_count_totS", 0, -1);}
	$HA_five_count_totS =	sprintf("%7s", $HA_five_count_tot); while(strlen($HA_five_count_totS)>7) {$HA_five_count_totS = substr("$HA_five_count_totS", 0, -1);}

	$SA_count_totS =	sprintf("%7s", $SA_count_tot); while(strlen($SA_count_totS)>7) {$SA_count_totS = substr("$SA_count_totS", 0, -1);}
	$SA_one_count_totS =	sprintf("%7s", $SA_one_count_tot); while(strlen($SA_one_count_totS)>7) {$SA_one_count_totS = substr("$SA_one_count_totS", 0, -1);}
	$SA_two_count_totS =	sprintf("%7s", $SA_two_count_tot); while(strlen($SA_two_count_totS)>7) {$SA_two_count_totS = substr("$SA_two_count_totS", 0, -1);}
	$SA_three_count_totS =	sprintf("%7s", $SA_three_count_tot); while(strlen($SA_three_count_totS)>7) {$SA_three_count_totS = substr("$SA_three_count_totS", 0, -1);}
	$SA_four_count_totS =	sprintf("%7s", $SA_four_count_tot); while(strlen($SA_four_count_totS)>7) {$SA_four_count_totS = substr("$SA_four_count_totS", 0, -1);}
	$SA_five_count_totS =	sprintf("%7s", $SA_five_count_tot); while(strlen($SA_five_count_totS)>7) {$SA_five_count_totS = substr("$SA_five_count_totS", 0, -1);}

	$DN_count_totS =	sprintf("%7s", $DN_count_tot); while(strlen($DN_count_totS)>7) {$DN_count_totS = substr("$DN_count_totS", 0, -1);}
	$DN_one_count_totS =	sprintf("%7s", $DN_one_count_tot); while(strlen($DN_one_count_totS)>7) {$DN_one_count_totS = substr("$DN_one_count_totS", 0, -1);}
	$DN_two_count_totS =	sprintf("%7s", $DN_two_count_tot); while(strlen($DN_two_count_totS)>7) {$DN_two_count_totS = substr("$DN_two_count_totS", 0, -1);}
	$DN_three_count_totS =	sprintf("%7s", $DN_three_count_tot); while(strlen($DN_three_count_totS)>7) {$DN_three_count_totS = substr("$DN_three_count_totS", 0, -1);}
	$DN_four_count_totS =	sprintf("%7s", $DN_four_count_tot); while(strlen($DN_four_count_totS)>7) {$DN_four_count_totS = substr("$DN_four_count_totS", 0, -1);}
	$DN_five_count_totS =	sprintf("%7s", $DN_five_count_tot); while(strlen($DN_five_count_totS)>7) {$DN_five_count_totS = substr("$DN_five_count_totS", 0, -1);}

	$CC_count_totS =	sprintf("%7s", $CC_count_tot); while(strlen($CC_count_totS)>7) {$CC_count_totS = substr("$CC_count_totS", 0, -1);}
	$CC_one_count_totS =	sprintf("%7s", $CC_one_count_tot); while(strlen($CC_one_count_totS)>7) {$CC_one_count_totS = substr("$CC_one_count_totS", 0, -1);}
	$CC_two_count_totS =	sprintf("%7s", $CC_two_count_tot); while(strlen($CC_two_count_totS)>7) {$CC_two_count_totS = substr("$CC_two_count_totS", 0, -1);}
	$CC_three_count_totS =	sprintf("%7s", $CC_three_count_tot); while(strlen($CC_three_count_totS)>7) {$CC_three_count_totS = substr("$CC_three_count_totS", 0, -1);}
	$CC_four_count_totS =	sprintf("%7s", $CC_four_count_tot); while(strlen($CC_four_count_totS)>7) {$CC_four_count_totS = substr("$CC_four_count_totS", 0, -1);}
	$CC_five_count_totS =	sprintf("%7s", $CC_five_count_tot); while(strlen($CC_five_count_totS)>7) {$CC_five_count_totS = substr("$CC_five_count_totS", 0, -1);}

	$UW_count_totS =	sprintf("%7s", $UW_count_tot); while(strlen($UW_count_totS)>7) {$UW_count_totS = substr("$UW_count_totS", 0, -1);}
	$UW_one_count_totS =	sprintf("%7s", $UW_one_count_tot); while(strlen($UW_one_count_totS)>7) {$UW_one_count_totS = substr("$UW_one_count_totS", 0, -1);}
	$UW_two_count_totS =	sprintf("%7s", $UW_two_count_tot); while(strlen($UW_two_count_totS)>7) {$UW_two_count_totS = substr("$UW_two_count_totS", 0, -1);}
	$UW_three_count_totS =	sprintf("%7s", $UW_three_count_tot); while(strlen($UW_three_count_totS)>7) {$UW_three_count_totS = substr("$UW_three_count_totS", 0, -1);}
	$UW_four_count_totS =	sprintf("%7s", $UW_four_count_tot); while(strlen($UW_four_count_totS)>7) {$UW_four_count_totS = substr("$UW_four_count_totS", 0, -1);}
	$UW_five_count_totS =	sprintf("%7s", $UW_five_count_tot); while(strlen($UW_five_count_totS)>7) {$UW_five_count_totS = substr("$UW_five_count_totS", 0, -1);}

	$BA_count_totS =	sprintf("%7s", $BA_count_tot); while(strlen($BA_count_totS)>7) {$BA_count_totS = substr("$BA_count_totS", 0, -1);}
	$BA_one_count_totS =	sprintf("%7s", $BA_one_count_tot); while(strlen($BA_one_count_totS)>7) {$BA_one_count_totS = substr("$BA_one_count_totS", 0, -1);}
	$BA_two_count_totS =	sprintf("%7s", $BA_two_count_tot); while(strlen($BA_two_count_totS)>7) {$BA_two_count_totS = substr("$BA_two_count_totS", 0, -1);}
	$BA_three_count_totS =	sprintf("%7s", $BA_three_count_tot); while(strlen($BA_three_count_totS)>7) {$BA_three_count_totS = substr("$BA_three_count_totS", 0, -1);}
	$BA_four_count_totS =	sprintf("%7s", $BA_four_count_tot); while(strlen($BA_four_count_totS)>7) {$BA_four_count_totS = substr("$BA_four_count_totS", 0, -1);}
	$BA_five_count_totS =	sprintf("%7s", $BA_five_count_tot); while(strlen($BA_five_count_totS)>7) {$BA_five_count_totS = substr("$BA_five_count_totS", 0, -1);}

	$MP_count_totS =	sprintf("%7s", $MP_count_tot); while(strlen($MP_count_totS)>7) {$MP_count_totS = substr("$MP_count_totS", 0, -1);}
	$MP_one_count_totS =	sprintf("%7s", $MP_one_count_tot); while(strlen($MP_one_count_totS)>7) {$MP_one_count_totS = substr("$MP_one_count_totS", 0, -1);}
	$MP_two_count_totS =	sprintf("%7s", $MP_two_count_tot); while(strlen($MP_two_count_totS)>7) {$MP_two_count_totS = substr("$MP_two_count_totS", 0, -1);}
	$MP_three_count_totS =	sprintf("%7s", $MP_three_count_tot); while(strlen($MP_three_count_totS)>7) {$MP_three_count_totS = substr("$MP_three_count_totS", 0, -1);}
	$MP_four_count_totS =	sprintf("%7s", $MP_four_count_tot); while(strlen($MP_four_count_totS)>7) {$MP_four_count_totS = substr("$MP_four_count_totS", 0, -1);}
	$MP_five_count_totS =	sprintf("%7s", $MP_five_count_tot); while(strlen($MP_five_count_totS)>7) {$MP_five_count_totS = substr("$MP_five_count_totS", 0, -1);}

	$HR_count_Tpc=0;
	$HR_one_count_Tpc=0;
	$HR_two_count_Tpc=0;
	$HR_three_count_Tpc=0;
	$HR_four_count_Tpc=0;
	$HR_five_count_Tpc=0;
	$HR_count_Tpc = (MathZDC($HR_count_tot, $TOTALleads) * 100);
	$HR_one_count_Tpc = (MathZDC($HR_one_count_tot, $TOTALleads) * 100);
	$HR_two_count_Tpc = (MathZDC($HR_two_count_tot, $TOTALleads) * 100);
	$HR_three_count_Tpc = (MathZDC($HR_three_count_tot, $TOTALleads) * 100);
	$HR_four_count_Tpc = (MathZDC($HR_four_count_tot, $TOTALleads) * 100);
	$HR_five_count_Tpc = (MathZDC($HR_five_count_tot, $TOTALleads) * 100);

	$HR_count_totS =	sprintf("%6.2f", $HR_count_Tpc); while(strlen($HR_count_totS)>7) {$HR_count_totS = substr("$HR_count_totS", 0, -1);}
	$HR_one_count_totS =	sprintf("%6.2f", $HR_one_count_Tpc); while(strlen($HR_one_count_totS)>7) {$HR_one_count_totS = substr("$HR_one_count_totS", 0, -1);}
	$HR_two_count_totS =	sprintf("%6.2f", $HR_two_count_Tpc); while(strlen($HR_two_count_totS)>7) {$HR_two_count_totS = substr("$HR_two_count_totS", 0, -1);}
	$HR_three_count_totS =	sprintf("%6.2f", $HR_three_count_Tpc); while(strlen($HR_three_count_totS)>7) {$HR_three_count_totS = substr("$HR_three_count_totS", 0, -1);}
	$HR_four_count_totS =	sprintf("%6.2f", $HR_four_count_Tpc); while(strlen($HR_four_count_totS)>7) {$HR_four_count_totS = substr("$HR_four_count_totS", 0, -1);}
	$HR_five_count_totS =	sprintf("%6.2f", $HR_five_count_Tpc); while(strlen($HR_five_count_totS)>7) {$HR_five_count_totS = substr("$HR_five_count_totS", 0, -1);}

	$SR_count_Tpc=0;
	$SR_one_count_Tpc=0;
	$SR_two_count_Tpc=0;
	$SR_three_count_Tpc=0;
	$SR_four_count_Tpc=0;
	$SR_five_count_Tpc=0;
	$SR_count_Tpc = (MathZDC($SR_count_tot, $TOTALleads) * 100);
	$SR_one_count_Tpc = (MathZDC($SR_one_count_tot, $TOTALleads) * 100);
	$SR_two_count_Tpc = (MathZDC($SR_two_count_tot, $TOTALleads) * 100);
	$SR_three_count_Tpc = (MathZDC($SR_three_count_tot, $TOTALleads) * 100);
	$SR_four_count_Tpc = (MathZDC($SR_four_count_tot, $TOTALleads) * 100);
	$SR_five_count_Tpc = (MathZDC($SR_five_count_tot, $TOTALleads) * 100);

	$SR_count_totS =	sprintf("%6.2f", $SR_count_Tpc); while(strlen($SR_count_totS)>7) {$SR_count_totS = substr("$SR_count_totS", 0, -1);}
	$SR_one_count_totS =	sprintf("%6.2f", $SR_one_count_Tpc); while(strlen($SR_one_count_totS)>7) {$SR_one_count_totS = substr("$SR_one_count_totS", 0, -1);}
	$SR_two_count_totS =	sprintf("%6.2f", $SR_two_count_Tpc); while(strlen($SR_two_count_totS)>7) {$SR_two_count_totS = substr("$SR_two_count_totS", 0, -1);}
	$SR_three_count_totS =	sprintf("%6.2f", $SR_three_count_Tpc); while(strlen($SR_three_count_totS)>7) {$SR_three_count_totS = substr("$SR_three_count_totS", 0, -1);}
	$SR_four_count_totS =	sprintf("%6.2f", $SR_four_count_Tpc); while(strlen($SR_four_count_totS)>7) {$SR_four_count_totS = substr("$SR_four_count_totS", 0, -1);}
	$SR_five_count_totS =	sprintf("%6.2f", $SR_five_count_Tpc); while(strlen($SR_five_count_totS)>7) {$SR_five_count_totS = substr("$SR_five_count_totS", 0, -1);}

	$DR_count_Tpc=0;
	$DR_one_count_Tpc=0;
	$DR_two_count_Tpc=0;
	$DR_three_count_Tpc=0;
	$DR_four_count_Tpc=0;
	$DR_five_count_Tpc=0;
	$DR_count_Tpc = (MathZDC($DR_count_tot, $TOTALleads) * 100);
	$DR_one_count_Tpc = (MathZDC($DR_one_count_tot, $TOTALleads) * 100);
	$DR_two_count_Tpc = (MathZDC($DR_two_count_tot, $TOTALleads) * 100);
	$DR_three_count_Tpc = (MathZDC($DR_three_count_tot, $TOTALleads) * 100);
	$DR_four_count_Tpc = (MathZDC($DR_four_count_tot, $TOTALleads) * 100);
	$DR_five_count_Tpc = (MathZDC($DR_five_count_tot, $TOTALleads) * 100);

	$DR_count_totS =	sprintf("%6.2f", $DR_count_Tpc); while(strlen($DR_count_totS)>7) {$DR_count_totS = substr("$DR_count_totS", 0, -1);}
	$DR_one_count_totS =	sprintf("%6.2f", $DR_one_count_Tpc); while(strlen($DR_one_count_totS)>7) {$DR_one_count_totS = substr("$DR_one_count_totS", 0, -1);}
	$DR_two_count_totS =	sprintf("%6.2f", $DR_two_count_Tpc); while(strlen($DR_two_count_totS)>7) {$DR_two_count_totS = substr("$DR_two_count_totS", 0, -1);}
	$DR_three_count_totS =	sprintf("%6.2f", $DR_three_count_Tpc); while(strlen($DR_three_count_totS)>7) {$DR_three_count_totS = substr("$DR_three_count_totS", 0, -1);}
	$DR_four_count_totS =	sprintf("%6.2f", $DR_four_count_Tpc); while(strlen($DR_four_count_totS)>7) {$DR_four_count_totS = substr("$DR_four_count_totS", 0, -1);}
	$DR_five_count_totS =	sprintf("%6.2f", $DR_five_count_Tpc); while(strlen($DR_five_count_totS)>7) {$DR_five_count_totS = substr("$DR_five_count_totS", 0, -1);}

	$CR_count_Tpc=0;
	$CR_one_count_Tpc=0;
	$CR_two_count_Tpc=0;
	$CR_three_count_Tpc=0;
	$CR_four_count_Tpc=0;
	$CR_five_count_Tpc=0;
	$CR_count_Tpc = (MathZDC($CR_count_tot, $TOTALleads) * 100);
	$CR_one_count_Tpc = (MathZDC($CR_one_count_tot, $TOTALleads) * 100);
	$CR_two_count_Tpc = (MathZDC($CR_two_count_tot, $TOTALleads) * 100);
	$CR_three_count_Tpc = (MathZDC($CR_three_count_tot, $TOTALleads) * 100);
	$CR_four_count_Tpc = (MathZDC($CR_four_count_tot, $TOTALleads) * 100);
	$CR_five_count_Tpc = (MathZDC($CR_five_count_tot, $TOTALleads) * 100);

	$CR_count_totS =	sprintf("%6.2f", $CR_count_Tpc); while(strlen($CR_count_totS)>7) {$CR_count_totS = substr("$CR_count_totS", 0, -1);}
	$CR_one_count_totS =	sprintf("%6.2f", $CR_one_count_Tpc); while(strlen($CR_one_count_totS)>7) {$CR_one_count_totS = substr("$CR_one_count_totS", 0, -1);}
	$CR_two_count_totS =	sprintf("%6.2f", $CR_two_count_Tpc); while(strlen($CR_two_count_totS)>7) {$CR_two_count_totS = substr("$CR_two_count_totS", 0, -1);}
	$CR_three_count_totS =	sprintf("%6.2f", $CR_three_count_Tpc); while(strlen($CR_three_count_totS)>7) {$CR_three_count_totS = substr("$CR_three_count_totS", 0, -1);}
	$CR_four_count_totS =	sprintf("%6.2f", $CR_four_count_Tpc); while(strlen($CR_four_count_totS)>7) {$CR_four_count_totS = substr("$CR_four_count_totS", 0, -1);}
	$CR_five_count_totS =	sprintf("%6.2f", $CR_five_count_Tpc); while(strlen($CR_five_count_totS)>7) {$CR_five_count_totS = substr("$CR_five_count_totS", 0, -1);}

	$UR_count_Tpc=0;
	$UR_one_count_Tpc=0;
	$UR_two_count_Tpc=0;
	$UR_three_count_Tpc=0;
	$UR_four_count_Tpc=0;
	$UR_five_count_Tpc=0;
	$UR_count_Tpc = (MathZDC($UR_count_tot, $TOTALleads) * 100);
	$UR_one_count_Tpc = (MathZDC($UR_one_count_tot, $TOTALleads) * 100);
	$UR_two_count_Tpc = (MathZDC($UR_two_count_tot, $TOTALleads) * 100);
	$UR_three_count_Tpc = (MathZDC($UR_three_count_tot, $TOTALleads) * 100);
	$UR_four_count_Tpc = (MathZDC($UR_four_count_tot, $TOTALleads) * 100);
	$UR_five_count_Tpc = (MathZDC($UR_five_count_tot, $TOTALleads) * 100);

	$UR_count_totS =	sprintf("%6.2f", $UR_count_Tpc); while(strlen($UR_count_totS)>7) {$UR_count_totS = substr("$UR_count_totS", 0, -1);}
	$UR_one_count_totS =	sprintf("%6.2f", $UR_one_count_Tpc); while(strlen($UR_one_count_totS)>7) {$UR_one_count_totS = substr("$UR_one_count_totS", 0, -1);}
	$UR_two_count_totS =	sprintf("%6.2f", $UR_two_count_Tpc); while(strlen($UR_two_count_totS)>7) {$UR_two_count_totS = substr("$UR_two_count_totS", 0, -1);}
	$UR_three_count_totS =	sprintf("%6.2f", $UR_three_count_Tpc); while(strlen($UR_three_count_totS)>7) {$UR_three_count_totS = substr("$UR_three_count_totS", 0, -1);}
	$UR_four_count_totS =	sprintf("%6.2f", $UR_four_count_Tpc); while(strlen($UR_four_count_totS)>7) {$UR_four_count_totS = substr("$UR_four_count_totS", 0, -1);}
	$UR_five_count_totS =	sprintf("%6.2f", $UR_five_count_Tpc); while(strlen($UR_five_count_totS)>7) {$UR_five_count_totS = substr("$UR_five_count_totS", 0, -1);}

	$BR_count_Tpc=0;
	$BR_one_count_Tpc=0;
	$BR_two_count_Tpc=0;
	$BR_three_count_Tpc=0;
	$BR_four_count_Tpc=0;
	$BR_five_count_Tpc=0;
	$BR_count_Tpc = (MathZDC($BR_count_tot, $TOTALleads) * 100);
	$BR_one_count_Tpc = (MathZDC($BR_one_count_tot, $TOTALleads) * 100);
	$BR_two_count_Tpc = (MathZDC($BR_two_count_tot, $TOTALleads) * 100);
	$BR_three_count_Tpc = (MathZDC($BR_three_count_tot, $TOTALleads) * 100);
	$BR_four_count_Tpc = (MathZDC($BR_four_count_tot, $TOTALleads) * 100);
	$BR_five_count_Tpc = (MathZDC($BR_five_count_tot, $TOTALleads) * 100);

	$BR_count_totS =	sprintf("%6.2f", $BR_count_Tpc); while(strlen($BR_count_totS)>7) {$BR_count_totS = substr("$BR_count_totS", 0, -1);}
	$BR_one_count_totS =	sprintf("%6.2f", $BR_one_count_Tpc); while(strlen($BR_one_count_totS)>7) {$BR_one_count_totS = substr("$BR_one_count_totS", 0, -1);}
	$BR_two_count_totS =	sprintf("%6.2f", $BR_two_count_Tpc); while(strlen($BR_two_count_totS)>7) {$BR_two_count_totS = substr("$BR_two_count_totS", 0, -1);}
	$BR_three_count_totS =	sprintf("%6.2f", $BR_three_count_Tpc); while(strlen($BR_three_count_totS)>7) {$BR_three_count_totS = substr("$BR_three_count_totS", 0, -1);}
	$BR_four_count_totS =	sprintf("%6.2f", $BR_four_count_Tpc); while(strlen($BR_four_count_totS)>7) {$BR_four_count_totS = substr("$BR_four_count_totS", 0, -1);}
	$BR_five_count_totS =	sprintf("%6.2f", $BR_five_count_Tpc); while(strlen($BR_five_count_totS)>7) {$BR_five_count_totS = substr("$BR_five_count_totS", 0, -1);}

	$MR_count_Tpc=0;
	$MR_one_count_Tpc=0;
	$MR_two_count_Tpc=0;
	$MR_three_count_Tpc=0;
	$MR_four_count_Tpc=0;
	$MR_five_count_Tpc=0;
	$MR_count_Tpc = (MathZDC($MR_count_tot, $TOTALleads) * 100);
	$MR_one_count_Tpc = (MathZDC($MR_one_count_tot, $TOTALleads) * 100);
	$MR_two_count_Tpc = (MathZDC($MR_two_count_tot, $TOTALleads) * 100);
	$MR_three_count_Tpc = (MathZDC($MR_three_count_tot, $TOTALleads) * 100);
	$MR_four_count_Tpc = (MathZDC($MR_four_count_tot, $TOTALleads) * 100);
	$MR_five_count_Tpc = (MathZDC($MR_five_count_tot, $TOTALleads) * 100);

	$MR_count_totS =	sprintf("%6.2f", $MR_count_Tpc); while(strlen($MR_count_totS)>7) {$MR_count_totS = substr("$MR_count_totS", 0, -1);}
	$MR_one_count_totS =	sprintf("%6.2f", $MR_one_count_Tpc); while(strlen($MR_one_count_totS)>7) {$MR_one_count_totS = substr("$MR_one_count_totS", 0, -1);}
	$MR_two_count_totS =	sprintf("%6.2f", $MR_two_count_Tpc); while(strlen($MR_two_count_totS)>7) {$MR_two_count_totS = substr("$MR_two_count_totS", 0, -1);}
	$MR_three_count_totS =	sprintf("%6.2f", $MR_three_count_Tpc); while(strlen($MR_three_count_totS)>7) {$MR_three_count_totS = substr("$MR_three_count_totS", 0, -1);}
	$MR_four_count_totS =	sprintf("%6.2f", $MR_four_count_Tpc); while(strlen($MR_four_count_totS)>7) {$MR_four_count_totS = substr("$MR_four_count_totS", 0, -1);}
	$MR_five_count_totS =	sprintf("%6.2f", $MR_five_count_Tpc); while(strlen($MR_five_count_totS)>7) {$MR_five_count_totS = substr("$MR_five_count_totS", 0, -1);}


	$TOTALleads =		sprintf("%10s", $TOTALleads);

	$OUToutput .= "+------------+------------------------------------------+----------+------------+----------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "\n";

	$OUToutput .= "             | "._QXZ("TOTALS",17,"r").":                                  | $TOTALleads |          |";
	$OUToutput .= " $HA_one_count_totS | $HA_two_count_totS | $HA_three_count_totS | $HA_four_count_totS | $HA_five_count_totS | $HA_count_totS |";
	$OUToutput .= " $HR_one_count_totS% | $HR_two_count_totS% | $HR_three_count_totS% | $HR_four_count_totS% | $HR_five_count_totS% | $HR_count_totS% |";
	$OUToutput .= " $SA_one_count_totS | $SA_two_count_totS | $SA_three_count_totS | $SA_four_count_totS | $SA_five_count_totS | $SA_count_totS |";
	$OUToutput .= " $SR_one_count_totS% | $SR_two_count_totS% | $SR_three_count_totS% | $SR_four_count_totS% | $SR_five_count_totS% | $SR_count_totS% |";
	$OUToutput .= " $DN_one_count_totS | $DN_two_count_totS | $DN_three_count_totS | $DN_four_count_totS | $DN_five_count_totS | $DN_count_totS |";
	$OUToutput .= " $DR_one_count_totS% | $DR_two_count_totS% | $DR_three_count_totS% | $DR_four_count_totS% | $DR_five_count_totS% | $DR_count_totS% |";
	$OUToutput .= " $CC_one_count_totS | $CC_two_count_totS | $CC_three_count_totS | $CC_four_count_totS | $CC_five_count_totS | $CC_count_totS |";
	$OUToutput .= " $CR_one_count_totS% | $CR_two_count_totS% | $CR_three_count_totS% | $CR_four_count_totS% | $CR_five_count_totS% | $CR_count_totS% |";
	$OUToutput .= " $UW_one_count_totS | $UW_two_count_totS | $UW_three_count_totS | $UW_four_count_totS | $UW_five_count_totS | $UW_count_totS |";
	$OUToutput .= " $UR_one_count_totS% | $UR_two_count_totS% | $UR_three_count_totS% | $UR_four_count_totS% | $UR_five_count_totS% | $UR_count_totS% |";
	$OUToutput .= " $BA_one_count_totS | $BA_two_count_totS | $BA_three_count_totS | $BA_four_count_totS | $BA_five_count_totS | $BA_count_totS |";
	$OUToutput .= " $BR_one_count_totS% | $BR_two_count_totS% | $BR_three_count_totS% | $BR_four_count_totS% | $BR_five_count_totS% | $BR_count_totS% |";
	$OUToutput .= " $MP_one_count_totS | $MP_two_count_totS | $MP_three_count_totS | $MP_four_count_totS | $MP_five_count_totS | $MP_count_totS |";
	$OUToutput .= " $MR_one_count_totS% | $MR_two_count_totS% | $MR_three_count_totS% | $MR_four_count_totS% | $MR_five_count_totS% | $MR_count_totS% |";
	$OUToutput .= "\n";

	$OUToutput .= "             +------------------------------------------+----------+------------+          +";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "---------+---------+---------+---------+---------+---------+";
	$OUToutput .= "\n";

	$CSV_text1.="\"\",\"\",\""._QXZ("TOTAL")."\",\"$TOTALleads\",\"\"";
	$CSV_text1.=",\"$HA_one_count_totS\",\"$HA_two_count_totS\",\"$HA_three_count_totS\",\"$HA_four_count_totS\",\"$HA_five_count_totS\",\"$HA_count_totS\"";
	$CSV_text1.=",\"$HR_one_count_totS%\",\"$HR_two_count_totS%\",\"$HR_three_count_totS%\",\"$HR_four_count_totS%\",\"$HR_five_count_totS%\",\"$HR_count_totS%\"";
	$CSV_text1.=",\"$SA_one_count_totS\",\"$SA_two_count_totS\",\"$SA_three_count_totS\",\"$SA_four_count_totS\",\"$SA_five_count_totS\",\"$SA_count_totS\"";
	$CSV_text1.=",\"$SR_one_count_totS%\",\"$SR_two_count_totS%\",\"$SR_three_count_totS%\",\"$SR_four_count_totS%\",\"$SR_five_count_totS%\",\"$SR_count_totS%\"";
	$CSV_text1.=",\"$DN_one_count_totS\",\"$DN_two_count_totS\",\"$DN_three_count_totS\",\"$DN_four_count_totS\",\"$DN_five_count_totS\",\"$DN_count_totS\"";
	$CSV_text1.=",\"$DR_one_count_totS%\",\"$DR_two_count_totS%\",\"$DR_three_count_totS%\",\"$DR_four_count_totS%\",\"$DR_five_count_totS%\",\"$DR_count_totS%\"";
	$CSV_text1.=",\"$CC_one_count_totS\",\"$CC_two_count_totS\",\"$CC_three_count_totS\",\"$CC_four_count_totS\",\"$CC_five_count_totS\",\"$CC_count_totS\"";
	$CSV_text1.=",\"$CR_one_count_totS%\",\"$CR_two_count_totS%\",\"$CR_three_count_totS%\",\"$CR_four_count_totS%\",\"$CR_five_count_totS%\",\"$CR_count_totS%\"";
	$CSV_text1.=",\"$UW_one_count_totS\",\"$UW_two_count_totS\",\"$UW_three_count_totS\",\"$UW_four_count_totS\",\"$UW_five_count_totS\",\"$UW_count_totS\"";
	$CSV_text1.=",\"$UR_one_count_totS%\",\"$UR_two_count_totS%\",\"$UR_three_count_totS%\",\"$UR_four_count_totS%\",\"$UR_five_count_totS%\",\"$UR_count_totS%\"";
	$CSV_text1.=",\"$BA_one_count_totS\",\"$BA_two_count_totS\",\"$BA_three_count_totS\",\"$BA_four_count_totS\",\"$BA_five_count_totS\",\"$BA_count_totS\"";
	$CSV_text1.=",\"$BR_one_count_totS%\",\"$BR_two_count_totS%\",\"$BR_three_count_totS%\",\"$BR_four_count_totS%\",\"$BR_five_count_totS%\",\"$BR_count_totS%\"";
	$CSV_text1.=",\"$MP_one_count_totS\",\"$MP_two_count_totS\",\"$MP_three_count_totS\",\"$MP_four_count_totS\",\"$MP_five_count_totS\",\"$MP_count_totS\"";
	$CSV_text1.=",\"$MR_one_count_totS%\",\"$MR_two_count_totS%\",\"$MR_three_count_totS%\",\"$MR_four_count_totS%\",\"$MR_five_count_totS%\",\"$MR_count_totS%\"";
	$CSV_text1.="\n";

	$totals2[1]=$HA_one_count_totS;
	$totals2[2]=$HA_two_count_totS;
	$totals2[3]=$HA_three_count_totS;
	$totals2[4]=$HA_four_count_totS;
	$totals2[5]=$HA_five_count_totS;
	$totals2[6]=$HA_count_totS;
	$totals2[7]=$HR_one_count_totS;
	$totals2[8]=$HR_two_count_totS;
	$totals2[9]=$HR_three_count_totS;
	$totals2[10]=$HR_four_count_totS;
	$totals2[11]=$HR_five_count_totS;
	$totals2[12]=$HR_count_totS;
	$totals2[13]=$SA_one_count_totS;
	$totals2[14]=$SA_two_count_totS;
	$totals2[15]=$SA_three_count_totS;
	$totals2[16]=$SA_four_count_totS;
	$totals2[17]=$SA_five_count_totS;
	$totals2[18]=$SA_count_totS;
	$totals2[19]=$SR_one_count_totS;
	$totals2[20]=$SR_two_count_totS;
	$totals2[21]=$SR_three_count_totS;
	$totals2[22]=$SR_four_count_totS;
	$totals2[23]=$SR_five_count_totS;
	$totals2[24]=$SR_count_totS;
	$totals2[25]=$DN_one_count_totS;
	$totals2[26]=$DN_two_count_totS;
	$totals2[27]=$DN_three_count_totS;
	$totals2[28]=$DN_four_count_totS;
	$totals2[29]=$DN_five_count_totS;
	$totals2[30]=$DN_count_totS;
	$totals2[31]=$DR_one_count_totS;
	$totals2[32]=$DR_two_count_totS;
	$totals2[33]=$DR_three_count_totS;
	$totals2[34]=$DR_four_count_totS;
	$totals2[35]=$DR_five_count_totS;
	$totals2[36]=$DR_count_totS;
	$totals2[37]=$CC_one_count_totS;
	$totals2[38]=$CC_two_count_totS;
	$totals2[39]=$CC_three_count_totS;
	$totals2[40]=$CC_four_count_totS;
	$totals2[41]=$CC_five_count_totS;
	$totals2[42]=$CC_count_totS;
	$totals2[43]=$CR_one_count_totS;
	$totals2[44]=$CR_two_count_totS;
	$totals2[45]=$CR_three_count_totS;
	$totals2[46]=$CR_four_count_totS;
	$totals2[47]=$CR_five_count_totS;
	$totals2[48]=$CR_count_totS;
	$totals2[49]=$UW_one_count_totS;
	$totals2[50]=$UW_two_count_totS;
	$totals2[51]=$UW_three_count_totS;
	$totals2[52]=$UW_four_count_totS;
	$totals2[53]=$UW_five_count_totS;
	$totals2[54]=$UW_count_totS;
	$totals2[55]=$UR_one_count_totS;
	$totals2[56]=$UR_two_count_totS;
	$totals2[57]=$UR_three_count_totS;
	$totals2[58]=$UR_four_count_totS;
	$totals2[59]=$UR_five_count_totS;
	$totals2[60]=$UR_count_totS;
	$totals2[61]=$BA_one_count_totS;
	$totals2[62]=$BA_two_count_totS;
	$totals2[63]=$BA_three_count_totS;
	$totals2[64]=$BA_four_count_totS;
	$totals2[65]=$BA_five_count_totS;
	$totals2[66]=$BA_count_totS;
	$totals2[67]=$BR_one_count_totS;
	$totals2[68]=$BR_two_count_totS;
	$totals2[69]=$BR_three_count_totS;
	$totals2[70]=$BR_four_count_totS;
	$totals2[71]=$BR_five_count_totS;
	$totals2[72]=$BR_count_totS;
	$totals2[73]=$MP_one_count_totS;
	$totals2[74]=$MP_two_count_totS;
	$totals2[75]=$MP_three_count_totS;
	$totals2[76]=$MP_four_count_totS;
	$totals2[77]=$MP_five_count_totS;
	$totals2[78]=$MP_count_totS;
	$totals2[79]=$MR_one_count_totS;
	$totals2[80]=$MR_two_count_totS;
	$totals2[81]=$MR_three_count_totS;
	$totals2[82]=$MR_four_count_totS;
	$totals2[83]=$MR_five_count_totS;
	$totals2[84]=$MR_count_totS;

	# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
	$multigraph_text="";
	$graph_id++;
	$graph_array=array("LIS_CONTACTS1data|1|CONTACTS 1st PASS|integer|", "LIS_CONTACTS2data|2|CONTACTS 2nd PASS|integer|", "LIS_CONTACTS3data|3|CONTACTS 3rd PASS|integer|", "LIS_CONTACTS4data|4|CONTACTS 4th PASS|integer|", "LIS_CONTACTS5data|5|CONTACTS 5th PASS|integer|", "LIS_CONTACTSLIFEdata|6|CONTACTS LIFE|integer|", "LIS_CNTRATE1data|7|CNT RATE 1st PASS|decimal|", "LIS_CNTRATE2data|8|CNT RATE 2nd PASS|decimal|", "LIS_CNTRATE3data|9|CNT RATE 3rd PASS|decimal|", "LIS_CNTRATE4data|10|CNT RATE 4th PASS|decimal|", "LIS_CNTRATE5data|11|CNT RATE 5th PASS|decimal|", "LIS_CNTRATELIFEdata|12|CNT RATE LIFE|decimal|", "LIS_SALES1data|13|SALES 1st PASS|integer|", "LIS_SALES2data|14|SALES 2nd PASS|integer|", "LIS_SALES3data|15|SALES 3rd PASS|integer|", "LIS_SALES4data|16|SALES 4th PASS|integer|", "LIS_SALES5data|17|SALES 5th PASS|integer|", "LIS_SALESLIFEdata|18|SALES LIFE|integer|", "LIS_CONVRATE1data|19|CONV RATE 1st PASS|decimal|", "LIS_CONVRATE2data|20|CONV RATE 2nd PASS|decimal|", "LIS_CONVRATE3data|21|CONV RATE 3rd PASS|decimal|", "LIS_CONVRATE4data|22|CONV RATE 4th PASS|decimal|", "LIS_CONVRATE5data|23|CONV RATE 5th PASS|decimal|", "LIS_CONVLIFE1data|24|CONV RATE LIFE|decimal|", "LIS_DNC1data|25|DNC 1st PASS|integer|", "LIS_DNC2data|26|DNC 2nd PASS|integer|", "LIS_DNC3data|27|DNC 3rd PASS|integer|", "LIS_DNC4data|28|DNC 4th PASS|integer|", "LIS_DNC5data|29|DNC 5th PASS|integer|", "LIS_DNCLIFEdata|30|DNC LIFE|integer|", "LIS_DNCRATE1data|31|DNC RATE 1st PASS|decimal|", "LIS_DNCRATE2data|32|DNC RATE 2nd PASS|decimal|", "LIS_DNCRATE3data|33|DNC RATE 3rd PASS|decimal|", "LIS_DNCRATE4data|34|DNC RATE 4th PASS|decimal|", "LIS_DNCRATE5data|35|DNC RATE 5th PASS|decimal|", "LIS_DNCRATELIFEdata|36|DNC RATE LIFE|decimal|", "LIS_CUSTCNT1data|37|CUST CNT 1st PASS|integer|", "LIS_CUSTCNT2data|38|CUST CNT 2nd PASS|integer|", "LIS_CUSTCNT3data|39|CUST CNT 3rd PASS|integer|", "LIS_CUSTCNT4data|40|CUST CNT 4th PASS|integer|", "LIS_CUSTCNT5data|41|CUST CNT 5th PASS|integer|", "LIS_CUSTCNTLIFEdata|42|CUST CNT LIFE|integer|", "LIS_CUSCNTRATE1data|43|CUST CUSTCNT RATE 1st PASS|decimal|", "LIS_CUSCNTRATE2data|44|CUST CUSTCNT RATE 2nd PASS|decimal|", "LIS_CUSCNTRATE3data|45|CUST CUSTCNT RATE 3rd PASS|decimal|", "LIS_CUSCNTRATE4data|46|CUST CUSTCNT RATE 4th PASS|decimal|", "LIS_CUSCNTRATE5data|47|CUST CUSTCNT RATE 5th PASS|decimal|", "LIS_CUSCNTRATELIFEdata|48|CUST CUSTCNT RATE LIFE|decimal|", "LIS_UNWRK1data|49|UNWRK 1st PASS|integer|", "LIS_UNWRK2data|50|UNWRK 2nd PASS|integer|", "LIS_UNWRK3data|51|UNWRK 3rd PASS|integer|", "LIS_UNWRK4data|52|UNWRK 4th PASS|integer|", "LIS_UNWRK5data|53|UNWRK 5th PASS|integer|", "LIS_UNWRKLIFEdata|54|UNWRK LIFE|integer|", "LIS_UNWRKRATE1data|55|UNWRK RATE 1st PASS|decimal|", "LIS_UNWRKRATE2data|56|UNWRK RATE 2nd PASS|decimal|", "LIS_UNWRKRATE3data|57|UNWRK RATE 3rd PASS|decimal|", "LIS_UNWRKRATE4data|58|UNWRK RATE 4th PASS|decimal|", "LIS_UNWRKRATE5data|59|UNWRK RATE 5th PASS|decimal|", "LIS_UNWRKRATELIFEdata|60|UNWRK RATE LIFE|decimal|", "LIS_SCHDCLBK1data|61|SCHD CLBK 1st PASS|integer|", "LIS_SCHDCLBK2data|62|SCHD CLBK 2nd PASS|integer|", "LIS_SCHDCLBK3data|63|SCHD CLBK 3rd PASS|integer|", "LIS_SCHDCLBK4data|64|SCHD CLBK 4th PASS|integer|", "LIS_SCHDCLBK5data|65|SCHD CLBK 5th PASS|integer|", "LIS_SCHDCLBKLIFEdata|66|SCHD CLBK LIFE|integer|", "LIS_SCHDCLBKRATE1data|67|SCHD CLBK RATE 1st PASS|decimal|", "LIS_SCHDCLBKRATE2data|68|SCHD CLBK RATE 2nd PASS|decimal|", "LIS_SCHDCLBKRATE3data|69|SCHD CLBK RATE 3rd PASS|decimal|", "LIS_SCHDCLBKRATE4data|70|SCHD CLBK RATE 4th PASS|decimal|", "LIS_SCHDCLBKRATE5data|71|SCHD CLBK RATE 5th PASS|decimal|", "LIS_SCHDCLBKRATELIFEdata|72|SCHD CLBK RATE LIFE|decimal|", "LIS_COMPLTD1data|73|COMPLTD 1st PASS|integer|", "LIS_COMPLTD2data|74|COMPLTD 2nd PASS|integer|", "LIS_COMPLTD3data|75|COMPLTD 3rd PASS|integer|", "LIS_COMPLTD4data|76|COMPLTD 4th PASS|integer|", "LIS_COMPLTD5data|77|COMPLTD 5th PASS|integer|", "LIS_COMPLTDLIFEdata|78|COMPLTD LIFE|integer|", "LIS_COMPLTDRATE1data|79|COMPLTD RATE 1st PASS|decimal|", "LIS_COMPLTDRATE2data|80|COMPLTD RATE 2nd PASS|decimal|", "LIS_COMPLTDRATE3data|81|COMPLTD RATE 3rd PASS|decimal|", "LIS_COMPLTDRATE4data|82|COMPLTD RATE 4th PASS|decimal|", "LIS_COMPLTDRATE5data|83|COMPLTD RATE 5th PASS|decimal|", "LIS_COMPLTDRATELIFEdata|84|COMPLTD RATE LIFE|decimal|");
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
		for ($d=0; $d<count($graph_stats2); $d++) {
			$labels.="\"".preg_replace('/ +/', ' ', $graph_stats2[$d][0])."\",";
			$data.="\"".$graph_stats2[$d][$dataset_index]."\",";
			$current_graph_total+=$graph_stats2[$d][$dataset_index];
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
	$GRAPH2.=$graphCanvas;

	#########
	$graph_array=array("ALRdata|||integer|");
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
	$graph_title=_QXZ("LIST ID SUMMARY 1");
	include("graphcanvas.inc");
	$HEADER.=$HTML_graph_head;
	$GRAPH.=$graphCanvas;


	if ($report_display_type=="HTML")
		{
		$MAIN.=$GRAPH.$GRAPH2.$GRAPH3;
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
		$CSVfilename = "AST_LISTS_pass_report_$US$FILE_TIME.csv";
		$CSV_var="CSV_text1";
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
