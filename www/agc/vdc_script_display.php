<?php
# vdc_script_display.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed display the contents of the SCRIPT tab in the agent interface
#
# CHANGELOG:
# 90824-1435 - First build of script
# 90827-1548 - Added list override script option
# 91204-1913 - Added recording_filename and recording_id variables
# 91211-1103 - Added user_custom_... variables
# 100116-0702 - Added preset variables
# 100127-1611 - Added ignore_list_script_override option
# 100823-1644 - Added DID variables
# 100902-1344 - Added closecallid, xfercallid, agent_log_id variables
# 110420-1201 - Added web_vars variable
# 110730-2339 - Added call_id variable
# 120227-2017 - Added parsing of IGNORENOSCROLL option in script to force scroll
# 130328-0013 - Converted ereg to preg functions
# 130402-2255 - Added user_group variable
# 130603-2206 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130705-1513 - Added optional encrypted passwords compatibility
# 130802-1035 - Changed to PHP mysqli functions
# 140429-2034 - Added TABLEper_call_notes display script variable
# 140623-2114 - Added script_override variable
# 140630-1023 - Added full_script_height script variable
# 140710-2143 - Added session_name variable
# 140810-1857 - Changed to use QXZ function for echoing text
# 141118-1422 - Added agent_email variable
# 141216-2121 - Added language settings lookups and user/pass variable standardization
# 150703-2034 - Added option to fully urlencode variables if IFRAME is used in script Issue #864
# 150725-1622 - Added entry_date variable
# 150923-2028 - Added DID custom variables
# 160818-1226 - Added MANUALDIALLINK option
# 170317-2315 - Fixed in-group list script override issue, added debug
# 170526-2343 - Added additional variable filtering
# 171006-2055 - Added inbound_list_script_override option
# 171126-1124 - Added email message display from inbound emails only
# 180224-1406 - Added LOGINvar variables, and options.php $INSERT_ variables
# 180327-1356 - Added code for LOCALFQDN conversion to browser-used server URL for script iframes
#

$version = '2.14-33';
$build = '180224-1406';

require_once("dbconnect_mysqli.php");
require_once("functions.php");

if (isset($_GET["lead_id"]))	{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))	{$lead_id=$_POST["lead_id"];}
if (isset($_GET["vendor_id"]))	{$vendor_id=$_GET["vendor_id"];}
	elseif (isset($_POST["vendor_id"]))	{$vendor_id=$_POST["vendor_id"];}
	$vendor_lead_code = $vendor_id;
if (isset($_GET["list_id"]))	{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))	{$list_id=$_POST["list_id"];}
if (isset($_GET["gmt_offset_now"]))	{$gmt_offset_now=$_GET["gmt_offset_now"];}
	elseif (isset($_POST["gmt_offset_now"]))	{$gmt_offset_now=$_POST["gmt_offset_now"];}
if (isset($_GET["phone_code"]))	{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))	{$phone_code=$_POST["phone_code"];}
if (isset($_GET["phone_number"]))	{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["title"]))	{$title=$_GET["title"];}
	elseif (isset($_POST["title"]))	{$title=$_POST["title"];}
if (isset($_GET["first_name"]))	{$first_name=$_GET["first_name"];}
	elseif (isset($_POST["first_name"]))	{$first_name=$_POST["first_name"];}
if (isset($_GET["middle_initial"]))	{$middle_initial=$_GET["middle_initial"];}
	elseif (isset($_POST["middle_initial"]))	{$middle_initial=$_POST["middle_initial"];}
if (isset($_GET["last_name"]))	{$last_name=$_GET["last_name"];}
	elseif (isset($_POST["last_name"]))	{$last_name=$_POST["last_name"];}
if (isset($_GET["address1"]))	{$address1=$_GET["address1"];}
	elseif (isset($_POST["address1"]))	{$address1=$_POST["address1"];}
if (isset($_GET["address2"]))	{$address2=$_GET["address2"];}
	elseif (isset($_POST["address2"]))	{$address2=$_POST["address2"];}
if (isset($_GET["address3"]))	{$address3=$_GET["address3"];}
	elseif (isset($_POST["address3"]))	{$address3=$_POST["address3"];}
if (isset($_GET["city"]))	{$city=$_GET["city"];}
	elseif (isset($_POST["city"]))	{$city=$_POST["city"];}
if (isset($_GET["state"]))	{$state=$_GET["state"];}
	elseif (isset($_POST["state"]))	{$state=$_POST["state"];}
if (isset($_GET["province"]))	{$province=$_GET["province"];}
	elseif (isset($_POST["province"]))	{$province=$_POST["province"];}
if (isset($_GET["postal_code"]))	{$postal_code=$_GET["postal_code"];}
	elseif (isset($_POST["postal_code"]))	{$postal_code=$_POST["postal_code"];}
if (isset($_GET["country_code"]))	{$country_code=$_GET["country_code"];}
	elseif (isset($_POST["country_code"]))	{$country_code=$_POST["country_code"];}
if (isset($_GET["gender"]))	{$gender=$_GET["gender"];}
	elseif (isset($_POST["gender"]))	{$gender=$_POST["gender"];}
if (isset($_GET["date_of_birth"]))	{$date_of_birth=$_GET["date_of_birth"];}
	elseif (isset($_POST["date_of_birth"]))	{$date_of_birth=$_POST["date_of_birth"];}
if (isset($_GET["alt_phone"]))	{$alt_phone=$_GET["alt_phone"];}
	elseif (isset($_POST["alt_phone"]))	{$alt_phone=$_POST["alt_phone"];}
if (isset($_GET["email"]))	{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))	{$email=$_POST["email"];}
if (isset($_GET["security_phrase"]))	{$security_phrase=$_GET["security_phrase"];}
	elseif (isset($_POST["security_phrase"]))	{$security_phrase=$_POST["security_phrase"];}
if (isset($_GET["comments"]))	{$comments=$_GET["comments"];}
	elseif (isset($_POST["comments"]))	{$comments=$_POST["comments"];}
if (isset($_GET["user"]))	{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))	{$user=$_POST["user"];}
if (isset($_GET["pass"]))	{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))	{$pass=$_POST["pass"];}
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
if (isset($_GET["uniqueid"]))	{$uniqueid=$_GET["uniqueid"];}
	elseif (isset($_POST["uniqueid"]))	{$uniqueid=$_POST["uniqueid"];}
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
if (isset($_GET["dispo"]))	{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))	{$dispo=$_POST["dispo"];}
if (isset($_GET["dialed_number"]))	{$dialed_number=$_GET["dialed_number"];}
	elseif (isset($_POST["dialed_number"]))	{$dialed_number=$_POST["dialed_number"];}
if (isset($_GET["dialed_label"]))	{$dialed_label=$_GET["dialed_label"];}
	elseif (isset($_POST["dialed_label"]))	{$dialed_label=$_POST["dialed_label"];}
if (isset($_GET["source_id"]))	{$source_id=$_GET["source_id"];}
	elseif (isset($_POST["source_id"]))	{$source_id=$_POST["source_id"];}
if (isset($_GET["rank"]))	{$rank=$_GET["rank"];}
	elseif (isset($_POST["rank"]))	{$rank=$_POST["rank"];}
if (isset($_GET["owner"]))	{$owner=$_GET["owner"];}
	elseif (isset($_POST["owner"]))	{$owner=$_POST["owner"];}
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
if (isset($_GET["ScrollDIV"]))			{$ScrollDIV=$_GET["ScrollDIV"];}
	elseif (isset($_POST["ScrollDIV"]))	{$ScrollDIV=$_POST["ScrollDIV"];}
if (isset($_GET["ignore_list_script"]))				{$ignore_list_script=$_GET["ignore_list_script"];}
	elseif (isset($_POST["ignore_list_script"]))	{$ignore_list_script=$_POST["ignore_list_script"];}
if (isset($_GET["CF_uses_custom_fields"]))			{$CF_uses_custom_fields=$_GET["CF_uses_custom_fields"];}
	elseif (isset($_POST["CF_uses_custom_fields"]))	{$CF_uses_custom_fields=$_POST["CF_uses_custom_fields"];}
if (isset($_GET["entry_list_id"]))			{$entry_list_id=$_GET["entry_list_id"];}
	elseif (isset($_POST["entry_list_id"]))	{$entry_list_id=$_POST["entry_list_id"];}
if (isset($_GET["call_id"]))			{$call_id=$_GET["call_id"];}
	elseif (isset($_POST["call_id"]))	{$call_id=$_POST["call_id"];}
if (isset($_GET["user_group"]))				{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))	{$user_group=$_POST["user_group"];}
if (isset($_GET["web_vars"]))			{$web_vars=$_GET["web_vars"];}
	elseif (isset($_POST["web_vars"]))	{$web_vars=$_POST["web_vars"];}
if (isset($_GET["orig_pass"]))			{$orig_pass=$_GET["orig_pass"];}
	elseif (isset($_POST["orig_pass"]))	{$orig_pass=$_POST["orig_pass"];}
if (isset($_GET["called_count"]))			{$called_count=$_GET["called_count"];}
	elseif (isset($_POST["called_count"]))	{$called_count=$_POST["called_count"];}
if (isset($_GET["script_override"]))			{$script_override=$_GET["script_override"];}
	elseif (isset($_POST["script_override"]))	{$script_override=$_POST["script_override"];}
if (isset($_GET["session_name"]))			{$session_name=$_GET["session_name"];}
	elseif (isset($_POST["session_name"]))	{$session_name=$_POST["session_name"];}
if (isset($_GET["entry_date"]))				{$entry_date=$_GET["entry_date"];}
	elseif (isset($_POST["entry_date"]))	{$entry_date=$_POST["entry_date"];}
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
if (isset($_GET["email_row_id"]))			{$email_row_id=$_GET["email_row_id"];}
	elseif (isset($_POST["email_row_id"]))	{$email_row_id=$_POST["email_row_id"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["inOUT"]))			{$inOUT=$_GET["inOUT"];}
	elseif (isset($_POST["inOUT"]))	{$inOUT=$_POST["inOUT"];}
if (isset($_GET["LOGINvarONE"]))			{$LOGINvarONE=$_GET["LOGINvarONE"];}
	elseif (isset($_POST["LOGINvarONE"]))	{$LOGINvarONE=$_POST["LOGINvarONE"];}
if (isset($_GET["LOGINvarTWO"]))			{$LOGINvarTWO=$_GET["LOGINvarTWO"];}
	elseif (isset($_POST["LOGINvarTWO"]))	{$LOGINvarTWO=$_POST["LOGINvarTWO"];}
if (isset($_GET["LOGINvarTHREE"]))			{$LOGINvarTHREE=$_GET["LOGINvarTHREE"];}
	elseif (isset($_POST["LOGINvarTHREE"]))	{$LOGINvarTHREE=$_POST["LOGINvarTHREE"];}
if (isset($_GET["LOGINvarFOUR"]))			{$LOGINvarFOUR=$_GET["LOGINvarFOUR"];}
	elseif (isset($_POST["LOGINvarFOUR"]))	{$LOGINvarFOUR=$_POST["LOGINvarFOUR"];}
if (isset($_GET["LOGINvarFIVE"]))			{$LOGINvarFIVE=$_GET["LOGINvarFIVE"];}
	elseif (isset($_POST["LOGINvarFIVE"]))	{$LOGINvarFIVE=$_POST["LOGINvarFIVE"];}


header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

$txt = '.txt';
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$MT[0]='';
$agents='@agents';
$script_height = ($script_height - 20);
$full_script_height = ($script_height + 200);

$server_name = getenv("SERVER_NAME");
$server_port = getenv("SERVER_PORT");
$CL=':';
if (($server_port == '80') or ($server_port == '443') ) {$server_port='';}
else {$server_port = "$CL$server_port";}
$FQDN = "$server_name$server_port";

$IFRAME=0;
$IFRAMEencode=1;

$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);
$orig_pass = preg_replace("/\'|\"|\\\\|;| /","",$orig_pass);
$lead_id = preg_replace('/[^0-9]/', '', $lead_id);
$list_id = preg_replace('/[^0-9]/', '', $list_id);
$email_row_id = preg_replace('/[^0-9]/', '', $email_row_id);
$server_ip = preg_replace("/\'|\"|\\\\|;/","",$server_ip);
$session_id = preg_replace('/[^0-9]/','',$session_id);
$uniqueid = preg_replace('/[^-_\.0-9a-zA-Z]/','',$uniqueid);
$campaign = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign);
$group = preg_replace('/[^-_0-9a-zA-Z]/','',$group);
$session_name = preg_replace("/\'|\"|\\\\|;/","",$session_name);
$LOGINvarONE=preg_replace("/[^-_0-9a-zA-Z]/","",$LOGINvarONE);
$LOGINvarTWO=preg_replace("/[^-_0-9a-zA-Z]/","",$LOGINvarTWO);
$LOGINvarTHREE=preg_replace("/[^-_0-9a-zA-Z]/","",$LOGINvarTHREE);
$LOGINvarFOUR=preg_replace("/[^-_0-9a-zA-Z]/","",$LOGINvarFOUR);
$LOGINvarFIVE=preg_replace("/[^-_0-9a-zA-Z]/","",$LOGINvarFIVE);

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$VUselected_language = '';
$stmt="SELECT selected_language from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$stmt = "SELECT use_non_latin,timeclock_end_of_day,agentonly_callback_campaign_lock,custom_fields_enabled,enable_languages,language_method FROM system_settings;";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
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
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$orig_pass=preg_replace("/[^-_0-9a-zA-Z]/","",$orig_pass);
	$length_in_sec = preg_replace("/[^0-9]/","",$length_in_sec);
	$phone_code = preg_replace("/[^0-9]/","",$phone_code);
	$phone_number = preg_replace("/[^0-9]/","",$phone_number);
	$session_name=preg_replace("/[^-_0-9a-zA-Z]/","",$session_name);
	}

# default optional vars if not set
if (!isset($format))   {$format="text";}
	if ($format == 'debug')	{$DB=1;}
if (!isset($ACTION))   {$ACTION="refresh";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}

$auth=0;
$auth_message = user_authorization($user,$pass,'',0,1,0,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0))
	{
	echo  _QXZ("Invalid Username/Password:")." |$user|$pass|$auth_message|\n";
	exit;
	}

if ($format=='debug')
	{
	echo "<html>\n";
	echo "<head>\n";
	echo "<!-- VERSION: $version     BUILD: $build    USER: $user   server_ip: $server_ip-->\n";
	echo "<title>"._QXZ("VICIDiaL Script Display Script");
	echo "</title>\n";
	echo "</head>\n";
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	}

if (strlen($in_script) < 1)
	{$call_script = $camp_script;}
else
	{$call_script = $in_script;}

$ignore_list_script_override='N';
if ($inOUT == 'IN')
	{
	$stmt = "SELECT ignore_list_script_override FROM vicidial_inbound_groups where group_id='$group';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$ilso_ct = mysqli_num_rows($rslt);
	if ($ilso_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$ignore_list_script_override =		$row[0];
		}
	if ($ignore_list_script_override=='Y')
		{$ignore_list_script=1;}
	}

if ($ignore_list_script < 1)
	{
#	$stmt="SELECT agent_script_override from vicidial_lists where list_id='$list_id';";
	$stmt="SELECT if('$inOUT'= 'IN',if(inbound_list_script_override is null or inbound_list_script_override='',agent_script_override,inbound_list_script_override),agent_script_override) from vicidial_lists where list_id='$list_id';";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$agent_script_override =		$row[0];
	if (strlen($agent_script_override) > 0)
		{$call_script = $agent_script_override;}
	}

$stmt="SELECT list_name,list_description from vicidial_lists where list_id='$list_id';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$list_name =			$row[0];
$list_description =		$row[1];

if (strlen($script_override)>1)
	{$call_script = $script_override;}

$stmt="SELECT script_name,script_text,script_color from vicidial_scripts where script_id='$call_script';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$script_name =		$row[0];
$script_text =		stripslashes($row[1]);
$script_color =		$row[2];

if (preg_match("/iframe\ssrc/i",$script_text))
	{
	$IFRAME=1;
	$user = preg_replace('/\s/i','+',$user);
	$pass = preg_replace('/\s/i','+',$orig_pass);

	if ($IFRAMEencode=='1')
		{
		$lead_id = urlencode(trim($lead_id));
		$vendor_id = urlencode(trim($vendor_id));
		$vendor_lead_code = urlencode(trim($vendor_lead_code));
		$list_id = urlencode(trim($list_id));
		$list_name = urlencode(trim($list_name));
		$list_description = urlencode(trim($list_description));
		$gmt_offset_now = urlencode(trim($gmt_offset_now));
		$phone_code = urlencode(trim($phone_code));
		$phone_number = urlencode(trim($phone_number));
		$title = urlencode(trim($title));
		$first_name = urlencode(trim($first_name));
		$middle_initial = urlencode(trim($middle_initial));
		$last_name = urlencode(trim($last_name));
		$address1 = urlencode(trim($address1));
		$address2 = urlencode(trim($address2));
		$address3 = urlencode(trim($address3));
		$city = urlencode(trim($city));
		$state = urlencode(trim($state));
		$province = urlencode(trim($province));
		$postal_code = urlencode(trim($postal_code));
		$country_code = urlencode(trim($country_code));
		$gender = urlencode(trim($gender));
		$date_of_birth = urlencode(trim($date_of_birth));
		$alt_phone = urlencode(trim($alt_phone));
		$email = urlencode(trim($email));
		$security_phrase = urlencode(trim($security_phrase));
		$comments = urlencode(trim($comments));
		$campaign = urlencode(trim($campaign));
		$phone_login = urlencode(trim($phone_login));
		$original_phone_login = urlencode(trim($original_phone_login));
		$phone_pass = urlencode(trim($phone_pass));
		$fronter = urlencode(trim($fronter));
		$closer = urlencode(trim($closer));
		$group = urlencode(trim($group));
		$channel_group = urlencode(trim($channel_group));
		$SQLdate = urlencode(trim($SQLdate));
		$epoch = urlencode(trim($epoch));
		$uniqueid = urlencode(trim($uniqueid));
		$customer_zap_channel = urlencode(trim($customer_zap_channel));
		$customer_server_ip = urlencode(trim($customer_server_ip));
		$server_ip = urlencode(trim($server_ip));
		$SIPexten = urlencode(trim($SIPexten));
		$session_id = urlencode(trim($session_id));
		$phone = urlencode(trim($phone));
		$parked_by = urlencode(trim($parked_by));
		$dispo = urlencode(trim($dispo));
		$dialed_number = urlencode(trim($dialed_number));
		$dialed_label = urlencode(trim($dialed_label));
		$source_id = urlencode(trim($source_id));
		$rank = urlencode(trim($rank));
		$owner = urlencode(trim($owner));
		$camp_script = urlencode(trim($camp_script));
		$in_script = urlencode(trim($in_script));
		$script_width = urlencode(trim($script_width));
		$script_height = urlencode(trim($script_height));
		$fullname = urlencode(trim($fullname));
		$agent_email = urlencode(trim($agent_email));
		$recording_filename = urlencode(trim($recording_filename));
		$recording_id = urlencode(trim($recording_id));
		$user_custom_one = urlencode(trim($user_custom_one));
		$user_custom_two = urlencode(trim($user_custom_two));
		$user_custom_three = urlencode(trim($user_custom_three));
		$user_custom_four = urlencode(trim($user_custom_four));
		$user_custom_five = urlencode(trim($user_custom_five));
		$preset_number_a = urlencode(trim($preset_number_a));
		$preset_number_b = urlencode(trim($preset_number_b));
		$preset_number_c = urlencode(trim($preset_number_c));
		$preset_number_d = urlencode(trim($preset_number_d));
		$preset_number_e = urlencode(trim($preset_number_e));
		$preset_number_f = urlencode(trim($preset_number_f));
		$preset_dtmf_a = urlencode(trim($preset_dtmf_a));
		$preset_dtmf_b = urlencode(trim($preset_dtmf_b));
		$did_id = urlencode(trim($did_id));
		$did_extension = urlencode(trim($did_extension));
		$did_pattern = urlencode(trim($did_pattern));
		$did_description = urlencode(trim($did_description));
		$called_count = urlencode(trim($called_count));
		$session_name = urlencode(trim($session_name));
		$entry_date = urlencode(trim($entry_date));
		$did_custom_one = urlencode(trim($did_custom_one));
		$did_custom_two = urlencode(trim($did_custom_two));
		$did_custom_three = urlencode(trim($did_custom_three));
		$did_custom_four = urlencode(trim($did_custom_four));
		$did_custom_five = urlencode(trim($did_custom_five));
		$web_vars = urlencode(trim($web_vars));
		}
	else
		{
		$lead_id = preg_replace('/\s/i','+',$lead_id);
		$vendor_id = preg_replace('/\s/i','+',$vendor_id);
		$vendor_lead_code = preg_replace('/\s/i','+',$vendor_lead_code);
		$list_id = preg_replace('/\s/i','+',$list_id);
		$list_name = preg_replace('/\s/i','+',$list_name);
		$list_description = preg_replace('/\s/i','+',$list_description);
		$gmt_offset_now = preg_replace('/\s/i','+',$gmt_offset_now);
		$phone_code = preg_replace('/\s/i','+',$phone_code);
		$phone_number = preg_replace('/\s/i','+',$phone_number);
		$title = preg_replace('/\s/i','+',$title);
		$first_name = preg_replace('/\s/i','+',$first_name);
		$middle_initial = preg_replace('/\s/i','+',$middle_initial);
		$last_name = preg_replace('/\s/i','+',$last_name);
		$address1 = preg_replace('/\s/i','+',$address1);
		$address2 = preg_replace('/\s/i','+',$address2);
		$address3 = preg_replace('/\s/i','+',$address3);
		$city = preg_replace('/\s/i','+',$city);
		$state = preg_replace('/\s/i','+',$state);
		$province = preg_replace('/\s/i','+',$province);
		$postal_code = preg_replace('/\s/i','+',$postal_code);
		$country_code = preg_replace('/\s/i','+',$country_code);
		$gender = preg_replace('/\s/i','+',$gender);
		$date_of_birth = preg_replace('/\s/i','+',$date_of_birth);
		$alt_phone = preg_replace('/\s/i','+',$alt_phone);
		$email = preg_replace('/\s/i','+',$email);
		$security_phrase = preg_replace('/\s/i','+',$security_phrase);
		$comments = preg_replace('/\s/i','+',$comments);
		$campaign = preg_replace('/\s/i','+',$campaign);
		$phone_login = preg_replace('/\s/i','+',$phone_login);
		$original_phone_login = preg_replace('/\s/i','+',$original_phone_login);
		$phone_pass = preg_replace('/\s/i','+',$phone_pass);
		$fronter = preg_replace('/\s/i','+',$fronter);
		$closer = preg_replace('/\s/i','+',$closer);
		$group = preg_replace('/\s/i','+',$group);
		$channel_group = preg_replace('/\s/i','+',$channel_group);
		$SQLdate = preg_replace('/\s/i','+',$SQLdate);
		$epoch = preg_replace('/\s/i','+',$epoch);
		$uniqueid = preg_replace('/\s/i','+',$uniqueid);
		$customer_zap_channel = preg_replace('/\s/i','+',$customer_zap_channel);
		$customer_server_ip = preg_replace('/\s/i','+',$customer_server_ip);
		$server_ip = preg_replace('/\s/i','+',$server_ip);
		$SIPexten = preg_replace('/\s/i','+',$SIPexten);
		$session_id = preg_replace('/\s/i','+',$session_id);
		$phone = preg_replace('/\s/i','+',$phone);
		$parked_by = preg_replace('/\s/i','+',$parked_by);
		$dispo = preg_replace('/\s/i','+',$dispo);
		$dialed_number = preg_replace('/\s/i','+',$dialed_number);
		$dialed_label = preg_replace('/\s/i','+',$dialed_label);
		$source_id = preg_replace('/\s/i','+',$source_id);
		$rank = preg_replace('/\s/i','+',$rank);
		$owner = preg_replace('/\s/i','+',$owner);
		$camp_script = preg_replace('/\s/i','+',$camp_script);
		$in_script = preg_replace('/\s/i','+',$in_script);
		$script_width = preg_replace('/\s/i','+',$script_width);
		$script_height = preg_replace('/\s/i','+',$script_height);
		$fullname = preg_replace('/\s/i','+',$fullname);
		$agent_email = preg_replace('/\s/i','+',$agent_email);
		$recording_filename = preg_replace('/\s/i','+',$recording_filename);
		$recording_id = preg_replace('/\s/i','+',$recording_id);
		$user_custom_one = preg_replace('/\s/i','+',$user_custom_one);
		$user_custom_two = preg_replace('/\s/i','+',$user_custom_two);
		$user_custom_three = preg_replace('/\s/i','+',$user_custom_three);
		$user_custom_four = preg_replace('/\s/i','+',$user_custom_four);
		$user_custom_five = preg_replace('/\s/i','+',$user_custom_five);
		$preset_number_a = preg_replace('/\s/i','+',$preset_number_a);
		$preset_number_b = preg_replace('/\s/i','+',$preset_number_b);
		$preset_number_c = preg_replace('/\s/i','+',$preset_number_c);
		$preset_number_d = preg_replace('/\s/i','+',$preset_number_d);
		$preset_number_e = preg_replace('/\s/i','+',$preset_number_e);
		$preset_number_f = preg_replace('/\s/i','+',$preset_number_f);
		$preset_dtmf_a = preg_replace('/\s/i','+',$preset_dtmf_a);
		$preset_dtmf_b = preg_replace('/\s/i','+',$preset_dtmf_b);
		$did_id = preg_replace('/\s/i','+',$did_id);
		$did_extension = preg_replace('/\s/i','+',$did_extension);
		$did_pattern = preg_replace('/\s/i','+',$did_pattern);
		$did_description = preg_replace('/\s/i','+',$did_description);
		$called_count = preg_replace('/\s/i','+',$called_count);
		$session_name = preg_replace('/\s/i','+',$session_name);
		$entry_date = preg_replace('/\s/i','+',$entry_date);
		$did_custom_one = preg_replace('/\s/i','+',$did_custom_one);
		$did_custom_two = preg_replace('/\s/i','+',$did_custom_two);
		$did_custom_three = preg_replace('/\s/i','+',$did_custom_three);
		$did_custom_four = preg_replace('/\s/i','+',$did_custom_four);
		$did_custom_five = preg_replace('/\s/i','+',$did_custom_five);
		$web_vars = preg_replace('/\s/i','+',$web_vars);
		}
	}

$script_text = preg_replace('/--A--lead_id--B--/i',"$lead_id",$script_text);
$script_text = preg_replace('/--A--vendor_id--B--/i',"$vendor_id",$script_text);
$script_text = preg_replace('/--A--vendor_lead_code--B--/i',"$vendor_lead_code",$script_text);
$script_text = preg_replace('/--A--list_id--B--/i',"$list_id",$script_text);
$script_text = preg_replace('/--A--list_name--B--/i',"$list_name",$script_text);
$script_text = preg_replace('/--A--list_description--B--/i',"$list_description",$script_text);
$script_text = preg_replace('/--A--gmt_offset_now--B--/i',"$gmt_offset_now",$script_text);
$script_text = preg_replace('/--A--phone_code--B--/i',"$phone_code",$script_text);
$script_text = preg_replace('/--A--phone_number--B--/i',"$phone_number",$script_text);
$script_text = preg_replace('/--A--title--B--/i',"$title",$script_text);
$script_text = preg_replace('/--A--first_name--B--/i',"$first_name",$script_text);
$script_text = preg_replace('/--A--middle_initial--B--/i',"$middle_initial",$script_text);
$script_text = preg_replace('/--A--last_name--B--/i',"$last_name",$script_text);
$script_text = preg_replace('/--A--address1--B--/i',"$address1",$script_text);
$script_text = preg_replace('/--A--address2--B--/i',"$address2",$script_text);
$script_text = preg_replace('/--A--address3--B--/i',"$address3",$script_text);
$script_text = preg_replace('/--A--city--B--/i',"$city",$script_text);
$script_text = preg_replace('/--A--state--B--/i',"$state",$script_text);
$script_text = preg_replace('/--A--province--B--/i',"$province",$script_text);
$script_text = preg_replace('/--A--postal_code--B--/i',"$postal_code",$script_text);
$script_text = preg_replace('/--A--country_code--B--/i',"$country_code",$script_text);
$script_text = preg_replace('/--A--gender--B--/i',"$gender",$script_text);
$script_text = preg_replace('/--A--date_of_birth--B--/i',"$date_of_birth",$script_text);
$script_text = preg_replace('/--A--alt_phone--B--/i',"$alt_phone",$script_text);
$script_text = preg_replace('/--A--email--B--/i',"$email",$script_text);
$script_text = preg_replace('/--A--security_phrase--B--/i',"$security_phrase",$script_text);
$script_text = preg_replace('/--A--comments--B--/i',"$comments",$script_text);
$script_text = preg_replace('/--A--user--B--/i',"$user",$script_text);
$script_text = preg_replace('/--A--pass--B--/i',"$pass",$script_text);
$script_text = preg_replace('/--A--campaign--B--/i',"$campaign",$script_text);
$script_text = preg_replace('/--A--phone_login--B--/i',"$phone_login",$script_text);
$script_text = preg_replace('/--A--original_phone_login--B--/i',"$original_phone_login",$script_text);
$script_text = preg_replace('/--A--phone_pass--B--/i',"$phone_pass",$script_text);
$script_text = preg_replace('/--A--fronter--B--/i',"$fronter",$script_text);
$script_text = preg_replace('/--A--closer--B--/i',"$closer",$script_text);
$script_text = preg_replace('/--A--group--B--/i',"$group",$script_text);
$script_text = preg_replace('/--A--channel_group--B--/i',"$channel_group",$script_text);
$script_text = preg_replace('/--A--SQLdate--B--/i',"$SQLdate",$script_text);
$script_text = preg_replace('/--A--epoch--B--/i',"$epoch",$script_text);
$script_text = preg_replace('/--A--uniqueid--B--/i',"$uniqueid",$script_text);
$script_text = preg_replace('/--A--customer_zap_channel--B--/i',"$customer_zap_channel",$script_text);
$script_text = preg_replace('/--A--customer_server_ip--B--/i',"$customer_server_ip",$script_text);
$script_text = preg_replace('/--A--server_ip--B--/i',"$server_ip",$script_text);
$script_text = preg_replace('/--A--SIPexten--B--/i',"$SIPexten",$script_text);
$script_text = preg_replace('/--A--session_id--B--/i',"$session_id",$script_text);
$script_text = preg_replace('/--A--phone--B--/i',"$phone",$script_text);
$script_text = preg_replace('/--A--parked_by--B--/i',"$parked_by",$script_text);
$script_text = preg_replace('/--A--dispo--B--/i',"$dispo",$script_text);
$script_text = preg_replace('/--A--dialed_number--B--/i',"$dialed_number",$script_text);
$script_text = preg_replace('/--A--dialed_label--B--/i',"$dialed_label",$script_text);
$script_text = preg_replace('/--A--source_id--B--/i',"$source_id",$script_text);
$script_text = preg_replace('/--A--rank--B--/i',"$rank",$script_text);
$script_text = preg_replace('/--A--owner--B--/i',"$owner",$script_text);
$script_text = preg_replace('/--A--camp_script--B--/i',"$camp_script",$script_text);
$script_text = preg_replace('/--A--in_script--B--/i',"$in_script",$script_text);
$script_text = preg_replace('/--A--script_width--B--/i',"$script_width",$script_text);
$script_text = preg_replace('/--A--script_height--B--/i',"$script_height",$script_text);
$script_text = preg_replace('/--A--full_script_height--B--/i',"$full_script_height",$script_text);
$script_text = preg_replace('/--A--fullname--B--/i',"$fullname",$script_text);
$script_text = preg_replace('/--A--agent_email--B--/i',"$agent_email",$script_text);
$script_text = preg_replace('/--A--recording_filename--B--/i',"$recording_filename",$script_text);
$script_text = preg_replace('/--A--recording_id--B--/i',"$recording_id",$script_text);
$script_text = preg_replace('/--A--user_custom_one--B--/i',"$user_custom_one",$script_text);
$script_text = preg_replace('/--A--user_custom_two--B--/i',"$user_custom_two",$script_text);
$script_text = preg_replace('/--A--user_custom_three--B--/i',"$user_custom_three",$script_text);
$script_text = preg_replace('/--A--user_custom_four--B--/i',"$user_custom_four",$script_text);
$script_text = preg_replace('/--A--user_custom_five--B--/i',"$user_custom_five",$script_text);
$script_text = preg_replace('/--A--preset_number_a--B--/i',"$preset_number_a",$script_text);
$script_text = preg_replace('/--A--preset_number_b--B--/i',"$preset_number_b",$script_text);
$script_text = preg_replace('/--A--preset_number_c--B--/i',"$preset_number_c",$script_text);
$script_text = preg_replace('/--A--preset_number_d--B--/i',"$preset_number_d",$script_text);
$script_text = preg_replace('/--A--preset_number_e--B--/i',"$preset_number_e",$script_text);
$script_text = preg_replace('/--A--preset_number_f--B--/i',"$preset_number_f",$script_text);
$script_text = preg_replace('/--A--preset_dtmf_a--B--/i',"$preset_dtmf_a",$script_text);
$script_text = preg_replace('/--A--preset_dtmf_b--B--/i',"$preset_dtmf_b",$script_text);
$script_text = preg_replace('/--A--did_id--B--/i',"$did_id",$script_text);
$script_text = preg_replace('/--A--did_extension--B--/i',"$did_extension",$script_text);
$script_text = preg_replace('/--A--did_pattern--B--/i',"$did_pattern",$script_text);
$script_text = preg_replace('/--A--did_description--B--/i',"$did_description",$script_text);
$script_text = preg_replace('/--A--closecallid--B--/i',"$closecallid",$script_text);
$script_text = preg_replace('/--A--xfercallid--B--/i',"$xfercallid",$script_text);
$script_text = preg_replace('/--A--agent_log_id--B--/i',"$agent_log_id",$script_text);
$script_text = preg_replace('/--A--entry_list_id--B--/i',"$entry_list_id",$script_text);
$script_text = preg_replace('/--A--call_id--B--/i',"$call_id",$script_text);
$script_text = preg_replace('/--A--user_group--B--/i',"$user_group",$script_text);
$script_text = preg_replace('/--A--called_count--B--/i',"$called_count",$script_text);
$script_text = preg_replace('/--A--session_name--B--/i',"$session_name",$script_text);
$script_text = preg_replace('/--A--entry_date--B--/i',"$entry_date",$script_text);
$script_text = preg_replace('/--A--did_custom_one--B--/i',"$did_custom_one",$script_text);
$script_text = preg_replace('/--A--did_custom_two--B--/i',"$did_custom_two",$script_text);
$script_text = preg_replace('/--A--did_custom_three--B--/i',"$did_custom_three",$script_text);
$script_text = preg_replace('/--A--did_custom_four--B--/i',"$did_custom_four",$script_text);
$script_text = preg_replace('/--A--did_custom_five--B--/i',"$did_custom_five",$script_text);
$script_text = preg_replace('/--A--LOGINvarONE--B--/i',"$LOGINvarONE",$script_text);
$script_text = preg_replace('/--A--LOGINvarTWO--B--/i',"$LOGINvarTWO",$script_text);
$script_text = preg_replace('/--A--LOGINvarTHREE--B--/i',"$LOGINvarTHREE",$script_text);
$script_text = preg_replace('/--A--LOGINvarFOUR--B--/i',"$LOGINvarFOUR",$script_text);
$script_text = preg_replace('/--A--LOGINvarFIVE--B--/i',"$LOGINvarFIVE",$script_text);
$script_text = preg_replace('/--A--web_vars--B--/i',"$web_vars",$script_text);
$script_text = preg_replace("/LOCALFQDN/",$FQDN,$script_text);

if ($CF_uses_custom_fields=='Y')
	{
	### find the names of all custom fields, if any
	$stmt = "SELECT field_label,field_type FROM vicidial_lists_fields where list_id='$entry_list_id' and field_type NOT IN('SCRIPT','DISPLAY') and field_label NOT IN('entry_date','vendor_lead_code','source_id','list_id','gmt_offset_now','called_since_last_reset','phone_code','phone_number','title','first_name','middle_initial','last_name','address1','address2','address3','city','state','province','postal_code','country_code','gender','date_of_birth','alt_phone','email','security_phrase','comments','called_count','last_local_call_time','rank','owner');";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$cffn_ct = mysqli_num_rows($rslt);
	$d=0;
	while ($cffn_ct > $d)
		{
		$row=mysqli_fetch_row($rslt);
		$field_name_id = $row[0];
		$field_name_tag = "--A--" . $field_name_id . "--B--";
		if (isset($_GET["$field_name_id"]))				{$form_field_value=$_GET["$field_name_id"];}
			elseif (isset($_POST["$field_name_id"]))	{$form_field_value=$_POST["$field_name_id"];}
		$script_text = preg_replace("/$field_name_tag/i","$form_field_value",$script_text);
			if ($DB) {echo "$d|$field_name_id|$field_name_tag|$form_field_value|<br>\n";}
		$d++;
		}
	}

$NOTESout='';
if (preg_match('/--A--TABLEper_call_notes--B--/i',$script_text))
	{
	### BEGIN Gather Call Log and notes ###
	if ($hide_call_log_info!='Y')
		{
		if ($search != 'logfirst')
			{$NOTESout .= _QXZ("CALL LOG FOR THIS LEAD:")."<br>\n";}
		$NOTESout .= "<TABLE CELLPADDING=0 CELLSPACING=1 BORDER=0>";
		$NOTESout .= "<TR>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:10px;font-family:sans-serif;\"><B> &nbsp; # &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("DATE/TIME")." &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("AGENT")." &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("LENGTH")." &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("STATUS")." &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("PHONE")." &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("CAMPAIGN")." &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("IN/OUT")." &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("ALT")." &nbsp; </font></TD>";
		$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\"><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("HANGUP")." &nbsp; </font></TD>";
	#	$NOTESout .= "</TR><TR>";
	#	$NOTESout .= "<TD BGCOLOR=\"#CCCCCC\" COLSPAN=9><font style=\"font-size:11px;font-family:sans-serif;\"><B> &nbsp; "._QXZ("FULL NAME")." &nbsp; </font></TD>";
		$NOTESout .= "</TR>";


		$stmt="SELECT start_epoch,call_date,campaign_id,length_in_sec,status,phone_code,phone_number,lead_id,term_reason,alt_dial,comments,uniqueid,user from vicidial_log where lead_id='$lead_id' order by call_date desc limit 10000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$out_logs_to_print = mysqli_num_rows($rslt);
		if ($format=='debug') {$NOTESout .= "|$out_logs_to_print|$stmt|";}

		$g=0;
		$u=0;
		while ($out_logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			$ALLsort[$g] =			"$row[0]-----$g";
			$ALLstart_epoch[$g] =	$row[0];
			$ALLcall_date[$g] =		$row[1];
			$ALLcampaign_id[$g] =	$row[2];
			$ALLlength_in_sec[$g] =	$row[3];
			$ALLstatus[$g] =		$row[4];
			$ALLphone_code[$g] =	$row[5];
			$ALLphone_number[$g] =	$row[6];
			$ALLlead_id[$g] =		$row[7];
			$ALLhangup_reason[$g] =	$row[8];
			$ALLalt_dial[$g] =		$row[9];
			$ALLuniqueid[$g] =		$row[11];
			$ALLuser[$g] =			$row[12];
			$ALLin_out[$g] =		"OUT-AUTO";
			if ($row[10] == 'MANUAL') {$ALLin_out[$g] = "OUT-MANUAL";}

			$stmtA="SELECT call_notes FROM vicidial_call_notes WHERE lead_id='$ALLlead_id[$g]' and vicidial_id='$ALLuniqueid[$g]';";
			$rsltA=mysql_to_mysqli($stmtA, $link);
			$out_notes_to_print = mysqli_num_rows($rslt);
			if ($out_notes_to_print > 0)
				{
				$rowA=mysqli_fetch_row($rsltA);
				$Allcall_notes[$g] =	$rowA[0];
				if (strlen($Allcall_notes[$g]) > 0)
					{$Allcall_notes[$g] =	"<b>NOTES: </b> $Allcall_notes[$g]";}
				}
			$stmtA="SELECT full_name,email FROM vicidial_users WHERE user='$ALLuser[$g]';";
			$rsltA=mysql_to_mysqli($stmtA, $link);
			$users_to_print = mysqli_num_rows($rslt);
			if ($users_to_print > 0)
				{
				$rowA=mysqli_fetch_row($rsltA);
				$ALLuser[$g] .=	" - $rowA[0]";
				$ALLemail[$g] .=	$rowA[1];
				}

			$Allcounter[$g] =		$g;
			$g++;
			$u++;
			}

		$stmt="SELECT start_epoch,call_date,campaign_id,length_in_sec,status,phone_code,phone_number,lead_id,term_reason,queue_seconds,uniqueid,closecallid,user from vicidial_closer_log where lead_id='$lead_id' order by closecallid desc limit 10000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$in_logs_to_print = mysqli_num_rows($rslt);
		if ($format=='debug') {$NOTESout .= "|$in_logs_to_print|$stmt|";}

		$u=0;
		while ($in_logs_to_print > $u) 
			{
			$row=mysqli_fetch_row($rslt);
			$ALLsort[$g] =			"$row[0]-----$g";
			$ALLstart_epoch[$g] =	$row[0];
			$ALLcall_date[$g] =		$row[1];
			$ALLcampaign_id[$g] =	$row[2];
			$ALLlength_in_sec[$g] =	($row[3] - $row[9]);
			if ($ALLlength_in_sec[$g] < 0) {$ALLlength_in_sec[$g]=0;}
			$ALLstatus[$g] =		$row[4];
			$ALLphone_code[$g] =	$row[5];
			$ALLphone_number[$g] =	$row[6];
			$ALLlead_id[$g] =		$row[7];
			$ALLhangup_reason[$g] =	$row[8];
			$ALLuniqueid[$g] =		$row[10];
			$ALLclosecallid[$g] =	$row[11];
			$ALLuser[$g] =			$row[12];
			$ALLalt_dial[$g] =		"MAIN";
			$ALLin_out[$g] =		"IN";

			$stmtA="SELECT call_notes FROM vicidial_call_notes WHERE lead_id='$ALLlead_id[$g]' and vicidial_id='$ALLclosecallid[$g]';";
			$rsltA=mysql_to_mysqli($stmtA, $link);
			$in_notes_to_print = mysqli_num_rows($rslt);
			if ($in_notes_to_print > 0)
				{
				$rowA=mysqli_fetch_row($rsltA);
				$Allcall_notes[$g] =	$rowA[0];
				if (strlen($Allcall_notes[$g]) > 0)
					{$Allcall_notes[$g] =	"<b>"._QXZ("NOTES:")." </b> $Allcall_notes[$g]";}
				}
			$stmtA="SELECT full_name FROM vicidial_users WHERE user='$ALLuser[$g]';";
			$rsltA=mysql_to_mysqli($stmtA, $link);
			$users_to_print = mysqli_num_rows($rslt);
			if ($users_to_print > 0)
				{
				$rowA=mysqli_fetch_row($rsltA);
				$ALLuser[$g] .=	" - $rowA[0]";
				}

			$Allcounter[$g] =		$g;

			$g++;
			$u++;
			}

		if ($g > 0)
			{sort($ALLsort, SORT_NUMERIC);}
		else
			{$NOTESout .= "<tr bgcolor=white><td colspan=11 align=center>"._QXZ("No calls found")."</td></tr>";}

		$u=0;
		while ($g > $u) 
			{
			$sort_split = explode("-----",$ALLsort[$u]);
			$i = $sort_split[1];

			if (preg_match("/1$|3$|5$|7$|9$/i", $u))
				{$bgcolor='bgcolor="#B9CBFD"';} 
			else
				{$bgcolor='bgcolor="#9BB9FB"';}

			$phone_number_display = $ALLphone_number[$i];
			if ($disable_alter_custphone == 'HIDE')
				{$phone_number_display = 'XXXXXXXXXX';}

			$u++;
			$NOTESout .= "<tr $bgcolor>";
			$NOTESout .= "<td><font size=1>$u</td>";
			$NOTESout .= "<td align=right><font size=2>$ALLcall_date[$i]</td>";
			$NOTESout .= "<td align=right><font size=2> $ALLuser[$i]</td>\n";
			$NOTESout .= "<td align=right><font size=2> $ALLlength_in_sec[$i]</td>\n";
			$NOTESout .= "<td align=right><font size=2> $ALLstatus[$i]</td>\n";
			$NOTESout .= "<td align=right><font size=2> $ALLphone_code[$i] $phone_number_display </td>\n";
			$NOTESout .= "<td align=right><font size=2> $ALLcampaign_id[$i] </td>\n";
			$NOTESout .= "<td align=right><font size=2> $ALLin_out[$i] </td>\n";
			$NOTESout .= "<td align=right><font size=2> $ALLalt_dial[$i] </td>\n";
			$NOTESout .= "<td align=right><font size=2> $ALLhangup_reason[$i] </td>\n";
			$NOTESout .= "</TR><TR>";
			$NOTESout .= "<td></td>";
			$NOTESout .= "<TD $bgcolor COLSPAN=9 align=left><font style=\"font-size:11px;font-family:sans-serif;\"> $Allcall_notes[$i] </font></TD>";
			$NOTESout .= "</tr>\n";
			}

		$NOTESout .= "</TABLE>";
		$NOTESout .= "<BR>";
		}
	### END Gather Call Log and notes ###
	}

$EMAILout='';
if ( (preg_match('/--A--EMAILinbound_message--B--/i',$script_text)) and (strlen($email_row_id) > 0) )
	{
	### BEGIN Gather inbound email message ###
	$stmtA="SELECT email_date,email_to,email_from,email_from_name,subject,message FROM vicidial_email_list WHERE email_row_id='$email_row_id';";
	$rsltA=mysql_to_mysqli($stmtA, $link);
	$out_email_to_print = mysqli_num_rows($rslt);
	if ($out_email_to_print > 0)
		{
		$rowA=mysqli_fetch_row($rsltA);
		$Eemail_date =			$rowA[0];
		$Eemail_to =			$rowA[1];
		$Eemail_from =			$rowA[2];
		$Eemail_from_name =		$rowA[3];
		$Esubject =				$rowA[4];
		$Emessage =				$rowA[5];

		$EMAILout .= "<table bgcolor=#999999 cellspacing=2 cellpadding=2><tr><td align=center colspan=2 bgcolor=white>"._QXZ("INBOUND EMAIL MESSAGE:").": </td></tr>";
		$EMAILout .= "<tr><td align=right bgcolor=white width=150>"._QXZ("Email Date").": </td><td align=left bgcolor=white>$Eemail_date</td></tr>";
		$EMAILout .= "<tr><td align=right bgcolor=white width=150>"._QXZ("Subject").": </td><td align=left bgcolor=white>$Esubject</td></tr>";
		$EMAILout .= "<tr><td align=right bgcolor=white width=150>"._QXZ("To").": </td><td align=left bgcolor=white>$Eemail_to</td></tr>";
		$EMAILout .= "<tr><td align=right bgcolor=white width=150>"._QXZ("From").": </td><td align=left bgcolor=white>$Eemail_from_name &lt;$Eemail_from&gt;</td></tr>";
		$EMAILout .= "<tr><td align=left colspan=2 bgcolor=white><font face='courier'>$Emessage</font></td></tr>";

		$att_stmt="SELECT * from inbound_email_attachments where email_row_id='$email_row_id'";
		$att_rslt=mysql_to_mysqli($att_stmt, $link);
		if (mysqli_num_rows($att_rslt)>0) 
			{
			$EMAILout.="<tr><td align=left valign=top colspan=2 bgcolor=white>"._QXZ("Attachments:")."<br><pre>";
			while($att_row=mysqli_fetch_array($att_rslt)) 
				{
				$EMAILout.="<LI><a href='./vdc_email_display.php?attachment_id=$att_row[attachment_id]&lead_id=$lead_id&user=$user&pass=$orig_pass' target='_blank'>$att_row[filename]</a>\n";
				}
			$EMAIL_form.="</pre></td></tr>";
			}

		$EMAILout .= "</table>";
		$EMAILout .= "<BR>";
		}
	### END Gather inbound email message ###
	}

$script_text = preg_replace("/\n/i","<BR>",$script_text);
$script_text = preg_replace('/--A--TABLEper_call_notes--B--/i',"$NOTESout",$script_text);
$script_text = preg_replace('/--A--EMAILinbound_message--B--/i',"$EMAILout",$script_text);
$manual_dial_code = "<a href=\"#\" onclick=\"NeWManuaLDiaLCalL('NO','','','','','YES');return false;\">" . _QXZ("MANUAL DIAL") ."</a>";
$script_text = preg_replace('/--A--MANUALDIALLINK--B--/i',$manual_dial_code,$script_text);
$script_text = stripslashes($script_text);


echo "<!-- IFRAME$IFRAME -->\n";
echo "<!-- $script_id -->\n";
if ( ( ($IFRAME < 1) and ($ScrollDIV > 0) ) or (preg_match("/IGNORENOSCROLL/i",$script_text)) )
	{
	echo "<TABLE WIDTH=$script_width border=0><TR><TD>";
	echo "<div class=\"scroll_script\" id=\"NewScriptContents\">";
	}
else
	{
	echo "<TABLE WIDTH=$script_width border=0 cellpadding=0 cellspacing=0><TR><TD>";
	}

if (strlen($script_override)< 1)
	{echo "<center><B>$script_name</B><BR></center>\n";}
echo "$script_text\n";
if ( ( ($IFRAME < 1) and ($ScrollDIV > 0) ) or (preg_match("/IGNORENOSCROLL/i",$script_text)) )
	{echo "</div>";}
echo "</TD></TR></TABLE>\n";

exit;

?>
