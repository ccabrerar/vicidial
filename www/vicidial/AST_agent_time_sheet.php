<?php 
# AST_agent_time_sheet.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60619-1729 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 80624-0132 - Added vicidial_timeclock entries
# 90310-0745 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 90524-2231 - Changed to use functions.php for seconds to HH:MM:SS conversion
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110703-1848 - Added download option
# 111104-1308 - Added user_group restrictions for selecting in-groups
# 130414-0148 - Added report logging
# 130610-1028 - Finalized changing of all ereg instances to preg
# 130621-0818 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0745 - Changed to mysqli PHP functions
# 140108-0718 - Added webserver and hostname to report logging
# 141114-0908 - Finalized adding QXZ translation to all admin files
# 141230-1523 - Added code for on-the-fly language translations display
# 151227-1735 - Added option for searching archived data
# 160325-1428 - Changes for sidebar update
# 170409-1539 - Added IP List validation code
# 170711-1102 - Added screen colors and fixed default date variable
# 220122-1700 - Added more variable filtering
# 220221-0938 - Added allow_web_debug system setting
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name = 'User Time Sheet';
$db_source = 'M';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["agent"]))				{$agent=$_GET["agent"];}
	elseif (isset($_POST["agent"]))		{$agent=$_POST["agent"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["calls_summary"]))			{$calls_summary=$_GET["calls_summary"];}
	elseif (isset($_POST["calls_summary"]))	{$calls_summary=$_POST["calls_summary"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))		{$file_download=$_POST["file_download"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if ( (!isset($query_date)) or (strlen($query_date) < 8) ) {$query_date = $NOW_DATE;}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,user_territories_active,enable_languages,language_method,admin_screen_colors,report_default_format,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSoutbound_autodial_active =	$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$user_territories_active =		$row[4];
	$SSenable_languages =			$row[5];
	$SSlanguage_method =			$row[6];
	$SSadmin_screen_colors =		$row[7];
	$SSreport_default_format =		$row[8];
	$SSallow_web_debug =			$row[9];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_timeclock_log", "vicidial_agent_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_timeclock_log_table=use_archive_table("vicidial_timeclock_log");
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
	$vicidial_timeclock_log_table="vicidial_timeclock_log";
	$vicidial_agent_log_table="vicidial_agent_log";
	}
#############

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$query_date);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$calls_summary = preg_replace('/[^-_0-9a-zA-Z]/', '', $calls_summary);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$agent = preg_replace('/[^-_0-9a-zA-Z]/','',$agent);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$agent = preg_replace('/[^-_0-9\p{L}]/u','',$agent);
	}


$user=$agent;

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$user, $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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
$vuLOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vuLOGadmin_viewable_groupsSQL = "and vicidial_users.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
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


$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='B9CBFD';
$SSstd_row3_background='8EBCFD';
$SSstd_row4_background='B6D3FC';
$SSstd_row5_background='A3C3D6';
$SSalt_row1_background='BDFFBD';
$SSalt_row2_background='99FF99';
$SSalt_row3_background='CCFFCC';

if ($SSadmin_screen_colors != 'default')
	{
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$colors_ct = mysqli_num_rows($rslt);
	if ($colors_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$SSmenu_background =		$row[0];
		$SSframe_background =		$row[1];
		$SSstd_row1_background =	$row[2];
		$SSstd_row2_background =	$row[3];
		$SSstd_row3_background =	$row[4];
		$SSstd_row4_background =	$row[5];
		$SSstd_row5_background =	$row[6];
		$SSalt_row1_background =	$row[7];
		$SSalt_row2_background =	$row[8];
		$SSalt_row3_background =	$row[9];
		$SSweb_logo =				$row[10];
		}
	}
$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;

$selected_logo = "./images/vicidial_admin_web_logo.png";
$selected_small_logo = "./images/vicidial_admin_web_logo.png";
$logo_new=0;
$logo_old=0;
$logo_small_old=0;
if (file_exists('./images/vicidial_admin_web_logo.png')) {$logo_new++;}
if (file_exists('vicidial_admin_web_logo_small.gif')) {$logo_small_old++;}
if (file_exists('vicidial_admin_web_logo.gif')) {$logo_old++;}
if ($SSweb_logo=='default_new')
	{
	$selected_logo = "./images/vicidial_admin_web_logo.png";
	$selected_small_logo = "./images/vicidial_admin_web_logo.png";
	}
if ( ($SSweb_logo=='default_old') and ($logo_old > 0) )
	{
	$selected_logo = "./vicidial_admin_web_logo.gif";
	$selected_small_logo = "./vicidial_admin_web_logo_small.gif";
	}
if ( ($SSweb_logo!='default_new') and ($SSweb_logo!='default_old') )
	{
	if (file_exists("./images/vicidial_admin_web_logo$SSweb_logo")) 
		{
		$selected_logo = "./images/vicidial_admin_web_logo$SSweb_logo";
		$selected_small_logo = "./images/vicidial_admin_web_logo$SSweb_logo";
		}
	}


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

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name");


##### BEGIN Set variables to make header show properly #####
$ADD =					'3';
$hh =					'users';
$sh =					'sheet';
$LOGast_admin_access =	'1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$users_color =		'#FFFF99';
$users_font =		'BLACK';
$users_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

#require("admin_header.php");

$MAIN.="<TABLE WIDTH=$page_width BGCOLOR=\"#$SSframe_background\" cellpadding=2 cellspacing=0><TR BGCOLOR=\"#$SSframe_background\"><TD>\n";

$MAIN.=_QXZ("Agent Time Sheet for").": $user\n";
$MAIN.="<BR>\n";
$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET> &nbsp; \n";
$MAIN.=_QXZ("Date").": <INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=19 VALUE=\"$query_date\">\n";
$MAIN.=_QXZ("User ID").": <INPUT TYPE=TEXT NAME=agent SIZE=20 MAXLENGTH=20 VALUE=\"$agent\">\n";

if ($archives_available=="Y") 
	{
	$MAIN.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR><BR>\n";
	}

$MAIN.="<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="</FORM>\n\n";

$MAIN.="<PRE><FONT SIZE=3>\n";


if (!$agent)
{
$MAIN.="\n";
$MAIN.=_QXZ("PLEASE SELECT AN AGENT ID AND DATE-TIME ABOVE AND CLICK SUBMIT")."\n";
$MAIN.=" "._QXZ("NOTE: stats taken from available agent log data")."\n";
}

else
{
$query_date_BEGIN = "$query_date 00:00:00";   
$query_date_END = "$query_date 23:59:59";
$time_BEGIN = "00:00:00";   
$time_END = "23:59:59";

$stmt="select full_name from vicidial_users where user='$agent' $vuLOGadmin_viewable_groupsSQL;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);
$full_name = $row[0];

$MAIN.=""._QXZ("Agent Time Sheet",44)." $NOW_TIME\n";

$MAIN.=_QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
$MAIN.="---------- "._QXZ("AGENT TIME SHEET").": $agent - $full_name -------------\n\n";

$CSV_text_header.="\""._QXZ("Agent Time Sheet")." - $NOW_TIME\"\n";
$CSV_text_header.="\""._QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\"\n";
$CSV_text_header.="\""._QXZ("AGENT TIME SHEET").": $agent - $full_name\"\n\n";


if ($calls_summary)
	{
	$stmt="select count(*) as calls,sum(talk_sec) as talk,avg(talk_sec),sum(pause_sec),avg(pause_sec),sum(wait_sec),avg(wait_sec),sum(dispo_sec),avg(dispo_sec) from ".$vicidial_agent_log_table." where event_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and event_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and user='" . mysqli_real_escape_string($link, $agent) . "' and pause_sec<48800 and wait_sec<48800 and talk_sec<48800 and dispo_sec<48800 limit 1;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);

	$TOTAL_TIME = ($row[1] + $row[3] + $row[5] + $row[7]);

	$TOTAL_TIME_HMS =		sec_convert($TOTAL_TIME,'H'); 
	$TALK_TIME_HMS =		sec_convert($row[1],'H'); 
	$PAUSE_TIME_HMS =		sec_convert($row[3],'H'); 
	$WAIT_TIME_HMS =		sec_convert($row[5],'H'); 
	$WRAPUP_TIME_HMS =		sec_convert($row[7],'H'); 
	$TALK_AVG_MS =			sec_convert($row[2],'H'); 
	$PAUSE_AVG_MS =			sec_convert($row[4],'H'); 
	$WAIT_AVG_MS =			sec_convert($row[6],'H'); 
	$WRAPUP_AVG_MS =		sec_convert($row[8],'H'); 

	$pfTOTAL_TIME_HMS =		sprintf("%8s", $TOTAL_TIME_HMS);
	$pfTALK_TIME_HMS =		sprintf("%8s", $TALK_TIME_HMS);
	$pfPAUSE_TIME_HMS =		sprintf("%8s", $PAUSE_TIME_HMS);
	$pfWAIT_TIME_HMS =		sprintf("%8s", $WAIT_TIME_HMS);
	$pfWRAPUP_TIME_HMS =	sprintf("%8s", $WRAPUP_TIME_HMS);
	$pfTALK_AVG_MS =		sprintf("%6s", $TALK_AVG_MS);
	$pfPAUSE_AVG_MS =		sprintf("%6s", $PAUSE_AVG_MS);
	$pfWAIT_AVG_MS =		sprintf("%6s", $WAIT_AVG_MS);
	$pfWRAPUP_AVG_MS =		sprintf("%6s", $WRAPUP_AVG_MS);

	$MAIN.=_QXZ("TOTAL CALLS TAKEN",17).": $row[0]     <a href='$PHP_SELF?calls_summary=$calls_summary&agent=$agent&query_date=$query_date&file_download=1&search_archived_data=$search_archived_data'>["._QXZ("DOWNLOAD")."]</a>\n";
	$MAIN.=_QXZ("TALK TIME:",24)." $pfTALK_TIME_HMS "._QXZ("AVERAGE",11,"r").": $pfTALK_AVG_MS\n";
	$MAIN.=_QXZ("PAUSE TIME:",24)." $pfPAUSE_TIME_HMS "._QXZ("AVERAGE",11,"r").": $pfPAUSE_AVG_MS\n";
	$MAIN.=_QXZ("WAIT TIME:",24)." $pfWAIT_TIME_HMS "._QXZ("AVERAGE",11,"r").": $pfWAIT_AVG_MS\n";
	$MAIN.=_QXZ("WRAPUP TIME:",24)." $pfWRAPUP_TIME_HMS "._QXZ("AVERAGE",11,"r").": $pfWRAPUP_AVG_MS\n";
	$MAIN.="----------------------------------------------------------------\n";
	$MAIN.=_QXZ("TOTAL ACTIVE AGENT TIME").": $pfTOTAL_TIME_HMS\n\n";
	$CSV_text1.=$CSV_text_header;
	$CSV_text1.="\"\",\""._QXZ("TOTAL CALLS TAKEN").": $row[0]\"\n";
	$CSV_text1.="\"\",\""._QXZ("TALK TIME").":\",\"$pfTALK_TIME_HMS\",\""._QXZ("AVERAGE").":\",\"$pfTALK_AVG_MS\"\n";
	$CSV_text1.="\"\",\""._QXZ("PAUSE TIME").":\",\"$pfPAUSE_TIME_HMS\",\""._QXZ("AVERAGE").":\",\"$pfPAUSE_AVG_MS\"\n";
	$CSV_text1.="\"\",\""._QXZ("WAIT TIME").":\",\"$pfWAIT_TIME_HMS\",\""._QXZ("AVERAGE").":\",\"$pfWAIT_AVG_MS\"\n";
	$CSV_text1.="\"\",\""._QXZ("WRAPUP TIME").":\",\"$pfWRAPUP_TIME_HMS\",\""._QXZ("AVERAGE").":\",\"$pfWRAPUP_AVG_MS\"\n";
	$CSV_text1.="\"\",\""._QXZ("TOTAL ACTIVE AGENT TIME").":\",\"$pfTOTAL_TIME_HMS\"\n\n";
	}
else
	{
	$MAIN.="<a href=\"$PHP_SELF?calls_summary=1&agent=$agent&query_date=$query_date\">"._QXZ("Call Activity Summary")."</a>\n\n";

	}

$stmt="select event_time,UNIX_TIMESTAMP(event_time) from ".$vicidial_agent_log_table." where event_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and event_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and user='" . mysqli_real_escape_string($link, $agent) . "' order by event_time limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$MAIN.=_QXZ("FIRST LOGIN:",21)." $row[0]\n";
$start = $row[1];

$CSV_login.="\"\",\""._QXZ("FIRST LOGIN").":\",\"$row[0]\"\n";

$stmt="select event_time,UNIX_TIMESTAMP(event_time) from ".$vicidial_agent_log_table." where event_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and event_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and user='" . mysqli_real_escape_string($link, $agent) . "' order by event_time desc limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$MAIN.=_QXZ("LAST LOG ACTIVITY:",21)." $row[0]\n";
$end = $row[1];

$CSV_login.="\"\",\""._QXZ("LAST LOG ACTIVITY").":\",\"$row[0]\"\n";

$login_time = ($end - $start);
$LOGIN_TIME_HMS =		sec_convert($login_time,'H'); 
$pfLOGIN_TIME_HMS =		sprintf("%8s", $LOGIN_TIME_HMS);

$MAIN.="-----------------------------------------\n";
$MAIN.=_QXZ("TOTAL LOGGED-IN TIME:",32)." $pfLOGIN_TIME_HMS\n";

$CSV_login.="\"\",\""._QXZ("TOTAL LOGGED-IN TIME").":\",\"$pfLOGIN_TIME_HMS\"\n\n";
$CSV_text1.=$CSV_login;

### timeclock records


##### vicidial_timeclock log records for user #####

$total_login_time=0;
$SQday_ARY =	explode('-',$query_date_BEGIN);
$EQday_ARY =	explode('-',$query_date_END);
$SQepoch = mktime(0, 0, 0, $SQday_ARY[1], $SQday_ARY[2], $SQday_ARY[0]);
$EQepoch = mktime(23, 59, 59, $EQday_ARY[1], $EQday_ARY[2], $EQday_ARY[0]);

$MAIN.="\n";

$MAIN.="<B>"._QXZ("TIMECLOCK LOGIN/LOGOUT TIME").":     <a href='$PHP_SELF?calls_summary=$calls_summary&agent=$agent&query_date=$query_date&file_download=2&search_archived_data=$search_archived_data'>["._QXZ("DOWNLOAD")."]</a></B>\n";
$MAIN.="<TABLE width=550 cellspacing=0 cellpadding=1>\n";
$MAIN.="<tr><td><font size=2>"._QXZ("ID")." </td><td><font size=2>"._QXZ("EDIT")." </td><td align=right><font size=2>"._QXZ("EVENT")." </td><td align=right><font size=2> "._QXZ("DATE")."</td><td align=right><font size=2> "._QXZ("IP ADDRESS")."</td><td align=right><font size=2> "._QXZ("GROUP")."</td><td align=right><font size=2>"._QXZ("HOURS:MINUTES")."</td></tr>\n";

$CSV_text2.=$CSV_text_header;
$CSV_text2.=$CSV_login;
$CSV_text2.="\""._QXZ("TIMECLOCK LOGIN/LOGOUT TIME").":\"\n";
$CSV_text2.="\"\",\""._QXZ("ID")."\",\""._QXZ("EDIT")."\",\""._QXZ("EVENT")."\",\""._QXZ("DATE")."\",\""._QXZ("IP ADDRESS")."\",\""._QXZ("GROUP")."\",\""._QXZ("HOURS:MINUTES")."\"\n";

	$stmt="SELECT event,event_epoch,user_group,login_sec,ip_address,timeclock_id,manager_user from ".$vicidial_timeclock_log_table." where user='$agent' and event_epoch >= '$SQepoch'  and event_epoch <= '$EQepoch';";
	if ($DB>0) {$MAIN.="|$stmt|";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$events_to_print = mysqli_num_rows($rslt);

	$total_logs=0;
	$o=0;
	while ($events_to_print > $o) {
		$row=mysqli_fetch_row($rslt);
		if ( ($row[0]=='START') or ($row[0]=='LOGIN') )
			{$bgcolor='bgcolor="#'.$SSstd_row3_background.'"';} 
		else
			{$bgcolor='bgcolor="#'.$SSstd_row4_background.'"';}

		$TC_log_date = date("Y-m-d H:i:s", $row[1]);

		$manager_edit='';
		if (strlen($row[6])>0) {$manager_edit = ' * ';}

		if (preg_match('/LOGIN/', $row[0]))
			{
			$login_sec='';
			$MAIN.="<tr $bgcolor><td><font size=2><A HREF=\"./timeclock_edit.php?timeclock_id=$row[5]\">$row[5]</A></td>";
			$MAIN.="<td align=right><font size=2>$manager_edit</td>";
			$MAIN.="<td align=right><font size=2>$row[0]</td>";
			$MAIN.="<td align=right><font size=2> $TC_log_date</td>\n";
			$MAIN.="<td align=right><font size=2> $row[4]</td>\n";
			$MAIN.="<td align=right><font size=2> $row[2]</td>\n";
			$MAIN.="<td align=right><font size=2> </td></tr>\n";
			$CSV_text2.="\"\",\"$row[5]\",\"$manager_edit\",\"$row[0]\",\"$TC_log_date\",\"$row[4]\",\"$row[2]\",\"\"\n";
			}
		if (preg_match('/LOGOUT/', $row[0]))
			{
			$login_sec = $row[3];
			$total_login_time = ($total_login_time + $login_sec);
			$event_hours_minutes =		sec_convert($login_sec,'H'); 

			$MAIN.="<tr $bgcolor><td><font size=2><A HREF=\"./timeclock_edit.php?timeclock_id=$row[5]\">$row[5]</A></td>";
			$MAIN.="<td align=right><font size=2>$manager_edit</td>";
			$MAIN.="<td align=right><font size=2>$row[0]</td>";
			$MAIN.="<td align=right><font size=2> $TC_log_date</td>\n";
			$MAIN.="<td align=right><font size=2> $row[4]</td>\n";
			$MAIN.="<td align=right><font size=2> $row[2]</td>\n";
			$MAIN.="<td align=right><font size=2> $event_hours_minutes";
			if ($DB) {$MAIN.=" - $total_login_time - $login_sec";}
			$MAIN.="</td></tr>\n";
			$CSV_text2.="\"\",\"$row[5]\",\"$manager_edit\",\"$row[0]\",\"$TC_log_date\",\"$row[4]\",\"$row[2]\",\"$event_hours_minutes\"\n";
			}
		$o++;
	}
if (strlen($login_sec)<1)
	{
	$login_sec = ($STARTtime - $row[1]);
	$total_login_time = ($total_login_time + $login_sec);
		if ($DB) {$MAIN.=_QXZ("LOGIN ONLY")." - $total_login_time - $login_sec";}
	}
$total_login_hours_minutes =		sec_convert($total_login_time,'H'); 

	if ($DB) {$MAIN.=" - $total_login_time - $login_sec";}

$MAIN.="<tr><td align=right><font size=2> </td>";
$MAIN.="<td align=right><font size=2> </td>\n";
$MAIN.="<td align=right><font size=2> </td>\n";
$MAIN.="<td align=right><font size=2> </td>\n";
$MAIN.="<td align=right colspan=2><font size=2><font size=2>"._QXZ("TOTAL")." </td>\n";
$MAIN.="<td align=right><font size=2> $total_login_hours_minutes  </td></tr>\n";
$CSV_text2.="\"\",\"\",\"\",\"\",\"\",\"\",\""._QXZ("TOTAL")."\",\"$total_login_hours_minutes\"\n";

$MAIN.="</TABLE><BR>$db_source\n";
$MAIN.="</BODY></HTML>\n";
}
	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_agent_time_sheet_$US$FILE_TIME.csv";
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
		header ("Content-type: text/html; charset=utf-8");
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

