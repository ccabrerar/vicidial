<?php
# closer.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# the purpose of this script and webpage is to allow for remote or local users of the system to log in and grab phone calls that are coming inbound into the Asterisk server and being put in the parked_channels table while they hear a soundfile for a limited amount of time before being forwarded on to either a set extension or a voicemail box. This gives remote or local agents a way to grab calls without tying up their phone lines all day. The agent sees the refreshing screen of calls on park and when they want to take one they just click on it, and a small window opens that will allow them to grab the call and/or look up more information on the caller through the callerID that is given(if available)
# CHANGES
#
# 60620-1032 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 90508-0644 - Changed to PHP long tags
# 120223-2249 - Removed logging of good login passwords if webroot writable is enabled
# 130610-1116 - Finalized changing of all ereg instances to preg
# 130620-0827 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-1934 - Changed to mysqli PHP functions
# 141007-2212 - Finalized adding QXZ translation to all admin files
# 141229-2041 - Added code for on-the-fly language translations display
# 170409-1532 - Added IP List validation code
# 220224-1629 - Added allow_web_debug system setting
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["group_selected"]))				{$group_selected=$_GET["group_selected"];}
	elseif (isset($_POST["group_selected"]))		{$group_selected=$_POST["group_selected"];}
if (isset($_GET["dialplan_number"]))				{$dialplan_number=$_GET["dialplan_number"];}
	elseif (isset($_POST["dialplan_number"]))		{$dialplan_number=$_POST["dialplan_number"];}
if (isset($_GET["extension"]))				{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))		{$extension=$_POST["extension"];}
if (isset($_GET["groupselect"]))				{$groupselect=$_GET["groupselect"];}
	elseif (isset($_POST["groupselect"]))		{$groupselect=$_POST["groupselect"];}
if (isset($_GET["PHONE_LOGIN"]))				{$PHONE_LOGIN=$_GET["PHONE_LOGIN"];}
	elseif (isset($_POST["PHONE_LOGIN"]))		{$PHONE_LOGIN=$_POST["PHONE_LOGIN"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["channel"]))				{$channel=$_GET["channel"];}
	elseif (isset($_POST["channel"]))		{$channel=$_POST["channel"];}
if (isset($_GET["parked_time"]))				{$parked_time=$_GET["parked_time"];}
	elseif (isset($_POST["parked_time"]))		{$parked_time=$_POST["parked_time"];}
if (isset($_GET["channel_group"]))				{$channel_group=$_GET["channel_group"];}
	elseif (isset($_POST["channel_group"]))		{$channel_group=$_POST["channel_group"];}
if (isset($_GET["debugvars"]))				{$debugvars=$_GET["debugvars"];}
	elseif (isset($_POST["debugvars"]))		{$debugvars=$_POST["debugvars"];}
if (isset($_GET["parked_by"]))				{$parked_by=$_GET["parked_by"];}
	elseif (isset($_POST["parked_by"]))		{$parked_by=$_POST["parked_by"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}

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

$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/', '', $server_ip);
$dialplan_number = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$dialplan_number);
$extension = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$extension);
$PHONE_LOGIN = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$PHONE_LOGIN);
$channel = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$channel);
$parked_time = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$parked_time);
$debugvars = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$debugvars);
$parked_by = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$parked_by);
$submit = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$submit);
$SUBMIT = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$SUBMIT);
$vendor_id = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$vendor_id);
$phone = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$phone);
$lead_id = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$lead_id);
$first_name = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$first_name);
$last_name = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$last_name);
$phone_number = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$phone_number);
$end_call = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$end_call);
$DB = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$DB);
$dispo = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$dispo);
$list_id = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$list_id);
$campaign_id = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$campaign_id);
$phone_code = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$phone_code);
$call_began = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$call_began);
$tsr = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$tsr);
$address1 = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$address1);
$address2 = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$address2);
$address3 = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$address3);
$city = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$city);
$state = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$state);
$postal_code = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$postal_code);
$province = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$province);
$country_code = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$country_code);
$alt_phone = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$alt_phone);
$email = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$email);
$security = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$security);
$comments = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$comments);
$status  = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$status);
$customer_zap_channel  = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$customer_zap_channel);
$session_id  = preg_replace("/\<|\>|\’|\"|\\\\|;/",'-',$session_id);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	$pass = preg_replace('/[^-_0-9a-zA-Z]/', '', $pass);
	$group = preg_replace('/[^-_0-9a-zA-Z]/', '', $group);
	$group_selected = preg_replace('/[^-_0-9a-zA-Z]/', '', $group_selected);
	$groupselect = preg_replace('/[^-_0-9a-zA-Z]/', '', $groupselect);
	$channel_group = preg_replace('/[^-_0-9a-zA-Z]/', '', $channel_group);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	$pass = preg_replace('/[^-_0-9\p{L}]/u', '', $pass);
	$group = preg_replace('/[^-_0-9\p{L}]/u', '', $group);
	$group_selected = preg_replace('/[^-_0-9\p{L}]/u', '', $group_selected);
	$groupselect = preg_replace('/[^-_0-9\p{L}]/u', '', $groupselect);
	$channel_group = preg_replace('/[^-_0-9\p{L}]/u', '', $channel_group);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$popup_page = './closer_popup.php';
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
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

$stmt="SELECT full_name from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname=$row[0];
$fullname = $row[0];

$color_class[0] = 'green';
$color_class[1] = 'red';
$color_class[2] = 'blue';
$color_class[3] = 'purple';
$color_class[4] = 'orange';
$color_class[5] = 'green';
$color_class[6] = 'red';
$color_class[7] = 'blue';
$color_class[8] = 'purple';
$color_class[9] = 'orange';

require("screen_colors.php");
?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   A:link {color: yellow}
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}
   .orange {color: white; background-color: orange}
-->
 </STYLE>

<TITLE><?php echo _QXZ("CLOSER: Main"); ?></TITLE></HEAD>
</HEAD>
<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<CENTER><FONT FACE="Courier" COLOR=BLACK SIZE=3>

<?php 
$group_selected = count($groupselect);

if (!$dialplan_number)
{

	if ($extension)
	{
	$stmt="SELECT count(*) from phones where extension='" . mysqli_real_escape_string($link, $extension) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$ext_found=$row[0];
		if ($ext_found > 0)
		{
		$stmt="SELECT dialplan_number,server_ip from phones where extension='" . mysqli_real_escape_string($link, $extension) . "';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$dialplan_number=$row[0];
		$server_ip=$row[1];

		if (!$group_selected)
			{
			$stmt="select distinct channel_group from park_log;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$groups_to_print = mysqli_num_rows($rslt);
			$i=0;
			while ($i < $groups_to_print)
				{
				$row=mysqli_fetch_row($rslt);
				$groups[$i] =$row[0];
				$i++;
				}
			echo "<br>"._QXZ("Please select the groups you want to pick calls up from").": <form action=$PHP_SELF method=GET>\n";
			echo "<table border=0><tr><td align=left>\n";
			echo "<input type=hidden name=PHONE_LOGIN value=1>\n";
			echo "<input type=hidden name=dialplan_number value=\"$dialplan_number\">\n";
			echo "<input type=hidden name=extension value=\"$extension\">\n";
			echo "<input type=hidden name=server_ip value=\"$server_ip\">\n";
			echo "<input type=hidden name=DB value=\"$DB\">\n";
			echo _QXZ("Inbound Call Groups").": <br>\n";
				$o=0;
				while ($groups_to_print > $o)
				{
					$group_form_print =		sprintf("%-20s", $groups[$o]);
					echo "<input name=\"groupselect[]\" type=checkbox value=\"$groups[$o]\">$group_form_print <BR>\n";
					$o++;
				}
			echo "</td></tr></table>\n";
			echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'>\n";
			echo "<BR><BR><BR>\n";
			}
		else
			{
			$o=0;
			while($o < $group_selected)
			{
				$form_groups = "$form_groups&groupselect[$o]=$groupselect[$o]";
				$o++;
			}

			$stmt="INSERT INTO vicidial_user_log values('','" . mysqli_real_escape_string($link, $user) . "','LOGIN','CLOSER','$NOW_TIME','$STARTtime');";
			$rslt=mysql_to_mysqli($stmt, $link);

			echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
			echo"<META HTTP-EQUIV=Refresh CONTENT=\"3; URL=$PHP_SELF?dialplan_number=$dialplan_number&server_ip=$server_ip&extension=$extension&DB=$DB$form_groups\">\n";

			echo "<font=green><b>"._QXZ("extension found, forwarding you to closer page, please wait 3 seconds")."...</b></font>\n";
			}

		}
		else
		{
		echo "<font=red><b>"._QXZ("The extension you entered does not exist, please try again")."</b></font>\n";
		echo "<br>"._QXZ("Please enter your phone_ID").": <form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=PHONE_LOGIN value=1>\n";
		echo _QXZ("phone station ID").": <input type=text name=extension size=10 maxlength=10 value=\"$extension\"> &nbsp; \n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'>\n";
		echo "<BR><BR><BR>\n";
		}

	}
	else
	{
	echo "<br>"._QXZ("Please enter your phone_ID").": <form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=PHONE_LOGIN value=1>\n";
	echo _QXZ("phone station ID").": <input type=text name=extension size=10 maxlength=10> &nbsp; \n";
	echo "<input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'>\n";
	echo "<BR><BR><BR>\n";
	}

exit;

}

$o=0;
while($o < $group_selected)
{
	$listen_groups = "$listen_groups &nbsp; &nbsp; $groupselect[$o]";
	$form_groups = "$form_groups&groupselect[$o]=$groupselect[$o]";
	$o++;
}


echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo"<META HTTP-EQUIV=Refresh CONTENT=\"5; URL=$PHP_SELF?dialplan_number=$dialplan_number&server_ip=$server_ip&extension=$extension&DB=$DB$form_groups\">\n";

#CHANNEL    SERVER        CHANNEL_GROUP   EXTENSION    PARKED_BY            PARKED_TIME        
#----------------------------------------------------------------------------------------------
#Zap/73-1   10.10.11.11   IN_800_TPP_CS   TPPpark      7275338730           2004-04-22 12:41:00



echo "$NOW_TIME &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; $PHP_AUTH_USER - $fullname -  "._QXZ("CALLS SENT TO").": $dialplan_number<BR><BR>\n";

echo _QXZ("Click on the channel below that you would like to have directed to your phone")."<BR>\n";

echo "<PRE>\n";
echo _QXZ("CHANNEL",10)." "._QXZ("SERVER",13)." "._QXZ("CHANNEL_GROUP",15)." "._QXZ("EXTENSION",12)." "._QXZ("PARKED_BY",20)." "._QXZ("PARKED_TIME",20)."\n";
echo "---------------------------------------------------------------------------------------------- \n";





$stmt="SELECT count(*) from parked_channels where server_ip='" . mysqli_real_escape_string($link, $server_ip) . "'";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);
$parked_count = $row[0];
	if ($parked_count > 0)
	{
	$stmt="SELECT channel,server_ip,channel_group,extension,parked_by,parked_time from parked_channels where server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' and channel_group LIKE \"CL_%\" order by channel_group,parked_time";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$parked_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $parked_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$channel =			sprintf("%-11s", $row[0]);
		$server =			sprintf("%-14s", $row[1]);
		$channel_group =	sprintf("%-16s", $row[2]);
		$park_extension =	sprintf("%-13s", $row[3]);
		$parked_by =		sprintf("%-21s", $row[4]);
		$parked_time =		sprintf("%-19s", $row[5]);

		$G='';   $EG='';
		$o=0;
		$with_color=0;
		while($o < $group_selected)
			{
				if (preg_match("/$groupselect[$o]/i",$channel_group))
					{
					$G="<SPAN class=\"$color_class[$o]\"><B>"; $EG='</B></SPAN>';
					$with_color++;
					}
				$o++;
			}
		if ($with_color)
			{
			if (preg_match('/CL_GAL/i',$row[2]))
				{
				echo "$G<A HREF=\"$popup_page?channel=$row[0]&server_ip=$server_ip&parked_time=$row[5]&dialplan_number=$dialplan_number&extension=$extension&channel_group=$row[2]&DB=$DB&debugvars=asdfuiywer786sdg786sfg7sdsgjhg352j3452hg45f2j3h4g5f2j3h4g5f2jhg4f5j3fg45jh23f5j4h23gf&parked_by=$row[4]&debugvars=asdfuiywer786sdg786sfg7sdsgjhg352j3452hg45f2j3h4g5f2j3h4g5f2jhg4f5j3fg45jh23f5j4h23gf\" target=\"_blank\">$channel</A>$server$channel_group$park_extension                     $parked_time $EG\n";
				}
			else
				{
				echo "$G<A HREF=\"$popup_page?channel=$row[0]&server_ip=$server_ip&parked_time=$row[5]&dialplan_number=$dialplan_number&extension=$extension&channel_group=$row[2]&DB=$DB&debugvars=asdfuiywer786sdg786sfg7sdsgjhg352j3452hg45f2j3h4g5f2j3h4g5f2jhg4f5j3fg45jh23f5j4h23gf&parked_by=$row[4]\" target=\"_blank\">$channel</A>$server$channel_group$park_extension$parked_by$parked_time $EG\n";
				}
			}
#	
		$i++;
		}

	}
	else
	{
	echo "********************************************************************************************** \n";
	echo "********************************************************************************************** \n";
	echo "*************************************** "._QXZ("NO PARKED CALLS",15)." ************************************** \n";
	echo "********************************************************************************************** \n";
	echo "********************************************************************************************** \n";
	}

echo "  \n\n";
echo "looking for calls on these groups:\n";
$o=0;
while($o < $group_selected)
{
	$group_print =		sprintf("%-20s", $groupselect[$o]);
	echo "  <SPAN class=\"$color_class[$o]\"><B>          </SPAN> - $group_print</B>\n";
	$o++;
}


$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "</PRE>\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";


?>

</body>
</html>

<?php
	
exit; 

?>
