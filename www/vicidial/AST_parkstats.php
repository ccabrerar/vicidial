<?php 
# AST_parkstats.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60619-1717 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 90508-0644 - Changed to PHP long tags
# 130610-1133 - Finalized changing of all ereg instances to preg
# 130621-0728 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2012 - Changed to mysqli PHP functions
# 140328-0005 - Converted division calculations to use MathZDC function
# 141114-0732 - Finalized adding QXZ translation to all admin files
# 141230-1441 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 170412-2129 - Updated for new park logging
# 170808-0220 - Added detailed log option
# 170823-2154 - Added HTML formatting and screen colors
# 180311-0327 - Added unique lead ID count
# 191013-0858 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name = 'Agent Parked Call Report';
$db_source = 'M';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["show_details"]))				{$show_details=$_GET["show_details"];}
	elseif (isset($_POST["show_details"]))		{$show_details=$_POST["show_details"];}
if (isset($_GET["sort_by_details"]))				{$sort_by_details=$_GET["sort_by_details"];}
	elseif (isset($_POST["sort_by_details"]))		{$sort_by_details=$_POST["sort_by_details"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))		{$query_date=$_POST["query_date"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin = 				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
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







$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$user, $query_date, $end_date, $call_status, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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

$stmt="SELECT full_name,user_group,admin_hide_lead_data,admin_hide_phone_data from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGuser_group =			$row[1];
$LOGadmin_hide_lead_data =	$row[2];
$LOGadmin_hide_phone_data =	$row[3];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$MAIN.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =		$row[0];
$LOGallowed_reports =		$row[1];
$LOGadmin_viewable_groups =	$row[2];

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and channel_group IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where channel_group IN('$rawLOGallowed_campaignsSQL')";
	}

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
if (!isset($group)) {$group = '';}
if (!isset($query_date)) {$query_date = $NOW_DATE;}

$stmt="select distinct channel_group from park_log $whereLOGallowed_campaignsSQL;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
$groups=array();
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	$i++;
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
$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"verticalbargraph.css\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"wz_jsgraphics.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"line.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

$MAIN.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 cellspacing=5 cellpadding=5><TR><TD VALIGN=TOP align=center>\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.=_QXZ("Date").":\n";
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

$MAIN.="</TD><TD VALIGN=TOP>\n";
$MAIN.="<SELECT SIZE=1 NAME=group>\n";
	$o=0;
	while ($groups_to_print > $o)
	{
		if ($groups[$o] == $group) {$MAIN.="<option selected value=\"$groups[$o]\">$groups[$o]</option>\n";}
		  else {$MAIN.="<option value=\"$groups[$o]\">$groups[$o]</option>\n";}
		$o++;
	}
$MAIN.="</SELECT></TD><TD VALIGN=middle align=center>\n";
$MAIN.=_QXZ("Display as:")."&nbsp;";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>";
$MAIN.="</TD><TD VALIGN=middle align=center>\n";
$MAIN.="<INPUT TYPE=checkbox NAME=show_details VALUE='checked' $show_details>"._QXZ("Show details")."\n";
$MAIN.="</TD><TD VALIGN=middle align=center>\n";
$MAIN.="<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=34&campaign_id=$group\">"._QXZ("MODIFY")."</a> | <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$MAIN.="</TD></TR></TABLE>\n";
$MAIN.="</FORM>\n\n";

$TEXT.="<PRE><FONT SIZE=2>\n\n";


if (!$group)
{
$MAIN.="\n\n";
$MAIN.=_QXZ("PLEASE SELECT A GROUP AND DATE ABOVE AND CLICK SUBMIT")."\n";
}

else
{


$TEXT.=_QXZ("Park Stats",48)." $NOW_TIME\n";
$TEXT.="\n";
$TEXT.="---------- "._QXZ("TOTALS")."\n";

$HTML.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
$HTML.="<th><font size='2'>"._QXZ("Park Stats",48)." $NOW_TIME</th>";
$HTML.="</tr>\n";

$stmt="select count(*),sum(parked_sec) from park_log where parked_time >= '$query_date 00:00:00' and parked_time <= '$query_date 23:59:59' and status IN('HUNGUP','GRABBED','GRABBEDIVR','IVRPARKED','PARKED') and channel_group='" . mysqli_real_escape_string($link, $group) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$TOTALcalls =	sprintf("%10s", $row[0]);
$average_hold_seconds = MathZDC($row[1], $row[0]);
$average_hold_seconds = round($average_hold_seconds, 0);
$average_hold_seconds =	sprintf("%10s", $average_hold_seconds);

$stmt="select count(distinct lead_id) from park_log where parked_time >= '$query_date 00:00:00' and parked_time <= '$query_date 23:59:59' and status IN('HUNGUP','GRABBED','GRABBEDIVR','IVRPARKED','PARKED') and channel_group='" . mysqli_real_escape_string($link, $group) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);
$TOTALleads =	sprintf("%10s", $row[0]);

$TEXT.=_QXZ("Total Calls parked in this Group:",45)." $TOTALcalls\n";
$TEXT.=_QXZ("Total Leads parked in this Group:",45)." $TOTALleads\n";
$TEXT.=_QXZ("Average Hold Time(seconds) for Parked Calls:",45)." $average_hold_seconds\n";
$TEXT.="\n";
$TEXT.="---------- "._QXZ("DROPS")."\n";

$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
$HTML.="<td><font size=2><B>"._QXZ("TOTALS")."</B></BR>";
$HTML.=_QXZ("Total Calls parked in this Group:")." $TOTALcalls</BR>";
$HTML.=_QXZ("Total Leads parked in this Group:")." $TOTALleads</BR>";
$HTML.=_QXZ("Average Hold Time(seconds) for Parked Calls:")." $average_hold_seconds";
$HTML.="</BR></BR>";
$HTML.="<B>"._QXZ("DROPS")."</B></BR>";

$stmt="select count(*),sum(parked_sec) from park_log where parked_time >= '$query_date 00:00:00' and parked_time <= '$query_date 23:59:59' and status ='HUNGUP' and channel_group='" . mysqli_real_escape_string($link, $group) . "' and (talked_sec < 5 or talked_sec is null);";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$row=mysqli_fetch_row($rslt);

$DROPcalls =	sprintf("%10s", $row[0]);
$DROPpercent = (MathZDC($DROPcalls, $TOTALcalls) * 100);
$DROPpercent = round($DROPpercent, 0);

$average_hold_seconds = MathZDC($row[1], $row[0]);
$average_hold_seconds = round($average_hold_seconds, 0);
$average_hold_seconds =	sprintf("%10s", $average_hold_seconds);

$TEXT.=_QXZ("Total Dropped Calls:",45)." $DROPcalls  $DROPpercent%\n";
$TEXT.=_QXZ("Average Hold Time(seconds) for Dropped Calls:",45)." $average_hold_seconds\n";

$HTML.=_QXZ("Total Dropped Calls:")." $DROPcalls  $DROPpercent%</BR>";
$HTML.=_QXZ("Average Hold Time(seconds) for Dropped Calls:")." $average_hold_seconds";
$HTML.="</font></td></tr></table>";


##############################
#########  USER STATS

$TEXT.="\n";
$TEXT.="---------- "._QXZ("USER PARK STATS").":\n";
$TEXT.="+---------------------------------------------+------------+------------+--------+--------+------------+--------+--------+\n";
$TEXT.="| "._QXZ("USER",43)." | "._QXZ("CALLS",10)." | "._QXZ("LEADS",10)." | "._QXZ("TIME M",6)." | "._QXZ("AVRG M",6)." | "._QXZ("DROPS",10)." | "._QXZ("TIME M",6)." | "._QXZ("AVRG M",6)." |\n";
$TEXT.="+---------------------------------------------+------------+------------+--------+--------+------------+--------+--------+\n";

$HTML.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
$HTML.="<th colspan='7'><font size='2'>"._QXZ("USER PARK STATS")."</font></th>";
$HTML.="</tr>\n";
$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
$HTML.="<th><font size='2'>"._QXZ("USER")."</font></th>";
$HTML.="<th><font size='2'>"._QXZ("CALLS")."</font></th>";
$HTML.="<th><font size='2'>"._QXZ("LEADS")."</font></th>";
$HTML.="<th><font size='2'>"._QXZ("TIME M")."</font></th>";
$HTML.="<th><font size='2'>"._QXZ("AVRG M")."</font></th>";
$HTML.="<th><font size='2'>"._QXZ("DROPS")."</font></th>";
$HTML.="<th><font size='2'>"._QXZ("TIME M")."</font></th>";
$HTML.="<th><font size='2'>"._QXZ("AVRG M")."</font></th>";
$HTML.="</tr>\n";

$stmt="select park_log.user,full_name,count(*),sum(parked_sec),avg(parked_sec) from park_log,vicidial_users where parked_time >= '$query_date 00:00:00' and parked_time <= '$query_date 23:59:59' and status IN('HUNGUP','GRABBED','GRABBEDIVR','IVRPARKED','PARKED') and channel_group='" . mysqli_real_escape_string($link, $group) . "' and parked_sec is not null and park_log.user is not null and park_log.user=vicidial_users.user group by park_log.user;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
$user=array();
$full_name=array();
$USERcalls=array();
$USERleads=array();
$USERtotTALK_MS=array();
$USERavgTALK_MS=array();
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);

	$user[$i] =			$row[0];
	$full_name[$i] =	sprintf("%-43s", "$row[0] - $row[1]"); while(strlen($full_name[$i])>43) {$full_name[$i] = substr("$full_name[$i]", 0, -1);}
	$USERcalls[$i] =	sprintf("%10s", $row[2]);
	$USERleads[$i] =	0;
	$USERtotTALK =	$row[3];
	$USERavgTALK =	$row[4];

	$USERtotTALK_M = MathZDC($USERtotTALK, 60);
	$USERtotTALK_M = round($USERtotTALK_M, 2);
	$USERtotTALK_M_int = intval("$USERtotTALK_M");
	$USERtotTALK_S = ($USERtotTALK_M - $USERtotTALK_M_int);
	$USERtotTALK_S = ($USERtotTALK_S * 60);
	$USERtotTALK_S = round($USERtotTALK_S, 0);
	if ($USERtotTALK_S < 10) {$USERtotTALK_S = "0$USERtotTALK_S";}
	$USERtotTALK_MS[$i] = "$USERtotTALK_M_int:$USERtotTALK_S";
	$USERtotTALK_MS[$i] =		sprintf("%6s", $USERtotTALK_MS[$i]);

	$USERavgTALK_M = MathZDC($USERavgTALK, 60);
	$USERavgTALK_M = round($USERavgTALK_M, 2);
	$USERavgTALK_M_int = intval("$USERavgTALK_M");
	$USERavgTALK_S = ($USERavgTALK_M - $USERavgTALK_M_int);
	$USERavgTALK_S = ($USERavgTALK_S * 60);
	$USERavgTALK_S = round($USERavgTALK_S, 0);
	if ($USERavgTALK_S < 10) {$USERavgTALK_S = "0$USERavgTALK_S";}
	$USERavgTALK_MS[$i] = "$USERavgTALK_M_int:$USERavgTALK_S";
	$USERavgTALK_MS[$i] =		sprintf("%6s", $USERavgTALK_MS[$i]);
	$i++;
	}

$i=0;
$USERdrops=array();
$USERtotDROP_MS=array();
$USERavgDROP_MS=array();
while ($i < $users_to_print)
	{
	$stmt="select count(*),sum(parked_sec),avg(parked_sec) from park_log where parked_time >= '$query_date 00:00:00' and parked_time <= '$query_date 23:59:59' and status IN('HUNGUP') and channel_group='" . mysqli_real_escape_string($link, $group) . "' and parked_sec is not null and park_log.user='$user[$i]' group by park_log.user;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$drop_to_print = mysqli_num_rows($rslt);
	if ($users_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$USERdrops[$i] =	sprintf("%10s", $row[0]);
		$USERtotDROP =	$row[1];
		$USERavgDROP =	$row[2];

		$USERtotDROP_M = MathZDC($USERtotDROP, 60);
		$USERtotDROP_M = round($USERtotDROP_M, 2);
		$USERtotDROP_M_int = intval("$USERtotDROP_M");
		$USERtotDROP_S = ($USERtotDROP_M - $USERtotDROP_M_int);
		$USERtotDROP_S = ($USERtotDROP_S * 60);
		$USERtotDROP_S = round($USERtotDROP_S, 0);
		if ($USERtotDROP_S < 10) {$USERtotDROP_S = "0$USERtotDROP_S";}
		$USERtotDROP_MS[$i] = "$USERtotDROP_M_int:$USERtotDROP_S";
		$USERtotDROP_MS[$i] =		sprintf("%6s", $USERtotDROP_MS[$i]);

		$USERavgDROP_M = MathZDC($USERavgDROP, 60);
		$USERavgDROP_M = round($USERavgDROP_M, 2);
		$USERavgDROP_M_int = intval("$USERavgDROP_M");
		$USERavgDROP_S = ($USERavgDROP_M - $USERavgDROP_M_int);
		$USERavgDROP_S = ($USERavgDROP_S * 60);
		$USERavgDROP_S = round($USERavgDROP_S, 0);
		if ($USERavgDROP_S < 10) {$USERavgDROP_S = "0$USERavgDROP_S";}
		$USERavgDROP_MS[$i] = "$USERavgDROP_M_int:$USERavgDROP_S";
		$USERavgDROP_MS[$i] =		sprintf("%6s", $USERavgDROP_MS[$i]);
		}

	$stmt="select count(distinct lead_id) from park_log where parked_time >= '$query_date 00:00:00' and parked_time <= '$query_date 23:59:59' and status IN('HUNGUP','GRABBED','GRABBEDIVR','IVRPARKED','PARKED') and channel_group='" . mysqli_real_escape_string($link, $group) . "' and parked_sec is not null and park_log.user='$user[$i]' group by park_log.user;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN.="$stmt\n";}
	$drop_to_print = mysqli_num_rows($rslt);
	if ($users_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$USERleads[$i] =	sprintf("%10s", $row[0]);
		}

	$TEXT.="| <a href=\"user_stats.php?park_rpt=1&begin_date=$query_date&end_date=$query_date&user=$user[$i]\">$full_name[$i]</a> | $USERcalls[$i] | $USERleads[$i] | $USERtotTALK_MS[$i] | $USERavgTALK_MS[$i] | $USERdrops[$i] | $USERtotDROP_MS[$i] | $USERavgDROP_MS[$i] |\n";

	$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
	$HTML.="<th><font size='2'><a href=\"user_stats.php?park_rpt=1&begin_date=$query_date&end_date=$query_date&user=$user[$i]\">$full_name[$i]</a></font></th>";
	$HTML.="<th><font size='2'>$USERcalls[$i]</font></th>";
	$HTML.="<th><font size='2'>$USERleads[$i]</font></th>";
	$HTML.="<th><font size='2'>$USERtotTALK_MS[$i]</font></th>";
	$HTML.="<th><font size='2'>$USERavgTALK_MS[$i]</font></th>";
	$HTML.="<th><font size='2'>$USERdrops[$i]</font></th>";
	$HTML.="<th><font size='2'>$USERtotDROP_MS[$i]</font></th>";
	$HTML.="<th><font size='2'>$USERavgDROP_MS[$i]</font></th>";
	$HTML.="</tr>\n";

	$i++;
	}

$TEXT.="+---------------------------------------------+------------+------------+--------+--------+------------+--------+--------+\n";
$HTML.="</table>\n";

##############################
#########  TIME STATS

$TEXT.="\n\n";
$TEXT.="---------- "._QXZ("TIME STATS");

$TEXT.="<FONT SIZE=0>";

$HTML.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
$HTML.="<th><font size='2'>"._QXZ("TIME STATS")."</font></th>";
$HTML.="</tr>";
$HTML.="<tr bgcolor='#FFFFFF'>";
$HTML.="<td align='left'><pre><font size='0'>";

$hi_hour_count=0;
$last_full_record=0;
$i=0;
$h=0;
$hour_count=array();
$drop_count=array();
while ($i <= 96)
	{
	$stmt="select count(*) from park_log where parked_time >= '$query_date $h:00:00' and parked_time <= '$query_date $h:14:59' and status IN('HUNGUP','GRABBED','GRABBEDIVR','IVRPARKED','PARKED') and channel_group='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN2.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$hour_count[$i] = $row[0];
	if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
	if ($hour_count[$i] > 0) {$last_full_record = $i;}
	$stmt="select count(*) from park_log where parked_time >= '$query_date $h:00:00' and parked_time <= '$query_date $h:14:59' and status ='HUNGUP' and channel_group='" . mysqli_real_escape_string($link, $group) . "' and (talked_sec < 5 or talked_sec is null);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN2.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$drop_count[$i] = $row[0];
	$i++;


	$stmt="select count(*) from park_log where parked_time >= '$query_date $h:15:00' and parked_time <= '$query_date $h:29:59' and status IN('HUNGUP','GRABBED','GRABBEDIVR','IVRPARKED','PARKED') and channel_group='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN2.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$hour_count[$i] = $row[0];
	if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
	if ($hour_count[$i] > 0) {$last_full_record = $i;}
	$stmt="select count(*) from park_log where parked_time >= '$query_date $h:15:00' and parked_time <= '$query_date $h:29:59' and status ='HUNGUP' and channel_group='" . mysqli_real_escape_string($link, $group) . "' and (talked_sec < 5 or talked_sec is null);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN2.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$drop_count[$i] = $row[0];
	$i++;

	$stmt="select count(*) from park_log where parked_time >= '$query_date $h:30:00' and parked_time <= '$query_date $h:44:59' and status IN('HUNGUP','GRABBED','GRABBEDIVR','IVRPARKED','PARKED') and channel_group='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN2.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$hour_count[$i] = $row[0];
	if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
	if ($hour_count[$i] > 0) {$last_full_record = $i;}
	$stmt="select count(*) from park_log where parked_time >= '$query_date $h:30:00' and parked_time <= '$query_date $h:44:59' and status ='HUNGUP' and channel_group='" . mysqli_real_escape_string($link, $group) . "' and (talked_sec < 5 or talked_sec is null);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN2.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$drop_count[$i] = $row[0];
	$i++;

	$stmt="select count(*) from park_log where parked_time >= '$query_date $h:45:00' and parked_time <= '$query_date $h:59:59' and status IN('HUNGUP','GRABBED','GRABBEDIVR','IVRPARKED','PARKED') and channel_group='" . mysqli_real_escape_string($link, $group) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN2.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$hour_count[$i] = $row[0];
	if ($hour_count[$i] > $hi_hour_count) {$hi_hour_count = $hour_count[$i];}
	if ($hour_count[$i] > 0) {$last_full_record = $i;}
	$stmt="select count(*) from park_log where parked_time >= '$query_date $h:45:00' and parked_time <= '$query_date $h:59:59' and status ='HUNGUP' and channel_group='" . mysqli_real_escape_string($link, $group) . "' and (talked_sec < 5 or talked_sec is null);";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {$MAIN2.="$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$drop_count[$i] = $row[0];
	$i++;
	$h++;
	}

$hour_multiplier = MathZDC(100, $hi_hour_count);
#$hour_multiplier = round($hour_multiplier, 0);

$MAIN2.="<!-- HICOUNT: $hi_hour_count|$hour_multiplier -->\n";
$MAIN2.=_QXZ("GRAPH IN 15 MINUTE INCREMENTS OF TOTAL INCOMING CALLS FOR THIS GROUP")."\n";

$k=1;
$Mk=0;
$call_scale = '0';
while ($k <= 102) 
	{
	if ($Mk >= 5) 
		{
		$Mk=0;
		$scale_num=MathZDC($k, $hour_multiplier);
		$scale_num = round($scale_num, 0);
		$LENscale_num = (strlen($scale_num));
		$k = ($k + $LENscale_num);
		$call_scale .= "$scale_num";
		}
	else
		{
		$call_scale .= " ";
		$k++;   $Mk++;
		}
	}


$MAIN2.="+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n";
$MAIN2.="| "._QXZ("HOUR",4)." |$call_scale| "._QXZ("DROPS",5)." | "._QXZ("TOTAL",5)." |\n";
$MAIN2.="+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n";

$ZZ = '00';
$i=0;
$h=4;
$hour= -1;
$no_lines_yet=1;

while ($i <= 96)
	{
	$char_counter=0;
	$time = '      ';
	if ($h >= 4) 
		{
		$hour++;
		$h=0;
		if ($hour < 10) {$hour = "0$hour";}
		$time = "+$hour$ZZ+";
		}
	if ($h == 1) {$time = "   15 ";}
	if ($h == 2) {$time = "   30 ";}
	if ($h == 3) {$time = "   45 ";}
	$Ghour_count = $hour_count[$i];
	if ($Ghour_count < 1) 
		{
		if ( ($no_lines_yet) or ($i > $last_full_record) )
			{
			$do_nothing=1;
			}
		else
			{
			$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);
			$MAIN2.="|$time|";
			$k=0;   while ($k <= 102) {$MAIN2.=" ";   $k++;}
			$MAIN2.="| $hour_count[$i] |\n";
			}
		}
	else
		{
		$no_lines_yet=0;
		$Xhour_count = ($Ghour_count * $hour_multiplier);
		$Yhour_count = (99 - $Xhour_count);

		$Gdrop_count = $drop_count[$i];
		if ($Gdrop_count < 1) 
			{
			$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);

			$MAIN2.="|$time|<SPAN class=\"green\">";
			$k=0;   while ($k <= $Xhour_count) {$MAIN2.="*";   $k++;   $char_counter++;}
			$MAIN2.="*X</SPAN>";   $char_counter++;
			$k=0;   while ($k <= $Yhour_count) {$MAIN2.=" ";   $k++;   $char_counter++;}
				while ($char_counter <= 101) {$MAIN2.=" ";   $char_counter++;}
			$MAIN2.="| 0     | $hour_count[$i] |\n";

			}
		else
			{
			$Xdrop_count = ($Gdrop_count * $hour_multiplier);

		#	if ($Xdrop_count >= $Xhour_count) {$Xdrop_count = ($Xdrop_count - 1);}

			$XXhour_count = ( ($Xhour_count - $Xdrop_count) - 1 );

			$hour_count[$i] =	sprintf("%-5s", $hour_count[$i]);
			$drop_count[$i] =	sprintf("%-5s", $drop_count[$i]);

			$MAIN2.="|$time|<SPAN class=\"red\">";
			$k=0;   while ($k <= $Xdrop_count) {$MAIN2.=">";   $k++;   $char_counter++;}
			$MAIN2.="D</SPAN><SPAN class=\"green\">";   $char_counter++;
			$k=0;   while ($k <= $XXhour_count) {$MAIN2.="*";   $k++;   $char_counter++;}
			$MAIN2.="X</SPAN>";   $char_counter++;
			$k=0;   while ($k <= $Yhour_count) {$MAIN2.=" ";   $k++;   $char_counter++;}
				while ($char_counter <= 102) {$MAIN2.=" ";   $char_counter++;}
			$MAIN2.="| $drop_count[$i] | $hour_count[$i] |\n";
			}
		}
	$i++;
	$h++;
	}

$MAIN2.="+------+-------------------------------------------------------------------------------------------------------+-------+-------+\n";
}

$TEXT.=$MAIN2."\n";
$HTML.=$MAIN2."</font></pre></td></tr></table>";

if ($show_details) {
	$stmt="select * from park_log where parked_time >= '$query_date 00:00:00' and parked_time <= '$query_date 23:59:59' and channel_group='" . mysqli_real_escape_string($link, $group) . "'  order by parked_time, grab_time, hangup_time desc"; 
	$rslt=mysql_to_mysqli($stmt, $link);

	if (!$lower_limit) {$lower_limit=1;}
	if ($lower_limit+999>=mysqli_num_rows($rslt)) {$upper_limit=($lower_limit+mysqli_num_rows($rslt)%1000)-1;} else {$upper_limit=$lower_limit+999;}
	
	$TEXT.="\n\n--- "._QXZ("PARK LOG RECORDS FOR")." $query_date, "._QXZ("CHANNEL GROUP")." $group\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit               ";
	# $MAIN.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&group=$group&show_detail=$show_detail&sort_by_detail=$sort_by_detail&query_date=$query_date&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>";
	$TEXT.="\n";

	$HTML.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
	$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML.="<th colspan='11'><font size='2'>"._QXZ("PARK LOG RECORDS FOR")." $query_date, "._QXZ("CHANNEL GROUP")." $group\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit</font></th>";
	$HTML.="</tr>\n";

	if (mysqli_num_rows($rslt)>0) {
		
		$header= "+---------------------+---------------------+---------------------+------------+--------------------------------+-----------------+------------+------------+----------------------+------------+------------+\n";
		$output.=$header;
		$output.="| "._QXZ("PARKED TIME",19)." | "._QXZ("GRAB TIME",19)." | "._QXZ("HANGUP TIME",19)." | "._QXZ("STATUS",10)." | "._QXZ("CHANNEL",30)." | "._QXZ("SERVER IP",15)." | "._QXZ("PARKED SEC",10)." | "._QXZ("TALKED SEC",10)." | "._QXZ("EXTENSION",20)." | "._QXZ("USER",10)." | "._QXZ("LEAD ID",10)." |\n";
		$output.=$header;
		$i=0;

		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("PARKED TIME")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("GRAB TIME")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("HANGUP TIME")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("STATUS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("CHANNEL")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("SERVER IP")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("PARKED SEC")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("TALKED SEC")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("EXTENSION")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("USER")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("LEAD ID")."</font></th>";
		$HTML.="</tr>\n";
		
		while($row=mysqli_fetch_array($rslt)) {
			$i++;
			$output.="| ";
			$output.=sprintf("%-19s", $row["parked_time"])." | ";
			$output.=sprintf("%-19s", $row["grab_time"])." | ";
			$output.=sprintf("%-19s", $row["hangup_time"])." | ";
			$output.=sprintf("%-10s", $row["status"])." | ";
			$output.=sprintf("%-30s", substr($row["channel"], 0, 30))." | ";
			$output.=sprintf("%-15s", $row["server_ip"])." | ";
			$output.=sprintf("%10s", $row["parked_sec"])." | ";
			$output.=sprintf("%10s", $row["talked_sec"])." | ";
			$output.=sprintf("%-20s", substr($row["extension"], 0, 20))." | ";
			$output.=sprintf("%-10s", $row["user"])." | ";
			$output.=sprintf("%-10s", $row["lead_id"])." |\n";

			$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML.="<th><font size='2'>".$row["parked_time"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["grab_time"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["hangup_time"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["status"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["channel"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["server_ip"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["parked_sec"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["talked_sec"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["extension"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["user"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["lead_id"]."</font></th>";
			$HTML.="</tr>\n";
		
		}
		$output.=$header;

		$ll=$lower_limit-1000;
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		if ($ll>=1) {
			$output.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&group=$group&show_detail=$show_detail&report_display_type=$report_display_type&sort_by_detail=$sort_by_detail&query_date=$query_date&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a>";
			$HTML.="<th colspan='6'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&group=$group&show_detail=$show_detail&report_display_type=$report_display_type&sort_by_detail=$sort_by_detail&query_date=$query_date&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a></font></th>";
		} else {
			$output.=sprintf("%-23s", " ");
			$HTML.="<td colspan='6'>&nbsp;</td>";
		}
		$output.=sprintf("%-171s", " ");

		if (($lower_limit+1000)<mysqli_num_rows($rslt)) {
			if ($upper_limit+1000>=mysqli_num_rows($rslt)) {$max_limit=mysqli_num_rows($rslt)-$upper_limit;} else {$max_limit=1000;}
			$output.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&group=$group&show_detail=$show_detail&report_display_type=$report_display_type&sort_by_detail=$sort_by_detail&query_date=$query_date&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a>";
			$HTML.="<td align='right' colspan='5'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&group=$group&show_detail=$show_detail&report_display_type=$report_display_type&sort_by_detail=$sort_by_detail&query_date=$query_date&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a></font></th>";
		} else {
			$output.=sprintf("%23s", " ");
			$HTML.="<td colspan='5'>&nbsp;</th>";
		}
		$output.="\n";

		$TEXT.=$output;
		$HTML.="</tr></table>";
	}
}

$TEXT.="</font></PRE>\n";

if ($report_display_type=="HTML") {
	$MAIN.=$HTML;
} else {
	$MAIN.=$TEXT;
}

$MAIN.="</form></BODY></HTML>\n";

echo $HEADER;
require("admin_header.php");
echo $MAIN;


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
