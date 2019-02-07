UPDATE system_settings SET db_schema_version='1349',version='2.8b0.5',db_schema_update_date=NOW() where db_schema_version < 1349;

ALTER TABLE vicidial_state_call_times ADD ct_holidays TEXT default '';

UPDATE system_settings SET db_schema_version='1350',db_schema_update_date=NOW() where db_schema_version < 1350;

ALTER TABLE vicidial_users ADD failed_login_count TINYINT(3) UNSIGNED default '0';
ALTER TABLE vicidial_users ADD last_login_date DATETIME default '2001-01-01 00:00:01';
ALTER TABLE vicidial_users ADD last_ip VARCHAR(15) default '';
ALTER TABLE vicidial_users ADD pass_hash VARCHAR(100) default '';

ALTER TABLE system_settings ADD pass_hash_enabled ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1351',db_schema_update_date=NOW() where db_schema_version < 1351;

ALTER TABLE vicidial_user_log ADD phone_login VARCHAR(15) default '';
ALTER TABLE vicidial_user_log ADD server_phone VARCHAR(15) default '';
ALTER TABLE vicidial_user_log ADD phone_ip VARCHAR(15) default '';

ALTER TABLE system_settings ADD pass_key VARCHAR(100) default '';
ALTER TABLE system_settings ADD pass_cost TINYINT(2) UNSIGNED default '2';

CREATE INDEX phone_ip ON vicidial_user_log (phone_ip);
CREATE INDEX vuled ON vicidial_user_log (event_date);

UPDATE system_settings SET db_schema_version='1352',db_schema_update_date=NOW() where db_schema_version < 1352;

ALTER TABLE system_settings ADD disable_auto_dial ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1353',db_schema_update_date=NOW() where db_schema_version < 1353;

CREATE TABLE vicidial_monitor_calls (
monitor_call_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
server_ip VARCHAR(15) NOT NULL,
callerid VARCHAR(20),
channel VARCHAR(100),
context VARCHAR(100),
uniqueid VARCHAR(20),
monitor_time DATETIME,
user_phone VARCHAR(10) default 'USER',
api_log ENUM('Y','N') default 'N',
barge_listen ENUM('LISTEN','BARGE') default 'LISTEN',
prepop_id VARCHAR(100) default '',
campaigns_limit VARCHAR(1000) default '',
users_list ENUM('Y','N') default 'N',
index (callerid),
index (monitor_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_monitor_log (
server_ip VARCHAR(15) NOT NULL,
callerid VARCHAR(20),
channel VARCHAR(100),
context VARCHAR(100),
uniqueid VARCHAR(20),
monitor_time DATETIME,
user VARCHAR(20),
campaign_id VARCHAR(8),
index (user),
index (campaign_id),
index (monitor_time)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1354',db_schema_update_date=NOW() where db_schema_version < 1354;

ALTER TABLE vicidial_custom_leadloader_templates ADD template_statuses VARCHAR(255);

UPDATE system_settings SET db_schema_version='1355',db_schema_update_date=NOW() where db_schema_version < 1355;

CREATE TABLE nanpa_prefix_exchanges_master (
areacode CHAR(3) default '',
prefix CHAR(4) default '',
source CHAR(1) default '',
type CHAR(1) default '',
tier VARCHAR(20) default '',
postal_code VARCHAR(20) default '',
new_areacode CHAR(3) default '',
tzcode VARCHAR(4) default '',
region CHAR(2) default ''
) ENGINE=MyISAM;

CREATE TABLE nanpa_prefix_exchanges_fast (
ac_prefix CHAR(7) default '',
type CHAR(1) default ''
) ENGINE=MyISAM;

CREATE INDEX nanpaacprefix on nanpa_prefix_exchanges_fast (ac_prefix);

CREATE TABLE nanpa_wired_to_wireless (
phone CHAR(10) NOT NULL UNIQUE PRIMARY KEY
) ENGINE=MyISAM;

CREATE TABLE nanpa_wireless_to_wired (
phone CHAR(10) NOT NULL UNIQUE PRIMARY KEY
) ENGINE=MyISAM;

CREATE TABLE vicidial_nanpa_filter_log (
output_code VARCHAR(30) PRIMARY KEY NOT NULL,
status VARCHAR(20) default 'BEGIN',
server_ip VARCHAR(15) default '',
list_id TEXT,
start_time DATETIME,
update_time DATETIME,
user VARCHAR(20) default '',
leads_count BIGINT(14) default '0',
filter_count BIGINT(14) default '0',
status_line VARCHAR(255) default '',
script_output TEXT,
index (start_time)
) ENGINE=MyISAM;

UPDATE system_settings SET db_schema_version='1356',db_schema_update_date=NOW() where db_schema_version < 1356;

ALTER TABLE system_settings ADD queuemetrics_record_hold ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1357',db_schema_update_date=NOW() where db_schema_version < 1357;

ALTER TABLE system_settings ADD country_code_list_stats ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1358',db_schema_update_date=NOW() where db_schema_version < 1358;

ALTER TABLE vicidial_campaigns ADD manual_dial_lead_id ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1359',db_schema_update_date=NOW() where db_schema_version < 1359;

ALTER TABLE servers ADD system_uptime VARCHAR(255) default '';
ALTER TABLE servers ADD auto_restart_asterisk ENUM('Y','N') default 'N';
ALTER TABLE servers ADD asterisk_temp_no_restart ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1360',db_schema_update_date=NOW() where db_schema_version < 1360;

ALTER TABLE vicidial_campaigns ADD dead_max SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_campaigns ADD dead_max_dispo VARCHAR(6) default 'DCMX';
ALTER TABLE vicidial_campaigns ADD dispo_max SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_campaigns ADD dispo_max_dispo VARCHAR(6) default 'DISMX';
ALTER TABLE vicidial_campaigns ADD pause_max SMALLINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1361',db_schema_update_date=NOW() where db_schema_version < 1361;

ALTER TABLE vicidial_closer_log ADD called_count SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_log ADD called_count SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_closer_log_archive ADD called_count SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_log_archive ADD called_count SMALLINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1362',db_schema_update_date=NOW() where db_schema_version < 1362;

CREATE TABLE vicidial_webservers (
webserver_id SMALLINT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
webserver VARCHAR(150) default '',
hostname VARCHAR(150) default '',
unique index vdweb (webserver, hostname)
) ENGINE=MyISAM;

CREATE TABLE vicidial_urls (
url_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
url VARCHAR(333) default '',
unique index (url)
) ENGINE=MyISAM;

ALTER TABLE vicidial_user_log ADD webserver SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_user_log ADD login_url INT(9) UNSIGNED default '0';

ALTER TABLE vicidial_api_log ADD webserver SMALLINT(5) UNSIGNED default '0';
ALTER TABLE vicidial_api_log ADD api_url INT(9) UNSIGNED default '0';

CREATE TABLE vicidial_api_log_archive LIKE vicidial_api_log;
ALTER TABLE vicidial_api_log_archive MODIFY api_id INT(9) UNSIGNED NOT NULL;

ALTER TABLE vicidial_report_log ADD webserver SMALLINT(5) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1363',db_schema_update_date=NOW() where db_schema_version < 1363;

CREATE INDEX vlecc on vicidial_log_extended (caller_code);

UPDATE system_settings SET db_schema_version='1364',db_schema_update_date=NOW() where db_schema_version < 1364;

ALTER TABLE vicidial_live_agents ADD external_pause_code VARCHAR(6) default '';
ALTER TABLE vicidial_live_agents ADD pause_code VARCHAR(6) default '';

UPDATE system_settings SET db_schema_version='1365',db_schema_update_date=NOW() where db_schema_version < 1365;

ALTER TABLE vicidial_campaigns MODIFY drop_action ENUM('HANGUP','MESSAGE','VOICEMAIL','IN_GROUP','AUDIO','CALLMENU','VMAIL_NO_INST') default 'AUDIO';
ALTER TABLE vicidial_campaigns MODIFY survey_method ENUM('AGENT_XFER','VOICEMAIL','EXTENSION','HANGUP','CAMPREC_60_WAV','CALLMENU','VMAIL_NO_INST') default 'AGENT_XFER';

ALTER TABLE vicidial_inbound_groups MODIFY drop_action ENUM('HANGUP','MESSAGE','VOICEMAIL','IN_GROUP','CALLMENU','VMAIL_NO_INST') default 'MESSAGE';
ALTER TABLE vicidial_inbound_groups MODIFY after_hours_action ENUM('HANGUP','MESSAGE','EXTENSION','VOICEMAIL','IN_GROUP','CALLMENU','VMAIL_NO_INST') default 'MESSAGE';
ALTER TABLE vicidial_inbound_groups MODIFY no_agent_action ENUM('CALLMENU','INGROUP','DID','MESSAGE','EXTENSION','VOICEMAIL','VMAIL_NO_INST') default 'MESSAGE';

ALTER TABLE vicidial_inbound_dids MODIFY did_route ENUM('EXTEN','VOICEMAIL','AGENT','PHONE','IN_GROUP','CALLMENU','VMAIL_NO_INST') default 'EXTEN';
ALTER TABLE vicidial_inbound_dids MODIFY user_unavailable_action ENUM('IN_GROUP','EXTEN','VOICEMAIL','PHONE','VMAIL_NO_INST') default 'VOICEMAIL';
ALTER TABLE vicidial_inbound_dids MODIFY filter_action ENUM('EXTEN','VOICEMAIL','AGENT','PHONE','IN_GROUP','CALLMENU','VMAIL_NO_INST') default 'EXTEN';
ALTER TABLE vicidial_inbound_dids MODIFY filter_user_unavailable_action ENUM('IN_GROUP','EXTEN','VOICEMAIL','PHONE','VMAIL_NO_INST') default 'VOICEMAIL';

ALTER TABLE phones ADD voicemail_dump_exten_no_inst VARCHAR(20) default '85026666666667';

ALTER TABLE servers ADD voicemail_dump_exten_no_inst VARCHAR(20) default '85026666666667';

UPDATE system_settings SET db_schema_version='1366',db_schema_update_date=NOW() where db_schema_version < 1366;

ALTER TABLE phones ADD voicemail_instructions ENUM('Y','N') default 'Y';

UPDATE system_settings SET db_schema_version='1367',db_schema_update_date=NOW() where db_schema_version < 1367;

CREATE UNIQUE INDEX vlca_user_campaign_id on vicidial_campaign_agents (user, campaign_id);

UPDATE system_settings SET db_schema_version='1368',db_schema_update_date=NOW() where db_schema_version < 1368;

ALTER TABLE system_settings ADD reload_timestamp DATETIME;

UPDATE system_settings SET db_schema_version='1369',db_schema_update_date=NOW(),reload_timestamp=NOW() where db_schema_version < 1369;

ALTER TABLE vicidial_users ADD alter_admin_interface_options ENUM('0','1') default '1';

UPDATE system_settings SET db_schema_version='1370',db_schema_update_date=NOW() where db_schema_version < 1370;

ALTER TABLE vicidial_inbound_dids MODIFY filter_inbound_number ENUM('DISABLED','GROUP','URL','DNC_INTERNAL','DNC_CAMPAIGN') default 'DISABLED';
ALTER TABLE vicidial_inbound_dids ADD filter_dnc_campaign VARCHAR(8) default '';
ALTER TABLE vicidial_inbound_dids ADD filter_url_did_redirect ENUM('Y','N') default 'N';

ALTER TABLE vicidial_did_log MODIFY did_route VARCHAR(20) default '';

ALTER TABLE vicidial_did_agent_log MODIFY did_route VARCHAR(20) default '';

UPDATE system_settings SET db_schema_version='1371',db_schema_update_date=NOW() where db_schema_version < 1371;

ALTER TABLE vicidial_users ADD max_inbound_calls SMALLINT(5) UNSIGNED default '0';

ALTER TABLE vicidial_campaigns ADD max_inbound_calls SMALLINT(5) UNSIGNED default '0';

ALTER TABLE vicidial_inbound_group_agents ADD group_type VARCHAR(1) default 'C';

UPDATE system_settings SET db_schema_version='1372',db_schema_update_date=NOW() where db_schema_version < 1372;

ALTER TABLE vicidial_live_agents ADD preview_lead_id INT(9) UNSIGNED default '0';

UPDATE system_settings SET db_schema_version='1373',db_schema_update_date=NOW() where db_schema_version < 1373;

ALTER TABLE vicidial_campaigns ADD manual_dial_search_checkbox ENUM('SELECTED','SELECTED_RESET','UNSELECTED','UNSELECTED_RESET') default 'SELECTED';
ALTER TABLE vicidial_campaigns ADD hide_call_log_info ENUM('Y','N') default 'N';

UPDATE system_settings SET db_schema_version='1374',db_schema_update_date=NOW() where db_schema_version < 1374;

ALTER TABLE vicidial_users ADD modify_custom_dialplans ENUM('1','0') default '0';

UPDATE system_settings,vicidial_users SET modify_custom_dialplans='1' where modify_ingroups='1' and db_schema_version < 1375;

UPDATE system_settings SET db_schema_version='1375',db_schema_update_date=NOW() where db_schema_version < 1375;

ALTER TABLE vicidial_agent_log ADD pause_type ENUM('UNDEFINED','SYSTEM','AGENT','API','ADMIN') default 'UNDEFINED';
ALTER TABLE vicidial_agent_log_archive ADD pause_type ENUM('UNDEFINED','SYSTEM','AGENT','API','ADMIN') default 'UNDEFINED';

ALTER TABLE system_settings ADD queuemetrics_pause_type ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1376',db_schema_update_date=NOW() where db_schema_version < 1376;

ALTER TABLE system_settings ADD frozen_server_call_clear ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1377',db_schema_update_date=NOW() where db_schema_version < 1377;

ALTER TABLE vicidial_campaigns MODIFY alt_number_dialing ENUM('N','Y','SELECTED','SELECTED_TIMER_ALT','SELECTED_TIMER_ADDR3') default 'N';
ALTER TABLE vicidial_campaigns ADD timer_alt_seconds SMALLINT(5) default '0';

UPDATE system_settings SET db_schema_version='1378',db_schema_update_date=NOW() where db_schema_version < 1378;
