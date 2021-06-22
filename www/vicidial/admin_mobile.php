<?php
# admin_mobile.php - VICIDIAL administration page, reduced functions for mobile app
#
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGES
# 190205-1702 - First Build
# 200210-1618 - Added links to more mobile-compatible reports
# 200309-1819 - Modifications for display formatting
#

$admin_version = '2.14-3a';
$build = '200309-1819';

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

######################################################################################################
######################################################################################################
#######   static variable settings for display options
######################################################################################################
######################################################################################################

$page_width='770';
$section_width='750';
$header_font_size='12';
$subheader_font_size='11';
$subcamp_font_size='11';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$users_color =		'#FFFF99';
$campaigns_color =	'#FFCC99';
$lists_color =		'#FFCCCC';
$ingroups_color =	'#CC99FF';
$remoteagent_color ='#CCFFCC';
$usergroups_color =	'#CCFFFF';
$scripts_color =	'#99FFCC';
$filters_color =	'#CCCCCC';
$admin_color =		'#FF99FF';
$reports_color =	'#99FF33';
	$times_color =		'#FF33FF';
	$shifts_color =		'#FF33FF';
	$phones_color =		'#FF33FF';
	$conference_color =	'#FF33FF';
	$server_color =		'#FF33FF';
	$templates_color =	'#FF33FF';
	$carriers_color =	'#FF33FF';
	$settings_color = 	'#FF33FF';
	$label_color =		'#FF33FF';
	$status_color = 	'#FF33FF';
	$moh_color = 		'#FF33FF';
	$vm_color = 		'#FF33FF';
	$tts_color = 		'#FF33FF';
	$cc_color = 		'#FF33FF';
	$cts_color = 		'#FF33FF';
$subcamp_color =	'#FF9933';
$users_font =		'BLACK';
$campaigns_font =	'BLACK';
$lists_font =		'BLACK';
$ingroups_font =	'BLACK';
$remoteagent_font =	'BLACK';
$usergroups_font =	'BLACK';
$scripts_font =		'BLACK';
$filters_font =		'BLACK';
$admin_font =		'BLACK';
$qc_font =			'BLACK';
$reports_font =		'BLACK';
	$times_font =		'BLACK';
	$phones_font =		'BLACK';
	$conference_font =	'BLACK';
	$server_font =		'BLACK';
	$settings_font = 	'BLACK';
	$label_font = 	'BLACK';
	$status_font = 	'BLACK';
	$moh_font = 	'BLACK';
	$vm_font = 		'BLACK';
	$tts_font = 	'BLACK';
	$cc_font =		'BLACK';
	$cts_font = 	'BLACK';
$subcamp_font =		'BLACK';

### comment this section out for colorful section headings
$users_color =		'#E6E6E6';
$campaigns_color =	'#E6E6E6';
$lists_color =		'#E6E6E6';
$ingroups_color =	'#E6E6E6';
$remoteagent_color ='#E6E6E6';
$usergroups_color =	'#E6E6E6';
$scripts_color =	'#E6E6E6';
$filters_color =	'#E6E6E6';
$admin_color =		'#E6E6E6';
$qc_color =			'#E6E6E6';
$reports_color =	'#E6E6E6';
	$times_color =		'#C6C6C6';
	$shifts_color =		'#C6C6C6';
	$phones_color =		'#C6C6C6';
	$conference_color =	'#C6C6C6';
	$server_color =		'#C6C6C6';
	$templates_color =	'#C6C6C6';
	$carriers_color =	'#C6C6C6';
	$settings_color = 	'#C6C6C6';
	$label_color =		'#C6C6C6';
	$colors_color =		'#C6C6C6';
	$status_color = 	'#C6C6C6';
	$moh_color = 		'#C6C6C6';
	$vm_color = 		'#C6C6C6';
	$tts_color = 		'#C6C6C6';
	$cc_color = 		'#C6C6C6';
	$cts_color = 		'#C6C6C6';
	$sc_color = 		'#C6C6C6';
	$sg_color = 		'#C6C6C6';
	$ar_color = 		'#C6C6C6';
	$il_color = 		'#C6C6C6';
$subcamp_color =	'#C6C6C6';

$Msubhead_color =	'#E6E6E6';
$Mselected_color =	'#C6C6C6';
$Mhead_color =		'#A3C3D6';
$Mmain_bgcolor =	'#015B91';

###


$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$QUERY_STRING = getenv("QUERY_STRING");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

$Vreports = 'NONE, Real-Time Main Report, Real-Time Campaign Summary, Real-Time Whiteboard Report, Inbound Report, Inbound Report by DID, Inbound Service Level Report, Inbound Summary Hourly Report, Inbound Daily Report, Inbound DID Report, Inbound DID Summary Report, Agent DID Report, Inbound IVR Report, Inbound Forecasting Report, Advanced Forecasting Report, Outbound Calling Report, Outbound Summary Interval Report, Outbound IVR Report, Callmenu Survey Report, Outbound Lead Source Report, Fronter - Closer Report, Fronter - Closer Detail Report, Lists Campaign Statuses Report, Lists Statuses Report, Campaign Status List Report, Export Calls Report, Export Leads Report, Agent Time Detail, Agent Status Detail, Agent Inbound Status Summary, Agent Performance Detail, Team Performance Detail, Performance Comparison Report, Single Agent Daily, Single Agent Daily Time, User Group Login Report, User Group Hourly Report, User Group Detail Hourly Report, User Timeclock Report, User Group Timeclock Status Report, User Timeclock Detail Report, Server Performance Report, Administration Change Log, List Update Stats, User Stats, User Time Sheet, Download List, Dialer Inventory Report, Maximum System Stats, Maximum Stats Detail, Search Leads Logs, Email Log Report, Carrier Log Report, Campaign Debug, Asterisk Debug, Hangup Cause Report, Lists Pass Report, Called Counts List IDs Report, Agent Debug Log Report, Agent Parked Call Report, Agent-Manager Chat Log, Recording Access Log Report, API Log Report, Real-Time Monitoring Log Report';

$UGreports = 'ALL REPORTS, NONE, Real-Time Main Report, Real-Time Campaign Summary, Real-Time Whiteboard Report, Inbound Report, Inbound Report by DID, Inbound Service Level Report, Inbound Summary Hourly Report, Inbound Daily Report, Inbound DID Report, Inbound DID Summary Report, Agent DID Report, Inbound Email Report, Inbound Chat Report, Inbound IVR Report, Inbound Forecasting Report, Advanced Forecasting Report, Outbound Calling Report, Outbound Summary Interval Report, Outbound IVR Report, Callmenu Survey Report, Outbound Lead Source Report, Fronter - Closer Report, Fronter - Closer Detail Report, Lists Campaign Statuses Report, Lists Statuses Report, Campaign Status List Report, Export Calls Report, Export Leads Report, Agent Time Detail, Agent Status Detail, Agent Inbound Status Summary, Agent Performance Detail, Team Performance Detail, Performance Comparison Report, Single Agent Daily, Single Agent Daily Time, User Group Login Report, User Group Hourly Report, User Group Detail Hourly Report, User Timeclock Report, User Group Timeclock Status Report, User Timeclock Detail Report, Server Performance Report, Administration Change Log, List Update Stats, User Stats, User Time Sheet, Download List, Dialer Inventory Report, Custom Reports Links, CallCard Search, Maximum System Stats, Maximum Stats Detail, Search Leads Logs, Email Log Report, Lists Pass Report, Called Counts List IDs Report, Front Page System Summary, Report Page Servers Summary, Admin Utilities Page, Agent Debug Log Report, Agent Parked Call Report, Agent-Manager Chat Log, Recording Access Log Report, API Log Report, Real-Time Monitoring Log Report';

$Vtables = 'NONE,log_noanswer,did_agent_log,contact_information';

$APIfunctions = 'ALL_FUNCTIONS add_group_alias add_lead add_list add_phone add_phone_alias add_user agent_ingroup_info agent_stats_export agent_status audio_playback blind_monitor call_agent callid_info change_ingroups check_phone_number did_log_export external_add_lead external_dial external_hangup external_pause external_status in_group_status logout moh_list park_call pause_code preview_dial_action ra_call_control recording recording_lookup send_dtmf server_refresh set_timer_action sounds_list st_get_agent_active_lead st_login_log transfer_conference update_fields update_lead update_list update_log_entry update_phone update_phone_alias update_user user_group_status vm_list webphone_url webserver logged_in_agents update_campaign update_did lead_field_info phone_number_log switch_lead ccc_lead_info lead_status_search call_status_stats calls_in_queue_count force_fronter_leave_3way';


### BEGIN housecleaning of old static report files, if not done before ###
if (!file_exists('old_clear'))
	{
	array_map('unlink', glob("./*.csv"));
	array_map('unlink', glob("./*.xls"));
	array_map('unlink', glob("./ploticus/*"));
	unlink('project_auth_entries.txt');

	$clear_file=fopen('old_clear', "w");
	fwrite($clear_file, '1');
	fclose($clear_file);
	}
### END housecleaning of old static report files ###

if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_queuemetrics_logging,enable_vtiger_integration,qc_features_active,outbound_autodial_active,sounds_central_control_active,enable_second_webform,user_territories_active,custom_fields_enabled,admin_web_directory,webphone_url,first_login_trigger,hosted_settings,default_phone_registration_password,default_phone_login_password,default_server_password,test_campaign_calls,active_voicemail_server,voicemail_timezones,default_voicemail_timezone,default_local_gmt,campaign_cid_areacodes_enabled,pllb_grouping_limit,did_ra_extensions_enabled,expanded_list_stats,contacts_enabled,alt_log_server_ip,alt_log_dbname,alt_log_login,alt_log_pass,tables_use_alt_log_db,call_menu_qualify_enabled,admin_list_counts,allow_voicemail_greeting,svn_revision,allow_emails,level_8_disable_add,pass_key,pass_hash_enabled,disable_auto_dial,country_code_list_stats,frozen_server_call_clear,active_modules,allow_chats,enable_languages,language_method,meetme_enter_login_filename,meetme_enter_leave3way_filename,enable_did_entry_list_id,enable_third_webform,default_language,user_hide_realtime_enabled,log_recording_access,alt_ivr_logging,admin_row_click,admin_screen_colors,ofcom_uk_drop_calc,agent_screen_colors,script_remove_js,manual_auto_next,user_new_lead_limit,agent_xfer_park_3way,agent_soundboards,web_loader_phone_length,agent_script,enable_auto_reports,enable_pause_code_limits,enable_drop_lists,allow_ip_lists,system_ip_blacklist,hide_inactive_lists,allow_manage_active_lists,expired_lists_inactive,did_system_filter,enable_gdpr_download_deletion FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
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
	$SSfrozen_server_call_clear =			$row[41];
	$SSactive_modules =						$row[42];
	$SSallow_chats =						$row[43];
	$SSenable_languages =					$row[44];
	$SSlanguage_method =					$row[45];
	$SSmeetme_enter_login_filename =		$row[46];
	$SSmeetme_enter_leave3way_filename =	$row[47];
	$SSenable_did_entry_list_id =			$row[48];
	$SSenable_third_webform =				$row[49];
	$SSdefault_language =					$row[50];
	$SSuser_hide_realtime_enabled =			$row[51];
	$SSlog_recording_access =				$row[52];
	$SSalt_ivr_logging =					$row[53];
	$SSadmin_row_click =					$row[54];
	$SSadmin_screen_colors =				$row[55];
	$SSofcom_uk_drop_calc =					$row[56];
	$SSagent_screen_colors =				$row[57];
	$SSscript_remove_js =					$row[58];
	$SSmanual_auto_next =					$row[59];
	$SSuser_new_lead_limit =				$row[60];
	$SSagent_xfer_park_3way =				$row[61];
	$SSagent_soundboards =					$row[62];
	$SSweb_loader_phone_length =			$row[63];
	$SSagent_script =						$row[64];
	$SSenable_auto_reports =				$row[65];
	$SSenable_pause_code_limits =			$row[66];
	$SSenable_drop_lists =					$row[67];
	$SSallow_ip_lists =						$row[68];
	$SSsystem_ip_blacklist =				$row[69];
	$SShide_inactive_lists =				$row[70];
	$SSallow_manage_active_lists =			$row[71];
	$SSexpired_lists_inactive =				$row[72];
	$SSdid_system_filter =					$row[73];
	$SSenable_gdpr_download_deletion =		$row[74];
	}
##### END SETTINGS LOOKUP #####
###########################################

### populate pass_key if not set
if ( ($qm_conf_ct > 0) and (strlen($SSpass_key)<16) )
	{
	$SSpass_key = '';
	$possible = "0123456789abcdefghijklmnpqrstvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ";  
	$i = 0; 
	$length = 16;
	while ($i < $length) 
		{ 
		$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
		$SSpass_key .= $char;
		$i++;
		}
	$stmt="UPDATE system_settings set pass_key='$SSpass_key' where ( (pass_key is NULL) or (pass_key='') );";
	$rslt=mysql_to_mysqli($stmt, $link);
	}

# make sure you have added a user to the vicidial_users MySQL table with at least user_level 9 to access this page the first time

$STARTtime = date("U");
$SQLdate = date("Y-m-d H:i:s");
$REPORTdate = date("Y-m-d");
$EXPtestdate = date("Ymd");
$CIDdate = date("mdHis");
while (strlen($CIDdate) > 9) {$CIDdate = substr("$CIDdate", 1);}

$MT[0]='';
$US='_';
$active_lists=0;
$inactive_lists=0;
$modify_refresh_set=0;
$modify_footer_refresh=0;
$check_time = ($STARTtime - 86400);
$SSanswer_transfer_agent =	'8368';
$add_copy_disabled=0;

$month_old = mktime(0, 0, 0, date("m")-1, date("d"),  date("Y"));
$past_month_date = date("Y-m-d H:i:s",$month_old);
$week_old = mktime(0, 0, 0, date("m"), date("d")-7,  date("Y"));
$past_week_date = date("Y-m-d H:i:s",$week_old);

$dtmf[0]='0';				$dtmf_key[0]='0';
$dtmf[1]='1';				$dtmf_key[1]='1';
$dtmf[2]='2';				$dtmf_key[2]='2';
$dtmf[3]='3';				$dtmf_key[3]='3';
$dtmf[4]='4';				$dtmf_key[4]='4';
$dtmf[5]='5';				$dtmf_key[5]='5';
$dtmf[6]='6';				$dtmf_key[6]='6';
$dtmf[7]='7';				$dtmf_key[7]='7';
$dtmf[8]='8';				$dtmf_key[8]='8';
$dtmf[9]='9';				$dtmf_key[9]='9';
$dtmf[10]='HASH';			$dtmf_key[10]='#';
$dtmf[11]='STAR';			$dtmf_key[11]='*';
$dtmf[12]='A';				$dtmf_key[12]='A';
$dtmf[13]='B';				$dtmf_key[13]='B';
$dtmf[14]='C';				$dtmf_key[14]='C';
$dtmf[15]='D';				$dtmf_key[15]='D';
$dtmf[16]='TIMECHECK';		$dtmf_key[16]='TIMECHECK';
$dtmf[17]='TIMEOUT';		$dtmf_key[17]='TIMEOUT';
$dtmf[18]='INVALID';		$dtmf_key[18]='INVALID';
$dtmf[19]='INVALID_2ND';	$dtmf_key[19]='INVALID_2ND';
$dtmf[20]='INVALID_3RD';	$dtmf_key[20]='INVALID_3RD';

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

if ($force_logout)
	{
	if( (strlen($PHP_AUTH_USER)>0) or (strlen($PHP_AUTH_PW)>0) )
		{
		Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
		Header("HTTP/1.0 401 Unauthorized");
		}
	echo _QXZ("You have now logged out. Thank you")."\n<BR>"._QXZ("To log back in").", <a href=\"$PHP_SELF\">"._QXZ("click here")."</a>";
	exit;
	}
#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,auto_dial_limit,user_territories_active,allow_custom_dialplan,callcard_enabled,admin_modify_refresh,nocache_admin,webroot_writable,allow_emails,manual_dial_validation FROM system_settings;";
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
	$SSemail_enabled =				$row[8];
	$SSmanual_dial_validation =		$row[9];

	# slightly increase limit value, because PHP somehow thinks 2.8 > 2.8
	$SSauto_dial_limit = ($SSauto_dial_limit + 0.001);
	}
##### END SETTINGS LOOKUP #####
###########################################

$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

$user_auth=0;
$auth=0;
$reports_auth=0;
$qc_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$user_auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

if ($user_auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and api_only_user != '1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1' and api_only_user != '1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 1 and qc_enabled='1' and api_only_user != '1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$qc_auth=$row[0];

	$reports_only_user=0;
	$qc_only_user=0;
	if ( ($reports_auth > 0) and ($auth < 1) )
		{
		$ADD=999990;
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
		$VDdisplayMESSAGE = _QXZ("You do not have permission to be here");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
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

$stmt="SELECT user_id,user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,shift_override_flag,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,callcard_admin,force_change_password,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,modify_contacts,modify_same_user_level,alter_admin_interface_options,modify_custom_dialplans,modify_languages,selected_language,user_choose_language,modify_colors,api_only_user,modify_auto_reports,modify_ip_lists,export_gdpr_leads from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
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
$LOGmodify_leads			=$row[19];
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
$LOGalter_admin_interface	=$row[83];
$LOGmodify_custom_dialplans =$row[84];
$LOGmodify_languages		=$row[85];
$LOGselected_language		=$row[86];
$LOGuser_choose_language	=$row[87];
$LOGmodify_colors			=$row[88];
$LOGapi_only_user			=$row[89];
$LOGmodify_auto_reports		=$row[90];
$LOGmodify_ip_lists			=$row[91];
$LOGexport_gdpr_leads		=$row[92];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times,qc_allowed_campaigns,qc_allowed_inbound_groups from vicidial_user_groups where user_group='$LOGuser_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];
$LOGqc_allowed_campaigns =		$row[4];
$LOGqc_allowed_inbound_groups =	$row[5];

$LOGallowed_campaignsSQL='';
$campLOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$campLOGallowed_campaignsSQL = "and camp.campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

if (preg_match("/DRA/",$SShosted_settings))
	{$LOGmodify_remoteagents=0;}

$admin_viewable_groupsALL=0;
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}
$regexLOGadmin_viewable_groups = " $LOGadmin_viewable_groups ";

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}
$regexLOGadmin_viewable_call_times = " $LOGadmin_viewable_call_times ";

$UUgroups_list='';
if ($admin_viewable_groupsALL > 0)
	{$UUgroups_list .= "<option value=\"---ALL---\">"._QXZ("All Admin User Groups")."</option>\n";}
$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
$rslt=mysql_to_mysqli($stmt, $link);
$UUgroups_to_print = mysqli_num_rows($rslt);
$o=0;
while ($UUgroups_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$UUgroups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$o++;
	}

$LOGqc_allowed_campaignsSQL='';
$whereLOGqc_allowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGqc_allowed_campaigns)) )
	{
	$rawLOGqc_allowed_campaignsSQL = preg_replace("/ -/",'',$LOGqc_allowed_campaigns);
	$rawLOGqc_allowed_campaignsSQL = preg_replace("/ /","','",$rawLOGqc_allowed_campaignsSQL);
	$LOGqc_allowed_campaignsSQL = "and campaign_id IN('$rawLOGqc_allowed_campaignsSQL')";
	$whereLOGqc_allowed_campaignsSQL = "where campaign_id IN('$rawLOGqc_allowed_campaignsSQL')";
	}

$LOGqc_allowed_inbound_groupsSQL='';
$whereLOGqc_allowed_inbound_groupsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGqc_allowed_inbound_groups)) )
	{
	$rawLOGqc_allowed_inbound_groupsSQL = preg_replace("/ -/",'',$LOGqc_allowed_inbound_groups);
	$rawLOGqc_allowed_inbound_groupsSQL = preg_replace("/ /","','",$rawLOGqc_allowed_inbound_groupsSQL);
	$LOGqc_allowed_inbound_groupsSQL = "and group_id IN('$rawLOGqc_allowed_inbound_groupsSQL')";
	$whereLOGqc_allowed_inbound_groupsSQL = "where group_id IN('$rawLOGqc_allowed_inbound_groupsSQL')";
	}

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
$SSweb_logo='default_old';

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

if ($download_max_system_stats_metric_name) 
	{
	if (!$query_date) {$query_date=date("Y-m-d", time()-(29*86400));}
	if (!$end_date) 
		{
		$end_date=date("Y-m-d", time());
		}
	else if (strtotime($end_date)>strtotime(date("Y-m-d"))) 
		{
		$end_date=date("Y-m-d");
		}
	if ($query_date>$end_date) {$query_date=$end_date;}

	$num_graph_days = ceil(abs(strtotime($end_date) - strtotime($query_date)) / 86400)+1;
	$CSV_text="";

	if ($download_max_system_stats_metric_name=="ALL" || $download_max_system_stats_metric_name=="total call count in and out") 
		{
		download_max_system_stats($campaign_id,$num_graph_days,'system','total_calls','total call count in and out',$end_date);
		}
	if ($download_max_system_stats_metric_name=="ALL" || $download_max_system_stats_metric_name=="total inbound call count") 
		{
		download_max_system_stats($campaign_id,$num_graph_days,'system','total_calls_inbound_all','total inbound call count',$end_date);
		}
	if ($download_max_system_stats_metric_name=="ALL" || $download_max_system_stats_metric_name=="total outbound call count") 
		{
		download_max_system_stats($campaign_id,$num_graph_days,'system','total_calls_outbound_all','total outbound call count',$end_date);
		}
	if ($download_max_system_stats_metric_name=="ALL" || $download_max_system_stats_metric_name=="most concurrent calls in and out") 
		{
		download_max_system_stats($campaign_id,$num_graph_days,'system','(max_inbound + max_outbound)','most concurrent calls in and out',$end_date);
		}
	if ($download_max_system_stats_metric_name=="ALL" || $download_max_system_stats_metric_name=="most concurrent calls inbound total") 
		{
		download_max_system_stats($campaign_id,$num_graph_days,'system','max_inbound','most concurrent calls inbound total',$end_date);
		}
	if ($download_max_system_stats_metric_name=="ALL" || $download_max_system_stats_metric_name=="most concurrent calls outbound total") 
		{
		download_max_system_stats($campaign_id,$num_graph_days,'system','max_outbound','most concurrent calls outbound total',$end_date);
		}
	if ($download_max_system_stats_metric_name=="ALL" || $download_max_system_stats_metric_name=="most concurrent agents") 
		{
		download_max_system_stats($campaign_id,$num_graph_days,'system','max_agents','most concurrent agents',$end_date);
		}

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "MAX_SYSTEM_STATS_$US$FILE_TIME.csv";
	$CSV_text=preg_replace('/ +\"/', '"', $CSV_text);
	$CSV_text=preg_replace('/\" +/', '"', $CSV_text);
	header('Content-type: application/octet-stream');

	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$CSV_text";

	exit;
	}


######################################################################################################
######################################################################################################
#######   Header settings
######################################################################################################
######################################################################################################


header ("Content-type: text/html; charset=utf-8");
if ($SSnocache_admin=='1')
	{
	header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
	header ("Pragma: no-cache");                          // HTTP/1.0
	}
echo "<html>\n";
echo "<head>\n";
echo "<!-- VERSION: $admin_version   BUILD: $build   ADD: $ADD   PHP_SELF: $PHP_SELF-->\n";
echo "<META NAME=\"ROBOTS\" CONTENT=\"NONE\">\n";
echo "<META NAME=\"COPYRIGHT\" CONTENT=\"&copy; 2019 ViciDial Group\">\n";
echo "<META NAME=\"AUTHOR\" CONTENT=\"ViciDial Group\">\n";
echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";


echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<STYLE type=\"text/css\">\n";
echo "<!--\n";
echo "	.admin_stats_table {width: 95vw; max-width: 950px; }\n";
echo "-->\n";
echo "</STYLE>\n";


if ($SSnocache_admin=='1')
	{
	echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
	echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">\n";
	echo "<META HTTP-EQUIV=\"CACHE-CONTROL\" CONTENT=\"NO-CACHE\">\n";
	}
if ( ($SSadmin_modify_refresh > 1) and (preg_match("/^3/",$ADD)) )
	{
	$modify_refresh_set=1;
	if (preg_match("/^3/",$ADD)) {$modify_url = "$PHP_SELF?$QUERY_STRING";}
	echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$SSadmin_modify_refresh;URL=$modify_url\">\n";
	}
echo "<title>"._QXZ("ADMINISTRATION").": ";

echo "</title>\n";

echo "<BODY BGCOLOR='#$SSframe_background' marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onLoad='LoadHourlyCharts()'>\n";


### set the default screen to the user list
if ( (!isset($ADD)) or (strlen($ADD)<1) )   {$ADD="999990";}
if ($ADD=='0') {$ADD="999990";}

$no_title=1;
$ADMIN=$PHP_SELF;
$android_header=1;
require("admin_header.php");


echo "<TABLE CELLPADDING=0 CELLSPACING=0 BGCOLOR='#$SSframe_background' width='100%' style='height:100vh' valign='top'><TR><TD>";

######################
# ADD=999990 - new main landing page with system stats
######################
if ($ADD==999990)
	{
	if ( (preg_match("/Front Page System Summary/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
		{
		$stmt="SELECT closer_campaigns from vicidial_campaigns $whereLOGallowed_campaignsSQL;";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$closer_campaigns = preg_replace("/^ | -$/","",$row[0]);
		$closer_campaigns = preg_replace("/ /","','",$closer_campaigns);
		$closer_campaigns = "'$closer_campaigns'";

		$stmt="SELECT status from vicidial_auto_calls where status NOT IN('XFER') and ( (call_type='IN' and campaign_id IN($closer_campaigns)) or (call_type='OUT' $LOGallowed_campaignsSQL) );";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$active_calls=mysqli_num_rows($rslt);
		$ringing_calls=0;
		if ($active_calls>0) 
			{
			while ($row=mysqli_fetch_row($rslt)) 
				{
				if (!preg_match("/LIVE|CLOSER/i",$row[0])) 
					{$ringing_calls++;}
				}
			}

		$active_stmt="SELECT active from vicidial_users $whereLOGadmin_viewable_groupsSQL";
		if ($DB) {echo "|$active_stmt|\n";}
		$active_rslt=mysql_to_mysqli($active_stmt, $link);
		while ($active_row=mysqli_fetch_array($active_rslt)) 
			{
			$users[$active_row["active"]]++;
			}

		$active_stmt="SELECT active from vicidial_campaigns $whereLOGallowed_campaignsSQL";
		if ($DB) {echo "|$active_stmt|\n";}
		$active_rslt=mysql_to_mysqli($active_stmt, $link);
		while ($active_row=mysqli_fetch_array($active_rslt)) 
			{
			$campaigns[$active_row["active"]]++;
			}

		$active_stmt="SELECT active from vicidial_lists $whereLOGallowed_campaignsSQL";
		if ($DB) {echo "|$active_stmt|\n";}
		$active_rslt=mysql_to_mysqli($active_stmt, $link);
		while ($active_row=mysqli_fetch_array($active_rslt)) 
			{
			$lists[$active_row["active"]]++;
			}

		$active_stmt="SELECT did_active from vicidial_inbound_dids $whereLOGadmin_viewable_groupsSQL";
		if ($DB) {echo "|$active_stmt|\n";}
		$active_rslt=mysql_to_mysqli($active_stmt, $link);
		while ($active_row=mysqli_fetch_array($active_rslt)) 
			{
			$dids[$active_row["did_active"]]++;
			}

		$active_stmt="SELECT active from vicidial_inbound_groups $whereLOGadmin_viewable_groupsSQL";
		if ($DB) {echo "|$active_stmt|\n";}
		$active_rslt=mysql_to_mysqli($active_stmt, $link);
		while ($active_row=mysqli_fetch_array($active_rslt)) 
			{
			$ingroups[$active_row["active"]]++;
			}

		$stmt="SELECT extension,user,conf_exten,status,server_ip,UNIX_TIMESTAMP(last_call_time),UNIX_TIMESTAMP(last_call_finish),call_server_ip,campaign_id from vicidial_live_agents $whereLOGallowed_campaignsSQL";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$agent_incall=0; $agent_total=0; $agent_paused=0; $agent_waiting=0;
		while($row=mysqli_fetch_array($rslt)) 
			{
			$status=$row[3];
			$agent_total++;
			if ( (preg_match("/INCALL/i",$status)) or (preg_match("/QUEUE/i",$status)) ) {$agent_incall++; }
			if ( (preg_match("/PAUSED/i",$status))) {$agent_paused++; }
			if ( (preg_match("/READY/i",$status)) or (preg_match("/CLOSER/i",$status)) ) {$agent_waiting++; }
			}
		if (preg_match("/MXAG/",$SShosted_settings))
			{
			$vla_set = $SShosted_settings;
			$vla_set = preg_replace("/.*MXAG|_BUILD_|DRA|_MXCS\d+|_MXTR\d+| /",'',$vla_set);
			$vla_set = preg_replace('/[^0-9]/','',$vla_set);
			if (strlen($vla_set)>0)
				{
				$AAf=''; $AAb='';
				if ($agent_total >= $vla_set)
					{$AAf='<font color=red>'; $AAb='<font>';}
				$agent_total = "$AAf$agent_total / $vla_set$AAb";
				}
			}

		echo "<BR><FONT CLASS=\"android_standard\">";

		echo "<center>";

	if ($LOGview_reports==1)
		{
		echo "<TABLE class='admin_stats_table' valign='top'><TR><TD>\n";
		echo "<FONT CLASS=\"android_standard bold\">";

		### Report link ###
		echo "<B><a href='#' id='ReportLink' onClick='ToggleReports()'>SHOW REPORTS</a></B>";

		### Reports to show ###
		echo "<div id='ReportStorageDiv' style='display:none'>";
		echo "<BR><B>"._QXZ("Real-Time Reports")."</B><BR>\n";
		echo "<UL>\n";
		echo "<LI><a href=\"#HourlyCampaignCounts\"><FONT CLASS=\"android_standard\">"._QXZ("Hourly Campaign Counts")."</a></FONT></LI>\n";
		echo "<LI><a href=\"#SystemSummary\"><FONT CLASS=\"android_standard\">"._QXZ("System Summary")."</a></FONT></LI>\n";
		if ( (preg_match("/Agent Sales Report/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
			{echo "<LI><a href=\"agent_sales_report_mobile.php\"><FONT CLASS=\"android_standard\">"._QXZ("Agent Sales Report")."</a></FONT></LI>\n";}
		if ( (preg_match("/Real-Time Main Report/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
			{echo "<LI><a href=\"realtime_report_mobile.php\"><FONT CLASS=\"android_standard\">"._QXZ("Real-Time Main Report")."</a></FONT></LI>\n";}
				#	echo "<BR> &nbsp; Real-Time SIP: <a href=\"AST_timeonVDADall.php?SIPmonitorLINK=1\"><FONT CLASS=\"android_standard\">"._QXZ("Listen")."</a></FONT> - <a href=\"AST_timeonVDADall.php?SIPmonitorLINK=2\"><FONT CLASS=\"android_standard\">"._QXZ("Barge")."</a></FONT>\n";
				#	echo "<BR> &nbsp; Real-Time IAX: <a href=\"AST_timeonVDADall.php?IAXmonitorLINK=1\"><FONT CLASS=\"android_standard\">"._QXZ("Listen")."</a></FONT> - <a href=\"AST_timeonVDADall.php?IAXmonitorLINK=2\"><FONT CLASS=\"android_standard\">"._QXZ("Barge")."</a></FONT><BR><BR>\n";
		if ( (preg_match("/Real-Time Campaign Summary/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
			{echo "<LI><a href=\"AST_timeonVDADallSUMMARY_mobile.php\"><FONT CLASS=\"android_standard\">"._QXZ("Real-Time Campaign Summary")."</a></FONT></LI>\n";}
		if ( (preg_match("/Real-Time Whiteboard Report/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
			{echo "<LI><a href=\"AST_rt_whiteboard_rpt_mobile.php\"><FONT CLASS=\"android_standard\">"._QXZ("Real-Time Whiteboard Report")."</a></FONT></LI>\n";}
		echo "</UL><BR>\n";


		echo "<B>"._QXZ("Agent Reports")."</B><BR>\n";
		echo "<UL>\n";
#		if ( (preg_match("/Agent Time Detail/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo "<LI><a href=\"AST_agent_time_detail.php\"><FONT CLASS=\"android_standard\">"._QXZ("Agent Time Detail")."</a></FONT>\n";}
#		if ( (preg_match("/Agent Status Detail/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo "<LI><a href=\"AST_agent_status_detail.php\"><FONT CLASS=\"android_standard\">"._QXZ("Agent Status Detail")."</a></FONT>\n";}
#		if ( (preg_match("/Agent Inbound Status Summary/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo " - <a href=\"AST_agent_inbound_status.php\"><FONT CLASS=\"android_standard\">"._QXZ("Inbound Summary")."</a></FONT>\n";}
		if ( (preg_match("/Agent Performance Detail/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
			{echo "<LI><a href=\"AST_agent_performance_detail_mobile.php\"><FONT CLASS=\"android_standard\">"._QXZ("Agent Performance Detail")."</a></FONT></LI>\n";}
		if ( (preg_match("/Team Performance Detail/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
			{echo "<LI><a href=\"AST_team_performance_detail_mobile.php\"><FONT CLASS=\"android_standard\">"._QXZ("Team Performance Detail")."</a></FONT></LI>\n";}
#		if ( (preg_match("/Performance Comparison Report/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo "<LI><a href=\"AST_performance_comparison_report.php\"><FONT CLASS=\"android_standard\">"._QXZ("Performance Comparison Report")."</a></FONT>\n";}
#		if ( (preg_match("/Single Agent Daily$/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo "<LI><a href=\"AST_agent_days_detail.php\"><FONT CLASS=\"android_standard\">"._QXZ("Single Agent Daily")."</a></FONT>\n";}
#		if ( (preg_match("/Single Agent Daily Time/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo " - <a href=\"AST_agent_days_time.php\"><FONT CLASS=\"android_standard\">"._QXZ("Time")."</a></FONT>\n";}
#		if ( (preg_match("/User Group Login Report/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo "<LI><a href=\"AST_usergroup_login_report.php\"><FONT CLASS=\"android_standard\">"._QXZ("User Group Login Report")."</a></FONT>\n";}
#		if ( (preg_match("/User Group Hourly Report/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo "<LI><a href=\"AST_user_group_hourly_detail.php\"><FONT CLASS=\"android_standard\">"._QXZ("User Group Hourly Report")."</a></FONT> - <a href=\"AST_user_group_hourly_detail_v2.php\"><FONT CLASS=\"android_standard\">"._QXZ("v2")."</a></FONT>\n";}
#		if ( (preg_match("/User Stats/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo "<LI><a href=\"user_stats.php\"><FONT CLASS=\"android_standard\">"._QXZ("User Stats")."</a></FONT>\n";}
#		if ( (preg_match("/User Time Sheet/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
#			{echo "<LI><a href=\"AST_agent_time_sheet.php\"><FONT CLASS=\"android_standard\">"._QXZ("User Time Sheet")."</a></FONT>\n";}
		echo "</UL>\n";
		echo "</div>";

		echo "<BR></TD></TR></TABLE>\n";		
		echo "<BR>\n";
		}
		
		echo "<TABLE class='admin_stats_table' cellpadding=6 cellspacing=0>\n";
		echo "<tr"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='realtime_report_mobile.php?report_display_type=HTML';\"";} echo ">";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><a href=\"realtime_report_mobile.php?report_display_type=HTML\"><img src=\"images/icon_users.png\" width=42 height=42 border=0></a></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_small bold\" color=\"white\">"._QXZ("Agents Logged In")."</font></td>";
		echo "<td width=3 rowspan=2> &nbsp; </td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><a href=\"realtime_report_mobile.php?report_display_type=HTML\"><img src=\"images/icon_agentsincalls.png\" width=42 height=42 border=0></a></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_small bold\" color=\"white\">"._QXZ("Agents In Calls")."</font></td>";
		echo "</tr>";
		echo "<tr"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='realtime_report_mobile.php?report_display_type=HTML';\"";} echo ">";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_large\" color=\"white\">$agent_total</font></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_large\" color=\"white\">$agent_incall</font></td>";
		echo "</tr>";
		echo "<tr"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='realtime_report_mobile.php?report_display_type=HTML';\"";} echo ">";
		echo "<td height=3 colspan=5> </td>";
		echo "</tr>";

		echo "<tr"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='realtime_report_mobile.php?report_display_type=HTML';\"";} echo ">";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><a href=\"realtime_report_mobile.php?report_display_type=HTML\"><img src=\"images/icon_agentspaused.png\" width=42 height=42 border=0></a></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_small bold\" color=\"white\">"._QXZ("Agents Paused")."</font></td>";
		echo "<td width=3 rowspan=2> &nbsp; </td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><a href=\"realtime_report_mobile.php?report_display_type=HTML\"><img src=\"images/icon_agentswaiting.png\" width=42 height=42 border=0></a></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_small bold\" color=\"white\">"._QXZ("Agents Waiting")."</font></td>";
		echo "</tr>";
		echo "<tr"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='realtime_report_mobile.php?report_display_type=HTML';\"";} echo ">";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_large\" color=\"white\">$agent_paused</font></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_large\" color=\"white\">$agent_waiting</font></td>";
		echo "</tr>";
		echo "<tr"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='realtime_report_mobile.php?report_display_type=HTML';\"";} echo ">";
		echo "<td height=3 colspan=5>  </td>";
		echo "</tr>";

		echo "<tr"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='realtime_report_mobile.php?report_display_type=HTML';\"";} echo ">";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><a href=\"realtime_report_mobile.php?report_display_type=HTML\"><img src=\"images/icon_calls.png\" width=42 height=42 border=0></a></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_small bold\" color=\"white\">"._QXZ("Active Calls")."</font></td>";
		echo "<td width=3 rowspan=2> &nbsp; </td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><a href=\"realtime_report_mobile.php?report_display_type=HTML\"><img src=\"images/icon_ringing.png\" width=42 height=42 border=0></a></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_small bold\" color=\"white\">"._QXZ("Calls Ringing")."</font></td>";
		echo "</tr>";
		echo "<tr"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='realtime_report_mobile.php?report_display_type=HTML';\"";} echo ">";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_large\" color=\"white\">$agent_incall</font></td>";
		echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font CLASS=\"android_large\" color=\"white\">$ringing_calls</font></td>";
		echo "</tr>";
		echo "</TABLE>";
		echo "<br><br>";

		echo "<a name='HourlyCampaignCounts'>";
		echo "<TABLE align='center' class='admin_stats_table' cellspacing=2>\n";
		echo "<tr><td>";
		echo "<canvas id='campaign_hour_chart' style='height: 40vh; max-height: 500px'></canvas>"; #  style='width: 40vw; max-width: 350px;'
		echo "</td></tr>";
		echo "</table>";

		echo "<BR><BR>";

		echo "<a name='SystemSummary'>";
		echo "<TABLE class='admin_stats_table' cellspacing=2>\n";
		echo "<tr>";
		echo "<td align='left' colspan='4'><font CLASS=\"android_standard bold\">"._QXZ("System Summary").":</font></td>";
		echo "</tr>";

		echo "<tr bgcolor=black>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=\"white\">&nbsp; "._QXZ("Records")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=\"white\">&nbsp; "._QXZ("Active")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=\"white\">&nbsp; "._QXZ("Inactive")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=\"white\">&nbsp; "._QXZ("Total")." &nbsp;</font></td>";
		echo "</tr>";
		echo "<tr bgcolor=#$SSstd_row4_background><td align=right><font class=\"android_standard\">"._QXZ("Users").": </font></td><td align=center><font class=\"android_standard bold\">".($users["Y"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($users["N"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($users["Y"]+$users["N"]+0)."</font></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row4_background><td align=right><font class=\"android_standard\">"._QXZ("Campaigns").": </font></td><td align=center><font class=\"android_standard bold\">".($campaigns["Y"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($campaigns["N"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($campaigns["Y"]+$campaigns["N"]+0)."</font></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row4_background><td align=right><font class=\"android_standard\">"._QXZ("Lists").": </font></td><td align=center><font class=\"android_standard bold\">".($lists["Y"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($lists["N"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($lists["Y"]+$lists["N"]+0)."</font></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row4_background><td align=right><font class=\"android_standard\">"._QXZ("In-Groups").": </font></td><td align=center><font class=\"android_standard bold\">".($ingroups["Y"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($ingroups["N"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($ingroups["Y"]+$ingroups["N"]+0)."</font></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row4_background><td align=right><font class=\"android_standard\">"._QXZ("DIDs").": </font></td><td align=center><font class=\"android_standard bold\">".($dids["Y"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($dids["N"]+0)."</font></td><td align=center><font class=\"android_standard bold\">".($dids["Y"]+$dids["N"]+0)."</font></td></tr>\n";
	
		// New voicemailbox code
		$stmt="(SELECT voicemail_id,count(*),messages,old_messages,'vm','vm' from vicidial_voicemail where on_login_report='Y' $LOGadmin_viewable_groupsSQL group by voicemail_id) UNION (SELECT voicemail_id,count(*),messages,old_messages,extension,server_ip from phones where on_login_report='Y' $LOGadmin_viewable_groupsSQL group by voicemail_id) order by voicemail_id;";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$vm_rows=mysqli_num_rows($rslt);
		if ($vm_rows>0) 
			{
			echo "<tr>";
			echo "<td align='left' colspan='4'>&nbsp;</td>";  # Padding
			echo "</tr>";
			echo "<tr bgcolor=black>";
			echo "<td  align='center'><font CLASS=\"android_standard\" color=\"white\">&nbsp; Voicemail Box &nbsp;</font></td>\n";
			echo "<td  align='center'><font CLASS=\"android_standard\" color=\"white\">&nbsp; New &nbsp;</font></td>\n";
			echo "<td  align='center'><font CLASS=\"android_standard\" color=\"white\">&nbsp; Old &nbsp;</font></td>\n";
			echo "<td  align='center'><font CLASS=\"android_standard\" color=\"white\">&nbsp; Total &nbsp;</font></td>\n";
			echo "</tr>\n";
	
			while($row=mysqli_fetch_array($rslt)) 
				{
				echo "<tr bgcolor='#$SSstd_row2_background'>\n";
				if ($row[4] == 'vm')
					{
					echo "<td align='right'><font class=\"android_standard\">$row[0]:</font></font></td>\n";
					}
				else
					{
					echo "<td align='right'><font class=\"android_standard\">$row[0]:</font></td>\n";
					}
				echo "<td align='center'><font class=\"android_standard\">$row[2]</font></td>\n";
				echo "<td align='center'><font class=\"android_standard\">$row[3]</font></td>\n";
				echo "<td align='center'><font class=\"android_standard\">".($row[2]+$row[3])."</font></td>\n";
				echo "</tr>\n";
				}
			}
		// End new voicemail box code
		echo "</TABLE></center>\n";

		echo "<BR>\n";

		$today=date("Y-m-d");
		$yesterday=date("Y-m-d", mktime(0,0,0,date("m"),date("d")-1,date("Y")));
		$thirtydays=date("Y-m-d", mktime(0,0,0,date("m"),date("d")-29,date("Y")));

		$total_calls=0;
		$total_inbound=0;
		$total_outbound=0;
		$stmt="SELECT stats_type,sum(total_calls) from vicidial_daily_max_stats where campaign_id!='' and stats_flag='OPEN' and stats_date='$today' $LOGallowed_campaignsSQL group by stats_type;";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$rows_to_print = mysqli_num_rows($rslt);
		if ($rows_to_print > 0) 
			{
			while ($rowx=mysqli_fetch_row($rslt)) 
				{
				$total_calls += $rowx[1];
				if (preg_match('/INGROUP/', $rowx[0])) {$total_inbound+=$rowx[1];}
				if (preg_match('/CAMPAIGN/', $rowx[0])) {$total_outbound+=$rowx[1];}
				}
			}

		$stmt="SELECT * from vicidial_daily_max_stats where stats_date='$today' and stats_flag='OPEN' and stats_type='TOTAL' $LOGallowed_campaignsSQL order by stats_date, campaign_id asc";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		echo "<center><TABLE class='admin_stats_table' cellspacing=2 cellpadding=1>\n";
		echo "<tr>";
		echo "<td align='left' colspan='4'><font CLASS=\"android_standard bold\">"._QXZ("Total Stats for Today").":</font></td>";
#		echo "<td align='right'><font size=1><a href='$PHP_SELF?query_date=$thirtydays&end_date=$today&max_system_stats_submit=ADJUST+DATE+RANGE&ADD=999992&stage=TOTAL'>["._QXZ("view max stats")."]</a></font></td>";
		echo "</tr>";
		echo "<tr bgcolor=black>";
		# echo "<td><font size=1 color=white align=left><B>CAMPAIGN ID</B></font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=white>"._QXZ("Total Calls")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=white>"._QXZ("Total Inbound Calls")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=white>"._QXZ("Total Outbound Calls")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=white>"._QXZ("Maximum Agents")." &nbsp;</font></td>";

		if (mysqli_num_rows($rslt)>0) 
			{
			while ($row=mysqli_fetch_array($rslt)) 
				{
				echo "<tr bgcolor='#$SSstd_row2_background'>";
			#	echo "<td align='left'><font size=1>".$row["campaign_id"]."</font></td>";
				echo "<td align='center'><font CLASS=\"android_standard bold\">".($total_calls+0)."</font></td>";
				echo "<td align='center'><font CLASS=\"android_standard bold\">".($total_inbound+0)."</font></td>";
				echo "<td align='center'><font CLASS=\"android_standard bold\">".($total_outbound+0)."</font></td>";
				echo "<td align='center'><font CLASS=\"android_standard bold\">".($row["max_agents"]+0)."</font></td>";
				echo "</tr>";
				}
			} 
		else 
			{
			echo "<tr bgcolor='#$SSstd_row2_background'>";
			echo "<td align='center' colspan='4'><font CLASS=\"android_standard bold\">*** "._QXZ("NO ACTIVITY FOR")." $today ***</font></td>";
			echo "</tr>";
			}
		echo "</TABLE></center>\n";

		$total_calls=0;
		$total_inbound=0;
		$total_outbound=0;
		$stmt="SELECT stats_type,sum(total_calls) from vicidial_daily_max_stats where campaign_id!='' and stats_flag='CLOSED' and stats_date='$yesterday' $LOGallowed_campaignsSQL group by stats_type;";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$rows_to_print = mysqli_num_rows($rslt);
		if ($rows_to_print > 0) 
			{
			while ($rowx=mysqli_fetch_row($rslt)) 
				{
				$total_calls += $rowx[1];
				if (preg_match('/INGROUP/', $rowx[0])) {$total_inbound+=$rowx[1];}
				if (preg_match('/CAMPAIGN/', $rowx[0])) {$total_outbound+=$rowx[1];}
				}
			}

		echo "<center><TABLE class='admin_stats_table' cellspacing=2 cellpadding=1>\n";
		echo "<tr>";
		echo "<td align='left' colspan='4'><font CLASS=\"android_standard bold\">"._QXZ("Total Stats for Yesterday").":</font></td>";
		# echo "<td align='right'><font size=1><a href='$PHP_SELF?query_date=$thirtydays&end_date=$today&max_system_stats_submit=ADJUST+DATE+RANGE&ADD=999992&stage=TOTAL'>["._QXZ("view max stats")."]</a></font></td>";
		echo "</tr>";
		echo "<tr bgcolor=black>";
	#	echo "<td><font size=1 color=white align=left><B>CAMPAIGN ID</B></font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=white>"._QXZ("Total Calls")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=white>"._QXZ("Total Inbound Calls")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=white>"._QXZ("Total Outbound Calls")." &nbsp;</font></td>";
		echo "<td align='center'><font CLASS=\"android_standard\" color=white>"._QXZ("Maximum Agents")." &nbsp;</font></td>";

		$stmt="SELECT * from vicidial_daily_max_stats where stats_date='$yesterday' and stats_type='TOTAL' $LOGallowed_campaignsSQL order by stats_date, campaign_id asc";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		if (mysqli_num_rows($rslt)>0) 
			{
			while ($row=mysqli_fetch_array($rslt)) 
				{
				echo "<tr bgcolor='#$SSstd_row2_background'>";
				#echo "<td align='left'><font size=1>".$row["campaign_id"]."</font></td>";
				echo "<td align='center'><font CLASS=\"android_standard bold\">".($row["total_calls"]+0)." / ".($total_calls+0)."</font></td>";
				echo "<td align='center'><font CLASS=\"android_standard bold\">".($total_inbound+0)."</font></td>";
				echo "<td align='center'><font CLASS=\"android_standard bold\">".($total_outbound+0)."</font></td>";
				echo "<td align='center'><font CLASS=\"android_standard bold\">".($row["max_agents"]+0)."</font></td>";
				echo "</tr>";
				}
			} 
		else 
			{
			echo "<tr bgcolor='#$SSstd_row2_background'>";
			echo "<td align='center' colspan='4'><font CLASS=\"android_standard bold\">*** "._QXZ("NO ACTIVITY FOR")." $today ***</font></td>";
			echo "</tr>";
			}

		echo "</FONT><BR><BR>";
		}
	else
		{
		echo "<BR><FONT CLASS=\"android_standard\">";
		echo "<center><TABLE class='admin_stats_table' cellspacing=2>\n";
		echo "<tr>";
		echo "<td align='left' colspan='4'>"._QXZ("Welcome")."</td>";
		echo "</tr>";

		echo "</FONT><BR><BR>";
		}
	}

echo "</TD></TR></TABLE></center>\n";

#### GRAPHS ####

		echo "<BR>";

		echo "<TABLE align='center' class='admin_stats_table' cellspacing=2>\n";
		echo "<tr>";
		echo "<td>";
		echo "<canvas id='ingroup_hour_chart'></canvas>";
		echo "</td></tr>";
		echo "</table>";


echo "</TD></TR></TABLE></center>\n";

$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "</TD></TR>\n";
echo "<TR><TD bgcolor=#$SSmenu_background ALIGN=CENTER>\n";
echo "<FONT STYLE=\"font-family:HELVETICA;font-size:9;color:white;\"><br><br><!-- RUNTIME: $RUNtime seconds<BR> -->";
echo _QXZ("VERSION").": $admin_version<BR>";
echo _QXZ("BUILD").": $build\n";
if (!preg_match("/_BUILD_/",$SShosted_settings))
	{echo "<BR><a href=\"$PHP_SELF?ADD=999995\"><font color=white>&copy; 2019 ViciDial Group</font></a><BR><img src=\"images/pixel.gif\">";}
echo "</FONT>\n";
?>

</TD><TD BGCOLOR=#<?php echo $SSframe_background ?>>
</TD></TR><TABLE>
</body>
<script src='chart/Chart.js'></script>

<script language="Javascript">
if (!window.A_TCALSIDX)
	{
	if (document.addEventListener)
		window.removeEventListener('scroll', f_tcalHideAll);
	if (window.attachEvent)
		window.detachEvent('onscroll', f_tcalHideAll);
	}

<?php
$JS_text="var CAMPAIGN_HOURS=[";
$JS_agents="var agents_hours=[";
$JS_answers="var answers_hours=[";
$JS_calls="var campaign_calls_hours=[";
$JS_drops="var drops_hours=[";
$JS_machines="var machines_hours=[";
$hour_categories=array('AGENTS', 'ANSWERS', 'CALLS', 'DROPS', 'MACHINES');
$category_counts=array();
$hour_array=array();
$calls_array=array();

# $hour_stmt="select distinct next_hour from vicidial_campaign_hour_counts where $LOGallowed_campaignsSQL";
$hour_stmt="select substr(date_hour,1,16) as dhour, sum(calls) from vicidial_campaign_hour_counts where type='CALLS' $LOGallowed_campaignsSQL group by dhour order by dhour";
$hour_rslt=mysqli_query($link, $hour_stmt);
while ($row=mysqli_fetch_row($hour_rslt)) 
	{
	$next_hour=$row[0];
	array_push($hour_array, $row[0]);
	array_push($calls_array, $row[1]);
	}
if (count($calls_array)>0)
	{
	$JS_text.="'".implode("', '", $hour_array)."'";
	$JS_calls.="'".implode("', '", $calls_array)."'";
	}

$JS_text.="];\n";
$JS_calls.="];\n";

/*
$campaign_stmt="select distinct campaign_id from vicidial_campaign_hour_counts where $LOGallowed_campaignsSQL";
$campaign_rslt=mysqli_query($link, $campaign_stmt);
$campaign_array=array();
while ($campaign_row=mysqli_fetch_row($campaign_rslt)) 
	{
	array_push($campaign_array, $campaign_row[0]);
	}

for ($i=0; $i<count($hour_array); $i++)
	{
	$hour=$hour_array[$i];
	$category_counts["$hour"]=0;
	$stat_stmt="select * from vicidial_campaign_hour_counts where campaign_id='$campaign_id' and date_hour='$hour' and type='CALLS'";
	}
*/

echo $JS_text.$JS_calls;

$ingroup_array=array();
$ingroup_stmt="select distinct closer_campaigns from vicidial_campaigns $whereLOGallowed_campaignsSQL";
$ingroup_rslt=mysqli_query($link, $ingroup_stmt);
while ($ingroup_row=mysqli_fetch_row($ingroup_rslt)) 
	{
	$closer_campaigns=preg_replace('/ -$/', '', $ingroup_row[0]);
	$campaign_ingroups=explode(" ", $closer_campaigns);
	$ingroup_array=array_merge($ingroup_array, $campaign_ingroups);
	}
$allowed_ingroups=array_unique($ingroup_array);

$JS_text="var INGROUPS_HOURS=[";
$JS_calls="var ingroup_calls_hours=[";
$ingroup_hour_array=array();
$ingroup_calls_array=array();

echo "// $hour_stmt\n";

$hour_stmt="select substr(date_hour,1,16) as dhour, sum(calls) from vicidial_ingroup_hour_counts where type='CALLS' and group_id in ('".implode("', '", $allowed_ingroups)."') group by dhour order by dhour";
$hour_rslt=mysqli_query($link, $hour_stmt);
while ($row=mysqli_fetch_row($hour_rslt)) 
	{
	$next_hour=$row[0];
	array_push($ingroup_hour_array, $row[0]);
	array_push($ingroup_calls_array, $row[1]);
	}
if (count($ingroup_calls_array)>0)
	{
	$JS_text.="'".implode("', '", $ingroup_hour_array)."'";
	$JS_calls.="'".implode("', '", $ingroup_calls_array)."'";
	}

$JS_text.="]\n";
$JS_calls.="]\n";

echo "// $hour_stmt\n";

echo $JS_text.$JS_calls;

?>

function ToggleReports() {
	var link_text=document.getElementById('ReportLink').innerHTML;
	if (link_text=="SHOW REPORTS") {
		document.getElementById('ReportLink').innerHTML="HIDE REPORTS";
		document.getElementById('ReportStorageDiv').style="display:block";
	} else {
		document.getElementById('ReportStorageDiv').style="display:none";
		document.getElementById('ReportLink').innerHTML="SHOW REPORTS";
	}
}

function LoadHourlyCharts() {
	if (campaign_calls_hours.length>0)
		{
		HourlyCampaignChartData = {
			labels: INGROUPS_HOURS,
			datasets: [{
				label: '<?php echo _QXZ("OUTBOUND"); ?>',
				fill: true,
				backgroundColor: 'rgb(54, 162, 235, 0.5)',
				borderColor: 'rgb(32, 32, 235)',
				borderWidth: 1,
				data: campaign_calls_hours,
				yAxisID: 'y-axis-1'
				}, {
				label: '<?php echo _QXZ("INBOUND"); ?>',
				fill: true,
				backgroundColor: 'rgb(255, 99, 132, 0.5)',
				borderColor: 'rgb(255, 32, 32)',
				borderWidth: 1,
				data: ingroup_calls_hours,
				yAxisID: 'y-axis-2'
				}]
			};

		var ctx = document.getElementById('campaign_hour_chart').getContext('2d');
		MainGraph = new Chart(ctx, {
			type: 'line',
			data: HourlyCampaignChartData,
			options: {
				scales: {

					yAxes: [{
						type: 'linear', // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
						display: true,
						position: 'left',
						id: 'y-axis-1',
						scaleLabel: {
							display: true,
							labelString: 'OUTBOUND'
						}
					}, {
						type: 'linear', // only linear but allow scale type registration. This allows extensions to exist solely for log scale for instance
						display: true,
						position: 'right',
						id: 'y-axis-2',
						scaleLabel: {
							display: true,
							labelString: 'INBOUND'
						},

						// grid line settings
						gridLines: {
							drawOnChartArea: false, // only want the grid lines for one axis to show up
						},
					}],


					xAxes: [{
						display: true,
						ticks: {
							beginAtZero: true,
							min: 0
						}
					}]
				},
				elements: {
					rectangle: {
						borderWidth: 1,
					}
				},
				responsive: true,
				legend: {
					display: true,
					position: 'top',
				},
				title: {
					display: true,
					text: 'CAMPAIGN HOURLY CALL COUNTS'
				}
			}
		});
		}
	else
		{
		var ctx = document.getElementById('campaign_hour_chart').getContext('2d');
		document.getElementById('campaign_hour_chart').height=400;
		ctx.font = "bold 14px Arial";
		ctx.textAlign = "center";
		ctx.fillText("<?php echo "*** "._QXZ("NO HOURLY CAMPAIGN COUNTS")." ***"; ?>", document.getElementById('campaign_hour_chart').width/2, document.getElementById('campaign_hour_chart').height/4);
		// document.getElementById('campaign_hour_chart').innerHTML="<TABLE class='admin_stats_table' cellspacing=2><tr bgcolor=black><td align='center'><font CLASS=\"android_standard\">&nbsp;  &nbsp;</font></td></table>";
		}
<?php
if ($voodoo)
{
?>
	if (ingroup_calls_hours.length>0)
		{
		HourlyIngroupChartData = {
			labels: INGROUPS_HOURS,
			datasets: [{
				label: '<?php echo _QXZ("CALLS"); ?>',
				fill: true,
				backgroundColor: 'rgb(255, 99, 132)',
				borderColor: 'rgb(255, 32, 32)',
				borderWidth: 1,
				data: ingroup_calls_hours
				}]
			};

		var ctx2 = document.getElementById('ingroup_hour_chart').getContext('2d');
		MainGraph = new Chart(ctx2, {
			type: 'line',
			data: HourlyIngroupChartData,
			options: {
				scales: {
					xAxes: [{
						display: true,
						ticks: {
							beginAtZero: true,
							min: 0
						}
					}]
				},
				elements: {
					rectangle: {
						borderWidth: 1,
					}
				},
				responsive: true,
				legend: {
					display: false,
					position: 'right',
				},
				title: {
					display: true,
					text: 'INGROUP HOURLY CALL COUNTS'
				}
			}
		});
		}
	else
		{
		var ctx2 = document.getElementById('ingroup_hour_chart').getContext('2d');
		document.getElementById('ingroup_hour_chart').height=40;
		ctx2.font = "bold 14px Arial";
		ctx2.textAlign = "center";
		ctx2.fillText("<?php echo "*** "._QXZ("NO HOURLY INGROUP COUNTS")." ***"; ?>", document.getElementById('ingroup_hour_chart').width/2, document.getElementById('ingroup_hour_chart').height/2);
		// document.getElementById('ingroup_hour_chart').innerHTML="<TABLE class='admin_stats_table' cellspacing=2><tr bgcolor=black><td align='center'><font CLASS=\"android_standard\">&nbsp; <?php echo "*** "._QXZ("NO HOURLY INGROUP COUNTS")." ***"; ?> &nbsp;</font></td></table>";
		}

<?php } ?>
/*
, {
				label: '<?php echo _QXZ("ANSWERS"); ?>',
				backgroundColor: 'rgb(54, 235, 162)',
				borderColor: 'rgb(32, 128, 32)',
				borderWidth: 1,
				data: answers_hours
			}, {
				label: '<?php echo _QXZ("CALLS"); ?>',
				backgroundColor: 'rgb(255, 99, 132)',
				borderColor: 'rgb(255, 32, 32)',
				borderWidth: 1,
				data: campaign_calls_hours
			}, {
				label: '<?php echo _QXZ("DROPS"); ?>',
				backgroundColor: 'rgb(235, 162, 235)',
				borderColor: 'rgb(235, 32, 235)',
				borderWidth: 1,
				data: drops_hours
			}, {
				label: '<?php echo _QXZ("MACHINES"); ?>',
				backgroundColor: 'rgb(235, 235, 162)',
				borderColor: 'rgb(235, 235, 32)',
				borderWidth: 1,
				data: machines_hours
			}
*/
}
</script>
<?php

if ( ($SSnocache_admin=='1') or ( ($SSadmin_modify_refresh > 1) and ($modify_footer_refresh > 0) and (strlen($modify_url)>10) ) )
	{
	echo "<head>\n";
	if ($SSnocache_admin=='1')
		{
		echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
		echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">\n";
		echo "<META HTTP-EQUIV=\"CACHE-CONTROL\" CONTENT=\"NO-CACHE\">\n";
		}
	if ( ($SSadmin_modify_refresh > 1) and ($modify_footer_refresh > 0) and (strlen($modify_url)>10) )
		{
		echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$SSadmin_modify_refresh;URL=$modify_url\">\n";
		}
	echo "</head>\n";
	}

echo "</html>\n";

	
exit;


##### CALCULATE COMPLETE LEADS #####
function complete_leads($DB,$link,$dial_statuses,$camp_lists,$call_count_limit,$single_status,$campaign_id)
{
if (isset($camp_lists))
	{
	if (strlen($camp_lists)>1)
		{
		if (strlen($dial_statuses)>2)
			{
			$dial_statuses = preg_replace("/ -$/","",$dial_statuses);
			$Dstatuses = explode(" ", $dial_statuses);
			$Ds_to_print = (count($Dstatuses) - 0);
			$Dsql = '';
			$o=0;
			while ($Ds_to_print > $o) 
				{
				$o++;
				$Dsql .= "'$Dstatuses[$o]',";
				}
			$Dsql = preg_replace("/,$/","",$Dsql);
			if (strlen($Dsql) < 2) {$Dsql = "''";}

			$CCLsql = "(called_count < 0) or";
			if ($call_count_limit > 0)
				{$CCLsql = "(called_count >= $call_count_limit) or";}

			$complete_statuses='';
			$stmt="SELECT status from vicidial_statuses where completed='Y';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$statuses_to_print = mysqli_num_rows($rslt);
			$q=0;
			while ($statuses_to_print > $q) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$complete_statuses.="'$rowx[0]',";
				$q++;
				}
			$stmt="SELECT status from vicidial_campaign_statuses where completed='Y' $LOGallowed_campaignsSQL;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$statuses_to_print = mysqli_num_rows($rslt);
			$q=0;
			while ($statuses_to_print > $q) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$complete_statuses.="'$rowx[0]',";
				$q++;
				}
			$complete_statuses = preg_replace("/,$/","",$complete_statuses);
			if (strlen($complete_statuses) < 2) {$complete_statuses = "''";}
			$CSsql = "status IN($complete_statuses)";

			$stmt="SELECT count(*) FROM vicidial_list where ( (status IN($Dsql)) and (list_id IN($camp_lists)) and ( $CCLsql ($CSsql) ) );";
			#$DB=1;
			if ($DB) {echo "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rslt_rows = mysqli_num_rows($rslt);
			if ($rslt_rows)
				{
				$rowx=mysqli_fetch_row($rslt);
				$complete_leads = $rowx[0];
				}
			else {$complete_leads = '0';}

			if ($DB > 0) {echo "|$DB|\n";}
			if ($single_status > 0)
				{return $complete_leads;}
			else
				{echo "There are $complete_leads completed leads in those lists\n";}
			}
		else
			{
			echo _QXZ("no dial statuses selected for this campaign")."\n";
			}
		}
	else
		{
		echo _QXZ("no active lists selected for this campaign")."\n";
		}
	}
else
	{
	echo _QXZ("no active lists selected for this campaign")."\n";
	}
}

?>
