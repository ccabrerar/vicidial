<?php
# vdc_form_display.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
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
#

$version = '2.14-33';
$build = '180503-1813';
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
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}


if ($bcrypt == 'OFF')
	{$bcrypt=0;}

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

$vicidial_list_fields = '|lead_id|entry_date|vendor_lead_code|source_id|list_id|gmt_offset_now|called_since_last_reset|phone_code|phone_number|title|first_name|middle_initial|last_name|address1|address2|address3|city|state|province|postal_code|country_code|gender|date_of_birth|alt_phone|email|security_phrase|comments|called_count|last_local_call_time|rank|owner|';

$IFRAME=0;
$SUBMIT_only=0;

$user = preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass = preg_replace("/\'|\"|\\\\|;| /","",$pass);
$lead_id = preg_replace('/[^0-9]/', '', $lead_id);
$list_id = preg_replace('/[^0-9]/', '', $list_id);
$new_list_id = preg_replace('/[^0-9]/', '', $new_list_id);
$agent_log_id = preg_replace('/[^0-9]/', '', $agent_log_id);
$server_ip = preg_replace("/\'|\"|\\\\|;/","",$server_ip);
$session_id = preg_replace('/[^0-9]/','',$session_id);
$uniqueid = preg_replace('/[^-_\.0-9a-zA-Z]/','',$uniqueid);
$campaign = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign);
$user_group = preg_replace('/[^-_0-9a-zA-Z]/','',$user_group);

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
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

$stmt = "SELECT use_non_latin,timeclock_end_of_day,agentonly_callback_campaign_lock,custom_fields_enabled,enable_languages,language_method,agent_debug_logging FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06005',$user,$server_ip,$session_name,$one_mysql_log);}
if ($DB) {echo "$stmt\n";}
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
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$length_in_sec = preg_replace("/[^0-9]/","",$length_in_sec);
	$phone_code = preg_replace("/[^0-9]/","",$phone_code);
	$phone_number = preg_replace("/[^0-9]/","",$phone_number);
	}

# default optional vars if not set
if (!isset($format))   {$format="text";}
	if ($format == 'debug')	{$DB=1;}
if (!isset($ACTION))   {$ACTION="custom_form_frame";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (strlen($SSagent_debug_logging) > 1)
	{
	if ($SSagent_debug_logging == "$user")
		{$SSagent_debug_logging=1;}
	else
		{$SSagent_debug_logging=0;}
	}

$auth_api_flag = 0;
if ( ($submit_button=='YES') or ($admin_submit=='YES') )
	{$auth_api_flag = 1;}

$auth=0;
$auth_message = user_authorization($user,$pass,'',0,$bcrypt,0,$auth_api_flag);
if ($auth_message == 'GOOD')
	{$auth=1;}

$stmt="SELECT count(*) from vicidial_users where user='$user' and ( (modify_leads='1') or (qc_enabled='1') );";
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


### BEGIN parse submission of the custom fields form ###
if ($stage=='SUBMIT')
	{
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
			$form_field_value = preg_replace("/\\b/","",$form_field_value);	// remove backslashes

			if ( ($A_field_type[$o]=='MULTI') or ($A_field_type[$o]=='CHECKBOX') or ($A_field_type[$o]=='RADIO') )
				{
				$k=0;
				$multi_count = count($form_field_value);
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
				$form_field_value = "$form_field_valueH:$form_field_valueM:00";
				}

			$A_field_value[$o] = $form_field_value;

			if ( ($A_field_type[$o]=='DISPLAY') or ($A_field_type[$o]=='SCRIPT') or ($A_field_type[$o]=='SWITCH') or ($A_field_type[$o]=='HIDDEN') or ($A_field_type[$o]=='HIDEBLOB') or ($A_field_type[$o]=='READONLY') )
				{
                if (($A_field_type[$o]=='DISPLAY') or ($A_field_type[$o]=='SCRIPT') or ($A_field_type[$o]=='SWITCH') or ($A_field_type[$o]=='READONLY'))
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
			$stmt="SELECT count(*) from custom_$list_id where lead_id='$lead_id';";
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
			$custom_table_update_SQL = "INSERT INTO custom_$list_id SET lead_id='$lead_id',$update_SQL;";
			if ($custom_record_lead_count > 0)
				{$custom_table_update_SQL = "UPDATE custom_$list_id SET $update_SQL where lead_id='$lead_id';";}

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
			$list_table_update_SQL = "UPDATE vicidial_list SET $custom_update_vl_SQL $VL_update_SQL where lead_id='$lead_id';";

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
				$list_table_update_SQL = "UPDATE vicidial_list SET entry_list_id='$list_id' where lead_id='$lead_id';";
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
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LEADS', event_type='MODIFY', record_id='$lead_id', event_code='ADMIN MODIFY CUSTOM LEAD', event_sql=\"$SQL_log\", event_notes='$custom_update_count|$list_update_count';";
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
		$new_list_table_update_SQL = "UPDATE vicidial_list SET entry_list_id='$new_list_id' where lead_id='$lead_id';";
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
		$stmt = "INSERT INTO vicidial_agent_function_log set agent_log_id='$agent_log_id',user='$user',function='switch_list',event_time=NOW(),campaign_id='$campaign',user_group='$user_group',lead_id='$lead_id',uniqueid='$uniqueid',caller_code='$call_id',stage='$new_list_id',comments='$list_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {$errno = mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'06006',$user,$server_ip,$session_name,$one_mysql_log);}

		$list_id = $new_list_id;
		}
	else
		{
		echo  _QXZ("Custom Form Output:")."\n<BR>\n";

		echo "$SUBMIT_output";

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
	echo "		}\n";
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
	echo "<input type=hidden name=user id=user value=\"$user\">\n";
	echo "<input type=hidden name=pass id=pass value=\"$pass\">\n";
	echo "<input type=hidden name=call_id id=call_id value=\"$call_id\">\n";
	echo "<input type=hidden name=agent_log_id id=agent_log_id value=\"$agent_log_id\">\n";
	echo "<input type=hidden name=campaign id=campaign value=\"$campaign\">\n";
	echo "<input type=hidden name=user_group id=user_group value=\"$user_group\">\n";
	echo "<input type=hidden name=uniqueid id=uniqueid value=\"$uniqueid\">\n";
	echo "\n";


	require_once("functions.php");

	$CFoutput = custom_list_fields_values($lead_id,$list_id,$uniqueid,$user,$DB,$call_id,$did_id,$did_extension,$did_pattern,$did_description,$dialed_number,$dialed_label);

	echo "$CFoutput";

	if ($bcrypt=='0')
		{echo "<input type=hidden name=bcrypt id=bcrypt value=\"OFF\">\n";}
	if ($submit_button=='YES')
		{
		echo "<input type=hidden name=admin_submit id=admin_submit value=\"YES\">\n";
		echo "<BR><BR><input type=submit name=VCformSubmit id=VCformSubmit value=submit>\n";
		}
	echo "</form></center><BR><BR>\n";
	echo "</BODY></HTML>\n";
	}

if ($SSagent_debug_logging > 0) 
	{
	vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt);
	}

exit;

?>
