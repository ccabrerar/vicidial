<?php
# AST_admin_template_maker.php - version 2.14
# 
# Copyright (C) 2021  Matt Florell,Joe Johnson <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 120402-2132 - First Build
# 120529-1427 - Filename filter fix
# 130514-2127 - Bug fix on Chrome/IE browsers
# 130610-1102 - Finalized changing of all ereg instances to preg
# 130619-2044 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130719-1914 - Added ability to filter by statuses
# 130824-2325 - Changed to mysqli PHP functions
# 141114-0912 - Finalized adding QXZ translation to all admin files
# 141229-2024 - Added code for on-the-fly language translations display
# 170409-1532 - Added IP List validation code
# 180324-0942 - Enforce User Group campaign permissions for templates based on list_id
# 180503-2215 - Added new help display
# 180927-0633 - Fix for deleted template function in alternate language, issue #1127
# 210312-1700 - Added layout editing functionality
#

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["standard_fields_layout"]))				{$standard_fields_layout=$_GET["standard_fields_layout"];}
	elseif (isset($_POST["standard_fields_layout"]))	{$standard_fields_layout=$_POST["standard_fields_layout"];}
if (isset($_GET["custom_fields_layout"]))				{$custom_fields_layout=$_GET["custom_fields_layout"];}
	elseif (isset($_POST["custom_fields_layout"]))		{$custom_fields_layout=$_POST["custom_fields_layout"];}
if (isset($_GET["template_id"]))				{$template_id=$_GET["template_id"];}
	elseif (isset($_POST["template_id"]))		{$template_id=$_POST["template_id"];}
if (isset($_GET["template_name"]))				{$template_name=$_GET["template_name"];}
	elseif (isset($_POST["template_name"]))		{$template_name=$_POST["template_name"];}
if (isset($_GET["template_description"]))				{$template_description=$_GET["template_description"];}
	elseif (isset($_POST["template_description"]))		{$template_description=$_POST["template_description"];}
if (isset($_GET["file_format"]))				{$file_format=$_GET["file_format"];}
	elseif (isset($_POST["file_format"]))		{$file_format=$_POST["file_format"];}
if (isset($_GET["file_delimiter"]))				{$file_delimiter=$_GET["file_delimiter"];}
	elseif (isset($_POST["file_delimiter"]))		{$file_delimiter=$_POST["file_delimiter"];}
if (isset($_GET["template_list_id"]))				{$template_list_id=$_GET["template_list_id"];}
	elseif (isset($_POST["template_list_id"]))		{$template_list_id=$_POST["template_list_id"];}
if (isset($_GET["template_statuses"]))				{$template_statuses=$_GET["template_statuses"];}
	elseif (isset($_POST["template_statuses"]))		{$template_statuses=$_POST["template_statuses"];}
if (isset($_GET["standard_fields_layout"]))				{$standard_fields_layout=$_GET["standard_fields_layout"];}
	elseif (isset($_POST["standard_fields_layout"]))	{$standard_fields_layout=$_POST["standard_fields_layout"];}
if (isset($_GET["custom_fields_layout"]))				{$custom_fields_layout=$_GET["custom_fields_layout"];}
	elseif (isset($_POST["custom_fields_layout"]))		{$custom_fields_layout=$_POST["custom_fields_layout"];}
if (isset($_GET["submit_template"]))				{$submit_template=$_GET["submit_template"];}
	elseif (isset($_POST["submit_template"]))		{$submit_template=$_POST["submit_template"];}
if (isset($_GET["submit_edited_template"]))				{$submit_edited_template=$_GET["submit_edited_template"];}
	elseif (isset($_POST["submit_edited_template"]))		{$submit_edited_template=$_POST["submit_edited_template"];}
if (isset($_GET["edit_standard_fields_layout"]))				{$edit_standard_fields_layout=$_GET["edit_standard_fields_layout"];}
	elseif (isset($_POST["edit_standard_fields_layout"]))	{$edit_standard_fields_layout=$_POST["edit_standard_fields_layout"];}
if (isset($_GET["edit_custom_fields_layout"]))				{$edit_custom_fields_layout=$_GET["edit_custom_fields_layout"];}
	elseif (isset($_POST["edit_custom_fields_layout"]))		{$edit_custom_fields_layout=$_POST["edit_custom_fields_layout"];}
if (isset($_GET["delete_template"]))				{$delete_template=$_GET["delete_template"];}
	elseif (isset($_POST["delete_template"]))		{$delete_template=$_POST["delete_template"];}

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

#$vicidial_list_fields = '|lead_id|vendor_lead_code|source_id|list_id|gmt_offset_now|called_since_last_reset|phone_code|phone_number|title|first_name|middle_initial|last_name|address1|address2|address3|city|state|province|postal_code|country_code|gender|date_of_birth|alt_phone|email|security_phrase|comments|called_count|last_local_call_time|rank|owner|entry_list_id|';
$vicidial_listloader_fields = '|vendor_lead_code|source_id|phone_code|phone_number|title|first_name|middle_initial|last_name|address1|address2|address3|city|state|province|postal_code|country_code|gender|date_of_birth|alt_phone|email|security_phrase|comments|rank|owner|';


$US='_';
#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,admin_web_directory,custom_fields_enabled,webroot_writable,enable_languages,language_method,admin_screen_colors FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$admin_web_directory =		$row[1];
	$custom_fields_enabled =	$row[2];
	$webroot_writable =			$row[3];
	$SSenable_languages =		$row[4];
	$SSlanguage_method =		$row[5];
	$SSadmin_screen_colors =	$row[6];
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
$list_id_override = preg_replace('/[^0-9]/','',$list_id_override);
$template_list_id = preg_replace('/[^0-9]/','',$template_list_id);
$template_id = preg_replace("/'|\"|\\\\|;/","",$template_id);
$template_name = preg_replace("/'|\"|\\\\|;/","",$template_name);
$template_description = preg_replace("/'|\"|\\\\|;/","",$template_description);
$standard_fields_layout = preg_replace("/'|\"|\\\\|;/","",$standard_fields_layout);
$custom_table = preg_replace("/'|\"|\\\\|;/","",$custom_table);
$custom_fields_layout = preg_replace("/'|\"|\\\\|;/","",$custom_fields_layout);
$edit_standard_fields_layout = preg_replace("/'|\"|\\\\|;/","",$edit_standard_fields_layout);
$edit_custom_fields_layout = preg_replace("/'|\"|\\\\|;/","",$edit_custom_fields_layout);

if ($submit_edited_template)
	{
	$upd_stmt="update vicidial_custom_leadloader_templates set template_name='$template_name', template_description='$template_description', standard_variables='$edit_standard_fields_layout', custom_variables='$edit_custom_fields_layout' where template_id='$template_id' limit 1";
	$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
	if (mysqli_affected_rows($link)>0) 
		{
		$success_msg=_QXZ("TEMPLATE UPDATED");
		if (!$edit_custom_fields_layout) 
			{
			$success_msg.="<BR/>**"._QXZ("NO CUSTOM FIELDS ASSIGNED")."**";
			}
		}
	else 
		{
		$errno = mysqli_errno($link);
		if ($errno > 0)
			{$error = mysqli_error($link);}
		$error_msg=_QXZ("TEMPLATE UPDATE FAILED")."<br>\n$errno - $error<br>\n[$upd_stmt]";
		}
	}


$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_datetime = $STARTtime;

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

$stmt="SELECT load_leads,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGload_leads =	$row[0];
$LOGuser_group =	$row[1];

if ($LOGload_leads < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo "You do not have permissions to load leads: |$PHP_AUTH_USER|\n";
	exit;
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

if ($submit_template==_QXZ("SUBMIT TEMPLATE") && $template_id && $template_name && $template_list_id && $standard_fields_layout) 
	{

	$status_str="";
	$status_count=count($template_statuses);
	for ($q=0; $q<count($template_statuses); $q++) {
			echo "<!-- $template_statuses[$q] //-->\n";

		$status_str.="$template_statuses[$q]|";
	}
	echo "<!-- $status_str //-->";
	$status_str=preg_replace('/\|$/', '', $status_str);
	if (preg_match('/\-\-ALL\-\-/', $status_str)) {$status_str="";}
	
	$custom_table="custom_".$template_list_id;
	$ins_stmt="INSERT INTO vicidial_custom_leadloader_templates(template_id, template_name, template_description, list_id, standard_variables, custom_table, custom_variables, template_statuses) values('$template_id', '$template_name', '$template_description', '$template_list_id', '$standard_fields_layout', '$custom_table', '$custom_fields_layout', '$status_str')";
	$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
	echo "<!-- $ins_stmt //-->";
	if (mysqli_affected_rows($link)>0) 
		{
		$success_msg=_QXZ("NEW TEMPLATE CREATED SUCCESSFULLY");
		if (!$custom_fields_layout) 
			{
			$success_msg.="<BR/>**"._QXZ("NO CUSTOM FIELDS ASSIGNED")."**";
			}
		}
	else 
		{
		$errno = mysqli_errno($link);
		if ($errno > 0)
			{$error = mysqli_error($link);}
		$error_msg=_QXZ("TEMPLATE CREATION FAILED")."<br>\n$errno - $error<br>\n[$ins_stmt]";
		}
	}
else if ( ($delete_template == _QXZ("DELETE TEMPLATE")) and (strlen($template_id) > 0) ) 
	{
	$delete_stmt="delete from vicidial_custom_leadloader_templates where template_id='$template_id'";
	$delete_rslt=mysql_to_mysqli($delete_stmt, $link);
	}


$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

$camp_lists='';
$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if (!preg_match('/\-ALL/i', $LOGallowed_campaigns))
	{
#	echo "<BR/>**$LOGallowed_campaigns**";
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
#	echo "<BR/>##$rawLOGallowed_campaignsSQL##";
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

$script_name = getenv("SCRIPT_NAME");
$server_name = getenv("SERVER_NAME");
$server_port = getenv("SERVER_PORT");
if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
else {$HTTPprotocol = 'http://';}
$admDIR = "$HTTPprotocol$server_name$script_name";
$admDIR = preg_replace('/AST_admin_template_maker\.php/i', '',$admDIR);
$admDIR = "/vicidial/";
$admSCR = 'admin.php';
# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

require("screen_colors.php");
?>
<html>
<head>
<title>ADMIN: Lead Loader Template Maker</title>
</head>

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<script language="Javascript">
var form_file_name='';

function init() {
	document.getElementById("listloader_file_primer").onsubmit=function() {
		document.getElementById("listloader_file_primer").target = "file_holder";
		alert(document.getElementById("listloader_file_primer").target);
	}
}
function PrimeFile() {
	document.forms[0].submit();
}

function DisplayTemplateFields(list_id) {
	if (list_id!='') {
		var custom_fields_enabled=1;
	} else {
		var custom_fields_enabled=0;
	}
	var template_file_type=document.getElementById("template_file_type").value;
	var delimiter=document.getElementById("template_file_delimiter").value;
	var buffer=document.getElementById("template_file_buffer").value;

		var endstr=document.forms[0].sample_template_file.value.lastIndexOf('\\');
		if (endstr>-1) 
			{
			endstr++;
			var filename=document.forms[0].sample_template_file.value.substring(endstr);
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
		var vs_query = "&custom_fields_enabled="+custom_fields_enabled+"&list_id="+list_id+"&delimiter="+delimiter+"&buffer="+buffer+"&sample_template_file_name="+form_file_name;
		xmlhttp.open('POST', 'leadloader_template_display.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(vs_query); 
		xmlhttp.onreadystatechange = function() { 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var StatSpanText = null;
				StatSpanText = xmlhttp.responseText;
				var output_array=StatSpanText.split("\|\|\|");
				document.getElementById("statuses_display").innerHTML = output_array[0];
				document.getElementById("field_display").innerHTML = output_array[1];
			}
		}
		delete xmlhttp;
	}

}

function EditTemplate(template_id) {
	if (!template_id || template_id=="")
		{
		return false;
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
		var vs_query = "&template_id="+template_id+"&form_action=update_template";
		// alert(vs_query);
		xmlhttp.open('POST', 'leadloader_template_display.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(vs_query); 
		xmlhttp.onreadystatechange = function() { 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var StatSpanText = null;
				StatSpanText = xmlhttp.responseText;
				// alert(StatSpanText);
				var output_array=StatSpanText.split("\|\|\|");
				// document.getElementById("statuses_display").innerHTML = output_array[0];
				document.getElementById("field_display_edit").innerHTML = output_array[1];

				if (output_array[1]=="") {
					document.getElementById('edit_data_display').style.display = 'none'; 
					document.getElementById('edit_data_display').style.visibility = 'hidden';
				} else {
					document.getElementById('list_data_display').style.display = 'none'; 
					document.getElementById('list_data_display').style.visibility = 'hidden';
					document.getElementById('edit_data_display').style.display = 'block'; 
					document.getElementById('edit_data_display').style.visibility = 'visible';
				}
			}
		}
		delete xmlhttp;
	}
}
function EditTemplateStrings() {
	var vicidial_string="<?php echo $vicidial_listloader_fields; ?>";
    var standard_string = '';
    var custom_string = '';
    for (var i = 0; i<document.listloader_template_edit_form.elements.length; i++) {
		if (document.listloader_template_edit_form.elements[i].type == 'text' && document.listloader_template_edit_form.elements[i].value!="" && document.listloader_template_edit_form.elements[i].name!="template_list_id" && document.listloader_template_edit_form.elements[i].name!="template_id" && document.listloader_template_edit_form.elements[i].name!="template_name" && document.listloader_template_edit_form.elements[i].name!="template_description" && document.listloader_template_edit_form.elements[i].name!="edit_standard_fields_layout" && document.listloader_template_edit_form.elements[i].name!="edit_custom_fields_layout") 
			{
			var field_name=document.listloader_template_edit_form.elements[i].name.replace("_field", "");
			var field_value=document.listloader_template_edit_form.elements[i].value.replace(/\D/g,'');
			var ptn="/\|"+field_name+"\|/gi";
			if (vicidial_string.match(ptn)) 
				{
				standard_string += field_name +","+ field_value + '|';
				} 
			else 
				{
				custom_string += field_name +","+ field_value + '|';
				}
			}
	}
	document.getElementById("edit_standard_fields_layout").value=standard_string;
	document.getElementById("edit_custom_fields_layout").value=custom_string;
}

function DrawTemplateStrings() {
	var vicidial_string="<?php echo $vicidial_listloader_fields; ?>";
    var standard_string = '';
    var custom_string = '';
    for (var i = 0; i<document.listloader_template_form.elements.length; i++) {
		if ((document.listloader_template_form.elements[i].type == 'select-one') && document.listloader_template_form.elements[i].name!="template_list_id") {
			var field_name=document.listloader_template_form.elements[i].name.replace("_field", "");
			var ptn="/\|"+field_name+"\|/gi";
			if (document.listloader_template_form.elements[i].selectedIndex != 0) {
				if (vicidial_string.match(ptn)) {
					standard_string += field_name +","+ document.listloader_template_form.elements[i].options[document.listloader_template_form.elements[i].selectedIndex].value + '|';
				} else {
					custom_string += field_name +","+ document.listloader_template_form.elements[i].options[document.listloader_template_form.elements[i].selectedIndex].value + '|';
				}
			}
		}
	}
	document.getElementById("standard_fields_layout").value=standard_string;
	document.getElementById("custom_fields_layout").value=custom_string;
}
function loadIFrame(form_action, field_value) {
	document.getElementById("template_list_id").disabled=true; // Disable these until the file finishes loading...
	document.getElementById("template_statuses").disabled=true; 

	form_file_name = field_value;
	if (field_value=="") {
		document.getElementById('list_data_display').style.display = 'none'; 
		document.getElementById('list_data_display').style.visibility = 'hidden';
	} else {
		document.getElementById('list_data_display').style.display = 'block'; 
		document.getElementById('list_data_display').style.visibility = 'visible';
		document.getElementById('edit_data_display').style.display = 'none'; 
		document.getElementById('edit_data_display').style.visibility = 'hidden';
	}
	document.forms[0].action="leadloader_template_display.php?form_action="+form_action;
}
function checkForm(form_name) {
	var error_msg = "<?php echo _QXZ("The form cannot be submitted for the following reasons"); ?>: \n";
	if (form_name.template_id.value=="") {error_msg += " - <?php echo _QXZ("Template ID is missing"); ?>\n";}
	else if (form_name.template_id.value.length<2) {error_msg += " - <?php echo _QXZ("Template ID needs to be at least 2 characters long"); ?>\n";}
	if (form_name.template_name.value=="") {error_msg += " - <?php echo _QXZ("Template name is missing"); ?>\n";}
	if (form_name.template_list_id.value=="") {error_msg += " - <?php echo _QXZ("List ID is missing"); ?>\n";}
	if (form_name.standard_fields_layout.value=="" && form_name.custom_fields_layout.value=="") {error_msg += " - <?php echo _QXZ("There does not seem to be a layout"); ?>\n";}
	if (error_msg.length>=80) {alert(error_msg); return false;} else {return true;}
}
function checkEditedForm(form_name) {
	var error_msg = "";
	if (form_name.template_id.value=="") {error_msg += " - <?php echo _QXZ("Template ID is missing"); ?>\n";}
	else if (form_name.template_id.value.length<2) {error_msg += " - <?php echo _QXZ("Template ID needs to be at least 2 characters long"); ?>\n";}
	if (form_name.template_name.value=="") {error_msg += " - <?php echo _QXZ("Template name is missing"); ?>\n";}
	if (form_name.edit_standard_fields_layout.value=="" && form_name.edit_custom_fields_layout.value=="") {error_msg += " - <?php echo _QXZ("There does not seem to be a layout"); ?>\n";}
	if (error_msg.length>0) {alert("<?php echo _QXZ("The form cannot be submitted for the following reasons"); ?>: \n"+error_msg); return false;} else {form_name.submit();}
}
</script>
</script>
<?php

function macfontfix($fontsize) 
	{
	$browser = getenv("HTTP_USER_AGENT");
	$pctype = explode("(", $browser);
	if (preg_match('/Mac/',$pctype[1])) 
		{
		/* Browser is a Mac.  If not Netscape 6, raise fonts */
		$blownbrowser = explode('/', $browser);
		$ver = explode(' ', $blownbrowser[1]);
		$ver = $ver[0];
		if ($ver >= 5.0) return $fontsize; else return ($fontsize+2);
		} 
	else return $fontsize;	/* Browser is not a Mac - don't touch fonts */
	}

echo "<style type=\"text/css\">\n
<!--\n
.title {  font-family: Arial, Helvetica, sans-serif; font-size: ".macfontfix(18)."pt}\n
.standard {  font-family: Arial, Helvetica, sans-serif; font-size: ".macfontfix(10)."pt}\n
.small_standard {  font-family: Arial, Helvetica, sans-serif; font-size: ".macfontfix(8)."pt}\n
.tiny_standard {  font-family: Arial, Helvetica, sans-serif; font-size: ".macfontfix(6)."pt}\n
.standard_bold {  font-family: Arial, Helvetica, sans-serif; font-size: ".macfontfix(10)."pt; font-weight: bold}\n
.standard_header {  font-family: Arial, Helvetica, sans-serif; font-size: ".macfontfix(14)."pt; font-weight: bold}\n
.standard_bold_highlight {  font-family: Arial, Helvetica, sans-serif; font-size: ".macfontfix(10)."pt; font-weight: bold; color: white; BACKGROUND-COLOR: black}\n
.standard_bold_blue_highlight {  font-family: Arial, Helvetica, sans-serif; font-size: 10pt; font-weight: bold; BACKGROUND-COLOR: blue}\n
A.employee_standard {  font-family: garamond, sans-serif; font-size: ".macfontfix(10)."pt; font-style: normal; font-variant: normal; font-weight: bold; text-decoration: none}\n
.employee_standard {  font-family: garamond, sans-serif; font-size: ".macfontfix(10)."pt; font-weight: bold}\n
.employee_title {  font-family: Garamond, sans-serif; font-size: ".macfontfix(14)."pt; font-weight: bold}\n
\\\\-->\n
</style>\n";
?>
<script language="JavaScript1.2">
function openNewWindow(url) 
	{
	window.open (url,"",'width=700,height=300,scrollbars=yes,menubar=yes,address=yes');
	}
</script>
<body BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<?php
$short_header=1;
require("admin_header.php");
?>
<BR/>
<table border="2" bordercolor="#<?php echo $SSmenu_background; ?>" cellpadding="10" width="900" align="left">
<tr height="20"><th bgcolor="#<?php echo $SSmenu_background; ?>"><font class="standard_bold" color="#FFFFFF"><?php echo _QXZ("Listloader Custom Template Maker"); ?><?php echo "$NWB#vicidial_template_maker$NWE"; ?></font></th></tr>
<tr><td align="center" bgcolor="#<?php echo $SSstd_row4_background; ?>">
<table border=0 cellpadding=15 cellspacing=0 width="90%" align="center" bgcolor="#<?php echo $SSframe_background; ?>">
<?php
if ($error_msg) 
	{
	echo "<tr bgcolor='#990000'>";
	echo "<th colspan='2'><font color='#FFFFFF'>$error_msg</font></th>";
	echo "</tr>";
	}
if ($success_msg) 
	{
	echo "<tr bgcolor='#009900'>";
	echo "<th colspan='2'><font color='#FFFFFF'>$success_msg</font></th>";
	echo "</tr>";
	}
?>
	<tr>
		<th width="50%"><font class="standard_bold"><?php echo _QXZ("Create a new template"); ?></font></th>
		<th width="50%"><font class="standard_bold"><?php echo _QXZ("Edit / delete an existing template"); ?></font></th>
	<tr valign="top">
		<td align="left" width='50%'>
		<form id="listloader_file_primer" action="leadloader_template_display.php?form_action=prime_file" method="post" enctype="multipart/form-data" target="file_holder">
			<font class="standard"><?php echo _QXZ("Sample file fitting template"); ?>:<BR><font size="-2">(<?php echo _QXZ("needed for field assignation"); ?>)</font></font><?php echo "$NWB#template_maker-create_template$NWE"; ?><BR><BR><input type=file name="sample_template_file" value="<?php echo $sample_template_file; ?>" onChange="loadIFrame('prime_file', this.value); this.form.submit();">
		</form>
		</td>
		<td align="left" width='50%'><form action="<?php echo $PHP_SELF; ?>" method="post"><font class="standard"><?php echo _QXZ("Select template to edit/delete"); ?>:</font><?php echo "$NWB#template_maker-delete_template$NWE"; ?><BR><select name="template_id" onChange="loadIFrame('hide_new_template_form', '')">
<?php
$template_stmt="SELECT template_id, template_name FROM vicidial_custom_leadloader_templates WHERE list_id IN (SELECT list_id FROM vicidial_lists $whereLOGallowed_campaignsSQL) ORDER BY template_id asc;";
$template_rslt=mysql_to_mysqli($template_stmt, $link);
if (mysqli_num_rows($template_rslt)>0) {
	if ($update_template) {echo "<option value='$update_template' selected>$update_template</option>\n";} else {echo "<option value='' selected>--"._QXZ("Choose an existing template")."--</option>\n";}
	while ($template_row=mysqli_fetch_array($template_rslt)) {
		echo "<option value='$template_row[template_id]'>$template_row[template_id] - $template_row[template_name]</option>\n";
	}
} else {
	echo "<option value='' selected>--"._QXZ("No templates exist")."--</option>\n";
}
?>
		</select><BR/><BR/><input type="button" value="<?php echo _QXZ("EDIT TEMPLATE"); ?>" style='background-color:#<?php echo $SSbutton_color; ?>' name="edit_template" style="align:right; width: 150px" onClick="EditTemplate(this.form.template_id.value)">&nbsp;&nbsp;<input type="submit" value="<?php echo _QXZ("DELETE TEMPLATE"); ?>" style='background-color:#<?php echo $SSbutton_color; ?>' name="delete_template" style="align:center; width: 150px">
		</form></td>
	</tr>
	<tr>
		<td align=left colspan="2"><font size=1> &nbsp; &nbsp; &nbsp; &nbsp; <a href="admin.php?ADD=100" target="_parent"><?php echo _QXZ("BACK TO ADMIN"); ?></a> &nbsp; &nbsp; &nbsp; &nbsp; <a href="./admin_listloader_fourth_gen.php"><?php echo _QXZ("Go to Lead Loader"); ?></a> &nbsp; &nbsp; </font></td>
	</tr>
	</tr>
</table>
</form>
<BR/>
<iframe id="file_holder" style="visibility:hidden;display:none" name="file_holder"></iframe>
<span id="edit_data_display" style="visibility:hidden;display:none">
<form id="listloader_template_edit_form" name="listloader_template_edit_form" action="<?php echo $PHP_SELF; ?>" onSubmit="return false;" method="post" enctype="multipart/form-data">
<table border=0 cellpadding=3 cellspacing=0 width="100%" align="center">
	<tr>
		<th colspan="2" bgcolor="#<?php echo $SSmenu_background; ?>"><font class="standard" color="white"><?php echo _QXZ("Edit template form"); ?></font><?php echo "$NWB#template_maker-editing_columns$NWE"; ?>
</th>
	</tr>
	<tr valign="top">
		<td border="1" align="center" bgcolor="#<?php echo $SSframe_background; ?>" width="50%"><font class="standard"><?php echo _QXZ("Standard Field"); ?></font></td>
		<td border="0" align="center" bgcolor="#<?php echo $SSalt_row1_background; ?>" width="50%"><font class="standard"><?php echo _QXZ("Custom Field"); ?></font></td>
	</tr>
	<tr valign="top">
		<td colspan="2" align="center">
		<span id="field_display_edit" name="field_display_edit"><font class="standard_bold" color='red'>**<?php echo _QXZ("Select a template from the drop down menu above to show columns"); ?>**</font></span>
		</td>
	</tr>
</table>
<input type="hidden" id="edit_standard_fields_layout" name="edit_standard_fields_layout">
<input type="hidden" id="edit_custom_fields_layout" name="edit_custom_fields_layout">
<input type="hidden" id="submit_edited_template" name="submit_edited_template" value="1">
</form>
</span>

<span id="list_data_display" style="visibility:hidden;display:none">
<form id="listloader_template_form" name="listloader_template_form" action="<?php echo $PHP_SELF; ?>" method="post" enctype="multipart/form-data">
<table border=0 cellpadding=3 cellspacing=0 width="100%" align="center">
	<tr>
		<th colspan="2" bgcolor="#<?php echo $SSmenu_background; ?>"><font class="standard" color="white"><?php echo _QXZ("New template form"); ?></font></th>
	</tr>
	<tr bgcolor="#<?php echo $SSframe_background; ?>">
		<td align="right" width='25%'><font class="standard"><?php echo _QXZ("Template ID"); ?>:</font></td>
		<td align="left" width='75%'><input type='text' name='template_id' size='15' maxlength='20'><?php echo "$NWB#template_maker-template_id$NWE"; ?></td>
	</tr>
	<tr bgcolor="#<?php echo $SSframe_background; ?>">
		<td align="right" width='25%'><font class="standard"><?php echo _QXZ("Template Name"); ?>:</font></td>
		<td align="left" width='75%'><input type='text' name='template_name' size='15' maxlength='30'><?php echo "$NWB#template_maker-template_name$NWE"; ?></td>
	</tr>
	<tr bgcolor="#<?php echo $SSframe_background; ?>">
		<td align="right" width='25%'><font class="standard"><?php echo _QXZ("Template Description"); ?>:</font></td>
		<td align="left" width='75%'><input type='text' name='template_description' size='50' maxlength='255'><?php echo "$NWB#template_maker-template_description$NWE"; ?></td>
	</tr>
	<tr bgcolor="#<?php echo $SSframe_background; ?>">
		<td width='25%' align="right"><font class="standard"><?php echo _QXZ("List ID template will load into"); ?>:</font></td>
		<td width='75%'>
			<select id='template_list_id' name='template_list_id' onChange="DisplayTemplateFields(this.value)">
			<option value=''>--<?php echo _QXZ("Select a list below"); ?>--</option>
			<?php
			$stmt="SELECT list_id, list_name from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$num_rows = mysqli_num_rows($rslt);

			$count=0;
			while ( $num_rows > $count ) 
				{
				$row = mysqli_fetch_row($rslt);
				echo "\t\t\t<option value='$row[0]'>$row[0] - $row[1]</option>\n";
				$count++;
				}
			?>
			</select></font><?php echo "$NWB#template_maker-list_id$NWE"; ?>
		</td>
	</tr>
	<tr bgcolor="#<?php echo $SSframe_background; ?>">
		<td width='25%' align="right"><font class="standard"><?php echo _QXZ("Statuses to dedupe against (optional)"); ?>:</font></td>
		<td width='75%'>
		<span id='statuses_display'>
			<select id='template_statuses' name='template_statuses[]' size=5 multiple>
			<option value='--ALL--' selected>--<?php echo _QXZ("ALL DISPOSITIONS"); ?>--</option>
			<?php
			$stmt="SELECT status, status_name from vicidial_statuses order by status;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$num_rows = mysqli_num_rows($rslt);

			$count=0;
			while ( $num_rows > $count ) 
				{
				$row = mysqli_fetch_row($rslt);
				echo "\t\t\t<option value='$row[0]'>$row[0] - $row[1]</option>\n";
				$count++;
				}
			?>
			</select></font><?php echo "$NWB#template_maker-list_id$NWE"; ?>
		</span>
		</td>
	</tr>
	<tr bgcolor="#<?php echo $SSframe_background; ?>">
		<th colspan='2'><input <?php echo "style='background-color:#$SSbutton_color'"; ?> type='submit' name='submit_template' onClick="return checkForm(this.form)" value='<?php echo _QXZ("SUBMIT TEMPLATE"); ?>'></th>
	</tr>
<tr bgcolor="#<?php echo $SSframe_background; ?>"><td align="center" colspan=2>
<table border=0 cellpadding=3 cellspacing=1 width="100%" align="center">
	<tr>
		<th colspan="2" bgcolor="#<?php echo $SSmenu_background; ?>"><font class="standard" color="white"><?php echo _QXZ("Assign columns to file fields"); ?><BR/><font size='-2'>(<?php echo _QXZ("selecting a different list will reset columns"); ?>)</font></font><?php echo "$NWB#template_maker-assign_columns$NWE"; ?></th>
	</tr>
	<tr valign="top">
		<td border="1" align="center" bgcolor="#<?php echo $SSframe_background; ?>" width="50%"><font class="standard"><?php echo _QXZ("Standard Field"); ?></font></td>
		<td border="0" align="center" bgcolor="#<?php echo $SSalt_row1_background; ?>" width="50%"><font class="standard"><?php echo _QXZ("Custom Field"); ?></font></td>
	</tr>
	<tr valign="top">
		<td colspan="2" align="center">
		<span id="field_display" name="field_display"><font class="standard_bold" color='red'>**<?php echo _QXZ("Select a list ID from the drop down menu above to show columns"); ?>**</font></span>
		</td>
	</tr>
</table>
</tr></td>
</table>
<input type="hidden" id="sample_template_file_name" name="sample_template_file_name">
<input type="hidden" id="convert_command" name="convert_command">
<input type="hidden" id="template_file_type" name="template_file_type">
<input type="hidden" id="template_file_delimiter" name="template_file_delimiter">
<input type="hidden" id="template_file_buffer" name="template_file_buffer">
<input type="hidden" id="standard_fields_layout" name="standard_fields_layout">
<input type="hidden" id="custom_fields_layout" name="custom_fields_layout">
</form>
</span>
</body>
</html>
