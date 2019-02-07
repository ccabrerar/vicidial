<?php
# SCRIPT_multirecording.php - script page that stops/starts recordings being made over a forced-recording (ALLFORCE) call
# 
# Copyright (C) 2018  Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# - works in conjunction with SCRIPT_multirecording_AJAX.php to allow reps the ability to make their own recordings over the course of a call while the entire call is being recorded into its own file, as in ALLFORCE recording.
#
# Implementation is done by creating a script in the Scripts section that calls this page within an <iframe> tag which passes several essential dialer variables to the page 
#
# SAMPLE SCRIPT TEXT:
# <iframe src="http://<your-domain.com>/agc/SCRIPT_multirecording.php?campaign=--A--campaign--B--&lead_id=--A--lead_id--B--&phone_number=--A--phone_number--B--&session_id=--A--session_id--B--&server_ip=--A--server_ip--B--&user=--A--user--B--&vendor_lead_code=--A--vendor_lead_code--B--&uniqueid=--A--uniqueid--B--" style="width:570;height:275;background-color:transparent;" scrolling="auto" frameborder="0" allowtransparency="true" id="popupFrame" name="popupFrame" width="570" height="270"></iframe>
#
# All style parameters may be adjusted to suit your needs, but all variables listed in the above example MUST be passed to the VICI_multirecording.php page if this is implemented. 
# Variables are: campaign, lead_id, phone_number, session_id, server_ip, user, vendor_lead_code, uniqueid
#
# CHANGELOG
# 120227-1512 - First Build
# 160401-0026 - HTML formatting fixes
# 170526-2158 - Added variable filtering
# 180514-2230 - Switched to mysqli, fixed variable filtering bug
#

require("dbconnect_mysqli.php");
if (isset($_GET["campaign"]))	{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))	{$campaign=$_POST["campaign"];}
if (isset($_GET["lead_id"]))	{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))	{$lead_id=$_POST["lead_id"];}
if (isset($_GET["phone_number"]))	{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["user"]))	{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))	{$user=$_POST["user"];}
if (isset($_GET["session_id"]))	{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))	{$session_id=$_POST["session_id"];}
if (isset($_GET["server_ip"]))	{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
if (isset($_GET["uniqueid"]))	{$uniqueid=$_GET["uniqueid"];}
	elseif (isset($_POST["uniqueid"]))	{$uniqueid=$_POST["uniqueid"];}
if (isset($_GET["vendor_lead_code"]))	{$vendor_lead_code=$_GET["vendor_lead_code"];}
	elseif (isset($_POST["vendor_lead_code"]))	{$vendor_lead_code=$_POST["vendor_lead_code"];}


# filter variables
$campaign = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign);
$phone_number = preg_replace('/[^-_0-9a-zA-Z]/','',$phone_number);
$lead_id = preg_replace('/[^0-9]/','',$lead_id);
$session_id = preg_replace('/[^0-9]/','',$session_id);
$vendor_lead_code = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$vendor_lead_code);
$user = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$user);
$server_ip = preg_replace('/[^\.0-9]/','',$server_ip);
$uniqueid = preg_replace('/[^-_\.0-9a-zA-Z]/','',$uniqueid);
$rec_action = preg_replace('/[^0-9a-zA-Z]/','',$rec_action);
$recording_channel = preg_replace("/\||`|&|\'|\"|\\\\| /","",$recording_channel);


?>
<html>
<head>
<title>script multirecording button page</title>
<style type="text/css">
input.red_btn{
   color:#FFFFFF;
   font-size:84%;
   font-weight:bold;
   background-color:#990000;
   border:2px solid;
   border-top-color:#FFCCCC;
   border-left-color:#FFCCCC;
   border-right-color:#660000;
   border-bottom-color:#660000;
}

input.green_btn{
   color:#FFFFFF;
   font-size:84%;
   font-weight:bold;
   background-color:#009900;
   border:2px solid;
   border-top-color:#CCFFCC;
   border-left-color:#CCFFCC;
   border-right-color:#006600;
   border-bottom-color:#006600;
}
</style>
<script language="Javascript">
function RecordingAction(campaign, lead_id, phone_number, user, session_id, server_ip, vendor_lead_code, uniqueid, rec_action, recording_channel) {
	document.getElementById("recording_button_span").innerHTML = "<b><font color='red'><blink>Please wait...</blink></font></b>";
	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		if (rec_action=="START") 
			{
			recording_query = "&campaign=" + campaign + "&lead_id=" + lead_id + "&phone_number=" + phone_number + "&user=" + user + "&session_id=" + session_id + "&server_ip=" + server_ip + "&vendor_lead_code=" + vendor_lead_code + "&uniqueid=" + uniqueid + "&rec_action=" + rec_action;
			}
		else 
			{
			recording_query = "&campaign=" + campaign + "&lead_id=" + lead_id + "&phone_number=" + phone_number + "&user=" + user + "&session_id=" + session_id + "&server_ip=" + server_ip + "&vendor_lead_code=" + vendor_lead_code + "&uniqueid=" + uniqueid + "&rec_action=" + rec_action + "&recording_channel=" + recording_channel;
			}
		xmlhttp.open('POST', 'SCRIPT_multirecording_AJAX.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(recording_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				recording_response = null;
				recording_response = xmlhttp.responseText;
			//	alert(recording_query);
			//	alert(xmlhttp.responseText);
				if (recording_response.length>0 && rec_action=="START") 
					{
					var recording_response_array=recording_response.split("|");
					var recording_id=recording_response_array[0];
					var recording_channel=recording_response_array[1];
					document.getElementById("recording_button_span").innerHTML = "<input type=\"button\" class=\"red_btn\" value=\"STOP RECORDING\" onclick=\"RecordingAction(<?php echo "'$campaign', '$lead_id', '$phone_number', '$user', '$session_id', '$server_ip', '$vendor_lead_code', '$uniqueid'"; ?>, '"+recording_id+"', '"+recording_channel+"')\" />";
					}
				else if (recording_response=="HANGUP SUCCESSFUL")
					{
					document.getElementById("recording_button_span").innerHTML = "<input type=\"button\" class=\"green_btn\" onClick=\"RecordingAction(<?php echo "'$campaign', '$lead_id', '$phone_number', '$user', '$session_id', '$server_ip', '$vendor_lead_code', '$uniqueid'"; ?>, 'START');\" value=\"START RECORDING\">";
					}
				}
			}
		delete xmlhttp;
		}
}
</script>
</head>
<body>
<form action="<?php echo $PHP_SELF; ?>" method="post" target="_self">
<center>
<!-- You may put script text here //-->
<span id="recording_button_span">
<input type="button" class="green_btn" onClick="RecordingAction(<?php echo "'$campaign', '$lead_id', '$phone_number', '$user', '$session_id', '$server_ip', '$vendor_lead_code', '$uniqueid'"; ?>, 'START')" value="START RECORDING">
</span>
<!-- You may also put script text here //-->
</center>
</form>
</body>
</html>
</iframe>
