<?php
# vdc_chat_display.php
#
# Copyright (C) 2017  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This is the interface for agents to chat with customers and each other.  It's separate from the manager-to-agent 
# chat interface out of necessity and calls the chat_db_query.php page to send information and display it.  It will
# display any open chat the agent has, and of those open chats the full conversation of the current active chat 
# will be displayed.  It will also show when an agent has a new unread message in any of his conversations and
# allow the agent to toggle between them.  They can also initiate chats with any agent currently logged into a
# campaign through the agent interface.
#
# Builds:
# 150903-2349 - First build
# 151213-1107 - Added variable filtering
# 151218-0913 - Added missing translation code and user auth
# 160303-0051 - Added code for chat transfers
# 160818-1235 - Added line colors and scrolling
# 170528-1001 - Added variable filtering
#

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["email"]))							{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))					{$email=$_POST["email"];}
if (isset($_GET["email_invite_lead_id"]))			{$email_invite_lead_id=$_GET["email_invite_lead_id"];}
	elseif (isset($_POST["email_invite_lead_id"]))	{$email_invite_lead_id=$_POST["email_invite_lead_id"];}
if (isset($_GET["chat_id"]))						{$chat_id=$_GET["chat_id"];}
	elseif (isset($_POST["chat_id"]))				{$chat_id=$_POST["chat_id"];}
if (isset($_GET["chat_group_id"]))					{$chat_group_id=$_GET["chat_group_id"];}
	elseif (isset($_POST["chat_group_id"]))			{$chat_group_id=$_POST["chat_group_id"];}
if (isset($_GET["chat_group_ids"]))					{$chat_group_ids=$_GET["chat_group_ids"];}
	elseif (isset($_POST["chat_group_ids"]))		{$chat_group_ids=$_POST["chat_group_ids"];}
if (isset($_GET["server_ip"]))						{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))				{$server_ip=$_POST["server_ip"];}
if (isset($_GET["lead_id"]))						{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))				{$lead_id=$_POST["lead_id"];}
if (isset($_GET["user"]))							{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))					{$user=$_POST["user"];}
if (isset($_GET["campaign"]))						{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))				{$campaign=$_POST["campaign"];}
if (isset($_GET["dial_method"]))					{$dial_method=$_GET["dial_method"];}
	elseif (isset($_POST["dial_method"]))			{$dial_method=$_POST["dial_method"];}
if (isset($_GET["pass"]))							{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))					{$pass=$_POST["pass"];}
if (isset($_GET["child_window"]))					{$child_window=$_GET["child_window"];}
	elseif (isset($_POST["child_window"]))			{$child_window=$_POST["child_window"];}
if (isset($_GET["outside_user_name"]))				{$outside_user_name=$_GET["outside_user_name"];}
	elseif (isset($_POST["outside_user_name"]))		{$outside_user_name=$_POST["outside_user_name"];}
if (isset($_GET["first_name"]))						{$first_name=$_GET["first_name"];}
	elseif (isset($_POST["first_name"]))			{$first_name=$_POST["first_name"];}
if (isset($_GET["last_name"]))						{$last_name=$_GET["last_name"];}
	elseif (isset($_POST["last_name"]))				{$last_name=$_POST["last_name"];}
if (isset($_GET["clickmute"]))						{$clickmute=$_GET["clickmute"];}
	elseif (isset($_POST["clickmute"]))				{$clickmute=$_POST["clickmute"];}
if (isset($_GET["stage"]))							{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))					{$stage=$_POST["stage"];}

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

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

if ($clickmute!="1") {$clickmute="0";} // Prevents annoying quirk of playing the audio cue every time you click the tab to view this 

$lead_id = preg_replace("/[^0-9]/","",$lead_id);
$chat_id = preg_replace('/[^- \_\.0-9a-zA-Z]/','',$chat_id);
$chat_group_id = preg_replace('/[^- \_\.0-9a-zA-Z]/','',$chat_group_id);
$server_ip = preg_replace('/[^- \_\.0-9a-zA-Z]/','',$server_ip);
$email = preg_replace("/\'|\"|\\\\|;/","",$email);
$campaign = preg_replace('/[^-\_0-9a-zA-Z]/','',$campaign);
$dial_method = preg_replace('/[^-\_0-9a-zA-Z]/','',$dial_method);
$clickmute = preg_replace("/\'|\"|\\\\|;/","",$clickmute);
$stage = preg_replace('/[^-\_0-9a-zA-Z]/','',$stage);
$email_invite_lead_id = preg_replace("/\'|\"|\\\\|;/","",$email_invite_lead_id);

if ($non_latin < 1)
	{
	$user = preg_replace('/[^-\_0-9a-zA-Z]/','',$user);
	$pass = preg_replace('/[^-\_0-9a-zA-Z]/','',$pass);
	$outside_user_name = preg_replace('/[^- \_\.0-9a-zA-Z]/','',$user);
	$chat_creator = preg_replace('/[^- \_0-9a-zA-Z]/','',$chat_creator);
	$phone_number = preg_replace("/[^0-9]/","",$phone_number);
	$first_name = preg_replace('/[^- \_\.0-9a-zA-Z]/','',$first_name);
	$last_name = preg_replace('/[^- \_\.0-9a-zA-Z]/','',$last_name);
	}
else
	{
	$user = preg_replace("/\'|\"|\\\\|;/","",$user);
	$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);
	$outside_user_name = preg_replace("/\'|\"|\\\\|;/","",$user);
	}

if( (strlen($stage) > 0) and ($stage == 'WELCOME') )
	{
	echo _QXZ("Customer Chat Frame");
	exit;
	}
if ($SSallow_chats < 1)
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("Error, chat disabled on this system");
	exit;
	}

$auth=0;
$auth_message = user_authorization($user,$pass,'',0,0,0,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0))
	{
	echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth_message|vdc_chat_display|\n";
	exit;
	}

$user_stmt="select full_name,user_level,selected_language from vicidial_users where user='$user'";
$user_level=0;
$user_rslt=mysql_to_mysqli($user_stmt, $link);
if (mysqli_num_rows($user_rslt)>0) {
	$user_row=mysqli_fetch_row($user_rslt);
	$full_name =			$user_row[0];
	$user_level =			$user_row[1];
	$VUselected_language =	$user_row[2];

	if ($chat_id) {
		$chat_stmt="select * from vicidial_live_chats where chat_creator='$user' and chat_id='$chat_id'";
	#	echo "<!-- \n$chat_stmt\n";
		$chat_rslt=mysql_to_mysqli($chat_stmt, $link);
		if (mysqli_num_rows($chat_rslt)>0) {
			$chat_creator=$user;
			echo "$chat_creator\n";
		}
	#	echo "\\-->\n";
	} else {
	# 	echo "Waiting for chat request..."; exit;
	}
} else {
	unset($pass);

	## Since user is not a vicidial user, check to see if they belong to another chat and use that as the default chat variable.
	$chat_stmt="select chat_id from vicidial_chat_participants where chat_member='$user'";
	$chat_rslt=mysql_to_mysqli($chat_stmt, $link);
	if (mysqli_num_rows($chat_rslt)>0) {
		$chat_row=mysqli_fetch_row($chat_rslt);
		$chat_id=$chat_row[0];
	}
}
$stmt="select * from vicidial_list where lead_id='$lead_id'";
$rslt=mysql_to_mysqli($stmt, $link);
if (mysqli_num_rows($rslt)>0) {
	$row=mysqli_fetch_array($rslt);
	$first_name=$row["first_name"];
	$last_name=$row["last_name"];
	if (!$full_name) {$full_name=trim("$first_name $last_name");}
	if (!$email) {$email=$row["email"];}
}
header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0
echo '<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
';
?>
<html>
<head>
<title><?php echo _QXZ("Agent Chat Interface"); ?></title>
<script type="text/javascript" src="simpletreemenu.js">

/***********************************************
* Simple Tree Menu- © Dynamic Drive DHTML code library (www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
***********************************************/

</script>

<link rel="stylesheet" type="text/css" href="css/simpletree.css" />

<script language="Javascript">
var clickMute=<?php echo $clickmute; ?>;
// Need campaign ID and dial_method to properly toggle the DialControl span
var campaign="<?php echo $campaign; ?>";
var dial_method="<?php echo $dial_method; ?>";
var email_invite_lead_id="<?php echo $email_invite_lead_id; ?>";
// Borrowed from parent window - need these to toggle controls when agent is starting chats
var DiaLControl_auto_HTML = "<a href=\"#\" onclick=\"AutoDial_ReSume_PauSe('VDADready','','','','','','','YES');\"><img src=\"./images/<?php echo _QXZ("vdc_LB_paused.gif") ?>\" border=\"0\" alt=\"You are paused\" /></a>";
var DiaLControl_auto_HTML_OFF = "<img src=\"./images/<?php echo _QXZ("vdc_LB_blank_OFF.gif") ?>\" border=\"0\" alt=\"pause button disabled\" />";
var DiaLControl_inbound_manual_HTML = "<a href=\"#\" onclick=\"AutoDial_ReSume_PauSe('VDADready','','','','','','','YES');\"><img src=\"./images/<?php echo _QXZ("vdc_LB_paused.gif"); ?>\" border=\"0\" alt=\"You are paused\" /></a><br /><a href=\"#\" onclick=\"ManualDialNext('','','','','','0','','','YES');\"><img src=\"./images/<?php echo _QXZ("vdc_LB_dialnextnumber.gif"); ?>\" border=\"0\" alt=\"Dial Next Number\" /></a>";
var DiaLControl_inbound_manual_HTML_OFF = "<img src=\"./images/<?php echo _QXZ("vdc_LB_blank_OFF.gif"); ?>\" border=\"0\" alt=\"pause button disabled\" /><br /><img src=\"./images/<?php echo _QXZ("vdc_LB_dialnextnumber_OFF.gif"); ?>\" border=\"0\" alt=\"Dial Next Number\" />";

// Show parent alert
	function chat_alert_box(temp_message)
		{
		window.parent.document.getElementById("AlertBoxContent").innerHTML = temp_message;

		parent.showDiv('AlertBox');

		window.parent.document.alert_form.alert_button.focus();
		}


function UpdateChatWindow() {
	var chat_id=document.getElementById('chat_id').value;
	var chat_creator=document.getElementById('chat_creator').value;
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;
	var current_message_field = document.getElementById('current_message_count');
	if (current_message_field == null) {var current_message_count=0;} else {var current_message_count=current_message_field.value;}

	if (chat_id)
		{
		var xmlhttp=false;
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp) 
			{ 
			chat_query = "&chat_creator="+chat_creator+"&chat_id="+chat_id+"&user="+user+"&pass="+pass+"&user_level="+user_level+"&current_message_count="+current_message_count+"&action=update_agent_chat_window";
			xmlhttp.open('POST', 'chat_db_query.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(chat_query); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
					// var fullchatlog = xmlhttp.responseText;
					var chatlogresponse	= xmlhttp.responseText;
					var chatlog_array = chatlogresponse.split("\n");
					var fullchatlog = chatlog_array[0];
					var live_message_count = chatlog_array[1];

					var last_live_message_count=document.getElementById('live_message_count_field');
					if (live_message_count!=last_live_message_count.value) {
						document.getElementById('ChatDisplay').innerHTML=fullchatlog;
						last_live_message_count.value=live_message_count;
					}


					// document.getElementById('ChatDisplay').innerHTML=fullchatlog;
					
					var current_message_field_update = document.getElementById('current_message_count');
					if (current_message_field_update != null) {var current_message_count_update=current_message_field_update.value;}

					// document.getElementById('ChatDisplay').innerHTML+=current_message_count_update+" > "+current_message_count;

					if (current_message_count_update>current_message_count) 
						{
						var myDiv = document.getElementById('ChatDisplay');
						document.getElementById('ChatDisplay').scrollTop = document.getElementById('ChatDisplay').scrollHeight;

						if (clickMute==0 && !document.getElementById("MuteCustomerChatAlert").checked) 
							{
							document.getElementById("CustomerChatAudioAlertFile").play();
							}
						else if (clickMute>0) 
							{
							clickMute=0;
							}
						}
					}
				}
			delete xmlhttp;
			}
		}
}

function SendMessage(chat_id, user, message, chat_member_name) {
	var chat_id=document.getElementById('chat_id').value;
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;
	var chat_message=encodeURIComponent(document.getElementById('chat_message').value.trim());
	var chat_member_name=encodeURIComponent(document.getElementById('chat_member_name').value.trim());
	window.user_level='<?php echo $user_level; ?>';

	if (!document.getElementById('private_message') || !document.getElementById('private_message').checked) {
		var chat_level=0;
	} else {
		var chat_level=1;
	}

	if (!chat_message || !user) {return false;}
	if (!chat_member_name) {chat_alert_box("<?php echo _QXZ("Please enter a name to chat as."); ?>");}
	if (!chat_id) {chat_alert_box("<?php echo _QXZ("You have not joined a chat yet."); ?>");}
	document.getElementById('chat_message').value='';

	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		chat_query = "&chat_message="+chat_message+"&chat_level="+chat_level+"&user_level="+user_level+"&chat_id="+chat_id+"&chat_member_name="+chat_member_name+"&user="+user+"&pass="+pass+"&action=agent_send_message";
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var posting_response = xmlhttp.responseText;
				if (posting_response) 
					{
					chat_alert_box(posting_response);
					}
				else
					{
					UpdateChatWindow();
					}
				}
			}
		delete xmlhttp;
		}
}

function JoinChat(chat_id) {
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;
	var chat_member_name=encodeURIComponent(document.getElementById('chat_member_name').value.trim());
	var chat_creator="";

	if (!chat_member_name)
	{
		chat_alert_box("<?php echo _QXZ("Please enter your name before joining a chat."); ?>");
		return false;
	}

	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		chat_query = "&chat_id="+chat_id+"&chat_member_name="+chat_member_name+"&user="+user+"&pass="+pass+"&action=join_chat";
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var join_attempt_results = xmlhttp.responseText.split("|");
				if (join_attempt_results[1]) 
					{
					chat_alert_box(join_attempt_results[1]);
					}
					else
					{
					chat_creator=xmlhttp.responseText;
					document.getElementById('chat_member_name').disabled = true;
					}
				}
				document.getElementById('chat_id').value=chat_id;
				document.getElementById('chat_creator').value=chat_creator;
				// If user is chat creator, show option to end chat or invite people
				if (chat_creator==user)
					{
					if (!email_invite_lead_id)
						{
						document.getElementById('chat_creator_console').innerHTML="<BR/><BR/><input class='blue_btn' type='button' style=\"width:150px\" value=\"<?php echo _QXZ("INVITE"); ?>\" onClick=\"javascript:document.getElementById('email_window').style.display='block'\">\n<BR/><BR/><input class='red_btn' type='button' style=\"width:150px\" value=\"<?php echo _QXZ("END CHAT"); ?>\" onClick=\"EndChat()\">";
						}
					else 
						{
						document.getElementById('chat_creator_console').innerHTML="<BR/><BR/><input class='red_btn' type='button' style=\"width:150px\" value=\"<?php echo _QXZ("END CHAT"); ?>\" onClick=\"EndChat()\">";
						}
					}
			}
		delete xmlhttp;
		}

}

function RefreshLiveChatWindow() {
	var chat_id=document.getElementById('chat_id').value;
	var chat_creator=document.getElementById('chat_creator').value;
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;
	window.user_level='<?php echo $user_level; ?>';

	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		chat_query = "&chat_creator="+chat_creator+"&chat_id="+chat_id+"&user="+user+"&pass="+pass+"&user_level="+user_level+"&action=show_live_chats";
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var live_chat_info = xmlhttp.responseText;
				document.getElementById('ActiveChats').innerHTML=live_chat_info;
				UpdateChatWindow();
				}
			}
		delete xmlhttp;
		}
}

function StartChat() {
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;
	var chat_group_id=document.getElementById('chat_group_id').value;
	var server_ip=document.getElementById('server_ip').value;

	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		chat_query = "&action=start_chat&user="+user+"&pass="+pass+"&chat_group_id="+chat_group_id+"&server_ip="+server_ip;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var start_chat_attempt_result = xmlhttp.responseText;
				if (!start_chat_attempt_result) {
					chat_alert_box("<?php echo _QXZ("ATTEMPT TO CREATE CHAT SESSION FAILED."); ?>");
				} else if (start_chat_attempt_result=="NOT_PAUSED") {
					chat_alert_box("<?php echo _QXZ("YOU MUST BE PAUSED TO INITIATE A CUSTOMER CHAT."); ?>");
				} else if (start_chat_attempt_result=="NO_GROUP") {
					chat_alert_box("<?php echo _QXZ("PLEASE SELECT A CHAT GROUP BEFORE STARTING A CHAT."); ?>");
				} else if (start_chat_attempt_result=="FAILED_LIVE_STATUS") {
					chat_alert_box("<?php echo _QXZ("UNABLE TO CHANGE LIVE AGENT STATUS"); ?>");
				} else {
					// parent.check_for_incoming_other('skip_email');
					// DEACTIVATE PAUSE BUTTON - AGENTS SHOULD NOT BE ALLOWED TO TOGGLE THIS - THEY ARE ESSENTIALLY INCALL ONCE THEY START A CHAT EVEN IF THE CUSTOMER HASN'T JOINED YET
					if (dial_method=="INBOUND_MAN")
						{
						window.parent.document.getElementById("DiaLControl").innerHTML = DiaLControl_inbound_manual_HTML_OFF;
						}
					else
						{
						window.parent.document.getElementById("DiaLControl").innerHTML = DiaLControl_auto_HTML_OFF;
						}
					chat_alert_box("<?php echo _QXZ("CHAT SESSION CREATED. INVITE CUSTOMER VIA EMAIL TO BEGIN."); ?>");
					document.getElementById('chat_id').value=start_chat_attempt_result;
					window.parent.document.vicidial_form.chat_id.value=start_chat_attempt_result;
					document.getElementById('chat_creator').value=user;
					document.getElementById('chat_creator_console').innerHTML="<BR/><BR/><input class='blue_btn' type='button' style=\"width:150px\" value=\"<?php echo _QXZ("INVITE"); ?>\" onClick=\"javascript:document.getElementById('email_window').style.display='block'\">\n<BR/><BR/><input class='red_btn' type='button' style=\"width:150px\" value=\"<?php echo _QXZ("END CHAT"); ?>\" onClick=\"EndChat()\">";

				}
				UpdateChatWindow();
				}
			}
		delete xmlhttp;
		}
}

function SendInvite() {
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;
	var lead_id=document.getElementById('lead_id').value;
	var chat_id=document.getElementById('chat_id').value;
	var chat_group_id=document.getElementById('chat_group_id').value;
	var email=document.getElementById('email_invite').value;

	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		chat_query = "&action=send_invite&chat_id="+chat_id+"&chat_group_id="+chat_group_id+"&lead_id="+lead_id+"&user="+user+"&pass="+pass+"&email="+email;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var email_sent = xmlhttp.responseText;
				if (email_sent) 
					{
					parent.check_for_incoming_other(email_sent);  // Force the agent interface to do it's thing for a live chat coming across, even though in this case the customer isn't in it yet.  Sends lead ID to parent function as a flag, so as not to show the INVITE button when this page reloads
					document.getElementById('email_window').style.display='none';
					document.getElementById('chat_creator_console').innerHTML="<BR/><BR/><input class='red_btn' type='button' style=\"width:150px\" value=\"<?php echo _QXZ("END CHAT"); ?>\" onClick=\"EndChat()\">";
					}
				else 
					{
					chat_alert_box("<?php echo _QXZ("There was a problem sending the email invite - please re-check your information and try again."); ?>"+email_sent);
					}
				}
			}
		delete xmlhttp;
		}
}

function LeaveChat(extra_action) {
	var chatIDField = document.getElementById('myElementId');
	if (document.getElementById('chat_id'))
		{
		var chat_id=document.getElementById('chat_id').value;
		var chat_creator=document.getElementById('chat_creator').value;
		var user=document.getElementById('user').value;
		var pass=document.getElementById('pass').value;

		var xmlhttp=false;
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp) 
			{ 
			chat_query = "&action=agent_leave_chat&chat_id="+chat_id+"&user="+user+"&pass="+pass;
			// chat_alert_box(chat_query);
			xmlhttp.open('POST', 'chat_db_query.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(chat_query); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
					//if (chat_creator==user) {EndChat();}
					if (extra_action=="close_window") {window.close();}
					}
				}
			delete xmlhttp;
			}
		}
}

function EndChat(hangup_override) { // hangup_override comes from parent Iframe when you click the CALL HANGUP button.  Added 2015-04-14 - not used yet.
	var chat_id=document.getElementById('chat_id').value;
	var chat_creator=document.getElementById('chat_creator').value;
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;
	var server_ip=document.getElementById('server_ip').value;
	var lead_id=document.getElementById('lead_id').value; // used to determine if chat involved a customer.  If so, don't allow START CHAT option until chat is fully terminated.


	if (!chat_creator || !user || !chat_id) {
		return false;
	} else if (user!=chat_creator && chat_creator!='XFER') {
		chat_alert_box("<?php echo _QXZ("Only the chat creator can end the chat"); ?>");
		return false;
	}

	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		chat_query = "&action=end_chat&chat_id="+chat_id+"&chat_creator="+chat_creator+"&user="+user+"&pass="+pass+"&lead_id="+lead_id+"&server_ip="+server_ip;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var finished_chat = xmlhttp.responseText;
				finished_chat_text=finished_chat.split("|");
				if (!hangup_override) {chat_alert_box(finished_chat_text[0]);} // Don't bother to alert if chat ends as the result of the agent clicking HANGUP CUSTOMER from the parent window
				if (finished_chat_text[0].match(/Chat ended/)) 
					{
					document.getElementById('chat_creator_console').innerHTML=finished_chat_text[1];
					document.getElementById('chat_group_id').value='';
					// IF AGENT NEVER INVITED SOMEONE, THERE'S NO LEAD ATTACHED AND IT'S SAFE FOR THEM TO JUST GO BACK TO BEING PAUSED WITHOUT HAVING TO DO ANYTHING ELSE.  
					// HOWEVER, SINCE WE DEACTIVATED THE PAUSE BUTTON FROM THE StartChat() FUNCTION WE NEED TO REACTIVATE IT IN PAUSED MODE.
					if (finished_chat_text[2]=="TOGGLE_DIAL_CONTROL")
						{
						if (dial_method=="INBOUND_MAN")
							{
							window.parent.document.getElementById("DiaLControl").innerHTML = DiaLControl_inbound_manual_HTML;
							}
						else
							{
							window.parent.document.getElementById("DiaLControl").innerHTML = DiaLControl_auto_HTML;
							}
						}
					}
				UpdateChatWindow();
				}
			}
		delete xmlhttp;
		}
}

function StartRefresh() {
	if (!window.parent.document)
		{
		alert("This page cannot run outside of the Vicidial agent interface");
		}
	else 
		{
		rInt=window.setInterval(function() {RefreshLiveChatWindow()}, 1000);
		}
}

function ShowHideMembers(menuName, chat_id) {
	submenu=document.getElementById(menuName);
	if (submenu.getAttribute("rel")=="closed") {
		submenu.style.display="block";
		submenu.setAttribute("rel", "open");
	} else {
		submenu.style.display="none";
		submenu.setAttribute("rel", "closed");
	}
}

function ToggleSpan(span_name) {
	var span_vis = document.getElementById(span_name).style;
	if (span_vis.display=='none') { span_vis.display = 'block'; } else { span_vis.display = 'none'; }
}

function LoadXferOptions() {
	var chat_group_id=document.getElementById('chat_group_id').value;
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;

	// Clear select menus
	var GroupOptions = document.getElementById("ChatXferGroups");
	while (GroupOptions.length > 1) {
	    GroupOptions.remove(GroupOptions.length-1);
	}
	var AgentOptions = document.getElementById("ChatXferAgents");
	while (AgentOptions.length > 1) {
	    AgentOptions.remove(AgentOptions.length-1);
	}

	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		chat_query = "&action=load_xfer_options&user="+user+"&pass="+pass+"&lead_id="+lead_id+"&server_ip="+server_ip+"&chat_group_id="+chat_group_id;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var xfer_options = xmlhttp.responseText;
				if (xfer_options!="")
					{
					var xfer_options_array=xfer_options.split("\n");
					var groups_array=xfer_options_array[0].split("|");
					var group_names_array=xfer_options_array[1].split("|");
					var agents_array=xfer_options_array[2].split("|");
					var agent_names_array=xfer_options_array[3].split("|");
/*
					var opt = document.createElement('option');
					opt.value = "";
					opt.innerHTML = "<?php echo _QXZ("-- Select a group to transfer to --"); ?>";
					GroupOptions.appendChild(opt);
					
					var opt = document.createElement('option');
					opt.value = "";
					opt.innerHTML = "<?php echo _QXZ("-- Select an agent to transfer to --"); ?>";
					AgentOptions.appendChild(opt);
*/					
					for (var i = 0; i<groups_array.length; i++)
						{
						var opt = document.createElement('option');
						opt.value = groups_array[i];
						opt.innerHTML = group_names_array[i];
						GroupOptions.appendChild(opt);
						}

					for (var i = 0; i<agents_array.length; i++)
						{
						var opt = document.createElement('option');
						opt.value = agents_array[i];
						opt.innerHTML = agent_names_array[i];
						AgentOptions.appendChild(opt);
						}
					} 
				}
			}
		}
}

function SendChatXferSpan(selGroup, selAgent) {
	var chat_id=document.getElementById('chat_id').value;
	var chat_member_name=encodeURIComponent(document.getElementById('chat_member_name').value.trim());
	var user=document.getElementById('user').value;
	var pass=document.getElementById('pass').value;
	var server_ip=document.getElementById('server_ip').value;

	if ((selGroup==0 && selAgent==0) || (selGroup>0 && selAgent>0))
		{
		return false;
		}
	else
		{
		if (selGroup>0)
			{
			var chat_xfer_value=document.getElementById("ChatXferGroups").options[selGroup].value;
			var chat_xfer_type="group";
			}
		else 
			{
			var chat_xfer_value=document.getElementById("ChatXferAgents").options[selAgent].value;
			var chat_xfer_type="agent";
			}
		}

	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		chat_query = "&action=xfer_chat&chat_member_name="+chat_member_name+"&user="+user+"&pass="+pass+"&lead_id="+lead_id+"&chat_id="+chat_id+"&server_ip="+server_ip+"&chat_xfer_value="+chat_xfer_value+"&chat_xfer_type="+chat_xfer_type;
		xmlhttp.open('POST', 'chat_db_query.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(chat_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var xfer_result_data = xmlhttp.responseText;
				var xfer_result=xfer_result_data.split("|");
				if (xfer_result[0]==0)
					{
					alert("<?php echo _QXZ("TRANSFER FAILED"); ?>");
					}
				else if (xfer_result[0]>1)
					{
					alert("<?php echo _QXZ("System error - multiple chats found"); ?>");
					}
				else 
					{
					document.getElementById('chat_creator_console').innerHTML=''; // DO NOT MAKE ANY BUTTONS AVAILABLE AT THIS POINT FOR ENDING OR STARTING A CHAT!
					document.getElementById('ChatConsoleSpan').style.display='none';
					document.getElementById('XferConsoleSpan').style.display='none';
					document.getElementById('chat_group_id').value='';
					document.getElementById('chat_creator').value='XFER';
					// IF AGENT NEVER INVITED SOMEONE, THERE'S NO LEAD ATTACHED AND IT'S SAFE FOR THEM TO JUST GO BACK TO BEING PAUSED WITHOUT HAVING TO DO ANYTHING ELSE.  
					// HOWEVER, SINCE WE DEACTIVATED THE PAUSE BUTTON FROM THE StartChat() FUNCTION WE NEED TO REACTIVATE IT IN PAUSED MODE.
					if (xfer_result[2]=="TOGGLE_DIAL_CONTROL")
						{
						if (dial_method=="INBOUND_MAN")
							{
							window.parent.document.getElementById("DiaLControl").innerHTML = DiaLControl_inbound_manual_HTML;
							}
						else
							{
							window.parent.document.getElementById("DiaLControl").innerHTML = DiaLControl_auto_HTML;
							}
						}
					UpdateChatWindow();				
					}
				}
			}
		}
}

window.onbeforeunload = LeaveChat;
</script>
</head>

<?php
if (!$user) {
	echo "<body>"._QXZ("No user ID - no chat access. Sorry")."</body>";
	exit;
}

$user_stmt="select if(user_nickname!='' and user_nickname is not null, user_nickname, full_name) from vicidial_users where user='$user' limit 1";
$user_rslt=mysql_to_mysqli($user_stmt, $link);
$inchat_html=""; $nochat_html="";
$autojoin_js_fx="StartRefresh();";

if (mysqli_num_rows($user_rslt)==0) {
	if($outside_user_name && $chat_id) {
		$inchat_html.="<input type='hidden' name='chat_member_name' id='chat_member_name' value='$outside_user_name'>";
		$autojoin_js_fx.="JoinChat('$chat_id');";
	} else {
		$chat_stmt="select chat_member_name from vicidial_chat_log where poster='$user' order by message_time desc limit 1";
		$chat_rslt=mysql_to_mysqli($chat_stmt, $link);
		if (mysqli_num_rows($chat_rslt)==0) {
			$nochat_html.="Please enter your name below before joining a chat:<BR>";
			$nochat_html.="<input type='text' name='chat_member_name' id='chat_member_name' value='$full_name'>";
		} else {
			$chat_row=mysqli_fetch_row($chat_rslt);
			$outside_user_name=$chat_row[0];
			$inchat_html.="<input type='hidden' name='chat_member_name' id='chat_member_name' value='$outside_user_name'>";
			$autojoin_js_fx.="JoinChat('$chat_id');";
		}
	}
} else {
	$user_row=mysqli_fetch_row($user_rslt);
	$inchat_html.="<input type='hidden' name='chat_member_name' id='chat_member_name' value='$user_row[0]'>";
	$autojoin_js_fx.="JoinChat('$chat_id');";
}

if($child_window) {
	$inchat_html.="<BR><BR><input type='button' class='red_btn' name='close_window_btn' id='close_window_btn' value=\""._QXZ("CLOSE WINDOW")."\" onClick='LeaveChat(\"close_window\")'>";
}
?>
<body onLoad="<?php echo $autojoin_js_fx; ?>" onUnload="javascript:clearInterval(rInt); LeaveChat();">
<?php echo "<!-- $user_stmt\n vdc_chat_display.php?user=$user&pass=$pass&lead_id=$lead_id&list_id=$list_id&email=$email&chat_id=$chat_id -->\n"; ?>
<form name='chat_form' action='<?php echo $PHP_SELF; ?>'>
<table width='100%' border='0'>
<tr>
	<td class='chat_window' height='300' width='*'>
	<span id='ChatDisplay' name='ChatDisplay' style="display:block;height:300px;overflow-y:auto;overflow-x:none;z-index:0;">
	</span>
<!--	<span style="display:block;top:280px;right:25px;z-index:1"><img border="0" src="images/VICIchat_powered_logo.gif" width="123" height="30"></span> //-->
	</td>
	<td width='200' valign='top'>
	<span id='ActiveChats' name='ActiveChats' style="overflow-y:auto; overflow-x:none;">
	</span>
	</td>
</tr>
<tr>
	<td align='center'>
	<span id="ChatConsoleSpan" name="ChatConsoleSpan" style="display: block;">
	<table width='400' align='center' border='0' cellpadding='0' cellspacing='0'>
		<tr>
			<td colspan='3' align='center'>
				<textarea border='1' name='chat_message' id='chat_message' class='chat_window' cols='100' rows='4' onkeypress="if (event.keyCode==13 && !event.shiftKey) {SendMessage(this.form.chat_id.value, this.form.user.value, this.form.chat_message.value); return false;}"></textarea>
			</td>
		</tr>
		<tr>
			<td align='left' class='chat_message' valign='top'><input class='blue_btn' type='button' style="width:100px" value="<?php echo _QXZ("SEND MESSAGE"); ?>" onClick="SendMessage(this.form.chat_id.value, this.form.user.value, this.form.chat_message.value)"><BR><BR><input type='checkbox' id='MuteCustomerChatAlert' name='MuteCustomerChatAlert'><?php echo _QXZ("Mute alert sound"); ?>
			</td>
			<td align='right' class='chat_message' valign='top'><input class='blue_btn' type='button' style="width:100px" value="<?php echo _QXZ("CLEAR"); ?>" onClick="document.getElementById('chat_message').value=''"><BR><BR>
			<?php
			if ($user_level) {
				echo "<input type='checkbox' name='private_message' id='private_message' value='1'>"._QXZ("Privacy ON");
			}
			?>
			</td>
			<td align='right' class='chat_message' valign='top'><input class='red_btn' type='button' style="width:100px" value="<?php echo _QXZ("TRANSFER"); ?>"  onClick="ToggleSpan('ChatConsoleSpan'); ToggleSpan('XferConsoleSpan'); LoadXferOptions();"><BR><BR>
			</td>
		</tr>
		<tr>
			<td colspan='2' align='center'>
			<?php
			echo "$nochat_html$inchat_html";
			?>
			</td>
		</tr>
	</table>
	</span>
	<span id="XferConsoleSpan" name="XferConsoleSpan" style="display: none;">
	<table width='450' align='center' border='0' cellpadding='3' cellspacing='0'>
		<tr>
			<td align='right' width='100' class='chat_message'><?php echo _QXZ("Chat group"); ?>:</td>
			<td align='left' width='240'>
				<select name='ChatXferGroups' id='ChatXferGroups' onChange="document.getElementById('ChatXferAgents').selectedIndex='0'" class='chat_window' style="width:240px">
					<option value=''><?php echo _QXZ("-- Select a group to transfer to --"); ?></option>
				</select>
			</td>
			<td align='center' valign='middle' rowspan='2' width='*'><input class='blue_btn' type='button' style="width:100px" value="<?php echo _QXZ("TRANSFER CHAT"); ?>" onClick="SendChatXferSpan(document.getElementById('ChatXferGroups').selectedIndex, document.getElementById('ChatXferAgents').selectedIndex)"></td>
		</tr>
		<tr>
			<td align='right' class='chat_message'><?php echo _QXZ("Agents"); ?>:</td>
			<td align='left'>
				<select name='ChatXferAgents' id='ChatXferAgents' onChange="document.getElementById('ChatXferGroups').selectedIndex='0'" class='chat_window' style="width:240px">
					<option value=''><?php echo _QXZ("-- Select an agent to transfer to --"); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td align='center' colspan='3'><BR><input class='red_btn' type='button' style="width:100px" value="<?php echo _QXZ("CANCEL"); ?>" onClick="ToggleSpan('ChatConsoleSpan'); ToggleSpan('XferConsoleSpan');"></td>
		</tr>
	</table>
	</span>
	</td>
	<td valign='middle' align='center' width='200' rowspan='2'>
	<?php

	if ($full_name) {
		echo "<span id='chat_creator_console' name='chat_creator_console'>";
		if (!$chat_id) {
			echo "<BR/><BR/><input class='green_btn' type='button' style=\"width:150px\" value=\""._QXZ("START CHAT")."\" onClick=\"StartChat()\">";

			echo "<BR/><BR/><select name='startchat_group_id' id='startchat_group_id' class='chat_window' onChange=\"document.getElementById('chat_group_id').value=this.value\">\n"; 
			echo "<option value='' selected>--"._QXZ("SELECT A CHAT GROUP")."--</option>\n";
			# CREATE LIST OF GroUP IDS to select
			if (count($chat_group_ids)>0) {
				$chat_group_idsSQL = implode("','", $chat_group_ids);
				$chat_group_idsSQL = preg_replace("/\\\\|;/","",$chat_group_idsSQL);
				$group_stmt="select group_id, group_name from vicidial_inbound_groups where group_handling='CHAT' and group_id in ('$chat_group_idsSQL') order by group_name asc";
				$group_rslt=mysql_to_mysqli($group_stmt, $link);
				while ($group_row=mysqli_fetch_row($group_rslt)) {
					echo "<option value='".$group_row[0]."'>".$group_row[1]."</option>\n";
				}
			} else {
				$vla_stmt="select closer_campaigns from vicidial_live_agents where user='$user'";
				$vla_rslt=mysql_to_mysqli($vla_stmt, $link);
				if (mysqli_num_rows($vla_rslt)>0) {
					$vla_row=mysqli_fetch_row($vla_rslt);
					$closer_campaigns=trim($vla_row[0]);
					echo "!$closer_campaigns!";
					$closer_campaigns=preg_replace('/\s/', '\',\'', $closer_campaigns);
					echo "*$closer_campaigns*";
					$closer_campaigns_SQL="'".$closer_campaigns."'";

					$group_stmt="select group_id, group_name from vicidial_inbound_groups where group_handling='CHAT' and group_id in ($closer_campaigns_SQL) order by group_name asc";
					$group_rslt=mysql_to_mysqli($group_stmt, $link);
					while ($group_row=mysqli_fetch_row($group_rslt)) {
						echo "<option value='".$group_row[0]."'>".$group_row[1]."</option>\n";
					}
				}
			}
			echo "</select>\n";
		}
		if ($chat_creator && $chat_creator==$user) {
			if (!$email_invite_lead_id) { # Flag from sending an invite - this page reloads as a result and this below INVITE button must be prevented from being loaded
				echo "<BR/><BR/><input class='blue_btn' type='button' style=\"width:150px\" value=\""._QXZ("INVITE")."\" onClick=\"javascript:document.getElementById('email_window').style.display='block'\">";
			}
			echo "<BR/><BR/><input class='red_btn' type='button' style=\"width:150px\" value=\""._QXZ("END CHAT")."\" onClick=\"EndChat()\">";
		}
		echo "</span>";
	}
	
	?>
	</td>
</tr>
<tr>
	<td align='center' height='50'>&nbsp;
		<span id="email_window" style="display: none">
		<table width='90%'>
			<tr>
				<td align='left' class='chat_message'><?php echo _QXZ("Enter email address of guest"); ?>: <input type='text' name='email_invite' id='email_invite'  onkeypress="if (event.keyCode==13 &amp;&amp; !event.shiftKey) {SendInvite(); return false;}"></td>
				<td align='left'><input class='green_btn' type='button' style="width:150px" value="<?php echo _QXZ("SEND"); ?>" onClick="SendInvite()"></td>
				<td align='center'><input class='red_btn' type='button' style="width:150px" value="<?php echo _QXZ("HIDE"); ?>" onClick="javascript:document.getElementById('email_window').style.display='none'"></td>
			</tr>
		</table>
		</span>
	</td>
</tr>
</table>
<input type='hidden' id='user' name='user' value='<?php echo $user; ?>'>
<input type='hidden' id='chat_id' name='chat_id' value='<?php echo $chat_id; ?>'>
<input type='hidden' id='chat_group_id' name='chat_group_id' value='<?php echo $chat_group_id; ?>'>
<input type='hidden' id='chat_creator' name='chat_creator' value='<?php echo $chat_creator; ?>'>
<input type='hidden' id='live_message_count_field' name='live_message_count_field' value='0'>
<input type='hidden' id='pass' name='pass' value='<?php echo $pass; ?>'>
<input type='hidden' id='lead_id' name='lead_id' value='<?php echo $lead_id; ?>'>
<input type='hidden' id='server_ip' name='server_ip' value='<?php echo $server_ip; ?>'>
<audio id='CustomerChatAudioAlertFile'><source src="sounds/chat_alert.mp3" type="audio/mpeg"></audio>
</form>
</body>
</html>
