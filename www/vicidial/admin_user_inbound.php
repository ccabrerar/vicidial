<?php
# admin_user_inbound.php
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen will control the optional user list new limit override settings 
#
#
# changes:
# 230523-0942 - First Build
#

$admin_version = '2.14-1';
$build = '230523-0942';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["action"]))					{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))		{$action=$_POST["action"];}
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["user"]))					{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))			{$user=$_POST["user"];}
if (isset($_GET["user_override"]))			{$user_override=$_GET["user_override"];}
	elseif (isset($_POST["user_override"]))	{$user_override=$_POST["user_override"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}

if (strlen($action) < 2)
	{$action = 'BLANK';}
if (strlen($DB) < 1)
	{$DB=0;}
$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,enable_languages,language_method,qc_features_active,user_territories_active,user_new_lead_limit,allow_web_debug,inbound_credits FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$ss_conf_ct = mysqli_num_rows($rslt);
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSenable_languages =			$row[2];
	$SSlanguage_method =			$row[3];
	$SSqc_features_active =			$row[4];
	$SSuser_territories_active =	$row[5];
	$SSuser_new_lead_limit =		$row[6];
	$SSallow_web_debug =			$row[7];
	$SSinbound_credits = 			$row[8];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$user_override = preg_replace('/[^-0-9]/','',$user_override);
$action = preg_replace('/[^-_0-9a-zA-Z]/','',$action);
$stage = preg_replace('/[^-_0-9a-zA-Z]/','',$stage);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/','',$SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/','',$user);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9\p{L}]/u', '', $user);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
#$user = $PHP_AUTH_USER;
$US='_';
# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$stmt="SELECT selected_language,qc_enabled from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$qc_auth =					$row[1];
	}

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

$stmt="SELECT full_name,modify_lists,user_level,modify_users,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =			$row[0];
$LOGmodify_lists =		$row[1];
$LOGuser_level =		$row[2];
$LOGmodify_users =		$row[3];
$LOGuser_group =		$row[4];

if ( ($LOGuser_level < 8) or ($LOGmodify_users < 1) )
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify users").": $LOGmodify_users|$LOGuser_level\n";
	exit;
	}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
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



if (strlen($user) < 1)
	{
	echo _QXZ("ERROR: user not defined:");
	exit;
	}

?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: User Inbound Calls Today"); ?>
</title></head><body bgcolor=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<?php 

##### BEGIN Set variables to make header show properly #####
$short_header=1;

require("admin_header.php");


if ($DB > 0)
	{
	echo "$DB,$action,$user,$user_override,$active\n<BR>";
	}



################################################################################
##### BEGIN USER LIST NEW control form
if (strlen($user) < 1)
	{
	echo _QXZ("ERROR: no user defined",0,'')."\n";
	exit;
	}
$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';
echo "<TABLE><TR><TD>\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
echo "<center><TABLE width=$section_width cellspacing=3>\n";
echo "<tr><td align=center colspan=2>\n";
$FORM_list_id='';
$FORM_user='';
if ($user == '---ALL---')
	{
	$userSQL = "user!=''";
	echo "<br><b>"._QXZ("User Inbound Calls Today ALL USERS",0,'')."</b> &nbsp; $NWB#user_inbound_calls_today$NWE\n";
	}
else
	{
	$userSQL = "user='$user'";
	}
echo "<form action=$PHP_SELF method=POST>\n";
echo "<input type=hidden name=DB value=\"$DB\">\n";
echo "<input type=hidden name=action value=USER_INBOUND_MODIFY>\n";
echo "<input type=hidden name=user value=\"$user\">\n";

$Ruser = array();
$Rfull_name = array();
$Ruser_group = array();
$Rinbound_credits = array();
$Rmax_inbound_calls = array();
$Rmax_inbound_filter_enabled = array();

### Gather all users to be displayed
$stmt="SELECT user,full_name,user_group,inbound_credits,max_inbound_calls,max_inbound_filter_enabled from vicidial_users where $userSQL $LOGadmin_viewable_groupsSQL order by user limit 10000;";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$users_to_print = mysqli_num_rows($rslt);
$i=0;
while ($users_to_print > $i) 
	{
	$rowx=mysqli_fetch_row($rslt);			
	$Ruser[$i] =						$rowx[0];
	$Rfull_name[$i] =					$rowx[1];
	$Ruser_group[$i] =					$rowx[2];
	$Rinbound_credits[$i] =				$rowx[3];
	$Rmax_inbound_calls[$i] =			$rowx[4];
	$Rmax_inbound_filter_enabled[$i] =	$rowx[5];
	$i++;
	}

### Go through each user and gather inbound call stats
$notes_ct=0;
$rows_output='';
$i=0;
while ($users_to_print > $i) 
	{
	$user_notes='';
	$ti = ($i + 1);
	$stmt="SELECT sum(calls_today),sum(calls_today_filtered) from vicidial_inbound_group_agents where user='$Ruser[$i]';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$ranks_to_print = mysqli_num_rows($rslt);
	if ($ranks_to_print > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$calls_today =			$row[0];
		$calls_today_filtered =	$row[1];
		}
	else
		{$calls_today=0;   $calls_today_filtered=0;}

	$max_limit_trigger=0;
	if ( ($Rmax_inbound_filter_enabled[$i] > 0) and ($calls_today_filtered >= $Rmax_inbound_calls[$i]) and ($Rmax_inbound_calls[$i] > 0) )
		{
		$max_limit_trigger++;
		if (strlen($user_notes) > 0) {$user_notes .= "<BR>";}
		$user_notes .= "MAX INBOUND FILTERED HIT";
		}
	if ( ($max_limit_trigger < 1) and ($calls_today >= $Rmax_inbound_calls[$i]) and ($Rmax_inbound_calls[$i] > 0) )
		{
		$max_limit_trigger++;
		if (strlen($user_notes) > 0) {$user_notes .= "<BR>";}
		$user_notes .= "MAX INBOUND HIT";
		}
	if ( ($Rinbound_credits[$i] == 0) and ($SSinbound_credits > 0) )
		{
		$max_limit_trigger++;
		if (strlen($user_notes) > 0) {$user_notes .= "<BR>";}
		$user_notes .= "ZERO INBOUND CREDITS";
		}
	if ($max_limit_trigger > 0) {$notes_ct++;}

	if (strlen($user_notes) > 0) {$user_notes = "<font color=red><b>$user_notes</b></font>";}
	if ($Rmax_inbound_calls[$i] == 0) {$Rmax_inbound_calls[$i] = "<font size=1>("._QXZ("disabled").")</font>";}
	if ($Rinbound_credits[$i] == -1) {$Rinbound_credits[$i] = "<font size=1>("._QXZ("disabled").")</font>";}

	$bgcolor = "bgcolor='#$SSstd_row3_background'";
	if (preg_match("/1$|3$|5$|7$|9$/",$i)) {$bgcolor = "bgcolor='#$SSstd_row4_background'";}

	$rows_output .= "<tr $bgcolor>";
	$rows_output .= "<td><font size=1>$ti</font></td>";
	$rows_output .= "<td><font size=2><a href=\"admin.php?ADD=3&user=$Ruser[$i]\">$Ruser[$i]</a> - $Rfull_name[$i]</font></td>";
	$rows_output .= "<td><font size=2>$Ruser_group[$i]</td>";
	$rows_output .= "<td align=right>$calls_today</td>";
	$rows_output .= "<td align=right>$calls_today_filtered</td>";
	$rows_output .= "<td align=right>$Rmax_inbound_calls[$i]</td>";
	$rows_output .= "<td align=right>$Rinbound_credits[$i]</td>";
	$rows_output .= "<td><font size=2>$user_notes</td>";
	$rows_output .= "</tr>\n";

	$i++;
	}

$credits_message='';
if ($SSinbound_credits < 1) {$credits_message = "<font size=1>("._QXZ("disabled").")</font>";}
echo "<TABLE width=1080 cellspacing=3>\n";
echo "<tr><td>#</td><td><font size=2><b>"._QXZ("USER")."</td><td><font size=2><b>"._QXZ("USER GROUP")."</td><td NOWRAP COLSPAN=2><font size=2><b>"._QXZ("INBOUND CALLS TODAY - filtered")."</td><td><font size=2><b>"._QXZ("MAX INBOUND CALLS")."</td><td><font size=2><b>"._QXZ("INBOUND CREDITS")." $credits_message</td><td><font size=2><b>"._QXZ("NOTES")." ($notes_ct)</td></tr>\n";

echo "$rows_output";

#echo "<tr bgcolor=white>";
#echo "<td colspan=7 align=center><input type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'></td>";
#echo "</tr>\n";

echo "</table></center><br>\n";
echo "</form>\n";

echo "</td></tr>\n";

echo "<TABLE width=700 cellspacing=3>\n";
echo "<tr $bgcolor>";
echo "</tr>\n";
echo "</table></center><br>\n";

echo "</TABLE>\n";

echo "</center>\n";
echo "</TD></TR></TABLE>\n";
### END USER LIST NEW control form


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").": $RUNtime "._QXZ("seconds")." &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

?>

</body>
</html>
