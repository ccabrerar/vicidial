<?php
# non_agent_api.php
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed as an API(Application Programming Interface) to allow
# other programs to interact with all non-agent-screen VICIDIAL functions
#
# required variables:
#  - $user
#  - $pass
#  - $function - ('add_lead','update_lead','version','sounds_list','moh_list','vm_list','blind_monitor','agent_ingroup_info','add_list',etc...)
#  - $source - ('vtiger','webform','adminweb')
#  - $format - ('text','debug')
# optional callback variables for add_lead/update_lead
#  - $callback -	('Y,'N','REMOVE')
#  - $callback_status -	('CALLBK','CBXYZ',...)
#  - $callback_datetime -	('YYYY-MM-DD+HH:MM:SS','NOW','273DAYS')
#  - $callback_type -	('USERONLY','ANYONE')
#  - $callback_user -	('6666','1001',...)
#  - $callback_comments - ('Comments go here',...)

# CHANGELOG:
# 80724-0021 - First build of script
# 80801-0047 - Added gmt lookup and hopper insert time validation
# 80909-2012 - Added support for campaign-specific DNC lists
# 80910-0020 - Added support for multi-alt-phones, added version function
# 90118-1056 - Added logging of API functions
# 90428-0209 - Added blind_monitor function
# 90508-0642 - Changed to PHP long tags
# 90514-0602 - Added sounds_list function
# 90522-0506 - Security fix
# 90530-0946 - Added QueueMetrics blind monitoring option
# 90721-1428 - Added rank and owner as vicidial_list fields
# 90904-1535 - Added moh_list musiconhold list
# 90916-2342 - Added vm_list voicemail list
# 91026-1059 - Added AREACODE DNC option
# 91203-1140 - Added agent_ingroup_info feature
# 91216-0331 - Added duplication check features to add_lead function
# 100118-0543 - Added new Australian and New Zealand DST schemes (FSO-FSA and LSS-FSA)
# 100704-1148 - Added custom fields inserts to the add_lead function
# 100712-1416 - Added entry_list_id field to vicidial_list to preserve link to custom fields if any
# 100718-0245 - Added update_lead function to update existing leads
# 100723-1333 - Added no_update option to the update_lead function
# 100728-1952 - Added delete_lead option to the update_lead function
# 100924-1403 - Added called_count as an update_lead option
# 101111-1536 - Added vicidial_hopper.source to vicidial_hopper inserts
# 101117-1104 - Added callback and custom field entry delete to delete_lead option
# 101206-2126 - Added recording_lookup and did_log_export functions
# 110127-2245 - Added add_user and add_phone functions
# 110303-2122 - Added information on agent-on-hook phone to real-time report popup
# 110306-1044 - Added add_list and update_list functions
# 110316-2035 - Added reset_time variable and NAMEPHONE dup search
# 110404-1356 - Added uniqueid search parameter to recording_lookup function
# 110409-0822 - Added run_time logging of API functions
# 110424-0854 - Added option for time zone code lookups using owner field
# 110529-1220 - Added time zone information output to version function
# 110614-0726 - Added reset_lead option to update_lead function(issue #502)
# 110705-1928 - Added options for USACAN 4th digit prefix(no 0 or 1) and valid areacode filtering to add_lead
# 110821-2318 - Added update_phone, add_phone_alias, update_phone_alias functions
# 110928-2110 - Added callback options to add_lead and update_lead
# 111106-0959 - Added user_group restrictions to some functions
# 120127-1331 - Small fix for plus replacement in custom fields strings for add/update_lead functions
# 120210-1215 - Small change for hopper adding vendor_lead_code
# 120213-1613 - Added optional logging of all non-admin.php requests, enabled in options.php
# 120315-1537 - Added filter for single-quotes and backslashes on custom field data
# 120326-1317 - Added agent_stats_export function
# 120810-0859 - Added add_group_alias function, altered agent_stats_export function
# 120831-1529 - Added vicidial_dial_log outbound call logging
# 120912-2042 - Added user_group_status and in_group_status functions
# 120913-1255 - Added update_log_entry function
# 121116-1938 - Added state call time restrictions to add_lead hopper insert function
# 121125-2210 - Added Other Campaign DNC option and list expiration date option
# 130328-0949 - Added update_phone_number option to update_lead function, issue #653
# 130405-1539 - Added agent_status function
# 130414-0311 - Added report logging for blind_monitor function
# 130420-1938 - Added NANPA prefix validation and timezone options
# 130614-0907 - Finalized changing of all ereg instances to preg
#             - Added pause code to output of agent_status function
# 130617-2232 - Added real-time sub-statuses to output of agent_status function
#             - Added user authentication process to eliminate brute force attacks
# 130705-1725 - Changes for encrypted password compatibility
# 130831-0825 - Changed to mysqli PHP functions
# 140107-2143 - Added webserver and url logging
# 140124-1057 - Added callid_info function
# 140206-1205 - Added update_user function
# 140211-1056 - Added server_refresh function
# 140214-1540 - Added check_phone_number function
# 140331-2119 - Converted division calculations to use MathZDC function
# 140403-2024 - Added camp_rg_only option to update_user function
# 140418-1553 - Added preview_lead_id for agent_status
# 140617-2029 - Added vicidial_users wrapup_seconds_override option
# 140812-0939 - Added phone_number and vendor_lead_code to agent_status function output
# 150123-1611 - Fixed issue with list local call times and add_lead
# 150217-1404 - archive deleted callbacks to vicidial_callbacks_archive table
# 150309-0250 - Added ability to use urlencoded web form addresses
# 150313-0818 - Allow for single quotes in vicidial_list and custom data fields
# 150428-1720 - Added web_form_address_three to add_list/update_list functions
# 150430-0644 - Added API allowed function restrictions and allowed list restrictions
# 150512-2028 - Added filtering of hash sign on some input variables, Issue #851
# 150516-1138 - Fixed conflict with functions.php
# 150603-1528 - Fixed format issue in recording_lookup
# 150730-2022 - Added option to set entry_list_id
# 150804-0948 - Added WHISPER option for blind_monitor function
# 150808-1438 - Added compatibility for custom fields data option
# 151226-0954 - Added session_id(conf_exten) field to output of agent_status, and added logged_in_agents function
# 160104-1229 - Added detection of dead chats to a few functions
# 160211-1232 - Fixed issue with blind monitoring, Issue #924
# 160603-1041 - Added update_campaign function
# 160709-2233 - Added lead_field_info function
# 160824-0802 - Fixed issue with allowed lists feature
# 161020-1042 - Added lookup_state option to add_lead, added 10-digit validation if usacan_areacode_check enabled
# 170205-2022 - Added phone_number_log function
# 170209-1149 - Added URL and IP logging
# 170219-1520 - Added 90-day duplicate check option
# 170223-0743 - Added QXZ translation to admin web user functions text
# 170301-1649 - Added validation that sounds web dir exists to sounds_list function
# 170409-1603 - Added IP List validation code
# 170423-0800 - Added force_entry_list_id option for update_lead
# 170508-1048 - Added blind_monitor logging
# 170527-2254 - Fix for rare inbound logging issue #1017
# 170601-0747 - Added add_to_hopper options to update_lead function
# 170609-1107 - Added ccc_lead_info function
# 170615-0006 - Added DIAL status for manual dial agent calls that have not been answered, to 4 functions
# 170713-2312 - Fix for issue #1028
# 170815-1315 - Added HTTP error code 418
# 171114-1015 - Added ability for update_lead function insert_if_not_found leads to be inserted into the hopper
# 171129-0751 - Fixed issue with duplicate custom fields
# 171229-1602 - Added lead_status_search function
# 180109-1313 - Added call_status_stats function
# 180208-1655 - Added call_dispo_report function
# 180301-2301 - Added GET-AND-POST URL logging
# 180331-1716 - Added options to update_campaign, add_user, update_phone. Added function update_did
# 180529-1102 - Fix for QM live monitoring
# 180916-1059 - Added check for daily list reset limit
# 181214-1606 - Added lead_callback_info function
# 190313-0710 - Fix for update_lead custom fields issue #1134
# 190325-1055 - Added agent_campaigns function
# 190628-1504 - Added update_cid_group_entry function
# 190703-0858 - Added custom_fields_copy option to add_list function
# 190930-1629 - Added list_description field to add_list and update_list functions
# 191107-1143 - Added list_info function
# 191113-1758 - Added add_dnc_phone and add_fpg_phone functions
# 191114-1637 - Added remove_from_hopper option to update_lead function
# 200331-1207 - Added list_custom_fields function
# 200418-0837 - Added custom_copy_method option for the add_list function, also added this feature to update_list
# 200423-0356 - Fix for update_lead --BLANK-- issue
# 200502-0858 - Fix for update_lead --BLANK-- issue with custom fields
# 200508-1503 - Fix for PHP7 issues
# 200525-0118 - Fix for middle_initial --BLANK--
# 200606-0938 - Added more settings to add_list & update_list
# 200610-1447 - Added duration option to recording_lookup function
# 200622-1609 - Added more options to add_phone and update_phone functions
# 200815-1025 - Added campaigns_list & hopper_list functions
# 200824-2330 - Added search_method BLOCK option for hopper_list function
# 201002-1545 - Added extension as recording lookup option, Allowed for secure sounds_web_server setting
# 201106-1654 - Added campaign_id option to agent_stats_export function
# 201113-0713 - Added delete_did option to update_did function, Issue #1242
# 201201-1625 - Added group_by_campaign option to agent_stats_export function
# 201214-1547 - Fixes for PHP8 compatibility
# 210113-1714 - Added copy_user function
# 210209-2014 - Added add_did function
# 210210-1627 - Added duplicate check with more X-day options for add_lead function
# 210216-1415 - Added list_exists_check option for add_lead function
# 210217-1454 - Added menu_id option for add_did and update_did functions
# 210227-1116 - Added xferconf number options to update_campaign, add_list & update_list functions
# 210303-1802 - Added list_exists_check option for update_lead function
# 210316-2207 - Added lead_all_info function
# 210319-1720 - Added special 'xDAYS' value option for callback_datetime
# 210320-2127 - Added 'custom_fields_add' option for update_list function
# 210322-1218 - Added display of bad wav file formats on sounds_list function
# 210325-2042 - Added more data output fields to agent_stats_export function
# 210328-2140 - Added more variable filtering
# 210329-2016 - Fixed for consistent custom fields values filtering
# 210330-1633 - Added ability to use custom_fields_copy on update_list on a deleted list_id
# 210401-1058 - Added 'custom_fields_update' option for update_list function
# 210402-1102 - Added 'custom_fields_delete' option for update_list function
# 210406-1047 - Added 'dialable_count' option to list_info function
# 210517-1850 - Added phone_code as modifiable field in update_lead function
# 210611-1610 - Added more variables for add_did/update_did functions
# 210618-1000 - Added CORS support
# 210625-1421 - Added BARGESWAP option to blind_monitor function
# 210701-2050 - Added ingroup_rank/ingroup_grade options to the update_user function
# 210812-2640 - Added update of live agent records for ingroup_rank/ingroup_grade in the update_user function
# 210823-0918 - Fix for security issue
# 210917-1615 - Added batch_update_lead function
# 211027-0845 - Added lead_search function
# 211107-1556 - Added optional phone_number search for lead_all_info function
# 211118-1946 - Added 'delete_cf_data' setting to the update_lead function
# 211217-0733 - Fixes for PHP8, issue #1341
# 220120-0914 - Added download_invalid_files user function for audio store file listings
# 220126-2116 - Added dispo_call_url and alt URL options for update_campaign. Added 'update_alt_url' function
# 220223-0838 - Added allow_web_debug system setting
# 220307-0937 - Added 'update_presets' function
# 220310-0825 - Added DELETE action to the 'update_presets' function
# 220312-0944 - Added vicidial_dial_cid_log logging
# 220519-2206 - Small fix for 'update_lead' delete_lead feature
# 220902-0823 - Added dial_status_add/dial_status_remove options to update_campaign function
# 220920-0814 - Added more "XDAY" options to add_lead duplicate checks
# 221108-1849 - Added include_ip option for agent_status function
# 230118-0833 - Added ingroup_list and callmenu_list functions
# 230122-1821 - Added reset_password option to update_user function
# 230308-1758 - Fix for update_lead list-restrict phone number update issue
# 230614-0828 - Added container_list function
# 230721-1753 - Added insert for daily RT monitoring
# 231109-0933 - Added archived_lead option to multiple functions
# 231109-2022 - Added lead_dearchive function
# 240101-0920 - Added DUPPHONEALT... options for duplicate_check within add_lead function 
# 240105-1009 - Added delete_fpg_phone function
# 240120-0840 - Added list_order,list_order_randomize,list_order_secondary optional fields to update_campaign function
# 240217-0907 - Added more missing vicidial_users fields from copy function
# 240302-0804 - Added delete_dnc_phone function
# 240420-2236 - Added ConfBridge code
# 240703-1657 - Added update_remote_agent function
# 240718-0942 - Fixes for inconsistencies in documentation and permissions for some functions
# 240718-1716 - Added fields to campaigns_list function output
# 240730-1832 - Changes for PHP8 compatibility, Added copy_did function
# 240824-1626 - Added user_details function
# 241004-1518 - Added webform_one-three variables to the update_campaign function
#

$version = '2.14-194';
$build = '241004-1518';
$php_script='non_agent_api.php';
$api_url_log = 0;
$camp_lead_order_random=1;

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

### If you have globals turned off uncomment these lines
if (isset($_GET["user"]))						{$user=$_GET["user"];}
	elseif (isset($_POST["user"]))				{$user=$_POST["user"];}
if (isset($_GET["pass"]))						{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))				{$pass=$_POST["pass"];}
if (isset($_GET["function"]))					{$function=$_GET["function"];}
	elseif (isset($_POST["function"]))			{$function=$_POST["function"];}
if (isset($_GET["format"]))						{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))			{$format=$_POST["format"];}
if (isset($_GET["list_id"]))					{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))			{$list_id=$_POST["list_id"];}
if (isset($_GET["phone_code"]))					{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))		{$phone_code=$_POST["phone_code"];}
if (isset($_GET["update_phone_number"]))		  {$update_phone_number=$_GET["update_phone_number"];}
	elseif (isset($_POST["update_phone_number"])) {$update_phone_number=$_POST["update_phone_number"];}
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
if (isset($_GET["dnc_check"]))					{$dnc_check=$_GET["dnc_check"];}
	elseif (isset($_POST["dnc_check"]))			{$dnc_check=$_POST["dnc_check"];}
if (isset($_GET["campaign_dnc_check"]))				{$campaign_dnc_check=$_GET["campaign_dnc_check"];}
	elseif (isset($_POST["campaign_dnc_check"]))	{$campaign_dnc_check=$_POST["campaign_dnc_check"];}
if (isset($_GET["add_to_hopper"]))				{$add_to_hopper=$_GET["add_to_hopper"];}
	elseif (isset($_POST["add_to_hopper"]))		{$add_to_hopper=$_POST["add_to_hopper"];}
if (isset($_GET["hopper_priority"]))			{$hopper_priority=$_GET["hopper_priority"];}
	elseif (isset($_POST["hopper_priority"]))	{$hopper_priority=$_POST["hopper_priority"];}
if (isset($_GET["hopper_local_call_time_check"]))			{$hopper_local_call_time_check=$_GET["hopper_local_call_time_check"];}
	elseif (isset($_POST["hopper_local_call_time_check"]))	{$hopper_local_call_time_check=$_POST["hopper_local_call_time_check"];}
if (isset($_GET["campaign_id"]))				{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))		{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["multi_alt_phones"]))			{$multi_alt_phones=$_GET["multi_alt_phones"];}
	elseif (isset($_POST["multi_alt_phones"]))	{$multi_alt_phones=$_POST["multi_alt_phones"];}
if (isset($_GET["source"]))						{$source=$_GET["source"];}
	elseif (isset($_POST["source"]))			{$source=$_POST["source"];}
if (isset($_GET["phone_login"]))				{$phone_login=$_GET["phone_login"];}
	elseif (isset($_POST["phone_login"]))		{$phone_login=$_POST["phone_login"];}
if (isset($_GET["session_id"]))					{$session_id=$_GET["session_id"];}
	elseif (isset($_POST["session_id"]))		{$session_id=$_POST["session_id"];}
if (isset($_GET["server_ip"]))					{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))			{$server_ip=$_POST["server_ip"];}
if (isset($_GET["stage"]))						{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))				{$stage=$_POST["stage"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["rank"]))						{$rank=$_GET["rank"];}
	elseif (isset($_POST["rank"]))				{$rank=$_POST["rank"];}
if (isset($_GET["owner"]))						{$owner=$_GET["owner"];}
	elseif (isset($_POST["owner"]))				{$owner=$_POST["owner"];}
if (isset($_GET["agent_user"]))					{$agent_user=$_GET["agent_user"];}
	elseif (isset($_POST["agent_user"]))		{$agent_user=$_POST["agent_user"];}
if (isset($_GET["duplicate_check"]))			{$duplicate_check=$_GET["duplicate_check"];}
	elseif (isset($_POST["duplicate_check"]))	{$duplicate_check=$_POST["duplicate_check"];}
if (isset($_GET["custom_fields"]))				{$custom_fields=$_GET["custom_fields"];}
	elseif (isset($_POST["custom_fields"]))		{$custom_fields=$_POST["custom_fields"];}
if (isset($_GET["search_method"]))				{$search_method=$_GET["search_method"];}
	elseif (isset($_POST["search_method"]))		{$search_method=$_POST["search_method"];}
if (isset($_GET["insert_if_not_found"]))			{$insert_if_not_found=$_GET["insert_if_not_found"];}
	elseif (isset($_POST["insert_if_not_found"]))	{$insert_if_not_found=$_POST["insert_if_not_found"];}
if (isset($_GET["records"]))					{$records=$_GET["records"];}
	elseif (isset($_POST["records"]))			{$records=$_POST["records"];}
if (isset($_GET["search_location"]))			{$search_location=$_GET["search_location"];}
	elseif (isset($_POST["search_location"]))	{$search_location=$_POST["search_location"];}
if (isset($_GET["status"]))						{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))			{$status=$_POST["status"];}
if (isset($_GET["statuses"]))						{$statuses=$_GET["statuses"];}
	elseif (isset($_POST["statuses"]))			{$statuses=$_POST["statuses"];}
if (isset($_GET["categories"]))			{$categories=$_GET["categories"];}
	elseif (isset($_POST["categories"]))	{$categories=$_POST["categories"];}
if (isset($_GET["user_field"]))					{$user_field=$_GET["user_field"];}
	elseif (isset($_POST["user_field"]))		{$user_field=$_POST["user_field"];}
if (isset($_GET["list_id_field"]))				{$list_id_field=$_GET["list_id_field"];}
	elseif (isset($_POST["list_id_field"]))		{$list_id_field=$_POST["list_id_field"];}
if (isset($_GET["lead_id"]))					{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))			{$lead_id=$_POST["lead_id"];}
if (isset($_GET["no_update"]))					{$no_update=$_GET["no_update"];}
	elseif (isset($_POST["no_update"]))			{$no_update=$_POST["no_update"];}
if (isset($_GET["delete_lead"]))				{$delete_lead=$_GET["delete_lead"];}
	elseif (isset($_POST["delete_lead"]))		{$delete_lead=$_POST["delete_lead"];}
if (isset($_GET["called_count"]))				{$called_count=$_GET["called_count"];}
	elseif (isset($_POST["called_count"]))		{$called_count=$_POST["called_count"];}
if (isset($_GET["date"]))						{$date=$_GET["date"];}
	elseif (isset($_POST["date"]))				{$date=$_POST["date"];}
if (isset($_GET["query_date"]))						{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))				{$query_date=$_POST["query_date"];}
if (isset($_GET["query_time"]))						{$query_time=$_GET["query_time"];}
	elseif (isset($_POST["query_time"]))				{$query_time=$_POST["query_time"];}
if (isset($_GET["end_date"]))						{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))				{$end_date=$_POST["end_date"];}
if (isset($_GET["end_time"]))						{$end_time=$_GET["end_time"];}
	elseif (isset($_POST["end_time"]))				{$end_time=$_POST["end_time"];}
if (isset($_GET["header"]))						{$header=$_GET["header"];}
	elseif (isset($_POST["header"]))			{$header=$_POST["header"];}
if (isset($_GET["agent_pass"]))					{$agent_pass=$_GET["agent_pass"];}
	elseif (isset($_POST["agent_pass"]))		{$agent_pass=$_POST["agent_pass"];}
if (isset($_GET["agent_user_level"]))			{$agent_user_level=$_GET["agent_user_level"];}
	elseif (isset($_POST["agent_user_level"]))	{$agent_user_level=$_POST["agent_user_level"];}
if (isset($_GET["agent_full_name"]))			{$agent_full_name=$_GET["agent_full_name"];}
	elseif (isset($_POST["agent_full_name"]))	{$agent_full_name=$_POST["agent_full_name"];}
if (isset($_GET["agent_user_group"]))			{$agent_user_group=$_GET["agent_user_group"];}
	elseif (isset($_POST["agent_user_group"]))	{$agent_user_group=$_POST["agent_user_group"];}
if (isset($_GET["phone_pass"]))				{$phone_pass=$_GET["phone_pass"];}
	elseif (isset($_POST["phone_pass"]))	{$phone_pass=$_POST["phone_pass"];}
if (isset($_GET["hotkeys_active"]))				{$hotkeys_active=$_GET["hotkeys_active"];}
	elseif (isset($_POST["hotkeys_active"]))	{$hotkeys_active=$_POST["hotkeys_active"];}
if (isset($_GET["voicemail_id"]))			{$voicemail_id=$_GET["voicemail_id"];}
	elseif (isset($_POST["voicemail_id"]))	{$voicemail_id=$_POST["voicemail_id"];}
if (isset($_GET["email"]))					{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))			{$email=$_POST["email"];}
if (isset($_GET["custom_one"]))				{$custom_one=$_GET["custom_one"];}
	elseif (isset($_POST["custom_one"]))	{$custom_one=$_POST["custom_one"];}
if (isset($_GET["custom_two"]))				{$custom_two=$_GET["custom_two"];}
	elseif (isset($_POST["custom_two"]))	{$custom_two=$_POST["custom_two"];}
if (isset($_GET["custom_three"]))			{$custom_three=$_GET["custom_three"];}
	elseif (isset($_POST["custom_three"]))	{$custom_three=$_POST["custom_three"];}
if (isset($_GET["custom_four"]))			{$custom_four=$_GET["custom_four"];}
	elseif (isset($_POST["custom_four"]))	{$custom_four=$_POST["custom_four"];}
if (isset($_GET["custom_five"]))			{$custom_five=$_GET["custom_five"];}
	elseif (isset($_POST["custom_five"]))	{$custom_five=$_POST["custom_five"];}
if (isset($_GET["extension"]))			{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))	{$extension=$_POST["extension"];}
if (isset($_GET["dialplan_number"]))			{$dialplan_number=$_GET["dialplan_number"];}
	elseif (isset($_POST["dialplan_number"]))	{$dialplan_number=$_POST["dialplan_number"];}
if (isset($_GET["protocol"]))			{$protocol=$_GET["protocol"];}
	elseif (isset($_POST["protocol"]))	{$protocol=$_POST["protocol"];}
if (isset($_GET["registration_password"]))			{$registration_password=$_GET["registration_password"];}
	elseif (isset($_POST["registration_password"]))	{$registration_password=$_POST["registration_password"];}
if (isset($_GET["phone_full_name"]))			{$phone_full_name=$_GET["phone_full_name"];}
	elseif (isset($_POST["phone_full_name"]))	{$phone_full_name=$_POST["phone_full_name"];}
if (isset($_GET["local_gmt"]))			{$local_gmt=$_GET["local_gmt"];}
	elseif (isset($_POST["local_gmt"]))	{$local_gmt=$_POST["local_gmt"];}
if (isset($_GET["outbound_cid"]))			{$outbound_cid=$_GET["outbound_cid"];}
	elseif (isset($_POST["outbound_cid"]))	{$outbound_cid=$_POST["outbound_cid"];}
if (isset($_GET["phone_context"]))			{$phone_context=$_GET["phone_context"];}
	elseif (isset($_POST["phone_context"]))	{$phone_context=$_POST["phone_context"];}
if (isset($_GET["list_name"]))			{$list_name=$_GET["list_name"];}
	elseif (isset($_POST["list_name"]))	{$list_name=$_POST["list_name"];}
if (isset($_GET["active"]))				{$active=$_GET["active"];}
	elseif (isset($_POST["active"]))	{$active=$_POST["active"];}
if (isset($_GET["script"]))				{$script=$_GET["script"];}
	elseif (isset($_POST["script"]))	{$script=$_POST["script"];}
if (isset($_GET["am_message"]))				{$am_message=$_GET["am_message"];}
	elseif (isset($_POST["am_message"]))	{$am_message=$_POST["am_message"];}
if (isset($_GET["drop_inbound_group"]))				{$drop_inbound_group=$_GET["drop_inbound_group"];}
	elseif (isset($_POST["drop_inbound_group"]))	{$drop_inbound_group=$_POST["drop_inbound_group"];}
if (isset($_GET["web_form_address"]))			{$web_form_address=$_GET["web_form_address"];}
	elseif (isset($_POST["web_form_address"]))	{$web_form_address=$_POST["web_form_address"];}
if (isset($_GET["web_form_address_two"]))			{$web_form_address_two=$_GET["web_form_address_two"];}
	elseif (isset($_POST["web_form_address_two"]))	{$web_form_address_two=$_POST["web_form_address_two"];}
if (isset($_GET["web_form_address_three"]))			{$web_form_address_three=$_GET["web_form_address_three"];}
	elseif (isset($_POST["web_form_address_three"]))	{$web_form_address_three=$_POST["web_form_address_three"];}
if (isset($_GET["reset_list"]))				{$reset_list=$_GET["reset_list"];}
	elseif (isset($_POST["reset_list"]))	{$reset_list=$_POST["reset_list"];}
if (isset($_GET["delete_list"]))			{$delete_list=$_GET["delete_list"];}
	elseif (isset($_POST["delete_list"]))	{$delete_list=$_POST["delete_list"];}
if (isset($_GET["delete_leads"]))			{$delete_leads=$_GET["delete_leads"];}
	elseif (isset($_POST["delete_leads"]))	{$delete_leads=$_POST["delete_leads"];}
if (isset($_GET["reset_time"]))				{$reset_time=$_GET["reset_time"];}
	elseif (isset($_POST["reset_time"]))	{$reset_time=$_POST["reset_time"];}
if (isset($_GET["uniqueid"]))			{$uniqueid=$_GET["uniqueid"];}
	elseif (isset($_POST["uniqueid"]))	{$uniqueid=$_POST["uniqueid"];}
if (isset($_GET["tz_method"]))			{$tz_method=$_GET["tz_method"];}
	elseif (isset($_POST["tz_method"]))	{$tz_method=$_POST["tz_method"];}
if (isset($_GET["reset_lead"]))				{$reset_lead=$_GET["reset_lead"];}
	elseif (isset($_POST["reset_lead"]))	{$reset_lead=$_POST["reset_lead"];}
if (isset($_GET["usacan_areacode_check"]))			{$usacan_areacode_check=$_GET["usacan_areacode_check"];}
	elseif (isset($_POST["usacan_areacode_check"]))	{$usacan_areacode_check=$_POST["usacan_areacode_check"];}
if (isset($_GET["usacan_prefix_check"]))			{$usacan_prefix_check=$_GET["usacan_prefix_check"];}
	elseif (isset($_POST["usacan_prefix_check"]))	{$usacan_prefix_check=$_POST["usacan_prefix_check"];}
if (isset($_GET["delete_phone"]))			{$delete_phone=$_GET["delete_phone"];}
	elseif (isset($_POST["delete_phone"]))	{$delete_phone=$_POST["delete_phone"];}
if (isset($_GET["alias_id"]))			{$alias_id=$_GET["alias_id"];}
	elseif (isset($_POST["alias_id"]))	{$alias_id=$_POST["alias_id"];}
if (isset($_GET["phone_logins"]))			{$phone_logins=$_GET["phone_logins"];}
	elseif (isset($_POST["phone_logins"]))	{$phone_logins=$_POST["phone_logins"];}
if (isset($_GET["alias_name"]))				{$alias_name=$_GET["alias_name"];}
	elseif (isset($_POST["alias_name"]))	{$alias_name=$_POST["alias_name"];}
if (isset($_GET["delete_alias"]))			{$delete_alias=$_GET["delete_alias"];}
	elseif (isset($_POST["delete_alias"]))	{$delete_alias=$_POST["delete_alias"];}
if (isset($_GET["callback"]))			{$callback=$_GET["callback"];}
	elseif (isset($_POST["callback"]))	{$callback=$_POST["callback"];}
if (isset($_GET["callback_status"]))			{$callback_status=$_GET["callback_status"];}
	elseif (isset($_POST["callback_status"]))	{$callback_status=$_POST["callback_status"];}
if (isset($_GET["callback_datetime"]))			{$callback_datetime=$_GET["callback_datetime"];}
	elseif (isset($_POST["callback_datetime"]))	{$callback_datetime=$_POST["callback_datetime"];}
if (isset($_GET["callback_type"]))			{$callback_type=$_GET["callback_type"];}
	elseif (isset($_POST["callback_type"]))	{$callback_type=$_POST["callback_type"];}
if (isset($_GET["callback_user"]))			{$callback_user=$_GET["callback_user"];}
	elseif (isset($_POST["callback_user"]))	{$callback_user=$_POST["callback_user"];}
if (isset($_GET["callback_comments"]))			{$callback_comments=$_GET["callback_comments"];}
	elseif (isset($_POST["callback_comments"]))	{$callback_comments=$_POST["callback_comments"];}
if (isset($_GET["admin_user_group"]))			{$admin_user_group=$_GET["admin_user_group"];}
	elseif (isset($_POST["admin_user_group"]))	{$admin_user_group=$_POST["admin_user_group"];}
if (isset($_GET["datetime_start"]))				{$datetime_start=$_GET["datetime_start"];}
	elseif (isset($_POST["datetime_start"]))	{$datetime_start=$_POST["datetime_start"];}
if (isset($_GET["datetime_end"]))			{$datetime_end=$_GET["datetime_end"];}
	elseif (isset($_POST["datetime_end"]))	{$datetime_end=$_POST["datetime_end"];}
if (isset($_GET["time_format"]))			{$time_format=$_GET["time_format"];}
	elseif (isset($_POST["time_format"]))	{$time_format=$_POST["time_format"];}
if (isset($_GET["group_alias_id"]))				{$group_alias_id=$_GET["group_alias_id"];}
	elseif (isset($_POST["group_alias_id"]))	{$group_alias_id=$_POST["group_alias_id"];}
if (isset($_GET["group_alias_name"]))			{$group_alias_name=$_GET["group_alias_name"];}
	elseif (isset($_POST["group_alias_name"]))	{$group_alias_name=$_POST["group_alias_name"];}
if (isset($_GET["caller_id_number"]))			{$caller_id_number=$_GET["caller_id_number"];}
	elseif (isset($_POST["caller_id_number"]))	{$caller_id_number=$_POST["caller_id_number"];}
if (isset($_GET["caller_id_name"]))				{$caller_id_name=$_GET["caller_id_name"];}
	elseif (isset($_POST["caller_id_name"]))	{$caller_id_name=$_POST["caller_id_name"];}
if (isset($_GET["user_groups"]))				{$user_groups=$_GET["user_groups"];}
	elseif (isset($_POST["user_groups"]))		{$user_groups=$_POST["user_groups"];}
if (isset($_GET["in_groups"]))				{$in_groups=$_GET["in_groups"];}
	elseif (isset($_POST["in_groups"]))		{$in_groups=$_POST["in_groups"];}
if (isset($_GET["did_ids"]))				{$did_ids=$_GET["did_ids"];}
	elseif (isset($_POST["did_ids"]))		{$did_ids=$_POST["did_ids"];}
if (isset($_GET["did_patterns"]))				{$did_patterns=$_GET["did_patterns"];}
	elseif (isset($_POST["did_patterns"]))		{$did_patterns=$_POST["did_patterns"];}
if (isset($_GET["call_id"]))				{$call_id=$_GET["call_id"];}
	elseif (isset($_POST["call_id"]))		{$call_id=$_POST["call_id"];}
if (isset($_GET["group"]))					{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))			{$group=$_POST["group"];}
if (isset($_GET["expiration_date"]))			{$expiration_date=$_GET["expiration_date"];}
	elseif (isset($_POST["expiration_date"]))	{$expiration_date=$_POST["expiration_date"];}
if (isset($_GET["nanpa_ac_prefix_check"]))			{$nanpa_ac_prefix_check=$_GET["nanpa_ac_prefix_check"];}
	elseif (isset($_POST["nanpa_ac_prefix_check"]))	{$nanpa_ac_prefix_check=$_POST["nanpa_ac_prefix_check"];}
if (isset($_GET["detail"]))				{$detail=$_GET["detail"];}
	elseif (isset($_POST["detail"]))	{$detail=$_POST["detail"];}
if (isset($_GET["delete_user"]))			{$delete_user=$_GET["delete_user"];}
	elseif (isset($_POST["delete_user"]))	{$delete_user=$_POST["delete_user"];}
if (isset($_GET["campaign_rank"]))			{$campaign_rank=$_GET["campaign_rank"];}
	elseif (isset($_POST["campaign_rank"]))	{$campaign_rank=$_POST["campaign_rank"];}
if (isset($_GET["campaign_grade"]))				{$campaign_grade=$_GET["campaign_grade"];}
	elseif (isset($_POST["campaign_grade"]))	{$campaign_grade=$_POST["campaign_grade"];}
if (isset($_GET["local_call_time"]))				{$local_call_time=$_GET["local_call_time"];}
	elseif (isset($_POST["local_call_time"]))	{$local_call_time=$_POST["local_call_time"];}
if (isset($_GET["camp_rg_only"]))				{$camp_rg_only=$_GET["camp_rg_only"];}
	elseif (isset($_POST["camp_rg_only"]))		{$camp_rg_only=$_POST["camp_rg_only"];}
if (isset($_GET["wrapup_seconds_override"]))			{$wrapup_seconds_override=$_GET["wrapup_seconds_override"];}
	elseif (isset($_POST["wrapup_seconds_override"]))	{$wrapup_seconds_override=$_POST["wrapup_seconds_override"];}
if (isset($_GET["entry_list_id"]))			{$entry_list_id=$_GET["entry_list_id"];}
	elseif (isset($_POST["entry_list_id"]))	{$entry_list_id=$_POST["entry_list_id"];}
if (isset($_GET["show_sub_status"]))			{$show_sub_status=$_GET["show_sub_status"];}
	elseif (isset($_POST["show_sub_status"]))	{$show_sub_status=$_POST["show_sub_status"];}
if (isset($_GET["campaigns"]))			{$campaigns=$_GET["campaigns"];}
	elseif (isset($_POST["campaigns"]))	{$campaigns=$_POST["campaigns"];}
if (isset($_GET["ingroups"]))			{$ingroups=$_GET["ingroups"];}
	elseif (isset($_POST["ingroups"]))	{$ingroups=$_POST["ingroups"];}
if (isset($_GET["campaign_name"]))			{$campaign_name=$_GET["campaign_name"];}
	elseif (isset($_POST["campaign_name"]))	{$campaign_name=$_POST["campaign_name"];}
if (isset($_GET["did_ids"]))						{$did_ids=$_GET["did_ids"];}
	elseif (isset($_POST["did_ids"]))				{$did_ids=$_POST["did_ids"];}
if (isset($_GET["did_pattern"]))						{$did_pattern=$_GET["did_pattern"];}
	elseif (isset($_POST["did_pattern"]))				{$did_pattern=$_POST["did_pattern"];}
if (isset($_GET["users"]))						{$users=$_GET["users"];}
	elseif (isset($_POST["users"]))				{$users=$_POST["users"];}
if (isset($_GET["auto_dial_level"]))			{$auto_dial_level=$_GET["auto_dial_level"];}
	elseif (isset($_POST["auto_dial_level"]))	{$auto_dial_level=$_POST["auto_dial_level"];}
if (isset($_GET["adaptive_maximum_level"]))				{$adaptive_maximum_level=$_GET["adaptive_maximum_level"];}
	elseif (isset($_POST["adaptive_maximum_level"]))	{$adaptive_maximum_level=$_POST["adaptive_maximum_level"];}
if (isset($_GET["campaign_vdad_exten"]))			{$campaign_vdad_exten=$_GET["campaign_vdad_exten"];}
	elseif (isset($_POST["campaign_vdad_exten"]))	{$campaign_vdad_exten=$_POST["campaign_vdad_exten"];}
if (isset($_GET["hopper_level"]))			{$hopper_level=$_GET["hopper_level"];}
	elseif (isset($_POST["hopper_level"]))	{$hopper_level=$_POST["hopper_level"];}
if (isset($_GET["reset_hopper"]))			{$reset_hopper=$_GET["reset_hopper"];}
	elseif (isset($_POST["reset_hopper"]))	{$reset_hopper=$_POST["reset_hopper"];}
if (isset($_GET["dial_method"]))			{$dial_method=$_GET["dial_method"];}
	elseif (isset($_POST["dial_method"]))	{$dial_method=$_POST["dial_method"];}
if (isset($_GET["dial_timeout"]))			{$dial_timeout=$_GET["dial_timeout"];}
	elseif (isset($_POST["dial_timeout"]))	{$dial_timeout=$_POST["dial_timeout"];}
if (isset($_GET["field_name"]))				{$field_name=$_GET["field_name"];}
	elseif (isset($_POST["field_name"]))	{$field_name=$_POST["field_name"];}
if (isset($_GET["lookup_state"]))			{$lookup_state=$_GET["lookup_state"];}
	elseif (isset($_POST["lookup_state"]))	{$lookup_state=$_POST["lookup_state"];}
if (isset($_GET["type"]))				{$type=$_GET["type"];}
	elseif (isset($_POST["type"]))		{$type=$_POST["type"];}
if (isset($_GET["status_breakdown"]))						{$status_breakdown=$_GET["status_breakdown"];}
	elseif (isset($_POST["status_breakdown"]))				{$status_breakdown=$_POST["status_breakdown"];}
if (isset($_GET["show_percentages"]))						{$show_percentages=$_GET["show_percentages"];}
	elseif (isset($_POST["show_percentages"]))				{$show_percentages=$_POST["show_percentages"];}
if (isset($_GET["file_download"]))						{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))				{$file_download=$_POST["file_download"];}
if (isset($_GET["force_entry_list_id"]))			{$force_entry_list_id=$_GET["force_entry_list_id"];}
	elseif (isset($_POST["force_entry_list_id"]))	{$force_entry_list_id=$_POST["force_entry_list_id"];}
if (isset($_GET["lead_filter_id"]))				{$lead_filter_id=$_GET["lead_filter_id"];}
	elseif (isset($_POST["lead_filter_id"]))	{$lead_filter_id=$_POST["lead_filter_id"];}
if (isset($_GET["agent_choose_ingroups"]))			{$agent_choose_ingroups=$_GET["agent_choose_ingroups"];}
	elseif (isset($_POST["agent_choose_ingroups"]))	{$agent_choose_ingroups=$_POST["agent_choose_ingroups"];}
if (isset($_GET["agent_choose_blended"]))			{$agent_choose_blended=$_GET["agent_choose_blended"];}
	elseif (isset($_POST["agent_choose_blended"]))	{$agent_choose_blended=$_POST["agent_choose_blended"];}
if (isset($_GET["closer_default_blended"]))				{$closer_default_blended=$_GET["closer_default_blended"];}
	elseif (isset($_POST["closer_default_blended"]))	{$closer_default_blended=$_POST["closer_default_blended"];}
if (isset($_GET["outbound_alt_cid"]))				{$outbound_alt_cid=$_GET["outbound_alt_cid"];}
	elseif (isset($_POST["outbound_alt_cid"]))		{$outbound_alt_cid=$_POST["outbound_alt_cid"];}
if (isset($_GET["phone_ring_timeout"]))				{$phone_ring_timeout=$_GET["phone_ring_timeout"];}
	elseif (isset($_POST["phone_ring_timeout"]))	{$phone_ring_timeout=$_POST["phone_ring_timeout"];}
if (isset($_GET["delete_vm_after_email"]))			{$delete_vm_after_email=$_GET["delete_vm_after_email"];}
	elseif (isset($_POST["delete_vm_after_email"]))	{$delete_vm_after_email=$_POST["delete_vm_after_email"];}
if (isset($_GET["did_description"]))			{$did_description=$_GET["did_description"];}
	elseif (isset($_POST["did_description"]))	{$did_description=$_POST["did_description"];}
if (isset($_GET["did_route"]))			{$did_route=$_GET["did_route"];}
	elseif (isset($_POST["did_route"]))	{$did_route=$_POST["did_route"];}
if (isset($_GET["record_call"]))			{$record_call=$_GET["record_call"];}
	elseif (isset($_POST["record_call"]))	{$record_call=$_POST["record_call"];}
if (isset($_GET["exten_context"]))			{$exten_context=$_GET["exten_context"];}
	elseif (isset($_POST["exten_context"]))	{$exten_context=$_POST["exten_context"];}
if (isset($_GET["voicemail_ext"]))			{$voicemail_ext=$_GET["voicemail_ext"];}
	elseif (isset($_POST["voicemail_ext"]))	{$voicemail_ext=$_POST["voicemail_ext"];}
if (isset($_GET["phone_extension"]))			{$phone_extension=$_GET["phone_extension"];}
	elseif (isset($_POST["phone_extension"]))	{$phone_extension=$_POST["phone_extension"];}
if (isset($_GET["filter_clean_cid_number"]))			{$filter_clean_cid_number=$_GET["filter_clean_cid_number"];}
	elseif (isset($_POST["filter_clean_cid_number"]))	{$filter_clean_cid_number=$_POST["filter_clean_cid_number"];}
if (isset($_GET["ignore_agentdirect"]))				{$ignore_agentdirect=$_GET["ignore_agentdirect"];}
	elseif (isset($_POST["ignore_agentdirect"]))	{$ignore_agentdirect=$_POST["ignore_agentdirect"];}
if (isset($_GET["areacode"]))				{$areacode=$_GET["areacode"];}
	elseif (isset($_POST["areacode"]))		{$areacode=$_POST["areacode"];}
if (isset($_GET["cid_group_id"]))			{$cid_group_id=$_GET["cid_group_id"];}
	elseif (isset($_POST["cid_group_id"]))	{$cid_group_id=$_POST["cid_group_id"];}
if (isset($_GET["cid_description"]))			{$cid_description=$_GET["cid_description"];}
	elseif (isset($_POST["cid_description"]))	{$cid_description=$_POST["cid_description"];}
if (isset($_GET["custom_fields_copy"]))				{$custom_fields_copy=$_GET["custom_fields_copy"];}
	elseif (isset($_POST["custom_fields_copy"]))	{$custom_fields_copy=$_POST["custom_fields_copy"];}
if (isset($_GET["list_description"]))			{$list_description=$_GET["list_description"];}
	elseif (isset($_POST["list_description"]))	{$list_description=$_POST["list_description"];}
if (isset($_GET["leads_counts"]))			{$leads_counts=$_GET["leads_counts"];}
	elseif (isset($_POST["leads_counts"]))	{$leads_counts=$_POST["leads_counts"];}
if (isset($_GET["remove_from_hopper"]))				{$remove_from_hopper=$_GET["remove_from_hopper"];}
	elseif (isset($_POST["remove_from_hopper"]))	{$remove_from_hopper=$_POST["remove_from_hopper"];}
if (isset($_GET["custom_order"]))				{$custom_order=$_GET["custom_order"];}
	elseif (isset($_POST["custom_order"]))		{$custom_order=$_POST["custom_order"];}
if (isset($_GET["custom_copy_method"]))				{$custom_copy_method=$_GET["custom_copy_method"];}
	elseif (isset($_POST["custom_copy_method"]))	{$custom_copy_method=$_POST["custom_copy_method"];}
if (isset($_GET["duration"]))			{$duration=$_GET["duration"];}
	elseif (isset($_POST["duration"]))	{$duration=$_POST["duration"];}
if (isset($_GET["is_webphone"]))			{$is_webphone=$_GET["is_webphone"];}
	elseif (isset($_POST["is_webphone"]))	{$is_webphone=$_POST["is_webphone"];}
if (isset($_GET["webphone_auto_answer"]))			{$webphone_auto_answer=$_GET["webphone_auto_answer"];}
	elseif (isset($_POST["webphone_auto_answer"]))	{$webphone_auto_answer=$_POST["webphone_auto_answer"];}
if (isset($_GET["use_external_server_ip"]))			{$use_external_server_ip=$_GET["use_external_server_ip"];}
	elseif (isset($_POST["use_external_server_ip"]))	{$use_external_server_ip=$_POST["use_external_server_ip"];}
if (isset($_GET["template_id"]))			{$template_id=$_GET["template_id"];}
	elseif (isset($_POST["template_id"]))	{$template_id=$_POST["template_id"];}
if (isset($_GET["on_hook_agent"]))			{$on_hook_agent=$_GET["on_hook_agent"];}
	elseif (isset($_POST["on_hook_agent"]))	{$on_hook_agent=$_POST["on_hook_agent"];}
if (isset($_GET["delete_did"]))				{$delete_did=$_GET["delete_did"];}
	elseif (isset($_POST["delete_did"]))	{$delete_did=$_POST["delete_did"];}
if (isset($_GET["group_by_campaign"]))			{$group_by_campaign=$_GET["group_by_campaign"];}
	elseif (isset($_POST["group_by_campaign"]))	{$group_by_campaign=$_POST["group_by_campaign"];}
if (isset($_GET["source_user"]))			{$source_user=$_GET["source_user"];}
	elseif (isset($_POST["source_user"]))	{$source_user=$_POST["source_user"];}
if (isset($_GET["list_exists_check"]))			{$list_exists_check=$_GET["list_exists_check"];}
	elseif (isset($_POST["list_exists_check"]))	{$list_exists_check=$_POST["list_exists_check"];}
if (isset($_GET["menu_id"]))			{$menu_id=$_GET["menu_id"];}
	elseif (isset($_POST["menu_id"]))	{$menu_id=$_POST["menu_id"];}
if (isset($_GET["xferconf_one"]))			{$xferconf_one=$_GET["xferconf_one"];}
	elseif (isset($_POST["xferconf_one"]))	{$xferconf_one=$_POST["xferconf_one"];}
if (isset($_GET["xferconf_two"]))			{$xferconf_two=$_GET["xferconf_two"];}
	elseif (isset($_POST["xferconf_two"]))	{$xferconf_two=$_POST["xferconf_two"];}
if (isset($_GET["xferconf_three"]))			{$xferconf_three=$_GET["xferconf_three"];}
	elseif (isset($_POST["xferconf_three"]))	{$xferconf_three=$_POST["xferconf_three"];}
if (isset($_GET["xferconf_four"]))			{$xferconf_four=$_GET["xferconf_four"];}
	elseif (isset($_POST["xferconf_four"]))	{$xferconf_four=$_POST["xferconf_four"];}
if (isset($_GET["xferconf_five"]))			{$xferconf_five=$_GET["xferconf_five"];}
	elseif (isset($_POST["xferconf_five"]))	{$xferconf_five=$_POST["xferconf_five"];}
if (isset($_GET["use_internal_webserver"]))				{$use_internal_webserver=$_GET["use_internal_webserver"];}
	elseif (isset($_POST["use_internal_webserver"]))	{$use_internal_webserver=$_POST["use_internal_webserver"];}
if (isset($_GET["field_label"]))				{$field_label=$_GET["field_label"];}
	elseif (isset($_POST["field_label"]))		{$field_label=$_POST["field_label"];}
if (isset($_GET["field_name"]))					{$field_name=$_GET["field_name"];}
	elseif (isset($_POST["field_name"]))		{$field_name=$_POST["field_name"];}
if (isset($_GET["field_description"]))			{$field_description=$_GET["field_description"];}
	elseif (isset($_POST["field_description"]))	{$field_description=$_POST["field_description"];}
if (isset($_GET["field_rank"]))					{$field_rank=$_GET["field_rank"];}
	elseif (isset($_POST["field_rank"]))		{$field_rank=$_POST["field_rank"];}
if (isset($_GET["field_help"]))					{$field_help=$_GET["field_help"];}
	elseif (isset($_POST["field_help"]))		{$field_help=$_POST["field_help"];}
if (isset($_GET["field_type"]))					{$field_type=$_GET["field_type"];}
	elseif (isset($_POST["field_type"]))		{$field_type=$_POST["field_type"];}
if (isset($_GET["field_options"]))				{$field_options=$_GET["field_options"];}
	elseif (isset($_POST["field_options"]))		{$field_options=$_POST["field_options"];}
if (isset($_GET["field_size"]))					{$field_size=$_GET["field_size"];}
	elseif (isset($_POST["field_size"]))		{$field_size=$_POST["field_size"];}
if (isset($_GET["field_max"]))					{$field_max=$_GET["field_max"];}
	elseif (isset($_POST["field_max"]))			{$field_max=$_POST["field_max"];}
if (isset($_GET["field_default"]))				{$field_default=$_GET["field_default"];}
	elseif (isset($_POST["field_default"]))		{$field_default=$_POST["field_default"];}
if (isset($_GET["field_required"]))				{$field_required=$_GET["field_required"];}
	elseif (isset($_POST["field_required"]))	{$field_required=$_POST["field_required"];}
if (isset($_GET["name_position"]))				{$name_position=$_GET["name_position"];}
	elseif (isset($_POST["name_position"]))		{$name_position=$_POST["name_position"];}
if (isset($_GET["multi_position"]))				{$multi_position=$_GET["multi_position"];}
	elseif (isset($_POST["multi_position"]))	{$multi_position=$_POST["multi_position"];}
if (isset($_GET["field_order"]))				{$field_order=$_GET["field_order"];}
	elseif (isset($_POST["field_order"]))		{$field_order=$_POST["field_order"];}
if (isset($_GET["field_encrypt"]))				{$field_encrypt=$_GET["field_encrypt"];}
	elseif (isset($_POST["field_encrypt"]))		{$field_encrypt=$_POST["field_encrypt"];}
if (isset($_GET["field_show_hide"]))			{$field_show_hide=$_GET["field_show_hide"];}
	elseif (isset($_POST["field_show_hide"]))	{$field_show_hide=$_POST["field_show_hide"];}
if (isset($_GET["field_duplicate"]))			{$field_duplicate=$_GET["field_duplicate"];}
	elseif (isset($_POST["field_duplicate"]))	{$field_duplicate=$_POST["field_duplicate"];}
if (isset($_GET["field_rerank"]))				{$field_rerank=$_GET["field_rerank"];}
	elseif (isset($_POST["field_rerank"]))		{$field_rerank=$_POST["field_rerank"];}
if (isset($_GET["custom_fields_add"]))				{$custom_fields_add=$_GET["custom_fields_add"];}
	elseif (isset($_POST["custom_fields_add"]))		{$custom_fields_add=$_POST["custom_fields_add"];}
if (isset($_GET["custom_fields_update"]))			{$custom_fields_update=$_GET["custom_fields_update"];}
	elseif (isset($_POST["custom_fields_update"]))	{$custom_fields_update=$_POST["custom_fields_update"];}
if (isset($_GET["custom_fields_delete"]))			{$custom_fields_delete=$_GET["custom_fields_delete"];}
	elseif (isset($_POST["custom_fields_delete"]))	{$custom_fields_delete=$_POST["custom_fields_delete"];}
if (isset($_GET["dialable_count"]))				{$dialable_count=$_GET["dialable_count"];}
	elseif (isset($_POST["dialable_count"]))	{$dialable_count=$_POST["dialable_count"];}
if (isset($_GET["call_handle_method"]))				{$call_handle_method=$_GET["call_handle_method"];}
	elseif (isset($_POST["call_handle_method"]))	{$call_handle_method=$_POST["call_handle_method"];}
if (isset($_GET["agent_search_method"]))			{$agent_search_method=$_GET["agent_search_method"];}
	elseif (isset($_POST["agent_search_method"]))	{$agent_search_method=$_POST["agent_search_method"];}
if (isset($_GET["ingroup_rank"]))			{$ingroup_rank=$_GET["ingroup_rank"];}
	elseif (isset($_POST["ingroup_rank"]))	{$ingroup_rank=$_POST["ingroup_rank"];}
if (isset($_GET["ingroup_grade"]))			{$ingroup_grade=$_GET["ingroup_grade"];}
	elseif (isset($_POST["ingroup_grade"]))	{$ingroup_grade=$_POST["ingroup_grade"];}
if (isset($_GET["ingrp_rg_only"]))			{$ingrp_rg_only=$_GET["ingrp_rg_only"];}
	elseif (isset($_POST["ingrp_rg_only"]))	{$ingrp_rg_only=$_POST["ingrp_rg_only"];}
if (isset($_GET["group_id"]))				{$group_id=$_GET["group_id"];}
	elseif (isset($_POST["group_id"]))		{$group_id=$_POST["group_id"];}
if (isset($_GET["lead_ids"]))				{$lead_ids=$_GET["lead_ids"];}
	elseif (isset($_POST["lead_ids"]))		{$lead_ids=$_POST["lead_ids"];}
if (isset($_GET["delete_cf_data"]))				{$delete_cf_data=$_GET["delete_cf_data"];}
	elseif (isset($_POST["delete_cf_data"]))	{$delete_cf_data=$_POST["delete_cf_data"];}
if (isset($_GET["dispo_call_url"]))				{$dispo_call_url=$_GET["dispo_call_url"];}
	elseif (isset($_POST["dispo_call_url"]))	{$dispo_call_url=$_POST["dispo_call_url"];}
if (isset($_GET["entry_type"]))				{$entry_type=$_GET["entry_type"];}
	elseif (isset($_POST["entry_type"]))	{$entry_type=$_POST["entry_type"];}
if (isset($_GET["alt_url_id"]))				{$alt_url_id=$_GET["alt_url_id"];}
	elseif (isset($_POST["alt_url_id"]))	{$alt_url_id=$_POST["alt_url_id"];}
if (isset($_GET["url_address"]))			{$url_address=$_GET["url_address"];}
	elseif (isset($_POST["url_address"]))	{$url_address=$_POST["url_address"];}
if (isset($_GET["url_type"]))				{$url_type=$_GET["url_type"];}
	elseif (isset($_POST["url_type"]))		{$url_type=$_POST["url_type"];}
if (isset($_GET["url_rank"]))				{$url_rank=$_GET["url_rank"];}
	elseif (isset($_POST["url_rank"]))		{$url_rank=$_POST["url_rank"];}
if (isset($_GET["url_statuses"]))			{$url_statuses=$_GET["url_statuses"];}
	elseif (isset($_POST["url_statuses"]))	{$url_statuses=$_POST["url_statuses"];}
if (isset($_GET["url_description"]))			{$url_description=$_GET["url_description"];}
	elseif (isset($_POST["url_description"]))	{$url_description=$_POST["url_description"];}
if (isset($_GET["url_lists"]))				{$url_lists=$_GET["url_lists"];}
	elseif (isset($_POST["url_lists"]))		{$url_lists=$_POST["url_lists"];}
if (isset($_GET["url_call_length"]))			{$url_call_length=$_GET["url_call_length"];}
	elseif (isset($_POST["url_call_length"]))	{$url_call_length=$_POST["url_call_length"];}
if (isset($_GET["preset_name"]))			{$preset_name=$_GET["preset_name"];}
	elseif (isset($_POST["preset_name"]))	{$preset_name=$_POST["preset_name"];}
if (isset($_GET["preset_number"]))			{$preset_number=$_GET["preset_number"];}
	elseif (isset($_POST["preset_number"]))	{$preset_number=$_POST["preset_number"];}
if (isset($_GET["preset_dtmf"]))			{$preset_dtmf=$_GET["preset_dtmf"];}
	elseif (isset($_POST["preset_dtmf"]))	{$preset_dtmf=$_POST["preset_dtmf"];}
if (isset($_GET["preset_hide_number"]))				{$preset_hide_number=$_GET["preset_hide_number"];}
	elseif (isset($_POST["preset_hide_number"]))	{$preset_hide_number=$_POST["preset_hide_number"];}
if (isset($_GET["action"]))				{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))	{$action=$_POST["action"];}
if (isset($_GET["dial_status_add"]))			{$dial_status_add=$_GET["dial_status_add"];}
	elseif (isset($_POST["dial_status_add"]))	{$dial_status_add=$_POST["dial_status_add"];}
if (isset($_GET["dial_status_remove"]))				{$dial_status_remove=$_GET["dial_status_remove"];}
	elseif (isset($_POST["dial_status_remove"]))	{$dial_status_remove=$_POST["dial_status_remove"];}
if (isset($_GET["include_ip"]))				{$include_ip=$_GET["include_ip"];}
	elseif (isset($_POST["include_ip"]))	{$include_ip=$_POST["include_ip"];}
if (isset($_GET["reset_password"]))				{$reset_password=$_GET["reset_password"];}
	elseif (isset($_POST["reset_password"]))	{$reset_password=$_POST["reset_password"];}
if (isset($_GET["archived_lead"]))			{$archived_lead=$_GET["archived_lead"];}
	elseif (isset($_POST["archived_lead"]))	{$archived_lead=$_POST["archived_lead"];}
if (isset($_GET["list_order"]))			{$list_order=$_GET["list_order"];}
	elseif (isset($_POST["list_order"]))	{$list_order=$_POST["list_order"];}
if (isset($_GET["list_order_randomize"]))			{$list_order_randomize=$_GET["list_order_randomize"];}
	elseif (isset($_POST["list_order_randomize"]))	{$list_order_randomize=$_POST["list_order_randomize"];}
if (isset($_GET["list_order_secondary"]))			{$list_order_secondary=$_GET["list_order_secondary"];}
	elseif (isset($_POST["list_order_secondary"]))	{$list_order_secondary=$_POST["list_order_secondary"];}
if (isset($_GET["number_of_lines"]))			{$number_of_lines=$_GET["number_of_lines"];}
	elseif (isset($_POST["number_of_lines"]))	{$number_of_lines=$_POST["number_of_lines"];}
if (isset($_GET["source_did_pattern"]))				{$source_did_pattern=$_GET["source_did_pattern"];}
	elseif (isset($_POST["source_did_pattern"]))	{$source_did_pattern=$_POST["source_did_pattern"];}
if (isset($_GET["new_dids"]))			{$new_dids=$_GET["new_dids"];}
	elseif (isset($_POST["new_dids"]))	{$new_dids=$_POST["new_dids"];}
if (isset($_GET["webform_one"]))			{$webform_one=$_GET["webform_one"];}
	elseif (isset($_POST["webform_one"]))	{$webform_one=$_POST["webform_one"];}
if (isset($_GET["webform_two"]))			{$webform_two=$_GET["webform_two"];}
	elseif (isset($_POST["webform_two"]))	{$webform_two=$_POST["webform_two"];}
if (isset($_GET["webform_three"]))			{$webform_three=$_GET["webform_three"];}
	elseif (isset($_POST["webform_three"]))	{$webform_three=$_POST["webform_three"];}

$DB=preg_replace('/[^0-9]/','',$DB);

if (file_exists('options.php'))
	{require('options.php');}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,custom_fields_enabled,pass_hash_enabled,agent_whisper_enabled,active_modules,auto_dial_limit,enable_languages,language_method,admin_web_directory,sounds_web_server,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$custom_fields_enabled =	$row[1];
	$SSpass_hash_enabled =		$row[2];
	$agent_whisper_enabled =	$row[3];
	$active_modules =			$row[4];
	$SSauto_dial_limit =		$row[5];
	# slightly increase limit value, because PHP somehow thinks 2.8 > 2.8
	$SSauto_dial_limit = ($SSauto_dial_limit + 0.001);
	$SSenable_languages =		$row[6];
	$SSlanguage_method =		$row[7];
	$SSadmin_web_directory =	$row[8];
	$SSsounds_web_server =		$row[9];
	$SSallow_web_debug =		$row[10];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$list_id = preg_replace('/[^-_0-9a-zA-Z]/','',$list_id);
$list_id_field = preg_replace('/[^0-9]/','',$list_id_field);
$lead_id = preg_replace('/[^0-9]/','',$lead_id);
$lead_ids = preg_replace('/[^\,0-9]/','',$lead_ids);
$list_exists_check = preg_replace('/[^0-9a-zA-Z]/','',$list_exists_check);
$use_internal_webserver = preg_replace('/[^0-9a-zA-Z]/','',$use_internal_webserver);
$field_rerank = preg_replace('/[^_0-9a-zA-Z]/','',$field_rerank);
$custom_fields_add = preg_replace('/[^_0-9a-zA-Z]/','',$custom_fields_add);
$custom_fields_update = preg_replace('/[^_0-9a-zA-Z]/','',$custom_fields_update);
$custom_fields_delete = preg_replace('/[^_0-9a-zA-Z]/','',$custom_fields_delete);
$dialable_count = preg_replace('/[^_0-9a-zA-Z]/','',$dialable_count);
$call_handle_method = preg_replace('/[^_0-9a-zA-Z]/','',$call_handle_method);
$agent_search_method = preg_replace('/[^_0-9a-zA-Z]/','',$agent_search_method);
$delete_cf_data = preg_replace('/[^A-Z]/','',$delete_cf_data);
$entry_type = preg_replace('/[^_0-9a-zA-Z]/','',$entry_type);
$alt_url_id = preg_replace('/[^0-9A-Z]/','',$alt_url_id);
$url_type = preg_replace('/[^_0-9a-zA-Z]/','',$url_type);
$url_rank = preg_replace('/[^0-9]/','',$url_rank);
$url_lists = preg_replace('/[^- 0-9A-Z]/', '',$url_lists);
$url_call_length = preg_replace('/[^0-9]/','',$url_call_length);
$status_breakdown = preg_replace('/[^1Y]/','',$status_breakdown);
$show_percentages = preg_replace('/[^1Y]/','',$show_percentages);
$function = preg_replace('/[^-_0-9a-zA-Z]/', '',$function);
$format = preg_replace('/[^0-9a-zA-Z]/','',$format);
$session_id = preg_replace('/[^0-9]/','',$session_id);
$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$server_ip);
$stage = preg_replace('/[^-_0-9a-zA-Z]/','',$stage);
$rank = preg_replace('/[^0-9]/','',$rank);
$did_ids=preg_replace('/[^\,\+0-9a-zA-Z]/','',$did_ids);
$did_patterns = preg_replace('/[^\,\:\+\*\#\.\_0-9a-zA-Z]/','',$did_patterns);
$end_date=preg_replace('/[^-0-9]/','',$end_date);
$end_time=preg_replace('/[^:0-9]/','',$end_time);
$entry_list_id = preg_replace('/[^0-9]/','',$entry_list_id);
$phone_code = preg_replace('/[^0-9]/','',$phone_code);
$update_phone_number=preg_replace('/[^A-Z]/','',$update_phone_number);
$date_of_birth = preg_replace('/[^-0-9]/','',$date_of_birth);
$gender = preg_replace('/[^A-Z]/','',$gender);
$dnc_check = preg_replace('/[^A-Z]/','',$dnc_check);
$campaign_dnc_check = preg_replace('/[^A-Z]/','',$campaign_dnc_check);
$add_to_hopper = preg_replace('/[^A-Z]/','',$add_to_hopper);
$hopper_priority = preg_replace("/[^-0-9]/", "",$hopper_priority);
$hopper_local_call_time_check = preg_replace('/[^A-Z]/','',$hopper_local_call_time_check);
$custom_fields = preg_replace('/[^0-9a-zA-Z]/','',$custom_fields);
$search_method = preg_replace('/[^-_0-9a-zA-Z]/','',$search_method);
$duplicate_check = preg_replace('/[^-_0-9a-zA-Z]/','',$duplicate_check);
$insert_if_not_found = preg_replace('/[^A-Z]/','',$insert_if_not_found);
$records = preg_replace('/[^0-9]/','',$records);
$search_location = preg_replace('/[^A-Z]/','',$search_location);
$no_update = preg_replace('/[^A-Z]/','',$no_update);
$delete_lead = preg_replace('/[^A-Z]/','',$delete_lead);
$called_count=preg_replace('/[^0-9]/','',$called_count);
$agent_user_level=preg_replace('/[^0-9]/','',$agent_user_level);
$hotkeys_active=preg_replace('/[^0-9]/','',$hotkeys_active);
$protocol=preg_replace('/[^0-9a-zA-Z]/','',$protocol);
$local_gmt=preg_replace('/[^-\.0-9]/','',$local_gmt);
$active=preg_replace('/[^A-Z]/','',$active);
$reset_list=preg_replace('/[^A-Z]/','',$reset_list);
$delete_list=preg_replace('/[^A-Z]/','',$delete_list);
$delete_leads=preg_replace('/[^A-Z]/','',$delete_leads);
$reset_time=preg_replace('/[^-_0-9]/', '',$reset_time);
$tz_method = preg_replace('/[^-\_0-9a-zA-Z]/', '',$tz_method);
$reset_lead = preg_replace('/[^A-Z]/','',$reset_lead);
$usacan_areacode_check = preg_replace('/[^A-Z]/','',$usacan_areacode_check);
$usacan_prefix_check = preg_replace('/[^A-Z]/','',$usacan_prefix_check);
$delete_phone = preg_replace('/[^A-Z]/','',$delete_phone);
$callback_datetime = preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$callback_datetime);
$callback = preg_replace('/[^A-Z]/','',$callback);
$callback_type = preg_replace('/[^A-Z]/','',$callback_type);
$datetime_start = preg_replace('/[^- \+\:\_0-9]/','',$datetime_start);
$datetime_end = preg_replace('/[^- \+\:\_0-9]/','',$datetime_end);
$time_format = preg_replace('/[^A-Z]/','',$time_format);
$nanpa_ac_prefix_check = preg_replace('/[^A-Z]/','',$nanpa_ac_prefix_check);
$delete_user = preg_replace('/[^A-Z]/','',$delete_user);
$campaign_rank = preg_replace('/[^-_0-9]/','',$campaign_rank);
$campaign_grade = preg_replace('/[^0-9]/','',$campaign_grade);
$camp_rg_only = preg_replace('/[^0-9]/','',$camp_rg_only);
$wrapup_seconds_override = preg_replace('/[^-0-9]/','',$wrapup_seconds_override);
$show_sub_status = preg_replace('/[^A-Z]/','',$show_sub_status);
$auto_dial_level = preg_replace('/[^\.0-9]/','',$auto_dial_level);
$adaptive_maximum_level = preg_replace('/[^\.0-9]/','',$adaptive_maximum_level);
$campaign_vdad_exten = preg_replace('/[^0-9]/','',$campaign_vdad_exten);
$hopper_level = preg_replace('/[^0-9]/','',$hopper_level);
$reset_hopper = preg_replace('/[^NY]/','',$reset_hopper);
$dial_method = preg_replace('/[^-_0-9a-zA-Z]/','',$dial_method);
$dial_timeout = preg_replace('/[^0-9]/','',$dial_timeout);
$lookup_state = preg_replace('/[^A-Z]/','',$lookup_state);
$detail = preg_replace('/[^A-Z]/','',$detail);
$type = preg_replace('/[^-_0-9a-zA-Z]/','',$type);
$force_entry_list_id = preg_replace('/[^0-9]/','',$force_entry_list_id);
$file_download = preg_replace('/[^0-9]/','',$file_download);
$agent_choose_ingroups = preg_replace('/[^0-9]/','',$agent_choose_ingroups);
$agent_choose_blended = preg_replace('/[^0-9]/','',$agent_choose_blended);
$closer_default_blended = preg_replace('/[^0-9]/','',$closer_default_blended);
$phone_ring_timeout = preg_replace('/[^0-9]/','',$phone_ring_timeout);
$delete_vm_after_email = preg_replace('/[^a-zA-Z]/','',$delete_vm_after_email);
$field_rank = preg_replace('/[^0-9]/','',$field_rank);
$field_size = preg_replace('/[^0-9]/','',$field_size);
$field_max = preg_replace('/[^0-9]/','',$field_max);
$field_order = preg_replace('/[^0-9]/','',$field_order);
$field_required = preg_replace('/[^_A-Z]/','',$field_required);
$field_encrypt = preg_replace('/[^NY]/','',$field_encrypt);
$field_duplicate = preg_replace('/[^_A-Z]/','',$field_duplicate);
$field_type = preg_replace('/[^0-9a-zA-Z]/','',$field_type);
$name_position = preg_replace('/[^0-9a-zA-Z]/','',$name_position);
$multi_position = preg_replace('/[^0-9a-zA-Z]/','',$multi_position);
$ingroup_rank = preg_replace('/[^-_0-9]/','',$ingroup_rank);
$ingroup_grade = preg_replace('/[^0-9]/','',$ingroup_grade);
$ingrp_rg_only = preg_replace('/[^0-9]/','',$ingrp_rg_only);
$query_date=preg_replace('/[^-0-9]/','',$query_date);
$query_time=preg_replace('/[^:0-9]/','',$query_time);
$gmt_offset_now = preg_replace('/[^-\_\.0-9]/','',$gmt_offset_now);
$date=preg_replace('/[^-0-9]/','',$date);
$header = preg_replace('/[^0-9a-zA-Z]/','',$header);
$preset_hide_number = preg_replace('/[^0-9a-zA-Z]/','',$preset_hide_number);
$preset_number = preg_replace('/[^\*\#\.\_0-9a-zA-Z]/','',$preset_number);
$preset_dtmf = preg_replace('/[^- \,\*\#0-9a-zA-Z]/','',$preset_dtmf);
$action = preg_replace('/[^0-9a-zA-Z]/','',$action);
$include_ip = preg_replace('/[^0-9a-zA-Z]/','',$include_ip);
$reset_password = preg_replace('/[^0-9]/','',$reset_password);
$archived_lead = preg_replace('/[^0-9a-zA-Z]/','',$archived_lead);
$list_order = preg_replace('/[^ 0-9a-zA-Z]/','',$list_order);
$list_order_randomize = preg_replace('/[^-_0-9a-zA-Z]/','',$list_order_randomize);
$list_order_secondary = preg_replace('/[^-_0-9a-zA-Z]/','',$list_order_secondary);
$number_of_lines = preg_replace('/[^0-9]/','',$number_of_lines);

if ($non_latin < 1)
	{
	$status = preg_replace('/[^-_0-9a-zA-Z]/','',$status);
	$ingroups = preg_replace('/[^-_0-9a-zA-Z]/','',$ingroups);
	$phone_extension = preg_replace('/[^-_0-9a-zA-Z]/','',$phone_extension);
	$users=preg_replace('/[^-\,\_0-9a-zA-Z]/','',$users);
	$statuses = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$statuses);
	$categories = preg_replace('/[^-\,\_0-9a-zA-Z]/','',$categories);
	$user=preg_replace('/[^-_0-9a-zA-Z]/','',$user);
	$pass=preg_replace('/[^-_0-9a-zA-Z]/','',$pass);
	$agent_user=preg_replace('/[^-_0-9a-zA-Z]/','',$agent_user);
	$phone_number = preg_replace('/[^\,0-9]/','',$phone_number);
	$vendor_lead_code = preg_replace('/;|#|\"/','',$vendor_lead_code);
		$vendor_lead_code = preg_replace('/\+/',' ',$vendor_lead_code);
	$source_id = preg_replace('/;|#|\"/','',$source_id);
		$source_id = preg_replace('/\+/',' ',$source_id);
	$title = preg_replace('/[^- \'\_\.0-9a-zA-Z]/','',$title);
	$first_name = preg_replace('/[^- \'\+\_\.0-9a-zA-Z]/','',$first_name);
		$first_name = preg_replace('/\+/',' ',$first_name);
	$middle_initial = preg_replace('/[^-_0-9a-zA-Z]/','',$middle_initial);
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
	$country_code = preg_replace('/[^-_0-9a-zA-Z]/','',$country_code);
	$alt_phone = preg_replace('/[^- \'\+\_\.0-9a-zA-Z]/','',$alt_phone);
		$alt_phone = preg_replace('/\+/',' ',$alt_phone);
	$email = preg_replace('/[^- \'\+\.\:\/\@\%\_0-9a-zA-Z]/','',$email);
		$email = preg_replace('/\+/',' ',$email);
	$security_phrase = preg_replace('/[^- \'\+\.\:\/\@\_0-9a-zA-Z]/','',$security_phrase);
		$security_phrase = preg_replace('/\+/',' ',$security_phrase);
	$comments = preg_replace('/;|#|\"/','',$comments);
		$comments = preg_replace('/\+/',' ',$comments);
	$campaign_id = preg_replace('/[^-\_0-9a-zA-Z]/', '',$campaign_id);
	$multi_alt_phones = preg_replace('/[^- \+\!\:\_0-9a-zA-Z]/','',$multi_alt_phones);
		$multi_alt_phones = preg_replace('/\+/',' ',$multi_alt_phones);
	$source = preg_replace('/[^0-9a-zA-Z]/','',$source);
	$phone_login = preg_replace('/[^-\_0-9a-zA-Z]/', '',$phone_login);
	$owner = preg_replace('/[^- \'\+\.\:\/\@\_0-9a-zA-Z]/','',$owner);
		$owner = preg_replace('/\+/',' ',$owner);
	$user_field = preg_replace('/[^-_0-9a-zA-Z]/','',$user_field);
	$voicemail_id=preg_replace('/[^0-9a-zA-Z]/','',$voicemail_id);
	$agent_pass=preg_replace('/[^-_0-9a-zA-Z]/','',$agent_pass);
	$agent_full_name=preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$agent_full_name);
	$agent_user_group=preg_replace('/[^-_0-9a-zA-Z]/','',$agent_user_group);
	$phone_pass=preg_replace('/[^-_0-9a-zA-Z]/','',$phone_pass);
	$custom_one=preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$custom_one);
	$custom_two=preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$custom_two);
	$custom_three=preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$custom_three);
	$custom_four=preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$custom_four);
	$custom_five=preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$custom_five);
	$extension=preg_replace('/[^-_0-9a-zA-Z]/','',$extension);
	$dialplan_number=preg_replace('/[^\*\#0-9a-zA-Z]/','',$dialplan_number);
	$registration_password=preg_replace('/[^-_0-9a-zA-Z]/','',$registration_password);
	$phone_full_name=preg_replace('/[^- \+\.\_0-9a-zA-Z]/','',$phone_full_name);
	$outbound_cid=preg_replace('/[^-_0-9a-zA-Z]/','',$outbound_cid);
	$phone_context=preg_replace('/[^-_0-9a-zA-Z]/','',$phone_context);
	$list_name=preg_replace('/[^- \+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$list_name);
	$script=preg_replace('/[^-_0-9a-zA-Z]/','',$script);
	$am_message=preg_replace('/[^-_0-9a-zA-Z]/','',$am_message);
	$drop_inbound_group=preg_replace('/[^-_0-9a-zA-Z]/','',$drop_inbound_group);
	$web_form_address=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$web_form_address);
	$web_form_address_two=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$web_form_address_two);
	$web_form_address_three=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$web_form_address_three);
	$dispo_call_url=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$dispo_call_url);
	$webform_one=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$webform_one);
	$webform_two=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$webform_two);
	$webform_three=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$webform_three);
	$url_address=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$url_address);
	$uniqueid=preg_replace('/[^- \.\_0-9a-zA-Z]/','',$uniqueid);
	$alias_id = preg_replace('/[^-\_0-9a-zA-Z]/', '',$alias_id);
	$phone_logins = preg_replace('/[^-\,\_0-9a-zA-Z]/','',$phone_logins);
	$alias_name = preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$alias_name);
	$delete_alias = preg_replace('/[^A-Z]/','',$delete_alias);
	$callback_status = preg_replace('/[^-\_0-9a-zA-Z]/', '',$callback_status);
	$callback_user = preg_replace('/[^-\_0-9a-zA-Z]/', '',$callback_user);
	$callback_comments = preg_replace('/[^- \+\.\:\/\@\_0-9a-zA-Z]/','',$callback_comments);
	$admin_user_group = preg_replace('/[^-\_0-9a-zA-Z]/', '',$admin_user_group);
	$group_alias_id = preg_replace('/[^\_0-9a-zA-Z]/','',$group_alias_id);
	$group_alias_name = preg_replace('/[^- \+\_0-9a-zA-Z]/','',$group_alias_name);
	$caller_id_number = preg_replace('/[^0-9]/','',$caller_id_number);
	$caller_id_name = preg_replace('/[^- \+\_0-9a-zA-Z]/','',$caller_id_name);
	$user_groups = preg_replace('/[^-\|\,\_0-9a-zA-Z]/','',$user_groups); #JCJ
	$in_groups = preg_replace('/[^-\|\,\_0-9a-zA-Z]/','',$in_groups); #JCJ
	$group = preg_replace('/[^-\|\_0-9a-zA-Z]/','',$group);
	$call_id = preg_replace('/[^0-9a-zA-Z]/','',$call_id);
	$expiration_date = preg_replace('/[^-_0-9a-zA-Z]/','',$expiration_date);
	$local_call_time = preg_replace('/[^-_0-9a-zA-Z]/','',$local_call_time);
	$campaigns = preg_replace('/[^-\,\|\_0-9a-zA-Z]/','',$campaigns); # JCJ
	$campaign_name = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$campaign_name);
	$field_name = preg_replace('/[^-_0-9a-zA-Z]/','',$field_name);
	$lead_filter_id = preg_replace('/[^-_0-9a-zA-Z]/','',$lead_filter_id);
	$outbound_alt_cid = preg_replace('/[^0-9a-zA-Z]/','',$outbound_alt_cid);
	$did_pattern = preg_replace('/[^:\+\*\#\.\_0-9a-zA-Z]/','',$did_pattern);
	$source_did_pattern = preg_replace('/[^:\+\*\#\.\_0-9a-zA-Z]/','',$source_did_pattern);
	$new_dids = preg_replace('/[^:\+\,\*\#\.\_0-9a-zA-Z]/','',$new_dids);
	$did_description = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$did_description);
	$did_route = preg_replace('/[^-_0-9a-zA-Z]/','',$did_route);
	$record_call = preg_replace('/[^-_0-9a-zA-Z]/','',$record_call);
	$exten_context = preg_replace('/[^-_0-9a-zA-Z]/','',$exten_context);
	$voicemail_ext = preg_replace('/[^\*\#\.\_0-9a-zA-Z]/','',$voicemail_ext);
	$extension = preg_replace('/[^-\*\#\.\:\/\@\_0-9a-zA-Z]/','',$extension);
	$filter_clean_cid_number = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$filter_clean_cid_number);
	$ignore_agentdirect = preg_replace('/[^A-Z]/','',$ignore_agentdirect);
	$areacode = preg_replace('/[^-_0-9a-zA-Z]/','',$areacode);
	$cid_group_id = preg_replace('/[^-_0-9a-zA-Z]/','',$cid_group_id);
	$cid_description = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$cid_description);
	$custom_fields_copy = preg_replace('/[^0-9]/','',$custom_fields_copy);
	if ($outbound_cid != '---ALL---')
		{$outbound_cid=preg_replace('/[^0-9]/','',$outbound_cid);}
	if ($areacode != '---ALL---')
		{$areacode=preg_replace('/[^0-9a-zA-Z]/','',$areacode);}
	$leads_counts = preg_replace('/[^-_0-9a-zA-Z]/','',$leads_counts);
	$remove_from_hopper=preg_replace('/[^0-9a-zA-Z]/','',$remove_from_hopper);
	$list_description=preg_replace('/[^- \+\.\:\/\@\?\&\_0-9a-zA-Z]/','',$list_description);
	$custom_order = preg_replace('/[^-_0-9a-zA-Z]/','',$custom_order);
	$custom_copy_method = preg_replace('/[^-_0-9a-zA-Z]/','',$custom_copy_method);
	$duration = preg_replace('/[^-_0-9a-zA-Z]/','',$duration);
	$is_webphone = preg_replace('/[^-_0-9a-zA-Z]/','',$is_webphone);
	$webphone_auto_answer = preg_replace('/[^-_0-9a-zA-Z]/','',$webphone_auto_answer);
	$use_external_server_ip = preg_replace('/[^-_0-9a-zA-Z]/','',$use_external_server_ip);
	$template_id = preg_replace('/[^-_0-9a-zA-Z]/','',$template_id);
	$on_hook_agent = preg_replace('/[^-_0-9a-zA-Z]/','',$on_hook_agent);
	$delete_did = preg_replace('/[^0-9a-zA-Z]/','',$delete_did);
	$group_by_campaign = preg_replace('/[^0-9a-zA-Z]/','',$group_by_campaign);
	$source_user = preg_replace('/[^-_0-9a-zA-Z]/','',$source_user);
	$menu_id = preg_replace('/[^-_0-9a-zA-Z]/','',$menu_id);
	$xferconf_one=preg_replace('/[^-_0-9a-zA-Z]/','',$xferconf_one);
	$xferconf_two=preg_replace('/[^-_0-9a-zA-Z]/','',$xferconf_two);
	$xferconf_three=preg_replace('/[^-_0-9a-zA-Z]/','',$xferconf_three);
	$xferconf_four=preg_replace('/[^-_0-9a-zA-Z]/','',$xferconf_four);
	$xferconf_five=preg_replace('/[^-_0-9a-zA-Z]/','',$xferconf_five);
	$field_label = preg_replace('/[^_0-9a-zA-Z]/','',$field_label);
	$field_show_hide = preg_replace('/[^_0-9a-zA-Z]/','',$field_show_hide);
	$field_name = preg_replace('/[^ \.\,-\_0-9a-zA-Z]/','',$field_name);
	$field_description = preg_replace('/[^ \.\,-\_0-9a-zA-Z]/','',$field_description);
	$field_options = preg_replace('/[^ \'\&\.\n\|\,-\_0-9a-zA-Z]/', '',$field_options);
	if ($field_type != 'SCRIPT')
		{$field_options = preg_replace('/[^ \.\n\|\,-\_0-9a-zA-Z]/', '',$field_options);}
	$field_help = preg_replace('/[^ \'\&\.\n\|\,-\_0-9a-zA-Z]/', '',$field_help);
	$field_default = preg_replace('/[^ \.\n\,-\_0-9a-zA-Z]/', '',$field_default);
	$group_id = preg_replace('/[^_0-9a-zA-Z]/','',$group_id);
	$url_statuses = preg_replace('/[^- 0-9a-zA-Z]/', '',$url_statuses);
	$url_description = preg_replace('/[^ \.\,-\_0-9a-zA-Z]/','',$url_description);
	$preset_name = preg_replace('/[^- \_0-9a-zA-Z]/','',$preset_name);
	$dial_status_add=preg_replace('/[^-_0-9a-zA-Z]/','',$dial_status_add);
	$dial_status_remove=preg_replace('/[^-_0-9a-zA-Z]/','',$dial_status_remove);
	}
else
	{
	$status = preg_replace('/[^-_0-9\p{L}]/u','',$status);
	$ingroups = preg_replace('/[^-_0-9\p{L}]/u','',$ingroups);
	$phone_extension = preg_replace('/[^-_0-9\p{L}]/u','',$phone_extension);
	$users=preg_replace('/[^-\,\_0-9\p{L}]/u','',$users);
	$statuses = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$statuses);
	$categories = preg_replace('/[^-\,\_0-9\p{L}]/u','',$categories);
	$user=preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$pass=preg_replace('/[^-_0-9\p{L}]/u','',$pass);
	$agent_user=preg_replace('/[^-_0-9\p{L}]/u','',$agent_user);
	$phone_number = preg_replace('/[^\,0-9]/','',$phone_number);
	$vendor_lead_code = preg_replace('/;|#|\"/','',$vendor_lead_code);
		$vendor_lead_code = preg_replace('/\+/',' ',$vendor_lead_code);
	$source_id = preg_replace('/;|#|\"/','',$source_id);
		$source_id = preg_replace('/\+/',' ',$source_id);
	$title = preg_replace('/[^- \'\_\.0-9\p{L}]/u','',$title);
	$first_name = preg_replace('/[^- \'\+\_\.0-9\p{L}]/u','',$first_name);
		$first_name = preg_replace('/\+/',' ',$first_name);
	$middle_initial = preg_replace('/[^-_0-9\p{L}]/u','',$middle_initial);
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
	$country_code = preg_replace('/[^-_0-9\p{L}]/u','',$country_code);
	$alt_phone = preg_replace('/[^- \'\+\_\.0-9\p{L}]/u','',$alt_phone);
		$alt_phone = preg_replace('/\+/',' ',$alt_phone);
	$email = preg_replace('/[^- \'\+\.\:\/\@\%\_0-9\p{L}]/u','',$email);
		$email = preg_replace('/\+/',' ',$email);
	$security_phrase = preg_replace('/[^- \'\+\.\:\/\@\_0-9\p{L}]/u','',$security_phrase);
		$security_phrase = preg_replace('/\+/',' ',$security_phrase);
	$comments = preg_replace('/;|#|\"/','',$comments);
		$comments = preg_replace('/\+/',' ',$comments);
	$campaign_id = preg_replace('/[^-\_0-9\p{L}]/u', '',$campaign_id);
	$multi_alt_phones = preg_replace('/[^- \+\!\:\_0-9\p{L}]/u','',$multi_alt_phones);
		$multi_alt_phones = preg_replace('/\+/',' ',$multi_alt_phones);
	$source = preg_replace('/[^0-9\p{L}]/u','',$source);
	$phone_login = preg_replace('/[^-\_0-9\p{L}]/u', '',$phone_login);
	$owner = preg_replace('/[^- \'\+\.\:\/\@\_0-9\p{L}]/u','',$owner);
		$owner = preg_replace('/\+/',' ',$owner);
	$user_field = preg_replace('/[^-_0-9\p{L}]/u','',$user_field);
	$voicemail_id=preg_replace('/[^0-9\p{L}]/u','',$voicemail_id);
	$agent_pass=preg_replace('/[^-_0-9\p{L}]/u','',$agent_pass);
	$agent_full_name=preg_replace('/[^- \+\.\:\/\@\_0-9\p{L}]/u','',$agent_full_name);
	$agent_user_group=preg_replace('/[^-_0-9\p{L}]/u','',$agent_user_group);
	$phone_pass=preg_replace('/[^-_0-9\p{L}]/u','',$phone_pass);
	$custom_one=preg_replace('/[^- \+\.\:\/\@\_0-9\p{L}]/u','',$custom_one);
	$custom_two=preg_replace('/[^- \+\.\:\/\@\_0-9\p{L}]/u','',$custom_two);
	$custom_three=preg_replace('/[^- \+\.\:\/\@\_0-9\p{L}]/u','',$custom_three);
	$custom_four=preg_replace('/[^- \+\.\:\/\@\_0-9\p{L}]/u','',$custom_four);
	$custom_five=preg_replace('/[^- \+\.\:\/\@\_0-9\p{L}]/u','',$custom_five);
	$extension=preg_replace('/[^-_0-9\p{L}]/u','',$extension);
	$dialplan_number=preg_replace('/[^\*\#0-9\p{L}]/u','',$dialplan_number);
	$registration_password=preg_replace('/[^-_0-9\p{L}]/u','',$registration_password);
	$phone_full_name=preg_replace('/[^- \+\.\_0-9\p{L}]/u','',$phone_full_name);
	$outbound_cid=preg_replace('/[^-_0-9\p{L}]/u','',$outbound_cid);
	$phone_context=preg_replace('/[^-_0-9\p{L}]/u','',$phone_context);
	$list_name=preg_replace('/[^- \+\.\:\/\@\?\&\_0-9\p{L}]/u','',$list_name);
	$script=preg_replace('/[^-_0-9\p{L}]/u','',$script);
	$am_message=preg_replace('/[^-_0-9\p{L}]/u','',$am_message);
	$drop_inbound_group=preg_replace('/[^-_0-9\p{L}]/u','',$drop_inbound_group);
	$web_form_address=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9\p{L}]/u','',$web_form_address);
	$web_form_address_two=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9\p{L}]/u','',$web_form_address_two);
	$web_form_address_three=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9\p{L}]/u','',$web_form_address_three);
	$dispo_call_url=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9\p{L}]/u','',$dispo_call_url);
	$webform_one=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9\p{L}]/u','',$webform_one);
	$webform_two=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9\p{L}]/u','',$webform_two);
	$webform_three=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9\p{L}]/u','',$webform_three);
	$url_address=preg_replace('/[^- %=\+\.\:\/\@\?\&\_0-9\p{L}]/u','',$url_address);
	$uniqueid=preg_replace('/[^- \.\_0-9\p{L}]/u','',$uniqueid);
	$alias_id = preg_replace('/[^-\_0-9\p{L}]/u', '',$alias_id);
	$phone_logins = preg_replace('/[^-\,\_0-9\p{L}]/u','',$phone_logins);
	$alias_name = preg_replace('/[^- \+\.\:\/\@\_0-9\p{L}]/u','',$alias_name);
	$delete_alias = preg_replace('/[^A-Z]/','',$delete_alias);
	$callback_status = preg_replace('/[^-\_0-9\p{L}]/u', '',$callback_status);
	$callback_user = preg_replace('/[^-\_0-9\p{L}]/u', '',$callback_user);
	$callback_comments = preg_replace('/[^- \+\.\:\/\@\_0-9\p{L}]/u','',$callback_comments);
	$admin_user_group = preg_replace('/[^-\_0-9\p{L}]/u', '',$admin_user_group);
	$group_alias_id = preg_replace('/[^\_0-9\p{L}]/u','',$group_alias_id);
	$group_alias_name = preg_replace('/[^- \+\_0-9\p{L}]/u','',$group_alias_name);
	$caller_id_number = preg_replace('/[^0-9]/','',$caller_id_number);
	$caller_id_name = preg_replace('/[^- \+\_0-9\p{L}]/u','',$caller_id_name);
	$user_groups = preg_replace('/[^-\|\,\_0-9\p{L}]/u','',$user_groups); #JCJ
	$in_groups = preg_replace('/[^-\|\,\_0-9\p{L}]/u','',$in_groups); #JCJ
	$group = preg_replace('/[^-\|\_0-9\p{L}]/u','',$group);
	$call_id = preg_replace('/[^0-9\p{L}]/u','',$call_id);
	$expiration_date = preg_replace('/[^-_0-9\p{L}]/u','',$expiration_date);
	$local_call_time = preg_replace('/[^-_0-9\p{L}]/u','',$local_call_time);
	$campaigns = preg_replace('/[^-\,\|\_0-9\p{L}]/u','',$campaigns); # JCJ
	$campaign_name = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$campaign_name);
	$field_name = preg_replace('/[^-_0-9\p{L}]/u','',$field_name);
	$lead_filter_id = preg_replace('/[^-_0-9\p{L}]/u','',$lead_filter_id);
	$outbound_alt_cid = preg_replace('/[^0-9\p{L}]/u','',$outbound_alt_cid);
	$did_pattern = preg_replace('/[^:\+\*\#\.\_0-9\p{L}]/u','',$did_pattern);
	$source_did_pattern = preg_replace('/[^:\+\*\#\.\_0-9\p{L}]/u','',$source_did_pattern);
	$new_dids = preg_replace('/[^:\+\,\*\#\.\_0-9\p{L}]/u','',$new_dids);
	$did_description = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$did_description);
	$did_route = preg_replace('/[^-_0-9\p{L}]/u','',$did_route);
	$record_call = preg_replace('/[^-_0-9\p{L}]/u','',$record_call);
	$exten_context = preg_replace('/[^-_0-9\p{L}]/u','',$exten_context);
	$voicemail_ext = preg_replace('/[^\*\#\.\_0-9\p{L}]/u','',$voicemail_ext);
	$extension = preg_replace('/[^-\*\#\.\:\/\@\_0-9\p{L}]/u','',$extension);
	$filter_clean_cid_number = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$filter_clean_cid_number);
	$ignore_agentdirect = preg_replace('/[^A-Z]/','',$ignore_agentdirect);
	$areacode = preg_replace('/[^-_0-9\p{L}]/u','',$areacode);
	$cid_group_id = preg_replace('/[^-_0-9\p{L}]/u','',$cid_group_id);
	$cid_description = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$cid_description);
	$custom_fields_copy = preg_replace('/[^0-9]/','',$custom_fields_copy);
	if ($outbound_cid != '---ALL---')
		{$outbound_cid=preg_replace('/[^0-9]/','',$outbound_cid);}
	if ($areacode != '---ALL---')
		{$areacode=preg_replace('/[^0-9\p{L}]/u','',$areacode);}
	$leads_counts = preg_replace('/[^-_0-9\p{L}]/u','',$leads_counts);
	$remove_from_hopper=preg_replace('/[^0-9\p{L}]/u','',$remove_from_hopper);
	$list_description=preg_replace('/[^- \+\.\:\/\@\?\&\_0-9\p{L}]/u','',$list_description);
	$custom_order = preg_replace('/[^-_0-9\p{L}]/u','',$custom_order);
	$custom_copy_method = preg_replace('/[^-_0-9\p{L}]/u','',$custom_copy_method);
	$duration = preg_replace('/[^-_0-9\p{L}]/u','',$duration);
	$is_webphone = preg_replace('/[^-_0-9\p{L}]/u','',$is_webphone);
	$webphone_auto_answer = preg_replace('/[^-_0-9\p{L}]/u','',$webphone_auto_answer);
	$use_external_server_ip = preg_replace('/[^-_0-9\p{L}]/u','',$use_external_server_ip);
	$template_id = preg_replace('/[^-_0-9\p{L}]/u','',$template_id);
	$on_hook_agent = preg_replace('/[^-_0-9\p{L}]/u','',$on_hook_agent);
	$delete_did = preg_replace('/[^0-9\p{L}]/u','',$delete_did);
	$group_by_campaign = preg_replace('/[^0-9\p{L}]/u','',$group_by_campaign);
	$source_user = preg_replace('/[^-_0-9\p{L}]/u','',$source_user);
	$menu_id = preg_replace('/[^-_0-9\p{L}]/u','',$menu_id);
	$xferconf_one=preg_replace('/[^-_0-9\p{L}]/u','',$xferconf_one);
	$xferconf_two=preg_replace('/[^-_0-9\p{L}]/u','',$xferconf_two);
	$xferconf_three=preg_replace('/[^-_0-9\p{L}]/u','',$xferconf_three);
	$xferconf_four=preg_replace('/[^-_0-9\p{L}]/u','',$xferconf_four);
	$xferconf_five=preg_replace('/[^-_0-9\p{L}]/u','',$xferconf_five);
	$field_label = preg_replace('/[^_0-9\p{L}]/u','',$field_label);
	$field_show_hide = preg_replace('/[^_0-9\p{L}]/u','',$field_show_hide);
	$field_name = preg_replace('/[^ \.\,-\_0-9\p{L}]/u','',$field_name);
	$field_description = preg_replace('/[^ \.\,-\_0-9\p{L}]/u','',$field_description);
	$field_options = preg_replace('/[^ \'\&\.\n\|\,-\_0-9\p{L}]/u', '',$field_options);
	if ($field_type != 'SCRIPT')
		{$field_options = preg_replace('/[^ \.\n\|\,-\_0-9\p{L}]/u', '',$field_options);}
	$field_help = preg_replace('/[^ \'\&\.\n\|\,-\_0-9\p{L}]/u', '',$field_help);
	$field_default = preg_replace('/[^ \.\n\,-\_0-9\p{L}]/u', '',$field_default);
	$group_id = preg_replace('/[^_0-9\p{L}]/u','',$group_id);
	$url_statuses = preg_replace('/[^- 0-9\p{L}]/u', '',$url_statuses);
	$url_description = preg_replace('/[^ \.\,-\_0-9\p{L}]/u','',$url_description);
	$preset_name = preg_replace('/[^- \_0-9\p{L}]/u','',$preset_name);
	$dial_status_add=preg_replace('/[^-_0-9\p{L}]/u','',$dial_status_add);
	$dial_status_remove=preg_replace('/[^-_0-9\p{L}]/u','',$dial_status_remove);
	}

$USarea = 			substr($phone_number, 0, 3);
$USprefix = 		substr($phone_number, 3, 3);
if (strlen($hopper_priority)<1) {$hopper_priority=0;}
if ($hopper_priority < -99) {$hopper_priority=-99;}
if ($hopper_priority > 99) {$hopper_priority=99;}
if (preg_match("/^Y$/",$remove_from_hopper)) {$add_to_hopper='N';}

$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$ip = getenv("REMOTE_ADDR");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
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
$barge_prefix='';

$MT[0]='';
$api_script = 'non-agent';
$api_logging = 1;

$vicidial_list_fields = '|lead_id|vendor_lead_code|source_id|list_id|gmt_offset_now|called_since_last_reset|phone_code|phone_number|title|first_name|middle_initial|last_name|address1|address2|address3|city|state|province|postal_code|country_code|gender|date_of_birth|alt_phone|email|security_phrase|comments|called_count|last_local_call_time|rank|owner|';

$secX = date("U");
$hour = date("H");
$min = date("i");
$sec = date("s");
$mon = date("m");
$mday = date("d");
$year = date("Y");
$isdst = date("I");
$Shour = date("H");
$Smin = date("i");
$Ssec = date("s");
$Smon = date("m");
$Smday = date("d");
$Syear = date("Y");
$pulldate0 = "$year-$mon-$mday $hour:$min:$sec";
$inSD = $pulldate0;
$dsec = ( ( ($hour * 3600) + ($min * 60) ) + $sec );

### Grab Server system settings from the database
$stmt="SELECT local_gmt FROM servers where active='Y' limit 1;";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
$gmt_recs = mysqli_num_rows($rslt);
if ($gmt_recs > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$DBSERVER_GMT =			$row[0];
	if (strlen($DBSERVER_GMT)>0)	{$SERVER_GMT = $DBSERVER_GMT;}
	if ($isdst) {$SERVER_GMT++;}
	}
else
	{
	$SERVER_GMT = date("O");
	$SERVER_GMT = preg_replace('/\+/i', '',$SERVER_GMT);
	$SERVER_GMT = ($SERVER_GMT + 0);
	$SERVER_GMT = ($SERVER_GMT / 100);
	}

$LOCAL_GMT_OFF = $SERVER_GMT;
$LOCAL_GMT_OFF_STD = $SERVER_GMT;

if ($archived_lead=="Y") {$vicidial_list_table="vicidial_list_archive";} 
else {$vicidial_list_table="vicidial_list"; $archived_lead="N";}





################################################################################
### version - show version, date, time and time zone information for the API
################################################################################
if ($function == 'version')
	{
	$data = "VERSION: $version|BUILD: $build|DATE: $NOW_TIME|EPOCH: $StarTtime|DST: $isdst|TZ: $DBSERVER_GMT|TZNOW: $SERVER_GMT|";
	$result = 'SUCCESS';
	echo "$data\n";
	api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
	exit;
	}
################################################################################
### END version
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




##### BEGIN user authentication for all functions below #####
$auth=0;
$auth_message = user_authorization($user,$pass,'REPORTS',1,1);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth < 1)
	{
	if ( ($function == 'blind_monitor') and ($source == 'queuemetrics') and ($stage == 'MONITOR') )
		{
		$stmt="SELECT count(*) from vicidial_auto_calls where callerid='$pass';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$monauth_to_check = mysqli_num_rows($rslt);
		if ($monauth_to_check > 0)
			{
			$rowvac=mysqli_fetch_row($rslt);
			$auth =	$rowvac[0];
			}
		}
	}
if ($auth < 1)
	{
	$VDdisplayMESSAGE = "ERROR: Login incorrect, please try again";
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = "ERROR: Too many login attempts, try again in 15 minutes";
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$user|$auth_message|\n";
		exit;
		}
	if ($auth_message == 'IPBLOCK')
		{
		$VDdisplayMESSAGE = "ERROR: Your IP Address is not allowed: $ip";
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header ("Content-type: text/html; charset=utf-8");
	echo "$VDdisplayMESSAGE: |$user|$pass|$auth_message|\n";
	exit;
	}

$stmt="SELECT api_list_restrict,api_allowed_functions,user_group,selected_language,delete_inbound_dids,download_invalid_files,user_level from vicidial_users where user='$user' and active='Y';";
if ($DB>0) {echo "DEBUG: auth query - $stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$api_list_restrict =		$row[0];
$api_allowed_functions =	$row[1];
$LOGuser_group =			$row[2];
$VUselected_language =		$row[3];
$VUdelete_inbound_dids =	$row[4];
$VUdownload_invalid_files = $row[5];
$VUuser_level =				$row[6];

if ( ($api_list_restrict > 0) and ( ($function == 'add_lead') or ($function == 'update_lead') or ($function == 'batch_update_lead') or ($function == 'update_list') or ($function == 'list_info') or ($function == 'list_custom_fields') or ($function == 'lead_search') ) )
	{
	$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
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
		$stmt="SELECT list_id from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
		if ($DB>0) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$lists_to_print = mysqli_num_rows($rslt);
		$i=0;
		$allowed_lists=' ';
		$allowed_listsSQL='';
		while ($i < $lists_to_print)
			{
			$row=mysqli_fetch_row($rslt);
			$allowed_lists .=		"$row[0] ";
			$allowed_listsSQL .=	"'$row[0]',";
			$i++;
			}
		$allowed_listsSQL = preg_replace("/,$/",'',$allowed_listsSQL);
		if ($DB>0) {echo "Allowed lists:|$allowed_lists|$allowed_listsSQL|\n";}
		}
	else
		{
		$result = 'ERROR';
		$result_reason = "user_group DOES NOT EXIST";
		echo "$result: $result_reason: |$user|$LOGuser_group|\n";
		$data = "$allowed_user";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	}
##### END user authentication for all functions below #####



################################################################################
### sounds_list - sends a list of the sounds in the audio store
################################################################################
if ($function == 'sounds_list')
	{
	$stmt="SELECT count(*) from vicidial_users where user='$user' and user_level > 6 and active='Y';";
	if ($DB>0) {echo "DEBUG: sounds_list query - $stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$allowed_user=$row[0];
	if ($allowed_user < 1)
		{
		$result = 'ERROR';
		$result_reason = "sounds_list USER DOES NOT HAVE PERMISSION TO VIEW SOUNDS LIST";
		echo "$result: $result_reason: |$user|$allowed_user|\n";
		$data = "$allowed_user";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$server_name = getenv("SERVER_NAME");
		$server_port = getenv("SERVER_PORT");
		if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
		  else {$HTTPprotocol = 'http://';}
		// $admDIR = "$HTTPprotocol$server_name:$server_port";
		// By setting this variable to empty, we'll use the same protocol and port as per the original request
		$admDIR = "";
		$admin_web_dir='';

		#############################################
		##### START SYSTEM_SETTINGS LOOKUP #####
		$stmt = "SELECT use_non_latin,sounds_central_control_active,sounds_web_server,sounds_web_directory,admin_web_directory FROM system_settings;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$ss_conf_ct = mysqli_num_rows($rslt);
		if ($ss_conf_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$non_latin =						$row[0];
			$sounds_central_control_active =	$row[1];
			$sounds_web_server =				$row[2];
			$sounds_web_directory =				$row[3];
			$admin_web_directory =				$row[4];
			if (preg_match("/\//",$admin_web_directory))
				{$admin_web_dir = dirname("$admin_web_directory");   $admin_web_dir .= "/";}
			}
		##### END SETTINGS LOOKUP #####
		###########################################

		if ($sounds_central_control_active < 1)
			{
			$result = 'ERROR';
			$result_reason = "sounds_list CENTRAL SOUND CONTROL IS NOT ACTIVE";
			echo "$result: $result_reason: |$user|$sounds_central_control_active|\n";
			$data = "$sounds_central_control_active";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$i=0;
			$filename_sort=$MT;
			$dirpath = "$WeBServeRRooT/$sounds_web_directory";
			if (!file_exists("$WeBServeRRooT/$sounds_web_directory"))
				{
				$result = 'ERROR';
				$result_reason = "audio store web directory does not exist";
				echo "$result: $result_reason: |$user|$function|$WeBServeRRooT/$sounds_web_directory|\n";
				$data = "$allowed_user";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}

			$bad_wavs='|';
			$stmt="SELECT audio_filename from audio_store_details where wav_asterisk_valid='BAD';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$bw_ct = mysqli_num_rows($rslt);
			$bw=0;
			while ($bw_ct > $bw)
				{
				$row=mysqli_fetch_row($rslt);
				$bad_wavs .= "$row[0]|";
				$bw++;
				}

			$dh = opendir($dirpath);
			if ($DB>0) {echo "DEBUG: sounds_list variables - $dirpath|$stage|$format\n";}

			$file_names=array();
			$file_namesPROMPT=array();
			$file_epoch=array();
			$file_dates=array();
			$file_sizes=array();
			$file_sizesPAD=array();

			while (false !== ($file = readdir($dh)))
				{
				# Do not list subdirectories
				if ( (!is_dir("$dirpath/$file")) and (preg_match('/\.wav$|\.gsm$/', $file)) )
					{
					if (file_exists("$dirpath/$file"))
						{
						$file_names[$i] = $file;
						$file_namesPROMPT[$i] = preg_replace("/\.wav$|\.gsm$/","",$file);
						$file_epoch[$i] = filemtime("$dirpath/$file");
						$file_dates[$i] = date ("Y-m-d H:i:s.", filemtime("$dirpath/$file"));
						$file_sizes[$i] = filesize("$dirpath/$file");
						$file_sizesPAD[$i] = sprintf("[%020s]\n",filesize("$dirpath/$file"));
						if (preg_match('/date/i',$stage)) {$file_sort[$i] = $file_epoch[$i] . "----------" . $i;}
						if (preg_match('/name/i',$stage)) {$file_sort[$i] = $file_names[$i] . "----------" . $i;}
						if (preg_match('/size/i',$stage)) {$file_sort[$i] = $file_sizesPAD[$i] . "----------" . $i;}

						$i++;
						}
					}
				}
			closedir($dh);

			if (preg_match('/date/i',$stage)) {rsort($file_sort);}
			if (preg_match('/name/i',$stage)) {sort($file_sort);}
			if (preg_match('/size/i',$stage)) {rsort($file_sort);}

			sleep(1);

			$k=0;
			$sf=0;
			while($k < $i)
				{
				$file_split = explode('----------',$file_sort[$k]);
				$m = $file_split[1];
				$NOWsize = filesize("$dirpath/$file_names[$m]");
				if ($DB>0) {echo "DEBUG: sounds_list variables - $file_sort[$k]|$size|$NOWsize|\n";}
				if ($file_sizes[$m] == $NOWsize)
					{
					if (preg_match('/tab/i',$format))
						{echo "$k\t$file_names[$m]\t$file_dates[$m]\t$file_sizes[$m]\t$file_epoch[$m]\n";}
					if (preg_match('/link/i',$format))
						{
						if (!preg_match("/^http:|^https:/i",$sounds_web_server))
							{$sounds_web_server = "http://".$sounds_web_server;}
						echo "<a href=\"$sounds_web_server/$admin_web_dir$sounds_web_directory/$file_names[$m]\">$file_names[$m]</a><br>\n";
						}
					if (preg_match('/selectframe/i',$format))
						{
						if ($sf < 1)
							{
							echo "\n";
							echo "<HTML><head><title>NON-AGENT API</title>\n";
							echo "<script language=\"Javascript\">\n";
							echo "function choose_file(filename,fieldname)\n";
							echo "	{\n";
							echo "	if (filename.length > 0)\n";
							echo "		{\n";
							echo "		parent.document.getElementById(fieldname).value = filename;\n";
							echo "		document.getElementById(\"selectframe\").innerHTML = '';\n";
							echo "		document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
							echo "		parent.close_chooser();\n";
							echo "		}\n";
							echo "	}\n";
							echo "function close_file()\n";
							echo "	{\n";
							echo "	document.getElementById(\"selectframe\").innerHTML = '';\n";
							echo "	document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
							echo "	parent.close_chooser();\n";
							echo "	}\n";
							echo "</script>\n";
							echo "</head>\n\n";

							echo "<body>\n";
							echo "<a href=\"javascript:close_file();\"><font size=1 face=\"Arial,Helvetica\">"._QXZ("close frame")."</font></a>\n";
							echo "<div id='selectframe' style=\"height:400px;width:710px;overflow:scroll;\">\n";
							echo "<table border=0 cellpadding=1 cellspacing=2 width=690 bgcolor=white><tr>\n";
							echo "<td>#</td>\n";
							echo "<td><a href=\"$PHP_SELF?source=admin&function=sounds_list&user=$user&pass=$pass&format=selectframe&comments=$comments&stage=name\"><font color=black>"._QXZ("FILENAME")."</td>\n";
							echo "<td><a href=\"$PHP_SELF?source=admin&function=sounds_list&user=$user&pass=$pass&format=selectframe&comments=$comments&stage=date\"><font color=black>"._QXZ("DATE")."</td>\n";
							echo "<td><a href=\"$PHP_SELF?source=admin&function=sounds_list&user=$user&pass=$pass&format=selectframe&comments=$comments&stage=size\"><font color=black>"._QXZ("SIZE")."</td>\n";
							echo "<td>"._QXZ("PLAY")."</td>\n";
							echo "</tr>\n";
							}
						$sf++;
						$BWB='';   $BWE='';
						$PLAYlink = "<a href=\"$admDIR/$admin_web_dir$sounds_web_directory/$file_names[$m]\" target=\"_blank\"><font size=1 face=\"Arial,Helvetica\">"._QXZ("PLAY")."</a>";
						if ( (preg_match('/\.wav$/', $file_names[$m])) and (strlen($bad_wavs) > 2) )
							{
							$temp_filename = $file_names[$m];
							if (preg_match("/\|$temp_filename\|/",$bad_wavs))
								{
								$BWB='<font color=red><b>';
								$BWE=" &nbsp; &nbsp; "._QXZ("BAD WAV FORMAT")."</b></font>";
								if ( ($VUdownload_invalid_files < 1) or ($VUuser_level < 9) )
									{
									$PLAYlink = "<DEL><font size=1 face=\"Arial,Helvetica\">"._QXZ("PLAY")."</DEL>";
									}
								}
							}
						echo "<tr><td><font size=1 face=\"Arial,Helvetica\">$sf</td>\n";
						echo "<td><a href=\"javascript:choose_file('$file_namesPROMPT[$m]','$comments');\"><font size=1 face=\"Arial,Helvetica\">$BWB$file_names[$m]$BWE</a></td>\n";
						echo "<td><font size=1 face=\"Arial,Helvetica\">$file_dates[$m]</td>\n";
						echo "<td><font size=1 face=\"Arial,Helvetica\">$file_sizes[$m]</td>\n";
						echo "<td>$PLAYlink</td></tr>\n";
						}
					}
				$k++;
				}
			if ($sf > 0)
				{
				echo "</table></div></body></HTML>\n";
				}

			exit;

			}
		}
	}
################################################################################
### END sounds_list
################################################################################



################################################################################
### moh_list - sends a list of the moh classes in the system
################################################################################
if ($function == 'moh_list')
	{
	$stmt="SELECT count(*) from vicidial_users where user='$user' and user_level > 6 and active='Y';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$allowed_user=$row[0];
	if ($allowed_user < 1)
		{
		$result = 'ERROR';
		$result_reason = "sounds_list USER DOES NOT HAVE PERMISSION TO VIEW SOUNDS LIST";
		echo "$result: $result_reason: |$user|$allowed_user|\n";
		$data = "$allowed_user";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$server_name = getenv("SERVER_NAME");
		$server_port = getenv("SERVER_PORT");
		if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
		  else {$HTTPprotocol = 'http://';}
		// $admDIR = "$HTTPprotocol$server_name:$server_port";
		// By setting this variable to empty, we'll use the same protocol and port as per the original request
		$admDIR = "";


		#############################################
		##### START SYSTEM_SETTINGS LOOKUP #####
		$stmt = "SELECT use_non_latin,sounds_central_control_active,sounds_web_server,sounds_web_directory FROM system_settings;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$ss_conf_ct = mysqli_num_rows($rslt);
		if ($ss_conf_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$non_latin =						$row[0];
			$sounds_central_control_active =	$row[1];
			$sounds_web_server =				$row[2];
			$sounds_web_directory =				$row[3];
			}
		##### END SETTINGS LOOKUP #####
		###########################################

		if ($sounds_central_control_active < 1)
			{
			$result = 'ERROR';
			$result_reason = "sounds_list CENTRAL SOUND CONTROL IS NOT ACTIVE";
			echo "$result: $result_reason: |$user|$sounds_central_control_active|\n";
			$data = "$sounds_central_control_active";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
			if ($DB>0) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$LOGallowed_campaigns =			$row[0];
			$LOGadmin_viewable_groups =		$row[1];

			$LOGadmin_viewable_groupsSQL='';
			$whereLOGadmin_viewable_groupsSQL='';
			if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
				{
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
				$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				}

			echo "\n";
			echo "<HTML><head><title>NON-AGENT API</title>\n";
			echo "<script language=\"Javascript\">\n";
			echo "function choose_file(filename,fieldname)\n";
			echo "	{\n";
			echo "	if (filename.length > 0)\n";
			echo "		{\n";
			echo "		parent.document.getElementById(fieldname).value = filename;\n";
			echo "		document.getElementById(\"selectframe\").innerHTML = '';\n";
			echo "		document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
			echo "		parent.close_chooser();\n";
			echo "		}\n";
			echo "	}\n";
			echo "function close_file()\n";
			echo "	{\n";
			echo "	document.getElementById(\"selectframe\").innerHTML = '';\n";
			echo "	document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
			echo "	parent.close_chooser();\n";
			echo "	}\n";
			echo "</script>\n";
			echo "</head>\n\n";

			echo "<body>\n";
			echo "<a href=\"javascript:close_file();\"><font size=1 face=\"Arial,Helvetica\">"._QXZ("close frame")."</font></a>\n";
			echo "<div id='selectframe' style=\"height:400px;width:710px;overflow:scroll;\">\n";
			echo "<table border=0 cellpadding=1 cellspacing=2 width=690 bgcolor=white><tr>\n";
			echo "<td width=30>#</td>\n";
			echo "<td colspan=2>"._QXZ("Music On Hold Class")."</td>\n";
			echo "<td>"._QXZ("Name")."</td>\n";
			echo "<td>"._QXZ("Random")."</td>\n";
			echo "</tr>\n";

			$rowx=array();
			$moh_id=array();
			$moh_name=array();
			$random=array();

			$stmt="SELECT moh_id,moh_name,random from vicidial_music_on_hold where active='Y' $LOGadmin_viewable_groupsSQL order by moh_id";
			$rslt=mysql_to_mysqli($stmt, $link);
			$moh_to_print = mysqli_num_rows($rslt);
			$k=0;
			$sf=0;
			while ($moh_to_print > $k)
				{
				$rowx=mysqli_fetch_row($rslt);
				$moh_id[$k] =	$rowx[0];
				$moh_name[$k] = $rowx[1];
				$random[$k] =	$rowx[2];
				$k++;
				}

			$k=0;
			$sf=0;
			while ($moh_to_print > $k)
				{
				$sf++;
				if (preg_match("/1$|3$|5$|7$|9$/i", $sf))
					{$bgcolor='bgcolor="#E6E6E6"';}
				else
					{$bgcolor='bgcolor="#F6F6F6"';}
				echo "<tr $bgcolor><td width=30><font size=1 face=\"Arial,Helvetica\">$sf</td>\n";
				echo "<td colspan=2><a href=\"javascript:choose_file('$moh_id[$k]','$comments');\"><font size=2 face=\"Arial,Helvetica\">$moh_id[$k]</a></td>\n";
				echo "<td><font size=2 face=\"Arial,Helvetica\">$moh_name[$k]</td>\n";
				echo "<td><font size=2 face=\"Arial,Helvetica\">$random[$k]</td></tr>\n";

				$stmt="SELECT filename from vicidial_music_on_hold_files where moh_id='$moh_id[$k]';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$mohfiles_to_print = mysqli_num_rows($rslt);
				$m=0;
				$MOHfiles = '';
				while ($mohfiles_to_print > $m)
					{
					$rowx=mysqli_fetch_row($rslt);
					$MOHfiles .=	"$rowx[0] &nbsp; ";
					$m++;
					}

				echo "<tr $bgcolor><td colspan=2 width=100><font size=1 face=\"Arial,Helvetica\">&nbsp;</td>\n";
				echo "<td colspan=3 width=590><font size=2 face=\"Arial,Helvetica\">Files: </font><font size=1 face=\"Arial,Helvetica\">$MOHfiles</td></tr>\n";

				$k++;
				}
			echo "</table></div></body></HTML>\n";

			exit;
			}
		}
	}
################################################################################
### END moh_list
################################################################################




################################################################################
### vm_list - sends a list of the voicemail boxes in the system
################################################################################
if ($function == 'vm_list')
	{
	$stmt="SELECT count(*) from vicidial_users where user='$user' and user_level > 6 and active='Y';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$allowed_user=$row[0];
	if ($allowed_user < 1)
		{
		$result = 'ERROR';
		$result_reason = "vm_list USER DOES NOT HAVE PERMISSION TO VIEW VOICEMAIL BOXES LIST";
		echo "$result: $result_reason: |$user|$allowed_user|\n";
		$data = "$allowed_user";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
		if ($DB>0) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$LOGallowed_campaigns =			$row[0];
		$LOGadmin_viewable_groups =		$row[1];

		$LOGadmin_viewable_groupsSQL='';
		$whereLOGadmin_viewable_groupsSQL='';
		if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
			{
			$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
			$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
			$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
			$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
			}

		$server_name = getenv("SERVER_NAME");
		$server_port = getenv("SERVER_PORT");
		if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
		  else {$HTTPprotocol = 'http://';}
		// $admDIR = "$HTTPprotocol$server_name:$server_port";
		// By setting this variable to empty, we'll use the same protocol and port as per the original request
		$admDIR = "";


		echo "\n";
		echo "<HTML><head><title>NON-AGENT API</title>\n";
		echo "<script language=\"Javascript\">\n";
		echo "function choose_file(filename,fieldname)\n";
		echo "	{\n";
		echo "	if (filename.length > 0)\n";
		echo "		{\n";
		echo "		parent.document.getElementById(fieldname).value = filename;\n";
		echo "		document.getElementById(\"selectframe\").innerHTML = '';\n";
		echo "		document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
		echo "		parent.close_chooser();\n";
		echo "		}\n";
		echo "	}\n";
		echo "function close_file()\n";
		echo "	{\n";
		echo "	document.getElementById(\"selectframe\").innerHTML = '';\n";
		echo "	document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
		echo "	parent.close_chooser();\n";
		echo "	}\n";
		echo "</script>\n";
		echo "</head>\n\n";

		echo "<body>\n";
		echo "<a href=\"javascript:close_file();\"><font size=1 face=\"Arial,Helvetica\">"._QXZ("close frame")."</font></a>\n";
		echo "<div id='selectframe' style=\"height:400px;width:710px;overflow:scroll;\">\n";
		echo "<table border=0 cellpadding=1 cellspacing=2 width=690 bgcolor=white><tr>\n";
		echo "<td width=30>#</td>\n";
		echo "<td colspan=2>"._QXZ("Voicemail Boxes")."</td>\n";
		echo "<td>"._QXZ("Name")."</td>\n";
		echo "<td>"._QXZ("Email")."</td>\n";
		echo "</tr>\n";

		$rowx=array();
		$voicemail_id=array();
		$fullname=array();
		$email=array();
		$extension=array();

		$stmt="SELECT voicemail_id,fullname,email from vicidial_voicemail where active='Y' $LOGadmin_viewable_groupsSQL order by voicemail_id";
		$rslt=mysql_to_mysqli($stmt, $link);
		$vm_to_print = mysqli_num_rows($rslt);
		$k=0;
		$sf=0;
		while ($vm_to_print > $k)
			{
			$rowx=mysqli_fetch_row($rslt);
			$voicemail_id[$k] =	$rowx[0];
			$fullname[$k] =		$rowx[1];
			$email[$k] =		$rowx[2];
			$sf++;
			if (preg_match("/1$|3$|5$|7$|9$/i", $sf))
				{$bgcolor='bgcolor="#E6E6E6"';}
			else
				{$bgcolor='bgcolor="#F6F6F6"';}
			echo "<tr $bgcolor><td width=30><font size=1 face=\"Arial,Helvetica\">$sf</td>\n";
			echo "<td colspan=2><a href=\"javascript:choose_file('$voicemail_id[$k]','$comments');\"><font size=2 face=\"Arial,Helvetica\">$voicemail_id[$k]</a></td>\n";
			echo "<td><font size=2 face=\"Arial,Helvetica\">$fullname[$k]</td>\n";
			echo "<td><font size=2 face=\"Arial,Helvetica\">$email[$k]</td></tr>\n";

			$k++;
			}

		$stmt="SELECT voicemail_id,fullname,email,extension from phones where active='Y' $LOGadmin_viewable_groupsSQL order by voicemail_id";
		$rslt=mysql_to_mysqli($stmt, $link);
		$vm_to_print = mysqli_num_rows($rslt);
		$k=0;
		$sf=0;
		while ($vm_to_print > $k)
			{
			$rowx=mysqli_fetch_row($rslt);
			$voicemail_id[$k] =	$rowx[0];
			$fullname[$k] =		$rowx[1];
			$email[$k] =		$rowx[2];
			$extension[$k] =	$rowx[3];
			$sf++;
			if (preg_match("/1$|3$|5$|7$|9$/i", $sf))
				{$bgcolor='bgcolor="#E6E6E6"';}
			else
				{$bgcolor='bgcolor="#F6F6F6"';}
			echo "<tr $bgcolor><td width=30><font size=1 face=\"Arial,Helvetica\">$sf</td>\n";
			echo "<td colspan=2><a href=\"javascript:choose_file('$voicemail_id[$k]','$comments');\"><font size=2 face=\"Arial,Helvetica\">$voicemail_id[$k]</a></td>\n";
			echo "<td><font size=2 face=\"Arial,Helvetica\">$extension[$k] - $fullname[$k]</td>\n";
			echo "<td><font size=2 face=\"Arial,Helvetica\">$email[$k]</td></tr>\n";

			$k++;
			}
		echo "</table></div></body></HTML>\n";

		exit;
		}
	}
################################################################################
### END vm_list
################################################################################




################################################################################
### ingroup_list - sends a list of the inbound groups in the system
################################################################################
if ($function == 'ingroup_list')
	{
	$stmt="SELECT count(*) from vicidial_users where user='$user' and user_level > 6 and active='Y';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$allowed_user=$row[0];
	if ($allowed_user < 1)
		{
		$result = 'ERROR';
		$result_reason = "ingroup_list USER DOES NOT HAVE PERMISSION TO VIEW INBOUND GROUPS LIST";
		echo "$result: $result_reason: |$user|$allowed_user|\n";
		$data = "$allowed_user";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
		if ($DB>0) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$LOGallowed_campaigns =			$row[0];
		$LOGadmin_viewable_groups =		$row[1];

		$LOGadmin_viewable_groupsSQL='';
		$whereLOGadmin_viewable_groupsSQL='';
		if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
			{
			$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
			$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
			$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
			$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
			}

		$server_name = getenv("SERVER_NAME");
		$server_port = getenv("SERVER_PORT");
		if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
		  else {$HTTPprotocol = 'http://';}
		$admDIR = "$HTTPprotocol$server_name:$server_port";

		echo "\n";
		echo "<HTML><head><title>NON-AGENT API</title>\n";
		echo "<script language=\"Javascript\">\n";
		echo "function choose_file(filename,fieldname)\n";
		echo "	{\n";
		echo "	if (filename.length > 0)\n";
		echo "		{\n";
		echo "		parent.document.getElementById(fieldname).value = filename;\n";
		echo "		document.getElementById(\"selectframe\").innerHTML = '';\n";
		echo "		document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
		echo "		parent.close_chooser();\n";
		echo "		}\n";
		echo "	}\n";
		echo "function close_file()\n";
		echo "	{\n";
		echo "	document.getElementById(\"selectframe\").innerHTML = '';\n";
		echo "	document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
		echo "	parent.close_chooser();\n";
		echo "	}\n";
		echo "</script>\n";
		echo "</head>\n\n";

		echo "<body>\n";
		echo "<a href=\"javascript:close_file();\"><font size=1 face=\"Arial,Helvetica\">"._QXZ("close frame")."</font></a>\n";
		echo "<div id='selectframe' style=\"height:400px;width:710px;overflow:scroll;\">\n";
		echo "<table border=0 cellpadding=1 cellspacing=2 width=690 bgcolor=white><tr>\n";
		echo "<td width=30>#</td>\n";
		echo "<td colspan=2>"._QXZ("Inbound Groups")."</td>\n";
		echo "<td>"._QXZ("Name")."</td>\n";
		echo "<td>"._QXZ("Color")."</td>\n";
		echo "</tr>\n";

		$rowx=array();
		$group_id=array();
		$fullname=array();
		$color=array();

		$stmt="SELECT group_id,group_name,group_color from vicidial_inbound_groups where active='Y' $LOGadmin_viewable_groupsSQL order by group_id";
		$rslt=mysql_to_mysqli($stmt, $link);
		$vm_to_print = mysqli_num_rows($rslt);
		$k=0;
		$sf=0;
		while ($vm_to_print > $k) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$group_id[$k] =	$rowx[0];
			$fullname[$k] =		$rowx[1];
			$color[$k] =		$rowx[2];
			$sf++;
			if (preg_match("/1$|3$|5$|7$|9$/i", $sf))
				{$bgcolor='bgcolor="#E6E6E6"';} 
			else
				{$bgcolor='bgcolor="#F6F6F6"';}
			echo "<tr $bgcolor><td width=30><font size=1 face=\"Arial,Helvetica\">$sf</td>\n";
			echo "<td colspan=2><a href=\"javascript:choose_file('$group_id[$k]','$comments');\"><font size=2 face=\"Arial,Helvetica\">$group_id[$k]</a></td>\n";
			echo "<td><font size=2 face=\"Arial,Helvetica\">$fullname[$k]</td>\n";
			echo "<td bgcolor=\"$color[$k]\"><font size=2 face=\"Arial,Helvetica\"> &nbsp; &nbsp; &nbsp; &nbsp; </td></tr>\n";

			$k++;
			}
		echo "</table></div></body></HTML>\n";

		exit;
		}
	}
################################################################################
### END ingroup_list
################################################################################




################################################################################
### callmenu_list - sends a list of the call menus in the system
################################################################################
if ($function == 'callmenu_list')
	{
	$stmt="SELECT count(*) from vicidial_users where user='$user' and user_level > 6 and active='Y';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$allowed_user=$row[0];
	if ($allowed_user < 1)
		{
		$result = 'ERROR';
		$result_reason = "callmenu_list USER DOES NOT HAVE PERMISSION TO VIEW CALL MENUS LIST";
		echo "$result: $result_reason: |$user|$allowed_user|\n";
		$data = "$allowed_user";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
		if ($DB>0) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$LOGallowed_campaigns =			$row[0];
		$LOGadmin_viewable_groups =		$row[1];

		$LOGadmin_viewable_groupsSQL='';
		$whereLOGadmin_viewable_groupsSQL='';
		if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
			{
			$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
			$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
			$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
			$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
			}

		$server_name = getenv("SERVER_NAME");
		$server_port = getenv("SERVER_PORT");
		if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
		  else {$HTTPprotocol = 'http://';}
		$admDIR = "$HTTPprotocol$server_name:$server_port";

		echo "\n";
		echo "<HTML><head><title>NON-AGENT API</title>\n";
		echo "<script language=\"Javascript\">\n";
		echo "function choose_file(filename,fieldname)\n";
		echo "	{\n";
		echo "	if (filename.length > 0)\n";
		echo "		{\n";
		echo "		parent.document.getElementById(fieldname).value = filename;\n";
		echo "		document.getElementById(\"selectframe\").innerHTML = '';\n";
		echo "		document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
		echo "		parent.close_chooser();\n";
		echo "		}\n";
		echo "	}\n";
		echo "function close_file()\n";
		echo "	{\n";
		echo "	document.getElementById(\"selectframe\").innerHTML = '';\n";
		echo "	document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
		echo "	parent.close_chooser();\n";
		echo "	}\n";
		echo "</script>\n";
		echo "</head>\n\n";

		echo "<body>\n";
		echo "<a href=\"javascript:close_file();\"><font size=1 face=\"Arial,Helvetica\">"._QXZ("close frame")."</font></a>\n";
		echo "<div id='selectframe' style=\"height:400px;width:710px;overflow:scroll;\">\n";
		echo "<table border=0 cellpadding=1 cellspacing=2 width=690 bgcolor=white><tr>\n";
		echo "<td width=30>#</td>\n";
		echo "<td colspan=2>"._QXZ("Call Menus")."</td>\n";
		echo "<td>"._QXZ("Name")."</td>\n";
	#	echo "<td>"._QXZ("Color")."</td>\n";
		echo "</tr>\n";

		$rowx=array();
		$group_id=array();
		$fullname=array();
		$color=array();

		$stmt="SELECT menu_id,menu_name,menu_prompt from vicidial_call_menu $whereLOGadmin_viewable_groupsSQL order by menu_id";
		$rslt=mysql_to_mysqli($stmt, $link);
		$vm_to_print = mysqli_num_rows($rslt);
		$k=0;
		$sf=0;
		while ($vm_to_print > $k) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$group_id[$k] =	$rowx[0];
			$fullname[$k] =		$rowx[1];
			$color[$k] =		$rowx[2];
			$sf++;
			if (preg_match("/1$|3$|5$|7$|9$/i", $sf))
				{$bgcolor='bgcolor="#E6E6E6"';} 
			else
				{$bgcolor='bgcolor="#F6F6F6"';}
			echo "<tr $bgcolor><td width=30><font size=1 face=\"Arial,Helvetica\">$sf</td>\n";
			echo "<td colspan=2><a href=\"javascript:choose_file('$group_id[$k]','$comments');\"><font size=2 face=\"Arial,Helvetica\">$group_id[$k]</a></td>\n";
			echo "<td><font size=2 face=\"Arial,Helvetica\">$fullname[$k]</td></tr>\n";
		#	echo "<td bgcolor=\"$color[$k]\"><font size=2 face=\"Arial,Helvetica\"> &nbsp; &nbsp; &nbsp; &nbsp; </td></tr>\n";

			$k++;
			}
		echo "</table></div></body></HTML>\n";

		exit;
		}
	}
################################################################################
### END callmenu_list
################################################################################




################################################################################
### container_list - sends a list of the settings containers in the system
################################################################################
if ($function == 'container_list')
	{
	if (strlen($type) < 1)
		{
		$result = 'ERROR';
		$result_reason = "container_list NO CONTAINER TYPE DEFINED";
		echo "$result: $result_reason: |$user|$type|\n";
		$data = "$allowed_user";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	$stmt="SELECT count(*) from vicidial_users where user='$user' and user_level > 6 and active='Y';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$allowed_user=$row[0];
	if ($allowed_user < 1)
		{
		$result = 'ERROR';
		$result_reason = "container_list USER DOES NOT HAVE PERMISSION TO VIEW CALL MENUS LIST";
		echo "$result: $result_reason: |$user|$allowed_user|\n";
		$data = "$allowed_user";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
		if ($DB>0) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$LOGallowed_campaigns =			$row[0];
		$LOGadmin_viewable_groups =		$row[1];

		$LOGadmin_viewable_groupsSQL='';
		$whereLOGadmin_viewable_groupsSQL='';
		if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
			{
			$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
			$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
			$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
			$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
			}

		$server_name = getenv("SERVER_NAME");
		$server_port = getenv("SERVER_PORT");
		if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
		  else {$HTTPprotocol = 'http://';}
		$admDIR = "$HTTPprotocol$server_name:$server_port";

		echo "\n";
		echo "<HTML><head><title>NON-AGENT API</title>\n";
		echo "<script language=\"Javascript\">\n";
		echo "function choose_file(filename,fieldname)\n";
		echo "	{\n";
		echo "	if (filename.length > 0)\n";
		echo "		{\n";
		echo "		parent.document.getElementById(fieldname).value = filename;\n";
		echo "		document.getElementById(\"selectframe\").innerHTML = '';\n";
		echo "		document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
		echo "		parent.close_chooser();\n";
		echo "		}\n";
		echo "	}\n";
		echo "function close_file()\n";
		echo "	{\n";
		echo "	document.getElementById(\"selectframe\").innerHTML = '';\n";
		echo "	document.getElementById(\"selectframe\").style.visibility = 'hidden';\n";
		echo "	parent.close_chooser();\n";
		echo "	}\n";
		echo "</script>\n";
		echo "</head>\n\n";

		echo "<body>\n";
		echo "<a href=\"javascript:close_file();\"><font size=1 face=\"Arial,Helvetica\">"._QXZ("close frame")."</font></a>\n";
		echo "<div id='selectframe' style=\"height:400px;width:710px;overflow:scroll;\">\n";
		echo "<table border=0 cellpadding=1 cellspacing=2 width=690 bgcolor=white><tr>\n";
		echo "<td width=30>#</td>\n";
		echo "<td colspan=2>"._QXZ("Settings Containers").": <br>"._QXZ("$type")." "._QXZ("type")."</td>\n";
		echo "<td>"._QXZ("Container Notes")."</td>\n";
	#	echo "<td>"._QXZ("Color")."</td>\n";
		echo "</tr>\n";

		$rowx=array();
		$group_id=array();
		$fullname=array();

		$stmt="SELECT container_id,container_notes from vicidial_settings_containers where container_type='$type' $LOGadmin_viewable_groupsSQL order by container_id";
		$rslt=mysql_to_mysqli($stmt, $link);
		$vm_to_print = mysqli_num_rows($rslt);
		$k=0;
		$sf=0;
		while ($vm_to_print > $k) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$group_id[$k] =	$rowx[0];
			$fullname[$k] =		$rowx[1];
			$sf++;
			if (preg_match("/1$|3$|5$|7$|9$/i", $sf))
				{$bgcolor='bgcolor="#E6E6E6"';} 
			else
				{$bgcolor='bgcolor="#F6F6F6"';}
			echo "<tr $bgcolor><td width=30><font size=1 face=\"Arial,Helvetica\">$sf</td>\n";
			echo "<td colspan=2><a href=\"javascript:choose_file('$group_id[$k]','$comments');\"><font size=2 face=\"Arial,Helvetica\">$group_id[$k]</a></td>\n";
			echo "<td><font size=2 face=\"Arial,Helvetica\">$fullname[$k]</td></tr>\n";

			$k++;
			}
	#	echo "<tr><td>$stmt</td></tr\n";
		echo "</table></div></body></HTML>\n";

		exit;
		}
	}
################################################################################
### END container_list
################################################################################





################################################################################
### agent_ingroup_info - displays agent in-group info in an HTML form allowing for changes
################################################################################
if ($function == 'agent_ingroup_info')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ( ($allowed_user < 1) and ($source != 'queuemetrics') )
			{
			$result = 'ERROR';
			$result_reason = "agent_ingroup_info USER DOES NOT HAVE PERMISSION TO GET AGENT INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
			if ($DB>0) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$LOGallowed_campaigns =			$row[0];
			$LOGadmin_viewable_groups =		$row[1];

			$LOGadmin_viewable_groupsSQL='';
			$whereLOGadmin_viewable_groupsSQL='';
			if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
				{
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
				$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				}

			$stmt="SELECT count(*) from vicidial_users where user='$agent_user' $LOGadmin_viewable_groupsSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$admin_permission=$row[0];

			if ($admin_permission < 1)
				{
				$result = 'ERROR';
				$result_reason = "agent_ingroup_info INVALID USER ID";
				echo "$result: $result_reason - $agent_user|$user\n";
				$data = "$session_id";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from vicidial_live_agents where user='$agent_user';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$session_exists=$row[0];

				if ($session_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "agent_ingroup_info INVALID USER ID";
					echo "$result: $result_reason - $agent_user|$user\n";
					$data = "$session_id";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT campaign_id,closer_campaigns,outbound_autodial,manager_ingroup_set,external_igb_set_user,on_hook_agent,on_hook_ring_time from vicidial_live_agents where user='$agent_user';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$campaign_id =				$row[0];
					$closer_campaigns =			$row[1];
					$blended =					$row[2];
					$manager_ingroup_set =		$row[3];
					$external_igb_set_user =	$row[4];
					$on_hook_agent =			$row[5];
					$on_hook_ring_time =		$row[6];

					$stmt="SELECT full_name from vicidial_users where user='$agent_user';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$full_name =				$row[0];

					$stmt = "select count(*) from vicidial_campaigns where campaign_id='$campaign_id' and campaign_allow_inbound='Y' and dial_method NOT IN('MANUAL');";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$allowed_campaign_inbound=$row[0];

					$stmt = "select count(*) from vicidial_campaigns where campaign_id='$campaign_id' and dial_method NOT IN('MANUAL','INBOUND_MAN');";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$allowed_campaign_autodial=$row[0];

					$stmt="SELECT count(*) from vicidial_users where user='$user' and change_agent_campaign='1' and active='Y';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$allowed_user_change_ingroups=$row[0];

					$stmt="SELECT count(*) from vicidial_users where user='$user' and modify_users='1' and active='Y';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$allowed_user_modify_user=$row[0];


					$result = 'SUCCESS';
					$result_reason = "";
					$data = "$agent_user|$stage";

					if ($stage == 'text')
						{
						$output .= "SELECTED INGROUPS: $closer_campaigns\n";
						$output .= "OUTBOUND AUTODIAL: $blended\n";
						$output .= "MANAGER OVERRIDE: $manager_ingroup_set\n";
						$output .= "MANAGER: $external_igb_set_user\n";
						echo "$result: $result_reason - $data\n$output\n";
						}
					else
						{
						$output  = '';
						$output .= "<TABLE WIDTH=680 CELLPADDING=0 CELLSPACING=5 BGCOLOR=\"#D9E6FE\"><TR><TD ALIGN=LEFT>\n";
						$output .= ""._QXZ("Agent").": $agent_user - $full_name </TD><TD>\n";
						$output .= " &nbsp; "._QXZ("Campaign").": $campaign_id</TD><TD>\n";
						$output .= "<a href=\"#\" onclick=\"hide_ingroup_info();\">"._QXZ("Close")."</a></TD></TR><TR><TD COLSPAN=3 BGCOLOR=\"#CCCCFF\">\n";

						$stmt="SELECT closer_campaigns from vicidial_campaigns where campaign_id='$campaign_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						if ($allowed_campaign_inbound < 1)
							{$row[0]='';}
						$closer_groups_pre = preg_replace('/-$/','',$row[0]);
						$closer_groups = explode(" ",$closer_groups_pre);
						$closer_groups_ct = count($closer_groups);

						$in_groups_pre = preg_replace('/-$/','',$closer_campaigns);
						$in_groups = explode(" ",$in_groups_pre);
						$in_groups_ct = count($in_groups);
						$k=1;
						$closer_select=array();

						while ($k < $closer_groups_ct)
							{
							$closer_select[$k]=0;
							if (strlen($closer_groups[$k])>1)
								{
								$m=0;
								while ($m < $in_groups_ct)
									{
									if (strlen($in_groups[$m])>1)
										{
										if ($closer_groups[$k] == $in_groups[$m])
											{$closer_select[$k]++;}
										}
									$m++;
									}
								}
							$k++;
							}

						if ( ($allowed_user_change_ingroups > 0) and ($stage == 'change') )
							{
							$output .= "<TABLE CELLPADDING=0 CELLSPACING=3 BORDER=0>\n";
							if ($on_hook_agent == 'Y')
								{
								$output .= "<TR><TD ALIGN=CENTER VALIGN=TOP COLSPAN=2><B>"._QXZ("This is a Phone On-Hook Agent")."</B> &nbsp; "._QXZ("Maximum Ring Time").":  $on_hook_ring_time</TD></TR>\n";
								}
							$output .= "<TR><TD ALIGN=RIGHT VALIGN=TOP>"._QXZ("Selected In-Groups").": </TD><TD ALIGN=LEFT>\n";
							$output .= "<INPUT TYPE=HIDDEN NAME=agent_user ID=agent_user value=\"$agent_user\">\n";
							$output .= "<SELECT SIZE=10 NAME=ingroup_new_selections ID=ingroup_new_selections multiple>\n";

							$m=0;
							$m_printed=0;
							while ($m < $closer_groups_ct)
								{
								if (strlen($closer_groups[$m])>1)
									{
									$stmt="SELECT group_name from vicidial_inbound_groups where group_id='$closer_groups[$m]';";
									$rslt=mysql_to_mysqli($stmt, $link);
									$row=mysqli_fetch_row($rslt);

									$output .= "<option value=\"$closer_groups[$m]\"";

									if ($closer_select[$m] > 0)
										{$output .= " SELECTED";}
									$output .= ">$closer_groups[$m] - $row[0]</option>\n";
									$m_printed++;
									}
								$m++;
								}

							if ($m_printed < 1)
								{$output .= "<option value=\"\">"._QXZ("No In-Groups Allowed")."</option>\n";}

							$output .= "</SELECT><BR></TD></TR>\n";

							$output .= "<TR><TD ALIGN=RIGHT>"._QXZ("Change, Add, Remove").":\n";
							$output .= "</TD><TD ALIGN=LEFT>\n";
							$output .= "<SELECT SIZE=1 NAME=ingroup_add_remove_change ID=ingroup_add_remove_change>\n";
							$output .= "<option value=\"CHANGE\">"._QXZ("CHANGE - Set in-groups to those selected above")."</option>\n";
							$output .= "<option value=\"ADD\">"._QXZ("ADD - Add in-groups selected above to agent selected")."</option>\n";
							$output .= "<option value=\"REMOVE\">"._QXZ("REMOVE - Remove in-groups selected above from agent selected")."</option>\n";
							$output .= "</SELECT>\n";
							$output .= "</TD></TR>\n";

							$output .= "<TR><TD ALIGN=RIGHT>"._QXZ("Blended Outbound Autodial").":\n";
							$output .= "</TD><TD ALIGN=LEFT>\n";
							$output .= "<SELECT SIZE=1 NAME=blended ID=blended";
							if ($allowed_campaign_autodial < 1)
								{
								$output .= " DISABLED";
								$blended = 'N';
								}
							$output .= ">\n";
							$output .= "<option value=\"YES\"";
							if ($blended == 'Y')
								{$output .= " SELECTED";}
							$output .= ">"._QXZ("Yes")."</option>\n";
							$output .= "<option value=\"NO\"";
							if ($blended == 'N')
								{$output .= " SELECTED";}
							$output .= ">"._QXZ("No")."</option>\n";
							$output .= "</SELECT>\n";
							$output .= "</TD></TR>\n";

							$output .= "<TR><TD ALIGN=RIGHT>"._QXZ("Set As User Default").":\n";
							$output .= "</TD><TD ALIGN=LEFT>\n";
							$output .= "<SELECT SIZE=1 NAME=set_as_default ID=set_as_default";
							if ($allowed_user_modify_user < 1)
								{$output .= " DISABLED";}
							$output .= ">\n";
							$output .= "<option value=\"YES\">"._QXZ("Yes")."</option>\n";
							$output .= "<option value=\"NO\" SELECTED>"._QXZ("No")."</option>\n";
							$output .= "</SELECT>\n";
							$output .= "</TD></TR>\n";

							if ( ($manager_ingroup_set == 'SET') or ($manager_ingroup_set == 'Y') )
								{
								$stmt="SELECT full_name from vicidial_users where user='$external_igb_set_user';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$Mfull_name =				$row[0];

								$output .= "<TR><TD ALIGN=RIGHT>"._QXZ("Manager In-Group Override").":\n";
								$output .= "</TD><TD ALIGN=LEFT>\n";
								$output .= "$manager_ingroup_set - $external_igb_set_user - $Mfull_name\n";
								$output .= "</TD></TR>\n";
								}

							$output .= "<TR><TD ALIGN=CENTER COLSPAN=2>\n";
							$output .= "<INPUT TYPE=BUTTON NAME=SUBMIT ID=SUBMIT VALUE=\""._QXZ("Submit Changes")."\" onclick=\"submit_ingroup_changes('$agent_user')\">\n";
							$output .= "</TD></TR>\n";

							$output .= "</TABLE>\n";
							$output .= "</TD></TR></TABLE>\n";
							}
						else
							{
							$output .= "<TABLE CELLPADDING=0 CELLSPACING=3 BORDER=0>\n";

							$m=0;
							$m_printed=0;
							while ($m < $closer_groups_ct)
								{
								if (strlen($closer_groups[$m])>1)
									{
									$stmt="SELECT group_name from vicidial_inbound_groups where group_id='$closer_groups[$m]';";
									$rslt=mysql_to_mysqli($stmt, $link);
									$row=mysqli_fetch_row($rslt);

									$output .= "<TR><TD>$closer_groups[$m]";

									if ($closer_select[$m] > 0)
										{$output .= " *";}
									$output .= "</TD><TD>$row[0]</TD></TR>\n";
									$m_printed++;
									}
								$m++;
								}

							if ($m_printed < 1)
								{$output .= "<TR><TD>"._QXZ("No In-Groups Allowed")."</TD></TR>\n";}

							$output .= "</TABLE><BR>\n";

							$output .= ""._QXZ("SELECTED INGROUPS").": $closer_campaigns<BR>\n";
							$output .= ""._QXZ("OUTBOUND AUTODIAL").": $blended<BR>\n";
							$output .= ""._QXZ("MANAGER OVERRIDE").": $manager_ingroup_set<BR>\n";
							$output .= ""._QXZ("MANAGER").": $external_igb_set_user<BR>\n";
							$output .= "\n";
							$output .= "</TD></TR></TABLE>\n";
							}

						echo "$output";
						}

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END agent_ingroup_info
################################################################################





################################################################################
### agent_campaigns - looks up allowed campaigns/in-groups for a specific user
################################################################################
if ($function == 'agent_campaigns')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 7 and modify_users='1' and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "agent_campaigns USER DOES NOT HAVE PERMISSION TO GET AGENT INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT user_level,user_group from vicidial_users where user='$user' and vdc_agent_api_access='1' and user_level >= 8;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$user_level =				$row[0];
			$LOGuser_group =			$row[1];

			$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$LOGallowed_campaigns =			$row[0];
			$LOGadmin_viewable_groups =		$row[1];

			$LOGadmin_viewable_groupsSQL='';
			$whereLOGadmin_viewable_groupsSQL='';
			if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
				{
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
				$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				}

			$stmt="SELECT count(*) from vicidial_users where user='$agent_user' $LOGadmin_viewable_groupsSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$group_exists=$row[0];
			if ($group_exists < 1)
				{
				$result = 'ERROR';
				$result_reason = "agent_campaigns AGENT USER DOES NOT EXIST";
				$data = "$agent_user";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT user_group from vicidial_users where user='$agent_user';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$aLOGuser_group =			$row[0];

				$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$aLOGuser_group';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$aLOGallowed_campaigns =	$row[0];

				$aLOGallowed_campaignsSQL='';
				$AwhereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $aLOGallowed_campaigns)) )
					{
					$ArawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$aLOGallowed_campaigns);
					$ArawLOGallowed_campaignsSQL = preg_replace("/ /","','",$ArawLOGallowed_campaignsSQL);
					$aLOGallowed_campaignsSQL = "and campaign_id IN('$ArawLOGallowed_campaignsSQL')";
					$AwhereLOGallowed_campaignsSQL = "where campaign_id IN('$ArawLOGallowed_campaignsSQL')";
					}

				$campaignSQL='';
				if (strlen($campaign_id) > 0) {$campaignSQL = "and campaign_id='$campaign_id'";}
				$campaigns_list='';
				$closer_campaigns_list='';

				$stmt="SELECT campaign_id,closer_campaigns from vicidial_campaigns where active='Y' $aLOGallowed_campaignsSQL $campaignSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$li_recs = mysqli_num_rows($rslt);
				$L=0;
				while ($li_recs > $L)
					{
					$row=mysqli_fetch_row($rslt);
					$campaigns_list .=	"'$row[0]',";
					$campaigns_output_list .=	$row[0]."-";
					$row[1] = preg_replace('/-$/','',$row[1]);
					$row[1] = preg_replace("/ /","','",$row[1]);
					$closer_campaigns_list .=	"'$row[1]',";
					$L++;
					}
				$campaigns_list = preg_replace('/,$/i', '',$campaigns_list);
				$campaigns_output_list = preg_replace('/-$/i', '',$campaigns_output_list);
				$closer_campaigns_list = preg_replace('/,$/i', '',$closer_campaigns_list);
				$closer_campaigns_list = preg_replace("/'',/",'',$closer_campaigns_list);
				$closer_campaigns_list = preg_replace("/,''/",'',$closer_campaigns_list);
				if ($DB > 0) {echo "DEBUG: |$campaigns_list|$campaigns_output_list|$closer_campaigns_list|\n";}
				}
			if (strlen($campaigns_list) < 3)
				{
				$result = 'ERROR';
				$result_reason = "agent_campaigns THIS AGENT USER HAS NO AVAILABLE CAMPAIGNS";
				$data = "$agent_user|$user|";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$ingroup_output_list='';
				if (strlen($closer_campaigns_list) > 2)
					{
					$excludeAGENTDIRECTsql='';
					if ($ignore_agentdirect == 'Y') {$excludeAGENTDIRECTsql = 'and group_id NOT LIKE "AGENTDIRECT%"';}
					$stmt="SELECT group_id from vicidial_inbound_groups where active='Y' and group_id IN($closer_campaigns_list) $excludeAGENTDIRECTsql;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$ig_recs = mysqli_num_rows($rslt);
					$M=0;
					while ($ig_recs > $M)
						{
						$row=mysqli_fetch_row($rslt);
						$ingroup_output_list .=	$row[0]."-";
						$M++;
						}
					$ingroup_output_list = preg_replace('/-$/i', '',$ingroup_output_list);
					}

				$output='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';}
				if ($header == 'YES')
					{$output .= 'user' . $DL . 'allowed_campaigns_list' . $DL . "allowed_ingroups_list\n";}

				$output .= "$agent_user$DL$campaigns_output_list$DL$ingroup_output_list\n";

				echo "$output";

				$result = 'SUCCESS';
				$data = "$user|$agent_user|$campaigns_output_list|$ingroup_output_list";
				$result_reason = "agent_campaigns RESULTS FOUND: 1";

				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		}
	exit;
	}
################################################################################
### END agent_campaigns
################################################################################





################################################################################
### campaigns_list - displays information about all campaigns in the system
################################################################################
if ($function == 'campaigns_list')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "campaigns_list USER DOES NOT HAVE PERMISSION TO GET CAMPAIGN INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT user_level,user_group from vicidial_users where user='$user' and vdc_agent_api_access='1' and user_level >= 8;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$user_level =				$row[0];
			$LOGuser_group =			$row[1];

			$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$LOGallowed_campaigns =			$row[0];
			$LOGadmin_viewable_groups =		$row[1];

			$LOGadmin_viewable_groupsSQL='';
			$whereLOGadmin_viewable_groupsSQL='';
			if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
				{
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
				$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				}
			if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
				{
				$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
				$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
				$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
				$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
				}

			$campaignSQL='';
			if (strlen($campaign_id) > 0) {$campaignSQL = "and campaign_id='$campaign_id'";}
			$campaigns_list='';
			$CLoutput='';
			$DLset=0;
			if ($stage == 'csv')
				{$DL = ',';   $DLset++;}
			if ($stage == 'tab')
				{$DL = "\t";   $DLset++;}
			if ($stage == 'pipe')
				{$DL = '|';   $DLset++;}
			if ($DLset < 1)
				{$DL='|';}
			if ($header == 'YES')
				{$CLoutput .= 'campaign_id' . $DL . 'campaign_name' . $DL . 'active' . $DL . 'user_group' . $DL . 'dial_method' . $DL . 'dial_level' . $DL . 'lead_order' . $DL . 'dial_statuses' . $DL . 'dial_timeout' . $DL . 'dial_prefix' . $DL . 'manual_dial_prefix' . $DL . 'three_way_dial_prefix'."\n";}

			$stmt="SELECT campaign_id,campaign_name,active,user_group,dial_method,auto_dial_level,lead_order,dial_statuses,dial_timeout,dial_prefix,manual_dial_prefix,three_way_dial_prefix from vicidial_campaigns where active IN('Y','N') $LOGallowed_campaignsSQL $campaignSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$li_recs = mysqli_num_rows($rslt);
			$L=0;
			while ($li_recs > $L)
				{
				$row=mysqli_fetch_row($rslt);
				$campaigns_list .=	"'$row[0]',";
				$row[7] = preg_replace('/^ | -$/i', '',$row[7]);
				$CLoutput .=	"$row[0]$DL$row[1]$DL$row[2]$DL$row[3]$DL$row[4]$DL$row[5]$DL$row[6]$DL$row[7]$DL$row[8]$DL$row[9]$DL$row[10]$DL$row[11]\n";
				$L++;
				}
			$campaigns_list = preg_replace('/,$/i', '',$campaigns_list);
			if ($DB > 0) {echo "DEBUG: $L|$campaigns_list|\n";}

			if (strlen($campaigns_list) < 3)
				{
				$result = 'ERROR';
				$result_reason = "campaigns_list THIS USER HAS NO VIEWABLE CAMPAIGNS";
				$data = "$user|";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				echo "$CLoutput";

				$result = 'SUCCESS';
				$data = "$user|$campaigns_list|";
				$result_reason = "campaigns_list RESULTS FOUND: $L";

				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		}
	exit;
	}
################################################################################
### END campaigns_list
################################################################################





################################################################################
### hopper_list - displays information about leads in the hopper for a campaign
################################################################################
if ($function == 'hopper_list')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "hopper_list USER DOES NOT HAVE PERMISSION TO GET CAMPAIGN INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT user_level,user_group from vicidial_users where user='$user' and vdc_agent_api_access='1' and user_level >= 8;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$user_level =				$row[0];
			$LOGuser_group =			$row[1];

			$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$LOGallowed_campaigns =			$row[0];
			$LOGadmin_viewable_groups =		$row[1];

			$LOGadmin_viewable_groupsSQL='';
			$whereLOGadmin_viewable_groupsSQL='';
			if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
				{
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
				$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				}
			if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
				{
				$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
				$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
				$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
				$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
				}

			$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' $LOGallowed_campaignsSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$allowed_user=$row[0];
			if ($allowed_user < 1)
				{
				$result = 'ERROR';
				$result_reason = "hopper_list THIS CAMPAIGN DOES NOT EXIST";
				echo "$result: $result_reason: |$user|$campaign_id\n";
				$data = "$allowed_user";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$campaignSQL = "and vicidial_hopper.campaign_id='$campaign_id'";
				$Hlead_id=array();
				$Lphone_number=array();
				$Hstate=array();
				$Lstatus=array();
				$Lcalled_count=array();
				$Hgmt_offset_now=array();
				$Halt_dial=array();
				$Hlist_id=array();
				$Hpriority=array();
				$Hsource=array();
				$Hvendor_lead_code=array();
				$Lphone_code=array();
				$Lentry_epoch=array();
				$Llast_call_epoch=array();
				$Lsource_id=array();
				$Lrank=array();
				$CLoutput='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';}
				if ($header == 'YES')
					{
					$CLoutput .= 'hopper_order' . $DL . 'priority' . $DL . 'lead_id' . $DL . 'list_id' . $DL . 'phone_number' . $DL . 'phone_code' . $DL . 'state' . $DL . 'status' . $DL . 'count' . $DL . 'gmt_offset' . $DL . 'rank' . $DL . 'alt' . $DL . 'hopper_source' . $DL . 'vendor_lead_code' . $DL . 'source_id' . $DL . 'age_days' . $DL . 'last_call_time'."\n";
					}

				if (preg_match("/BLOCK/",$search_method))
					{
					$stmt="SELECT vicidial_hopper.lead_id,phone_number,vicidial_hopper.state,vicidial_list.status,called_count,vicidial_hopper.gmt_offset_now,hopper_id,alt_dial,vicidial_hopper.list_id,vicidial_hopper.priority,vicidial_hopper.source,vicidial_hopper.vendor_lead_code, phone_code,UNIX_TIMESTAMP(entry_date),UNIX_TIMESTAMP(last_local_call_time),source_id,vicidial_list.rank from vicidial_hopper,vicidial_list where vicidial_hopper.status='READY' and vicidial_hopper.lead_id=vicidial_list.lead_id $LOGallowed_campaignsSQL $campaignSQL order by priority desc,hopper_id limit 10000;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$hl_recs = mysqli_num_rows($rslt);
						if ($DB > 0) {echo "DEBUG: $hl_recs|$stmt|\n";}
					$L=0;
					while ($hl_recs > $L)
						{
						$row=mysqli_fetch_row($rslt);
						$Hlead_id[$L] =				$row[0];
						$Lphone_number[$L] =		$row[1];
						$Hstate[$L] =				$row[2];
						$Lstatus[$L] =				$row[3];
						$Lcalled_count[$L] =		$row[4];
						$Hgmt_offset_now[$L] =		$row[5];
						$Halt_dial[$L] =			$row[7];
						$Hlist_id[$L] =				$row[8];
						$Hpriority[$L] =			$row[9];
						$Hsource[$L] =				$row[10];
						$Hvendor_lead_code[$L] =	$row[11];
						$Lphone_code[$L] =			$row[12];
						$Lentry_epoch[$L] =			$row[13];
						$Llast_call_epoch[$L] =		$row[14];
						$Lsource_id[$L] =			$row[15];
						$Lrank[$L] =				$row[16];

						$L++;
						}
					}
				else
					{
				$stmt="SELECT lead_id,status,user,list_id,gmt_offset_now,state,alt_dial,priority,source,vendor_lead_code from vicidial_hopper where status='READY' $LOGallowed_campaignsSQL $campaignSQL order by priority desc,hopper_id limit 10000;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$hl_recs = mysqli_num_rows($rslt);
						if ($DB > 0) {echo "DEBUG: $hl_recs|$stmt|\n";}
				$L=0;
				while ($hl_recs > $L)
					{
					$row=mysqli_fetch_row($rslt);
					$Hlead_id[$L] =				$row[0];
					$Hlist_id[$L] =				$row[3];
					$Hgmt_offset_now[$L] =		$row[4];
					$Hstate[$L] =				$row[5];
					$Halt_dial[$L] =			$row[6];
					$Hpriority[$L] =			$row[7];
					$Hsource[$L] =				$row[8];
					$Hvendor_lead_code[$L] =	$row[9];
					$L++;
					}
				if ($DB > 0) {echo "DEBUG: $L hopper records\n";}
					
					$L=0;
					while ($hl_recs > $L)
					{
						$stmt="SELECT phone_number,status,called_count,phone_code,UNIX_TIMESTAMP(entry_date),UNIX_TIMESTAMP(last_local_call_time),source_id,rank from vicidial_list where lead_id='$Hlead_id[$L]';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$vl_recs = mysqli_num_rows($rslt);
						if ($vl_recs > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$Lphone_number[$L] =	$row[0];
							$Lstatus[$L] =			$row[1];
							$Lcalled_count[$L] =	$row[2];
							$Lphone_code[$L] =		$row[3];
							$Lentry_epoch[$L] =		$row[4];
							$Llast_call_epoch[$L] =	$row[5];
							$Lsource_id[$L] =		$row[6];
							$Lrank[$L] =			$row[7];
							}
						$L++;
						}
					}
				if ($L < 1)
					{
					$result = 'ERROR';
					$result_reason = "hopper_list THERE ARE NO LEADS IN THE HOPPER FOR THIS CAMPAIGN";
					$data = "$user|$campaign_id";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$L=0;
					while ($hl_recs > $L)
						{
						$lead_age = intval(($StarTtime - $Lentry_epoch[$L]) / 86400);

							$lead_offset = ($Hgmt_offset_now[$L] - $SERVER_GMT);
							if (($lead_offset > 0) or ($lead_offset < 0))
								{$lead_offset = ($lead_offset * 3600);}
						$Llast_call_epoch[$L] = ($Llast_call_epoch[$L] + $lead_offset);
						$last_call_age = intval(($StarTtime - $Llast_call_epoch[$L]) / 3600);
						if ($DB > 0) {echo "GMT: $lead_offset($gmt|$SERVER_GMT)|LC: $Llast_call_epoch[$L]($StarTtime)|$last_call_age|\n";}
							if ($last_call_age < 24)
								{
								$last_call_age_TEXT = $last_call_age." "._QXZ("HOURS",6);
								}
							else
								{
								$last_call_age = intval(($last_call_age) / 24);
								if ($last_call_age < 365)
									{
									$last_call_age_TEXT = $last_call_age." "._QXZ("DAYS",6);
									}
								else
									{
									$last_call_age = intval(($last_call_age) / 365);
									if ($last_call_age < 30)
										{
										$last_call_age_TEXT = $last_call_age." "._QXZ("YEARS",6);
										}
									else
										{
										$last_call_age_TEXT = _QXZ("NEVER",9);
										}
									}
								}
						$CLoutput .= $L . $DL . $Hpriority[$L] . $DL . $Hlead_id[$L] . $DL . $Hlist_id[$L] . $DL . $Lphone_number[$L] . $DL . $Lphone_code[$L] . $DL . $Hstate[$L] . $DL . $Lstatus[$L] . $DL . $Lcalled_count[$L] . $DL . $Hgmt_offset_now[$L] . $DL . $Lrank[$L] . $DL . $Halt_dial[$L] . $DL . $Hsource[$L] . $DL . $Hvendor_lead_code[$L] . $DL . $Lsource_id[$L] . $DL . $lead_age . $DL . $last_call_age_TEXT."\n";

						$L++;
						}
					echo "$CLoutput";

					$result = 'SUCCESS';
					$data = "$user|$hopper_list|";
					$result_reason = "hopper_list RESULTS FOUND: $L";

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END hopper_list
################################################################################





################################################################################
### blind_monitor - sends call to phone from session from listening
################################################################################
if ($function == 'blind_monitor')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ( ($allowed_user < 1) and ($source != 'queuemetrics') )
			{
			$result = 'ERROR';
			$result_reason = "blind_monitor USER DOES NOT HAVE PERMISSION TO BLIND MONITOR";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt ="SELECT conf_engine from servers where server_ip='$server_ip';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$conf_engine=$row[0];
			
			if($conf_engine == "CONFBRIDGE")
				{
				$conf_table = "vicidial_confbridges";
				$monitor_prefix = '4';
				$barge_prefix = '6';
				}
			else
				{
				$conf_table = "vicidial_conferences";
				$monitor_prefix = '0';
				$barge_prefix = '';
				}
				
			$stmt="SELECT count(*) from $conf_table where conf_exten='$session_id' and server_ip='$server_ip';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$session_exists=$row[0];

			if ($session_exists < 1)
				{
				$result = 'ERROR';
				$result_reason = "blind_monitor INVALID SESSION ID";
				echo "$result: $result_reason - $session_id|$server_ip|$user\n";
				$data = "$session_id";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from phones where login='$phone_login';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$phone_exists=$row[0];

				if ( ($phone_exists < 1) and ($source != 'queuemetrics') )
					{
					$result = 'ERROR';
					$result_reason = "blind_monitor INVALID PHONE LOGIN";
					echo "$result: $result_reason - $phone_login|$user\n";
					$data = "$phone_login";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					if ($source == 'queuemetrics')
						{
						$stmt="SELECT active_voicemail_server from system_settings;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$monitor_server_ip =	$row[0];
						$dialplan_number =		$phone_login;
						$outbound_cid =			'';
						if (strlen($monitor_server_ip)<7)
							{$monitor_server_ip = $server_ip;}
						}
					else
						{
						$stmt="SELECT dialplan_number,server_ip,outbound_cid from phones where login='$phone_login';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$dialplan_number =	$row[0];
						$monitor_server_ip =$row[1];
						$outbound_cid =		$row[2];
						}

					$S='*';
					$D_s_ip = explode('.', $server_ip);
					if (strlen($D_s_ip[0])<2) {$D_s_ip[0] = "0$D_s_ip[0]";}
					if (strlen($D_s_ip[0])<3) {$D_s_ip[0] = "0$D_s_ip[0]";}
					if (strlen($D_s_ip[1])<2) {$D_s_ip[1] = "0$D_s_ip[1]";}
					if (strlen($D_s_ip[1])<3) {$D_s_ip[1] = "0$D_s_ip[1]";}
					if (strlen($D_s_ip[2])<2) {$D_s_ip[2] = "0$D_s_ip[2]";}
					if (strlen($D_s_ip[2])<3) {$D_s_ip[2] = "0$D_s_ip[2]";}
					if (strlen($D_s_ip[3])<2) {$D_s_ip[3] = "0$D_s_ip[3]";}
					if (strlen($D_s_ip[3])<3) {$D_s_ip[3] = "0$D_s_ip[3]";}
					$monitor_dialstring = "$D_s_ip[0]$S$D_s_ip[1]$S$D_s_ip[2]$S$D_s_ip[3]$S";

					$monitor_type='LISTEN'; $cid_prefix='BM'; $swap_chan=0;
					if ( (preg_match('/MONITOR/',$stage)) or (strlen($stage)<1) ) {$stage = $monitor_prefix;}
					if (preg_match('/BARGE/',$stage)) 
						{
						if (preg_match('/SWAP/',$stage)) {$swap_chan=1;}
						$stage = $barge_prefix; $monitor_type='BARGE'; $cid_prefix='BB';
						}
					if (preg_match('/HIJACK/',$stage)) {$stage = ''; $monitor_type='HIJACK'; $cid_prefix='BB';}
					if (preg_match('/WHISPER/',$stage))
						{
						if ($agent_whisper_enabled == '1')
							{$stage = '47378218'; $monitor_type='WHISPER'; $cid_prefix='BW';}
						else
							{
							# WHISPER not enabled
							$stage = '0';
							}
						}

					$PADuser = sprintf("%08s", $user);
						while (strlen($PADuser) > 8) {$PADuser = substr("$PADuser", 0, -1);}
					$BMquery = "$cid_prefix$StarTtime$PADuser";

					$stmt = "SELECT user,lead_id,campaign_id,status FROM vicidial_live_agents where conf_exten='$session_id' and server_ip='$server_ip';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$qm_conf_ct = mysqli_num_rows($rslt);
					if ($qm_conf_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$AGENTuser =		$row[0];
						$AGENTlead_id =		$row[1];
						$AGENTcampaign =	$row[2];
						$AGENTstatus =		$row[3];
						}

					### insert a new lead in the system with this phone number
					$stmt = "INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$monitor_server_ip','','Originate','$BMquery','Channel: Local/$monitor_dialstring$stage$session_id@default','Context: default','Exten: $dialplan_number','Priority: 1','Callerid: \"$BMquery\" <$outbound_cid>','','','','','');";
					if ($swap_chan > 0)
						{
						$stmt = "INSERT INTO vicidial_manager values('','','$NOW_TIME','NEW','N','$monitor_server_ip','','Originate','$BMquery','Channel: Local/$dialplan_number@default','Context: default','Exten: $monitor_dialstring$stage$session_id','Priority: 1','Callerid: \"$BMquery\" <$outbound_cid>','','','','','');";
						}
					if ($DB>0) {echo "DEBUG: blind_monitor query - $stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$affected_rows = mysqli_affected_rows($link);
					if ($affected_rows > 0)
						{
						$man_id = mysqli_insert_id($link);

						$stmt = "INSERT INTO vicidial_dial_log SET caller_code='$BMquery',lead_id='0',server_ip='$monitor_server_ip',call_date='$NOW_TIME',extension='$dialplan_number',channel='Local/$monitor_dialstring$stage$session_id@default',timeout='0',outbound_cid='\"$BMquery\" <$outbound_cid>',context='default';";
						$rslt=mysql_to_mysqli($stmt, $link);

						### log outbound call in the dial cid log
						$stmt = "INSERT INTO vicidial_dial_cid_log SET caller_code='$BMquery',call_date='$NOW_TIME',call_type='MANUAL',call_alt='MAIN', outbound_cid='$outbound_cid',outbound_cid_type='MONITOR_AGENT';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);

						$stmt = "INSERT INTO vicidial_rt_monitor_log SET manager_user='$user',manager_server_ip='$monitor_server_ip',manager_phone='$phone_login',manager_ip='$ip',agent_user='$AGENTuser',agent_server_ip='$server_ip',agent_status='$AGENTstatus',agent_session='$session_id',lead_id='$AGENTlead_id',campaign_id='$AGENTcampaign',caller_code='$BMquery',monitor_start_time=NOW(),monitor_type='$monitor_type';";
						$rslt=mysql_to_mysqli($stmt, $link);

						$stmt = "INSERT INTO vicidial_daily_rt_monitor_log SET manager_user='$user',manager_server_ip='$monitor_server_ip',manager_phone='$phone_login',manager_ip='$ip',agent_user='$AGENTuser',agent_server_ip='$server_ip',agent_status='$AGENTstatus',agent_session='$session_id',lead_id='$AGENTlead_id',campaign_id='$AGENTcampaign',caller_code='$BMquery',monitor_start_time=NOW(),monitor_type='$monitor_type';";
						$rslt=mysql_to_mysqli($stmt, $link);

						##### BEGIN log visit to the vicidial_report_log table #####
						$endMS = microtime();
						$startMSary = explode(" ",$startMS);
						$endMSary = explode(" ",$endMS);
						$runS = ($endMSary[0] - $startMSary[0]);
						$runM = ($endMSary[1] - $startMSary[1]);
						$TOTALrun = ($runS + $runM);
						$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$user', ip_address='1.1.1.1', report_name='API Blind Monitor', browser='API', referer='realtime_report.php', notes='$user, $monitor_server_ip, $dialplan_number, $session_id, $phone_login', url='REALTIME BLIND MONITOR',run_time='$TOTALrun';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						##### END log visit to the vicidial_report_log table #####

						$result = 'SUCCESS';
						$result_reason = "blind_monitor HAS BEEN LAUNCHED";
						echo "$result: $result_reason - $phone_login|$monitor_dialstring$stage$session_id|$dialplan_number|$session_id|$man_id|$user\n";
						$data = "$phone_login|$monitor_dialstring|$session_id|$man_id";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END blind_monitor
################################################################################



### BEGIN optional logging to vicidial_url_log for non-interface URL calls ###
if ($api_url_log > 0)
	{
	$ip = getenv("REMOTE_ADDR");
	$REQUEST_URI = getenv("REQUEST_URI");
	$POST_URI = '';
	foreach($_POST as $key=>$value)
		{$POST_URI .= '&'.$key.'='.$value;}
	$REQUEST_URI = preg_replace("/'|\"|\\\\|;/","",$REQUEST_URI);
	$POST_URI = preg_replace("/'|\"|\\\\|;/","",$POST_URI);
	$NOW_DATE = date("Y-m-d");
	$NOW_TIME = date("Y-m-d H:i:s");
	$stmt="INSERT INTO vicidial_url_log set uniqueid='$NOW_DATE',url_date='$NOW_TIME',url_type='non-agent',url='$REQUEST_URI$POST_URI',url_response='$ip';";
	$rslt=mysql_to_mysqli($stmt, $link);
	}
### END optional logging to vicidial_url_log for non-interface URL calls ###


################################################################################
### add_user - adds user to the vicidial_users table
################################################################################
if ($function == 'add_user')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_users='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_user USER DOES NOT HAVE PERMISSION TO ADD USERS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($agent_user)<2) or (strlen($agent_pass)<2) or (strlen($agent_user_level)<1) or (strlen($agent_full_name)<1) or (strlen($agent_user_group)<1) )
				{
				$result = 'ERROR';
				$result_reason = "add_user YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$agent_user|$agent_pass|$agent_user_level|$agent_full_name|$agent_user_group";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT user_level,user_group,modify_same_user_level from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_users='1' and user_level >= 8;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$user_level =				$row[0];
				$LOGuser_group =			$row[1];
				$modify_same_user_level =	$row[2];

				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				if ( ( ($user_level < 9) and ($user_level <= $agent_user_level) ) or ( ($modify_same_user_level < 1) and ($user_level >= 9) and ($user_level==$agent_user_level) ) )
					{
					$result = 'ERROR';
					$result_reason = "add_user USER DOES NOT HAVE PERMISSION TO ADD USERS IN THIS USER LEVEL";
					$data = "$agent_user_level|$user_level";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT count(*) from vicidial_user_groups where user_group='$agent_user_group' $LOGadmin_viewable_groupsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$group_exists=$row[0];
					if ($group_exists < 1)
						{
						$result = 'ERROR';
						$result_reason = "add_user USER GROUP DOES NOT EXIST";
						$data = "$agent_user_group";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						$stmt="SELECT count(*) from vicidial_users where user='$agent_user';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$user_exists=$row[0];
						if ($user_exists > 0)
							{
							$result = 'ERROR';
							$result_reason = "add_user USER ALREADY EXISTS";
							$data = "$agent_user";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							# if user value is set to autogenerate then find the next value for user
							if (preg_match('/AUTOGENERA/',$agent_user))
								{
								$new_user=0;
								$auto_user_add_value=0;
								while ($new_user < 2)
									{
									if ($new_user < 1)
										{
										$stmt = "SELECT auto_user_add_value FROM system_settings;";
										$rslt=mysql_to_mysqli($stmt, $link);
										$ss_auav_ct = mysqli_num_rows($rslt);
										if ($ss_auav_ct > 0)
											{
											$row=mysqli_fetch_row($rslt);
											$auto_user_add_value = $row[0];
											}
										$new_user++;
										}
									$stmt = "SELECT count(*) FROM vicidial_users where user='$auto_user_add_value';";
									$rslt=mysql_to_mysqli($stmt, $link);
									$row=mysqli_fetch_row($rslt);
									if ($row[0] < 1)
										{
										$new_user++;
										}
									else
										{
									#	echo "<!-- AG: $auto_user_add_value -->\n";
										$auto_user_add_value = ($auto_user_add_value + 7);
										}
									}
								$agent_user = $auto_user_add_value;

								$stmt="UPDATE system_settings SET auto_user_add_value='$agent_user';";
								$rslt=mysql_to_mysqli($stmt, $link);
								}

							if (strlen($hotkeys_active)<1) {$hotkeys_active='0';}
							if (strlen($wrapup_seconds_override)<1) {$wrapup_seconds_override='-1';}
							if (strlen($agent_choose_ingroups)<1) {$agent_choose_ingroups='1';}
							if (strlen($agent_choose_blended)<1) {$agent_choose_blended='1';}
							if (strlen($closer_default_blended)<1) {$closer_default_blended='0';}

							$pass_hash='';
							if ( ($SSpass_hash_enabled > 0) and (strlen($agent_pass) > 1) )
								{
								$agent_pass = preg_replace("/\'|\"|\\\\|;| /","",$agent_pass);
								$pass_hash = exec("../agc/bp.pl --pass=$agent_pass");
								$pass_hash = preg_replace("/PHASH: |\n|\r|\t| /",'',$pass_hash);
								$agent_pass='';
								}

							if (strlen($in_groups) > 0)
								{
								$in_groups = preg_replace("/\|/"," ",$in_groups);
								$in_groups = " ".$in_groups." -";
								}

							$stmt="INSERT INTO vicidial_users (user,pass,full_name,user_level,user_group,phone_login,phone_pass,hotkeys_active,voicemail_id,email,custom_one,custom_two,custom_three,custom_four,custom_five,pass_hash,wrapup_seconds_override,agent_choose_ingroups,agent_choose_blended,closer_default_blended,closer_campaigns) values('$agent_user','$agent_pass','$agent_full_name','$agent_user_level','$agent_user_group','$phone_login','$phone_pass','$hotkeys_active','$voicemail_id','$email','$custom_one','$custom_two','$custom_three','$custom_four','$custom_five','$pass_hash','$wrapup_seconds_override','$agent_choose_ingroups','$agent_choose_blended','$closer_default_blended','$in_groups');";
							$rslt=mysql_to_mysqli($stmt, $link);

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='USERS', event_type='ADD', record_id='$agent_user', event_code='ADMIN API ADD USER', event_sql=\"$SQL_log\", event_notes='user: $agent_user';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "add_user USER HAS BEEN ADDED";
							$data = "$agent_user|$agent_pass|$agent_user_level|$agent_full_name|$agent_user_group";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END add_user
################################################################################





################################################################################
### copy_user - adds user to the vicidial_users table
################################################################################
if ($function == 'copy_user')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_users='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "copy_user USER DOES NOT HAVE PERMISSION TO COPY USERS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($agent_user)<2) or (strlen($agent_pass)<2) or (strlen($source_user)<1) or (strlen($agent_full_name)<1) )
				{
				$result = 'ERROR';
				$result_reason = "copy_user YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$agent_user|$agent_pass|$source_user|$agent_full_name";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT user_level,user_group,modify_same_user_level from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_users='1' and user_level >= 8;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$user_level =				$row[0];
				$LOGuser_group =			$row[1];
				$modify_same_user_level =	$row[2];

				$stmt="SELECT user_level,user_group from vicidial_users where user='$source_user';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$SOURCEuser_level =			$row[0];
				$SOURCEuser_group =			$row[1];

				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				if ( ( ($user_level < 9) and ($user_level <= $SOURCEuser_level) ) or ( ($modify_same_user_level < 1) and ($user_level >= 9) and ($user_level==$SOURCEuser_level) ) )
					{
					$result = 'ERROR';
					$result_reason = "copy_user USER DOES NOT HAVE PERMISSION TO COPY USERS IN THIS USER LEVEL";
					$data = "$SOURCEuser_level|$user_level";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT count(*) from vicidial_user_groups where user_group='$SOURCEuser_group' $LOGadmin_viewable_groupsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$group_exists=$row[0];
					if ($group_exists < 1)
						{
						$result = 'ERROR';
						$result_reason = "copy_user USER GROUP DOES NOT EXIST";
						$data = "$SOURCEuser_group";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						$stmt="SELECT count(*) from vicidial_users where user='$agent_user';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$user_exists=$row[0];
						if ($user_exists > 0)
							{
							$result = 'ERROR';
							$result_reason = "copy_user USER ALREADY EXISTS";
							$data = "$agent_user";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							# if user value is set to autogenerate then find the next value for user
							if (preg_match('/AUTOGENERA/',$agent_user))
								{
								$new_user=0;
								$auto_user_add_value=0;
								while ($new_user < 2)
									{
									if ($new_user < 1)
										{
										$stmt = "SELECT auto_user_add_value FROM system_settings;";
										$rslt=mysql_to_mysqli($stmt, $link);
										$ss_auav_ct = mysqli_num_rows($rslt);
										if ($ss_auav_ct > 0)
											{
											$row=mysqli_fetch_row($rslt);
											$auto_user_add_value = $row[0];
											}
										$new_user++;
										}
									$stmt = "SELECT count(*) FROM vicidial_users where user='$auto_user_add_value';";
									$rslt=mysql_to_mysqli($stmt, $link);
									$row=mysqli_fetch_row($rslt);
									if ($row[0] < 1)
										{
										$new_user++;
										}
									else 
										{
									#	echo "<!-- AG: $auto_user_add_value -->\n";
										$auto_user_add_value = ($auto_user_add_value + 7);
										}
									}
								$agent_user = $auto_user_add_value;

								$stmt="UPDATE system_settings SET auto_user_add_value='$agent_user';";
								$rslt=mysql_to_mysqli($stmt, $link);
								}

							$pass_hash='';
							if ( ($SSpass_hash_enabled > 0) and (strlen($agent_pass) > 1) )
								{
								$agent_pass = preg_replace("/\'|\"|\\\\|;| /","",$agent_pass);
								$pass_hash = exec("../agc/bp.pl --pass=$agent_pass");
								$pass_hash = preg_replace("/PHASH: |\n|\r|\t| /",'',$pass_hash);
								$agent_pass='';
								}

							$stmt="INSERT INTO vicidial_users (user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,agent_choose_territories,custom_one,custom_two,custom_three,custom_four,custom_five,voicemail_id,agent_call_log_view_override,callcard_admin,agent_choose_blended,realtime_block_user_info,custom_fields_modify,force_change_password,agent_lead_search_override,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,preset_contact_search,modify_contacts,modify_same_user_level,admin_hide_lead_data,admin_hide_phone_data,agentcall_email,agentcall_chat,modify_email_accounts,pass_hash,alter_admin_interface_options,max_inbound_calls,modify_custom_dialplans,wrapup_seconds_override,modify_languages,selected_language,user_choose_language,ignore_group_on_search,api_list_restrict,api_allowed_functions,lead_filter_id,admin_cf_show_hidden,user_hide_realtime,modify_colors,user_nickname,user_new_lead_limit,api_only_user,modify_auto_reports,modify_ip_lists,ignore_ip_list,ready_max_logout,export_gdpr_leads,access_recordings,pause_code_approval,max_hopper_calls,max_hopper_calls_hour,mute_recordings,hide_call_log_info,next_dial_my_callbacks,user_admin_redirect_url,max_inbound_filter_enabled,max_inbound_filter_statuses,max_inbound_filter_ingroups,max_inbound_filter_min_sec,status_group_id,mobile_number,two_factor_override,manual_dial_filter,user_location,download_invalid_files,user_group_two,modify_dial_prefix,inbound_credits,hci_enabled) SELECT \"$agent_user\",\"$agent_pass\",\"$agent_full_name\",user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,agent_choose_territories,custom_one,custom_two,custom_three,custom_four,custom_five,voicemail_id,agent_call_log_view_override,callcard_admin,agent_choose_blended,realtime_block_user_info,custom_fields_modify,force_change_password,agent_lead_search_override,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,preset_contact_search,modify_contacts,modify_same_user_level,admin_hide_lead_data,admin_hide_phone_data,agentcall_email,agentcall_chat,modify_email_accounts,\"$pass_hash\",alter_admin_interface_options,max_inbound_calls,modify_custom_dialplans,wrapup_seconds_override,modify_languages,selected_language,user_choose_language,ignore_group_on_search,api_list_restrict,api_allowed_functions,lead_filter_id,admin_cf_show_hidden,user_hide_realtime,modify_colors,user_nickname,user_new_lead_limit,api_only_user,modify_auto_reports,modify_ip_lists,ignore_ip_list,ready_max_logout,export_gdpr_leads,access_recordings,pause_code_approval,max_hopper_calls,max_hopper_calls_hour,mute_recordings,hide_call_log_info,next_dial_my_callbacks,user_admin_redirect_url,max_inbound_filter_enabled,max_inbound_filter_statuses,max_inbound_filter_ingroups,max_inbound_filter_min_sec,status_group_id,mobile_number,two_factor_override,manual_dial_filter,user_location,download_invalid_files,user_group_two,modify_dial_prefix,inbound_credits,hci_enabled from vicidial_users where user=\"$source_user\";";
							$rslt=mysql_to_mysqli($stmt, $link);
							$affected_rows = mysqli_affected_rows($link);

							$stmtA="INSERT INTO vicidial_inbound_group_agents (user,group_id,group_rank,group_weight,calls_today,group_type,daily_limit) SELECT \"$agent_user\",group_id,group_rank,group_weight,\"0\",group_type,daily_limit from vicidial_inbound_group_agents where user=\"$source_user\";";
							$rslt=mysql_to_mysqli($stmtA, $link);
							$affected_rowsA = mysqli_affected_rows($link);

							$stmtB="INSERT INTO vicidial_campaign_agents (user,campaign_id,campaign_rank,campaign_weight,calls_today) SELECT \"$agent_user\",campaign_id,campaign_rank,campaign_weight,\"0\" from vicidial_campaign_agents where user=\"$source_user\";";
							$rslt=mysql_to_mysqli($stmtB, $link);
							$affected_rowsB = mysqli_affected_rows($link);

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|$stmtA|$stmtB|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='USERS', event_type='COPY', record_id='$agent_user', event_code='ADMIN API COPY USER', event_sql=\"$SQL_log\", event_notes='user: $agent_user   source_user: $source_user   rows: $affected_rows,$affected_rowsA,$affected_rowsB';";
							if ($DB) {echo "|$stmt|$stmtA|$stmtB|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "copy_user USER HAS BEEN COPIED";
							$data = "$agent_user|$agent_pass|$agent_full_name|$source_user";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END copy_user
################################################################################






################################################################################
### update_user - updates user entry already in the system
################################################################################
if ($function == 'update_user')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_users='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_user USER DOES NOT HAVE PERMISSION TO UPDATE USERS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($agent_user)<2) or (strlen($agent_user)>20) )
				{
				$result = 'ERROR';
				$result_reason = "update_user YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$agent_user|";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_users where user='$agent_user';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$user_exists=$row[0];
				if ($user_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "update_user USER DOES NOT EXIST";
					$data = "$server_ip";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$user_level_modify_gt='<=';
					$stmt="SELECT user_level,modify_same_user_level from vicidial_users where user='$user';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$api_user_level =				$row[0];
					$api_modify_same_user_level =	$row[1];
					if ($api_modify_same_user_level < 1)
						{$user_level_modify_gt='<';}

					$stmt="SELECT count(*) from vicidial_users where user='$agent_user' and user_level $user_level_modify_gt '$api_user_level' $LOGadmin_viewable_groupsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$phone_exists=$row[0];
					if ($phone_exists < 1)
						{
						$result = 'ERROR';
						$result_reason = "update_user USER DOES NOT HAVE PERMISSION TO UPDATE THIS USER";
						$data = "$agent_user|$user_level_modify_gt|$api_user_level\n";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						if ($delete_user == 'Y')
							{
							$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and delete_users='1' and modify_users='1' and user_level >= 8 and active='Y';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$allowed_user=$row[0];
							if ($allowed_user < 1)
								{
								$result = 'ERROR';
								$result_reason = "update_user USER DOES NOT HAVE PERMISSION TO DELETE USERS";
								$data = "$allowed_user";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{
								$stmt="DELETE FROM vicidial_users WHERE user='$agent_user';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$affected_rows = mysqli_affected_rows($link);
								if ($DB) {echo "|$stmt|\n";}

								### LOG INSERTION Admin Log Table ###
								$SQL_log = "$stmt|";
								$SQL_log = preg_replace('/;/', '', $SQL_log);
								$SQL_log = addslashes($SQL_log);
								$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='USERS', event_type='DELETE', record_id='$agent_user', event_code='ADMIN API DELETE USER', event_sql=\"$SQL_log\", event_notes='user: $agent_user';";
								if ($DB) {echo "|$stmt|\n";}
								$rslt=mysql_to_mysqli($stmt, $link);

								$result = 'SUCCESS';
								$result_reason = "update_user USER HAS BEEN DELETED";
								$data = "$agent_user|";
								echo "$result: $result_reason - $user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							exit;
							}

						$passSQL='';
						$pass_hashSQL='';
						$full_nameSQL='';
						$user_levelSQL='';
						$user_groupSQL='';
						$phone_loginSQL='';
						$phone_passSQL='';
						$hotkeys_activeSQL='';
						$voicemail_idSQL='';
						$emailSQL='';
						$custom_oneSQL='';
						$custom_twoSQL='';
						$custom_threeSQL='';
						$custom_fourSQL='';
						$custom_fiveSQL='';
						$activeSQL='';
						$wrapup_seconds_overrideSQL='';

						if (strlen($agent_pass) > 0)
							{
							$pass_hash='';
							if ($SSpass_hash_enabled > 0)
								{
								$agent_pass = preg_replace("/\'|\"|\\\\|;| /","",$agent_pass);
								$pass_hash = exec("../agc/bp.pl --pass=$agent_pass");
								$pass_hash = preg_replace("/PHASH: |\n|\r|\t| /",'',$pass_hash);
								$pass_hashSQL = ",pass_hash='$pass_hash'";
								$passSQL=",pass=''";
								}
							else
								{
								$passSQL = " ,pass='$agent_pass'";
								}
							}
						if (strlen($agent_full_name) > 0)
							{
							if ($agent_full_name == '--BLANK--')
								{$full_nameSQL = " ,full_name=''";}
							else
								{
								if ( (strlen($agent_full_name) > 50) or (strlen($agent_full_name) < 1) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID FULL NAME, THIS IS AN OPTIONAL FIELD";
									$data = "$agent_full_name";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$full_nameSQL = " ,full_name='$agent_full_name'";}
								}
							}
						if (strlen($agent_user_level) > 0)
							{
							if ( ($agent_user_level > 9) or ($agent_user_level < 1) or ($agent_user_level > $api_user_level) or ( ($agent_user_level == $api_user_level) and ($api_modify_same_user_level < 1) ) )
								{
								$result = 'ERROR';
								$result_reason = "update_user YOU MUST USE A VALID USER LEVEL, THIS IS AN OPTIONAL FIELD";
								$data = "$agent_user_level|$api_user_level|$api_modify_same_user_level";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$user_levelSQL = " ,user_level='$agent_user_level'";}
							}
						if (strlen($agent_user_group) > 0)
							{
							$stmt="SELECT count(*) from vicidial_user_groups where user_group='$agent_user_group' $LOGadmin_viewable_groupsSQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$valid_user_group =			$row[0];

							if ( (strlen($agent_user_group) > 20) or (strlen($agent_user_group) < 1) or ($valid_user_group < 1) )
								{
								$result = 'ERROR';
								$result_reason = "update_user YOU MUST USE A VALID USER GROUP, THIS IS AN OPTIONAL FIELD";
								$data = "$agent_user_group|$valid_user_group";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$user_groupSQL = " ,user_group='$agent_user_group'";}
							}
						if (strlen($phone_login) > 0)
							{
							if ($phone_login == '--BLANK--')
								{$phone_loginSQL = " ,phone_login=''";}
							else
								{
								$stmt="SELECT count(*) from phones where login='$phone_login';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$valid_phone_login =			$row[0];

								$stmt="SELECT count(*) from phones_alias where alias_id='$phone_login';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$valid_phone_alias =			$row[0];

								if ( (strlen($phone_login) > 20) or (strlen($phone_login) < 1) or ( ($valid_phone_login < 1) and ($valid_phone_alias < 1) ) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID PHONE LOGIN, THIS IS AN OPTIONAL FIELD";
									$data = "$phone_login|$valid_phone_login|$valid_phone_alias";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$phone_loginSQL = " ,phone_login='$phone_login'";}
								}
							}
						if (strlen($phone_pass) > 0)
							{
							if ($phone_pass == '--BLANK--')
								{$phone_passSQL = " ,phone_pass=''";}
							else
								{
								if ( (strlen($phone_pass) > 20) or (strlen($phone_pass) < 1) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID PHONE PASSWORD, THIS IS AN OPTIONAL FIELD";
									$data = "$phone_pass";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$phone_passSQL = " ,phone_pass='$phone_pass'";}
								}
							}
						if (strlen($hotkeys_active) > 0)
							{
							if ( ($hotkeys_active > 1) or ($hotkeys_active < 0) )
								{
								$result = 'ERROR';
								$result_reason = "update_user YOU MUST USE A VALID HOTKEYS SETTING, THIS IS AN OPTIONAL FIELD";
								$data = "$hotkeys_active";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$hotkeys_activeSQL = " ,hotkeys_active='$hotkeys_active'";}
							}
						if (strlen($voicemail_id) > 0)
							{
							if ($voicemail_id == '--BLANK--')
								{$voicemail_idSQL = " ,voicemail_id=''";}
							else
								{
								if ( (strlen($voicemail_id) > 10) or (strlen($voicemail_id) < 2) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID VOICEMAIL ID, THIS IS AN OPTIONAL FIELD";
									$data = "$voicemail_id";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$voicemail_idSQL = " ,voicemail_id='$voicemail_id'";}
								}
							}
						if (strlen($email) > 0)
							{
							if ($email == '--BLANK--')
								{$emailSQL = " ,email=''";}
							else
								{
								if ( (strlen($email) > 100) or (strlen($email) < 5) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID EMAIL, THIS IS AN OPTIONAL FIELD";
									$data = "$email";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$emailSQL = " ,email='$email'";}
								}
							}
						if (strlen($custom_one) > 0)
							{
							if ($custom_one == '--BLANK--')
								{$custom_oneSQL = " ,custom_one=''";}
							else
								{
								if (strlen($custom_one) > 100)
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID CUSTOM ONE, THIS IS AN OPTIONAL FIELD";
									$data = "$custom_one";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$custom_oneSQL = " ,custom_one='$custom_one'";}
								}
							}
						if (strlen($custom_two) > 0)
							{
							if ($custom_two == '--BLANK--')
								{$custom_twoSQL = " ,custom_two=''";}
							else
								{
								if (strlen($custom_two) > 100)
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID CUSTOM TWO, THIS IS AN OPTIONAL FIELD";
									$data = "$custom_two";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$custom_twoSQL = " ,custom_two='$custom_two'";}
								}
							}
						if (strlen($custom_three) > 0)
							{
							if ($custom_three == '--BLANK--')
								{$custom_threeSQL = " ,custom_three=''";}
							else
								{
								if (strlen($custom_three) > 100)
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID CUSTOM THREE, THIS IS AN OPTIONAL FIELD";
									$data = "$custom_three";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$custom_threeSQL = " ,custom_three='$custom_three'";}
								}
							}
						if (strlen($custom_four) > 0)
							{
							if ($custom_four == '--BLANK--')
								{$custom_fourSQL = " ,custom_four=''";}
							else
								{
								if (strlen($custom_four) > 100)
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID CUSTOM FOUR, THIS IS AN OPTIONAL FIELD";
									$data = "$custom_four";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$custom_fourSQL = " ,custom_four='$custom_four'";}
								}
							}
						if (strlen($custom_five) > 0)
							{
							if ($custom_five == '--BLANK--')
								{$custom_fiveSQL = " ,custom_five=''";}
							else
								{
								if (strlen($custom_five) > 100)
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID CUSTOM FIVE, THIS IS AN OPTIONAL FIELD";
									$data = "$custom_five";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$custom_fiveSQL = " ,custom_five='$custom_five'";}
								}
							}
						if (strlen($active) > 0)
							{
							if ( ($active != 'Y') and ($active != 'N') )
								{
								$result = 'ERROR';
								$result_reason = "update_user ACTIVE MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
								$data = "$active";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$activeSQL = " ,active='$active'";}
							}
						if (strlen($wrapup_seconds_override) > 0)
							{
							if ( ($wrapup_seconds_override < -1) or ($wrapup_seconds_override > 9999) )
								{
								$result = 'ERROR';
								$result_reason = "update_user wrapup_seconds_override MUST BE A VALID DIGIT BETWEEN -1 AND 9999, THIS IS AN OPTIONAL FIELD";
								$data = "$wrapup_seconds_override";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$wrapup_seconds_overrideSQL = " ,wrapup_seconds_override='$wrapup_seconds_override'";}
							}

						if ( (strlen($campaign_rank) > 0) or (strlen($campaign_grade) > 0) )
							{
							$rank_BEGIN_SQL='';
							$rank_MID_SQL='';
							$rank_END_SQL='';
							$grade_BEGIN_SQL='';
							$grade_MID_SQL='';
							$grade_END_SQL='';
							if (strlen($campaign_rank) > 0)
								{
								if ( ($campaign_rank > 9) or ($campaign_rank < -9) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID CAMPAIGN RANK, THIS IS AN OPTIONAL FIELD";
									$data = "$campaign_rank";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								$rank_BEGIN_SQL=',campaign_rank,campaign_weight';
								$rank_MID_SQL=",'$campaign_rank','$campaign_rank'";
								$rank_END_SQL="campaign_rank='$campaign_rank',campaign_weight='$campaign_rank'";
								}
							if (strlen($campaign_grade) > 0)
								{
								if ( ($campaign_grade > 10) or ($campaign_grade < 1) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID CAMPAIGN GRADE, THIS IS AN OPTIONAL FIELD";
									$data = "$campaign_grade";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								if (strlen($rank_END_SQL)>0)
									{$grade_END_SQL .= ",";}
								$grade_BEGIN_SQL=',campaign_grade';
								$grade_MID_SQL=",'$campaign_grade'";
								$grade_END_SQL.="campaign_grade='$campaign_grade'";
								}
							$camp_rg_onlySQL='';
							$camp_rg_onlyNOTE='';
							if ($camp_rg_only=='1')
								{
								$camp_rg_onlySQL = "where campaign_id='$campaign_id'";
								$camp_rg_only_andSQL = "and campaign_id='$campaign_id'";
								$camp_rg_onlyNOTE = "|$campaign_id|$camp_rg_only";
								}
							$stmt="INSERT INTO vicidial_campaign_agents(user,campaign_id $rank_BEGIN_SQL $grade_BEGIN_SQL) SELECT '$agent_user',campaign_id $rank_MID_SQL $grade_MID_SQL from vicidial_campaigns $camp_rg_onlySQL ON DUPLICATE KEY UPDATE $rank_END_SQL $grade_END_SQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$affected_rows = mysqli_affected_rows($link);
							if ($DB) {echo "|$stmt|$affected_rows|\n";}

							$stmtB="UPDATE vicidial_live_agents set campaign_weight='$campaign_rank', campaign_grade='$campaign_grade' where user='$agent_user' $camp_rg_only_andSQL;";
							$rslt=mysql_to_mysqli($stmtB, $link);
							$affected_rowsB = mysqli_affected_rows($link);
							if ($DB) {echo "|$stmtB|$affected_rowsB|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|$stmtB|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$agent_user', event_code='ADMIN API UPDATE USER RANK', event_sql=\"$SQL_log\", event_notes='user: $agent_user|rank: $campaign_rank|grade: $campaign_grade|rows: $affected_rows|$affected_rowsB|$camp_rg_onlyNOTE';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'NOTICE';
							$result_reason = "update_user USER CAMPAIGN RANKS/GRADES HAVE BEEN UPDATED";
							$data = "$agent_user|$campaign_rank|$campaign_grade$camp_rg_onlyNOTE";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}

						if ( (strlen($ingroup_rank) > 0) or (strlen($ingroup_grade) > 0) )
							{
							$rank_BEGIN_SQL='';
							$rank_MID_SQL='';
							$rank_END_SQL='';
							$grade_BEGIN_SQL='';
							$grade_MID_SQL='';
							$grade_END_SQL='';
							if (strlen($ingroup_rank) > 0)
								{
								if ( ($ingroup_rank > 9) or ($ingroup_rank < -9) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID IN-GROUP RANK, THIS IS AN OPTIONAL FIELD";
									$data = "$ingroup_rank";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								$rank_BEGIN_SQL=',group_rank,group_weight';
								$rank_MID_SQL=",'$ingroup_rank','$ingroup_rank'";
								$rank_END_SQL="group_rank='$ingroup_rank',group_weight='$ingroup_rank'";
								}
							if (strlen($ingroup_grade) > 0)
								{
								if ( ($ingroup_grade > 10) or ($ingroup_grade < 1) )
									{
									$result = 'ERROR';
									$result_reason = "update_user YOU MUST USE A VALID IN-GROUP GRADE, THIS IS AN OPTIONAL FIELD";
									$data = "$ingroup_grade";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								if (strlen($rank_END_SQL)>0)
									{$grade_END_SQL .= ",";}
								$grade_BEGIN_SQL=',group_grade';
								$grade_MID_SQL=",'$ingroup_grade'";
								$grade_END_SQL.="group_grade='$ingroup_grade'";
								}
							$ingrp_rg_onlySQL='';
							$ingrp_rg_onlyNOTE='';
							if ($ingrp_rg_only=='1')
								{
								$ingrp_rg_onlySQL = "where group_id='$group_id'";
								$ingrp_rg_only_andSQL = "and group_id='$group_id'";
								$ingrp_rg_onlyNOTE = "|$group_id|$ingrp_rg_only";
								}
							$stmt="INSERT INTO vicidial_inbound_group_agents(user,group_id $rank_BEGIN_SQL $grade_BEGIN_SQL) SELECT '$agent_user',group_id $rank_MID_SQL $grade_MID_SQL from vicidial_inbound_groups $ingrp_rg_onlySQL ON DUPLICATE KEY UPDATE $rank_END_SQL $grade_END_SQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$affected_rows = mysqli_affected_rows($link);
							if ($DB) {echo "|$stmt|$affected_rows|\n";}

							$stmtB="UPDATE vicidial_live_inbound_agents set group_weight='$ingroup_rank', group_grade='$ingroup_grade' where user='$agent_user' $ingrp_rg_only_andSQL;";
							$rslt=mysql_to_mysqli($stmtB, $link);
							$affected_rowsB = mysqli_affected_rows($link);
							if ($DB) {echo "|$stmtB|$affected_rowsB|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|$stmtB|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$agent_user', event_code='ADMIN API UPDATE USER RANK', event_sql=\"$SQL_log\", event_notes='user: $agent_user|rank: $ingroup_rank|grade: $ingroup_grade|rows: $affected_rows|$affected_rowsB|$ingrp_rg_onlyNOTE';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'NOTICE';
							$result_reason = "update_user USER IN-GROUP RANKS/GRADES HAVE BEEN UPDATED";
							$data = "$agent_user|$ingroup_rank|$ingroup_grade$ingrp_rg_onlyNOTE";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}

						if ( (strlen($reset_password) > 0) and ($reset_password > 7) )
							{
							### BEGIN reset_password section ###
							if (strlen($email) < 5)
								{
								$stmt="SELECT email from vicidial_users where user='$agent_user';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$email =	$row[0];
								}
							if (!filter_var($email, FILTER_VALIDATE_EMAIL))
								{
								$result = 'NOTICE';
								$result_reason = "update_user USER PASSWORD RESET FAILED: NO VALID EMAIL PROVIDED";
								$data = "$agent_user|$email";
								echo "$result: $result_reason - $user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								$temp_pass = '';
								$possible = "0123456789ABCDEFGHIJKLMNPQRSTUVWXYZ";  
								$i = 0; 
								while ($i < $reset_password) 
									{ 
									$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
									$temp_pass .= $char;
									$i++;
									}

								$email_message = "Here is your new temporary password: $temp_pass";
								$email_subject = "User Account Reset";
							
								$success = mail($email, $email_subject, $email_message);
								if ($success)
									{
									$pass_hash='';
									$pass_hashSQL='';
									if ($SSpass_hash_enabled > 0)
										{
										if (strlen($temp_pass) > 1)
											{
											$temp_pass = preg_replace("/\'|\"|\\\\|;| /","",$temp_pass);
											$pass_hash = exec("../agc/bp.pl --pass=$temp_pass");
											$pass_hash = preg_replace("/PHASH: |\n|\r|\t| /",'',$pass_hash);
											$pass_hashSQL = ",pass_hash='$pass_hash'";
											}
										$temp_pass='';
										}

									$stmt="UPDATE vicidial_users SET force_change_password='Y',pass='$temp_pass' $pass_hashSQL WHERE user='$agent_user';";
									$rslt=mysql_to_mysqli($stmt, $link);
									if ($DB) {echo "|$stmt|\n";}

									### LOG INSERTION Admin Log Table ###
									$SQL_log = "$stmt|";
									$SQL_log = preg_replace('/;/', '', $SQL_log);
									$SQL_log = addslashes($SQL_log);
									$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$agent_user', event_code='ADMIN API RESET PASS', event_sql=\"$SQL_log\", event_notes='user: $agent_user';";
									if ($DB) {echo "|$stmt|\n";}
									$rslt=mysql_to_mysqli($stmt, $link);

									$result = 'NOTICE';
									$result_reason = "update_user USER PASSWORD HAS BEEN RESET";
									$data = "$agent_user|$email";
									echo "$result: $result_reason - $user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								else
									{
									$error_msg = error_get_last()['message'];
									$result = 'NOTICE';
									$result_reason = "update_user USER PASSWORD RESET FAILED: EMAIL FAILURE";
									$data = "$agent_user|$email|$error_msg";
									echo "$result: $result_reason - $user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								}
							### END reset_password section ###
							}

						$updateSQL = "$passSQL$pass_hashSQL$full_nameSQL$user_levelSQL$user_groupSQL$phone_loginSQL$phone_passSQL$hotkeys_activeSQL$voicemail_idSQL$emailSQL$custom_oneSQL$custom_twoSQL$custom_threeSQL$custom_fourSQL$custom_fiveSQL$activeSQL$wrapup_seconds_overrideSQL";

						if (strlen($updateSQL)< 3)
							{
							$result = 'ERROR';
							$result_reason = "update_user NO UPDATES DEFINED";
							$data = "$updateSQL";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							$stmt="UPDATE vicidial_users SET failed_login_count='0' $updateSQL WHERE user='$agent_user';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$agent_user', event_code='ADMIN API UPDATE USER', event_sql=\"$SQL_log\", event_notes='user: $agent_user';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_user USER HAS BEEN UPDATED";
							$data = "$agent_user|";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END update_user
################################################################################





################################################################################
### update_remote_agent - updates remote agent entry already in the system
################################################################################
if ($function == 'update_remote_agent')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_remoteagents='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_remote_agent USER DOES NOT HAVE PERMISSION TO UPDATE REMOTE AGENTS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($agent_user)<2) or (strlen($agent_user)>20) )
				{
				$result = 'ERROR';
				$result_reason = "update_remote_agent YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$agent_user|";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_remote_agents where user_start='$agent_user';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$user_exists=$row[0];
				if ($user_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "update_remote_agent REMOTE AGENT DOES NOT EXIST";
					$data = "$server_ip";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$user_level_modify_gt='<=';
					$stmt="SELECT user_level,modify_same_user_level from vicidial_users where user='$user';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$api_user_level =				$row[0];
					$api_modify_same_user_level =	$row[1];
					if ($api_modify_same_user_level < 1)
						{$user_level_modify_gt='<';}

					$stmt="SELECT count(*) from vicidial_users where user='$agent_user' and user_level $user_level_modify_gt '$api_user_level' $LOGadmin_viewable_groupsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$agent_allowed=$row[0];
					if ($agent_allowed < 1)
						{
						$result = 'ERROR';
						$result_reason = "update_remote_agent USER DOES NOT HAVE PERMISSION TO UPDATE THIS REMOTE AGENT";
						$data = "$agent_user|$user_level_modify_gt|$api_user_level\n";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						$statusSQL='';
						$campaign_idSQL='';
						$number_of_linesSQL='';

						if (strlen($number_of_lines) > 0)
							{
							if ( ($number_of_lines > 999) or ($number_of_lines < 1) )
								{
								$result = 'ERROR';
								$result_reason = "update_remote_agent YOU MUST USE A VALID NUMBER OF LINES, THIS IS AN OPTIONAL FIELD";
								$data = "$number_of_lines";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$number_of_linesSQL = " ,number_of_lines='$number_of_lines'";}
							}
						if (strlen($campaign_id) > 0)
							{
							$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' $LOGallowed_campaignsSQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$valid_campaign =			$row[0];
							
							if ( (strlen($campaign_id) > 8) or (strlen($campaign_id) < 1) or ($valid_campaign < 1) )
								{
								$result = 'ERROR';
								$result_reason = "update_remote_agent YOU MUST USE A VALID CAMPAIGN ID, THIS IS AN OPTIONAL FIELD";
								$data = "$campaign_id|$valid_campaign";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$campaign_idSQL = " ,campaign_id='$campaign_id'";}
							}
							
						if (strlen($status) > 0)
							{
							if (!preg_match("/ACTIVE|INACTIVE/",$status))
								{
								$result = 'ERROR';
								$result_reason = "update_remote_agent STATUS MUST BE ACTIVE OR INACTIVE, THIS IS AN OPTIONAL FIELD";
								$data = "$status";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{
								$stmt="SELECT count(*) from vicidial_remote_agents where user_start='$agent_user' and status='$status';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$agent_status=$row[0];

								if ($agent_status > 0)
									{
									$result = 'NOTICE';
									$result_reason = "update_remote_agent REMOTE AGENT IS ALREADY $status";
									$data = "$agent_user";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								else
									{$statusSQL = " ,status='$status'";}
								}
							}
						
						$updateSQL = "$number_of_linesSQL$campaign_idSQL$statusSQL";
						$updateSQL = preg_replace("/^ ,/",'',$updateSQL);

						if (strlen($updateSQL)< 3)
							{
							$result = 'ERROR';
							$result_reason = "update_remote_agent NO UPDATES DEFINED";
							$data = "$updateSQL";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							$stmt="UPDATE vicidial_remote_agents SET $updateSQL WHERE user_start='$agent_user';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='REMOTEAGENTS', event_type='MODIFY', record_id='$agent_user', event_code='ADMIN API UPDATE REMOTEAGENTS', event_sql=\"$SQL_log\", event_notes='user start: $agent_user';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_remote_agent REMOTE AGENT HAS BEEN UPDATED";
							$data = "$agent_user|";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END update_remote_agent
################################################################################





################################################################################
### add_group_alias - adds group alias to the system
################################################################################
if ($function == 'add_group_alias')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and ast_admin_access='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_group_alias USER DOES NOT HAVE PERMISSION TO ADD GROUP ALIASES";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if (strlen($caller_id_number)<6)
				{
				$result = 'ERROR';
				$result_reason = "add_group_alias YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$group_alias_id|$group_alias_name|$caller_id_number";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				if (strlen($group_alias_id)<2) {$group_alias_id = $caller_id_number;}
				if (strlen($group_alias_name)<2) {$group_alias_name = $caller_id_number;}
				if (strlen($active)<1) {$active = 'Y';}
				if (strlen($admin_user_group)<1) {$admin_user_group = '---ALL---';}
				$group_alias_name = preg_replace("/\+/",' ',$group_alias_name);
				$caller_id_name = preg_replace("/\+/",' ',$caller_id_name);

				$stmt="SELECT count(*) from groups_alias where group_alias_id='$group_alias_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$alias_exists=$row[0];
				if ($alias_exists > 0)
					{
					$result = 'ERROR';
					$result_reason = "add_group_alias GROUP ALIAS ALREADY EXISTS";
					$data = "$group_alias_id|$caller_id_number";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="INSERT INTO groups_alias (group_alias_id,group_alias_name,caller_id_number,caller_id_name,active,user_group) values('$group_alias_id','$group_alias_name','$caller_id_number','$caller_id_name','$active','$admin_user_group');";
					$rslt=mysql_to_mysqli($stmt, $link);

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='GROUPALIASES', event_type='ADD', record_id='$group_alias_id', event_code='ADMIN API ADD GROUP ALIAS', event_sql=\"$SQL_log\", event_notes='';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$result = 'SUCCESS';
					$result_reason = "add_group_alias GROUP ALIAS HAS BEEN ADDED";
					$data = "$group_alias_id|$group_alias_name|$caller_id_number";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END add_group_alias
################################################################################



################################################################################
### add_dnc_phone - adds a phone number to a DNC list in the system
################################################################################
if ($function == 'add_dnc_phone')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and modify_lists='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_dnc_phone USER DOES NOT HAVE PERMISSION TO ADD DNC NUMBERS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($phone_number) < 6) or (strlen($campaign_id) < 1) )
				{
				$result = 'ERROR';
				$result_reason = "add_dnc_phone YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$phone_number|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from vicidial_campaign_dnc where phone_number='$phone_number' and campaign_id='$campaign_id';";
				if ($campaign_id == 'SYSTEM_INTERNAL')
					{$stmt="SELECT count(*) from vicidial_dnc where phone_number='$phone_number';";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$dnc_exists=$row[0];
				if ($dnc_exists > 0)
					{
					$result = 'ERROR';
					$result_reason = "add_dnc_phone DNC NUMBER ALREADY EXISTS";
					$data = "$phone_number|$campaign_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmtA="INSERT INTO vicidial_campaign_dnc (phone_number,campaign_id) values('$phone_number','$campaign_id');";
					if ($campaign_id == 'SYSTEM_INTERNAL')
						{$stmtA="INSERT INTO vicidial_dnc (phone_number) values('$phone_number');";}
					$rslt=mysql_to_mysqli($stmtA, $link);
					$affected_rowsA = mysqli_affected_rows($link);

					$stmtB="INSERT INTO vicidial_dnc_log SET phone_number='$phone_number', campaign_id='$campaign_id', action='add', action_date=NOW(), user='$user';";
					if ($campaign_id == 'SYSTEM_INTERNAL')
						{$stmtB="INSERT INTO vicidial_dnc_log SET phone_number='$phone_number', campaign_id='-SYSINT-', action='add', action_date=NOW(), user='$user';";}
					$rslt=mysql_to_mysqli($stmtB, $link);
					$affected_rowsB = mysqli_affected_rows($link);

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmtA|$stmtB|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LISTS', event_type='ADD', record_id='$phone_number', event_code='ADMIN API ADD DNC NUMBER', event_sql=\"$SQL_log\", event_notes='$phone_number|$campaign_id|$affected_rowsA|$affected_rowsB';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$result = 'SUCCESS';
					$result_reason = "add_dnc_phone DNC NUMBER HAS BEEN ADDED";
					$data = "$phone_number|$campaign_id";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END add_dnc_phone
################################################################################


################################################################################
### delete_dnc_phone - removes a phone number from a DNC list in the system
################################################################################
if ($function == 'delete_dnc_phone')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and modify_lists='1' and delete_from_dnc='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "delete_dnc_phone USER DOES NOT HAVE PERMISSION TO DELETE DNC NUMBERS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($phone_number) < 6) or (strlen($campaign_id) < 1) )
				{
				$result = 'ERROR';
				$result_reason = "delete_dnc_phone YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$phone_number|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from vicidial_campaign_dnc where phone_number='$phone_number' and campaign_id='$campaign_id';";
				if ($campaign_id == 'SYSTEM_INTERNAL')
					{$stmt="SELECT count(*) from vicidial_dnc where phone_number='$phone_number';";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$dnc_exists=$row[0];
				if ($dnc_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "delete_dnc_phone DNC NUMBER DOES NOT EXIST";
					$data = "$phone_number|$campaign_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmtA="DELETE FROM vicidial_campaign_dnc WHERE phone_number='$phone_number' and campaign_id='$campaign_id';";
					if ($campaign_id == 'SYSTEM_INTERNAL')
						{$stmtA="DELETE FROM vicidial_dnc where phone_number='$phone_number';";}
					$rslt=mysql_to_mysqli($stmtA, $link);
					$affected_rowsA = mysqli_affected_rows($link);

					$stmtB="INSERT INTO vicidial_dnc_log SET phone_number='$phone_number', campaign_id='$campaign_id', action='delete', action_date=NOW(), user='$user';";
					if ($campaign_id == 'SYSTEM_INTERNAL')
						{$stmtB="INSERT INTO vicidial_dnc_log SET phone_number='$phone_number', campaign_id='-SYSINT-', action='delete', action_date=NOW(), user='$user';";}
					$rslt=mysql_to_mysqli($stmtB, $link);
					$affected_rowsB = mysqli_affected_rows($link);

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmtA|$stmtB|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LISTS', event_type='DELETE', record_id='$phone_number', event_code='ADMIN API DELETE DNC NUMBER', event_sql=\"$SQL_log\", event_notes='$phone_number|$campaign_id|$affected_rowsA|$affected_rowsB';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$result = 'SUCCESS';
					$result_reason = "delete_dnc_phone DNC NUMBER HAS BEEN DELETED";
					$data = "$phone_number|$campaign_id";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END delete_dnc_phone
################################################################################


################################################################################
### add_fpg_phone - adds a phone number to a Filter Phone Group in the system
################################################################################
if ($function == 'add_fpg_phone')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and modify_lists='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_fpg_phone USER DOES NOT HAVE PERMISSION TO ADD FILTER PHONE GROUP NUMBERS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($phone_number) < 6) or (strlen($group) < 1) )
				{
				$result = 'ERROR';
				$result_reason = "add_fpg_phone YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$phone_number|$group";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from vicidial_filter_phone_groups where filter_phone_group_id='$group';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$fpg_exists=$row[0];
				if ($fpg_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "add_fpg_phone FILTER PHONE GROUP DOES NOT EXIST";
					$data = "$group";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT count(*) from vicidial_filter_phone_numbers where phone_number='$phone_number' and filter_phone_group_id='$group';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$fpg_entry_exists=$row[0];
					if ($fpg_entry_exists > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_fpg_phone FILTER PHONE GROUP NUMBER ALREADY EXISTS";
						$data = "$phone_number|$group";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						$stmtA="INSERT INTO vicidial_filter_phone_numbers (phone_number,filter_phone_group_id) values('$phone_number','$group');";
						$rslt=mysql_to_mysqli($stmtA, $link);
						$affected_rowsA = mysqli_affected_rows($link);

						### LOG INSERTION Admin Log Table ###
						$SQL_log = "$stmtA|";
						$SQL_log = preg_replace('/;/', '', $SQL_log);
						$SQL_log = addslashes($SQL_log);
						$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='FILTERPHONEGROUPS', event_type='ADD', record_id='$phone_number', event_code='ADMIN API ADD NUMBER TO FILTER PHONE GROUP $group', event_sql=\"$SQL_log\", event_notes='$phone_number|$group|$affected_rowsA';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);

						$result = 'SUCCESS';
						$result_reason = "add_fpg_phone FILTER PHONE GROUP NUMBER HAS BEEN ADDED";
						$data = "$phone_number|$group";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END add_fpg_phone
################################################################################


################################################################################
### delete_fpg_phone - removes a phone number from a Filter Phone Group in the system
################################################################################
if ($function == 'delete_fpg_phone')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and modify_lists='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "delete_fpg_phone USER DOES NOT HAVE PERMISSION TO DELETE FILTER PHONE GROUP NUMBERS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($phone_number) < 6) or (strlen($group) < 1) )
				{
				$result = 'ERROR';
				$result_reason = "delete_fpg_phone YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$phone_number|$group";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from vicidial_filter_phone_groups where filter_phone_group_id='$group';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$fpg_exists=$row[0];
				if ($fpg_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "delete_fpg_phone FILTER PHONE GROUP DOES NOT EXIST";
					$data = "$group";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT count(*) from vicidial_filter_phone_numbers where phone_number='$phone_number' and filter_phone_group_id='$group';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$fpg_entry_exists=$row[0];
					if ($fpg_entry_exists < 1)
						{
						$result = 'ERROR';
						$result_reason = "delete_fpg_phone FILTER PHONE GROUP NUMBER DOES NOT EXIST";
						$data = "$phone_number|$group";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						$stmtA="DELETE FROM vicidial_filter_phone_numbers WHERE phone_number='$phone_number' and filter_phone_group_id='$group';";
						$rslt=mysql_to_mysqli($stmtA, $link);
						$affected_rowsA = mysqli_affected_rows($link);

						### LOG INSERTION Admin Log Table ###
						$SQL_log = "$stmtA|";
						$SQL_log = preg_replace('/;/', '', $SQL_log);
						$SQL_log = addslashes($SQL_log);
						$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='FILTERPHONEGROUPS', event_type='DELETE', record_id='$phone_number', event_code='ADMIN API DELETE NUMBER FROM FILTER PHONE GROUP $group', event_sql=\"$SQL_log\", event_notes='$phone_number|$group|$affected_rowsA';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);

						$result = 'SUCCESS';
						$result_reason = "delete_fpg_phone FILTER PHONE GROUP NUMBER HAS BEEN DELETED";
						$data = "$phone_number|$group";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END delete_fpg_phone
################################################################################


################################################################################
### add_phone - adds phone to the phones table
################################################################################
if ($function == 'add_phone')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and ast_admin_access='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_phone USER DOES NOT HAVE PERMISSION TO ADD PHONES";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($extension)<2) or (strlen($dialplan_number)<2) or (strlen($voicemail_id)<1) or (strlen($phone_login)<1) or (strlen($phone_pass)<1) or (strlen($server_ip)<1) or (strlen($protocol)<1) or (strlen($registration_password)<1) or (strlen($phone_full_name)<1) or (strlen($local_gmt)<1) or (strlen($outbound_cid)<1) or ( ($protocol != 'IAX2') and ($protocol != 'SIP') and ($protocol != 'Zap') and ($protocol != 'EXTERNAL') ) )
				{
				$result = 'ERROR';
				$result_reason = "add_phone YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$extension|$dialplan_number|$voicemail_id|$phone_login|$phone_pass|$server_ip|$protocol|$registration_password|$phone_full_name|$local_gmt|$outbound_cid";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from servers where server_ip='$server_ip';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$server_exists=$row[0];
				if ($server_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "add_phone SERVER DOES NOT EXIST";
					$data = "$server_ip";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT count(*) from phones where extension='$extension' and server_ip='$server_ip';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$phone_exists=$row[0];
					if ($phone_exists > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_phone PHONE ALREADY EXISTS ON THIS SERVER";
						$data = "$server_ip|$extension";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						$stmt="SELECT count(*) from phones where login='$phone_login';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$phone_exists=$row[0];
						if ($phone_exists > 0)
							{
							$result = 'ERROR';
							$result_reason = "add_phone PHONE LOGIN ALREADY EXISTS";
							$data = "$phone_login";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							if ( ($local_gmt != '12.75') and ($local_gmt != '12.00') and ($local_gmt != '11.00') and ($local_gmt != '10.00') and ($local_gmt != '9.50') and ($local_gmt != '9.00') and ($local_gmt != '8.00') and ($local_gmt != '7.00') and ($local_gmt != '6.50') and ($local_gmt != '6.00') and ($local_gmt != '5.75') and ($local_gmt != '5.50') and ($local_gmt != '5.00') and ($local_gmt != '4.50') and ($local_gmt != '4.00') and ($local_gmt != '3.50') and ($local_gmt != '3.00') and ($local_gmt != '2.00') and ($local_gmt != '1.00') and ($local_gmt != '0.00') and ($local_gmt != '-1.00') and ($local_gmt != '-2.00') and ($local_gmt != '-3.00') and ($local_gmt != '-3.50') and ($local_gmt != '-4.00') and ($local_gmt != '-5.00') and ($local_gmt != '-6.00') and ($local_gmt != '-7.00') and ($local_gmt != '-8.00') and ($local_gmt != '-9.00') and ($local_gmt != '-10.00') and ($local_gmt != '-11.00') and ($local_gmt != '-12.00') )
								{
								$result = 'ERROR';
								$result_reason = "add_phone YOU MUST USE A VALID TIMEZONE";
								$data = "$local_gmt";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{
								$is_webphone_SQL='';
								$webphone_auto_answer_SQL='';
								$use_external_server_ip_SQL='';
								$template_id_SQL='';
								$on_hook_agent_SQL='';
								if (preg_match("/^Y$|^N$|^Y_API_LAUNCH$/i",$is_webphone))
									{$is_webphone_SQL = ",is_webphone='$is_webphone'";}
								if (preg_match("/^Y$|^N$/i",$webphone_auto_answer))
									{$webphone_auto_answer_SQL = ",webphone_auto_answer='$webphone_auto_answer'";}
								if (preg_match("/^Y$|^N$/i",$use_external_server_ip))
									{$use_external_server_ip_SQL = ",use_external_server_ip='$use_external_server_ip'";}
								if (preg_match("/^Y$|^N$/i",$on_hook_agent))
									{$on_hook_agent_SQL = ",on_hook_agent='$on_hook_agent'";}
								if (strlen($template_id) > 1)
									{
									if (preg_match("/^--BLANK--$/i",$template_id))
										{$template_id_SQL = ",template_id=''";}
									else
										{
										$stmt="SELECT count(*) from vicidial_conf_templates where template_id='$template_id';";
										$rslt=mysql_to_mysqli($stmt, $link);
										$row=mysqli_fetch_row($rslt);
										$template_id_exists=$row[0];
										if ($template_id_exists > 0)
											{$template_id_SQL = ",template_id='$template_id'";}
										else
											{
											$result = 'ERROR';
											$result_reason = "add_phone TEMPLATE ID DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
											$data = "$phone_login|$template_id";
											echo "$result: $result_reason: |$user|$data\n";
											api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
											exit;
											}
										}
									}

								if (strlen($phone_context)<1) {$phone_context='default';}
								if (strlen($admin_user_group)<1) {$admin_user_group='---ALL---';}

								$stmt="INSERT INTO phones SET  extension='$extension', dialplan_number='$dialplan_number', voicemail_id='$voicemail_id', login='$phone_login', pass='$phone_pass', server_ip='$server_ip', protocol='$protocol', conf_secret='$registration_password', fullname='$phone_full_name', local_gmt='$local_gmt', outbound_cid='$outbound_cid', phone_context='$phone_context', email='$email', active='Y', status='ACTIVE', user_group='$admin_user_group' $is_webphone_SQL $webphone_auto_answer_SQL $use_external_server_ip_SQL $template_id_SQL $on_hook_agent_SQL;";
								$rslt=mysql_to_mysqli($stmt, $link);

								### LOG INSERTION Admin Log Table ###
								$SQL_log = "$stmt|";
								$SQL_log = preg_replace('/;/', '', $SQL_log);
								$SQL_log = addslashes($SQL_log);
								$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='PHONES', event_type='ADD', record_id='$extension', event_code='ADMIN API ADD PHONE', event_sql=\"$SQL_log\", event_notes='phone: $extension|$server_ip';";
								if ($DB) {echo "|$stmt|\n";}
								$rslt=mysql_to_mysqli($stmt, $link);

								$stmtA="UPDATE servers SET rebuild_conf_files='Y' where generate_vicidial_conf='Y' and active_asterisk_server='Y' and server_ip='$server_ip';";
								$rslt=mysql_to_mysqli($stmtA, $link);

								$result = 'SUCCESS';
								$result_reason = "add_phone PHONE HAS BEEN ADDED";
								$data = "$extension|$server_ip|$protocol|$dialplan_number";
								echo "$result: $result_reason - $user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END add_phone
################################################################################






################################################################################
### update_phone - updates phone entry already in the system
################################################################################
if ($function == 'update_phone')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and ast_admin_access='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_phone USER DOES NOT HAVE PERMISSION TO UPDATE PHONES";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($extension)<2) or (strlen($server_ip)<1) )
				{
				$result = 'ERROR';
				$result_reason = "update_phone YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$extension|$server_ip";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$stmt="SELECT count(*) from servers where server_ip='$server_ip';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$server_exists=$row[0];
				if ($server_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "update_phone SERVER DOES NOT EXIST";
					$data = "$server_ip";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT count(*) from phones where extension='$extension' and server_ip='$server_ip' $LOGadmin_viewable_groupsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$phone_exists=$row[0];
					if ($phone_exists < 1)
						{
						$result = 'ERROR';
						$result_reason = "update_phone PHONE DOES NOT EXIST ON THIS SERVER";
						$data = "$server_ip|$extension";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						if ($delete_phone == 'Y')
							{
							$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and ast_delete_phones='1' and user_level >= 8 and active='Y';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$allowed_user=$row[0];
							if ($allowed_user < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_phone USER DOES NOT HAVE PERMISSION TO DELETE PHONES";
								$data = "$allowed_user";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{
								$stmt="DELETE FROM phones WHERE extension='$extension' and server_ip='$server_ip';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$affected_rows = mysqli_affected_rows($link);
								if ($DB) {echo "|$stmt|\n";}

								### LOG INSERTION Admin Log Table ###
								$SQL_log = "$stmt|";
								$SQL_log = preg_replace('/;/', '', $SQL_log);
								$SQL_log = addslashes($SQL_log);
								$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='PHONES', event_type='DELETE', record_id='$extension', event_code='ADMIN API DELETE PHONE', event_sql=\"$SQL_log\", event_notes='phone: $extension|$server_ip';";
								if ($DB) {echo "|$stmt|\n";}
								$rslt=mysql_to_mysqli($stmt, $link);

								$stmtA="UPDATE servers SET rebuild_conf_files='Y' where generate_vicidial_conf='Y' and active_asterisk_server='Y' and server_ip='$server_ip';";
								$rslt=mysql_to_mysqli($stmtA, $link);

								$result = 'SUCCESS';
								$result_reason = "update_phone PHONE HAS BEEN DELETED";
								$data = "$extension|$server_ip|";
								echo "$result: $result_reason - $user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							exit;
							}

						$dialplan_numberSQL='';
						$activeSQL='';
						$outboundcidSQL='';
						$voicemail_idSQL='';
						$phone_loginSQL='';
						$phone_passSQL='';
						$protocolSQL='';
						$registration_passwordSQL='';
						$phone_full_nameSQL='';
						$phone_contextSQL='';
						$emailSQL='';
						$local_gmtSQL='';
						$admin_user_groupSQL='';
						$outbound_alt_cidSQL='';
						$phone_ring_timeoutSQL='';
						$delete_vm_after_emailSQL='';
						$is_webphone_SQL='';
						$webphone_auto_answer_SQL='';
						$use_external_server_ip_SQL='';
						$template_id_SQL='';
						$on_hook_agent_SQL='';

						if (strlen($local_gmt) > 0)
							{
							if ( ($local_gmt != '12.75') and ($local_gmt != '12.00') and ($local_gmt != '11.00') and ($local_gmt != '10.00') and ($local_gmt != '9.50') and ($local_gmt != '9.00') and ($local_gmt != '8.00') and ($local_gmt != '7.00') and ($local_gmt != '6.50') and ($local_gmt != '6.00') and ($local_gmt != '5.75') and ($local_gmt != '5.50') and ($local_gmt != '5.00') and ($local_gmt != '4.50') and ($local_gmt != '4.00') and ($local_gmt != '3.50') and ($local_gmt != '3.00') and ($local_gmt != '2.00') and ($local_gmt != '1.00') and ($local_gmt != '0.00') and ($local_gmt != '-1.00') and ($local_gmt != '-2.00') and ($local_gmt != '-3.00') and ($local_gmt != '-3.50') and ($local_gmt != '-4.00') and ($local_gmt != '-5.00') and ($local_gmt != '-6.00') and ($local_gmt != '-7.00') and ($local_gmt != '-8.00') and ($local_gmt != '-9.00') and ($local_gmt != '-10.00') and ($local_gmt != '-11.00') and ($local_gmt != '-12.00') )
								{
								$result = 'ERROR';
								$result_reason = "update_phone YOU MUST USE A VALID TIMEZONE";
								$data = "$local_gmt";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$local_gmtSQL = " ,local_gmt='$local_gmt'";}
							}
						if (strlen($email) > 0)
							{
							if ($email == '--BLANK--')
								{$emailSQL = " ,email=''";}
							else
								{
								if ( (strlen($email) > 300) or (strlen($email) < 5) )
									{
									$result = 'ERROR';
									$result_reason = "update_phone YOU MUST USE A VALID EMAIL";
									$data = "$email";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$emailSQL = " ,email='$email'";}
								}
							}
						if (strlen($phone_context) > 0)
							{
							if ($phone_context == '--BLANK--')
								{$phone_contextSQL = " ,phone_context=''";}
							else
								{
								if ( (strlen($phone_context) > 30) or (strlen($phone_context) < 2) )
									{
									$result = 'ERROR';
									$result_reason = "update_phone YOU MUST USE A VALID PHONE CONTEXT";
									$data = "$phone_context";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$phone_contextSQL = " ,phone_context='$phone_context'";}
								}
							}
						if (strlen($phone_full_name) > 0)
							{
							if ($phone_full_name == '--BLANK--')
								{$phone_full_nameSQL = " ,fullname=''";}
							else
								{
								if ( (strlen($phone_full_name) > 30) or (strlen($phone_full_name) < 2) )
									{
									$result = 'ERROR';
									$result_reason = "update_phone YOU MUST USE A VALID PHONE NAME";
									$data = "$phone_full_name";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$phone_full_nameSQL = " ,fullname='$phone_full_name'";}
								}
							}
						if (strlen($registration_password) > 0)
							{
							if ($registration_password == '--BLANK--')
								{$registration_passwordSQL = " ,conf_secret=''";}
							else
								{
								if ( (strlen($registration_password) > 30) or (strlen($registration_password) < 2) )
									{
									$result = 'ERROR';
									$result_reason = "update_phone YOU MUST USE A VALID REGISTRATION PASSWORD";
									$data = "$registration_password";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$registration_passwordSQL = " ,conf_secret='$registration_password'";}
								}
							}
						if (strlen($protocol) > 0)
							{
							if ( ($protocol != 'IAX2') and ($protocol != 'SIP') and ($protocol != 'Zap') and ($protocol != 'EXTERNAL') )
								{
								$result = 'ERROR';
								$result_reason = "update_phone YOU MUST USE A VALID PROTOCOL";
								$data = "$protocol";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$protocolSQL = " ,protocol='$protocol'";}
							}
						if (strlen($phone_pass) > 0)
							{
							if ( (strlen($phone_pass) > 30) or (strlen($phone_pass) < 2) )
								{
								$result = 'ERROR';
								$result_reason = "update_phone YOU MUST USE A VALID PHONE PASSWORD";
								$data = "$phone_pass";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$phone_passSQL = " ,pass='$phone_pass'";}
							}
						if (strlen($phone_login) > 0)
							{
							$stmt="SELECT count(*) from phones where login='$phone_login' and extension != '$extension' and server_ip != '$server_ip';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$login_exists=$row[0];
							if ($login_exists > 0)
								{
								$result = 'ERROR';
								$result_reason = "update_phone LOGIN ALREADY EXISTS";
								$data = "$phone_login";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$phone_loginSQL = " ,login='$phone_login'";}
							}
						if (strlen($voicemail_id) > 0)
							{
							if ($voicemail_id == '--BLANK--')
								{$voicemail_idSQL = " ,voicemail_id=''";}
							else
								{
								if ( (strlen($voicemail_id) > 30) or (strlen($voicemail_id) < 2) )
									{
									$result = 'ERROR';
									$result_reason = "update_phone YOU MUST USE A VALID VOICEMAIL NUMBER";
									$data = "$voicemail_id";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$voicemail_idSQL = " ,voicemail_id='$voicemail_id'";}
								}
							}
						if (strlen($dialplan_number) > 0)
							{
							if ( (strlen($dialplan_number) > 30) or (strlen($dialplan_number) < 3) )
								{
								$result = 'ERROR';
								$result_reason = "update_phone YOU MUST USE A VALID DIALPLAN NUMBER";
								$data = "$dialplan_number";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$dialplan_numberSQL = " ,dialplan_number='$dialplan_number'";}
							}
						if (strlen($active) > 0)
							{
							if ( ($active != 'Y') and ($active != 'N') )
								{
								$result = 'ERROR';
								$result_reason = "update_phone ACTIVE MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
								$data = "$active";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$activeSQL = " ,active='$active'";}
							}
						if (strlen($outbound_cid) > 0)
							{
							if ( (strlen($outbound_cid) > 18) or (strlen($outbound_cid) < 6) )
								{
								$result = 'ERROR';
								$result_reason = "update_phone OUTBOUND CID MUST BE FROM 6 TO 18 DIGITS, THIS IS AN OPTIONAL FIELD";
								$data = "$outbound_cid";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$outboundcidSQL = " ,outbound_cid='$outbound_cid'";}
							}
						if (strlen($admin_user_group) > 0)
							{
							if ( (strlen($admin_user_group) > 20) or (strlen($admin_user_group) < 2) )
								{
								$result = 'ERROR';
								$result_reason = "update_phone USER GROUP MUST BE FROM 2 TO 20 CHARACTERS, THIS IS AN OPTIONAL FIELD";
								$data = "$admin_user_group";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$admin_user_groupSQL = " ,user_group='$admin_user_group'";}
							}
						if (strlen($outbound_alt_cid) > 0)
							{
							if ($outbound_alt_cid == '--BLANK--')
								{$outbound_alt_cidSQL = " ,outbound_alt_cid=''";}
							else
								{
								if ( (strlen($outbound_alt_cid) > 18) or (strlen($outbound_alt_cid) < 6) )
									{
									$result = 'ERROR';
									$result_reason = "update_phone OUTBOUND ALT CID MUST BE FROM 6 TO 18 DIGITS, THIS IS AN OPTIONAL FIELD";
									$data = "$outbound_alt_cid";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$outbound_alt_cidSQL = " ,outbound_alt_cid='$outbound_alt_cid'";}
								}
							}
						if (strlen($phone_ring_timeout) > 0)
							{
							if ( ($phone_ring_timeout > 180) or ($phone_ring_timeout < 2) )
								{
								$result = 'ERROR';
								$result_reason = "update_phone PHONE RING TIMEOUT MUST BE FROM 2 TO 180 SECONDS, THIS IS AN OPTIONAL FIELD";
								$data = "$outbound_alt_cid";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$phone_ring_timeoutSQL = " ,phone_ring_timeout='$phone_ring_timeout'";}
							}
						if (strlen($delete_vm_after_email) > 0)
							{
							if ( ($delete_vm_after_email != 'N') and ($delete_vm_after_email != 'Y') )
								{
								$result = 'ERROR';
								$result_reason = "update_phone DELETE VOICEMAIL AFTER EMAIL MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
								$data = "$delete_vm_after_email";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$delete_vm_after_emailSQL = " ,delete_vm_after_email='$delete_vm_after_email'";}
							}
						if (preg_match("/^Y$|^N$|^Y_API_LAUNCH$/i",$is_webphone))
							{$is_webphone_SQL = ",is_webphone='$is_webphone'";}
						if (preg_match("/^Y$|^N$/i",$webphone_auto_answer))
							{$webphone_auto_answer_SQL = ",webphone_auto_answer='$webphone_auto_answer'";}
						if (preg_match("/^Y$|^N$/i",$use_external_server_ip))
							{$use_external_server_ip_SQL = ",use_external_server_ip='$use_external_server_ip'";}
						if (preg_match("/^Y$|^N$/i",$on_hook_agent))
							{$on_hook_agent_SQL = ",on_hook_agent='$on_hook_agent'";}
						if (strlen($template_id) > 1)
							{
							if (preg_match("/^--BLANK--$/i",$template_id))
								{$template_id_SQL = ",template_id=''";}
							else
								{
								$stmt="SELECT count(*) from vicidial_conf_templates where template_id='$template_id';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$template_id_exists=$row[0];
								if ($template_id_exists > 0)
									{$template_id_SQL = ",template_id='$template_id'";}
								else
									{
									$result = 'ERROR';
									$result_reason = "update_phone TEMPLATE ID DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
									$data = "$phone_login|$template_id";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							}

						$updateSQL = "$dialplan_numberSQL$activeSQL$outboundcidSQL$voicemail_idSQL$phone_loginSQL$phone_passSQL$protocolSQL$registration_passwordSQL$phone_full_nameSQL$phone_contextSQL$emailSQL$local_gmtSQL$admin_user_groupSQL$outbound_alt_cidSQL$phone_ring_timeoutSQL$delete_vm_after_emailSQL $is_webphone_SQL $webphone_auto_answer_SQL $use_external_server_ip_SQL $template_id_SQL $on_hook_agent_SQL";


						if (strlen($updateSQL)< 3)
							{
							$result = 'NOTICE';
							$result_reason = "update_phone NO UPDATES DEFINED";
							$data = "$updateSQL";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$stmt="UPDATE phones SET install_directory='' $updateSQL WHERE extension='$extension' and server_ip='$server_ip';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='PHONES', event_type='MODIFY', record_id='$extension', event_code='ADMIN API UPDATE PHONE', event_sql=\"$SQL_log\", event_notes='phone: $extension|$server_ip';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$stmtA="UPDATE servers SET rebuild_conf_files='Y' where generate_vicidial_conf='Y' and active_asterisk_server='Y' and server_ip='$server_ip';";
							$rslt=mysql_to_mysqli($stmtA, $link);

							$result = 'SUCCESS';
							$result_reason = "update_phone PHONE HAS BEEN UPDATED";
							$data = "$extension|$server_ip|";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END update_phone
################################################################################



################################################################################
### add_phone_alias - adds phone alias to the system
################################################################################
if ($function == 'add_phone_alias')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and ast_admin_access='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_phone_alias USER DOES NOT HAVE PERMISSION TO ADD PHONE ALIASES";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($alias_id)<2) or (strlen($phone_logins)<2) or (strlen($alias_name)<1) )
				{
				$result = 'ERROR';
				$result_reason = "add_phone_alias YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$alias_id|$alias_name|$phone_logins";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from phones_alias where alias_id='$alias_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$alias_exists=$row[0];
				if ($alias_exists > 0)
					{
					$result = 'ERROR';
					$result_reason = "add_phone_alias PHONE ALIAS ALREADY EXISTS";
					$data = "$alias_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$phone_loginsX = explode(",",$phone_logins);
					$phone_logins_ct = count($phone_loginsX);
					$k=0;
					while ($k < $phone_logins_ct)
						{
						$phone_check = $phone_loginsX[$k];
						$stmt="SELECT count(*) from phones where login='$phone_check';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$phone_exists=$row[0];
						if ($phone_exists < 1)
							{
							$result = 'ERROR';
							$result_reason = "add_phone_alias PHONE DOES NOT EXIST";
							$data = "$phone_check";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						$k++;
						}

					$stmt="INSERT INTO phones_alias (alias_id,alias_name,logins_list) values('$alias_id','$alias_name','$phone_logins');";
					$rslt=mysql_to_mysqli($stmt, $link);

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='PHONEALIASES', event_type='ADD', record_id='$alias_id', event_code='ADMIN API ADD PHONE ALIAS', event_sql=\"$SQL_log\", event_notes='phones: $phone_logins';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$result = 'SUCCESS';
					$result_reason = "add_phone_alias PHONE ALIAS HAS BEEN ADDED";
					$data = "$alias_id|$alias_name|$phone_logins";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END add_phone_alias
################################################################################





################################################################################
### update_phone_alias - updates phone entry already in the system
################################################################################
if ($function == 'update_phone_alias')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and ast_admin_access='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_phone_alias USER DOES NOT HAVE PERMISSION TO UPDATE PHONE ALIASES";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($alias_id)<2) or ( (strlen($phone_logins)<2) and (strlen($alias_name)<1) and (strlen($delete_alias)<1) ) )
				{
				$result = 'ERROR';
				$result_reason = "update_phone_alias YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$alias_id|$alias_name|$phone_logins";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT count(*) from phones_alias where alias_id='$alias_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$alias_exists=$row[0];
				if ($alias_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "update_phone_alias PHONE ALIAS DOES NOT EXIST";
					$data = "$alias_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					if ($delete_alias == 'Y')
						{
						$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and ast_delete_phones='1' and user_level >= 8 and active='Y';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$allowed_user=$row[0];
						if ($allowed_user < 1)
							{
							$result = 'NOTICE';
							$result_reason = "update_phone_alias USER DOES NOT HAVE PERMISSION TO DELETE PHONE ALIASES";
							$data = "$allowed_user";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							$stmt="DELETE FROM phones_alias where alias_id='$alias_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$affected_rows = mysqli_affected_rows($link);
							if ($DB) {echo "|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='PHONEALIASES', event_type='DELETE', record_id='$alias_id', event_code='ADMIN API DELETE PHONE ALIAS', event_sql=\"$SQL_log\", event_notes='';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_phone_alias PHONE ALIAS HAS BEEN DELETED";
							$data = "$alias_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						exit;
						}

					$phone_loginsSQL='';
					$alias_nameSQL='';

					if (strlen($alias_name) > 0)
						{
						if ( (strlen($alias_name) > 50) or (strlen($alias_name) < 2) )
							{
							$result = 'ERROR';
							$result_reason = "update_phone_alias YOU MUST USE A VALID ALIAS NAME";
							$data = "$alias_name";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$alias_nameSQL = "alias_name='$alias_name',";}
						}
					if (strlen($phone_logins) > 1)
						{
						$phone_loginsX = explode(",",$phone_logins);
						$phone_logins_ct = count($phone_loginsX);
						$k=0;
						while ($k < $phone_logins_ct)
							{
							$phone_check = $phone_loginsX[$k];
							$stmt="SELECT count(*) from phones where login='$phone_check';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$phone_exists=$row[0];
							if ($phone_exists < 1)
								{
								$result = 'ERROR';
								$result_reason = "update_phone_alias PHONE DOES NOT EXIST";
								$data = "$phone_check";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							$k++;
							}
						$phone_loginsSQL = "logins_list='$phone_logins',";
						}


					$updateSQL = "$phone_loginsSQL$alias_nameSQL";
					$updateSQL = preg_replace("/\,$/","",$updateSQL);


					if (strlen($updateSQL)< 3)
						{
						$result = 'NOTICE';
						$result_reason = "update_phone_alias NO UPDATES DEFINED";
						$data = "$updateSQL";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$stmt="UPDATE phones_alias SET $updateSQL WHERE alias_id='$alias_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "|$stmt|\n";}

						### LOG INSERTION Admin Log Table ###
						$SQL_log = "$stmt|";
						$SQL_log = preg_replace('/;/', '', $SQL_log);
						$SQL_log = addslashes($SQL_log);
						$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='PHONEALIASES', event_type='MODIFY', record_id='$alias_id', event_code='ADMIN API UPDATE PHONE', event_sql=\"$SQL_log\", event_notes='phones: $phone_logins';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);

						$result = 'SUCCESS';
						$result_reason = "update_phone_alias PHONE ALIAS HAS BEEN UPDATED";
						$data = "$alias_id|";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END update_phone_alias
################################################################################




################################################################################
### server_refresh - forces a conf file refresh on all telco servers in the cluster
################################################################################
if ($function == 'server_refresh')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and ast_admin_access='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "server_refresh USER DOES NOT HAVE PERMISSION TO REFRESH SERVERS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if (!preg_match("/REFRESH/",$stage))
				{
				$result = 'ERROR';
				$result_reason = "server_refresh YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$alias_id|$alias_name|$phone_logins";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="UPDATE system_settings set reload_timestamp=NOW();";
				$rslt=mysql_to_mysqli($stmt, $link);

				$stmt="UPDATE servers SET rebuild_conf_files='Y';";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "|$stmt|\n";}
				$affected_rows = mysqli_affected_rows($link);

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$stmt|";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='SERVERS', event_type='OTHER', record_id='refresh', event_code='ADMIN API SERVERS REFRESH', event_sql=\"$SQL_log\", event_notes='servers: $affected_rows';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);

				$result = 'SUCCESS';
				$result_reason = "server_refresh SERVER REFRESH HAS BEEN TRIGGERED";
				$data = "$affected_rows";
				echo "$result: $result_reason - $user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		}
	exit;
	}
################################################################################
### END server_refresh
################################################################################



################################################################################
### update_list - updates list information in the vicidial_lists table and other functions
################################################################################
if ($function == 'update_list')
	{
	$list_id = preg_replace('/[^0-9]/','',$list_id);
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_lists='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_list USER DOES NOT HAVE PERMISSION TO UPDATE LISTS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($list_id)<2) or (strlen($list_id)>14) )
				{
				$result = 'ERROR';
				$result_reason = "update_list YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$list_id|$list_name|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				if ($api_list_restrict > 0)
					{
					if (!preg_match("/ $list_id /",$allowed_lists))
						{
						$result = 'ERROR';
						$result_reason = "update_list NOT AN ALLOWED LIST ID";
						$data = "$list_id";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_lists where list_id='$list_id' $LOGallowed_campaignsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$list_exists=$row[0];
				if ( ($list_exists < 1) and ($custom_fields_add != 'Y') and ($custom_fields_update != 'Y') and ($custom_fields_delete != 'Y') and ( (strlen($custom_fields_copy) < 1) or (strlen($custom_fields_copy) > 14) ) )
					{
					if ($insert_if_not_found == 'Y')
						{
						$result = 'NOTICE';
						$result_reason = "update_list LIST DOES NOT EXIST, SENDING TO add_list FUNCTION";
						$data = "$list_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

						$function = 'add_list';
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "update_list LIST DOES NOT EXIST";
						$data = "$list_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				else
					{
					### BEGIN 'custom_fields_add' section
					if ($custom_fields_add == 'Y')
						{
						$add_custom_fields_trigger=0;

						if ($list_exists < 1)
							{
							$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$list_exists=$row[0];
							if ($DB>0) {echo "$list_exists|$stmt|\n";}
							if ($list_exists < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list CUSTOM FIELDS LIST ID TO ADD TO HAS NO CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$list_exists";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						if ($list_exists > 0)
							{
							$stmt="SELECT count(*) from vicidial_users where user='$user' and custom_fields_modify='1';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$custom_fields_modify_exists=$row[0];
							if ($DB>0) {echo "$custom_fields_modify_exists|$stmt|\n";}
							if ($custom_fields_modify_exists < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list USER DOES NOT HAVE PERMISSION TO MODIFY CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$custom_fields_copy|$custom_fields_modify_exists";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								if ($DB>0) {echo "Add custom field triggered|$add_custom_fields_trigger|\n";}
								$add_custom_fields_trigger++;
								}
							}

						if ( ($add_custom_fields_trigger > 0) and (strlen($list_id) > 1) )
							{
							if ( (strlen($field_label) < 1) or (strlen($field_name) < 1) or (strlen($field_size) < 1) or (strlen($field_type) < 1) or (strlen($field_rank) < 1) )
								{
								$result = 'NOTICE';
								$result_reason = "update_list REQUIRED CUSTOM FIELDS VARIABLES ARE MISSING, FIELD NOT ADDED, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$field_label|$field_name|$field_size|$field_type|$field_rank|";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								### BEGIN add custom fields ###
								$admin_lists_custom = 'admin_lists_custom.php';
								if (!preg_match("/^Y$|^N$/",$field_encrypt)) {$field_encrypt = 'N';}
								if (!preg_match("/^DISABLED$|^X_OUT_ALL$|LAST_1|LAST_2|LAST_3|LAST_4|FIRST_1_LAST_4/",$field_show_hide)) {$field_show_hide = 'DISABLED';}
								if (!preg_match("/^TOP$|^LEFT$/",$name_position)) {$name_position = 'LEFT';}
								if (!preg_match("/^HORIZONTAL$|^VERTICAL$/",$multi_position)) {$multi_position = 'HORIZONTAL';}
								if (!preg_match("/^Y$|^N$/",$field_required)) {$field_required = 'N';}
								if (!preg_match("/^Y$|^N$/",$field_duplicate)) {$field_duplicate = 'N';}
								if (!preg_match("/^YES$|^NO$/",$field_rerank)) {$field_rerank = 'NO';}
								if (strlen($field_order) < 1) {$field_order = '1';}
								$temp_webserver = (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]";
								if ($use_internal_webserver == 'Y') {$temp_webserver = "://$SSsounds_web_server";}

								$url = "http$temp_webserver/$SSadmin_web_directory/" . $admin_lists_custom;
								$url_post_fields = "action=ADD_CUSTOM_FIELD&list_id=$list_id&field_label=$field_label&field_name=$field_name&field_size=$field_size&field_type=$field_type&field_rank=$field_rank&field_order=$field_order&field_rerank=$field_rerank&field_max=$field_max&field_default=$field_default&field_options=$field_options&field_duplicate=$field_duplicate&field_description=$field_description&field_help=$field_help&field_required=$field_required&multi_position=$multi_position&name_position=$name_position&field_encrypt=$field_encrypt&field_show_hide=$field_show_hide";

								if ($DB>0) {echo "Add custom fields url|$url|$url_post_fields|\n";}
								# use cURL to call the copy custom fields code
								$curl = curl_init();
								
								# Set some options - we are passing in a useragent too here
								curl_setopt_array($curl, array(
									CURLOPT_RETURNTRANSFER => 1,
									CURLOPT_URL => $url,
									CURLOPT_USERPWD => "$user:$pass",
									CURLOPT_USERAGENT => 'non_agent_api.php',
									CURLOPT_POST => 1,
									CURLOPT_POSTFIELDS => "$url_post_fields"
								));
								
								# Send the request & save response to $resp
								$resp = curl_exec($curl);
								$temp_response = 'NONE';
								if (preg_match('/ERROR:/',$resp)) {$temp_response = 'ERROR: Field not added';}
								if (preg_match('/SUCCESS:/',$resp)) {$temp_response = 'SUCCESS: Field added';}
								
								# Close request to clear up some resources
								curl_close($curl);
								### END copy custom fields ###

								$result = 'NOTICE';
								$result_reason = "update_list ADD CUSTOM FIELD COMMAND SENT";
								$data = "$list_id|$field_label|$field_type|$temp_response|";
								echo "$result: $result_reason - $user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						}
					### END 'custom_fields_add' section


					### BEGIN 'custom_fields_update' section
					if ($custom_fields_update == 'Y')
						{
						$update_custom_fields_trigger=0;

						if ($list_exists < 1)
							{
							$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$list_exists=$row[0];
							if ($DB>0) {echo "$list_exists|$stmt|\n";}
							if ($list_exists < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list CUSTOM FIELDS LIST ID TO UPDATE TO HAS NO CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$list_exists";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						if ($list_exists > 0)
							{
							$stmt="SELECT count(*) from vicidial_users where user='$user' and custom_fields_modify='1';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$custom_fields_modify_exists=$row[0];
							if ($DB>0) {echo "$custom_fields_modify_exists|$stmt|\n";}
							if ($custom_fields_modify_exists < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list USER DOES NOT HAVE PERMISSION TO MODIFY CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$custom_fields_copy|$custom_fields_modify_exists";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								if ($DB>0) {echo "Update custom field triggered|$update_custom_fields_trigger|\n";}
								$update_custom_fields_trigger++;
								}
							}

						if ( ($update_custom_fields_trigger > 0) and (strlen($list_id) > 1) )
							{
							if (strlen($field_label) < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list REQUIRED CUSTOM FIELDS VARIABLES ARE MISSING, FIELD NOT UPDATED, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$field_label|";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								# Gather existing settings for this custom field
								$stmt="SELECT field_id,field_label,field_name,field_description,field_rank,field_help,field_type,field_options,field_size,field_max,field_default,field_cost,field_required,multi_position,name_position,field_order,field_encrypt,field_show_hide,field_duplicate from vicidial_lists_fields where list_id='$list_id' and field_label='$field_label';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$fields_to_print = mysqli_num_rows($rslt);
								if ($fields_to_print < 1) 
									{
									$result = 'NOTICE';
									$result_reason = "update_list FIELD DOES NOT EXIST, FIELD NOT UPDATED, THIS IS AN OPTIONAL FIELD";
									$data = "$list_id|$field_label|$field_name|$field_size|$field_type|$field_rank|";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								else
									{
									$rowx=mysqli_fetch_row($rslt);
									$A_field_id =			$rowx[0];
									$A_field_label =		$rowx[1];
									$A_field_name =			$rowx[2];
									$A_field_description =	$rowx[3];
									$A_field_rank =			$rowx[4];
									$A_field_help =			$rowx[5];
									$A_field_type =			$rowx[6];
									$A_field_options =		$rowx[7];
									$A_field_size =			$rowx[8];
									$A_field_max =			$rowx[9];
									$A_field_default =		$rowx[10];
									$A_field_required =		$rowx[12];
									$A_multi_position =		$rowx[13];
									$A_name_position =		$rowx[14];
									$A_field_order =		$rowx[15];
									$A_field_encrypt =		$rowx[16];
									$A_field_show_hide =	$rowx[17];
									$A_field_duplicate =	$rowx[18];

									### BEGIN update custom fields ###

									if (!preg_match("/^TOP$|^LEFT$/",$name_position)) {$name_position = $A_name_position;}
									if (!preg_match("/^HORIZONTAL$|^VERTICAL$/",$multi_position)) {$multi_position = $A_multi_position;}
									if (!preg_match("/^Y$|^N$/",$field_required)) {$field_required = $A_field_required;}
									if (!preg_match("/^Y$|^N$/",$field_duplicate)) {$field_duplicate = $A_field_duplicate;}
									if (!preg_match("/^YES$|^NO$/",$field_rerank)) {$field_rerank = 'NO';}
									if (!preg_match("/^Y$|^N$/",$field_encrypt)) {$field_encrypt = $A_field_encrypt;}
									if (!preg_match("/^DISABLED$|^X_OUT_ALL$|LAST_1|LAST_2|LAST_3|LAST_4|FIRST_1_LAST_4/",$field_show_hide)) {$field_show_hide = $A_field_show_hide;}
									if (strlen($field_name) < 1) {$field_name = $A_field_name;}
									if (strlen($field_description) < 1) {$field_description = $A_field_description;}
									if (strlen($field_rank) < 1) {$field_rank = $A_field_rank;}
									if (strlen($field_help) < 1) {$field_help = $A_field_help;}
									if (strlen($field_type) < 1) {$field_type = $A_field_type;}
									if (strlen($field_options) < 1) {$field_options = $A_field_options;}
									if (strlen($field_size) < 1) {$field_size = $A_field_size;}
									if (strlen($field_max) < 1) {$field_max = $A_field_max;}
									if (strlen($field_default) < 1) {$field_default = $A_field_default;}
									if (strlen($field_order) < 1) {$field_order = $A_field_order;}
									if ($field_description == '--BLANK--') {$field_description = '';}
									if ($field_help == '--BLANK--') {$field_help = '';}
									if ($field_options == '--BLANK--') {$field_options = '';}
									if ($field_default == '--BLANK--') {$field_default = '';}

									$admin_lists_custom = 'admin_lists_custom.php';
									$temp_webserver = (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]";
									if ($use_internal_webserver == 'Y') {$temp_webserver = "://$SSsounds_web_server";}
									$url = "http$temp_webserver/$SSadmin_web_directory/" . $admin_lists_custom;
									$url_post_fields = "action=MODIFY_CUSTOM_FIELD_SUBMIT&list_id=$list_id&field_id=$A_field_id&field_label=$field_label&field_name=$field_name&field_size=$field_size&field_type=$field_type&field_rank=$field_rank&field_order=$field_order&field_rerank=$field_rerank&field_max=$field_max&field_default=$field_default&field_options=$field_options&field_duplicate=$field_duplicate&field_description=$field_description&field_help=$field_help&field_required=$field_required&multi_position=$multi_position&name_position=$name_position&field_encrypt=$field_encrypt&field_show_hide=$field_show_hide";

									if ($DB>0) {echo "Update custom fields url|$url|$url_post_fields|\n";}
									# use cURL to call the copy custom fields code
									$curl = curl_init();
									
									# Set some options - we are passing in a useragent too here
									curl_setopt_array($curl, array(
										CURLOPT_RETURNTRANSFER => 1,
										CURLOPT_URL => $url,
										CURLOPT_USERPWD => "$user:$pass",
										CURLOPT_USERAGENT => 'non_agent_api.php',
										CURLOPT_POST => 1,
										CURLOPT_POSTFIELDS => "$url_post_fields"
									));
									
									# Send the request & save response to $resp
									$resp = curl_exec($curl);
									$temp_response = 'NONE';
									if (preg_match('/ERROR:/',$resp)) {$temp_response = 'ERROR: Field not updated';}
									if (preg_match('/SUCCESS:/',$resp)) {$temp_response = 'SUCCESS: Field updated';}
									
									# Close request to clear up some resources
									curl_close($curl);
									### END copy custom fields ###

									$result = 'NOTICE';
									$result_reason = "update_list UPDATE CUSTOM FIELD COMMAND SENT";
									$data = "$list_id|$field_label|$field_type|$temp_response|";
									echo "$result: $result_reason - $user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								}
							}
						}
					### END 'custom_fields_update' section

					### BEGIN 'custom_fields_delete' section
					if ($custom_fields_delete == 'Y')
						{
						$delete_custom_fields_trigger=0;

						if ($list_exists < 1)
							{
							$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$list_exists=$row[0];
							if ($DB>0) {echo "$list_exists|$stmt|\n";}
							if ($list_exists < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list CUSTOM FIELDS LIST ID TO DELETE TO HAS NO CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$list_exists";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						if ($list_exists > 0)
							{
							$stmt="SELECT count(*) from vicidial_users where user='$user' and custom_fields_modify='1';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$custom_fields_modify_exists=$row[0];
							if ($DB>0) {echo "$custom_fields_modify_exists|$stmt|\n";}
							if ($custom_fields_modify_exists < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list USER DOES NOT HAVE PERMISSION TO MODIFY CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$custom_fields_copy|$custom_fields_modify_exists";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								if ($DB>0) {echo "Delete custom field triggered|$delete_custom_fields_trigger|\n";}
								$delete_custom_fields_trigger++;
								}
							}

						if ( ($delete_custom_fields_trigger > 0) and (strlen($list_id) > 1) )
							{
							if (strlen($field_label) < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list REQUIRED CUSTOM FIELDS VARIABLES ARE MISSING, FIELD NOT DELETED, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$field_label|";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								# Gather existing settings for this custom field
								$stmt="SELECT field_id,field_label from vicidial_lists_fields where list_id='$list_id' and field_label='$field_label';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$fields_to_print = mysqli_num_rows($rslt);
								if ($fields_to_print < 1) 
									{
									$result = 'NOTICE';
									$result_reason = "update_list FIELD DOES NOT EXIST, FIELD NOT DELETED, THIS IS AN OPTIONAL FIELD";
									$data = "$list_id|$field_label|";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								else
									{
									$rowx=mysqli_fetch_row($rslt);
									$A_field_id =			$rowx[0];
									$A_field_label =		$rowx[1];

									### BEGIN delete custom fields ###

									$admin_lists_custom = 'admin_lists_custom.php';
									$temp_webserver = (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]";
									if ($use_internal_webserver == 'Y') {$temp_webserver = "://$SSsounds_web_server";}
									$url = "http$temp_webserver/$SSadmin_web_directory/" . $admin_lists_custom;
									$url_post_fields = "action=DELETE_CUSTOM_FIELD&list_id=$list_id&field_id=$A_field_id&field_label=$field_label&ConFiRm=YES";

									if ($DB>0) {echo "Delete custom fields url|$url|$url_post_fields|\n";}
									# use cURL to call the copy custom fields code
									$curl = curl_init();
									
									# Set some options - we are passing in a useragent too here
									curl_setopt_array($curl, array(
										CURLOPT_RETURNTRANSFER => 1,
										CURLOPT_URL => $url,
										CURLOPT_USERPWD => "$user:$pass",
										CURLOPT_USERAGENT => 'non_agent_api.php',
										CURLOPT_POST => 1,
										CURLOPT_POSTFIELDS => "$url_post_fields"
									));
									
									# Send the request & save response to $resp
									$resp = curl_exec($curl);
									$temp_response = 'NONE';
									if (preg_match('/ERROR:/',$resp)) {$temp_response = 'ERROR: Field not deleted';}
									if (preg_match('/SUCCESS:/',$resp)) {$temp_response = 'SUCCESS: Field deleted';}
									
									# Close request to clear up some resources
									curl_close($curl);
									### END copy custom fields ###

									$result = 'NOTICE';
									$result_reason = "update_list DELETE CUSTOM FIELD COMMAND SENT";
									$data = "$list_id|$field_label|$field_id|$temp_response|";
									echo "$result: $result_reason - $user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								}
							}
						}
					### END 'custom_fields_delete' section

					$campaignSQL='';
					$scriptSQL='';
					$dropingroupSQL='';
					$listnameSQL='';
					$activeSQL='';
					$outboundcidSQL='';
					$ammessageSQL='';
					$webformSQL='';
					$webformtwoSQL='';
					$webformthreeSQL='';
					$resettimeSQL='';
					$expiration_dateSQL='';
					$list_descriptionSQL='';
					$tz_methodSQL='';
					$local_call_timeSQL='';
					$xferconf_oneSQL='';
					$xferconf_twoSQL='';
					$xferconf_threeSQL='';
					$xferconf_fourSQL='';
					$xferconf_fiveSQL='';

					if (strlen($campaign_id) > 0)
						{
						$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$campaign_exists=$row[0];
						if ($campaign_exists < 1)
							{
							$result = 'ERROR';
							$result_reason = "update_list CAMPAIGN DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
							$data = "$campaign_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$campaignSQL = " ,campaign_id='$campaign_id'";}
						}
					if (strlen($script) > 0)
						{
						if ($script == '--BLANK--')
							{$scriptSQL = " ,agent_script_override=''";}
						else
							{
							$stmt="SELECT count(*) from vicidial_scripts where script_id='$script';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$script_exists=$row[0];
							if ($script_exists < 1)
								{
								$result = 'ERROR';
								$result_reason = "update_list SCRIPT DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
								$data = "$script";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$scriptSQL = " ,agent_script_override='$script'";}
							}
						}
					if (strlen($drop_inbound_group) > 0)
						{
						if ($drop_inbound_group == '--BLANK--')
							{$dropingroupSQL = " ,drop_inbound_group_override=''";}
						else
							{
							$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$drop_inbound_group';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$script_exists=$row[0];
							if ($script_exists < 1)
								{
								$result = 'ERROR';
								$result_reason = "update_list IN-GROUP DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
								$data = "$drop_inbound_group";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$dropingroupSQL = " ,drop_inbound_group_override='$drop_inbound_group'";}
							}
						}
					if (strlen($list_name) > 0)
						{
						if ( (strlen($list_name) > 30) or (strlen($list_name) < 6) )
							{
							$result = 'ERROR';
							$result_reason = "update_list LIST NAME MUST BE FROM 6 TO 30 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$list_name";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$listnameSQL = " ,list_name='$list_name'";}
						}
					if (strlen($active) > 0)
						{
						if ( ($active != 'Y') and ($active != 'N') )
							{
							$result = 'ERROR';
							$result_reason = "update_list ACTIVE MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
							$data = "$active";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$activeSQL = " ,active='$active'";}
						}
					if (strlen($outbound_cid) > 0)
						{
						if ($outbound_cid == '--BLANK--')
							{$outboundcidSQL = " ,campaign_cid_override=''";}
						else
							{
							if ( (strlen($outbound_cid) > 18) or (strlen($outbound_cid) < 6) )
								{
								$result = 'ERROR';
								$result_reason = "update_list OUTBOUND CID MUST BE FROM 6 TO 18 DIGITS, THIS IS AN OPTIONAL FIELD";
								$data = "$outbound_cid";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$outboundcidSQL = " ,campaign_cid_override='$outbound_cid'";}
							}
						}
					if (strlen($am_message) > 0)
						{
						if ($am_message == '--BLANK--')
							{$ammessageSQL = " ,am_message_exten_override=''";}
						else
							{
							if (strlen($am_message) > 100)
								{
								$result = 'ERROR';
								$result_reason = "update_list ANSWERING MACHINE MESSAGE MUST LESS THAN 101 DIGITS, THIS IS AN OPTIONAL FIELD";
								$data = "$am_message";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$ammessageSQL = " ,am_message_exten_override='$am_message'";}
							}
						}
					if (strlen($reset_time) > 0)
						{
						if ($reset_time == '--BLANK--')
							{$resettimeSQL = " ,reset_time=''";}
						else
							{
							if (strlen($reset_time) < 4)
								{
								$result = 'ERROR';
								$result_reason = "update_list RESET TIME IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$reset_time";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$resettimeSQL = " ,reset_time='$reset_time'";}
							}
						}
					if (strlen($expiration_date) > 9)
						{
						if ($expiration_date == '--BLANK--')
							{$expiration_dateSQL = " ,expiration_date='2099-12-31'";}
						else
							{
							if (strlen($expiration_date) < 10)
								{
								$result = 'ERROR';
								$result_reason = "update_list EXPIRATION DATE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$expiration_date";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$resettimeSQL = " ,expiration_date='$expiration_date'";}
							}
						}
					if (strlen($web_form_address) > 0)
						{
						if (preg_match("/%3A%2F%2F/",$web_form_address))
							{
							$web_form_address = urldecode($web_form_address);
							$web_form_address = preg_replace("/ /",'+',$web_form_address);
							}
						if ($web_form_address == '--BLANK--')
							{$webformSQL = " ,web_form_address=''";}
						else
							{$webformSQL = " ,web_form_address='$web_form_address'";}
						}
					if (strlen($web_form_address_two) > 0)
						{
						if (preg_match("/%3A%2F%2F/",$web_form_address_two))
							{
							$web_form_address_two = urldecode($web_form_address_two);
							$web_form_address_two = preg_replace("/ /",'+',$web_form_address_two);
							}
						if ($web_form_address_two == '--BLANK--')
							{$webformtwoSQL = " ,web_form_address_two=''";}
						else
							{$webformtwoSQL = " ,web_form_address_two='$web_form_address_two'";}
						}
					if (strlen($web_form_address_three) > 0)
						{
						if (preg_match("/%3A%2F%2F/",$web_form_address_three))
							{
							$web_form_address_three = urldecode($web_form_address_three);
							$web_form_address_three = preg_replace("/ /",'+',$web_form_address_three);
							}
						if ($web_form_address_three == '--BLANK--')
							{$webformthreeSQL = " ,web_form_address_three=''";}
						else
							{$webformthreeSQL = " ,web_form_address_three='$web_form_address_three'";}
						}
					if (strlen($list_description) > 0)
						{
						if ($list_description == '--BLANK--')
							{$list_descriptionSQL = " ,list_description=''";}
						else
							{$list_descriptionSQL = " ,list_description='$list_description'";}
						}
					if (strlen($tz_method) > 0)
						{
						if (preg_match("/^COUNTRY_AND_AREA_CODE$|^POSTAL_CODE$|^NANPA_PREFIX$|^OWNER_TIME_ZONE_CODE$/",$tz_method))
							{$tz_methodSQL = " ,time_zone_setting='$tz_method'";}
						else
							{
							$result = 'ERROR';
							$result_reason = "update_list TIME ZONE METHOD IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$tz_method";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					if (strlen($local_call_time) > 0)
						{
						if (preg_match("/^campaign$/",$local_call_time))
							{$local_call_timeSQL = " ,local_call_time='$local_call_time'";}
						else
							{
							$stmt="SELECT count(*) from vicidial_call_times where call_time_id='$local_call_time';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$call_time_exists=$row[0];
							if ($call_time_exists < 1)
								{
								$result = 'ERROR';
								$result_reason = "update_list LOCAL CALL TIME DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
								$data = "$local_call_time";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$local_call_timeSQL = " ,local_call_time='$local_call_time'";}
							}
						}
					if (strlen($xferconf_one) > 0)
						{
						if ($xferconf_one == '--BLANK--')
							{$xferconf_oneSQL = " ,xferconf_a_number=''";}
						else
							{
							if (strlen($xferconf_one) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_list TRANSFER CONF OVERRIDE ONE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_one";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_oneSQL = " ,xferconf_a_number='$xferconf_one'";}
							}
						}
					if (strlen($xferconf_two) > 0)
						{
						if ($xferconf_two == '--BLANK--')
							{$xferconf_twoSQL = " ,xferconf_b_number=''";}
						else
							{
							if (strlen($xferconf_two) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_list TRANSFER CONF OVERRIDE TWO IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_two";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_twoSQL = " ,xferconf_b_number='$xferconf_two'";}
							}
						}
					if (strlen($xferconf_three) > 0)
						{
						if ($xferconf_three == '--BLANK--')
							{$xferconf_threeSQL = " ,xferconf_c_number=''";}
						else
							{
							if (strlen($xferconf_three) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_list TRANSFER CONF OVERRIDE THREE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_three";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_threeSQL = " ,xferconf_c_number='$xferconf_three'";}
							}
						}
					if (strlen($xferconf_four) > 0)
						{
						if ($xferconf_four == '--BLANK--')
							{$xferconf_fourSQL = " ,xferconf_d_number=''";}
						else
							{
							if (strlen($xferconf_four) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_list TRANSFER CONF OVERRIDE FOUR IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_four";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_fourSQL = " ,xferconf_d_number='$xferconf_four'";}
							}
						}
					if (strlen($xferconf_five) > 0)
						{
						if ($xferconf_five == '--BLANK--')
							{$xferconf_fiveSQL = " ,xferconf_e_number=''";}
						else
							{
							if (strlen($xferconf_five) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_list TRANSFER CONF OVERRIDE FIVE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_five";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_fiveSQL = " ,xferconf_e_number='$xferconf_five'";}
							}
						}

					$updateSQL = "$webformthreeSQL$webformtwoSQL$webformSQL$ammessageSQL$outboundcidSQL$activeSQL$listnameSQL$campaignSQL$scriptSQL$dropingroupSQL$resettimeSQL$expiration_dateSQL$list_descriptionSQL$tz_methodSQL$local_call_timeSQL$xferconf_oneSQL$xferconf_twoSQL$xferconf_threeSQL$xferconf_fourSQL$xferconf_fiveSQL";

					if (strlen($updateSQL)< 3)
						{
						$result = 'NOTICE';
						$result_reason = "update_list NO UPDATES DEFINED";
						$data = "$updateSQL";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						if (strlen($campaignSQL) > 3)
							{
							$stmt="SELECT campaign_id from vicidial_lists where list_id='$list_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$old_campaign_id=$row[0];
							if ($campaign_id != "$old_campaign_id")
								{
								$stmt="DELETE from vicidial_hopper where list_id='$list_id' and campaign_id='$old_campaign_id';";
								$rslt=mysql_to_mysqli($stmt, $link);
								if ($DB) {echo "|$stmt|\n";}
								}
							}

						$stmt="UPDATE vicidial_lists SET list_changedate='$NOW_TIME' $updateSQL WHERE list_id='$list_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "|$stmt|\n";}

						### LOG INSERTION Admin Log Table ###
						$SQL_log = "$stmt|";
						$SQL_log = preg_replace('/;/', '', $SQL_log);
						$SQL_log = addslashes($SQL_log);
						$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LISTS', event_type='MODIFY', record_id='$list_id', event_code='ADMIN API UPDATE LIST', event_sql=\"$SQL_log\", event_notes='list: $list_id';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);

						$result = 'SUCCESS';
						$result_reason = "update_list LIST HAS BEEN UPDATED";
						$data = "$list_id";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}

					if ($reset_list == 'Y')
						{
						$stmt="SELECT daily_reset_limit,resets_today from vicidial_lists where list_id='$list_id' $LOGallowed_campaignsSQL;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$daily_reset_limit =	$row[0];
						$resets_today =			$row[1];
						if ( ($daily_reset_limit > $resets_today) or ($daily_reset_limit < 0) )
							{
							$stmt="UPDATE vicidial_lists SET list_changedate='$NOW_TIME',resets_today=(resets_today + 1) WHERE list_id='$list_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "|$stmt|\n";}

							$resets_today = ($resets_today + 1);

							$stmtB="UPDATE $vicidial_list_table SET called_since_last_reset='N' WHERE list_id='$list_id';";
							$rslt=mysql_to_mysqli($stmtB, $link);
							$affected_rowsB = mysqli_affected_rows($link);
							if ($DB) {echo "|$stmtB|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|$stmtB|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LISTS', event_type='RESET', record_id='$list_id', event_code='ADMIN API RESET LIST', event_sql=\"$SQL_log\", event_notes='$affected_rowsB leads reset, list resets today: $resets_today';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'NOTICE';
							$result_reason = "update_list LEADS IN LIST HAVE BEEN RESET";
							$data = "$list_id|$affected_rows";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LISTS', event_type='RESET', record_id='$list_id', event_code='ADMIN API RESET LIST FAILED', event_sql=\"\", event_notes='daily list resets limit reached: $resets_today / $daily_reset_limit';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'NOTICE';
							$result_reason = "update_list LIST RESET FAILED, daily reset limit reached: $resets_today / $daily_reset_limit";
							$data = "$list_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}

					if ($delete_list == 'Y')
						{
						$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_lists='1' and delete_lists='1' and user_level >= 8 and active='Y';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$allowed_user=$row[0];
						if ($allowed_user < 1)
							{
							$result = 'NOTICE';
							$result_reason = "update_list USER DOES NOT HAVE PERMISSION TO DELETE LISTS";
							$data = "$allowed_user";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							$stmt="DELETE FROM vicidial_lists WHERE list_id='$list_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$affected_rows = mysqli_affected_rows($link);
							if ($DB) {echo "|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LISTS', event_type='DELETE', record_id='$list_id', event_code='ADMIN API DELETE LIST', event_sql=\"$SQL_log\", event_notes='list: $list_id';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'NOTICE';
							$result_reason = "update_list LIST HAS BEEN DELETED";
							$data = "$list_id|$affected_rows";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}

					if ($delete_leads == 'Y')
						{
						$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_lists='1' and delete_lists='1' and modify_leads IN('1','2','3','4') and user_level >= 8 and active='Y';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$allowed_user=$row[0];
						if ($allowed_user < 1)
							{
							$result = 'NOTICE';
							$result_reason = "update_list USER DOES NOT HAVE PERMISSION TO DELETE LEADS";
							$data = "$allowed_user";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							$stmt="DELETE FROM $vicidial_list_table WHERE list_id='$list_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$affected_rows = mysqli_affected_rows($link);
							if ($DB) {echo "|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LISTS', event_type='DELETE', record_id='$list_id', event_code='ADMIN API DELETE LEADS', event_sql=\"$SQL_log\", event_notes='list: $list_id';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'NOTICE';
							$result_reason = "update_list LEADS IN LIST HAVE BEEN DELETED";
							$data = "$list_id|$affected_rows";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}

					$copy_custom_fields_trigger=0;
					if ( (strlen($custom_fields_copy) > 1) and (strlen($custom_fields_copy) < 15) )
						{
						$stmt="SELECT count(*) from vicidial_lists where list_id='$custom_fields_copy';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$custom_fields_copy_exists=$row[0];
						if ($DB>0) {echo "$custom_fields_copy_exists|$stmt|\n";}
						if ($custom_fields_copy_exists < 1)
							{
							$result = 'NOTICE';
							$result_reason = "update_list CUSTOM FIELDS LIST ID TO COPY FROM DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
							$data = "$list_id|$custom_fields_copy|$custom_fields_copy_exists";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$custom_fields_copy';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$vicidial_lists_fields_exists=$row[0];
							if ($DB>0) {echo "$vicidial_lists_fields_exists|$stmt|\n";}
							if ($vicidial_lists_fields_exists < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_list CUSTOM FIELDS LIST ID TO COPY FROM HAS NO CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
								$data = "$list_id|$custom_fields_copy|$vicidial_lists_fields_exists";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								$stmt="SELECT count(*) from vicidial_users where user='$user' and custom_fields_modify='1';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$custom_fields_modify_exists=$row[0];
								if ($DB>0) {echo "$custom_fields_modify_exists|$stmt|\n";}
								if ($custom_fields_modify_exists < 1)
									{
									$result = 'NOTICE';
									$result_reason = "update_list USER DOES NOT HAVE PERMISSION TO MODIFY CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
									$data = "$list_id|$custom_fields_copy|$custom_fields_modify_exists";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								else
									{
									if ($DB>0) {echo "Copy custom fields triggered|$copy_custom_fields_trigger|\n";}
									$copy_custom_fields_trigger++;
									}
								}
							}

						if ( ($copy_custom_fields_trigger > 0) and (strlen($custom_fields_copy) > 1) )
							{
							### BEGIN copy custom fields ###
							$admin_lists_custom = 'admin_lists_custom.php';
							if (!preg_match("/^APPEND$|^UPDATE$|^REPLACE$/",$custom_copy_method))
								{$custom_copy_method = 'APPEND';}
							$temp_webserver = (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]";
							if ($use_internal_webserver == 'Y') {$temp_webserver = "://$SSsounds_web_server";}

							$url = "http$temp_webserver/$SSadmin_web_directory/" . $admin_lists_custom . "?copy_option=" . $custom_copy_method . "&action=COPY_FIELDS_SUBMIT&list_id=$list_id&source_list_id=$custom_fields_copy";

							if ($DB>0) {echo "Copy custom fields url|$url|\n";}
							# use cURL to call the copy custom fields code
							$curl = curl_init();

							# Set some options - we are passing in a useragent too here
							curl_setopt_array($curl, array(
								CURLOPT_RETURNTRANSFER => 1,
								CURLOPT_URL => $url,
								CURLOPT_USERPWD => "$user:$pass",
								CURLOPT_USERAGENT => 'non_agent_api.php'
							));

							# Send the request & save response to $resp
							$resp = curl_exec($curl);
							$temp_response = 'NONE';
							if (preg_match('/ERROR:/',$resp)) {$temp_response = 'ERROR: Fields not copied';}
							if (preg_match('/SUCCESS:/',$resp)) {$temp_response = 'SUCCESS: Fields copied';}

							# Close request to clear up some resources
							curl_close($curl);
							### END copy custom fields ###

							$result = 'NOTICE';
							$result_reason = "update_list COPY CUSTOM FIELDS COMMAND SENT";
							$data = "$list_id|$custom_fields_copy|$custom_copy_method|$temp_response|";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	if ($function != 'add_list')
		{exit;}
	}
################################################################################
### END update_list
################################################################################





################################################################################
### list_info - summary information about a list
################################################################################
if ($function == 'list_info')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_lists='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "list_info USER DOES NOT HAVE PERMISSION TO MODIFY LISTS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($list_id)<2) or (strlen($list_id)>14) )
				{
				$result = 'ERROR';
				$result_reason = "list_info YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$list_id|$list_name|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				if ($api_list_restrict > 0)
					{
					if (!preg_match("/ $list_id /",$allowed_lists))
						{
						$result = 'ERROR';
						$result_reason = "list_info NOT AN ALLOWED LIST ID";
						$data = "$list_id";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_lists where list_id='$list_id' $LOGallowed_campaignsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$list_exists=$row[0];
				if ($list_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "list_info LIST DOES NOT EXIST";
					$data = "$list_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT list_name,campaign_id,active,list_changedate,list_lastcalldate,expiration_date,resets_today from vicidial_lists where list_id='$list_id' $LOGallowed_campaignsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$list_name =			$row[0];
					$campaign_id =			$row[1];
					$active =				$row[2];
					$list_changedate =		$row[3];
					$list_lastcalldate =	$row[4];
					$expiration_date =		$row[5];
					$resets_today =			$row[6];

					$output='';
					$DLset=0;
					if ($stage == 'csv')
						{$DL = ',';   $DLset++;}
					if ($stage == 'tab')
						{$DL = "\t";   $DLset++;}
					if ($stage == 'pipe')
						{$DL = '|';   $DLset++;}
					if ($DLset < 1)
						{$DL='|';}
					if ($header == 'YES')
						{$output .= 'list_id' . $DL . 'list_name' . $DL . 'campaign_id' . $DL . 'active' . $DL . 'list_changedate' . $DL . 'list_lastcalldate' . $DL . 'expiration_date' . $DL . 'resets_today';}
					$leads_counts_output='';
					if ($leads_counts == 'Y')
						{
						if ($header == 'YES')
							{$output .= $DL . 'all_leads_count' . $DL . 'new_leads_count';}

						$stmt="SELECT count(*) from $vicidial_list_table where list_id='$list_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$all_leads_count =			$row[0];

						$stmt="SELECT count(*) from $vicidial_list_table where list_id='$list_id' and status='NEW';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$new_leads_count =			$row[0];

						$leads_counts_output .= $DL . "$all_leads_count" . $DL . "$new_leads_count";
						}
					$leads_dialable_output='';
					if ($dialable_count == 'Y')
						{
						if ($header == 'YES')
							{$output .= $DL . 'dialable_count';}

						$stmt="SELECT dial_statuses,local_call_time,lead_filter_id,drop_lockout_time,call_count_limit from vicidial_campaigns where campaign_id='$campaign_id' $LOGallowed_campaignsSQL;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$camps_to_print = mysqli_num_rows($rslt);
						if ($camps_to_print > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$dial_statuses =		$row[0];
							$local_call_time =		$row[1];
							$lead_filter_id =		$row[2];
							$drop_lockout_time =	$row[3];
							$call_count_limit =		$row[4];
							}

						$stmt="SELECT lead_filter_sql from vicidial_lead_filters where lead_filter_id='$lead_filter_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$filters_to_print = mysqli_num_rows($rslt);
						$filterSQL='';
						if ($filters_to_print > 0) 
							{
							$rowx=mysqli_fetch_row($rslt);
							$filterSQL = "$rowx[0]";
							}
						$filterSQL = preg_replace("/\\\\/","",$filterSQL);
						$filterSQL = preg_replace('/^and|and$|^or|or$/i', '',$filterSQL);
						if (strlen($filterSQL)>4)
							{$fSQL = "and ($filterSQL)";}
						else
							{$fSQL = '';}

						### call function to calculate and print dialable leads
						$single_status=0;
						$only_return=1;
						$leads_dialable_output = dialable_leads($DB,$link,$local_call_time,$dial_statuses,"'$list_id'",$drop_lockout_time,$call_count_limit,$single_status,$fSQL,$only_return);
						}
					if ($header == 'YES')
						{$output .= "\n";}

					$output .= "$list_id" . $DL . "$list_name" . $DL . "$campaign_id" . $DL . "$active" . $DL . "$list_changedate" . $DL . "$list_lastcalldate" . $DL . "$expiration_date" . $DL . "$resets_today" . $leads_counts_output . $DL . $leads_dialable_output . "\n";

					$result = 'SUCCESS';
					$result_reason = "list_info LIST INFORMATION SENT";
					$data = "$list_id";
					echo $output;
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	}
################################################################################
### END list_info
################################################################################





################################################################################
### list_custom_fields - shows the custom fields that are in a list, or all lists
################################################################################
if ($function == 'list_custom_fields')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		if ($custom_fields_enabled < 1)
			{
			$result = 'ERROR';
			$result_reason = "CUSTOM LIST FIELDS ARE NOT ENABLED ON THIS SYSTEM";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_lists='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "list_custom_fields USER DOES NOT HAVE PERMISSION TO MODIFY LISTS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($list_id)<2) or (strlen($list_id)>14) )
				{
				$result = 'ERROR';
				$result_reason = "list_custom_fields YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$list_id|$list_name|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				if ($api_list_restrict > 0)
					{
					if ( (!preg_match("/ $list_id /",$allowed_lists)) and ($list_id != '---ALL---') )
						{
						$result = 'ERROR';
						$result_reason = "list_custom_fields NOT AN ALLOWED LIST ID";
						$data = "$list_id";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}

				if ($list_id == '---ALL---')
					{$list_exists=1;}
				else
					{
					$stmt="SELECT count(*) from vicidial_lists where list_id='$list_id' $LOGallowed_campaignsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$list_exists=$row[0];
					}
				if ($list_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "list_custom_fields LIST DOES NOT EXIST";
					$data = "$list_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$listSQL = "list_id='$list_id'";
					if ($list_id == '---ALL---')
						{
						if (strlen($allowed_listsSQL) > 3)
							{$listSQL = "list_id IN($allowed_listsSQL)";}
						else
							{$listSQL = "list_id > 0";}
						}

					$OUTlist_name=array();
					$OUTcampaign_id=array();
					$OUTactive=array();
					$OUTlist_changedate=array();
					$OUTlist_lastcalldate=array();
					$OUTexpiration_date=array();
					$OUTresets_today=array();
					$OUTlist_id=array();

					$stmt="SELECT list_name,campaign_id,active,list_changedate,list_lastcalldate,expiration_date,resets_today,list_id from vicidial_lists where $listSQL $LOGallowed_campaignsSQL order by list_id;";
					if ($DB>0) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$lists_to_print = mysqli_num_rows($rslt);
					$lp=0;
					while ($lp < $lists_to_print)
						{
						$row=mysqli_fetch_row($rslt);
						$OUTlist_name[$lp] =			$row[0];
						$OUTcampaign_id[$lp] =			$row[1];
						$OUTactive[$lp] =				$row[2];
						$OUTlist_changedate[$lp] =		$row[3];
						$OUTlist_lastcalldate[$lp] =	$row[4];
						$OUTexpiration_date[$lp] =		$row[5];
						$OUTresets_today[$lp] =			$row[6];
						$OUTlist_id[$lp] =				$row[7];
						$lp++;
						}

					$output='';
					$DLset=0;
					if ($stage == 'csv')
						{$DL = ',';   $DLset++;}
					if ($stage == 'tab')
						{$DL = "\t";   $DLset++;}
					if ($stage == 'pipe')
						{$DL = '|';   $DLset++;}
					if ($DLset < 1)
						{$DL='|';}
					if ($header == 'YES')
						{$output .= 'list_id' . $DL . 'list_name' . $DL . 'campaign_id' . $DL . 'active' . $DL . 'list_changedate' . $DL . 'list_lastcalldate' . $DL . 'custom_fields_list_space_delimited' . "\n";}

					$lp=0;
					while ($lp < $lists_to_print)
						{
						$stmt="SHOW TABLES LIKE \"custom_$OUTlist_id[$lp]\";";
						if ($DB>0) {echo "$stmt";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$tablecount_to_print = mysqli_num_rows($rslt);
						$cf=0;   $af=0;
						$CFtable_order='';
						if ($tablecount_to_print > 0)
							{
							$stmtA = "describe custom_$OUTlist_id[$lp];";
							$rslt=mysql_to_mysqli($stmtA, $link);
							if ($DB) {echo "$stmtA\n";}
							$columns_ct = mysqli_num_rows($rslt);
							$column=array();
							while ($columns_ct > $cf)
								{
								$row=mysqli_fetch_row($rslt);
								$column[$cf] =	$row[0];
								if ( ($row[0] != 'lead_id') and (strlen($row[0]) > 0) )
									{
									$field_list[$af] =	$row[0];
									if ($af > 0)
										{$CFtable_order .= " $field_list[$af]";}
									else
										{$CFtable_order .= "$field_list[$af]";}
									$af++;
									}
								$cf++;
								}
							}
						$leads_fields_output = $CFtable_order;
						if ($custom_order == 'alpha_up')
							{
							sort($field_list);
							$leads_fields_output = implode(" ",$field_list);
							}
						if ($custom_order == 'alpha_down')
							{
							rsort($field_list);
							$leads_fields_output = implode(" ",$field_list);
							}

						$output .= "$OUTlist_id[$lp]" . $DL . "$OUTlist_name[$lp]" . $DL . "$OUTcampaign_id[$lp]" . $DL . "$OUTactive[$lp]" . $DL . "$OUTlist_changedate[$lp]" . $DL . "$OUTlist_lastcalldate[$lp]" . $DL . $leads_fields_output . "\n";

						$lp++;
						}

					$result = 'SUCCESS';
					$result_reason = "list_custom_fields LIST CUSTOM FIELDS INFORMATION SENT";
					$data = "$list_id";
					echo $output;
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	}
################################################################################
### END list_custom_fields
################################################################################





################################################################################
### add_list - adds list to the vicidial_lists table
################################################################################
if ($function == 'add_list')
	{
	$list_id = preg_replace('/[^0-9]/','',$list_id);
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_lists='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_list USER DOES NOT HAVE PERMISSION TO ADD LISTS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($list_id)<2) or (strlen($list_id)>14) or (strlen($campaign_id)<1) or (strlen($campaign_id)>8) or (strlen($list_name)<2) or (strlen($list_name)>30) )
				{
				$result = 'ERROR';
				$result_reason = "add_list YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$list_id|$list_name|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' $LOGallowed_campaignsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$campaign_exists=$row[0];
				if ($campaign_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "add_list CAMPAIGN DOES NOT EXIST";
					$data = "$campaign_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT count(*) from vicidial_lists where list_id='$list_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$list_exists=$row[0];
					if ($list_exists > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_list LIST ALREADY EXISTS";
						$data = "$list_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					else
						{
						$stmt="SELECT count(*) from vicidial_lists where list_id='$list_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$list_exists=$row[0];
						if ($list_exists > 0)
							{
							$result = 'ERROR';
							$result_reason = "add_list LIST ALREADY EXISTS";
							$data = "$list_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							if (strlen($script) > 0)
								{
								$stmt="SELECT count(*) from vicidial_scripts where script_id='$script';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$script_exists=$row[0];
								if ($script_exists < 1)
									{
									$result = 'ERROR';
									$result_reason = "add_list SCRIPT DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
									$data = "$script";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							if (strlen($drop_inbound_group) > 0)
								{
								$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$drop_inbound_group';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$script_exists=$row[0];
								if ($script_exists < 1)
									{
									$result = 'ERROR';
									$result_reason = "add_list IN-GROUP DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
									$data = "$drop_inbound_group";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							if ( (strlen($reset_time) > 0) and (strlen($reset_time) < 4) )
								{
								$result = 'ERROR';
								$result_reason = "add_list RESET TIME IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$reset_time";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							$copy_custom_fields_trigger=0;
							if ( (strlen($custom_fields_copy) > 1) and (strlen($custom_fields_copy) < 15) )
								{
								$stmt="SELECT count(*) from vicidial_lists where list_id='$custom_fields_copy';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$custom_fields_copy_exists=$row[0];
								if ($DB>0) {echo "$custom_fields_copy_exists|$stmt|\n";}
								if ($custom_fields_copy_exists < 1)
									{
									$result = 'ERROR';
									$result_reason = "add_list CUSTOM FIELDS LIST ID TO COPY FROM DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
									$data = "$list_id|$custom_fields_copy|$custom_fields_copy_exists";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{
									$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$custom_fields_copy';";
									$rslt=mysql_to_mysqli($stmt, $link);
									$row=mysqli_fetch_row($rslt);
									$vicidial_lists_fields_exists=$row[0];
									if ($DB>0) {echo "$vicidial_lists_fields_exists|$stmt|\n";}
									if ($vicidial_lists_fields_exists < 1)
										{
										$result = 'ERROR';
										$result_reason = "add_list CUSTOM FIELDS LIST ID TO COPY FROM HAS NO CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
										$data = "$list_id|$custom_fields_copy|$vicidial_lists_fields_exists";
										echo "$result: $result_reason: |$user|$data\n";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										exit;
										}
									else
										{
										$stmt="SELECT count(*) from vicidial_users where user='$user' and custom_fields_modify='1';";
										$rslt=mysql_to_mysqli($stmt, $link);
										$row=mysqli_fetch_row($rslt);
										$custom_fields_modify_exists=$row[0];
										if ($DB>0) {echo "$custom_fields_modify_exists|$stmt|\n";}
										if ($custom_fields_modify_exists < 1)
											{
											$result = 'ERROR';
											$result_reason = "add_list USER DOES NOT HAVE PERMISSION TO MODIFY CUSTOM FIELDS, THIS IS AN OPTIONAL FIELD";
											$data = "$list_id|$custom_fields_copy|$custom_fields_modify_exists";
											echo "$result: $result_reason: |$user|$data\n";
											api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
											exit;
											}
										else
											{
											if ($DB>0) {echo "Copy custom fields triggered|$copy_custom_fields_trigger|\n";}
											$copy_custom_fields_trigger++;
											}
										}
									}
								}

							$webformSQL='';
							$webformtwoSQL='';
							$webformthreeSQL='';
							$list_descriptionSQL='';
							$tz_methodSQL='';
							$local_call_timeSQL='';
							$xferconf_oneSQL='';
							$xferconf_twoSQL='';
							$xferconf_threeSQL='';
							$xferconf_fourSQL='';
							$xferconf_fiveSQL='';
							if (strlen($web_form_address) > 0)
								{
								if (preg_match("/%3A%2F%2F/",$web_form_address))
									{
									$web_form_address = urldecode($web_form_address);
									$web_form_address = preg_replace("/ /",'+',$web_form_address);
									}
								if ($web_form_address == '--BLANK--')
									{$webformSQL = " ,web_form_address=''";}
								else
									{$webformSQL = " ,web_form_address='$web_form_address'";}
								}
							if (strlen($web_form_address_two) > 0)
								{
								if (preg_match("/%3A%2F%2F/",$web_form_address_two))
									{
									$web_form_address_two = urldecode($web_form_address_two);
									$web_form_address_two = preg_replace("/ /",'+',$web_form_address_two);
									}
								if ($web_form_address_two == '--BLANK--')
									{$webformtwoSQL = " ,web_form_address_two=''";}
								else
									{$webformtwoSQL = " ,web_form_address_two='$web_form_address_two'";}
								}
							if (strlen($web_form_address_three) > 0)
								{
								if (preg_match("/%3A%2F%2F/",$web_form_address_three))
									{
									$web_form_address_three = urldecode($web_form_address_three);
									$web_form_address_three = preg_replace("/ /",'+',$web_form_address_three);
									}
								if ($web_form_address_three == '--BLANK--')
									{$webformthreeSQL = " ,web_form_address_three=''";}
								else
									{$webformthreeSQL = " ,web_form_address_three='$web_form_address_three'";}
								}
							if (strlen($active)<1) {$active='N';}
							if (strlen($expiration_date)<10) {$expiration_date='2099-12-31';}
							if (strlen($list_description) > 0)
								{
								if ($list_description == '--BLANK--')
									{$list_descriptionSQL = " ,list_description=''";}
								else
									{$list_descriptionSQL = " ,list_description='$list_description'";}
								}
							if (strlen($tz_method) > 0)
								{
								if (preg_match("/^COUNTRY_AND_AREA_CODE$|^POSTAL_CODE$|^NANPA_PREFIX$|^OWNER_TIME_ZONE_CODE$/",$tz_method))
									{$tz_methodSQL = " ,time_zone_setting='$tz_method'";}
								else
									{
									$result = 'ERROR';
									$result_reason = "add_list TIME ZONE METHOD IS NOT VALID, THIS IS AN OPTIONAL FIELD";
									$data = "$tz_method";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							if ( (strlen($local_call_time) > 0) and (!preg_match("/^campaign$/",$local_call_time)) )
								{
								$stmt="SELECT count(*) from vicidial_call_times where call_time_id='$local_call_time';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$call_time_exists=$row[0];
								if ($call_time_exists < 1)
									{
									$result = 'ERROR';
									$result_reason = "add_list LOCAL CALL TIME DOES NOT EXIST, THIS IS AN OPTIONAL FIELD";
									$data = "$local_call_time";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$local_call_timeSQL = " ,local_call_time='$local_call_time'";}
								}
							if (strlen($xferconf_one) > 0)
								{
								if (strlen($xferconf_one) > 50)
									{
									$result = 'ERROR';
									$result_reason = "add_list TRANSFER CONF OVERRIDE ONE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
									$data = "$xferconf_one";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$xferconf_oneSQL = " ,xferconf_a_number='$xferconf_one'";}
								}
							if (strlen($xferconf_two) > 0)
								{
								if (strlen($xferconf_two) > 50)
									{
									$result = 'ERROR';
									$result_reason = "add_list TRANSFER CONF OVERRIDE TWO IS NOT VALID, THIS IS AN OPTIONAL FIELD";
									$data = "$xferconf_two";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$xferconf_twoSQL = " ,xferconf_b_number='$xferconf_two'";}
								}
							if (strlen($xferconf_three) > 0)
								{
								if (strlen($xferconf_three) > 50)
									{
									$result = 'ERROR';
									$result_reason = "add_list TRANSFER CONF OVERRIDE THREE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
									$data = "$xferconf_three";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$xferconf_threeSQL = " ,xferconf_c_number='$xferconf_three'";}
								}
							if (strlen($xferconf_four) > 0)
								{
								if (strlen($xferconf_four) > 50)
									{
									$result = 'ERROR';
									$result_reason = "add_list TRANSFER CONF OVERRIDE FOUR IS NOT VALID, THIS IS AN OPTIONAL FIELD";
									$data = "$xferconf_four";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$xferconf_fourSQL = " ,xferconf_d_number='$xferconf_four'";}
								}
							if (strlen($xferconf_five) > 0)
								{
								if (strlen($xferconf_five) > 50)
									{
									$result = 'ERROR';
									$result_reason = "add_list TRANSFER CONF OVERRIDE FIVE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
									$data = "$xferconf_five";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$xferconf_fiveSQL = " ,xferconf_e_number='$xferconf_five'";}
								}

							$stmt="INSERT INTO vicidial_lists SET list_id='$list_id', list_name='$list_name', campaign_id='$campaign_id', active='$active', campaign_cid_override='$outbound_cid', agent_script_override='$script', am_message_exten_override='$am_message', drop_inbound_group_override='$drop_inbound_group', reset_time='$reset_time', expiration_date='$expiration_date' $webformSQL $webformtwoSQL $webformthreeSQL $list_descriptionSQL $tz_methodSQL $local_call_timeSQL $xferconf_oneSQL$xferconf_twoSQL$xferconf_threeSQL$xferconf_fourSQL$xferconf_fiveSQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "|$stmt|\n";}

							if ( ($copy_custom_fields_trigger > 0) and (strlen($custom_fields_copy) > 1) )
								{
								### BEGIN copy custom fields ###
								$admin_lists_custom = 'admin_lists_custom.php';
								if (!preg_match("/^APPEND$|^UPDATE$|^REPLACE$/",$custom_copy_method))
									{$custom_copy_method = 'APPEND';}
								$temp_webserver = (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]";
								if ($use_internal_webserver == 'Y') {$temp_webserver = "://$SSsounds_web_server";}

								$url = "http$temp_webserver/$SSadmin_web_directory/" . $admin_lists_custom . "?copy_option=" . $custom_copy_method . "&action=COPY_FIELDS_SUBMIT&list_id=$list_id&source_list_id=$custom_fields_copy";

								if ($DB>0) {echo "Copy custom fields url|$url|\n";}
								# use cURL to call the copy custom fields code
								$curl = curl_init();

								# Set some options - we are passing in a useragent too here
								curl_setopt_array($curl, array(
									CURLOPT_RETURNTRANSFER => 1,
									CURLOPT_URL => $url,
									CURLOPT_USERPWD => "$user:$pass",
									CURLOPT_USERAGENT => 'non_agent_api.php'
								));

								# Send the request & save response to $resp
								$resp = curl_exec($curl);
								$temp_response = 'NONE';
								if (preg_match('/ERROR:/',$resp)) {$temp_response = 'ERROR: Fields not copied';}
								if (preg_match('/SUCCESS:/',$resp)) {$temp_response = 'SUCCESS: Fields copied';}

								# Close request to clear up some resources
								curl_close($curl);
								### END copy custom fields ###
								}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='LISTS', event_type='ADD', record_id='$list_id', event_code='ADMIN API ADD LIST', event_sql=\"$SQL_log\", event_notes='list: $list_id|$campaign_id|$custom_fields_copy|$temp_response';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "add_list LIST HAS BEEN ADDED";
							$data = "$list_id|$list_name|$campaign_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END add_list
################################################################################






################################################################################
### update_campaign - updates campaign information in the vicidial_campaigns table and other functions
################################################################################
if ($function == 'update_campaign')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_campaigns='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_campaign USER DOES NOT HAVE PERMISSION TO UPDATE CAMPAIGNS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($campaign_id)<2) or (strlen($campaign_id)>8) )
				{
				$result = 'ERROR';
				$result_reason = "update_campaign YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$campaign_id|$campaign_name|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' $LOGallowed_campaignsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$camp_exists=$row[0];
				if ($camp_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "update_campaign CAMPAIGN DOES NOT EXIST";
					$data = "$campaign_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$dialmethodSQL='';
					$adaptivemaximumlevelSQL='';
					$campaignvdadextenSQL='';
					$hopperlevelSQL='';
					$dialtimeoutSQL='';
					$campaignnameSQL='';
					$campaigncidSQL='';
					$campaignfilterSQL='';
					$activeSQL='';
					$autodiallevelSQL='';
					$xferconf_oneSQL='';
					$xferconf_twoSQL='';
					$xferconf_threeSQL='';
					$xferconf_fourSQL='';
					$xferconf_fiveSQL='';
					$dispo_call_urlSQL='';
					$list_orderSQL='';
					$list_order_randomizeSQL='';
					$list_order_secondarySQL='';
					$dial_statusesSQL='';
					$webform_oneSQL='';
					$webform_twoSQL='';
					$webform_threeSQL='';

					if (strlen($auto_dial_level) > 0)
						{
						if ( ($auto_dial_level > $SSauto_dial_limit) or ($auto_dial_level < 0) )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign AUTO DIAL LEVEL MUST BE FROM 0 TO $SSauto_dial_limit, THIS IS AN OPTIONAL FIELD";
							$data = "$auto_dial_level";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$autodiallevelSQL = " ,auto_dial_level='$auto_dial_level'";}
						}
					if (strlen($dial_method) > 0)
						{
						if (preg_match("/^MANUAL$|^RATIO$|^INBOUND_MAN$|^ADAPT_AVERAGE$|^ADAPT_HARD_LIMIT$|^ADAPT_TAPERED$/",$dial_method))
							{$dialmethodSQL = " ,dial_method='$dial_method'";}
						else
							{
							$result = 'ERROR';
							$result_reason = "update_campaign DIAL METHOD IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$dial_method";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					if (strlen($adaptive_maximum_level) > 0)
						{
						if ( (strlen($adaptive_maximum_level) > 5) or (strlen($adaptive_maximum_level) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign ADAPT MAXIMUM DIAL LEVEL MUST BE FROM 1 TO 5 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$adaptive_maximum_level";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$adaptivemaximumlevelSQL = " ,adaptive_maximum_level='$adaptive_maximum_level'";}
						}
					if (strlen($campaign_vdad_exten) > 0)
						{
						if ( (strlen($campaign_vdad_exten) > 20) or (strlen($campaign_vdad_exten) < 3) )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign ROUTING EXTENSION MUST BE FROM 3 TO 20 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$campaign_vdad_exten";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$campaignvdadextenSQL = " ,campaign_vdad_exten='$campaign_vdad_exten'";}
						}
					if (strlen($hopper_level) > 0)
						{
						if ( ($hopper_level > 5000) or ($hopper_level < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign HOPPER LEVEL MUST BE FROM 1 TO 2000, THIS IS AN OPTIONAL FIELD";
							$data = "$hopper_level";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$hopperlevelSQL = " ,hopper_level='$hopper_level'";}
						}
					if (strlen($dial_timeout) > 0)
						{
						if ( ($dial_timeout > 120) or ($dial_timeout < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign DIAL TIMEOUT MUST BE FROM 1 TO 120, THIS IS AN OPTIONAL FIELD";
							$data = "$dial_timeout";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$dialtimeoutSQL = " ,dial_timeout='$dial_timeout'";}
						}
					if (strlen($campaign_name) > 0)
						{
						if ( (strlen($campaign_name) > 40) or (strlen($campaign_name) < 6) )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign CAMPAIGN NAME MUST BE FROM 6 TO 40 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$campaign_name";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$campaignnameSQL = " ,campaign_name='$campaign_name'";}
						}
					if (strlen($outbound_cid) > 0)
						{
						if ( (strlen($outbound_cid) > 20) or (strlen($outbound_cid) < 6) )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign CAMPAIGN CID MUST BE FROM 6 TO 20 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$outbound_cid";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$campaigncidSQL = " ,campaign_cid='$outbound_cid'";}
						}
					if (strlen($lead_filter_id) > 0)
						{
						if ($lead_filter_id == '---NONE---')
							{$lead_filter_id='';}
						else
							{
							$stmt="SELECT count(*) from vicidial_lead_filters where lead_filter_id='$lead_filter_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$filter_exists=$row[0];

							if ($filter_exists < 1)
								{
								$result = 'ERROR';
								$result_reason = "update_campaign CAMPAIGN LEAD FILTER MUST BE A VALID FILTER IN THE SYSTEM, THIS IS AN OPTIONAL FIELD";
								$data = "$lead_filter_id";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						$campaignfilterSQL = " ,lead_filter_id='$lead_filter_id'";
						}
					if (strlen($active) > 0)
						{
						if ( ($active != 'Y') and ($active != 'N') )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign ACTIVE MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
							$data = "$active";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$activeSQL = " ,active='$active'";}
						}
					if (strlen($xferconf_one) > 0)
						{
						if ($xferconf_one == '--BLANK--')
							{$xferconf_oneSQL = " ,xferconf_a_number=''";}
						else
							{
							if (strlen($xferconf_one) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_campaign TRANSFER CONF NUMBER ONE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_one";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_oneSQL = " ,xferconf_a_number='$xferconf_one'";}
							}
						}
					if (strlen($xferconf_two) > 0)
						{
						if ($xferconf_two == '--BLANK--')
							{$xferconf_twoSQL = " ,xferconf_b_number=''";}
						else
							{
							if (strlen($xferconf_two) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_campaign TRANSFER CONF NUMBER TWO IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_two";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_twoSQL = " ,xferconf_b_number='$xferconf_two'";}
							}
						}
					if (strlen($xferconf_three) > 0)
						{
						if ($xferconf_three == '--BLANK--')
							{$xferconf_threeSQL = " ,xferconf_c_number=''";}
						else
							{
							if (strlen($xferconf_three) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_campaign TRANSFER CONF NUMBER THREE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_three";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_threeSQL = " ,xferconf_c_number='$xferconf_three'";}
							}
						}
					if (strlen($xferconf_four) > 0)
						{
						if ($xferconf_four == '--BLANK--')
							{$xferconf_fourSQL = " ,xferconf_d_number=''";}
						else
							{
							if (strlen($xferconf_four) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_campaign TRANSFER CONF NUMBER FOUR IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_four";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_fourSQL = " ,xferconf_d_number='$xferconf_four'";}
							}
						}
					if (strlen($xferconf_five) > 0)
						{
						if ($xferconf_five == '--BLANK--')
							{$xferconf_fiveSQL = " ,xferconf_e_number=''";}
						else
							{
							if (strlen($xferconf_five) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_campaign TRANSFER CONF NUMBER FIVE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$xferconf_five";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$xferconf_fiveSQL = " ,xferconf_e_number='$xferconf_five'";}
							}
						}
					if (strlen($dispo_call_url) > 0)
						{
						if ($dispo_call_url == '--BLANK--')
							{$dispo_call_urlSQL = " ,dispo_call_url=''";}
						else
							{
							if ( (strlen($dispo_call_url) < 3) or (strlen($dispo_call_url) > 65000) )
								{
								$result = 'ERROR';
								$result_reason = "update_campaign DISPO CALL URL IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$dispo_call_url";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$dispo_call_urlSQL = " ,dispo_call_url='" . mysqli_real_escape_string($link, $dispo_call_url) . "'";}
							}
						}
					if (strlen($webform_one) > 0)
						{
						if ($webform_one == '--BLANK--')
							{$webform_oneSQL = " ,web_form_address=''";}
						else
							{
							if ( (strlen($webform_one) < 3) or (strlen($webform_one) > 65000) )
								{
								$result = 'ERROR';
								$result_reason = "update_campaign WENFORM 1 URL IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$webform_one";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$webform_oneSQL = " ,web_form_address='" . mysqli_real_escape_string($link, $webform_one) . "'";}
							}
						}
					if (strlen($webform_two) > 0)
						{
						if ($webform_two == '--BLANK--')
							{$webform_twoSQL = " ,web_form_address_two=''";}
						else
							{
							if ( (strlen($webform_two) < 3) or (strlen($webform_two) > 65000) )
								{
								$result = 'ERROR';
								$result_reason = "update_campaign WENFORM 2 URL IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$webform_two";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$webform_twoSQL = " ,web_form_address_two='" . mysqli_real_escape_string($link, $webform_two) . "'";}
							}
						}
					if (strlen($webform_three) > 0)
						{
						if ($webform_three == '--BLANK--')
							{$webform_threeSQL = " ,web_form_address_three=''";}
						else
							{
							if ( (strlen($webform_three) < 3) or (strlen($webform_three) > 65000) )
								{
								$result = 'ERROR';
								$result_reason = "update_campaign WENFORM 3 URL IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$webform_three";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$webform_threeSQL = " ,web_form_address_three='" . mysqli_real_escape_string($link, $webform_three) . "'";}
							}
						}
					if (strlen($list_order) > 0)
						{
						if ( ($camp_lead_order_random > 0) and (preg_match("/RANDOM/i",$list_order)) )
							{
							$result = 'ERROR';
							$result_reason = "update_campaign LIST ORDER INCLUDING RANDOM ARE NOT ALLOWED, THIS IS AN OPTIONAL FIELD";
							$data = "$list_order";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						if (!preg_match("/^DOWN$|^UP$|^DOWN PHONE$|^UP PHONE$|^DOWN LAST NAME$|^UP LAST NAME$|^DOWN COUNT$|^UP COUNT$|^RANDOM$|^DOWN LAST CALL TIME$|^UP LAST CALL TIME$|^DOWN RANK$|^UP RANK$|^DOWN OWNER$|^UP OWNER$|^DOWN TIMEZONE$|^UP TIMEZONE$|^DOWN 2nd NEW$|^DOWN 3rd NEW$|^DOWN 4th NEW$|^DOWN 5th NEW$|^DOWN 6th NEW$|^UP 2nd NEW$|^UP 3rd NEW$|^UP 4th NEW$|^UP 5th NEW$|^UP 6th NEW$|^DOWN PHONE 2nd NEW$|^DOWN PHONE 3rd NEW$|^DOWN PHONE 4th NEW$|^DOWN PHONE 5th NEW$|^DOWN PHONE 6th NEW$|^UP PHONE 2nd NEW$|^UP PHONE 3rd NEW$|^UP PHONE 4th NEW$|^UP PHONE 5th NEW$|^UP PHONE 6th NEW$|^DOWN LAST NAME 2nd NEW$|^DOWN LAST NAME 3rd NEW$|^DOWN LAST NAME 4th NEW$|^DOWN LAST NAME 5th NEW$|^DOWN LAST NAME 6th NEW$|^UP LAST NAME 2nd NEW$|^UP LAST NAME 3rd NEW$|^UP LAST NAME 4th NEW$|^UP LAST NAME 5th NEW$|^UP LAST NAME 6th NEW$|^DOWN COUNT 2nd NEW$|^DOWN COUNT 3rd NEW$|^DOWN COUNT 4th NEW$|^DOWN COUNT 5th NEW$|^DOWN COUNT 6th NEW$|^UP COUNT 2nd NEW$|^UP COUNT 3rd NEW$|^UP COUNT 4th NEW$|^UP COUNT 5th NEW$|^UP COUNT 6th NEW$|^RANDOM 2nd NEW$|^RANDOM 3rd NEW$|^RANDOM 4th NEW$|^RANDOM 5th NEW$|^RANDOM 6th NEW$|^DOWN LAST CALL TIME 2nd NEW$|^DOWN LAST CALL TIME 3rd NEW$|^DOWN LAST CALL TIME 4th NEW$|^DOWN LAST CALL TIME 5th NEW$|^DOWN LAST CALL TIME 6th NEW$|^UP LAST CALL TIME 2nd NEW$|^UP LAST CALL TIME 3rd NEW$|^UP LAST CALL TIME 4th NEW$|^UP LAST CALL TIME 5th NEW$|^UP LAST CALL TIME 6th NEW$|^DOWN RANK 2nd NEW$|^DOWN RANK 3rd NEW$|^DOWN RANK 4th NEW$|^DOWN RANK 5th NEW$|^DOWN RANK 6th NEW$|^UP RANK 2nd NEW$|^UP RANK 3rd NEW$|^UP RANK 4th NEW$|^UP RANK 5th NEW$|^UP RANK 6th NEW$|^DOWN OWNER 2nd NEW$|^DOWN OWNER 3rd NEW$|^DOWN OWNER 4th NEW$|^DOWN OWNER 5th NEW$|^DOWN OWNER 6th NEW$|^UP OWNER 2nd NEW$|^UP OWNER 3rd NEW$|^UP OWNER 4th NEW$|^UP OWNER 5th NEW$|^UP OWNER 6th NEW$|^DOWN TIMEZONE 2nd NEW$|^DOWN TIMEZONE 3rd NEW$|^DOWN TIMEZONE 4th NEW$|^DOWN TIMEZONE 5th NEW$|^DOWN TIMEZONE 6th NEW$|^UP TIMEZONE 2nd NEW$|^UP TIMEZONE 3rd NEW$|^UP TIMEZONE 4th NEW$|^UP TIMEZONE 5th NEW$|^UP TIMEZONE 6th NEW$/",$list_order))
							{
							$result = 'ERROR';
							$result_reason = "update_campaign LIST ORDER IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$list_order";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							if ( (strlen($list_order) < 2) or (strlen($list_order) > 30) )
								{
								$result = 'ERROR';
								$result_reason = "update_campaign LIST ORDER IS NOT A VALID LENGTH, THIS IS AN OPTIONAL FIELD";
								$data = "$list_order";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$list_orderSQL = " ,lead_order='$list_order'";}
							}
						}
					if (strlen($list_order_randomize) > 0)
						{
						if (!preg_match("/^Y$|^N$/",$list_order_randomize))
							{
							$result = 'ERROR';
							$result_reason = "update_campaign LIST ORDER RANDOMIZE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$list_order_randomize";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							if ( (strlen($list_order_randomize) < 1) or (strlen($list_order_randomize) > 1) )
								{
								$result = 'ERROR';
								$result_reason = "update_campaign LIST ORDER RANDOMIZE IS NOT A VALID LENGTH, THIS IS AN OPTIONAL FIELD";
								$data = "$list_order";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$list_order_randomizeSQL = " ,lead_order_randomize='$list_order_randomize'";}
							}
						}
					if (strlen($list_order_secondary) > 0)
						{
						if (!preg_match("/^LEAD_ASCEND$|^LEAD_DESCEND$|^CALLTIME_ASCEND$|^CALLTIME_DESCEND$|^VENDOR_ASCEND$|^VENDOR_DESCEND$/",$list_order_secondary))
							{
							$result = 'ERROR';
							$result_reason = "update_campaign LIST ORDER SECONDARY IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$list_order_secondary";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							if ( (strlen($list_order_secondary) < 10) or (strlen($list_order_secondary) > 20) )
								{
								$result = 'ERROR';
								$result_reason = "update_campaign LIST ORDER SECONDARY IS NOT A VALID LENGTH, THIS IS AN OPTIONAL FIELD";
								$data = "$list_order";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$list_order_secondarySQL = " ,lead_order_secondary='$list_order_secondary'";}
							}
						}

					if (strlen($dial_status_add) > 0)
						{
						if (strlen($dial_status_add) > 6)
							{
							$result = 'ERROR';
							$result_reason = "update_campaign DIAL STATUS ADD IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$dial_status_add";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							$stmt="SELECT dial_statuses from vicidial_campaigns where campaign_id='$campaign_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$temp_dial_statuses = $row[0];
							if (strlen($temp_dial_statuses) > 248)
								{
								$result = 'ERROR';
								$result_reason = "update_campaign DIAL STATUS ADD FAILURE, TOO MANY EXISTING DIAL STATUSES, THIS IS AN OPTIONAL FIELD";
								$data = "$dial_status_add";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							if (strlen($temp_dial_statuses)<2) {$temp_dial_statuses = ' -';}
							$dial_statusesSQL = " $dial_status_add$temp_dial_statuses";
							}
						}

					if (strlen($dial_status_remove) > 0)
						{
						if (strlen($dial_status_remove) > 6)
							{
							$result = 'ERROR';
							$result_reason = "update_campaign DIAL STATUS REMOVE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$dial_status_remove";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							if (strlen($dial_statusesSQL) > 0)
								{$temp_dial_statuses = $dial_statusesSQL;}
							else
								{
								$stmt="SELECT dial_statuses from vicidial_campaigns where campaign_id='$campaign_id';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$temp_dial_statuses = $row[0];
								if ( (strlen($temp_dial_statuses) < 1) or ($temp_dial_statuses == ' -') )
									{
									$result = 'ERROR';
									$result_reason = "update_campaign DIAL STATUS REMOVE FAILURE, NO EXISTING DIAL STATUSES SET, THIS IS AN OPTIONAL FIELD";
									$data = "$dial_status_remove";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}
							if (!preg_match("/\s$dial_status_remove\s/i",$temp_dial_statuses))
								{
								$result = 'ERROR';
								$result_reason = "update_campaign DIAL STATUS REMOVE FAILURE, NOT AN EXISTING DIAL STATUS, THIS IS AN OPTIONAL FIELD";
								$data = "$dial_status_remove";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							$dial_statusesSQL = preg_replace("/\s$dial_status_remove\s/i", " ",$temp_dial_statuses);
							}
						}
					if (strlen($dial_statusesSQL) > 0)
						{$dial_statusesSQL = ",dial_statuses='$dial_statusesSQL'";}


					$updateSQL = "$campaignnameSQL$activeSQL$dialtimeoutSQL$hopperlevelSQL$campaignvdadextenSQL$adaptivemaximumlevelSQL$dialmethodSQL$autodiallevelSQL$campaigncidSQL$campaignfilterSQL$xferconf_oneSQL$xferconf_twoSQL$xferconf_threeSQL$xferconf_fourSQL$xferconf_fiveSQL$list_orderSQL$list_order_randomizeSQL$list_order_secondarySQL$dial_statusesSQL$webform_oneSQL$webform_twoSQL$webform_threeSQL$dispo_call_urlSQL";

					if (strlen($updateSQL)< 3)
						{
						$result = 'NOTICE';
						$result_reason = "update_campaign NO UPDATES DEFINED";
						$data = "$updateSQL";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$stmt="UPDATE vicidial_campaigns SET campaign_changedate='$NOW_TIME' $updateSQL WHERE campaign_id='$campaign_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "|$stmt|\n";}

						### LOG INSERTION Admin Log Table ###
						$SQL_log = "$stmt|";
						$SQL_log = preg_replace('/;/', '', $SQL_log);
						$SQL_log = addslashes($SQL_log);
						$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='CAMPAIGNS', event_type='MODIFY', record_id='$campaign_id', event_code='ADMIN API UPDATE CAMPAIGN', event_sql=\"$SQL_log\", event_notes='campaign: $campaign_id';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);

						$result = 'SUCCESS';
						$result_reason = "update_campaign CAMPAIGN HAS BEEN UPDATED";
						$data = "$campaign_id";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}

					if ($reset_hopper == 'Y')
						{
						$stmt="DELETE from vicidial_hopper where campaign_id='$campaign_id' and campaign_id='$campaign_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "|$stmt|\n";}

						### LOG INSERTION Admin Log Table ###
						$SQL_log = "$stmt|";
						$SQL_log = preg_replace('/;/', '', $SQL_log);
						$SQL_log = addslashes($SQL_log);
						$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='CAMPAIGNS', event_type='RESET', record_id='$campaign_id', event_code='ADMIN API RESET HOPPER', event_sql=\"$SQL_log\", event_notes='campaign: $campaign_id';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);

						$result = 'NOTICE';
						$result_reason = "update_campaign HOPPER HAS BEEN RESET";
						$data = "$campaign_id|$affected_rows";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END update_campaign
################################################################################





################################################################################
### update_alt_url - updates/adds/displays alternate dispo call url entries for a campaign
################################################################################
if ($function == 'update_alt_url')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_campaigns='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_alt_url USER DOES NOT HAVE PERMISSION TO UPDATE CAMPAIGNS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($campaign_id)<2) or (strlen($campaign_id)>20) )
				{
				$result = 'ERROR';
				$result_reason = "update_alt_url YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$campaign_id|$campaign_name|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' $LOGallowed_campaignsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$camp_exists=$row[0];
				if ($camp_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "update_alt_url CAMPAIGN DOES NOT EXIST";
					$data = "$campaign_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					if ($alt_url_id == 'LIST')
						{
						$output='';
						$DLset=0;
						if ($stage == 'csv')
							{$DL = ',';   $DLset++;}
						if ($stage == 'tab')
							{$DL = "\t";   $DLset++;}
						if ($stage == 'pipe')
							{$DL = '|';   $DLset++;}
						if ($DLset < 1)
							{$DL='|';}
						if ($header == 'YES')
							{$output .= 'url_id' . $DL . 'campaign_id' . $DL . 'entry_type' . $DL . 'active' . $DL . 'url_type' . $DL . 'url_rank' . $DL . 'url_statuses' . $DL . 'url_description' . $DL . 'url_lists' . $DL . 'url_call_length' . $DL . "url_address\n";}

						$stmt="SELECT url_id,campaign_id,entry_type,active,url_type,url_rank,url_statuses,url_description,url_lists,url_call_length,url_address from vicidial_url_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and url_type='$url_type' order by url_id limit 1000;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$vum_recs = mysqli_num_rows($rslt);
						if ($DB>0) {echo "$vum_recs|$stmt|\n";}
						$M=0;
						while ($vum_recs > $M)
							{
							$row=mysqli_fetch_row($rslt);
							$url_id =			$row[0];
							$campaign_id =		$row[1];
							$entry_type =		$row[2];
							$active =			$row[3];
							$url_type =			$row[4];
							$url_rank =			$row[5];
							$url_statuses =		$row[6];
							$url_description =	$row[7];
							$url_lists =		$row[8];
							$url_call_length =	$row[9];
							$url_address =		$row[10];

							$output .= "$url_id" . $DL . "$campaign_id" . $DL . "$entry_type" . $DL . "$active" . $DL . "$url_type" . $DL . "$url_rank" . $DL . "$url_statuses" . $DL . "$url_description" . $DL . "$url_lists" . $DL . "$url_call_length" . $DL . "$url_address\n";

							$M++;
							}
						if ($M < 1)
							{echo "NOTICE: update_alt_url LIST, No Records Found - $user|$url_type|$entry_type|$campaign_id|0\n";}
						else
							{echo $output;}

						$result = 'SUCCESS';
						$result_reason = "update_alt_url ALT URL LIST DISPLAYED";
						$data = "$url_type|$entry_type|$campaign_id|$M";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

						exit;
						}
					if ($entry_type == 'campaign')
						{
						$event_section = 'CAMPAIGNS';
						if ($url_type == 'dispo')
							{$validate_stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and dispo_call_url='ALT';";}
						elseif ($url_type == 'start')
							{$validate_stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and start_call_url='ALT';";}
						elseif ($url_type == 'noagent')
							{$validate_stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and na_call_url='ALT';";}
						else
							{
							$result = 'ERROR';
							$result_reason = "update_alt_url NO VALID URL TYPE DEFINED:";
							$data = "$url_type|$entry_type|$campaign_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					elseif ($entry_type == 'ingroup')
						{
						$event_section = 'INGROUPS';
						if ($url_type == 'dispo')
							{$validate_stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and dispo_call_url='ALT';";}
						elseif ($url_type == 'start')
							{$validate_stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and start_call_url='ALT';";}
						elseif ($url_type == 'noagent')
							{$validate_stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and na_call_url='ALT';";}
						elseif ($url_type == 'addlead')
							{$validate_stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and add_lead_url='ALT';";}
						else
							{
							$result = 'ERROR';
							$result_reason = "update_alt_url NO VALID URL TYPE DEFINED:";
							$data = "$url_type|$entry_type|$campaign_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					elseif ($entry_type == 'list')
						{
						$event_section = 'LISTS';
						if ($url_type == 'noagent')
							{$validate_stmt="SELECT count(*) from vicidial_lists where list_id='$campaign_id' and na_call_url='ALT';";}
						else
							{
							$result = 'ERROR';
							$result_reason = "update_alt_url NO VALID URL TYPE DEFINED:";
							$data = "$url_type|$entry_type|$campaign_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "update_alt_url NO ENTRY TYPE DEFINED:";
						$data = "$entry_type|$campaign_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					
					# find if the campaign/in-group/list field type is set to use ALT URLs
					$rslt=mysql_to_mysqli($validate_stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$camp_exists=$row[0];
					if ($camp_exists < 1)
						{
						$result = 'ERROR';
						$result_reason = "update_alt_url $event_section $url_type URL IS NOT SET TO ALT";
						$data = "$url_type|$entry_type|$campaign_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}

					if ($alt_url_id != 'NEW')
						{
						$stmt="SELECT count(*) from vicidial_url_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and url_type='$url_type' and url_id='$alt_url_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$alt_id_exists=$row[0];
						if ($alt_id_exists < 1)
							{
							$stmt="SELECT url_id from vicidial_url_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and url_type='$url_type' order by url_id limit 2;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$vum_recs = mysqli_num_rows($rslt);
							if ($vum_recs < 2)
								{
								$row=mysqli_fetch_row($rslt);
								$alt_url_id =		$row[0];
								}
							else
								{
								$result = 'ERROR';
								$result_reason = "update_alt_url ALT URL ID DOES NOT EXIST";
								$data = "$alt_url_id|$url_type|$entry_type|$campaign_id|$vum_recs";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						}

					$activeSQL='';
					$url_addressSQL='';
					$url_rankSQL='';
					$url_statusesSQL='';
					$url_descriptionSQL='';
					$url_listsSQL='';
					$url_call_lengthSQL='';

					if (strlen($active) > 0)
						{
						if ( ($active != 'Y') and ($active != 'N') )
							{
							$result = 'ERROR';
							$result_reason = "update_alt_url ACTIVE MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
							$data = "$active";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$activeSQL = " ,active='$active'";}
						}
					if (strlen($url_address) > 0)
						{
						if ($url_address == '--BLANK--')
							{$url_addressSQL = " ,url_address=''";}
						else
							{
							if ( (strlen($url_address) < 5) or (strlen($url_address) > 65000) )
								{
								$result = 'ERROR';
								$result_reason = "update_alt_url URL ADDRESS IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$url_address";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$url_addressSQL = " ,url_address='" . mysqli_real_escape_string($link, $url_address) . "'";}
							}
						}
					if (strlen($url_rank) > 0)
						{
						if (strlen($url_rank) > 5)
							{
							$result = 'ERROR';
							$result_reason = "update_alt_url URL RANK IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$url_rank";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$url_rankSQL = " ,url_rank='$url_rank'";}
						}
					if (strlen($url_call_length) > 0)
						{
						if (strlen($url_call_length) > 5)
							{
							$result = 'ERROR';
							$result_reason = "update_alt_url URL CAL LENGTH IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$url_call_length";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$url_call_lengthSQL = " ,url_call_length='$url_call_length'";}
						}
					if (strlen($url_statuses) > 0)
						{
						if (strlen($url_statuses) > 1000)
							{
							$result = 'ERROR';
							$result_reason = "update_alt_url URL STATUSES IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$url_statuses";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$url_statusesSQL = " ,url_statuses='$url_statuses'";}
						}
					if (strlen($url_description) > 0)
						{
						if ($url_description == '--BLANK--')
							{$url_descriptionSQL = " ,url_description=''";}
						else
							{
							if (strlen($url_description) > 255)
								{
								$result = 'ERROR';
								$result_reason = "update_alt_url URL DESCRIPTION IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$url_description";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$url_descriptionSQL = " ,url_description='$url_description'";}
							}
						}
					if (strlen($url_lists) > 0)
						{
						if ($url_lists == '--BLANK--')
							{$url_listsSQL = " ,url_lists=''";}
						else
							{
							if (strlen($url_lists) > 1000)
								{
								$result = 'ERROR';
								$result_reason = "update_alt_url URL LISTS IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$url_lists";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$url_listsSQL = " ,url_lists='$url_lists'";}
							}
						}

					$updateSQL = "$activeSQL$url_rankSQL$url_statusesSQL$url_descriptionSQL$url_listsSQL$url_call_lengthSQL$url_addressSQL";

					if (strlen($updateSQL)< 3)
						{
						$result = 'NOTICE';
						$result_reason = "update_alt_url NO UPDATES DEFINED";
						$data = "$updateSQL";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						if ($alt_url_id == 'NEW')
							{
							$updateSQLx = ltrim($updateSQL, ' ,');
							$stmt="INSERT INTO vicidial_url_multi SET campaign_id='$campaign_id',entry_type='$entry_type',url_type='$url_type', $updateSQLx;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$add_count = mysqli_affected_rows($link);
							$new_url_id = mysqli_insert_id($link);
							if ($DB) {echo "$add_count|$new_url_id|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='$event_section', event_type='ADD', record_id='$campaign_id', event_code='ADMIN API UPDATE ALT URL', event_sql=\"$SQL_log\", event_notes='campaign: $campaign_id|url_id: $new_url_id|entry_type: $entry_type|url_type:$url_type';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_alt_url ALT URL HAS BEEN ADDED";
							$data = "NEW URL ID: $new_url_id|$url_type|$entry_type|$campaign_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$updateSQLx = ltrim($updateSQL, ' ,');
							$stmt="UPDATE vicidial_url_multi SET $updateSQLx WHERE campaign_id='$campaign_id' and url_id='$alt_url_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='$event_section', event_type='MODIFY', record_id='$campaign_id', event_code='ADMIN API UPDATE ALT URL', event_sql=\"$SQL_log\", event_notes='campaign: $campaign_id|url_id: $alt_url_id|entry_type: $entry_type|url_type:$url_type';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_alt_url ALT URL HAS BEEN UPDATED";
							$data = "$alt_url_id|$url_type|$entry_type|$campaign_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END update_alt_url
################################################################################





################################################################################
### update_presets - updates/adds/displays campaign preset entries
################################################################################
if ($function == 'update_presets')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_campaigns='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_presets USER DOES NOT HAVE PERMISSION TO UPDATE CAMPAIGNS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($campaign_id)<2) or (strlen($campaign_id)>8) )
				{
				$result = 'ERROR';
				$result_reason = "update_presets YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$campaign_id|$campaign_name|$campaign_id";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' $LOGallowed_campaignsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$camp_exists=$row[0];
				if ($camp_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "update_presets CAMPAIGN DOES NOT EXIST";
					$data = "$campaign_id";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					if ($action == 'LIST')
						{
						$output='';
						$DLset=0;
						if ($stage == 'csv')
							{$DL = ',';   $DLset++;}
						if ($stage == 'tab')
							{$DL = "\t";   $DLset++;}
						if ($stage == 'pipe')
							{$DL = '|';   $DLset++;}
						if ($DLset < 1)
							{$DL='|';}
						if ($header == 'YES')
							{$output .= 'preset_name' . $DL . 'campaign_id' . $DL . 'preset_number' . $DL . 'preset_hide_number' . $DL . "preset_dtmf\n";}

						$stmt="SELECT preset_name,campaign_id,preset_number,preset_hide_number,preset_dtmf from vicidial_xfer_presets where campaign_id='$campaign_id' order by preset_name limit 1000;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$vum_recs = mysqli_num_rows($rslt);
						if ($DB>0) {echo "$vum_recs|$stmt|\n";}
						$M=0;
						while ($vum_recs > $M)
							{
							$row=mysqli_fetch_row($rslt);
							$preset_name =			$row[0];
							$campaign_id =			$row[1];
							$preset_number =		$row[2];
							$preset_hide_number =	$row[3];
							$preset_dtmf =			$row[4];

							$output .= "$preset_name" . $DL . "$campaign_id" . $DL . "$preset_number" . $DL . "$preset_hide_number" . $DL . "$preset_dtmf\n";

							$M++;
							}
						if ($M < 1)
							{echo "NOTICE: update_presets LIST, No Records Found - $user|$campaign_id|0\n";}
						else
							{echo $output;}

						$result = 'SUCCESS';
						$result_reason = "update_presets PRESET LIST DISPLAYED";
						$data = "$campaign_id|$M";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

						exit;
						}

					if ($action != 'NEW')
						{
						$stmt="SELECT count(*) from vicidial_xfer_presets where campaign_id='$campaign_id' and preset_name='$preset_name';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$alt_id_exists=$row[0];
						if ($alt_id_exists < 1)
							{
							$stmt="SELECT preset_name from vicidial_xfer_presets where campaign_id='$campaign_id' order by preset_name limit 2;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$vum_recs = mysqli_num_rows($rslt);
							if ($vum_recs < 2)
								{
								$row=mysqli_fetch_row($rslt);
								$preset_name =		$row[0];
								}
							else
								{
								$result = 'ERROR';
								$result_reason = "update_presets PRESET NAME DOES NOT EXIST";
								$data = "$preset_name|$campaign_id|$vum_recs";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}
						}

					$preset_numberSQL='';
					$preset_dtmfSQL='';
					$preset_hide_numberSQL='';

					if (strlen($preset_hide_number) > 0)
						{
						if ( ($preset_hide_number != 'Y') and ($preset_hide_number != 'N') )
							{
							$result = 'ERROR';
							$result_reason = "update_presets PRESET HIDE NUMBER MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
							$data = "$preset_hide_number";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$preset_hide_numberSQL = " ,preset_hide_number='$preset_hide_number'";}
						}
					if (strlen($preset_number) > 0)
						{
						if ( (strlen($preset_number) > 50) or (strlen($preset_number) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_presets PRESET NUMBER IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$preset_number";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$preset_numberSQL = " ,preset_number='$preset_number'";}
						}
					if (strlen($preset_dtmf) > 0)
						{
						if ($preset_dtmf == '--BLANK--')
							{$preset_dtmfSQL = " ,preset_dtmf=''";}
						else
							{
							if (strlen($preset_dtmf) > 50)
								{
								$result = 'ERROR';
								$result_reason = "update_presets PRESET DTMF IS NOT VALID, THIS IS AN OPTIONAL FIELD";
								$data = "$preset_dtmf";
								echo "$result: $result_reason: |$user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{$preset_dtmfSQL = " ,preset_dtmf='$preset_dtmf'";}
							}
						}

					$updateSQL = "$preset_numberSQL$preset_dtmfSQL$preset_hide_numberSQL";

					if ( (strlen($updateSQL)< 3) or ($action == 'DELETE') )
						{
						if ($action == 'DELETE')
							{
							$stmt="DELETE FROM vicidial_xfer_presets WHERE campaign_id='$campaign_id' and preset_name='$preset_name';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$del_count = mysqli_affected_rows($link);
							if ($DB) {echo "$del_count|$preset_name|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='$event_section', event_type='DELETE', record_id='$campaign_id', event_code='ADMIN API DELETE PRESET', event_sql=\"$SQL_log\", event_notes='campaign: $campaign_id|preset_name: $preset_name';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_presets PRESET HAS BEEN DELETED";
							$data = "$preset_name|$campaign_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$result = 'NOTICE';
							$result_reason = "update_presets NO UPDATES DEFINED";
							$data = "$updateSQL";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					else
						{
						if ($action == 'NEW')
							{
							$updateSQLx = ltrim($updateSQL, ' ,');
							$stmt="INSERT INTO vicidial_xfer_presets SET campaign_id='$campaign_id',preset_name='$preset_name', $updateSQLx;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$add_count = mysqli_affected_rows($link);
							if ($DB) {echo "$add_count|$preset_name|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='$event_section', event_type='ADD', record_id='$campaign_id', event_code='ADMIN API UPDATE PRESET', event_sql=\"$SQL_log\", event_notes='campaign: $campaign_id|preset_name: $preset_name';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_presets PRESET HAS BEEN ADDED";
							$data = "NEW PRESET: $preset_name|$campaign_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$updateSQLx = ltrim($updateSQL, ' ,');
							$stmt="UPDATE vicidial_xfer_presets SET $updateSQLx WHERE campaign_id='$campaign_id' and preset_name='$preset_name';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='$event_section', event_type='MODIFY', record_id='$campaign_id', event_code='ADMIN API UPDATE PRESET', event_sql=\"$SQL_log\", event_notes='campaign: $campaign_id|preset_name: $preset_name';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_presets PRESET HAS BEEN UPDATED";
							$data = "$preset_name|$campaign_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END update_presets
################################################################################





################################################################################
### add_did - adds new Inbound DID entries to the system in the vicidial_inbound_dids table
################################################################################
if ($function == 'add_did')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_inbound_dids='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_did USER DOES NOT HAVE PERMISSION TO ADD DIDS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($did_pattern)<2) or (strlen($did_pattern)>50) )
				{
				$result = 'ERROR';
				$result_reason = "add_did YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$did_pattern";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$admin_viewable_groupsSQL='';
				$WHEREadmin_viewable_groupsSQL='';
				if  (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups))
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$admin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$WHEREadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_inbound_dids where did_pattern='$did_pattern';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$did_exists=$row[0];
				if ($did_exists > 0)
					{
					$result = 'ERROR';
					$result_reason = "add_did DID ALREADY EXISTS";
					$data = "$did_pattern";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$did_patternSQL = "did_pattern='$did_pattern'";
					$did_descriptionSQL='';
					$activeSQL='';
					$did_routeSQL='';
					$record_callSQL='';
					$extensionSQL='';
					$exten_contextSQL='';
					$voicemail_extSQL='';
					$phone_extensionSQL='';
					$server_ipSQL='';
					$groupSQL='';
					$menu_idSQL='';
					$filter_clean_cid_numberSQL='';
					$call_handle_methodSQL='';
					$agent_search_methodSQL='';
					$list_idSQL='';
					$entry_list_idSQL='';
					$campaign_idSQL='';
					$phone_codeSQL='';

					if (strlen($did_description) > 0)
						{
						if ( (strlen($did_description) > 50) or (strlen($did_description) < 6) )
							{
							$result = 'ERROR';
							$result_reason = "add_did DID DESCRIPTION MUST BE FROM 6 TO 50 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$did_description";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$did_descriptionSQL = " ,did_description='$did_description'";}
						}
					if (strlen($active) > 0)
						{
						if ( ($active != 'Y') and ($active != 'N') )
							{
							$result = 'ERROR';
							$result_reason = "add_did ACTIVE MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
							$data = "$active";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$activeSQL = " ,did_active='$active'";}
						}
					if (strlen($did_route) > 0)
						{
						if (preg_match("/^EXTEN$|^VOICEMAIL$|^AGENT$|^PHONE$|^IN_GROUP$|^CALLMENU$|^VMAIL_NO_INST$/",$did_route))
							{$did_routeSQL = " ,did_route='$did_route'";}
						else
							{
							$result = 'ERROR';
							$result_reason = "add_did DID ROUTE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$did_route";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					if (strlen($record_call) > 0)
						{
						if ( ($record_call != 'Y') and ($record_call != 'N') and ($record_call != 'Y_QUEUESTOP') )
							{
							$result = 'ERROR';
							$result_reason = "add_did RECORD CALL MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
							$data = "$record_call";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$record_callSQL = " ,record_call='$record_call'";}
						}
					if (strlen($extension) > 0)
						{
						if (strlen($extension) > 50)
							{
							$result = 'ERROR';
							$result_reason = "add_did EXTENSION MUST BE FROM 1 TO 50 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$extension";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$extensionSQL = " ,extension='$extension'";}
						}
					if (strlen($exten_context) > 0)
						{
						if (strlen($exten_context) > 50)
							{
							$result = 'ERROR';
							$result_reason = "add_did EXTENSION CONTEXT MUST BE FROM 1 TO 50 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$exten_context";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$exten_contextSQL = " ,exten_context='$exten_context'";}
						}
					if (strlen($voicemail_ext) > 0)
						{
						if ( (strlen($voicemail_ext) > 10) or (strlen($voicemail_ext) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "add_did VOICEMAIL EXTENSION MUST BE FROM 1 TO 10 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$voicemail_ext";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$voicemail_extSQL = " ,voicemail_ext='$voicemail_ext'";}
						}
					if (strlen($phone_extension) > 0)
						{
						if ( (strlen($phone_extension) > 100) or (strlen($phone_extension) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "add_did PHONE EXTENSION MUST BE FROM 1 TO 100 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$phone_extension";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$phone_extensionSQL = " ,phone='$phone_extension'";}
						}
					if (strlen($server_ip) > 0)
						{
						$stmt="SELECT count(*) from servers where server_ip='$server_ip';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$server_exists=$row[0];

						if ($server_exists < 1)
							{
							$result = 'ERROR';
							$result_reason = "add_did SERVER IP MUST BE A VALID SERVER IN THE SYSTEM, THIS IS AN OPTIONAL FIELD";
							$data = "$server_ip";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						$server_ipSQL = " ,server_ip='$server_ip'";
						}
					if (strlen($group) > 0)
						{
						$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$group';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$group_exists=$row[0];

						if ($group_exists < 1)
							{
							$result = 'ERROR';
							$result_reason = "add_did GROUP ID MUST BE A VALID INBOUND GROUP IN THE SYSTEM, THIS IS AN OPTIONAL FIELD";
							$data = "$group";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						$groupSQL = " ,group_id='$group'";
						}
					if (strlen($menu_id) > 0)
						{
						$stmt="SELECT count(*) from vicidial_call_menu where menu_id='$menu_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$menu_id_exists=$row[0];

						if ($menu_id_exists < 1)
							{
							$result = 'ERROR';
							$result_reason = "update_did MENU ID MUST BE A VALID CALL MENU IN THE SYSTEM, THIS IS AN OPTIONAL FIELD";
							$data = "$menu_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						$menu_idSQL = " ,menu_id='$menu_id'";
						}
					if (strlen($filter_clean_cid_number) > 0)
						{
						if ( (strlen($filter_clean_cid_number) > 20) or (strlen($filter_clean_cid_number) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "add_did CLEAN CID NUMBER MUST BE FROM 1 TO 20 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$filter_clean_cid_number";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$filter_clean_cid_numberSQL = " ,filter_clean_cid_number='$filter_clean_cid_number'";}
						}
					if (strlen($call_handle_method) > 0)
						{
						if ( (strlen($call_handle_method) > 20) or (strlen($call_handle_method) < 3) )
							{
							$result = 'ERROR';
							$result_reason = "add_did CALL HANDLE METHOD MUST BE FROM 3 TO 20 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$call_handle_method";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$call_handle_methodSQL = " ,call_handle_method='$call_handle_method'";}
						}
					if (strlen($agent_search_method) > 0)
						{
						if ( (strlen($agent_search_method) > 2) or (strlen($agent_search_method) < 2) )
							{
							$result = 'ERROR';
							$result_reason = "add_did AGENT SEARCH METHOD MUST BE 2 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$agent_search_method";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$agent_search_methodSQL = " ,agent_search_method='$agent_search_method'";}
						}
					if (strlen($list_id) > 0)
						{
						if ( (strlen($list_id) > 14) or (strlen($list_id) < 3) )
							{
							$result = 'ERROR';
							$result_reason = "add_did LIST ID MUST BE FROM 3 TO 14 DIGITS, THIS IS AN OPTIONAL FIELD";
							$data = "$list_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$list_idSQL = " ,list_id='$list_id'";}
						}
					if (strlen($entry_list_id) > 0)
						{
						if ( (strlen($entry_list_id) > 14) or (strlen($entry_list_id) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "add_did ENTRY LIST ID MUST BE FROM 1 TO 14 DIGITS, THIS IS AN OPTIONAL FIELD";
							$data = "$entry_list_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$entry_list_idSQL = " ,entry_list_id='$entry_list_id'";}
						}
					if (strlen($campaign_id) > 0)
						{
						if ( (strlen($campaign_id) > 8) or (strlen($campaign_id) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "add_did CAMPAIGN MUST BE FROM 1 TO 8 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$campaign_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$campaign_idSQL = " ,campaign_id='$campaign_id'";}
						}
					if (strlen($phone_code) > 0)
						{
						if ( (strlen($phone_code) > 10) or (strlen($phone_code) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "add_did PHONE CODE MUST BE FROM 1 TO 10 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$phone_code";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$phone_codeSQL = " ,phone_code='$phone_code'";}
						}

					$addSQL = "$did_patternSQL$did_descriptionSQL$activeSQL$did_routeSQL$record_callSQL$extensionSQL$exten_contextSQL$voicemail_extSQL$phone_extensionSQL$server_ipSQL$groupSQL$menu_idSQL$filter_clean_cid_numberSQL$call_handle_methodSQL$agent_search_methodSQL$list_idSQL$entry_list_idSQL$campaign_idSQL$phone_codeSQL";

					$addSQL = preg_replace("/^ ,/",'',$addSQL);
					$stmt="INSERT INTO vicidial_inbound_dids SET $addSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$add_count = mysqli_affected_rows($link);
					$did_id = mysqli_insert_id($link);
					if ($DB) {echo "$add_count|$did_id|$stmt|\n";}

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='DIDS', event_type='ADD', record_id='$did_id', event_code='ADMIN API ADD DID', event_sql=\"$SQL_log\", event_notes='did: $did_pattern did_id: $did_id inserted: $add_count';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$result = 'SUCCESS';
					$result_reason = "add_did DID HAS BEEN ADDED";
					$data = "$did_pattern";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END add_did
################################################################################





################################################################################
### copy_did - adds new Inbound DID entries to the system, copied from an existing DID entry
################################################################################
if ($function == 'copy_did')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_inbound_dids='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "copy_did USER DOES NOT HAVE PERMISSION TO ADD DIDS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($source_did_pattern)<2) or (strlen($source_did_pattern)>50) or (strlen($new_dids)<2) or (strlen($new_dids)>2000) )
				{
				$result = 'ERROR';
				$result_reason = "copy_did YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$source_did_pattern|$new_dids";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$admin_viewable_groupsSQL='';
				$WHEREadmin_viewable_groupsSQL='';
				if  (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups))
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$admin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$WHEREadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_inbound_dids where did_pattern='$source_did_pattern' $admin_viewable_groupsSQL;";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$did_exists=$row[0];
				if ($did_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "copy_did SOURCE DID DOES NOT EXIST";
					$data = "$source_did_pattern";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					# loop through all new_dids and insert them
					$new_dids_ct=0;
					$new_dids_ARY=array();
					if (preg_match("/,/",$new_dids))
						{
						$new_dids_ARY = explode(',',$new_dids);
						$new_dids_ct = count($new_dids_ARY);
						}
					else 
						{
						$new_dids_ARY[0] = $new_dids;
						$new_dids_ct=1;
						}

					$did_inserts=0;
					$did_insert_errors=0;
					$did_insert_log='';
					$i=0;
					while ($i < $new_dids_ct)
						{
						$stmt="SELECT count(*) from vicidial_inbound_dids where did_pattern='$new_dids_ARY[$i]';";
						if ($DB>0) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$did_exists=$row[0];
						if ($did_exists > 0)
							{
							$result = 'NOTICE';
							$result_reason = "copy_did DID ALREADY EXISTS";
							$data = "$new_dids_ARY[$i]";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							$did_insert_errors++;
							$did_insert_log .= "$new_dids_ARY[$i] ALREADY EXISTS\n";
							}
						else
							{
							$did_patternSQL = "did_pattern='$did_pattern'";
							$did_descriptionSQL='';

							if (strlen($did_description) > 0)
								{
								if ( (strlen($did_description) > 50) or (strlen($did_description) < 6) )
									{
									$result = 'ERROR';
									$result_reason = "copy_did DID DESCRIPTION MUST BE EITHER BLANK OR FROM 6 TO 50 CHARACTERS, THIS IS AN OPTIONAL FIELD";
									$data = "$did_description";
									echo "$result: $result_reason: |$user|$data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								else
									{$did_descriptionSQL = "'$did_description'";}
								}
							else
								{$did_descriptionSQL = "did_description";}

							$stmt = "INSERT IGNORE INTO vicidial_inbound_dids (did_pattern,did_description,did_active,did_route,extension,exten_context,voicemail_ext,phone,server_ip,user,user_unavailable_action,user_route_settings_ingroup,group_id,call_handle_method,agent_search_method,list_id,campaign_id,phone_code,menu_id,record_call,filter_inbound_number,filter_phone_group_id,filter_url,filter_action,filter_extension,filter_exten_context,filter_voicemail_ext,filter_phone,filter_server_ip,filter_user,filter_user_unavailable_action,filter_user_route_settings_ingroup,filter_group_id,filter_call_handle_method,filter_agent_search_method,filter_list_id,filter_campaign_id,filter_phone_code,filter_menu_id,filter_clean_cid_number,custom_one,custom_two,custom_three,custom_four,custom_five,user_group,filter_dnc_campaign,filter_url_did_redirect,no_agent_ingroup_redirect,no_agent_ingroup_id,no_agent_ingroup_extension,pre_filter_phone_group_id,pre_filter_extension,entry_list_id,filter_entry_list_id,max_queue_ingroup_calls,max_queue_ingroup_id,max_queue_ingroup_extension,did_carrier_description) SELECT '$new_dids_ARY[$i]',$did_descriptionSQL,did_active,did_route,extension,exten_context,voicemail_ext,phone,server_ip,user,user_unavailable_action,user_route_settings_ingroup,group_id,call_handle_method,agent_search_method,list_id,campaign_id,phone_code,menu_id,record_call,filter_inbound_number,filter_phone_group_id,filter_url,filter_action,filter_extension,filter_exten_context,filter_voicemail_ext,filter_phone,filter_server_ip,filter_user,filter_user_unavailable_action,filter_user_route_settings_ingroup,filter_group_id,filter_call_handle_method,filter_agent_search_method,filter_list_id,filter_campaign_id,filter_phone_code,filter_menu_id,filter_clean_cid_number,custom_one,custom_two,custom_three,custom_four,custom_five,user_group,filter_dnc_campaign,filter_url_did_redirect,no_agent_ingroup_redirect,no_agent_ingroup_id,no_agent_ingroup_extension,pre_filter_phone_group_id,pre_filter_extension,entry_list_id,filter_entry_list_id,max_queue_ingroup_calls,max_queue_ingroup_id,max_queue_ingroup_extension,did_carrier_description from vicidial_inbound_dids where did_pattern='$source_did_pattern';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$add_count = mysqli_affected_rows($link);
							$did_id = mysqli_insert_id($link);
							if ($DB) {echo "$add_count|$did_id|$stmt|\n";}
							$did_insert_log .= "$new_dids_ARY[$i] ADDED, did_id: $did_id\n";
							$did_inserts = ($did_inserts + $add_count);

							$result = 'NOTICE';
							$result_reason = "copy_did DID HAS BEEN ADDED";
							$data = "$new_dids_ARY[$i]|$source_did_pattern|$did_id|$add_count";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						$i++;
						}

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='DIDS', event_type='COPY', record_id='$did_id', event_code='ADMIN API COPY DIDS', event_sql=\"$SQL_log\", event_notes='DID Source: $source_did_pattern Inserts: $did_inserts Insert Errors: $did_insert_errors Copy Log:\n$did_insert_log';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					if ($did_inserts > 0)
						{
						$result = 'SUCCESS';
						$result_reason = "copy_did DIDS HAVE BEEN ADDED";
						$data = "$source_did_pattern|$did_inserts|$did_insert_errors";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "copy_did NO DIDS ADDED";
						$data = "$source_did_pattern|$did_inserts|$did_insert_errors";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END copy_did
################################################################################





################################################################################
### update_did - updates Inbound DID information in the vicidial_inbound_dids table
################################################################################
if ($function == 'update_did')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_inbound_dids='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_did USER DOES NOT HAVE PERMISSION TO UPDATE DIDS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($did_pattern)<2) or (strlen($did_pattern)>50) )
				{
				$result = 'ERROR';
				$result_reason = "update_did YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$did_pattern";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$admin_viewable_groupsSQL='';
				$WHEREadmin_viewable_groupsSQL='';
				if  (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups))
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$admin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$WHEREadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_inbound_dids where did_pattern='$did_pattern' $admin_viewable_groupsSQL;";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$did_exists=$row[0];
				if ($did_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "update_did DID DOES NOT EXIST";
					$data = "$did_pattern";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$stmt="SELECT did_id from vicidial_inbound_dids where did_pattern='$did_pattern' $admin_viewable_groupsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$did_id=$row[0];

					$did_descriptionSQL='';
					$activeSQL='';
					$did_routeSQL='';
					$record_callSQL='';
					$extensionSQL='';
					$exten_contextSQL='';
					$voicemail_extSQL='';
					$phone_extensionSQL='';
					$server_ipSQL='';
					$groupSQL='';
					$menu_idSQL='';
					$filter_clean_cid_numberSQL='';
					$call_handle_methodSQL='';
					$agent_search_methodSQL='';
					$list_idSQL='';
					$entry_list_idSQL='';
					$campaign_idSQL='';
					$phone_codeSQL='';

					if ($delete_did == 'Y')
						{
						if ($VUdelete_inbound_dids < 1)
							{
							$result = 'ERROR';
							$result_reason = "update_did USER DOES NOT HAVE PERMISSION TO DELETE DIDS";
							$data = "$allowed_user";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{
							$stmt="DELETE FROM vicidial_inbound_dids WHERE did_pattern='$did_pattern' limit 1;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$update_count = mysqli_affected_rows($link);
							if ($DB) {echo "$update_count|$stmt|\n";}

							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/', '', $SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='DIDS', event_type='DELETE', record_id='$did_id', event_code='ADMIN API DELETE DID', event_sql=\"$SQL_log\", event_notes='did: $did_pattern did_id: $did_id';";
							if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$result = 'SUCCESS';
							$result_reason = "update_did DID HAS BEEN DELETED";
							$data = "$did_pattern|$did_id";
							echo "$result: $result_reason - $user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

							exit;
							}
						}
					if (strlen($did_description) > 0)
						{
						if ( (strlen($did_description) > 50) or (strlen($did_description) < 6) )
							{
							$result = 'ERROR';
							$result_reason = "update_did DID DESCRIPTION MUST BE FROM 6 TO 50 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$did_description";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$did_descriptionSQL = " ,did_description='$did_description'";}
						}
					if (strlen($active) > 0)
						{
						if ( ($active != 'Y') and ($active != 'N') )
							{
							$result = 'ERROR';
							$result_reason = "update_did ACTIVE MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
							$data = "$active";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$activeSQL = " ,did_active='$active'";}
						}
					if (strlen($did_route) > 0)
						{
						if (preg_match("/^EXTEN$|^VOICEMAIL$|^AGENT$|^PHONE$|^IN_GROUP$|^CALLMENU$|^VMAIL_NO_INST$/",$did_route))
							{$did_routeSQL = " ,did_route='$did_route'";}
						else
							{
							$result = 'ERROR';
							$result_reason = "update_did DID ROUTE IS NOT VALID, THIS IS AN OPTIONAL FIELD";
							$data = "$did_route";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					if (strlen($record_call) > 0)
						{
						if ( ($record_call != 'Y') and ($record_call != 'N') and ($record_call != 'Y_QUEUESTOP') )
							{
							$result = 'ERROR';
							$result_reason = "update_did RECORD CALL MUST BE Y OR N, THIS IS AN OPTIONAL FIELD";
							$data = "$record_call";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$record_callSQL = " ,record_call='$record_call'";}
						}
					if (strlen($extension) > 0)
						{
						if (strlen($extension) > 50)
							{
							$result = 'ERROR';
							$result_reason = "update_did EXTENSION MUST BE FROM 1 TO 50 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$extension";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$extensionSQL = " ,extension='$extension'";}
						}
					if (strlen($exten_context) > 0)
						{
						if (strlen($exten_context) > 50)
							{
							$result = 'ERROR';
							$result_reason = "update_did EXTENSION CONTEXT MUST BE FROM 1 TO 50 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$exten_context";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$exten_contextSQL = " ,exten_context='$exten_context'";}
						}
					if (strlen($voicemail_ext) > 0)
						{
						if ( (strlen($voicemail_ext) > 10) or (strlen($voicemail_ext) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_did VOICEMAIL EXTENSION MUST BE FROM 1 TO 10 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$voicemail_ext";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$voicemail_extSQL = " ,voicemail_ext='$voicemail_ext'";}
						}
					if (strlen($phone_extension) > 0)
						{
						if ( (strlen($phone_extension) > 100) or (strlen($phone_extension) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_did PHONE EXTENSION MUST BE FROM 1 TO 100 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$phone_extension";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$phone_extensionSQL = " ,phone='$phone_extension'";}
						}
					if (strlen($server_ip) > 0)
						{
						$stmt="SELECT count(*) from servers where server_ip='$server_ip';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$server_exists=$row[0];

						if ($server_exists < 1)
							{
							$result = 'ERROR';
							$result_reason = "update_did SERVER IP MUST BE A VALID SERVER IN THE SYSTEM, THIS IS AN OPTIONAL FIELD";
							$data = "$server_ip";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						$server_ipSQL = " ,server_ip='$server_ip'";
						}
					if (strlen($group) > 0)
						{
						$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$group';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$group_exists=$row[0];

						if ($group_exists < 1)
							{
							$result = 'ERROR';
							$result_reason = "update_did GROUP ID MUST BE A VALID INBOUND GROUP IN THE SYSTEM, THIS IS AN OPTIONAL FIELD";
							$data = "$group";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						$groupSQL = " ,group_id='$group'";
						}
					if (strlen($menu_id) > 0)
						{
						$stmt="SELECT count(*) from vicidial_call_menu where menu_id='$menu_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$menu_id_exists=$row[0];

						if ($menu_id_exists < 1)
							{
							$result = 'ERROR';
							$result_reason = "update_did MENU ID MUST BE A VALID CALL MENU IN THE SYSTEM, THIS IS AN OPTIONAL FIELD";
							$data = "$menu_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						$menu_idSQL = " ,menu_id='$menu_id'";
						}
					if (strlen($filter_clean_cid_number) > 0)
						{
						if ( (strlen($filter_clean_cid_number) > 20) or (strlen($filter_clean_cid_number) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_did CLEAN CID NUMBER MUST BE FROM 1 TO 20 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$filter_clean_cid_number";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$filter_clean_cid_numberSQL = " ,filter_clean_cid_number='$filter_clean_cid_number'";}
						}
					if (strlen($call_handle_method) > 0)
						{
						if ( (strlen($call_handle_method) > 20) or (strlen($call_handle_method) < 3) )
							{
							$result = 'ERROR';
							$result_reason = "update_did CALL HANDLE METHOD MUST BE FROM 3 TO 20 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$call_handle_method";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$call_handle_methodSQL = " ,call_handle_method='$call_handle_method'";}
						}
					if (strlen($agent_search_method) > 0)
						{
						if ( (strlen($agent_search_method) > 2) or (strlen($agent_search_method) < 2) )
							{
							$result = 'ERROR';
							$result_reason = "update_did AGENT SEARCH METHOD MUST BE 2 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$agent_search_method";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$agent_search_methodSQL = " ,agent_search_method='$agent_search_method'";}
						}
					if (strlen($list_id) > 0)
						{
						if ( (strlen($list_id) > 14) or (strlen($list_id) < 3) )
							{
							$result = 'ERROR';
							$result_reason = "update_did LIST ID MUST BE FROM 3 TO 14 DIGITS, THIS IS AN OPTIONAL FIELD";
							$data = "$list_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$list_idSQL = " ,list_id='$list_id'";}
						}
					if (strlen($entry_list_id) > 0)
						{
						if ( (strlen($entry_list_id) > 14) or (strlen($entry_list_id) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_did ENTRY LIST ID MUST BE FROM 1 TO 14 DIGITS, THIS IS AN OPTIONAL FIELD";
							$data = "$entry_list_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$entry_list_idSQL = " ,entry_list_id='$entry_list_id'";}
						}
					if (strlen($campaign_id) > 0)
						{
						if ( (strlen($campaign_id) > 8) or (strlen($campaign_id) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_did CAMPAIGN MUST BE FROM 1 TO 8 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$campaign_id";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$campaign_idSQL = " ,campaign_id='$campaign_id'";}
						}
					if (strlen($phone_code) > 0)
						{
						if ( (strlen($phone_code) > 10) or (strlen($phone_code) < 1) )
							{
							$result = 'ERROR';
							$result_reason = "update_did PHONE CODE MUST BE FROM 1 TO 10 CHARACTERS, THIS IS AN OPTIONAL FIELD";
							$data = "$phone_code";
							echo "$result: $result_reason: |$user|$data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						else
							{$phone_codeSQL = " ,phone_code='$phone_code'";}
						}

					$updateSQL = "$did_descriptionSQL$activeSQL$did_routeSQL$record_callSQL$extensionSQL$exten_contextSQL$voicemail_extSQL$phone_extensionSQL$server_ipSQL$groupSQL$menu_idSQL$filter_clean_cid_numberSQL$call_handle_methodSQL$agent_search_methodSQL$list_idSQL$entry_list_idSQL$campaign_idSQL$phone_codeSQL";

					if (strlen($updateSQL)< 3)
						{
						$result = 'NOTICE';
						$result_reason = "update_did NO UPDATES DEFINED";
						$data = "$updateSQL";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$updateSQL = preg_replace("/^ ,/",'',$updateSQL);
						$stmt="UPDATE vicidial_inbound_dids SET $updateSQL WHERE did_pattern='$did_pattern';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$update_count = mysqli_affected_rows($link);
						if ($DB) {echo "$update_count|$stmt|\n";}

						### LOG INSERTION Admin Log Table ###
						$SQL_log = "$stmt|";
						$SQL_log = preg_replace('/;/', '', $SQL_log);
						$SQL_log = addslashes($SQL_log);
						$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='DIDS', event_type='MODIFY', record_id='$did_id', event_code='ADMIN API UPDATE DID', event_sql=\"$SQL_log\", event_notes='did: $did_pattern did_id: $did_id';";
						if ($DB) {echo "|$stmt|\n";}
						$rslt=mysql_to_mysqli($stmt, $link);

						$result = 'SUCCESS';
						$result_reason = "update_did DID HAS BEEN UPDATED";
						$data = "$did_pattern";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				}
			}
		}
	exit;
	}
################################################################################
### END update_did
################################################################################





################################################################################
### update_cid_group_entry - updates CID Group entries in the vicidial_campaign_cid_areacodes table
################################################################################
if ($function == 'update_cid_group_entry')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_campaigns='1' and user_level >= 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_cid_group_entry USER DOES NOT HAVE PERMISSION TO UPDATE CID GROUPS";
			$data = "$allowed_user";
			echo "$result: $result_reason: |$user|$data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ( (strlen($cid_group_id)<1) or (strlen($cid_group_id)>20) or (strlen($stage)<3) or (strlen($stage)>6) or (strlen($areacode)<1) or (strlen($areacode)>9) )
				{
				$result = 'ERROR';
				$result_reason = "update_cid_group_entry YOU MUST USE ALL REQUIRED FIELDS";
				$data = "$cid_group_id|$areacode|$stage";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
					}
				$admin_viewable_groupsSQL='';
				$WHEREadmin_viewable_groupsSQL='';
				if  (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups))
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$admin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$WHEREadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$stmt="SELECT count(*) from vicidial_cid_groups where cid_group_id='$cid_group_id' $admin_viewable_groupsSQL;";
				if ($DB>0) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$did_exists=$row[0];
				if ($did_exists < 1)
					{
					$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$cid_group_id' $LOGallowed_campaignsSQL;";
					if ($DB>0) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$campaign_exists=$row[0];

					if ($campaign_exists < 1)
						{
						$result = 'ERROR';
						$result_reason = "update_cid_group_entry CID GROUP DOES NOT EXIST";
						$data = "$cid_group_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}

				$areacodeSQL = "and areacode='$areacode'";
				if ($areacode == '---ALL---')
					{$areacodeSQL='';}
				$outbound_cidSQL = "and outbound_cid='$outbound_cid'";
				if ( ($outbound_cid == '---ALL---') or (strlen($outbound_cid) < 1) )
					{$outbound_cidSQL='';}

				##### BEGIN get information on CID Group entries #####
				if ($stage == 'INFO')
					{
					$stmt="SELECT areacode,outbound_cid,active,cid_description,call_count_today from vicidial_campaign_cid_areacodes where campaign_id='$cid_group_id' $areacodeSQL $outbound_cidSQL order by areacode,outbound_cid;";
					if ($DB>0) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$vcca_recs = mysqli_num_rows($rslt);
					$M=0;
					if ($vcca_recs > $M)
						{
						$results_output="SUCCESS: update_cid_group_entry CID GROUP INFO AS FOLLOWS $cid_group_id|$areacode|$outbound_cid|$vcca_recs\n";
						$data = "$cid_group_id|$areacode|$outbound_cid|$vcca_recs";
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "update_cid_group_entry THIS CID GROUP HAS NO ENTRIES";
						$data = "$cid_group_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					while ($vcca_recs > $M)
						{
						$row=mysqli_fetch_row($rslt);
						$ACareacode =			$row[0];
						$ACoutbound_cid =		$row[1];
						$ACactive =				$row[2];
						$ACcid_description =	$row[3];
						$ACcall_count_today =	$row[4];
						if ($ACactive == '') {$ACactive='N';}

						$M++;

						$results_output.="$M|$ACareacode|$ACoutbound_cid|$ACactive|$ACcid_description|$ACcall_count_today\n";
						}

					$result = 'SUCCESS';
					$result_reason = "update_cid_group_entry CID GROUP INFO AS FOLLOWS";
					echo "$results_output";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				##### END get information on CID Group entries #####

				##### BEGIN add a CID Group entry #####
				if ($stage == 'ADD')
					{
					if ($areacode == '---ALL---')
						{
						$result = 'ERROR';
						$result_reason = "update_cid_group_entry YOU MUST DEFINE AN AREACODE WHEN ADDING AND ENTRY";
						$data = "$cid_group_id|$areacode";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					if (strlen($outbound_cid) < 4)
						{
						$result = 'ERROR';
						$result_reason = "update_cid_group_entry YOU MUST DEFINE A CID NUMBER WHEN ADDING AND ENTRY";
						$data = "$cid_group_id|$outbound_cid";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					if ( (strlen($active) < 1) or ($active == 'N') or ( ($active != 'Y') and ($active != 'N') ) )
						{$active = '';}
					if (strlen($cid_description) < 1)
						{$cid_description = '';}

					$stmt="SELECT count(*) from vicidial_campaign_cid_areacodes where campaign_id='$cid_group_id' and areacode='$areacode' and outbound_cid='$outbound_cid';";
					if ($DB>0) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$cid_exists=$row[0];
					if ($cid_exists > 0)
						{
						$result = 'ERROR';
						$result_reason = "update_cid_group_entry THIS CID GROUP ENTRY ALREADY EXISTS";
						$data = "$cid_group_id|$areacode|$outbound_cid";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}

					$stmt="INSERT INTO vicidial_campaign_cid_areacodes SET campaign_id='$cid_group_id',areacode='$areacode',outbound_cid='$outbound_cid',active='$active',cid_description='$cid_description';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$insert_count = mysqli_affected_rows($link);
					if ($DB) {echo "$insert_count|$stmt|\n";}

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='CIDGROUPS', event_type='ADD', record_id='$cid_group_id', event_code='ADMIN API ADD CID', event_sql=\"$SQL_log\", event_notes='areacode: $areacode outbound_cid: $outbound_cid active: $active inserted: $insert_count';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$result = 'SUCCESS';
					$result_reason = "update_cid_group_entry CID GROUP ENTRY HAS BEEN ADDED";
					$data = "$cid_group_id|$areacode|$outbound_cid|$insert_count";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				##### END add a CID Group entry #####

				##### BEGIN delete CID Group entries #####
				if ($stage == 'DELETE')
					{
					$stmt="SELECT areacode,outbound_cid,active,cid_description,call_count_today from vicidial_campaign_cid_areacodes where campaign_id='$cid_group_id' $areacodeSQL $outbound_cidSQL order by areacode,outbound_cid;";
					if ($DB>0) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$vcca_recs = mysqli_num_rows($rslt);
					$M=0;
					if ($vcca_recs > $M)
						{
						$notes_output='';
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "update_cid_group_entry THIS CID GROUP HAS NO ENTRIES";
						$data = "$cid_group_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					while ($vcca_recs > $M)
						{
						$row=mysqli_fetch_row($rslt);
						$ACareacode =			$row[0];
						$ACoutbound_cid =		$row[1];
						$ACactive =				$row[2];
						$ACcid_description =	$row[3];
						$ACcall_count_today =	$row[4];
						if ($ACactive == '') {$ACactive='N';}
						$M++;
						$notes_output.="$M|$ACareacode|$ACoutbound_cid|$ACactive|$ACcid_description|$ACcall_count_today\n";
						}

					$stmt="DELETE FROM vicidial_campaign_cid_areacodes WHERE campaign_id='$cid_group_id' $areacodeSQL $outbound_cidSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$delete_count = mysqli_affected_rows($link);
					if ($DB) {echo "$insert_count|$stmt|\n";}

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='CIDGROUPS', event_type='DELETE', record_id='$cid_group_id', event_code='ADMIN API DELETE CID', event_sql=\"$SQL_log\", event_notes='areacode: $areacode outbound_cid: $outbound_cid deleted: $delete_count\n$notes_output';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$result = 'SUCCESS';
					$result_reason = "update_cid_group_entry CID GROUP ENTRIES HAVE BEEN DELETED";
					$data = "$cid_group_id|$areacode|$outbound_cid|$delete_count";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				##### END delete CID Group entries #####

				##### BEGIN update CID Group entries #####
				if ($stage == 'UPDATE')
					{
					$stmt="SELECT areacode,outbound_cid,active,cid_description,call_count_today from vicidial_campaign_cid_areacodes where campaign_id='$cid_group_id' $areacodeSQL $outbound_cidSQL order by areacode,outbound_cid;";
					if ($DB>0) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$vcca_recs = mysqli_num_rows($rslt);
					$M=0;
					if ($vcca_recs > $M)
						{
						$notes_output='';
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "update_cid_group_entry THIS CID GROUP HAS NO ENTRIES";
						$data = "$cid_group_id";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					while ($vcca_recs > $M)
						{
						$row=mysqli_fetch_row($rslt);
						$ACareacode =			$row[0];
						$ACoutbound_cid =		$row[1];
						$ACactive =				$row[2];
						$ACcid_description =	$row[3];
						$ACcall_count_today =	$row[4];
						if ($ACactive == '') {$ACactive='N';}
						$M++;
						$notes_output.="$M|$ACareacode|$ACoutbound_cid|$ACactive|$ACcid_description|$ACcall_count_today\n";
						}

					$updateSQL='';
					if ( ($active == 'Y') or ($active == 'N') )
						{
						$activeSQL=$active;
						if ($active == 'N') {$activeSQL='';}
						$updateSQL .= "active='$activeSQL'";
						}
					if (strlen($cid_description) > 0)
						{
						if (strlen($updateSQL) > 0) {$updateSQL .= ",";}
						$updateSQL .= "cid_description='$cid_description'";
						}
					if (strlen($updateSQL) < 5)
						{
						$result = 'ERROR';
						$result_reason = "update_cid_group_entry NO UPDATES WERE DEFINED";
						$data = "$cid_group_id|$areacode|$outbound_cid|$active|$cid_description";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}

					$stmt="UPDATE vicidial_campaign_cid_areacodes SET $updateSQL WHERE campaign_id='$cid_group_id' $areacodeSQL $outbound_cidSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$modify_count = mysqli_affected_rows($link);
					if ($DB) {echo "$modify_count|$stmt|\n";}

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$user', ip_address='$ip', event_section='CIDGROUPS', event_type='MODIFY', record_id='$cid_group_id', event_code='ADMIN API MODIFY CID', event_sql=\"$SQL_log\", event_notes='areacode: $areacode outbound_cid: $outbound_cid modified: $modify_count\n$notes_output';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					$result = 'SUCCESS';
					$result_reason = "update_cid_group_entry CID GROUP ENTRIES HAVE BEEN UPDATED";
					$data = "$cid_group_id|$areacode|$outbound_cid|$modify_count";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				##### END update CID Group entries #####
				}
			}
		}
	exit;
	}
################################################################################
### END update_cid_group_entry
################################################################################





################################################################################
### recording_lookup - looks up recordings based upon user and date or lead_id
################################################################################
if ($function == 'recording_lookup')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "recording_lookup USER DOES NOT HAVE PERMISSION TO GET RECORDING INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$search_SQL='';
			$search_ready=0;
			if (strlen($uniqueid)>8)
				{
				$uniqueid_search_SQL='';
				$uniqueidTEST = $uniqueid;
				$uniqueidTEST = preg_replace('/\..*$/','',$uniqueid);
				$stmt="select count(*) from vicidial_log where uniqueid LIKE \"$uniqueidTEST%\";";
				$rslt=mysql_to_mysqli($stmt, $link);
				$vlec_recs = mysqli_num_rows($rslt);
				if ($vlec_recs > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$VLfound_ct  =	$row[0];
					if ($VLfound_ct > 0)
						{
						$uniqueid_search_SQL .= "vicidial_id LIKE \"$uniqueidTEST%\"";
						}
					}
				$stmt="select closecallid from vicidial_closer_log where uniqueid LIKE \"$uniqueidTEST%\";";
				$rslt=mysql_to_mysqli($stmt, $link);
				$vclec_recs = mysqli_num_rows($rslt);
				if ($vclec_recs > 0)
					{
					$L=0;
					while ($vclec_recs > $L)
						{
						$row=mysqli_fetch_row($rslt);
						$search_ids .=	"'$row[0]',";
						$L++;
						}
					$search_ids = preg_replace('/,$/','',$search_ids);
					if (strlen($uniqueid_search_SQL)>5)
						{$uniqueid_search_SQL .= " or ";}
					$uniqueid_search_SQL .= "vicidial_id IN($search_ids)";
					}

				if (strlen($uniqueid_search_SQL)>5)
					{
					$search_SQL .= "($uniqueid_search_SQL)";
					$search_ready++;
					$search_ready++;
					}
				}
			if ( (strlen($agent_user)>2) and (strlen($agent_user)<21) )
				{
				if (strlen($search_SQL)>5)
					{$search_SQL .= " and ";}
				$search_SQL .= "user='$agent_user'";
				$search_ready++;
				}
			if ( (strlen($lead_id)>0) and (strlen($lead_id)<11) )
				{
				if (strlen($search_SQL)>5)
					{$search_SQL .= " and ";}
				$search_SQL .= "lead_id=$lead_id";
				$search_ready++;
				$search_ready++;
				}
			if ( (strlen($extension)>2) and (strlen($extension)<101) )
				{
				if (strlen($search_SQL)>5)
					{$search_SQL .= " and ";}
				$search_SQL .= "extension='$extension'";
				$search_ready++;
				}
			if ( (strlen($date)>9) and (strlen($date)<11) )
				{
				if (strlen($search_SQL)>5)
					{$search_SQL .= " and ";}
				$search_SQL .= "( (start_time >= \"$date 00:00:00\") and (start_time <= \"$date 23:59:59\") )";
				$search_ready++;
				}
			if ($search_ready < 2)
				{
				$result = 'ERROR';
				$result_reason = "recording_lookup INVALID SEARCH PARAMETERS";
				$data = "$user|$agent_user|$lead_id|$date|$uniqueid";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT start_time,user,recording_id,lead_id,location,start_epoch,end_epoch,length_in_sec from recording_log where $search_SQL order by start_time limit 100000;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$rec_recs = mysqli_num_rows($rslt);
				if ($DB>0) {echo "DEBUG: recording_lookup query - $rec_recs|$stmt\n";}
				if ($rec_recs < 1)
					{
					$result = 'ERROR';
					$result_reason = "recording_lookup NO RECORDINGS FOUND";
					$data = "$user|$agent_user|$lead_id|$date|$uniqueid";
					echo "$result: $result_reason - $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$k=0;
					$output='';
					$DLset=0;
					if ($stage == 'csv')
						{$DL = ',';   $DLset++;}
					if ($stage == 'tab')
						{$DL = "\t";   $DLset++;}
					if ($stage == 'pipe')
						{$DL = '|';   $DLset++;}
					if ($DLset < 1)
						{$DL='|';}
					if ($header == 'YES')
						{$output .= 'start_time' . $DL . 'user' . $DL . 'recording_id' . $DL . 'lead_id' . $DL . "location\n";}

					while ($rec_recs > $k)
						{
						$row=mysqli_fetch_row($rslt);
						$RLstart_time =		$row[0];
						$RLuser =			$row[1];
						$RLrecording_id =	$row[2];
						$RLlead_id =		$row[3];
						$RLlocation =		$row[4];
						$Rduration='';
						if (preg_match("/Y/",$duration))
							{
							$Rduration .= "$DL";
							$temp_duration=0;
							if ($row[7] > 0)
								{$temp_duration = $row[7];}
							else
								{
								$calc_duration = ($row[6] - $row[5]);
								if ( ($calc_duration > 0) and ($calc_duration < 86400) )
									{
									if ($DB > 0) {echo "DEBUG: CALC duration";}
									$temp_duration = $calc_duration;
									}
								}
							$Rduration .= "$temp_duration";
							}

						$output .= "$RLstart_time$DL$RLuser$DL$RLrecording_id$DL$RLlead_id$Rduration$DL$RLlocation\n";

						$k++;
						}

					echo "$output";

					$result = 'SUCCESS';
					$data = "$user|$agent_user|$lead_id|$date|$uniqueid|$stage";
					$result_reason = "recording_lookup RECORDINGS FOUND: $rec_recs";

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END recording_lookup
################################################################################





################################################################################
### did_log_export - exports all calls inbound to a DID for one day
################################################################################
if ($function == 'did_log_export')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "did_log_export USER DOES NOT HAVE PERMISSION TO GET DID INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$search_SQL='';
			$search_ready=0;
			if ( (strlen($phone_number)>0) and (strlen($phone_number)<21) )
				{
				$search_SQL .= "extension='$phone_number'";
				$search_ready++;
				}
			if ( (strlen($date)>9) and (strlen($date)<11) )
				{
				if (strlen($search_SQL)>5)
					{$search_SQL .= " and ";}
				$search_SQL .= "( (call_date >= \"$date 00:00:00\") and (call_date <= \"$date 23:59:59\") )";
				$search_ready++;
				}
			if ($search_ready < 2)
				{
				$result = 'ERROR';
				$result_reason = "did_log_export INVALID SEARCH PARAMETERS";
				$data = "$user|$phone_number|$date";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT uniqueid,caller_id_number,call_date,UNIX_TIMESTAMP(call_date) from vicidial_did_log where $search_SQL order by call_date limit 100000;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$rec_recs = mysqli_num_rows($rslt);
				if ($DB>0) {echo "DEBUG: did_log_export query - $rec_recs|$stmt\n";}
				if ($rec_recs < 1)
					{
					$result = 'ERROR';
					$result_reason = "did_log_export NO RECORDS FOUND";
					$data = "$user|$agent_user|$lead_id|$date";
					echo "$result: $result_reason - $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$k=0;
					$output='';
					$DLset=0;
					if ($stage == 'csv')
						{$DL = ',';   $DLset++;}
					if ($stage == 'tab')
						{$DL = "\t";   $DLset++;}
					if ($stage == 'pipe')
						{$DL = '|';   $DLset++;}
					if ($DLset < 1)
						{$DL='|';   $stage='pipe';}
					if ($header == 'YES')
						{$output .= 'did_number' . $DL . 'call_date' . $DL . 'caller_id_number' . $DL . "length_in_sec\n";}
					$DLuniqueid=array();
					$DLcall_date=array();
					$DLcaller_id_number=array();
					$DLepoch=array();
					$DLlength_in_sec=array();
					$DLcloser_epoch=array();

					while ($rec_recs > $k)
						{
						$row=mysqli_fetch_row($rslt);
						$DLuniqueid[$k] =			$row[0];
						$DLcall_date[$k] =			$row[1];
						$DLcaller_id_number[$k] =	$row[2];
						$DLepoch[$k] =				$row[3];
						$k++;
						}

					$k=0;
					while ($rec_recs > $k)
						{
						$DLlength_in_sec[$k]=0;
						$DLcloser_epoch[$k]=$DLepoch[$k];
						$stmt="SELECT length_in_sec,UNIX_TIMESTAMP(call_date) from vicidial_closer_log where uniqueid='$DLuniqueid[$k]' order by closecallid desc limit 1;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$vcl_recs = mysqli_num_rows($rslt);
						if ($DB>0) {echo "DEBUG: did_log_export query - $vcl_recs|$stmt\n";}
						if ($vcl_recs > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$DLlength_in_sec[$k] =		$row[0];
							$DLcloser_epoch[$k] =		$row[1];
							}

						$total_sec = ( ($DLcloser_epoch[$k] + $DLlength_in_sec[$k]) - $DLepoch[$k]);

						$output .= "$phone_number$DL$DLcall_date[$k]$DL$DLcaller_id_number[$k]$DL$total_sec\n";

						$k++;
						}

					echo "$output";

					$result = 'SUCCESS';
					$data = "$user|$agent_user|$lead_id|$date|$stage";
					$result_reason = "did_log_export RECORDS FOUND: $rec_recs";

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END did_log_export
################################################################################





################################################################################
### phone_number_log - exports list of calls placed to one of more phone numbers
################################################################################
if ($function == 'phone_number_log')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "phone_number_log USER DOES NOT HAVE PERMISSION TO GET CALL LOG INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$output='';
			$header_out=0;
			$phones_count=0;
			$phones_ready[0]='';
			$search_SQL='';
			$limit_SQL='';
			$search_ready=0;
			if (strlen($phone_number)>3)
				{
				if (preg_match("/,/",$phone_number))
					{
					$phones_ary = explode(",",$phone_number);
					$phones_ary_ct = count($phones_ary);
					$k=0;   $phones_in='';
					while($phones_ary_ct > $k)
						{
						if (strlen($phones_ary[$k])>3)
							{
							$phones_ready[$phones_count] = "$phones_ary[$k]";
							$phones_count++;
							}
						$k++;
						}
					}
				else
					{
					$phones_ready[$phones_count] = "$phone_number";
					$phones_count++;
					}
				}
			if ($phones_count < 1)
				{
				$result = 'ERROR';
				$result_reason = "phone_number_log NO VALID PHONE NUMBERS DEFINED";
				$data = "$user|$phone_number|$date";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$m=0;
				$k=0;
				$number_found=0;
				if ($detail == 'LAST')
					{$limit_SQL = "order by call_date desc limit 1";}
				else
					{$limit_SQL = "order by call_date desc limit 100000";}
				if ( ($type!='IN') and ($type!='OUT') and ($type!='ALL') ) {$type='OUT';}

				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}

				$DLphone_number=array();
				$DLcall_date=array();
				$DLlist_id=array();
				$DLlead_id=array();
				$DLlength_in_sec=array();
				$DLdispo_status=array();
				$DLhangup_reason=array();
				$DLlead_status=array();
				$DLsource_id=array();
				$DLuser=array();

				while($phones_count > $m)
					{
					$search_SQL = "phone_number='$phones_ready[$m]'";

					if ($DB>0) {echo "DEBUG: $m|$phones_count|$phones_ready[$m]\n";}

					##### FIND ALL OUTBOUND CALLS #####
					if ( ($type == 'OUT') or ($type == 'ALL') )
						{
						$s=0;
						$stmt="SELECT call_date,list_id,lead_id,length_in_sec,status,term_reason,user from vicidial_log where $search_SQL $limit_SQL;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$rec_recs = mysqli_num_rows($rslt);
						if ($DB>0) {echo "DEBUG: phone_number_log outbound query - $rec_recs|$stmt\n";}
						if ($rec_recs > 0)
							{
							$number_found++;
							if ( ($header == 'YES') and ($header_out < 1) )
								{
								$output .= 'phone_number' . $DL . 'call_date' . $DL . 'list_id' . $DL . 'length_in_sec' . $DL . 'lead_status' . $DL . 'hangup_reason' . $DL . 'call_status' . $DL . 'source_id' . $DL . "user\n";
								$header_out++;
								}

							while ($rec_recs > $s)
								{
								$row=mysqli_fetch_row($rslt);
								$DLphone_number[$k] =		$phones_ready[$m];
								$DLcall_date[$k] =			$row[0];
								$DLlist_id[$k] =			$row[1];
								$DLlead_id[$k] =			$row[2];
								$DLlength_in_sec[$k] =		$row[3];
								$DLdispo_status[$k] =		$row[4];
								$DLhangup_reason[$k] =		$row[5];
								$DLlead_status[$k] =		$row[4];
								$DLsource_id[$k] =			'';
								$DLuser[$k] =				$row[6];
								$k++;
								$s++;
								}
							}
						}
					##### FIND ALL INBOUND CALLS #####
					if ( ($type == 'IN') or ($type == 'ALL') )
						{
						$s=0;
						$stmt="SELECT call_date,list_id,lead_id,length_in_sec,status,term_reason,user from vicidial_closer_log where $search_SQL $limit_SQL;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$rec_recs = mysqli_num_rows($rslt);
						if ($DB>0) {echo "DEBUG: phone_number_log inbound query - $rec_recs|$stmt\n";}
						if ($rec_recs > 0)
							{
							$number_found++;
							if ( ($header == 'YES') and ($header_out < 1) )
								{
								$output .= 'phone_number' . $DL . 'call_date' . $DL . 'list_id' . $DL . 'length_in_sec' . $DL . 'lead_status' . $DL . 'hangup_reason' . $DL . 'call_status' . $DL . 'source_id' . $DL . "user\n";
								$header_out++;
								}

							while ($rec_recs > $s)
								{
								$row=mysqli_fetch_row($rslt);
								$DLphone_number[$k] =		$phones_ready[$m];
								$DLcall_date[$k] =			$row[0];
								$DLlist_id[$k] =			$row[1];
								$DLlead_id[$k] =			$row[2];
								$DLlength_in_sec[$k] =		$row[3];
								$DLdispo_status[$k] =		$row[4];
								$DLhangup_reason[$k] =		$row[5];
								$DLstatus[$k] =				$row[4];
								$DLsource_id[$k] =			'';
								$DLuser[$k] =				$row[6];
								$k++;
								$s++;
								}
							}
						}
					$m++;
					}

				$p=0;
				while ($k > $p)
					{
					$DLlength_in_sec[$k]=0;
					$DLcloser_epoch[$k]=$DLepoch[$k];
					$stmt="SELECT status,source_id from $vicidial_list_table where lead_id='$DLlead_id[$p]';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$vcl_recs = mysqli_num_rows($rslt);
					if ($DB>0) {echo "DEBUG: phone_number_log list query - $vcl_recs|$stmt\n";}
					if ($vcl_recs > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$DLlead_status[$p] =		$row[0];
						$DLsource_id[$p] =			$row[1];
						}
					$output .= "$DLphone_number[$p]$DL$DLcall_date[$p]$DL$DLlist_id[$p]$DL$DLlength_in_sec[$p]$DL$DLlead_status[$p]$DL$DLhangup_reason[$p]$DL$DLdispo_status[$p]$DL$DLsource_id[$p]$DL$DLuser[$p]\n";

					$p++;
					}

				if ($number_found < 1)
					{
					$result = 'NOTICE';
					$result_reason = "phone_number_log NO RECORDS FOUND FOR THIS PHONE NUMBER";
					$data = "$user|$phone_number|$lead_id|$date";
					echo "$result: $result_reason - $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					echo "$output";

					$result = 'SUCCESS';
					$data = "$user|$agent_user|$lead_id|$date|$stage";
					$result_reason = "phone_number_log RECORDS FOUND: $rec_recs";

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END phone_number_log
################################################################################





################################################################################
### agent_stats_export - exports agent stats for set time period
################################################################################
if ($function == 'agent_stats_export')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "agent_stats_export USER DOES NOT HAVE PERMISSION TO GET AGENT INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$search_SQL='';
			$search_ready=0;

			if ( (strlen($agent_user)>0) and (strlen($agent_user)<21) )
				{
				$search_SQL .= "user='$agent_user'";
				$search_ready++;
				}
			if ( (strlen($campaign_id)>0) and (strlen($campaign_id)<9) )
				{
				if (strlen($search_SQL)>5)
					{$search_SQL .= " and ";}
				$search_SQL .= "campaign_id='$campaign_id'";
				$search_ready++;
				}
			if ( (strlen($datetime_start)>18) and (strlen($datetime_start)<20) and (strlen($datetime_end)>18) and (strlen($datetime_end)<20) )
				{
				$datetime_start = preg_replace("/\+/",' ',$datetime_start);
				$datetime_end = preg_replace("/\+/",' ',$datetime_end);
				if (strlen($search_SQL)>5)
					{$search_SQL .= " and ";}
				$search_SQL .= "( (event_time >= \"$datetime_start\") and (event_time <= \"$datetime_end\") )";
				$search_ready++;
				}
			else
				{$search_ready=0;}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "agent_stats_export INVALID SEARCH PARAMETERS";
				$data = "$user|$agent_user|$datetime_start|$datetime_end|$campaign_id";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				if ($group_by_campaign == 'YES')
					{
					$stmt="SELECT user,lead_id,sub_status,pause_sec,wait_sec,talk_sec,dispo_sec,dead_sec,pause_epoch,campaign_id from vicidial_agent_log where $search_SQL order by campaign_id,user,agent_log_id limit 10000000;";
					}
				else
					{
					$stmt="SELECT user,lead_id,sub_status,pause_sec,wait_sec,talk_sec,dispo_sec,dead_sec,pause_epoch,campaign_id from vicidial_agent_log where $search_SQL order by user,agent_log_id limit 10000000;";
					}
				$rslt=mysql_to_mysqli($stmt, $link);
				$rec_recs = mysqli_num_rows($rslt);
				if ($DB>0) {echo "DEBUG: agent_stats_export query - $rec_recs|$stmt\n";}
				if ($rec_recs < 1)
					{
					$result = 'ERROR';
					$result_reason = "agent_stats_export NO RECORDS FOUND";
					$data = "$user|$agent_user|$datetime_start|$datetime_end|$campaign_id";
					echo "$result: $result_reason - $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					$k=0;
					$output='';
					$DLset=0;
					if ($stage == 'csv')
						{$DL = ',';   $DLset++;}
					if ($stage == 'tab')
						{$DL = "\t";   $DLset++;}
					if ($stage == 'pipe')
						{$DL = '|';   $DLset++;}
					if ($DLset < 1)
						{$DL='|';   $stage='pipe';}
					if (strlen($time_format) < 1)
						{$time_format = 'HF';}
					if ($header == 'YES')
						{
						if ($group_by_campaign == 'YES')
							{
							$output .= 'campaign_id' . $DL . 'user' . $DL . 'full_name' . $DL . 'user_group' . $DL . 'calls' . $DL . 'login_time' . $DL . 'total_talk_time' . $DL . 'avg_talk_time' . $DL . 'avg_wait_time' . $DL . 'pct_of_queue' . $DL . 'pause_time' . $DL . 'sessions' . $DL . 'avg_session' . $DL . 'pauses' . $DL . 'avg_pause_time' . $DL . 'pause_pct' . $DL . 'pauses_per_session' . $DL . 'wait_time' . $DL . 'talk_time' . $DL . 'dispo_time' . $DL . 'dead_time' . "\n";
							}
						else
							{
							$output .= 'user' . $DL . 'full_name' . $DL . 'user_group' . $DL . 'calls' . $DL . 'login_time' . $DL . 'total_talk_time' . $DL . 'avg_talk_time' . $DL . 'avg_wait_time' . $DL . 'pct_of_queue' . $DL . 'pause_time' . $DL . 'sessions' . $DL . 'avg_session' . $DL . 'pauses' . $DL . 'avg_pause_time' . $DL . 'pause_pct' . $DL . 'pauses_per_session' . $DL . 'wait_time' . $DL . 'talk_time' . $DL . 'dispo_time' . $DL . 'dead_time' . "\n";
							}
						}

					$ASuser=array();
					$AScampaign=array();
					$ASstart_epoch=array();
					$AScalls=array();
					$ASpauses=array();
					$ASsessions=array();
					$ASpause_sec=array();
					$ASwait_sec=array();
					$AStalk_sec=array();
					$ASdispo_sec=array();
					$ASdead_sec=array();
					$ASend_epoch=array();
					$ASend_sec=array();
					$ASfull_name=array();
					$ASuser_group=array();

					$last_user='998877665544332211328497';
					$total_calls=0;
					$uc=-1;
					while ($rec_recs > $k)
						{
						# user,lead_id,sub_status,pause_sec,wait_sec,talk_sec,dispo_sec,dead_sec
						$row=mysqli_fetch_row($rslt);
						if ($group_by_campaign == 'YES')
							{
							$temp_camp_user="$row[9] $row[0]";
							if (!preg_match("/^$last_user$/i", $temp_camp_user))
								{
								$uc++;
								$ASuser[$uc] =			$row[0];
#								$ASdispo_sec[$uc] =		$row[6];
#								$ASdead_sec[$uc] =	$row[7];
								$AScampaign[$uc] =		$row[9];
								$ASstart_epoch[$uc] =	$row[8];
								$last_user =			$temp_camp_user;
								$AScalls[$uc] =			0;
								$ASpauses[$uc] =		0;
								$ASsessions[$uc] =		0;
								}
							}
						else
							{
							if (!preg_match("/^$last_user$/i", $row[0]))
								{
								$uc++;
								$ASuser[$uc] =			$row[0];
								$ASdispo_sec[$uc] =		$row[6];
#								$ASdead_sec[$uc] =	$row[7];
#								$ASstart_epoch[$uc] =	$row[8];
								$last_user =			$row[0];
								$AScalls[$uc] =			0;
								$ASpauses[$uc] =		0;
								$ASsessions[$uc] =		0;
								}
							}
						if ($row[1] > 0)
							{
							$AScalls[$uc]++;
							$total_calls++;
							}
						if ($row[3] > -1)
							{
							$ASpauses[$uc]++;
							}
						if (preg_match("/LOGIN/",$row[2])) {$ASsessions[$uc]++;}
						$ASpause_sec[$uc] =		($ASpause_sec[$uc] + $row[3]);
						$ASwait_sec[$uc] =		($ASwait_sec[$uc] + $row[4]);
						$AStalk_sec[$uc] =		($AStalk_sec[$uc] + $row[5]);
						$ASdispo_sec[$uc] =		($ASdispo_sec[$uc] + $row[6]);
						$ASdead_sec[$uc] =		($ASdead_sec[$uc] + $row[7]);
						$ASend_epoch[$uc] =		$row[8];
						$ASend_sec[$uc] =		($row[3] + $row[4] + $row[5] + $row[6]);
						$k++;
						}

					$k=0;
					while ($uc >= $k)
						{
						$stmt="SELECT full_name,user_group from vicidial_users where user='$ASuser[$k]' limit 1;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$vcl_recs = mysqli_num_rows($rslt);
						if ($DB>0) {echo "DEBUG: agent_stats_export query - $vcl_recs|$stmt\n";}
						if ($vcl_recs > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$ASfull_name[$k] =		$row[0];
							$ASuser_group[$k] =		$row[1];
							}
						$login_sec = ($ASpause_sec[$k] + $ASwait_sec[$k] + $AStalk_sec[$k] + $ASdispo_sec[$k]);
						$login_start_end_check = ( ($ASend_epoch[$k] - $ASstart_epoch[$k]) + $ASend_sec[$k]);
						if ($login_sec > $login_start_end_check) {$login_sec = $login_start_end_check;}
						if ($ASsessions[$k] < 1) {$ASsessions[$k] = 1;}
						$avg_session_sec = MathZDC($login_sec, $ASsessions[$k]);
						$avg_pause_sec = MathZDC($ASpause_sec[$k], $ASpauses[$k]);
						$avg_pause_session = MathZDC($ASpauses[$k], $ASsessions[$k]);
						if ( ($ASpauses[$k] < 1) or ($login_sec < 1) )
							{
							$pct_pause = 100;
							}
						else
							{
							$pct_pause = ( MathZDC($ASpause_sec[$k], $login_sec) * 100);
							}
						$avg_cust_sec = MathZDC($cust_sec, $AScalls[$k]);
						$avg_wait_sec = MathZDC($ASwait_sec[$k], $AScalls[$k]);
						$pct_of_queue = ( MathZDC($AScalls[$k], $total_calls) * 100);
						if ($AScalls[$k] < 1)
							{
							$cust_sec = 0;
							$wait_sec = 0;
							$talk_sec = 0;
							$dead_sec = 0;
							$dispo_sec = 0;
							}
						else
							{
							$cust_sec = ($AStalk_sec[$k] - $ASdead_sec[$k]);
							$wait_sec = $ASwait_sec[$k];
							$talk_sec = $AStalk_sec[$k];
							$dead_sec = $ASdead_sec[$k];
							$dispo_sec = $ASdispo_sec[$k];
							}
						$avg_session_sec = round($avg_session_sec);
						$avg_pause_sec = round($avg_pause_sec);
						$avg_pause_session = round($avg_pause_session);
						$pct_pause = round($pct_pause, 1);
						$avg_cust_sec = round($avg_cust_sec);
						$avg_wait_sec = round($avg_wait_sec);
						$pct_of_queue = round($pct_of_queue, 1);
						$login_sec =		sec_convert($login_sec,$time_format);
						$avg_session_sec =	sec_convert($avg_session_sec,$time_format);
						$avg_pause_sec =	sec_convert($avg_pause_sec,$time_format);
						$cust_sec =			sec_convert($cust_sec,$time_format);
						$wait_sec =			sec_convert($wait_sec,$time_format);
						$talk_sec =			sec_convert($talk_sec,$time_format);
						$dead_sec =			sec_convert($dead_sec,$time_format);
						$dispo_sec =		sec_convert($dispo_sec,$time_format);
						$avg_cust_sec =		sec_convert($avg_cust_sec,$time_format);
						$avg_wait_sec =		sec_convert($avg_wait_sec,$time_format);

						if ($group_by_campaign == 'YES')
							{
							$output .= "$AScampaign[$k]$DL$ASuser[$k]$DL$ASfull_name[$k]$DL$ASuser_group[$k]$DL$AScalls[$k]$DL$login_sec$DL$cust_sec$DL$avg_cust_sec$DL$avg_wait_sec$DL$pct_of_queue%$DL$ASpause_sec[$k]$DL$ASsessions[$k]$DL$avg_session_sec$DL$ASpauses[$k]$DL$avg_pause_sec$DL$pct_pause%$DL$avg_pause_session$DL$wait_sec$DL$talk_sec$DL$dispo_sec$DL$dead_sec\n";
							}
						else
							{
							$output .= "$ASuser[$k]$DL$ASfull_name[$k]$DL$ASuser_group[$k]$DL$AScalls[$k]$DL$login_sec$DL$cust_sec$DL$avg_cust_sec$DL$avg_wait_sec$DL$pct_of_queue%$DL$ASpause_sec[$k]$DL$ASsessions[$k]$DL$avg_session_sec$DL$ASpauses[$k]$DL$avg_pause_sec$DL$pct_pause%$DL$avg_pause_session$DL$wait_sec$DL$talk_sec$DL$dispo_sec$DL$dead_sec\n";
							}
						$k++;
						}

					echo "$output";

					$result = 'SUCCESS';
					$data = "$user|$agent_user|$lead_id|$date|$stage";
					$result_reason = "agent_stats_export AGENTS FOUND: $k";

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### END agent_stats_export
################################################################################





################################################################################
### user_group_status - exports user group real-time stats
################################################################################
if ($function == 'user_group_status')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "user_group_status USER DOES NOT HAVE PERMISSION TO GET USER GROUP INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$search_SQL='';
			$search_ready=0;

			if ( (strlen($user_groups)>0) and (strlen($user_groups)<10000) )
				{
				$user_groupsOUTPUT = preg_replace("/\|/",' ',$user_groups);
				$user_groupsSQL = preg_replace("/\|/","','",$user_groups);
				$search_SQL .= "and user_group IN('$user_groupsSQL')";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "user_group_status INVALID SEARCH PARAMETERS";
				$data = "$user|$user_groups";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}
				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				$whereLOGallowed_callsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";

					$stmt="select group_id from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$groups_to_print = mysqli_num_rows($rslt);
					$i=0;
					$rawLOGallowed_ingroupsSQL='';
					while ($i < $groups_to_print)
						{
						$row=mysqli_fetch_row($rslt);
						$rawLOGallowed_ingroupsSQL .=	"$row[0]','";
						$i++;
						}
					$whereLOGallowed_callsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL','$rawLOGallowed_ingroupsSQL')";
					}

				$k=0;
				$output='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}
				if (strlen($time_format) < 1)
					{$time_format = 'HF';}
				if ($header == 'YES')
					{$output .= 'usergroups' . $DL . 'calls_waiting' . $DL . 'agents_logged_in' . $DL . 'agents_in_calls' . $DL . 'agents_waiting' . $DL . 'agents_paused' . $DL . 'agents_in_dead_calls' . $DL . 'agents_in_dispo' . $DL . 'agents_in_dial' . "\n";}

				$total_agents=0;
				$total_agents_in_calls=0;
				$total_agents_waiting=0;
				$total_agents_paused=0;
				$total_agents_dead=0;
				$total_agents_dispo=0;
				$total_agents_in_dial=0;
				$total_calls=0;
				$total_calls_waiting=0;
				$call_camps[0]='';
				$call_types[0]='';
				$call_camps=array();
				$call_types=array();

				$callerids='';
				$pausecode='';
				$stmt="select callerid,status,campaign_id,call_type from vicidial_auto_calls $whereLOGallowed_callsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$calls_to_list = mysqli_num_rows($rslt);
				if ($calls_to_list > 0)
					{
					$i=0;
					while ($i < $calls_to_list)
						{
						$row=mysqli_fetch_row($rslt);
						$callerids .=	"$row[0]|";
						if (preg_match("/LIVE/i",$row[1]))
							{$total_calls_waiting++;}
						$call_camps[$i] = $row[2];
						$call_types[$i] = $row[3];
						$i++;
						}
					}

				$stmt="select vicidial_live_agents.user,vicidial_live_agents.status,vicidial_live_agents.campaign_id,vicidial_users.user_group,vicidial_live_agents.comments,vicidial_live_agents.callerid,lead_id from vicidial_live_agents,vicidial_users where vicidial_live_agents.user=vicidial_users.user $search_SQL $LOGadmin_viewable_groupsSQL limit 10000000;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$rec_recs = mysqli_num_rows($rslt);
				if ($DB>0) {echo "DEBUG: user_group_status query - $rec_recs|$stmt\n";}
				if ($rec_recs > 0)
					{
					while ($rec_recs > $k)
						{
						# user,status,campaign_id,user_group,comments,callerid,lead_id
						$row=mysqli_fetch_row($rslt);

						if (preg_match("/READY|PAUSED/i",$row[1]))
							{
							if ($row[6] > 0)
								{$row[1] =	'DISPO';}
							}

						if (preg_match("/INCALL/i",$row[1]))
							{
							$stmtP="select count(*) from parked_channels where channel_group='$row[5]';";
							$rsltP=mysql_to_mysqli($stmtP,$link);
							$rowP=mysqli_fetch_row($rsltP);
							$parked_channel = $rowP[0];

							if ($parked_channel > 0)
								{$row[1] =	'PARK';}
							else
								{
								if (preg_match("/CHAT/i",$row[4]))
									{
									$stmtCT="SELECT chat_id from vicidial_live_chats where chat_creator='$row[0]' and lead_id='$row[6]' order by chat_start_time desc limit 1;";
									if ($DB) {echo "$stmtCT\n";}
									$rsltCT=mysql_to_mysqli($stmtCT,$link);
									$chatting_to_print = mysqli_num_rows($rslt);
									if ($chatting_to_print > 0)
										{
										$rowCT=mysqli_fetch_row($rsltCT);
										$Achat_id = $rowCT[0];

										$stmtCL="SELECT count(*) from vicidial_chat_log where chat_id='$Achat_id' and message LIKE \"%has left chat\";";
										if ($DB) {echo "$stmtCL\n";}
										$rsltCL=mysql_to_mysqli($stmtCL,$link);
										$rowCL=mysqli_fetch_row($rsltCL);
										$left_chat = $rowCL[0];

										if ($left_chat > 0)
											{
											$row[1] =	'DEAD';
											}
										}
									}
								elseif (preg_match("/EMAIL/i",$row[4]))
									{$do_nothing=1;}
								else
									{
									if (!preg_match("/$row[5]\|/",$callerids))
										{$row[1] =	'DEAD';}
									else
										{
										if ($row[4] == 'MANUAL')
											{
											$stmt="SELECT uniqueid,channel from vicidial_auto_calls where callerid='$row[5]' LIMIT 1;";
											$rslt=mysql_to_mysqli($stmt, $link);
											if ($DB) {echo "$stmt\n";}
											$mandial_to_check = mysqli_num_rows($rslt);
											if ($mandial_to_check > 0)
												{
												$rowvac=mysqli_fetch_row($rslt);
												if ( (strlen($rowvac[0])<5) and (strlen($rowvac[1])<5) )
													{
													$row[1] =	'DIAL';
													}
												}
											}
										}
									}
								}
							}

						if ($row[1]=='DEAD')
							{$total_agents_dead++;}
						if ($row[1]=='DISPO')
							{$total_agents_dispo++;}
						if ($row[1]=='PAUSED')
							{$total_agents_paused++;}
						if ( (preg_match("/INCALL|DIAL/i",$row[1])) or (preg_match("/QUEUE/i",$row[1])) or (preg_match('/PARK/i',$row[1])))
							{$total_agents_in_calls++;}
						if (preg_match("/DIAL/i",$row[1]))
							{$total_agents_in_dial++;}
						if ( (preg_match("/READY/i",$row[1])) or (preg_match("/CLOSER/i",$row[1])) )
							{$total_agents_waiting++;}

						$total_agents++;
						$k++;
						}
					}
				$output .= "$user_groupsOUTPUT$DL$total_calls_waiting$DL$total_agents$DL$total_agents_in_calls$DL$total_agents_waiting$DL$total_agents_paused$DL$total_agents_dead$DL$total_agents_dispo$DL$total_agents_in_dial\n";

				echo "$output";

				$result = 'SUCCESS';
				$data = "$user|$user_groups|$stage";
				$result_reason = "user_group_status AGENTS FOUND: $k";

				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		}
	exit;
	}
################################################################################
### END user_group_status
################################################################################





################################################################################
### in_group_status - exports in-group real-time stats
################################################################################
if ($function == 'in_group_status')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "in_group_status USER DOES NOT HAVE PERMISSION TO GET IN-GROUP INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$search_SQL='';
			$search_ready=0;

			if ( (strlen($in_groups)>0) and (strlen($in_groups)<10000) )
				{
				$in_groupsOUTPUT = preg_replace("/\|/",' ',$in_groups);
				$in_groupsSQL = preg_replace("/\|/","','",$in_groups);
				$search_SQL .= "where campaign_id IN('$in_groupsSQL')";
				$agent_search_SQL .= "where group_id IN('$in_groupsSQL')";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "in_group_status INVALID SEARCH PARAMETERS";
				$data = "$user|$in_groups";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];
				$LOGadmin_viewable_groups =		$row[1];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}
				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				$LOGallowed_callsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";

					$stmt="select group_id from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL order by group_id;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$groups_to_print = mysqli_num_rows($rslt);
					$i=0;
					$rawLOGallowed_ingroupsSQL='';
					while ($i < $groups_to_print)
						{
						$row=mysqli_fetch_row($rslt);
						$rawLOGallowed_ingroupsSQL .=	"$row[0]','";
						$i++;
						}
					$LOGallowed_callsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL','$rawLOGallowed_ingroupsSQL')";
					}


				$k=0;
				$output='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}
				if (strlen($time_format) < 1)
					{$time_format = 'HF';}
				if ($header == 'YES')
					{$output .= 'ingroups' . $DL . 'total_calls' . $DL . 'calls_waiting' . $DL . 'agents_logged_in' . $DL . 'agents_in_calls' . $DL . 'agents_waiting' . $DL . 'agents_paused' . $DL . 'agents_in_dispo' . $DL . 'agents_in_dial' . "\n";}

				$total_agents=0;
				$total_agents_in_calls=0;
				$total_agents_waiting=0;
				$total_agents_paused=0;
				$total_agents_dead=0;
				$total_agents_dispo=0;
				$total_agents_in_dial=0;
				$total_calls=0;
				$total_calls_waiting=0;
				$call_camps=array();
				$call_types=array();
				$call_camps[0]='';
				$call_types[0]='';

				$callerids='';
				$pausecode='';
				$stmt="select callerid,status,campaign_id,call_type from vicidial_auto_calls $search_SQL $LOGallowed_callsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$calls_to_list = mysqli_num_rows($rslt);
				if ($calls_to_list > 0)
					{
					$i=0;
					while ($i < $calls_to_list)
						{
						$row=mysqli_fetch_row($rslt);
						$callerids .=	"$row[0]|";
						if (preg_match("/LIVE/i",$row[1]))
							{$total_calls_waiting++;}
						$call_camps[$i] = $row[2];
						$call_types[$i] = $row[3];
						$total_calls++;
						$i++;
						}
					}

				$users='';
				$stmt="SELECT distinct user from vicidial_live_inbound_agents where group_id IN('$in_groupsSQL');";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$IG_users = mysqli_num_rows($rslt);
				if ($IG_users > 0)
					{
					$i=0;
					while ($i < $IG_users)
						{
						$row=mysqli_fetch_row($rslt);
						$users .=	"$row[0]','";
						$i++;
						}
					}

				$k=0;
				$stmt="select vicidial_live_agents.user,vicidial_live_agents.status,vicidial_live_agents.campaign_id,vicidial_users.user_group,vicidial_live_agents.comments,vicidial_live_agents.callerid,lead_id from vicidial_live_agents,vicidial_users where vicidial_live_agents.user=vicidial_users.user and vicidial_live_agents.user IN('$users') $LOGadmin_viewable_groupsSQL limit 10000000;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$rec_recs = mysqli_num_rows($rslt);
				if ($DB>0) {echo "DEBUG: in_group_status query - $rec_recs|$stmt\n";}
				if ($rec_recs > 0)
					{
					while ($rec_recs > $k)
						{
						# user,status,campaign_id,user_group,comments,callerid,lead_id
						$row=mysqli_fetch_row($rslt);

						if (preg_match("/READY|PAUSED/i",$row[1]))
							{
							if ($row[6] > 0)
								{$row[1] =	'DISPO';}
							}
						if ($row[1]=='DISPO')
							{$total_agents_dispo++;}
						if ($row[1]=='PAUSED')
							{$total_agents_paused++;}
						if ( (preg_match("/INCALL|DIAL/i",$row[1])) or (preg_match("/QUEUE/i",$row[1])) or (preg_match('/PARK/i',$row[1])))
							{$total_agents_in_calls++;}
						if (preg_match("/DIAL/i",$row[1]))
							{$total_agents_in_dial++;}
						if ( (preg_match("/READY/i",$row[1])) or (preg_match("/CLOSER/i",$row[1])) )
							{$total_agents_waiting++;}

						$total_agents++;
						$k++;
						}
					}
				$output .= "$in_groupsOUTPUT$DL$total_calls$DL$total_calls_waiting$DL$total_agents$DL$total_agents_in_calls$DL$total_agents_waiting$DL$total_agents_paused$DL$total_agents_dispo$DL$total_agents_in_dial\n";

				echo "$output";

				$result = 'SUCCESS';
				$data = "$user|$in_groups|$stage";
				$result_reason = "in_group_status CALLS FOUND: $k";

				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		}
	exit;
	}
################################################################################
### END in_group_status
################################################################################




################################################################################
### agent_status - exports agent user real-time stats
################################################################################
if ($function == 'agent_status')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "agent_status USER DOES NOT HAVE PERMISSION TO GET AGENT INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$agent_search_SQL='';
			$search_ready=0;

			if ( (strlen($agent_user)>0) and (strlen($agent_user)<100) )
				{
				$agent_search_SQL .= "where user='$agent_user'";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "agent_status INVALID SEARCH PARAMETERS";
				$data = "$user|$agent_user";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGadmin_viewable_groups =		$row[0];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$k=0;
				$output='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}
				if (strlen($time_format) < 1)
					{$time_format = 'HF';}
				if ($header == 'YES')
					{
					$output .= 'status' . $DL . 'callerid' . $DL . 'lead_id' . $DL . 'campaign_id' . $DL . 'calls_today' . $DL . 'full_name' . $DL . 'user_group' . $DL . 'user_level' . $DL . 'pause_code' . $DL . 'real_time_sub_status' . $DL . 'phone_number' . $DL . 'vendor_lead_code' . $DL . 'session_id';
					if ($include_ip == 'YES')
						{$output .= $DL . 'computer_ip';}
					$output .= "\n";
					}

				$stmt="SELECT full_name,user_group,user_level from vicidial_users $agent_search_SQL $LOGadmin_viewable_groupsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$user_to_list = mysqli_num_rows($rslt);
				if ($user_to_list > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$full_name = 	$row[0];
					$user_group = 	$row[1];
					$user_level = 	$row[2];

					$stmt="SELECT status,callerid,lead_id,campaign_id,calls_today,agent_log_id,on_hook_agent,ring_callerid,preview_lead_id,conf_exten,comments from vicidial_live_agents $agent_search_SQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$agent_to_list = mysqli_num_rows($rslt);
					if ($agent_to_list > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$status =			$row[0];
						$callerid =			$row[1];
						$lead_id =			$row[2];
						$campaign_id =		$row[3];
						$calls_today =		$row[4];
						$agent_log_id =		$row[5];
						$on_hook_agent =	$row[6];
						$ring_callerid =	$row[7];
						$preview_lead_id =	$row[8];
						$conf_exten =		$row[9];
						$comments =			$row[10];
						$pause_code =		'';
						$rtr_status =		'';

						$stmt="SELECT sub_status from vicidial_agent_log $agent_search_SQL and agent_log_id='$agent_log_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$agent_to_log = mysqli_num_rows($rslt);
						if ($agent_to_log > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$pause_code =		$row[0];
							}

						if ( ($on_hook_agent == 'Y') and (strlen($ring_callerid) > 18) )
							{$rtr_status = "RING";}

						if ( ($status == 'PAUSED') and ($lead_id > 0) )
							{$rtr_status = 'DISPO';}

						if ( ($status == 'PAUSED') and ($preview_lead_id > 0) )
							{
							$rtr_status = 'PREVIEW';
							$lead_id = $preview_lead_id;
							}

						if ($status == 'INCALL')
							{
							if ($lead_id > 0)
								{
								$threewaystmt="select UNIX_TIMESTAMP(last_call_time) from vicidial_live_agents where lead_id=$lead_id and status='INCALL' order by UNIX_TIMESTAMP(last_call_time) desc;";
								$threewayrslt=mysql_to_mysqli($threewaystmt, $link);
								if (mysqli_num_rows($threewayrslt)>1)
									{$rtr_status = '3-WAY';}
								}

							$stmt="SELECT count(*) from parked_channels where channel_group='$callerid';";
							$rslt=mysql_to_mysqli($stmt,$link);
							$row=mysqli_fetch_row($rslt);
							$parked_channel = $row[0];
							if ($parked_channel > 0)
								{$rtr_status = 'PARK';}
							else
								{
								if (preg_match("/CHAT/i",$comments))
									{
									$stmtCT="SELECT chat_id from vicidial_live_chats where chat_creator='$agent_user' and lead_id=$lead_id order by chat_start_time desc limit 1;";
									if ($DB) {echo "$stmtCT\n";}
									$rsltCT=mysql_to_mysqli($stmtCT,$link);
									$chatting_to_print = mysqli_num_rows($rslt);
									if ($chatting_to_print > 0)
										{
										$rowCT=mysqli_fetch_row($rsltCT);
										$Achat_id = $rowCT[0];

										$stmtCL="SELECT count(*) from vicidial_chat_log where chat_id='$Achat_id' and message LIKE \"%has left chat\";";
										if ($DB) {echo "$stmtCL\n";}
										$rsltCL=mysql_to_mysqli($stmtCL,$link);
										$rowCL=mysqli_fetch_row($rsltCL);
										$left_chat = $rowCL[0];

										if ($left_chat > 0)
											{
											$rtr_status = 'DEAD';
											}
										}
									}
								elseif (preg_match("/EMAIL/i",$comments))
									{$do_nothing=1;}
								else
									{
									$stmt="SELECT count(*) from vicidial_auto_calls where callerid='$callerid';";
									$rslt=mysql_to_mysqli($stmt,$link);
									$row=mysqli_fetch_row($rslt);
									$live_channel = $row[0];
									if ($live_channel < 1)
										{$rtr_status = 'DEAD';}
									else
										{
										if ($comments == 'MANUAL')
											{
											$stmt="SELECT uniqueid,channel from vicidial_auto_calls where callerid='$callerid' LIMIT 1;";
											$rslt=mysql_to_mysqli($stmt, $link);
											if ($DB) {echo "$stmt\n";}
											$mandial_to_check = mysqli_num_rows($rslt);
												if ($mandial_to_check > 0)
												{
												$rowvac=mysqli_fetch_row($rslt);
												if ( (strlen($rowvac[0])<5) and (strlen($rowvac[1])<5) )
													{
													$rtr_status =	'DIAL';
													}
												}
											}
										}
									}
								}
							}

						$vendor_lead_code='';
						$phone_number='';
						if ($lead_id > 0)
							{
							$stmt="SELECT vendor_lead_code,phone_number from vicidial_list where lead_id=$lead_id;";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "$stmt\n";}
							$leadinfo_ct = mysqli_num_rows($rslt);
							if ($leadinfo_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$vendor_lead_code =		$row[0];
								$phone_number =			$row[1];
								}
							}
						if (strlen($callerid) > 0)
							{
							$stmt="SELECT phone_number from vicidial_auto_calls where callerid='$callerid';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "$stmt\n";}
							$phoneinfo_ct = mysqli_num_rows($rslt);
							if ($phoneinfo_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								if (strlen($row[0])>3)
									{$phone_number = $row[0];}
								}
							}
						$computer_ipOUTPUT='';
						if ($include_ip == 'YES')
							{
							$computer_ip='';
							$two_days_ago = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-2,date("Y")));
							$stmt="SELECT computer_ip from vicidial_user_log where user='$agent_user' and event='LOGIN' and event_date < \"$two_days_ago\" order by event_date desc limit 1;";
							$rslt=mysql_to_mysqli($stmt, $link);
							if ($DB) {echo "$stmt\n";}
							$ipinfo_ct = mysqli_num_rows($rslt);
							if ($ipinfo_ct > 0)
								{
								$row=mysqli_fetch_row($rslt);
								if (strlen($row[0])>3)
									{
									$computer_ip = $row[0];
									$computer_ipOUTPUT = "$DL$computer_ip";
									}
								}
							}


						$output .= "$status$DL$callerid$DL$lead_id$DL$campaign_id$DL$calls_today$DL$full_name$DL$user_group$DL$user_level$DL$pause_code$DL$rtr_status$DL$phone_number$DL$vendor_lead_code$DL$conf_exten$computer_ipOUTPUT\n";

						echo "$output";

						$result = 'SUCCESS';
						$data = "$user|$agent_user|$stage";
						$result_reason = "agent_status $output";

						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "agent_status AGENT NOT LOGGED IN";
						$data = "$user|$agent_user";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "agent_status AGENT NOT FOUND";
					$data = "$user|$agent_user";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	exit;
	}
################################################################################
### END agent_status
################################################################################




################################################################################
### user_details - display information about one user
################################################################################
if ($function == 'user_details')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "user_details USER DOES NOT HAVE PERMISSION TO GET USER DETAILS";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$agent_search_SQL='';
			$search_ready=0;

			if ( (strlen($agent_user)>0) and (strlen($agent_user)<100) )
				{
				$agent_search_SQL .= "where user='$agent_user'";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "user_details INVALID SEARCH PARAMETERS";
				$data = "$user|$agent_user";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGadmin_viewable_groups =		$row[0];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$k=0;
				$output='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}
				if (strlen($time_format) < 1)
					{$time_format = 'HF';}
				if ($header == 'YES')
					{
					$output .= 'user' . $DL . 'full_name' . $DL . 'user_group' . $DL . 'user_level' . $DL . 'active';
					$output .= "\n";
					}

				$stmt="SELECT full_name,user_group,user_level,active from vicidial_users $agent_search_SQL $LOGadmin_viewable_groupsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$user_to_list = mysqli_num_rows($rslt);
				if ($user_to_list > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$full_name = 	$row[0];
					$user_group = 	$row[1];
					$user_level = 	$row[2];
					$active = 		$row[3];

					$output .= "$agent_user$DL$full_name$DL$user_group$DL$user_level$DL$active\n";

					echo "$output";

					$result = 'SUCCESS';
					$data = "$user|$agent_user|$stage";
					$result_reason = "user_details $output";

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "user_details USER NOT FOUND";
					$data = "$user|$agent_user";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	exit;
	}
################################################################################
### END user_details
################################################################################




################################################################################
### callid_info - outputs information about a call based upon the caller_code(or call ID)
################################################################################
if ($function == 'callid_info')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "callid_info USER DOES NOT HAVE PERMISSION TO GET CALL INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$call_search_SQL='';
			$search_ready=0;
			$call_id = preg_replace("/\n|\r|\t| /",'',$call_id);

			if ( (strlen($call_id)>17) and (strlen($call_id)<40) and (preg_match("/^Y|^J|^V|^M|^DC|^S|^LP|^VH|^XL/",$call_id)) )
				{
				$call_search_SQL .= "where caller_code='$call_id'";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "callid_info INVALID SEARCH PARAMETERS";
				$data = "$user|$call_id";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT admin_viewable_groups from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGadmin_viewable_groups =		$row[0];

				$LOGadmin_viewable_groupsSQL='';
				$whereLOGadmin_viewable_groupsSQL='';
				if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
					{
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
					$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
					$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
					}

				$k=0;
				$output='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}
				if (strlen($time_format) < 1)
					{$time_format = 'HF';}
				if ($header == 'YES')
					{
					if ($detail == 'YES')
						{
						$output .= 'call_id' . $DL . 'custtime' . $DL . 'call_date' . $DL . 'phone' . $DL . 'call_type' . $DL . 'campaign_id' . $DL . 'list_id' . $DL . 'status' . $DL . 'user' . "\n";
						}
					else
						{
						$output .= 'call_id' . $DL . 'custtime' . "\n";
						}
					}

				$stmt="SELECT uniqueid,call_date,lead_id from vicidial_log_extended $call_search_SQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$vle_to_list = mysqli_num_rows($rslt);
				if ($vle_to_list > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$uniqueid = 	$row[0];
					$call_date = 	$row[1];
					$lead_id = 		$row[2];

					if (preg_match("/^Y|^J|^LP|^VH/",$call_id))
						{
						$stmt="SELECT phone_number,user,status,list_id,campaign_id from vicidial_closer_log where call_date='$call_date' and uniqueid='$uniqueid' and lead_id=$lead_id;";
						$call_type = 'INBOUND';
						}
					else
						{
						if (preg_match("/^V/",$call_id))
							{
							$stmt="SELECT phone_number,user,status,list_id,campaign_id from vicidial_log where uniqueid='$uniqueid' and lead_id=$lead_id;";
							$call_type = 'OUTBOUND_AUTO';
							}
						else
							{
							if (preg_match("/^M/",$call_id))
								{
								$stmt="SELECT phone_number,user,status,list_id,campaign_id from vicidial_log where uniqueid='$uniqueid' and lead_id=$lead_id;";
								$call_type = 'OUTBOUND_MANUAL';
								}
							else
								{
								$stmt="SELECT phone_number,user,status,list_id,campaign_id from vicidial_log where uniqueid='$uniqueid' and lead_id=$lead_id;";
								$call_type = 'OUTBOUND_OTHER';
								}
							}
						}
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$call_to_list = mysqli_num_rows($rslt);
					if ($call_to_list > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$log_phone =		$row[0];
						$log_user =			$row[1];
						$log_status =		$row[2];
						$log_list_id =		$row[3];
						$log_campaign_id =	$row[4];
						$cust_sec =			0;

						$stmt="SELECT talk_sec,dead_sec from vicidial_agent_log where uniqueid='$uniqueid' and lead_id=$lead_id and user='$log_user';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$agent_to_log = mysqli_num_rows($rslt);
						if ($agent_to_log > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$talk_sec =		$row[0];
							$dead_sec =		$row[1];
							$cust_sec = ($talk_sec - $dead_sec);
							}

						if ($detail == 'YES')
							{
							$output .= "$call_id$DL$cust_sec$DL$call_date$DL$log_phone$DL$call_type$DL$log_campaign_id$DL$log_list_id$DL$log_status$DL$log_user\n";
							}
						else
							{
							$output .= "$call_id$DL$cust_sec\n";
							}
						echo "$output";

						$result = 'SUCCESS';
						$data = "$user|$call_id|$stage";
						$result_reason = "callid_info $output";

						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "callid_info CALL LOG NOT FOUND";
						$data = "$user|$agent_user";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "callid_info CALL NOT FOUND";
					$data = "$user|$agent_user";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	exit;
	}
################################################################################
### callid_info - outputs information about a call based upon the caller_code(or call ID)
################################################################################





################################################################################
### ccc_lead_info - outputs lead data for cross-cluster-communication call
################################################################################
if ($function == 'ccc_lead_info')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "ccc_lead_info USER DOES NOT HAVE PERMISSION TO GET LEAD INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$call_search_SQL='';
			$search_ready=0;
			$call_id = preg_replace("/\n|\r|\t| /",'',$call_id);

			if ( (strlen($call_id)>17) and (strlen($call_id)<40) and (preg_match("/^Y|^J|^V|^M|^DC|^S|^LP|^VH|^XL/",$call_id)) )
				{
				$tenhoursago = date("Y-m-d H:i:s", mktime(date("H")-10,date("i"),date("s"),date("m"),date("d"),date("Y")));
				$call_search_SQL .= "where caller_code='$call_id' and call_date > \"$tenhoursago\"";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "ccc_lead_info INVALID SEARCH PARAMETERS";
				$data = "$user|$call_id";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT admin_viewable_groups,allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGadmin_viewable_groups =		$row[0];
				$LOGallowed_campaigns =			$row[1];

				$LOGallowed_campaignsSQL='';
				if ( (!preg_match("/ALL-CAMPAIGNS/i",$LOGallowed_campaigns)) )
					{
					$LOGallowed_campaignsSQL = preg_replace('/\s-/i','',$LOGallowed_campaigns);
					$LOGallowed_campaignsSQL = preg_replace('/\s/i',"','",$LOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$LOGallowed_campaignsSQL')";
					}

				$k=0;
				$output='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'newline')
					{$DL = "\n";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}
				if (strlen($time_format) < 1)
					{$time_format = 'HF';}
				if ($header == 'YES')
					{
					$output .= 'status' . $DL . 'user' . $DL . 'vendor_lead_code' . $DL . 'source_id' . $DL . 'list_id' . $DL . 'gmt_offset_now' . $DL . 'phone_code' . $DL . 'phone_number' . $DL . 'title' . $DL . 'first_name' . $DL . 'middle_initial' . $DL . 'last_name' . $DL . 'address1' . $DL . 'address2' . $DL . 'address3' . $DL . 'city' . $DL . 'state' . $DL . 'province' . $DL . 'postal_code' . $DL . 'country_code' . $DL . 'gender' . $DL . 'date_of_birth' . $DL . 'alt_phone' . $DL . 'email' . $DL . 'security_phrase' . $DL . 'comments' . $DL . 'called_count' . $DL . 'last_local_call_time' . $DL . 'rank' . $DL . 'owner' . "\n";
					}

				$stmt="SELECT uniqueid,call_date,lead_id from vicidial_log_extended $call_search_SQL;";
				if (preg_match("/^DC/",$call_id))
					{$stmt="SELECT uniqueid,call_date,lead_id from vicidial_dial_log $call_search_SQL;";}
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$vle_to_list = mysqli_num_rows($rslt);
				if ($vle_to_list > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$uniqueid = 	$row[0];
					$call_date = 	$row[1];
					$lead_id = 		$row[2];

					$stmt="SELECT status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner from vicidial_list where lead_id=$lead_id;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$lead_to_list = mysqli_num_rows($rslt);
					if ($lead_to_list > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$LEADstatus =				$row[0];
						$LEADuser =					$row[1];
						$LEADvendor_lead_code =		$row[2];
						$LEADsource_id =			$row[3];
						$LEADlist_id =				$row[4];
						$LEADgmt_offset_now =		$row[5];
						$LEADphone_code =			$row[6];
						$LEADphone_number =			$row[7];
						$LEADtitle =				$row[8];
						$LEADfirst_name =			$row[9];
						$LEADmiddle_initial =		$row[10];
						$LEADlast_name =			$row[11];
						$LEADaddress1 =				$row[12];
						$LEADaddress2 =				$row[13];
						$LEADaddress3 =				$row[14];
						$LEADcity =					$row[15];
						$LEADstate =				$row[16];
						$LEADprovince =				$row[17];
						$LEADpostal_code =			$row[18];
						$LEADcountry_code =			$row[19];
						$LEADgender =				$row[20];
						$LEADdate_of_birth =		$row[21];
						$LEADalt_phone =			$row[22];
						$LEADemail =				$row[23];
						$LEADsecurity_phrase =		$row[24];
						$LEADcomments =				$row[25];
						$LEADcalled_count =			$row[26];
						$LEADlast_local_call_time = $row[27];
						$LEADrank =					$row[28];
						$LEADowner =				$row[29];

						if ( (strlen($LOGallowed_campaignsSQL) > 3) and ($api_list_restrict > 0) )
							{
							$stmt="SELECT count(*) from vicidial_lists where list_id='$LEADlist_id' $LOGallowed_campaignsSQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$allowed_list=$row[0];
							if ($allowed_list < 1)
								{
								$result = 'ERROR';
								$result_reason = "ccc_lead_info LEAD NOT FOUND";
								echo "$result: $result_reason: |$user|$allowed_user|\n";
								$data = "$allowed_user";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}

						$output .= "$LEADstatus$DL$LEADuser$DL$LEADvendor_lead_code$DL$LEADsource_id$DL$LEADlist_id$DL$LEADgmt_offset_now$DL$LEADphone_code$DL$LEADphone_number$DL$LEADtitle$DL$LEADfirst_name$DL$LEADmiddle_initial$DL$LEADlast_name$DL$LEADaddress1$DL$LEADaddress2$DL$LEADaddress3$DL$LEADcity$DL$LEADstate$DL$LEADprovince$DL$LEADpostal_code$DL$LEADcountry_code$DL$LEADgender$DL$LEADdate_of_birth$DL$LEADalt_phone$DL$LEADemail$DL$LEADsecurity_phrase$DL$LEADcomments$DL$LEADcalled_count$DL$LEADlast_local_call_time$DL$LEADrank$DL$LEADowner\n";

						echo "$output";

						$result = 'SUCCESS';
						$data = "$user|$call_id|$stage";
						$result_reason = "ccc_lead_info $output";

						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "ccc_lead_info LEAD NOT FOUND";
						$data = "$user|$agent_user";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "ccc_lead_info CALL NOT FOUND";
					$data = "$user|$agent_user";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	exit;
	}
################################################################################
### ccc_lead_info - outputs lead data for cross-cluster-communication call
################################################################################





################################################################################
### lead_callback_info - outputs scheduled callback data for a specific lead
################################################################################
if ($function == 'lead_callback_info')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "lead_callback_info USER DOES NOT HAVE PERMISSION TO GET LEAD INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$call_search_SQL='';
			$search_ready=0;

			if ( (strlen($lead_id)>0) and (strlen($lead_id)<12) )
				{
				$call_search_SQL .= "where lead_id=$lead_id";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "lead_callback_info INVALID SEARCH PARAMETERS";
				$data = "$user|$lead_id";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT admin_viewable_groups,allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGadmin_viewable_groups =		$row[0];
				$LOGallowed_campaigns =			$row[1];

				$LOGallowed_campaignsSQL='';
				if ( (!preg_match("/ALL-CAMPAIGNS/i",$LOGallowed_campaigns)) )
					{
					$LOGallowed_campaignsSQL = preg_replace('/\s-/i','',$LOGallowed_campaigns);
					$LOGallowed_campaignsSQL = preg_replace('/\s/i',"','",$LOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$LOGallowed_campaignsSQL')";
					}

				$k=0;
				$output='';
				$DLset=0;
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'newline')
					{$DL = "\n";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}
				if (strlen($time_format) < 1)
					{$time_format = 'HF';}
				if ($header == 'YES')
					{
					$output .= 'lead_id' . $DL . 'callback_type' . $DL . 'recipient' . $DL . 'callback_status' . $DL . 'lead_status' . $DL . 'callback_date' . $DL . 'entry_date' . $DL . 'user' . $DL . 'list_id' . $DL . 'campaign_id' . $DL . 'user_group' . $DL . 'customer_timezone' . $DL . 'customer_time' . $DL . 'comments' . "\n";
					}

				if ( ($search_location == 'ARCHIVE') or ($search_location == 'ALL') )
					{
					# search archive callbacks
					$stmt="SELECT recipient,status,lead_status,callback_time,entry_time,user,list_id,campaign_id,user_group,comments,customer_timezone,customer_time from vicidial_callbacks_archive $call_search_SQL order by callback_id;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$vcb_to_list = mysqli_num_rows($rslt);
					$r=0;
					while ($vcb_to_list > $r)
						{
						$row=mysqli_fetch_row($rslt);
						$callback_type = 		'ARCHIVE';
						$CBrecipient = 			$row[0];
						$CBstatus = 			$row[1];
						$CBlead_status = 		$row[2];
						$CBcallback_time = 		$row[3];
						$CBentry_time = 		$row[4];
						$CBuser = 				$row[5];
						$CBlist_id = 			$row[6];
						$CBcampaign_id = 		$row[7];
						$CBuser_group = 		$row[8];
						$CBcomments =			$row[9];
						$CBcustomer_timezone = 	$row[10];
							$CBcustomer_timezone = preg_replace("/,/",'-',$CBcustomer_timezone);
						$CBcustomer_time = 		$row[11];

						if ( (strlen($LOGallowed_campaignsSQL) > 3) and ($api_list_restrict > 0) )
							{
							$stmt="SELECT count(*) from vicidial_lists where list_id='$CBlist_id' $LOGallowed_campaignsSQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$allowed_list=$row[0];
							if ($allowed_list < 1)
								{
								$result = 'ERROR';
								$result_reason = "lead_callback_info LEAD NOT FOUND";
								echo "$result: $result_reason: |$user|$allowed_user|\n";
								$data = "$allowed_user";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}

						$output .= "$lead_id$DL$callback_type$DL$CBrecipient$DL$CBstatus$DL$CBlead_status$DL$CBcallback_time$DL$CBentry_time$DL$CBuser$DL$CBlist_id$DL$CBcampaign_id$DL$CBuser_group$DL$CBcustomer_timezone$DL$CBcustomer_time$DL$CBcomments\n";
						$r++;
						}
					}

				if ( ($search_location == 'CURRENT') or ($search_location == 'ALL') or ($search_location == '') )
					{
					# search current callbacks
					$stmt="SELECT recipient,status,lead_status,callback_time,entry_time,user,list_id,campaign_id,user_group,comments,customer_timezone,customer_time from vicidial_callbacks $call_search_SQL order by callback_id;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$vcb_to_list = mysqli_num_rows($rslt);
					$r=0;
					while ($vcb_to_list > $r)
						{
						$row=mysqli_fetch_row($rslt);
						$callback_type = 		'CURRENT';
						$CBrecipient = 			$row[0];
						$CBstatus = 			$row[1];
						$CBlead_status = 		$row[2];
						$CBcallback_time = 		$row[3];
						$CBentry_time = 		$row[4];
						$CBuser = 				$row[5];
						$CBlist_id = 			$row[6];
						$CBcampaign_id = 		$row[7];
						$CBuser_group = 		$row[8];
						$CBcomments =			$row[9];
						$CBcustomer_timezone = 	$row[10];
							$CBcustomer_timezone = preg_replace("/,/",'-',$CBcustomer_timezone);
						$CBcustomer_time = 		$row[11];

						if ( (strlen($LOGallowed_campaignsSQL) > 3) and ($api_list_restrict > 0) )
							{
							$stmt="SELECT count(*) from vicidial_lists where list_id='$CBlist_id' $LOGallowed_campaignsSQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$allowed_list=$row[0];
							if ($allowed_list < 1)
								{
								$result = 'ERROR';
								$result_reason = "lead_callback_info LEAD NOT FOUND";
								echo "$result: $result_reason: |$user|$allowed_user|\n";
								$data = "$allowed_user";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							}

						$output .= "$lead_id$DL$callback_type$DL$CBrecipient$DL$CBstatus$DL$CBlead_status$DL$CBcallback_time$DL$CBentry_time$DL$CBuser$DL$CBlist_id$DL$CBcampaign_id$DL$CBuser_group$DL$CBcustomer_timezone$DL$CBcustomer_time$DL$CBcomments\n";
						$r++;
						}
					}

				if ( ( ( ($header == 'NO') or ($header == '') ) and (strlen($output) > 10) ) or ( ($header == 'YES') and (strlen($output) > 170) ) )
					{
					echo "$output";

					$result = 'SUCCESS';
					$data = "$user|$lead_id|$stage|$search_location";
					$result_reason = "lead_callback_info $output";

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "lead_callback_info CALLBACK NOT FOUND";
					$data = "$user|$lead_id|$stage|$search_location";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	exit;
	}
################################################################################
### lead_callback_info - outputs scheduled callback data for a specific lead
################################################################################





################################################################################
### lead_dearchive - moves a lead from the archive to active leads table
################################################################################
if ($function == 'lead_dearchive')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads='1' and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "lead_dearchive USER DOES NOT HAVE PERMISSION TO MODIFY LEAD INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$lead_search_SQL='';
			$search_ready=0;

			if ( (strlen($lead_id)>0) and (strlen($lead_id)<12) )
				{
				$lead_search_SQL .= "where lead_id='$lead_id'";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "lead_dearchive INVALID SEARCH PARAMETERS";
				$data = "$user|$lead_id";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGallowed_campaigns =			$row[0];

				$LOGallowed_campaignsSQL='';
				$whereLOGallowed_campaignsSQL='';
				$allowed_listsSQL='';
				if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
					{
					$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
					$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
					$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";

					$stmt="SELECT list_id from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
					if ($DB>0) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$lists_to_print = mysqli_num_rows($rslt);
					$i=0;
					$allowed_lists=' ';
					$allowed_listsSQL='';
					while ($i < $lists_to_print)
						{
						$row=mysqli_fetch_row($rslt);
						$allowed_lists .=		"$row[0] ";
						$allowed_listsSQL .=	"'$row[0]',";
						$i++;
						}
					$allowed_listsSQL = preg_replace("/,$/",'',$allowed_listsSQL);
					if ($DB>0) {echo "Allowed lists:|$allowed_lists|$allowed_listsSQL|\n";}
					if (strlen($allowed_listsSQL) > 3)
						{$allowed_listsSQL = "and list_id IN($allowed_listsSQL)";}
					}

				$stmt="SELECT list_id,entry_list_id from vicidial_list_archive $lead_search_SQL $allowed_listsSQL limit 1;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$lead_archive_exists = mysqli_num_rows($rslt);

				if ($lead_archive_exists > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$lead_list_id =			$row[0];
					$lead_entry_list_id =	$row[1];
	
					$stmt="SELECT list_id,entry_list_id from vicidial_list $lead_search_SQL limit 1";
					if ($DB) {$MAIN.="|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$lead_exists = mysqli_num_rows($rslt);

					if ($lead_exists < 1)
						{
						# Insert archived lead back into vicidial_list
						$vl_table_insert_SQL = "INSERT INTO vicidial_list SELECT * from vicidial_list_archive where lead_id='$lead_id';";
						$rslt=mysql_to_mysqli($vl_table_insert_SQL, $link);
						$vl_table_insert_count = mysqli_affected_rows($link);

						if ($vl_table_insert_count > 0)
							{
							# Delete archived lead from vicidial_list_archive
							$vla_table_delete_SQL = "DELETE FROM vicidial_list_archive where lead_id='$lead_id';";
							$rslt=mysql_to_mysqli($vla_table_delete_SQL, $link);
							$vla_table_delete_count = mysqli_affected_rows($link);

							$result = 'SUCCESS';
							$data = "$user|$lead_id|$vl_table_insert_count|$vla_table_delete_count";
							$result_reason = "lead_dearchive LEAD DE-ARCHIVED";
							echo "$result: $result_reason: $data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$result = 'ERROR';
							$result_reason = "lead_dearchive LEAD INSERT FAILED";
							$data = "$user|$lead_id|$lead_list_id";
							echo "$result: $result_reason: $data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "lead_dearchive NON-ARCHIVED LEAD FOUND";
						$data = "$user|$lead_id|$lead_list_id";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "lead_dearchive ARCHIVED LEAD NOT FOUND";
					$data = "$user|$lead_id|$stage|$search_location";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	exit;
	}
################################################################################
### lead_dearchive - moves a lead from the archive to active leads table
################################################################################





################################################################################
### lead_field_info - pulls the value of one field of a lead
################################################################################
if ($function == 'lead_field_info')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4') and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "lead_field_info USER DOES NOT HAVE PERMISSION TO GET LEAD INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$lead_search_SQL='';
			$search_ready=0;

			if ( (strlen($lead_id)>0) and (strlen($lead_id)<12) and (strlen($field_name)>0) )
				{
				$lead_search_SQL .= "where lead_id=$lead_id";
				$search_ready++;
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "lead_field_info INVALID SEARCH PARAMETERS";
				$data = "$user|$lead_id|$field_name";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
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

				$stmt="SELECT list_id,entry_list_id from $vicidial_list_table $lead_search_SQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$lead_exists = mysqli_num_rows($rslt);

				if ($lead_exists > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$lead_list_id =			$row[0];
					$lead_entry_list_id =	$row[1];

					$stmt="SELECT count(*) from vicidial_lists where list_id='$lead_list_id' $LOGallowed_campaignsSQL;";
					if ($DB) {$MAIN.="|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$list_exists =	$row[0];

					if ($list_exists > 0)
						{
						if ($custom_fields == 'Y')
							{
							$stmt="SELECT admin_hide_lead_data,admin_hide_phone_data,admin_cf_show_hidden from vicidial_users where user='$user';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$LOGadmin_hide_lead_data =		$row[0];
							$LOGadmin_hide_phone_data =		$row[1];
							$LOGadmin_cf_show_hidden =		$row[2];
							if ($DB) {echo "CF user: |$LOGadmin_hide_lead_data|$LOGadmin_hide_phone_data|$LOGadmin_cf_show_hidden|\n";}

							if (strlen($list_id)>1)
								{$lead_entry_list_id = $list_id;}

							if (preg_match("/cf_encrypt/",$active_modules))
								{
								$enc_fields=0;
								$stmt = "SELECT count(*) from vicidial_lists_fields where field_encrypt='Y' and list_id='$lead_entry_list_id';";
								$rslt=mysql_to_mysqli($stmt, $link);
								if ($DB) {echo "$stmt\n";}
								$enc_field_ct = mysqli_num_rows($rslt);
								if ($enc_field_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$enc_fields =	$row[0];
									}
								if ($enc_fields > 0)
									{
									$stmt = "SELECT field_label from vicidial_lists_fields where field_encrypt='Y' and list_id='$lead_entry_list_id';";
									$rslt=mysql_to_mysqli($stmt, $link);
									if ($DB) {echo "$stmt\n";}
									$enc_field_ct = mysqli_num_rows($rslt);
									$r=0;
									while ($enc_field_ct > $r)
										{
										$row=mysqli_fetch_row($rslt);
										$encrypt_list .= "$row[0],";
										$r++;
										}
									$encrypt_list = ",$encrypt_list";
									}
								if ($LOGadmin_cf_show_hidden < 1)
									{
									$hide_fields=0;
									$stmt = "SELECT count(*) from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$lead_entry_list_id';";
									$rslt=mysql_to_mysqli($stmt, $link);
									if ($DB) {echo "$stmt\n";}
									$hide_field_ct = mysqli_num_rows($rslt);
									if ($hide_field_ct > 0)
										{
										$row=mysqli_fetch_row($rslt);
										$hide_fields =	$row[0];
										}
									if ($hide_fields > 0)
										{
										$stmt = "SELECT field_label from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$lead_entry_list_id';";
										$rslt=mysql_to_mysqli($stmt, $link);
										if ($DB) {echo "$stmt\n";}
										$hide_field_ct = mysqli_num_rows($rslt);
										$r=0;
										while ($hide_field_ct > $r)
											{
											$row=mysqli_fetch_row($rslt);
											$hide_list .= "$row[0],";
											$r++;
											}
										$hide_list = ",$hide_list";
										}
									}
								}

							$stmt="SELECT $field_name from custom_$lead_entry_list_id $lead_search_SQL;";
							}
						else
							{
							$stmt="SELECT $field_name from $vicidial_list_table $lead_search_SQL;";
							}
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$field_exists = mysqli_num_rows($rslt);

						if ($field_exists > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$output =			$row[0];

							if ($enc_fields > 0)
								{
								$field_enc='';   $field_enc_all='';
								if ($DB) {echo "|$field_name|$encrypt_list|$hide_list|\n";}
								if ( (preg_match("/,$field_name,/",$encrypt_list)) and (strlen($output) > 0) )
									{
									if ($DB) {echo "DECRYPTING $field_name\n";}
									exec("../agc/aes.pl --decrypt --text=$output", $field_enc);
									$field_enc_ct = count($field_enc);
									$k=0;
									while ($field_enc_ct > $k)
										{
										$field_enc_all .= $field_enc[$k];
										$k++;
										}
									$field_enc_all = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
									$output = base64_decode($field_enc_all);
									}
								}
							if ( ( (preg_match("/,$field_name,/",$hide_list)) or ($LOGadmin_hide_lead_data > 0) ) and (strlen($output) > 0) )
								{
								$field_temp_val = $output;
								$output = preg_replace("/./",'X',$field_temp_val);
								}

							echo "$output";

							$result = 'SUCCESS';
							$data = "$user|$lead_id|$stage";
							$result_reason = "lead_field_info $output";

							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$result = 'ERROR';
							$result_reason = "lead_field_info LIST FIELD NOT FOUND";
							$data = "$user|$lead_id";
							echo "$result: $result_reason: $data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "lead_field_info LIST NOT FOUND";
						$data = "$user|$lead_id|$lead_list_id";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "lead_field_info LEAD NOT FOUND";
					$data = "$user|$lead_id";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			}
		}
	exit;
	}
################################################################################
### lead_field_info - pulls the value of one field of a lead
################################################################################




################################################################################
### lead_all_info - outputs all lead data for a lead(including custom fields)
################################################################################
if ($function == 'lead_all_info')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4','5') and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "lead_all_info USER DOES NOT HAVE PERMISSION TO GET LEAD INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$call_search_SQL='';
			$search_ready=0;
			$no_list_counter=0;
			$no_list_output='';
			$call_id = preg_replace("/\n|\r|\t| /",'',$call_id);

			if ( (strlen($lead_id) < 1) and ( (strlen($phone_number) < 6) or (strlen($phone_number) > 19) ) )
				{
				$result = 'ERROR';
				$result_reason = "lead_all_info LEAD NOT FOUND";
				$data = "$user|$lead_id|$phone_number";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT admin_viewable_groups,allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$LOGadmin_viewable_groups =		$row[0];
				$LOGallowed_campaigns =			$row[1];

				$LOGallowed_campaignsSQL='';
				if ( (!preg_match("/ALL-CAMPAIGNS/i",$LOGallowed_campaigns)) )
					{
					$LOGallowed_campaignsSQL = preg_replace('/\s-/i','',$LOGallowed_campaigns);
					$LOGallowed_campaignsSQL = preg_replace('/\s/i',"','",$LOGallowed_campaignsSQL);
					$LOGallowed_campaignsSQL = "and campaign_id IN('$LOGallowed_campaignsSQL')";
					}

				$searchSQL='';
				if ( (strlen($phone_number) >= 6) and (strlen($phone_number) <= 19) )
					{$searchSQL = "phone_number='$phone_number'";}
				if (strlen($lead_id) > 0)
					{$searchSQL = "lead_id='$lead_id'";}

				$DLset=0;
				$output='';
				if ($stage == 'csv')
					{$DL = ',';   $DLset++;}
				if ($stage == 'tab')
					{$DL = "\t";   $DLset++;}
				if ($stage == 'newline')
					{$DL = "\n";   $DLset++;}
				if ($stage == 'pipe')
					{$DL = '|';   $DLset++;}
				if ($DLset < 1)
					{$DL='|';   $stage='pipe';}

				$stmt="SELECT list_id,entry_list_id,lead_id from $vicidial_list_table where $searchSQL order by lead_id desc limit 1000;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$lead_exists = mysqli_num_rows($rslt);
				if ($DB) {echo "$lead_exists|$stmt\n";}
				$lead_loop_ct=0;
				$ARY_lead_list_id=array();
				$ARY_lead_entry_list_id=array();
				$ARY_lead_lead_id=array();

				while ($lead_exists > $lead_loop_ct)
					{
					$row=mysqli_fetch_row($rslt);
					$ARY_lead_list_id[$lead_loop_ct] =			$row[0];
					$ARY_lead_entry_list_id[$lead_loop_ct] =	$row[1];
					$ARY_lead_lead_id[$lead_loop_ct] =			$row[2];
					$lead_loop_ct++;
					}

				$lead_loop_ct=0;
				while ($lead_exists > $lead_loop_ct)
					{
					$lead_list_id =			$ARY_lead_list_id[$lead_loop_ct];
					$lead_entry_list_id =	$ARY_lead_entry_list_id[$lead_loop_ct];
					$lead_lead_id =			$ARY_lead_lead_id[$lead_loop_ct];
					# check for custom list override
					if (strlen($force_entry_list_id) > 0)
						{$lead_entry_list_id = $force_entry_list_id;}
					if ($DB) {echo "DEBUG: $lead_loop_ct|$lead_list_id|$lead_entry_list_id|$lead_lead_id|\n";}
	
					$stmt="SELECT count(*) from vicidial_lists where list_id='$lead_list_id' $LOGallowed_campaignsSQL;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$list_exists =	$row[0];
					if ($DB) {echo "DEBUG: $list_exists|$stmt|\n";}

					if ($list_exists > 0)
						{
						$CF_data_output='';
						$CF_header_output='';
						if ($custom_fields == 'Y')
							{
							$stmt="SELECT admin_hide_lead_data,admin_hide_phone_data,admin_cf_show_hidden from vicidial_users where user='$user';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$LOGadmin_hide_lead_data =		$row[0];
							$LOGadmin_hide_phone_data =		$row[1];
							$LOGadmin_cf_show_hidden =		$row[2];
							if ($DB) {echo "CF user: |$LOGadmin_hide_lead_data|$LOGadmin_hide_phone_data|$LOGadmin_cf_show_hidden|\n";}

							if (preg_match("/cf_encrypt/",$active_modules))
								{
								$enc_fields=0;
								$stmt = "SELECT count(*) from vicidial_lists_fields where field_encrypt='Y' and list_id='$lead_entry_list_id';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$enc_field_ct = mysqli_num_rows($rslt);
								if ($DB) {echo "$enc_field_ct|$stmt\n";}
								if ($enc_field_ct > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$enc_fields =	$row[0];
									}
								if ($enc_fields > 0)
									{
									$stmt = "SELECT field_label from vicidial_lists_fields where field_encrypt='Y' and list_id='$lead_entry_list_id';";
									$rslt=mysql_to_mysqli($stmt, $link);
									$enc_field_ct = mysqli_num_rows($rslt);
									if ($DB) {echo "$enc_field_ct|$stmt\n";}
									$r=0;
									while ($enc_field_ct > $r)
										{
										$row=mysqli_fetch_row($rslt);
										$encrypt_list .= "$row[0],";
										$r++;
										}
									$encrypt_list = ",$encrypt_list";
									}
								if ($LOGadmin_cf_show_hidden < 1)
									{
									$hide_fields=0;
									$stmt = "SELECT count(*) from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$lead_entry_list_id';";
									$rslt=mysql_to_mysqli($stmt, $link);
									$hide_field_ct = mysqli_num_rows($rslt);
									if ($DB) {echo "$hide_field_ct|$stmt\n";}
									if ($hide_field_ct > 0)
										{
										$row=mysqli_fetch_row($rslt);
										$hide_fields =	$row[0];
										}
									if ($hide_fields > 0)
										{
										$stmt = "SELECT field_label from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$lead_entry_list_id';";
										$rslt=mysql_to_mysqli($stmt, $link);
										$hide_field_ct = mysqli_num_rows($rslt);
										if ($DB) {echo "$hide_field_ct|$stmt\n";}
										$r=0;
										while ($hide_field_ct > $r)
											{
											$row=mysqli_fetch_row($rslt);
											$hide_list .= "$row[0],";
											$r++;
											}
										$hide_list = ",$hide_list";
										}
									}
								}
							$custom_fields_list='';
							$stmt = "SELECT field_label from vicidial_lists_fields where list_id='$lead_entry_list_id' and field_type NOT IN('DISPLAY','SCRIPT','SWITCH','BUTTON') and field_label NOT IN('lead_id','vendor_lead_code','source_id','list_id','gmt_offset_now','called_since_last_reset','phone_code','phone_number','title','first_name','middle_initial','last_name','address1','address2','address3','city','state','province','postal_code','country_code','gender','date_of_birth','alt_phone','email','security_phrase','comments','called_count','last_local_call_time','rank','owner') order by field_rank,field_order;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$cust_field_ct = mysqli_num_rows($rslt);
							if ($DB) {echo "$cust_field_ct|$stmt\n";}
							$r=0;
							while ($cust_field_ct > $r)
								{
								$row=mysqli_fetch_row($rslt);
								if ($r > 0) {$custom_fields_list .= ",";}
								$custom_fields_list .= "$row[0]";
								$custom_fields_ARY[$r] = $row[0];
								$r++;
								}

							$stmt="SELECT $custom_fields_list from custom_$lead_entry_list_id where lead_id='$lead_lead_id';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$custom_fields_found = mysqli_num_rows($rslt);
							if ($DB) {echo "$custom_fields_found|$stmt\n";}
							if ($custom_fields_found > 0)
								{
								$row=mysqli_fetch_row($rslt);
								$rc=0;
								while ($r > $rc)
									{
									$temp_CF_value =	$row[$rc];
									$temp_CP_label =	$custom_fields_ARY[$rc];

									if ($enc_fields > 0)
										{
										$field_enc='';   $field_enc_all='';
										if ($DB) {echo "|$temp_CP_label|$encrypt_list|$hide_list|\n";}
										if ( (preg_match("/,$temp_CP_label,/",$encrypt_list)) and (strlen($temp_CF_value) > 0) )
											{
											if ($DB) {echo "DECRYPTING $temp_CP_label\n";}
											exec("../agc/aes.pl --decrypt --text=$temp_CF_value", $field_enc);
											$field_enc_ct = count($field_enc);
											$k=0;
											while ($field_enc_ct > $k)
												{
												$field_enc_all .= $field_enc[$k];
												$k++;
												}
											$field_enc_all = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
											$temp_CF_value = base64_decode($field_enc_all);
											}
										}
									if ( ( (preg_match("/,$temp_CP_label,/",$hide_list)) or ($LOGadmin_hide_lead_data > 0) ) and (strlen($temp_CF_value) > 0) )
										{
										$field_temp_val = $temp_CF_value;
										$temp_CF_value = preg_replace("/./",'X',$field_temp_val);
										}
									$CF_data_output .=		"$DL$temp_CF_value";
									$CF_header_output .=	"$DL$temp_CP_label";
									$rc++;
									}
								}
							}
							if ($header == 'YES')
								{
							$output .= 'status' . $DL . 'user' . $DL . 'vendor_lead_code' . $DL . 'source_id' . $DL . 'list_id' . $DL . 'gmt_offset_now' . $DL . 'phone_code' . $DL . 'phone_number' . $DL . 'title' . $DL . 'first_name' . $DL . 'middle_initial' . $DL . 'last_name' . $DL . 'address1' . $DL . 'address2' . $DL . 'address3' . $DL . 'city' . $DL . 'state' . $DL . 'province' . $DL . 'postal_code' . $DL . 'country_code' . $DL . 'gender' . $DL . 'date_of_birth' . $DL . 'alt_phone' . $DL . 'email' . $DL . 'security_phrase' . $DL . 'comments' . $DL . 'called_count' . $DL . 'last_local_call_time' . $DL . 'rank' . $DL . 'owner' . $DL . 'entry_list_id' . $DL . 'lead_id' . $CF_header_output . "\n";
								}

						$stmt="SELECT status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id from $vicidial_list_table where lead_id='$lead_lead_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$standard_field_exists = mysqli_num_rows($rslt);
						if ($standard_field_exists > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$LEADstatus =				$row[0];
							$LEADuser =					$row[1];
							$LEADvendor_lead_code =		$row[2];
							$LEADsource_id =			$row[3];
							$LEADlist_id =				$row[4];
							$LEADgmt_offset_now =		$row[5];
							$LEADphone_code =			$row[6];
							$LEADphone_number =			$row[7];
							$LEADtitle =				$row[8];
							$LEADfirst_name =			$row[9];
							$LEADmiddle_initial =		$row[10];
							$LEADlast_name =			$row[11];
							$LEADaddress1 =				$row[12];
							$LEADaddress2 =				$row[13];
							$LEADaddress3 =				$row[14];
							$LEADcity =					$row[15];
							$LEADstate =				$row[16];
							$LEADprovince =				$row[17];
							$LEADpostal_code =			$row[18];
							$LEADcountry_code =			$row[19];
							$LEADgender =				$row[20];
							$LEADdate_of_birth =		$row[21];
							$LEADalt_phone =			$row[22];
							$LEADemail =				$row[23];
							$LEADsecurity_phrase =		$row[24];
							$LEADcomments =				$row[25];
							$LEADcalled_count =			$row[26];
							$LEADlast_local_call_time = $row[27];
							$LEADrank =					$row[28];
							$LEADowner =				$row[29];
							$LEADentry_list_id =		$row[30];

							$output .= "$LEADstatus$DL$LEADuser$DL$LEADvendor_lead_code$DL$LEADsource_id$DL$LEADlist_id$DL$LEADgmt_offset_now$DL$LEADphone_code$DL$LEADphone_number$DL$LEADtitle$DL$LEADfirst_name$DL$LEADmiddle_initial$DL$LEADlast_name$DL$LEADaddress1$DL$LEADaddress2$DL$LEADaddress3$DL$LEADcity$DL$LEADstate$DL$LEADprovince$DL$LEADpostal_code$DL$LEADcountry_code$DL$LEADgender$DL$LEADdate_of_birth$DL$LEADalt_phone$DL$LEADemail$DL$LEADsecurity_phrase$DL$LEADcomments$DL$LEADcalled_count$DL$LEADlast_local_call_time$DL$LEADrank$DL$LEADowner$DL$LEADentry_list_id$DL$lead_lead_id$CF_data_output\n";

						#	echo "$output";

							$result = 'SUCCESS';
							$data = "$user|$lead_id|$phone_number|$stage";
							$result_reason = "lead_all_info $output";

							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$result = 'ERROR';
							$result_reason = "lead_all_info LEAD NOT FOUND";
							$data = "$user|$lead_id|$phone_number";
							echo "$result: $result_reason: $data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					else
						{
						$no_list_counter++;
						$no_list_output = "$lead_list_id";
						}
					if ( ($list_exists < 1) and ($api_list_restrict > 0) )
						{
						$result = 'ERROR';
						$result_reason = "lead_all_info LIST NOT FOUND";
						$data = "$user|$lead_id|$phone_number";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					#	exit;
						}
					$lead_loop_ct++;
					}
				if ($lead_exists < 1)
					{
					$result = 'ERROR';
					$result_reason = "lead_all_info LEAD NOT FOUND";
					$data = "$user|$lead_id|$phone_number";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				if (strlen($output) > 10)
					{echo "$output";}
				else
					{
					if ($no_list_counter > 0)
						{
						$result = 'ERROR';
						$result_reason = "lead_all_info LIST NOT FOUND";
						$data = "$user|$lead_id|$phone_number|$no_list_output";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
				}
			}
		}
			}
		}
	exit;
	}
################################################################################
### lead_all_info - outputs all lead data for a lead(including custom fields)
################################################################################





################################################################################
### lead_status_search - displays all field values of all leads that match the status and call date in request
################################################################################
if ($function == 'lead_status_search')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4') and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "lead_status_search USER DOES NOT HAVE PERMISSION TO GET LEAD INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$lead_search_SQL='';
			$ANDlead_search_SQL='';
			$search_ready=0;

			if ( (strlen($lead_id) > 0) or ( (strlen($status) > 0) and (strlen($date) < 11) and (strlen($date) > 9) ) )
				{
				if ( (strlen($status) > 0) and (strlen($date) < 11) and (strlen($date) > 9) )
					{
					$lead_search_SQL .= "where status='$status' and call_date >= \"$date 00:00:00\" and call_date <= \"$date 23:59:59\" order by call_date desc limit 1000";
					$ANDlead_search_SQL .= "and status='$status' and call_date >= \"$date 00:00:00\" and call_date <= \"$date 23:59:59\" order by call_date desc limit 1000";
					$search_ready++;
					}
				if ( (strlen($lead_id) > 0) and ($search_ready < 1) )
					{
					$lead_search_SQL .= "where lead_id=$lead_id";
					$ANDlead_search_SQL .= "and lead_id=$lead_id";
					$search_ready++;
					}
				}
			if ($search_ready < 1)
				{
				$result = 'ERROR';
				$result_reason = "lead_status_search INVALID SEARCH PARAMETERS";
				$data = "$user|$status|$date|$lead_id";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
				if ($DB) {$MAIN.="|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
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

				if ($custom_fields == 'Y')
					{
					$stmt="SELECT admin_hide_lead_data,admin_hide_phone_data,admin_cf_show_hidden from vicidial_users where user='$user';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$LOGadmin_hide_lead_data =		$row[0];
					$LOGadmin_hide_phone_data =		$row[1];
					$LOGadmin_cf_show_hidden =		$row[2];
					if ($DB) {echo "CF user: |$LOGadmin_hide_lead_data|$LOGadmin_hide_phone_data|$LOGadmin_cf_show_hidden|\n";}
					}

				### gather outbound calls
				$stmt="SELECT distinct(lead_id) from vicidial_log $lead_search_SQL $LOGallowed_campaignsSQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$leads_exist = mysqli_num_rows($rslt);
				$x=0;
				$lead_id_list="'0'";
				$export_lead_id=array();
				while ($leads_exist > $x)
					{
					$row=mysqli_fetch_row($rslt);
					$export_lead_id[$x] =			$row[0];
					$lead_id_list .= ",'$row[0]'";
					$x++;
					}

				### gather inbound calls
				$stmt="SELECT distinct(lead_id) from vicidial_closer_log where lead_id NOT IN($lead_id_list) $ANDlead_search_SQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$leads_exist = mysqli_num_rows($rslt);
				$y=0;
				while ($leads_exist > $y)
					{
					$row=mysqli_fetch_row($rslt);
					$export_lead_id[$x] =			$row[0];
					$x++;
					$y++;
					}

				$leads_exist = $x;
				$i=0;
				$LP=0;
				$output='';
				while ($leads_exist > $i)
					{
					$stmt="SELECT status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id from $vicidial_list_table where lead_id='$export_lead_id[$i]';";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$lead_to_list = mysqli_num_rows($rslt);
					if ($lead_to_list > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$LEADstatus =				$row[0];
						$LEADuser =					$row[1];
						$LEADvendor_lead_code =		$row[2];
						$LEADsource_id =			$row[3];
						$LEADlist_id =				$row[4];
						$LEADgmt_offset_now =		$row[5];
						$LEADphone_code =			$row[6];
						$LEADphone_number =			$row[7];
						$LEADtitle =				$row[8];
						$LEADfirst_name =			$row[9];
						$LEADmiddle_initial =		$row[10];
						$LEADlast_name =			$row[11];
						$LEADaddress1 =				$row[12];
						$LEADaddress2 =				$row[13];
						$LEADaddress3 =				$row[14];
						$LEADcity =					$row[15];
						$LEADstate =				$row[16];
						$LEADprovince =				$row[17];
						$LEADpostal_code =			$row[18];
						$LEADcountry_code =			$row[19];
						$LEADgender =				$row[20];
						$LEADdate_of_birth =		$row[21];
						$LEADalt_phone =			$row[22];
						$LEADemail =				$row[23];
						$LEADsecurity_phrase =		$row[24];
						$LEADcomments =				$row[25];
						$LEADcalled_count =			$row[26];
						$LEADlast_local_call_time = $row[27];
						$LEADrank =					$row[28];
						$LEADowner =				$row[29];
						$LEADentry_list_id =		$row[30];

						$allowed_list=1;
						if ( (strlen($LOGallowed_campaignsSQL) > 3) and ($api_list_restrict > 0) )
							{
							$stmt="SELECT count(*) from vicidial_lists where list_id='$LEADlist_id' $LOGallowed_campaignsSQL;";
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$allowed_list=$row[0];
							if ($allowed_list < 1)
								{
								if ($DB > 0) {echo "DEBUG: NOT AN ALLOWED LIST: $LEADlist_id|$export_lead_id[$i]\n";}
								}
							}

						if ($allowed_list > 0)
							{
							$output .= ";---------- START OF RECORD ". ($LP + 1) ." ----------\n";
							$output .= "lead_id => $export_lead_id[$i]\n";
							$output .= "status => $LEADstatus\n";
							$output .= "user => $LEADuser\n";
							$output .= "vendor_lead_code => $LEADvendor_lead_code\n";
							$output .= "source_id => $LEADsource_id\n";
							$output .= "list_id => $LEADlist_id\n";
							$output .= "gmt_offset_now => $LEADgmt_offset_now\n";
							$output .= "phone_code => $LEADphone_code\n";
							$output .= "phone_number => $LEADphone_number\n";
							$output .= "title => $LEADtitle\n";
							$output .= "first_name => $LEADfirst_name\n";
							$output .= "middle_initial => $LEADmiddle_initial\n";
							$output .= "last_name => $LEADlast_name\n";
							$output .= "address1 => $LEADaddress1\n";
							$output .= "address2 => $LEADaddress2\n";
							$output .= "address3 => $LEADaddress3\n";
							$output .= "city => $LEADcity\n";
							$output .= "state => $LEADstate\n";
							$output .= "province => $LEADprovince\n";
							$output .= "postal_code => $LEADpostal_code\n";
							$output .= "country_code => $LEADcountry_code\n";
							$output .= "gender => $LEADgender\n";
							$output .= "date_of_birth => $LEADdate_of_birth\n";
							$output .= "alt_phone => $LEADalt_phone\n";
							$output .= "email => $LEADemail\n";
							$output .= "security_phrase => $LEADsecurity_phrase\n";
							$output .= "comments => $LEADcomments\n";
							$output .= "called_count => $LEADcalled_count\n";
							$output .= "last_local_call_time => $LEADlast_local_call_time\n";
							$output .= "rank => $LEADrank\n";
							$output .= "owner => $LEADowner\n";
							$output .= "entry_list_id => $LEADentry_list_id\n";

							if ($custom_fields == 'Y')
								{
								$CF_list_id = $LEADlist_id;
								if ($LEADentry_list_id > 99)
									{$CF_list_id = $LEADentry_list_id;}
								$stmt="SHOW TABLES LIKE \"custom_$CF_list_id\";";
								if ($DB>0) {echo "$stmt";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$tablecount_to_print = mysqli_num_rows($rslt);
								if ($tablecount_to_print > 0)
									{
									$column_list='';
									$encrypt_list='';
									$hide_list='';
									$stmt = "DESCRIBE custom_$CF_list_id;";
									$rslt=mysql_to_mysqli($stmt, $link);
									if ($DB) {echo "$stmt\n";}
									$columns_ct = mysqli_num_rows($rslt);
									$u=0;
									while ($columns_ct > $u)
										{
										$row=mysqli_fetch_row($rslt);
										$column =	$row[0];
										$column_list .= "$row[0],";
										$u++;
										}
									if ($columns_ct > 1)
										{
										$column_list = preg_replace("/lead_id,/",'',$column_list);
										$column_list = preg_replace("/,$/",'',$column_list);
										$column_list_array = explode(',',$column_list);
										if (preg_match("/cf_encrypt/",$active_modules))
											{
											$enc_fields=0;
											$stmt = "SELECT count(*) from vicidial_lists_fields where field_encrypt='Y' and list_id='$CF_list_id';";
											$rslt=mysql_to_mysqli($stmt, $link);
											if ($DB) {echo "$stmt\n";}
											$enc_field_ct = mysqli_num_rows($rslt);
											if ($enc_field_ct > 0)
												{
												$row=mysqli_fetch_row($rslt);
												$enc_fields =	$row[0];
												}
											if ($enc_fields > 0)
												{
												$stmt = "SELECT field_label from vicidial_lists_fields where field_encrypt='Y' and list_id='$CF_list_id';";
												$rslt=mysql_to_mysqli($stmt, $link);
												if ($DB) {echo "$stmt\n";}
												$enc_field_ct = mysqli_num_rows($rslt);
												$r=0;
												while ($enc_field_ct > $r)
													{
													$row=mysqli_fetch_row($rslt);
													$encrypt_list .= "$row[0],";
													$r++;
													}
												$encrypt_list = ",$encrypt_list";
												}
											if ($LOGadmin_cf_show_hidden < 1)
												{
												$hide_fields=0;
												$stmt = "SELECT count(*) from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$CF_list_id';";
												$rslt=mysql_to_mysqli($stmt, $link);
												if ($DB) {echo "$stmt\n";}
												$hide_field_ct = mysqli_num_rows($rslt);
												if ($hide_field_ct > 0)
													{
													$row=mysqli_fetch_row($rslt);
													$hide_fields =	$row[0];
													}
												if ($hide_fields > 0)
													{
													$stmt = "SELECT field_label from vicidial_lists_fields where field_show_hide!='DISABLED' and list_id='$CF_list_id';";
													$rslt=mysql_to_mysqli($stmt, $link);
													if ($DB) {echo "$stmt\n";}
													$hide_field_ct = mysqli_num_rows($rslt);
													$r=0;
													while ($hide_field_ct > $r)
														{
														$row=mysqli_fetch_row($rslt);
														$hide_list .= "$row[0],";
														$r++;
														}
													$hide_list = ",$hide_list";
													}
												}
											}
										$stmt = "SELECT $column_list from custom_$CF_list_id where lead_id='$export_lead_id[$i]' limit 1;";
										$rslt=mysql_to_mysqli($stmt, $link);
										if ($DB) {echo "$stmt\n";}
										$customfield_ct = mysqli_num_rows($rslt);
										if ($customfield_ct > 0)
											{
											$row=mysqli_fetch_row($rslt);
											$t=0;
											while ($columns_ct >= $t)
												{
												if ($enc_fields > 0)
													{
													$field_enc='';   $field_enc_all='';
													if ($DB) {echo "|$column_list|$encrypt_list|\n";}
													if ( (preg_match("/,$column_list_array[$t],/",$encrypt_list)) and (strlen($row[$t]) > 0) )
														{
														exec("../agc/aes.pl --decrypt --text=$row[$t]", $field_enc);
														$field_enc_ct = count($field_enc);
														$k=0;
														while ($field_enc_ct > $k)
															{
															$field_enc_all .= $field_enc[$k];
															$k++;
															}
														$field_enc_all = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
														$row[$t] = base64_decode($field_enc_all);
														}
													}
												if ( (preg_match("/,$column_list_array[$t],/",$hide_list)) and (strlen($row[$t]) > 0) )
													{
													$field_temp_val = $row[$t];
													$row[$t] = preg_replace("/./",'X',$field_temp_val);
													}
												### PARSE TAB CHARACTERS FROM THE DATA ITSELF
												$row[$t]=preg_replace('/\t/', ' -- ', $row[$t]);
												$row[$t]=preg_replace('/\n|\r\n/', '!N', $row[$t]);
												$temp_custom_data = $column_list_array[$t]." => ".$row[$t];
												if (strlen($column_list_array[$t]) > 0)
													{$output .= "$temp_custom_data\n";}
												$t++;
												}
											}
										}
									}
								}
							$LP++;
						#	$output .= ";---------- END OF RECORD $LP ----------\n";
							}
						}
					$i++;
					}
				if ($LP < 1)
					{
					$result = 'ERROR';
					$result_reason = "lead_status_search NO RESULTS FOUND";
					$data = "$user|$lead_id|$status|$call_date";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					echo "$output";

					$result = 'SUCCESS';
					$data = "$user|$lead_id|$stage";
					$result_reason = "lead_status_search $output";

					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		}
	exit;
	}
################################################################################
### lead_status_search - displays all field values of all leads that match the status and call date in request
################################################################################





################################################################################
### lead_search - searches for a lead in the vicidial_list table
################################################################################
if ($function == 'lead_search')
	{
	$list_id = preg_replace('/[^0-9]/','',$list_id);
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4','5') and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$modify_leads=$row[0];

		if ($modify_leads < 1)
			{
			$result = 'ERROR';
			$result_reason = "lead_search USER DOES NOT HAVE PERMISSION TO SEARCH FOR LEADS";
			echo "$result: $result_reason: |$user|$modify_leads|\n";
			$data = "$modify_leads";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$search_SQL='';
			$lead_id_SQL='';
			$vendor_lead_code_SQL='';
			$phone_number_SQL='';
			$find_lead_id=0;
			$find_vendor_lead_code=0;
			$find_phone_number=0;
			$find_lead_idARY=array();
			$find_vendor_lead_codeARY=array();
			$find_phone_numberARY=array();
			$find_vendor_lead_codeARYx=array();
			$find_phone_numberARYx=array();
			if ( (strlen($search_location)<4) or ($list_id < 99) or (strlen($list_id)<3) )
				{$search_location='SYSTEM';}
			if (strlen($search_method)<6)
				{$search_method='PHONE_NUMBER';}
			if (strlen($records)<1)
				{$records='1000';}
			if (strlen($force_entry_list_id) > 1)
				{$entry_list_id = $force_entry_list_id;}
			if ( (preg_match("/LEAD_ID/",$search_method)) and (strlen($lead_id)>0) )
				{
				$find_lead_id=1;
				$lead_id_SQL = "lead_id IN($lead_ids)";
				$find_lead_idARY = explode(',',$lead_ids);
				}
			if ( (preg_match("/VENDOR_LEAD_CODE/",$search_method)) and (strlen($vendor_lead_code)>0) )
				{
				$find_vendor_lead_code=1;
				$vendor_lead_code_SQL='';
				$find_vendor_lead_codeARY = explode(',',$vendor_lead_code);
				$VLC_count = count($find_vendor_lead_codeARY);
				if ($VLC_count > 0)
					{
					$tc=0;
					$pc=0;
					while ($tc < $VLC_count)
						{
						if (strlen($find_vendor_lead_codeARY[$tc]) > 0)
							{
							if ($pc > 0) {$vendor_lead_code_SQL .= ",";}
							$vendor_lead_code_SQL .= "\"$find_vendor_lead_codeARY[$tc]\"";
							$find_vendor_lead_codeARYx[$pc] = $find_vendor_lead_codeARY[$tc];
							$pc++;
							}
						$tc++;
						}
					if (strlen($vendor_lead_code_SQL) > 2)
						{$vendor_lead_code_SQL = "vendor_lead_code IN($vendor_lead_code_SQL)";}
					}
				}
			if ( (preg_match("/PHONE_NUMBER/",$search_method)) and (strlen($phone_number)>5) )
				{
				$find_phone_number=1;
				$phone_number_SQL='';
				$find_phone_numberARY = explode(',',$phone_number);
				$PN_count = count($find_phone_numberARY);
				if ($PN_count > 0)
					{
					$tc=0;
					$pc=0;
					while ($tc < $PN_count)
						{
						if ( (strlen($find_phone_numberARY[$tc]) > 5) and (strlen($find_phone_numberARY[$tc])<19) )
							{
							if ($pc > 0) {$phone_number_SQL .= ",";}
							$phone_number_SQL .= "'$find_phone_numberARY[$tc]'";
							$find_phone_numberARYx[$pc] = $find_phone_numberARY[$tc];
							$pc++;
							}
						$tc++;
						}
					if (strlen($phone_number_SQL) > 2)
						{$phone_number_SQL = "phone_number IN($phone_number_SQL)";}
					}
				}
			if ( ($find_lead_id<1) and ($find_vendor_lead_code<1) and ($find_phone_number<1) )
				{
				$result = 'ERROR';
				$result_reason = "lead_search NO VALID SEARCH METHOD";
				$data = "$search_method|$lead_id($find_lead_id)|$vendor_lead_code($find_vendor_lead_code)|$phone_number($find_phone_number)";
				echo "$result: $result_reason - $user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				if ( ($api_list_restrict > 0) and ($list_id >= 99) )
					{
					if (!preg_match("/ $list_id /",$allowed_lists))
						{
						$result = 'ERROR';
						$result_reason = "lead_search NOT AN ALLOWED LIST ID";
						$data = "$phone_number|$list_id";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}

				if (preg_match("/CAMPAIGN/i",$search_location)) # find lists within campaign
					{
					$stmt="SELECT campaign_id from vicidial_lists where list_id='$list_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$ci_recs = mysqli_num_rows($rslt);
					if ($ci_recs > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$search_camp =	$row[0];

						$stmt="select list_id from vicidial_lists where campaign_id='$search_camp';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$li_recs = mysqli_num_rows($rslt);
						if ($li_recs > 0)
							{
							$L=0;
							while ($li_recs > $L)
								{
								$row=mysqli_fetch_row($rslt);
								$search_lists .=	"'$row[0]',";
								$L++;
								}
							$search_lists = preg_replace('/,$/i', '',$search_lists);
							$search_SQL = "and list_id IN($search_lists)";
							}
						}
					}
				if (preg_match("/LIST/i",$search_location)) # search check within list
					{
					$search_SQL = "and list_id='$list_id'";
					}

				$search_found=0;
				$search_key=array();
				$search_lead_id=array();
				$search_lead_list=array();
				$search_entry_list=array();
				$search_phone_number=array();
				$search_phone_code=array();
				if ($find_lead_id > 0) # search for the lead_id
					{
					$stmt="SELECT lead_id,list_id,entry_list_id,phone_number,phone_code from $vicidial_list_table where $lead_id_SQL $search_SQL order by lead_id desc limit $records;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($DB>0) {echo "DEBUG: Checking for lead_id - $lead_id|$pc_recs|$stmt|\n";}
					$n=0;
					while ($pc_recs > $n)
						{
						$row=mysqli_fetch_row($rslt);
						$search_key[$search_found] =			$row[0];
						$search_lead_id[$search_found] =		$row[0];
						$search_lead_list[$search_found] =		$row[1];
						$search_entry_list[$search_found] =		$row[2];
						$search_phone_number[$search_found] =	$row[3];
						$search_phone_code[$search_found] =		$row[4];
						if (strlen($force_entry_list_id) > 1)
							{$search_entry_list[$search_found] = $force_entry_list_id;}
						$n++;
						$search_found++;
						}
					}
				if ( ($find_vendor_lead_code > 0) and ($search_found < 1) ) # search for the vendor_lead_code
					{
					$stmt="SELECT lead_id,list_id,entry_list_id,phone_number,phone_code,vendor_lead_code from $vicidial_list_table where $vendor_lead_code_SQL $search_SQL order by lead_id desc limit $records;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($DB>0) {echo "DEBUG: Checking for vendor_lead_code - $vendor_lead_code|$pc_recs|$stmt|\n";}
					$n=0;
					while ($pc_recs > $n)
						{
						$row=mysqli_fetch_row($rslt);
						$search_key[$search_found] =			$row[5];
						$search_lead_id[$search_found] =		$row[0];
						$search_lead_list[$search_found] =		$row[1];
						$search_entry_list[$search_found] =		$row[2];
						$search_phone_number[$search_found] =	$row[3];
						$search_phone_code[$search_found] =		$row[4];
						if (strlen($force_entry_list_id) > 1)
							{$search_entry_list[$search_found] = $force_entry_list_id;}
						$n++;
						$search_found++;
						}
					}
				if ( ($find_phone_number > 0) and ($search_found < 1) ) # search for the phone_number
					{
					$stmt="SELECT lead_id,list_id,entry_list_id,phone_number,phone_code from $vicidial_list_table where $phone_number_SQL $search_SQL order by lead_id desc limit $records;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($DB>0) {echo "DEBUG: Checking for phone_number - $phone_number|$pc_recs|$stmt|\n";}
					$n=0;
					while ($pc_recs > $n)
						{
						$row=mysqli_fetch_row($rslt);
						$search_key[$search_found] =			$row[3];
						$search_lead_id[$search_found] =		$row[0];
						$search_lead_list[$search_found] =		$row[1];
						$search_entry_list[$search_found] =		$row[2];
						$search_phone_number[$search_found] =	$row[3];
						$search_phone_code[$search_found] =		$row[4];
						if (strlen($force_entry_list_id) > 1)
							{$search_entry_list[$search_found] = $force_entry_list_id;}
						$n++;
						$search_found++;
						}
					}
				### END searching ###

				if ($search_found > 0)
					{
					$output='';
					if ($header == 'YES')
						{
						$output .= 'search_key' . $DL . 'records_found' . $DL . 'lead_ids' . "\n";
						}
					$PH_search = count($find_phone_numberARYx);
					$SK_count = count($search_key);
					if ($DB > 0) {echo "Results found, preparing output: |$search_found|$PH_search|$SK_count|\n";}
					$tc=0;
					while ($tc < $PH_search)
						{
						$rc=0;
						$temp_match=0;
						$temp_lead_ids='';
						while ($rc < $SK_count)
							{
							if ($find_phone_numberARYx[$tc] == $search_key[$rc])
								{
								$temp_match++;
								if ($temp_match > 1) {$temp_lead_ids .= "-";}
								$temp_lead_ids .= "$search_lead_id[$rc]";
								}
							$rc++;
							}
						$output .= "$find_phone_numberARYx[$tc]|$temp_match|$temp_lead_ids\n";
						$tc++;
						}

					$PH_search = count($find_vendor_lead_codeARYx);
					$SK_count = count($search_key);
					$tc=0;
					while ($tc < $PH_search)
						{
						$rc=0;
						$temp_match=0;
						$temp_lead_ids='';
						while ($rc < $SK_count)
							{
							if ($find_vendor_lead_codeARYx[$tc] == $search_key[$rc])
								{
								$temp_match++;
								if ($temp_match > 1) {$temp_lead_ids .= "-";}
								$temp_lead_ids .= "$search_lead_id[$rc]";
								}
							$rc++;
							}
						$output .= "$find_vendor_lead_codeARYx[$tc]|$temp_match|$temp_lead_ids\n";
						$tc++;
						}

					$result = 'SUCCESS';
					$result_reason = "lead_search LEADS FOUND IN THE SYSTEM";
					$data = "$lead_id|$vendor_lead_code|$phone_number|$search_found";
					echo "$output";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "lead_search NO MATCHES FOUND IN THE SYSTEM";
					$data = "$lead_id|$vendor_lead_code|$phone_number";
					echo "$result: $result_reason: |$user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

					exit;
					}
				}
			}
		exit;
		}
	}
################################################################################
### END lead_search
################################################################################





################################################################################
### update_log_entry - updates the status of a log entry
################################################################################
if ($function == 'update_log_entry')
	{
	if (strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4') and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_log_entry USER DOES NOT HAVE PERMISSION TO UPDATE LOGS";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$search_SQL='';
			$search_ready=0;
			if ( (strlen($group)<2) or (strlen($call_id)<8) or (strlen($status)<8) )
				{
				$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$group';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$inbound_call=$row[0];

				if ($inbound_call < 1)
					{
					$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$group';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$outbound_call=$row[0];
					}

				if ( ($inbound_call < 1) and ($outbound_call < 1) )
					{
					$result = 'ERROR';
					$result_reason = "update_log_entry GROUP NOT FOUND";
					$data = "$user|$group";
					echo "$result: $result_reason: $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					if (preg_match("/\./",$call_id))
						{
						if ($inbound_call > 0)
							{$stmt="SELECT lead_id,status,closecallid from vicidial_closer_log where campaign_id='$group' and uniqueid='$call_id' order by closecallid desc limit 1;";}
						else
							{$stmt="SELECT lead_id,status,uniqueid from vicidial_log where campaign_id='$group' and uniqueid='$call_id';";}
						}
					else
						{
						$lead_id = substr($call_id, -10);
						$lead_id = ($lead_id + 0);

						if ($inbound_call > 0)
							{$stmt="SELECT lead_id,status,closecallid from vicidial_closer_log where campaign_id='$group' and lead_id=$lead_id order by closecallid desc limit 1;";}
						else
							{$stmt="SELECT lead_id,status,uniqueid from vicidial_log where campaign_id='$group' and lead_id=$lead_id order by call_date desc limit 1;";}
						}
					$rslt=mysql_to_mysqli($stmt, $link);
					$found_recs = mysqli_num_rows($rslt);
					if ($found_recs > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$lead_id =		$row[0];
						$old_status =	$row[1];
						$uniqueid =		$row[2];
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "update_log_entry NO RECORDS FOUND";
						$data = "$user|$group|$call_id|$status";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}

					if ($inbound_call > 0)
						{$stmt="UPDATE vicidial_closer_log SET status='$status' where campaign_id='$group' and closecallid='$uniqueid' order by closecallid desc limit 1;";}
					else
						{$stmt="UPDATE vicidial_log SET status='$status' where campaign_id='$group' and uniqueid='$uniqueid';";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$update_count = mysqli_affected_rows($link);
					if ($update_count > 0)
						{
						$result = 'SUCCESS';
						$result_reason = "update_log_entry RECORD HAS BEEN UPDATED";
						$data = "$user|$group|$call_id|$status|$old_status|$uniqueid|$affected_rows";
						echo "$result: $result_reason - $user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = 'ERROR';
						$result_reason = "update_log_entry NO RECORDS UPDATED";
						$data = "$user|$group|$call_id|$status|$old_status|$uniqueid";
						echo "$result: $result_reason: $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				}
			else
				{
				$result = 'ERROR';
				$result_reason = "update_log_entry INVALID SEARCH PARAMETERS";
				$data = "$user|$group|$call_id|$status";
				echo "$result: $result_reason: $data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			}
		}
	exit;
	}
################################################################################
### END update_log_entry
################################################################################





################################################################################
### add_lead - inserts a lead into the vicidial_list table
################################################################################
if ($function == 'add_lead')
	{
	$list_id = preg_replace('/[^0-9]/','',$list_id);
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4') and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$modify_leads=$row[0];

		if ($modify_leads < 1)
			{
			$result = 'ERROR';
			$result_reason = "add_lead USER DOES NOT HAVE PERMISSION TO ADD LEADS TO THE SYSTEM";
			echo "$result: $result_reason: |$user|$modify_leads|\n";
			$data = "$modify_leads";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if ($api_list_restrict > 0)
				{
				if (!preg_match("/ $list_id /",$allowed_lists))
					{
					$result = 'ERROR';
					$result_reason = "add_lead NOT AN ALLOWED LIST ID";
					$data = "$phone_number|$list_id";
					echo "$result: $result_reason - $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			if (preg_match("/Y/i",$list_exists_check))
				{
				$stmt="SELECT count(*) from vicidial_lists where list_id='$list_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($DB>0) {echo "DEBUG: add_lead list_exists_check query - $row[0]|$stmt\n";}
				$list_exists_count = $row[0];
				if ($list_exists_count < 1)
					{
					$result = 'ERROR';
					$result_reason = "add_lead NOT A DEFINED LIST ID, LIST EXISTS CHECK ENABLED";
					$data = "$phone_number|$list_id";
					echo "$result: $result_reason - $data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			if (strlen($gender)<1) {$gender='U';}
			if (strlen($rank)<1) {$rank='0';}
			if (strlen($list_id)<3) {$list_id='999';}
			if (strlen($phone_code)<1) {$phone_code='1';}
			if ( ($nanpa_ac_prefix_check == 'Y') or (preg_match("/NANPA/i",$tz_method)) )
				{
				$stmt="SELECT count(*) from vicidial_nanpa_prefix_codes;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$vicidial_nanpa_prefix_codes_count = $row[0];
				if ($vicidial_nanpa_prefix_codes_count < 10)
					{
					$nanpa_ac_prefix_check='N';
					$tz_method = preg_replace("/NANPA/",'',$tz_method);

					$result = 'NOTICE';
					$result_reason = "add_lead NANPA options disabled, NANPA prefix data not loaded";
					echo "$result: $result_reason - $vicidial_nanpa_prefix_codes_count|$user\n";
					$data = "$inserted_alt_phones|$lead_id";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}

			$valid_number=1;
			if ( (strlen($phone_number)<6) || (strlen($phone_number)>16) )
				{
				$valid_number=0;
				$result_reason = "add_lead INVALID PHONE NUMBER LENGTH";
				}
			if ( ($usacan_prefix_check=='Y') and ($valid_number > 0) )
				{
				$USprefix = 	substr($phone_number, 3, 1);
				if ($DB>0) {echo "DEBUG: add_lead prefix check - $USprefix|$phone_number\n";}
				if ($USprefix < 2)
					{
					$valid_number=0;
					$result_reason = "add_lead INVALID PHONE NUMBER PREFIX";
					}
				}
			if ( ($usacan_areacode_check=='Y') and ($valid_number > 0) )
				{
				$phone_areacode = substr($phone_number, 0, 3);
				$stmt = "select count(*) from vicidial_phone_codes where areacode='$phone_areacode' and country_code='1';";
				if ($DB>0) {echo "DEBUG: add_lead areacode check query - $stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$valid_number=$row[0];
				if ( ($valid_number < 1) || (strlen($phone_number)>10) || (strlen($phone_number)<10) )
					{
					$result_reason = "add_lead INVALID PHONE NUMBER AREACODE";
					}
				}
			if ( ($nanpa_ac_prefix_check=='Y') and ($valid_number > 0) )
				{
				$phone_areacode = substr($phone_number, 0, 3);
				$phone_prefix = substr($phone_number, 3, 3);
				$stmt = "SELECT count(*) from vicidial_nanpa_prefix_codes where areacode='$phone_areacode' and prefix='$phone_prefix';";
				if ($DB>0) {echo "DEBUG: add_lead areacode check query - $stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				$valid_number=$row[0];
				if ($valid_number < 1)
					{
					$result_reason = "add_lead INVALID PHONE NUMBER NANPA AREACODE PREFIX";
					}
				}
			if ($valid_number < 1)
				{
				$result = 'ERROR';
				echo "$result: $result_reason - $phone_number|$user\n";
				$data = "$phone_number";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				### state lookup if enabled
				if ( ($lookup_state == 'Y') and (strlen($state) < 1) )
					{
					$phone_areacode = substr($phone_number, 0, 3);
					$stmt="SELECT state from vicidial_phone_codes where country_code='$phone_code' and areacode='$phone_areacode';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$vpc_recs = mysqli_num_rows($rslt);
					if ($vpc_recs > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$state =	$row[0];
						}
					}

				### START checking for DNC if defined ###
				if ( ($dnc_check == 'Y') or ($dnc_check == 'AREACODE') )
					{
					if ($DB>0) {echo "DEBUG: Checking for system DNC\n";}
					if ($dnc_check == 'AREACODE')
						{
						$phone_areacode = substr($phone_number, 0, 3);
						$phone_areacode .= "XXXXXXX";
						$stmt="SELECT count(*) from vicidial_dnc where phone_number IN('$phone_number','$phone_areacode');";
						}
					else
						{$stmt="SELECT count(*) from vicidial_dnc where phone_number='$phone_number';";}
					if ($DB>0) {echo "DEBUG: add_lead query - $stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$dnc_found=$row[0];

					if ($dnc_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead PHONE NUMBER IN DNC";
						echo "$result: $result_reason - $phone_number|$user\n";
						$data = "$phone_number";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if ( ($campaign_dnc_check == 'Y') or ($campaign_dnc_check == 'AREACODE') )
					{
					if ($DB>0) {echo "DEBUG: Checking for campaign DNC\n";}

					$stmt="SELECT use_other_campaign_dnc from vicidial_campaigns where campaign_id='$campaign_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$use_other_campaign_dnc =	$row[0];
					$temp_campaign_id = $campaign_id;
					if (strlen($use_other_campaign_dnc) > 0) {$temp_campaign_id = $use_other_campaign_dnc;}

					if ($campaign_dnc_check == 'AREACODE')
						{
						$phone_areacode = substr($phone_number, 0, 3);
						$phone_areacode .= "XXXXXXX";
						$stmt="SELECT count(*) from vicidial_campaign_dnc where phone_number IN('$phone_number','$phone_areacode') and campaign_id='$temp_campaign_id';";
						}
					else
						{$stmt="SELECT count(*) from vicidial_campaign_dnc where phone_number='$phone_number' and campaign_id='$temp_campaign_id';";}
					if ($DB>0) {echo "DEBUG: add_lead query - $stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$dnc_found=$row[0];

					if ($dnc_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead PHONE NUMBER IN CAMPAIGN DNC";
						echo "$result: $result_reason - $phone_number|$campaign_id|$user\n";
						$data = "$phone_number|$campaign_id";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				### END checking for DNC if defined ###

				### START checking for duplicate if defined ###
				$multidaySQL='';
				if (preg_match("/1DAY|2DAY|3DAY|7DAY|14DAY|15DAY|21DAY|28DAY|30DAY|60DAY|90DAY|180DAY|360DAY/i",$duplicate_check))
					{
					$day_val=30;
					if (preg_match("/1DAY/i",$duplicate_check)) {$day_val=1;}
					if (preg_match("/2DAY/i",$duplicate_check)) {$day_val=2;}
					if (preg_match("/3DAY/i",$duplicate_check)) {$day_val=3;}
					if (preg_match("/7DAY/i",$duplicate_check)) {$day_val=7;}
					if (preg_match("/14DAY/i",$duplicate_check)) {$day_val=14;}
					if (preg_match("/15DAY/i",$duplicate_check)) {$day_val=15;}
					if (preg_match("/21DAY/i",$duplicate_check)) {$day_val=21;}
					if (preg_match("/28DAY/i",$duplicate_check)) {$day_val=28;}
					if (preg_match("/30DAY/i",$duplicate_check)) {$day_val=30;}
					if (preg_match("/60DAY/i",$duplicate_check)) {$day_val=60;}
					if (preg_match("/90DAY/i",$duplicate_check)) {$day_val=90;}
					if (preg_match("/180DAY/i",$duplicate_check)) {$day_val=180;}
					if (preg_match("/360DAY/i",$duplicate_check)) {$day_val=360;}
					$multiday = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-$day_val,date("Y")));
					$multidaySQL = "and entry_date > \"$multiday\"";
					if ($DB > 0) {echo "DEBUG: $day_val day SQL: |$multidaySQL|";}
					}

				### find list of list_ids in this campaign for the CAMP duplicate check options
				if (preg_match("/CAMP/i",$duplicate_check)) # find lists within campaign
					{
					$stmt="SELECT campaign_id from vicidial_lists where list_id='$list_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$ci_recs = mysqli_num_rows($rslt);
					if ($ci_recs > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$duplicate_camp =	$row[0];

						$stmt="select list_id from vicidial_lists where campaign_id='$duplicate_camp';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$li_recs = mysqli_num_rows($rslt);
						if ($li_recs > 0)
							{
							$L=0;
							while ($li_recs > $L)
								{
								$row=mysqli_fetch_row($rslt);
								$duplicate_lists .=	"'$row[0]',";
								$L++;
								}
							$duplicate_lists = preg_replace('/,$/i', '',$duplicate_lists);
							}
						}
					}

				if (preg_match("/DUPLIST/i",$duplicate_check)) # duplicate check within list
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPLIST\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where phone_number='$phone_number' and list_id='$list_id' $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE PHONE NUMBER IN LIST";
						$data = "$phone_number|$list_id|$duplicate_lead_id";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPCAMP/i",$duplicate_check)) # duplicate check within campaign lists
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPCAMP - $duplicate_lists\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where phone_number='$phone_number' and list_id IN($duplicate_lists) $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE PHONE NUMBER IN CAMPAIGN LISTS";
						$data = "$phone_number|$list_id|$duplicate_lead_id|$duplicate_lead_list";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPSYS/i",$duplicate_check)) # duplicate check within entire system
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPSYS\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where phone_number='$phone_number' $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE PHONE NUMBER IN SYSTEM";
						$data = "$phone_number|$list_id|$duplicate_lead_id|$duplicate_lead_list";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}

				if (preg_match("/DUPPHONEALTLIST/i",$duplicate_check)) # duplicate check for phone against phone_number and alt_phone within list
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPPHONEALTLIST\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id,phone_number,alt_phone from vicidial_list where ( (phone_number='$phone_number') or (alt_phone='$phone_number') ) and list_id='$list_id' $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($DB>0) {echo "DEBUG 2: |$pc_recs|$stmt|\n";}
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =		$row[0];
						$duplicate_lead_list =		$row[1];
						$duplicate_phone_number =	$row[2];
						$duplicate_alt_phone =		$row[3];
						if ($phone_number == $duplicate_phone_number) {$duplicate_type='PHONE';}
						else {$duplicate_type='ALT';}
						}

					if ($duplicate_found > 0) 
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE PHONE NUMBER IN LIST";
						$data = "$phone_number|$list_id|$duplicate_lead_id|$duplicate_type";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPPHONEALTCAMP/i",$duplicate_check)) # duplicate check for phone against phone_number and alt_phone within campaign lists
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPPHONEALTCAMP - $duplicate_lists\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id,phone_number,alt_phone from vicidial_list where ( (phone_number='$phone_number') or (alt_phone='$phone_number') ) and list_id IN($duplicate_lists) $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($DB>0) {echo "DEBUG 2: |$pc_recs|$stmt|\n";}
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =		$row[0];
						$duplicate_lead_list =		$row[1];
						$duplicate_phone_number =	$row[2];
						$duplicate_alt_phone =		$row[3];
						if ($phone_number == $duplicate_phone_number) {$duplicate_type='PHONE';}
						else {$duplicate_type='ALT';}
						}

					if ($duplicate_found > 0) 
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE PHONE NUMBER IN CAMPAIGN LISTS";
						$data = "$phone_number|$list_id|$duplicate_lead_id|$duplicate_lead_list|$duplicate_type";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPPHONEALTSYS/i",$duplicate_check)) # duplicate check for phone against phone_number and alt_phone within entire system
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPPHONEALTSYS\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id,phone_number,alt_phone from vicidial_list where ( (phone_number='$phone_number') or (alt_phone='$phone_number') ) $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($DB>0) {echo "DEBUG 2: |$pc_recs|$stmt|\n";}
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =		$row[0];
						$duplicate_lead_list =		$row[1];
						$duplicate_phone_number =	$row[2];
						$duplicate_alt_phone =		$row[3];
						if ($phone_number == $duplicate_phone_number) {$duplicate_type='PHONE';}
						else {$duplicate_type='ALT';}
						}

					if ($duplicate_found > 0) 
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE PHONE NUMBER IN SYSTEM";
						$data = "$phone_number|$list_id|$duplicate_lead_id|$duplicate_lead_list|$duplicate_type";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}

				if (preg_match("/DUPTITLEALTPHONELIST/i",$duplicate_check)) # duplicate title/alt_phone check within list
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPTITLEALTPHONELIST\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where title='$title' and alt_phone='$alt_phone' and list_id='$list_id' $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE TITLE ALT_PHONE IN LIST";
						$data = "$title|$alt_phone|$list_id|$duplicate_lead_id";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPTITLEALTPHONECAMP/i",$duplicate_check)) # duplicate title/alt_phone check within campaign lists
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPTITLEALTPHONECAMP\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where title='$title' and alt_phone='$alt_phone' and list_id IN($duplicate_lists) $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE TITLE ALT_PHONE IN CAMPAIGN LISTS";
						$data = "$title|$alt_phone|$list_id|$duplicate_lead_id|$duplicate_lead_list";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPTITLEALTPHONESYS/i",$duplicate_check)) # duplicate title/alt_phone check within entire system
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPTITLEALTPHONESYS\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where title='$title' and alt_phone='$alt_phone' $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE TITLE ALT_PHONE IN SYSTEM";
						$data = "$title|$alt_phone|$list_id|$duplicate_lead_id|$duplicate_lead_list";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPNAMEPHONELIST/i",$duplicate_check)) # duplicate name/phone check within list
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPNAMEPHONELIST\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where first_name='$first_name' and last_name='$last_name' and phone_number='$phone_number' $multidaySQL and list_id='$list_id' limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE NAME PHONE IN LIST";
						$data = "$first_name|$last_name|$phone_number|$list_id|$duplicate_lead_id";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPNAMEPHONECAMP/i",$duplicate_check)) # duplicate name/phone check within campaign lists
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPNAMEPHONECAMP\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where first_name='$first_name' and last_name='$last_name' and phone_number='$phone_number' and list_id IN($duplicate_lists) $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE NAME PHONE IN CAMPAIGN LISTS";
						$data = "$first_name|$last_name|$phone_number|$list_id|$duplicate_lead_id|$duplicate_lead_list";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if (preg_match("/DUPNAMEPHONESYS/i",$duplicate_check)) # duplicate name/phone check within entire system
					{
					if ($DB>0) {echo "DEBUG: Checking for duplicates - DUPNAMEPHONESYS\n";}
					$duplicate_found=0;
					$stmt="SELECT lead_id,list_id from vicidial_list where first_name='$first_name' and last_name='$last_name' and phone_number='$phone_number' $multidaySQL limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$duplicate_found=1;
						$row=mysqli_fetch_row($rslt);
						$duplicate_lead_id =	$row[0];
						$duplicate_lead_list =	$row[1];
						}

					if ($duplicate_found > 0)
						{
						$result = 'ERROR';
						$result_reason = "add_lead DUPLICATE NAME PHONE IN SYSTEM";
						$data = "$first_name|$last_name|$phone_number|$list_id|$duplicate_lead_id|$duplicate_lead_list";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				### END checking for duplicate if defined ###


				### get current gmt_offset of the phone_number
				$gmt_offset = lookup_gmt_api($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,$tz_method,$postal_code,$owner,$USprefix);

				$new_status='NEW';
				if ($callback == 'Y')
					{$new_status='CBHOLD';}
				$entry_list_idSQL = ",entry_list_id='0'";
				if (strlen($entry_list_id) > 0)
					{$entry_list_idSQL = ",entry_list_id='$entry_list_id'";}

				### insert a new lead in the system with this phone number
				$stmt = "INSERT INTO vicidial_list SET phone_code=\"$phone_code\",phone_number=\"$phone_number\",list_id=\"$list_id\",status=\"$new_status\",user=\"$user\",vendor_lead_code=\"$vendor_lead_code\",source_id=\"$source_id\",gmt_offset_now=\"$gmt_offset\",title=\"$title\",first_name=\"$first_name\",middle_initial=\"$middle_initial\",last_name=\"$last_name\",address1=\"$address1\",address2=\"$address2\",address3=\"$address3\",city=\"$city\",state=\"$state\",province=\"$province\",postal_code=\"$postal_code\",country_code=\"$country_code\",gender=\"$gender\",date_of_birth=\"$date_of_birth\",alt_phone=\"$alt_phone\",email=\"$email\",security_phrase=\"$security_phrase\",comments=\"$comments\",called_since_last_reset=\"N\",entry_date=\"$ENTRYdate\",last_local_call_time=\"$NOW_TIME\",rank=\"$rank\",owner=\"$owner\" $entry_list_idSQL;";
				if ($DB>0) {echo "DEBUG: add_lead query - $stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);
				if ($affected_rows > 0)
					{
					$lead_id = mysqli_insert_id($link);

					$result = 'SUCCESS';
					$result_reason = "add_lead LEAD HAS BEEN ADDED";
					echo "$result: $result_reason - $phone_number|$list_id|$lead_id|$gmt_offset|$user\n";
					$data = "$phone_number|$list_id|$lead_id|$gmt_offset";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

					if (strlen($multi_alt_phones) > 5)
						{
						$map=$MT;  $ALTm_phone_code=$MT;  $ALTm_phone_number=$MT;  $ALTm_phone_note=$MT;
						$map = explode('!', $multi_alt_phones);
						$map_count = count($map);
						if ($DB>0) {echo "DEBUG: add_lead multi-al-entry - $a|$map_count|$multi_alt_phones\n";}
						$g++;
						$r=0;   $s=0;   $inserted_alt_phones=0;
						while ($r < $map_count)
							{
							$s++;
							$ncn=$MT;
							$ncn = explode('_', $map[$r]);
							print "$ncn[0]|$ncn[1]|$ncn[2]";

							if (strlen($forcephonecode) > 0)
								{$ALTm_phone_code[$r] =	$forcephonecode;}
							else
								{$ALTm_phone_code[$r] =		$ncn[1];}
							if (strlen($ALTm_phone_code[$r]) < 1)
								{$ALTm_phone_code[$r]='1';}
							$ALTm_phone_number[$r] =	$ncn[0];
							$ALTm_phone_note[$r] =		$ncn[2];
							$stmt = "INSERT INTO vicidial_list_alt_phones (lead_id,phone_code,phone_number,alt_phone_note,alt_phone_count) values($lead_id,'$ALTm_phone_code[$r]','$ALTm_phone_number[$r]','$ALTm_phone_note[$r]','$s');";
							if ($DB>0) {echo "DEBUG: add_lead query - $stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$Zaffected_rows = mysqli_affected_rows($link);
							$inserted_alt_phones = ($inserted_alt_phones + $Zaffected_rows);
							$r++;
							}
						$result = 'NOTICE';
						$result_reason = "add_lead MULTI-ALT-PHONE NUMBERS LOADED";
						echo "$result: $result_reason - $inserted_alt_phones|$lead_id|$user\n";
						$data = "$inserted_alt_phones|$lead_id";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}

					### BEGIN custom fields insert section ###
					if ($custom_fields == 'Y')
						{
						if ($custom_fields_enabled > 0)
							{
							$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
							if ($DB>0) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$tablecount_to_print = mysqli_num_rows($rslt);
							if ($tablecount_to_print > 0)
								{
								$CFinsert_SQL='';
								$stmt="SELECT field_id,field_label,field_name,field_description,field_rank,field_help,field_type,field_options,field_size,field_max,field_default,field_cost,field_required,multi_position,name_position,field_order,field_encrypt from vicidial_lists_fields where list_id='$list_id' and field_duplicate!='Y' order by field_rank,field_order,field_label;";
								if ($DB>0) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
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
									$A_field_encrypt[$o] =		$rowx[16];
									$A_field_value[$o] =		'';
									$field_name_id =			$A_field_label[$o];

									if (isset($_GET["$field_name_id"]))				{$form_field_value=$_GET["$field_name_id"];}
										elseif (isset($_POST["$field_name_id"]))	{$form_field_value=$_POST["$field_name_id"];}

									$form_field_value = preg_replace("/\+/"," ",$form_field_value);
									$form_field_value = preg_replace("/;|\"/","",$form_field_value);
									$form_field_value = preg_replace("/\\b/","",$form_field_value);
									$form_field_value = preg_replace("/\\\\$/","",$form_field_value);
									$A_field_value[$o] = $form_field_value;

									if ( ($A_field_type[$o]=='DISPLAY') or ($A_field_type[$o]=='SCRIPT') )
										{
										$A_field_value[$o]='----IGNORE----';
										}
									else
										{
										if (!preg_match("/\|$A_field_label[$o]\|/",$vicidial_list_fields))
											{
											if ( (preg_match("/cf_encrypt/",$active_modules)) and ($A_field_encrypt[$o] == 'Y') and (strlen($A_field_value[$o]) > 0) )
												{
												$field_enc=$MT;
												$A_field_valueSQL[$o] = base64_encode($A_field_value[$o]);
												exec("../agc/aes.pl --encrypt --text=$A_field_valueSQL[$o]", $field_enc);
												$field_enc_ct = count($field_enc);
												$k=0;
												$field_enc_all='';
												while ($field_enc_ct > $k)
													{
													$field_enc_all .= $field_enc[$k];
													$k++;
													}
												$A_field_valueSQL[$o] = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
												}
											else
												{$A_field_valueSQL[$o] = $A_field_value[$o];}

											$CFinsert_SQL .= "$A_field_label[$o]=\"$A_field_valueSQL[$o]\",";
											}
										}
									$o++;
									}

								if (strlen($CFinsert_SQL)>3)
									{
									$CFinsert_SQL = preg_replace("/\"--BLANK--\"/",'""',$CFinsert_SQL);
									$custom_table_update_SQL = "INSERT INTO custom_$list_id SET lead_id=$lead_id,$CFinsert_SQL;";
									if ($DB>0) {echo "$custom_table_update_SQL\n";}
									$rslt=mysql_to_mysqli($custom_table_update_SQL, $link);
									$custom_insert_count = mysqli_affected_rows($link);
									if ($custom_insert_count > 0)
										{
										# Update vicidial_list entry to put list_id as entry_list_id
										$vl_table_entry_update_SQL = "UPDATE vicidial_list SET entry_list_id='$list_id' where lead_id=$lead_id;";
										$rslt=mysql_to_mysqli($vl_table_entry_update_SQL, $link);
										$vl_table_entry_update_count = mysqli_affected_rows($link);

										$result = 'NOTICE';
										$result_reason = "add_lead CUSTOM FIELDS VALUES ADDED";
										echo "$result: $result_reason - $phone_number|$lead_id|$list_id|$vl_table_entry_update_count\n";
										$data = "$phone_number|$lead_id|$list_id";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										}
									else
										{
										$result = 'NOTICE';
										$result_reason = "add_lead CUSTOM FIELDS NOT ADDED, NO FIELDS TO UPDATE DEFINED";
										echo "$result: $result_reason - $phone_number|$lead_id|$list_id\n";
										$data = "$phone_number|$lead_id|$list_id";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										}
									}
								else
									{
									$result = 'NOTICE';
									$result_reason = "add_lead CUSTOM FIELDS NOT ADDED, NO FIELDS DEFINED";
									echo "$result: $result_reason - $phone_number|$lead_id|$list_id\n";
									$data = "$phone_number|$lead_id|$list_id";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								}
							else
								{
								$result = 'NOTICE';
								$result_reason = "add_lead CUSTOM FIELDS NOT ADDED, NO CUSTOM FIELDS DEFINED FOR THIS LIST";
								echo "$result: $result_reason - $phone_number|$lead_id|$list_id\n";
								$data = "$phone_number|$lead_id|$list_id";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						else
							{
							$result = 'NOTICE';
							$result_reason = "add_lead CUSTOM FIELDS NOT ADDED, CUSTOM FIELDS DISABLED";
							echo "$result: $result_reason - $phone_number|$lead_id|$custom_fields|$custom_fields_enabled\n";
							$data = "$phone_number|$lead_id|$custom_fields|$custom_fields_enabled";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					### END custom fields insert section ###

					### BEGIN add to hopper section ###
					if ($add_to_hopper == 'Y')
						{
						$dialable=1;

						$stmt="SELECT vicidial_campaigns.local_call_time,vicidial_lists.local_call_time,vicidial_campaigns.campaign_id from vicidial_campaigns,vicidial_lists where list_id='$list_id' and vicidial_campaigns.campaign_id=vicidial_lists.campaign_id;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$local_call_time =		$row[0];
						$list_local_call_time = $row[1];
						$VD_campaign_id =		$row[2];

						if ($DB > 0) {echo "DEBUG call time: |$local_call_time|$list_local_call_time|$VD_campaign_id|";}
						if ( ($list_local_call_time!='') and (!preg_match("/^campaign$/i",$list_local_call_time)) )
							{$local_call_time = $list_local_call_time;}

						if ($hopper_local_call_time_check == 'Y')
							{
							### call function to determine if lead is dialable
							$dialable = dialable_gmt($DB,$link,$local_call_time,$gmt_offset,$state);
							}
						if ($dialable < 1)
							{
							$result = 'NOTICE';
							$result_reason = "add_lead NOT ADDED TO HOPPER, OUTSIDE OF LOCAL TIME";
							echo "$result: $result_reason - $phone_number|$lead_id|$gmt_offset|$dialable|$user\n";
							$data = "$phone_number|$lead_id|$gmt_offset|$dialable|$state";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							### insert record into vicidial_hopper for alt_phone call attempt
							$stmt = "INSERT INTO vicidial_hopper SET lead_id=$lead_id,campaign_id='$VD_campaign_id',status='READY',list_id='$list_id',gmt_offset_now='$gmt_offset',state='$state',user='',priority='$hopper_priority',source='P',vendor_lead_code=\"$vendor_lead_code\";";
							if ($DB>0) {echo "DEBUG: add_lead query - $stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$Haffected_rows = mysqli_affected_rows($link);
							if ($Haffected_rows > 0)
								{
								$hopper_id = mysqli_insert_id($link);

								$result = 'NOTICE';
								$result_reason = "add_lead ADDED TO HOPPER";
								echo "$result: $result_reason - $phone_number|$lead_id|$hopper_id|$user\n";
								$data = "$phone_number|$lead_id|$hopper_id";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								$result = 'NOTICE';
								$result_reason = "add_lead NOT ADDED TO HOPPER";
								echo "$result: $result_reason - $phone_number|$lead_id|$stmt|$user\n";
								$data = "$phone_number|$lead_id|$stmt";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						}
					### END add to hopper section ###

					### BEGIN scheduled callback section ###
					if ($callback == 'Y')
						{
						$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$camp_recs = mysqli_num_rows($rslt);
						if ($camp_recs > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$camp_count =	$row[0];
							}
						if ($camp_count > 0)
							{
							$valid_callback=0;
							$user_group='';
							if ($callback_type == 'USERONLY')
								{
								$stmt="SELECT user_group from vicidial_users where user='$callback_user';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$user_recs = mysqli_num_rows($rslt);
								if ($user_recs > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$user_group =	$row[0];
									$valid_callback++;
									}
								else
									{
									$result = 'NOTICE';
									$result_reason = "add_lead SCHEDULED CALLBACK NOT ADDED, USER NOT VALID";
									$data = "$lead_id|$campaign_id|$callback_user|$callback_type";
									echo "$result: $result_reason - $data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								}
							else
								{
								$callback_type='ANYONE';
								$callback_user='';
								$valid_callback++;
								}
							if ($valid_callback > 0)
								{
								if ($callback_datetime == 'NOW')
									{$callback_datetime=$NOW_TIME;}
								if (preg_match("/\dDAYS$/i",$callback_datetime)) 
									{
									$callback_days = preg_replace('/[^0-9]/','',$callback_datetime);
									$callback_datetime = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")+$callback_days,date("Y")));
									}
								if (strlen($callback_status)<1)
									{$callback_status='CALLBK';}

								$stmt="INSERT INTO vicidial_callbacks (lead_id,list_id,campaign_id,status,entry_time,callback_time,user,recipient,comments,user_group,lead_status) values($lead_id,'$list_id','$campaign_id','ACTIVE','$NOW_TIME','$callback_datetime','$callback_user','$callback_type','$callback_comments','$user_group','$callback_status');";
								if ($DB>0) {echo "DEBUG: add_lead query - $stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$CBaffected_rows = mysqli_affected_rows($link);

								$result = 'NOTICE';
								$result_reason = "add_lead SCHEDULED CALLBACK ADDED";
								$data = "$lead_id|$campaign_id|$callback_datetime|$callback_type|$callback_user|$callback_status";
								echo "$result: $result_reason - $data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							}
						else
							{
							$result = 'NOTICE';
							$result_reason = "add_lead SCHEDULED CALLBACK NOT ADDED, CAMPAIGN NOT VALID";
							$data = "$lead_id|$campaign_id";
							echo "$result: $result_reason - $data\n";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					### END scheduled callback section ###
					}
				else
					{
					$result = 'ERROR';
					$result_reason = "add_lead LEAD HAS NOT BEEN ADDED";
					echo "$result: $result_reason - $phone_number|$list_id|$stmt|$user\n";
					$data = "$phone_number|$list_id|$stmt";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		exit;
		}
	}
################################################################################
### END add_lead
################################################################################





################################################################################
### update_lead - updates a lead in the vicidial_list table
################################################################################
if ($function == 'update_lead')
	{
	$list_id = preg_replace('/[^0-9]/','',$list_id);
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4') and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$modify_leads=$row[0];

		if ($modify_leads < 1)
			{
			$result = 'ERROR';
			$result_reason = "update_lead USER DOES NOT HAVE PERMISSION TO UPDATE LEADS IN THE SYSTEM";
			echo "$result: $result_reason: |$user|$modify_leads|\n";
			$data = "$modify_leads";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$search_SQL='';
			$lead_id_SQL='';
			$vendor_lead_code_SQL='';
			$phone_number_SQL='';
			$find_lead_id=0;
			$find_vendor_lead_code=0;
			$find_phone_number=0;
			$insert_if_not_found_inserted=0;
			if ( (strlen($search_location)<4) or ($list_id < 99) or (strlen($list_id)<3) )
				{$search_location='SYSTEM';}
			if (strlen($search_method)<6)
				{$search_method='LEAD_ID';}
			if (strlen($records)<1)
				{$records='1';}
			if (strlen($force_entry_list_id) > 1)
				{$entry_list_id = $force_entry_list_id;}
			if ( (preg_match("/LEAD_ID/",$search_method)) and (strlen($lead_id)>0) )
				{
				$find_lead_id=1;
				$lead_id_SQL = "lead_id=$lead_id";
				}
			if ( (preg_match("/VENDOR_LEAD_CODE/",$search_method)) and (strlen($vendor_lead_code)>0) )
				{
				$find_vendor_lead_code=1;
				$vendor_lead_code_SQL = "vendor_lead_code=\"$vendor_lead_code\"";
				}
			if ( (preg_match("/PHONE_NUMBER/",$search_method)) and (strlen($phone_number)>5) and (strlen($phone_number)<19) )
				{
				$find_phone_number=1;
				$phone_number_SQL = "phone_number='$phone_number'";
				}
			if ( ($find_lead_id<1) and ($find_vendor_lead_code<1) and ($find_phone_number<1) )
				{
				$result = 'ERROR';
				$result_reason = "update_lead NO VALID SEARCH METHOD";
				$data = "$search_method|$lead_id|$vendor_lead_code|$phone_number";
				echo "$result: $result_reason - $user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				if ( ($api_list_restrict > 0) and ($list_id >= 99) )
					{
					if (!preg_match("/ $list_id /",$allowed_lists))
						{
						$result = 'ERROR';
						$result_reason = "update_lead NOT AN ALLOWED LIST ID, SEARCH";
						$data = "$phone_number|$list_id";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}
				if ( (preg_match("/Y/i",$list_exists_check)) and (strlen($list_id_field) > 0) )
					{
					$stmt="SELECT count(*) from vicidial_lists where list_id='$list_id_field';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					if ($DB>0) {echo "DEBUG: update_lead list_exists_check query - $row[0]|$stmt\n";}
					$list_exists_count = $row[0];
					if ($list_exists_count < 1)
						{
						$result = 'ERROR';
						$result_reason = "update_lead NOT A DEFINED LIST ID, LIST EXISTS CHECK ENABLED";
						$data = "$phone_number|$list_id_field";
						echo "$result: $result_reason - $data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						exit;
						}
					}

				if (preg_match("/CAMPAIGN/i",$search_location)) # find lists within campaign
					{
					$stmt="SELECT campaign_id from vicidial_lists where list_id='$list_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$ci_recs = mysqli_num_rows($rslt);
					if ($ci_recs > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$search_camp =	$row[0];

						$stmt="select list_id from vicidial_lists where campaign_id='$search_camp';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$li_recs = mysqli_num_rows($rslt);
						if ($li_recs > 0)
							{
							$L=0;
							while ($li_recs > $L)
								{
								$row=mysqli_fetch_row($rslt);
								$search_lists .=	"'$row[0]',";
								$L++;
								}
							$search_lists = preg_replace('/,$/i', '',$search_lists);
							$search_SQL = "and list_id IN($search_lists)";
							}
						}
					}
				if (preg_match("/LIST/i",$search_location)) # search check within list
					{
					$search_SQL = "and list_id='$list_id'";
					}

				$search_found=0;
				if ($find_lead_id > 0) # search for the lead_id
					{
					if ($DB>0) {echo "DEBUG: Checking for lead_id - $lead_id\n";}
					$stmt="SELECT lead_id,list_id,entry_list_id from $vicidial_list_table where $lead_id_SQL $search_SQL order by entry_date desc limit $records;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					$n=0;
					$search_lead_id=array();
					$search_lead_list=array();
					$search_entry_list=array();
					while ($pc_recs > $n)
						{
						$row=mysqli_fetch_row($rslt);
						$search_lead_id[$n] =		$row[0];
						$search_lead_list[$n] =		$row[1];
						$search_entry_list[$n] =	$row[2];
						if (strlen($force_entry_list_id) > 1)
							{$search_entry_list[$n] = $force_entry_list_id;}
						$n++;
						$search_found++;
						}
					}
				if ( ($find_vendor_lead_code > 0) and ($search_found < 1) ) # search for the vendor_lead_code
					{
					if ($DB>0) {echo "DEBUG: Checking for vendor_lead_code - $vendor_lead_code\n";}
					$stmt="SELECT lead_id,list_id,entry_list_id from $vicidial_list_table where $vendor_lead_code_SQL $search_SQL order by entry_date desc limit $records;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					$n=0;
					while ($pc_recs > $n)
						{
						$row=mysqli_fetch_row($rslt);
						$search_lead_id[$n] =		$row[0];
						$search_lead_list[$n] =		$row[1];
						$search_entry_list[$n] =	$row[2];
						if (strlen($force_entry_list_id) > 1)
							{$search_entry_list[$n] = $force_entry_list_id;}
						$n++;
						$search_found++;
						}
					}
				if ( ($find_phone_number > 0) and ($search_found < 1) ) # search for the phone_number
					{
					if ($DB>0) {echo "DEBUG: Checking for phone_number - $phone_number\n";}
					$stmt="SELECT lead_id,list_id,entry_list_id from $vicidial_list_table where $phone_number_SQL $search_SQL order by entry_date desc limit $records;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					$n=0;
					while ($pc_recs > $n)
						{
						$row=mysqli_fetch_row($rslt);
						$search_lead_id[$n] =		$row[0];
						$search_lead_list[$n] =		$row[1];
						$search_entry_list[$n] =	$row[2];
						if (strlen($force_entry_list_id) > 1)
							{$search_entry_list[$n] = $force_entry_list_id;}
						$n++;
						$search_found++;
						}
					}
				### END searching ###

				if ($no_update=='Y')
					{
					if ($search_found > 0)
						{
						if (strlen($lead_id)<1) {$lead_id=$search_lead_id[0];}
						if (strlen($list_id)<1) {$list_id=$search_lead_list[0];}
						$result = 'NOTICE';
						$result_reason = "update_lead LEADS FOUND IN THE SYSTEM";
						$data = "$lead_id|$vendor_lead_code|$phone_number|$search_lead_list[0]|$search_entry_list[0]";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					else
						{
						$result = 'NOTICE';
						$result_reason = "update_lead NO MATCHES FOUND IN THE SYSTEM";
						$data = "$lead_id|$vendor_lead_code|$phone_number";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

						exit;
						}
					}
				else
					{
					if ($search_found > 0)
						{
						if (strlen($lead_id)<1) {$lead_id=$search_lead_id[0];}
						if (strlen($list_id)<1) {$list_id=$search_lead_list[0];}
						$VL_update_SQL='';
						##### BEGIN update lead information in the system #####
						if (strlen($user_field)>0)			{$VL_update_SQL .= "user=\"$user_field\",";}
						if (strlen($list_id_field)>0)		{$VL_update_SQL .= "list_id=\"$list_id_field\",";}
						if (strlen($status)>0)				{$VL_update_SQL .= "status=\"$status\",";}
						if (strlen($vendor_lead_code)>0)	{$VL_update_SQL .= "vendor_lead_code=\"$vendor_lead_code\",";}
						if (strlen($source_id)>0)			{$VL_update_SQL .= "source_id=\"$source_id\",";}
						if (strlen($gmt_offset_now)>0)		{$VL_update_SQL .= "gmt_offset_now=\"$gmt_offset_now\",";}
						if (strlen($title)>0)				{$VL_update_SQL .= "title=\"$title\",";}
						if (strlen($first_name)>0)			{$VL_update_SQL .= "first_name=\"$first_name\",";}
						if (strlen($middle_initial)>0)		{$VL_update_SQL .= "middle_initial=\"$middle_initial\",";}
						if (strlen($last_name)>0)			{$VL_update_SQL .= "last_name=\"$last_name\",";}
						if (strlen($address1)>0)			{$VL_update_SQL .= "address1=\"$address1\",";}
						if (strlen($address2)>0)			{$VL_update_SQL .= "address2=\"$address2\",";}
						if (strlen($address3)>0)			{$VL_update_SQL .= "address3=\"$address3\",";}
						if (strlen($city)>0)				{$VL_update_SQL .= "city=\"$city\",";}
						if (strlen($state)>0)				{$VL_update_SQL .= "state=\"$state\",";}
						if (strlen($province)>0)			{$VL_update_SQL .= "province=\"$province\",";}
						if (strlen($postal_code)>0)			{$VL_update_SQL .= "postal_code=\"$postal_code\",";}
						if (strlen($country_code)>0)		{$VL_update_SQL .= "country_code=\"$country_code\",";}
						if (strlen($gender)>0)				{$VL_update_SQL .= "gender=\"$gender\",";}
						if (strlen($date_of_birth)>0)		{$VL_update_SQL .= "date_of_birth=\"$date_of_birth\",";}
						if (strlen($alt_phone)>0)			{$VL_update_SQL .= "alt_phone=\"$alt_phone\",";}
						if (strlen($email)>0)				{$VL_update_SQL .= "email=\"$email\",";}
						if (strlen($security_phrase)>0)		{$VL_update_SQL .= "security_phrase=\"$security_phrase\",";}
						if (strlen($comments)>0)			{$VL_update_SQL .= "comments=\"$comments\",";}
						if (strlen($rank)>0)				{$VL_update_SQL .= "rank=\"$rank\",";}
						if (strlen($owner)>0)				{$VL_update_SQL .= "owner=\"$owner\",";}
						if (strlen($called_count)>0)		{$VL_update_SQL .= "called_count=\"$called_count\",";}
						if (strlen($phone_code)>0)			{$VL_update_SQL .= "phone_code=\"$phone_code\",";}
						if ( (strlen($reset_lead) > 0 && $reset_lead == 'Y') )	{$VL_update_SQL .= "called_since_last_reset='N',";}
                        if ( (strlen($update_phone_number)>0 && $update_phone_number=='Y' && strlen($phone_number)>0) ) {$VL_update_SQL .= "phone_number='$phone_number',";}
						if ( (strlen($entry_list_id)>0) and ($custom_fields!='Y') )	{$VL_update_SQL .= "entry_list_id=\"$entry_list_id\",";}
						$VL_update_SQL = preg_replace("/,$/","",$VL_update_SQL);
						$VL_update_SQL = preg_replace("/\"--BLANK--\"/",'""',$VL_update_SQL);
						$VL_update_SQL = preg_replace("/\n/","!N",$VL_update_SQL);

						$n=0;
						while ($search_found > $n)
							{
							$VLaffected_rows=0;
							$CFaffected_rows=0;
							$VCBaffected_rows=0;
							if ( ($api_list_restrict > 0) and ($search_lead_list[$n] >= 99) )
								{
								if (!preg_match("/ $search_lead_list[$n] /",$allowed_lists))
									{
									$result = 'ERROR';
									$result_reason = "update_lead NOT AN ALLOWED LIST ID, RESULTS";
									$data = "$search_lead_list[$n]";
									echo "$result: $result_reason - $data\n";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									exit;
									}
								}

							if ($delete_cf_data=='Y')
								{
								$CFentry_list_id='';
								if (strlen($entry_list_id) > 0)
									{$CFentry_list_id=$entry_list_id;}
								if (strlen($force_entry_list_id) > 0)
									{$CFentry_list_id=$force_entry_list_id;}
								if (strlen($CFentry_list_id) < 1)
									{$CFentry_list_id=$search_entry_list[$n];}
								$stmt="SHOW TABLES LIKE \"custom_$CFentry_list_id\";";
								if ($DB>0) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$tablecount_to_print = mysqli_num_rows($rslt);
								if ($tablecount_to_print > 0) 
									{
									$stmt = "DELETE from custom_$CFentry_list_id where lead_id='$search_lead_id[$n]';";
									if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
									$rslt=mysql_to_mysqli($stmt, $link);
									$VCFaffected_rows = mysqli_affected_rows($link);
									}

								$stmt = "UPDATE $vicidial_list_table SET entry_list_id=0 where lead_id='$search_lead_id[$n]';";
								$result_reason = "update_lead CUSTOM FIELDS DATA HAS BEEN DELETED";
								if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$VLaffected_rows = mysqli_affected_rows($link);

								$result = 'NOTICE';
								echo "$result: $result_reason - $user|$search_lead_id[$n]|$CFentry_list_id|$VLaffected_rows|$VCFaffected_rows\n";
								$data = "$phone_number|$search_lead_list[$n]|$search_lead_id[$n]|$CFentry_list_id|$VLaffected_rows|$VCFaffected_rows";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}

							if ( (strlen($VL_update_SQL)>6) or ($delete_lead=='Y') )
								{
								if (strlen($VL_update_SQL)>6)
									{
									$stmt = "UPDATE $vicidial_list_table SET $VL_update_SQL where lead_id='$search_lead_id[$n]';";
									$result_reason = "update_lead LEAD HAS BEEN UPDATED";
									}
								if ($delete_lead=='Y')
									{
									$stmt = "INSERT INTO vicidial_callbacks_archive SELECT * from vicidial_callbacks where lead_id='$search_lead_id[$n]';";
									if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
									$rslt=mysql_to_mysqli($stmt, $link);
									$VCBAaffected_rows = mysqli_affected_rows($link);

									$stmt = "DELETE from vicidial_callbacks where lead_id='$search_lead_id[$n]';";
									if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
									$rslt=mysql_to_mysqli($stmt, $link);
									$VCBaffected_rows = mysqli_affected_rows($link);

									$stmt = "DELETE from $vicidial_list_table where lead_id='$search_lead_id[$n]';";
									$result_reason = "update_lead LEAD HAS BEEN DELETED $VCBaffected_rows|$VCBAaffected_rows";
									}
								if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$VLaffected_rows = mysqli_affected_rows($link);

								$result = 'SUCCESS';
								echo "$result: $result_reason - $user|$search_lead_id[$n]|$VLaffected_rows|$VCBAaffected_rows|$VCBAaffected_rows|\n";
								$data = "$phone_number|$search_lead_list[$n]|$search_lead_id[$n]|$gmt_offset";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							##### BEGIN scheduled callback section #####
							if ($callback == 'Y')
								{
								$cb_count=0;
								$stmt="SELECT callback_id from vicidial_callbacks where lead_id='$search_lead_id[$n]';";
								$rslt=mysql_to_mysqli($stmt, $link);
								$lead_recs = mysqli_num_rows($rslt);
								if ($lead_recs > 0)
									{
									$row=mysqli_fetch_row($rslt);
									$callback_id =	$row[0];
									}
								if ($lead_recs > 0)
									{
									### Update existing scheduled callback
									if (strlen($callback_datetime)>0)
										{
										if ($callback_datetime == 'NOW')
											{$callback_datetime=$NOW_TIME;}
										if (preg_match("/\dDAYS$/i",$callback_datetime)) 
											{
											$callback_days = preg_replace('/[^0-9]/','',$callback_datetime);
											$callback_datetime = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")+$callback_days,date("Y")));
											}
										$callback_datetimeSQL=",callback_time='$callback_datetime'";
										}
									if (strlen($campaign_id)>0)
										{$campaign_idSQL=",campaign_id='$campaign_id'";}
									if (strlen($list_id_field)>0)
										{$list_idSQL=",list_id='$list_id_field'";}
									if (strlen($callback_status)>0)
										{$callback_statusSQL=",lead_status='$callback_status'";}
									if (strlen($callback_comments)>0)
										{$callback_commentsSQL=",comments='$callback_comments'";}
									if (strlen($callback_type)>0)
										{$callback_typeSQL=",recipient='$callback_type'";}
									if (strlen($callback_user)>0)
										{$callback_userSQL=",user='$callback_user'";}

									$CBupdateSQL = "$callback_datetimeSQL$callback_statusSQL$campaign_idSQL$list_idSQL$callback_typeSQL$callback_commentsSQL$callback_userSQL";

									if (strlen($CBupdateSQL)>3)
										{
										$stmt="UPDATE vicidial_callbacks SET status='ACTIVE',entry_time='$NOW_TIME'$CBupdateSQL where callback_id='$callback_id';";
										if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
										$rslt=mysql_to_mysqli($stmt, $link);
										$CBaffected_rows = mysqli_affected_rows($link);

										$result = 'NOTICE';
										$result_reason = "update_lead SCHEDULED CALLBACK UPDATED";
										$data = "$search_lead_id[$n]|$campaign_id|$callback_datetime|$callback_type|$callback_user|$callback_status";
										echo "$result: $result_reason - $data\n";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										}
									else
										{
										$result = 'NOTICE';
										$result_reason = "update_lead SCHEDULED CALLBACK NOT UPDATED, NO FIELDS SPECIFIED";
										$data = "$search_lead_id[$n]|$CBupdateSQL";
										echo "$result: $result_reason - $data\n";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										}
									}
								else
									{
									### Add new scheduled callback
									$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id';";
									$rslt=mysql_to_mysqli($stmt, $link);
									$camp_recs = mysqli_num_rows($rslt);
									if ($camp_recs > 0)
										{
										$row=mysqli_fetch_row($rslt);
										$camp_count =	$row[0];
										}
									if ($camp_count > 0)
										{
										$valid_callback=0;
										$user_group='';
										if ($callback_type == 'USERONLY')
											{
											$stmt="SELECT user_group from vicidial_users where user='$callback_user';";
											$rslt=mysql_to_mysqli($stmt, $link);
											$user_recs = mysqli_num_rows($rslt);
											if ($user_recs > 0)
												{
												$row=mysqli_fetch_row($rslt);
												$user_group =	$row[0];
												$valid_callback++;
												}
											else
												{
												$result = 'NOTICE';
												$result_reason = "update_lead SCHEDULED CALLBACK NOT ADDED, USER NOT VALID";
												$data = "$search_lead_id[$n]|$campaign_id|$callback_user|$callback_type";
												echo "$result: $result_reason - $data\n";
												api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
												}
											}
										else
											{
											$callback_type='ANYONE';
											$callback_user='';
											$valid_callback++;
											}
										if ($valid_callback > 0)
											{
											if ($callback_datetime == 'NOW')
												{$callback_datetime=$NOW_TIME;}
											if (preg_match("/\dDAYS$/i",$callback_datetime)) 
												{
												$callback_days = preg_replace('/[^0-9]/','',$callback_datetime);
												$callback_datetime = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")+$callback_days,date("Y")));
												}
											if (strlen($callback_status)<1)
												{$callback_status='CALLBK';}

											$stmt="INSERT INTO vicidial_callbacks (lead_id,list_id,campaign_id,status,entry_time,callback_time,user,recipient,comments,user_group,lead_status) values('$search_lead_id[$n]','$search_lead_list[$n]','$campaign_id','ACTIVE','$NOW_TIME','$callback_datetime','$callback_user','$callback_type','$callback_comments','$user_group','$callback_status');";
											if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
											$rslt=mysql_to_mysqli($stmt, $link);
											$CBaffected_rows = mysqli_affected_rows($link);

											$result = 'NOTICE';
											$result_reason = "update_lead SCHEDULED CALLBACK ADDED";
											$data = "$search_lead_id[$n]|$campaign_id|$callback_datetime|$callback_type|$callback_user|$callback_status";
											echo "$result: $result_reason - $data\n";
											api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
											}
										}
									else
										{
										$result = 'NOTICE';
										$result_reason = "update_lead SCHEDULED CALLBACK NOT ADDED, CAMPAIGN NOT VALID";
										$data = "$search_lead_id[$n]|$campaign_id";
										echo "$result: $result_reason - $data\n";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										}
									}
								}
							if ($callback == 'REMOVE')
								{
								$stmt = "INSERT INTO vicidial_callbacks_archive SELECT * from vicidial_callbacks where lead_id='$search_lead_id[$n]';";
								if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$VCBAaffected_rows = mysqli_affected_rows($link);

								$stmt = "DELETE from vicidial_callbacks where lead_id='$search_lead_id[$n]';";
								if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$VCBaffected_rows = mysqli_affected_rows($link);

								$result = 'NOTICE';
								$result_reason = "update_lead SCHEDULED CALLBACK DELETED";
								$data = "$user|$search_lead_id[$n]|$VCBaffected_rows|$VCBAaffected_rows";
								echo "$result: $result_reason - $data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							##### END scheduled callback section #####

							##### BEGIN custom fields update query build #####
							if ($custom_fields=='Y')
								{
								$lead_custom_list = $search_lead_list[$n];
								if (strlen($force_entry_list_id) > 1)
									{$lead_custom_list = $force_entry_list_id;}
								if ($search_entry_list[$n] > 99) {$lead_custom_list = $search_entry_list[$n];}
								$update_sent=0;
								$CFoutput='';
								$stmt="SHOW TABLES LIKE \"custom_$lead_custom_list\";";
								if ($DB>0) {echo "$stmt";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$tablecount_to_print = mysqli_num_rows($rslt);
								if ($tablecount_to_print > 0)
									{
									if ($delete_lead=='Y')
										{
										$stmt = "DELETE from custom_$lead_custom_list where lead_id='$search_lead_id[$n]';";
										if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
										$rslt=mysql_to_mysqli($stmt, $link);
										$VCLDaffected_rows = mysqli_affected_rows($link);

										if ($VCLDaffected_rows > 0)
											{
											$result = 'NOTICE';
											$result_reason = "update_lead CUSTOM FIELDS ENTRY DELETED";
											echo "$result: $result_reason - $phone_number|$search_lead_id[$n]|$search_lead_list[$n]|$search_entry_list[$n]|$lead_custom_list|$VCLDaffected_rows\n";
											$data = "$phone_number|$search_lead_id[$n]|$search_lead_list[$n]|$VCLDaffected_rows";
											api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
											}
										}
									else
										{
										$update_SQL='';
										$VL_update_SQL='';
										$stmt="SELECT field_id,field_label,field_name,field_description,field_rank,field_help,field_type,field_options,field_size,field_max,field_default,field_cost,field_required,multi_position,name_position,field_order,field_encrypt from vicidial_lists_fields where list_id='$lead_custom_list' and field_duplicate!='Y' order by field_rank,field_order,field_label;";
										$rslt=mysql_to_mysqli($stmt, $link);
										$fields_to_print = mysqli_num_rows($rslt);
										$fields_list='';
										$o=0;
										while ($fields_to_print > $o)
											{
											$new_field_value='';
											$form_field_value='';
											$update_this_field=0;
											$rowx=mysqli_fetch_row($rslt);
											$A_field_id[$o] =			$rowx[0];
											$A_field_label[$o] =		$rowx[1];
											$A_field_name[$o] =			$rowx[2];
											$A_field_type[$o] =			$rowx[6];
											$A_field_size[$o] =			$rowx[8];
											$A_field_max[$o] =			$rowx[9];
											$A_field_required[$o] =		$rowx[12];
											$A_field_encrypt[$o] =		$rowx[16];
											$A_field_value[$o] =		'';
											$field_name_id =			$A_field_label[$o];

											if (preg_match("/\?$field_name_id=|&$field_name_id=/",$query_string))
												{
												if (isset($_GET["$field_name_id"]))				{$form_field_value=$_GET["$field_name_id"];}
													elseif (isset($_POST["$field_name_id"]))	{$form_field_value=$_POST["$field_name_id"];}

												$form_field_value = preg_replace("/\+/"," ",$form_field_value);
												$form_field_value = preg_replace("/;|\"/","",$form_field_value);
												$form_field_value = preg_replace("/\\b/","",$form_field_value);
												$form_field_value = preg_replace("/\\\\$/","",$form_field_value);
												$A_field_value[$o] = $form_field_value;
												$update_this_field++;
												}

											if ( ($A_field_type[$o]=='DISPLAY') or ($A_field_type[$o]=='SCRIPT') or ($update_this_field < 1) )
												{
												$A_field_value[$o]='----IGNORE----';
												}
											else
												{
												if (!preg_match("/\|$A_field_label[$o]\|/",$vicidial_list_fields))
													{
													if ( (preg_match("/cf_encrypt/",$active_modules)) and ($A_field_encrypt[$o] == 'Y') and (strlen($A_field_value[$o]) > 0) )
														{
														$field_enc=$MT;
														$A_field_valueSQL[$o] = base64_encode($A_field_value[$o]);
														exec("../agc/aes.pl --encrypt --text=$A_field_valueSQL[$o]", $field_enc);
														$field_enc_ct = count($field_enc);
														$k=0;
														$field_enc_all='';
														while ($field_enc_ct > $k)
															{
															$field_enc_all .= $field_enc[$k];
															$k++;
															}
														$A_field_valueSQL[$o] = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
														}
													else
														{$A_field_valueSQL[$o] = $A_field_value[$o];}

													$update_SQL .= "$A_field_label[$o]=\"$A_field_valueSQL[$o]\",";
													}
												}
											$o++;
											}

										$custom_update_count=0;
										if (strlen($update_SQL)>3)
											{
											$custom_record_lead_count=0;
											$stmt="SELECT count(*) from custom_$lead_custom_list where lead_id='$search_lead_id[$n]';";
											if ($DB>0) {echo "$stmt";}
											$rslt=mysql_to_mysqli($stmt, $link);
											$fieldleadcount_to_print = mysqli_num_rows($rslt);
											if ($fieldleadcount_to_print > 0)
												{
												$rowx=mysqli_fetch_row($rslt);
												$custom_record_lead_count =	$rowx[0];
												}
											$update_SQL = preg_replace("/,$/","",$update_SQL);
											$update_SQL = preg_replace("/\"--BLANK--\"/",'""',$update_SQL);
											$custom_table_update_SQL = "INSERT INTO custom_$lead_custom_list SET lead_id='$search_lead_id[$n]',$update_SQL;";
											if ($custom_record_lead_count > 0)
												{$custom_table_update_SQL = "UPDATE custom_$lead_custom_list SET $update_SQL where lead_id='$search_lead_id[$n]';";}

											$rslt=mysql_to_mysqli($custom_table_update_SQL, $link);
											$custom_update_count = mysqli_affected_rows($link);
											if ($DB) {echo "$custom_update_count|$custom_table_update_SQL\n";}
											if (!$rslt) {die('Could not execute: ' . mysqli_error());}

											$result = 'NOTICE';
											$result_reason = "update_lead CUSTOM FIELDS VALUES UPDATED";
											echo "$result: $result_reason - $phone_number|$search_lead_id[$n]|$search_lead_list[$n]|$search_entry_list[$n]|$lead_custom_list|$custom_update_count\n";
											$data = "$phone_number|$search_lead_id[$n]|$search_lead_list[$n]";
											api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

											$update_sent++;
											}

										if ($custom_update_count > 0)
											{
											$list_table_update_SQL = "UPDATE $vicidial_list_table SET entry_list_id='$lead_custom_list' where lead_id='$search_lead_id[$n]';";
											$rslt=mysql_to_mysqli($list_table_update_SQL, $link);
											$list_update_count = mysqli_affected_rows($link);
											if ($DB) {echo "$list_update_count|$list_table_update_SQL\n";}
											if (!$rslt) {die('Could not execute: ' . mysqli_error());}
											}
										}
									}
								else
									{
									$result = 'NOTICE';
									$result_reason = "update_lead CUSTOM FIELDS NOT ADDED, NO CUSTOM FIELDS DEFINED FOR THIS LIST";
									echo "$result: $result_reason - $phone_number|$search_lead_id[$n]|$search_lead_list[$n]\n";
									$data = "$phone_number|$search_lead_id[$n]|$search_lead_list[$n]";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								}
							##### END custom fields update query build #####

							$n++;
							}

						##### END update lead information in the system #####
						}
					else
						{
						$result = 'ERROR';
						if ($insert_if_not_found=='Y')
							{$result = 'NOTICE';}
						$result_reason = "update_lead NO MATCHES FOUND IN THE SYSTEM";
						$data = "$lead_id|$vendor_lead_code|$phone_number";
						echo "$result: $result_reason: |$user|$data\n";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						if ($insert_if_not_found!='Y')
							{exit;}
						else
							{
							if (strlen($list_id_field)>0)		{$list_id=$list_id_field;}
							##### BEGIN insert not-found lead into the system #####
							if  ( (strlen($phone_number)<6) or (strlen($phone_number)>18) or (strlen($phone_code)<1) or (strlen($list_id)<3) )
								{
								$result = 'ERROR';
								$result_reason = "update_lead INVALID DATA FOR LEAD INSERTION";
								$data = "$phone_number|$phone_code|$list_id|$insert_if_not_found";
								echo "$result: $result_reason - $user|$data\n";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								exit;
								}
							else
								{
								if ( ($api_list_restrict > 0) and ($list_id >= 99) )
									{
									if (!preg_match("/ $list_id /",$allowed_lists)) # $search_lead_list[$n] replaced with $list_id 03/08/23
										{
										$result = 'ERROR';
										$result_reason = "update_lead NOT AN ALLOWED LIST ID, INSERT";
										$data = "$phone_number|$list_id";
										echo "$result: $result_reason - $data\n";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										exit;
										}
									}
								### get current gmt_offset of the phone_number
								$gmt_offset = lookup_gmt_api($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,$tz_method,$postal_code,$owner,$USprefix);

								$entry_list_idSQL = ",entry_list_id='0'";
								if ( (strlen($entry_list_id) > 0) and ($custom_fields!='Y') )
									{$entry_list_idSQL = ",entry_list_id='$entry_list_id'";}

								if (strlen($status)<1)
									{$status='NEW';}
								### insert a new lead in the system with this phone number
								$stmt = "INSERT INTO vicidial_list SET phone_code=\"$phone_code\",phone_number=\"$phone_number\",list_id=\"$list_id\",status=\"$status\",user=\"$user\",vendor_lead_code=\"$vendor_lead_code\",source_id=\"$source_id\",gmt_offset_now=\"$gmt_offset\",title=\"$title\",first_name=\"$first_name\",middle_initial=\"$middle_initial\",last_name=\"$last_name\",address1=\"$address1\",address2=\"$address2\",address3=\"$address3\",city=\"$city\",state=\"$state\",province=\"$province\",postal_code=\"$postal_code\",country_code=\"$country_code\",gender=\"$gender\",date_of_birth=\"$date_of_birth\",alt_phone=\"$alt_phone\",email=\"$email\",security_phrase=\"$security_phrase\",comments=\"$comments\",called_since_last_reset=\"N\",entry_date=\"$ENTRYdate\",last_local_call_time=\"$NOW_TIME\",rank=\"$rank\",owner=\"$owner\" $entry_list_idSQL;";
								if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$affected_rows = mysqli_affected_rows($link);
								$insert_if_not_found_inserted = $affected_rows;
								if ($affected_rows > 0)
									{
									$lead_id = mysqli_insert_id($link);

									$result = 'SUCCESS';
									$result_reason = "update_lead LEAD HAS BEEN ADDED";
									echo "$result: $result_reason - $phone_number|$list_id|$lead_id|$gmt_offset|$user\n";
									$data = "$phone_number|$list_id|$lead_id|$gmt_offset";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

									if (strlen($multi_alt_phones) > 5)
										{
										$map=$MT;  $ALTm_phone_code=$MT;  $ALTm_phone_number=$MT;  $ALTm_phone_note=$MT;
										$map = explode('!', $multi_alt_phones);
										$map_count = count($map);
										if ($DB>0) {echo "DEBUG: update_lead multi-al-entry - $a|$map_count|$multi_alt_phones\n";}
										$g++;
										$r=0;   $s=0;   $inserted_alt_phones=0;
										while ($r < $map_count)
											{
											$s++;
											$ncn=$MT;
											$ncn = explode('_', $map[$r]);
											print "$ncn[0]|$ncn[1]|$ncn[2]";

											if (strlen($forcephonecode) > 0)
												{$ALTm_phone_code[$r] =	$forcephonecode;}
											else
												{$ALTm_phone_code[$r] =		$ncn[1];}
											if (strlen($ALTm_phone_code[$r]) < 1)
												{$ALTm_phone_code[$r]='1';}
											$ALTm_phone_number[$r] =	$ncn[0];
											$ALTm_phone_note[$r] =		$ncn[2];
											$stmt = "INSERT INTO vicidial_list_alt_phones (lead_id,phone_code,phone_number,alt_phone_note,alt_phone_count) values($lead_id,'$ALTm_phone_code[$r]','$ALTm_phone_number[$r]','$ALTm_phone_note[$r]','$s');";
											if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
											$rslt=mysql_to_mysqli($stmt, $link);
											$Zaffected_rows = mysqli_affected_rows($link);
											$inserted_alt_phones = ($inserted_alt_phones + $Zaffected_rows);
											$r++;
											}
										$result = 'NOTICE';
										$result_reason = "update_lead MULTI-ALT-PHONE NUMBERS LOADED";
										echo "$result: $result_reason - $inserted_alt_phones|$lead_id|$user\n";
										$data = "$inserted_alt_phones|$lead_id";
										api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
										}


									### BEGIN custom fields insert section ###
									if ($custom_fields == 'Y')
										{
										if ($custom_fields_enabled > 0)
											{
											$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
											if ($DB>0) {echo "$stmt";}
											$rslt=mysql_to_mysqli($stmt, $link);
											$tablecount_to_print = mysqli_num_rows($rslt);
											if ($tablecount_to_print > 0)
												{
												$CFinsert_SQL='';
												$stmt="SELECT field_id,field_label,field_name,field_description,field_rank,field_help,field_type,field_options,field_size,field_max,field_default,field_cost,field_required,multi_position,name_position,field_order,field_encrypt from vicidial_lists_fields where list_id='$list_id' and field_duplicate!='Y' order by field_rank,field_order,field_label;";
												$rslt=mysql_to_mysqli($stmt, $link);
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
													$A_field_encrypt[$o] =		$rowx[16];
													$A_field_value[$o] =		'';
													$field_name_id =			$A_field_label[$o];

													if (isset($_GET["$field_name_id"]))				{$form_field_value=$_GET["$field_name_id"];}
														elseif (isset($_POST["$field_name_id"]))	{$form_field_value=$_POST["$field_name_id"];}

													$form_field_value = preg_replace("/\+/"," ",$form_field_value);
													$form_field_value = preg_replace("/;|\"/","",$form_field_value);
													$form_field_value = preg_replace("/\\b/","",$form_field_value);
													$form_field_value = preg_replace("/\\\\$/","",$form_field_value);
													$A_field_value[$o] = $form_field_value;

													if ( ($A_field_type[$o]=='DISPLAY') or ($A_field_type[$o]=='SCRIPT') )
														{
														$A_field_value[$o]='----IGNORE----';
														}
													else
														{
														if (!preg_match("/\|$A_field_label[$o]\|/",$vicidial_list_fields))
															{
															if ( (preg_match("/cf_encrypt/",$active_modules)) and ($A_field_encrypt[$o] == 'Y') and (strlen($A_field_value[$o]) > 0) )
																{
																$field_enc=$MT;
																$A_field_valueSQL[$o] = base64_encode($A_field_value[$o]);
																exec("../agc/aes.pl --encrypt --text=$A_field_valueSQL[$o]", $field_enc);
																$field_enc_ct = count($field_enc);
																$k=0;
																$field_enc_all='';
																while ($field_enc_ct > $k)
																	{
																	$field_enc_all .= $field_enc[$k];
																	$k++;
																	}
																$A_field_valueSQL[$o] = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
																}
															else
																{$A_field_valueSQL[$o] = $A_field_value[$o];}

															$CFinsert_SQL .= "$A_field_label[$o]=\"$A_field_valueSQL[$o]\",";
															}
														}
													$o++;
													}

												if (strlen($CFinsert_SQL)>3)
													{
													$temp_entry_list_id = $list_id;
													if (strlen($force_entry_list_id) > 1)
														{$temp_entry_list_id = $force_entry_list_id;}
													$CFinsert_SQL = preg_replace("/,$/","",$CFinsert_SQL);

													$CFinsert_SQL = preg_replace("/\"--BLANK--\"/",'""',$CFinsert_SQL);
													$custom_table_update_SQL = "INSERT INTO custom_$temp_entry_list_id SET lead_id=$lead_id,$CFinsert_SQL;";
													$rslt=mysql_to_mysqli($custom_table_update_SQL, $link);
													$custom_insert_count = mysqli_affected_rows($link);
													if ($custom_insert_count > 0)
														{
														# Update vicidial_list entry to put list_id as entry_list_id
														$vl_table_entry_update_SQL = "UPDATE $vicidial_list_table SET entry_list_id='$temp_entry_list_id' where lead_id=$lead_id;";
														$rslt=mysql_to_mysqli($vl_table_entry_update_SQL, $link);
														$vl_table_entry_update_count = mysqli_affected_rows($link);

														$result = 'NOTICE';
														$result_reason = "update_lead CUSTOM FIELDS VALUES ADDED";
														echo "$result: $result_reason - $phone_number|$lead_id|$list_id|$vl_table_entry_update_count\n";
														$data = "$phone_number|$lead_id|$list_id";
														api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
														}
													else
														{
														$result = 'NOTICE';
														$result_reason = "update_lead CUSTOM FIELDS NOT ADDED, NO FIELDS DEFINED";
														echo "$result: $result_reason - $phone_number|$lead_id|$list_id\n";
														$data = "$phone_number|$lead_id|$list_id";
														api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
														}
													}
												else
													{
													$result = 'NOTICE';
													$result_reason = "update_lead CUSTOM FIELDS NOT ADDED, NO FIELDS DEFINED";
													echo "$result: $result_reason - $phone_number|$lead_id|$list_id\n";
													$data = "$phone_number|$lead_id|$list_id";
													api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
													}
												}
											else
												{
												$result = 'NOTICE';
												$result_reason = "update_lead CUSTOM FIELDS NOT ADDED, NO CUSTOM FIELDS DEFINED FOR THIS LIST";
												echo "$result: $result_reason - $phone_number|$lead_id|$list_id\n";
												$data = "$phone_number|$lead_id|$list_id";
												api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
												}
											}
										else
											{
											$result = 'NOTICE';
											$result_reason = "update_lead CUSTOM FIELDS NOT ADDED, CUSTOM FIELDS DISABLED";
											echo "$result: $result_reason - $phone_number|$lead_id|$custom_fields|$custom_fields_enabled\n";
											$data = "$phone_number|$lead_id|$custom_fields|$custom_fields_enabled";
											api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
											}
										}
									### END custom fields insert section ###
									}
								}
							##### END insert not-found lead into the system #####
							}
						}
					}

				### BEGIN add to hopper section ###
				if ( ($add_to_hopper == 'Y') and ( ($search_found > 0) or ($insert_if_not_found_inserted > 0) ) )
					{
					$stmt="SELECT count(*) from vicidial_hopper where lead_id=$lead_id;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$hopper_lead_count =		$row[0];

					if ($hopper_lead_count < 1)
						{
						$dialable=1;

						$stmt="SELECT vicidial_campaigns.local_call_time,vicidial_lists.local_call_time,vicidial_campaigns.campaign_id from vicidial_campaigns,vicidial_lists where list_id='$list_id' and vicidial_campaigns.campaign_id=vicidial_lists.campaign_id;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$local_call_time =		$row[0];
						$list_local_call_time = $row[1];
						$VD_campaign_id =		$row[2];

						if ($DB > 0) {echo "DEBUG call time: |$local_call_time|$list_local_call_time|$VD_campaign_id|";}
						if ( ($list_local_call_time!='') and (!preg_match("/^campaign$/i",$list_local_call_time)) )
							{$local_call_time = $list_local_call_time;}

						$stmt="SELECT state,vendor_lead_code,gmt_offset_now from $vicidial_list_table where lead_id=$lead_id;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$ulhi_recs = mysqli_num_rows($rslt);
						if ($ulhi_recs > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$state =				$row[0];
							$vendor_lead_code =		$row[1];
							$gmt_offset =			$row[2];

							if ($hopper_local_call_time_check == 'Y')
								{
								### call function to determine if lead is dialable
								$dialable = dialable_gmt($DB,$link,$local_call_time,$gmt_offset,$state);
								}
							if ($dialable < 1)
								{
								$result = 'NOTICE';
								$result_reason = "update_lead NOT ADDED TO HOPPER, OUTSIDE OF LOCAL TIME";
								echo "$result: $result_reason - $phone_number|$lead_id|$gmt_offset|$dialable|$user\n";
								$data = "$phone_number|$lead_id|$gmt_offset|$dialable|$state";
								api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
								}
							else
								{
								### insert record into vicidial_hopper
								$stmt = "INSERT INTO vicidial_hopper SET lead_id=$lead_id,campaign_id='$VD_campaign_id',status='READY',list_id='$list_id',gmt_offset_now='$gmt_offset',state='$state',user='',priority='$hopper_priority',source='P',vendor_lead_code=\"$vendor_lead_code\";";
								if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$Haffected_rows = mysqli_affected_rows($link);
								if ($Haffected_rows > 0)
									{
									$hopper_id = mysqli_insert_id($link);

									$result = 'NOTICE';
									$result_reason = "update_lead ADDED TO HOPPER";
									echo "$result: $result_reason - $phone_number|$lead_id|$hopper_id|$user\n";
									$data = "$phone_number|$lead_id|$hopper_id";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								else
									{
									$result = 'NOTICE';
									$result_reason = "update_lead NOT ADDED TO HOPPER";
									echo "$result: $result_reason - $phone_number|$lead_id|$stmt|$user\n";
									$data = "$phone_number|$lead_id|$stmt";
									api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
									}
								}
							}
						else
							{
							$result = 'NOTICE';
							$result_reason = "update_lead NOT ADDED TO HOPPER, LEAD NOT FOUND";
							echo "$result: $result_reason - $phone_number|$lead_id|$user\n";
							$data = "$phone_number|$lead_id|$user";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					else
						{
						$result = 'NOTICE';
						$result_reason = "update_lead NOT ADDED TO HOPPER, LEAD IS ALREADY IN THE HOPPER";
						echo "$result: $result_reason - $phone_number|$lead_id|$user\n";
						$data = "$phone_number|$lead_id|$user";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				### END add to hopper section ###

				### BEGIN remove from hopper section ###
				if ( (preg_match("/^Y$/",$remove_from_hopper)) and ( ($search_found > 0) or ($insert_if_not_found_inserted > 0) ) )
					{
					$stmt="SELECT status from vicidial_hopper where lead_id='$lead_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$hopper_ct = mysqli_num_rows($rslt);
					if ($hopper_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$hopper_status =		$row[0];

						### delete record from vicidial_hopper
						$stmt = "DELETE FROM vicidial_hopper WHERE lead_id='$lead_id' and status NOT IN('INCALL','DONE','HOLD','DNC');";
						if ($DB>0) {echo "DEBUG: update_lead query - $stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$Haffected_rows = mysqli_affected_rows($link);
						if ($Haffected_rows > 0)
							{
							$result = 'NOTICE';
							$result_reason = "update_lead REMOVED FROM HOPPER";
							echo "$result: $result_reason - $phone_number|$lead_id|$hopper_status|$user\n";
							$data = "$phone_number|$lead_id|$hopper_status";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						else
							{
							$result = 'NOTICE';
							$result_reason = "update_lead NOT REMOVED FROM HOPPER";
							echo "$result: $result_reason - $phone_number|$lead_id|$hopper_status|$user\n";
							$data = "$phone_number|$lead_id|$hopper_status";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							}
						}
					else
						{
						$result = 'NOTICE';
						$result_reason = "update_lead NOT REMOVED FROM HOPPER, LEAD IS NOT IN THE HOPPER";
						echo "$result: $result_reason - $phone_number|$lead_id|$user\n";
						$data = "$phone_number|$lead_id|$user";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
						}
					}
				### END remove from hopper section ###
				}
			}
		exit;
		}
	}
################################################################################
### END update_lead
################################################################################





################################################################################
### batch_update_lead - updates multiple leads in vicidial_list with single set of field values (limited field options)
################################################################################
if ($function == 'batch_update_lead')
	{
	$list_id_field = preg_replace('/[^0-9]/','',$list_id_field);
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and modify_leads IN('1','2','3','4') and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$modify_leads=$row[0];

		if ($modify_leads < 1)
			{
			$result = 'ERROR';
			$result_reason = "batch_update_lead USER DOES NOT HAVE PERMISSION TO UPDATE LEADS IN THE SYSTEM";
			echo "$result: $result_reason: |$user|$modify_leads|\n";
			$data = "$modify_leads";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$lead_ids_SQL='';
			$find_lead_id=0;
			if (strlen($records)<1)
				{$records='1000';}

			if (strlen($lead_ids)>0)
				{
				$find_lead_id=1;
				$lead_ids_SQL = "lead_id IN($lead_ids)";
				}
			else
				{
				$result = 'ERROR';
				$result_reason = "batch_update_lead NO LEADS DEFINED";
				$data = "$lead_ids";
				echo "$result: $result_reason - $user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}

			if ( ($api_list_restrict > 0) and ($list_id_field >= 99) )
				{
				if (!preg_match("/ $list_id_field /",$allowed_lists))
					{
					$result = 'ERROR';
					$result_reason = "batch_update_lead NOT AN ALLOWED LIST ID";
					$data = "$list_id_field";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}
			if ( (preg_match("/Y/i",$list_exists_check)) and (strlen($list_id_field) > 0) )
				{
				$stmt="SELECT count(*) from vicidial_lists where list_id='$list_id_field';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($DB>0) {echo "DEBUG: batch_update_lead list_exists_check query - $row[0]|$stmt\n";}
				$list_exists_count = $row[0];
				if ($list_exists_count < 1)
					{
					$result = 'ERROR';
					$result_reason = "batch_update_lead NOT A DEFINED LIST ID, LIST EXISTS CHECK ENABLED";
					$data = "$list_id_field";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				}

			$search_found=0;
			$found_lead_ids='';
			if (strlen($lead_ids) > 0) # search for the lead_id
				{
				if (strlen($allowed_listsSQL) > 3)
					{$allowed_listsSQL = "and list_id IN($allowed_listsSQL)";}
				$stmt="SELECT lead_id from $vicidial_list_table where $lead_ids_SQL $allowed_listsSQL order by lead_id desc limit $records;";
				if ($DB>0) {echo "DEBUG: Checking for lead_ids - $lead_ids |$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$pc_recs = mysqli_num_rows($rslt);
				$n=0;
				while ($pc_recs > $n)
					{
					$row=mysqli_fetch_row($rslt);
					if ($n > 0)
						{$found_lead_ids .= ",";}
					$found_lead_ids .=	"$row[0]";
					$n++;
					$search_found++;
					}
				}
			### END searching ###

			if ( ($search_found > 0) and (strlen($found_lead_ids) > 0) )
				{
				$VL_update_SQL='';
				##### BEGIN update lead information in the system #####
				if (strlen($user_field)>0)			{$VL_update_SQL .= "user=\"$user_field\",";}
				if (strlen($list_id_field)>0)		{$VL_update_SQL .= "list_id=\"$list_id_field\",";}
				if (strlen($status)>0)				{$VL_update_SQL .= "status=\"$status\",";}
				if (strlen($vendor_lead_code)>0)	{$VL_update_SQL .= "vendor_lead_code=\"$vendor_lead_code\",";}
				if (strlen($source_id)>0)			{$VL_update_SQL .= "source_id=\"$source_id\",";}
				if (strlen($gmt_offset_now)>0)		{$VL_update_SQL .= "gmt_offset_now=\"$gmt_offset_now\",";}
				if (strlen($title)>0)				{$VL_update_SQL .= "title=\"$title\",";}
				if (strlen($first_name)>0)			{$VL_update_SQL .= "first_name=\"$first_name\",";}
				if (strlen($middle_initial)>0)		{$VL_update_SQL .= "middle_initial=\"$middle_initial\",";}
				if (strlen($last_name)>0)			{$VL_update_SQL .= "last_name=\"$last_name\",";}
				if (strlen($address1)>0)			{$VL_update_SQL .= "address1=\"$address1\",";}
				if (strlen($address2)>0)			{$VL_update_SQL .= "address2=\"$address2\",";}
				if (strlen($address3)>0)			{$VL_update_SQL .= "address3=\"$address3\",";}
				if (strlen($city)>0)				{$VL_update_SQL .= "city=\"$city\",";}
				if (strlen($state)>0)				{$VL_update_SQL .= "state=\"$state\",";}
				if (strlen($province)>0)			{$VL_update_SQL .= "province=\"$province\",";}
				if (strlen($postal_code)>0)			{$VL_update_SQL .= "postal_code=\"$postal_code\",";}
				if (strlen($country_code)>0)		{$VL_update_SQL .= "country_code=\"$country_code\",";}
				if (strlen($gender)>0)				{$VL_update_SQL .= "gender=\"$gender\",";}
				if (strlen($date_of_birth)>0)		{$VL_update_SQL .= "date_of_birth=\"$date_of_birth\",";}
				if (strlen($alt_phone)>0)			{$VL_update_SQL .= "alt_phone=\"$alt_phone\",";}
				if (strlen($email)>0)				{$VL_update_SQL .= "email=\"$email\",";}
				if (strlen($security_phrase)>0)		{$VL_update_SQL .= "security_phrase=\"$security_phrase\",";}
				if (strlen($comments)>0)			{$VL_update_SQL .= "comments=\"$comments\",";}
				if (strlen($rank)>0)				{$VL_update_SQL .= "rank=\"$rank\",";}
				if (strlen($owner)>0)				{$VL_update_SQL .= "owner=\"$owner\",";}
				if (strlen($called_count)>0)		{$VL_update_SQL .= "called_count=\"$called_count\",";}
				if (strlen($phone_code)>0)			{$VL_update_SQL .= "phone_code=\"$phone_code\",";}
				if ( (strlen($reset_lead) > 0 && $reset_lead == 'Y') )	{$VL_update_SQL .= "called_since_last_reset='N',";}
				$VL_update_SQL = preg_replace("/,$/","",$VL_update_SQL);
				$VL_update_SQL = preg_replace("/\"--BLANK--\"/",'""',$VL_update_SQL);
				$VL_update_SQL = preg_replace("/\n/","!N",$VL_update_SQL);

				$VLaffected_rows=0;

				if (strlen($VL_update_SQL)>6)
					{
					$stmt = "UPDATE $vicidial_list_table SET $VL_update_SQL where lead_id IN($found_lead_ids);";
					$result_reason = "batch_update_lead LEADS HAVE BEEN UPDATED";

					if ($DB>0) {echo "DEBUG: batch_update_lead query - $stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$VLaffected_rows = mysqli_affected_rows($link);

					$result = 'SUCCESS';
					$data = "$VLaffected_rows|$search_found|$found_lead_ids";
					echo "$result: $result_reason - $user|$data\n";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				##### END update lead information in the system #####
				}
			else
				{
				$result = 'ERROR';
				$result_reason = "batch_update_lead NO MATCHES FOUND IN THE SYSTEM";
				$data = "$lead_ids";
				echo "$result: $result_reason: |$user|$data\n";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		exit;
		}
	}
################################################################################
### END batch_update_lead
################################################################################





################################################################################
### check_phone_number - allows you to check if a phone number is valid and dialable
################################################################################
if ($function == 'check_phone_number')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and user_level > 7 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_check=$row[0];

		if ($allowed_check < 1)
			{
			$result = 'ERROR';
			$result_reason = "check_phone_number USER DOES NOT HAVE PERMISSION TO CHECK PHONE NUMBERS";
			echo "$result: $result_reason: |$user|$modify_leads|\n";
			$data = "$modify_leads";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT count(*) from vicidial_call_times where call_time_id='$local_call_time';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$valid_call_time=$row[0];

			if ($valid_call_time < 1)
				{
				$result = 'ERROR';
				$result_reason = "check_phone_number THIS IS NOT A VALID CALL TIME";
				echo "$result: $result_reason: |$user|$local_call_time|\n";
				$data = "$valid_call_time";
				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				exit;
				}
			else
				{
				if (strlen($phone_code)<1) {$phone_code='1';}
				if (strlen($tz_method)<1) {$tz_method='AREACODE';}
				if ( ($nanpa_ac_prefix_check == 'Y') or (preg_match("/NANPA/i",$tz_method)) )
					{
					$stmt="SELECT count(*) from vicidial_nanpa_prefix_codes;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$vicidial_nanpa_prefix_codes_count = $row[0];
					if ($vicidial_nanpa_prefix_codes_count < 10)
						{
						$nanpa_ac_prefix_check='N';
						$tz_method = preg_replace("/NANPA/",'',$tz_method);

						$result = 'ERROR';
						$result_reason = "check_phone_number NANPA options disabled, NANPA prefix data not loaded";
						echo "$result: $result_reason - $vicidial_nanpa_prefix_codes_count|$user\n";
						$data = "$inserted_alt_phones|$lead_id";
						api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);

						exit;
						}
					}

				$valid_number=1;
				if ( (strlen($phone_number)<6) || (strlen($phone_number)>16) )
					{
					$valid_number=0;
					$result_reason = "check_phone_number INVALID PHONE NUMBER LENGTH";
					}
				if ( ($usacan_prefix_check=='Y') and ($valid_number > 0) )
					{
					$USprefix = 	substr($phone_number, 3, 1);
					if ($DB>0) {echo "DEBUG: check_phone_number prefix check - $USprefix|$phone_number\n";}
					if ($USprefix < 2)
						{
						$valid_number=0;
						$result_reason = "check_phone_number INVALID PHONE NUMBER PREFIX";
						}
					}
				if ( ($usacan_areacode_check=='Y') and ($valid_number > 0) )
					{
					$phone_areacode = substr($phone_number, 0, 3);
					$stmt = "select count(*) from vicidial_phone_codes where areacode='$phone_areacode' and country_code='1';";
					if ($DB>0) {echo "DEBUG: check_phone_number areacode check query - $stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$valid_number=$row[0];
					if ($valid_number < 1)
						{
						$result_reason = "check_phone_number INVALID PHONE NUMBER AREACODE";
						}
					}
				if ( ($nanpa_ac_prefix_check=='Y') and ($valid_number > 0) )
					{
					$phone_areacode = substr($phone_number, 0, 3);
					$phone_prefix = substr($phone_number, 3, 3);
					$stmt = "SELECT count(*) from vicidial_nanpa_prefix_codes where areacode='$phone_areacode' and prefix='$phone_prefix';";
					if ($DB>0) {echo "DEBUG: check_phone_number areacode check query - $stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$valid_number=$row[0];
					if ($valid_number < 1)
						{
						$result_reason = "check_phone_number INVALID PHONE NUMBER NANPA AREACODE PREFIX";
						}
					}
				if ($valid_number < 1)
					{
					$result = 'ERROR';
					echo "$result: $result_reason - $phone_number|$user\n";
					$data = "$phone_number";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					exit;
					}
				else
					{
					### START checking for DNC if defined ###
					if ( ($dnc_check == 'Y') or ($dnc_check == 'AREACODE') )
						{
						if ($DB>0) {echo "DEBUG: Checking for system DNC\n";}
						if ($dnc_check == 'AREACODE')
							{
							$phone_areacode = substr($phone_number, 0, 3);
							$phone_areacode .= "XXXXXXX";
							$stmt="SELECT count(*) from vicidial_dnc where phone_number IN('$phone_number','$phone_areacode');";
							}
						else
							{$stmt="SELECT count(*) from vicidial_dnc where phone_number='$phone_number';";}
						if ($DB>0) {echo "DEBUG: check_phone_number query - $stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$dnc_found=$row[0];

						if ($dnc_found > 0)
							{
							$result = 'ERROR';
							$result_reason = "check_phone_number PHONE NUMBER IN DNC";
							echo "$result: $result_reason - $phone_number|$user\n";
							$data = "$phone_number";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					if ( ($campaign_dnc_check == 'Y') or ($campaign_dnc_check == 'AREACODE') )
						{
						if ($DB>0) {echo "DEBUG: Checking for campaign DNC\n";}

						$stmt="SELECT use_other_campaign_dnc from vicidial_campaigns where campaign_id='$campaign_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$use_other_campaign_dnc =	$row[0];
						$temp_campaign_id = $campaign_id;
						if (strlen($use_other_campaign_dnc) > 0) {$temp_campaign_id = $use_other_campaign_dnc;}

						if ($campaign_dnc_check == 'AREACODE')
							{
							$phone_areacode = substr($phone_number, 0, 3);
							$phone_areacode .= "XXXXXXX";
							$stmt="SELECT count(*) from vicidial_campaign_dnc where phone_number IN('$phone_number','$phone_areacode') and campaign_id='$temp_campaign_id';";
							}
						else
							{$stmt="SELECT count(*) from vicidial_campaign_dnc where phone_number='$phone_number' and campaign_id='$temp_campaign_id';";}
						if ($DB>0) {echo "DEBUG: check_phone_number query - $stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$row=mysqli_fetch_row($rslt);
						$dnc_found=$row[0];

						if ($dnc_found > 0)
							{
							$result = 'ERROR';
							$result_reason = "check_phone_number PHONE NUMBER IN CAMPAIGN DNC";
							echo "$result: $result_reason - $phone_number|$campaign_id|$user\n";
							$data = "$phone_number|$campaign_id";
							api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
							exit;
							}
						}
					### END checking for DNC if defined ###

					$tz_run=0;
					$result_reason='';
					$gmt_offset='';   $dialable='0';
					if (preg_match("/AREACODE/i",$tz_method))
						{
						$tz_run++;
						### get current gmt_offset of the phone_number
						$gmt_offset = lookup_gmt_api($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,'',$postal_code,$owner,$USprefix);

						### call function to determine if lead is dialable
						$dialable = dialable_gmt($DB,$link,$local_call_time,$gmt_offset,$state);

						$result_reason .= "AREACODE: $dialable|$gmt_offset#";
						}
					$gmt_offset='';   $dialable='0';
					if (preg_match("/POSTAL/i",$tz_method))
						{
						$tz_run++;

						if ( (strlen($postal_code)>4) and (strlen($postal_code)< 6) )
							{
							### get current gmt_offset of the phone_number
							$gmt_offset = lookup_gmt_api($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,'POSTAL',$postal_code,$owner,$USprefix);

							### call function to determine if lead is dialable
							$dialable = dialable_gmt($DB,$link,$local_call_time,$gmt_offset,$state);
							}

						$result_reason .= "POSTAL: $dialable|$gmt_offset#";
						}
					$gmt_offset='';   $dialable='0';
					if (preg_match("/TZCODE/i",$tz_method))
						{
						$tz_run++;

						if ( (strlen($owner)>0) and (strlen($owner)< 7) )
							{
							### get current gmt_offset of the phone_number
							$gmt_offset = lookup_gmt_api($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,'TZCODE',$postal_code,$owner,$USprefix);

							### call function to determine if lead is dialable
							$dialable = dialable_gmt($DB,$link,$local_call_time,$gmt_offset,$state);
							}

						$result_reason .= "TZCODE: $dialable|$gmt_offset#";
						}
					$gmt_offset='';   $dialable='0';
					if (preg_match("/NANPA/i",$tz_method))
						{
						$tz_run++;
						### get current gmt_offset of the phone_number
						$gmt_offset = lookup_gmt_api($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,'NANPA',$postal_code,$owner,$USprefix);

						### call function to determine if lead is dialable
						$dialable = dialable_gmt($DB,$link,$local_call_time,$gmt_offset,$state);

						$result_reason .= "NANPA: $dialable|$gmt_offset#";
						}

					$result = 'NOTICE';
					$result_reason .= "PHONE: $phone_number|$phone_code|$postal_code|$state|$owner|";
					echo "$result_reason\n";
					$data = "";
					api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
					}
				}
			}
		exit;
		}
	}
################################################################################
### END check_phone_number
################################################################################






################################################################################
### logged_in_agents - list of agents that are logged in to the system
################################################################################
if ($function == 'logged_in_agents')
	{
	if(strlen($source)<2)
		{
		$result = 'ERROR';
		$result_reason = "Invalid Source";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and vdc_agent_api_access='1' and view_reports='1' and user_level > 6 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "logged_in_agents USER DOES NOT HAVE PERMISSION TO GET AGENT INFO";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			$stmt="SELECT admin_viewable_groups,allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
			if ($DB) {$MAIN.="|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$row=mysqli_fetch_row($rslt);
			$LOGadmin_viewable_groups =		$row[0];
			$LOGallowed_campaigns =			$row[1];

			$LOGadmin_viewable_groupsSQL='';
			$whereLOGadmin_viewable_groupsSQL='';
			if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
				{
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
				$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
				$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
				}
			$LOGallowed_campaignsSQL='';
			$whereLOGallowed_campaignsSQL='';
			if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
				{
				$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
				$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
				$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
				$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
				}

			$search_UG_SQL='';
			if ( (strlen($user_groups)>0) and (strlen($user_groups)<10000) )
				{
				$user_groupsSQL = preg_replace("/\|/","','",$user_groups);
				$search_UG_SQL .= "and user_group IN('$user_groupsSQL')";
				}
			$search_CAMP_SQL='';
			if ( (strlen($campaigns)>0) and (strlen($campaigns)<10000) )
				{
				$and_CAMP_SQL = 'where';
				if (strlen($whereLOGallowed_campaignsSQL) > 10)
					{$and_CAMP_SQL = 'and';}
				$campaignsSQL = preg_replace("/\|/","','",$campaigns);
				$search_CAMP_SQL .= "$and_CAMP_SQL campaign_id IN('$campaignsSQL')";
				}

			$k=0;
			$output='';
			$DLset=0;
			if ($stage == 'csv')
				{$DL = ',';   $DLset++;}
			if ($stage == 'tab')
				{$DL = "\t";   $DLset++;}
			if ($stage == 'pipe')
				{$DL = '|';   $DLset++;}
			if ($DLset < 1)
				{$DL='|';   $stage='pipe';}
			if (strlen($time_format) < 1)
				{$time_format = 'HF';}
			if ($header == 'YES')
				{
				$header_sub_status='';
				if ($show_sub_status == 'YES')
					{$header_sub_status = $DL . 'pause_code' . $DL . 'sub_status';}
				$output .= 'user' . $DL . 'campaign_id' . $DL . 'session_id' . $DL . 'status' . $DL . 'lead_id' . $DL . 'callerid' . $DL . 'calls_today' . $DL . 'full_name' . $DL . 'user_group' . $DL . 'user_level' . $header_sub_status . "\n";
				}
			$Astatus=array();
			$Acallerid=array();
			$Alead_id=array();
			$Acampaign_id=array();
			$Acalls_today=array();
			$Aagent_log_id=array();
			$Aon_hook_agent=array();
			$Aring_callerid=array();
			$Apreview_lead_id=array();
			$Aconf_exten=array();
			$Auser=array();
			$Acomments=array();
			$Apause_code=array();
			$Artr_status=array();

			$stmt="SELECT status,callerid,lead_id,campaign_id,calls_today,agent_log_id,on_hook_agent,ring_callerid,preview_lead_id,conf_exten,user,comments from vicidial_live_agents $whereLOGallowed_campaignsSQL $search_CAMP_SQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {echo "$stmt\n";}
			$agents_to_list = mysqli_num_rows($rslt);
			$i=0;
			while ($agents_to_list > $i)
				{
				$row=mysqli_fetch_row($rslt);
				$Astatus[$i] =			$row[0];
				$Acallerid[$i] =		$row[1];
				$Alead_id[$i] =			$row[2];
				$Acampaign_id[$i] =		$row[3];
				$Acalls_today[$i] =		$row[4];
				$Aagent_log_id[$i] =	$row[5];
				$Aon_hook_agent[$i] =	$row[6];
				$Aring_callerid[$i] =	$row[7];
				$Apreview_lead_id[$i] =	$row[8];
				$Aconf_exten[$i] =		$row[9];
				$Auser[$i] =			$row[10];
				$Acomments[$i] =		$row[11];
				$Apause_code[$i] =		'';
				$Artr_status[$i] =		'';
				$i++;
				}

			$i=0;
			$printed_agents=0;
			while ($agents_to_list > $i)
				{
				$stmt="SELECT full_name,user_group,user_level from vicidial_users where user='$Auser[$i]' $LOGadmin_viewable_groupsSQL $search_UG_SQL;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {echo "$stmt\n";}
				$user_to_list = mysqli_num_rows($rslt);
				if ($user_to_list > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$full_name = 	$row[0];
					$user_group = 	$row[1];
					$user_level = 	$row[2];
					$agent_sub_status = '';

					if ($show_sub_status == 'YES')
						{
						$pause_code='';
						$rtr_status='';
						$stmt="SELECT sub_status from vicidial_agent_log where user='$Auser[$i]' and agent_log_id='$Aagent_log_id[$i]';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {echo "$stmt\n";}
						$agent_to_log = mysqli_num_rows($rslt);
						if ($agent_to_log > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$pause_code =		$row[0];
							}

						if ( ($Aon_hook_agent[$i] == 'Y') and (strlen($Aring_callerid[$i]) > 18) )
							{$rtr_status = "RING";}

						if ( ($Astatus[$i] == 'PAUSED') and ($Alead_id[$i] > 0) )
							{$rtr_status = 'DISPO';}

						if ( ($Astatus[$i] == 'PAUSED') and ($Apreview_lead_id[$i] > 0) )
							{
							$rtr_status = 'PREVIEW';
							$Alead_id[$i] = $Apreview_lead_id[$i];
							}

						if ($Astatus[$i] == 'INCALL')
							{
							if ($Alead_id[$i] > 0)
								{
								$threewaystmt="select UNIX_TIMESTAMP(last_call_time) from vicidial_live_agents where lead_id='$Alead_id[$i]' and status='INCALL' order by UNIX_TIMESTAMP(last_call_time) desc;";
								$threewayrslt=mysql_to_mysqli($threewaystmt, $link);
								if (mysqli_num_rows($threewayrslt)>1)
									{$rtr_status = '3-WAY';}
								}

							$stmt="SELECT count(*) from parked_channels where channel_group='$Acallerid[$i]';";
							$rslt=mysql_to_mysqli($stmt,$link);
							$row=mysqli_fetch_row($rslt);
							$parked_channel = $row[0];
							if ($parked_channel > 0)
								{$rtr_status = 'PARK';}
							else
								{
								if (preg_match("/CHAT/i",$Acomments[$i]))
									{
									$stmtCT="SELECT chat_id from vicidial_live_chats where chat_creator='$Auser[$i]' and lead_id='$Alead_id[$i]' order by chat_start_time desc limit 1;";
									if ($DB) {echo "$stmtCT\n";}
									$rsltCT=mysql_to_mysqli($stmtCT,$link);
									$chatting_to_print = mysqli_num_rows($rslt);
									if ($chatting_to_print > 0)
										{
										$rowCT=mysqli_fetch_row($rsltCT);
										$Achat_id = $rowCT[0];

										$stmtCL="SELECT count(*) from vicidial_chat_log where chat_id='$Achat_id' and message LIKE \"%has left chat\";";
										if ($DB) {echo "$stmtCL\n";}
										$rsltCL=mysql_to_mysqli($stmtCL,$link);
										$rowCL=mysqli_fetch_row($rsltCL);
										$left_chat = $rowCL[0];

										if ($left_chat > 0)
											{
											$rtr_status =	'DEAD';
											}
										}
									}
								elseif (preg_match("/EMAIL/i",$Acomments[$i]))
									{$do_nothing=1;}
								else
									{
									$stmt="SELECT count(*) from vicidial_auto_calls where callerid='$Acallerid[$i]';";
									$rslt=mysql_to_mysqli($stmt,$link);
									$row=mysqli_fetch_row($rslt);
									$live_channel = $row[0];
									if ($live_channel < 1)
										{$rtr_status = 'DEAD';}
									else
										{
										if ($Acomments[$i] == 'MANUAL')
											{
											$stmt="SELECT uniqueid,channel from vicidial_auto_calls where callerid='$Acallerid[$i]' LIMIT 1;";
											$rslt=mysql_to_mysqli($stmt, $link);
											if ($DB) {echo "$stmt\n";}
											$mandial_to_check = mysqli_num_rows($rslt);
												if ($mandial_to_check > 0)
												{
												$rowvac=mysqli_fetch_row($rslt);
												if ( (strlen($rowvac[0])<5) and (strlen($rowvac[1])<5) )
													{
													$rtr_status =	'DIAL';
													}
												}
											}
										}
									}
								}
							}
						$agent_sub_status = "$DL$pause_code$DL$rtr_status";
						}

					$output .= "$Auser[$i]$DL$Acampaign_id[$i]$DL$Aconf_exten[$i]$DL$Astatus[$i]$DL$Alead_id[$i]$DL$Acallerid[$i]$DL$Acalls_today[$i]$DL$full_name$DL$user_group$DL$user_level$agent_sub_status\n";
					$printed_agents++;
					}
				$i++;
				}
			if ($printed_agents > 0)
				{
				echo "$output";

				$result = 'SUCCESS';
				$data = "$user|$agents_to_list|$stage";
				$result_reason = "logged_in_agents $output";

				api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
				}
			}
		if ($printed_agents < 1)
			{
			$result = 'ERROR';
			$result_reason = "logged_in_agents NO LOGGED IN AGENTS";
			$data = "$user|$agent_user";
			echo "$result: $result_reason: $data\n";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		}
	exit;
	}
################################################################################
### END logged_in_agents
################################################################################



################################################################################
### call_status_stats - campaign and ingroup call stats by hour and status
################################################################################
if ($function == 'call_status_stats')
	{
#	header("Content-Type: text/plain");
	if(strlen($campaigns)<2)
		{
		$result = 'ERROR';
		$result_reason = "call_status_stats INVALID OR MISSING CAMPAIGNS";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		$stmt="SELECT count(*) from vicidial_users where user='$user' and view_reports='1' and user_level > 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "call_status_stats USER DOES NOT HAVE PERMISSION TO VIEW STATS";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if (!$query_date) {$query_date=date("Y-m-d");}
			if (!$query_time) {$query_time="00:00:00";}
			if (!$end_date) {$end_date=$query_date;}
			if (!$end_time) {$end_time="23:59:59";}
			$outbound_array=array();
			$inbound_array=array();
			$temp_hour_array=array();
			$temp_stat_array=array();
/*
			$total_calls_array=array();
			$total_hr_array=array();
			$total_stat_array=array();
*/

			$campaign_array=explode("-", $campaigns);
			$campaign_SQL=" and campaign_id in ('".implode("', '", $campaign_array)."') ";
			if (in_array("ALLCAMPAIGNS", $campaign_array) || preg_match('/\-\-\-ALL\-\-\-/', $campaigns))
				{
				$campaign_SQL="";
				$campaign_stmt="select campaign_id from vicidial_campaigns order by campaign_id";
				if ($DB>0) {echo $campaign_stmt."\n";}
				$campaign_rslt=mysql_to_mysqli($campaign_stmt, $link);
				while ($row=mysqli_fetch_row($campaign_rslt))
					{
					$outbound_array["$row[0]"][0]=0;
					$outbound_array["$row[0]"][1]=0;
					}
				}
			else
				{
				for ($i=0; $i<count($campaign_array); $i++)
					{
					$outbound_array["$campaign_array[$i]"][0]=0;
					$outbound_array["$campaign_array[$i]"][1]=0;
					}
				}
			ksort($outbound_array);

			$ha_stmt="select distinct status from vicidial_statuses where human_answered='Y' UNION select distinct status from vicidial_campaign_statuses where human_answered='Y' $campaign_SQL";
			if ($DB>0) {echo $ha_stmt."\n";}
			$ha_rslt=mysql_to_mysqli($ha_stmt, $link);
			$human_ans_array=array();
			while ($ha_row=mysqli_fetch_row($ha_rslt))
				{
				array_push($human_ans_array, $ha_row[0]);
				}

			if (strlen($statuses)>0)
				{
				$status_array=explode("-", $statuses);
				$status_SQL=" and status in ('".implode("', '", $status_array)."') ";
				}
			else
				{
				$status_SQL="";
				}

			if (strlen($ingroups)==0)
				{
				$ingroup_array=array();
				$ingroup_stmt="select closer_campaigns from vicidial_campaigns where closer_campaigns is not null and closer_campaigns!='' $campaign_SQL";
				if ($DB>0) {echo $ingroup_stmt."\n";}
				$ingroup_rslt=mysql_to_mysqli($ingroup_stmt, $link);
				while($ingroup_row=mysqli_fetch_row($ingroup_rslt))
					{
					$ingroup_row[0]=preg_replace('/ -$/', "", trim($ingroup_row[0]));
					$ing_ary=explode(" ", $ingroup_row[0]);
					$ingroup_array=array_merge($ingroup_array, $ing_ary);
					}
				$ingroup_array=array_unique($ingroup_array);
				$ingroup_array=array_values($ingroup_array);
				}
			else
				{
				$ingroup_array=explode("-", $ingroups);
				}

			$ingroup_SQL=" and campaign_id in ('".implode("', '", $ingroup_array)."') ";
			for ($i=0; $i<count($ingroup_array); $i++)
				{
				$inbound_array["$ingroup_array[$i]"][0]=0;
				$inbound_array["$ingroup_array[$i]"][1]=0;
				}
			ksort($inbound_array);

			$outb_stmt="select campaign_id, status, substr(call_date, 12, 2) as hour, count(*) from vicidial_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $campaign_SQL $status_SQL group by campaign_id, status, hour order by campaign_id, status, hour";
			if ($DB>0) {echo $outb_stmt."\n";}
			$outb_rslt=mysql_to_mysqli($outb_stmt, $link);

			for ($i=0; $i<24; $i++)
				{
				$hrkey=substr("0$i", -2);
				$total_hr_array["$hrkey"]=0;
				}

			while($outb_row=mysqli_fetch_row($outb_rslt))
				{
				$outbound_array["$outb_row[0]"][0]+=$outb_row[3];
				if (in_array($outb_row[1], $human_ans_array))
					{
					$outbound_array["$outb_row[0]"][1]+=$outb_row[3];
					}
				$temp_stat_array["$outb_row[0]"]["$outb_row[1]"]+=$outb_row[3];
				$temp_hour_array["$outb_row[0]"]["$outb_row[2]"]+=$outb_row[3];
				}

			$inb_stmt="select campaign_id, status, substr(call_date, 12, 2) as hour, count(*) from vicidial_closer_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $ingroup_SQL $status_SQL group by campaign_id, status, hour order by campaign_id, status, hour";
			if ($DB>0) {echo $inb_stmt."\n";}
			$inb_rslt=mysql_to_mysqli($inb_stmt, $link);
			while($inb_row=mysqli_fetch_row($inb_rslt))
				{
				$inbound_array["$inb_row[0]"][0]+=$inb_row[3];
				if (in_array($inb_row[1], $human_ans_array))
					{
					$inbound_array["$inb_row[0]"][1]+=$inb_row[3];
					}
				$temp_stat_array["$inb_row[0]"]["$inb_row[1]"]+=$inb_row[3];
				$temp_hour_array["$inb_row[0]"]["$inb_row[2]"]+=$inb_row[3];
				}
			}

#			while(list($key, $val)=each($outbound_array)) {
			foreach($outbound_array as $key => $val) {
				$hour_str="";
				$status_str="";
				for ($i=0; $i<24; $i++)
					{
					$hrkey=substr("0$i", -2);
					$hrs=$temp_hour_array["$key"]["$hrkey"]+=0;
					$hour_str.="$hrkey-$hrs,";
					}
				$hour_str=substr($hour_str, 0, -1);

				if (!is_array($temp_stat_array["$key"])) $temp_stat_array["$key"] = array();
				$temp_ary_ct = count($temp_stat_array["$key"]);
				if ($temp_ary_ct > 0)
					{
					ksort($temp_stat_array["$key"]);
#					while(list($statkey, $statval)=each($temp_stat_array{"$key"})) 
					foreach($temp_stat_array["$key"] as $statkey => $statval)
						{
						$status_str.=$statkey."-".$temp_stat_array["$key"]["$statkey"].",";
						}
					}
				$status_str=substr($status_str, 0, -1);

				echo $key."|".$outbound_array[$key][0]."|".$outbound_array[$key][1]."|".$hour_str."|".$status_str."|\n";
			}

#			while(list($key, $val)=each($inbound_array)) {
			foreach($inbound_array as $key => $val) {
				$hour_str="";
				$status_str="";
				for ($i=0; $i<24; $i++)
					{
					$hrkey=substr("0$i", -2);
					$hrs=$temp_hour_array["$key"]["$hrkey"]+=0;
					$hour_str.="$hrkey-$hrs,";
					}
				$hour_str=substr($hour_str, 0, -1);

				if (!is_array($temp_stat_array["$key"])) $temp_stat_array["$key"] = array();
				$temp_ary_ct = count($temp_stat_array["$key"]);
				if ($temp_ary_ct > 0)
					{
					ksort($temp_stat_array["$key"]);
					# while(list($statkey, $statval)=each($temp_stat_array{"$key"})) 
					foreach($temp_stat_array["$key"] as $statkey => $statval)
						{
						$status_str.=$statkey."-".$temp_stat_array["$key"]["$statkey"].",";
						}
					}
				$status_str=substr($status_str, 0, -1);

				echo $key."|".$inbound_array[$key][0]."|".$inbound_array[$key][1]."|".$hour_str."|".$status_str."|\n";
			}

		$result = 'SUCCESS';
		$data = "$user|$stage";
		$result_reason = "call_status_stats";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		}
	exit;
	}
################################################################################
### END call_status_stats
################################################################################


################################################################################
### call_dispo_report - call disposition breakdown report
################################################################################
if ($function == 'call_dispo_report')
	{
	if(strlen($campaigns)<2 && strlen($ingroups)<2 && strlen($did_ids)<1 && strlen($did_patterns)<2)
		{
		$result = 'ERROR';
		$result_reason = "call_dispo_report INVALID OR MISSING CAMPAIGNS, INGROUPS, OR DIDS";
		echo "$result: $result_reason - $source\n";
		api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
		echo "ERROR: Invalid Source: |$source|\n";
		exit;
		}
	else
		{
		if ( (!preg_match("/ $function /",$api_allowed_functions)) and (!preg_match("/ALL_FUNCTIONS/",$api_allowed_functions)) )
			{
			$result = 'ERROR';
			$result_reason = "auth USER DOES NOT HAVE PERMISSION TO USE THIS FUNCTION";
			echo "$result: $result_reason: |$user|$function|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}

		$stmt="SELECT count(*) from vicidial_users where user='$user' and view_reports='1' and user_level > 8 and active='Y';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$allowed_user=$row[0];
		if ($allowed_user < 1)
			{
			$result = 'ERROR';
			$result_reason = "call_status_stats USER DOES NOT HAVE PERMISSION TO VIEW STATS";
			echo "$result: $result_reason: |$user|$allowed_user|\n";
			$data = "$allowed_user";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			exit;
			}
		else
			{
			if (!$query_date) {$query_date=date("Y-m-d");}
			if (!$query_time) {$query_time="00:00:00";}
			if (!$end_date) {$end_date=$query_date;}
			if (!$end_time) {$end_time="23:59:59";}
			if ($show_percentages && !$status_breakdown) {$show_percentages="";}

			# COMPILE INBOUND CAMPAIGN CLAUSE
			$skip_inbound=0;
			if (strlen($ingroups)>0)
				{
				$ingroup_array=explode("-", $ingroups);
				$inb_SQL="and campaign_id in ('".implode("', '", $ingroup_array)."')";
				if (in_array("ALLGROUPS", $ingroup_array) || preg_match('/\-\-\-ALL\-\-\-/', $ingroups))
					{
					$inb_SQL="";
					}
				}
			else
				{
				$skip_inbound=1;
				}
			########################

			# COMPILE OUTBOUND CAMPAIGN CLAUSE
			$skip_outbound=0;  #
			if (strlen($campaigns)>0)
				{
				$campaign_array=explode("-", $campaigns);
				$campaign_SQL=" and campaign_id in ('".implode("', '", $campaign_array)."') ";
				if (in_array("ALLCAMPAIGNS", $campaign_array) || preg_match('/\-\-\-ALL\-\-\-/', $campaigns))
					{
					$campaign_SQL="";
					}

				}
			else
				{
				$skip_outbound=1;
				}
			########################

			# COMPILE DID CLAUSE
			$skip_dids=0;
			if (strlen($did_patterns)>0 || strlen($did_ids)>0)
				{
				$did_id_array=explode("-", $did_ids);
				$did_pattern_array=explode("-", $did_patterns);
				$did_stmt="select did_id, did_pattern from vicidial_inbound_dids where did_pattern in ('".implode("','", $did_pattern_array)."')";
				if ($DB) {$rpt_str.=$did_stmt."<BR>\n";}
				$did_rslt=mysql_to_mysqli($did_stmt, $link);
				while ($did_row=mysqli_fetch_row($did_rslt))
					{
					if (!in_array($did_row[0], $did_id_array))
						{
						array_push($did_id_array, $did_row[0]);
						array_push($did_pattern_array, $did_row[1]);
						}
					}
				}

			if (!is_array($did_id_array)) $did_id_array = array();
			if (count($did_id_array)>0 && $skip_inbound) # DON'T DO A REPORT FOR INGROUPS AND DIDS YET.
				{
				$did_SQL="and did_id in ('".implode("', '", $did_id_array)."')";
				if (in_array("ALLDIDS", $did_id_array) || preg_match('/\-\-\-ALL\-\-\-/', $did_ids) || in_array("ALLPATTERNS", $did_pattern_array) || preg_match('/\-\-\-ALL\-\-\-/', $did_patterns))
					{
					$did_SQL="";
					}
				}
			else
				{
				$skip_dids=1;
				}
			########################

			# COMPILE STATUS CLAUSE
			if (strlen($categories)>0 || strlen($statuses)>0)
				{
				$status_array=explode("-", $statuses);
				$categories_array=explode("-", $categories);
				$cat_stmt="select distinct status from vicidial_statuses where category in ('".implode("','", $categories_array)."') UNION select distinct status from vicidial_campaign_statuses where category in ('".implode("','", $categories_array)."') $campaign_SQL";
				if ($DB) {$rpt_str.=$cat_stmt."<BR>\n";}
				$cat_rslt=mysql_to_mysqli($cat_stmt, $link);
				while($cat_row=mysqli_fetch_row($cat_rslt))
					{
					if (!in_array($cat_row[0], $status_array))
						{
						array_push($status_array, $cat_row[0]);
						}
					}
				}

			if (!is_array($status_array)) $status_array = array();
			if ($status_array && count($status_array)>0) 
				{
				$status_SQL=" and status in ('".implode("', '", $status_array)."') ";
				if (in_array("ALLSTATUSES", $status_array) || preg_match('/\-\-\-ALL\-\-\-/', $statuses) || in_array("ALLCATEGORIES", $categories_array) || preg_match('/\-\-\-ALL\-\-\-/', $categories))
					{
					$status_SQL="";
					}
				}
			########################

			# COMPILE USER CLAUSE
			if (strlen($user_groups)>0 || strlen($users)>0)
				{
				$user_array=explode("-", $users);
				$user_group_array=explode("-", $user_groups);
				$ug_stmt="select user from vicidial_users where user_group in ('".implode("', '", $user_group_array)."')";
				if ($DB) {$rpt_str.=$ug_stmt."<BR>\n";}
				$ug_rslt=mysql_to_mysqli($ug_stmt, $link);
				while ($ug_row=mysqli_fetch_row($ug_rslt))
					{
					if (!in_array($ug_row[0], $user_array))
						{
						array_push($user_array, $ug_row[0]);
						}
					}
				}
			if (!is_array($user_array)) $user_array = array();
			if ($user_array && count($user_array)>0) 
				{
				$user_SQL=" and user in ('".implode("', '", $user_array)."') ";
				if (in_array("ALLUSERS", $user_array) || preg_match('/\-\-\-ALL\-\-\-/', $users) || in_array("ALLGROUPS", $user_group_array) || preg_match('/\-\-\-ALL\-\-\-/', $user_groups))
					{
					$user_SQL="";
					}
				}
			########################

			$outbound_ct_array=array();
			$inbound_ct_array=array();
			$did_ct_array=array();
			$status_ct_array=array();
			$grand_total_array=array();
			$grand_total_calls=0;
			if (!$skip_outbound)
				{
				$stmt="select campaign_id, status, count(*) from vicidial_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $campaign_SQL $status_SQL $user_SQL group by campaign_id, status order by campaign_id, status asc";
				if ($DB) {$rpt_str.=$stmt."<BR>\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				while ($row=mysqli_fetch_row($rslt))
					{
					$outbound_ct_array["$row[0]"]["TOTAL CALLS"]+=$row[2];
					$grand_total_calls+=$row[2];
					if ($status_breakdown)
						{
						if (!in_array("$row[1]", $status_ct_array))
							{
							array_push($status_ct_array, "$row[1]");
							}
						$outbound_ct_array["$row[0]"]["$row[1]"]+=$row[2];
						$grand_total_array["$row[1]"]+=$row[2];
						}
					}
				}
			if (!$skip_inbound)
				{
				$stmt="select campaign_id, status, count(*) from vicidial_closer_log where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $inb_SQL $status_SQL $user_SQL group by campaign_id, status order by campaign_id, status asc";
				if ($DB) {$rpt_str.=$stmt."<BR>\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				while ($row=mysqli_fetch_row($rslt))
					{
					$inbound_ct_array["$row[0]"]["TOTAL CALLS"]+=$row[2];
					$grand_total_calls+=$row[2];
					if ($status_breakdown)
						{
						if (!in_array("$row[1]", $status_ct_array))
							{
							array_push($status_ct_array, "$row[1]");
							}
						$inbound_ct_array["$row[0]"]["$row[1]"]+=$row[2];
						$grand_total_array["$row[1]"]+=$row[2];
						}
					}
				}
			if (!$skip_dids)
				{
				$stmt="select did_id, extension, campaign_id, status, count(*) from vicidial_did_log vdl, vicidial_closer_log vcl where vdl.call_date>='$query_date $query_time' and vdl.call_date<='$end_date $end_time' $did_SQL $status_SQL $user_SQL and vcl.uniqueid=vdl.uniqueid group by campaign_id, status order by campaign_id, status asc";
				if ($DB) {$rpt_str.=$stmt."<BR>\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				while ($row=mysqli_fetch_row($rslt))
					{
					$did_ct_array["$row[1]"]["TOTAL CALLS"]+=$row[4];
					$grand_total_calls+=$row[4];
					if ($status_breakdown)
						{
						if (!in_array("$row[1]", $status_ct_array))
							{
							array_push($status_ct_array, "$row[3]");
							}
						$did_ct_array["$row[1]"]["$row[3]"]+=$row[4];
						$grand_total_array["$row[3]"]+=$row[4];
						}
					}
				}

			if (!is_array($status_ct_array)) $status_ct_array = array();
			$rpt_str.="CAMPAIGN,TOTAL CALLS";
			if ($status_breakdown)
				{
				for ($i=0; $i<count($status_ct_array); $i++)
					{
					$rpt_str.=",$status_ct_array[$i]";
					}
				}
			$rpt_str.="\n";
#			while (list($key, $val)=each($outbound_ct_array)) 
			foreach($outbound_ct_array as $key => $val)
				{
				$total_calls=$outbound_ct_array[$key]["TOTAL CALLS"];
				$rpt_str.="$key,".$outbound_ct_array[$key]["TOTAL CALLS"];
				unset($outbound_ct_array[$key]["TOTAL CALLS"]);
				if ($status_breakdown)
					{
					for ($i=0; $i<count($status_ct_array); $i++)
						{
						$outbound_ct_array[$key]["$status_ct_array[$i]"]+=0;
						}
					ksort($outbound_ct_array[$key]);
#					while (list($key2, $val2)=each($outbound_ct_array{$key})) 
					foreach($outbound_ct_array[$key] as $key2 => $val2)
						{
						$rpt_str.=",$val2";
						if ($show_percentages)
							{
							$rpt_str.=" (";
							$rpt_str.=sprintf("%.1f", (100*$val2/$total_calls));
							$rpt_str.="%)";
							}
						}
					}
				$rpt_str.="\n";
				}
#			while (list($key, $val)=each($inbound_ct_array)) 
			foreach($inbound_ct_array as $key => $val)
				{
				$total_calls=$inbound_ct_array[$key]["TOTAL CALLS"];
				$rpt_str.="$key,".$inbound_ct_array[$key]["TOTAL CALLS"];
				unset($inbound_ct_array[$key]["TOTAL CALLS"]);
				if ($status_breakdown)
					{
					for ($i=0; $i<count($status_ct_array); $i++)
						{
						$inbound_ct_array[$key]["$status_ct_array[$i]"]+=0;
						}
					ksort($inbound_ct_array[$key]);
#					while (list($key2, $val2)=each($inbound_ct_array{$key})) 
					foreach($inbound_ct_array[$key] as $key2 => $val2)
						{
						$rpt_str.=",$val2";
						if ($show_percentages)
							{
							$rpt_str.=" (";
							$rpt_str.=sprintf("%.1f", (100*$val2/$total_calls));
							$rpt_str.="%)";
							}
						}
					}
				$rpt_str.="\n";
				}
#			while (list($key, $val)=each($did_ct_array)) 
			foreach($did_ct_array as $key => $val)
				{
				$total_calls=$did_ct_array[$key]["TOTAL CALLS"];
				$rpt_str.="$key,".$did_ct_array[$key]["TOTAL CALLS"];
				unset($did_ct_array[$key]["TOTAL CALLS"]);
				if ($status_breakdown)
					{
					for ($i=0; $i<count($status_ct_array); $i++)
						{
						$did_ct_array[$key]["$status_ct_array[$i]"]+=0;
						}
					ksort($did_ct_array[$key]);
#					while (list($key2, $val2)=each($did_ct_array{$key})) 
					foreach($did_ct_array[$key] as $key2 => $val2)
						{
						$rpt_str.=",$val2";
						if ($show_percentages)
							{
							$rpt_str.=" (";
							$rpt_str.=sprintf("%.1f", (100*$val2/$total_calls));
							$rpt_str.="%)";
							}
						}
					}
				$rpt_str.="\n";
				}
			$rpt_str.="TOTAL,$grand_total_calls";
			ksort($grand_total_array);
#			while (list($key, $val)=each($grand_total_array)) 
			foreach($grand_total_array as $key => $val)
				{
				$rpt_str.=",$val";
				if ($show_percentages)
					{
					$rpt_str.=" (";
					$rpt_str.=sprintf("%.1f", (100*$val/$grand_total_calls));
					$rpt_str.="%)";
					}
				}

			if ($file_download>0)
				{
				$CSVfilename = "API_call_dispo_report_$ENTRYdate.csv";
				// We'll be outputting a TXT file
				header('Content-type: application/octet-stream');

				// It will be called LIST_101_20090209-121212.txt
				header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				ob_clean();
				flush();

				echo "$rpt_str";
				exit;
				}
			else
				{
				header('Content-type: text/plain');
				echo "$rpt_str";
				if ($DB)
					{
					print_r($outbound_ct_array);
					print_r($inbound_ct_array);
					print_r($did_ct_array);
					}
				}
			$result = 'SUCCESS';
			$data = "$user|$stage";
			$result_reason = "call_dispo_report";
			api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);
			}
		}
	exit;
	}
################################################################################
### END call_dispo_report
################################################################################


$result = 'ERROR';
$result_reason = "NO FUNCTION SPECIFIED";
echo "$result: $result_reason\n";
api_log($link,$api_logging,$api_script,$user,$agent_user,$function,$value,$result,$result_reason,$source,$data);




if ($format=='debug')
	{
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $StarTtime);
	echo "\n<!-- script runtime: $RUNtime seconds -->";
	echo "\n</body>\n</html>\n";
	}

exit;







##### FUNCTIONS #####

##### LOOKUP GMT, FINDS THE CURRENT GMT OFFSET FOR A PHONE NUMBER #####

function lookup_gmt_api($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,$tz_method,$postal_code,$owner,$USprefix)
{
require("dbconnect_mysqli.php");

$postalgmt_found=0;
if ( (preg_match("/POSTAL/i",$tz_method)) && (strlen($postal_code)>4) )
	{
	if (preg_match('/^1$/', $phone_code))
		{
		$stmt="select postal_code,state,GMT_offset,DST,DST_range,country,country_code from vicidial_postal_codes where country_code='$phone_code' and postal_code LIKE \"$postal_code%\";";
		$rslt=mysql_to_mysqli($stmt, $link);
		$pc_recs = mysqli_num_rows($rslt);
		if ($pc_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$gmt_offset =	$row[2];	 $gmt_offset = preg_replace('/\+/i', '',$gmt_offset);
			$dst =			$row[3];
			$dst_range =	$row[4];
			$PC_processed++;
			$postalgmt_found++;
			$post++;
			}
		}
	}
if ( ($tz_method=="TZCODE") && (strlen($owner)>1) )
	{
	$dst_range='';
	$dst='N';
	$gmt_offset=0;

	$stmt="select GMT_offset from vicidial_phone_codes where tz_code='$owner' and country_code='$phone_code' limit 1;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$pc_recs = mysqli_num_rows($rslt);
	if ($pc_recs > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$gmt_offset =	$row[0];	 $gmt_offset = preg_replace('/\+/i', '',$gmt_offset);
		$PC_processed++;
		$postalgmt_found++;
		$post++;
		}

	$stmt = "select distinct DST_range from vicidial_phone_codes where tz_code='$owner' and country_code='$phone_code' order by DST_range desc limit 1;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$pc_recs = mysqli_num_rows($rslt);
	if ($pc_recs > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$dst_range =	$row[0];
		if (strlen($dst_range)>2) {$dst = 'Y';}
		}
	}
if ( (preg_match("/NANPA/i",$tz_method)) && (strlen($USarea)>2) && (strlen($USprefix)>2) )
	{
	$stmt="select GMT_offset,DST from vicidial_nanpa_prefix_codes where areacode='$USarea' and prefix='$USprefix';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$pc_recs = mysqli_num_rows($rslt);
	if ($pc_recs > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$gmt_offset =	$row[0];	 $gmt_offset = preg_replace('/\+/i', '',$gmt_offset);
		$dst =			$row[1];
		$dst_range =	'';
		if ($dst == 'Y')
			{$dst_range =	'SSM-FSN';}
		$PC_processed++;
		$postalgmt_found++;
		$post++;
		}
	}
if ($postalgmt_found < 1)
	{
	$PC_processed=0;
	### UNITED STATES ###
	if ($phone_code =='1')
		{
		$stmt="select country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$phone_code' and areacode='$USarea';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$pc_recs = mysqli_num_rows($rslt);
		if ($pc_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$gmt_offset =	$row[4];	 $gmt_offset = preg_replace('/\+/i', '',$gmt_offset);
			$dst =			$row[5];
			$dst_range =	$row[6];
			$PC_processed++;
			}
		}
	### MEXICO ###
	if ($phone_code =='52')
		{
		$stmt="select country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$phone_code' and areacode='$USarea';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$pc_recs = mysqli_num_rows($rslt);
		if ($pc_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$gmt_offset =	$row[4];	 $gmt_offset = preg_replace('/\+/i', '',$gmt_offset);
			$dst =			$row[5];
			$dst_range =	$row[6];
			$PC_processed++;
			}
		}
	### AUSTRALIA ###
	if ($phone_code =='61')
		{
		$stmt="select country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$phone_code' and state='$state';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$pc_recs = mysqli_num_rows($rslt);
		if ($pc_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$gmt_offset =	$row[4];	 $gmt_offset = preg_replace('/\+/i', '',$gmt_offset);
			$dst =			$row[5];
			$dst_range =	$row[6];
			$PC_processed++;
			}
		}
	### ALL OTHER COUNTRY CODES ###
	if (!$PC_processed)
		{
		$PC_processed++;
		$stmt="select country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$phone_code';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$pc_recs = mysqli_num_rows($rslt);
		if ($pc_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$gmt_offset =	$row[4];	 $gmt_offset = preg_replace('/\+/i', '',$gmt_offset);
			$dst =			$row[5];
			$dst_range =	$row[6];
			$PC_processed++;
			}
		}
	}

### Find out if DST to raise the gmt offset ###
$AC_GMT_diff = ($gmt_offset - $LOCAL_GMT_OFF_STD);
$AC_localtime = mktime(($Shour + $AC_GMT_diff), $Smin, $Ssec, $Smon, $Smday, $Syear);
	$hour = date("H",$AC_localtime);
	$min = date("i",$AC_localtime);
	$sec = date("s",$AC_localtime);
	$mon = date("m",$AC_localtime);
	$mday = date("d",$AC_localtime);
	$wday = date("w",$AC_localtime);
	$year = date("Y",$AC_localtime);
$dsec = ( ( ($hour * 3600) + ($min * 60) ) + $sec );

$AC_processed=0;
if ( (!$AC_processed) and ($dst_range == 'SSM-FSN') )
	{
	if ($DBX) {print "     Second Sunday March to First Sunday November\n";}
	#**********************************************************************
	# SSM-FSN
	#     This is returns 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect.
	#     Based on Second Sunday March to First Sunday November at 2 am.
	#     INPUTS:
	#       mm              INTEGER       Month.
	#       dd              INTEGER       Day of the month.
	#       ns              INTEGER       Seconds into the day.
	#       dow             INTEGER       Day of week (0=Sunday, to 6=Saturday)
	#     OPTIONAL INPUT:
	#       timezone        INTEGER       hour difference UTC - local standard time
	#                                      (DEFAULT is blank)
	#                                     make calculations based on UTC time,
	#                                     which means shift at 10:00 UTC in April
	#                                     and 9:00 UTC in October
	#     OUTPUT:
	#                       INTEGER       1 = DST, 0 = not DST
	#
	# S  M  T  W  T  F  S
	# 1  2  3  4  5  6  7
	# 8  9 10 11 12 13 14
	#15 16 17 18 19 20 21
	#22 23 24 25 26 27 28
	#29 30 31
	#
	# S  M  T  W  T  F  S
	#    1  2  3  4  5  6
	# 7  8  9 10 11 12 13
	#14 15 16 17 18 19 20
	#21 22 23 24 25 26 27
	#28 29 30 31
	#
	#**********************************************************************

		$USACAN_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 3 || $mm > 11) {
		$USACAN_DST=0;
		} elseif ($mm >= 4 and $mm <= 10) {
		$USACAN_DST=1;
		} elseif ($mm == 3) {
		if ($dd > 13) {
			$USACAN_DST=1;
		} elseif ($dd >= ($dow+8)) {
			if ($timezone) {
			if ($dow == 0 and $ns < (7200+$timezone*3600)) {
				$USACAN_DST=0;
			} else {
				$USACAN_DST=1;
			}
			} else {
			if ($dow == 0 and $ns < 7200) {
				$USACAN_DST=0;
			} else {
				$USACAN_DST=1;
			}
			}
		} else {
			$USACAN_DST=0;
		}
		} elseif ($mm == 11) {
		if ($dd > 7) {
			$USACAN_DST=0;
		} elseif ($dd < ($dow+1)) {
			$USACAN_DST=1;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (7200+($timezone-1)*3600)) {
				$USACAN_DST=1;
			} else {
				$USACAN_DST=0;
			}
			} else { # local time calculations
			if ($ns < 7200) {
				$USACAN_DST=1;
			} else {
				$USACAN_DST=0;
			}
			}
		} else {
			$USACAN_DST=0;
		}
		} # end of month checks
	if ($DBX) {print "     DST: $USACAN_DST\n";}
	if ($USACAN_DST) {$gmt_offset++;}
	$AC_processed++;
	}

if ( (!$AC_processed) and ($dst_range == 'FSA-LSO') )
	{
	if ($DBX) {print "     First Sunday April to Last Sunday October\n";}
	#**********************************************************************
	# FSA-LSO
	#     This is returns 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect.
	#     Based on first Sunday in April and last Sunday in October at 2 am.
	#**********************************************************************

		$USA_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 4 || $mm > 10) {
		$USA_DST=0;
		} elseif ($mm >= 5 and $mm <= 9) {
		$USA_DST=1;
		} elseif ($mm == 4) {
		if ($dd > 7) {
			$USA_DST=1;
		} elseif ($dd >= ($dow+1)) {
			if ($timezone) {
			if ($dow == 0 and $ns < (7200+$timezone*3600)) {
				$USA_DST=0;
			} else {
				$USA_DST=1;
			}
			} else {
			if ($dow == 0 and $ns < 7200) {
				$USA_DST=0;
			} else {
				$USA_DST=1;
			}
			}
		} else {
			$USA_DST=0;
		}
		} elseif ($mm == 10) {
		if ($dd < 25) {
			$USA_DST=1;
		} elseif ($dd < ($dow+25)) {
			$USA_DST=1;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (7200+($timezone-1)*3600)) {
				$USA_DST=1;
			} else {
				$USA_DST=0;
			}
			} else { # local time calculations
			if ($ns < 7200) {
				$USA_DST=1;
			} else {
				$USA_DST=0;
			}
			}
		} else {
			$USA_DST=0;
		}
		} # end of month checks

	if ($DBX) {print "     DST: $USA_DST\n";}
	if ($USA_DST) {$gmt_offset++;}
	$AC_processed++;
	}

if ( (!$AC_processed) and ($dst_range == 'LSM-LSO') )
	{
	if ($DBX) {print "     Last Sunday March to Last Sunday October\n";}
	#**********************************************************************
	#     This is s 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect.
	#     Based on last Sunday in March and last Sunday in October at 1 am.
	#**********************************************************************

		$GBR_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 3 || $mm > 10) {
		$GBR_DST=0;
		} elseif ($mm >= 4 and $mm <= 9) {
		$GBR_DST=1;
		} elseif ($mm == 3) {
		if ($dd < 25) {
			$GBR_DST=0;
		} elseif ($dd < ($dow+25)) {
			$GBR_DST=0;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$GBR_DST=0;
			} else {
				$GBR_DST=1;
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$GBR_DST=0;
			} else {
				$GBR_DST=1;
			}
			}
		} else {
			$GBR_DST=1;
		}
		} elseif ($mm == 10) {
		if ($dd < 25) {
			$GBR_DST=1;
		} elseif ($dd < ($dow+25)) {
			$GBR_DST=1;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$GBR_DST=1;
			} else {
				$GBR_DST=0;
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$GBR_DST=1;
			} else {
				$GBR_DST=0;
			}
			}
		} else {
			$GBR_DST=0;
		}
		} # end of month checks
		if ($DBX) {print "     DST: $GBR_DST\n";}
	if ($GBR_DST) {$gmt_offset++;}
	$AC_processed++;
	}
if ( (!$AC_processed) and ($dst_range == 'LSO-LSM') )
	{
	if ($DBX) {print "     Last Sunday October to Last Sunday March\n";}
	#**********************************************************************
	#     This is s 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect.
	#     Based on last Sunday in October and last Sunday in March at 1 am.
	#**********************************************************************

		$AUS_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 3 || $mm > 10) {
		$AUS_DST=1;
		} elseif ($mm >= 4 and $mm <= 9) {
		$AUS_DST=0;
		} elseif ($mm == 3) {
		if ($dd < 25) {
			$AUS_DST=1;
		} elseif ($dd < ($dow+25)) {
			$AUS_DST=1;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$AUS_DST=1;
			} else {
				$AUS_DST=0;
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$AUS_DST=1;
			} else {
				$AUS_DST=0;
			}
			}
		} else {
			$AUS_DST=0;
		}
		} elseif ($mm == 10) {
		if ($dd < 25) {
			$AUS_DST=0;
		} elseif ($dd < ($dow+25)) {
			$AUS_DST=0;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$AUS_DST=0;
			} else {
				$AUS_DST=1;
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$AUS_DST=0;
			} else {
				$AUS_DST=1;
			}
			}
		} else {
			$AUS_DST=1;
		}
		} # end of month checks
	if ($DBX) {print "     DST: $AUS_DST\n";}
	if ($AUS_DST) {$gmt_offset++;}
	$AC_processed++;
	}

if ( (!$AC_processed) and ($dst_range == 'FSO-LSM') )
	{
	if ($DBX) {print "     First Sunday October to Last Sunday March\n";}
	#**********************************************************************
	#   TASMANIA ONLY
	#     This is s 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect.
	#     Based on first Sunday in October and last Sunday in March at 1 am.
	#**********************************************************************

		$AUST_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 3 || $mm > 10) {
		$AUST_DST=1;
		} elseif ($mm >= 4 and $mm <= 9) {
		$AUST_DST=0;
		} elseif ($mm == 3) {
		if ($dd < 25) {
			$AUST_DST=1;
		} elseif ($dd < ($dow+25)) {
			$AUST_DST=1;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$AUST_DST=1;
			} else {
				$AUST_DST=0;
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$AUST_DST=1;
			} else {
				$AUST_DST=0;
			}
			}
		} else {
			$AUST_DST=0;
		}
		} elseif ($mm == 10) {
		if ($dd > 7) {
			$AUST_DST=1;
		} elseif ($dd >= ($dow+1)) {
			if ($timezone) {
			if ($dow == 0 and $ns < (7200+$timezone*3600)) {
				$AUST_DST=0;
			} else {
				$AUST_DST=1;
			}
			} else {
			if ($dow == 0 and $ns < 3600) {
				$AUST_DST=0;
			} else {
				$AUST_DST=1;
			}
			}
		} else {
			$AUST_DST=0;
		}
		} # end of month checks
	if ($DBX) {print "     DST: $AUST_DST\n";}
	if ($AUST_DST) {$gmt_offset++;}
	$AC_processed++;
	}

if ( (!$AC_processed) and ($dst_range == 'FSO-FSA') )
	{
	if ($DBX) {print "     Sunday in October to First Sunday in April\n";}
	#**********************************************************************
	# FSO-FSA
	#   2008+ AUSTRALIA ONLY (country code 61)
	#     This is returns 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect.
	#     Based on first Sunday in October and first Sunday in April at 1 am.
	#**********************************************************************

	$AUSE_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 4 or $mm > 10) {
	$AUSE_DST=1;
    } elseif ($mm >= 5 and $mm <= 9) {
	$AUSE_DST=0;
    } elseif ($mm == 4) {
	if ($dd > 7) {
	    $AUSE_DST=0;
	} elseif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 and $ns < (3600+$timezone*3600)) {
		    $AUSE_DST=1;
		} else {
		    $AUSE_DST=0;
		}
	    } else {
		if ($dow == 0 and $ns < 7200) {
		    $AUSE_DST=1;
		} else {
		    $AUSE_DST=0;
		}
	    }
	} else {
	    $AUSE_DST=1;
	}
    } elseif ($mm == 10) {
	if ($dd >= 8) {
	    $AUSE_DST=1;
	} elseif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 and $ns < (7200+$timezone*3600)) {
		    $AUSE_DST=0;
		} else {
		    $AUSE_DST=1;
		}
	    } else {
		if ($dow == 0 and $ns < 3600) {
		    $AUSE_DST=0;
		} else {
		    $AUSE_DST=1;
		}
	    }
	} else {
	    $AUSE_DST=0;
	}
    } # end of month checks
	if ($DBX) {print "     DST: $AUSE_DST\n";}
	if ($AUSE_DST) {$gmt_offset++;}
	$AC_processed++;
	}

if ( (!$AC_processed) and ($dst_range == 'FSO-TSM') )
	{
	if ($DBX) {print "     First Sunday October to Third Sunday March\n";}
	#**********************************************************************
	#     This is s 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect.
	#     Based on first Sunday in October and third Sunday in March at 1 am.
	#**********************************************************************

		$NZL_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 3 || $mm > 10) {
		$NZL_DST=1;
		} elseif ($mm >= 4 and $mm <= 9) {
		$NZL_DST=0;
		} elseif ($mm == 3) {
		if ($dd < 14) {
			$NZL_DST=1;
		} elseif ($dd < ($dow+14)) {
			$NZL_DST=1;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$NZL_DST=1;
			} else {
				$NZL_DST=0;
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$NZL_DST=1;
			} else {
				$NZL_DST=0;
			}
			}
		} else {
			$NZL_DST=0;
		}
		} elseif ($mm == 10) {
		if ($dd > 7) {
			$NZL_DST=1;
		} elseif ($dd >= ($dow+1)) {
			if ($timezone) {
			if ($dow == 0 and $ns < (7200+$timezone*3600)) {
				$NZL_DST=0;
			} else {
				$NZL_DST=1;
			}
			} else {
			if ($dow == 0 and $ns < 3600) {
				$NZL_DST=0;
			} else {
				$NZL_DST=1;
			}
			}
		} else {
			$NZL_DST=0;
		}
		} # end of month checks
	if ($DBX) {print "     DST: $NZL_DST\n";}
	if ($NZL_DST) {$gmt_offset++;}
	$AC_processed++;
	}

if ( (!$AC_processed) and ($dst_range == 'LSS-FSA') )
	{
	if ($DBX) {print "     Last Sunday in September to First Sunday in April\n";}
	#**********************************************************************
	# LSS-FSA
	#   2007+ NEW ZEALAND (country code 64)
	#     This is returns 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect.
	#     Based on last Sunday in September and first Sunday in April at 1 am.
	#**********************************************************************

	$NZLN_DST=0;
	$mm = $mon;
	$dd = $mday;
	$ns = $dsec;
	$dow= $wday;

    if ($mm < 4 || $mm > 9) {
	$NZLN_DST=1;
    } elseif ($mm >= 5 && $mm <= 9) {
	$NZLN_DST=0;
    } elseif ($mm == 4) {
	if ($dd > 7) {
	    $NZLN_DST=0;
	} elseif ($dd >= ($dow+1)) {
	    if ($timezone) {
		if ($dow == 0 && $ns < (3600+$timezone*3600)) {
		    $NZLN_DST=1;
		} else {
		    $NZLN_DST=0;
		}
	    } else {
		if ($dow == 0 && $ns < 7200) {
		    $NZLN_DST=1;
		} else {
		    $NZLN_DST=0;
		}
	    }
	} else {
	    $NZLN_DST=1;
	}
    } elseif ($mm == 9) {
	if ($dd < 25) {
	    $NZLN_DST=0;
	} elseif ($dd < ($dow+25)) {
	    $NZLN_DST=0;
	} elseif ($dow == 0) {
	    if ($timezone) { # UTC calculations
		if ($ns < (3600+($timezone-1)*3600)) {
		    $NZLN_DST=0;
		} else {
		    $NZLN_DST=1;
		}
	    } else { # local time calculations
		if ($ns < 3600) {
		    $NZLN_DST=0;
		} else {
		    $NZLN_DST=1;
		}
	    }
	} else {
	    $NZLN_DST=1;
	}
    } # end of month checks
	if ($DBX) {print "     DST: $NZLN_DST\n";}
	if ($NZLN_DST) {$gmt_offset++;}
	$AC_processed++;
	}

if ( (!$AC_processed) and ($dst_range == 'TSO-LSF') )
	{
	if ($DBX) {print "     Third Sunday October to Last Sunday February\n";}
	#**********************************************************************
	# TSO-LSF
	#     This is returns 1 if Daylight Savings Time is in effect and 0 if
	#       Standard time is in effect. Brazil
	#     Based on Third Sunday October to Last Sunday February at 1 am.
	#**********************************************************************

		$BZL_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 2 || $mm > 10) {
		$BZL_DST=1;
		} elseif ($mm >= 3 and $mm <= 9) {
		$BZL_DST=0;
		} elseif ($mm == 2) {
		if ($dd < 22) {
			$BZL_DST=1;
		} elseif ($dd < ($dow+22)) {
			$BZL_DST=1;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$BZL_DST=1;
			} else {
				$BZL_DST=0;
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$BZL_DST=1;
			} else {
				$BZL_DST=0;
			}
			}
		} else {
			$BZL_DST=0;
		}
		} elseif ($mm == 10) {
		if ($dd < 22) {
			$BZL_DST=0;
		} elseif ($dd < ($dow+22)) {
			$BZL_DST=0;
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$BZL_DST=0;
			} else {
				$BZL_DST=1;
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$BZL_DST=0;
			} else {
				$BZL_DST=1;
			}
			}
		} else {
			$BZL_DST=1;
		}
		} # end of month checks
	if ($DBX) {print "     DST: $BZL_DST\n";}
	if ($BZL_DST) {$gmt_offset++;}
	$AC_processed++;
	}

if (!$AC_processed)
	{
	if ($DBX) {print "     No DST Method Found\n";}
	if ($DBX) {print "     DST: 0\n";}
	$AC_processed++;
	}

return $gmt_offset;
}





##### DETERMINE IF LEAD IS DIALABLE #####
function dialable_gmt($DB,$link,$local_call_time,$gmt_offset,$state)
	{
	$dialable=0;

	$pzone=3600 * $gmt_offset;
	$pmin=(gmdate("i", time() + $pzone));
	$phour=( (gmdate("G", time() + $pzone)) * 100);
	$pday=gmdate("w", time() + $pzone);
	$tz = sprintf("%.2f", $p);
	$GMT_gmt = "$tz";
	$GMT_day = "$pday";
	$GMT_hour = ($phour + $pmin);

	$stmt="SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times FROM vicidial_call_times where call_time_id='$local_call_time';";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$call_times_to_print = mysqli_num_rows($rslt);
	if ($call_times_to_print > 0)
		{
		$rowx=mysqli_fetch_row($rslt);
		$Gct_default_start =	$rowx[3];
		$Gct_default_stop =		$rowx[4];
		$Gct_sunday_start =		$rowx[5];
		$Gct_sunday_stop =		$rowx[6];
		$Gct_monday_start =		$rowx[7];
		$Gct_monday_stop =		$rowx[8];
		$Gct_tuesday_start =	$rowx[9];
		$Gct_tuesday_stop =		$rowx[10];
		$Gct_wednesday_start =	$rowx[11];
		$Gct_wednesday_stop =	$rowx[12];
		$Gct_thursday_start =	$rowx[13];
		$Gct_thursday_stop =	$rowx[14];
		$Gct_friday_start =		$rowx[15];
		$Gct_friday_stop =		$rowx[16];
		$Gct_saturday_start =	$rowx[17];
		$Gct_saturday_stop =	$rowx[18];
		$Gct_state_call_times = $rowx[19];

		if ( (strlen($Gct_state_call_times) > 2) and (strlen($state)>0) )
			{
			$Gct_state_call_timesSQL = $Gct_state_call_times;
			$Gct_state_call_timesSQL = preg_replace("/\|/","','",$Gct_state_call_timesSQL);
			$Gct_state_call_timesSQL = preg_replace("/^',|,'$/",'',$Gct_state_call_timesSQL);

			$stmt="SELECT sct_default_start,sct_default_stop,sct_sunday_start,sct_sunday_stop,sct_monday_start,sct_monday_stop,sct_tuesday_start,sct_tuesday_stop,sct_wednesday_start,sct_wednesday_stop,sct_thursday_start,sct_thursday_stop,sct_friday_start,sct_friday_stop,sct_saturday_start,sct_saturday_stop FROM vicidial_state_call_times where state_call_time_id IN($Gct_state_call_timesSQL) and state_call_time_state='$state';";
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$state_times_to_print = mysqli_num_rows($rslt);
			if ($state_times_to_print > 0)
				{
				$rowx=mysqli_fetch_row($rslt);
				$Gct_default_start =	$rowx[0];
				$Gct_default_stop =		$rowx[1];
				$Gct_sunday_start =		$rowx[2];
				$Gct_sunday_stop =		$rowx[3];
				$Gct_monday_start =		$rowx[4];
				$Gct_monday_stop =		$rowx[5];
				$Gct_tuesday_start =	$rowx[6];
				$Gct_tuesday_stop =		$rowx[7];
				$Gct_wednesday_start =	$rowx[8];
				$Gct_wednesday_stop =	$rowx[9];
				$Gct_thursday_start =	$rowx[10];
				$Gct_thursday_stop =	$rowx[11];
				$Gct_friday_start =		$rowx[12];
				$Gct_friday_stop =		$rowx[13];
				$Gct_saturday_start =	$rowx[14];
				$Gct_saturday_stop =	$rowx[15];
				}
			}

		### go through each day to determine dialability
		if ($GMT_day==0)	#### Sunday local time
			{
			if (($Gct_sunday_start==0) and ($Gct_sunday_stop==0))
				{
				if ( ($GMT_hour>=$Gct_default_start) and ($GMT_hour<$Gct_default_stop) )
					{$dialable=1;}
				}
			else
				{
				if ( ($GMT_hour>=$Gct_sunday_start) and ($GMT_hour<$Gct_sunday_stop) )
					{$dialable=1;}
				}
			}
		if ($GMT_day==1)	#### Monday local time
			{
			if (($Gct_monday_start==0) and ($Gct_monday_stop==0))
				{
				if ( ($GMT_hour>=$Gct_default_start) and ($GMT_hour<$Gct_default_stop) )
					{$dialable=1;}
				}
			else
				{
				if ( ($GMT_hour>=$Gct_monday_start) and ($GMT_hour<$Gct_monday_stop) )
					{$dialable=1;}
				}
			}
		if ($GMT_day==2)	#### Tuesday local time
			{
			if (($Gct_tuesday_start==0) and ($Gct_tuesday_stop==0))
				{
				if ( ($GMT_hour>=$Gct_default_start) and ($GMT_hour<$Gct_default_stop) )
					{$dialable=1;}
				}
			else
				{
				if ( ($GMT_hour>=$Gct_tuesday_start) and ($GMT_hour<$Gct_tuesday_stop) )
					{$dialable=1;}
				}
			}
		if ($GMT_day==3)	#### Wednesday local time
			{
			if (($Gct_wednesday_start==0) and ($Gct_wednesday_stop==0))
				{
				if ( ($GMT_hour>=$Gct_default_start) and ($GMT_hour<$Gct_default_stop) )
					{$dialable=1;}
				}
			else
				{
				if ( ($GMT_hour>=$Gct_wednesday_start) and ($GMT_hour<$Gct_wednesday_stop) )
					{$dialable=1;}
				}
			}
		if ($GMT_day==4)	#### Thursday local time
			{
			if (($Gct_thursday_start==0) and ($Gct_thursday_stop==0))
				{
				if ( ($GMT_hour>=$Gct_default_start) and ($GMT_hour<$Gct_default_stop) )
					{$dialable=1;}
				}
			else
				{
				if ( ($GMT_hour>=$Gct_thursday_start) and ($GMT_hour<$Gct_thursday_stop) )
					{$dialable=1;}
				}
			}
		if ($GMT_day==5)	#### Friday local time
			{
			if (($Gct_friday_start==0) and ($Gct_friday_stop==0))
				{
				if ( ($GMT_hour>=$Gct_default_start) and ($GMT_hour<$Gct_default_stop) )
					{$dialable=1;}
				}
			else
				{
				if ( ($GMT_hour>=$Gct_friday_start) and ($GMT_hour<$Gct_friday_stop) )
					{$dialable=1;}
				}
			}
		if ($GMT_day==6)	#### Saturday local time
			{
			if (($Gct_saturday_start==0) and ($Gct_saturday_stop==0))
				{
				if ( ($GMT_hour>=$Gct_default_start) and ($GMT_hour<$Gct_default_stop) )
					{$dialable=1;}
				}
			else
				{
				if ( ($GMT_hour>=$Gct_saturday_start) and ($GMT_hour<$Gct_saturday_stop) )
					{$dialable=1;}
				}
			}

		return $dialable;
		}
	else
		{
		return 0;
		}
	}



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
