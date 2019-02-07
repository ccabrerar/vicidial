<?php
# live_exten_check.php    version 2.14
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed purely to send whether the client channel is live and to what channel it is connected
# This script depends on the server_ip being sent and also needs to have a valid user/pass from the vicidial_users table
# 
# required variables:
#  - $server_ip
#  - $session_name
#  - $user
#  - $pass
# optional variables:
#  - $format - ('text','debug')
#  - $exten - ('cc101','testphone','49-1','1234','913125551212',...)
#  - $protocol - ('SIP','Zap','IAX2',...)
# 
#
# changes
# 50404-1249 - First build of script
# 50406-1402 - added connected trunk lookup
# 50428-1452 - added live_inbound check for exten on 2nd line of output
# 50503-1233 - added session_name checking for extra security
# 50524-1429 - added parked calls count
# 50610-1204 - Added NULL check on MySQL results to reduced errors
# 50711-1204 - removed HTTP authentication in favor of user/pass vars
# 60103-1541 - added favorite extens status display
# 60421-1359 - check GET/POST vars lines with isset to not trigger PHP NOTICES
# 60619-1203 - Added variable filters to close security holes for login form
# 60825-1029 - Fixed translation variable issue ChannelA
# 90508-0727 - Changed to PHP long tags
# 130328-0027 - Converted ereg to preg functions
# 130603-2214 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130705-1522 - Added optional encrypted passwords compatibility
# 130802-1009 - Changed to PHP mysqli functions
# 140811-0841 - Changed to use QXZ function for echoing text
# 141118-1236 - Formatting changes for QXZ output
# 141128-0854 - Code cleanup for QXZ functions
# 141216-2109 - Added language settings lookups and user/pass variable standardization
# 150723-1713 - Added ajax logging
# 170526-2234 - Added additional variable filtering
#

$version = '2.14-17';
$build = '170526-2234';
$php_script = 'live_exten_check.php';
$SSagent_debug_logging=0;
$startMS = microtime();

require_once("dbconnect_mysqli.php");
require_once("functions.php");

### If you have globals turned off uncomment these lines
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["pass"]))				{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))		{$pass=$_POST["pass"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["session_name"]))				{$session_name=$_GET["session_name"];}
	elseif (isset($_POST["session_name"]))		{$session_name=$_POST["session_name"];}
if (isset($_GET["format"]))				{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))		{$format=$_POST["format"];}
if (isset($_GET["exten"]))				{$exten=$_GET["exten"];}
	elseif (isset($_POST["exten"]))		{$exten=$_POST["exten"];}
if (isset($_GET["protocol"]))				{$protocol=$_GET["protocol"];}
	elseif (isset($_POST["protocol"]))		{$protocol=$_POST["protocol"];}
if (isset($_GET["favorites_count"]))				{$favorites_count=$_GET["favorites_count"];}
	elseif (isset($_POST["favorites_count"]))		{$favorites_count=$_POST["favorites_count"];}
if (isset($_GET["favorites_list"]))				{$favorites_list=$_GET["favorites_list"];}
	elseif (isset($_POST["favorites_list"]))		{$favorites_list=$_POST["favorites_list"];}

# variable filtering
$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);
$session_name = preg_replace("/\'|\"|\\\\|;/","",$session_name);
$server_ip = preg_replace("/\'|\"|\\\\|;/","",$server_ip);
$exten = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$exten);
$protocol = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$protocol);
$favorites_list = preg_replace("/\'|\"|\\\\|;| /","",$favorites_list);

# default optional vars if not set
if (!isset($format))   {$format="text";}

$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
if (!isset($query_date)) {$query_date = $NOW_DATE;}


#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$stmt = "SELECT use_non_latin,enable_languages,language_method,agent_debug_logging FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$SSagent_debug_logging =	$row[3];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	}
if (strlen($SSagent_debug_logging) > 1)
	{
	if ($SSagent_debug_logging == "$user")
		{$SSagent_debug_logging=1;}
	else
		{$SSagent_debug_logging=0;}
	}

$auth=0;
$auth_message = user_authorization($user,$pass,'',0,1,0,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0))
	{
	echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth_message|\n";
	exit;
	}
else
	{
	if( (strlen($server_ip)<6) or (!isset($server_ip)) or ( (strlen($session_name)<12) or (!isset($session_name)) ) )
		{
		echo _QXZ("Invalid server_ip: %1s or Invalid session_name: %2s",0,'',$server_ip,$session_name)."\n";
		exit;
		}
	else
		{
		$stmt="SELECT count(*) from web_client_sessions where session_name='$session_name' and server_ip='$server_ip';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$SNauth=$row[0];
		if($SNauth==0)
			{
			echo _QXZ("Invalid session_name:")." |$session_name|$server_ip|\n";
			exit;
			}
		else
			{
			# do nothing for now
			}
		}
	}

if ($format=='debug')
	{
	echo "<html>\n";
	echo "<head>\n";
	echo "<!-- VERSION: $version     BUILD: $build    EXTEN: $exten   server_ip: $server_ip-->\n";
	echo "<title>"._QXZ("Live Extension Check");
	echo "</title>\n";
	echo "</head>\n";
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	}


echo "DateTime: $NOW_TIME|";
echo "UnixTime: $StarTtime|";

$stmt="SELECT count(*) FROM parked_channels where server_ip = '$server_ip';";
	if ($format=='debug') {echo "\n<!-- $stmt -->";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
echo "$row[0]|";

$MT[0]='';
$row='';   $rowx='';
$channel_live=1;
if ( (strlen($exten)<1) or (strlen($protocol)<3) )
	{
	$channel_live=0;
	echo _QXZ("Exten %1s is not valid or protocol %2s is not valid",0,'',$exten,$protocol)."\n";
	exit;
	}
else
	{
	$stmt="SELECT channel,extension FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$protocol/$exten%\";";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($rslt) {$channels_list = mysqli_num_rows($rslt);}
	echo "$channels_list|";
	$loop_count=0;
	while ($channels_list>$loop_count)
		{
		$loop_count++;
		$row=mysqli_fetch_row($rslt);
		$ChanneLA[$loop_count] = "$row[0]";
		$ChanneLB[$loop_count] = "$row[1]";
		if ($format=='debug') {echo "\n<!-- $row[0]     $row[1] -->";}
		}
	}

$counter=0;
while($loop_count > $counter)
	{
		$counter++;
	$stmt="SELECT channel FROM live_channels where server_ip = '$server_ip' and channel_data = '$ChanneLA[$counter]';";
		if ($format=='debug') {echo "\n<!-- $stmt -->";}
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($rslt) {$trunk_count = mysqli_num_rows($rslt);}
	if ($trunk_count>0)
		{
		$row=mysqli_fetch_row($rslt);
		echo "Conversation: $counter ~";
		echo "ChannelA: $ChanneLA[$counter] ~";
		echo "ChannelB: $ChanneLB[$counter] ~";
		echo "ChannelBtrunk: $row[0]|";
		}
	else
		{
		$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel_data = '$ChanneLA[$counter]';";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($rslt) {$trunk_count = mysqli_num_rows($rslt);}
		if ($trunk_count>0)
			{
			$row=mysqli_fetch_row($rslt);
			echo "Conversation: $counter ~";
			echo "ChannelA: $ChanneLA[$counter] ~";
			echo "ChannelB: $ChanneLB[$counter] ~";
			echo "ChannelBtrunk: $row[0]|";
			}
		else
			{
			$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$ChanneLB[$counter]%\";";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($rslt) {$trunk_count = mysqli_num_rows($rslt);}
			if ($trunk_count>0)
				{
				$row=mysqli_fetch_row($rslt);
				echo "Conversation: $counter ~";
				echo "ChannelA: $ChanneLA[$counter] ~";
				echo "ChannelB: $ChanneLB[$counter] ~";
				echo "ChannelBtrunk: $row[0]|";
				}
			else
				{
				$stmt="SELECT channel FROM live_channels where server_ip = '$server_ip' and channel LIKE \"$ChanneLB[$counter]%\";";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($rslt) {$trunk_count = mysqli_num_rows($rslt);}
				if ($trunk_count>0)
					{
					$row=mysqli_fetch_row($rslt);
					echo "Conversation: $counter ~";
					echo "ChannelA: $ChanneLA[$counter] ~";
					echo "ChannelB: $ChanneLB[$counter] ~";
					echo "ChannelBtrunk: $row[0]|";
					}
				else
					{
					echo "Conversation: $counter ~";
					echo "ChannelA: $ChanneLA[$counter] ~";
					echo "ChannelB: $ChanneLB[$counter] ~";
					echo "ChannelBtrunk: $ChanneLA[$counter]|";
					}
				}
			}
		}
	}

echo "\n";

### check for live_inbound entry
$stmt="select * from live_inbound where server_ip = '$server_ip' and phone_ext = '$exten' and acknowledged='N';";
	if ($format=='debug') {echo "\n<!-- $stmt -->";}
$rslt=mysql_to_mysqli($stmt, $link);
if ($rslt) {$channels_list = mysqli_num_rows($rslt);}
if ($channels_list>0)
	{
	$row=mysqli_fetch_row($rslt);
	$LIuniqueid = "$row[0]";
	$LIchannel = "$row[1]";
	$LIcallerid = "$row[3]";
	$LIdatetime = "$row[6]";
	echo "$LIuniqueid|$LIchannel|$LIcallerid|$LIdatetime|$row[8]|$row[9]|$row[10]|$row[11]|$row[12]|$row[13]|";
	if ($format=='debug') {echo "\n<!-- $row[0]|$row[1]|$row[2]|$row[3]|$row[4]|$row[5]|$row[6]|$row[7]|$row[8]|$row[9]|$row[10]|$row[11]|$row[12]|$row[13]| -->";}
	}

echo "\n";


### if favorites are present do a lookup to see if any are active
if ($favorites_count > 0)
	{
	$favorites = explode(',',$favorites_list);
	$h=0;
	$favs_print='';
	while ($favorites_count > $h)
		{
		$fav_extension = explode('/',$favorites[$h]);
		$stmt="SELECT count(*) FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$favorites[$h]%\";";
			if ($format=='debug') {echo "\n<!-- $stmt -->";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$favs_print .= "$fav_extension[1]: $row[0] ~";
		$h++;
		}
	echo "$favs_print\n";
	}

if ($format=='debug') {echo "\n<!-- |$favorites_count|$favorites_list| -->";}


if ($format=='debug') 
	{
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $StarTtime);
	echo "\n<!-- script runtime: $RUNtime seconds -->";
	echo "\n</body>\n</html>\n";
	}

if ($SSagent_debug_logging > 0) {vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);}
exit; 

?>
