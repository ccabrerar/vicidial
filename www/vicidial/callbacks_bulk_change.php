<?php
# callbacks_bulk_change.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 120819-0119 - First build
# 130414-0021 - Added admin logging
# 130610-0951 - Finalized changing of all ereg instances to preg
# 130620-0902 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-1940 - Changed to mysqli PHP functions
# 141007-2207 - Finalized adding QXZ translation to all admin files
# 141229-2050 - Added code for on-the-fly language translations display
# 161104-0702 - Added option to change callbacks to ANYONE callbacks
# 170409-1547 - Added IP List validation code
# 170822-2255 - Added screen color settings
# 170829-0040 - Added screen color settings
# 220217-2220 - Added input variable filtering
# 220224-1752 - Added allow_web_debug system setting
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["old_user"]))			{$old_user=$_GET["old_user"];}
	elseif (isset($_POST["old_user"]))	{$old_user=$_POST["old_user"];}
if (isset($_GET["new_user"]))			{$new_user=$_GET["new_user"];}
	elseif (isset($_POST["new_user"]))	{$new_user=$_POST["new_user"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["stage"]))				{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))		{$stage=$_POST["stage"];}
if (isset($_GET["confirm_transfer"]))			{$confirm_transfer=$_GET["confirm_transfer"];}
	elseif (isset($_POST["confirm_transfer"]))	{$confirm_transfer=$_POST["confirm_transfer"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["convert_to_anyone"]))			{$convert_to_anyone=$_GET["convert_to_anyone"];}
	elseif (isset($_POST["convert_to_anyone"]))	{$convert_to_anyone=$_POST["convert_to_anyone"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$SSenable_languages =			$row[3];
	$SSlanguage_method =			$row[4];
	$SSallow_web_debug =			$row[5];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$convert_to_anyone = preg_replace('/[^-_0-9a-zA-Z]/', '', $convert_to_anyone);
$confirm_transfer = preg_replace('/[^-_0-9a-zA-Z]/', '', $confirm_transfer);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$old_user = preg_replace('/[^-_0-9a-zA-Z]/', '', $old_user);
	$new_user = preg_replace('/[^-_0-9a-zA-Z]/', '', $new_user);
	$group = preg_replace('/[^-_0-9a-zA-Z]/', '', $group);
	$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$old_user = preg_replace('/[^-_0-9\p{L}]/u', '', $old_user);
	$new_user = preg_replace('/[^-_0-9\p{L}]/u', '', $new_user);
	$group = preg_replace('/[^-_0-9\p{L}]/u', '', $group);
	$stage = preg_replace('/[^-_0-9\p{L}]/u', '', $stage);
	}

$StarTtimE = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$ip = getenv("REMOTE_ADDR");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

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

$stmt="SELECT full_name,change_agent_campaign,modify_timeclock_log,user_group,user_level,modify_leads from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$change_agent_campaign =	$row[1];
$modify_timeclock_log =		$row[2];
$LOGuser_group =			$row[3];
$user_level =				$row[4];
$LOGmodify_leads =			$row[5];
if ($user_level==9) 
	{
	$ul_clause="where user_level<=9";
	}
else 
	{
	$ul_clause="where user_level<$user_level";
	}

if ($LOGmodify_leads < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify leads").": |$PHP_AUTH_USER|\n";
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

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}

$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group desc;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$groups_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =		$row[0];
	$group_names[$i] =	$row[1];
	$i++;
	}

if ($SUBMIT && $old_user && $new_user && $confirm_transfer) {
	if ($new_user=="ANYONE") {
		$upd_stmt="UPDATE vicidial_callbacks set recipient='ANYONE' where recipient='USERONLY' and status!='INACTIVE' and user='$old_user' $LOGadmin_viewable_groupsSQL";
	} else {
		$upd_stmt="UPDATE vicidial_callbacks set user='$new_user' where recipient='USERONLY' and status!='INACTIVE' and user='$old_user' $LOGadmin_viewable_groupsSQL";
	}
	$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
	if ($DB) {echo "$upd_stmt\n";}

	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$upd_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$new_user', event_code='ADMIN CALLBACK BULK CHANGE', event_sql=\"$SQL_log\", event_notes='Old user: $old_user';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
}

require("screen_colors.php");

?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: USERONLY Callbacks Transfer"); ?>
<?php

##### BEGIN Set variables to make header show properly #####
$ADD =					'311111';
$hh =					'usergroups';
$LOGast_admin_access =	'1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$usergroups_color =		'#FFFF99';
$usergroups_font =		'BLACK';
$usergroups_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");

?>

<CENTER>
<TABLE WIDTH=620 BGCOLOR=#<?php echo $SSframe_background; ?> cellpadding=2 cellspacing=0><TR BGCOLOR=#<?php echo $SSmenu_background; ?>><TD ALIGN=LEFT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B> &nbsp; <?php echo _QXZ("USERONLY Callback Transfer"); ?></TD><TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B> &nbsp; </TD></TR>




<?php 

echo "<TR BGCOLOR=\"#".$SSstd_row1_background."\"><TD ALIGN=center COLSPAN=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3><B> &nbsp; \n";

### callbacks change form
echo "<form action=$PHP_SELF method=POST>\n";
echo "<input type=hidden name=DB value=\"$DB\">\n";
if ($SUBMIT && $old_user && $new_user && !$confirm_transfer) 
	{
	$stmt="select count(*) as ct from vicidial_callbacks where recipient='USERONLY' and status!='INACTIVE' and user='$old_user' $LOGadmin_viewable_groupsSQL";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_array($rslt);
	$callback_ct=$row["ct"];

	$user_stmt="select full_name from vicidial_users $ul_clause and user='$old_user' $LOGadmin_viewable_groupsSQL";
	$user_rslt=mysql_to_mysqli($user_stmt, $link);
	$user_row=mysqli_fetch_array($user_rslt);
	$old_user_name=$user_row["full_name"];

	$user_stmt="select full_name from vicidial_users $ul_clause and user='$new_user' $LOGadmin_viewable_groupsSQL ";
	$user_rslt=mysql_to_mysqli($user_stmt, $link);
	$user_row=mysqli_fetch_array($user_rslt);
	$new_user_name=$user_row["full_name"];

	echo "<input type=hidden name=old_user value=\"$old_user\">\n";
	echo "<input type=hidden name=new_user value=\"$new_user\">\n";
	echo _QXZ("You are about to transfer")." $callback_ct "._QXZ("callbacks")."<BR>\n";
	echo _QXZ("from user")." $old_user - $old_user_name<BR>\n";
	echo _QXZ("to user")." $new_user".($new_user_name!="" ? " - $new_user_name" : "")."<BR><BR><BR>\n";
	echo "<a href='$PHP_SELF?DB=$DB&old_user=$old_user&new_user=$new_user&confirm_transfer=1&SUBMIT=1'>"._QXZ("CLICK TO CONFIRM")."</a><BR><BR>";
	echo "<a href='$PHP_SELF?DB=$DB'>"._QXZ("CLICK TO CANCEL")."</a><BR><BR>";
	} 
else 
	{
	$stmt="select user, count(*) as ct from vicidial_callbacks where recipient='USERONLY' and status!='INACTIVE' $LOGadmin_viewable_groupsSQL group by user order by user";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	# <tr><td align='right'>
	echo _QXZ("Agents with callbacks").":<BR><select name='old_user' size=5>\n";
	if (mysqli_num_rows($rslt)>0) 
		{
		while ($row=mysqli_fetch_array($rslt)) 
			{
			$user_stmt="select full_name from vicidial_users $ul_clause and user='$row[user]' $LOGadmin_viewable_groupsSQL";
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			if (mysqli_num_rows($user_rslt)>0) 
				{
				$user_row=mysqli_fetch_array($user_rslt);
				echo "\t<option value='$row[user]'>$row[user] - $user_row[full_name] - ($row[ct] "._QXZ("callbacks").")</option>\n";
				}
			}
		} 
	else 
		{
		echo "\t<option value=''>**"._QXZ("NO CALLBACKS")."**</option>\n";
		}
	echo "</select>";
	echo "<BR/><BR/><input type='submit' name='SUBMIT' value='  "._QXZ("TRANSFER TO")." '><BR/><BR/>";

	$stmt="select user, full_name from vicidial_users $ul_clause $LOGadmin_viewable_groupsSQL order by user asc";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	echo "<select name='new_user' size=5>\n";
	echo "\t<option value='ANYONE'>"._QXZ("Anyone - available to all agents in campaign")."</option>\n";
	while ($row=mysqli_fetch_array($rslt)) 
		{
		echo "\t<option value='$row[user]'>$row[user] - $row[full_name]</option>\n";
		}
	echo "</select>\n";
	}
echo "</form>\n";

echo "\n<BR><BR><BR>";


$ENDtime = date("U");

$RUNtime = ($ENDtime - $StarTtimE);

echo "\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";

echo "|$stage|$group|";

?>


</TD></TR><TABLE>
</body>
</html>

<?php
	
exit; 


?>
