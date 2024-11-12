<?php
# VERM_AJAX_functions.php - Vicidial Enhanced Reporting AJAX functions page
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1608 - First build
# 240709-2151 - Added input variable filtering
# 240801-1130 - Code updates for PHP8 compatibility
#

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

$startMS = microtime();
$STARTtime = date("U");

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["function"]))			{$function=$_GET["function"];}
	elseif (isset($_POST["function"]))	{$function=$_POST["function"];}
if (isset($_GET["custom_report_vars"]))			{$custom_report_vars=$_GET["custom_report_vars"];}
	elseif (isset($_POST["custom_report_vars"]))	{$custom_report_vars=$_POST["custom_report_vars"];}
if (isset($_GET["custom_report_name"]))			{$custom_report_name=$_GET["custom_report_name"];}
	elseif (isset($_POST["custom_report_name"]))	{$custom_report_name=$_POST["custom_report_name"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,agent_whisper_enabled,report_default_format,enable_pause_code_limits,allow_web_debug,admin_screen_colors,admin_web_directory FROM system_settings;";
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
	$agent_whisper_enabled =		$row[6];
	$SSreport_default_format =		$row[7];
	$SSenable_pause_code_limits =	$row[8];
	$SSallow_web_debug =			$row[9];
	$SSadmin_screen_colors =		$row[10];
	$SSadmin_web_directory =		$row[11];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$function = preg_replace('/[^\-_0-9a-zA-Z]/','',$function);
	$custom_report_vars = preg_replace('/[^-\/\|\_\#\*\,\.\_\[\]0-9a-zA-Z]/','',$custom_report_vars);
	$custom_report_name = preg_replace('/[^\-_0-9a-zA-Z]/','',$custom_report_name);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$function = preg_replace('/[^-_0-9\p{L}/u','',$function);
	$custom_report_vars = preg_replace('/[^-\/\|\_\#\*\,\.\_\[\]0-9\p{L}]/u','',$custom_report_vars);
	$custom_report_name = preg_replace('/[^-_0-9\p{L}/u','',$custom_report_name);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);	
	}


##### BEGIN log visit to the vicidial_report_log table #####
$LOGip = getenv("REMOTE_ADDR");
$LOGbrowser = getenv("HTTP_USER_AGENT");
$LOGscript_name = getenv("SCRIPT_NAME");
$LOGserver_name = getenv("SERVER_NAME");
$LOGserver_port = getenv("SERVER_PORT");
$LOGrequest_uri = getenv("REQUEST_URI");
$LOGhttp_referer = getenv("HTTP_REFERER");
$LOGbrowser=preg_replace("/<|>|\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/<|>|\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/<|>|\'|\"|\\\\/","",$LOGhttp_referer);
if (preg_match("/443/i",$LOGserver_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) {$LOGserver_port='';}
else {$LOGserver_port = ":$LOGserver_port";}
$LOGfull_url = "$HTTPprotocol$LOGserver_name$LOGserver_port$LOGrequest_uri";

$LOGhostname = php_uname('n');
if (strlen($LOGhostname)<1) {$LOGhostname='X';}
if (strlen($LOGserver_name)<1) {$LOGserver_name='X';}


if ($function=="log_custom_report")
	{
	$rpt_log_stmt="insert ignore into verm_custom_report_holder(user, report_name, report_parameters) values('$PHP_AUTH_USER', '$custom_report_name', '$LOGhttp_referer') ON DUPLICATE KEY UPDATE report_name='$custom_report_name', report_parameters='$custom_report_vars'";
	$rpt_log_rslt=mysql_to_mysqli($rpt_log_stmt, $link);
	return mysqli_affected_rows($link);
	}


?>
