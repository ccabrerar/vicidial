<?php
# AST_usergroup_login_report.php
#
# This User-Group based report runs some very intensive SQL queries, so it is
# not recommended to run this on long time periods. 
#
# Copyright (C) 2019  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 120526-0803 - First build
# 130414-0145 - Added report logging
# 130610-0957 - Finalized changing of all ereg instances to preg
# 130620-2248 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130627-0742 - Added new phone fields
# 130901-2004 - Changed to mysqli PHP functions
# 140108-0731 - Added webserver and hostname to report logging
# 141113-2339 - Finalized adding QXZ translation to all admin files
# 141230-1413 - Added code for on-the-fly language translations display
# 150516-1316 - Fixed Javascript element problem, Issue #857
# 151229-2014 - Added archive search option
# 160121-2218 - Added report title header, default report format, cleaned up formatting
# 170409-1538 - Added IP List validation code
# 170829-0040 - Added screen color settings
# 180507-2315 - Added new help display
# 191013-0822 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}

$report_name = 'User Group Login Report';
$db_source = 'M';

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,report_default_format FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
if ($archive_tbl) {$agent_log_table="vicidial_agent_log_archive";} else {$agent_log_table="vicidial_agent_log";}
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
	}
##### END SETTINGS LOOKUP #####
###########################################
if (strlen($report_display_type)<2) {$report_display_type = $SSreport_default_format;}

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_user_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_user_log_table=use_archive_table("vicidial_user_log");
	}
else
	{
	$vicidial_user_log_table="vicidial_user_log";
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
$group = preg_replace("/'|\"|\\\\|;/","",$group);

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
$LOGbrowser=preg_replace("/\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/\'|\"|\\\\/","",$LOGhttp_referer);
if (preg_match("/443/i",$LOGserver_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) {$LOGserver_port='';}
else {$LOGserver_port = ":$LOGserver_port";}
$LOGfull_url = "$HTTPprotocol$LOGserver_name$LOGserver_port$LOGrequest_uri";

$day30range=date("Y-m-d", mktime(0,0,0,date("m"),date("d")-30,date("Y")));

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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group[0], $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
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
	$HTML_text.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
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

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}

#######################################
#for ($i=0; $i<count($user_group); $i++) 
#	{
#	if (preg_match('/\-\-ALL\-\-/', $user_group[$i])) {$all_user_groups=1; $user_group="";}
#	}

$stmt="select user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$HTML_text.="$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
$user_groups=array();
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	if ($all_user_groups) {$user_group[$i]=$row[0];}
	$i++;
	}


$MT[0]='';
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($user_group)) {$user_group = array();}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$user_group_SQL .= "'$user_group[$i]',";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	$user_group_SQL = preg_replace('/,$/i', '',$user_group_SQL);
	$user_group_SQL = "where user_group in ($user_group_SQL)";
	#$user_group_SQL = "and vicidial_agent_log.user_group IN($user_group_SQL)";
	}

######################################
if ($DB) {$HTML_text.="$user_group_string|$user_group_ct|$user_groupQS|$i<BR>";}

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

###########################

$HTML_head.="<HTML>\n";
$HTML_head.="<HEAD>\n";
$HTML_head.="<STYLE type=\"text/css\">\n";
$HTML_head.="<!--\n";
$HTML_head.="   .green {color: white; background-color: green}\n";
$HTML_head.="   .red {color: white; background-color: red}\n";
$HTML_head.="   .blue {color: white; background-color: blue}\n";
$HTML_head.="   .purple {color: white; background-color: purple}\n";
$HTML_head.="-->\n";
$HTML_head.=" </STYLE>\n";

$HTML_head.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
$HTML_head.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_head.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$HTML_text.="<script language=\"JavaScript\">\n";
$HTML_text.="function openNewWindow(url)\n";
$HTML_text.="  {\n";
$HTML_text.="  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
$HTML_text.="  }\n";
$HTML_text.="</script>\n";

$HTML_head.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HTML_head.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$HTML_text.="<TABLE CELLPADDING=3 CELLSPACING=0><TR><TD>";
$HTML_text.="<b>"._QXZ("$report_name")."</b> $NWB#usergroup_login$NWE\n";

$HTML_text.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$HTML_text.="<TABLE CELLSPACING=3 BGCOLOR=\"#".$SSframe_background."\"><TR><TD VALIGN=TOP>&nbsp;<BR>";
$HTML_text.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$HTML_text.="<INPUT TYPE=HIDDEN NAME=type VALUE=\"$type\">\n";
$HTML_text.="</TD><TD VALIGN=TOP>"._QXZ("Teams/User Groups").":<BR>";
$HTML_text.="<SELECT SIZE=5 NAME=user_group[] multiple>\n";

if  (preg_match('/\-\-ALL\-\-/',$user_group_string))
	{$HTML_text.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
else
	{$HTML_text.="<option value=\"--ALL--\">-- "._QXZ("ALL USER GROUPS")." --</option>\n";}
$o=0;
while ($user_groups_to_print > $o)
	{
	if  (preg_match("/\|$user_groups[$o]\|/i",$user_group_string)) 
		{$HTML_text.="<option selected value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	else 
		{$HTML_text.="<option value=\"$user_groups[$o]\">$user_groups[$o]</option>\n";}
	$o++;
	}
$HTML_text.="</SELECT>\n";
$HTML_text.="</TD>\n";

$HTML_text.="<TD VALIGN=TOP>\n";
$HTML_text.=_QXZ("Display as:")."<BR>";
$HTML_text.="<select name='report_display_type'>";
if ($report_display_type) {$HTML_text.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$HTML_text.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";

if ($archives_available=="Y") 
	{
	$HTML_text.="<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."\n";
	}

$HTML_text.="</TD><TD VALIGN=MIDDLE ALIGN='CENTER'> &nbsp; &nbsp; &nbsp; &nbsp; ";

$HTML_text.="<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
$HTML_text.="<a href=\"$PHP_SELF?DB=$DB&query_date=$query_date&end_date=$end_date&query_date_D=$query_date_D&query_date_T=$query_date_T&end_date_D=$end_date_D&end_date_T=$end_date_T$groupQS$user_groupQS$call_statusQS&file_download=1&SUBMIT=$SUBMIT&search_archived_data=$search_archived_data\">"._QXZ("DOWNLOAD")."</a> |";
$HTML_text.=" <a href=\"./admin.php?ADD=999999\">"._QXZ("REPORTS")."</a> </FONT>\n";
$HTML_text.="</FONT>\n";
$HTML_text.="<BR><BR><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$HTML_text.="</TD></TR></TABLE>";
$HTML_text.="</FORM>\n";

	if ($file_download < 1)
		{
		$ASCII_text.="<font size=2><PRE>"._QXZ("Usergroup Login Report",24).": $user                     $NOW_TIME ($db_source)\n";
		$GRAPH_text.=_QXZ("Usergroup Login Report Report",24).": $user                     $NOW_TIME ($db_source)\n";
		}
	else
		{
		$CSV_text .= _QXZ("Usergroup Login Report Report",24).": $user                     $NOW_TIME ($db_source)\n";
		}

if ($SUBMIT) 
	{
	$ASCII_text.="+--------------------------------+----------+----------------------+---------------------+---------------------+----------+-----------------+-----------------+----------------------+--------------+-----------------+-----------------+-----------------+\n";
	$ASCII_text.="| "._QXZ("USER NAME",30)." | "._QXZ("ID",8)." | "._QXZ("USER GROUP",20)." | "._QXZ("FIRST LOGIN DATE",19)." | "._QXZ("LAST LOGIN DATE",19)." | "._QXZ("CAMPAIGN",8)." | "._QXZ("SERVER IP",15)." | "._QXZ("COMPUTER IP",15)." | "._QXZ("EXTENSION",20)." | "._QXZ("BROWSER",12)." | "._QXZ("PHONE LOGIN",15)." | "._QXZ("SERVER PHONE",15)." | "._QXZ("PHONE IP",15)." |\n";
	$ASCII_text.="+--------------------------------+----------+----------------------+---------------------+---------------------+----------+-----------------+-----------------+----------------------+--------------+-----------------+-----------------+-----------------+\n";

	$HTML_text2.="<table border='0' cellpadding='3' cellspacing='1'>";
	$HTML_text2.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML_text2.="<th colspan='11'><font size='2'>"._QXZ("Usergroup Login Report Report").": $user</th><th colspan='2'><font size='2'>$NOW_TIME ($db_source)</font></th>";
	$HTML_text2.="</tr>\n";
	$HTML_text2.="<tr bgcolor='#".$SSstd_row1_background."'>";
	$HTML_text2.="<th><font size='2'>"._QXZ("USER NAME")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("ID")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("USER GROUP")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("FIRST LOGIN DATE")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("LAST LOGIN DATE")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("CAMPAIGN")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("SERVER IP")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("COMPUTER IP")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("EXTENSION")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("BROWSER")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("PHONE LOGIN")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("SERVER PHONE")."</font></th>";
	$HTML_text2.="<th><font size='2'>"._QXZ("PHONE IP")."</font></th>";
	$HTML_text2.="</tr>\n";

	$CSV_text="\""._QXZ("User group login report")."\",\""._QXZ("User groups").":\",\""._QXZ("$user_group_string")."\"\n\n";
	$CSV_text.="\""._QXZ("User name")."\",\""._QXZ("User ID")."\",\""._QXZ("User group")."\",\""._QXZ("First login date")."\",\""._QXZ("Last login date")."\",\""._QXZ("Campaign ID")."\",\""._QXZ("Server IP")."\",\""._QXZ("Computer IP")."\",\""._QXZ("Extension")."\",\""._QXZ("Browser")."\",\""._QXZ("Phone login")."\",\""._QXZ("Server phone")."\",\""._QXZ("Phone IP")."\"\n";
	$stmt="select distinct user, substr(full_name,1,30) as fullname, full_name from vicidial_users $user_group_SQL order by user";
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_array($rslt)) 
		{
		$date_stmt="select min(event_date) as min_date, max(event_date) as max_date from ".$vicidial_user_log_table." where user='$row[user]' and event='LOGIN' and event_date>='$day30range'";
		$date_rslt=mysql_to_mysqli($date_stmt, $link);
		$date_row=mysqli_fetch_array($date_rslt);

		$data_stmt="select campaign_id, server_ip, computer_ip, user_group, substring(extension,1,20) as ext, extension, browser, phone_login, server_phone, phone_ip from ".$vicidial_user_log_table." where user='$row[user]' and event_date='$date_row[max_date]' and event='LOGIN'";
		$data_rslt=mysql_to_mysqli($data_stmt, $link);
		while ($data_row=mysqli_fetch_array($data_rslt)) 
			{
			preg_match('/^[^\s]+/', $data_row["browser"], $browser_ary);
			if ($report_display_type=="TEXT") {$browser=$browser_ary[0];} else {$browser=$data_row["browser"];}
			$ASCII_text.="| ".sprintf("%-30s", $row["fullname"])." | <a href='user_stats.php?user=$row[user]'>".sprintf("%-8s", $row["user"])."</a> | ".sprintf("%-20s", $data_row["user_group"])." | ".sprintf("%-19s", $date_row["min_date"])." | ".sprintf("%-19s", $date_row["max_date"])." | ".sprintf("%-8s", $data_row["campaign_id"])." | ".sprintf("%-15s", $data_row["server_ip"])." | ".sprintf("%-15s", $data_row["computer_ip"])." | ".sprintf("%-20s", $data_row["ext"])." | ".sprintf("%-12s", $browser)." | ".sprintf("%-15s", $data_row["phone_login"])." | ".sprintf("%-15s", $data_row["server_phone"])." | ".sprintf("%-15s", $data_row["phone_ip"])." |\n";
			$CSV_text.="\"$row[full_name]\",\"$row[user]\",\"$data_row[user_group]\",\"$date_row[min_date]\",\"$date_row[max_date]\",\"$data_row[campaign_id]\",\"$data_row[server_ip]\",\"$data_row[computer_ip]\",\"$data_row[extension]\",\"$data_row[browser]\",\"$data_row[phone_login]\",\"$data_row[server_phone]\",\"$data_row[phone_ip]\"\n";

			$HTML_text2.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML_text2.="<td><font size='2'>".$row["fullname"]."</font></td>";
			$HTML_text2.="<td><font size='2'><a href='user_stats.php?user=$row[user]'>".$row["user"]."</a></font></td>";
			$HTML_text2.="<td><font size='2'>".$data_row["user_group"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$date_row["min_date"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$date_row["max_date"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$data_row["campaign_id"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$data_row["server_ip"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$data_row["computer_ip"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$data_row["ext"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$browser."</font></td>";
			$HTML_text2.="<td><font size='2'>".$data_row["phone_login"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$data_row["server_phone"]."</font></td>";
			$HTML_text2.="<td><font size='2'>".$data_row["phone_ip"]."</font></td>";
			$HTML_text2.="</tr>\n";
			}
		}
	$ASCII_text.="+--------------------------------+----------+----------------------+---------------------+---------------------+----------+-----------------+-----------------+----------------------+--------------+-----------------+-----------------+-----------------+\n";
	$HTML_text2.="</table>\n";
	}

if ($file_download>0) 
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_usergroup_login_report_$US$FILE_TIME.csv";
	$CSV_text=preg_replace('/\n +,/', ',', $CSV_text);
	$CSV_text=preg_replace('/ +\"/', '"', $CSV_text);
	$CSV_text=preg_replace('/\" +/', '"', $CSV_text);

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');
	// It will be called LIST_101_20090209-121212.txt
	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$CSV_text";
	}
else
	{
	header("Content-type: text/html; charset=utf-8");
#	$JS_onload.="}\n";
#	if ($report_display_type=='HTML') {$JS_text.=$JS_onload;}
#	$JS_text.="</script>\n";

	if ($report_display_type=="HTMLOFF")
		{
		$HTML_text.=$GRAPH_text;
		}
	else if ($report_display_type=="HTML")
		{
		$HTML_text.=$HTML_text2;
		}
	else
		{
		$HTML_text.=$ASCII_text."</PRE></font>";
		}

	echo $HTML_head;
#	echo $JS_text;
	$short_header=1;
	require("admin_header.php");
	echo $HTML_text;
	flush();
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

exit;

?>
