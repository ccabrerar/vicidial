<?php
# timeclock_edit.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 80624-1342 - First build
# 80701-1323 - functional beta version done
# 90310-2109 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 120223-2135 - Removed logging of good login passwords if webroot writable is enabled
# 130610-1107 - Finalized changing of all ereg instances to preg
# 130616-1541 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0840 - Changed to mysqli PHP functions
# 140328-0005 - Converted division calculations to use MathZDC function
# 141007-2217 - Finalized adding QXZ translation to all admin files
# 141229-1905 - Added code for on-the-fly language translations display
# 151203-1902 - Fix for javascript timezone issues in editing of timeclock entries
# 160329-1610 - Fix for DST time and editing entries
# 170409-1539 - Added IP List validation code
# 220226-1726 - Added allow_web_debug system setting
#

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["oldLOGINepoch"]))				{$oldLOGINepoch=$_GET["oldLOGINepoch"];}
	elseif (isset($_POST["oldLOGINepoch"]))		{$oldLOGINepoch=$_POST["oldLOGINepoch"];}
if (isset($_GET["oldLOGOUTepoch"]))				{$oldLOGOUTepoch=$_GET["oldLOGOUTepoch"];}
	elseif (isset($_POST["oldLOGOUTepoch"]))	{$oldLOGOUTepoch=$_POST["oldLOGOUTepoch"];}
if (isset($_GET["oldLOGINdate"]))				{$oldLOGINdate=$_GET["oldLOGINdate"];}
	elseif (isset($_POST["oldLOGINdate"]))		{$oldLOGINdate=$_POST["oldLOGINdate"];}
if (isset($_GET["oldLOGOUTdate"]))				{$oldLOGOUTdate=$_GET["oldLOGOUTdate"];}
	elseif (isset($_POST["oldLOGOUTdate"]))		{$oldLOGOUTdate=$_POST["oldLOGOUTdate"];}
if (isset($_GET["LOGINepoch"]))					{$LOGINepoch=$_GET["LOGINepoch"];}
	elseif (isset($_POST["LOGINepoch"]))		{$LOGINepoch=$_POST["LOGINepoch"];}
if (isset($_GET["LOGOUTepoch"]))				{$LOGOUTepoch=$_GET["LOGOUTepoch"];}
	elseif (isset($_POST["LOGOUTepoch"]))		{$LOGOUTepoch=$_POST["LOGOUTepoch"];}
if (isset($_GET["notes"]))						{$notes=$_GET["notes"];}
	elseif (isset($_POST["notes"]))				{$notes=$_POST["notes"];}
if (isset($_GET["LOGINevent_id"]))				{$LOGINevent_id=$_GET["LOGINevent_id"];}
	elseif (isset($_POST["LOGINevent_id"]))		{$LOGINevent_id=$_POST["LOGINevent_id"];}
if (isset($_GET["LOGOUTevent_id"]))				{$LOGOUTevent_id=$_GET["LOGOUTevent_id"];}
	elseif (isset($_POST["LOGOUTevent_id"]))	{$LOGOUTevent_id=$_POST["LOGOUTevent_id"];}
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["stage"]))						{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))				{$stage=$_POST["stage"];}
if (isset($_GET["timeclock_id"]))				{$timeclock_id=$_GET["timeclock_id"];}
	elseif (isset($_POST["timeclock_id"]))		{$timeclock_id=$_POST["timeclock_id"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))						{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))			{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$StarTtimE = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$isdst = date("I");
$ip = getenv("REMOTE_ADDR");
$invalid_record=0;

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$user_territories_active =		$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$SSallow_web_debug =			$row[6];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$timeclock_id = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$timeclock_id);
$oldLOGINepoch = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$oldLOGINepoch);
$oldLOGOUTepoch = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$oldLOGOUTepoch);
$oldLOGINdate = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$oldLOGINdate);
$oldLOGOUTdate = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$oldLOGOUTdate);
$LOGINepoch = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$LOGINepoch);
$LOGOUTepoch = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$LOGOUTepoch);
$notes = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$notes);
$LOGINevent_id = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$LOGINevent_id);
$LOGOUTevent_id = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$LOGOUTevent_id);
$submit = preg_replace('/[^-_0-9a-zA-Z]/',"",$submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/',"",$SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	$stage = preg_replace('/[^-_0-9a-zA-Z]/',"",$stage);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	$stage = preg_replace('/[^-_0-9\p{L}]/u',"",$stage);
	}

$local_gmt=0;
$stmt = "SELECT local_gmt FROM servers where active='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$sr_conf_ct = mysqli_num_rows($rslt);
if ($sr_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$local_gmt = ($row[0] * 1);
	}
$local_gmt = ($local_gmt + $isdst);
$local_gmt_sec = ($local_gmt * -3600);

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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and view_reports='1';";
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

$stmt="SELECT full_name,modify_timeclock_log from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$modify_timeclock_log =		$row[1];

$stmt="SELECT full_name,user_group from vicidial_users where user='" . mysqli_real_escape_string($link, $user) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$full_name = $row[0];
$user_group = $row[1];

$stmt="SELECT event,tcid_link from vicidial_timeclock_log where timeclock_id='" . mysqli_real_escape_string($link, $timeclock_id) . "';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$tc_logs_to_print = mysqli_num_rows($rslt);
if ($tc_logs_to_print > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$event =		$row[0];
	$tcid_link =	$row[1];
	}
if (preg_match('/LOGIN/',$event))
	{
	$LOGINevent_id =	$timeclock_id;
	$LOGOUTevent_id =	$tcid_link;
	if ( (preg_match('/NULL/',$LOGOUTevent_id)) or (strlen($LOGOUTevent_id)<1) )
		{$invalid_record++;}
	}
if (preg_match('/LOGOUT/',$event))
	{
	$LOGOUTevent_id =	$timeclock_id;
	$stmt="SELECT timeclock_id from vicidial_timeclock_log where tcid_link='" . mysqli_real_escape_string($link, $timeclock_id) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$tc_logs_to_print = mysqli_num_rows($rslt);
	if ($tc_logs_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$LOGINevent_id =		$row[0];
		}
	if ( (preg_match('/NULL/',$LOGOUTevent_id)) or (strlen($LOGOUTevent_id)<1) )
		{$invalid_record++;}
	}
if (strlen($LOGOUTevent_id)<1)
	{$invalid_record++;}

### 
if ($invalid_record < 1)
	{
	$stmt="SELECT event_epoch,event_date,login_sec,event,user,user_group,ip_address,shift_id,notes,manager_user,manager_ip,event_datestamp from vicidial_timeclock_log where timeclock_id='$LOGINevent_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$tc_logs_to_print = mysqli_num_rows($rslt);
	if ($tc_logs_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$LOGINevent_epoch =		$row[0];
		$LOGINevent_date =		$row[1];
		$LOGINlogin_sec =		$row[2];
		$LOGINevent =			$row[3];
		$LOGINuser =			$row[4];
		$LOGINuser_group =		$row[5];
		$LOGINip_address =		$row[6];
		$LOGINshift_id =		$row[7];
		$LOGINnotes =			$row[8];
		$LOGINmanager_user =	$row[9];
		$LOGINmanager_ip =		$row[10];
		$LOGINevent_datestamp =	$row[11];
		}
	$stmt="SELECT event_epoch,event_date,login_sec,event,user,user_group,ip_address,shift_id,notes,manager_user,manager_ip,event_datestamp from vicidial_timeclock_log where timeclock_id='$LOGOUTevent_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$tc_logs_to_print = mysqli_num_rows($rslt);
	if ($tc_logs_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$LOGOUTevent_epoch =	$row[0];
		$LOGOUTevent_date =		$row[1];
		$LOGOUTlogin_sec =		$row[2];
		$LOGOUTevent =			$row[3];
		$LOGOUTuser =			$row[4];
		$LOGOUTuser_group =		$row[5];
		$LOGOUTip_address =		$row[6];
		$LOGOUTshift_id =		$row[7];
		$LOGOUTnotes =			$row[8];
		$LOGOUTmanager_user =	$row[9];
		$LOGOUTmanager_ip =		$row[10];
		$LOGOUTevent_datestamp =$row[11];
		}

	$user=$LOGINuser;
	}



?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: Timeclock Record Edit"); ?>
<?php

##### BEGIN Set variables to make header show properly #####
$ADD =					'3';
$hh =					'users';
$TCedit_javascript =	'1';
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

require("admin_header.php");

?>


<CENTER>
<TABLE WIDTH=720 BGCOLOR=#D9E6FE cellpadding=2 cellspacing=0><TR BGCOLOR=#015B91><TD ALIGN=LEFT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Timeclock Record Edit for"); ?> <?php echo $user ?></TD><TD ALIGN=RIGHT> &nbsp; </TD></TR>

<?php 

echo "<TR BGCOLOR=\"#F0F5FE\"><TD ALIGN=LEFT COLSPAN=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3><B> &nbsp; \n";





##### BEGIN TIMECLOCK RECORD MODIFY #####

if ( ($invalid_record < 1) or (strlen($timeclock_id)<1) )
	{
	if ($stage == "edit_TC_log")
		{
		$log_time = ($LOGOUTepoch - $LOGINepoch);
		$NEXTevent_epoch = $StarTtimE;
		$PREVevent_epoch = 0;

		$stmt="SELECT event_epoch,timeclock_id from vicidial_timeclock_log where timeclock_id > '$LOGOUTevent_id' and user='$user' order by timeclock_id limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$tc_logs_to_print = mysqli_num_rows($rslt);
		if ($tc_logs_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$NEXTevent_epoch =	$row[0];
			$NEXTevent_id =		$row[1];
			}
		$stmt="SELECT event_epoch,timeclock_id from vicidial_timeclock_log where timeclock_id < '$LOGINevent_id' and user='$user' order by timeclock_id desc limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$tc_logs_to_print = mysqli_num_rows($rslt);
		if ($tc_logs_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$PREVevent_epoch =	$row[0];
			$PREVevent_id =		$row[1];
			}

		if ( ($LOGINepoch <= $PREVevent_epoch) || ($LOGOUTepoch >= $NEXTevent_epoch) )
			{
			echo _QXZ("ERROR- There is a problem with the data that you entered, please go back")."<BR>\n";
			echo _QXZ("A timeclock session cannot overlap another timeclock session")."<BR>\n";
			echo "$LOGINepoch<BR>\n";
			echo "$LOGOUTepoch<BR>\n";
			echo "$LOGINevent_id<BR>\n";
			echo "$LOGOUTevent_id<BR>\n";
			echo "$LOGINuser<BR>\n";
			echo "$PREVevent_epoch<BR>\n";
			echo "$PREVevent_id<BR>\n";
			echo "$NEXTevent_epoch<BR>\n";
			echo "$NEXTevent_id<BR>\n";
			exit;
			}
		if ( ($LOGINepoch > $StarTtimE) || ($LOGOUTepoch > $StarTtimE) || ($log_time > 86400) || ($log_time < 1) )
			{
			echo _QXZ("ERROR- There is a problem with the data that you entered, please go back")."<BR>\n";
			echo "$LOGINepoch<BR>\n";
			echo "$LOGOUTepoch<BR>\n";
			echo "$notes<BR>\n";
			echo "$LOGINevent_id<BR>\n";
			echo "$LOGOUTevent_id<BR>\n";
			echo "$LOGINuser<BR>\n";
			exit;
			}
		else
			{
			$LOGINdatetime = date("Y-m-d H:i:s", $LOGINepoch);
			$LOGOUTdatetime = date("Y-m-d H:i:s", $LOGOUTepoch);

			### update LOGIN record in the timeclock log
			$stmtA="UPDATE vicidial_timeclock_log set event_epoch='$LOGINepoch', event_date='$LOGINdatetime', manager_user='$PHP_AUTH_USER', manager_ip='$ip', notes='Manager MODIFY', login_sec='$log_time' where timeclock_id='$LOGINevent_id';";
			if ($DB) {echo "$stmtA\n";}
			$rslt=mysql_to_mysqli($stmtA, $link);
			$affected_rows = mysqli_affected_rows($link);
			$timeclock_id = mysqli_insert_id($link);
			print "<!-- UPDATE vicidial_timeclock_log record updated for $user:   |$affected_rows|$timeclock_id| -->\n";

			### Add a record to the vicidial_admin_log
			$SQL_log = "$stmtA|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='TIMECLOCK', event_type='MODIFY', record_id='$LOGINevent_id', event_code='MANAGER MODIFY TIMECLOCK LOG', event_sql=\"$SQL_log\", event_notes='user: $user|$oldLOGINepoch|$oldLOGINdate|sec: $log_time|';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);
			print "<!-- NEW vicidial_admin_log record inserted for $PHP_AUTH_USER:   |$affected_rows| -->\n";

			### update LOGOUT record in the timeclock log
			$stmtB="UPDATE vicidial_timeclock_log set event_epoch='$LOGOUTepoch', event_date='$LOGOUTdatetime', manager_user='$PHP_AUTH_USER', manager_ip='$ip', notes='Manager MODIFY', login_sec='$log_time' where timeclock_id='$LOGOUTevent_id';";
			if ($DB) {echo "$stmtB\n";}
			$rslt=mysql_to_mysqli($stmtB, $link);
			$affected_rows = mysqli_affected_rows($link);
			$timeclock_id = mysqli_insert_id($link);
			print "<!-- UPDATE vicidial_timeclock_log record updated for $user:   |$affected_rows|$timeclock_id| -->\n";

			### Add a record to the vicidial_admin_log
			$SQL_log = "$stmtB|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='TIMECLOCK', event_type='MODIFY', record_id='$LOGOUTevent_id', event_code='MANAGER MODIFY TIMECLOCK LOG', event_sql=\"$SQL_log\", event_notes='user: $user|$oldLOGOUTepoch|$oldLOGOUTdate|sec: $log_time|';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);
			print "<!-- NEW vicidial_admin_log record inserted for $PHP_AUTH_USER:   |$affected_rows| -->\n";

			echo _QXZ("The timeclock session has been updated").". <A HREF=\"$PHP_SELF?timeclock_id=$LOGINevent_id\">"._QXZ("Click here to view")."</A>.<BR>\n";
			exit;
			}
		}
	##### END TIMECLOCK RECORD MODIFY #####




	echo "\n<BR>";

	if ($modify_timeclock_log > 0)
		{
	#	$LOGINevent_id =	$timeclock_id;
	#	$LOGOUTevent_id =	$tcid_link;

		$event_hours = MathZDC($LOGINlogin_sec, 3600);
		$event_hours_int = round($event_hours, 2);
		$event_hours_int = intval("$event_hours_int");
		$event_minutes = ($event_hours - $event_hours_int);
		$event_minutes = ($event_minutes * 60);
		$event_minutes_int = round($event_minutes, 0);
		if ($event_minutes_int < 10) {$event_minutes_int = "0$event_minutes_int";}

		$stmt="SELECT full_name from vicidial_users where user='$LOGINuser';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$full_name =		$row[0];

		echo "<BR><BR>\n";
		echo "<form action=$PHP_SELF method=POST name=edit_log id=edit_log>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<input type=hidden name=user value=\"$user\">\n";
		echo "<input type=hidden name=stage value=edit_TC_log>\n";
		echo "<input type=hidden name=oldLOGINepoch id=oldLOGINepoch value=\"$LOGINevent_epoch\">\n";
		echo "<input type=hidden name=oldLOGOUTepoch id=oldLOGOUTepoch value=\"$LOGOUTevent_epoch\">\n";
		echo "<input type=hidden name=oldLOGINdate id=oldLOGINdate value=\"$LOGINevent_date\">\n";
		echo "<input type=hidden name=oldLOGOUTdate id=oldLOGOUTdate value=\"$LOGOUTevent_date\">\n";
		echo "<input type=hidden name=LOGINepoch id=LOGINepoch value=\"$LOGINevent_epoch\">\n";
		echo "<input type=hidden name=LOGOUTepoch id=LOGOUTepoch value=\"$LOGOUTevent_epoch\">\n";
		echo "<input type=hidden name=LOGINevent_id id=LOGINevent_id value=\"$LOGINevent_id\">\n";
		echo "<input type=hidden name=LOGOUTevent_id id=LOGOUTevent_id value=\"$LOGOUTevent_id\">\n";
		echo "<input type=hidden name=stage value=edit_TC_log>\n";
		echo "<TABLE BORDER=0><TR><TD COLSPAN=3 ALIGN=LEFT>\n";
		echo " &nbsp; &nbsp; &nbsp; &nbsp;"._QXZ("USER").": $LOGINuser ($full_name) &nbsp; &nbsp; &nbsp; &nbsp; \n";
		echo _QXZ("HOURS").": <span name=login_time id=login_time> $event_hours_int:$event_minutes_int </span>\n";
		echo "</TD></TR>\n";
		echo "<TR><TD>\n";
		echo "<TABLE BORDER=0>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("LOGIN TIME").": </TD><TD ALIGN=RIGHT><input type=text name=LOGINbegin_date id=LOGINbegin_date value=\"$LOGINevent_date\" size=20 maxlength=20 onchange=\"calculate_hours();\"></TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("TIMECLOCK ID").": </TD><TD ALIGN=RIGHT>$LOGINevent_id</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("USER GROUP").": </TD><TD ALIGN=RIGHT>$LOGINuser_group</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("IP ADDRESS").": </TD><TD ALIGN=RIGHT>$LOGINip_address</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("MANAGER USER").": </TD><TD ALIGN=RIGHT>$LOGINmanager_user</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("MANAGER IP").": </TD><TD ALIGN=RIGHT>$LOGINmanager_ip</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("NOTES").": </TD><TD ALIGN=RIGHT>$LOGINnotes</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("LAST CHANGE").": </TD><TD ALIGN=RIGHT>$LOGINevent_datestamp</TD></TR>\n";
		echo "</TABLE>\n";

		echo "</TD><TD> &nbsp; &nbsp; &nbsp; &nbsp; \n";
		echo "</TD><TD>\n";
		echo "<TABLE BORDER=0>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("LOGOUT TIME").": </TD><TD ALIGN=RIGHT><input type=text name=LOGOUTbegin_date id=LOGOUTbegin_date value=\"$LOGOUTevent_date\" size=20 maxlength=20 onchange=\"calculate_hours();\"></TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("TIMECLOCK ID").": </TD><TD ALIGN=RIGHT>$LOGOUTevent_id</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("USER GROUP").": </TD><TD ALIGN=RIGHT>$LOGOUTuser_group</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("IP ADDRESS").": </TD><TD ALIGN=RIGHT>$LOGOUTip_address</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("MANAGER USER").": </TD><TD ALIGN=RIGHT>$LOGOUTmanager_user</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("MANAGER IP").": </TD><TD ALIGN=RIGHT>$LOGOUTmanager_ip</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("NOTES").": </TD><TD ALIGN=RIGHT>$LOGOUTnotes</TD></TR>\n";
		echo "<TR><TD ALIGN=RIGHT>"._QXZ("LAST CHANGE").": </TD><TD ALIGN=RIGHT>$LOGOUTevent_datestamp</TD></TR>\n";
		echo "</TABLE>\n";
		echo "</TD></TR>\n";

		echo "<TR><TD COLSPAN=3 ALIGN=LEFT>\n";
		echo _QXZ("NEW NOTES").": <input type=text name=notes value='' size=80 maxlength=255>\n";
		echo "</TD></TR>\n";
		echo "<TR><TD COLSPAN=3 ALIGN=CENTER>\n";
		echo "<input style='background-color:#$SSbutton_color' type=button name=go_submit id=go_submit value="._QXZ("SUBMIT")." onclick=\"run_submit();\"><BR></form>\n";
		echo "</TD></TR></TABLE>\n";
		echo "<BR><BR>\n";
		}


	echo "<a href=\"./AST_agent_time_sheet.php?agent=$user\">"._QXZ("Agent Time Sheet")."</a>\n";
	echo " - <a href=\"./user_stats.php?user=$user\">"._QXZ("User Stats")."</a>\n";
	echo " - <a href=\"./admin.php?ADD=3&user=$user\">"._QXZ("Modify User")."</a>\n";

	echo "</B></TD></TR>\n";
	echo "<TR><TD ALIGN=LEFT COLSPAN=2>\n";


	$ENDtime = date("U");

	$RUNtime = ($ENDtime - $StarTtimE);

	echo "\n\n\n<br><br><br>\n\n";


	echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";

	echo "|$stage|$group|";
	}
else
	{
	echo _QXZ("ERROR! You cannot edit this timeclock record").": $timeclock_id\n";
	}
?>


</TD></TR><TABLE>
</body>
</html>

<?php
	
exit; 

?>

