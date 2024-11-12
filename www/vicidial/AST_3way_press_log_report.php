<?php 
# AST_3way_press_log_report.php
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 231228-2204 - First build
# 240108-1556 - Added channel/server display
# 240114-1018 - Added Agent Gone summary stat
# 240801-1130 - Code updates for PHP8 compatibility
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
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["phone_number"]))			{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$MT=array();
$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
$start_screen=0;
if (!is_array($group)) {$group = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;   $start_screen=1;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$report_name = '3-Way Press Log Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
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
	$SSallow_web_debug =			$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_3way_press_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_3way_press_log_table=use_archive_table("vicidial_3way_press_log");
	}
else
	{
	$vicidial_3way_press_log_table="vicidial_3way_press_log";
	}
#############

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$query_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/',"",$end_date);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9a-zA-Z]/','',$group);
	$phone_number = preg_replace('/[^-_0-9a-zA-Z]/', '', $phone_number);
	$user = preg_replace('/[^-_0-9a-zA-Z]/', '', $user);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9\p{L}]/u','',$group);
	$phone_number = preg_replace('/[^-_0-9\p{L}]/u', '', $phone_number);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $phone_number, $file_download|', url='$LOGfull_url', webserver='$webserver_id';";
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

$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$user_groups=array();
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	$i++;
	}
$user_group_SQL = "and user_group IN('".implode("', '", $user_groups)."')";
if (preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) {$user_group_SQL='';}

$stmt="SELECT user,full_name,user_group from vicidial_users where active IN('Y','N') $user_group_SQL order by user;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
$USERusers=array();
$USERfull_names=array();
$USERuser_groups=array();
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$USERusers[$i] =		$row[0];
	$USERfull_names[$i] =	$row[1];
	$USERuser_groups[$i] =	$row[2];
	$i++;
	}


$LINKbase = "$PHP_SELF?query_date=$query_date&end_date=$end_date&phone_number=$phone_number&DB=$DB&user=$user$groupQS&search_archived_data=$search_archived_data";

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
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:21;'></div>";
	echo "<span style=\"position:absolute;left:0px;top:0px;z-index:20;\"  id=admin_header>";

	$short_header=1;

	require("admin_header.php");

	echo "</span>\n";
	echo "<span style=\"position:absolute;left:3px;top:30px;z-index:19;\"  id=agent_status_stats>\n";
	echo "<b>"._QXZ("$report_name")."</b> $NWB#3way_press_log$NWE\n";
	echo "<PRE><FONT SIZE=2>";
	}

if ($start_screen > 0)
	{
	echo "";
	echo _QXZ("PLEASE SELECT A DATE RANGE BELOW AND CLICK SUBMIT")."\n";
	echo " "._QXZ("NOTE: user and phone number are optional")."\n";
	}

else
	{
	if (strlen($time_BEGIN) < 6) {$time_BEGIN = "00:00:00";}
	if (strlen($time_END) < 6) {$time_END = "23:59:59";}

	$query_date_BEGIN = "$query_date $time_BEGIN";   
	$query_date_END = "$end_date $time_END";

	if ($file_download < 1)
		{
		$ASCII_text.=_QXZ("3-Way Press Agent Report",24).": $user                     $NOW_TIME ($db_source)\n";
		$ASCII_text.=_QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}
	else
		{
		$file_output .= _QXZ("3-Way Press Agent Report",24).": $user                     $NOW_TIME ($db_source)\n";
		$file_output .= _QXZ("Time range").": $query_date_BEGIN "._QXZ("to")." $query_date_END\n\n";
		}

	$statuses='-';
	$statusesTXT='';
	$statusesHEAD='';
	$statusesHTML='';
	$statusesFILE='';
	$statusesARY=array();
	$statusesARY[0]='';
	$j=0;
	$dates='-';
	$date=array();
	$calls=array();
	$status=array();
	$datesARY=array();
	$datesARY[0]='';
	$date_namesARY=array();
	$date_namesARY[0]='';
	$k=0;

	$user_SQL='';
	if (strlen($user) > 0) {$user_SQL = "and user='$user'";}
	$phone_number_SQL='';
	if (strlen($phone_number) > 0) {$phone_number_SQL = "and phone_number='$phone_number'";}

	$Rcall_date=array();
	$Ruser=array();
	$Rfull_name=array();
	$Rlead_id=array();
	$Rphone_number=array();
	$Routbound_cid=array();
	$Rchannel=array();
	$Rserver_ip=array();
	$Rresult=array();
	$Rtransfer=array();

	$TOTtotal=0;
	$TOTanswered=0;
	$TOTaccepted=0;
	$TOTdeclined=0;
	$TOTtooslow=0;
	$TOTdefeated=0;
	$TOTtransfer=0;
	$TOTagentgone=0;

	$stmt = "SELECT date_format(call_date, '%Y-%m-%d %H:%i:%s') as date,user,lead_id,phone_number,outbound_cid,result,call_channel,server_ip from ".$vicidial_3way_press_log_table." where call_date <= '$query_date_END' and call_date >= '$query_date_BEGIN' $user_SQL $phone_number_SQL order by call_date desc limit 100000";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$rows_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $rows_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$Rcall_date[$i] =		$row[0];
		$Ruser[$i] =			$row[1];
		$Rfull_name[$i] =		_QXZ("-- NOT FOUND --");
		$Rlead_id[$i] =			$row[2];
		$Rphone_number[$i] =	$row[3];
		$Routbound_cid[$i] =	$row[4];
		$Rresult[$i] =			$row[5];
		$Rchannel[$i] =			$row[6];
		$Rserver_ip[$i] =		$row[7];
		$Rtransfer[$i] =		0;
		$i++;
		}

	$ASCII_text_data='';
	$file_output_data='';
	$i=0;
	while ($i < $rows_to_print)
		{
		$h=0;   $name_found=0;
		while ( ($h < $users_to_print) and ($name_found < 1) )
			{
			if ($Ruser[$i] == $USERusers[$h])
				{$Rfull_name[$i] = $USERfull_names[$h];   $name_found++;}
			$h++;
			}
		if ( ($name_found > 0) or (preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) )
			{
			# print record
			$BD='';   $ED='';
			$TOTtotal++;
			if (preg_match("/Answered/",$Rresult[$i])) {$TOTanswered++;}
			if (preg_match("/Accepted/",$Rresult[$i])) {$TOTaccepted++;}
			if (preg_match("/Declined/",$Rresult[$i])) {$TOTdeclined++;}
			if (preg_match("/Too Slow/",$Rresult[$i])) {$TOTtooslow++;}
			if (preg_match("/Defeated/",$Rresult[$i])) {$TOTdefeated++;}
			if (preg_match("/Agent Gone/",$Rresult[$i])) {$TOTagentgone++;}
			if (preg_match("/Transfer/",$Rresult[$i])) {$TOTtransfer++;   $Rtransfer[$i]++;}
			else
				{$BD='<font color=red>';   $ED='</font>';}
			$file_output_data .= "\"".trim($Rcall_date[$i])."\",\"".trim($Rfull_name[$i])."\",\"".trim($Rlead_id[$i])."\",\"".trim($Rphone_number[$i])."\",\"".trim($Routbound_cid[$i])."\",\"".trim($Rchannel[$i])."\",\"".trim($Rserver_ip[$i])."\",\"".trim($Rtransfer[$i])."\",\"".trim($Rresult[$i])."\"\n";
			$Rcall_date[$i] =		$BD.sprintf("%19s", $Rcall_date[$i]).$ED;
			$Rfull_name[$i] =		"$Ruser[$i] - $Rfull_name[$i]";
			if (mb_strlen($Rfull_name[$i],'utf-8')>40)
							{$Rfull_name[$i] = mb_substr($Rfull_name[$i],0,40,'utf-8') . '...';}
			$Rfull_name[$i] =		$BD.sprintf("%-43s", "$Rfull_name[$i]").$ED;
			$Rlead_id[$i] =			$BD.sprintf("%-9s", $Rlead_id[$i]).$ED;
			$Rphone_number[$i] =	$BD.sprintf("%-10s", $Rphone_number[$i]).$ED;
			$Routbound_cid[$i] =	$BD.sprintf("%-10s", $Routbound_cid[$i]).$ED;
			if (mb_strlen($Rchannel[$i],'utf-8')>30)
							{$Rchannel[$i] = mb_substr($Rchannel[$i],0,27,'utf-8') . '...';}
			$Rchannel[$i] =	$BD.sprintf("%-30s", $Rchannel[$i]).$ED;
			if (mb_strlen($Rserver_ip[$i],'utf-8')>15)
							{$Rserver_ip[$i] = mb_substr($Rserver_ip[$i],0,12,'utf-8') . '...';}
			$Rserver_ip[$i] =	$BD.sprintf("%-15s", $Rserver_ip[$i]).$ED;
			$Rresult[$i] =			$BD.sprintf("%-200s", $Rresult[$i]).$ED;
			$ASCII_text_data.="| $Rcall_date[$i] | $Rfull_name[$i] | $Rlead_id[$i] | $Rphone_number[$i] | $Routbound_cid[$i] | $Rchannel[$i] | $Rserver_ip[$i] | $Rresult[$i]\n";
			}
		$i++;
		}

	if (strlen($ASCII_text_data) > 8)
		{
		$TOTtotal = sprintf("%10s", $TOTtotal);
		$TOTanswered = sprintf("%10s", $TOTanswered);
		$TOTaccepted = sprintf("%10s", $TOTaccepted);
		$TOTdeclined = sprintf("%10s", $TOTdeclined);
		$TOTtooslow = sprintf("%10s", $TOTtooslow);
		$TOTdefeated = sprintf("%10s", $TOTdefeated);
		$TOTtransfer = sprintf("%10s", $TOTtransfer);
		$TOTagentgone = sprintf("%10s", $TOTagentgone);

		$ASCII_text.=""._QXZ("Results Summary").":\n";
		$ASCII_text.="+------------+------------+\n";
		$ASCII_text.="|    "._QXZ("TOTAL",7)." | $TOTtotal |\n";
		$ASCII_text.="| "._QXZ("Answered",10)." | $TOTanswered |\n";
		$ASCII_text.="| "._QXZ("Accepted",10)." | $TOTaccepted |\n";
		$ASCII_text.="| "._QXZ("Declined",10)." | $TOTdeclined |\n";
		$ASCII_text.="| "._QXZ("Too Slow",10)." | $TOTtooslow |\n";
		$ASCII_text.="| "._QXZ("Defeated",10)." | $TOTdefeated |\n";
		$ASCII_text.="| "._QXZ("Agent Gone",10)." | $TOTagentgone |\n";
		$ASCII_text.="| "._QXZ("Transfer",10)." | $TOTtransfer |\n";
		$ASCII_text.="+------------+------------+\n";

		$ASCII_text.="\n"._QXZ("Results Detail").":\n";
		$ASCII_text.="+---------------------+---------------------------------------------+-----------+------------+------------+--------------------------------+-----------------+------------\n";
		$ASCII_text.="| "._QXZ("Date Time",19)." | "._QXZ("User Agent",43)." | "._QXZ("Lead",9)." | "._QXZ("Phone",10)." | "._QXZ("CID",10)." | "._QXZ("Channel",30)." | "._QXZ("Server",15)." | "._QXZ("Results",20)."\n";
		$ASCII_text.="+---------------------+---------------------------------------------+-----------+------------+------------+--------------------------------+-----------------+------------\n";
		$ASCII_text.=$ASCII_text_data;
		$ASCII_text.="+---------------------+---------------------------------------------+-----------+------------+------------+--------------------------------+-----------------+------------\n";

		$file_output .= "\""._QXZ("Results Summary").":\"\n";
		$file_output .= "\""._QXZ("TOTAL")."\",\"".trim($TOTtotal)."\"\n";
		$file_output .= "\""._QXZ("Answered")."\",\"".trim($TOTanswered)."\"\n";
		$file_output .= "\""._QXZ("Accepted")."\",\"".trim($TOTaccepted)."\"\n";
		$file_output .= "\""._QXZ("Declined")."\",\"".trim($TOTdeclined)."\"\n";
		$file_output .= "\""._QXZ("Too Slow")."\",\"".trim($TOTtooslow)."\"\n";
		$file_output .= "\""._QXZ("Defeated")."\",\"".trim($TOTdefeated)."\"\n";
		$file_output .= "\""._QXZ("Agent Gone")."\",\"".trim($TOTagentgone)."\"\n";
		$file_output .= "\""._QXZ("Transfer")."\",\"".trim($TOTtransfer)."\"\n";
		$file_output .= "\n";
		$file_output .= "\""._QXZ("Results Detail").":\"\n";
		$file_output .= "\""._QXZ("Date Time")."\",\""._QXZ("User Agent")."\",\""._QXZ("Lead")."\",\""._QXZ("Phone")."\",\""._QXZ("CID")."\",\""._QXZ("Channel")."\",\""._QXZ("Server")."\",\""._QXZ("Transfer")."\",\""._QXZ("Results")."\"\n";
		$file_output .= $file_output_data;
		}
	else
		{
		$ASCII_text.=_QXZ("No Results Found",29)."\n";
		$file_output .= _QXZ("No Results Found")."\n";
		}




/*

	$TOTcalls = sprintf("%7s", $TOTcalls);
	$CIScountTOT = sprintf("%7s", $CIScountTOT);
	$DNCcountPCT = ( MathZDC($DNCcountTOT, $CIScountTOT) * 100);
	#$DNCcountPCT = round($DNCcountPCT,2);
	$DNCcountPCT = round($DNCcountPCT);
	#$DNCcountPCT = sprintf("%3.2f", $DNCcountPCT);
	$DNCcountPCT = sprintf("%6s", $DNCcountPCT);


	if ($file_download < 1)
		{
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";
		$ASCII_text.="| "._QXZ("TOTALS",10)." | $TOTcalls| $CIScountTOT| $DNCcountPCT%|$SUMstatusesHTML\n";
		$ASCII_text.="+------------+--------+--------+--------+$statusesHEAD\n";

		$ASCII_text.="\n\n</PRE>";
		}
	else
		{
		$file_output .= _QXZ("TOTALS").",".trim($TOTcalls).",".trim($CIScountTOT).",".trim($DNCcountPCT)."%,".trim($SUMstatusesFILE)."\n";
		}
	}
*/

	} // END USER CHECK

if ($file_download > 0)
	{
	$US='_';
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "3WAY_PRESS_$FILE_TIME.csv";

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called 3WAY_PRESS_20090209-121212.txt
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


echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>";
echo "<TABLE CELLSPACING=3 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP> "._QXZ("Dates").":<BR>";
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

echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

?>
<script language="JavaScript">
var o_cal = new tcal ({
	// form name
	'formname': 'vicidial_report',
	// input name
	'controlname': 'end_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Monday week start
</script>
<?php

echo "</TD><TD VALIGN=TOP> "._QXZ("User").":<BR>";
echo "<INPUT TYPE=TEXT SIZE=18 MAXLENGTH=20 NAME=user value=\"$user\">\n";
echo "</TD><TD VALIGN=TOP>"._QXZ("Phone Number").":<BR>";
echo "<INPUT TYPE=TEXT SIZE=12 MAXLENGTH=18 NAME=phone_number value=\"$phone_number\">\n";

if ($archives_available=="Y") 
	{
	echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

echo "<BR><BR><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo "</TD><TD VALIGN=TOP> &nbsp; &nbsp; &nbsp; &nbsp; ";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
echo " <a href=\"$LINKbase&stage=$stage&file_download=1\">"._QXZ("DOWNLOAD")."</a> | \n";
if (strlen($user) > 1)
	{
	echo " <a href=\"./admin.php?ADD=3&user=$user\">"._QXZ("USER")."</a> | \n";
	echo " <a href=\"./user_stats.php?user=$user&begin_date=$query_date&end_date=$end_date\">"._QXZ("USER STATS")."</a> | \n";
	}
else
	{echo " <a href=\"./admin.php?ADD=0A\">"._QXZ("USERS")."</a> | \n";}
echo "<a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
echo "</TD></TR></TABLE>";

echo "</FORM>";

# print the page
echo $ASCII_text;


echo "</span>\n";

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
