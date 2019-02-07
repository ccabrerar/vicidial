UPDATE system_settings SET db_schema_version='1318',version='2.6b0.5',db_schema_update_date=NOW() where db_schema_version < 1318;

ALTER TABLE vicidial_phone_codes MODIFY geographic_description VARCHAR(100);

ALTER TABLE vicidial_campaigns ADD in_group_dial ENUM('DISABLED','MANUAL_DIAL','NO_DIAL','BOTH') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD in_group_dial_select ENUM('AGENT_SELECTED','CAMPAIGN_SELECTED','ALL_USER_GROUP') default 'CAMPAIGN_SELECTED';

UPDATE system_settings SET db_schema_version='1319',db_schema_update_date=NOW() where db_schema_version < 1319;

ALTER TABLE vicidial_inbound_groups ADD dial_ingroup_cid VARCHAR(20) default '';

UPDATE system_settings SET db_schema_version='1320',db_schema_update_date=NOW() where db_schema_version < 1320;

ALTER TABLE vicidial_campaigns ADD safe_harbor_audio_field VARCHAR(30) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1321',db_schema_update_date=NOW() where db_schema_version < 1321;

ALTER TABLE system_settings ADD call_menu_qualify_enabled ENUM('0','1') default '0';

ALTER TABLE vicidial_call_menu ADD qualify_sql TEXT;

UPDATE system_settings SET db_schema_version='1322',db_schema_update_date=NOW() where db_schema_version < 1322;

ALTER TABLE recording_log MODIFY filename VARCHAR(100);

ALTER TABLE vicidial_live_agents ADD external_recording VARCHAR(20) default '';

UPDATE system_settings SET db_schema_version='1323',db_schema_update_date=NOW() where db_schema_version < 1323;

ALTER TABLE system_settings ADD admin_list_counts ENUM('0','1') default '1';

UPDATE system_settings SET db_schema_version='1324',db_schema_update_date=NOW() where db_schema_version < 1324;

ALTER TABLE phones MODIFY is_webphone ENUM('Y','N','Y_API_LAUNCH') default 'N';

CREATE TABLE vicidial_session_data (
session_name VARCHAR(40) UNIQUE NOT NULL,
user VARCHAR(20),
campaign_id VARCHAR(8),
server_ip VARCHAR(15) NOT NULL,
conf_exten VARCHAR(20),
extension VARCHAR(100) NOT NULL,
login_time DATETIME NOT NULL,
webphone_url TEXT,
agent_login_call TEXT
);

UPDATE system_settings SET db_schema_version='1325',db_schema_update_date=NOW() where db_schema_version < 1325;

CREATE TABLE vicidial_dial_log (
caller_code VARCHAR(30) NOT NULL,
lead_id INT(9) UNSIGNED default '0',
server_ip VARCHAR(15),
call_date DATETIME,
extension VARCHAR(100) default '',
channel VARCHAR(100) default '',
context VARCHAR(100) default '',
timeout MEDIUMINT(7) UNSIGNED default '0',
outbound_cid VARCHAR(100) default '',
index (caller_code),
index (call_date)
);

CREATE TABLE vicidial_dial_log_archive LIKE vicidial_dial_log;
CREATE UNIQUE INDEX vddla on vicidial_dial_log_archive (caller_code,call_date);

UPDATE system_settings SET db_schema_version='1326',db_schema_update_date=NOW() where db_schema_version < 1326;

ALTER TABLE vicidial_campaigns MODIFY agent_dial_owner_only ENUM('NONE','USER','TERRITORY','USER_GROUP','USER_BLANK','TERRITORY_BLANK','USER_GROUP_BLANK') default 'NONE';

UPDATE system_settings SET db_schema_version='1327',db_schema_update_date=NOW() where db_schema_version < 1327;

ALTER TABLE phones ADD voicemail_greeting VARCHAR(100) default '';

ALTER TABLE vicidial_voicemail ADD voicemail_greeting VARCHAR(100) default '';

ALTER TABLE system_settings ADD allow_voicemail_greeting ENUM('0','1') default '0';
ALTER TABLE system_settings ADD audio_store_purge TEXT;

ALTER TABLE servers ADD audio_store_purge TEXT;

UPDATE system_settings SET db_schema_version='1328',db_schema_update_date=NOW() where db_schema_version < 1328;

ALTER TABLE system_settings ADD svn_revision INT(9) default '0';

ALTER TABLE servers ADD svn_revision INT(9) default '0';
ALTER TABLE servers ADD svn_info TEXT;

UPDATE system_settings SET db_schema_version='1329',db_schema_update_date=NOW() where db_schema_version < 1329;

ALTER TABLE vicidial_campaigns ADD pause_after_next_call ENUM('ENABLED','DISABLED') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD owner_populate ENUM('ENABLED','DISABLED') default 'DISABLED';

UPDATE system_settings SET db_schema_version='1330',db_schema_update_date=NOW() where db_schema_version < 1330;

CREATE TABLE vicidial_qc_agent_log (
qc_agent_log_id INT(9) unsigned NOT NULL AUTO_INCREMENT,
qc_user VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
qc_user_group VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
qc_user_ip VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
lead_user VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
web_server_ip VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
view_datetime DATETIME NOT NULL,
save_datetime DATETIME DEFAULT NULL,
view_epoch INT(10) unsigned NOT NULL,
save_epoch INT(10) unsigned DEFAULT NULL,
elapsed_seconds SMALLINT(5) unsigned DEFAULT NULL,
lead_id INT(9) unsigned NOT NULL,
list_id BIGINT(14) unsigned NOT NULL,
campaign_id VARCHAR(8) COLLATE utf8_unicode_ci NOT NULL,
old_status VARCHAR(6) COLLATE utf8_unicode_ci DEFAULT NULL,
new_status VARCHAR(6) COLLATE utf8_unicode_ci DEFAULT NULL,
details TEXT COLLATE utf8_unicode_ci,
processed ENUM('Y','N') COLLATE utf8_unicode_ci NOT NULL,
PRIMARY KEY (qc_agent_log_id),
KEY view_epoch (view_epoch)
);

CREATE TABLE vicidial_comments (
comment_id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
lead_id INT(11) NOT NULL,
user_id INT(11) NOT NULL,
timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
list_id BIGINT(14) UNSIGNED NOT NULL,
campaign_id INT(11) NOT NULL,
comment VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
hidden TINYINT(1) DEFAULT NULL,
hidden_user_id INT(11) DEFAULT NULL,
hidden_timestamp DATETIME DEFAULT NULL,
unhidden_user_id INT(11) DEFAULT NULL,
unhidden_timestamp DATETIME DEFAULT NULL,
PRIMARY KEY (comment_id),
index (lead_id)
);

CREATE TABLE vicidial_configuration (
id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
name VARCHAR(36) NOT NULL ,
value VARCHAR(36) NOT NULL ,
UNIQUE (name)
);

CREATE TABLE vicidial_lists_custom (
list_id BIGINT(14) unsigned NOT NULL,
audit_comments TINYINT(1) DEFAULT NULL COMMENT 'visible',
audit_comments_enabled TINYINT(1) DEFAULT NULL COMMENT 'invisible',
PRIMARY KEY (list_id)
);

ALTER TABLE vicidial_qc_codes ADD qc_result_type ENUM( 'PASS', 'FAIL', 'CANCEL', 'COMMIT' ) NOT NULL;

INSERT INTO vicidial_qc_codes (code,code_name,qc_result_type) VALUES ('QCPASS','PASS','PASS');
INSERT INTO vicidial_qc_codes (code,code_name,qc_result_type) VALUES ('QCFAIL','FAIL','FAIL');
INSERT INTO vicidial_qc_codes (code,code_name,qc_result_type) VALUES ('QCCANCEL','CANCEL','CANCEL');

INSERT INTO vicidial_statuses (status, status_name, selectable, human_answered, category, sale, dnc, customer_contact, not_interested, unworkable, scheduled_callback) VALUES ('QCFAIL', 'QC_FAIL_CALLBK', 'N', 'Y', 'QC', 'N', 'N', 'Y', 'N', 'N', 'Y');

INSERT INTO vicidial_configuration (id, name, value) VALUES (NULL, 'qc_database_version', '1638');
UPDATE vicidial_configuration set value='1766' where name='qc_database_version';

UPDATE system_settings set vdc_agent_api_active='1';

UPDATE system_settings SET db_schema_version='1331',db_schema_update_date=NOW() where db_schema_version < 1331;

ALTER TABLE system_settings ADD queuemetrics_socket VARCHAR(20) default 'NONE';
ALTER TABLE system_settings ADD queuemetrics_socket_url TEXT;

UPDATE system_settings SET db_schema_version='1332',db_schema_update_date=NOW() where db_schema_version < 1332;

CREATE TABLE vicidial_call_time_holidays (
holiday_id VARCHAR(30) PRIMARY KEY NOT NULL,
holiday_name VARCHAR(100) NOT NULL,
holiday_comments VARCHAR(255) default '',
holiday_date DATE,
holiday_status ENUM('ACTIVE','INACTIVE','EXPIRED') default 'INACTIVE',
ct_default_start SMALLINT(4) unsigned NOT NULL default '900',
ct_default_stop SMALLINT(4) unsigned NOT NULL default '2100',
default_afterhours_filename_override VARCHAR(255) default '',
user_group VARCHAR(20) default '---ALL---'
);

ALTER TABLE vicidial_call_times ADD ct_holidays TEXT default '';
UPDATE vicidial_call_times SET ct_holidays='' where ct_holidays is NULL;

UPDATE system_settings SET db_schema_version='1333',db_schema_update_date=NOW() where db_schema_version < 1333;

ALTER TABLE vicidial_lists ADD expiration_date DATE default '2099-12-31';

ALTER TABLE vicidial_campaigns ADD use_other_campaign_dnc VARCHAR(8) default '';

UPDATE system_settings SET db_schema_version='1334',db_schema_update_date=NOW() where db_schema_version < 1334;

ALTER TABLE system_settings ADD enhanced_disconnect_logging ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1335',db_schema_update_date=NOW() where db_schema_version < 1335;

ALTER TABLE vicidial_campaigns MODIFY agent_lead_search ENUM('ENABLED','LIVE_CALL_INBOUND','LIVE_CALL_INBOUND_AND_MANUAL','DISABLED') default 'DISABLED';

ALTER TABLE vicidial_users MODIFY agent_lead_search_override ENUM('NOT_ACTIVE','ENABLED','LIVE_CALL_INBOUND','LIVE_CALL_INBOUND_AND_MANUAL','DISABLED') default 'NOT_ACTIVE';

UPDATE system_settings SET db_schema_version='1336',db_schema_update_date=NOW() where db_schema_version < 1336;

CREATE TABLE vicidial_email_list (
email_row_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
lead_id INT(9) UNSIGNED DEFAULT NULL,
email_date DATETIME DEFAULT NULL,
protocol ENUM('POP3','IMAP','NONE') DEFAULT 'IMAP',
email_to VARCHAR(255) DEFAULT NULL,
email_from VARCHAR(255) DEFAULT NULL,
email_from_name VARCHAR(255) DEFAULT NULL,
subject TEXT,
mime_type TEXT,
content_type TEXT,
x_mailer TEXT,
sender_ip VARCHAR(25) DEFAULT NULL,
message TEXT,
email_account_id VARCHAR(20) DEFAULT NULL,
group_id VARCHAR(20) DEFAULT NULL,
user VARCHAR(20) DEFAULT NULL,
status VARCHAR(10) DEFAULT NULL,
direction ENUM('INBOUND','OUTBOUND') DEFAULT 'INBOUND',
uniqueid VARCHAR(20) DEFAULT NULL,
xfercallid INT(9) UNSIGNED DEFAULT NULL,
PRIMARY KEY (email_row_id),
KEY email_list_account_key (email_account_id),
KEY email_list_user_key (user),
KEY vicidial_email_lead_id_key (lead_id)
);

CREATE TABLE vicidial_email_accounts (
email_account_id VARCHAR(20) NOT NULL,
email_account_name VARCHAR(100) DEFAULT NULL,
email_account_description VARCHAR(255) DEFAULT NULL,
user_group VARCHAR(20) DEFAULT '---ALL---',
protocol ENUM('POP3','IMAP','SMTP') DEFAULT 'IMAP',
email_replyto_address VARCHAR(255) DEFAULT NULL,
email_account_server VARCHAR(255) DEFAULT NULL,
email_account_user VARCHAR(255) DEFAULT NULL,
email_account_pass VARCHAR(100) DEFAULT NULL,
active ENUM('Y','N') DEFAULT 'N',
email_frequency_check_mins TINYINT(3) UNSIGNED DEFAULT '5',
group_id VARCHAR(20) DEFAULT NULL,
default_list_id BIGINT(14) UNSIGNED DEFAULT NULL,
call_handle_method VARCHAR(20) DEFAULT 'CID',
agent_search_method ENUM('LO','LB','SO') DEFAULT 'LB',
campaign_id VARCHAR(8) DEFAULT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT NULL,
email_account_type ENUM('INBOUND','OUTBOUND') DEFAULT 'INBOUND',
PRIMARY KEY (email_account_id),
KEY email_accounts_group_key (group_id)
);

CREATE TABLE inbound_email_attachments (
attachment_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
email_row_id INT(10) UNSIGNED DEFAULT NULL,
filename VARCHAR(250) NOT NULL DEFAULT '',
file_type VARCHAR(100) DEFAULT NULL,
file_encoding VARCHAR(20) DEFAULT NULL,
file_size VARCHAR(45) DEFAULT NULL,
file_extension VARCHAR(5) NOT NULL DEFAULT '',
file_contents LONGBLOB NOT NULL,
PRIMARY KEY (attachment_id),
KEY attachments_email_id_key (email_row_id)
);

CREATE TABLE vicidial_email_log (
email_log_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
email_row_id INT(10) UNSIGNED DEFAULT NULL,
lead_id INT(9) UNSIGNED DEFAULT NULL,
email_date DATETIME DEFAULT NULL,
user VARCHAR(20) DEFAULT NULL,
email_to VARCHAR(255) DEFAULT NULL,
message TEXT,
campaign_id VARCHAR(10) DEFAULT NULL,
attachments TEXT,
PRIMARY KEY (email_log_id),
KEY vicidial_email_log_lead_id_key (lead_id),
KEY vicidial_email_log_email_row_id_key (email_row_id)
);

ALTER TABLE system_settings ADD allow_emails ENUM('0','1') default '0';

ALTER TABLE vicidial_campaigns ADD allow_emails ENUM('Y','N') default 'N';

ALTER TABLE vicidial_inbound_groups ADD group_handling ENUM('PHONE','EMAIL') default 'PHONE';
ALTER TABLE vicidial_inbound_groups MODIFY get_call_launch ENUM('NONE','SCRIPT','WEBFORM','WEBFORMTWO','FORM','EMAIL') default 'NONE';

ALTER TABLE vicidial_users ADD agentcall_email ENUM('0','1') default '0';
ALTER TABLE vicidial_users ADD modify_email_accounts ENUM('0','1') default '0';

ALTER TABLE vicidial_live_agents MODIFY status ENUM('READY','QUEUE','INCALL','PAUSED','CLOSER','MQUEUE') default 'PAUSED';

UPDATE system_settings SET db_schema_version='1337',db_schema_update_date=NOW() where db_schema_version < 1337;

CREATE INDEX pvmid on phones (voicemail_id);
CREATE INDEX pdpn on phones (dialplan_number);

UPDATE system_settings SET db_schema_version='1338',db_schema_update_date=NOW() where db_schema_version < 1338;

ALTER TABLE vicidial_email_list ADD COLUMN content_transfer_encoding TEXT AFTER content_type;
ALTER TABLE vicidial_email_list MODIFY message text character set utf8;

ALTER TABLE vicidial_email_log MODIFY message text character set utf8;

UPDATE system_settings SET db_schema_version='1339',db_schema_update_date=NOW() where db_schema_version < 1339;

ALTER TABLE vicidial_campaigns MODIFY cpd_amd_action ENUM('DISABLED','DISPO','MESSAGE','CALLMENU','INGROUP') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD amd_inbound_group VARCHAR(20) default '';
ALTER TABLE vicidial_campaigns ADD amd_callmenu VARCHAR(50) default '';

UPDATE system_settings SET db_schema_version='1340',db_schema_update_date=NOW() where db_schema_version < 1340;

ALTER TABLE system_settings ADD level_8_disable_add ENUM('0','1') default '0';

UPDATE system_settings SET db_schema_version='1341',db_schema_update_date=NOW() where db_schema_version < 1341;

ALTER TABLE vicidial_dial_log ADD sip_hangup_cause SMALLINT(4) UNSIGNED default '0';
ALTER TABLE vicidial_dial_log_archive ADD sip_hangup_cause SMALLINT(4) UNSIGNED default '0';
ALTER TABLE vicidial_dial_log ADD sip_hangup_reason VARCHAR(50) default '';
ALTER TABLE vicidial_dial_log_archive ADD sip_hangup_reason VARCHAR(50) default '';
ALTER TABLE vicidial_dial_log ADD uniqueid VARCHAR(20) default '';
ALTER TABLE vicidial_dial_log_archive ADD uniqueid VARCHAR(20) default '';

ALTER TABLE vicidial_carrier_log ADD sip_hangup_cause SMALLINT(4) UNSIGNED default '0';
ALTER TABLE vicidial_carrier_log_archive ADD sip_hangup_cause SMALLINT(4) UNSIGNED default '0';
ALTER TABLE vicidial_carrier_log ADD sip_hangup_reason VARCHAR(50) default '';
ALTER TABLE vicidial_carrier_log_archive ADD sip_hangup_reason VARCHAR(50) default '';

CREATE INDEX vddllid on vicidial_dial_log (lead_id);
CREATE INDEX vdcllid on vicidial_carrier_log (lead_id);

UPDATE system_settings SET db_schema_version='1342',db_schema_update_date=NOW() where db_schema_version < 1342;

ALTER TABLE vicidial_carrier_log ADD caller_code VARCHAR(30) default '';
ALTER TABLE vicidial_carrier_log_archive ADD caller_code VARCHAR(30) default '';

UPDATE system_settings SET db_schema_version='1343',db_schema_update_date=NOW() where db_schema_version < 1343;

CREATE TABLE vicidial_report_log (
report_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
event_date DATETIME NOT NULL,
user VARCHAR(20) NOT NULL,
ip_address VARCHAR(15) NOT NULL,
report_name VARCHAR(50) NOT NULL,
browser TEXT,
referer TEXT,
notes TEXT,
url TEXT,
run_time VARCHAR(20) default '0',
index (user),
index (report_name)
);

UPDATE system_settings SET db_schema_version='1344',db_schema_update_date=NOW() where db_schema_version < 1344;

ALTER TABLE vicidial_nanpa_prefix_codes ADD lata_type VARCHAR(1) default '';

UPDATE system_settings SET db_schema_version='1345',db_schema_update_date=NOW() where db_schema_version < 1345;

ALTER TABLE vicidial_campaigns ADD survey_wait_sec TINYINT(3) default '10';

UPDATE system_settings SET db_schema_version='1346',db_schema_update_date=NOW() where db_schema_version < 1346;

ALTER TABLE vicidial_campaigns MODIFY survey_no_response_action ENUM('OPTIN','OPTOUT','DROP') default 'OPTIN';

UPDATE system_settings SET db_schema_version='1347',db_schema_update_date=NOW() where db_schema_version < 1347;
