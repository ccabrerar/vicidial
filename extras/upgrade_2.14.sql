UPDATE system_settings SET db_schema_version='1479',version='2.13rc1tk',db_schema_update_date=NOW() where db_schema_version < 1479;

UPDATE system_settings SET db_schema_version='1480',version='2.14b0.5',db_schema_update_date=NOW() where db_schema_version < 1480;

ALTER TABLE vicidial_settings_containers MODIFY container_type VARCHAR(40) default 'OTHER';

UPDATE system_settings SET db_schema_version='1481',db_schema_update_date=NOW() where db_schema_version < 1481;

CREATE TABLE parked_channels_recent (
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
channel_group VARCHAR(30),
park_end_time DATETIME,
index (channel_group),
index (park_end_time)
) ENGINE=MyISAM;

ALTER TABLE vicidial_manager_chats ADD column internal_chat_type ENUM('AGENT', 'MANAGER') default 'MANAGER' after manager_chat_id;
ALTER TABLE vicidial_manager_chats_archive ADD column internal_chat_type ENUM('AGENT', 'MANAGER') default 'MANAGER' after manager_chat_id;

ALTER TABLE vicidial_manager_chat_log ADD column message_id VARCHAR(20) after message;
ALTER TABLE vicidial_manager_chat_log_archive ADD column message_id VARCHAR(20) after message;

UPDATE system_settings SET db_schema_version='1482',db_schema_update_date=NOW() where db_schema_version < 1482;

ALTER TABLE system_settings ADD agent_chat_screen_colors VARCHAR(20) default 'default';

UPDATE system_settings SET db_schema_version='1483',db_schema_update_date=NOW() where db_schema_version < 1483;

ALTER TABLE servers ADD conf_qualify ENUM('Y','N') default 'Y';

UPDATE system_settings SET db_schema_version='1484',db_schema_update_date=NOW() where db_schema_version < 1484;

ALTER TABLE vicidial_inbound_groups ADD populate_lead_province VARCHAR(20) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1485',db_schema_update_date=NOW() where db_schema_version < 1485;

ALTER TABLE vicidial_users ADD api_only_user ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1486',db_schema_update_date=NOW() where db_schema_version < 1486;

CREATE TABLE vicidial_api_urls (
api_id INT(9) UNSIGNED PRIMARY KEY NOT NULL,
api_date DATETIME,
remote_ip VARCHAR(50),
url MEDIUMTEXT
) ENGINE=MyISAM;

CREATE TABLE vicidial_api_urls_archive LIKE vicidial_api_urls;

UPDATE system_settings SET db_schema_version='1487',db_schema_update_date=NOW() where db_schema_version < 1487;

ALTER TABLE vicidial_campaigns ADD dead_to_dispo ENUM('ENABLED','DISABLED') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1488',db_schema_update_date=NOW() where db_schema_version < 1488;

ALTER TABLE vicidial_live_agents ADD external_lead_id INT(9) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1489',db_schema_update_date=NOW() where db_schema_version < 1489;

ALTER TABLE vicidial_inbound_groups ADD areacode_filter ENUM('DISABLED','ALLOW_ONLY','DROP_ONLY') default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD areacode_filter_seconds SMALLINT(5) default '10';
ALTER TABLE vicidial_inbound_groups ADD areacode_filter_action ENUM('CALLMENU','INGROUP','DID','MESSAGE','EXTENSION','VOICEMAIL','VMAIL_NO_INST') default 'MESSAGE';
ALTER TABLE vicidial_inbound_groups ADD areacode_filter_action_value VARCHAR(255) default 'nbdy-avail-to-take-call|vm-goodbye';
ALTER TABLE vicidial_inbound_groups MODIFY max_calls_action ENUM('DROP','AFTERHOURS','NO_AGENT_NO_QUEUE','AREACODE_FILTER') default 'NO_AGENT_NO_QUEUE';

CREATE TABLE vicidial_areacode_filters (
group_id VARCHAR(20) NOT NULL,
areacode VARCHAR(6) NOT NULL,
index(group_id)
) ENGINE=MyISAM;

ALTER TABLE vicidial_closer_log MODIFY term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','HOLDRECALLXFER','HOLDTIME','NOAGENT','NONE','MAXCALLS','ACFILTER','CLOSETIME') default 'NONE';
ALTER TABLE vicidial_closer_log_archive MODIFY term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','HOLDRECALLXFER','HOLDTIME','NOAGENT','NONE','MAXCALLS','ACFILTER','CLOSETIME') default 'NONE';

UPDATE system_settings SET db_schema_version='1490',db_schema_update_date=NOW() where db_schema_version < 1490;

ALTER TABLE vicidial_lists_fields MODIFY field_required ENUM('Y','N','INBOUND_ONLY') default 'N';

UPDATE system_settings SET db_schema_version='1491',db_schema_update_date=NOW() where db_schema_version < 1491;

ALTER TABLE system_settings ADD enable_auto_reports ENUM('1','0') default '0';

ALTER TABLE vicidial_users ADD modify_auto_reports ENUM('1','0') default '0';

CREATE TABLE vicidial_automated_reports (
report_id VARCHAR(30) UNIQUE NOT NULL,
report_name VARCHAR(100),
report_last_run DATETIME,
report_last_length SMALLINT(5) default '0',
report_server VARCHAR(30) default 'active_voicemail_server',
report_times VARCHAR(100) default '',
report_weekdays VARCHAR(7) default '',
report_monthdays VARCHAR(100) default '',
report_destination ENUM('EMAIL','FTP') default 'EMAIL',
email_from VARCHAR(255) default '',
email_to VARCHAR(255) default '',
email_subject VARCHAR(255) default '',
ftp_server VARCHAR(255) default '',
ftp_user VARCHAR(255) default '',
ftp_pass VARCHAR(255) default '',
ftp_directory VARCHAR(255) default '',
report_url TEXT,
run_now_trigger ENUM('N','Y') default 'N',
active ENUM('N','Y') default 'N',
user_group VARCHAR(20) default '---ALL---',
index (report_times),
index (run_now_trigger)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1492',db_schema_update_date=NOW() where db_schema_version < 1492;

ALTER TABLE vicidial_campaigns ADD agent_xfer_validation ENUM('N','Y') default 'N';

ALTER TABLE vicidial_inbound_groups ADD populate_state_areacode ENUM('DISABLED','NEW_LEAD_ONLY','OVERWRITE_ALWAYS') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1493',db_schema_update_date=NOW() where db_schema_version < 1493;

ALTER TABLE vicidial_campaigns MODIFY inbound_queue_no_dial ENUM('DISABLED','ENABLED','ALL_SERVERS','ENABLED_WITH_CHAT','ALL_SERVERS_WITH_CHAT') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1494',db_schema_update_date=NOW() where db_schema_version < 1494;

ALTER TABLE phones ADD conf_qualify ENUM('Y','N') default 'Y';

UPDATE system_settings SET db_schema_version='1495',db_schema_update_date=NOW() where db_schema_version < 1495;

ALTER TABLE system_settings ADD enable_pause_code_limits ENUM('1','0') default '0';

ALTER TABLE vicidial_pause_codes ADD time_limit SMALLINT(5) UNSIGNED default '65000';

UPDATE system_settings SET db_schema_version='1496',db_schema_update_date=NOW() where db_schema_version < 1496;

ALTER TABLE system_settings ADD enable_drop_lists ENUM('0','1','2') default '0';

CREATE TABLE vicidial_drop_lists (
dl_id VARCHAR(30) UNIQUE NOT NULL,
dl_name VARCHAR(100),
last_run DATETIME,
dl_server VARCHAR(30) default 'active_voicemail_server',
dl_times VARCHAR(120) default '',
dl_weekdays VARCHAR(7) default '',
dl_monthdays VARCHAR(100) default '',
drop_statuses VARCHAR(255) default ' DROP -',
list_id BIGINT(14) UNSIGNED,
duplicate_check VARCHAR(50) default 'NONE',
run_now_trigger ENUM('N','Y') default 'N',
active ENUM('N','Y') default 'N',
user_group VARCHAR(20) default '---ALL---',
closer_campaigns TEXT,
index (dl_times),
index (run_now_trigger)
) ENGINE=MyISAM;

CREATE TABLE vicidial_drop_log (
uniqueid VARCHAR(50) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
drop_date DATETIME NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
phone_code VARCHAR(10),
phone_number VARCHAR(18),
campaign_id VARCHAR(20) NOT NULL,
status VARCHAR(6) NOT NULL,
drop_processed ENUM('N','Y','U') default 'N',
index(drop_date),
index(drop_processed)
) ENGINE=MyISAM;

CREATE TABLE vicidial_drop_log_archive LIKE vicidial_drop_log; 
DROP INDEX drop_date on vicidial_drop_log_archive;
CREATE UNIQUE INDEX vicidial_drop_log_archive_key on vicidial_drop_log_archive(drop_date, uniqueid);

UPDATE system_settings SET db_schema_version='1497',db_schema_update_date=NOW() where db_schema_version < 1497;

ALTER TABLE vicidial_campaigns MODIFY use_custom_cid ENUM('Y','N','AREACODE','USER_CUSTOM_1','USER_CUSTOM_2','USER_CUSTOM_3','USER_CUSTOM_4','USER_CUSTOM_5') default 'N';

UPDATE system_settings SET db_schema_version='1498',db_schema_update_date=NOW() where db_schema_version < 1498;

ALTER TABLE system_settings ADD allow_ip_lists ENUM('0','1','2') default '0';
ALTER TABLE system_settings ADD system_ip_blacklist VARCHAR(30) default '';

ALTER TABLE vicidial_users ADD modify_ip_lists ENUM('1','0') default '0';
ALTER TABLE vicidial_users ADD ignore_ip_list ENUM('1','0') default '0';

ALTER TABLE vicidial_user_groups ADD admin_ip_list VARCHAR(30) default '';
ALTER TABLE vicidial_user_groups ADD agent_ip_list VARCHAR(30) default '';
ALTER TABLE vicidial_user_groups ADD api_ip_list VARCHAR(30) default '';

CREATE TABLE vicidial_ip_lists (
ip_list_id VARCHAR(30) UNIQUE NOT NULL,
ip_list_name VARCHAR(100),
active ENUM('N','Y') default 'N',
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM;

CREATE TABLE vicidial_ip_list_entries (
ip_list_id VARCHAR(30) NOT NULL,
ip_address VARCHAR(45) NOT NULL,
index(ip_list_id),
index(ip_address)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1499',db_schema_update_date=NOW() where db_schema_version < 1499;

ALTER TABLE vicidial_drop_lists ADD dl_minutes MEDIUMINT(6) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1500',db_schema_update_date=NOW() where db_schema_version < 1500;

ALTER TABLE vicidial_campaigns ADD ready_max_logout MEDIUMINT(7) default '0';

ALTER TABLE vicidial_users ADD ready_max_logout MEDIUMINT(7) default '-1';

ALTER TABLE servers ADD routing_prefix VARCHAR(10) default '13';

UPDATE system_settings SET db_schema_version='1501',db_schema_update_date=NOW() where db_schema_version < 1501;

CREATE TABLE cid_channels_recent (
caller_id_name VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL,
connected_line_name VARCHAR(30) COLLATE utf8_unicode_ci NOT NULL,
server_ip VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT NULL,
call_date DATETIME,
channel VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT '',
dest_channel VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT '',
linkedid VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT '',
dest_uniqueid VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT '',
uniqueid VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT '',
index(call_date)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1502',db_schema_update_date=NOW() where db_schema_version < 1502;

ALTER TABLE vicidial_campaigns ADD callback_display_days SMALLINT(3) default '0';

UPDATE system_settings SET db_schema_version='1503',db_schema_update_date=NOW() where db_schema_version < 1503;

ALTER TABLE vicidial_campaigns ADD three_way_record_stop ENUM('Y','N') default 'N';
ALTER TABLE vicidial_campaigns ADD hangup_xfer_record_start ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1504',db_schema_update_date=NOW() where db_schema_version < 1504;

CREATE TABLE vicidial_rt_monitor_log (
manager_user VARCHAR(20) NOT NULL,
manager_server_ip VARCHAR(15) NOT NULL,
manager_phone VARCHAR(20) NOT NULL,
manager_ip VARCHAR(15),
agent_user VARCHAR(20),
agent_server_ip VARCHAR(15),
agent_status VARCHAR(10),
agent_session VARCHAR(10),
lead_id INT(9) UNSIGNED,
campaign_id VARCHAR(8),
caller_code VARCHAR(20),
monitor_start_time DATETIME,
monitor_end_time DATETIME,
monitor_sec INT(9) UNSIGNED default '0',
monitor_type ENUM('LISTEN','BARGE','HIJACK','WHISPER') default 'LISTEN',
index (manager_user),
index (agent_user),
unique index (caller_code),
index (monitor_start_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_rt_monitor_log_archive LIKE vicidial_rt_monitor_log; 

UPDATE system_settings SET db_schema_version='1505',db_schema_update_date=NOW() where db_schema_version < 1505;

ALTER TABLE system_settings ADD agent_push_events ENUM('0','1') default '0';
ALTER TABLE system_settings ADD agent_push_url TEXT;

UPDATE system_settings SET db_schema_version='1506',db_schema_update_date=NOW() where db_schema_version < 1506;

ALTER TABLE system_settings ADD hide_inactive_lists ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1507',db_schema_update_date=NOW() where db_schema_version < 1507;

ALTER TABLE phones MODIFY pass VARCHAR(100);
ALTER TABLE phones MODIFY login_pass VARCHAR(100);

ALTER TABLE vicidial_users MODIFY pass VARCHAR(100) NOT NULL;
ALTER TABLE vicidial_users MODIFY phone_pass VARCHAR(100);
ALTER TABLE vicidial_users MODIFY pass_hash VARCHAR(500) default '';

ALTER TABLE vicidial_avatars MODIFY avatar_api_pass VARCHAR(100) default '';

ALTER TABLE system_settings MODIFY default_phone_registration_password VARCHAR(100) default 'test';
ALTER TABLE system_settings MODIFY default_phone_login_password VARCHAR(100) default 'test';
ALTER TABLE system_settings MODIFY default_server_password VARCHAR(100) default 'test';

UPDATE system_settings SET db_schema_version='1508',db_schema_update_date=NOW() where db_schema_version < 1508;

ALTER TABLE user_call_log ADD xfer_hungup VARCHAR(20) default '';
ALTER TABLE user_call_log ADD xfer_hungup_datetime DATETIME;

UPDATE system_settings SET db_schema_version='1509',db_schema_update_date=NOW() where db_schema_version < 1509;

CREATE TABLE vicidial_campaign_hour_counts (
campaign_id VARCHAR(8),
date_hour DATETIME,
next_hour DATETIME,
last_update DATETIME,
type VARCHAR(8) default 'CALLS',
calls MEDIUMINT(6) UNSIGNED default '0',
hr TINYINT(2) default '0',
index (campaign_id),
index (date_hour),
unique index vchc_camp_hour (campaign_id, date_hour, type)
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_hour_counts_archive LIKE vicidial_campaign_hour_counts;

CREATE TABLE vicidial_carrier_hour_counts (
date_hour DATETIME,
next_hour DATETIME,
last_update DATETIME,
type VARCHAR(20) default 'ANSWERED',
calls MEDIUMINT(6) UNSIGNED default '0',
hr TINYINT(2) default '0',
index (date_hour),
unique index vclhc_hour (date_hour, type)
) ENGINE=MyISAM;

CREATE TABLE vicidial_carrier_hour_counts_archive LIKE vicidial_carrier_hour_counts;

UPDATE system_settings SET db_schema_version='1510',db_schema_update_date=NOW() where db_schema_version < 1510;

CREATE TABLE user_call_log_archive LIKE user_call_log;
ALTER TABLE user_call_log_archive MODIFY user_call_log_id INT(9) UNSIGNED NOT NULL;

UPDATE system_settings SET db_schema_version='1511',db_schema_update_date=NOW() where db_schema_version < 1511;

CREATE TABLE vicidial_inbound_survey_log (
uniqueid VARCHAR(50) NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
campaign_id VARCHAR(20) NOT NULL,
call_date DATETIME,
participate ENUM('N','Y') default 'N',
played ENUM('N','R','Y') default 'N',
dtmf_response VARCHAR(1) default '',
next_call_menu TEXT,
index (call_date),
index (lead_id),
index (uniqueid)
) ENGINE=MyISAM;

CREATE TABLE vicidial_inbound_survey_log_archive LIKE vicidial_inbound_survey_log;
CREATE UNIQUE INDEX visla_key on vicidial_inbound_survey_log_archive(uniqueid, call_date, campaign_id, lead_id);

ALTER TABLE vicidial_inbound_groups ADD inbound_survey ENUM('DISABLED','ENABLED') default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD inbound_survey_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD inbound_survey_accept_digit VARCHAR(1) default '';
ALTER TABLE vicidial_inbound_groups ADD inbound_survey_question_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD inbound_survey_callmenu TEXT;

UPDATE system_settings SET db_schema_version='1512',db_schema_update_date=NOW() where db_schema_version < 1512;

ALTER TABLE system_settings ADD allow_manage_active_lists ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1513',db_schema_update_date=NOW() where db_schema_version < 1513;

ALTER TABLE vicidial_automated_reports ADD filename_override VARCHAR(255) default '';
ALTER TABLE vicidial_automated_reports MODIFY report_times VARCHAR(255) default '';

UPDATE system_settings SET db_schema_version='1514',db_schema_update_date=NOW() where db_schema_version < 1514;

CREATE INDEX vle_lead_id on vicidial_log_extended(lead_id);

ALTER TABLE vicidial_xfer_log ADD front_uniqueid VARCHAR(50) default '';
ALTER TABLE vicidial_xfer_log ADD close_uniqueid VARCHAR(50) default '';

CREATE TABLE vicidial_xfer_log_archive LIKE vicidial_xfer_log; 
ALTER TABLE vicidial_xfer_log_archive MODIFY xfercallid INT(9) UNSIGNED NOT NULL;

UPDATE system_settings SET db_schema_version='1515',db_schema_update_date=NOW() where db_schema_version < 1515;

ALTER TABLE system_settings ADD expired_lists_inactive ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1516',db_schema_update_date=NOW() where db_schema_version < 1516;

ALTER TABLE system_settings ADD did_system_filter ENUM('0','1') default '0';

CREATE TABLE vicidial_dnccom_scrub_log (
phone_number VARCHAR(18),
scrub_date DATETIME NOT NULL,
flag_invalid ENUM('','0','1') default '',
flag_dnc ENUM('','0','1') default '',
flag_litigator ENUM('','0','1') default '',
full_response VARCHAR(255) default '',
index(phone_number),
index(scrub_date)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1517',db_schema_update_date=NOW() where db_schema_version < 1517;

ALTER TABLE vicidial_dnccom_scrub_log ADD flag_projdnc ENUM('','0','1') default '' AFTER flag_dnc;

UPDATE system_settings SET db_schema_version='1518',db_schema_update_date=NOW() where db_schema_version < 1518;

ALTER TABLE vicidial_campaigns MODIFY extension_appended_cidname ENUM('Y','N','Y_USER','Y_WITH_CAMPAIGN','Y_USER_WITH_CAMPAIGN') default 'N';

ALTER TABLE vicidial_inbound_groups MODIFY extension_appended_cidname ENUM('Y','N','Y_USER','Y_WITH_CAMPAIGN','Y_USER_WITH_CAMPAIGN') default 'N';

ALTER TABLE vicidial_custom_reports MODIFY path_name TEXT COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE vicidial_custom_reports ADD custom_variables TEXT COLLATE utf8_unicode_ci DEFAULT NULL AFTER path_name;

UPDATE system_settings SET db_schema_version='1519',db_schema_update_date=NOW() where db_schema_version < 1519;

ALTER TABLE server_performance ADD disk_reads MEDIUMINT(7);
ALTER TABLE server_performance ADD disk_writes MEDIUMINT(7);

UPDATE system_settings SET db_schema_version='1520',db_schema_update_date=NOW() where db_schema_version < 1520;

ALTER TABLE vicidial_lists ADD inbound_list_script_override VARCHAR(20);

UPDATE system_settings SET db_schema_version='1521',db_schema_update_date=NOW() where db_schema_version < 1521;

ALTER TABLE phones ADD webphone_layout VARCHAR(255) default '';

ALTER TABLE vicidial_user_groups ADD webphone_layout VARCHAR(255) default '';

UPDATE system_settings SET db_schema_version='1522',db_schema_update_date=NOW() where db_schema_version < 1522;

ALTER TABLE vicidial_campaigns ADD scheduled_callbacks_email_alert ENUM('Y', 'N') default 'N';

ALTER TABLE vicidial_callbacks ADD email_alert datetime;
ALTER TABLE vicidial_callbacks ADD email_result ENUM('SENT','FAILED','NOT AVAILABLE');

ALTER TABLE vicidial_callbacks_archive ADD email_alert datetime;
ALTER TABLE vicidial_callbacks_archive ADD email_result ENUM('SENT','FAILED','NOT AVAILABLE');

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('AGENT_CALLBACK_EMAIL ','Scheduled callback email alert settings','OTHER','---ALL---','; sending email address\r\nemail_from => vicidial@local.server\r\n\r\n; subject of the email\r\nemail_subject => Scheduled callback alert for --A--agent_name--B--\r\n\r\nemail_body_begin => \r\nThis is a reminder that you have a scheduled callback right now for the following lead:\r\n\r\nName: --A--first_name--B-- --A--last_name--B--\r\nPhone: --A--phone_number--B--\r\nAlt. phone: --A--alt_phone--B--\r\nEmail: --A--email--B--\r\nCB Comments: --A--callback_comments--B--\r\nLead Comments: --A--comments--B--\r\n\r\nPlease don\'t respond to this, fool.\r\n\r\nemail_body_end');

UPDATE system_settings SET db_schema_version='1523',db_schema_update_date=NOW() where db_schema_version < 1523;

ALTER TABLE vicidial_lists_fields ADD field_duplicate ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1524',db_schema_update_date=NOW() where db_schema_version < 1524;

ALTER TABLE vicidial_campaigns ADD max_inbound_calls_outcome ENUM('DEFAULT','ALLOW_AGENTDIRECT','ALLOW_MI_PAUSE','ALLOW_AGENTDIRECT_AND_MI_PAUSE') default 'DEFAULT';

UPDATE system_settings SET db_schema_version='1525',db_schema_update_date=NOW() where db_schema_version < 1525;

ALTER TABLE vicidial_campaigns ADD manual_auto_next_options ENUM('DEFAULT','PAUSE_NO_COUNT') default 'DEFAULT';

UPDATE system_settings SET db_schema_version='1526',db_schema_update_date=NOW() where db_schema_version < 1526;

ALTER TABLE vicidial_campaigns ADD agent_screen_time_display VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1527',db_schema_update_date=NOW() where db_schema_version < 1527;

ALTER TABLE vicidial_campaigns MODIFY get_call_launch ENUM('NONE','SCRIPT','SCRIPTTWO','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM','PREVIEW_WEBFORM','PREVIEW_WEBFORMTWO','PREVIEW_WEBFORMTHREE','PREVIEW_SCRIPT','PREVIEW_SCRIPTTWO','PREVIEW_FORM') default 'NONE';

UPDATE system_settings SET db_schema_version='1528',db_schema_update_date=NOW() where db_schema_version < 1528;

CREATE TABLE vicidial_ingroup_hour_counts (
group_id VARCHAR(20),
date_hour DATETIME,
next_hour DATETIME,
last_update DATETIME,
type VARCHAR(22) default 'CALLS',
calls INT(9) UNSIGNED default '0',
hr TINYINT(2) default '0',
index (group_id),
index (date_hour),
unique index vihc_ingr_hour (group_id, date_hour, type)
) ENGINE=MyISAM;

CREATE TABLE vicidial_ingroup_hour_counts_archive LIKE vicidial_ingroup_hour_counts;

ALTER TABLE vicidial_lists ADD default_xfer_group VARCHAR(20) default '---NONE---';

UPDATE system_settings SET db_schema_version='1529',db_schema_update_date=NOW() where db_schema_version < 1529;

ALTER TABLE vicidial_campaigns ADD next_dial_my_callbacks ENUM('DISABLED','ENABLED') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1530',db_schema_update_date=NOW() where db_schema_version < 1530;

ALTER TABLE system_settings ADD anyone_callback_inactive_lists ENUM('default','NO_ADD_TO_HOPPER','KEEP_IN_HOPPER') default 'default';

UPDATE system_settings SET db_schema_version='1531',db_schema_update_date=NOW() where db_schema_version < 1531;

CREATE TABLE vicidial_process_log (
serial_id VARCHAR(20) NOT NULL,
run_time DATETIME,
run_sec INT,
server_ip VARCHAR(15) NOT NULL,
script VARCHAR(100),
process VARCHAR(100),
output_lines MEDIUMTEXT,
index (serial_id),
index (run_time)
) ENGINE=MyISAM;

DELETE from cid_channels_recent;

UPDATE system_settings SET db_schema_version='1532',db_schema_update_date=NOW() where db_schema_version < 1532;

ALTER TABLE vicidial_process_trigger_log MODIFY trigger_results MEDIUMTEXT;

ALTER TABLE vicidial_campaigns ADD inbound_no_agents_no_dial_container VARCHAR(40) default '---DISABLED---';
ALTER TABLE vicidial_campaigns ADD inbound_no_agents_no_dial_threshold SMALLINT(5) default '0';

ALTER TABLE vicidial_settings_containers MODIFY container_type VARCHAR(40) default 'OTHER';

UPDATE system_settings SET db_schema_version='1533',db_schema_update_date=NOW() where db_schema_version < 1533;

CREATE TABLE vicidial_inbound_callback_queue (
icbq_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
icbq_date DATETIME,
icbq_status VARCHAR(10),
icbq_phone_number VARCHAR(20),
icbq_phone_code VARCHAR(10),
icbq_nextday_choice ENUM('Y','N','U') default 'U',
lead_id INT(9) UNSIGNED NOT NULL,
group_id VARCHAR(20) NOT NULL,
queue_priority TINYINT(2) default '0',
call_date DATETIME,
gmt_offset_now DECIMAL(4,2) DEFAULT '0.00',
modify_date TIMESTAMP,
index (icbq_status)
) ENGINE=MyISAM;

CREATE TABLE vicidial_inbound_callback_queue_archive LIKE vicidial_inbound_callback_queue; 
ALTER TABLE vicidial_inbound_callback_queue_archive MODIFY icbq_id INT(9) UNSIGNED NOT NULL;

ALTER TABLE vicidial_inbound_groups ADD icbq_expiration_hours SMALLINT(5) default '96';
ALTER TABLE vicidial_inbound_groups ADD closing_time_action VARCHAR(30) default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD closing_time_now_trigger ENUM('Y','N') default 'N';
ALTER TABLE vicidial_inbound_groups ADD closing_time_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD closing_time_end_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD closing_time_lead_reset ENUM('Y','N') default 'N';
ALTER TABLE vicidial_inbound_groups ADD closing_time_option_exten VARCHAR(20) default '8300';
ALTER TABLE vicidial_inbound_groups ADD closing_time_option_callmenu VARCHAR(50) default '';
ALTER TABLE vicidial_inbound_groups ADD closing_time_option_voicemail VARCHAR(20) default '';
ALTER TABLE vicidial_inbound_groups ADD closing_time_option_xfer_group VARCHAR(20) default '---NONE---';
ALTER TABLE vicidial_inbound_groups ADD closing_time_option_callback_list_id BIGINT(14) UNSIGNED default '999';
ALTER TABLE vicidial_inbound_groups ADD add_lead_timezone ENUM('SERVER','PHONE_CODE_AREACODE') default 'SERVER';
ALTER TABLE vicidial_inbound_groups ADD icbq_call_time_id VARCHAR(20) default '24hours';
ALTER TABLE vicidial_inbound_groups ADD icbq_dial_filter VARCHAR(50) default 'NONE';

ALTER TABLE vicidial_closer_log MODIFY term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','HOLDRECALLXFER','HOLDTIME','NOAGENT','NONE','MAXCALLS','ACFILTER','CLOSETIME') default 'NONE';
ALTER TABLE vicidial_closer_log_archive MODIFY term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','HOLDRECALLXFER','HOLDTIME','NOAGENT','NONE','MAXCALLS','ACFILTER','CLOSETIME') default 'NONE';

UPDATE system_settings SET db_schema_version='1534',db_schema_update_date=NOW() where db_schema_version < 1534;

ALTER TABLE system_settings ADD enable_gdpr_download_deletion ENUM('0','1','2') default '0';

ALTER TABLE vicidial_users ADD export_gdpr_leads ENUM('0','1','2') default '0';

CREATE TABLE recording_log_deletion_queue (
recording_id INT(9) UNSIGNED PRIMARY KEY, 
lead_id int(10) UNSIGNED, 
filename VARCHAR(100), 
location VARCHAR(255), 
date_queued DATETIME, 
date_deleted DATETIME,
index (date_deleted)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1535',db_schema_update_date=NOW() where db_schema_version < 1535;

CREATE TABLE vicidial_cid_groups (
cid_group_id VARCHAR(20) PRIMARY KEY NOT NULL,
cid_group_notes VARCHAR(255) default '',
cid_group_type ENUM('AREACODE','STATE','NONE') default 'AREACODE',
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE vicidial_campaign_cid_areacodes MODIFY campaign_id VARCHAR(20) NOT NULL;

ALTER TABLE vicidial_campaigns ADD cid_group_id VARCHAR(20) default '---DISABLED---';

UPDATE system_settings SET db_schema_version='1536',db_schema_update_date=NOW() where db_schema_version < 1536;

ALTER TABLE vicidial_campaigns ADD pause_max_dispo VARCHAR(6) default 'PAUSMX';

UPDATE system_settings SET db_schema_version='1537',db_schema_update_date=NOW() where db_schema_version < 1537;

ALTER TABLE vicidial_inbound_groups MODIFY no_agent_no_queue ENUM('N','Y','NO_PAUSED','NO_READY') default 'N';

UPDATE system_settings SET db_schema_version='1538',db_schema_update_date=NOW() where db_schema_version < 1538;

ALTER TABLE vicidial_campaigns ADD script_top_dispo ENUM('Y', 'N') default 'N';

UPDATE system_settings SET db_schema_version='1539',db_schema_update_date=NOW() where db_schema_version < 1539;

ALTER TABLE system_settings ADD source_id_display ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1540',db_schema_update_date=NOW() where db_schema_version < 1540;

ALTER TABLE vicidial_pause_codes ADD require_mgr_approval ENUM('NO','YES') default 'NO';

ALTER TABLE vicidial_users ADD pause_code_approval ENUM('1','0') default '0';

CREATE TABLE vicidial_agent_function_log (
agent_function_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
agent_log_id INT(9) UNSIGNED,
user VARCHAR(20),
function VARCHAR(20),
event_time DATETIME,
lead_id INT(9) UNSIGNED,
campaign_id VARCHAR(8),
user_group VARCHAR(20),
caller_code VARCHAR(30) default '',
comments VARCHAR(40) default '',
stage VARCHAR(40) default '',
uniqueid VARCHAR(20) default '',
index (event_time),
index (caller_code),
index (user),
index (lead_id),
index (stage)
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_function_log_archive LIKE vicidial_agent_function_log;
ALTER TABLE vicidial_agent_function_log_archive MODIFY agent_function_log_id INT(9) UNSIGNED NOT NULL;

UPDATE system_settings SET db_schema_version='1541',db_schema_update_date=NOW() where db_schema_version < 1541;

ALTER TABLE vicidial_inbound_groups ADD populate_lead_source VARCHAR(20) default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD populate_lead_vendor VARCHAR(20) default 'INBOUND_NUMBER';

UPDATE system_settings SET db_schema_version='1542',db_schema_update_date=NOW() where db_schema_version < 1542;

ALTER TABLE vicidial_inbound_groups ADD park_file_name VARCHAR(100) default '';

UPDATE system_settings SET db_schema_version='1543',db_schema_update_date=NOW() where db_schema_version < 1543;

ALTER TABLE vicidial_lists_fields MODIFY field_type ENUM('TEXT','AREA','SELECT','MULTI','RADIO','CHECKBOX','DATE','TIME','DISPLAY','SCRIPT','HIDDEN','READONLY','HIDEBLOB','SWITCH','SOURCESELECT','BUTTON') default 'TEXT';

UPDATE system_settings SET db_schema_version='1544',db_schema_update_date=NOW() where db_schema_version < 1544;

ALTER TABLE system_settings ADD help_modification_date VARCHAR(20) default '0';

CREATE TABLE help_documentation (
help_id varchar(100) PRIMARY KEY COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
help_title text COLLATE utf8_unicode_ci,
help_text text COLLATE utf8_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1545',db_schema_update_date=NOW() where db_schema_version < 1545;

ALTER TABLE vicidial_campaign_agents ADD hopper_calls_today SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_campaign_agents ADD hopper_calls_hour SMALLINT(5) UNSIGNED default '0';

ALTER TABLE vicidial_users ADD max_hopper_calls SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_users ADD max_hopper_calls_hour SMALLINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1546',db_schema_update_date=NOW() where db_schema_version < 1546;

ALTER TABLE vicidial_inbound_groups ADD waiting_call_url_on TEXT;
ALTER TABLE vicidial_inbound_groups ADD waiting_call_url_off TEXT;
ALTER TABLE vicidial_inbound_groups ADD waiting_call_count SMALLINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1547',db_schema_update_date=NOW() where db_schema_version < 1547;

ALTER TABLE vicidial_inbound_groups ADD enter_ingroup_url TEXT;

UPDATE system_settings SET db_schema_version='1548',db_schema_update_date=NOW() where db_schema_version < 1548;

ALTER TABLE vicidial_campaigns ADD dead_trigger_seconds SMALLINT(5) default '0';
ALTER TABLE vicidial_campaigns ADD dead_trigger_action ENUM('DISABLED','AUDIO','URL','AUDIO_AND_URL') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD dead_trigger_repeat ENUM('NO','REPEAT_ALL','REPEAT_AUDIO','REPEAT_URL') default 'NO';
ALTER TABLE vicidial_campaigns ADD dead_trigger_filename TEXT;
ALTER TABLE vicidial_campaigns ADD dead_trigger_url TEXT;

CREATE TABLE vicidial_ccc_log (
call_date DATETIME,
remote_call_id VARCHAR(30) default '',
local_call_id VARCHAR(30) default '',
lead_id INT(9) UNSIGNED,
uniqueid VARCHAR(20) default '',
channel VARCHAR(100) default '',
server_ip VARCHAR(60) NOT NULL,
list_id BIGINT(14) UNSIGNED,
container_id VARCHAR(40) default '',
remote_lead_id INT(9) UNSIGNED,
index (call_date),
index (local_call_id),
index (lead_id)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1549',db_schema_update_date=NOW() where db_schema_version < 1549;

CREATE TABLE vicidial_did_log_archive LIKE vicidial_did_log;
CREATE UNIQUE INDEX vdidla_key on vicidial_did_log_archive(uniqueid, call_date, server_ip);

ALTER TABLE vicidial_inbound_groups ADD cid_cb_confirm_number VARCHAR(20) default 'NO';
ALTER TABLE vicidial_inbound_groups ADD cid_cb_invalid_filter_phone_group VARCHAR(20) default '';
ALTER TABLE vicidial_inbound_groups ADD cid_cb_valid_length VARCHAR(30) default '10';
ALTER TABLE vicidial_inbound_groups ADD cid_cb_valid_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD cid_cb_confirmed_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD cid_cb_enter_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD cid_cb_you_entered_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD cid_cb_press_to_confirm_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD cid_cb_invalid_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD cid_cb_reenter_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD cid_cb_error_filename TEXT;

UPDATE system_settings SET db_schema_version='1550',db_schema_update_date=NOW() where db_schema_version < 1550;

ALTER TABLE system_settings ADD agent_logout_link ENUM('0','1','2','3','4') default '1';

UPDATE system_settings SET db_schema_version='1551',db_schema_update_date=NOW() where db_schema_version < 1551;

ALTER TABLE vicidial_campaigns ADD scheduled_callbacks_force_dial ENUM('N','Y') default 'N';

UPDATE system_settings SET db_schema_version='1552',db_schema_update_date=NOW() where db_schema_version < 1552;

ALTER TABLE vicidial_campaigns ADD scheduled_callbacks_auto_reschedule VARCHAR(10) default 'DISABLED';

CREATE TABLE vicidial_recent_ascb_calls (
call_date DATETIME,
callback_date DATETIME,
callback_id INT(9) UNSIGNED default '0',
caller_code VARCHAR(30) default '',
lead_id INT(9) UNSIGNED,
server_ip VARCHAR(60) NOT NULL,
orig_status VARCHAR(6) default 'CALLBK',
reschedule VARCHAR(10) default '',
list_id BIGINT(14) UNSIGNED,
rescheduled ENUM('U','P','Y','N') default 'U',
unique index (caller_code),
index (call_date),
index (lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_recent_ascb_calls_archive LIKE vicidial_recent_ascb_calls;

UPDATE system_settings SET db_schema_version='1553',db_schema_update_date=NOW() where db_schema_version < 1553;

ALTER TABLE vicidial_phone_codes ADD php_tz VARCHAR(100) default '';

ALTER TABLE vicidial_campaigns ADD scheduled_callbacks_timezones_container VARCHAR(40) default 'DISABLED';

ALTER TABLE vicidial_callbacks ADD customer_timezone VARCHAR(100) default '';
ALTER TABLE vicidial_callbacks ADD customer_timezone_diff VARCHAR(6) default '';
ALTER TABLE vicidial_callbacks ADD customer_time DATETIME;

ALTER TABLE vicidial_callbacks_archive ADD customer_timezone VARCHAR(100) default '';
ALTER TABLE vicidial_callbacks_archive ADD customer_timezone_diff VARCHAR(6) default '';
ALTER TABLE vicidial_callbacks_archive ADD customer_time DATETIME;

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('TIMEZONES_USA','USA Timezone List','TIMEZONE_LIST','---ALL---','USA,AST,N,Atlantic Time Zone\nUSA,EST,Y,Eastern Time Zone\nUSA,CST,Y,Central Time Zone\nUSA,MST,Y,Mountain Time Zone\nUSA,MST,N,Arizona Time Zone\nUSA,PST,Y,Pacific Time Zone\nUSA,AKST,Y,Alaska Time Zone\nUSA,HST,N,Hawaii Time Zone\n');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('TIMEZONES_CANADA','Canadian Timezone List','TIMEZONE_LIST','---ALL---','CAN,NST,Y,Newfoundland Time Zone\nCAN,AST,Y,Atlantic Time Zone\nCAN,EST,Y,Eastern Time Zone\nCAN,CST,Y,Central Time Zone\nCAN,CST,N,Saskatchewan Time Zone\nCAN,MST,Y,Mountain Time Zone\nCAN,PST,Y,Pacific Time Zone\n');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('TIMEZONES_AUSTRALIA','Australian Timezone List','TIMEZONE_LIST','---ALL---','AUS,AEST,Y,Eastern Australia Time Zone\nAUS,AEST,N,Queensland Time Zone\nAUS,ACST,Y,Central Australia Time Zone\nAUS,ACST,N,Northern Territory Time Zone\nAUS,AWST,N,Western Australia Time Zone\n');

UPDATE system_settings SET db_schema_version='1554',db_schema_update_date=NOW() where db_schema_version < 1554;

CREATE INDEX vicidial_email_group_key on vicidial_email_list(group_id);

UPDATE system_settings SET db_schema_version='1555',db_schema_update_date=NOW() where db_schema_version < 1555;

ALTER TABLE vicidial_ccc_log ADD remote_lead_id INT(9) UNSIGNED;

CREATE TABLE vicidial_ccc_log_archive LIKE vicidial_ccc_log;
CREATE UNIQUE INDEX ccc_unq_key on vicidial_ccc_log_archive(uniqueid, call_date, lead_id);

UPDATE system_settings SET db_schema_version='1556',db_schema_update_date=NOW() where db_schema_version < 1556;

ALTER TABLE vicidial_lists ADD daily_reset_limit SMALLINT(5) default '-1';
ALTER TABLE vicidial_lists ADD resets_today SMALLINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1557',db_schema_update_date=NOW() where db_schema_version < 1557;

ALTER TABLE vicidial_campaigns ADD three_way_volume_buttons VARCHAR(20) default 'ENABLED';

UPDATE system_settings SET db_schema_version='1558',db_schema_update_date=NOW() where db_schema_version < 1558;

ALTER TABLE vicidial_campaigns ADD callback_dnc ENUM('ENABLED','DISABLED') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1559',db_schema_update_date=NOW() where db_schema_version < 1559;

ALTER TABLE vicidial_campaigns MODIFY next_agent_call VARCHAR(40) default 'longest_wait_time';
ALTER TABLE vicidial_inbound_groups MODIFY next_agent_call VARCHAR(40) default 'longest_wait_time';

UPDATE system_settings SET db_schema_version='1560',db_schema_update_date=NOW() where db_schema_version < 1560;

ALTER TABLE servers ADD external_web_socket_url VARCHAR(255) default '';

UPDATE system_settings SET db_schema_version='1561',db_schema_update_date=NOW() where db_schema_version < 1561;

ALTER TABLE system_settings ADD manual_dial_validation ENUM('0','1','2','3','4') default '0';

ALTER TABLE vicidial_campaigns ADD manual_dial_validation ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1562',db_schema_update_date=NOW() where db_schema_version < 1562;

CREATE TABLE vicidial_sessions_recent (
lead_id INT(9) UNSIGNED,
server_ip VARCHAR(15) NOT NULL,
call_date DATETIME,
user VARCHAR(20),
campaign_id VARCHAR(20),
conf_exten VARCHAR(20),
call_type VARCHAR(1) default '',
index(lead_id),
index(call_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_sessions_recent_archive LIKE vicidial_sessions_recent;

UPDATE system_settings SET db_schema_version='1563',db_schema_update_date=NOW() where db_schema_version < 1563;

ALTER TABLE vicidial_inbound_groups ADD place_in_line_caller_number_filename TEXT;
ALTER TABLE vicidial_inbound_groups ADD place_in_line_you_next_filename TEXT;

UPDATE system_settings SET db_schema_version='1564',db_schema_update_date=NOW() where db_schema_version < 1564;

ALTER TABLE system_settings ADD mute_recordings ENUM('1','0') default '0';

ALTER TABLE vicidial_campaigns ADD mute_recordings ENUM('Y','N') default 'N';

ALTER TABLE vicidial_users ADD mute_recordings ENUM('DISABLED','Y','N') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1565',db_schema_update_date=NOW() where db_schema_version < 1565;

ALTER TABLE vicidial_campaigns MODIFY hide_call_log_info ENUM('Y','N','SHOW_1','SHOW_2','SHOW_3','SHOW_4','SHOW_5','SHOW_6','SHOW_7','SHOW_8','SHOW_9','SHOW_10') default 'N';

ALTER TABLE vicidial_users ADD hide_call_log_info ENUM('DISABLED','Y','N','SHOW_1','SHOW_2','SHOW_3','SHOW_4','SHOW_5','SHOW_6','SHOW_7','SHOW_8','SHOW_9','SHOW_10') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1566',db_schema_update_date=NOW() where db_schema_version < 1566;

CREATE TABLE vicidial_amd_log (
call_date DATETIME,
caller_code VARCHAR(30) default '',
lead_id INT(9) UNSIGNED,
uniqueid VARCHAR(20) default '',
channel VARCHAR(100) default '',
server_ip VARCHAR(60) NOT NULL,
AMDSTATUS VARCHAR(10) default '',
AMDRESPONSE VARCHAR(20) default '',
AMDCAUSE VARCHAR(30) default '',
run_time VARCHAR(20) default '0',
AMDSTATS VARCHAR(100) default '',
index (call_date),
index (lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_amd_log_archive LIKE vicidial_amd_log;
CREATE UNIQUE INDEX amd_unq_key on vicidial_amd_log_archive(uniqueid, call_date, lead_id);

UPDATE system_settings SET db_schema_version='1567',db_schema_update_date=NOW() where db_schema_version < 1567;

ALTER TABLE vicidial_users ADD next_dial_my_callbacks ENUM('NOT_ACTIVE','DISABLED','ENABLED') default 'NOT_ACTIVE';

UPDATE system_settings SET db_schema_version='1568',db_schema_update_date=NOW() where db_schema_version < 1568;

ALTER TABLE system_settings ADD user_admin_redirect ENUM('1','0') default '0';

ALTER TABLE vicidial_users ADD user_admin_redirect_url TEXT;

UPDATE system_settings SET db_schema_version='1569',db_schema_update_date=NOW() where db_schema_version < 1569;

ALTER TABLE system_settings ADD list_status_modification_confirmation ENUM('1','0') default '0';

UPDATE system_settings SET db_schema_version='1570',db_schema_update_date=NOW() where db_schema_version < 1570;

ALTER TABLE system_settings ADD sip_event_logging ENUM('0','1','2','3','4','5','6','7') default '0';

CREATE TABLE vicidial_sip_event_log ( 
sip_event_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL, 
caller_code VARCHAR(30) NOT NULL, 
channel VARCHAR(100), 
server_ip VARCHAR(15), 
uniqueid VARCHAR(20), 
sip_call_id VARCHAR(256), 
event_date DATETIME(6), 
sip_event VARCHAR(10), 
index(caller_code), 
index(event_date) 
) ENGINE=MyISAM;

CREATE TABLE vicidial_sip_event_log_0 LIKE vicidial_sip_event_log; 
ALTER TABLE vicidial_sip_event_log_0 MODIFY sip_event_id INT(9) UNSIGNED NOT NULL;
CREATE TABLE vicidial_sip_event_log_1 LIKE vicidial_sip_event_log; 
ALTER TABLE vicidial_sip_event_log_1 MODIFY sip_event_id INT(9) UNSIGNED NOT NULL;
CREATE TABLE vicidial_sip_event_log_2 LIKE vicidial_sip_event_log; 
ALTER TABLE vicidial_sip_event_log_2 MODIFY sip_event_id INT(9) UNSIGNED NOT NULL;
CREATE TABLE vicidial_sip_event_log_3 LIKE vicidial_sip_event_log; 
ALTER TABLE vicidial_sip_event_log_3 MODIFY sip_event_id INT(9) UNSIGNED NOT NULL;
CREATE TABLE vicidial_sip_event_log_4 LIKE vicidial_sip_event_log; 
ALTER TABLE vicidial_sip_event_log_4 MODIFY sip_event_id INT(9) UNSIGNED NOT NULL;
CREATE TABLE vicidial_sip_event_log_5 LIKE vicidial_sip_event_log; 
ALTER TABLE vicidial_sip_event_log_5 MODIFY sip_event_id INT(9) UNSIGNED NOT NULL;
CREATE TABLE vicidial_sip_event_log_6 LIKE vicidial_sip_event_log; 
ALTER TABLE vicidial_sip_event_log_6 MODIFY sip_event_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_sip_event_archive_details ( 
wday TINYINT(1) UNSIGNED PRIMARY KEY NOT NULL, 
start_event_date DATETIME(6), 
end_event_date DATETIME(6),
record_count INT(9) UNSIGNED default '0'
) ENGINE=MyISAM;

CREATE TABLE vicidial_sip_event_recent ( 
caller_code VARCHAR(20) default '', 
channel VARCHAR(100), 
server_ip VARCHAR(15), 
uniqueid VARCHAR(20), 
invite_date DATETIME(6), 
first_100_date DATETIME(6), 
first_180_date DATETIME(6), 
first_183_date DATETIME(6), 
last_100_date DATETIME(6), 
last_180_date DATETIME(6), 
last_183_date DATETIME(6), 
200_date DATETIME(6), 
error_date DATETIME(6), 
processed ENUM('N','Y','U') default 'N', 
index(caller_code), 
index(invite_date), 
index(processed) 
) ENGINE=MyISAM;  

CREATE TABLE vicidial_log_extended_sip (
call_date DATETIME(6),
caller_code VARCHAR(30) NOT NULL,
invite_to_ring DECIMAL(10,6) DEFAULT '0.000000',
ring_to_final DECIMAL(10,6) DEFAULT '0.000000',
invite_to_final DECIMAL(10,6) DEFAULT '0.000000',
last_event_code SMALLINT(3) default '0',
index(call_date),
index(caller_code)
) ENGINE=MyISAM;

CREATE TABLE vicidial_log_extended_sip_archive LIKE vicidial_log_extended_sip;
CREATE UNIQUE INDEX vlesa on vicidial_log_extended_sip_archive (caller_code,call_date);

UPDATE system_settings SET db_schema_version='1571',db_schema_update_date=NOW() where db_schema_version < 1571;

CREATE INDEX vicidial_email_xfer_key on vicidial_email_list (xfercallid);

ALTER TABLE vicidial_campaigns MODIFY agent_screen_time_display VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1572',db_schema_update_date=NOW() where db_schema_version < 1572;

ALTER TABLE system_settings ADD call_quota_lead_ranking ENUM('0','1','2') default '0';

ALTER TABLE vicidial_campaigns ADD auto_active_list_new VARCHAR(20) default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD call_quota_lead_ranking VARCHAR(40) default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD call_quota_process_running TINYINT(3) default '0';
ALTER TABLE vicidial_campaigns ADD call_quota_last_run_date DATETIME;

ALTER TABLE vicidial_lists ADD auto_active_list_rank SMALLINT(5) default '0';
ALTER TABLE vicidial_lists ADD cache_count INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_lists ADD cache_count_new INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_lists ADD cache_count_dialable_new INT(9) UNSIGNED default '0';
ALTER TABLE vicidial_lists ADD cache_date DATETIME;

CREATE TABLE vicidial_lead_call_quota_counts (
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT '0',
first_call_date DATETIME,
last_call_date DATETIME,
status VARCHAR(6),
called_count SMALLINT(5) UNSIGNED default '0',
session_one_calls TINYINT(3) default '0',
session_two_calls TINYINT(3) default '0',
session_three_calls TINYINT(3) default '0',
session_four_calls TINYINT(3) default '0',
session_five_calls TINYINT(3) default '0',
session_six_calls TINYINT(3) default '0',
day_one_calls TINYINT(3) default '0',
day_two_calls TINYINT(3) default '0',
day_three_calls TINYINT(3) default '0',
day_four_calls TINYINT(3) default '0',
day_five_calls TINYINT(3) default '0',
day_six_calls TINYINT(3) default '0',
day_seven_calls TINYINT(3) default '0',
session_one_today_calls TINYINT(3) default '0',
session_two_today_calls TINYINT(3) default '0',
session_three_today_calls TINYINT(3) default '0',
session_four_today_calls TINYINT(3) default '0',
session_five_today_calls TINYINT(3) default '0',
session_six_today_calls TINYINT(3) default '0',
rank SMALLINT(5) NOT NULL default '0',
modify_date DATETIME,
unique index vlcqc_lead_list (lead_id, list_id),
index(last_call_date),
index(list_id),
index(modify_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_lead_call_quota_counts_archive (
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT '0',
first_call_date DATETIME,
last_call_date DATETIME,
status VARCHAR(6),
called_count SMALLINT(5) UNSIGNED default '0',
session_one_calls TINYINT(3) default '0',
session_two_calls TINYINT(3) default '0',
session_three_calls TINYINT(3) default '0',
session_four_calls TINYINT(3) default '0',
session_five_calls TINYINT(3) default '0',
session_six_calls TINYINT(3) default '0',
day_one_calls TINYINT(3) default '0',
day_two_calls TINYINT(3) default '0',
day_three_calls TINYINT(3) default '0',
day_four_calls TINYINT(3) default '0',
day_five_calls TINYINT(3) default '0',
day_six_calls TINYINT(3) default '0',
day_seven_calls TINYINT(3) default '0',
session_one_today_calls TINYINT(3) default '0',
session_two_today_calls TINYINT(3) default '0',
session_three_today_calls TINYINT(3) default '0',
session_four_today_calls TINYINT(3) default '0',
session_five_today_calls TINYINT(3) default '0',
session_six_today_calls TINYINT(3) default '0',
rank SMALLINT(5) NOT NULL default '0',
modify_date DATETIME,
unique index vlcqc_lead_date (lead_id, first_call_date),
index(first_call_date)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1573',db_schema_update_date=NOW() where db_schema_version < 1573;

ALTER TABLE vicidial_campaigns ADD sip_event_logging VARCHAR(40) default 'DISABLED';
ALTER TABLE vicidial_campaigns MODIFY call_quota_lead_ranking VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1574',db_schema_update_date=NOW() where db_schema_version < 1574;

CREATE TABLE vicidial_bench_agent_log (
lead_id INT(9) UNSIGNED,
bench_date DATETIME,
absent_agent VARCHAR(20),
bench_agent VARCHAR(20),
user VARCHAR(20),
index (bench_date),
index (lead_id)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1575',db_schema_update_date=NOW() where db_schema_version < 1575;

CREATE TABLE vicidial_sip_action_log (
call_date DATETIME(6),
caller_code VARCHAR(30) NOT NULL,
lead_id INT(9) UNSIGNED,
phone_number VARCHAR(18),
user VARCHAR(20),
result VARCHAR(40),
index(call_date),
index(caller_code),
index(result)
) ENGINE=MyISAM;

CREATE TABLE vicidial_sip_action_log_archive LIKE vicidial_sip_action_log;
CREATE UNIQUE INDEX vlesa on vicidial_sip_action_log_archive (caller_code,call_date);

ALTER TABLE vicidial_log MODIFY term_reason ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE','SYSTEM') default 'NONE';
ALTER TABLE twoday_vicidial_log MODIFY term_reason ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE','SYSTEM') default 'NONE';
ALTER TABLE vicidial_log_noanswer MODIFY term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE','SYSTEM') default 'NONE';
ALTER TABLE vicidial_log_archive MODIFY term_reason ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE','SYSTEM') default 'NONE';
ALTER TABLE vicidial_log_noanswer_archive MODIFY term_reason ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE','SYSTEM') default 'NONE';

UPDATE system_settings SET db_schema_version='1576',db_schema_update_date=NOW() where db_schema_version < 1576;

ALTER TABLE vicidial_users ADD max_inbound_filter_enabled ENUM('0','1') default '0';
ALTER TABLE vicidial_users ADD max_inbound_filter_statuses TEXT;
ALTER TABLE vicidial_users ADD max_inbound_filter_ingroups TEXT;
ALTER TABLE vicidial_users ADD max_inbound_filter_min_sec SMALLINT(5) default '-1';

ALTER TABLE vicidial_live_inbound_agents ADD calls_today_filtered SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_live_inbound_agents ADD last_call_time_filtered DATETIME;
ALTER TABLE vicidial_live_inbound_agents ADD last_call_finish_filtered DATETIME;

ALTER TABLE vicidial_inbound_group_agents ADD calls_today_filtered SMALLINT(5) UNSIGNED default '0';

ALTER TABLE vicidial_live_agents ADD last_inbound_call_time_filtered DATETIME;
ALTER TABLE vicidial_live_agents ADD last_inbound_call_finish_filtered DATETIME;

UPDATE system_settings SET db_schema_version='1577',db_schema_update_date=NOW() where db_schema_version < 1577;

ALTER TABLE system_settings ADD enable_second_script ENUM('0','1') default '0';

ALTER TABLE vicidial_inbound_groups ADD ingroup_script_two VARCHAR(20) default '';
ALTER TABLE vicidial_inbound_groups MODIFY get_call_launch ENUM('NONE','SCRIPT','SCRIPTTWO','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM','EMAIL') default 'NONE';

ALTER TABLE vicidial_campaigns ADD campaign_script_two VARCHAR(20) default '';
ALTER TABLE vicidial_campaigns MODIFY get_call_launch ENUM('NONE','SCRIPT','SCRIPTTWO','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM','PREVIEW_WEBFORM','PREVIEW_WEBFORMTWO','PREVIEW_WEBFORMTHREE','PREVIEW_SCRIPT','PREVIEW_SCRIPTTWO','PREVIEW_FORM') default 'NONE';
ALTER TABLE vicidial_campaigns ADD leave_vm_no_dispo ENUM('ENABLED','DISABLED') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD leave_vm_message_group_id VARCHAR(40) default '---NONE---';

CREATE TABLE leave_vm_message_groups (
leave_vm_message_group_id VARCHAR(40) PRIMARY KEY NOT NULL,
leave_vm_message_group_notes VARCHAR(255) default '',
active ENUM('Y','N') default 'Y',
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE leave_vm_message_groups_entries (
leave_vm_message_group_id VARCHAR(40) NOT NULL,
audio_filename VARCHAR(255) NOT NULL,
audio_name VARCHAR(255) default '',
rank SMALLINT(5) default '0',
time_start VARCHAR(4) default '0000',
time_end VARCHAR(4) default '2400'
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_agent_vmm_overrides (
call_date DATETIME,
caller_code VARCHAR(30) default '',
lead_id INT(9) UNSIGNED,
user VARCHAR(20) default '',
vm_message VARCHAR(255) default '',
index (caller_code),
index (call_date),
index (lead_id)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1578',db_schema_update_date=NOW() where db_schema_version < 1578;

ALTER TABLE vicidial_campaigns ADD dial_timeout_lead_container VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1579',db_schema_update_date=NOW() where db_schema_version < 1579;

ALTER TABLE vicidial_users ADD status_group_id VARCHAR(20) default '';

ALTER TABLE vicidial_campaigns ADD amd_type ENUM('AMD','CPD','KHOMP') default 'AMD';

UPDATE system_settings SET db_schema_version='1580',db_schema_update_date=NOW() where db_schema_version < 1580;

ALTER TABLE system_settings ADD enable_first_webform ENUM('0','1') default '1';
ALTER TABLE system_settings ADD recording_buttons VARCHAR(30) default 'START_STOP';

UPDATE system_settings SET db_schema_version='1581',db_schema_update_date=NOW() where db_schema_version < 1581;

ALTER TABLE vicidial_campaigns MODIFY survey_first_audio_file TEXT;
ALTER TABLE vicidial_campaigns MODIFY survey_opt_in_audio_file TEXT;
ALTER TABLE vicidial_campaigns MODIFY survey_ni_audio_file TEXT;
ALTER TABLE vicidial_campaigns MODIFY survey_third_audio_file TEXT;
ALTER TABLE vicidial_campaigns MODIFY survey_fourth_audio_file TEXT;

UPDATE system_settings SET db_schema_version='1582',db_schema_update_date=NOW() where db_schema_version < 1582;

ALTER TABLE vicidial_campaigns ADD vmm_daily_limit TINYINT(3) UNSIGNED default '0';

CREATE TABLE vicidial_vmm_counts (
call_date DATE,
lead_id INT(9) UNSIGNED,
vmm_count SMALLINT(5) UNSIGNED default '0',
vmm_played SMALLINT(5) UNSIGNED default '0',
index (call_date),
index (lead_id)
) ENGINE=MyISAM;

CREATE UNIQUE INDEX vvmmcount on vicidial_vmm_counts (lead_id,call_date);

CREATE TABLE vicidial_vmm_counts_archive LIKE vicidial_vmm_counts;

UPDATE system_settings SET db_schema_version='1583',db_schema_update_date=NOW() where db_schema_version < 1583;

ALTER TABLE vicidial_cid_groups MODIFY cid_group_type ENUM('AREACODE','STATE','NONE') default 'AREACODE';

UPDATE system_settings SET db_schema_version='1584',db_schema_update_date=NOW() where db_schema_version < 1584;

ALTER TABLE vicidial_cid_groups ADD cid_auto_rotate_minutes MEDIUMINT(7) UNSIGNED default '0';
ALTER TABLE vicidial_cid_groups ADD cid_auto_rotate_minimum MEDIUMINT(7) UNSIGNED default '0';
ALTER TABLE vicidial_cid_groups ADD cid_auto_rotate_calls MEDIUMINT(7) UNSIGNED default '0';
ALTER TABLE vicidial_cid_groups ADD cid_last_auto_rotate DATETIME;
ALTER TABLE vicidial_cid_groups ADD cid_auto_rotate_cid VARCHAR(20) default '';

UPDATE vicidial_cid_groups SET cid_last_auto_rotate=NOW() where cid_last_auto_rotate IS NULL;

UPDATE system_settings SET db_schema_version='1585',db_schema_update_date=NOW() where db_schema_version < 1585;

ALTER TABLE system_settings ADD opensips_cid_name ENUM('0','1') default '0';

ALTER TABLE vicidial_campaigns ADD opensips_cid_name VARCHAR(15) default '';

UPDATE system_settings SET db_schema_version='1586',db_schema_update_date=NOW() where db_schema_version < 1586;

ALTER TABLE system_settings ADD require_password_length TINYINT(3) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1587',db_schema_update_date=NOW() where db_schema_version < 1587;

ALTER TABLE vicidial_campaigns MODIFY manual_dial_cid ENUM('CAMPAIGN','AGENT_PHONE','AGENT_PHONE_OVERRIDE') default 'CAMPAIGN';
ALTER TABLE vicidial_campaigns ADD amd_agent_route_options ENUM('ENABLED','DISABLED','PENDING') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1588',db_schema_update_date=NOW() where db_schema_version < 1588;

ALTER TABLE system_settings ADD user_account_emails ENUM('DISABLED','SEND_NO_PASS','SEND_WITH_PASS') DEFAULT 'DISABLED';

UPDATE system_settings SET db_schema_version='1589',db_schema_update_date=NOW() where db_schema_version < 1589;

ALTER TABLE system_settings ADD outbound_cid_any ENUM('DISABLED','API_ONLY') DEFAULT 'DISABLED';

UPDATE system_settings SET db_schema_version='1590',db_schema_update_date=NOW() where db_schema_version < 1590;

ALTER TABLE system_settings ADD entries_per_page SMALLINT(5) UNSIGNED DEFAULT '0';

UPDATE system_settings SET db_schema_version='1591',db_schema_update_date=NOW() where db_schema_version < 1591;

ALTER TABLE system_settings ADD browser_call_alerts ENUM('0','1','2') DEFAULT '0';

ALTER TABLE vicidial_campaigns ADD browser_alert_sound VARCHAR(20) default '---NONE---';
ALTER TABLE vicidial_campaigns ADD browser_alert_volume TINYINT(3) UNSIGNED default '50';

ALTER TABLE vicidial_inbound_groups ADD browser_alert_sound VARCHAR(20) default '---DISABLED---';
ALTER TABLE vicidial_inbound_groups ADD browser_alert_volume TINYINT(3) UNSIGNED default '50';

UPDATE system_settings SET db_schema_version='1592',db_schema_update_date=NOW() where db_schema_version < 1592;

CREATE TABLE vicidial_security_event_log (
event_id int(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
server_ip varchar(15),
event VARCHAR(25) NOT NULL,
event_time DATETIME(3) NOT NULL,
severity ENUM('Informational','Error') NOT NULL,
service VARCHAR(25) NOT NULL,
event_version VARCHAR(25) NOT NULL,
account_id VARCHAR(25) NOT NULL,
session_id VARCHAR(25) NOT NULL,
local_address VARCHAR(15) NOT NULL,
local_port SMALLINT NOT NULL,
remote_address VARCHAR(15) NOT NULL,
remote_port SMALLINT NOT NULL,
module VARCHAR(25),
session_time DATETIME(3),
optional_one VARCHAR(100),
optional_two VARCHAR(100),
optional_three VARCHAR(100),
index (server_ip),
index (event),
index (event_time),
index (remote_address)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1593',db_schema_update_date=NOW() where db_schema_version < 1593;

CREATE TABLE vicidial_lead_messages (
lead_id INT(9) UNSIGNED NOT NULL,
call_date DATETIME,
user VARCHAR(20) DEFAULT NULL,
played TINYINT(3) default '0',
message_entry MEDIUMTEXT,
index (lead_id),
index (call_date)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1594',db_schema_update_date=NOW() where db_schema_version < 1594;

ALTER TABLE vicidial_users MODIFY agentcall_manual ENUM('0','1','2','3','4','5') default '0';

UPDATE system_settings SET db_schema_version='1595',db_schema_update_date=NOW() where db_schema_version < 1595;

ALTER TABLE vicidial_campaigns ADD three_way_record_stop_exception VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1596',db_schema_update_date=NOW() where db_schema_version < 1596;

ALTER TABLE system_settings ADD queuemetrics_pausereason ENUM('STANDARD','EVERY_NEW','EVERY_NEW_ADMINCALL','EVERY_NEW_ALLCALL') default 'STANDARD';

UPDATE system_settings SET db_schema_version='1597',db_schema_update_date=NOW() where db_schema_version < 1597;

ALTER TABLE system_settings ADD inbound_answer_config ENUM('0','1','2','3','4','5') DEFAULT '0';

ALTER TABLE vicidial_inbound_dids ADD inbound_route_answer ENUM('Y','N') DEFAULT 'Y';

ALTER TABLE vicidial_call_menu ADD answer_signal ENUM('Y','N') DEFAULT 'Y';

ALTER TABLE vicidial_inbound_groups ADD answer_signal ENUM('START','ROUTE','NONE') DEFAULT 'START';

UPDATE servers SET rebuild_conf_files='Y' where active_asterisk_server='Y';

UPDATE system_settings SET db_schema_version='1598',db_schema_update_date=NOW() where db_schema_version < 1598;

ALTER TABLE vicidial_campaigns MODIFY concurrent_transfers ENUM('AUTO','1','2','3','4','5','6','7','8','9','10','15','20','25','30','40','50','60','80','100','1000','10000') default 'AUTO';

UPDATE system_settings SET db_schema_version='1599',db_schema_update_date=NOW() where db_schema_version < 1599;

ALTER TABLE vicidial_lists ADD inbound_drop_voicemail VARCHAR(20);
ALTER TABLE vicidial_lists ADD inbound_after_hours_voicemail VARCHAR(20);

UPDATE system_settings SET db_schema_version='1600',db_schema_update_date=NOW() where db_schema_version < 1600;

ALTER TABLE system_settings MODIFY queuemetrics_pausereason ENUM('STANDARD','EVERY_NEW','EVERY_NEW_ADMINCALL','EVERY_NEW_ALLCALL') default 'STANDARD';

UPDATE system_settings SET db_schema_version='1601',db_schema_update_date=NOW() where db_schema_version < 1601;

ALTER TABLE system_settings MODIFY queuemetrics_pausereason ENUM('STANDARD','EVERY_NEW','EVERY_NEW_ADMINCALL','EVERY_NEW_ALLCALL') default 'STANDARD';

UPDATE system_settings SET db_schema_version='1602',db_schema_update_date=NOW() where db_schema_version < 1602;

ALTER TABLE vicidial_screen_colors ADD button_color VARCHAR(6) default 'EFEFEF';

ALTER TABLE system_settings ADD enable_international_dncs ENUM('0','1') default '0'; 

CREATE TABLE vicidial_country_dnc_queue (
dnc_file_id int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
filename varchar(250) DEFAULT NULL,
country_code varchar(3) DEFAULT NULL,
file_layout varchar(30) DEFAULT NULL,
file_status enum('UPLOADING','READY','PENDING','INVALID LAYOUT','PROCESSING','FINISHED','CANCELLED') DEFAULT NULL,
file_action enum('PURGE','APPEND') DEFAULT NULL,
date_uploaded DATETIME DEFAULT NULL,
total_records int(10) UNSIGNED DEFAULT NULL,
records_processed int(10) UNSIGNED DEFAULT NULL,
records_inserted int(10) UNSIGNED DEFAULT NULL,
date_processed DATETIME DEFAULT NULL,
PRIMARY KEY (dnc_file_id),
KEY vicidial_country_dnc_queue_filename_key (filename)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;

INSERT INTO vicidial_settings_containers VALUES ('INTERNATIONAL_DNC_IMPORT','Process DNC lists of various countries from FTP site','PERL_CLI','---ALL---','# This setting container is used for the international DNC system. \r\n# The below two settings are mandatory for importing suppression lists\r\n# and tell the import process where to look for new files and where to\r\n# move them when handled.  These settings cannot have the same value. \r\n--file-dir=/root/ftp\r\n--file-destination=/root/ftp/DONE\r\n\r\n# Uncomment below and set the status to whatever custom disposition you \r\n# would like already-loaded leads to be set to when they dedupe against\r\n# a country\'s DNC list (default is \"DNCI\")\r\n# --dnc-status-override=BMNR\r\n\r\n# The below settings are optional for when files are stored on a remote\r\n# server.  It is strongly recommended these settings are not used and\r\n# that the processing scripts and files are stored locally on the same\r\n# server. \r\n# --ftp-host=localhost\r\n# --ftp-user=user\r\n# --ftp-pwd=pwd\r\n# --ftp-port=21\r\n# --ftp-passive=1\r\n'),('DNC_IMPORT_FORMATS','Import formats for DNC files','OTHER','---ALL---','# This setting container is used for storing file formats used when \r\n# loading DNC suppression lists into the dialer. \r\n#\r\n# import template => (delimited|fixed),delimiter,phone1(,phone2,phone3)\r\n#\r\n# For delimited files, the phone1 value should be the index value of\r\n# the field where the phone appears.  The first array index is 0 and\r\n# indexes continue through the natural numbers.\r\n\r\n# In delimited files, acceptable values for the \"delimiter\" field are:\r\n# - \"tab\", \"pipe\", \"comma\", \"quote-comma\"\r\nBASIC_DELIMITED_FORMAT => delimited,pipe,0\r\n\r\n# If the phone number is split into multiple fields (ex: area code in\r\n# one field, rest of the number in another), simply list additional \r\n# indices of the phone number fields separated by commas in the order \r\n# in which the data should be combined to make the complete phone \r\n# number \r\nDELIMITED_WITH_AC_AND_EXCHANGE_SPLIT => delimited,tab,0,1\r\n\r\n# For fixed-length files, the phone field values should be of the type:\r\n# - \"starting_position|length\"\r\nBASIC_FIXED_FORMAT => fixed,,0|10\r\n\r\n# (delimited|fixed) is not used for CSV/Excel files, so all that needs \r\n# providing for those is the index field value(s) of the phone number\r\nBASIC_CSV_OR_EXCEL_FORMAT => ,,0'),('DNC_CURRENT_BLOCKED_LISTS','Lists currently blocked due to pending DNC scrub','READ_ONLY','---ALL---','');

UPDATE system_settings SET db_schema_version='1603',db_schema_update_date=NOW() where db_schema_version < 1603;

ALTER TABLE vicidial_users MODIFY modify_leads ENUM('0','1','2','3','4','5','6') default '0';

UPDATE system_settings SET db_schema_version='1604',db_schema_update_date=NOW() where db_schema_version < 1604;

ALTER TABLE vicidial_campaigns MODIFY get_call_launch ENUM('NONE','SCRIPT','SCRIPTTWO','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM','PREVIEW_WEBFORM','PREVIEW_WEBFORMTWO','PREVIEW_WEBFORMTHREE','PREVIEW_SCRIPT','PREVIEW_SCRIPTTWO','PREVIEW_FORM') default 'NONE';

UPDATE system_settings SET db_schema_version='1605',db_schema_update_date=NOW() where db_schema_version < 1605;

ALTER TABLE vicidial_campaigns MODIFY alt_number_dialing ENUM('N','Y','SELECTED','SELECTED_TIMER_ALT','SELECTED_TIMER_ADDR3','UNSELECTED','UNSELECTED_TIMER_ALT','UNSELECTED_TIMER_ADDR3') default 'N';

UPDATE system_settings SET db_schema_version='1606',db_schema_update_date=NOW() where db_schema_version < 1606;

ALTER TABLE system_settings ADD web_loader_phone_strip VARCHAR(10) default 'DISABLED'; 
ALTER TABLE system_settings ADD manual_dial_phone_strip VARCHAR(10) default 'DISABLED'; 

UPDATE system_settings SET db_schema_version='1607',db_schema_update_date=NOW() where db_schema_version < 1607;

ALTER TABLE vicidial_campaigns ADD pause_max_exceptions VARCHAR(40) default '';

UPDATE system_settings SET db_schema_version='1608',db_schema_update_date=NOW() where db_schema_version < 1608;

ALTER TABLE vicidial_inbound_groups ADD no_agent_delay SMALLINT(5) default '0';

UPDATE system_settings SET db_schema_version='1609',db_schema_update_date=NOW() where db_schema_version < 1609;

ALTER TABLE park_log ADD campaign_id VARCHAR(20) default '';
CREATE INDEX pl_campaign_id on park_log(campaign_id);
CREATE TABLE park_log_archive LIKE park_log;
CREATE UNIQUE INDEX uniqueidtime_park on park_log_archive (uniqueid,parked_time);

ALTER TABLE vicidial_campaign_stats ADD park_calls_today MEDIUMINT(8) UNSIGNED default '0';
ALTER TABLE vicidial_campaign_stats ADD park_sec_today BIGINT(14) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1610',db_schema_update_date=NOW() where db_schema_version < 1610;

ALTER TABLE vicidial_campaigns ADD hopper_drop_run_trigger VARCHAR(1) default 'N';

UPDATE system_settings SET db_schema_version='1611',db_schema_update_date=NOW() where db_schema_version < 1611;

ALTER TABLE system_settings ADD daily_call_count_limit ENUM('0','1') default '0';

ALTER TABLE vicidial_campaigns ADD daily_call_count_limit TINYINT(3) UNSIGNED default '0';
ALTER TABLE vicidial_campaigns ADD daily_limit_manual VARCHAR(20) default 'DISABLED';

CREATE TABLE vicidial_lead_call_daily_counts (
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT '0',
called_count_total TINYINT(3) UNSIGNED default '0',
called_count_auto TINYINT(3) UNSIGNED default '0',
called_count_manual TINYINT(3) UNSIGNED default '0',
modify_date DATETIME,
unique index vlcdc_lead (lead_id),
index(list_id)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1612',db_schema_update_date=NOW() where db_schema_version < 1612;

ALTER TABLE vicidial_campaigns ADD transfer_button_launch VARCHAR(12) default 'NONE';

UPDATE system_settings SET db_schema_version='1613',db_schema_update_date=NOW() where db_schema_version < 1613;

ALTER TABLE system_settings ADD allow_shared_dial ENUM('0','1','2','3','4','5','6') default '0';

ALTER TABLE vicidial_campaigns ADD shared_dial_rank TINYINT(3) default '99';
ALTER TABLE vicidial_campaigns MODIFY dial_method ENUM('MANUAL','RATIO','ADAPT_HARD_LIMIT','ADAPT_TAPERED','ADAPT_AVERAGE','INBOUND_MAN','SHARED_RATIO','SHARED_ADAPT_HARD_LIMIT','SHARED_ADAPT_TAPERED','SHARED_ADAPT_AVERAGE') default 'MANUAL';

ALTER TABLE vicidial_live_agents ADD dial_campaign_id VARCHAR(8) default '';

CREATE TABLE vicidial_agent_dial_campaigns (
campaign_id VARCHAR(8),
group_id VARCHAR(20),
user VARCHAR(20),
validate_time DATETIME,
dial_time DATETIME,
index (user),
index (campaign_id)
) ENGINE=MyISAM;

CREATE UNIQUE INDEX vadc_key on vicidial_agent_dial_campaigns(campaign_id, user);

UPDATE system_settings SET db_schema_version='1614',db_schema_update_date=NOW() where db_schema_version < 1614;

ALTER TABLE system_settings ADD agent_search_method ENUM('0','1','2','3','4','5','6') default '0';

ALTER TABLE vicidial_campaigns ADD agent_search_method VARCHAR(2) default '';

ALTER TABLE vicidial_inbound_groups ADD agent_search_method VARCHAR(2) default '';

UPDATE system_settings SET db_schema_version='1615',db_schema_update_date=NOW() where db_schema_version < 1615;

ALTER TABLE system_settings MODIFY allow_shared_dial ENUM('0','1','2','3','4','5','6') default '0';

CREATE TABLE vicidial_shared_log (
campaign_id VARCHAR(20) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
log_time DATETIME,
total_agents SMALLINT(5) default '0',
total_calls SMALLINT(5) default '0',
debug_output TEXT,
adapt_output TEXT,
index (campaign_id),
index (log_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_shared_drops (
callerid VARCHAR(20),
server_ip VARCHAR(15) NOT NULL,
campaign_id VARCHAR(20),
status ENUM('SENT','RINGING','LIVE','XFER','PAUSED','CLOSER','BUSY','DISCONNECT','IVR') default 'PAUSED',
lead_id INT(9) UNSIGNED NOT NULL,
uniqueid VARCHAR(20),
channel VARCHAR(100),
phone_code VARCHAR(10),
phone_number VARCHAR(18),
call_time DATETIME,
call_type ENUM('IN','OUT','OUTBALANCE') default 'OUT',
stage VARCHAR(20) default 'START',
last_update_time DATETIME,
alt_dial VARCHAR(6) default 'NONE',
drop_time DATETIME,
index (callerid),
index (call_time),
index (drop_time)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1616',db_schema_update_date=NOW() where db_schema_version < 1616;

ALTER TABLE vicidial_lists_fields MODIFY field_type ENUM('TEXT','AREA','SELECT','MULTI','RADIO','CHECKBOX','DATE','TIME','DISPLAY','SCRIPT','HIDDEN','READONLY','HIDEBLOB','SWITCH','SOURCESELECT','BUTTON') default 'TEXT';

UPDATE system_settings SET db_schema_version='1617',db_schema_update_date=NOW() where db_schema_version < 1617;

INSERT INTO vicidial_settings_containers VALUES ('PHONE_DEFAULTS','Default phone settings for preloading','PHONE_DEFAULTS','---ALL---','# Below are all phone settings recognized under the PHONE_DEFAULTS \r\n# container type and the type of data each accepts.  Any setting that\r\n# uses a default value in the database has said value pre-set below\r\n\r\n# 10 char max\r\nvoicemail_id => \r\n \r\n# 15 char max\r\nserver_ip => \r\n\r\n# 100 char max\r\npass => \r\n\r\n# 10 char max\r\nstatus => \r\n\r\n# Y/N only\r\nactive => Y\r\n\r\n# 50 char max\r\nphone_type => \r\n\r\n# \'SIP\',\'Zap\',\'IAX2\' or \'EXTERNAL\'\r\nprotocol => SIP\r\n\r\n# positive or negatier 2-decimal floating point number\r\nlocal_gmt => -5.00\r\n\r\n# 20 char max\r\nvoicemail_dump_exten => 85026666666666\r\n\r\n# 20 char max\r\noutbound_cid => \r\n\r\n# 100 char max\r\nemail => \r\n\r\n# 15 char max\r\ntemplate_id => \r\n\r\n# text, conf_override can span multiple lines, see below\r\nconf_override => \r\n# type=friend\r\n# host=dynamic\r\n# canreinvite=no\r\n# context=default1\r\n\r\n# 50 char max\r\nphone_context => default\r\n\r\n# Unsigned - max value 65536\r\nphone_ring_timeout => 60\r\n\r\n# 20 char max\r\nconf_secret => test\r\n\r\n# Y/N only\r\ndelete_vm_after_email => N\r\n\r\n# Options - Y, N, or Y_API_LAUNCH\r\nis_webphone => N\r\n\r\n# Y/N only\r\nuse_external_server_ip => N\r\n\r\n# 100 char max\r\ncodecs_list => \r\n\r\n# 0/1 only\r\ncodecs_with_template => 0\r\n\r\n# Options - Y, N, TOGGLE, or TOGGLE_OFF\r\nwebphone_dialpad => Y\r\n\r\n# Y/N only\r\non_hook_agent => N\r\n\r\n# Y/N only\r\nwebphone_auto_answer => Y\r\n\r\n# 30 char max\r\nvoicemail_timezone => eastern\r\n\r\n# 255 char max\r\nvoicemail_options => \r\n\r\n# 20 char max\r\nuser_group => ---ALL---\r\n\r\n# 100 char max\r\nvoicemail_greeting => \r\n\r\n# 20 char max\r\nvoicemail_dump_exten_no_inst => 85026666666667\r\n\r\n# Y/N only\r\nvoicemail_instructions => Y\r\n\r\n# Y/N only\r\non_login_report => N\r\n\r\n# 40 char max\r\nunavail_dialplan_fwd_exten => \r\n\r\n# 100 char max\r\nunavail_dialplan_fwd_context => \r\n\r\n# text\r\nnva_call_url => \r\n\r\n# 40 char max\r\nnva_search_method => \r\n\r\n# 255 char max\r\nnva_error_filename => \r\n\r\n# Integer, any size\r\nnva_new_list_id => 995\r\n\r\n# 10 char max\r\nnva_new_phone_code => 1\r\n\r\n# 6 char max\r\nnva_new_status => NVAINS\r\n\r\n# Y/N only\r\nwebphone_dialbox => Y\r\n\r\n# Y/N only\r\nwebphone_mute => Y\r\n\r\n# Y/N only\r\nwebphone_volume => Y\r\n\r\n# Y/N only\r\nwebphone_debug => N\r\n\r\n# 20 char max\r\noutbound_alt_cid => \r\n\r\n# Y/N only\r\nconf_qualify => Y\r\n\r\n# 255 char max\r\nwebphone_layout => \r\n');

ALTER TABLE system_settings ADD phone_defaults_container VARCHAR(40) default '---DISABLED---';

UPDATE system_settings SET db_schema_version='1618',db_schema_update_date=NOW() where db_schema_version < 1618;

CREATE TABLE quality_control_checkpoint_log (
qc_checkpoint_log_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
qc_log_id INT(10) UNSIGNED DEFAULT NULL,
campaign_id VARCHAR(8) DEFAULT NULL,
group_id VARCHAR(20) DEFAULT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT NULL,
qc_scorecard_id VARCHAR(20) DEFAULT NULL,
checkpoint_row_id INT(10) UNSIGNED DEFAULT NULL,
checkpoint_text TEXT,
checkpoint_rank TINYINT(3) UNSIGNED DEFAULT NULL,
checkpoint_points TINYINT(3) UNSIGNED DEFAULT NULL,
instant_fail ENUM('Y','N') DEFAULT 'N',
checkpoint_points_earned TINYINT(5) UNSIGNED DEFAULT NULL,
qc_agent VARCHAR(20) DEFAULT NULL,
checkpoint_comment_agent TEXT,
PRIMARY KEY (qc_checkpoint_log_id)
) ENGINE=MyISAM;

CREATE TABLE quality_control_checkpoints (
checkpoint_row_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
qc_scorecard_id VARCHAR(20) DEFAULT NULL,
checkpoint_text TEXT,
checkpoint_rank INT(3) UNSIGNED DEFAULT NULL,
checkpoint_points TINYINT(3) UNSIGNED DEFAULT NULL,
instant_fail ENUM('Y','N') DEFAULT 'N',
admin_notes TEXT,
active ENUM('Y','N') DEFAULT NULL,
campaign_ids TEXT,
ingroups TEXT,
list_ids TEXT,
create_date DATETIME DEFAULT NULL,
create_user VARCHAR(10) DEFAULT NULL,
modify_date TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
modify_user VARCHAR(10) DEFAULT NULL,
PRIMARY KEY (checkpoint_row_id)
) ENGINE=MyISAM;

CREATE TABLE quality_control_queue (
qc_log_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
qc_display_method ENUM('CALL','LEAD') DEFAULT 'CALL',
lead_id INT(10) UNSIGNED DEFAULT NULL,
status VARCHAR(6) DEFAULT NULL,
call_date DATETIME DEFAULT NULL,
agent_log_id INT(9) UNSIGNED DEFAULT NULL,
user VARCHAR(20) DEFAULT NULL,
user_group VARCHAR(20) DEFAULT NULL,
campaign_id VARCHAR(8) DEFAULT NULL,
group_id VARCHAR(20) DEFAULT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT NULL,
scorecard_source ENUM('CAMPAIGN','INGROUP','LIST') DEFAULT 'CAMPAIGN',
qc_web_form_address VARCHAR(255) DEFAULT NULL,
vicidial_id VARCHAR(20) DEFAULT NULL,
recording_id INT(10) UNSIGNED DEFAULT NULL,
qc_scorecard_id VARCHAR(20) DEFAULT NULL,
qc_agent VARCHAR(20) DEFAULT NULL,
qc_user_group VARCHAR(20) DEFAULT NULL,
qc_status VARCHAR(20) DEFAULT NULL,
date_modified TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
date_claimed DATETIME DEFAULT NULL,
date_completed DATETIME DEFAULT NULL,
PRIMARY KEY (qc_log_id),
UNIQUE KEY quality_control_queue_agent_log_id_key (agent_log_id),
KEY quality_control_queue_lead_id_key (lead_id)
) ENGINE=MyISAM;

CREATE TABLE quality_control_scorecards (
qc_scorecard_id VARCHAR(20) NOT NULL,
scorecard_name VARCHAR(255) DEFAULT NULL,
active ENUM('Y','N') DEFAULT 'Y',
passing_score SMALLINT(5) UNSIGNED DEFAULT 0,
last_modified TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
PRIMARY KEY (qc_scorecard_id)
) ENGINE=MyISAM;

ALTER TABLE vicidial_campaigns ADD qc_scorecard_id VARCHAR(20) DEFAULT '';
ALTER TABLE vicidial_campaigns ADD qc_statuses_id VARCHAR(20) DEFAULT '';

ALTER TABLE vicidial_lists ADD qc_scorecard_id VARCHAR(20) DEFAULT '';
ALTER TABLE vicidial_lists ADD qc_statuses_id VARCHAR(20) DEFAULT '';
ALTER TABLE vicidial_lists ADD qc_web_form_address VARCHAR(255) DEFAULT '';

ALTER TABLE vicidial_inbound_groups ADD qc_scorecard_id VARCHAR(20) DEFAULT '';
ALTER TABLE vicidial_inbound_groups ADD qc_statuses_id VARCHAR(20) DEFAULT '';

ALTER TABLE system_settings ADD qc_claim_limit TINYINT UNSIGNED DEFAULT '3';
ALTER TABLE system_settings ADD qc_expire_days TINYINT UNSIGNED DEFAULT '3';

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('QC_STATUS_TEMPLATE','Sample QC Status Template','QC_TEMPLATE','---ALL---','# These types of containers are simply used for creating a list of \r\n# QC-enabled statuses to apply to campaigns, lists, and ingroups.\r\n# Simply put all the statuses that this template should allow in\r\n# a comma-delimited string, as below:\r\n\r\nSALE,DNC,NI');

UPDATE system_settings SET db_schema_version='1619',db_schema_update_date=NOW() where db_schema_version < 1619;

ALTER TABLE vicidial_lists_fields MODIFY field_type ENUM('TEXT','AREA','SELECT','MULTI','RADIO','CHECKBOX','DATE','TIME','DISPLAY','SCRIPT','HIDDEN','READONLY','HIDEBLOB','SWITCH','SOURCESELECT','BUTTON') default 'TEXT';

ALTER TABLE vicidial_users ADD mobile_number VARCHAR(20) default '';
ALTER TABLE vicidial_users ADD two_factor_override  ENUM('NOT_ACTIVE','ENABLED','DISABLED') default 'NOT_ACTIVE';

ALTER TABLE system_settings ADD two_factor_auth_hours SMALLINT(5) default '0';
ALTER TABLE system_settings ADD two_factor_container VARCHAR(40) default '---DISABLED---';

INSERT INTO vicidial_call_menu (menu_id,menu_name,menu_prompt,menu_timeout,menu_timeout_prompt,menu_invalid_prompt,menu_repeat,menu_time_check,call_time_id,track_in_vdac,custom_dialplan_entry,tracking_group,dtmf_log,dtmf_field,user_group,qualify_sql,alt_dtmf_log,question,answer_signal) values('2FA_say_auth_code','2FA_say_auth_code','sip-silence|hello|your|access-code|is|cm_speak_var.agi,say_digits---access_code---DP',1,'NONE','NONE',1,'0','24hours','1','','CALLMENU','0','NONE','---ALL---','','0',0,'Y');

INSERT INTO vicidial_call_menu_options (menu_id,option_value,option_description,option_route,option_route_value,option_route_value_context) values('2FA_say_auth_code','TIMEOUT','','HANGUP','','');

CREATE TABLE vicidial_two_factor_auth (
auth_date DATETIME,
auth_exp_date DATETIME,
user VARCHAR(20) default '',
auth_stage ENUM('0','1','2','3','4','5','6') default '0',
auth_code VARCHAR(20) default '',
auth_code_exp_date DATETIME,
auth_method VARCHAR(20) default 'EMAIL',
auth_attempts SMALLINT(5) default '0',
index (user),
index (auth_date),
index (auth_exp_date)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1620',db_schema_update_date=NOW() where db_schema_version < 1620;

ALTER TABLE system_settings MODIFY enhanced_disconnect_logging ENUM('0','1','2','3','4','5','6') default '0';

UPDATE system_settings SET db_schema_version='1621',db_schema_update_date=NOW() where db_schema_version < 1621;

ALTER TABLE vicidial_campaigns ADD clear_form ENUM('DISABLED','ENABLED','ACKNOWLEDGE') default 'ACKNOWLEDGE';

UPDATE system_settings SET db_schema_version='1622',db_schema_update_date=NOW() where db_schema_version < 1622;

CREATE TABLE vicidial_agent_visibility_log (
db_time DATETIME NOT NULL,
event_start_epoch INT(10) UNSIGNED,
event_end_epoch INT(10) UNSIGNED,
user VARCHAR(20),
length_in_sec INT(10),
visibility  ENUM('VISIBLE','HIDDEN','LOGIN','NONE') default 'NONE',
agent_log_id INT(9) UNSIGNED,
index (db_time),
unique index visibleuser (user, visibility, event_end_epoch)
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_visibility_log_archive LIKE vicidial_agent_visibility_log;

ALTER TABLE system_settings ADD agent_hidden_sound VARCHAR(20) default 'click_quiet';
ALTER TABLE system_settings ADD agent_hidden_sound_volume TINYINT(3) UNSIGNED default '25';
ALTER TABLE system_settings ADD agent_hidden_sound_seconds TINYINT(3) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1623',db_schema_update_date=NOW() where db_schema_version < 1623;

ALTER TABLE quality_control_checkpoint_log ADD instant_fail_value ENUM('Y', 'N') default 'N' AFTER instant_fail;
ALTER TABLE quality_control_checkpoint_log ADD checkpoint_text_presets TEXT AFTER checkpoint_text;

ALTER TABLE quality_control_checkpoints ADD checkpoint_text_presets TEXT AFTER checkpoint_text;

UPDATE system_settings SET db_schema_version='1624',db_schema_update_date=NOW() where db_schema_version < 1624;

ALTER TABLE audio_store_details ADD wav_format_details VARCHAR(255) default '';
ALTER TABLE audio_store_details ADD wav_asterisk_valid ENUM('','GOOD','BAD','NA') default '';

UPDATE system_settings SET db_schema_version='1625',db_schema_update_date=NOW() where db_schema_version < 1625;

ALTER TABLE vicidial_campaigns ADD leave_3way_start_recording ENUM('DISABLED','ALL_CALLS','ALL_BUT_EXCEPTIONS','ONLY_EXCEPTIONS') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD leave_3way_start_recording_exception VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1626',db_schema_update_date=NOW() where db_schema_version < 1626;

ALTER TABLE vicidial_inbound_groups ADD populate_lead_comments VARCHAR(40) default 'CALLERID_NAME';

UPDATE system_settings SET db_schema_version='1627',db_schema_update_date=NOW() where db_schema_version < 1627;

ALTER TABLE system_settings ADD agent_screen_timer VARCHAR(20) default 'setTimeout';

UPDATE system_settings SET db_schema_version='1628',db_schema_update_date=NOW() where db_schema_version < 1628;

CREATE TABLE vicidial_peer_event_log (
`peer_event_id` INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
`event_type` ENUM('UNKNOWN','REGISTERED','UNREGISTERED','REACHABLE','LAGGED','UNREACHABLE','RTPDISCONNECT','CRITICALTIMEOUT') COLLATE utf8_unicode_ci DEFAULT 'UNKNOWN',
`event_date` DATETIME(6) NOT NULL,
`channel` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT '',
`server_ip` VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
`host_ip` VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT '',
`port` SMALLINT(6) DEFAULT NULL,
`channel_type` ENUM('IAX2','SIP') COLLATE utf8_unicode_ci DEFAULT NULL,
`peer` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT '',
`data` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT '',
PRIMARY KEY (`peer_event_id`),
KEY `event_date` (`event_date`),
KEY `peer` (`peer`),
KEY `channel` (`channel`)
) ENGINE=MyISAM AUTO_INCREMENT=630320 DEFAULT CHARSET=utf8 
COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_peer_event_log_archive LIKE vicidial_peer_event_log;
ALTER TABLE vicidial_peer_event_log_archive MODIFY peer_event_id INT(9) UNSIGNED NOT NULL;

UPDATE system_settings SET db_schema_version='1629',db_schema_update_date=NOW() where db_schema_version < 1629;

ALTER TABLE vicidial_campaigns ADD calls_waiting_vl_one VARCHAR(25) default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD calls_waiting_vl_two VARCHAR(25) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1630',db_schema_update_date=NOW() where db_schema_version < 1630;

ALTER TABLE system_settings ADD label_lead_id VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_list_id VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_entry_date VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_gmt_offset_now VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_source_id VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_called_since_last_reset VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_status VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_user VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_date_of_birth VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_country_code VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_last_local_call_time VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_called_count VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_rank VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_owner VARCHAR(60) default '';
ALTER TABLE system_settings ADD label_entry_list_id VARCHAR(60) default '';

ALTER TABLE vicidial_screen_labels ADD label_lead_id VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_list_id VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_entry_date VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_gmt_offset_now VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_source_id VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_called_since_last_reset VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_status VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_user VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_date_of_birth VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_country_code VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_last_local_call_time VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_called_count VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_rank VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_owner VARCHAR(60) default '';
ALTER TABLE vicidial_screen_labels ADD label_entry_list_id VARCHAR(60) default '';

UPDATE system_settings SET db_schema_version='1631',db_schema_update_date=NOW() where db_schema_version < 1631;

ALTER TABLE vicidial_campaigns ADD calls_inqueue_count_one VARCHAR(40) default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD calls_inqueue_count_two VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1632',db_schema_update_date=NOW() where db_schema_version < 1632;

ALTER TABLE phones ADD mohsuggest VARCHAR(100) default '';

UPDATE system_settings SET db_schema_version='1633',db_schema_update_date=NOW() where db_schema_version < 1633;

CREATE TABLE vicidial_tiltx_shaken_log (
db_time DATETIME NOT NULL,
server_ip VARCHAR(15) NOT NULL,
url_log_id INT(9) UNSIGNED NOT NULL,
caller_code VARCHAR(20),
phone_number VARCHAR(19) default '',
CIDnumber VARCHAR(19) default '',
CallerIDToUse VARCHAR(19) default '',
IsDNC TINYINT(1) default '0',
IsDisconnected TINYINT(1) default '0',
TILTXID VARCHAR(50),
Identity TEXT,
CAID VARCHAR(50),
index (db_time)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1634',db_schema_update_date=NOW() where db_schema_version < 1634;

ALTER TABLE vicidial_inbound_groups ADD drop_call_seconds_override VARCHAR(40) default 'DISABLED';

ALTER TABLE vicidial_campaigns ADD in_man_dial_next_ready_seconds SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_campaigns ADD in_man_dial_next_ready_seconds_override VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1635',db_schema_update_date=NOW() where db_schema_version < 1635;

ALTER TABLE phones ADD peer_status ENUM('UNKNOWN','REGISTERED','UNREGISTERED','REACHABLE','LAGGED','UNREACHABLE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNKNOWN';
ALTER TABLE phones ADD ping_time SMALLINT(6) DEFAULT NULL;

UPDATE system_settings SET db_schema_version='1636',db_schema_update_date=NOW() where db_schema_version < 1636;

ALTER TABLE vicidial_campaigns ADD transfer_no_dispo ENUM('DISABLED','EXTERNAL_ONLY','LOCAL_ONLY','LEAVE3WAY_ONLY','LOCAL_AND_EXTERNAL','LOCAL_AND_LEAVE3WAY','LEAVE3WAY_AND_EXTERNAL','LOCAL_AND_EXTERNAL_AND_LEAVE3WAY') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1637',db_schema_update_date=NOW() where db_schema_version < 1637;

ALTER TABLE vicidial_users ADD manual_dial_filter VARCHAR(50) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1638',db_schema_update_date=NOW() where db_schema_version < 1638;

ALTER TABLE vicidial_list MODIFY called_since_last_reset ENUM('Y','N','Y1','Y2','Y3','Y4','Y5','Y6','Y7','Y8','Y9','Y10','D') default 'N';

CREATE TABLE `vicidial_khomp_log` (
`khomp_log_id` int(9) unsigned NOT NULL AUTO_INCREMENT,
`caller_code` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
`lead_id` int(10) unsigned DEFAULT 0,
`server_ip` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
`khomp_header` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`khomp_id` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
`khomp_id_format` enum('CALLERCODE','CALLERCODE_EXTERNIP','CALLERCODE_CAMP_EXTERNIP') COLLATE utf8_unicode_ci DEFAULT NULL,
`sip_call_id` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
`start_date` datetime(6) DEFAULT NULL,
`audio_date` datetime(6) DEFAULT NULL,
`answer_date` datetime(6) DEFAULT NULL,
`end_date` datetime(6) DEFAULT NULL,
`analyzer_date` datetime(6) DEFAULT NULL,
`conclusion` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`pattern` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
`action` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`hangup_origin` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
`hangup_cause_recv` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
`hangup_cause_sent` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
`hangup_auth_time` varchar(20) COLLATE utf8_unicode_ci DEFAULT '0',
`hangup_query_time` varchar(20) COLLATE utf8_unicode_ci DEFAULT '0',
`route_auth_time` varchar(20) COLLATE utf8_unicode_ci DEFAULT '0',
`route_query_time` varchar(20) COLLATE utf8_unicode_ci DEFAULT '0',
`vici_action` varchar(10) COLLATE utf8_unicode_ci DEFAULT NULL,
`vici_status` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
PRIMARY KEY (`khomp_log_id`),
KEY `caller_code` (`caller_code`),
KEY `start_date` (`start_date`),
KEY `khomp_id` (`khomp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_lead_24hour_calls (
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT '0',
call_date DATETIME,
phone_number VARCHAR(18),
phone_code VARCHAR(10),
state VARCHAR(2),
call_type ENUM('MANUAL','AUTO','') default '',
index(lead_id),
index(call_date),
index(phone_number)
) ENGINE=MyISAM;

ALTER TABLE vicidial_campaigns ADD call_limit_24hour_method ENUM('DISABLED','PHONE_NUMBER','LEAD') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD call_limit_24hour_scope ENUM('SYSTEM_WIDE','CAMPAIGN_LISTS') default 'SYSTEM_WIDE';
ALTER TABLE vicidial_campaigns ADD call_limit_24hour TINYINT(3) UNSIGNED default '0';
ALTER TABLE vicidial_campaigns ADD call_limit_24hour_override VARCHAR(40) default 'DISABLED';

ALTER TABLE system_settings ADD call_limit_24hour ENUM('0','1') default '0';
ALTER TABLE system_settings ADD call_limit_24hour_reset DATETIME default '2000-01-01 00:00:01';

UPDATE system_settings SET db_schema_version='1639',db_schema_update_date=NOW() where db_schema_version < 1639;

ALTER TABLE vicidial_campaigns ADD cid_group_id_two VARCHAR(20) default '---DISABLED---';

UPDATE system_settings SET db_schema_version='1640',db_schema_update_date=NOW() where db_schema_version < 1640;

CREATE TABLE vicidial_inbound_caller_codes (
uniqueid VARCHAR(50) NOT NULL,
server_ip VARCHAR(15),
call_date DATETIME,
group_id VARCHAR(20) NOT NULL,
lead_id INT(9) UNSIGNED,
caller_code VARCHAR(30) NOT NULL,
prev_caller_code VARCHAR(40) NOT NULL,
index (uniqueid),
index (call_date),
unique index cicc_cd (caller_code, uniqueid)
) ENGINE=MyISAM;

CREATE TABLE vicidial_inbound_caller_codes_archive LIKE vicidial_inbound_caller_codes;

UPDATE system_settings SET db_schema_version='1641',db_schema_update_date=NOW() where db_schema_version < 1641;

ALTER TABLE phones MODIFY protocol ENUM('SIP','PJSIP','Zap','IAX2','EXTERNAL') default 'SIP';

ALTER TABLE vicidial_server_carriers MODIFY protocol ENUM('SIP','PJSIP','PJSIP_WIZ','Zap','IAX2','EXTERNAL') default 'SIP';

ALTER TABLE vicidial_peer_event_log MODIFY channel_type ENUM('IAX2','SIP','PJSIP') COLLATE utf8_unicode_ci DEFAULT NULL;

ALTER TABLE system_settings ADD allowed_sip_stacks ENUM('SIP','PJSIP','SIP_and_PJSIP') default 'SIP';

UPDATE system_settings SET db_schema_version='1642',db_schema_update_date=NOW() where db_schema_version < 1642;

CREATE INDEX vavl_agent_log_id on vicidial_agent_visibility_log (agent_log_id);

UPDATE system_settings SET db_schema_version='1643',db_schema_update_date=NOW() where db_schema_version < 1643;

ALTER TABLE vicidial_inbound_dids MODIFY call_handle_method VARCHAR(40) default 'CID';
ALTER TABLE vicidial_inbound_dids MODIFY filter_call_handle_method VARCHAR(40) default 'CID';

UPDATE system_settings SET db_schema_version='1644',db_schema_update_date=NOW() where db_schema_version < 1644;

ALTER TABLE vicidial_inbound_groups ADD populate_lead_owner VARCHAR(20) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1645',db_schema_update_date=NOW() where db_schema_version < 1645;

ALTER TABLE vicidial_campaigns ADD incall_tally_threshold_seconds SMALLINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1646',db_schema_update_date=NOW() where db_schema_version < 1646;

ALTER TABLE vicidial_inbound_groups ADD in_queue_nanque ENUM('N','Y','NO_PAUSED','NO_PAUSED_EXCEPTIONS','NO_READY') default 'N';
ALTER TABLE vicidial_inbound_groups ADD in_queue_nanque_exceptions VARCHAR(40) default '';

UPDATE system_settings SET db_schema_version='1647',db_schema_update_date=NOW() where db_schema_version < 1647;

ALTER TABLE vicidial_url_multi ADD url_call_length SMALLINT(5) default '0';

UPDATE system_settings SET db_schema_version='1648',db_schema_update_date=NOW() where db_schema_version < 1648;

ALTER TABLE vicidial_users ADD user_location VARCHAR(100) default '';

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('USER_LOCATIONS_SYSTEM','User Locations List','OTHER','---ALL---',';location|description\n|default\n');

CREATE TABLE vicidial_queue_groups (
queue_group VARCHAR(20) NOT NULL,
queue_group_name VARCHAR(40) NOT NULL,
included_campaigns TEXT,
included_inbound_groups TEXT,
user_group VARCHAR(20) default '---ALL---',
active ENUM('Y','N')
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1649',db_schema_update_date=NOW() where db_schema_version < 1649;

ALTER TABLE vicidial_user_groups ADD allowed_queue_groups TEXT;
ALTER TABLE vicidial_user_groups ADD reports_header_override ENUM('DISABLED','LOGO_ONLY_SMALL','LOGO_ONLY_LARGE','ALT_1','ALT_2','ALT_3','ALT_4') default 'DISABLED';
ALTER TABLE vicidial_user_groups ADD admin_home_url VARCHAR(255) default '';

UPDATE system_settings SET db_schema_version='1650',db_schema_update_date=NOW() where db_schema_version < 1650;

ALTER TABLE vicidial_campaigns ADD auto_alt_threshold TINYINT(3) UNSIGNED default '0';

ALTER TABLE vicidial_lists ADD auto_alt_threshold TINYINT(3) default '-1';

UPDATE system_settings SET db_schema_version='1651',db_schema_update_date=NOW() where db_schema_version < 1651;

ALTER TABLE vicidial_users ADD download_invalid_files ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1652',db_schema_update_date=NOW() where db_schema_version < 1652;

ALTER TABLE vicidial_campaigns ADD pause_max_url TEXT;

UPDATE system_settings SET db_schema_version='1653',db_schema_update_date=NOW() where db_schema_version < 1653;

ALTER TABLE system_settings MODIFY script_remove_js ENUM('1','0','2','3','4','5','6') default '1';
ALTER TABLE system_settings ADD agent_hide_hangup ENUM('1','0','2','3','4','5','6') default '0';

ALTER TABLE vicidial_campaigns ADD agent_hide_hangup ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1654',db_schema_update_date=NOW() where db_schema_version < 1654;

ALTER TABLE system_settings ADD allow_web_debug ENUM('0','1','2','3','4','5','6') default '0';

UPDATE system_settings SET db_schema_version='1655',db_schema_update_date=NOW() where db_schema_version < 1655;

ALTER TABLE vicidial_campaigns MODIFY enable_xfer_presets ENUM('DISABLED','ENABLED','STAGING','CONTACTS') default 'DISABLED';

CREATE TABLE vicidial_sync_log (
user VARCHAR(20) default '',
start_time DATETIME NOT NULL,
db_time DATETIME NOT NULL,
run_time VARCHAR(20) default '0',
php_script VARCHAR(40) NOT NULL,
action VARCHAR(100) default '',
lead_id INT(10) UNSIGNED default '0',
stage VARCHAR(200) default '',
session_name VARCHAR(40) default '',
last_sql TEXT,
KEY ajax_dbtime_key (db_time)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1656',db_schema_update_date=NOW() where db_schema_version < 1656;

ALTER TABLE vicidial_lists ADD cid_group_id VARCHAR(20) default '---DISABLED---';

CREATE TABLE vicidial_dial_cid_log (
caller_code VARCHAR(30) NOT NULL,
call_date DATETIME,
call_type ENUM('OUT','OUTBALANCE','MANUAL','OVERRIDE','3WAY') default 'OUT',
call_alt VARCHAR(20) default '',
outbound_cid VARCHAR(20) default '',
outbound_cid_type VARCHAR(20) default '',
index (caller_code),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_dial_cid_log_archive LIKE vicidial_dial_cid_log;
CREATE UNIQUE INDEX caller_code_date on vicidial_dial_cid_log_archive (caller_code,call_date);

UPDATE system_settings SET db_schema_version='1657',db_schema_update_date=NOW() where db_schema_version < 1657;

ALTER TABLE vicidial_campaigns ADD ig_xfer_list_sort ENUM('GROUP_ID_UP','GROUP_ID_DOWN','GROUP_NAME_UP','GROUP_NAME_DOWN','PRIORITY_UP','PRIORITY_DOWN') default 'GROUP_ID_UP';

UPDATE system_settings SET db_schema_version='1658',db_schema_update_date=NOW() where db_schema_version < 1658;

ALTER TABLE vicidial_inbound_dids ADD pre_filter_recent_call VARCHAR(20) default '';
ALTER TABLE vicidial_inbound_dids ADD pre_filter_recent_extension VARCHAR(50) default '';

UPDATE system_settings SET db_schema_version='1659',db_schema_update_date=NOW() where db_schema_version < 1659;

ALTER TABLE vicidial_campaigns ADD script_tab_frame_size VARCHAR(10) default 'DEFAULT';

UPDATE system_settings SET db_schema_version='1660',db_schema_update_date=NOW() where db_schema_version < 1660;

ALTER TABLE vicidial_users ADD user_group_two VARCHAR(20) default '';

UPDATE system_settings SET db_schema_version='1661',db_schema_update_date=NOW() where db_schema_version < 1661;

ALTER TABLE vicidial_lists ADD dial_prefix VARCHAR(20) default '';

ALTER TABLE system_settings ADD max_logged_in_agents ENUM('0','1','2','3','4','5','6','7') default '0';

ALTER TABLE vicidial_campaigns ADD max_logged_in_agents SMALLINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1662',db_schema_update_date=NOW() where db_schema_version < 1662;

ALTER TABLE system_settings ADD user_codes_admin ENUM('0','1','2','3','4','5','6','7') default '0';

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('USER_CODES_SYSTEM','User Codes List','OTHER','---ALL---','');

UPDATE system_settings SET db_schema_version='1663',db_schema_update_date=NOW() where db_schema_version < 1663;

ALTER TABLE servers ADD `conf_engine` ENUM('MEETME','CONFBRIDGE') COLLATE utf8_unicode_ci DEFAULT 'MEETME';
ALTER TABLE servers ADD `conf_update_interval` SMALLINT(6) NOT NULL DEFAULT 60;

CREATE TABLE `vicidial_confbridges` (   
`conf_exten` INT(7) UNSIGNED NOT NULL,
`server_ip` VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
`extension` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`leave_3way` ENUM('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
`leave_3way_datetime` DATETIME DEFAULT NULL,
UNIQUE KEY `serverconf` (`server_ip`,`conf_exten`),
UNIQUE KEY `conf_exten` (`conf_exten`,`server_ip`) 
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

UPDATE system_settings SET db_schema_version='1664',db_schema_update_date=NOW() where db_schema_version < 1664;

CREATE TABLE `verm_custom_report_holder` (
`custom_report_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`user` VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
`report_name` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`report_parameters` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
`modify_date` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
PRIMARY KEY (`custom_report_id`),
UNIQUE KEY `verm_custom_report_holder_pkey` (`user`,`report_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `wallboard_widgets` (
`widget_id` VARCHAR(100) COLLATE utf8_unicode_ci NOT NULL,
`wallboard_report_id` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`view_id` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_title` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_type` VARCHAR(30) COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_width` TINYINT(3) UNSIGNED DEFAULT NULL,
`widget_is_row` ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
`widget_rowspan` TINYINT(3) UNSIGNED DEFAULT 1,
`widget_text` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_queue` VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_sla_level` VARCHAR(5) COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_agent` VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_color` VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_color2` VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_alarms` TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
`widget_order` TINYINT(3) UNSIGNED DEFAULT NULL,
PRIMARY KEY (`widget_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `wallboard_reports` (
`wallboard_report_id` VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
`wallboard_name` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`wallboard_views` TINYINT(3) UNSIGNED DEFAULT NULL,
`date_created` DATETIME DEFAULT NULL,
`last_modified` TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
`data_refresh_rate` SMALLINT(5) UNSIGNED DEFAULT 10,
`view_refresh_rate` SMALLINT(5) UNSIGNED DEFAULT 30,
PRIMARY KEY (`wallboard_report_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `wallboard_widgets` VALUES ('queues_widget_1','AGENTS_AND_QUEUES','queues','','TEXT',5,'N',1,'Queue Information','','',NULL,'','',NULL,2),('queues_widget_0','AGENTS_AND_QUEUES','queues','','LOGO',2,'N',1,NULL,'','',NULL,'','',NULL,1),('queues_widget_2','AGENTS_AND_QUEUES','queues','SLA Level %','SLA_LEVEL_PCT',1,'N',1,NULL,'','>60',NULL,'','',NULL,3),('queues_widget_3','AGENTS_AND_QUEUES','queues','Outbound calls','LIVE_QUEUE_INFO',1,'N',1,'','201201','','','','','yellow_alarm,|red_alarm,',4),('queues_widget_4','AGENTS_AND_QUEUES','queues','USA Ded Inbound','LIVE_QUEUE_INFO',1,'N',1,'','ALL_IN','','','','','yellow_alarm,|red_alarm,',5),('queues_widget_5','AGENTS_AND_QUEUES','queues','MLA Ded Inbound','LIVE_QUEUE_INFO',1,'N',1,'','SALESLINE','','','','','yellow_alarm,|red_alarm,',6),('queues_widget_6','AGENTS_AND_QUEUES','queues','N Waiting Calls','N_WAITING_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,7),('queues_widget_7','AGENTS_AND_QUEUES','queues','Offered Calls','OFFERED_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,8),('queues_widget_8','AGENTS_AND_QUEUES','queues','Answered Calls','ANSWERED_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,9),('queues_widget_9','AGENTS_AND_QUEUES','queues','Lost Calls','LOST_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,10),('queues_widget_10','AGENTS_AND_QUEUES','queues','Longest Wait','LONGEST_WAIT',1,'N',1,NULL,'','',NULL,'','',NULL,11),('queues_widget_11','AGENTS_AND_QUEUES','queues','Live Queues','LIVE_QUEUES',1,'Y',1,NULL,'','',NULL,'','',NULL,12),('queues_widget_12','AGENTS_AND_QUEUES','queues','Live Calls','LIVE_CALLS',1,'Y',2,NULL,'','',NULL,'','',NULL,13),('agent_widget_0','AGENTS_AND_QUEUES','agents','','LOGO',2,'N',1,NULL,'','',NULL,'','',NULL,1),('agent_widget_1','AGENTS_AND_QUEUES','agents','N Waiting Calls','N_WAITING_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,2),('agent_widget_2','AGENTS_AND_QUEUES','agents','Agents Ready','AGENTS_READY',1,'N',1,NULL,'','',NULL,'','',NULL,3),('agent_widget_3','AGENTS_AND_QUEUES','agents','Agents On Call','N_AGENTS_ON_CALL',1,'N',1,NULL,'','',NULL,'','',NULL,4),('agent_widget_4','AGENTS_AND_QUEUES','agents','N Answered Calls','N_ANSWERED_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,5),('agent_widget_5','AGENTS_AND_QUEUES','agents','Clock','CLOCK',1,'N',1,NULL,'','',NULL,'','',NULL,6),('agent_widget_6','AGENTS_AND_QUEUES','agents','Live Agents','LIVE_AGENTS',1,'Y',3,NULL,'','',NULL,'','',NULL,7);

INSERT INTO `wallboard_reports` VALUES ('AGENTS_AND_QUEUES','Agents and Queues',2,'2022-01-18 09:00:23','2022-01-18 15:00:23',10,30);

INSERT INTO `vicidial_settings_containers` VALUES ('VERM_STATUS_NAMES_OVERRIDE','Override dialer status names in enhanced reporting','OTHER','---ALL---','; For each status name you want overridden, type the status followed by\r\n; a pipe, then the new status name\r\n; Ex:\r\n; NZ|Taumatawhakatangihangakoauauotamateaturipukakapikimaungahoronukupoka\r\n201214|Request To Cancel\r\n210200|No Answer-Incomplete Call\r\n210201|Contact Established\r\n210202|Provider Review - HB\r\n210203|Promise to Pay\r\n210204|Setup Payment Plan\r\n210205|Research-Inquiry\r\n210206|Voice Mail Left - HB\r\n210207|Do Not Call\r\n210208|Appeal Verification\r\n210209|Bad Phone\r\n210210|Bad Address\r\n210211|Direct Pay Verification\r\n210213|Provider Approved\r\n210215|Update Notes Only\r\n210216|Voicemail-No Status Change\r\n210217|Sent Letter Request - HB\r\n210218|Auto VoiceMail Left - HB\r\n210219|Auto VoiceMail-No Status Change\r\n210302|Provider Review - LB\r\n210306|Voice Mail Left - LB\r\n210317|Sent Letter Request - LB\r\n210318|Auto VoiceMail Left - LB\r\n211503|Provider - COVID-19\r\n211603|Transferred Call to MLA\r\n'), ('VERM_REPORT_OPTIONS','Container for customizing VERM report output','OTHER','---ALL---','; This is the report queue used if none is chosen by the user\r\n; It\'s preloaded in some forms as well\r\nVERM_default_report_queue => ALL\r\n\r\n; If there are statuses to exclude from reports, list them here\r\n; Separate with commas.  Default is AFTHRS\r\nexc_addtl_statuses => AFTHRS\r\n\r\n; Set the below value to 1 (or anything non-blank/non-zero) in order to \r\n; show the agents ID in addition to their full name in the report results\r\nshow_full_agent_info => 1\r\n\r\n; Some reports count \"lost\" calls - which are defined by the below variable\r\n; listing what you define as \"lost\" dispos.  Separate with commas.\r\nlost_statuses => LOST,210208,DISPO\r\n\r\n; You can create a detailed IVR survey report for ingroups by defining\r\n; \"ivr_survey_ingroups_detail\" and \"ivr_survey_ingroups_voicemails.\"\r\n; For \"details\", supply an ingroup used as a tracking group on call menus.\r\n; Then, add a pipe and after that list all call menus that use the ingroup\r\n; as the tracking group, separating each with a comma\r\n; To track whether the calls went to voicemail, list every call menu/option\r\n; combination that goes to voicemail, separating the call menu from the \r\n; option with a pipe.  One callmenu/option combo per line.\r\nivr_survey_ingroups_detail => 521205|561401,561402,561403,561404,561505\r\nivr_survey_ingroups_voicemails => 561505|t\r\n\r\n; #####################################################\r\n; # ALL of the below are used in the wallboard report #\r\n; #####################################################\r\nVERM_default_outb_widget_queue => ALL_OUT\r\nVERM_default_inb_widget_queue1 => SALESLINE\r\nVERM_default_inb_widget_queue2 => 515915v_MLA_Shared\r\n\r\n; Used specifically for the SLA widget\r\n; Uses ingroups - separate multiple ingroups by commas\r\n; Comment out or leave blank to count all ingroups\r\nSLA_LEVEL_PCT_ingroups => SALESLINE,SUPPORT\r\n\r\n; This removes remote agents from the wallboard reports\r\n; Comment out to include remote agents (or set to zero)\r\nomit_remote_agents => 1\r\n');

UPDATE system_settings SET db_schema_version='1665',db_schema_update_date=NOW() where db_schema_version < 1665;

ALTER TABLE vicidial_user_groups ADD script_id VARCHAR(20) default '';

ALTER TABLE vicidial_campaigns ADD user_group_script ENUM('DISABLED','ENABLED') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1666',db_schema_update_date=NOW() where db_schema_version < 1666;

ALTER TABLE vicidial_users ADD failed_login_attempts_today MEDIUMINT(8) UNSIGNED default '0';
ALTER TABLE vicidial_users ADD failed_login_count_today SMALLINT(6) UNSIGNED default '0';
ALTER TABLE vicidial_users ADD failed_last_ip_today VARCHAR(50) default '';
ALTER TABLE vicidial_users ADD failed_last_type_today VARCHAR(20) default '';

CREATE TABLE vicidial_user_logins_daily (
user VARCHAR(20),
login_day DATE,
last_login_date DATETIME default '2001-01-01 00:00:01',
last_ip VARCHAR(50) default '',
failed_login_attempts_today MEDIUMINT(8) UNSIGNED default '0',
failed_login_count_today SMALLINT(6) UNSIGNED default '0',
failed_last_ip_today VARCHAR(50) default '',
failed_last_type_today VARCHAR(20) default '',
index (user)
) ENGINE=MyISAM;

CREATE UNIQUE INDEX vicidial_user_logins_daily_user on vicidial_user_logins_daily(login_day, user);

UPDATE system_settings SET db_schema_version='1667',db_schema_update_date=NOW() where db_schema_version < 1667;

ALTER TABLE system_settings ADD login_kickall ENUM('0','1','2','3','4','5','6','7') default '0';

UPDATE system_settings SET db_schema_version='1668',db_schema_update_date=NOW() where db_schema_version < 1668;

ALTER TABLE phones ADD webphone_settings VARCHAR(40) default 'VICIPHONE_SETTINGS';

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('VICIPHONE_SETTINGS','VICIphone WebRTC Extra Settings','WEBPHONE_SETTINGS','---ALL---','# determines if automatic gain control is enabled\nautoGain : 0\n\n# determines if echo cancellation is enabled\nechoCan : 0\n\n# determines if noise suppression is enabled\nnoiseSup :0\n\n# determines if the reg_exten is called upon successful registration\ndialRegExten : 1\n\n# determines the regional sound to use for progress audio\nprogReg : na\n\n# English translation phrases\nlangAttempting:"Attempting"\nlangConnected:"WS Connected"\nlangDisconnected:"WS Disconnected"\nlangExten:"Extension"\nlangIncall:"Incall"\nlangInit:"Initializing..."\nlangRedirect:"Redirect"\nlangRegFailed:"Reg. Failed"\nlangRegistering:"Registering"\nlangRegistered:"Registered"\nlangReject:"Rejected"\nlangRinging:"Ringing"\nlangSend:"Send"\nlangTrying:"Trying"\nlangUnregFailed:"Unreg. Failed"\nlangUnregistered:"Unregistered"\nlangUnregistering:"Unregistering"\nlangWebrtcError:"Something went wrong with WebRTC. Either your browser does not support the necessary WebRTC functions, you did not allow your browser to access the microphone, or there is a configuration issue. Please check your browsers error console for more details. For a list of compatible browsers please vist http://webrtc.org/"');

UPDATE system_settings SET db_schema_version='1669',db_schema_update_date=NOW() where db_schema_version < 1669;

CREATE TABLE vicidial_long_extensions (
le_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
extension VARCHAR(1000),
call_date DATETIME default '2001-01-01 00:00:01',
source VARCHAR(20) default '',
index (call_date)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1670',db_schema_update_date=NOW() where db_schema_version < 1670;

CREATE INDEX vicq_group_id on vicidial_inbound_callback_queue(group_id);
CREATE INDEX vicq_icbq_date on vicidial_inbound_callback_queue(icbq_date);
CREATE INDEX vicq_call_date on vicidial_inbound_callback_queue(call_date);

CREATE INDEX vicqa_group_id on vicidial_inbound_callback_queue_archive(group_id);
CREATE INDEX vicqa_icbq_date on vicidial_inbound_callback_queue_archive(icbq_date);
CREATE INDEX vicqa_call_date on vicidial_inbound_callback_queue_archive(call_date);

UPDATE system_settings SET db_schema_version='1671',db_schema_update_date=NOW() where db_schema_version < 1671;

ALTER TABLE vicidial_inbound_groups MODIFY group_color VARCHAR(20);

ALTER TABLE vicidial_scripts MODIFY script_color VARCHAR(20) default 'white';

UPDATE system_settings SET db_schema_version='1672',db_schema_update_date=NOW() where db_schema_version < 1672;

CREATE TABLE vicidial_postal_codes_cities (
postal_code VARCHAR(10) NOT NULL,
state VARCHAR(4),
city VARCHAR(60),
county VARCHAR(60),
latitude VARCHAR(17),
longitude VARCHAR(17),
areacode CHAR(3),
country_code SMALLINT(5) UNSIGNED,
country CHAR(3),
index (postal_code)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1673',db_schema_update_date=NOW() where db_schema_version < 1673;

ALTER TABLE vicidial_inbound_groups MODIFY on_hook_cid VARCHAR(30) default 'CUSTOMER_PHONE_RINGAGENT';

UPDATE system_settings SET db_schema_version='1674',db_schema_update_date=NOW() where db_schema_version < 1674;

ALTER TABLE vicidial_campaigns ADD agent_hangup_route ENUM('HANGUP','MESSAGE','EXTENSION','IN_GROUP','CALLMENU') default 'HANGUP';
ALTER TABLE vicidial_campaigns ADD agent_hangup_value TEXT;
ALTER TABLE vicidial_campaigns ADD agent_hangup_ig_override ENUM('Y','N') default 'N';
UPDATE vicidial_campaigns SET agent_hangup_value='' where agent_hangup_value IS NULL;

UPDATE system_settings SET db_schema_version='1675',db_schema_update_date=NOW() where db_schema_version < 1675;

CREATE TABLE gateway_recording_log (
gateway_recording_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
recording_log_id INT(10) UNSIGNED default '0',
call_direction ENUM('INBOUND','OUTBOUND','NA') default 'NA',
call_id VARCHAR(40) default '',
lead_id INT(9) UNSIGNED,
uniqueid VARCHAR(20) NOT NULL,
server_ip VARCHAR(15),
caller_id_number VARCHAR(18),
caller_id_name VARCHAR(20),
extension VARCHAR(100),
start_time DATETIME,
end_time DATETIME,
length_in_sec MEDIUMINT(8) UNSIGNED default '0',
filename VARCHAR(100),
location VARCHAR(255),
index(start_time),
index(call_id),
index(lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_did_gateway_log (
uniqueid VARCHAR(20) NOT NULL,
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
caller_id_number VARCHAR(18),
caller_id_name VARCHAR(20),
extension VARCHAR(100),
call_date DATETIME,
VICIrecGatewayID VARCHAR(30) default '',
index (uniqueid),
index (VICIrecGatewayID),
index (extension),
index (call_date)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1676',db_schema_update_date=NOW() where db_schema_version < 1676;

update vicidial_settings_containers set container_entry=CONCAT(container_entry, '\r\n\r\n; #### AUTO DOWNLOAD ####\r\n; If the "total calls" value on any report requested exceeds the below \r\n; limit, automatically download the three "DETAILS" reports instead\r\n; of attempting to display that many records on-screen\r\nauto_download_limit => 50000\r\n\r\n; #### OUTCOMES report overrides ####\r\n; Use "outcome_lagged_status_overrides" for conditions where the call \r\n; record in the vicidial_log or vicidial_closer_log table has no uniqueid\r\n; value despite having a status/outcome, which can indicate a call \r\n; affected by network lag for certain statuses.  This will change the call \r\n; status to "LAGGED".  Separate statuses with commas.  Default is the \r\n; automatic "PU" status.\r\noutcome_lagged_status_overrides => PU\r\n\r\n; Use "unknown_network_statuses" to change call statuses to read "Network/\r\n; LAGGED" on the OUTCOMES report. Separate statuses with commas.\r\n; IMPORTANT: if you are using the outcome_lagged_status_overrides option \r\n; above, make sure "LAGGED" is one of the unknown_network_statuses here\r\n; unknown_network_statuses => LAGGED\r\n\r\n; Use "outcome_status_overrides" to change one status to another on the \r\n; OUTCOMES report.  Overrides are comma-separated pairs of dispositions  \r\n; where the first disposition is the disposition to change, and the second\r\n; is the disposition to change to.  Separate pairs with a pipe character as\r\n; in the below example.  Off by default.\r\n; outcome_status_overrides => CBHOLD,DISPO|XFER,AL') WHERE container_id='VERM_REPORT_OPTIONS' and container_entry NOT LIKE "%outcome_status_overrides%";

UPDATE system_settings SET db_schema_version='1677',db_schema_update_date=NOW() where db_schema_version < 1677;

ALTER TABLE servers ADD ara_url TEXT;

ALTER TABLE vicidial_campaigns ADD show_confetti ENUM('DISABLED','SALES','CALLBACKS','SALES_AND_CALLBACKS') default 'DISABLED';

INSERT INTO vicidial_settings_containers (container_id,container_notes,container_type,user_group,container_entry) VALUES ('CONFETTI_SETTINGS', 'Confetti settings for screen display', 'OTHER', '---ALL---', '; Confetti settings, to add visual interest to certain events\r\n; duration is how long the confetti animation runs, maxParticleCount is the\r\n; max number of confetti \"pieces\", and particleSpeed is how fast they float\r\nduration => 2\r\nmaxParticleCount => 2350\r\nparticleSpeed => 2\r\n');

ALTER TABLE system_settings ADD abandon_check_queue ENUM('0','1','2','3','4','5','6','7') default '0';

CREATE TABLE vicidial_abandon_check_queue (
abandon_check_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED,
phone_number VARCHAR(18) default '',
call_id VARCHAR(40) default '',
abandon_time DATETIME,
dispo VARCHAR(6),
check_status ENUM('NEW','REJECT','QUEUE','PROCESSING','COMPLETE','CONNECTED','ARCHIVE') default 'NEW',
reject_reason VARCHAR(40) default '',
source VARCHAR(20),
index(abandon_time),
index(phone_number),
index(lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_abandon_check_queue_archive LIKE vicidial_abandon_check_queue;
ALTER TABLE vicidial_abandon_check_queue_archive MODIFY abandon_check_id INT(9) UNSIGNED NOT NULL;

UPDATE system_settings SET db_schema_version='1678',db_schema_update_date=NOW() where db_schema_version < 1678;

ALTER TABLE system_settings ADD agent_notifications ENUM('0','1','2','3','4','5','6','7') default '0';

CREATE TABLE vicidial_agent_notifications (
notification_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
entry_date DATETIME DEFAULT current_timestamp(),
recipient VARCHAR(20) DEFAULT NULL,
recipient_type ENUM('USER','USER_GROUP','CAMPAIGN','ALT_DISPLAY') DEFAULT NULL,
notification_date DATETIME DEFAULT current_timestamp(),
notification_retry ENUM('Y','N') DEFAULT 'N',
notification_text TEXT DEFAULT NULL,
text_size TINYINT(3) UNSIGNED DEFAULT 12,
text_font VARCHAR(30) DEFAULT 'Arial',
text_weight VARCHAR(30) DEFAULT 'bold',
text_color VARCHAR(15) DEFAULT NULL,
show_confetti ENUM('Y','N') DEFAULT 'N',
confetti_options VARCHAR(15) DEFAULT NULL,
notification_status ENUM('QUEUED','READY','SENT','DEAD') DEFAULT NULL,
PRIMARY KEY (notification_id),
KEY recipient (recipient),
KEY notification_date (notification_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_notifications_archive LIKE vicidial_agent_notifications;
ALTER TABLE vicidial_agent_notifications_archive MODIFY notification_id INT(10) UNSIGNED NOT NULL;

CREATE TABLE vicidial_agent_notifications_queue (
queue_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
notification_id INT(10) UNSIGNED DEFAULT NULL,
queue_date DATETIME DEFAULT current_timestamp(),
user VARCHAR(20) DEFAULT NULL,
PRIMARY KEY (queue_id)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1679',db_schema_update_date=NOW() where db_schema_version < 1679;

INSERT INTO vicidial_statuses (status,status_name) VALUES ('ADAIR', 'Dead Air Auto');

UPDATE system_settings SET db_schema_version='1680',db_schema_update_date=NOW() where db_schema_version < 1680;

CREATE TABLE vicidial_user_dial_log (
caller_code VARCHAR(30) NOT NULL,
user VARCHAR(20) default '',
call_date DATETIME,
call_type VARCHAR(10) default '',
notes VARCHAR(100) default '',
index (caller_code),
index (user),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_user_dial_log_archive LIKE vicidial_user_dial_log;
CREATE UNIQUE INDEX vdudl on vicidial_user_dial_log_archive (caller_code,call_date,user);

UPDATE system_settings SET db_schema_version='1681',db_schema_update_date=NOW() where db_schema_version < 1681;

CREATE TABLE vicidial_live_agents_details (
user VARCHAR(20) NOT NULL PRIMARY KEY,
update_date DATETIME,
web_ip VARCHAR(45) default '',
latency MEDIUMINT(7) default '0',
latency_min_avg MEDIUMINT(7) default '0',
latency_min_peak MEDIUMINT(7) default '0',
latency_hour_avg MEDIUMINT(7) default '0',
latency_hour_peak MEDIUMINT(7) default '0',
latency_today_avg MEDIUMINT(7) default '0',
latency_today_peak MEDIUMINT(7) default '0',
index (user),
index (update_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_latency_log (
user VARCHAR(20) NOT NULL,
log_date DATETIME,
latency MEDIUMINT(7) default '0',
web_ip VARCHAR(45) default '',
index (user),
index (log_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_latency_summary_log (
user VARCHAR(20) NOT NULL,
log_date DATETIME,
web_ip VARCHAR(45) default '',
latency_avg MEDIUMINT(7) default '0',
latency_peak MEDIUMINT(7) default '0',
latency_count SMALLINT(4) default '0',
index (user),
index (log_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_latency_log_archive LIKE vicidial_agent_latency_log;
CREATE UNIQUE INDEX vdalla on vicidial_agent_latency_log_archive (user,log_date);

CREATE TABLE vicidial_agent_latency_summary_log_archive LIKE vicidial_agent_latency_summary_log;
CREATE UNIQUE INDEX vdalsla on vicidial_agent_latency_summary_log_archive (user,log_date,web_ip);

UPDATE system_settings SET db_schema_version='1682',db_schema_update_date=NOW() where db_schema_version < 1682;

ALTER TABLE system_settings ADD demographic_quotas ENUM('0','1','2','3','4','5','6','7') default '0';
ALTER TABLE system_settings ADD log_latency_gaps ENUM('0','1','2','3','4','5','6','7') default '1';

ALTER TABLE vicidial_campaigns ADD demographic_quotas ENUM('DISABLED','ENABLED','INVALID','COMPLETE') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD demographic_quotas_container VARCHAR(40) default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD demographic_quotas_rerank ENUM('NO','NOW','HOUR','MINUTE','NOW_HOUR') default 'NO';
ALTER TABLE vicidial_campaigns ADD demographic_quotas_last_rerank DATETIME default '2000-01-01 00:00:00';
ALTER TABLE vicidial_campaigns ADD demographic_quotas_list_resets ENUM('AUTO','MANUAL') default 'MANUAL';

CREATE TABLE vicidial_demographic_quotas_goals (
vdqg_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
campaign_id VARCHAR(8) default '',
demographic_quotas_container VARCHAR(40) default '',
quota_field VARCHAR(20) default '',
quota_field_order TINYINT(3) default '0',
quota_value VARCHAR(100) default '',
quota_value_order TINYINT(3) default '0',
quota_goal MEDIUMINT(7) default '0',
quota_count MEDIUMINT(7) default '0',
quota_leads_total MEDIUMINT(7) default '0',
quota_leads_active MEDIUMINT(7) default '0',
quota_status VARCHAR(10) default 'ACTIVE',
quota_modify_date DATETIME,
last_lead_id INT(9) UNSIGNED default '0',
last_list_id BIGINT(14) UNSIGNED default '0',
last_call_date DATETIME,
last_status VARCHAR(6) default '',
index(campaign_id),
index(quota_field),
index(quota_value),
unique index vdqgi (campaign_id,quota_field,quota_field_order,quota_value,quota_value_order)
) ENGINE=MyISAM;

CREATE TABLE vicidial_latency_gaps (
user VARCHAR(20) default '',
user_ip VARCHAR(45) default '',
gap_date DATETIME,
gap_length MEDIUMINT(5) UNSIGNED default '0',
last_login_date DATETIME,
check_date DATETIME,
index(user),
index(gap_date),
index(check_date),
unique index vlgi (user,gap_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_latency_gaps_archive LIKE vicidial_latency_gaps;
CREATE UNIQUE INDEX vdlga on vicidial_latency_gaps_archive (user,gap_date);

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('HOPPER_CLI_FLAGS', 'Comand-line flags for hopper process', 'PERL_CLI', '---ALL---', '');

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('AGENT_LATENCY_LOGGING','Default agent latency logging settings','PERL_CLI','---ALL---','minimum_gap => 30\r\nemail_sender => \r\nemail_list => \r\nemail_subject => Agent Network Alert');

UPDATE system_settings SET db_schema_version='1683',db_schema_update_date=NOW() where db_schema_version < 1683;

ALTER TABLE vicidial_campaigns ADD custom_one TEXT;
ALTER TABLE vicidial_campaigns ADD custom_two TEXT;
ALTER TABLE vicidial_campaigns ADD custom_three TEXT;
ALTER TABLE vicidial_campaigns ADD custom_four TEXT;
ALTER TABLE vicidial_campaigns ADD custom_five TEXT;

ALTER TABLE vicidial_inbound_groups ADD custom_one TEXT;
ALTER TABLE vicidial_inbound_groups ADD custom_two TEXT;
ALTER TABLE vicidial_inbound_groups ADD custom_three TEXT;
ALTER TABLE vicidial_inbound_groups ADD custom_four TEXT;
ALTER TABLE vicidial_inbound_groups ADD custom_five TEXT;

UPDATE vicidial_campaigns SET custom_one='' WHERE custom_one IS NULL;
UPDATE vicidial_campaigns SET custom_two='' WHERE custom_two IS NULL;
UPDATE vicidial_campaigns SET custom_three='' WHERE custom_three IS NULL;
UPDATE vicidial_campaigns SET custom_four='' WHERE custom_four IS NULL;
UPDATE vicidial_campaigns SET custom_five='' WHERE custom_five IS NULL;

UPDATE vicidial_inbound_groups SET custom_one='' WHERE custom_one IS NULL;
UPDATE vicidial_inbound_groups SET custom_two='' WHERE custom_two IS NULL;
UPDATE vicidial_inbound_groups SET custom_three='' WHERE custom_three IS NULL;
UPDATE vicidial_inbound_groups SET custom_four='' WHERE custom_four IS NULL;
UPDATE vicidial_inbound_groups SET custom_five='' WHERE custom_five IS NULL;

UPDATE system_settings SET db_schema_version='1684',db_schema_update_date=NOW() where db_schema_version < 1684;

ALTER TABLE vicidial_users ADD modify_dial_prefix ENUM('0','1','2','3','4','5','6') default '0';

UPDATE vicidial_users, system_settings SET modify_dial_prefix='1' where campaign_detail='1' and user_level >= 8 and active='Y' and db_schema_version < 1685;

UPDATE system_settings SET db_schema_version='1685',db_schema_update_date=NOW() where db_schema_version < 1685;

ALTER TABLE system_settings ADD inbound_credits ENUM('0','1','2','3','4','5','6','7') default '0';

ALTER TABLE vicidial_users ADD inbound_credits MEDIUMINT(7) default '-1';

UPDATE system_settings SET db_schema_version='1686',db_schema_update_date=NOW() where db_schema_version < 1686;

ALTER TABLE system_settings ADD weekday_resets ENUM('0','1','2','3','4','5','6','7') default '0';

ALTER TABLE vicidial_lists ADD weekday_resets_container VARCHAR(40) default 'DISABLED';

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('EXAMPLE_LIST_WEEKDAY_RESETS','Example Weekday List Resets Container settings','LIST_WEEKDAY_RESETS','---ALL---','; weekday => reset-times in 24-hour time separated by dashes\r\nmonday => 0830-1230-1800\r\ntuesday => 0900-1400-1830\r\nwednesday => 0930-1200-1730-1900\r\nthursday => 1030-1330-1900\r\nfriday => 0800-1300-1530\r\nsaturday => 0930-1100\r\nsunday => 1000');

UPDATE system_settings SET db_schema_version='1687',db_schema_update_date=NOW() where db_schema_version < 1687;

ALTER TABLE vicidial_inbound_groups ADD second_alert_trigger VARCHAR(20) default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD second_alert_trigger_seconds INT(6) default '600';
ALTER TABLE vicidial_inbound_groups ADD second_alert_filename VARCHAR(100) default '';
ALTER TABLE vicidial_inbound_groups ADD second_alert_delay INT(6) default '1000';

UPDATE system_settings SET db_schema_version='1688',db_schema_update_date=NOW() where db_schema_version < 1688;

ALTER TABLE vicidial_inbound_groups ADD second_alert_container VARCHAR(40) default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD second_alert_only VARCHAR(40) default 'DISABLED';

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('EXAMPLE_VID_PROMPT_SPECIAL','Example Call Menu VID Special Container settings','CM_VIDPROMPT_SPECIAL','---ALL---','list_ingroup => 102,TEST_IN2\r\nlist_ingroup => 103,TEST_IN3\r\nlist_ingroup => 104,TEST_IN4\r\ndefault_ingroup => TEST_IN5\r\nnot_found_action => new_lead\r\nnew_lead_ingroup => TEST_IN');

UPDATE system_settings SET db_schema_version='1689',db_schema_update_date=NOW() where db_schema_version < 1689;

ALTER TABLE vicidial_campaigns ADD dead_stop_recording ENUM('DISABLED','ALL_CALLS','OUTBOUND_ONLY','INBOUND_ONLY','AUTODIAL_ONLY','MANUAL_ONLY') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1690',db_schema_update_date=NOW() where db_schema_version < 1690;

ALTER TABLE vicidial_campaigns ADD manual_vm_status_updates ENUM('ENABLED','DISABLED') default 'ENABLED';

UPDATE system_settings SET db_schema_version='1691',db_schema_update_date=NOW() where db_schema_version < 1691;

ALTER TABLE vicidial_campaigns ADD force_per_call_notes ENUM('DISABLED','ENABLED','5_CHARACTERS','15_CHARACTERS','30_CHARACTERS','100_CHARACTERS') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1692',db_schema_update_date=NOW() where db_schema_version < 1692;

ALTER TABLE vicidial_inbound_groups ADD third_alert_trigger VARCHAR(20) default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD third_alert_trigger_seconds INT(6) default '600';
ALTER TABLE vicidial_inbound_groups ADD third_alert_filename VARCHAR(100) default '';
ALTER TABLE vicidial_inbound_groups ADD third_alert_delay INT(6) default '1000';
ALTER TABLE vicidial_inbound_groups ADD third_alert_container VARCHAR(40) default 'DISABLED';
ALTER TABLE vicidial_inbound_groups ADD third_alert_only VARCHAR(40) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1693',db_schema_update_date=NOW() where db_schema_version < 1693;

CREATE TABLE vicidial_daily_rt_monitoring_log LIKE vicidial_rt_monitor_log;

UPDATE system_settings SET db_schema_version='1694',db_schema_update_date=NOW() where db_schema_version < 1694;

ALTER TABLE vicidial_user_groups MODIFY allowed_reports VARCHAR(4000) default 'ALL REPORTS';

ALTER TABLE system_settings MODIFY reports_use_slave_db VARCHAR(4000) default '';

UPDATE system_settings SET db_schema_version='1695',db_schema_update_date=NOW() where db_schema_version < 1695;

ALTER TABLE vicidial_campaigns ADD agent_search_ingroup_list ENUM('DISABLED','ENABLED','ENABLED_OVERRIDE') default 'DISABLED';

ALTER TABLE vicidial_inbound_groups ADD agent_search_list VARCHAR(20) default '';

UPDATE system_settings SET db_schema_version='1696',db_schema_update_date=NOW() where db_schema_version < 1696;

ALTER TABLE phones MODIFY conf_secret VARCHAR(100) default 'test';

ALTER TABLE servers MODIFY conf_secret VARCHAR(100) default 'test';

UPDATE system_settings SET db_schema_version='1697',db_schema_update_date=NOW() where db_schema_version < 1697;

ALTER TABLE system_settings ADD two_factor_auth_agent_hours SMALLINT(5) default '0';

UPDATE system_settings SET db_schema_version='1698',db_schema_update_date=NOW() where db_schema_version < 1698;

ALTER TABLE system_settings ADD highest_lead_id VARCHAR(20) default '0';

ALTER TABLE system_settings ADD hopper_hold_inserts ENUM('0','1','2','3','4','5','6','7') default '0';

ALTER TABLE vicidial_campaigns ADD hopper_hold_inserts ENUM('ENABLED','DISABLED','AUTONEXT') default 'DISABLED';

ALTER TABLE vicidial_hopper MODIFY status ENUM('READY','QUEUE','INCALL','DONE','HOLD','DNC','RHOLD','RQUEUE') default 'READY';

UPDATE system_settings SET db_schema_version='1699',db_schema_update_date=NOW() where db_schema_version < 1699;

CREATE TABLE vicidial_3way_press_live (
call_date DATETIME(6),
caller_code VARCHAR(30) NOT NULL,
call_3way_id VARCHAR(30) NOT NULL,
lead_id INT(9) UNSIGNED,
phone_number VARCHAR(18),
dialstring VARCHAR(28),
outbound_cid VARCHAR(20),
user VARCHAR(20),
session_id VARCHAR(20),
server_ip VARCHAR(15),
session_id_3way VARCHAR(20) default '',
status VARCHAR(40),
index(call_date),
index(caller_code),
index(call_3way_id),
index(lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_3way_press_log (
call_date DATETIME(6),
caller_code VARCHAR(30) NOT NULL,
call_3way_id VARCHAR(30) NOT NULL,
lead_id INT(9) UNSIGNED,
phone_number VARCHAR(18),
dialstring VARCHAR(28),
outbound_cid VARCHAR(20),
user VARCHAR(20),
session_id VARCHAR(20),
server_ip VARCHAR(15),
session_id_3way VARCHAR(20) default '',
result TEXT,
index(call_date),
index(caller_code),
index(call_3way_id),
index(lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_3way_press_log_archive LIKE vicidial_3way_press_log;
CREATE UNIQUE INDEX vdpla on vicidial_3way_press_log_archive (call_date,caller_code,user);

UPDATE system_settings SET db_schema_version='1700',db_schema_update_date=NOW() where db_schema_version < 1700;

ALTER TABLE vicidial_users ADD hci_enabled ENUM('0','1','2','3','4','5','6') default '0';

CREATE TABLE vicidial_hci_live_agents (
user VARCHAR(20),
campaign_id VARCHAR(8),
user_ip VARCHAR(45) default '',
login_time DATETIME,
last_call_time DATETIME,
last_update_time TIMESTAMP,
status VARCHAR(40),
lead_id INT(9) UNSIGNED default '0',
phone_number VARCHAR(18),
random_id INT(8) UNSIGNED,
index(user),
index(campaign_id),
index(last_update_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_hci_agent_log (
user VARCHAR(20),
campaign_id VARCHAR(8),
user_ip VARCHAR(45) default '',
login_time DATETIME,
last_call_time DATETIME,
status VARCHAR(40),
index(user),
index(login_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_hci_reserve (
user VARCHAR(20),
lead_id INT(9) UNSIGNED,
phone_number VARCHAR(18),
reserve_date DATETIME,
campaign_id VARCHAR(8),
status VARCHAR(40),
index(user),
index(reserve_date),
index(lead_id)
) ENGINE=MyISAM;

CREATE UNIQUE INDEX vhcir on vicidial_hci_reserve (lead_id,user,campaign_id);

CREATE TABLE vicidial_hci_log (
user VARCHAR(20),
lead_id INT(9) UNSIGNED,
phone_number VARCHAR(18),
call_date DATETIME,
campaign_id VARCHAR(8),
status VARCHAR(40),
user_ip VARCHAR(45) default '',
index(user),
index(call_date),
index(lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_hci_log_archive LIKE vicidial_hci_log;
CREATE UNIQUE INDEX vhlclu on vicidial_hci_log_archive (call_date,lead_id,user);

CREATE TABLE hci_logs (
date DATETIME,
user VARCHAR(20) default '',
lead_id INT(9) UNSIGNED NOT NULL,
campaign_id VARCHAR(8),
index(date)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1701',db_schema_update_date=NOW() where db_schema_version < 1701;

ALTER TABLE vicidial_campaigns ADD daily_phone_number_call_limit TINYINT(3) UNSIGNED default '0';

CREATE TABLE vicidial_phone_number_call_daily_counts (
phone_number VARCHAR(18) NOT NULL,
called_count TINYINT(3) UNSIGNED default '0',
modify_date DATETIME,
unique index vpncdc_phone_number (phone_number)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1702',db_schema_update_date=NOW() where db_schema_version < 1702;

ALTER TABLE vicidial_agent_notifications MODIFY recipient_type ENUM('USER','USER_GROUP','CAMPAIGN','ALT_DISPLAY') DEFAULT NULL;

CREATE TABLE vicidial_3way_press_multi (
user VARCHAR(20) PRIMARY KEY,
call_date DATETIME,
phone_numbers VARCHAR(255) default '',
phone_numbers_ct TINYINT(3) default '0',
status VARCHAR(40) default '',
index(call_date)
) ENGINE=MyISAM;

CREATE INDEX vdplpn on vicidial_3way_press_log (phone_number);

UPDATE system_settings SET db_schema_version='1703',db_schema_update_date=NOW() where db_schema_version < 1703;

ALTER TABLE vicidial_3way_press_live ADD call_channel VARCHAR(100) default '';

ALTER TABLE vicidial_3way_press_log ADD call_channel VARCHAR(100) default '';
ALTER TABLE vicidial_3way_press_log ADD call_transfer ENUM('N','Y') default 'N';

ALTER TABLE vicidial_3way_press_log_archive ADD call_channel VARCHAR(100) default '';
ALTER TABLE vicidial_3way_press_log_archive ADD call_transfer ENUM('N','Y') default 'N';

UPDATE system_settings SET db_schema_version='1704',db_schema_update_date=NOW() where db_schema_version < 1704;

ALTER TABLE vicidial_3way_press_live ADD agent_heartbeat DATETIME;

UPDATE system_settings SET db_schema_version='1705',db_schema_update_date=NOW() where db_schema_version < 1705;

ALTER TABLE vicidial_qc_agent_log ADD qc_log_id INT(10) UNSIGNED;

UPDATE system_settings SET db_schema_version='1706',db_schema_update_date=NOW() where db_schema_version < 1706;

ALTER TABLE vicidial_campaigns ADD state_descriptions VARCHAR(40) default '---DISABLED---';

ALTER TABLE vicidial_inbound_groups ADD state_descriptions VARCHAR(40) default '---DISABLED---';

UPDATE system_settings SET db_schema_version='1707',db_schema_update_date=NOW() where db_schema_version < 1707;

ALTER TABLE vicidial_inbound_group_agents ADD daily_limit SMALLINT(5) default '-1';

ALTER TABLE vicidial_live_inbound_agents ADD daily_limit SMALLINT(5) default '-1';

CREATE TABLE server_live_stats (
update_time DATETIME NOT NULL,
server_ip VARCHAR(15) NOT NULL,
server_name VARCHAR(100) NOT NULL,
cpu_count SMALLINT(5) UNSIGNED default '0',
loadavg_1 DECIMAL(8,2) default '0.00',
loadavg_5 DECIMAL(8,2) default '0.00',
loadavg_15 DECIMAL(8,2) default '0.00',
freeram INT(9) default '0',
usedram INT(9) default '0',
processes SMALLINT(4) default '0',
system_uptime VARCHAR(255) default '',
cpu_user_percent DECIMAL(6,2) default '0.00',
cpu_sys_percent DECIMAL(6,2) default '0.00',
cpu_idle_percent DECIMAL(6,2) default '0.00',
cpu_iowait_percent DECIMAL(6,2) default '0.00',
cpu_vm_percent DECIMAL(6,2) default '0.00',
disk_reads INT(9) UNSIGNED default '0',
disk_writes INT(9) UNSIGNED default '0',
asterisk_channels_total SMALLINT(4) UNSIGNED default '0',
asterisk_agents_total SMALLINT(4) UNSIGNED default '0',
mysql_uptime VARCHAR(20) default '0',
mysql_queries_per_second INT(9) UNSIGNED default '0',
mysql_connections MEDIUMINT(7) UNSIGNED default '0',
unique index liveservers (server_ip)
) ENGINE=MyISAM;

CREATE TABLE server_live_drives (
update_time DATETIME NOT NULL,
server_ip VARCHAR(15) NOT NULL,
drive_order TINYINT UNSIGNED default '0',
drive_device VARCHAR(100) default '',
read_sec DECIMAL(8,2) default '0.0',
write_sec DECIMAL(8,2) default '0.0',
kb_read_sec DECIMAL(12,2) default '0.0',
kb_write_sec DECIMAL(12,2) default '0.0',
util_pct DECIMAL(7,2) default '0.0',
unique index livedrives (server_ip, drive_device)
) ENGINE=MyISAM;

CREATE TABLE server_live_partitions (
update_time DATETIME NOT NULL,
server_ip VARCHAR(15) NOT NULL,
partition_order TINYINT UNSIGNED default '0',
partition_path VARCHAR(100) default '',
partition_filesystem VARCHAR(100) default '',
use_pct TINYINT UNSIGNED default '0',
mb_used BIGINT(14) default '0',
mb_available BIGINT(14) default '0',
unique index livepartitions (server_ip, partition_path)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1708',db_schema_update_date=NOW() where db_schema_version < 1708;

ALTER TABLE vicidial_campaigns MODIFY hopper_hold_inserts ENUM('ENABLED','DISABLED','AUTONEXT') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1709',db_schema_update_date=NOW() where db_schema_version < 1709;

ALTER TABLE vicidial_call_time_holidays ADD holiday_method VARCHAR(40) default 'REPLACE';

UPDATE system_settings SET db_schema_version='1710',db_schema_update_date=NOW() where db_schema_version < 1710;

CREATE TABLE inbound_disabled_entries (
interval_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
start_datetime DATETIME,
end_datetime datetime,
location VARCHAR(50),
message VARCHAR(100),
message_type ENUM('MEETING','CLOSED','WEATHER','CUSTOM'),
status ENUM('ACTIVE','LIVE','COMPLETED','CANCELLED') DEFAULT 'ACTIVE',
user VARCHAR(20),
modify_date TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
modified_by VARCHAR(20),
holiday_id VARCHAR(30),
KEY inbound_disabled_entries_key (start_datetime,end_datetime,location)
) ENGINE=MyISAM;

CREATE TABLE vicidial_pending_ar (
ar_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
report_id VARCHAR(30) default '',
start_datetime DATETIME,
php_script VARCHAR(50) default '',
user VARCHAR(20),
status ENUM('TRIGGERED','AUTHORIZED','COMPLETED','ERROR') DEFAULT 'TRIGGERED',
notes TEXT,
KEY pending_ar_key (user,start_datetime)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1711',db_schema_update_date=NOW() where db_schema_version < 1711;

ALTER TABLE vicidial_campaigns ADD script_tab_height SMALLINT(5) default '0';

UPDATE system_settings SET db_schema_version='1712',db_schema_update_date=NOW() where db_schema_version < 1712;

CREATE TABLE vicidial_timeoff_log (
vtl_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
user VARCHAR(20) DEFAULT NULL,
full_name VARCHAR(100) DEFAULT NULL,
timeoff_month CHAR(7) DEFAULT NULL,
timeoff_type VARCHAR(10) DEFAULT NULL,
hours DECIMAL(5,2) unsigned DEFAULT NULL,
entry_date DATETIME DEFAULT NULL,
modify_date TIMESTAMP NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
entered_by VARCHAR(20) DEFAULT NULL,
last_modified_by VARCHAR(20) DEFAULT NULL,
PRIMARY KEY (vtl_id),
UNIQUE KEY vicidial_timeoff_log_agent_month_key (user,timeoff_month,timeoff_type)
);

INSERT INTO vicidial_settings_containers VALUES ('VICIDIAL_TIMEOFF_SETTINGS','Settings for time-off admin utility','OTHER','---ALL---','; Comma-delimited time-off codes - MANDATORY, must have at least one defined. \r\n; Default is \'VAC\' for vacation\r\ntimeoff_types => VAC\r\n\r\n; optional, if set to \'1\' will show all viewable agents, even ones with no \r\n; time off for month.  Default is 0\r\ndisplay_all_agents => 0\r\n\r\n; optional, used to filter users displayed, in addition to user_group \r\n; permissions\r\nuser_filter_SQL =>  \r\n\r\n; optional, uses columns from vicidial_users table.   Defaults to \r\n; full_name asc, user asc if commented out or non-existent\r\n; sort_SQL => full_name asc, user asc\r\n\r\n; include custom coding for misc download coding where \"custom_download\" \r\n; marked in agent_timeoff_script.php - DO NOT USE WITHOUT CODING KNOWLEDGE\r\n; set to \'1\' to activate\r\ncustom_download => 0');

UPDATE system_settings SET db_schema_version='1713',db_schema_update_date=NOW() where db_schema_version < 1713;

ALTER TABLE vicidial_campaigns ADD call_log_days SMALLINT(5) default '0';

UPDATE system_settings SET db_schema_version='1714',db_schema_update_date=NOW() where db_schema_version < 1714;

ALTER TABLE vicidial_campaigns ADD leave_3way_stop_recording ENUM('DISABLED','ALL_CALLS') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1715',db_schema_update_date=NOW() where db_schema_version < 1715;

ALTER TABLE system_settings ADD coldstorage_server_ip VARCHAR(50) default '';
ALTER TABLE system_settings ADD coldstorage_dbname VARCHAR(50) default '';
ALTER TABLE system_settings ADD coldstorage_login VARCHAR(50) default '';
ALTER TABLE system_settings ADD coldstorage_pass VARCHAR(50) default '';
ALTER TABLE system_settings ADD coldstorage_port VARCHAR(10) default '';

UPDATE system_settings SET db_schema_version='1716',db_schema_update_date=NOW() where db_schema_version < 1716;

INSERT INTO vicidial_state_call_times SET state_call_time_id='florida',state_call_time_state='FL',state_call_time_name='Florida 8am 8pm',sct_default_start='800',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='maine',state_call_time_state='ME',state_call_time_name='Maine 9am-5pm',sct_default_start='900',sct_default_stop='1700';
INSERT INTO vicidial_state_call_times SET state_call_time_id='maryland',state_call_time_state='MD',state_call_time_name='Maryland 8am 8pm',sct_default_start='800',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='oklahoma',state_call_time_state='OK',state_call_time_name='Oklahoma 8am 8pm',sct_default_start='800',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='washington',state_call_time_state='WA',state_call_time_name='Washington 8am-8pm',sct_default_start='800',sct_default_stop='2000';

UPDATE system_settings SET db_schema_version='1717',db_schema_update_date=NOW() where db_schema_version < 1717;

ALTER TABLE vicidial_campaigns ADD manual_minimum_ring_seconds SMALLINT(5) default '0';
ALTER TABLE vicidial_campaigns ADD manual_minimum_attempt_seconds SMALLINT(5) default '0';
ALTER TABLE vicidial_campaigns ADD manual_minimum_answer_seconds SMALLINT(5) default '0';

UPDATE system_settings SET db_schema_version='1718',db_schema_update_date=NOW() where db_schema_version < 1718;

ALTER TABLE system_settings ADD stereo_recording ENUM('0','1','2','3','4','5','6') default '0';

ALTER TABLE vicidial_campaigns ADD stereo_recording ENUM('DISABLED','CUSTOMER','CUSTOMER_MUTE') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD khomp_settings_container VARCHAR(40) DEFAULT 'KHOMPSETTINGS';

ALTER TABLE vicidial_inbound_groups ADD stereo_recording ENUM('DISABLED','CUSTOMER','CUSTOMER_MUTE') default 'DISABLED';

ALTER TABLE vicidial_manager MODIFY cmd_line_i VARCHAR(50);
ALTER TABLE vicidial_manager MODIFY cmd_line_j VARCHAR(50);
ALTER TABLE vicidial_manager MODIFY cmd_line_d VARCHAR(200);

CREATE TABLE recording_log_stereo (
recording_id INT(10) UNSIGNED PRIMARY KEY NOT NULL,
server_ip VARCHAR(15),
start_time DATETIME,
end_time DATETIME,
length_in_sec MEDIUMINT(8) UNSIGNED,
filename VARCHAR(100),
lead_id INT(9) UNSIGNED,
options VARCHAR(100),
processing_log TEXT,
index(filename),
index(lead_id),
index(start_time)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1719',db_schema_update_date=NOW() where db_schema_version < 1719;

ALTER TABLE vicidial_khomp_log ADD khomp_settings_container VARCHAR(40); 

UPDATE system_settings SET db_schema_version='1720',db_schema_update_date=NOW() where db_schema_version < 1720;
