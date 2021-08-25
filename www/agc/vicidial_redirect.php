<?php
# vicidial_redirect.php - forwards agents to another URL for vicidial.php login
# 
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 71202-1546 - First Build 
# 90508-0727 - Changed to PHP long tags
# 140811-0852 - Changed to use QXZ function for echoing text
# 141216-2131 - Added language settings lookups and user/pass variable standardization
# 210823-1004 - Fix for security issue
#

require_once("functions.php");
require_once("dbconnect_mysqli.php");

if (isset($_GET["DB"]))						    {$DB=$_GET["DB"];}
        elseif (isset($_POST["DB"]))            {$DB=$_POST["DB"];}
if (isset($_GET["phone_login"]))                {$phone_login=$_GET["phone_login"];}
        elseif (isset($_POST["phone_login"]))   {$phone_login=$_POST["phone_login"];}
if (isset($_GET["phone_pass"]))					{$phone_pass=$_GET["phone_pass"];}
        elseif (isset($_POST["phone_pass"]))    {$phone_pass=$_POST["phone_pass"];}
if (isset($_GET["VD_login"]))					{$VD_login=$_GET["VD_login"];}
        elseif (isset($_POST["VD_login"]))      {$VD_login=$_POST["VD_login"];}
if (isset($_GET["VD_pass"]))					{$VD_pass=$_GET["VD_pass"];}
        elseif (isset($_POST["VD_pass"]))       {$VD_pass=$_POST["VD_pass"];}
if (isset($_GET["VD_campaign"]))                {$VD_campaign=$_GET["VD_campaign"];}
        elseif (isset($_POST["VD_campaign"]))   {$VD_campaign=$_POST["VD_campaign"];}
if (isset($_GET["relogin"]))					{$relogin=$_GET["relogin"];}
        elseif (isset($_POST["relogin"]))       {$relogin=$_POST["relogin"];}

if (!isset($phone_login)) 
	{
	if (isset($_GET["pl"]))                {$phone_login=$_GET["pl"];}
			elseif (isset($_POST["pl"]))   {$phone_login=$_POST["pl"];}
	}
if (!isset($phone_pass))
	{
	if (isset($_GET["pp"]))                {$phone_pass=$_GET["pp"];}
			elseif (isset($_POST["pp"]))   {$phone_pass=$_POST["pp"];}
	}

$DB = preg_replace('/[^-\._0-9\p{L}]/u',"",$DB);
$phone_login = preg_replace('/[^-\._0-9\p{L}]/u',"",$phone_login);
$phone_pass = preg_replace('/[^-\._0-9\p{L}]/u',"",$phone_pass);
$VD_login = preg_replace('/[^-\._0-9\p{L}]/u',"",$VD_login);
$VD_pass = preg_replace('/[^-\._0-9\p{L}]/u',"",$VD_pass);
$VD_campaign = preg_replace('/[^-\._0-9\p{L}]/u',"",$VD_campaign);
$relogin = preg_replace('/[^-\._0-9\p{L}]/u',"",$relogin);


#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$VD_login';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$VD_login,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$stmt = "SELECT use_non_latin,admin_home_url,admin_web_directory,enable_languages,language_method FROM system_settings;";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$VD_login,$server_ip,$session_name,$one_mysql_log);}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =			$row[0];
	$welcomeURL =			$row[1];
	$admin_web_directory =	$row[2];
	$SSenable_languages =	$row[3];
	$SSlanguage_method =	$row[4];
	}
##### END SETTINGS LOOKUP #####
###########################################

$URL = "http://192.168.1.60/agc/vicidial.php?phone_login=$phone_login&phone_pass=$phone_pass&DB=$DB&VD_login=$VD_login&VD_pass=$VD_pass&VD_campaign=$VD_campaign&relogin=$relogin";

echo"<TITLE>"._QXZ("Agent Redirect")."</TITLE>\n";
echo"<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=iso-8859-1\">\n";
echo"<META HTTP-EQUIV=Refresh CONTENT=\"0; URL=$URL\">\n";
echo"</HEAD>\n";
echo"<BODY BGCOLOR=#FFFFFF marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo"<a href=\"$URL\">"._QXZ("click here to continue").". . .</a>\n";
exit;

?>
