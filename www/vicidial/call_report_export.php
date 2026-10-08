<?php
# call_report_export.php
#
# displays options to select for downloading of leads and their vicidial_log
# and/or vicidial_closer_log information by status, list_id and date range.
# downloads to a flat text file that is tab delimited
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 90310-2247 - First build
# 90330-1343 - Added more debug info, bug fixes
# 90508-0644 - Changed to PHP long tags
# 90721-1137 - Added rank and owner as vicidial_list fields
# 91121-0253 - Added list name, list description and status name
# 100119-1039 - Filtered comments for \n newlines
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100507-1413 - Added headers for export
# 100702-1332 - Added custom fields option
# 100712-1324 - Added system setting slave server option
# 100713-0101 - Added recordings fields option (for filename, recording ID and URL)
# 100713-1050 - Fixed minor custom fields issue
# 100802-2347 - Added User Group Allowed Reports option validation and allowed campaigns restrictions
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
#               Allow level 7 users to view this report
# 110224-1135 - Added call_notes export option
# 110316-2121 - Added export_fields option
# 110329-1330 - Added more fields to EXTENDED option
# 110531-1945 - Changed first phone_number field to phone_number_dialed, issue #495
# 110721-2027 - Added IVR export options
# 110911-1445 - Added did fields to the EXTENDED format
# 111010-1930 - Added ALTERNATE_1 format
# 111104-1240 - Added user_group restrictions for selecting in-groups
# 130414-0122 - Added report logging
# 130610-0952 - Finalized changing of all ereg instances to preg
# 130620-1725 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0728 - Changed to mysqli PHP functions, Added fields to extended output
# 140108-0727 - Added webserver and hostname to report logging
# 141001-2200 - Finalized adding QXZ translation to all admin files
# 141230-1349 - Added code for on-the-fly language translations display
# 150108-1110 - Fixed issue with inbound and no outbound calls export
# 150126-1205 - Fixed issue with Extended and Alt formats
# 150727-2143 - Enabled user features for hiding phone numbers and lead data
# 150903-1536 - Added compatibility for custom fields data options
# 150909-0747 - Fixed issues with translated select list values, issue #885
# 151125-1621 - Added search archive option
# 160121-1236 - Added EXTENDED_2 option with term_reason field
# 160510-2100 - Added coding to remove tab characters from the data
# 160914-2200 - Added option to grab reports by either call date or entry date
# 161017-1242 - Added DID custom variables to EXTENDED_3 output option
# 161111-1232 - Fixed debug output to work with export
# 161122-1136 - Added code to check for recordings in archived/non-archived tables if none found
# 170409-1547 - Added IP List validation code
# 180418-1555 - Fix for missing call notes on inbound calls
# 190116-2116 - Added ---ALL--- options for Campaigns and In-Groups
# 190610-2035 - Fixed admin hide phone issue
# 190926-0925 - Fixes for PHP7
# 191119-1731 - Fix for alternate server url for recordings, issue #1175
# 200115-1151 - Added ALTERNATE_2 export option with alternate header option in options.php
# 200709-2106 - Added EXTENDED_4 export option with logged list_id from time of call
# 210911-1907 - Fix for --ALL-- selection user-group permission issue
# 211119-1500 - Fix for status names 
# 220301-1603 - Added allow_web_debug system setting
# 230622-1652 - Added filtering by time (hours), and option for single user exports.
# 230623-1025 - Added sort_dir sort direction
# 240801-1130 - Code updates for PHP8 compatibility
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
if (isset($_GET["query_hour"]))				{$query_hour=$_GET["query_hour"];}
	elseif (isset($_POST["query_hour"]))	{$query_hour=$_POST["query_hour"];}
if (isset($_GET["date_field"]))				{$date_field=$_GET["date_field"];}
	elseif (isset($_POST["date_field"]))	{$date_field=$_POST["date_field"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["end_hour"]))				{$end_hour=$_GET["end_hour"];}
	elseif (isset($_POST["end_hour"]))		{$end_hour=$_POST["end_hour"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["sort_dir"]))				{$sort_dir=$_GET["sort_dir"];}
	elseif (isset($_POST["sort_dir"]))		{$sort_dir=$_POST["sort_dir"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["status"]))					{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))		{$status=$_POST["status"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["run_export"]))				{$run_export=$_GET["run_export"];}
	elseif (isset($_POST["run_export"]))	{$run_export=$_POST["run_export"];}
if (isset($_GET["header_row"]))				{$header_row=$_GET["header_row"];}
	elseif (isset($_POST["header_row"]))	{$header_row=$_POST["header_row"];}
if (isset($_GET["rec_fields"]))				{$rec_fields=$_GET["rec_fields"];}
	elseif (isset($_POST["rec_fields"]))	{$rec_fields=$_POST["rec_fields"];}
if (isset($_GET["custom_fields"]))			{$custom_fields=$_GET["custom_fields"];}
	elseif (isset($_POST["custom_fields"]))	{$custom_fields=$_POST["custom_fields"];}
if (isset($_GET["call_notes"]))				{$call_notes=$_GET["call_notes"];}
	elseif (isset($_POST["call_notes"]))	{$call_notes=$_POST["call_notes"];}
if (isset($_GET["export_fields"]))			{$export_fields=$_GET["export_fields"];}
	elseif (isset($_POST["export_fields"]))	{$export_fields=$_POST["export_fields"];}
if (isset($_GET["ivr_export"]))				{$ivr_export=$_GET["ivr_export"];}
	elseif (isset($_POST["ivr_export"]))	{$ivr_export=$_POST["ivr_export"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$US='_';
$MT[0]='';
$ip = getenv("REMOTE_ADDR");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_TIME = date("Ymd-His");
$STARTtime = date("U");
if (!is_array($campaign)) {$campaign = array();}
if (!is_array($group)) {$group = array();}
if (!is_array($user_group)) {$user_group = array();}
if (!is_array($list_id)) {$list_id = array();}
if (!is_array($status)) {$status = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}
if (!isset($query_hour)) {$query_hour = "00";}
if (!isset($end_hour)) {$end_hour = "23";}
if (!isset($sort_dir)) {$sort_dir = "asc";}
if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Export Calls Report';
$db_source = 'M';
$file_exported=0;
$DBout='';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,custom_fields_enabled,enable_languages,language_method,active_modules,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$DBout .= "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$custom_fields_enabled =		$row[4];
	$SSenable_languages =			$row[5];
	$SSlanguage_method =			$row[6];
	$active_modules =				$row[7];
	$SSallow_web_debug =			$row[8];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $end_date);
$query_hour = preg_replace('/[^0-9]/', '', $query_hour);
$end_hour = preg_replace('/[^0-9]/', '', $end_hour);
$date_field = preg_replace('/[^-\.\_0-9a-zA-Z]/', '', $date_field);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$ivr_export = preg_replace('/[^-_0-9a-zA-Z]/', '', $ivr_export);
$run_export = preg_replace('/[^-_0-9a-zA-Z]/', '', $run_export);
$header_row = preg_replace('/[^-_0-9a-zA-Z]/', '', $header_row);
$rec_fields = preg_replace('/[^-_0-9a-zA-Z]/', '', $rec_fields);
$custom_fields = preg_replace('/[^-_0-9a-zA-Z]/', '', $custom_fields);
$call_notes = preg_replace('/[^-_0-9a-zA-Z]/', '', $call_notes);
$export_fields = preg_replace('/[^-_0-9a-zA-Z]/', '', $export_fields);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$query_time=$query_hour.":00:00";
$end_time=$end_hour.":59:59";


# Variables filtered further down in the code
# $campaign
# $group
# $user_group
# $list_id
# $status

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	$sort_dir = preg_replace('/[^a-zA-Z]/', '', $sort_dir);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$sort_dir = preg_replace('/[^\p{L}]/u', '', $sort_dir);
	}

switch ($sort_dir) {
	default:
	case "asc":
		$asc="checked";
		$desc="";
		break;
	case "desc":
		$asc="";
		$desc="checked";
		break;
}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_log", "vicidial_closer_log", "vicidial_agent_log", "vicidial_log_extended", "recording_log", "vicidial_carrier_log", "vicidial_cpd_log", "vicidial_did_log", "vicidial_outbound_ivr_log");
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
	$vicidial_log_extended_table=use_archive_table("vicidial_log_extended");
	$recording_log_table=use_archive_table("recording_log");
	$vicidial_carrier_log_table=use_archive_table("vicidial_carrier_log");
	$vicidial_cpd_log_table=use_archive_table("vicidial_cpd_log");
	$vicidial_did_log_table=use_archive_table("vicidial_did_log");
	$vicidial_outbound_ivr_log_table=use_archive_table("vicidial_outbound_ivr_log");
	}
else
	{
	$vicidial_log_table="vicidial_log";
	$vicidial_closer_log_table="vicidial_closer_log";
	$vicidial_agent_log_table="vicidial_agent_log";
	$vicidial_log_extended_table="vicidial_log_extended";
	$recording_log_table="recording_log";
	$vicidial_carrier_log_table="vicidial_carrier_log";
	$vicidial_cpd_log_table="vicidial_cpd_log";
	$vicidial_did_log_table="vicidial_did_log";
	$vicidial_outbound_ivr_log_table="vicidial_outbound_ivr_log";
	}
#############

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$DBout .= "|$stmt|\n";}
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
	if ($DB) {$DBout .= "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {$DBout .= "|$stmt|\n";}
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

$stmt="SELECT export_reports,user_group,admin_hide_lead_data,admin_hide_phone_data,admin_cf_show_hidden from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGexport_reports =			$row[0];
$LOGuser_group =				$row[1];
$LOGadmin_hide_lead_data =		$row[2];
$LOGadmin_hide_phone_data =		$row[3];
$LOGadmin_cf_show_hidden =		$row[4];

if ($LOGexport_reports < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for export reports").": |$PHP_AUTH_USER|\n";
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
if ($DB) {$DBout .= "$stmt\n";}
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
	if ($DB) {$DBout .= "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	$webserver_id = mysqli_insert_id($link);
	}

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {$DBout .= "|$stmt|\n";}
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

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$DBout .= "|$stmt|\n";}
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

##### START RUN THE EXPORT AND OUTPUT FLAT DATA FILE #####
if ($run_export > 0)
	{

	if ($user)
		{
		$user_SQL=" and vl.user='$user' ";
		}
	else
		{
		$user_SQL="";
		}

	if ($date_field=="entry_date") {
		$date_field = "vi.entry_date";
		# Since entry_date only appears when the EXTENDED export option is selected, EXTENDED will automatically be used when entry_date is selected as the date field.
		if (!preg_match("/EXTENDED/", $export_fields)) {$export_fields = "EXTENDED";}
	} else {
		$date_field="vl.call_date";
	}

	$campaign_ct = count($campaign);
	$group_ct = count($group);
	$user_group_ct = count($user_group);
	$list_ct = count($list_id);
	$status_ct = count($status);
	$campaign_string='|';
	$group_string='|';
	$user_group_string='|';
	$list_string='|';
	$status_string='|';

	# If LOCATION or ALL is seleced in recording fields, we need to get information from the vicidial_servers table
	if (preg_match("/(ALL|LOCATION)/", $rec_fields))
		{
		$server_recording_links = array();
		$stmt="SELECT recording_web_link, server_ip, alt_server_ip, external_server_ip FROM servers";
		$rslt=mysql_to_mysqli($stmt, $link);
		while (($row = mysqli_fetch_row($rslt)))
			{
			$server_recording_links[$row[1]] = $row[1];
			if ($row[0] == 'ALT_IP')
				{$server_recording_links[$row[1]] = $row[2];}
			elseif ($row[0] == 'EXTERNAL_IP')
				{$server_recording_links[$row[1]] = $row[3];}
			}
		}

	# Get campaigns for "ALL"
	$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$ALL_available_campaigns = mysqli_num_rows($rslt);
	$h=0;
	$ALL_campaign_SQL="";
	while ($h < $ALL_available_campaigns)
		{
		$row=mysqli_fetch_row($rslt);
		$ALL_campaign_SQL .= "'$row[0]',";
		$h++;
		}

	$i=0;
	while($i < $campaign_ct)
		{
		$campaign[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $campaign[$i]);
		if ( (preg_match("/ $campaign[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
			{
			$campaign_string .= "$campaign[$i]|";
			$campaign_SQL .= "'$campaign[$i]',";
			}
		# Need this for next few lines
		if (preg_match("/\-\-ALL\-\-/",$campaign[$i]) && !preg_match("/\-\-ALL\-\-/",$campaign_string))
			{
			$campaign_string .= "$campaign[$i]|";
			}
		$i++;
		}
	if ( (preg_match('/\s\-\-NONE\-\-\s/',$campaign_string) ) or ($campaign_ct < 1) )
		{
		$campaign_SQL = "campaign_id IN('')";
		$RUNcampaign=0;
		}
	else
		{
		if (preg_match("/\-\-ALL\-\-/",$campaign_string) )
			{
			$campaign_SQL = preg_replace('/,$/i', '',$ALL_campaign_SQL);
			$campaign_SQL = "and vl.campaign_id IN($campaign_SQL)";
			$RUNcampaign++;
			}
		else
			{
			$campaign_SQL = preg_replace('/,$/i', '',$campaign_SQL);
			$campaign_SQL = "and vl.campaign_id IN($campaign_SQL)";
			$RUNcampaign++;
			}
		}
	if ($DB) {echo "** $regexLOGallowed_campaigns --- $LOGallowed_campaigns --- $campaign_string **\n";}


	# Get inbound groups for "ALL"
	$stmt="select group_id from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$ALL_available_groups = mysqli_num_rows($rslt);
	$h=0;
	$ALL_group_SQL="";
	while($h < $ALL_available_groups)
		{
		$row=mysqli_fetch_row($rslt);
		$ALL_group_SQL .= "'$row[0]',";
		$h++;
		}
	
	$i=0;
	while($i < $group_ct)
		{
		$group[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $group[$i]);
		$group_string .= "$group[$i]|";
		$group_SQL .= "'$group[$i]',";
		$i++;
		}
	if ( (preg_match('/\s\-\-NONE\-\-\s/',$group_string) ) or ($group_ct < 1) )
		{
		$group_SQL = "''";
		$group_SQL = "campaign_id IN('')";
		$RUNgroup=0;
		}
	else
		{
		if ( (preg_match("/\-\-ALL\-\-/",$group_string) ) or ($group_ct < 1) )
			{
			$group_SQL = preg_replace('/,$/i', '',$ALL_group_SQL);
			$group_SQL = "and vl.campaign_id IN($group_SQL)";
			$RUNgroup++;
			}
		else
			{
			$group_SQL = preg_replace('/,$/i', '',$group_SQL);
			$group_SQL = "and vl.campaign_id IN($group_SQL)";
			$RUNgroup++;
			}
		}
	
	# Get user groups for "ALL"
	$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$ALL_available_user_groups = mysqli_num_rows($rslt);
	$h=0;
	$ALL_user_group_SQL="";
	while ($h < $ALL_available_user_groups)
		{
		$row=mysqli_fetch_row($rslt);
		$ALL_user_group_SQL .= "'$row[0]',";
		$h++;
		}

	$i=0;
	while($i < $user_group_ct)
		{
		$user_group[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $user_group[$i]);
		$user_group_string .= "$user_group[$i]|";
		$user_group_SQL .= "'$user_group[$i]',";
		$i++;
		}
	if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
		{
		$user_group_SQL = preg_replace('/,$/i', '',$ALL_user_group_SQL);
		$user_group_SQL = "and (vl.user_group IN($user_group_SQL) or vl.user_group is null)";
		}
	else
		{
		$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
		$user_group_SQL = "and vl.user_group IN($user_group_SQL)";
		}



	# Get lists for "ALL"
	$stmt="select list_id from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$ALL_available_lists = mysqli_num_rows($rslt);
	$h=0;
	$ALL_list_SQL="";
	while ($h < $ALL_available_lists)
		{
		$row=mysqli_fetch_row($rslt);
		$ALL_list_SQL .= "'$row[0]',";
		$h++;
		}

	$i=0;
	while($i < $list_ct)
		{
		$list_id[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $list_id[$i]);
		$list_string .= "$list_id[$i]|";
		$list_SQL .= "'$list_id[$i]',";
		$i++;
		}
	if ( (preg_match('/\-\-ALL\-\-/',$list_string) ) or ($list_ct < 1) )
		{
		$list_SQL = preg_replace('/,$/i', '',$ALL_list_SQL);
		$list_SQL = "and vl.list_id IN($list_SQL)";
		}
	else
		{
		$list_SQL = preg_replace('/,$/i', '',$list_SQL);
		$list_SQL = "and vi.list_id IN($list_SQL)";
		}



	$stmt="select status from vicidial_statuses order by status;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$ALL_available_statuses = mysqli_num_rows($rslt);
	$h=0;
	$ALL_status_SQL="";
	while ($h < $ALL_available_statuses)
		{
		$row=mysqli_fetch_row($rslt);
		$ALL_status_SQL .= "'$row[0]',";
		$h++;
		}

	$stmt="select distinct status from vicidial_campaign_statuses $whereLOGallowed_campaignsSQL order by status;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$ALL_available_Cstatuses = mysqli_num_rows($rslt);
	$j=0;
	while ($j < $ALL_available_Cstatuses)
		{
		$row=mysqli_fetch_row($rslt);
		$ALL_status_SQL .= "'$row[0]',";
		$j++;
		}


	$i=0;
	while($i < $status_ct)
		{
		$status[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $status[$i]);
		$status_string .= "$status[$i]|";
		$status_SQL .= "'$status[$i]',";
		$i++;
		}
	if ( (preg_match('/\-\-ALL\-\-/',$status_string) ) or ($status_ct < 1) )
		{
		$status_SQL = preg_replace('/,$/i', '',$ALL_status_SQL);
		$status_SQL = "and vl.status IN($status_SQL)";
		}
	else
		{
		$status_SQL = preg_replace('/,$/i', '',$status_SQL);
		$status_SQL = "and vl.status IN($status_SQL)";
		}

	$export_fields_SQL='';
	$EFheader='';
	if ($export_fields == 'EXTENDED')
		{
		$export_fields_SQL = ",entry_date,vl.called_count,last_local_call_time,modify_date,called_since_last_reset";
		$EFheader = "\tentry_date\tcalled_count\tlast_local_call_time\tmodify_date\tcalled_since_last_reset";
		}
	if ($export_fields == 'EXTENDED_2')
		{
		$export_fields_SQL = ",entry_date,vl.called_count,last_local_call_time,modify_date,called_since_last_reset,term_reason";
		$EFheader = "\tentry_date\tcalled_count\tlast_local_call_time\tmodify_date\tcalled_since_last_reset\tterm_reason";
		}
	if ($export_fields == 'EXTENDED_3')
		{
		$export_fields_SQL = ",entry_date,vl.called_count,last_local_call_time,modify_date,called_since_last_reset,term_reason";
		$EFheader = "\tentry_date\tcalled_count\tlast_local_call_time\tmodify_date\tcalled_since_last_reset\tterm_reason";
		}
	if ($export_fields == 'EXTENDED_4')
		{
		$export_fields_SQL = ",vl.called_count,vl.list_id";
		$EFheader = "\tlog_called_count\tlog_list_id";
		}
	if ($export_fields == 'ALTERNATE_1')
		{
		$export_fields_SQL = ",vl.called_count,last_local_call_time";
		$EFheader = "|called_count|last_local_call_time";
		}

	if ($DB > 0)
		{
		$DBout .= "<BR>\n";
		$DBout .= "$campaign_ct|$campaign_string|$campaign_SQL\n";
		$DBout .= "<BR>\n";
		$DBout .= "$group_ct|$group_string|$group_SQL\n";
		$DBout .= "<BR>\n";
		$DBout .= "$user_group_ct|$user_group_string|$user_group_SQL\n";
		$DBout .= "<BR>\n";
		$DBout .= "$list_ct|$list_string|$list_SQL\n";
		$DBout .= "<BR>\n";
		$DBout .= "$status_ct|$status_string|$status_SQL\n";
		$DBout .= "<BR>\n";
		}

	$outbound_calls=0;
	$export_rows=array();
	$export_status = array();
	$export_list_id = array();
	$export_lead_id = array();
	$export_uniqueid = array();
	$export_vicidial_id = array();
	$export_entry_list_id = array();
	$export_wrapup_time = array();
	$export_queue_time = array();
	$export_rows = array();
	$k=0;
	if ($RUNcampaign > 0)
		{
		if ( ($export_fields == 'EXTENDED') or ($export_fields == 'EXTENDED_2') or ($export_fields == 'EXTENDED_3') or ($export_fields == 'EXTENDED_4') )
			{
			$stmt = "SELECT vl.call_date,vl.phone_number,vl.status,vl.user,vu.full_name,vl.campaign_id,vi.vendor_lead_code,vi.source_id,vi.list_id,vi.gmt_offset_now,vi.phone_code,vi.phone_number,vi.title,vi.first_name,vi.middle_initial,vi.last_name,vi.address1,vi.address2,vi.address3,vi.city,vi.state,vi.province,vi.postal_code,vi.country_code,vi.gender,vi.date_of_birth,vi.alt_phone,vi.email,vi.security_phrase,vi.comments,vl.length_in_sec,vl.user_group,vl.alt_dial,vi.rank,vi.owner,vi.lead_id,vl.uniqueid,vi.entry_list_id, ifnull(val.dispo_sec+val.dead_sec,0)$export_fields_SQL from vicidial_users vu,vicidial_list vi,".$vicidial_log_table." vl LEFT OUTER JOIN ".$vicidial_agent_log_table." val ON vl.uniqueid=val.uniqueid and vl.lead_id=val.lead_id and vl.user=val.user where ".$date_field." >= '$query_date $query_time' and ".$date_field." <= '$end_date $end_time' and vu.user=vl.user and vi.lead_id=vl.lead_id $list_SQL $campaign_SQL $user_SQL $user_group_SQL $status_SQL order by ".$date_field." $sort_dir limit 1000000;";
			}
		else
			{
			$stmt = "SELECT vl.call_date,vl.phone_number,vl.status,vl.user,vu.full_name,vl.campaign_id,vi.vendor_lead_code,vi.source_id,vi.list_id,vi.gmt_offset_now,vi.phone_code,vi.phone_number,vi.title,vi.first_name,vi.middle_initial,vi.last_name,vi.address1,vi.address2,vi.address3,vi.city,vi.state,vi.province,vi.postal_code,vi.country_code,vi.gender,vi.date_of_birth,vi.alt_phone,vi.email,vi.security_phrase,vi.comments,vl.length_in_sec,vl.user_group,vl.alt_dial,vi.rank,vi.owner,vi.lead_id,vl.uniqueid,vi.entry_list_id$export_fields_SQL from vicidial_users vu,".$vicidial_log_table." vl,vicidial_list vi where ".$date_field." >= '$query_date $query_time' and ".$date_field." <= '$end_date $end_time' and vu.user=vl.user and vi.lead_id=vl.lead_id $list_SQL $campaign_SQL $user_SQL $user_group_SQL $status_SQL order by ".$date_field." $sort_dir limit 1000000;";
			}
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$DBout .= "$stmt\n";}
		$outbound_to_print = mysqli_num_rows($rslt);
		if ( ($outbound_to_print < 1) and ($RUNgroup < 1) )
			{
			echo _QXZ("There are no outbound calls during this time period for these parameters")."\n";
			if ($DB) {echo "$stmt\n";}
			exit;
			}
		else
			{
			$i=0;
			while ($i < $outbound_to_print)
				{
				$row=mysqli_fetch_row($rslt);

				$row[29] = preg_replace("/\n|\r/",'!N',$row[29]);

				$export_status[$k] =		$row[2];
				$export_campaign_id[$k] =	$row[5];
				$export_list_id[$k] =		$row[8];
				$export_lead_id[$k] =		$row[35];
				$export_uniqueid[$k] =		$row[36];
				$export_vicidial_id[$k] =	$row[36];
				$export_entry_list_id[$k] =	$row[37];
				$export_wrapup_time[$k] =		$row[38];
				$export_queue_time[$k] =		0;

				if ($LOGadmin_hide_phone_data != '0')
					{
					if ($DB > 0) {$DBout .= "HIDEPHONEDATA|$row[1]|$LOGadmin_hide_phone_data|\n";}
					$phone_temp = $row[1];
					$phone_lead_temp = $row[11];
					if ( (strlen($phone_temp) > 0) or (strlen($phone_lead_temp) > 0) )
						{
						if ($LOGadmin_hide_phone_data == '4_DIGITS')
							{
							$row[1] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);
							$row[11] = str_repeat("X", (strlen($phone_lead_temp) - 4)) . substr($phone_lead_temp,-4,4);
							}
						elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
							{
							$row[1] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);
							$row[11] = str_repeat("X", (strlen($phone_lead_temp) - 3)) . substr($phone_lead_temp,-3,3);
							}
						elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
							{
							$row[1] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);
							$row[11] = str_repeat("X", (strlen($phone_lead_temp) - 2)) . substr($phone_lead_temp,-2,2);
							}
						else
							{
							$row[1] = preg_replace("/./",'X',$phone_temp);
							$row[11] = preg_replace("/./",'X',$phone_lead_temp);
							}
						}
					}
				if ($LOGadmin_hide_lead_data != '0')
					{
					if ($DB > 0) {$DBout .= "HIDELEADDATA|$row[6]|$row[7]|$row[12]|$row[13]|$row[14]|$row[15]|$row[16]|$row[17]|$row[18]|$row[19]|$row[20]|$row[21]|$row[22]|$row[26]|$row[27]|$row[28]|$LOGadmin_hide_lead_data|\n";}
					if (strlen($row[6]) > 0)
						{$data_temp = $row[6];   $row[6] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[7]) > 0)
						{$data_temp = $row[7];   $row[7] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[12]) > 0)
						{$data_temp = $row[12];   $row[12] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[13]) > 0)
						{$data_temp = $row[13];   $row[13] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[14]) > 0)
						{$data_temp = $row[14];   $row[14] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[15]) > 0)
						{$data_temp = $row[15];   $row[15] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[16]) > 0)
						{$data_temp = $row[16];   $row[16] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[17]) > 0)
						{$data_temp = $row[17];   $row[17] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[18]) > 0)
						{$data_temp = $row[18];   $row[18] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[19]) > 0)
						{$data_temp = $row[19];   $row[19] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[20]) > 0)
						{$data_temp = $row[20];   $row[20] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[21]) > 0)
						{$data_temp = $row[21];   $row[21] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[22]) > 0)
						{$data_temp = $row[22];   $row[22] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[26]) > 0)
						{$data_temp = $row[26];   $row[26] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[27]) > 0)
						{$data_temp = $row[27];   $row[27] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[28]) > 0)
						{$data_temp = $row[28];   $row[28] = preg_replace("/./",'X',$data_temp);}
					}

				### PARSE TAB CHARACTERS FROM THE DATA ITSELF
				for ($t=0; $t<count($row); $t++)
					{
					$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
					}

				$export_fieldsDATA='';
				if ($export_fields == 'ALTERNATE_1')
					{
					$ALTcall_date = $row[0];
					$LASTcall_date = $row[39];
					$ALTcall_date = preg_replace("/-| |:|\d\d$/",'',$ALTcall_date);
					$LASTcall_date = preg_replace("/-| |:|\d\d$/",'',$LASTcall_date);
					$export_fieldsDATA = "$row[38]|$LASTcall_date|";
					$export_rows[$k] = "$ALTcall_date|$row[1]|$row[2]|$row[5]|$row[6]|$row[7]|$row[13]|$row[15]|$row[30]|$export_fieldsDATA";
					}
				else
					{
					if ($export_fields == 'EXTENDED')
						{$export_fieldsDATA = "$row[39]\t$row[40]\t$row[41]\t$row[42]\t$row[43]\t";}
					if ($export_fields == 'EXTENDED_2')
						{$export_fieldsDATA = "$row[39]\t$row[40]\t$row[41]\t$row[42]\t$row[43]\t$row[44]\t";}
					if ($export_fields == 'EXTENDED_3')
						{$export_fieldsDATA = "$row[39]\t$row[40]\t$row[41]\t$row[42]\t$row[43]\t$row[44]\t";}
					if ($export_fields == 'EXTENDED_4')
						{$export_fieldsDATA = "$row[39]\t$row[40]\t";}
					if ($export_fields == 'ALTERNATE_2')
						{$export_rows[$k] = "$row[18]\t$row[13]\t$row[15]\t$row[11]\t$row[17]\t!STATUS_DESCRIPTION!\t$row[0]";}
					else
						{
						$export_rows[$k] = "$row[0]\t$row[1]\t$row[2]\t$row[3]\t$row[4]\t$row[5]\t$row[6]\t$row[7]\t$row[8]\t$row[9]\t$row[10]\t$row[11]\t$row[12]\t$row[13]\t$row[14]\t$row[15]\t$row[16]\t$row[17]\t$row[18]\t$row[19]\t$row[20]\t$row[21]\t$row[22]\t$row[23]\t$row[24]\t$row[25]\t$row[26]\t$row[27]\t$row[28]\t$row[29]\t$row[30]\t$row[31]\t$row[32]\t$row[33]\t$row[34]\t$row[35]\t$export_fieldsDATA";
						}
					}
				$i++;
				$k++;
				$outbound_calls++;
				}
			}
		}
	else
		{if ($DB) {$DBout .= "NO OUTBOUND CALLS: $RUNcampaign|$campaign_string|$campaign_ct\n";}}

	if ($RUNgroup > 0)
		{
		if ( ($export_fields == 'EXTENDED') or ($export_fields == 'EXTENDED_2') or ($export_fields == 'EXTENDED_3') or ($export_fields == 'EXTENDED_4') )
			{
			$stmtA = "SELECT vl.call_date,vl.phone_number,vl.status,vl.user,vu.full_name,vl.campaign_id,vi.vendor_lead_code,vi.source_id,vi.list_id,vi.gmt_offset_now,vi.phone_code,vi.phone_number,vi.title,vi.first_name,vi.middle_initial,vi.last_name,vi.address1,vi.address2,vi.address3,vi.city,vi.state,vi.province,vi.postal_code,vi.country_code,vi.gender,vi.date_of_birth,vi.alt_phone,vi.email,vi.security_phrase,vi.comments,vl.length_in_sec,vl.user_group,vl.queue_seconds,vi.rank,vi.owner,vi.lead_id,vl.closecallid,vi.entry_list_id,vl.uniqueid,val.campaign_id, ifnull(val.dispo_sec+val.dead_sec,0)$export_fields_SQL from vicidial_users vu,vicidial_list vi,".$vicidial_closer_log_table." vl LEFT OUTER JOIN ".$vicidial_agent_log_table." val ON vl.uniqueid=val.uniqueid and vl.lead_id=val.lead_id and vl.user=val.user where ".$date_field." >= '$query_date $query_time' and ".$date_field." <= '$end_date $end_time' and vu.user=vl.user and vi.lead_id=vl.lead_id $list_SQL $group_SQL $user_SQL $user_group_SQL $status_SQL order by ".$date_field." $sort_dir limit 1000000;";
			}
		else
			{
			$stmtA = "SELECT vl.call_date,vl.phone_number,vl.status,vl.user,vu.full_name,vl.campaign_id,vi.vendor_lead_code,vi.source_id,vi.list_id,vi.gmt_offset_now,vi.phone_code,vi.phone_number,vi.title,vi.first_name,vi.middle_initial,vi.last_name,vi.address1,vi.address2,vi.address3,vi.city,vi.state,vi.province,vi.postal_code,vi.country_code,vi.gender,vi.date_of_birth,vi.alt_phone,vi.email,vi.security_phrase,vi.comments,vl.length_in_sec,vl.user_group,vl.queue_seconds,vi.rank,vi.owner,vi.lead_id,vl.closecallid,vi.entry_list_id,vl.uniqueid,val.campaign_id$export_fields_SQL from vicidial_users vu,vicidial_list vi,".$vicidial_closer_log_table." vl LEFT OUTER JOIN ".$vicidial_agent_log_table." val ON vl.uniqueid=val.uniqueid and vl.lead_id=val.lead_id and vl.user=val.user where ".$date_field." >= '$query_date $query_time' and ".$date_field." <= '$end_date $end_time' and vu.user=vl.user and vi.lead_id=vl.lead_id $list_SQL $group_SQL $user_SQL $user_group_SQL $status_SQL order by ".$date_field." $sort_dir limit 1000000;";
			}
		$rslt=mysql_to_mysqli($stmtA, $link);
		if ($DB) {$DBout .= "$stmtA\n";}
		$inbound_to_print = mysqli_num_rows($rslt);
		if ( ($inbound_to_print < 1) and ($outbound_calls < 1) )
			{
			echo _QXZ("There are no inbound calls during this time period for these parameters")."\n";
			if ($DB) {echo "$stmtA\n";}
			exit;
			}
		else
			{
			$i=0;
			while ($i < $inbound_to_print)
				{
				$row=mysqli_fetch_row($rslt);

				$row[29] = preg_replace("/\n|\r/",'!N',$row[29]);

				$export_status[$k] =		$row[2];
				$export_campaign_id[$k] =	$row[39];
				$export_list_id[$k] =		$row[8];
				$export_lead_id[$k] =		$row[35];
				$export_vicidial_id[$k] =	$row[36];
				$export_entry_list_id[$k] =	$row[37];
				$export_uniqueid[$k] =		$row[38];
				$export_wrapup_time[$k] =		$row[40];
				$export_queue_time[$k] =		$row[32];

				if ($LOGadmin_hide_phone_data != '0')
					{
					if ($DB > 0) {$DBout .= "HIDEPHONEDATA|$row[1]|$LOGadmin_hide_phone_data|\n";}
					$phone_temp = $row[1];
					$phone_lead_temp = $row[11];
					if ( (strlen($phone_temp) > 0) or (strlen($phone_lead_temp) > 0) )
						{
						if ($LOGadmin_hide_phone_data == '4_DIGITS')
							{
							$row[1] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);
							$row[11] = str_repeat("X", (strlen($phone_lead_temp) - 4)) . substr($phone_lead_temp,-4,4);
							}
						elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
							{
							$row[1] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);
							$row[11] = str_repeat("X", (strlen($phone_lead_temp) - 3)) . substr($phone_lead_temp,-3,3);
							}
						elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
							{
							$row[1] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);
							$row[11] = str_repeat("X", (strlen($phone_lead_temp) - 2)) . substr($phone_lead_temp,-2,2);
							}
						else
							{
							$row[1] = preg_replace("/./",'X',$phone_temp);
							$row[11] = preg_replace("/./",'X',$phone_lead_temp);
							}
						}
					}
				if ($LOGadmin_hide_lead_data != '0')
					{
					if ($DB > 0) {$DBout .= "HIDELEADDATA|$row[6]|$row[7]|$row[12]|$row[13]|$row[14]|$row[15]|$row[16]|$row[17]|$row[18]|$row[19]|$row[20]|$row[21]|$row[22]|$row[26]|$row[27]|$row[28]|$LOGadmin_hide_lead_data|\n";}
					if (strlen($row[6]) > 0)
						{$data_temp = $row[6];   $row[6] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[7]) > 0)
						{$data_temp = $row[7];   $row[7] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[12]) > 0)
						{$data_temp = $row[12];   $row[12] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[13]) > 0)
						{$data_temp = $row[13];   $row[13] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[14]) > 0)
						{$data_temp = $row[14];   $row[14] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[15]) > 0)
						{$data_temp = $row[15];   $row[15] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[16]) > 0)
						{$data_temp = $row[16];   $row[16] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[17]) > 0)
						{$data_temp = $row[17];   $row[17] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[18]) > 0)
						{$data_temp = $row[18];   $row[18] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[19]) > 0)
						{$data_temp = $row[19];   $row[19] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[20]) > 0)
						{$data_temp = $row[20];   $row[20] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[21]) > 0)
						{$data_temp = $row[21];   $row[21] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[22]) > 0)
						{$data_temp = $row[22];   $row[22] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[26]) > 0)
						{$data_temp = $row[26];   $row[26] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[27]) > 0)
						{$data_temp = $row[27];   $row[27] = preg_replace("/./",'X',$data_temp);}
					if (strlen($row[28]) > 0)
						{$data_temp = $row[28];   $row[28] = preg_replace("/./",'X',$data_temp);}
					}

				### PARSE TAB CHARACTERS FROM THE DATA ITSELF
				for ($t=0; $t<count($row); $t++){
					$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
				}

				$export_fieldsDATA='';
				if ($export_fields == 'ALTERNATE_1')
					{
					$ALTcall_date = $row[0];
					$LASTcall_date = $row[39];
					$ALTcall_date = preg_replace("/-| |:|\d\d$/",'',$ALTcall_date);
					$LASTcall_date = preg_replace("/-| |:|\d\d$/",'',$LASTcall_date);
					$export_fieldsDATA = "$row[38]|$LASTcall_date|";
					$export_rows[$k] = "$ALTcall_date|$row[1]|$row[2]|$row[5]|$row[6]|$row[7]|$row[13]|$row[15]|$row[30]|$export_fieldsDATA";
					}
				else
					{
					if ($export_fields == 'EXTENDED')
						{$export_fieldsDATA = "$row[40]\t$row[41]\t$row[42]\t$row[43]\t$row[44]\t";}
					if ($export_fields == 'EXTENDED_2')
						{$export_fieldsDATA = "$row[40]\t$row[41]\t$row[42]\t$row[43]\t$row[44]\t$row[45]\t";}
					if ($export_fields == 'EXTENDED_3')
						{$export_fieldsDATA = "$row[40]\t$row[41]\t$row[42]\t$row[43]\t$row[44]\t$row[45]\t";}
					if ($export_fields == 'EXTENDED_4')
						{$export_fieldsDATA = "$row[40]\t$row[41]\t";}
					if ($export_fields == 'ALTERNATE_2')
						{$export_rows[$k] = "$row[18]\t$row[13]\t$row[15]\t$row[11]\t$row[17]\t!STATUS_DESCRIPTION!\t$row[0]";}
					else
						{
						$export_rows[$k] = "$row[0]\t$row[1]\t$row[2]\t$row[3]\t$row[4]\t$row[5]\t$row[6]\t$row[7]\t$row[8]\t$row[9]\t$row[10]\t$row[11]\t$row[12]\t$row[13]\t$row[14]\t$row[15]\t$row[16]\t$row[17]\t$row[18]\t$row[19]\t$row[20]\t$row[21]\t$row[22]\t$row[23]\t$row[24]\t$row[25]\t$row[26]\t$row[27]\t$row[28]\t$row[29]\t$row[30]\t$row[31]\t$row[32]\t$row[33]\t$row[34]\t$row[35]\t$export_fieldsDATA";
						}
					}
				$i++;
				$k++;
				}
			}
		}


	if ( ($outbound_to_print > 0) or ($inbound_to_print > 0) )
		{
		$TXTfilename = "EXPORT_CALL_REPORT_$FILE_TIME.txt";

		// We'll be outputting a TXT file
		header('Content-type: application/octet-stream');

		// It will be called LIST_101_20090209-121212.txt
		header("Content-Disposition: attachment; filename=\"$TXTfilename\"");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		ob_clean();
		flush();

		if (file_exists('options.php'))
			{
			require('options.php');
			}

		if ($header_row=='YES')
			{
			$RFheader = '';
			$NFheader = '';
			$CFheader = '';
			$IVRheader = '';
			$EXheader = '';
			if ($rec_fields=='ID')
				{$RFheader = "\trecording_id";}
			if ($rec_fields=='FILENAME')
				{$RFheader = "\trecording_filename";}
			if ($rec_fields=='LOCATION')
				{$RFheader = "\trecording_location";}
			if ($rec_fields=='ALL')
				{$RFheader = "\trecording_id\trecording_filename\trecording_location";}
			if ( ($export_fields=='EXTENDED') or ($export_fields=='EXTENDED_2') )
				{$EXheader = "\twrapup_time\tqueue_time\tuniqueid\tcaller_code\tserver_ip\thangup_cause\tdialstatus\tchannel\tdial_time\tanswered_time\tcpd_result\tdid_pattern\tdid_id\tdid_description";}
			if ($export_fields=='EXTENDED_3')
				{$EXheader = "\twrapup_time\tqueue_time\tuniqueid\tcaller_code\tserver_ip\thangup_cause\tdialstatus\tchannel\tdial_time\tanswered_time\tcpd_result\tdid_pattern\tdid_id\tdid_description\tdid_custom_one\tdid_custom_two\tdid_custom_three\tdid_custom_four\tdid_custom_five\tdid_carrier_description";}
			if ($export_fields=='EXTENDED_4')
				{$EXheader = "\twrapup_time\tqueue_time\tuniqueid\tcaller_code\tserver_ip\thangup_cause\tdialstatus\tchannel\tdial_time\tanswered_time\tcpd_result\tdid_pattern\tdid_id\tdid_description";}
			if ($export_fields == 'ALTERNATE_1')
				{$EXheader = "|caller_code";}
			if ($call_notes=='YES')
				{$NFheader = "\tcall_notes";}
			if ($ivr_export=='YES')
				{
				$IVRheader = "\tivr_path";
				if ($export_fields == 'ALTERNATE_1')
					{$IVRheader = "|ivr_path";}
				}
			if ( ($custom_fields_enabled > 0) and ($custom_fields=='YES') )
				{$CFheader = "\tcustom_fields";}

			if ($export_fields == 'ALTERNATE_1')
				{
				echo "call_date|phone_number_dialed|status|campaign_id|vendor_lead_code|source_id|first_name|last_name|length_in_sec$EFheader$RFheader$EXheader$NFheader$IVRheader$CFheader\r\n";
				}
			else if ($export_fields == 'ALTERNATE_2')
				{
				echo $call_export_report_ALTERNATE_2_header;
				}
			else
				{
				echo "call_date\tphone_number_dialed\tstatus\tuser\tfull_name\tcampaign_id\tvendor_lead_code\tsource_id\tlist_id\tgmt_offset_now\tphone_code\tphone_number\ttitle\tfirst_name\tmiddle_initial\tlast_name\taddress1\taddress2\taddress3\tcity\tstate\tprovince\tpostal_code\tcountry_code\tgender\tdate_of_birth\talt_phone\temail\tsecurity_phrase\tcomments\tlength_in_sec\tuser_group\talt_dial\trank\towner\tlead_id$EFheader\tlist_name\tlist_description\tstatus_name$RFheader$EXheader$NFheader$IVRheader$CFheader\r\n";
				}
			}

		$i=0;
		while ($k > $i)
			{
			$custom_data='';
			$ex_list_name='';
			$ex_list_description='';
			$stmt = "SELECT list_name,list_description FROM vicidial_lists where list_id='$export_list_id[$i]';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$ex_list_ct = mysqli_num_rows($rslt);
			if ($ex_list_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$ex_list_name =			$row[0];
				$ex_list_description =	$row[1];
				}

			$ex_status_name='';
			$stmt = "SELECT status_name FROM vicidial_statuses where status='$export_status[$i]';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$ex_list_ct = mysqli_num_rows($rslt);
			if ($ex_list_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$ex_status_name =			$row[0];
				}
			else
				{
				$stmt = "SELECT status_name, if(campaign_id='$export_campaign_id[$i]', 0, 1) as priority FROM vicidial_campaign_statuses where status='$export_status[$i]' order by priority asc;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$ex_list_ct = mysqli_num_rows($rslt);
				if ($ex_list_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$ex_status_name =			$row[0];
					}
				}

			$rec_data='';
			if ( ($rec_fields=='ID') or ($rec_fields=='FILENAME') or ($rec_fields=='LOCATION') or ($rec_fields=='ALL') )
				{
				$rec_id='';
				$rec_filename='';
				$rec_location='';
				$stmt = "SELECT recording_id,filename,location from ".$recording_log_table." where vicidial_id='$export_vicidial_id[$i]' order by recording_id desc LIMIT 10;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$recordings_ct = mysqli_num_rows($rslt);
				$u=0;
				while ($recordings_ct > $u)
					{
					$row=mysqli_fetch_row($rslt);

					### PARSE TAB CHARACTERS FROM THE DATA ITSELF
					for ($t=0; $t<count($row); $t++){
						$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
					}

					$rec_id .=			"$row[0]|";
					$rec_filename .=	"$row[1]|";
					$rec_location .=	"$row[2]|";

					$u++;
					}
				if ($recordings_ct < 1)
					{
					if (preg_match("/_archive/",$recording_log_table))
						{$TEMPrecording_log_table = 'recording_log';}
					else
						{$TEMPrecording_log_table = 'recording_log_archive';}
					$stmt = "SELECT recording_id,filename,location from ".$TEMPrecording_log_table." where vicidial_id='$export_vicidial_id[$i]' order by recording_id desc LIMIT 10;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "CHECKING FOR RECORDINGS IN OTHER TABLE:$stmt\n";}
					$recordings_ct = mysqli_num_rows($rslt);
					$u=0;
					while ($recordings_ct > $u)
						{
						$row=mysqli_fetch_row($rslt);

						### PARSE TAB CHARACTERS FROM THE DATA ITSELF
						for ($t=0; $t<count($row); $t++){
							$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
						}

						$rec_id .=			"$row[0]|";
						$rec_filename .=	"$row[1]|";
						$rec_location .=	"$row[2]|";
						$u++;
						}
					}
				$rec_id = preg_replace("/.$/",'',$rec_id);
				$rec_filename = preg_replace("/.$/",'',$rec_filename);
				$rec_location = preg_replace("/.$/",'',$rec_location);
				if (isset($server_recording_links))
					{
					foreach ($server_recording_links as $server_ip => $recording_ip)
						{$rec_location = str_replace($server_ip,$recording_ip,$rec_location);}
					}
				if ($rec_fields=='ID')
					{$rec_data = "\t$rec_id";}
				if ($rec_fields=='FILENAME')
					{$rec_data = "\t$rec_filename";}
				if ($rec_fields=='LOCATION')
					{$rec_data = "\t$rec_location";}
				if ($rec_fields=='ALL')
					{$rec_data = "\t$rec_id\t$rec_filename\t$rec_location";}
				}

			$extended_data_a='';
			$extended_data_b='';
			$extended_data_c='';
			$extended_data_d='';
			$extended_data_e='';
			if ($export_fields=='ALTERNATE_1')
				{
				$extended_data = '';
				if (strlen($export_uniqueid[$i]) > 0)
					{
					$uniqueidTEST = $export_uniqueid[$i];
					$uniqueidTEST = preg_replace('/\..*$/','',$uniqueidTEST);
					$stmt = "SELECT caller_code,server_ip from ".$vicidial_log_extended_table." where uniqueid LIKE \"$uniqueidTEST%\" and lead_id='$export_lead_id[$i]' LIMIT 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$vle_ct = mysqli_num_rows($rslt);
					if ($vle_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);

						### PARSE TAB CHARACTERS FROM THE DATA ITSELF
						$row[0]=preg_replace('/\t/', ' -- ', $row[0]);

						$extended_data_a =	"$row[0]";
						$export_call_id[$i] = $row[0];
						}
					}
				if (strlen($extended_data_a)<1)
					{$extended_data_a =	"";}
				$extended_data .= "$extended_data_a";
				}
			if ( ($export_fields=='EXTENDED') or ($export_fields=='EXTENDED_2') or ($export_fields=='EXTENDED_3') or ($export_fields=='EXTENDED_4') )
				{
				$extended_data = "\t$export_wrapup_time[$i]\t$export_queue_time[$i]\t$export_uniqueid[$i]";
				if (strlen($export_uniqueid[$i]) > 0)
					{
					$uniqueidTEST = $export_uniqueid[$i];
					$uniqueidTEST = preg_replace('/\..*$/','',$uniqueidTEST);
					$stmt = "SELECT caller_code,server_ip from ".$vicidial_log_extended_table." where uniqueid LIKE \"$uniqueidTEST%\" and lead_id='$export_lead_id[$i]' LIMIT 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$vle_ct = mysqli_num_rows($rslt);
					if ($vle_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);

						### PARSE TAB CHARACTERS FROM THE DATA ITSELF
						for ($t=0; $t<count($row); $t++){
							$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
						}

						$extended_data_a =	"\t$row[0]\t$row[1]";
						$export_call_id[$i] = $row[0];
						}

					$stmt = "SELECT hangup_cause,dialstatus,channel,dial_time,answered_time from ".$vicidial_carrier_log_table." where uniqueid LIKE \"$uniqueidTEST%\" and lead_id='$export_lead_id[$i]' LIMIT 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$vcarl_ct = mysqli_num_rows($rslt);
					if ($vcarl_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);

						### PARSE TAB CHARACTERS FROM THE DATA ITSELF
						for ($t=0; $t<count($row); $t++){
							$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
						}

						$extended_data_b =	"\t$row[0]\t$row[1]\t$row[2]\t$row[3]\t$row[4]";
						}

					$stmt = "SELECT result from ".$vicidial_cpd_log_table." where callerid='$export_call_id[$i]' LIMIT 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$vcpdl_ct = mysqli_num_rows($rslt);
					if ($vcpdl_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						### PARSE TAB CHARACTERS FROM THE DATA ITSELF
						$row[0]=preg_replace('/\t/', ' -- ', $row[0]);
						$extended_data_c =	"\t$row[0]";
						}

					$stmt = "SELECT extension,did_id from ".$vicidial_did_log_table." where uniqueid='$export_uniqueid[$i]' LIMIT 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$vcdid_ct = mysqli_num_rows($rslt);
					if ($vcdid_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);

						### PARSE TAB CHARACTERS FROM THE DATA ITSELF
						for ($t=0; $t<count($row); $t++)
							{
							$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
							}

						$extended_data_d =	"\t$row[0]\t$row[1]";

						$stmt = "SELECT did_description,custom_one,custom_two,custom_three,custom_four,custom_five,did_carrier_description from vicidial_inbound_dids where did_id='$row[1]' LIMIT 1;";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$vcdidx_ct = mysqli_num_rows($rslt);
						if ($vcdidx_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							### PARSE TAB CHARACTERS FROM THE DATA ITSELF
							$row[0]=preg_replace('/\t/', ' -- ', $row[0]);
							$extended_data_d .=	"\t$row[0]";
							$extended_data_e .=	"\t$row[1]\t$row[2]\t$row[3]\t$row[4]\t$row[5]\t$row[6]";
							}
						else
							{
							$extended_data_d .= "\t";
							$extended_data_e .= "\t\t\t\t\t\t";
							}
						}

					}
				if (strlen($extended_data_a)<1)
					{$extended_data_a =	"\t\t";}
				if (strlen($extended_data_b)<1)
					{$extended_data_b =	"\t\t\t\t\t";}
				if (strlen($extended_data_c)<1)
					{$extended_data_c =	"\t";}
				if (strlen($extended_data_d)<1)
					{$extended_data_d =	"\t\t\t";}
				if (strlen($extended_data_e)<1)
					{$extended_data_e =	"\t\t\t\t\t\t";}
				if ($export_fields!='EXTENDED_3')
					{$extended_data_e='';}
				$extended_data .= "$extended_data_a$extended_data_b$extended_data_c$extended_data_d$extended_data_e";
				}

			$notes_data='';
			if ($call_notes=='YES')
				{
				if (strlen($export_vicidial_id[$i]) > 0)
					{
					$stmt = "SELECT call_notes from vicidial_call_notes where vicidial_id IN('$export_vicidial_id[$i]','$export_uniqueid[$i]') order by notesid desc LIMIT 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$notes_ct = mysqli_num_rows($rslt);
					if ($notes_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);

						### PARSE TAB CHARACTERS FROM THE DATA ITSELF
						$row[0]=preg_replace('/\t/', ' -- ', $row[0]);

						$notes_data =	$row[0];
						}
					$notes_data = preg_replace("/\r\n/",' ',$notes_data);
					$notes_data = preg_replace("/\n/",' ',$notes_data);
					}
				$notes_data =	"\t$notes_data";
				}

			$ivr_data='';
			if ($ivr_export=='YES')
				{
				$ivr_path='';
				if (strlen($export_uniqueid[$i]) > 0)
					{
					$IVRdelimiter='|';
					if ($export_fields=='ALTERNATE_1')
						{$IVRdelimiter='^';}
					$stmt="select menu_id,UNIX_TIMESTAMP(event_date) from ".$vicidial_outbound_ivr_log_table." where event_date >= '$query_date $query_time' and event_date <= '$end_date $end_time' and uniqueid='$export_uniqueid[$i]' order by event_date,menu_action desc;";
					$rslt=mysql_to_mysqli($stmt, $link);
				#	$ivr_path = "$stmt|$export_uniqueid[$i]|";
					if ($DB) {$MAIN.="$stmt\n";}
					$logs_to_print = mysqli_num_rows($rslt);
					$u=0;
					while ($u < $logs_to_print)
						{
						$row=mysqli_fetch_row($rslt);

						### PARSE TAB CHARACTERS FROM THE DATA ITSELF
						$row[0]=preg_replace('/\t/', ' -- ', $row[0]);

						$ivr_path .= "$row[0]$IVRdelimiter";
						$u++;
						}
					$ivr_path = preg_replace("/\|$|\^$/",'',$ivr_path);
					}
				$ivr_data =	"\t$ivr_path";
				if ($export_fields=='ALTERNATE_1')
					{$ivr_data =	"|$ivr_path";}
				}

			if ( ($custom_fields_enabled > 0) and ($custom_fields=='YES') )
				{
				$CF_list_id = $export_list_id[$i];
				if ($export_entry_list_id[$i] > 99)
					{$CF_list_id = $export_entry_list_id[$i];}
				$stmt="SHOW TABLES LIKE \"custom_$CF_list_id\";";
				if ($DB>0) {echo "$stmt";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$tablecount_to_print = mysqli_num_rows($rslt);
				if ($tablecount_to_print > 0)
					{
					$column_list='';
					$encrypt_list='';
					$hide_list='';
					$stmt = "DESCRIBE custom_$CF_list_id;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$columns_ct = mysqli_num_rows($rslt);
					$u=0;
					while ($columns_ct > $u)
						{
						$row=mysqli_fetch_row($rslt);
						$column =	$row[0];
						$column_list .= "$row[0],";
						$u++;
						}
					if ($columns_ct > 1)
						{
						$column_list = preg_replace("/lead_id,/",'',$column_list);
						$column_list = preg_replace("/,$/",'',$column_list);
						$column_list_array = explode(',',$column_list);
						if (preg_match("/cf_encrypt/",$active_modules))
							{
							$enc_fields=0;
							$stmt = "SELECT count(*) from vicidial_lists_fields where field_encrypt='Y' and list_id='$CF_list_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "$stmt\n";}
							$enc_field_ct = mysqli_num_rows($rslt);
							if ($enc_field_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$enc_fields =	$row[0];
								}
							if ($enc_fields > 0)
								{
								$stmt = "SELECT field_label from vicidial_lists_fields where field_encrypt='Y' and list_id='$CF_list_id';";
								$rslt=mysql_to_mysqli($stmt, $link);
								if ($DB) {echo "$stmt\n";}
								$enc_field_ct = mysqli_num_rows($rslt);
								$r=0;
								while ($enc_field_ct > $r)
									{
									$row=mysqli_fetch_row($rslt);
									$encrypt_list .= "$row[0],";
									$r++;
									}
								$encrypt_list = ",$encrypt_list";
								}
							if ($LOGadmin_cf_show_hidden < 1)
								{
								$hide_fields=0;
								$stmt = "SELECT count(*) from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$CF_list_id';";
								$rslt=mysql_to_mysqli($stmt, $link);
								if ($DB) {echo "$stmt\n";}
								$hide_field_ct = mysqli_num_rows($rslt);
								if ($hide_field_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$hide_fields =	$row[0];
									}
								if ($hide_fields > 0)
									{
									$stmt = "SELECT field_label from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$CF_list_id';";
									$rslt=mysql_to_mysqli($stmt, $link);
									if ($DB) {echo "$stmt\n";}
									$hide_field_ct = mysqli_num_rows($rslt);
									$r=0;
									while ($hide_field_ct > $r)
										{
										$row=mysqli_fetch_row($rslt);
										$hide_list .= "$row[0],";
										$r++;
										}
									$hide_list = ",$hide_list";
									}
								}
							}
						$stmt = "SELECT $column_list from custom_$CF_list_id where lead_id='$export_lead_id[$i]' limit 1;";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$customfield_ct = mysqli_num_rows($rslt);
						if ($customfield_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$t=0;
							while ($columns_ct >= $t)
								{
								if ($enc_fields > 0)
									{
									$field_enc='';   $field_enc_all='';
									if ($DB) {echo "|$column_list|$encrypt_list|\n";}
									if ( (preg_match("/,$column_list_array[$t],/",$encrypt_list)) and (strlen($row[$t]) > 0) )
										{
										exec("../agc/aes.pl --decrypt --text=$row[$t]", $field_enc);
										$field_enc_ct = count($field_enc);
										$k=0;
										while ($field_enc_ct > $k)
											{
											$field_enc_all .= $field_enc[$k];
											$k++;
											}
										$field_enc_all = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
										$row[$t] = base64_decode($field_enc_all);
										}
									}
								if ( (preg_match("/,$column_list_array[$t],/",$hide_list)) and (strlen($row[$t]) > 0) )
									{
									$field_temp_val = $row[$t];
									$row[$t] = preg_replace("/./",'X',$field_temp_val);
									}
								### PARSE TAB CHARACTERS FROM THE DATA ITSELF
								$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
								$custom_data .= "\t$row[$t]";
								$t++;
								}
							}
						}
					$custom_data = preg_replace("/\r\n/",'!N',$custom_data);
					$custom_data = preg_replace("/\n/",'!N',$custom_data);
					}
				}

			if ($export_fields=='ALTERNATE_1')
				{
				echo "$export_rows[$i]$rec_data$extended_data$notes_data$ivr_data$custom_data\r\n";
				}
			else if ($export_fields=='ALTERNATE_2')
				{
				$export_rows[$i]=preg_replace('/!STATUS_DESCRIPTION!/', "$ex_status_name", $export_rows[$i]);
				echo "$export_rows[$i]\r\n";
				}
			else
				{
				echo "$export_rows[$i]$ex_list_name\t$ex_list_description\t$ex_status_name$rec_data$extended_data$notes_data$ivr_data$custom_data\r\n";
				}
			$i++;
			}
		$file_exported++;
		}
	else
		{
		echo _QXZ("There are no calls during this time period for these parameters")."\n";
		exit;
		}
	echo "$DBout";
	}
##### END RUN THE EXPORT AND OUTPUT FLAT DATA FILE #####


else
	{
	echo "$DBout";
	if ($date_field=="entry_date") {
		$date_field = "vi.entry_date";
		# Since entry_date only appears when the EXTENDED export option is selected, EXTENDED will automatically be used when entry_date is selected as the date field.
		if (!preg_match("/EXTENDED/", $export_fields)) {$export_fields = "EXTENDED";}
	} else {
		$date_field="vl.call_date";
	}

	$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$campaigns_to_print = mysqli_num_rows($rslt);
	$i=0;
		$LISTcampaigns[$i]='---NONE---';
		$i++;
		$campaigns_to_print++;
		$LISTcampaigns[$i]='---ALL---';
		$i++;
		$campaigns_to_print++;
	while ($i < $campaigns_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTcampaigns[$i] =$row[0];
		$i++;
		}

	$stmt="select group_id from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$groups_to_print = mysqli_num_rows($rslt);
	$i=0;
		$LISTgroups[$i]='---NONE---';
		$i++;
		$groups_to_print++;
		$LISTgroups[$i]='---ALL---';
		$i++;
		$groups_to_print++;
	while ($i < $groups_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTgroups[$i] =$row[0];
		$i++;
		}

	$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
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

	$stmt="select list_id from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$lists_to_print = mysqli_num_rows($rslt);
	$i=0;
		$LISTlists[$i]='---ALL---';
		$i++;
		$lists_to_print++;
	while ($i < $lists_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTlists[$i] =$row[0];
		$i++;
		}

	$stmt="select status from vicidial_statuses order by status;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$statuses_to_print = mysqli_num_rows($rslt);
	$i=0;
		$LISTstatus[$i]='---ALL---';
		$i++;
		$statuses_to_print++;
	while ($i < $statuses_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTstatus[$i] =$row[0];
		$i++;
		}

	$stmt="select distinct status from vicidial_campaign_statuses $whereLOGallowed_campaignsSQL order by status;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$Cstatuses_to_print = mysqli_num_rows($rslt);
	$j=0;
	while ($j < $Cstatuses_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTstatus[$i] =$row[0];
		$i++;
		$j++;
		}
	$statuses_to_print = ($statuses_to_print + $Cstatuses_to_print);

	echo "<HTML><HEAD>\n";

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<style type='text/css'>\n";
	echo "label {\n";
	echo "    display:flex;\n";
	echo "    align-items: baseline;\n";
	echo "}\n";
	echo "\n";
	echo "input[type=checkbox] {\n";
	echo "    margin-right: 8px;\n";
	echo "}\n";
	echo "</style>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("ADMINISTRATION").": "._QXZ("$report_name");
	if ($ivr_export == 'YES')
		{echo " "._QXZ("IVR");}

	##### BEGIN Set variables to make header show properly #####
	$ADD =					'100';
	$hh =					'lists';
	$LOGast_admin_access =	'1';
	$SSoutbound_autodial_active = '1';
	$ADMIN =				'admin.php';
	$page_width='770';
	$section_width='750';
	$header_font_size='3';
	$subheader_font_size='2';
	$subcamp_font_size='2';
	$header_selected_bold='<b>';
	$header_nonselected_bold='';
	$lists_color =		'#FFFF99';
	$lists_font =		'BLACK';
	$lists_color =		'#E6E6E6';
	$subcamp_color =	'#C6C6C6';
	##### END Set variables to make header show properly #####
	$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
	$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

	require("admin_header.php");


	echo "<CENTER><BR>\n";
	echo "<FONT SIZE=3 FACE=\"Arial,Helvetica\"><B>"._QXZ("Export Calls Report");
	if ($ivr_export == 'YES')
		{echo " "._QXZ("IVR");}
	echo "</B></FONT> $NWB#call_export_report$NWE<BR><BR>\n";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
	echo "<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">";
	echo "<INPUT TYPE=HIDDEN NAME=run_export VALUE=\"1\">";
	echo "<INPUT TYPE=HIDDEN NAME=ivr_export VALUE=\"$ivr_export\">";
	echo "<TABLE BORDER=0 CELLSPACING=8><TR><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";

	echo "<font class=\"select_bold\"><B>"._QXZ("Date Range").":</B></font><BR>\n"; #<CENTER>
	echo "<INPUT TYPE=TEXT NAME=query_date SIZE=8 MAXLENGTH=10 VALUE=\"$query_date\">";

	?>
	<script language="JavaScript">
	var o_cal = new tcal ({
		// form name
		'formname': 'vicidial_report',
		// input name
		'controlname': 'query_date'
	});
	o_cal.a_tpl.yearscroll = false;
	// o_cal.a_tpl.weekstart = 1; // Monday week start
	</script>
	<?php
	echo "<BR>\n";
	echo "<select name='query_hour' id='query_hour'>\n";
	for ($i=0; $i<24; $i++)
		{
		$hr=substr("0$i", -2);
		if($query_hour==$hr) {$s="selected";} else {$s="";}
		echo "<option value='$hr' $s>$hr</option>\n";
		}
	echo "</select><B>:00:00</B>\n";

	echo "<BR><CENTER><B>"._QXZ("to")."</B></CENTER>\n";
	echo "<INPUT TYPE=TEXT NAME=end_date SIZE=8 MAXLENGTH=10 VALUE=\"$end_date\">";

	?>
	<script language="JavaScript">
	var o_cal = new tcal ({
		// form name
		'formname': 'vicidial_report',
		// input name
		'controlname': 'end_date'
	});
	o_cal.a_tpl.yearscroll = false;
	// o_cal.a_tpl.weekstart = 1; // Monday week start
	</script>
	<?php
	echo "<BR>\n";
	echo "<select name='end_hour' id='end_hour'>\n";
	for ($i=0; $i<24; $i++)
		{
		$hr=substr("0$i", -2);
		if($end_hour==$hr) {$s="selected";} else {$s="";}
		echo "<option value='$hr' $s>$hr</option>\n";
		}
	echo "</select><B>:59:59</B>\n";

	echo "<BR><BR>\n";

	echo "<B>"._QXZ("User").":</B><BR>\n";
	echo "<input type='text' name='user' id='user' size='7' maxlength='20'> $NWB#call_export_report-user$NWE\n";

	echo "<BR><BR>\n";

	echo "<B>"._QXZ("Date Field").":</B><BR>\n";
	echo "<select size=1 name=date_field><option selected value=\"call_date\">"._QXZ("Call date")."</option><option value=\"entry_date\">"._QXZ("Entry date")."</option></select> $NWB#call_export_report-date_field$NWE\n";

	echo "<BR><BR>\n";

	echo "<B>"._QXZ("Sort order").":</B> $NWB#call_export_report-sort_dir$NWE\n<BR>\n";
	echo "<input type=radio value='asc' name='sort_dir' $asc>"._QXZ("Ascending")."<BR>\n";
	echo "<input type=radio value='desc' name='sort_dir' $desc>"._QXZ("Descending")."<BR>\n";
	
	echo "<BR><BR>\n";

	echo "<B>"._QXZ("Header Row").":</B><BR>\n";
	echo "<select size=1 name=header_row><option selected value=\"YES\">"._QXZ("YES")."</option><option value=\"NO\">"._QXZ("NO")."</option></select> $NWB#call_export_report-header_row$NWE\n";

	echo "<BR><BR>\n";

	echo "<B>"._QXZ("Rec Fields").":</B><BR>\n";
	echo "<select size=1 name=rec_fields>";
	echo "<option value=\"ID\">"._QXZ("ID")."</option>";
	echo "<option value=\"FILENAME\">"._QXZ("FILENAME")."</option>";
	echo "<option value=\"LOCATION\">"._QXZ("LOCATION")."</option>";
	echo "<option value=\"ALL\">"._QXZ("ALL")."</option>";
	echo "<option selected value=\"NONE\">"._QXZ("NONE")."</option>";
	echo "</select> $NWB#call_export_report-rec_fields$NWE\n";

	if ($custom_fields_enabled > 0)
		{
		echo "<BR><BR>\n";

		echo "<B>"._QXZ("Custom Fields").":</B><BR>\n";
		echo "<select size=1 name=custom_fields><option value=\"YES\">"._QXZ("YES")."</option><option selected value=\"NO\">"._QXZ("NO")."</option></select> $NWB#call_export_report-custom_fields$NWE\n";
		}

	echo "<BR><BR>\n";

	echo "<B>"._QXZ("Per Call Notes").":</B><BR>\n";
	echo "<select size=1 name=call_notes><option value=\"YES\">"._QXZ("YES")."</option><option selected value=\"NO\">"._QXZ("NO")."</option></select> $NWB#call_export_report-call_notes$NWE\n";

	echo "<BR><BR>\n";

	echo "<B>"._QXZ("Export Type").":</B>$NWB#call_export_report-export_type$NWE<BR>\n";
	echo "<select size=1 name=export_fields><option selected value=\"STANDARD\">"._QXZ("STANDARD")."</option><option value=\"EXTENDED\">"._QXZ("EXTENDED")."</option><option value=\"EXTENDED_2\">"._QXZ("EXTENDED_2")."</option><option value=\"EXTENDED_3\">"._QXZ("EXTENDED_3")."</option><option value=\"EXTENDED_4\">"._QXZ("EXTENDED_4")."</option><option value=\"ALTERNATE_1\">ALTERNATE_1</option><option value=\"ALTERNATE_2\">ALTERNATE_2</option></select>\n";


	if ($archives_available=="Y")
	{
	echo "<BR><BR><label><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data><B>"._QXZ("Search archived data")."</B> $NWB#call_export_report-search_archived_data$NWE</label><BR>\n";
	}

	### bottom of first column

	echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=2>\n";
	echo "<font class=\"select_bold\"><B>"._QXZ("Campaigns").":</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=20 NAME=campaign[] multiple>\n";
		$o=0;
		while ($campaigns_to_print > $o)
		{
			if (preg_match("/\|$LISTcampaigns[$o]\|/",$campaign_string))
				{echo "<option selected value=\"$LISTcampaigns[$o]\">"._QXZ("$LISTcampaigns[$o]")."</option>\n";}
			else
				{echo "<option value=\"$LISTcampaigns[$o]\">"._QXZ("$LISTcampaigns[$o]")."</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";

	if ($ivr_export != 'YES')
		{
		echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
		echo "<font class=\"select_bold\"><B>"._QXZ("Inbound Groups").":</B></font><BR><CENTER>\n";
		echo "<SELECT SIZE=20 NAME=group[] multiple>\n";
			$o=0;
			while ($groups_to_print > $o)
			{
				if (preg_match("/\|$LISTgroups[$o]\|/",$group_string))
					{echo "<option selected value=\"$LISTgroups[$o]\">"._QXZ("$LISTgroups[$o]")."</option>\n";}
				else
					{echo "<option value=\"$LISTgroups[$o]\">"._QXZ("$LISTgroups[$o]")."</option>\n";}
				$o++;
			}
		echo "</SELECT>\n";
		}
	echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
	echo "<font class=\"select_bold\"><B>"._QXZ("Lists").":</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=20 NAME=list_id[] multiple>\n";
		$o=0;
		while ($lists_to_print > $o)
		{
			if (preg_match("/\|$LISTlists[$o]\|/",$list_string))
				{echo "<option selected value=\"$LISTlists[$o]\">"._QXZ("$LISTlists[$o]")."</option>\n";}
			else
				{echo "<option value=\"$LISTlists[$o]\">"._QXZ("$LISTlists[$o]")."</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";
	echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
	echo "<font class=\"select_bold\"><B>"._QXZ("Statuses").":</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=20 NAME=status[] multiple>\n";
		$o=0;
		while ($statuses_to_print > $o)
		{
			if (preg_match("/\|$LISTstatus[$o]\|/",$status_string)) 
				{echo "<option selected value=\"$LISTstatus[$o]\">"._QXZ("$LISTstatus[$o]")."</option>\n";}
			else
				{echo "<option value=\"$LISTstatus[$o]\">"._QXZ("$LISTstatus[$o]")."</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";
	if ($ivr_export != 'YES')
		{
		echo "</TD><TD ALIGN=LEFT VALIGN=TOP ROWSPAN=3>\n";
		echo "<font class=\"select_bold\"><B>"._QXZ("User Groups").":</B></font><BR><CENTER>\n";
		echo "<SELECT SIZE=20 NAME=user_group[] multiple>\n";
			$o=0;
			while ($user_groups_to_print > $o)
			{
				if (preg_match("/\|$LISTuser_groups[$o]\|/",$user_group_string))
					{echo "<option selected value=\"$LISTuser_groups[$o]\">"._QXZ("$LISTuser_groups[$o]")."</option>\n";}
				else
					{echo "<option value=\"$LISTuser_groups[$o]\">"._QXZ("$LISTuser_groups[$o]")."</option>\n";}
				$o++;
			}
		echo "</SELECT>\n";
		}
	echo "</TD></TR><TR></TD><TD ALIGN=LEFT VALIGN=TOP COLSPAN=2> &nbsp; \n";

	echo "</TD></TR><TR></TD><TD ALIGN=CENTER VALIGN=TOP COLSPAN=5>\n";
	echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
	echo "</TD></TR></TABLE>\n";
	echo "</FORM>\n\n";

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

if ($file_exported > 0)
	{
	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$stmt|$stmtA|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LEADS', event_type='EXPORT', record_id='', event_code='ADMIN EXPORT CALLS REPORT', event_sql=\"$SQL_log\", event_notes='';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	}

exit;

?>

