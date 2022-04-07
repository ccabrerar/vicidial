<?php 
# shared_debug.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 210207-0913 - First build
# 220227-1944 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='Shared Debug';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_date_T"]))			{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["stage"]))				{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))		{$stage=$_POST["stage"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($server_ip)) {$server_ip = '10.10.10.15';}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method,allow_shared_dial,allow_web_debug FROM system_settings;";
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
	$SSallow_shared_dial =			$row[6];
	$SSallow_web_debug =			$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^-_0-9a-zA-Z]/', '', $query_date);
$query_date_T = preg_replace('/[^- \:_0-9a-zA-Z]/', '', $query_date_T);
$submit = preg_replace('/[^-_0-9a-zA-Z]/',"",$submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/',"",$SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$stage = preg_replace('/[^-_0-9\p{L}]/u', '', $stage);
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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

$stmt="SELECT modify_campaigns,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGmodify_campaigns =	$row[0];
$LOGuser_group =		$row[1];

if ($LOGmodify_campaigns < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for campaign debugging").": |$PHP_AUTH_USER|\n";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url', webserver='$webserver_id';";
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
if ($stage == 'empty')
	{
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("Shared Debug Detail Start")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<PRE><FONT SIZE=2>\n";
	echo "</PRE>\n";
	echo "</BODY></HTML>\n";
	exit;
	}
if ($stage == 'detail')
	{
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("Shared Debug Detail")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<PRE><FONT SIZE=2>\n";
		echo _QXZ("Shared Agent Campaign Log Entries").":     $query_date_T\n";

	$stmt="select log_time,campaign_id,server_ip,total_agents,total_calls,debug_output,adapt_output from vicidial_shared_log where log_time='$query_date_T' and ( (debug_output!='') or (adapt_output!='') ) order by log_time desc,server_ip desc,campaign_id,total_agents limit 1000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$debugs_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($debugs_to_print > $i)
		{
		$row=mysqli_fetch_row($rslt);
		echo "Time:     $row[0]\n";
		echo "Campaign: $row[1]\n";
		echo "Server:   $row[2]\n";
		echo "Agents:   $row[3]\n";
		echo "Calls:    $row[4]\n";
		if (strlen($row[5])>0) {echo "Debug1:   $row[5]\n";}
		if (strlen($row[6])>0) {echo "Debug2:   $row[6]\n";}
		echo "\n";

		$i++;
		}

	$old_log_time =	$query_date_T;
	$stmt="select log_time from vicidial_shared_log where log_time < \"$query_date_T\" and ( (debug_output!='') or (adapt_output!='') ) and campaign_id='--ALL--' order by log_time desc limit 1;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$logs_to_print = mysqli_num_rows($rslt);
	if ($logs_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$old_log_time =		$row[0];
		}

	echo "\n"._QXZ("Older Shared Agent Campaign Log Entries").":     $row[0]\n\n";

	$stmt="select log_time,campaign_id,server_ip,total_agents,total_calls,debug_output,adapt_output from vicidial_shared_log where log_time < \"$query_date_T\" and log_time > \"$old_log_time\" and ( (debug_output!='') or (adapt_output!='') ) order by log_time desc,server_ip desc,campaign_id,total_agents limit 10000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$debugs_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($debugs_to_print > $i)
		{
		$row=mysqli_fetch_row($rslt);
		echo "Time:     $row[0]\n";
		echo "Campaign: $row[1]\n";
		echo "Server:   $row[2]\n";
		echo "Agents:   $row[3]\n";
		echo "Calls:    $row[4]\n";
		if (strlen($row[5])>0) {echo "Debug1:   $row[5]\n";}
		if (strlen($row[6])>0) {echo "Debug2:   $row[6]\n";}
		echo "\n";

		$i++;
		}

	echo "</PRE>\n";
	echo "</BODY></HTML>\n";
	exit;
	}
else
	{
	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("Shared Debug")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

		$short_header=1;

		require("admin_header.php");

	echo "<TABLE CELLPADDING=4 CELLSPACING=4 WIDTH=100%><TR><TD COLSPAN=2 border=1>";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET ID='vicidial_report' NAME='vicidial_report'>\n";
	echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
	echo "<script language=\"JavaScript\">\n";
	echo "var o_cal = new tcal ({\n";
	echo "	// form name\n";
	echo "	'formname': 'vicidial_report',\n";
	echo "	// input name\n";
	echo "	'controlname': 'query_date'\n";
	echo "});\n";
	echo "o_cal.a_tpl.yearscroll = false;\n";
	echo "// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
	echo "</script>\n";
	echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
	echo "</FORM>\n\n";
	echo "</TD></TR>\n\n";
	echo "<TR><TD ALIGN=LEFT VALIGN=TOP WIDTH=220>\n";

	echo "<PRE><FONT SIZE=2>\n\n";


	if (!$query_date)
		{
		echo "\n\n";
		echo _QXZ("PLEASE SELECT A DATE ABOVE AND CLICK SUBMIT")."\n";
		}

	else
		{
		echo "\n";
		echo "---- "._QXZ("SHARED LOG ENTRIES").":\n     $NOW_TIME\n";
		echo "\n";
		echo "<a href=\"$PHP_SELF?stage=detail&query_date_T=$NOW_TIME\" target=\"detail\">BUFFER ENTRIES</a>\n";

		$stmt="select distinct log_time,total_agents from vicidial_shared_log where log_time >= \"$query_date 00:00:00\" and log_time <= \"$query_date 23:59:59\" and ( (debug_output!='') or (adapt_output!='') ) and campaign_id='--ALL--' order by log_time desc limit 100000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$logs_to_print = mysqli_num_rows($rslt);
		$lct=0;
		while ($logs_to_print > $lct)
			{
			$row=mysqli_fetch_row($rslt);
			$log_time =			$row[0];
			$total_agents =		$row[1];

			echo "<a href=\"$PHP_SELF?stage=detail&query_date_T=$log_time\" target=\"detail\">$log_time - $total_agents</a>\n";
			$lct++;
			}
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

	</PRE>

	</TD><TD ALIGN=LEFT VALIGN=TOP>
	<iframe src="<?php echo $PHP_SELF ?>?stage=empty" style="width:1000px;height:1000px;background-color:transparent;z-index:10;" scrolling="auto" frameborder="1" allowtransparency="true" id="detail" name="detail" width="1000px" height="1000px" onload="window.parent.parent.scrollTo(0,0)"> </iframe>
	</TD></TR></TABLE>

	</BODY></HTML>
	<?php
	}
exit;
