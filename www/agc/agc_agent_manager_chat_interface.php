<?php
# agc_agent_manager_chat_interface.php
# 
# Copyright (C) 2018  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This page is for agents to chat with managers via the agent interface.
#
# changes:
# 141212-2245 - First Build
# 151213-1108 - Added variable filtering
# 151218-1141 - Added missing translation code and user auth, merged js code into file
# 151231-0842 - Added agent_allowed_chat_groups setting
# 160108-2300 - Changed some mysqli_query to mysql_to_mysqli for consistency
# 160523-0630 - Fixed vicidial_stylesheet issues
# 161217-0827 - Added code for multi-user internal chat sessions
# 161221-0801 - Added color-coding for users in internal chat sessions
# 180927-0624 - Fix for missing translationm issue #1125
#

$admin_version = '2.14-9';
$build = '180927-0624';

$sh="managerchats"; 

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["action"]))						{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))			{$action=$_POST["action"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["manager_chat_id"]))			{$manager_chat_id=$_GET["manager_chat_id"];}
	elseif (isset($_POST["manager_chat_id"]))	{$manager_chat_id=$_POST["manager_chat_id"];}
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["pass"]))						{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))				{$pass=$_POST["pass"];}
if (!$user) {echo "Page should only be viewed through the agent interface."; die;}

if ($non_latin < 1)
	{
	$user = preg_replace('/[^-\_0-9a-zA-Z]/','',$user);
	$pass = preg_replace('/[^-\_0-9a-zA-Z]/','',$pass);
	$manager_chat_id = preg_replace('/[^- \_\.0-9a-zA-Z]/','',$user);
	}
else
	{
	$user = preg_replace("/\'|\"|\\\\|;/","",$user);
	$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);
	$manager_chat_id = preg_replace("/\'|\"|\\\\|;/","",$user);
	}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$VUselected_language = '';
$stmt = "SELECT use_non_latin,enable_languages,language_method,default_language,allow_chats FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =			$row[0];
	$SSenable_languages =	$row[1];
	$SSlanguage_method =	$row[2];
	$SSdefault_language =	$row[3];
	$SSallow_chats =		$row[4];
	}
$VUselected_language = $SSdefault_language;
##### END SETTINGS LOOKUP #####
###########################################

$auth=0;
$auth_message = user_authorization($user,$pass,'',0,0,0,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0))
	{
	echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth_message|$sh|\n";
	exit;
	}

$user_stmt="select full_name,user_level,selected_language from vicidial_users where user='$user'";
$user_level=0;
$user_rslt=mysql_to_mysqli($user_stmt, $link);
if (mysqli_num_rows($user_rslt)>0) 
	{
	$user_row=mysqli_fetch_row($user_rslt);
	$full_name =			$user_row[0];
	$user_level =			$user_row[1];
	$VUselected_language =	$user_row[2];
	}
if ($SSallow_chats < 1)
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("Error, chat disabled on this system");
	exit;
	}


# Get a count on unread messages where the user is involved but not the chat manager/initiator in order to create the ChatReloadIDNumber variable
$chat_reload_id_number_array=array();
$unread_stmt="select manager_chat_id, manager_chat_subid, sum(if(message_viewed_date is not null and vicidial_manager_chat_log.user='$user', 0, 1)) as unread_count from vicidial_manager_chat_log where vicidial_manager_chat_log.user='$user' group by manager_chat_id, manager_chat_subid order by manager_chat_id, manager_chat_subid";
$unread_rslt=mysql_to_mysqli($unread_stmt, $link);
while ($unread_row=mysqli_fetch_array($unread_rslt)) {
	$IDNumber=$unread_row["manager_chat_id"]."-".$unread_row["manager_chat_subid"]."-".$unread_row["unread_count"];
	array_push($chat_reload_id_number_array, "$IDNumber");
}

# Pull the most recently posted-to chat that has not been viewed, then the most recent period, and display that as the default window
$stmt="select distinct vicidial_manager_chat_log.manager_chat_id, vicidial_manager_chat_log.manager_chat_subid, vicidial_users.full_name, vicidial_manager_chats.chat_start_date, sum(if(vicidial_manager_chat_log.message_viewed_date is null and vicidial_manager_chat_log.user='$user', 1, 0)),vicidial_manager_chat_log.user from vicidial_manager_chat_log, vicidial_manager_chats, vicidial_users where vicidial_manager_chat_log.user='$user' and vicidial_manager_chat_log.manager_chat_id=vicidial_manager_chats.manager_chat_id and vicidial_manager_chats.manager=vicidial_users.user group by manager_chat_id, manager_chat_subid order by message_viewed_date asc, message_date desc";
$rslt=mysql_to_mysqli($stmt, $link);

$active_chats_array=array();
$chat_subid_array=array();
$unread_chats_array=array();
$chat_managers_array=array();
$chat_start_date_array=array();
$agents_managers_array=array(); // for override
$priority_chat="";
$priority_chat_subid="";
while ($row=mysqli_fetch_row($rslt)) {
	if ($row[0]!="") {
		if (!$priority_chat) {$priority_chat=$row[0];} # The priority_chat is the most recent chat that has not been viewed.
		if (!$priority_chat_subid) {$priority_chat_subid=$row[1];} # The priority_chat is the most recent chat that has not been viewed.
		if (!$agent_manager_override) {$agent_manager_override="0";} # The priority_chat is the most recent chat that has not been viewed.
		array_push($active_chats_array, "$row[0]");
		$chat_subid_array[$row[0]]="$row[1]";
		$chat_managers_array[$row[0]]="$row[2]";
		$chat_start_date_array[$row[0]]="$row[3]";
		if ($row[4]>0) {array_push($unread_chats_array, $row[0]);} # Store any chat with unread messages.
		$agents_managers_array[$row[0]]="0";  // not a chat where the agent is a manager
	}
}

	# Get a count on unread messages where the user is the chat manager/initiator in order to create the ChatReloadIDNumber variable
	$unread_stmt="select manager_chat_id, manager_chat_subid, sum(if(message_viewed_date is not null and vicidial_manager_chat_log.user='$user', 0, 1)) as unread_count from vicidial_manager_chat_log where vicidial_manager_chat_log.manager='$user' group by manager_chat_id, manager_chat_subid order by manager_chat_id, manager_chat_subid";
	$unread_rslt=mysql_to_mysqli($unread_stmt, $link);
	while ($unread_row=mysqli_fetch_array($unread_rslt)) {
		$IDNumber=$unread_row["manager_chat_id"]."-".$unread_row["manager_chat_subid"]."-".$unread_row["unread_count"];
		array_push($chat_reload_id_number_array, "$IDNumber");
	}


	### This was added for agent to agent chats since there needs to be a list of open chats where the agent viewing this is also the manager,
	### which will now happen because agents can now start their own chats.  Added vicidial_manager_chat_log.user to this query on 2/4/15 for
	### manager override
	$stmt="select distinct vicidial_manager_chat_log.manager_chat_id, vicidial_manager_chat_log.manager_chat_subid, vicidial_users.full_name, vicidial_manager_chats.chat_start_date, sum(if(vicidial_manager_chat_log.message_viewed_date is null and vicidial_manager_chat_log.user='$user', 1, 0)),vicidial_manager_chat_log.user from vicidial_manager_chat_log, vicidial_manager_chats, vicidial_users where vicidial_manager_chat_log.manager='$user' and vicidial_manager_chat_log.manager_chat_id=vicidial_manager_chats.manager_chat_id and vicidial_manager_chat_log.user=vicidial_users.user group by manager_chat_id, manager_chat_subid order by message_viewed_date asc, message_date desc";
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) {
		if ($row[0]!="") {
			if (!$priority_chat) {$priority_chat=$row[0];} # The priority_chat is the most recent chat that has not been viewed.
			if (!$priority_chat_subid) {$priority_chat_subid=$row[1];} # The priority_chat is the most recent chat that has not been viewed.
			if (!$agent_manager_override) {$agent_manager_override=$row[5];} # The priority_chat is the most recent chat that has not been viewed.
			array_push($active_chats_array, "$row[0]");
			$chat_subid_array[$row[0]]="$row[1]"; // Added back in on 3/3 - why was this removed?
			$chat_managers_array[$row[0]]="$row[2]";
			$chat_start_date_array[$row[0]]="$row[3]";
			if ($row[4]>0) {array_push($unread_chats_array, $row[0]);} # Store any chat with unread messages.
			$agents_managers_array[$row[0]]="$row[5]";  // IS a chat where the agent is a manager
		}
	}
	#########
asort($active_chats_array);
asort($chat_managers_array);

asort($chat_reload_id_number_array);
$ChatReloadIDNumber="";
while (list($key, $id_number) = each($chat_reload_id_number_array)) {
	$ChatReloadIDNumber.="$id_number.";
}
$ChatReloadIDNumber=substr($ChatReloadIDNumber,0,-1);

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0
echo '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
';
?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
</head>
<link rel="stylesheet" href="css/vicidial_stylesheet.css">
<link rel="stylesheet" href="css/simpletree.css">
<script language="JavaScript">

// ################################################################################
// Show parent alert
	function chat_alert_box(temp_message)
		{
		window.parent.document.getElementById("AlertBoxContent").innerHTML = temp_message;

		parent.showDiv('AlertBox');

		window.parent.document.alert_form.alert_button.focus();
		}

/// Functions for agent/manager chatting
function CreateAgentToAgentChat() {
	var agent_message=encodeURIComponent(document.getElementById("agent_message").value);
	var user=document.getElementById("user").value;
	var pass='<?php echo $pass ?>';
	var agent=document.getElementById("agent").value;

	if (!agent_message || agent_message=="")
		{
			chat_alert_box("<?php echo _QXZ("Please enter a chat message"); ?>");
			return false;
		}
	if (!agent || agent=="")
		{
			chat_alert_box("<?php echo _QXZ("Please select an agent to chat with"); ?>");
			return false;
		}

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
		var chat_SQL_query = "action=CreateAgentToAgentChat&agent_manager="+user+"&pass="+pass+"&agent_user="+agent+"&manager_message="+agent_message+"&user="+user;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatText = null;
				ChatText = xmlhttp.responseText;
				var ChatText_array=ChatText.split("|");

				if (ChatText.match(/^Error/)) 
					{
					chat_alert_box(ChatText);
					}
				else 
					{
					document.getElementById("agent_message").value="";
					document.getElementById("AgentNewChatSpan").style.display='none';
					document.getElementById("AgentChatSpan").style.display='block';
					document.getElementById("AgentEndChatSpan").style.display = 'block';
					document.getElementById("AgentManagerOverride").value=agent;
					document.getElementById("CurrentActiveChat").value=ChatText_array[0];
					document.getElementById("CurrentActiveChatSubID").value=ChatText_array[1];
					}
				}
			}
		delete xmlhttp;
		}
}

// Displays selected chat, also marks any message on it as read.
function DisplayMgrAgentChat(manager_chat_id, manager_chat_subid) {
	if (manager_chat_id)
		{
		document.getElementById("CurrentActiveChat").value=manager_chat_id;
		document.getElementById("CurrentActiveChatSubID").value=manager_chat_subid;
		} 
	else 
		{
		var manager_chat_id=document.getElementById("CurrentActiveChat").value;
		var manager_chat_subid=document.getElementById("CurrentActiveChatSubID").value;
		}

	if (!manager_chat_id || !manager_chat_subid)
		{
		document.getElementById("AllowAgentReplies").style.display = 'none';
		// document.getElementById("ActiveManagerChatTranscript").innerHTML=ChatText_array[1];
		// document.getElementById("ActiveChatStartDate").innerHTML=ChatText_array[2];
		// document.getElementById("ActiveChatManager").innerHTML=ChatText_array[3];
		return false;
		}
	var user=document.getElementById("user").value;
	var pass='<?php echo $pass ?>';
	var agent_override=document.getElementById("AgentManagerOverride").value;

	// JCJ - commented out 10/19 so any agent in a-2-a chat can end it.
	// if (agent_override && agent_override!="0" && agent_override!="")
	//	{
		document.getElementById("AgentEndChatSpan").style.display = 'block';
	//	}
	// else 
	//	{
	//	document.getElementById("AgentEndChatSpan").style.display = 'none';
	//	}

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
		var chat_SQL_query = "action=DisplayMgrAgentChat&user="+user+"&pass="+pass+"&manager_chat_id="+manager_chat_id+"&manager_chat_subid="+manager_chat_subid;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatText = null;
				ChatText = xmlhttp.responseText;
				var ChatText_array=ChatText.split("\n");

				var allow_agent_replies=ChatText_array[0];
				var new_messages=ChatText_array[4];
				var internal_chat_type=ChatText_array[5];

				if (allow_agent_replies=="Y")
					{
					document.getElementById("AllowAgentReplies").style.display = 'block';
					} 
				else 
					{
					document.getElementById("AllowAgentReplies").style.display = 'none';
					}

				if (ChatText_array[1].match(/^CHAT ENDED/)) 
					{
					document.getElementById("AgentAddChatSpan").style.display = 'none';
					document.getElementById("AllLiveNonChatAgents").style.display = 'none';
					}
				else 
					{
						if (internal_chat_type=="AGENT")
							{
							document.getElementById("AgentAddChatSpan").style.display = 'block';
							}
						else 
							{
							document.getElementById("AgentAddChatSpan").style.display = 'none';
							}
					}

				document.getElementById("ActiveManagerChatTranscript").innerHTML=ChatText_array[1];
				document.getElementById("ActiveChatStartDate").innerHTML=ChatText_array[2];
				document.getElementById("ActiveChatManager").innerHTML=ChatText_array[3];
				document.getElementById("ActiveManagerChatTranscript").scrollTop = document.getElementById("ActiveManagerChatTranscript").scrollHeight;
				RefreshActiveChatView();
				}
			}
		delete xmlhttp;
		}
}

// Ends selected displayed chat
function EndAgentToAgentChat() {
	var manager_chat_id=document.getElementById("CurrentActiveChat").value;
	var manager_chat_subid=document.getElementById("CurrentActiveChatSubID").value;
	var user=document.getElementById("user").value;
	var pass='<?php echo $pass ?>';

	if (!manager_chat_id || !manager_chat_subid)
		{
		document.getElementById("AllowAgentReplies").style.display = 'none';
		return false;
		}

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
		var chat_SQL_query = "action=EndAgentToAgentChat&user="+user+"&pass="+pass+"&manager_chat_id="+manager_chat_id+"&manager_chat_subid="+manager_chat_subid;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ChatText = null;
				ChatText = xmlhttp.responseText; // echoes number of lines affected - should be greater than zero.

				if (ChatText>0)
					{
					document.getElementById("AllowAgentReplies").style.display = 'none';
					document.getElementById("AgentEndChatSpan").style.display = 'none';
					document.getElementById("ActiveManagerChatTranscript").innerHTML='';	
					document.getElementById("AgentManagerOverride").value='';
					document.getElementById("ActiveChatStartDate").innerHTML='';
					document.getElementById("ActiveChatManager").innerHTML='';
					}

				RefreshActiveChatView();
				}
			}
		delete xmlhttp;
		}
}

function RefreshActiveChatView() {
	var user=document.getElementById("user").value;
	var pass='<?php echo $pass ?>';
	var ChatReloadIDNumber=document.getElementById("ChatReloadIDNumber").value;
	var manager_chat_id=document.getElementById("CurrentActiveChat").value;
	var manager_chat_subid=document.getElementById("CurrentActiveChatSubID").value;
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
		var chat_SQL_query = "action=RefreshActiveChatView&user="+user+"&pass="+pass+"&ChatReloadIDNumber="+ChatReloadIDNumber+"&manager_chat_id="+manager_chat_id+"&manager_chat_subid="+manager_chat_subid;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var ActiveChatText = null;
				ActiveChatText = xmlhttp.responseText;
				if(ActiveChatText!="") 
					{
					var ActiveChatText_array=ActiveChatText.split("|");
					document.getElementById("ChatReloadIDNumber").value=ActiveChatText_array[0];
					document.getElementById("AllActiveChats").innerHTML=ActiveChatText_array[1];
					}
				}
			}
		delete xmlhttp;
		}
}

function ReloadAgentNewChatSpan(user) {
	var xmlhttp=false;
	var pass='<?php echo $pass ?>';
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
		var chat_SQL_query = "action=ReloadAgentNewChatSpan&user="+user+"&pass="+pass;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_SQL_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var Agent2AgentText = xmlhttp.responseText;
				document.getElementById("AgentNewChatSpan").innerHTML=Agent2AgentText;
				}
			}
		delete xmlhttp;
		}
}

function SendMgrChatMessage(manager_chat_id, manager_chat_subid) {
	// if (!manager_chat_id) {return false;}
	if (manager_chat_id)
		{
		document.getElementById("CurrentActiveChat").value=manager_chat_id;
		document.getElementById("CurrentActiveChatSubID").value=manager_chat_subid;
		} 
	else 
		{
		var manager_chat_id=document.getElementById("CurrentActiveChat").value;
		var manager_chat_subid=document.getElementById("CurrentActiveChatSubID").value;
		}

	var user=document.getElementById("user").value;
	var pass='<?php echo $pass ?>';
	var agent_override=document.getElementById("AgentManagerOverride").value;
	var chat_message=encodeURIComponent(document.getElementById("manager_message").value);

	if (!chat_message || chat_message=="") {return false;}

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
		var chat_SQL_query = "action=SendMgrChatMessage&user="+user+"&pass="+pass+"&manager_chat_id="+manager_chat_id+"&manager_chat_subid="+manager_chat_subid+"&chat_message="+chat_message+"&agent_override="+agent_override;
		xmlhttp.open('POST', 'chat_db_query.php'); 
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
					chat_alert_box(ChatText);
					}
				else 
					{
					document.getElementById("manager_message").value="";
					}
				}
			}
		delete xmlhttp;
		}
}

function LoadAvailableAgentsForChat(destinationId, field_name) {
	var user=document.getElementById("user").value;
	var manager_chat_id=document.getElementById("CurrentActiveChat").value;
	var manager_chat_subid=document.getElementById("CurrentActiveChatSubID").value;
	var pass='<?php echo $pass ?>';
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
		var chat_SQL_query = "action=load_available_agents_for_chat&user="+user+"&pass="+pass+"&manager_chat_id="+manager_chat_id+"&manager_chat_subid="+manager_chat_subid+"&field_name="+field_name;
		xmlhttp.open('POST', 'chat_db_query.php'); 
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
					chat_alert_box(ChatText);
					}
				else 
					{
					document.getElementById(destinationId).innerHTML=ChatText;
					}
				}
			}
		delete xmlhttp;
		}
}

function AddAgentToExistingChat() {
	var agent_to_add=document.getElementById("agent_to_add").value;
	var user=document.getElementById("user").value;
	var manager_chat_id=document.getElementById("CurrentActiveChat").value;
	var manager_chat_subid=document.getElementById("CurrentActiveChatSubID").value;
	var pass='<?php echo $pass ?>';
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
		var chat_SQL_query = "action=add_agent_to_existing_chat&user="+user+"&pass="+pass+"&manager_chat_id="+manager_chat_id+"&manager_chat_subid="+manager_chat_subid+"&agent_to_add="+agent_to_add;
		xmlhttp.open('POST', 'chat_db_query.php'); 
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
					chat_alert_box(ChatText);
					}
				else 
					{
					// document.getElementById(destinationId).innerHTML=ChatText;
					}
				LoadAvailableAgentsForChat('AllLiveNonChatAgents', 'agent_to_add');
				}
			}
		delete xmlhttp;
		}
}

function ToggleSpan(span_name) {
	var span_vis = document.getElementById(span_name).style;
	if (span_vis.display=='none') { span_vis.display = 'block'; } else { span_vis.display = 'none'; }
}

function MgrAgentAutoRefresh() {
	rInt=window.setInterval(function() {DisplayMgrAgentChat()}, 500);
}


</script>
<title><?php echo _QXZ("ADMINISTRATION: Agent/Manager Chat Interface"); ?></title>
<body onLoad="MgrAgentAutoRefresh();" onUnLoad="clearInterval(rInt);"><!-- DisplayMgrAgentChat(<?php echo $priority_chat; ?>); -->
<span id="AgentChatSpan" name="AgentChatSpan" style="display: block;">
<?php
echo "<form name='agent_manager_chat_form' id='agent_manager_chat_form'>";
echo "<table width='620' border='0' cellpadding='5' cellspacing='0'>";
echo "<TR BGCOLOR='#E6E6E6'>\n";
echo "<td align='left' width='190' valign='top'><font class='arial'>"._QXZ("Chatting with").": </font><BR><span class='arial_bold' id='ActiveChatManager'>".$chat_managers_array[$priority_chat]."</span></td>";
echo "<td align='right' width='190' valign='top'><font class='arial'>"._QXZ("Chat started").": </font><BR><span class='arial_bold' id='ActiveChatStartDate'>".$chat_start_date_array[$priority_chat]."</span></td>";
echo "<td align='left' width='*' valign='bottom'><font class='arial'>"._QXZ("Your active chats").":</font></td>";
echo "</TR>";

echo "<TR BGCOLOR='#E6E6E6'>\n";
echo "<TD align='left' colspan='2' valign='top' width='380'>\n";
echo "\t<div class='scrolling_transcript' id='ActiveManagerChatTranscript'></div><BR>\n";
echo "\t<div id='AllowAgentReplies' align='center' style='display:none;'>\n";
echo "\t<textarea class='small_arial' rows='2' cols='65' name='manager_message' id='manager_message' onkeypress='if (event.keyCode == 13) {SendMgrChatMessage();}'></textarea><BR><input class='blue_btn' type='button' style='width:200px' value='"._QXZ("SEND MESSAGE")."' onClick=\"SendMgrChatMessage()\">\n";
echo "\t</div>\n";
echo "</TD>\n";
echo "<TD align='left' rowspan='2' valign='top' width='210'>\n";
echo "<div class='scrolling_chat_display' id='AllActiveChats'>\n";
	echo "<ul class='chatview'>";
	if (empty($chat_managers_array)) {
		echo "\t<li class='arial_bold'>"._QXZ("NO OPEN CHATS")."</li>\n";
	} else {
		while (list($manager_chat_id, $text) = each($chat_managers_array)) {
			$manager_chat_subid=$chat_subid_array[$manager_chat_id];
			if (!empty($unread_chats_array) && in_array($manager_chat_id, $unread_chats_array)) {$cclass="unreadchat";} else {$cclass="viewedchat";}
			echo "\t<li class='".$cclass."'><a onClick=\"document.getElementById('CurrentActiveChat').value='$manager_chat_id'; document.getElementById('CurrentActiveChatSubID').value='$manager_chat_subid'; document.getElementById('AgentManagerOverride').value='".$agents_managers_array[$manager_chat_id]."'; LoadAvailableAgentsForChat('AllLiveNonChatAgents', 'agent_to_add');\">Chat #".$manager_chat_id."</a></li>\n"; # $chat_managers_array[$manager_chat_id]

				$additional_agents_stmt="select concat(manager, selected_agents) as participants from vicidial_manager_chats where manager_chat_id='$manager_chat_id'";
				$additional_agents_rslt=mysql_to_mysqli($additional_agents_stmt, $link);
				$aa_row=mysqli_fetch_row($additional_agents_rslt);
				$additional_agents=preg_replace("/^\||\|$/", "", $aa_row[0]);
				$additional_agents=preg_replace("/\|/", "','", $additional_agents);
				$full_name_stmt="select full_name from vicidial_users where user in ('$additional_agents') and user!='$user' order by full_name asc";
				$full_name_rslt=mysql_to_mysqli($full_name_stmt, $link);
				while ($fname_row=mysqli_fetch_row($full_name_rslt)) {
					echo "\t<li class='additional_agents'><a onClick=\"document.getElementById('CurrentActiveChat').value='$manager_chat_id'; document.getElementById('CurrentActiveChatSubID').value='$manager_chat_subid'; document.getElementById('AgentManagerOverride').value='".$agents_managers_array[$manager_chat_id]."'; LoadAvailableAgentsForChat('AllLiveNonChatAgents', 'agent_to_add');\">".$fname_row[0]."</a></li>\n";
				}

			$sid++;
		}
	}
	echo "</ul>\n";
echo "\t</div>\n";
echo "<font class='small_arial_bold'>("._QXZ("bolded chats = unread messages").")<BR><input type='checkbox' id='MuteChatAlert' name='MuteChatAlert'>"._QXZ("Mute alert sound")."</font>\n";
echo "\t<BR><input class='green_btn' type='button' style='width:200px' value='"._QXZ("CHAT WITH LIVE AGENT")."' onClick=\"document.getElementById('AgentChatSpan').style.display='none'; document.getElementById('AgentNewChatSpan').style.display='block'; ReloadAgentNewChatSpan('$user');\">\n";
echo "\t<span id='AgentEndChatSpan' style='display: none;'><div align='left'><BR><input class='red_btn' type='button' style='width:200px' value='"._QXZ("END CHAT")."' onClick='EndAgentToAgentChat()'></div></span>";
echo "\t<span id='AgentAddChatSpan' style='display: none;'><BR><input class='blue_btn' type='button' style='width:200px' value='"._QXZ("ADD AGENT TO CURRENT CHAT")."' onClick=\"LoadAvailableAgentsForChat('AllLiveNonChatAgents', 'agent_to_add'); ToggleSpan('AllLiveNonChatAgents');\"></span>\n";
echo "<BR><div id='AllLiveNonChatAgents' align='center' style='display: none;'></div></span>";

echo "</TD>\n";
echo "</TR>\n";

#echo "<TR BGCOLOR='#E6E6E6'>\n";
#echo "<TD align='center' colspan='2'>&nbsp;\n";
#echo "</TD>\n";
#echo "</TR>\n";
echo "</table>\n";
echo "<input type='hidden' name='CurrentActiveChat' id='CurrentActiveChat' value='$priority_chat'>\n";
echo "<input type='hidden' name='InternalMessageCount' id='InternalMessageCount' value='0'>\n";
echo "<input type='hidden' name='CurrentActiveChatSubID' id='CurrentActiveChatSubID' value='$priority_chat_subid'>\n";
echo "<input type='hidden' name='AgentManagerOverride' id='AgentManagerOverride' value='$agent_manager_override'>\n";
echo "<input type='hidden' name='user' id='user' value='$user'>\n";
echo "<input type='hidden' size='50' name='ChatReloadIDNumber' id='ChatReloadIDNumber' value='$ChatReloadIDNumber'>\n";
echo "</form>";
?>
</span>
<span id='AgentNewChatSpan' name='AgentNewChatSpan' style='display: none;'>
<?php
echo "<table width='600' border='0' cellpadding='5' cellspacing='0'>\n";
echo "<TR BGCOLOR='#E6E6E6' valign='top'>\n";
echo "<td width='*'><font class='arial'>"._QXZ("Select a live agent").":</font><BR>\n";

$stmt="SELECT user_group from vicidial_users where user='$user';";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00573',$user,$server_ip,$session_name,$one_mysql_log);}
$row=mysqli_fetch_row($rslt);
$VU_user_group =	$row[0];

$stmt="SELECT campaign_id from vicidial_live_agents where user='$user';";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
$row=mysqli_fetch_row($rslt);
$campaign_id =	$row[0];

$agent_allowed_chat_groupsSQL='';
### Gather timeclock and shift enforcement restriction settings
$stmt="SELECT agent_status_viewable_groups,agent_status_view_time,agent_allowed_chat_groups from vicidial_user_groups where user_group='$VU_user_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$agent_allowed_chat_groups = $row[2];
$agent_allowed_chat_groupsSQL = preg_replace('/\s\s/i','',$agent_allowed_chat_groups);
$agent_allowed_chat_groupsSQL = preg_replace('/\s/i',"','",$agent_allowed_chat_groupsSQL);
$agent_allowed_chat_groupsSQL = "user_group IN('$agent_allowed_chat_groupsSQL')";
$agent_status_view = 0;
if (strlen($agent_allowed_chat_groups) > 2)
	{$agent_status_view = 1;}
$agent_status_view_time=0;
if ($row[1] == 'Y')
	{$agent_status_view_time=1;}
$andSQL='';
if (preg_match("/ALL-GROUPS/",$agent_allowed_chat_groups))
	{$AGENTviewSQL = "";}
else
	{
	$AGENTviewSQL = "($agent_allowed_chat_groupsSQL)";

	if (preg_match("/CAMPAIGN-AGENTS/",$agent_allowed_chat_groups))
		{$AGENTviewSQL = "($AGENTviewSQL or (campaign_id='$campaign_id'))";}
	$AGENTviewSQL = "and $AGENTviewSQL";
	}

$stmt="SELECT vla.user,vu.full_name from vicidial_live_agents vla,vicidial_users vu where vla.user=vu.user and vu.user!='$user' $AGENTviewSQL order by vu.full_name;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($rslt) {$agents_count = mysqli_num_rows($rslt);}
$loop_count=0;
echo "<div id='AllLiveAgents'>"; # TEST
echo "<select name='agent' id='agent'>\n";
echo "<option value=''>Available agents</option>\n";
while ($agents_count > $loop_count)
	{
	$row=mysqli_fetch_row($rslt);
	echo "<option value='$row[0]'>$row[1]</option>\n";
	$loop_count++;
	}
echo "</select>";
echo "</div>"; # TEST

echo "</td>\n";
echo "<td width='200'><font class='arial'>"._QXZ("Message").":</font><BR>\n";
echo "<textarea class='small_arial' rows='5' style='width:200px; name='agent_message' id='agent_message'></textarea>";
echo "</td></TR>\n";

echo "<TR BGCOLOR='#E6E6E6'>\n";
echo "<td><BR><input class='red_btn' type='button' style='width:200px' value='"._QXZ("BACK TO CHAT SCREEN")."' onClick=\"document.getElementById('AgentChatSpan').style.display='block'; document.getElementById('AgentNewChatSpan').style.display='none';\"></td>\n";
echo "<td align='center'><BR><input class='green_btn' type='button' style='width:200px' value='"._QXZ("START CHAT")."' onClick=\"CreateAgentToAgentChat()\">\n</td></TR>\n";
echo "</table>";
?>
</span>
</body>
</html>
