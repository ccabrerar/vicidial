<?php
# manager_chat_interface.php
# 
# Copyright (C) 2022  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This page is for managers (level 8 or higher) to chat with live agents
#
# changes:
# 150608-2041 - First Build
# 150909-0118 - Bug fixes, added feature to submit messages when Enter is pressed
# 151219-0718 - Added vicidial_chat.js code, translation code where missing
# 160107-2241 - Added realtime check to see whether sub chats are still running
# 160108-2300 - Changed some mysqli_query to mysql_to_mysqli for consistency
# 161029-2127 - Fixed menu displays, text sizes
# 161217-0819 - Added chat-type to allow for multi-user internal chat sessions
# 170409-1551 - Added IP List validation code
# 180508-2215 - Added new help display
# 210114-1338 - Fixed user group permission bug, Issue #1240
# 220223-0933 - Added allow_web_debug system setting
#

$admin_version = '2.14-11';
$build = '220223-0933';

$sh="managerchats"; 

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))									{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))						{$DB=$_POST["DB"];}
if (isset($_GET["action"]))								{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))					{$action=$_POST["action"];}
if (isset($_GET["SUBMIT"]))								{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))					{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["manager_chat_id"]))					{$manager_chat_id=$_GET["manager_chat_id"];}
	elseif (isset($_POST["manager_chat_id"]))			{$manager_chat_id=$_POST["manager_chat_id"];}
if (isset($_GET["available_chat_agents"]))				{$available_chat_agents=$_GET["available_chat_agents"];}
	elseif (isset($_POST["available_chat_agents"]))		{$available_chat_agents=$_POST["available_chat_agents"];}
if (isset($_GET["available_chat_groups"]))				{$available_chat_groups=$_GET["available_chat_groups"];}
	elseif (isset($_POST["available_chat_groups"]))		{$available_chat_groups=$_POST["available_chat_groups"];}
if (isset($_GET["available_chat_campaigns"]))			{$available_chat_campaigns=$_GET["available_chat_campaigns"];}
	elseif (isset($_POST["available_chat_campaigns"]))	{$available_chat_campaigns=$_POST["available_chat_campaigns"];}
if (isset($_GET["manager_message"]))					{$manager_message=$_GET["manager_message"];}
	elseif (isset($_POST["manager_message"]))			{$manager_message=$_POST["manager_message"];}
if (isset($_GET["allow_replies"]))						{$allow_replies=$_GET["allow_replies"];}
	elseif (isset($_POST["allow_replies"]))				{$allow_replies=$_POST["allow_replies"];}
if (isset($_GET["end_all_chats"]))						{$end_all_chats=$_GET["end_all_chats"];}
	elseif (isset($_POST["end_all_chats"]))				{$end_all_chats=$_POST["end_all_chats"];}
if (isset($_GET["submit_chat"]))						{$submit_chat=$_GET["submit_chat"];}
	elseif (isset($_POST["submit_chat"]))				{$submit_chat=$_POST["submit_chat"];}
if (!$allow_replies) {$allow_replies="N";}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$VUselected_language = '';
$stmt = "SELECT use_non_latin,enable_queuemetrics_logging,enable_vtiger_integration,qc_features_active,outbound_autodial_active,sounds_central_control_active,enable_second_webform,user_territories_active,custom_fields_enabled,admin_web_directory,webphone_url,first_login_trigger,hosted_settings,default_phone_registration_password,default_phone_login_password,default_server_password,test_campaign_calls,active_voicemail_server,voicemail_timezones,default_voicemail_timezone,default_local_gmt,campaign_cid_areacodes_enabled,pllb_grouping_limit,did_ra_extensions_enabled,expanded_list_stats,contacts_enabled,alt_log_server_ip,alt_log_dbname,alt_log_login,alt_log_pass,tables_use_alt_log_db,allow_emails,allow_emails,level_8_disable_add,allow_chats,enable_languages,language_method,default_language,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =							$row[0];
	$SSenable_queuemetrics_logging =		$row[1];
	$SSenable_vtiger_integration =			$row[2];
	$SSqc_features_active =					$row[3];
	$SSoutbound_autodial_active =			$row[4];
	$SSsounds_central_control_active =		$row[5];
	$SSenable_second_webform =				$row[6];
	$SSuser_territories_active =			$row[7];
	$SScustom_fields_enabled =				$row[8];
	$SSadmin_web_directory =				$row[9];
	$SSwebphone_url =						$row[10];
	$SSfirst_login_trigger =				$row[11];
	$SShosted_settings =					$row[12];
	$SSdefault_phone_registration_password =$row[13];
	$SSdefault_phone_login_password =		$row[14];
	$SSdefault_server_password =			$row[15];
	$SStest_campaign_calls =				$row[16];
	$SSactive_voicemail_server =			$row[17];
	$SSvoicemail_timezones =				$row[18];
	$SSdefault_voicemail_timezone =			$row[19];
	$SSdefault_local_gmt =					$row[20];
	$SScampaign_cid_areacodes_enabled =		$row[21];
	$SSpllb_grouping_limit =				$row[22];
	$SSdid_ra_extensions_enabled =			$row[23];
	$SSexpanded_list_stats =				$row[24];
	$SScontacts_enabled =					$row[25];
	$SSalt_log_server_ip =					$row[26];
	$SSalt_log_dbname =						$row[27];
	$SSalt_log_login =						$row[28];
	$SSalt_log_pass =						$row[29];
	$SStables_use_alt_log_db =				$row[30];
	$SSallow_emails =						$row[31];
	$SSemail_enabled =						$row[32];
	$SSlevel_8_disable_add =				$row[33];
	$SSallow_chats =						$row[34];
    $SSenable_languages =					$row[35];
    $SSlanguage_method =					$row[36];
	$SSdefault_language =					$row[37];
	$SSallow_web_debug =					$row[38];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
$VUselected_language = $SSdefault_language;
##### END SETTINGS LOOKUP #####
###########################################

$list_id = preg_replace("/[^0-9]/","",$list_id);
$action = preg_replace("/[^-_0-9a-zA-Z]/", "",$action);
$SUBMIT = preg_replace("/[^-_0-9a-zA-Z]/", "",$SUBMIT);
$manager_chat_id = preg_replace("/[^-_0-9a-zA-Z]/", "",$manager_chat_id);
$allow_replies = preg_replace("/[^-_0-9a-zA-Z]/", "",$allow_replies);
$end_all_chats = preg_replace("/[^-_0-9a-zA-Z]/", "",$end_all_chats);
$submit_chat = preg_replace("/[^- \.\_0-9a-zA-Z]/", "",$submit_chat);

### Variables filtered further down in the code
# $available_chat_agents
# $available_chat_groups
# $available_chat_campaigns
# $manager_message

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace("/[^-_0-9a-zA-Z]/", "",$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace("/[^-_0-9a-zA-Z]/", "",$PHP_AUTH_PW);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$user = $PHP_AUTH_USER;
$add_copy_disabled=0;

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

$user_stmt="select full_name,user_level,selected_language,qc_enabled,user_group from vicidial_users where user='$PHP_AUTH_USER'";
$user_level=0;
$user_rslt=mysql_to_mysqli($user_stmt, $link);
if (mysqli_num_rows($user_rslt)>0) 
	{
	$user_row=mysqli_fetch_row($user_rslt);
	$full_name =			$user_row[0];
	$user_level =			$user_row[1];
	$VUselected_language =	$user_row[2];
	$qc_auth =				$user_row[3];
	$LOGuser_group =		$user_row[4];
	}
if ($SSallow_chats < 1)
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("Error, chat disabled on this system");
	exit;
	}


if ($end_all_chats) 
	{
	$archive_stmt="insert ignore into vicidial_manager_chat_log_archive select * from vicidial_manager_chat_log where manager_chat_id='$end_all_chats'";
	$archive_rslt=mysql_to_mysqli($archive_stmt, $link);

	$archive_stmt="insert ignore into vicidial_manager_chats_archive select * from vicidial_manager_chats where manager_chat_id='$end_all_chats'";
	$archive_rslt=mysql_to_mysqli($archive_stmt, $link);

	$delete_stmt="delete from vicidial_manager_chat_log where manager_chat_id='$end_all_chats'";
	$delete_rslt=mysql_to_mysqli($delete_stmt, $link);

	$archive_stmt="delete from vicidial_manager_chats where manager_chat_id='$end_all_chats'";
	$archive_rslt=mysql_to_mysqli($archive_stmt, $link);
	}

if ($submit_chat == _QXZ("ALL LIVE AGENTS")) 
	{
	$chat_agents_SQL_OR="";
	$chat_groups_SQL_OR="";
	$chat_campaigns_SQL_OR="";
	$available_chat_agents_string='|';
	$available_chat_groups_string='|';
	$available_chat_campaigns_string='|';
	} 
else if ($submit_chat) 
	{
	$i=0;
	$available_chat_agents_string='|';
	$available_chat_agents_ct = count($available_chat_agents);
	$chat_agents_SQL_OR="vicidial_live_agents.user in ('',";
	while($i < $available_chat_agents_ct)
		{
		$available_chat_agents[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$available_chat_agents[$i]);
		$available_chat_agents_string .= "$available_chat_agents[$i]|";
		$chat_agents_SQL_OR.="'$available_chat_agents[$i]',";
		$i++;
		}
	$chat_agents_SQL_OR=substr($chat_agents_SQL_OR, 0, -1);
	$chat_agents_SQL_OR.=") OR ";

	$i=0;
	$available_chat_groups_string='|';
	$available_chat_groups_ct = count($available_chat_groups);
	$chat_groups_SQL_OR="vicidial_users.user_group in ('',";
	while($i < $available_chat_groups_ct)
		{
		$available_chat_groups[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$available_chat_groups[$i]);
		$available_chat_groups_string .= "$available_chat_groups[$i]|";
		$chat_groups_SQL_OR.="'$available_chat_groups[$i]',";
		$i++;
		}
	$chat_groups_SQL_OR=preg_replace("/,$/", "", $chat_groups_SQL_OR);
	$chat_groups_SQL_OR.=") OR ";

	$i=0;
	$available_chat_campaigns_string='|';
	$available_chat_campaigns_ct = count($available_chat_campaigns);
	$chat_campaigns_SQL_OR="vicidial_live_agents.campaign_id in ('',";
	while($i < $available_chat_campaigns_ct)
		{
		$available_chat_campaigns[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$available_chat_campaigns[$i]);
		$available_chat_campaigns_string .= "$available_chat_campaigns[$i]|";
		$chat_campaigns_SQL_OR.="'$available_chat_campaigns[$i]',";
		$i++;
		}
	$chat_campaigns_SQL_OR=preg_replace("/,$/", "", $chat_campaigns_SQL_OR);
	$chat_campaigns_SQL_OR.=") ";
	}
if (strlen($chat_agents_SQL_OR.$chat_groups_SQL_OR.$chat_campaigns_SQL_OR)>0) 
	{
	$chat_query_SQL=" and (".$chat_agents_SQL_OR.$chat_groups_SQL_OR.$chat_campaigns_SQL_OR.")";
	}
else 
	{
	$chat_query_SQL="";
	}

$check_stmt="select manager_chat_id from vicidial_manager_chats where manager='$PHP_AUTH_USER' limit 1";
$check_rslt=mysql_to_mysqli($check_stmt, $link);
$active_chats=mysqli_num_rows($check_rslt);
if ($active_chats>0) 
	{
	$active_row=mysqli_fetch_row($check_rslt);
	$manager_chat_id=$active_row[0];
	}

if ($active_chats<1 && $manager_message && ($submit_chat== _QXZ("ALL LIVE AGENTS") || ($submit_chat== _QXZ("SELECTED AGENTS") && ($available_chat_agents_ct+$available_chat_groups_ct+$available_chat_campaigns_ct)>0))) 
	{
	$stmt="select vicidial_live_agents.user, vicidial_users.full_name from vicidial_live_agents, vicidial_users where vicidial_live_agents.user=vicidial_users.user $chat_query_SQL and vicidial_users.user!='$PHP_AUTH_USER' order by vicidial_users.full_name asc";
	$rslt=mysql_to_mysqli($stmt, $link);
	if (mysqli_num_rows($rslt)>0) 
		{
		$ins_stmt="insert into vicidial_manager_chats(chat_start_date, manager, selected_agents, selected_user_groups, selected_campaigns, allow_replies, internal_chat_type) VALUES(now(), '$PHP_AUTH_USER', '$available_chat_agents_string', '$available_chat_groups_string', '$available_chat_campaigns_string', '$allow_replies', 'MANAGER')";
		$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
		$manager_chat_id=mysqli_insert_id($link);

		$subid=1;
		while($row=mysqli_fetch_row($rslt)) 
			{
			$user=$row[0];

			#$manager_message = preg_replace('/"/i','',$manager_message);
			#$manager_message = preg_replace("/'/i",'',$manager_message);
			# $manager_message = preg_replace('/;/i','',$manager_message);
			# $manager_message = preg_replace("/\\\\/i",' ',$manager_message);

			$manager_message = preg_replace("/\r/i",'',$manager_message);
			$manager_message = preg_replace("/\n/i",' ',$manager_message);
			# $manager_message=addslashes(trim("$manager_message"));
			
			$message_id=date("U").".".rand(10000000,99999999);

			$ins_chat_stmt="insert into vicidial_manager_chat_log(manager_chat_id, manager_chat_subid, manager, user, message, message_id, message_date, message_posted_by) VALUES('$manager_chat_id', '$subid', '".$PHP_AUTH_USER."', '$user', '".mysqli_real_escape_string($link, $manager_message)."', '$message_id', now(), '".$PHP_AUTH_USER."')";
			$ins_chat_rslt=mysql_to_mysqli($ins_chat_stmt, $link);
			$subid++;
			}
		}
	}

if (($LOGuser_level < 9) and ($SSlevel_8_disable_add > 0))
	{$add_copy_disabled++;}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times,agent_allowed_chat_groups from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo $stmt;}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];
$LOGagent_allowed_chat_groups =	$row[4];
$admin_viewable_groupsALL=0;
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match("/\-\-ALL\-\-/i",$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}
$regexLOGadmin_viewable_groups = " $LOGadmin_viewable_groups ";

$UUgroups_list='';
if ($admin_viewable_groupsALL > 0)
	{$UUgroups_list .= "<option value=\"---ALL---\">"._QXZ("All Admin User Groups")."</option>\n";}
$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
$UUgroups_to_print = mysqli_num_rows($rslt);
$o=0;
while ($UUgroups_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$UUgroups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$o++;
	}

if ( (!preg_match("/\-\-ALL\-GROUPS\-\-/i",$LOGagent_allowed_chat_groups)) and (strlen($LOGagent_allowed_chat_groups) > 3) )
	{
	$rawLOGagent_allowed_chat_groupsSQL = preg_replace("/ -/",'',$LOGagent_allowed_chat_groups);
	$rawLOGagent_allowed_chat_groupsSQL = preg_replace("/ /","','",$rawLOGagent_allowed_chat_groupsSQL);
	$LOGagent_allowed_chat_groupsSQL = "and user_group IN('---ALL---','$rawLOGagent_allowed_chat_groupsSQL')";
	$whereLOGagent_allowed_chat_groupsSQL = "where user_group IN('---ALL---','$rawLOGagent_allowed_chat_groupsSQL')";
	$vuLOGagent_allowed_chat_groupsSQL = "and vu.user_group IN('---ALL---','$rawLOGagent_allowed_chat_groupsSQL')";
	$valLOGagent_allowed_chat_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGagent_allowed_chat_groupsSQL')";
	$vmLOGagent_allowed_chat_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGagent_allowed_chat_groupsSQL')";
	}
else 
	{$agent_allowed_chat_groupsALL=1;}



header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0
?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
</head>

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<script language="JavaScript">

function RefreshChatDisplay(manager_chat_id) {
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
		chat_SQL_query = "reload_chat_span=1";
		xmlhttp.open('POST', 'manager_chat_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatText = null;
				ChatText = xmlhttp.responseText;
				document.getElementById("ManagerChatAvailabilityDisplay").innerHTML=ChatText;
				}
			}
		delete xmlhttp;
		}
	}

function CheckNewMessages(manager_chat_id) {
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
		chat_SQL_query = "action=CheckNewMessages&manager_chat_id="+manager_chat_id;
		// alert(chat_SQL_query);
		xmlhttp.open('POST', 'manager_chat_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatResponseText = null;
				ChatResponseText = xmlhttp.responseText;
				if (ChatResponseText==0)
					{
					CheckEndedChats(manager_chat_id);
					}
				else
					{
					var sub_ids=[];
					var ChatText_array=ChatResponseText.split("\n");
					for (var i=0; i<ChatText_array.length; i++) 
						{
						if (ChatText_array[i].length>0) {sub_ids.push(ChatText_array[i]);}
						}
					// console.log(sub_ids);
					RefreshChatSubIDs(manager_chat_id, sub_ids);
					}
				}
			}
		delete xmlhttp;
		}
	}

function RefreshChatSubIDs(manager_chat_id, sub_ids) {
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
		var sub_id_str="";
		for (var i=0; i<sub_ids.length; i++) 
			{
			sub_id_str+="&chat_sub_ids[]="+sub_ids[i];
			}
		chat_SQL_query = "action=PrintSubChatText&manager_chat_id="+manager_chat_id+sub_id_str;
		// alert(chat_SQL_query);
		xmlhttp.open('POST', 'manager_chat_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatResponseText = null;
				ChatResponseText = xmlhttp.responseText;
				var ChatText_array=ChatResponseText.split("\n");
				for (var j=1; j<ChatText_array.length; j+=2) 
					{
					var manager_chat_span_id=ChatText_array[j-1];
					var ChatText=ChatText_array[j];
					if (document.getElementById(manager_chat_span_id))
						{
						document.getElementById(manager_chat_span_id).innerHTML=ChatText;
						var objDiv = document.getElementById(manager_chat_span_id);
						objDiv.scrollTop = objDiv.scrollHeight;
						}
					}	
				CheckEndedChats(manager_chat_id);
				}
			}
		delete xmlhttp;
		}
	}

function CheckEndedChats(manager_chat_id) {
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
		chat_SQL_query = "action=CheckEndedChats&manager_chat_id="+manager_chat_id;
		// alert(chat_SQL_query);
		xmlhttp.open('POST', 'manager_chat_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatResponseText = null;
				ChatResponseText = xmlhttp.responseText;
				if (ChatResponseText==0)
					{
					return false;
					}
				else
					{
					var ChatText_array=ChatResponseText.split("\n");
					for (var i=0; i<ChatText_array.length; i++) 
						{
						var EndedChatSpanName="manager_chat_message_"+manager_chat_id+"_"+ChatText_array[i];
						if (document.getElementById(EndedChatSpanName))
							{
							document.getElementById(EndedChatSpanName).className="chat_box_ended";
							}
						// Set style of chat span to nothing
						}
					// console.log(sub_ids);
					}
				}
			}
		delete xmlhttp;
		}
}

function PrintSubChatText(manager_chat_id, chat_sub_id) {
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
		var manager_chat_span_id="manager_chat_"+manager_chat_id+"_"+chat_sub_id;
		chat_SQL_query = "action=PrintSubChatText&manager_chat_id="+manager_chat_id+"&chat_sub_id="+chat_sub_id;
		xmlhttp.open('POST', 'manager_chat_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatText = null;
				ChatText = xmlhttp.responseText;
				document.getElementById(manager_chat_span_id).innerHTML=ChatText;
				}
			}
		delete xmlhttp;
		}
	}

function SendChatMessage(manager_chat_id, chat_sub_id, user) {
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
		var chat_message_field_id="manager_chat_message_"+manager_chat_id+"_"+chat_sub_id;
		var chat_message=encodeURIComponent(document.getElementById(chat_message_field_id).value);

		chat_SQL_query = "action=SendChatMessage&manager_chat_id="+manager_chat_id+"&chat_sub_id="+chat_sub_id+"&chat_message="+chat_message+"&user="+user;
		xmlhttp.open('POST', 'manager_chat_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatText = null;
				ChatText = xmlhttp.responseText;
				if (ChatText.length>0 && ChatText.match(/^Error/)) 
					{
					alert(ChatText);
					}
				else 
					{
					document.getElementById(chat_message_field_id).value="";
					}
				}
			}
		delete xmlhttp;
		}
	}

function EndAgentChat(manager_chat_id, chat_sub_id) {
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
		chat_SQL_query = "action=EndAgentChat&manager_chat_id="+manager_chat_id+"&chat_sub_id="+chat_sub_id;
		xmlhttp.open('POST', 'manager_chat_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatText = null;
				ChatText = xmlhttp.responseText;
				if (ChatText=="ALL CHATS CLOSED")
					{
					window.location.assign("manager_chat_interface.php");
					}
				document.getElementById("ManagerChatDisplay").innerHTML=ChatText;
				}
			}
		delete xmlhttp;
		}
	}


</script>
<title><?php echo _QXZ("ADMINISTRATION: Manager Chat Interface"); ?></title>
<?php 

##### BEGIN Set variables to make header show properly #####
# $ADD =					'3';
$hh =					'managerchats';
$sh =					'users';
$LOGast_admin_access =	'1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$admin_color =		'#FFFF99';
$admin_font =		'BLACK';
$admin_color =		'#E6E6E6';
$emails_color =		'#FFFF99';
$emails_font =		'BLACK';
$emails_color =		'#C6C6C6';
$subcamp_color =	'#C6C6C6';
$managerchats_color =	'#E6E6E6';

##### END Set variables to make header show properly #####

require("admin_header.php");

if ($SSallow_chats < 1)
	{
	echo _QXZ("ERROR: Chats are not enabled on this system")."\n";
	exit;
	}


# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

	echo "<span id='ManagerChatDisplay'>";
	if (!$manager_chat_id) { # DO NOT ALLOW NEW CHATS WHILE AN OLD ONE IS OPEN!!!
		$stmt="select vla.user, vu.full_name, vu.user_group, vla.campaign_id, vc.campaign_name, vug.group_name from vicidial_users vu, vicidial_live_agents vla, vicidial_campaigns vc, vicidial_user_groups vug where vla.user=vu.user and vla.campaign_id=vc.campaign_id and vu.user_group=vug.user_group $vuLOGagent_allowed_chat_groupsSQL";
		if ($DB) {echo $stmt;}
		$rslt=mysql_to_mysqli($stmt, $link);
		$user_array=array();
		$user_group_array=array();
		$campaign_id_array=array();
		while ($row=mysqli_fetch_row($rslt)) {
			if (!in_array("$row[0]", $user_array)) {$user_array[$row[0]]="$row[1]";}
			if (!in_array("$row[2]", $user_group_array)) {$user_group_array["$row[2]"]="$row[5]";}
			if (!in_array("$row[3]", $campaign_id_array)) {$campaign_id_array["$row[3]"]="$row[4]";}
		}
		asort($user_array);
		asort($user_group_array);
		asort($campaign_id_array);

		echo "<form action='/vicidial/manager_chat_interface.php' method='GET'>";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><BR>";
		if ($message) {echo "<B>$message</B><BR>";}
		echo "<span id='ManagerChatAvailabilityDisplay'><TABLE width=750 cellspacing=1 cellpadding=1>\n";
		echo "<TR><TD align='left'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("VICIDIAL Manager Chat Interface").":</font></TD><TD align='right'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><a link='#FFFF00' vlink='#FFFF00' href='manager_chat_interface.php'>["._QXZ("RELOAD")."]</a></font></TD></TR>";
		echo "<TR BGCOLOR=BLACK>\n";
		echo "<TD><font size=1 color=white width='50%'>"._QXZ("CURRENT LIVE AGENTS")."</TD>\n";
		echo "<TD><font size=1 color=white width='50%'>"._QXZ("CURRENT LIVE CAMPAIGNS")."</TD></tr>\n";
		echo "<TR BGCOLOR='#E6E6E6'>";
		echo "<TD rowspan=3 valign='top'>";
		echo "<select name='available_chat_agents[]' multiple size='12' style=\"width:350px\">\n";
		if (count($user_array)==0) {echo "<option value=''>---- "._QXZ("NO LIVE AGENTS")." ----</option>";}
#		while (list($user, $full_name) = each($user_array)) {
		foreach($user_array as $user => $full_name) {
			echo "<option value='$user'>$user - $full_name</option>\n";
		}
		echo "</select>\n";
		echo "</TD>";
		echo "<TD valign='top'>";
		echo "<select name='available_chat_campaigns[]' multiple size='5' style=\"width:350px\">\n";
		if (count($campaign_id_array)==0) {echo "<option value=''>---- "._QXZ("NO LIVE CAMPAIGNS")." ----</option>";}
#		while (list($campaign_id, $campaign_name) = each($campaign_id_array)) {
		foreach($campaign_id_array as $campaign_id => $campaign_name) {
			echo "<option value='$campaign_id'>$campaign_id - $campaign_name</option>\n";
		}
		echo "</select>\n";
		echo "</TD>";
		echo "<TR BGCOLOR=BLACK>";
		echo "<TD><font size=1 color=white>"._QXZ("CURRENT LIVE USER GROUPS")."</TD>\n";
		echo "</TR>";
		echo "<TR BGCOLOR='#E6E6E6'>";
		echo "<TD valign='top'>";
		echo "<select name='available_chat_groups[]' multiple size='5' style=\"width:350px\">\n";
		if (count($user_group_array)==0) {echo "<option value=''>---- "._QXZ("NO LIVE USER GROUPS")." ----</option>";}
#		while (list($user_group, $group_name) = each($user_group_array)) {
		foreach($user_group_array as $user_group => $group_name) {
			echo "<option value='$user_group'>$user_group - $group_name</option>\n";
		}
		echo "</select>\n";
		echo "</TD>";
		echo "</TR>";
		echo "</table></span>";

		echo "<TABLE width=750 cellspacing=1 cellpadding=1>\n";
		echo "<TR BGCOLOR=BLACK>\n";
		echo "<TD align='left'><font size=1 color=white>"._QXZ("MESSAGE").":</font></td>";
		echo "<TD align='left'><font size=1 color=white>"._QXZ("SEND TO").":</font></td>";
		echo "</TR>";
		echo "<TR BGCOLOR='#E6E6E6'>\n";
		echo "<TD align='left'>";
		echo "<textarea rows='5' cols='50' name='manager_message' id='manager_message'></textarea><BR><BR>";
		echo "<input type='checkbox' name='allow_replies' id='allow_replies' value='Y' checked><font size='1'>"._QXZ("Allow agent replies")."</font>";
		echo "</TD>";
		echo "<td align='center' valign='middle'>";
		echo "<input style='background-color:#$SSbutton_color' type='submit' name='submit_chat' style='width: 150px' value='"._QXZ("SELECTED AGENTS")."'><BR><BR><BR>";
		echo "<input style='background-color:#$SSbutton_color' type='submit' name='submit_chat' style='width: 150px' value='"._QXZ("ALL LIVE AGENTS")."'>";
		echo "</td>";
		echo "</TR>";
		echo "</table>";
		echo "</form>";
		echo "<BR>";

		$reload_function="setInterval(\"RefreshChatDisplay()\", 30000);\n";
		echo "<script language=\"JavaScript\">\n";
		echo "$reload_function";
		echo "</script>\n";
	} else {
		echo "<TABLE width=750 cellspacing=1 cellpadding=1>";
		echo "<TR BGCOLOR=BLACK>\n";
		echo "<TD align='left' colspan='3'><font FACE=\"ARIAL,HELVETICA\" size=1 color=white><B>"._QXZ("CURRENT CHAT")." #$manager_chat_id</B></font></TD>\n";
		echo "<TD align='right'><font FACE=\"ARIAL,HELVETICA\" size=1><B><a href='manager_chat_interface.php?end_all_chats=$manager_chat_id' style=\"color: rgb(255,255,255)\">["._QXZ("END ALL CHATS")."]</a></font></td>";
		echo "</TR>";
		echo "<TR BGCOLOR=BLACK>\n";
		echo "\t<TD align='left'><font FACE=\"ARIAL,HELVETICA\" size=1 color=white>"._QXZ("Agent")."</font></TD>\n";
		echo "\t<TD align='left' colspan='1'><font FACE=\"ARIAL,HELVETICA\" size=1 color=white>"._QXZ("Transcript")."</font></TD>\n";
		echo "\t<TD align='left' colspan='2'><font FACE=\"ARIAL,HELVETICA\" size=1 color=white>"._QXZ("Message")."</font></TD>\n";
		echo "</TR>";
		$stmt="select vm.message_posted_by, vm.message, vm.message_date, vu.full_name, vm.manager, vm.manager_chat_subid, vm.user from vicidial_manager_chats vmc, vicidial_manager_chat_log vm, vicidial_users vu where vmc.manager_chat_id='$manager_chat_id' and vmc.manager_chat_id=vm.manager_chat_id and vm.user=vu.user order by vm.manager_chat_subid asc, message_date desc";
		$rslt=mysql_to_mysqli($stmt, $link);
		if (mysqli_num_rows($rslt)>0) 
			{
			$prev_chat_subid="";
			$backlog_limit=20;
			$chat_output_header=array();
			$chat_output_text=array();
			$chat_output_footer=array();
			$chat_subids_array="[";
			while($row=mysqli_fetch_row($rslt)) 
				{
				$full_name=$row[3];
				$chat_subid=$row[5]; 
				$chat_subids_array.="'$chat_subid',";

				if ($backlog_limit>0) 
					{
					if ($row[0]==$row[4]) {$fc="#990000";} else {$fc="#000099";}
					$chat_output_text[$chat_subid]="<font color='$fc' FACE=\"ARIAL,HELVETICA\" size='1'>$row[1]</font><BR>".$chat_output_text[$chat_subid]; 
					$backlog_limit--;
					}
				if ($prev_chat_subid!=$chat_subid) 
					{
					$agent_id=$row[6];
					if ($bgcolor=="#E6E6E6") {$bgcolor="#FFFFFF";} else {$bgcolor="#E6E6E6";}
					$chat_output_header[$chat_subid]= "<TR BGCOLOR='$bgcolor'>\n";
					$chat_output_header[$chat_subid].="\t<td width='100' align='left'><font FACE=\"ARIAL,HELVETICA\" size='1'>$full_name</font></td>";
					$chat_output_header[$chat_subid].="\t<td width='325' align='left' valign='top'><div class='scrolling' id='manager_chat_".$manager_chat_id."_".$chat_subid."'>";

					$chat_output_footer[$chat_subid]= "</span></td>";
					# $chat_output_footer[$chat_subid].="\t<td width='75' align='center'><input type='button' style='width: 75px' class='tiny_green_btn' value='SHOW ALL' onClick='ShowFullChat($manager_chat_id, $chat_subid)'><BR><BR><input type='button' style='width: 75px' class='tiny_red_btn' value='HIDE' onClick='HideFullChat($manager_chat_id, $chat_subid)'></td>";
					$chat_output_footer[$chat_subid].="\t<td width='250' align='left' valign='top'><textarea class='chat_box' id='manager_chat_message_".$manager_chat_id."_".$chat_subid."' name='manager_chat_message_".$manager_chat_id."_".$chat_subid."' rows='7' cols='40' onkeypress='if (event.keyCode==13 && !event.shiftKey) {SendChatMessage($manager_chat_id, $chat_subid, \"$agent_id\"); return false;}'></textarea></td>";
					$chat_output_footer[$chat_subid].="\t<td width='75' align='center'><input type='button' style='width: 75px' class='tiny_green_btn' value='"._QXZ("SEND")."' onClick='SendChatMessage($manager_chat_id, $chat_subid, \"$agent_id\")'><BR><BR><input type='button' style='width: 75px' class='tiny_red_btn' value='"._QXZ("END")."' onClick='EndAgentChat($manager_chat_id, $chat_subid)'></td>";
					$chat_output_footer[$chat_subid].="</TR>";

					$backlog_limit=20;
					$prev_chat_subid=$chat_subid;
					}
				}
			$chat_subids_array=preg_replace("/,$/", "", $chat_subids_array);
			$chat_subids_array.="]";
#			while (list($chat_subid, $text) = each($chat_output_header)) 
			foreach($chat_output_header as $chat_subid => $text)
				{
				echo $chat_output_header[$chat_subid];
				echo $chat_output_text[$chat_subid];
				echo $chat_output_footer[$chat_subid];
				}
			# $reload_function="setInterval(\"CheckNewMessages($manager_chat_id, $chat_subids_array)\", 3000);\n";
			$reload_function="setInterval(\"CheckNewMessages($manager_chat_id)\", 500);\n";
			} 
		else 
			{
			$reload_function="setInterval(\"RefreshChatDisplay()\", 30000);\n";
			}
		echo "</table>";
		echo "<script language=\"JavaScript\">\n";
		echo "$reload_function";
		echo "</script>\n";
		}
	echo "</span>";


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").": $RUNtime "._QXZ("seconds")." $ENDtime &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

# End chat - select all exchanges between user and manager and move them to the dustbin

?>
</body>
</html>
