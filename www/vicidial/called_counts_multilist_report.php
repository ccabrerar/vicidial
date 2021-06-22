<?php
# called_counts_multilist_report.php
# 
# Copyright (C) 2019  Joe Johnson <freewermadmin@gmail.com>, Matt Florell <mattf@vicidial.com>    LICENSE: AGPLv2
#
# This is a report designed for showing called counts similar to the results
# at the bottom of each list detail screen, but for multiple lists and using
# a date range, if necessary.  The date range will restrict the results returned
# to leads called at least once within the given range (the count will still
# be cumulative for the entire existence of the leads, however).
#
# CHANGES
# 140311-1940 - First build based upon admin.php & AST_VDADstats.php
# 141114-0045 - Finalized adding QXZ translation to all admin files
# 141230-1346 - Added code for on-the-fly language translations display
# 151229-2050 - Added archive search option
# 160227-1036 - Uniform form format
# 170409-1539 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 180508-0115 - Added new help display
# 191013-0840 - Fixes for PHP7
#

$startMS = microtime();

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$QUERY_STRING=$_SERVER['QUERY_STRING'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["list_ids"]))				{$list_ids=$_GET["list_ids"];}
	elseif (isset($_POST["list_ids"]))		{$list_ids=$_POST["list_ids"];}
if (isset($_GET["override_date"]))				{$override_date=$_GET["override_date"];}
	elseif (isset($_POST["override_date"]))	{$override_date=$_POST["override_date"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))	{$group=$_POST["group"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["shift"]))				{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))		{$shift=$_POST["shift"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))				{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if (strlen($shift)<2) {$shift='ALL';}
if (strlen($bottom_graph)<2) {$bottom_graph='NO';}
if (strlen($carrier_stats)<2) {$carrier_stats='NO';}
if (strlen($include_rollover)<2) {$include_rollover='NO';}

$report_name = 'Called Counts List IDs Report';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_text.="function openNewWindow(url)\n";
$JS_text.="  {\n";
$JS_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$JS_text.="  }\n";
$JS_onload="onload = function() {\n";

##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method FROM system_settings;";
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
	}
##### END SETTINGS LOOKUP #####
###########################################

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_list", "vicidial_log", "vicidial_closer_log", "user_call_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_list_table=use_archive_table("vicidial_list");
	$vicidial_closer_log_table=use_archive_table("vicidial_closer_log");
	$user_call_log_table=use_archive_table("user_call_log");
	$vicidial_log_table=use_archive_table("vicidial_log");
	}
else
	{
	$vicidial_list_table="vicidial_list";
	$vicidial_closer_log_table="vicidial_closer_log";
	$user_call_log_table="user_call_log";
	$vicidial_log_table="vicidial_log";
	}
#############

##### SERVER CARRIER LOGGING LOOKUP #####
$stmt = "SELECT count(*) FROM servers where carrier_logging_active='Y' and max_vicidial_trunks > 0;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$srv_conf_ct = mysqli_num_rows($rslt);
if ($srv_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$carrier_logging_active =		$row[0];
	}

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
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
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
if (!isset($list_ids)) {$list_ids = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

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
if ($DB) {echo "$stmt\n";}
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

# grab names of global statuses and statuses in the selected campaign
$stmt="SELECT status,status_name from vicidial_statuses order by status;";
$rslt=mysql_to_mysqli($stmt, $link);
$statuses_to_print = mysqli_num_rows($rslt);

$o=0;
$statuses_list=array();
while ($statuses_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$statuses_list["$rowx[0]"] = "$rowx[1]";
	$o++;
	}

$stmt="SELECT status,status_name from vicidial_campaign_statuses $whereLOGallowed_campaignsSQL order by status;";
$rslt=mysql_to_mysqli($stmt, $link);
$Cstatuses_to_print = mysqli_num_rows($rslt);

$o=0;
while ($Cstatuses_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$statuses_list["$rowx[0]"] = "$rowx[1]";
	$o++;
	}
# end grab status names



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

	if (preg_match("/YES/i",$include_rollover))
		{
		$stmt="select drop_inbound_group from vicidial_campaigns where campaign_id='$group[$i]' $LOGallowed_campaignsSQL and drop_inbound_group NOT LIKE \"%NONE%\" and drop_inbound_group is NOT NULL and drop_inbound_group != '';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$in_groups_to_print = mysqli_num_rows($rslt);
		if ($in_groups_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$group_drop_SQL .= "'$row[0]',";

			$rollover_groups_count++;
			}
		}

	$i++;
	}
if (strlen($group_drop_SQL) < 2)
	{$group_drop_SQL = "''";}
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) or (strlen($group_string) < 2) )
	{
	$group_SQL = "$LOGallowed_campaignsSQL";
	$group_drop_SQL = "";
	}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_drop_SQL = preg_replace('/,$/i', '',$group_drop_SQL);
	$both_group_SQLand = "and ( (campaign_id IN($group_drop_SQL)) or (campaign_id IN($group_SQL)) )";
	$both_group_SQL = "where ( (campaign_id IN($group_drop_SQL)) or (campaign_id IN($group_SQL)) )";
	$group_SQLand = "and campaign_id IN($group_SQL)";
	$group_SQL = "where campaign_id IN($group_SQL)";
	$group_drop_SQLand = "and campaign_id IN($group_drop_SQL)";
	$group_drop_SQL = "where campaign_id IN($group_drop_SQL)";
	}

$i=0;
$list_id_string='|';
$list_id_ct = count($list_ids);
while($i < $list_id_ct)
	{
	$list_id_string .= "$list_ids[$i]|";
	$list_id_SQL .= "'$list_ids[$i]',";
	$list_idQS .= "&list_ids[]=$list_ids[$i]";

	$i++;
	}
$list_id_title_str=$list_id_SQL;
$list_id_title_str=preg_replace('/\'/', '',$list_id_title_str);
$list_id_title_str = preg_replace('/,$/i', '',$list_id_title_str);
$list_id_title_str=_QXZ("$list_id_title_str");

# If ALL lists are selected, filter it down to all lists within selected campaigns
if ( preg_match('/\-\-ALL\-\-/',$list_id_string) )
	{
	$list_id_string='|';
	$list_id_SQL="";
	$list_idQS="";
	$list_stmt="select list_id from vicidial_lists $group_SQL";
	if ($DB) {echo $list_stmt."\n";}
	$list_rslt=mysql_to_mysqli($list_stmt, $link);
	while ($list_row=mysqli_fetch_row($list_rslt)) 
		{
		$list_id_string .= "$list_row[0]|";
		$list_id_SQL .= "'$list_row[0]',";
		$list_idQS .= "&list_ids[]=$list_row[0]";
		}
	}

$list_id_SQL = preg_replace('/,$/i', '',$list_id_SQL);
$list_id_SQLandVLJOIN = "and ".$vicidial_log_table.".lead_id=".$vicidial_list_table.".lead_id";
$list_id_SQLandVCLJOIN = "and ".$vicidial_closer_log_table.".lead_id=".$vicidial_list_table.".lead_id";
$list_id_SQLandUCLJOIN = "and ".$user_call_log_table.".lead_id=".$vicidial_list_table.".lead_id";
if (strlen($list_id_SQL)>0) 
	{
	$list_id_SQLandVLJOIN .= " and ".$vicidial_list_table.".list_id IN($list_id_SQL)";
	$list_id_SQLandVCLJOIN .= " and ".$vicidial_list_table.".list_id IN($list_id_SQL)";
	$list_id_SQLandUCLJOIN .= " and ".$vicidial_list_table.".list_id IN($list_id_SQL)";
	$list_id_SQL = "where list_id IN($list_id_SQL)";
	$list_id_SQLand = "and list_id IN($list_id_SQL)";
	}

/*
if ( (preg_match('/\-\-ALL\-\-/',$list_id_string) ) or ($list_id_ct < 1) or (strlen($list_id_string) < 2) )
	{
	$list_id_SQL = "";
	$list_id_drop_SQL = "";
	$skip_productivity_calc=0;
	}
else 
	{
	$list_id_SQL = preg_replace('/,$/i', '',$list_id_SQL);
	$skip_productivity_calc=1;
	}
*/

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HEADER.="<!DOCTYPE HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";

$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";

$HEADER.="$JS_text";
#$HEADER.="<script language=\"JavaScript\">\n";
$list_stmt="select list_id, list_name, campaign_id from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id asc";
$list_rslt=mysql_to_mysqli($list_stmt, $link);
$list_rows=mysqli_num_rows($list_rslt);
$list_options="<select name='list_ids[]' id='list_ids' multiple size=5>\n";
	if  (preg_match('/\-\-ALL\-\-/',$list_id_string))
		{$list_options.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL LISTS")." --</option>\n";}
	else
		{$list_options.="<option value=\"--ALL--\">-- "._QXZ("ALL LISTS")." --</option>\n";}


if ($list_rows>0) {

	$list_id_ary_str.="var list_id_ary=[";
	$list_name_ary_str.="var list_name_ary=[";
	$campaign_id_ary_str.="var campaign_id_ary=[";
	while ($list_row=mysqli_fetch_row($list_rslt)) {
		$list_id_ary_str.="'$list_row[0]',";
		$list_name_ary_str.="'$list_row[1]',";
		$campaign_id_ary_str.="'$list_row[2]',";

		if (preg_match("/\|$list_row[0]\|/i",$list_id_string)) {$list_options.="<option selected value=\"$list_row[0]\">$list_row[0] - $list_row[1]</option>\n";}
		  else {$list_options.="<option value=\"$list_row[0]\">$list_row[0] - $list_row[1]</option>\n";}

		#$list_options.="\t<option value='$list_row[0]'>$list_row[0] - $list_row[1]</option>\n";
	}
	$list_id_ary_str=preg_replace('/,$/', '', $list_id_ary_str)."];\n";
	$list_name_ary_str=preg_replace('/,$/', '', $list_name_ary_str)."];\n";
	$campaign_id_ary_str=preg_replace('/,$/', '', $campaign_id_ary_str)."];\n";

	$HEADER.=$list_id_ary_str;
	$HEADER.=$list_name_ary_str;
	$HEADER.=$campaign_id_ary_str;
}

$list_options.="</select>\n";

$HEADER.="function LoadLists(FromBox) {\n";
$HEADER.="	if (!FromBox) {alert(\"NO\"); return false;}\n";
$HEADER.="	var selectedCampaigns=\"|\";\n";
$HEADER.="	var selectedcamps = new Array();\n";
$HEADER.="\n";
$HEADER.="\n";
$HEADER.="\n";
$HEADER.="	for(i = 0; i < document.getElementById('group').options.length; i++) {\n";
$HEADER.="		if (document.getElementById('group').options[i].selected) {\n";
$HEADER.="			selectedCampaigns += document.getElementById('group').options[i].value+\"|\";\n";
$HEADER.="		} \n";
$HEADER.="	}\n";
$HEADER.="\n";
$HEADER.="	// Clear List menu\n";
$HEADER.="	document.getElementById('list_ids').options.length=0;\n";
$HEADER.="	var new_list = new Option();\n";
$HEADER.="	new_list.value = \"--ALL--\";\n";
$HEADER.="	new_list.text = \"--"._QXZ("ALL LISTS")."--\";\n";
$HEADER.="	document.getElementById('list_ids')[0] = new_list;\n";
$HEADER.="\n";
$HEADER.="	list_id_index=1;\n";
$HEADER.="	for (j=0; j<campaign_id_ary.length; j++) {\n";
$HEADER.="		var campaignID=\"/\|\"+campaign_id_ary[j]+\"\|/g\";\n";
$HEADER.="		var campaign_matches = selectedCampaigns.match(campaignID);\n";
$HEADER.="		if (campaign_matches) {\n";
$HEADER.="\n";
$HEADER.="			var new_list = new Option();\n";
$HEADER.="			new_list.value = list_id_ary[j];\n";
$HEADER.="			new_list.text = list_id_ary[j]+\" - \"+list_name_ary[j];\n";
$HEADER.="			document.getElementById('list_ids')[list_id_index] = new_list;\n";
$HEADER.="			list_id_index++;\n";
$HEADER.="		}\n";
$HEADER.="	}\n";
$HEADER.="}\n";

$HEADER.="</script>\n";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;
$draw_graph=1;

#require("admin_header.php");

$MAIN.="<b>"._QXZ("$report_name")."</b> $NWB#called_counts_multilist_report$NWE\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP><input type='checkbox' name='override_date' value='1' checked>"._QXZ("All dates")."<BR><BR>"._QXZ("Dates").":<BR>";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=outbound_rate VALUE=\"$outbound_rate\">\n";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

$MAIN.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

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

if (preg_match('/MSIE/i', $_SERVER['HTTP_USER_AGENT'])) {
	$JS_events="onBlur='LoadLists(this.form.group)' onKeyUp='LoadLists(this.form.group)'";
} else {
	$JS_events="onMouseUp='LoadLists(this.form.group)' onBlur='LoadLists(this.form.group)' onKeyUp='LoadLists(this.form.group)'";
}
$MAIN.="</TD><TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
$MAIN.="<SELECT multiple SIZE=5 NAME=group[] id='group' $JS_events>\n";
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
$MAIN.="</SELECT>\n";
$MAIN.="</TD><TD VALIGN=TOP>";
$MAIN.=_QXZ("Lists").": <font size=1>("._QXZ("optional, possibly slow").")</font><BR>\n";
$MAIN.=$list_options;
$MAIN.="</TD><TD VALIGN=TOP ALIGN=CENTER>";

if ($archives_available=="Y") 
	{
	$MAIN.="<BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}


$MAIN.="<BR><BR><INPUT type=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="<BR><BR><a href=\"$PHP_SELF\">"._QXZ("reset")."</a>";
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


if (strlen($QUERY_STRING) > 5)
	{
	$leads_in_list = 0;
	$leads_in_list_N = 0;
	$leads_in_list_Y = 0;
	
	if (!$override_date) {
		$date_range_title=" "._QXZ("FOR LEADS CALLED")." $query_date "._QXZ("THROUGH")." $end_date";
		$stmt="select distinct ".$vicidial_list_table.".lead_id from ".$vicidial_list_table.", ".$vicidial_log_table." where ".$vicidial_log_table.".call_date>='$query_date 00:00:00' and ".$vicidial_log_table.".call_date<='$end_date 23:59:59' $list_id_SQLandVLJOIN UNION select distinct ".$vicidial_list_table.".lead_id from ".$vicidial_list_table.", ".$vicidial_closer_log_table." where ".$vicidial_closer_log_table.".call_date>='$query_date 00:00:00' and ".$vicidial_closer_log_table.".call_date<='$end_date 23:59:59' $list_id_SQLandVCLJOIN;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if (!$rslt) {$MAIN.="<BR>**".mysqli_error($link)."<BR>$stmt";}
		if ($DB) {$MAIN.="$stmt<BR><BR>";}
		$lead_id_str="";
		if (mysqli_num_rows($rslt)>200000) {
			$MAIN.=_QXZ("WARNING: Query resulting from report parameters is too large.  Running report by list ID selection only.");
		} else if (mysqli_num_rows($rslt)>0) {
			while ($rowx=mysqli_fetch_row($rslt)) {
				$lead_id_str.="$rowx[0],";
			}
			$lead_id_str=preg_replace('/,$/', '', $lead_id_str);
			if (!$list_id_SQL) {
				$lead_id_str=" where lead_id in ($lead_id_str) ";
			} else {
				$lead_id_str=" and lead_id in ($lead_id_str) ";
			}
		} else {
			$lead_id_str=" and lead_id is null ";
		}
	}

	$stmt="select status, if(called_count >= 100, 100, called_count), count(*) from ".$vicidial_list_table." $list_id_SQL $lead_id_str group by status, if(called_count >= 100, 100, called_count) order by status,called_count;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if (!$rslt) {$MAIN.="<BR>**".mysqli_error($link)."<BR>$stmt";}
	if ($DB) {$MAIN.="$stmt<BR><BR>";}

	$status_called_to_print = mysqli_num_rows($rslt);

	$CSV_text="";

	$o=0;
	$sts=0;
	$first_row=1;
	$all_called_first=1000;
	$all_called_last=0;
	$count_statuses=array();
	$count_called=array();
	$count_count=array();
	$all_called_count=array();
	while ($status_called_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$leads_in_list = ($leads_in_list + $rowx[2]);
		$count_statuses[$o]			= $rowx[0];
		$count_called[$o]			= $rowx[1];
		$count_count[$o]			= $rowx[2];
		$all_called_count[$rowx[1]] = ($all_called_count[$rowx[1]] + $rowx[2]);

		if ( (strlen($status[$sts]) < 1) or ($status[$sts] != "$rowx[0]") )
			{
			if ($first_row) {$first_row=0;}
			else {$sts++;}
			$status[$sts] = "$rowx[0]";
			$status_called_first[$sts] = "$rowx[1]";
			if ($status_called_first[$sts] < $all_called_first) {$all_called_first = $status_called_first[$sts];}
			}
		$leads_in_sts[$sts] = ($leads_in_sts[$sts] + $rowx[2]);
		$status_called_last[$sts] = "$rowx[1]";
		if ($status_called_last[$sts] > $all_called_last) {$all_called_last = $status_called_last[$sts];}

		$o++;
		}

	$CSV_text="";
	$MAIN.="<center>\n";
	$MAIN.="<br><b>"._QXZ("CALLED COUNTS WITHIN LIST(S)")." $list_id_title_str$date_range_title:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"$PHP_SELF?DB=$DB$groupQS$list_idQS&query_date=$query_date&end_date=$end_date&override_date=$override_date&SUBMIT=$SUBMIT&file_download=1&search_archived_data=$search_archived_data\">["._QXZ("DOWNLOAD")."]</a></b><br>\n";
	$CSV_text.=_QXZ("CALLED COUNTS WITHIN LIST(S)")." $list_id_title_str$date_range_title:\n";
	$MAIN.="<TABLE width=700 cellspacing=1>\n";
	$MAIN.="<tr><td align=left><font size=1>"._QXZ("STATUS")."</td><td align=center><font size=1>"._QXZ("STATUS NAME")."</td>";
	$CSV_text.="\""._QXZ("STATUS")."\",\""._QXZ("STATUS NAME")."\",";
	$first = $all_called_first;
	while ($first <= $all_called_last)
		{
		if (preg_match('/1$|3$|5$|7$|9$/i', $first)) {$AB='bgcolor="#AFEEEE"';} 
		else{$AB='bgcolor="#E0FFFF"';}
		if ($first >= 100) {$Fplus='+';}
		else {$Fplus='';}
		$MAIN.="<td align=center $AB><font size=1>$first$Fplus</td>";
		$CSV_text.="\"$first$Fplus\",";
		$first++;
		}
	$MAIN.="<td align=center><font size=1>"._QXZ("SUBTOTAL")."</td></tr>\n";
	$CSV_text.="\""._QXZ("SUBTOTAL")."\"\n";

	$sts=0;
	$statuses_called_to_print = count($status);
	while ($statuses_called_to_print > $sts) 
		{
		$Pstatus = $status[$sts];
		if (preg_match("/1$|3$|5$|7$|9$/i", $sts))
			{$bgcolor='bgcolor="#B9CBFD"';   $AB='bgcolor="#9BB9FB"';} 
		else
			{$bgcolor='bgcolor="#9BB9FB"';   $AB='bgcolor="#B9CBFD"';}
	#	$MAIN.="$status[$sts]|$status_called_first[$sts]|$status_called_last[$sts]|$leads_in_sts[$sts]|\n";
	#	$MAIN.="$status[$sts]|";
		$MAIN.="<tr $bgcolor><td><font size=1>$Pstatus</td><td><font size=1>$statuses_list[$Pstatus]</td>";
		$CSV_text.="\"$Pstatus\",\"$statuses_list[$Pstatus]\",";

		$first = $all_called_first;
		while ($first <= $all_called_last)
			{
			if (preg_match("/1$|3$|5$|7$|9$/i", $sts))
				{
				if (preg_match('/1$|3$|5$|7$|9$/i', $first)) {$AB='bgcolor="#9BB9FB"';} 
				else{$AB='bgcolor="#B9CBFD"';}
				}
			else
				{
				if (preg_match("/0$|2$|4$|6$|8$/i", $first)) {$AB='bgcolor="#9BB9FB"';} 
				else{$AB='bgcolor="#B9CBFD"';}
				}

			$called_printed=0;
			$o=0;
			while ($status_called_to_print > $o) 
				{
				if ( ($count_statuses[$o] == "$Pstatus") and ($count_called[$o] == "$first") )
					{
					$called_printed++;
					$MAIN.="<td $AB><font size=1> $count_count[$o]</td>";
					$CSV_text.="\"$count_count[$o]\",";
					}

				$o++;
				}
			if (!$called_printed) 
				{
				$MAIN.="<td $AB><font size=1> &nbsp;</td>";
				$CSV_text.="\"\",";
				}
			$first++;
			}
		$MAIN.="<td><font size=1>$leads_in_sts[$sts]</td></tr>\n\n";
		$CSV_text.="\"$leads_in_sts[$sts]\"\n";

		$sts++;
		}

	$MAIN.="<tr><td align=center colspan=2><b><font size=1>"._QXZ("TOTAL")."</td>";
	$CSV_text.="\"\",\""._QXZ("TOTAL")."\",";
	$first = $all_called_first;
	while ($first <= $all_called_last)
		{
		if (preg_match('/1$|3$|5$|7$|9$/i', $first)) {$AB='bgcolor="#AFEEEE"';} 
		else{$AB='bgcolor="#E0FFFF"';}
		$MAIN.="<td align=center $AB><b><font size=1>$all_called_count[$first]</td>";
		$CSV_text.="\"$all_called_count[$first]\",";
		$first++;
		}
	$MAIN.="<td align=center><b><font size=1>$leads_in_list</td></tr>\n";
	$CSV_text.="\"$leads_in_list\",";

	$MAIN.="</table></center><br>\n";
	$MAIN.="</BODY></HTML>\n";

	if ($file_download>0) 
		{
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "CALLED_COUNTS_MULTILIST_report_$US$FILE_TIME.csv";
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
		echo $HEADER;
		require("admin_header.php");
		echo $MAIN;
		}
	}
else
	{
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

exit;
?>