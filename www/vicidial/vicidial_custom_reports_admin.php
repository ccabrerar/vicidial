<?php
# vicidial_custom_reports_admin.php
# 
# Allows admin level users to add access to their own 
# custom reports and make them available to user groups
# of their choosing
#
# Copyright (C) 2018 Joseph Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 151023-0106 - First build 
# 170409-1542 - Added IP List validation code
# 170822-2313 - Added screen colors
# 170823-1411 - Fixed delete bug
# 170829-0040 - Added screen color settings
# 171002-2140 - Added capability to add/modify preset variables
# 180508-2215 - Added new help display
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["report_name"]))				{$report_name=$_GET["report_name"];}
	elseif (isset($_POST["report_name"]))	{$report_name=$_POST["report_name"];}
if (isset($_GET["domain"]))				{$domain=$_GET["domain"];}
	elseif (isset($_POST["domain"]))	{$domain=$_POST["domain"];}
if (isset($_GET["path_name"]))				{$path_name=$_GET["path_name"];}
	elseif (isset($_POST["path_name"]))	{$path_name=$_POST["path_name"];}
if (isset($_GET["presets_string"]))				{$presets_string=$_GET["presets_string"];}
	elseif (isset($_POST["presets_string"]))	{$presets_string=$_POST["presets_string"];}
if (isset($_GET["slave"]))				{$slave=$_GET["slave"];}
	elseif (isset($_POST["slave"]))	{$slave=$_POST["slave"];}
if (isset($_GET["custom_reports_user_groups"]))				{$custom_reports_user_groups=$_GET["custom_reports_user_groups"];}
	elseif (isset($_POST["custom_reports_user_groups"]))	{$custom_reports_user_groups=$_POST["custom_reports_user_groups"];}
if (isset($_GET["add_custom_report"]))				{$add_custom_report=$_GET["add_custom_report"];}
	elseif (isset($_POST["add_custom_report"]))	{$add_custom_report=$_POST["add_custom_report"];}
if (isset($_GET["delete_custom_report"]))				{$delete_custom_report=$_GET["delete_custom_report"];}
	elseif (isset($_POST["delete_custom_report"]))	{$delete_custom_report=$_POST["delete_custom_report"];}
if (isset($_GET["update_custom_report"]))				{$update_custom_report=$_GET["update_custom_report"];}
	elseif (isset($_POST["update_custom_report"]))	{$update_custom_report=$_POST["update_custom_report"];}
if (isset($_GET["upd_custom_report_id"]))				{$upd_custom_report_id=$_GET["upd_custom_report_id"];}
	elseif (isset($_POST["upd_custom_report_id"]))	{$upd_custom_report_id=$_POST["upd_custom_report_id"];}
if (isset($_GET["upd_report_name"]))				{$upd_report_name=$_GET["upd_report_name"];}
	elseif (isset($_POST["upd_report_name"]))	{$upd_report_name=$_POST["upd_report_name"];}
if (isset($_GET["upd_path_name"]))				{$upd_path_name=$_GET["upd_path_name"];}
	elseif (isset($_POST["upd_path_name"]))	{$upd_path_name=$_POST["upd_path_name"];}
if (isset($_GET["upd_presets_string"]))				{$upd_presets_string=$_GET["upd_presets_string"];}
	elseif (isset($_POST["upd_presets_string"]))	{$upd_presets_string=$_POST["upd_presets_string"];}
if (isset($_GET["upd_slave"]))				{$upd_slave=$_GET["upd_slave"];}
	elseif (isset($_POST["upd_slave"]))	{$upd_slave=$_POST["upd_slave"];}
if (isset($_GET["upd_domain"]))				{$upd_domain=$_GET["upd_domain"];}
	elseif (isset($_POST["upd_domain"]))	{$upd_domain=$_POST["upd_domain"];}
if (isset($_GET["upd_custom_reports_user_groups"]))				{$upd_custom_reports_user_groups=$_GET["upd_custom_reports_user_groups"];}
	elseif (isset($_POST["upd_custom_reports_user_groups"]))	{$upd_custom_reports_user_groups=$_POST["upd_custom_reports_user_groups"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,custom_fields_enabled,enable_languages,language_method,active_modules FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$custom_fields_enabled =		$row[4];
	$SSenable_languages =			$row[5];
	$SSlanguage_method =			$row[6];
	$active_modules =				$row[7];
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
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1,0);
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

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	if ($reports_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to view reports");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ( ($reports_auth > 0) and ($admin_auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
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
###############
$stmt="SELECT export_reports,user_group,admin_hide_lead_data,admin_hide_phone_data,admin_cf_show_hidden from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGexport_reports =			$row[0];
$LOGuser_group =				$row[1];
$LOGadmin_hide_lead_data =		$row[2];
$LOGadmin_hide_phone_data =		$row[3];
$LOGadmin_cf_show_hidden =		$row[4];


$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
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

	$allowedUGarray=explode(" ", $rawLOGadmin_viewable_groupsSQL);
	if (in_array('---ALL---',$custom_reports_user_groups)) 
		{
		$custom_reports_user_groups=$allowedUGarray;
		}
	}

# Need to do this just in case
if (preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) {

	$all_user_groups_stmt="select user_group from vicidial_user_groups";
	$all_user_groups_rslt=mysql_to_mysqli($all_user_groups_stmt, $link);

	$allowedUGarray=array();
	while ($user_group_row=mysqli_fetch_row($all_user_groups_rslt)) 
		{
		array_push($allowedUGarray, $user_group_row[0]);
		}

	if (is_array($custom_reports_user_groups))
		{
		if (in_array('---ALL---',$custom_reports_user_groups)) 
			{
			$custom_reports_user_groups=$allowedUGarray;
			}
		}
}

# From system settings
$Vreports = array("Real-Time Main Report", " Real-Time Campaign Summary", " Inbound Report", " Inbound Service Level Report", " Inbound Summary Hourly Report", " Inbound Daily Report", " Inbound DID Report", " Inbound IVR Report", " Outbound Calling Report", " Outbound Summary Interval Report", " Outbound IVR Report", " Fronter - Closer Report", " Lists Campaign Statuses Report", " Campaign Status List Report", " Export Calls Report", " Export Leads Report", " Agent Time Detail", " Agent Status Detail", " Agent Performance Detail", " Team Performance Detail", " Performance Comparison Report", " Single Agent Daily", " Single Agent Daily Time", " User Group Login Report", " User Timeclock Report", " User Group Timeclock Status Report", " User Timeclock Detail Report", " Server Performance Report", " Administration Change Log", " List Update Stats", " User Stats", " User Time Sheet", " Download List", " Dialer Inventory Report", " Maximum System Stats", " Maximum Stats Detail", " Search Leads Logs", " Email Log Report", " Carrier Log Report", " Campaign Debug", " Hangup Cause Report", " Lists Pass Report", " Called Counts List IDs Report");


if ($add_custom_report) {
	if (!$report_name || !$path_name) {
		$error_msg="<BR><B>"._QXZ("CANNOT ADD REPORT, SOME FIELDS ARE MISSING")."</B><BR>";
	} else {
		if (in_array($report_name, $Vreports)) {
			$error_msg="<BR><B>"._QXZ("CANNOT ADD REPORT, REPORT NAME ALREADY IN USE FOR STANDARD VICIDIAL REPORT")."</B><BR>";
		} else {
			$ins_stmt="insert into vicidial_custom_reports(report_name, date_added, user, domain, path_name, custom_variables) VALUES('$report_name', now(), '$PHP_AUTH_USER', '$domain', '$path_name', '$presets_string')";
			$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
			if (mysqli_affected_rows($link)<1) {
				$error_msg="<BR><B>"._QXZ("INSERT FAILED")."</B><BR>";
			} else {
				$user_group_stmt="select user_group, allowed_custom_reports from vicidial_user_groups where user_group in ('".implode("','", $custom_reports_user_groups)."') $LOGadmin_viewable_groupsSQL";
				#		echo $user_group_stmt."<BR>\n";
				$user_group_rslt=mysql_to_mysqli($user_group_stmt, $link);
				while ($user_group_row=mysqli_fetch_array($user_group_rslt)) {
					$user_group=$user_group_row["user_group"];
					$allowed_custom_reports=$user_group_row["allowed_custom_reports"];
					if (!preg_match('/^$report_name\||\|$report_name\|/i', $allowed_custom_reports)) {
						$allowed_custom_reports=$allowed_custom_reports."$report_name|";
						$upd_stmt="update vicidial_user_groups set allowed_custom_reports='$allowed_custom_reports' where user_group='$user_group'";
						# echo $upd_stmt."<BR>\n";
						$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
					}
				}
			}
		}
	}
} else if ($upd_custom_report_id) {
	if (preg_match('/\-\-ALL\-\-/i',$upd_custom_reports_user_groups)) {
		$update_UG_array=$allowedUGarray; # All allowed user groups for user
	} else {
		$update_UG_array=explode("|", $upd_custom_reports_user_groups);
	}

	if (!$upd_report_name || !$upd_path_name) {
		$error_msg="<BR><B>"._QXZ("CANNOT UPDATE REPORT, SOME FIELDS ARE MISSING")."</B><BR>";
	} else {
		if (in_array($upd_report_name, $Vreports)) {
			$error_msg="<BR><B>"._QXZ("CANNOT UPDATE REPORT, NEW CUSTOM REPORT NAME ALREADY IN USE FOR STANDARD VICIDIAL REPORT")."</B><BR>";
		} else {
			$old_rpt_stmt="select report_name from vicidial_custom_reports from vicidial_custom_reports where custom_report_id='$upd_custom_report_id'";
			$old_rpt_rslt=mysql_to_mysqli($old_rpt_stmt, $link);
			$old_rpt_row=mysqli_fetch_row($old_rpt_rslt);
			$old_rpt_name=$old_rpt_row[0];

			$upd_stmt="update vicidial_custom_reports set report_name='$upd_report_name', date_added=now(), user='$PHP_AUTH_USER', domain='$upd_domain', path_name='$upd_path_name', custom_variables='$upd_presets_string' where custom_report_id='$upd_custom_report_id'";
			$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
			if (mysqli_affected_rows($link)<1) {
				$error_msg="<BR><B>"._QXZ("UPDATE FAILED")."</B><BR>";
			} else {
				$user_group_stmt="select user_group, allowed_custom_reports from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL";
				$user_group_rslt=mysql_to_mysqli($user_group_stmt, $link);
				while ($user_group_row=mysqli_fetch_array($user_group_rslt)) {
					$user_group=$user_group_row["user_group"];
					$allowed_custom_reports=$user_group_row["allowed_custom_reports"];

					if (in_array($user_group, $update_UG_array)) {
						if (!preg_match("/^$upd_report_name\||\|$upd_report_name\|/i", $allowed_custom_reports)) {
							$allowed_custom_reports=$allowed_custom_reports."$upd_report_name|";
							$upd_stmt="update vicidial_user_groups set allowed_custom_reports='$allowed_custom_reports' where user_group='$user_group'";
							$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
						}
					} else {
						if (preg_match("/^$upd_report_name\||\|$upd_report_name\|/i", $allowed_custom_reports)) {
							$allowed_custom_reports=preg_replace("/^$upd_report_name\||\|$upd_report_name\|/i", '', $allowed_custom_reports);
							$upd_stmt="update vicidial_user_groups set allowed_custom_reports='$allowed_custom_reports' where user_group='$user_group'";
							$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
						}
					}
				}
			}
		}
	}
} else if ($delete_custom_report) {
	$old_rpt_stmt="select report_name from vicidial_custom_reports from vicidial_custom_reports where custom_report_id='$upd_custom_report_id'";
	$old_rpt_rslt=mysql_to_mysqli($old_rpt_stmt, $link);
	$old_rpt_row=mysqli_fetch_row($old_rpt_rslt);
	$old_rpt_name=$old_rpt_row[0];

	$del_stmt="delete from vicidial_custom_reports where custom_report_id='$delete_custom_report'";
	$del_rslt=mysql_to_mysqli($del_stmt, $link);
	if (mysqli_affected_rows($link)<1) {
		$error_msg="<BR><B>"._QXZ("DELETE FAILED")."</B><BR>";
	} else {
		$user_group_stmt="select user_group, allowed_custom_reports from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL";
		$user_group_rslt=mysql_to_mysqli($user_group_stmt, $link);
		while ($user_group_row=mysqli_fetch_array($user_group_rslt)) {
			$user_group=$user_group_row["user_group"];
			$allowed_custom_reports=$user_group_row["allowed_custom_reports"];
			$old_report_removed=preg_replace("/^$old_rpt_name\||\|$old_rpt_name\|/i", "", $allowed_custom_reports);
			$upd_stmt="update vicidial_user_groups set allowed_custom_reports='$old_report_removed' where user_group='$user_group'";
			# echo $upd_stmt." - $allowed_custom_reports<BR>\n";
			$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
		}
	}
}

require("screen_colors.php");

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

	echo "<HTML><HEAD>\n";

?>
<script language="Javascript">
function SubmitIDValues(id_no) {
	RNfieldName="report_name"+id_no;
	// SLfieldName="slave"+id_no;
	DMfieldName="domain"+id_no;
	PNfieldName="path_name"+id_no;
	UGfieldName="custom_reports_user_groups"+id_no;
	CVfieldName="presets_string"+id_no;
	var new_constants_string="";
	var custom_variables = document.getElementById(CVfieldName).value.replace(/\n+/g, '&');
	// custom_variables = document.getElementById(CVfieldName).value.replace(/\n/g, '&');

	var ALL_presets_array=custom_variables.split("\&");
	for (y=0; y<ALL_presets_array.length; y++) {
		if (ALL_presets_array[y].length>0) {
			var key_value=ALL_presets_array[y].split("\=");
			if (key_value.length>=2)
				{
				key_value[0]=key_value[0].replace(/[^a-z0-9_\[\]]/gi, '');
				key_value[0]=key_value[0].replace(/\[[^\]]+/gi, '[');
				key_value[0]=key_value[0].replace(/\].*/gi, ']');
				key_value[1]=key_value[1].replace(/[^- \.\:\/\@\_0-9a-zA-Z]/gi, '');
				key_value[1]=encodeURIComponent(key_value[1]);
				new_constants_string+="&"+key_value[0]+"="+key_value[1];
				}
		}
	}

	var selectedUGstr = "";


	document.getElementById("upd_custom_report_id").value=id_no;
	document.getElementById("upd_report_name").value=document.getElementById(RNfieldName).value;
	document.getElementById("upd_domain").value=document.getElementById(DMfieldName).value;
	// document.getElementById("upd_slave").value=document.getElementById(SLfieldName).value;
	document.getElementById("upd_path_name").value=document.getElementById(PNfieldName).value;
	document.getElementById("upd_presets_string").value=new_constants_string;

	//for (i=0;i<document.getElementById(SLfieldName).length;i++) {
	//	if (document.getElementById(SLfieldName)[i].selected) {
	//		document.getElementById("upd_slave").value =  document.getElementById(SLfieldName)[i].value;
	//	}
	//}
	for (x=0;x<document.getElementById(UGfieldName).length;x++) {
		if (document.getElementById(UGfieldName)[x].selected) {
			selectedUGstr = selectedUGstr + "|" + document.getElementById(UGfieldName)[x].value;
		}
	}
	document.getElementById("upd_custom_reports_user_groups").value=selectedUGstr.substring(1);

	document.getElementById("updateForm").submit();
}
function AddConstant() {
	var current_constants=document.getElementById('presets_string').value;
	var new_variable_name=document.getElementById('new_preset_name').value;
	new_variable_name=new_variable_name.replace(/[^a-z0-9_\[\]]/gi, '');
	new_variable_name=new_variable_name.replace(/\[[^\]]+/gi, '[');
	new_variable_name=new_variable_name.replace(/\].*/gi, ']');

	var new_variable_value="";
	for (x=0;x<document.getElementById('new_preset_value').length;x++) {
		if (document.getElementById('new_preset_value')[x].selected && document.getElementById('new_preset_value')[x].value!="") {
			new_variable_value =  "--A--"+document.getElementById('new_preset_value')[x].value+"--B--";
		}
	}
	if (new_variable_value=='') {new_variable_value=document.getElementById('new_preset_custom').value;}
	new_variable_value=new_variable_value.replace(/[^- \.\:\/\@\_0-9a-zA-Z]/gi, '');	
	new_variable_value=encodeURIComponent(new_variable_value);

	current_constants+="&"+new_variable_name+"="+new_variable_value;
	current_constants = current_constants.replace(/^&/, '');
	document.getElementById('presets_string').value=current_constants;
	var ALL_presets_array=current_constants.split("\&");

	var SpanInnerHTML="<table width='100%' border='0' cellpadding='3'>";
	for (y=0; y<ALL_presets_array.length; y++) {
		var key_value=ALL_presets_array[y].split("\=");
		SpanInnerHTML+="<tr><td align='left'>"+key_value[0]+"</td><td align='left'>"+key_value[1]+"</td><td align='center'><input style='background-color:#<?php echo "$SSbutton_color"; ?>' type='button' onClick='RemoveConstant("+y+")' value='REMOVE'></td></tr>";
	}
	SpanInnerHTML+="</table>";
	document.getElementById('new_presets').innerHTML=SpanInnerHTML;
}
function RemoveConstant(array_index) {
	var current_constants=document.getElementById('presets_string').value;
	var new_constants_string="";
	var ALL_presets_array=current_constants.split("\&");
	var SpanInnerHTML="<table width='100%' border='0' cellpadding='3'>";
	var x=0;
	for (y=0; y<ALL_presets_array.length; y++) {
		if (y!=array_index) {
			new_constants_string+="&"+ALL_presets_array[y];
			var key_value=ALL_presets_array[y].split("\=");
			SpanInnerHTML+="<tr><td align='left'>"+key_value[0]+"</td><td align='left'>"+key_value[1]+"</td><td align='center'><input style='background-color:#<?php echo "$SSbutton_color"; ?>' type='button' onClick='RemoveConstant("+x+")' value='REMOVE'></td></tr>";
			x++;
		}
	}
	SpanInnerHTML+="</table>";

	new_constants_string = new_constants_string.replace(/^&/, '');
	document.getElementById('new_presets').innerHTML=SpanInnerHTML;
	document.getElementById('presets_string').value=new_constants_string;
}
</script>
<?php
	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("ADMINISTRATION").": "._QXZ("Custom Reports");
	if ($ivr_export == 'YES')
		{echo " "._QXZ("IVR");}

	##### BEGIN Set variables to make header show properly #####
	$ADD =					'100';
	$hh =					'lists';
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
	$lists_color =		'#FFFF99';
	$lists_font =		'BLACK';
	$lists_color =		'#E6E6E6';
	$subcamp_color =	'#C6C6C6';
	##### END Set variables to make header show properly #####

	require("admin_header.php");


	echo "<CENTER><BR>\n";
	echo "<FONT SIZE=3 FACE=\"Arial,Helvetica\"><B>"._QXZ("Vicidial Custom Reports");
	echo "</B></FONT><BR>\n";

	echo "<TABLE><TR><TD>\n";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	echo "$error_msg";

	echo "<br><form action=$PHP_SELF method=GET>\n";

	echo "<center><TABLE width=$section_width cellspacing=3>\n";
	echo "<tr><td align='left' colspan='2'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><B>"._QXZ("ADD A NEW CUSTOM REPORT")."</B></FONT></td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row2_background."><td align=right>"._QXZ("Report Name").": </td><td align=left><input type=text name=report_name size=20 maxlength=100>$NWB#custom_reports_admin-report_name$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row2_background."><td align=right>"._QXZ("Domain").": </td><td align=left><input type=text name='domain' id='domain' size=20 maxlength=70> ("._QXZ("leave blank if on same server as dialer admin").")$NWB#custom_reports_admin-domain$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row2_background."><td align=right>"._QXZ("Path Name").": </td><td align=left><input type=text id='path_name' name='path_name' size=20>$NWB#custom_reports_admin-path_name$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row2_background."><td align=right>"._QXZ("Preset constants").": </td><td align=left>"._QXZ("Variable name").": <input type='text' id='new_preset_name' name='new_preset_name' size='10' maxlength='100'>&nbsp;&nbsp;"._QXZ("Value").": ";
	echo "<select name='new_preset_value' id='new_preset_value'><option value=''>"._QXZ("Custom value")." --->></option><option value='today'>"._QXZ("today")."</option><option value='yesterday'>"._QXZ("yesterday")."</option><option value='datetime'>"._QXZ("datetime")."</option><option value='filedatetime'>"._QXZ("filedatetime")."</option><option value='6days'>"._QXZ("6days")."</option><option value='7days'>"._QXZ("7days")."</option><option value='8days'>"._QXZ("8days")."</option><option value='13days'>"._QXZ("13days")."</option><option value='14days'>"._QXZ("14days")."</option><option value='15days'>"._QXZ("15days")."</option><option value='30days'>"._QXZ("30days")."</option></select>&nbsp;";
	echo "<input type='text' name='new_preset_custom' id='new_preset_custom' size='10' maxlength='100'>&nbsp;<input style='background-color:#$SSbutton_color' type='button' onClick='AddConstant()' value='"._QXZ("ADD")."'>$NWB#custom_reports_admin-constants$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row2_background."><td align=right>"._QXZ("Current constants").":</td><td align=left><span id='new_presets'></span>$NWB#custom_reports_admin-current_constants$NWE<input type='hidden' name='presets_string' id='presets_string'></td></tr>\n";
	# echo "<tr bgcolor=#".$SSstd_row2_background."><td align=right>"._QXZ("Use slave").": </td><td align=left><select size=1 name=slave><option value='Y'>"._QXZ("Y")."</option><option value='N' selected>"._QXZ("N")."</option></select>$NWB#custom_reports_admin-use_slave$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row2_background."><td align=right>"._QXZ("User groups").": </td><td align=left><select size=5 name=custom_reports_user_groups[] multiple><option value='---ALL---'>"._QXZ("ALL USER GROUPS")."</option>";
	
	$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$usergroups_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i<$usergroups_to_print) {
		$ug_row=mysqli_fetch_row($rslt);
		echo "<option value='$ug_row[0]'>$ug_row[1]</option>";
		$i++;
	}

	echo "</select>$NWB#custom_reports_admin-custom_reports_user_groups$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row2_background."><td align='center' colspan='2'><input style='background-color:#$SSbutton_color' type='submit' name='add_custom_report' id='add_custom_report' value='"._QXZ("ADD REPORT")."'></td></tr>\n";
	echo "</table>";
	echo "</form>";

	echo "<BR><BR><BR>";


	$rpt_stmt="select * from vicidial_custom_reports";	
	$rpt_rslt=mysql_to_mysqli($rpt_stmt, $link);
	echo "<form action=$PHP_SELF method=GET id='updateForm'>";

	if (mysqli_num_rows($rpt_rslt)>0) {
		echo "<br><B>"._QXZ("UPDATE EXISTING CUSTOM REPORTS")."</B>\n";
		echo "<center><TABLE width=$section_width cellpadding=3>\n";

		echo "<tr bgcolor='#000000'>";
		echo "<td><font size=1 color=white align=left>"._QXZ("REPORT NAME")."</font></td>";
		echo "<td><font size=1 color=white align=left>"._QXZ("DOMAIN")."</font></td>";
#		echo "<td><font size=1 color=white align=left>"._QXZ("PATH NAME")."</font></td>";
		echo "<td><font size=1 color=white align=left>"._QXZ("PRESET CONSTANTS")."$NWB#custom_reports_admin-preset_constants$NWE</font></td>";
		echo "<td>&nbsp;</td>";
		# echo "<td><font size=1 color=white align=left>"._QXZ("SLAVE")."</font></td>";
		echo "<td><font size=1 color=white align=left>"._QXZ("USER GROUPS")."</font></td>";
		echo "<td>&nbsp;</td>";
		echo "</tr>";

		while($rpt_row=mysqli_fetch_array($rpt_rslt)) {
			$id=$rpt_row["custom_report_id"];
			$current_rpt_name=$rpt_row["report_name"];

			if ($bgcolor==$SSstd_row3_background) {$bgcolor=$SSstd_row4_background;} else {$bgcolor=$SSstd_row3_background;}

			$UGarray=array();
			$UGstmt="select user_group from vicidial_user_groups where allowed_custom_reports like '%$current_rpt_name%' $LOGadmin_viewable_groupsSQL";
			$UGrslt=mysql_to_mysqli($UGstmt, $link);
			while($UGrow=mysqli_fetch_row($UGrslt)) {
				array_push($UGarray, $UGrow[0]);
			}

			$custom_variables=preg_replace("/\&/", "\n", urldecode($rpt_row["custom_variables"]));
			
			echo "<tr bgcolor='#".$bgcolor."'>";
			echo "<td align=left><input type=text class='form_field' id='report_name".$id."' name='report_name".$id."' size=20 maxlength=100 value='".$rpt_row["report_name"]."'></td>\n";
			echo "<td align=left><input type=text class='form_field' id='domain".$id."' name='domain".$id."' size=20 maxlength=70 value='".$rpt_row["domain"]."'></td>\n";
			echo "<td align=left rowspan='2'><textarea class='form_field' rows='5' cols='35' id='presets_string".$id."' name='presets_string".$id."'>".$custom_variables."</textarea></td>\n";
			echo "<td align=center nowrap rowspan='2'><a href='".$rpt_row["domain"].$rpt_row["path_name"]."?".$rpt_row["custom_variables"]."' target='_blank'>"._QXZ("TEST LINK")."</a></td>";
			# echo "<td align=left><select size=1 id=slave".$id." name=slave".$id."><option value='Y'>"._QXZ("Y")."</option><option value='N'>"._QXZ("N")."</option><option value='".$rpt_row["use_slave_server"]."' selected>"._QXZ($rpt_row["use_slave_server"])."</option></select></td>\n";
			echo "<td align=left rowspan='2'><select size=5 class='form_field' id=custom_reports_user_groups".$id." name=custom_reports_user_groups".$id." multiple><option value='---ALL---'>"._QXZ("ALL USER GROUPS")."</option>";

			$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$usergroups_to_print = mysqli_num_rows($rslt);
			$i=0;
			while ($i<$usergroups_to_print) {
				$ug_row=mysqli_fetch_row($rslt);
				if (in_array("$ug_row[0]", $UGarray)) {$x="selected";} else {$x="";}
				echo "<option value='$ug_row[0]' $x>$ug_row[1]</option>";
				$i++;
			}
			echo "<td align='center'><font size=1><input type='button' class='blue_btn' value='"._QXZ("UPDATE")."' onClick=\"SubmitIDValues($id)\"></td>";
			echo "</tr>";
			echo "<tr bgcolor='#".$bgcolor."'>";
			echo "<td colspan='2' align=left nowrap><font size='1'>PATH:<input type=text class='form_field' id='path_name".$id."' name='path_name".$id."' size=40 maxlength=100 value='".$rpt_row["path_name"]."'></font></td>\n";
			echo "<td align='center'><a href='vicidial_custom_reports_admin.php?delete_custom_report=".$id."'>"._QXZ("DELETE")."</a></font></td>";
			echo "</tr>\n";

		}
	}
	echo "</table>";
	echo "<input type=hidden name='upd_custom_report_id' id='upd_custom_report_id'>";
	echo "<input type=hidden name='upd_report_name' id='upd_report_name'>";
	echo "<input type=hidden name='upd_domain' id='upd_domain'>";
	# echo "<input type=hidden name='upd_slave' id='upd_slave'>";
	echo "<input type=hidden name='upd_path_name' id='upd_path_name'>";
	echo "<input type=hidden name='upd_presets_string' id='upd_presets_string'>";
	echo "<input type=hidden name='upd_custom_reports_user_groups' id='upd_custom_reports_user_groups'>";
	echo "</form>";

	echo "</TD></TR></TABLE>\n";
	echo "</BODY></HTML>";
?>


