<?php
# callbacks_bulk_move.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 150218-0923 - First build based on callbacks_bulk_change.php
# 151025-1041 - Fixed issue with check-all
# 161105-0056 - Added options to purge uncalled callbacks, and also to revert callbacks to their most recent non-callback-dispo based on the callback entry time and the log tables.
# 170409-1548 - Added IP List validation code
# 180508-0115 - Added new help display
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["days"]))				{$days=$_GET["days"];}
	elseif (isset($_POST["days"]))		{$days=$_POST["days"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["list_id"]))			{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))	{$list_id=$_POST["list_id"];}
if (isset($_GET["campaign_id"]))			{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))	{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
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

if (isset($_GET["new_list_id"]))			{$new_list_id=$_GET["new_list_id"];}
	elseif (isset($_POST["new_list_id"]))	{$new_list_id=$_POST["new_list_id"];}
if (isset($_GET["new_status"]))				{$new_status=$_GET["new_status"];}
	elseif (isset($_POST["new_status"]))	{$new_status=$_POST["new_status"];}
if (isset($_GET["cb_groups"]))			{$cb_groups=$_GET["cb_groups"];}
	elseif (isset($_POST["cb_groups"]))	{$cb_groups=$_POST["cb_groups"];}
if (isset($_GET["cb_user_groups"]))			{$cb_user_groups=$_GET["cb_user_groups"];}
	elseif (isset($_POST["cb_user_groups"]))	{$cb_user_groups=$_POST["cb_user_groups"];}
if (isset($_GET["cb_lists"]))			{$cb_lists=$_GET["cb_lists"];}
	elseif (isset($_POST["cb_lists"]))	{$cb_lists=$_POST["cb_lists"];}
if (isset($_GET["cb_users"]))			{$cb_users=$_GET["cb_users"];}
	elseif (isset($_POST["cb_users"]))	{$cb_users=$_POST["cb_users"];}
if (isset($_GET["days_uncalled"]))			{$days_uncalled=$_GET["days_uncalled"];}
	elseif (isset($_POST["days_uncalled"]))	{$days_uncalled=$_POST["days_uncalled"];}
if (isset($_GET["purge_called_records"]))			{$purge_called_records=$_GET["purge_called_records"];}
	elseif (isset($_POST["purge_called_records"]))	{$purge_called_records=$_POST["purge_called_records"];}
if (isset($_GET["purge_uncalled_records"]))			{$purge_uncalled_records=$_GET["purge_uncalled_records"];}
	elseif (isset($_POST["purge_uncalled_records"]))	{$purge_uncalled_records=$_POST["purge_uncalled_records"];}
if (isset($_GET["revert_status"]))			{$revert_status=$_GET["revert_status"];}
	elseif (isset($_POST["revert_status"]))	{$revert_status=$_POST["revert_status"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,enable_languages,language_method,qc_features_active FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$SSenable_languages =			$row[3];
	$SSlanguage_method =			$row[4];
	$SSqc_features_active =			$row[5];
	}
##### END SETTINGS LOOKUP #####
###########################################

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

$StarTtimE = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$ip = getenv("REMOTE_ADDR");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

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

$category='';
$record_id='';
if (strlen($days) < 1)			{$days='2';}
if (strlen($user) < 1)			{$category='USERS';			$record_id=$user;}
if (strlen($user_group) < 1)	{$category='USERGROUPS';	$record_id=$user_group;}
if (strlen($list_id) < 1)		{$category='LISTS';			$record_id=$list_id;}
if (strlen($campaign_id) < 1)	{$category='CAMPAIGNS';		$record_id=$campaign_id;}

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
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
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


$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	$group_string .= "$group[$i]|";
	$i++;
	}

$i=0;
$users_string='|';
$users_ct = count($users);
while($i < $users_ct)
	{
	$users_string .= "$users[$i]|";
	$i++;
	}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $users_ct)
	{
	$user_group_string .= "$user_group[$i]|";
	$i++;
	}

$stmt="SELECT campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$groups[$i] =$row[0];
	#if (preg_match('/\-ALL/',$group_string) )
	#	{$group[$i] = $groups[$i];}
	$i++;
	}

$stmt="SELECT user_group from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$user_groups_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $user_groups_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_groups[$i] =$row[0];
	# if (preg_match('/\-ALL/',$user_group_string)) {$user_group[$i]=$row[0];}
	$i++;
	}

$stmt="SELECT user, full_name from vicidial_users $whereLOGadmin_viewable_groupsSQL order by user";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$users_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $users_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$user_list[$i]=$row[0];
	$user_names[$i]=$row[1];
	if ($all_users) {$user_list[$i]=$row[0];}
	$i++;
	}


$i=0;
$user_string='|';
$user_ct = count($users);
while($i < $user_ct)
	{
	$user_string .= "$users[$i]|";
#	$user_SQL .= "'$users[$i]',";
	$user_SQL .= " selected_agents like '%|".$users[$i]."|%' or ";
	$userQS .= "&users[]=$users[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_string) ) or ($user_ct < 1) )
	{$user_SQL = "";}
else
	{
	# $user_string=preg_replace("/^\||\|$/", "", $user_string);
	$user_SQL = preg_replace('/ or $/i', '',$user_SQL);
#	$user_agent_log_SQL = "and vicidial_agent_log.user IN($user_SQL)";
	}

$i=0;
$user_group_string='|';
$user_group_ct = count($user_group);
while($i < $user_group_ct)
	{
	$user_group_string .= "$user_group[$i]|";
#	$user_group_SQL .= "'$user_group[$i]',";
	$user_group_SQL .= " selected_user_groups like '%|".$user_group[$i]."|%' or ";
	$user_groupQS .= "&user_group[]=$user_group[$i]";
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$user_group_string) ) or ($user_group_ct < 1) )
	{$user_group_SQL = "";}
else
	{
	# $user_group_string=preg_replace("/^\||\|$/", "", $user_group_string);
	$user_group_SQL = preg_replace('/ or $/i', '',$user_group_SQL);
#	$user_group_agent_log_SQL = "and vicidial_agent_log.user_group IN($user_group_SQL)";
	$user_group_SQL = "and ($user_group_SQL or selected_user_groups='|')";
	}

$i=0;
$group_string='|';
$group_ct = count($group);
while($i < $group_ct)
	{
	if ( (preg_match("/ $group[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
		{
		$group_string .= "$group[$i]|";
#		$group_SQL .= "'$group[$i]',";
		$group_SQL .= " selected_campaigns like '%|".$group[$i]."|%' or ";
		$groupQS .= "&group[]=$group[$i]";
		}
	$i++;
	}
if ( (preg_match('/\-\-ALL\-\-/',$group_string) ) or ($group_ct < 1) )
	{$group_SQL = "";}
else
	{
	# $group_string=preg_replace("/^\||\|$/", "", $group_string);
	$group_SQL = preg_replace('/ or $/i', '',$group_SQL);
	$group_SQL = "and ($group_SQL or selected_campaigns='|')";
	}

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

### PRELOAD ###
$preload_campaigns=array();
$preload_lists=array();
$preload_user_groups=array();
$preload_users=array();
if ($campaign_id) 
	{
	# Trying a three-level UNION statement to see if that's faster
	$stmt="SELECT distinct campaign_id, 'CAMPAIGN' as dtype FROM vicidial_callbacks where campaign_id='$campaign_id' UNION SELECT distinct user_group, 'USER_GROUP' as dtype FROM vicidial_callbacks where campaign_id='$campaign_id' UNION SELECT distinct list_id, 'LIST_ID' as dtype from vicidial_callbacks where campaign_id='$campaign_id' UNION SELECT distinct user, 'USER' as dtype from vicidial_callbacks where campaign_id='$campaign_id' order by dtype asc;";
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) 
		{
		switch ($row[1]) 
			{
			case "CAMPAIGN":
				array_push($preload_campaigns, "$row[0]");
				break;
			case "USER_GROUP":
				array_push($preload_user_groups, "$row[0]");
				break;
			case "USER":
				array_push($preload_users, "$row[0]");
				break;
			case "LIST_ID":
				array_push($preload_lists, "$row[0]");
				break;
			}
		}
	}

if ($list_id) 
	{
	$preload_campaigns=array();
	$preload_lists=array();
	$preload_user_groups=array();
	$preload_users=array();
	# Trying a three-level UNION statement to see if that's faster
	$stmt="SELECT distinct campaign_id, 'CAMPAIGN' as dtype FROM vicidial_callbacks where list_id='$list_id' UNION SELECT distinct user_group, 'USER_GROUP' as dtype FROM vicidial_callbacks where list_id='$list_id' UNION SELECT distinct list_id, 'LIST_ID' as dtype from vicidial_callbacks where list_id='$list_id' UNION SELECT distinct user, 'USER' as dtype from vicidial_callbacks where list_id='$list_id' order by dtype asc;";
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) 
		{
		switch ($row[1]) 
			{
			case "CAMPAIGN":
				array_push($preload_campaigns, "$row[0]");
				break;
			case "USER_GROUP":
				array_push($preload_user_groups, "$row[0]");
				break;
			case "USER":
				array_push($preload_users, "$row[0]");
				break;
			case "LIST_ID":
				array_push($preload_lists, "$row[0]");
				break;
			}
		}
	}

if ($user) 
	{
	$preload_campaigns=array();
	$preload_lists=array();
	$preload_user_groups=array();
	$preload_users=array();
	# Trying a three-level UNION statement to see if that's faster
	$stmt="SELECT distinct campaign_id, 'CAMPAIGN' as dtype FROM vicidial_callbacks where user='$user' UNION SELECT distinct user_group, 'USER_GROUP' as dtype FROM vicidial_callbacks where user='$user' UNION SELECT distinct list_id, 'LIST_ID' as dtype from vicidial_callbacks where user='$user' UNION SELECT distinct user, 'USER' as dtype from vicidial_callbacks where user='$user' order by dtype asc;";
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) 
		{
		switch ($row[1]) 
			{
			case "CAMPAIGN":
				array_push($preload_campaigns, "$row[0]");
				break;
			case "USER_GROUP":
				array_push($preload_user_groups, "$row[0]");
				break;
			case "USER":
				array_push($preload_users, "$row[0]");
				break;
			case "LIST_ID":
				array_push($preload_lists, "$row[0]");
				break;
			}
		}
	}

if ($user_group) 
	{
	$preload_campaigns=array();
	$preload_lists=array();
	$preload_user_groups=array();
	$preload_users=array();
	# Trying a three-level UNION statement to see if that's faster
	$stmt="SELECT distinct campaign_id, 'CAMPAIGN' as dtype FROM vicidial_callbacks where user_group='$user_group' UNION SELECT distinct user_group, 'USER_GROUP' as dtype FROM vicidial_callbacks where user_group='$user_group' UNION SELECT distinct list_id, 'LIST_ID' as dtype from vicidial_callbacks where user_group='$user_group' UNION SELECT distinct user, 'USER' as dtype from vicidial_callbacks where user_group='$user_group' order by dtype asc;";
	$rslt=mysql_to_mysqli($stmt, $link);
	while ($row=mysqli_fetch_row($rslt)) 
		{
		switch ($row[1]) 
			{
			case "CAMPAIGN":
				array_push($preload_campaigns, "$row[0]");
				break;
			case "USER_GROUP":
				array_push($preload_user_groups, "$row[0]");
				break;
			case "USER":
				array_push($preload_users, "$row[0]");
				break;
			case "LIST_ID":
				array_push($preload_lists, "$row[0]");
				break;
			}
		}
	}

###############


?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<title><?php echo _QXZ("ADMINISTRATION: Callbacks Bulk Move"); ?>
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
<TABLE WIDTH=680 BGCOLOR=<?php echo $SSmenu_background ?> cellpadding=2 cellspacing=0><TR BGCOLOR=#<?php echo $SSmenu_background ?>><TD ALIGN=LEFT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B> &nbsp; <?php echo _QXZ("Callbacks Bulk Move"); ?><?php echo "$NWB#cb-bulk$NWE"; ?></TD><TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B> &nbsp; </TD></TR>



<?php 

echo "<TR BGCOLOR=\"#".$SSstd_row1_background."\"><TD ALIGN=center COLSPAN=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3><B> &nbsp; \n";

### callbacks change form
echo "<form action=$PHP_SELF method=POST>\n";
echo "<input type=hidden name=DB value=\"$DB\">\n";




if ($SUBMIT && $new_list_id && ($new_status || $revert_status)) 
	{
	if (count($cb_users)>0) 
		{
		if (in_array("ALL", $cb_users))
			{
			$user_stmt="SELECT distinct user from vicidial_users $ul_clause $LOGadmin_viewable_groupsSQL";
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			$usersSQL=" and vc.user in (";
			$usersQS="";
			while($user_row=mysqli_fetch_row($user_rslt)) 
				{
				$usersSQL.="'$user_row[0]',";
				$usersQS.="&cb_users[]=".$user_row[0];
				}
			$usersSQL=preg_replace('/,$/', '', $usersSQL);
			$usersSQL.=") ";
			}
		else 
			{
			$usersSQL=" and vc.user in ('".implode("','", $cb_users)."') ";
			$usersQS="";
			for ($i=0; $i<count($cb_users); $i++) 
				{
				$usersQS.="&cb_users[]=".$cb_users[$i];
				}
			}
		}

	if (count($cb_user_groups)>0) 
		{
		if (in_array("ALL", $cb_user_groups)) 
			{
			$UGstmt="SELECT distinct user_group from vicidial_callbacks $whereLOGadmin_viewable_groupsSQL order by user_group";
			$UGrslt=mysql_to_mysqli($UGstmt, $link);
			$user_groupsSQL=" and vc.user_group in (";
			$user_groupsQS="";
			while($UGrow=mysqli_fetch_row($UGrslt)) 
				{
				$user_groupsSQL.="'$UGrow[0]',";
				$user_groupsQS.="&cb_user_groups[]=".$UGrow[0];
				}
			$user_groupsSQL=preg_replace('/,$/', '', $user_groupsSQL);
			$user_groupsSQL.=") ";
			}
		else 
			{
			$user_groupsSQL=" and vc.user_group in ('".implode("','", $cb_user_groups)."') ";
			$user_groupsQS="";
			for ($i=0; $i<count($cb_user_groups); $i++) 
				{
				$user_groupsQS.="&cb_user_groups[]=".$cb_user_groups[$i];
				}
			}
		}

	if (count($cb_lists)>0) 
		{
		if (in_array("ALL", $cb_lists)) 
			{
			$list_stmt="SELECT list_id from vicidial_lists $whereLOGallowed_campaignsSQL";
			$list_rslt=mysql_to_mysqli($list_stmt, $link);
			$list_id_str="";
			while($list_row=mysqli_fetch_row($list_rslt)) 
				{
				$list_id_str.="'$list_row[0]',";
				}
			$list_id_str=substr($list_id_str,0,-1);

			$list_stmt="SELECT distinct list_id from vicidial_callbacks where list_id in ($list_id_str) order by list_id";
			$list_rslt=mysql_to_mysqli($list_stmt, $link);
			$listsSQL=" and vc.list_id in (";
			$listsQS="";
			while($list_row=mysqli_fetch_row($list_rslt)) 
				{
				$listsSQL.="'$list_row[0]',";
				$listsQS.="&cb_lists[]=".$list_row[0];
				}
			$listsSQL=preg_replace('/,$/', '', $listsSQL);
			$listsSQL.=") ";
			}
		else 
			{
			$listsSQL=" and vc.list_id in ('".implode("','", $cb_lists)."') ";
			$listsQS="";
			for ($i=0; $i<count($cb_lists); $i++) 
				{
				$listsQS.="&cb_lists[]=".$cb_lists[$i];
				}
			}
		}

	if (count($cb_groups)>0) 
		{
		if (in_array("ALL", $cb_groups)) 
			{
			$groups_stmt="SELECT distinct campaign_id from vicidial_callbacks $whereLOGallowed_campaignsSQL";
			$groups_rslt=mysql_to_mysqli($groups_stmt, $link);
			$groupsSQL=" and vc.campaign_id in (";
			$groupsQS="";
			while($groups_row=mysqli_fetch_row($groups_rslt)) 
				{
				$groupsSQL.="'$groups_row[0]',";
				$groupsQS.="&cb_groups[]=".$groups_row[0];
				}
			$groupsSQL=preg_replace('/,$/', '', $groupsSQL);
			$groupsSQL.=") ";
			}
		else
			{
			$groupsSQL=" and vc.campaign_id in ('".implode("','", $cb_groups)."') ";
			$groupsQS="";
			for ($i=0; $i<count($cb_groups); $i++) 
				{
				$groupsQS.="&cb_groups[]=".$cb_groups[$i];
				}
			}
		}

	if ($days_uncalled>0 && $days_uncalled<=30) 
		{
		$daySQL=" and vc.callback_time<='".date("Y-m-d H:i:s")."'-INTERVAL $days_uncalled DAY ";
		}
#	else 
#		{}

	$callback_dispo_stmt="SELECT distinct status from vicidial_statuses where scheduled_callback='Y' UNION SELECT distinct status from vicidial_campaign_statuses where scheduled_callback='Y' ".preg_replace("/vc\./", "", $groupsSQL);
	if ($DB) {echo $callback_dispo_stmt."<BR>";}
	$callback_dispo_rslt=mysql_to_mysqli($callback_dispo_stmt, $link);
	$cb_dispos=array("CALLBK","CBHOLD");
	while ($cb_dispo_row=mysqli_fetch_row($callback_dispo_rslt)) 
		{
		array_push($cb_dispos, "$cb_dispo_row[0]");
		}
	}

$purge_trigger=0;
$callback_statuses="'LIVE'";
$purge_verbiage=array();
if ($purge_called_records) 
	{
	### DELETE INACTIVE RECORDS
	$purge_called_clause="(vc.status='INACTIVE' or (vc.status='LIVE' and vl.status not in ('".implode("','", $cb_dispos)."')))";
	$purge_trigger++;
	array_push($purge_verbiage, "CALLED", "INACTIVE");
	}
	
if ($purge_uncalled_records) 
	{
	### DELETE ACTIVE RECORDS
	$purge_uncalled_clause="(vc.status in ('ACTIVE', 'LIVE') and vl.status in ('".implode("','", $cb_dispos)."'))";
	$purge_trigger++;
	$callback_statuses.=",'ACTIVE'";
	array_push($purge_verbiage, "UNCALLED");
	}

if ($purge_trigger==1) 
	{
	$purge_clause=$purge_called_clause.$purge_uncalled_clause;
	} 
else if ($purge_trigger==2) 
	{
	$purge_clause="(".$purge_called_clause." or ".$purge_uncalled_clause.")";
	}


if ($SUBMIT && $new_list_id && ($new_status || $revert_status) && (count($cb_users)>0 || count($cb_user_groups)>0 || count($cb_lists)>0 || count($cb_groups)>0) && $confirm_transfer) 
	{
	$actual_archived_ct=0;
	$actual_purged_ct=0;
	$arch_stmts='';
	$del_stmts='';

	if ($purge_trigger>0)
		{
		$stmt = "SELECT vc.callback_id, vc.lead_id, vc.status from vicidial_callbacks vc, vicidial_list vl where $purge_clause and vc.lead_id=vl.lead_id $usersSQL $user_groupsSQL $listsSQL $groupsSQL $daySQL;";
		$rslt = mysql_to_mysqli($stmt, $link);
		$purge_ct=mysqli_num_rows($rslt); 
		
		while($row=mysqli_fetch_row($rslt)) 
			{
			$archive_stmt = "INSERT INTO vicidial_callbacks_archive SELECT * from vicidial_callbacks where callback_id='$row[0]';";
			$archive_rslt=mysql_to_mysqli($archive_stmt, $link);
			$actual_archived_ct+=mysqli_affected_rows($link);
			$arch_stmts .= " $archive_stmt";

			$delete_stmt="DELETE from vicidial_callbacks where callback_id='$row[0]';";
			$delete_rslt=mysql_to_mysqli($delete_stmt, $link);
			$actual_purged_ct+=mysqli_affected_rows($link);
			$del_stmts .= " $delete_stmt";
			}
		
			# $callback_stmt="SELECT vc.callback_id, vc.lead_id, vc.status from vicidial_callbacks vc, vicidial_list vl where vc.status='LIVE' and vl.status in ('".implode("','", $cb_dispos)."') and vc.lead_id=vl.lead_id $usersSQL $user_groupsSQL $listsSQL $groupsSQL $daySQL";
		}

	$callback_stmt="SELECT vc.callback_id, vc.lead_id, vc.status, vc.entry_time from vicidial_callbacks vc where status in ($callback_statuses) $usersSQL $user_groupsSQL $listsSQL $groupsSQL $daySQL order by callback_time asc";
	if ($DB) {echo "|$callback_stmt|\n";}

	$callback_rslt=mysql_to_mysqli($callback_stmt, $link);
	$callback_ct=mysqli_num_rows($callback_rslt);
	$actual_callback_ct=0;
	$new_listSQL="list_id='$new_list_id',";
	if ($new_list_id == 'X')
		{$new_listSQL='';}
	$upd_stmts='';
	while ($cb_row=mysqli_fetch_row($callback_rslt)) 
		{
		$lead_id=$cb_row[1];

		if ($revert_status) {
			$callback_entry_time=$cb_row[3];
			# SET UPDATE STATUS TO NEW IN CASE NO CALL HISTORY IS FOUND BEFORE LEAD WAS MARKED CALLBACK
			$new_status='NEW';
			$callback_time_clause=" and call_date<='$callback_entry_time'";
			$sort_by="desc";

			# FIND THE STATUS THE CALLBACK WAS BEFORE THE CALL THAT THE AGENT DISPO'ED IT AS, IF IT EXISTS
			$revert_stmt="select event_time, status, uniqueid from vicidial_agent_log where lead_id='$lead_id' and event_time<='$callback_entry_time' and status not in ('".implode("','", $cb_dispos)."') order by event_time desc limit 1";
			$revert_rslt=mysql_to_mysqli($revert_stmt, $link);
			if (mysqli_num_rows($revert_rslt)>0) {
				$revert_row=mysqli_fetch_row($revert_rslt);
				if ($revert_row[0]!="") {
					$callback_entry_time=$revert_row[0]; # THIS BECOMES THE MOST RECENT CALLBACK TIME TO CHECK THE CLOSER LOG FOR
					$new_status=$revert_row[1];
					$callback_time_clause="  and call_date>='$callback_entry_time' and call_date<'$cb_row[3]' ";
				}
			} 

			# FIND ANY OUTBOUND CALL MADE AFTER CALL WAS DISPO'ED BY AGENT AND NOT A CALLBACK
			$revert_stmt2="select call_date, status, uniqueid from vicidial_log where lead_id='$lead_id' $callback_time_clause and status not in ('".implode("','", $cb_dispos)."') order by call_date desc limit 1";
			$revert_rslt2=mysql_to_mysqli($revert_stmt2, $link);
			if (mysqli_num_rows($revert_rslt2)>0) {
				$revert_row2=mysqli_fetch_row($revert_rslt2);
				if ($revert_row2[0]!="") {
					$callback_entry_time=$revert_row2[0];
					$new_status=$revert_row2[1];
					$callback_time_clause="  and call_date>='$callback_entry_time' and call_date<'$cb_row[3]' ";
				}
			}

			# FIND ANY INBOUND CALL MADE AFTER CALL WAS DISPO'ED BY AGENT AND NOT A CALLBACK
			$revert_stmt3="select call_date, status, closecallid from vicidial_closer_log where lead_id='$lead_id' $callback_time_clause and status not in ('".implode("','", $cb_dispos)."') order by call_date asc limit 1";
			$revert_rslt3=mysql_to_mysqli($revert_stmt3, $link);
			if (mysqli_num_rows($revert_rslt3)>0) {
				$revert_row3=mysqli_fetch_row($revert_rslt3);
				if ($revert_row3[0]!="") {
					$callback_entry_time=$revert_row3[0];
					$new_status=$revert_row3[1];
				}
			}
			if ($DB) {echo "|$cb_row[0]<BR>$revert_stmt - $revert_row[2]<BR>$revert_stmt2 - $revert_row2[2]<BR>$revert_stmt3 - $revert_row3[2]|\n";}
		}

		$upd_stmt="update vicidial_list set $new_listSQL status='$new_status' where lead_id='$lead_id';";
		$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
		$actual_callback_ct+=mysqli_affected_rows($link);
		$upd_stmts .= " $upd_stmt";

		$archive_stmt = "INSERT INTO vicidial_callbacks_archive SELECT * from vicidial_callbacks where callback_id='$cb_row[0]';";
		$archive_rslt=mysql_to_mysqli($archive_stmt, $link);
		$actual_archived_ct+=mysqli_affected_rows($link);
		$arch_stmts .= " $archive_stmt";

		$delete_stmt="DELETE from vicidial_callbacks where callback_id='$cb_row[0]';";
		$delete_rslt=mysql_to_mysqli($delete_stmt, $link);
		$actual_purged_ct+=mysqli_affected_rows($link);
		$del_stmts .= " $delete_stmt";
		}

	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$callback_stmt|$del_stmts|$upd_stmts|$arch_stmts|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LEADS', event_type='MODIFY', record_id='$lead_id', event_code='ADMIN CALLBACK BULK MOVE', event_sql=\"$SQL_log\", event_notes='callbacks deleted: $actual_purged_ct|leads moved: $actual_callback_ct|moved to: $new_list_id|archived cb: $actual_archived_ct';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	echo "<table width='500' align='center'><tr><td align='left'>";
	if ($purge_called_records) 
		{
		echo "<B>"._QXZ("PURGE COMPLETE")."</B>";
		echo "<UL><LI>$purge_ct "._QXZ("RECORDS FOUND")."</LI>";
		echo "<LI>$actual_purged_ct "._QXZ("RECORDS PURGED")."</LI></UL>";
		echo "<BR><BR>";
		}
	echo "<B>"._QXZ("LEAD UPDATE COMPLETE")."</B>";
	echo "<UL><LI>$callback_ct "._QXZ("RECORDS FOUND")."</LI>";
	echo "<LI>$actual_callback_ct "._QXZ("RECORDS MIGRATED TO")." $new_list_id</LI></UL><BR><BR>";

	echo "<a href='$PHP_SELF?DB=$DB'>"._QXZ("BACK")."</a><BR><BR>";
	}
else if ($SUBMIT && $new_list_id  && ($new_status || $revert_status) && (count($cb_users)>0 || count($cb_user_groups)>0 || count($cb_lists)>0 || count($cb_groups)>0) && !$confirm_transfer) 
	{
#	$DB=1;
	if ($purge_trigger==0) 
		{
		$callback_stmt="SELECT * from vicidial_callbacks vc where status='LIVE' $usersSQL $user_groupsSQL $listsSQL $groupsSQL $daySQL";
		}
	else 
		{
		$callback_stmt="SELECT * from vicidial_callbacks vc, vicidial_list vl where $purge_clause and vc.lead_id=vl.lead_id $usersSQL $user_groupsSQL $listsSQL $groupsSQL $daySQL";

		$purge_stmt="SELECT vc.status, vl.status from vicidial_callbacks vc, vicidial_list vl where $purge_clause and vc.lead_id=vl.lead_id $usersSQL $user_groupsSQL $listsSQL $groupsSQL $daySQL";
		$purge_rslt=mysql_to_mysqli($purge_stmt, $link);
		$purge_ct=mysqli_num_rows($purge_rslt);
		$purge_called_ct=0;
		$purge_uncalled_ct=0;
		while ($purge_row=mysqli_fetch_row($purge_rslt)) 
			{
			if ($purge_row[0]=="INACTIVE" || ($purge_row[0]=="LIVE" && !in_array("$purge_row[1]", $cb_dispos))) {$purge_called_ct++;} 
			if ($purge_row[0]=="ACTIVE" || ($purge_row[0]=="LIVE" && in_array("$purge_row[1]", $cb_dispos))) {$purge_uncalled_ct++;} 
			}
		}
	$callback_rslt=mysql_to_mysqli($callback_stmt, $link);
	$callback_ct=mysqli_num_rows($callback_rslt);
	if ($DB) {echo $callback_stmt."<BR>";}
	# if ($DB) {echo $purge_stmt."<BR>";}

	$list_name_stmt="SELECT list_name from vicidial_lists where list_id='$new_list_id'";
	$list_name_rslt=mysql_to_mysqli($list_name_stmt, $link);
	$list_name_row=mysqli_fetch_row($list_name_rslt);
	$new_list_id_name=$list_name_row[0];

	echo "<table width='500' align='center'><tr><td align='left'>";
	echo _QXZ("You are about to transfer")." $callback_ct "._QXZ("callbacks from")."<BR><UL>\n";
	if (count($cb_groups)>0) {echo "<LI>"._QXZ("campaigns")." ".implode(",", $cb_groups)."</LI>\n";}
	if (count($cb_lists)>0) {echo "<LI>"._QXZ("lists")." ".implode(",", $cb_lists)."</LI>\n";}
	if (count($cb_user_groups)>0) {echo"<LI>". _QXZ("user groups")." ".implode(",", $cb_user_groups)."</LI>\n";}
	if (count($cb_users)>0) {echo "<LI>"._QXZ("users")." ".implode(",", $cb_users)."</LI>\n";}
	if ($days_uncalled) {echo "<LI>"._QXZ("where leads have been LIVE and uncalled for ")." $days_uncalled "._QXZ("days")."</LI>\n";}
	echo "</UL><BR>";

	echo _QXZ("to list ID")." <B>$new_list_id - $new_list_id_name</B> ".($revert_status ? "as <B>previous status</B>" :_QXZ("as status")." <B>$new_status</B>")."<BR><BR>\n";
	if ($purge_called_records) {echo "<B><font color='#990000'>**$purge_called_ct "._QXZ("RECORDS HAVING BEEN CALLED or INACTIVE WILL BE PURGED")."**</font></B><BR><BR>\n";}
	if ($purge_uncalled_records) {echo "<B><font color='#990000'>**$purge_uncalled_ct "._QXZ("ACTIVE RECORDS WILL BE PURGED")."**</font></B><BR><BR>\n";}
	if ($purge_called_records && $purge_uncalled_records) {echo "<B><font color='#990000'>**$purge_ct "._QXZ("RECORDS HAVING BEEN ").implode(" or ", $purge_verbiage)._QXZ(" WILL BE PURGED")."**</font></B><BR><BR>\n";}
	echo "</td></tr></table>";

	echo "<a href='$PHP_SELF?DB=$DB&new_list_id=$new_list_id&new_status=$new_status&days_uncalled=$days_uncalled&revert_status=$revert_status&purge_called_records=$purge_called_records&purge_uncalled_records=$purge_uncalled_records&SUBMIT=1&confirm_transfer=YES$usersQS$user_groupsQS$listsQS$groupsQS'>"._QXZ("CLICK TO CONFIRM")."</a><BR><BR>";
	echo "<a href='$PHP_SELF?DB=$DB'>"._QXZ("CLICK TO CANCEL")."</a><BR><BR>";

	}
else 
	{
	echo "<table width='650' align='center' border=0>";
	echo "<tr>";
	echo "<td align='left' width='270'>";
	$stmt="SELECT campaign_id, count(*) as ct from vicidial_callbacks $whereLOGallowed_campaignsSQL group by campaign_id order by campaign_id";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	echo "<B>"._QXZ("Campaigns with callbacks").":</B>$NWB#cb-bulk-campaigns$NWE<BR><select name='cb_groups[]' size=5 multiple>\n";
	echo "<option value='ALL'>"._QXZ("CHECK ALL CAMPAIGNS")."</option>\n";
	if (mysqli_num_rows($rslt)>0) 
		{
		while ($row=mysqli_fetch_array($rslt)) 
			{
			if (in_array($row["campaign_id"], $preload_campaigns)) {$s=" selected";} else {$s="";}
			echo "\t<option value='$row[campaign_id]'$s>".($row["campaign_id"] ? $row["campaign_id"] : "(none)")." - ($row[ct] callbacks)</option>\n";
			}
		}
	else
		{
		echo "\t<option value=''>**"._QXZ("NO CALLBACKS")."**</option>\n";
		}
	echo "</select>";
	echo "</td>";

	echo "<td align='left' width='400'>";
	$list_stmt="SELECT list_id from vicidial_lists $whereLOGallowed_campaignsSQL";
	$list_rslt=mysql_to_mysqli($list_stmt, $link);
	$list_id_str="";
	while($list_row=mysqli_fetch_row($list_rslt)) 
		{
		$list_id_str.="'$list_row[0]',";
		}
	$list_id_str=substr($list_id_str,0,-1);

	$stmt="SELECT v.list_id, vl.list_name, vl.campaign_id, count(*) as ct from vicidial_callbacks v, vicidial_lists vl where v.list_id in ($list_id_str) and v.list_id=vl.list_id group by list_id order by list_id";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	echo "<B>"._QXZ("Lists with callbacks").":</B>$NWB#cb-bulk-lists$NWE<BR><select name='cb_lists[]' size=5 multiple>\n";
	echo "<option value='ALL'>"._QXZ("CHECK ALL LISTS")."</option>\n";
	if (mysqli_num_rows($rslt)>0) 
		{
		while ($row=mysqli_fetch_array($rslt)) 
			{
			if (in_array($row["list_id"], $preload_lists)) {$s=" selected";} else {$s="";}
			echo "\t<option value='$row[list_id]'$s>$row[list_id] - $row[list_name] - $row[campaign_id] - ($row[ct] callbacks)</option>\n";
			}
		}
	else
		{
		echo "\t<option value=''>**"._QXZ("NO LISTS")."**</option>\n";
		}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td align='left' width='270'>";
	$stmt="SELECT user_group, count(*) as ct from vicidial_callbacks $whereLOGadmin_viewable_groupsSQL group by user_group order by user_group";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	echo "<B>"._QXZ("User groups with callbacks").":</B>$NWB#cb-bulk-usergroups$NWE<BR><select name='cb_user_groups[]' size=5 multiple>\n";
	echo "<option value='ALL'>"._QXZ("CHECK ALL USER GROUPS")."</option>\n";
	if (mysqli_num_rows($rslt)>0) 
		{
		while ($row=mysqli_fetch_array($rslt)) 
			{
			if (in_array($row["user_group"], $preload_user_groups)) {$s=" selected";} else {$s="";}
			echo "\t<option value='$row[user_group]'$s>$row[user_group] - ($row[ct] callbacks)</option>\n";
			}
		}
	else
		{
		echo "\t<option value=''>**"._QXZ("NO CALLBACKS")."**</option>\n";
		}
	echo "</select>";
	echo "</td>";
	echo "<td align='left' width='400'>";
	$stmt="SELECT user, count(*) as ct from vicidial_callbacks $whereLOGadmin_viewable_groupsSQL group by user order by user";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	echo "<B>"._QXZ("Agents with callbacks (USERONLY)").":</B>$NWB#cb-bulk-agents$NWE<BR><select name='cb_users[]' size=5 multiple>\n";
	echo "<option value='ALL'>"._QXZ("CHECK ALL AGENTS")."</option>\n";
	if (mysqli_num_rows($rslt)>0) 
		{
		while ($row=mysqli_fetch_array($rslt)) 
			{
			$user_stmt="SELECT full_name from vicidial_users $ul_clause and user='$row[user]' $LOGadmin_viewable_groupsSQL";
			$user_rslt=mysql_to_mysqli($user_stmt, $link);
			if (mysqli_num_rows($user_rslt)>0) 
				{
				$user_row=mysqli_fetch_array($user_rslt);
				if (in_array($row["user"], $preload_users)) {$s=" selected";} else {$s="";}
				echo "\t<option value='$row[user]'$s>$row[user] - $user_row[full_name] - ($row[ct] callbacks)</option>\n";
				}
			}
		}
	else 
		{
		echo "\t<option value=''>**"._QXZ("NO CALLBACKS")."**</option>\n";
		}
	echo "</select>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td align='left' width='270'>";
	echo "<B><input type='checkbox' name='purge_called_records' value='Y' checked>"._QXZ("Purge called records")."$NWB#cb-bulk-purge$NWE<BR>";
	echo "<input type='checkbox' name='purge_uncalled_records' value='Y' checked>"._QXZ("Purge uncalled records")."$NWB#cb-bulk-purge$NWE<BR>";
	echo "</td>";
	echo "<td align='left' width='400'>";
	echo "<B>"._QXZ("Live and uncalled for over")." <select name='days_uncalled'>";
	echo "<option value='0'>--</option>\n";
	for ($i=1; $i<=31; $i++) 
		{
		echo "<option value='$i'>$i</option>\n";
		}
	if (strlen($days_uncalled)>0)
		{echo "<option selected value='$days_uncalled'>$days_uncalled</option>\n";}
	echo "</select>";
	echo " "._QXZ("days, leave blank to ignore")."</B>$NWB#cb-bulk-liveanduncalled$NWE";
	echo "</td>";
	echo "</tr>";
	echo "</table>";

	echo "<BR><BR>";

	echo "<table width='650' align='center' border=0>";
	echo "<tr>";
	echo "<td align='center' colspan='2'>";
	echo "<input type='submit' name='SUBMIT' value='  "._QXZ("TRANSFER TO")." '><BR/><BR/>";
	echo "</td>";
	echo "</tr>";

	$stmt="SELECT list_id, list_name from vicidial_lists $whereLOGadmin_viewable_groupsSQL order by list_id asc";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);

	echo "<tr>";
	echo "<td align='left' width='400'><B>";
	echo _QXZ("List ID").": <select name='new_list_id' size=1>\n";
	echo "<option value='X'>"._QXZ("DO NOT ALTER LIST ID")."</option>";
	while ($row=mysqli_fetch_array($rslt)) 
		{
		echo "\t<option value='$row[list_id]'>$row[list_id] - $row[list_name]</option>\n";
		}
	echo "</select>$NWB#cb-bulk-newlist$NWE\n";
	echo "</B></td>";

	echo "<td align='right' width='250'><B>";	
	echo _QXZ("New Status").": <input type=text name='new_status' size=7 maxlength=6 value='$new_status'>$NWB#cb-bulk-newstatus$NWE<BR>\n";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td align='right' colspan='2'>";
	echo "<B><input type='checkbox' name='revert_status' value='checked' $revert_status>&nbsp;"._QXZ("Revert to last status")."<BR><font size='1'>("._QXZ("overrides -New Status-").")</font></B>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	}
echo "</form>\n";

echo "\n<BR><BR><BR>";


$ENDtime = date("U");

$RUNtime = ($ENDtime - $StarTtimE);

echo "\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";


?>


</TD></TR><TABLE>
</body>
</html>

<?php
	
exit; 


?>
