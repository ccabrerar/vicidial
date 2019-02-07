<?php
# SCRIPT_multirecording_AJAX.php - script that stops/starts recordings being made over a forced-recording (ALLFORCE) call
# 
# Copyright (C) 2018  Joe Johnson, Matt Florell <mattf@vicidial.com>    LICENSE: AGPLv2
#
# Other scripts that this application depends on:
# - SCRIPT_multirecording.php: Gives agents ability to stop and start recordings over a forced-recording (ALLFORCE) call
#
# CHANGELOG
# 120224-2240 - First Build
# 130328-0009 - Converted ereg to preg functions
# 160401-0026 - Fix for Asterisk 1.8
# 170526-2157 - Added variable filtering
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
if (isset($_GET["rec_action"]))	{$rec_action=$_GET["rec_action"];}
	elseif (isset($_POST["rec_action"]))	{$rec_action=$_POST["rec_action"];}
if (isset($_GET["recording_channel"]))	{$recording_channel=$_GET["recording_channel"];}
	elseif (isset($_POST["recording_channel"]))	{$recording_channel=$_POST["recording_channel"];}

$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$NOWnum = date("YmdHis");
$exten="8309";
$ext_context="default";
#if (eregi("^10.10.", $server_ip)) {$ext_context="demo";} else {$ext_content="default";}

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

$stmt="select campaign_rec_filename from vicidial_campaigns where campaign_id='$campaign'";
$rslt=mysqli_query($link, $stmt);
$row=mysqli_fetch_array($rslt);
$filename=$row["campaign_rec_filename"];
$filename=preg_replace("/CAMPAIGN/i", $campaign, $filename);
$filename=preg_replace("/INGROUP/i", $campaign, $filename);
$filename=preg_replace("/CUSTPHONE/i", $phone_number, $filename);
$filename=preg_replace("/FULLDATE/i", date("Ymd-His"), $filename);
$filename=preg_replace("/TINYDATE/i", (date("Y")-2000).date("mdHis"), $filename);
$filename=preg_replace("/AGENT/i", $user, $filename);
$filename=preg_replace("/EPOCH/i", $StarTtime, $filename);
$filename=preg_replace("/VENDORLEADCODE/i", $vendor_lead_code, $filename);
$filename=preg_replace("/LEADID/i", $lead_id, $filename);

$channel="Local/5".$session_id."@".$ext_context;
$ext_priority=1;
$one_mysql_log=0;
$row='';   $rowx='';
$channel_live=1;

if ($rec_action=="START") 
	{

	if ( (strlen($exten)<3) or (strlen($channel)<4) or (strlen($filename)<15)) {
			$channel_live=0;
			echo "ERROR 1";
	} else {
	######################## START RECORDING ##########################

		$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$channel%\" and (channel LIKE \"%,1\" or channel LIKE \"%;1\");";
		$rslt=mysqli_query($link, $stmt);
		$channel_SQL=" and channel not in (";
		while($row=mysqli_fetch_array($rslt))
			{
			$channel_SQL.="'$row[channel]',";
			}
		$channel_SQL=substr($channel_SQL,0,-1);
		$channel_SQL.=")";



		$VDvicidial_id='';
		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Originate','$filename','Channel: $channel','Context: $ext_context','Exten: $exten','Priority: $ext_priority','Callerid: $filename','','','','','');";
		$rslt=mysqli_query($link, $stmt);

		$stmt = "INSERT INTO recording_log (channel,server_ip,extension,start_time,start_epoch,filename,lead_id,user) values('$channel','$server_ip','$exten','$NOW_TIME','$StarTtime','$filename','$lead_id','$user')";
		$rslt=mysqli_query($link, $stmt);
		$RLaffected_rows = mysqli_affected_rows($link);
		if ($RLaffected_rows > 0) {
			usleep(2000000);
			$recording_id = mysqli_insert_id($link);
			$current_rec_filename=$filename;
			$channel_stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$channel%\" and (channel LIKE \"%,1\" or channel LIKE \"%;1\") $channel_SQL;";
			$channel_rslt=mysqli_query($link, $channel_stmt);
			if (mysqli_num_rows($channel_rslt)==1) 
				{
				$channel_row=mysqli_fetch_row($channel_rslt);
				echo "$recording_id|$channel_row[0]";
				}
			else 
				{
				echo "Error 2\n$channel_stmt\n".mysqli_num_rows($channel_rslt);
				}
		} else {
			echo "Error 3";
		}

		##### get call type from vicidial_live_agents table
		$VLA_inOUT='NONE';
		$stmt="SELECT comments FROM vicidial_live_agents where user='$user' order by last_update_time desc limit 1;";
		$rslt=mysqli_query($link, $stmt);
		$VLA_inOUT_ct = mysqli_num_rows($rslt);
		if ($VLA_inOUT_ct > 0) {
			$row=mysqli_fetch_row($rslt);
			$VLA_inOUT =		$row[0];
		}
		if ($VLA_inOUT == 'INBOUND') {
			$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));

			##### look for the vicidial ID in the vicidial_closer_log table
			$stmt="SELECT closecallid FROM vicidial_closer_log where lead_id='$lead_id' and user='$user' and call_date > \"$four_hours_ago\" order by closecallid desc limit 1;";
		} else {
			##### look for the vicidial ID in the vicidial_log table
			$stmt="SELECT uniqueid FROM vicidial_log where uniqueid='$uniqueid' and lead_id='$lead_id';";
		}
/*
		$rslt=mysqli_query($link, $stmt);
		$VM_mancall_ct = mysqli_num_rows($rslt);
		if ($VM_mancall_ct > 0) {
			$row=mysqli_fetch_row($rslt);
			$VDvicidial_id =	$row[0];	
			$stmt = "UPDATE recording_log SET vicidial_id='$VDvicidial_id' where recording_id='$recording_id';";
			$rslt=mysqli_query($link, $stmt);
		}
*/
	}

	}
else
	{
############### STOP RECORDING ########
	$stmt="SELECT recording_id,start_epoch FROM recording_log where recording_id='$rec_action' and end_epoch is null";
	$rslt=mysqli_query($link, $stmt);
	$rec_count = mysqli_num_rows($rslt);
	if ($rec_count>0) {
		$row=mysqli_fetch_row($rslt);
		$recording_id = $row[0];
		$start_time = $row[1];
		$length_in_sec = ($StarTtime - $start_time);
		$length_in_min = ($length_in_sec / 60);
		$length_in_min = sprintf("%8.2f", $length_in_min);

		$stmt = "UPDATE recording_log set end_time='$NOW_TIME',end_epoch='$StarTtime',length_in_sec=$length_in_sec,length_in_min='$length_in_min' where recording_id='$rec_action'";
		$rslt=mysqli_query($link, $stmt);
	}

	# find and hang up the recording 
	$stmt="SELECT channel FROM live_sip_channels where server_ip = '$server_ip' and channel LIKE \"$recording_channel%\" and (channel LIKE \"%,1\" or channel LIKE \"%;1\");";
	$rslt=mysqli_query($link, $stmt);
	$rec_count = mysqli_num_rows($rslt);

	$h=0;
	while ($rec_count>$h) {
		$rowx=mysqli_fetch_row($rslt);
		$HUchannel[$h] = $rowx[0];
		$h++;
	}
	$i=0;
	while ($h>$i) {
		$stmt="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Hangup','RH12345$StarTtime$i','Channel: $HUchannel[$i]','','','','','','','','','');";
		$rslt=mysqli_query($link, $stmt);
		$i++;
		echo "HANGUP SUCCESSFUL";
	}

	}
######################################
?>
