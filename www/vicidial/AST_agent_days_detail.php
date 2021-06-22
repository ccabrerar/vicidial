<?php 
# AST_agent_days_detail.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 90206-2202 - First build
# 90225-1051 - Added CSV download option
# 90310-0752 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 100214-1421 - Sort menu alphabetically
# 100216-0042 - Added popup date selector
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation and allowed campaigns restrictions
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 111104-1302 - Added user_group restrictions for selecting in-groups
# 120224-0910 - Added HTML display option with bar graphs
# 130414-0143 - Added report logging
# 130610-1037 - Finalized changing of all ereg instances to preg
# 130621-0830 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130704-0942 - Fixed issue #675
# 130831-0928 - Changed to mysqli PHP functions
# 140108-0751 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0021 - Finalized adding QXZ translation to all admin files
# 141230-1526 - Added code for on-the-fly language translations display
# 150516-1307 - Fixed Javascript element problem, Issue #857
# 151227-1718 - Added option to search archive records instead of main
# 160121-2216 - Added report title header, default report format, cleaned up formatting
# 160225-1625 - Fixed download issue where report was not printing properly
# 160714-2348 - Added and tested ChartJS features for more aesthetically appealing graphs
# 170409-1542 - Added IP List validation code
# 171012-2015 - Fixed javascript/apache errors with graphs
# 171204-2300 - Fixed minor reporting bug
# 191013-0848 - Fixes for PHP7
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
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Single Agent Daily';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
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
	$SSreport_default_format =		$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_agent_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_agent_log_table=use_archive_table("vicidial_agent_log");
	}
else
	{
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
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
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

$MT=array();
$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
	}

$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	if (preg_match('/\-ALL/',$group_string) )
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
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	$group_SQL = preg_replace('/,$/i', '',$group_SQL);
	$group_SQL = "and campaign_id IN($group_SQL)";
	}

$customer_interactive_statuses='';
$stmt="select status from vicidial_statuses where human_answered='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$customer_interactive_statuses .= "|$row[0]";
	$i++;
	}
$stmt="select status from vicidial_campaign_statuses where human_answered='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$statha_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $statha_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$customer_interactive_statuses .= "|$row[0]";
	$i++;
	}
if (strlen($customer_interactive_statuses)>0)
	{$customer_interactive_statuses .= '|';}

#$customer_interactive_statuses = '|NI|DNC|CALLBK|AP|SALE|COMP|HAP1|HAP2|HBED|DIED|';
#$customer_interactive_statuses = '|NI|DNC|CALLBK|XFER|C2|B7|B8|C1|';

$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date&shift=$shift&DB=$DB&user=$user$groupQS&search_archived_data=$search_archived_data&report_display_type=$report_display_type";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

if ($file_download < 1)
	{
	?>

	<HTML>
	<HEAD>
	<STYLE type="text/css">
	<!--
	   .yellow {color: white; background-color: yellow}
	   .red {color: white; background-color: red}
	   .blue {color: white; background-color: blue}
	   .purple {color: white; background-color: purple}
	-->
	 </STYLE>

	<?php

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
	require("chart_button.php");
	echo "<script src='chart/Chart.js'></script>\n"; 
	echo "<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:21;'></div>";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\"  id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";
	echo "<span style=\"position:absolute;left:3px;top:30px;z-index:19;\"  id=agent_status_stats>\n";
	echo "<b>"._QXZ("$report_name")."</b> $NWB#single_agent_daily$NWE\n";
	echo "<PRE><FONT SIZE=2>";
	}

if (strlen($group[0]) < 1)
	{
	echo "";
	echo _QXZ("PLEASE SELECT A USER AND DATE-TIME BELOW AND CLICK SUBMIT")."\n";
	echo " "._QXZ("NOTE: stats taken from shift specified")."\n";
	}

else
	{
	if ($shift == 'AM') 
		{
		$time_BEGIN=$AM_shift_BEGIN;
		$time_END=$AM_shift_END;
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "03:45:00";}   
		if (strlen($time_END) < 6) {$time_END = "15:14:59";}
		}
	if ($shift == 'PM') 
		{
		$time_BEGIN=$PM_shift_BEGIN;
		$time_END=$PM_shift_END;
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "15:15:00";}
		if (strlen($time_END) < 6) {$time_END = "23:15:00";}
		}
	if ($shift == 'ALL') 
		{
		if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
		if (strlen($time_END) < 6) {$time_END = "23:59:59";}
		}
	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";

	if ($file_download < 1)
		{
		$ASCII_text.=_QXZ("Agent Days Status Report",24).": $user                     $NOW_TIME ($db_source)\n";
		$ASCII_text.=_QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}
	else
		{
		$file_output .= _QXZ("Agent Days Status Report",24).": $user                     $NOW_TIME ($db_source)\n";
		$file_output .= _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}

	$statuses='-';
	$statusesTXT='';
	$statusesHEAD='';
	$statusesHTML='';
	$statusesFILE='';
	$statusesARY=array();
	$statusesARY[0]='';
	$j=0;
	$dates='-';
	$date=array();
	$calls=array();
	$status=array();
	$datesARY=array();
	$datesARY[0]='';
	$date_namesARY=array();
	$date_namesARY[0]='';
	$k=0;

	$stmt="select date_format(event_time, '%Y-%m-%d') as date,count(*) as calls,status from vicidial_users,".$vicidial_agent_log_table." where event_time <= '$query_date_END' and event_time >= '$query_date_BEGIN' and vicidial_users.user=".$vicidial_agent_log_table.".user and ".$vicidial_agent_log_table.".user='$user' $group_SQL $user_group_SQL $vuLOGadmin_viewable_groupsSQL group by date,status order by date,status desc limit 500000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);

		if ( ($row[1] > 0) and (strlen($row[2]) > 0) )
			{
			$date[$i] =			$row[0];
			$calls[$i] =		$row[1];
			$status[$i] =		$row[2];
			if ( (!preg_match("/\-$status[$i]\-/i", $statuses)) and (strlen($status[$i])>0) )
				{
				$statusesTXT = sprintf("%8s", $status[$i]);
				$statusesHEAD .= "----------+";
				$statusesHTML .= " $statusesTXT |";
				$statusesFILE .= "$status[$i],";
				$statuses .= "$status[$i]-";
				$statusesARY[$j] = $status[$i];
				$j++;
				}
			if (!preg_match("/\-$date[$i]\-/i", $dates))
				{
				$dates .= "$date[$i]-";
				$datesARY[$k] = $date[$i];
				$k++;
				}
			}
		$i++;
		}

	if ($file_download < 1)
		{
		$ASCII_text.=_QXZ("LEAD STATS BREAKDOWN").":\n";
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";
		$ASCII_text.="| <a href=\"$LINKbase\">"._QXZ("DATE",10)."</a> | <a href=\"$LINKbase&stage=LEADS\">"._QXZ("CALLS",6)."</a> | <a href=\"$LINKbase&stage=CI\">"._QXZ("CIcalls",7)."</a>| <a href=\"$LINKbase&stage=DNCCI\">"._QXZ("DNC/CI",6)."%</a>|$statusesHTML\n";
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";
		for ($i=0; $i<count($statusesARY); $i++) {
			$Sstatus=$statusesARY[$i];
			$SstatusTXT=$Sstatus;
			if ($Sstatus=="") {$SstatusTXT="(blank)";}
		}

		}
	else
		{
		$file_output .= _QXZ("DATE").","._QXZ("CALLS").","._QXZ("CIcalls").","._QXZ("DNC-CI")."%,$statusesFILE\n";
		}

	### BEGIN loop through each user ###
	$m=0;
	$CIScountTOT=0;
	$DNCcountTOT=0;

	$graph_stats=array();
	$TOPsort=array();
	$TOPsortTALLY=array();
	$TOPsorted_output=array();
	$TOPsorted_outputFILE=array();
	$max_calls=1;
	$max_cicalls=1;
	$max_dncci=1;

	while ($m < $k)
		{
		$Sdate=$datesARY[$m];
		$Scalls=0;
		$SstatusesHTML='';
		$SstatusesFILE='';
		$CIScount=0;
		$DNCcount=0;

		### BEGIN loop through each status ###
		$n=0;
		while ($n < $j)
			{
			$Sstatus=$statusesARY[$n];
			$SstatusTXT='';
			$varname=$Sstatus."_graph";
			$$varname=$graph_header."<th class='thgraph' scope='col'>$Sstatus</th></tr>";
			$max_varname="max_".$Sstatus;
			$graph_stats[$m][(4+$n)]=0;
			### BEGIN loop through each stat line ###
			$i=0; $status_found=0;
			while ($i < $rows_to_print)
				{
	#			if ( (preg_match("/$date[$i]/i", $Sdate)) and ($Sstatus=="$status[$i]") )
				if ( ($Sdate=="$date[$i]") and ($Sstatus=="$status[$i]") )
					{
					$Scalls =		($Scalls + $calls[$i]);
					if (preg_match("/\|$status[$i]\|/i",$customer_interactive_statuses))
						{
						$CIScount =	($CIScount + $calls[$i]);
						$CIScountTOT =	($CIScountTOT + $calls[$i]);
						}
					if (preg_match("/DNC/i", $status[$i]))
						{
						$DNCcount =	($DNCcount + $calls[$i]);
						$DNCcountTOT =	($DNCcountTOT + $calls[$i]);
						}

					if ($calls[$i]>$$max_varname) {$$max_varname=$calls[$i];}
					$graph_stats[$m][(4+$n)]=$calls[$i];					

					$SstatusTXT = sprintf("%8s", $calls[$i]);
					$SstatusesHTML .= " $SstatusTXT |";
					$SstatusesFILE .= "$calls[$i],";
					$status_found++;
					}
				$i++;
				}
			if ($status_found < 1)
				{
				$SstatusesHTML .= "        0 |";
				$SstatusesFILE .= "0,";
				}
			### END loop through each stat line ###
			$n++;
			}
		### END loop through each status ###
		$TOTcalls=($TOTcalls + $Scalls);

		$RAWdate = $Sdate;
		$RAWcalls = $Scalls;
		$RAWcis = $CIScount;
		$Scalls =	sprintf("%6s", $Scalls);
		$CIScount =	sprintf("%6s", $CIScount);

		$Sdate =		sprintf("%-10s", $Sdate);
			while(strlen($Suser)>10) {$Suser = substr("$Sdate", 0, -1);}

		$DNCcountPCTs = ( MathZDC($DNCcount, $CIScount) * 100);
		$RAWdncPCT = $DNCcountPCTs;
	#	$DNCcountPCTs = round($DNCcountPCTs,2);
		$DNCcountPCTs = round($DNCcountPCTs);
		$rawDNCcountPCTs = $DNCcountPCTs;
	#	$DNCcountPCTs = sprintf("%3.2f", $DNCcountPCTs);
		$DNCcountPCTs = sprintf("%6s", $DNCcountPCTs);

		if (trim($Scalls)>$max_calls) {$max_calls=trim($Scalls);}
		if (trim($CIScount)>$max_cicalls) {$max_cicalls=trim($CIScount);}
		if (trim($DNCcountPCTs)>$max_dncci) {$max_dncci=trim($DNCcountPCTs);}
		$graph_stats[$m][1]=trim("$Scalls");
		$graph_stats[$m][2]=trim("$CIScount");
		$graph_stats[$m][3]=trim("$DNCcountPCTs");

		if ($file_download < 1)
			{
			$Toutput = "| <a href=\"./user_stats.php?user=$user&start_date=$RAWdate\">$Sdate</a> | $Scalls | $CIScount | $DNCcountPCTs%|$SstatusesHTML\n";
			$graph_stats[$m][0]=trim("$Sdate");
			}
		else
			{
			$fileToutput = "$RAWdate,$RAWcalls,$RAWcis,$rawDNCcountPCTs%,$SstatusesFILE\n";
			}

		$TOPsorted_output[$m] = $Toutput;
		$TOPsorted_outputFILE[$m] = $fileToutput;

		if ($stage == 'ID')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWdate) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'LEADS')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWcalls) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$RAWcalls;
			}
		if ($stage == 'TIME')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $Stime) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$Stime;
			}
		if ($stage == 'CI')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWcis) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$RAWcis;
			}
		if ($stage == 'DNCCI')
			{
			$TOPsort[$m] =	'' . sprintf("%08s", $RAWdncPCT) . '-----' . $m . '-----' . sprintf("%020s", $RAWdate);
			$TOPsortTALLY[$m]=$RAWdncPCT;
			}
		if (!preg_match('/ID|TIME|LEADS|CI|DNCCI/',$stage))
			{
			if ($file_download < 1)
				{$ASCII_text.="$Toutput";}
			else
				{$file_output .= "$fileToutput";}
			}

		if ($TOPsortMAX < $TOPsortTALLY[$m]) {$TOPsortMAX = $TOPsortTALLY[$m];}

		$m++;
		}
	### END loop through each user ###

	$TOT_AGENTS = sprintf("%4s", $m);


	### BEGIN sort through output to display properly ###
	if (preg_match('/ID|TIME|LEADS|CI|DNCCI/',$stage))
		{
		if (preg_match('/ID/',$stage))
			{sort($TOPsort, SORT_NUMERIC);}
		if (preg_match('/TIME|LEADS|CI|DNCCI/',$stage))
			{rsort($TOPsort, SORT_NUMERIC);}

		$m=0;
		$sort_order=array();
		while ($m < $k)
			{
			$sort_split = explode("-----",$TOPsort[$m]);
			$i = $sort_split[1];
			$sort_order[$m] = "$i";
			if ($file_download < 1)
				{$ASCII_text.="$TOPsorted_output[$i]";}
			else
				{$file_output .= "$TOPsorted_outputFILE[$i]";}
			$m++;
			}
		}
	### END sort through output to display properly ###



	###### LAST LINE FORMATTING ##########
	### BEGIN loop through each status ###
	$SUMstatusesHTML='';
	$n=0;
	while ($n < $j)
		{
		$Scalls=0;
		$Sstatus=$statusesARY[$n];
		$SUMstatusTXT='';
		$total_var=$Sstatus."_total";
		### BEGIN loop through each stat line ###
		$i=0; $status_found=0;
		while ($i < $rows_to_print)
			{
			if ($Sstatus=="$status[$i]")
				{
				$Scalls =		($Scalls + $calls[$i]);
				$status_found++;
				}
			$i++;
			}
		### END loop through each stat line ###
		if ($status_found < 1)
			{
			$SUMstatusesHTML .= "        0 |";
			$$total_var=0;
			}
		else
			{
			$SUMstatusTXT = sprintf("%8s", $Scalls);
			$SUMstatusesHTML .= " $SUMstatusTXT |";
			$SUMstatusesFILE .= "$Scalls,";
			$$total_var=$Scalls;
			}
		$n++;
		}
	### END loop through each status ###

	$TOTcalls = sprintf("%7s", $TOTcalls);
	$CIScountTOT = sprintf("%7s", $CIScountTOT);
	$DNCcountPCT = ( MathZDC($DNCcountTOT, $CIScountTOT) * 100);
	#$DNCcountPCT = round($DNCcountPCT,2);
	$DNCcountPCT = round($DNCcountPCT);
	#$DNCcountPCT = sprintf("%3.2f", $DNCcountPCT);
	$DNCcountPCT = sprintf("%6s", $DNCcountPCT);


	if ($file_download < 1)
		{
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";
		$ASCII_text.="| "._QXZ("TOTALS",10)." | $TOTcalls| $CIScountTOT| $DNCcountPCT%|$SUMstatusesHTML\n";
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";

		$ASCII_text.="\n\n</PRE>";


		for ($e=0; $e<count($statusesARY); $e++) {
			$Sstatus=$statusesARY[$e];
			$SstatusTXT=$Sstatus;
			if ($Sstatus=="") {$SstatusTXT="(blank)";}
		}


		# USE THIS FOR multiple graphs, use pipe-delimited array elements, dataset_name|index|link_name
		$multigraph_text="";
		$graph_id++;
		$graph_array=array("ADD_CALLSdata|1|CALLS|integer|", "ADD_CICALLSdata|2|CI/CALLS|integer|", "ADD_DNCCIdata|3|DNC/CI|percent|");
		$default_graph="line"; # Graph that is initally displayed when page loads
		include("graph_color_schemas.inc"); 

		for ($e=0; $e<count($statusesARY); $e++) {
			$Sstatus=$statusesARY[$e];
			$SstatusTXT=$Sstatus;
			if ($Sstatus=="") {$SstatusTXT="(blank)";}
			array_push($graph_array, "ADD_SUBSTATUS".$e."data|".($e+4)."|".$SstatusTXT."|integer|");
		}

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
			$JS_text.="var main_ctx = document.getElementById(\"CanvasID".$graph_id."_".$q."\");\n";
			$JS_text.="var GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: '$default_graph', $chart_options data: $dataset_name});\n";
		}

		$graph_count=count($graph_array);
		$graph_title=_QXZ("LEAD STATS BREAKDOWN");
		include("graphcanvas.inc");
		echo $HTML_graph_head;
		$GRAPH_text.=$graphCanvas;
		

		}
	else
		{
		$file_output .= _QXZ("TOTALS").",".trim($TOTcalls).",".trim($CIScountTOT).",".trim($DNCcountPCT)."%,".trim($SUMstatusesFILE)."\n";
		}
	}


if ($file_download > 0)
	{
	$US='_';
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AGENT_DAYS_$user$US$FILE_TIME.csv";

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$file_output";

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
	}

$JS_onload.="}\n";
if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
$JS_text.="</script>\n";

echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>";
echo "<TABLE CELLSPACING=3 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Dates").":<BR>";
echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

?>
<script language="JavaScript">
function openNewWindow(url)
  {
  window.open (url,"",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');
  }
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

echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

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

echo "</TD><TD VALIGN=TOP> "._QXZ("Campaigns").":<BR>";
echo "<SELECT SIZE=5 NAME=group[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$group_string))
	{echo "<option value=\"--ALL--\" selected>-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
else
	{echo "<option value=\"--ALL--\">-- "._QXZ("ALL CAMPAIGNS")." --</option>\n";}
$o=0;
while ($campaigns_to_print > $o)
{
	if (preg_match("/$groups[$o]\|/i",$group_string)) {echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	  else {echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
}
echo "</SELECT>\n";
echo "</TD><TD VALIGN=TOP>";
echo _QXZ("Display as").":&nbsp;&nbsp;&nbsp;<BR>";
echo "<select name='report_display_type'>";
if ($report_display_type) {echo "<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
echo "<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
echo "</TD><TD VALIGN=TOP>"._QXZ("User").":<BR>";
echo "<INPUT TYPE=TEXT SIZE=10 NAME=user value=\"$user\">\n";
echo "</TD><TD VALIGN=TOP>"._QXZ("Shift").":<BR>";
echo "<SELECT SIZE=1 NAME=shift>\n";
echo "<option selected value=\"$shift\">"._QXZ("$shift")."</option>\n";
echo "<option value=\"\">--</option>\n";
echo "<option value=\"AM\">"._QXZ("AM")."</option>\n";
echo "<option value=\"PM\">"._QXZ("PM")."</option>\n";
echo "<option value=\"ALL\">"._QXZ("ALL")."</option>\n";
echo "</SELECT>\n";

if ($archives_available=="Y") 
	{
	echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

echo "<BR><BR><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
if (strlen($user) > 1)
	{
	echo " <a href=\"$LINKbase&stage=$stage&file_download=1\">"._QXZ("DOWNLOAD")."</a> | \n";
	echo " <a href=\"./admin.php?ADD=3&user=$user\">"._QXZ("USER")."</a> | \n";
	echo " <a href=\"./user_stats.php?user=$user&begin_date=$query_date&end_date=$end_date\">"._QXZ("USER STATS")."</a> | \n";
	}
else
	{echo " <a href=\"./admin.php?ADD=0A\">"._QXZ("USERS")."</a> | \n";}
echo "<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</TD></TR></TABLE>";

echo "</FORM>";

if ($report_display_type=="HTML")
	{
	echo $GRAPH_text;
	echo $JS_text;
	}
else
	{
	echo $ASCII_text;
	}


echo "</span>\n";

if ($report_display_type=="TEXT" || !$report_display_type) 
	{
	echo "<span style=\"position:absolute;left:3px;top:3px;z-index:18;\"  id=agent_status_bars>\n";
	echo "<PRE><FONT SIZE=2>\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";

	$m=0;
	$sort_order=array();
	while ($m < $k)
		{
		$sort_split = explode("-----",$TOPsort[$m]);
		$i = $sort_split[1];
		$sort_order[$m] = "$i";

		if ( ($TOPsortTALLY[$i] < 1) or ($TOPsortMAX < 1) )
			{echo "              \n";}
		else
			{
			echo "              <SPAN class=\"yellow\">";
			$TOPsortPLOT = ( MathZDC($TOPsortTALLY[$i], $TOPsortMAX) * 120 );
			$h=0;
			while ($h <= $TOPsortPLOT)
				{
				echo " ";
				$h++;
				}
			echo "</SPAN>\n";
			}
		$m++;
		}

	echo "</span>\n";
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

</BODY></HTML>
