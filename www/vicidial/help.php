<?php
# help.php - VICIDIAL administration page
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
# 

# CHANGELOG:
# 131019-0848 - Moved help from admin.php
# 131029-2058 - Added auto-restart asterisk help
# 131208-1635 - Added help for max dead, dispo and pause time campaign options
# 140126-1023 - Added VMAIL_NO_INST options
# 140126-2254 - Added voicemail_instructions option for phones
# 140404-1104 - Added new DID filter options
# 140418-0915 - Added users and campaigns max_inbound_calls
# 140423-1637 - Added manual_dial_search_checkbox and hide_call_log_info
# 140425-0912 - Added modify_custom_dialplans
# 140425-1306 - Added queuemetrics_pause_type
# 140509-2211 - Added frozen_server_call_clear
# 140521-2020 - Changed alt_number_dialing and added timer_alt_seconds
# 140617-2021 - Added vicidial_users wrapup_seconds_override option
# 140621-2151 - Added inbound did new filtering options
# 140623-2220 - Added wrapup_bypass and wrapup_message change to allow script use
# 140625-1934 - Added wrapup_after_hotkey
# 140705-0928 - Added custom list fields help section
# 140706-0935 - Added callback_time_24hour
# 140902-0815 - Added callback_active_limit and callback_active_limit_override
# 141123-1019 - Added help for new campaign comments options
# 141124-2144 - Added show_previous_callback
# 141124-2231 - Added clear_script
# 141204-0605 - Added enable_languages
# 141204-2209 - Added QXZ function output
# 141211-1647 - Added cpd_unknown_action and lists-na_call_url
# 141212-0945 - Added user_choose_language, selected_language and language_method
# 141230-1503 - Added code for on-the-fly language translations display
# 150107-1954 - Added users-ignore_group_on_search
# 150111-1542 - Added lists-local_call_time and manual_dial_search_filter
# 150117-1439 - Added NAME option to campaigns-status_display_fields
# 150120-0636 - Hide non-functional agent_extended_alt_dial campaign feature
# 150204-1246 - Small fixes, issue #826
# 150210-0659 - Added LOCK options for manual_dial_search_checkbox
# 150217-0657 - Added vmail show on login option
# 150218-0800 - Added Callbacks Bulk Move help
# 150223-1548 - Added DYN option to am_message_exten
# 150307-2317 - Added custom meetme enter options
# 150404-0934 - Added enable_did_entry_list_id and related DID options
# 150428-1704 - Added enable_third_webform
# 150429-1232 - Added new user API restrictions
# 150513-2310 - Added POP3 Auth Mode
# 150608-1154 - Added manual dial override field entry and updated manual dial search and filter entries
# 150609-1204 - Added chat-related entries
# 150609-1231 - Added new agent screen status display option entries
# 150610-0938 - Added campaigns-customer_gone_seconds
# 150708-2238 - Added max_queue_ingroup_ options
# 150710-1124 - Added explanation of new ALT URL feature and alt_multi_urls
# 150724-0047 - Added agent_debug_logging
# 150725-1406 - Added agent_display_fields
# 150727-1039 - Added default_language
# 150728-0904 - Added state_conversion for list loader
# 150804-1108 - Added agent_whisper_enabled system settings option
# 150804-1631 - Added multiple in-group _lead_reset options
# 150806-1346 - Added Settings Containers
# 150903-1458 - Added compatibility for custom fields data options
# 150925-2235 - Added user_hide_realtime and user lead filter options
# 150926-1058 - Added did_carrier_description
# 151006-0935 - Updated campaign_cid_areacodes entry
# 151020-0704 - Added Status Groups and Custom Reports entries
# 151030-0639 - Added usacan_phone_dialcode_fix entry
# 151104-1541 - Added am_message_wildcards entry
# 151121-1144 - Added cache_carrier_stats_realtime
# 151204-0635 - Added phones-unavail_dialplan_fwd_exten
# 151209-1437 - Added phones-nva entries
# 151220-1553 - Added more phones nva options
# 151221-0751 - Changed in-group download link to customer chat links
# 151229-1659 - Added servers-gather_asterisk_output entry
# 151229-2302 - Added campaigns-manual_dial_timeout entry
# 151231-0839 - Added user_groups-agent_allowed_chat_groups entry
# 160101-0933 - Added entries for routing_initiated_recordings in campaigns and in-groups
# 160106-0700 - Added AREACODE description to the inbound_dids-filter_inbound_number entry
# 160106-1348 - Added inbound_groups-on_hook_cid_number entry
# 160108-2217 - Added campaigns-manual_dial_hopper_check entry
# 160116-1512 - Added access_recording and log_recording_access entries
# 160121-2155 - Added settings-report_default_format entry and help text for several reports
# 160211-2255 - Added help text for remaining reports
# 160305-2115 - Added Alt IVR(call menu) DTMF logging
# 160306-1201 - Added new webphone options and server options
# 160324-1940 - Added callback_useronly_move_minutes
# 160407-1931 - Updated Phones Email entry
# 160414-0916 - Added default_phone_code
# 160429-0834 - Added settings-admin_row_click
# 160508-0836 - Added screen colors
# 160515-1958 - Added ofcom_uk_drop_calc entry
# 160527-1359 - Added phones-outbound_alt_cid entry
# 160621-1735 - Added agent_screen_colors and script_remove_js entries
# 160731-1030 - Added manual_auto_next, manual_auto_show, user_nickname entries
# 160731-2053 - Added POSTx description for recording filenames
# 160809-1351 - Added customer_chat_screen_colors and customer_chat_survey_link/text entries
# 160915-0954 - Added ---READONLY--- option for field labels
# 160926-1351 - Added user_new_lead_limit entries
# 161018-2245 - Added allow_required_fields
# 161028-1548 - Added agent_xfer_park_3way entry for system_settings
# 161031-1410 - Added users-user_new_lead_limit entry
# 161105-0246 - Added web_loader_phone_length, agent soundboards and purge uncalled records
# 161106-2102 - Added agent_script
# 161126-1815 - Fixed several spelling errors
# 161207-1958 - Added Agent DID Stats report entry
# 161222-0843 - Added agent_chat_screen_colors entry
# 161226-2214 - Added conf_qualify entry
# 170113-1647 - Added call menu in-group option DYNAMIC_INGROUP_VAR for use with cm_phonesearch.agi
# 170114-1404 - Added inbound_groups-populate_lead_province entry
# 170207-1331 - Added api_only_user entry
# 170217-1353 - Added dead_to_dispo entry
# 170220-1811 - Added areacode_filter entries
# 170301-1337 - Updated entry for custom fields required setting
# 170304-1346 - Added auto_reports section
# 170309-1212 - Added agent_xfer_validation and populate_state_areacode entries
# 170313-2012 - Added CHAT option to inbound_queue_no_dial entry
# 170320-1346 - Added phones conf_qualify entry
# 170321-1130 - Added pause code limits entries
# 170322-1720 - Added filter-phone-list entry
# 170326-1135 - Added drop lists entries
# 170327-1649 - updated the campaigns-use_custom_cid entry
# 170407-0745 - Added Agents count on server page
# 170409-0939 - Added IP Lists entries
# 170410-1329 - Added dl_minutes drop lists entry
# 170416-1620 - Added servers-routing_prefix and user/campaign ready_max_logout entries
# 170428-1959 - Added Inbound and Advanced Forecasting Reports
# 170429-0810 - Added callback_display_days entry
# 170430-0957 - Added three_way_record_stop and hangup_xfer_record_start entries
# 170516-0628 - Added rt_monitor_log_report entry
# 170529-2337 - Added agent push events entries
# 170613-0855 - Added hide_inactive_lists
# 170623-2134 - Changed parameters for password recommendations
# 170816-1057 - Added inbound after call entries
# 170819-0951 - Added allow_manage_active_lists entry
# 170825-1129 - Added auto_reports-filename_override entry
# 170920-2156 - Added expired_lists_inactive entry
# 170923-1458 - Added settings-did_system_filter entry
# 170930-0906 - Added new extension append cidname options and custom reports help
# 171006-2058 - Added lists-inbound_list_script_override entry
# 171011-1505 - Added webphone_layout entries
# 171018-2203 - Added campaigns-scheduled_callbacks_email_alert entry
# 171020-0028 - Added whiteboard report entry
# 171114-1255 - Updated definitions of SALEs to specifically mention status flag of SALE set to Y
# 171116-1727 - Added lists_fields-field_duplicate entry
# 171124-1045 - Added campaigns-max_inbound_calls_outcome entry
# 171124-1353 = Added campaigns-manual_auto_next_options entry
# 171130-0048 - Added agent_screen_time_display entry
# 171224-1027 - Added lists-default_xfer_group entry
# 180108-2115 - Added campaigns-next_dial_my_callbacks entry
# 180111-1547 - Added settings-anyone_callback_inactive_lists entry
# 180130-0000 - Added GDPR entries for user and system settings
# 180130-2303 - Added inbound_no_agents_no_dial entries
# 180204-0213 - Added inbound_groups-icbq_expiration_hours and closed-time entries
# 180211-1119 - Added source_vlc_status_report
# 180214-0042 - Added cid_groups
# 180217-0810 - Added pause_max_dispo
# 180306-1639 - Added script_top_dispo
# 180310-2246 - Added settings-source_id_display
# 180410-1609 - Added users-pause_code_approval
# 180424-1530 - Added in-group populate_lead_source, populate_lead_vendor entries
# 180430-1837 - Added inbound_groups-park_ext entry
# 180506-1822 - Added text for custom list fields SWITCH field type
# 191227-0857 - Fixes for translated phrases gathering
# 220228-1100 - Added allow_web_debug system setting
#

require("dbconnect_mysqli.php");
require("functions.php");

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_queuemetrics_logging,enable_vtiger_integration,qc_features_active,outbound_autodial_active,sounds_central_control_active,enable_second_webform,user_territories_active,custom_fields_enabled,admin_web_directory,webphone_url,first_login_trigger,hosted_settings,default_phone_registration_password,default_phone_login_password,default_server_password,test_campaign_calls,active_voicemail_server,voicemail_timezones,default_voicemail_timezone,default_local_gmt,campaign_cid_areacodes_enabled,pllb_grouping_limit,did_ra_extensions_enabled,expanded_list_stats,contacts_enabled,alt_log_server_ip,alt_log_dbname,alt_log_login,alt_log_pass,tables_use_alt_log_db,call_menu_qualify_enabled,admin_list_counts,allow_voicemail_greeting,svn_revision,allow_emails,level_8_disable_add,pass_key,pass_hash_enabled,disable_auto_dial,country_code_list_stats,enable_languages,language_method,enable_third_webform,allow_chats,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =							$row[0];
	$SSenable_queuemetrics_logging =		$row[1];
	$SSenable_vtiger_integration =			$row[2];
	$SSqc_features_active =					$row[3];
	$SSoutbound_autodial_active =			$row[4];
	$SSsounds_central_control_active =		$row[5];
	$SSenable_second_webform =				$row[6];
	$SSuser_territories_active =			$row[7];
	$SScustom_fields_enabled =				$row[8];
	$SSadmin_web_directory =				$row[9];
	$SSwebphone_url =						$row[10];
	$SSfirst_login_trigger =				$row[11];
	$SShosted_settings =					$row[12];
	$SSdefault_phone_registration_password =$row[13];
	$SSdefault_phone_login_password =		$row[14];
	$SSdefault_server_password =			$row[15];
	$SStest_campaign_calls =				$row[16];
	$SSactive_voicemail_server =			$row[17];
	$SSvoicemail_timezones =				$row[18];
	$SSdefault_voicemail_timezone =			$row[19];
	$SSdefault_local_gmt =					$row[20];
	$SScampaign_cid_areacodes_enabled =		$row[21];
	$SSpllb_grouping_limit =				$row[22];
	$SSdid_ra_extensions_enabled =			$row[23];
	$SSexpanded_list_stats =				$row[24];
	$SScontacts_enabled =					$row[25];
	$SSalt_log_server_ip =					$row[26];
	$SSalt_log_dbname =						$row[27];
	$SSalt_log_login =						$row[28];
	$SSalt_log_pass =						$row[29];
	$SStables_use_alt_log_db =				$row[30];
	$SScall_menu_qualify_enabled =			$row[31];
	$SSadmin_list_counts =					$row[32];
	$SSallow_voicemail_greeting =			$row[33];
	$SSsvn_revision =						$row[34];
	$SSallow_emails =						$row[35];
	$SSlevel_8_disable_add =				$row[36];
	$SSpass_key =							$row[37];
	$SSpass_hash_enabled =					$row[38];
	$SSdisable_auto_dial =					$row[39];
	$SScountry_code_list_stats =			$row[40];
	$SSenable_languages =					$row[41];
	$SSlanguage_method =					$row[42];
	$SSenable_third_webform =				$row[43];
	$SSallow_chats =						$row[44];
	$SSallow_web_debug =					$row[45];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];

if ($non_latin < 1)
	{
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
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
		$VDdisplayMESSAGE = "You do not have permission to be here";
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	}
else
	{
	$VDdisplayMESSAGE = "Login incorrect, please try again";
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = "Too many login attempts, try again in 15 minutes";
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}



######################
# display the HELP SCREENS
######################

header ("Content-type: text/html; charset=utf-8");
echo "</title>\n";
echo "</head>\n";
echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo "<CENTER>\n";
echo "<TABLE WIDTH=98% BGCOLOR=#E6E6E6 cellpadding=2 cellspacing=0><TR><TD ALIGN=LEFT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=4><B>"._QXZ("ADMINISTRATION: HELP")."<BR></B></FONT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><BR><BR>\n";

?>
<B><FONT SIZE=3><?php echo _QXZ("USERS TABLE"); ?></FONT></B><BR><BR>
<A NAME="users-user">
<BR>
<B><?php echo _QXZ("User ID"); ?> -</B><?php echo _QXZ("This field is where you put the users ID number, can be up to 8 digits in length, Must be at least 2 characters in length."); ?>

<BR>
<A NAME="users-pass">
<BR>
<B><?php echo _QXZ("Password"); ?> -</B><?php echo _QXZ("This field is where you put the users password. Must be at least 2 characters in length. A medium strength user password will be at least 10 characters in length, and a strong user password will be at least 20 characters in length and have letters as well as at least one number. It is recommended that you use a longer password if possible, stringing together several unrelated words with no spaces, and a number somewhere in the string. The maximum size of a password is 100 characters."); ?>

<BR>
<A NAME="users-force_change_password">
<BR>
<B><?php echo _QXZ("Force Change Password"); ?> -</B><?php echo _QXZ("If this option is set to Y then the user will be prompted to change their password the next time they log in to the administration webpage. Default is N."); ?>

<BR>
<A NAME="users-last_login_date">
<BR>
<B><?php echo _QXZ("Last Login Info"); ?> -</B><?php echo _QXZ("This shows the last login attempt date and time, and if there has been a recent failed login attempt. If this modify user form is submitted, then the failed login attempt counter will be reset and the agent can immediately attempt to log in again. If an agent has 10 failed login attempts in a row then they cannot attempt to log in again for at least 15 minutes unless their account is manually reset."); ?>

<BR>
<A NAME="users-full_name">
<BR>
<B><?php echo _QXZ("Full Name"); ?> -</B><?php echo _QXZ("This field is where you put the users full name. Must be at least 2 characters in length."); ?>

<BR>
<A NAME="users-user_level">
<BR>
<B><?php echo _QXZ("User Level"); ?> -</B><?php echo _QXZ("This menu is where you select the users user level. Must be a level of 1 to log into the agent screen, Must be level greater than 2 to log in as a closer, Must be user level 8 or greater to get into admin web section."); ?>

<BR>
<A NAME="users-user_group">
<BR>
<B><?php echo _QXZ("User Group"); ?> -</B><?php echo _QXZ("This menu is where you select the users group that this user will belong to. There are several agent screen features that can be controlled through user group settings. If this field is left blank then the user cannot log in to the agent screen."); ?>

<BR>
<A NAME="users-phone_login">
<BR>
<B><?php echo _QXZ("Phone Login"); ?> -</B><?php echo _QXZ("Here is where you can set a default phone login value for when the user logs into the agent screen. This value will populate the phone_login automatically when the user logs in with their user-pass-campaign in the agent login screen."); ?>

<BR>
<A NAME="users-phone_pass">
<BR>
<B><?php echo _QXZ("Phone Pass"); ?> -</B><?php echo _QXZ("Here is where you can set a default phone pass value for when the user logs into the agent screen. This value will populate the phone_pass automatically when the user logs in with their user-pass-campaign in the agent login screen."); ?>

<BR>
<A NAME="users-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This field defines whether the user is active in the system and can log in as an agent or manager. Default is Y."); ?>

<BR>
<A NAME="users-voicemail_id">
<BR>
<B><?php echo _QXZ("Voicemail ID"); ?> -</B><?php echo _QXZ("This is the voicemail box that calls will be directed to in an AGENTDIRECT in-group at the drop time if the in-group has the drop method set to VOICEMAIL and the Voicemail field set to AGENTVMAIL."); ?>

<BR>
<A NAME="users-optional">
<BR>
<B><?php echo _QXZ("Email, User Code and Territory"); ?> -</B><?php echo _QXZ("These are optional fields."); ?>

<BR>
<A NAME="users-user_nickname">
<BR>
<B><?php echo _QXZ("User Nickname"); ?> -</B><?php echo _QXZ("Optional alternative name used for agent when chatting with customers in the customer website chat feature. Only used if populated."); ?>

<BR>
<A NAME="users-user_new_lead_limit">
<BR>
<B><?php echo _QXZ("User New Lead Limit"); ?> -</B><?php echo _QXZ("This Overall Limit setting will limit the number of new leads this user can dial across all lists per day. This feature will only work properly if the campaign is set to either the MANUAL or INBOUND_MAN Dial Method and No Hopper dialing is enabled. Default is -1 for disabled."); ?>

<BR>
<A NAME="users-hotkeys_active">
<BR>
<B><?php echo _QXZ("Hot Keys Active"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to use the Hot Keys quick-dispositioning function in the agent screen."); ?>

<BR>
<A NAME="users-agent_choose_ingroups">
<BR>
<B><?php echo _QXZ("Agent Choose Ingroups"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to choose the ingroups that they will receive calls from when they login to a CLOSER or INBOUND campaign. Otherwise the Manager will need to set this in their user detail screen of the admin page."); ?>

<BR>
<A NAME="users-agent_choose_blended">
<BR>
<B><?php echo _QXZ("Agent Choose Blended"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to choose if the agent has their campaign set to blended or not, and if not then the default blended setting will be used. Default is 1 for enabled."); ?>

<BR>
<A NAME="users-agent_choose_territories">
<BR>
<B><?php echo _QXZ("Agent Choose Territories"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to choose the territories that they will receive calls from when they login to a MANUAL or INBOUND_MAN campaign. Otherwise the user will be set to use all of the territories that they are set to belong to in the User Territories administrative section."); ?>

<BR>
<A NAME="users-scheduled_callbacks">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks"); ?> -</B><?php echo _QXZ("This option allows an agent to disposition a call as CALLBK and choose the date and time at which the lead will be re-activated."); ?>

<BR>
<A NAME="users-agentonly_callbacks">
<BR>
<B><?php echo _QXZ("Agent-Only Callbacks"); ?> -</B><?php echo _QXZ("This option allows an agent to set a callback so that they are the only Agent that can call the customer back. This also allows the agent to see their callback listings and call them back any time they want to."); ?>

<BR>
<A NAME="users-agentcall_manual">
<BR>
<B><?php echo _QXZ("Agent Call Manual"); ?> -</B><?php echo _QXZ("This option allows an agent to manually enter a new lead into the system and call them. This also allows the calling of any phone number from their agent screen and puts that call into their session. Use this option with caution."); ?>

<BR>
<A NAME="users-agentcall_email">
<BR>
<B><?php echo _QXZ("Agent Call Email"); ?> -</B><?php echo _QXZ("This option is disabled."); ?>

<BR>
<A NAME="users-agentcall_chat">
<BR>
<B><?php echo _QXZ("Agent Call Chat"); ?> -</B><?php echo _QXZ("This option is disabled."); ?>

<BR>
<A NAME="users-agent_recording">
<BR>
<B><?php echo _QXZ("Agent Recording"); ?> -</B><?php echo _QXZ("This option can prevent an agent from doing any recordings after they log in to the agent screen. This option must be on for the agent screen to follow the campaign recording settings."); ?>

<BR>
<A NAME="users-agent_transfers">
<BR>
<B><?php echo _QXZ("Agent Transfers"); ?> -</B><?php echo _QXZ("This option can prevent an agent from opening the transfer - conference session in the agent screen. If this is disabled, the agent cannot third party call or blind transfer any calls."); ?>

<?php
if ($SSoutbound_autodial_active > 0)
	{
	?>
	<BR>
	<A NAME="users-closer_default_blended">
	<BR>
	<B><?php echo _QXZ("Closer Default Blended"); ?> -</B><?php echo _QXZ("This option simply defaults the Blended checkbox on a CLOSER login screen.");
	}
?>

<BR>
<A NAME="users-user_choose_language">
<BR>
<B><?php echo _QXZ("User Choose Language"); ?> -</B><?php echo _QXZ("This option allows a user to select the language they want for the agent or administrative interface to display in. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-selected_language">
<BR>
<B><?php echo _QXZ("Selected Language"); ?> -</B><?php echo _QXZ("This is the language that the agent and administrative interface will default to when the agent logs in. Default is -default English-."); ?>

<BR>
<A NAME="users-agent_recording_override">
<BR>
<B><?php echo _QXZ("Agent Recording Override"); ?> -</B><?php echo _QXZ("This option will override whatever the option is in the campaign for recording. DISABLED will not override the campaign recording setting. NEVER will disable recording on the client. ONDEMAND is the default and allows the agent to start and stop recording as needed. ALLCALLS will start recording on the client whenever a call is sent to an agent. ALLFORCE will start recording on the client whenever a call is sent to an agent giving the agent no option to stop recording. For ALLCALLS and ALLFORCE there is an option to use the Recording Delay to cut down on very short recordings and reduce system load."); ?>

<BR>
<A NAME="users-agent_shift_enforcement_override">
<BR>
<B><?php echo _QXZ("Agent Shift Enforcement Override"); ?> -</B><?php echo _QXZ("This setting will override whatever the users user group has set for Shift Enforcement. DISABLED will use the user group setting. OFF will not enforce shifts at all. START will only enforce the login time but will not affect an agent that is running over their shift time if they are already logged in. ALL will enforce shift start time and will log an agent out after they run over the end of their shift time. Default is DISABLED."); ?>

<BR>
<A NAME="users-agent_call_log_view_override">
<BR>
<B><?php echo _QXZ("Agent Call Log View Override"); ?> -</B><?php echo _QXZ("This setting will override whatever the users user group has set for Agent Call Log View. DISABLED will use the user group setting. N will not allow showing the users call log. Y will allow showing the user call log. Default is DISABLED."); ?>

<BR>
<A NAME="users-agent_lead_search_override">
<BR>
<B><?php echo _QXZ("Agent Lead Search Override"); ?> -</B><?php echo _QXZ("This setting will override whatever the campaign has set for Agent Lead Search. NOT_ACTIVE will use the campaign setting. ENABLED will allow lead searching and DISABLED will not allow lead searching. Default is NOT_ACTIVE. LIVE_CALL_INBOUND will allow search for a lead while on an inbound call only. LIVE_CALL_INBOUND_AND_MANUAL will allow search for a lead while on an inbound call or while paused. When Lead Search is used on a live inbound call, the lead of the call when it went to the agent will be changed to a status of LSMERG, and the logs for the call will be modified to link to the agent selected lead instead."); ?>

<BR>
<A NAME="users-lead_filter_id">
<BR>
<B><?php echo _QXZ("Lead Filter"); ?> -</B><?php echo _QXZ("This option allows you to set a Lead Filter for an individual user. To use this option, the user must be logged in to a campaign that has No Hopper Dialing enabled. Default is EMPTY for disabled."); ?>

<BR>
<A NAME="users-user_hide_realtime">
<BR>
<B><?php echo _QXZ("User Hide in RealTime"); ?> -</B><?php echo _QXZ("This setting allows you to hide this user from the Real-Time Report display. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-alert_enabled">
<BR>
<B><?php echo _QXZ("Alert Enabled"); ?> -</B><?php echo _QXZ("This field shows whether the agent has web browser alerts enabled for when calls come into their agent screen session. Default is 0 for NO."); ?>

<BR>
<A NAME="users-allow_alerts">
<BR>
<B><?php echo _QXZ("Allow Alerts"); ?> -</B><?php echo _QXZ("This field gives you the ability to allow agent browser alerts to be enabled by the agent for when calls come into their agent screen session. Default is 0 for NO."); ?>

<BR>
<A NAME="users-preset_contact_search">
<BR>
<B><?php echo _QXZ("Preset Contact Search"); ?> -</B><?php echo _QXZ("If the user is logged into a campaign that has Transfer Presets set to CONTACTS, then this setting can disable contact searching for this user only. Default is NOT_ACTIVE which will use whatever the campaign setting is."); ?>

<BR>
<A NAME="users-max_inbound_calls">
<BR>
<B><?php echo _QXZ("Max Inbound Calls"); ?> -</B><?php echo _QXZ("If this setting is set to a number greater than 0, then it will be the maximum number of inbound calls that an agent can handle across all inbound groups in one day. If the agent reaches their maximum number of inbound calls, then they will not be able to select inbound groups to take calls from until the next day. This setting will override the Campaign setting of the same name. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-wrapup_seconds_override">
<BR>
<B><?php echo _QXZ("Wrap Seconds Override"); ?> -</B><?php echo _QXZ("If this setting is set to a number 0 or greater, then it will override the Campaign setting for Wrapup Seconds. This is a setting that is only refreshed in the agent interface at agent login time. Default is -1 for disabled."); ?>

<BR>
<A NAME="users-ready_max_logout">
<BR>
<B><?php echo _QXZ("Agent Ready Max Logout Override"); ?> -</B><?php echo _QXZ("If this setting is set to a number 0 or greater, then it will override the Campaign setting for Agent Ready Max Logout Seconds. This is a setting that is only refreshed in the agent interface at agent login time. Default is -1 for disabled."); ?>

<BR>
<A NAME="users-campaign_ranks">
<BR>
<B><?php echo _QXZ("Campaign Ranks"); ?> -</B><?php echo _QXZ("In this section you can define the rank an agent will have for each campaign. These ranks can be used to allow for preferred call routing when Next Agent Call is set to campaign_rank. Also in this section are the WEB VARs for each campaign. These allow each agent to have a different variable string that can be added to the WEB FORM or SCRIPT tab URLs by simply putting --A--web_vars--B-- as you would put any other field. Another field in this section is GRADE, which allows for the campaign_grade_random Next Agent Call setting to be used. This grade setting will increase or decrease the probability that the agent will receive the call."); ?>

<BR>
<A NAME="users-closer_campaigns">
<BR>
<B><?php echo _QXZ("Inbound Groups"); ?> -</B><?php echo _QXZ("Here is where you select the inbound groups you want to receive calls from if you have selected the CLOSER campaign. You will also be able to set the rank, or skill level, in this section for each of the inbound groups as well as being able to see the number of calls received from each inbound group for this specific agent. Also in this section is the ability to give the agent a rank for each inbound group. These ranks can be used for preferred call routing when that option is selected in the in-group screen. Also in this section are the WEB VARs for each campaign. These allow each agent to have a different variable string that can be added to the WEB FORM or SCRIPT tab URLs by simply putting --A--web_vars--B-- as you would put any other field."); ?>

<BR>
<A NAME="users-alter_custdata_override">
<BR>
<B><?php echo _QXZ("Agent Alter Customer Data Override"); ?> -</B><?php echo _QXZ("This option will override whatever the option is in the campaign for altering of customer data. NOT_ACTIVE will use whatever setting is present for the campaign. ALLOW_ALTER will always allow for the agent to alter the customer data, no matter what the campaign setting is. Default is NOT_ACTIVE."); ?>

<BR>
<A NAME="users-alter_custphone_override">
<BR>
<B><?php echo _QXZ("Agent Alter Customer Phone Override"); ?> -</B><?php echo _QXZ("This option will override whatever the option is in the campaign for altering of customer phone number. NOT_ACTIVE will use whatever setting is present for the campaign. ALLOW_ALTER will always allow for the agent to alter the customer phone number, no matter what the campaign setting is. Default is NOT_ACTIVE."); ?>

<BR>
<A NAME="users-custom_one">
<BR>
<B><?php echo _QXZ("Custom User Fields"); ?> -</B><?php echo _QXZ("These five fields can be used for various purposes, and they can be populated in the web form addresses and scripts as user_custom_one and so on. The Custom 5 field can be used as a field to define a dialplan number to send a call to from an AGENTDIRECT in-group if the user is unavailable, you just need to put AGENTEXT in the MESSAGE or EXTENSION field in the AGENTDIRECT in-group and the system will look up the custom five field and send the call to that dialplan number."); ?>

<BR>
<A NAME="users-alter_agent_interface_options">
<BR>
<B><?php echo _QXZ("Alter Agent Interface Options"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the administrative user to modify the Agents interface options in admin.php."); ?>

<BR>
<A NAME="users-delete_users">
<BR>
<B><?php echo _QXZ("Delete Users"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to delete other users of equal or lesser user level from the system."); ?>

<BR>
<A NAME="users-delete_user_groups">
<BR>
<B><?php echo _QXZ("Delete User Groups"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to delete user groups from the system."); ?>

<BR>
<A NAME="users-delete_lists">
<BR>
<B><?php echo _QXZ("Delete Lists"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to delete lists from the system."); ?>

<BR>
<A NAME="users-delete_campaigns">
<BR>
<B><?php echo _QXZ("Delete Campaigns"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to delete campaigns from the system."); ?>

<BR>
<A NAME="users-delete_ingroups">
<BR>
<B><?php echo _QXZ("Delete In-Groups"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to delete Inbound Groups from the system."); ?>

<BR>
<A NAME="users-modify_custom_dialplans">
<BR>
<B><?php echo _QXZ("Modify Custom Dialplans"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to view and modify custom dialplan entries that are available in the Call Menu, System Settings and Servers modification screens."); ?>

<BR>
<A NAME="users-delete_remote_agents">
<BR>
<B><?php echo _QXZ("Delete Remote Agents"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to delete remote agents from the system."); ?>

<?php
if ($SSoutbound_autodial_active > 0)
	{
	?>
	<BR>
	<A NAME="users-load_leads">
	<BR>
	<B><?php echo _QXZ("Load Leads"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to load lead lists into the list table by way of the web based lead loader."); ?>
	<?php
	}
if ($SScustom_fields_enabled > 0)
	{
	?>
	<BR>
	<A NAME="users-custom_fields_modify">
	<BR>
	<B><?php echo _QXZ("Custom Fields Modify"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to modify custom list fields."); ?>
	<?php
	}
?>

<BR>
<A NAME="users-campaign_detail">
<BR>
<B><?php echo _QXZ("Campaign Detail"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to view and modify the campaign detail screen elements."); ?>

<BR>
<A NAME="users-ast_admin_access">
<BR>
<B><?php echo _QXZ("AGC Admin Access"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to login to the astGUIclient admin pages."); ?>

<BR>
<A NAME="users-ast_delete_phones">
<BR>
<B><?php echo _QXZ("AGC Delete Phones"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to delete phone entries in the astGUIclient admin pages."); ?>

<BR>
<A NAME="users-delete_scripts">
<BR>
<B><?php echo _QXZ("Delete Scripts"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to delete Campaign scripts in the script modification screen."); ?>

<BR>
<A NAME="users-modify_leads">
<BR>
<B><?php echo _QXZ("Modify Leads"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to modify leads in the admin section lead search results page."); ?>

<BR>
<A NAME="users-export_gdpr_leads">
<BR>
<B><?php echo _QXZ("GDPR-Compliant Export Delete Leads"); ?> -</B><?php echo _QXZ("This setting if enabled will allow for the complete download and/or deletion of all customer data for a particular lead, in compliance with the General Data Protection Regulation (GDPR). Default is 0 for disabled.  A setting of 1 will enable downloading data, and a setting of 2 will enable not just downloading, but also deletion of data, including any recordings. "); ?>
<BR>
<B><?php echo _QXZ("You are not allowed to set this user setting higher than the current system setting. "); ?></B>

<?php
if ($SSallow_emails>0)
	{
?>
<BR>
<A NAME="users-modify_email_accounts">
<BR>
<B><?php echo _QXZ("Modify Email Accounts"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to modify email accounts in the email account management page.");
	}
?>

<BR>
<A NAME="users-change_agent_campaign">
<BR>
<B><?php echo _QXZ("Change Agent Campaign"); ?> -</B><?php echo _QXZ("This option if set to 1 allows the user to alter the campaign that an agent is logged into while they are logged into it."); ?>

<?php
if ($SSoutbound_autodial_active > 0)
	{
	?>
	<BR>
	<A NAME="users-delete_filters">
	<BR>
	<B><?php echo _QXZ("Delete Filters"); ?> -</B><?php echo _QXZ("This option allows the user to be able to delete lead filters from the system.");
	}
?>

<BR>
<A NAME="users-delete_call_times">
<BR>
<B><?php echo _QXZ("Delete Call Times"); ?> -</B><?php echo _QXZ("This option allows the user to be able to delete call times records and state call times records from the system."); ?>

<BR>
<A NAME="users-modify_call_times">
<BR>
<B><?php echo _QXZ("Modify Call Times"); ?> -</B><?php echo _QXZ("This option allows the user to view and modify the call times and state call times records. A user does not need this option enabled if they only need to change the call times option on the campaigns screen."); ?>

<BR>
<A NAME="users-modify_sections">
<BR>
<B><?php echo _QXZ("Modify Sections"); ?> -</B><?php echo _QXZ("These options allow the user to view and modify each sections records. If set to 0, the user will be able to see the section list, but not the detail or modification screen of a record in that section."); ?>

<BR>
<A NAME="users-view_reports">
<BR>
<B><?php echo _QXZ("View Reports"); ?> -</B><?php echo _QXZ("This option allows the user to view the system web reports."); ?>

<BR>
<A NAME="users-access_recordings">
<BR>
<B><?php echo _QXZ("Access Recordings"); ?> -</B><?php echo _QXZ("This option allows the user to have access to call recordings.");


if ($SSqc_features_active > 0)
	{
	?>
	<BR>
	<A NAME="users-qc_enabled">
	<BR>
	<B><?php echo _QXZ("QC Enabled"); ?> -</B><?php echo _QXZ("This option allows the user to log in to the Quality Control agent screen."); ?>

	<BR>
	<A NAME="users-qc_user_level">
	<BR>
	<B><?php echo _QXZ("QC User Level"); ?> -</B><?php echo _QXZ("This setting defines what the agent Quality Control user level is. This will dictate the level of functionality for the agent in the QC section:"); ?><BR>
	<?php echo _QXZ("1 - Modify Nothing"); ?><BR>
	<?php echo _QXZ("2 - Modify Nothing Except Status"); ?><BR>
	<?php echo _QXZ("3 - Modify All Fields"); ?><BR>
	<?php echo _QXZ("4 - Verify First Round of QC"); ?><BR>
	<?php echo _QXZ("5 - View QC Statistics"); ?><BR>
	<?php echo _QXZ("6 - Ability to Modify FINISHed records"); ?><BR>
	<?php echo _QXZ("7 - Manager Level"); ?><BR>

	<BR>
	<A NAME="users-qc_pass">
	<BR>
	<B><?php echo _QXZ("QC Record Pass"); ?> -</B><?php echo _QXZ("This option allows the agent to specify that a record has passed the first round of QC after reviewing the record."); ?>

	<BR>
	<A NAME="users-qc_finish">
	<BR>
	<B><?php echo _QXZ("QC Record Finish"); ?> -</B><?php echo _QXZ("This option allows the agent to specify that a record has finished the second round of QC after reviewing the passed record."); ?>

	<BR>
	<A NAME="users-qc_commit">
	<BR>
	<B><?php echo _QXZ("QC Record Commit"); ?> -</B><?php echo _QXZ("This option allows the agent to specify that a record has been committed in QC. It can no longer be modified by anyone."); 
	}
?>

<BR>
<A NAME="users-add_timeclock_log">
<BR>
<B><?php echo _QXZ("Add Timeclock Log Record"); ?> -</B><?php echo _QXZ("This option allows the user to add records to the timeclock log."); ?>

<BR>
<A NAME="users-modify_timeclock_log">
<BR>
<B><?php echo _QXZ("Modify Timeclock Log Record"); ?> -</B><?php echo _QXZ("This option allows the user to modify records in the timeclock log."); ?>

<BR>
<A NAME="users-delete_timeclock_log">
<BR>
<B><?php echo _QXZ("Delete Timeclock Log Record"); ?> -</B><?php echo _QXZ("This option allows the user to delete records in the timeclock log."); ?>

<BR>
<A NAME="users-manager_shift_enforcement_override">
<BR>
<B><?php echo _QXZ("Manager Shift Enforcement Override"); ?> -</B><?php echo _QXZ("This setting if set to 1 will allow a manager to enter their user and password on an agent screen to override the shift restrictions on an agent session if the agent is trying to log in outside of their shift. Default is 0."); ?>

<BR>
<A NAME="users-pause_code_approval">
<BR>
<B><?php echo _QXZ("Manager Pause Code Approval"); ?> -</B><?php echo _QXZ("If a campaign pause code is set to require manager approval, the manager that approves the agent pause code selection must have this setting set to 1. Default is 0."); ?>

<BR>
<A NAME="users-vdc_agent_api_access">
<BR>
<B><?php echo _QXZ("Agent API Access"); ?> -</B><?php echo _QXZ("This option allows the account to be used with the agent and non-agent API commands."); ?>

<BR>
<A NAME="users-api_list_restrict">
<BR>
<B><?php echo _QXZ("API List Restrict"); ?> -</B><?php echo _QXZ("If enabled, the API commands that involve leads and lists will be restricted to the lists within the allowed campaigns for the user group of this user. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-api_only_user">
<BR>
<B><?php echo _QXZ("API Only User"); ?> -</B><?php echo _QXZ("This option if enabled will prevent a user from being able to log in to the admin web screen and the agent screen. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-api_allowed_functions">
<BR>
<B><?php echo _QXZ("API Allowed Functions"); ?> -</B><?php echo _QXZ("This option will allow you to restrict the API functions that are allowed to be used by this user. Default is ALL_FUNCTIONS."); ?>

<BR>
<A NAME="users-admin_cf_show_hidden">
<BR>
<B><?php echo _QXZ("Admin Custom Fields Show Hidden"); ?> -</B><?php echo _QXZ("On the ViciHost.com platform this option allows an administrator to view or export custom fields that are partially or fully hidden using the Show Hide field options. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-download_lists">
<BR>
<B><?php echo _QXZ("Download Lists"); ?> -</B><?php echo _QXZ("This setting if set to 1 will allow a manager to click on the download list link at the bottom of a list modification screen to export the entire contents of a list to a flat data file. Default is 0."); ?>

<BR>
<A NAME="users-export_reports">
<BR>
<B><?php echo _QXZ("Export Reports"); ?> -</B><?php echo _QXZ("This setting if set to 1 will allow a manager to access the export call and lead reports on the REPORTS screen. Default is 0. For the Export Calls and Leads Reports, the following field order is used for exports: <BR>call_date, phone_number_dialed, status, user, full_name, campaign_id/in-group, vendor_lead_code, source_id, list_id, gmt_offset_now, phone_code, phone_number, title, first_name, middle_initial, last_name, address1, address2, address3, city, state, province, postal_code, country_code, gender, date_of_birth, alt_phone, email, security_phrase, comments, length_in_sec, user_group, alt_dial/queue_seconds, rank, owner"); ?>

<BR>
<A NAME="users-delete_from_dnc">
<BR>
<B><?php echo _QXZ("Delete From DNC Lists"); ?> -</B><?php echo _QXZ("This setting if set to 1 will allow a manager to remove phone numbers from the DNC lists in the system."); ?>

<BR>
<A NAME="users-realtime_block_user_info">
<BR>
<B><?php echo _QXZ("Realtime Block User Info"); ?> -</B><?php echo _QXZ("This setting if set to 1 will block user and station information from being displayed in the Real-time report. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-modify_same_user_level">
<BR>
<B><?php echo _QXZ("Modify Same User Level"); ?> -</B><?php echo _QXZ("This setting only applies to level 9 users. If enabled it allows the level 9 user to modify their own settings as well as other level 9 users. Default is 1 for enabled."); ?>

<BR>
<A NAME="users-admin_hide_lead_data">
<BR>
<B><?php echo _QXZ("Admin Hide Lead Data"); ?> -</B><?php echo _QXZ("This setting only applies to level 7, 8 and 9 users. If enabled it replaces the customer lead data in the many reports and screens in the system with Xs. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-admin_hide_phone_data">
<BR>
<B><?php echo _QXZ("Admin Hide Phone Data"); ?> -</B><?php echo _QXZ("This setting only applies to level 7, 8 and 9 users. If enabled it replaces the customer phone numbers in the many reports and screens in the system with Xs. The DIGITS settings will show only the last X digits of the phone number. Default is 0 for disabled."); ?>

<BR>
<A NAME="users-ignore_group_on_search">
<BR>
<B><?php echo _QXZ("Search Lead Ignore Group Restrictions"); ?> -</B><?php echo _QXZ("Changing this setting to 1 will allow this user to search for leads throughout the entire system instead of just within the allowed campaigns that are set within their User Group. This will also allow modifying of those leads in the administrative lead modification page. Default is 0 for disabled. To be able to modify this setting, you must belong to a user group that has ALL CAMPAIGNS selected in the Allowed Campaigns section."); ?>

<BR>
<A NAME="users-alter_admin_interface_options">
<BR>
<B><?php echo _QXZ("Alter Admin Interface Options"); ?> -</B><?php echo _QXZ("Changing this setting to 1 will allow this user to alter admin interface options."); ?>



<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("CAMPAIGNS TABLE"); ?></FONT></B><BR><BR>
<A NAME="campaigns-campaign_id">
<BR>
<B><?php echo _QXZ("Campaign ID"); ?> -</B><?php echo _QXZ("This is the short name of the campaign, it is not editable after initial submission, cannot contain spaces and must be between 2 and 8 characters in length."); ?>

<BR>
<A NAME="campaigns-campaign_name">
<BR>
<B><?php echo _QXZ("Campaign Name"); ?> -</B><?php echo _QXZ("This is the description of the campaign, it must be between 6 and 40 characters in length."); ?>

<BR>
<A NAME="campaigns-campaign_description">
<BR>
<B><?php echo _QXZ("Campaign Description"); ?> -</B><?php echo _QXZ("This is a memo field for the campaign, it is optional and can be a maximum of 255 characters in length."); ?>

<BR>
<A NAME="campaigns-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this campaign, this allows admin viewing of this campaign as well as the lists assigned to this campaign to be restricted by user group. Default is --ALL-- which allows any admin user with user group campaign permissions to view this campaign."); ?>

<BR>
<A NAME="campaigns-campaign_changedate">
<BR>
<B><?php echo _QXZ("Campaign Change Date"); ?> -</B><?php echo _QXZ("This is the last time that the settings for this campaign were modified in any way."); ?>

<BR>
<A NAME="campaigns-campaign_logindate">
<BR>
<B><?php echo _QXZ("Last Campaign Login Date"); ?> -</B><?php echo _QXZ("This is the last time that an agent was logged into this campaign."); ?>

<BR>
<A NAME="campaigns-campaign_calldate">
<BR>
<B><?php echo _QXZ("Last Campaign Call Date"); ?> -</B><?php echo _QXZ("This is the last time that a call was handled by an agent logged into this campaign."); ?>

<BR>
<A NAME="campaigns-max_stats">
<BR>
<B><?php echo _QXZ("Daily Maximum Stats"); ?> -</B><?php echo _QXZ("These are statistics that are generated by the system each day throughout the day until the timeclock end-of-day as it is set in the System Settings. These numbers are generated from the logs within the system to allow for much faster display. The stats are included are - Total Calls, Maximum Agents, Maximum inbound calls, Maximum outbound calls."); ?>

<BR>
<A NAME="campaigns-campaign_stats_refresh">
<BR>
<B><?php echo _QXZ("Campaign Stats Refresh"); ?> -</B><?php echo _QXZ("This option will allow you to force a campaign calling stats refresh, even if the campaign is not active."); ?>

<BR>
<A NAME="campaigns-realtime_agent_time_stats">
<BR>
<B><?php echo _QXZ("Real-Time Agent Time Stats"); ?> -</B><?php echo _QXZ("Setting this to anything but DISABLED will enable the gathering of agent time stats for today for this campaign, that are viewable through the Real-Time report. CALLS are the average calls handled per agent, WAIT is the average agent wait time, CUST is the average customer talk time, ACW is the average After Call Work time, PAUSE is the average pause time. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This is where you set the campaign to Active or Inactive. If Inactive, noone can log into it."); ?>

<BR>
<A NAME="campaigns-park_ext">
<BR>
<B><?php echo _QXZ("Park Music-on-Hold"); ?> -</B><?php echo _QXZ("This is where you can define the on-hold music context for this campaign. Make sure you select a valid music on hold context from the select list and that the context that you have selected has valid files in it. Default is default."); ?>

<BR>
<A NAME="campaigns-park_file_name">
<BR>
<B><?php echo _QXZ("Park File Name"); ?> -</B><?php echo _QXZ("NOT USED."); ?>

<BR>
<A NAME="campaigns-web_form_address">
<BR>
<B><?php echo _QXZ("Web Form"); ?> -</B><?php echo _QXZ("This is where you can set the custom web page that will be opened when the user clicks on the WEB FORM button. To customize the query string after the web form, simply begin the web form with VAR and then the URL that you want to use, replacing the variables with the variable names that you want to use --A--phone_number--B-- just like in the SCRIPTS tab section. If you want to use custom fields in a web form address, you need to add &CF_uses_custom_fields=Y as part of your URL."); ?>

<BR>
<A NAME="campaigns-web_form_target">
<BR>
<B><?php echo _QXZ("Web Form Target-</B> This is where you can set the custom web page frame that the web form will be opened in when the user clicks on the WEB FORM button. Default is _blank."); ?>

<BR>
<A NAME="campaigns-allow_closers">
<BR>
<B><?php echo _QXZ("Allow Closers"); ?> -</B>
<?php 

echo _QXZ("This is where you can set whether the users of this campaign will have the option to send the call to a closer.");

if ($SSallow_emails > 0) 
		{
?>
	<BR>
	<A NAME="campaigns-allow_emails">
	<BR>
	<B><?php echo _QXZ("Allow Emails"); ?> -</B><?php echo _QXZ("This is where you can set whether the users of this campaign will be able to receive inbound emails in addition to phone calls."); ?>
<?php
		}

if ($SSallow_chats > 0) 
		{
?>
	<BR>
	<A NAME="campaigns-allow_chats">
	<BR>
	<B><?php echo _QXZ("Allow Chats"); ?> -</B><?php echo _QXZ("This is where you can set whether the users of this campaign will be able to conduct chats in addition to receive phone calls."); ?>
<?php
		}
?>


<BR>
<A NAME="campaigns-default_xfer_group">
<BR>
<B><?php echo _QXZ("Default Transfer Group"); ?> -</B><?php echo _QXZ("This field is the default In-Group that will be automatically selected when the agent goes to the transfer-conference frame in their agent interface."); ?>

<BR>
<A NAME="campaigns-agent_xfer_validation">
<BR>
<B><?php echo _QXZ("Agent Transfer In-Group Validation"); ?> -</B><?php echo _QXZ("This option involves the agents that show up in the agent selection screen when an agent is transferring a call to another agent using an AGENTDIRECT in-group. Enabling this option will ensure that the agents listed as available to transfer to have selected the AGENTDIRECT in-group that was chosen by the agent originating the transfer. For example, if the originating agent has selected the AGENTDIRECT_2 in-group, then when that agent clicks on the AGENTS link to select another agent to transfer the call to, only agents that have selected to take calls from the AGENTDIRECT_2 in-group will be shown."); ?>

<BR>
<A NAME="campaigns-xfer_groups">
<BR>
<B><?php echo _QXZ("Allowed Transfer Groups"); ?> -</B><?php echo _QXZ("With these checkbox listings you can select the groups that agents in this campaign can transfer calls to. Allow Closers must be enabled for this option to show up.");


if ($SSoutbound_autodial_active > 0)
	{
	?>
	<BR>
	<A NAME="campaigns-campaign_allow_inbound">
	<BR>
	<B><?php echo _QXZ("Allow Inbound and Blended"); ?> -</B><?php echo _QXZ("This is where you can set whether the users of this campaign will have the option to take inbound calls with this campaign. If you want to do blended inbound and outbound then this must be set to Y. If you only want to do outbound dialing on this campaign set this to N. Default is N."); ?>

	<BR>
	<A NAME="campaigns-dial_status">
	<BR>
	<B><?php echo _QXZ("Dial Status"); ?> -</B><?php echo _QXZ("This is where you set the statuses that you are wanting to dial on within the lists that are active for the campaign below. To add another status to dial, select it from the drop-down list and click ADD. To remove one of the dial statuses, click on the REMOVE link next to the status you want to remove."); ?>

	<BR>
	<A NAME="campaigns-lead_order">
	<BR>
	<B><?php echo _QXZ("List Order"); ?> -</B><?php echo _QXZ("This menu is where you select how the leads that match the statuses selected above will be put in the lead hopper"); ?>:
	 <BR> &nbsp; - <?php echo _QXZ("DOWN: select the first leads loaded into the list table"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("UP: select the last leads loaded into the list table"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("UP PHONE: select the highest phone number and works its way down"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN PHONE: select the lowest phone number and works its way up"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("UP LAST NAME: starts with last names starting with Z and works its way down"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN LAST NAME: starts with last names starting with A and works its way up"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("UP COUNT: starts with most called leads and works its way down"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN COUNT: starts with least called leads and works its way up"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN COUNT 2nd NEW: starts with least called leads and works its way up inserting a NEW lead in every other lead - Must NOT have NEW selected in the dial statuses"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN COUNT 3nd NEW: starts with least called leads and works its way up inserting a NEW lead in every third lead - Must NOT have NEW selected in the dial statuses"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN COUNT 4th NEW: starts with least called leads and works its way up inserting a NEW lead in every forth lead - Must NOT have NEW selected in the dial statuses"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("RANDOM: Randomly grabs lead within the statuses and lists defined"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("UP LAST CALL TIME: Sorts by the newest local call time for the leads"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN LAST CALL TIME: Sorts by the oldest local call time for the leads"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("UP RANK: Starts with the highest rank and works its way down"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN RANK: Starts with the lowest rank and works its way up"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("UP OWNER: Starts with owners beginning with Z and works its way down"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN OWNER: Starts with owners beginning with A and works its way up"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("UP TIMEZONE: Starts with Eastern timezones and works West"); ?>
	 <BR> &nbsp; - <?php echo _QXZ("DOWN TIMEZONE: Starts with Western timezones and works East"); ?>

	<BR>
	<A NAME="campaigns-lead_order_randomize">
	<BR>
	<B><?php echo _QXZ("List Order Randomize"); ?> -</B><?php echo _QXZ("This option allows you to randomize the order of the leads in the hopper load within the results defined by the criteria set above. For instance, the List Order will be preserved, but the results will be randomized within that sorting. Setting this option to Y will enable this feature. Default is N for disabled. NOTE, if you have a large number of leads this option may slow down the speed of the hopper loading script."); ?>

	<BR>
	<A NAME="campaigns-lead_order_secondary">
	<BR>
	<B><?php echo _QXZ("List Order Secondary"); ?> -</B><?php echo _QXZ("This option allows you to select the second sorting order of the leads in the hopper load after the List Order and within the results defined by the criteria set above. For instance, the List Order will be used for the first sort of the leads, but the results will be sorted a second time within that sorting across the same set of the first List Order. For example, if you have List Order set to DOWN COUNT and you only have leads that have been attempted 1 and 2 times, then if you have the List Order Secondary set to LEAD_ASCEND all of the attempt 1 leads will be sorted by the oldest leads first and will be put into the hopper that way. Default is LEAD_ASCEND. NOTE, if you have a large number of leads using one of the CALLTIME options may slow down the speed of the hopper loading script. If List Order Randomize is enabled, this option will be ignored."); ?>

	<BR>
	<A NAME="campaigns-hopper_level">
	<BR>
	<B><?php echo _QXZ("Minimum Hopper Level"); ?> -</B><?php echo _QXZ("This is the minimum number of leads the hopper loading script tries to keep in the hopper table for this campaign. If running VDhopper script every minute, make this slightly greater than the number of leads you go through in a minute."); ?>

	<BR>
	<A NAME="campaigns-use_auto_hopper">
	<BR>
	<B><?php echo _QXZ("Automatic Hopper Level"); ?> -</B><?php echo _QXZ("Setting this to Y will allow the system to automatically adjust the hopper based off the settings you have in your campaign.  The formula it uses to do this is:<BR>Number of Active Agents * Auto Dial Level * ( 60 seconds / Dial Timeout ) * Auto Hopper Multiplier<BR>Default is Y."); ?>

	<BR>
	<A NAME="campaigns-auto_hopper_multi">
	<BR>
	<B><?php echo _QXZ("Automatic Hopper Multiplier"); ?> -</B><?php echo _QXZ("This is multiplier for the Auto Hopper. Setting this less than 1 will cause the Auto Hopper algorithm to load less leads than it normally would.  Setting this greater than 1 will cause the Auto Hopper algorithm to load more leads than it normally would.  Default is 1."); ?>

	<BR>
	<A NAME="campaigns-auto_trim_hopper">
	<BR>
	<B><?php echo _QXZ("Auto Trim Hopper"); ?> -</B><?php echo _QXZ("Setting this to Y will allow the system to automatically remove excess leads from the hopper. Default is Y."); ?>

	<BR>
	<A NAME="campaigns-hopper_vlc_dup_check">
	<BR>
	<B><?php echo _QXZ("Hopper VLC Dup Check"); ?> -</B><?php echo _QXZ("Setting this to Y will result in every lead being inserted into the hopper being checked by vendor_lead_code to make sure there are no duplicate leads inserted with the same vendor_lead_code. This is most useful when Auto-Alt-Dialing with MULTI_LEAD. Default is N."); ?>

	<BR>
	<A NAME="campaigns-manual_dial_hopper_check">
	<BR>
	<B><?php echo _QXZ("Manual Dial Hopper Check"); ?> -</B><?php echo _QXZ("Setting this to Y will mean that any manually dialed campaign phone call through the agent screen will first check for a lead in the hopper with the same phone number, and if one exists it will be deleted before the manual dial call is placed. Default is N."); ?>

	<BR>
	<A NAME="campaigns-lead_filter_id">
	<BR>
	<B><?php echo _QXZ("Lead Filter"); ?> -</B><?php echo _QXZ("This is a method of filtering your leads using a fragment of a SQL query. Use this feature with caution, it is easy to stop dialing accidentally with the slightest alteration to the SQL statement. Default is NONE."); ?>

	<BR>
	<A NAME="campaigns-call_count_limit">
	<BR>
	<B><?php echo _QXZ("Call Count Limit"); ?> -</B><?php echo _QXZ("This enforces a limit on the number of call attempts for the leads dialed in this campaign. A lead may go over this limit slightly if Lead Recycling or Auto-Alt-Dialing is enabled. Default is 0 for no limit."); ?>

	<BR>
	<A NAME="campaigns-call_count_target">
	<BR>
	<B><?php echo _QXZ("Call Count Target"); ?> -</B><?php echo _QXZ("This option is only used for reporting purposes and has no effect on leads dialed. Default is 3."); ?>

	<BR>
	<A NAME="campaigns-drop_lockout_time">
	<BR>
	<B><?php echo _QXZ("Drop Lockout Time"); ?> -</B><?php echo _QXZ("This is a number of hours that DROP abandon calls will be prevented from being dialed, to disable set to 0. This setting is very useful in countries like the UK where there are regulations preventing the attempted calling of customers within 72 hours of an Abandon, or DROP. Default is 0."); ?>

	<BR>
	<A NAME="campaigns-force_reset_hopper">
	<BR>
	<B><?php echo _QXZ("Force Reset of Hopper"); ?> -</B><?php echo _QXZ("This allows you to wipe out the hopper contents upon form submission. It should be filled again when the VDhopper script runs."); ?>

	<BR>
	<A NAME="campaigns-dial_method">
	<BR>
	<B><?php echo _QXZ("Dial Method"); ?> -</B><?php echo _QXZ("This field is the way to define how dialing is to take place. If MANUAL then the auto_dial_level will be locked at 0 unless Dial Method is changed. If RATIO then the normal dialing a number of lines for Active agents. ADAPT_HARD_LIMIT will dial predictively up to the dropped percentage and then not allow aggressive dialing once the drop limit is reached until the percentage goes down again. ADAPT_TAPERED allows for running over the dropped percentage in the first half of the shift -as defined by call_time selected for campaign- and gets more strict as the shift goes on. ADAPT_AVERAGE tries to maintain an average or the dropped percentage not imposing hard limits as aggressively as the other two methods. You cannot change the Auto Dial Level if you are in any of the ADAPT dial methods. Only the Dialer can change the dial level when in predictive dialing mode. INBOUND_MAN allows the agent to place manual dial calls from a campaign list while being able to take inbound calls between manual dial calls."); ?>

	<BR>
	<A NAME="campaigns-auto_dial_level">
	<BR>
	<B><?php echo _QXZ("Auto Dial Level"); ?> -</B><?php echo _QXZ("This is where you set how many lines the system should use per active agent. zero 0 means auto dialing is off and the agents will click to dial each number. Otherwise the system will keep dialing lines equal to active agents multiplied by the dial level to arrive at how many lines this campaign on each server should allow. The ADAPT OVERRIDE checkbox allows you to force a new dial level even though the dial method is in an ADAPT mode. This is useful if there is a dramatic shift in the quality of leads and you want to drastically change the dial_level manually."); ?>

	<BR>
	<A NAME="campaigns-dial_level_threshold">
	<BR>
	<B><?php echo _QXZ("Auto Dial Level Threshold"); ?> -</B><?php echo _QXZ("This setting only works with an ADAPT or RATIO Dial Method, and it must be set to something other than DISABLED and the number of agents setting must be above 0. This feature allows you to set a minimum number agents that predictive algorithm will work at. If the number of agents falls below the number that you have set, then the dial level will go to 1.0 until more agents log in or go into the selected state. LOGGED-IN_AGENTS will count all agents logged into the campaign, NON-PAUSED_AGENTS will only count agents that are waiting or talking, and WAITING_AGENTS will only count agents that are waiting for a call. Default is DISABLED."); ?>

	<BR>
	<A NAME="campaigns-available_only_ratio_tally">
	<BR>
	<B><?php echo _QXZ("Available Only Tally"); ?> -</B><?php echo _QXZ("This field if set to Y will leave out INCALL and QUEUE status agents when calculating the number of calls to dial when not in MANUAL dial mode. Default is N."); ?>

	<BR>
	<A NAME="campaigns-available_only_tally_threshold">
	<BR>
	<B><?php echo _QXZ("Available Only Tally Threshold"); ?> -</B><?php echo _QXZ("This setting only works with an ADAPT or RATIO Dial Method, Available Only Tally must be set to N, this setting must be set to something other than DISABLED and the number of agents setting must be above 0. This feature allows you to set the number of agents below which Available Only Tally will be enabled. If the number of agents falls below the number that you have set, then the Available Only Tally setting with go to Y temporarily until more agents log in or go into the selected state. LOGGED-IN_AGENTS will count all agents logged into the campaign, NON-PAUSED_AGENTS will only count agents that are waiting or talking, and WAITING_AGENTS will only count agents that are waiting for a call. Default is DISABLED."); ?>

	<BR>
	<A NAME="campaigns-adaptive_dropped_percentage">
	<BR>
	<B><?php echo _QXZ("Drop Percentage Limit"); ?> -</B><?php echo _QXZ("This field is where you set the limit of the percentage of dropped calls you would like while using an adaptive-predictive dial method, not MANUAL or RATIO."); ?>

	<BR>
	<A NAME="campaigns-adaptive_maximum_level">
	<BR>
	<B><?php echo _QXZ("Maximum Adapt Dial Level"); ?> -</B><?php echo _QXZ("This field is where you set the limit of the limit to the number of lines you would like dialed per agent while using an adaptive-predictive dial method, not MANUAL or RATIO. This number can be higher than the Auto Dial Level if your hardware will support it. Value must be a positive number greater than one and can have decimal places Default 3.0."); ?>

	<BR>
	<A NAME="campaigns-adaptive_latest_server_time">
	<BR>
	<B><?php echo _QXZ("Latest Server Time"); ?> -</B><?php echo _QXZ("This field is only used by the ADAPT_TAPERED dial method. You should enter in the hour and minute that you will stop calling on this campaign, 2100 would mean that you will stop dialing this campaign at 9PM server time. This allows the Tapered algorithm to decide how aggressively to dial by how long you have until you will be finished calling."); ?>

	<BR>
	<A NAME="campaigns-adaptive_intensity">
	<BR>
	<B><?php echo _QXZ("Adapt Intensity Modifier"); ?> -</B><?php echo _QXZ("This field is used to adjust the predictive intensity either higher or lower. The higher a positive number you select, the greater the dialer will increase the call pacing when it goes up and the slower the dialer will decrease the call pacing when it goes down. The lower the negative number you select here, the slower the dialer will increase the call pacing and the faster the dialer will lower the call pacing when it goes down. Default is 0. This field is not used by the MANUAL or RATIO dial methods."); ?>

	<BR>
	<A NAME="campaigns-adaptive_dl_diff_target">
	<BR>
	<B><?php echo _QXZ("Dial Level Difference Target"); ?> -</B><?php echo _QXZ("This field is used to define whether you want to target having a specific number of agents waiting for calls or calls waiting for agents. For example if you would always like to have on average one agent free to take calls immediately you would set this to -1, if you would like to target always having one call on hold waiting for an agent you would set this to 1. Default is 0. This field is not used by the MANUAL or INBOUND_MAN dial methods."); ?>

	<BR>
	<A NAME="campaigns-dl_diff_target_method">
	<BR>
	<B><?php echo _QXZ("Dial Level Difference Target Method"); ?> -</B><?php echo _QXZ("This option allows you to define whether the dial level difference target setting is applied only to the calculation of the dial level or also to the actual dialing on each dialing server. If you are running a small campaign with agents logged in on many servers you may want to use the ADAPT_CALC_ONLY option, because the CALLS_PLACED option may result in fewer calls being placed than desired. This option is only active if Dial Level Difference Target is set to something other than 0. Default is ADAPT_CALC_ONLY."); ?>

	<BR>
	<A NAME="campaigns-concurrent_transfers">
	<BR>
	<B><?php echo _QXZ("Concurrent Transfers"); ?> -</B><?php echo _QXZ("This setting is used to define the number of calls that can be sent to agents at the same time. It is recommended that this setting is left at AUTO. This field is not used by the MANUAL dial method."); ?>

	<BR>
	<A NAME="campaigns-queue_priority">
	<BR>
	<B><?php echo _QXZ("Queue Priority"); ?> -</B><?php echo _QXZ("This setting is used to define the order in which the calls from this outbound campaign should be answered in relation to the inbound calls if this campaign is in blended mode. We do not recommend setting inbound calls to a higher priority than the outbound campaign calls because people calling in expect to wait and people being called expect for someone to be there on the phone."); ?>

	<BR>
	<A NAME="campaigns-drop_rate_group">
	<BR>
	<B><?php echo _QXZ("Multiple Campaign Drop Rate Group"); ?> -</B><?php echo _QXZ("This feature allows you to set a campaign as a member of a Campaign Drop Rate Group, or a group of campaigns whose Human Answered calls and Drop calls for all campaigns in the group will be combined into a shared drop percentage, or abandon rate. This allows you to to run multiple campaigns at once and more easily control your drop rate. This is particularly useful in the UK where regulations permit this drop rate calculation method with campaign grouping for the same company even if there are several campaigns that company is running during the same day. To enable this for a campaign, just select a group from the list. There are 10 groups defined in the system by default, you can contact your system administrator to add more. Default is DISABLED."); ?>

	<BR>
	<A NAME="campaigns-inbound_queue_no_dial">
	<BR>
	<B><?php echo _QXZ("Inbound Queue No Dial"); ?> -</B><?php echo _QXZ("This feature if set to ENABLED allows you to prevent outbound auto-dialing of this campaign if there are any inbound calls waiting in queue that are part of the allowed inbound groups set in this campaign. Setting this to ALL_SERVERS will change the algorithm to calculate all inbound calls as active calls on this server even if they are on another server which will reduce the chance of placing unnecessary outbound calls if you have calls coming in on another server. Default is DISABLED.") . ' ' . _QXZ("If the selected option includes CHAT, then no outbound auto-dialing will take place while an inbound customer chat is waiting."); ?>

	<BR>
	<A NAME="campaigns-inbound_no_agents_no_dial_container">
	<BR>
	<B><?php echo _QXZ("Inbound No-Agents No-Dial"); ?> -</B><?php echo _QXZ("If set to something other than ---DISABLED---, the selected INGROUP_LIST type of Settings Container will be used to determine if any agents are ready and waiting for phone calls from at least one of the listed In-Groups. If there are less inbound agents available than the Threshold setting below, then no outbound calls will be placed for this campaign. Default is DISABLED."); ?>

	<BR>
	<A NAME="campaigns-inbound_no_agents_no_dial_threshold">
	<BR>
	<B><?php echo _QXZ("Inbound No-Agents No-Dial Threshold"); ?> -</B><?php echo _QXZ("If the Inbound No-Agents No-Dial option is enabled above, then this setting will be used to determine if the campaign will be allowed to place outbound auto-dial calls, if this number is greater than the number of inbound agents available. Default is 0, for disabled."); ?>

	<BR>
	<A NAME="campaigns-auto_alt_dial">
	<BR>
	<B><?php echo _QXZ("Auto Alt-Number Dialing"); ?> -</B><?php echo _QXZ("This setting is used to automatically dial alternate number fields while dialing in the RATIO and ADAPT dial methods when there is no contact at the main phone number for a lead, the statuses to trigger alt dial can be set in the Auto Alt Dial page. This setting is not used by the MANUAL dial method. EXTENDED alternate numbers are numbers loaded into the system outside of the standard lead information screen. Using EXTENDED you can have hundreds of phone numbers for a single customer record. Using MULTI_LEAD allows you to use a separate lead for each number on a customer account that all share a common vendor_lead_code, and the type of phone number for each is defined with a label in the Owner field. This option will disable some options on the Modify Campaign screen and show a link to the Multi-Alt Settings page which allows you to set which phone number types, defined by the label of each lead, will be dialed and in what order. This will create a special lead filter and will alter the Rank field of all of the leads inside the lists set to this campaign in order to call them in the order you have specified."); ?>

	<BR>
	<A NAME="campaigns-dial_timeout">
	<BR>
	<B><?php echo _QXZ("Dial Timeout"); ?> -</B><?php echo _QXZ("If defined, calls that would normally hang up after the timeout defined in extensions.conf would instead timeout at this amount of seconds if it is less than the extensions.conf timeout. This allows for quickly changing dial timeouts from server to server and limiting the effects to a single campaign. If you are having a lot of Answering Machine or Voicemail calls you may want to try changing this value to between 21-26 and see if results improve."); ?>

	<BR>
	<A NAME="campaigns-campaign_vdad_exten">
	<BR>
	<B><?php echo _QXZ("Routing Extension"); ?> -</B><?php echo _QXZ("This field allows for a custom outbound routing extension. This allows you to use different call handling methods depending upon how you want to route calls through your outbound campaign. Formerly called Campaign VDAD extension."); ?>
	<BR>- <?php echo _QXZ("8364 - same as 8368"); ?>
	<BR>- <?php echo _QXZ("8365 - Will send the call only to an agent on the same server as the call is placed on"); ?>
	<BR>- <?php echo _QXZ("8366 - Used for press-1, broadcast and survey campaigns"); ?>
	<BR>- <?php echo _QXZ("8367 - Will try to first send the call to an agent on the local server, then it will look on other servers"); ?>
	<BR>- <?php echo _QXZ("8368 - DEFAULT - Will send the call to the next available agent no matter what server they are on"); ?>
	<BR>- <?php echo _QXZ("8369 - Used for Answering Machine Detection after that, same behavior as 8368"); ?>
	<BR>- <?php echo _QXZ("8373 - Used for Answering Machine Detection after that same behavior as 8366"); ?>
	<BR>- <?php echo _QXZ("8374 - Used for press-1, broadcast and survey campaigns with Cepstral Text-to-speech"); ?>
	<BR>- <?php echo _QXZ("8375 - Used for Answering Machine Detection then press-1, broadcast and survey campaigns with Cepstral Text-to-speech"); ?>

	<BR>
	<A NAME="campaigns-am_message_exten">
	<BR>
	<B><?php echo _QXZ("Answering Machine Message"); ?> -</B><?php echo _QXZ("This field is for entering the prompt to play when the agent gets an answering machine and clicks on the Answering Machine Message button in the transfer conference frame. You must set this to either an audio file in the audio store or a TTS prompt if TTS is enabled on your system. You can also use lead fields to generate audio filenames using the DYN flag, for instance using DYN--A--user--B-- for agent 1234 would look for a file named 1234.wav in your audio store to play."); ?>

	<BR>
	<A NAME="campaigns-waitforsilence_options">
	<BR>
	<B><?php echo _QXZ("WaitForSilence Options"); ?> -</B><?php echo _QXZ("If Wait For Silence is desired on calls that are detected as Answering Machines then this field has those options. There are two settings separated by a comma, the first option is how long to detect silence in milliseconds and the second option is for how many times to detect that before playing the message. Default is EMPTY for disabled. A standard value for this would be wait for 2 seconds of silence twice: 2000,2"); ?>

	<BR>
	<A NAME="campaigns-am_message_wildcards">
	<BR>
	<B><?php echo _QXZ("AM Message Wildcards"); ?> -</B><?php echo _QXZ("This option, if enabled, allows you to go to the AM Message Wildcard administration page where you can define wildcards that can match data in a default lead field and can play a different message based upon the data in a specific lead. Default is N for disabled."); ?>

	<BR>
	<A NAME="campaigns-amd_send_to_vmx">
	<BR>
	<B><?php echo _QXZ("AMD send to Action"); ?> -</B><?php echo _QXZ("This option allows you to define whether a call is sent to the Answering Machine Message or CPD AMD Action when an answering machine is detected, or if it is hung up. If this is set to N, then the call will be hung up as soon as it is determined to be an answering machine. Default is N."); ?>

	<BR>
	<A NAME="campaigns-cpd_amd_action">
	<BR>
	<B><?php echo _QXZ("CPD AMD Action"); ?> -</B><?php echo _QXZ("If you are using the Sangoma ParaXip Call Progress Detection software then you will want to enable this setting either setting it to DISPO which will disposition the call as AA and hang it up if the call is being processed and has not been sent to an agent yet or MESSAGE which will send the call to the defined Answering Machine Message for this campaign. Default is DISABLED. Setting this to INGROUP will send an answering machine to an inbound group. Setting this to CALLMENU will send an answering machine to a Call Menu in the system."); ?>

	<BR>
	<A NAME="campaigns-cpd_unknown_action">
	<BR>
	<B><?php echo _QXZ("CPD Unknown Action"); ?> -</B><?php echo _QXZ("If you are using the Sangoma ParaXip Call Progress Detection software and you want to send calls that have an Unknown result to a destination other than an agent, then you will want to enable this setting either setting it to DISPO which will disposition the call as AA and hang it up if the call is being processed and has not been sent to an agent yet or MESSAGE which will send the call to the defined Answering Machine Message for this campaign. Default is DISABLED. Setting this to INGROUP will send an answering machine to an inbound group. Setting this to CALLMENU will send an answering machine to a Call Menu in the system."); ?>

	<BR>
	<A NAME="campaigns-amd_inbound_group">
	<BR>
	<B><?php echo _QXZ("AMD Inbound Group"); ?> -</B><?php echo _QXZ("If CPD AMD Action is set to INGROUP, then this is the Inbound Group that the call will be sent to if an answering machine is detected."); ?>

	<BR>
	<A NAME="campaigns-amd_callmenu">
	<BR>
	<B><?php echo _QXZ("AMD Call Menu"); ?> -</B><?php echo _QXZ("If CPD AMD Action is set to CALLMENU, then this is the Call Menu that the call will be sent to if an answering machine is detected."); ?>

	<BR>
	<A NAME="campaigns-manual_auto_next">
	<BR>
	<B><?php echo _QXZ("Manual Auto Next Seconds"); ?> -</B><?php echo _QXZ("If the Dial Method is set to MANUAL or INBOUND_MAN, then this setting will trigger the next lead to be automatically be dialed after this number of seconds. If enabled, it cannot be set lower than 5 seconds. Default is 0 for disabled."); ?>

	<BR>
	<A NAME="campaigns-manual_auto_next_options">
	<BR>
	<B><?php echo _QXZ("Manual Auto Next Options"); ?> -</B><?php echo _QXZ("If the Manual Auto Next option is enabled above, then this setting will determine if the timer to automatically dial the next number will count down while the agent is paused or not. Default is DEFAULT, which will count down while the agent is paused."); ?>

	<BR>
	<A NAME="campaigns-manual_auto_show">
	<BR>
	<B><?php echo _QXZ("Manual Auto Next Show Timer"); ?> -</B><?php echo _QXZ("If the Manual Auto Next Seconds option above is enabled, this setting will display a countdown timer to the agent if enabled. Default is N for disabled."); ?>

	<BR>
	<A NAME="campaigns-alt_number_dialing">
	<BR>
	<B><?php echo _QXZ("Manual Alt Num Dialing"); ?> -</B><?php echo _QXZ("This option allows an agent to manually dial the alternate phone number or address3 field after the main number has been called. If the option has SELECTED in it then the Alt Dial checkbox will be automatically checked for each call. If the option has TIMER in it then the Alt Phone or Address3 field will be automatically be dialed after Timer Alt Seconds. Default is N for disabled."); ?>

	<BR>
	<A NAME="campaigns-timer_alt_seconds">
	<BR>
	<B><?php echo _QXZ("Timer Alt Seconds"); ?> -</B><?php echo _QXZ("If the Manual Alt Num Dialing setting has TIMER in it then the Alt Phone or Address3 field will be automatically be dialed after this number of seconds. Default is 0 for disabled."); ?>

	<BR>
	<A NAME="campaigns-drop_call_seconds">
	<BR>
	<B><?php echo _QXZ("Drop Call Seconds"); ?> -</B><?php echo _QXZ("The number of seconds from the time the customer line is picked up until the call is considered a DROP, only applies to outbound calls."); ?>

	<BR>
	<A NAME="campaigns-drop_action">
	<BR>
	<B><?php echo _QXZ("Drop Action"); ?> -</B><?php echo _QXZ("This menu allows you to choose what happens to a call when it has been waiting for longer than what is set in the Drop Call Seconds field. HANGUP will simply hang up the call, MESSAGE will send the call the Drop Exten that you have defined below, VOICEMAIL will send the call to the voicemail box that you have defined below and IN_GROUP will send the call to the Inbound Group that is defined below. VMAIL_NO_INST will send the call to the voicemail box that you have defined below and will not play instructions after the voicemail message."); ?>

	<BR>
	<A NAME="campaigns-safe_harbor_exten">
	<BR>
	<B><?php echo _QXZ("Safe Harbor Exten"); ?> -</B><?php echo _QXZ("This is the dial plan extension that the desired Safe Harbor audio file is located at on your server."); ?>

	<BR>
	<A NAME="campaigns-safe_harbor_audio">
	<BR>
	<B><?php echo _QXZ("Safe Harbor Audio"); ?> -</B><?php echo _QXZ("This is the audio prompt file that is played if the Drop Action is set to AUDIO. Default is buzz."); ?>

	<BR>
	<A NAME="campaigns-safe_harbor_audio_field">
	<BR>
	<B><?php echo _QXZ("Safe Harbor Audio Field"); ?> -</B><?php echo _QXZ("This optional setting allows you to define a field in the list that the system will use as the audio filename for each lead in place of the Safe Harbor Audio file. If this is set to DISABLED the Safe Harbor Audio file will always be used. The system will do no validation to make sure that the audio file exists other than to make sure the value of the field is at least one character, so if you want a lead to use the default Safe harbor Audio then you just set the field value in the lead to empty. You can use the pipe character to link multiple audio files together in the field value for each lead. Default is DISABLED. Here is the list of fields that can be used for this setting: vendor_lead_code, source_id, list_id, phone_code, phone_number, title, first_name, middle_initial, last_name, address1, address2, address3, city, state, province, postal_code, country_code, gender, alt_phone, email, security_phrase, comments, rank, owner, entry_list_id"); ?>

	<BR>
	<A NAME="campaigns-safe_harbor_menu_id">
	<BR>
	<B><?php echo _QXZ("Safe Harbor Call Menu"); ?> -</B><?php echo _QXZ("This is the call menu that a call is sent to if the Drop Action is set to CALLMENU."); ?>

	<BR>
	<A NAME="campaigns-voicemail_ext">
	<BR>
	<B><?php echo _QXZ("Voicemail"); ?> -</B><?php echo _QXZ("If defined, calls that would normally DROP would instead be directed to this voicemail box to hear and leave a message."); ?>

	<BR>
	<A NAME="campaigns-drop_inbound_group">
	<BR>
	<B><?php echo _QXZ("Drop Transfer Group"); ?> -</B><?php echo _QXZ("If Drop Action is set to IN_GROUP, the call will be sent to this inbound group if it reaches Drop Call Seconds."); ?>

	<BR>
	<A NAME="campaigns-no_hopper_leads_logins">
	<BR>
	<B><?php echo _QXZ("Allow No-Hopper-Leads Logins"); ?> -</B><?php echo _QXZ("If set to Y, allows agents to login to the campaign even if there are no leads loaded into the hopper for that campaign. This function is not needed in CLOSER-type campaigns. Default is N."); ?>

	<BR>
	<A NAME="campaigns-no_hopper_dialing">
	<BR>
	<B><?php echo _QXZ("No Hopper Dialing"); ?> -</B><?php echo _QXZ("If This is enabled, the hopper will not run for this campaign. This option is only available when the dial method is set to MANUAL or INBOUND_MAN. It is recommended that you do not enable this option if you have a very large lead database, over 100,000 leads. With No Hopper Dialing, the following features do not work: lead recycling, auto-alt-dialing, list mix, list ordering with Xth NEW. If you want to use Owner Only Dialing you must have No Hopper Dialing enabled. Default is N for disabled."); ?>

	<BR>
	<A NAME="campaigns-agent_dial_owner_only">
	<BR>
	<B><?php echo _QXZ("Owner Only Dialing"); ?> -</B><?php echo _QXZ("If This is enabled, the agent will only receive leads that they are within the ownership parameters for. If this is set to USER then the agent must be the user defined in the database as the owner of this lead. If this is set to TERRITORY then the owner of the lead must match the territory listed in the User Modification screen for this agent. If this is set to USER_GROUP then the owner of the lead must match the user group that the agent is a member of. For this feature to work the dial method must be set to MANUAL or INBOUND_MAN and No Hopper Dialing must be enabled. Default is NONE for disabled. If the option has BLANK at the end, then users are allowed to dial leads with no owner defined in addition to owner defined leads."); ?>

	<BR>
	<A NAME="campaigns-owner_populate">
	<BR>
	<B><?php echo _QXZ("Owner Populate"); ?> -</B><?php echo _QXZ("If this is enabled and the owner field of the lead is blank, the owner field for the lead will populate with the user ID of the agent that handles the call first. Default is DISABLED.");

	if ($SSuser_territories_active > 0)
		{
		?>
		<BR>
		<A NAME="campaigns-agent_select_territories">
		<BR>
		<B><?php echo _QXZ("Agent Select Territories"); ?> -</B><?php echo _QXZ("If this option is enabled and the agent belongs to at least one territory, the agent will have the option of selecting territories to dial leads from. The agent will see a list of available territories upon login and they will have the ability to go back to that territory list when paused to change their territories. For this function to work the Owner Only Dialing option must be set to TERRITORY and User Territories must be enabled in the System Settings.");
		}
	?>

	<BR>
	<A NAME="campaigns-list_order_mix">
	<BR>
	<B><?php echo _QXZ("List Order Mix"); ?> -</B><?php echo _QXZ("Overrides the Lead Order and Dial Status fields. Will use the List and status parameters for the selected List Mix entry in the List Mix sub section instead. Default is DISABLED."); ?>

	<BR>
	<A NAME="campaigns-vcl_id">
	<BR>
	<B><?php echo _QXZ("List Mix ID"); ?> -</B><?php echo _QXZ("ID of the list mix. Must be from 2-20 characters in length with no spaces or other special punctuation."); ?>

	<BR>
	<A NAME="campaigns-vcl_name">
	<BR>
	<B><?php echo _QXZ("List Mix Name"); ?> -</B><?php echo _QXZ("Descriptive name of the list mix. Must be from 2-50 characters in length."); ?>

	<BR>
	<A NAME="campaigns-list_mix_container">
	<BR>
	<B><?php echo _QXZ("List Mix Detail"); ?> -</B><?php echo _QXZ("The composition of the List Mix entry. Contains the List ID, mix order, percentages and statuses that make up this List Mix. The percentages always have to add up to 100, and the lists all have to be active and set to the campaign for the order mix entry to be Activated."); ?>

	<BR>
	<A NAME="campaigns-mix_method">
	<BR>
	<B><?php echo _QXZ("List Mix Method"); ?> -</B><?php echo _QXZ("The method of mixing all of the parts of the List Mix Detail together. EVEN_MIX will mix leads from each part interleaved with the other parts, like this 1,2,3,1,2,3,1,2,3. IN_ORDER will put the leads in the order in which they are listed in the List Mix Detail screen 1,1,1,2,2,2,3,3,3. RANDOM will put them in RANDOM order 1,3,2,1,1,3,2,1,3. Default is IN_ORDER."); ?>

<!--	<BR>
	<A NAME="campaigns-agent_extended_alt_dial">
	<BR>
	<B><?php echo _QXZ("Agent Screen Extended Alt Dial"); ?> -</B><?php echo _QXZ("This feature allows for agents to access extended alternate phone numbers for leads beyond the standard Alt Phone and Address3 fields that can be used in the agent screen for phone numbers beyond the main phone number. The Extended phone numbers can be dialed automatically using the Auto-Alt-Dial feature in the Campaign settings, but enabling this Agent Screen feature will also allow for the agent to call these numbers from their agent screen as well as edit their information. This feature is in development and is not currently available."); ?>
-->

	<BR>
	<A NAME="campaigns-survey_first_audio_file">
	<BR>
	<B><?php echo _QXZ("Survey First Audio File"); ?> -</B><?php echo _QXZ("This is the audio filename that is played as soon as the customer picks up the phone when running a survey campaign."); ?>

	<BR>
	<A NAME="campaigns-survey_dtmf_digits">
	<BR>
	<B><?php echo _QXZ("Survey DTMF Digits"); ?> -</B><?php echo _QXZ("This field is where you define the digits that a customer can press as an option on a survey campaign. valid dtmf digits are 0123456789*#. All options except for the Not Interested, Third and Fourth digit options will move on to the Survey Method call path."); ?>

	<BR>
	<A NAME="campaigns-survey_wait_sec">
	<BR>
	<B><?php echo _QXZ("Survey Wait Seconds"); ?> -</B><?php echo _QXZ("This is the number of seconds when in Survey mode the system will wait for input from the person called until the survey or drop action is triggered. Is not applied if the Survey Method is HANGUP. Default is 10 seconds."); ?>

	<BR>
	<A NAME="campaigns-survey_ni_digit">
	<BR>
	<B><?php echo _QXZ("Survey Not Interested Digit"); ?> -</B><?php echo _QXZ("This field is where you define the customer digit pressed that will show they are Not Interested."); ?>

	<BR>
	<A NAME="campaigns-survey_ni_status">
	<BR>
	<B><?php echo _QXZ("Survey Not Interested Status"); ?> -</B><?php echo _QXZ("This field is where you select the status to be used for Not Interested. If DNC is used and the campaign is set to use DNC then the phone number will be automatically added to the internal DNC list and possibly the campaign-specific DNC list if that is enabled in the campaign."); ?>

	<BR>
	<A NAME="campaigns-survey_opt_in_audio_file">
	<BR>
	<B><?php echo _QXZ("Survey Opt-in Audio File"); ?> -</B><?php echo _QXZ("This is the audio filename that is played when the customer has opted-in to the survey, not opted-out or not responded if the no-response-action is set to OPTOUT. After this audio file is played, the Survey Method action is taken."); ?>

	<BR>
	<A NAME="campaigns-survey_ni_audio_file">
	<BR>
	<B><?php echo _QXZ("Survey Not Interested Audio File"); ?> -</B><?php echo _QXZ("This is the audio filename that is played when the customer has opted-out of the survey, not opted-in or not responded if the no-response-action is set to OPTIN. After this audio file is played, the call will be hung up."); ?>

	<BR>
	<A NAME="campaigns-survey_method">
	<BR>
	<B><?php echo _QXZ("Survey Method"); ?> -</B><?php echo _QXZ("This option defines what happens to a call after the customer has opted-in. AGENT_XFER will send the call to the next available agent. VOICEMAIL will send the call to the voicemail box that is specified in the Voicemail field. EXTENSION will send the customer to the extension defined in the Survey Xfer Extension field. HANGUP will hang up the customer. CAMPREC_60_WAV will send the customer to have a recording made with their response, this recording will be placed in a folder named as the campaign inside of the Survey Campaign Recording Directory. CALLMENU will send the customer to the Call Menu defined in the select list below. VMAIL_NO_INST will send the call to the voicemail box that you have defined below and will not play instructions after the voicemail message."); ?>

	<BR>
	<A NAME="campaigns-survey_no_response_action">
	<BR>
	<B><?php echo _QXZ("Survey No-Response Action"); ?> -</B><?php echo _QXZ("This is where you define what will happen if there is no response to the survey question. OPTIN will only send the call on to the Survey Method if the customer presses a dtmf digit. OPTOUT will send the customer on to the Survey Method even if they do not press a dtmf digit. DROP will drop the call using the campaign drop method but still log the call as a PM played message status."); ?>

	<BR>
	<A NAME="campaigns-survey_response_digit_map">
	<BR>
	<B><?php echo _QXZ("Survey Response Digit Map"); ?> -</B><?php echo _QXZ("This is the section where you can define a description to go with each dtmf digit option that the customer may select."); ?>

	<BR>
	<A NAME="campaigns-survey_xfer_exten">
	<BR>
	<B><?php echo _QXZ("Survey Xfer Extension"); ?> -</B><?php echo _QXZ("If the Survey Method of EXTENSION is selected then the customer call would be directed to this dialplan extension."); ?>

	<BR>
	<A NAME="campaigns-survey_camp_record_dir">
	<BR>
	<B><?php echo _QXZ("Survey Campaign Recording Directory"); ?> -</B><?php echo _QXZ("If the Survey Method of CAMPREC_60_WAV is selected then the customer response will be recorded and placed in a directory named after the campaign inside of this directory."); ?>

	<BR>
	<A NAME="campaigns-survey_third_digit">
	<BR>
	<B><?php echo _QXZ("Survey Third Digit"); ?> -</B><?php echo _QXZ("This allows for a third call path if the Third digit as defined in this field is pressed by the customer."); ?>

	<BR>
	<A NAME="campaigns-survey_fourth_digit">
	<BR>
	<B><?php echo _QXZ("Survey Fourth Digit"); ?> -</B><?php echo _QXZ("This allows for a fourth call path if the Fourth digit as defined in this field is pressed by the customer."); ?>

	<BR>
	<A NAME="campaigns-survey_third_audio_file">
	<BR>
	<B><?php echo _QXZ("Survey Third Audio File"); ?> -</B><?php echo _QXZ("This is the third audio file to be played upon the selection by the customer of the Third Digit option."); ?>

	<BR>
	<A NAME="campaigns-survey_third_status">
	<BR>
	<B><?php echo _QXZ("Survey Third Status"); ?> -</B><?php echo _QXZ("This is the third status used for the call upon the selection by the customer of the Third Digit option."); ?>

	<BR>
	<A NAME="campaigns-survey_third_exten">
	<BR>
	<B><?php echo _QXZ("Survey Third Extension"); ?> -</B><?php echo _QXZ("This is the third extension used for the call upon the selection by the customer of the Third Digit option. Default is 8300 which immediately hangs up the call after the Audio File message is played."); ?>

	<BR>
	<A NAME="campaigns-survey_fourth_audio_file">
	<BR>
	<B><?php echo _QXZ("Survey Fourth Audio File"); ?> -</B><?php echo _QXZ("This is the fourth audio file to be played upon the selection by the customer of the Fourth Digit option."); ?>

	<BR>
	<A NAME="campaigns-survey_fourth_status">
	<BR>
	<B><?php echo _QXZ("Survey Fourth Status"); ?> -</B><?php echo _QXZ("This is the fourth status used for the call upon the selection by the customer of the Fourth Digit option."); ?>

	<BR>
	<A NAME="campaigns-survey_fourth_exten">
	<BR>
	<B><?php echo _QXZ("Survey Fourth Extension"); ?> -</B><?php echo _QXZ("This is the fourth extension used for the call upon the selection by the customer of the Fourth Digit option. Default is 8300 which immediately hangs up the call after the Audio File message is played."); ?>

	<BR>
	<A NAME="campaigns-agent_display_dialable_leads">
	<BR>
	<B><?php echo _QXZ("Agent Display Dialable Leads"); ?> -</B><?php echo _QXZ("This option if enabled will show the number of dialable leads available in the campaign in the agent screen. This number is updated in the system once a minute and will be refreshed on the agent screen every few seconds."); ?>

	<BR>
	<A NAME="campaigns-survey_menu_id">
	<BR>
	<B><?php echo _QXZ("Survey Call Menu"); ?> -</B><?php echo _QXZ("If the method is set to CALLMENU, this is the Call Menu that the customer is sent to if they opt-in."); ?>

	<BR>
	<A NAME="campaigns-survey_recording">
	<BR>
	<B><?php echo _QXZ("Survey Recording"); ?> -</B><?php echo _QXZ("If enabled, this will start recording when the call is answered. Only recommended if the method is not set to transfer to an agent. Default is N for disabled. If set to Y_WITH_AMD even answering machine detected message calls will be recorded.");

	}
?>

<BR>
<A NAME="campaigns-next_agent_call">
<BR>
<B><?php echo _QXZ("Next Agent Call"); ?> -</B><?php echo _QXZ("This determines which agent receives the next call that is available:"); ?>
 <BR> &nbsp; - <?php echo _QXZ("random: orders by the random update value in the live_agents table"); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_call_start: orders by the last time an agent was sent a call. Results in agents receiving about the same number of calls overall."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_call_finish: orders by the last time an agent finished a call. AKA agent waiting longest receives first call."); ?>
 <BR> &nbsp; - <?php echo _QXZ("overall_user_level: orders by the user_level of the agent as defined in the users table a higher user_level will receive more calls."); ?>
 <BR> &nbsp; - <?php echo _QXZ("campaign_rank: orders by the rank given to the agent for the campaign. Highest to Lowest."); ?>
 <BR> &nbsp; - <?php echo _QXZ("campaign_grade_random: gives a higher probability of getting a call to the higher graded agents."); ?>
 <BR> &nbsp; - <?php echo _QXZ("fewest_calls: orders by the number of calls received by an agent for that specific inbound group. Least calls first."); ?>
 <BR> &nbsp; - <?php echo _QXZ("longest_wait_time: orders by the amount of time agent has been actively waiting for a call."); ?>

<BR>
<A NAME="campaigns-local_call_time">
<BR>
<B><?php echo _QXZ("Local Call Time"); ?> -</B><?php echo _QXZ("This is where you set during which hours you would like to dial, as determined by the local time in the area in which you are calling. This is controlled by area code and is adjusted for Daylight Savings time if applicable. General Guidelines in the USA for Business to Business is 9am to 5pm and Business to Consumer calls is 9am to 9pm."); ?>

<BR>
<A NAME="campaigns-dial_prefix">
<BR>
<B><?php echo _QXZ("Dial Prefix"); ?> -</B><?php echo _QXZ("This field allows for more easily changing a path of dialing to go out through a different method without doing a reload in Asterisk. Default is 9 based upon a 91NXXNXXXXXX in the dial plan - extensions.conf."); ?>

<BR>
<A NAME="campaigns-manual_dial_prefix">
<BR>
<B><?php echo _QXZ("Manual Dial Prefix"); ?> -</B><?php echo _QXZ("This optional field allows you to set the dial prefix to be used only when placing manual dial calls from the agent interface, such as using the MANUAL DIAL feature, or Dial Next Number when in the MANUAL dial method, or manual alt number dialing, or scheduled user-only callbacks. Default is empty for disabled, which will use the Dial Prefix defined in the field above. This option does not interfere with the 3way Dial Prefix option."); ?>

<BR>
<A NAME="campaigns-omit_phone_code">
<BR>
<B><?php echo _QXZ("Omit Phone Code"); ?> -</B><?php echo _QXZ("This field allows you to leave out the phone_code field while dialing within the system. For instance if you are dialing in the UK from the UK you would have 44 in as your phone_code field for all leads, but you just want to dial 10 digits in your dial plan extensions.conf to place calls instead of 44 then 10 digits. Default is N."); ?>

<BR>
<A NAME="campaigns-campaign_cid">
<BR>
<B><?php echo _QXZ("Campaign CallerID"); ?> -</B><?php echo _QXZ("This field allows for the sending of a custom callerid number on the outbound calls. This is the number that would show up on the callerid of the person you are calling. The default is UNKNOWN. If you are using T1 or E1s to dial out this option is only available if you are using PRIs - ISDN T1s or E1s - that have the custom callerid feature turned on, this will not work with Robbed-bit service -RBS- circuits. This will also work through most VOIP -SIP or IAX trunks- providers that allow dynamic outbound callerID. The custom callerID only applies to calls placed for the campaign directly, any 3rd party calls or transfers will not send the custom callerID. NOTE: Sometimes putting UNKNOWN or PRIVATE in the field will yield the sending of your default callerID number by your carrier with the calls. You may want to test this and put 0000000000 in the callerid field instead if you do not want to send you CallerID. For more information on CallerID priority, see the CALLERID PRIORITY entry a few entries below this one."); ?>

<BR>
<A NAME="campaigns-use_custom_cid">
<BR>
<B><?php echo _QXZ("Custom CallerID"); ?> -</B><?php echo _QXZ("When set to Y, this option allows you to use the security_phrase field in the list table as the CallerID to send out when placing for each specific lead. If this field has no CID in it then the Campaign CallerID defined above will be used instead. This option will disable the list CallerID Override if there is a CID present in the security_phrase field. Default is N. When set to AREACODE you have the ability to go into the AC-CID submenu and define multiple callerids to be used per areacode. For MANUAL and INBOUND_MAN dial methods only, you can use one of the USER_CUSTOM selections to take a CallerID number put into one of the User Custom fields in the User Modify screen and use that for outbound manual dial calls placed by an agent. For more information on CallerID priority, see the CALLERID PRIORITY entry a couple entries below this one."); ?>

<BR>
<A NAME="campaigns-cid_group_id">
<BR>
<B><?php echo _QXZ("CID Group"); ?> -</B><?php echo _QXZ("If set to something other than ---DISABLED---, the selected CID Group will override all other campaign CID settings above and will use the state or areacode based CIDs that are defined in the CID Group. Default is ---DISABLED---. For more information on CallerID priority, see the CALLERID PRIORITY entry below this one."); ?>

<BR>
<A NAME="campaigns-cid_priority">
<BR>
<B><?php echo _QXZ("CALLERID PRIORITY"); ?> -</B><?php echo _QXZ("If all of the CallerID options are enabled, this is the priority in which each type of CallerID will be used, 1 is top priority - <BR>1 - List CallerID Override <BR>2 - CID Group entry <BR>3 - AC-CID AREACODE entry <BR>4 - Custom CallerID, Security Phrase <BR>5 - USER_CUSTOM CallerID <BR>6 - Campaign CID <BR>"); ?>

<BR>
<A NAME="campaigns-campaign_rec_exten">
<BR>
<B><?php echo _QXZ("Campaign Rec extension"); ?> -</B><?php echo _QXZ("This field allows for a custom recording extension to be used with the system. This allows you to use different extensions depending upon how long you want to allow a maximum recording and what type of codec you want to record in. The default exten is 8309 which if you follow the SCRATCH_INSTALL examples will record in the WAV format for up to one hour. Another option included in the examples is 8310 which will record in GSM format for up to one hour. The recording time can be lengthened by raising the setting in the Server Modification screen in the Admin section."); ?>

<BR>
<A NAME="campaigns-campaign_recording">
<BR>
<B><?php echo _QXZ("Campaign Recording"); ?> -</B><?php echo _QXZ("This menu allows you to choose what level of recording is allowed on this campaign. NEVER will disable recording on the client. ONDEMAND is the default and allows the agent to start and stop recording as needed. ALLCALLS will start recording on the client whenever a call is sent to an agent. ALLFORCE will start recording on the client whenever a call is sent to an agent giving the agent no option to stop recording. For ALLCALLS and ALLFORCE there is an option to use the Recording Delay to cut down on very short recordings and reduce system load."); ?>

<BR>
<A NAME="campaigns-campaign_rec_filename">
<BR>
<B><?php echo _QXZ("Campaign Rec Filename"); ?> -</B><?php echo _QXZ("This field allows you to customize the name of the recording when Campaign recording is ONDEMAND or ALLCALLS. The allowed variables are CAMPAIGN INGROUP CUSTPHONE FULLDATE TINYDATE EPOCH AGENT VENDORLEADCODE LEADID CALLID RECID. If your dialers have --POST recording processing enabled, you can also use POSTVLC POSTSP POSTARRD3 POSTSTATUS. These POST options will alter the recording file name after the call has been finished and will replace the post variable with the value from the default fields. The default is FULLDATE_AGENT and would look like this 20051020-103108_6666. Another example is CAMPAIGN_TINYDATE_CUSTPHONE which would look like this TESTCAMP_51020103108_3125551212. The resulting filename must be less than 90 characters in length."); ?>

<BR>
<A NAME="campaigns-allcalls_delay">
<BR>
<B><?php echo _QXZ("Recording Delay"); ?> -</B><?php echo _QXZ("For ALLCALLS and ALLFORCE recording only. This setting will delay the starting of the recording on all calls for the number of seconds specified in this field. Default is 0."); ?>

<BR>
<A NAME="campaigns-routing_initiated_recordings">
<BR>
<B><?php echo _QXZ("Routing Initiated Recording"); ?> -</B><?php echo _QXZ("This option, if enabled, allows you to have the call routing script for Outbound auto-dial calls trigger the agent call recording instead of the agent screen. This option will only work if the recording option is set to ALLCALLS or ALLFORCE. This will not work with agent manual dialed calls. Default is N for disabled."); ?>

<BR>
<A NAME="campaigns-per_call_notes">
<BR>
<B><?php echo _QXZ("Call Notes Per Call"); ?> -</B><?php echo _QXZ("Setting this option to ENABLED will allow agents to enter in notes for every call they handle in the agent interface. The notes entry field will appear below the Comments field in the agent interface. Also, if the Agent User Group is allowed to view Call Logs then the agent will be able to view past call notes for a lead at any time. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-comments_all_tabs">
<BR>
<B><?php echo _QXZ("Comments All Tabs"); ?> -</B><?php echo _QXZ("Setting this option to ENABLED will display the Comments field on all tabs in the main agent screen. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-comments_dispo_screen">
<BR>
<B><?php echo _QXZ("Comments Dispo Screen"); ?> -</B><?php echo _QXZ("Setting this option to ENABLED will display the Comments field at the top of the agent disposition screen. If the REPLACE_CALL_NOTES option is selected, then the Comments field will replace the Call Notes field on the disposition screen if Per Call Notes is enabled. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-comments_callback_screen">
<BR>
<B><?php echo _QXZ("Comments Callback Screen"); ?> -</B><?php echo _QXZ("Setting this option to ENABLED will display the Comments field at the top of the callback scheduling screen. If the REPLACE_CB_NOTES option is selected, then the Comments field will replace the Callback Notes field on the callback scheduling screen. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-qc_comment_history">
<BR>
<B><?php echo _QXZ("QC Comments History"); ?> -</B><?php echo _QXZ("Setting this option to AUTO_OPEN will automatically open the QC comments history panel in the agent interface when a call goes to the agent screen that has QC comments. The MINIMIZE options will allow the QC Comment History panel to collapse to the bottom of the screen when you click to hide it instead of it disappearing completely. Default is CLICK."); ?>

<BR>
<A NAME="campaigns-hide_call_log_info">
<BR>
<B><?php echo _QXZ("Hide Call Log Info"); ?> -</B><?php echo _QXZ("Enabling this option will hide any call log or call count information when lead information is displayed on the agent screen. Default is N."); ?>

<BR>
<A NAME="campaigns-agent_lead_search">
<BR>
<B><?php echo _QXZ("Agent Lead Search"); ?> -</B><?php echo _QXZ("Setting this option to ENABLED will allow agents to search for leads and view lead information while paused in the agent interface. Also, if the Agent User Group is allowed to view Call Logs then the agent will be able to view past call notes for any lead that they are viewing information on. Default is DISABLED. LIVE_CALL_INBOUND will allow search for a lead while on an inbound call only. LIVE_CALL_INBOUND_AND_MANUAL will allow search for a lead while on an inbound call or while paused. When Lead Search is used on a live inbound call, the lead of the call when it went to the agent will be changed to a status of LSMERG, and the logs for the call will be modified to link to the agent selected lead instead."); ?>

<BR>
<A NAME="campaigns-agent_lead_search_method">
<BR>
<B><?php echo _QXZ("Agent Lead Search Method"); ?> -</B><?php echo _QXZ("If Agent Lead Search is enabled, this setting defines where the agent will be allowed to search for leads. SYSTEM will search the entire system, CAMPAIGNLISTS will search inside all of the active lists within the campaign, CAMPLISTS_ALL will search inside all of the active and inactive lists within the campaign, LIST will search only within the Manual Dial List ID as defined in the campaign. Default is CAMPLISTS_ALL. One of these options with USER_ in front will only search within leads that have the owner field matching the user ID of the agent, the options with GROUP_ in front will only search within leads that have the owner field matching the user group that the user is a member of, the options with TERRITORY_ in front will only search within leads that have the owner field matching the territories that the agent has selected."); ?>

<BR>
<A NAME="campaigns-campaign_script">
<BR>
<B><?php echo _QXZ("Campaign Script"); ?> -</B><?php echo _QXZ("This menu allows you to choose the script that will appear on the agents screen for this campaign. Select NONE to show no script for this campaign."); ?>

<BR>
<A NAME="campaigns-clear_script">
<BR>
<B><?php echo _QXZ("Clear Script"); ?> -</B><?php echo _QXZ("This option if enabled will clear the agent SCRIPT tab after a call has been dispositioned by the agent. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-get_call_launch">
<BR>
<B><?php echo _QXZ("Get Call Launch"); ?> -</B><?php echo _QXZ("This menu allows you to choose whether you want to auto-launch the web-form page in a separate window, auto-switch to the SCRIPT, EMAIL, or CHAT tab ,emails and chats must be allowed to have those options available, or do nothing when a call is sent to the agent for this campaign. If custom list fields are enabled on your system, FORM will open the FORM tab upon connection of a call to an agent. If the PREVIEW option is included, then the launch will happen if a lead is being previewed."); ?>

<BR>
<A NAME="campaigns-xferconf_a_dtmf">
<BR>
<B><?php echo _QXZ("Xfer-Conf DTMF"); ?> -</B><?php echo _QXZ("These fields allow for you to have two sets of Transfer Conference and DTMF presets. When the call or campaign is loaded, the agent screen will show two buttons on the transfer-conference frame and auto-populate the number-to-dial and the send-dtmf fields when pressed. If you want to allow Consultative Transfers, a fronter to a closer, have the agent use the CONSULTATIVE checkbox, which does not work for third party non-agent consultative calls. For those just have the agent click the Dial With Customer button. Then the agent can just LEAVE-3WAY-CALL and move on to their next call. If you want to allow Blind transfers of customers to an AGI script for logging or an IVR, then place AXFER in the number-to-dial field. You can also specify a custom extension after the AXFER, for instance if you want to do a call to a special IVR you have set to extension 83900 you would put AXFER83900 in the number-to-dial field."); ?>

<BR>
<A NAME="campaigns-prepopulate_transfer_preset">
<BR>
<B><?php echo _QXZ("PrePopulate Transfer Preset"); ?> -</B><?php echo _QXZ("This option will fill in the Number to Dial field in the Transfer Conference frame of the agent screen if defined. Default is N for disabled."); ?>

<BR>
<A NAME="campaigns-enable_xfer_presets">
<BR>
<B><?php echo _QXZ("Enable Transfer Presets"); ?> -</B><?php echo _QXZ("This option will enable the Presets sub menu to appear at the top of the Campaign Modification page, and also you will have the ability to specify Preset dialing numbers for Agents to use in the Transfer-Conference frame of the agent interface. Default is DISABLED. CONTACTS is an option only if contact_information is enabled on your system, that is a custom feature."); ?>

<BR>
<A NAME="campaigns-hide_xfer_number_to_dial">
<BR>
<B><?php echo _QXZ("Hide Transfer Number to Dial"); ?> -</B><?php echo _QXZ("This option will hide the Number to Dial field in the Transfer-Conference frame of the agent interface. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-quick_transfer_button">
<BR>
<B><?php echo _QXZ("Quick Transfer Button"); ?> -</B><?php echo _QXZ("This option will add a Quick Transfer button to the agent screen below the Transfer-Conf button that will allow one click blind transferring of calls to the selected In-Group or number. IN_GROUP will send calls to the Default Xfer Group for this Campaign, or In-Group if there was an inbound call. The PRESET options will send the calls to the preset selected. Default is N for disabled. The LOCKED options are used to lock the value to the Quick Transfer Button even if the agent uses the transfer conference features during a call before using the Quick Transfer Button."); ?>

<BR>
<A NAME="campaigns-custom_3way_button_transfer">
<BR>
<B><?php echo _QXZ("Custom 3-Way Button Transfer"); ?> -</B><?php echo _QXZ("This option will add a Custom Transfer button to the agent screen below the Transfer-Conf button that will allow one click three way calls using the selected preset or field. The PRESET_ options will place calls using the defined preset value. The FIELD_ options will place calls using the number in the selected field from the lead. DISABLED will not show the button on the agent screen. The PARK_ options will park the customer before dialing. Default is DISABLED. The VIEW_PRESET option will simply open the transfer frame and the preset frame. The VIEW_CONTACTS option will open a contacts search window, this will only work if Enable Presets is set to CONTACTS."); ?>

<BR>
<A NAME="campaigns-three_way_call_cid">
<BR>
<B><?php echo _QXZ("3-Way Call Outbound CallerID"); ?> -</B><?php echo _QXZ("This defines what is sent out as the outbound callerID number from 3-way calls placed by the agent, CAMPAIGN uses the custom campaign callerID, CUSTOMER uses the number of the customer that is active on the agents screen and AGENT_PHONE uses the callerID for the phone that the agent is logged into. AGENT_CHOOSE allows the agent to choose which callerID to use for 3-way calls from a list of choices. CUSTOM_CID will use the Custom CID that is defined in the security_phrase field of the list table for the lead."); ?>

<BR>
<A NAME="campaigns-three_way_dial_prefix">
<BR>
<B><?php echo _QXZ("3-Way Call Dial Prefix"); ?> -</B><?php echo _QXZ("This defines what is used as the dial prefix for 3-way calls, default is empty so the campaign dial prefix is used, passthru so you can hear ringing is 88."); ?>

<BR>
<A NAME="campaigns-customer_3way_hangup_logging">
<BR>
<B><?php echo _QXZ("Customer 3-Way Hangup Logging"); ?> -</B><?php echo _QXZ("If this option is ENABLED the user_call_log will log when a customer hangup up if they hang up during a 3-way call. Also, this can allow for the Customer 3-way hangup action if one is defined below. Default is ENABLED."); ?>

<BR>
<A NAME="campaigns-customer_3way_hangup_seconds">
<BR>
<B><?php echo _QXZ("Customer 3-Way Hangup Seconds"); ?> -</B><?php echo _QXZ("If Customer 3-way logging is enabled, this option allows you to define the number of seconds after the customer hangup is detected before it is actually logged and the optional customer 3-way hangup action is executed. Default is 5 seconds."); ?>

<BR>
<A NAME="campaigns-customer_3way_hangup_action">
<BR>
<B><?php echo _QXZ("Customer 3-Way Hangup Action"); ?> -</B><?php echo _QXZ("If Customer 3-way logging is enabled, this option allows you to have the agent screen automatically hang up on the call and go to the DISPO screen if this option is set to DISPO. Default is NONE."); ?>

<BR>
<A NAME="campaigns-three_way_record_stop">
<BR>
<B><?php echo _QXZ("3-Way Recording Stop"); ?> -</B><?php echo _QXZ("If this option is enabled, recording of the session will stop when an agent clicks on the DIAL WITH CUSTOMER or PARK CUSTOMER DIAL transfer buttons. Default is N for disabled."); ?>

<BR>
<A NAME="campaigns-hangup_xfer_record_start">
<BR>
<B><?php echo _QXZ("Hangup Xfer Recording Start"); ?> -</B><?php echo _QXZ("If this option is enabled, recording of the session will start when the agent clicks on the HANGUP XFER button in the transfer conference section. Default is N for disabled."); ?>

<BR>
<A NAME="campaigns-ivr_park_call">
<BR>
<B><?php echo _QXZ("Park Call IVR"); ?> -</B><?php echo _QXZ("This option will allow an agent to park a call with a separate IVR PARK CALL button on their agent interface if this is ENABLED or ENABLED_PARK_ONLY. The ENABLED_PARK_ONLY option will allow the agent to send the call to park but not click to retrieve the call like the ENABLED option. The ENABLED_BUTTON_HIDDEN option allows the function through the API only. Default is DISABLED. This feature requires Asterisk 1.8 or higher to work."); ?>

<BR>
<A NAME="campaigns-ivr_park_call_agi">
<BR>
<B><?php echo _QXZ("Park Call IVR AGI"); ?> -</B><?php echo _QXZ("If the Park Call IVR field is not DISABLED, then this field is used as the AGI application string that the customer is sent to. This is a setting that should be set by your administrator if possible. This feature requires Asterisk 1.8 or higher to work."); ?>

<BR>
<A NAME="campaigns-timer_action">
<BR>
<B><?php echo _QXZ("Timer Action"); ?> -</B><?php echo _QXZ("This feature allows you to trigger actions after a certain amount of time. the D1 and D2 DIAL options will launch a call to the Transfer Conference Number presets and send them to the agent session, this is usually used for simple IVR validation AGI applications or just to play a pre-recorded message. WEBFORM will open the web form address. MESSAGE_ONLY will simply display the message that is in the field below. NONE will disable this feature and is the default. HANGUP will hang up the call when the timer is triggered, CALLMENU will send the call to the Call Menu specified in the Timer Action Destination field, EXTENSION will send the call to the Extension that is specified in the Timer Action Destination field, IN_GROUP will send the call to the In-Group specified in the Timer Action Destination field."); ?>

<BR>
<A NAME="campaigns-timer_action_message">
<BR>
<B><?php echo _QXZ("Timer Action Message"); ?> -</B><?php echo _QXZ("This is the message that appears on the agent screen at the time the Timer Action is triggered."); ?>

<BR>
<A NAME="campaigns-timer_action_seconds">
<BR>
<B><?php echo _QXZ("Timer Action Seconds"); ?> -</B><?php echo _QXZ("This is the amount of time after the call is connected to the customer that the Timer Action is triggered. Default is -1 which is also inactive."); ?>

<BR>
<A NAME="campaigns-timer_action_destination">
<BR>
<B><?php echo _QXZ("Timer Action Destination"); ?> -</B><?php echo _QXZ("This field is where you specify the Call Menu, Extension or In-Group that you want the call sent to if the Time Action is set to CALLMENU, EXTENSION or IN_GROUP. Default is empty."); ?>

<BR>
<A NAME="campaigns-scheduled_callbacks">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks"); ?> -</B><?php echo _QXZ("This option allows an agent to disposition a call as CALLBK and choose the data and time at which the lead will be re-activated."); ?>

<BR>
<A NAME="campaigns-scheduled_callbacks_alert">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Alert"); ?> -</B><?php echo _QXZ("This option allows the callbacks status line in the agent interface to be red, blink or blink red when there are AGENTONLY scheduled callbacks that have hit their trigger time and date. Default is NONE for standard status line. The DEFER options will stop blinking and-or displaying in red when you check the callbacks, until the number of callbacks changes."); ?>

<BR>
<A NAME="campaigns-scheduled_callbacks_email_alert">
<BR>
<B><?php echo _QXZ("Send Callbacks Email"); ?> -</B><?php echo _QXZ("This option will cause the dialer to attempt to send an email notification to the owner of a USERONLY scheduled callback when the callback time arrives.  In order for this to work, the owner must have a valid email address on their user profile, and the system must have a settings container named AGENT_CALLBACK_EMAIL in the Settings Container section of the admin portal.  That container must also define the following variables for the email to be sent properly: email_from - the email address the notification shows as coming from, email_subject - the subject shown on the email, and email_body_begin which when declared will treat all text between it and the string email_body_end as the body of the email.  Values from the lead ,the vicidial_list table, can be included using the same --A-- --B-- declaration style used in the scripts feature. These emails are only sent when the agent is logged into the agent screen."); ?>

<BR>
<A NAME="campaigns-scheduled_callbacks_count">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Count"); ?> -</B><?php echo _QXZ("These options allows you to limit the viewable callbacks in the agent callback alert section on the agent screen, to only LIVE callbacks.  LIVE callbacks are user-only scheduled callbacks that have hit their trigger date and time. ACTIVE call backs are user-only callbacks that are active in the system but have not yet triggered.  You can view both ACTIVE and LIVE callbacks by selecting ALL_ACTIVE.  Default is ALL_ACTIVE."); ?>

<BR>
<A NAME="campaigns-callback_days_limit">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Days Limit"); ?> -</B><?php echo _QXZ("This option allows you to reduce the agent scheduled callbacks calendar to a selectable number of days from today, the full 12 month calendar will still be displayed, but only the set number of days will be selectable. Default is 0 for unlimited."); ?>

<BR>
<A NAME="campaigns-callback_hours_block">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Hours Block"); ?> -</B><?php echo _QXZ("This option allows you to restrict a USERONLY scheduled callback from being displayed on the agent callback list until X hours after it has been set. Default is 0 for no block."); ?>

<BR>
<A NAME="campaigns-callback_list_calltime">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Calltime Block"); ?> -</B><?php echo _QXZ("This option if enabled will prevent the scheduled callback in the agent callback list from being dialed if it is outside of the scheduled calltime for the campaign. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-callback_active_limit">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Active Limit"); ?> -</B><?php echo _QXZ("This option if enabled will limit an agent to this number of active or live user-only callbacks. If the limit is reached, the agent will not be able to select the My Callback checkbox when setting a callback for a lead. Default is 0 for disabled."); ?>

<BR>
<A NAME="campaigns-callback_active_limit_override">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Active Limit Override"); ?> -</B><?php echo _QXZ("Enabling this option will allow the Custom User 3 field to override the Scheduled Callbacks Active Limit. Default is N for disabled."); ?>

<BR>
<A NAME="campaigns-callback_display_days">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Display Days"); ?> -</B><?php echo _QXZ("Enabling this option will restrict the scheduled callbacks listings on the agent screen to showing only callbacks set to trigger a number of days from today. The -day- is a standard calendar day, so if this is set to 1, only the current callbacks for today until midnight will be displayed. Default is 0 for disabled."); ?>

<BR>
<A NAME="campaigns-my_callback_option">
<BR>
<B><?php echo _QXZ("My Callbacks Checkbox Default"); ?> -</B><?php echo _QXZ("This option allows you to pre-set the My Callback checkbox on the agent scheduled callback screen. CHECKED will check the checkbox automatically for every call. Default is UNCHECKED."); ?>

<BR>
<A NAME="campaigns-show_previous_callback">
<BR>
<B><?php echo _QXZ("Show Previous Callback"); ?> -</B><?php echo _QXZ("This option if enabled will show on the agent screen with a separate yellow panel information about the previously set callback that the agent has up on their screen. Disabling this option will not show that panel. Default is ENABLED."); ?>

<BR>
<A NAME="campaigns-callback_useronly_move_minutes">
<BR>
<B><?php echo _QXZ("Scheduled Callbacks Useronly Move Minutes"); ?> -</B><?php echo _QXZ("This option if set to a number greater than 0, will change all USERONLY Scheduled Callbacks that are X minutes after their callback time to ANYONE callbacks. This process runs every minute. Default is 0 for disabled."); ?>

<BR>
<A NAME="campaigns-next_dial_my_callbacks">
<BR>
<B><?php echo _QXZ("Next-Dial My Callbacks"); ?> -</B><?php echo _QXZ("This option only works for MANUAL and INBOUND_MAN dial methods, and also only if No Hopper Dialing is enabled. This feature will look for Scheduled Callbacks that have triggered for the agent and dial them next when the agent clicks on the Dial Next Number button on their agent screen. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-wrapup_seconds">
<BR>
<B><?php echo _QXZ("Wrap Up Seconds"); ?> -</B><?php echo _QXZ("The number of seconds to force an agent to wait before allowing them to receive or dial another call. The timer begins as soon as an agent hangs up on their customer - or in the case of alternate number dialing when the agent finishes the lead - Default is 0 seconds. If the timer runs out before the agent has dispositioned the call, the agent still will NOT move on to the next call until they select a disposition."); ?>

<BR>
<A NAME="campaigns-wrapup_message">
<BR>
<B><?php echo _QXZ("Wrap Up Message"); ?> -</B><?php echo _QXZ("This is a campaign-specific message to be displayed on the wrap up screen if wrap up seconds is set. You can use a Script in the system if you use the script id with WUSCRIPT in front of it, so if you wanted to use a script called agent_script, then you would put WUSCRIPTagent_script in this field."); ?>

<BR>
<A NAME="campaigns-wrapup_bypass">
<BR>
<B><?php echo _QXZ("Wrap Up Bypass"); ?> -</B><?php echo _QXZ("If set to ENABLED then the agent will be able to click a link to stop the Wrap Up timer before the time is completed. Default is ENABLED."); ?>

<BR>
<A NAME="campaigns-wrapup_after_hotkey">
<BR>
<B><?php echo _QXZ("Wrap Up After Hotkey"); ?> -</B><?php echo _QXZ("If set to ENABLED and the campaign has hotkeys configured and the agent terminates a call with a hotkey, then the wrap up settings will be used after that call. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-disable_dispo_screen">
<BR>
<B><?php echo _QXZ("Disable Dispo Screen"); ?> -</B><?php echo _QXZ("This option allows you to disable the disposition screen in the agent interface. The Disable Dispo Status field below must be filled in for this option to work. Default is DISPO_ENABLED. The DISPO_SELECT_DISABLED option will not disable the dispo screen completely, but will display the dispo screen without any dispositions, this option should only be used if you want to force your agents to use your CRM software which will send the status to the system through the API."); ?>

<BR>
<A NAME="campaigns-disable_dispo_status">
<BR>
<B><?php echo _QXZ("Disable Dispo Status"); ?> -</B><?php echo _QXZ("If the Disable Dispo Screen option is set to DISPO_DISABLED, then this field must be filled in. You can use any disposition you want for this field as long as it is 1 to 6 characters in length with only letters and numbers."); ?>

<BR>
<A NAME="campaigns-script_top_dispo">
<BR>
<B><?php echo _QXZ("Script on top of Dispo"); ?> -</B><?php echo _QXZ("If you are using an IFRAME in your SCRIPT tab contents, and the page you are using is sending statuses through the Agent API, you may want to use this feature to cover the Dispostion screen with the script tab after a call is hung up. Default is N for disabled."); ?>

<BR>
<A NAME="campaigns-dead_max">
<BR>
<B><?php echo _QXZ("Dead Call Max Seconds"); ?> -</B><?php echo _QXZ("If this is set to greater than 0, after a customer hangs up and the agent has not clicked on the Hangup Customer button in this number of seconds, the call will automatically be hung up, the status below will be set and the agent will be paused. Default is 0 for disabled."); ?>

<BR>
<A NAME="campaigns-dead_max_dispo">
<BR>
<B><?php echo _QXZ("Dead Call Max Status"); ?> -</B><?php echo _QXZ("If Dead Call Max Seconds is enabled, this is the status set for the call when the agent dead call is not hung up past the number of seconds set above. Default is DCMX."); ?>

<BR>
<A NAME="campaigns-dead_to_dispo">
<BR>
<B><?php echo _QXZ("Dead Call to Dispo Only"); ?> -</B><?php echo _QXZ("If Dead Call Max Seconds is set greater than 0, this option can be enabled if you want to send the agent to the dispo screen after a dead call without automatically dispositioning it. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-dispo_max">
<BR>
<B><?php echo _QXZ("Dispo Call Max Seconds"); ?> -</B><?php echo _QXZ("If this is set to greater than 0, and the agent has not selected a disposition status in this number of seconds, the call will automatically be set to the status below and the agent will be paused. Default is 0 for disabled."); ?>

<BR>
<A NAME="campaigns-dispo_max_dispo">
<BR>
<B><?php echo _QXZ("Dispo Call Max Status"); ?> -</B><?php echo _QXZ("If Dispo Call Max Seconds is enabled, this is the status set for the call when the agent has not selected a status past the number of seconds set above. Default is DISMX."); ?>

<BR>
<A NAME="campaigns-pause_max">
<BR>
<B><?php echo _QXZ("Agent Pause Max Seconds"); ?> -</B><?php echo _QXZ("If this is set to greater than 0, and the agent has not gone out of PAUSED status in this number of seconds, the agent will automatically be logged out of the agent screen. Default is 0 for disabled."); ?>

<BR>
<A NAME="campaigns-pause_max_dispo">
<BR>
<B><?php echo _QXZ("Agent Pause Max Status"); ?> -</B><?php echo _QXZ("If Agent Pause Max Seconds is enabled, this is the status set for the call when the agent has not selected a status past the number of seconds set above. This situation can happen when manual alt dial is enabled and the agent has not finished the lead they are on. Default is PAUSMX."); ?>

<BR>
<A NAME="campaigns-ready_max_logout">
<BR>
<B><?php echo _QXZ("Agent Ready Max Seconds Logout"); ?> -</B><?php echo _QXZ("If this is set to greater than 0, and the agent has not gone out of READY or CLOSER status in this number of seconds, the agent will automatically be logged out of the agent screen. Default is 0 for disabled."); ?>

<BR>
<A NAME="campaigns-customer_gone_seconds">
<BR>
<B><?php echo _QXZ("Customer Gone Warning Seconds"); ?> -</B><?php echo _QXZ("This setting controls the number of seconds after a customer hangs up before a warning that the customer has hung up will appear on the agent screen. Default is 30."); ?>

<BR>
<A NAME="campaigns-screen_labels">
<BR>
<B><?php echo _QXZ("Agent Screen Labels"); ?> -</B><?php echo _QXZ("You can select a set of agent screen labels to use with this option. Default is --SYSTEM-SETTINGS-- for the default labels."); ?>

<BR>
<A NAME="campaigns-allow_required_fields">
<BR>
<B><?php echo _QXZ("Allow Required Fields"); ?> -</B><?php echo _QXZ("Must be enabled for required fields as defined in screen labels to work. Once a field is designated as required, the agent will not be allowed to hang up a lead until there is something filled in within that field, this will affect all calls the agent receives or places. Default is N for disabled."); ?>

<BR>
<A NAME="campaigns-status_display_fields">
<BR>
<B><?php echo _QXZ("Status Display Fields"); ?> -</B><?php echo _QXZ("You can select which variables for calls will be displayed in the status line of the agent screen. CALLID will display the 20 character unique call ID, LEADID will display the system lead ID, LISTID will display the list ID, NAME will display the customer name. Default is CALLID."); ?>

<BR>
<A NAME="campaigns-status_display_ingroup">
<BR>
<B><?php echo _QXZ("Status Display In-Group"); ?> -</B><?php echo _QXZ("This option if set to ENABLED will display the In-Group name in the agent screen when an inbound call is sent to the agent. Default is ENABLED."); ?>

<BR>
<A NAME="campaigns-agent_display_fields">
<BR>
<B><?php echo _QXZ("Agent Display Fields"); ?> -</B><?php echo _QXZ("This option allows you to display hidden fields as read-only in the agent screen. Available fields are entry_date, source_id, date_of_birth, rank, owner, last_local_call_time. Default is blank."); ?>

<BR>
<A NAME="campaigns-agent_screen_time_display">
<BR>
<B><?php echo _QXZ("Agent Screen Time Display"); ?> -</B><?php echo _QXZ("This option allows you to display a link on the agent screen that when clicked will open a frame showing the agent their time statistics for the day. There are several options that will determine what information is displayed. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-use_internal_dnc">
<BR>
<B><?php echo _QXZ("Use Internal DNC List"); ?> -</B><?php echo _QXZ("This defines whether this campaign is to filter leads against the Internal DNC list. If it is set to Y, the hopper will look for each phone number in the DNC list before placing it in the hopper. If it is in the DNC list then it will change that lead status to DNCL so it cannot be dialed. Default is N. The AREACODE option is just like the Y option, except it is used to also filter out an entire area code in North America from being dialed, in this case using the 201XXXXXXX entry in the DNC list would block all calls to the 201 areacode if enabled."); ?>

<BR>
<A NAME="campaigns-use_campaign_dnc">
<BR>
<B><?php echo _QXZ("Use Campaign DNC List"); ?> -</B><?php echo _QXZ("This defines whether this campaign is to filter leads against a DNC list that is specific to that campaign only. If it is set to Y, the hopper will look for each phone number in the campaign-specific DNC list before placing it in the hopper. If it is in the campaign-specific DNC list then it will change that lead status to DNCC so it cannot be dialed. Default is N. The AREACODE option is just like the Y option, except it is used to also filter out an entire area code in North America from being dialed, in this case using the 201XXXXXXX entry in the DNC list would block all calls to the 201 areacode if enabled."); ?>

<BR>
<A NAME="campaigns-use_other_campaign_dnc">
<BR>
<B><?php echo _QXZ("Other Campaign DNC"); ?> -</B><?php echo _QXZ("If the option Use Campaign DNC List is enabled, this option can allow you to use a different campaign DNC list, just put the Campaign ID of the other campaign in this field. If you use this option, the original campaign DNC list will no longer be checked, only the OTHER campaign DNC list will be used. This does not affect use of the Internal System DNC list. Default is EMPTY."); ?>

<BR>
<A NAME="campaigns-closer_campaigns">
<BR>
<B><?php echo _QXZ("Allowed Inbound Groups"); ?> -</B><?php echo _QXZ("For CLOSER campaigns only. Here is where you select the inbound groups you want agents in this CLOSER campaign to be able to take calls from. It is important for BLENDED inbound-outbound campaigns only to select the inbound groups that are used for agents in this campaign. The calls coming into the inbound groups selected here will be counted as active calls for a blended campaign even if all agents in the campaign are not logged in to receive calls from all of those selected inbound groups."); ?>

<BR>
<A NAME="campaigns-agent_pause_codes_active">
<BR>
<B><?php echo _QXZ("Agent Pause Codes Active"); ?> -</B><?php echo _QXZ("Allows agents to select a pause code when they click on the PAUSE button in the agent screen. Pause codes are definable per campaign at the bottom of the campaign view detail screen and they are stored in the agent_log table. Default is N. FORCE will force the agents to choose a PAUSE code if they click on the PAUSE button."); ?>

<BR>
<A NAME="campaigns-auto_pause_precall">
<BR>
<B><?php echo _QXZ("Auto Pause Pre-Call Work"); ?> -</B><?php echo _QXZ("In auto-dial mode, this setting if enabled will set the agent to paused automatically when the agent clicks on any of the following functions that requires them to be paused- Manual Dial, Fast Dial, Lead Search, Call Log View, Callbacks Check, Enter Pause Code. Default is N for inactive."); ?>

<BR>
<A NAME="campaigns-auto_resume_precall">
<BR>
<B><?php echo _QXZ("Auto Resume Pre-Call Work"); ?> -</B><?php echo _QXZ("In auto-dial mode, this setting if enabled will set the agent to active automatically when the agent clicks out of, or cancels, on any of the following functions that requires them to be paused- Manual Dial, Fast Dial, Lead Search, Call Log View, Callbacks Check, Enter Pause Code. Default is N for inactive."); ?>

<BR>
<A NAME="campaigns-auto_pause_precall_code">
<BR>
<B><?php echo _QXZ("Auto Pause Pre-Call Code"); ?> -</B><?php echo _QXZ("If the Auto Pause Pre-Call Work function above is active, and Agent Pause Codes is active, this setting will be the pause code that is used when the agent is paused for these activities. Default is PRECAL."); ?>

<BR>
<A NAME="campaigns-disable_alter_custdata">
<BR>
<B><?php echo _QXZ("Disable Alter Customer Data"); ?> -</B><?php echo _QXZ("If set to Y, does not change any of the customer data record when an agent dispositions the call. Default is N."); ?>

<BR>
<A NAME="campaigns-disable_alter_custphone">
<BR>
<B><?php echo _QXZ("Disable Alter Customer Phone"); ?> -</B><?php echo _QXZ("If set to Y, does not change the customer phone number when an agent dispositions the call. Default is Y. Use the HIDE option to completely remove the customer phone number from the agent display."); ?>

<BR>
<A NAME="campaigns-display_queue_count">
<BR>
<B><?php echo _QXZ("Agent Display Queue Count"); ?> -</B><?php echo _QXZ("If set to Y, when a customer is waiting for an agent, the Queue Calls display at the top of the agent screen will turn red and show the number of waiting calls. Default is Y."); ?>

<BR>
<A NAME="campaigns-manual_dial_override">
<BR>
<B><?php echo _QXZ("Manual Dial Override"); ?> -</B><?php echo _QXZ("The setting can override the Users setting for manual dial ability for agents when they are logged into this campaign. NONE will follow the Users setting, ALLOW_ALL will allow any agent logged into this campaign to place manual dial calls, DISABLE_ALL will not allow anyone logged into this campaign to place manual dial calls. Default is NONE."); ?>

<BR>
<A NAME="campaigns-manual_dial_override_field">
<BR>
<B><?php echo _QXZ("Manual Dial Override Field"); ?> -</B><?php echo _QXZ("The setting if set to ENABLED will show the Manual Dial Override field in the agent screen. Default is ENABLED."); ?>

<BR>
<A NAME="campaigns-manual_dial_list_id">
<BR>
<B><?php echo _QXZ("Manual Dial List ID"); ?> -</B><?php echo _QXZ("The default list_id to be used when an agent places a manual call and a new lead record is created in the list table. Default is 999. This field can contain digits only."); ?>

<BR>
<A NAME="campaigns-manual_dial_filter">
<BR>
<B><?php echo _QXZ("Manual Dial Filter"); ?> -</B><?php echo _QXZ("This allows you to filter the calls that agents make in manual dial mode for this campaign by any combination of the following: DNC - to kick out, CAMPAIGNLISTS - the number must be within the lists for the campaign, NONE - no filter on manual dial or fast dial lists. CAMPLISTS_ALL - will include inactive lists in the search for the number. WITH_ALT will also search the Alt Phone field for the phone number. WITH_ALT_ADDR3 will also search the Alt Phone field and the Address 3 field for the phone number. As for the DNC options, DNC will use the campaign settings for DNC filtering, CAMPDNC will ignore the campaign DNC settings and will use the this campaign DNC list, INTERNALDNC will ignore the campaign DNC settings and will use the internal DNC list"); ?>

<BR>
<A NAME="campaigns-manual_dial_search_checkbox">
<BR>
<B><?php echo _QXZ("Manual Dial Search Checkbox"); ?> -</B><?php echo _QXZ("This allows you to define if you want the manual dial search checkbox to be selected by default or not. If an option with RESET is chosen, then the checkbox will be reset after every call. If an option with LOCK is chosen, then the agent will not be able to click on the checkbox. Default is SELECTED."); ?>

<BR>
<A NAME="campaigns-manual_dial_search_filter">
<BR>
<B><?php echo _QXZ("Manual Dial Search Filter"); ?> -</B><?php echo _QXZ("This allows the agent to search only within lists belonging to this campaign when the agent has the Manual Dial Search Checkbox selected in the manual dial screen. The options are, CAMPLISTS_ONLY - will check for the number within the active lists for the campaign, CAMPLISTS_ALL - will also include inactive lists in the search for the number, NONE - no filter on manual dial searching. Default is NONE. If a lead is not found, then a new lead will be added. WITH_ALT will also search the Alt Phone field for the phone number. WITH_ALT_ADDR3 will also search the Alt Phone field and the Address 3 field for the phone number."); ?>

<BR>
<A NAME="campaigns-manual_preview_dial">
<BR>
<B><?php echo _QXZ("Manual Preview Dial"); ?> -</B><?php echo _QXZ("This allows the agent in manual dial mode to see the lead information when they click Dial Next Number before they actively dial the phone call. There is an optional link to SKIP the lead and move on to the next one if selected. Default is PREVIEW_AND_SKIP."); ?>

<BR>
<A NAME="campaigns-manual_dial_lead_id">
<BR>
<B><?php echo _QXZ("Manual Dial by Lead ID"); ?> -</B><?php echo _QXZ("This allows the agent in manual dial mode to place a call by lead_id instead of a phone number. Default is N for disabled."); ?>

<BR>
<A NAME="campaigns-api_manual_dial">
<BR>
<B><?php echo _QXZ("Manual Dial API"); ?> -</B><?php echo _QXZ("This option allows you to set the Agent API to make either one call at a time, STANDARD, or the ability to queue up manual dial calls and have them dial automatically once the agent goes on pause or is available to take their next call with the option to disable the automatic dialing of these calls, QUEUE, or QUEUE_AND_AUTOCALL which is the same as QUEUE but without the option to disable the automatic dialing of these calls. If an agent has more than one call queued up for them they will see the count of how many manual dial calls are in queue right below the Pause button, or Dial Next Number button. We suggest that if QUEUE is used that you send API actions using the preview=YES option so you are not repeatedly dialing calls for the agent without notice. Also, if using QUEUE and heavily using manual dial calls in a non MANUAL dial method, we would recommend setting the Agent Pause After Each Call option to Y. Default is STANDARD."); ?>

<BR>
<A NAME="campaigns-manual_dial_call_time_check">
<BR>
<B><?php echo _QXZ("Manual Call Time Check"); ?> -</B><?php echo _QXZ("If this option is enabled, it will check all manual dial calls to make sure they are within the call time settings set for the campaign. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-manual_dial_cid">
<BR>
<B><?php echo _QXZ("Manual Dial CID"); ?> -</B><?php echo _QXZ("This defines whether an agent making manual dial calls will have the campaign callerID settings used, or their agent phone callerID settings used. Default is CAMPAIGN. If the Use Custom CID campaign option is enabled or the list Campaign CID Override setting is used, this setting will be ignored."); ?>

<BR>
<A NAME="campaigns-manual_dial_timeout">
<BR>
<B><?php echo _QXZ("Manual Dial Timeout"); ?> -</B><?php echo _QXZ("This is an override field that, if populated, will override the campaign dial timeout setting for manual dialed calls. Default is blank for disabled."); ?>

<BR>
<A NAME="campaigns-post_phone_time_diff_alert">
<BR>
<B><?php echo _QXZ("Phone Post Time Difference Alert"); ?> -</B><?php echo _QXZ("This manual-dial-only feature, if enabled, will display an alert if the time zone for the lead postal code, or zip code, is different from the time zone of the area code of the phone number for the lead. The OUTSIDE_CALLTIME_ONLY option will only show the alert if the two time zones are different and one of the time zones is outside of the call time selected for the campaign. OUTSIDE_CALLTIME_PHONE will only check the time zone of the phone number of the lead and alert if it is outside of the local call time. OUTSIDE_CALLTIME_POSTAL will only check the time zone of the postal code of the lead and alert if it is outside of the local call time. OUTSIDE_CALLTIME_BOTH will check the postal code and phone number for being within the local call time, even if they are in the same time zone. These alerts will show in the call log info, callbacks list info, search results info, when a lead is dialed and when a lead is previewed. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-in_group_dial">
<BR>
<B><?php echo _QXZ("In-Group Manual Dial"); ?> -</B><?php echo _QXZ("This feature allows you to enable the ability for agents to place manual dial outbound calls that are logged as in-group calls assigned to a specific in-group. The MANUAL_DIAL option allows the placing of phone calls out through an In-Group to the agent placing the call. The NO_DIAL option allows the agent to log time on a call that does not exist, as if it were a real call, this is often used for logging email or faxing time. The BOTH option will allow both call and no-call in-group dialing. The default is DISABLED."); ?>

<BR>
<A NAME="campaigns-in_group_dial_select">
<BR>
<B><?php echo _QXZ("In-Group Manual Dial Select"); ?> -</B><?php echo _QXZ("This option is only active if the above In-Group Manual Dial feature is not DISABLED. This option restricts the selectable In-Groups that the agent can place In-Group Manual Dial calls through. CAMPAIGN_SELECTED will show only the in-groups that the campaign has set as allowable in-groups. ALL_USER_GROUP will show all of the in-groups that are viewable to the members of the user group that the agent belongs to."); ?>

<BR>
<A NAME="campaigns-agent_clipboard_copy">
<BR>
<B><?php echo _QXZ("Agent Screen Clipboard Copy"); ?> -</B><?php echo _QXZ("THIS FEATURE IS CURRENTLY ONLY ENABLED FOR INTERNET EXPLORER. This feature allows you to select a field that will be copied to the computer clipboard of the agent computer upon a call being sent to an agent. Common uses for this are to allow for easy pasting of account numbers or phone numbers into legacy client applications on the agent computer.");

if ($SSqc_features_active > 0)
	{
	?>
	<BR>
	<A NAME="campaigns-qc_enabled">
	<BR>
	<B><?php echo _QXZ("QC Enabled"); ?> -</B><?php echo _QXZ("Setting this field to Y allows for the agent Quality Control features to work. Default is N."); ?>

	<BR>
	<A NAME="campaigns-qc_statuses">
	<BR>
	<B><?php echo _QXZ("QC Statuses"); ?> -</B><?php echo _QXZ("This area is where you select which statuses of leads should be gone over by the QC system. Place a check next to the status that you want QC to review. "); ?>

	<BR>
	<A NAME="campaigns-qc_shift_id">
	<BR>
	<B><?php echo _QXZ("QC Shift"); ?> -</B><?php echo _QXZ("This is the shift timeframe used to pull QC records for a campaign. The days of the week are ignored for these functions."); ?>

	<BR>
	<A NAME="campaigns-qc_get_record_launch">
	<BR>
	<B><?php echo _QXZ("QC Get Record Launch-</B> This allows one of the following actions to be triggered upon a QC agent receiving a new record."); ?>

	<BR>
	<A NAME="campaigns-qc_show_recording">
	<BR>
	<B><?php echo _QXZ("QC Show Recording"); ?> -</B><?php echo _QXZ("This allows for a recording that may be linked with the QC record to be display in the QC agent screen."); ?>

	<BR>
	<A NAME="campaigns-qc_web_form_address">
	<BR>
	<B><?php echo _QXZ("QC WebForm Address"); ?> -</B><?php echo _QXZ("This is the website address that a QC agent can go to when clicking on the WEBFORM link in the QC screen."); ?>

	<BR>
	<A NAME="campaigns-qc_script">
	<BR>
	<B><?php echo _QXZ("QC Script"); ?> -</B><?php echo _QXZ("This is the script that can be used by QC agents in the SCRIPT tab in the QC screen.");
	}
?>

<BR>
<A NAME="campaigns-vtiger_search_category">
<BR>
<B><?php echo _QXZ("Vtiger Search Category"); ?> -</B><?php echo _QXZ("If Vtiger integration is enabled in the system settings then this setting will define where the vtiger_search.php page will search for the phone number that was entered. There are 4 options that can be used in this field: LEAD- This option will search through the Vtiger leads only, ACCOUNT- This option will search through the Vtiger accounts and all contacts and sub-contacts for the phone number, VENDOR- This option will only search through the Vtiger vendors, ACCTID- This option works only for accounts and it will take the list vendor_lead_code field and try to search for the Vtiger account ID. If unsuccessful it will try any other methods listed that you have selected. Multiple options can be used for each search, but on large databases this is not recommended. Default is LEAD. UNIFIED_CONTACT- This option will use the beta Vtiger 5.1.0 feature to search by phone number and bring up a search page in Vtiger."); ?>

<BR>
<A NAME="campaigns-vtiger_search_dead">
<BR>
<B><?php echo _QXZ("Vtiger Search Dead Accounts"); ?> -</B><?php echo _QXZ("If Vtiger integration is enabled in the system settings then this setting will define whether deleted accounts will be searched when the agent clicks WEB FORM to search in the Vtiger system. DISABLED- deleted leads will not be searched, ASK- deleted leads will be searched and the vtiger search web page will ask the agent if they want to make the Vtiger account active, RESURRECT- will automatically make the deleted account active again and will take the agent to the account screen without delay upon clicking on WEB FORM. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-vtiger_create_call_record">
<BR>
<B><?php echo _QXZ("Vtiger Create Call Record"); ?> -</B><?php echo _QXZ("If Vtiger integration is enabled in the system settings then this setting will define whether a new Vtiger activity record is created for the call when the agent goes to the vtiger_search page. Default is Y. The DISPO option will create a call record for the Vtiger account without the agent needing to go to the vtiger search page through the WEB FORM."); ?>

<BR>
<A NAME="campaigns-vtiger_create_lead_record">
<BR>
<B><?php echo _QXZ("Vtiger Create Lead Record"); ?> -</B><?php echo _QXZ("If Vtiger integration is enabled in the system settings and Vtiger Search Category includes LEAD then this setting will define whether a new Vtiger lead record is created when the agent goes to the vtiger_search page and no record is found to have the call phone number. Default is Y."); ?>

<BR>
<A NAME="campaigns-vtiger_screen_login">
<BR>
<B><?php echo _QXZ("Vtiger Screen Login"); ?> -</B><?php echo _QXZ("If Vtiger integration is enabled in the system settings then this setting will define whether the user is logged into the Vtiger interface automatically when they login to the agent screen. Default is Y. The NEW_WINDOW option will open a new window upon login to the agent screen."); ?>

<BR>
<A NAME="campaigns-vtiger_status_call">
<BR>
<B><?php echo _QXZ("Vtiger Status Call"); ?> -</B><?php echo _QXZ("If Vtiger integration is enabled in the system settings then this setting will define whether the status of the Vtiger Account will be updated with the status of the call after it has been dispositioned. Default is N."); ?>

<BR>
<A NAME="campaigns-queuemetrics_callstatus">
<BR>
<B><?php echo _QXZ("QM CallStatus Override"); ?> -</B><?php echo _QXZ("If QueueMetrics integration is enabled in the system settings then this setting allow the overriding of the System Settings setting for CallStatus queue_log entries. Default is DISABLED which will use the system setting."); ?>

<BR>
<A NAME="campaigns-queuemetrics_phone_environment">
<BR>
<B><?php echo _QXZ("QM Phone Environment"); ?> -</B><?php echo _QXZ("If QueueMetrics integration is enabled in the system settings then this setting allow the insertion of this data value in the data4 field of the queue_log for agent activity records. Default is empty for disabled."); ?>

<BR>
<A NAME="campaigns-extension_appended_cidname">
<BR>
<B><?php echo _QXZ("Extension Append CID"); ?> -</B><?php echo _QXZ("If enabled, the calls placed from this campaign will have a space and the phone extension of the agent appended to the end of the CallerID name for the call before it is sent to the agent. Default is N for disabled. If USER is part of the option, then the user ID will be used instead of the phone extension. If WITH_CAMPAIGN is used, then another spae and the campaign ID will be included as well."); ?>

<BR>
<A NAME="campaigns-pllb_grouping">
<BR>
<B><?php echo _QXZ("PLLB Grouping"); ?> -</B><?php echo _QXZ("Phone Login Load Balancing Grouping, only allowed if there are multiple agent servers and phone aliases are present on the system. If set to ONE_SERVER_ONLY it will force all agents for this campaign to login to the same server. If set to CASCADING it will group logged in agents on the same server until PLLB Grouping Limit number of agents are reached, then the next agent will login to the next server with the least number of agents. If set to DISABLED the standard Phone Aliases behavior of each agent finding the server with the least number of non-remote agents logged into it will be used. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-pllb_grouping_limit">
<BR>
<B><?php echo _QXZ("PLLB Grouping Limit"); ?> -</B><?php echo _QXZ("Phone Login Load Balancing Grouping Limit. If PLLB Grouping is set to CASCADING then this setting will determine the number of agents acceptable in each server for this campaign. Default is 50."); ?>

<BR>
<A NAME="campaigns-crm_popup_login">
<BR>
<B><?php echo _QXZ("CRM Popup Login"); ?> -</B><?php echo _QXZ("If set to Y, the CRM Popup Address is used to open a new window on agent login to this campaign. Default is N."); ?>

<BR>
<A NAME="campaigns-crm_login_address">
<BR>
<B><?php echo _QXZ("CRM Popup Address"); ?> -</B><?php echo _QXZ("The web address of a CRM login page, it can have variables populated just like the web form address, with the VAR in the front and using --A--user_custom_one--B-- to define variables."); ?>

<BR>
<A NAME="campaigns-start_call_url">
<BR>
<B><?php echo _QXZ("Start Call URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is called every time a call is sent to an agent if it is populated. Uses the same variables as the web form fields and scripts. This URL can NOT be a relative path. For Manual dial calls, the Start Call URL will be sent when the call is placed. Default is blank."); ?>

<BR>
<A NAME="campaigns-dispo_call_url">
<BR>
<B><?php echo _QXZ("Dispo Call URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is called every time a call is dispositioned by an agent if it is populated. Uses the same variables as the web form fields and scripts. dispo, callback_lead_status and talk_time are the variables you can use to retrieve the agent-defined disposition for the call and the actual talk time in seconds of the call. This URL can NOT be a relative path. Default is blank.") . " " . _QXZ("If you put ALT into this field and submit this form, you will be able to go to a separate page where you can define multiple URLs for this action as well as specific statuses that will trigger them."); ?>

<BR>
<A NAME="campaigns-na_call_url">
<BR>
<B><?php echo _QXZ("No Agent Call URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but if it is populated it is called every time a call that is not handled by an agent is hung up or transferred. Uses the same variables as the web form fields and scripts. dispo can be used to retrieve the system-defined disposition for the call. This URL can NOT be a relative path. Default is blank."); ?> <?php echo _QXZ("Custom Fields are not available with this feature."); ?>

<BR>
<A NAME="campaigns-agent_allow_group_alias">
<BR>
<B><?php echo _QXZ("Group Alias Allowed"); ?> -</B><?php echo _QXZ("If you want to allow your agents to use group aliases then you need to set this to Y. Group Aliases are explained more in the Admin section, they allow agents to select different callerIDs for outbound manual calls that they may place. Default is N."); ?>

<BR>
<A NAME="campaigns-default_group_alias">
<BR>
<B><?php echo _QXZ("Default Group Alias"); ?> -</B><?php echo _QXZ("If you have allowed Group Aliases then this is the group alias that is selected first by default when the agent chooses to use a Group Alias for an outbound manual call. Default is NONE or empty."); ?>

<BR>
<A NAME="campaigns-view_calls_in_queue">
<BR>
<B><?php echo _QXZ("Agent View Calls in Queue"); ?> -</B><?php echo _QXZ("If set to anything but NONE, agents will be able to see details about the calls that are waiting in queue in their agent screen. If set to a number value, the calls displayed will be limited to the number selected. Default is NONE."); ?>

<BR>
<A NAME="campaigns-view_calls_in_queue_launch">
<BR>
<B><?php echo _QXZ("View Calls in Queue Launch"); ?> -</B><?php echo _QXZ("This setting if set to AUTO will have the Calls in Queue frame show up upon login by the agent into the agent screen. Default is MANUAL."); ?>

<BR>
<A NAME="campaigns-grab_calls_in_queue">
<BR>
<B><?php echo _QXZ("Agent Grab Calls in Queue"); ?> -</B><?php echo _QXZ("This option if set to Y will allow the agent to select the call that they want to take from the Calls in Queue display by clicking on it while paused. Agents will only be able to grab inbound calls or transferred calls, not outbound calls. Default is N."); ?>

<BR>
<A NAME="campaigns-call_requeue_button">
<BR>
<B><?php echo _QXZ("Agent Call Re-Queue Button"); ?> -</B><?php echo _QXZ("This option if set to Y will add a Re-Queue Customer button to the agent screen, allowing the agent to send the call into an AGENTDIRECT queue that is reserved for the agent only. Default is N."); ?>

<BR>
<A NAME="campaigns-pause_after_each_call">
<BR>
<B><?php echo _QXZ("Agent Pause After Each Call"); ?> -</B><?php echo _QXZ("This option if set to Y will pause the agent after every call automatically. Default is N."); ?>

<BR>
<A NAME="campaigns-pause_after_next_call">
<BR>
<B><?php echo _QXZ("Agent Pause After Next Call Link"); ?> -</B><?php echo _QXZ("This option if enabled will display a link on the agent screen that will let the agent go on pause automatically after they hang up their next call. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-blind_monitor_warning">
<BR>
<B><?php echo _QXZ("Blind Monitor Warning"); ?> -</B><?php echo _QXZ("This option if enabled will let the agent know in various optional ways if they are being blind monitored by someone. DISABLED means this feature is not active, ALERT will only pop an alert up on the agent screen, NOTICE will post a note that stays up on the agent screen as long as they are being monitored, AUDIO will play the filename defined below when an agent is starting to be monitored and the other options are combinations of the above options. Default is DISABLED."); ?>

<BR>
<A NAME="campaigns-blind_monitor_message">
<BR>
<B><?php echo _QXZ("Blind Monitor Notice"); ?> -</B><?php echo _QXZ("This is the message that will show on the agent screen while they are being monitored if the NOTICE option is selected. Default is -Someone is blind monitoring your session-."); ?>

<BR>
<A NAME="campaigns-blind_monitor_filename">
<BR>
<B><?php echo _QXZ("Blind Monitor Filename"); ?> -</B><?php echo _QXZ("This is the audio file that will play in the agents session at the start of someone blind monitoring them. This prompt will be played for everyone in the session including the customer if any is present. Default is empty."); ?>

<BR>
<A NAME="campaigns-max_inbound_calls">
<BR>
<B><?php echo _QXZ("Max Inbound Calls"); ?> -</B><?php echo _QXZ("If this setting is set to a number greater than 0, then it will be the maximum number of inbound calls that an agent can handle across all inbound groups in one day. If the agent reaches their maximum number of inbound calls, then they will not be able to select inbound groups to take calls from until the next day. This setting can be overridden by the User setting of the same name. Default is 0 for disabled."); ?>

<BR>
<A NAME="campaigns-max_inbound_calls_outcome">
<BR>
<B><?php echo _QXZ("Max Inbound Calls Outcome"); ?> -</B><?php echo _QXZ("If the Max Inbound Calls setting above is enabled, this will be the outcome of what happens to the agent when the maximum number of inbound calls is reached. DEFAULT will halt all inbound call handling to the agent, and if the agent is in an INBOUND_MAN campaign, it will only allow them to manual dial, with no pausing ability. ALLOW_AGENTDIRECT will allow the agent to continue to receive AGENTDIRECT in-group calls after they have reached the max inbound calls count. ALLOW_MI_PAUSE will allow an agent in an INBOUND_MAN campaign to continue to pause after they have reached the max inbound calls count. Default is DEFAULT."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("LISTS TABLE"); ?></FONT></B><BR><BR>
<A NAME="lists-list_id">
<BR>
<B><?php echo _QXZ("List ID"); ?> -</B><?php echo _QXZ("This is the numerical name of the list, it is not editable after initial submission, must contain only numbers and must be between 2 and 8 characters in length. Must be a number greater than 99."); ?>

<BR>
<A NAME="lists-list_name">
<BR>
<B><?php echo _QXZ("List Name"); ?> -</B><?php echo _QXZ("This is the description of the list, it must be between 2 and 20 characters in length."); ?>

<BR>
<A NAME="lists-list_description">
<BR>
<B><?php echo _QXZ("List Description"); ?> -</B><?php echo _QXZ("This is the memo field for the list, it is optional."); ?>

<BR>
<A NAME="lists-list_changedate">
<BR>
<B><?php echo _QXZ("List Change Date"); ?> -</B><?php echo _QXZ("This is the last time that the settings for this list were modified in any way."); ?>

<BR>
<A NAME="lists-list_lastcalldate">
<BR>
<B><?php echo _QXZ("List Last Call Date"); ?> -</B><?php echo _QXZ("This is the last time that lead was dialed from this list."); ?>

<BR>
<A NAME="lists-campaign_id">
<BR>
<B><?php echo _QXZ("Campaign"); ?> -</B><?php echo _QXZ("This is the campaign that this list belongs to. A list can only be dialed on a single campaign at one time."); ?>

<BR>
<A NAME="lists-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This defines whether the list is to be dialed on or not."); ?>

<BR>
<A NAME="lists-reset_list">
<BR>
<B><?php echo _QXZ("Reset Lead-Called-Status for this list"); ?> -</B><?php echo _QXZ("This resets all leads in this list to N for not called since last reset and means that any lead can now be called if it is the right status as defined in the campaign screen."); ?>

<BR>
<A NAME="lists-reset_time">
<BR>
<B><?php echo _QXZ("Reset Times"); ?> -</B><?php echo _QXZ("This field allows you to put times in, separated by a dash-, that this list will be automatically reset by the system. The times must be in 24 hour format with no punctuation, for example 0800-1700 would reset the list at 8AM and 5PM every day. Default is empty."); ?>

<BR>
<A NAME="lists-expiration_date">
<BR>
<B><?php echo _QXZ("Expiration Date"); ?> -</B><?php echo _QXZ("This option allows you to set the date after which leads in this list will not be allowed to be auto-dialed or manual-list-dialed by the system. Default is 2099-12-31."); ?>

<BR>
<A NAME="lists-local_call_time">
<BR>
<B><?php echo _QXZ("Local Call Time"); ?> -</B><?php echo _QXZ("This is a setting for this list only, where you set during which hours you would like to dial leads within this specific list, as determined by the local time in the area in which you are calling. This is controlled by the time zone, as defined by that list setting, and is adjusted for Daylight Savings time if applicable. However, state rules are based on the state field for the lead and not only the time zone that is set. This call time setting is applied after the campaign local call time has been applied to the list and will NOT override the campaign settings. This setting will only narrow down call times in relation to the campaign local call time setting. It will NOT allow calling outside the hours set by the campaign local call time setting. This is useful if you have lists that need different call times within the same campaign. For example calling business numbers between 9am to 5pm and consumer phones between 9am to 9pm within the same campaign. General Guidelines in the USA for Business to Business is 9am to 5pm and Business to Consumer calls is 9am to 9pm. Default is campaign."); ?>

<BR>
<A NAME="lists-audit_comments">
<BR>
<B><?php echo _QXZ("Audit Comments"); ?> -</B><?php echo _QXZ("This option allows comments to be moved to an audit table. No longer editable, but viewable along with the date-time-creator of each comment. Default is N. This is a part of the Quality Control Add-On."); ?>

<BR>
<A NAME="lists-agent_script_override">
<BR>
<B><?php echo _QXZ("Agent Script Override"); ?> -</B><?php echo _QXZ("If this field is set, this will be the script that the agent sees on their screen instead of the campaign script when the lead is from this list. Default is not set."); ?>

<BR>
<A NAME="lists-inbound_list_script_override">
<BR>
<B><?php echo _QXZ("Inbound Script Override"); ?> -</B><?php echo _QXZ("If this field is set, this will be the script that the agent sees on their screen instead of the campaign script when the agent receives an inbound call and the lead is from this list. Default is not set."); ?>

<BR>
<A NAME="lists-campaign_cid_override">
<BR>
<B><?php echo _QXZ("Campaign CID Override"); ?> -</B><?php echo _QXZ("If this field is set, this will override the campaign CallerID that is set for calls that are placed to leads in this list. Default is not set."); ?>

<BR>
<A NAME="lists-am_message_exten_override">
<BR>
<B><?php echo _QXZ("Answering Machine Message Override"); ?> -</B><?php echo _QXZ("If this field is set, this will override the Answering Machine Message set in the campaign for customers in this list. Default is not set."); ?>

<BR>
<A NAME="lists-drop_inbound_group_override">
<BR>
<B><?php echo _QXZ("Drop Inbound Group Override"); ?> -</B><?php echo _QXZ("If this field is set, this in-group will be used for outbound calls within this list that drop from the outbound campaign instead of the drop in-group set in the campaign detail screen. Default is not set."); ?>

<BR>
<A NAME="lists-status_group_id">
<BR>
<B><?php echo _QXZ("Status Group Override"); ?> -</B><?php echo _QXZ("If this field is set, this Status Group will be used instead of the campaign statuses for calls handled by agents from this list. This does not affect System Statuses which will always be shown. Statuses defined within this status group will not be available with Campaign HotKeys unless they are defined in Campaign Statuses. Default is not set."); ?>

<BR>
<A NAME="lists-web_form_address">
<BR>
<B><?php echo _QXZ("Web Form Override"); ?> -</B><?php echo _QXZ("This is the custom address that clicking on the WEB FORM button in the agent screen will take you to for calls that come in on this list. If you want to use custom fields in a web form address, you need to add &CF_uses_custom_fields=Y as part of your URL."); ?>

<BR>
<A NAME="lists-na_call_url">
<BR>
<B><?php echo _QXZ("No Agent Call URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but if it is populated it is called every time a call that is not handled by an agent is hung up or transferred. Uses the same variables as the web form fields and scripts. dispo can be used to retrieve the system-defined disposition for the call. This URL can NOT be a relative path. Default is blank."); ?> <?php echo _QXZ("Custom Fields are not available with this feature."); ?>

<BR>
<A NAME="lists-xferconf_a_dtmf">
<BR>
<B><?php echo _QXZ("Xfer-Conf Number Override"); ?> -</B><?php echo _QXZ("These five fields allow for you to override the Transfer Conference number presets when the lead is from this list. Default is blank."); ?>

<BR>
<A NAME="lists-user_new_lead_limit">
<BR>
<B><?php echo _QXZ("User New Lead Limit"); ?> -</B><?php echo _QXZ("This setting will limit the number of new leads any user can dial in this list per day. This feature will only work properly if the campaign is set to either the MANUAL or INBOUND_MAN Dial Method and No Hopper dialing is enabled. Default is -1 for disabled."); ?>

<BR>
<A NAME="lists-default_xfer_group">
<BR>
<B><?php echo _QXZ("Default Transfer Group"); ?> -</B><?php echo _QXZ("This field is the default In-Group that will be automatically selected when the agent opens the transfer-conference frame in their agent interface. If set to NONE, then the campaign or in-group Default Transfer Group will be used. Default is NONE."); ?>

<BR>
<A NAME="lists-inventory_report">
<BR>
<B><?php echo _QXZ("Inventory Report"); ?> -</B><?php echo _QXZ("If the Inventory Report is enabled on your system, this option will determine whether this list is included in the report or not. Default is Y for yes."); ?>

<BR>
<A NAME="lists-time_zone_setting">
<BR>
<B><?php echo _QXZ("Time Zone Setting"); ?> -</B><?php echo _QXZ("This option allows you to set the method of maintaining the current time zone lookup for the leads within this list. This process is only done at night so any changes you make will not be immediate. COUNTRY_AND_AREA_CODE is the default, and will use the country code and area code of the phone number to determine the time zone of the lead. POSTAL_CODE will use the postal code if available to determine the time zone of the lead. NANPA_PREFIX works only in the USA and will use the area code and prefix of the phone number to determine the time zone of the lead, but this is not enabled by default in the system, so please be sure you have the NANPA prefix data loaded onto your system before selecting this option. OWNER_TIME_ZONE_CODE will use the standard time zone abbreviation loaded into the owner field of the lead to determine the time zone, in the USA examples are AST, EST, CST, MST, PST, AKST, HST. This feature must be enabled by your system administrator to go into effect."); ?>

<BR>
<BR>
<A NAME="internal_list-dnc">
<BR>
<B><?php echo _QXZ("Internal DNC List"); ?> -</B><?php echo _QXZ("This Do Not Call list contains every lead that has been set to a status of DNC in the system. Through the LISTS - ADD NUMBER TO DNC page you are able to manually add numbers to this list so that they will not be called by campaigns that use the internal DNC list. There is also the option to add leads to the campaign-specific DNC lists for those campaigns that have them. If you have the active DNC option set to AREACODE then you can also use area code wildcard entries like this 201XXXXXXX to block all calls to the 201 areacode when enabled."); ?>


<BR>
<BR>
<A NAME="filter-phone-list">
<BR>
<B><?php echo _QXZ("Filter Phone Group List"); ?> -</B><?php echo _QXZ("Through the Add-Delete FPG Number page you are able to manually add numbers to this list so that they will be filtered when they enter a DID that is using this list. If you have the Filter Phone Group option set to include AREACODE then you can also use area code wildcard entries like this 201XXXXXXX to filter all calls from the 201 areacode when enabled. There is also a special entry that you can add to your Filter Phone Group, BLANK, this will filter calls that arrive with an empty caller ID number."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("Drop Lists Help"); ?></FONT></B><BR><BR>
<A NAME="drop_lists">
<BR>
<?php echo _QXZ("The Drop Lists process allows you to take the phone numbers of calls that have been dropped from inbound groups and put them into a specified list at defined scheduled days and times."); ?>
<BR>

<BR>
<A NAME="drop_lists-dl_id">
<BR>
<B><?php echo _QXZ("Drop List ID"); ?> -</B><?php echo _QXZ("This is the ID of the Drop List entry, it must be from 2 to 30 characters in length and contain no spaces."); ?>

<BR>
<A NAME="drop_lists-dl_name">
<BR>
<B><?php echo _QXZ("Drop List Name"); ?> -</B><?php echo _QXZ("This is the name of the Drop List entry, it must be from 2 to 100 characters in length and it used to describe the drop list."); ?>

<BR>
<A NAME="drop_lists-last_run">
<BR>
<B><?php echo _QXZ("Last Run Time"); ?> -</B><?php echo _QXZ("This is the date and time of the last run of this drop list process."); ?>

<BR>
<A NAME="drop_lists-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user with Modify Lists permissions to view this record."); ?>

<BR>
<A NAME="drop_lists-dl_server">
<BR>
<B><?php echo _QXZ("Run Server"); ?> -</B><?php echo _QXZ("This is the machine that you want to use to run this drop list process. You must choose a server that runs the keepalive process every minute in the crontab. The default is to use the active voicemail server as defined in System Settings."); ?>

<BR>
<A NAME="drop_lists-dl_times">
<BR>
<B><?php echo _QXZ("Run Times"); ?> -</B><?php echo _QXZ("This is the list of times in HHMM format when you want the drop list process to run. You can specify multiple times by separating them with a dash, like how the List Reset Times feature works. The drop list process will gather all of the matching records since the last time the process was run."); ?>

<BR>
<A NAME="drop_lists-dl_weekdays">
<BR>
<B><?php echo _QXZ("Run Weekdays"); ?> -</B><?php echo _QXZ("This is the list of days of the week when you want the drop list process to run. Either this option or the Month Days field below must be set for the process to run automatically."); ?>

<BR>
<A NAME="drop_lists-dl_monthdays">
<BR>
<B><?php echo _QXZ("Run Month Days"); ?> -</B><?php echo _QXZ("This is the list of days of the month in DD format when you want the drop list process to run. You can specify multiple month days by separating them with a dash. Either this option or the Weekdays field above must be set for the process to run automatically."); ?>

<BR>
<A NAME="drop_lists-duplicate_check">
<BR>
<B><?php echo _QXZ("Duplicate Check"); ?> -</B><?php echo _QXZ("This setting allows you to specify if the drop list process will check for duplicates before adding a new lead that was gathered from the drop list process. You can select LIST which will check for duplicate phone numbers within the leads already within the specified list or LIST_CAMPAIGN_LISTS which will also check for duplicate phone numbers within all of the lists associated with the campaign that the list defined below is assigned to. Default is NONE for no duplicate checking."); ?>

<BR>
<A NAME="drop_lists-list_id">
<BR>
<B><?php echo _QXZ("List ID"); ?> -</B><?php echo _QXZ("This is the list ID of the list that the new leads gathered from the drop list process are to be inserted into."); ?>

<BR>
<A NAME="drop_lists-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("The drop list process will only run on the set schedule above if active is set to Y. Default is N."); ?>

<BR>
<A NAME="drop_lists-dl_minutes">
<BR>
<B><?php echo _QXZ("Gather Minutes"); ?> -</B><?php echo _QXZ("If set to a number greater than 0, the process will look back this number of minutes from the run time to gather the drop call data. Default is 0 for disabled."); ?>

<BR>
<A NAME="drop_lists-run_now_trigger">
<BR>
<B><?php echo _QXZ("Run Now Trigger"); ?> -</B><?php echo _QXZ("This option offers a way to test this drop list process before setting it to active. It can take up to one minute for the process to start running after submitting this page with this field set to Y. Default is N."); ?>

<BR>
<A NAME="drop_lists-drop_statuses">
<BR>
<B><?php echo _QXZ("Drop Statuses"); ?> -</B><?php echo _QXZ("This is the list of drop statuses that will be used to gather the new leads by the drop list process. Default is DROP."); ?>

<BR>
<A NAME="drop_lists-closer_campaigns">
<BR>
<B><?php echo _QXZ("Inbound Groups"); ?> -</B><?php echo _QXZ("This is the list of Inbound Groups that will be used to gather the new leads by the drop list process."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("Lists Custom Fields Help"); ?></FONT></B><BR><BR>

<A NAME="lists_fields-field_label">
<BR>
<B><?php echo _QXZ("Field Label"); ?> -</B><?php echo _QXZ("This is the database field identifier for this field. This needs to be a unique identifier within the custom fields for this list. Do not use any spaces or punctuation for this field. max 50 characters, minimum of 2 characters. You can also include the default fields in a custom field setup, and you will see them in red in the list. These fields will not be added to the custom list database table, the agent interface will instead reference the list table directly. The labels that you can use to include the default fields are -  vendor_lead_code, source_id, list_id, gmt_offset_now, called_since_last_reset, phone_code, phone_number, title, first_name, middle_initial, last_name, address1, address2, address3, city, state, province, postal_code, country_code, gender, date_of_birth, alt_phone, email, security_phrase, comments, called_count, last_local_call_time, rank, owner"); ?>
<BR><BR>

<A NAME="lists_fields-field_name">
<BR>
<B><?php echo _QXZ("Field Name"); ?> -</B><?php echo _QXZ("This is the name of the field as it will appear to an agent through their interface. You can use spaces in this field, but no punctuation characters, maximum of 50 characters and minimum of 2 characters."); ?>

<BR>
<A NAME="lists_fields-field_description">
<BR>
<B><?php echo _QXZ("Field Description"); ?> -</B><?php echo _QXZ("The description of this field as it will appear in the administration interface. This is an optional field with a maximum of 100 characters."); ?>

<BR>
<A NAME="lists_fields-field_rank">
<BR>
<B><?php echo _QXZ("Field Rank"); ?> -</B><?php echo _QXZ("The order in which these fields is displayed to the agent from lowest on top to highest on the bottom."); ?>

<BR>
<A NAME="lists_fields-field_order">
<BR>
<B><?php echo _QXZ("Field Order"); ?> -</B><?php echo _QXZ("If more than one field has the same rank, they will be placed on the same line and they will be placed in order by this value from lowest to highest, left to right."); ?>

<BR>
<A NAME="lists_fields-field_help">
<BR>
<B><?php echo _QXZ("Field Help"); ?> -</B><?php echo _QXZ("Optional field, if you fill it in, the agent will be able to see this text when they click on a help link next to the field in their agent interface."); ?>

<BR>
<A NAME="lists_fields-field_type">
<BR>
<B><?php echo _QXZ("Field Type"); ?> -</B><?php echo _QXZ("This option defines the type of field that will be displayed. TEXT is a standard single-line entry form, AREA is a multi-line text box, SELECT is a single-selection pull-down menu, MULTI is a multiple-select box, RADIO is a list of radio buttons where only one option can be selected, CHECKBOX is a list of checkboxes where multiple options can be selected, DATE is a year month day calendar popup where the agent can select the date and TIME is a time selection box. The default is TEXT. For the SELECT, MULTI, RADIO and CHECKBOX options you must define the option values below in the Field Options box. DISPLAY will display only and not allow for modification by the agent. SCRIPT will also display only, but you are able to use script variables just like in the Scripts feature. SCRIPT fields will also only display the content in the Options, and not the field name like the DISPLAY type does. HIDDEN will not show the agent the field, but will allow the field to have data imported into it and exported from it, as well as have it available to the script tab and web form address. READONLY will display the value of the data in the field, but will not allow the agent to alter the data. HIDEBLOB is similar to HIDDEN except the data storage type on the database is a BLOB type, suitable for binary data or data that needs to be secured. The SWITCH field type allows the agent to switch the lead custom fields to another list, as well as reloading the FORM tab with the new set of list custom fields for the new list. To configure SWITCH type fields, you must define the button values below in the Field Options box."); ?>

<BR>
<A NAME="lists_fields-field_options">
<BR>
<B><?php echo _QXZ("Field Options"); ?> -</B><?php echo _QXZ("For the SELECT, MULTI, RADIO and CHECKBOX field types, you must define the option values in this box. You must put a list of comma separated option label and option text here with each option one its own line. The first value should have no spaces in it, and neither values should have any punctuation. For example - electric_meter, Electric Meter") . ". " . _QXZ("For the SCRIPT field types, this field is where you put your script contents. You can use single quote and amphersand characters as well so that you can create links and iframe elements. If you want to put urlencoded fields in this area, make sure you use the --U-- and --V-- flags for your variables instead of using A and B, for example --U--test_field--V--. For the SWITCH field type, you should define the list ID for the custom fields as well as the text that you want to appear in the button to activate the new form in a comma separated line, with one line for each button you want to appear. For the SWITCH field type, it is a requirement that one of the entries be the current list ID."); ?>

<BR>
<A NAME="lists_fields-multi_position">
<BR>
<B><?php echo _QXZ("Option Position"); ?> -</B><?php echo _QXZ("For CHECKBOX and RADIO field types only, if set to HORIZONTAL the options will appear on the same line possibly wrapping to the line below if there are many options. If set to VERTICAL there will be only one option per line. Default is HORIZONTAL."); ?>

<BR>
<A NAME="lists_fields-field_size">
<BR>
<B><?php echo _QXZ("Field Size"); ?> -</B><?php echo _QXZ("This setting will mean different things depending on what the field type is. For TEXT fields, the size is the number of characters that will show in the field. For AREA fields, the size is the width of the text box in characters. For MULTI fields, this setting defines the number of options to be shown in the multi select list. For SELECT, RADIO, CHECKBOX, DATE and TIME this setting is ignored."); ?>

<BR>
<A NAME="lists_fields-field_max">
<BR>
<B><?php echo _QXZ("Field Max"); ?> -</B><?php echo _QXZ("This setting will mean different things depending on what the field type is. For TEXT, HIDDEN and READONLY fields, the size is the maximum number of characters that are allowed in the field. For AREA fields, this field defines the number of rows of text visible in the text box. For MULTI, SELECT, RADIO, CHECKBOX, DATE and TIME this setting is ignored."); ?>

<BR>
<A NAME="lists_fields-field_default">
<BR>
<B><?php echo _QXZ("Field Default"); ?> -</B><?php echo _QXZ("This optional field lets you define what value to assign to a field if nothing is loaded into that field. Default is NULL which disables the default function. For DATE field types, the default is always set to today unless a number is put in in which case the date will be that many days plus or minus today. For TIME field types, the default is always set to the current server time unless a number is put in in which case the time will be that many minutes plus or minus current time. For RADIO and SELECT field types, the default must be one of the options defined for the field or NULL."); ?>

<BR>
<A NAME="lists_fields-field_cost">
<BR>
<B><?php echo _QXZ("Field Cost"); ?> -</B><?php echo _QXZ("This read only field tells you what the cost of this field is in the custom field table for this list. There is no hard limit for the number of custom fields you can have in a list, but the total of the cost of all fields for the list must be below 65000. This typically allows for hundreds of fields, but if you specify several TEXT fields that are hundreds or thousands of characters in length then you may hit this limit quickly. If you need that much text in a field you should choose an AREA type, which are stored differently and do not use as much table space."); ?>

<BR>
<A NAME="lists_fields-field_encrypt">
<BR>
<B><?php echo _QXZ("Field Encrypt"); ?> -</B><?php echo _QXZ("On the ViciHost.com platform a built-in, high-level NIST-approved encryption option is available for custom fields. Default is N."); ?>

<BR>
<A NAME="lists_fields-field_show_hide">
<BR>
<B><?php echo _QXZ("Field Show Hide"); ?> -</B><?php echo _QXZ("On the ViciHost.com platform, this option allows you to display only set characters from a READONLY or TEXT field. If a TEXT field, the value and a blank field with be displayed, if the blank field is populated by the agent, then the previous value will be overwritten when the agent completes their call. Default is N."); ?>

<BR>
<A NAME="lists_fields-field_required">
<BR>
<B><?php echo _QXZ("Field Required"); ?> -</B><?php echo _QXZ("If the campaign option allowing required fields is also enabled, this field allows you to force an agent to fill in this field before they can hang up the call. Y will affect all calls, INBOUND_ONLY will only affect calls received by the agent through an In-Group. This option will only work for the following custom field types: TEXT, AREA, DATE, SELECT, MULTI, RADIO, CHECKBOX. Default is N."); ?>

<BR>
<A NAME="lists_fields-field_duplicate">
<BR>
<B><?php echo _QXZ("Field Duplicate"); ?> -</B><?php echo _QXZ("This option will allow you to create a duplicate of a TEXT type custom field that already exists in a different location within your custom list fields form. This option only works with TEXT type fields. This option is only available when you create a new custom field entry. When an agent modifies the text in one of these duplicate fields and clicks to another field, the system will copy the value that they place in that duplicate field to the original field. Default is N."); ?>

<BR>
<A NAME="lists_fields-name_position">
<BR>
<B><?php echo _QXZ("Field Name Position"); ?> -</B><?php echo _QXZ("If set to LEFT, this field name will appear to the left of the field, if set to TOP the field name will take up the entire line and appear above the field. Default is LEFT."); ?>

<BR>
<A NAME="lists_fields-copy_option">
<BR>
<B><?php echo _QXZ("Copy Option"); ?> -</B><?php echo _QXZ("When copying field definitions from one list to another, you have a few options for how the copying process works. APPEND will add the fields that are not present in the destination list, if there are matching field labels those will remained untouched, no custom field data will be deleted or modified using this option. UPDATE will update the common field_label fields in the destination list to the field definitions from the source list. custom field data may be modified or lost using this option. REPLACE will remove all existing custom fields in the destination list and replace them with the custom fields from the source list, all custom field data will be deleted using this option."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>INBOUND_GROUPS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="inbound_groups-group_id">
<BR>
<B><?php echo _QXZ("Group ID"); ?> -</B><?php echo _QXZ("This is the short name of the inbound group, it is not editable after initial submission, must not contain any spaces and must be between 2 and 20 characters in length."); ?>

<BR>
<A NAME="inbound_groups-group_name">
<BR>
<B><?php echo _QXZ("Group Name"); ?> -</B><?php echo _QXZ("This is the description of the group, it must be between 2 and 30 characters in length. Cannot include dashes, pluses or spaces ."); ?>

<BR>
<A NAME="inbound_groups-group_color">
<BR>
<B><?php echo _QXZ("Group Color"); ?> -</B><?php echo _QXZ("This is the color that displays in the agent client app when a call comes in on this group. It must be between 2 and 7 characters long. If this is a hex color definition you must remember to put a # at the beginning of the string or the agent screen will not work properly."); ?>

<BR>
<A NAME="inbound_groups-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This determines whether this inbound group is available to take calls. If this is set to inactive then the After Hours Action will be used on any calls coming into it."); ?>

<BR>
<A NAME="inbound_groups-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this inbound group, this allows admin viewing of this in-group restricted by user group. Default is --ALL-- which allows any admin user to view this in-group."); ?>

<BR>
<A NAME="inbound_groups-park_ext">
<BR>
<B><?php echo _QXZ("Park Music-on-Hold"); ?> -</B><?php echo _QXZ("This optional setting will override the agent campaign setting for Park Music-on-Hold, if populated. Default is empty for disabled."); ?>

<BR>
<A NAME="inbound_groups-callback_queue_calls">
<BR>
<B><?php echo _QXZ("Callback Queue Calls"); ?> -</B><?php echo _QXZ("This will only show up if there are LIVE inbound callback queue calls waiting to be called back by agents when their turn arrives."); ?>

<BR>
<A NAME="inbound_groups-group_calldate">
<BR>
<B><?php echo _QXZ("In-Group Calldate"); ?> -</B><?php echo _QXZ("This is the last date and time that a call was directed to this inbound group."); ?>

<BR>
<A NAME="inbound_groups-group_emaildate">
<BR>
<B><?php echo _QXZ("In-Group Email Date"); ?> -</B><?php echo _QXZ("This is the last date and time that an email was directed to this inbound group."); ?>

<BR>
<A NAME="inbound_groups-group_chatdate">
<BR>
<B><?php echo _QXZ("In-Group Chat Date"); ?> -</B><?php echo _QXZ("This is the last date and time that a chat was directed to this inbound group."); ?>

<BR>
<A NAME="inbound_groups-web_form_address">
<BR>
<B><?php echo _QXZ("Web Form"); ?> -</B><?php echo _QXZ("This is the custom address that clicking on the WEB FORM button in the agent screen will take you to for calls that come in on this group. If you want to use custom fields in a web form address, you need to add &CF_uses_custom_fields=Y as part of your URL."); ?>

<BR>
<A NAME="inbound_groups-next_agent_call">
<BR>
<B><?php echo _QXZ("Next Agent Call"); ?> -</B><?php echo _QXZ("This determines which agent receives the next call that is available:"); ?>
 <BR> &nbsp; - <?php echo _QXZ("random: orders by the random update value in the live_agents table"); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_call_start: orders by the last time an agent was sent a call. Results in agents receiving about the same number of calls overall."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_call_finish: orders by the last time an agent finished a call. AKA agent waiting longest receives first call."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_inbound_call_start: orders by the last time an agent was sent an inbound call. Results in agents receiving about the same number of calls overall."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_inbound_call_finish: orders by the last time an agent finished an inbound call. AKA agent waiting longest receives first call."); ?>
 <BR> &nbsp; - <?php echo _QXZ("overall_user_level: orders by the user_level of the agent as defined in the users table a higher user_level will receive more calls."); ?>
 <BR> &nbsp; - <?php echo _QXZ("inbound_group_rank: orders by the rank given to the agent for the specific inbound group. Highest to Lowest."); ?>
 <BR> &nbsp; - <?php echo _QXZ("campaign_rank: orders by the rank given to the agent for the campaign. Highest to Lowest."); ?>
 <BR> &nbsp; - <?php echo _QXZ("ingroup_grade_random: gives a higher probability of getting a call to the higher graded agents by in-group."); ?>
 <BR> &nbsp; - <?php echo _QXZ("campaign_grade_random: gives a higher probability of getting a call to the higher graded agents by campaign."); ?>
 <BR> &nbsp; - <?php echo _QXZ("fewest_calls: orders by the number of calls received by an agent for that specific inbound group. Least calls first."); ?>
 <BR> &nbsp; - <?php echo _QXZ("fewest_calls_campaign: orders by the number of calls received by an agent for the campaign. Least calls first."); ?>
 <BR> &nbsp; - <?php echo _QXZ("longest_wait_time: orders by the amount of time agent has been actively waiting for a call."); ?>
 <BR> &nbsp; - <?php echo _QXZ("ring_all: rings all available agents until one picks up the phone."); ?>
 <BR> <?php echo _QXZ("NOTES: For ring_all, the agents that are using phones that have On Hook Agent enabled will have their phones ring and the first one to answer will receive the call and the information on the agent screen. Since ring_all ignores agent wait time and ranking and will call every agent that is available for the queue, we do not recommend using this method for large queues. When using ring_all, agents logged with phones that have the On Hook Agent disabled will have to use the Calls In Queue panel and click on the TAKE CALL link to take calls in queue. The amount of time the agents phone will ring for ring_all is set to the On-Hook Ring Time setting or the shortest ring time of the phones that will be called. We do not recommend using ring_all on high call volume queues, or queues with many agents. The ring_all method is intended to be used with only a few agents and on low call volume in-groups."); ?>
<BR>

<BR>
<A NAME="inbound_groups-next_agent_email">
<BR>
<B><?php echo _QXZ("Next Agent Email"); ?> -</B><?php echo _QXZ("This determines which agent receives the next email that is available:"); ?>
 <BR> &nbsp; - <?php echo _QXZ("random: orders by the random update value in the live_agents table"); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_call_start: orders by the last time an agent was sent a call/email. Results in agents receiving about the same number of calls/emails overall."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_call_finish: orders by the last time an agent finished a call/email. AKA agent waiting longest receives first call."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_inbound_call_start: orders by the last time an agent was sent an inbound call. Results in agents receiving about the same number of calls/emails overall."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_inbound_call_finish: orders by the last time an agent finished an inbound call. AKA agent waiting longest receives first call."); ?>
 <BR> &nbsp; - <?php echo _QXZ("overall_user_level: orders by the user_level of the agent as defined in the users table a higher user_level will receive more calls/emails."); ?>
 <BR> &nbsp; - <?php echo _QXZ("inbound_group_rank: orders by the rank given to the agent for the specific inbound group. Highest to Lowest."); ?>
 <BR> &nbsp; - <?php echo _QXZ("campaign_rank: orders by the rank given to the agent for the campaign. Highest to Lowest."); ?>
 <BR> &nbsp; - <?php echo _QXZ("ingroup_grade_random: gives a higher probability of getting a call/email to the higher graded agents by in-group."); ?>
 <BR> &nbsp; - <?php echo _QXZ("campaign_grade_random: gives a higher probability of getting a call/email to the higher graded agents by campaign."); ?>
 <BR> &nbsp; - <?php echo _QXZ("fewest_calls: orders by the number of calls/emails received by an agent for that specific inbound group. Least calls/emails first."); ?>
 <BR> &nbsp; - <?php echo _QXZ("fewest_calls_campaign: orders by the number of calls/emails received by an agent for the campaign. Least calls/emails first."); ?>
 <BR> &nbsp; - <?php echo _QXZ("longest_wait_time: orders by the amount of time agent has been actively waiting for a call/email."); ?>
 <BR> &nbsp; - <?php echo _QXZ("ring_all: rings all available agents until one picks up the phone."); ?>
 <BR> <?php echo _QXZ("NOTES: For ring_all, the agents that are using phones that have On Hook Agent enabled will have their phones ring and the first one to answer will receive the call and the information on the agent screen. Since ring_all ignores agent wait time and ranking and will call every agent that is available for the queue, we do not recommend using this method for large queues. When using ring_all, agents logged with phones that have the On Hook Agent disabled will have to use the Calls In Queue panel and click on the TAKE CALL link to take calls in queue. The amount of time the agents phone will ring for ring_all is set to the On-Hook Ring Time setting or the shortest ring time of the phones that will be called. We do not recommend using ring_all on high call volume queues, or queues with many agents. The ring_all method is intended to be used with only a few agents and on low call volume in-groups."); ?>
<BR>

<BR>
<A NAME="inbound_groups-next_agent_chat">
<BR>
<B><?php echo _QXZ("Next Agent Chat"); ?> -</B><?php echo _QXZ("This determines which agent receives the next chat that is available:"); ?>
 <BR> &nbsp; - <?php echo _QXZ("random: orders by the random update value in the live_agents table"); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_call_start: orders by the last time an agent was sent a call/chat. Results in agents receiving about the same number of calls/chats overall."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_call_finish: orders by the last time an agent finished a call/chat. AKA agent waiting longest receives first call."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_inbound_call_start: orders by the last time an agent was sent an inbound call. Results in agents receiving about the same number of calls/chats overall."); ?>
 <BR> &nbsp; - <?php echo _QXZ("oldest_inbound_call_finish: orders by the last time an agent finished an inbound call. AKA agent waiting longest receives first call."); ?>
 <BR> &nbsp; - <?php echo _QXZ("overall_user_level: orders by the user_level of the agent as defined in the users table a higher user_level will receive more calls/chats."); ?>
 <BR> &nbsp; - <?php echo _QXZ("inbound_group_rank: orders by the rank given to the agent for the specific inbound group. Highest to Lowest."); ?>
 <BR> &nbsp; - <?php echo _QXZ("campaign_rank: orders by the rank given to the agent for the campaign. Highest to Lowest."); ?>
 <BR> &nbsp; - <?php echo _QXZ("ingroup_grade_random: gives a higher probability of getting a call/chat to the higher graded agents by in-group."); ?>
 <BR> &nbsp; - <?php echo _QXZ("campaign_grade_random: gives a higher probability of getting a call/chat to the higher graded agents by campaign."); ?>
 <BR> &nbsp; - <?php echo _QXZ("fewest_calls: orders by the number of calls/chats received by an agent for that specific inbound group. Least calls/chats first."); ?>
 <BR> &nbsp; - <?php echo _QXZ("fewest_calls_campaign: orders by the number of calls/chats received by an agent for the campaign. Least calls/chats first."); ?>
 <BR> &nbsp; - <?php echo _QXZ("longest_wait_time: orders by the amount of time agent has been actively waiting for a call/chat."); ?>
 <BR> &nbsp; - <?php echo _QXZ("ring_all: rings all available agents until one picks up the phone."); ?>
 <BR> <?php echo _QXZ("NOTES: For ring_all, the agents that are using phones that have On Hook Agent enabled will have their phones ring and the first one to answer will receive the call and the information on the agent screen. Since ring_all ignores agent wait time and ranking and will call every agent that is available for the queue, we do not recommend using this method for large queues. When using ring_all, agents logged with phones that have the On Hook Agent disabled will have to use the Calls In Queue panel and click on the TAKE CALL link to take calls in queue. The amount of time the agents phone will ring for ring_all is set to the On-Hook Ring Time setting or the shortest ring time of the phones that will be called. We do not recommend using ring_all on high call volume queues, or queues with many agents. The ring_all method is intended to be used with only a few agents and on low call volume in-groups."); ?>
<BR>

<BR>
<A NAME="inbound_groups-on_hook_ring_time">
<BR>
<B><?php echo _QXZ("On-Hook Ring Time"); ?> -</B><?php echo _QXZ("This option is only used for agents that are logged in with phones that have the agent-on-hook feature enabled. This is the number of seconds that each call attempt to the agent will ring for until the system will wait one second and start ringing the available agent phones again. This field can be overridden if the agent phones are set to a lower ring time which may be necessary to prevent calls from being sent one phones voicemail. Default is 15."); ?>

<BR>
<A NAME="inbound_groups-on_hook_cid">
<BR>
<B><?php echo _QXZ("On-Hook CID"); ?> -</B><?php echo _QXZ("This option is only used for agents that are logged in with phones that have the agent-on-hook feature enabled. This is the caller ID that will show up on their agent phones when the calls are ringing. GENERIC is a generic RINGAGENT00000000001 type of notification. INGROUP will show only the in-group the call came from. CUSTOMER_PHONE will show only the customer phone number. CUSTOMER_PHONE_RINGAGENT will show RINGAGENT_3125551212 with the RINGAGENT as part of the CID with the customer phone number. CUSTOMER_PHONE_INGROUP will show the first 10 characters of the in-group followed by the customer phone number. Default is GENERIC."); ?>

<BR>
<A NAME="inbound_groups-on_hook_cid_number">
<BR>
<B><?php echo _QXZ("On-Hook CID Number"); ?> -</B><?php echo _QXZ("This option allows you to set a CID Number to be sent out with the On-Hook CID. If you put a Y, YES or CUSTOMER in this field, then the customer CID number will be sent to the ringing agent phone. If you put a different phone number, that number will be sent. Default is blank for disabled."); ?>

<BR>
<A NAME="inbound_groups-queue_priority">
<BR>
<B><?php echo _QXZ("Queue Priority"); ?> -</B><?php echo _QXZ("This setting is used to define the order in which the calls from this inbound group should be answered in relation to calls from other inbound groups."); ?>

<BR>
<A NAME="inbound_groups-fronter_display">
<BR>
<B><?php echo _QXZ("Fronter Display"); ?> -</B><?php echo _QXZ("This field determines whether the inbound agent would have the fronter name - if there is one - displayed in the Status field when the call comes to the agent."); ?>

<BR>
<A NAME="inbound_groups-ingroup_script">
<BR>
<B><?php echo _QXZ("Campaign Script"); ?> -</B><?php echo _QXZ("This menu allows you to choose the script that will appear on the agents screen for this campaign. Select NONE to show no script for this campaign."); ?>

<BR>
<A NAME="inbound_groups-ignore_list_script_override">
<BR>
<B><?php echo _QXZ("Ignore List Script Override-</B> This option allows you to ignore the list ID Script Override option for calls coming into this In-Group. Setting this to Y will ignore any List ID script settings. Default is N."); ?>

<BR>
<A NAME="inbound_groups-status_group_id">
<BR>
<B><?php echo _QXZ("Status Group Override"); ?> -</B><?php echo _QXZ("If this field is set, this Status Group will be used instead of the campaign statuses for calls handled by agents from this inbound group. This does not affect System Statuses which will always be shown. Statuses defined within this status group will not be available with Campaign HotKeys unless they are defined in Campaign Statuses. Default is not set."); ?>

<BR>
<A NAME="inbound_groups-get_call_launch">
<BR>
<B><?php echo _QXZ("Get Call Launch"); ?> -</B><?php echo _QXZ("This menu allows you to choose whether you want to auto-launch the web-form page in a separate window, auto-switch to the SCRIPT, EMAIL, or CHAT tab ,emails and chats must be allowed to have those options available, or do nothing when a call is sent to the agent for this campaign. If custom list fields are enabled on your system, FORM will open the FORM tab upon connection of a call to an agent."); ?>

<BR>
<A NAME="inbound_groups-group_handling">
<BR>
<B><?php echo _QXZ("Group Handling"); ?> -</B><?php echo _QXZ("This menu allows you to choose what type of inbound activity this group should handle. PHONE means this in-group is for handling phone calls and will show under the In-Group section. EMAIL is for handling incoming emails and will cause the group to be listed under the Email Group section. CHAT is for handling customer chats and will cause the group to be listed under the Email Group section"); ?>

<BR>
<A NAME="inbound_groups-xferconf_a_dtmf">
<BR>
<B><?php echo _QXZ("Xfer-Conf DTMF"); ?> -</B><?php echo _QXZ("These four fields allow for you to have two sets of Transfer Conference and DTMF presets. When the call or campaign is loaded, the agent screen will show two buttons on the transfer-conference frame and auto-populate the number-to-dial and the send-dtmf fields when pressed. If you want to allow Consultative Transfers, a fronter to a closer, have the agent use the CONSULTATIVE checkbox, which does not work for third party agent screen consultative calls. For those just have the agent click the Dial With Customer button. Then the agent can just LEAVE-3WAY-CALL and move on to their next call. If you want to allow Blind transfers of customers to an AGI script for logging or an IVR, then place AXFER in the number-to-dial field. You can also specify a custom extension after the AXFER, for instance if you want to do a call to a special IVR you have set to extension 83900 you would put AXFER83900 in the number-to-dial field."); ?>

<BR>
<A NAME="inbound_groups-timer_action">
<BR>
<B><?php echo _QXZ("Timer Action"); ?> -</B><?php echo _QXZ("This feature allows you to trigger actions after a certain amount of time. the D1 and D2 DIAL options will launch a call to the Transfer Conference Number presets and send them to the agent session, this is usually used for simple IVR validation AGI applications or just to play a pre-recorded message. WEBFORM will open the web form address. MESSAGE_ONLY will simply display the message that is in the field below. NONE will disable this feature and is the default. This setting will override the Campaign settings. HANGUP will hang up the call when the timer is triggered, CALLMENU will send the call to the Call Menu specified in the Timer Action Destination field, EXTENSION will send the call to the Extension that is specified in the Timer Action Destination field, IN_GROUP will send the call to the In-Group specified in the Timer Action Destination field."); ?>

<BR>
<A NAME="inbound_groups-timer_action_message">
<BR>
<B><?php echo _QXZ("Timer Action Message"); ?> -</B><?php echo _QXZ("This is the message that appears on the agent screen at the time the Timer Action is triggered."); ?>

<BR>
<A NAME="inbound_groups-timer_action_seconds">
<BR>
<B><?php echo _QXZ("Timer Action Seconds"); ?> -</B><?php echo _QXZ("This is the amount of time after the call is connected to the customer that the Timer Action is triggered. Default is -1 which is also inactive."); ?>

<BR>
<A NAME="inbound_groups-timer_action_destination">
<BR>
<B><?php echo _QXZ("Timer Action Destination"); ?> -</B><?php echo _QXZ("This field is where you specify the Call Menu, Extension or In-Group that you want the call sent to if the Time Action is set to CALLMENU, EXTENSION or IN_GROUP. Default is empty."); ?>

<BR>
<A NAME="inbound_groups-drop_call_seconds">
<BR>
<B><?php echo _QXZ("Drop Call Seconds"); ?> -</B><?php echo _QXZ("The number of seconds a call will stay in queue before being considered a DROP."); ?>

<BR>
<A NAME="inbound_groups-drop_action">
<BR>
<B><?php echo _QXZ("Drop Action"); ?> -</B><?php echo _QXZ("This menu allows you to choose what happens to a call when it has been waiting for longer than what is set in the Drop Call Seconds field. HANGUP will simply hang up the call, MESSAGE will send the call the Drop Exten that you have defined below, VOICEMAIL will send the call to the voicemail box that you have defined below and IN_GROUP will send the call to the Inbound Group that is defined below. VMAIL_NO_INST will send the call to the voicemail box that you have defined below and will not play instructions after the voicemail message."); ?>

<BR>
<A NAME="inbound_groups-drop_lead_reset">
<BR>
<B><?php echo _QXZ("Drop Lead Reset"); ?> -</B><?php echo _QXZ("This option if set to Y, will set the lead called-since-last-reset field to N when the call is dropped and sent to an action like Message, Voicemail or Hangup. Default is N for disabled."); ?>

<BR>
<A NAME="inbound_groups-drop_exten">
<BR>
<B><?php echo _QXZ("Drop Exten"); ?> -</B><?php echo _QXZ("If Drop Action is set to MESSAGE, this is the dial plan extension that the call will be sent to if it reaches Drop Call Seconds. For AGENTDIRECT in-groups, if the user is unavailable, you can put AGENTEXT in this field and the system will look up the user custom five field and send the call to that dialplan number."); ?>

<BR>
<A NAME="inbound_groups-voicemail_ext">
<BR>
<B><?php echo _QXZ("Voicemail"); ?> -</B><?php echo _QXZ("If Drop Action is set to VOICEMAIL, the call DROP would instead be directed to this voicemail box to hear and leave a message. In an AGENTDIRECT in-group, setting this to AGENTVMAIL will select the User voicemail ID to use."); ?>

<BR>
<A NAME="inbound_groups-drop_inbound_group">
<BR>
<B><?php echo _QXZ("Drop Transfer Group"); ?> -</B><?php echo _QXZ("If Drop Action is set to IN_GROUP, the call will be sent to this inbound group if it reaches Drop Call Seconds."); ?>

<BR>
<A NAME="inbound_groups-drop_callmenu">
<BR>
<B><?php echo _QXZ("Drop Call Menu"); ?> -</B><?php echo _QXZ("If Drop Action is set to CALLMENU, the call will be sent to this call menu if it reaches Drop Call Seconds."); ?>

<BR>
<A NAME="inbound_groups-action_xfer_cid">
<BR>
<B><?php echo _QXZ("Action Transfer CID"); ?> -</B><?php echo _QXZ("Used for Drop, After-hours and No-agent-no-queue actions. This is the caller ID number that the call uses before it is transferred to extensions, messages, voicemail or call menus. You can use CUSTOMER in this field to use the customer phone number, or CAMPAIGN to use the first allowed campaign caller id number. Default is CUSTOMER. If this is a call that will go to a Call Menu and then back to an in-group, we suggest you use CUSTOMERCLOSER in this field, and also you need to set the In-Group Handle Method in the Call Menu to CLOSER."); ?>

<BR>
<A NAME="inbound_groups-call_time_id">
<BR>
<B><?php echo _QXZ("Call Time"); ?> -</B><?php echo _QXZ("This is the call time scheme to use for this inbound group. Keep in mind that the time is based on the server time. Default is 24hours."); ?>

<BR>
<A NAME="inbound_groups-after_hours_action">
<BR>
<B><?php echo _QXZ("After Hours Action"); ?> -</B><?php echo _QXZ("The action to perform if it is after hours as defined in the call time for this inbound group. HANGUP will immediately hangup the call, MESSAGE will play the file in the After Hours Message Filename field, EXTENSION will send the call to the After Hours Extension in the dialplan and VOICEMAIL will send the call to the voicemail box listed in the After Hours Voicemail field, IN_GROUP will send the call to the inbound group selected in the After Hours Transfer Group select list. Default is MESSAGE. VMAIL_NO_INST will send the call to the voicemail box that you have defined below and will not play instructions after the voicemail message."); ?>

<BR>
<A NAME="inbound_groups-after_hours_lead_reset">
<BR>
<B><?php echo _QXZ("After Hours Lead Reset"); ?> -</B><?php echo _QXZ("This option if set to Y, will set the lead called-since-last-reset field to N when the call is after hours and sent to an action like Message, Voicemail or Hangup. Default is N for disabled."); ?>

<BR>
<A NAME="inbound_groups-after_hours_message_filename">
<BR>
<B><?php echo _QXZ("After Hours Message Filename"); ?> -</B><?php echo _QXZ("The audio file located on the server to be played if the Action is set to MESSAGE. Default is vm-goodbye"); ?>

<BR>
<A NAME="inbound_groups-after_hours_exten">
<BR>
<B><?php echo _QXZ("After Hours Extension"); ?> -</B><?php echo _QXZ("The dialplan extension to send the call to if the Action is set to EXTENSION. Default is 8300. For AGENTDIRECT in-groups, you can put AGENTEXT in this field and the system will look up the user custom five field and send the call to that dialplan number."); ?>

<BR>
<A NAME="inbound_groups-after_hours_voicemail">
<BR>
<B><?php echo _QXZ("After Hours Voicemail"); ?> -</B><?php echo _QXZ("The voicemail box to send the call to if the Action is set to VOICEMAIL. In an AGENTDIRECT in-group, setting this to AGENTVMAIL will select the User voicemail ID to use."); ?>

<BR>
<A NAME="inbound_groups-afterhours_xfer_group">
<BR>
<B><?php echo _QXZ("After Hours Transfer Group"); ?> -</B><?php echo _QXZ("If After Hours Action is set to IN_GROUP, the call will be sent to this inbound group if it enters the in-group outside of the call time scheme defined for the in-group."); ?>

<BR>
<A NAME="inbound_groups-after_hours_callmenu">
<BR>
<B><?php echo _QXZ("After Hours Call Menu"); ?> -</B><?php echo _QXZ("If After Hours Action is set to CALLMENU, the call will be sent to this Call Menu if it enters the in-group outside of the call time scheme defined for the in-group."); ?>

<BR>
<A NAME="inbound_groups-no_agent_no_queue">
<BR>
<B><?php echo _QXZ("No Agents No Queueing"); ?> -</B><?php echo _QXZ("If this field is set to Y, NO_READY or NO_PAUSED then no calls will be put into the queue for this in-group if there are no agents logged in and the calls will go to the No Agent No Queue Action. The NO_PAUSED option will also not send the callers into the queue if there are only paused agents in the in-group. The NO_READY option will also not send the callers into the queue if there are no agents ready to take the call in the in-group. Default is N. In an AGENTDIRECT in-group, setting this to AGENTVMAIL will select the User voicemail ID to use. You can also put AGENTEXT in this field if it is set to EXTENSION and the system will look up the user custom five field and send the call to that dialplan number. If set to N, the calls will queue up, even if there are no agents logged in and set to take calls from this in-group."); ?>

<BR>
<A NAME="inbound_groups-no_agent_action">
<BR>
<B><?php echo _QXZ("No Agent No Queue Action"); ?> -</B><?php echo _QXZ("If No Agent No Queue is enabled, then this field defines where the call will go if there are no agents in the In-Group. Default is MESSAGE, this plays the sound files in the Action Value field and then hangs up."); ?>

<BR>
<A NAME="inbound_groups-nanq_lead_reset">
<BR>
<B><?php echo _QXZ("No Agent No Queue Lead Reset"); ?> -</B><?php echo _QXZ("This option if set to Y, will set the lead called-since-last-reset field to N when No agent no queue is triggered and the call is sent to an action like Message, Voicemail or Hangup. Default is N for disabled."); ?>

<BR>
<A NAME="inbound_groups-no_agent_action_value">
<BR>
<B><?php echo _QXZ("No Agent No Queue Action Value"); ?> -</B><?php echo _QXZ("This is the value for the Action above. Default is"); ?>: nbdy-avail-to-take-call|vm-goodbye.

<BR>
<A NAME="inbound_groups-max_calls_method">
<BR>
<B><?php echo _QXZ("Max Calls Method"); ?> -</B><?php echo _QXZ("This option can enable the maximum concurrent calls feature for this in-group. If set to TOTAL, then the total number of calls being handled by agents and in queue in this in-group will not be allowed to exceed the Max Calls Count number of lines as defined below. If set to IN_QUEUE, then if the number of calls in queue waiting for agents will not be allowed to exceed the Max Calls Count no matter how many calls are with agents for this in-group. Default is DISABLED."); ?>

<BR>
<A NAME="inbound_groups-max_calls_count">
<BR>
<B><?php echo _QXZ("Max Calls Count"); ?> -</B><?php echo _QXZ("This option must be set higher than 0 if you want to use the Max Calls Method feature. Default is 0."); ?>

<BR>
<A NAME="inbound_groups-max_calls_action">
<BR>
<B><?php echo _QXZ("Max Calls Action"); ?> -</B><?php echo _QXZ("This is the action to be taken if the Max Calls Method is enabled and the number of calls exceeds what is set above in the Max Calls Count setting. The calls above that amount will be sent to either the DROP action, the AFTERHOURS action or the NO_AGENT_NO_QUEUE action and will be logged as a MAXCAL status with a MAXCALLS hangup reason. Default is NO_AGENT_NO_QUEUE."); ?>

<BR>
<A NAME="inbound_groups-areacode_filter">
<BR>
<B><?php echo _QXZ("Areacode Filter"); ?> -</B><?php echo _QXZ("This feature allows you to filter calls that have been waiting in queue by the areacode of the customer phone number. The areacodes are defined on a per In-Group basis using the Areacode List modification page that you can get to by clicking on the areacode filter list link to the right. The ALLOW_ONLY option will only allow those customer phone numbers that begin with the areacodes included in the areacode filter list to continue waiting in the queue. The DROP_ONLY option will only drop those customer phone numbers that begin with the areacodes included in the areacode filter list. Areacodes in the filter list can be from 1 to 6 digits in length. Default is DISABLED."); ?>

<BR>
<A NAME="inbound_groups-areacode_filter_seconds">
<BR>
<B><?php echo _QXZ("Areacode Filter Seconds"); ?> -</B><?php echo _QXZ("If the Areacode Filter feature above is enabled, then this field is where you set the number of seconds waiting in the queue that the feature is excuted. Default is 10 seconds."); ?>

<BR>
<A NAME="inbound_groups-areacode_filter_action">
<BR>
<B><?php echo _QXZ("Areacode Filter Action"); ?> -</B><?php echo _QXZ("If the Areacode Filter feature above is enabled, this is the action taken on the phone call as it is dropped out of this in-group. Default is MESSAGE."); ?>

<BR>
<A NAME="inbound_groups-welcome_message_filename">
<BR>
<B><?php echo _QXZ("Welcome Message Filename"); ?> -</B><?php echo _QXZ("The audio file located on the server to be played when the call comes in. If set to ---NONE--- then no message will be played. Default is ---NONE---. This field as with the other audio fields in In-Groups, with the exception of the Agent Alert Filename, can have multiple audio files played if you put a pipe-separated list of audio files into the field."); ?>

<BR>
<A NAME="inbound_groups-play_welcome_message">
<BR>
<B><?php echo _QXZ("Play Welcome Message"); ?> -</B><?php echo _QXZ("These settings select when to play the defined welcome message, ALWAYS will play it every time, NEVER will never play it, IF_WAIT_ONLY will only play the welcome message if the call does not immediately go to an agent, and YES_UNLESS_NODELAY will always play the welcome message unless the NO_DELAY setting is enabled. Default is ALWAYS."); ?>

<BR>
<A NAME="inbound_groups-moh_context">
<BR>
<B><?php echo _QXZ("Music On Hold Context"); ?> -</B><?php echo _QXZ("The music on hold context to use when the customer is placed on hold. Default is default."); ?>

<BR>
<A NAME="inbound_groups-onhold_prompt_filename">
<BR>
<B><?php echo _QXZ("On Hold Prompt Filename"); ?> -</B><?php echo _QXZ("The audio file located on the server to be played at a regular interval when the customer is on hold. Default is generic_hold. This audio file MUST be 9 seconds or less in length. If this prompt is too long, it can cause calls to not be routed properly. We usually recommend not using this feature, and instead putting a periodic audio message within the Music on Hold plan that you have defined for this in-group."); ?>

<BR>
<A NAME="inbound_groups-prompt_interval">
<BR>
<B><?php echo _QXZ("On Hold Prompt Interval"); ?> -</B><?php echo _QXZ("The length of time in seconds to wait before playing the on hold prompt. Default is 60. To disable the On Hold Prompt, set the interval to 0."); ?>

<BR>
<A NAME="inbound_groups-onhold_prompt_no_block">
<BR>
<B><?php echo _QXZ("On Hold Prompt No Block"); ?> -</B><?php echo _QXZ("Setting this option to Y will allow calls in line behind a call where the on hold prompt is playing to go to an agent if one becomes available while the message is playing. While the On Hold Prompt Filename message is playing to a customer they cannot be sent to an agent. Default is N."); ?>

<BR>
<A NAME="inbound_groups-onhold_prompt_seconds">
<BR>
<B><?php echo _QXZ("On Hold Prompt Seconds"); ?> -</B><?php echo _QXZ("This field needs to be set to the number of seconds that the On Hold Prompt Filename plays for. Default is 9."); ?>

<BR>
<A NAME="inbound_groups-play_place_in_line">
<BR>
<B><?php echo _QXZ("Play Place in Line"); ?> -</B><?php echo _QXZ("This defines whether the caller will hear their place in line when they enter the queue as well as when they hear the announcement. Default is N."); ?>

<BR>
<A NAME="inbound_groups-play_estimate_hold_time">
<BR>
<B><?php echo _QXZ("Play Estimated Hold Time"); ?> -</B><?php echo _QXZ("This defines whether the caller will hear the estimated hold time before they are transferred to an agent. Default is N. If the customer is on hold and hears this estimated hold time message, the minimum time that will be played is 15 seconds."); ?>

<BR>
<A NAME="inbound_groups-calculate_estimated_hold_seconds">
<BR>
<B><?php echo _QXZ("Calculate Estimated Hold Seconds"); ?> -</B><?php echo _QXZ("This defines the number of seconds into the queue that the customer will wait before the Estimated Hold Time will be calculated and optionally played. Minimum is 3 seconds, even if set lower than 3. Default is 0."); ?>

<BR>
<A NAME="inbound_groups-eht_minimum_prompt_filename">
<BR>
<B><?php echo _QXZ("Estimated Hold Time Minimum Filename"); ?> -</B><?php echo _QXZ("If the Estimated Hold Time is active and it is calculated to be at or below the minimum of 15 seconds, then this prompt file will be played instead of the default announcement. Default is Empty for inactive."); ?>

<BR>
<A NAME="inbound_groups-eht_minimum_prompt_no_block">
<BR>
<B><?php echo _QXZ("Estimated Hold Time Minimum Prompt No Block"); ?> -</B><?php echo _QXZ("If Estimated Hold Time is active and the Estimated Hold Time Minimum Filename field above is filled-in, then this option to allow calls in line behind a call where the prompt is playing to go to an agent if one becomes available while the message is playing. While the prompt is playing to a customer they cannot be sent to an agent. Default is N."); ?>

<BR>
<A NAME="inbound_groups-eht_minimum_prompt_seconds">
<BR>
<B><?php echo _QXZ("Estimated Hold Time Minimum Prompt Seconds"); ?> -</B><?php echo _QXZ("This field needs to be set to the number of seconds that the Estimated Hold Time Minimum Filename prompt plays for. Default is 10."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option">
<BR>
<B><?php echo _QXZ("Wait Time Option"); ?> -</B><?php echo _QXZ("This allows you to give customers options to leave the queue if their wait time is over the amount of seconds specified below. Default is NONE. If one of the PRESS_ options is selected, it will play the Press Filename defined below and give the customer the option to press 1 on their phone to leave the queue and run the selected option. The PRESS_STAY option will send the customer back to the queue without loosing their place in line. The PRESS_CALLBACK_QUEUE option will preserve the caller place in line and will call the customer back when their place is the next one to go to an agent, this inbound callback queue entry will last until the call is placed back to the customer or as long as the Callback Queue Expire Hours setting below."); ?>

<BR>
<A NAME="inbound_groups-wait_time_second_option">
<BR>
<B><?php echo _QXZ("Wait Time Second Option"); ?> -</B><?php echo _QXZ("Same as the first Wait Time Option field above, except this one will check for the customer pressing the 2 key. Default is NONE. If no first Wait Time Option is selected then this option will not be offered."); ?>

<BR>
<A NAME="inbound_groups-wait_time_third_option">
<BR>
<B><?php echo _QXZ("Wait Time Third Option"); ?> -</B><?php echo _QXZ("Same as the first Wait Time Option field above, except this one will check for the customer pressing the 3 key. Default is NONE. If no Second Wait Time Option is selected then this option will not be offered."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_seconds">
<BR>
<B><?php echo _QXZ("Wait Time Option Seconds"); ?> -</B><?php echo _QXZ("If Wait Time Option is set to anything but NONE, this is the number of seconds that the customer has been waiting in queue that will trigger the wait time options. Default is 120 seconds."); ?>

<BR>
<A NAME="inbound_groups-wait_time_lead_reset">
<BR>
<B><?php echo _QXZ("Wait Time Option Lead Reset"); ?> -</B><?php echo _QXZ("This option if set to Y, will set the lead called-since-last-reset field to N when the Wait Time Option is triggered and the call is sent to an action like Message, Voicemail or Hangup. Default is N for disabled."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_exten">
<BR>
<B><?php echo _QXZ("Wait Time Option Extension"); ?> -</B><?php echo _QXZ("If Wait Time Option is set to PRESS_EXTEN, this is the dialplan extension that the call will be sent to if the customer presses the option key when presented with the option. For AGENTDIRECT in-groups, you can put AGENTEXT in this field and the system will look up the user custom five field and send the call to that dialplan number."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_callmenu">
<BR>
<B><?php echo _QXZ("Wait Time Option Callmenu"); ?> -</B><?php echo _QXZ("If Wait Time Option is set to PRESS_CALLMENU, this is the Call Menu that the call will be sent to if the customer presses the option key when presented with the option."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_voicemail">
<BR>
<B><?php echo _QXZ("Wait Time Option Voicemail"); ?> -</B><?php echo _QXZ("If Wait Time Option is set to PRESS_VMAIL, this is the voicemail box that the call will be sent to if the customer presses the option key when presented with the option. In an AGENTDIRECT in-group, setting this to AGENTVMAIL will select the User voicemail ID to use."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_xfer_group">
<BR>
<B><?php echo _QXZ("Wait Time Option Transfer In-Group"); ?> -</B><?php echo _QXZ("If Wait Time Option is set to PRESS_INGROUP, this is the inbound group that the call will be sent to if the customer presses the option key when presented with the option."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_press_filename">
<BR>
<B><?php echo _QXZ("Wait Time Option Press Filename"); ?> -</B><?php echo _QXZ("If Wait Time Option is set to one of the PRESS_ options, this is the filename prompt that is played if the customer wait time exceeds the Wait Time Option Seconds to give the customer the option to press 1, 2 or 3 on their phone to run the selected Wait Time Press options. It is very important that you include options in the audio file for all of your selected Wait Time Options, and that the audio file length in seconds is properly defined in the Filename Seconds field below or there will be problems. Default is to-be-called-back."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_no_block">
<BR>
<B><?php echo _QXZ("Wait Time Option Press No Block"); ?> -</B><?php echo _QXZ("Setting this option to Y will allow calls in line behind a call where the Wait Time Option Press Filename prompt is playing to go to an agent if one becomes available while the message is playing. While the Wait Time Option Press Filename message is playing to a customer they cannot be sent to an agent. Default is N."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_prompt_seconds">
<BR>
<B><?php echo _QXZ("Wait Time Option Press Filename Seconds"); ?> -</B><?php echo _QXZ("This field needs to be set to the number of seconds that the Wait Time Option Press Filename plays for. Default is 10."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_callback_filename">
<BR>
<B><?php echo _QXZ("Wait Time Option After Press Filename"); ?> -</B><?php echo _QXZ("If Wait Time Option is set to one of the PRESS_ options, this is the filename prompt that is played after the customer has pressed 1, 2 or 3."); ?>

<BR>
<A NAME="inbound_groups-wait_time_option_callback_list_id">
<BR>
<B><?php echo _QXZ("Wait Time Option Callback List ID"); ?> -</B><?php echo _QXZ("If Wait Time Option is set to PRESS_CID_CALLBACK, this is the List ID the call is added to as a new lead if the customer presses the option key when presented with the option."); ?>

<BR>
<A NAME="inbound_groups-wait_hold_option_priority">
<BR>
<B><?php echo _QXZ("Wait Hold Option Priority"); ?> -</B><?php echo _QXZ("If both Estimated Hold Time options and Wait Time options are active, this setting will define whether one, the other or both of these features are active. For example, if the Estimated Hold Time Option is set to 360, the Wait Time option is set to 120 and the customer has been waiting for 120 seconds and there are still 400 seconds estimated hold time, then they are both active at the same time and this setting will be checked to see what options will be offered. Default is WAIT only."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option">
<BR>
<B><?php echo _QXZ("Estimated Hold Time Option"); ?> -</B><?php echo _QXZ("This allows you to specify the routing of the call if the estimated hold time is over the amount of seconds specified below. Default is NONE. If one of the PRESS_ options is selected, it will play the Press Filename defined below and give the customer the option to press 1 on their phone to leave the queue and run the selected option. The PRESS_CALLBACK_QUEUE option will preserve the caller place in line and will call the customer back when their place is the next one to go to an agent, this inbound callback queue entry will last until the call is placed back to the customer or as long as the Callback Queue Expire Hours setting below."); ?>

<BR>
<A NAME="inbound_groups-hold_time_second_option">
<BR>
<B><?php echo _QXZ("Hold Time Second Option"); ?> -</B><?php echo _QXZ("Same as the first Hold Time Option field above, except this one will check for the customer pressing the 2 key. Default is NONE. If no first Hold Time Option is selected then this option will not be offered."); ?>

<BR>
<A NAME="inbound_groups-hold_time_third_option">
<BR>
<B><?php echo _QXZ("Hold Time Third Option"); ?> -</B><?php echo _QXZ("Same as the first Hold Time Option field above, except this one will check for the customer pressing the 3 key. Default is NONE. If no Second Hold Time Option is selected then this option will not be offered."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_seconds">
<BR>
<B><?php echo _QXZ("Hold Time Option Seconds"); ?> -</B><?php echo _QXZ("If Hold Time Option is set to anything but NONE, this is the number of seconds of estimated hold time that will trigger the hold time option. Default is 360 seconds."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_minimum">
<BR>
<B><?php echo _QXZ("Hold Time Option Minimum"); ?> -</B><?php echo _QXZ("If Hold Time Option enabled, this is the minimum number of seconds the call must be waiting before it will be presented with the hold time option. The hold time option will immediately be presented at this time if the estimated hold time is greater than the Hold Time Option Seconds value. Default is 0 seconds."); ?>

<BR>
<A NAME="inbound_groups-hold_time_lead_reset">
<BR>
<B><?php echo _QXZ("Hold Time Option Lead Reset"); ?> -</B><?php echo _QXZ("This option if set to Y, will set the lead called-since-last-reset field to N when the Hold Time Option is triggered and the call is sent to an action like Message, Voicemail or Hangup. Default is N for disabled."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_exten">
<BR>
<B><?php echo _QXZ("Hold Time Option Extension"); ?> -</B><?php echo _QXZ("If Hold Time Option is set to EXTENSION, this is the dialplan extension that the call will be sent to if the estimated hold time exceeds the Hold Time Option Seconds. For AGENTDIRECT in-groups, you can put AGENTEXT in this field and the system will look up the user custom five field and send the call to that dialplan number."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_callmenu">
<BR>
<B><?php echo _QXZ("Hold Time Option Callmenu"); ?> -</B><?php echo _QXZ("If Hold Time Option is set to CALL_MENU, this is the Call Menu that the call will be sent to if the estimated hold time exceeds the Hold Time Option Seconds."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_voicemail">
<BR>
<B><?php echo _QXZ("Hold Time Option Voicemail"); ?> -</B><?php echo _QXZ("If Hold Time Option is set to VOICEMAIL, this is the voicemail box that the call will be sent to if the estimated hold time exceeds the Hold Time Option Seconds. In an AGENTDIRECT in-group, setting this to AGENTVMAIL will select the User voicemail ID to use."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_xfer_group">
<BR>
<B><?php echo _QXZ("Hold Time Option Transfer In-Group"); ?> -</B><?php echo _QXZ("If Hold Time Option is set to IN_GROUP, this is the inbound group that the call will be sent to if the estimated hold time exceeds the Hold Time Option Seconds."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_press_filename">
<BR>
<B><?php echo _QXZ("Hold Time Option Press Filename"); ?> -</B><?php echo _QXZ("If Hold Time Option is set to one of the PRESS_ options, this is the filename prompt that is played if the estimated hold time exceeds the Hold Time Option Seconds to give the customer the option to press 1 on their phone to run the selected Hold Time Press Option. It is very important that this audio file is 10 seconds or less or there will be problems. Default is to-be-called-back."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_no_block">
<BR>
<B><?php echo _QXZ("Hold Time Option Press No Block"); ?> -</B><?php echo _QXZ("Setting this option to Y will allow calls in line behind a call where the Hold Time Option Press Filename prompt is playing to go to an agent if one becomes available while the message is playing. While the Hold Time Option Press Filename message is playing to a customer they cannot be sent to an agent. Default is N."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_prompt_seconds">
<BR>
<B><?php echo _QXZ("Hold Time Option Press Filename Seconds"); ?> -</B><?php echo _QXZ("This field needs to be set to the number of seconds that the Hold Time Option Press Filename plays for. Default is 10."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_callback_filename">
<BR>
<B><?php echo _QXZ("Hold Time Option After Press Filename"); ?> -</B><?php echo _QXZ("If Hold Time Option is set to one of the PRESS_ options or CALLERID_CALLBACK, this is the filename prompt that is played after the customer has pressed 1 or the call has been added to the callback list."); ?>

<BR>
<A NAME="inbound_groups-hold_time_option_callback_list_id">
<BR>
<B><?php echo _QXZ("Hold Time Option Callback List ID"); ?> -</B><?php echo _QXZ("If Hold Time Option is set to CALLERID_CALLBACK, this is the List ID the call is added to as a new lead if the estimated hold time exceeds the Hold Time Option Seconds."); ?>

<BR>
<A NAME="inbound_groups-icbq_expiration_hours">
<BR>
<B><?php echo _QXZ("Callback Queue Expire Hours"); ?> -</B><?php echo _QXZ("If a Hold Time or Wait Time Option is set to PRESS_CALLBACK_QUEUE, this is the maximum number of hours that an entry can stay in the inbound callback queue before it is removed without dialing it. Default is 96 hours."); ?>

<BR>
<A NAME="inbound_groups-icbq_call_time_id">
<BR>
<B><?php echo _QXZ("Callback Queue Call Time"); ?> -</B><?php echo _QXZ("For any inbound callback queue outbound calls to be placed, this is the local call time used to determine if the number can be dialed right now or not."); ?>

<BR>
<A NAME="inbound_groups-icbq_dial_filter">
<BR>
<B><?php echo _QXZ("Callback Queue Dial Filter"); ?> -</B><?php echo _QXZ("This option allows you to remove DNC numbers from your Callback Queue. You can use any combination of: Internal DNC List, Campaign DNC List tied to campaign of the list where the lead is, and Areacode DNC wildcard"); ?>

<BR>
<A NAME="inbound_groups-closing_time_action">
<BR>
<B><?php echo _QXZ("Closing Time Action"); ?> -</B><?php echo _QXZ("This allows you to specify the routing of the call if the closing time of the in-group is reached while the call is still waiting for an agent, Closing time is the end of the Call Time that is defined for this in-group. Default is DISABLED. If one of the PRESS_ options is selected, it will play the Press Filename defined below and give the customer the option to press 1 on their phone to leave the queue and run the selected option. The PRESS_CALLBACK_QUEUE option will preserve the caller place in line and will call the customer back when their place is the next one to go to an agent, this inbound callback queue entry will last until the call is placed back to the customer or as long as the Callback Queue Expire Hours setting above."); ?>

<BR>
<A NAME="inbound_groups-closing_time_now_trigger">
<BR>
<B><?php echo _QXZ("Closing Time Now Trigger"); ?> -</B><?php echo _QXZ("If Closing Time Action is enabled, this flag allows you to send all customers waiting in the queue to the Closing Time Action as defined above, before the actual closing time is reached for the day."); ?>

<BR>
<A NAME="inbound_groups-closing_time_filename">
<BR>
<B><?php echo _QXZ("Closing Time Press Filename"); ?> -</B><?php echo _QXZ("If Closing Time Option is set to one of the PRESS_ options, this is the filename prompt that is played if the in-group has reached the closing time for the day. It is very important that this audio file is 10 seconds or less or there will be problems."); ?>

<BR>
<A NAME="inbound_groups-closing_time_end_filename">
<BR>
<B><?php echo _QXZ("Closing Time End Filename"); ?> -</B><?php echo _QXZ("If Closing Time Option is set to one of the PRESS_ options or PRESS_CID_CALLBACK or PRESS_CALLBACK_QUEUE, this is the filename prompt that is played after the customer has pressed 1 or the call has been added to the callback list or queue."); ?>

<BR>
<A NAME="inbound_groups-closing_time_lead_reset">
<BR>
<B><?php echo _QXZ("Closing Time Option Lead Reset"); ?> -</B><?php echo _QXZ("This option if set to Y, will set the lead called-since-last-reset field to N when the Closing Time Option is triggered and the call is sent to an action like Message, Voicemail or Hangup. Default is N for disabled."); ?>

<BR>
<A NAME="inbound_groups-closing_time_option_exten">
<BR>
<B><?php echo _QXZ("Closing Time Option Extension"); ?> -</B><?php echo _QXZ("If Closing Time Option is set to EXTENSION, this is the dialplan extension that the call will be sent to if the Closing Time is reached. For AGENTDIRECT in-groups, you can put AGENTEXT in this field and the system will look up the user custom five field and send the call to that dialplan number."); ?>

<BR>
<A NAME="inbound_groups-closing_time_option_callmenu">
<BR>
<B><?php echo _QXZ("Closing Time Option Callmenu"); ?> -</B><?php echo _QXZ("If Closing Time Option is set to CALL_MENU, this is the Call Menu that the call will be sent to if the Closing Time is reached."); ?>

<BR>
<A NAME="inbound_groups-closing_time_option_voicemail">
<BR>
<B><?php echo _QXZ("Closing Time Option Voicemail"); ?> -</B><?php echo _QXZ("If Closing Time Option is set to VOICEMAIL, this is the voicemail box that the call will be sent to if the Closing Time is reached. In an AGENTDIRECT in-group, setting this to AGENTVMAIL will select the User voicemail ID to use."); ?>

<BR>
<A NAME="inbound_groups-closing_time_option_xfer_group">
<BR>
<B><?php echo _QXZ("Closing Time Option Transfer In-Group"); ?> -</B><?php echo _QXZ("If Closing Time Option is set to IN_GROUP, this is the inbound group that the call will be sent to if the Closing Time is reached."); ?>

<BR>
<A NAME="inbound_groups-closing_time_option_callback_list_id">
<BR>
<B><?php echo _QXZ("Closing Time Option Callback List ID"); ?> -</B><?php echo _QXZ("If Closing Time Option is set to CALLERID_CALLBACK, this is the List ID the call is added to as a new lead if the Closing Time is reached."); ?>

<BR>
<A NAME="inbound_groups-agent_alert_exten">
<BR>
<B><?php echo _QXZ("Agent Alert Filename"); ?> -</B><?php echo _QXZ("The audio file to play to an agent to announce that a call is coming to the agent. To not use this function set this to X. Default is ding."); ?>

<BR>
<A NAME="inbound_groups-agent_alert_delay">
<BR>
<B><?php echo _QXZ("Agent Alert Delay"); ?> -</B><?php echo _QXZ("The length of time in milliseconds to wait before sending the call to the agent after playing the on Agent Alert Extension. Default is 1000."); ?>

<BR>
<A NAME="inbound_groups-default_xfer_group">
<BR>
<B><?php echo _QXZ("Default Transfer Group"); ?> -</B><?php echo _QXZ("This field is the default In-Group that will be automatically selected when the agent goes to the transfer-conference frame in their agent interface."); ?>

<BR>
<A NAME="inbound_groups-ingroup_recording_override">
<BR>
<B><?php echo _QXZ("In-Group Recording Override"); ?> -</B><?php echo _QXZ("This field allows for the overriding of the campaign call recording setting. This setting can be overridden by the user recording override setting. DISABLED will not override the campaign recording setting. NEVER will disable recording on the client. ONDEMAND is the default and allows the agent to start and stop recording as needed. ALLCALLS will start recording on the client whenever a call is sent to an agent. ALLFORCE will start recording on the client whenever a call is sent to an agent giving the agent no option to stop recording."); ?>

<BR>
<A NAME="inbound_groups-routing_initiated_recordings">
<BR>
<B><?php echo _QXZ("Routing Initiated Recording"); ?> -</B><?php echo _QXZ("This option, if enabled, allows you to have the call routing script for Inbound calls trigger the agent call recording instead of the agent screen. This option will only work if the recording option is set to ALLCALLS or ALLFORCE. This will not work with inbound on-hook agents. Default is N for disabled."); ?>

<BR>
<A NAME="inbound_groups-ingroup_rec_filename">
<BR>
<B><?php echo _QXZ("In-Group Recording Filename"); ?> -</B><?php echo _QXZ("This field will override the Campaign Recording Filenaming Scheme unless it is set to NONE. The allowed variables are CAMPAIGN INGROUP CUSTPHONE FULLDATE TINYDATE EPOCH AGENT VENDORLEADCODE LEADID CALLID RECID. If your dialers have --POST recording processing enabled, you can also use POSTVLC POSTSP POSTARRD3 POSTSTATUS. These POST options will alter the recording file name after the call has been finished and will replace the post variable with the value from the default fields. The default is FULLDATE_AGENT and would look like this 20051020-103108_6666. Another example is CAMPAIGN_TINYDATE_CUSTPHONE which would look like this TESTCAMP_51020103108_3125551212. The resulting filename must be less than 90 characters in length. Default is NONE.");

if ($SSqc_features_active > 0)
	{
	?>
	<BR>
	<A NAME="inbound_groups-qc_enabled">
	<BR>
	<B><?php echo _QXZ("QC Enabled"); ?> -</B><?php echo _QXZ("Setting this field to Y allows for the agent Quality Control features to work. Default is N."); ?>

	<BR>
	<A NAME="inbound_groups-qc_statuses">
	<BR>
	<B><?php echo _QXZ("QC Statuses"); ?> -</B><?php echo _QXZ("This area is where you select which statuses of leads should be gone over by the QC system. Place a check next to the status that you want QC to review. "); ?>

	<BR>
	<A NAME="inbound_groups-qc_shift_id">
	<BR>
	<B><?php echo _QXZ("QC Shift"); ?> -</B><?php echo _QXZ("This is the shift timeframe used to pull QC records for an inbound_group. The days of the week are ignored for these functions."); ?>

	<BR>
	<A NAME="inbound_groups-qc_get_record_launch">
	<BR>
	<B><?php echo _QXZ("QC Get Record Launch-</B> This allows one of the following actions to be triggered upon a QC agent receiving a new record."); ?>

	<BR>
	<A NAME="inbound_groups-qc_show_recording">
	<BR>
	<B><?php echo _QXZ("QC Show Recording"); ?> -</B><?php echo _QXZ("This allows for a recording that may be linked with the QC record to be display in the QC agent screen."); ?>

	<BR>
	<A NAME="inbound_groups-qc_web_form_address">
	<BR>
	<B><?php echo _QXZ("QC WebForm Address"); ?> -</B><?php echo _QXZ("This is the website address that a QC agent can go to when clicking on the WEBFORM link in the QC screen."); ?>

	<BR>
	<A NAME="inbound_groups-qc_script">
	<BR>
	<B><?php echo _QXZ("QC Script"); ?> -</B><?php echo _QXZ("This is the script that can be used by QC agents in the SCRIPT tab in the QC screen.");
	}
?>

<BR>
<A NAME="inbound_groups-hold_recall_xfer_group">
<BR>
<B><?php echo _QXZ("Hold Recall Transfer In-Group"); ?> -</B><?php echo _QXZ("If a customer calls back to this in-group more than once and this is not set to NONE, then the call will automatically be sent on to the In-Group selected in this field. Default is NONE."); ?>

<BR>
<A NAME="inbound_groups-no_delay_call_route">
<BR>
<B><?php echo _QXZ("No Delay Call Route"); ?> -</B><?php echo _QXZ("Setting this to Y will remove all wait times and audio prompts and attempt to send the call right to an agent. Does not override welcome message or on hold prompt settings. Default is N."); ?>

<BR>
<A NAME="inbound_groups-answer_sec_pct_rt_stat_one">
<BR>
<B><?php echo _QXZ("Stats Percent of Calls Answered Within X seconds"); ?> -</B><?php echo _QXZ("This field allows you to set the number of hold seconds that the realtime stats display will use to calculate the percentage of answered calls that were answered within X number of seconds on hold."); ?>

<BR>
<A NAME="inbound_groups-start_call_url">
<BR>
<B><?php echo _QXZ("Start Call URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is called every time a call is sent to an agent if it is populated. Uses the same variables as the web form fields and scripts. Default is blank."); ?>

<BR>
<A NAME="inbound_groups-dispo_call_url">
<BR>
<B><?php echo _QXZ("Dispo Call URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is called every time a call is dispositioned by an agent if it is populated. Uses the same variables as the web form fields and scripts. dispo and talk_time are the variables you can use to retrieve the agent-defined disposition for the call and the actual talk time in seconds of the call. Default is blank.") . " " . _QXZ("If you put ALT into this field and submit this form, you will be able to go to a separate page where you can define multiple URLs for this action as well as specific statuses that will trigger them.") . " " . _QXZ("If you want the campaign Dispo Call URL to be used for inbound calls, then put CAMP into this field."); ?>

<BR>
<A NAME="inbound_groups-na_call_url">
<BR>
<B><?php echo _QXZ("No Agent Call URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but if it is populated it is called every time a call that is not handled by an agent is hung up or transferred. Uses the same variables as the web form fields and scripts. dispo can be used to retrieve the system-defined disposition for the call. This URL can NOT be a relative path. Default is blank."); ?> <?php echo _QXZ("Custom Fields are not available with this feature."); ?>

<BR>
<A NAME="inbound_groups-add_lead_url">
<BR>
<B><?php echo _QXZ("Add Lead URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is called every time a lead is added to the system through the inbound process. Default is blank. You must begin this URL with VAR if you want to use variables, and of course --A-- and --B-- around the actual variable in the URL where you want to use it. Here is the list of variables that are available for this function. lead_id, vendor_lead_code, list_id, phone_number, phone_code, did_id, did_extension, did_pattern, did_description, uniqueid"); ?>

<BR>
<A NAME="inbound_groups-start_email_url">
<BR>
<B><?php echo _QXZ("Start Email URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is emailed every time a email is sent to an agent if it is populated. Uses the same variables as the web form fields and scripts. Default is blank."); ?>

<BR>
<A NAME="inbound_groups-dispo_email_url">
<BR>
<B><?php echo _QXZ("Dispo Email URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is emailed every time a email is dispositioned by an agent if it is populated. Uses the same variables as the web form fields and scripts. dispo and talk_time are the variables you can use to retrieve the agent-defined disposition for the email and the actual talk time in seconds of the email. Default is blank.") . " " . _QXZ("If you put ALT into this field and submit this form, you will be able to go to a separate page where you can define multiple URLs for this action as well as specific statuses that will trigger them.") . " " . _QXZ("If you want the campaign Dispo email URL to be used for inbound emails, then put CAMP into this field."); ?>

<BR>
<A NAME="inbound_groups-default_list_id">
<BR>
<B><?php echo _QXZ("Default List ID"); ?> -</B><?php echo _QXZ("This is the List ID that leads may be searched through and that leads will be inserted into if necessary."); ?>

<BR>
<A NAME="inbound_groups-start_chat_url">
<BR>
<B><?php echo _QXZ("Start Chat URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is called every time a chat is sent to an agent if it is populated. Uses the same variables as the web form fields and scripts. Default is blank."); ?>

<BR>
<A NAME="inbound_groups-dispo_chat_url">
<BR>
<B><?php echo _QXZ("Dispo Chat URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but it is called every time a chat is dispositioned by an agent if it is populated. Uses the same variables as the web form fields and scripts. dispo and talk_time are the variables you can use to retrieve the agent-defined disposition for the chat and the actual talk time in seconds of the chat. Default is blank.") . " " . _QXZ("If you put ALT into this field and submit this form, you will be able to go to a separate page where you can define multiple URLs for this action as well as specific statuses that will trigger them.") . " " . _QXZ("If you want the campaign Dispo Chat URL to be used for inbound chats, then put CAMP into this field."); ?>

<BR>
<A NAME="inbound_groups-na_chat_url">
<BR>
<B><?php echo _QXZ("No Agent Chat URL"); ?> -</B><?php echo _QXZ("This web URL address is not seen by the agent, but if it is populated it is called every time a chat that is not handled by an agent is hung up or transferred. Uses the same variables as the web form fields and scripts. dispo can be used to retrieve the system-defined disposition for the chat. This URL can NOT be a relative path. Default is blank."); ?> <?php echo _QXZ("Custom Fields are not available with this feature."); ?>

<BR>
<A NAME="inbound_groups-add_lead_timezone">
<BR>
<B><?php echo _QXZ("Add Lead Timezone"); ?> -</B><?php echo _QXZ("This is the method that the system will use to determine the current timezone when a lead is created when a call is being routed through this in-group. SERVER will use the current timezone of the server. PHONE_CODE_AREACODE will look up the timezone based on the phone code set in the lead and the areacode of the phone number. Default is SERVER."); ?>

<BR>
<A NAME="inbound_groups-default_group_alias">
<BR>
<B><?php echo _QXZ("Default Group Alias"); ?> -</B><?php echo _QXZ("If you have allowed Group Aliases for the campaign that the agent is logged into then this is the group alias that is selected first by default on a call coming in from this inbound group when the agent chooses to use a Group Alias for an outbound manual call. Default is NONE or empty."); ?>

<BR>
<A NAME="inbound_groups-dial_ingroup_cid">
<BR>
<B><?php echo _QXZ("Dial In-Group CID"); ?> -</B><?php echo _QXZ("If the agent campaign allows for Manual In-Group Dialing, this caller ID number will be sent as the outgoing CID of the phone call if it is populated, overriding the campaign settings and list CID override setting. Default is empty."); ?>

<BR>
<A NAME="inbound_groups-extension_appended_cidname">
<BR>
<B><?php echo _QXZ("Extension Append CID"); ?> -</B><?php echo _QXZ("If enabled, the calls coming in from this in-group will have a space and the phone extension of the agent appended to the end of the CallerID name for the call before it is sent to the agent. Default is N for disabled. If USER is part of the option, then the user ID will be used instead of the phone extension. If WITH_CAMPAIGN is used, then another spae and the campaign ID will be included as well."); ?>

<BR>
<A NAME="inbound_groups-uniqueid_status_display">
<BR>
<B><?php echo _QXZ("Uniqueid Status Display"); ?> -</B><?php echo _QXZ("If enabled, when an agent receives a call through this in-group they will see the uniqueid of the call added to the status line in their agent interface. The PREFIX option will add the prefix, defined below, to the beginning of the uniqueid in the display. Default is DISABLED. If there was already a Uniqueid defined on a call entering this in-group, then the original uniqueid will be displayed. If the PRESERVE option is used and the call is sent to a second agent, the uniqueid and prefix displayed to the first agent will also be displayed to the second agent."); ?>

<BR>
<A NAME="inbound_groups-uniqueid_status_prefix">
<BR>
<B><?php echo _QXZ("Uniqueid Status Prefix"); ?> -</B><?php echo _QXZ("If PREFIX option is selected above then this is the value of that prefix. Default is empty."); ?>

<BR>
<A NAME="inbound_groups-customer_chat_screen_colors">
<BR>
<B><?php echo _QXZ("Customer Chat Screen Colors"); ?> -</B><?php echo _QXZ("This option allows you to select the colors and logo that the customer will see when they use the chat screen. default is the default blue color scheme."); ?>

<BR>
<A NAME="inbound_groups-customer_chat_survey_link">
<BR>
<B><?php echo _QXZ("Customer Chat Survey Link"); ?> -</B><?php echo _QXZ("This option allows you to define a link that the customer can go to after their chat session is over so that they can take a survey. If no link is defined then the customer will not be presented with a link. default is the empty."); ?>

<BR>
<A NAME="inbound_groups-customer_chat_survey_text">
<BR>
<B><?php echo _QXZ("Customer Chat Survey Text"); ?> -</B><?php echo _QXZ("If the Survey Link above is populated, then this field can be used to define the text that is shown to the customer for them to click on to go to the survey link. default is the empty, in which case -PLEASE TAKE OUR SURVEY- will be used."); ?>

<BR>
<A NAME="inbound_groups-populate_lead_ingroup">
<BR>
<B><?php echo _QXZ("Populate Lead In-Group"); ?> -</B><?php echo _QXZ("If this option is ENABLED, then when a new lead is created when going into an In-Group, the security_phrase or Show field will be populated with the Group ID of the In-Group. Default is ENABLED."); ?>

<BR>
<A NAME="inbound_groups-populate_lead_province">
<BR>
<B><?php echo _QXZ("Populate Lead Province"); ?> -</B><?php echo _QXZ("If this option is not DISABLED, then the system will look up the original DID that the inbound call came in on and populate one of the listed DID fields in the province field on the customer lead. The OW options will overwrite the province field every time the call enters this In-Group. Default is DISABLED."); ?>

<BR>
<A NAME="inbound_groups-populate_state_areacode">
<BR>
<B><?php echo _QXZ("Populate Lead State Areacode"); ?> -</B><?php echo _QXZ("If this option is not DISABLED, then the system will look up the state that the areacode of the phone number is from and populate the state field with that value. If the OVERWRITE_ALWAYS option is selected, then every time that lead goes through this in-group, the state field will be looked up and populated again. Default is DISABLED."); ?>

<BR>
<A NAME="inbound_groups-populate_lead_source">
<BR>
<B><?php echo _QXZ("Populate Lead Source"); ?> -</B><?php echo _QXZ("If this option is not DISABLED and there is no source_id channel variable set on the call, then the system will fill the source_id lead field with one of the other options when leads are added with an inbound call. INBOUND_NUMBER will use the inbound phone number dialed. BLANK will leave the source_id field blank. DISABLED will use the default behavior which will set the source_id field to VDCL. Default is DISABLED."); ?>

<BR>
<A NAME="inbound_groups-populate_lead_vendor">
<BR>
<B><?php echo _QXZ("Populate Lead Vendor"); ?> -</B><?php echo _QXZ("If this option is not DISABLED, and the inbound call was not delivered with a VID option, then the system will put the value entered in this setting into the vendor_lead_code field when a new lead is inserted. Special options include INBOUND_NUMBER which will use the inbound phone number dialed. Default is INBOUND_NUMBER."); ?>

<BR>
<A NAME="inbound_groups-inbound_survey">
<BR>
<B><?php echo _QXZ("After Call Survey"); ?> -</B><?php echo _QXZ("If this option is ENABLED, then the customer calling in to this In-Group will be asked if they would like to participate in a survey after their call has been handled by an agent. Default is DISABLED."); ?>

<BR>
<A NAME="inbound_groups-inbound_survey_filename">
<BR>
<B><?php echo _QXZ("After Call Survey Accept Filename"); ?> -</B><?php echo _QXZ("If the After Call Survey option is enabled above, this is the filename of the audio prompt that is played to ask the customer if they want to participate in the survey."); ?>

<BR>
<A NAME="inbound_groups-inbound_survey_accept_digit">
<BR>
<B><?php echo _QXZ("After Call Survey Accept Digit"); ?> -</B><?php echo _QXZ("If the After Call Survey option is enabled above, this is the digit that the customer must press to have their call set to take a survey after the agent has handled their call."); ?>

<BR>
<A NAME="inbound_groups-inbound_survey_question_filename">
<BR>
<B><?php echo _QXZ("After Call Question Filename"); ?> -</B><?php echo _QXZ("If the After Call Survey option is enabled above, and if the customer has chosen to take the survey, this is the audio prompt filename that will play the question to the customer."); ?>

<BR>
<A NAME="inbound_groups-inbound_survey_callmenu">
<BR>
<B><?php echo _QXZ("After Call End Call Menu"); ?> -</B><?php echo _QXZ("If the After Call Survey option is enabled above, this is the Call Menu that the customer will be sent to after the customer has responded to the survey question. This option can allow the customer to answer additional questions within Call Menus if desired. If this field is blank, the customer phone call will be hung up after they respond to the question."); ?>


<BR>
<A NAME="inbound_groups-customer_chat_link">
<BR>
<B><?php echo _QXZ("Customer Chat Link"); ?> -</B><?php echo _QXZ("Clicking this link will take you to the customer chat interface for this in-group.  You can use this link as a direct link on your website for a customer chat feature. The first link will go to a page in an IFRAME, the Second Link will go directly to a page that you can put into your own IFRAME. The Chat URL System Setting should look like one of these links without what is after the question mark."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>INBOUND_DIDS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<BR>
<A NAME="inbound_dids-did_pattern">
<BR>
<B><?php echo _QXZ("DID Extension"); ?> -</B><?php echo _QXZ("This is the number, extension or DID that will trigger this entry and that you will route within the system using this function. There is a reserved default DID that you can use which is just the word -default- without the dashes, that will allows you to send any call that does not match any other existing patterns to the default DID."); ?>

<BR>
<A NAME="inbound_dids-did_description">
<BR>
<B><?php echo _QXZ("DID Description"); ?> -</B><?php echo _QXZ("This is the description of the DID routing entry."); ?>

<BR>
<A NAME="inbound_dids-did_carrier_description">
<BR>
<B><?php echo _QXZ("DID Carrier Description"); ?> -</B><?php echo _QXZ("This is another description field for the DID, to be used to describe the carrier of this DID. It is not used for any other purpose in the system."); ?>

<BR>
<A NAME="inbound_dids-did_active">
<BR>
<B><?php echo _QXZ("DID Active"); ?> -</B><?php echo _QXZ("This the field where you set the DID entry to active or not. Default is Y."); ?>

<BR>
<A NAME="inbound_dids-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this did, this allows admin viewing of this did restricted by user group. Default is --ALL-- which allows any admin user to view this did."); ?>

<BR>
<A NAME="inbound_dids-did_route">
<BR>
<B><?php echo _QXZ("DID Route"); ?> -</B><?php echo _QXZ("This the type of route that you set the DID to use. EXTEN will send calls to the extension entered below, VOICEMAIL will send calls directly to the voicemail box entered below, AGENT will send calls to an agent if they are logged in, PHONE will send the call to a phones entry selected below, IN_GROUP will send calls directly to the specified inbound group. Default is EXTEN. CALLMENU will send the call to the defined Call Menu. VMAIL_NO_INST will send the call to the voicemail box that you have defined below and will not play instructions after the voicemail message."); ?>

<BR>
<A NAME="inbound_dids-record_call">
<BR>
<B><?php echo _QXZ("Record Call"); ?> -</B><?php echo _QXZ("This option allows you to set the calls coming into this DID to be recorded. Y will record the entire call, Y_QUEUESTOP will record the call until the call is hungup or enters an in-group queue, N will not record the call. Default is N."); ?>

<BR>
<A NAME="inbound_dids-extension">
<BR>
<B><?php echo _QXZ("Extension"); ?> -</B><?php echo _QXZ("If EXTEN is selected as the DID Route, then this is the dialplan extension that calls will be sent to. Default is 9998811112, no-service."); ?>

<BR>
<A NAME="inbound_dids-exten_context">
<BR>
<B><?php echo _QXZ("Extension Context"); ?> -</B><?php echo _QXZ("If EXTEN is selected as the DID Route, then this is the dialplan context that calls will be sent to. Default is default."); ?>

<BR>
<A NAME="inbound_dids-voicemail_ext">
<BR>
<B><?php echo _QXZ("Voicemail Box"); ?> -</B><?php echo _QXZ("If VOICEMAIL is selected as the DID Route, then this is the voicemail box that calls will be sent to. Default is empty."); ?>

<BR>
<A NAME="inbound_dids-phone">
<BR>
<B><?php echo _QXZ("Phone Extension"); ?> -</B><?php echo _QXZ("If PHONE is selected as the DID Route, then this is the phone extension that calls will be sent to."); ?>

<BR>
<A NAME="inbound_dids-server_ip">
<BR>
<B><?php echo _QXZ("Phone Server IP"); ?> -</B><?php echo _QXZ("If PHONE is selected as the DID Route, then this is the server IP for the phone extension that calls will be sent to."); ?>

<BR>
<A NAME="inbound_dids-menu_id">
<BR>
<B><?php echo _QXZ("Call Menu"); ?> -</B><?php echo _QXZ("If CALLMENU is selected as the DID Route, then this is the Call Menu that calls will be sent to."); ?>

<BR>
<A NAME="inbound_dids-user">
<BR>
<B><?php echo _QXZ("User Agent"); ?> -</B><?php echo _QXZ("If AGENT is selected as the DID Route, then this is the Agent that calls will be sent to."); ?>

<BR>
<A NAME="inbound_dids-user_unavailable_action">
<BR>
<B><?php echo _QXZ("User Unavailable Action"); ?> -</B><?php echo _QXZ("If AGENT is selected as the DID Route, and the user is not logged in or available, then this is the route that the calls will take."); ?>

<BR>
<A NAME="inbound_dids-user_route_settings_ingroup">
<BR>
<B><?php echo _QXZ("User Route Settings In-Group"); ?> -</B><?php echo _QXZ("If AGENT is selected as the DID Route, then this is the In-Group that will be used for the queue settings as the caller is waiting to be sent to the agent. Default is AGENTDIRECT."); ?>

<BR>
<A NAME="inbound_dids-group_id">
<BR>
<B><?php echo _QXZ("In-Group ID"); ?> -</B><?php echo _QXZ("If IN_GROUP is selected as the DID Route, then this is the In-Group that calls will be sent to."); ?>

<BR>
<A NAME="inbound_dids-call_handle_method">
<BR>
<B><?php echo _QXZ("In-Group Call Handle Method"); ?> -</B><?php echo _QXZ("If IN_GROUP is selected as the DID Route, then this is the call handling method used for these calls. CID will add a new lead record with every call using the CallerID as the phone number, CIDLOOKUP will attempt to lookup the phone number by the CallerID in the entire system, CIDLOOKUPRL will attempt to lookup the phone number by the CallerID in only one specified list, CIDLOOKUPRC will attempt to lookup the phone number by the CallerID in all of the lists that belong to the specified campaign, CLOSER is specified for Closer calls, ANI will add a new lead record with every call using the ANI as the phone number, ANILOOKUP will attempt to lookup the phone number by the ANI in the entire system, ANILOOKUPRL will attempt to lookup the phone number by the ANI in only one specified list, XDIGITID will prompt the caller for an X digit code before the call will be put into the queue, VIDPROMPT will prompt the caller for their ID number and will create a new lead record with the CallerID as the phone number and the ID as the Vendor ID, VIDPROMPTLOOKUP will attempt to lookup the ID in the entire system, VIDPROMPTLOOKUPRL will attempt to lookup the vendor ID by the ID in only one specified list, VIDPROMPTLOOKUPRC will attempt to lookup the vendor ID by the ID in all of the lists that belong to the specified campaign. Default is CID. If a CIDLOOKUP method is used with ALT, it will search the alt_phone field for the phone number if no matches are found for the main phone number. If a CIDLOOKUP method is used with ADDR3, it will search the address3 field for the phone number if no matches are found for the main phone number and optionally the alt_phone field."); ?>

<BR>
<A NAME="inbound_dids-agent_search_method">
<BR>
<B><?php echo _QXZ("In-Group Agent Search Method"); ?> -</B><?php echo _QXZ("If IN_GROUP is selected as the DID Route, then this is the agent search method to be used by the inbound group, LO is Load-Balanced-Overflow and will try to send the call to an agent on the local server before trying to send it to an agent on another server, LB is Load-Balanced and will try to send the call to the next agent no matter what server they are on, SO is Server-Only and will only try to send the calls to agents on the server that the call came in on. Default is LB."); ?>

<BR>
<A NAME="inbound_dids-list_id">
<BR>
<B><?php echo _QXZ("In-Group List ID"); ?> -</B><?php echo _QXZ("If IN_GROUP is selected as the DID Route, then this is the List ID that leads may be searched through and that leads will be inserted into if necessary."); ?>

<BR>
<A NAME="inbound_dids-entry_list_id">
<BR>
<B><?php echo _QXZ("In-Group Entry List ID"); ?> -</B><?php echo _QXZ("If IN_GROUP is selected as the DID Route, then this is the Entry List ID that a new lead will be populated with if a new lead is added. Default is 0 for disabled."); ?>

<BR>
<A NAME="inbound_dids-campaign_id">
<BR>
<B><?php echo _QXZ("In-Group Campaign ID"); ?> -</B><?php echo _QXZ("If IN_GROUP is selected as the DID Route, then this is the Campaign ID that leads may be searched for in if the call handle method is CIDLOOKUPRC."); ?>

<BR>
<A NAME="inbound_dids-phone_code">
<BR>
<B><?php echo _QXZ("In-Group Phone Code"); ?> -</B><?php echo _QXZ("If IN_GROUP is selected as the DID Route, then this is the Phone Code used if a new lead is created."); ?>

<BR>
<A NAME="inbound_dids-filter_clean_cid_number">
<BR>
<B><?php echo _QXZ("Clean CID Number"); ?> -</B><?php echo _QXZ("This field allows you to specify a number of digits to restrict the incoming caller ID number to by putting an R in front of the number of digits, for example to restrict to the right 10 digits you would enter in R10. You can also use this feature to remove only a leading digit or digits by putting an L in front of the specific digits that you want to remove, for example to remove a 1 as the first digit you would enter in L1. Default is empty. If more than one rule is specified make sure you separate them with a space and the R will run before the L."); ?>

<BR>
<A NAME="inbound_dids-no_agent_ingroup_redirect">
<BR>
<B><?php echo _QXZ("No-Agent In-Group Redirect"); ?> -</B><?php echo _QXZ("This setting allows you to redirect calls on this DID if there are no logged-in agents set to take calls from a specific In-Group. If this field is set to Y or NO_PAUSED and there are no agents logged in to take calls from the specific In-Group the calls will go to the No Agent In-Group Extension set below. The NO_PAUSED option will only send the call to the defined Extension if there are only paused agents in the in-group. The READY_ONLY option will send the call to the defined Extension if there are no agents waiting for calls right now in the in-group. Default is DISABLED. See the No Agent In-Group Extension setting below for more information."); ?>

<BR>
<A NAME="inbound_dids-no_agent_ingroup_id">
<BR>
<B><?php echo _QXZ("No-Agent In-Group ID"); ?> -</B><?php echo _QXZ("For the No-Agent In-Group Redirect feature above to work properly, an in-Group must be selected from this menu. Default is blank."); ?>

<BR>
<A NAME="inbound_dids-no_agent_ingroup_extension">
<BR>
<B><?php echo _QXZ("No-Agent In-Group Extension"); ?> -</B><?php echo _QXZ("For the No-Agent In-Group Redirect feature above to work properly, an Extension must be set in this field. Default is 9998811112. Below you will see some examples of default extensions that you can use in the system to terminate calls to,"); ?><BR>
 - <?php echo _QXZ("9998811112 - ANSWERED, This number is not in service"); ?><BR>
 - <?php echo _QXZ("9993333333 - UNANSWERED, signal 1, unallocated number, immediate hangup"); ?><BR>
 - <?php echo _QXZ("9998888888 - UNANSWERED, signal 17, busy signal, immediate hangup"); ?><BR>
 - <?php echo _QXZ("9994444444 - UNANSWERED, signal 27, out of order, immediate hangup"); ?><BR>
 - <?php echo _QXZ("9995555555 - UNANSWERED, ring for 120 seconds then hangup"); ?><BR>

<BR>
<A NAME="inbound_dids-max_queue_ingroup_calls">
<BR>
<B><?php echo _QXZ("Max Queue In-Group Calls"); ?> -</B><?php echo _QXZ("This setting allows you to redirect calls on this DID if the number of calls waiting in queue in a specific In-Group is above a set number. If this field is set to 0 this feature is disabled. Default is 0. See the Max Queue In-Group Extension setting below for more information."); ?>

<BR>
<A NAME="inbound_dids-max_queue_ingroup_id">
<BR>
<B><?php echo _QXZ("Max Queue In-Group ID"); ?> -</B><?php echo _QXZ("For the Max Queue In-Group Calls feature above to work properly, an in-Group must be selected from this menu. Default is blank."); ?>

<BR>
<A NAME="inbound_dids-max_queue_ingroup_extension">
<BR>
<B><?php echo _QXZ("Max Queue In-Group Extension"); ?> -</B><?php echo _QXZ("For the Max Queue In-Group Calls feature above to work properly, an Extension must be set in this field. Default is 9998811112. Directly above, in the No-Agent In-Group Extension description, you will see some examples of default extensions that you can use in the system to terminate calls to."); ?><BR>

<BR>
<A NAME="inbound_dids-pre_filter_phone_group_id">
<BR>
<B><?php echo _QXZ("Pre-Filter Phone Group ID"); ?> -</B><?php echo _QXZ("This option allows you to filter calls through a Filter Phone Group before going through the standard filtering process below. If a match is found then the call is redirected to the Pre-Filter Phone Group DID as defined below. Default is blank for disabled."); ?>

<BR>
<A NAME="inbound_dids-pre_filter_extension">
<BR>
<B><?php echo _QXZ("Pre-Filter Phone Group DID"); ?> -</B><?php echo _QXZ("For the Pre-Filter Phone Group ID feature above to work properly, a DID Pattern must be set in this field. Default is blank for disabled. It is recommended that you confirm the DID you enter here is properly set in the system before assigning it."); ?>

<BR>
<A NAME="inbound_dids-filter_inbound_number">
<BR>
<B><?php echo _QXZ("Filter Inbound Number"); ?> -</B><?php echo _QXZ("This option if enabled allows you to filter calls coming into this DID and send them to an alternative action if they match a phone number that is in the filter phone group or a URL response if you have configured one. Default is DISABLED. GROUP will search in a Filter Phone Group. URL will send a URL and will match if a 1 is sent back. DNC_INTERNAL will search by the internal DNC list. DNC_CAMPAIGN will search by one specific campaign DNC list. If the option has AREACODE at the end, then the number and an entry for that numbers 3 digit areacode will be searched for. Both DNC options already have areacode searching built in."); ?>

<BR>
<A NAME="inbound_dids-filter_phone_group_id">
<BR>
<B><?php echo _QXZ("Filter Phone Group ID"); ?> -</B><?php echo _QXZ("If the Filter Inbound Number field is set to GROUP then this is the ID of the Filter Phone Group that will have its numbers searched looking for a match to the caller ID number of the incoming call."); ?>

<BR>
<A NAME="inbound_dids-filter_url">
<BR>
<B><?php echo _QXZ("Filter URL"); ?> -</B><?php echo _QXZ("If the Filter Inbound Number field is set to URL then this is the web address of a script that will search a remote system and return a 1 for a match and a 0 for no match. Only two variables are available in the address if you use the VAR prefix like with webform addresses in campaigns, --A--phone_number--B-- and --A--did_pattern--B-- can be used in the URL to indicate the caller ID of the caller and the DID that the customer called in on."); ?>

<BR>
<A NAME="inbound_dids-filter_url_did_redirect">
<BR>
<B><?php echo _QXZ("Filter URL DID Redirect"); ?> -</B><?php echo _QXZ("If the Filter Inbound Number field is set to URL then this setting allows the URL response to specify a system DID to redirect the call to instead of using the default action. If a 0 is returned then the default action is used. If anything other than a 0 is returned then the call will be redirected to the resulting URL response value."); ?>

<BR>
<A NAME="inbound_dids-filter_dnc_campaign">
<BR>
<B><?php echo _QXZ("Filter DNC Campaign"); ?> -</B><?php echo _QXZ("If the Filter Inbound Number field is set to DNC_CAMPAIGN then this is the specific campaign ID that the campaign DNC list belongs to.

"); ?>

<BR>
<A NAME="inbound_dids-filter_action">
<BR>
<B><?php echo _QXZ("Filter Action"); ?> -</B><?php echo _QXZ("If Filter Inbound Number is activated and a match is found then this is the action that is to be taken. This is the same as the Route that you select for a DID, and the settings below function just like they do for a standard routing."); ?>

<BR>
<A NAME="inbound_dids-custom_one">
<BR>
<B><?php echo _QXZ("Custom DID Fields"); ?> -</B><?php echo _QXZ("These five fields can be used for various purposes, mostly relating to custom programming and reports."); ?>

<BR>
<A NAME="did_ra_extensions">
<BR>
<B><?php echo _QXZ("DID Remote Agent Extension Overrides"); ?> -</B><?php echo _QXZ("<BR>This section allows you to enable DIDs to have extension overrides for remote agent routed calls through in-groups. The User Start must be a valid Remote Agent User Start or if you want the Extension Override entry to work for all calls then you can use ---ALL--- in the User Start field. If there are multiple entries for the same DID and User Start then the active entries will be used in a round robin method."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("CALL MENU TABLE"); ?></FONT></B><BR><BR>
<A NAME="call_menu-menu_id">
<BR>
<B><?php echo _QXZ("Menu ID"); ?> -</B><?php echo _QXZ("This is the ID for this step of the call menu. This will also show up as the context that is used in the dialplan for this call menu. Here is a list of reserved phrases that cannot be used as menu IDs: vicidial, vicidial-auto, general, globals, default, trunkinbound, loopback-no-log, monitor_exit, monitor."); ?>

<BR>
<A NAME="call_menu-menu_name">
<BR>
<B><?php echo _QXZ("Menu Name"); ?> -</B><?php echo _QXZ("This field is the descriptive name for the call menu."); ?>

<BR>
<A NAME="call_menu-menu_prompt">
<BR>
<B><?php echo _QXZ("Menu Prompt"); ?> -</B><?php echo _QXZ("This field contains the file name of the audio prompt to play at the beginning of this menu. You can enter multiple prompts in this field and the other prompt fields by separating them with a pipe character. You can add NOINT directly in front of an audio file name to make it so the playback cannot be interrupted with a key press by the caller, the NOINT should not be a part of the filename, it is a special flag for the system. You may also use special purpose .agi scripts in this field as well like the cm_date.agi script, discuss with your administrator for more details."); ?>

<BR>
<A NAME="call_menu-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="call_menu-menu_timeout">
<BR>
<B><?php echo _QXZ("Menu Timeout"); ?> -</B><?php echo _QXZ("This field is where you set the timeout in seconds that the menu will wait for the caller to enter in a DTMF choice. Setting this field to zero 0 will mean that there will be no wait time after the prompt is played."); ?>

<BR>
<A NAME="call_menu-menu_timeout_prompt">
<BR>
<B><?php echo _QXZ("Menu Timeout Prompt"); ?> -</B><?php echo _QXZ("This field contains the file name of the audio prompt to play when the timeout has been reached. Default is NONE to play no audio at timeout."); ?>

<BR>
<A NAME="call_menu-menu_invalid_prompt">
<BR>
<B><?php echo _QXZ("Menu Invalid Prompt"); ?> -</B><?php echo _QXZ("This field contains the file name of the audio prompt to play when the caller has selected an invalid option. Default is NONE to play no audio at invalid."); ?>

<BR>
<A NAME="call_menu-menu_repeat">
<BR>
<B><?php echo _QXZ("Menu Repeat"); ?> -</B><?php echo _QXZ("This field is where you define the number of times that the menu will play after the first time if no valid choice is made by the caller. Default is 1 to repeat the menu once."); ?>

<BR>
<A NAME="call_menu-menu_time_check">
<BR>
<B><?php echo _QXZ("Menu Time Check"); ?> -</B><?php echo _QXZ("This field is where you can select whether to restrict the Call Menu access to the specific hours set up in the selected Call Time. If the Call Time is blank, this setting will be ignored. Default is 0 for disabled."); ?>

<BR>
<A NAME="call_menu-call_time_id">
<BR>
<B><?php echo _QXZ("Call Time ID"); ?> -</B><?php echo _QXZ("This is the Call Time ID that will be used to restrict calling times if the Menu Time Check option is enabled."); ?>

<BR>
<A NAME="call_menu-track_in_vdac">
<BR>
<B><?php echo _QXZ("Track Calls in Real-Time Report"); ?> -</B><?php echo _QXZ("This field is where you can select whether you want the call to be tracked in the Real-time screen as an incoming IVR type call. Default is 1 for active."); ?>

<BR>
<A NAME="call_menu-tracking_group">
<BR>
<B><?php echo _QXZ("Tracking Group"); ?> -</B><?php echo _QXZ("This is the ID that you can use to track calls to this Call Menu when looking at the IVR Report. The list includes CALLMENU as the default as well as all of the In-Groups."); ?>

<BR>
<A NAME="call_menu-dtmf_log">
<BR>
<B><?php echo _QXZ("Log Key Press"); ?> -</B><?php echo _QXZ("This option if enabled will log the DTMF key press by the caller in this Call Menu. Default is 0 for disabled."); ?>

<BR>
<A NAME="call_menu-dtmf_field">
<BR>
<B><?php echo _QXZ("Log Field"); ?> -</B><?php echo _QXZ("If the Log Key Press option is enabled, this optional setting can allow the response to also be stored in this list field. vendor_lead_code, source_id, phone_code, title, first_name, middle_initial, last_name, address1, address2, address3, city, state, province, postal_code, country_code, alt_phone, email, security_phrase, comments, rank, owner, status, user. Default is NONE for disabled."); ?>

<BR>
<A NAME="call_menu-alt_dtmf_log">
<BR>
<B><?php echo _QXZ("Alt DTMF Log"); ?> -</B><?php echo _QXZ("This option if enabled will log the DTMF key press by the caller in this Call Menu to a separate database table. Default is 0 for disabled."); ?>

<BR>
<A NAME="call_menu-question">
<BR>
<B><?php echo _QXZ("Question"); ?> -</B><?php echo _QXZ("If the Alt DTMF Log option is enabled, this is the question number that the response will be logged to. Default is blank for disabled."); ?>

<BR>
<A NAME="call_menu-option_value">
<BR>
<B><?php echo _QXZ("Option Value"); ?> -</B><?php echo _QXZ("This field is where you define the menu option, possible choices are: 0,1,2,3,4,5,6,7,8,9,*,#,A,B,C,D,TIMECHECK. The special option TIMECHECK can be used only if you have Menu Time Check enabled and there is a Call Time defined for the Menu. To delete an Option, just set the Route to REMOVE and the option will be deleted when you click the SUBMIT button. TIMEOUT will allow you to set what happens to the call when it times out with no input from the caller. INVALID will allow you to set what happens when the caller enters an invalid option. INVALID_2ND and 3RD can only be active if INVALID is not used, it will wait until the second or third invalid entry by the caller before it executes the option."); ?>

<BR>
<A NAME="call_menu-option_description">
<BR>
<B><?php echo _QXZ("Option Description"); ?> -</B><?php echo _QXZ("This field is where you can describe the option, this description will be put into the dialplan as a comment above the option."); ?>

<BR>
<A NAME="call_menu-option_route">
<BR>
<B><?php echo _QXZ("Option Route"); ?> -</B><?php echo _QXZ("This menu contains the options for where to send the call if this option is selected: CALLMENU,INGROUP,DID,HANGUP,EXTENSION,PHONE. For CALLMENU, the Route Value should be the Menu ID of the Call Menu that you want the call sent to. For INGROUP, the In-Group that you want the call to be sent to needs to be selected as well as the other 5 options that need to be set to properly route a call to an Inbound Group. For DID, the Route Value needs to be the DID pattern that you want to send the call to. For HANGUP, the Route Value can be the name of an audio file to play before hanging up the call. For EXTENSION, the Route Value needs to be the dialplan extension you want to send the call to, and the Route Value Context is the context that extension is located in, if left blank the context will default to default. For PHONE, the Route Value needs to be the phone login value for the phones entry that you want to send the call to. For VOICEMAIL, the Route Value needs to be the voicemail box number, the unavailable message will be played. For AGI, the Route Value needs to be the agi script and any values that need to be passed to it. VMAIL_NO_INST will send the call to the voicemail box that you have defined below and will not play instructions after the voicemail message."); ?>

<BR>
<A NAME="call_menu-option_route_value">
<BR>
<B><?php echo _QXZ("Option Route Value"); ?> -</B><?php echo _QXZ("This field is where you enter the value that defines where in the selected Option Route that the call is to be directed to."); ?>

<BR>
<A NAME="call_menu-option_route_value_context">
<BR>
<B><?php echo _QXZ("Option Route Value Context"); ?> -</B><?php echo _QXZ("This field is optional and only used for EXTENSION Option Routes."); ?>

<BR>
<A NAME="call_menu-ingroup_settings">
<BR>
<B><?php echo _QXZ("Call Menu In-Group Settings"); ?> -</B><?php echo _QXZ("If the route is set to INGROUP then there are many options that you can set to define how the call is sent to into the queue. In-Group is the inbound group that you want the call to go to. Handle Method is the way you want the call to be handled,"); ?> <a href="#inbound_dids-call_handle_method"><?php echo _QXZ("Click here to see a list of the available handle methods"); ?></a>. <?php echo _QXZ("Search Method defines how the queue will find the next agent, recommend leave this on LB. List ID is the list that the new lead is inserted into, also if the Method is not a LOOKUP method and the lead is not found. Campaign ID is the campaign to search lists through if one of the RC methods is used. Phone Code is the phone_code field entry for the lead that is inserted with. VID Enter Filename is used if the Method is set to one of the VIDPROMPT methods, it is the audio prompt played to ask the customer to enter their ID. VID ID Number Filename is used if the Method is set to one of the VIDPROMPT methods, it is the audio prompt played after customer enters their ID, something like YOU HAVE ENTERED. VID Confirm Filename is used if the Method is set to one of the VIDPROMPT methods, it is the audio prompt played to confirm their ID, something like PRESS 1 TO CONFIRM AND 2 TO REENTER. VID Digits is used if the Method is set to one of the VIDPROMPT methods, if it is set to a number it is the number of digits that must be entered by the customer when prompted for their ID, if set to empty or X then the customer will have to press pound or hash to finish their entry of their ID.") . ' ' . _QXZ("If you are using the cm_phonesearch.agi in this Call Menu, you can set the B option to use the special DYNAMIC_INGROUP_VAR in-group for that script to work properly."); ?>

<BR>
<A NAME="call_menu-custom_dialplan_entry">
<BR>
<B><?php echo _QXZ("Custom Dialplan Entry"); ?> -</B><?php echo _QXZ("This field allows you to enter in any dialplan elements that you want for the Call Menu."); ?>

<BR>
<A NAME="call_menu-qualify_sql">
<BR>
<B><?php echo _QXZ("Qualify SQL"); ?> -</B><?php echo _QXZ("This field allows you to input SQL - Structured Query Language - database fragments, like with Filters, to determine whether this call menu should play for the caller or not. This feature only works if the call has the callerIDname set prior to being sent to this call menu, either as an outbound survey transfer, or through the use of a drop call menu for an In-Group call. If there is a match, the call will proceed as normal. If there is no match, the call will go to the D option or the invalid option if no D option is set. You cannot use single-quotes in this field, only double-quotes if they are needed. Default is empty for disabled."); ?>





<BR><BR><BR><BR>

<BR>
<A NAME="filter_phone_groups-filter_phone_group_id">
<BR>
<B><?php echo _QXZ("Filter Phone Group ID"); ?> -</B><?php echo _QXZ("This is the ID of the Filter Phone Group that is the container for a group of phone numbers that you can have automatically searched through when a call comes into a DID and send to an alternate route if there is a match. This field should be between 2 and 20 characters and have no punctuation except for underscore."); ?>

<BR>
<A NAME="filter_phone_groups-filter_phone_group_name">
<BR>
<B><?php echo _QXZ("Filter Phone Group Name"); ?> -</B><?php echo _QXZ("This is the name of the Filter Phone Group and is displayed with the ID in select lists where this feature is used. This field should be between 2 and 40 characters."); ?>

<BR>
<A NAME="filter_phone_groups-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="filter_phone_groups-filter_phone_group_description">
<BR>
<B><?php echo _QXZ("Filter Phone Group Description"); ?> -</B><?php echo _QXZ("This is the description of the Filter Phone Group, it is purely for notation purposes only and is not a required field."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>REMOTE_AGENTS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="remote_agents-user_start">
<BR>
<B><?php echo _QXZ("User ID Start"); ?> -</B><?php echo _QXZ("This is the starting User ID that is used when the remote agent entries are inserted into the system. If the Number of Lines is set higher than 1, this number is incremented by one until each line has an entry. Make sure you create a new user account with a user level of 4 or great if you want them to be able to use the vdremote.php page for remote web access of this account."); ?>

<BR>
<A NAME="remote_agents-number_of_lines">
<BR>
<B><?php echo _QXZ("Number of Lines"); ?> -</B><?php echo _QXZ("This defines how many remote agent entries the system creates, and determines how many lines it thinks it can safely send to the number below."); ?>

<BR>
<A NAME="remote_agents-server_ip">
<BR>
<B><?php echo _QXZ("Server IP"); ?> -</B><?php echo _QXZ("A remote agent entry is only good for one specific server, here is where you select which server you want."); ?>

<BR>
<A NAME="remote_agents-conf_exten">
<BR>
<B><?php echo _QXZ("External Extension"); ?> -</B><?php echo _QXZ("This is the number that you want the calls forwarded to. Make sure that it is a full dial plan number and that if you need a 9 at the beginning you put it in here. Test by dialing this number from a phone on the system."); ?>

<BR>
<A NAME="remote_agents-extension_group">
<BR>
<B><?php echo _QXZ("Extension Group"); ?> -</B><?php echo _QXZ("If set to something other than NONE or empty this will override the External Extension field and use the Extension Group entries that have the same extension group ID. Default is NONE for deactivated."); ?>

<BR>
<A NAME="remote_agents-status">
<BR>
<B><?php echo _QXZ("Status"); ?> -</B><?php echo _QXZ("Here is where you turn the remote agent on and off. As soon as the agent is Active the system assumes that it can send calls to it. It may take up to 30 seconds once you change the status to Inactive to stop receiving calls."); ?>

<BR>
<A NAME="remote_agents-campaign_id">
<BR>
<B><?php echo _QXZ("Campaign"); ?> -</B><?php echo _QXZ("Here is where you select the campaign that these remote agents will be logged into. Inbound needs to use the CLOSER campaign and select the inbound campaigns below that you want to receive calls from."); ?>

<BR>
<A NAME="remote_agents-on_hook_agent">
<BR>
<B><?php echo _QXZ("On-Hook Agent"); ?> -</B><?php echo _QXZ("This option is only used for inbound calls going to this remote agent. This feature will call the remote agent and will not send the customer to the remote agent until the line is answered. Default is N for disabled."); ?>

<BR>
<A NAME="remote_agents-on_hook_ring_time">
<BR>
<B><?php echo _QXZ("On-Hook Ring Time"); ?> -</B><?php echo _QXZ("This option is only used when the On-Hook Agent field above is set to Y and then only for inbound calls coming to this remote agent. This is the number of seconds that each call attempt will ring to try to get an answer. It is recommended that you set this to a few seconds less than it takes for a call to be sent to voicemail. Default is 15."); ?>

<BR>
<A NAME="remote_agents-closer_campaigns">
<BR>
<B><?php echo _QXZ("Inbound Groups"); ?> -</B><?php echo _QXZ("Here is where you select the inbound groups you want to receive calls from if you have selected the CLOSER campaign."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>EXTENSION_GROUPS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="extension_groups-extension_group_id">
<BR>
<B><?php echo _QXZ("Extension Group"); ?> -</B><?php echo _QXZ("This required field is where you enter the group ID that you want this extension to be put into. No spaces or special characters except for underscore letters and numbers."); ?>

<BR>
<A NAME="extension_groups-extension">
<BR>
<B><?php echo _QXZ("Extension"); ?> -</B><?php echo _QXZ("This required field is where you put the dialplan extension that you want the remote agent calls to be sent to for this extension group entry."); ?>

<BR>
<A NAME="extension_groups-rank">
<BR>
<B><?php echo _QXZ("Rank"); ?> -</B><?php echo _QXZ("This field allows you to rank the extension group entries that share the same extension group. Default is 0."); ?>

<BR>
<A NAME="extension_groups-campaign_groups">
<BR>
<B><?php echo _QXZ("Campaigns Groups"); ?> -</B><?php echo _QXZ("In this field you can put a list of campaign IDs and or inbound group IDs that you want to restrict the use of the extension group for. List must be separate by pipes and have a pipe at the beginning and end of the string.
"); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>CAMPAIGN_LISTS</FONT></B><BR><BR>
<A NAME="campaign_lists">
<BR>
<B><?php echo _QXZ("The lists within this campaign are listed here, whether they are active is denoted by the Y or N and you can go to the list screen by clicking on the list ID in the first column.")."</B>"; ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>CAMPAIGN_STATUSES <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="campaign_statuses">
<BR>
<B><?php echo _QXZ("Through the use of custom campaign statuses, you can have statuses that only exist for a specific campaign. The Status must be 1-8 characters in length, the description must be 2-30 characters in length and Selectable defines whether it shows up in the system as a disposition. The human_answered field is used when calculating the drop percentage, or abandon rate. Setting human_answered to Y will use this status when counting the human-answered calls. The Category option allows you to group several statuses into a category that can be used for statistical analysis. There are also 7 additional settings that will define the kind of status: sale, dnc, customer contact, not interested, unworkable, scheduled callback, completed. The MIN SEC and MAX SEC fields for each status will determine whether an agent can select that status at the end of their call based upon the length of the call. If the call is 10 seconds and the MIN SEC for a status is set to 20 seconds, then the agent will not be able to select that status. Also, if a call is 40 seconds and the MAX SEC for a status is set to 30 seconds, then the agent will not be able to select that status.")."</B>";





if ($SSoutbound_autodial_active > 0)
	{
	?>
	<BR><BR><BR><BR>

	<B><FONT SIZE=3>CAMPAIGN_HOTKEYS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
	<A NAME="campaign_hotkeys">
	<BR>
	<B><?php echo _QXZ("Through the use of custom campaign hot keys, agents that use the agent web-client can hang up and disposition calls just by pressing a single key on their keyboard. There are two special HotKey options that you can use in conjunction with Alternate Phone number dialing, ALTPH2 - Alternate Phone Hot Dial and ADDR3-----Address3 Hot Dial allow an agent to use a hotkey to hang up their call, stay on the same lead, and dial another contact number from that lead. You can also use LTMG or XFTAMM as statuses to trigger an automatic transfer to the Leave-Voicemail option."); ?></B>





	<BR><BR><BR><BR>

	<B><FONT SIZE=3>LEAD_RECYCLE <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
	<A NAME="lead_recycle">
	<BR>
	<B><?php echo _QXZ("Through the use of lead recycling, you can call specific statuses of leads again at a specified interval without resetting the entire list. Lead recycling is campaign-specific and does not have to be a selected dialable status in your campaign. The attempt delay field is the number of seconds until the lead can be placed back in the hopper, this number must be at least 120 seconds. The attempt maximum field is the maximum number of times that a lead of this status can be attempted before the list needs to be reset, this number can be from 1 to 10. You can activate and deactivate a lead recycle entry with the provided links."); ?></B>





	<BR><BR><BR><BR>

	<B><FONT SIZE=3><?php echo _QXZ("AUTO ALT DIAL STATUSES"); ?></FONT></B><BR><BR>
	<A NAME="auto_alt_dial_statuses">
	<BR>
	<B><?php echo _QXZ("If the Auto Alt-Number Dialing field is set, then the leads that are dispositioned under these auto alt dial statuses will have their alt_phone and-or address3 fields dialed after any of these no-answer statuses are set."); ?></B>

	<?php
	}
?>



<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("AGENT PAUSE CODES"); ?></FONT></B><BR><BR>
<A NAME="pause_codes">
<BR>
<B><?php echo _QXZ("If the Agent Pause Codes Active field is set to active then the agents will be able to select from these pause codes when they click on the PAUSE button on their screens. This data is then stored in the agent log. The Pause code must contain only letters and numbers and be less than 7 characters long. The pause code name can be no longer than 30 characters.") . ' ' . _QXZ("The Time Limit field, if enabled in System Settings, will change the color of the agent on the Real-Time Report if they are in that pause code for more than the defined amount of seconds.") . ' ' . _QXZ("The Mgr Approval field, if enabled, will require a manager to go to the agent screen and enter their login credentials to allow the agent to enter that pause code."); ?></B>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("CAMPAIGN PRESETS"); ?></FONT></B><BR><BR>
<A NAME="xfer_presets">
<BR>
<B><?php echo _QXZ("If the Campaign setting for presets is set to ENABLED then you have the ability to define Transfer-Conference presets that will be available to the agent allowing them to 3-way call these presets or blind transfer calls to these preset numbers. These presets also have an option to hide the number associated with each preset from the agent."); ?></B>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("CAMPAIGN CID AREACODES"); ?></FONT></B><BR><BR>
<A NAME="campaign_cid_areacodes">
<BR>
<B><?php echo _QXZ("If the System Setting for Areacode CIDs is enabled and the Campaign setting for Use Custom CallerID is set to AREACODE then you have the ability to define Areacode CIDs that will be used when outbound calling to leads in this specific campaign. You can add multiple callerIDs per areacode and you can activate and deactivate them each in real time. If more than one callerID is active for a specific areacode then the system will use the callerid that has been used the least number of times today. If no callerIDs are active for the areacode then the campaign CallerID or list override CallerID will be used. An areacode in this section can be from 2 to 5 digits in length, and if a shorter defined areacode overlaps with a longer areacode then the longer areacode will be used. For example, if the areacodes 31 and 312 are both defined and active for a campaign, then areacode 312 would be used for phone number 3125551212."); ?></B>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("CID GROUPS"); ?></FONT></B><BR><BR>
<A NAME="cid_groups">
<BR>
<B><?php echo _QXZ("CID Groups are very similar to Campaign AC-CID, except with CID Groups you can use the same set of CIDs across multiple campaigns, and you can also define CID Groups on a per-state basis as well as per-areacode, instead of just by areacode like with Campaign AC-CID. If the STATE type is used, then the STATE value should match the state field for a lead, and if no match is found, the dialer will use the campaign CID as a default."); ?></B>





<BR><BR><BR><BR>

<B><FONT SIZE=3>USER_GROUPS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="user_groups-user_group">
<BR>
<B><?php echo _QXZ("User Group"); ?> -</B><?php echo _QXZ("This is the short name of a User group, try not to use any spaces or punctuation for this field. max 20 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="user_groups-group_name">
<BR>
<B><?php echo _QXZ("Group Name"); ?> -</B><?php echo _QXZ("This is the description of the user group max of 40 characters."); ?>

<BR>
<A NAME="user_groups-forced_timeclock_login">
<BR>
<B><?php echo _QXZ("Force Timeclock Login"); ?> -</B><?php echo _QXZ("This option allows you to not let an agent log in to the agent interface if they have not logged into the timeclock. Default is N. There is an option to exempt admin users, levels 8 and 9."); ?>

<BR>
<A NAME="user_groups-shift_enforcement">
<BR>
<B><?php echo _QXZ("Shift Enforcement"); ?> -</B><?php echo _QXZ("This setting allows you to restrict agent logins based upon the shifts that are selected below. OFF will not enforce shifts at all. START will only enforce the login time but will not affect an agent that is running over their shift time if they are already logged in. ALL will enforce shift start time and will log an agent out after they run over the end of their shift time. Default is OFF."); ?>

<BR>
<A NAME="user_groups-group_shifts">
<BR>
<B><?php echo _QXZ("Group Shifts"); ?> -</B><?php echo _QXZ("This is a selectable list of shifts that can restrict the agents login time on the system."); ?>

<BR>
<A NAME="user_groups-allowed_campaigns">
<BR>
<B><?php echo _QXZ("Allowed Campaigns"); ?> -</B><?php echo _QXZ("This is a selectable list of Campaigns to which members of this user group can log in to. The ALL-CAMPAIGNS option allows the users in this group to see and log in to any campaign on the system."); ?>

<BR>
<A NAME="user_groups-agent_status_viewable_groups">
<BR>
<B><?php echo _QXZ("Agent Status Viewable Groups"); ?> -</B><?php echo _QXZ("This is a selectable list of User Groups and user functions to which members of this user group can view the status of as well as transfer calls to inside of the agent screen. The ALL-GROUPS option allows the users in this group to see and transfer calls to any user on the system. The CAMPAIGN-AGENTS option allows users in this group to see and transfer calls to any user in the campaign that they are logged into. The NOT-LOGGED-IN-AGENTS option allows all users in the system to be displayed, even if they are not logged-in currently."); ?>

<BR>
<A NAME="user_groups-agent_status_view_time">
<BR>
<B><?php echo _QXZ("Agent Status View Time"); ?> -</B><?php echo _QXZ("This option defines whether the agent will see the amount of time that users in their agent sidebar have been in their current status. Default is N for no or disabled."); ?>

<BR>
<A NAME="user_groups-agent_call_log_view">
<BR>
<B><?php echo _QXZ("Agent Call Log View"); ?> -</B><?php echo _QXZ("This option defines whether the agent will be able to see their call log for calls handled through the agent screen. Default is N for no or disabled."); ?>

<BR>
<A NAME="user_groups-agent_xfer_options">
<BR>
<B><?php echo _QXZ("Agent Transfer Options"); ?> -</B><?php echo _QXZ("These options allow for the disabling of specific buttons in the Transfer Conference section of the Agent interface. Default is Y for yes or enabled."); ?>

<BR>
<A NAME="user_groups-agent_fullscreen">
<BR>
<B><?php echo _QXZ("Agent Fullscreen"); ?> -</B><?php echo _QXZ("This option if set to Y will set the height and width of the agent screen to the size of the web browser window without any allowance for the Agents View, Calls in Queue View or Calls in Session view. Default is N for no or disabled."); ?>

<BR>
<A NAME="user_groups-agent_allowed_chat_groups">
<BR>
<B><?php echo _QXZ("Agent Allowed Chat Groups"); ?> -</B><?php echo _QXZ("This is a selectable list of User Groups and user functions to which members of this user group can view the status of as well as transfer calls to inside of the agent screen. The ALL-GROUPS option allows the users in this group to see and transfer calls to any user on initiate an internal chat to within the agent screen. The CAMPAIGN-AGENTS option allows users in this group to see and start chats with any user in the campaign that they are logged into."); ?>

<BR>
<A NAME="user_groups-allowed_reports">
<BR>
<B><?php echo _QXZ("Allowed Reports"); ?> -</B><?php echo _QXZ("If a user in this group is set to user level 7 or higher, then this feature can be used to restrict the reports that the users can view. Default is ALL. If you want to select more than one report then press the Ctrl key on your keyboard as you select the reports."); ?>

<BR>
<A NAME="user_groups-allowed_custom_reports">
<BR>
<B><?php echo _QXZ("Allowed Custom Reports"); ?> -</B><?php echo _QXZ("If a user in this group is set to user level 7 or higher, then this feature can be used to restrict the custom reports that the users can view. Access is determined as reports are added from the custom reports admin page, for example, ALL is not the default.  If you want to select more than one report then press the Ctrl key on your keyboard as you select the reports."); ?>

<BR>
<A NAME="user_groups-admin_ip_list">
<BR>
<B><?php echo _QXZ("Admin IP Whitelist"); ?> -</B><?php echo _QXZ("If enabled, this will restrict administration web screen use to only be allowed from the selected list of IP Addresses. Default is DISABLED."); ?>

<BR>
<A NAME="user_groups-agent_ip_list">
<BR>
<B><?php echo _QXZ("Agent IP Whitelist"); ?> -</B><?php echo _QXZ("If enabled, this will restrict agent web screen use to only be allowed from the selected list of IP Addresses. Default is DISABLED."); ?>

<BR>
<A NAME="user_groups-api_ip_list">
<BR>
<B><?php echo _QXZ("API IP Whitelist"); ?> -</B><?php echo _QXZ("If enabled, this will restrict API use to only be allowed from the selected list of IP Addresses. Please note that some API functions need to be done from the servers themselves, so if you enable this, you may need to add internal server IP Addresses for those functions to continue to work. Default is DISABLED."); ?>

<BR>
<A NAME="user_groups-admin_viewable_groups">
<BR>
<B><?php echo _QXZ("Allowed User Groups"); ?> -</B><?php echo _QXZ("This is a selectable list of User Groups to which members of this user group can view and possibly edit. User Groups can restrict access to almost all aspects of the system, from inbound DIDs to phones to voicemail boxes. The --ALL-- option allows the users in this group to see and log in to any record on the system if their user permissions allow for it."); ?>

<BR>
<A NAME="user_groups-admin_viewable_call_times">
<BR>
<B><?php echo _QXZ("Allowed Call Times"); ?> -</B><?php echo _QXZ("This is a selectable list of Call Times to which members of this user group can use in campaigns, in-groups and call menus. The --ALL-- option allows the users in this group to use all call times in the system."); ?>


<?php
if (strlen($SSwebphone_url) > 5)
	{
	?>
	<BR>
	<A NAME="user_groups-webphone_url_override">
	<BR>
	<B><?php echo _QXZ("Webphone URL Override"); ?> -</B><?php echo _QXZ("This setting allows you to set an alternate webphone URL just for the members of one user group. Default is empty."); ?>

	<BR>
	<A NAME="user_groups-webphone_systemkey_override">
	<BR>
	<B><?php echo _QXZ("Webphone System Key Override"); ?> -</B><?php echo _QXZ("This setting allows you to set an alternate webphone System Key just for the members of one user group. Default is empty."); ?>

	<BR>
	<A NAME="user_groups-webphone_dialpad_override">
	<BR>
	<B><?php echo _QXZ("Webphone Dialpad Override"); ?> -</B><?php echo _QXZ("This setting allows you to activate or deactivate the dialpad on the webphone just for the members of one user group. Default is DISABLED. TOGGLE will allow the user to view and hide the dialpad by clicking a link. TOGGLE_OFF will default to not show the dialpad on first load, but will allow the user to show the dialpad by clicking on the dialpad link."); ?>

	<BR>
	<A NAME="user_groups-webphone_layout">
	<BR>
	<B><?php echo _QXZ("Webphone Layout Override"); ?> - </B><?php echo _QXZ("For the WebRTC phone, this setting will allow you to use an alternate layout for all users within this user group, overriding whatever is set in the phone webphone layout field. Default is blank."); ?>

	<?php
	}

if ($SSqc_features_active > 0)
	{
	?>
	<BR>
	<A NAME="user_groups-qc_allowed_campaigns">
	<BR>
	<B><?php echo _QXZ("QC Allowed Campaigns"); ?> -</B><?php echo _QXZ("This is a selectable list of Campaigns which members of this user group will be able to QC. The ALL-CAMPAIGNS option allows the users in this group to QC any campaign on the system."); ?>

	<BR>
	<A NAME="user_groups-qc_allowed_inbound_groups">
	<BR>
	<B><?php echo _QXZ("QC Allowed Inbound Groups"); ?> -</B><?php echo _QXZ("This is a selectable list of Inbound Groups which members of this user group will be able to QC. The ALL-GROUPS option allows the users in this user group to QC any inbound group on the system."); ?>
	<?php
	}
?>




<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("SCRIPTS TABLE"); ?></FONT></B><BR><BR>
<A NAME="scripts-script_id">
<BR>
<B><?php echo _QXZ("Script ID"); ?> -</B><?php echo _QXZ("This is the short name of a Script. This needs to be a unique identifier. Try not to use any spaces or punctuation for this field. max 20 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="scripts-script_name">
<B><?php echo _QXZ("Script Name"); ?> -</B><?php echo _QXZ("This is the title of a Script. This is a short summary of the script. max 50 characters, minimum of 2 characters. There should be no spaces or punctuation of any kind in this field."); ?>

<BR>
<A NAME="scripts-script_comments">
<B><?php echo _QXZ("Script Comments"); ?> -</B><?php echo _QXZ("This is where you can place comments for an agent screen Script such as -changed to free upgrade on Sept 23-.  max 255 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="scripts-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="scripts-script_text">
<B><?php echo _QXZ("Script Text"); ?> -</B><?php echo _QXZ("This is where you place the content of an agent screen Script. Minimum of 2 characters. You can have customer information be auto-populated in this script using --A--field--B-- where field is one of the following fieldnames: vendor_lead_code, source_id, list_id, gmt_offset_now, called_since_last_reset, phone_code, phone_number, title, first_name, middle_initial, last_name, address1, address2, address3, city, state, province, postal_code, country_code, gender, date_of_birth, alt_phone, email, security_phrase, comments, lead_id, campaign, phone_login, group, channel_group, SQLdate, epoch, uniqueid, customer_zap_channel, server_ip, SIPexten, session_id, dialed_number, dialed_label, rank, owner, camp_script, in_script, script_width, script_height, recording_filename, recording_id, user_custom_one, user_custom_two, user_custom_three, user_custom_four, user_custom_five, preset_number_a, preset_number_b, preset_number_c, preset_number_d, preset_number_e, preset_number_f, preset_dtmf_a, preset_dtmf_b, did_id, did_extension, did_pattern, did_description, closecallid, xfercallid, agent_log_id, entry_list_id, call_id, user_group, called_count, TABLEper_call_notes, MANUALDIALLINK, EMAILinbound_message. For example, this sentence would print the persons name in it----<BR><BR>  Hello, can I speak with --A--first_name--B-- --A--last_name--B-- please? Well hello --A--title--B-- --A--last_name--B-- how are you today?<BR><BR> This would read----<BR><BR>Hello, can I speak with John Doe please? Well hello Mr. Doe how are you today?<BR><BR> You can also use an iframe to load a separate window within the SCRIPT tab, here is an example with prepopulated variables:"); ?>

<DIV style="height:200px;width:400px;background:white;overflow:scroll;font-size:12px;font-family:sans-serif;" id=iframe_example>
&#60;iframe src="http://www.sample.net/test_output.php?lead_id=--A--lead_id--B--&#38;vendor_id=--A--vendor_lead_code--B--&#38;list_id=--A--list_id--B--&#38;gmt_offset_now=--A--gmt_offset_now--B--&#38;phone_code=--A--phone_code--B--&#38;phone_number=--A--phone_number--B--&#38;title=--A--title--B--&#38;first_name=--A--first_name--B--&#38;middle_initial=--A--middle_initial--B--&#38;last_name=--A--last_name--B--&#38;address1=--A--address1--B--&#38;address2=--A--address2--B--&#38;address3=--A--address3--B--&#38;city=--A--city--B--&#38;state=--A--state--B--&#38;province=--A--province--B--&#38;postal_code=--A--postal_code--B--&#38;country_code=--A--country_code--B--&#38;gender=--A--gender--B--&#38;date_of_birth=--A--date_of_birth--B--&#38;alt_phone=--A--alt_phone--B--&#38;email=--A--email--B--&#38;security_phrase=--A--security_phrase--B--&#38;comments=--A--comments--B--&#38;user=--A--user--B--&#38;campaign=--A--campaign--B--&#38;phone_login=--A--phone_login--B--&#38;fronter=--A--fronter--B--&#38;closer=--A--user--B--&#38;group=--A--group--B--&#38;channel_group=--A--group--B--&#38;SQLdate=--A--SQLdate--B--&#38;epoch=--A--epoch--B--&#38;uniqueid=--A--uniqueid--B--&#38;customer_zap_channel=--A--customer_zap_channel--B--&#38;server_ip=--A--server_ip--B--&#38;SIPexten=--A--SIPexten--B--&#38;session_id=--A--session_id--B--&#38;dialed_number=--A--dialed_number--B--&#38;dialed_label=--A--dialed_label--B--&#38;rank=--A--rank--B--&#38;owner=--A--owner--B--&#38;phone=--A--phone--B--&#38;camp_script=--A--camp_script--B--&#38;in_script=--A--in_script--B--&#38;script_width=--A--script_width--B--&#38;script_height=--A--script_height--B--&#38;recording_filename=--A--recording_filename--B--&#38;recording_id=--A--recording_id--B--&#38;user_custom_one=--A--user_custom_one--B--&#38;user_custom_two=--A--user_custom_two--B--&#38;user_custom_three=--A--user_custom_three--B--&#38;user_custom_four=--A--user_custom_four--B--&#38;user_custom_five=--A--user_custom_five--B--&#38;preset_number_a=--A--preset_number_a--B--&#38;preset_number_b=--A--preset_number_b--B--&#38;preset_number_c=--A--preset_number_c--B--&#38;preset_number_d=--A--preset_number_d--B--&#38;preset_number_e=--A--preset_number_e--B--&#38;preset_number_f=--A--preset_number_f--B--&#38;preset_dtmf_a=--A--preset_dtmf_a--B--&#38;preset_dtmf_b=--A--preset_dtmf_b--B--&#38;did_id=--A--did_id--B--&#38;did_extension=--A--did_extension--B--&#38;did_pattern=--A--did_pattern--B--&#38;did_description=--A--did_description--B--&#38;closecallid=--A--closecallid--B--&#38;xfercallid=--A--xfercallid--B--&#38;agent_log_id=--A--agent_log_id--B--&#38;entry_list_id=--A--entry_list_id--B--&#38;call_id=--A--call_id--B--&&#38;user_group=--A--user_group--B--&&#38;" style="width:580;height:290;background-color:transparent;" scrolling="auto" frameborder="0" allowtransparency="true" id="popupFrame" name="popupFrame" width="460" height="290" STYLE="z-index:17"&#62;
&#60;/iframe&#62;
</DIV>
<BR>
<?php echo _QXZ("You can also use a special variable IGNORENOSCROLL to force scroll bars on the script tab even if you are using an iframe within it."); ?>

<BR>
<A NAME="scripts-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This determines whether this script can be selected to be used by a campaign."); ?>

<BR>
<A NAME="scripts-script_color">
<BR>
<B><?php echo _QXZ("Script Color"); ?> -</B><?php echo _QXZ("This determines the background color of the script as displayed in the agent screen. default is white.");





if ($SSoutbound_autodial_active > 0)
	{
	?>
	<BR><BR><BR><BR>

	<B><FONT SIZE=3>LEAD_FILTERS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
	<A NAME="lead_filters-lead_filter_id">
	<BR>
	<B><?php echo _QXZ("Filter ID"); ?> -</B><?php echo _QXZ("This is the short name of a Lead Filter. This needs to be a unique identifier. Do not use any spaces or punctuation for this field. max 10 characters, minimum of 2 characters."); ?>

	<BR>
	<A NAME="lead_filters-lead_filter_name">
	<B><?php echo _QXZ("Filter Name"); ?> -</B><?php echo _QXZ("This is a more descriptive name of the Filter. This is a short summary of the filter. max 30 characters, minimum of 2 characters."); ?>

	<BR>
	<A NAME="lead_filters-lead_filter_comments">
	<B><?php echo _QXZ("Filter Comments"); ?> -</B><?php echo _QXZ("This is where you can place comments for a Filter such as -calls all California leads-.  max 255 characters, minimum of 2 characters."); ?>

	<BR>
	<A NAME="lead_filters-user_group">
	<BR>
	<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

	<BR>
	<A NAME="lead_filters-lead_filter_sql">
	<B><?php echo _QXZ("Filter SQL"); ?> -</B><?php echo _QXZ("This is where you place the SQL query fragment that you want to filter by. do not begin or end with an AND, that will be added by the hopper cron script automatically. an example SQL query that would work here is- called_count > 4 and called_count < 8 -.");
	}
?>




<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("CALL TIMES TABLE"); ?></FONT></B><BR><BR>
<A NAME="call_times-call_time_id">
<BR>
<B><?php echo _QXZ("Call Time ID"); ?> -</B><?php echo _QXZ("This is the short name of a Call Time Definition. This needs to be a unique identifier. Do not use any spaces or punctuation for this field. max 10 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="call_times-call_time_name">
<B><?php echo _QXZ("Call Time Name"); ?> -</B><?php echo _QXZ("This is a more descriptive name of the Call Time Definition. This is a short summary of the Call Time definition. max 30 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="call_times-call_time_comments">
<B><?php echo _QXZ("Call Time Comments"); ?> -</B><?php echo _QXZ("This is where you can place comments for a Call Time Definition such as -10am to 4pm with extra call state restrictions-.  max 255 characters."); ?>

<BR>
<A NAME="call_times-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="call_times-ct_default_start">
<B><?php echo _QXZ("Default Start and Stop Times"); ?> -</B><?php echo _QXZ("This is the default time that calling will be allowed to be started or stopped within this call time definition if the day-of-the-week start time is not defined. 0 is midnight. To prevent calling completely set this field to 2400 and set the Default Stop time to 2400. To allow calling 24 hours a day set the start time to 0 and the stop time to 2400. For inbound only, you can also set the stop call time higher than 2400 if you want the call time to go beyond midnight. So if you want your call time to run from 6 am until 2 am the next day, you would put 0600 as the start time and 2600 as the stop time."); ?>

<BR>
<A NAME="call_times-ct_sunday_start">
<B><?php echo _QXZ("Weekday Start and Stop Times"); ?> -</B><?php echo _QXZ("These are the custom times per day that can be set for the call time definition. same rules apply as with the Default start and stop times."); ?>

<BR>
<A NAME="call_times-default_afterhours_filename_override">
<B><?php echo _QXZ("After Hours Filename Override"); ?> -</B><?php echo _QXZ("These fields allow you to override the After Hours message for inbound groups if it is set to something. Default is empty."); ?>

<BR>
<A NAME="call_times-ct_state_call_times">
<B><?php echo _QXZ("State Call Time Definitions"); ?> -</B><?php echo _QXZ("This is the list of State specific call time definitions that are followed in this Call Time Definition."); ?>

<BR>
<A NAME="call_times-state_call_time_state">
<B><?php echo _QXZ("State Call Time State"); ?> -</B><?php echo _QXZ("This is the two letter code for the state that this calling time definition is for. For this to be in effect the local call time that is set in the campaign must have this state call time record in it as well as all of the leads having two letter state codes in them."); ?>

<BR>
<A NAME="call_times-holiday_id">
<BR>
<B><?php echo _QXZ("Holiday ID"); ?> -</B><?php echo _QXZ("This is the short name of a Holiday Definition. This needs to be a unique identifier. Do not use any spaces or punctuation for this field. max 30 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="call_times-holiday_name">
<B><?php echo _QXZ("Holiday Name"); ?> -</B><?php echo _QXZ("This is a more descriptive name of the Holiday Definition. This is a short summary of the Holiday definition. max 100 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="call_times-holiday_comments">
<B><?php echo _QXZ("Holiday Comments"); ?> -</B><?php echo _QXZ("This is where you can place comments for a Holiday Definition such as -10am to 4pm boxing day restrictions-.  max 255 characters."); ?>

<BR>
<A NAME="call_times-holiday_date">
<B><?php echo _QXZ("Holiday Date"); ?> -</B><?php echo _QXZ("This is the date of the holiday."); ?>

<BR>
<A NAME="call_times-holiday_status">
<B><?php echo _QXZ("Holiday Status"); ?> -</B><?php echo _QXZ("This is the status of the holiday entry. ACTIVE status means that the holiday will be enabled on the holiday date. INACTIVE status means that the holiday will be ignored even on the holiday date. EXPIRED means that the holiday has passed its holiday date. Default is INACTIVE."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("SHIFTS TABLE"); ?></FONT></B><BR><BR>
<A NAME="shifts-shift_id">
<BR>
<B><?php echo _QXZ("Shift ID"); ?> -</B><?php echo _QXZ("This is the short name of a system Shift Definition. This needs to be a unique identifier. Do not use any spaces or punctuation for this field. max 20 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="shifts-shift_name">
<B><?php echo _QXZ("Shift Name"); ?> -</B><?php echo _QXZ("This is a more descriptive name of the Shift Definition. This is a short summary of the Shift definition. max 50 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="shifts-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="shifts-shift_start_time">
<B><?php echo _QXZ("Shift Start Time"); ?> -</B><?php echo _QXZ("This is the time that the campaign shift begins. Must only be numbers, 9:30 AM would be 0930 and 5:00 PM would be 1700."); ?>

<BR>
<A NAME="shifts-shift_length">
<B><?php echo _QXZ("Shift Length"); ?> -</B><?php echo _QXZ("This is the time in Hours and Minutes that the campaign shift lasts. 8 hours would be 08:00 and 7 hours and 30 minutes would be 07:30."); ?>

<BR>
<A NAME="shifts-shift_weekdays">
<B><?php echo _QXZ("Shift Weekdays"); ?> -</B><?php echo _QXZ("In this section you should choose the days of the week that this shift is active."); ?>

<BR>
<A NAME="shifts-report_option">
<B><?php echo _QXZ("Report Option"); ?> -</B><?php echo _QXZ("This option allows this specific shift to show up in selected reports that support this option."); ?>

<BR>
<A NAME="shifts-report_rank">
<B><?php echo _QXZ("Report Rank"); ?> -</B><?php echo _QXZ("This option allows you to rank shifts in selected reports that support this option."); ?>





<BR><BR><BR><BR>
<A NAME="audio_store">
<B><?php echo _QXZ("Audio Store"); ?> -</B><?php echo _QXZ("This utility allows you to upload audio files to the web server so that they can be distributed to all of the system servers in a multi-server cluster. An important note, only two audio file types will work, .wav files that are PCM Mono 16bit 8k and .gsm files that are 8bit 8k. Please verify that your files are properly formatted before uploading them here."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>MUSIC_ON_HOLD <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="music_on_hold-moh_id">
<BR>
<B><?php echo _QXZ("Music On Hold ID"); ?> -</B><?php echo _QXZ("This is the short name of a Music On Hold entry. This needs to be a unique identifier. Do not use any spaces or punctuation for this field. max 100 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="music_on_hold-moh_name">
<B><?php echo _QXZ("Music On Hold Name"); ?> -</B><?php echo _QXZ("This is a more descriptive name of the Music On Hold entry. This is a short summary of the Music On Hold context and will show as a comment in the musiconhold conf file. max 255 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="music_on_hold-active">
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This option allows you to set the Music On Hold entry to active or inactive. Inactive will remove the entry from the conf files."); ?>

<BR>
<A NAME="music_on_hold-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="music_on_hold-random">
<B><?php echo _QXZ("Random Order"); ?> -</B><?php echo _QXZ("This option allows you to define the playback of the audio files in a random order. If set to N then the defined order will be used."); ?>

<BR>
<A NAME="music_on_hold-filename">
<B><?php echo _QXZ("Filename"); ?> -</B><?php echo _QXZ("To add a new audio file to a Music On Hold entry the file must first be in the audio store, then you can select the file and click submit to add it to the file list. Music on hold is updated once per minute if there have been changes made. Any files not listed in a music on hold entry that are present in the music on hold folder will be deleted."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>TTS_PROMPTS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="tts_prompts-tts_id">
<BR>
<B><?php echo _QXZ("TTS ID"); ?> -</B><?php echo _QXZ("This is the short name of a TTS entry. This needs to be a unique identifier. Do not use any spaces or punctuation for this field. max 50 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="tts_prompts-tts_name">
<B><?php echo _QXZ("TTS Name"); ?> -</B><?php echo _QXZ("This is a more descriptive name of the TTS entry. This is a short summary of the TTS definition. max 100 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="tts_prompts-active">
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This option allows you to set the TTS entry to active or inactive."); ?>

<BR>
<A NAME="tts_prompts-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="tts_prompts-tts_voice">
<B><?php echo _QXZ("TTS Voice"); ?> -</B><?php echo _QXZ("This is where you define the voice to be used in the TTS generation. Default is Allison-8kHz."); ?>

<BR>
<A NAME="tts_prompts-tts_text">
<B><?php echo _QXZ("TTS Text"); ?> -</B><?php echo _QXZ("This is the actual Text To Speech data field that is sent to Cepstral for creation of the audio file to be played to the customer. you can use Speech Synthesis Markup Language -SSML- in this field, for example,"); ?> &lt;break time='1000ms'/&gt; <?php echo _QXZ("for a 1 second break. You can also use several variables such as first name, last name and title as system variables just like you do in a Script: --A--first_name--B--. If you have static audio files that you want to use based upon the value of one of the fields you can use those as well with C and D tags. The file names must be all lower case and they must be 8k 16bit pcm wav files. The field name must be the same but without the .wav in the filename. For example --C----A--address3--B----D-- would first find the value for address3, then it would try to find an audio file matching that value to put it into the prompt. Here is a list of the available variables: lead_id, entry_date, modify_date, status, user, vendor_lead_code, source_id, list_id, phone_number, title, first_name, middle_initial, last_name, address1, address2, address3, city, state, province, postal_code, country_code, gender, date_of_birth, alt_phone, email, security_phrase, comments, called_count, last_local_call_time, rank, owner"); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("VOICEMAIL TABLE"); ?></FONT></B><BR><BR>
<A NAME="voicemail-voicemail_id">
<BR>
<B><?php echo _QXZ("Voicemail ID"); ?> -</B><?php echo _QXZ("This is the all numbers identifier of this mailbox. This must not be a duplicate of an existing voicemail ID or the voicemail ID of a phone on the system, minimum of 2 characters."); ?>

<BR>
<A NAME="voicemail-fullname">
<BR>
<B><?php echo _QXZ("Name"); ?> -</B><?php echo _QXZ("This is name associated with this voicemail box. max 100 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="voicemail-pass">
<BR>
<B><?php echo _QXZ("Password"); ?> -</B><?php echo _QXZ("This is the password that is used to gain access to the voicemail box when dialing in to check messages max 10 characters, minimum of 2 characters."); ?>

<BR>
<A NAME="voicemail-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This option allows you to set the voicemail box to active or inactive. If the box is inactive you cannot leave messages on it and you cannot check messages in it."); ?>

<BR>
<A NAME="voicemail-email">
<BR>
<B><?php echo _QXZ("Email"); ?> -</B><?php echo _QXZ("This optional setting allows you to have the voicemail messages sent to an email account, if your system is set up to send out email. If this field is empty then no emails will be sent out."); ?>

<BR>
<A NAME="voicemail-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="voicemail-delete_vm_after_email">
<BR>
<B><?php echo _QXZ("Delete Voicemail After Email"); ?> -</B><?php echo _QXZ("This optional setting allows you to have the voicemail messages deleted from the system after they have been emailed out. Default is N."); ?>

<BR>
<A NAME="voicemail-show_vm_on_summary">
<BR>
<B><?php echo _QXZ("Show VM on Summary Screen"); ?> -</B><?php echo _QXZ("This option will display this Voicemail Box information on the summary page seen when logging into the administration page. It will show the box name, new message count, old count, and total messages in the box. Note the table will not be shown unless you have set at least one mailbox to Y. Default is N for off."); ?>

<BR>
<A NAME="voicemail-voicemail_greeting">
<BR>
<B><?php echo _QXZ("Voicemail Greeting"); ?> -</B><?php echo _QXZ("This optional setting allows you to define a voicemail greeting audio file from the audio store. Default is blank."); ?>

<BR>
<A NAME="voicemail-voicemail_timezone">
<BR>
<B><?php echo _QXZ("Voicemail Zone"); ?> -</B><?php echo _QXZ("This setting allows you to set the zone that this voicemail box will be set to when the time is logged for a message. Default is set in the System Settings."); ?>

<BR>
<A NAME="voicemail-voicemail_options">
<BR>
<B><?php echo _QXZ("Voicemail Options"); ?> -</B><?php echo _QXZ("This optional setting allows you to define additional voicemail settings. It is recommended that you leave this blank unless you know what you are doing."); ?>

<?php
if ($SSoutbound_autodial_active > 0)
	{
	?>

	<BR><BR><BR><BR>

	<B><FONT SIZE=3><?php echo _QXZ("LIST LOADER FUNCTIONALITY"); ?></FONT></B><BR><BR>
	<A NAME="list_loader">
	<BR>
	<?php echo _QXZ("The basic web-based lead loader is designed simply to take a lead file - up to 8MB in size - that is either tab or pipe delimited and load it into the list table. The lead loader allows for field choosing and TXT- Plain Text, CSV- Comma Separated Values and XLS- Excel file formats. The lead loader does not do data validation, but it does allow you to check for duplicates in itself, within the campaign or within the entire system. Also, make sure that you have created the list that these leads are to be under so that you can use them. Here is a list of the fields in their proper order for the lead files:"); ?>
		<OL>
		<LI><?php echo _QXZ("Vendor Lead Code - shows up in the Vendor ID field of the GUI"); ?>
		<LI><?php echo _QXZ("Source Code - internal use only for admins and DBAs"); ?>
		<LI><?php echo _QXZ("List ID - the list number that these leads will show up under"); ?>
		<LI><?php echo _QXZ("Phone Code - the prefix for the phone number - 1 for US, 44 for UK, 61 for AUS, etc"); ?>
		<LI><?php echo _QXZ("Phone Number - must be at least 8 digits long"); ?>
		<LI><?php echo _QXZ("Title - title of the customer - Mr. Ms. Mrs, etc..."); ?>
		<LI><?php echo _QXZ("First Name"); ?>
		<LI><?php echo _QXZ("Middle Initial"); ?>
		<LI><?php echo _QXZ("Last Name"); ?>
		<LI><?php echo _QXZ("Address Line 1"); ?>
		<LI><?php echo _QXZ("Address Line 2"); ?>
		<LI><?php echo _QXZ("Address Line 3"); ?>
		<LI><?php echo _QXZ("City"); ?>
		<LI><?php echo _QXZ("State - limited to 2 characters"); ?>
		<LI><?php echo _QXZ("Province"); ?>
		<LI><?php echo _QXZ("Postal Code"); ?>
		<LI><?php echo _QXZ("Country"); ?>
		<LI><?php echo _QXZ("Gender"); ?>
		<LI><?php echo _QXZ("Date of Birth"); ?>
		<LI><?php echo _QXZ("Alternate Phone Number"); ?>
		<LI><?php echo _QXZ("Email Address"); ?>
		<LI><?php echo _QXZ("Security Phrase"); ?>
		<LI><?php echo _QXZ("Comments"); ?>
		<LI><?php echo _QXZ("Rank"); ?>
		<LI><?php echo _QXZ("Owner"); ?>
		</OL>

	<BR><?php echo _QXZ("NOTES: The Excel Lead loader functionality is enabled by a series of perl scripts and needs to have a properly configured /etc/astguiclient.conf file in place on the web server. Also, a couple perl modules must be loaded for it to work as well - OLE-Storage_Lite and Spreadsheet-ParseExcel. You can check for runtime errors in these by looking at your apache error_log file. Also, for duplication checks against campaign lists, the list that has new leads going into it does need to be created in the system before you start to load the leads."); ?>

	<BR>
	<A NAME="list_loader-duplicate_check">
	<BR>
	<B><?php echo _QXZ("Duplicate Checking"); ?> -</B><?php echo _QXZ("The duplicate options allow you to check for duplicate entries as you load the leads into the system. You can select to check for duplicates within only the same list, only the same campaign lists or within the entire system. If you have chosen a duplicate check method, you can also optionally select the only specific statuses that you want to duplicate check against."); ?>

	<BR>
	<A NAME="list_loader-file_layout">
	<BR>
	<B><?php echo _QXZ("File layout"); ?> -</B><?php echo _QXZ("The layout of the file you are loading.  Standard Format uses the pre-defined standard file format. Custom layout allows the user to define the layout of the file themselves. Custom template is a hybrid of the previous two options, which allows the user to use a custom format they have defined previously and saved using the Custom Template Maker."); ?>

	<BR>
	<A NAME="list_loader-template_id">
	<BR>
	<B><?php echo _QXZ("Template ID"); ?> -</B><?php echo _QXZ("If the user has selected Custom layout from the File layout options, then this the template the lead loader will use.  It will also override the selected list ID with the list ID that was assigned to the selected template when it was created."); ?>

	<BR>
	<A NAME="list_loader-state_conversion">
	<BR>
	<B><?php echo _QXZ("State Abbreviation Lookup"); ?> -</B><?php echo _QXZ("If your lead file has state names spelled out and you would like to load them into the state field as their two-character abbreviations, this feature will look up the abbreviation from the internal database. Default is DISABLED."); ?>



	<BR><BR><BR><BR>
	<?php
	}
?>





<B><FONT SIZE=3><?php echo _QXZ("PHONES TABLE"); ?></FONT></B><BR><BR>
<A NAME="phones-extension">
<BR>
<B><?php echo _QXZ("Phone Extension"); ?> -</B><?php echo _QXZ("This field is where you put the phones name as it appears to Asterisk not including the protocol or slash at the beginning. For Example: for the SIP phone SIP/test101 the Phone extension would be test101. Also, for IAX2 phones: IAX2/IAXphone1@IAXphone1 would be IAXphone1. For Zap and Dahdi attached channelbank or FXS phones make sure you put the full channel number without the prefix: Zap/25-1 would be 25-1.  Another note, make sure you set the Protocol field below correctly for your type of phone. For SIP and IAX phones, this field should not contain any dashes."); ?>

<BR>
<A NAME="phones-dialplan_number">
<BR>
<B><?php echo _QXZ("Dial Plan Number"); ?> -</B><?php echo _QXZ("This field is for the number you dial to have the phone ring. This number is defined in the extensions.conf file of your Asterisk server"); ?>

<BR>
<A NAME="phones-voicemail_id">
<BR>
<B><?php echo _QXZ("Voicemail Box"); ?> -</B><?php echo _QXZ("This field is for the voicemail box that the messages go to for the user of this phone. We use this to check for voicemail messages and for the user to be able to use the VOICEMAIL button on astGUIclient app."); ?>

<BR>
<A NAME="phones-voicemail_timezone">
<B><?php echo _QXZ("Voicemail Zone"); ?> -</B><?php echo _QXZ("This setting allows you to set the zone that this voicemail box will be set to when the time is logged for a message. Default is set in the System Settings."); ?>

<BR>
<A NAME="phones-voicemail_options">
<B><?php echo _QXZ("Voicemail Options"); ?> -</B><?php echo _QXZ("This optional setting allows you to define additional voicemail settings. It is recommended that you leave this blank unless you know what you are doing."); ?>

<BR>
<A NAME="phones-outbound_cid">
<BR>
<B><?php echo _QXZ("Outbound CallerID"); ?> -</B><?php echo _QXZ("This field is where you would enter the callerID number that you would like to appear on outbound calls placed form the astguiclient web-client. This does not work on RBS, non-PRI, T1/E1s."); ?>

<BR>
<A NAME="phones-outbound_alt_cid">
<BR>
<B><?php echo _QXZ("Outbound Alt CallerID"); ?> -</B><?php echo _QXZ("This optional field is where you can enter an alternate callerID number that can be used in certain cases in place of the original Outbound Caller ID number. Using this will require custom dialplan entries for it to work. Default is blank."); ?>

<BR>
<A NAME="phones-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="phones-phone_ip">
<BR>
<B><?php echo _QXZ("Phone IP address"); ?> -</B><?php echo _QXZ("This field is for the phone IP address if it is a VOIP phone. This is an optional field"); ?>

<BR>
<A NAME="phones-computer_ip">
<BR>
<B><?php echo _QXZ("Computer IP address"); ?> -</B><?php echo _QXZ("This field is for the user computer IP address. This is an optional field"); ?>

<BR>
<A NAME="phones-server_ip">
<BR>
<B><?php echo _QXZ("Server IP"); ?> -</B><?php echo _QXZ("This menu is where you select which server the phone is active on."); ?>

<BR>
<A NAME="phones-login">
<BR>
<B><?php echo _QXZ("Agent Screen Login"); ?> -</B><?php echo _QXZ("The login used for the phone user to login to the client applications, like the agent screen."); ?>

<BR>
<A NAME="phones-pass">
<BR>
<B><?php echo _QXZ("Login Password"); ?> -</B><?php echo _QXZ(" The password used for the phone user to login to the web-based client applications. IMPORTANT, this is the password only for the agent web interface phone login, to change the sip.conf or iax.conf password, or secret, for this phone device you need to modify the Registration Password in the next field."); ?>

<BR>
<A NAME="phones-conf_secret">
<BR>
<B><?php echo _QXZ("Registration Password"); ?> -</B><?php echo _QXZ("This is the secret, or password, for the phone in the iax or sip auto-generated conf file for this phone. Limit is 20 characters alphanumeric dash and underscore accepted. Default is test. Formerly called Conf File Secret. A strong registration password should be at least 8 characters in length and have lower case and upper case letters as well as at least one number."); ?>

<BR>
<A NAME="phones-is_webphone">
<BR>
<B><?php echo _QXZ("Set As Webphone"); ?> -</B><?php echo _QXZ(" Setting this option to Y will attempt to load a web-based phone when the agent logs into their agent screen. Default is N. The Y_API_LAUNCH option can be used with the agent API to launch the webphone in a separate Iframe or window."); ?>

<BR>
<A NAME="phones-webphone_dialpad">
<BR>
<B><?php echo _QXZ("Webphone Dialpad"); ?> -</B><?php echo _QXZ(" This setting allows you to activate or deactivate the dialpad for this webphone. Default is Y for enabled. TOGGLE will allow the user to view and hide the dialpad by clicking a link. This feature is not available on all webphone versions. TOGGLE_OFF will default to not show the dialpad on first load, but will allow the user to show the dialpad by clicking on the dialpad link."); ?>

<BR>
<A NAME="phones-webphone_auto_answer">
<BR>
<B><?php echo _QXZ("Webphone Auto-Answer"); ?> -</B><?php echo _QXZ(" This setting allows the web phone to be set to automatically answer calls that come in by setting it to Y, or to have calls ring by setting it to N. Default is Y."); ?>

<BR>
<A NAME="phones-webphone_dialbox">
<BR>
<B><?php echo _QXZ("Webphone Dialbox"); ?> - </B><?php echo _QXZ("For the WebRTC phone, this setting will allow the number to dial input box to be active. Default is Y."); ?>

<BR>
<A NAME="phones-webphone_mute">
<BR>
<B><?php echo _QXZ("Webphone Mute"); ?> - </B><?php echo _QXZ("For the WebRTC phone, this setting will allow the mute button to be active. Default is Y."); ?>

<BR>
<A NAME="phones-webphone_volume">
<BR>
<B><?php echo _QXZ("Webphone Volume"); ?> - </B><?php echo _QXZ("For the WebRTC phone, this setting will allow the volume buttons to be active. Default is Y."); ?>

<BR>
<A NAME="phones-webphone_debug">
<BR>
<B><?php echo _QXZ("Webphone Debug"); ?> - </B><?php echo _QXZ("For the WebRTC phone, this setting will show debug output. Default is N."); ?>

<BR>
<A NAME="phones-webphone_layout">
<BR>
<B><?php echo _QXZ("Webphone Layout"); ?> - </B><?php echo _QXZ("For the WebRTC phone, this setting will allow you to use an alternate layout. Default is blank."); ?>

<BR>
<A NAME="phones-use_external_server_ip">
<BR>
<B><?php echo _QXZ("Use External Server IP"); ?> -</B><?php echo _QXZ(" If using as a web phone, you can set this to Y to use the servers External IP to register to instead of the Server IP. Default is empty."); ?>

<BR>
<A NAME="phones-status">
<BR>
<B><?php echo _QXZ("Status"); ?> -</B><?php echo _QXZ("The status of the phone in the system, ACTIVE and ADMIN allow for GUI clients to work. ADMIN allows access to this administrative web site. All other statuses do not allow GUI or Admin web access."); ?>

<BR>
<A NAME="phones-active">
<BR>
<B><?php echo _QXZ("Active Account"); ?> -</B><?php echo _QXZ("Whether the phone is active to put it in the list in the GUI client."); ?>

<BR>
<A NAME="phones-phone_type">
<BR>
<B><?php echo _QXZ("Phone Type"); ?> -</B><?php echo _QXZ("Purely for administrative notes."); ?>

<BR>
<A NAME="phones-fullname">
<BR>
<B><?php echo _QXZ("Full Name"); ?> -</B><?php echo _QXZ("Used by the GUIclient in the list of active phones."); ?>

<BR>
<A NAME="phones-company">
<BR>
<B><?php echo _QXZ("Company"); ?> -</B><?php echo _QXZ("Purely for administrative notes."); ?>

<BR>
<A NAME="phones-email">
<BR>
<B><?php echo _QXZ("Phones Email"); ?> -</B><?php echo _QXZ("The email address associated with this phone entry. This is used for voicemail settings. Upon placing an email in this field, you are activating the process to drop voice mails to the email address entered."); ?>

<BR>
<A NAME="phones-delete_vm_after_email">
<B><?php echo _QXZ("Delete Voicemail After Email"); ?> -</B><?php echo _QXZ("This optional setting allows you to have the voicemail messages deleted from the system after they have been emailed out. Default is N."); ?>

<BR>
<A NAME="phones-voicemail_greeting">
<B><?php echo _QXZ("Voicemail Greeting"); ?> -</B><?php echo _QXZ("This optional setting allows you to define a voicemail greeting audio file from the audio store. Default is blank."); ?>

<BR>
<A NAME="phones-voicemail_instructions">
<B><?php echo _QXZ("Voicemail Instructions"); ?> -</B><?php echo _QXZ("This setting allows you to define if the voicemail instructions will play after the voicemail greeting when a call rings on the agent extension and times out to voicemail. Default is Y."); ?>

<BR>
<A NAME="phones-show_vm_on_summary">
<B><?php echo _QXZ("Show VM on Summary Screen"); ?> -</B><?php echo _QXZ("This option will display this Voicemail Box information on the summary page seen when logging into the administration page. It will show the box name, new message count, old count, and total messages in the box. Note the table will not be shown unless you have set at least one mailbox to Y. Default is N for off."); ?>

<BR>
<A NAME="phones-unavail_dialplan_fwd_exten">
<B><?php echo _QXZ("Unavailable Dialplan Forward"); ?> -</B><?php echo _QXZ("If this field is populated, any calls sent to this phone that go unanswered will be sent to a dialplan extension instead of going to the phone voicemail box. If context is left blank then the call will go to the extension at the default context. Default is blank for disabled."); ?>

<BR>
<A NAME="phones-picture">
<BR>
<B><?php echo _QXZ("Picture"); ?> -</B><?php echo _QXZ("Not yet Implemented."); ?>

<BR>
<A NAME="phones-messages">
<BR>
<B><?php echo _QXZ("New Messages"); ?> -</B><?php echo _QXZ("Number of new voicemail messages for this phone on the Asterisk server."); ?>

<BR>
<A NAME="phones-old_messages">
<BR>
<B><?php echo _QXZ("Old Messages"); ?> -</B><?php echo _QXZ("Number of old voicemail messages for this phone on the Asterisk server."); ?>

<BR>
<A NAME="phones-protocol">
<BR>
<B><?php echo _QXZ("Client Protocol"); ?> -</B><?php echo _QXZ("The protocol that the phone uses to connect to the Asterisk server: SIP, IAX2, Zap . Also, there is EXTERNAL for remote dial numbers or speed dial numbers that you want to list as phones."); ?>

<BR>
<A NAME="phones-local_gmt">
<BR>
<B><?php echo _QXZ("Local GMT"); ?> -</B><?php echo _QXZ("The difference from Greenwich Mean time, or ZULU time where the phone is located. DO NOT ADJUST FOR DAYLIGHT SAVINGS TIME. This is used by the campaign to accurately display the system time and customer time, as well as accurately log when events happen."); ?>

<BR>
<A NAME="phones-phone_ring_timeout">
<BR>
<B><?php echo _QXZ("Phone Ring Timeout"); ?> -</B><?php echo _QXZ("This is the amount of time, in seconds, that the phone will ring in the dialplan before sending the call to voicemail. Default is 60 seconds."); ?>

<BR>
<A NAME="phones-on_hook_agent">
<BR>
<B><?php echo _QXZ("On-Hook Agent Login"); ?> -</B><?php echo _QXZ("This option is only used for inbound calls going to an agent logged in with this phone. This feature will call the agent and will not send the customer to the agents session until the line is answered. Default is N for disabled."); ?>

<BR>
<A NAME="phones-ASTmgrUSERNAME">
<BR>
<B><?php echo _QXZ("Manager Login"); ?> -</B><?php echo _QXZ("This is the login that the GUI clients for this phone will use to access the Database where the server data resides."); ?>

<BR>
<A NAME="phones-ASTmgrSECRET">
<BR>
<B><?php echo _QXZ("Manager Secret"); ?> -</B><?php echo _QXZ("This is the password that the GUI clients for this phone will use to access the Database where the server data resides."); ?>

<BR>
<A NAME="phones-login_user">
<BR>
<B><?php echo _QXZ("Agent Default User"); ?> -</B><?php echo _QXZ("This is to place a default value in the agent user field whenever this phone user opens the client app. Leave blank for no user."); ?>

<BR>
<A NAME="phones-login_pass">
<BR>
<B><?php echo _QXZ("Agent Default Pass"); ?> -</B><?php echo _QXZ("This is to place a default value in the agent password field whenever this phone user opens the client app. Leave blank for no pass."); ?>

<BR>
<A NAME="phones-login_campaign">
<BR>
<B><?php echo _QXZ("Agent Default Campaign"); ?> -</B><?php echo _QXZ("This is to place a default value in the agent screen campaign field whenever this phone user opens the client app. Leave blank for no campaign."); ?>

<BR>
<A NAME="phones-park_on_extension">
<BR>
<B><?php echo _QXZ("Park Exten"); ?> -</B><?php echo _QXZ("This is the default Parking extension for the client apps. Verify that a different one works before you change this."); ?>

<BR>
<A NAME="phones-conf_on_extension">
<BR>
<B><?php echo _QXZ("Conf Exten"); ?> -</B><?php echo _QXZ("This is the default Conference park extension for the client apps. Verify that a different one works before you change this."); ?>

<BR>
<A NAME="phones-park_on_extension">
<BR>
<B><?php echo _QXZ("Agent Park Exten"); ?> -</B><?php echo _QXZ("This is the default Parking extension for client app. Verify that a different one works before you change this."); ?>

<BR>
<A NAME="phones-park_on_filename">
<BR>
<B><?php echo _QXZ("Agent Park File"); ?> -</B><?php echo _QXZ("This is the default agent screen park extension file name for the client apps. Verify that a different one works before you change this. limited to 10 characters."); ?>

<BR>
<A NAME="phones-monitor_prefix">
<BR>
<B><?php echo _QXZ("Monitor Prefix"); ?> -</B><?php echo _QXZ("This is the dial plan prefix for monitoring of Zap channels automatically within the astGUIclient app. Only change according to the extensions.conf ZapBarge extensions records."); ?>

<BR>
<A NAME="phones-recording_exten">
<BR>
<B><?php echo _QXZ("Recording Exten"); ?> -</B><?php echo _QXZ("This is the dial plan extension for the recording extension that is used to drop into meetme conferences to record them. It usually lasts upto one hour if not stopped. verify with extensions.conf file before changing."); ?>

<BR>
<A NAME="phones-voicemail_exten">
<BR>
<B><?php echo _QXZ("VMAIL Main Exten"); ?> -</B><?php echo _QXZ("This is the dial plan extension going to check your voicemail. verify with extensions.conf file before changing."); ?>

<BR>
<A NAME="phones-voicemail_dump_exten">
<BR>
<B><?php echo _QXZ("VMAIL Dump Exten"); ?> -</B><?php echo _QXZ("This is the dial plan prefix used to send calls directly to a user voicemail from a live call in the astGUIclient app. verify with extensions.conf file before changing."); ?>

<BR>
<A NAME="phones-voicemail_dump_exten_no_inst">
<BR>
<B><?php echo _QXZ("VMAIL Dump Exten NI"); ?> -</B><?php echo _QXZ("This is the dial plan prefix used to send calls directly to a user voicemail from a live call in the astGUIclient app. This is the No Instructions setting."); ?>

<BR>
<A NAME="phones-ext_context">
<BR>
<B><?php echo _QXZ("Exten Context"); ?> -</B><?php echo _QXZ("This is the dial plan context that the agent screen, primarily uses. It is assumed that all numbers dialed by the client apps are using this context so it is a good idea to make sure this is the most wide context possible. verify with extensions.conf file before changing. default is default."); ?>

<BR>
<A NAME="phones-phone_context">
<BR>
<B><?php echo _QXZ("Phone Context"); ?> -</B><?php echo _QXZ("This is the dial plan context that this phone will use to dial out. If you are running a call center and you do not want your agents to be able to dial out outside of the agent screen application for example, then you would set this field to a dialplan context that does not exist, something like agent-nodial. default is default."); ?>

<BR>
<A NAME="phones-codecs_list">
<BR>
<B><?php echo _QXZ("Allowed Codecs"); ?> -</B><?php echo _QXZ("You can define a comma delimited list of codecs to be set as the default codecs for this phone. Options for codecs include ulaw, alaw, gsm, g729, speex, g722, g723, g726, ilbc, ... Some of these codecs might not be available on your system, like g729 or g726. If the field is empty, then the system default codecs or the phone entry above this one will be used for the allowable codecs. Default is empty."); ?>

<BR>
<A NAME="phones-codecs_with_template">
<BR>
<B><?php echo _QXZ("Allowed Codecs With Template"); ?> -</B><?php echo _QXZ("Setting this option to 1 will include the codecs defined above even if a conf file template is used. Default is 0."); ?>

<BR>
<A NAME="phones-conf_qualify">
<BR>
<B><?php echo _QXZ("Conf Qualify"); ?> -</B><?php echo _QXZ("This setting allows you to add or remove the qualify entry in the Asterisk conf file for this phone if it is IAX type. Default is Y for active."); ?>

<BR>
<A NAME="phones-dtmf_send_extension">
<BR>
<B><?php echo _QXZ("DTMF send Channel"); ?> -</B><?php echo _QXZ("This is the channel string used to send DTMF sounds into meetme conferences from the client apps. Verify the exten and context with the extensions.conf file."); ?>

<BR>
<A NAME="phones-call_out_number_group">
<BR>
<B><?php echo _QXZ("Outbound Call Group"); ?> -</B><?php echo _QXZ("This is the channel group that outbound calls from this phone are placed out of. There are a couple routines in the client apps that use this. For Zap channels you want to use something like Zap/g2 , for IAX2 trunks you would want to use the full IAX prefix like IAX2/VICItest1:secret@10.10.10.15:4569. Verify the trunks with the extensions.conf file, it is usually what you have defined as the TRUNK global variable at the top of the file."); ?>

<BR>
<A NAME="phones-client_browser">
<BR>
<B><?php echo _QXZ("Browser Location"); ?> -</B><?php echo _QXZ("This is applicable to only UNIX/LINUX clients, the absolute path to Mozilla or Firefox browser on the machine. verify this by launching it manually."); ?>

<BR>
<A NAME="phones-install_directory">
<BR>
<B><?php echo _QXZ("Install Directory"); ?> -</B><?php echo _QXZ("Not used anymore."); ?>

<BR>
<A NAME="phones-local_web_callerID_URL">
<BR>
<B><?php echo _QXZ("CallerID URL"); ?> -</B><?php echo _QXZ("This is the web address of the page used to do custom callerID lookups. default testing address is: http://astguiclient.sf.net/test_callerid_output.php"); ?>

<BR>
<A NAME="phones-agent_web_URL">
<BR>
<B><?php echo _QXZ("Agent Default URL"); ?> -</B><?php echo _QXZ("This is the web address of the page used to do custom agent Web Form queries. default testing address is defined in the database schema."); ?>

<BR>
<A NAME="phones-nva_call_url">
<BR>
<B><?php echo _QXZ("NVA Call URL"); ?> -</B><?php echo _QXZ("This is the optional web URL that can be used together with the NVA agi script in a Call Menu to log phone calls made outside of the agent screen. Variables that can be used with this feature are- phone_number, uniqueid, lead_id, extension, server_ip, entry_date, modify_date, status, user, vendor_lead_code, source_id, list_id, phone_number, title, first_name, middle_initial, last_name, address1, address2, address3, city, state, province, postal_code, country_code, gender, date_of_birth, alt_phone, email, security_phrase, comments, called_count, last_local_call_time, rank, owner, campaign_id, list_description, recording_id, recording_filename."); ?>

<BR>
<A NAME="phones-nva_search_method">
<BR>
<B><?php echo _QXZ("NVA Search Method"); ?> -</B><?php echo _QXZ("If this phone dials through the NVA agi script in a Call Menu, and the NVA agi option is set to use the phone NVA Search Method, this is where that is defined."); ?>

<BR>
<A NAME="phones-nva_error_filename">
<BR>
<B><?php echo _QXZ("NVA Error Filename"); ?> -</B><?php echo _QXZ("If this phone dials through the NVA agi script in a Call Menu, this is the error file that is played for the user of this phone if an error occurs."); ?>

<BR>
<A NAME="phones-nva_new_list_id">
<BR>
<B><?php echo _QXZ("NVA New List ID"); ?> -</B><?php echo _QXZ("If this phone dials through the NVA agi script in a Call Menu, this is the list ID that a new lead is inserted into if the phone number is not found and the NVA option to insert a new lead is set to Y. Default is 995."); ?>

<BR>
<A NAME="phones-nva_new_phone_code">
<BR>
<B><?php echo _QXZ("NVA New Phone Code"); ?> -</B><?php echo _QXZ("If this phone dials through the NVA agi script in a Call Menu, this is the phone code that a new lead is inserted with if the phone number is not found and the NVA option to insert a new lead is set to Y. Default is 1."); ?>

<BR>
<A NAME="phones-nva_new_status">
<BR>
<B><?php echo _QXZ("NVA New Status"); ?> -</B><?php echo _QXZ("If this phone dials through the NVA agi script in a Call Menu, this is the status that a new lead is inserted with if the phone number is not found and the NVA option to insert a new lead is set to Y. Default is NVAINS."); ?>

<BR>
<A NAME="phones-AGI_call_logging_enabled">
<BR>
<B><?php echo _QXZ("Call Logging"); ?> -</B><?php echo _QXZ("This is set to true if the call_log step is in place in the extensions.conf file for all outbound and hang up h extensions to log all calls. This should always be 1 because it is mandatory for many of the system features to work properly."); ?>

<BR>
<A NAME="phones-user_switching_enabled">
<BR>
<B><?php echo _QXZ("User Switching"); ?> -</B><?php echo _QXZ("Set to true to allow user to switch to another user account. NOTE: If user switches they can initiate recording on the new user phone conversation"); ?>

<BR>
<A NAME="phones-conferencing_enabled">
<BR>
<B><?php echo _QXZ("Conferencing"); ?> -</B><?php echo _QXZ("Set to true to allow user to start conference calls with upto six external lines."); ?>

<BR>
<A NAME="phones-admin_hangup_enabled">
<BR>
<B><?php echo _QXZ("Admin Hang Up"); ?> -</B><?php echo _QXZ("Set to true to allow user to be able to hang up any line at will through astGUIclient. Good idea only to enable this for Admin users."); ?>

<BR>
<A NAME="phones-admin_hijack_enabled">
<BR>
<B><?php echo _QXZ("Admin Hijack"); ?> -</B><?php echo _QXZ("Set to true to allow user to be able to grab and redirect to their extension any line at will through astGUIclient. Good idea only to enable this for Admin users. But is very useful for Managers."); ?>

<BR>
<A NAME="phones-admin_monitor_enabled">
<BR>
<B><?php echo _QXZ("Admin Monitor"); ?> -</B><?php echo _QXZ("Set to true to allow user to be able to grab and redirect to their extension any line at will through astGUIclient. Good idea only to enable this for Admin users. But is very useful for Managers and as a training tool."); ?>

<BR>
<A NAME="phones-call_parking_enabled">
<BR>
<B><?php echo _QXZ("Call Park"); ?> -</B><?php echo _QXZ("Set to true to allow user to be able to park calls on astGUIclient hold to be picked up by any other astGUIclient user on the system. Calls stay on hold for upto a half hour then hang up. Usually enabled for all."); ?>

<BR>
<A NAME="phones-updater_check_enabled">
<BR>
<B><?php echo _QXZ("Updater Check"); ?> -</B><?php echo _QXZ("Set to true to display a popup warning that the updater time has not changed in 20 seconds. Useful for Admin users."); ?>

<BR>
<A NAME="phones-AFLogging_enabled">
<BR>
<B><?php echo _QXZ("AF Logging"); ?> -</B><?php echo _QXZ("Set to true to log many actions of astGUIclient usage to a text file on the user computer."); ?>

<BR>
<A NAME="phones-QUEUE_ACTION_enabled">
<BR>
<B><?php echo _QXZ("Queue Enabled"); ?> -</B><?php echo _QXZ("Set to true to have client apps use the Asterisk Central Queue system. Required for the system to work and recommended for all phones."); ?>

<BR>
<A NAME="phones-CallerID_popup_enabled">
<BR>
<B><?php echo _QXZ("CallerID Popup"); ?> -</B><?php echo _QXZ("Set to true to allow for numbers defined in the extensions.conf file to send CallerID popup screens to astGUIclient users."); ?>

<BR>
<A NAME="phones-voicemail_button_enabled">
<BR>
<B><?php echo _QXZ("VMail Button"); ?> -</B><?php echo _QXZ("Set to true to display the VOICEMAIL button and the messages count display on astGUIclient."); ?>

<BR>
<A NAME="phones-enable_fast_refresh">
<BR>
<B><?php echo _QXZ("Fast Refresh"); ?> -</B><?php echo _QXZ("Set to true to enable a new rate of refresh of call information for the astGUIclient. Default disabled rate is 1000 ms ,1 second. Can increase system load if you lower this number."); ?>

<BR>
<A NAME="phones-fast_refresh_rate">
<BR>
<B><?php echo _QXZ("Fast Refresh Rate"); ?> -</B><?php echo _QXZ("in milliseconds. Only used if Fast Refresh is enabled. Default disabled rate is 1000 ms ,1 second. Can increase system load if you lower this number."); ?>

<BR>
<A NAME="phones-enable_persistant_mysql">
<BR>
<B><?php echo _QXZ("Persistant MySQL"); ?> -</B><?php echo _QXZ("If enabled the astGUIclient connection will remain connected instead of connecting every second. Useful if you have a fast refresh rate set. It will increase the number of connections on your MySQL machine."); ?>

<BR>
<A NAME="phones-auto_dial_next_number">
<BR>
<B><?php echo _QXZ("Auto Dial Next Number"); ?> -</B><?php echo _QXZ("If enabled the agent screen will dial the next number on the list automatically upon disposition of a call unless they selected to PAUSE AGENT DIALING on the disposition screen."); ?>

<BR>
<A NAME="phones-VDstop_rec_after_each_call">
<BR>
<B><?php echo _QXZ("Stop Rec after each call"); ?> -</B><?php echo _QXZ("If enabled the agent screen will stop whatever recording is going on after each call has been dispositioned. Useful if you are doing a lot of recording or you are using a web form to trigger recording. "); ?>

<BR>
<A NAME="phones-enable_sipsak_messages">
<BR>
<B><?php echo _QXZ("Enable SIPSAK Messages"); ?> -</B><?php echo _QXZ("If enabled, the server will send messages to the SIP phone to display on the phone display screen when logged into the agent web interface. Feature only works with SIP phones and requires sipsak application to be installed on the web server. Default is 0."); ?>

<BR>
<A NAME="phones-DBX_server">
<BR>
<B><?php echo _QXZ("DBX Server"); ?> -</B><?php echo _QXZ("The MySQL database server that this user should be connecting to."); ?>

<BR>
<A NAME="phones-DBX_database">
<BR>
<B><?php echo _QXZ("DBX Database"); ?> -</B><?php echo _QXZ("The MySQL database that this user should be connecting to. Default is asterisk."); ?>

<BR>
<A NAME="phones-DBX_user">
<BR>
<B><?php echo _QXZ("DBX User"); ?> -</B><?php echo _QXZ("The MySQL user login that this user should be using when connecting. Default is cron."); ?>

<BR>
<A NAME="phones-DBX_pass">
<BR>
<B><?php echo _QXZ("DBX Pass"); ?> -</B><?php echo _QXZ("The MySQL user password that this user should be using when connecting. Default is 1234."); ?>

<BR>
<A NAME="phones-DBX_port">
<BR>
<B><?php echo _QXZ("DBX Port"); ?> -</B><?php echo _QXZ("The MySQL TCP port that this user should be using when connecting. Default is 3306."); ?>

<BR>
<A NAME="phones-DBY_server">
<BR>
<B><?php echo _QXZ("DBY Server"); ?> -</B><?php echo _QXZ("The MySQL database server that this user should be connecting to. Secondary server, not used currently."); ?>

<BR>
<A NAME="phones-DBY_database">
<BR>
<B><?php echo _QXZ("DBY Database"); ?> -</B><?php echo _QXZ("The MySQL database that this user should be connecting to. Default is asterisk. Secondary server, not used currently."); ?>

<BR>
<A NAME="phones-DBY_user">
<BR>
<B><?php echo _QXZ("DBY User"); ?> -</B><?php echo _QXZ("The MySQL user login that this user should be using when connecting. Default is cron. Secondary server, not used currently."); ?>

<BR>
<A NAME="phones-DBY_pass">
<BR>
<B><?php echo _QXZ("DBY Pass"); ?> -</B><?php echo _QXZ("The MySQL user password that this user should be using when connecting. Default is 1234. Secondary server, not used currently."); ?>

<BR>
<A NAME="phones-DBY_port">
<BR>
<B><?php echo _QXZ("DBY Port"); ?> -</B><?php echo _QXZ("The MySQL TCP port that this user should be using when connecting. Default is 3306. Secondary server, not used currently."); ?>

<BR>
<A NAME="phones-alias_id">
<BR>
<B><?php echo _QXZ("Alias ID"); ?> -</B><?php echo _QXZ("The ID of the alias used to allow for phone load balanced logins. no spaces or other special characters allowed. Must be between 2 and 20 characters in length."); ?>

<BR>
<A NAME="phones-alias_name">
<BR>
<B><?php echo _QXZ("Alias Name"); ?> -</B><?php echo _QXZ("The name used to describe a phones alias, Must be between 2 and 50 characters in length."); ?>

<BR>
<A NAME="phones-logins_list">
<BR>
<B><?php echo _QXZ("Phones Logins List"); ?> -</B><?php echo _QXZ("The comma separated list of phone logins used when an agent logs in using phone load balanced logins. The Agent application will find the active server with the fewest agents logged into it and place a call from that server to the agent upon login."); ?>

<BR>
<A NAME="phones-template_id">
<BR>
<B><?php echo _QXZ("Template ID"); ?> -</B><?php echo _QXZ("This is the conf file template ID that this phone entry will use for its Asterisk settings. Default is --NONE--."); ?>

<BR>
<A NAME="phones-conf_override">
<BR>
<B><?php echo _QXZ("Conf Override Settings"); ?> -</B><?php echo _QXZ("If populated, and the Template ID is set to --NONE-- then the contents of this field are used as the conf file entries for this phone. generate conf files for this phones server must be set to Y for this to work. This field should NOT contain the [extension] line, that will be automatically generated."); ?>

<BR>
<A NAME="phones-group_alias_id">
<BR>
<B><?php echo _QXZ("Group Alias ID"); ?> -</B><?php echo _QXZ("The ID of the group alias used by agents to dial out calls from the agent interface with different Caller IDs. no spaces or other special characters allowed. Must be between 2 and 20 characters in length."); ?>

<BR>
<A NAME="phones-group_alias_name">
<BR>
<B><?php echo _QXZ("Group Alias Name"); ?> -</B><?php echo _QXZ("The name used to describe a group alias, Must be between 2 and 50 characters in length."); ?>

<BR>
<A NAME="phones-caller_id_number">
<BR>
<B><?php echo _QXZ("Caller ID Number"); ?> -</B><?php echo _QXZ("The Caller ID number used in this Group Alias. Must be digits only."); ?>

<BR>
<A NAME="phones-caller_id_name">
<BR>
<B><?php echo _QXZ("Caller ID Name"); ?> -</B><?php echo _QXZ("The Caller ID name that can be sent out with this Group Alias. As far as we know this will only work in Canada on PRI circuits and using an IAX loop trunk through Asterisk."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("SERVERS TABLE"); ?></FONT></B><BR><BR>
<A NAME="servers-server_id">
<BR>
<B><?php echo _QXZ("Server ID"); ?> -</B><?php echo _QXZ("This field is where you put the Asterisk servers name, does not have to be an official domain sub, just a nickname to identify the server to Admin users."); ?>

<BR>
<A NAME="servers-server_description">
<BR>
<B><?php echo _QXZ("Server Description"); ?> -</B><?php echo _QXZ("The field where you use a small phrase to describe the Asterisk server."); ?>

<BR>
<A NAME="servers-server_ip">
<BR>
<B><?php echo _QXZ("Server IP Address"); ?> -</B><?php echo _QXZ("The field where you put the Network IP address of the Asterisk server."); ?>

<BR>
<A NAME="servers-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("Set whether the Asterisk server is active or inactive."); ?>

<BR>
<A NAME="servers-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="servers-sysload">
<BR>
<B><?php echo _QXZ("System Load"); ?> -</B><?php echo _QXZ("These two statistics show the loadavg of a system times 100 and the CPU usage percentage of the server and is updated every minute. The loadavg should on average be below 100 multiplied by the number of CPU cores your system has, for optimal performance. The CPU usage percentage should stay below 50 for optimal performance."); ?>

<BR>
<A NAME="servers-channels_total">
<BR>
<B><?php echo _QXZ("Live Channels"); ?> -</B><?php echo _QXZ("This field shows the current number of Asterisk channels that are live on the system right now. It is important to note that the number of Asterisk channels is usually much higher than the number of actual calls on a system. This field is updated once every minute.") . ' ' . _QXZ("The Agents field shows the current number of agents logged into the agent screen on this server."); ?>

<BR>
<A NAME="servers-disk_usage">
<BR>
<B><?php echo _QXZ("Disk Usage"); ?> -</B><?php echo _QXZ("This field will show the disk usage for every partition on this server. This field is updated once every minute."); ?>

<BR>
<A NAME="servers-system_uptime">
<BR>
<B><?php echo _QXZ("System Uptime"); ?> -</B><?php echo _QXZ("This field will show the system uptime of this server. This field only updates if configured to do so by your administrator."); ?>

<BR>
<A NAME="servers-asterisk_version">
<BR>
<B><?php echo _QXZ("Asterisk Version"); ?> -</B><?php echo _QXZ("Set the version of Asterisk that you have installed on this server. Examples: 1.2, 1.0.8, 1.0.7, CVS_HEAD, REALLY OLD, etc... This is used because versions 1.0.8 and 1.0.9 have a different method of dealing with Local/ channels, a bug that has been fixed in CVS v1.0, and need to be treated differently when handling their Local/ channels. Also, current CVS_HEAD and the 1.2 release tree uses different manager and command output so it must be treated differently as well."); ?>

<BR>
<A NAME="servers-max_trunks">
<BR>
<B><?php echo _QXZ("Max Trunks"); ?> -</B><?php echo _QXZ("This field will determine the maximum number of lines that the auto-dialer will attempt to call on this server. If you want to dedicate two full PRI T1s to outbound dialing on a server then you would set this to 46. Any inbound or manual dial calls will be counted against this total as well. Default is 96."); ?>

<BR>
<A NAME="servers-outbound_calls_per_second">
<BR>
<B><?php echo _QXZ("Max Calls per Second"); ?> -</B><?php echo _QXZ("This setting determines the maximum number of calls that can be placed by the outbound auto-dialing script on this server per second. Must be from 1 to 100. Default is 20."); ?>

<BR>
<A NAME="servers-telnet_host">
<BR>
<B><?php echo _QXZ("Telnet Host"); ?> -</B><?php echo _QXZ("This is the address or name of the Asterisk server and is how the manager applications connect to it from where they are running. If they are running on the Asterisk server, then the default of localhost is fine."); ?>

<BR>
<A NAME="servers-telnet_port">
<BR>
<B><?php echo _QXZ("Telnet Port"); ?> -</B><?php echo _QXZ("This is the port of the Asterisk server Manager connection and is how the manager applications connect to it from where they are running. The default of 5038 is fine for a standard install."); ?>

<BR>
<A NAME="servers-ASTmgrUSERNAME">
<BR>
<B><?php echo _QXZ("Manager User"); ?> -</B><?php echo _QXZ("The username or login used to connect genericly to the Asterisk server manager. Default is cron"); ?>

<BR>
<A NAME="servers-ASTmgrSECRET">
<BR>
<B><?php echo _QXZ("Manager Secret"); ?> -</B><?php echo _QXZ("The secret or password used to connect genericly to the Asterisk server manager. Default is 1234"); ?>

<BR>
<A NAME="servers-ASTmgrUSERNAMEupdate">
<BR>
<B><?php echo _QXZ("Manager Update User"); ?> -</B><?php echo _QXZ("The username or login used to connect to the Asterisk server manager optimized for the Update scripts. Default is updatecron and assumes the same secret as the generic user."); ?>

<BR>
<A NAME="servers-ASTmgrUSERNAMElisten">
<BR>
<B><?php echo _QXZ("Manager Listen User"); ?> -</B><?php echo _QXZ("The username or login used to connect to the Asterisk server manager optimized for scripts that only listen for output. Default is listencron and assumes the same secret as the generic user."); ?>

<BR>
<A NAME="servers-ASTmgrUSERNAMEsend">
<BR>
<B><?php echo _QXZ("Manager Send User"); ?> -</B><?php echo _QXZ("The username or login used to connect to the Asterisk server manager optimized for scripts that only send Actions to the manager. Default is sendcron and assumes the same secret as the generic user."); ?>

<BR>
<A NAME="servers-conf_secret">
<BR>
<B><?php echo _QXZ("Conf File Secret"); ?> -</B><?php echo _QXZ("This is the secret, or password, for the server in the iax auto-generated conf file for this server on other servers. Limit is 20 characters alphanumeric dash and underscore accepted. Default is test. A strong conf file secret should be at least 8 characters in length and have lower case and upper case letters as well as at least one number."); ?>

<BR>
<A NAME="servers-local_gmt">
<BR>
<B><?php echo _QXZ("Server GMT offset"); ?> -</B><?php echo _QXZ("The difference in hours from GMT time not adjusted for Daylight-Savings-Time of the server. Default is -5"); ?>

<BR>
<A NAME="servers-voicemail_dump_exten">
<BR>
<B><?php echo _QXZ("VMail Dump Exten"); ?> -</B><?php echo _QXZ("The extension prefix used on this server to send calls directly through agc to a specific voicemail box. Default is 85026666666666"); ?>

<BR>
<A NAME="servers-voicemail_dump_exten_no_inst">
<BR>
<B><?php echo _QXZ("VMAIL Dump Exten NI"); ?> -</B><?php echo _QXZ("This is the dial plan prefix used to send calls directly to a user voicemail from a live call in the astGUIclient app. This is the No Instructions setting."); ?>

<BR>
<A NAME="servers-answer_transfer_agent">
<BR>
<B><?php echo _QXZ("auto dial extension"); ?> -</B><?php echo _QXZ("The default extension if none is present in the campaign to send calls to for  auto dialing. Default is 8365"); ?>

<BR>
<A NAME="servers-routing_prefix">
<BR>
<B><?php echo _QXZ("Routing Prefix"); ?> -</B><?php echo _QXZ("If populated, this value will be added in front of the auto dial extension when an auto-dial call is placced on a dialer server that is running Asterisk verison 13 or higher. Default is 13."); ?>

<BR>
<A NAME="servers-ext_context">
<BR>
<B><?php echo _QXZ("Default Context"); ?> -</B><?php echo _QXZ("The default dial plan context used for scripts that operate for this server. Default is default"); ?>

<BR>
<A NAME="servers-sys_perf_log">
<BR>
<B><?php echo _QXZ("System Performance"); ?> -</B><?php echo _QXZ("Setting this option to Y will enable logging of system performance stats for the server machine including system load, system processes and Asterisk channels in use. Default is N."); ?>

<BR>
<A NAME="servers-vd_server_logs">
<BR>
<B><?php echo _QXZ("Server Logs"); ?> -</B><?php echo _QXZ("Setting this option to Y will enable logging of all system related scripts to their text log files. Setting this to N will stop writing logs to files for these processes, also the screen logging of asterisk will be disabled if this is set to N when Asterisk is started. Default is Y."); ?>

<BR>
<A NAME="servers-agi_output">
<BR>
<B><?php echo _QXZ("AGI Output"); ?> -</B><?php echo _QXZ("Setting this option to NONE will disable output from all system related AGI scripts. Setting this to STDERR will send the AGI output to the Asterisk CLI. Setting this to FILE will send the output to a file in the logs directory. Setting this to BOTH will send output to both the Asterisk CLI and a log file. Default is FILE."); ?>

<BR>
<A NAME="servers-balance_active">
<BR>
<B><?php echo _QXZ("Balance Dialing"); ?> -</B><?php echo _QXZ("Setting this field to Y will allow the server to place balance calls for campaigns in the system so that the defined dial level can be met even if there are no agents logged into that campaign on this server. Default is N."); ?>

<BR>
<A NAME="servers-balance_rank">
<BR>
<B><?php echo _QXZ("Balance Rank"); ?> -</B><?php echo _QXZ("This field allows you to set the order in which this server is to be used for balance dialing, if balance dialing is enabled. The server with the highest rank will be used first in placing Balance fill calls. Default is 0."); ?>

<BR>
<A NAME="servers-balance_trunks_offlimits">
<BR>
<B><?php echo _QXZ("Balance Offlimits"); ?> -</B><?php echo _QXZ("This setting defines the number of trunks to not allow the balance dialing processes to use. For example if you have 40 max trunks and balance offlimits is set to 10 you will only be able to use 30 trunk lines for balance dialing. Default is 0."); ?>

<BR>
<A NAME="servers-recording_web_link">
<BR>
<B><?php echo _QXZ("Recording Web Link"); ?> -</B><?php echo _QXZ("This setting allows you to override the default of the display of the recording link in the admin web pages. Default is SERVER_IP."); ?>

<BR>
<A NAME="servers-alt_server_ip">
<BR>
<B><?php echo _QXZ("Alternate Recording Server IP"); ?> -</B><?php echo _QXZ("This setting is where you can put a server IP or other machine name that can be used in place of the server_ip in the links to recordings within the admin web pages. Default is empty."); ?>

<BR>
<A NAME="servers-external_server_ip">
<BR>
<B><?php echo _QXZ("External Server IP"); ?> -</B><?php echo _QXZ("This setting is where you can put a server IP or other machine name that can be used in place of the server_ip when using a webphone in the agent interface. For this to work you also must have the phones entry set to use the External Server IP. Default is empty."); ?>

<BR>
<A NAME="servers-external_server_ip">
<BR>
<B><?php echo _QXZ("External Server IP"); ?> -</B><?php echo _QXZ("This setting is where you can put a server IP or other machine name that can be used in place of the server_ip when using a webphone in the agent interface. For this to work you also must have the phones entry set to use the External Server IP. Default is empty."); ?>

<BR>
<A NAME="servers-web_socket_url">
<BR>
<B><?php echo _QXZ("Web Socket URL"); ?> -</B><?php echo _QXZ("For systems running Asterisk 11 and higher, this is the URL that a WebRTC phone needs to connect to the server."); ?>

<BR>
<A NAME="servers-active_asterisk_server">
<BR>
<B><?php echo _QXZ("Active Asterisk Server"); ?> -</B><?php echo _QXZ("If Asterisk is not running on this server, or if the dialing processes should not be using this server, or if are only using this server for other scripts like the hopper loading script you would want to set this to N. Default is Y."); ?>

<BR>
<A NAME="servers-auto_restart_asterisk">
<BR>
<B><?php echo _QXZ("Auto-Restart Asterisk"); ?> -</B><?php echo _QXZ("If Asterisk is running on this server and you want the system to make sure that it will be restarted in the event that it crashes, you might want to consider enabling this setting. If enabled, the system will check every minute to see if Asterisk is running, and if it is not it will attempt to restart it. This process will not run in the first 5 minutes after a system has been up. Default is N."); ?>

<BR>
<A NAME="servers-asterisk_temp_no_restart">
<BR>
<B><?php echo _QXZ("Temp No-Restart Asterisk"); ?> -</B><?php echo _QXZ("If Auto-Restart Asterisk is enabled on this server, turning on this setting will prevent the auto-restart process from running until after the server is rebooted. Default is N."); ?>

<BR>
<A NAME="servers-active_agent_login_server">
<BR>
<B><?php echo _QXZ("Active Agent Server"); ?> -</B><?php echo _QXZ("Setting this option to N will prevent agents from being able to log in to this server through the agent screen. This is very useful when using a phone login load balanced setup. Default is Y."); ?>

<BR>
<A NAME="servers-generate_conf">
<BR>
<B><?php echo _QXZ("Generate conf files"); ?> -</B><?php echo _QXZ("If you would like the system to auto-generate asterisk conf files based upon the phones entries, carrier entries and load balancing setup within the system then set this to Y. Default is Y."); ?>

<BR>
<A NAME="servers-rebuild_conf_files">
<BR>
<B><?php echo _QXZ("Rebuild conf files"); ?> -</B><?php echo _QXZ("If you want to force a rebuilding of the Asterisk conf files or if any of the phones or carrier entries have changed then this should be set to Y. After the conf files have been generated and Asterisk has been reloaded then this will be changed to N. Default is Y."); ?>

<BR>
<A NAME="servers-rebuild_music_on_hold">
<BR>
<B><?php echo _QXZ("Rebuild Music On Hold"); ?> -</B><?php echo _QXZ("If you want to force a rebuilding of the music on hold files or if the music on hold entries or server entries have changed then this should be set to Y. After the music on hold files have been synchronized and reloaded then this will be changed to N. Default is Y."); ?>

<BR>
<A NAME="servers-sounds_update">
<BR>
<B><?php echo _QXZ("Sounds Update"); ?> -</B><?php echo _QXZ("If you want to force a check of the sound files on this server, and the central audio store is enabled as a system setting, then this field will allow the sounds updater to run at the next top of the minute. Any time an audio file is uploaded from the web interface this is automatically set to Y for all servers that have Asterisk active. Default is N."); ?>

<BR>
<A NAME="servers-recording_limit">
<BR>
<B><?php echo _QXZ("Recording Limit"); ?> -</B><?php echo _QXZ("This field is where you set the maximum number of minutes that a call recording initiated by the system can be. Default is 60 minutes. This setting also limits the amount of time a 3-way call that has been left by an agent will stay up before it is terminated."); ?>

<BR>
<A NAME="servers-carrier_logging_active">
<BR>
<B><?php echo _QXZ("Carrier Logging Active"); ?> -</B><?php echo _QXZ("This setting allows you to log all hangup return codes for any outbound list dialing calls that you are placing. Default is N."); ?>

<BR>
<A NAME="servers-gather_asterisk_output">
<BR>
<B><?php echo _QXZ("Gather Asterisk Output"); ?> -</B><?php echo _QXZ("This setting allows you to activate a process that can run every 5 minutes on an active asterisk server and log the SIP/IAX peers and registry output along with the last 1000 lines of Asterisk CLI output. This output is then available to be displayed in the Asterisk Output Report on the Admin Utilities page. Default is N for inactive."); ?>

<BR>
<A NAME="servers-conf_qualify">
<BR>
<B><?php echo _QXZ("Conf Qualify"); ?> -</B><?php echo _QXZ("This setting allows you to add or remove the qualify entries in the Asterisk conf files. Default is Y for active."); ?>

<BR>
<A NAME="servers-custom_dialplan_entry">
<BR>
<B><?php echo _QXZ("Custom Dialplan Entry"); ?> -</B><?php echo _QXZ("This field allows you to enter in any dialplan elements that you want for the server, the lines will be added to the default context."); ?>






<BR><BR><BR><BR>

<B><FONT SIZE=3>conf_templates <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="conf_templates-template_id">
<BR>
<B><?php echo _QXZ("Template ID"); ?> -</B><?php echo _QXZ("This field needs to be at least 2 characters in length and no more than 15 characters in length, no spaces. This is the ID that will be used to identify the conf template throughout the system."); ?>

<BR>
<A NAME="conf_templates-template_name">
<BR>
<B><?php echo _QXZ("Template Name"); ?> -</B><?php echo _QXZ("This is the descriptive name of the conf file template entry."); ?>

<BR>
<A NAME="conf_templates-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="conf_templates-template_contents">
<BR>
<B><?php echo _QXZ("Template Contents"); ?> -</B><?php echo _QXZ("This field is where you can enter in the specific settings to be used by all phones and-or carriers that are set to use this conf template. Fields that should NOT be included in this box are: secret, accountcode, account, username and mailbox."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>server_carriers <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="server_carriers-carrier_id">
<BR>
<B><?php echo _QXZ("Carrier ID"); ?> -</B><?php echo _QXZ("This field needs to be at least 2 characters in length and no more than 15 characters in length, no spaces. This is the ID that will be used to identify the carrier for this specific entry throughout the system."); ?>

<BR>
<A NAME="server_carriers-carrier_name">
<BR>
<B><?php echo _QXZ("Carrier Name"); ?> -</B><?php echo _QXZ("This is the descriptive name of the carrier entry."); ?>

<BR>
<A NAME="server_carriers-carrier_description">
<BR>
<B><?php echo _QXZ("Carrier Description"); ?> -</B><?php echo _QXZ("This is put in the comments of the asterisk conf files above the dialplan and account entries. Maximum 255 characters."); ?>

<BR>
<A NAME="server_carriers-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="server_carriers-registration_string">
<BR>
<B><?php echo _QXZ("Registration String"); ?> -</B><?php echo _QXZ("This field is where you can enter in the exact string needed in the IAX or SIP configuration file to register to the provider. Optional but highly recommended if your carrier allows registration."); ?>

<BR>
<A NAME="server_carriers-template_id">
<BR>
<B><?php echo _QXZ("Template ID"); ?> -</B><?php echo _QXZ("This optional field allows you to choose a conf file template for this carrier entry."); ?>

<BR>
<A NAME="server_carriers-account_entry">
<BR>
<B><?php echo _QXZ("Account Entry"); ?> -</B><?php echo _QXZ("This field is used if you have not selected a template to use, and it is where you can enter in the specific account settings to be used for this carrier. If you will be taking in inbound calls from this carrier trunk you might want to set the context=trunkinbound within this field so that you can use the DID handling process within the system."); ?>

<BR>
<A NAME="server_carriers-protocol">
<BR>
<B><?php echo _QXZ("Protocol"); ?> -</B><?php echo _QXZ("This field allows you to define the protocol to use for the carrier entry. Currently only IAX and SIP are supported."); ?>

<BR>
<A NAME="server_carriers-globals_string">
<BR>
<B><?php echo _QXZ("Globals String"); ?> -</B><?php echo _QXZ("This optional field allows you to define a global variable to use for the carrier in the dialplan."); ?>

<BR>
<A NAME="server_carriers-dialplan_entry">
<BR>
<B><?php echo _QXZ("Dialplan Entry"); ?> -</B><?php echo _QXZ("This optional field allows you to define a set of dialplan entries to use for this carrier."); ?>

<BR>
<A NAME="server_carriers-server_ip">
<BR>
<B><?php echo _QXZ("Server IP"); ?> -</B><?php echo _QXZ("This is the server that this specific carrier record is associated with. If you set this to 0.0.0.0 then this carrier entry will be put on all active asterisk servers."); ?>

<BR>
<A NAME="server_carriers-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This defines whether the carrier will be included in the auto-generated conf files or not."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>CONFERENCES <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="conferences-conf_exten">
<BR>
<B><?php echo _QXZ("Conference Number"); ?> -</B><?php echo _QXZ("This field is where you put the meetme conference dialplan number. It is also recommended that the meetme number in meetme.conf matches this number for each entry. This is for the conferences in the astGUIclient user screen and is used for leave-3way-call functionality in the system."); ?>

<BR>
<A NAME="conferences-server_ip">
<BR>
<B><?php echo _QXZ("Server IP"); ?> -</B><?php echo _QXZ("The menu where you select the Asterisk server that this conference will be on.");




if ($SSoutbound_autodial_active > 0)
	{
	?>
	<BR><BR><BR><BR>

	<B><FONT SIZE=3>SERVER_TRUNKS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
	<A NAME="server_trunks">
	<BR>
	<B><?php echo _QXZ("Server Trunks allows you to restrict the outgoing lines that are used on this server for campaign dialing on a per-campaign basis. You have the option to reserve a specific number of lines to be used by only one campaign as well as allowing that campaign to run over its reserved lines into whatever lines remain open, as long at the total lines used by the system on this server is less than the Max Trunks setting. Not having any of these records will allow the campaign that dials the line first to have as many lines as it can get under the Max Trunks setting."); ?></B>
	<?php
	}
?>




<BR><BR><BR><BR>

<B><FONT SIZE=3>SYSTEM_SETTINGS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="settings-use_non_latin">
<BR>
<B><?php echo _QXZ("Use Non-Latin"); ?> -</B><?php echo _QXZ("This option allows you to default the web display script to use UTF8 characters and not do any latin-character-family regular expression filtering or display formatting. Default is 0."); ?>

<BR>
<A NAME="settings-enable_languages">
<BR>
<B><?php echo _QXZ("Enable Languages"); ?> -</B><?php echo _QXZ("This setting allows you to enable non-English language translations on the system. A new section called Languages under the Admin section will also be available to manager Languages. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-language_method">
<BR>
<B><?php echo _QXZ("Language Method"); ?> -</B><?php echo _QXZ("This setting defines how the language translation works when pages are loaded. The MYSQL method performs a database query for every display of a phrase in the interface. The DISABLED method will always display -default English- no matter what other settings are set to. Default is DISABLED."); ?>

<BR>
<A NAME="settings-default_language">
<BR>
<B><?php echo _QXZ("Default Language"); ?> -</B><?php echo _QXZ("This is the language that the agent and administrative interface will default to before the agent logs in. Default is -default English-."); ?>

<BR>
<A NAME="settings-webroot_writable">
<BR>
<B><?php echo _QXZ("Webroot Writable"); ?> -</B><?php echo _QXZ("This setting allows you to define whether temp files and authentication files should be placed in the webroot on your web server. Default is 1."); ?>

<BR>
<A NAME="settings-agent_disable">
<BR>
<B><?php echo _QXZ("Agent Disable Display"); ?> -</B><?php echo _QXZ("This field is used to select when to show an agent notices when their session has been disabled by the system, a manager action or by an external measure. The NOT_ACTIVE setting will disable the message on the agents screen. The LIVE_AGENT setting will only display the disabled message when the agents auto_calls record has been removed, such as during a force logout or emergency logout. Default is ALL."); ?>

<BR>
<A NAME="settings-frozen_server_call_clear">
<BR>
<B><?php echo _QXZ("Clear Frozen Calls"); ?> -</B><?php echo _QXZ("This option can enable the ability for the general Reports page and the optional AST_timecheck.pl script to clear out the auto_calls entries for a frozen server so they do not affect call routing. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-allow_sipsak_messages">
<BR>
<B><?php echo _QXZ("Allow SIPSAK Messages"); ?> -</B><?php echo _QXZ("If set to 1, this will allow the sipsak phones table setting to work if the phone is set to the SIP protocol. The server will send messages to the SIP phone to display on the phone display when logged into the system. This feature only works with SIP phones and requires sipsak application to be installed on the web server that the agent is logged into. Default is 0. "); ?>

<BR>
<A NAME="settings-vdc_agent_api_active">
<BR>
<B><?php echo _QXZ("Agent API Active"); ?> -</B><?php echo _QXZ("If set to 1, this will allow the Agent API interface to function. Default is 0. "); ?>

<BR>
<A NAME="settings-admin_modify_refresh">
<BR>
<B><?php echo _QXZ("Admin Modify Auto-Refresh"); ?> -</B><?php echo _QXZ("This is the refresh interval in seconds of the modify screens in this admin interface. Setting this to 0 will disable it, setting it below 5 will mostly make the modify screens unusable because they will refresh too quickly to change fields. This option is useful in situations where more than one manager is controlling settings on an active campaign or in-group so that the settings are refreshed frequently. Default is 0."); ?>

<BR>
<A NAME="settings-nocache_admin">
<BR>
<B><?php echo _QXZ("Admin No-Cache"); ?> -</B><?php echo _QXZ("Setting this to 1 will set all admin pages to web browser no-cache, so every screen has to be reloaded every time it is viewed, even if clicking back on the browser. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-admin_row_click">
<BR>
<B><?php echo _QXZ("Admin Row Click"); ?> -</B><?php echo _QXZ("Setting this to 1 will allow you to click on a row to go to a record link in addition to being able to go to the record link by clicking on the text link. Default is 1 for enabled."); ?>

<BR>
<A NAME="settings-enable_agc_xfer_log">
<BR>
<B><?php echo _QXZ("Enable Agent Transfer Logfile"); ?> -</B><?php echo _QXZ("This option will log to a text logfile on the webserver every time a call is transferred to an agent. Default is 0, disabled."); ?>

<BR>
<A NAME="settings-enable_agc_dispo_log">
<BR>
<B><?php echo _QXZ("Enable Agent Disposition Logfile"); ?> -</B><?php echo _QXZ("This option will log to a text logfile on the webserver every time a call is dispositioned by an agent. Default is 0, disabled."); ?>

<BR>
<A NAME="settings-timeclock_end_of_day">
<BR>
<B><?php echo _QXZ("Timeclock End Of Day"); ?> -</B><?php echo _QXZ("This setting defines when all users are to be auto logged out of the timeclock system. Only runs once a day. must be only 4 digits 2 digit hour and 2 digit minutes in 24 hour time. Default is 0000."); ?>

<BR>
<A NAME="settings-default_local_gmt">
<BR>
<B><?php echo _QXZ("Default Local GMT"); ?> -</B><?php echo _QXZ("This setting defines what will be used by default when new phones and servers are added using this admin web interface. Default is -5."); ?>

<BR>
<A NAME="settings-default_voicemail_timezone">
<BR>
<B><?php echo _QXZ("Default Voicemail Zone"); ?> -</B><?php echo _QXZ("This setting defines what zone will be used by default when new phones and voicemail boxes are created. The list of available zones is directly taken from the voicemail.conf file. Default is eastern."); ?>

<BR>
<A NAME="settings-agents_calls_reset">
<BR>
<B><?php echo _QXZ("Agents Calls Reset"); ?> -</B><?php echo _QXZ("This setting defines whether the logged-in agents and active phone calls records are to be reset at the timeclock end of day. Default is 1 for enabled."); ?>

<BR>
<A NAME="settings-timeclock_last_reset_date">
<BR>
<B><?php echo _QXZ("Timeclock Last Auto Logout"); ?> -</B><?php echo _QXZ("This field displays the date of the last auto-logout."); ?>

<BR>
<A NAME="settings-vdc_header_date_format">
<BR>
<B><?php echo _QXZ("Agent Screen Header Date Format"); ?> -</B><?php echo _QXZ("This menu allows you to choose the format of the date and time that shows up at the top of the agent screen. The options for this setting are: default is MS_DASH_24HR"); ?><BR>
MS_DASH_24HR  2008-06-24 23:59:59 - <?php echo _QXZ("Default date format with year month day followed by 24 hour time"); ?><BR>
US_SLASH_24HR 06/24/2008 23:59:59 - <?php echo _QXZ("USA date format with month day year followed by 24 hour time"); ?><BR>
EU_SLASH_24HR 24/06/2008 23:59:59 - <?php echo _QXZ("European date format with day month year followed by 24 hour time"); ?><BR>
AL_TEXT_24HR  JUN 24 23:59:59 - <?php echo _QXZ("Text date format with abbreviated month day followed by 24 hour time"); ?><BR>
MS_DASH_AMPM  2008-06-24 11:59:59 PM - <?php echo _QXZ("Default date format with year month day followed by 12 hour time"); ?><BR>
US_SLASH_AMPM 06/24/2008 11:59:59 PM - <?php echo _QXZ("USA date format with month day year followed by 12 hour time"); ?><BR>
EU_SLASH_AMPM 24/06/2008 11:59:59 PM - <?php echo _QXZ("European date format with day month year followed by 12 hour time"); ?><BR>
AL_TEXT_AMPM  JUN 24 11:59:59 PM - <?php echo _QXZ("Text date format with abbreviated month day followed by 12 hour time"); ?><BR>

<BR>
<A NAME="settings-vdc_customer_date_format">
<BR>
<B><?php echo _QXZ("Agent Screen Customer Date Format"); ?> -</B><?php echo _QXZ("This menu allows you to choose the format of the customer date and time that shows up at the top of the Customer Information section of the agent screen. The options for this setting are: default is AL_TEXT_AMPM"); ?><BR>
MS_DASH_24HR  2008-06-24 23:59:59 - <?php echo _QXZ("Default date format with year month day followed by 24 hour time"); ?><BR>
US_SLASH_24HR 06/24/2008 23:59:59 - <?php echo _QXZ("USA date format with month day year followed by 24 hour time"); ?><BR>
EU_SLASH_24HR 24/06/2008 23:59:59 - <?php echo _QXZ("European date format with day month year followed by 24 hour time"); ?><BR>
AL_TEXT_24HR  JUN 24 23:59:59 - <?php echo _QXZ("Text date format with abbreviated month day followed by 24 hour time"); ?><BR>
MS_DASH_AMPM  2008-06-24 11:59:59 PM - <?php echo _QXZ("Default date format with year month day followed by 12 hour time"); ?><BR>
US_SLASH_AMPM 06/24/2008 11:59:59 PM - <?php echo _QXZ("USA date format with month day year followed by 12 hour time"); ?><BR>
EU_SLASH_AMPM 24/06/2008 11:59:59 PM - <?php echo _QXZ("European date format with day month year followed by 12 hour time"); ?><BR>
AL_TEXT_AMPM  JUN 24 11:59:59 PM - <?php echo _QXZ("Text date format with abbreviated month day followed by 12 hour time"); ?><BR>

<BR>
<A NAME="settings-vdc_header_phone_format">
<BR>
<B><?php echo _QXZ("Agent Screen Customer Phone Format"); ?> -</B><?php echo _QXZ("This menu allows you to choose the format of the customer phone number that shows up in the status section of the agent screen. The options for this setting are: default is US_PARN"); ?><BR>
US_DASH 000-000-0000 - <?php echo _QXZ("USA dash separated phone number"); ?><BR>
US_PARN (000)000-0000 - <?php echo _QXZ("USA dash separated number with area code in parenthesis"); ?><BR>
MS_NODS 0000000000 - <?php echo _QXZ("No formatting"); ?><BR>
UK_DASH 00 0000-0000 - <?php echo _QXZ("UK dash separated phone number with space after city code"); ?><BR>
AU_SPAC 000 000 000 - <?php echo _QXZ("Australia space separated phone number"); ?><BR>
IT_DASH 0000-000-000 - <?php echo _QXZ("Italy dash separated phone number"); ?><BR>
FR_SPAC 00 00 00 00 00 - <?php echo _QXZ("France space separated phone number"); ?><BR>

<BR>
<A NAME="settings-agent_xfer_park_3way">
<BR>
<B><?php echo _QXZ("Agent Screen Park Xfer Button"); ?> -</B><?php echo _QXZ("This option defines whether the agent screen can have a button in the Transfer Conference frame that will allow the agent to park a 3way call. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-agent_soundboards">
<BR>
<B><?php echo _QXZ("Agent Soundboards"); ?> -</B><?php echo _QXZ("This option allows you to create agent soundboards that allow an agent in the user screen to click on audio files to have them play in their session. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-enable_pause_code_limits">
<BR>
<B><?php echo _QXZ("Enable Pause Code Time Limits"); ?> -</B><?php echo _QXZ("This option allows you to be able to set the Time Limit field in campaign pause codes, which will change the color of the agent on the Real-Time Report if they are in that pause code for more than the defined amount of seconds. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-agentonly_callback_campaign_lock">
<BR>
<B><?php echo _QXZ("Agent Only Callback Campaign Lock"); ?> -</B><?php echo _QXZ("This option defines whether AGENTONLY callbacks are locked to the campaign that the agent originally created them under. Setting this to 1 means that the agent can only dial them from the campaign they were set under, 0 means that the agent can access them no matter what campaign they are logged into. Default is 1."); ?>

<BR>
<A NAME="settings-callback_time_24hour">
<BR>
<B><?php echo _QXZ("Callback Time 24 Hours"); ?> -</B><?php echo _QXZ("This option defines whether the agent sees 12 hour time with AM PM options or 24 hour time on the Callback setting screen in the agent interface. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-anyone_callback_inactive_lists">
<BR>
<B><?php echo _QXZ("Anyone Callback Inactive Lists"); ?> -</B><?php echo _QXZ("This option defines whether an ANYONE callback within an inactive list will be placed in the hopper to be dialed or not. The default option will place ANYONE scheduled callbacks from inactive lists into the hopper for up to one minute before removing them from the hopper. Using the NO_ADD_TO_HOPPER option will prevent ANYONE scheduled callbacks from inactive lists from ever being put into the hopper while the list is inactive. The KEEP_IN_HOPPER option will put ANYONE scheduled callbacks from inactive lists into the hopper and will make sure they stay in the hopper until they are dialed, or they are no longer dialable due to a Call Time setting. This feature only affects campaigns that use the dial hopper. Default is default."); ?>

<BR>
<A NAME="settings-sounds_central_control_active">
<BR>
<B><?php echo _QXZ("Central Sound Control Active"); ?> -</B><?php echo _QXZ("This option defines whether the sound synchronization system is active across all servers. Default is 0 for inactive."); ?>

<BR>
<A NAME="settings-sounds_web_server">
<BR>
<B><?php echo _QXZ("Sounds Web Server"); ?> -</B><?php echo _QXZ("This is the server name or IP address of the web server that will be handling the sound files on this system, this must match the server name or IP of the machine you are trying to access the audio_store.php webpage on or it will not work. Default is 127.0.0.1."); ?>

<BR>
<A NAME="settings-sounds_web_directory">
<BR>
<B><?php echo _QXZ("Sounds Web Directory"); ?> -</B><?php echo _QXZ("This auto-generated directory name is created at random by the system as the place that the audio store will be kept. All audio files will reside in this directory."); ?>

<BR>
<A NAME="settings-meetme_enter_login_filename">
<BR>
<B><?php echo _QXZ("Custom Agent Login Sound"); ?> -</B><?php echo _QXZ("This is a systemwide feature that only works on Asterisk 1.8 servers or higher. This allows you to set an audio file for your agents to hear after their phone has connected to the server after they have logged in to the agent screen. If you want to have this audio prompt be the only prompt that the agent hears, then you will need to copy the sip-silence audio files over the only-person audio files. If you want the agent to hear no prompt when they login then also set this field to sip-silence.  Default is EMPTY."); ?>

<BR>
<A NAME="settings-meetme_enter_leave3way_filename">
<BR>
<B><?php echo _QXZ("Custom Agent Leave 3way Sound"); ?> -</B><?php echo _QXZ("This is a systemwide feature that only works on Asterisk 1.8 servers or higher. This allows you to set an audio file for your agents to hear after they have left a 3way conference in the agent screen. If you want to have this audio prompt be the only prompt that the agent hears, then you will need to copy the sip-silence audio files over the only-person audio files. If you want the agent to hear no prompt after they leave a 3way call then also set this field to sip-silence. Default is EMPTY."); ?>

<BR>
<A NAME="settings-admin_home_url">
<BR>
<B><?php echo _QXZ("Admin Home URL"); ?> -</B><?php echo _QXZ("This is the URL or web site address that you will go to if you click on the HOME link at the top of the admin.php page."); ?>

<BR>
<A NAME="settings-admin_web_directory">
<BR>
<B><?php echo _QXZ("Admin Web Directory"); ?> -</B><?php echo _QXZ("This is the web directory that your administration web content, like admin.php, are in. To figure out your Admin web directory, it is everything that is between the domain name and the admin.php in the URL on this page, without the beginning and ending slashes."); ?>

<BR>
<A NAME="settings-agent_script">
<BR>
<B><?php echo _QXZ("Agent Screen Script"); ?> -</B><?php echo _QXZ("This is the PHP script page of the agent screen."); ?>

<BR>
<A NAME="settings-active_voicemail_server">
<BR>
<B><?php echo _QXZ("Active Voicemail Server"); ?> -</B><?php echo _QXZ("In multi-server systems, this is the server that will handle all voicemail boxes. This server is also where the dial-in generated prompts will be uploaded from, the 8168 recordings."); ?>

<BR>
<A NAME="settings-allow_voicemail_greeting">
<BR>
<B><?php echo _QXZ("Allow Voicemail Greeting Chooser"); ?> -</B><?php echo _QXZ("If this setting is enabled it will allow you to choose an audio file from the audio store to be played as the voicemail greeting to a specific voicemail box. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-outbound_autodial_active">
<BR>
<B><?php echo _QXZ("Outbound Auto-Dial Active"); ?> -</B><?php echo _QXZ("This option allows you to enable or disable outbound auto-dialing within the system, setting this field to 0 will remove the LISTS and FILTERS sections and many fields from the Campaign Modification screens. Manual entry dialing will still be allowable from within the agent screen, but no list dialing will be possible. Default is 1 for active."); ?>

<BR>
<A NAME="settings-ofcom_uk_drop_calc">
<BR>
<B><?php echo _QXZ("UK OFCOM Drop Calculation"); ?> -</B><?php echo _QXZ("This option allows you to enable the new UK OFCOM Drop calculation formula for individual campaigns. As of December 2015, OFCOM in the UK changed their method for calculating the drop,or abandon, percentage for an outbound dialing campaign. The new formula includes an estimate of the number of drops that were answering machines. They do this by using the agent-answered percentage of answering machines and subtracting that percentage from the number of drops. Then that new drop number is divided by the total agent-answered human-answered calls PLUS the number of drops. This differs in several ways from the way it had been done, as well as the way the drop percentage has been calculated in the USA and Canada. This new UK drop calculation method can be activated as a system setting AND a campaign option. Both must be enabled for the campaign to use the new method. In order for agent-statused answering machines to be calculated properly, we have added an answering machine status flag that is used to gather those statuses. Default is 0 for inactive."); ?>

<BR>
<A NAME="settings-disable_auto_dial">
<BR>
<B><?php echo _QXZ("Disable Auto-Dial"); ?> -</B><?php echo _QXZ("This option is only editable by a system administrator. It will not remove any options from the management web interface, but it will prevent any auto-dialing of leads from happening on the system. Only Manual Dial outbound calls triggered directly by agents will function if this option is enabled. Default is 0 for inactive."); ?>

<BR>
<A NAME="settings-auto_dial_limit">
<BR>
<B><?php echo _QXZ("Ratio Dial Limit"); ?> -</B><?php echo _QXZ("This is the maximum limit of the auto dial level in the campaign screen."); ?>

<BR>
<A NAME="settings-outbound_calls_per_second">
<BR>
<B><?php echo _QXZ("Max FILL Calls per Second"); ?> -</B><?php echo _QXZ("This setting determines the maximum number of calls that can be placed by the auto-FILL outbound auto-dialing script on for all servers, per second. Must be from 1 to 200. Default is 40."); ?>

<BR>
<A NAME="settings-web_loader_phone_length">
<BR>
<B><?php echo _QXZ("Web Lead Loader Phone Length"); ?> -</B><?php echo _QXZ("This setting allows you to only allow phone numbers of a specific length into the system when loading leads with the web lead loader. The CHOOSE option allows a manager to optionally select a number of phone number digits to check the length by when loading leads one file at a time. Selecting a number option will not allow a manager to choose while loading leads, the check will be used every time the web lead loader is used. Default is DISABLED."); ?>

<BR>
<A NAME="settings-allow_custom_dialplan">
<BR>
<B><?php echo _QXZ("Allow Custom Dialplan Entries"); ?> -</B><?php echo _QXZ("This option allows you to enter custom dialplan lines into Call Menus, Servers and System Settings. Default is 0 for inactive."); ?>

<BR>
<A NAME="settings-pllb_grouping_limit">
<BR>
<B><?php echo _QXZ("PLLB Grouping Limit"); ?> -</B><?php echo _QXZ("Phone Login Load Balancing Grouping Limit. If PLLB Grouping is set to CASCADING at the campaign level then this setting will determine the number of agents acceptable on each server across all campaigns. Default is 100."); ?>

<BR>
<A NAME="settings-generate_cross_server_exten">
<BR>
<B><?php echo _QXZ("Generate Cross-Server Phone Extensions"); ?> -</B><?php echo _QXZ("This option if set to 1 will generate dialplan entries for every phone on a multi-server system. Default is 0 for inactive."); ?>

<BR>
<A NAME="settings-usacan_phone_dialcode_fix">
<BR>
<B><?php echo _QXZ("USA-Canada Phone Number Dialcode Fix"); ?> -</B><?php echo _QXZ("This option if set to 1 will trigger a process that will run at the Timeclock End of Day and will check all phone numbers to populate the dial code, or phone code, field with a 1 if it is missing as well as remove a leading 1 from the phone number field if it is present. Default is 0 for inactive."); ?>

<BR>
<A NAME="settings-default_phone_code">
<BR>
<B><?php echo _QXZ("Default Phone Code"); ?> -</B><?php echo _QXZ("This setting will be used to fill in the Phone Code field on manual dial calls from the agent screen, also called Dial Code in some places. Default is 1."); ?>

<BR>
<A NAME="settings-user_territories_active">
<BR>
<B><?php echo _QXZ("User Territories Active"); ?> -</B><?php echo _QXZ("This setting allows you to enable the User Territories settings from the user modification screen. This feature was added to allow for more integration with a customized Vtiger installation but can have applications in system by itself as well. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-enable_second_webform">
<BR>
<B><?php echo _QXZ("Enable Second Webform"); ?> -</B><?php echo _QXZ("This setting allows you to have a second web form for campaigns and in-groups in the agent interface. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-enable_third_webform">
<BR>
<B><?php echo _QXZ("Enable Third Webform"); ?> -</B><?php echo _QXZ("This setting allows you to have a third web form for campaigns and in-groups in the agent interface. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-enable_tts_integration">
<BR>
<B><?php echo _QXZ("Enable TTS Integration"); ?> -</B><?php echo _QXZ("This setting allows you to enable Text To Speech integration with Cepstral. This is currently only available for outbound Survey type campaigns. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-callcard_enabled">
<BR>
<B><?php echo _QXZ("Enable CallCard"); ?> -</B><?php echo _QXZ("This setting enables the CallCard features to allow for callers to use pin numbers and card_ids that have a balance of minutes and those balances can have agent talk time on customer calls to in-groups deducted. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-test_campaign_calls">
<BR>
<B><?php echo _QXZ("Enable Campaign Test Calls"); ?> -</B><?php echo _QXZ("This setting enables the ability to enter a phone code and phone number into fields at the bottom of the Campaign Detail screen and place a phone call to that number as if it were a lead being auto-dialed in the system. The phone number will be stored as a new lead in the manual dial list ID list. The campaign must be active for this feature to be enabled, and it is recommended that the lists assigned to the campaign all be set to inactive. The dial prefix, dial timeout and all other dialing related features, except for DNC and call time options, will affect the dialing of the test number. The phone call will be placed on the server selected as the voicemail server in the system settings. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-did_system_filter">
<BR>
<B><?php echo _QXZ("DID System Filter"); ?> -</B><?php echo _QXZ("This setting enables the special did_system_filter DID entry. The filter settings in this DID entry will be applied to all incoming calls to the system, prior to any other actions on the call. This feature is commonly used for system-wide inbound blacklists, or filtering of do-not-contact phone numbers. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-user_new_lead_limit">
<BR>
<B><?php echo _QXZ("New Leads Per List Limit"); ?> -</B><?php echo _QXZ("This setting enables the new lead limits per list to be set on the list modify page and the user list new lead limit page. This feature will only work properly if the campaign is set to either the MANUAL or INBOUND_MAN Dial Method and No Hopper dialing is enabled. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-custom_fields_enabled">
<BR>
<B><?php echo _QXZ("Enable Custom List Fields"); ?> -</B><?php echo _QXZ("This setting enables the custom list fields feature that allows for custom data fields to be defined in the administration web interface on a per-list basis and then have those fields available in a FORM tab to the agent in the agent web interface. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-expanded_list_stats">
<BR>
<B><?php echo _QXZ("Enable Expanded List Stats"); ?> -</B><?php echo _QXZ("This setting enables two additional columns to be displayed on most of the List status breakdown tables on the list modification and campaign modification pages. Penetration is defined as the percent of leads that are at or above the campaign Call Count Limit and-or the status is marked as Completed. Default is 1 for enabled."); ?>

<BR>
<A NAME="settings-hide_inactive_lists">
<BR>
<B><?php echo _QXZ("Hide Inactive Lists"); ?> -</B><?php echo _QXZ("This setting allows you to hide inactive lists from the Lists Listing page. Similar to the default Users feature, a link to display all lists will be available at the top of the listings section. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-country_code_list_stats">
<BR>
<B><?php echo _QXZ("Country Code List Stats"); ?> -</B><?php echo _QXZ("This setting if enabled will show a country code breakdown summary on the list modify screen. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-enable_did_entry_list_id">
<BR>
<B><?php echo _QXZ("Enable DID Entry List ID"); ?> -</B><?php echo _QXZ("This setting if enabled will allow a manager to define an entry list id to use on the DID modify screen. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-allow_manage_active_lists">
<BR>
<B><?php echo _QXZ("Lead Manager Active Lists"); ?> -</B><?php echo _QXZ("This setting if enabled will allow a manager to select active lists for modification within the Lead Management admin utilities. We do not recommend enabling this option since it can cause issues with leads that could be part of active calling within the system. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-expired_lists_inactive">
<BR>
<B><?php echo _QXZ("Expired Lists Auto Inactive"); ?> -</B><?php echo _QXZ("This setting if enabled will automatically change lists that have an expiration date set to a past date to Active equals N. This is performed through both a check every time the List Modify screen is loaded for a specific list as well as a once an hour check on all lists. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-enable_gdpr_download_deletion">
<BR>
<B><?php echo _QXZ("Enable GDPR-compliant Data Download Deletion"); ?> -</B><?php echo _QXZ("This setting if enabled will allow for the complete download and/or deletion of all customer data for a particular lead, in compliance with the General Data Protection Regulation (GDPR). Default is 0 for disabled.  A setting of 1 will enable downloading data, and a setting of 2 will enable not just downloading, but also deletion of data, including any recordings."); ?>

<BR>
<A NAME="settings-enable_drop_lists">
<BR>
<B><?php echo _QXZ("Enable Drop Lists"); ?> -</B><?php echo _QXZ("This setting if enabled will make the Drop Lists feature appear under the LISTS menu. This feature set can take dropped call log records and create new leads in a list from multiple inbound groups. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-source_id_display">
<BR>
<B><?php echo _QXZ("Admin Lead Source ID Display"); ?> -</B><?php echo _QXZ("This setting if enabled will show the source_id field of a lead in the hopper display and the Modify Lead page. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-agent_debug_logging">
<BR>
<B><?php echo _QXZ("Agent Screen Debug Logging"); ?> -</B><?php echo _QXZ("This setting if enabled will log almost all agent screen mouse clicks and AJAX processes triggered by the agent screen. To enable for all agents, set this option to 1. To enable only for one agent on the system, set this option to the user that you want to log. Warning, this feature can log hundreds of entries per phone call, so use with caution. These agent debug records are deleted after 7 days. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-enhanced_disconnect_logging">
<BR>
<B><?php echo _QXZ("Enhanced Disconnect Logging"); ?> -</B><?php echo _QXZ("This setting enables logging of calls that get a CONGESTION signal with a cause code of 1, 19, 21, 34 or 38. We usually do not recommend enabling this in the USA. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-cache_carrier_stats_realtime">
<BR>
<B><?php echo _QXZ("Cached Realtime Carrier Stats"); ?> -</B><?php echo _QXZ("This setting if enabled will change the real-time report from gathering the carrier log stats every time it is refreshed on every screen it is running on, to a cached set of carrier log stats that are refreshed once per minute. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-campaign_cid_areacodes_enabled">
<BR>
<B><?php echo _QXZ("Enable CID Groups and Campaign Areacode CID"); ?> -</B><?php echo _QXZ("This setting enables the ability to set specific outbound callerid numbers to be used per areacode for a campaign, as well as CID Groups which allows both per areacode and per state options for groups of CallerID numbers for one or multiple campaigns. Default is 1 for enabled."); ?>

<BR>
<A NAME="settings-did_ra_extensions_enabled">
<BR>
<B><?php echo _QXZ("Enable Remote Agent Extension Overrides"); ?> -</B><?php echo _QXZ("This setting enables DIDs to have extension overrides for remote agent routed calls through in-groups. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-agent_whisper_enabled">
<BR>
<B><?php echo _QXZ("Enable Agent Whisper Monitoring"); ?> -</B><?php echo _QXZ("This setting allows a manager to be able to speak to a logged in agent without the customer being able to hear them. WARNING, this feature is considered experimental and may not function properly in some cases. Older versions of Asterisk have shown to have serious issues at times with this feature and can cause Asterisk to freeze or crash. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-user_hide_realtime_enabled">
<BR>
<B><?php echo _QXZ("Enable User Hide RealTime"); ?> -</B><?php echo _QXZ("This setting allows a User Modify setting to be changed to allow a user to be hidden from all managers in the Real-Time Report. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-manual_auto_next">
<BR>
<B><?php echo _QXZ("Enable Manual Dial Auto Next"); ?> -</B><?php echo _QXZ("This setting allows the campaign setting to be enabled forcing a manual dial after X seconds in a manual or inbound manual dial mode. Default is 0 for disabled."); ?>

<BR>
<A NAME="contact_information">
<A NAME="settings-contacts_enabled">
<BR>
<B><?php echo _QXZ("Contacts Enabled"); ?> -</B><?php echo _QXZ("This setting enables the Contacts sub-section in Admin which allows a manager to add modify or delete contacts in the system that can be used as part of a Custom Transfer in a campaign where an agent can search for contacts by first name last name or office number and then select one of many numbers associated with that contact. This feature is often used by operators or in switchboard functions where the user would need to transfer a call to a non-agent phone. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-call_menu_qualify_enabled">
<BR>
<B><?php echo _QXZ("Call Menu Qualify Enabled"); ?> -</B><?php echo _QXZ("This setting enables the option in Call Menus to put a SQL qualification on the people that hear that call menu. For more information on how that feature works, view the help for Call Menus. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-alt_ivr_logging">
<BR>
<B><?php echo _QXZ("Call Menu Alt DTMF Logging"); ?> -</B><?php echo _QXZ("This setting enables the option in Call Menus to log the DTMF responses to an alternate database table. This can be used in addition to the default DTMF lead logging option. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-allow_ip_lists">
<BR>
<B><?php echo _QXZ("Allow IP Lists"); ?> -</B><?php echo _QXZ("This setting allows the IP Lists admin section to be used, as well as the User Group options for web access whitelists and the System Settings option for a system-wide web blacklist. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-system_ip_blacklist">
<BR>
<B><?php echo _QXZ("System IP Blacklist"); ?> -</B><?php echo _QXZ("If Allow IP Lists above are enabled, this option will allow you to set an IP List as a blacklist for IP addresses that are not able to access the web resources of this system. Default DISABLED."); ?>

<BR>
<A NAME="settings-level_8_disable_add">
<BR>
<B><?php echo _QXZ("Level 8 Disable Add"); ?> -</B><?php echo _QXZ("This setting if enabled will prevent any level 8 user from adding or copying any record in the system, no matter what their user settings are. Excluded from these restrictions are the ability to add DNC and Filter Phone Groups numbers and the Add a New Lead page. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-script_remove_js">
<BR>
<B><?php echo _QXZ("Script Text Remove JS"); ?> -</B><?php echo _QXZ("This setting if enabled will remove any javascript that is included in the script text for a script. This is considered a security feature to prevent cross-site scripting, or XSS. Default is 1 for enabled."); ?>

<BR>
<A NAME="settings-admin_list_counts">
<BR>
<B><?php echo _QXZ("Admin List Counts Link"); ?> -</B><?php echo _QXZ("This setting gives you the option to display list counts by clicking on the -show lists leads counts- link at the top of the Lists listing and the Campaign modify screens. Default is 1 for enabled."); ?>

<BR>
<A NAME="settings-allow_emails">
<BR>
<B><?php echo _QXZ("Allow Emails"); ?> -</B><?php echo _QXZ("This is where you can set whether this system will be able to receive inbound emails in addition to phone calls."); ?>

<BR>
<A NAME="settings-allow_chats">
<BR>
<B><?php echo _QXZ("Allow Chats"); ?> -</B><?php echo _QXZ("This is where you can set whether this system will be able to receive incoming chats in addition to phone calls, as well as allow agent-to-agent and agent-to-manager chatting."); ?>

<BR>
<A NAME="settings-chat_timeout">
<BR>
<B><?php echo _QXZ("Chat Timeout"); ?> -</B><?php echo _QXZ("This is where you can set how long a customer chat can stay alive after the customer has navigated away from or closed their chat window. When the timeout is reached the chat is closed."); ?>

<BR>
<A NAME="settings-chat_url">
<BR>
<B><?php echo _QXZ("Chat URL"); ?> -</B><?php echo _QXZ("This is the location where you have placed the chat web pages for customer use."); ?>

<BR>
<A NAME="settings-agent_push_events">
<BR>
<B><?php echo _QXZ("Agent Push Events"); ?> -</B><?php echo _QXZ("This setting will enable the sending of events from agent screens to the URL defined below. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-agent_push_url">
<BR>
<B><?php echo _QXZ("Agent Push URL"); ?> -</B><?php echo _QXZ("If Agent Push Events are enabled above, this is the URL that the events will be sent to. The variables that you can use within the URL are --A--user--B--, --A--event--B--, --A--message--B--, --A--lead_id--B--. Since this function uses AJAX, it must reference a local script on the web server. If you want to reference an external web address then you should use the get2post.php script that can be found in the extras directory."); ?>

<BR>
<A NAME="settings-log_recording_access">
<BR>
<B><?php echo _QXZ("Log Recording Access"); ?> -</B><?php echo _QXZ("This option if enabled allows the logging of user access to call recordings. It also requires the User setting Access Recordings to be set to 1 to allow a user to access call recordings. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-enable_auto_reports">
<BR>
<B><?php echo _QXZ("Enable Automated Reports"); ?> -</B><?php echo _QXZ("This option if enabled allows you access to the Automated Reports section where you can set up reports to run at scheduled times and be delivered by email or FTP. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-first_login_trigger">
<BR>
<B><?php echo _QXZ("First Login Trigger"); ?> -</B><?php echo _QXZ("This setting allows for the initial configuration of the server screen to be shown to the administrator when they first log into the system."); ?>

<BR>
<A NAME="settings-default_phone_registration_password">
<BR>
<B><?php echo _QXZ("Default Phone Registration Password"); ?> -</B><?php echo _QXZ("This is the default registration password used when new phones are added to the system. Default is test."); ?>

<BR>
<A NAME="settings-default_phone_login_password">
<BR>
<B><?php echo _QXZ("Default Phone Login Password"); ?> -</B><?php echo _QXZ("This is the default phone web login password used when new phones are added to the system. Default is test."); ?>

<BR>
<A NAME="settings-default_server_password">
<BR>
<B><?php echo _QXZ("Default Server Password"); ?> -</B><?php echo _QXZ("This is the default server password used when new servers are added to the system. Default is test."); ?>

<BR>
<A NAME="settings-report_default_format">
<BR>
<B><?php echo _QXZ("Report Default Format"); ?> -</B><?php echo _QXZ("Determines whether the reports will default to display in text or html format. Default is TEXT."); ?>

<BR>
<A NAME="settings-slave_db_server">
<BR>
<B><?php echo _QXZ("Slave Database Server"); ?> -</B><?php echo _QXZ("If you have a MySQL slave database server then enter the local IP address for that server here. This option is currently only used by the selected reports in the next option and has nothing to do with automatically configuring MySQL master-slave replication. Default is empty for disabled."); ?>

<BR>
<A NAME="settings-reports_use_slave_db">
<BR>
<B><?php echo _QXZ("Reports to use Slave DB"); ?> -</B><?php echo _QXZ("This option allows you to select the reports that you want to have use the MySQL slave database as defined in the option above instead of the master database that your live system is running on. You must set up the MySQL slave replication before you can enable this option. Default is empty for disabled."); ?>


<BR>
<A NAME="settings-custom_reports_use_slave_db">
<BR>
<B><?php echo _QXZ("Custom Reports to use Slave DB"); ?> -</B><?php echo _QXZ("This option allows you to select the custom reports that you want to have use the MySQL slave database as defined in the option above instead of the master database that your live system is running on. You must set up the MySQL slave replication before you can enable this option. Default is empty for disabled."); ?>

<BR>
<A NAME="settings-default_field_labels">
<BR>
<B><?php echo _QXZ("Default Field Labels"); ?> -</B><?php echo _QXZ("These 19 fields allow you to set the name as it will appear in the agent interface as well as the administrative modify lead page. Default is empty which will use the hard-coded defaults in the agent interface. You can also set a label to ---HIDE--- to hide both the label and the field. Another option for most fields is ---READONLY--- which will display but not allow an agent to modify the field. One more option for most fields is ---REQUIRED--- which will force an agent to populate that field on all calls before being able to hang up and disposition each call. For the REQUIRED option to work, the campaign must have Allow Required Fields enabled."); ?>

<BR>
<A NAME="settings-admin_screen_colors">
<BR>
<B><?php echo _QXZ("Admin Screen Colors"); ?> -</B><?php echo _QXZ("This feature allows you to set different color schemes and logo for the administrative web screens. These can be defined in the Screen Colors section. Default is default, for the standard blue screen colors"); ?>

<BR>
<A NAME="settings-agent_screen_colors">
<BR>
<B><?php echo _QXZ("Agent Screen Colors"); ?> -</B><?php echo _QXZ("This feature allows you to set different color schemes and logo for the agent screen. These can be defined in the Screen Colors section. Default is default, for the standard blue screen colors"); ?>

<BR>
<A NAME="settings-agent_chat_screen_colors">
<BR>
<B><?php echo _QXZ("Agent Chat Screen Colors"); ?> -</B><?php echo _QXZ("This feature allows you to set the different colors that are used for the users chatting within the internal chat window in the agent screen. These can be defined in the Screen Colors section, and only the five standard and three alternate colors are used. Default is default, for a standard set of eight colors"); ?>

<BR>
<A NAME="settings-label_hide_field_logs">
<BR>
<B><?php echo _QXZ("Hide Label in Call Logs"); ?> -</B><?php echo _QXZ("If a label is set to ---HIDE--- then the agent call logs, if enabled on the campaign, will still show the field and data unless this option is set to Y. Default is N."); ?>

<BR>
<A NAME="settings-qc_features_active">
<BR>
<B><?php echo _QXZ("QC Features Active"); ?> -</B><?php echo _QXZ("This option allows you to enable or disable the QC or Quality Control features. Default is 0 for inactive."); ?>

<BR>
<A NAME="settings-default_webphone">
<BR>
<B><?php echo _QXZ("Default Webphone"); ?> -</B><?php echo _QXZ("If set to 1, this option will make all new phones created have Set As Webphone set to Y. Default is 0."); ?>

<BR>
<A NAME="settings-webphone_systemkey">
<BR>
<B><?php echo _QXZ("Webphone System Key"); ?> -</B><?php echo _QXZ("If your system or provider requires it, this is where the System Key for the webphone should be entered in. Default is empty."); ?>

<BR>
<A NAME="settings-default_codecs">
<BR>
<B><?php echo _QXZ("Default Codecs"); ?> -</B><?php echo _QXZ("You can define a comma delimited list of codecs to be set as the default codecs for all systems. Options for codecs include ulaw, alaw, gsm, g729, speex, g722, g723, g726, ilbc, ... Default is empty."); ?>

<BR>
<A NAME="settings-custom_dialplan_entry">
<BR>
<B><?php echo _QXZ("Custom Dialplan Entry"); ?> -</B><?php echo _QXZ("This field allows you to enter in any dialplan elements that you want for all of the asterisk servers, the lines will be added to the default context."); ?>

<BR>
<A NAME="settings-reload_dialplan_on_servers">
<BR>
<B><?php echo _QXZ("Reload Dialplan On Servers"); ?> -</B><?php echo _QXZ("This option allows you to force a reload of the dialplan on all Asterisk servers in the cluster. If you made changes in the Custom Dialplan Entry above you should set this to 1 and submit to have those changes go into effect on the servers."); ?>

<BR>
<A NAME="settings-noanswer_log">
<BR>
<B><?php echo _QXZ("No-Answer Log"); ?> -</B><?php echo _QXZ("This option will log the auto-dial calls that are not answered to a separate table. Default is N."); ?>

<BR>
<A NAME="settings-did_agent_log">
<BR>
<B><?php echo _QXZ("DID Agent Log"); ?> -</B><?php echo _QXZ("This option will log the inbound DID calls along with an in-group and user ID, if applicable, to a separate table. Default is N."); ?>

<BR>
<A NAME="settings-alt_log_server_ip">
<BR>
<B><?php echo _QXZ("Alt-Log DB Server"); ?> -</B><?php echo _QXZ("This is the alternate log database server. This is optional, and allows some logs to be written to a separate database. Default is empty."); ?>

<BR>
<A NAME="settings-tables_use_alt_log_db">
<BR>
<B><?php echo _QXZ("Alt-Log Tables"); ?> -</B><?php echo _QXZ("These are the tables that are available for logging on the alternate log database server. Default is blank."); ?>

<BR>
<A NAME="settings-qc_features_active">
<BR>
<B><?php echo _QXZ("Default External Server IP"); ?> -</B><?php echo _QXZ("If set to 1, this option will make all new phones created have Use External Server IP set to Y. Default is 0."); ?>

<BR>
<A NAME="settings-qc_features_active">
<BR>
<B><?php echo _QXZ("Webphone URL"); ?> -</B><?php echo _QXZ("This is the URL of the webphone that will be used with this system if it is enabled in the phones record that an agent is using. Default is empty."); ?>

<BR>
<A NAME="settings-enable_queuemetrics_logging">
<BR>
<B><?php echo _QXZ("Enable QueueMetrics Logging"); ?> -</B><?php echo _QXZ("This setting allows you to define whether the system will insert log entries into the queue_log database table as Asterisk Queues activity does. QueueMetrics is a standalone, closed-source statistical analysis program. You must have QueueMetrics already installed and configured before enabling this feature. Default is 0."); ?>

<BR>
<A NAME="settings-queuemetrics_server_ip">
<BR>
<B><?php echo _QXZ("QueueMetrics Server IP"); ?> -</B><?php echo _QXZ("This is the IP address of the database for your QueueMetrics installation."); ?>

<BR>
<A NAME="settings-queuemetrics_dbname">
<BR>
<B><?php echo _QXZ("QueueMetrics Database Name"); ?> -</B><?php echo _QXZ("This is the database name for your QueueMetrics database."); ?>

<BR>
<A NAME="settings-queuemetrics_login">
<BR>
<B><?php echo _QXZ("QueueMetrics Database Login"); ?> -</B><?php echo _QXZ("This is the user name used to log in to your QueueMetrics database."); ?>

<BR>
<A NAME="settings-queuemetrics_pass">
<BR>
<B><?php echo _QXZ("QueueMetrics Database Password"); ?> -</B><?php echo _QXZ("This is the password used to log in to your QueueMetrics database."); ?>

<BR>
<A NAME="settings-queuemetrics_url">
<BR>
<B><?php echo _QXZ("QueueMetrics URL"); ?> -</B><?php echo _QXZ("This is the URL or web site address used to get to your QueueMetrics installation."); ?>

<BR>
<A NAME="settings-queuemetrics_log_id">
<BR>
<B><?php echo _QXZ("QueueMetrics Log ID"); ?> -</B><?php echo _QXZ("This is the server ID that all contact center logs going into the QueueMetrics database will use as an identifier for each record."); ?>

<BR>
<A NAME="settings-queuemetrics_eq_prepend">
<BR>
<B><?php echo _QXZ("QueueMetrics EnterQueue Prepend"); ?> -</B><?php echo _QXZ("This field is used to allow for prepending of one of the list data fields in front of the phone number of the customer for customized QueueMetrics reports. Default is NONE to not populate anything."); ?>

<BR>
<A NAME="settings-queuemetrics_loginout">
<BR>
<B><?php echo _QXZ("QueueMetrics Login-Out"); ?> -</B><?php echo _QXZ("This option affects how the system will log the logins and logouts of an agent in the queue_log. Default is STANDARD to use standard AGENTLOGIN AGENTLOGOFF, CALLBACK will use AGENTCALLBACKLOGIN and AGENTCALLBACKLOGOFF that QM will parse differently, NONE will not log any logins and logouts within queue_log."); ?>

<BR>
<A NAME="settings-queuemetrics_callstatus">
<BR>
<B><?php echo _QXZ("QueueMetrics CallStatus"); ?> -</B><?php echo _QXZ("This option if set to 0 will not put in the CALLSTATUS entry into queue_log when an agent dispositions a call. Default is 1 for enabled."); ?>

<BR>
<A NAME="settings-queuemetrics_addmember_enabled">
<BR>
<B><?php echo _QXZ("QueueMetrics Addmember Enabled"); ?> -</B><?php echo _QXZ("This option if set to 1 will generate ADDMEMBER2 and REMOVEMEMBER entries in queue_log. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-queuemetrics_dispo_pause">
<BR>
<B><?php echo _QXZ("QueueMetrics Dispo Pause Code"); ?> -</B><?php echo _QXZ("This option, if populated, allows you to define whether a dispo pause code is entered into queue_log when an agent is in dispo status. Default is empty for disabled."); ?>

<BR>
<A NAME="settings-queuemetrics_pause_type">
<BR>
<B><?php echo _QXZ("QueueMetrics Pause Type Logging"); ?> -</B><?php echo _QXZ("If enabled, this option will log the type of pause in the queue_log table data5 field. You must make sure that you have a data5 field or enabling this feature will break QM compatibility. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-queuemetrics_pe_phone_append">
<BR>
<B><?php echo _QXZ("QueueMetrics Phone Environment Phone Append"); ?> -</B><?php echo _QXZ("This option, if enabled, will append the agent phone login to the data4 record in the queue log table if the Campaign Phone Environment field is populated. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-queuemetrics_record_hold">
<BR>
<B><?php echo _QXZ("QueueMetrics Hold Call Log"); ?> -</B><?php echo _QXZ("This option, if enabled, will log when a customer is put on hold and taken off hold in the newer record_tags QM table. Default is 0 for disabled."); ?>

<BR>
<A NAME="settings-queuemetrics_socket">
<BR>
<B><?php echo _QXZ("QueueMetrics Socket Send"); ?> -</B><?php echo _QXZ("This option, if enabled, will send QM data to a web page that will send it through a socket for logging. The CONNECT_COMPLETE option will send CONNECT, COMPLETEAGENT and COMPLETECALLER events to the url defined below. Default is NONE for disabled."); ?>

<BR>
<A NAME="settings-queuemetrics_socket_url">
<BR>
<B><?php echo _QXZ("QueueMetrics Socket Send URL"); ?> -</B><?php echo _QXZ("If Socket Send is enabled above, this is the URL that is used to send the data to. Default is EMPTY for disabled."); ?>

<BR>
<A NAME="settings-enable_vtiger_integration">
<BR>
<B><?php echo _QXZ("Enable Vtiger Integration"); ?> -</B><?php echo _QXZ("This setting allows you to enable Vtiger integration with the system. Currently links to Vtiger admin and search as well as user replication are the only integration features available. Default is 0."); ?>

<BR>
<A NAME="settings-vtiger_server_ip">
<BR>
<B><?php echo _QXZ("Vtiger DB Server IP"); ?> -</B><?php echo _QXZ("This is the IP address of the database for your Vtiger installation."); ?>

<BR>
<A NAME="settings-vtiger_dbname">
<BR>
<B><?php echo _QXZ("Vtiger Database Name"); ?> -</B><?php echo _QXZ("This is the database name for your Vtiger database."); ?>

<BR>
<A NAME="settings-vtiger_login">
<BR>
<B><?php echo _QXZ("Vtiger Database Login"); ?> -</B><?php echo _QXZ("This is the user name used to log in to your Vtiger database."); ?>

<BR>
<A NAME="settings-vtiger_pass">
<BR>
<B><?php echo _QXZ("Vtiger Database Password"); ?> -</B><?php echo _QXZ("This is the password used to log in to your Vtiger database."); ?>

<BR>
<A NAME="settings-vtiger_url">
<BR>
<B><?php echo _QXZ("Vtiger URL"); ?> -</B><?php echo _QXZ("This is the URL or web site address used to get to your Vtiger installation."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>STATUSES <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="system_statuses">
<BR>
<B><?php echo _QXZ("Through the use of system statuses, you can have statuses that exist for all campaigns and in-groups. The Status must be 1-6 characters in length, the description must be 2-30 characters in length and Selectable defines whether it shows up in the system as an agent disposition. The human_answered field is used when calculating the drop percentage, or abandon rate. Setting human_answered to Y will use this status when counting the human-answered calls. The Category option allows you to group several statuses into a category that can be used for statistical analysis. There are also 7 additional settings that will define the kind of status: sale, dnc, customer contact, not interested, unworkable, scheduled callback, completed. The MIN SEC and MAX SEC fields for each status will determine whether an agent can select that status at the end of their call based upon the length of the call. If the call is 10 seconds and the MIN SEC for a status is set to 20 seconds, then the agent will not be able to select that status. Also, if a call is 40 seconds and the MAX SEC for a status is set to 30 seconds, then the agent will not be able to select that status."); ?></B>





<BR><BR><BR><BR>

<B><FONT SIZE=3>STATUS_GROUPS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="status_groups">
<BR>
<B><?php echo _QXZ("Through the use of status groups, you can have statuses that exist for lists or in-groups specifically. The Status must be 1-6 characters in length, the description must be 2-30 characters in length and Selectable defines whether it shows up in the system as an agent disposition. The human_answered field is used when calculating the drop percentage, or abandon rate. Setting human_answered to Y will use this status when counting the human-answered calls. The Category option allows you to group several statuses into a category that can be used for statistical analysis. There are also 7 additional settings that will define the kind of status: sale, dnc, customer contact, not interested, unworkable, scheduled callback, completed. The MIN SEC and MAX SEC fields for each status will determine whether an agent can select that status at the end of their call based upon the length of the call. If the call is 10 seconds and the MIN SEC for a status is set to 20 seconds, then the agent will not be able to select that status. Also, if a call is 40 seconds and the MAX SEC for a status is set to 30 seconds, then the agent will not be able to select that status. The Status Group ID field must be between 2 and 20 characters in length, must not match a current campaign ID, and cannot contain spaces or other special characters."); ?></B>






<BR><BR><BR><BR>

<B><FONT SIZE=3>SCREEN_LABELS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="screen_labels">
<BR>
<B><?php echo _QXZ("Screen labels give you the option of setting different labels for the default agent screen fields on a per campaign basis."); ?></B>

<A NAME="screen_labels-label_id">
<BR>
<B><?php echo _QXZ("Screen Label ID"); ?> -</B><?php echo _QXZ("This field needs to be at least 2 characters in length and no more than 20 characters in length, no spaces or special characters. This is the ID that will be used to identify the screen label throughout the system."); ?>

<BR>
<A NAME="screen_labels-label_name">
<BR>
<B><?php echo _QXZ("Screen Label Name"); ?> -</B><?php echo _QXZ("This is the descriptive name of the screen label entry."); ?>

<BR>
<A NAME="screen_labels-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="screen_labels-label_hide_field_logs">
<BR>
<B><?php echo _QXZ("Hide Label in Call Logs"); ?> -</B><?php echo _QXZ("If a label is set to ---HIDE--- then the agent call logs, if enabled on the campaign, will still show the field and data unless this option is set to Y. Default is N."); ?>

<BR>
<A NAME="screen_labels-default_field_labels">
<BR>
<B><?php echo _QXZ("Default Field Labels"); ?> -</B><?php echo _QXZ("These 19 fields allow you to set the name as it will appear in the agent interface as well as the administrative modify lead page. Default is empty which will use the hard-coded defaults in the agent interface. You can also set a label to ---HIDE--- to hide both the label and the field. Another option for most fields is ---READONLY--- which will display but not allow an agent to modify the field. One more option for most fields is ---REQUIRED--- which will force an agent to populate that field on all calls before being able to hang up and disposition each call. For the REQUIRED option to work, the campaign must have Allow Required Fields enabled."); ?>





<BR><BR><BR><BR>

<B><FONT SIZE=3>SCREEN_COLORS <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="screen_colors">
<BR>
<B><?php echo _QXZ("Screen colors give you the option of setting a different color scheme and logo for the web interface in the System Settings."); ?></B>

<A NAME="screen_colors-colors_id">
<BR>
<B><?php echo _QXZ("Screen Colors ID"); ?> -</B><?php echo _QXZ("This field needs to be at least 2 characters in length and no more than 20 characters in length, no spaces or special characters. This is the ID that will be used to identify the screen colors in the system."); ?>

<BR>
<A NAME="screen_colors-colors_name">
<BR>
<B><?php echo _QXZ("Screen Colors Name"); ?> -</B><?php echo _QXZ("This is the descriptive name of the screen colors entry."); ?>

<BR>
<A NAME="screen_colors-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="screen_colors-menu_background">
<BR>
<B><?php echo _QXZ("Menu Background"); ?> -</B><?php echo _QXZ("This is where you set the hex color value for the sidebar menu background. It must be a darker color since white text will be displayed on top of it. Default is 015B91."); ?>

<BR>
<A NAME="screen_colors-frame_background">
<BR>
<B><?php echo _QXZ("Frame Background"); ?> -</B><?php echo _QXZ("This is where you set the hex color value for the frame main background. It must be a light color since black text will be displayed on top of it. Default is D9E6FE."); ?>

<BR>
<A NAME="screen_colors-std_row_background">
<BR>
<B><?php echo _QXZ("Standard Row Backgrounds"); ?> -</B><?php echo _QXZ("This is where you set the hex color values for the several standard row backgrounds. They should be lighter colors since black text will be displayed on top of them. These are often used to make different sections stand apart or as alternating colors for rows. Defaults are 9BB9FB, B9CBFD, 8EBCFD, B6D3FC, A3C3D6. The Standard Row 5 Background is used in the Agent screen for the background color behind the logo after the agent has logged in."); ?>

<BR>
<A NAME="screen_colors-alt_row_background">
<BR>
<B><?php echo _QXZ("Alternate Row Backgrounds"); ?> -</B><?php echo _QXZ("This is where you set the hex color values for the several alternate row backgrounds. They should be lighter colors since black text will be displayed on top of them. These are used to differentiate sections from the Standard backgrounds defined above. Defaults are BDFFBD, 99FF99, CCFFCC."); ?>

<BR>
<A NAME="screen_colors-web_logo">
<BR>
<B><?php echo _QXZ("Web Logo"); ?> -</B><?php echo _QXZ("This is where you can select a custom image file for your logo. The standard size for a logo image is 170 pixels wide and 45 pixels high. We suggest using the PNG format, but GIF and JPG will work as well. To have a custom logo image show up in this list, it needs to be uploaded to your webserver in the images directory, and the filename needs to begin with vicidial_admin_web_logo. For example, if you upload the image file vicidial_admin_web_logoSAMPLE.png, it will show up in the select list as SAMPLE.png. Default is default_new for the new PNG logo image file."); ?>






<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("SETTINGS CONTAINERS TABLE"); ?></FONT></B><BR><BR>
<A NAME="settings_containers">
<BR>
<B><?php echo _QXZ("Settings Containers allow for an easy way to add, modify and delete large configurations of settings, or for elements like email templates to be used by PHP and Perl scripts within the codebase."); ?></B>

<A NAME="settings_containers-container_id">
<BR>
<B><?php echo _QXZ("Container ID"); ?> -</B><?php echo _QXZ("This field needs to be at least 2 characters in length and no more than 40 characters in length, no spaces or special characters. This is the ID that will be used to identify the Settings Container throughout the system."); ?>

<BR>
<A NAME="settings_containers-container_notes">
<BR>
<B><?php echo _QXZ("Container Notes"); ?> -</B><?php echo _QXZ("This is the descriptive name of the Settings Container entry."); ?>

<BR>
<A NAME="settings_containers-container_type">
<BR>
<B><?php echo _QXZ("Container Type"); ?> -</B><?php echo _QXZ("This is the type of Container entry. This is only used for categorization."); ?>

<BR>
<A NAME="settings_containers-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user to view this record."); ?>

<BR>
<A NAME="settings_containers-container_entry">
<BR>
<B><?php echo _QXZ("Container Entry"); ?> -</B><?php echo _QXZ("This is where you put the contents of the settings that you want in this container."); ?>






<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("AUTOMATED REPORTS TABLE"); ?></FONT></B><BR><BR>
<A NAME="auto_reports">
<BR>
<B><?php echo _QXZ("Automated Reports allow for a simplified way of configuring reports to be run on a scheduled and recurring basis, and to have the results sent by email or uploaded to an FTP server."); ?></B>

<A NAME="auto_reports-report_id">
<BR>
<B><?php echo _QXZ("Report ID"); ?> -</B><?php echo _QXZ("This field needs to be at least 2 characters in length and no more than 30 characters in length, no spaces or special characters. This is the ID that will be used to identify the Automated Report throughout the system."); ?>

<BR>
<A NAME="auto_reports-report_name">
<BR>
<B><?php echo _QXZ("Report Name"); ?> -</B><?php echo _QXZ("This is the descriptive name of the Automated Report entry."); ?>

<BR>
<A NAME="auto_reports-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user with Modify Automated Reports permissions to view this record."); ?>

<BR>
<A NAME="auto_reports-report_last_run">
<BR>
<B><?php echo _QXZ("Last Run Time"); ?> -</B><?php echo _QXZ("This is the date and time of the last run of this automated report process. Also included is the number of seconds it took for this process to run the last time."); ?>

<BR>
<A NAME="auto_reports-report_server">
<BR>
<B><?php echo _QXZ("Report Server"); ?> -</B><?php echo _QXZ("This is the machine that you want to use to run this automated report. You must choose a server that runs the keepalive process every minute in the crontab. The default is to use the active voicemail server as defined in System Settings."); ?>

<BR>
<A NAME="auto_reports-report_times">
<BR>
<B><?php echo _QXZ("Report Times"); ?> -</B><?php echo _QXZ("This is the list of times in HHMM format when you want the automated report to run. You can specify multiple times by separating them with a dash, like how the List Reset Times feature works."); ?>

<BR>
<A NAME="auto_reports-report_weekdays">
<BR>
<B><?php echo _QXZ("Report Weekdays"); ?> -</B><?php echo _QXZ("This is the list of days of the week when you want the automated report to run. Either this option or the Month Days field below must be set for the report to run automatically."); ?>

<BR>
<A NAME="auto_reports-report_monthdays">
<BR>
<B><?php echo _QXZ("Report Month Days"); ?> -</B><?php echo _QXZ("This is the list of days of the month in DD format when you want the automated report to run. You can specify multiple month days by separating them with a dash. Either this option or the Weekdays field above must be set for the report to run automatically."); ?>

<BR>
<A NAME="auto_reports-report_destination">
<BR>
<B><?php echo _QXZ("Report Destination"); ?> -</B><?php echo _QXZ("This option will determine how you want the automated report to be delivered, by EMAIL or FTP. Depending on the destination you choose, you must fill in the configuration fields below for it to work properly. Default is EMAIL."); ?>

<BR>
<A NAME="auto_reports-email_from">
<BR>
<B><?php echo _QXZ("Email From"); ?> -</B><?php echo _QXZ("If the EMAIL destination is chosen above, then this field must be filled in with the email address that you want the automated report email to come from. Note: some email servers may require certain registered email address to be used for delivery to be accepted by them. You may also have to set up a reverse domain name lookup with your network provider for the server you have chosen to send emails from."); ?>

<BR>
<A NAME="auto_reports-email_to">
<BR>
<B><?php echo _QXZ("Email To"); ?> -</B><?php echo _QXZ("If the EMAIL destination is chosen above, then this field must be filled in with the email address that you want to receive the automated report email. You can define more than one email address by separating them with a colon."); ?>

<BR>
<A NAME="auto_reports-email_subject">
<BR>
<B><?php echo _QXZ("Email Subject"); ?> -</B><?php echo _QXZ("If the EMAIL destination is chosen above, then this field must be filled in with the subject of the email. As an option, you can use the --A--date--B-- or --A--datetime--B-- flag in this field to populate the current date or date-time."); ?>

<BR>
<A NAME="auto_reports-ftp_server">
<BR>
<B><?php echo _QXZ("FTP Server"); ?> -</B><?php echo _QXZ("If the FTP destination is chosen above, then this field must be filled in with the FTP server address. Note: this works only for standard FTP servers, not SFTP or FTP over SSL."); ?>

<BR>
<A NAME="auto_reports-ftp_user">
<BR>
<B><?php echo _QXZ("FTP User"); ?> -</B><?php echo _QXZ("If the FTP destination is chosen above, then this field must be filled in with the FTP login user."); ?>

<BR>
<A NAME="auto_reports-ftp_pass">
<BR>
<B><?php echo _QXZ("FTP Pass"); ?> -</B><?php echo _QXZ("If the FTP destination is chosen above, then this field must be filled in with the FTP login password."); ?>

<BR>
<A NAME="auto_reports-ftp_directory">
<BR>
<B><?php echo _QXZ("FTP Directory"); ?> -</B><?php echo _QXZ("If the FTP destination is chosen above, then this field can be filled in with the FTP folder directory that you want the automated reports to be uploaded into. Note: the folder must already exist, this process will not try to create the folder."); ?>

<BR>
<A NAME="auto_reports-filename_override">
<BR>
<B><?php echo _QXZ("Filename Override"); ?> -</B><?php echo _QXZ("By default, the reports will be saved as files and either emailed or sent by FTP to the selected destination, and the filenames used for those files are automatically generated with unique generic names based upon the report ID. If you would like to override those unique generic names, you can do so by using this field. You can also use the variables shown below in the Report URL field so that you can put dates into the filename. You can also use --A--filedatetime--B-- as a variable to note the date and time the report was generated, which is useful if you will be running a report more than once a day. Only letters, numbers dashes and underscore characters are allowed in this field. Also, you should not include a file extension as part of the filename override, since the type of report will dictate the file extension that is used. Default is empty for disabled."); ?>

<BR>
<A NAME="auto_reports-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("The automated report will only run on the set schedule above if active is set to Y. Default is N."); ?>

<BR>
<A NAME="auto_reports-run_now_trigger">
<BR>
<B><?php echo _QXZ("Run Now Trigger"); ?> -</B><?php echo _QXZ("This option offers a way to test this automated report process before setting it to active. It can take up to one minute for the process to start running after submitting this page with this field set to Y. Default is N."); ?>

<BR>
<A NAME="auto_reports-report_url">
<BR>
<B><?php echo _QXZ("Report URL"); ?> -</B><?php echo _QXZ("This is the field where you will put the full web address, or URL, for the report that you want to run. The easiest way to figure out what address to use is to run the report normally, then go to the Reports, Admin Utilities, Admin Report Log Viewer to see the full URL that was used to run that report. Once you have copied that URL, you can replace the dates that you see with one of these variables in the standard format that is used within this system, for example, --A--today--B--. You can use: today, yesterday, 6days, 7days, 8days, 13days, 14days, 15days, 30days. Note: some networks may require that you use a local server address in this URL if that is the only way that a server on your network can access the webserver used for reporting. Related to this, you may also have to use a local IP address if a full domain name is not accessible from within your system network. Also, it is recommended that you run reports in TEXT format for best display results."); ?>







<BR><BR><BR><BR>

<B><FONT SIZE=3><?php echo _QXZ("IP LISTS TABLE"); ?></FONT></B><BR><BR>
<A NAME="ip_lists">
<BR>
<?php echo _QXZ("The purpose of this feature is to allow an administrator the ability to restrict who can use the system web resources on an IP Address basis. This is designed to allow you to create IP Lists and then assign those IP Lists on a per-User-Group basis separated as Admin, Agent and API permissions. For example, you can set the IP List for Agent web access to DISABLED to allow anyone in the User Group to log in as an agent from any location, this is the default behavior. You can then set an IP List with one IP Address in it so within that same User Group, managers will only be able to log in to the admin web screens from that one location. You can also set an IP List with no entries in it for the API access IP List to not allow any user in that User Group to use the APIs."); ?><BR><BR>

<?php echo _QXZ("This IP whitelisting authentication is designed to happen after user authentication in order to allow a specific user to be set to ignore the IP List blocking feature, as well as to be able to have the IP List feature work on a per-User-Group basis."); ?><BR><BR>

<?php echo _QXZ("There is also a System-wide Blacklist option that allows you to specify an IP List to use to block all web service access to all users coming from the IP Addresses listed in the system settings blacklist IP List."); ?><BR><BR>

<?php echo _QXZ("In order to use any of these features, the System Settings option Allow IP Lists must be enabled on your system. Then you will see the IP Lists Admin section appear in the Admin menu."); ?><BR><BR>

<A NAME="ip_lists-ip_list_id">
<BR>
<B><?php echo _QXZ("IP List ID"); ?> -</B><?php echo _QXZ("This field needs to be at least 2 characters in length and no more than 30 characters in length, no spaces or special characters. This is the ID that will be used to identify the IP List throughout the system."); ?>

<BR>
<A NAME="ip_lists-ip_list_name">
<BR>
<B><?php echo _QXZ("IP List Name"); ?> -</B><?php echo _QXZ("This is the descriptive name of the IP List entry."); ?>

<BR>
<A NAME="ip_lists-user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this record, this allows admin viewing of this record restricted by user group. Default is --ALL-- which allows any admin user with Modify IP Lists permissions to view this record."); ?>

<BR>
<A NAME="ip_lists-active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("The IP List will only be able to be ebaled for a user group if active is set to Y. Default is N."); ?>

<BR>
<A NAME="ip_lists-ip_address">
<BR>
<B><?php echo _QXZ("IP Addresses"); ?> -</B><?php echo _QXZ("This is the list of IP Addresses within this IP List. Only one IP Address per line is allowed. If you want to use IP whitelisting for agent access and you are using Dispo Call URL features like the dispo_move_list.php script, you will need to remember to include the IP Addresses of the web server in your IP List, because Dispo Call URL actions are run from the webserver."); ?>








<BR><BR><BR><BR>

<B><FONT SIZE=3>STATUS_CATEGORIES <?php echo _QXZ("TABLE"); ?></FONT></B><BR><BR>
<A NAME="status_categories">
<BR>
<B><?php echo _QXZ("Through the use of system status categories, you can group together statuses to allow for statistical analysis on a group of statuses. The Category ID must be 2-20 characters in length with no spaces, the name must be 2-50 characters in length, the description is optional and TimeonVDAD Display defines whether that status will be one of the upto 4 statuses that can be calculated and displayed on the Time On VDAD Real-Time report.</B> The Sale Category and Dead Lead Category are both used by the List Suggestion system when analyzing list statistics."); ?>





<BR><BR><BR><BR>
<?php
if ($SSallow_emails>0)
	{
?>
<B><FONT SIZE=3><?php echo _QXZ("EMAIL ACCOUNTS"); ?></FONT></B><BR><BR>
<A NAME="email_accounts">
<BR>
<B><?php echo _QXZ("The Email Accounts management section allows you to create, copy, and delete email account settings that will allow you to have email messages come into your system and be treated as if they were phone calls to agents.  EMAIL ACCOUNTS MUST BE SET UP BY YOU AND AN EMAIL SERVICE PROVIDER - THAT IS NOT COVERED BY THIS MODULE."); ?></B>

<BR>
<A NAME="email_accounts-email_account_id">
<BR>
<B><?php echo _QXZ("Email Account ID"); ?> -</B><?php echo _QXZ("This is the short name of the email account, it is not editable after initial submission, must not contain any spaces and must be between 2 and 20 characters in length."); ?>

<BR>
<A NAME="email_accounts-email_account_name">
<BR>
<B><?php echo _QXZ("Email Account Name"); ?> -</B><?php echo _QXZ("This is the full name of the email account, it must be between 2 and 30 characters in length. "); ?>

<BR>
<A NAME="email_accounts-email_account_active">
<BR>
<B><?php echo _QXZ("Active"); ?> -</B><?php echo _QXZ("This determines whether this account will be checked for new email messages to be loaded into the dialer. "); ?>

<BR>
<A NAME="email_accounts-email_account_description">
<BR>
<B><?php echo _QXZ("Email Account Description"); ?> -</B><?php echo _QXZ("This allows for a lengthy description, if needed, of the email account.  255 characters max. "); ?>

<BR>
<A NAME="email_accounts-email_account_type">
<BR>
<B><?php echo _QXZ("Email Account Type"); ?> -</B><?php echo _QXZ("Specifies whether the account is used for inbound or outbound email messages.  Should be set to INBOUND. "); ?>

<BR>
<A NAME="email_accounts-admin_user_group">
<BR>
<B><?php echo _QXZ("Admin User Group"); ?> -</B><?php echo _QXZ("This is the administrative user group for this inbound group, this allows admin viewing of this in-group restricted by user group. Default is --ALL-- which allows any admin user to view this in-group.  "); ?>

<BR>
<A NAME="email_accounts-protocol">
<BR>
<B><?php echo _QXZ("Email Account Protocol"); ?> -</B><?php echo _QXZ("This is the email protocol used by the account you are setting up access to.  Currently only IMAP and POP3 accounts are supported.  "); ?>

<BR>
<A NAME="email_accounts-email_replyto_address">
<BR>
<B><?php echo _QXZ("Email Reply-to Address"); ?> -</B><?php echo _QXZ("The email address of the account you are setting up access to.  Replies to email messages from the agent interface will read as coming from this address.  "); ?>

<BR>
<A NAME="email_accounts-email_account_server">
<BR>
<B><?php echo _QXZ("Email Account Server"); ?> -</B><?php echo _QXZ("The email server that the account is housed on.  "); ?>

<BR>
<A NAME="email_accounts-email_account_user">
<BR>
<B><?php echo _QXZ("Email Account User"); ?> -</B><?php echo _QXZ("The login used to access this account.  Usually its the portion of the reply-to address before the -at- symbol.  "); ?>

<BR>
<A NAME="email_accounts-pop3_auth_mode">
<BR>
<B><?php echo _QXZ("Auth Mode for POP3 protocol only"); ?> -</B><?php echo _QXZ("The authorization mode to use when POP3 is the mail protocol on the email account. BEST is the default.  It will use APOP if the server appears to support it and it can be used to successfully log on, then try similarly with CRAM-MD5, and finally PASS. APOP and CRAM-MD5 imply that an MD5 checksum will be used instead of sending your password in cleartext. However, if the server does not claim to support APOP or CRAM-MD5, the cleartext method will be used. Be careful. There are a few servers that will send a timestamp in the banner greeting, but APOP will not work with them, for instance if the server does not know your password in cleartext. If you think your authentication information is correct, you may have to use PASS for that server. The same applies to CRAM-MD5."); ?>

<BR>
<A NAME="email_accounts-email_account_pass">
<BR>
<B><?php echo _QXZ("Email Account Password"); ?> -</B><?php echo _QXZ("The password used to access this account.  This is usually set at the time the email account is created.  "); ?>

<BR>
<A NAME="email_accounts-email_frequency_check_mins">
<BR>
<B><?php echo _QXZ("Email Frequency Check Rate (mins)"); ?> -</B><?php echo _QXZ("How often this email account should be checked.  The highest rate of frequency at the moment is five minutes, some email providers will not allow more than three login attempts in fifteen minutes before locking the account for an indeterminate amount of time.  "); ?>

<BR>
<A NAME="email_accounts-in_group">
<BR>
<B><?php echo _QXZ("In-Group ID"); ?> -</B><?php echo _QXZ("The In-Group that email messages will be sent to.   "); ?>

<BR>
<A NAME="email_accounts-default_list_id">
<BR>
<B><?php echo _QXZ("Default List ID"); ?> -</B><?php echo _QXZ("The List ID that leads will be inserted into if necessary.  "); ?>

<BR>
<A NAME="email_accounts-call_handle_method">
<BR>
<B><?php echo _QXZ("In-Group Call Handle Method"); ?> -</B><?php echo _QXZ("This is the action that will be taken when a new email is found in the account.  EMAIL means all email messages will be inserted into the list table as a new lead.  EMAILLOOKUP will search the entire list table for the email address in the email column - if the lead is found, that lead list ID will be used in the record that goes into the email_list table.  EMAILLOOKUPRC does the same, but it will only search lists belonging to the campaign selected in the In-Group Campaign ID box below.  EMAILLOOKUPRL will only search one particular list, which is the one entered into the In-Group List ID box below."); ?>

<BR>
<A NAME="email_accounts-agent_search_method">
<BR>
<B><?php echo _QXZ("In-Group Agent Search Method"); ?> -</B><?php echo _QXZ("The agent search method to be used by the inbound group, LO is Load-Balanced-Overflow and will try to send the call to an agent on the local server before trying to send it to an agent on another server, LB is Load-Balanced and will try to send the call to the next agent no matter what server they are on, SO is Server-Only and will only try to send the calls to agents on the server that the call came in on. Default is LB. NOT USED"); ?>

<BR>
<A NAME="email_accounts-ingroup_list_id">
<BR>
<B><?php echo _QXZ("In-Group List ID"); ?> -</B><?php echo _QXZ("This is the List ID that will be used to search for a match within.  "); ?>

<BR>
<A NAME="email_accounts-ingroup_campaign_id">
<BR>
<B><?php echo _QXZ("In-Group Campaign ID"); ?> -</B><?php echo _QXZ("This is the Campaign ID that will be used to search for a match within."); ?>





<BR><BR><BR><BR>
<?php } ?>
<B><FONT SIZE=3><?php echo _QXZ("CUSTOM TEMPLATE MAKER"); ?></FONT></B><BR><BR>
<A NAME="template_maker">
<BR>
<?php echo _QXZ("The custom template maker allows you to define your own file layouts for use with the list loader and also delete them, if necessary.  If you frequently upload files that are in a consistent layout other than the standard layout, you may find this tool helpful.  The saved layout will work on any uploaded file it matches, regardless of file type or delimiter."); ?>

<BR>
<A NAME="template_maker-create_template">
<BR>
<B><?php echo _QXZ("Create a new template - </B>In order to begin creating your new listloader template, you must first load a lead file that has the layout you wish to create the template for.  Click Choose file, and open the file on your computer you wish to use.  This will upload a copy to your server and process it to determine the file type and delimiter (for TXT files)."); ?>

<BR>
<A NAME="template_maker-delete_template">
<BR>
<B><?php echo _QXZ("Delete template"); ?> -</B><?php echo _QXZ("If you have a template you no longer use or you mis-entered information on it and would like to re-enter it, select the template from the drop-down menu and click DELETE TEMPLATE."); ?>

<BR>
<A NAME="template_maker-template_id">
<BR>
<B><?php echo _QXZ("Template ID"); ?> -</B><?php echo _QXZ("This field is where you enter an arbitrary ID for your new custom template.  It must be between 2 and 20 characters and consist of alphanumeric characters and underscores."); ?>

<BR>
<A NAME="template_maker-template_name">
<BR>
<B><?php echo _QXZ("Template Name"); ?> -</B><?php echo _QXZ("This field is where you enter the name for your new custom template.  Can be up to 30 characters long."); ?>

<BR>
<A NAME="template_maker-template_description">
<BR>
<B><?php echo _QXZ("Template Description"); ?> -</B><?php echo _QXZ("This field is where you enter the description for your new custom template.  It can be up to 255 characters long."); ?>

<BR>
<A NAME="template_maker-list_id">
<BR>
<B><?php echo _QXZ("List ID"); ?> -</B><?php echo _QXZ("All templates must load their records into a list.  Select a list ID to load leads into from this drop-down list, which will display any lists available to you given your user settings."); ?>

<BR>
<A NAME="template_maker-assign_columns">
<BR>
<B><?php echo _QXZ("Assigning columns"); ?> -</B><?php echo _QXZ("Once you have loaded a sample lead file matching the layout you want to make into a template and select a list ID to load leads into, all of the available columns from the list table and the custom table for the list you selected (if any) will be displayed here.  Columns highlighted in blue are standard columns from the list table.  Columns highlighted in pink belong to the custom table for the selected list.  Each column listed has a drop-down menu, which should be populated with the fields from the first row of the sample lead file you uploaded.  Assign the appropriate fields to the appropriate columns and press SUBMIT TEMPLATE to create your template.  You do not need to assign every field to a column, and you do not need to assign every column a field.  For details on the standard list columns, click"); ?> <a href="#list_loader"><?php echo _QXZ("HERE"); ?></a>.





<BR><BR><BR><BR>

<A NAME="max_stats">
<B><FONT SIZE=3><?php echo _QXZ("Maximum System Stats Reports"); ?></FONT></B><BR><BR>
<BR>
<B><?php echo _QXZ("These statistics are cached totals that are stored throughout each day in real-time through back-end processes. For inbound calls, the total calls per in-group are calculated for each call that enters the process that calculates. For the whole system counts, the totals are generated from log entries as well as other in-group and campaign totals. These totals may not add up due to the settings that you have in your system as well as when the call is hung up."); ?></B>


<?php
if ($SSqc_features_active > 0)
	{
	?>
	<BR><BR><BR><BR>

	<B><FONT SIZE=3><?php echo _QXZ("QC STATUS CODES"); ?></FONT></B><BR><BR>
	<A NAME="qc_status_codes">
	<BR>
	<B><?php echo _QXZ("The Quality Control"); ?> -</B> <?php echo _QXZ("QC function has its own set of status codes separate from those within the call handling functions of the system. QC status codes must be between 2 and 8 characters in length and contain no special characters like a space or colon. The QC status code description must be between 2 and 30 characters in length. For these functions to work, you must have QC enabled in the System Settings."); ?>
	<?php
	}
?>





<BR><BR><BR><BR>
<B><FONT SIZE=3><?php echo _QXZ("Reports"); ?></FONT></B><BR><BR>

<A NAME="agent_time_detail">
<BR>
<B><?php echo _QXZ("Agent Time Detail"); ?> -</B><?php echo _QXZ("In this report you can view how much time agents spent on what."); ?><BR>
<?php echo _QXZ("<U>TIME CLOCK</U> = Time the agent been logged in to the time clock."); ?><BR>
<?php echo _QXZ("<U>AGENT TIME</U> = Total time on the system (<U>WAIT</U> + <U>TALK</U> + <U>DISPO</U> + <U>PAUSE</U>)."); ?><BR>
<?php echo _QXZ("<U>WAIT</U> = Time the agent waits for a call."); ?><BR>
<?php echo _QXZ("<U>TALK</U> = Time the agent talks to a customer or is in dead state (<U>DEAD</U> + <U>CUSTOMER</U>)."); ?><BR>
<?php echo _QXZ("<U>DISPO</U> = Time the agent uses at the disposition screen (where the agent picks NI, SALE etc)."); ?><BR>
<?php echo _QXZ("<U>PAUSE</U> = Time the agent is in pause mode (<U>LOGIN</U> + <U>LAGGED</U> + ...)."); ?><BR>
<?php echo _QXZ("<U>DEAD</U> = Time the agent is in a call after the customer has hung up."); ?><BR>
<?php echo _QXZ("<U>CUSTOMER</U> = Time the agent is in a live call with a customer."); ?><BR>
<?php echo _QXZ("- The next table is pause codes and their time."); ?><BR>
<?php echo _QXZ("<U>LOGIN</U> = The pause code when going from login directly to pause."); ?><BR>
<?php echo _QXZ("<U>LAGGED</U> = The time the agent had some network problem or similar."); ?><BR>
<?php echo _QXZ("<U>ANDIAL</U> = This pause code triggers if the agent been on dispo screen for longer than 1000 seconds."); ?><BR>
<?php echo _QXZ("and empty is undefined pause code."); ?><BR>

<A NAME="agent_status_detail">
<BR>
<B><?php echo _QXZ("Agent Status Detail"); ?> -</B><?php echo _QXZ("In this report you can view what and how many statuses has been selected by the agents."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Total number of calls sent to the user."); ?><BR>
<?php echo _QXZ("<U>CIcalls</U> = Total number of call where there was a Human Answer which is set under Admin -> System Statuses."); ?><BR>
<?php echo _QXZ("<U>DNC/CI%</U> = How much in percent DNC (Do Not Call) per Human Answers."); ?><BR>
<?php echo _QXZ("And the rest is just System Statuses that the agent picked and how many, to find out what they means then head over to Admin -> System Statuses."); ?><BR>

<A NAME="agent_performance_detail">
<BR>
<B><?php echo _QXZ("Agent Performance Detail"); ?> -</B><?php echo _QXZ("This is a combination of Agent Time Detail and Agent Status Detail."); ?><BR>
<?php echo _QXZ("(Statistics related to handling of calls only. What this means is that the top section will only include time involving a phone call. If an agent clicks to go active, then pauses, then goes active again without taking a call, that time will not be included in the top section of this report.)"); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Total number of calls sent to the user."); ?><BR>
<?php echo _QXZ("<U>TIME</U> = Total time of these (<U>PAUSE</U> + <U>WAIT</U> + <U>TALK</U> + <U>DISPO</U>)."); ?><BR>
<?php echo _QXZ("<U>PAUSE</U> = Amount of time being paused in related to call handling."); ?><BR>
<?php echo _QXZ("<U>AVG</U> means Average so everything -AVG is for example amount of PAUSE-time divided by total number of calls: (<U>PAUSE</U> / <U>CALLS</U> = <U>PAUSAVG</U>)"); ?><BR>
<?php echo _QXZ("<U>WAIT</U> = Time the agent waits for a call."); ?><BR>
<?php echo _QXZ("<U>TALK</U> = Time the agent talks to a customer or is in dead state (<U>DEAD</U> + <U>CUSTOMER</U>)."); ?><BR>
<?php echo _QXZ("<U>DISPO</U> = Time the agent uses at the disposition screen (where the agent picks NI, SALE etc)."); ?><BR>
<?php echo _QXZ("<U>DEAD</U> = Time the agent is in a call after the customer has hung up."); ?><BR>
<?php echo _QXZ("<U>CUSTOMER</U> = Time the agent is in a live call with a customer."); ?><BR>
<?php echo _QXZ("And the rest is just System Statuses that the agent picked and how many, to find out what they means then head over to Admin -> System Statuses."); ?><BR>
<?php echo _QXZ("- Next table is Pause Codes."); ?><BR>
<?php echo _QXZ("<U>LOGIN TIME</U> = Total time on the system (<U>WAIT</U> + <U>TALK</U> + <U>DISPO</U> + <U>PAUSE</U>)."); ?><BR>
<?php echo _QXZ("<U>NONPAUSE</U> = Everything except pause (<U>WAIT</U> + <U>TALK</U> + <U>DISPO</U>)."); ?><BR>
<?php echo _QXZ("<U>PAUSE</U> = Only Pause."); ?><BR>
<?php echo _QXZ("- The last table is pause codes and their time (like Agent Time Detail)."); ?><BR>
<?php echo _QXZ("<U>LOGIN</U> = The pause code when going from login directly to pause."); ?><BR>
<?php echo _QXZ("<U>LAGGED</U> = The time the agent had some network problem or similar."); ?><BR>
<?php echo _QXZ("<U>ANDIAL</U> = This pause code triggers if the agent been on dispo screen for longer than 1000 seconds."); ?><BR>
<?php echo _QXZ("and empty is undefined pause code."); ?><BR>

<A NAME="team_performance_detail">
<BR>
<B><?php echo _QXZ("Team Performance Detail"); ?> -</B><?php echo _QXZ("This report contains some of the same information as the Agent Time Detail report, as well as several new fields. Also, the users are grouped together within their User Groups, and there is a User Group summary at the bottom of the results."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Total number of calls sent to the user."); ?><BR>
<?php echo _QXZ("<U>LEADS</U> = Total number of unique leads the user handled."); ?><BR>
<?php echo _QXZ("<U>CONTACTS</U> = Total number of calls the user handled that were statused in a Customer Contact status."); ?><BR>
<?php echo _QXZ("<U>CONTACT RATIO</U> = Percent of calls where a customer was contacted."); ?><BR>
<?php echo _QXZ("<U>NONPAUSE</U> = Everything except pause (<U>WAIT</U> + <U>TALK</U> + <U>DISPO</U>)."); ?><BR>
<?php echo _QXZ("<U>TIME</U> = Total time of these (<U>PAUSE</U> + <U>WAIT</U> + <U>TALK</U> + <U>DISPO</U>)."); ?><BR>
<?php echo _QXZ("<U>TALK</U> = Time the agent talks to a customer minus <U>DEAD</U> time."); ?><BR>
<?php echo _QXZ("<U>SALES</U> = Total number of calls the user handled where the SALE status flag is set to Y."); ?><BR>
<?php echo _QXZ("<U>SALES PER WORKING HOUR</U> = Total number of sales divided by system time."); ?><BR>
<?php echo _QXZ("<U>SALES TO LEADS RATIO</U> = Total number of sales divided by leads handled."); ?><BR>
<?php echo _QXZ("<U>SALES TO CONTACTS RATIO</U> = Total number of sales divided by contacts."); ?><BR>
<?php echo _QXZ("<U>SALES PER HOUR</U> = Total number of sales divided by talk time."); ?><BR>
<?php echo _QXZ("<U>INCOMPLETE SALES</U> = Total number of calls statused as QCFAIL."); ?><BR>
<?php echo _QXZ("<U>CANCELLED SALES</U> = Total number of calls statused as QCCANC."); ?><BR>
<?php echo _QXZ("<U>CALLBACKS</U> = Total number of active or live callbacks for this user in the system."); ?><BR>
<?php echo _QXZ("<U>FIRST CALL RESOLUTION</U> = Total number of calls handled divided by leads handled."); ?><BR>
<?php echo _QXZ("<U>AVERAGE SALE TIME</U> = Total number of sales divided by the talk time for those sales calls."); ?><BR>
<?php echo _QXZ("<U>AVERAGE CONTACT TIME</U> = Total number of contacts divided by the talk time for those contact calls."); ?><BR>

<A NAME="performance_comparison_report">
<BR>
<B><?php echo _QXZ("Performance Comparison Report"); ?> -</B><?php echo _QXZ("This report contains some of the same information as the Team Performance Detail report, and the results for today and yesterday as well as the last 2, 3, 5, 10 and 30 days."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Total number of calls sent to the user."); ?><BR>
<?php echo _QXZ("<U>SALES</U> = Total number of calls the user handled where the SALE status flag is set to Y."); ?><BR>
<?php echo _QXZ("<U>SALES CONVERSION PCT</U> = Total number of sales divided by calls handled."); ?><BR>
<?php echo _QXZ("<U>SALES PER HOUR</U> = Total number of sales divided by system time."); ?><BR>
<?php echo _QXZ("<U>TIME</U> = Total time of these (<U>PAUSE</U> + <U>WAIT</U> + <U>TALK</U> + <U>DISPO</U>)."); ?><BR>

<A NAME="single_agent_daily">
<BR>
<B><?php echo _QXZ("Single Agent Daily"); ?> -</B><?php echo _QXZ("This report contains most of the same information as the Agent Status Detail report, except for only one agent across a range of days."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Total number of calls sent to the user."); ?><BR>
<?php echo _QXZ("<U>CI CALLS</U> = Total number of calls the user handled that were statused in a Customer Contact status."); ?><BR>
<?php echo _QXZ("<U>DNC CI PCT</U> = Total number of DNC statused calls divided by the number of Customer Contact calls."); ?><BR>
<?php echo _QXZ("<U>SALES PER HOUR</U> = Total number of calls the user handled where the SALE status flag is set to Y, divided by system time."); ?><BR>
<?php echo _QXZ("(the rest of the fields are the statuses that the agent selected)"); ?><BR>

<A NAME="single_agent_time">
<BR>
<B><?php echo _QXZ("Single Agent Daily Time"); ?> -</B><?php echo _QXZ("This report is similar to the Agent Time Detail report, except for only one agent across a range of days."); ?><BR>
<?php echo _QXZ("<U>PAUSE</U> = Amount of time for the agent being paused."); ?><BR>
<?php echo _QXZ("<U>WAIT</U> = Time the agent waits for a call."); ?><BR>
<?php echo _QXZ("<U>TALK</U> = Time the agent talks to a customer or is in dead state (<U>DEAD</U> + <U>CUSTOMER</U>)."); ?><BR>
<?php echo _QXZ("<U>DISPO</U> = Time the agent uses at the disposition screen (where the agent picks NI, SALE etc)."); ?><BR>
<?php echo _QXZ("<U>DEAD</U> = Time the agent is in a call after the customer has hung up."); ?><BR>
<?php echo _QXZ("<U>CUSTOMER</U> = Time the agent is in a live call with a customer."); ?><BR>
<?php echo _QXZ("<U>TOTAL</U> = Total time of these (<U>PAUSE</U> + <U>WAIT</U> + <U>TALK</U> + <U>DISPO</U>)."); ?><BR>

<A NAME="usergroup_login">
<BR>
<B><?php echo _QXZ("User Group Login Report"); ?> -</B><?php echo _QXZ("This report includes information on all of the users within a user group related to agent screen activity."); ?><BR>
<?php echo _QXZ("<U>FIRST LOGIN DATE</U> = The earliest date this user logged in to the agent screen."); ?><BR>
<?php echo _QXZ("<U>LAST LOGIN DATE</U> = The most recent date this user logged in to the agent screen."); ?><BR>
<?php echo _QXZ("<U>CAMPAIGN</U> = The most recent campaign this user was logged in to."); ?><BR>
<?php echo _QXZ("<U>SERVER IP</U> = The most recent server this user logged in to."); ?><BR>
<?php echo _QXZ("<U>COMPUTER IP</U> = The most recent computer this user used to log in to the agent screen."); ?><BR>
<?php echo _QXZ("<U>EXTENSION</U> = The most recent phone this user used to log in to the agent screen."); ?><BR>
<?php echo _QXZ("<U>BROWSER</U> = The most recent web browser version this user used to log in to the agent screen."); ?><BR>
<?php echo _QXZ("<U>PHONE LOGIN</U> = The most recent phone login this user used to log in to the agent screen."); ?><BR>
<?php echo _QXZ("<U>SERVER PHONE</U> = The most recent server phone account this user was logged in through."); ?><BR>
<?php echo _QXZ("<U>PHONE IP</U> = The IP address of the phone during the most recent agent screen session."); ?><BR>

<A NAME="agent_timeclock_detail">
<BR>
<B><?php echo _QXZ("User Timeclock Detail Report"); ?> -</B><?php echo _QXZ("Pulls all timeclock records for agents meeting the selected parameters."); ?><BR>
<?php echo _QXZ("<U>TIME CLOCK</U> = Total amount of time agent spent logged in."); ?><BR>
<?php echo _QXZ("<U>TIME CLOCK PUNCHES</U> = A list of the punch-in and punch-out times for each agent.  Punch-out times marked with an asterisk (*) denotes AUTOLOGOUT from timeclock."); ?><BR>

<A NAME="campaign_status_list_report">
<BR>
<B><?php echo _QXZ("Campaign Status List Report"); ?> -</B><?php echo _QXZ("This report is designed to show the breakdown by list_id of the calls and their statuses for all lists within a campaign for a set time period."); ?><BR>
<?php echo _QXZ("<U>DISPOSITION</U> = The distinct dispositions made for the list within the time frame specified."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls ending with the disposition listed."); ?><BR>
<?php echo _QXZ("<U>DURATION</U> = Sum of the length of the calls ending with the disposition listed."); ?><BR>
<?php echo _QXZ("<U>HANDLE TIME</U> = Sum of the time the calls ending with the disposition listed were handled by an agent (<U>TALK</U> + <U>DEAD</U>)."); ?><BR><BR>
<?php echo _QXZ("<U>TOTAL CALLS</U> = Number of calls placed to leads belonging to lists in this campaign within the time frame specified."); ?><BR>
<?php echo _QXZ("<U>STATUS FLAGS BREAKDOWN</U> = Breakdown of the total calls into status categories, including counts per category and percentage relative to the number of calls to leads in the list within the time frame specified."); ?><BR>

<A NAME="source_vlc_status_report">
<BR>
<B><?php echo _QXZ("Outbound Lead Source Report"); ?> -</B><?php echo _QXZ("This report is designed to show the breakdown by either vendor_lead_code or source_id, choice of the user, of the calls and their statuses for all lists within a campaign for a set time period"); ?><BR>

<A NAME="CLOSER_service_level">
<BR>
<B><?php echo _QXZ("Inbound Service Level Report"); ?> -</B><?php echo _QXZ("This report is designed to give a daily breakdown of the number of calls, holds, and drop within the specified date range for the selected inbound group."); ?><BR>
<?php echo _QXZ("<U>DROPS</U> = The number of dropped calls to the inbound group within the time frame specified for that time interval."); ?><BR>
<?php echo _QXZ("<U>DROP %</U> = The percentage of calls ending in DROPS."); ?><BR>
<?php echo _QXZ("<U>AVG DROP(S)</U> = Average length in seconds of the calls ending in DROPS."); ?><BR>
<?php echo _QXZ("<U>HOLDS</U> = The number of calls to the inbound group that were held in queue for that time interval."); ?><BR>
<?php echo _QXZ("<U>HOLD %</U> = The percentage of calls that went to queue relative to the total calls."); ?><BR>
<?php echo _QXZ("<U>AVG HOLD(S) HOLD</U> = Average length in seconds of the queue time for only the calls in queue."); ?><BR>
<?php echo _QXZ("<U>AVG HOLD(S) TOTAL</U> = Average length in seconds of the queue time for ALL calls for the time interval."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Number of calls received by the ingroup within the time interval."); ?><BR>
<?php echo _QXZ("<U>TOTAL CALLTIME MIN:SEC</U> = Total length of all calls received."); ?><BR>
<?php echo _QXZ("<U>AVG CALLTIME SECONDS</U> = Average length of all calls received."); ?><BR>

<A NAME="CLOSERstats">
<BR>
<B><?php echo _QXZ("Inbound Report (by DID)"); ?> -</B><?php echo _QXZ("This report is designed to give several different statistical breakdowns of calls received within the specified time frame, by either ingroup or by DID (refer to the title of the report)."); ?><BR>
<?php echo _QXZ("<B>MULTI-GROUP BREAKDOWN</B>"); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls to the inbound groups or DIDs within the time frame specified for that time interval."); ?><BR>
<?php echo _QXZ("<U>DROPS</U> = The number of dropped calls to the inbound groups or DIDs within the time frame specified for that time interval."); ?><BR>
<?php echo _QXZ("<U>DROP %</U> = The percentage of calls ending in DROPS."); ?><BR>
<?php echo _QXZ("<U>IVR</U> = The number of calls to the inbound group or DID that went to an IVR within the time frame specified for that time interval."); ?><BR>

<?php echo _QXZ("<B>CALL HANGUP REASON STATS</B>"); ?><BR>
<?php echo _QXZ("<U>HANGUP REASON</U> = The reason the call terminated."); ?><BR>
<?php echo _QXZ("<U>HOLDS</U> = The number of calls terminated from the aforementioned reason."); ?><BR>

<?php echo _QXZ("<B>CALL STATUS STATS</B>"); ?><BR>
<?php echo _QXZ("<U>STATUS</U> = The call disposition."); ?><BR>
<?php echo _QXZ("<U>DESCRIPTION</U> = A description of the call disposition."); ?><BR>
<?php echo _QXZ("<U>CATEGORY</U> = The call category that the disposition currently belongs to."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls taken by the ingroups or DIDs that were dispositioned as the listed disposition."); ?><BR>
<?php echo _QXZ("<U>TOTAL TIME</U> = The total amount of time that was spent on calls that ended as the listed disposition."); ?><BR>
<?php echo _QXZ("<U>AVG TIME</U> = The average amount of time that was spent on calls that ended as the listed disposition."); ?><BR>
<?php echo _QXZ("<U>CALLS /HOUR</U> = The number of calls per hour to the ingroups or DIDs that ended as the listed disposition."); ?><BR>

<?php echo _QXZ("<B>CUSTOM STATUS CATEGORY STATS</B>"); ?><BR>
<?php echo _QXZ("<U>CATEGORY</U> = The custom call category that a call was dispositioned under the selected report parameters."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls dispositioned under the custom status category."); ?><BR>
<?php echo _QXZ("<U>DESCRIPTION</U> = A description of the custom call category."); ?><BR>

<?php echo _QXZ("<B>AGENT STATS</B>"); ?><BR>
<?php echo _QXZ("<U>AGENT</U> = The agent who received a call to the specified inbound groups or DIDs."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls fielded by the agent."); ?><BR>
<?php echo _QXZ("<U>DESCRIPTION</U> = The total time the agent spent in calls to the specified inbound groups or DIDs."); ?><BR>
<?php echo _QXZ("<U>DESCRIPTION</U> = The average time the agent spent in calls to the specified inbound groups or DIDs."); ?><BR>

<A NAME="CLOSERstats_v2">
<BR>
<B><?php echo _QXZ("Inbound Report (by DID), v2"); ?> -</B><?php echo _QXZ("This report is designed to give several different statistical breakdowns of calls received within the specified time frame, by either ingroup or by DID (refer to the title of the report)."); ?><BR>
<?php echo _QXZ("<B>MULTI-GROUP BREAKDOWN</B>"); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls to the inbound groups or DIDs within the time frame specified for that time interval."); ?><BR>
<?php echo _QXZ("<U>DROPS</U> = The number of dropped calls to the inbound groups or DIDs within the time frame specified for that time interval."); ?><BR>
<?php echo _QXZ("<U>DROP %</U> = The percentage of calls ending in DROPS."); ?><BR>
<?php echo _QXZ("<U>IVR</U> = The number of calls to the inbound group or DID that went to an IVR within the time frame specified for that time interval."); ?><BR>

<?php echo _QXZ("<B>CALL HANGUP REASON STATS</B>"); ?><BR>
<?php echo _QXZ("<U>HANGUP REASON</U> = The reason the call terminated."); ?><BR>
<?php echo _QXZ("<U>HOLDS</U> = The number of calls terminated from the aforementioned reason."); ?><BR>

<?php echo _QXZ("<B>CALL STATUS STATS</B>"); ?><BR>
<?php echo _QXZ("<U>STATUS</U> = The call disposition."); ?><BR>
<?php echo _QXZ("<U>DESCRIPTION</U> = A description of the call disposition."); ?><BR>
<?php echo _QXZ("<U>CATEGORY</U> = The call category that the disposition currently belongs to."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls taken by the ingroups or DIDs that were dispositioned as the listed disposition."); ?><BR>
<?php echo _QXZ("<U>TOTAL TIME</U> = The total amount of time that was spent on calls that ended as the listed disposition."); ?><BR>
<?php echo _QXZ("<U>AVG TIME</U> = The average amount of time that was spent on calls that ended as the listed disposition."); ?><BR>
<?php echo _QXZ("<U>CALLS /HOUR</U> = The number of calls per hour to the ingroups or DIDs that ended as the listed disposition."); ?><BR>

<?php echo _QXZ("<B>CUSTOM STATUS CATEGORY STATS</B>"); ?><BR>
<?php echo _QXZ("<U>CATEGORY</U> = The custom call category that a call was dispositioned under the selected report parameters."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls dispositioned under the custom status category."); ?><BR>
<?php echo _QXZ("<U>DESCRIPTION</U> = A description of the custom call category."); ?><BR>

<?php echo _QXZ("<B>AGENT STATS</B>"); ?><BR>
<?php echo _QXZ("<U>AGENT</U> = The agent who received a call to the specified inbound groups or DIDs."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls fielded by the agent."); ?><BR>
<?php echo _QXZ("<U>TIME H:M:S</U> = The total time the agent spent in calls to the specified inbound groups or DIDs."); ?><BR>
<?php echo _QXZ("<U>AVERAGE</U> = The average time the agent spent in calls to the specified inbound groups or DIDs."); ?><BR>


<A NAME="CLOSERsummary_hourly">
<BR>
<B><?php echo _QXZ("Inbound Summary Hourly Report"); ?> -</B><?php echo _QXZ("This report is designed to give several different statistical breakdowns of calls received within the specified time frame, by either ingroup or by DID (refer to the title of the report)."); ?><BR>
<?php echo _QXZ("<U>TOTAL CALLS</U> = Total calls taken by the in-group."); ?><BR>
<?php echo _QXZ("<U>TOTAL ANSWER</U> = Total calls answered by the in-group."); ?><BR>
<?php echo _QXZ("<U>TOTAL TALK</U> = Total amount of time spent in calls answered by the in-group."); ?><BR>
<?php echo _QXZ("<U>AVERAGE TALK</U> = Average amount of time spent in calls answered by the in-group."); ?><BR>
<?php echo _QXZ("<U>TOTAL QUEUE TIME</U> = Total amount of time calls handled by the in-group spent in queue."); ?><BR>
<?php echo _QXZ("<U>TOTAL QUEUE TIME</U> = Average amount of time calls handled by the in-group spent in queue."); ?><BR>
<?php echo _QXZ("<U>MAXIMUM QUEUE TIME</U> = The longest amount of time any single call handled by the in-group spent in queue."); ?><BR>
<?php echo _QXZ("<U>TOTAL ABANDON CALLS</U> = The number of calls to the in-group that were unanswered and abandoned by the caller."); ?><BR>

<A NAME="dialer_inventory_report">
<BR>
<B><?php echo _QXZ("Dialer Inventory Report"); ?> -</B><?php echo _QXZ("This report gives statistical information, including a status breakdown, of a single list or all lists in a single campaign."); ?><BR>
<?php echo _QXZ("<U>Start Inv</U> = Total leads currently in list."); ?><BR>
<?php echo _QXZ("<U>Call Inv Total</U> = Total leads currently dialable in list based on the campaign settings it belongs to."); ?><BR>
<?php echo _QXZ("<U>Call Inv No filtr</U> = Total leads currently dialable in list based on the campaign settings EXCLUDING any filters."); ?><BR>
<?php echo _QXZ("<U>Call Inv One-off</U> = Total leads currently dialable in list based on the campaign settings that are at least one dial attempt below the call count limit."); ?><BR>
<?php echo _QXZ("<U>Call Inv Inactive</U> = Total leads currently in list that belong to INCOMPLETE statuses that are NOT currently selected as dialable for the campaign."); ?><BR>
<?php echo _QXZ("<U>Dial Avg</U> = Total calls made on the list divided by the <U>Start Inv</U> of that list."); ?><BR>
<?php echo _QXZ("<U>Pen. pct</U> = Percentage of leads in the list that are no longer dialable, Start Inv - Call Inv Total divided-by Start Inv."); ?><BR>

<A NAME="DIDstats">
<BR>
<B><?php echo _QXZ("Inbound DID Report"); ?> -</B><?php echo _QXZ("This report breaks down activity for a DID or a list of DIDs over a given time interval."); ?><BR>
<?php echo _QXZ("<U>ROUTE</U> = Where the DID is currently directed when a call reaches it."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Number of calls the DIDs/servers/time frames (depending on which sub-report you are viewing) received during the specific date range."); ?><BR>

<A NAME="agentDIDstats">
<BR>
<B><?php echo _QXZ("Agent DID Report"); ?> -</B><?php echo _QXZ("This report breaks down agent call handling activity for a DID or a group of DIDs over a given time interval. The results will show the number of calls handled for each agent per day and per week, with column breakdowns for each DID using the DID name for the column header."); ?><BR>
<BR>

<A NAME="email_log_report">
<BR>
<B><?php echo _QXZ("Email Log Report"); ?> -</B><?php echo _QXZ("This report gives a detailed log report of any emails handled by the dialer, including links to the full text of received emails.  It will display the date the email was received, the address and name of the sender, a bit of the beginning of the emails text (with a link to the full text), and the status that the agent dispositioned the email as."); ?><BR>

<A NAME="inbound_daily_report">
<BR>
<B><?php echo _QXZ("Inbound Daily Report"); ?> -</B><?php echo _QXZ("This report will give a daily or hourly, weekly, monthly, and quarterly count on all calls received by the in-groups selected for the given date range."); ?><BR>
<?php echo _QXZ("<U>TOTAL CALLS OFFERED</U> = Total calls taken by the in-group."); ?><BR>
<?php echo _QXZ("<U>TOTAL CALLS ANSWERED</U> = Total calls answered by the in-group."); ?><BR>
<?php echo _QXZ("<U>TOTAL AGENTS ANSWERED</U> = Total number of distinct agents who fielded calls to this in-group."); ?><BR>
<?php echo _QXZ("<U>TOTAL CALLS ABANDONED</U> = Total calls ended in queue (dropped)."); ?><BR>
<?php echo _QXZ("<U>TOTAL ABANDON PERCENT</U> = Percentage of calls abandoned (<U>TOTAL CALLS ABANDONED</U> / <U>TOTAL CALLS OFFERED</U>)."); ?><BR>
<?php echo _QXZ("<U>AVG ABANDON TIME</U> = Average amount of time a caller waited in queue before abandoning the call."); ?><BR>
<?php echo _QXZ("<U>AVG ANSWER SPEED</U> = Average amount of time a caller waited in queue before an agent answered."); ?><BR>
<?php echo _QXZ("<U>AVG TALK TIME</U> = Average amount of time an agent remained on the line with the caller before the call was hung up - does not include caller queue time."); ?><BR>
<?php echo _QXZ("<U>TOTAL TALK TIME</U> = Total amount of time agents remained on the line with callers before calls ended - does not include caller queue time."); ?><BR>
<?php echo _QXZ("<U>TOTAL WRAP TIME</U> = Total amount of time agents used to -wrap- the call - assumed to be 15 second per answered call."); ?><BR>
<?php echo _QXZ("<U>TOTAL CALL TIME</U> = Total talk time plus total wrap time."); ?><BR>

<A NAME="IVRstats">
<BR>
<B><?php echo _QXZ("Inbound IVR Report"); ?> -</B><?php echo _QXZ("This report shows a breakdown of IVR paths followed by callers on selected IVRs based on the date range."); ?><BR>
<?php echo _QXZ("<U>IVR CALLS</U> = Total calls taken by or made through the selected IVRs that follow the -CALL PATH-."); ?><BR>
<?php echo _QXZ("<U>QUEUE CALLS</U> = Total inbound calls taken by the selected IVRs that follow the -CALL PATH-."); ?><BR>
<?php echo _QXZ("<U>QUEUE DROP CALLS</U> = Total inbound calls taken by the selected IVRs that follow the -CALL PATH- that were dropped."); ?><BR>
<?php echo _QXZ("<U>QUEUE DROP PERCENT</U> = Percentage of dropped inbound calls taken by the selected IVRs (QUEUE DROP CALLS / QUEUE CALLS)."); ?><BR>
<?php echo _QXZ("<U>IVR AVG TIME</U> = Average amount of time spent in-call, taken by dividing IVR CALLS by the total time spent in the selected IVRs."); ?><BR>
<?php echo _QXZ("<U>TOTAL AVG TIME</U> =  Total call time spent in the selected IVRs."); ?><BR>
<?php echo _QXZ("<U>CALL PATH</U> = The specific call path followed on the IVR - report will display each distinct path followed by all the selected IVRs for the selected date range."); ?><BR>

<A NAME="LISTS_campaign_stats">
<BR>
<B><?php echo _QXZ("Lists Campaign Statuses Report"); ?> -</B><BR>
<?php echo _QXZ("<U>LIST ID SUMMARY</U> = Shows each list in the campaign(s) selected by the user, the count of leads in each list, and whether the list is active or inactive."); ?><BR>
<?php echo _QXZ("<U>STATUS FLAGS SUMMARY</U> = Shows a breakdown of the status flags for all the selected lists in all the selected campaigns combined. Status flags are set in the -Statuses- section of a campaign or the -System Statuses-."); ?><BR>
<?php echo _QXZ("<U>CUSTOM STATUS CATEGORY STATS</U> = Shows a breakdown of the custom status categories for all the selected lists in all the selected campaigns combined.  Categories can be defined under the -System Statuses- in the admin section."); ?><BR>
<?php echo _QXZ("<U>PER LIST DETAIL STATS</U> = Shows each list in the campaign(s) selected by the user, with a breakdown of each status flag (and percentage of each flag) and status within that list."); ?><BR>

<A NAME="LISTS_pass_report">
<BR>
<B><?php echo _QXZ("Lists Pass Report"); ?> -</B><?php echo _QXZ("This is a list inventory report, not a calling report. This report will show statistics for all of the lists in the selected campaigns"); ?><BR>
<?php echo _QXZ("<U>FIRST LOAD DATE</U> = Date list first had leads loaded."); ?><BR>
<?php echo _QXZ("<U>LIST ID and NAME</U> = List ID number and name of list."); ?><BR>
<?php echo _QXZ("<U>CAMPAIGN</U> = Campaign that the list currently belongs to."); ?><BR>
<?php echo _QXZ("<U>LEAD COUNT</U> = Number of leads in the list."); ?><BR>
<?php echo _QXZ("<U>ACTIVE</U> = Whether or not the lead was active."); ?><BR>
<?php echo _QXZ("<U>CONTACTS 1st-5th,LIFE PASS</U> = Leads that were -human answered- (dispoed a status where HUMAN ANSWER is set to -Y- on the statuses section) on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>CNT RATE 1st-5th PASS</U> = Percentage of leads on the list that were -human answered- on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>SALE 1st-5th,LIFE PASS</U> = Leads that were sold to (dispoed a status where SALE is set to -Y- on the statuses section) on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>CNV RATE 1st-5th PASS</U> = Percentage of leads on the list that were sold to on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>DNC 1st-5th,LIFE PASS</U> = Leads that were dispositioned -do not call- (dispoed a status where DNC is set to -Y- on the statuses section) on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>DNC RATE 1st-5th,LIFE PASS</U> = Percentage of leads on the list that were -do not call- on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>CUST CONTACT 1st-5th,LIFE PASS</U> = Leads that were -customer contacted- (dispoed a status where CUSTOMER CONTACT is set to -Y- on the statuses section) on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>CUCT RATE 1st-5th,LIFE PASS</U> = Percentage of leads on the list that were -customer contacted- on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>UNWORKABL 1st-5th,LIFE PASS</U> = Leads that were -unworkable- (dispoed a status where UNWORKABLE is set to -Y- on the statuses section) on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>UNWK RATE 1st-5th,LIFE PASS</U> = Percentage of leads on the list that were -unworkable- on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>SCHEDL CB 1st-5th,LIFE PASS</U> = Leads that were -scheduled callbacks- (dispoed a status where SCHEDULED CALLBACK is set to -Y- on the statuses section) on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>SHCB RATE 1st-5th,LIFE PASS</U> = Percentage of leads on the list that were -scheduled callbacks- on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>COMPLETED 1st-5th,LIFE PASS</U> = Leads that were -completed- (dispoed a status where COMPLETED is set to -Y- on the statuses section) on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>
<?php echo _QXZ("<U>COMP RATE 1st-5th,LIFE PASS</U> = Percentage of leads on the list that were -completed- on or after the first-fifth call attempt, or LIFE if contacted as such at any point ever."); ?><BR>


<A NAME="LISTS_stats">
<BR>
<B><?php echo _QXZ("Lists Statuses Report"); ?> -</B><?php echo _QXZ("This report gives a detailed breakdown of the contents of a list or lists."); ?><BR>
<?php echo _QXZ("<U>TOTAL LIST ID SUMMARY</U> = Shows each list selected by the user, the count of leads in the list, and whether the list is active or inactive."); ?><BR>
<?php echo _QXZ("<U>TOTAL STATUS FLAGS SUMMARY</U> = Shows a breakdown of the status flags for all the selected lists combined. Status flags are set in the -Statuses- section of a campaign or the -System Statuses-."); ?><BR>
<?php echo _QXZ("<U>LIST ID SUMMARY</U> = Each list selected in the report will be listed separately after the TOTALS summaries.  The list summary gives specific information about each particular list."); ?><BR>
<?php echo _QXZ("<U>STATUS FLAGS BREAKDOWN</U> = Same as <U>TOTAL STATUS FLAGS SUMMARY</U>, but only for one list."); ?><BR>
<?php echo _QXZ("<U>STATUS BREAKDOWN</U> = A breakdown of each status in the individual list, along with the status description, number of leads, and percentage relative to the total leads in the list."); ?><BR>

<A NAME="OUTBOUNDsummary_interval">
<BR>
<B><?php echo _QXZ("Outbound Summary Interval Report"); ?> -</B><?php echo _QXZ("This report gives statistical information on selected campaigns, both as a summary of all selected campaigns and also by a selected interval for each individual campaign on outbound calling activity based on a date range and call time."); ?><BR>
<?php echo _QXZ("<U>CAMPAIGN</U> = The campaign being reported on."); ?><BR>
<?php echo _QXZ("<U>TOTAL CALLS</U> = The number of calls taken either by a campaign or during a time interval during a campaign, depending on the report."); ?><BR>
<?php echo _QXZ("<U>SYSTEM RELEASE CALLS</U> = The number of calls terminated by the dialer per campaign or during a time interval for a campaign, depending on the report."); ?><BR>
<?php echo _QXZ("<U>AGENT RELEASE CALLS</U> = The number of calls terminated by the agent per campaign or during a time interval for a campaign, depending on the report."); ?><BR>
<?php echo _QXZ("<U>SALE CALLS</U> = Total number of calls the user handled where the SALE status flag is set to Y, made either on a campaign or during a time interval during a campaign, depending on the report."); ?><BR>
<?php echo _QXZ("<U>DNC CALLS</U> = The number of DNC made either on a campaign or during a time interval during a campaign, depending on the report."); ?><BR>
<?php echo _QXZ("<U>NO ANSWER PERCENT</U> = The percentage of calls dispositioned as no answer against the TOTAL CALLS."); ?><BR>
<?php echo _QXZ("<U>DROP PERCENT</U> = The percentage of calls dispositioned as DROPs against the TOTAL CALLS."); ?><BR>
<?php echo _QXZ("<U>AGENT LOGIN TIME (H:M:S)</U> = The total pause, wait, talk, and dispo times logged by agents for the campaign/interval."); ?><BR>
<?php echo _QXZ("<U>AGENT PAUSE TIME (H:M:S)</U> = The total pause time logged by agents for the campaign/interval."); ?><BR>
<?php echo _QXZ("<U>INTERVAL</U> = The interval (in -HHii- format) being reported on under the campaign."); ?><BR>

<A NAME="VDADstats">
<BR>
<B><?php echo _QXZ("Outbound Calling Report"); ?> -</B><?php echo _QXZ("This report gives several reports on outbound calling for campaigns and lists for a specified date range"); ?><BR>
<?php echo _QXZ("<B>CALL HANGUP REASON STATS</B>"); ?><BR>
<?php echo _QXZ("<U>HANGUP REASON</U> = The reason the call terminated."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls terminated from the aforementioned reason."); ?><BR>

<?php echo _QXZ("<B>CALL STATUS STATS</B>"); ?><BR>
<?php echo _QXZ("<U>STATUS</U> = The call disposition."); ?><BR>
<?php echo _QXZ("<U>DESCRIPTION</U> = The description of the status."); ?><BR>
<?php echo _QXZ("<U>CATEGORY</U> = The category of the status."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls resulting the disposition."); ?><BR>
<?php echo _QXZ("<U>CALL TIME - TOTAL TIME</U> = The total time spent on calls resulting in the disposition."); ?><BR>
<?php echo _QXZ("<U>CALL TIME - AVG TIME</U> = The average amount of time spent on calls resulting in the disposition."); ?><BR>
<?php echo _QXZ("<U>CALL TIME - CALLS/HOUR</U> = The number of calls per hour where the call resulted in the disposition - this is relative to the total time of all dispositions, not just this one."); ?><BR>
<?php echo _QXZ("<U>AGENT TIME - CALLS/HOUR</U> = The number of calls per agent hour where the call resulted in the disposition, relative to the total time agents spent in call for all dispositions."); ?><BR>

<?php echo _QXZ("<B>LIST ID STATS</B>"); ?><BR>
<?php echo _QXZ("<U>LIST</U> = The list ID number - all lists meeting the selected criteria are displayed here."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of outbound calls placed from the aforementioned list."); ?><BR>

<?php echo _QXZ("<B>AGENT PRESET DIALS</B>"); ?><BR>
<?php echo _QXZ("<U>PRESET NAME</U> = The preset name logged in the user_call_log table (where applicable) for the outbound calls meeting the selected criteria."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls logged with the aforementioned preset."); ?><BR>

<?php echo _QXZ("<B>CUSTOM STATUS CATEGORY STATS</B>"); ?><BR>
<?php echo _QXZ("<U>CATEGORY</U> = The category the custom status is currently assigned to."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of calls logged with the aforementioned category."); ?><BR>
<?php echo _QXZ("<U>DESCRIPTION</U> = The description of the category."); ?><BR>

<?php echo _QXZ("<B>AGENT STATS</B>"); ?><BR>
<?php echo _QXZ("<U>AGENT</U> = The user ID and agent involved on outbound calls meeting the selected criteria."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = The number of outbound calls handled by the aforementioned agent."); ?><BR>
<?php echo _QXZ("<U>TIME H:M:S</U> = The total amount of time the agent spent on the outbound calls."); ?><BR>
<?php echo _QXZ("<U>AVERAGE</U> = The average amount of time the agent spent on the outbound calls."); ?><BR>


<A NAME="called_counts_multilist_report">
<BR>
<B><?php echo _QXZ("Called Counts List IDs Report"); ?> -</B><?php echo _QXZ("This report will show a called count breakdown by status for a list or group of lists, optionally within a given date range.  Lists may also be selected by campaign."); ?><BR>

<A NAME="fcstats">
<BR>
<B><?php echo _QXZ("Fronter - Closer Report"); ?> -</B><?php echo _QXZ("This report displays fronter and closer information on an in-group for the selected date - useful in local closer campaigns so you can see the activity on internal transfers."); ?><BR>
<?php echo _QXZ("<B>FRONTER STATS</B>"); ?><BR>
<?php echo _QXZ("<U>AGENT</U> = The agent making the transfer (fronter)."); ?><BR>
<?php echo _QXZ("<U>XFERS</U> = The number of transfers the agent made."); ?><BR>
<?php echo _QXZ("<U>SALE %</U> = Percentage of transfers the agent made that resulted in a sale."); ?><BR>
<?php echo _QXZ("<U>SALE</U> = Number of sales (statuses where sale status flag is set to Y in the -Statuses- section) made on the agents transfers."); ?><BR>
<?php echo _QXZ("<U>DROP</U> = Number of transfers that were dropped (did not make it to a live closer)."); ?><BR>
<?php echo _QXZ("<U>OTHER</U> = Number of transfers that were not dropped, but also not sold by the closer."); ?><BR>

<?php echo _QXZ("<B>CLOSER STATS</B>"); ?><BR>
<?php echo _QXZ("<U>AGENT</U> = The agent receiving the transfer (closer)."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Total calls answered by the closer."); ?><BR>
<?php echo _QXZ("<U>SALE</U> = Number of sales (statuses where sale status flag is set to Y in the -Statuses- section) made on the transfers the closer fielded."); ?><BR>
<?php echo _QXZ("<U>DROP</U> = Number of calls the closer received that were dispoed as dropped."); ?><BR>
<?php echo _QXZ("<U>OTHER</U> = Calls the closer fielded that did not result in sales."); ?><BR>
<?php echo _QXZ("<U>CONV %</U> = Percentage of transfers the closer received that resulted in sales."); ?><BR>

<A NAME="inbound_forecasting">
<BR>
<B><?php echo _QXZ("Inbound forecasting"); ?> -</B><?php echo _QXZ("This report uses Erlang B formulas to generate call center stats based on inbound call activity for selected campaigns and/or ingroups across a specified date range.  From these statistics and also by entering a desired drop call rate, this report can be used to calculate a recommend number of agents to have on the phones to reach the desired drop rate entered."); ?><BR><BR>

<?php echo _QXZ("<U>CALLING HOUR</U> = The hour interval being reported on."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Number of calls showing activity in this hour interval.  Unlike other reports, <u>this includes calls that start in the preceding hour, if the length of the call causes the call to end in the current hour</u>.  Example: an inbound call to the system begins at 9:59am and lasts for three minutes, ending at 10:02am.  This call will be counted in both intervals."); ?><BR>
<?php echo _QXZ("<U>TOTAL TIME</U> = The total amount of time calls were active within this call interval.  This includes time in queue waiting for an agent, and as stated for the CALLS section above, calls starting in one hour and ending in the other will have the time spent in each interval counted in both.  In the above example, 1 minute will be attributed to the 9am hour and 2 minutes to the 10am hour."); ?><BR>
<?php echo _QXZ("<U>AVERAGE TIME</U> = The average call length for the interval, taken by dividing the total time of call activity within this interval divided by the number of calls active within this interval."); ?><BR>
<?php echo _QXZ("<U>DROPPED HOURS</U> = The total amount of time taken by calls that were dropped with time active within this call interval."); ?><BR>
<?php echo _QXZ("<U>BLOCKING</U> = The drop rate, expressed as a decimal in TEXT or a percent in graph form, of an hourly interval.  This is taken by dividing the number dropped calls active in the hour by the number of calls active in the hour."); ?><BR>
<?php echo _QXZ("<U>ERLANGS</U> = Number of Erlangs for the hour.  Erlangs are calculated by taking the number of calls received in an interval, multiplied by the average duration of a call expressed as a decimal value relative to the interval.  For example, if 100 calls came in during an hour, and the average call length is 6 minutes, the Erlang value is 100 calls/hour * .1 hour (6 minutes is 1/10th of an hour), or 10."); ?><BR>

<A NAME="inbound_forecasting_gos"><?php echo _QXZ("<U>GOS</U> = -Grade of Service-.  This is the probability that a call is dropped, which is given by the equation"); ?><BR><BR> GoS = (E<sup>M</sup>/M!)<big>/</big>(<big>&#8721;</big><sup>M</sup><sub style="margin-left:-10px;">n=0</sub> E<sup>n</sup>/n!)<BR><BR>
<?php echo _QXZ("where -GoS- is the desired drop rate, -E- is the Erlang value, and -M- is the number of lines/agents needed"); ?><BR>


<?php echo _QXZ("<U>REC AGENTS</U> = The recommended number of agents needed to meet the desired drop rate or the desired queue probability.  For the Erlang B report it uses the formula"); ?><BR><BR> GoS = (E<sup>M</sup>/M!)<big>/</big>(<big>&#8721;</big><sup>M</sup><sub style="margin-left:-10px;">n=0</sub> E<sup>n</sup>/n!)<BR> <BR>
<?php echo _QXZ("where -GoS- is the desired drop rate, -E- is the Erlang value, and -M- is the number of lines/agents needed.  For the Erlang C report, the equation used is:"); ?><BR><BR> P<sub><small>queued</small></sub> = (E<sup>M</sup>/M!)<big>/</big>[E<sup>M</sup>/M! + (1 - E/M)<big>&#8721;</big><sup>M-1</sup><sub style="margin-left:-23px;">n=0</sub> E<sup>n</sup>/n!]<BR><BR>
<?php echo _QXZ("where P<sub><small>queued</small></sub> is the queue probability, -E- is the Erlang value, and -M- is the number of lines/agents needed"); ?><BR>
<?php echo _QXZ("<U>EST AGENTS</U> = The estimated number of agents based on the current drop rate and Erlangs, as entered by the user, for the hours being reported on.  This is reached by using the Erlang B formula, which  is"); ?><BR><BR> GoS = (E<sup>M</sup>/M!)<big>/</big>(<big>&#8721;</big><sup>M</sup><sub style="margin-left:-10px;">n=0</sub> E<sup>n</sup>/n!)<BR><BR>
<?php echo _QXZ("where -GoS- is the current drop rate, -E- is the Erlang value, and -M- is the number of lines/agents"); ?><BR>
<?php echo _QXZ("<U>CALLS/AGENT</U> = The average number of calls each agent received for the time interval, gotten by dividing the number of calls by the number of agents (estimated, if an -actual agent- value is not given)"); ?><BR>

<BR><BR><BR><BR>
<A NAME="erlang_report">
<BR>
<B><?php echo _QXZ("Erlang report"); ?> -</B><?php echo _QXZ("This report uses Erlang B and C formulas to generate call center stats based on call activity for selected campaigns and/or ingroups across a specified date range.  From these statistics and also by entering a desired drop call rate, this report can be used to calculate a recommend number of agents to have on the phones to reach the desired drop rate entered, and depending on whether the report type being run is -B- or -C-, it will display statistics pertaining to call loss (drop) probability, or wait time probability."); ?><BR><BR>
<B><?php echo _QXZ("COST ANALYSIS STATS"); ?></B><BR><?php echo _QXZ("This is an entirely optional set of data that can be entered to calculate the monetary performance of campaigns/call centers."); ?><BR>
<?php echo _QXZ("<U>Actual agents</U> = If you know the actual number of agents and wish to use this in the report caluclations, enter a non-zero value here.  If you leave this blank the report will use the number of agents it estimates is in the dialer to make its reports."); ?><BR>
<?php echo _QXZ("<U>Hourly pay per agent</U> = Hourly wages an average employee makes."); ?><BR>
<?php echo _QXZ("<U>Revenue per sale</U> = How much money each sale made in the selected campaigns/ingroups is worth."); ?><BR>
<?php echo _QXZ("<U>Chance of sale</U> = The estimated percentage of calls that result in a sale (the actual percentage is provided after the report is run)."); ?><BR>
<?php echo _QXZ("<U>Retry rate</U> = The percentage of callers that attempt to retry after their call is dropped."); ?><BR><BR>

<?php echo _QXZ("<U>CALLING HOUR</U> = The hour interval being reported on."); ?><BR>
<?php echo _QXZ("<U>CALLS</U> = Number of calls showing activity in this hour interval.  Unlike other reports, <u>this includes calls that start in the preceding hour, if the length of the call causes the call to end in the current hour</u>.  Example: an inbound call to the system begins at 9:59am and lasts for three minutes, ending at 10:02am.  This call will be counted in both intervals."); ?><BR>
<?php echo _QXZ("<U>TOTAL TIME</U> = The total amount of time calls were active within this call interval.  This includes time in queue waiting for an agent, and as stated for the CALLS section above, calls starting in one hour and ending in the other will have the time spent in each interval counted in both.  In the above example, 1 minute will be attributed to the 9am hour and 2 minutes to the 10am hour."); ?><BR>
<?php echo _QXZ("<U>AVERAGE TIME</U> = The average call length for the interval, taken by dividing the total time of call activity within this interval divided by the number of calls active within this interval."); ?><BR>
<?php echo _QXZ("<U>DROPPED HOURS</U> = The total amount of time taken by calls that were dropped with time active within this call interval."); ?><BR>
<?php echo _QXZ("<U>BLOCKING</U> = The drop rate, expressed as a decimal in TEXT or a percent in graph form, of an hourly interval.  This is taken by dividing the number dropped calls active in the hour by the number of calls active in the hour."); ?><BR>
<?php echo _QXZ("<U>ERLANGS</U> = Number of Erlangs for the hour.  Erlangs are calculated by taking the number of calls received in an interval, multiplied by the average duration of a call expressed as a decimal value relative to the interval.  For example, if 100 calls came in during an hour, and the average call length is 6 minutes, the Erlang value is 100 calls/hour * .1 hour (6 minutes is 1/10th of an hour), or 10."); ?><BR>

<A NAME="erlang_report_gos"><?php echo _QXZ("<U>GOS</U> = -Grade of Service-.   A -B- report value - this is the probability that a call is dropped, which is given by the equation"); ?><BR><BR> GoS = (E<sup>M</sup>/M!)<big>/</big>(<big>&#8721;</big><sup>M</sup><sub style="margin-left:-10px;">n=0</sub> E<sup>n</sup>/n!)<BR><BR>
<?php echo _QXZ("where -GoS- is the desired drop rate, -E- is the Erlang value, and -M- is the number of lines/agents needed"); ?><BR>

<?php echo _QXZ("<U>QUEUE PROB</U> =  A -C- report value - the probability that a call is queued, which is given by the equation"); ?><BR><BR> P<sub><small>queued</small></sub> = (E<sup>M</sup>/M!)<big>/</big>[E<sup>M</sup>/M! + (1 - E/M)<big>&#8721;</big><sup>M-1</sup><sub style="margin-left:-23px;">n=0</sub> E<sup>n</sup>/n!]<BR><BR>
<?php echo _QXZ("where P<sub><small>queued</small></sub> is the queue probability, -E- is the Erlang value, and -M- is the number of lines/agents needed"); ?><BR>

<?php echo _QXZ("<U>AVERAGE ANSWER</U> = A -C- report value - the average speed of answer, or average call waiting time.  This is given by the equation"); ?><BR><BR> ASA = (T*P<sub><small>queued</small></sub>)/(M-E)<BR><BR>
<?php echo _QXZ("-ASA- is the average speed of answer, -T- is the average duration of the call, P<sub><small>queued</small></sub> is the queue probability, -E- is the Erlang value, and -M- is the number of lines/agents needed.  Both this and the P<sub><small>queued</small></sub> formulas above are only valid if -M- is greater than -E-, that is, the number of trunks in a system is greater than the number of Erlangs."); ?><BR>

<?php echo _QXZ("<U>REC AGENTS</U> = The recommended number of agents needed to meet either the desired drop rate or the desired queue probability.  These values are entered by the user and depending on whether the report being run is type -B- or type -C- the report will base their estimate on one of those values.  For the Erlang B report it uses the formula"); ?><BR><BR> GoS = (E<sup>M</sup>/M!)<big>/</big>(<big>&#8721;</big><sup>M</sup><sub style="margin-left:-10px;">n=0</sub> E<sup>n</sup>/n!)<BR> <BR>
<?php echo _QXZ("where -GoS- is the desired drop rate, -E- is the Erlang value, and -M- is the number of lines/agents needed.  For the Erlang C report, the equation used is:"); ?><BR><BR> P<sub><small>queued</small></sub> = (E<sup>M</sup>/M!)<big>/</big>[E<sup>M</sup>/M! + (1 - E/M)<big>&#8721;</big><sup>M-1</sup><sub style="margin-left:-23px;">n=0</sub> E<sup>n</sup>/n!]<BR><BR>
<?php echo _QXZ("where P<sub><small>queued</small></sub> is the queue probability, -E- is the Erlang value, and -M- is the number of lines/agents needed"); ?><BR>
<?php echo _QXZ("<U>EST AGENTS</U> = The estimated number of agents based on the current drop rate and Erlangs, as entered by the user, for the hours being reported on.  This is reached by using the Erlang B formula, for both the B and C report.  Since Vicidial does not log what calls were queued in outbound dialing, the -C- report instead uses the drop rate of outbound calls to estimate agents.  The formula is"); ?><BR><BR> GoS = (E<sup>M</sup>/M!)<big>/</big>(<big>&#8721;</big><sup>M</sup><sub style="margin-left:-10px;">n=0</sub> E<sup>n</sup>/n!)<BR><BR>
<?php echo _QXZ("where -GoS- is the current drop rate, -E- is the Erlang value, and -M- is the number of lines/agents"); ?><BR>
<?php echo _QXZ("<U>CALLS/AGENT</U> = The average number of calls each agent received for the time interval, gotten by dividing the number of calls by the number of agents (estimated, if an -actual agent- value is not given)"); ?><BR>
<?php echo _QXZ("<U>REV/CALL</U> = Average revenue per call, taken by multiplying the revenue per sale by the number of sales, then dividing by the number of calls"); ?><BR>
<?php echo _QXZ("<U>REV/AGENT</U> = Average revenue generated per agent, based on the total revenue (sales * revenue per sale) divided by the number of agents (estimated, if an -actual agent- value is not given)"); ?><BR>
<?php echo _QXZ("<U>TOTAL REV</U> = Total revenue, gotten by multiplying the number of sales by the revenue per sale"); ?><BR>
<?php echo _QXZ("<U>TOTAL COST</U> = Total cost, gotten by multiplying the agent rate by the number of agents (estimated, if an -actual agent- value is not given)"); ?><BR>
<?php echo _QXZ("<U>MARGIN</U> = The difference between the total revenue and the total cost"); ?><BR>


<BR><BR><BR><BR>
<A NAME="rt_monitor_log_report">
<BR>
<B><?php echo _QXZ("Real-time monitoring log report"); ?> -</B><?php echo _QXZ("This report enables level 9 users, users with Admin Utilities privileges, to search the logs of Vicidial managers who utilized the monitoring capabilities of the real-time report.  Searches can be done for date ranges, campaigns, managers, and agents being monitored, and the results can be sorted by any of the following data returned by the report."); ?><BR><BR>
<?php echo _QXZ("<U>START TIME</U> = The time the manager started monitoring an agent"); ?><BR>
<?php echo _QXZ("<U>MANAGER</U> = The manager who initiated the monitoring session"); ?><BR>
<?php echo _QXZ("<U>MANAGER SERVER</U> = The IP address of the server the manager was monitoring from"); ?><BR>
<?php echo _QXZ("<U>MANAGER PHONE</U> = The phone login the manager used to monitor the agent"); ?><BR>
<?php echo _QXZ("<U>MANAGER IP</U> = The IP address of the computer the manager was monitoring from"); ?><BR>
<?php echo _QXZ("<U>AGENT MONITORED</U> = The agent ID and name of the agent being monitored"); ?><BR>
<?php echo _QXZ("<U>AGENT SERVER</U> = The IP address of the server the agent phone was operating from"); ?><BR>
<?php echo _QXZ("<U>AGENT STATUS</U> = The dialer status of the agent at the time the monitoring session was initiated"); ?><BR>
<?php echo _QXZ("<U>AGENT SESSION</U> = The session ID of the agent at the time the monitoring session was initiated"); ?><BR>
<?php echo _QXZ("<U>LEAD ID</U> = The lead ID the agent was speaking to at the time the monitoring session was initiated.  If the agent was not in a call at the time the manager started monitoring them, this value will be -0-"); ?><BR>
<?php echo _QXZ("<U>CAMPAIGN</U> = The campaign ID the agent being monitored was placing calls from at the time they started being monitored"); ?><BR>
<?php echo _QXZ("<U>END TIME</U> = The time the manager stopped monitoring the agent, ending the monitoring session"); ?><BR>
<?php echo _QXZ("<U>LENGTH</U> = The total length in seconds of the monitoring session"); ?><BR>
<?php echo _QXZ("<U>TYPE</U> = The type of monitoring session that the manager was running"); ?><BR>



<BR><BR><BR><BR>
<A NAME="rt_whiteboard_report">
<BR>
<B><?php echo _QXZ("Real-Time Whiteboard report"); ?> -</B><?php echo _QXZ("This report allows the user to display reports in a large graph form with associated statistics printed with the report, and the report will be refreshed at a set interval.  This is useful for overhead projections where the user needs to display a set of data in a comprehensive, easy-to-read format.  Also, when the user selects a report to display, any report option relevant to that report will be highlighted in red: campaigns, status flags, etc...."); ?><BR><BR>
<A NAME="rt_whiteboard_report-report_type">
<?php echo _QXZ("<U>Report Type</U> = The type of report to display."); ?><BR>
<UL>
<?php echo _QXZ("<LI>Disposition Totals - A breakdown of disposition totals of calls made."); ?><BR>
<?php echo _QXZ("<LI>Agent Performance Totals - Shows total calls and sales for agents within a specified date-time range."); ?><BR>
<?php echo _QXZ("<LI>Agent Performance Rates - Shows conversion rates for agents within a specified date-time range."); ?><BR>
<?php echo _QXZ("<LI>Team Performance Totals - Shows total calls and sales for teams/user groups within a specified date-time range."); ?><BR>
<?php echo _QXZ("<LI>Team Performance Rates - Shows converstion rates for teams/user groups within a specified date-time range."); ?><BR>
<?php echo _QXZ("<LI>Floor Performance Totals (ticker) - Shows cumulative total of sales in a -ticker- format-line graph, with an option to draw a target gross sales."); ?><BR>
<?php echo _QXZ("<LI>Floor Performance Rates (ticker) - Shows time-elapsed conversion in a -ticker- format-line graph, with an option to draw a target conversion rate."); ?><BR>
<?php echo _QXZ("<LI>Ingroup Performance Total - Shows total calls and sales for selected ingroups within a specified date-time range."); ?><BR>
<?php echo _QXZ("<LI>Ingroup Performance Rates - Shows conversion rates for selected ingroups within a specified date-time range."); ?><BR>
<?php echo _QXZ("<LI>DID Performance Total - Shows total calls and sales for selected DIDs within a specified date-time range."); ?><BR>
<?php echo _QXZ("<LI>DID Performance Rates - Shows conversion rates for selected DIDs within a specified date-time range."); ?><BR>
</UL><BR>
<A NAME="rt_whiteboard_report-parameters">
<?php echo _QXZ("<U>User Groups,In-groups,Campaigns,Users,DIDs,Status flags</U> = Parameters that may or may not be required based on the type of report run.  Available options are dependent on the user permissions. Additionally, selecting specific campaigns combined with selecting -ALL- for ingroups will restrict the -ALL- selection to only ingroups for the selected campaigns.  Specific campaign selection will also limit the statuses counted if any status flags are selected."); ?><BR>
<A NAME="rt_whiteboard_report-target_per_unit">
<?php echo _QXZ("<U>Target per unit</U> = The target number of sales per -unit-, which refers to users,user groups,ingroups,DIDs,etc depending on the report type selected, displayed as a horizontal line."); ?><BR>
<A NAME="rt_whiteboard_report-target_gross_sales">
<?php echo _QXZ("<U>Target gross sales</U> = The target number of total sales for ticker reports, displayed as a horizontal line.."); ?><BR>
<A NAME="rt_whiteboard_report-start_date">
<?php echo _QXZ("<U>Start date/time</U> = If filled out, the starting date and time that the report will use up through the current date and time when compiling the report."); ?><BR>
<A NAME="rt_whiteboard_report-show_results">
<?php echo _QXZ("<U>Show results for the past X hours</U> = If filled out and the start date-time is NOT filled out, the selected report will compile data starting at the time X hours ago up through the current time, which makes it a dynamic starting time as opposed to the set date-time used in the previous parameter."); ?><BR>



<BR><BR><BR><BR>
<A NAME="api_log_report">
<BR>
<B><?php echo _QXZ("API Log report"); ?> -</B><?php echo _QXZ("This report shows all records in the API logging table that meet the report criteria.  Reports can be run by date range that the API was accessed, the user, agent user, the API function called, and the API result.  The report can also be run to include the actual URL of the API that was called and the IP address the request came from."); ?>
<BR><BR>
<?php echo _QXZ("Additionally, if this extended version of the report is run, the variables used in the API call can be listed separately if the user creates a system container in the System Containers settings by the name of API_LOG_URL_COLUMNS <B>(name must be exact)</B>.  In this container, variable names are listed individually per line, and each listed variable is given its own column in the detailed URL report."); ?><BR><BR>
<A NAME="api_log_report-parameters">



<BR><BR><BR><BR>
<B><FONT SIZE=3><?php echo _QXZ("Nanpa cellphone filtering"); ?></FONT></B><BR><BR>


<A NAME="nanpa-running">
<BR>
<B><?php echo _QXZ("Currently running NANPA scrubs"); ?> -</B><?php echo _QXZ("Displays a log of the currently running scrubs, including: Start Time, Leads Count, Filter Count, Status Line, Time to Complete, Field Updated, and Field excluded"); ?>

<BR>
<A NAME="nanpa-settings">
<BR>
<B><?php echo _QXZ("Inactive Lists"); ?> -</B><?php echo _QXZ("Contains all the inactive lists, that are eligible to be scrubbed.  A list can only be scrubbed if Active is set to N on the list."); ?>

<BR>
<B><?php echo _QXZ("Field to Update"); ?> -</B><?php echo _QXZ("Indicates which lead field will contain the scrub result -Cellphone, Landline, or Invalid-.  If NONE is selected it will use the default field Country_Code."); ?>

<BR>
<B><?php echo _QXZ("Exclusion Field"); ?> -</B><?php echo _QXZ("In conjunction with Exclusion Value allows administrators to indicate leads that are not to be scanned. This field indicates which list field contains the scrub result."); ?>

<BR>
<B><?php echo _QXZ("Exclusion Value"); ?> -</B><?php echo _QXZ("In conjunction with Exclusion Field allows administrators to indicate leads that are not to be scanned. This field indicates which scrub result -either Cellphone, Landline, or Invalid- should be ignored."); ?>

<BR>
<B><?php echo _QXZ("List Conversions"); ?> -</B><?php echo _QXZ("Administrators can indicate a list ID in any of the fields -Cellphone, Landline, or Invalid- and the scrub will move all the corresponding stamped leads to that list."); ?>

<BR>
<B><?php echo _QXZ("Time until Activation"); ?> -</B><?php echo _QXZ("allows The administrator to set how long, in the future, the scrub will occur."); ?>

<BR>
<A NAME="nanpa-log">
<BR>
<B><?php echo _QXZ("View Past Scrubs"); ?> -</B><?php echo _QXZ("-link at the bottom- Click to see a log of all previous scrubs, including: Start Time, Leads Count, Filter Count, Status Line, Time to Complete, Field updated, Field Excluded, and total leads in each category."); ?>



<BR><BR><BR><BR>
<A NAME="cb-bulk">
<B><FONT SIZE=3><?php echo _QXZ("Callbacks Bulk Move"); ?></FONT></B><BR><BR>

<?php echo _QXZ("This page is used to move callbacks meeting a set of selected criteria to a new list and or a new lead status.  It can also purge called records from the callbacks table."); ?>

<BR>
<A NAME="cb-bulk-campaigns">
<BR>
<B><?php echo _QXZ("Campaigns with callbacks"); ?> -</B><?php echo _QXZ("The current list of all campaigns having at least one callback - select one or more to move callbacks from these campaigns in bulk to new lists and or assign new statuses.  This will combine with other selected criteria to make a final list of callbacks to move."); ?>

<BR>
<A NAME="cb-bulk-lists">
<BR>
<B><?php echo _QXZ("Lists with callbacks"); ?> -</B><?php echo _QXZ("All list IDs having at least one callback - select one or more to move callbacks from these lists in bulk to new lists and or assign new statuses.  This will combine with other selected criteria to make a final list of callbacks to move."); ?>

<BR>
<A NAME="cb-bulk-usergroups">
<BR>
<B><?php echo _QXZ("User groups with callbacks"); ?> -</B><?php echo _QXZ("The current list of all user groups having at least one callback - select one or more to move callbacks from these user groups to new lists and or assign new statuses.  This will combine with other selected criteria to make a final list of callbacks to move."); ?>

<BR>
<A NAME="cb-bulk-agents">
<BR>
<B><?php echo _QXZ("Agents with callbacks"); ?> -</B><?php echo _QXZ("The current list of all agents having at least one callback - select one or more to move callbacks from these agents to new lists and or assign new statuses.  This will combine with other selected criteria to make a final list of callbacks to move."); ?>

<BR>
<A NAME="cb-bulk-purge">
<BR>
<B><?php echo _QXZ("Purge called records"); ?> -</B><?php echo _QXZ("If this box is checked, before the leads meeting the selected criteria are moved, the callbacks table will purge itself of all inactive records in the table.  This includes records that have a status of INACTIVE, OR any record still in the callback table that is LIVE but whose status in the vicidial_list table is NOT listed as a callback, i.e. it has been called, lead has called in, lead dispo has been modified.  The page will notify you how many leads will be purged before you confirm that you would like to proceed."); ?>
<BR>
<B><?php echo _QXZ("Purge uncalled records"); ?> - </B><?php echo _QXZ("If this box is checked, before the leads meeting the selected criteria are moved, the callbacks table will purge itself of all uncalled records in the table.  This includes records that have a status of ACTIVE, OR any record still in the callback table that is LIVE but whose status in the vicidial_list table is still listed as a callback, i.e. it has not been called, has not called in, and has not had the lead dispo modified.  The page will notify you how many leads will be purged before you confirm that you would like to proceed."); ?>


<BR>
<A NAME="cb-bulk-liveanduncalled">
<BR>
<B><?php echo _QXZ("Live and uncalled for over XX days"); ?> -</B><?php echo _QXZ("Selecting a date range from this drop down menu will filter out any callback where the scheduled callback time is past the selected number of days from the current date and time.  This will combine with other selected criteria to make a final list of callbacks to move."); ?>

<BR>
<A NAME="cb-bulk-newlist">
<BR>
<B><?php echo _QXZ("Transfer to List ID"); ?> -</B><?php echo _QXZ("The list ID to which all callbacks will be moved. The move will occur on the records in vicidial_list table - the matching records in vicidial_callbacks will be removed."); ?>

<BR>
<A NAME="cb-bulk-newstatus">
<BR>
<B><?php echo _QXZ("New Status"); ?> -</B><?php echo _QXZ("The new vicidial_list status the selected callbacks will be updated to. This affects the vicidial_list table ONLY."); ?>



<BR><BR><BR><BR>
<A NAME="alt_multi_urls">
<B><FONT SIZE=3><?php echo _QXZ("Alternate Multi URLs"); ?></FONT></B><BR><BR>

<?php echo _QXZ("This page allows you to define Alternate URLs with conditions in place of the simple URL option for Dispo, Start, Add Lead or No Agent URLs. The RANK field will define the order in which the URLs are requested, this is important because if you have a URL that may take a few seconds to run ranked as 1, that will mean that any URLs ranked after it will have to wait to be run until that first URL request receives a response. The ACTIVE field will determine whether the specific URL is run. The STATUSES field only works for the Dispo URLs and will determine which disposition statuses will trigger the URL being run, if you want to run the entry for all statuses just fill in ---ALL--- in this field, if you want only a few statuses, separate them by spaces. If populated, the LISTS field will limit the calls that the URL is run from to only the list IDs listed in this field. To have it run for all lists, leave the field blank, if you want only a few lists, separate them by spaces. The URL field works the same as it would in the URL option in campaigns, in-groups and lists, and it is also limited to 2000 characters in length. If you want to delete a URL, it must first be set to not active, then you can click on the DELETE link below the SUBMIT button for that URL entry."); ?>


<BR><BR><BR><BR>
<A NAME="amm_multi">
<B><FONT SIZE=3><?php echo _QXZ("AM Message Wildcards"); ?></FONT></B><BR><BR>

<?php echo _QXZ("This page allows you to define Am Message Wildcards that will check lead data from the defined lead fields for matches in the defined order specified on this page. For example, if you add a wildcard with the word -vacation- tied to the vendor_lead_code field, and the lead being played a message has -vacation- in that field, then it will hear the message defined in that AM Message Wildcard record. If you want to delete a wildcard, it must first be set to not active, then you can click on the DELETE link below the SUBMIT button for that wildcard entry."); ?>


<BR><BR><BR><BR>
<A NAME="user_list_new_limits">
<B><FONT SIZE=3><?php echo _QXZ("User List New Lead Limits"); ?></FONT></B><BR><BR>

<?php echo _QXZ("If the system setting for New Leads Per List Limit is enabled, then this page allows for the setting of per-user per-list new lead limit overrides to those List limits. This feature will only work properly if the campaign is set to either the MANUAL or INBOUND_MAN Dial Method and No Hopper dialing is enabled."); ?>



<BR><BR><BR><BR>

<A NAME="custom_reports_admin">
<B><FONT SIZE=3><?php echo _QXZ("Custom Reports Admin"); ?></FONT></B><BR><BR>
<BR>


<BR>
<A NAME="custom_reports_admin-report_name">
<BR>
<B><?php echo _QXZ("Report Name"); ?> -</B><?php echo _QXZ("The report name that the report will be referred to as in the system for purposes of user group access and slave server use in the system settings.  The report name may not be the same as any other custom report, nor may it be the same name as one of the standard system reports."); ?>

<BR>
<A NAME="custom_reports_admin-domain">
<BR>
<B><?php echo _QXZ("Domain"); ?> -</B><?php echo _QXZ("The domain that the report is located at.  This will be combined with the path name to make a complete URL to use as a link from the reports page.  You may leave this blank when the report is located on the same server as the web admin and just use the path name field to make a local URL."); ?>

<BR>
<A NAME="custom_reports_admin-path_name">
<BR>
<B><?php echo _QXZ("Path name"); ?> -</B><?php echo _QXZ("The path on the domain where the report is located.  When the domain is blank it is assumed that the report is housed on the local web server."); ?>

<BR>
<A NAME="custom_reports_admin-constants">
<BR>
<B><?php echo _QXZ("Preset constants"); ?> -</B><?php echo _QXZ("Here is where a user can define a preset value to pass to the report when it is accessed from the custom report link.  This is useful if the user is viewing a report that accepts multiple variables that change the report output and there is a certain set of report parameters the user frequently uses and they wish to save time by coming to the report with those values already set."); ?>
<BR>

<B><?php echo _QXZ("Variable name"); ?> -</B><?php echo _QXZ("The enters the variable name here.  Names allow alphanumeric characters, underscores, and to a limited extent brackets.  Brackets are used to pass an array to the report and should be placed at the end of the variable name, such as -var_name[]-.  The interface will condense brackets by removing any characters within a pair of them, and will also remove unmatched brackets."); ?>
<BR>

<B><?php echo _QXZ("Value"); ?> -</B><?php echo _QXZ("Then, the user enters a variable value in the -Value- field, either by selecting a pre-defined variable value or a custom value.  With the exception of -datetime- and -filedatetime-, all pre-defined variables are dates in yyyy-mm-dd format.  The predefined values are: -today- for today-s date, -yesterday- for yesterday-s date, -datetime- which is a timestamp in the format of -yyyy-mm-dd hh:ii:ss-, -filedatetime- which is an all-numeric timestamp in the format -yyyymmddhhiiss-, and 6/7/8/13/14/15/30days, all of which are the date 6/7/etc days ago.  If the user would like to define their own value for the variable, they can set the value to -Custom Value-, then fill out the custom value in the field to the right.  Click -ADD- to add the variable name/value pair to the presets to include in the report.  Values will be URL-encoded prior to being entered in the database."); ?>

<BR><BR>
<A NAME="custom_reports_admin-current_constants">
<BR>
<B><?php echo _QXZ("Current constants"); ?> -</B><?php echo _QXZ("Any report constant the user has entered to add to the report is displayed here.  They are not editable, but they can be removed by clicking -REMOVE- and then re-entered."); ?>

<BR><BR>
<A NAME="custom_reports_admin-preset_constants">
<BR>
<B><?php echo _QXZ("Preset constants"); ?> -</B><?php echo _QXZ("This textarea field is where presets for the current custom reports are displayed and can be edited.  The name/value pairs are displayed per-line and are fully editable.  Users can also remove variables simply by deleting the line they are displayed on, or can enter new name/value pairs per line.  Variables can also be separated by an ampersand as in the typical URL GET display - the dialer interface will make the necessary adjustments to display them properly in this textarea field."); ?>


<BR><BR>
<A NAME="custom_reports_admin-custom_reports_user_groups">
<BR>
<B><?php echo _QXZ("User groups"); ?> -</B><?php echo _QXZ("The user groups that will be allowed to view this report.  When a user goes to the reports page, if they belong to a user group that has access to any of the custom reports, they can see a Custom Reports section on this page with links to every custom report they can view."); ?>



<BR><BR><BR><BR><BR><BR><BR><BR><BR>
</TD></TR></TABLE></BODY></HTML>
<?php
exit;

#### END HELP SCREENS
?>
