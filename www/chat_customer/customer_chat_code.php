<?php
# customer_chat_code.php
#
# Copyright (C) 2024  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# Example for incorporating the customer side of the Vicidial chat into a web page.  
# Can be called as an include file, if desired.
#
# Builds:
# 151212-0829 - First Build for customer chat
# 151217-1015 - Allow for group_id variable
# 151219-1415 - Added header and language variable
# 160108-1659 - Added available_agents variable
# 160120-1944 - Added show_email variable
# 220220-1921 - Added allow_web_debug system setting
# 240801-1130 - Code updates for PHP8 compatibility
# 240805-2103 - Added PHP_error_reporting_OVERRIDE options
#

$PHP_error_reporting_OVERRIDE=0;
if (file_exists('options.php'))
        {
        require('options.php');
        }
if ($PHP_error_reporting_OVERRIDE > 0)
	{
	$php_err_suppression_value=32767; # E_ALL
	$php_err_suppression_value-=($PHP_error_reporting_HIDE_ERRORS ? 1 : 0);
	$php_err_suppression_value-=($PHP_error_reporting_HIDE_WARNINGS ? 2 : 0);
	$php_err_suppression_value-=($PHP_error_reporting_HIDE_PARSES ? 4 : 0);
	$php_err_suppression_value-=($PHP_error_reporting_HIDE_NOTICES ? 8 : 0);
	$php_err_suppression_value-=($PHP_error_reporting_HIDE_DEPRECATIONS ? 8192 : 0);
	error_reporting($php_err_suppression_value);
	}

if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["chat_id"]))				{$chat_id=$_GET["chat_id"];}
	elseif (isset($_POST["chat_id"]))		{$chat_id=$_POST["chat_id"];}
if (isset($_GET["group_id"]))				{$chat_group_id=$_GET["group_id"];}
	elseif (isset($_POST["group_id"]))		{$chat_group_id=$_POST["group_id"];}
if (isset($_GET["chat_group_id"]))			{$chat_group_id=$_GET["chat_group_id"];}
	elseif (isset($_POST["chat_group_id"]))	{$chat_group_id=$_POST["chat_group_id"];}
if (isset($_GET["email"]))					{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))			{$email=$_POST["email"];}
if (isset($_GET["unique_userID"]))			{$unique_userID=$_GET["unique_userID"];}
	elseif (isset($_POST["unique_userID"]))	{$unique_userID=$_POST["unique_userID"];}
if (isset($_GET["language"]))				{$language=$_GET["language"];}
	elseif (isset($_POST["language"]))		{$language=$_POST["language"];}
if (isset($_GET["available_agents"]))			{$available_agents=$_GET["available_agents"];}
	elseif (isset($_POST["available_agents"]))	{$available_agents=$_POST["available_agents"];}
if (isset($_GET["show_email"]))				{$show_email=$_GET["show_email"];}
	elseif (isset($_POST["show_email"]))	{$show_email=$_POST["show_email"];}

if (isset($lead_id)) {$lead_id = preg_replace("/[^0-9]/","",$lead_id);}
if (isset($chat_id)) {$chat_id = preg_replace('/[^-\_\.0-9a-zA-Z]/','',$chat_id);}
if (isset($group_id)) {$group_id = preg_replace('/[^-\_0-9\p{L}]/u','',$group_id);}
if (isset($chat_group_id)) {$chat_group_id = urlencode(preg_replace('/[^-\_0-9\p{L}]/u','',$chat_group_id));}
if (isset($email)) {$email = urlencode(preg_replace('/[^-\.\:\/\@\_0-9\p{L}]/u','',$email));}
if (isset($unique_userID)) {$unique_userID = urlencode(preg_replace('/[^-\.\_0-9a-zA-Z]/','',$unique_userID));}
if (isset($language)) {$language = urlencode(preg_replace('/[^-\_0-9a-zA-Z]/','',$language));}
if (isset($available_agents)) {urlencode($available_agents = preg_replace('/[^-\_0-9a-zA-Z]/','',$available_agents));}
if (isset($show_email)) {$show_email = urlencode(preg_replace('/[^-\_0-9a-zA-Z]/','',$show_email));}

$URL_vars="?user=".$unique_userID."&lead_id=".$lead_id."&group_id=".$chat_group_id."&chat_id=".$chat_id."&email=".$email."&language=".$language."&available_agents=".$available_agents."&show_email=".$show_email;
header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0
echo '<?xml version="1.0" encoding="UTF-8"?><html><head><title>Chat</title></head>';
?>

<iframe src="/chat_customer/vicidial_chat_customer_side.php<?php echo $URL_vars; ?>" style="width:640;height:480;background-color:transparent;" scrolling="auto" frameborder="0" allowtransparency="true" id="ViCiDiAlChAtIfRaMe" name="ViCiDiAlChAtIfRaMe"/>
</html>
