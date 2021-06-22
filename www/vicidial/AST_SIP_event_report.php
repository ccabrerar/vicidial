<?php 
# AST_SIP_event_report.php
# 
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 190607-1522 - First build
# 191013-0824 - Fixes for PHP7
# 200409-1634 - Fixed help popup
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_date_D"]))			{$query_date_D=$_GET["query_date_D"];}
	elseif (isset($_POST["query_date_D"]))	{$query_date_D=$_POST["query_date_D"];}
if (isset($_GET["query_date_T"]))			{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["shift"]))					{$shift=$_GET["shift"];}
	elseif (isset($_POST["shift"]))			{$shift=$_POST["shift"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["color"]))					{$color=$_GET["color"];}
	elseif (isset($_POST["color"]))			{$color=$_POST["color"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$query_date = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','',$query_date);
$query_date_D = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','',$query_date_D);
$query_date_T = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','',$query_date_T);
$group = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','',$group);
$shift = preg_replace('/[^0-9]/','',$shift);
$stage = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','',$stage);
$file_download = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','',$file_download);
$color = preg_replace('/[^0-9a-zA-Z]/','',$color);
$DB = preg_replace('/[^0-9a-zA-Z]/','',$DB);
$report_display_type = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','',$report_display_type);
$search_archived_data = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','',$search_archived_data);
if (strlen($color)<1) {$color='E6E6E6';}
$report_name = 'SIP Event Report';
$db_source = 'M';
$JS_text="<script language='Javascript'>\n";
$JS_onload="onload = function() {\n";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format,sip_event_logging FROM system_settings;";
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
	$SSreport_default_format =		$row[6];
	$SSsip_event_logging =			$row[7];
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_log_extended_sip","vicidial_log_extended","vicidial_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_log_extended_sip_table=use_archive_table("vicidial_log_extended_sip");
	$vicidial_log_extended_table=use_archive_table("vicidial_log_extended");
	$vicidial_log_table=use_archive_table("vicidial_log");
	$lead_archive_link='&archive_log=Yes';
	}
else
	{
	$vicidial_log_extended_sip_table="vicidial_log_extended_sip";
	$vicidial_log_extended_table="vicidial_log_extended";
	$vicidial_log_table="vicidial_log";
	$lead_archive_link='';
	}
#############

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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

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
	if ($SSsip_event_logging < 1)
		{
		$VDdisplayMESSAGE = _QXZ("SIP event logging is disabled on this system");
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

##### BEGIN display sip event details #####
if (strlen($stage)>19)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo "<HTML><HEAD><TITLE>SIP Event Detail</TITLE></HEAD>\n";
	echo "<BODY BGCOLOR=white><table cellpadding=4 bgcolor=#$color><tr><td>\n";

	# gather details for this call
	$call_date='';
	$stmt="SELECT call_date,invite_to_ring,ring_to_final,invite_to_final,last_event_code,UNIX_TIMESTAMP(call_date) from ".$vicidial_log_extended_sip_table." where caller_code = '$stage' order by call_date desc limit 1;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB > 0) {echo "$stmt\n";}
	$VLESrows_to_print = mysqli_num_rows($rslt);
	if ($VLESrows_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$call_date =			$row[0];
		$invite_to_ring =		$row[1];
		$ring_to_final =		$row[2];
		$invite_to_final =		$row[3];
		$last_event_code =		$row[4];
		$UNIXcall_date =		$row[5];
		}
	else
		{
		# gather details for this call
		$stmt="SELECT call_date,UNIX_TIMESTAMP(call_date) from ".$vicidial_log_extended_table." where caller_code = '$stage' order by call_date desc limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 0) {echo "$stmt\n";}
		$VLESrows_to_print = mysqli_num_rows($rslt);
		if ($VLESrows_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$call_date =			$row[0];
			$invite_to_ring =		'not found';
			$ring_to_final =		'not found';
			$invite_to_final =		'not found';
			$last_event_code =		'not found';
			$UNIXcall_date =		$row[1];
			}
		}

	if (strlen($call_date) > 7)
		{
		# find out the newest sip event archive log entry
		$NEWESTarchiveDATE=0;
		$stmt="SELECT end_event_date,UNIX_TIMESTAMP(end_event_date) from vicidial_sip_event_archive_details where end_event_date != '0000-00-00 00:00:00.000000' order by end_event_date desc limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 0) {echo "$stmt\n";}
		$VSEADrows_to_print = mysqli_num_rows($rslt);
		if ($VSEADrows_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$NEWESTarchiveDATE =			$row[1];
			}

		if ($UNIXcall_date > $NEWESTarchiveDATE)
			{$sip_event_table = 'vicidial_sip_event_log';}
		else
			{
			$sip_event_table = '';
			# find out the oldest sip event archive log entry
			$OLDESTarchiveDATE=0;
			$stmt="SELECT start_event_date,UNIX_TIMESTAMP(start_event_date) from vicidial_sip_event_archive_details where end_event_date != '0000-00-00 00:00:00.000000' order by start_event_date limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB > 0) {echo "$stmt\n";}
			$VSEADrows_to_print = mysqli_num_rows($rslt);
			if ($VSEADrows_to_print > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$OLDESTarchiveDATE =			$row[1];
				}
			if ($UNIXcall_date < $OLDESTarchiveDATE)
				{$sip_event_table = '';}
			else
				{
				# find out what sip event log table the detail records are in
				$stmt="SELECT wday from vicidial_sip_event_archive_details where start_event_date <= \"$call_date\" and end_event_date >= \"$call_date\" limit 1;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB > 0) {echo "$stmt\n";}
				$VLESrows_to_print = mysqli_num_rows($rslt);
				if ($VLESrows_to_print > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$SIPwday =			$row[0];
					$sip_event_table = "vicidial_sip_event_log_".$SIPwday;
					}
				}
			}

		echo "<PRE><FONT SIZE=2>";
		echo ""._QXZ("Call").": $stage    "._QXZ("Post-Dial").": $invite_to_ring    "._QXZ("Ring-Time").": $ring_to_final    "._QXZ("Total-Dial").": $invite_to_final     ";
		echo "<span onClick=\"ClearAndHideDetailDiv()\">[X]</span>\n";
		if (strlen($sip_event_table) > 10)
			{
			# gether sip event log records for this call
			$stmt="SELECT channel,server_ip,uniqueid,sip_call_id,event_date,sip_event,UNIX_TIMESTAMP(event_date) from $sip_event_table where caller_code = '$stage' order by event_date limit 999;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB > 0) {echo "$stmt\n";}
			$SELrows_to_print = mysqli_num_rows($rslt);
			$h=0;
			while ($SELrows_to_print > $h)
				{
				$row=mysqli_fetch_row($rslt);
				if ($h == 0)
					{
					$Schannel =		$row[0];
					$Sserver_ip =	$row[1];
					$Suniqueid =	$row[2];
					$Ssip_call_id = $row[3];
					$F_last_time =	$row[6];
					$S_last_time =	$row[6];
					$S_length='';
					echo ""._QXZ("Channel").": $Schannel    "._QXZ("Server IP").": $Sserver_ip    "._QXZ("UniqueID").": $Suniqueid \n";
					echo ""._QXZ("SIP Call ID").": $Ssip_call_id \n";
					echo "+-----+-----------------------------+------------+------------+\n";
					echo "| #   | "._QXZ("Date Time Microseconds",27)." | "._QXZ("Event",10)." | "._QXZ("Length",10)." |\n";
					echo "+-----+-----------------------------+------------+------------+\n";
					}
				else
					{
					$S_length=($row[6] - $S_last_time);
					$S_length = sprintf("%01.6f",$S_length);
					$S_last_time =	$row[6];
					}
				$Sevent_date =			$row[4];
				$Ssip_event =			$row[5];
				$Sevent_dateU =			$row[6];

				$h++;
				echo "| ".sprintf("%3s", $h)." |";
				echo " ".sprintf("%27s", $Sevent_date)." |";
				echo " ".sprintf("%10s", $Ssip_event)." |";
				echo " ".sprintf("%10s", $S_length)." |\n";
				}
			if ($h > 1)
				{
				$S_length=($S_last_time - $F_last_time);
				$S_length = sprintf("%01.6f",$S_length);
				$S_length = sprintf("%10s",$S_length);
				echo "+-----+-----------------------------+------------+------------+\n";
				echo "                                                 | $S_length |\n";
				}
			}
		else
			{echo ""._QXZ("No SIP event details available")." \n";}
		echo "</PRE>";
		}
	else
		{echo ""._QXZ("No call details found").": $stage \n";}


	echo "</td></tr></table></BODY></HTML>\n";
	exit;
	}
##### END display sip event details #####


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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group, $query_date, $query_date_D, $query_date_T, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

$LOGadmin_viewable_groupsSQL='';
$vuLOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vuLOGadmin_viewable_groupsSQL = "and vicidial_users.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}

$MT[0]='';
if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}
if (strlen($shift) < 1) {$shift = '0';}
$shiftSQL = "$shift,1000";
$next_shift = ($shift + 1000);
$prev_shift = ($shift - 1000);
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
$query_date_BEGIN = "$query_date $query_date_D";
$query_date_END = "$query_date $query_date_T";
if (strlen($group) < 1) {$group = 'call_date desc';}

$LINKbase = "$PHP_SELF?query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T&group=$group&DB=$DB&search_archived_data=$search_archived_data&report_display_type=$report_display_type";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

if ($file_download < 1)
	{
	?>

	<HTML>
	<HEAD>
	<STYLE type="text/css">
	<!--
	   .yellow {color: white; background-color: yellow}
	   .red {color: white; background-color: red}
	   .blue {color: white; background-color: blue}
	   .purple {color: white; background-color: purple}
	-->
	 </STYLE>

	<?php

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";

	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:100';'></div>";

	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
	require("chart_button.php");
	echo "<script src='chart/Chart.js'></script>\n"; 
	echo "<script language=\"JavaScript\" src=\"vicidial_chart_functions.js\"></script>\n";
	echo "<div id='DetailDisplayDiv' style='position:absolute; top:0; left:0; z-index:20; background-color:white display:none;'></div>\n";
	echo "<script language=\"JavaScript\">\n";
	echo "mouseY=0;\n";
	echo "function getMousePos(event) {mouseY=event.pageY;}\n";
	echo "document.addEventListener(\"click\", getMousePos);\n";
	echo "// Detect if the browser is IE or not.\n";
	echo "// If it is not IE, we assume that the browser is NS.\n";
	echo "var IE = document.all?true:false\n";
	echo "// If NS -- that is, !IE -- then set up for mouse capture\n";
	echo "if (!IE) document.captureEvents(Event.MOUSEMOVE)\n";
	echo "\n";
	echo "function ClearAndHideDetailDiv() {\n";
	echo "	document.getElementById(\"DetailDisplayDiv\").innerHTML=\"\";\n";
	echo "	document.getElementById(\"DetailDisplayDiv\").style.display=\"none\";\n";
	echo "}\n";
	echo "function ShowCallDetail(e, call_id, color) \n";
	echo "	{\n";
	echo "	document.getElementById(\"DetailDisplayDiv\").innerHTML=\"\";\n";
	echo "	if (IE) { // grab the x-y pos.s if browser is IE\n";
	echo "		tempX = event.clientX + document.body.scrollLeft+250\n";
	echo "		tempY = event.clientY + document.body.scrollTop\n";
	echo "	} else {  // grab the x-y pos.s if browser is NS\n";
	echo "		tempX = e.pageX\n";
	echo "		tempY = e.pageY\n";
	echo "	}  \n";
	echo "	// catch possible negative values in NS4\n";
	echo "	if (tempX < 0){tempX = 0}\n";
	echo "	if (tempY < 0){tempY = 0}  \n";
	echo "	// show the position values in the form named Show\n";
	echo "	// in the text fields named MouseX and MouseY\n";
	echo "	tempX+=10;\n";
	echo "	tempY+=10;\n";
	echo "	document.getElementById(\"DetailDisplayDiv\").style.display=\"block\";\n";
	echo "	document.getElementById(\"DetailDisplayDiv\").style.left = tempX + \"px\";\n";
	echo "	document.getElementById(\"DetailDisplayDiv\").style.top = tempY + \"px\";\n";
	#echo "  alert(tempX + '|' + tempY);\n";
	echo "	var DetailVerbiage = null;\n";
	echo "	var xmlhttp=false;\n";
	echo "	/*@cc_on @*/\n";
	echo "	/*@if (@_jscript_version >= 5)\n";
	echo "	// JScript gives us Conditional compilation, we can cope with old IE versions.\n";
	echo "	// and security blocked creation of the objects.\n";
	echo "	 try {\n";
	echo "	  xmlhttp = new ActiveXObject(\"Msxml2.XMLHTTP\");\n";
	echo "	 } catch (e) {\n";
	echo "	  try {\n";
	echo "	   xmlhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
	echo "	  } catch (E) {\n";
	echo "	   xmlhttp = false;\n";
	echo "	  }\n";
	echo "	 }\n";
	echo "	@end @*/\n";
	echo "	if (!xmlhttp && typeof XMLHttpRequest!='undefined')\n";
	echo "		{\n";
	echo "		xmlhttp = new XMLHttpRequest();\n";
	echo "		}\n";
	echo "	if (xmlhttp) \n";
	echo "		{ \n";
	echo "		detail_query = \"&search_archived_data=$search_archived_data&stage=\" + call_id + \"&color=\" + color;\n";
	echo "		xmlhttp.open('POST', '$PHP_SELF');\n";
	echo "		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');\n";
	echo "		xmlhttp.send(detail_query); \n";
	echo "		xmlhttp.onreadystatechange = function() \n";
	echo "			{ \n";
	echo "			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) \n";
	echo "				{\n";
	echo "				DetailVerbiage = xmlhttp.responseText;\n";
	echo "				document.getElementById(\"DetailDisplayDiv\").innerHTML=DetailVerbiage;\n";
	echo "				}\n";
	echo "			}\n";
	echo "		delete xmlhttp;\n";
	echo "		}\n";
	echo "	}\n";

	echo "</script>\n";
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\"  id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";
	echo "<span style=\"position:absolute;left:3px;top:30px;z-index:19;\"  id=agent_status_stats>\n";
	echo "<b>"._QXZ("$report_name")."</b> $NWB#sip_event_report$NWE\n";
	echo "<PRE><FONT SIZE=2>";
	}

if (strlen($query_date) < 1)
	{
	echo "";
	echo _QXZ("PLEASE SELECT A DATE AND TIME RANGE BELOW AND CLICK SUBMIT")."\n";
	}

else
	{
	$SIProws_ct=0;
	$stmt="SELECT count(*) from ".$vicidial_log_extended_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB > 1) {echo "$stmt\n";}
	$VLEXrows_to_print = mysqli_num_rows($rslt);
	if ($VLEXrows_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$SIProws_ct =		$row[0];
		}

	if ($file_download < 1)
		{
		$ASCII_text.=_QXZ("SIP Event Report",24).":    "._QXZ("Total Records").": $SIProws_ct    "._QXZ("Starting Record").": $shift                  $NOW_TIME ($db_source)\n";
		$ASCII_text.=_QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}
	else
		{
		$file_output .= _QXZ("SIP Event Report",24).":    "._QXZ("Total Records").": $SIProws_ct    "._QXZ("Starting Record").": $shift                  $NOW_TIME ($db_source)\n";
		$file_output .= _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}

	$ASCII_text_table='';
	$statuses='-';
	$statusesTXT='';
	$statusesHEAD='';
	$statusesHTML='';
	$statusesFILE='';
	$statusesARY[0]='';
	$j=0;
	$dates='-';
	$datesARY[0]='';
	$date_namesARY[0]='';
	$k=0;

	$stmt="SELECT call_date,caller_code,invite_to_ring,ring_to_final,invite_to_final,last_event_code from ".$vicidial_log_extended_sip_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' order by $group limit $shiftSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$i=0;
	$SIPcall_date=array();
	$SIPcaller_code=array();
	$SIPinvite_to_ring=array();
	$SIPring_to_final=array();
	$SIPinvite_to_final=array();
	$SIPlast_event_code=array();
	while ($i < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$SIPcall_date[$i] =			$row[0];
		$SIPcaller_code[$i] =		$row[1];
		$SIPinvite_to_ring[$i] =	$row[2];
		$SIPring_to_final[$i] =		$row[3];
		$SIPinvite_to_final[$i] =	$row[4];
		$SIPlast_event_code[$i] =	$row[5];

		$i++;
		}


	### loop through every call, pick up details from other tables and put in display variable to be printed
	$i=0;
	$SIPuniqueid=array();
	$SIPserver_ip=array();
	$SIPlead_id=array();
	$SIPlength_in_sec=array();
	$SIPstatus=array();
	$SIPphone_number=array();
	$SIPcalled_count=array();
	$SIPuser=array();
	while ($i < $rows_to_print)
		{
		$stmt="SELECT uniqueid,server_ip,lead_id from ".$vicidial_log_extended_table." where caller_code = '$SIPcaller_code[$i]' order by call_date desc limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 1) {echo "$stmt\n";}
		$VLErows_to_print = mysqli_num_rows($rslt);
		if ($VLErows_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$SIPuniqueid[$i] =		$row[0];
			$SIPserver_ip[$i] =		$row[1];
			$SIPlead_id[$i] =		$row[2];
			}

		$stmt="SELECT length_in_sec,status,phone_number,called_count,user from ".$vicidial_log_table." where lead_id = '$SIPlead_id[$i]' and uniqueid='$SIPuniqueid[$i]' order by call_date desc limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 1) {echo "$stmt\n";}
		$VLrows_to_print = mysqli_num_rows($rslt);
		if ($VLrows_to_print > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$SIPlength_in_sec[$i] =	$row[0];
			$SIPstatus[$i] =		$row[1];
			$SIPphone_number[$i] =	$row[2];
			$SIPcalled_count[$i] =	$row[3];
			$SIPuser[$i] =			$row[4];
			}


		$ASCII_text_table.="| ".sprintf("%-27s", $SIPcall_date[$i]);
		$ASCII_text_table.=" | <span onClick=\"ShowCallDetail(event,'$SIPcaller_code[$i]','$SSframe_background')\"><font color=blue><u>".sprintf("%-20s", $SIPcaller_code[$i])."</u></font></span>";
		$ASCII_text_table.=" | ".sprintf("%12s", $SIPinvite_to_ring[$i]);
		$ASCII_text_table.=" | ".sprintf("%12s", $SIPring_to_final[$i]);
		$ASCII_text_table.=" | ".sprintf("%12s", $SIPinvite_to_final[$i]);
		$ASCII_text_table.=" | ".sprintf("%20s", $SIPuniqueid[$i]);
		$ASCII_text_table.=" | ".sprintf("%15s", $SIPserver_ip[$i]);
		$ASCII_text_table.=" | <a href=\"admin_modify_lead.php?lead_id=$SIPlead_id[$i]$lead_archive_link&CIDdisplay=Yes\" target=\"_blank\">".sprintf("%9s", $SIPlead_id[$i])."</a>";
		$ASCII_text_table.=" | ".sprintf("%5s", $SIPlength_in_sec[$i]);
		$ASCII_text_table.=" | ".sprintf("%10s", $SIPphone_number[$i]);
		$ASCII_text_table.=" | ".sprintf("%6s", $SIPstatus[$i]);
		$ASCII_text_table.=" | ".sprintf("%6s", $SIPcalled_count[$i]);
		$ASCII_text_table.=" |\n";

		$i++;
		}

	$ASCII_text.=_QXZ("Displaying Records").": $i                                       ";

	if ($shift > 999)
		{
		$ASCII_text.="<a href=\"$LINKbase&shift=$prev_shift\">"._QXZ("PREV")."</a>   ";
		}
	if ($i > 999)
		{
		$ASCII_text.="<a href=\"$LINKbase&shift=$next_shift\">"._QXZ("NEXT")."</a>   ";
		}
	$ASCII_text.="\n";
	$ASCII_text.="+-----------------------------+----------------------+--------------+--------------+--------------+----------------------+-----------------+-----------+-------+------------+--------+--------+\n";
	$ASCII_text.="| "._QXZ("Date Time Microseconds",27);
	$ASCII_text.=" | "._QXZ("Caller Code",20);
	$ASCII_text.=" | "._QXZ("Post-Dial",12);
	$ASCII_text.=" | "._QXZ("Ring-Time",12);
	$ASCII_text.=" | "._QXZ("Total-Dial",12);
	$ASCII_text.=" | "._QXZ("UniqueID",20);
	$ASCII_text.=" | "._QXZ("Server IP",15);
	$ASCII_text.=" | "._QXZ("Lead ID",9);
	$ASCII_text.=" | "._QXZ("sec",5);
	$ASCII_text.=" | "._QXZ("Phone",10);
	$ASCII_text.=" | "._QXZ("Status",6);
	$ASCII_text.=" |"._QXZ("Call Ct",8);
	$ASCII_text.="|\n";
	$ASCII_text.="+-----------------------------+----------------------+--------------+--------------+--------------+----------------------+-----------------+-----------+-------+------------+--------+--------+\n";
	$ASCII_text.="$ASCII_text_table";
	$ASCII_text.="+----------------------------------------------------+--------------+--------------+--------------+----------------------+-----------------+-----------+-------+------------+--------+--------+\n";
	$ASCII_text.="|                                                    |              |              |              |\n";

	}


if ($file_download > 0)
	{
	$US='_';
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "SIP_EVENT_$US$FILE_TIME.csv";

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$file_output";

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

	exit;
	}

$JS_onload.="}\n";
if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
$JS_text.="</script>\n";

echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>";
echo "<TABLE CELLSPACING=3 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Date").":<BR>";
echo "<INPUT TYPE=hidden NAME=DB VALUE=\"$DB\">\n";
echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

?>
<script language="JavaScript">
function openNewWindow(url)
  {
  window.open (url,"",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');
  }
var o_cal = new tcal ({
	// form name
	'formname': 'vicidial_report',
	// input name
	'controlname': 'query_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Monday week start
</script>
<?php

echo "<BR><BR><INPUT TYPE=TEXT NAME=query_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_D\">";
echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

echo "</TD><TD VALIGN=TOP>";
#echo _QXZ("Display as").":&nbsp;&nbsp;&nbsp;<BR>";
#echo "<select name='report_display_type'>";
#if ($report_display_type) {echo "<option value='$report_display_type' selected>$report_display_type</option>";}
#echo "<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
echo "</TD><TD VALIGN=TOP>"._QXZ("Sort").":<BR>";
echo "<select name='group'>";
echo "<option "; if ($group == 'call_date desc') {echo "SELECTED ";} echo "value='call_date desc'>"._QXZ("Date-Time UP")."</option>";
echo "<option "; if ($group == 'call_date asc') {echo "SELECTED ";} echo "value='call_date asc'>"._QXZ("Date-Time DOWN")."</option>";
echo "<option "; if ($group == 'invite_to_final desc') {echo "SELECTED ";} echo "value='invite_to_final desc'>"._QXZ("Total-Dial UP")."</option>";
echo "<option "; if ($group == 'invite_to_final asc') {echo "SELECTED ";} echo "value='invite_to_final asc'>"._QXZ("Total-Dial DOWN")."</option>";
echo "<option "; if ($group == 'invite_to_ring desc') {echo "SELECTED ";} echo "value='invite_to_ring desc'>"._QXZ("Post-Dial UP")."</option>";
echo "<option "; if ($group == 'invite_to_ring asc') {echo "SELECTED ";} echo "value='invite_to_ring asc'>"._QXZ("Post-Dial DOWN")."</option>";
echo "<option "; if ($group == 'ring_to_final desc') {echo "SELECTED ";} echo "value='ring_to_final desc'>"._QXZ("Ring-Time UP")."</option>";
echo "<option "; if ($group == 'ring_to_final asc') {echo "SELECTED ";} echo "value='ring_to_final asc'>"._QXZ("Ring-Time DOWN")."</option>";
echo "\n<BR><BR>";
echo "</TD><TD VALIGN=TOP>\n";

if ($archives_available=="Y") 
	{
	echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

echo "<BR><BR><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
echo "<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</TD></TR></TABLE>";

echo "</FORM>";

if ($report_display_type=="HTML")
	{
	echo $GRAPH_text;
	echo $JS_text;
	}
else
	{
	echo $ASCII_text;
	}


echo "</span>\n";

if ($report_display_type=="TEXT" || !$report_display_type) 
	{
	echo "<span style=\"position:absolute;left:3px;top:3px;z-index:18;\"  id=agent_status_bars>\n";
	echo "<PRE><FONT SIZE=2>\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n";

	$m=0;
	$sort_order=array();
	while ($m < $k)
		{
		$sort_split = explode("-----",$TOPsort[$m]);
		$i = $sort_split[1];
		$sort_order[$m] = "$i";

		if ( ($TOPsortTALLY[$i] < 1) or ($TOPsortMAX < 1) )
			{echo "              \n";}
		else
			{
			echo "              <SPAN class=\"yellow\">";
			$TOPsortPLOT = ( MathZDC($TOPsortTALLY[$i], $TOPsortMAX) * 120 );
			$h=0;
			while ($h <= $TOPsortPLOT)
				{
				echo " ";
				$h++;
				}
			echo "</SPAN>\n";
			}
		$m++;
		}

	echo "</span>\n";
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
