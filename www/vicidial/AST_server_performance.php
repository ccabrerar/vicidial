<?php 
# AST_server_performance.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
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

$report_name = 'Server Performance Report';
$db_source = 'M';

#############################################
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

$begin_query_time = preg_replace('/[^- \:_0-9a-zA-Z]/', '', $begin_query_time);
$end_query_time = preg_replace('/[^- \:_0-9a-zA-Z]/', '', $end_query_time);
$group = preg_replace('/[^\._0-9a-zA-Z]/', '', $group);

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

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");

if (strlen($begin_query_time) < 10) {$begin_query_time = "$NOW_DATE 09:00:00";}
if (strlen($end_query_time) < 10) {$end_query_time = "$NOW_DATE 15:30:00";}
if (!isset($group)) {$group = '';}

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
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";

echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";
echo "</head>";

	$short_header=1;

	require("admin_header.php");

echo "<b>"._QXZ("$report_name")."</b> $NWB#serverperformance$NWE\n";

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";


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
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n";


if (!$group)
	{
	echo "\n";
	echo _QXZ("PLEASE SELECT A SERVER AND DATE/TIME RANGE ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$query_date_BEGIN = $begin_query_time;   
	$query_date_END = $end_query_time;


	echo _QXZ("Server Performance Report",53)." $NOW_TIME\n";

	echo _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
	echo "---------- "._QXZ("TOTALS, PEAKS and AVERAGES")."\n";

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

	$stmt="select count(*),SUM(length_in_min) from call_log where extension NOT IN('8365','8366','8367') and  start_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and start_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' and server_ip='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$TOTALcalls =	sprintf("%10s", $row[0]);
	$OFFHOOKtime =	sprintf("%10s", $row[1]);


	echo _QXZ("Total Calls in/out on this server:",42)."  $TOTALcalls\n";
	echo _QXZ("Total Off-Hook time on this server (min):",42)." $OFFHOOKtime\n";
	echo _QXZ("Average/Peak channels in use for server:",42)." $AVGchannels / $HIGHchannels\n";
	echo _QXZ("Average/Peak load for server:",42)." $AVGload / $HIGHload\n";
	echo _QXZ("Average USER process cpu percentage:",42)." $AVGcpuUSER %\n";
	echo _QXZ("Average SYSTEM process cpu percentage:",42)." $AVGcpuSYSTEM %\n";
	echo _QXZ("Average IDLE process cpu percentage:",42)." $AVGcpuIDLE %\n";

	echo "\n";
	echo "---------- "._QXZ("LINE GRAPH").":\n";



	##############################
	#########  Graph stats

	$DAT = '.dat';
	$HTM = '.htm';
	$PNG = '.png';
	$filedate = date("Y-m-d_His");
	$DATfile = "$group$query_date$shift$filedate$DAT";
	$HTMfile = "$group$query_date$shift$filedate$HTM";
	$PNGfile = "$group$query_date$shift$filedate$PNG";

	$HTMfp = fopen ("$DOCroot/$HTMfile", "a");
	$DATfp = fopen ("$DOCroot/$DATfile", "a");

	$stmt="select DATE_FORMAT(start_time,'%Y-%m-%d.%H:%i:%s') as timex,sysload,processes,channels_total,live_recordings,cpu_user_percent,cpu_system_percent from server_performance where server_ip='" . mysqli_real_escape_string($link, $group) . "' and start_time <= '" . mysqli_real_escape_string($link, $query_date_END) . "' and start_time >= '" . mysqli_real_escape_string($link, $query_date_BEGIN) . "' order by timex limit 99999;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		if ($i<1) {$time_BEGIN = $row[0];}
		$time_END = $row[0];
		$row[5] = intval(($row[5] + $row[6]) * $HIGHmulti);
		$row[6] = intval($row[6] * $HIGHmulti);
		if ($rows_to_print > 9999)
			{
			if ($rows_to_print <= 19999)
				{
				if (preg_match("/0$|2$|4$|6$|8$/",$i))
					{
					fwrite ($DATfp, "$row[5]\t$row[6]\t$row[0]\t$row[1]\t$row[2]\t$row[3]\n");
					}
				}
			if ( ($rows_to_print > 19999) and ($rows_to_print <= 49999) )
				{
				if (preg_match("/0$|5$/",$i))
					{
					fwrite ($DATfp, "$row[5]\t$row[6]\t$row[0]\t$row[1]\t$row[2]\t$row[3]\n");
					}
				}
			if ( ($rows_to_print > 49999) and ($rows_to_print <= 99999) )
				{
				if (preg_match("/0$/",$i))
					{
					fwrite ($DATfp, "$row[5]\t$row[6]\t$row[0]\t$row[1]\t$row[2]\t$row[3]\n");
					}
				}
			}
		else
			{
			fwrite ($DATfp, "$row[5]\t$row[6]\t$row[0]\t$row[1]\t$row[2]\t$row[3]\n");
			}
		$i++;
		}
	fclose($DATfp);

	$rows_to_max = ($rows_to_print + 100);

	$time_scale_abb = '5 '._QXZ("minutes");
	$time_scale_tick = '1 minute';
	if ($i > 1000) {$time_scale_abb = '10 '._QXZ("minutes");   $time_scale_tick = '2 '._QXZ("minutes");}
	if ($i > 1500) {$time_scale_abb = '15 '._QXZ("minutes");   $time_scale_tick = '3 '._QXZ("minutes");}
	if ($i > 2000) {$time_scale_abb = '20 '._QXZ("minutes");   $time_scale_tick = '4 '._QXZ("minutes");}
	if ($i > 3000) {$time_scale_abb = '30 '._QXZ("minutes");   $time_scale_tick = '5 '._QXZ("minutes");}
	if ($i > 4000) {$time_scale_abb = '40 '._QXZ("minutes");   $time_scale_tick = '10 '._QXZ("minutes");}
	if ($i > 5000) {$time_scale_abb = '60 '._QXZ("minutes");   $time_scale_tick = '15 '._QXZ("minutes");}
	if ($i > 6000) {$time_scale_abb = '90 '._QXZ("minutes");   $time_scale_tick = '15 '._QXZ("minutes");}
	if ($i > 7000) {$time_scale_abb = '120 '._QXZ("minutes");   $time_scale_tick = '30 '._QXZ("minutes");}

	print _QXZ("rows").": $i   "._QXZ("tick").": $time_scale_abb   "._QXZ("scale").": $time_scale_tick\n";

	$HTMcontent  = '';
	$HTMcontent .= "#proc page\n";
	$HTMcontent .= "#if @DEVICE in png,gif\n";
	$HTMcontent .= "   scale: 0.6\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#endif\n";
	$HTMcontent .= "#proc getdata\n";
	$HTMcontent .= "file: $DOCroot/$DATfile\n";
	$HTMcontent .= "fieldnames: userproc sysproc datetime load processes channels\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc areadef\n";
	$HTMcontent .= "title: Server $group   $query_date_BEGIN to $query_date_END\n";
	$HTMcontent .= "titledetails: size=14  align=C\n";
	$HTMcontent .= "rectangle: 1 1 12 7\n";
	$HTMcontent .= "xscaletype: datetime yyyy-mm-dd.hh:mm:ss\n";
	$HTMcontent .= "xrange: $time_BEGIN $time_END\n";
	$HTMcontent .= "yrange: 0 $HIGHlimit\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc xaxis\n";
	$HTMcontent .= "stubs: inc $time_scale_abb\n";
	$HTMcontent .= "minorticinc: $time_scale_tick\n";
	$HTMcontent .= "stubformat: hh:mma\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc yaxis\n";
	$HTMcontent .= "stubs: inc 50\n";
	$HTMcontent .= "grid: color=yellow\n";
	$HTMcontent .= "gridskip: min\n";
	$HTMcontent .= "ticincrement: 100 1000\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc lineplot\n";
	$HTMcontent .= "xfield: datetime\n";
	$HTMcontent .= "yfield: userproc\n";
	$HTMcontent .= "linedetails: color=purple width=.5\n";
	$HTMcontent .= "fill: lavender\n";
	$HTMcontent .= "legendlabel: user proc%\n";
	$HTMcontent .= "maxinpoints: $rows_to_max\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc lineplot\n";
	$HTMcontent .= "xfield: datetime\n";
	$HTMcontent .= "yfield: sysproc\n";
	$HTMcontent .= "linedetails: color=yelloworange width=.5\n";
	$HTMcontent .= "fill: dullyellow\n";
	$HTMcontent .= "legendlabel: system proc%\n";
	$HTMcontent .= "maxinpoints: $rows_to_max\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc curvefit\n";
	$HTMcontent .= "xfield: datetime\n";
	$HTMcontent .= "yfield: load\n";
	$HTMcontent .= "linedetails: color=blue width=.5\n";
	$HTMcontent .= "legendlabel: load\n";
	$HTMcontent .= "maxinpoints: $rows_to_max\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc curvefit\n";
	$HTMcontent .= "xfield: datetime\n";
	$HTMcontent .= "yfield: processes\n";
	$HTMcontent .= "linedetails: color=red width=.5\n";
	$HTMcontent .= "legendlabel: processes\n";
	$HTMcontent .= "maxinpoints: $rows_to_max\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc curvefit\n";
	$HTMcontent .= "xfield: datetime\n";
	$HTMcontent .= "yfield: channels\n";
	$HTMcontent .= "linedetails: color=green width=.5\n";
	$HTMcontent .= "legendlabel: channels\n";
	$HTMcontent .= "maxinpoints: $rows_to_max\n";
	$HTMcontent .= "\n";
	$HTMcontent .= "#proc legend\n";
	$HTMcontent .= "location: max-1 max\n";
	$HTMcontent .= "seglen: 0.2\n";
	$HTMcontent .= "\n";

	fwrite ($HTMfp, "$HTMcontent");
	fclose($HTMfp);

	if (file_exists("/usr/local/bin/pl"))
		{
		passthru("/usr/local/bin/pl -png $DOCroot/$HTMfile -o $DOCroot/$PNGfile");
		}
	else
		{
		if (file_exists("/usr/bin/pl"))
			{
			passthru("/usr/bin/pl -png $DOCroot/$HTMfile -o $DOCroot/$PNGfile");
			}
		else
			{
			if (file_exists("/usr/bin/ploticus"))
				{
				passthru("/usr/bin/ploticus -png $DOCroot/$HTMfile -o $DOCroot/$PNGfile");
				}
			else
				{
				echo "ERROR: ploticus not found\n";
				}
			}
		}
	sleep(1);

	echo "</PRE>";
	echo "\n";
	echo "<IMG SRC=\"/$PLOTroot/$PNGfile\">\n";

	#echo "<!-- /usr/local/bin/pl -png $DOCroot/$HTMfile -o $DOCroot/$PNGfile -->";
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

</TD></TR></TABLE>
<BR>
<?php echo "$db_source"; ?>
</BODY></HTML>
