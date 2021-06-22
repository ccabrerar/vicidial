<?php
# AST_email_log_display - VICIDIAL administration page
#
# Copyright (C) 2017  Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# This page displays emails from the log
#
# changes:
# 130221-2124 - First build
# 130610-1039 - Finalized changing of all ereg instances to preg
# 130621-0756 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0733 - Changed to mysqli PHP functions
# 141114-0843 - Finalized adding QXZ translation to all admin files
# 141230-1506 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["email_row_id"]))	{$email_row_id=$_GET["email_row_id"];}
	elseif (isset($_POST["email_row_id"]))	{$email_row_id=$_POST["email_row_id"];}
if (isset($_GET["email_log_id"]))	{$email_log_id=$_GET["email_log_id"];}
	elseif (isset($_POST["email_log_id"]))	{$email_log_id=$_POST["email_log_id"];}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,timeclock_end_of_day,agentonly_callback_campaign_lock,custom_fields_enabled,allow_emails,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =							$row[0];
	$timeclock_end_of_day =					$row[1];
	$agentonly_callback_campaign_lock =		$row[2];
	$custom_fields_enabled =				$row[3];
	$allow_emails =							$row[4];
	$SSenable_languages =					$row[5];
	$SSlanguage_method =					$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($allow_emails<1) 
	{
	echo _QXZ("Your system does not have the email setting enabled")."\n";
	exit;
	}

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


if ($email_log_id) {
	$stmt="select * from vicidial_email_log where email_log_id='$email_log_id'";
	$rslt=mysql_to_mysqli($stmt, $link);
} else if ($email_row_id) {
	$stmt="select * from vicidial_email_list where email_row_id='$email_row_id'";
	$rslt=mysql_to_mysqli($stmt, $link);
}
	
if (mysqli_num_rows($rslt)>0) {
	$row=mysqli_fetch_array($rslt);
	$row["message"]=preg_replace('/\r|\n/', "<BR/>", $row["message"]);
	$EMAIL_form="<TABLE cellspacing=2 cellpadding=2 bgcolor='#CCCCCC' width='500'>\n";
	$EMAIL_form.="<tr bgcolor=white><td align='right' valign='top' width='100'>"._QXZ("Date sent").":</td><td align='left' valign='top' width='400'>$row[email_date]</td></tr>\n";
	$EMAIL_form.="<tr bgcolor=white><td align='right' valign='top' width='100'>"._QXZ("Message").":</td><td align='left' valign='top' width='400'>$row[message]</td></tr>\n";
	$EMAIL_form.="</table>";
}
?>

<html>
<head>
<title><?php echo _QXZ("email frame"); ?></title>
</head>
<body topmargin=0 leftmargin=0>
<?php echo $EMAIL_form; ?>
</body>
</html>
