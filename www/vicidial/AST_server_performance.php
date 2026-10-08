<?php 
# AST_server_performance.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60619-1732 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 70417-1106 - Changed time frame to be definable per time range on a single day
#            - Fixed vertical scaling issues
# 80118-1508 - Fixed horizontal scale marking issues
# 90310-2151 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 100214-1421 - Sort menu alphabetically
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 130414-0157 - Added report logging
# 130610-0959 - Finalized changing of all ereg instances to preg
# 130621-0726 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2012 - Changed to mysqli PHP functions
# 130926-0658 - Added check for several different ploticus bin paths
# 140108-0716 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0730 - Finalized adding QXZ translation to all admin files
# 141230-1440 - Added code for on-the-fly language translations display
# 170409-1550 - Added IP List validation code
# 170422-0750 - Added input variable filtering
# 180223-1541 - Fixed blank default date/time ranges
# 191013-0842 - Fixes for PHP7
# 220302-0841 - Added allow_web_debug system setting
# 251001-1700 - Switched graph to Graph.js from ploticus, converted to HTML
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["begin_query_time"]))			{$begin_query_time=$_GET["begin_query_time"];}
	elseif (isset($_POST["begin_query_time"]))	{$begin_query_time=$_POST["begin_query_time"];}
if (isset($_GET["end_query_time"]))				{$end_query_time=$_GET["end_query_time"];}
	elseif (isset($_POST["end_query_time"]))	{$end_query_time=$_POST["end_query_time"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (strlen($begin_query_time) < 10) {$begin_query_time = "$NOW_DATE 09:00:00";}
if (strlen($end_query_time) < 10) {$end_query_time = "$NOW_DATE 15:30:00";}
if (!isset($group)) {$group = '';}

$report_name = 'Server Performance Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
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
	$SSallow_web_debug =			$row[6];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$begin_query_time = preg_replace('/[^- \:_0-9a-zA-Z]/', '', $begin_query_time);
$end_query_time = preg_replace('/[^- \:_0-9a-zA-Z]/', '', $end_query_time);
$group = preg_replace('/[^- \.\:\_0-9a-zA-Z]/', '', $group);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
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
$LOGbrowser=preg_replace("/<|>|\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/<|>|\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/<|>|\'|\"|\\\\/","",$LOGhttp_referer);
if (preg_match("/443/i",$LOGserver_port)) 
	{$HTTPprotocol = 'https://';}
else 
	{$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) 
	{$LOGserver_port='';}
else 
	{$LOGserver_port = ":$LOGserver_port";}
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group, $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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

# path from root to where ploticus files will be stored
$PLOTroot = "vicidial/ploticus";
$DOCroot = "$WeBServeRRooT/$PLOTroot/";

$stmt="select server_ip from servers order by server_ip;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$servers_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $servers_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	$i++;
	}

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

require("screen_colors.php");

?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}
-->
 </STYLE>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0></TITLE>\n";

require("chart_button.php");
# echo "<script type=\"text/javascript\" src='chart/Chart.js'></script>\n"; 
echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.bundle.js\"></script>";
echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.bundle.min.js\"></script>";
echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.js\"></script>";
echo "<script src=\"https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.6.0/Chart.min.js\"></script>";
echo "<script type=\"text/javascript\" src='chart/xlsx.full.min.js'></script>\n"; 
echo "<script src=\"https://cdn.jsdelivr.net/npm/chartjs-plugin-datasource\"></script>\n";

echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "</head>";

	$short_header=1;

	require("admin_header.php");

echo "<b>"._QXZ("$report_name")."</b> $NWB#serverperformance$NWE\n";

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD colspan='2' align='left'><font class='standard'>";


echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo _QXZ("Date/Time Range").": <INPUT TYPE=TEXT NAME=begin_query_time SIZE=22 MAXLENGTH=19 VALUE=\"$begin_query_time\"> \n";
echo _QXZ("to")." <INPUT TYPE=TEXT NAME=end_query_time SIZE=22 MAXLENGTH=19 VALUE=\"$end_query_time\"> \n";
echo _QXZ("Server").": <SELECT SIZE=1 NAME=group>\n";
$o=0;
while ($servers_to_print > $o)
	{
	if ($groups[$o] == $group) 
		{echo "<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
	else 
		{echo "<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
	$o++;
	}
echo "</SELECT> \n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>\n";
echo "</FORM>\n\n";
echo "<BR></font></TD></TR>";

if (!$group)
	{
	echo "<tr><td colspan='2' align='left'><font class='standard'>"._QXZ("PLEASE SELECT A SERVER AND DATE/TIME RANGE ABOVE AND CLICK SUBMIT")."</td></tr>\n";
	}

else
	{
	$query_date_BEGIN = $begin_query_time;   
	$query_date_END = $end_query_time;

	echo "<tr bgcolor='#".$SSstd_row1_background."'><td align='right' width='280'><font class='standard'>"._QXZ("Server Performance Report").": </font></td><td align='left'><font class='standard_bold'>$NOW_TIME</font></td></tr>\n";

	echo "<tr bgcolor='#".$SSstd_row1_background."'><td align='right' width='280'><font class='standard'>"._QXZ("Time range").": </font></td><td align='left'><font class='standard_bold'>$query_date_BEGIN "._QXZ("to")." $query_date_END</font></td></tr>\n\n";
	echo "<tr><td colspan='2' align='center'><font class='standard_bold'>"._QXZ("TOTALS, PEAKS and AVERAGES")."</font></td></tr>\n";

	$stmt="select AVG(sysload),AVG(channels_total),MAX(sysload),MAX(channels_total),MAX(processes) from server_performance where start_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and start_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and server_ip='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$AVGload =	sprintf("%10s", $row[0]);
	$AVGchannels =	sprintf("%10s", $row[1]);
	$HIGHload =	$row[2];
		$HIGHmulti = intval(MathZDC($HIGHload, 100));
	$HIGHchannels =	$row[3];
	$HIGHprocesses =$row[4];
	if ($row[2] > $row[3]) {$HIGHlimit = $row[2];}
	else {$HIGHlimit = $row[3];}
	if ($HIGHlimit < $row[4]) {$HIGHlimit = $row[4];}

	$stmt="select AVG(cpu_user_percent),AVG(cpu_system_percent),AVG(cpu_idle_percent) from server_performance where start_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and start_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and server_ip='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$AVGcpuUSER =	sprintf("%10s", $row[0]);
	$AVGcpuSYSTEM =	sprintf("%10s", $row[1]);
	$AVGcpuIDLE =	sprintf("%10s", $row[2]);

	$stmt="select count(*),if(SUM(length_in_min) is null, 0, SUM(length_in_min)) from call_log where extension NOT IN('8365','8366','8367') and  start_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and start_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and server_ip='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$TOTALcalls =	sprintf("%10s", $row[0]);
	$OFFHOOKtime =	sprintf("%10s", $row[1]);


	echo "<tr bgcolor='#".$SSstd_row2_background."'><td align='left' width='280'><font class='standard'>"._QXZ("Total Calls in/out on this server:")."</font></td><td align='left'><font class='standard_bold'>$TOTALcalls</font></td></tr>\n";
	echo "<tr bgcolor='#".$SSstd_row3_background."'><td align='left' width='280'><font class='standard'>"._QXZ("Total Off-Hook time on this server (min):")."</font></td><td align='left'><font class='standard_bold'>$OFFHOOKtime</font></td></tr>\n";
	echo "<tr bgcolor='#".$SSstd_row2_background."'><td align='left' width='280'><font class='standard'>"._QXZ("Average/Peak channels in use for server:")."</font></td><td align='left'><font class='standard_bold'>$AVGchannels / $HIGHchannels</font></td></tr>\n";
	echo "<tr bgcolor='#".$SSstd_row3_background."'><td align='left' width='280'><font class='standard'>"._QXZ("Average/Peak load for server:")."</font></td><td align='left'><font class='standard_bold'>$AVGload / $HIGHload</font></td></tr>\n";
	echo "<tr bgcolor='#".$SSstd_row2_background."'><td align='left' width='280'><font class='standard'>"._QXZ("Average USER process cpu percentage:")."</font></td><td align='left'><font class='standard_bold'>$AVGcpuUSER %</font></td></tr>\n";
	echo "<tr bgcolor='#".$SSstd_row3_background."'><td align='left' width='280'><font class='standard'>"._QXZ("Average SYSTEM process cpu percentage:")."</font></td><td align='left'><font class='standard_bold'>$AVGcpuSYSTEM %</font></td></tr>\n";
	echo "<tr bgcolor='#".$SSstd_row2_background."'><td align='left' width='280'><font class='standard'>"._QXZ("Average IDLE process cpu percentage:")."</font></td><td align='left'><font class='standard_bold'>$AVGcpuIDLE %</font></td></tr>\n";

	$stmt="select DATE_FORMAT(start_time,'%Y-%m-%d %H:%i:%s') as timex,sysload,processes,channels_total,live_recordings,cpu_user_percent,cpu_system_percent, start_time from server_performance where server_ip='" . mysqli_real_escape_string($link, $group) . "' and start_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and start_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' order by timex limit 99999;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$i=0;

	$linewidth=1; $radius=1; $mod=1;
	switch (true)
		{
		case ($rows_to_print <= 50):
			$linewidth=3;
			$radius=3;
		case ($rows_to_print <= 100):
			$linewidth=2;
			$radius=2;
		case ($rows_to_print <= 9999):
			$mod=1;
			break;
		case ($rows_to_print <= 19999):
			$mod=2;
			break;
		case ($rows_to_print <= 49999):
			$mod=5;
			break;
		case ($rows_to_print <= 99999):
			$mod=10;
			break;
		}
	
	$labels="\t\tlabels: [";
	$datasets="\t\tdatasets: [\n";

	$SYSLOAD_dataset.="\t\t\t{\n";
	$SYSLOAD_dataset.="\t\t\t\tlabel: \"System load\",\n";
	$SYSLOAD_dataset.="\t\t\t\tradius: $radius,\n";
	$SYSLOAD_dataset.="\t\t\t\tborderWidth: \"$linewidth\",\n";
	$SYSLOAD_dataset.="\t\t\t\tborderColor: \"#FF0000\",\n";
	$SYSLOAD_dataset.="\t\t\t\tbackgroundColor: \"#FF0000\",\n";
	$SYSLOAD_dataset.="\t\t\t\tfill: false,\n";
	$SYSLOAD_dataset.="\t\t\t\tdata: [";

	$PROCESSES_dataset.="\t\t\t{\n";
	$PROCESSES_dataset.="\t\t\t\tlabel: \"Processes\",\n";
	$PROCESSES_dataset.="\t\t\t\tradius: $radius,\n";
	$PROCESSES_dataset.="\t\t\t\tborderWidth: \"$linewidth\",\n";
	$PROCESSES_dataset.="\t\t\t\tfill: false,\n";
	$PROCESSES_dataset.="\t\t\t\tborderColor: \"#0000FF\",\n";
	$PROCESSES_dataset.="\t\t\t\tbackgroundColor: \"#0000FF\",\n";
	$PROCESSES_dataset.="\t\t\t\tdata: [";

	$CHANNELS_dataset.="\t\t\t{\n";
	$CHANNELS_dataset.="\t\t\t\tlabel: \"Channels\",\n";
	$CHANNELS_dataset.="\t\t\t\tradius: $radius,\n";
	$CHANNELS_dataset.="\t\t\t\tborderWidth: \"$linewidth\",\n";
	$CHANNELS_dataset.="\t\t\t\tfill: false,\n";
	$CHANNELS_dataset.="\t\t\t\tborderColor: \"#FF9900\",\n";
	$CHANNELS_dataset.="\t\t\t\tbackgroundColor: \"#FF9900\",\n";
	$CHANNELS_dataset.="\t\t\t\tdata: [";

	$CPU_USER_dataset.="\t\t\t{\n";
	$CPU_USER_dataset.="\t\t\t\tlabel: \"CPU - User\",\n";
	$CPU_USER_dataset.="\t\t\t\tradius: $radius,\n";
	$CPU_USER_dataset.="\t\t\t\tborderWidth: \"$linewidth\",\n";
	$CPU_USER_dataset.="\t\t\t\tfill: false,\n";
	$CPU_USER_dataset.="\t\t\t\tborderColor: \"#00FF00\",\n";
	$CPU_USER_dataset.="\t\t\t\tbackgroundColor: \"#00FF00\",\n";
	$CPU_USER_dataset.="\t\t\t\tdata: [\n";

	$CPU_SYS_dataset.="\t\t\t{\n";
	$CPU_SYS_dataset.="\t\t\t\tlabel: \"CPU - System\",\n";
	$CPU_SYS_dataset.="\t\t\t\tradius: $radius,\n";
	$CPU_SYS_dataset.="\t\t\t\tborderWidth: \"$linewidth\",\n";
	$CPU_SYS_dataset.="\t\t\t\tfill: false,\n";
	$CPU_SYS_dataset.="\t\t\t\tborderColor: \"#FF00FF\",\n";
	$CPU_SYS_dataset.="\t\t\t\tbackgroundColor: \"#FF00FF\",\n";
	$CPU_SYS_dataset.="\t\t\t\tdata: [";

	while ($i < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		if ($i<1) {$time_BEGIN = $row[0];}
		$time_END = $row[0];
		$row[5] = intval(($row[5] + $row[6]) * $HIGHmulti);
		$row[6] = intval($row[6] * $HIGHmulti);

		if ($i%$mod==0)
			{
			$labels.="\"$row[0]\",";
			$SYSLOAD_dataset.="\"$row[1]\",";
			$PROCESSES_dataset.="\"$row[2]\",";
			$CHANNELS_dataset.="\"$row[3]\",";
			$CPU_USER_dataset.="\"$row[5]\",";
			$CPU_SYS_dataset.="\"$row[6]\",";
			}
		$i++;	
		}

	$labels=preg_replace('/,$/', "", $labels)."],\n";
	$SYSLOAD_dataset=preg_replace('/,$/', "", $SYSLOAD_dataset)."]\n";
	$PROCESSES_dataset=preg_replace('/,$/', "", $PROCESSES_dataset)."]\n";
	$CHANNELS_dataset=preg_replace('/,$/', "", $CHANNELS_dataset)."]\n";
	$CPU_USER_dataset=preg_replace('/,$/', "", $CPU_USER_dataset)."]\n";
	$CPU_SYS_dataset=preg_replace('/,$/', "", $CPU_SYS_dataset)."]\n";

	$SYSLOAD_dataset.="\t\t\t}\n";
	$PROCESSES_dataset.="\t\t\t}\n";
	$CHANNELS_dataset.="\t\t\t}\n";
	$CPU_USER_dataset.="\t\t\t}\n";
	$CPU_SYS_dataset.="\t\t\t}\n";
?>

<tr>
<td colspan='2'><BR>
<canvas id="performanceChart"></canvas>
<script language="Javascript">
var pf_ctx = document.getElementById("performanceChart");
var ctx = document.getElementById('performanceChart').getContext('2d');
var performance_data = {
<?php
echo $labels;
echo "\t\t\tdatasets: [\n";
echo $SYSLOAD_dataset.",";
echo $PROCESSES_dataset.",";
echo $CHANNELS_dataset.",";
echo $CPU_USER_dataset.",";
echo $CPU_SYS_dataset;
echo "\t\t\t]\n";
?>
}

var performance_chart = new Chart(pf_ctx, {type: 'line', options: { scales: { xAxes: [{ ticks: {maxRotation: 82, minRotation: 45}}] } }, data: performance_data});

    
</script>
</td>
<tr><td colspan='2'>

<?php

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

echo "<BR><BR><font class='standard'>Total run time: </font><font class='standard_bold'>$TOTALrun seconds</font>";

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

?>

</TD></TR></TABLE>
<?php 
echo "<BR><BR><font class='standard'>$db_source</font>";
}
?>

</BODY></HTML>
