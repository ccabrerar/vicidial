<?php
# closer-fronter_popup.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this is the closer popup of a specific call that starts recording the call and allows you to go and fetch info on that caller in the local CRM system.
# CHANGES
#
# 60620-1020 - Added variable filtering to eliminate SQL injection attack threat
# 61201-1114 - Added lead_id and user to recording_log entry
# 90508-0644 - Changed to PHP long tags
# 120223-2124 - Removed logging of good login passwords if webroot writable is enabled
# 130610-1115 - Finalized changing of all ereg instances to preg
# 130620-0824 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-1933 - Changed to mysqli PHP functions
# 141007-2210 - Finalized adding QXZ translation to all admin files
# 141229-2040 - Added code for on-the-fly language translations display
# 170409-1532 - Added IP List validation code
# 220224-1626 - Added allow_web_debug system setting
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["address1"]))				{$address1=$_GET["address1"];}
	elseif (isset($_POST["address1"]))		{$address1=$_POST["address1"];}
if (isset($_GET["address2"]))				{$address2=$_GET["address2"];}
	elseif (isset($_POST["address2"]))		{$address2=$_POST["address2"];}
if (isset($_GET["address3"]))				{$address3=$_GET["address3"];}
	elseif (isset($_POST["address3"]))		{$address3=$_POST["address3"];}
if (isset($_GET["alt_phone"]))				{$alt_phone=$_GET["alt_phone"];}
	elseif (isset($_POST["alt_phone"]))		{$alt_phone=$_POST["alt_phone"];}
if (isset($_GET["call_began"]))				{$call_began=$_GET["call_began"];}
	elseif (isset($_POST["call_began"]))		{$call_began=$_POST["call_began"];}
if (isset($_GET["campaign_id"]))				{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))		{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["channel"]))				{$channel=$_GET["channel"];}
	elseif (isset($_POST["channel"]))		{$channel=$_POST["channel"];}
if (isset($_GET["channel_group"]))				{$channel_group=$_GET["channel_group"];}
	elseif (isset($_POST["channel_group"]))		{$channel_group=$_POST["channel_group"];}
if (isset($_GET["city"]))				{$city=$_GET["city"];}
	elseif (isset($_POST["city"]))		{$city=$_POST["city"];}
if (isset($_GET["comments"]))				{$comments=$_GET["comments"];}
	elseif (isset($_POST["comments"]))		{$comments=$_POST["comments"];}
if (isset($_GET["country_code"]))				{$country_code=$_GET["country_code"];}
	elseif (isset($_POST["country_code"]))		{$country_code=$_POST["country_code"];}
if (isset($_GET["customer_zap_channel"]))				{$customer_zap_channel=$_GET["customer_zap_channel"];}
	elseif (isset($_POST["customer_zap_channel"]))		{$customer_zap_channel=$_POST["customer_zap_channel"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["dispo"]))				{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))		{$dispo=$_POST["dispo"];}
if (isset($_GET["email"]))				{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))		{$email=$_POST["email"];}
if (isset($_GET["end_call"]))				{$end_call=$_GET["end_call"];}
	elseif (isset($_POST["end_call"]))		{$end_call=$_POST["end_call"];}
if (isset($_GET["extension"]))				{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))		{$extension=$_POST["extension"];}
if (isset($_GET["first_name"]))				{$first_name=$_GET["first_name"];}
	elseif (isset($_POST["first_name"]))		{$first_name=$_POST["first_name"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["last_name"]))				{$last_name=$_GET["last_name"];}
	elseif (isset($_POST["last_name"]))		{$last_name=$_POST["last_name"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["parked_time"]))				{$parked_time=$_GET["parked_time"];}
	elseif (isset($_POST["parked_time"]))		{$parked_time=$_POST["parked_time"];}
if (isset($_GET["pass"]))				{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))		{$pass=$_POST["pass"];}
if (isset($_GET["phone_code"]))				{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))		{$phone_code=$_POST["phone_code"];}
if (isset($_GET["phone_number"]))				{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))		{$phone_number=$_POST["phone_number"];}
if (isset($_GET["phone"]))				{$phone=$_GET["phone"];}
	elseif (isset($_POST["phone"]))		{$phone=$_POST["phone"];}
if (isset($_GET["postal_code"]))				{$postal_code=$_GET["postal_code"];}
	elseif (isset($_POST["postal_code"]))		{$postal_code=$_POST["postal_code"];}
if (isset($_GET["province"]))				{$province=$_GET["province"];}
	elseif (isset($_POST["province"]))		{$province=$_POST["province"];}
if (isset($_GET["security"]))				{$security=$_GET["security"];}
	elseif (isset($_POST["security"]))		{$security=$_POST["security"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["session_id"]))				{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))		{$session_id=$_POST["session_id"];}
if (isset($_GET["state"]))				{$state=$_GET["state"];}
	elseif (isset($_POST["state"]))		{$state=$_POST["state"];}
if (isset($_GET["status"]))				{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))		{$status=$_POST["status"];}
if (isset($_GET["tsr"]))				{$tsr=$_GET["tsr"];}
	elseif (isset($_POST["tsr"]))		{$tsr=$_POST["tsr"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["vendor_id"]))				{$vendor_id=$_GET["vendor_id"];}
	elseif (isset($_POST["vendor_id"]))		{$vendor_id=$_POST["vendor_id"];}
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

#$DB = '1';	# DEBUG override
$US = '_';
$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$REC_TIME = date("Ymd-His");
$FILE_datetime = $STARTtime;
$parked_time = $STARTtime;
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


if ( (strlen($customer_zap_channel)>2) and (preg_match('/zap/i',$customer_zap_channel)) )
	{
	echo "\n<!-- zap channel: $customer_zap_channel -->\n";
	echo "\n<!-- session_id: $session_id -->\n";

	}
else
	{
	echo _QXZ("Bad channel").": $customer_zap_channel\n";
	echo _QXZ("Make sure the Zap channel is live and try again")."\n";
	exit;
	}

require("screen_colors.php");

echo "<html>\n";
echo "<head>\n";
echo "<title>"._QXZ("FRONTER-CLOSER: Popup")."</title>\n";
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";

if (preg_match('/CL_UNIV/i',$channel_group))
	{
	?>
	<script language="Javascript1.2">
	var btn_name="search";
	if (document.layers) {
		document.captureEvents(Event.KEYPRESS);
		document.onkeypress = function (evt) {
			if (evt.target.constructor == Input) {
				if (evt.target.name == 'lead_id' || evt.target.name == 'phone' || evt.target.name == 'confirmation_id') {
					return ((evt.which >= '0'.charCodeAt() && evt.which <= '9'.charCodeAt()));
				} 
			}
		}
	}
	function CheckForm() {
		if (btn_name=="update") {
			if (document.forms[0].phone.value.length!=10) {
				alert("<?php echo _QXZ("The phone number you entered does not have 10 digits"); ?>.\n\n<?php echo _QXZ("It has"); ?> "+document.forms[0].phone.value.length+" - <?php echo _QXZ("please correct it and try again"); ?>.");
				return false;
			} else if (document.forms[0].confirmation_id.value.length<=5) {
				alert("<?php echo _QXZ("The confirmation ID is either missing or not enough characters in length"); ?>.\n\n<?php echo _QXZ("Please correct it and try again"); ?>.");
				return false;
			} else {
				return true;
			}
		}
	}
	</script>
	<?php
	}
else 
	{
	echo "<script language=\"Javascript1.2\">\n";
	echo "function WaitFirefix() {setTimeout(document.forms[0].search_phone.focus(), 1000)}\n";
	echo "</script>\n";
	}
?>
</head>
<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onLoad="document.forms[0].search_phone.focus(); setTimeout('document.forms[0].search_phone.focus()', 1000); self.focus()">
<CENTER><FONT FACE="Courier" COLOR=BLACK SIZE=3>

<?php 

$stmt="SELECT count(*) from live_channels where server_ip='$server_ip' and channel='$customer_zap_channel'";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);
$parked_count = $row[0];

if ($parked_count > 0)
{

#	$stmt="DELETE from parked_channels where server_ip='$server_ip' and parked_time='$parked_time' and channel='$channel' LIMIT 1";
#	if ($DB) {echo "|$stmt|\n";}
#	$rslt=mysql_to_mysqli($stmt, $link);

	$DTqueryCID = "RZ$FILE_datetime$user";

	### insert a NEW record to the vicidial_manager table to be processed

	$channel = $customer_zap_channel;
	$channel = preg_replace('/Zap\//i', "", $channel);
	$SIPexten = preg_replace('/SIP\//i', "", $SIPexten);
	$filename = "$REC_TIME$US$SIPexten";

#	$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Monitor','$DTqueryCID','Channel: Zap/$channel','File: $filename','Callerid: $DTqueryCID','','','','','','','')";
#	if ($DB) {echo "|$stmt|\n";}
#	$rslt=mysql_to_mysqli($stmt, $link);

	# Local/78600098@demo-6617,2

	$stmt = "INSERT INTO vicidial_manager values('','','$SQLdate','NEW','N','" . mysqli_real_escape_string($link, $server_ip) . "','','Originate','$DTqueryCID','Channel: $local_DEF$conf_silent_prefix" . mysqli_real_escape_string($link, $session_id) . "$local_AMP$ext_context','Context: $ext_context','Exten: $recording_exten','Priority: 1','Callerid: " . mysqli_real_escape_string($link, $filename) . "','','','','','')";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename,lead_id,user) values('Zap/" . mysqli_real_escape_string($link, $channel) . "','" . mysqli_real_escape_string($link, $server_ip) . "','SIP/" . mysqli_real_escape_string($link, $SIPexten) . "','$NOW_TIME','$STARTtime','" . mysqli_real_escape_string($link, $filename) . "','$lead_id','$user')";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	$stmt="SELECT recording_id FROM recording_log where filename='" . mysqli_real_escape_string($link, $filename) . "'";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$recording_id = $row[0];

	echo _QXZ("Recording command sent for channel")." $channel - $filename - $recording_id &nbsp; &nbsp; &nbsp; $NOW_TIME\n<BR><BR>\n";

	$stmt="SELECT full_name from vicidial_users where user='" . mysqli_real_escape_string($link, $user) . "'";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$full_name = $row[0];

	echo _QXZ("Call Referred by").": $user - $full_name\n<BR><BR>\n";

   $url = "http://10.10.99.2/vicidial/closer_dispo.php?lead_id=$lead_id&channel=$channel&server_ip=$server_ip&extension=$SIPexten&call_began=$STARTtime&parked_time=$parked_time&DB=$DB";

	echo "<a href=\"$url\">"._QXZ("View Customer Info and Disposition Call")."</a>\n<BR><BR>\n";



#	$stmt="UPDATE park_log set grab_time='$NOW_TIME',status='TALKING',extension='$extension',user='$user' where parked_time='$parked_time' and server_ip='$server_ip' and  channel='$channel'";
#	if ($DB) {echo "|$stmt|\n";}
#		$fp = fopen ("./closer_SQL_updates.txt", "a");
#		fwrite ($fp, "$date|$user|$stmt|\n");
#		fclose($fp);
#	$rslt=mysql_to_mysqli($stmt, $link);

###########################################################################################
####### HERE IS WHERE YOU DEFINE DIFFERENT CONTENTS DEPENDING UPON THE CHANNEL_GROUP PREFIX 
###########################################################################################
if (preg_match('/CL_TEST/i',$channel_group))
	{
	echo _QXZ("GALLERIA TEST CLOSER GROUP").": $channel_group\n";

	$stmt="SELECT user,phone_number from vicidial_list where lead_id='" . mysqli_real_escape_string($link, $parked_by) . "';";
		if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$fronter=$row[0];
	$search_phone=$row[1];


	?>
	<form action="http://10.10.10.196/vicidial/closer_lookup3.php" method="post">
		<input type=hidden name="fronter" value="<?php echo $fronter ?>">
		<input type=hidden name="closer" value="<?php echo $user ?>">
		<input type=hidden name="group" value="<?php echo $channel_group ?>">
		<input type=hidden name="recording_id" value="<?php echo $recording_id ?>">
	<table border=0 cellspacing=5 cellpadding=3 align=center width=90%>
	<tr>
		<th colspan=2 bgcolor='#666666'><font class='standard_bold' color='white'><?php echo _QXZ("COF MW Customer Search"); ?></font></th>
	</tr>
	<tr bgcolor='#99FF99'>
		<td align=right width="50%" nowrap><font class='standard_bold'><?php echo _QXZ("Phone number"); ?></font></td>
		<td align=left width="50%" nowrap><input type=text size=10 maxlength=10 name="search_phone" value="<?php echo $search_phone ?>"></td>
	</tr>
	<tr>
		<th colspan=2 bgcolor='#666666'><input style='background-color:#<?php echo "$SSbutton_color"; ?>' type=submit name="submit_COF" value="<?php echo _QXZ("SEARCH"); ?>"></th>
	</tr>
	</table>
	</form>
	<BR><BR>
	<?php
	}

if ( (preg_match('/CL_MWCOF/i',$channel_group)) or (preg_match('/MWCOF/i',$group)) or (preg_match('/TESTCAMP/i',$group)))
	{
	echo _QXZ("BUYERS EDGE INTERNAL CLOSER GROUP").": $channel_group\n";

	$stmt="SELECT user,phone_number from vicidial_list where lead_id='" . mysqli_real_escape_string($link, $parked_by) . "';";
		if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$fronter=$row[0];
	$search_phone=$row[1];


	?>
	<form action="http://10.10.99.2/vicidial/closer_lookup4.php" method="post">
		<input type=hidden name="fronter" value="<?php echo $fronter ?>">
		<input type=hidden name="closer" value="<?php echo $user ?>">
		<input type=hidden name="group" value="<?php echo $channel_group ?>">
		<input type=hidden name="recording_id" value="<?php echo $recording_id ?>">
	<table border=0 cellspacing=5 cellpadding=3 align=center width=90%>
	<tr>
		<th colspan=2 bgcolor='#666666'><font class='standard_bold' color='white'><?php echo _QXZ("COF MW Customer Search"); ?></font></th>
	</tr>
	<tr bgcolor='#99FF99'>
		<td align=right width="50%" nowrap><font class='standard_bold'><?php echo _QXZ("Phone number"); ?></font></td>
		<td align=left width="50%" nowrap><input type=text size=10 maxlength=10 name="search_phone" value="<?php echo $search_phone ?>"></td>
	</tr>
	<tr>
		<th colspan=2 bgcolor='#666666'><input style='background-color:#<?php echo "$SSbutton_color"; ?>' type=submit name="submit_COF" value="<?php echo _QXZ("SEARCH"); ?>"></th>
	</tr>
	</table>
	</form>
	<BR><BR>
	<?php
	}



if (preg_match('/CL_GAL/i',$channel_group))
	{
	echo _QXZ("GALLERIA CLOSER GROUP").": $channel_group\n";
	$group_color='#CCCCCC';

	if (preg_match('/CL_GALLERIA/i',$channel_group))
		{
		echo "<br><font color=green size=3><b>-- "._QXZ("INTERNAL CALL GALLERIA FRONT")." --</b></font><br>\n";
		$group_color='#99FF99';
		}
	if (preg_match('/CL_GALLER2/i',$channel_group))
		{
		echo "<br><font color=red size=3><b>-- "._QXZ("TouchAsia CALL Simple Escapes FRONT")." --</b></font><br>\n";
		$group_color='#FF9999';
		}
	if (preg_match('/CL_GALLER3/i',$channel_group))
		{
		echo "<br><font color=red size=3><b>-- "._QXZ("DebitSupplies CALL Simple Escapes FRONT")." --</b></font><br>\n";
		$group_color='#FF9999';
		}
	if (preg_match('/CL_GALLER4/i',$channel_group))
		{
		echo "<br><font color=red size=3><b>-- "._QXZ("Vishnu CALL Simple Escapes FRONT")." --</b></font><br>\n";
		$group_color='#FF9999';
		}


	?>
	<form action="http://10.10.99.2/vicidial/closer_lookup4.php" method="post">
		<input type=hidden name="fronter" value="<?php echo $parked_by ?>">
		<input type=hidden name="closer" value="<?php echo $user ?>">
		<input type=hidden name="group" value="<?php echo $channel_group ?>">
		<input type=hidden name="recording_id" value="<?php echo $recording_id ?>">
	<table border=0 cellspacing=5 cellpadding=3 align=center width=90%>
	<tr>
		<th colspan=2 bgcolor='#666666'><font class='standard_bold' color='white'><?php echo _QXZ("COF MW Customer Search"); ?></font></th>
	</tr>
	<tr bgcolor="<?php echo $group_color ?>">
		<td align=right width="50%" nowrap><font class='standard_bold'><?php echo _QXZ("Phone number"); ?></font></td>
		<td align=left width="50%" nowrap><input type=text size=10 maxlength=10 name="search_phone" value="<?php echo $phone ?>"></td>
	</tr>
	<tr>
		<th colspan=2 bgcolor='#666666'><input style='background-color:#<?php echo "$SSbutton_color"; ?>' type=submit name="submit_COF" value="<?php echo _QXZ("SEARCH"); ?>"></th>
	</tr>
	</table>
	</form>
	<BR><BR>
	
<!----
	<form action="http://10.10.10.196/vicidial/closer_lookup3.php" method="post">
		<input type=hidden name="fronter" value="<?php echo $parked_by ?>">
		<input type=hidden name="closer" value="<?php echo $user ?>">
	<table border=0 cellspacing=5 cellpadding=3 align=center width=90%>
	<tr>
		<th colspan=2 bgcolor='#666666'><font class='standard_bold' color='white'>NEW COF BlueGreen Customer Search</font></th>
	</tr>
	<tr bgcolor='#CCCCCC'>
		<td align=right width="50%" nowrap><font class='standard_bold'>Phone number</font></td>
		<td align=left width="50%" nowrap><input type=text size=10 maxlength=10 name="search_phone" value="<?php echo $phone ?>"></td>
	</tr>
	<tr>
		<th colspan=2 bgcolor='#666666'><input style='background-color:#<?php echo "$SSbutton_color"; ?>' type=submit name="submit_COF" value="SEARCH"></th>
	</tr>
	</table>
	</form>
	<BR><BR>
---->

	<?php
	}


if (preg_match('/CL_UNIV/i',$channel_group))
	{
	echo _QXZ("UNIVERSAL CLOSER GROUP").": $channel_group\n";


	?>

	<form action="uk_mail_lookup.php" method="post" onSubmit="return CheckForm()">
		<input type=hidden name="fronter" value="<?php echo $parked_by ?>">
		<input type=hidden name="closer" value="<?php echo $user ?>">
		<input type=hidden name="group" value="<?php echo $channel_group ?>">
		<input type=hidden name="recording_id" value="<?php echo $recording_id ?>">
	<table border=0 width=80% cellpadding=5 cellspacing=0 align=center>
	<tr>
			<th colspan=2 bgcolor='#CCCCCC'><font class='standard_bold'><?php echo _QXZ("New Search"); ?></font></th>
	</tr>

	<tr bgcolor='#CCCCCC'>
			<td align=right width="50%" nowrap><font class='standard_bold'><?php echo _QXZ("Reservation Number"); ?>:</font></td>
			<td align=left width="50%" nowrap><input type=text size=10 maxlength=10
	name="reservation_no" value="" ONKEYPRESS="var keyCode = event.which ? event.which : event.keyCode; if (keyCode!=8 && keyCode!=9 && keyCode!=37 && keyCode!=39) return ((keyCode >= '0'.charCodeAt() && keyCode <= '9'.charCodeAt()))"></td>
	</tr>
	<tr bgcolor='#CCCCCC'><td align=right width='50%' nowrap><font class='standard_bold'><?php echo _QXZ("Confirmation"); ?> #:</font></td>
	<td align=left width='50%' nowrap><input type=text name='confirmation_no' size=10 maxlength=20 value=''></td></tr>
	<tr bgcolor='#CCCCCC'>
			<td align=right width="50%" nowrap><font class='standard_bold'><?php echo _QXZ("Phone"); ?> #:</font></td>
			<td align=left width="50%" nowrap><input type=text size=10 maxlength=10
	name="phone" value="" ONKEYPRESS="var keyCode = event.which ? event.which : event.keyCode; if (keyCode!=8 && keyCode!=9 && keyCode!=37 && keyCode!=39) return ((keyCode >= '0'.charCodeAt() && keyCode <= '9'.charCodeAt()))"></td>

	</tr><tr>
		<th colspan=2 bgcolor='#CCCCCC'><input style='background-color:#<?php echo "$SSbutton_color"; ?>' type=submit name="submit_COF" value="<?php echo _QXZ("SEARCH"); ?>" onClick="javascript:btn_name='search'"><br><br></th>
	</tr>
	<tr>
		<th colspan=2 bgcolor='#666666'><font class='standard_bold'><a href='closer_popup.php'><?php echo _QXZ("Back"); ?></a></font></th>
	</tr>
	</table>

	</form>


	<?php

	}

###########################################################################################
####### END CUSTOM CONTENTS 
###########################################################################################


#	echo "<a href=\"#\">Close this window</a>\n<BR><BR>\n";
}
else
{
	echo _QXZ("Record command FAILED for channel")." $channel &nbsp; &nbsp; &nbsp; $NOW_TIME\n<BR><BR>\n";
	echo _QXZ("Please go back and try again")."\n<BR><BR>\n";
#	echo "<form><input style='background-color:#$SSbutton_color' type=button value=\"Close This Window\" onClick=\"javascript:window.close();\"></form>\n";
}



$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";


?>


</body>
</html>

<?php
	
exit; 



?>





