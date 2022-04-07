<?php
# voice_lab.php
# 
# This script is designed to broadcast a recorded message or allow a person to
# speak to all agents logged into a VICIDIAL campaign.
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
#
# CHANGES
#
# 61220-1050 - First Build
# 70115-1246 - Added ability to define an exten to play
# 90508-0644 - Changed to PHP long tags
# 120223-2124 - Removed logging of good login passwords if webroot writable is enabled
# 130414-0045 - Added report logging
# 130610-0937 - Finalized changing of all ereg instances to preg
# 130615-2342 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0831 - Changed to mysqli PHP functions
# 141007-2143 - Finalized adding QXZ translation to all admin files
# 141229-1817 - Added code for on-the-fly language translations display
# 170217-1213 - Fixed non-latin auth issue #995
# 220226-2221 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='Voice Lab';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["message"]))				{$message=$_GET["message"];}
	elseif (isset($_POST["message"]))		{$message=$_POST["message"];}
if (isset($_GET["session_id"]))				{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))	{$session_id=$_POST["session_id"];}
if (isset($_GET["campaign_id"]))			{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))	{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["NEW_VOICE_LAB"]))			{$NEW_VOICE_LAB=$_GET["NEW_VOICE_LAB"];}
	elseif (isset($_POST["NEW_VOICE_LAB"]))	{$NEW_VOICE_LAB=$_POST["NEW_VOICE_LAB"];}
if (isset($_GET["KILL_VOICE_LAB"]))				{$KILL_VOICE_LAB=$_GET["KILL_VOICE_LAB"];}
	elseif (isset($_POST["KILL_VOICE_LAB"]))	{$KILL_VOICE_LAB=$_POST["KILL_VOICE_LAB"];}
if (isset($_GET["PLAY_MESSAGE"]))			{$PLAY_MESSAGE=$_GET["PLAY_MESSAGE"];}
	elseif (isset($_POST["PLAY_MESSAGE"]))	{$PLAY_MESSAGE=$_POST["PLAY_MESSAGE"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$mysql_datetime = date("Y-m-d H:i:s");
$FILE_datetime = date("Ymd-His_");
$secX = $STARTtime;
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

$local_DEF = 'Local/';
$local_AMP = '@';
$ext_context = 'demo';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,custom_fields_enabled,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$custom_fields_enabled =	$row[1];
	$SSenable_languages =		$row[2];
	$SSlanguage_method =		$row[3];
	$SSallow_web_debug =		$row[4];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$server_ip = preg_replace('/[^\.0-9a-zA-Z]/', '', $server_ip);
$session_id = preg_replace('/[^0-9a-zA-Z]/', '', $session_id);
$message = preg_replace('/[^0-9a-zA-Z]/', '', $message);
$NEW_VOICE_LAB = preg_replace('/[^0-9a-zA-Z]/', '', $NEW_VOICE_LAB);
$KILL_VOICE_LAB = preg_replace('/[^0-9a-zA-Z]/', '', $KILL_VOICE_LAB);
$PLAY_MESSAGE = preg_replace('/[^0-9a-zA-Z]/', '', $PLAY_MESSAGE);
$submit = preg_replace('/[^-_0-9a-zA-Z]/',"",$submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/',"",$SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$campaign_id = preg_replace('/[^0-9a-zA-Z]/', '', $campaign_id);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$campaign_id = preg_replace('/[^-_0-9\p{L}]/u', '', $campaign_id);
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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if( (strlen($PHP_AUTH_USER)<2) or (strlen($PHP_AUTH_PW)<2) or (!$auth))
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("Invalid Username/Password").": |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
    exit;
	}
else
	{
	if($auth>0)
		{
		$stmt="SELECT full_name from vicidial_users where user='$PHP_AUTH_USER';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$LOGfullname=$row[0];
		}
	else
		{
		# nothing
		}

	$stmt="SELECT full_name from vicidial_users where user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$full_name = $row[0];
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####


##### get server listing for dynamic pulldown
$stmt="SELECT server_ip,server_description from servers order by server_ip";
$rslt=mysql_to_mysqli($stmt, $link);
$servers_to_print = mysqli_num_rows($rslt);
$servers_list='';

$o=0;
while ($servers_to_print > $o)
	{
	$rowx=mysqli_fetch_row($rslt);
	$servers_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$o++;
	}

##### get campaigns listing for dynamic pulldown
$stmt="SELECT campaign_id,campaign_name from vicidial_campaigns order by campaign_id";
$rslt=mysql_to_mysqli($stmt, $link);
$campaigns_to_print = mysqli_num_rows($rslt);
$campaigns_list='';

$o=0;
while ($campaigns_to_print > $o)
	{
	$rowx=mysqli_fetch_row($rslt);
	$campaigns_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$o++;
	}

require("screen_colors.php");


?>
<html>
<head>
<title><?php echo _QXZ("VOICE LAB: Admin"); ?></title>
<?php
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
?>
</head>
<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<CENTER>

<?php 
if ($NEW_VOICE_LAB > 0)
	{
	if ( (strlen($server_ip) > 6) && (strlen($session_id) > 6) && (strlen($campaign_id) > 2) )
		{
		echo "<br><br><br>"._QXZ("TO START YOUR VOICE LAB, DIAL 9%1s ON YOUR PHONE NOW",0,'',$session_id)."<br>\n";

		echo "<br>"._QXZ("or, you can enter an extension that you want played below")."<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=PLAY_MESSAGE value=2>\n";
		echo "<input type=hidden name=session_id value=8600900>\n";
		echo "<input type=hidden name=server_ip value=$server_ip>\n";
		echo "<input type=hidden name=campaign_id value=$campaign_id>\n";
		echo _QXZ("Message Extension")."<input type=text name=message>\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("PLAY THIS MESSAGE")."'>\n";
		echo "</form><BR><BR><BR>\n";
		
		$S='*';
		$D_s_ip = explode('.', $server_ip);
		if (strlen($D_s_ip[0])<2) {$D_s_ip[0] = "0$D_s_ip[0]";}
		if (strlen($D_s_ip[0])<3) {$D_s_ip[0] = "0$D_s_ip[0]";}
		if (strlen($D_s_ip[1])<2) {$D_s_ip[1] = "0$D_s_ip[1]";}
		if (strlen($D_s_ip[1])<3) {$D_s_ip[1] = "0$D_s_ip[1]";}
		if (strlen($D_s_ip[2])<2) {$D_s_ip[2] = "0$D_s_ip[2]";}
		if (strlen($D_s_ip[2])<3) {$D_s_ip[2] = "0$D_s_ip[2]";}
		if (strlen($D_s_ip[3])<2) {$D_s_ip[3] = "0$D_s_ip[3]";}
		if (strlen($D_s_ip[3])<3) {$D_s_ip[3] = "0$D_s_ip[3]";}
		$remote_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S$D_s_ip[3]$S$session_id";

		$thirty_minutes_old = mktime(date("H"), date("i"), date("s")-30, date("m"), date("d"),  date("Y"));
		$past_thirty = date("Y-m-d H:i:s",$thirty_minutes_old);

		$stmt="SELECT conf_exten,server_ip,user from vicidial_live_agents where last_update_time > '$past_thirty' and campaign_id='$campaign_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$agents_to_loop = mysqli_num_rows($rslt);
		$agents_sessions[0]='';
		$agents_servers[0]='';
		$agents_users[0]='';

		$o=0;
		while ($agents_to_loop > $o)
			{
			$rowx=mysqli_fetch_row($rslt);
			$agents_sessions[$o] = "$rowx[0]";
			$agents_servers[$o] = "$rowx[1]";
			$agents_users[$o] = "$rowx[2]";
			$o++;
			}

		$o=0;
		while ($agents_to_loop > $o)
			{
			if ($agents_servers[$o] == "$server_ip") 
				{$dial_string = $session_id;}
			else
				{$dial_string = $remote_dialstring;}

			$stmt="INSERT INTO vicidial_manager values('','','$mysql_datetime','NEW','N','$agents_servers[$o]','','Originate','VL$FILE_datetime$o','Channel: $local_DEF$dial_string$local_AMP$ext_context','Context: $ext_context','Exten: $agents_sessions[$o]','Priority: 1','Callerid: VL$FILE_datetime$o','','','','','')";
			echo "|$stmt|\n<BR><BR>\n";
			$rslt=mysql_to_mysqli($stmt, $link);

			echo _QXZ("LOGGED IN USER")." $agents_users[$o] "._QXZ("at session")." $agents_sessions[$o] "._QXZ("on server")." $agents_servers[$o]\n";

			$o++;
			}



		echo "<br>"._QXZ("Kill a Voice Lab Session").": 8600900<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=KILL_VOICE_LAB value=2>\n";
		echo "<input type=hidden name=session_id value=8600900>\n";
		echo "<input type=hidden name=server_ip value=$server_ip>\n";
		echo "<input type=hidden name=campaign_id value=$campaign_id>\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("KILL THIS VOICE LAB")."'>\n";
		echo "</form><BR><BR><BR>\n";
		}
		else
		{
		echo _QXZ("ERROR!!!!    Not all info entered properly")."\n<BR><BR>\n";
		echo "|$server_ip| |$session_id| |$campaign_id|\n<BR><BR>\n";
		echo "<a href=\"$PHP_SELF\">"._QXZ("Back to main voicelab screen")."</a>\n<BR><BR>\n";
		}
	}
else
	{
	if ($PLAY_MESSAGE > 0)
		{
		if ( (strlen($server_ip) > 6) && (strlen($session_id) > 6) && (strlen($campaign_id) > 2) && (strlen($message) > 0) )
			{
			echo "<br><br><br>"._QXZ("TO START YOUR VOICE LAB, DIAL")." 9$session_id "._QXZ("ON YOUR PHONE NOW")."<br>\n";

			echo "<br>"._QXZ("or, you can enter an extension that you want played below")."<form action=$PHP_SELF method=POST>\n";
			echo "<input type=hidden name=PLAY_MESSAGE value=2>\n";
			echo "<input type=hidden name=session_id value=8600900>\n";
			echo "<input type=hidden name=server_ip value=$server_ip>\n";
			echo "<input type=hidden name=campaign_id value=$campaign_id>\n";
			echo _QXZ("Message Extension")."<input type=text name=message>\n";
			echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("PLAY THIS MESSAGE")."'>\n";
			echo "</form><BR><BR><BR>\n";
			
			$nn='99';
			$n='9';

			$stmt="INSERT INTO vicidial_manager values('','','$mysql_datetime','NEW','N','$server_ip','','Originate','VL$FILE_datetime$nn','Channel: $local_DEF$n$session_id$local_AMP$ext_context','Context: $ext_context','Exten: $message','Priority: 1','Callerid: VL$FILE_datetime$nn','','','','','')";
			echo "|$stmt|\n<BR><BR>\n";
			$rslt=mysql_to_mysqli($stmt, $link);

			echo _QXZ("MESSAGE")." $message "._QXZ("played at session")." $session_id "._QXZ("on server")." $server_ip\n";

			echo "<br>"._QXZ("Kill a Voice Lab Session").": 8600900<form action=$PHP_SELF method=POST>\n";
			echo "<input type=hidden name=KILL_VOICE_LAB value=2>\n";
			echo "<input type=hidden name=session_id value=8600900>\n";
			echo "<input type=hidden name=server_ip value=$server_ip>\n";
			echo "<input type=hidden name=campaign_id value=$campaign_id>\n";
			echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("KILL THIS VOICE LAB")."'>\n";
			echo "</form><BR><BR><BR>\n";
			}
			else
			{
			echo _QXZ("ERROR!!!!    Not all info entered properly")."\n<BR><BR>\n";
			echo "|$server_ip| |$session_id| |$campaign_id|\n<BR><BR>\n";
			echo "<a href=\"$PHP_SELF\">"._QXZ("Back to main voicelab screen")."</a>\n<BR><BR>\n";
			}
		}
	else
		{
		echo "<br>"._QXZ("Start a Voice Lab Session").": 8600900<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=NEW_VOICE_LAB value=2>\n";
		echo "<input type=hidden name=session_id value=8600900>\n";
		echo _QXZ("Your Server").": <select size=1 name=server_ip>$servers_list</select>\n";
		echo "<BR>\n";
		echo _QXZ("Campaign").": <select size=1 name=campaign_id>$campaigns_list</select>";
		echo "<BR>\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'>\n";
		echo "</form><BR><BR><BR>\n";


		echo "<br>"._QXZ("Kill a Voice Lab Session").": 8600900<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=KILL_VOICE_LAB value=2>\n";
		echo "<input type=hidden name=session_id value=8600900>\n";
		echo _QXZ("Your Server").": <select size=1 name=server_ip>$servers_list</select>\n";
		echo "<BR>\n";
		echo _QXZ("Campaign").": <select size=1 name=campaign_id>$campaigns_list</select>";
		echo "<BR>\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'>\n";
		echo "</form><BR><BR><BR>\n";
		}
	}


if ($KILL_VOICE_LAB > 1)
	{
	if ( (strlen($server_ip) > 6) && (strlen($session_id) > 6) && (strlen($campaign_id) > 2) )
		{
		$kill_dial_string = "5555$session_id";
		$hangup_exten='8300';
		$stmt="INSERT INTO vicidial_manager values('','','$mysql_datetime','NEW','N','$server_ip','','Originate','VLK$FILE_datetime','Channel: $local_DEF$kill_dial_string$local_AMP$ext_context','Context: $ext_context','Exten: $hangup_exten','Priority: 1','Callerid: VLK$FILE_datetime','','','','','')";
		echo "|$stmt|\n<BR><BR>\n";
		$rslt=mysql_to_mysqli($stmt, $link);

		echo _QXZ("VOICELAB SESSION KILLED").": $session_id "._QXZ("at")." $server_ip | $KILL_VOICE_LAB\n";
		}
	else
		{
		echo _QXZ("ERROR!!!!    Not all info entered properly")."\n<BR><BR>\n";
		echo "|$server_ip| |$session_id| |$campaign_id|\n<BR><BR>\n";
		echo "<a href=\"$PHP_SELF\">"._QXZ("Back to main voicelab screen")."</a>\n<BR><BR>\n";
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

</BODY></HTML>
