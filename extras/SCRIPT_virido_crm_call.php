<?php
### SCRIPT_virido_crm_call.php - button to post to virido to initiate call in CRM
#
# To be inserted into a SCRIPT in VICIDIAL with the following code:
#
# <iframe src="/agc/SCRIPT_virido_crm_call.php?lead_id=--A--lead_id--B--&list_id=--A--list_id--B--&user=--A--user--B--&campaign_id=--A--campaign_id--B--&phone_number=--A--phone_number--B--&vendor_id=--A--vendor_lead_code--B--&user_custom_five=--A--user_custom_five--B--" style="width:600;height:400;background-color:transparent;" scrolling="no" frameborder="0" allowtransparency="true" id="popupFrame" name="popupFrame" width="600" height="400" STYLE="z-index:17"> </iframe>
#
# Copyright (C) 2013  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 
# CHANGES:
# 120223-1156 - First Build
# 130328-0014 - Converted ereg to preg functions
#

if (isset($_GET["button_id"]))				{$button_id=$_GET["button_id"];}
	elseif (isset($_POST["button_id"]))		{$button_id=$_POST["button_id"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["vendor_id"]))				{$vendor_id=$_GET["vendor_id"];}
	elseif (isset($_POST["vendor_id"]))		{$vendor_id=$_POST["vendor_id"];}
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["phone_number"]))			{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["user_custom_five"]))			{$user_custom_five=$_GET["user_custom_five"];}
	elseif (isset($_POST["user_custom_five"]))	{$user_custom_five=$_POST["user_custom_five"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["epoch"]))					{$epoch=$_GET["epoch"];}
	elseif (isset($_POST["epoch"]))			{$epoch=$_POST["epoch"];}

### security strip all non-alphanumeric characters out of the variables ###
$button_id=preg_replace("/[^0-9]/","",$button_id);
$lead_id=preg_replace("/[^0-9]/","",$lead_id);
$list_id=preg_replace("/[^0-9]/","",$list_id);
$phone_number=preg_replace("/[^0-9]/","",$phone_number);
$vendor_id = preg_replace("/[^- \:\/\_0-9a-zA-Z]/","",$vendor_id);
$user=preg_replace("/[^0-9a-zA-Z]/","",$user);
$campaign = preg_replace("/[^-\_0-9a-zA-Z]/","",$campaign);
$stage = preg_replace("/[^-\_0-9a-zA-Z]/","",$stage);
$epoch = preg_replace("/[^0-9]/","",$epoch);

require("dbconnect.php");

if (preg_match("/CLICK/i",$stage))
	{
	$epochNOW = date("U");
	$click_seconds = ($epochNOW - $epoch);
	if ( ($click_seconds < 0) or ($click_seconds > 3600) )
		{$click_seconds=0;}

	$stmt="UPDATE qr_busy_button_log SET stage='$stage',click_seconds='$click_seconds' where button_id='$button_id';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_query($stmt, $link);

	echo "Thank you for clicking the button\n";

	exit;
	}

else
	{
	$CL=':';
	$script_name = getenv("SCRIPT_NAME");
	$server_name = getenv("SERVER_NAME");
	$server_port = getenv("SERVER_PORT");
	if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
	  else {$HTTPprotocol = 'http://';}
	if (($server_port == '80') or ($server_port == '443') ) {$server_port='';}
	else {$server_port = "$CL$server_port";}
	$agcPAGE = "$HTTPprotocol$server_name$server_port$script_name";

	$epoch = date("U");
	$SQLdate = date("Y-m-d H:i:s");

#	$stmt="INSERT INTO qr_busy_button_log SET lead_id='$lead_id',list_id='$list_id',phone_number='$phone_number',vendor_lead_code='$vendor_id',user='$user',campaign_id='$campaign',stage='DISPLAY',event_time='$SQLdate';";
#	if ($DB) {echo "|$stmt|\n";}
#	$rslt=mysql_query($stmt, $link);
#	$button_id = mysql_insert_id($link);


#	Client_Code: XYZ (Default)
#	Vendor_Code: ABC (Default)
#	Campaign_Code: XX12 (Default)
#	BTN: Outbound Dialed Phone number as specified in the lead file (This number will be used to identify the record from the lead file)
#	User_Name: This is the Agent Id of the CSR which are used in the daily files sent to us for import. These Ids will also be used by the agent to log into the scripting application

	$virido_url = 'http://iconnect1.intellicomsoftware.com/script/dialercall.aspx';

	echo "<B>Client_Code: </B>XYZ<BR>\n";
	echo "<B>Vendor_Code: </B>ABC<BR>\n";
	echo "<B>Campaign_Code: </B>XX12<BR>\n";
	echo "<B>User_Name: </B>$user_custom_five<BR>\n";
	echo "<B>BTN: </B>$phone_number<BR>\n";
	echo "<BR>\n";

	echo "<FORM NAME=button_form ID=button_form ACTION=\"$virido_url\" METHOD=POST>\n";
	echo "<INPUT TYPE=HIDDEN NAME=Client_Code VALUE=\"XYZ\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=Vendor_Code VALUE=\"ABC\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=Campaign_Code VALUE=\"XX12\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=User_Name VALUE=\"$user_custom_five\">\n";
	echo "<INPUT TYPE=HIDDEN NAME=BTN VALUE=\"$phone_number\">\n";
	echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE=\"Initiate Phone Call\">\n";
	echo "</FORM>\n\n";
	}

?>

