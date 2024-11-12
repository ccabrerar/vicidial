<?php
# api.php
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed as an API(Application Programming Interface) to allow
# other programs to interact with the VICIDIAL Agent screen
# 
# required variables:
#  - $user
#  - $pass
#  - $agent_user
#  - $function - ('external_hangup','external_status','external_pause','external_dial','change_ingroups',...)
#  - $value
#  - $vendor_id
#  - $focus
#  - $preview
#  - $notes
#  - $phone_code
#  - $search
#  - $group_alias
#  - $dial_prefix
#  - $source - ('vtiger','webform','adminweb')
#  - $format - ('text','debug')
#  - $vtiger_callback - ('YES','NO')
#  - $blended - ('YES','NO')
#  - $ingroup_choices - (' TEST_IN SALESLINE -')
#  - $set_as_default - ('YES','NO')
#  - $alt_user
#  - $stage
#  - $status
#  - $close_window_link
#  - $language
#  - $alt_dial - ('','MAIN','ALT','ADDR3')

# CHANGELOG:
# 80703-2225 - First build of script
# 90116-1229 - Added external_pause and external_dial functions
# 90118-1051 - Added logging of API functions
# 90128-0229 - Added vendor_id to dial function
# 90303-0723 - Added group alias and dial prefix
# 90407-1920 - Added vtiger_callback option for external_dial function
# 90508-0727 - Changed to PHP long tags
# 90522-0506 - Security fix
# 91130-1307 - Added change_ingroups(Manager InGroup change feature)
# 91211-1805 - Added st_login_log and st_get_agent_active_lead functions, added alt_user
# 91228-1059 - Added update_fields function
# 100315-2021 - Added ra_call_control function
# 100318-0605 - Added close_window_link and language options
# 100401-2357 - Added external_add_lead function (contributed by aouyar)
# 100527-0926 - Added send_dtmf, transfer_conference and park_call functions
# 100914-1538 - Fixed bug in change_ingroups function
# 101123-1050 - Added manual dial queue features to external_dial function
# 110224-1711 - Added compatibility with QM phone environment logging
# 110409-0821 - Added run_time logging of API functions
# 110430-0953 - Added option to external_dial by lead_id with alt_dial option
# 110911-1555 - Added logout function
# 111114-0037 - Added scheduled callback and qm-dispo-code fields to external_status function
# 120301-1745 - Fixed ereg statements dashes
# 120529-1551 - Fixed callback_datetime filter
# 120731-1206 - Allow dot in vendor_id
# 120809-2338 - Added recording and webserver functions
# 120819-1758 - Added webphone_url and call_agent functions
# 120913-2039 - Added group_alias to transfer_conference function
# 121120-0855 - Added QM socket-send functionality
# 121124-2354 - Added Other Campaign DNC option
# 130328-0010 - Converted ereg to preg functions
# 130603-2221 - Added login lockout for 15 minutes after 10 failed logins, and other security fixes
# 130705-1526 - Added optional encrypted passwords compatibility
# 130802-1000 - Changed to PHP mysqli functions
# 140107-2140 - Added webserver and url logging
# 140126-0701 - Added pause_code function
# 140214-1736 - Added preview_dial_action function
# 140301-2046 - Added options to dial next number and search for lead phone number
# 140403-1738 - Added option to append filename on recording start
# 140428-1656 - Added pause_type logging to queue_log pause/unpause entries for ra_call_control function
# 140619-1006 - Added basic audio_playback function
# 140811-1243 - Changed to use QXZ function for echoing text
# 141128-0847 - Code cleanup for QXZ functions
# 141216-2118 - Added language settings lookups and user/pass variable standardization
# 150108-1039 - Added transfer_conf-ID of epoch to help prevent double-execution of transfer commands
# 150313-0825 - Allow for single quotes in vicidial_list and custom data fields
# 150429-1717 - Added user allowed function restrictions
# 150512-2027 - Added filtering of hash sign on some input variables, Issue #851
# 150626-2120 - Modified mysqli_error() to mysqli_connect_error() where appropriate
# 150928-1157 - Fix allowing for * and # in phone_number field
# 160113-0921 - Fix for numeric audio files in playback function
# 161102-1042 - Fixed QM partition problem
# 161103-1729 - Added agent_debug to audio playing
# 170209-1222 - Added URL and IP logging
# 170220-1303 - Added switch_lead function
# 170527-2250 - Fix for rare inbound logging issue #1017, Added variable filtering
# 170815-1314 - Added HTTP error code 418
# 180124-1608 - Added calls_in_queue_count function
# 180204-2350 - Added dial_ingroup external_dial option
# 180301-2302 - Added GET-AND-POST URL logging
# 180323-2227 - Fix for dial_ingroup error message on external_dial function
# 180903-1606 - Added count for waiting emails to calls_in_queue_count function
# 180908-1433 - Added force_fronter_leave_3way function
# 190222-1152 - Added force_fronter_audio_stop function
# 190901-0952 - Added cid_choice option to transfer_conference function
# 200403-1510 - Added outbound_cid option to external_dial function
# 201112-2053 - Added vm_message function
# 210116-1138 - Addressed session ID issue in ticket #1251
# 210222-1058 - Added call length logging to ra_call_control function
# 210320-2335 - Added additional update_fields options: scriptreload,script2reload,formreload,emailreload,chatreload
# 210616-2057 - Added optional CORS support, see options.php for details
# 210819-0902 - Updated change_ingroups for changes in vicidial_live_inbound_agents insert/update
# 220220-0847 - Added allow_web_debug system setting
# 230412-0945 - Added send_notification function
# 230413-1957 - Fix for send_notification user group permissions
# 230519-0731 - Fix for input variable filtering
# 231129-1457 - Added refresh_panel function
# 231222-2105 - Added multi_dial_phones option to the transfer_conference function
# 240219-1500 - Added daily_limit setting to change_ingroups function
# 240425-1901 - Added md_check option for transfer_conference function
# 240427-0809 - Added tw_check option for transfer_conference function
# 240429-2220 - Added PARK_XFER|GRAB_XFER options for park_call function
#

$version = '2.14-81';
$build = '240429-2220';
$php_script = 'api.php';

$startMS = microtime();

require_once("dbconnect_mysqli.php");
require_once("functions.php");

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

### If you have globals turned off uncomment these lines
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["pass"]))						{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))				{$pass=$_POST["pass"];}
if (isset($_GET["agent_user"]))					{$agent_user=$_GET["agent_user"];}
	elseif (isset($_POST["agent_user"]))		{$agent_user=$_POST["agent_user"];}
if (isset($_GET["function"]))					{$function=$_GET["function"];}
	elseif (isset($_POST["function"]))			{$function=$_POST["function"];}
if (isset($_GET["value"]))						{$value=$_GET["value"];}
	elseif (isset($_POST["value"]))				{$value=$_POST["value"];}
if (isset($_GET["vendor_id"]))					{$vendor_id=$_GET["vendor_id"];}
	elseif (isset($_POST["vendor_id"]))			{$vendor_id=$_POST["vendor_id"];}
if (isset($_GET["focus"]))						{$focus=$_GET["focus"];}
	elseif (isset($_POST["focus"]))				{$focus=$_POST["focus"];}
if (isset($_GET["preview"]))					{$preview=$_GET["preview"];}
	elseif (isset($_POST["preview"]))			{$preview=$_POST["preview"];}
if (isset($_GET["notes"]))						{$notes=$_GET["notes"];}
	elseif (isset($_POST["notes"]))				{$notes=$_POST["notes"];}
if (isset($_GET["phone_code"]))					{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))		{$phone_code=$_POST["phone_code"];}
if (isset($_GET["search"]))						{$search=$_GET["search"];}
	elseif (isset($_POST["search"]))			{$search=$_POST["search"];}
if (isset($_GET["group_alias"]))				{$group_alias=$_GET["group_alias"];}
	elseif (isset($_POST["group_alias"]))		{$group_alias=$_POST["group_alias"];}
if (isset($_GET["dial_prefix"]))				{$dial_prefix=$_GET["dial_prefix"];}
	elseif (isset($_POST["dial_prefix"]))		{$dial_prefix=$_POST["dial_prefix"];}
if (isset($_GET["source"]))						{$source=$_GET["source"];}
	elseif (isset($_POST["source"]))			{$source=$_POST["source"];}
if (isset($_GET["format"]))						{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))			{$format=$_POST["format"];}
if (isset($_GET["vtiger_callback"]))			{$vtiger_callback=$_GET["vtiger_callback"];}
	elseif (isset($_POST["vtiger_callback"]))	{$vtiger_callback=$_POST["vtiger_callback"];}
if (isset($_GET["blended"]))					{$blended=$_GET["blended"];}
	elseif (isset($_POST["blended"]))			{$blended=$_POST["blended"];}
if (isset($_GET["ingroup_choices"]))			{$ingroup_choices=$_GET["ingroup_choices"];}
	elseif (isset($_POST["ingroup_choices"]))	{$ingroup_choices=$_POST["ingroup_choices"];}
if (isset($_GET["set_as_default"]))				{$set_as_default=$_GET["set_as_default"];}
	elseif (isset($_POST["set_as_default"]))	{$set_as_default=$_POST["set_as_default"];}
if (isset($_GET["alt_user"]))					{$alt_user=$_GET["alt_user"];}
	elseif (isset($_POST["alt_user"]))			{$alt_user=$_POST["alt_user"];}
if (isset($_GET["lead_id"]))					{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))			{$lead_id=$_POST["lead_id"];}
if (isset($_GET["phone_number"]))				{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))		{$phone_number=$_POST["phone_number"];}
if (isset($_GET["vendor_lead_code"]))			{$vendor_lead_code=$_GET["vendor_lead_code"];}
	elseif (isset($_POST["vendor_lead_code"]))	{$vendor_lead_code=$_POST["vendor_lead_code"];}
if (isset($_GET["source_id"]))					{$source_id=$_GET["source_id"];}
	elseif (isset($_POST["source_id"]))			{$source_id=$_POST["source_id"];}
if (isset($_GET["gmt_offset_now"]))				{$gmt_offset_now=$_GET["gmt_offset_now"];}
	elseif (isset($_POST["gmt_offset_now"]))	{$gmt_offset_now=$_POST["gmt_offset_now"];}
if (isset($_GET["title"]))						{$title=$_GET["title"];}
	elseif (isset($_POST["title"]))				{$title=$_POST["title"];}
if (isset($_GET["first_name"]))					{$first_name=$_GET["first_name"];}
	elseif (isset($_POST["first_name"]))		{$first_name=$_POST["first_name"];}
if (isset($_GET["middle_initial"]))				{$middle_initial=$_GET["middle_initial"];}
	elseif (isset($_POST["middle_initial"]))	{$middle_initial=$_POST["middle_initial"];}
if (isset($_GET["last_name"]))					{$last_name=$_GET["last_name"];}
	elseif (isset($_POST["last_name"]))			{$last_name=$_POST["last_name"];}
if (isset($_GET["address1"]))					{$address1=$_GET["address1"];}
	elseif (isset($_POST["address1"]))			{$address1=$_POST["address1"];}
if (isset($_GET["address2"]))					{$address2=$_GET["address2"];}
	elseif (isset($_POST["address2"]))			{$address2=$_POST["address2"];}
if (isset($_GET["address3"]))					{$address3=$_GET["address3"];}
	elseif (isset($_POST["address3"]))			{$address3=$_POST["address3"];}
if (isset($_GET["city"]))						{$city=$_GET["city"];}
	elseif (isset($_POST["city"]))				{$city=$_POST["city"];}
if (isset($_GET["state"]))						{$state=$_GET["state"];}
	elseif (isset($_POST["state"]))				{$state=$_POST["state"];}
if (isset($_GET["province"]))					{$province=$_GET["province"];}
	elseif (isset($_POST["province"]))			{$province=$_POST["province"];}
if (isset($_GET["postal_code"]))				{$postal_code=$_GET["postal_code"];}
	elseif (isset($_POST["postal_code"]))		{$postal_code=$_POST["postal_code"];}
if (isset($_GET["country_code"]))				{$country_code=$_GET["country_code"];}
	elseif (isset($_POST["country_code"]))		{$country_code=$_POST["country_code"];}
if (isset($_GET["gender"]))						{$gender=$_GET["gender"];}
	elseif (isset($_POST["gender"]))			{$gender=$_POST["gender"];}
if (isset($_GET["date_of_birth"]))				{$date_of_birth=$_GET["date_of_birth"];}
	elseif (isset($_POST["date_of_birth"]))		{$date_of_birth=$_POST["date_of_birth"];}
if (isset($_GET["alt_phone"]))					{$alt_phone=$_GET["alt_phone"];}
	elseif (isset($_POST["alt_phone"]))			{$alt_phone=$_POST["alt_phone"];}
if (isset($_GET["email"]))						{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))				{$email=$_POST["email"];}
if (isset($_GET["security_phrase"]))			{$security_phrase=$_GET["security_phrase"];}
	elseif (isset($_POST["security_phrase"]))	{$security_phrase=$_POST["security_phrase"];}
if (isset($_GET["comments"]))					{$comments=$_GET["comments"];}
	elseif (isset($_POST["comments"]))			{$comments=$_POST["comments"];}
if (isset($_GET["rank"]))						{$rank=$_GET["rank"];}
	elseif (isset($_POST["rank"]))				{$rank=$_POST["rank"];}
if (isset($_GET["owner"]))						{$owner=$_GET["owner"];}
	elseif (isset($_POST["owner"]))				{$owner=$_POST["owner"];}
if (isset($_GET["stage"]))						{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))				{$stage=$_POST["stage"];}
if (isset($_GET["status"]))						{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))			{$status=$_POST["status"];}
if (isset($_GET["close_window_link"]))			{$close_window_link=$_GET["close_window_link"];}
	elseif (isset($_POST["close_window_link"]))	{$close_window_link=$_POST["close_window_link"];}
if (isset($_GET["dnc_check"]))					{$dnc_check=$_GET["dnc_check"];}
	elseif (isset($_POST["dnc_check"]))			{$dnc_check=$_POST["dnc_check"];}
if (isset($_GET["campaign_dnc_check"]))				{$campaign_dnc_check=$_GET["campaign_dnc_check"];}
	elseif (isset($_POST["campaign_dnc_check"]))	{$campaign_dnc_check=$_POST["campaign_dnc_check"];}
if (isset($_GET["dial_override"]))				{$dial_override=$_GET["dial_override"];}
	elseif (isset($_POST["dial_override"]))		{$dial_override=$_POST["dial_override"];}
if (isset($_GET["consultative"]))				{$consultative=$_GET["consultative"];}
	elseif (isset($_POST["consultative"]))		{$consultative=$_POST["consultative"];}
if (isset($_GET["alt_dial"]))					{$alt_dial=$_GET["alt_dial"];}
	elseif (isset($_POST["alt_dial"]))			{$alt_dial=$_POST["alt_dial"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["callback_datetime"]))			{$callback_datetime=$_GET["callback_datetime"];}
	elseif (isset($_POST["callback_datetime"]))	{$callback_datetime=$_POST["callback_datetime"];}
if (isset($_GET["callback_type"]))			{$callback_type=$_GET["callback_type"];}
	elseif (isset($_POST["callback_type"]))	{$callback_type=$_POST["callback_type"];}
if (isset($_GET["callback_comments"]))			{$callback_comments=$_GET["callback_comments"];}
	elseif (isset($_POST["callback_comments"]))	{$callback_comments=$_POST["callback_comments"];}
if (isset($_GET["qm_dispo_code"]))			{$qm_dispo_code=$_GET["qm_dispo_code"];}
	elseif (isset($_POST["qm_dispo_code"]))	{$qm_dispo_code=$_POST["qm_dispo_code"];}
if (isset($_GET["agent_debug"]))			{$agent_debug=$_GET["agent_debug"];}
	elseif (isset($_POST["agent_debug"]))	{$agent_debug=$_POST["agent_debug"];}
if (isset($_GET["dial_ingroup"]))			{$dial_ingroup=$_GET["dial_ingroup"];}
	elseif (isset($_POST["dial_ingroup"]))	{$dial_ingroup=$_POST["dial_ingroup"];}
if (isset($_GET["cid_choice"]))				{$cid_choice=$_GET["cid_choice"];}
	elseif (isset($_POST["cid_choice"]))	{$cid_choice=$_POST["cid_choice"];}
if (isset($_GET["outbound_cid"]))			{$outbound_cid=$_GET["outbound_cid"];}
	elseif (isset($_POST["outbound_cid"]))	{$outbound_cid=$_POST["outbound_cid"];}
if (isset($_GET["duration"]))			{$duration=$_GET["duration"];}
	elseif (isset($_POST["duration"]))	{$duration=$_POST["duration"];}
if (isset($_GET["recipient"]))				{$recipient=$_GET["recipient"];}
	elseif (isset($_POST["recipient"]))		{$recipient=$_POST["recipient"];}
if (isset($_GET["recipient_type"]))				{$recipient_type=$_GET["recipient_type"];}
	elseif (isset($_POST["recipient_type"]))	{$recipient_type=$_POST["recipient_type"];}
if (isset($_GET["show_confetti"]))				{$show_confetti=$_GET["show_confetti"];}
	elseif (isset($_POST["show_confetti"]))		{$show_confetti=$_POST["show_confetti"];}
if (isset($_GET["maxParticleCount"]))			{$maxParticleCount=$_GET["maxParticleCount"];}
	elseif (isset($_POST["maxParticleCount"]))	{$maxParticleCount=$_POST["maxParticleCount"];}
if (isset($_GET["particleSpeed"]))			{$particleSpeed=$_GET["particleSpeed"];}
	elseif (isset($_POST["particleSpeed"]))	{$particleSpeed=$_POST["particleSpeed"];}
if (isset($_GET["notification_date"]))				{$notification_date=$_GET["notification_date"];}
	elseif (isset($_POST["notification_date"]))		{$notification_date=$_POST["notification_date"];}
if (isset($_GET["notification_text"]))				{$notification_text=$_GET["notification_text"];}
	elseif (isset($_POST["notification_text"]))		{$notification_text=$_POST["notification_text"];}
if (isset($_GET["notification_retry"]))				{$notification_retry=$_GET["notification_retry"];}
	elseif (isset($_POST["notification_retry"]))	{$notification_retry=$_POST["notification_retry"];}
if (isset($_GET["text_size"]))				{$text_size=$_GET["text_size"];}
	elseif (isset($_POST["text_size"]))		{$text_size=$_POST["text_size"];}
if (isset($_GET["text_font"]))				{$text_font=$_GET["text_font"];}
	elseif (isset($_POST["text_font"]))		{$text_font=$_POST["text_font"];}
if (isset($_GET["text_weight"]))			{$text_weight=$_GET["text_weight"];}
	elseif (isset($_POST["text_weight"]))	{$text_weight=$_POST["text_weight"];}
if (isset($_GET["text_color"]))				{$text_color=$_GET["text_color"];}
	elseif (isset($_POST["text_color"]))	{$text_color=$_POST["text_color"];}
if (isset($_GET["multi_dial_phones"]))			{$multi_dial_phones=$_GET["multi_dial_phones"];}
	elseif (isset($_POST["multi_dial_phones"]))	{$multi_dial_phones=$_POST["multi_dial_phones"];}
if (isset($_GET["md_check"]))			{$md_check=$_GET["md_check"];}
	elseif (isset($_POST["md_check"]))	{$md_check=$_POST["md_check"];}
if (isset($_GET["tw_check"]))			{$tw_check=$_GET["tw_check"];}
	elseif (isset($_POST["tw_check"]))	{$tw_check=$_POST["tw_check"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require('options.php');
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

$user = preg_replace("/'|\"|\\\\|;| /","",$user);
$pass = preg_replace("/'|\"|\\\\|;| /","",$pass);

#############################################
##### START SYSTEM_SETTINGS AND USER LANGUAGE LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,agent_debug_logging,outbound_cid_any,allow_web_debug,agent_notifications FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$SSagent_debug_logging =	$row[3];
	$SSoutbound_cid_any =		$row[4];
	$SSallow_web_debug =		$row[5];
	$SSagent_notifications =	$row[6];
	}
if ($SSallow_web_debug < 1) {$DB=0;}

$VUselected_language = '';
$stmt="SELECT selected_language,api_list_restrict,api_allowed_functions,user_group from vicidial_users where user='$user';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00XXX',$user,$server_ip,$session_name,$one_mysql_log);}
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$VUapi_list_restrict =		$row[1];
	$VUapi_allowed_functions =	$row[2];
	$VUuser_group =				$row[3];
	}
##### END SETTINGS LOOKUP #####
###########################################

$ingroup_choices = preg_replace("/\+/"," ",$ingroup_choices);
$query_string = preg_replace("/'|\"|\\\\|;/","",$query_string);
$stage = preg_replace("/\'|\"|\\\\|;/","",$stage);
$close_window_link=preg_replace("/[^-_0-9a-zA-Z]/","",$close_window_link);
$dnc_check=preg_replace("/[^-_0-9a-zA-Z]/","",$dnc_check);
$campaign_dnc_check=preg_replace("/[^-_0-9a-zA-Z]/","",$campaign_dnc_check);
$notification_date = preg_replace('/[^- \:0-9]/','',$notification_date);
$multi_dial_phones = preg_replace('/[^\,0-9]/','',$multi_dial_phones);
$md_check = preg_replace("/[^0-9a-zA-Z]/","",$md_check);
$tw_check = preg_replace("/[^0-9a-zA-Z]/","",$tw_check);

if ($non_latin < 1)
	{
	$user=preg_replace("/[^-_0-9a-zA-Z]/","",$user);
	$pass=preg_replace("/[^-\.\+\/\=_0-9a-zA-Z]/","",$pass);
	$agent_user=preg_replace("/[^-_0-9a-zA-Z]/","",$agent_user);
	$function = preg_replace("/[^-\_0-9a-zA-Z]/","",$function);
	$value = preg_replace("/[^-\|\_0-9a-zA-Z]/","",$value);
	$focus = preg_replace("/[^-\_0-9a-zA-Z]/","",$focus);
	$preview = preg_replace("/[^-\_0-9a-zA-Z]/","",$preview);
		$notes = preg_replace("/\+/"," ",$notes);
	$notes = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$notes);
	$search = preg_replace("/[^-\_0-9a-zA-Z]/","",$search);
	$group_alias = preg_replace("/[^0-9a-zA-Z]/","",$group_alias);
	$dial_prefix = preg_replace("/[^0-9a-zA-Z]/","",$dial_prefix);
	$source = preg_replace("/[^0-9a-zA-Z]/","",$source);
	$format = preg_replace("/[^0-9a-zA-Z]/","",$format);
	$vtiger_callback = preg_replace("/[^A-Z]/","",$vtiger_callback);
	$alt_dial = preg_replace("/[^0-9A-Z]/","",$alt_dial);
	$blended = preg_replace("/[^A-Z]/","",$blended);
	$ingroup_choices = preg_replace("/[^- \_0-9a-zA-Z]/","",$ingroup_choices);
	$set_as_default = preg_replace("/[^A-Z]/","",$set_as_default);
	$phone_code = preg_replace("/[^0-9X]/","",$phone_code);
	$phone_number = preg_replace("/[^\*#0-9]/","",$phone_number);
	$lead_id = preg_replace("/[^0-9]/","",$lead_id);
	$vendor_id = preg_replace('/;|#/','',$vendor_id);
		$vendor_id = preg_replace('/\+/',' ',$vendor_id);
	$vendor_lead_code = preg_replace('/;|#/','',$vendor_lead_code);
		$vendor_lead_code = preg_replace('/\+/',' ',$vendor_lead_code);
	$source_id = preg_replace('/;|#/','',$source_id);
		$source_id = preg_replace('/\+/',' ',$source_id);
	$gmt_offset_now = preg_replace('/[^-\_\.0-9]/','',$gmt_offset_now);
	$title = preg_replace('/[^- \'\_\.0-9a-zA-Z]/','',$title);
	$first_name = preg_replace('/[^- \'\+\_\.0-9a-zA-Z]/','',$first_name);
		$first_name = preg_replace('/\+/',' ',$first_name);
	$middle_initial = preg_replace('/[^0-9a-zA-Z]/','',$middle_initial);
	$last_name = preg_replace('/[^- \'\+\_\.0-9a-zA-Z]/','',$last_name);
		$last_name = preg_replace('/\+/',' ',$last_name);
	$address1 = preg_replace('/[^- \'\+\.\:\/\@\_0-9a-zA-Z]/','',$address1);
	$address2 = preg_replace('/[^- \'\+\.\:\/\@\_0-9a-zA-Z]/','',$address2);
	$address3 = preg_replace('/[^- \'\+\.\:\/\@\_0-9a-zA-Z]/','',$address3);
		$address1 = preg_replace('/\+/',' ',$address1);
		$address2 = preg_replace('/\+/',' ',$address2);
		$address3 = preg_replace('/\+/',' ',$address3);
	$city = preg_replace('/[^- \'\+\.\:\/\@\_0-9a-zA-Z]/','',$city);
		$city = preg_replace('/\+/',' ',$city);
	$state = preg_replace('/[^- 0-9a-zA-Z]/','',$state);
	$province = preg_replace('/[^- \'\+\.\_0-9a-zA-Z]/','',$province);
		$province = preg_replace('/\+/',' ',$province);
	$postal_code = preg_replace('/[^- \'\+0-9a-zA-Z]/','',$postal_code);
		$postal_code = preg_replace('/\+/',' ',$postal_code);
	$country_code = preg_replace('/[^A-Z]/','',$country_code);
	$gender = preg_replace('/[^A-Z]/','',$gender);
	$date_of_birth = preg_replace('/[^-0-9]/','',$date_of_birth);
	$alt_phone = preg_replace('/[^- \'\+\_\.0-9a-zA-Z]/','',$alt_phone);
		$alt_phone = preg_replace('/\+/',' ',$alt_phone);
	$email = preg_replace('/[^- \'\+\.\:\/\@\%\_0-9a-zA-Z]/','',$email);
		$email = preg_replace('/\+/',' ',$email);
	$security_phrase = preg_replace('/[^- \'\+\.\:\/\@\_0-9a-zA-Z]/','',$security_phrase);
		$security_phrase = preg_replace('/\+/',' ',$security_phrase);
	$comments = preg_replace('/;|#/','',$comments);
		$comments = preg_replace('/\+/',' ',$comments);
	$rank = preg_replace('/[^0-9]/','',$rank);
	$owner = preg_replace('/[^- \'\+\.\:\/\@\_0-9a-zA-Z]/','',$owner);
		$owner = preg_replace('/\+/',' ',$owner);
	$dial_override = preg_replace("/[^A-Z]/","",$dial_override);
	$consultative = preg_replace("/[^A-Z]/","",$consultative);
		$callback_datetime = preg_replace("/\+/"," ",$callback_datetime);
	$callback_datetime = preg_replace("/[^- \:\.\_0-9a-zA-Z]/","",$callback_datetime);
	$callback_type = preg_replace("/[^A-Z]/","",$callback_type);
		$callback_comments = preg_replace("/\+/"," ",$callback_comments);
	$callback_comments = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$callback_comments);
	$qm_dispo_code = preg_replace("/[^-\.\_0-9a-zA-Z]/","",$qm_dispo_code);
	$alt_user = preg_replace("/[^0-9a-zA-Z]/","",$alt_user);
	$postal_code = preg_replace("/[^- \.\_0-9a-zA-Z]/","",$postal_code);
	$agent_debug = preg_replace("/[^- \.\:\|\_0-9a-zA-Z]/","",$agent_debug);
	$status = preg_replace("/[^-\_0-9a-zA-Z]/","",$status);
	$dial_ingroup = preg_replace("/[^-\_0-9a-zA-Z]/","",$dial_ingroup);
	$cid_choice = preg_replace("/[^-\_0-9a-zA-Z]/","",$cid_choice);
	$outbound_cid = preg_replace("/[^-\_0-9a-zA-Z]/","",$outbound_cid);
	$recipient = preg_replace("/[^-\_0-9a-zA-Z]/","",$recipient);
	$recipient_type = preg_replace("/[^\_a-zA-Z]/","",$recipient_type);
	$notification_text = preg_replace('/;|#/','',$notification_text);
		$notification_text = preg_replace('/\+/',' ',$notification_text);
 	$notification_retry = preg_replace("/[^YN]/i", "", $notification_retry);
	$text_size = preg_replace("/[^0-9]/","",$text_size);
	$text_font = preg_replace("/[^\,\- a-zA-Z]/","",$text_font);
	$text_weight = preg_replace("/[^\,a-zA-Z]/","",$text_weight);
	$text_color = preg_replace("/[^0-9a-zA-Z]/","",$text_color);
	$show_confetti = preg_replace("/[^YN]/i", "", $show_confetti);
	$duration = preg_replace("/[^0-9]/","",$duration);
	$maxParticleCount = preg_replace("/[^0-9]/","",$maxParticleCount);
	$particleSpeed = preg_replace("/[^0-9]/","",$particleSpeed);
	}
else
	{
	$user=preg_replace("/[^-_0-9\p{L}]/u","",$user);
	$pass = preg_replace('/[^-\.\+\/\=_0-9\p{L}]/u','',$pass);
	$agent_user=preg_replace("/[^-_0-9\p{L}]/u","",$agent_user);
	$function = preg_replace("/[^-\_0-9\p{L}]/u","",$function);
	$value = preg_replace("/[^-\|\_0-9\p{L}]/u","",$value);
	$focus = preg_replace("/[^-\_0-9\p{L}]/u","",$focus);
	$preview = preg_replace("/[^-\_0-9\p{L}]/u","",$preview);
		$notes = preg_replace("/\+/"," ",$notes);
	$notes = preg_replace("/[^- \.\_0-9\p{L}]/u","",$notes);
	$search = preg_replace("/[^-\_0-9\p{L}]/u","",$search);
	$group_alias = preg_replace("/[^0-9\p{L}]/u","",$group_alias);
	$dial_prefix = preg_replace("/[^0-9\p{L}]/u","",$dial_prefix);
	$source = preg_replace("/[^0-9\p{L}]/u","",$source);
	$format = preg_replace("/[^0-9\p{L}]/u","",$format);
	$vtiger_callback = preg_replace("/[^A-Z]/","",$vtiger_callback);
	$alt_dial = preg_replace("/[^0-9A-Z]/","",$alt_dial);
	$blended = preg_replace("/[^A-Z]/","",$blended);
	$ingroup_choices = preg_replace("/[^- \_0-9\p{L}]/u","",$ingroup_choices);
	$set_as_default = preg_replace("/[^A-Z]/","",$set_as_default);
	$phone_code = preg_replace("/[^0-9X]/","",$phone_code);
	$phone_number = preg_replace("/[^\*#0-9]/","",$phone_number);
	$lead_id = preg_replace("/[^0-9]/","",$lead_id);
	$vendor_id = preg_replace('/;|#/','',$vendor_id);
		$vendor_id = preg_replace('/\+/',' ',$vendor_id);
	$vendor_lead_code = preg_replace('/;|#/','',$vendor_lead_code);
		$vendor_lead_code = preg_replace('/\+/',' ',$vendor_lead_code);
	$source_id = preg_replace('/;|#/','',$source_id);
		$source_id = preg_replace('/\+/',' ',$source_id);
	$gmt_offset_now = preg_replace('/[^-\_\.0-9]/','',$gmt_offset_now);
	$title = preg_replace('/[^- \'\_\.0-9\p{L}]/u','',$title);
	$first_name = preg_replace('/[^- \'\+\_\.0-9\p{L}]/u','',$first_name);
		$first_name = preg_replace('/\+/',' ',$first_name);
	$middle_initial = preg_replace('/[^0-9\p{L}]/u','',$middle_initial);
	$last_name = preg_replace('/[^- \'\+\_\.0-9\p{L}]/u','',$last_name);
		$last_name = preg_replace('/\+/',' ',$last_name);
	$address1 = preg_replace('/[^- \'\+\.\:\/\@\_0-9\p{L}]/u','',$address1);
	$address2 = preg_replace('/[^- \'\+\.\:\/\@\_0-9\p{L}]/u','',$address2);
	$address3 = preg_replace('/[^- \'\+\.\:\/\@\_0-9\p{L}]/u','',$address3);
		$address1 = preg_replace('/\+/',' ',$address1);
		$address2 = preg_replace('/\+/',' ',$address2);
		$address3 = preg_replace('/\+/',' ',$address3);
	$city = preg_replace('/[^- \'\+\.\:\/\@\_0-9\p{L}]/u','',$city);
		$city = preg_replace('/\+/',' ',$city);
	$state = preg_replace('/[^- 0-9\p{L}]/u','',$state);
	$province = preg_replace('/[^- \'\+\.\_0-9\p{L}]/u','',$province);
		$province = preg_replace('/\+/',' ',$province);
	$postal_code = preg_replace('/[^- \'\+0-9\p{L}]/u','',$postal_code);
		$postal_code = preg_replace('/\+/',' ',$postal_code);
	$country_code = preg_replace('/[^A-Z]/','',$country_code);
	$gender = preg_replace('/[^A-Z]/','',$gender);
	$date_of_birth = preg_replace('/[^-0-9]/','',$date_of_birth);
	$alt_phone = preg_replace('/[^- \'\+\_\.0-9\p{L}]/u','',$alt_phone);
		$alt_phone = preg_replace('/\+/',' ',$alt_phone);
	$email = preg_replace('/[^- \'\+\.\:\/\@\%\_0-9\p{L}]/u','',$email);
		$email = preg_replace('/\+/',' ',$email);
	$security_phrase = preg_replace('/[^- \'\+\.\:\/\@\_0-9\p{L}]/u','',$security_phrase);
		$security_phrase = preg_replace('/\+/',' ',$security_phrase);
	$comments = preg_replace('/;|#/','',$comments);
		$comments = preg_replace('/\+/',' ',$comments);
	$rank = preg_replace('/[^0-9]/','',$rank);
	$owner = preg_replace('/[^- \'\+\.\:\/\@\_0-9\p{L}]/u','',$owner);
		$owner = preg_replace('/\+/',' ',$owner);
	$dial_override = preg_replace("/[^A-Z]/","",$dial_override);
	$consultative = preg_replace("/[^A-Z]/","",$consultative);
		$callback_datetime = preg_replace("/\+/"," ",$callback_datetime);
	$callback_datetime = preg_replace("/[^- \:\.\_0-9\p{L}]/u","",$callback_datetime);
	$callback_type = preg_replace("/[^A-Z]/","",$callback_type);
		$callback_comments = preg_replace("/\+/"," ",$callback_comments);
	$callback_comments = preg_replace("/[^- \.\_0-9\p{L}]/u","",$callback_comments);
	$qm_dispo_code = preg_replace("/[^-\.\_0-9\p{L}]/u","",$qm_dispo_code);
	$alt_user = preg_replace("/[^0-9\p{L}]/u","",$alt_user);
	$postal_code = preg_replace("/[^- \.\_0-9\p{L}]/u","",$postal_code);
	$agent_debug = preg_replace("/[^- \.\:\|\_0-9\p{L}]/u","",$agent_debug);
	$status = preg_replace("/[^-\_0-9\p{L}]/u","",$status);
	$dial_ingroup = preg_replace("/[^-\_0-9\p{L}]/u","",$dial_ingroup);
	$cid_choice = preg_replace("/[^-\_0-9\p{L}]/u","",$cid_choice);
	$outbound_cid = preg_replace("/[^-\_0-9\p{L}]/u","",$outbound_cid);
	$recipient = preg_replace("/[^-\_0-9\p{L}]/u","",$recipient);
	$recipient_type = preg_replace("/[^\_\p{L}]/u","",$recipient_type);
	$notification_text = preg_replace('/;|#/','',$notification_text);
		$notification_text = preg_replace('/\+/',' ',$notification_text);
 	$notification_retry = preg_replace("/[^YN]/i", "", $notification_retry);
	$text_size = preg_replace("/[^0-9]/","",$text_size);
	$text_font = preg_replace("/[^\,\- \p{L}]/u","",$text_font);
	$text_weight = preg_replace("/[^\,\p{L}]/u","",$text_weight);
	$text_color = preg_replace("/[^0-9\p{L}]/u","",$text_color);
	$show_confetti = preg_replace("/[^YN]/i", "", $show_confetti);
	$duration = preg_replace("/[^0-9]/","",$duration);
	$maxParticleCount = preg_replace("/[^0-9]/","",$maxParticleCount);
	$particleSpeed = preg_replace("/[^0-9]/","",$particleSpeed);
	}

### date and fixed variables
$epoch = date("U");
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$MT[0]='';
$api_script = 'agent';
$api_logging = 1;
if ($consultative != 'YES') {$consultative='NO';}


################################################################################
### BEGIN - version - show version and date information for the API
################################################################################
if ($function == 'version')
	{
	$data = _QXZ("VERSION:")." $version|"._QXZ("BUILD:")." $build|"._QXZ("DATE:")." $NOW_TIME|"._QXZ("EPOCH:")." $StarTtime";
	$result = _QXZ("SUCCESS");
	echo "$data\n";
	api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
	exit;
	}
################################################################################
### END - version
################################################################################





################################################################################
### BEGIN - coffee/teapot 418 - reject coffee requests
################################################################################
if ( ($function == 'coffee') or ($function == 'start_coffee') or ($function == 'make_coffee') or ($function == 'brew_coffee') )
	{
	$data = _QXZ("Coffee").": $function|Error 418 I'm a teapot";
	$result = _QXZ("ERROR");
	Header("HTTP/1.0 418 I'm a teapot");
	echo "$data";
	api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
	exit;
	}
################################################################################
### END - coffee/teapot
################################################################################





################################################################################
### BEGIN - user validation section (most functions run through this first)
################################################################################

if ($ACTION == 'LogiNCamPaigns')
	{
	$skip_user_validation=1;
	}
else
	{
	if(strlen($source)<2)
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("Invalid Source");
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		$auth=0;
		$auth_message = user_authorization($user,$pass,'',0,0,0,1,'api');
		if ($auth_message == 'GOOD')
			{$auth=1;}

		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1';";
		if ($DB) {echo "|$stmt|\n";}
		if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$auth_api=$row[0];

		if( (strlen($user)<2) or (strlen($pass)<2) or ($auth==0) or ($auth_api==0))
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("Invalid Username/Password");
			echo "$result: $result_reason: |$user|$pass|$auth|$auth_api|$auth_message|\n";
			$data = "$user|$pass|$auth|$auth_api|$auth_message|";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT count(*) from system_settings where vdc_agent_api_active='1';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$SNauth=$row[0];
			if($SNauth==0)
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("System API NOT ACTIVE");
				echo "$result: $result_reason\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				# do nothing for now
				}
			}
		}

	if ( ($VUapi_list_restrict > 0) and ( ($function == 'feature_not_needed') or ($function == 'feature_not_needed2') ) )
		{
		$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$VUuser_group';";
		if ($DB>0) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$ss_conf_ct = mysqli_num_rows($rslt);
		if ($ss_conf_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$LOGallowed_campaigns =			$row[0];
			$LOGallowed_campaignsSQL='';
			$whereLOGallowed_campaignsSQL='';
			if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
				{
				$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
				$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
				$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
				$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
				}
			$stmt="SELECT list_id from vicidial_lists $whereLOGadmin_viewable_groupsSQL order by list_id;";
			if ($DB>0) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$lists_to_print = mysqli_num_rows($rslt);
			$i=0;
			$allowed_lists=' ';
			while ($i < $lists_to_print)
				{
				$row=mysqli_fetch_row($rslt);
				$allowed_lists .=	"$row[0] ";
				$i++;
				}
			if ($DB>0) {echo "Allowed lists:|$allowed_lists|\n";}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("user_group DOES NOT EXIST");
			echo "$result: $result_reason - $value|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		}
	}

if ($format=='debug')
	{
	$DB=1;
	echo "<html>\n";
	echo "<head>\n";
	echo "<!-- VERSION: $version     BUILD: $build    USER: $user\n";
	echo "<title>"._QXZ("VICIDiaL Agent API");
	echo "</title>\n";
	echo "</head>\n";
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	}
################################################################################
### END - user validation section
################################################################################





################################################################################
### BEGIN - webserver - show webserver information
################################################################################
if ($function == 'webserver')
	{
	if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
		echo "$result: $result_reason - $user|$function|$VUuser_group\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	exec('ps aux | grep httpd', $output);
	$processes = count($output);
	$load = sys_getloadavg();

	$data="Webserver Data:\n";
	$data .= "set.timezone: " . date('e') . "\n";
	$data .= "abbr.timezone: " . date('T') . "\n";
	$data .= "dst.timezone: " . date('I') . "\n";
	$data .= "uname: " . php_uname('e') . "\n";
	$data .= _QXZ("host name: ") . php_uname('n') . "\n";
	$data .= _QXZ("server name: ") . $_SERVER['SERVER_NAME'] . "\n";
	$data .= _QXZ("php version: ") . phpversion() . "\n";
	$data .= _QXZ("apache version: ") . apache_get_version() . "\n";
	$data .= _QXZ("apache processes: ") . $processes . "\n";
	$data .= _QXZ("system load average: ") . $load[0] . "\n";
	$data .= _QXZ("disk free space: ") . disk_free_space('/') . "\n";

	if (ini_get('date.timezone')) 
		{
		$data .= "date.timezone: " . ini_get('date.timezone') . "\n";
		$data .= _QXZ("maximum execution time: ") . ini_get('max_execution_time') . "\n";
		$data .= _QXZ("maximum input time: ") . ini_get('max_input_time') . "\n";
		$data .= _QXZ("memory limit: ") . ini_get('memory_limit') . "\n";
		$data .= _QXZ("post maximum size: ") . ini_get('post_max_size') . "\n";
		$data .= _QXZ("upload maximum filesize: ") . ini_get('upload_max_filesize') . "\n";
		$data .= _QXZ("default socket timeout: ") . ini_get('default_socket_timeout') . "\n";
		}
	else {$data .= _QXZ("ini_get not allowed: ")."\n";}

	$result = _QXZ("SUCCESS");
	echo "<PRE>$data</PRE>\n";
	$data = preg_replace("/\n/",'|',$data);
	api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
	exit;
	}
################################################################################
### END - webserver
################################################################################





################################################################################
### BEGIN - external_hangup - hang up the active agent call
################################################################################
if ($function == 'external_hangup')
	{
	if ( (strlen($value)<1) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("external_hangup not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt="UPDATE vicidial_live_agents set external_hangup='$value' where user='$agent_user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$result = _QXZ("SUCCESS");
			$result_reason = _QXZ("external_hangup function set");
			echo "$result: $result_reason - $value|$agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - external_hangup
################################################################################





################################################################################
### BEGIN - external_status - set the dispo code or status for a call and move on
################################################################################
if ($function == 'external_status')
	{
	if ( (strlen($value)<1) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("external_status not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$CB_status_found=0;
			if (strlen($callback_datetime) > 12)
				{
				$callback_status = $value;

				$stmt = "select count(*) from vicidial_statuses where status='$callback_status' and scheduled_callback='Y';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] > 0)
					{$CB_status_found++;}
				else
					{
					$stmt = "select campaign_id from vicidial_live_agents where user='$agent_user';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$campaign_id = $row[0];

					$stmt = "select count(*) from vicidial_campaign_statuses where campaign_id='$campaign_id' and status='$callback_status' and scheduled_callback='Y';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					if ($row[0] > 0)
						{$CB_status_found++;}
					}

				if ($CB_status_found > 0)
					{
					if (strlen($callback_type) < 4)
						{$callback_type='ANYONE';}
					while (strlen($callback_comments) > 200) {$callback_comments = preg_replace("/.$/",'',$callback_comments);}
					$value = "$callback_status!$callback_datetime!$callback_type!$callback_comments!";
					}
				}
			if (strlen($qm_dispo_code) > 0)
				{
				if ($CB_status_found < 1)
					{$value = "$value!!!!$qm_dispo_code";}
				else
					{$value = "$value$qm_dispo_code";}
				}

			$stmt="UPDATE vicidial_live_agents set external_status='$value' where user='$agent_user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$result = _QXZ("SUCCESS");
			$result_reason = _QXZ("external_status function set");
			echo "$result: $result_reason - $value|$agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - external_status
################################################################################





################################################################################
### BEGIN - external_pause - pause or resume the agent
################################################################################
if ($function == 'external_pause')
	{
	if ( (strlen($value)<1) or ( (strlen($agent_user)<1) and (strlen($alt_user)<1) ) or (!preg_match("/PAUSE|RESUME/",$value)) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("external_pause not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			if (preg_match("/RESUME/",$value))
				{
				$stmt = "select count(*) from vicidial_live_agents where user='$agent_user' and status IN('READY','QUEUE','INCALL','CLOSER');";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] > 0)
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("external_pause agent is not paused");
					echo "$result: $result_reason - $value|$agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			$stmt="UPDATE vicidial_live_agents set external_pause='$value!$epoch' where user='$agent_user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$result = _QXZ("SUCCESS");
			$result_reason = _QXZ("external_pause function set");
			echo "$result: $result_reason - $value|$epoch|$agent_user\n";
			$data = "$epoch";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - external_pause
################################################################################





################################################################################
### BEGIN - logout - log the agent out of the system
################################################################################
if ($function == 'logout')
	{
	if ( (strlen($value)<1) or ( (strlen($agent_user)<1) and (strlen($alt_user)<1) ) or (!preg_match("/LOGOUT/",$value)) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("logout not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt="UPDATE vicidial_live_agents set external_pause='$value' where user='$agent_user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$result = _QXZ("SUCCESS");
			$result_reason = _QXZ("logout function set");
			echo "$result: $result_reason - $value|$epoch|$agent_user\n";
			$data = "$epoch";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - logout
################################################################################






################################################################################
### BEGIN - recording - send a start or stop recording signal to agent screen
################################################################################
if ($function == 'recording')
	{
	if ( ( (!preg_match("/START/",$value)) and (!preg_match("/STOP/",$value)) and (!preg_match("/STATUS/",$value)) ) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("recording not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select external_recording,server_ip,conf_exten,status from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$recording_id =		$row[0];
			$AGENTserver_ip =	$row[1];
			$AGENTconf_exten =	$row[2];
			$AGENTstatus =		$row[3];

			if ($value=='STATUS')
				{
				if ( ($recording_id!='START') and ($recording_id!='STOP') and ($recording_id > 0) )
					{
					$RECfilename =		'';
					$RECserver_ip =		'';
					$RECstart_time =	'';
					$stmt = "SELECT filename,server_ip,start_time FROM recording_log where recording_id='$recording_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$rl_ct = mysqli_num_rows($rslt);
					if ($rl_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$RECfilename =		$row[0];
						$RECserver_ip =		$row[1];
						$RECstart_time =	$row[2];
						}

					$result = _QXZ("NOTICE");
					$result_reason = _QXZ("recording active");
					echo "$result: $result_reason - $agent_user|$recording_id|$RECfilename|$RECserver_ip|$RECstart_time|$AGENTserver_ip|$AGENTconf_exten|$AGENTstatus\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = _QXZ("NOTICE");
					$result_reason = _QXZ("not recording");
					echo "$result: $result_reason - $agent_user|||||$AGENTserver_ip|$AGENTconf_exten|$AGENTstatus\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				if ( (preg_match("/STOP/",$value)) and ( ($recording_id=='STOP') or ($recording_id < 1) ) )
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("stop recording error");
					echo "$result: $result_reason - $agent_user|$recording_id||||$AGENTserver_ip|$AGENTconf_exten|$AGENTstatus\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

					exit;
					}
				if ( (strlen($stage)>0) and (preg_match("/START/",$value)) )
					{
					while (strlen($stage)>14) {$stage = preg_replace("/.$/",'',$stage);}
					$value = "$value$stage";
					}
				$stmt="UPDATE vicidial_live_agents set external_recording='$value' where user='$agent_user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$result = _QXZ("SUCCESS");
				$result_reason = _QXZ("recording function sent");
				echo "$result: $result_reason - $agent_user|$value||||$AGENTserver_ip|$AGENTconf_exten|$AGENTstatus\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - recording
################################################################################






################################################################################
### BEGIN - webphone_url - display or launch the webphone url for the current agent's session
################################################################################
if ($function == 'webphone_url')
	{
	if ( ( ($value!='DISPLAY') and ($value!='LAUNCH') ) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("webphone_url not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select webphone_url from vicidial_session_data where user='$agent_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rl_ct = mysqli_num_rows($rslt);
			if ($rl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$webphone_url =		$row[0];

				if (strlen($webphone_url) > 5)
					{
					if ($value=='DISPLAY')
						{
						$result = _QXZ("NOTICE");
						$result_reason = _QXZ("webphone_url active and displayed");
						echo "$webphone_url";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = _QXZ("NOTICE");
						$result_reason = _QXZ("webphone_url active and launched");
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						echo"<META HTTP-EQUIV=Refresh CONTENT=\"0; URL=$webphone_url\">\n";
						echo"</HEAD>\n";
						echo"<BODY BGCOLOR=#FFFFFF marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
						echo"<a href=\"$webphone_url\">"._QXZ("click here to continue. . .")."</a>\n";
						echo"</BODY>\n";
						}
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("webphone_url error - webphone url is empty");
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("webphone_url error - no session data");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - webphone_url
################################################################################





################################################################################
### BEGIN - call_agent - send a call to connect the agent to their session
################################################################################
if ($function == 'call_agent')
	{
	if ( ($value!='CALL') or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("call_agent not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*), conf_exten from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$conf_exten=$row[1];
			$stmt = "select agent_login_call from vicidial_session_data where user='$agent_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rl_ct = mysqli_num_rows($rslt);
			if ($rl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$agent_login_call =		$row[0];

				if (strlen($agent_login_call) > 5)
					{
					$call_agent_conference = preg_replace("/(.+?Exten: )\d{7}(\|Priority.+)/", "$1 $conf_exten$2",$agent_login_call);
					$call_agent_string = preg_replace("/\|/","','",$call_agent_conference);
					$stmt="INSERT INTO vicidial_manager values('$call_agent_string');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$result = _QXZ("SUCCESS");
					$result_reason = _QXZ("call_agent function sent");
					echo "$result: $result_reason - $agent_user\n";
					$data = "$epoch";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("call_agent error - entry is empty");
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("call_agent error - no session data");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - call_agent
################################################################################





################################################################################
### BEGIN - audio_playback - play/pause/resume/stop/restart audio in agent session
################################################################################
if ($function == 'audio_playback')
	{
	if ( ( (strlen($value)<1) and ($stage=='PLAY') ) or (strlen($stage)<4) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("audio_playback not valid");
		$data = "$stage";
		echo "$result: $result_reason - $value|$data|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				$data = "$stage|$alt_user";
				echo "$result: $result_reason - $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select conf_exten,server_ip from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rl_ct = mysqli_num_rows($rslt);
			if ($rl_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$VLAconf_exten =	$row[0];
				$VLAserver_ip =		$row[1];

				if ( (strlen($VLAconf_exten) > 5) and (strlen($VLAserver_ip) > 5) )
					{
					$valueCIDfull = "\"$value\" <473782158521111>";

					$stmt = "select ext_context from servers where server_ip='$VLAserver_ip';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$ext_context =		$row[0];

					$stmt = "select channel from live_channels where extension='$VLAconf_exten' and server_ip='$VLAserver_ip' and channel LIKE \"IAX2/ASTplay%\";";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$rl_ct = mysqli_num_rows($rslt);

					if ($stage == 'PLAY')
						{
						if ($rl_ct > 0)
							{
							if ($dial_override == 'Y')
								{
								$row=mysqli_fetch_row($rslt);
								$VLAchannel =	$row[0];
								$VLAchannel_inc =	$VLAchannel;
								$VLAchannel_inc = preg_replace("/IAX2\/ASTplay-/",'',$VLAchannel_inc);

								$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$VLAserver_ip','','Hangup','PLAYHU$ENTRYdate','Channel: $VLAchannel','','','','','','','','','');";
								if ($format=='debug') {echo "\n<!-- $stmtX -->";}
								$rslt=mysql_to_mysqli($stmtX, $link);
								$result = _QXZ("NOTICE");
								$data = "$stage|$dial_override";
								$result_reason = _QXZ("audio_playback previous playback stopped");
								echo "$result: $result_reason - $data|$agent_user\n";
								$data = "$epoch";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

								$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$VLAserver_ip','','Originate','$value','Channel: Local\/47378216$VLAconf_exten@$ext_context','Context: $ext_context','Exten: 473782158521111','Priority: 1','CallerID: $valueCIDfull','','','','','');";
								}
							else
								{
								$result = _QXZ("ERROR");
								$data = "$VLAconf_exten|$VLAserver_ip|$stage|$dial_override";
								$result_reason = _QXZ("audio_playback error - audio already playing in agent session");
								echo "$result: $result_reason - $data|$agent_user\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						else
							{
							$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$VLAserver_ip','','Originate','$value','Channel: Local\/47378216$VLAconf_exten@$ext_context','Context: $ext_context','Exten: 473782158521111','Priority: 1','CallerID: $valueCIDfull','','','','','');";
							}
						}
					else
						{
						if ($rl_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$VLAchannel =	$row[0];
							$VLAchannel_inc =	$VLAchannel;
							$VLAchannel_inc = preg_replace("/IAX2\/ASTplay-/",'',$VLAchannel_inc);

							if ($stage == 'STOP')
								{
								$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$VLAserver_ip','','Hangup','PLAYHU$ENTRYdate','Channel: $VLAchannel','','','','','','','','','');";
								}
							if ($stage == 'RESTART')
								{
								$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$VLAserver_ip','','Originate','PLAYRS$ENTRYdate','Channel: Local\/473782148521111$VLAchannel_inc@$ext_context','Context: $ext_context','Exten: 473782138521111','Priority: 1','CallerID: 4','','','','','');";
								}
							if ( ($stage == 'PAUSE') or ($stage == 'RESUME') )
								{
								$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$VLAserver_ip','','Originate','PLAYPA$ENTRYdate','Channel: Local\/473782148521111$VLAchannel_inc@$ext_context','Context: $ext_context','Exten: 473782138521111','Priority: 1','CallerID: 3','','','','','');";
								}
							}
						else
							{
							$result = _QXZ("ERROR");
							$data = "$VLAconf_exten|$VLAserver_ip|$stage";
							$result_reason = _QXZ("audio_playback error - no audio playing in agent session");
							echo "$result: $result_reason - $data|$agent_user\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					if ($format=='debug') {echo "\n<!-- $stmtX -->";}
					$rslt=mysql_to_mysqli($stmtX, $link);
					$result = _QXZ("SUCCESS");
					$data = "$value|$stage";
					$result_reason = _QXZ("audio_playback function sent");
					echo "$result: $result_reason - $data|$agent_user\n";
					$data = "$epoch";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = _QXZ("ERROR");
					$data = "$stage";
					$result_reason = _QXZ("audio_playback error - entry is empty");
					echo "$result: $result_reason - $data|$agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$data = "$stage";
				$result_reason = _QXZ("audio_playback error - no session data");
				echo "$result: $result_reason - $data|$agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$data = "$stage";
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $data|$agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}

	if ($source == 'soundboard')
		{
		### log any events that are sent from agent sub-screens
		if ( (strlen($agent_debug) > 1) and ($SSagent_debug_logging > 0) )
			{
			$endMS = microtime();
			$startMSary = explode(" ",$startMS);
			$endMSary = explode(" ",$endMS);
			$runS = ($endMSary[0] - $startMSary[0]);
			$runM = ($endMSary[1] - $startMSary[1]);
			$TOTALrun = ($runS + $runM);
			$cd=0;
			$agent_debug = preg_replace("/\|$/",'',$agent_debug);
			$debug_details = explode('|',$agent_debug);
			$debug_details_ct = count($debug_details);
			while($cd < $debug_details_ct)
				{
				$debug_data = explode('-----',$debug_details[$cd]);
				$debug_time = $debug_data[0];
				$debug_function_data = explode('---',$debug_data[1]);
				$debug_function = $debug_function_data[0];
				$debug_options = $debug_function_data[1];

				$stmtA="INSERT INTO vicidial_ajax_log set user='$agent_user',start_time='$debug_time',db_time=NOW(),run_time='$TOTALrun',php_script='api.php',action='$debug_function',stage='$cd|$debug_options',last_sql='';";
				$rslt=mysql_to_mysqli($stmtA, $link);

				$cd++;
				}
			}
		}
	}
################################################################################
### END - audio_playback
################################################################################





################################################################################
### BEGIN - external_dial - place a manual dial phone call
################################################################################
if ($function == 'external_dial')
	{
	if ($value == 'MANUALNEXT')
		{$value = preg_replace("/[^0-9a-zA-Z]/","",$value);}
	else
		{$value = preg_replace("/[^0-9]/","",$value);}

	if ( ( (strlen($value)<2) and (strlen($lead_id)<1) ) or ( (strlen($agent_user)<2) and (strlen($alt_user)<2) ) or (strlen($search)<2) or (strlen($preview)<2) or (strlen($focus)<2) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("external_dial not valid");
		$data = "$phone_code|$search|$preview|$focus|$lead_id";
		echo "$result: $result_reason - $value|$data|$agent_user|$alt_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "SELECT campaign_id FROM vicidial_live_agents where user='$agent_user';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vlac_conf_ct = mysqli_num_rows($rslt);
			if ($vlac_conf_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$vac_campaign_id =	$row[0];
				}
			$stmt = "SELECT api_manual_dial FROM vicidial_campaigns where campaign_id='$vac_campaign_id';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vcc_conf_ct = mysqli_num_rows($rslt);
			if ($vcc_conf_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$api_manual_dial =	$row[0];
				}

			if ($api_manual_dial=='STANDARD')
				{
				$stmt = "select count(*) from vicidial_live_agents where user='$agent_user' and status='PAUSED' and lead_id < 1;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_ready = $row[0];
				}
			else
				{
				$agent_ready=1;
				}
			if (strlen($dial_ingroup)>0)
				{
				$stmt = "select count(*) from vicidial_inbound_groups where group_id='$dial_ingroup';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] < 1)
					{
					$result = _QXZ("NOTICE");
					$result_reason = _QXZ("defined dial_ingroup not found");
					echo "$result: $result_reason - $dial_ingroup\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					$dial_ingroup='';
					}
				}
			if ( ($agent_ready > 0) or (strlen($dial_ingroup)>0) )
				{
				$stmt = "select count(*) from vicidial_users where user='$agent_user' and agentcall_manual='1';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] > 0)
					{
					if (strlen($group_alias)>1)
						{
						$stmt = "select caller_id_number from groups_alias where group_alias_id='$group_alias';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$VDIG_cidnum_ct = mysqli_num_rows($rslt);
						if ($VDIG_cidnum_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$caller_id_number	= $row[0];
							if ($caller_id_number < 4)
								{
								$result = _QXZ("ERROR");
								$result_reason = _QXZ("caller_id_number from group_alias is not valid");
								$data = "$group_alias|$caller_id_number";
								echo "$result: $result_reason - $agent_user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						else
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("group_alias is not valid");
							$data = "$group_alias";
							echo "$result: $result_reason - $agent_user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					if (strlen($outbound_cid)>1)
						{
						if ($SSoutbound_cid_any != 'API_ONLY')
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("outbound_cid is not allowed on this system");
							$data = "$outbound_cid|$SSoutbound_cid_any";
							echo "$result: $result_reason - $agent_user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							$caller_id_number = $outbound_cid;
							$group_alias='-FORCE-';
							}
						}

					####### Begin Vtiger CallBack Launching #######
					$vtiger_callback_id='';
					if ( (preg_match("/YES/i",$vtiger_callback)) and (preg_match("/^99/",$value)) )
						{
						$value = preg_replace("/^99/",'',$value);
						$value = ($value + 0);

						$stmt = "SELECT enable_vtiger_integration,vtiger_server_ip,vtiger_dbname,vtiger_login,vtiger_pass,vtiger_url FROM system_settings;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$ss_conf_ct = mysqli_num_rows($rslt);
						if ($ss_conf_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$enable_vtiger_integration =	$row[0];
							$vtiger_server_ip	=			$row[1];
							$vtiger_dbname =				$row[2];
							$vtiger_login =					$row[3];
							$vtiger_pass =					$row[4];
							$vtiger_url =					$row[5];
							}

						if ($enable_vtiger_integration > 0)
							{
							$stmt = "SELECT campaign_id FROM vicidial_live_agents where user='$agent_user';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$vtc_camp_ct = mysqli_num_rows($rslt);
							if ($vtc_camp_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$campaign_id =		$row[0];
								}
							$stmt = "SELECT vtiger_search_category,vtiger_create_call_record,vtiger_create_lead_record,vtiger_search_dead,vtiger_status_call FROM vicidial_campaigns where campaign_id='$campaign_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$vtc_conf_ct = mysqli_num_rows($rslt);
							if ($vtc_conf_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$vtiger_search_category =		$row[0];
								$vtiger_create_call_record =	$row[1];
								$vtiger_create_lead_record =	$row[2];
								$vtiger_search_dead =			$row[3];
								$vtiger_status_call =			$row[4];
								}

							### connect to your vtiger database
							$linkV=mysqli_connect("$vtiger_server_ip", "$vtiger_login","$vtiger_pass");
							if (!$linkV) {die(_QXZ("Could not connect: ")."$vtiger_server_ip|$vtiger_dbname|$vtiger_login|$vtiger_pass" . mysqli_connect_error());}
							mysqli_select_db($linkV, "$vtiger_dbname");

							# make sure the ID is present in Vtiger database as an account
							$stmt="SELECT count(*) from vtiger_seactivityrel where activityid='$value';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $linkV);
							$vt_act_ct = mysqli_num_rows($rslt);
							if ($vt_act_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$activity_check = $row[0];
								}
							if ($activity_check > 0)
								{
								$stmt="SELECT crmid from vtiger_seactivityrel where activityid='$value';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $linkV);
								$vt_actsel_ct = mysqli_num_rows($rslt);
								if ($vt_actsel_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$vendor_id = $row[0];
									}
								if (strlen($vendor_id) > 0)
									{
									$stmt="SELECT phone from vtiger_account where accountid='$vendor_id';";
									if ($DB) {echo "$stmt\n";}
									$rslt=mysql_to_mysqli($stmt, $linkV);
									$vt_acct_ct = mysqli_num_rows($rslt);
									if ($vt_acct_ct > 0)
										{
										$row=mysqli_fetch_row($rslt);
										$vtiger_callback_id="$value";
										$value = $row[0];
										}
									}
								}
							else
								{
								$result = _QXZ("ERROR");
								$result_reason = _QXZ("vtiger callback activity does not exist in vtiger system");
								echo "$result: $result_reason - $value\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						}
					####### End Vtiger CallBack Launching #######

					### If lead_id is populated, check for it and adjust variables accordingly
					if (strlen($lead_id) > 0)
						{
						$phone_search = $value;
						$value='';
						$phone_code='';
						if ($alt_dial=='SEARCH')
							{
							$alt_dial='';
							$stmt = "SELECT phone_number,alt_phone,address3 FROM vicidial_list where lead_id=$lead_id;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$paa_ct = mysqli_num_rows($rslt);
							if ($paa_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$P_main =	$row[0];
								$P_alt =	$row[1];
								$P_adr3 =	$row[2];

								if ($P_adr3 == "$phone_search")
									{$alt_dial='ADDR3';}
								if ($P_alt == "$phone_search")
									{$alt_dial='ALT';}
								if ($P_main == "$phone_search")
									{$alt_dial='MAIN';}
								if ($alt_dial=='')
									{
									$result = _QXZ("ERROR");
									$result_reason = _QXZ("phone number lead_id search not found");
									$data = "$value|$lead_id|$alt_dial";
									echo "$result: $result_reason - $agent_user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							}
						if ($alt_dial=='ALT')
							{$stmtPF = "select alt_phone,phone_code from vicidial_list where lead_id=$lead_id;";}
						if ($alt_dial=='ADDR3')
							{$stmtPF = "select address3,phone_code from vicidial_list where lead_id=$lead_id;";}
						if (strlen($stmtPF)<20)
							{$stmtPF = "select phone_number,phone_code from vicidial_list where lead_id=$lead_id;";}
						if ($DB) {echo "$stmtPF\n";}
						$rslt=mysql_to_mysqli($stmtPF, $link);
						$VL_lead_id_ct = mysqli_num_rows($rslt);
						if ($VL_lead_id_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$value	=		$row[0];
							$phone_code	=	$row[1];
							$value = preg_replace("/[^0-9]/","",$value);
							if (strlen($value)<2)
								{
								$result = _QXZ("ERROR");
								$result_reason = _QXZ("phone number is not valid");
								$data = "$value|$lead_id|$alt_dial";
								echo "$result: $result_reason - $agent_user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						else
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("lead_id is not valid");
							$data = "$lead_id";
							echo "$result: $result_reason - $agent_user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}

					$success=0;
					### If no errors, run the update to place the call ###
					if ($api_manual_dial=='STANDARD')
						{
						$stmt="UPDATE vicidial_live_agents set external_dial='$value!$phone_code!$search!$preview!$focus!$vendor_id!$epoch!$dial_prefix!$group_alias!$caller_id_number!$vtiger_callback_id!$lead_id!$alt_dial!$dial_ingroup' where user='$agent_user';";
						if ($DB) {echo "$stmt\n";}
						$success=1;
						}
					else
						{
						$stmt = "select count(*) from vicidial_manual_dial_queue where user='$agent_user' and phone_number='$value';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						if ($row[0] < 1)
							{
							$stmt="INSERT INTO vicidial_manual_dial_queue set user='$agent_user',phone_number='$value',entry_time=NOW(),status='READY',external_dial='$value!$phone_code!$search!$preview!$focus!$vendor_id!$epoch!$dial_prefix!$group_alias!$caller_id_number!$vtiger_callback_id!$lead_id!$alt_dial!$dial_ingroup';";
							$success=1;
							}
						else
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("phone_number is already in this agents manual dial queue");
							echo "$result: $result_reason - $agent_user|$value\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					if ($success > 0)
						{
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$result = _QXZ("SUCCESS");
						$result_reason = _QXZ("external_dial function set");
						$data = "$phone_code|$search|$preview|$focus|$vendor_id|$epoch|$dial_prefix|$group_alias|$caller_id_number|$alt_dial";
						echo "$result: $result_reason - $value|$agent_user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("agent_user is not allowed to place manual dial calls");
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user is not paused");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - external_dial
################################################################################





################################################################################
### BEGIN - preview_dial_action - sends a SKIP, DIALONLY, ALTDIAL, ADR3DIAL or FINISH when a lead is being previewed or manual alt dial
################################################################################
if ($function == 'preview_dial_action')
	{
	$value = preg_replace("/[^A-Z0-9]/","",$value);

	if ( (strlen($value)<4) or ( (strlen($agent_user)<2) and (strlen($alt_user)<2) ) or ( ($value != 'SKIP') and ($value != 'DIALONLY') and ($value != 'ALTDIAL') and ($value != 'ADR3DIAL') and ($value != 'FINISH') ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("preview_dial_action not valid");
		$data = "";
		echo "$result: $result_reason - $value|$data|$agent_user|$alt_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "SELECT campaign_id,status FROM vicidial_live_agents where user='$agent_user';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vlac_conf_ct = mysqli_num_rows($rslt);
			if ($vlac_conf_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$vac_campaign_id =	$row[0];
				$vac_status =		$row[1];
				}
			$stmt = "SELECT manual_preview_dial,alt_number_dialing FROM vicidial_campaigns where campaign_id='$vac_campaign_id';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vcc_conf_ct = mysqli_num_rows($rslt);
			if ($vcc_conf_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$manual_preview_dial =	$row[0];
				$alt_number_dialing =	$row[1];
				}
			if ($manual_preview_dial == 'DISABLED')
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("preview dialing not allowed on this campaign");
				$data = "$vac_campaign_id|$manual_preview_dial";
				echo "$result: $result_reason - $agent_user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			if ( ($manual_preview_dial == 'PREVIEW_ONLY') and ($value == 'SKIP') )
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("preview dial skipping not allowed on this campaign");
				$data = "$vac_campaign_id|$manual_preview_dial";
				echo "$result: $result_reason - $agent_user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			if ( ($alt_number_dialing == 'N') and ( ($value == 'ALTDIAL') or ($value == 'ADR3DIAL') or ($value == 'FINISH') ) )
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("alt number dialing not allowed on this campaign");
				$data = "$vac_campaign_id|$alt_number_dialing";
				echo "$result: $result_reason - $agent_user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}

			$stmt = "select count(*) from vicidial_live_agents where user='$agent_user' and status='PAUSED';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$agent_ready = $row[0];

			if ($agent_ready > 0)
				{
				$stmt = "select count(*) from vicidial_users where user='$agent_user' and agentcall_manual='1';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] > 0)
					{
					$stmt="UPDATE vicidial_live_agents set external_dial='$value' where user='$agent_user';";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);

					if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$result = _QXZ("SUCCESS");
					$result_reason = _QXZ("preview_dial_action function set");
					$data = "$value";
					echo "$result: $result_reason - $value|$agent_user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("agent_user is not allowed to place manual dial calls");
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user is not paused");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - preview_dial_action
################################################################################





################################################################################
### BEGIN - external_add_lead - add lead in manual dial list of the campaign for logged-in agent
################################################################################
if ($function == 'external_add_lead')
	{
	if ( (strlen($value) < 1) and (strlen($phone_number) > 1) )
		{$value = $phone_number;}
	if ( ( (strlen($agent_user)<2) and (strlen($alt_user)<2) ) or (strlen($phone_code)<1) or (strlen($value)<2) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("external_add_lead not valid");
		$data = "$value|$phone_code";
		echo "$result: $result_reason - $data|$agent_user|$alt_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($vendor_id) > 0 )
			{
			$vendor_lead_code = $vendor_id;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select c.campaign_id,c.manual_dial_list_id from vicidial_campaigns c,vicidial_live_agents a where a.user='$agent_user' and a.campaign_id=c.campaign_id;";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$nrow=mysqli_num_rows($rslt);
			if ($nrow > 0)
				{
				$list_id =		$row[1];
				$campaign_id =	$row[0];

				# DNC Check
				if ($dnc_check == 'YES' or $dnc_check=='Y')
					{
					$stmt="SELECT count(*) from vicidial_dnc where phone_number='$value';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$dnc_found=$row[0];
					}
				else
					{
					$dnc_found=0;
					}

				# Campaign DNC Check
				if ( ($campaign_dnc_check=='YES') or ($campaign_dnc_check=='Y') )
					{
					$stmt="SELECT use_other_campaign_dnc from vicidial_campaigns where campaign_id='$campaign_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$use_other_campaign_dnc =	$row[0];
					$temp_campaign_id = $campaign_id;
					if (strlen($use_other_campaign_dnc) > 0) {$temp_campaign_id = $use_other_campaign_dnc;}

					$stmt="SELECT count(*) from vicidial_campaign_dnc where phone_number='$value' and campaign_id='$temp_campaign_id';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$camp_dnc_found=$row[0];
					}
				else
					{
					$camp_dnc_found=0;
					}

				if ($dnc_found==0 and $camp_dnc_found==0)
					{
					### insert a new lead in the system with this phone number
					$stmt = "INSERT INTO vicidial_list SET phone_code=\"$phone_code\",phone_number=\"$value\",list_id=\"$list_id\",status=\"NEW\",user=\"$user\",vendor_lead_code=\"$vendor_lead_code\",source_id=\"$source_id\",title=\"$title\",first_name=\"$first_name\",middle_initial=\"$middle_initial\",last_name=\"$last_name\",address1=\"$address1\",address2=\"$address2\",address3=\"$address3\",city=\"$city\",state=\"$state\",province=\"$province\",postal_code=\"$postal_code\",country_code=\"$country_code\",gender=\"$gender\",date_of_birth=\"$date_of_birth\",alt_phone=\"$alt_phone\",email=\"$email\",security_phrase=\"$security_phrase\",comments=\"$comments\",called_since_last_reset=\"N\",entry_date=\"$ENTRYdate\",last_local_call_time=\"$NOW_TIME\",rank=\"$rank\",owner=\"$owner\";";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$affected_rows = mysqli_affected_rows($link);
					if ($affected_rows > 0)
						{
						$lead_id = mysqli_insert_id($link);
						$result = _QXZ("SUCCESS");
						$result_reason = _QXZ("lead added");
						echo "$result: $result_reason - $value|$campaign_id|$list_id|$lead_id|$agent_user\n";
						$data = "$value|$list_id|$lead_id";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("lead insertion failed");
						echo "$result: $result_reason - $value|$campaign_id|$list_id|$agent_user\n";
						$data = "$value|$list_id|$stmt";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				else
					{
					if ($dnc_found>0)
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("add_lead PHONE NUMBER IN DNC");
						echo "$result: $result_reason - $value|$agent_user\n";
						$data = "$value";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					if ($camp_dnc_found>0)
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("add_lead PHONE NUMBER IN CAMPAIGN DNC");
						echo "$result: $result_reason - $value|$campaign_id|$agent_user\n";
						$data = "$value|$campaign_id";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("campaign manual dial list undefined");
				echo "$result: $result_reason - $value|$campaign_id|$agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}		
		}
	}
################################################################################
### END - external_add_lead
################################################################################




################################################################################
### BEGIN - change_ingroups - change selected in-groups for logged-in agent
################################################################################
if ($function == 'change_ingroups')
	{
	$value = preg_replace("/[^A-Z]/","",$value);

	if ( (strlen($blended)<2) or (strlen($agent_user)<2) or (strlen($value)<3) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("change_ingroups not valid");
		$data = "$value|$blended|$ingroup_choices";
		echo "$result: $result_reason - $data|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select count(*) from vicidial_live_agents vla, vicidial_campaigns vc where user='$agent_user' and campaign_allow_inbound='Y' and vla.campaign_id=vc.campaign_id;";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select count(*) from vicidial_users where user='$user' and change_agent_campaign='1';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] > 0)
					{
					if ($blended == 'YES')
						{
						$stmt = "select count(*) from vicidial_live_agents vla, vicidial_campaigns vc where user='$agent_user' and dial_method IN('MANUAL','INBOUND_MAN') and vla.campaign_id=vc.campaign_id;";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						if ($row[0] > 0)
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("campaign dial_method does not allow outbound autodial");
							$data = "$blended";
							echo "$result: $result_reason - $agent_user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					# gather what the user's closer_campaigns is currently set to
					$stmt = "select closer_campaigns from vicidial_live_agents where user='$agent_user';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$orig_closer_groups_list = $row[0];

					if (strlen($ingroup_choices)>0)
						{
						$in_groups_pre = preg_replace('/-$/','',$ingroup_choices);
						$in_groups = explode(" ",$in_groups_pre);
						$in_groups_ct = count($in_groups);
						$k=1;
						while ($k < $in_groups_ct)
							{
							if (strlen($in_groups[$k])>1)
								{
								$stmt="SELECT count(*) FROM vicidial_inbound_groups where group_id='$in_groups[$k]';";
								$rslt=mysql_to_mysqli($stmt, $link);
								if ($DB) {echo "$stmt\n";}
								$row=mysqli_fetch_row($rslt);
								if ($row[0] < 1)
									{
									$result = _QXZ("ERROR");
									$result_reason = _QXZ("ingroup does not exist");
									$data = "$in_groups[$k]|$ingroup_choices";
									echo "$result: $result_reason - $agent_user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							$k++;
							}
						}
					if ( (strlen($ingroup_choices) < 1) and ( ($value == 'ADD') or ($value == 'REMOVE') ) )
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("ingroup_choices are required for ADD and REMOVE values");
						$data = "$value|$ingroup_choices";
						echo "$result: $result_reason - $agent_user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}

					if ($value == 'ADD')
						{
						$stmt = "select closer_campaigns from vicidial_live_agents where user='$agent_user';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$orig_closer_groups_list = $row[0];
						$closer_groups_pre = preg_replace('/-$/','',$row[0]);
						$closer_groups = explode(" ",$closer_groups_pre);
						$closer_groups_ct = count($closer_groups);

						$in_groups_pre = preg_replace('/-$/','',$ingroup_choices);
						$in_groups = explode(" ",$in_groups_pre);
						$in_groups_ct = count($in_groups);
						$k=1;
						while ($k < $in_groups_ct)
							{
							$duplicate_group=0;
							if (strlen($in_groups[$k])>1)
								{
								$m=0;
								while ($m < $closer_groups_ct)
									{
									if (strlen($closer_groups[$m])>1)
										{
										if ($closer_groups[$m] == $in_groups[$k])
											{$duplicate_group++;}
										}
									$m++;
									}
								if ($duplicate_group < 1)
									{
									$closer_groups[$closer_groups_ct] = $in_groups[$k];
									$closer_groups_ct++;
									}
								}
							$k++;
							}

						$m=0;
						$NEWcloser_groups=' ';
						while ($m < $closer_groups_ct)
							{
							if (strlen($closer_groups[$m])>1)
								{
								$NEWcloser_groups .= "$closer_groups[$m] ";
								}
							$m++;
							}
						$NEWcloser_groups .= '-';
						$ingroup_choices = $NEWcloser_groups;
						}

					if ($value == 'REMOVE')
						{
						$stmt = "select closer_campaigns from vicidial_live_agents where user='$agent_user';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$closer_groups_list = $row[0];
						$orig_closer_groups_list = $row[0];
						$closer_groups_pre = preg_replace('/-$/','',$row[0]);
						$closer_groups = explode(" ",$closer_groups_pre);
						$closer_groups_ct = count($closer_groups);

						$in_groups_pre = preg_replace('/-$/','',$ingroup_choices);
						$in_groups = explode(" ",$in_groups_pre);
						$in_groups_ct = count($in_groups);
						$k=1;
						while ($k < $in_groups_ct)
							{
							$duplicate_group=0;
							if (strlen($in_groups[$k])>1)
								{
								$m=0;
								while ($m < $closer_groups_ct)
									{
									if (strlen($closer_groups[$m])>1)
										{
										if ($closer_groups[$m] == $in_groups[$k])
											{$duplicate_group++;}
										}
									$m++;
									}
								if ($duplicate_group > 0)
									{
									$closer_groups_list = preg_replace("/ $in_groups[$k] /",' ',$closer_groups_list);
									}
								}
							$k++;
							}

						$ingroup_choices = $closer_groups_list;
						}

					### If no errors, run the update to change selected ingroups ###
					$external_blended=0;
					if ($blended == 'YES')
						{$external_blended=1;}

					$stmt="UPDATE vicidial_live_agents set external_ingroups='$ingroup_choices',external_blended='$external_blended',external_igb_set_user='$user',manager_ingroup_set='SET' where user='$agent_user';";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);

				#	$stmtA="DELETE FROM vicidial_live_inbound_agents where user='$agent_user';";
				#		if ($format=='debug') {echo "\n<!-- $stmtA -->";}
				#	$rslt=mysql_to_mysqli($stmtA, $link);

					# insert/update selected closer in-groups into the vicidial_live_inbound_agents table
					$in_groups_pre = preg_replace('/-$/','',$ingroup_choices);
					$in_groups = explode(" ",$in_groups_pre);
					$in_groups_ct = count($in_groups);
					$k=1;
					while ($k < $in_groups_ct)
						{
						if (strlen($in_groups[$k])>1)
							{
							$stmtB="SELECT group_weight,calls_today,group_grade,calls_today_filtered,daily_limit FROM vicidial_inbound_group_agents where user='$agent_user' and group_id='$in_groups[$k]';";
							$rslt=mysql_to_mysqli($stmtB, $link);
							if ($DB) {echo "$stmtB\n";}
							$viga_ct = mysqli_num_rows($rslt);
							if ($viga_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$group_weight = $row[0];
								$calls_today =	$row[1];
								$group_grade =	$row[2];
								$calls_today_filtered =	$row[3];
								$daily_limit =	$row[4];
								}
							else
								{
								$group_weight = 0;
								$calls_today =	0;
								$group_grade =	0;
								$calls_today_filtered =	0;
								$daily_limit =	-1;
								}
							$stmtB="INSERT IGNORE INTO vicidial_live_inbound_agents set user='$agent_user',group_id='$in_groups[$k]',group_weight='$group_weight',group_grade='$group_grade',calls_today='$calls_today',calls_today_filtered='$calls_today_filtered',last_call_time='$NOW_TIME',last_call_finish='$NOW_TIME',last_call_time_filtered='$NOW_TIME',last_call_finish_filtered='$NOW_TIME',daily_limit='$daily_limit' ON DUPLICATE KEY UPDATE group_weight='$group_weight',group_grade='$group_grade',calls_today='$calls_today',calls_today_filtered='$calls_today_filtered',daily_limit='$daily_limit';";
							$stmtBlog .= "$stmtB|";
								if ($format=='debug') {echo "\n<!-- $stmtB -->";}
							$rslt=mysql_to_mysqli($stmtB, $link);

							$orig_closer_groups_list = preg_replace("/ $in_groups[$k] /",' ',$orig_closer_groups_list);
							}
						$k++;
						}

					# remove de-selected closer groups
					$in_groups_pre = preg_replace('/-$/','',$orig_closer_groups_list);
					$in_groups = explode(" ",$in_groups_pre);
					$in_groups_ct = count($in_groups);
					$k=1;
					while ($k < $in_groups_ct)
						{
						if (strlen($in_groups[$k])>1)
							{
							$stmtB="DELETE FROM vicidial_live_inbound_agents WHERE user='$agent_user' and group_id='$in_groups[$k]';";
							$stmtBlog .= "$stmtB|";
								if ($format=='debug') {echo "\n<!-- $stmtB -->";}
							$rslt=mysql_to_mysqli($stmtB, $link);
							}
						$k++;
						}

					$default_data = "";
					if ($set_as_default == 'YES')
						{
						$stmt="UPDATE vicidial_users set closer_campaigns='$ingroup_choices',closer_default_blended='$external_blended' where user='$agent_user';";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$default_data = _QXZ("User settings set as default");

						### LOG INSERTION Admin Log Table ###
						$ip = getenv("REMOTE_ADDR");
						$SQL_log = "$stmt|$stmtA|$stmtBlog";
						$SQL_log = preg_replace('/;/','',$SQL_log);
						$SQL_log = addslashes($SQL_log);
						$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$user', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$agent_user', event_code='API MODIFY USER', event_sql=\"$SQL_log\", event_notes='';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						}

					$result = _QXZ("SUCCESS");
					$result_reason = _QXZ("change_ingroups function set");
					$data = "$ingroup_choices|$blended|$default_data";
					echo "$result: $result_reason - $user|$agent_user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("user is not allowed to change agent in-groups");
					echo "$result: $result_reason - $user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("campaign does not allow inbound calls");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - change_ingroups
################################################################################





################################################################################
### BEGIN - update_fields
################################################################################
if ($function == 'update_fields')
	{
	if (strlen($agent_user)<1)
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("agent_user not valid");
		$data = "$agent_user";
		echo "$result: $result_reason - $data\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select count(*) from vicidial_users where user='$user' and modify_leads IN('1','2','3','4');";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select lead_id from vicidial_live_agents where user='$agent_user';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$lead_id = $row[0];
				if ($lead_id > 0)
					{
					$fieldsSQL='';
					$fieldsLISTS='';
					$field_set=0;
					$agent_update=0;
					if (preg_match('/phone_code/',$query_string))
						{
						if ($DB) {echo _QXZ("phone_code set to")." $phone_code\n";}
						$fieldsSQL .= "phone_code=\"$phone_code\",";
						$fieldsLIST .= "phone_code,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/address1/',$query_string))
						{
						if ($DB) {echo _QXZ("address1 set to")." $address1\n";}
						$fieldsSQL .= "address1=\"$address1\",";
						$fieldsLIST .= "address1,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/address2/',$query_string))
						{
						if ($DB) {echo _QXZ("address2 set to")." $address2\n";}
						$fieldsSQL .= "address2=\"$address2\",";
						$fieldsLIST .= "address2,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/address3/',$query_string))
						{
						if ($DB) {echo _QXZ("address3 set to")." $address3\n";}
						$fieldsSQL .= "address3=\"$address3\",";
						$fieldsLIST .= "address3,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/alt_phone/',$query_string))
						{
						if ($DB) {echo _QXZ("alt_phone set to")." $alt_phone\n";}
						$fieldsSQL .= "alt_phone=\"$alt_phone\",";
						$fieldsLIST .= "alt_phone,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/city/',$query_string))
						{
						if ($DB) {echo _QXZ("city set to")." $city\n";}
						$fieldsSQL .= "city=\"$city\",";
						$fieldsLIST .= "city,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/comments/',$query_string))
						{
						if ($DB) {echo _QXZ("comments set to")." $comments\n";}
						$fieldsSQL .= "comments=\"$comments\",";
						$fieldsLIST .= "comments,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/country_code/',$query_string))
						{
						if ($DB) {echo _QXZ("country_code set to")." $country_code\n";}
						$fieldsSQL .= "country_code=\"$country_code\",";
						$fieldsLIST .= "country_code,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/date_of_birth/',$query_string))
						{
						if ($DB) {echo _QXZ("date_of_birth set to")." $date_of_birth\n";}
						$fieldsSQL .= "date_of_birth=\"$date_of_birth\",";
						$fieldsLIST .= "date_of_birth,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/email/',$query_string))
						{
						if ($DB) {echo _QXZ("email set to")." $email\n";}
						$fieldsSQL .= "email=\"$email\",";
						$fieldsLIST .= "email,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/first_name/',$query_string))
						{
						if ($DB) {echo _QXZ("first_name set to")." $first_name\n";}
						$fieldsSQL .= "first_name=\"$first_name\",";
						$fieldsLIST .= "first_name,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/gender/',$query_string))
						{
						if ($DB) {echo _QXZ("gender set to")." $gender\n";}
						$fieldsSQL .= "gender=\"$gender\",";
						$fieldsLIST .= "gender,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/gmt_offset_now/',$query_string))
						{
						if ($DB) {echo _QXZ("gmt_offset_now set to")." $gmt_offset_now\n";}
						$fieldsSQL .= "gmt_offset_now=\"$gmt_offset_now\",";
						$fieldsLIST .= "gmt_offset_now,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/last_name/',$query_string))
						{
						if ($DB) {echo _QXZ("last_name set to")." $last_name\n";}
						$fieldsSQL .= "last_name=\"$last_name\",";
						$fieldsLIST .= "last_name,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/middle_initial/',$query_string))
						{
						if ($DB) {echo _QXZ("middle_initial set to")." $middle_initial\n";}
						$fieldsSQL .= "middle_initial=\"$middle_initial\",";
						$fieldsLIST .= "middle_initial,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/phone_number/',$query_string))
						{
						if ($DB) {echo _QXZ("phone_number set to")." $phone_number\n";}
						$fieldsSQL .= "phone_number=\"$phone_number\",";
						$fieldsLIST .= "phone_number,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/postal_code/i',$query_string))
						{
						if ($DB) {echo _QXZ("postal_code set to")." $postal_code\n";}
						$fieldsSQL .= "postal_code=\"$postal_code\",";
						$fieldsLIST .= "postal_code,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/province/i',$query_string))
						{
						if ($DB) {echo _QXZ("province set to")." $province\n";}
						$fieldsSQL .= "province=\"$province\",";
						$fieldsLIST .= "province,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/security_phrase/i',$query_string))
						{
						if ($DB) {echo _QXZ("security_phrase set to")." $security_phrase\n";}
						$fieldsSQL .= "security_phrase=\"$security_phrase\",";
						$fieldsLIST .= "security_phrase,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/source_id/i',$query_string))
						{
						if ($DB) {echo _QXZ("source_id set to")." $source_id\n";}
						$fieldsSQL .= "source_id=\"$source_id\",";
						$fieldsLIST .= "source_id,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/state/i',$query_string))
						{
						if ($DB) {echo _QXZ("state set to")." $state\n";}
						$fieldsSQL .= "state=\"$state\",";
						$fieldsLIST .= "state,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/title/i',$query_string))
						{
						if ($DB) {echo _QXZ("title set to")." $title\n";}
						$fieldsSQL .= "title=\"$title\",";
						$fieldsLIST .= "title,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/vendor_lead_code/i',$query_string))
						{
						if ($DB) {echo _QXZ("vendor_lead_code set to")." $vendor_lead_code\n";}
						$fieldsSQL .= "vendor_lead_code=\"$vendor_lead_code\",";
						$fieldsLIST .= "vendor_lead_code,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/rank/i',$query_string))
						{
						if ($DB) {echo _QXZ("rank set to")." $rank\n";}
						$fieldsSQL .= "rank=\"$rank\",";
						$fieldsLIST .= "rank,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/owner/i',$query_string))
						{
						if ($DB) {echo _QXZ("owner set to")." $owner\n";}
						$fieldsSQL .= "owner=\"$owner\",";
						$fieldsLIST .= "owner,";
						$field_set++;
						$agent_update++;
						}
					if (preg_match('/formreload/i',$query_string))
						{
						if ($DB) {echo _QXZ("form reload triggered")."\n";}
						$fieldsLIST .= "formreload,";
						$agent_update++;
						}
					if (preg_match('/scriptreload/i',$query_string))
						{
						if ($DB) {echo _QXZ("script reload triggered")."\n";}
						$fieldsLIST .= "scriptreload,";
						$agent_update++;
						}
					if (preg_match('/script2reload/i',$query_string))
						{
						if ($DB) {echo _QXZ("script 2 reload triggered")."\n";}
						$fieldsLIST .= "script2reload,";
						$agent_update++;
						}
					if (preg_match('/emailreload/i',$query_string))
						{
						if ($DB) {echo _QXZ("email reload triggered")."\n";}
						$fieldsLIST .= "emailreload,";
						$agent_update++;
						}
					if (preg_match('/chatreload/i',$query_string))
						{
						if ($DB) {echo _QXZ("chat reload triggered")."\n";}
						$fieldsLIST .= "chatreload,";
						$agent_update++;
						}
					if ( ($field_set > 0) or ($agent_update > 0) )
						{
						$fieldsSQL = preg_replace("/,$/","",$fieldsSQL);
						$fieldsLIST = preg_replace("/,$/","",$fieldsLIST);

						$stmt="UPDATE vicidial_list set $fieldsSQL where lead_id=$lead_id;";
						$affected_rowsVL=0;
						if ($field_set > 0)
							{
							$stmt="UPDATE vicidial_list set $fieldsSQL where lead_id=$lead_id;";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
							$affected_rowsVL = mysqli_affected_rows($link);
							}

						$affected_rowsVLA=0;
						$stmt="UPDATE vicidial_live_agents set external_update_fields='1',external_update_fields_data='$fieldsLIST' where user='$agent_user';";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$affected_rowsVLA = mysqli_affected_rows($link);

						$result = _QXZ("SUCCESS");
						$result_reason = _QXZ("update_fields lead updated");
						$data = "$user|$agent_user|$lead_id|$affected_rowsVL|$affected_rowsVLA|$fieldsLIST|$fieldsSQL|";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("no fields have been defined");
						echo "$result: $result_reason - $agent_user\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("agent_user does not have a lead on their screen");
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("user is not allowed to modify lead information");
				echo "$result: $result_reason - $agent_user|$user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - update_fields
################################################################################





################################################################################
### BEGIN - refresh_panel
################################################################################
if ($function == 'refresh_panel')
	{
	if (strlen($agent_user)<1)
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("agent_user not valid");
		$data = "$agent_user";
		echo "$result: $result_reason - $data\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$fieldsLISTS='';
			$agent_update=0;

			if (preg_match('/formreload/i',$query_string))
				{
				if ($DB) {echo _QXZ("form reload triggered")."\n";}
				$fieldsLIST .= "formreload,";
				$agent_update++;
				}
			if (preg_match('/scriptreload/i',$query_string))
				{
				if ($DB) {echo _QXZ("script reload triggered")."\n";}
				$fieldsLIST .= "scriptreload,";
				$agent_update++;
				}
			if (preg_match('/script2reload/i',$query_string))
				{
				if ($DB) {echo _QXZ("script 2 reload triggered")."\n";}
				$fieldsLIST .= "script2reload,";
				$agent_update++;
				}
			if (preg_match('/emailreload/i',$query_string))
				{
				if ($DB) {echo _QXZ("email reload triggered")."\n";}
				$fieldsLIST .= "emailreload,";
				$agent_update++;
				}
			if (preg_match('/chatreload/i',$query_string))
				{
				if ($DB) {echo _QXZ("chat reload triggered")."\n";}
				$fieldsLIST .= "chatreload,";
				$agent_update++;
				}
			if (preg_match('/callbacksreload/i',$query_string))
				{
				if ($DB) {echo _QXZ("callbacks reload triggered")."\n";}
				$fieldsLIST .= "callbacksreload,";
				$agent_update++;
				}
			if ( ($field_set > 0) or ($agent_update > 0) )
				{
				$fieldsLIST = preg_replace("/,$/","",$fieldsLIST);

				$affected_rowsVLA=0;
				$stmt="UPDATE vicidial_live_agents set external_update_fields='1',external_update_fields_data='$fieldsLIST' where user='$agent_user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rowsVLA = mysqli_affected_rows($link);

				$result = _QXZ("SUCCESS");
				$result_reason = _QXZ("refresh_panel request sent");
				$data = "$user|$agent_user|$affected_rowsVLA|$fieldsLIST|";
				echo "$result: $result_reason - $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no panels have been defined");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - refresh_panel
################################################################################





################################################################################
### BEGIN - set_timer_action
################################################################################
if ($function == 'set_timer_action')
	{
	if ( (strlen($agent_user)<1) or (strlen($value)<2) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("set_timer_action not valid");
		$data = "$agent_user|$value";
		echo "$result: $result_reason - $data\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select count(*) from vicidial_users where user='$user' and modify_campaigns='1';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt="UPDATE vicidial_live_agents set external_timer_action='$value',external_timer_action_message='$notes',external_timer_action_seconds='$rank' where user='$agent_user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);

				$result = _QXZ("SUCCESS");
				$result_reason = _QXZ("set_timer_action lead updated");
				$data = "$user|$agent_user|$value|$notes|$rank";
				echo "$result: $result_reason - $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("user is not allowed to modify campaign settings");
				echo "$result: $result_reason - $agent_user|$user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - set_timer_action
################################################################################





################################################################################
### BEGIN - st_login_log - looks up vicidial_users.custom_three from a CRM
################################################################################
if ($function == 'st_login_log')
	{
	if ( (strlen($value)<1) or (strlen($vendor_id)<1) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("st_login_log not valid");
		$data = "$value|$vendor_id";
		echo "$result: $result_reason - $data\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt = "select count(*) from vicidial_users where custom_three='$value';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select user from vicidial_users where custom_three='$value' order by user;";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);

			$stmt="UPDATE vicidial_users set custom_four='$vendor_id' where user='$row[0]';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);

			$result = _QXZ("SUCCESS");
			$result_reason = _QXZ("st_login_log user found");
			$data = "$row[0]";
			echo "$result: $result_reason - $row[0]\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("no user found");
			echo "$result: $result_reason - $value\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - st_login_log
################################################################################




################################################################################
### BEGIN - st_get_agent_active_lead - looks up vicidial_users.custom_three and output active lead info
################################################################################
if ($function == 'st_get_agent_active_lead')
	{
	if ( (strlen($value)<1) or (strlen($vendor_id)<1) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("st_get_agent_active_lead not valid");
		$data = "$value|$vendor_id";
		echo "$result: $result_reason - $data\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt = "select count(*) from vicidial_users where custom_three='$value';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select user from vicidial_users where custom_three='$value' order by user;";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$VC_user = $row[0];

			$stmt = "select count(*) from vicidial_live_agents where user='$VC_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select lead_id from vicidial_live_agents where user='$VC_user';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$lead_id = $row[0];

				if ($lead_id > 0)
					{
					$stmt = "select phone_number,vendor_lead_code,province,security_phrase,source_id from vicidial_list where lead_id=$lead_id;";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$phone_number =		$row[0];
					$vendor_lead_code = $row[1];
					$province =			$row[2];
					$security_phrase =	$row[3];
					$source_id =		$row[4];

					$result = _QXZ("SUCCESS");
					$result_reason = _QXZ("st_get_agent_active_lead lead found");
					$data = "$VC_user|$phone_number|$lead_id|$vendor_lead_code|$province|$security_phrase|$source_id";
					echo "$result: $result_reason - $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("no active lead found");
					echo "$result: $result_reason - $VC_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("user not logged in");
				echo "$result: $result_reason - $VC_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("no user found");
			echo "$result: $result_reason - $value\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - st_get_agent_active_lead
################################################################################




################################################################################
### BEGIN - ra_call_control - remote agent call control: hangup/transfer
################################################################################
if ($function == 'ra_call_control')
	{
	if ( (strlen($value)<1) or (strlen($agent_user)<1) or (strlen($stage)<1) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("ra_call_control not valid");
		echo "$result: $result_reason - $value|$agent_user|$stage\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select count(*) from vicidial_auto_calls where callerid='$value';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select channel,server_ip,call_type,campaign_id,lead_id,phone_number,uniqueid,stage,queue_position from vicidial_auto_calls where callerid='$value';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$channel =		$row[0];
				$server_ip = 	$row[1];
				$call_type = 	$row[2];
				$campaign_id =	$row[3];
				$lead_id =		$row[4];
				$vdac_phone =	$row[5];
				$uniqueid =		$row[6];
				$ra_stage =		$row[7];
				$queue_position =	$row[8];

				$stmt = "select server_ip,user,extension from vicidial_live_agents where callerid='$value';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$ra_server_ip = $row[0];
				$ra_user =		$row[1];
				$ra_extension =	$row[2];

				$processed=0;
				if ($stage=='HANGUP')
					{
					$processed++;
					$HANGUPcid = $value;
					$HANGUPcid = preg_replace("/^..../",'HAPI',$HANGUPcid);

					$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Hangup','$HANGUPcid','Channel: $channel','','','','','','','','','');";
				#		if ($format=='debug') {echo "\n<!-- $stmt -->";}
				#	$rslt=mysql_to_mysqli($stmt, $link);
					$result = _QXZ("SUCCESS");
					$result_reason = _QXZ("ra_call_control hungup");
					echo "$result: $result_reason - $agent_user|$value|HANGUP\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				if ($stage=='EXTENSIONTRANSFER')
					{
					$processed++;
					if (strlen($phone_number) < 2)
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("phone_number is not valid");
						echo "$result: $result_reason - $phone_number\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$stmt = "select ext_context from servers where server_ip='$server_ip';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$ext_context =		$row[0];

						$TRANSFERcid = $value;
						$TRANSFERcid = preg_replace("/^..../",'XAPI',$TRANSFERcid);

						$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$TRANSFERcid','Channel: $channel','Context: $ext_context','Exten: $phone_number','Priority: 1','CallerID: $TRANSFERcid','','','','','');";
					#		if ($format=='debug') {echo "\n<!-- $stmt -->";}
					#	$rslt=mysql_to_mysqli($stmt, $link);
						$result = _QXZ("SUCCESS");
						$result_reason = _QXZ("ra_call_control transfer");
						echo "$result: $result_reason - $agent_user|$value|$phone_number\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				if ($stage=='INGROUPTRANSFER')
					{
					$processed++;
					if (strlen($ingroup_choices) < 2)
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("ingroup is not valid");
						echo "$result: $result_reason - $ingroup_choices\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$stmt = "select ext_context from servers where server_ip='$server_ip';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$ext_context =		$row[0];

						if (preg_match("/DEFAULTINGROUP/",$ingroup_choices))
							{
							if ($call_type=='IN')
								{
								$stmt = "select default_xfer_group from vicidial_inbound_groups where group_id='$campaign_id';";
								}
							else
								{
								$stmt = "select default_xfer_group from vicidial_campaigns where campaign_id='$campaign_id';";
								}
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$ingroup_choices =		$row[0];
							}

						$stmt = "select count(*) from vicidial_inbound_groups where group_id='$ingroup_choices' and active='Y';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$ingroupactive =	$row[0];

						if ($ingroupactive < 1)
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("ingroup is not valid");
							echo "$result: $result_reason - $ingroup_choices\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$TRANSFERcid = $value;
							$TRANSFERcid = preg_replace("/^..../",'XAPI',$TRANSFERcid);

							$TRANSFERexten = "90009*$ingroup_choices**$lead_id**$vdac_phone**$agent_user**";

							$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$server_ip','','Redirect','$TRANSFERcid','Channel: $channel','Context: $ext_context','Exten: $TRANSFERexten','Priority: 1','CallerID: $TRANSFERcid','','','','','');";
						#		if ($format=='debug') {echo "\n<!-- $stmt -->";}
						#	$rslt=mysql_to_mysqli($stmt, $link);
							$result = _QXZ("SUCCESS");
							$result_reason = _QXZ("ra_call_control transfer");
							echo "$result: $result_reason - $agent_user|$value|$phone_number\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				if ($processed < 1)
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("stage is not valid");
					echo "$result: $result_reason - $stage\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}

				if ($result == 'SUCCESS')
					{
					if (strlen($status)<1)
						{$status='RAXFER';}
					if ($call_type=='IN')
						{

						$stmt = "UPDATE vicidial_closer_log SET status='$status',length_in_sec=(UNIX_TIMESTAMP(NOW()) - start_epoch),end_epoch=UNIX_TIMESTAMP(NOW()) where uniqueid='$uniqueid' and lead_id=$lead_id and campaign_id='$campaign_id' order by closecallid desc limit 1;";
						}
					else
						{
						$stmt = "UPDATE vicidial_log SET status='$status',user='$agent_user',length_in_sec=(UNIX_TIMESTAMP(NOW()) - start_epoch),end_epoch=UNIX_TIMESTAMP(NOW()) where uniqueid='$uniqueid' and lead_id=$lead_id order by call_date desc limit 1;";
						}
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$stmt = "UPDATE vicidial_list SET status='$status' where lead_id=$lead_id limit 1;";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$StarTtime = date("U");
					$RArandom = (rand(1000000, 9999999) + 10000000);

					#############################################
					##### START QUEUEMETRICS LOGGING LOOKUP #####
					$stmt = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_pe_phone_append,queuemetrics_socket,queuemetrics_socket_url,queuemetrics_pause_type FROM system_settings;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$qm_conf_ct = mysqli_num_rows($rslt);
					if ($qm_conf_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$enable_queuemetrics_logging =	$row[0];
						$queuemetrics_server_ip	=		$row[1];
						$queuemetrics_dbname =			$row[2];
						$queuemetrics_login	=			$row[3];
						$queuemetrics_pass =			$row[4];
						$queuemetrics_log_id =			$row[5];
						$queuemetrics_pe_phone_append =	$row[6];
						$queuemetrics_socket =			$row[7];
						$queuemetrics_socket_url =		$row[8];
						$queuemetrics_pause_type =		$row[9];
						}
					##### END QUEUEMETRICS LOGGING LOOKUP #####
					###########################################
					if ($enable_queuemetrics_logging > 0)
						{
						$linkB=mysqli_connect("$queuemetrics_server_ip", "$queuemetrics_login", "$queuemetrics_pass");
						if (!$linkB) {die(_QXZ("Could not connect: ")."$queuemetrics_server_ip|$queuemetrics_login" . mysqli_connect_error());}
						mysqli_select_db($linkB, "$queuemetrics_dbname");

						$stmt = "SELECT time_id from queue_log where call_id='$value' and queue='$campaign_id' and agent='Agent/$ra_user' and verb='CONNECT';";
						$rslt=mysql_to_mysqli($stmt, $linkB);
						if ($DB) {echo "$stmt\n";}
						$qm_con_ct = mysqli_num_rows($rslt);
						if ($qm_con_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$ra_time_id =	$row[0];
							$ra_length = ($StarTtime - $ra_time_id);
							}
						if ($ra_length < 1) {$ra_length=1;}
						$ra_stage = preg_replace("/XFER|CLOSER|-/",'',$ra_stage);
						if ($ra_stage < 0.25) {$ra_stage=0;}

						$data4SQL='';
						$data4SS='';
						$stmt="SELECT queuemetrics_phone_environment FROM vicidial_campaigns where campaign_id='$campaign_id' and queuemetrics_phone_environment!='';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$cqpe_ct = mysqli_num_rows($rslt);
						if ($cqpe_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$pe_append='';
							if ( ($queuemetrics_pe_phone_append > 0) && (strlen($row[0])>0) )
								{
								$qm_extension = explode('/',$ra_extension);
								$pe_append = "-$qm_extension[1]";
								}
							$data4SQL = ",data4='$row[0]$pe_append'";
							$data4SS = "&data4=$row[0]$pe_append";
							}

						$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='$value',queue='$campaign_id',agent='Agent/$ra_user',verb='COMPLETEAGENT',data1='$ra_stage',data2='$ra_length',data3='$queue_position',serverid='$queuemetrics_log_id' $data4SQL;";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $linkB);

						$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='$value',queue='$campaign_id',agent='Agent/$ra_user',verb='CALLSTATUS',data1='$status',serverid='$queuemetrics_log_id';";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $linkB);

						$pause_typeSQL='';
						if ($queuemetrics_pause_type > 0)
							{$pause_typeSQL=",data5='API'";}
						$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='NONE',queue='NONE',agent='Agent/$ra_user',verb='PAUSEALL',serverid='$queuemetrics_log_id' $data4SQL $pause_typeSQL;";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $linkB);

						$stmt = "INSERT INTO queue_log SET `partition`='P01',time_id='$StarTtime',call_id='NONE',queue='NONE',agent='Agent/$ra_user',verb='UNPAUSEALL',serverid='$queuemetrics_log_id' $data4SQL  $pause_typeSQL;";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
						$rslt=mysql_to_mysqli($stmt, $linkB);

						if ( ($queuemetrics_socket == 'CONNECT_COMPLETE') and (strlen($queuemetrics_socket_url) > 10) )
							{
							if (preg_match("/--A--/",$queuemetrics_socket_url))
								{
								##### grab the data from vicidial_list for the lead_id
								$stmt="SELECT vendor_lead_code,list_id,phone_code,phone_number,title,first_name,middle_initial,last_name,postal_code FROM vicidial_list where lead_id=$lead_id LIMIT 1;";
								$rslt=mysql_to_mysqli($stmt, $link);
								if ($DB) {echo "$stmt\n";}
								$list_lead_ct = mysqli_num_rows($rslt);
								if ($list_lead_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$vendor_id		= urlencode(trim($row[0]));
									$list_id		= urlencode(trim($row[1]));
									$phone_code		= urlencode(trim($row[2]));
									$phone_number	= urlencode(trim($row[3]));
									$title			= urlencode(trim($row[4]));
									$first_name		= urlencode(trim($row[5]));
									$middle_initial	= urlencode(trim($row[6]));
									$last_name		= urlencode(trim($row[7]));
									$postal_code	= urlencode(trim($row[8]));
									}
								$queuemetrics_socket_url = preg_replace('/^VAR/','',$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--lead_id--B--/i',"$lead_id",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--vendor_id--B--/i',"$vendor_id",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--vendor_lead_code--B--/i',"$vendor_id",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--list_id--B--/i',"$list_id",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--phone_number--B--/i',"$phone_number",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--title--B--/i',"$title",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--first_name--B--/i',"$first_name",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--middle_initial--B--/i',"$middle_initial",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--last_name--B--/i',"$last_name",$queuemetrics_socket_url);
								$queuemetrics_socket_url = preg_replace('/--A--postal_code--B--/i',"$postal_code",$queuemetrics_socket_url);
								}
							$socket_send_data_begin='?';
							$socket_send_data = "time_id=$StarTtime&call_id=$value&queue=$campaign_id&agent=Agent/$ra_user&verb=COMPLETEAGENT&data1=$ra_stage&data2=$ra_length&data3=$queue_position$data4SS";
							if (preg_match("/\?/",$queuemetrics_socket_url))
								{$socket_send_data_begin='&';}
							### send queue_log data to the queuemetrics_socket_url ###
							if ($DB > 0) {echo "$queuemetrics_socket_url$socket_send_data_begin$socket_send_data<BR>\n";}
							$SCUfile = file("$queuemetrics_socket_url$socket_send_data_begin$socket_send_data");
							if ($DB > 0) {echo "$SCUfile[0]<BR>\n";}
							}
						}

					### finally send the call
					if ($format=='debug') {echo "\n<!-- $stmtX -->";}
					$rslt=mysql_to_mysqli($stmtX, $link);

					$stmt = "UPDATE vicidial_live_agents set random_id='$RArandom',last_call_finish='$NOW_TIME',lead_id='',uniqueid='',callerid='',channel='',last_state_change='$NOW_TIME' where user='$ra_user' and server_ip='$ra_server_ip';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$stmt = "UPDATE vicidial_live_agents set status='READY' where user='$ra_user' and server_ip='$ra_server_ip';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$stmt = "DELETE from vicidial_auto_calls where callerid='$value';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$stmt = "UPDATE vicidial_live_agents set ring_callerid='' where ring_callerid='$value';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no active call found");
				echo "$result: $result_reason - $value\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - ra_call_control
################################################################################






################################################################################
### BEGIN - send_dtmf - send dtmf signals
################################################################################
if ($function == 'send_dtmf')
	{
	if ( (strlen($value)<1) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("send_dtmf not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt="UPDATE vicidial_live_agents set external_dtmf='$value' where user='$agent_user';";
				if ($format=='debug') {echo "\n<!-- $stmt -->";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$result = _QXZ("SUCCESS");
			$result_reason = "send_dtmf function set";
			echo "$result: $result_reason - $value|$agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - send_dtmf
################################################################################





################################################################################
### BEGIN - park_call - send customer to park or pick up customer from park
################################################################################
if ($function == 'park_call')
	{
	if ( (!preg_match("/PARK_CUSTOMER|GRAB_CUSTOMER|PARK_IVR_CUSTOMER|GRAB_IVR_CUSTOMER|PARK_XFER|GRAB_XFER/",$value)) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("park_call not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select lead_id from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$lead_id = $row[0];
			if ($lead_id > 0)
				{
				$stmt="UPDATE vicidial_live_agents set external_park='$value' where user='$agent_user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$result = _QXZ("SUCCESS");
				$result_reason = _QXZ("park_call function set");
				echo "$result: $result_reason - $value|$agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user does not have a lead on their screen");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - park_call
################################################################################





################################################################################
### BEGIN - transfer_conference - send several different functions for 3-way calling and transfers
################################################################################
if ($function == 'transfer_conference')
	{
	if ( (strlen($value)<8) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("transfer_conference not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$processed=0;
		$SUCCESS=0;
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select lead_id,callerid,server_ip,conf_exten,status from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$lead_id =			$row[0];
			$callerid =			$row[1];
			$AGENTserver_ip =	$row[2];
			$AGENTconf_exten =	$row[3];
			$AGENTstatus =		$row[4];
			if ( ($lead_id > 0) and (strlen($callerid)>15) )
				{
				### START In-group transfer or bridge ###
				if ( ($value=='LOCAL_CLOSER') or ( ( ($value=='DIAL_WITH_CUSTOMER') or ($value=='PARK_CUSTOMER_DIAL') ) and ($consultative=='YES') ) )
					{
					$processed++;
					if (strlen($ingroup_choices) < 2)
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("ingroup is not valid");
						echo "$result: $result_reason - $ingroup_choices\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						if (preg_match("/DEFAULTINGROUP/",$ingroup_choices))
							{
							$stmt = "select campaign_id,call_type from vicidial_auto_calls where callerid='$callerid';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$campaign_id =	$row[0];
							$call_type =	$row[1];

							if ($call_type=='IN')
								{
								$stmt = "select default_xfer_group from vicidial_inbound_groups where group_id='$campaign_id';";
								}
							else
								{
								$stmt = "select default_xfer_group from vicidial_campaigns where campaign_id='$campaign_id';";
								}
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$ingroup_choices =		$row[0];
							}

						$stmt = "select count(*) from vicidial_inbound_groups where group_id='$ingroup_choices' and active='Y';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$ingroupactive =	$row[0];

						if ($ingroupactive < 1)
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("ingroup is not valid");
							echo "$result: $result_reason - $ingroup_choices\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$SUCCESS++;

							$caller_id_number='';
							if (strlen($group_alias)>1)
								{
								$stmt = "select caller_id_number from groups_alias where group_alias_id='$group_alias';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$VDIG_cidnum_ct = mysqli_num_rows($rslt);
								if ($VDIG_cidnum_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$caller_id_number	= $row[0];
									if ($caller_id_number < 4)
										{
										$result = _QXZ("ERROR");
										$result_reason = _QXZ("caller_id_number from group_alias is not valid");
										$data = "$group_alias|$caller_id_number";
										echo "$result: $result_reason - $agent_user|$data\n";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										exit;
										}
									}
								else
									{
									$result = _QXZ("ERROR");
									$result_reason = _QXZ("group_alias is not valid");
									$data = "$group_alias";
									echo "$result: $result_reason - $agent_user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							if (strlen($cid_choice)>1)
								{
								if (!preg_match("/CAMPAIGN|AGENT_PHONE|CUSTOMER|CUSTOM_CID/",$cid_choice))
									{
									$result = _QXZ("ERROR");
									$result_reason = _QXZ("cid_choice is not valid");
									$data = "$cid_choice";
									echo "$result: $result_reason - $agent_user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}

							$external_transferconf = "$value---$ingroup_choices---$phone_number---$consultative------$group_alias---$caller_id_number---$epoch---$cid_choice";
							}
						}
					}
				### END In-group transfer or bridge ###

				### START other transfers ###
				if ( ($processed < 1) and (($value=='BLIND_TRANSFER') or ($value=='LEAVE_VM') or ($value=='DIAL_WITH_CUSTOMER') or ($value=='PARK_CUSTOMER_DIAL')) )
					{
					$caller_id_number='';
					if (strlen($group_alias)>1)
						{
						$stmt = "select caller_id_number from groups_alias where group_alias_id='$group_alias';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$VDIG_cidnum_ct = mysqli_num_rows($rslt);
						if ($VDIG_cidnum_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$caller_id_number	= $row[0];
							if ($caller_id_number < 4)
								{
								$result = _QXZ("ERROR");
								$result_reason = _QXZ("caller_id_number from group_alias is not valid");
								$data = "$group_alias|$caller_id_number";
								echo "$result: $result_reason - $agent_user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						else
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("group_alias is not valid");
							$data = "$group_alias";
							echo "$result: $result_reason - $agent_user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					if (strlen($cid_choice)>1)
						{
						if (!preg_match("/CAMPAIGN|AGENT_PHONE|CUSTOMER|CUSTOM_CID/",$cid_choice))
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("cid_choice is not valid");
							$data = "$cid_choice";
							echo "$result: $result_reason - $agent_user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					$processed++;
					$external_transferconf = "$value------$phone_number---NO---$dial_override---$group_alias---$caller_id_number---$epoch---$cid_choice";
					$SUCCESS++;
					}

				### START hangups ###
				if ( ($processed < 1) and ( ($value=='HANGUP_XFER') or ($value=='HANGUP_BOTH') ) )
					{
					$processed++;
					$external_transferconf = "$value---------NO------------$epoch";
					$SUCCESS++;
					}

				### START leave-3way-call ###
				if ( ($processed < 1) and ($value=='LEAVE_3WAY_CALL') )
					{
					$processed++;
					$external_transferconf = "$value---------NO------------$epoch";
					$SUCCESS++;
					}

				if ($processed < 1)
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("value is not valid");
					echo "$result: $result_reason - $value|$user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					if ($SUCCESS > 0)
						{
						$stmtVP='';
						$multi_dial_phones_OUTPUT='';
						$multi_dial_phones_STRING='';
						if (strlen($multi_dial_phones) > 4)
							{
							### START 3-way press-1 multi-dial calls processing ###
							$multi_dial_phonesARY=array();
							$multi_dial_phones_ct=0;
							if (preg_match("/,/",$multi_dial_phones))
								{
								$multi_dial_phonesARY = explode(',',$multi_dial_phones);
								$multi_dial_phones_ct = count($multi_dial_phonesARY);
								}
							else
								{
								$multi_dial_phonesARY[0] = $multi_dial_phones;
								$multi_dial_phones_ct = 1;
								}
							$mp=0;
							$vp=0;
							while ($mp < $multi_dial_phones_ct)
								{
								$temp_phone = $multi_dial_phonesARY[$mp];
								if ( (strlen($temp_phone) > 4) and (strlen($temp_phone) < 19) and (!preg_match("/$temp_phone/",$multi_dial_phones_STRING)) )
									{
									if ($vp > 0) {$multi_dial_phones_STRING .= ",";}
									$multi_dial_phones_STRING .= "$temp_phone";
									$vp++;
									}
								$mp++;
								}
							$affected_rowsVP=0;
							if ($vp > 0)
								{
								$md_check_ok=1;
								$md_check_ct=0;
								if (preg_match("/YES/i",$md_check))
									{
									$half_hour_ago = date("Y-m-d H:i:s", mktime(date("H"),date("i")-30,date("s"),date("m"),date("d"),date("Y")));
									# check for still-active previous 3-way calls from this agent and send error if any are found
									$stmt = "select count(*) from vicidial_3way_press_live where user='$agent_user' and status NOT IN('HUNGUP','DEFEATED','TRANSFER','TOOSLOW','DECLINED') and call_date > \"$half_hour_ago\";";
									if ($DB) {echo "$stmt\n";}
									$rslt=mysql_to_mysqli($stmt, $link);
									$VDTW_live_ct = mysqli_num_rows($rslt);
									if ($VDTW_live_ct > 0)
										{
										$row=mysqli_fetch_row($rslt);
										$md_check_ct	= $row[0];
										}
									if ($md_check_ct > 0) {$md_check_ok = 0;}
									}
								if ($md_check_ok > 0)
									{
									$stmtVP = "INSERT IGNORE INTO vicidial_3way_press_multi SET user='$agent_user',call_date=NOW(),phone_numbers='$multi_dial_phones_STRING',phone_numbers_ct='$vp',status='NEW' ON DUPLICATE KEY UPDATE call_date=NOW(),phone_numbers='$multi_dial_phones_STRING',phone_numbers_ct='$vp',status='NEW';";
									$rslt=mysql_to_mysqli($stmtVP, $link);
									$affected_rowsVP = mysqli_affected_rows($link);
									}
								else 
									{
									$result = _QXZ("ERROR");
									$result_reason = _QXZ("agent_user has previous active 3-way calls");
									$data = "$md_check_ct|$md_check_ok|$md_check";
									echo "$result: $result_reason - $agent_user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							$multi_dial_phones_OUTPUT = "|Multi-phones: $vp $mp $affected_rowsVP";
							### END 3-way press-1 multi-dial calls processing ###
							}

						### START Check for already active 3-way calls in agent session, if enabled ###
						if (preg_match("/YES/i",$tw_check))
							{
							$conf_engine='';
							$conf_table = 'vicidial_conferences';
							$tw_check_ct=0;

							$stmt = "SELECT conf_engine from servers where server_ip='$AGENTserver_ip';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$conf_engine =	$row[0];
							if (preg_match("/CONFBRIDGE/i",$conf_engine)) {$conf_table = 'vicidial_confbridges';}

							# check for active 3-way call from this agent and send error if any are found
							$stmt = "SELECT count(*) from live_sip_channels where server_ip='$AGENTserver_ip' and channel_group LIKE \"DC%W\" and extension='$AGENTconf_exten';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$TW_live_ct = mysqli_num_rows($rslt);
							if ($TW_live_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$tw_check_ct	= $row[0];
								}
							if ($tw_check_ct > 0)
								{
								$result = _QXZ("ERROR");
								$result_reason = _QXZ("agent_user has current active 3-way call");
								$data = "$tw_check_ct|0|$tw_check";
								echo "$result: $result_reason - $agent_user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						### END Check for already active 3-way calls in agent session, if enabled ###

						$stmt="UPDATE vicidial_live_agents set external_transferconf='$external_transferconf' where user='$agent_user';";
							if ($format=='debug') {echo "\n<!-- $stmt -->";}
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$result = _QXZ("SUCCESS");
						$result_reason = _QXZ("transfer_conference function set");
						$data = "$callerid$multi_dial_phones_OUTPUT";
						echo "$result: $result_reason - $value|$ingroup_choices|$phone_number|$consultative|$agent_user|$data|\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user does not have a live call");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - transfer_conference
################################################################################







################################################################################
### BEGIN - switch_lead - for inbound calls, switches lead_id of live inbound call on agent screen
################################################################################
if ($function == 'switch_lead')
	{
	if ( ( ( (strlen($lead_id)<1) or ($lead_id < 1) ) and (strlen($vendor_lead_code)<1) ) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("switch_lead not valid");
		echo "$result: $result_reason - $lead_id|$vendor_lead_code|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $lead_id|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$processed=0;
		$SUCCESS=0;
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select lead_id,callerid,campaign_id from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$current_lead_id =		$row[0];
			$callerid =				$row[1];
			$campaign_id =			$row[2];

			if ( ($current_lead_id > 0) and (strlen($callerid)>15) )
				{
				if (preg_match("/^Y/",$callerid))
					{
					$stmt = "SELECT agent_lead_search from vicidial_campaigns where campaign_id='$campaign_id';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$camp_ct = mysqli_num_rows($rslt);
					if ($camp_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$agent_lead_search =	$row[0];

						if ( ($agent_lead_search == 'LIVE_CALL_INBOUND') or ($agent_lead_search == 'LIVE_CALL_INBOUND_AND_MANUAL') )
							{
							### search for defined lead ###
							$searchSQL = "lead_id=$lead_id";
							if (strlen($vendor_lead_code)>0) {$searchSQL = "vendor_lead_code='$vendor_lead_code' order by lead_id desc limit 1";}
							$stmt = "SELECT lead_id,vendor_lead_code from vicidial_list where $searchSQL;";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$sl_ct = mysqli_num_rows($rslt);
							if ($sl_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$new_lead_id =	$row[0];
								$new_vendor_lead_code = $row[1];

								$stmt="UPDATE vicidial_live_agents set external_lead_id='$new_lead_id' where user='$agent_user';";
									if ($format=='debug') {echo "\n<!-- $stmt -->";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$result = _QXZ("SUCCESS");
								$result_reason = _QXZ("switch_lead function set");
								$data = "$new_lead_id|$new_vendor_lead_code|$campaign_id|$current_lead_id";
								echo "$result: $result_reason - $agent_user|$data|\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								$result = _QXZ("ERROR");
								$result_reason = _QXZ("switch-to lead not found");
								echo "$result: $result_reason - $agent_user|$lead_id|$vendor_lead_code\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						else
							{
							$result = _QXZ("ERROR");
							$result_reason = _QXZ("campaign does not allow inbound lead search");
							echo "$result: $result_reason - $agent_user|$campaign_id\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					else
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("campaign not found");
						echo "$result: $result_reason - $agent_user|$campaign_id\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("agent call is not inbound");
					echo "$result: $result_reason - $agent_user|$callerid";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user does not have a live call");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - switch_lead
################################################################################





################################################################################
### BEGIN - vm_message - set a custom voicemail message to be played when agent clicks the VM button on the agent screen
################################################################################
if ($function == 'vm_message')
	{
	if ( ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) or (strlen($value)<1) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("vm_message not valid");
		echo "$result: $result_reason - $lead_id|$value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $lead_id|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$processed=0;
		$SUCCESS=0;
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select lead_id,callerid,campaign_id from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$current_lead_id =		$row[0];
			$callerid =				$row[1];
			$campaign_id =			$row[2];

			if ( ($current_lead_id > 0) and (strlen($callerid)>15) )
				{
				if (strlen($lead_id) < 1) {$lead_id = $current_lead_id;}
				if ($current_lead_id == $lead_id)
					{
					$stmt="INSERT INTO vicidial_lead_messages (lead_id,call_date,user,message_entry) values('$lead_id',NOW(),'$user','$value');";
						if ($format=='debug') {echo "\n<!-- $stmt -->";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$result = _QXZ("SUCCESS");
					$result_reason = _QXZ("vm_message function set");
					$data = "$lead_id|$value";
					echo "$result: $result_reason - $agent_user|$data|\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("current call does not match lead_id submitted");
					$data = "$lead_id|$current_lead_id";
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user does not have a live call");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - vm_message
################################################################################





################################################################################
### BEGIN - pause_code - set a pause code for an agent that is already paused
################################################################################
if ($function == 'pause_code')
	{
	if ( (strlen($value)<1) or (strlen($value)>6) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("pause_code not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "select count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$stmt = "select status from vicidial_live_agents where user='$agent_user' and status='PAUSED';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rl_ct = mysqli_num_rows($rslt);
			if ($rl_ct > 0)
				{
				$pause_code_string = preg_replace("/\|/","','",$agent_login_call);
				$stmt="UPDATE vicidial_live_agents SET external_pause_code='$value' where user='$agent_user';";
					if ($format=='debug') {echo "\n<!-- $stmt -->";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$result = _QXZ("SUCCESS");
				$result_reason = _QXZ("pause_code function sent");
				echo "$result: $result_reason - $agent_user\n";
				$data = "$epoch";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("pause_code error - agent is not paused");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - pause_code
################################################################################





################################################################################
### BEGIN - calls_in_queue_count - display a count of the calls waiting in queue for the specific agent
################################################################################
if ($function == 'calls_in_queue_count')
	{
	if ( ($value!='DISPLAY') or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("calls_in_queue_count not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "SELECT count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{
			$ADsql='';
			### grab the status, campaign and in-group details for this logged-in agent
			$stmt="SELECT status,campaign_id,closer_campaigns from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$Alogin=$row[0];
			$Acampaign=$row[1];
			$AccampSQL=$row[2];
			$AccampSQL = preg_replace('/\s\-/','', $AccampSQL);
			$AccampSQL = preg_replace('/\s/',"','", $AccampSQL);
			if (preg_match('/AGENTDIRECT/i', $AccampSQL))
				{
				$AccampSQL = preg_replace('/AGENTDIRECT/i','', $AccampSQL);
				$ADsql = "or ( (campaign_id LIKE \"%AGENTDIRECT%\") and (agent_only='$agent_user') )";
				}

			### grab the number of calls waiting in queue that could be routed to this agent
			$stmt="SELECT count(*) from vicidial_auto_calls where status IN('LIVE') and ( (campaign_id='$Acampaign') or (campaign_id IN('$AccampSQL')) $ADsql);";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$calls_in_queue_count=$row[0];

			### grab the number of emails waiting in queue that could be routed to this agent
			$stmt="SELECT count(*) from vicidial_email_list where status IN('NEW','QUEUE') and (group_id IN('$AccampSQL'));";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$emails_in_queue_count=$row[0];

			$total_in_queue_count = ($calls_in_queue_count + $emails_in_queue_count);

			$result = _QXZ("SUCCESS");
			$result_reason = _QXZ("SUCCESS: calls_in_queue_count") . " - " . $total_in_queue_count;
			echo "$result_reason";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - calls_in_queue_count
################################################################################





################################################################################
### BEGIN - force_fronter_leave_3way - will send a command to fronter agent to leave-3way call that executing agent is on
################################################################################
if ($function == 'force_fronter_leave_3way')
	{
	if ( ( ($value!='LOCAL_ONLY') and ($value!='LOCAL_AND_CCC') and ($value!='CCC_REMOTE') ) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("force_fronter_leave_3way not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "SELECT count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ( ($row[0] > 0) or ($value == 'CCC_REMOTE') )
			{
			$Alead_id=0;
			### grab the lead_id for this logged-in agent
			$stmt="SELECT lead_id from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$vla_ct = mysqli_num_rows($rslt);
			if ($vla_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$Alead_id = $row[0];
				}

			if ( ($Alead_id > 0) or ( (strlen($lead_id) > 0) and ($value == 'CCC_REMOTE') ) )
				{
				$QUERYlead_id = $Alead_id;
				if ($value == 'CCC_REMOTE')
					{$QUERYlead_id = $lead_id;}

				$other_user='';
				$fronter_found=0;
				### user ID of fronter agent
				$stmt="SELECT user from vicidial_live_agents where user!='$agent_user' and lead_id='$QUERYlead_id' order by last_call_time limit 1;";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$vla_ct = mysqli_num_rows($rslt);
				if ($vla_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$other_user = $row[0];
					$fronter_found++;

					$external_transferconf = "LEAVE_3WAY_CALL---------NO------------$epoch";
					$stmt="UPDATE vicidial_live_agents set external_transferconf='$external_transferconf' where user='$other_user';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$affected_rows = mysqli_affected_rows($link);

					$result = _QXZ("SUCCESS");
					$result_reason = _QXZ("SUCCESS: force_fronter_leave_3way SENT")." - $other_user";
					echo "$result_reason";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					if ($value == 'LOCAL_AND_CCC')
						{
						# check for CCC-fronted call on this server
						$stmt="SELECT remote_lead_id,container_id from vicidial_ccc_log where lead_id='$QUERYlead_id';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$vcl_ct = mysqli_num_rows($rslt);
						if ($vcl_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$remote_lead_id =	$row[0];
							$container_id =		$row[1];

							$stmt="SELECT container_entry from vicidial_settings_containers where container_id='$container_id';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$vsc_ct = mysqli_num_rows($rslt);
							if ($vsc_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$container_entry =	$row[0];
								$container_entry = preg_replace("/ccc_lead_info/","force_fronter_leave_3way&value=CCC_REMOTE&lead_id=$remote_lead_id&agent_user=CCC_REMOTE",$container_entry);
								$container_entry = preg_replace("/vicidial\/non_agent_|admin\/non_agent_/","agc/",$container_entry);

								$SQL_log = "$container_entry";
								$SQL_log = preg_replace('/;/','',$SQL_log);
								$SQL_log = addslashes($SQL_log);
								$stmt = "INSERT INTO vicidial_url_log SET uniqueid='$uniqueid',url_date='$NOW_TIME',url_type='start',url='$SQL_log',url_response='';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$affected_rows = mysqli_affected_rows($link);
								$url_id = mysqli_insert_id($link);

								$URLstart_sec = date("U");

								### grab the call_start_url ###
								if ($DB > 0) {echo "$container_entry<BR>\n";}
								$SCUfile = file("$container_entry");
								if ( !($SCUfile) )
									{
									$error_array = error_get_last();
									$error_type = $error_array["type"];
									$error_message = $error_array["message"];
									$error_line = $error_array["line"];
									$error_file = $error_array["file"];
									}

								if ($DB > 0) {echo "$SCUfile[0]<BR>\n";}

								### update url log entry
								$URLend_sec = date("U");
								$URLdiff_sec = ($URLend_sec - $URLstart_sec);
								if ($SCUfile)
									{
									$SCUfile_contents = implode("", $SCUfile);
									$SCUfile_contents = preg_replace('/;/','',$SCUfile_contents);
									$SCUfile_contents = addslashes($SCUfile_contents);
									$fronter_found++;

									$result = _QXZ("SUCCESS");
									$result_reason = _QXZ("force_fronter_leave_3way command sent over CCC")." - $container_id";
									echo "$result: $result_reason\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								else
									{
									$SCUfile_contents = "PHP ERROR: Type=$error_type - Message=$error_message - Line=$error_line - File=$error_file";
									}
								$stmt = "UPDATE vicidial_url_log SET response_sec='$URLdiff_sec',url_response='$SCUfile_contents' where url_log_id='$url_id';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$affected_rows = mysqli_affected_rows($link);
								}
							}
						}
					}
				if ($fronter_found < 1)
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("no fronter found");
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user is not on a phone call");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - force_fronter_leave_3way
################################################################################





################################################################################
### BEGIN - force_fronter_audio_stop - will send a command to fronter session to stop playing soundboard audio
################################################################################
if ($function == 'force_fronter_audio_stop')
	{
	if ( ( ($value!='LOCAL_ONLY') and ($value!='LOCAL_AND_CCC') and ($value!='CCC_REMOTE') ) or ( (strlen($agent_user)<1) and (strlen($alt_user)<2) ) )
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("force_fronter_audio_stop not valid");
		echo "$result: $result_reason - $value|$agent_user\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if (strlen($alt_user)>1)
			{
			$stmt = "select count(*) from vicidial_users where custom_three='$alt_user';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			if ($row[0] > 0)
				{
				$stmt = "select user from vicidial_users where custom_three='$alt_user' order by user;";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$agent_user = $row[0];
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no user found");
				echo "$result: $result_reason - $alt_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		$stmt = "SELECT count(*) from vicidial_live_agents where user='$agent_user';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ( ($row[0] > 0) or ($value == 'CCC_REMOTE') )
			{
			$Alead_id=0;
			### grab the lead_id for this logged-in agent
			$stmt="SELECT lead_id from vicidial_live_agents where user='$agent_user';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$vla_ct = mysqli_num_rows($rslt);
			if ($vla_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$Alead_id = $row[0];
				}

			if ( ($Alead_id > 0) or ( (strlen($lead_id) > 0) and ($value == 'CCC_REMOTE') ) )
				{
				$QUERYlead_id = $Alead_id;
				if ($value == 'CCC_REMOTE')
					{$QUERYlead_id = $lead_id;}

				$other_agent='';
				$fronter_found=0;
				$VLAconf_exten='';
				$VLAserver_ip='';
				### user ID of fronter agent
				$stmt="SELECT user from vicidial_live_agents where user!='$agent_user' and lead_id='$QUERYlead_id' order by last_call_time limit 1;";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$vla_ct = mysqli_num_rows($rslt);
				if ($vla_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$other_agent =	$row[0];
					$fronter_found++;
					}
				else
					{
					### search recent sessions on this cluster for this lead ID, if the fronter has left the 3way call
					$stmt="SELECT user,conf_exten,server_ip from vicidial_sessions_recent where user!='$agent_user' and lead_id='$QUERYlead_id' and call_date > DATE_SUB(NOW(), INTERVAL 5 MINUTE) order by call_date desc limit 1;";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$vla_ct = mysqli_num_rows($rslt);
					if ($vla_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$other_agent =		$row[0];
						$VLAconf_exten =	$row[1];
						$VLAserver_ip =		$row[2];
						$fronter_found++;
						}
					}
				if ($fronter_found > 0)
					{
					if ( (strlen($VLAserver_ip) < 5) and (strlen($VLAconf_exten) < 1) )
						{
						$stmt = "select conf_exten,server_ip from vicidial_live_agents where user='$other_agent';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$rl_ct = mysqli_num_rows($rslt);
						if ($rl_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$VLAconf_exten =	$row[0];
							$VLAserver_ip =		$row[1];
							}
						}
					if ( (strlen($VLAconf_exten) > 5) and (strlen($VLAserver_ip) > 5) )
						{
						$valueCIDfull = "\"STOP\" <473782158521111>";

						$stmt = "SELECT channel from live_channels where extension='$VLAconf_exten' and server_ip='$VLAserver_ip' and channel LIKE \"IAX2/ASTplay%\";";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$rl_ct = mysqli_num_rows($rslt);

						if ($rl_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$VLAchannel =	$row[0];
							$VLAchannel_inc =	$VLAchannel;
							$VLAchannel_inc = preg_replace("/IAX2\/ASTplay-/",'',$VLAchannel_inc);

							$stmtX="INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$VLAserver_ip','','Hangup','PLAYHU$ENTRYdate','Channel: $VLAchannel','','','','','','','','','');";
							}
						else
							{
							$result = _QXZ("ERROR");
							$data = "$VLAconf_exten|$VLAserver_ip|$stage";
							$result_reason = _QXZ("force_fronter_audio_stop error - no audio playing in other agent session");
							echo "$result: $result_reason - $data|$agent_user|$other_agent\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						if ($format=='debug') {echo "\n<!-- $stmtX -->";}
						$rslt=mysql_to_mysqli($stmtX, $link);
						$result = _QXZ("SUCCESS");
						$data = "$value|$stage";
						$result_reason = _QXZ("force_fronter_audio_stop function sent");
						echo "$result: $result_reason - $data|$agent_user|$other_agent\n";
						$data = "$epoch";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = _QXZ("ERROR");
						$data = "$stage";
						$result_reason = _QXZ("force_fronter_audio_stop error - no fronter session found");
						echo "$result: $result_reason - $data|$agent_user|$other_agent\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				else
					{
					if ($value == 'LOCAL_AND_CCC')
						{
						# check for CCC-fronted call on this server
						$stmt="SELECT remote_lead_id,container_id from vicidial_ccc_log where lead_id='$QUERYlead_id';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$vcl_ct = mysqli_num_rows($rslt);
						if ($vcl_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$remote_lead_id =	$row[0];
							$container_id =		$row[1];

							$stmt="SELECT container_entry from vicidial_settings_containers where container_id='$container_id';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$vsc_ct = mysqli_num_rows($rslt);
							if ($vsc_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$container_entry =	$row[0];
								$container_entry = preg_replace("/ccc_lead_info/","force_fronter_audio_stop&value=CCC_REMOTE&lead_id=$remote_lead_id&agent_user=CCC_REMOTE",$container_entry);
								$container_entry = preg_replace("/vicidial\/non_agent_|admin\/non_agent_/","agc/",$container_entry);

								$SQL_log = "$container_entry";
								$SQL_log = preg_replace('/;/','',$SQL_log);
								$SQL_log = addslashes($SQL_log);
								$stmt = "INSERT INTO vicidial_url_log SET uniqueid='$uniqueid',url_date='$NOW_TIME',url_type='start',url='$SQL_log',url_response='';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$affected_rows = mysqli_affected_rows($link);
								$url_id = mysqli_insert_id($link);

								$URLstart_sec = date("U");

								### grab the call_start_url ###
								if ($DB > 0) {echo "$container_entry<BR>\n";}
								$SCUfile = file("$container_entry");
								if ( !($SCUfile) )
									{
									$error_array = error_get_last();
									$error_type = $error_array["type"];
									$error_message = $error_array["message"];
									$error_line = $error_array["line"];
									$error_file = $error_array["file"];
									}

								if ($DB > 0) {echo "$SCUfile[0]<BR>\n";}

								### update url log entry
								$URLend_sec = date("U");
								$URLdiff_sec = ($URLend_sec - $URLstart_sec);
								if ($SCUfile)
									{
									$SCUfile_contents = implode("", $SCUfile);
									$SCUfile_contents = preg_replace('/;/','',$SCUfile_contents);
									$SCUfile_contents = addslashes($SCUfile_contents);
									$fronter_found++;

									$result = _QXZ("SUCCESS");
									$result_reason = _QXZ("force_fronter_audio_stop command sent over CCC")." - $container_id";
									echo "$result: $result_reason\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								else
									{
									$SCUfile_contents = "PHP ERROR: Type=$error_type - Message=$error_message - Line=$error_line - File=$error_file";
									}
								$stmt = "UPDATE vicidial_url_log SET response_sec='$URLdiff_sec',url_response='$SCUfile_contents' where url_log_id='$url_id';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$affected_rows = mysqli_affected_rows($link);
								}
							}
						}
					}
				if ($fronter_found < 1)
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("no fronter found");
					echo "$result: $result_reason - $agent_user\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("agent_user is not on a phone call");
				echo "$result: $result_reason - $agent_user\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("agent_user is not logged in");
			echo "$result: $result_reason - $agent_user\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - force_fronter_audio_stop
################################################################################


################################################################################
### BEGIN - send_notification - will send text alerts and/or confetti effects to agent interface
################################################################################
if ($function == 'send_notification')
	{
	if ($SSagent_notifications < 1)
		{
		$result = _QXZ("ERROR");
		$result_reason = _QXZ("Agent API Notifications are disabled on this system");
		echo "$result: $result_reason - $SSagent_notifications\n";
		api_log($link,$api_logging,$api_script,$user,$recipient,$function,$recipient_type,$result,$result_reason,$source,$data);
		}
	else
		{
		if ( (!preg_match("/ $function /",$VUapi_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$VUapi_allowed_functions)) )
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION");
			echo "$result: $result_reason - $value|$user|$function|$VUuser_group\n";
			api_log($link,$api_logging,$api_script,$user,$recipient,$function,$recipient_type,$result,$result_reason,$source,$data);
			exit;
			}

		$valid_request=0;
		$valid_recipients=array("USER", "USER_GROUP", "CAMPAIGN");

		# &user=6666&pass=1234&source=test&function=send_notification&recipient=6666&recipient_type=USER&show_confetti=1
		if ($recipient && !in_array($recipient_type, $valid_recipients))
			{
			$old_recipient_type=$recipient_type;
			$recipient_type="";
			$rt_stmt="select 'USER', 1 as priority from vicidial_users where user='$recipient' UNION select 'USER_GROUP', 2 from vicidial_user_groups where user_group='$recipient' UNION select 'CAMPAIGN', 3 as priority from vicidial_campaigns where campaign_id='$recipient' order by priority limit 1";
			$rt_rslt=mysql_to_mysqli($rt_stmt, $link);
			echo "Invalid recipient_type ($old_recipient_type), ";
			if (mysqli_num_rows($rt_rslt)>0)
				{
				$rt_row=mysqli_fetch_row($rt_rslt);
				$recipient_type=$rt_row[0];
				echo "NOTICE: found $recipient as $recipient_type<BR>\n";
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("no matching recipient found in users, user_groups, or campaigns");
				echo "$result: $result_reason - $recipient\n";
				api_log($link,$api_logging,$api_script,$user,$recipient,$function,$recipient_type,$result,$result_reason,$source,$data);
				exit;
				}
			if ($DBX) {echo "$rt_stmt";}
			}

		if ($recipient && $recipient_type)
			{
			### Check that recipient exists in respective table
			switch($recipient_type) 
				{
				case "CAMPAIGN":
					$tbl_name="vicidial_campaigns";
					$col_name="campaign_id";
					break;
				case "USER_GROUP":
					$tbl_name="vicidial_user_groups";
					$col_name="user_group";
					break;
				case "USER":
					$tbl_name="vicidial_users";
					$col_name="user";
					break;
				}
			$exist_stmt="select $col_name from $tbl_name where $col_name='$recipient'";
			if ($DB) {echo $exist_stmt."<BR>\n";}
			$exist_rslt=mysql_to_mysqli($exist_stmt, $link);
			$exist_ct = mysqli_num_rows($exist_rslt);
			if ($exist_ct==0)
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("$recipient DOES NOT EXIST in $tbl_name");
				echo "$result: $result_reason - $value|$recipient\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}

			# For campaign alerts, verify the user has access to said campaign
			if($recipient_type=="CAMPAIGN" || $recipient_type=="USER_GROUP")
				{
				$stmt="SELECT allowed_campaigns, admin_viewable_groups from vicidial_user_groups where user_group='$VUuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$ss_conf_ct = mysqli_num_rows($rslt);
				if ($ss_conf_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$LOGallowed_campaigns =			$row[0];
					$LOGadmin_viewable_groups =			$row[1];
					if ($recipient_type=="CAMPAIGN" && (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) && !preg_match("/ $recipient /i", $LOGallowed_campaigns) )
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("ACCESS TO CAMPAIGN $recipient NOT ALLOWED FOR USER $user ($LOGallowed_campaigns)");
						echo "$result: $result_reason - $value|$recipient\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else if ($recipient_type=="USER_GROUP" && (!preg_match('/\-\-\-ALL/i', $LOGadmin_viewable_groups)) && !preg_match("/ $recipient /i", $LOGadmin_viewable_groups) )
						{
						$result = _QXZ("ERROR");
						$result_reason = _QXZ("ACCESS TO USER GROUP $recipient NOT ALLOWED FOR USER $user");
						echo "$result: $result_reason - $value|$recipient\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				else
					{
					$result = _QXZ("ERROR");
					$result_reason = _QXZ("user_group DOES NOT EXIST");
					echo "$result: $result_reason - $value|$VUuser_group\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}

			if(!$notification_date) {$notification_date=date("Y-m-d H:i:s");}
			if (preg_match('/Y/i', $notification_retry)) 
				{
				$notification_retry="Y";
				} 
			else 
				{
				$notification_retry="N";
				}
			if (!$text_size) {$text_size="12";}
			if (!$text_font) {$text_font="Arial";}
			if (!$text_weight) {$text_weight="bold";}
			if (!$text_color) {$text_color="black";}

			if (preg_match('/^[0-9A-F]{3,6}$/', $text_color)) {$text_color="#".$text_color;}

			if(!preg_match('/[0-9]{2,4}\-[0-9]{1,2}\-[0-9]{1,2}\s?[0-9]{1,2}\:[0-9]{1,2}(\:[0-9]{1,2})?/', $notification_date))
				{
				echo "NOTICE: Invalid notification date $notification_date, changing to now";
				$notification_date=date("Y-m-d H:i:s");
				}

			$notification_status='QUEUED';
			$SQL_columns="recipient, recipient_type, notification_date, notification_retry, notification_status, ";
			$SQL_values="'$recipient', '$recipient_type', '$notification_date', '$notification_retry', '$notification_status', ";

			if(strlen($notification_text)>0)
				{
				$SQL_columns.="notification_text, text_size, text_font, text_weight, text_color, ";
				$SQL_values.="'$notification_text', '$text_size', '$text_font', '$text_weight', '$text_color', ";
				$valid_request=1;
				}

			## Confetti 
			if ($show_confetti=="Y")
				{
				# Check if any of the three confetti settings was NOT passed, meaning the container should be checked.
				if (!$duration || !$maxParticleCount || !$particleSpeed)
					{
					$confetti_stmt="select * from vicidial_settings_containers where container_id='CONFETTI_SETTINGS'";
					if ($DBX) {echo $confetti_stmt."<BR>\n";}
					$confetti_rslt=mysql_to_mysqli($confetti_stmt, $link);

					if (mysqli_num_rows($confetti_rslt)>0)
						{
						$confetti_row=mysqli_fetch_array($confetti_rslt);
						$container_entry=explode("\n", $confetti_row['container_entry']);

						for ($q=0; $q<count($container_entry); $q++)
							{
							if (!preg_match('/^\;/', $container_entry[$q]) && preg_match('/\s\=\>\s/', $container_entry[$q]))
								{
								$container_setting=explode(" => ", trim($container_entry[$q]));
								$setting_name=$container_setting[0];
								$setting_value=$container_setting[1];
								if ($setting_name=="duration" || $setting_name=="maxParticleCount" || $setting_name=="particleSpeed") 
									{
									if (!$$setting_name) {$$setting_name=$setting_value;}
									}
								}
							}
						}
					}

				# Set any remaining missing confetti settings that aren't already set
				if (!$duration) {$duration="2";} # seconds
				if (!$maxParticleCount) {$maxParticleCount="2350";}
				if (!$particleSpeed) {$particleSpeed="2";}

				$SQL_columns.="show_confetti, confetti_options, ";
				$SQL_values.="'$show_confetti', '$duration,$maxParticleCount,$particleSpeed', ";

				$valid_request=1;
				}

			if ($valid_request)
				{
				$SQL_columns=preg_replace('/, $/', "", $SQL_columns);
				$SQL_values=preg_replace('/, $/', "", $SQL_values);
				$ins_stmt="INSERT INTO vicidial_agent_notifications($SQL_columns) VALUES($SQL_values)";
				if ($DB) {echo $ins_stmt."<BR>\n";}
				$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
				$notification_id = mysqli_insert_id($link);

				$result = _QXZ("SUCCESS");
				$result_reason = _QXZ("notification queued");
				echo "$result: $result_reason - $recipient|$recipient_type|$notification_id\n";
				api_log($link,$api_logging,$api_script,$user,$recipient,$function,$recipient_type,$result,$result_reason,$source,$data);
				}
			else
				{
				$result = _QXZ("ERROR");
				$result_reason = _QXZ("invalid request");
				echo "$result: $result_reason - $recipient|$recipient_type\n";
				api_log($link,$api_logging,$api_script,$user,$recipient,$function,$recipient_type,$result,$result_reason,$source,$data);
				}
			}
		else
			{
			$result = _QXZ("ERROR");
			$result_reason = _QXZ("Missing recipient or recipient_type");
			echo "$result: $result_reason - $recipient|$recipient_type\n";
			api_log($link,$api_logging,$api_script,$user,$recipient,$function,$recipient_type,$result,$result_reason,$source,$data);
			}
		}
	}
################################################################################
### END - send_notification
################################################################################


################################################################################
### BEGIN - optional "close window" link
################################################################################
if ($close_window_link > 0) 
	{
	$close_this_window_text = 'Close This Window';
	if ($language=='es')
		{$close_this_window_text = 'Cerrar esta ventana';}
	echo "\n<a href=\"javascript:window.opener='x';window.close();\">$close_this_window_text</a>\n";
	}
################################################################################
### END - optional "close window" link
################################################################################





if ($format=='debug') 
	{
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $StarTtime);
	echo "\n<!-- script runtime: $RUNtime seconds -->";
	echo "\n</body>\n</html>\n";
	}
	
exit; 



##### FUNCTIONS #####

##### Logging #####
function api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data)
	{
	if ($api_logging > 0)
		{
		global $startMS, $query_string, $ip;

		$CL=':';
		$script_name = getenv("SCRIPT_NAME");
		$server_name = getenv("SERVER_NAME");
		$server_port = getenv("SERVER_PORT");
		if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
		  else {$HTTPprotocol = 'http://';}
		if (($server_port == '80') or ($server_port == '443') ) {$server_port='';}
		else {$server_port = "$CL$server_port";}
		$apiPAGE = "$HTTPprotocol$server_name$server_port$script_name";
		$apiURL = $apiPAGE . '?' . $query_string;

		$endMS = microtime();
		$startMSary = explode(" ",$startMS);
		$endMSary = explode(" ",$endMS);
		$runS = ($endMSary[0] - $startMSary[0]);
		$runM = ($endMSary[1] - $startMSary[1]);
		$TOTALrun = ($runS + $runM);

		$VULhostname = php_uname('n');
		$VULservername = $_SERVER['SERVER_NAME'];
		if (strlen($VULhostname)<1) {$VULhostname='X';}
		if (strlen($VULservername)<1) {$VULservername='X';}

		$stmt="SELECT webserver_id FROM vicidial_webservers where webserver='$VULservername' and hostname='$VULhostname' LIMIT 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$webserver_id_ct = mysqli_num_rows($rslt);
		if ($webserver_id_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$webserver_id = $row[0];
			}
		else
			{
			##### insert webserver entry
			$stmt="INSERT INTO vicidial_webservers (webserver,hostname) values('$VULservername','$VULhostname');";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);
			$webserver_id = mysqli_insert_id($link);
			}

		$stmt="SELECT url_id FROM vicidial_urls where url='$apiPAGE' LIMIT 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$url_id_ct = mysqli_num_rows($rslt);
		if ($url_id_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$url_id = $row[0];
			}
		else
			{
			##### insert url entry
			$stmt="INSERT INTO vicidial_urls (url) values('$apiPAGE');";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);
			$url_id = mysqli_insert_id($link);
			}

		$NOW_TIME = date("Y-m-d H:i:s");
		$data = preg_replace("/\"/","'",$data);
		$stmt="INSERT INTO vicidial_api_log set user='$user',agent_user='$agent_user',function='$function',value='$value',result=\"$result\",result_reason='$result_reason',source='$source',data=\"$data\",api_date='$NOW_TIME',api_script='$api_script',run_time='$TOTALrun',webserver='$webserver_id',api_url='$url_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$ALaffected_rows = mysqli_affected_rows($link);
		$api_id = mysqli_insert_id($link);

		if ($ALaffected_rows > 0)
			{
			$stmt="INSERT INTO vicidial_api_urls set api_id='$api_id',api_date=NOW(),remote_ip='$ip',url='$apiURL';";
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		}
	return 1;
	}

?>
