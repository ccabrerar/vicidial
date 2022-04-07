<?php 
# bench_agent_assign.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 190815-2242 - First build
# 220224-1753 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='Bench Agent Assign';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$ip = getenv("REMOTE_ADDR");
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["absent_agent"]))			{$absent_agent=$_GET["absent_agent"];}
	elseif (isset($_POST["absent_agent"]))	{$absent_agent=$_POST["absent_agent"];}
if (isset($_GET["bench_agent"]))			{$bench_agent=$_GET["bench_agent"];}
	elseif (isset($_POST["bench_agent"]))	{$bench_agent=$_POST["bench_agent"];}
if (isset($_GET["list_id"]))			{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))	{$list_id=$_POST["list_id"];}
if (isset($_GET["server_ip"]))			{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

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

$list_id = preg_replace('/[^0-9]/', '', $list_id);
$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/', '', $server_ip);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$absent_agent = preg_replace('/[^-_0-9a-zA-Z]/', '', $absent_agent);
	$bench_agent = preg_replace('/[^-_0-9a-zA-Z]/', '', $bench_agent);
	$group = preg_replace('/[^-_0-9a-zA-Z]/', '', $group);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$absent_agent = preg_replace('/[^-_0-9\p{L}]/u', '', $absent_agent);
	$bench_agent = preg_replace('/[^-_0-9\p{L}]/u', '', $bench_agent);
	$group = preg_replace('/[^-_0-9\p{L}]/u', '', $group);
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
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 8 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 8 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	if ($reports_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to assign bench agents");
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

$stmt="SELECT modify_leads,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGmodify_leads =	$row[0];
$LOGuser_group =		$row[1];

if ($LOGmodify_leads < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify leads").": |$PHP_AUTH_USER|\n";
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

if ($db_source == 'S')
	{
	mysqli_close($link);
	$use_slave_server=0;
	$db_source = 'M';
	require("dbconnect_mysqli.php");
	}


$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILEDATE = date("YmdHis");

$STARTtime = date("U");

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
echo "<TITLE>"._QXZ("Bench Agent Assign")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

	require("admin_header.php");


if ( (strlen($absent_agent) < 1) or (strlen($bench_agent) < 1) )
	{
	$owner_menu="<option value=\"\">--- "._QXZ("SELECT ABSENT AGENT HERE")." ---</option>\n";
	$list_idSQL='';
	$owners_to_print=0;
	if (strlen($list_id) > 1) {$list_idSQL = "and list_id='$list_id'";}
	$stmt="SELECT owner,count(*) from vicidial_list where owner!='' $list_idSQL group by owner order by owner;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	if ($rslt) {$owners_to_print = mysqli_num_rows($rslt);}
	$i=0;
	while ($i < $owners_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$owners[$i] =			$row[0];
		$owners_count[$i] =		$row[1];
		$i++;
		}

	$i=0;
	while ($i < $owners_to_print)
		{
		$users_to_print=0;
		$stmt="SELECT user,full_name from vicidial_users where user='$owners[$i]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		if ($rslt) {$users_to_print = mysqli_num_rows($rslt);}
		if ($users_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$owners_name[$i] =	$row[1];
			}
		$owner_menu .= "<option value=\"$owners[$i]\">$owners[$i] - $owners_name[$i] - $owners_count[$i]</option>\n";
		$i++;
		}

	$bench_menu="<option value=\"\">--- "._QXZ("SELECT BENCH AGENT HERE")." ---</option>\n";
	$benches_to_print=0;
	$stmt="SELECT user,full_name from vicidial_users where active='Y'  order by user;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	if ($rslt) {$benches_to_print = mysqli_num_rows($rslt);}
	$j=0;
	while ($j < $benches_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$users[$j] =			$row[0];
		$users_name[$j] =		$row[1];
		$bench_menu .= "<option value=\"$users[$j]\">$users[$j] - $users_name[$j]</option>\n";
		$j++;
		}

	echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
	echo _QXZ("Absent Agent").": <SELECT SIZE=1 NAME=absent_agent>\n";
	echo "$owner_menu";
	echo "</SELECT>\n";

	echo " &nbsp; &nbsp; \n";

	echo _QXZ("Bench Agent").": <SELECT SIZE=1 NAME=bench_agent>\n";
	echo "$bench_menu";
	echo "</SELECT>\n";

	echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
	echo "</FORM>\n\n";

	echo "<PRE><FONT SIZE=2>\n\n";

	echo "\n\n";
	echo _QXZ("PLEASE SELECT AN ABSENT AGENT AND A BENCH AGENT AND CLICK SUBMIT")."\n";
	}

else
	{
	echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";

	echo _QXZ("ABSENT AGENT").": $absent_agent \n";
	echo _QXZ("BENCH AGENT").":  $bench_agent \n";

	$leads_to_print=0;
	if (strlen($list_id) > 1) {$list_idSQL = "and list_id='$list_id'";}
	$stmt="SELECT lead_id from vicidial_list where owner='$absent_agent' $list_idSQL order by lead_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	if ($rslt) {$leads_to_print = mysqli_num_rows($rslt);}
	$j=0;
	while ($j < $leads_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$owner_lead_ids[$j] =	$row[0];
		$j++;
		}

	$LOGaffected_rows=0;
	$UPDATEaffected_rows=0;
	$j=0;
	while ($j < $leads_to_print)
		{
		$stmt="UPDATE vicidial_list set owner='$bench_agent' where lead_id='$owner_lead_ids[$j]';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$tempUPDATEaffected_rows = mysqli_affected_rows($link);
		if ($DB) {echo "$tempUPDATEaffected_rows|$stmt|\n";}
		if ($tempUPDATEaffected_rows > 0)
			{
			$UPDATEaffected_rows = ($UPDATEaffected_rows + $tempUPDATEaffected_rows);

			$stmt="INSERT INTO vicidial_bench_agent_log set lead_id='$owner_lead_ids[$j]', bench_date='$NOW_TIME', absent_agent='$absent_agent', bench_agent='$bench_agent', user='$PHP_AUTH_USER';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$LOGaffected_rows = ($LOGaffected_rows + mysqli_affected_rows($link));
			if ($DB) {echo "$LOGaffected_rows|$stmt|\n";}
			}

		$j++;
		}

	if ($UPDATEaffected_rows > 0)
		{
		### LOG INSERTION Admin Log Table ###
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$absent_agent', event_code='ADMIN BENCH AGENT MODIFY', event_sql=\"\", event_notes='absent agent: $absent_agent   bench_agent: $bench_agent   leads updated: $UPDATEaffected_rows';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}

	echo _QXZ("Process complete, records updated").":  $UPDATEaffected_rows <br>\n";
	echo "<a href=\"$PHP_SELF\">"._QXZ("Click here to go back to the bench agent assign screen")."</a> <br>\n";
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

</TD></TR></TABLE>

</BODY></HTML>
