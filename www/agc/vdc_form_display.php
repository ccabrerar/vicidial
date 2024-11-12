<?php
# vdc_form_display.php
# 
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed display the contents of the FORM tab in the agent 
# interface, as well as take submission of the form submission when the agent 
# dispositions the call
#
# CHANGELOG:
# 100630-1119 - First build of script
# 100703-1124 - Added submit_button,admin_submit fields, which will log to admin log
# 100712-2322 - Added code to log vicidial_list.entry_list_id field if data altered
# 100916-1749 - Added non-lead variable parsing
# 110719-0856 - Added HIDEBLOB type
# 110730-2335 - Added call_id variable
# 111025-1433 - Fixed case sensitivity on list fields
# 120315-1729 - Filtere out single quotes and backslashes from custom fields
# 130328-0012 - Converted ereg to preg functions
# 130402-2256 - Added user_group variable
# 130603-2204 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130615-2155 - Allow qc_enabled user access to this page even if not logged in as an agent
# 130705-1512 - Added optional encrypted passwords compatibility
# 130802-1033 - Changed to PHP mysqli functions
# 140101-2139 - Small fix for admin modify lead page on encrypted password systems
# 140429-2042 - Added TABLEper_call_notes display script variable for form display
# 140810-2119 - Changed to use QXZ function for echoing text
# 141118-1424 - Added agent_email variable
# 141128-0855 - Code cleanup for QXZ functions
# 141216-2116 - Added language settings lookups and user/pass variable standardization
# 150114-2045 - Added list_name variable
# 150312-1502 - Allow for single quotes in vicidial_list data fields
# 150418-1751 - Added fixed fields to submit output, issue #842
# 150512-0617 - Fix for non-latin customer data
# 150609-1923 - Added list_description variable
# 150923-2027 - Added DID custom variables
# 160129-1019 - Added missing pass field to SUBMIT stage form
# 160912-0805 - Added debug, fixed issue with multi-selected values
# 170301-0834 - Added call_id field for custom fields
# 170317-0755 - Added more missing display variables
# 170331-2300 - Added more debug logging
# 170428-1215 - Small fix for admin modify lead display
# 171021-1339 - Fix to update default field if duplicate field in custom fields changed
# 171116-2333 - Added code for duplicate fields
# 180503-1813 - Added code for SWITCH field type
# 200406-1137 - Added hide_gender and gender default population
# 201117-2056 - Changes for better compatibility with non-latin data input
# 210211-0146 - Added SOURCESELECT field type
# 210304-1612 - Added READONLY submit_button option
# 210310-1115 - Added BUTTON field type with SubmitRefresh function
# 210315-1747 - Added campaign setting for clear_form
# 210329-2025 - Fixed for consistent custom fields values filtering
# 210404-1522 - Added only_field option for refresh of already-loaded custom form SOURCESELECT element
# 210506-1825 - Fix for SELECTSOURCE reloading issue
# 210616-2037 - Added optional CORS support, see options.php for details
# 210825-0902 - Fix for XSS security issue
# 230518-1053 - Added in-group and campaign custom fields 1-5, for script/webform/dispo-call-url use
#

$version = '2.14-45';
$build = '230518-1053';
$php_script = 'vdc_form_display.php';

require_once("dbconnect_mysqli.php");
require_once("functions.php");

$bcrypt=1;

if (isset($_GET["lead_id"]))			{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))	{$lead_id=$_POST["lead_id"];}
if (isset($_GET["list_id"]))			{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))	{$list_id=$_POST["list_id"];}
if (isset($_GET["new_list_id"]))			{$new_list_id=$_GET["new_list_id"];}
	elseif (isset($_POST["new_list_id"]))	{$new_list_id=$_POST["new_list_id"];}
if (isset($_GET["user"]))				{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))		{$user=$_POST["user"];}
if (isset($_GET["pass"]))				{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))		{$pass=$_POST["pass"];}
if (isset($_GET["server_ip"]))			{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
if (isset($_GET["session_id"]))				{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))	{$session_id=$_POST["session_id"];}
if (isset($_GET["uniqueid"]))			{$uniqueid=$_GET["uniqueid"];}
	elseif (isset($_POST["uniqueid"]))	{$uniqueid=$_POST["uniqueid"];}
if (isset($_GET["stage"]))				{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))		{$stage=$_POST["stage"];}
if (isset($_GET["submit_button"]))			{$submit_button=$_GET["submit_button"];}
	elseif (isset($_POST["submit_button"]))	{$submit_button=$_POST["submit_button"];}
if (isset($_GET["admin_submit"]))			{$admin_submit=$_GET["admin_submit"];}
	elseif (isset($_POST["admin_submit"]))	{$admin_submit=$_POST["admin_submit"];}
if (isset($_GET["bgcolor"]))			{$bgcolor=$_GET["bgcolor"];}
	elseif (isset($_POST["bgcolor"]))	{$bgcolor=$_POST["bgcolor"];}

if (isset($_GET["campaign"]))	{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))	{$campaign=$_POST["campaign"];}
if (isset($_GET["phone_login"]))	{$phone_login=$_GET["phone_login"];}
	elseif (isset($_POST["phone_login"]))	{$phone_login=$_POST["phone_login"];}
if (isset($_GET["original_phone_login"]))	{$original_phone_login=$_GET["original_phone_login"];}
	elseif (isset($_POST["original_phone_login"]))	{$original_phone_login=$_POST["original_phone_login"];}
if (isset($_GET["phone_pass"]))	{$phone_pass=$_GET["phone_pass"];}
	elseif (isset($_POST["phone_pass"]))	{$phone_pass=$_POST["phone_pass"];}
if (isset($_GET["fronter"]))	{$fronter=$_GET["fronter"];}
	elseif (isset($_POST["fronter"]))	{$fronter=$_POST["fronter"];}
if (isset($_GET["closer"]))	{$closer=$_GET["closer"];}
	elseif (isset($_POST["closer"]))	{$closer=$_POST["closer"];}
if (isset($_GET["group"]))	{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))	{$group=$_POST["group"];}
if (isset($_GET["channel_group"]))	{$channel_group=$_GET["channel_group"];}
	elseif (isset($_POST["channel_group"]))	{$channel_group=$_POST["channel_group"];}
if (isset($_GET["SQLdate"]))	{$SQLdate=$_GET["SQLdate"];}
	elseif (isset($_POST["SQLdate"]))	{$SQLdate=$_POST["SQLdate"];}
if (isset($_GET["epoch"]))	{$epoch=$_GET["epoch"];}
	elseif (isset($_POST["epoch"]))	{$epoch=$_POST["epoch"];}
if (isset($_GET["customer_zap_channel"]))	{$customer_zap_channel=$_GET["customer_zap_channel"];}
	elseif (isset($_POST["customer_zap_channel"]))	{$customer_zap_channel=$_POST["customer_zap_channel"];}
if (isset($_GET["customer_server_ip"]))	{$customer_server_ip=$_GET["customer_server_ip"];}
	elseif (isset($_POST["customer_server_ip"]))	{$customer_server_ip=$_POST["customer_server_ip"];}
if (isset($_GET["server_ip"]))	{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
if (isset($_GET["SIPexten"]))	{$SIPexten=$_GET["SIPexten"];}
	elseif (isset($_POST["SIPexten"]))	{$SIPexten=$_POST["SIPexten"];}
if (isset($_GET["session_id"]))	{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))	{$session_id=$_POST["session_id"];}
if (isset($_GET["phone"]))	{$phone=$_GET["phone"];}
	elseif (isset($_POST["phone"]))	{$phone=$_POST["phone"];}
if (isset($_GET["parked_by"]))	{$parked_by=$_GET["parked_by"];}
	elseif (isset($_POST["parked_by"]))	{$parked_by=$_POST["parked_by"];}
if (isset($_GET["dialed_number"]))	{$dialed_number=$_GET["dialed_number"];}
	elseif (isset($_POST["dialed_number"]))	{$dialed_number=$_POST["dialed_number"];}
if (isset($_GET["dialed_label"]))	{$dialed_label=$_GET["dialed_label"];}
	elseif (isset($_POST["dialed_label"]))	{$dialed_label=$_POST["dialed_label"];}
if (isset($_GET["camp_script"]))	{$camp_script=$_GET["camp_script"];}
	elseif (isset($_POST["camp_script"]))	{$camp_script=$_POST["camp_script"];}
if (isset($_GET["in_script"]))	{$in_script=$_GET["in_script"];}
	elseif (isset($_POST["in_script"]))	{$in_script=$_POST["in_script"];}
if (isset($_GET["script_width"]))	{$script_width=$_GET["script_width"];}
	elseif (isset($_POST["script_width"]))	{$script_width=$_POST["script_width"];}
if (isset($_GET["script_height"]))	{$script_height=$_GET["script_height"];}
	elseif (isset($_POST["script_height"]))	{$script_height=$_POST["script_height"];}
if (isset($_GET["fullname"]))	{$fullname=$_GET["fullname"];}
	elseif (isset($_POST["fullname"]))	{$fullname=$_POST["fullname"];}
if (isset($_GET["agent_email"]))	{$agent_email=$_GET["agent_email"];}
	elseif (isset($_POST["agent_email"]))	{$agent_email=$_POST["agent_email"];}
if (isset($_GET["recording_filename"]))	{$recording_filename=$_GET["recording_filename"];}
	elseif (isset($_POST["recording_filename"]))	{$recording_filename=$_POST["recording_filename"];}
if (isset($_GET["recording_id"]))	{$recording_id=$_GET["recording_id"];}
	elseif (isset($_POST["recording_id"]))	{$recording_id=$_POST["recording_id"];}
if (isset($_GET["user_custom_one"]))	{$user_custom_one=$_GET["user_custom_one"];}
	elseif (isset($_POST["user_custom_one"]))	{$user_custom_one=$_POST["user_custom_one"];}
if (isset($_GET["user_custom_two"]))	{$user_custom_two=$_GET["user_custom_two"];}
	elseif (isset($_POST["user_custom_two"]))	{$user_custom_two=$_POST["user_custom_two"];}
if (isset($_GET["user_custom_three"]))	{$user_custom_three=$_GET["user_custom_three"];}
	elseif (isset($_POST["user_custom_three"]))	{$user_custom_three=$_POST["user_custom_three"];}
if (isset($_GET["user_custom_four"]))	{$user_custom_four=$_GET["user_custom_four"];}
	elseif (isset($_POST["user_custom_four"]))	{$user_custom_four=$_POST["user_custom_four"];}
if (isset($_GET["user_custom_five"]))	{$user_custom_five=$_GET["user_custom_five"];}
	elseif (isset($_POST["user_custom_five"]))	{$user_custom_five=$_POST["user_custom_five"];}
if (isset($_GET["camp_custom_one"]))	{$camp_custom_one=$_GET["camp_custom_one"];}
	elseif (isset($_POST["camp_custom_one"]))	{$camp_custom_one=$_POST["camp_custom_one"];}
if (isset($_GET["camp_custom_two"]))	{$camp_custom_two=$_GET["camp_custom_two"];}
	elseif (isset($_POST["camp_custom_two"]))	{$camp_custom_two=$_POST["camp_custom_two"];}
if (isset($_GET["camp_custom_three"]))	{$camp_custom_three=$_GET["camp_custom_three"];}
	elseif (isset($_POST["camp_custom_three"]))	{$camp_custom_three=$_POST["camp_custom_three"];}
if (isset($_GET["camp_custom_four"]))	{$camp_custom_four=$_GET["camp_custom_four"];}
	elseif (isset($_POST["camp_custom_four"]))	{$camp_custom_four=$_POST["camp_custom_four"];}
if (isset($_GET["camp_custom_five"]))	{$camp_custom_five=$_GET["camp_custom_five"];}
	elseif (isset($_POST["camp_custom_five"]))	{$camp_custom_five=$_POST["camp_custom_five"];}
if (isset($_GET["preset_number_a"]))	{$preset_number_a=$_GET["preset_number_a"];}
	elseif (isset($_POST["preset_number_a"]))	{$preset_number_a=$_POST["preset_number_a"];}
if (isset($_GET["preset_number_b"]))	{$preset_number_b=$_GET["preset_number_b"];}
	elseif (isset($_POST["preset_number_b"]))	{$preset_number_b=$_POST["preset_number_b"];}
if (isset($_GET["preset_number_c"]))	{$preset_number_c=$_GET["preset_number_c"];}
	elseif (isset($_POST["preset_number_c"]))	{$preset_number_c=$_POST["preset_number_c"];}
if (isset($_GET["preset_number_d"]))	{$preset_number_d=$_GET["preset_number_d"];}
	elseif (isset($_POST["preset_number_d"]))	{$preset_number_d=$_POST["preset_number_d"];}
if (isset($_GET["preset_number_e"]))	{$preset_number_e=$_GET["preset_number_e"];}
	elseif (isset($_POST["preset_number_e"]))	{$preset_number_e=$_POST["preset_number_e"];}
if (isset($_GET["preset_number_f"]))	{$preset_number_f=$_GET["preset_number_f"];}
	elseif (isset($_POST["preset_number_f"]))	{$preset_number_f=$_POST["preset_number_f"];}
if (isset($_GET["preset_dtmf_a"]))	{$preset_dtmf_a=$_GET["preset_dtmf_a"];}
	elseif (isset($_POST["preset_dtmf_a"]))	{$preset_dtmf_a=$_POST["preset_dtmf_a"];}
if (isset($_GET["preset_dtmf_b"]))	{$preset_dtmf_b=$_GET["preset_dtmf_b"];}
	elseif (isset($_POST["preset_dtmf_b"]))	{$preset_dtmf_b=$_POST["preset_dtmf_b"];}
if (isset($_GET["did_id"]))				{$did_id=$_GET["did_id"];}
	elseif (isset($_POST["did_id"]))	{$did_id=$_POST["did_id"];}
if (isset($_GET["did_extension"]))			{$did_extension=$_GET["did_extension"];}
	elseif (isset($_POST["did_extension"]))	{$did_extension=$_POST["did_extension"];}
if (isset($_GET["did_pattern"]))			{$did_pattern=$_GET["did_pattern"];}
	elseif (isset($_POST["did_pattern"]))	{$did_pattern=$_POST["did_pattern"];}
if (isset($_GET["did_description"]))			{$did_description=$_GET["did_description"];}
	elseif (isset($_POST["did_description"]))	{$did_description=$_POST["did_description"];}
if (isset($_GET["closecallid"]))			{$closecallid=$_GET["closecallid"];}
	elseif (isset($_POST["closecallid"]))	{$closecallid=$_POST["closecallid"];}
if (isset($_GET["xfercallid"]))				{$xfercallid=$_GET["xfercallid"];}
	elseif (isset($_POST["xfercallid"]))	{$xfercallid=$_POST["xfercallid"];}
if (isset($_GET["agent_log_id"]))			{$agent_log_id=$_GET["agent_log_id"];}
	elseif (isset($_POST["agent_log_id"]))	{$agent_log_id=$_POST["agent_log_id"];}
if (isset($_GET["call_id"]))			{$call_id=$_GET["call_id"];}
	elseif (isset($_POST["call_id"]))	{$call_id=$_POST["call_id"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["web_vars"]))			{$web_vars=$_GET["web_vars"];}
	elseif (isset($_POST["web_vars"]))	{$web_vars=$_POST["web_vars"];}
if (isset($_GET["bcrypt"]))				{$bcrypt=$_GET["bcrypt"];}
	elseif (isset($_POST["bcrypt"]))	{$bcrypt=$_POST["bcrypt"];}
if (isset($_GET["called_count"]))			{$called_count=$_GET["called_count"];}
	elseif (isset($_POST["called_count"]))	{$called_count=$_POST["called_count"];}
if (isset($_GET["list_name"]))			{$list_name=$_GET["list_name"];}
	elseif (isset($_POST["list_name"]))	{$list_name=$_POST["list_name"];}
if (isset($_GET["list_description"]))			{$list_description=$_GET["list_description"];}
	elseif (isset($_POST["list_description"]))	{$list_description=$_POST["list_description"];}
if (isset($_GET["did_custom_one"]))				{$did_custom_one=$_GET["did_custom_one"];}
	elseif (isset($_POST["did_custom_one"]))	{$did_custom_one=$_POST["did_custom_one"];}
if (isset($_GET["did_custom_two"]))				{$did_custom_two=$_GET["did_custom_two"];}
	elseif (isset($_POST["did_custom_two"]))	{$did_custom_two=$_POST["did_custom_two"];}
if (isset($_GET["did_custom_three"]))			{$did_custom_three=$_GET["did_custom_three"];}
	elseif (isset($_POST["did_custom_three"]))	{$did_custom_three=$_POST["did_custom_three"];}
if (isset($_GET["did_custom_four"]))			{$did_custom_four=$_GET["did_custom_four"];}
	elseif (isset($_POST["did_custom_four"]))	{$did_custom_four=$_POST["did_custom_four"];}
if (isset($_GET["did_custom_five"]))			{$did_custom_five=$_GET["did_custom_five"];}
	elseif (isset($_POST["did_custom_five"]))	{$did_custom_five=$_POST["did_custom_five"];}
if (isset($_GET["ig_custom_one"]))				{$ig_custom_one=$_GET["ig_custom_one"];}
	elseif (isset($_POST["ig_custom_one"]))	{$ig_custom_one=$_POST["ig_custom_one"];}
if (isset($_GET["ig_custom_two"]))				{$ig_custom_two=$_GET["ig_custom_two"];}
	elseif (isset($_POST["ig_custom_two"]))	{$ig_custom_two=$_POST["ig_custom_two"];}
if (isset($_GET["ig_custom_three"]))			{$ig_custom_three=$_GET["ig_custom_three"];}
	elseif (isset($_POST["ig_custom_three"]))	{$ig_custom_three=$_POST["ig_custom_three"];}
if (isset($_GET["ig_custom_four"]))			{$ig_custom_four=$_GET["ig_custom_four"];}
	elseif (isset($_POST["ig_custom_four"]))	{$ig_custom_four=$_POST["ig_custom_four"];}
if (isset($_GET["ig_custom_five"]))			{$ig_custom_five=$_GET["ig_custom_five"];}
	elseif (isset($_POST["ig_custom_five"]))	{$ig_custom_five=$_POST["ig_custom_five"];}
if (isset($_GET["hide_gender"]))			{$hide_gender=$_GET["hide_gender"];}
	elseif (isset($_POST["hide_gender"]))	{$hide_gender=$_POST["hide_gender"];}
if (isset($_GET["button_action"]))			{$button_action=$_GET["button_action"];}
	elseif (isset($_POST["button_action"]))	{$button_action=$_POST["button_action"];}
if (isset($_GET["only_field"]))				{$only_field=$_GET["only_field"];}
	elseif (isset($_POST["only_field"]))	{$only_field=$_POST["only_field"];}
if (isset($_GET["source_field"]))			{$source_field=$_GET["source_field"];}
	elseif (isset($_POST["source_field"]))	{$source_field=$_POST["source_field"];}
if (isset($_GET["source_field_value"]))				{$source_field_value=$_GET["source_field_value"];}
	elseif (isset($_POST["source_field_value"]))	{$source_field_value=$_POST["source_field_value"];}
if (isset($_GET["orig_URL"]))			{$orig_URL=$_GET["orig_URL"];}
	elseif (isset($_POST["orig_URL"]))	{$orig_URL=$_POST["orig_URL"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

if ($bcrypt == 'OFF')
	{$bcrypt=0;}

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

if ($stage=='WELCOME')
	{echo "FORM"; exit;}

$txt = '.txt';
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$MT[0]='';
$agents='@agents';
$script_height = ($script_height - 20);
if (strlen($bgcolor) < 6) {$bgcolor='FFFFFF';}
$startMS = microtime();
$ip = getenv("REMOTE_ADDR");
$query_string = getenv("QUERY_STRING");
$REQUEST_URI = getenv("REQUEST_URI");
$POST_URI = '';
foreach($_POST as $key=>$value)
	{$POST_URI .= '&'.$key.'='.$value;}
if (strlen($POST_URI)>1)
	{$POST_URI = preg_replace("/^&/",'',$POST_URI);}
$REQUEST_URI = preg_replace("/'|\"|\\\\|;/","",$REQUEST_URI);
$POST_URI = preg_replace("/'|\"|\\\\|;/","",$POST_URI);
if ( (strlen($query_string) < 1) and (strlen($POST_URI) > 2) )
	{$query_string = $POST_URI;}
if ( (strlen($query_string) > 0) and (strlen($POST_URI) > 2) )
	{$query_string .= "&GET-AND-POST=Y&".$POST_URI;}
$CL=':';
$script_name = getenv("SCRIPT_NAME");
$server_name = getenv("SERVER_NAME");
$server_port = getenv("SERVER_PORT");
if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($server_port == '80') or ($server_port == '443') ) {$server_port='';}
else {$server_port = "$CL$server_port";}
$vdcPAGE = "$HTTPprotocol$server_name$server_port$script_name";
$vdcURL = $vdcPAGE . '?' . $query_string;

$vicidial_list_fields = '|lead_id|entry_date|vendor_lead_code|source_id|list_id|gmt_offset_now|called_since_last_reset|phone_code|phone_number|title|first_name|middle_initial|last_name|address1|address2|address3|city|state|province|postal_code|country_code|gender|date_of_birth|alt_phone|email|security_phrase|comments|called_count|last_local_call_time|rank|owner|';

# default optional vars if not set
if (!isset($format))   {$format="text";}
	if ($format == 'debug')	{$DB=1;}
if (!isset($ACTION))   {$ACTION="custom_form_frame";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}

$IFRAME=0;
$SUBMIT_only=0;

$user = preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass = preg_replace("/\'|\"|\\\\|;| /","",$pass);

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$stmt = "SELECT use_non_latin,timeclock_end_of_day,agentonly_callback_campaign_lock,custom_fields_enabled,enable_languages,language_method,agent_debug_logging,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06005',$user,$server_ip,$session_name,$one_mysql_log);}
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =							$row[0];
	$timeclock_end_of_day =					$row[1];
	$agentonly_callback_campaign_lock =		$row[2];
	$custom_fields_enabled =				$row[3];
	$SSenable_languages =					$row[4];
	$SSlanguage_method =					$row[5];
	$SSagent_debug_logging =				$row[6];
	$SSallow_web_debug =					$row[7];
	}
if ($SSallow_web_debug < 1) {$DB=0;}

$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06002',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}
##### END SETTINGS LOOKUP #####
###########################################

$agent_email = preg_replace("/\<|\>|\"|\\\\|;/",'-',$agent_email);
$agent_log_id = preg_replace("/\<|\>|\"|\\\\|;/",'-',$agent_log_id);
$agent_log_id = preg_replace('/[^0-9]/', '', $agent_log_id);
$button_action = preg_replace('/[^-_0-9a-zA-Z]/','',$button_action);
$call_id = preg_replace('/[^-_\.0-9a-zA-Z]/','',$call_id);
$called_count = preg_replace("/\<|\>|\"|\\\\|;/",'-',$called_count);
$camp_script = preg_replace("/\<|\>|\"|\\\\|;/",'-',$camp_script);
$channel_group = preg_replace("/\<|\>|\"|\\\\|;/",'-',$channel_group);
$closecallid = preg_replace("/[^-_0-9a-zA-Z]/",'-',$closecallid);
$closer = preg_replace("/\<|\>|\"|\\\\|;/",'-',$closer);
$customer_server_ip = preg_replace("/\<|\>|\"|\\\\|;/",'-',$customer_server_ip);
$customer_zap_channel = preg_replace("/\<|\>|\"|\\\\|;/",'-',$customer_zap_channel);
$DB = preg_replace("/\"|\\\\|;/",'-',$DB);
$dialed_label = preg_replace("/\<|\>|\"|\\\\|;/",'-',$dialed_label);
$dialed_number = preg_replace("/\<|\>|\"|\\\\|;/",'-',$dialed_number);
$did_custom_five  = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_custom_five );
$did_custom_four = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_custom_four);
$did_custom_one = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_custom_one);
$did_custom_three = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_custom_three);
$did_custom_two = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_custom_two);
$did_description = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_description);
$did_extension = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_extension);
$did_id = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_id);
$did_pattern = preg_replace("/\<|\>|\"|\\\\|;/",'-',$did_pattern);
$epoch = preg_replace("/\<|\>|\"|\\\\|;/",'-',$epoch);
$fronter = preg_replace("/\<|\>|\"|\\\\|;/",'-',$fronter);
$fullname = preg_replace("/\<|\>|\"|\\\\|;/",'-',$fullname);
$in_script = preg_replace("/\<|\>|\"|\\\\|;/",'-',$in_script);
$lead_id = preg_replace('/[^0-9]/', '', $lead_id);
$list_id = preg_replace('/[^0-9]/', '', $list_id);
$new_list_id = preg_replace('/[^0-9]/', '', $new_list_id);
$only_field = preg_replace('/[^-_0-9a-zA-Z]/','',$only_field);
$original_phone_login = preg_replace("/\<|\>|\"|\\\\|;/",'-',$original_phone_login);
$parked_by = preg_replace("/\<|\>|\"|\\\\|;/",'-',$parked_by);
$pass = preg_replace("/\<|\>|\"|\\\\|;/",'-',$pass);
$phone = preg_replace("/\<|\>|\"|\\\\|;/",'-',$phone);
$phone_login = preg_replace("/\<|\>|\"|\\\\|;/",'-',$phone_login);
$phone_pass = preg_replace("/\<|\>|\"|\\\\|;/",'-',$phone_pass);
$preset_dtmf_a = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_dtmf_a);
$preset_dtmf_b = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_dtmf_b);
$preset_number_a = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_a);
$preset_number_b = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_b);
$preset_number_c = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_c);
$preset_number_d = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_d);
$preset_number_e = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_e);
$preset_number_f = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_f);
$recording_filename = preg_replace("/\<|\>|\"|\\\\|;/",'-',$recording_filename);
$recording_id = preg_replace("/\<|\>|\"|\\\\|;/",'-',$recording_id);
$script_height = preg_replace("/\<|\>|\"|\\\\|;/",'-',$script_height);
$script_width = preg_replace("/\<|\>|\"|\\\\|;/",'-',$script_width);
$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$server_ip);
$session_id = preg_replace('/[^0-9]/','',$session_id);
$SIPexten = preg_replace("/\<|\>|\"|\\\\|;/",'-',$SIPexten);
$source_field = preg_replace('/[^-_0-9a-zA-Z]/','',$source_field);
$source_field_value = preg_replace("/\'|\"|\\\\|;/",'',$source_field_value);
$SQLdate = preg_replace("/\<|\>|\"|\\\\|;/",'-',$SQLdate);
$uniqueid = preg_replace('/[^-_\.0-9a-zA-Z]/','',$uniqueid);
$user_custom_five = preg_replace("/\"|\\\\|;/",'-',$user_custom_five);
$user_custom_four = preg_replace("/\"|\\\\|;/",'-',$user_custom_four);
$user_custom_one = preg_replace("/\"|\\\\|;/",'-',$user_custom_one);
$user_custom_three = preg_replace("/\"|\\\\|;/",'-',$user_custom_three);
$user_custom_two = preg_replace("/\"|\\\\|;/",'-',$user_custom_two);
$user_group = preg_replace("/\<|\>|\"|\\\\|;/",'-',$user_group);
$web_vars = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$web_vars);
$xfercallid = preg_replace("/\<|\>|\"|\\\\|;/",'-',$xfercallid);
$stage = preg_replace('/[^-_\.0-9a-zA-Z]/','',$stage);
$submit_button = preg_replace('/[^-_0-9a-zA-Z]/','',$submit_button);
$admin_submit = preg_replace('/[^-_0-9a-zA-Z]/','',$admin_submit);
$bgcolor = preg_replace("/\<|\>|\'|\"|\\\\|;| /","",$bgcolor);
$bcrypt = preg_replace('/[^-_0-9a-zA-Z]/','',$bcrypt);
$hide_gender = preg_replace('/[^-_0-9a-zA-Z]/','',$hide_gender);
$orig_URL = preg_replace("/\<|\>|\"|\\\\|;/",'-',$orig_URL);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$uniqueid = preg_replace('/[^-_\.0-9a-zA-Z]/','',$uniqueid);
	$campaign = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign);
	$group = preg_replace('/[^-_0-9a-zA-Z]/','',$group);
	$agent_email = preg_replace("/[^-\.\:\/\@\_0-9a-zA-Z]/",'-',$agent_email);
	$did_id = preg_replace("/[^-_0-9a-zA-Z]/",'-',$did_id);
	$did_extension = preg_replace("/[^-_0-9a-zA-Z]/",'-',$did_extension);
	$did_pattern = preg_replace("/[^:\+\*\#\.\_0-9a-zA-Z]/",'-',$did_pattern);
	$did_description = preg_replace("/[^- \.\,\_0-9a-zA-Z]/",'-',$did_description);
	$xfercallid = preg_replace("/[^-_0-9a-zA-Z]/",'-',$xfercallid);
	$agent_log_id = preg_replace("/[^-_0-9a-zA-Z]/",'-',$agent_log_id);
	$user_group = preg_replace("/[^-_0-9a-zA-Z]/",'-',$user_group);
	$called_count = preg_replace("/[^-_0-9a-zA-Z]/",'-',$called_count);
	$did_custom_one = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$did_custom_one);
	$did_custom_two = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$did_custom_two);
	$did_custom_three = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$did_custom_three);
	$did_custom_four = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$did_custom_four);
	$did_custom_five  = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$did_custom_five );
	$list_name = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$list_name);
	$list_description = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$list_description);
	$camp_custom_one = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_one);
	$camp_custom_two = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_two);
	$camp_custom_three = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_three);
	$camp_custom_four = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_four);
	$camp_custom_five  = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$camp_custom_five );
	$ig_custom_one = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$ig_custom_one);
	$ig_custom_two = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$ig_custom_two);
	$ig_custom_three = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$ig_custom_three);
	$ig_custom_four = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$ig_custom_four);
	$ig_custom_five  = preg_replace('/[^- \.\:\/\@\_0-9a-zA-Z]/','-',$ig_custom_five );
	}
else
	{
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$uniqueid = preg_replace('/[^-_\.0-9\p{L}]/u','',$uniqueid);
	$campaign = preg_replace('/[^-_0-9\p{L}]/u','',$campaign);
	$group = preg_replace('/[^-_0-9\p{L}]/u','',$group);
	$agent_email = preg_replace("/[^-\.\:\/\@\_0-9\p{L}]/u",'-',$agent_email);
	$did_id = preg_replace("/[^-_0-9\p{L}]/u",'-',$did_id);
	$did_extension = preg_replace("/[^-_0-9\p{L}]/u",'-',$did_extension);
	$did_pattern = preg_replace("/[^:\+\*\#\.\_0-9\p{L}]/u",'-',$did_pattern);
	$did_description = preg_replace("/[^- \.\,\_0-9\p{L}]/u",'-',$did_description);
	$xfercallid = preg_replace("/[^-_0-9\p{L}]/u",'-',$xfercallid);
	$agent_log_id = preg_replace("/[^-_0-9\p{L}]/u",'-',$agent_log_id);
	$user_group = preg_replace("/[^-_0-9\p{L}]/u",'-',$user_group);
	$called_count = preg_replace("/[^-_0-9\p{L}]/u",'-',$called_count);
	$did_custom_one = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$did_custom_one);
	$did_custom_two = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$did_custom_two);
	$did_custom_three = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$did_custom_three);
	$did_custom_four = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$did_custom_four);
	$did_custom_five  = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$did_custom_five );
	$list_name = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$list_name);
	$list_description = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$list_description);
	$camp_custom_one = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_one);
	$camp_custom_two = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_two);
	$camp_custom_three = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_three);
	$camp_custom_four = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_four);
	$camp_custom_five  = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$camp_custom_five );
	$ig_custom_one = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$ig_custom_one);
	$ig_custom_two = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$ig_custom_two);
	$ig_custom_three = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$ig_custom_three);
	$ig_custom_four = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$ig_custom_four);
	$ig_custom_five  = preg_replace('/[^- \.\:\/\@\_0-9\p{L}]/u','-',$ig_custom_five );
	}

if (strlen($SSagent_debug_logging) > 1)
	{
	if ($SSagent_debug_logging == "$user")
		{$SSagent_debug_logging=1;}
	else
		{$SSagent_debug_logging=0;}
	}

$auth_api_flag = 0;
if ( ($submit_button=='YES') or ($submit_button=='READONLY') or ($admin_submit=='YES') )
	{$auth_api_flag = 1;}

$auth=0;
$auth_message = user_authorization($user,$pass,'',0,$bcrypt,0,$auth_api_flag,'vdc_form_display');
if ($auth_message == 'GOOD')
	{$auth=1;}

$stmt="SELECT count(*) from vicidial_users where user='$user' and ( (modify_leads IN('1','2','3','4')) or (qc_enabled='1') );";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$VUmodify=$row[0];

$stmt="SELECT count(*) from vicidial_live_agents where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LVAactive=$row[0];

if ($custom_fields_enabled < 1)
	{
	echo _QXZ("Custom Fields Disabled:")." |$custom_fields_enabled|\n";
	echo "<form action=./vdc_form_display.php method=POST name=form_custom_fields id=form_custom_fields>\n";
	echo "<input type=hidden name=user id=user value=\"$user\">\n";
	echo "</form>\n";
	exit;
	}

if ( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0) or ( ($LVAactive < 1) and ($VUmodify < 1) ) )
	{
	echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth_message|\n";
	echo "<form action=./vdc_form_display.php method=POST name=form_custom_fields id=form_custom_fields>\n";
	echo "<input type=hidden name=user id=user value=\"$user\">\n";
	echo "</form>\n";
	exit;
	}
else
	{
	# do nothing for now
	}

### BEGIN refresh single field ###
if ( ($stage=='REFRESH_SINGLE_FIELD') and (strlen($only_field) > 0) )
	{
	require_once("functions.php");

	$CFoutput = custom_list_fields_values($lead_id,$list_id,$uniqueid,$user,$DB,$call_id,$did_id,$did_extension,$did_pattern,$did_description,$dialed_number,$dialed_label,$only_field,$source_field,$source_field_value);

	echo "$CFoutput";

	if ($SSagent_debug_logging > 0) 
		{
		$stage .= " $only_field";
		vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);
		}

	exit;
	}
### END refresh single field ###


### BEGIN parse submission of the custom fields form ###
if ($stage=='SUBMIT')
	{
	if ($submit_button=='READONLY')
		{
		echo  _QXZ("You do not have permission to modify leads").": $submit_button\n<BR>\n";
		exit;
		}
	$SUBMIT_only=1;
	if ($SSagent_debug_logging > 0) 
		{
		$stage .= " custom_$list_id";
		}
	$update_sent=0;
	$SUBMIT_output='';
	$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	if ($DB>0) {echo "$stmt";}
	$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06001',$user,$server_ip,$session_name,$one_mysql_log);}
	$tablecount_to_print = mysqli_num_rows($rslt);
	if ($tablecount_to_print > 0) 
		{
		$update_SQL='';
		$VL_update_SQL='';
		$stmt="SELECT field_id,field_label,field_name,field_description,field_rank,field_help,field_type,field_options,field_size,field_max,field_default,field_cost,field_required,multi_position,name_position,field_order,field_duplicate from vicidial_lists_fields where list_id='$list_id' order by field_rank,field_order,field_label;";
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06003',$user,$server_ip,$session_name,$one_mysql_log);}
		$fields_to_print = mysqli_num_rows($rslt);
		$fields_list='';
		$A_field_id = array();
		$A_field_label = array();
		$A_field_name = array();
		$A_field_type = array();
		$A_field_size = array();
		$A_field_max = array();
		$A_field_required = array();
		$A_field_duplicate = array();
		$A_field_value = array();
		$o=0;
		while ($fields_to_print > $o) 
			{
			$new_field_value='';
			$form_field_value='';
			$rowx=mysqli_fetch_row($rslt);
			$A_field_id[$o] =			$rowx[0];
			$A_field_label[$o] =		$rowx[1];
			$A_field_name[$o] =			$rowx[2];
			$A_field_type[$o] =			$rowx[6];
			$A_field_size[$o] =			$rowx[8];
			$A_field_max[$o] =			$rowx[9];
			$A_field_required[$o] =		$rowx[12];
			$A_field_duplicate[$o] =	$rowx[16];
			$A_field_value[$o] =		'';
			$field_name_id =			$A_field_label[$o];

			if (isset($_GET["$field_name_id"]))				{$form_field_value=$_GET["$field_name_id"];}
				elseif (isset($_POST["$field_name_id"]))	{$form_field_value=$_POST["$field_name_id"];}
			$form_field_value = preg_replace("/\"/","",$form_field_value);	// remove double-quote
			$form_field_value = preg_replace("/\\b/","",$form_field_value);	// remove backspaces
			$form_field_value = preg_replace("/\\\\$/","",$form_field_value);	// remove end backslashes

			if ( ($A_field_type[$o]=='MULTI') or ($A_field_type[$o]=='CHECKBOX') or ($A_field_type[$o]=='RADIO') )
				{
				$k=0;
				$multi_count=0;
				if (is_array($form_field_value)) {$multi_count = count($form_field_value);}
				$multi_array = $form_field_value;
				while ($k < $multi_count)
					{
					$new_field_value .= "$multi_array[$k],";
					$k++;
					}
				$form_field_value = preg_replace("/,$/","",$new_field_value);
				}

			if ($A_field_type[$o]=='TIME')
				{
				if (isset($_GET["MINUTE_$field_name_id"]))			{$form_field_valueM=$_GET["MINUTE_$field_name_id"];}
					elseif (isset($_POST["MINUTE_$field_name_id"]))	{$form_field_valueM=$_POST["MINUTE_$field_name_id"];}
				if (isset($_GET["HOUR_$field_name_id"]))			{$form_field_valueH=$_GET["HOUR_$field_name_id"];}
					elseif (isset($_POST["HOUR_$field_name_id"]))	{$form_field_valueH=$_POST["HOUR_$field_name_id"];}
				$form_field_valueH = preg_replace('/[^0-9]/','',$form_field_valueH);
				$form_field_valueM = preg_replace('/[^0-9]/','',$form_field_valueM);
				$form_field_value = "$form_field_valueH:$form_field_valueM:00";
				}

			$A_field_value[$o] = $form_field_value;

			if ( ($A_field_type[$o]=='DISPLAY') or ($A_field_type[$o]=='SCRIPT') or ($A_field_type[$o]=='SWITCH') or ($A_field_type[$o]=='HIDDEN') or ($A_field_type[$o]=='HIDEBLOB') or ($A_field_type[$o]=='READONLY') or ($A_field_type[$o]=='BUTTON') )
				{
                if (($A_field_type[$o]=='DISPLAY') or ($A_field_type[$o]=='SCRIPT') or ($A_field_type[$o]=='SWITCH') or ($A_field_type[$o]=='READONLY') or ($A_field_type[$o]=='BUTTON'))
					{
					$SUBMIT_output .= "<b>$A_field_name[$o]:</b> $A_field_value[$o]<BR>";
					}
				$A_field_value[$o]='----IGNORE----';
				}
			else
				{
				if (!preg_match("/_DUPLICATE_\d\d\d/",$A_field_label[$o]))
					{				
					if (preg_match("/\|$A_field_label[$o]\|/i",$vicidial_list_fields))
						{
						$VL_update_SQL .= "$A_field_label[$o]=\"$A_field_value[$o]\",";
						}
					else
						{
						$update_SQL .= "$A_field_label[$o]=\"$A_field_value[$o]\",";
						}
					}
				$SUBMIT_output .= "<b>$A_field_name[$o]:</b> $A_field_value[$o]<BR>";
				}
			$o++;
			}
	#	$SUBMIT_output .= "$update_SQL<BR>$VL_update_SQL";

		$custom_update_count=0;
		if (strlen($update_SQL)>3)
			{
			$custom_record_lead_count=0;
			$stmt="SELECT count(*) from custom_$list_id where lead_id=$lead_id;";
			if ($DB>0) {echo "$stmt";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06004',$user,$server_ip,$session_name,$one_mysql_log);}
			$fieldleadcount_to_print = mysqli_num_rows($rslt);
			if ($fieldleadcount_to_print > 0) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$custom_record_lead_count =	$rowx[0];
				}
			$update_SQL = preg_replace("/,$/","",$update_SQL);
			$custom_table_update_SQL = "INSERT INTO custom_$list_id SET lead_id=$lead_id,$update_SQL;";
			if ($custom_record_lead_count > 0)
				{$custom_table_update_SQL = "UPDATE custom_$list_id SET $update_SQL where lead_id=$lead_id;";}

			$rslt=mysql_to_mysqli($custom_table_update_SQL, $link);
			$custom_update_count = mysqli_affected_rows($link);
			if ($DB) {echo "$custom_update_count|$custom_table_update_SQL\n";}
			if (!$rslt) {die('Could not execute: ' . mysqli_error($link));}

			$update_sent++;
			}

		if (strlen($VL_update_SQL)>3)
			{
			$custom_update_vl_SQL='';
			if ($custom_update_count > 0)
				{$custom_update_vl_SQL = "entry_list_id='$list_id',";}
			$VL_update_SQL = preg_replace("/,$/","",$VL_update_SQL);
			$list_table_update_SQL = "UPDATE vicidial_list SET $custom_update_vl_SQL $VL_update_SQL where lead_id=$lead_id;";

			$rslt=mysql_to_mysqli($list_table_update_SQL, $link);
			$list_update_count = mysqli_affected_rows($link);
			if ($DB) {echo "$list_update_count|$list_table_update_SQL\n";}
			if (!$rslt) {die('Could not execute: ' . mysqli_error($link));}

			$update_sent++;
			}
		else
			{
			if ($custom_update_count > 0)
				{
				$list_table_update_SQL = "UPDATE vicidial_list SET entry_list_id='$list_id' where lead_id=$lead_id;";
				$rslt=mysql_to_mysqli($list_table_update_SQL, $link);
				$list_update_count = mysqli_affected_rows($link);
				if ($DB) {echo "$list_update_count|$list_table_update_SQL\n";}
				if (!$rslt) {die('Could not execute: ' . mysqli_error($link));}
				}
			}

		if ( ($admin_submit=='YES') and ($update_sent > 0) )
			{
			### LOG INSERTION Admin Log Table ###
			$ip = getenv("REMOTE_ADDR");
			$SQL_log = "$list_table_update_SQL|$custom_table_update_SQL|";
			$SQL_log = preg_replace('/;/','',$SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LEADS', event_type='MODIFY', record_id=$lead_id, event_code='ADMIN MODIFY CUSTOM LEAD', event_sql=\"$SQL_log\", event_notes='$custom_update_count|$list_update_count';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		}
	else
		{$SUBMIT_output .= _QXZ("ERROR: no custom list fields table")."\n";}

	if (strlen($new_list_id) > 1)
		{
		$SUBMIT_only=0;

		### change the entry_list_id for this lead to new list ID
		$new_list_table_update_SQL = "UPDATE vicidial_list SET entry_list_id='$new_list_id' where lead_id=$lead_id;";
		$rslt=mysql_to_mysqli($new_list_table_update_SQL, $link);
		$new_list_update_count = mysqli_affected_rows($link);
		if ($DB) {echo "$new_list_update_count|$new_list_table_update_SQL \n";}
		if (!$rslt) {die('Could not execute: ' . mysqli_error($link));}

		### update the vicidial_live_agents record to update the entry_list_id
		$vla_update_SQL = "UPDATE vicidial_live_agents SET external_update_fields='1',external_update_fields_data='entry_list_id,custom_field_names' where user='$user';";
		$rslt=mysql_to_mysqli($vla_update_SQL, $link);
		$vla_update_count = mysqli_affected_rows($link);
		if ($DB) {echo "$vla_update_count|$vla_update_SQL \n";}
		if (!$rslt) {die('Could not execute: ' . mysqli_error($link));}

		### insert into the vicidial_agent_function_log table that the list switch happened
		$stmt = "INSERT INTO vicidial_agent_function_log set agent_log_id='$agent_log_id',user='$user',function='switch_list',event_time=NOW(),campaign_id='$campaign',user_group='$user_group',lead_id=$lead_id,uniqueid='$uniqueid',caller_code='$call_id',stage='$new_list_id',comments='$list_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06006',$user,$server_ip,$session_name,$one_mysql_log);}

		$list_id = $new_list_id;
		}
	else
		{
		if ( (strlen($orig_URL)> 10) and ($button_action == 'SubmitRefresh') )
			{
			echo "<HTML><HEAD>\n";
			echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"1;URL=$orig_URL\">\n";
			echo "</HEAD><BODY>\n";
			echo "<b>"._QXZ("Committing changes and reloading form")."...</b>\n<BR><BR>\n";
			echo "<a href=\"$orig_URL\">"._QXZ("click here if form is not reloading")."</a>\n";
			}
		else
			{
			$clear_form='';
			$stmt="SELECT clear_form from vicidial_campaigns where campaign_id='$campaign';";
			if ($DB>0) {echo "$stmt";}
			$rslt=mysql_to_mysqli($stmt, $link);
				if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06XXX',$user,$server_ip,$session_name,$one_mysql_log);}
			$camps_to_print = mysqli_num_rows($rslt);
			if ($camps_to_print > 0) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$clear_form =	$rowx[0];
				}
			if ($clear_form == 'ACKNOWLEDGE')
				{
				echo  _QXZ("Custom Form Data Submitted")."\n<BR>\n";
				}
			if ($clear_form == 'ENABLED')
				{
				echo "\n<BR>\n";
				}
			if ($clear_form == 'DISABLED')
				{
				echo  _QXZ("Custom Form Output:")."\n<BR>\n";
				echo "$SUBMIT_output";
				}
			}

		echo "<form action=./vdc_form_display.php method=POST name=form_custom_fields id=form_custom_fields>\n";
		echo "<input type=hidden name=user id=user value=\"$user\">\n";
		echo "<input type=hidden name=pass id=pass value=\"$pass\">\n";
		echo "</form>\n";

		}
	}
### END parse submission of the custom fields form ###
if ($SUBMIT_only < 1)
	{
	if ($SSagent_debug_logging > 0) 
		{
		$stage .= " render $list_id";
		}
	echo "<html>\n";
	echo "<head>\n";
	echo "<!-- VERSION: $version     BUILD: $build    USER: $user   server_ip: $server_ip-->\n";
	echo "<title>". _QXZ("Agent Form Display Script");
	echo "</title>\n";

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "	<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "	<link rel=\"stylesheet\" href=\"./css/vicidial_stylesheet.css\">\n";
	echo "	<script language=\"Javascript\">\n";
	echo "  var orig_URL = '$vdcURL';\n";
	echo "	var ajax_timeout=0;\n";
	echo "	var debug_append='';\n";
	echo "	function open_help(taskspan,taskhelp) \n";
	echo "		{\n";
	echo "		document.getElementById(\"P_\" + taskspan).innerHTML = \" &nbsp; <a href=\\\"javascript:close_help('\" + taskspan + \"','\" + taskhelp + \"');\\\">help-</a><BR> &nbsp; \";\n";
	echo "		document.getElementById(taskspan).innerHTML = \"<B>\" + taskhelp + \"</B>\";\n";
	echo "		document.getElementById(taskspan).style.background = \"#FFFF99\";\n";
	echo "		}\n";
	echo "	function close_help(taskspan,taskhelp) \n";
	echo "		{\n";
	echo "		document.getElementById(\"P_\" + taskspan).innerHTML = \"\";\n";
	echo "		document.getElementById(taskspan).innerHTML = \" &nbsp; <a href=\\\"javascript:open_help('\" + taskspan + \"','\" + taskhelp + \"');\\\">help+</a>\";\n";
	echo "		document.getElementById(taskspan).style.background = \"white\";\n";
	echo "		}\n";
	echo "	function update_default_vd_field(taskfield) \n";
	echo "		{\n";
	echo "		var tempvalue = document.getElementById(taskfield).value;\n";
	echo "		parent.document.getElementById(taskfield).value = tempvalue;\n";
#	echo "		alert('chech refresh:'+ taskfield + ' ' + custom_refresh_fields);\n";
	echo "		var REFRESHfieldREG = new RegExp('|' + taskfield + ' ',\"g\");\n";
	echo "		if (custom_refresh_fields.match(REFRESHfieldREG))\n";
	echo "			{\n";
	echo "			field_refresh(taskfield);\n";
	echo "			}\n";
	echo "		}\n";
	if ($hide_gender > 0)
		{
		echo "	function update_default_vd_select(taskfield) \n";
		echo "		{\n";
		echo "		var taskIndex = document.getElementById(taskfield).selectedIndex;\n";
		echo "		var taskValue = document.getElementById(taskfield).options[taskIndex].value;\n";
		echo "		parent.document.getElementById(taskfield).value = taskValue;\n";
		echo "		var REFRESHfieldREG = new RegExp('|' + taskfield + ' ',\"g\");\n";
		echo "		if (custom_refresh_fields.match(REFRESHfieldREG))\n";
		echo "			{\n";
		echo "			field_refresh(taskfield);\n";
		echo "			}\n";
		echo "		}\n";
		}
	else
		{
		echo "	function update_default_vd_select(taskfield) \n";
		echo "		{\n";
		echo "		var taskIndex = document.getElementById(taskfield).selectedIndex;\n";
		echo "		var taskValue = document.getElementById(taskfield).options[taskIndex].value;\n";
		echo "		var gIndex = 0;\n";
		echo "		if (taskValue == 'U') {var gIndex = 0;}\n";
		echo "		if (taskValue == 'M') {var gIndex = 1;}\n";
		echo "		if (taskValue == 'F') {var gIndex = 2;}\n";
		echo "		parent.document.getElementById('gender_list').selectedIndex = gIndex;\n";
		echo "		var genderIndex = parent.document.getElementById('gender_list').selectedIndex;\n";
		echo "		var genderValue = parent.document.getElementById('gender_list').options[genderIndex].value;\n";
		echo "		parent.document.getElementById('gender').value = genderValue;\n";
		echo "		var REFRESHfieldREG = new RegExp('|' + taskfield + ' ',\"g\");\n";
		echo "		if (custom_refresh_fields.match(REFRESHfieldREG))\n";
		echo "			{\n";
		echo "			field_refresh(taskfield);\n";
		echo "			}\n";
		echo "		}\n";
		}
	echo "	function update_dup_field(taskmasterfield,taskupdatefields,taskupdatecount,taskdefaultflag,taskdefaultfield) \n";
	echo "		{\n";
#	echo "		alert('1: ' + taskmasterfield + ' 2: ' + taskupdatefields + ' 3: ' + taskupdatecount + ' 4: ' + taskdefaultflag);\n";
	echo "		var tempmastervalue = document.getElementById(taskmasterfield).value;\n";
	echo "		var update_fields_array=taskupdatefields.split(\"|\");\n";
	echo "		var temp_ct=0;\n";
	echo "		while (taskupdatecount > temp_ct)\n";
	echo "			{\n";
	echo "			var temp_updating_field = update_fields_array[temp_ct];\n";
	echo "			document.getElementById(temp_updating_field).value = tempmastervalue;\n";
	echo "			temp_ct++;\n";
	echo "			}\n";
	echo "      if (taskdefaultflag=='1')\n";
	echo "			{parent.document.getElementById(taskdefaultfield).value = tempmastervalue;}\n";
	echo "		var REFRESHfieldREG = new RegExp('|' + taskmasterfield + ' ',\"g\");\n";
	echo "		if (custom_refresh_fields.match(REFRESHfieldREG))\n";
	echo "			{\n";
	echo "			field_refresh(taskmasterfield);\n";
	echo "			}\n";
	echo "		}\n";
	echo "	function switch_list(temp_new_list_id)\n";
	echo "		{\n";
#	echo "		alert('new list:'+ temp_new_list_id);\n";
	echo "		if (temp_new_list_id.length > 1)\n";
	echo "			{\n";
	echo "			document.getElementById('new_list_id').value = temp_new_list_id;\n";
	echo "			document.form_custom_fields.submit();\n";
	echo "			}\n";
	echo "		}\n";
	echo "	function form_button_functions(temp_button_action)\n";
	echo "		{\n";
#	echo "		alert('button action:'+ temp_button_action);\n";
	echo "		if (temp_button_action == 'SubmitRefresh')\n";
	echo "			{\n";
	echo "			document.getElementById('button_action').value = temp_button_action;\n";
	echo "			document.form_custom_fields.submit();\n";
	echo "			}\n";
	echo "		}\n";
	echo "	function catchall_field_change(taskfield) \n";
	echo "		{\n";
#	echo "		alert('chech refresh:'+ taskfield + ' ' + custom_refresh_fields);\n";
	echo "		var REFRESHfieldREG = new RegExp('|' + taskfield + ' ',\"g\");\n";
	echo "		if (custom_refresh_fields.match(REFRESHfieldREG))\n";
	echo "			{\n";
	echo "			field_refresh(taskfield);\n";
	echo "			}\n";
	echo "		}\n";

	$only_field_query = "DB=$DB&SIPexten=$SIPexten&SQLdate=$SQLdate&admin_submit=$admin_submit&agent_email=$agent_email&agent_log_id=$agent_log_id&bcrypt=$bcrypt&bgcolor=$bgcolor&button_action=$button_action&call_id=$call_id&called_count=$called_count&camp_script=$camp_script&campaign=$campaign&channel_group=$channel_group&closecallid=$closecallid&closer=$closer&customer_server_ip=$customer_server_ip&customer_zap_channel=$customer_zap_channel&dialed_label=$dialed_label&dialed_number=$dialed_number&did_custom_five=$did_custom_five&did_custom_four=$did_custom_four&did_custom_one=$did_custom_one&did_custom_three=$did_custom_three&did_custom_two=$did_custom_two&ig_custom_five=$ig_custom_five&ig_custom_four=$ig_custom_four&ig_custom_one=$ig_custom_one&ig_custom_three=$ig_custom_three&ig_custom_two=$ig_custom_two&did_description=$did_description&did_extension=$did_extension&did_id=$did_id&did_pattern=$did_pattern&epoch=$epoch&fronter=$fronter&fullname=$fullname&group=$group&hide_gender=$hide_gender&in_script=$in_script&lead_id=$lead_id&list_description=$list_description&list_id=$list_id&list_name=$list_name&new_list_id=$new_list_id&original_phone_login=$original_phone_login&parked_by=$parked_by&pass=$pass&phone=$phone&phone_login=$phone_login&phone_pass=$phone_pass&preset_dtmf_a=$preset_dtmf_a&preset_dtmf_b=$preset_dtmf_b&preset_number_a=$preset_number_a&preset_number_b=$preset_number_b&preset_number_c=$preset_number_c&preset_number_d=$preset_number_d&preset_number_e=$preset_number_e&preset_number_f=$preset_number_f&recording_filename=$recording_filename&recording_id=$recording_id&script_height=$script_height&script_width=$script_width&server_ip=$server_ip&server_ip=$server_ip&session_id=$session_id&session_id=$session_id&submit_button=$submit_button&uniqueid=$uniqueid&user=$user&user_custom_five=$user_custom_five&user_custom_four=$user_custom_four&user_custom_one=$user_custom_one&user_custom_three=$user_custom_three&user_custom_two=$user_custom_two&camp_custom_five=$camp_custom_five&camp_custom_four=$camp_custom_four&camp_custom_one=$camp_custom_one&camp_custom_three=$camp_custom_three&camp_custom_two=$camp_custom_two&user_group=$user_group&xfercallid=$xfercallid&web_vars=$web_vars&orig_URL=$orig_URL";
	?>

// ################################################################################
// Trigger a refresh of a single custom field
	function field_refresh(temp_field)
		{
		if (temp_field.length > 0)
			{
			var source_field=temp_field;
			var source_field_value='';
			var only_field='';

			//alert('Starting field refresh: '+ temp_field + ' ' + custom_refresh_fields);
			//$custom_refresh_fields .= "$sourceselect_field $sourceselect_type $sourceselect_default $A_field_label[$o] $A_field_type[$o]|";
			var REFRESHfieldREG = new RegExp('|' + temp_field + ' ',"g");
			if (custom_refresh_fields.match(REFRESHfieldREG))
				{
				var CRF_array=custom_refresh_fields.split("|");
				var CRF_count=CRF_array.length;
				var CRF_tick=0;
				while (CRF_tick < CRF_count)
					{
					var temp_CRF_value='';
					var CRF_field = CRF_array[CRF_tick];
					if (CRF_field.length > 0)
						{
						var temp_CRF_field=CRF_field.split(' ');
						if (temp_CRF_field[0] == temp_field)
							{
							if (temp_CRF_field[2] == '1')
								{
								// it is a default field, check type and grab value
								if (temp_CRF_field[1] == 'TEXT')
									{
									temp_CRF_value = parent.document.getElementById(temp_field).value;
									if (temp_CRF_value == '') {temp_CRF_value='--BLANK--';}
									}
								if (temp_CRF_field[1] == 'SELECT')
									{
									var taskIndex = parent.document.getElementById('gender_list').selectedIndex;
									temp_CRF_value = parent.document.getElementById('gender_list').options[taskIndex].value;
									if (temp_CRF_value == '') {temp_CRF_value='--BLANK--';}
									}
								}
							else
								{
								// it is a custom field, check type and grab value
								if (temp_CRF_field[1] == 'TEXT')
									{
									temp_CRF_value = document.getElementById(temp_field).value;
									if (temp_CRF_value == '') {temp_CRF_value='--BLANK--';}
									}
								if ( (temp_CRF_field[1] == 'SELECT') || (temp_CRF_field[1] == 'SOURCESELECT') )
									{
									var taskIndex = document.getElementById(temp_field).selectedIndex;
									temp_CRF_value = document.getElementById(temp_field).options[taskIndex].value;
									if (temp_CRF_value == '') {temp_CRF_value='--BLANK--';}
									}
								}

							// update the target field now that we know the source field, type and value it is linked to
							if (temp_CRF_value != '')
								{
								var temp_ajax_timeout = (ajax_timeout + 10);
								setTimeout(field_refresh_ajax, temp_ajax_timeout, temp_CRF_field[3],temp_CRF_field[0],temp_CRF_value,temp_ajax_timeout);
								ajax_timeout = (ajax_timeout + 100);
								}
							}
						}
					CRF_tick++;
					}
				ajax_timeout=0;
				}
			}
		}

// ################################################################################
// Trigger a refresh of a single custom field
	function field_refresh_ajax(temp_field,temp_source,temp_source_value,task_timeout)
		{
		var field_content_span = 'field_content_' + temp_field;
		var loading_select = '<select size=1 name=' + temp_field + ' id=' + temp_field + "><option value='' SELECTED><?php echo _QXZ("Refreshing..."); ?></option></select>\n";

		//alert('field refresh: '+ field_content_span + ' ' + temp_source + ' ' + temp_source_value);
		document.getElementById(field_content_span).innerHTML = loading_select;

		var xmlhttp=false;
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		// JScript gives us Conditional compilation, we can cope with old IE versions.
		// and security blocked creation of the objects.
		 try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		 } catch (e) {
		  try {
		   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		  } catch (E) {
		   xmlhttp = false;
		  }
		 }
		@end @*/
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp)
			{ 
			LSview_query = "only_field=" + temp_field + "&source_field=" + temp_source + "&source_field_value=" + temp_source_value + "&stage=REFRESH_SINGLE_FIELD&<?php echo $only_field_query ?>";
			//document.getElementById("debugbottomspan2").innerHTML = LSview_query;
			xmlhttp.open('POST', 'vdc_form_display.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(LSview_query); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
					//alert(xmlhttp.responseText);
					document.getElementById(field_content_span).innerHTML = xmlhttp.responseText + "\n";

					var REFRESHfieldREG = new RegExp('|' + temp_field + ' ',"g");
					if (custom_refresh_fields.match(REFRESHfieldREG))
						{
						//debug_append = debug_append + "\n" + temp_field + ' timeout: ' + task_timeout;
						//document.getElementById("debugbottomspan2").insertAdjacentHTML('beforeend',debug_append);
						setTimeout(field_refresh, 100, temp_field);
						}
					}
				}
			delete xmlhttp;
			}
		}

	<?php
	echo "	function nothing()\n";
	echo "		{}\n";
	echo "	</script>\n";
	echo "	<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "</head>\n";
	echo "<BODY BGCOLOR=\"#" . $bgcolor . "\" marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onload=\"parent.document.getElementById('FORM_LOADED').value='1';\">";
	echo "\n";
	echo "<form action=./vdc_form_display.php method=POST name=form_custom_fields id=form_custom_fields>\n";
	echo "<input type=hidden name=lead_id id=lead_id value=\"$lead_id\">\n";
	echo "<input type=hidden name=list_id id=list_id value=\"$list_id\">\n";
	echo "<input type=hidden name=new_list_id id=new_list_id value=''>\n";
	echo "<input type=hidden name=button_action id=button_action value=''>\n";
	echo "<input type=hidden name=user id=user value=\"$user\">\n";
	echo "<input type=hidden name=pass id=pass value=\"$pass\">\n";
	echo "<input type=hidden name=call_id id=call_id value=\"$call_id\">\n";
	echo "<input type=hidden name=agent_log_id id=agent_log_id value=\"$agent_log_id\">\n";
	echo "<input type=hidden name=campaign id=campaign value=\"$campaign\">\n";
	echo "<input type=hidden name=user_group id=user_group value=\"$user_group\">\n";
	echo "<input type=hidden name=uniqueid id=uniqueid value=\"$uniqueid\">\n";
	echo "<input type=hidden name=orig_URL id=orig_URL value=\"$vdcURL\">\n";
	echo "\n";
	if ($submit_button=='READONLY')
		{
		echo "<input type=hidden name=submit_button id=submit_button value=\"READONLY\">\n";
		}

	$only_field='';

	require_once("functions.php");

	$CFoutput = custom_list_fields_values($lead_id,$list_id,$uniqueid,$user,$DB,$call_id,$did_id,$did_extension,$did_pattern,$did_description,$dialed_number,$dialed_label,$only_field,$source_field,$source_field_value);

	echo "$CFoutput";

	if ($bcrypt=='0')
		{echo "<input type=hidden name=bcrypt id=bcrypt value=\"OFF\">\n";}
	if ($submit_button=='YES')
		{
		echo "<input type=hidden name=admin_submit id=admin_submit value=\"YES\">\n";
		echo "<BR><BR><input type=submit name=VCformSubmit id=VCformSubmit value=submit>\n";
		}
	echo "</form></center><BR><BR>\n";
	echo "<br><span id=debugbottomspan2></span>\n";
	echo "</BODY></HTML>\n";
	}

if ($SSagent_debug_logging > 0) 
	{
	vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);
	}

exit;

?>
