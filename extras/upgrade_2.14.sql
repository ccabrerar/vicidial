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

ALTER TABLE vicidial_campaigns ADD agent_screen_time_display ENUM('DISABLED','ENABLED_BASIC','ENABLED_FULL','ENABLED_BILL_BREAK_LUNCH_COACH') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1527',db_schema_update_date=NOW() where db_schema_version < 1527;

ALTER TABLE vicidial_campaigns MODIFY get_call_launch ENUM('NONE','SCRIPT','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM','PREVIEW_WEBFORM','PREVIEW_WEBFORMTWO','PREVIEW_WEBFORMTHREE') default 'NONE';

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
cid_group_type ENUM('AREACODE','STATE') default 'AREACODE',
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

ALTER TABLE vicidial_lists_fields MODIFY field_type ENUM('TEXT','AREA','SELECT','MULTI','RADIO','CHECKBOX','DATE','TIME','DISPLAY','SCRIPT','HIDDEN','READONLY','HIDEBLOB','SWITCH') default 'TEXT';

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
