<?php
# display_call_details.php - Vicidial Enhanced Reporting call details page
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1611 - First build
# 230126-1158 - Added recording log access, QXZ translation
# 240801-1130 - Code updates for PHP8 compatibility
#
$subreport_name="VERM Reports";
$report_display_type="display_call_details.php";

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["detail_id"]))			{$detail_id=$_GET["detail_id"];}
	elseif (isset($_POST["detail_id"]))	{$detail_id=$_POST["detail_id"];}
if (isset($_GET["call_type"]))			{$call_type=$_GET["call_type"];}
	elseif (isset($_POST["call_type"]))	{$call_type=$_POST["call_type"];}
if (isset($_GET["detail_span_height"]))			{$detail_span_height=$_GET["detail_span_height"];}
	elseif (isset($_POST["detail_span_height"]))	{$detail_span_height=$_POST["detail_span_height"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,agent_whisper_enabled,report_default_format,enable_pause_code_limits,allow_web_debug,admin_screen_colors,admin_web_directory,log_recording_access FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$agent_whisper_enabled =		$row[6];
	$SSreport_default_format =		$row[7];
	$SSenable_pause_code_limits =	$row[8];
	$SSallow_web_debug =			$row[9];
	$SSadmin_screen_colors =		$row[10];
	$SSadmin_web_directory =		$row[11];
	$log_recording_access =			$row[12];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$detail_id = preg_replace('/[^0-9\.\|]/','',$detail_id);
$detail_span_height = preg_replace('/[^0-9]/','',$detail_span_height);

if ($non_latin < 1)
	{
	$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$call_type = preg_replace('/[^a-zA-Z]/','',$call_type);
	}
else
	{
	$DB=preg_replace("/[^0-9\p{L}]/u","",$DB);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$call_type = preg_replace('/[^\p{L}]/u','',$call_type);
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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',0,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

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

require("VERM_global_vars.inc");  # Must be after user_authorization for sanitization/security

##### BEGIN log visit to the vicidial_report_log table #####
$LOGip = getenv("REMOTE_ADDR");
$LOGbrowser = getenv("HTTP_USER_AGENT");
$LOGscript_name = getenv("SCRIPT_NAME");
$LOGserver_name = getenv("SERVER_NAME");
$LOGserver_port = getenv("SERVER_PORT");
$LOGrequest_uri = getenv("REQUEST_URI");
$LOGhttp_referer = getenv("HTTP_REFERER");
$LOGbrowser=preg_replace("/<|>|\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/<|>|\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/<|>|\'|\"|\\\\/","",$LOGhttp_referer);
if (preg_match("/443/i",$LOGserver_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) {$LOGserver_port='';}
else {$LOGserver_port = ":$LOGserver_port";}
$LOGfull_url = "$HTTPprotocol$LOGserver_name$LOGserver_port$LOGrequest_uri";

$LOGhostname = php_uname('n');
if (strlen($LOGhostname)<1) {$LOGhostname='X';}
if (strlen($LOGserver_name)<1) {$LOGserver_name='X';}

$stmt="SELECT webserver_id FROM vicidial_webservers where webserver='$LOGserver_name' and hostname='$LOGhostname' LIMIT 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$webserver_id_ct = mysqli_num_rows($rslt);
if ($webserver_id_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$webserver_id = $row[0];
	}
else
	{
	##### insert webserver entry
	$stmt="INSERT INTO vicidial_webservers (webserver,hostname) values('$LOGserver_name','$LOGhostname');";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	$webserver_id = mysqli_insert_id($link);
	}

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$detail_id, $closecallid, $uniqueid, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

if ( (strlen($slave_db_server)>5) and (preg_match("/$subreport_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}



if (!$detail_id) {echo "Beat it."; die;}
if (!$detail_span_height) {$detail_span_height="70";}
$detail_span_height-=15; # account for headers;

# $report_name="Vox Enhanced Reporting Module - Call detail display";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$lookup_data=explode("|", $detail_id);

$uniqueid=$lookup_data[0];
$closecallid=$lookup_data[1];

### Lookup of call details for display
if (!$closecallid)
	{
	$stmt="select date_format(call_date, '%m/%d - %H:%i:%s') as call_date, phone_number, campaign_id, 0 as ivr, '0' as wait, length_in_sec as duration, '1' as queue_position, CAST(term_reason AS CHAR) as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as dnis, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, 0 as xfercallid, 'O' as direction, lead_id from vicidial_log where uniqueid='$uniqueid'";
	$vicidial_id=$uniqueid;
	}
else
	{
	$stmt="select date_format(call_date, '%m/%d - %H:%i:%s') as call_date, phone_number, campaign_id, 0 as ivr, queue_seconds as wait, if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds) as duration, queue_position, CAST(term_reason AS CHAR) as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as dnis, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, xfercallid, 'I' as direction, lead_id from vicidial_closer_log where uniqueid='$uniqueid' and closecallid='$closecallid'";
	$vicidial_id=$closecallid;
	}

$rslt=mysql_to_mysqli($stmt, $link);
# if (mysqli_num_rows($rslt)==0) {echo "No records found for ".implode(", ", $lookup_data); die;}
$row=mysqli_fetch_array($rslt);

$asterisk_call_id=GetCallerCode($uniqueid);
$ivr_events=0;
if ($row["xfercallid"]==0 || $call_type=="ivr") # Possible origin call, need to lookup IVR (may add additional clause to do this on inbound calls only)
	{
	$ivr_info_array=GetIVRInfo($uniqueid, $row["call_date"]);
		$ivr_duration=$ivr_info_array[1];
		$ivr_path=$ivr_info_array[2];
		$row["call_date"]=$ivr_info_array[3];
		$ivr_HTML=$ivr_info_array[4];
		$ivr_events=$ivr_info_array[5];
	}
$dnis=GetDNIS($uniqueid);
$recording_array=GetRecording($vicidial_id);

if ($log_recording_access<1) 
	{$recording_link="<a href='$recording_array[1]'>$recording_array[0]</a>";}
else
	{$recording_link = "<a href=\"recording_log_redirect.php?recording_id=$recording_array[2]&lead_id=$row[lead_id]&search_archived_data=$search_archived_data\" target=\"_blank\">$recording_array[0]</a>";}
	
# $xfer_array=GetTransferInfo($uniqueid);

 echo "<table border=0 width='100%' bgcolor='#FFF'><tr>";
# echo "<td>";

# echo "<div align='right'><a onClick='HideCallDetails()'><h2 class='rpt_header'>[X]</h2></a></div>";
# echo "<div align='left'><h2 class='rpt_header'>Call Detail:</h2></div>";


echo "<td align='left'>";
#echo "<div style='position:fixed; top: -5; right: -30;'><a onClick='HideAgentDetails()'><h2 class='rpt_header'>[X]</h2></a></div>";
#echo "<div align='left'><h2 class='rpt_header'>Agent Detail: $user</h2></div>";
echo "<h2 class='rpt_header'>"._QXZ("Call Detail").":</h2>";
echo "</td>";
echo "<td align='right'>";
echo "<a onClick='HideCallDetails()'><h2 class='rpt_header'>[X]</h2></a>";
echo "</td>";
echo "</tr>";
#echo "<tr><td colspan='2'>";
#echo "<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'></td></tr>";
echo "<tr><td colspan='2'><input type='button' class='actButton' value='"._QXZ("Call details")."' onClick=\"ToggleVisibility('all_call_details', 'block'); ToggleVisibility('all_ivr_details', 'none');\"> $NWB#VERM_display_call_details$NWE&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp; &nbsp;<input type='button' class='actButton' value='IVR events: $ivr_events' onClick=\"ToggleVisibility('all_call_details', 'none'); ToggleVisibility('all_ivr_details', 'block');\"> $NWB#VERM_display_call_details-IVR$NWE</td></tr>";

echo "<tr><td colspan='2'>";

echo "<span id='all_call_details' style='display:block; overflow-y:auto; height: ".$detail_span_height."vh;'>\n";
echo "<table id='rpt_table' width='100%'>";
echo "<tr><td class='bold_cell'>"._QXZ("Asterisk Call ID").":</td><td>".$asterisk_call_id."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Date and time").":</td><td>".$row["call_date"]."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Campaign/ingroup").":</td><td>".$queue_names["$row[campaign_id]"]." <i>[".$row["campaign_id"]."]</i></td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Caller ID").":</td><td>".$row["phone_number"]."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Handled by").":</td><td>".$fullname_info["$row[user]"]."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Duration").":</td><td>".$row["duration"]." sec</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Time in IVR before queueing").":</td><td>".$ivr_duration."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Waiting time").":</td><td>".$row["wait"]." sec</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Original position").":</td><td># ".$row["queue_position"]."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Disconnection cause").":</td><td>".$row["term_reason"]."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Transferred to").":</td><td></td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Attempts").":</td><td>1</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("Status code").":</td><td>".$row["status"].": ".$status_names["$row[status]"]."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("DNIS").":</td><td>".$dnis."</td></tr>";
echo "<tr><td class='bold_cell'>"._QXZ("IVR selection").":</td><td>".$ivr_path."</td></tr>";
echo "<tr><td class='bold_cell' colspan='2'>".$recording_link."</td></tr>";
echo "</table>";
echo "</span>\n";

echo "<span id='all_ivr_details' style='display:none; overflow-y:auto; height: ".$detail_span_height."vh;'>\n";
echo $ivr_HTML;
echo "</span>\n";

echo "</td></tr></table>";
#echo "</body>";
#echo "</html>";

if ($db_source == 'S')
	{
	mysqli_close($link);
	$use_slave_server=0;
	$db_source = 'M';
	require("dbconnect_mysqli.php");
	}

$ENDtime=date("U");
$endMS = microtime();
$startMSary = explode(" ",$startMS);
$endMSary = explode(" ",$endMS);
$runS = ((int)$endMSary[0] - (int)$startMSary[0]);
$runM = ((int)$endMSary[1] - (int)$startMSary[1]);
$TOTALrun = ($runS + $runM);

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);


function GetCallerCode($uniqueid)
	{
	global $link, $DB;
	$caller_code="";
	$cc_stmt="select caller_code from vicidial_log_extended where uniqueid='$uniqueid'";
	$cc_rslt=mysqli_query($link, $cc_stmt);
	$cc_row=mysqli_fetch_array($cc_rslt);
	$caller_code=$cc_row[0];
	return $caller_code;
	}

function GetIVRInfo($uniqueid, $actual_start_time)
	{
	global $link, $DB;

	$ivr_path="";
	$ivr_length=0;
	$ivr_duration=0;
	$ivr_span_HTML="<table id='rpt_table' width='100%'>";
	$ivr_span_HTML.="<tr><th>Hour</th><th>Duration</th><th>Event</th><th>Agent</th><th>...</th></tr>";

	$ivr_stmt="select extension,date_format(start_time, '%m/%d - %H:%i:%s') as start_time,comment_a,comment_b,comment_d,UNIX_TIMESTAMP(start_time),phone_ext from live_inbound_log where uniqueid='$uniqueid' and comment_a IN('CALLMENU') order by start_time";
	$ivr_rslt=mysqli_query($link, $ivr_stmt);
	$ivr_paths=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
	$ivr_counts=array();
	$ivr_event="START";
	while ($ivr_row=mysqli_fetch_array($ivr_rslt))
		{
		if(!$prev_time) {$prev_time=$ivr_row[5];}
		if(!$ivr_start_time) 
			{
			$actual_start_time=$ivr_row[1];
			$ivr_start_time=$ivr_row[5];
			}
		$ivr_path.=$ivr_row["comment_b"].",";

		$ivr_duration=$ivr_row[5]-$ivr_start_time;
		$ivr_interval_duration=$ivr_row[5]-$prev_time;
		$ivr_interval_duration_fmt=($ivr_interval_duration>=3600 ? intval(floor($ivr_interval_duration/3600)).date(":i:s", $ivr_interval_duration) : intval(date("i", $ivr_interval_duration)).":".date("s", $ivr_interval_duration));
		$ivr_length+=$ivr_duration;
		$prev_time=$ivr_row[5];
		
		if(preg_match('/\>/', $ivr_row["comment_d"])) {$ivr_event="Option: $ivr_row[comment_b]";}
		$ivr_span_HTML.="<tr><th>".substr($ivr_row["start_time"], -8)."</th><th>$ivr_interval_duration_fmt</th><th>$ivr_event</th><th>&nbsp;</th><th>".$ivr_row["comment_b"]."</th></tr>";
		$ivr_event="";
		}

	$ivr_path=preg_replace('/,$/', '', $ivr_path);
	$ivr_duration_fmt=($ivr_duration>=3600 ? floor($ivr_duration/3600).":" : "").gmdate("i:s", $ivr_duration);
	$ivr_length_fmt=($ivr_length>=3600 ? floor($ivr_length/3600).":" : "").gmdate("i:s", $ivr_length);

	$ivr_span_events=mysqli_num_rows($ivr_rslt);
	return array("$ivr_length_fmt", "$ivr_duration_fmt", "$ivr_path", "$actual_start_time", "$ivr_span_HTML", "$ivr_span_events");
	}

function GetDNIS($uniqueid)
	{
	global $link, $DB, $did_id_info, $did_pattern_info;

	$did_str="";

	$did_stmt="select extension, did_id from vicidial_did_log where uniqueid in ('$uniqueid')";
	$did_rslt=mysqli_query($link, $did_stmt);
	$did_row=mysqli_fetch_array($did_rslt);
	$did_str=$did_row["extension"]." - ".$did_id_info["$did_row[did_id]"];

	return $did_str;
	}

function GetRecording($vicidial_id)
	{
	global $link, $DB;

	$rec_str="";

	$rec_stmt="select filename, location, recording_id from recording_log where vicidial_id='$vicidial_id'";
	$rec_rslt=mysqli_query($link, $rec_stmt);
	$rec_row=mysqli_fetch_array($rec_rslt);

	return array("$rec_row[filename]", "$rec_row[location]", "$rec_row[recording_id]");
	}

?>
