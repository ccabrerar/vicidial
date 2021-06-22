<?php
# send_CID_call.php
#
# Send calls with custom callerID numbers from web form
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: GPLv2
#
# CHANGES
#
# 90714-1355 - First Build
# 120831-1527 - Added vicidial_dial_log logging
# 130414-0039 - Added admin logging
# 130610-0943 - Finalized changing of all ereg instances to preg
# 130616-2144 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0853 - Changed to mysqli PHP functions
# 141007-2151 - Finalized adding QXZ translation to all admin files
# 141229-2008 - Added code for on-the-fly language translations display
# 170409-1533 - Added IP List validation code
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$ip = getenv("REMOTE_ADDR");
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["sender"]))					{$sender=$_GET["sender"];}
	elseif (isset($_POST["sender"]))		{$sender=$_POST["sender"];}
if (isset($_GET["receiver"]))				{$receiver=$_GET["receiver"];}
	elseif (isset($_POST["receiver"]))		{$receiver=$_POST["receiver"];}
if (isset($_GET["cid_number"]))				{$cid_number=$_GET["cid_number"];}
	elseif (isset($_POST["cid_number"]))	{$cid_number=$_POST["cid_number"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
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
$sender = preg_replace('/[^0-9]/','',$sender);
$receiver = preg_replace('/[^0-9]/','',$receiver);
$cid_number = preg_replace('/[^0-9]/','',$cid_number);
$server_ip = preg_replace('/[^\.0-9]/','',$server_ip);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
$ip = getenv("REMOTE_ADDR");

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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth < 1)
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
$stmt="select server_ip,server_id from servers;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$servers_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $servers_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =		$row[0];
	$group_names[$i] =	$row[1];
	$i++;
	}

echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>"._QXZ("VICIDIAL: Manual CID call")."</TITLE></HEAD><BODY BGCOLOR=WHITE>\n";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo _QXZ("Sender").": <INPUT TYPE=TEXT NAME=sender SIZE=12 MAXLENGTH=10 VALUE=\"$sender\"> &nbsp; &nbsp; \n";
echo _QXZ("Receiver").": <INPUT TYPE=TEXT NAME=receiver SIZE=12 MAXLENGTH=10 VALUE=\"$receiver\"> &nbsp; &nbsp; \n";
echo _QXZ("CID Number").": <INPUT TYPE=TEXT NAME=cid_number SIZE=12 MAXLENGTH=10 VALUE=\"$cid_number\"> &nbsp; &nbsp; \n";
echo _QXZ("Server").": <SELECT SIZE=1 NAME=server_ip>\n";
$o=0;
while ($servers_to_print > $o)
	{
	if ($groups[$o] == $server_ip) {echo "<option selected value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";}
	  else {echo "<option value=\"$groups[$o]\">$groups[$o] - $group_names[$o]</option>\n";}
	$o++;
	}
echo "</SELECT> &nbsp; \n";
echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
echo "<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</FORM><BR>\n\n";


if ( (strlen($sender) < 6) or (strlen($receiver) < 6) or (strlen($cid_number) < 6) or (strlen($server_ip) < 7) )
	{
	echo "\n\n";
	echo _QXZ("PLEASE ENTER A CALLER, RECEIVER AND CALLERID NUMBER ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	$stmt = "INSERT INTO user_call_log (user,call_date,call_type,server_ip,phone_number,number_dialed,lead_id,callerid,group_alias_id) values('$PHP_AUTH_USER','$NOW_TIME','CID','$server_ip','$sender','$receiver','0','$cid_number','$ip')";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	$Local_end = '@default';

	$stmt = "INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','TESTCIDCALL098765432','Exten: 91$receiver','Context: default','Channel: Local/91$sender$Local_end','Priority: 1','Callerid: \"$cid_number\" <$cid_number>','','','','','');";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	$stmt = "INSERT INTO vicidial_dial_log SET caller_code='TESTCIDCALL098765432',lead_id='0',server_ip='$server_ip',call_date='$NOW_TIME',extension='91$receiver',channel='Local/91$sender$Local_end',timeout='$Local_dial_timeout',outbound_cid='\"$cid_number\" <$cid_number>',context='default';";
	$rslt=mysql_to_mysqli($stmt, $link);

	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='CIDSEND', event_type='OTHER', record_id='$PHP_AUTH_USER', event_code='ADMIN SEND CID CALL', event_sql=\"$SQL_log\", event_notes='Server: $server_ip';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	echo "<B>Call sent from $sender to $receiver using CIDnumber: $cid_number</B>";
	}

?>

</BODY></HTML>

