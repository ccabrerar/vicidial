<?php
# closer_popup.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this is the closer popup of a specific call that grabs the call and allows you
# to go and fetch info on that caller in the local CRM system.
#
# CHANGES
#
# 60620-1029 - Added variable filtering to eliminate SQL injection attack threat
# 90508-0644 - Changed to PHP long tags
# 120223-2135 - Removed logging of good login passwords if webroot writable is enabled
# 130610-1113 - Finalized changing of all ereg instances to preg
# 130620-0010 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-1931 - Changed to mysqli PHP functions
# 141007-2213 - Finalized adding QXZ translation to all admin files
# 141229-2035 - Added code for on-the-fly language translations display
# 170409-1532 - Added IP List validation code
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
	elseif (isset($_POST["group_selected"]))	{$group_selected=$_POST["group_selected"];}
if (isset($_GET["dialplan_number"]))			{$dialplan_number=$_GET["dialplan_number"];}
	elseif (isset($_POST["dialplan_number"]))	{$dialplan_number=$_POST["dialplan_number"];}
if (isset($_GET["extension"]))				{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))		{$extension=$_POST["extension"];}
if (isset($_GET["groupselect"]))				{$groupselect=$_GET["groupselect"];}
	elseif (isset($_POST["groupselect"]))		{$groupselect=$_POST["groupselect"];}
if (isset($_GET["PHONE_LOGIN"]))				{$PHONE_LOGIN=$_GET["PHONE_LOGIN"];}
	elseif (isset($_POST["PHONE_LOGIN"]))		{$PHONE_LOGIN=$_POST["PHONE_LOGIN"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["user"]))			{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))	{$user=$_POST["user"];}
if (isset($_GET["channel"]))			{$channel=$_GET["channel"];}
	elseif (isset($_POST["channel"]))	{$channel=$_POST["channel"];}
if (isset($_GET["parked_time"]))			{$parked_time=$_GET["parked_time"];}
	elseif (isset($_POST["parked_time"]))	{$parked_time=$_POST["parked_time"];}
if (isset($_GET["channel_group"]))			{$channel_group=$_GET["channel_group"];}
	elseif (isset($_POST["channel_group"]))	{$channel_group=$_POST["channel_group"];}
if (isset($_GET["debugvars"]))				{$debugvars=$_GET["debugvars"];}
	elseif (isset($_POST["debugvars"]))		{$debugvars=$_POST["debugvars"];}
if (isset($_GET["parked_by"]))				{$parked_by=$_GET["parked_by"];}
	elseif (isset($_POST["parked_by"]))		{$parked_by=$_POST["parked_by"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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

#$DB=1;
$US = '_';
$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$REC_TIME = date("Ymd-His");
$FILE_datetime = $STARTtime;

$ext_context = 'demo';

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

require("screen_colors.php");

echo "<html>\n";
echo "<head>\n";
echo "<title>"._QXZ("VICIDIAL CLOSER: Popup")."</title>\n";
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
<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onLoad="document.forms[0].search_phone.focus(); setTimeout('document.forms[0].search_phone.focus()', 1000)">
<CENTER><FONT FACE="Courier" COLOR=BLACK SIZE=3>

<?php 

$stmt="SELECT count(*) from parked_channels where server_ip='$server_ip' and parked_time='$parked_time' and channel='$channel'";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);
$parked_count = $row[0];

if ($parked_count > 0)
{
	$stmt="DELETE from parked_channels where server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' and parked_time='" . mysqli_real_escape_string($link, $parked_time) . "' and channel='" . mysqli_real_escape_string($link, $channel) . "' LIMIT 1";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);


	#$monitor_channel = preg_replace('/Zap\//i', "", $channel);
	$monitor_channel = preg_replace('/\-1/i', "", $channel);
	$SIPexten = $extension;
	$filename = "$REC_TIME$US$SIPexten";
	$DTqueryCID = "RR$FILE_datetime$PHP_AUTH_USER";

#	$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Monitor','$DTqueryCID','Channel: $monitor_channel','File: $filename','Callerid: $DTqueryCID','','','','','','','')";
#	if ($DB) {echo "|$stmt|\n";}
#	$rslt=mysql_to_mysqli($stmt, $link);

#	$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename) values('$monitor_channel','$server_ip','SIP/$SIPexten','$NOW_TIME','$STARTtime','$filename')";
#	if ($DB) {echo "|$stmt|\n";}
#	$rslt=mysql_to_mysqli($stmt, $link);

#	$stmt="SELECT recording_id FROM recording_log where filename='$filename'";
#	$rslt=mysql_to_mysqli($stmt, $link);
#	if ($DB) {echo "$stmt\n";}
#	$row=mysqli_fetch_row($rslt);
#	$recording_id = $row[0];

#	echo "Recording command sent for channel $channel - $filename - $recording_id<BR>\n";

	### insert a NEW record to the vicidial_manager table to be processed
	$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','" . mysqli_real_escape_string($link, $server_ip) . "','','Redirect','$DTqueryCID','Exten: $dialplan_number','Channel: " . mysqli_real_escape_string($link, $channel) . "','Context: $ext_context','Priority: 1','Callerid: $DTqueryCID','','','','','')";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	echo _QXZ("Redirect command sent for channel")." $channel &nbsp; &nbsp; &nbsp; $NOW_TIME\n<BR><BR>\n";

	$stmt="SELECT full_name from vicidial_users where user='" . mysqli_real_escape_string($link, $parked_by) . "'";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$full_name = $row[0];

	echo _QXZ("Call Referred by").": $parked_by - $full_name\n<BR><BR>\n";

   $url = "http://10.10.10.196/vicidial/closer_dispo.php?lead_id=$parked_by&channel=$channel&server_ip=$server_ip&extension=$extension&call_began=$STARTtime&parked_time=$parked_time&DB=$DB";

	echo "<a href=\"$url\">"._QXZ("View Customer Info and Disposition Call")."</a>\n<BR><BR>\n";



	$stmt="UPDATE park_log set grab_time='$NOW_TIME',status='TALKING',extension='" . mysqli_real_escape_string($link, $extension) . "',user='$PHP_AUTH_USER' where parked_time='" . mysqli_real_escape_string($link, $parked_time) . "' and server_ip='" . mysqli_real_escape_string($link, $server_ip) . "' and  channel='" . mysqli_real_escape_string($link, $channel) . "'";
	if ($DB) {echo "|$stmt|\n";}
	#	$fp = fopen ("./closer_SQL_updates.txt", "a");
	#	fwrite ($fp, "$date|$PHP_AUTH_USER|$stmt|\n");
	#	fclose($fp);


	$rslt=mysql_to_mysqli($stmt, $link);

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
		<input type=hidden name="closer" value="<?php echo $PHP_AUTH_USER ?>">
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

if (preg_match('/CL_MWCOF/i',$channel_group))
	{
	echo _QXZ("GALLERIA INTERNAL CLOSER GROUP").": $channel_group\n";

	$stmt="SELECT user,phone_number from vicidial_list where lead_id='" . mysqli_real_escape_string($link, $parked_by) . "';";
		if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$fronter=$row[0];
	$search_phone=$row[1];


	?>
	<form action="http://10.10.10.196/vicidial/closer_lookup3.php" method="post">
		<input type=hidden name="fronter" value="<?php echo $fronter ?>">
		<input type=hidden name="closer" value="<?php echo $PHP_AUTH_USER ?>">
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
	<form action="http://10.10.10.196/vicidial/closer_lookup3.php" method="post">
		<input type=hidden name="fronter" value="<?php echo $parked_by ?>">
		<input type=hidden name="closer" value="<?php echo $PHP_AUTH_USER ?>">
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
		<input type=hidden name="closer" value="<?php echo $PHP_AUTH_USER ?>">
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
		<input type=hidden name="closer" value="<?php echo $PHP_AUTH_USER ?>">
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
	echo _QXZ("Redirect command FAILED for channel")." $channel &nbsp; &nbsp; &nbsp; $NOW_TIME\n<BR><BR>\n";
	echo "<form><input style='background-color:#$SSbutton_color' type=button value=\""._QXZ("Close This Window")."\" onClick=\"javascript:window.close();\"></form>\n";
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





