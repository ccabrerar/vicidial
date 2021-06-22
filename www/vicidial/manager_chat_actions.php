<?php
# manager_chat_actions.php
# 
# Copyright (C) 2017  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# Contains PHP actions for manager_chat_interface.php - works with vicidial_chat.js
#
# changes:
# 150605-2022 - First Build
# 151213-1114 - Added variable filtering
# 151218-0739 - Added translation where missing
# 160107-2315 - Bug fix to prevent sending messages on dead chats
# 160108-2300 - Changed some mysqli_query to mysql_to_mysqli for consistency
# 161217-0821 - Added chat-type to allow for multi-user internal chat sessions
# 170409-1550 - Added IP List validation code
#

$admin_version = '2.14-7';
$build = '170409-1550';

$sh="managerchats"; 

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["manager_chat_id"]))			{$manager_chat_id=$_GET["manager_chat_id"];}
	elseif (isset($_POST["manager_chat_id"]))	{$manager_chat_id=$_POST["manager_chat_id"];}
if (isset($_GET["chat_sub_id"]))				{$chat_sub_id=$_GET["chat_sub_id"];}
	elseif (isset($_POST["chat_sub_id"]))		{$chat_sub_id=$_POST["chat_sub_id"];}
if (isset($_GET["chat_sub_ids"]))				{$chat_sub_ids=$_GET["chat_sub_ids"];}
	elseif (isset($_POST["chat_sub_ids"]))		{$chat_sub_ids=$_POST["chat_sub_ids"];}
if (isset($_GET["chat_message"]))				{$chat_message=$_GET["chat_message"];}
	elseif (isset($_POST["chat_message"]))		{$chat_message=$_POST["chat_message"];}
if (isset($_GET["reload_chat_span"]))			{$reload_chat_span=$_GET["reload_chat_span"];}
	elseif (isset($_POST["reload_chat_span"]))	{$reload_chat_span=$_POST["reload_chat_span"];}
if (isset($_GET["action"]))						{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))			{$action=$_POST["action"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,allow_chats,enable_languages,language_method,default_language FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =			$row[0];
	$SSallow_chats =		$row[1];
    $SSenable_languages =	$row[2];
    $SSlanguage_method =	$row[3];
	$SSdefault_language =	$row[4];
	}
$VUselected_language = $SSdefault_language;
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace("/[^-_0-9a-zA-Z]/", "",$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace("/[^-_0-9a-zA-Z]/", "",$PHP_AUTH_PW);
	$user = preg_replace("/[^-_0-9a-zA-Z]/", "",$user);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$user = preg_replace("/'|\"|\\\\|;/","",$user);
	}

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

$user_stmt="select full_name,user_level,selected_language from vicidial_users where user='$PHP_AUTH_USER'";
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


$chat_sub_id_ct=count($chat_sub_ids);

$debug=0;

if ($action=="EndAgentChat") {
	$archive_stmt="insert ignore into vicidial_manager_chat_log_archive select * from vicidial_manager_chat_log where manager_chat_id='$manager_chat_id' and manager_chat_subid='$chat_sub_id'";
	$archive_rslt=mysql_to_mysqli($archive_stmt, $link);

	if ($debug==1) {echo "$archive_stmt<BR>\n";}

	$delete_stmt="delete from vicidial_manager_chat_log where manager_chat_id='$manager_chat_id' and manager_chat_subid='$chat_sub_id'";
	$delete_rslt=mysql_to_mysqli($delete_stmt, $link);

	if ($debug==1) {echo "$delete_stmt<BR>\n";}

	$count_stmt="select count(*) from vicidial_manager_chat_log where manager_chat_id='$manager_chat_id'";

	if ($debug==1) {echo "$count_stmt<BR>\n";}

	$count_rslt=mysql_to_mysqli($count_stmt, $link);
	$count_row=mysqli_fetch_row($count_rslt);
	$active_chats=$count_row[0];
	if ($active_chats==0) {
		$archive_stmt="insert ignore into vicidial_manager_chats_archive select * from vicidial_manager_chats where manager_chat_id='$manager_chat_id'";
		$archive_rslt=mysql_to_mysqli($archive_stmt, $link);
		
		$del_stmt="delete from vicidial_manager_chats where manager_chat_id='$manager_chat_id'";
		$del_rslt=mysql_to_mysqli($del_stmt, $link);
		$manager_chat_id="";
		echo "ALL CHATS CLOSED";
		exit;
	} 

	$reload_chat_span=1;
}

if ($action=="CheckNewMessages") {
	$time_stmt="select distinct manager_chat_subid from vicidial_manager_chat_log where manager_chat_id='$manager_chat_id' and message_date>=now()-INTERVAL 6 SECOND";
	$time_rslt=mysql_to_mysqli($time_stmt, $link);
	# echo $time_stmt;
	if (mysqli_num_rows($time_rslt)==0) {
		echo "0";
	} else {
		$sub_id_str="";
		while ($row=mysqli_fetch_row($time_rslt)) {
			$sub_id_str.="$row[0]\n";
		}
		echo $sub_id_str;
	}
}

if ($action=="CheckEndedChats" && $manager_chat_id) {
	$ended_chat_stmt="select distinct manager_chat_subid from vicidial_manager_chat_log_archive where manager_chat_id='$manager_chat_id'";
	$ended_chat_rslt=mysql_to_mysqli($ended_chat_stmt, $link);
	if (mysqli_num_rows($ended_chat_rslt)==0) {
		echo "0";
	} else {
		$sub_id_str="";
		while ($row=mysqli_fetch_row($ended_chat_rslt)) {
			$sub_id_str.="$row[0]\n";
		}
		echo trim($sub_id_str);
	}
}

if ($action=="PrintSubChatText") {
	for ($i=0; $i<$chat_sub_id_ct; $i++) {
		$chat_sub_id=$chat_sub_ids[$i];
		$span_name="manager_chat_".$manager_chat_id."_".$chat_sub_id;

		$stmt="select vm.message_posted_by, vm.message, vm.message_date, vu.full_name, vm.manager, vm.manager_chat_subid from vicidial_manager_chats vmc, vicidial_manager_chat_log vm, vicidial_users vu where vmc.manager_chat_id='$manager_chat_id' and vmc.manager_chat_id=vm.manager_chat_id and vm.manager_chat_subid='$chat_sub_id' and vm.user=vu.user order by vm.manager_chat_subid asc, message_date desc";
	if ($debug==1) {echo "$stmt<BR>\n";}

		$rslt=mysql_to_mysqli($stmt, $link);
		if (mysqli_num_rows($rslt)>0) {
			$prev_chat_subid="";
			$backlog_limit=20;
			$chat_output_text="";
			while($row=mysqli_fetch_row($rslt)) {
				if ($backlog_limit>0) {
					if ($row[0]==$row[4]) {$fc="#990000";} else {$fc="#000099";}
					$chat_output_text="<font color='$fc' FACE=\"ARIAL,HELVETICA\" size='1'>$row[1]</font><BR>".$chat_output_text; 
					$backlog_limit--;
				}
			}
			echo $span_name."\n".$chat_output_text."\n";
		}
	}
}

if ($action=="SendChatMessage" && $chat_message) {

	$check_live_stmt="select * from vicidial_manager_chat_log where manager_chat_id='$manager_chat_id' and manager_chat_subid='$chat_sub_id'";
	$check_live_rslt=mysql_to_mysqli($check_live_stmt, $link);
	if (mysqli_num_rows($check_live_rslt)==0) {
		echo _QXZ("Error - chat session was ended by the agent");
	} else {

		$chat_message = preg_replace("/\r/i",'',$chat_message);
		$chat_message = preg_replace("/\n/i",' ',$chat_message);

		$message_id=date("U").".".rand(10000000,99999999);

		$ins_chat_stmt="insert into vicidial_manager_chat_log(manager_chat_id, manager_chat_subid, manager, user, message, message_id, message_date, message_posted_by) VALUES('$manager_chat_id', '$chat_sub_id', '$PHP_AUTH_USER', '$user', '".mysqli_real_escape_string($link, $chat_message)."', '$message_id', now(), '$PHP_AUTH_USER')";
		$ins_chat_rslt=mysql_to_mysqli($ins_chat_stmt, $link);
		if (mysqli_insert_id($link)>0) {
			$reload_chat_span=1;
		} else {
			echo _QXZ("Error sending message.");
		}
	}
}

if ($reload_chat_span) {
	if (!$manager_chat_id) {
		$stmt="select vla.user, vu.full_name, vu.user_group, vla.campaign_id, vc.campaign_name, vug.group_name from vicidial_users vu, vicidial_live_agents vla, vicidial_campaigns vc, vicidial_user_groups vug where vla.user=vu.user and vla.campaign_id=vc.campaign_id and vu.user_group=vug.user_group";
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

		echo "<TABLE width=750 cellspacing=1 cellpadding=1>\n";
		echo "<TR><TD align='left' class='arial'>"._QXZ("VICIDIAL Manager Chat Interface").":</TD><TD align='right' class='arial_bold'><a link='#FFFF00' vlink='#FFFF00' href='manager_chat_interface.php'>["._QXZ("RELOAD")."]</a></TD></TR>";
		echo "<TR BGCOLOR=BLACK>\n";
		echo "<TD><font size=1 color=white width='50%'>"._QXZ("CURRENT LIVE AGENTS")."</TD>\n";
		echo "<TD><font size=1 color=white width='50%'>"._QXZ("CURRENT LIVE CAMPAIGNS")."</TD></tr>\n";
		echo "<TR BGCOLOR='#E6E6E6'>";
		echo "<TD rowspan=3 valign='top'>";
		echo "<select name='available_chat_agents[]' multiple size='12' style=\"width:350px\">\n";
		if (count($user_array)==0) {echo "<option value=''>---- "._QXZ("NO LIVE AGENTS")." ----</option>";}
		#while (list($user, $full_name) = each($user_array)) {
		foreach($user_array as $user => $full_name) {
			echo "<option value='$user'>$user - $full_name</option>\n";
		}
		echo "</select>\n";
		echo "</TD>";
		echo "<TD valign='top'>";
		echo "<select name='available_chat_campaigns[]' multiple size='5' style=\"width:350px\">\n";
		if (count($campaign_id_array)==0) {echo "<option value=''>---- "._QXZ("NO LIVE CAMPAIGNS")." ----</option>";}
		#while (list($campaign_id, $campaign_name) = each($campaign_id_array)) {
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
		# while (list($user_group, $group_name) = each($user_group_array)) {
		foreach($user_group_array as $user_group => $group_name) {
			echo "<option value='$user_group'>$user_group - $group_name</option>\n";
		}
		echo "</select>\n";
		echo "</TD>";
		echo "</TR>";
		echo "</table>";
	} else {
		echo "<TABLE width=750 cellspacing=1 cellpadding=1>";
		echo "<TR BGCOLOR=BLACK>\n";
		echo "<TD align='left' colspan='3'><font FACE=\"ARIAL,HELVETICA\" size=1 color=white><B>"._QXZ("CURRENT CHAT")." #$manager_chat_id</B></font></TD>\n";
		echo "<TD align='right'><font FACE=\"ARIAL,HELVETICA\" size=1><B><a href='manager_chat_interface.php?end_all_chats=$manager_chat_id' style=\"color: rgb(255,255,255)\">[END ALL CHATS]</a></font></td>";
		echo "</TR>";
		echo "<TR BGCOLOR=BLACK>\n";
		echo "\t<TD align='left'><font FACE=\"ARIAL,HELVETICA\" size=1 color=white>Agent</font></TD>\n";
		echo "\t<TD align='left' colspan='1'><font FACE=\"ARIAL,HELVETICA\" size=1 color=white>"._QXZ("Transcript")."</font></TD>\n";
		echo "\t<TD align='left' colspan='2'><font FACE=\"ARIAL,HELVETICA\" size=1 color=white>"._QXZ("Message")."</font></TD>\n";
		echo "</TR>";
		$stmt="select vm.message_posted_by, vm.message, vm.message_date, vu.full_name, vm.manager, vm.manager_chat_subid, vm.user from vicidial_manager_chats vmc, vicidial_manager_chat_log vm, vicidial_users vu where vmc.manager_chat_id='$manager_chat_id' and vmc.manager_chat_id=vm.manager_chat_id and vm.user=vu.user order by vm.manager_chat_subid asc, message_date desc";
		$rslt=mysql_to_mysqli($stmt, $link);
		if (mysqli_num_rows($rslt)>0) {
			$prev_chat_subid="";
			$backlog_limit=20;
			$chat_output_header=array();
			$chat_output_text=array();
			$chat_output_footer=array();
			$chat_subids_array="[";
			while($row=mysqli_fetch_row($rslt)) {
				$full_name=$row[3];
				$chat_subid=$row[5]; 
				$chat_subids_array.="'$chat_subid',";

				if ($backlog_limit>0) {
					if ($row[0]==$row[4]) {$fc="#990000";} else {$fc="#000099";}
					$chat_output_text[$chat_subid]="<font color='$fc' FACE=\"ARIAL,HELVETICA\" size='1'>$row[1]</font><BR>".$chat_output_text[$chat_subid]; 
					$backlog_limit--;
				}
				if ($prev_chat_subid!=$chat_subid) {
					$agent_id=$row[6];
					if ($bgcolor=="#E6E6E6") {$bgcolor="#FFFFFF";} else {$bgcolor="#E6E6E6";}
					$chat_output_header[$chat_subid]= "<TR BGCOLOR='$bgcolor'>\n";
					$chat_output_header[$chat_subid].="\t<td width='100' align='left'><font FACE=\"ARIAL,HELVETICA\" size='1'>$full_name</font></td>";
					$chat_output_header[$chat_subid].="\t<td width='325' align='left' valign='top'><div class='scrolling' id='manager_chat_".$manager_chat_id."_".$chat_subid."'>";

					$chat_output_footer[$chat_subid]= "</span></td>";
					# $chat_output_footer[$chat_subid].="\t<td width='75' align='center'><input type='button' style='width: 75px' class='tiny_green_btn' value='SHOW ALL' onClick='ShowFullChat($manager_chat_id, $chat_subid)'><BR><BR><input type='button' style='width: 75px' class='tiny_red_btn' value='HIDE' onClick='HideFullChat($manager_chat_id, $chat_subid)'></td>";
					$chat_output_footer[$chat_subid].="\t<td width='250' align='left' valign='top'><textarea class='chat_box' id='manager_chat_message_".$manager_chat_id."_".$chat_subid."' name='manager_chat_message_".$manager_chat_id."_".$chat_subid."' rows='7' cols='40'></textarea></td>";
					$chat_output_footer[$chat_subid].="\t<td width='75' align='center'><input type='button' style='width: 75px' class='tiny_green_btn' value='"._QXZ("SEND")."' onClick='SendChatMessage($manager_chat_id, $chat_subid, \"$agent_id\")'><BR><BR><input type='button' style='width: 75px' class='tiny_red_btn' value='"._QXZ("END")."' onClick='EndAgentChat($manager_chat_id, $chat_subid)'></td>";
					$chat_output_footer[$chat_subid].="</TR>";

					$backlog_limit=20;
					$prev_chat_subid=$chat_subid;
				}
			}
			$chat_subids_array=preg_replace("/,$/", "", $chat_subids_array);
			$chat_subids_array.="]";
			# while (list($chat_subid, $text) = each($chat_output_header)) {
			foreach($chat_output_header as $chat_subid => $text) {
				echo $chat_output_header[$chat_subid];
				echo $chat_output_text[$chat_subid];
				echo $chat_output_footer[$chat_subid];
			}
			# $reload_function="setInterval(\"CheckNewMessages($manager_chat_id, $chat_subids_array)\", 3000);\n";
			$reload_function="setInterval(\"CheckNewMessages($manager_chat_id)\", 2000);\n";
		} else {
			$reload_function="setInterval(\"RefreshChatDisplay()\", 30000);\n";
		}
		echo "</table>";
		echo "<script language=\"JavaScript\">\n";
		echo "$reload_function";
		echo "</script>\n";
	}
}
?>
