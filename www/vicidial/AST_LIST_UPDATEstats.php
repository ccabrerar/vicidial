<?php 
# AST_LIST_UPDATEstats.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 90627-2055 - First build
# 90907-0636 - Added list id to results
# 100712-1324 - Added system setting slave server option
# 100802-2347 - Added User Group Allowed Reports option validation
# 100914-1326 - Added lookup for user_level 7 users to set to reports only which will remove other admin links
# 130414-0201 - Added report logging
# 130610-1003 - Finalized changing of all ereg instances to preg
# 130621-0737 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2026 - Changed to mysqli PHP functions
# 140108-0735 - Added webserver and hostname to report logging
# 141114-0830 - Finalized adding QXZ translation to all admin files
# 141230-1445 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 220302-1324 - Added allow_web_debug system setting
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
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = "$NOW_DATE 00:00:00";}
if (!isset($end_date)) {$end_date = "$NOW_DATE 23:59:59";}
if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'List Update Stats';
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

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $end_date);
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: black; background-color: #99FF99}
   .red {color: black; background-color: #FF9999}
   .orange {color: black; background-color: #FFCC99}
-->
 </STYLE>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	$short_header=1;

	require("admin_header.php");

if ($DB > 0)
	{
	echo "<BR>\n";
	echo "$query_date|$end_date\n";
	echo "<BR>\n";
	}

echo "<b>"._QXZ("$report_name")."</b> $NWB#LISTUPDATE_stats$NWE\n";
echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<TABLE BORDER=0 CELLPADDING=2 CELLSPACING=2><TR><TD align=center valign=top>\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=20 MAXLENGTH=20 VALUE=\"$query_date\">\n";
echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=20 MAXLENGTH=20 VALUE=\"$end_date\">\n";
echo "</TD><TD align=center valign=top>\n";
echo "</TD><TD align=center valign=top>\n";
echo "</TD><TD align=center valign=top>\n";
echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
echo "<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;  <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</TD></TR></TABLE>\n";
echo "</FORM>\n";

echo "<PRE><FONT SIZE=2>";


if (!$query_date)
{
echo "\n\n";
echo _QXZ("PLEASE SELECT A DATE RANGE ABOVE AND CLICK SUBMIT")."\n";
}

else
{
echo _QXZ("List Update Process Report",46)." $NOW_TIME\n";
echo "\n";
echo _QXZ("Time range")." $query_date "._QXZ("to")." $end_date\n\n";


### GRAB ALL RECORDS WITHIN RANGE FROM THE DATABASE ###
echo _QXZ("List Update Summary").":\n";
echo "+----------------------------------------------------+------------+----------+----------+\n";
echo "| "._QXZ("FILENAME",50)." | "._QXZ("RESULT",10)." | "._QXZ("RECORDS",8)." | "._QXZ("UPDATES",8)." |\n";
echo "+----------------------------------------------------+------------+----------+----------+\n";

$stmt="SELECT count(*),filename,result,sum(result_rows) from vicidial_list_update_log where event_date >= '$query_date' and event_date <= '$end_date' group by filename,result order by filename,result;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$records_to_grab = mysqli_num_rows($rslt);
$i=0;
while ($i < $records_to_grab)
	{
	$row=mysqli_fetch_row($rslt);
	$TOTcount = ($TOTcount + $row[0]);
	$TOTupdated = ($TOTupdated + $row[3]);
	$count =	sprintf("%-8s", $row[0]);
	$filename =	sprintf("%-50s", trim($row[1]));
	$result =	sprintf("%-10s", $row[2]);
	$updated =	sprintf("%-8s", $row[3]);
	echo "| $filename | $result | $count | $updated |\n";

	$i++;
	}
$TOTcount =	sprintf("%-8s", $TOTcount);
$TOTupdated =	sprintf("%-8s", $TOTupdated);

echo "+----------------------------------------------------+------------+----------+----------+\n";
echo "                                                                  | $TOTcount | $TOTupdated |\n";
echo "                                                                  +----------+----------+\n";
echo "\n</PRE>\n";



### GRAB ALL RECORDS WITHIN RANGE FROM THE DATABASE ###
echo _QXZ("List Update Detail").":<BR>\n";
echo "<TABLE CELLPADDING=1 CELLSPACING=1 BORDER=1>\n";
echo "<TR><TD><B>"._QXZ("DATE/TIME")."</B></TD><TD><B>"._QXZ("LEAD_ID")."</B></TD><TD><B>"._QXZ("VENDOR_ID")."</B></TD><TD><B>"._QXZ("PHONE")."</B></TD><TD><B>"._QXZ("STATUS")."</B></TD><TD><B>"._QXZ("OLD_STATUS")."</B></TD><TD><B>"._QXZ("OLD_LIST")."</B></TD><TD><B>"._QXZ("FILENAME")."</B></TD><TD><B>"._QXZ("RESULT")."</B></TD><TD><B>"._QXZ("UPDATED_ROWS")."</B></TD>\n";
echo "</TR>\n";


$stmt="SELECT event_date,lead_id,vendor_id,phone_number,status,old_status,filename,result,result_rows,list_id from vicidial_list_update_log where event_date >= '$query_date' and event_date <= '$end_date' order by event_date,filename;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$records_to_grab = mysqli_num_rows($rslt);
$i=0;
while ($i < $records_to_grab)
	{
	$row=mysqli_fetch_row($rslt);
	$row[6] = trim($row[6]);
	echo "<TR><TD>$row[0]</TD><TD>$row[1]</TD><TD>$row[2]</TD><TD>$row[3]</TD><TD>$row[4]</TD><TD>$row[5]</TD><TD>$row[9]</TD><TD>$row[6]</TD><TD>$row[7]</TD><TD>$row[8]</TD></TR>\n";
	$i++;
	}




$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "</TABLE><BR>\n"._QXZ("Run Time").": $RUNtime "._QXZ("seconds")."|$db_source\n";
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

</BODY></HTML>
