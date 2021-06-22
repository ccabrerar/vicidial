<?php 
# AST_LISTS_stats.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This is a list inventory report, not a calling report. This report will show
# statistics for all of the lists in the selected campaigns
#
# CHANGES
# 130926-0721 - First build based upon LISTS campaign report
# 130927-2154 - Added summary and full download options
# 140108-0714 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0825 - Finalized adding QXZ translation to all admin files
# 141230-1442 - Added code for on-the-fly language translations display
# 150516-1304 - Fixed Javascript element problem, Issue #857
# 160227-1026 - Fixed dbconnect bug, standardized form layout
# 170409-1539 - Added IP List validation code
# 170903-0951 - Added screen color settings
# 180507-2315 - Added new help display
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
if (isset($_GET["list"]))				{$list=$_GET["list"];}
	elseif (isset($_POST["list"]))		{$list=$_POST["list"];}
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
if (isset($_GET["campaigns_or_lists_rpt"]))				{$campaigns_or_lists_rpt=$_GET["campaigns_or_lists_rpt"];}
	elseif (isset($_POST["campaigns_or_lists_rpt"]))	{$campaigns_or_lists_rpt=$_POST["campaigns_or_lists_rpt"];}

$report_name = 'Lists Statuses Report';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_text.="function openNewWindow(url)\n";
$JS_text.="  {\n";
$JS_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$JS_text.="  }\n";
$JS_onload="onload = function() {\n";

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

###########
if (!isset($list)) {$list = array();}

$i=0;
$list_string='|';
$list_ct = count($list);
while($i < $list_ct)
	{
	$list_string .= "$list[$i]|";
	$i++;
	}

$stmt="select list_id, list_name, campaign_id from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$lists_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $lists_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$lists[$i] =		$row[0];
	$list_names[$i] =	$row[1];
	$list_campaigns[$i] =	$row[2];
	if (preg_match('/\-ALL/',$list_string) )
		{$list[$i] = $lists[$i];}
	$i++;
	}

$i=0;
$list_string='|';
$list_ct = count($list);
while($i < $list_ct)
	{
	$list_string .= "$list[$i]|";
	$list_SQL .= "'$list[$i]',";
	$listQS .= "&list[]=$list[$i]";
	$i++;
	}
	$list_id_str=substr($list_SQL,0,-1);

	$group_SQL = "$LOGallowed_campaignsSQL";
	$group_SQLand = "$LOGallowed_campaignsSQL";

#######################

# Get lists to query to avoid using a nested query
$lists_id_str="";
$list_stmt="SELECT list_id from vicidial_lists where active IN('Y','N') $group_SQLand";
$list_rslt=mysql_to_mysqli($list_stmt, $link);
while ($lrow=mysqli_fetch_row($list_rslt)) {
	$lists_id_str.="'$lrow[0]',";
}
$lists_id_str=substr($lists_id_str,0,-1);

$stmt="select vsc_id,vsc_name from vicidial_status_categories;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statcats_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statcats_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$vsc_id[$i] =	$row[0];
	$vsc_name[$i] =	$row[1];

	$category_statuses="";
	$status_stmt="select distinct status from vicidial_statuses where category='$row[0]' UNION select distinct status from vicidial_campaign_statuses where category='$row[0]' $group_SQLand";
	if ($DB) {echo "$status_stmt\n";}
	$status_rslt=mysql_to_mysqli($status_stmt, $link);
	while ($status_row=mysqli_fetch_row($status_rslt)) 
		{
		$category_statuses.="'$status_row[0]',";
        }
	$category_statuses=substr($category_statuses, 0, -1);

	$category_stmt="select count(*) from vicidial_list where status in ($category_statuses) and list_id IN($lists_id_str)";
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

$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#LISTS_stats$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP>";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.="</TD>";

$MAIN.="<TD VALIGN=TOP> "._QXZ("Lists").":<BR>";
$MAIN.="<SELECT SIZE=5 NAME=list[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$list_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL LISTS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL LISTS")." --</option>\n";}
$o=0;
while ($lists_to_print > $o)
	{
	if (preg_match("/$lists[$o]\|/i",$list_string)) {$MAIN.="<option selected value=\"$lists[$o]\">$lists[$o] - $list_names[$o]</option>\n";}
	  else {$MAIN.="<option value=\"$lists[$o]\">$lists[$o] - $list_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT><BR><a href=\"AST_LISTS_campaign_stats.php?DB=$DB\">"._QXZ("SWITCH TO CAMPAIGNS")."</a>\n";
$MAIN.="</TD>";

$MAIN.="<TD VALIGN=TOP>";
#$MAIN.="Display as:<BR/>";
#$MAIN.="<select name='report_display_type'>";
#if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>$report_display_type</option>";}
#$MAIN.="<option value='TEXT'>TEXT</option><option value='HTML'>HTML</option></select>&nbsp; ";
$MAIN.="<BR><BR><BR>\n";
$MAIN.="<INPUT style='background-color:#$SSbutton_color' type=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
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


if (strlen($list[0]) < 1)
	{
	$MAIN.="\n\n";
	$MAIN.=_QXZ("PLEASE SELECT A LIST OR LISTS ABOVE AND CLICK SUBMIT")."\n";
	}
else
	{
	$totalOUToutput = '';
	$totalOUToutput .= _QXZ("List Status Stats",45)." $NOW_TIME     <a href=\"$PHP_SELF?DB=$DB$listQS&SUBMIT=$SUBMIT&file_download=ALL\">"._QXZ("DOWNLOAD FULL REPORT")."</a>\n";

	$totalOUToutput .= "\n";

	$list_stmt="select vicidial_list.list_id,list_name,active, count(*) from vicidial_list, vicidial_lists where vicidial_lists.list_id in ($list_id_str) and vicidial_lists.list_id=vicidial_list.list_id group by vicidial_list.list_id, list_name, active order by list_id, list_name asc;";
	$list_rslt=mysql_to_mysqli($list_stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$listids_to_print = mysqli_num_rows($list_rslt);
	$CSV_text1="\"\",\""._QXZ("LIST ID SUMMARY")."\"\n";
	$CSV_text1.="\""._QXZ("LIST ID")."\",\""._QXZ("LIST NAME")."\",\""._QXZ("TOTAL LEADS")."\",\""._QXZ("ACTIVE/INACTIVE")."\"\n";
	$CSV_text2="";
	$CSV_text3="";
	$CSV_textALL="";
	$i=0;

	$totalOUToutput .= "---------- "._QXZ("TOTAL LIST ID SUMMARY",25)." <a href=\"$PHP_SELF?DB=$DB$listQS&SUBMIT=$SUBMIT&file_download=ALL\">"._QXZ("DOWNLOAD FULL REPORT")."</a>\n";
	$totalOUToutput .= "+------------------------------------------+------------+----------+\n";
	$totalOUToutput .= "| "._QXZ("LIST",40)." | "._QXZ("LEADS",10)." | "._QXZ("ACTIVE",8)." |\n";
	$totalOUToutput .= "+------------------------------------------+------------+----------+\n";

	$CSV_textSUMMARY.="\""._QXZ("LIST ID SUMMARY")."\"\n";
	$CSV_textSUMMARY.="\""._QXZ("LIST")."\",\""._QXZ("LEADS")."\",\""._QXZ("ACTIVE")."\"\n";

	$OUToutput='';
	$OUToutput .= "\n";
	$OUToutput .= "---------- "._QXZ("INDIVIDUAL LIST ID SUMMARIES")."\n";
	
	while ($i < $listids_to_print)
		{
		$list_row=mysqli_fetch_row($list_rslt);
		$LISTIDlists[$i] =	$list_row[0];
		$LISTIDlist_names[$i] =	$list_row[1];
		if ($list_row[2]=="Y") {$active_txt=_QXZ("ACTIVE");} else {$active_txt=_QXZ("INACTIVE");}
		$active_txt=sprintf("%-8s", $active_txt);
		$LISTIDcalls[$i] =	$list_row[3];
		$TOTALleads =$list_row[3];
		$totalTOTALleads+=$TOTALleads;

		$LISTIDcount =	sprintf("%10s", $LISTIDcalls[$i]);while(strlen($LISTIDcount)>10) {$LISTIDcount = substr("$LISTIDcount", 0, -1);}
		$LISTIDname =	sprintf("%-40s", "$LISTIDlists[$i] - $LISTIDlist_names[$i]");while(strlen($LISTIDname)>40) {$LISTIDname = substr("$LISTIDname", 0, -1);}
		$totalOUToutput .= "| $LISTIDname | $LISTIDcount | $active_txt |\n";
		$CSV_textSUMMARY.="\"$LISTIDname\",\"$LISTIDcount\",\"$active_txt\"\n";


		$OUToutput .= "\n";
		$OUToutput .= "---------- "._QXZ("LIST ID SUMMARY",19)." <a href=\"$PHP_SELF?DB=$DB$listQS&SUBMIT=$SUBMIT&file_download=1\">"._QXZ("DOWNLOAD LIST SUMMARIES")."</a>\n";
		$OUToutput .= "+--------------------------------------------------------+\n";
		$OUToutput .= "| "._QXZ("LIST ID:",14)." ".sprintf("%-30s", $list_row[0])." ".sprintf("%-8s", $active_txt)." |\n";
		$OUToutput .= "| "._QXZ("LIST NAME:",14)." ".sprintf("%-30s", $list_row[1])." ".sprintf("%8s", "")." |\n";
		$OUToutput .= "| "._QXZ("TOTAL LEADS:",14)." ".sprintf("%-30s", $list_row[3])." ".sprintf("%8s", "")." |\n";
		$OUToutput .= "+--------------------------------------------------------+\n";
		$CSV_text1.="\"$list_row[0]\",\"$list_row[1]\",\"$list_row[3]\",\"$active_txt\"\n";

		$CSV_textALL.="\""._QXZ("LIST ID")." #$list_row[0] "._QXZ("SUMMARY")."\",\"\"\n";
		$CSV_textALL.="\""._QXZ("LIST NAME")."\",\""._QXZ("TOTAL LEADS")."\",\""._QXZ("ACTIVE/INACTIVE")."\"\n";
		$CSV_textALL.="\"$list_row[1]\",\"$list_row[3]\",\"$active_txt\"\n";


		# $list_id_SQL .=		"'$row[0]',";
		# if ($row[0]>$max_calls) {$max_calls=$row[3];}
		$i++;
		$list_id=$list_row[0];

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

		$stmt="select count(*) from vicidial_list where status IN($human_answered_statuses) and list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$HA_results = mysqli_num_rows($rslt);
		if ($HA_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$HA_count = $row[0];
			$flag_count+=$row[0];
			$category_totals["HA"]+=$HA_count;
			if ($HA_count>$max_calls) {$max_calls=$HA_count;}
			$HA_percent = ( MathZDC($HA_count, $TOTALleads) * 100);
			}
		$stmt="select count(*) from vicidial_list where status IN($sale_statuses) and list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SALE_results = mysqli_num_rows($rslt);
		if ($SALE_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$SALE_count = $row[0];
			$flag_count+=$row[0];
			$category_totals["SALE"]+=$SALE_count;
			if ($SALE_count>$max_calls) {$max_calls=$SALE_count;}
			$SALE_percent = ( MathZDC($SALE_count, $TOTALleads) * 100);
			}
		$stmt="select count(*) from vicidial_list where status IN($dnc_statuses) and list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$DNC_results = mysqli_num_rows($rslt);
		if ($DNC_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$DNC_count = $row[0];
			$flag_count+=$row[0];
			$category_totals["DNC"]+=$DNC_count;
			if ($DNC_count>$max_calls) {$max_calls=$DNC_count;}
			$DNC_percent = ( MathZDC($DNC_count, $TOTALleads) * 100);
			}
		$stmt="select count(*) from vicidial_list where status IN($customer_contact_statuses) and list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$CC_results = mysqli_num_rows($rslt);
		if ($CC_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$CC_count = $row[0];
			$flag_count+=$row[0];
			$category_totals["CC"]+=$CC_count;
			if ($C_count>$max_calls) {$max_calls=$CC_count;}
			$CC_percent = ( MathZDC($CC_count, $TOTALleads) * 100);
			}
		$stmt="select count(*) from vicidial_list where status IN($not_interested_statuses) and list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$NI_results = mysqli_num_rows($rslt);
		if ($NI_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$NI_count = $row[0];
			$flag_count+=$row[0];
			$category_totals["NI"]+=$NI_count;
			if ($NI_count>$max_calls) {$max_calls=$NI_count;}
			$NI_percent = ( MathZDC($NI_count, $TOTALleads) * 100);
			}
		$stmt="select count(*) from vicidial_list where status IN($unworkable_statuses) and list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$UW_results = mysqli_num_rows($rslt);
		if ($UW_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$UW_count = $row[0];
			$flag_count+=$row[0];
			$category_totals["UW"]+=$UW_count;
			if ($UW_count>$max_calls) {$max_calls=$UW_count;}
			$UW_percent = ( MathZDC($UW_count, $TOTALleads) * 100);
			}
		$stmt="select count(*) from vicidial_list where status IN($scheduled_callback_statuses) and list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$SC_results = mysqli_num_rows($rslt);
		if ($SC_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$SC_count = $row[0];
			$flag_count+=$row[0];
			$category_totals["SC"]+=$SC_count;
			if ($SC_count>$max_calls) {$max_calls=$SC_count;}
			$SC_percent = ( MathZDC($SC_count, $TOTALleads) * 100);
			}
		$stmt="select count(*) from vicidial_list where status IN($completed_statuses) and list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$COMP_results = mysqli_num_rows($rslt);
		if ($COMP_results > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$COMP_count = $row[0];
			$flag_count+=$row[0];
			$category_totals["COMP"]+=$COMP_count;
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

		$OUToutput .= "\n";
		$OUToutput .= "---------- "._QXZ("STATUS FLAGS BREAKDOWN").":    ("._QXZ("and % of total leads in list").")     <a href=\"$PHP_SELF?DB=$DB$listQS&SUBMIT=$SUBMIT&file_download=2\">"._QXZ("DOWNLOAD FLAG BREAKDOWNS")."</a>\n";
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

		$CSV_text_block = "\""._QXZ("STATUS FLAGS SUMMARY FOR LIST ID")." #$list_id:\"\n";
		$CSV_text_block .= "\""._QXZ("Human Answer")."\",\"$HA_count\",\"$HA_percent%\"\n";
		$CSV_text_block .= "\""._QXZ("Sale")."\",\"$SALE_count\",\"$SALE_percent%\"\n";
		$CSV_text_block .= "\""._QXZ("DNC")."\",\"$DNC_count\",\"$DNC_percent%\"\n";
		$CSV_text_block .= "\""._QXZ("Customer Contact")."\",\"$CC_count\",\"$CC_percent%\"\n";
		$CSV_text_block .= "\""._QXZ("Not Interested")."\",\"$NI_count\",\"$NI_percent%\"\n";
		$CSV_text_block .= "\""._QXZ("Unworkable")."\",\"$UW_count\",\"$UW_percent%\"\n";
		$CSV_text_block .= "\""._QXZ("Scheduled Callbacks")."\",\"$SC_count\",\"$SC_percent%\"\n";
		$CSV_text_block .= "\""._QXZ("Completed")."\",\"$COMP_count\",\"$COMP_percent%\"\n\n";

		$CSV_text2.=$CSV_text_block;
		$CSV_textALL.="\n".$CSV_text_block;

		$stmt="select status, count(*) From vicidial_list where list_id='$list_id' group by status order by status asc";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$OUToutput .= "---------- "._QXZ("STATUS BREAKDOWN").":    ("._QXZ("and % of total leads in list").")     <a href=\"$PHP_SELF?DB=$DB$listQS&SUBMIT=$SUBMIT&file_download=3\">"._QXZ("DOWNLOAD STAT BREAKDOWNS")."</a>\n";
		$OUToutput .= "+--------+--------------------------------+----------+---------+\n";
		$OUToutput .= "| "._QXZ("STATUS",6)." | "._QXZ("STATUS NAME",30)." | "._QXZ("COUNT",8)." | "._QXZ("LEAD",5)." % |\n";
		$OUToutput .= "+--------+--------------------------------+----------+---------+\n";

		$CSV_text3.="\"\",\""._QXZ("STATUS BREAKDOWN FOR LIST ID")." #$list_id:\"\n";
		$CSV_text3.="\""._QXZ("STATUS")."\",\""._QXZ("STATUS NAME")."\",\""._QXZ("COUNT")."\",\""._QXZ("LEAD")." %\"\n";

		$CSV_textALL.="\""._QXZ("STATUS BREAKDOWN FOR LIST ID")." #$list_id:\",\"\"\n";
		$CSV_textALL.="\""._QXZ("STATUS")."\",\""._QXZ("STATUS NAME")."\",\""._QXZ("COUNT")."\",\""._QXZ("LEAD")." %\"\n";

		while ($row=mysqli_fetch_row($rslt)) 
			{
			$OUToutput .= "| ".sprintf("%6s", $row[0])." | ".sprintf("%30s", $statname_list["$row[0]"])." | ".sprintf("%8s", $row[1])." | ".sprintf("%6.2f", ( MathZDC($row[1], $TOTALleads) * 100))."% |\n";
			$CSV_text3.="\"$row[0]\",\"".$statname_list["$row[0]"]."\",\"$row[1]\",\"".sprintf("%6.2f", ( MathZDC($row[1], $TOTALleads) * 100))."%\"\n";
			$CSV_textALL.="\"$row[0]\",\"".$statname_list["$row[0]"]."\",\"$row[1]\",\"".sprintf("%6.2f", ( MathZDC($row[1], $TOTALleads) * 100))."%\"\n";
			}
		$OUToutput .= "+-----------------------------------------+----------+---------+\n";
		$OUToutput .= "|                                         | ".sprintf("%8s", $TOTALleads)." | 100.00% |\n";
		$OUToutput .= "+-----------------------------------------+----------+---------+\n";
		$CSV_text3.="\"\",\"\",\"$TOTALleads\",\"100.00%\"\n\n\n";
		$CSV_textALL.="\"\",\"\",\"$TOTALleads\",\"100.00%\"\n\n\n";
		$OUToutput .= "\n";
		$OUToutput .= "\n";
		$OUToutput .= "\n";
		}

	$total_HA_percent =	sprintf("%6.2f", ( MathZDC($category_totals["HA"], $totalTOTALleads) * 100)); while(strlen($total_HA_percent)>6) {$total_HA_percent = substr("$total_HA_percent", 0, -1);}
	$total_SALE_percent =	sprintf("%6.2f", ( MathZDC($category_totals["SALE"], $totalTOTALleads) * 100)); while(strlen($total_SALE_percent)>6) {$total_SALE_percent = substr("$total_SALE_percent", 0, -1);}
	$total_DNC_percent =	sprintf("%6.2f", ( MathZDC($category_totals["DNC"], $totalTOTALleads) * 100)); while(strlen($total_DNC_percent)>6) {$total_DNC_percent = substr("$total_DNC_percent", 0, -1);}
	$total_CC_percent =	sprintf("%6.2f", ( MathZDC($category_totals["CC"], $totalTOTALleads) * 100)); while(strlen($total_CC_percent)>6) {$total_CC_percent = substr("$total_CC_percent", 0, -1);}
	$total_NI_percent =	sprintf("%6.2f", ( MathZDC($category_totals["NI"], $totalTOTALleads) * 100)); while(strlen($total_NI_percent)>6) {$total_NI_percent = substr("$total_NI_percent", 0, -1);}
	$total_UW_percent =	sprintf("%6.2f", ( MathZDC($category_totals["UW"], $totalTOTALleads) * 100)); while(strlen($total_UW_percent)>6) {$total_UW_percent = substr("$total_UW_percent", 0, -1);}
	$total_SC_percent =	sprintf("%6.2f", ( MathZDC($category_totals["SC"], $totalTOTALleads) * 100)); while(strlen($total_SC_percent)>6) {$total_SC_percent = substr("$total_SC_percent", 0, -1);}
	$total_COMP_percent =	sprintf("%6.2f", ( MathZDC($category_totals["COMP"], $totalTOTALleads) * 100)); while(strlen($total_COMP_percent)>6) {$total_COMP_percent = substr("$total_COMP_percent", 0, -1);}

	$total_HA_count =	sprintf("%10s", $category_totals["HA"]); while(strlen($total_HA_count)>10) {$total_HA_count = substr("$total_HA_count", 0, -1);}
	$total_SALE_count =	sprintf("%10s", $category_totals["SALE"]); while(strlen($total_SALE_count)>10) {$total_SALE_count = substr("$total_SALE_count", 0, -1);}
	$total_DNC_count =	sprintf("%10s", $category_totals["DNC"]); while(strlen($total_DNC_count)>10) {$total_DNC_count = substr("$total_DNC_count", 0, -1);}
	$total_CC_count =	sprintf("%10s", $category_totals["CC"]); while(strlen($total_CC_count)>10) {$total_CC_count = substr("$total_CC_count", 0, -1);}
	$total_NI_count =	sprintf("%10s", $category_totals["NI"]); while(strlen($total_NI_count)>10) {$total_NI_count = substr("$total_NI_count", 0, -1);}
	$total_UW_count =	sprintf("%10s", $category_totals["UW"]); while(strlen($total_UW_count)>10) {$total_UW_count = substr("$total_UW_count", 0, -1);}
	$total_SC_count =	sprintf("%10s", $category_totals["SC"]); while(strlen($total_SC_count)>10) {$total_SC_count = substr("$total_SC_count", 0, -1);}
	$total_COMP_count =	sprintf("%10s", $category_totals["COMP"]); while(strlen($total_COMP_count)>10) {$total_COMP_count = substr("$total_COMP_count", 0, -1);}

	$totalOUToutput .= "+------------------------------------------+------------+----------+\n";
	$totalOUToutput .= "| "._QXZ("TOTAL",40,"r")." | ".sprintf("%10s", $totalTOTALleads)." |\n";
	$totalOUToutput .= "+------------------------------------------+------------+\n";
	$CSV_textSUMMARY .= "\""._QXZ("TOTAL")."\",\"$totalTOTALleads\"\n";

	$totalOUToutput .= "\n";
	$totalOUToutput .= "\n";
	$totalOUToutput .= "---------- "._QXZ("TOTAL STATUS FLAGS SUMMARY").":    ("._QXZ("and % of leads in selected lists").")     <a href=\"$PHP_SELF?DB=$DB$listQS&SUBMIT=$SUBMIT&file_download=ALL\">"._QXZ("DOWNLOAD FULL REPORT")."</a>\n";
	$totalOUToutput .= "+------------------+------------+----------+\n";
	$totalOUToutput .= "| "._QXZ("Human Answer",16)." | $total_HA_count |  $total_HA_percent% |\n";
	$totalOUToutput .= "| "._QXZ("Sale",16)." | $total_SALE_count |  $total_SALE_percent% |\n";
	$totalOUToutput .= "| "._QXZ("DNC",16)." | $total_DNC_count |  $total_DNC_percent% |\n";
	$totalOUToutput .= "| "._QXZ("Customer Contact",16)." | $total_CC_count |  $total_CC_percent% |\n";
	$totalOUToutput .= "| "._QXZ("Not Interested",16)." | $total_NI_count |  $total_NI_percent% |\n";
	$totalOUToutput .= "| "._QXZ("Unworkable",16)." | $total_UW_count |  $total_UW_percent% |\n";
	$totalOUToutput .= "| "._QXZ("Sched Callbacks",16)." | $total_SC_count |  $total_SC_percent% |\n";
	$totalOUToutput .= "| "._QXZ("Completed",16)." | $total_COMP_count |  $total_COMP_percent% |\n";
	$totalOUToutput .= "+------------------+------------+----------+\n";
	$totalOUToutput .= "\n\n\n";

	$CSV_textSUMMARY .= "\n\""._QXZ("STATUS FLAGS SUMMARY").":\"\n";
	$CSV_textSUMMARY .= "\""._QXZ("Human Answer")."\",\"$total_HA_count\",\"$total_HA_percent%\"\n";
	$CSV_textSUMMARY .= "\""._QXZ("Sale")."\",\"$total_SALE_count\",\"$total_SALE_percent%\"\n";
	$CSV_textSUMMARY .= "\""._QXZ("DNC")."\",\"$total_DNC_count\",\"$total_DNC_percent%\"\n";
	$CSV_textSUMMARY .= "\""._QXZ("Customer Contact")."\",\"$total_CC_count\",\"$total_CC_percent%\"\n";
	$CSV_textSUMMARY .= "\""._QXZ("Not Interested")."\",\"$total_NI_count\",\"$total_NI_percent%\"\n";
	$CSV_textSUMMARY .= "\""._QXZ("Unworkable")."\",\"$total_UW_count\",\"$total_UW_percent%\"\n";
	$CSV_textSUMMARY .= "\""._QXZ("Scheduled Callbacks")."\",\"$total_SC_count\",\"$total_SC_percent%\"\n";
	$CSV_textSUMMARY .= "\""._QXZ("Completed")."\",\"$total_COMP_count\",\"$total_COMP_percent%\"\n\n\n\n";

	$CSV_textALL=$CSV_textSUMMARY.$CSV_textALL;

	if ($report_display_type=="HTML")
		{
		$MAIN.=$GRAPH;
		}
	else
		{
		$MAIN.="$totalOUToutput$OUToutput";
		}



	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	$MAIN.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
	$MAIN.="</PRE>\n";
	$MAIN.="</TD></TR></TABLE>\n";
	$MAIN.="</BODY></HTML>\n";

	}

	if ($file_download>0 || $file_download=="ALL") {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_LISTS_stats_$US$FILE_TIME.csv";
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
		echo $JS_text;
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

exit;

?>
