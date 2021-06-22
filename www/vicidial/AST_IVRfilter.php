<?php 
# AST_IVRfilter.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 81030-0432 - First build
# 90310-2054 - Admin header
# 90508-0644 - Changed to PHP long tags
# 120113-2022 - Added new columns for sent to queue and agent
# 130414-0257 - Added report logging
# 130610-1007 - Finalized changing of all ereg instances to preg
# 130621-0741 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130704-0939 - Fixed issue #675
# 130901-0820 - Changed to mysqli PHP functions
# 140108-0738 - Added webserver and hostname to report logging
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0833 - Finalized adding QXZ translation to all admin files
# 141230-1449 - Added code for on-the-fly language translations display
# 160227-1931 - Uniform form format
# 170409-1538 - Added IP List validation code
# 180507-2315 - Added new help display
#

$startMS = microtime();

$report_name='IVR Filter Report';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_dateY"]))				{$query_dateY=$_GET["query_dateY"];}
	elseif (isset($_POST["query_dateY"]))	{$query_dateY=$_POST["query_dateY"];}
if (isset($_GET["query_dateM"]))				{$query_dateM=$_GET["query_dateM"];}
	elseif (isset($_POST["query_dateM"]))	{$query_dateM=$_POST["query_dateM"];}
if (isset($_GET["query_dateD"]))				{$query_dateD=$_GET["query_dateD"];}
	elseif (isset($_POST["query_dateD"]))	{$query_dateD=$_POST["query_dateD"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["end_dateY"]))				{$end_dateY=$_GET["end_dateY"];}
	elseif (isset($_POST["end_dateY"]))	{$end_dateY=$_POST["end_dateY"];}
if (isset($_GET["end_dateM"]))				{$end_dateM=$_GET["end_dateM"];}
	elseif (isset($_POST["end_dateM"]))	{$end_dateM=$_POST["end_dateM"];}
if (isset($_GET["end_dateD"]))				{$end_dateD=$_GET["end_dateD"];}
	elseif (isset($_POST["end_dateD"]))	{$end_dateD=$_POST["end_dateD"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["hourly_breakdown"]))				{$hourly_breakdown=$_GET["hourly_breakdown"];}
	elseif (isset($_POST["hourly_breakdown"]))	{$hourly_breakdown=$_POST["hourly_breakdown"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}

if ($hourly_breakdown) 
	{
	$date_int=3600;
	$substr_place=13;
	$checked="checked";
	} 
else 
	{
	$date_int=86400;
	$substr_place=10;
	$checked="";
	}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_header.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
$i=0;
while ($i < $qm_conf_ct)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$i++;
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$campaign[0], $query_date, $end_date|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$i=0;



# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HTML_header.="<HTML>\n";
$HTML_header.="<HEAD>\n";
$HTML_header.="<STYLE type=\"text/css\">\n";
$HTML_header.="<!--\n";
$HTML_header.="   .green {color: white; background-color: green}\n";
$HTML_header.="   .red {color: white; background-color: red}\n";
$HTML_header.="   .blue {color: white; background-color: blue}\n";
$HTML_header.="   .purple {color: white; background-color: purple}\n";
$HTML_header.="-->\n";
$HTML_header.=" </STYLE>\n";

$HTML_header.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_header.="<TITLE>"._QXZ("VICIDIAL: VDL IVR Filter Stats")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
$HTML_header.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_header.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";

$HTML_header.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_header.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_header.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;

#	require("admin_header.php");

$HTML_text="<b>"._QXZ("$report_name")."</b> $NWB#IVRfilter$NWE\n";
$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" NAME='IVRfilter_report' METHOD=GET>\n";
$HTML_text.="<TABLE BORDER=0 CELLPADDING=3 CELLSPACING=0 BGCOLOR=\"#e3e3ff\"><TR><TD VALIGN=TOP>\n";
$HTML_text.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$HTML_text.=_QXZ("Date Range").":<BR>\n";
$HTML_text.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">\n";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="  {\n";
$HTML_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="  }\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'IVRfilter_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'query_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.=" "._QXZ("to")." <INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">\n";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="var o_cal = new tcal ({\n";
$HTML_text.="	// form name\n";
$HTML_text.="	'formname': 'IVRfilter_report',\n";
$HTML_text.="	// input name\n";
$HTML_text.="	'controlname': 'end_date'\n";
$HTML_text.="});\n";
$HTML_text.="o_cal.a_tpl.yearscroll = false;\n";
$HTML_text.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$HTML_text.="</script>\n";

$HTML_text.="</TD><TD ROWSPAN=2 VALIGN=TOP><input type='checkbox' name='hourly_breakdown' value='1' $checked>"._QXZ("Break into hours")."\n";
$HTML_text.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
$HTML_text.="</TD><TD ROWSPAN=2 VALIGN=TOP>\n";
$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
$HTML_text.="<a href=\"./admin.php?ADD=1000&group_id=$group[0]\">"._QXZ("ADMIN")."</a> | ";
$HTML_text.="<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a>\n";
$HTML_text.="</FONT>\n";

$HTML_text.="</TD></TR>\n";
$HTML_text.="<TR><TD>\n";

$HTML_text.=" &nbsp; <INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$HTML_text.="</TD></TR></TABLE>\n";
$HTML_text.="</FORM>\n\n";

$HTML_text.="<PRE><FONT SIZE=2>\n\n";

$shift = 'ALL';

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



$HTML_text.=_QXZ("VDL: IVR Filter Stats").":           $NOW_TIME   <a href=\"$PHP_SELF?query_date=$query_date&end_date=$end_date&hourly_breakdown=$hourly_breakdown&DB=$DB&SUBMIT=$SUBMIT&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n";

$CSV_header="\""._QXZ("VDL: IVR Filter Stats").":           $NOW_TIME\"\n";

$start_date=mktime(substr($time_BEGIN,0,2), substr($time_BEGIN,3,2), substr($time_BEGIN,6,2), substr($query_date_BEGIN,5,2), substr($query_date_BEGIN,8,2), substr($query_date_BEGIN,0,4));
$end_date=mktime(substr($time_END,0,2), substr($time_END,3,2), substr($time_END,6,2), substr($query_date_END,5,2), substr($query_date_END,8,2), substr($query_date_END,0,4));

$i=0;
while($start_date<$end_date) {
	$start_date_ary[$i]=date("Y-m-d H:i:s", $start_date);
	$end_date_ary[$i]=date("Y-m-d H:i:s", ($start_date+($date_int-1)) );
	for ($j=0; $j<16; $j++) {
		$count_ary[substr($start_date_ary[$i],0,$substr_place)][$j]=0;
	}
	
	$i++;
	$start_date+=$date_int;
}

$total=0;
$Utotal=0;
$totalstq=0;
$Utotalstq=0;
$totalnocid=0;
$Utotalnocid=0;
$totaldnc=0;
$Utotaldnc=0;
$totalsale=0;
$Utotalsale=0;
$totalarch=0;
$Utotalarch=0;
$totaltiq=0;
$Utotaltiq=0;
$totalsta=0;
$Utotalsta=0;

	$stmtA="select count(*),count(distinct caller_id), substr(start_time,1,$substr_place) as stime from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a='INBOUND_IVR_FILTER' and comment_b='CLEAN' group by stime";
	$rsltA=mysql_to_mysqli($stmtA, $link);
	if ($DB) {$HTML_text.="$stmtA\n";}
	while ($rowA=mysqli_fetch_row($rsltA)) {
		$count_ary[$rowA[2]][0]+=$rowA[0];
		$count_ary[$rowA[2]][1]+=$rowA[1];
		$count_ary[$rowA[2]][14]+=$rowA[0];
		$count_ary[$rowA[2]][15]+=$rowA[1];
		$total+=$rowA[0];
		$Utotal+=$rowA[1];
		$totalstq+=$rowA[0];
		$Utotalstq+=$rowA[1];
	}

	$stmtB="select count(*),count(distinct caller_id), substr(start_time,1,$substr_place) as stime from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a='INBOUND_IVR_FILTER' and comment_b='NOT_FOUND' group by stime;";
	$rsltB=mysql_to_mysqli($stmtB, $link);
	if ($DB) {$HTML_text.="$stmtB\n";}
	while ($rowB=mysqli_fetch_row($rsltB)) {
		$count_ary[$rowB[2]][2]+=$rowB[0];
		$count_ary[$rowB[2]][3]+=$rowB[1];
		$total+=$rowB[0];
		$Utotal+=$rowB[1];
		$count_ary[$rowB[2]][14]+=$rowB[0];
		$count_ary[$rowB[2]][15]+=$rowB[1];
		$totalnocid+=$rowB[0];
		$Utotalnocid+=$rowB[1];
	}

	$stmtC="select count(*),count(distinct caller_id), substr(start_time,1,$substr_place) as stime from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a='INBOUND_IVR_FILTER' and comment_b='EXISTING' and comment_c='DNC' group by stime;";
	$rsltC=mysql_to_mysqli($stmtC, $link);
	if ($DB) {$HTML_text.="$stmtC\n";}
	while ($rowC=mysqli_fetch_row($rsltC)) {
		$count_ary[$rowC[2]][4]+=$rowC[0];
		$count_ary[$rowC[2]][5]+=$rowC[1];
		$total+=$rowC[0];
		$Utotal+=$rowC[1];
		$count_ary[$rowC[2]][14]+=$rowC[0];
		$count_ary[$rowC[2]][15]+=$rowC[1];
		$totaldnc+=$rowC[0];
		$Utotaldnc+=$rowC[1];
	}

	$stmtD="select count(*),count(distinct caller_id), substr(start_time,1,$substr_place) as stime from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a='INBOUND_IVR_FILTER' and comment_b='EXISTING' and comment_c='SALE' group by stime;";
	$rsltD=mysql_to_mysqli($stmtD, $link);
	if ($DB) {$HTML_text.="$stmtD\n";}
	while ($rowD=mysqli_fetch_row($rsltD)) {
		$count_ary[$rowD[2]][6]+=$rowD[0];
		$count_ary[$rowD[2]][7]+=$rowD[1];
		$total+=$rowD[0];
		$Utotal+=$rowD[1];
		$count_ary[$rowD[2]][14]+=$rowD[0];
		$count_ary[$rowD[2]][15]+=$rowD[1];
		$totalsale+=$rowD[0];
		$Utotalsale+=$rowD[1];
	}

	$stmtE="select count(*),count(distinct caller_id), substr(start_time,1,$substr_place) as stime from live_inbound_log where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a='INBOUND_IVR_FILTER' and comment_b='EXISTING' and comment_c='ARCHIVE' group by stime;";
	$rsltE=mysql_to_mysqli($stmtE, $link);
	if ($DB) {$HTML_text.="$stmtE\n";}
	while ($rowE=mysqli_fetch_row($rsltE)) {
		$count_ary[$rowE[2]][8]+=$rowE[0];
		$count_ary[$rowE[2]][9]+=$rowE[1];
		$total+=$rowE[0];
		$Utotal+=$rowE[1];
		$count_ary[$rowE[2]][14]+=$rowE[0];
		$count_ary[$rowE[2]][15]+=$rowE[1];
		$totalarch+=$rowE[0];
		$Utotalarch+=$rowE[1];
	}

	$stmtF="select count(*),count(distinct caller_id), substr(start_time,1,$substr_place) as stime From vicidial_closer_log vc, live_inbound_log l where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a='INBOUND_IVR_FILTER' and comment_b='CLEAN' and (vc.xfercallid is null or vc.xfercallid='0') and vc.uniqueid=l.uniqueid group by stime;";
	$rsltF=mysql_to_mysqli($stmtF, $link);
	if ($DB) {$HTML_text.="$stmtF\n";}
	while ($rowF=mysqli_fetch_row($rsltF)) {
		$count_ary[$rowF[2]][10]+=$rowF[0];
		$count_ary[$rowF[2]][11]+=$rowF[1];
		$totaltiq+=$rowF[0];
		$Utotaltiq+=$rowF[1];
	}

	$stmtG="select count(*),count(distinct caller_id), substr(start_time,1,$substr_place) as stime From vicidial_closer_log vc, live_inbound_log l where start_time >= '$query_date_BEGIN' and start_time <= '$query_date_END' and comment_a='INBOUND_IVR_FILTER' and comment_b='CLEAN' and (vc.xfercallid is null or vc.xfercallid='0') and vc.uniqueid=l.uniqueid and vc.user!='VDCL' group by stime;";
	$rsltG=mysql_to_mysqli($stmtG, $link);
	if ($DB) {$HTML_text.="$stmtG\n";}
	while ($rowG=mysqli_fetch_row($rsltG)) {
		$count_ary[$rowG[2]][12]+=$rowG[0];
		$count_ary[$rowG[2]][13]+=$rowG[1];
		$totalsta+=$rowG[0];
		$Utotalsta+=$rowG[1];
	}


	$HTML_text.="\n";
	$HTML_text.="+------------------------------+-------------------------+-----------------------------------------------------+-------------------------+-------------------------+-------------------------+-------------------------+\n";
	$HTML_text.="|                              | "._QXZ("TOTALS",23)." | "._QXZ("SENT TO QUEUE",51)." | "._QXZ("CALLERID NOT FOUND",23)." | "._QXZ("PREVIOUS DNC",23)." | "._QXZ("PREVIOUS SALE",23)." | "._QXZ("ARCHIVE ONLY",23)." |\n";
	$HTML_text.="|                              +--------+--------+-------+----------------------+----------------------+-------+--------+--------+-------+--------+--------+-------+--------+--------+-------+--------+--------+-------+\n";
	$HTML_text.="| "._QXZ("HOURS",28)." | "._QXZ("CALLS",6)." | "._QXZ("UNIQUE",6)." |     % | "._QXZ("CALLS**",20)." | "._QXZ("UNIQUE**",20)." |     % | "._QXZ("CALLS",6)." | "._QXZ("UNIQUE",6)." |     % | "._QXZ("CALLS",6)." | "._QXZ("UNIQUE",6)." |     % | "._QXZ("CALLS",6)." | "._QXZ("UNIQUE",6)." |     % | "._QXZ("CALLS",6)." | "._QXZ("UNIQUE",6)." |     % |\n";
	$HTML_text.="+------------------------------+--------+--------+-------+----------------------+----------------------+-------+--------+--------+-------+--------+--------+-------+--------+--------+-------+--------+--------+-------+\n";
	
	$CSV_text.="\n";
	$CSV_text.="\"\",\"\",\""._QXZ("TOTALS")."\",\"\",\"\",\""._QXZ("SENT TO QUEUE")."\",\"\",\"\",\""._QXZ("CALLERID NOT FOUND")."\",\"\",\"\",\""._QXZ("REVIOUS DNC")."\",\"\",\"\",\""._QXZ("PREVIOUS SALE")."\",\"\",\"\",\""._QXZ("ARCHIVE ONLY")."\",\n";
	$CSV_text.="\""._QXZ("HOURS")."\",\""._QXZ("CALLS")."\",\""._QXZ("UNIQUE")."\",\"%\",\""._QXZ("CALLS**")."\",\""._QXZ("UNIQUE**")."\",\"%\",\""._QXZ("CALLS*")."\",\""._QXZ("UNIQUE**")."\",\"%\",\""._QXZ("CALLS*")."\",\""._QXZ("UNIQUE**")."\",\"%\",\""._QXZ("CALLS*")."\",\""._QXZ("UNIQUE**")."\",\"%\",\""._QXZ("CALLS*")."\",\""._QXZ("UNIQUE**")."\",\"%\",\n";

	for($i=0; $i<count($start_date_ary); $i++) {
		$key=substr($start_date_ary[$i],0,$substr_place);
		$queue_str=$count_ary[$key][0]." (".$count_ary[$key][10]."|".$count_ary[$key][12].")";
		$Uqueue_str=$count_ary[$key][1]." (".$count_ary[$key][11]."|".$count_ary[$key][13].")";

		$HTML_text.="| $start_date_ary[$i]-".substr($end_date_ary[$i],11,8)." | ";
		$HTML_text.=sprintf("%6s", $count_ary[$key][14])." | ";
		$HTML_text.=sprintf("%6s", $count_ary[$key][15])." |";
		$HTML_text.=sprintf("%6s", "n/a")." | ";


		$HTML_text.=sprintf("%20s", $queue_str)." | ";
		$HTML_text.=sprintf("%20s", $Uqueue_str)." |";
		$UtotalPERCENT = (MathZDC($count_ary[$key][1], $count_ary[$key][15]) * 100);
		$UtotalPERCENT = round($UtotalPERCENT, 2);
		$UtotalPERCENT =	sprintf("%6s", $UtotalPERCENT);
		$HTML_text.=$UtotalPERCENT." | ";

		$HTML_text.=sprintf("%6s", $count_ary[$key][2])." | ";
		$HTML_text.=sprintf("%6s", $count_ary[$key][3])." |";
		$UagentPERCENT = (MathZDC($count_ary[$key][3], $count_ary[$key][15]) * 100);
		$UagentPERCENT = round($UagentPERCENT, 2);
		$UagentPERCENT =	sprintf("%6s", $UagentPERCENT);
		$HTML_text.=$UagentPERCENT." | ";

		$HTML_text.=sprintf("%6s", $count_ary[$key][4])." | ";
		$HTML_text.=sprintf("%6s", $count_ary[$key][5])." |";
		$UntfndPERCENT = (MathZDC($count_ary[$key][5], $count_ary[$key][15]) * 100);
		$UntfndPERCENT = round($UntfndPERCENT, 2);
		$UntfndPERCENT =	sprintf("%6s", $UntfndPERCENT);
		$HTML_text.=$UntfndPERCENT." | ";

		$HTML_text.=sprintf("%6s", $count_ary[$key][6])." | ";
		$HTML_text.=sprintf("%6s", $count_ary[$key][7])." |";
		$UprdncPERCENT = (MathZDC($count_ary[$key][7], $count_ary[$key][15]) * 100);
		$UprdncPERCENT = round($UprdncPERCENT, 2);
		$UprdncPERCENT =	sprintf("%6s", $UprdncPERCENT);
		$HTML_text.=$UprdncPERCENT." | ";

		$HTML_text.=sprintf("%6s", $count_ary[$key][8])." | ";
		$HTML_text.=sprintf("%6s", $count_ary[$key][9])." |";
		$UpsalePERCENT = (MathZDC($count_ary[$key][9], $count_ary[$key][15]) * 100);
		$UpsalePERCENT = round($UpsalePERCENT, 2);
		$UpsalePERCENT =	sprintf("%6s", $UpsalePERCENT);
		$HTML_text.=$UpsalePERCENT." |\n";

		$CSV_text.="\"$start_date_ary[$i]-".substr($end_date_ary[$i],11,8)."\",\"".$count_ary[$key][14]."\",\"".$count_ary[$key][15]."\",\"n/a\",\"$queue_str\",\"$Uqueue_str\",\"$UtotalPERCENT\",\"".$count_ary[$key][2]."\",\"".$count_ary[$key][3]."\",\"$UagentPERCENT\",\"".$count_ary[$key][4]."\",\"".$count_ary[$key][5]."\",\"$UntfndPERCENT\",\"".$count_ary[$key][6]."\",\"".$count_ary[$key][7]."\",\"$UprdncPERCENT\",\"".$count_ary[$key][8]."\",\"".$count_ary[$key][9]."\",\"$UpsalePERCENT\",\n";
	
	}

	$HTML_text.="+------------------------------+--------+--------+-------+----------------------+----------------------+-------+--------+--------+-------+--------+--------+-------+--------+--------+-------+--------+--------+-------+\n";
	$HTML_text.="| "._QXZ("TOTALS",28,"r")." | ";
	$totalstq+=$rowA[0];
	$Utotalstq+=$rowA[1];

#	$CSV_text.="\"TOTALS\",\"$total\",\"$Utotal\",\"n/a\",\"$queue_str\",\"$Uqueue_str\",\"$UtotalPERCENT\",\"".$count_ary[$key][2]."\",\"".$count_ary[$key][3]."\",\"$UagentPERCENT\",\"".$count_ary[$key][4]."\",\"".$count_ary[$key][5]."\",\"$UntfndPERCENT\",\"".$count_ary[$key][6]."\",\"".$count_ary[$key][7]."\",\"$UprdncPERCENT\",\"".$count_ary[$key][8]."\",\"".$count_ary[$key][9]."\",\"$UpsalePERCENT\"\n";


	$HTML_text.=sprintf("%6s", $total)." | ";
	$HTML_text.=sprintf("%6s", $Utotal)." |";
	$HTML_text.=sprintf("%6s", "n/a")." | ";

	$queue_str=$totalstq." (".$totaltiq."|".$totalsta.")";
	$Uqueue_str=$Utotalstq." (".$Utotaltiq."|".$Utotalsta.")";

	$HTML_text.=sprintf("%20s", $queue_str)." | ";
	$HTML_text.=sprintf("%20s", $Uqueue_str)." |";
	$UtotalPERCENT = (MathZDC($Utotalstq, $Utotal) * 100);
	$UtotalPERCENT = round($UtotalPERCENT, 2);
	$UtotalPERCENT =	sprintf("%6s", $UtotalPERCENT);
	$HTML_text.=$UtotalPERCENT." | ";

	$HTML_text.=sprintf("%6s", $totalnocid)." | ";
	$HTML_text.=sprintf("%6s", $Utotalnocid)." |";
	$UnocidPERCENT = (MathZDC($Utotalnocid, $Utotal) * 100);
	$UnocidPERCENT = round($UnocidPERCENT, 2);
	$UnocidPERCENT =	sprintf("%6s", $UnocidPERCENT);
	$HTML_text.=$UnocidPERCENT." | ";

	$HTML_text.=sprintf("%6s", $totaldnc)." | ";
	$HTML_text.=sprintf("%6s", $Utotaldnc)." |";
	$UprdncPERCENT = (MathZDC($Utotaldnc, $Utotal) * 100);
	$UprdncPERCENT = round($UprdncPERCENT, 2);
	$UprdncPERCENT =	sprintf("%6s", $UprdncPERCENT);
	$HTML_text.=$UprdncPERCENT." | ";

	$HTML_text.=sprintf("%6s", $totalsale)." | ";
	$HTML_text.=sprintf("%6s", $Utotalsale)." |";
	$UpsalePERCENT = (MathZDC($Utotalsale, $Utotal) * 100);
	$UpsalePERCENT = round($UpsalePERCENT, 2);
	$UpsalePERCENT =	sprintf("%6s", $UpsalePERCENT);
	$HTML_text.=$UpsalePERCENT." | ";

	$HTML_text.=sprintf("%6s", $totalarch)." | ";
	$HTML_text.=sprintf("%6s", $Utotalarch)." |";
	$UarchPERCENT = (MathZDC($Utotalarch, $Utotal) * 100);
	$UarchPERCENT = round($UarchPERCENT, 2);
	$UarchPERCENT =	sprintf("%6s", $UarchPERCENT);
	$HTML_text.=$UarchPERCENT." |\n";

	$HTML_text.="+------------------------------+--------+--------+-------+----------------------+----------------------+-------+--------+--------+-------+--------+--------+-------+--------+--------+-------+--------+--------+-------+\n";

	$CSV_text.="\""._QXZ("TOTALS")."\",\"$total\",\"$Utotal\",\"n/a\",\"$queue_str\",\"$Uqueue_str\",\"$UtotalPERCENT\",\"$totalnocid\",\"$Utotalnocid\",\"$UnocidPERCENT\",\"$totaldnc\",\"$Utotaldnc\",\"$UprdncPERCENT\",\"$totalsale\",\"$Utotalsale\",\"$UpsalePERCENT\",\"$totalarch\",\"$Utotalarch\",\"$UarchPERCENT\",\n";

	$HTML_text.="** - (x|y) "._QXZ("values under")." \""._QXZ("SENT TO QUEUE")."\" - \"x\" "._QXZ("is number of calls taken into queue").", \"y\" "._QXZ("is number of calls sent to an agent")."\n";
	$CSV_text.="\n\"** - (x|y) "._QXZ("values under")." '"._QXZ("SENT TO QUEUE")."'\",\n\" - 'x' "._QXZ("is number of calls taken into queue")."\",\n\" - 'y' "._QXZ("is number of calls sent to an agent")."\",\n";

$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
$HTML_text.="\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."\n";

$HTML_text.="</PRE>\n";
$HTML_text.="</TD></TR></TABLE>\n";

$HTML_text.="</BODY></HTML>\n";

$HTML_text.="\n\n<BR>$db_source";
$HTML_text.="</TD></TR></TABLE>";

$HTML_text.="</BODY></HTML>";


if ($file_download == 0 || !$file_download) {
	echo $HTML_header;
	require("admin_header.php");
	echo $HTML_text;
} else {

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_IVRfilter$US$FILE_TIME.csv";

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

exit;

?>