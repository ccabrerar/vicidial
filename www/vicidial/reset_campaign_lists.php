<?php
# reset_campaign_lists.php - VICIDIAL administration page
#
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130711-2051 - First build
# 130830-1800 - Changed to mysqli PHP functions
# 141007-2058 - Finalized adding QXZ translation to all admin files
# 141229-2009 - Added code for on-the-fly language translations display
# 160508-2301 - Added colors features, fixed allowed campaigns bug
# 170409-1541 - Added IP List validation code
# 180916-1027 - Added per-list daily reset limit
#

$admin_version = '2.14-7';
$build = '180916-1027';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$QUERY_STRING = getenv("QUERY_STRING");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,auto_dial_limit,user_territories_active,allow_custom_dialplan,callcard_enabled,admin_modify_refresh,nocache_admin,webroot_writable,allow_emails,hosted_settings,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSauto_dial_limit =			$row[1];
	$SSuser_territories_active =	$row[2];
	$SSallow_custom_dialplan =		$row[3];
	$SScallcard_enabled =			$row[4];
	$SSadmin_modify_refresh =		$row[5];
	$SSnocache_admin =				$row[6];
	$SSwebroot_writable =			$row[7];
	$SSemail_enabled =				$row[8];
	$SShosted_settings =			$row[9];
	$SSenable_languages =			$row[10];
	$SSlanguage_method =			$row[11];
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

$NOW_DATE = date("Y-m-d");

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

$user_auth=0;
$auth=0;
$reports_auth=0;
$qc_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
if ($auth_message == 'GOOD')
	{$user_auth=1;}

if ($user_auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 1 and qc_enabled='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$qc_auth=$row[0];

	$reports_only_user=0;
	$qc_only_user=0;
	if ( ($reports_auth > 0) and ($auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	if ( ($qc_auth > 0) and ($reports_auth < 1) and ($auth < 1) )
		{
		if ( ($ADD != '881') and ($ADD != '100000000000000') )
			{
            $ADD=100000000000000;
			}
		$qc_only_user=1;
		}
	if ( ($qc_auth < 1) and ($reports_auth < 1) and ($auth < 1) )
		{
		$VDdisplayMESSAGE = _QXZ("You do not have permission to be here");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
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


$stmt="SELECT user_id,user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,shift_override_flag,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,callcard_admin,force_change_password,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,modify_contacts,modify_same_user_level from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfull_name				=$row[3];
$LOGuser_level				=$row[4];
$LOGuser_group				=$row[5];
$LOGdelete_users			=$row[8];
$LOGdelete_user_groups		=$row[9];
$LOGdelete_lists			=$row[10];
$LOGdelete_campaigns		=$row[11];
$LOGdelete_ingroups			=$row[12];
$LOGdelete_remote_agents	=$row[13];
$LOGload_leads				=$row[14];
$LOGcampaign_detail			=$row[15];
$LOGast_admin_access		=$row[16];
$LOGast_delete_phones		=$row[17];
$LOGdelete_scripts			=$row[18];
$LOGdelete_filters			=$row[29];
$LOGalter_agent_interface	=$row[30];
$LOGdelete_call_times		=$row[32];
$LOGmodify_call_times		=$row[33];
$LOGmodify_users			=$row[34];
$LOGmodify_campaigns		=$row[35];
$LOGmodify_lists			=$row[36];
$LOGmodify_scripts			=$row[37];
$LOGmodify_filters			=$row[38];
$LOGmodify_ingroups			=$row[39];
$LOGmodify_usergroups		=$row[40];
$LOGmodify_remoteagents		=$row[41];
$LOGmodify_servers			=$row[42];
$LOGview_reports			=$row[43];
$LOGmodify_dids				=$row[56];
$LOGdelete_dids				=$row[57];
$LOGmanager_shift_enforcement_override=$row[61];
$LOGexport_reports			=$row[64];
$LOGdelete_from_dnc			=$row[65];
$LOGcallcard_admin			=$row[70];
$LOGforce_change_password	=$row[71];
$LOGmodify_shifts			=$row[72];
$LOGmodify_phones			=$row[73];
$LOGmodify_carriers			=$row[74];
$LOGmodify_labels			=$row[75];
$LOGmodify_statuses			=$row[76];
$LOGmodify_voicemail		=$row[77];
$LOGmodify_audiostore		=$row[78];
$LOGmodify_moh				=$row[79];
$LOGmodify_tts				=$row[80];
$LOGmodify_contacts			=$row[81];
$LOGmodify_same_user_level	=$row[82];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
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
	$LOGallowed_campaignsSQL = "and vc.campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where vc.campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

if (isset($_GET["reset_lead_called_campaigns"]))			{$reset_lead_called_campaigns=$_GET["reset_lead_called_campaigns"];}
	elseif (isset($_POST["reset_lead_called_campaigns"]))	{$reset_lead_called_campaigns=$_POST["reset_lead_called_campaigns"];}
if (isset($_GET["all_or_active_only"]))			{$all_or_active_only=$_GET["all_or_active_only"];}
	elseif (isset($_POST["all_or_active_only"]))	{$all_or_active_only=$_POST["all_or_active_only"];}
if (isset($_GET["submit_campaign_reset"]))			{$submit_campaign_reset=$_GET["submit_campaign_reset"];}
	elseif (isset($_POST["submit_campaign_reset"]))	{$submit_campaign_reset=$_POST["submit_campaign_reset"];}

?>

<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: Campaign Lists Reset"); ?>

<?php

##### BEGIN Set variables to make header show properly #####
$ADD =					'311111';
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

if ( ($LOGuser_level >= 9) and $LOGmodify_campaigns>0 and $LOGmodify_lists>0 and ( (preg_match("/Administration Change Log/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) ) )
	{
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	$campaign_stmt="SELECT vl.campaign_id, vc.campaign_name, count(*) as ct from vicidial_lists vl, vicidial_campaigns vc where vc.active='Y' and vc.campaign_id=vl.campaign_id $LOGallowed_campaignsSQL group by campaign_id order by campaign_id, campaign_name asc";
	$campaign_rslt=mysql_to_mysqli($campaign_stmt, $link);
	if ($DB > 0) {echo $campaign_stmt;}

	echo "<br><B>"._QXZ("Reset Lead-Called-Status for Campaigns").":</B><BR><BR>\n";
	echo "<form action='$PHP_SELF?ADD=200000000000' method='post'>";
	echo "<center><TABLE width=$section_width cellspacing=0 cellpadding=1 BGCOLOR=#$SSframe_background>\n";
	echo "<tr bgcolor=black>";
	echo "<td><font size=1 color=white align=left><B>"._QXZ("Select campaign").":</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("Reset all/active only")."</B></td>";
	echo "<td align=center><font size=1 color=white><B>&nbsp;</B></td></tr>\n";

	echo "<tr bgcolor='#$SSstd_row2_background'>";
	echo "<td align='left'>";
	echo "<select name='reset_lead_called_campaigns'>\n";
	while ($campaign_row=mysqli_fetch_array($campaign_rslt)) 
		{
		if ($campaign_row["ct"]>0) 
			{
			$selected="";
			if ($reset_lead_called_campaigns==$campaign_row["campaign_id"]) 
				{
				$selected="selected";
				}
			echo "<option value='$campaign_row[campaign_id]' $selected>$campaign_row[campaign_id] - $campaign_row[campaign_name], $campaign_row[ct] "._QXZ("list")."(s)</option>\n";
			}
		}
	echo "</select></td>";
	echo "<td><select name='all_or_active_only'>";
	echo "<option value='Y'>"._QXZ("Active lists only")."</option>";
	echo "<option value=''>"._QXZ("All lists")."</option>";
	echo "</select>";
	echo "</td>";
	echo "<td align='right'><input style='background-color:#$SSbutton_color' type='submit' name='submit_campaign_reset' value='"._QXZ("SUBMIT")."'></td></tr>";
	echo "<tr ><td colspan='3'>";

	if ($submit_campaign_reset && $reset_lead_called_campaigns) 
		{
		if ($all_or_active_only=="Y") {$list_id_clause="and active='Y'";  $verbiage="(active lists only)";}
				
		$list_id_stmt="SELECT list_id,daily_reset_limit,resets_today from vicidial_lists where campaign_id='$reset_lead_called_campaigns' $list_id_clause order by list_id asc";
		if ($DB > 0) {echo $list_id_stmt;}
		$list_id_rslt=mysql_to_mysqli($list_id_stmt, $link);
		if (mysqli_num_rows($list_id_rslt)>0) 
			{
			echo _QXZ("CAMPAIGN")." <B>$reset_lead_called_campaigns</B> "._QXZ("LISTS RESETTING")." $verbiage:<BR>\n<UL>";
			
			### LOG INSERTION Admin Log Table ###
			$SQLdate=date("Y-m-d H:i:s");
			$SQL_log = "$list_id_stmt|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='CAMPAIGNS', event_type='RESET', record_id='$reset_lead_called_campaigns', event_code='ADMIN RESET CAMPAIGN LISTS', event_sql=\"$SQL_log\", event_notes='';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			}

		while ($list_id_row=mysqli_fetch_array($list_id_rslt)) 
			{
			$list_id =				$list_id_row["list_id"];
			$daily_reset_limit =	$list_id_row["daily_reset_limit"];
			$resets_today =			$list_id_row["resets_today"];
			if ( ($daily_reset_limit > $resets_today) or ($daily_reset_limit < 0) )
				{
				$upd_stmt="UPDATE vicidial_lists SET resets_today=(resets_today + 1) where list_id='$list_id';";
				$upd_rslt=mysql_to_mysqli($upd_stmt, $link);

				$resets_today=($resets_today + 1);

				$upd_stmtB="UPDATE vicidial_list SET called_since_last_reset='N' where list_id='$list_id';";
				$upd_rsltB=mysql_to_mysqli($upd_stmtB, $link);
				$affected_rowsB = mysqli_affected_rows($link);

				### LOG INSERTION Admin Log Table ###
				$SQLdate=date("Y-m-d H:i:s");
				$SQL_log = "$upd_stmt|$upd_stmtB|";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='RESET', record_id='$list_id', event_code='ADMIN RESET LIST', event_sql=\"$SQL_log\", event_notes='$affected_rowsB leads reset, list resets today: $resets_today';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				
				echo "<LI>"._QXZ("LIST ID")." $list_id - ";
				if ($affected_rowsB > 0) {echo _QXZ("RESET")."<BR>";} else {echo "<B>"._QXZ("NOT")."</B> "._QXZ("RESET")."<BR>";}
				}
			else
				{
				echo "<LI>"._QXZ("LIST ID")." $list_id - <B>"._QXZ("NOT RESET, daily reset limit reached").": $daily_reset_limit / $resets_today</B><BR>";
				}
			}
		if (mysqli_num_rows($list_id_rslt)>0) {echo "</UL>";}
		if (mysqli_num_rows($list_id_rslt)<7) 
			{
			for ($j=mysqli_num_rows($list_id_rslt); $j<7; $j++) {echo "<BR>";}
			}
		}
	else 
		{
		echo "<BR><BR><BR><BR><BR><BR><BR>&nbsp;";
		}
	echo "</td></tr>";
	echo "</TABLE></center></form>\n";

	echo "</TABLE></center>";

	echo "</TD></TR>\n";
	echo "<TR><TD BGCOLOR=#$SSmenu_background ALIGN=CENTER>\n";
	echo "<font size=0 color=white><br><br><!-- RUNTIME: $RUNtime seconds<BR> -->";
	echo _QXZ("VERSION").": $admin_version<BR>";
	echo _QXZ("BUILD").": $build\n";
	if (!preg_match("/_BUILD_/",$SShosted_settings))
		{echo "<BR><a href=\"$PHP_SELF?ADD=999995\"><font color=white>&copy; 2016 "._QXZ("ViciDial Group")."</font></a><BR><img src=\"images/pixel.gif\">";}
	echo "</font>\n";
	?>

	</TD><TD BGCOLOR=#<?php echo $SSframe_background; ?>>
	</TD></TR><TABLE>
	</body>
	</html>
	<?php
	}
else 
	{
	echo _QXZ("You are not authorized to view this page."); 
	exit;
	}
?>
