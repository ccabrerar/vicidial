UPDATE system_settings SET db_schema_version='1404',version='2.11rc1tk',db_schema_update_date=NOW() where db_schema_version < 1404;

UPDATE system_settings SET db_schema_version='1405',version='2.12b0.5',db_schema_update_date=NOW() where db_schema_version < 1405;

ALTER TABLE system_settings ADD meetme_enter_login_filename VARCHAR(255) default '';
ALTER TABLE system_settings ADD meetme_enter_leave3way_filename VARCHAR(255) default '';

UPDATE system_settings SET db_schema_version='1406',db_schema_update_date=NOW() where db_schema_version < 1406;

ALTER TABLE system_settings ADD enable_did_entry_list_id ENUM('0','1') default '0';

ALTER TABLE vicidial_inbound_dids ADD entry_list_id BIGINT(14) UNSIGNED default '0';
ALTER TABLE vicidial_inbound_dids ADD filter_entry_list_id BIGINT(14) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1407',db_schema_update_date=NOW() where db_schema_version < 1407;

ALTER TABLE system_settings ADD enable_third_webform ENUM('0','1') default '0';

ALTER TABLE vicidial_campaigns ADD web_form_address_three TEXT;
ALTER TABLE vicidial_lists ADD web_form_address_three TEXT;
ALTER TABLE vicidial_inbound_groups ADD web_form_address_three TEXT;

ALTER TABLE vicidial_campaigns MODIFY get_call_launch ENUM('NONE','SCRIPT','SCRIPTTWO','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM','PREVIEW_WEBFORM','PREVIEW_WEBFORMTWO','PREVIEW_WEBFORMTHREE','PREVIEW_SCRIPT','PREVIEW_SCRIPTTWO','PREVIEW_FORM') default 'NONE';
ALTER TABLE vicidial_inbound_groups MODIFY get_call_launch ENUM('NONE','SCRIPT','SCRIPTTWO','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM') default 'NONE';

UPDATE system_settings SET db_schema_version='1408',db_schema_update_date=NOW() where db_schema_version < 1408;

ALTER TABLE vicidial_users ADD api_list_restrict ENUM('1','0') default '0';

UPDATE system_settings SET db_schema_version='1409',db_schema_update_date=NOW() where db_schema_version < 1409;

ALTER TABLE vicidial_users ADD api_allowed_functions VARCHAR(1000) default ' ALL_FUNCTIONS ';

UPDATE system_settings SET db_schema_version='1410',db_schema_update_date=NOW() where db_schema_version < 1410;

ALTER TABLE vicidial_email_accounts ADD pop3_auth_mode ENUM('BEST','PASS','APOP','CRAM-MD5') default 'BEST' AFTER email_account_pass;

UPDATE system_settings SET db_schema_version='1411',db_schema_update_date=NOW() where db_schema_version < 1411;

ALTER TABLE system_settings ADD chat_url VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE system_settings ADD chat_timeout INT(3) UNSIGNED DEFAULT NULL;

ALTER TABLE vicidial_inbound_groups MODIFY group_handling ENUM('PHONE','EMAIL','CHAT') default 'PHONE';

CREATE TABLE vicidial_chat_archive (
chat_id INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
chat_start_time DATETIME DEFAULT NULL,
status VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_creator VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
group_id VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
lead_id INT(9) UNSIGNED DEFAULT NULL,
PRIMARY KEY (chat_id),
KEY vicidial_chat_archive_lead_id_key (lead_id),
KEY vicidial_chat_archive_start_time_key (chat_start_time)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_chat_log (
message_row_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
chat_id VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
message MEDIUMTEXT COLLATE utf8_unicode_ci,
message_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
poster VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_member_name VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_level ENUM('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
PRIMARY KEY (message_row_id),
KEY vicidial_chat_log_user_key (poster)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_chat_log_archive (
message_row_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
chat_id VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
message MEDIUMTEXT COLLATE utf8_unicode_ci,
message_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
poster VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_member_name VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_level ENUM('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
PRIMARY KEY (message_row_id),
KEY vicidial_chat_log_archive_user_key (poster)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_chat_participants (
chat_participant_id INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
chat_id INT(9) UNSIGNED DEFAULT NULL,
chat_member VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_member_name VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
ping_date DATETIME DEFAULT NULL,
vd_agent ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
PRIMARY KEY (chat_participant_id),
UNIQUE KEY vicidial_chat_participants_key (chat_id,chat_member)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_live_chats (
chat_id INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
chat_start_time DATETIME DEFAULT NULL,
status VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_creator VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
group_id VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
lead_id INT(9) UNSIGNED DEFAULT NULL,
PRIMARY KEY (chat_id),
KEY vicidial_live_chats_lead_id_key (lead_id),
KEY vicidial_live_chats_start_time_key (chat_start_time)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_manager_chat_log (
manager_chat_message_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
manager_chat_id INT(10) UNSIGNED DEFAULT NULL,
manager_chat_subid TINYINT(3) UNSIGNED DEFAULT NULL,
manager VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
user VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
message MEDIUMTEXT COLLATE utf8_unicode_ci,
message_date DATETIME DEFAULT NULL,
message_viewed_date DATETIME DEFAULT NULL,
message_posted_by VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
audio_alerted ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
PRIMARY KEY (manager_chat_message_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_manager_chat_log_archive (
manager_chat_message_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
manager_chat_id INT(10) UNSIGNED DEFAULT NULL,
manager_chat_subid TINYINT(3) UNSIGNED DEFAULT NULL,
manager VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
user VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
message MEDIUMTEXT COLLATE utf8_unicode_ci,
message_date DATETIME DEFAULT NULL,
message_viewed_date DATETIME DEFAULT NULL,
message_posted_by VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
audio_alerted ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
PRIMARY KEY (manager_chat_message_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_manager_chats (
manager_chat_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
chat_start_date DATETIME DEFAULT NULL,
manager VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
selected_agents MEDIUMTEXT COLLATE utf8_unicode_ci,
selected_user_groups MEDIUMTEXT COLLATE utf8_unicode_ci,
selected_campaigns MEDIUMTEXT COLLATE utf8_unicode_ci,
allow_replies ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
PRIMARY KEY (manager_chat_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_manager_chats_archive (
manager_chat_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
chat_start_date DATETIME DEFAULT NULL,
manager VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
selected_agents MEDIUMTEXT COLLATE utf8_unicode_ci,
selected_user_groups MEDIUMTEXT COLLATE utf8_unicode_ci,
selected_campaigns MEDIUMTEXT COLLATE utf8_unicode_ci,
allow_replies ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
PRIMARY KEY (manager_chat_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1412',db_schema_update_date=NOW() where db_schema_version < 1412;

ALTER TABLE vicidial_campaigns ADD manual_dial_override_field ENUM('ENABLED','DISABLED') default 'ENABLED';

UPDATE system_settings SET db_schema_version='1413',db_schema_update_date=NOW() where db_schema_version < 1413;

ALTER TABLE vicidial_campaigns ADD status_display_ingroup ENUM('ENABLED','DISABLED') default 'ENABLED';

ALTER TABLE vicidial_inbound_groups ADD populate_lead_ingroup ENUM('ENABLED','DISABLED') default 'ENABLED';

ALTER TABLE vicidial_scripts ADD script_color VARCHAR(7) default 'white';

UPDATE system_settings SET db_schema_version='1414',db_schema_update_date=NOW() where db_schema_version < 1414;

ALTER TABLE vicidial_campaigns ADD customer_gone_seconds SMALLINT(5) UNSIGNED default '30';

UPDATE system_settings SET db_schema_version='1415',db_schema_update_date=NOW() where db_schema_version < 1415;

UPDATE vicidial_inbound_groups set group_handling='PHONE' where group_handling='';

UPDATE system_settings SET db_schema_version='1416',db_schema_update_date=NOW() where db_schema_version < 1416;

ALTER TABLE vicidial_campaigns MODIFY lead_filter_id VARCHAR(20) default 'NONE';

ALTER TABLE vicidial_lead_filters MODIFY lead_filter_id VARCHAR(20) NOT NULL;

ALTER TABLE vicidial_users ADD lead_filter_id VARCHAR(20) default 'NONE';

UPDATE system_settings SET db_schema_version='1417',db_schema_update_date=NOW() where db_schema_version < 1417;

ALTER TABLE vicidial_inbound_dids ADD max_queue_ingroup_calls SMALLINT(5) default '0';
ALTER TABLE vicidial_inbound_dids ADD max_queue_ingroup_id VARCHAR(20) default '';
ALTER TABLE vicidial_inbound_dids ADD max_queue_ingroup_extension VARCHAR(50) default '9998811112';

UPDATE system_settings SET db_schema_version='1418',db_schema_update_date=NOW() where db_schema_version < 1418;

CREATE TABLE vicidial_url_multi (
url_id INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
campaign_id VARCHAR(20) NOT NULL,
entry_type ENUM('campaign','ingroup','list','') default '',
active ENUM('Y','N') default 'N',
url_type ENUM('dispo','start','addlead','noagent','') default '',
url_rank SMALLINT(5) default '1',
url_statuses VARCHAR(1000) default '',
url_description VARCHAR(255) default '',
url_address TEXT,
PRIMARY KEY (url_id),
KEY vicidial_url_multi_campaign_id_key (campaign_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1419',db_schema_update_date=NOW() where db_schema_version < 1419;

CREATE TABLE vicidial_dtmf_log (
dtmf_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
dtmf_time DATETIME,
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
uniqueid VARCHAR(20) default '',
digit VARCHAR(1) default '',
direction ENUM('Received','Sent') default 'Received',
state ENUM('BEGIN','END') default 'BEGIN',
PRIMARY KEY (dtmf_id),
KEY vicidial_dtmf_uniqueid_key (uniqueid)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1420',db_schema_update_date=NOW() where db_schema_version < 1420;

CREATE TABLE vicidial_ajax_log (
user VARCHAR(20) default '',
start_time DATETIME NOT NULL,
db_time DATETIME NOT NULL,
run_time VARCHAR(20) default '0',
php_script VARCHAR(40) NOT NULL,
action VARCHAR(100) default '',
lead_id INT(10) UNSIGNED default '0',
stage VARCHAR(100) default '',
session_name VARCHAR(40) default '',
last_sql TEXT,
KEY ajax_dbtime_key (db_time)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE system_settings ADD agent_debug_logging VARCHAR(20) default '0';

UPDATE system_settings SET db_schema_version='1421',db_schema_update_date=NOW() where db_schema_version < 1421;

ALTER TABLE vicidial_campaigns ADD agent_display_fields VARCHAR(50) default '';

UPDATE system_settings SET db_schema_version='1422',db_schema_update_date=NOW() where db_schema_version < 1422;

ALTER TABLE system_settings ADD default_language VARCHAR(100) default 'default English';

UPDATE system_settings SET db_schema_version='1423',db_schema_update_date=NOW() where db_schema_version < 1423;

ALTER TABLE vicidial_campaigns MODIFY lead_order_secondary ENUM('LEAD_ASCEND','LEAD_DESCEND','CALLTIME_ASCEND','CALLTIME_DESCEND','VENDOR_ASCEND','VENDOR_DESCEND') default 'LEAD_ASCEND';

UPDATE system_settings SET db_schema_version='1424',db_schema_update_date=NOW() where db_schema_version < 1424;

ALTER TABLE system_settings ADD agent_whisper_enabled ENUM('0','1') default '0';

UPDATE servers SET rebuild_conf_files='Y' where active_asterisk_server='Y';

UPDATE system_settings SET db_schema_version='1425',db_schema_update_date=NOW() where db_schema_version < 1425;

ALTER TABLE vicidial_inbound_groups ADD drop_lead_reset ENUM('Y','N') default 'N';
ALTER TABLE vicidial_inbound_groups ADD after_hours_lead_reset ENUM('Y','N') default 'N';
ALTER TABLE vicidial_inbound_groups ADD nanq_lead_reset ENUM('Y','N') default 'N';
ALTER TABLE vicidial_inbound_groups ADD wait_time_lead_reset ENUM('Y','N') default 'N';
ALTER TABLE vicidial_inbound_groups ADD hold_time_lead_reset ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1426',db_schema_update_date=NOW() where db_schema_version < 1426;

CREATE TABLE vicidial_settings_containers (
container_id VARCHAR(40) PRIMARY KEY NOT NULL,
container_notes VARCHAR(255) default '',
container_type VARCHAR(40) default 'OTHER',
user_group VARCHAR(20) default '---ALL---',
container_entry MEDIUMTEXT
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1427',db_schema_update_date=NOW() where db_schema_version < 1427;

ALTER TABLE vicidial_lists_fields ADD field_encrypt ENUM('Y','N') default 'N';
ALTER TABLE vicidial_lists_fields ADD field_show_hide ENUM('DISABLED','X_OUT_ALL','LAST_1','LAST_2','LAST_3','LAST_4','FIRST_1_LAST_4') default 'DISABLED';

ALTER TABLE vicidial_users ADD admin_cf_show_hidden ENUM('1','0') default '0';

UPDATE system_settings SET db_schema_version='1428',db_schema_update_date=NOW() where db_schema_version < 1428;

ALTER TABLE vicidial_users ADD agentcall_chat ENUM('1','0') default '0';

UPDATE system_settings SET db_schema_version='1429',db_schema_update_date=NOW() where db_schema_version < 1429;

ALTER TABLE system_settings ADD user_hide_realtime_enabled ENUM('0','1') default '0';

ALTER TABLE vicidial_users ADD user_hide_realtime ENUM('1','0') default '0';

UPDATE system_settings SET db_schema_version='1430',db_schema_update_date=NOW() where db_schema_version < 1430;

ALTER TABLE vicidial_inbound_dids ADD did_carrier_description VARCHAR(255) default '';

UPDATE system_settings SET db_schema_version='1431',db_schema_update_date=NOW() where db_schema_version < 1431;

CREATE TABLE vicidial_dnc_log (
phone_number VARCHAR(18) NOT NULL,
campaign_id VARCHAR(8) NOT NULL,
action ENUM('add','delete') default 'add',
action_date DATETIME NOT NULL,
user VARCHAR(20) default '',
index (phone_number)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1432',db_schema_update_date=NOW() where db_schema_version < 1432;

CREATE TABLE vicidial_status_groups (
status_group_id VARCHAR(20) PRIMARY KEY NOT NULL,
status_group_notes VARCHAR(255) default '',
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE vicidial_campaign_statuses MODIFY campaign_id VARCHAR(20);

ALTER TABLE vicidial_lists ADD status_group_id VARCHAR(20) default '';

ALTER TABLE vicidial_inbound_groups ADD status_group_id VARCHAR(20) default '';

ALTER TABLE vicidial_campaign_statuses ADD min_sec INT(5) UNSIGNED default '0';
ALTER TABLE vicidial_campaign_statuses ADD max_sec INT(5) UNSIGNED default '0';

ALTER TABLE vicidial_statuses ADD min_sec INT(5) UNSIGNED default '0';
ALTER TABLE vicidial_statuses ADD max_sec INT(5) UNSIGNED default '0';

ALTER TABLE system_settings ADD custom_reports_use_slave_db VARCHAR(2000) default '';

ALTER TABLE vicidial_user_groups ADD allowed_custom_reports VARCHAR(2000) default '';

CREATE TABLE vicidial_custom_reports (
custom_report_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
report_name VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
date_added DATETIME DEFAULT NULL,
user VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
domain VARCHAR(70) COLLATE utf8_unicode_ci DEFAULT NULL,
path_name VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
date_modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
user_modify VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
PRIMARY KEY (custom_report_id),
UNIQUE KEY custom_report_name_key (report_name)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1433',db_schema_update_date=NOW() where db_schema_version < 1433;

ALTER TABLE system_settings ADD usacan_phone_dialcode_fix ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1434',db_schema_update_date=NOW() where db_schema_version < 1434;

CREATE TABLE vicidial_amm_multi (
amm_id INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
campaign_id VARCHAR(20) NOT NULL,
entry_type ENUM('campaign','ingroup','list','') default '',
active ENUM('Y','N') default 'N',
amm_field VARCHAR(30) default 'vendor_lead_code',
amm_rank SMALLINT(5) default '1',
amm_wildcard VARCHAR(100) default '',
amm_filename VARCHAR(255) default '',
amm_description VARCHAR(255) default '',
PRIMARY KEY (amm_id),
KEY vicidial_AMM_multi_campaign_id_key (campaign_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE vicidial_campaigns ADD am_message_wildcards ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1435',db_schema_update_date=NOW() where db_schema_version < 1435;

CREATE TABLE vicidial_html_cache_stats (
stats_type VARCHAR(20) NOT NULL,
stats_id VARCHAR(20) NOT NULL,
stats_date DATETIME NOT NULL,
stats_count INT(9) UNSIGNED default '0',
stats_html MEDIUMTEXT,
UNIQUE KEY vicidial_html_cache_stats_key (stats_type,stats_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE system_settings ADD cache_carrier_stats_realtime ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1436',db_schema_update_date=NOW() where db_schema_version < 1436;

ALTER TABLE system_settings ADD oldest_logs_date DATETIME;

UPDATE system_settings SET db_schema_version='1437',db_schema_update_date=NOW() where db_schema_version < 1437;

CREATE TABLE vicidial_dnccom_filter_log (
filter_log_id INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT NULL,
filter_date DATETIME DEFAULT NULL,
new_status VARCHAR(6) COLLATE utf8_unicode_ci DEFAULT NULL,
old_status VARCHAR(6) COLLATE utf8_unicode_ci DEFAULT NULL,
phone_number VARCHAR(18) COLLATE utf8_unicode_ci DEFAULT NULL,
dnccom_data VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
PRIMARY KEY (filter_log_id),
KEY lead_id (lead_id),
KEY filter_date (filter_date)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1438',db_schema_update_date=NOW() where db_schema_version < 1438;

ALTER TABLE phones ADD unavail_dialplan_fwd_exten VARCHAR(40) default '';
ALTER TABLE phones ADD unavail_dialplan_fwd_context VARCHAR(100) default '';

UPDATE system_settings SET db_schema_version='1439',db_schema_update_date=NOW() where db_schema_version < 1439;

ALTER TABLE phones ADD nva_call_url TEXT;
ALTER TABLE phones ADD nva_search_method VARCHAR(40) default 'NONE';
ALTER TABLE phones ADD nva_error_filename VARCHAR(255) default '';

UPDATE system_settings SET db_schema_version='1440',db_schema_update_date=NOW() where db_schema_version < 1440;

ALTER TABLE phones ADD nva_new_list_id BIGINT(14) UNSIGNED default '995';
ALTER TABLE phones ADD nva_new_phone_code VARCHAR(10) default '1';
ALTER TABLE phones ADD nva_new_status VARCHAR(6) default 'NVAINS';

UPDATE system_settings SET db_schema_version='1441',db_schema_update_date=NOW() where db_schema_version < 1441;

CREATE TABLE vicidial_country_iso_tld (
country_name VARCHAR(200) default '',
iso2 VARCHAR(2) default '',
iso3 VARCHAR(3) default '',
num3 VARCHAR(4) default '',
tld VARCHAR(20) default '',
index(iso3)
)ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1442',db_schema_update_date=NOW() where db_schema_version < 1442;

CREATE TABLE vicidial_asterisk_output (
server_ip VARCHAR(15) NOT NULL,
sip_peers MEDIUMTEXT,
iax_peers MEDIUMTEXT,
asterisk MEDIUMTEXT,
update_date DATETIME,
unique index(server_ip)
)ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE servers ADD gather_asterisk_output ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1443',db_schema_update_date=NOW() where db_schema_version < 1443;

ALTER TABLE vicidial_campaigns ADD manual_dial_timeout VARCHAR(3) default '';

UPDATE system_settings SET db_schema_version='1444',db_schema_update_date=NOW() where db_schema_version < 1444;

ALTER TABLE vicidial_user_groups ADD agent_allowed_chat_groups TEXT;

UPDATE vicidial_user_groups INNER JOIN system_settings ON system_settings.db_schema_version = 1444 SET agent_allowed_chat_groups=agent_status_viewable_groups;

UPDATE system_settings SET db_schema_version='1445',db_schema_update_date=NOW() where db_schema_version < 1445;

ALTER TABLE vicidial_campaigns ADD routing_initiated_recordings ENUM('Y','N') default 'Y';

ALTER TABLE vicidial_inbound_groups ADD routing_initiated_recordings ENUM('Y','N') default 'Y';

CREATE TABLE routing_initiated_recordings (
recording_id INT(10) UNSIGNED PRIMARY KEY NOT NULL,
filename VARCHAR(100),
launch_time DATETIME,
lead_id INT(9) UNSIGNED,
vicidial_id VARCHAR(20),
user VARCHAR(20) DEFAULT NULL,
processed TINYINT(1) default '0',
index(lead_id),
index(user),
index(processed)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1446',db_schema_update_date=NOW() where db_schema_version < 1446;

CREATE INDEX live_chat_id on vicidial_chat_log (chat_id);

UPDATE system_settings SET db_schema_version='1447',db_schema_update_date=NOW() where db_schema_version < 1447;

ALTER TABLE vicidial_inbound_dids MODIFY filter_inbound_number ENUM('DISABLED','GROUP','URL','DNC_INTERNAL','DNC_CAMPAIGN','GROUP_AREACODE') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1448',db_schema_update_date=NOW() where db_schema_version < 1448;

ALTER TABLE vicidial_inbound_groups ADD on_hook_cid_number VARCHAR(18) default '';

UPDATE system_settings SET db_schema_version='1449',db_schema_update_date=NOW() where db_schema_version < 1449;

ALTER TABLE vicidial_campaigns ADD manual_dial_hopper_check ENUM('Y','N') default 'N';

CREATE INDEX manager_chat_id_key on vicidial_manager_chat_log (manager_chat_id);
CREATE INDEX manager_chat_subid_key on vicidial_manager_chat_log (manager_chat_subid);
CREATE INDEX manager_chat_manager_key on vicidial_manager_chat_log (manager);
CREATE INDEX manager_chat_user_key on vicidial_manager_chat_log (user);

CREATE INDEX manager_chat_id_archive_key on vicidial_manager_chat_log_archive (manager_chat_id);
CREATE INDEX manager_chat_subid_archive_key on vicidial_manager_chat_log_archive (manager_chat_subid);

UPDATE system_settings SET db_schema_version='1450',db_schema_update_date=NOW() where db_schema_version < 1450;

CREATE TABLE vicidial_recording_access_log (
recording_access_log_id INT(10) unsigned NOT NULL AUTO_INCREMENT,
recording_id INT(10) unsigned DEFAULT NULL,
lead_id INT(10) unsigned DEFAULT NULL,
user VARCHAR(20) DEFAULT NULL,
access_datetime DATETIME DEFAULT NULL,
access_result ENUM('ACCESSED','INVALID USER','INVALID PERMISSIONS','NO RECORDING','RECORDING UNAVAILABLE') DEFAULT NULL,
ip VARCHAR(15) default '',
PRIMARY KEY (recording_access_log_id),
index(recording_id),
index(lead_id)
) ENGINE=MyISAM AUTO_INCREMENT=1599 DEFAULT CHARSET=utf8;

ALTER TABLE vicidial_users ADD access_recordings ENUM('0', '1') default '0';

ALTER TABLE system_settings ADD log_recording_access ENUM('0', '1') default '0';

UPDATE system_settings SET db_schema_version='1451',db_schema_update_date=NOW() where db_schema_version < 1451;

ALTER TABLE system_settings ADD report_default_format ENUM('TEXT', 'HTML') default 'TEXT';

UPDATE system_settings SET db_schema_version='1452',db_schema_update_date=NOW() where db_schema_version < 1452;

ALTER TABLE vicidial_live_chats ADD transferring_agent VARCHAR(20); 
ALTER TABLE vicidial_live_chats ADD user_direct VARCHAR(20);
ALTER TABLE vicidial_live_chats ADD user_direct_group_id VARCHAR(20);

ALTER TABLE vicidial_chat_archive ADD transferring_agent VARCHAR(20); 
ALTER TABLE vicidial_chat_archive ADD user_direct VARCHAR(20);
ALTER TABLE vicidial_chat_archive ADD user_direct_group_id VARCHAR(20);

INSERT INTO vicidial_inbound_groups(group_id,group_name,group_color,active,web_form_address,voicemail_ext,next_agent_call,fronter_display,ingroup_script,get_call_launch,xferconf_a_dtmf,xferconf_a_number,xferconf_b_dtmf,xferconf_b_number,drop_call_seconds,drop_action,drop_exten,call_time_id,after_hours_action,after_hours_message_filename,after_hours_exten,after_hours_voicemail,welcome_message_filename,moh_context,onhold_prompt_filename,prompt_interval,agent_alert_exten,agent_alert_delay,default_xfer_group,queue_priority,drop_inbound_group,ingroup_recording_override,ingroup_rec_filename,afterhours_xfer_group,qc_enabled,qc_statuses,qc_shift_id,qc_get_record_launch,qc_show_recording,qc_web_form_address,qc_script,play_place_in_line,play_estimate_hold_time,hold_time_option,hold_time_option_seconds,hold_time_option_exten,hold_time_option_voicemail,hold_time_option_xfer_group,hold_time_option_callback_filename,hold_time_option_callback_list_id,hold_recall_xfer_group,no_delay_call_route,play_welcome_message,answer_sec_pct_rt_stat_one,answer_sec_pct_rt_stat_two,default_group_alias,no_agent_no_queue,no_agent_action,no_agent_action_value,web_form_address_two,timer_action,timer_action_message,timer_action_seconds,start_call_url,dispo_call_url,xferconf_c_number,xferconf_d_number,xferconf_e_number,ignore_list_script_override,extension_appended_cidname,uniqueid_status_display,uniqueid_status_prefix,hold_time_option_minimum,hold_time_option_press_filename,hold_time_option_callmenu,hold_time_option_no_block,hold_time_option_prompt_seconds,onhold_prompt_no_block,onhold_prompt_seconds,hold_time_second_option,hold_time_third_option,wait_hold_option_priority,wait_time_option,wait_time_second_option,wait_time_third_option,wait_time_option_seconds,wait_time_option_exten,wait_time_option_voicemail,wait_time_option_xfer_group,wait_time_option_callmenu,wait_time_option_callback_filename,wait_time_option_callback_list_id,wait_time_option_press_filename,wait_time_option_no_block,wait_time_option_prompt_seconds,timer_action_destination,calculate_estimated_hold_seconds,add_lead_url,eht_minimum_prompt_filename,eht_minimum_prompt_no_block,eht_minimum_prompt_seconds,on_hook_ring_time,na_call_url,on_hook_cid,group_calldate,action_xfer_cid,drop_callmenu,after_hours_callmenu,user_group,max_calls_method,max_calls_count,max_calls_action,dial_ingroup_cid,group_handling,web_form_address_three,populate_lead_ingroup,drop_lead_reset,after_hours_lead_reset,nanq_lead_reset,wait_time_lead_reset,hold_time_lead_reset,status_group_id,routing_initiated_recordings,on_hook_cid_number) VALUES ('AGENTDIRECT_CHAT','Agent Direct Queue for Chats','#FFFFFF','Y','','','longest_wait_time','Y','','',NULL,NULL,NULL,NULL,360,'MESSAGE','8307','24hours','MESSAGE','vm-goodbye','8300',NULL,'---NONE---','default','generic_hold',60,'ding',1000,'---NONE---',99,'---NONE---','DISABLED','NONE','---NONE---','N',NULL,'24HRMIDNIGHT','NONE','Y',NULL,NULL,'N','N','NONE',360,'8300','','---NONE---','vm-hangup',0,'---NONE---','N','ALWAYS',20,30,'','N','MESSAGE','nbdy-avail-to-take-call|vm-goodbye','','NONE','',-1,'','','','','','N','N','DISABLED','',0,'to-be-called-back|digits/1','','N',10,'N',10,'NONE','NONE','WAIT','NONE','NONE','NONE',120,'8300','','---NONE---','','vm-hangup',999,'to-be-called-back|digits/1','N',10,'',0,'','','N',10,15,'','GENERIC',NULL,'CUSTOMER','','','---ALL---','DISABLED',0,'DROP','','CHAT','','ENABLED','N','N','N','N','N','','N','');

UPDATE system_settings SET db_schema_version='1453',db_schema_update_date=NOW() where db_schema_version < 1453;

CREATE TABLE vicidial_ivr_response (
id INT(11) unsigned NOT NULL AUTO_INCREMENT,
btn VARCHAR(10) DEFAULT NULL,
lead_id INT(10) unsigned DEFAULT NULL,
created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
question INT(11) DEFAULT NULL,
response VARCHAR(10) DEFAULT NULL,
uniqueid VARCHAR(50) DEFAULT NULL,
campaign VARCHAR(20) DEFAULT NULL,
PRIMARY KEY (id),
KEY question_created (question,uniqueid,campaign,created),
index(lead_id)
) ENGINE=MyISAM AUTO_INCREMENT=1599 DEFAULT CHARSET=utf8;

ALTER TABLE system_settings ADD alt_ivr_logging ENUM('0', '1') default '0';

ALTER TABLE vicidial_call_menu ADD alt_dtmf_log ENUM('0','1') default '0';
ALTER TABLE vicidial_call_menu ADD question INT(11) DEFAULT NULL;

UPDATE system_settings SET db_schema_version='1454',db_schema_update_date=NOW() where db_schema_version < 1454;

ALTER TABLE servers ADD web_socket_url VARCHAR(255) default '';

ALTER TABLE phones ADD webphone_dialbox ENUM('Y','N') default 'Y';
ALTER TABLE phones ADD webphone_mute ENUM('Y','N') default 'Y';
ALTER TABLE phones ADD webphone_volume ENUM('Y','N') default 'Y';
ALTER TABLE phones ADD webphone_debug ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1455',db_schema_update_date=NOW() where db_schema_version < 1455;

ALTER TABLE vicidial_admin_log MODIFY event_sql MEDIUMTEXT;
ALTER TABLE vicidial_admin_log MODIFY event_notes MEDIUMTEXT;

UPDATE system_settings SET db_schema_version='1456',db_schema_update_date=NOW() where db_schema_version < 1456;

ALTER TABLE vicidial_campaigns ADD callback_useronly_move_minutes MEDIUMINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1457',db_schema_update_date=NOW() where db_schema_version < 1457;

ALTER TABLE system_settings ADD admin_row_click ENUM('0', '1') default '1';

UPDATE system_settings SET db_schema_version='1458',db_schema_update_date=NOW() where db_schema_version < 1458;

CREATE TABLE vicidial_screen_colors (
colors_id VARCHAR(20) PRIMARY KEY NOT NULL,
colors_name VARCHAR(100),
active ENUM('Y','N') default 'N',
menu_background VARCHAR(6) default '015B91',
frame_background VARCHAR(6) default 'D9E6FE',
std_row1_background VARCHAR(6) default '9BB9FB',
std_row2_background VARCHAR(6) default 'B9CBFD',
std_row3_background VARCHAR(6) default '8EBCFD',
std_row4_background VARCHAR(6) default 'B6D3FC',
std_row5_background VARCHAR(6) default 'A3C3D6',
alt_row1_background VARCHAR(6) default 'BDFFBD',
alt_row2_background VARCHAR(6) default '99FF99',
alt_row3_background VARCHAR(6) default 'CCFFCC',
user_group VARCHAR(20) default '---ALL---',
web_logo VARCHAR(100) default 'default_new'
) ENGINE=MyISAM;

INSERT INTO vicidial_screen_colors VALUES ('red_rust','dark red rust','Y','804435','E7D0C2','C68C71','D9B39F','D9B49F','C68C72','C68C73','BDFFBD','99FF99','CCFFCC','---ALL---','default_new'),('pale_green','pale green','Y','738035','E0E7C2','B6C572','C4CF8B','B6C572','C4CF8B','C4CF8B','BDFFBD','99FF99','CCFFCC','---ALL---','default_new'),('alt_green','alternate green','Y','333333','D6E3B2','AEC866','BCD180','BCD180','AEC866','AEC866','BDFFBD','99FF99','CCFFCC','---ALL---','default_new'),('default_blue_test','default blue test','Y','015B91','D9E6FE','9BB9FB','B9CBFD','8EBCFD','B6D3FC','A3C3D6','BDFFBD','99FF99','CCFFCC','---ALL---','default_new'),('basic_orange','basic orange','Y','804d00','ffebcc','ffcc80','ffd699','ffcc80','ffd699','ffcc80','BDFFBD','99FF99','CCFFCC','---ALL---','default_new'),('basic_purple','basic purple','Y','660066','ffccff','ff99ff','ffb3ff','ff99ff','ffb3ff','ff99ff','BDFFBD','99FF99','CCFFCC','---ALL---','SAMPLE.png'),('basic_yellow','basic yellow','Y','666600','ffffcc','ffff66','ffff99','ffff66','ffff99','ffff66','BDFFBD','99FF99','CCFFCC','---ALL---','default_new'),('basic_red','basic red','Y','800000','ffe6e6','ff9999','ffb3b3','ff9999','ffb3b3','ff9999','BDFFBD','99FF99','CCFFCC','---ALL---','default_new');

ALTER TABLE vicidial_users ADD modify_colors ENUM('1','0') default '0';

ALTER TABLE system_settings ADD admin_screen_colors VARCHAR(20) default 'default';

UPDATE system_settings SET db_schema_version='1459',db_schema_update_date=NOW() where db_schema_version < 1459;

ALTER TABLE system_settings ADD ofcom_uk_drop_calc ENUM('1','0') default '0';

ALTER TABLE vicidial_campaigns ADD ofcom_uk_drop_calc ENUM('Y','N') default 'N';

ALTER TABLE vicidial_statuses ADD answering_machine ENUM('Y','N') default 'N';
ALTER TABLE vicidial_campaign_statuses ADD answering_machine ENUM('Y','N') default 'N';

UPDATE vicidial_statuses INNER JOIN system_settings ON system_settings.db_schema_version = 1459 SET answering_machine='Y' where status IN('A','AM','AL');

ALTER TABLE vicidial_campaign_stats ADD answering_machines_today INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_campaign_stats ADD agenthandled_today INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_campaign_stats MODIFY drops_today DECIMAL(12,3) default '0';

ALTER TABLE vicidial_drop_rate_groups ADD answering_machines_today INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_drop_rate_groups ADD agenthandled_today INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_drop_rate_groups MODIFY drops_today DOUBLE(12,3) default '0';

UPDATE system_settings SET db_schema_version='1460',db_schema_update_date=NOW() where db_schema_version < 1460;

ALTER TABLE phones ADD outbound_alt_cid VARCHAR(20) default '';

UPDATE system_settings SET db_schema_version='1461',db_schema_update_date=NOW() where db_schema_version < 1461;

ALTER TABLE system_settings ADD agent_screen_colors VARCHAR(20) default 'default';

ALTER TABLE vicidial_user_log ADD browser_width SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_user_log ADD browser_height SMALLINT(5) UNSIGNED default '0';

ALTER TABLE system_settings ADD script_remove_js ENUM('1','0') default '1';

UPDATE system_settings SET db_schema_version='1462',db_schema_update_date=NOW() where db_schema_version < 1462;

ALTER TABLE vicidial_users ADD user_nickname VARCHAR(50) default '';

ALTER TABLE system_settings ADD manual_auto_next ENUM('1','0') default '0';

ALTER TABLE vicidial_campaigns ADD manual_auto_next SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_campaigns ADD manual_auto_show ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1463',db_schema_update_date=NOW() where db_schema_version < 1463;

ALTER TABLE vicidial_url_multi ADD url_lists VARCHAR(1000) default '';

UPDATE system_settings SET db_schema_version='1464',db_schema_update_date=NOW() where db_schema_version < 1464;

ALTER TABLE vicidial_inbound_groups ADD customer_chat_screen_colors VARCHAR(20) default 'default';
ALTER TABLE vicidial_inbound_groups ADD customer_chat_survey_link TEXT;
ALTER TABLE vicidial_inbound_groups ADD customer_chat_survey_text TEXT;

UPDATE system_settings SET db_schema_version='1465',db_schema_update_date=NOW() where db_schema_version < 1465;

CREATE TABLE recording_log_archive LIKE recording_log;
ALTER TABLE recording_log_archive MODIFY recording_id INT(10) UNSIGNED UNIQUE NOT NULL;
ALTER TABLE recording_log_archive DROP PRIMARY KEY;

UPDATE system_settings SET db_schema_version='1466',db_schema_update_date=NOW() where db_schema_version < 1466;

ALTER TABLE vicidial_recording_access_log convert to character set utf8 collate utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1467',db_schema_update_date=NOW() where db_schema_version < 1467;

ALTER TABLE vicidial_campaigns MODIFY agent_display_fields VARCHAR(100) default '';

UPDATE system_settings SET db_schema_version='1468',db_schema_update_date=NOW() where db_schema_version < 1468;

ALTER TABLE system_settings ADD user_new_lead_limit ENUM('1','0') default '0';

ALTER TABLE vicidial_lists ADD user_new_lead_limit SMALLINT(5) default '-1';

CREATE TABLE vicidial_user_list_new_lead (
user VARCHAR(20) NOT NULL,
list_id BIGINT(14) UNSIGNED default '999',
user_override SMALLINT(5) default '-1',
new_count MEDIUMINT(8) UNSIGNED default '0',
unique index userlistnew (user, list_id)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1469',db_schema_update_date=NOW() where db_schema_version < 1469;

ALTER TABLE vicidial_screen_labels MODIFY label_title VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_first_name VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_middle_initial VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_last_name VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_address1 VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_address2 VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_address3 VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_city VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_state VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_province VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_postal_code VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_vendor_lead_code VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_gender VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_phone_number VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_phone_code VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_alt_phone VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_security_phrase VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_email VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels MODIFY label_comments VARCHAR(60) default '';

ALTER TABLE system_settings MODIFY label_title VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_first_name VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_middle_initial VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_last_name VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_address1 VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_address2 VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_address3 VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_city VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_state VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_province VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_postal_code VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_vendor_lead_code VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_gender VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_phone_number VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_phone_code VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_alt_phone VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_security_phrase VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_email VARCHAR(60) default '';
ALTER TABLE system_settings MODIFY label_comments VARCHAR(60) default '';

ALTER TABLE vicidial_campaigns ADD allow_required_fields ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1470',db_schema_update_date=NOW() where db_schema_version < 1470;

CREATE INDEX vtl_event_epoch on vicidial_timeclock_log (event_epoch);

UPDATE system_settings SET db_schema_version='1471',db_schema_update_date=NOW() where db_schema_version < 1471;

ALTER TABLE vicidial_user_groups ADD agent_xfer_park_3way ENUM('Y','N') default 'Y';

ALTER TABLE system_settings ADD agent_xfer_park_3way ENUM('1','0') default '0';

UPDATE system_settings SET db_schema_version='1472',db_schema_update_date=NOW() where db_schema_version < 1472;

ALTER TABLE system_settings ADD rec_prompt_count INT(9) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1473',db_schema_update_date=NOW() where db_schema_version < 1473;

ALTER TABLE vicidial_users ADD user_new_lead_limit SMALLINT(5) default '-1';

UPDATE system_settings SET db_schema_version='1474',db_schema_update_date=NOW() where db_schema_version < 1474;

ALTER TABLE system_settings ADD agent_soundboards ENUM('1','0') default '0';
ALTER TABLE system_settings ADD web_loader_phone_length VARCHAR(10) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1475',db_schema_update_date=NOW() where db_schema_version < 1475;

ALTER TABLE system_settings ADD agent_script VARCHAR(50) default 'vicidial.php';

INSERT INTO vicidial_screen_colors VALUES ('default_grey_agent','default grey agent','Y','FFFFFF','cccccc','E6E6E6','E6E6E6','E6E6E6','E6E6E6','E6E6E6','E6E6E6','E6E6E6','E6E6E6','---ALL---','DEFAULTAGENT.png');

UPDATE system_settings SET db_schema_version='1476',db_schema_update_date=NOW() where db_schema_version < 1476;

ALTER TABLE vicidial_avatars ADD soundboard_layout VARCHAR(40) default 'default';
ALTER TABLE vicidial_avatars ADD columns_limit SMALLINT(5) default '5';

ALTER TABLE vicidial_avatar_audio ADD button_type VARCHAR(40) default 'button';
ALTER TABLE vicidial_avatar_audio ADD font_size VARCHAR(3) default '2';

ALTER TABLE vicidial_scripts MODIFY script_id VARCHAR(20) NOT NULL;

ALTER TABLE vicidial_campaigns MODIFY campaign_script VARCHAR(20);
ALTER TABLE vicidial_campaigns MODIFY qc_script VARCHAR(20);

ALTER TABLE vicidial_lists MODIFY agent_script_override VARCHAR(20) default '';

ALTER TABLE vicidial_inbound_groups MODIFY ingroup_script VARCHAR(20);
ALTER TABLE vicidial_inbound_groups MODIFY qc_script VARCHAR(20);

UPDATE system_settings SET db_schema_version='1477',db_schema_update_date=NOW() where db_schema_version < 1477;

ALTER TABLE system_settings ADD vdad_debug_logging ENUM('1','0') default '0';

CREATE TABLE vicidial_vdad_log (
caller_code VARCHAR(30) NOT NULL,
server_ip VARCHAR(15),
call_date DATETIME,
epoch_micro VARCHAR(20) default '',
db_time DATETIME NOT NULL,
run_time VARCHAR(20) default '0',
vdad_script VARCHAR(40) NOT NULL,
lead_id INT(10) UNSIGNED default '0',
stage VARCHAR(100) default '',
step SMALLINT(5) UNSIGNED default '0',
index (caller_code),
KEY vdad_dbtime_key (db_time)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1478',db_schema_update_date=NOW() where db_schema_version < 1478;
