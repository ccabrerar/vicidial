<?php
# group_hourly_stats.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60620-1014 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 90310-2138 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 120221-0159 - Added User Group restrictions
# 120223-2135 - Removed logging of good login passwords if webroot writable is enabled
# 130414-0224 - Added report logging
# 130610-0946 - Finalized changing of all ereg instances to preg
# 130619-2329 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-1929 - Changed to mysqli PHP functions
# 140108-0724 - Added webserver and hostname to report logging
# 141114-0043 - Finalized adding QXZ translation to all admin files
# 141230-1343 - Added code for on-the-fly language translations display
# 160325-1426 - Changes for sidebar update
# 170409-1539 - Added IP List validation code
# 220303-1547 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='User Group Hourly Stats';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["status"]))				{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))	{$status=$_POST["status"];}
if (isset($_GET["date_with_hour"]))				{$date_with_hour=$_GET["date_with_hour"];}
	elseif (isset($_POST["date_with_hour"]))	{$date_with_hour=$_POST["date_with_hour"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$date_with_hour_default = date("Y-m-d H");
$date_no_hour_default = $TODAY;
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
if (!isset($date_with_hour)) {$date_with_hour = $date_with_hour_default;}
	$date_no_hour = $date_with_hour;
	$date_no_hour = preg_replace('/\s([0-9]{2})/i','',$date_no_hour);
if (!isset($begin_date)) {$begin_date = $TODAY;}
if (!isset($end_date)) {$end_date = $TODAY;}

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

$group = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$group);
$status = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$status);
$date_with_hour = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$date_with_hour);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group, $status|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####


$stmt="SELECT full_name,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =		$row[0];
$LOGuser_group =	$row[1];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}


?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: Group Hourly Stats"); ?>
<?php

##### BEGIN Set variables to make header show properly #####
$ADD =					'311111';
$hh =					'usergroups';
$sh =					'hour';
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

require("admin_header.php");

?>


<CENTER>
<TABLE WIDTH=620 BGCOLOR=#D9E6FE cellpadding=2 cellspacing=0><TR BGCOLOR=#015B91><TD ALIGN=LEFT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B> &nbsp; <?php echo _QXZ("Group Hourly Stats"); ?> <?php echo $group ?></TD><TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B> &nbsp; </TD></TR>




<?php 

if ( ($group) and ($status) and ($date_with_hour) )
{
$stmt="SELECT user,full_name from vicidial_users where user_group = '" . mysqli_real_escape_string($link, $group) . "' $LOGadmin_viewable_groupsSQL order by full_name desc;";
	if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$tsrs_to_print = mysqli_num_rows($rslt);
	$o=0;
	while($o < $tsrs_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$VDuser[$o] = "$row[0]";
		$VDname[$o] = "$row[1]";
		$o++;
		}

	$o=0;
	while($o < $tsrs_to_print)
		{
		$stmt="select count(*) from vicidial_log where call_date >= '" . mysqli_real_escape_string($link, $date_with_hour) . ":00:00' and  call_date <= '" . mysqli_real_escape_string($link, $date_with_hour) . ":59:59' and user='$VDuser[$o]' $LOGadmin_viewable_groupsSQL;";
			if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$VDtotal[$o] = "$row[0]";

		$stmt="select count(*) from vicidial_log where call_date >= '" . mysqli_real_escape_string($link, $date_no_hour) . " 00:00:00' and  call_date <= '" . mysqli_real_escape_string($link, $date_no_hour) . " 23:59:59' and user='$VDuser[$o]' and status='" . mysqli_real_escape_string($link, $status) . "' $LOGadmin_viewable_groupsSQL;";
			if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$VDday[$o] = "$row[0]";

		$stmt="select count(*) from vicidial_log where call_date >= '" . mysqli_real_escape_string($link, $date_with_hour) . ":00:00' and  call_date <= '" . mysqli_real_escape_string($link, $date_with_hour) . ":59:59' and user='$VDuser[$o]' and status='" . mysqli_real_escape_string($link, $status) . "' $LOGadmin_viewable_groupsSQL;";
			if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$VDcount[$o] = "$row[0]";
		$o++;
		}

echo "<TR><TD ALIGN=LEFT COLSPAN=2>\n";

echo "<br><center>\n";

echo "<B>"._QXZ("TSR HOUR COUNTS").": <a href=\"./admin.php?ADD=3111&group_id=$group\">$group</a> | $status | $date_with_hour | $date_no_hour</B>\n";

echo "<center><TABLE width=600 cellspacing=0 cellpadding=1>\n";
echo "<tr><td><font size=2>"._QXZ("TSR")." </td><td align=left><font size=2>"._QXZ("ID")." </td><td align=right><font size=2> &nbsp; $status</td><td align=right><font size=2> &nbsp; "._QXZ("TOTAL CALLS")."</td><td align=right><font size=2> &nbsp; $status "._QXZ("DAY")."</td><td align=right><font size=2> &nbsp; &nbsp; </td></tr>\n";

	$day_calls=0;
	$hour_calls=0;
	$total_calls=0;
	$o=0;
	while($o < $tsrs_to_print)
		{
		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='bgcolor="#B9CBFD"';} 
		else
			{$bgcolor='bgcolor="#9BB9FB"';}
		echo "<tr $bgcolor><td><font size=2>$VDuser[$o]</td>";
		echo "<td align=left><font size=2> $VDname[$o]</td>\n";
		echo "<td align=right><font size=2> $VDcount[$o]</td>\n";
		echo "<td align=right><font size=2> $VDtotal[$o]</td>\n";
		echo "<td align=right><font size=2> $VDday[$o]</td>\n";
		echo "<td align=right><font size=1><a href=\"./admin.php?ADD=3&user=$VDuser[$o]\">"._QXZ("MODIFY")."</a> | <a href=\"./user_stats.php?user=$VDuser[$o]\">"._QXZ("STATS")."</a></td></tr>\n";
		$total_calls = ($total_calls + $VDtotal[$o]);
		$hour_calls = ($hour_calls + $VDcount[$o]);
		$day_calls = ($day_calls + $VDday[$o]);

		$o++;
		}

	echo "<tr><td><font size=2>"._QXZ("TOTAL")." </td><td align=right><font size=2> $status </td><td align=right><font size=2> $hour_calls</td><td align=right><font size=2> $total_calls</td><td align=right><font size=2> $day_calls</td></tr>\n";


	}

echo "</TABLE></center>\n";
echo "<br><br>\n";


echo "<br>"._QXZ("Please enter the group you want to get hourly stats for").": <form action=$PHP_SELF method=GET>\n";
echo "<input type=hidden name=DB value=$DB>\n";
echo _QXZ("group").": <select size=1 name=group>\n";

$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group";
$rslt=mysql_to_mysqli($stmt, $link);
$groups_to_print = mysqli_num_rows($rslt);
$o=0;
$groups_list='';
while ($groups_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	if ($group == $group)
		{$groups_list .= "<option selected value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";}
	else
		{$groups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";}
	$o++;
	}
echo "$groups_list</select><br>\n";
echo _QXZ("status").": <input type=text name=status size=10 maxlength=10 value=\"$status\"> &nbsp; ("._QXZ("example").": "._QXZ("XFER").")<br>\n";
echo _QXZ("date with hour").": <input type=text name=date_with_hour size=14 maxlength=13 value=\"$date_with_hour\"> &nbsp; ("._QXZ("example").": 2004-06-25 14)<br>\n";
echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("SUBMIT")."'>\n";
echo "<BR><BR><BR>\n";


$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";


?>


</TD></TR></TABLE>
</body>
</html>

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

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

exit; 

?>
