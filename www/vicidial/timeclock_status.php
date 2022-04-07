<?php
# timeclock_status.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 80602-0201 - First Build
# 80603-1500 - formatting changes
# 90310-2103 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 100214-1421 - Sort menu alphabetically
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 110703-1833 - Added download option
# 111104-1315 - Added user_group restrictions for selecting in-groups
# 130414-0152 - Added report logging
# 130610-0940 - Finalized changing of all ereg instances to preg
# 130616-0114 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0838 - Changed to mysqli PHP functions
# 140108-0719 - Added webserver and hostname to report logging
# 141007-2216 - Finalized adding QXZ translation to all admin files
# 141229-1853 - Added code for on-the-fly language translations display
# 161019-2254 - Added screen colors
# 170409-1544 - Added IP List validation code
# 220226-1712 - Added allow_web_debug system setting
#

#header ("Content-type: text/html; charset=utf-8");

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["begin_date"]))				{$begin_date=$_GET["begin_date"];}
	elseif (isset($_POST["begin_date"]))	{$begin_date=$_POST["begin_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))					{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))		{$file_download=$_POST["file_download"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$report_name = 'User Group Timeclock Status Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,webroot_writable,timeclock_end_of_day,enable_languages,language_method,admin_screen_colors,allow_web_debug FROM system_settings;";
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
	$webroot_writable =				$row[4];
	$timeclock_end_of_day =			$row[5];
	$SSenable_languages =			$row[6];
	$SSlanguage_method =			$row[7];
	$SSadmin_screen_colors =		$row[8];
	$SSallow_web_debug =			$row[9];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$begin_date = preg_replace('/[^-_0-9a-zA-Z]/',"",$begin_date);
$end_date = preg_replace('/[^-_0-9a-zA-Z]/',"",$end_date);
$submit = preg_replace('/[^-_0-9a-zA-Z]/',"",$submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/',"",$SUBMIT);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/',"",$file_download);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	$user_group = preg_replace('/[^-_0-9a-zA-Z]/', '', $user_group);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	$user_group = preg_replace('/[^-_0-9\p{L}]/u', '', $user_group);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$HHMM = date("Hi");
$HHteod = substr($timeclock_end_of_day,0,2);
$MMteod = substr($timeclock_end_of_day,2,2);

if ($HHMM < $timeclock_end_of_day)
	{$EoD = mktime($HHteod, $MMteod, 10, date("m"), date("d")-1, date("Y"));}
else
	{$EoD = mktime($HHteod, $MMteod, 10, date("m"), date("d"), date("Y"));}

$EoDdate = date("Y-m-d H:i:s", $EoD);
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$user_group, $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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

$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
	$i=0;
	$user_groups_to_print++;
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$LISTuser_groups[$i] =$row[0];
	if ($row[0]==$user_group)
		{$FORMuser_groups.="<option value=\"$row[0]\" SELECTED>$row[0]</option>";}
	else
		{$FORMuser_groups.="<option value=\"$row[0]\">$row[0]</option>";}
	$i++;
	}

if (strlen($user_group) > 0)
	{
	$stmt="SELECT group_name from vicidial_user_groups where user_group='$user_group' $LOGadmin_viewable_groupsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$group_name = $row[0];
	}

require("screen_colors.php");

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

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HEADER.="<html>\n";
$HEADER.="<head>\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<title>"._QXZ("ADMINISTRATION").": \n";
$HEADER.=_QXZ("$report_name");
$HEADER.="</title></head>";
$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

##### BEGIN Set variables to make header show properly #####
$ADD =					'311111';
$hh =					'usergroups';
$LOGast_admin_access =	'1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$usergroups_color =		'#FFFF99';
$usergroups_font =		'BLACK';
$usergroups_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

# require("admin_header.php");



$MAIN.="<font class=\"standard_bold\">"._QXZ("$report_name")."</font> $NWB#timeclockstatusreport$NWE\n<BR><BR>";
$MAIN.="<CENTER>\n";
$MAIN.="<TABLE WIDTH=750 BGCOLOR=#". $SSframe_background ." cellpadding=2 cellspacing=0><TR BGCOLOR=#". $SSmenu_background ."><TD ALIGN=LEFT>\n";
$MAIN.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2><B>"._QXZ("Timeclock Status for")." $user_group</TD><TD ALIGN=RIGHT> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
$MAIN.="<a href=\"./timeclock_report.php\"><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2><B>"._QXZ("TIMECLOCK REPORT")."</a> | ";
$MAIN.="<a href=\"./admin.php?ADD=311111&user_group=$user_group\"><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2><B>"._QXZ("USER GROUP")."</a>\n";
$MAIN.="</TD></TR>\n";

$MAIN.="<TR BGCOLOR=\"#". $SSstd_row3_background ."\"><TD ALIGN=LEFT COLSPAN=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><B> &nbsp; \n";

$MAIN.="<form action=$PHP_SELF method=GET>\n";
$MAIN.="<input type=hidden name=DB value=\"$DB\">\n";
$MAIN.="<select size=1 name=user_group>$FORMuser_groups</select>";
$MAIN.="<input style='background-color:#$SSbutton_color' type=submit name=submit VALUE='"._QXZ("SUBMIT")."'\n";

$MAIN.="</B></TD></TR>\n";
$MAIN.="<TR><TD ALIGN=LEFT COLSPAN=2>\n";
$MAIN.="<br><center>\n";

if (strlen($user_group) < 1)
	{
	header ("Content-type: text/html; charset=utf-8");
	echo "$HEADER";
	require("admin_header.php");
	echo "$MAIN";

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


##### grab all users in this user_group #####
$stmt="SELECT user,full_name from vicidial_users where user_group='" . mysqli_real_escape_string($link, $user_group) . "' $LOGadmin_viewable_groupsSQL order by full_name;";
if ($DB>0) {$MAIN.="|$stmt|";}
$rslt=mysql_to_mysqli($stmt, $link);
$users_to_print = mysqli_num_rows($rslt);
$o=0;
while ($users_to_print > $o) 
	{
	$row=mysqli_fetch_row($rslt);
	$users[$o] =		$row[0];
	$full_name[$o] =	$row[1];
	$Vevent_time[$o] =	'';
	$Vevent_epoch[$o] =	0;
	$Vcampaign[$o] =	'';
	$Tevent_epoch[$o] =	'';
	$Tevent_date[$o] =	'';
	$Tstatus[$o] =		'';
	$Tip_address[$o] =	'';
	$Tlogin_time[$o] =	'';
	$Tlogin_sec[$o] =	0;

	$o++;
	}

$o=0;
while ($users_to_print > $o) 
	{
	$total_login_time = 0;
	##### grab timeclock status record for this user #####
	$stmt="SELECT event_epoch,event_date,status,ip_address from vicidial_timeclock_status where user='$users[$o]' and event_epoch >= '$EoD' limit 1;";
	if ($DB>0) {$MAIN.="|$stmt|";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$stats_to_print = mysqli_num_rows($rslt);
	if ($stats_to_print > 0) 
		{
		$row=mysqli_fetch_row($rslt);
		$Tevent_epoch[$o] =	$row[0];
		$Tevent_date[$o] =	$row[1];
		$Tstatus[$o] =		$row[2];
		$Tip_address[$o] =	$row[3];

		if ( ($row[2]=='START') or ($row[2]=='LOGIN') )
			{$bgcolor[$o]='bgcolor="#'. $SSstd_row3_background .'"';} 
		else
			{$bgcolor[$o]='bgcolor="#'. $SSstd_row3_background .'"';}
		}

	##### grab timeclock logged-in time for each user #####
	$stmt="SELECT event,event_epoch,login_sec from vicidial_timeclock_log where user='$users[$o]' and event_epoch >= '$EoD' order by user;";
	if ($DB>0) {$MAIN.="|$stmt|";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$logs_to_parse = mysqli_num_rows($rslt);
	$p=0;
	while ($logs_to_parse > $p) 
		{
		$row=mysqli_fetch_row($rslt);
		if ( (preg_match('/LOGIN/', $row[0])) or (preg_match('/START/', $row[0])) )
			{
			$login_sec='';
			$Tevent_time[$o] = date("Y-m-d H:i:s", $row[1]);
			}
		if (preg_match('/LOGOUT/', $row[0]))
			{
			$login_sec = $row[2];
			$total_login_time = ($total_login_time + $login_sec);
			}
		$p++;
		}
	if ( (strlen($login_sec)<1) and ($logs_to_parse > 0) )
		{
		$login_sec = ($STARTtime - $row[1]);
		$total_login_time = ($total_login_time + $login_sec);
		}
	if ($logs_to_parse > 0)
		{
		$total_login_hours = ($total_login_time / 3600);
		$total_login_hours_int = round($total_login_hours, 2);
		$total_login_hours_int = intval("$total_login_hours");
		$total_login_minutes = ($total_login_hours - $total_login_hours_int);
		$total_login_minutes = ($total_login_minutes * 60);
		$total_login_minutes_int = round($total_login_minutes, 0);
		if ($total_login_minutes_int < 10) {$total_login_minutes_int = "0$total_login_minutes_int";}

		$Tlogin_time[$o] = "$total_login_hours_int:$total_login_minutes_int";
		$Tlogin_sec[$o] = $total_login_time;
		}
	else
		{
		$total_login_time = 0;
		$Tlogin_time[$o] = "0:00";
		$Tlogin_sec[$o] = $total_login_time;
		}

	if ($DB>0) {$MAIN.="|$Tlogin_sec[$o]|$Tlogin_time[$o]|";}

	##### grab vicidial_agent_log records in this user_group #####
	$stmt="SELECT event_time,UNIX_TIMESTAMP(event_time),campaign_id from vicidial_agent_log where user='$users[$o]' and event_time >= '$EoDdate' order by agent_log_id desc limit 1;";
	if ($DB>0) {$MAIN.="|$stmt|";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$vals_to_print = mysqli_num_rows($rslt);
	if ($vals_to_print > 0) 
		{
		$row=mysqli_fetch_row($rslt);
		$Vevent_time[$o] =	$row[0];
		$Vevent_epoch[$o] =	$row[1];
		$Vcampaign[$o] =	$row[2];
		}

	$o++;
	}


##### print each user that has any activity for today #####
$MAIN.="<br>\n";
$MAIN.="<center>\n";

$MAIN.="<TABLE width=720 cellspacing=0 cellpadding=1>\n";
$MAIN.="<TR>\n";
$MAIN.="<TD bgcolor=\"#99FF33\"> &nbsp; &nbsp; </TD><TD align=left> "._QXZ("TC Logged in and VICI active")."</TD>\n"; # bright green
$MAIN.="<TD bgcolor=\"#FFFF33\"> &nbsp; &nbsp; </TD><TD align=left> "._QXZ("TC Logged in only")."</TD>\n"; # bright yellow
$MAIN.="<TD bgcolor=\"#FF6666\"> &nbsp; &nbsp; </TD><TD align=left> "._QXZ("VICI active only")."</TD>\n"; # bright red
$MAIN.="</TR><TR>\n";
$MAIN.="<TD bgcolor=\"#66CC66\"> &nbsp; &nbsp; </TD><TD align=left> "._QXZ("TC Logged out and VICI active")."</TD>\n"; # dull green
$MAIN.="<TD bgcolor=\"#CCCC00\"> &nbsp; &nbsp; </TD><TD align=left> "._QXZ("TC Logged out only")."</TD>\n"; # dull yellow
$MAIN.="<TD> &nbsp; &nbsp; </TD><TD align=left> &nbsp; </TD>\n";
$MAIN.="</TR></TABLE><BR>\n";

$MAIN.="<B>"._QXZ("USER STATUS FOR USER GROUP").": $user_group &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='$PHP_SELF?DB=$DB&user_group=$user_group&submit=$submit&file_download=1'>["._QXZ("DOWNLOAD")."]</a></B>\n";
$MAIN.="<TABLE width=700 cellspacing=0 cellpadding=1>\n";
$MAIN.="<tr><td><font size=2># </td><td><font size=2>"._QXZ("USER")." </td><td align=left><font size=2>"._QXZ("NAME")." </td><td align=right><font size=2> "._QXZ("IP ADDRESS")."</td><td align=right><font size=2> "._QXZ("TC TIME")."</td><td align=right><font size=2>"._QXZ("TC LOGIN")."</td><td align=right><font size=2> "._QXZ("VICI LAST LOG")."</td><td align=right><font size=2> "._QXZ("VICI CAMPAIGN")."</td></tr>\n";

$CSV_text.="\""._QXZ("USER STATUS FOR USER GROUP").": $user_group\"\n";
$CSV_text.="\"\",\"#\",\""._QXZ("USER")."\",\""._QXZ("NAME")."\",\""._QXZ("STATUS")."\",\""._QXZ("IP ADDRESS")."\",\""._QXZ("TC TIME")."\",\""._QXZ("TC LOGIN")."\",\""._QXZ("VICI LAST LOG")."\",\""._QXZ("VICI CAMPAIGN")."\"\n";

$o=0;
$s=0;
while ($users_to_print > $o) 
	{
	if ( ($Tlogin_sec[$o] > 0) or (strlen($Vevent_time[$o]) > 0) )
		{
		if ( ($Tstatus[$o]=='START') or ($Tstatus[$o]=='LOGIN') )
			{
			if ($Tlogin_sec[$o] > 0)
				{$bgcolor[$o]='bgcolor="#FFFF33"'; $CSV_status=_QXZ("TC Logged in only");} # yellow
			if ( ($Tlogin_sec[$o] > 0) and (strlen($Vevent_time[$o]) > 0) )
				{$bgcolor[$o]='bgcolor="#99FF33"'; $CSV_status=_QXZ("TC Logged in and VICI active");} # green
			}
		else
			{
			if ($Tlogin_sec[$o] > 0)
				{$bgcolor[$o]='bgcolor="#CCCC00"'; $CSV_status=_QXZ("TC Logged out only");} # yellow
			if (strlen($Vevent_time[$o]) > 0)
				{$bgcolor[$o]='bgcolor="#FF6666"'; $CSV_status=_QXZ("VICI active only");} # red
			if ( ($Tlogin_sec[$o] > 0) and (strlen($Vevent_time[$o]) > 0) )
				{$bgcolor[$o]='bgcolor="#66CC66"'; $CSV_status=_QXZ("TC Logged out and VICI active");} # green
			}

		$s++;
		$MAIN.="<tr $bgcolor[$o]>";
		$MAIN.="<td><font size=1>$s</td>";
		$MAIN.="<td><font size=2><a href=\"./user_status.php?user=$users[$o]\">$users[$o]</a></td>";
		$MAIN.="<td><font size=2>$full_name[$o]</td>";
		$MAIN.="<td><font size=2>$Tip_address[$o]</td>";
		$MAIN.="<td align=right><font size=2>$Tlogin_time[$o]</td>";
		$MAIN.="<td align=right><font size=2>$Tevent_time[$o]</td>";
		$MAIN.="<td align=right><font size=2>$Vevent_time[$o]</td>";
		$MAIN.="<td align=right><font size=2>$Vcampaign[$o]</td>";
		$MAIN.="</tr>";

		$CSV_text.="\"\",\"$s\",\"$users[$o]\",\"$full_name[$o]\",\"$CSV_status\",\"$Tip_address[$o]\",\"$Tlogin_time[$o]\",\"$Tevent_time[$o]\",\"$Vevent_time[$o]\",\"$Vcampaign[$o]\"\n";

		if (strlen($Tstatus[$o])>0)
			{$TOTlogin_sec = ($TOTlogin_sec + $Tlogin_sec[$o]);}
		}
	$o++;
	}



$total_login_hours = ($TOTlogin_sec / 3600);
$total_login_hours_int = round($total_login_hours, 2);
$total_login_hours_int = intval("$total_login_hours");
$total_login_minutes = ($total_login_hours - $total_login_hours_int);
$total_login_minutes = ($total_login_minutes * 60);
$total_login_minutes_int = round($total_login_minutes, 0);
if ($total_login_minutes_int < 10) {$total_login_minutes_int = "0$total_login_minutes_int";}

$MAIN.="<tr bgcolor=white>";
$MAIN.="<td colspan=4><font size=2>"._QXZ("TOTALS")."</td>";
$MAIN.="<td align=right><font size=2>$total_login_hours_int:$total_login_minutes_int</td>";
$MAIN.="<td align=right><font size=2></td>";
$MAIN.="<td align=right><font size=2></td>";
$MAIN.="<td align=right><font size=2></td>";
$MAIN.="</tr>";
$MAIN.="</table>";

$CSV_text.="\"\",\""._QXZ("TOTALS")."\",\"\",\"\",\"\",\"\",\"$total_login_hours_int:$total_login_minutes_int\"\n";


$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

$MAIN.="\n\n\n<br><br><br>\n\n";


$MAIN.="<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."|$db_source</font>";

$MAIN.="</TD></TR><TABLE>\n";
$MAIN.="</body>\n";
$MAIN.="</html>\n";

if ($file_download > 0)
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "timeclock_status_$US$FILE_TIME.csv";
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
	header ("Content-type: text/html; charset=utf-8");
	echo "$HEADER";
	require("admin_header.php");
	echo "$MAIN";
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
