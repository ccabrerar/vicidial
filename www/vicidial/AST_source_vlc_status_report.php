<?php 
# AST_source_vlc_status_report.php
#
# This report is designed to show the breakdown by either vendor_lead_code
# or aource_id ,user's choice, of the calls and their statuses for all lists 
# within a campaign for a set time period
#
# Copyright (C) 2019  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 180211-1111 - First build
# 180214-2230 - Added column to download for VLC or source_id
# 180507-2315 - Added new help display
# 191013-0834 - Fixes for PHP7
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
if (isset($_GET["group_by"]))					{$group_by=$_GET["group_by"];}
	elseif (isset($_POST["group_by"]))			{$group_by=$_POST["group_by"];}
if (isset($_GET["sort_by"]))					{$sort_by=$_GET["sort_by"];}
	elseif (isset($_POST["sort_by"]))			{$sort_by=$_POST["sort_by"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$report_name="Outbound Lead Source Report";
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
$log_tables_array=array("vicidial_list");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

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

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

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

$HTML_head.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_head.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_head.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HTML_head.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_head.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
require("chart_button.php");
$HTML_head.="<script src='chart/Chart.js'></script>\n"; 
$HTML_head.="<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>$group_S\n";
$short_header=1;

#	require("admin_header.php");

$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";
$HTML_text.="<b>"._QXZ("$report_name")."</b> $NWB#source_vlc_status_report$NWE\n";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Entry date").":<BR>";
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

$HTML_text.="</TD><TD VALIGN=TOP> "._QXZ("Campaign").":<BR>";
$HTML_text.="<SELECT NAME=group[]>\n";
$HTML_text.="<option value=\"\">-- "._QXZ("Select a campaign")." --</option>\n";
$o=0;
while ($campaigns_to_print > $o)
	{
	if (preg_match("/$groups[$o]\|/",$group_string)) {$HTML_text.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	else {$HTML_text.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT><BR><BR>\n";

$HTML_text.=_QXZ("Group by").":<BR>";
$HTML_text.="<select name='group_by'>";
if ($group_by) {$HTML_text.="<option value='$group_by' selected>$group_by</option>";}
$HTML_text.="<option value='vendor_lead_code'>"._QXZ("vendor_lead_code")."</option><option value='source_id'>"._QXZ("source_id")."</option></select>\n";

$HTML_text.="</TD><TD VALIGN=TOP>&nbsp;\n";
$HTML_text.="</TD><TD VALIGN=TOP>\n";
$HTML_text.=_QXZ("Display as").":<BR>";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";

if ($archives_available=="Y") 
	{
	$HTML_text.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

$HTML_text.="<BR><BR><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";

$HTML_text.="</TD><TD VALIGN=TOP>";

$report_URL="$PHP_SELF?DB=$DB&query_date=$query_date&end_date=$end_date&query_date_D=$query_date_D&query_date_T=$query_date_T&end_date_D=$end_date_D&end_date_T=$end_date_T$groupQS&SUBMIT=$SUBMIT&group_by=$group_by&search_archived_data=$search_archived_data";

$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>\n";
$HTML_text.="<a href=\"$report_URL&sort_by=$sort_by&file_download=1\">"._QXZ("DOWNLOAD")."</a> |";
$HTML_text.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$HTML_text.="</FONT>\n";
$HTML_text.="</TD></TR></TABLE>";
$HTML_text.="</FORM>\n\n";

$HTML_text.="<PRE>";
$i=0;
$group_string='|';
$group_ct = count($group);
$HTML_body="";

while($i < $group_ct)
	{

	$list_stmt="select list_id from vicidial_lists where campaign_id='$group[$i]'";
	if ($DB) {$HTML_text.="|$list_stmt|\n";}
	$list_rslt=mysql_to_mysqli($list_stmt, $link);
	$list_ary=array();
	while($list_row=mysqli_fetch_row($list_rslt)) 
		{
		array_push($list_ary, $list_row[0]);
		}

	$dispo_ary=array();
	$dispo_stmt="SELECT status, status_name from vicidial_campaign_statuses where campaign_id='$group[$i]' UNION SELECT status, status_name from vicidial_statuses order by status, status_name";
	if ($DB) {$HTML_text.="|$dispo_stmt|\n";}
	$dispo_rslt=mysql_to_mysqli($dispo_stmt, $link);
	while($dispo_row=mysqli_fetch_row($dispo_rslt)) 
		{
		$dispo_ary["$dispo_row[0]"]=$dispo_row[1];
		}

	$count_ary=array();
	$total_ary=array();
	$status_totals=array();
	$stmt="select lead_id, $group_by, status from ".$vicidial_list_table." where list_id in ('".implode("','", $list_ary)."') and entry_date>='$query_date' and entry_date<='$end_date'";
	if ($DB) {$HTML_text.="|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$grand_total=mysqli_num_rows($rslt);
	while ($row=mysqli_fetch_row($rslt)) 
		{
		$count_ary["$row[1]"]["$row[2]"]++;
		$total_ary["$row[1]"]++;
		$status_totals["$row[2]"]++;
		}
	ksort($count_ary);
	arsort($total_ary);
	arsort($status_totals);

	$HTML_body.="<B>"._QXZ("CAMPAIGN").": $group[$i]</B><BR>\n";
	$ASCII_text.="<B>"._QXZ("CAMPAIGN").": $group[$i]</B>\n";
	$GRAPH.="<B>"._QXZ("CAMPAIGN").": $group[$i]</B>\n";
	$CSV_text.="\""._QXZ("CAMPAIGN").": $group[$i]\"\n";

	$rpt_group_title=strtoupper(preg_replace('/_/', " ", $group_by));



#	while(list($key, $val)=each($count_ary)) 
	foreach($count_ary as $key => $val)
		{
		arsort($count_ary[$key]);
		switch ($sort_by) 
			{
			case "dispo_desc":
				krsort($count_ary[$key]);
				break;
			case "dispo_asc":
				ksort($count_ary[$key]);
				break;
			case "count_asc":
				asort($count_ary[$key]);
				break;
			}
		
		$HTML_body.="<table border='0' cellpadding='0' cellspacing='0' width='600'>";
		$HTML_body.="<tr bgcolor='#".$SSmenu_background."'><th colspan='3'><font color='#FFFFFF'>"._QXZ("$rpt_group_title")." : ".($key !="" ? $key : "("._QXZ("NONE").")")."</font></th></tr>";
		$HTML_body.="<tr bgcolor='#FFFFFF'><td align='left'><a href='$report_URL&sort_by=".($sort_by == "dispo_asc" ? "dispo_desc" : "dispo_asc")."'>"._QXZ("DISPOSITION",40)."</a></td><td align='center'><a href='$report_URL&sort_by=".($sort_by == "count_desc" ? "count_asc" : "count_desc")."'>"._QXZ("CALLS",6)."</a></td><td align='center'>"._QXZ("PERCENT", 7)."</td></tr>";
		
		$ASCII_text.="<FONT SIZE=2><B>"._QXZ("$rpt_group_title")." : ".($key !="" ? $key : "("._QXZ("NONE").")")."</B>\n";
		$ASCII_text.="+------------------------------------------+--------+----------+\n";
		$ASCII_text.="| <a href='$report_URL&sort_by=".($sort_by == "dispo_asc" ? "dispo_desc" : "dispo_asc")."'>"._QXZ("DISPOSITION",40)."</a> | <a href='$report_URL&sort_by=".($sort_by == "count_asc" ? "count_desc" : "count_asc")."'>"._QXZ("CALLS",6)."</a> | "._QXZ("PERCENT", 8)." |\n";
		$ASCII_text.="+------------------------------------------+--------+----------+\n";

		# $CSV_text.="\""._QXZ("$rpt_group_title")." : ".($key !="" ? $key : "(NONE)")."\"\n";
		$CSV_text.="\""._QXZ("VENDOR LEAD CODE")."\",\""._QXZ("DISPOSITION")."\",\""._QXZ("CALLS")."\",\""._QXZ("PERCENT")."\"\n";

		$j=0;
#		while(list($key2, $val2)=each($count_ary[$key])) 
		foreach($count_ary[$key] as $key2 => $val2)
			{
			if ($j%2==0) 
				{
				$bgcolor=$SSstd_row1_background;
				} 
			else 
				{
				$bgcolor=$SSstd_row2_background;
				}

			$percent=sprintf("%.2f", 100*MathZDC($val2, $total_ary["$key"]));
			$HTML_body.="<tr bgcolor='#".$bgcolor."'><td align='left'>".($dispo_ary["$key2"] !="" ? $dispo_ary["$key2"] : $key2)."</td><td align='center'>".$val2."</td><td align='center'>".$percent." %</td></tr>";
			$ASCII_text.="| ".sprintf("%-40s", ($dispo_ary["$key2"] !="" ? $dispo_ary["$key2"] : $key2))." | ".sprintf("%6s", $val2)." | ".sprintf("%7s", $percent)."% |\n";
			$CSV_text.="\"".($key !="" ? $key : "("._QXZ("NONE").")")."\",\"".($dispo_ary["$key2"] !="" ? $dispo_ary["$key2"] : $key2)."\",\"".$val2."\",\"".$percent." %\"\n";

			$j++;
			}
		$HTML_body.="<tr bgcolor='#".$SSmenu_background."'><td align='left'><font color='#FFFFFF'>"._QXZ("TOTAL")."</font></td><td align='center'><font color='#FFFFFF'>".$total_ary["$key"]."</font></td><td align='center'>&nbsp;</td></tr>";
		$HTML_body.="</table><BR>\n";
		$ASCII_text.="+------------------------------------------+--------+----------+\n";
		$ASCII_text.="| "._QXZ("TOTAL",40)." | ".sprintf("%6s", $total_ary["$key"])." | ".sprintf("%8s", " ")." |\n";
		$ASCII_text.="+------------------------------------------+--------+----------+\n\n";
		$CSV_text.="\"".($key !="" ? $key : "("._QXZ("NONE").")")."\",\""._QXZ("TOTAL")."\",\"".$total_ary["$key"]."\",\"\"\n\n";
		}
	$i++;

	$HTML_body.="<table border='0' cellpadding='0' cellspacing='0' width='600'>";
	$HTML_body.="<tr bgcolor='#".$SSmenu_background."'><th colspan='3'><font color='#FFFFFF'>"._QXZ("$rpt_group_title")." : "._QXZ("TOTALS")."</font></th></tr>";
	$HTML_body.="<tr bgcolor='#FFFFFF'><td align='left'><a href='$report_URL&sort_by=".($sort_by == "dispo_asc" ? "dispo_desc" : "dispo_asc")."'>"._QXZ("DISPOSITION",40)."</a></td><td align='center'><a href='$report_URL&sort_by=".($sort_by == "count_desc" ? "count_asc" : "count_desc")."'>"._QXZ("CALLS",6)."</a></td><td align='center'>"._QXZ("PERCENT", 7)."</td></tr>";
		
	$ASCII_text.="<FONT SIZE=2><B>"._QXZ("$rpt_group_title")." : "._QXZ("TOTALS")."</B>\n";
	$ASCII_text.="+------------------------------------------+--------+----------+\n";
	$ASCII_text.="| <a href='$report_URL&sort_by=".($sort_by == "dispo_asc" ? "dispo_desc" : "dispo_asc")."'>"._QXZ("DISPOSITION",40)."</a> | <a href='$report_URL&sort_by=".($sort_by == "count_asc" ? "count_desc" : "count_asc")."'>"._QXZ("CALLS",6)."</a> | "._QXZ("PERCENT", 8)." |\n";
	$ASCII_text.="+------------------------------------------+--------+----------+\n";

	# $CSV_text.="\""._QXZ("$rpt_group_title")." : TOTALS\"\n";
	$CSV_text.="\""._QXZ("VENDOR LEAD CODE")."\",\""._QXZ("DISPOSITION")."\",\""._QXZ("CALLS")."\",\""._QXZ("PERCENT")."\"\n";

	arsort($status_totals);
	switch ($sort_by) 
		{
		case "dispo_desc":
			krsort($status_totals);
			break;
		case "dispo_asc":
			ksort($status_totals);
			break;
		case "count_asc":
			asort($status_totals);
			break;
		}
#	while(list($key, $val)=each($status_totals)) 
	foreach($status_totals as $key => $val)
		{
		if ($j%2==0) 
			{
			$bgcolor=$SSstd_row1_background;
			} 
		else 
			{
			$bgcolor=$SSstd_row2_background;
			}

		$percent=sprintf("%.2f", 100*MathZDC($val, $grand_total));
		$HTML_body.="<tr bgcolor='#".$bgcolor."'><td align='left'>".($dispo_ary["$key"] !="" ? $dispo_ary["$key"] : $key)."</td><td align='center'>".$val."</td><td align='center'>".$percent." %</td></tr>";
		$ASCII_text.="| ".sprintf("%-40s", ($dispo_ary["$key"] !="" ? $dispo_ary["$key"] : $key))." | ".sprintf("%6s", $val)." | ".sprintf("%7s", $percent)."% |\n";
		$CSV_text.="\""._QXZ("TOTALS")."\",\"".($dispo_ary["$key"] !="" ? $dispo_ary["$key"] : $key)."\",\"".$val."\",\"".$percent." %\"\n";

		$j++;
		}
	$HTML_body.="<tr bgcolor='#".$SSmenu_background."'><td align='left'><font color='#FFFFFF'>"._QXZ("GRAND TOTAL")."</font></td><td align='center'><font color='#FFFFFF'>".$grand_total."</font></td><td align='center'>&nbsp;</td></tr>";
	$HTML_body.="</table><BR>\n";
	$ASCII_text.="+------------------------------------------+--------+----------+\n";
	$ASCII_text.="| "._QXZ("GRAND TOTAL",40)." | ".sprintf("%6s", $grand_total)." | ".sprintf("%8s", " ")." |\n";
	$ASCII_text.="+------------------------------------------+--------+----------+\n\n";
	$CSV_text.="\"\",\""._QXZ("GRAND TOTAL")."\",\"".$grand_total."\",\"\"\n\n";
	
	}

if ($report_display_type=="HTML")
	{
	$HTML_text.=$HTML_body;
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
