<?php
# admin_launch_trigger.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to launch a CLI script on the voicemail server through
# the triggering process
#
# CHANGELOG:
# 161016-2014 - First build of script based upon admin_NANPA_updater.php
# 170409-1543 - Added IP List validation code
#

$version = '2.14-2';
$build = '170409-1543';
$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$server_ip=$WEBserver_ip;
$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["container_id"]))			{$container_id=$_GET["container_id"];}
	elseif (isset($_POST["container_id"]))	{$container_id=$_POST["container_id"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}

$block_scheduling_while_running=0;

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,active_voicemail_server,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$active_voicemail_server =		$row[4];
	$SSenable_languages =			$row[5];
	$SSlanguage_method =			$row[6];
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

$container_id = preg_replace('/[^-_0-9a-zA-Z]/','',$container_id);

$NOW_DATE = date("Y-m-d");

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$user_auth=0;
$auth=0;
$reports_auth=0;
$qc_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$user_auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

if ($user_auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 8;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 1 and qc_enabled='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$qc_auth=$row[0];

	$reports_only_user=0;
	$qc_only_user=0;
	if ( ($reports_auth > 0) and ($auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	if ( ($qc_auth > 0) and ($reports_auth < 1) and ($auth < 1) )
		{
		if ( ($ADD != '881') and ($ADD != '100000000000000') )
			{
            $ADD=100000000000000;
			}
		$qc_only_user=1;
		}
	if ( ($qc_auth < 1) and ($reports_auth < 1) and ($auth < 1) )
		{
		$VDdisplayMESSAGE = _QXZ("You do not have permission to be here");
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

if (strlen($active_voicemail_server)<7)
	{
	echo _QXZ("ERROR: Admin -> System Settings -> Active Voicemail Server is not set")."\n";
	exit;
	}



header ("Content-type: text/html; charset=utf-8");
if ($SSnocache_admin=='1')
	{
	header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
	header ("Pragma: no-cache");                          // HTTP/1.0
	}

echo "<html>\n";
echo "<head>\n";
echo "<!-- VERSION: $admin_version   BUILD: $build   ADD: $ADD   PHP_SELF: $PHP_SELF-->\n";
echo "<META NAME=\"ROBOTS\" CONTENT=\"NONE\">\n";
echo "<META NAME=\"COPYRIGHT\" CONTENT=\"&copy; 2016 ViciDial Group\">\n";
echo "<META NAME=\"AUTHOR\" CONTENT=\"ViciDial Group\">\n";
if ($SSnocache_admin=='1')
	{
	echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
	echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">\n";
	echo "<META HTTP-EQUIV=\"CACHE-CONTROL\" CONTENT=\"NO-CACHE\">\n";
	}
if ( ($SSadmin_modify_refresh > 1) and (preg_match("/^3/",$ADD)) )
	{
	$modify_refresh_set=1;
	if (preg_match("/^3/",$ADD)) {$modify_url = "$PHP_SELF?$QUERY_STRING";}
	echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$SSadmin_modify_refresh;URL=$modify_url\">\n";
	}
echo "<title>"._QXZ("ADMIN LAUNCH TRIGGER")."</title>";
echo "</head>\n";
$ADMIN=$PHP_SELF;
$short_header=1;


echo "\n<BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

require("admin_header.php");



if ( ($stage=="LAUNCH") && (strlen($container_id) > 1) ) 
	{
	$stmt="SELECT count(*) from vicidial_settings_containers where container_id='$container_id';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$container_ct=$row[0];

	if ($container_ct < 1)
		{
		echo "ERROR: Container does not exist: $container_id";
		exit;
		}

	$options="--container=$container_id ";
	
	$uniqueid=rand(1, 9999);
	$TRIGGERdate = date("YmdHis");

	$ins_stmt="INSERT into vicidial_process_triggers (trigger_id, trigger_name, server_ip, trigger_time, trigger_run, user, trigger_lines) VALUES('D".$TRIGGERdate."X".$uniqueid."', 'TEST Send Manual Trigger', '$active_voicemail_server', now()+INTERVAL 2 MINUTE, '1', '$PHP_AUTH_USER', '/usr/share/astguiclient/TEST_script.pl $options')";
	$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
	$affected_rows = mysqli_affected_rows($link);

	echo "<BR><BR><B>Process D".$TRIGGERdate."X".$uniqueid." has been triggered!<BR>It will start in 2 minutes using Container $container_id.($affected_rows)</B><BR><BR>\n";
	if ($DB) {echo "DEBUG: |$ins_stmt|<BR><BR>\n";}
	}


echo "<form action='$PHP_SELF' method='get'><input type=hidden name=stage value='LAUNCH'>\n";

echo "<BR><BR>Custom Process trigger page for TEST. Select a Container below and click SUBMIT to launch the process.<BR><BR>\n";

echo "<BR>	<table align=left width='770' border=1 cellpadding=0 cellspacing=0 bgcolor=#D9E6FE>";

echo "<tr bgcolor=white><td align=right>"._QXZ("Container").": </td><td align=left><select size=1 name=container_id>\n";

$stmt="SELECT container_id,container_notes from vicidial_settings_containers order by container_id;";
$rslt=mysql_to_mysqli($stmt, $link);
$containers_to_print = mysqli_num_rows($rslt);
$containers_list='';

$o=0;
while ($containers_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$containers_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$o++;
	}
echo "$containers_list";
echo "<option SELECTED value=\"X\">--- PLEASE SELECT A CONTAINER ---</option>\n";
echo "</select></td></tr>\n";
echo "<tr bgcolor=#$SSstd_row4_background><td align=center colspan=2><input type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'></td></tr>\n";
echo "</TABLE></center></form>\n";

echo "</td></tr>";
echo "</table>";
echo "</td></tr>";

echo "</table>";
echo "</form>";
echo "</body>";
echo "</html>";
?>
