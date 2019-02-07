<?php
# launch.php - launches vicidial.php in restricted window
# 
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# For launch validation to work, the options.php file must have
# window_validation = 1 and win_valid_name set to window_name below
#
# CHANGELOG
# 130903-2055 - First Build
# 140811-0800 - Changed to use QXZ function for echoing text
# 141216-2134 - Added language settings lookups and user/pass variable standardization
#

$window_name = 'subwindow_launch';

require_once("functions.php");
require_once("dbconnect_mysqli.php");

### do not edit below this line ###

if (isset($_GET["DB"]))						    {$DB=$_GET["DB"];}
        elseif (isset($_POST["DB"]))            {$DB=$_POST["DB"];}
if (isset($_GET["JS_browser_width"]))				{$JS_browser_width=$_GET["JS_browser_width"];}
        elseif (isset($_POST["JS_browser_width"]))  {$JS_browser_width=$_POST["JS_browser_width"];}
if (isset($_GET["JS_browser_height"]))				{$JS_browser_height=$_GET["JS_browser_height"];}
        elseif (isset($_POST["JS_browser_height"])) {$JS_browser_height=$_POST["JS_browser_height"];}
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
if (isset($_GET["MGR_override"]))				{$MGR_override=$_GET["MGR_override"];}
        elseif (isset($_POST["MGR_override"]))  {$MGR_override=$_POST["MGR_override"];}
if (!isset($phone_login)) 
	{
	if (isset($_GET["pl"]))            {$phone_login=$_GET["pl"];}
		elseif (isset($_POST["pl"]))   {$phone_login=$_POST["pl"];}
	}
if (!isset($phone_pass))
	{
	if (isset($_GET["pp"]))            {$phone_pass=$_GET["pp"];}
		elseif (isset($_POST["pp"]))   {$phone_pass=$_POST["pp"];}
	}
if (isset($VD_campaign))
	{
	$VD_campaign = strtoupper($VD_campaign);
	$VD_campaign = preg_replace("/\s/i",'',$VD_campaign);
	}
if (!isset($flag_channels))
	{
	$flag_channels=0;
	$flag_string='';
	}

### security strip all non-alphanumeric characters out of the variables ###
$DB=preg_replace("/[^0-9a-z]/","",$DB);
$phone_login=preg_replace("/[^\,0-9a-zA-Z]/","",$phone_login);
$phone_pass=preg_replace("/[^-_0-9a-zA-Z]/","",$phone_pass);
$VD_login=preg_replace("/\'|\"|\\\\|;| /","",$VD_login);
$VD_pass=preg_replace("/\'|\"|\\\\|;| /","",$VD_pass);
$VD_campaign = preg_replace("/[^-_0-9a-zA-Z]/","",$VD_campaign);

$login_string='';

if (strlen($phone_login)>0)
	{$login_string .= "phone_login=$phone_login&";}
if (strlen($phone_pass)>0)
	{$login_string .= "phone_pass=$phone_pass&";}
if (strlen($VD_login)>0)
	{$login_string .= "VD_login=$VD_login&";}
if (strlen($VD_pass)>0)
	{$login_string .= "VD_pass=$VD_pass&";}
if (strlen($VD_campaign)>0)
	{$login_string .= "VD_campaign=$VD_campaign";}
if (strlen($login_string)>0)
	{$login_string = "?$login_string";}

$US='_';
$CL=':';
$AT='@';
$DS='-';
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$script_name = getenv("SCRIPT_NAME");
$server_name = getenv("SERVER_NAME");
$server_port = getenv("SERVER_PORT");
if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($server_port == '80') or ($server_port == '443') ) {$server_port='';}
else {$server_port = "$CL$server_port";}
$agcPAGE = "$HTTPprotocol$server_name$server_port$script_name";
$agcDIR = preg_replace("/launch\.php/",'',$agcPAGE);
if (strlen($static_agent_url) > 5)
	{$agcPAGE = $static_agent_url;}

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

if ($non_latin < 1)
	{
	$VD_login=preg_replace("/[^-_0-9a-zA-Z]/","",$VD_login);
	$VD_pass=preg_replace("/[^-_0-9a-zA-Z]/","",$VD_pass);
	}

?>

<html>
<head>
<title><?php echo _QXZ("Agent Screen Opener"); ?></title>
<script type="text/javascript">

<!-- 
var BrowseWidth = 0;
var BrowseHeight = 0;

function browser_dimensions() 
	{
<?php 
	if (preg_match('/MSIE/',$browser)) 
		{
		echo "	if (document.documentElement && document.documentElement.clientHeight)\n";
		echo "		{BrowseWidth = document.documentElement.clientWidth;}\n";
		echo "	else if (document.body)\n";
		echo "		{BrowseWidth = document.body.clientWidth;}\n";
		echo "	if (document.documentElement && document.documentElement.clientHeight)\n";
		echo "		{BrowseHeight = document.documentElement.clientHeight;}\n";
		echo "	else if (document.body)\n";
		echo "		{BrowseHeight = document.body.clientHeight;}\n";
		}
	else 
		{
		echo "	BrowseWidth = window.innerWidth;\n";
		echo "	BrowseHeight = window.innerHeight;\n";
		}

	echo "	BrowseWidth = (BrowseWidth - 20);\n";
	echo "	BrowseHeight = (BrowseHeight - 20);\n";
?>
	// alert('opening window');
	// document.getElementById("dimensions").innerHTML = BrowseWidth + " x " + BrowseHeight;
	subwin = window.open('<?php echo $agcDIR ?>vicidial.php<?php echo $login_string ?>','<?php echo $window_name ?>','titlebar=no, status=no, menubar=no, toolbar=no, location=no, scrollbars=yes, resizable=yes');
	}
</script>
</head>
<body bgcolor=white onload="browser_dimensions();">
<?php echo _QXZ("Agent Screen Opener"); ?>
<span id=dimensions></span>
</body>
</html>

