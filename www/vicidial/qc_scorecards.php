<?php
# qc_scorecards.php
# 
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen manages QC Scorecards within VICIdial
#
# changes:
# 210306-1532 - First Build
# 210827-1818 - Fix for security issue
#

$admin_version = '2.14-1';
$build = '210306-1532';

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php"); # /srv/www/vhosts/vicimarketing/vicidial/
require("functions.php");
$link=mysqli_connect("$VARDB_server", "$VARDB_user", "$VARDB_pass", "$VARDB_database", $VARDB_port);
if (!$link) 
	{
    die("MySQL connect ERROR:  " . mysqli_error('mysqli'));
	}

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$QUERY_STRING = getenv("QUERY_STRING");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,auto_dial_limit,user_territories_active,allow_custom_dialplan,callcard_enabled,admin_modify_refresh,nocache_admin,webroot_writable,admin_screen_colors,qc_features_active,hosted_settings FROM system_settings;";
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
	$SSadmin_screen_colors =		$row[8];
	$SSqc_features_active =			$row[9];
	$SShosted_settings =			$row[10];

	# slightly increase limit value, because PHP somehow thinks 2.8 > 2.8
	$SSauto_dial_limit = ($SSauto_dial_limit + 0.001);
	}
##### END SETTINGS LOOKUP #####
###########################################

$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u','',$PHP_AUTH_USER);

if ( file_exists("/etc/mysql_enc.conf") ) {
	$DBCagc = file("/etc/mysql_enc.conf");
	foreach ($DBCagc as $DBCline) {
		$DBCline = preg_replace("/ |>|\n|\r|\t|\#.*|;.*/","",$DBCline);
		if (ereg("^enckey", $DBCline)) {$enckey = $DBCline;   $enckey = preg_replace("/.*=/","",$enckey);}
	}
}

if (isset($_GET["DB"]))	{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))	{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["new_scorecard_id"]))	{$new_scorecard_id=$_GET["new_scorecard_id"];}
	elseif (isset($_POST["new_scorecard_id"]))	{$new_scorecard_id=$_POST["new_scorecard_id"];}
if (isset($_GET["new_scorecard_name"]))	{$new_scorecard_name=$_GET["new_scorecard_name"];}
	elseif (isset($_POST["new_scorecard_name"]))	{$new_scorecard_name=$_POST["new_scorecard_name"];}
if (isset($_GET["new_active"]))	{$new_active=$_GET["new_active"];}
	elseif (isset($_POST["new_active"]))	{$new_active=$_POST["new_active"];}
if (isset($_GET["active"]))	{$active=$_GET["active"];}
	elseif (isset($_POST["active"]))	{$active=$_POST["active"];}
if (isset($_GET["action"]))	{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))	{$action=$_POST["action"];}
if (isset($_GET["confirm_deletion"]))	{$confirm_deletion=$_GET["confirm_deletion"];}
	elseif (isset($_POST["confirm_deletion"]))	{$confirm_deletion=$_POST["confirm_deletion"];}
if (isset($_GET["scorecard_id"]))	{$scorecard_id=$_GET["scorecard_id"];}
	elseif (isset($_POST["scorecard_id"]))	{$scorecard_id=$_POST["scorecard_id"];}

$submit=preg_replace('/[^-0-9 \p{L}]/u','',$submit);
$new_scorecard_id = preg_replace('/[^-_0-9a-zA-Z]/','',$new_scorecard_id);
$new_scorecard_name = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$new_scorecard_name);
$new_active = preg_replace('/[^NY]/','',$new_active);
$active = preg_replace('/[^NY]/','',$active);
$action = preg_replace('/[^-_0-9a-zA-Z]/','',$action);
$confirm_deletion = preg_replace('/[^Y]/','',$confirm_deletion);
$scorecard_id = preg_replace('/[^-_0-9a-zA-Z]/','',$scorecard_id);


$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='B9CBFD';
$SSstd_row3_background='8EBCFD';
$SSstd_row4_background='B6D3FC';
$SSstd_row5_background='A3C3D6';
$SSalt_row1_background='BDFFBD';
$SSalt_row2_background='99FF99';
$SSalt_row3_background='CCFFCC';

if ($SSadmin_screen_colors != 'default')
	{
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$colors_ct = mysqli_num_rows($rslt);
	if ($colors_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$SSmenu_background =		$row[0];
		$SSframe_background =		$row[1];
		$SSstd_row1_background =	$row[2];
		$SSstd_row2_background =	$row[3];
		$SSstd_row3_background =	$row[4];
		$SSstd_row4_background =	$row[5];
		$SSstd_row5_background =	$row[6];
		$SSalt_row1_background =	$row[7];
		$SSalt_row2_background =	$row[8];
		$SSalt_row3_background =	$row[9];
		$SSweb_logo =				$row[10];
		}
	}
$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;

if ($submit==_QXZ("SUBMIT NEW SCORECARD")) {
	$ins_stmt="insert into quality_control_scorecards(qc_scorecard_id, scorecard_name, active) VALUES('".mysqli_escape_string($link, $new_scorecard_id)."', '".mysqli_escape_string($link, $new_scorecard_name)."', '$new_active')";
	$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
}

# Valid user
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

if ($auth) 
	{
	$office_no=strtoupper($PHP_AUTH_USER);
	$password=strtoupper($PHP_AUTH_PW);
	$auth_stmt="SELECT user_id,user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,shift_override_flag,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,callcard_admin,force_change_password,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,modify_contacts,modify_same_user_level from vicidial_users where user='$PHP_AUTH_USER';";
	$rslt=mysqli_query($link, $auth_stmt);
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
	$qc_auth					=$row[46];
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
	$rslt=mysqli_query($link, $stmt);
	$row=mysqli_fetch_row($rslt);
	$LOGallowed_campaigns =			$row[0];
	$LOGallowed_reports =			$row[1];
	$LOGadmin_viewable_groups =		$row[2];
	$LOGadmin_viewable_call_times =	$row[3];
	}

$stmt="SELECT allowed_campaigns,allowed_reports from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysqli_query($link, $stmt);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns = $row[0];
$LOGallowed_reports =	$row[1];

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match("/ALL-/",$LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");      // HTTP/1.0

echo "<html>\n";
echo "<head>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";	
echo "<title>"._QXZ("Quality control scorecards")."</title>\n";
echo "</head>\n";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";


##### BEGIN Set variables to make header show properly #####
$ADD =  '999998';
$hh =       'qc';
$qc_display_group_type =		'SCORECARD';
$LOGast_admin_access = '1';
$SSoutbound_autodial_active = '1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$admin_color =    '#FFFF99';
$admin_font =      'BLACK';
$admin_color =    '#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####


require("admin_header.php");

echo "<!-- QC: $SSqc_features_active $qc_auth -->";

?>
<style type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}

td.small_grey {
 	FONT-SIZE: 10pt;
    background: #FFFFCC;
    border-bottom:1px dotted #000000;
}
th.display_header {
    background: #000000;
	color:#FFFFFF;
    font-family: Arial, Sans-Serif;
	FONT-SIZE: 10pt;
	FONT-WEIGHT: bold;
    border-spacing: 0px;
	padding: 2px;
	border-collapse: separate;
}
th.small_display_header {
    background: #000000;
	color:#FFFFFF;
    font-family: Arial, Sans-Serif;
	FONT-SIZE: 8pt;
	FONT-WEIGHT: bold;
    border-spacing: 0px;
	padding: 2px;
	border-collapse: separate;
}
th.display_white_header {
    background: #FFFFFF;
	color:#000000;
    font-family: Arial, Sans-Serif;
	FONT-SIZE: 10pt;
	FONT-WEIGHT: bold;
    border-spacing: 0px;
	padding: 2px;
	border-collapse: separate;
}

input.red_btn{
   color:#FFFFFF;
   font-size:12px;
   font-weight:bold;
   background-color:#993333;
   border:2px solid;
   border-top-color:#FFCCCC;
   border-left-color:#FFCCCC;
   border-right-color:#660000;
   border-bottom-color:#660000;
   filter:progid:DXImageTransform.Microsoft.Gradient
      (GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ff660000');}
input.blue_btn{
   color:#FFFFFF;
   font-size:12px;
   font-weight:bold;
   background-color:#3333FF;
   border:2px solid;
   border-top-color:#CCCCFF;
   border-left-color:#CCCCFF;
   border-right-color:#000066;
   border-bottom-color:#000066;
   filter:progid:DXImageTransform.Microsoft.Gradient
      (GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ff000066');}
input.yellow_btn{
   color:#000000;
   font-size:12px;
   font-weight:bold;
   background-color:#FFFF00;
   border:2px solid;
   border-top-color:#FFFFCC;
   border-left-color:#FFFFCC;
   border-right-color:#333300;
   border-bottom-color:#333300;
   filter:progid:DXImageTransform.Microsoft.Gradient
      (GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ffFFFF00');}
input.green_btn{
   color:#FFFFFF;
   font-size:12px;
   font-weight:bold;
   background-color:#009900;
   border:2px solid;
   border-top-color:#CCFFCC;
   border-left-color:#CCFFCC;
   border-right-color:#003300;
   border-bottom-color:#003300;
   filter:progid:DXImageTransform.Microsoft.Gradient
      (GradientType=0,StartColorStr='#00ffffff',EndColorStr='#FF003300');}
input.tiny_red_btn{
   color:#FFFFFF;
   font-size:8px;
   font-weight:bold;
   background-color:#993333;
   border:2px solid;
   border-top-color:#FFCCCC;
   border-left-color:#FFCCCC;
   border-right-color:#660000;
   border-bottom-color:#660000;
   filter:progid:DXImageTransform.Microsoft.Gradient
      (GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ff660000');}
input.tiny_blue_btn{
   color:#FFFFFF;
   font-size:8px;
   font-weight:bold;
   background-color:#3333FF;
   border:2px solid;
   border-top-color:#CCCCFF;
   border-left-color:#CCCCFF;
   border-right-color:#000066;
   border-bottom-color:#000066;
   filter:progid:DXImageTransform.Microsoft.Gradient
      (GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ff000066');}
input.tiny_yellow_btn{
   color:#000000;
   font-size:8px;
   font-weight:bold;
   background-color:#FFFF00;
   border:2px solid;
   border-top-color:#FFFFCC;
   border-left-color:#FFFFCC;
   border-right-color:#333300;
   border-bottom-color:#333300;
   filter:progid:DXImageTransform.Microsoft.Gradient
      (GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ffFFFF00');}
input.tiny_green_btn{
   color:#FFFFFF;
   font-size:8px;
   font-weight:bold;
   background-color:#009900;
   border:2px solid;
   border-top-color:#CCFFCC;
   border-left-color:#CCFFCC;
   border-right-color:#003300;
   border-bottom-color:#003300;
   filter:progid:DXImageTransform.Microsoft.Gradient
      (GradientType=0,StartColorStr='#00ffffff',EndColorStr='#FF003300');}
.form_field {
    font-family: Arial, Sans-Serif;
    font-size: 12px;
    margin-bottom: 3px;
 
    padding: 2px;
    border: solid 1px #000066;
    background-image: url( 'images/blue_bg.jpg' );
    background-repeat: repeat-x;
    background-position: top;
}
textarea.notes_box {
    font-family: Arial, Sans-Serif;
    font-size: 10px;
    margin-bottom: 3px;
    padding: 2px;
    border: solid 1px #000066;
}
-->
</style>
<script language="JavaScript">
function NewScorecard() {
	var xmlhttp=false;
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	if (xmlhttp) { 
		var display_query = "&qc_action=new_scorecard";
		xmlhttp.open('POST', 'qc_module_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(display_query); 
		xmlhttp.onreadystatechange = function() { 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var QCCheckpoints = xmlhttp.responseText;
				document.getElementById("checkpoint_display").innerHTML = QCCheckpoints;
			}
		}
		delete xmlhttp;
	}
}
function LoadCheckpoints(scorecard_id) {
	var xmlhttp=false;
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	if (xmlhttp) { 
		var display_query = "&qc_action=load_checkpoints&scorecard_id="+scorecard_id;
		xmlhttp.open('POST', 'qc_module_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(display_query); 
		xmlhttp.onreadystatechange = function() { 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var QCCheckpoints = xmlhttp.responseText;
				document.getElementById("checkpoint_display").innerHTML = QCCheckpoints;
				ReloadScorecardDisplay();
			}
		}
		delete xmlhttp;
	}
}
function ReloadScorecardDisplay() {
	var xmlhttp=false;
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	if (xmlhttp) { 
		var display_query = "&qc_action=reload_scorecard_display";
		xmlhttp.open('POST', 'qc_module_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(display_query); 
		xmlhttp.onreadystatechange = function() { 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var QCDisplay = xmlhttp.responseText;
				document.getElementById("scorecards_display").innerHTML = QCDisplay;
			}
		}
		delete xmlhttp;
	}
}
function AddCheckpoint(scorecard_id) {
	var qrank = document.getElementById("new_checkpoint_rank");
	var checkpoint_rank = qrank.options[qrank.selectedIndex].value;	

	var qactive = document.getElementById("new_active");
	if (qactive.checked) {var active="Y";} else {var active="N";}

	var qinstfail=document.getElementById("new_instant_fail");
	if (qinstfail.checked) {var instant_fail="Y";} else {var instant_fail="N";}

	// var qcomm=document.getElementById("new_commission_loss");
	// if (qcomm.checked) {var comm_loss="Y";} else {var comm_loss="N";}
	var comm_loss="N";

	var qtext = document.getElementById("new_checkpoint_text");
	var checkpoint_text=encodeURIComponent(qtext.value);

	var qtext_presets = document.getElementById("new_checkpoint_text_presets");
	var checkpoint_text_presets=encodeURIComponent(qtext_presets.value);

	var qpts = document.getElementById("new_checkpoint_points");
	var checkpoint_points=encodeURIComponent(qpts.value);

	var qadmin = document.getElementById("new_admin_notes");
	var admin_notes=encodeURIComponent(qadmin.value);

	var add_query = "&qc_action=add_checkpoint&scorecard_id="+scorecard_id+"&new_checkpoint_rank="+checkpoint_rank+"&new_active="+active+"&new_checkpoint_text="+checkpoint_text+"&new_checkpoint_text_presets="+checkpoint_text_presets+"&new_admin_notes="+admin_notes+"&new_checkpoint_points="+checkpoint_points+"&new_instant_fail="+instant_fail+"&new_commission_loss="+comm_loss;

	var xmlhttp=false;
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	if (xmlhttp) { 
		xmlhttp.open('POST', 'qc_module_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(add_query); 
		xmlhttp.onreadystatechange = function() { 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var QCCheckpoints = xmlhttp.responseText;
				LoadCheckpoints(scorecard_id);
			}
		}
		delete xmlhttp;
	}

}
function ChangeCheckpoint(checkpoint_row_id, scorecard_id, q_action, parameter, old_checkpoint_rank, new_checkpoint_rank) {
	if (q_action=="delete_checkpoint") 
		{
		var elementID="checkpoint_rank"+checkpoint_row_id;
		var e = document.getElementById(elementID);
		var checkpoint_rank = e.options[e.selectedIndex].value;
		var update_query = "&qc_action="+q_action+"&scorecard_id="+scorecard_id+"&checkpoint_row_id="+checkpoint_row_id+"&checkpoint_rank="+checkpoint_rank;
		}
	else if (q_action=="update_scorecard")
		{
		var elementID=parameter+scorecard_id;
		var e = document.getElementById(elementID);
		var parameter_value="";
		if (parameter=="active") 
			{
			if (e.checked) {parameter_value="Y";} else {parameter_value="N";}
			}
		else
			{
			parameter_value=encodeURIComponent(e.value);
			}
		var update_query = "&qc_action="+q_action+"&scorecard_id="+scorecard_id+"&parameter="+parameter+"&parameter_value="+parameter_value;
		}
	else 
		{
		var elementID=parameter+checkpoint_row_id;
		var e = document.getElementById(elementID);
		var parameter_value="";

		if (parameter=="active" || parameter=="instant_fail" || parameter=="commission_loss") 
			{
			if (e.checked) {parameter_value="Y";} else {parameter_value="N";}
			} 
		else if (parameter=="checkpoint_text" || parameter=="checkpoint_text_presets" || parameter=="checkpoint_points" || parameter=="admin_notes") 
			{
			parameter_value=encodeURIComponent(e.value);
			}

		var update_query = "&qc_action="+q_action+"&scorecard_id="+scorecard_id+"&checkpoint_row_id="+checkpoint_row_id+"&parameter="+parameter+"&parameter_value="+parameter_value+"&old_checkpoint_rank="+old_checkpoint_rank+"&new_checkpoint_rank="+new_checkpoint_rank;
		}


	var xmlhttp=false;
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	if (xmlhttp) { 
		xmlhttp.open('POST', 'qc_module_actions.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(update_query); 
		xmlhttp.onreadystatechange = function() { 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var QCCheckpoints = xmlhttp.responseText;
				if (q_action!="update_scorecard") {LoadCheckpoints(scorecard_id);} else {ReloadScorecardDisplay();}
			}
		}
		delete xmlhttp;
	}
}
</script>
<?php
    echo "<TABLE width='100%'><TR><TD>\n";
    echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	echo "<img src=\"images/icon_black_qc.png\" alt=\"Quality Control\" width=42 height=42> "._QXZ("Quality Control Scorecards").":<BR><BR>\n";

	if ($action=="delete_scorecard" && $scorecard_id && !$confirm_deletion) 
		{
		echo "<a href='$PHP_SELF?scorecard_id=$scorecard_id&action=delete_scorecard&confirm_deletion=Y'>"._QXZ("CLICK HERE TO CONFIRM SCORECARD")." \"$scorecard_id\" "._QXZ("DELETION")."</a><BR><BR>";
		}
	else if ($action=="delete_scorecard" && $scorecard_id && $confirm_deletion=="Y") 
		{
		$del_stmt="delete from quality_control_scorecards where qc_scorecard_id='$scorecard_id'";
		$del_rslt=mysql_to_mysqli($del_stmt, $link);
		if(mysqli_affected_rows($link)>0)
			{
			echo "<B>"._QXZ("SCORECARD")." $scorecard_id "._QXZ("DELETED")."</B><BR><BR>";
			}


		$del_stmt="delete from quality_control_checkpoints where qc_scorecard_id='$scorecard_id'";
		$del_rslt=mysql_to_mysqli($del_stmt, $link);

		}
	echo "<span id='scorecards_display'>\n";
	echo "<table cellpadding=2 cellspacing=0 width='100%'>\n";
	echo "\t<tr bgcolor=black nowrap>\n";
	echo "\t\t<td align=left><font size=1 color=white>"._QXZ("Scorecard")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Checkpoints")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Pass/max score")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Campaigns")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("In-groups")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Lists")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Last modified")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>"._QXZ("Active")."</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>&nbsp;</font></td>\n";
	echo "\t\t<td align=left><font size=1 color='white'>&nbsp;</font></td>\n";
	echo "\t</tr>\n";
	# <select name='scorecard_id' onChange='LoadCheckpoints(this.value)'>
	$stmt="select * from quality_control_scorecards order by qc_scorecard_id asc";
	$rslt=mysql_to_mysqli($stmt, $link);
	$i=0;
	while ($row=mysqli_fetch_array($rslt)) {
		$i++;
		if ($i%2==0) {$bgcolor=$SSstd_row1_background;} else {$bgcolor=$SSstd_row2_background;}
		echo "\t<tr bgcolor='".$bgcolor."'>\n";
		echo "\t\t\t<td align=left><font size=1>$row[qc_scorecard_id] - $row[scorecard_name]</font></td>\n";

		$qstmt="select count(*), sum(checkpoint_points) from quality_control_checkpoints where qc_scorecard_id='$row[qc_scorecard_id]'";
		$qrslt=mysql_to_mysqli($qstmt, $link);
		$qrow=mysqli_fetch_row($qrslt);
		$checkpoints=$qrow[0];
		($checkpoints==0 ? $score_total="--" : $score_total=$qrow[1]);
		echo "\t\t\t<td align=left><font size=1>$checkpoints</font></td>\n";
		echo "\t\t\t<td align=left><font size=1>$row[passing_score] / $score_total</font></td>\n";


		$qstmt="select count(*) from vicidial_campaigns where qc_scorecard_id='$row[qc_scorecard_id]'";
		$qrslt=mysql_to_mysqli($qstmt, $link);
		$qrow=mysqli_fetch_row($qrslt);
		$campaigns_in_use=$qrow[0];

		echo "\t\t\t<td align=left><font size=1>$campaigns_in_use</font></td>\n";


		$qstmt="select count(*) from vicidial_inbound_groups where qc_scorecard_id='$row[qc_scorecard_id]'";
		$qrslt=mysql_to_mysqli($qstmt, $link);
		$qrow=mysqli_fetch_row($qrslt);
		$ingroups_in_use=$qrow[0];

		echo "\t\t\t<td align=left><font size=1>$ingroups_in_use</font></td>\n";


		$qstmt="select count(*) from vicidial_lists where qc_scorecard_id='$row[qc_scorecard_id]'";
		$qrslt=mysql_to_mysqli($qstmt, $link);
		$qrow=mysqli_fetch_row($qrslt);
		$lists_in_use=$qrow[0];

		echo "\t\t\t<td align=left><font size=1>$lists_in_use</font></td>\n";
		echo "\t\t\t<td align=left><font size=1>$row[last_modified]</font></td>\n";
		echo "\t\t\t<td align=left><input type='checkbox' name='active".$row["qc_scorecard_id"]."' id='active".$row["qc_scorecard_id"]."' onClick=\"ChangeCheckpoint('0', '$row[qc_scorecard_id]', 'update_scorecard', 'active')\"".($row["active"]=="Y" ? " checked" : "")."></td>\n";

		echo "\t\t\t<td align=left><input type='button' class='tiny_blue_btn' onClick=\"LoadCheckpoints('$row[qc_scorecard_id]')\" value='"._QXZ("MODIFY")."'></td>\n";
		echo "\t\t\t<td align=left><input type='button' class='tiny_red_btn' onClick=\"window.location.href='$PHP_SELF?scorecard_id=$row[qc_scorecard_id]&action=delete_scorecard'\" value='"._QXZ("DELETE")."'></font></td>\n";
		echo "</tr>";
	}
	echo "\t<tr bgcolor=black>\n";
	echo "\t\t<th colspan='10'><font size=1 color='white'><input type='button' class='tiny_blue_btn' onClick='NewScorecard()' value='"._QXZ("ADD NEW SCORECARD")."'></font></td>\n";
	echo "\t</tr>\n";
	echo "</table>\n";
	echo "</span>\n";

	echo "<BR><a name='display_tag'><span id='checkpoint_display'>";
	echo "</span>";

	echo "</td></tr>\n";
	echo "</table>\n";
	echo "</font>\n";
	echo "</TD></TR></TABLE>\n";

echo "</TD></TR>\n";
echo "<TR><TD bgcolor=#$SSmenu_background ALIGN=CENTER><BR>\n";

echo "<FONT STYLE=\"font-family:HELVETICA;font-size:9;color:white;\">";
echo _QXZ("VERSION").": $admin_version<BR>";
echo _QXZ("BUILD").": $build\n";
if (!preg_match("/_BUILD_/",$SShosted_settings))
	{echo "<BR><a href=\"$PHP_SELF?ADD=999995\"><font color=white>&copy; 2021 ViciDial Group</font></a><BR><img src=\"images/pixel.gif\">";}
echo "</FONT>\n";
echo "</TD><TD bgcolor=#$SSframe_background ALIGN=CENTER>\n";
echo "</TD></TR>\n";
echo "</TABLE>\n";

?>
	</body>
</html>
