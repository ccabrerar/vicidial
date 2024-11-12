<?php
# vdc_script_dispo_example.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to be used in the SCRIPT tab in an IFRAME and will not submit unless a specific field is filled in
#
# ***** THIS SCRIPT REQUIRES CUSTOMIZATION BEFORE USING !!!!!!!!!!!! *****
#       The default of this script assumes 2 custom fields('question' and 'answer')
#
# Example of a ViciDial agent SCRIPT using this script(you must customize this for your custom fields!):
# <iframe src="./vdc_script_dispo_example.php?lead_id=--A--lead_id--B--&vendor_id=--A--vendor_lead_code--B--&list_id=--A--list_id--B--&gmt_offset_now=--A--gmt_offset_now--B--&phone_code=--A--phone_code--B--&phone_number=--A--phone_number--B--&title=--A--title--B--&first_name=--A--first_name--B--&middle_initial=--A--middle_initial--B--&last_name=--A--last_name--B--&address1=--A--address1--B--&address2=--A--address2--B--&address3=--A--address3--B--&city=--A--city--B--&state=--A--state--B--&province=--A--province--B--&postal_code=--A--postal_code--B--&country_code=--A--country_code--B--&gender=--A--gender--B--&date_of_birth=--A--date_of_birth--B--&alt_phone=--A--alt_phone--B--&email=--A--email--B--&security_phrase=--A--security_phrase--B--&comments=--A--comments--B--&user=--A--user--B--&pass=--A--pass--B--&campaign=--A--campaign--B--&phone_login=--A--phone_login--B--&fronter=--A--fronter--B--&closer=--A--user--B--&group=--A--group--B--&channel_group=--A--group--B--&SQLdate=--A--SQLdate--B--&epoch=--A--epoch--B--&uniqueid=--A--uniqueid--B--&rank=--A--rank--B--&owner=--A--owner--B--&customer_zap_channel=--A--customer_zap_channel--B--&server_ip=--A--server_ip--B--&SIPexten=--A--SIPexten--B--&session_id=--A--session_id--B--&entry_list_id=--A--entry_list_id--B--&closecallid=--A--closecallid--B--&fullname=--A--fullname--B--&question=--A--question--B--&answer=--A--answer--B--" style="background-color:transparent;" scrolling="auto" frameborder="0" allowtransparency="true" id="popupFrame" name="popupFrame"  width="--A--script_width--B--" height="--A--script_height--B--" STYLE="z-index:17"> </iframe> 
#
# CHANGELOG:
# 110208-1239 - First build of script based upon vdc_script_notes.php
# 110221-1252 - Added missing variables, accounted for call notes on manual dial calls
# 130328-0019 - Converted ereg to preg functions
# 130603-2201 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130802-1036 - Changed to PHP mysqli functions
# 140811-0832 - Changed to use QXZ function for echoing text
# 141216-2133 - Added language settings lookups and user/pass variable standardization
# 170526-2345 - Added additional variable filtering
# 170528-0902 - Added more variable filtering
# 190111-0910 - Fix for PHP7
# 210616-2035 - Added optional CORS support, see options.php for details
# 210825-0838 - Fix for security issue
# 220219-0137 - Added allow_web_debug system setting
#

$version = '2.14-13';
$build = '220219-0136';
$php_script = 'vdc_script_dispo_example.php';

require_once("dbconnect_mysqli.php");
require_once("functions.php");


# Configuration settings-
# Define the required fields: (these must be text fields, pipe separated)
$required_fields = 'phone_number|question|answer';

# Define statuses that trigger required fields: (must be surrounded by pipes)
#$required_statuses = '---ALL---'; # use this for all statuses required
$required_statuses = '|CALLBK|';

# Define any extra non-selectable: (single-quote-qualified, comma delimited)
$extra_statuses = "'CALLBK'";



if (isset($_POST["lead_id"]))	{$lead_id=$_POST["lead_id"];}
	elseif (isset($_GET["lead_id"]))	{$lead_id=$_GET["lead_id"];}
if (isset($_POST["vendor_id"]))	{$vendor_id=$_POST["vendor_id"];}
	elseif (isset($_GET["vendor_id"]))	{$vendor_id=$_GET["vendor_id"];}
	$vendor_lead_code = $vendor_id;
if (isset($_POST["list_id"]))	{$list_id=$_POST["list_id"];}
	elseif (isset($_GET["list_id"]))	{$list_id=$_GET["list_id"];}
if (isset($_POST["gmt_offset_now"]))	{$gmt_offset_now=$_POST["gmt_offset_now"];}
	elseif (isset($_GET["gmt_offset_now"]))	{$gmt_offset_now=$_GET["gmt_offset_now"];}
if (isset($_POST["phone_code"]))	{$phone_code=$_POST["phone_code"];}
	elseif (isset($_GET["phone_code"]))	{$phone_code=$_GET["phone_code"];}
if (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
	elseif (isset($_GET["phone_number"]))	{$phone_number=$_GET["phone_number"];}
if (isset($_POST["title"]))	{$title=$_POST["title"];}
	elseif (isset($_GET["title"]))	{$title=$_GET["title"];}
if (isset($_POST["first_name"]))	{$first_name=$_POST["first_name"];}
	elseif (isset($_GET["first_name"]))	{$first_name=$_GET["first_name"];}
if (isset($_POST["middle_initial"]))	{$middle_initial=$_POST["middle_initial"];}
	elseif (isset($_GET["middle_initial"]))	{$middle_initial=$_GET["middle_initial"];}
if (isset($_POST["last_name"]))	{$last_name=$_POST["last_name"];}
	elseif (isset($_GET["last_name"]))	{$last_name=$_GET["last_name"];}
if (isset($_POST["address1"]))	{$address1=$_POST["address1"];}
	elseif (isset($_GET["address1"]))	{$address1=$_GET["address1"];}
if (isset($_POST["address2"]))	{$address2=$_POST["address2"];}
	elseif (isset($_GET["address2"]))	{$address2=$_GET["address2"];}
if (isset($_POST["address3"]))	{$address3=$_POST["address3"];}
	elseif (isset($_GET["address3"]))	{$address3=$_GET["address3"];}
if (isset($_POST["city"]))	{$city=$_POST["city"];}
	elseif (isset($_GET["city"]))	{$city=$_GET["city"];}
if (isset($_POST["state"]))	{$state=$_POST["state"];}
	elseif (isset($_GET["state"]))	{$state=$_GET["state"];}
if (isset($_POST["province"]))	{$province=$_POST["province"];}
	elseif (isset($_GET["province"]))	{$province=$_GET["province"];}
if (isset($_POST["postal_code"]))	{$postal_code=$_POST["postal_code"];}
	elseif (isset($_GET["postal_code"]))	{$postal_code=$_GET["postal_code"];}
if (isset($_POST["country_code"]))	{$country_code=$_POST["country_code"];}
	elseif (isset($_GET["country_code"]))	{$country_code=$_GET["country_code"];}
if (isset($_POST["gender"]))	{$gender=$_POST["gender"];}
	elseif (isset($_GET["gender"]))	{$gender=$_GET["gender"];}
if (isset($_POST["date_of_birth"]))	{$date_of_birth=$_POST["date_of_birth"];}
	elseif (isset($_GET["date_of_birth"]))	{$date_of_birth=$_GET["date_of_birth"];}
if (isset($_POST["alt_phone"]))	{$alt_phone=$_POST["alt_phone"];}
	elseif (isset($_GET["alt_phone"]))	{$alt_phone=$_GET["alt_phone"];}
if (isset($_POST["email"]))	{$email=$_POST["email"];}
	elseif (isset($_GET["email"]))	{$email=$_GET["email"];}
if (isset($_POST["security_phrase"]))	{$security_phrase=$_POST["security_phrase"];}
	elseif (isset($_GET["security_phrase"]))	{$security_phrase=$_GET["security_phrase"];}
if (isset($_POST["comments"]))	{$comments=$_POST["comments"];}
	elseif (isset($_GET["comments"]))	{$comments=$_GET["comments"];}
if (isset($_POST["user"]))	{$user=$_POST["user"];}
	elseif (isset($_GET["user"]))	{$user=$_GET["user"];}
if (isset($_POST["pass"]))	{$pass=$_POST["pass"];}
	elseif (isset($_GET["pass"]))	{$pass=$_GET["pass"];}
if (isset($_POST["campaign"]))	{$campaign=$_POST["campaign"];}
	elseif (isset($_GET["campaign"]))	{$campaign=$_GET["campaign"];}
if (isset($_POST["phone_login"]))	{$phone_login=$_POST["phone_login"];}
	elseif (isset($_GET["phone_login"]))	{$phone_login=$_GET["phone_login"];}
if (isset($_POST["original_phone_login"]))	{$original_phone_login=$_POST["original_phone_login"];}
	elseif (isset($_GET["original_phone_login"]))	{$original_phone_login=$_GET["original_phone_login"];}
if (isset($_POST["phone_pass"]))	{$phone_pass=$_POST["phone_pass"];}
	elseif (isset($_GET["phone_pass"]))	{$phone_pass=$_GET["phone_pass"];}
if (isset($_POST["fronter"]))	{$fronter=$_POST["fronter"];}
	elseif (isset($_GET["fronter"]))	{$fronter=$_GET["fronter"];}
if (isset($_POST["closer"]))	{$closer=$_POST["closer"];}
	elseif (isset($_GET["closer"]))	{$closer=$_GET["closer"];}
if (isset($_POST["group"]))	{$group=$_POST["group"];}
	elseif (isset($_GET["group"]))	{$group=$_GET["group"];}
if (isset($_POST["channel_group"]))	{$channel_group=$_POST["channel_group"];}
	elseif (isset($_GET["channel_group"]))	{$channel_group=$_GET["channel_group"];}
if (isset($_POST["SQLdate"]))	{$SQLdate=$_POST["SQLdate"];}
	elseif (isset($_GET["SQLdate"]))	{$SQLdate=$_GET["SQLdate"];}
if (isset($_POST["epoch"]))	{$epoch=$_POST["epoch"];}
	elseif (isset($_GET["epoch"]))	{$epoch=$_GET["epoch"];}
if (isset($_POST["uniqueid"]))	{$uniqueid=$_POST["uniqueid"];}
	elseif (isset($_GET["uniqueid"]))	{$uniqueid=$_GET["uniqueid"];}
if (isset($_POST["customer_zap_channel"]))	{$customer_zap_channel=$_POST["customer_zap_channel"];}
	elseif (isset($_GET["customer_zap_channel"]))	{$customer_zap_channel=$_GET["customer_zap_channel"];}
if (isset($_POST["customer_server_ip"]))	{$customer_server_ip=$_POST["customer_server_ip"];}
	elseif (isset($_GET["customer_server_ip"]))	{$customer_server_ip=$_GET["customer_server_ip"];}
if (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
	elseif (isset($_GET["server_ip"]))	{$server_ip=$_GET["server_ip"];}
if (isset($_POST["SIPexten"]))	{$SIPexten=$_POST["SIPexten"];}
	elseif (isset($_GET["SIPexten"]))	{$SIPexten=$_GET["SIPexten"];}
if (isset($_POST["session_id"]))	{$session_id=$_POST["session_id"];}
	elseif (isset($_GET["session_id"]))	{$session_id=$_GET["session_id"];}
if (isset($_POST["phone"]))	{$phone=$_POST["phone"];}
	elseif (isset($_GET["phone"]))	{$phone=$_GET["phone"];}
if (isset($_POST["parked_by"]))	{$parked_by=$_POST["parked_by"];}
	elseif (isset($_GET["parked_by"]))	{$parked_by=$_GET["parked_by"];}
if (isset($_POST["dispo"]))	{$dispo=$_POST["dispo"];}
	elseif (isset($_GET["dispo"]))	{$dispo=$_GET["dispo"];}
if (isset($_POST["dialed_number"]))	{$dialed_number=$_POST["dialed_number"];}
	elseif (isset($_GET["dialed_number"]))	{$dialed_number=$_GET["dialed_number"];}
if (isset($_POST["dialed_label"]))	{$dialed_label=$_POST["dialed_label"];}
	elseif (isset($_GET["dialed_label"]))	{$dialed_label=$_GET["dialed_label"];}
if (isset($_POST["source_id"]))	{$source_id=$_POST["source_id"];}
	elseif (isset($_GET["source_id"]))	{$source_id=$_GET["source_id"];}
if (isset($_POST["rank"]))	{$rank=$_POST["rank"];}
	elseif (isset($_GET["rank"]))	{$rank=$_GET["rank"];}
if (isset($_POST["owner"]))	{$owner=$_POST["owner"];}
	elseif (isset($_GET["owner"]))	{$owner=$_GET["owner"];}
if (isset($_POST["camp_script"]))	{$camp_script=$_POST["camp_script"];}
	elseif (isset($_GET["camp_script"]))	{$camp_script=$_GET["camp_script"];}
if (isset($_POST["in_script"]))	{$in_script=$_POST["in_script"];}
	elseif (isset($_GET["in_script"]))	{$in_script=$_GET["in_script"];}
if (isset($_POST["script_width"]))	{$script_width=$_POST["script_width"];}
	elseif (isset($_GET["script_width"]))	{$script_width=$_GET["script_width"];}
if (isset($_POST["script_height"]))	{$script_height=$_POST["script_height"];}
	elseif (isset($_GET["script_height"]))	{$script_height=$_GET["script_height"];}
if (isset($_POST["fullname"]))	{$fullname=$_POST["fullname"];}
	elseif (isset($_GET["fullname"]))	{$fullname=$_GET["fullname"];}
if (isset($_POST["recording_filename"]))	{$recording_filename=$_POST["recording_filename"];}
	elseif (isset($_GET["recording_filename"]))	{$recording_filename=$_GET["recording_filename"];}
if (isset($_POST["recording_id"]))	{$recording_id=$_POST["recording_id"];}
	elseif (isset($_GET["recording_id"]))	{$recording_id=$_GET["recording_id"];}
if (isset($_POST["user_custom_one"]))	{$user_custom_one=$_POST["user_custom_one"];}
	elseif (isset($_GET["user_custom_one"]))	{$user_custom_one=$_GET["user_custom_one"];}
if (isset($_POST["user_custom_two"]))	{$user_custom_two=$_POST["user_custom_two"];}
	elseif (isset($_GET["user_custom_two"]))	{$user_custom_two=$_GET["user_custom_two"];}
if (isset($_POST["user_custom_three"]))	{$user_custom_three=$_POST["user_custom_three"];}
	elseif (isset($_GET["user_custom_three"]))	{$user_custom_three=$_GET["user_custom_three"];}
if (isset($_POST["user_custom_four"]))	{$user_custom_four=$_POST["user_custom_four"];}
	elseif (isset($_GET["user_custom_four"]))	{$user_custom_four=$_GET["user_custom_four"];}
if (isset($_POST["user_custom_five"]))	{$user_custom_five=$_POST["user_custom_five"];}
	elseif (isset($_GET["user_custom_five"]))	{$user_custom_five=$_GET["user_custom_five"];}
if (isset($_POST["preset_number_a"]))	{$preset_number_a=$_POST["preset_number_a"];}
	elseif (isset($_GET["preset_number_a"]))	{$preset_number_a=$_GET["preset_number_a"];}
if (isset($_POST["preset_number_b"]))	{$preset_number_b=$_POST["preset_number_b"];}
	elseif (isset($_GET["preset_number_b"]))	{$preset_number_b=$_GET["preset_number_b"];}
if (isset($_POST["preset_number_c"]))	{$preset_number_c=$_POST["preset_number_c"];}
	elseif (isset($_GET["preset_number_c"]))	{$preset_number_c=$_GET["preset_number_c"];}
if (isset($_POST["preset_number_d"]))	{$preset_number_d=$_POST["preset_number_d"];}
	elseif (isset($_GET["preset_number_d"]))	{$preset_number_d=$_GET["preset_number_d"];}
if (isset($_POST["preset_number_e"]))	{$preset_number_e=$_POST["preset_number_e"];}
	elseif (isset($_GET["preset_number_e"]))	{$preset_number_e=$_GET["preset_number_e"];}
if (isset($_POST["preset_number_f"]))	{$preset_number_f=$_POST["preset_number_f"];}
	elseif (isset($_GET["preset_number_f"]))	{$preset_number_f=$_GET["preset_number_f"];}
if (isset($_POST["preset_dtmf_a"]))	{$preset_dtmf_a=$_POST["preset_dtmf_a"];}
	elseif (isset($_GET["preset_dtmf_a"]))	{$preset_dtmf_a=$_GET["preset_dtmf_a"];}
if (isset($_POST["preset_dtmf_b"]))	{$preset_dtmf_b=$_POST["preset_dtmf_b"];}
	elseif (isset($_GET["preset_dtmf_b"]))	{$preset_dtmf_b=$_GET["preset_dtmf_b"];}
if (isset($_POST["ScrollDIV"]))	{$ScrollDIV=$_POST["ScrollDIV"];}
	elseif (isset($_GET["ScrollDIV"]))	{$ScrollDIV=$_GET["ScrollDIV"];}
if (isset($_POST["ignore_list_script"]))	{$ignore_list_script=$_POST["ignore_list_script"];}
	elseif (isset($_GET["ignore_list_script"]))	{$ignore_list_script=$_GET["ignore_list_script"];}
if (isset($_POST["entry_list_id"]))			{$entry_list_id=$_POST["entry_list_id"];}
	elseif (isset($_GET["entry_list_id"]))	{$entry_list_id=$_GET["entry_list_id"];}
if (isset($_POST["status"]))			{$status=$_POST["status"];}
	elseif (isset($_GET["status"]))		{$status=$_GET["status"];}
if (isset($_POST["pause"]))				{$pause=$_POST["pause"];}
	elseif (isset($_GET["pause"]))		{$pause=$_GET["pause"];}
if (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
	elseif (isset($_GET["DB"]))		{$DB=$_GET["DB"];}
if (isset($_POST["process"]))			{$process=$_POST["process"];}
	elseif (isset($_GET["process"]))	{$process=$_GET["process"];}
if (isset($_POST["vicidial_id"]))			{$vicidial_id=$_POST["vicidial_id"];}
	elseif (isset($_GET["vicidial_id"]))	{$vicidial_id=$_GET["vicidial_id"];}
if (isset($_POST["closecallid"]))			{$closecallid=$_POST["closecallid"];}
	elseif (isset($_GET["closecallid"]))	{$closecallid=$_GET["closecallid"];}

##### BEGIN Put custom fields parsing and input here #####
if (isset($_POST["question"]))			{$question=$_POST["question"];}
	elseif (isset($_GET["question"]))	{$question=$_GET["question"];}
if (isset($_POST["answer"]))		{$answer=$_POST["answer"];}
	elseif (isset($_GET["answer"]))	{$answer=$_GET["answer"];}

if (isset($_POST["call_date"]))			{$call_date=$_POST["call_date"];}
	elseif (isset($_GET["call_date"]))	{$call_date=$_GET["call_date"];}
if (isset($_POST["order_id"]))			{$order_id=$_POST["order_id"];}
	elseif (isset($_GET["order_id"]))	{$order_id=$_GET["order_id"];}
if (isset($_POST["appointment_date"]))			{$appointment_date=$_POST["appointment_date"];}
	elseif (isset($_GET["appointment_date"]))	{$appointment_date=$_GET["appointment_date"];}
if (isset($_POST["appointment_time"]))			{$appointment_time=$_POST["appointment_time"];}
	elseif (isset($_GET["appointment_time"]))	{$appointment_time=$_GET["appointment_time"];}
if (isset($_POST["call_notes"]))				{$call_notes=$_POST["call_notes"];}
	elseif (isset($_GET["call_notes"]))	{$call_notes=$_GET["call_notes"];}
if (isset($_POST["notesid"]))			{$notesid=$_POST["notesid"];}
	elseif (isset($_GET["notesid"]))	{$notesid=$_GET["notesid"];}
if ($notesid < 100)
	{$notesid=0;}
$vicidial_id = $closecallid;
if (strlen($vicidial_id) < 1)
	{$vicidial_id = $uniqueid;}
if (strlen($appointment_time) < 1)
	{$appointment_time = '12:00:00';}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

##### END Put custom fields here #####

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

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

# default optional vars if not set
if (!isset($format))   {$format="text";}
	if ($format == 'debug')	{$DB=1;}
if (!isset($ACTION))   {$ACTION="refresh";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (strlen($call_date) < 1)	{$call_date = $NOW_TIME;}

$user=preg_replace("/\'|\"|\\\\|;| /","",$user);
$pass=preg_replace("/\'|\"|\\\\|;| /","",$pass);

$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$stmt = "SELECT use_non_latin,timeclock_end_of_day,agentonly_callback_campaign_lock,custom_fields_enabled,enable_languages,language_method,allow_web_debug FROM system_settings;";
#if ($DB) {echo "$stmt\n";}
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
	$SSallow_web_debug =					$row[6];
	}
if ($SSallow_web_debug < 1) {$DB=0;}

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
##### END SETTINGS LOOKUP #####
###########################################

$lead_id = preg_replace('/[^0-9]/', '', $lead_id);
$list_id = preg_replace('/[^0-9]/', '', $list_id);
$notesid = preg_replace('/[^0-9]/', '', $notesid);
$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$server_ip);
$session_id = preg_replace('/[^0-9]/','',$session_id);
$uniqueid = preg_replace('/[^-_\.0-9a-zA-Z]/','',$uniqueid);
$campaign = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign);
$group = preg_replace('/[^-_0-9a-zA-Z]/','',$group);
$question = preg_replace("/\'|\"|\\\\|;/","",$question);
$answer = preg_replace("/\'|\"|\\\\|;/","",$answer);
$status = preg_replace("/\'|\"|\\\\|;/","",$status);
$entry_list_id = preg_replace("/^0-9]/",'',$entry_list_id);
$notesid = preg_replace('/[^0-9]/', '', $notesid);
$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$server_ip);
$session_id = preg_replace('/[^0-9]/','',$session_id);
$uniqueid = preg_replace('/[^-_\.0-9a-zA-Z]/','',$uniqueid);
$session_name = preg_replace("/\'|\"|\\\\|;/","",$session_name);
$phone_code = preg_replace("/[^0-9]/","",$phone_code);
$phone_number = preg_replace("/[^0-9]/","",$phone_number);
$vendor_id = preg_replace("/\"|\\\\|;/",'-',$vendor_id);
$vendor_lead_code = preg_replace("/\"|\\\\|;/",'-',$vendor_lead_code);
$gmt_offset_now = preg_replace("/\"|\\\\|;/",'-',$gmt_offset_now);
$title = preg_replace("/\"|\\\\|;/",'-',$title);
$first_name = preg_replace("/\"|\\\\|;/",'-',$first_name);
$middle_initial = preg_replace("/\"|\\\\|;/",'-',$middle_initial);
$last_name = preg_replace("/\"|\\\\|;/",'-',$last_name);
$address1 = preg_replace("/\"|\\\\|;/",'-',$address1);
$address2 = preg_replace("/\"|\\\\|;/",'-',$address2);
$address3 = preg_replace("/\"|\\\\|;/",'-',$address3);
$city = preg_replace("/\"|\\\\|;/",'-',$city);
$state = preg_replace("/\"|\\\\|;/",'-',$state);
$province = preg_replace("/\"|\\\\|;/",'-',$province);
$postal_code = preg_replace("/\"|\\\\|;/",'-',$postal_code);
$country_code = preg_replace("/\"|\\\\|;/",'-',$country_code);
$gender = preg_replace("/\"|\\\\|;/",'-',$gender);
$date_of_birth = preg_replace("/\"|\\\\|;/",'-',$date_of_birth);
$alt_phone = preg_replace("/\"|\\\\|;/",'-',$alt_phone);
$email = preg_replace("/\"|\\\\|;/",'-',$email);
$security_phrase = preg_replace("/\"|\\\\|;/",'-',$security_phrase);
$comments = preg_replace("/\"|\\\\|;/",'-',$comments);
$pass = preg_replace("/\<|\>|\"|\\\\|;/",'-',$pass);
$phone_login = preg_replace("/\<|\>|\"|\\\\|;/",'-',$phone_login);
$original_phone_login = preg_replace("/\<|\>|\"|\\\\|;/",'-',$original_phone_login);
$phone_pass = preg_replace("/\<|\>|\"|\\\\|;/",'-',$phone_pass);
$fronter = preg_replace("/\<|\>|\"|\\\\|;/",'-',$fronter);
$closer = preg_replace("/\<|\>|\"|\\\\|;/",'-',$closer);
$channel_group = preg_replace("/\<|\>|\"|\\\\|;/",'-',$channel_group);
$SQLdate = preg_replace("/\<|\>|\"|\\\\|;/",'-',$SQLdate);
$epoch = preg_replace("/\<|\>|\"|\\\\|;/",'-',$epoch);
$customer_zap_channel = preg_replace("/\<|\>|\"|\\\\|;/",'-',$customer_zap_channel);
$customer_server_ip = preg_replace("/\<|\>|\"|\\\\|;/",'-',$customer_server_ip);
$SIPexten = preg_replace("/\<|\>|\"|\\\\|;/",'-',$SIPexten);
$phone = preg_replace("/\<|\>|\"|\\\\|;/",'-',$phone);
$parked_by = preg_replace("/\<|\>|\"|\\\\|;/",'-',$parked_by);
$dispo = preg_replace("/\<|\>|\"|\\\\|;/",'-',$dispo);
$dialed_number = preg_replace("/\<|\>|\"|\\\\|;/",'-',$dialed_number);
$dialed_label = preg_replace("/\<|\>|\"|\\\\|;/",'-',$dialed_label);
$source_id = preg_replace("/\<|\>|\"|\\\\|;/",'-',$source_id);
$rank = preg_replace("/\<|\>|\"|\\\\|;/",'-',$rank);
$owner = preg_replace("/\<|\>|\"|\\\\|;/",'-',$owner);
$camp_script = preg_replace("/\<|\>|\"|\\\\|;/",'-',$camp_script);
$in_script = preg_replace("/\<|\>|\"|\\\\|;/",'-',$in_script);
$script_width = preg_replace("/\<|\>|\"|\\\\|;/",'-',$script_width);
$script_height = preg_replace("/\<|\>|\"|\\\\|;/",'-',$script_height);
$fullname = preg_replace("/\<|\>|\"|\\\\|;/",'-',$fullname);
$recording_filename = preg_replace("/\<|\>|\"|\\\\|;/",'-',$recording_filename);
$recording_id = preg_replace("/\<|\>|\"|\\\\|;/",'-',$recording_id);
$user_custom_one = preg_replace("/\"|\\\\|;/",'-',$user_custom_one);
$user_custom_two = preg_replace("/\"|\\\\|;/",'-',$user_custom_two);
$user_custom_three = preg_replace("/\"|\\\\|;/",'-',$user_custom_three);
$user_custom_four = preg_replace("/\"|\\\\|;/",'-',$user_custom_four);
$user_custom_five = preg_replace("/\"|\\\\|;/",'-',$user_custom_five);
$preset_number_a = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_a);
$preset_number_b = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_b);
$preset_number_c = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_c);
$preset_number_d = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_d);
$preset_number_e = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_e);
$preset_number_f = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_number_f);
$preset_dtmf_a = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_dtmf_a);
$preset_dtmf_b = preg_replace("/\<|\>|\"|\\\\|;/",'-',$preset_dtmf_b);
$ScrollDIV = preg_replace("/\<|\>|\"|\\\\|;/",'-',$ScrollDIV);
$ignore_list_script = preg_replace("/\<|\>|\"|\\\\|;/",'-',$ignore_list_script);
$DB = preg_replace("/\"|\\\\|;/",'-',$DB);
$process = preg_replace("/\"|\\\\|;/",'-',$process);
$vicidial_id = preg_replace("/\"|\\\\|;/",'-',$vicidial_id);
$call_date = preg_replace("/\"|\\\\|;/",'-',$call_date);
$order_id = preg_replace("/\"|\\\\|;/",'-',$order_id);
$appointment_date = preg_replace("/\<|\>|\"|\\\\|;/",'-',$appointment_date);
$appointment_time = preg_replace("/\<|\>|\"|\\\\|;/",'-',$appointment_time);
$call_notes  = preg_replace("/\<|\>|\"|\\\\|;/",'-',$call_notes );
$pause = preg_replace("/\<|\>|\'|\"|\\\\|;/",'-',$pause);
$closecallid = preg_replace("/[^-_0-9a-zA-Z]/",'-',$closecallid);
$question = preg_replace("/\<|\>|\"|\\\\|;/",'-',$question);
$answer  = preg_replace("/\<|\>|\"|\\\\|;/",'-',$answer);
$length_in_sec = preg_replace("/[^0-9]/","",$length_in_sec);
$phone_code = preg_replace("/[^0-9]/","",$phone_code);
$phone_number = preg_replace("/[^0-9]/","",$phone_number);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$status=preg_replace("/[^-_0-9a-zA-Z]/","",$status);
	$campaign=preg_replace("/[^-_0-9a-zA-Z]/","",$campaign);
	}
else
	{
	$user=preg_replace("/[^-_0-9\p{L}]/u","",$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$campaign=preg_replace("/[^-_0-9\p{L}]/u","",$campaign);
	$status=preg_replace("/[^-_0-9\p{L}]/u","",$status);
	}

$appointment_timeARRAY = explode(":",$appointment_time);
$appointment_hour = $appointment_timeARRAY[0];
$appointment_min = $appointment_timeARRAY[1];

if ($DB > 0)
	{
	echo "<BR>$lead_id|$entry_date|$modify_date|$status|$user|$vendor_lead_code|$source_id|$list_id|$gmt_offset_now|$called_since_last_reset|$phone_code|$phone_number|$title|$first_name|$middle_initial|$last_name|$address1|$address2|$address3|$city|$state|$province|$postal_code|$country_code|$gender|$date_of_birth|$alt_phone|$email|$security_phrase|$comments|$called_count|$last_local_call_time|$rank|$owner|\n<BR>";
	}

### BEGIN find any custom field labels ###
$label_title =				_QXZ("Title");
$label_first_name =			_QXZ("First");
$label_middle_initial =		_QXZ("MI");
$label_last_name =			_QXZ("Last");
$label_address1 =			_QXZ("Address1");
$label_address2 =			_QXZ("Address2");
$label_address3 =			_QXZ("Address3");
$label_city =				_QXZ("City");
$label_state =				_QXZ("State");
$label_province =			_QXZ("Province");
$label_postal_code =		_QXZ("PostCode");
$label_vendor_lead_code =	_QXZ("Vendor ID");
$label_gender =				_QXZ("Gender");
$label_phone_number =		_QXZ("Phone");
$label_phone_code =			_QXZ("DialCode");
$label_alt_phone =			_QXZ("Alt. Phone");
$label_security_phrase =	_QXZ("Show");
$label_email =				_QXZ("Email");
$label_comments =			_QXZ("Comments");

$stmt="SELECT label_title,label_first_name,label_middle_initial,label_last_name,label_address1,label_address2,label_address3,label_city,label_state,label_province,label_postal_code,label_vendor_lead_code,label_gender,label_phone_number,label_phone_code,label_alt_phone,label_security_phrase,label_email,label_comments from system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
if (strlen($row[0])>0)	{$label_title =				$row[0];}
if (strlen($row[1])>0)	{$label_first_name =		$row[1];}
if (strlen($row[2])>0)	{$label_middle_initial =	$row[2];}
if (strlen($row[3])>0)	{$label_last_name =			$row[3];}
if (strlen($row[4])>0)	{$label_address1 =			$row[4];}
if (strlen($row[5])>0)	{$label_address2 =			$row[5];}
if (strlen($row[6])>0)	{$label_address3 =			$row[6];}
if (strlen($row[7])>0)	{$label_city =				$row[7];}
if (strlen($row[8])>0)	{$label_state =				$row[8];}
if (strlen($row[9])>0)	{$label_province =			$row[9];}
if (strlen($row[10])>0) {$label_postal_code =		$row[10];}
if (strlen($row[11])>0) {$label_vendor_lead_code =	$row[11];}
if (strlen($row[12])>0) {$label_gender =			$row[12];}
if (strlen($row[13])>0) {$label_phone_number =		$row[13];}
if (strlen($row[14])>0) {$label_phone_code =		$row[14];}
if (strlen($row[15])>0) {$label_alt_phone =			$row[15];}
if (strlen($row[16])>0) {$label_security_phrase =	$row[16];}
if (strlen($row[17])>0) {$label_email =				$row[17];}
if (strlen($row[18])>0) {$label_comments =			$row[18];}
### END find any custom field labels ###

$auth=0;
$auth_message = user_authorization($user,$pass,'',0,0,0,0,'vdc_script_dispo');
if ($auth_message == 'GOOD')
	{$auth=1;}

if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0))
	{
	echo _QXZ("Invalid Username/Password:")." |$user|$pass|$auth_message|\n";
	exit;
	}

echo "<HTML>\n";
echo "<head>\n";
?>
<script language="Javascript">
var required_fields='<?php echo $required_fields; ?>';
var required_statuses='<?php echo $required_statuses; ?>';
var extra_statuses="<?php echo $extra_statuses; ?>";

<!-- commenting out date/time submission prep
//	var appointment_hourFORM = document.getElementById('appointment_hour');
//	var appointment_hourVALUE = appointment_hourFORM[appointment_hourFORM.selectedIndex].text;
//	var appointment_minFORM = document.getElementById('appointment_min');
//	var appointment_minVALUE = appointment_minFORM[appointment_minFORM.selectedIndex].text;
//	document.vsn.appointment_time.value = appointment_hourVALUE + ":" + appointment_minVALUE + ":00";
-->

function submit_form()
	{
	var RF_dispoFORM = document.getElementById('status');
	var RF_dispoVALUE = '|' + RF_dispoFORM[RF_dispoFORM.selectedIndex].text + '|';

	var regREQtest = new RegExp(RF_dispoVALUE,"g");
	if ( (required_statuses.match(regREQtest)) || (required_statuses == '---ALL---') )
		{
		var errors = 0;
		var RF_array = required_fields.split("|");
		var RF_array_count = RF_array.length;
		var RF_inc = 0;
		while (RF_inc < RF_array_count)
			{
			if (RF_array[RF_inc].length > 1)
				{
				var RF_field_name = RF_array[RF_inc];
				var RF_field_element = document.getElementById(RF_field_name);
				var RF_field_value = RF_field_element.value;
				if (RF_field_value.length < 1)
					{
					alert("You must fill in the " + RF_field_name + " field: " + RF_field_value + '|' + RF_field_value.length);
					errors++;
					}

				}
			RF_inc++;
			}
		}

	if (errors < 1)
		{
		document.vsn.submit();
		document.vsn.smt.disabled = true;
		document.vsn.smt.value = '<?php echo _QXZ("Processing..."); ?>';
		}
	}

</script>

<?php
echo "</script>\n";
echo "<!-- VERSION: $version     BUILD: $build    USER: $user   server_ip: $server_ip-->\n";
echo "<title>"._QXZ("ViciDial Script");
echo "</title>\n";
echo "</head>\n";
echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

if ($process > 0)
	{
	#Update vicidial_list record
	$stmt="UPDATE vicidial_list SET vendor_lead_code='" . mysqli_real_escape_string($link, $vendor_lead_code) . "',title='" . mysqli_real_escape_string($link, $title) . "',first_name='" . mysqli_real_escape_string($link, $first_name) . "',middle_initial='" . mysqli_real_escape_string($link, $middle_initial) . "',last_name='" . mysqli_real_escape_string($link, $last_name) . "',address1='" . mysqli_real_escape_string($link, $address1) . "',address2='" . mysqli_real_escape_string($link, $address2) . "',address3='" . mysqli_real_escape_string($link, $address3) . "',city='" . mysqli_real_escape_string($link, $city) . "',state='" . mysqli_real_escape_string($link, $state) . "',province='" . mysqli_real_escape_string($link, $province) . "',postal_code='" . mysqli_real_escape_string($link, $postal_code) . "',phone_code='" . mysqli_real_escape_string($link, $phone_code) . "',phone_number='" . mysqli_real_escape_string($link, $phone_number) . "',gender='" . mysqli_real_escape_string($link, $gender) . "',date_of_birth='" . mysqli_real_escape_string($link, $date_of_birth) . "',alt_phone='" . mysqli_real_escape_string($link, $alt_phone) . "',email='" . mysqli_real_escape_string($link, $email) . "',security_phrase='" . mysqli_real_escape_string($link, $security_phrase) . "',comments='" . mysqli_real_escape_string($link, $comments) . "',rank='" . mysqli_real_escape_string($link, $rank) . "',owner='" . mysqli_real_escape_string($link, $owner) . "' where lead_id=$lead_id;";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);

	#Insert/Update custom record
	$stmt="INSERT IGNORE INTO custom_$entry_list_id SET question=\"$question\",answer=\"$answer\",lead_id='$lead_id' ON DUPLICATE KEY UPDATE question=\"$question\",answer=\"$answer\";";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$CFaffected_rows = mysqli_affected_rows($link);

	$pauseSQL = '';
	$epoch = date("U");
	if ($pause == '1') {$pauseSQL = ",external_pause='PAUSE!$epoch'";}
	#Update the agent screen with new data
	$stmt="UPDATE vicidial_live_agents set external_update_fields='1',external_update_fields_data='vendor_lead_code,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,phone_code,phone_number,gender,date_of_birth,alt_phone,email,security_phrase,comments,rank,owner,question,answer,formreload,' $pauseSQL where user='$user';";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);

	if ($notesid < 100)
		{
		if (strlen($vicidial_id) < 1)
			{
			$four_hours_ago = date("Y-m-d H:i:s", mktime(date("H")-4,date("i"),date("s"),date("m"),date("d"),date("Y")));
			$stmt = "SELECT uniqueid FROM vicidial_log where call_date > \"$four_hours_ago\" and user='$user' and lead_id=$lead_id order by call_date desc limit 1;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$vid_ct = mysqli_num_rows($rslt);
			if ($vid_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$vicidial_id =	$row[0];
				}
			}
		# Insert into vicidial_call_notes
		$stmt="INSERT INTO vicidial_call_notes set lead_id=$lead_id,vicidial_id='" . mysqli_real_escape_string($link, $vicidial_id) . "',call_date='" . mysqli_real_escape_string($link, $call_date) . "',order_id='" . mysqli_real_escape_string($link, $order_id) . "',appointment_date='" . mysqli_real_escape_string($link, $appointment_date) . "',appointment_time='" . mysqli_real_escape_string($link, $appointment_time) . "',call_notes='" . mysqli_real_escape_string($link, $call_notes) . "';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		$notesid = mysqli_insert_id($link);
		}
	else
		{
		# update vicidial_call_notes record
		$stmt="UPDATE vicidial_call_notes set order_id='" . mysqli_real_escape_string($link, $order_id) . "',appointment_date='" . mysqli_real_escape_string($link, $appointment_date) . "',appointment_time='" . mysqli_real_escape_string($link, $appointment_time) . "',call_notes='" . mysqli_real_escape_string($link, $call_notes) . "' where notesid='$notesid';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		}

	echo "<BR><b>"._QXZ("Data Updated, Hanging up call, please wait")."</b><BR><BR>\n";

	sleep(3);

	#Update the agent screen with status and hangup
	$stmt="UPDATE vicidial_live_agents set external_status=\"$status\",external_hangup='1' where user='$user';";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	}

$URLarray = explode("?", $PHP_SELF);
$URLsubmit = $URLarray[0];
?>

<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=2 WIDTH=450>
<TR><TD COLSPAN=2 ALIGN=CENTER>
<FORM METHOD=POST NAME=vsn ID=vsn ACTION="<?php echo $URLsubmit ?>">
<input type=hidden name=DB id=DB value=<?php echo $DB ?>>
<input type=hidden name=process id=process value=1>
<input type=hidden name=lead_id id=lead_id value="<?php echo $lead_id ?>">
<input type=hidden name=user id=user value="<?php echo $user ?>">
<input type=hidden name=pass id=user value="<?php echo $pass ?>">
<input type=hidden name=notesid id=notesid value="<?php echo $notesid ?>">
<input type=hidden name=phone_code id=phone_code value="<?php echo $phone_code ?>">
<input type=hidden name=gender id=gender value="<?php echo $gender ?>">
<input type=hidden name=date_of_birth id=date_of_birth value="<?php echo $date_of_birth ?>">
<input type=hidden name=rank id=rank value="<?php echo $rank ?>">
<input type=hidden name=owner id=owner value="<?php echo $owner ?>">
<input type=hidden name=entry_list_id id=entry_list_id value="<?php echo $entry_list_id ?>">
<!-- unused hidden variables go here
<input type=hidden name=vendor_id id=vendor_id value="<?php echo $vendor_id ?>">
<input type=hidden name=title id=title value="<?php echo $title ?>">
<input type=hidden name=middle_initial id=middle_initial value="<?php echo $middle_initial ?>">
<input type=hidden name=province id=province value="<?php echo $middle_initial ?>">
<input type=hidden name=alt_phone id=alt_phone value="<?php echo $alt_phone ?>">
<input type=hidden name=email id=email value="<?php echo $email ?>">
<input type=hidden name=security_phrase id=security_phrase value="<?php echo $security_phrase ?>">
<input type=hidden name=comments id=comments value="<?php echo $comments ?>">
-->
</TD></TR>

<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Vendor ID:"); ?> </TD><TD ALIGN=LEFT><input type=text name=vendor_id id=vendor_id size=20 maxlength=20 value="<?php echo $vendor_id ?>"></TD>
</TR>
<!-- <TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Source ID: </TD><TD ALIGN=LEFT>$source_id<input type=hidden name=source_id id=source_id value="<?php echo $source_id ?>"></TD>
</TR> -->
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Title:"); ?> </TD><TD ALIGN=LEFT><input type=text name=title id=title size=5 maxlength=4 value="<?php echo $title ?>"></TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_first_name ?>: </TD><TD ALIGN=LEFT><input type=text name=first_name id=first_name size=30 maxlength=30 value="<?php echo $first_name ?>"> *</TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Middle Initial:"); ?> </TD><TD ALIGN=LEFT><input type=text name=middle_initial id=middle_initial size=2 maxlength=1 value="<?php echo $middle_initial ?>"></TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_last_name ?>: </TD><TD ALIGN=LEFT><input type=text name=last_name id=last_name size=30 maxlength=30 value="<?php echo $last_name ?>"> *</TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_address1 ?>: </TD><TD ALIGN=LEFT><input type=text name=address1 id=address1 size=30 maxlength=100 value="<?php echo $address1 ?>"> *</TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_address2 ?>: </TD><TD ALIGN=LEFT><input type=text name=address2 id=address2 size=30 maxlength=100 value="<?php echo $address2 ?>"></TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_address3 ?>: </TD><TD ALIGN=LEFT><input type=text name=address3 id=address3 size=30 maxlength=100 value="<?php echo $address3 ?>"></TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_city ?>: </TD><TD ALIGN=LEFT><input type=text name=city id=city size=30 maxlength=50 value="<?php echo $city ?>"> *</TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_state ?>: </TD><TD ALIGN=LEFT>
<SELECT name="state" id=state>
<OPTION value="<?php echo $state ?>" selected><?php echo $state ?></OPTION>
<OPTGROUP label="United States"> 
<OPTION value="AL">Alabama</OPTION>
<OPTION value="AK">Alaska</OPTION>
<OPTION value="AZ">Arizona</OPTION>
<OPTION value="AR">Arkansas</OPTION>
<OPTION value="CA">California</OPTION>
<OPTION value="CO">Colorado</OPTION>
<OPTION value="CT">Connecticut</OPTION>
<OPTION value="DE">Delaware</OPTION>
<OPTION value="FL">Florida</OPTION>
<OPTION value="GA">Georgia</OPTION>
<OPTION value="HI">Hawaii</OPTION>
<OPTION value="ID">Idaho</OPTION>
<OPTION value="IL">Illinois</OPTION>
<OPTION value="IN">Indiana</OPTION>
<OPTION value="IA">Iowa</OPTION>
<OPTION value="KS">Kansas</OPTION>
<OPTION value="KY">Kentucky</OPTION>
<OPTION value="LA">Louisiana</OPTION>
<OPTION value="ME">Maine</OPTION>
<OPTION value="MD">Maryland</OPTION>
<OPTION value="MA">Massachusetts</OPTION>
<OPTION value="MI">Michigan</OPTION>
<OPTION value="MN">Minnesota</OPTION>
<OPTION value="MS">Mississippi</OPTION>
<OPTION value="MO">Missouri</OPTION>
<OPTION value="MT">Montana</OPTION>
<OPTION value="NE">Nebraska</OPTION>
<OPTION value="NV">Nevada</OPTION>
<OPTION value="NH">New Hampshire</OPTION>
<OPTION value="NJ">New Jersey</OPTION>
<OPTION value="NM">New Mexico</OPTION>
<OPTION value="NY">New York</OPTION>
<OPTION value="NC">North Carolina</OPTION>
<OPTION value="ND">North Dakota</OPTION>
<OPTION value="OH">Ohio</OPTION>
<OPTION value="OK">Oklahoma</OPTION>
<OPTION value="OR">Oregon</OPTION>
<OPTION value="PA">Pennsylvania</OPTION>
<OPTION value="RI">Rhode Island</OPTION>
<OPTION value="SC">South Carolina</OPTION>
<OPTION value="SD">South Dakota</OPTION>
<OPTION value="TN">Tennessee</OPTION>
<OPTION value="TX">Texas</OPTION>
<OPTION value="UT">Utah</OPTION>
<OPTION value="VT">Vermont</OPTION>
<OPTION value="VA">Virginia</OPTION>
<OPTION value="WA">Washington</OPTION>
<OPTION value="DC">Washington, DC</OPTION>
<OPTION value="WV">West Virginia</OPTION>
<OPTION value="WI">Wisconsin</OPTION>
<OPTION value="WY">Wyoming</OPTION>
</OPTGROUP>
<!--
<OPTGROUP label="Canada"> 
<OPTION value="AB">ALBERTA</OPTION>
<OPTION value="NT">NORTHWEST TERRITORY</OPTION>
<OPTION value="BC">BRITISH COLUMBIA</OPTION>
<OPTION value="ON">ONTARIO</OPTION>
<OPTION value="LB">LABRADOR</OPTION>
<OPTION value="PE">PRINCE EDWARDISLAND</OPTION>
<OPTION value="MB">MANITOBA</OPTION>
<OPTION value="PQ">QUEBEC</OPTION>
<OPTION value="NB">NEW BRUNSWICK</OPTION>
<OPTION value="SK">SASKATCHEWAN</OPTION>
<OPTION value="NF">NEWFOUNDLAND</OPTION>
<OPTION value="YT">YUKON TERRITORY</OPTION>
<OPTION value="NS">NOVA SCOTIA</OPTION>
</OPTGROUP>
-->
</SELECT> *</TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Province:"); ?> </TD><TD ALIGN=LEFT><input type=text name=province id=province size=20 maxlength=50 value="<?php echo $province ?>"></TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_postal_code ?>: </TD><TD ALIGN=LEFT><input type=text name=postal_code id=postal_code size=6 maxlength=5 value="<?php echo $postal_code ?>"> *</TD>
</TR>
<!-- <TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Phone Code: </TD><TD ALIGN=LEFT><input type=text name=phone_code id=phone_code size=10 maxlength=10 value="<?php echo $phone_code ?>"></TD>
</TR> -->
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo $label_phone_number ?>: </TD><TD ALIGN=LEFT><input type=text name=phone_number id=phone_number size=18 maxlength=18 value="<?php echo $phone_number ?>"> *</TD>
</TR>
<!-- <TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Gender: </TD><TD ALIGN=LEFT><input type=text name=gender id=gender size=2 maxlength=1 value="<?php echo $gender ?>"></TD>
</TR> -->
<!-- <TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Date of Birth: </TD><TD ALIGN=LEFT><input type=text name=date_if_birth id=date_if_birth size=12 maxlength=12 value="<?php echo $date_of_birth ?>"></TD>
</TR> -->
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Alt. Phone:"); ?> </TD><TD ALIGN=LEFT><input type=text name=alt_phone id=alt_phone size=12 maxlength=12 value="<?php echo $alt_phone ?>"> *</TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Email:"); ?> </TD><TD ALIGN=LEFT><input type=text name=email id=email size=30 maxlength=70 value="<?php echo $email ?>"> *</TD>
</TR>
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Show:"); ?> </TD><TD ALIGN=LEFT><input type=text name=security_phrase id=security_phrase size=30 maxlength=100 value="<?php echo $security_phrase ?>"> *</TD>
</TR>
<!-- <TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Comments: </TD><TD ALIGN=LEFT><input type=text name=comments id=comments size=40 maxlength=255 value="<?php echo $comments ?>"> *</TD>
</TR> -->
<!-- <TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Rank: </TD><TD ALIGN=LEFT><input type=text name=rank id=rank size=5 maxlength=5 value="<?php echo $rank ?>"> *</TD>
</TR> -->
<!-- <TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Owner: </TD><TD ALIGN=LEFT><input type=text name=owner id=owner size=20 maxlength=20 value="<?php echo $owner ?>"> *</TD>
</TR> -->

<!-- commenting out date/time fields
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Appointment Date/Time: </TD><TD ALIGN=LEFT><input type=text name=appointment_date id=appointment_date size=10 maxlength=10 value="<?php echo $appointment_date ?>">

<script language="JavaScript">
var o_cal = new tcal ({
	// form name
	'formname': 'vsn',
	// input name
	'controlname': 'appointment_date'
});
o_cal.a_tpl.yearscroll = false;
// o_cal.a_tpl.weekstart = 1; // Monday week start
</script>

<input type=hidden name=appointment_time id=appointment_time value="<?php echo $appointment_time ?>">
<SELECT name=appointment_hour id=appointment_hour>
<option>00</option>
<option>01</option>
<option>02</option>
<option>03</option>
<option>04</option>
<option>05</option>
<option>06</option>
<option>07</option>
<option>08</option>
<option>09</option>
<option>10</option>
<option>11</option>
<option>12</option>
<option>13</option>
<option>14</option>
<option>15</option>
<option>16</option>
<option>17</option>
<option>18</option>
<option>19</option>
<option>20</option>
<option>21</option>
<option>22</option>
<option>23</option>
<OPTION value="<?php echo $appointment_hour ?>" selected><?php echo $appointment_hour ?></OPTION>
</SELECT>
<SELECT name=appointment_min id=appointment_min>
<option>00</option>
<option>05</option>
<option>10</option>
<option>15</option>
<option>20</option>
<option>25</option>
<option>30</option>
<option>35</option>
<option>40</option>
<option>45</option>
<option>50</option>
<option>55</option>
<OPTION value="<?php echo $appointment_min ?>" selected><?php echo $appointment_min ?></OPTION>
</SELECT>

</TD>
</TR>
-->

<TR BGCOLOR="#E6E6E6">
<TD ALIGN=CENTER COLSPAN=2><FONT FACE="ARIAL,HELVETICA" size=2><?php echo _QXZ("Comments:"); ?><BR><TEXTAREA NAME=comments ID=comments ROWS=5 COLS=50><?php echo $comments ?></TEXTAREA></font><br>
</TD>
</TR>

<!-- <TR BGCOLOR="#E6E6E6">
<TD ALIGN=CENTER COLSPAN=2><FONT FACE="ARIAL,HELVETICA" size=2>Call Notes:<BR><TEXTAREA NAME=call_notes ID=call_notes ROWS=5 COLS=50><?php echo $call_notes ?></TEXTAREA></font><br>
</TD>
</TR> -->

<!-- commented BEGIN put custom fields here
<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA">Order ID: </TD><TD ALIGN=LEFT><input type=text name=order_id id=order_id size=20 maxlength=20 value="<?php echo $order_id ?>"></TD>
</TR>

commented END put custom fields here -->

<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Question:"); ?> </TD><TD ALIGN=LEFT><input type=text name=question id=question size=30 maxlength=50 value="<?php echo $question ?>"></TD>
</TR>

<TR BGCOLOR="#E6E6E6">
<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("Answer:"); ?> </TD><TD ALIGN=LEFT><input type=text name=answer id=answer size=30 maxlength=50 value="<?php echo $answer ?>"></TD>
</TR>


<?php
echo "<tr bgcolor=#E6E6E6><td align=right>"._QXZ("Disposition:")." </td><td align=left><select size=1 name=status id=status>\n";


$stmt="SELECT status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable from vicidial_statuses where selectable='Y' or status IN($extra_statuses) order by status";
$rslt=mysql_to_mysqli($stmt, $link);
$statuses_to_print = mysqli_num_rows($rslt);
$statuses_list='';

$o=0;
$DS=0;
while ($statuses_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	if ( (strlen($dispo) ==  strlen($rowx[0])) and (preg_match("/$dispo/i",$rowx[0])) )
		{$statuses_list .= "<option SELECTED value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n"; $DS++;}
	else
		{$statuses_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";}
	$o++;
	}

$stmt="SELECT status,status_name,selectable,campaign_id,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable from vicidial_campaign_statuses where (selectable='Y' or status IN($extra_statuses)) and campaign_id='$campaign' order by status";
$rslt=mysql_to_mysqli($stmt, $link);
$CAMPstatuses_to_print = mysqli_num_rows($rslt);

$o=0;
$CBhold_set=0;
while ($CAMPstatuses_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	if ( (strlen($dispo) ==  strlen($rowx[0])) and (preg_match("/$dispo/i",$rowx[0])) )
		{$statuses_list .= "<option SELECTED value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n"; $DS++;}
	else
		{$statuses_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";}
	$o++;
	}

echo "$statuses_list";
echo "</select></td></tr>\n";


?>

<TR BGCOLOR=white>
<TD ALIGN=CENTER COLSPAN=2>

<input type=button value="SUBMIT and HANGUP CALL" name=smt id=smt onClick="submit_form()">
 &nbsp; &nbsp; &nbsp; &nbsp; <input type=checkbox name=pause id=pause size="1" value="1" /> <?php echo _QXZ("PAUSE AGENT DIALING"); ?> 
</TD>
</TR>

<TR BGCOLOR="#E6E6E6">
<TD ALIGN=CENTER COLSPAN=2><FONT FACE="ARIAL,HELVETICA" size=1><?php echo _QXZ("Please click SUBMIT to hangup and disposition the call"); ?>, &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; * <?php echo _QXZ("denotes required fields"); ?></font><br>
</TD>
</TR>

</TABLE>

</FORM>
</CENTER>

</B></FONT>
</TD>

</TR>
</TABLE>


</BODY>
</HTML>
