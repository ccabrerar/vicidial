<?php 
# settings_compare.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 210306-2052 - First build
# 220227-1955 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='Settings Compare';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["first_id"]))			{$first_id=$_GET["first_id"];}
	elseif (isset($_POST["first_id"]))	{$first_id=$_POST["first_id"];}
if (isset($_GET["second_id"]))			{$second_id=$_GET["second_id"];}
	elseif (isset($_POST["second_id"]))	{$second_id=$_POST["second_id"];}
if (isset($_GET["stage"]))				{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))		{$stage=$_POST["stage"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($server_ip)) {$server_ip = '10.10.10.15';}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,enable_languages,language_method,allow_shared_dial,qc_features_active,allow_web_debug FROM system_settings;";
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
	$SSallow_shared_dial =			$row[6];
	$SSqc_features_active =			$row[7];
	$SSallow_web_debug =			$row[8];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$first_id = preg_replace('/[^-:\+\*\#\.\_0-9\p{L}]/u', '', $first_id);
$second_id = preg_replace('/[^-:\+\*\#\.\_0-9\p{L}]/u', '', $second_id);
$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);
$submit = preg_replace('/[^-_0-9a-zA-Z]/',"",$submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/',"",$SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

$stmt="SELECT user_group,qc_enabled,modify_campaigns,modify_lists,modify_ingroups,modify_inbound_dids,modify_users,modify_usergroups,modify_phones,modify_servers,modify_shifts from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGuser_group =			$row[0];
$qc_auth =					$row[1];
$LOGmodify_campaigns =		$row[2];
$LOGmodify_lists =			$row[3];
$LOGmodify_ingroups =		$row[4];
$LOGmodify_inbound_dids =	$row[5];
$LOGmodify_users =			$row[6];
$LOGmodify_usergroups =		$row[7];
$LOGmodify_phones =			$row[8];
$LOGmodify_servers =		$row[9];
$LOGmodify_shifts =			$row[10];

if ($LOGmodify_campaigns < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for campaign debugging").": |$PHP_AUTH_USER|\n";
	exit;
	}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

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

$admin_viewable_groupsALL=0;
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}


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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url', webserver='$webserver_id';";
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
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}


	.diff table{
	margin          : 1px 1px 1px 1px;
	border-collapse : collapse;
	border-spacing  : 0;
	}

	.diff td{
	vertical-align : top;
	font-family    : monospace;
	font-size      : 9;
	}
	.diff span{
	display:block;
	min-height:1pm;
	margin-top:-1px;
	padding:1px 1px 1px 1px;
	}

	* html .diff span{
	height:1px;
	}

	.diff span:first-child{
	margin-top:1px;
	}

	.diffDeleted span{
	border:1px solid rgb(255,51,0);
	background:rgb(255,173,153);
	}

	.diffInserted span{
	border:1px solid rgb(51,204,51);
	background:rgb(102,255,51);
	}

-->
 </STYLE>

<?php 
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";

echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$short_header=1;

require("admin_header.php");

if ( ($stage == 'empty') or (strlen($stage) < 1) )
	{
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("Settings Compare Utility")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<TABLE CELLPADDING=4 CELLSPACING=4 WIDTH=100%><TR><TD COLSPAN=2 border=0>";
	echo "<FONT SIZE=3 FACE=\"Ariel,Helvetica\"><b>\n";
	echo _QXZ("Settings Compare Utility").$NWB."settings_compare".$NWE."<br><br>\n";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET ID='vicidial_report' NAME='vicidial_report'>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<FONT SIZE=2>"._QXZ("Settings Type").": </FONT>";
	echo "<select size=1 name=stage>";
	if ($LOGmodify_campaigns > 0)		{echo "<option SELECTED value='CAMPAIGNS'>"._QXZ("CAMPAIGNS")."</option>";}
	if ($LOGmodify_lists > 0)			{echo "<option value='LISTS'>"._QXZ("LISTS")."</option>";}
	if ($LOGmodify_ingroups > 0)		{echo "<option value='IN-GROUPS'>"._QXZ("IN-GROUPS")."</option>";}
	if ($LOGmodify_inbound_dids > 0)	{echo "<option value='DIDS'>"._QXZ("DIDS")."</option>";}
	if ($LOGmodify_inbound_dids > 0)	{echo "<option value='CALLMENUS'>"._QXZ("CALLMENUS")."</option>";}
	if ($LOGmodify_users > 0)			{echo "<option value='USERS'>"._QXZ("USERS")."</option>";}
	if ($LOGmodify_usergroups > 0)		{echo "<option value='USER-GROUPS'>"._QXZ("USER-GROUPS")."</option>";}
	if ($LOGmodify_phones > 0)			{echo "<option value='PHONES'>"._QXZ("PHONES")."</option>";}
	if ($LOGmodify_servers > 0)			{echo "<option value='SERVERS'>"._QXZ("SERVERS")."</option>";}
	if ($LOGmodify_shifts > 0)			{echo "<option value='SHIFTS'>"._QXZ("SHIFTS")."</option>";}
	echo "</select>";
	echo " &nbsp; <INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
	echo "</FORM>\n\n";
	echo "</TD></TR>\n\n";
	echo "</TABLE>\n";
	echo "</BODY></HTML>\n";
	exit;
	}
else
	{
	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("Settings Compare Utility")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	echo "<TABLE CELLPADDING=4 CELLSPACING=4 WIDTH=100%><TR><TD COLSPAN=2 border=0>";
	echo "<FONT SIZE=3 FACE=\"Ariel,Helvetica\"><b>\n";
	echo _QXZ("Settings Compare Utility").$NWB."settings_compare".$NWE." &nbsp; &nbsp; &nbsp; &nbsp; $stage &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF\">"._QXZ("RESET")."</a><br><br>\n";

	$first_id_menu='';
	$second_id_menu='';
	$stmt='';
	if ( ($stage == 'CAMPAIGNS') and ($LOGmodify_campaigns > 0) )
		{$stmt="SELECT campaign_id,campaign_name from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";}
	if ( ($stage == 'LISTS') and ($LOGmodify_lists > 0) )
		{$stmt="SELECT list_id,list_name from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";}
	if ( ($stage == 'IN-GROUPS') and ($LOGmodify_ingroups > 0) )
		{$stmt="SELECT group_id,group_name from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";}
	if ( ($stage == 'DIDS') and ($LOGmodify_inbound_dids > 0) )
		{$stmt="SELECT did_pattern,did_description from vicidial_inbound_dids $whereLOGadmin_viewable_groupsSQL order by did_pattern;";}
	if ( ($stage == 'CALLMENUS') and ($LOGmodify_inbound_dids > 0) )
		{$stmt="SELECT menu_id,menu_name from vicidial_call_menu $whereLOGadmin_viewable_groupsSQL order by menu_id;";}
	if ( ($stage == 'USERS') and ($LOGmodify_users > 0) )
		{$stmt="SELECT user,full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user;";}
	if ( ($stage == 'USER-GROUPS') and ($LOGmodify_usergroups > 0) )
		{$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";}
	if ( ($stage == 'PHONES') and ($LOGmodify_phones > 0) )
		{$stmt="SELECT extension,server_ip from phones $whereLOGadmin_viewable_groupsSQL order by extension,server_ip;";}
	if ( ($stage == 'SERVERS') and ($LOGmodify_servers > 0) )
		{$stmt="SELECT server_ip,server_description from servers $whereLOGadmin_viewable_groupsSQL order by server_ip;";}
	if ( ($stage == 'SHIFTS') and ($LOGmodify_shifts > 0) )
		{$stmt="SELECT shift_id,shift_name from vicidial_shifts $whereLOGadmin_viewable_groupsSQL order by shift_id;";}

	if (strlen($stmt) > 10)
		{
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$ids_to_print = mysqli_num_rows($rslt);
		$first_found=0;
		$first_text='';
		$second_found=0;
		$second_text='';
		$i=0;
		while ($ids_to_print > $i)
			{
			$row=mysqli_fetch_row($rslt);
			$first_id_menu .= "<option";
			if ($stage == 'PHONES')
				{
				if ($first_id == "$row[0]-----$row[1]") {$first_id_menu .= " SELECTED";   $first_found++;}
				$first_id_menu .= " value=\"$row[0]-----$row[1]\">$row[0] - $row[1]</option>";
				}
			else
				{
				if ($first_id == $row[0]) {$first_id_menu .= " SELECTED";   $first_found++;}
				$first_id_menu .= " value=\"$row[0]\">$row[0] - $row[1]</option>";
				}

			$second_id_menu .= "<option";
			if ($stage == 'PHONES')
				{
				if ($second_id == "$row[0]-----$row[1]") {$second_id_menu .= " SELECTED";   $second_found++;}
				$second_id_menu .= " value=\"$row[0]-----$row[1]\">$row[0] - $row[1]</option>";
				}
			else
				{
				if ($second_id == $row[0]) {$second_id_menu .= " SELECTED";   $second_found++;}
				$second_id_menu .= " value=\"$row[0]\">$row[0] - $row[1]</option>";
				}


			$i++;
			}
		if ($first_found < 1) {$first_id_menu .= "<option SELECTED value=''>"._QXZ("Select first")." "._QXZ("$stage")." "._QXZ("entry")."</option>";}
		if ($second_found < 1) {$second_id_menu .= "<option SELECTED value=''>"._QXZ("Select second")." "._QXZ("$stage")." "._QXZ("entry")."</option>";}

		echo "<TABLE CELLPADDING=4 CELLSPACING=4 WIDTH=100%><TR><TD COLSPAN=2 border=0>";
		echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET ID='vicidial_report' NAME='vicidial_report'>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<input type=hidden name=stage value=\"$stage\">\n";
		echo "<FONT SIZE=2>"._QXZ("$stage")." 1: </FONT>";
		echo "<select size=1 name=first_id>";
		echo "$first_id_menu";
		echo "</select>";
		echo "<br>\n";
		echo "<FONT SIZE=2>"._QXZ("$stage")." 2: </FONT>";
		echo "<select size=1 name=second_id>";
		echo "$second_id_menu";
		echo "</select> &nbsp; ";
		echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
		echo "</FORM>\n\n";
		echo "</TD></TR>\n\n";
		echo "</TABLE>\n";

		if ( (strlen($first_id) > 0) and (strlen($second_id) > 0) )
			{
			$stmtA='';   $stmtAA='';   $countAA='';   $stmtAAA='';   $countAAA='';   $stmtAAAA='';   $countAAAA='';   $stmtAAAAA='';   $countAAAAA='';
			if ($stage == 'CAMPAIGNS')		
				{
				$stmtA="SELECT * from vicidial_campaigns where campaign_id='$first_id' $LOGallowed_campaignsSQL;";
				$stmtAA="SELECT * from vicidial_campaign_statuses where campaign_id='$first_id' $LOGallowed_campaignsSQL order by status;";
				$countAA='campaign statuses';
				$stmtAAA="SELECT * from vicidial_campaign_hotkeys where campaign_id='$first_id' $LOGallowed_campaignsSQL order by hotkey;";
				$countAAA='campaign hotkeys';
				$stmtAAAA="SELECT * from vicidial_lead_recycle where campaign_id='$first_id' $LOGallowed_campaignsSQL order by status;";
				$countAAAA='campaign lead recycle';
				$stmtAAAAA="SELECT * from vicidial_pause_codes where campaign_id='$first_id' $LOGallowed_campaignsSQL order by pause_code;";
				$countAAAAA='campaign pause codes';
				}
			if ($stage == 'LISTS')			
				{
				$stmtA="SELECT * from vicidial_lists where list_id='$first_id' $LOGallowed_campaignsSQL;";
				$stmtAA="SELECT * from vicidial_lists_fields where list_id='$first_id' order by field_label;";
				$countAA='custom list fields';
				}
			if ($stage == 'IN-GROUPS')		{$stmtA="SELECT * from vicidial_inbound_groups where group_id='$first_id' $LOGadmin_viewable_groupsSQL;";}
			if ($stage == 'DIDS')			{$stmtA="SELECT * from vicidial_inbound_dids where did_pattern='$first_id' $LOGadmin_viewable_groupsSQL;";}
			if ($stage == 'CALLMENUS')		
				{
				$stmtA="SELECT * from vicidial_call_menu where menu_id='$first_id' $LOGadmin_viewable_groupsSQL;";
				$stmtAA="SELECT * from vicidial_call_menu_options where menu_id='$first_id' order by option_value limit 20;";
				$countAA='options';
				}
			if ($stage == 'USERS')			
				{
				$stmtA="SELECT * from vicidial_users where user='$first_id' $LOGadmin_viewable_groupsSQL;";
				$stmtAA="SELECT * from vicidial_campaign_agents where user='$first_id' order by campaign_id limit 1000;";
				$countAA='user campaign settings';
				$stmtAAA="SELECT * from vicidial_inbound_group_agents where user='$first_id' order by group_id limit 1000;";
				$countAAA='user in-group settings';
				}
			if ($stage == 'USER-GROUPS')	{$stmtA="SELECT * from vicidial_user_groups where user_group='$first_id' $LOGadmin_viewable_groupsSQL;";}
			if ($stage == 'PHONES')			
				{
				$first_id_ARY = explode('-----',$first_id);
				$stmtA="SELECT * from phones where extension='$first_id_ARY[0]'and server_ip='$first_id_ARY[1]' $LOGadmin_viewable_groupsSQL;";
				}
			if ($stage == 'SERVERS')		{$stmtA="SELECT * from servers where server_ip='$first_id' $LOGadmin_viewable_groupsSQL;";}
			if ($stage == 'SHIFTS')			{$stmtA="SELECT * from vicidial_shifts where shift_id='$first_id' $LOGadmin_viewable_groupsSQL;";}


			$stmtB='';   $stmtBB='';   $countBB='';   $stmtBBB='';   $countBBB='';   $stmtBBBB='';   $countBBBB='';
			if ($stage == 'CAMPAIGNS')		
				{
				$stmtB="SELECT * from vicidial_campaigns where campaign_id='$second_id' $LOGallowed_campaignsSQL;";
				$stmtBB="SELECT * from vicidial_campaign_statuses where campaign_id='$second_id' $LOGallowed_campaignsSQL order by status;";
				$countBB='campaign statuses';
				$stmtBBB="SELECT * from vicidial_campaign_hotkeys where campaign_id='$second_id' $LOGallowed_campaignsSQL order by hotkey;";
				$countBBB='campaign hotkeys';
				$stmtBBBB="SELECT * from vicidial_lead_recycle where campaign_id='$second_id' $LOGallowed_campaignsSQL order by status;";
				$countBBBB='campaign lead recycle';
				$stmtBBBBB="SELECT * from vicidial_pause_codes where campaign_id='$second_id' $LOGallowed_campaignsSQL order by pause_code;";
				$countBBBBB='campaign pause codes';
				}
			if ($stage == 'LISTS')			
				{
				$stmtB="SELECT * from vicidial_lists where list_id='$second_id' $LOGallowed_campaignsSQL;";
				$stmtBB="SELECT * from vicidial_lists_fields where list_id='$second_id' order by field_label;";
				$countBB='custom list fields';
				}
			if ($stage == 'IN-GROUPS')		{$stmtB="SELECT * from vicidial_inbound_groups where group_id='$second_id' $LOGadmin_viewable_groupsSQL;";}
			if ($stage == 'DIDS')			{$stmtB="SELECT * from vicidial_inbound_dids where did_pattern='$second_id' $LOGadmin_viewable_groupsSQL;";}
			if ($stage == 'CALLMENUS')		
				{
				$stmtB="SELECT * from vicidial_call_menu where menu_id='$second_id' $LOGadmin_viewable_groupsSQL;";
				$stmtBB="SELECT * from vicidial_call_menu_options where menu_id='$second_id' order by option_value limit 20;";
				$countBB='options';
				}
			if ($stage == 'USERS')			
				{
				$stmtB="SELECT * from vicidial_users where user='$second_id' $LOGadmin_viewable_groupsSQL;";
				$stmtBB="SELECT * from vicidial_campaign_agents where user='$second_id' order by campaign_id limit 1000;";
				$countBB='user campaign settings';
				$stmtBBB="SELECT * from vicidial_inbound_group_agents where user='$second_id' order by group_id limit 1000;";
				$countBBB='user in-group settings';
				}
			if ($stage == 'USER-GROUPS')	{$stmtB="SELECT * from vicidial_user_groups where user_group='$second_id' $LOGadmin_viewable_groupsSQL;";}
			if ($stage == 'PHONES')			
				{
				$second_id_ARY = explode('-----',$second_id);
				$stmtB="SELECT * from phones where extension='$second_id_ARY[0]'and server_ip='$second_id_ARY[1]' $LOGadmin_viewable_groupsSQL;";
				}
			if ($stage == 'SERVERS')		{$stmtB="SELECT * from servers where server_ip='$second_id' $LOGadmin_viewable_groupsSQL;";}
			if ($stage == 'SHIFTS')			{$stmtB="SELECT * from vicidial_shifts where shift_id='$second_id' $LOGadmin_viewable_groupsSQL;";}

			if ( (strlen($stmtA) > 10) and (strlen($stmtB) > 10) )
				{
				$rslt=mysql_to_mysqli($stmtA, $link);
				if ($DB) {echo "$stmtA\n";}
				$ids_to_print = mysqli_num_rows($rslt);
				$i=0;
				while ($ids_to_print > $i)
					{
					$row = mysqli_fetch_array($rslt);
					for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
						{
						$field_info = mysqli_fetch_field($rslt);
						$col = "{$field_info->name}";
						if ($col != 'pass_hash')
							{$first_text .= $col . "='" . $row[$col] . "' \n";}
						}
					$i++;
					}

				$rslt=mysql_to_mysqli($stmtB, $link);
				if ($DB) {echo "$stmtB\n";}
				$ids_to_print = mysqli_num_rows($rslt);
				$i=0;
				while ($ids_to_print > $i)
					{
					$row = mysqli_fetch_array($rslt);
					for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
						{
						$field_info = mysqli_fetch_field($rslt);
						$col = "{$field_info->name}";
						if ($col != 'pass_hash')
							{$second_text .= $col . "='" . $row[$col] . "' \n";}
						}
					$i++;
					}

				### list first sub-entries, if defined
				if (strlen($stmtAA) > 10)
					{
					$rslt=mysql_to_mysqli($stmtAA, $link);
					if ($DB) {echo "$stmtAA\n";}
					$ids_to_print = mysqli_num_rows($rslt);
					$first_text .= $countAA . " section start \n";
					$first_text .= $countAA . "='" . $ids_to_print . "' \n";
					$i=0;
					$field_name_ARY=array();
					while ( ($ids_to_print > $i) and ($i < 1000) )
						{
						$row = mysqli_fetch_array($rslt);
						$fields = mysqli_num_fields($rslt);
						if ($i==0)
							{
							for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
								{
								$field_info = mysqli_fetch_field($rslt);
								$field_name_ARY[$j] = "{$field_info->name}";
								}
							}
						$j=0;
						while($fields > $j)
							{
							$col=$field_name_ARY[$j];
							$value=$row[$j];
							if ($col != 'pass_hash')
								{$first_text .= $col . "='" . $value . "' \n";}
							if ($DB) {echo "$i   Fields: $fields|J: $j|col: $col|value: $value\n";}
							$j++;
							}
						$i++;
						}
					}
				if (strlen($stmtBB) > 10)
					{
					$rslt=mysql_to_mysqli($stmtBB, $link);
					if ($DB) {echo "$stmtBB\n";}
					$ids_to_print = mysqli_num_rows($rslt);
					$second_text .= $countBB . " section start \n";
					$second_text .= $countBB . "='" . $ids_to_print . "' \n";
					$i=0;
					$field_name_ARY=array();
					while ( ($ids_to_print > $i) and ($i < 1000) )
						{
						$row = mysqli_fetch_array($rslt);
						$fields = mysqli_num_fields($rslt);
						if ($i==0)
							{
							for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
								{
								$field_info = mysqli_fetch_field($rslt);
								$field_name_ARY[$j] = "{$field_info->name}";
								}
							}
						$j=0;
						while($fields > $j)
							{
							$col=$field_name_ARY[$j];
							$value=$row[$j];
							if ($col != 'pass_hash')
								{$second_text .= $col . "='" . $value . "' \n";}
							if ($DB) {echo "$i   Fields: $fields|J: $j|col: $col|value: $value\n";}
							$j++;
							}
						$i++;
						}
					}

				### list second sub-entries, if defined
				if (strlen($stmtAAA) > 10)
					{
					$rslt=mysql_to_mysqli($stmtAAA, $link);
					if ($DB) {echo "$stmtAAA\n";}
					$ids_to_print = mysqli_num_rows($rslt);
					$first_text .= $countAAA . " section start \n";
					$first_text .= $countAAA . "='" . $ids_to_print . "' \n";
					$i=0;
					$field_name_ARY=array();
					while ( ($ids_to_print > $i) and ($i < 1000) )
						{
						$row = mysqli_fetch_array($rslt);
						$fields = mysqli_num_fields($rslt);
						if ($i==0)
							{
							for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
								{
								$field_info = mysqli_fetch_field($rslt);
								$field_name_ARY[$j] = "{$field_info->name}";
								}
							}
						$j=0;
						while($fields > $j)
							{
							$col=$field_name_ARY[$j];
							$value=$row[$j];
							if ($col != 'pass_hash')
								{$first_text .= $col . "='" . $value . "' \n";}
							if ($DB) {echo "$i   Fields: $fields|J: $j|col: $col|value: $value\n";}
							$j++;
							}
						$i++;
						}
					}
				if (strlen($stmtBBB) > 10)
					{
					$rslt=mysql_to_mysqli($stmtBBB, $link);
					if ($DB) {echo "$stmtBBB\n";}
					$ids_to_print = mysqli_num_rows($rslt);
					$second_text .= $countBBB . " section start \n";
					$second_text .= $countBBB . "='" . $ids_to_print . "' \n";
					$i=0;
					$field_name_ARY=array();
					while ( ($ids_to_print > $i) and ($i < 1000) )
						{
						$row = mysqli_fetch_array($rslt);
						$fields = mysqli_num_fields($rslt);
						if ($i==0)
							{
							for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
								{
								$field_info = mysqli_fetch_field($rslt);
								$field_name_ARY[$j] = "{$field_info->name}";
								}
							}
						$j=0;
						while($fields > $j)
							{
							$col=$field_name_ARY[$j];
							$value=$row[$j];
							if ($col != 'pass_hash')
								{$second_text .= $col . "='" . $value . "' \n";}
							if ($DB) {echo "$i   Fields: $fields|J: $j|col: $col|value: $value\n";}
							$j++;
							}
						$i++;
						}
					}

				### list third sub-entries, if defined
				if (strlen($stmtAAAA) > 10)
					{
					$rslt=mysql_to_mysqli($stmtAAAA, $link);
					if ($DB) {echo "$stmtAAAA\n";}
					$ids_to_print = mysqli_num_rows($rslt);
					$first_text .= $countAAAA . " section start \n";
					$first_text .= $countAAAA . "='" . $ids_to_print . "' \n";
					$i=0;
					$field_name_ARY=array();
					while ( ($ids_to_print > $i) and ($i < 1000) )
						{
						$row = mysqli_fetch_array($rslt);
						$fields = mysqli_num_fields($rslt);
						if ($i==0)
							{
							for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
								{
								$field_info = mysqli_fetch_field($rslt);
								$field_name_ARY[$j] = "{$field_info->name}";
								}
							}
						$j=0;
						while($fields > $j)
							{
							$col=$field_name_ARY[$j];
							$value=$row[$j];
							if ($col != 'pass_hash')
								{$first_text .= $col . "='" . $value . "' \n";}
							if ($DB) {echo "$i   Fields: $fields|J: $j|col: $col|value: $value\n";}
							$j++;
							}
						$i++;
						}
					}
				if (strlen($stmtBBBB) > 10)
					{
					$rslt=mysql_to_mysqli($stmtBBBB, $link);
					if ($DB) {echo "$stmtBBBB\n";}
					$ids_to_print = mysqli_num_rows($rslt);
					$second_text .= $countBBBB . " section start \n";
					$second_text .= $countBBBB . "='" . $ids_to_print . "' \n";
					$i=0;
					$field_name_ARY=array();
					while ( ($ids_to_print > $i) and ($i < 1000) )
						{
						$row = mysqli_fetch_array($rslt);
						$fields = mysqli_num_fields($rslt);
						if ($i==0)
							{
							for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
								{
								$field_info = mysqli_fetch_field($rslt);
								$field_name_ARY[$j] = "{$field_info->name}";
								}
							}
						$j=0;
						while($fields > $j)
							{
							$col=$field_name_ARY[$j];
							$value=$row[$j];
							if ($col != 'pass_hash')
								{$second_text .= $col . "='" . $value . "' \n";}
							if ($DB) {echo "$i   Fields: $fields|J: $j|col: $col|value: $value\n";}
							$j++;
							}
						$i++;
						}
					}

				### list fourth sub-entries, if defined
				if (strlen($stmtAAAAA) > 10)
					{
					$rslt=mysql_to_mysqli($stmtAAAAA, $link);
					if ($DB) {echo "$stmtAAAAA\n";}
					$ids_to_print = mysqli_num_rows($rslt);
					$first_text .= $countAAAAA . " section start \n";
					$first_text .= $countAAAAA . "='" . $ids_to_print . "' \n";
					$i=0;
					$field_name_ARY=array();
					while ( ($ids_to_print > $i) and ($i < 1000) )
						{
						$row = mysqli_fetch_array($rslt);
						$fields = mysqli_num_fields($rslt);
						if ($i==0)
							{
							for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
								{
								$field_info = mysqli_fetch_field($rslt);
								$field_name_ARY[$j] = "{$field_info->name}";
								}
							}
						$j=0;
						while($fields > $j)
							{
							$col=$field_name_ARY[$j];
							$value=$row[$j];
							if ($col != 'pass_hash')
								{$first_text .= $col . "='" . $value . "' \n";}
							if ($DB) {echo "$i   Fields: $fields|J: $j|col: $col|value: $value\n";}
							$j++;
							}
						$i++;
						}
					}
				if (strlen($stmtBBBBB) > 10)
					{
					$rslt=mysql_to_mysqli($stmtBBBBB, $link);
					if ($DB) {echo "$stmtBBBBB\n";}
					$ids_to_print = mysqli_num_rows($rslt);
					$second_text .= $countBBBBB . " section start \n";
					$second_text .= $countBBBBB . "='" . $ids_to_print . "' \n";
					$i=0;
					$field_name_ARY=array();
					while ( ($ids_to_print > $i) and ($i < 1000) )
						{
						$row = mysqli_fetch_array($rslt);
						$fields = mysqli_num_fields($rslt);
						if ($i==0)
							{
							for($j = 0; $j < mysqli_num_fields($rslt); $j++) 
								{
								$field_info = mysqli_fetch_field($rslt);
								$field_name_ARY[$j] = "{$field_info->name}";
								}
							}
						$j=0;
						while($fields > $j)
							{
							$col=$field_name_ARY[$j];
							$value=$row[$j];
							if ($col != 'pass_hash')
								{$second_text .= $col . "='" . $value . "' \n";}
							if ($DB) {echo "$i   Fields: $fields|J: $j|col: $col|value: $value\n";}
							$j++;
							}
						$i++;
						}
					}

				// include the Diff class
				require_once './class.Diff.php';

				echo Diff::toTable(Diff::compare($first_text, $second_text));
				}
			}
		}
	else
		{
		echo _QXZ("Please click RESET to start over").":  <a href=\"$PHP_SELF\">"._QXZ("RESET")."</a><br><br>\n";
		exit;
		}
	echo "</TD></TR>\n\n";
	echo "</TABLE>\n";
	echo "</BODY></HTML>";
	}
exit;
