<?php 
# AST_recording_log_report.php
#
# This report is for viewing the a report of which users accessed which recordings
#
# Copyright (C) 2019  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 160115-2303 - First build
# 170409-1538 - Added IP List validation code
# 170824-2130 - Added HTML formatting and screen colors
# 180507-2315 - Added new help display
# 191013-0830 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["access_date_D"]))			{$access_date_D=$_GET["access_date_D"];}
	elseif (isset($_POST["access_date_D"]))	{$access_date_D=$_POST["access_date_D"];}
if (isset($_GET["access_date_end_D"]))			{$access_date_end_D=$_GET["access_date_end_D"];}
	elseif (isset($_POST["access_date_end_D"]))	{$access_date_end_D=$_POST["access_date_end_D"];}
if (isset($_GET["access_date_T"]))			{$access_date_T=$_GET["access_date_T"];}
	elseif (isset($_POST["access_date_T"]))	{$access_date_T=$_POST["access_date_T"];}
if (isset($_GET["access_date_end_T"]))			{$access_date_end_T=$_GET["access_date_end_T"];}
	elseif (isset($_POST["access_date_end_T"]))	{$access_date_end_T=$_POST["access_date_end_T"];}
if (isset($_GET["users"]))					{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))			{$users=$_POST["users"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))		{$file_download=$_POST["file_download"];}
if (isset($_GET["list_ids"]))					{$list_ids=$_GET["list_ids"];}
	elseif (isset($_POST["list_ids"]))			{$list_ids=$_POST["list_ids"];}
if (isset($_GET["recording_date_D"]))			{$recording_date_D=$_GET["recording_date_D"];}
	elseif (isset($_POST["recording_date_D"]))	{$recording_date_D=$_POST["recording_date_D"];}
if (isset($_GET["recording_date_end_D"]))			{$recording_date_end_D=$_GET["recording_date_end_D"];}
	elseif (isset($_POST["recording_date_end_D"]))	{$recording_date_end_D=$_POST["recording_date_end_D"];}
if (isset($_GET["recording_date_T"]))			{$recording_date_T=$_GET["recording_date_T"];}
	elseif (isset($_POST["recording_date_T"]))	{$recording_date_T=$_POST["recording_date_T"];}
if (isset($_GET["recording_date_end_T"]))			{$recording_date_end_T=$_GET["recording_date_end_T"];}
	elseif (isset($_POST["recording_date_end_T"]))	{$recording_date_end_T=$_POST["recording_date_end_T"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

$report_name="Recording Access Log Report";
$NOW_DATE = date("Y-m-d");
if (!isset($users)) {$users=array();}
if (!isset($user_group)) {$user_group=array();}
if (!isset($access_date_D)) {$access_date_D=$NOW_DATE;}
if (!isset($access_date_end_D)) {$access_date_end_D=$NOW_DATE;}
if (!isset($access_date_T)) {$access_date_T="00:00:00";}
if (!isset($access_date_end_T)) {$access_date_end_T="23:59:59";}
$access_date_from="$access_date_D $access_date_T";
$access_date_to="$access_date_end_D $access_date_end_T";
$CSV_text="\""._QXZ("RECORDING ACCESS LOG REPORT")."\"\n";
$CSV_text.="\""._QXZ("ACCESS DATE RANGE").":\",\"$access_date_from "._QXZ("to")." $access_date_to\"\n";
$ASCII_rpt_header=""._QXZ("ACCESS DATE RANGE").": $access_date_from "._QXZ("to")." $access_date_to\n";

$recording_date_SQL="";
if ($recording_date_D) 
	{
	if (!isset($recording_date_end_D)) {$recording_date_end_D=$recording_date_D;}
	$recording_date_from="$recording_date_D 00:00:00";
	$recording_date_to="$recording_date_end_D 23:59:59";
	$recording_date_title="<LI>"._QXZ("RECORDINGS CREATED")." $recording_date_D "._QXZ("to")." $recording_date_end_D"; 
	$recording_date_SQL=" start_time>='$recording_date_from' and start_time<='$recording_date_to' and ";
	$CSV_text.="\""._QXZ("RECORDING DATE RANGE").":\",\"$recording_date_from "._QXZ("to")." $recording_date_to\"\n";
	$ASCII_rpt_header.=_QXZ("RECORDING DATE RANGE").": $recording_date_from "._QXZ("to")." $recording_date_to\n";
	}


#if (!isset($recording_date_D)) {$recording_date_D=$NOW_DATE;}
#if (!isset($recording_date_end_D)) {$recording_date_end_D=$NOW_DATE;}
#if (!isset($recording_date_T)) {$recording_date_T="00:00:00";}
#if (!isset($recording_date_end_T)) {$recording_date_end_T="23:59:59";}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
if ($archive_tbl) {$agent_log_table="vicidial_agent_log_archive";} else {$agent_log_table="vicidial_agent_log";}
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
$log_tables_array=array("recording_log", "vicidial_recording_access_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_recording_access_log_table=use_archive_table("vicidial_recording_access_log");
	$recording_log_table=use_archive_table("recording_log");
	}
else
	{
	$vicidial_recording_access_log_table="vicidial_recording_access_log";
	$recording_log_table="recording_log";
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




$i=0;
$users_string='|';
$users_ct = count($users);
while($i < $users_ct)
	{
	$users_string .= "$users[$i]|";
	$i++;
	}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $users_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$i++;
	}

$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	# if (preg_match('/\-ALL/',$user_group_string)) {$user_group[$i]=$row[0];}
	$i++;
	}

$stmt="select user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_list[$i]=$row[0];
	$user_names[$i]=$row[1];
	if ($all_users) {$user_list[$i]=$row[0];}
	$i++;
	}


$i=0;
$user_string='|';
$user_ct = count($users);
while($i < $user_ct)
	{
	$user_string .= "$users[$i]|";
	$userQS .= "&users[]=$users[$i]";
	$vra_user_SQL="vra.user in ('".implode("','", $users)."') and ";
	$vu_user_SQL="vu.user in ('".implode("','", $users)."') and ";
	$user_SQL="vra.user in ('".implode("','", $users)."') and ";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$user_string) ) or ($user_ct < 1) )
	{$vu_user_SQL = ""; $vra_user_SQL = ""; $user_SQL="";}
else
	{
	$agents_title="<LI>"._QXZ("AGENTS").": ".implode(", ", $users);
	$ASCII_rpt_header.=_QXZ("AGENTS").": ".implode(", ", $users)."\n";
	$CSV_text.="\""._QXZ("AGENTS").":\",\"".implode(", ", $users)."\"\n";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>"._QXZ("SELECTED AGENTS")."</th>";
	}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$user_group_SQL=" user_group in ('".implode("','", $user_group)."') and vra.user=vu.user and ";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	$ASCII_rpt_header.="   "._QXZ("User groups").": ".implode(", ", $user_group)."\n";
	$CSV_text.="\""._QXZ("User groups").":\",\"".implode(", ", $user_group)."\"\n";
	$GRAPH_header.="<th class='column_header grey_graph_cell'>"._QXZ("SELECTED USER GROUPS")."</th>";
	}


##### BEGIN Define colors and logo #####
$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='B9CBFD';
$SSstd_row3_background='8EBCFD';
$SSstd_row4_background='B6D3FC';
$SSstd_row5_background='FFFFFF';
$SSalt_row1_background='BDFFBD';
$SSalt_row2_background='99FF99';
$SSalt_row3_background='CCFFCC';

$screen_color_stmt="select admin_screen_colors from system_settings";
$screen_color_rslt=mysql_to_mysqli($screen_color_stmt, $link);
$screen_color_row=mysqli_fetch_row($screen_color_rslt);
$agent_screen_colors="$screen_color_row[0]";

if ($agent_screen_colors != 'default')
	{
	$asc_stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$agent_screen_colors';";
	$asc_rslt=mysql_to_mysqli($asc_stmt, $link);
	$qm_conf_ct = mysqli_num_rows($asc_rslt);
	if ($qm_conf_ct > 0)
		{
		$asc_row=mysqli_fetch_row($asc_rslt);
		$SSmenu_background =            $asc_row[0];
		$SSframe_background =           $asc_row[1];
		$SSstd_row1_background =        $asc_row[2];
		$SSstd_row2_background =        $asc_row[3];
		$SSstd_row3_background =        $asc_row[4];
		$SSstd_row4_background =        $asc_row[5];
		$SSstd_row5_background =        $asc_row[6];
		$SSalt_row1_background =        $asc_row[7];
		$SSalt_row2_background =        $asc_row[8];
		$SSalt_row3_background =        $asc_row[9];
		$SSweb_logo =		           $asc_row[10];
		}
	}

if ($DB) {echo "$user_group_string|$user_group_ct|$user_groupQS|$i<BR>\n";}

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date$groupQS$user_groupQS&shift=$shift&DB=$DB&show_percentages=$show_percentages";

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
$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function ToggleSpan(spanID) {\n";
$HTML_text.="  if (document.getElementById(spanID).style.display == 'none') {\n";
$HTML_text.="    document.getElementById(spanID).style.display = 'block';\n";
$HTML_text.="  } else {\n";
$HTML_text.="    document.getElementById(spanID).style.display = 'none';\n";
$HTML_text.="  }\n";
$HTML_text.=" }\n";
$HTML_text.="</script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

#	require("admin_header.php");

$HTML_text.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLSPACING=3><TR><TD VALIGN=TOP> "._QXZ("Access date range").":<BR>";
$HTML_text.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=access_date ID=access_date VALUE=\"$access_date\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=access_date_end ID=access_date_end VALUE=\"$access_date_end\">\n";
$HTML_text.="<INPUT TYPE=TEXT NAME=access_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$access_date_D\">";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'access_date_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=access_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$access_date_T\">";
$HTML_text.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=access_date_end_D SIZE=11 MAXLENGTH=10 VALUE=\"$access_date_end_D\">";
$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'access_date_end_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=access_date_end_T SIZE=9 MAXLENGTH=8 VALUE=\"$access_date_end_T\">";
$HTML_text.="</TD>";

# $HTML_text.="<TD VALIGN=TOP>"._QXZ("User Groups").":<BR>";
# $HTML_text.="<SELECT SIZE=5 NAME=user_group[] multiple>\n";
# if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
# 	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
# else
# 	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
# $o=0;
# while ($user_groups_to_print > $o)
# 	{
# 	if  (preg_match("/$user_groups[$o]\|/i",$user_group_string)) {$HTML_text.="<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
# 	  else {$HTML_text.="<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
# 	$o++;
# 	}
# $HTML_text.="</SELECT>\n";
# $HTML_text.="</TD>";

$HTML_text.="<TD VALIGN=TOP>"._QXZ("Users").": <BR>";
$HTML_text.="<SELECT SIZE=5 NAME=users[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$users_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USERS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USERS")." --</option>\n";}
$o=0;
while ($users_to_print > $o)
	{
	if  (preg_match("/$user_list[$o]\|/i",$users_string)) {$HTML_text.="<option selected value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	  else {$HTML_text.="<option value=\"$user_list[$o]\">$user_list[$o] - $user_names[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";

$HTML_text.="<TD VALIGN=TOP> "._QXZ("Recording date range").":<BR>";
$HTML_text.="<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=recording_date ID=recording_date VALUE=\"$recording_date\">\n";
$HTML_text.="<INPUT TYPE=hidden NAME=recording_date_end ID=recording_date_end VALUE=\"$recording_date_end\">\n";
$HTML_text.="<INPUT TYPE=TEXT NAME=recording_date_D SIZE=11 MAXLENGTH=10 VALUE=\"$recording_date_D\">";
$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'recording_date_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
#$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=recording_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$recording_date_T\">";
$HTML_text.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=recording_date_end_D SIZE=11 MAXLENGTH=10 VALUE=\"$recording_date_end_D\">";
$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'vicidial_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'recording_date_end_D'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";
#$HTML_text.=" &nbsp; <INPUT TYPE=TEXT NAME=access_date_end_T SIZE=9 MAXLENGTH=8 VALUE=\"$recording_date_end_T\">";
$HTML_text.="</TD><TD VALIGN='TOP'>";
$HTML_text.=_QXZ("Display as:")."<BR>";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
$HTML_text.="<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
if ($archives_available=="Y") 
	{
	$HTML_text.="<BR><BR><input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}
$HTML_text.="</TD>";
$HTML_text.="</TR></TABLE>";
$HTML_text.="</FORM>\n\n";

$TEXT.="<PRE><FONT SIZE=2>\n";

if ($SUBMIT) 
	{
	$stmt="select vra.recording_access_log_id,vra.recording_id,vra.lead_id,vra.user,vra.access_datetime,vra.access_result,vra.ip, vu.full_name, vu.user_group, r.start_time from ".$vicidial_recording_access_log_table." vra, vicidial_users vu, ".$recording_log_table." r where access_datetime>='$access_date_from' and access_datetime<='$access_date_to' and $user_SQL $user_group_SQL $recording_date_SQL vra.recording_id=r.recording_id and vra.user=vu.user order by access_datetime asc";
	if ($DB) {print $stmt."\n";}

	$ASCII_border="+--------------+---------------------+------------+--------------------------------+---------------------+---------------------+-----------------+\n";
	$TEXT.=$ASCII_rpt_header;
	$TEXT.=sprintf("%110s", " ");
	$TEXT.="<a href=\"$PHP_SELF?DB=$DB&access_date_D=$access_date_D&access_date_T=$access_date_T&access_date_end_D=$access_date_end_D&access_date_end_T=$access_date_end_T&recording_date_D=$recording_date_D&recording_date_end_D=$recording_date_end_D$userQS$user_groupQS&file_download=1&SUBMIT=$SUBMIT\">"._QXZ("DOWNLOAD")."</a>\n";

	$HTML.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
	$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML.="<td colspan='6' align='left'><B><font size='2'>"._QXZ("RECORDING ACCESS LOG FOR:")."<UL><LI>"._QXZ("RECORDS VIEWED ")."$access_date_from "._QXZ("TO")." $access_date_to$recording_date_title $agents_title</UL></font></B></td>";
	$HTML.="<th><font size='2'><a href=\"$PHP_SELF?DB=$DB&access_date_D=$access_date_D&access_date_T=$access_date_T&access_date_end_D=$access_date_end_D&access_date_end_T=$access_date_end_T&recording_date_D=$recording_date_D&recording_date_end_D=$recording_date_end_D$userQS$user_groupQS&file_download=1&SUBMIT=$SUBMIT\">"._QXZ("DOWNLOAD")."</a></font></th>";
	$HTML.="</tr>\n";
	$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML.="<th><font size='2'>"._QXZ("RECORDING ID")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("RECORDING DATE/TIME")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("LEAD ID")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("USER")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("DATE/TIME ACCESSED")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("ACCESS RESULT")."</font></th>";
	$HTML.="<th><font size='2'>"._QXZ("IP")."</font></th>";
	$HTML.="</tr>\n";

	$TEXT.=$ASCII_border;
	$TEXT.="| "._QXZ("RECORDING ID", 12)." | "._QXZ("RECORDING DATE/TIME", 19)." | "._QXZ("LEAD ID", 10)." | "._QXZ("USER", 30)." | "._QXZ("DATE/TIME ACCESSED", 19)." | "._QXZ("ACCESS RESULT", 19)." | "._QXZ("IP", 15)." |\n";
	$TEXT.=$ASCII_border;

	$CSV_text.="\n\""._QXZ("RECORDING ID")."\",\""._QXZ("RECORDING DATE/TIME")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("USER")."\",\""._QXZ("DATE/TIME ACCESSED")."\",\""._QXZ("ACCESS RESULT")."\",\""._QXZ("IP")."\"\n";

	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_array($rslt)) 
		{
		$full_user=substr($row["user"]." - ".$row["full_name"], 0, 30);
		$TEXT.="| ";
		$TEXT.=sprintf("%-12s", $row["recording_id"])." | ";
		$TEXT.=sprintf("%-19s", $row["start_time"])." | ";
		$TEXT.=sprintf("%-10s", $row["lead_id"])." | ";
		$TEXT.=sprintf("%-30s", $full_user)." | ";
		$TEXT.=sprintf("%-19s", $row["access_datetime"])." | ";
		$TEXT.=sprintf("%-19s", _QXZ($row["access_result"], 19))." | ";
		$TEXT.=sprintf("%-15s", $row["ip"])." |\n";

		$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
		$HTML.="<th><font size='2'>".$row["recording_id"]."</font></th>";
		$HTML.="<th><font size='2'>".$row["start_time"]."</font></th>";
		$HTML.="<th><font size='2'>".$row["lead_id"]."</font></th>";
		$HTML.="<th><font size='2'>".$row["user"]." - ".$row["full_name"]."</font></th>";
		$HTML.="<th><font size='2'>".$row["access_datetime"]."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ($row["access_result"])."</font></th>";
		$HTML.="<th><font size='2'>".$row["ip"]."</font></th>";
		$HTML.="</tr>\n";

		$CSV_text.="\"$row[recording_id]\",\"$row[start_time]\",\"$row[lead_id]\",\"$full_user\",\"$row[access_datetime]\",\"$row[access_result]\",\"$row[ip]\"\n";
		}
	$TEXT.=$ASCII_border;

	}

$TEXT.="</FONT></PRE>";
$HTML.="</table>";

if ($report_display_type=="HTML") {
	$HTML_text.=$HTML;
} else {
	$HTML_text.=$TEXT;
}

$HTML_text.="</BODY></HTML>";


if ($file_download == 0 || !$file_download) 
	{
	echo $HTML_head;
	require("admin_header.php");
	echo $HTML_text;
	}
else
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_recording_access_log_report_$US$FILE_TIME.csv";
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