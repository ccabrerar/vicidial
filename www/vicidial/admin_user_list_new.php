<?php
# admin_user_list_new.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen will control the optional user list new limit override settings 
#
#
# changes:
# 161012-1327 - First Build
# 161031-1500 - Added overall user option
# 170409-1546 - Added IP List validation code
# 180503-2215 - Added new help display
#

$admin_version = '2.14-3';
$build = '170409-1546';

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
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["user_override"]))			{$user_override=$_GET["user_override"];}
	elseif (isset($_POST["user_override"]))	{$user_override=$_POST["user_override"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}

if (strlen($action) < 2)
	{$action = 'BLANK';}
if (strlen($DB) < 1)
	{$DB=0;}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,enable_languages,language_method,qc_features_active,user_territories_active,user_new_lead_limit FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$user = preg_replace('/[^-_0-9a-zA-Z]/','',$user);
	$list_id = preg_replace('/[^-_0-9a-zA-Z]/','',$list_id);
	$user_override = preg_replace('/[^-0-9]/','',$user_override);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
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

if ( ($LOGmodify_lists < 1) or ($LOGmodify_users < 1) )
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify lists and users").": $LOGmodify_lists|$LOGmodify_users\n";
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



##### BEGIN Set variables to make header show properly #####
$ADD =					'311';
$SUB =					'1';
$hh =					'lists';
$sh =					'newlimit';
$LOGast_admin_access =	'1';
$SSoutbound_autodial_active = '1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$campaigns_color =		'#FFFF99';
$campaigns_font =		'BLACK';
$campaigns_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
$ingroups_font =	'BLACK';
$ingroups_color =	'#E6E6E6';
$lists_font =		'BLACK';
$lists_color =		'#E6E6E6';
##### END Set variables to make header show properly #####
if ( ($stage == 'overall') and ($list_id == 'NONE') )
	{
	$ADD =					'3';
	$SUB =					'1';
	$hh =					'users';
	}

if (strlen($list_id) < 1)
	{
	echo _QXZ("ERROR: list not defined:");
	exit;
	}
if (strlen($user) < 1)
	{
	echo _QXZ("ERROR: user not defined:");
	exit;
	}

$stmt_name="SELECT list_name from vicidial_lists where list_id='$list_id';";
$mod_link = "ADD=$ADD&list_id=";
$event_section = 'LISTS';

$camp_name='';
$rslt=mysql_to_mysqli($stmt_name, $link);
$names_to_print = mysqli_num_rows($rslt);
if ($names_to_print > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$camp_name = $row[0];
	}

?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: User List New Lead Limits"); ?>
<?php 


require("admin_header.php");


if ($DB > 0)
	{
	echo "$DB,$action,$list_id,$user,$user_override,$active\n<BR>";
	}



################################################################################
##### BEGIN USER LIST NEW control form
if ( ($list_id == '---ALL---') and ($user == '---ALL---') )
	{
	echo _QXZ("ERROR: you must select a list or user",0,'')."\n";
	exit;
	}
$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';
echo "<TABLE><TR><TD>\n";
echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
echo "<br>"._QXZ("User List New Limits Form");
echo "<center><TABLE width=$section_width cellspacing=3>\n";
echo "<tr><td align=center colspan=2>\n";
$FORM_list_id='';
$FORM_user='';
if ( ($stage == 'overall') and ($list_id == 'NONE') )
	{
	echo "<br><b>"._QXZ("User Overall New Limit Entries ALL USERS",0,'')."</b> &nbsp; $NWB#user_list_new_limits$NWE\n";
	}
else
	{
	if ($list_id != '---ALL---') 
		{
		$FORM_list_id = $list_id;
		echo "<br><b>"._QXZ("User List New Limit Entries LIST",0,'').": <a href=\"./admin.php?$mod_link$list_id\">$list_id</a></b> &nbsp; $NWB#user_list_new_limits$NWE\n";
		}
	if ($user != '---ALL---') 
		{
		$FORM_user = $user;
		echo "<br><b>"._QXZ("User List New Limit Entries USER",0,'').": <a href=\"./admin.php?ADD=3&user=$user\">$user</a></b> &nbsp; $NWB#user_list_new_limits$NWE\n";
		}
	}
echo "<form action=$PHP_SELF method=POST>\n";
echo "<input type=hidden name=DB value=\"$DB\">\n";
echo "<input type=hidden name=action value=USER_LIST_NEW_MODIFY>\n";
echo "<input type=hidden name=user value=\"$user\">\n";
echo "<input type=hidden name=list_id value=\"$list_id\">\n";
if ($stage == 'overall')
	{echo "<input type=hidden name=stage value=\"overall\">\n";}
echo "<TABLE width=740 cellspacing=3>\n";
if ($stage == 'overall')
	{echo "<tr><td>#</td><td>"._QXZ("USER")."</td><td>"._QXZ("NEW LIMIT")."</td><td>"._QXZ("TODAY")."</td></tr>\n";}
else
	{echo "<tr><td>#</td><td>"._QXZ("USER")."</td><td>"._QXZ("LIST")."</td><td>"._QXZ("ACTIVE")."</td><td>"._QXZ("CAMPAIGN")."</td><td>"._QXZ("NEW LIMIT")."</td><td>"._QXZ("TODAY")."</td></tr>\n";}

$list_idSQL = "list_id='$list_id'";
if ( ($list_id == '---ALL---') or ($list_id == 'NONE') ) {$list_idSQL = "list_id != 0";}
$userSQL = "user='$user'";
if ($user == '---ALL---') {$userSQL = "user!=''";}

###############################################################################
##### BEGIN run for one user, all lists #####
###############################################################################
else
	{
	$changesLINK="admin.php?ADD=720000000000000&category=USERS&stage=$user";

	$stmt="SELECT user,full_name,user_group from vicidial_users where $userSQL $LOGadmin_viewable_groupsSQL order by user limit 10000;";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$users_to_print = mysqli_num_rows($rslt);
	if ($users_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);			
		$Ruser =			$rowx[0];
		$Rfull_name =		$rowx[1];
		$Ruser_group =		$rowx[2];

		$stmt="SELECT list_id,list_name,campaign_id,user_new_lead_limit,active from vicidial_lists where $list_idSQL $LOGallowed_campaignsSQL order by campaign_id,list_id limit 10000;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$lists_to_print = mysqli_num_rows($rslt);
		$o=0;
		while ($lists_to_print > $o) 
			{
			$rowx=mysqli_fetch_row($rslt);			
			$Rlist_id[$o] =				$rowx[0];
			$Rlist_name[$o] =			$rowx[1];
			$Rcampaign[$o] =			$rowx[2];
			$Ruser_new_lead_limit[$o] =	$rowx[3];
			$Ractive[$o] =				$rowx[4];

			$o++;
			}
		$rollingSQL_log='';
		$vulnl_updates=0;
		$vulnl_inserts=0;
		$vulnl_nochng=0;
		$o=0;
		while ($lists_to_print > $o) 
			{
			### BEGIN modification of vicidial_user_list_new_lead record ###
			if ($action == "USER_LIST_NEW_MODIFY")
				{
				$Ruser_override='-1';
				$vulnl_found=0;
				$stmt="SELECT user_override from vicidial_user_list_new_lead where user='$Ruser' and list_id='$Rlist_id[$o]';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$entries_to_print = mysqli_num_rows($rslt);
				if ($entries_to_print > 0) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$Ruser_override =	$rowx[0];
					$vulnl_found++;;
					}
				if (isset($_GET["$Ruser$US$Rlist_id[$o]"]))						{$Ruser_overrideNEW=$_GET["$Ruser$US$Rlist_id[$o]"];}
					elseif (isset($_POST["$Ruser$US$Rlist_id[$o]"]))			{$Ruser_overrideNEW=$_POST["$Ruser$US$Rlist_id[$o]"];}
				if ( ($Ruser_overrideNEW == $Ruser_override) or (strlen($Ruser_overrideNEW) < 1) )
					{
					$vulnl_nochng++;
					if ($DB) {echo "NO CHANGE: |$Ruser$US$Rlist_id[$o]|$Ruser_overrideNEW|$Ruser_override|\n";}
					}
				else
					{
					if ($vulnl_found > 0)
						{
						$stmt="UPDATE vicidial_user_list_new_lead SET user_override='$Ruser_overrideNEW' where list_id='$Rlist_id[$o]' and user='$Ruser';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$affected_rows = mysqli_affected_rows($link);
						if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
						if ($affected_rows > 0)
							{
							$rollingSQL_log .= "$stmt|";
							$vulnl_updates++;
							}
						else
							{echo _QXZ("ERROR: Problem modifying USER LIST NEW LIMIT").":  $affected_rows|$stmt\n<BR>";}
						}
					else
						{
						$stmt="INSERT INTO vicidial_user_list_new_lead SET list_id='$Rlist_id[$o]',user_override='$Ruser_overrideNEW',user='$Ruser';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$affected_rows = mysqli_affected_rows($link);
						if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
						if ($affected_rows > 0)
							{
							$rollingSQL_log .= "$stmt|";
							$vulnl_inserts++;
							}
						else
							{echo _QXZ("ERROR: Problem modifying USER LIST NEW LIMIT").":  $affected_rows|$stmt\n<BR>";}
						}
					}
				}
			### END modification of vicidial_user_list_new_lead record ###

			$Ruser_override='-1';
			$Rnew_count=0;
			$stmt="SELECT user_override,new_count from vicidial_user_list_new_lead where user='$Ruser' and list_id='$Rlist_id[$o]';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$entries_to_print = mysqli_num_rows($rslt);
			if ($entries_to_print > 0) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$Ruser_override =	$rowx[0];
				$Rnew_count =		$rowx[1];
				}

			if (preg_match('/1$|3$|5$|7$|9$/i', $o))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$ct = ($o + 1);
			echo "<tr $bgcolor>";
			echo "<td><font size=1>$ct</td>";
			echo "<td><font size=1><a href=\"admin.php?ADD=3&user=$Ruser\">$Ruser</a> - $Rfull_name</td>";
			echo "<td><font size=1><a href=\"admin.php?ADD=311&list_id=$Rlist_id[$o]\">$Rlist_id[$o]</a> - $Rlist_name[$o]</td>";
			echo "<td><font size=1>$Ractive[$o]</td>";
			echo "<td><font size=1><a href=\"admin.php?ADD=34&campaign_id=$Rcampaign[$o]\">$Rcampaign[$o]</a></td>";
			echo "<td><font size=1><input type=text size=4 maxlength=4 name=$Ruser$US$Rlist_id[$o] value=\"$Ruser_override\"></td>";
			echo "<td><font size=1>$Rnew_count</td>";
			echo "</tr>\n";

			$o++;
			}

		if ($action == "USER_LIST_NEW_MODIFY")
			{
			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$rollingSQL_log|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$user', event_code='MODIFY USER LIST NEW LIMIT ENTRIES', event_sql=\"$SQL_log\", event_notes='$user|$o|NOCHANGE: $vulnl_nochng|UPDATES: $vulnl_updates|INSERTS: $vulnl_inserts|';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB > 0) {echo "$user|$o|$stmt\n<BR>";}

			echo "<BR><b>$o "._QXZ("USER LIST NEW LIMIT ENTRIES MODIFIED")." (ID: $user) $o</b><BR><BR>";
			}
		}
	}
###############################################################################
##### END run for one user, all lists #####
###############################################################################





###############################################################################
##### BEGIN run for one list, all users #####
###############################################################################
if ( ($list_id != '---ALL---') and ($list_id != 'NONE') )
	{
	$changesLINK="admin.php?ADD=720000000000000&category=LISTS&stage=$list_id";

	$stmt="SELECT list_id,list_name,campaign_id,user_new_lead_limit,active from vicidial_lists where $list_idSQL $LOGallowed_campaignsSQL order by campaign_id,list_id limit 10000;";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$lists_to_print = mysqli_num_rows($rslt);
	if ($lists_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);			
		$Rlist_id =				$rowx[0];
		$Rlist_name =			$rowx[1];
		$Rcampaign =			$rowx[2];
		$Ruser_new_lead_limit =	$rowx[3];
		$Ractive =				$rowx[4];

		$stmt="SELECT user,full_name,user_group from vicidial_users where active='Y' and $userSQL $LOGadmin_viewable_groupsSQL order by user limit 10000;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$users_to_print = mysqli_num_rows($rslt);
		$o=0;
		while ($users_to_print > $o) 
			{
			$rowx=mysqli_fetch_row($rslt);			
			$Ruser[$o] =			$rowx[0];
			$Rfull_name[$o] =		$rowx[1];
			$Ruser_group[$o] =		$rowx[2];

			$o++;
			}
		$rollingSQL_log='';
		$vulnl_updates=0;
		$vulnl_inserts=0;
		$vulnl_nochng=0;
		$o=0;
		while ($users_to_print > $o) 
			{
			### BEGIN modification of vicidial_user_list_new_lead record ###
			if ($action == "USER_LIST_NEW_MODIFY")
				{
				$Ruser_override='-1';
				$vulnl_found=0;
				$stmt="SELECT user_override from vicidial_user_list_new_lead where user='$Ruser[$o]' and list_id='$Rlist_id';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$entries_to_print = mysqli_num_rows($rslt);
				if ($entries_to_print > 0) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$Ruser_override =	$rowx[0];
					$vulnl_found++;;
					}
				if (isset($_GET["$Ruser[$o]$US$Rlist_id"]))						{$Ruser_overrideNEW=$_GET["$Ruser[$o]$US$Rlist_id"];}
					elseif (isset($_POST["$Ruser[$o]$US$Rlist_id"]))			{$Ruser_overrideNEW=$_POST["$Ruser[$o]$US$Rlist_id"];}
				if ( ($Ruser_overrideNEW == $Ruser_override) or (strlen($Ruser_overrideNEW) < 1) )
					{
					$vulnl_nochng++;
					if ($DB) {echo "NO CHANGE: |$Ruser[$o]$US$Rlist_id|$Ruser_overrideNEW|$Ruser_override|\n";}
					}
				else
					{
					if ($vulnl_found > 0)
						{
						$stmt="UPDATE vicidial_user_list_new_lead SET user_override='$Ruser_overrideNEW' where list_id='$Rlist_id' and user='$Ruser[$o]';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$affected_rows = mysqli_affected_rows($link);
						if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
						if ($affected_rows > 0)
							{
							$rollingSQL_log .= "$stmt|";
							$vulnl_updates++;
							}
						else
							{echo _QXZ("ERROR: Problem modifying USER LIST NEW LIMIT").":  $affected_rows|$stmt\n<BR>";}
						}
					else
						{
						$stmt="INSERT INTO vicidial_user_list_new_lead SET list_id='$Rlist_id',user_override='$Ruser_overrideNEW',user='$Ruser[$o]';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$affected_rows = mysqli_affected_rows($link);
						if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
						if ($affected_rows > 0)
							{
							$rollingSQL_log .= "$stmt|";
							$vulnl_inserts++;
							}
						else
							{echo _QXZ("ERROR: Problem modifying USER LIST NEW LIMIT").":  $affected_rows|$stmt\n<BR>";}
						}
					}
				}
			### END modification of vicidial_user_list_new_lead record ###

			$Ruser_override='-1';
			$Rnew_count=0;
			$stmt="SELECT user_override,new_count from vicidial_user_list_new_lead where user='$Ruser[$o]' and list_id='$Rlist_id';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$entries_to_print = mysqli_num_rows($rslt);
			if ($entries_to_print > 0) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$Ruser_override =	$rowx[0];
				$Rnew_count =		$rowx[1];
				}

			if (preg_match('/1$|3$|5$|7$|9$/i', $o))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

			$ct = ($o + 1);
			echo "<tr $bgcolor>";
			echo "<td><font size=1>$ct</td>";
			echo "<td><font size=1><a href=\"admin.php?ADD=3&user=$Ruser[$o]\">$Ruser[$o]</a> - $Rfull_name[$o]</td>";
			echo "<td><font size=1><a href=\"admin.php?ADD=311&list_id=$Rlist_id\">$Rlist_id</a> - $Rlist_name</td>";
			echo "<td><font size=1>$Ractive</td>";
			echo "<td><font size=1><a href=\"admin.php?ADD=34&campaign_id=$Rcampaign\">$Rcampaign</a></td>";
			echo "<td><font size=1><input type=text size=4 maxlength=4 name=$Ruser[$o]$US$Rlist_id value=\"$Ruser_override\"></td>";
			echo "<td><font size=1>$Rnew_count</td>";
			echo "</tr>\n";

			$o++;
			}

		if ($action == "USER_LIST_NEW_MODIFY")
			{
			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$rollingSQL_log|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='MODIFY', record_id='$list_id', event_code='MODIFY USER LIST NEW LIMIT ENTRIES', event_sql=\"$SQL_log\", event_notes='$list_id|$o|NOCHANGE: $vulnl_nochng|UPDATES: $vulnl_updates|INSERTS: $vulnl_inserts|';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB > 0) {echo "$user|$o|$stmt\n<BR>";}

			echo "<BR><b>$o "._QXZ("USER LIST NEW LIMIT ENTRIES MODIFIED")." (ID: $list_id) $o</b><BR><BR>";
			}
		}
	}
###############################################################################
##### END run for one list, all users #####
###############################################################################





###############################################################################
##### BEGIN run for all users overall limits #####
###############################################################################
if ($list_id == 'NONE')
	{
	$changesLINK="admin.php?ADD=720000000000000&category=LISTS&stage=$list_id";

	$stmt="SELECT user,full_name,user_group from vicidial_users where active='Y' $LOGadmin_viewable_groupsSQL order by user limit 10000;";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$users_to_print = mysqli_num_rows($rslt);
	$o=0;
	while ($users_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);			
		$Ruser[$o] =			$rowx[0];
		$Rfull_name[$o] =		$rowx[1];
		$Ruser_group[$o] =		$rowx[2];

		$o++;
		}
	$rollingSQL_log='';
	$vulnl_updates=0;
	$vulnl_inserts=0;
	$vulnl_nochng=0;
	$o=0;
	while ($users_to_print > $o) 
		{
		### BEGIN modification of vicidial_users record ###
		if ($action == "USER_LIST_NEW_MODIFY")
			{
			$Ruser_override='-1';
			$vulnl_found=0;
			$stmt="SELECT user_new_lead_limit from vicidial_users where user='$Ruser[$o]';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$entries_to_print = mysqli_num_rows($rslt);
			if ($entries_to_print > 0) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$Ruser_override =	$rowx[0];
				$vulnl_found++;;
				}
			if (isset($_GET["$Ruser[$o]$US$Rlist_id"]))						{$Ruser_overrideNEW=$_GET["$Ruser[$o]$US$Rlist_id"];}
				elseif (isset($_POST["$Ruser[$o]$US$Rlist_id"]))			{$Ruser_overrideNEW=$_POST["$Ruser[$o]$US$Rlist_id"];}
			if ( ($Ruser_overrideNEW == $Ruser_override) or (strlen($Ruser_overrideNEW) < 1) )
				{
				$vulnl_nochng++;
				if ($DB) {echo "NO CHANGE: |$Ruser[$o]$US$Rlist_id|$Ruser_overrideNEW|$Ruser_override|\n";}
				}
			else
				{
				if ($vulnl_found > 0)
					{
					$stmt="UPDATE vicidial_users SET user_new_lead_limit='$Ruser_overrideNEW' where user='$Ruser[$o]';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$affected_rows = mysqli_affected_rows($link);
					if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
					if ($affected_rows > 0)
						{
						$rollingSQL_log .= "$stmt|";
						$vulnl_updates++;
						}
					else
						{echo _QXZ("ERROR: Problem modifying USER OVERALL NEW LIMIT").":  $affected_rows|$stmt\n<BR>";}
					}
				else
					{
					echo _QXZ("ERROR: Problem modifying USER OVERALL NEW LIMIT").":  $vulnl_found|$stmt\n<BR>";
					}
				}
			}
		### END modification of vicidial_user_list_new_lead record ###

		$Ruser_override='-1';
		$Rnew_count=0;
		$stmt="SELECT sum(new_count) from vicidial_user_list_new_lead where user='$Ruser[$o]';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$entries_to_print = mysqli_num_rows($rslt);
		if ($entries_to_print > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$Rnew_count =		$rowx[0];
			if (strlen($Rnew_count)<1) {$Rnew_count=0;}
			}
		$stmt="SELECT user_new_lead_limit from vicidial_users where user='$Ruser[$o]';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$entries_to_print = mysqli_num_rows($rslt);
		if ($entries_to_print > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$Ruser_override =	$rowx[0];
			}

		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
		else
			{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}

		$ct = ($o + 1);
		echo "<tr $bgcolor>";
		echo "<td><font size=1>$ct</td>";
		echo "<td><font size=1><a href=\"admin.php?ADD=3&user=$Ruser[$o]\">$Ruser[$o]</a> - $Rfull_name[$o]</td>";
		echo "<td><font size=1><input type=text size=4 maxlength=4 name=$Ruser[$o]$US$Rlist_id value=\"$Ruser_override\"></td>";
		echo "<td><font size=1>$Rnew_count</td>";
		echo "</tr>\n";

		$o++;
		}

	if ($action == "USER_LIST_NEW_MODIFY")
		{
		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$rollingSQL_log|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='MODIFY', record_id='$list_id', event_code='MODIFY USER LIST NEW LIMIT ENTRIES', event_sql=\"$SQL_log\", event_notes='$list_id|$o|NOCHANGE: $vulnl_nochng|UPDATES: $vulnl_updates|INSERTS: $vulnl_inserts|';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 0) {echo "$user|$o|$stmt\n<BR>";}

		echo "<BR><b>$o "._QXZ("USER OVERALL NEW LIMIT ENTRIES MODIFIED")." (ID: $list_id) $o</b><BR><BR>";
		}
	}
###############################################################################
##### END run for all users overall limits #####
###############################################################################



echo "<tr bgcolor=white>";
echo "<td colspan=7 align=center><input type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'></td>";
echo "</tr>\n";

echo "</table></center><br>\n";
echo "</form>\n";

echo "</td></tr>\n";

echo "<TABLE width=700 cellspacing=3>\n";
echo "<tr $bgcolor>";
echo "</tr>\n";
echo "</table></center><br>\n";

echo "</TABLE>\n";


if ( ($LOGuser_level >= 9) and ( (preg_match("/Administration Change Log/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) ) )
	{
	echo "<br><br><font face=\"Arial,Helvetica\"><a href=\"$changesLINK\">"._QXZ("Click here to see Admin changes to this record")."</font>\n";
	}

echo "</center>\n";
echo "</TD></TR></TABLE>\n";
### END USER LIST NEW control form


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").": $RUNtime "._QXZ("seconds")." &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

?>

</body>
</html>
