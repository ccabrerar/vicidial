CREATE TABLE phones (
extension VARCHAR(100),
dialplan_number VARCHAR(20),
voicemail_id VARCHAR(10),
phone_ip VARCHAR(15),
computer_ip VARCHAR(15),
server_ip VARCHAR(15),
login VARCHAR(15),
pass VARCHAR(100),
status VARCHAR(10),
active ENUM('Y','N'),
phone_type VARCHAR(50),
fullname VARCHAR(50),
company VARCHAR(10),
picture VARCHAR(19),
messages INT(4),
old_messages INT(4),
protocol ENUM('SIP','PJSIP','Zap','IAX2','EXTERNAL') default 'SIP',
local_gmt VARCHAR(6) default '-5.00',
ASTmgrUSERNAME VARCHAR(20) default 'cron',
ASTmgrSECRET VARCHAR(20) default '1234',
login_user VARCHAR(20),
login_pass VARCHAR(100),
login_campaign VARCHAR(10),
park_on_extension VARCHAR(10) default '8301',
conf_on_extension VARCHAR(10) default '8302',
VICIDIAL_park_on_extension VARCHAR(10) default '8301',
VICIDIAL_park_on_filename VARCHAR(10) default 'park',
monitor_prefix VARCHAR(10) default '8612',
recording_exten VARCHAR(10) default '8309',
voicemail_exten VARCHAR(10) default '8501',
voicemail_dump_exten VARCHAR(20) default '85026666666666',
ext_context VARCHAR(20) default 'default',
dtmf_send_extension VARCHAR(100) default 'local/8500998@default',
call_out_number_group VARCHAR(100) default 'Zap/g2/',
client_browser VARCHAR(100) default '/usr/bin/mozilla',
install_directory VARCHAR(100) default '/usr/local/perl_TK',
local_web_callerID_URL VARCHAR(255) default 'http://www.vicidial.org/test_callerid_output.php',
VICIDIAL_web_URL VARCHAR(255) default 'http://www.vicidial.org/test_VICIDIAL_output.php',
AGI_call_logging_enabled ENUM('0','1') default '1',
user_switching_enabled ENUM('0','1') default '1',
conferencing_enabled ENUM('0','1') default '1',
admin_hangup_enabled ENUM('0','1') default '0',
admin_hijack_enabled ENUM('0','1') default '0',
admin_monitor_enabled ENUM('0','1') default '1',
call_parking_enabled ENUM('0','1') default '1',
updater_check_enabled ENUM('0','1') default '1',
AFLogging_enabled ENUM('0','1') default '1',
QUEUE_ACTION_enabled ENUM('0','1') default '1',
CallerID_popup_enabled ENUM('0','1') default '1',
voicemail_button_enabled ENUM('0','1') default '1',
enable_fast_refresh ENUM('0','1') default '0',
fast_refresh_rate INT(5) default '1000',
enable_persistant_mysql ENUM('0','1') default '0',
auto_dial_next_number ENUM('0','1') default '1',
VDstop_rec_after_each_call ENUM('0','1') default '1',
DBX_server VARCHAR(15),
DBX_database VARCHAR(15) default 'asterisk',
DBX_user VARCHAR(15) default 'cron',
DBX_pass VARCHAR(15) default '1234',
DBX_port INT(6) default '3306',
DBY_server VARCHAR(15),
DBY_database VARCHAR(15) default 'asterisk',
DBY_user VARCHAR(15) default 'cron',
DBY_pass VARCHAR(15) default '1234',
DBY_port INT(6) default '3306',
outbound_cid VARCHAR(20),
enable_sipsak_messages ENUM('0','1') default '0',
email VARCHAR(100),
template_id VARCHAR(15) NOT NULL,
conf_override TEXT,
phone_context VARCHAR(20) default 'default',
phone_ring_timeout SMALLINT(3) default '60',
conf_secret VARCHAR(100) default 'test',
delete_vm_after_email ENUM('N','Y') default 'N',
is_webphone ENUM('Y','N','Y_API_LAUNCH') default 'N',
use_external_server_ip ENUM('Y','N') default 'N',
codecs_list VARCHAR(100) default '',
codecs_with_template ENUM('0','1') default '0',
webphone_dialpad ENUM('Y','N','TOGGLE','TOGGLE_OFF') default 'Y',
on_hook_agent ENUM('Y','N') default 'N',
webphone_auto_answer ENUM('Y','N') default 'Y',
voicemail_timezone VARCHAR(30) default 'eastern',
voicemail_options VARCHAR(255) default '',
user_group VARCHAR(20) default '---ALL---',
voicemail_greeting VARCHAR(100) default '',
voicemail_dump_exten_no_inst VARCHAR(20) default '85026666666667',
voicemail_instructions ENUM('Y','N') default 'Y',
on_login_report enum('Y','N') NOT NULL default 'N',
unavail_dialplan_fwd_exten VARCHAR(40) default '',
unavail_dialplan_fwd_context VARCHAR(100) default '',
nva_call_url TEXT,
nva_search_method VARCHAR(40) default 'NONE',
nva_error_filename VARCHAR(255) default '',
nva_new_list_id BIGINT(14) UNSIGNED default '995',
nva_new_phone_code VARCHAR(10) default '1',
nva_new_status VARCHAR(6) default 'NVAINS',
webphone_dialbox ENUM('Y','N') default 'Y',
webphone_mute ENUM('Y','N') default 'Y',
webphone_volume ENUM('Y','N') default 'Y',
webphone_debug ENUM('Y','N') default 'N',
outbound_alt_cid VARCHAR(20) default '',
conf_qualify ENUM('Y','N') default 'Y',
webphone_layout VARCHAR(255) default '',
mohsuggest VARCHAR(100) default '',
peer_status ENUM('UNKNOWN','REGISTERED','UNREGISTERED','REACHABLE','LAGGED','UNREACHABLE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNKNOWN',
ping_time SMALLINT(6) DEFAULT NULL,
webphone_settings VARCHAR(40) default 'VICIPHONE_SETTINGS',
index (server_ip),
index (voicemail_id),
index (dialplan_number),
unique index extenserver (extension, server_ip)
) ENGINE=MyISAM;

CREATE TABLE servers (
server_id VARCHAR(10) NOT NULL,
server_description VARCHAR(255),
server_ip VARCHAR(15) NOT NULL,
active ENUM('Y','N'),
asterisk_version VARCHAR(20) default '1.4.21.2',
max_vicidial_trunks SMALLINT(4) default '23',
telnet_host VARCHAR(20) NOT NULL default 'localhost',
telnet_port INT(5) NOT NULL default '5038',
ASTmgrUSERNAME VARCHAR(20) NOT NULL default 'cron',
ASTmgrSECRET VARCHAR(20) NOT NULL default '1234',
ASTmgrUSERNAMEupdate VARCHAR(20) NOT NULL default 'updatecron',
ASTmgrUSERNAMElisten VARCHAR(20) NOT NULL default 'listencron',
ASTmgrUSERNAMEsend VARCHAR(20) NOT NULL default 'sendcron',
local_gmt VARCHAR(6) default '-5.00',
voicemail_dump_exten VARCHAR(20) NOT NULL default '85026666666666',
answer_transfer_agent VARCHAR(20) NOT NULL default '8365',
ext_context VARCHAR(20) NOT NULL default 'default',
sys_perf_log ENUM('Y','N') default 'N',
vd_server_logs ENUM('Y','N') default 'Y',
agi_output ENUM('NONE','STDERR','FILE','BOTH') default 'FILE',
vicidial_balance_active ENUM('Y','N') default 'N',
balance_trunks_offlimits SMALLINT(5) UNSIGNED default '0',
recording_web_link ENUM('SERVER_IP','ALT_IP','EXTERNAL_IP') default 'SERVER_IP',
alt_server_ip VARCHAR(100) default '',
active_asterisk_server ENUM('Y','N') default 'Y',
generate_vicidial_conf ENUM('Y','N') default 'Y',
rebuild_conf_files ENUM('Y','N') default 'Y',
outbound_calls_per_second SMALLINT(3) UNSIGNED default '5',
sysload INT(6) NOT NULL default '0',
channels_total SMALLINT(4) UNSIGNED NOT NULL default '0',
cpu_idle_percent SMALLINT(3) UNSIGNED NOT NULL default '0',
disk_usage VARCHAR(255) default '1',
sounds_update ENUM('Y','N') default 'N',
vicidial_recording_limit MEDIUMINT(8) default '60',
carrier_logging_active ENUM('Y','N') default 'Y',
vicidial_balance_rank TINYINT(3) UNSIGNED default '0',
rebuild_music_on_hold ENUM('Y','N') default 'Y',
active_agent_login_server ENUM('Y','N') default 'Y',
conf_secret VARCHAR(100) default 'test',
external_server_ip VARCHAR(100) default '',
custom_dialplan_entry TEXT,
active_twin_server_ip VARCHAR(15) default '',
user_group VARCHAR(20) default '---ALL---',
audio_store_purge TEXT,
svn_revision INT(9) default '0',
svn_info TEXT,
system_uptime VARCHAR(255) default '',
auto_restart_asterisk ENUM('Y','N') default 'N',
asterisk_temp_no_restart ENUM('Y','N') default 'N',
voicemail_dump_exten_no_inst VARCHAR(20) default '85026666666667',
gather_asterisk_output ENUM('Y','N') default 'N',
web_socket_url VARCHAR(255) default '',
conf_qualify ENUM('Y','N') default 'Y',
routing_prefix VARCHAR(10) default '13',
external_web_socket_url VARCHAR(255) default '',
conf_engine ENUM('MEETME','CONFBRIDGE') COLLATE utf8_unicode_ci DEFAULT 'MEETME',
conf_update_interval SMALLINT(6) NOT NULL DEFAULT '60',
ara_url TEXT
) ENGINE=MyISAM;

CREATE UNIQUE INDEX server_id on servers (server_id);

CREATE TABLE live_channels (
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
channel_group VARCHAR(30),
extension VARCHAR(100),
channel_data VARCHAR(100)
) ENGINE=MyISAM;

CREATE TABLE live_sip_channels (
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
channel_group VARCHAR(30),
extension VARCHAR(100),
channel_data VARCHAR(100)
) ENGINE=MyISAM;

CREATE TABLE parked_channels (
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
channel_group VARCHAR(30),
extension VARCHAR(100),
parked_by VARCHAR(100),
parked_time DATETIME
) ENGINE=MyISAM;

CREATE TABLE conferences (
conf_exten INT(7) UNSIGNED NOT NULL,
server_ip VARCHAR(15) NOT NULL,
extension VARCHAR(100),
unique index extenserver (conf_exten, server_ip)
) ENGINE=MyISAM;

CREATE TABLE recording_log (
recording_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
channel VARCHAR(100),
server_ip VARCHAR(15),
extension VARCHAR(100),
start_time DATETIME,
start_epoch INT(10) UNSIGNED,
end_time DATETIME,
end_epoch INT(10) UNSIGNED,
length_in_sec MEDIUMINT(8) UNSIGNED,
length_in_min DOUBLE(8,2),
filename VARCHAR(100),
location VARCHAR(255),
lead_id INT(9) UNSIGNED,
user VARCHAR(20),
vicidial_id VARCHAR(20),
index(filename),
index(lead_id),
index(user),
index(vicidial_id)
) ENGINE=MyISAM;

CREATE TABLE live_inbound (
uniqueid VARCHAR(20) NOT NULL,
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
caller_id VARCHAR(30),
extension VARCHAR(100),
phone_ext VARCHAR(40),
start_time DATETIME,
acknowledged ENUM('Y','N') default 'N',
inbound_number VARCHAR(20),
comment_a VARCHAR(50),
comment_b VARCHAR(50),
comment_c VARCHAR(50),
comment_d VARCHAR(50),
comment_e VARCHAR(50)
) ENGINE=MyISAM;

CREATE TABLE inbound_numbers (
extension VARCHAR(30) NOT NULL,
full_number VARCHAR(30) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
inbound_name VARCHAR(30),
department VARCHAR(30)
) ENGINE=MyISAM;

CREATE TABLE server_updater (
server_ip VARCHAR(15) NOT NULL,
last_update DATETIME,
db_time TIMESTAMP,
unique index serverip (server_ip)
) ENGINE=MyISAM;

CREATE TABLE call_log (
uniqueid VARCHAR(20) PRIMARY KEY NOT NULL,
channel VARCHAR(100),
channel_group VARCHAR(30),
type VARCHAR(10),
server_ip VARCHAR(15),
extension VARCHAR(100),
number_dialed VARCHAR(15),
caller_code VARCHAR(20),
start_time DATETIME,
start_epoch INT(10),
end_time DATETIME,
end_epoch INT(10),
length_in_sec INT(10),
length_in_min DOUBLE(8,2),
index (caller_code),
index (server_ip),
index (channel)
) ENGINE=MyISAM;

CREATE TABLE park_log (
uniqueid VARCHAR(20) default '',
status VARCHAR(10),
channel VARCHAR(100),
channel_group VARCHAR(30),
server_ip VARCHAR(15),
parked_time DATETIME,
grab_time DATETIME,
hangup_time DATETIME,
parked_sec INT(10),
talked_sec INT(10),
extension VARCHAR(100),
user VARCHAR(20),
lead_id INT(9) UNSIGNED default '0',
campaign_id VARCHAR(20) default '',
index (parked_time),
index (lead_id),
index (campaign_id)
) ENGINE=MyISAM;

CREATE INDEX uniqueid_park on park_log (uniqueid);

CREATE TABLE vicidial_manager (
man_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
uniqueid VARCHAR(20),
entry_date DATETIME,
status  ENUM('NEW','QUEUE','SENT','UPDATED','DEAD'),
response  ENUM('Y','N'),
server_ip VARCHAR(15) NOT NULL,
channel VARCHAR(100),
action VARCHAR(20),
callerid VARCHAR(20),
cmd_line_b VARCHAR(100),
cmd_line_c VARCHAR(100),
cmd_line_d VARCHAR(200),
cmd_line_e VARCHAR(100),
cmd_line_f VARCHAR(100),
cmd_line_g VARCHAR(100),
cmd_line_h VARCHAR(100),
cmd_line_i VARCHAR(50),
cmd_line_j VARCHAR(50),
cmd_line_k VARCHAR(100),
index (callerid),
index (uniqueid),
index serverstat(server_ip,status)
) ENGINE=MyISAM;

CREATE TABLE vicidial_list (
lead_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
entry_date DATETIME,
modify_date TIMESTAMP,
status VARCHAR(6),
user VARCHAR(20),
vendor_lead_code VARCHAR(20),
source_id VARCHAR(50),
list_id BIGINT(14) UNSIGNED NOT NULL DEFAULT '0',
gmt_offset_now DECIMAL(4,2) DEFAULT '0.00',
called_since_last_reset ENUM('Y','N','Y1','Y2','Y3','Y4','Y5','Y6','Y7','Y8','Y9','Y10','D') default 'N',
phone_code VARCHAR(10),
phone_number VARCHAR(18) NOT NULL,
title VARCHAR(4),
first_name VARCHAR(30),
middle_initial VARCHAR(1),
last_name VARCHAR(30),
address1 VARCHAR(100),
address2 VARCHAR(100),
address3 VARCHAR(100),
city VARCHAR(50),
state VARCHAR(2),
province VARCHAR(50),
postal_code VARCHAR(10),
country_code VARCHAR(3),
gender ENUM('M','F','U') default 'U',
date_of_birth DATE,
alt_phone VARCHAR(12),
email VARCHAR(70),
security_phrase VARCHAR(100),
comments VARCHAR(255),
called_count SMALLINT(5) UNSIGNED default '0',
last_local_call_time DATETIME,
rank SMALLINT(5) NOT NULL default '0',
owner VARCHAR(20) default '',
entry_list_id BIGINT(14) UNSIGNED NOT NULL DEFAULT '0',
index (phone_number),
index (list_id),
index (called_since_last_reset),
index (status),
index (gmt_offset_now),
index (postal_code),
index (last_local_call_time),
index (rank),
index (owner)
) ENGINE=MyISAM;

CREATE TABLE vicidial_hopper (
hopper_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
campaign_id VARCHAR(8),
status ENUM('READY','QUEUE','INCALL','DONE','HOLD','DNC','RHOLD','RQUEUE') default 'READY',
user VARCHAR(20),
list_id BIGINT(14) UNSIGNED NOT NULL,
gmt_offset_now DECIMAL(4,2) DEFAULT '0.00',
state VARCHAR(2) default '',
alt_dial VARCHAR(6) default 'NONE',
priority TINYINT(2) default '0',
source VARCHAR(1) default '',
vendor_lead_code VARCHAR(20) default '',
index (lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_live_agents (
live_agent_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20),
server_ip VARCHAR(15) NOT NULL,
conf_exten VARCHAR(20),
extension VARCHAR(100),
status ENUM('READY','QUEUE','INCALL','PAUSED','CLOSER','MQUEUE') default 'PAUSED',
lead_id INT(9) UNSIGNED NOT NULL,
campaign_id VARCHAR(8),
uniqueid VARCHAR(20),
callerid VARCHAR(20),
channel VARCHAR(100),
random_id INT(8) UNSIGNED,
last_call_time DATETIME,
last_update_time TIMESTAMP,
last_call_finish DATETIME,
closer_campaigns TEXT,
call_server_ip VARCHAR(15),
user_level TINYINT(3) UNSIGNED default '0',
comments VARCHAR(20),
campaign_weight TINYINT(1) default '0',
calls_today SMALLINT(5) UNSIGNED default '0',
external_hangup VARCHAR(1) default '',
external_status VARCHAR(255) default '',
external_pause VARCHAR(20) default '',
external_dial VARCHAR(100) default '',
external_ingroups TEXT,
external_blended ENUM('0','1') default '0',
external_igb_set_user VARCHAR(20) default '',
external_update_fields ENUM('0','1') default '0',
external_update_fields_data VARCHAR(255) default '',
external_timer_action VARCHAR(20) default '',
external_timer_action_message VARCHAR(255) default '',
external_timer_action_seconds MEDIUMINT(7) default '-1',
agent_log_id INT(9) UNSIGNED default '0',
last_state_change DATETIME,
agent_territories TEXT,
outbound_autodial ENUM('Y','N') default 'N',
manager_ingroup_set ENUM('Y','N','SET') default 'N',
ra_user VARCHAR(20) default '',
ra_extension VARCHAR(100) default '',
external_dtmf VARCHAR(100) default '',
external_transferconf VARCHAR(120) default '',
external_park VARCHAR(40) default '',
external_timer_action_destination VARCHAR(100) default '',
on_hook_agent ENUM('Y','N') default 'N',
on_hook_ring_time SMALLINT(5) default '15',
ring_callerid VARCHAR(20) default '',
last_inbound_call_time DATETIME,
last_inbound_call_finish DATETIME,
campaign_grade TINYINT(2) UNSIGNED default '1',
external_recording VARCHAR(20) default '',
external_pause_code VARCHAR(6) default '',
pause_code VARCHAR(6) default '',
preview_lead_id INT(9) UNSIGNED default '0',
external_lead_id INT(9) UNSIGNED default '0',
last_inbound_call_time_filtered DATETIME,
last_inbound_call_finish_filtered DATETIME,
dial_campaign_id VARCHAR(8) default '',
index (random_id),
index (last_call_time),
index (last_update_time),
index (last_call_finish)
) ENGINE=MyISAM;

CREATE TABLE vicidial_auto_calls (
auto_call_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
server_ip VARCHAR(15) NOT NULL,
campaign_id VARCHAR(20),
status ENUM('SENT','RINGING','LIVE','XFER','PAUSED','CLOSER','BUSY','DISCONNECT','IVR') default 'PAUSED',
lead_id INT(9) UNSIGNED NOT NULL,
uniqueid VARCHAR(20),
callerid VARCHAR(20),
channel VARCHAR(100),
phone_code VARCHAR(10),
phone_number VARCHAR(18),
call_time DATETIME,
call_type ENUM('IN','OUT','OUTBALANCE') default 'OUT',
stage VARCHAR(20) default 'START',
last_update_time TIMESTAMP,
alt_dial VARCHAR(6) default 'NONE',
queue_priority TINYINT(2) default '0',
agent_only VARCHAR(20) default '',
agent_grab VARCHAR(20) default '',
queue_position SMALLINT(4) UNSIGNED default '1',
extension VARCHAR(100) default '',
agent_grab_extension VARCHAR(100) default '',
index (uniqueid),
index (callerid),
index (call_time),
index (last_update_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_log (
uniqueid VARCHAR(20) PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED,
campaign_id VARCHAR(8),
call_date DATETIME,
start_epoch INT(10) UNSIGNED,
end_epoch INT(10) UNSIGNED,
length_in_sec INT(10),
status VARCHAR(6),
phone_code VARCHAR(10),
phone_number VARCHAR(18),
user VARCHAR(20),
comments VARCHAR(255),
processed ENUM('Y','N'),
user_group VARCHAR(20),
term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE','SYSTEM') default 'NONE',
alt_dial VARCHAR(6) default 'NONE',
called_count SMALLINT(5) UNSIGNED default '0',
index (lead_id),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_closer_log (
closecallid INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED,
campaign_id VARCHAR(20),
call_date DATETIME,
start_epoch INT(10) UNSIGNED,
end_epoch INT(10) UNSIGNED,
length_in_sec INT(10),
status VARCHAR(6),
phone_code VARCHAR(10),
phone_number VARCHAR(18),
user VARCHAR(20),
comments VARCHAR(255),
processed ENUM('Y','N'),
queue_seconds DECIMAL(7,2) default '0',
user_group VARCHAR(20),
xfercallid INT(9) UNSIGNED,
term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','HOLDRECALLXFER','HOLDTIME','NOAGENT','NONE','MAXCALLS','ACFILTER','CLOSETIME') default 'NONE',
uniqueid VARCHAR(20) NOT NULL default '',
agent_only VARCHAR(20) default '',
queue_position SMALLINT(4) UNSIGNED default '1',
called_count SMALLINT(5) UNSIGNED default '0',
index (lead_id),
index (call_date),
index (campaign_id),
index (uniqueid)
) ENGINE=MyISAM;

CREATE TABLE vicidial_xfer_log (
xfercallid INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED,
campaign_id VARCHAR(20),
call_date DATETIME,
phone_code VARCHAR(10),
phone_number VARCHAR(18),
user VARCHAR(20),
closer VARCHAR(20),
front_uniqueid VARCHAR(50) default '',
close_uniqueid VARCHAR(50) default '',
index (lead_id),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_users (
user_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20) NOT NULL,
pass VARCHAR(100) NOT NULL,
full_name VARCHAR(50),
user_level TINYINT(3) UNSIGNED default '1',
user_group VARCHAR(20),
phone_login VARCHAR(20),
phone_pass VARCHAR(100),
delete_users ENUM('0','1') default '0',
delete_user_groups ENUM('0','1') default '0',
delete_lists ENUM('0','1') default '0',
delete_campaigns ENUM('0','1') default '0',
delete_ingroups ENUM('0','1') default '0',
delete_remote_agents ENUM('0','1') default '0',
load_leads ENUM('0','1') default '0',
campaign_detail ENUM('0','1') default '0',
ast_admin_access ENUM('0','1') default '0',
ast_delete_phones ENUM('0','1') default '0',
delete_scripts ENUM('0','1') default '0',
modify_leads ENUM('0','1','2','3','4','5','6') default '0',
hotkeys_active ENUM('0','1') default '0',
change_agent_campaign ENUM('0','1') default '0',
agent_choose_ingroups ENUM('0','1') default '1',
closer_campaigns TEXT,
scheduled_callbacks ENUM('0','1') default '1',
agentonly_callbacks ENUM('0','1') default '0',
agentcall_manual ENUM('0','1','2','3','4','5') default '0',
vicidial_recording ENUM('0','1') default '1',
vicidial_transfers ENUM('0','1') default '1',
delete_filters ENUM('0','1') default '0',
alter_agent_interface_options ENUM('0','1') default '0',
closer_default_blended ENUM('0','1') default '0',
delete_call_times ENUM('0','1') default '0',
modify_call_times ENUM('0','1') default '0',
modify_users ENUM('0','1') default '0',
modify_campaigns ENUM('0','1') default '0',
modify_lists ENUM('0','1') default '0',
modify_scripts ENUM('0','1') default '0',
modify_filters ENUM('0','1') default '0',
modify_ingroups ENUM('0','1') default '0',
modify_usergroups ENUM('0','1') default '0',
modify_remoteagents ENUM('0','1') default '0',
modify_servers ENUM('0','1') default '0',
view_reports ENUM('0','1') default '0',
vicidial_recording_override ENUM('DISABLED','NEVER','ONDEMAND','ALLCALLS','ALLFORCE') default 'DISABLED',
alter_custdata_override ENUM('NOT_ACTIVE','ALLOW_ALTER') default 'NOT_ACTIVE',
qc_enabled ENUM('0','1') default '0',
qc_user_level INT(2) default '1',
qc_pass ENUM('0','1') default '0',
qc_finish ENUM('0','1') default '0',
qc_commit ENUM('0','1') default '0',
add_timeclock_log ENUM('0','1') default '0',
modify_timeclock_log ENUM('0','1') default '0',
delete_timeclock_log ENUM('0','1') default '0',
alter_custphone_override ENUM('NOT_ACTIVE','ALLOW_ALTER') default 'NOT_ACTIVE',
vdc_agent_api_access ENUM('0','1') default '0',
modify_inbound_dids ENUM('0','1') default '0',
delete_inbound_dids ENUM('0','1') default '0',
active ENUM('Y','N') default 'Y',
alert_enabled ENUM('0','1') default '0',
download_lists ENUM('0','1') default '0',
agent_shift_enforcement_override ENUM('DISABLED','OFF','START','ALL') default 'DISABLED',
manager_shift_enforcement_override ENUM('0','1') default '0',
shift_override_flag ENUM('0','1') default '0',
export_reports ENUM('0','1') default '0',
delete_from_dnc ENUM('0','1') default '0',
email VARCHAR(100) default '',
user_code VARCHAR(100) default '',
territory VARCHAR(100) default '',
allow_alerts ENUM('0','1') default '0',
agent_choose_territories ENUM('0','1') default '1',
custom_one VARCHAR(100) default '',
custom_two VARCHAR(100) default '',
custom_three VARCHAR(100) default '',
custom_four VARCHAR(100) default '',
custom_five VARCHAR(100) default '',
voicemail_id VARCHAR(10),
agent_call_log_view_override ENUM('DISABLED','Y','N') default 'DISABLED',
callcard_admin ENUM('1','0') default '0',
agent_choose_blended ENUM('0','1') default '1',
realtime_block_user_info ENUM('0','1') default '0',
custom_fields_modify ENUM('0','1') default '0',
force_change_password ENUM('Y','N') default 'N',
agent_lead_search_override ENUM('NOT_ACTIVE','ENABLED','LIVE_CALL_INBOUND','LIVE_CALL_INBOUND_AND_MANUAL','DISABLED') default 'NOT_ACTIVE',
modify_shifts ENUM('1','0') default '0',
modify_phones ENUM('1','0') default '0',
modify_carriers ENUM('1','0') default '0',
modify_labels ENUM('1','0') default '0',
modify_statuses ENUM('1','0') default '0',
modify_voicemail ENUM('1','0') default '0',
modify_audiostore ENUM('1','0') default '0',
modify_moh ENUM('1','0') default '0',
modify_tts ENUM('1','0') default '0',
preset_contact_search ENUM('NOT_ACTIVE','ENABLED','DISABLED') default 'NOT_ACTIVE',
modify_contacts ENUM('1','0') default '0',
modify_same_user_level ENUM('0','1') default '1',
admin_hide_lead_data ENUM('0','1') default '0',
admin_hide_phone_data ENUM('0','1','2_DIGITS','3_DIGITS','4_DIGITS') default '0',
agentcall_email ENUM('0','1') default '0',
modify_email_accounts ENUM('0','1') default '0',
failed_login_count TINYINT(3) UNSIGNED default '0',
last_login_date DATETIME default '2001-01-01 00:00:01',
last_ip VARCHAR(15) default '',
pass_hash VARCHAR(500) default '',
alter_admin_interface_options ENUM('0','1') default '1',
max_inbound_calls SMALLINT(5) UNSIGNED default '0',
modify_custom_dialplans ENUM('1','0') default '0',
wrapup_seconds_override SMALLINT(4) default '-1',
modify_languages ENUM('1','0') default '0',
selected_language VARCHAR(100) default 'default English',
user_choose_language ENUM('1','0') default '0',
ignore_group_on_search ENUM('1','0') default '0',
api_list_restrict ENUM('1','0') default '0',
api_allowed_functions VARCHAR(1000) default ' ALL_FUNCTIONS ',
lead_filter_id VARCHAR(20) default 'NONE',
admin_cf_show_hidden ENUM('1','0') default '0',
agentcall_chat ENUM('1','0') default '0',
user_hide_realtime ENUM('1','0') default '0',
access_recordings ENUM('0', '1') default '0',
modify_colors ENUM('1','0') default '0',
user_nickname VARCHAR(50) default '',
user_new_lead_limit SMALLINT(5) default '-1',
api_only_user ENUM('0','1') default '0',
modify_auto_reports ENUM('1','0') default '0',
modify_ip_lists ENUM('1','0') default '0',
ignore_ip_list ENUM('1','0') default '0',
ready_max_logout MEDIUMINT(7) default '-1',
export_gdpr_leads ENUM('0','1','2') default '0',
pause_code_approval ENUM('1','0') default '0',
max_hopper_calls SMALLINT(5) UNSIGNED default '0',
max_hopper_calls_hour SMALLINT(5) UNSIGNED default '0',
mute_recordings ENUM('DISABLED','Y','N') default 'DISABLED',
hide_call_log_info ENUM('DISABLED','Y','N','SHOW_1','SHOW_2','SHOW_3','SHOW_4','SHOW_5','SHOW_6','SHOW_7','SHOW_8','SHOW_9','SHOW_10') default 'DISABLED',
next_dial_my_callbacks ENUM('NOT_ACTIVE','DISABLED','ENABLED') default 'NOT_ACTIVE',
user_admin_redirect_url TEXT,
max_inbound_filter_enabled ENUM('0','1') default '0',
max_inbound_filter_statuses TEXT,
max_inbound_filter_ingroups TEXT,
max_inbound_filter_min_sec SMALLINT(5) default '-1',
status_group_id VARCHAR(20) default '',
mobile_number VARCHAR(20) default '',
two_factor_override  ENUM('NOT_ACTIVE','ENABLED','DISABLED') default 'NOT_ACTIVE',
manual_dial_filter VARCHAR(50) default 'DISABLED',
user_location VARCHAR(100) default '',
download_invalid_files ENUM('0','1') default '0',
user_group_two VARCHAR(20) default '',
failed_login_attempts_today MEDIUMINT(8) UNSIGNED default '0',
failed_login_count_today SMALLINT(6) UNSIGNED default '0',
failed_last_ip_today VARCHAR(50) default '',
failed_last_type_today VARCHAR(20) default '',
modify_dial_prefix ENUM('0','1','2','3','4','5','6') default '0',
inbound_credits MEDIUMINT(7) default '-1',
hci_enabled ENUM('0','1','2','3','4','5','6') default '0'
) ENGINE=MyISAM;

CREATE UNIQUE INDEX user ON vicidial_users (user);

CREATE TABLE vicidial_user_log (
user_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20),
event VARCHAR(50),
campaign_id VARCHAR(8),
event_date DATETIME,
event_epoch INT(10) UNSIGNED,
user_group VARCHAR(20),
session_id VARCHAR(20),
server_ip VARCHAR(15),
extension VARCHAR(50),
computer_ip VARCHAR(15),
browser VARCHAR(255),
data VARCHAR(255),
phone_login VARCHAR(15) default '',
server_phone VARCHAR(15) default '',
phone_ip VARCHAR(15) default '',
webserver SMALLINT(5) UNSIGNED default '0',
login_url INT(9) UNSIGNED default '0',
browser_width SMALLINT(5) UNSIGNED default '0',
browser_height SMALLINT(5) UNSIGNED default '0',
index (user),
index (event_date),
index (phone_ip)
) ENGINE=MyISAM;

CREATE TABLE vicidial_user_groups (
user_group VARCHAR(20) NOT NULL,
group_name VARCHAR(40) NOT NULL,
allowed_campaigns TEXT,
qc_allowed_campaigns TEXT,
qc_allowed_inbound_groups TEXT,
group_shifts TEXT,
forced_timeclock_login ENUM('Y','N','ADMIN_EXEMPT') default 'N',
shift_enforcement ENUM('OFF','START','ALL','ADMIN_EXEMPT') default 'OFF',
agent_status_viewable_groups TEXT,
agent_status_view_time ENUM('Y','N') default 'N',
agent_call_log_view ENUM('Y','N') default 'N',
agent_xfer_consultative ENUM('Y','N') default 'Y',
agent_xfer_dial_override ENUM('Y','N') default 'Y',
agent_xfer_vm_transfer ENUM('Y','N') default 'Y',
agent_xfer_blind_transfer ENUM('Y','N') default 'Y',
agent_xfer_dial_with_customer ENUM('Y','N') default 'Y',
agent_xfer_park_customer_dial ENUM('Y','N') default 'Y',
agent_fullscreen ENUM('Y','N') default 'N',
allowed_reports VARCHAR(4000) default 'ALL REPORTS',
webphone_url_override VARCHAR(255) default '',
webphone_systemkey_override VARCHAR(100) default '',
webphone_dialpad_override ENUM('DISABLED','Y','N','TOGGLE','TOGGLE_OFF') default 'DISABLED',
admin_viewable_groups TEXT,
admin_viewable_call_times TEXT,
allowed_custom_reports VARCHAR(2000) default '',
agent_allowed_chat_groups TEXT,
agent_xfer_park_3way ENUM('Y','N') default 'Y',
admin_ip_list VARCHAR(30) default '',
agent_ip_list VARCHAR(30) default '',
api_ip_list VARCHAR(30) default '',
webphone_layout VARCHAR(255) default '',
allowed_queue_groups TEXT,
reports_header_override ENUM('DISABLED','LOGO_ONLY_SMALL','LOGO_ONLY_LARGE','ALT_1','ALT_2','ALT_3','ALT_4') default 'DISABLED',
admin_home_url VARCHAR(255) default '',
script_id VARCHAR(20) default ''
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaigns (
campaign_id VARCHAR(8) PRIMARY KEY NOT NULL,
campaign_name VARCHAR(40),
active ENUM('Y','N'),
dial_status_a VARCHAR(6),
dial_status_b VARCHAR(6),
dial_status_c VARCHAR(6),
dial_status_d VARCHAR(6),
dial_status_e VARCHAR(6),
lead_order VARCHAR(30),
park_ext VARCHAR(10),
park_file_name VARCHAR(100) default 'default',
web_form_address TEXT,
allow_closers ENUM('Y','N'),
hopper_level INT(8) UNSIGNED default '1',
auto_dial_level VARCHAR(6) default '0',
next_agent_call VARCHAR(40) default 'longest_wait_time',
local_call_time VARCHAR(10) DEFAULT '9am-9pm',
voicemail_ext VARCHAR(10),
dial_timeout TINYINT UNSIGNED default '60',
dial_prefix VARCHAR(20) default '9',
campaign_cid VARCHAR(20) default '0000000000',
campaign_vdad_exten VARCHAR(20) default '8368',
campaign_rec_exten VARCHAR(20) default '8309',
campaign_recording ENUM('NEVER','ONDEMAND','ALLCALLS','ALLFORCE') default 'ONDEMAND',
campaign_rec_filename VARCHAR(50) default 'FULLDATE_CUSTPHONE',
campaign_script VARCHAR(20),
get_call_launch ENUM('NONE','SCRIPT','SCRIPTTWO','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM','PREVIEW_WEBFORM','PREVIEW_WEBFORMTWO','PREVIEW_WEBFORMTHREE','PREVIEW_SCRIPT','PREVIEW_SCRIPTTWO','PREVIEW_FORM') default 'NONE',
am_message_exten VARCHAR(100) default 'vm-goodbye',
amd_send_to_vmx ENUM('Y','N') default 'N',
xferconf_a_dtmf VARCHAR(50),
xferconf_a_number VARCHAR(50),
xferconf_b_dtmf VARCHAR(50),
xferconf_b_number VARCHAR(50),
alt_number_dialing ENUM('N','Y','SELECTED','SELECTED_TIMER_ALT','SELECTED_TIMER_ADDR3','UNSELECTED','UNSELECTED_TIMER_ALT','UNSELECTED_TIMER_ADDR3') default 'N',
scheduled_callbacks ENUM('Y','N') default 'N',
lead_filter_id VARCHAR(20) default 'NONE',
drop_call_seconds TINYINT(3) default '5',
drop_action ENUM('HANGUP','MESSAGE','VOICEMAIL','IN_GROUP','AUDIO','CALLMENU','VMAIL_NO_INST') default 'AUDIO',
safe_harbor_exten VARCHAR(20)  default '8307',
display_dialable_count ENUM('Y','N') default 'Y',
wrapup_seconds SMALLINT(3) UNSIGNED default '0',
wrapup_message VARCHAR(255) default 'Wrapup Call',
closer_campaigns TEXT,
use_internal_dnc ENUM('Y','N','AREACODE') default 'N',
allcalls_delay SMALLINT(3) UNSIGNED default '0',
omit_phone_code ENUM('Y','N') default 'N',
dial_method ENUM('MANUAL','RATIO','ADAPT_HARD_LIMIT','ADAPT_TAPERED','ADAPT_AVERAGE','INBOUND_MAN','SHARED_RATIO','SHARED_ADAPT_HARD_LIMIT','SHARED_ADAPT_TAPERED','SHARED_ADAPT_AVERAGE') default 'MANUAL',
available_only_ratio_tally ENUM('Y','N') default 'N',
adaptive_dropped_percentage VARCHAR(4) default '3',
adaptive_maximum_level VARCHAR(6) default '3.0',
adaptive_latest_server_time VARCHAR(4) default '2100',
adaptive_intensity VARCHAR(6) default '0',
adaptive_dl_diff_target SMALLINT(3) default '0',
concurrent_transfers ENUM('AUTO','1','2','3','4','5','6','7','8','9','10','15','20','25','30','40','50','60','80','100','1000','10000') default 'AUTO',
auto_alt_dial ENUM('NONE','ALT_ONLY','ADDR3_ONLY','ALT_AND_ADDR3','ALT_AND_EXTENDED','ALT_AND_ADDR3_AND_EXTENDED','EXTENDED_ONLY','MULTI_LEAD') default 'NONE',
auto_alt_dial_statuses VARCHAR(255) default ' B N NA DC -',
agent_pause_codes_active ENUM('Y','N','FORCE') default 'N',
campaign_description VARCHAR(255),
campaign_changedate DATETIME,
campaign_stats_refresh ENUM('Y','N') default 'N',
campaign_logindate DATETIME,
dial_statuses VARCHAR(255) default ' NEW -',
disable_alter_custdata ENUM('Y','N') default 'N',
no_hopper_leads_logins ENUM('Y','N') default 'N',
list_order_mix VARCHAR(20) default 'DISABLED',
campaign_allow_inbound ENUM('Y','N') default 'N',
manual_dial_list_id BIGINT(14) UNSIGNED default '998',
default_xfer_group VARCHAR(20) default '---NONE---',
xfer_groups TEXT,
queue_priority TINYINT(2) default '50',
drop_inbound_group VARCHAR(20) default '---NONE---',
qc_enabled ENUM('Y','N') default 'N',
qc_statuses TEXT,
qc_lists TEXT,
qc_shift_id VARCHAR(20) default '24HRMIDNIGHT',
qc_get_record_launch ENUM('NONE','SCRIPT','WEBFORM','QCSCRIPT','QCWEBFORM') default 'NONE',
qc_show_recording ENUM('Y','N') default 'Y',
qc_web_form_address VARCHAR(255),
qc_script VARCHAR(20),
survey_first_audio_file TEXT,
survey_dtmf_digits VARCHAR(16) default '1238',
survey_ni_digit VARCHAR(1) default '8',
survey_opt_in_audio_file TEXT,
survey_ni_audio_file TEXT,
survey_method ENUM('AGENT_XFER','VOICEMAIL','EXTENSION','HANGUP','CAMPREC_60_WAV','CALLMENU','VMAIL_NO_INST') default 'AGENT_XFER',
survey_no_response_action ENUM('OPTIN','OPTOUT','DROP') default 'OPTIN',
survey_ni_status VARCHAR(6) default 'NI',
survey_response_digit_map VARCHAR(255) default '1-DEMOCRAT|2-REPUBLICAN|3-INDEPENDANT|8-OPTOUT|X-NO RESPONSE|',
survey_xfer_exten VARCHAR(20) default '8300',
survey_camp_record_dir VARCHAR(255) default '/home/survey',
disable_alter_custphone ENUM('Y','N','HIDE') default 'Y',
display_queue_count ENUM('Y','N') default 'Y',
manual_dial_filter VARCHAR(50) default 'NONE',
agent_clipboard_copy VARCHAR(50) default 'NONE',
agent_extended_alt_dial ENUM('Y','N') default 'N',
use_campaign_dnc ENUM('Y','N','AREACODE') default 'N',
three_way_call_cid ENUM('CAMPAIGN','CUSTOMER','AGENT_PHONE','AGENT_CHOOSE','CUSTOM_CID') default 'CAMPAIGN',
three_way_dial_prefix VARCHAR(20) default '',
web_form_target VARCHAR(100) NOT NULL default 'vdcwebform',
vtiger_search_category VARCHAR(100) default 'LEAD',
vtiger_create_call_record ENUM('Y','N','DISPO') default 'Y',
vtiger_create_lead_record ENUM('Y','N') default 'Y',
vtiger_screen_login ENUM('Y','N','NEW_WINDOW') default 'Y',
cpd_amd_action ENUM('DISABLED','DISPO','MESSAGE','CALLMENU','INGROUP') default 'DISABLED',
agent_allow_group_alias ENUM('Y','N') default 'N',
default_group_alias VARCHAR(30) default '',
vtiger_search_dead ENUM('DISABLED','ASK','RESURRECT') default 'ASK',
vtiger_status_call ENUM('Y','N') default 'N',
survey_third_digit VARCHAR(1) default '',
survey_third_audio_file TEXT,
survey_third_status VARCHAR(6) default 'NI',
survey_third_exten VARCHAR(20) default '8300',
survey_fourth_digit VARCHAR(1) default '',
survey_fourth_audio_file TEXT,
survey_fourth_status VARCHAR(6) default 'NI',
survey_fourth_exten VARCHAR(20) default '8300',
drop_lockout_time VARCHAR(6) default '0',
quick_transfer_button VARCHAR(20) default 'N',
prepopulate_transfer_preset ENUM('N','PRESET_1','PRESET_2','PRESET_3','PRESET_4','PRESET_5') default 'N',
drop_rate_group VARCHAR(20) default 'DISABLED',
view_calls_in_queue ENUM('NONE','ALL','1','2','3','4','5') default 'NONE',
view_calls_in_queue_launch ENUM('AUTO','MANUAL') default 'MANUAL',
grab_calls_in_queue ENUM('Y','N') default 'N',
call_requeue_button ENUM('Y','N') default 'N',
pause_after_each_call ENUM('Y','N') default 'N',
no_hopper_dialing ENUM('Y','N') default 'N',
agent_dial_owner_only ENUM('NONE','USER','TERRITORY','USER_GROUP','USER_BLANK','TERRITORY_BLANK','USER_GROUP_BLANK') default 'NONE',
agent_display_dialable_leads ENUM('Y','N') default 'N',
web_form_address_two TEXT,
waitforsilence_options VARCHAR(25) default '',
agent_select_territories ENUM('Y','N') default 'N',
campaign_calldate DATETIME,
crm_popup_login ENUM('Y','N') default 'N',
crm_login_address TEXT,
timer_action VARCHAR(20) default 'NONE',
timer_action_message VARCHAR(255) default '',
timer_action_seconds MEDIUMINT(7) default '-1',
start_call_url TEXT,
dispo_call_url TEXT,
xferconf_c_number VARCHAR(50) default '',
xferconf_d_number VARCHAR(50) default '',
xferconf_e_number VARCHAR(50) default '',
use_custom_cid ENUM('Y','N','AREACODE','USER_CUSTOM_1','USER_CUSTOM_2','USER_CUSTOM_3','USER_CUSTOM_4','USER_CUSTOM_5') default 'N',
scheduled_callbacks_alert ENUM('NONE','BLINK','RED','BLINK_RED','BLINK_DEFER','RED_DEFER','BLINK_RED_DEFER') default 'NONE',
queuemetrics_callstatus_override ENUM('DISABLED','NO','YES') default 'DISABLED',
extension_appended_cidname ENUM('Y','N','Y_USER','Y_WITH_CAMPAIGN','Y_USER_WITH_CAMPAIGN') default 'N',
scheduled_callbacks_count ENUM('LIVE','ALL_ACTIVE') default 'ALL_ACTIVE',
manual_dial_override ENUM('NONE','ALLOW_ALL','DISABLE_ALL') default 'NONE',
blind_monitor_warning ENUM('DISABLED','ALERT','NOTICE','AUDIO','ALERT_NOTICE','ALERT_AUDIO','NOTICE_AUDIO','ALL') default 'DISABLED',
blind_monitor_message VARCHAR(255) default 'Someone is blind monitoring your session',
blind_monitor_filename VARCHAR(100) default '',
inbound_queue_no_dial ENUM('DISABLED','ENABLED','ALL_SERVERS','ENABLED_WITH_CHAT','ALL_SERVERS_WITH_CHAT') default 'DISABLED',
timer_action_destination VARCHAR(30) default '',
enable_xfer_presets ENUM('DISABLED','ENABLED','STAGING','CONTACTS') default 'DISABLED',
hide_xfer_number_to_dial ENUM('DISABLED','ENABLED') default 'DISABLED',
manual_dial_prefix VARCHAR(20) default '',
customer_3way_hangup_logging ENUM('DISABLED','ENABLED') default 'ENABLED',
customer_3way_hangup_seconds SMALLINT(5) UNSIGNED default '5',
customer_3way_hangup_action ENUM('NONE','DISPO') default 'NONE',
ivr_park_call ENUM('DISABLED','ENABLED','ENABLED_PARK_ONLY','ENABLED_BUTTON_HIDDEN') default 'DISABLED',
ivr_park_call_agi TEXT,
manual_preview_dial ENUM('DISABLED','PREVIEW_AND_SKIP','PREVIEW_ONLY') default 'PREVIEW_AND_SKIP',
realtime_agent_time_stats ENUM('DISABLED','WAIT_CUST_ACW','WAIT_CUST_ACW_PAUSE','CALLS_WAIT_CUST_ACW_PAUSE') default 'CALLS_WAIT_CUST_ACW_PAUSE',
use_auto_hopper ENUM('Y','N') default 'Y',
auto_hopper_multi VARCHAR(6) default '1',
auto_hopper_level MEDIUMINT(8) UNSIGNED default '0',
auto_trim_hopper ENUM('Y','N') default 'Y',
api_manual_dial ENUM('STANDARD','QUEUE','QUEUE_AND_AUTOCALL') default 'STANDARD',
manual_dial_call_time_check ENUM('DISABLED','ENABLED') default 'DISABLED',
display_leads_count ENUM('Y','N') default 'N',
lead_order_randomize ENUM('Y','N') default 'N',
lead_order_secondary ENUM('LEAD_ASCEND','LEAD_DESCEND','CALLTIME_ASCEND','CALLTIME_DESCEND','VENDOR_ASCEND','VENDOR_DESCEND') default 'LEAD_ASCEND',
per_call_notes ENUM('ENABLED','DISABLED') default 'DISABLED',
my_callback_option ENUM('CHECKED','UNCHECKED') default 'UNCHECKED',
agent_lead_search ENUM('ENABLED','LIVE_CALL_INBOUND','LIVE_CALL_INBOUND_AND_MANUAL','DISABLED') default 'DISABLED',
agent_lead_search_method VARCHAR(30) default 'CAMPLISTS_ALL',
queuemetrics_phone_environment VARCHAR(20) default '',
auto_pause_precall ENUM('Y','N') default 'N',
auto_pause_precall_code VARCHAR(6) default 'PRECAL',
auto_resume_precall ENUM('Y','N') default 'N',
manual_dial_cid ENUM('CAMPAIGN','AGENT_PHONE','AGENT_PHONE_OVERRIDE') default 'CAMPAIGN',
post_phone_time_diff_alert VARCHAR(30) default 'DISABLED',
custom_3way_button_transfer VARCHAR(30) default 'DISABLED',
available_only_tally_threshold ENUM('DISABLED','LOGGED-IN_AGENTS','NON-PAUSED_AGENTS','WAITING_AGENTS') default 'DISABLED',
available_only_tally_threshold_agents SMALLINT(5) UNSIGNED default '0',
dial_level_threshold ENUM('DISABLED','LOGGED-IN_AGENTS','NON-PAUSED_AGENTS','WAITING_AGENTS') default 'DISABLED',
dial_level_threshold_agents SMALLINT(5) UNSIGNED default '0',
safe_harbor_audio VARCHAR(100) default 'buzz',
safe_harbor_menu_id VARCHAR(50) default '',
survey_menu_id VARCHAR(50) default '',
callback_days_limit SMALLINT(3) default '0',
dl_diff_target_method ENUM('ADAPT_CALC_ONLY','CALLS_PLACED') default 'ADAPT_CALC_ONLY',
disable_dispo_screen ENUM('DISPO_ENABLED','DISPO_DISABLED','DISPO_SELECT_DISABLED') default 'DISPO_ENABLED',
disable_dispo_status VARCHAR(6) default '',
screen_labels VARCHAR(20) default '--SYSTEM-SETTINGS--',
status_display_fields VARCHAR(30) default 'CALLID',
na_call_url TEXT,
survey_recording ENUM('Y','N','Y_WITH_AMD') default 'N',
pllb_grouping ENUM('DISABLED','ONE_SERVER_ONLY','CASCADING') default 'DISABLED',
pllb_grouping_limit SMALLINT(5) default '50',
call_count_limit SMALLINT(5) UNSIGNED default '0',
call_count_target SMALLINT(5) UNSIGNED default '3',
callback_hours_block TINYINT(2) default '0',
callback_list_calltime ENUM('ENABLED','DISABLED') default 'DISABLED',
user_group VARCHAR(20) default '---ALL---',
hopper_vlc_dup_check ENUM('Y','N') default 'N',
in_group_dial ENUM('DISABLED','MANUAL_DIAL','NO_DIAL','BOTH') default 'DISABLED',
in_group_dial_select ENUM('AGENT_SELECTED','CAMPAIGN_SELECTED','ALL_USER_GROUP') default 'CAMPAIGN_SELECTED',
safe_harbor_audio_field VARCHAR(30) default 'DISABLED',
pause_after_next_call ENUM('ENABLED','DISABLED') default 'DISABLED',
owner_populate ENUM('ENABLED','DISABLED') default 'DISABLED',
use_other_campaign_dnc VARCHAR(8) default '',
allow_emails ENUM('Y','N') default 'N',
amd_inbound_group VARCHAR(20) default '',
amd_callmenu VARCHAR(50) default '',
survey_wait_sec TINYINT(3) default '10',
manual_dial_lead_id ENUM('Y','N') default 'N',
dead_max SMALLINT(5) UNSIGNED default '0',
dead_max_dispo VARCHAR(6) default 'DCMX',
dispo_max SMALLINT(5) UNSIGNED default '0',
dispo_max_dispo VARCHAR(6) default 'DISMX',
pause_max SMALLINT(5) UNSIGNED default '0',
max_inbound_calls SMALLINT(5) UNSIGNED default '0',
manual_dial_search_checkbox ENUM('SELECTED','SELECTED_RESET','UNSELECTED','UNSELECTED_RESET','SELECTED_LOCK','UNSELECTED_LOCK') default 'SELECTED',
hide_call_log_info ENUM('Y','N','SHOW_1','SHOW_2','SHOW_3','SHOW_4','SHOW_5','SHOW_6','SHOW_7','SHOW_8','SHOW_9','SHOW_10') default 'N',
timer_alt_seconds SMALLINT(5) default '0',
wrapup_bypass ENUM('DISABLED','ENABLED') default 'ENABLED',
wrapup_after_hotkey ENUM('DISABLED','ENABLED') default 'DISABLED',
callback_active_limit SMALLINT(5) UNSIGNED default '0',
callback_active_limit_override ENUM('N','Y') default 'N',
allow_chats ENUM('Y','N') default 'N',
comments_all_tabs ENUM('DISABLED','ENABLED') default 'DISABLED',
comments_dispo_screen ENUM('DISABLED','ENABLED','REPLACE_CALL_NOTES') default 'DISABLED',
comments_callback_screen ENUM('DISABLED','ENABLED','REPLACE_CB_NOTES') default 'DISABLED',
qc_comment_history ENUM('CLICK','AUTO_OPEN','CLICK_ALLOW_MINIMIZE','AUTO_OPEN_ALLOW_MINIMIZE') default 'CLICK',
show_previous_callback ENUM('DISABLED','ENABLED') default 'ENABLED',
clear_script ENUM('DISABLED','ENABLED') default 'DISABLED',
cpd_unknown_action ENUM('DISABLED','DISPO','MESSAGE','CALLMENU','INGROUP') default 'DISABLED',
manual_dial_search_filter VARCHAR(50) default 'NONE',
web_form_address_three TEXT,
manual_dial_override_field ENUM('ENABLED','DISABLED') default 'ENABLED',
status_display_ingroup ENUM('ENABLED','DISABLED') default 'ENABLED',
customer_gone_seconds SMALLINT(5) UNSIGNED default '30',
agent_display_fields VARCHAR(100) default '',
am_message_wildcards ENUM('Y','N') default 'N',
manual_dial_timeout VARCHAR(3) default '',
routing_initiated_recordings ENUM('Y','N') default 'Y',
manual_dial_hopper_check ENUM('Y','N') default 'N',
callback_useronly_move_minutes MEDIUMINT(5) UNSIGNED default '0',
ofcom_uk_drop_calc ENUM('Y','N') default 'N',
manual_auto_next SMALLINT(5) UNSIGNED default '0',
manual_auto_show ENUM('Y','N') default 'N',
allow_required_fields ENUM('Y','N') default 'N',
dead_to_dispo ENUM('ENABLED','DISABLED') default 'DISABLED',
agent_xfer_validation ENUM('N','Y') default 'N',
ready_max_logout MEDIUMINT(7) default '0',
callback_display_days SMALLINT(3) default '0',
three_way_record_stop ENUM('Y','N') default 'N',
hangup_xfer_record_start ENUM('Y','N') default 'N',
scheduled_callbacks_email_alert ENUM('Y', 'N') default 'N',
max_inbound_calls_outcome ENUM('DEFAULT','ALLOW_AGENTDIRECT','ALLOW_MI_PAUSE','ALLOW_AGENTDIRECT_AND_MI_PAUSE') default 'DEFAULT',
manual_auto_next_options ENUM('DEFAULT','PAUSE_NO_COUNT') default 'DEFAULT',
agent_screen_time_display VARCHAR(40) default 'DISABLED',
next_dial_my_callbacks ENUM('DISABLED','ENABLED') default 'DISABLED',
inbound_no_agents_no_dial_container VARCHAR(40) default '---DISABLED---',
inbound_no_agents_no_dial_threshold SMALLINT(5) default '0',
cid_group_id VARCHAR(20) default '---DISABLED---',
pause_max_dispo VARCHAR(6) default 'PAUSMX',
script_top_dispo ENUM('Y', 'N') default 'N',
dead_trigger_seconds SMALLINT(5) default '0',
dead_trigger_action ENUM('DISABLED','AUDIO','URL','AUDIO_AND_URL') default 'DISABLED',
dead_trigger_repeat ENUM('NO','REPEAT_ALL','REPEAT_AUDIO','REPEAT_URL') default 'NO',
dead_trigger_filename TEXT,
dead_trigger_url TEXT,
scheduled_callbacks_force_dial ENUM('N','Y') default 'N',
scheduled_callbacks_auto_reschedule VARCHAR(10) default 'DISABLED',
scheduled_callbacks_timezones_container VARCHAR(40) default 'DISABLED',
three_way_volume_buttons VARCHAR(20) default 'ENABLED',
callback_dnc ENUM('ENABLED','DISABLED') default 'DISABLED',
manual_dial_validation ENUM('Y','N') default 'N',
mute_recordings ENUM('Y','N') default 'N',
auto_active_list_new VARCHAR(20) default 'DISABLED',
call_quota_lead_ranking VARCHAR(40) default 'DISABLED',
call_quota_process_running TINYINT(3) default '0',
call_quota_last_run_date DATETIME,
sip_event_logging VARCHAR(40) default 'DISABLED',
campaign_script_two VARCHAR(20) default '',
leave_vm_no_dispo ENUM('ENABLED','DISABLED') default 'DISABLED',
leave_vm_message_group_id VARCHAR(40) default '---NONE---',
dial_timeout_lead_container VARCHAR(40) default 'DISABLED',
amd_type ENUM('AMD','CPD','KHOMP') default 'AMD',
vmm_daily_limit TINYINT(3) UNSIGNED default '0',
opensips_cid_name VARCHAR(15) default '',
amd_agent_route_options ENUM('ENABLED','DISABLED','PENDING') default 'DISABLED',
browser_alert_sound VARCHAR(20) default '---NONE---',
browser_alert_volume TINYINT(3) UNSIGNED default '50',
three_way_record_stop_exception VARCHAR(40) default 'DISABLED',
pause_max_exceptions VARCHAR(40) default '',
hopper_drop_run_trigger VARCHAR(1) default 'N',
daily_call_count_limit TINYINT(3) UNSIGNED default '0',
daily_limit_manual VARCHAR(20) default 'DISABLED',
transfer_button_launch VARCHAR(12) default 'NONE',
shared_dial_rank TINYINT(3) default '99',
agent_search_method VARCHAR(2) default '',
qc_scorecard_id VARCHAR(20) DEFAULT '',
qc_statuses_id VARCHAR(20) DEFAULT '',
clear_form ENUM('DISABLED','ENABLED','ACKNOWLEDGE') default 'ACKNOWLEDGE',
leave_3way_start_recording ENUM('DISABLED','ALL_CALLS','ALL_BUT_EXCEPTIONS','ONLY_EXCEPTIONS') default 'DISABLED',
leave_3way_start_recording_exception VARCHAR(40) default 'DISABLED',
calls_waiting_vl_one VARCHAR(25) default 'DISABLED',
calls_waiting_vl_two VARCHAR(25) default 'DISABLED',
calls_inqueue_count_one VARCHAR(40) default 'DISABLED',
calls_inqueue_count_two VARCHAR(40) default 'DISABLED',
in_man_dial_next_ready_seconds SMALLINT(5) UNSIGNED default '0',
in_man_dial_next_ready_seconds_override VARCHAR(40) default 'DISABLED',
transfer_no_dispo ENUM('DISABLED','EXTERNAL_ONLY','LOCAL_ONLY','LEAVE3WAY_ONLY','LOCAL_AND_EXTERNAL','LOCAL_AND_LEAVE3WAY','LEAVE3WAY_AND_EXTERNAL','LOCAL_AND_EXTERNAL_AND_LEAVE3WAY') default 'DISABLED',
call_limit_24hour_method ENUM('DISABLED','PHONE_NUMBER','LEAD') default 'DISABLED',
call_limit_24hour_scope ENUM('SYSTEM_WIDE','CAMPAIGN_LISTS') default 'SYSTEM_WIDE',
call_limit_24hour TINYINT(3) UNSIGNED default '0',
call_limit_24hour_override VARCHAR(40) default 'DISABLED',
cid_group_id_two VARCHAR(20) default '---DISABLED---',
incall_tally_threshold_seconds SMALLINT(5) UNSIGNED default '0',
auto_alt_threshold TINYINT(3) UNSIGNED default '0',
pause_max_url TEXT,
agent_hide_hangup ENUM('Y','N') default 'N',
ig_xfer_list_sort ENUM('GROUP_ID_UP','GROUP_ID_DOWN','GROUP_NAME_UP','GROUP_NAME_DOWN','PRIORITY_UP','PRIORITY_DOWN') default 'GROUP_ID_UP',
script_tab_frame_size VARCHAR(10) default 'DEFAULT',
max_logged_in_agents SMALLINT(5) UNSIGNED default '0',
user_group_script ENUM('DISABLED','ENABLED') default 'DISABLED',
agent_hangup_route ENUM('HANGUP','MESSAGE','EXTENSION','IN_GROUP','CALLMENU') default 'HANGUP',
agent_hangup_value TEXT,
agent_hangup_ig_override ENUM('Y','N') default 'N',
show_confetti ENUM('DISABLED','SALES','CALLBACKS','SALES_AND_CALLBACKS') default 'DISABLED',
demographic_quotas ENUM('DISABLED','ENABLED','INVALID','COMPLETE') default 'DISABLED',
demographic_quotas_container VARCHAR(40) default 'DISABLED',
demographic_quotas_rerank ENUM('NO','NOW','HOUR','MINUTE','NOW_HOUR') default 'NO',
demographic_quotas_last_rerank DATETIME default '2000-01-01 00:00:00',
demographic_quotas_list_resets ENUM('AUTO','MANUAL') default 'MANUAL',
custom_one TEXT,
custom_two TEXT,
custom_three TEXT,
custom_four TEXT,
custom_five TEXT,
dead_stop_recording ENUM('DISABLED','ALL_CALLS','OUTBOUND_ONLY','INBOUND_ONLY','AUTODIAL_ONLY','MANUAL_ONLY') default 'DISABLED',
manual_vm_status_updates ENUM('ENABLED','DISABLED') default 'ENABLED',
force_per_call_notes ENUM('DISABLED','ENABLED','5_CHARACTERS','15_CHARACTERS','30_CHARACTERS','100_CHARACTERS') default 'DISABLED',
agent_search_ingroup_list ENUM('DISABLED','ENABLED','ENABLED_OVERRIDE') default 'DISABLED',
hopper_hold_inserts ENUM('ENABLED','DISABLED','AUTONEXT') default 'DISABLED',
daily_phone_number_call_limit TINYINT(3) UNSIGNED default '0',
state_descriptions VARCHAR(40) default '---DISABLED---',
script_tab_height SMALLINT(5) default '0',
call_log_days SMALLINT(5) default '0',
leave_3way_stop_recording ENUM('DISABLED','ALL_CALLS') default 'DISABLED',
manual_minimum_ring_seconds SMALLINT(5) default '0',
manual_minimum_attempt_seconds SMALLINT(5) default '0',
manual_minimum_answer_seconds SMALLINT(5) default '0',
stereo_recording ENUM('DISABLED','CUSTOMER','CUSTOMER_MUTE') default 'DISABLED',
khomp_settings_container VARCHAR(40) DEFAULT 'KHOMPSETTINGS'
) ENGINE=MyISAM;

CREATE TABLE vicidial_lists (
list_id BIGINT(14) UNSIGNED PRIMARY KEY NOT NULL,
list_name VARCHAR(30),
campaign_id VARCHAR(8),
active ENUM('Y','N'),
list_description VARCHAR(255),
list_changedate DATETIME,
list_lastcalldate DATETIME,
reset_time VARCHAR(100) default '',
agent_script_override VARCHAR(20) default '',
campaign_cid_override VARCHAR(20) default '',
am_message_exten_override VARCHAR(100) default '',
drop_inbound_group_override VARCHAR(20) default '',
xferconf_a_number VARCHAR(50) default '',
xferconf_b_number VARCHAR(50) default '',
xferconf_c_number VARCHAR(50) default '',
xferconf_d_number VARCHAR(50) default '',
xferconf_e_number VARCHAR(50) default '',
web_form_address TEXT,
web_form_address_two TEXT,
time_zone_setting ENUM('COUNTRY_AND_AREA_CODE','POSTAL_CODE','NANPA_PREFIX','OWNER_TIME_ZONE_CODE') default 'COUNTRY_AND_AREA_CODE',
inventory_report ENUM('Y','N') default 'Y',
expiration_date DATE default '2099-12-31',
na_call_url TEXT,
local_call_time VARCHAR(10) NOT NULL DEFAULT 'campaign',
web_form_address_three TEXT,
status_group_id VARCHAR(20) default '',
user_new_lead_limit SMALLINT(5) default '-1',
inbound_list_script_override VARCHAR(20),
default_xfer_group VARCHAR(20) default '---NONE---',
daily_reset_limit SMALLINT(5) default '-1',
resets_today SMALLINT(5) UNSIGNED default '0',
auto_active_list_rank SMALLINT(5) default '0',
cache_count INT(9) UNSIGNED default '0',
cache_count_new INT(9) UNSIGNED default '0',
cache_count_dialable_new INT(9) UNSIGNED default '0',
cache_date DATETIME,
inbound_drop_voicemail VARCHAR(20),
inbound_after_hours_voicemail VARCHAR(20),
qc_scorecard_id VARCHAR(20) DEFAULT '',
qc_statuses_id VARCHAR(20) DEFAULT '',
qc_web_form_address VARCHAR(255) DEFAULT '',
auto_alt_threshold TINYINT(3) default '-1',
cid_group_id VARCHAR(20) default '---DISABLED---',
dial_prefix VARCHAR(20) default '',
weekday_resets_container VARCHAR(40) default 'DISABLED'
) ENGINE=MyISAM;

CREATE TABLE vicidial_statuses (
status VARCHAR(6) PRIMARY KEY NOT NULL,
status_name VARCHAR(30),
selectable ENUM('Y','N') default 'N',
human_answered ENUM('Y','N') default 'N',
category VARCHAR(20) default 'UNDEFINED',
sale ENUM('Y','N') default 'N',
dnc ENUM('Y','N') default 'N',
customer_contact ENUM('Y','N') default 'N',
not_interested ENUM('Y','N') default 'N',
unworkable ENUM('Y','N') default 'N',
scheduled_callback ENUM('Y','N') default 'N',
completed ENUM('Y','N') default 'N',
min_sec INT(5) UNSIGNED default '0',
max_sec INT(5) UNSIGNED default '0',
answering_machine ENUM('Y','N') default 'N'
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_statuses (
status VARCHAR(6) NOT NULL,
status_name VARCHAR(30),
selectable ENUM('Y','N'),
campaign_id VARCHAR(20),
human_answered ENUM('Y','N') default 'N',
category VARCHAR(20) default 'UNDEFINED',
sale ENUM('Y','N') default 'N',
dnc ENUM('Y','N') default 'N',
customer_contact ENUM('Y','N') default 'N',
not_interested ENUM('Y','N') default 'N',
unworkable ENUM('Y','N') default 'N',
scheduled_callback ENUM('Y','N') default 'N',
completed ENUM('Y','N') default 'N',
min_sec INT(5) UNSIGNED default '0',
max_sec INT(5) UNSIGNED default '0',
answering_machine ENUM('Y','N') default 'N',
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_hotkeys (
status VARCHAR(6) NOT NULL,
hotkey VARCHAR(1) NOT NULL,
status_name VARCHAR(30),
selectable ENUM('Y','N'),
campaign_id VARCHAR(8),
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_conferences (
conf_exten INT(7) UNSIGNED NOT NULL,
server_ip VARCHAR(15) NOT NULL,
extension VARCHAR(100),
leave_3way ENUM('0','1') default '0',
leave_3way_datetime DATETIME,
unique index vextenserver (conf_exten, server_ip)
) ENGINE=MyISAM;

CREATE UNIQUE INDEX serverconf on vicidial_conferences (server_ip, conf_exten);

CREATE TABLE vicidial_phone_codes (
country_code SMALLINT(5) UNSIGNED,
country CHAR(3),
areacode CHAR(3),
state VARCHAR(4),
GMT_offset VARCHAR(6),
DST enum('Y','N'),
DST_range VARCHAR(8),
geographic_description VARCHAR(100),
tz_code VARCHAR(4) default '',
php_tz VARCHAR(100) default ''
) ENGINE=MyISAM;

CREATE TABLE vicidial_inbound_groups (
group_id VARCHAR(20) PRIMARY KEY NOT NULL,
group_name VARCHAR(30),
group_color VARCHAR(20),
active ENUM('Y','N'),
web_form_address TEXT,
voicemail_ext VARCHAR(10),
next_agent_call VARCHAR(40) default 'longest_wait_time',
fronter_display ENUM('Y','N') default 'Y',
ingroup_script VARCHAR(20),
get_call_launch ENUM('NONE','SCRIPT','SCRIPTTWO','WEBFORM','WEBFORMTWO','WEBFORMTHREE','FORM','EMAIL') default 'NONE',
xferconf_a_dtmf VARCHAR(50),
xferconf_a_number VARCHAR(50),
xferconf_b_dtmf VARCHAR(50),
xferconf_b_number VARCHAR(50),
drop_call_seconds SMALLINT(4) unsigned default '360',
drop_action ENUM('HANGUP','MESSAGE','VOICEMAIL','IN_GROUP','CALLMENU','VMAIL_NO_INST') default 'MESSAGE',
drop_exten VARCHAR(20)  default '8307',
call_time_id VARCHAR(20) default '24hours',
after_hours_action ENUM('HANGUP','MESSAGE','EXTENSION','VOICEMAIL','IN_GROUP','CALLMENU','VMAIL_NO_INST') default 'MESSAGE',
after_hours_message_filename VARCHAR(255) default 'vm-goodbye',
after_hours_exten VARCHAR(20) default '8300',
after_hours_voicemail VARCHAR(20),
welcome_message_filename VARCHAR(255) default '---NONE---',
moh_context VARCHAR(50) default 'default',
onhold_prompt_filename VARCHAR(255) default 'generic_hold',
prompt_interval SMALLINT(5) UNSIGNED default '60',
agent_alert_exten VARCHAR(100) default 'ding',
agent_alert_delay INT(6) default '1000',
default_xfer_group VARCHAR(20) default '---NONE---',
queue_priority TINYINT(2) default '0',
drop_inbound_group VARCHAR(20) default '---NONE---',
ingroup_recording_override  ENUM('DISABLED','NEVER','ONDEMAND','ALLCALLS','ALLFORCE') default 'DISABLED',
ingroup_rec_filename VARCHAR(50) default 'NONE',
afterhours_xfer_group VARCHAR(20) default '---NONE---',
qc_enabled ENUM('Y','N') default 'N',
qc_statuses TEXT,
qc_shift_id VARCHAR(20) default '24HRMIDNIGHT',
qc_get_record_launch ENUM('NONE','SCRIPT','WEBFORM','QCSCRIPT','QCWEBFORM') default 'NONE',
qc_show_recording ENUM('Y','N') default 'Y',
qc_web_form_address VARCHAR(255),
qc_script VARCHAR(20),
play_place_in_line ENUM('Y','N') default 'N',
play_estimate_hold_time ENUM('Y','N') default 'N',
hold_time_option VARCHAR(30) default 'NONE',
hold_time_option_seconds SMALLINT(5) default '360',
hold_time_option_exten VARCHAR(20) default '8300',
hold_time_option_voicemail VARCHAR(20) default '',
hold_time_option_xfer_group VARCHAR(20) default '---NONE---',
hold_time_option_callback_filename VARCHAR(255) default 'vm-hangup',
hold_time_option_callback_list_id BIGINT(14) UNSIGNED default '999',
hold_recall_xfer_group VARCHAR(20) default '---NONE---',
no_delay_call_route ENUM('Y','N') default 'N',
play_welcome_message ENUM('ALWAYS','NEVER','IF_WAIT_ONLY','YES_UNLESS_NODELAY') default 'ALWAYS',
answer_sec_pct_rt_stat_one SMALLINT(5) UNSIGNED default '20',
answer_sec_pct_rt_stat_two SMALLINT(5) UNSIGNED default '30',
default_group_alias VARCHAR(30) default '',
no_agent_no_queue ENUM('N','Y','NO_PAUSED','NO_READY') default 'N',
no_agent_action ENUM('CALLMENU','INGROUP','DID','MESSAGE','EXTENSION','VOICEMAIL','VMAIL_NO_INST') default 'MESSAGE',
no_agent_action_value VARCHAR(255) default 'nbdy-avail-to-take-call|vm-goodbye',
web_form_address_two TEXT,
timer_action VARCHAR(20) default 'NONE',
timer_action_message VARCHAR(255) default '',
timer_action_seconds MEDIUMINT(7) default '-1',
start_call_url TEXT,
dispo_call_url TEXT,
xferconf_c_number VARCHAR(50) default '',
xferconf_d_number VARCHAR(50) default '',
xferconf_e_number VARCHAR(50) default '',
ignore_list_script_override ENUM('Y','N') default 'N',
extension_appended_cidname ENUM('Y','N','Y_USER','Y_WITH_CAMPAIGN','Y_USER_WITH_CAMPAIGN') default 'N',
uniqueid_status_display ENUM('DISABLED','ENABLED','ENABLED_PREFIX','ENABLED_PRESERVE') default 'DISABLED',
uniqueid_status_prefix VARCHAR(50) default '',
hold_time_option_minimum SMALLINT(5) default '0',
hold_time_option_press_filename VARCHAR(255) default 'to-be-called-back|digits/1',
hold_time_option_callmenu VARCHAR(50) default '',
hold_time_option_no_block ENUM('N','Y') default 'N',
hold_time_option_prompt_seconds SMALLINT(5) default '10',
onhold_prompt_no_block ENUM('N','Y') default 'N',
onhold_prompt_seconds SMALLINT(5) default '9',
hold_time_second_option VARCHAR(30) default 'NONE',
hold_time_third_option VARCHAR(30) default 'NONE',
wait_hold_option_priority ENUM('WAIT','HOLD','BOTH') default 'WAIT',
wait_time_option VARCHAR(30) default 'NONE',
wait_time_second_option VARCHAR(30) default 'NONE',
wait_time_third_option VARCHAR(30) default 'NONE',
wait_time_option_seconds SMALLINT(5) default '120',
wait_time_option_exten VARCHAR(20) default '8300',
wait_time_option_voicemail VARCHAR(20) default '',
wait_time_option_xfer_group VARCHAR(20) default '---NONE---',
wait_time_option_callmenu VARCHAR(50) default '',
wait_time_option_callback_filename VARCHAR(255) default 'vm-hangup',
wait_time_option_callback_list_id BIGINT(14) UNSIGNED default '999',
wait_time_option_press_filename VARCHAR(255) default 'to-be-called-back|digits/1',
wait_time_option_no_block ENUM('N','Y') default 'N',
wait_time_option_prompt_seconds SMALLINT(5) default '10',
timer_action_destination VARCHAR(30) default '',
calculate_estimated_hold_seconds SMALLINT(5) UNSIGNED default '0',
add_lead_url TEXT,
eht_minimum_prompt_filename VARCHAR(255) default '',
eht_minimum_prompt_no_block ENUM('N','Y') default 'N',
eht_minimum_prompt_seconds SMALLINT(5) default '10',
on_hook_ring_time SMALLINT(5) default '15',
na_call_url TEXT,
on_hook_cid VARCHAR(30) default 'CUSTOMER_PHONE_RINGAGENT',
group_calldate DATETIME,
action_xfer_cid VARCHAR(18) default 'CUSTOMER',
drop_callmenu VARCHAR(50) default '',
after_hours_callmenu VARCHAR(50) default '',
user_group VARCHAR(20) default '---ALL---',
max_calls_method ENUM('TOTAL','IN_QUEUE','DISABLED') default 'DISABLED',
max_calls_count SMALLINT(5) default '0',
max_calls_action ENUM('DROP','AFTERHOURS','NO_AGENT_NO_QUEUE','AREACODE_FILTER') default 'NO_AGENT_NO_QUEUE',
dial_ingroup_cid VARCHAR(20) default '',
group_handling ENUM('PHONE','EMAIL','CHAT') default 'PHONE',
web_form_address_three TEXT,
populate_lead_ingroup ENUM('ENABLED','DISABLED') default 'ENABLED',
drop_lead_reset ENUM('Y','N') default 'N',
after_hours_lead_reset ENUM('Y','N') default 'N',
nanq_lead_reset ENUM('Y','N') default 'N',
wait_time_lead_reset ENUM('Y','N') default 'N',
hold_time_lead_reset ENUM('Y','N') default 'N',
status_group_id VARCHAR(20) default '',
routing_initiated_recordings ENUM('Y','N') default 'Y',
on_hook_cid_number VARCHAR(18) default '',
customer_chat_screen_colors VARCHAR(20) default 'default',
customer_chat_survey_link TEXT,
customer_chat_survey_text TEXT,
populate_lead_province VARCHAR(20) default 'DISABLED',
areacode_filter ENUM('DISABLED','ALLOW_ONLY','DROP_ONLY') default 'DISABLED',
areacode_filter_seconds SMALLINT(5) default '10',
areacode_filter_action ENUM('CALLMENU','INGROUP','DID','MESSAGE','EXTENSION','VOICEMAIL','VMAIL_NO_INST') default 'MESSAGE',
areacode_filter_action_value VARCHAR(255) default 'nbdy-avail-to-take-call|vm-goodbye',
populate_state_areacode ENUM('DISABLED','NEW_LEAD_ONLY','OVERWRITE_ALWAYS') default 'DISABLED',
inbound_survey ENUM('DISABLED','ENABLED') default 'DISABLED',
inbound_survey_filename TEXT,
inbound_survey_accept_digit VARCHAR(1) default '',
inbound_survey_question_filename TEXT,
inbound_survey_callmenu TEXT,
icbq_expiration_hours SMALLINT(5) default '96',
closing_time_action VARCHAR(30) default 'DISABLED',
closing_time_now_trigger ENUM('Y','N') default 'N',
closing_time_filename TEXT,
closing_time_end_filename TEXT,
closing_time_lead_reset ENUM('Y','N') default 'N',
closing_time_option_exten VARCHAR(20) default '8300',
closing_time_option_callmenu VARCHAR(50) default '',
closing_time_option_voicemail VARCHAR(20) default '',
closing_time_option_xfer_group VARCHAR(20) default '---NONE---',
closing_time_option_callback_list_id BIGINT(14) UNSIGNED default '999',
add_lead_timezone ENUM('SERVER','PHONE_CODE_AREACODE') default 'SERVER',
icbq_call_time_id VARCHAR(20) default '24hours',
icbq_dial_filter VARCHAR(50) default 'NONE',
populate_lead_source VARCHAR(20) default 'DISABLED',
populate_lead_vendor VARCHAR(20) default 'INBOUND_NUMBER',
park_file_name VARCHAR(100) default '',
waiting_call_url_on TEXT,
waiting_call_url_off TEXT,
waiting_call_count SMALLINT(5) UNSIGNED default '0',
enter_ingroup_url TEXT,
cid_cb_confirm_number VARCHAR(20) default 'NO',
cid_cb_invalid_filter_phone_group VARCHAR(20) default '',
cid_cb_valid_length VARCHAR(30) default '10',
cid_cb_valid_filename TEXT,
cid_cb_confirmed_filename TEXT,
cid_cb_enter_filename TEXT,
cid_cb_you_entered_filename TEXT,
cid_cb_press_to_confirm_filename TEXT,
cid_cb_invalid_filename TEXT,
cid_cb_reenter_filename TEXT,
cid_cb_error_filename TEXT,
place_in_line_caller_number_filename TEXT,
place_in_line_you_next_filename TEXT,
ingroup_script_two VARCHAR(20) default '',
browser_alert_sound VARCHAR(20) default '---NONE---',
browser_alert_volume TINYINT(3) UNSIGNED default '50',
answer_signal ENUM('START','ROUTE','NONE') DEFAULT 'START',
no_agent_delay SMALLINT(5) default '0',
agent_search_method VARCHAR(2) default '',
qc_scorecard_id VARCHAR(20) DEFAULT '',
qc_statuses_id VARCHAR(20) DEFAULT '',
populate_lead_comments VARCHAR(40) default 'CALLERID_NAME',
drop_call_seconds_override VARCHAR(40) default 'DISABLED',
populate_lead_owner VARCHAR(20) default 'DISABLED',
in_queue_nanque ENUM('N','Y','NO_PAUSED','NO_PAUSED_EXCEPTIONS','NO_READY') default 'N',
in_queue_nanque_exceptions VARCHAR(40) default '',
custom_one TEXT,
custom_two TEXT,
custom_three TEXT,
custom_four TEXT,
custom_five TEXT,
second_alert_trigger VARCHAR(20) default 'DISABLED',
second_alert_trigger_seconds INT(6) default '600',
second_alert_filename VARCHAR(100) default '',
second_alert_delay INT(6) default '1000',
second_alert_container VARCHAR(40) default 'DISABLED',
second_alert_only VARCHAR(40) default 'DISABLED',
third_alert_trigger VARCHAR(20) default 'DISABLED',
third_alert_trigger_seconds INT(6) default '600',
third_alert_filename VARCHAR(100) default '',
third_alert_delay INT(6) default '1000',
third_alert_container VARCHAR(40) default 'DISABLED',
third_alert_only VARCHAR(40) default 'DISABLED',
agent_search_list VARCHAR(20) default '',
state_descriptions VARCHAR(40) default '---DISABLED---',
stereo_recording ENUM('DISABLED','CUSTOMER','CUSTOMER_MUTE') default 'DISABLED'
) ENGINE=MyISAM;

CREATE TABLE vicidial_stations (
agent_station VARCHAR(10) PRIMARY KEY NOT NULL,
phone_channel VARCHAR(100),
computer_ip VARCHAR(15) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
DB_server_ip VARCHAR(15) NOT NULL,
DB_user VARCHAR(15),
DB_pass VARCHAR(15),
DB_port VARCHAR(6)
) ENGINE=MyISAM;

CREATE TABLE vicidial_remote_agents (
remote_agent_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user_start VARCHAR(20),
number_of_lines TINYINT UNSIGNED default '1',
server_ip VARCHAR(15) NOT NULL,
conf_exten VARCHAR(20),
status ENUM('ACTIVE','INACTIVE') default 'INACTIVE',
campaign_id VARCHAR(8),
closer_campaigns TEXT,
extension_group VARCHAR(20) default 'NONE',
extension_group_order VARCHAR(20) default 'NONE',
on_hook_agent ENUM('Y','N') default 'N',
on_hook_ring_time SMALLINT(5) default '15'
) ENGINE=MyISAM;

CREATE TABLE live_inbound_log (
uniqueid VARCHAR(20) NOT NULL,
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
caller_id VARCHAR(30),
extension VARCHAR(100),
phone_ext VARCHAR(40),
start_time DATETIME,
acknowledged ENUM('Y','N') default 'N',
inbound_number VARCHAR(20),
comment_a VARCHAR(50),
comment_b VARCHAR(50),
comment_c VARCHAR(50),
comment_d VARCHAR(50),
comment_e VARCHAR(50),
index (uniqueid),
index (phone_ext),
index (start_time)
) ENGINE=MyISAM;

CREATE TABLE web_client_sessions (
extension VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
program ENUM('agc','vicidial','monitor','other') default 'agc',
start_time DATETIME NOT NULL,
session_name VARCHAR(40) UNIQUE NOT NULL
) ENGINE=MyISAM;

CREATE TABLE server_performance (
start_time DATETIME NOT NULL,
server_ip VARCHAR(15) NOT NULL,
sysload INT(6) NOT NULL,
freeram SMALLINT(5) UNSIGNED NOT NULL,
usedram SMALLINT(5) UNSIGNED NOT NULL,
processes SMALLINT(4) UNSIGNED NOT NULL,
channels_total SMALLINT(4) UNSIGNED NOT NULL,
trunks_total SMALLINT(4) UNSIGNED NOT NULL,
clients_total SMALLINT(4) UNSIGNED NOT NULL,
clients_zap SMALLINT(4) UNSIGNED NOT NULL,
clients_iax SMALLINT(4) UNSIGNED NOT NULL,
clients_local SMALLINT(4) UNSIGNED NOT NULL,
clients_sip SMALLINT(4) UNSIGNED NOT NULL,
live_recordings SMALLINT(4) UNSIGNED NOT NULL,
cpu_user_percent SMALLINT(3) UNSIGNED NOT NULL default '0',
cpu_system_percent SMALLINT(3) UNSIGNED NOT NULL default '0',
cpu_idle_percent SMALLINT(3) UNSIGNED NOT NULL default '0',
disk_reads MEDIUMINT(7),
disk_writes MEDIUMINT(7)
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_log (
agent_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20),
server_ip VARCHAR(15) NOT NULL,
event_time DATETIME,
lead_id INT(9) UNSIGNED,
campaign_id VARCHAR(8),
pause_epoch INT(10) UNSIGNED,
pause_sec SMALLINT(5) UNSIGNED default '0',
wait_epoch INT(10) UNSIGNED,
wait_sec SMALLINT(5) UNSIGNED default '0',
talk_epoch INT(10) UNSIGNED,
talk_sec SMALLINT(5) UNSIGNED default '0',
dispo_epoch INT(10) UNSIGNED,
dispo_sec SMALLINT(5) UNSIGNED default '0',
status VARCHAR(6),
user_group VARCHAR(20),
comments VARCHAR(20),
sub_status VARCHAR(6),
dead_epoch INT(10) UNSIGNED,
dead_sec SMALLINT(5) UNSIGNED default '0',
processed ENUM('Y','N') default 'N',
uniqueid VARCHAR(20) default '',
pause_type ENUM('UNDEFINED','SYSTEM','AGENT','API','ADMIN') default 'UNDEFINED',
index (lead_id),
index (user),
index (event_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_scripts (
script_id VARCHAR(20) PRIMARY KEY NOT NULL,
script_name VARCHAR(50),
script_comments VARCHAR(255),
script_text TEXT,
active ENUM('Y','N'),
user_group VARCHAR(20) default '---ALL---',
script_color VARCHAR(20) default 'white'
) ENGINE=MyISAM;

CREATE TABLE phone_favorites (
extension VARCHAR(100),
server_ip VARCHAR(15),
extensions_list TEXT
) ENGINE=MyISAM;

CREATE TABLE vicidial_callbacks (
callback_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED,
list_id BIGINT(14) UNSIGNED,
campaign_id VARCHAR(8),
status VARCHAR(10),
entry_time DATETIME,
callback_time DATETIME,
modify_date TIMESTAMP,
user VARCHAR(20),
recipient ENUM('USERONLY','ANYONE'),
comments VARCHAR(255),
user_group VARCHAR(20),
lead_status VARCHAR(6) default 'CALLBK',
email_alert datetime,
email_result ENUM('SENT','FAILED','NOT AVAILABLE'),
customer_timezone VARCHAR(100) default '',
customer_timezone_diff VARCHAR(6) default '',
customer_time DATETIME,
index (lead_id),
index (status),
index (callback_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_list_pins (
pins_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
entry_time DATETIME,
phone_number VARCHAR(18),
lead_id INT(9) UNSIGNED,
campaign_id VARCHAR(20),
product_code VARCHAR(20),
user VARCHAR(20),
digits VARCHAR(20),
index (lead_id),
index (phone_number),
index (entry_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_lead_filters (
lead_filter_id VARCHAR(20) PRIMARY KEY NOT NULL,
lead_filter_name VARCHAR(30) NOT NULL,
lead_filter_comments VARCHAR(255),
lead_filter_sql TEXT,
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM;

CREATE TABLE vicidial_call_times (
call_time_id VARCHAR(10) PRIMARY KEY NOT NULL,
call_time_name VARCHAR(30) NOT NULL,
call_time_comments VARCHAR(255) default '',
ct_default_start SMALLINT(4) unsigned NOT NULL default '900',
ct_default_stop SMALLINT(4) unsigned NOT NULL default '2100',
ct_sunday_start SMALLINT(4) unsigned default '0',
ct_sunday_stop SMALLINT(4) unsigned default '0',
ct_monday_start SMALLINT(4) unsigned default '0',
ct_monday_stop SMALLINT(4) unsigned default '0',
ct_tuesday_start SMALLINT(4) unsigned default '0',
ct_tuesday_stop SMALLINT(4) unsigned default '0',
ct_wednesday_start SMALLINT(4) unsigned default '0',
ct_wednesday_stop SMALLINT(4) unsigned default '0',
ct_thursday_start SMALLINT(4) unsigned default '0',
ct_thursday_stop SMALLINT(4) unsigned default '0',
ct_friday_start SMALLINT(4) unsigned default '0',
ct_friday_stop SMALLINT(4) unsigned default '0',
ct_saturday_start SMALLINT(4) unsigned default '0',
ct_saturday_stop SMALLINT(4) unsigned default '0',
ct_state_call_times TEXT,
default_afterhours_filename_override VARCHAR(255) default '',
sunday_afterhours_filename_override VARCHAR(255) default '',
monday_afterhours_filename_override VARCHAR(255) default '',
tuesday_afterhours_filename_override VARCHAR(255) default '',
wednesday_afterhours_filename_override VARCHAR(255) default '',
thursday_afterhours_filename_override VARCHAR(255) default '',
friday_afterhours_filename_override VARCHAR(255) default '',
saturday_afterhours_filename_override VARCHAR(255) default '',
user_group VARCHAR(20) default '---ALL---',
ct_holidays TEXT
) ENGINE=MyISAM;

CREATE TABLE vicidial_state_call_times (
state_call_time_id VARCHAR(10) PRIMARY KEY NOT NULL,
state_call_time_state VARCHAR(2) NOT NULL,
state_call_time_name VARCHAR(30) NOT NULL,
state_call_time_comments VARCHAR(255) default '',
sct_default_start SMALLINT(4) unsigned NOT NULL default '900',
sct_default_stop SMALLINT(4) unsigned NOT NULL default '2100',
sct_sunday_start SMALLINT(4) unsigned default '0',
sct_sunday_stop SMALLINT(4) unsigned default '0',
sct_monday_start SMALLINT(4) unsigned default '0',
sct_monday_stop SMALLINT(4) unsigned default '0',
sct_tuesday_start SMALLINT(4) unsigned default '0',
sct_tuesday_stop SMALLINT(4) unsigned default '0',
sct_wednesday_start SMALLINT(4) unsigned default '0',
sct_wednesday_stop SMALLINT(4) unsigned default '0',
sct_thursday_start SMALLINT(4) unsigned default '0',
sct_thursday_stop SMALLINT(4) unsigned default '0',
sct_friday_start SMALLINT(4) unsigned default '0',
sct_friday_stop SMALLINT(4) unsigned default '0',
sct_saturday_start SMALLINT(4) unsigned default '0',
sct_saturday_stop SMALLINT(4) unsigned default '0',
user_group VARCHAR(20) default '---ALL---',
ct_holidays TEXT
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_stats (
campaign_id VARCHAR(20) PRIMARY KEY NOT NULL,
update_time TIMESTAMP,
dialable_leads INT(9) UNSIGNED default '0',
calls_today INT(9) UNSIGNED default '0',
answers_today INT(9) UNSIGNED default '0',
drops_today DECIMAL(12,3) default '0',
drops_today_pct VARCHAR(6) default '0',
drops_answers_today_pct VARCHAR(6) default '0',
calls_hour INT(9) UNSIGNED default '0',
answers_hour INT(9) UNSIGNED default '0',
drops_hour INT(9) UNSIGNED default '0',
drops_hour_pct VARCHAR(6) default '0',
calls_halfhour INT(9) UNSIGNED default '0',
answers_halfhour INT(9) UNSIGNED default '0',
drops_halfhour INT(9) UNSIGNED default '0',
drops_halfhour_pct VARCHAR(6) default '0',
calls_fivemin INT(9) UNSIGNED default '0',
answers_fivemin INT(9) UNSIGNED default '0',
drops_fivemin INT(9) UNSIGNED default '0',
drops_fivemin_pct VARCHAR(6) default '0',
calls_onemin INT(9) UNSIGNED default '0',
answers_onemin INT(9) UNSIGNED default '0',
drops_onemin INT(9) UNSIGNED default '0',
drops_onemin_pct VARCHAR(6) default '0',
differential_onemin VARCHAR(20) default '0',
agents_average_onemin VARCHAR(20) default '0',
balance_trunk_fill SMALLINT(5) UNSIGNED default '0',
status_category_1 VARCHAR(20),
status_category_count_1 INT(9) UNSIGNED default '0',
status_category_2 VARCHAR(20),
status_category_count_2 INT(9) UNSIGNED default '0',
status_category_3 VARCHAR(20),
status_category_count_3 INT(9) UNSIGNED default '0',
status_category_4 VARCHAR(20),
status_category_count_4 INT(9) UNSIGNED default '0',
hold_sec_stat_one MEDIUMINT(8) UNSIGNED default '0',
hold_sec_stat_two MEDIUMINT(8) UNSIGNED default '0',
agent_non_pause_sec MEDIUMINT(8) UNSIGNED default '0',
hold_sec_answer_calls MEDIUMINT(8) UNSIGNED default '0',
hold_sec_drop_calls MEDIUMINT(8) UNSIGNED default '0',
hold_sec_queue_calls MEDIUMINT(8) UNSIGNED default '0',
agent_calls_today INT(9) UNSIGNED default '0',
agent_wait_today BIGINT(14) UNSIGNED default '0',
agent_custtalk_today BIGINT(14) UNSIGNED default '0',
agent_acw_today BIGINT(14) UNSIGNED default '0',
agent_pause_today BIGINT(14) UNSIGNED default '0',
answering_machines_today INT(9) UNSIGNED default '0',
agenthandled_today INT(9) UNSIGNED default '0',
park_calls_today MEDIUMINT(8) UNSIGNED default '0',
park_sec_today BIGINT(14) UNSIGNED default '0'
) ENGINE=MyISAM;

CREATE TABLE vicidial_dnc (
phone_number VARCHAR(18) PRIMARY KEY NOT NULL
) ENGINE=MyISAM;

CREATE TABLE vicidial_lead_recycle (
recycle_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
campaign_id VARCHAR(8),
status VARCHAR(6) NOT NULL,
attempt_delay SMALLINT(5) UNSIGNED default '1800',
attempt_maximum TINYINT(3) UNSIGNED default '2',
active ENUM('Y','N') default 'N',
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_server_stats (
campaign_id VARCHAR(20) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
update_time TIMESTAMP,
local_trunk_shortage SMALLINT(5) UNSIGNED default '0',
index (campaign_id),
index (server_ip)
) ENGINE=MyISAM;

CREATE TABLE vicidial_server_trunks (
server_ip VARCHAR(15) NOT NULL,
campaign_id VARCHAR(20) NOT NULL,
dedicated_trunks SMALLINT(5) UNSIGNED default '0',
trunk_restriction ENUM('MAXIMUM_LIMIT','OVERFLOW_ALLOWED') default 'OVERFLOW_ALLOWED',
index (campaign_id),
index (server_ip)
) ENGINE=MyISAM;

CREATE TABLE vicidial_postal_codes (
postal_code VARCHAR(10) NOT NULL,
state VARCHAR(4),
GMT_offset VARCHAR(6),
DST enum('Y','N'),
DST_range VARCHAR(8),
country CHAR(3),
country_code SMALLINT(5) UNSIGNED
) ENGINE=MyISAM;

CREATE TABLE vicidial_pause_codes (
pause_code VARCHAR(6) NOT NULL,
pause_code_name VARCHAR(30),
billable ENUM('NO','YES','HALF') default 'NO',
campaign_id VARCHAR(8),
time_limit SMALLINT(5) UNSIGNED default '65000',
require_mgr_approval ENUM('NO','YES') default 'NO',
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE system_settings (
version VARCHAR(50),
install_date VARCHAR(50),
use_non_latin ENUM('0','1') default '0',
webroot_writable ENUM('0','1') default '1',
enable_queuemetrics_logging ENUM('0','1') default '0',
queuemetrics_server_ip VARCHAR(15),
queuemetrics_dbname VARCHAR(50),
queuemetrics_login VARCHAR(50),
queuemetrics_pass VARCHAR(50),
queuemetrics_url VARCHAR(255),
queuemetrics_log_id VARCHAR(10) default 'VIC',
queuemetrics_eq_prepend VARCHAR(255) default 'NONE',
vicidial_agent_disable ENUM('NOT_ACTIVE','LIVE_AGENT','EXTERNAL','ALL') default 'ALL',
allow_sipsak_messages ENUM('0','1') default '0',
admin_home_url VARCHAR(255) default '../vicidial/welcome.php',
enable_agc_xfer_log ENUM('0','1') default '0',
db_schema_version INT(8) UNSIGNED default '0',
auto_user_add_value INT(9) UNSIGNED default '101',
timeclock_end_of_day VARCHAR(4) default '0000',
timeclock_last_reset_date DATE,
vdc_header_date_format VARCHAR(50) default 'MS_DASH_24HR  2008-06-24 23:59:59',
vdc_customer_date_format VARCHAR(50) default 'AL_TEXT_AMPM  OCT 24, 2008 11:59:59 PM',
vdc_header_phone_format VARCHAR(50) default 'US_PARN (000)000-0000',
vdc_agent_api_active ENUM('0','1') default '0',
qc_last_pull_time DATETIME,
enable_vtiger_integration ENUM('0','1') default '0',
vtiger_server_ip VARCHAR(15),
vtiger_dbname VARCHAR(50),
vtiger_login VARCHAR(50),
vtiger_pass VARCHAR(50),
vtiger_url VARCHAR(255),
qc_features_active ENUM('1','0') default '0',
outbound_autodial_active ENUM('1','0') default '1',
outbound_calls_per_second SMALLINT(3) UNSIGNED default '10',
enable_tts_integration ENUM('0','1') default '0',
agentonly_callback_campaign_lock ENUM('0','1') default '1',
sounds_central_control_active ENUM('0','1') default '0',
sounds_web_server VARCHAR(50) default '127.0.0.1',
sounds_web_directory VARCHAR(255) default '',
active_voicemail_server VARCHAR(15) default '',
auto_dial_limit VARCHAR(5) default '4',
user_territories_active ENUM('0','1') default '0',
allow_custom_dialplan ENUM('0','1') default '1',
db_schema_update_date DATETIME,
enable_second_webform ENUM('0','1') default '1',
default_webphone ENUM('1','0') default '0',
default_external_server_ip ENUM('1','0') default '0',
webphone_url VARCHAR(255) default '',
static_agent_url VARCHAR(255) default '',
default_phone_code VARCHAR(8) default '1',
enable_agc_dispo_log ENUM('0','1') default '0',
custom_dialplan_entry TEXT,
queuemetrics_loginout ENUM('STANDARD','CALLBACK','NONE') default 'STANDARD',
callcard_enabled ENUM('1','0') default '0',
queuemetrics_callstatus ENUM('0','1') default '1',
default_codecs VARCHAR(100) default '',
custom_fields_enabled ENUM('0','1') default '0',
admin_web_directory VARCHAR(255) default 'vicidial',
label_title VARCHAR(60) default '',
label_first_name VARCHAR(60) default '',
label_middle_initial VARCHAR(60) default '',
label_last_name VARCHAR(60) default '',
label_address1 VARCHAR(60) default '',
label_address2 VARCHAR(60) default '',
label_address3 VARCHAR(60) default '',
label_city VARCHAR(60) default '',
label_state VARCHAR(60) default '',
label_province VARCHAR(60) default '',
label_postal_code VARCHAR(60) default '',
label_vendor_lead_code VARCHAR(60) default '',
label_gender VARCHAR(60) default '',
label_phone_number VARCHAR(60) default '',
label_phone_code VARCHAR(60) default '',
label_alt_phone VARCHAR(60) default '',
label_security_phrase VARCHAR(60) default '',
label_email VARCHAR(60) default '',
label_comments VARCHAR(60) default '',
slave_db_server VARCHAR(50) default '',
reports_use_slave_db VARCHAR(4000) default '',
webphone_systemkey VARCHAR(100) default '',
first_login_trigger ENUM('Y','N') default 'N',
hosted_settings VARCHAR(100) default '',
default_phone_registration_password VARCHAR(100) default 'test',
default_phone_login_password VARCHAR(100) default 'test',
default_server_password VARCHAR(100) default 'test',
admin_modify_refresh SMALLINT(5) UNSIGNED default '0',
nocache_admin ENUM('0','1') default '1',
generate_cross_server_exten ENUM('0','1') default '0',
queuemetrics_addmember_enabled ENUM('0','1') default '0',
queuemetrics_dispo_pause VARCHAR(6) default '',
label_hide_field_logs VARCHAR(6) default 'Y',
queuemetrics_pe_phone_append ENUM('0','1') default '0',
test_campaign_calls ENUM('0','1') default '0',
agents_calls_reset ENUM('0','1') default '1',
voicemail_timezones TEXT,
default_voicemail_timezone VARCHAR(30) default 'eastern',
default_local_gmt VARCHAR(6) default '-5.00',
noanswer_log ENUM('Y','N') default 'N',
alt_log_server_ip VARCHAR(50) default '',
alt_log_dbname VARCHAR(50) default '',
alt_log_login VARCHAR(50) default '',
alt_log_pass VARCHAR(50) default '',
tables_use_alt_log_db VARCHAR(2000) default '',
did_agent_log ENUM('Y','N') default 'N',
campaign_cid_areacodes_enabled ENUM('0','1') default '1',
pllb_grouping_limit SMALLINT(5) default '100',
did_ra_extensions_enabled ENUM('0','1') default '0',
expanded_list_stats ENUM('0','1') default '1',
contacts_enabled ENUM('0','1') default '0',
svn_version VARCHAR(100) default '',
call_menu_qualify_enabled ENUM('0','1') default '0',
admin_list_counts ENUM('0','1') default '1',
allow_voicemail_greeting ENUM('0','1') default '0',
audio_store_purge TEXT,
svn_revision INT(9) default '0',
queuemetrics_socket VARCHAR(20) default 'NONE',
queuemetrics_socket_url TEXT,
enhanced_disconnect_logging ENUM('0','1','2','3','4','5','6') default '0',
allow_emails ENUM('0','1') default '0',
level_8_disable_add ENUM('0','1') default '0',
pass_hash_enabled ENUM('0','1') default '0',
pass_key VARCHAR(100) default '',
pass_cost TINYINT(2) UNSIGNED default '2',
disable_auto_dial ENUM('0','1') default '0',
queuemetrics_record_hold ENUM('0','1') default '0',
country_code_list_stats ENUM('0','1') default '0',
reload_timestamp DATETIME,
queuemetrics_pause_type ENUM('0','1') default '0',
frozen_server_call_clear ENUM('0','1') default '0',
callback_time_24hour ENUM('0','1') default '0',
active_modules TEXT,
allow_chats ENUM('0','1') default '0',
enable_languages ENUM('0','1') default '0',
language_method VARCHAR(20) default 'DISABLED',
meetme_enter_login_filename VARCHAR(255) default '',
meetme_enter_leave3way_filename VARCHAR(255) default '',
enable_did_entry_list_id ENUM('0','1') default '0',
enable_third_webform ENUM('0','1') default '0',
chat_url VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_timeout INT(3) unsigned DEFAULT NULL,
agent_debug_logging VARCHAR(20) default '0',
default_language VARCHAR(100) default 'default English',
agent_whisper_enabled ENUM('0','1') default '0',
user_hide_realtime_enabled ENUM('0','1') default '0',
custom_reports_use_slave_db VARCHAR(2000) default '',
usacan_phone_dialcode_fix ENUM('0','1') default '0',
cache_carrier_stats_realtime ENUM('0','1') default '0',
oldest_logs_date DATETIME,
log_recording_access ENUM('0', '1') default '0',
report_default_format ENUM('TEXT', 'HTML') default 'TEXT',
alt_ivr_logging ENUM('0', '1') default '0',
admin_row_click ENUM('0', '1') default '1',
admin_screen_colors VARCHAR(20) default 'default',
ofcom_uk_drop_calc ENUM('1','0') default '0',
agent_screen_colors VARCHAR(20) default 'default',
script_remove_js ENUM('1','0','2','3','4','5','6') default '1',
manual_auto_next ENUM('1','0') default '0',
user_new_lead_limit ENUM('1','0') default '0',
agent_xfer_park_3way ENUM('1','0') default '0',
rec_prompt_count INT(9) UNSIGNED default '0',
agent_soundboards ENUM('1','0') default '0',
web_loader_phone_length VARCHAR(10) default 'DISABLED',
agent_script VARCHAR(50) default 'vicidial.php',
vdad_debug_logging ENUM('1','0') default '0',
agent_chat_screen_colors VARCHAR(20) default 'default',
enable_auto_reports ENUM('1','0') default '0',
enable_pause_code_limits ENUM('1','0') default '0',
enable_drop_lists ENUM('0','1','2') default '0',
allow_ip_lists ENUM('0','1','2') default '0',
system_ip_blacklist VARCHAR(30) default '',
agent_push_events ENUM('0','1') default '0',
agent_push_url TEXT,
hide_inactive_lists ENUM('0','1') default '0',
allow_manage_active_lists ENUM('0','1') default '0',
expired_lists_inactive ENUM('0','1') default '0',
did_system_filter ENUM('0','1') default '0',
anyone_callback_inactive_lists ENUM('default','NO_ADD_TO_HOPPER','KEEP_IN_HOPPER') default 'default',
enable_gdpr_download_deletion ENUM('0','1','2') default '0',
source_id_display ENUM('0','1') default '0',
help_modification_date VARCHAR(20) default '0',
agent_logout_link ENUM('0','1','2','3','4') default '1',
manual_dial_validation ENUM('0','1','2','3','4') default '0',
mute_recordings ENUM('1','0') default '0',
user_admin_redirect ENUM('1','0') default '0',
list_status_modification_confirmation ENUM('1','0') default '0',
sip_event_logging ENUM('0','1','2','3','4','5','6','7') default '0',
call_quota_lead_ranking ENUM('0','1','2') default '0',
enable_second_script ENUM('0','1') default '0',
enable_first_webform ENUM('0','1') default '1',
recording_buttons VARCHAR(30) default 'START_STOP',
opensips_cid_name ENUM('0','1') default '0',
require_password_length TINYINT(3) UNSIGNED default '0',
user_account_emails ENUM('DISABLED','SEND_NO_PASS','SEND_WITH_PASS') DEFAULT 'DISABLED',
outbound_cid_any ENUM('DISABLED','API_ONLY') DEFAULT 'DISABLED',
entries_per_page SMALLINT(5) UNSIGNED DEFAULT '0',
browser_call_alerts ENUM('0','1','2') DEFAULT '0',
queuemetrics_pausereason ENUM('STANDARD','EVERY_NEW','EVERY_NEW_ADMINCALL','EVERY_NEW_ALLCALL') default 'STANDARD',
inbound_answer_config ENUM('0','1','2','3','4','5') DEFAULT '0',
enable_international_dncs ENUM('0','1') default '0',
web_loader_phone_strip VARCHAR(10) default 'DISABLED',
manual_dial_phone_strip VARCHAR(10) default 'DISABLED',
daily_call_count_limit ENUM('0','1') default '0',
allow_shared_dial ENUM('0','1','2','3','4','5','6') default '0',
agent_search_method ENUM('0','1','2','3','4','5','6') default '0',
phone_defaults_container VARCHAR(40) default '---DISABLED---',
qc_claim_limit TINYINT UNSIGNED DEFAULT '3',
qc_expire_days TINYINT UNSIGNED DEFAULT '3',
two_factor_auth_hours SMALLINT(5) default '0',
two_factor_container VARCHAR(40) default '---DISABLED---',
agent_hidden_sound VARCHAR(20) default 'click_quiet',
agent_hidden_sound_volume TINYINT(3) UNSIGNED default '25',
agent_hidden_sound_seconds TINYINT(3) UNSIGNED default '0',
agent_screen_timer VARCHAR(20) default 'setTimeout',
label_lead_id VARCHAR(60) default '',
label_list_id VARCHAR(60) default '',
label_entry_date VARCHAR(60) default '',
label_gmt_offset_now VARCHAR(60) default '',
label_source_id VARCHAR(60) default '',
label_called_since_last_reset VARCHAR(60) default '',
label_status VARCHAR(60) default '',
label_user VARCHAR(60) default '',
label_date_of_birth VARCHAR(60) default '',
label_country_code VARCHAR(60) default '',
label_last_local_call_time VARCHAR(60) default '',
label_called_count VARCHAR(60) default '',
label_rank VARCHAR(60) default '',
label_owner VARCHAR(60) default '',
label_entry_list_id VARCHAR(60) default '',
call_limit_24hour ENUM('0','1') default '0',
call_limit_24hour_reset DATETIME default '2000-01-01 00:00:01',
allowed_sip_stacks ENUM('SIP','PJSIP','SIP_and_PJSIP') default 'SIP',
agent_hide_hangup ENUM('1','0','2','3','4','5','6') default '0',
allow_web_debug ENUM('0','1','2','3','4','5','6') default '0',
max_logged_in_agents ENUM('0','1','2','3','4','5','6','7') default '0',
user_codes_admin ENUM('0','1','2','3','4','5','6','7') default '0',
login_kickall ENUM('0','1','2','3','4','5','6','7') default '0',
abandon_check_queue ENUM('0','1','2','3','4','5','6','7') default '0',
agent_notifications ENUM('0','1','2','3','4','5','6','7') default '0',
demographic_quotas ENUM('0','1','2','3','4','5','6','7') default '0',
log_latency_gaps ENUM('0','1','2','3','4','5','6','7') default '1',
inbound_credits ENUM('0','1','2','3','4','5','6','7') default '0',
weekday_resets ENUM('0','1','2','3','4','5','6','7') default '0',
two_factor_auth_agent_hours SMALLINT(5) default '0',
highest_lead_id VARCHAR(20) default '0',
hopper_hold_inserts ENUM('0','1','2','3','4','5','6','7') default '0',
coldstorage_server_ip VARCHAR(50) default '',
coldstorage_dbname VARCHAR(50) default '',
coldstorage_login VARCHAR(50) default '',
coldstorage_pass VARCHAR(50) default '',
coldstorage_port VARCHAR(10) default '',
stereo_recording ENUM('0','1','2','3','4','5','6') default '0'
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaigns_list_mix (
vcl_id VARCHAR(20) PRIMARY KEY NOT NULL,
vcl_name VARCHAR(50),
campaign_id VARCHAR(8),
list_mix_container TEXT,
mix_method ENUM('EVEN_MIX','IN_ORDER','RANDOM') default 'IN_ORDER',
status ENUM('ACTIVE','INACTIVE') default 'INACTIVE',
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_status_categories (
vsc_id VARCHAR(20) PRIMARY KEY NOT NULL,
vsc_name VARCHAR(50),
vsc_description VARCHAR(255),
tovdad_display ENUM('Y','N') default 'N',
sale_category ENUM('Y','N') default 'N',
dead_lead_category ENUM('Y','N') default 'N'
) ENGINE=MyISAM;

CREATE TABLE vicidial_ivr (
ivr_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
entry_time DATETIME,
length_in_sec SMALLINT(5) UNSIGNED default '0',
inbound_number VARCHAR(12),
recording_id INT(9) UNSIGNED,
recording_filename VARCHAR(50),
company_id VARCHAR(12),
phone_number VARCHAR(18),
lead_id INT(9) UNSIGNED,
campaign_id VARCHAR(20),
product_code VARCHAR(20),
user VARCHAR(20),
prompt_audio_1 VARCHAR(20),
prompt_response_1 TINYINT(1) UNSIGNED default '0',
prompt_audio_2 VARCHAR(20),
prompt_response_2 TINYINT(1) UNSIGNED default '0',
prompt_audio_3 VARCHAR(20),
prompt_response_3 TINYINT(1) UNSIGNED default '0',
prompt_audio_4 VARCHAR(20),
prompt_response_4 TINYINT(1) UNSIGNED default '0',
prompt_audio_5 VARCHAR(20),
prompt_response_5 TINYINT(1) UNSIGNED default '0',
prompt_audio_6 VARCHAR(20),
prompt_response_6 TINYINT(1) UNSIGNED default '0',
prompt_audio_7 VARCHAR(20),
prompt_response_7 TINYINT(1) UNSIGNED default '0',
prompt_audio_8 VARCHAR(20),
prompt_response_8 TINYINT(1) UNSIGNED default '0',
prompt_audio_9 VARCHAR(20),
prompt_response_9 TINYINT(1) UNSIGNED default '0',
prompt_audio_10 VARCHAR(20),
prompt_response_10 TINYINT(1) UNSIGNED default '0',
prompt_audio_11 VARCHAR(20),
prompt_response_11 TINYINT(1) UNSIGNED default '0',
prompt_audio_12 VARCHAR(20),
prompt_response_12 TINYINT(1) UNSIGNED default '0',
prompt_audio_13 VARCHAR(20),
prompt_response_13 TINYINT(1) UNSIGNED default '0',
prompt_audio_14 VARCHAR(20),
prompt_response_14 TINYINT(1) UNSIGNED default '0',
prompt_audio_15 VARCHAR(20),
prompt_response_15 TINYINT(1) UNSIGNED default '0',
prompt_audio_16 VARCHAR(20),
prompt_response_16 TINYINT(1) UNSIGNED default '0',
prompt_audio_17 VARCHAR(20),
prompt_response_17 TINYINT(1) UNSIGNED default '0',
prompt_audio_18 VARCHAR(20),
prompt_response_18 TINYINT(1) UNSIGNED default '0',
prompt_audio_19 VARCHAR(20),
prompt_response_19 TINYINT(1) UNSIGNED default '0',
prompt_audio_20 VARCHAR(20),
prompt_response_20 TINYINT(1) UNSIGNED default '0',
index (phone_number),
index (entry_time)
) ENGINE=MyISAM;

ALTER TABLE vicidial_ivr AUTO_INCREMENT = 1000000;

CREATE TABLE vicidial_inbound_group_agents (
user VARCHAR(20),
group_id VARCHAR(20),
group_rank TINYINT(1) default '0',
group_weight TINYINT(1) default '0',
calls_today SMALLINT(5) UNSIGNED default '0',
group_web_vars VARCHAR(255) default '',
group_grade TINYINT(2) UNSIGNED default '1',
group_type VARCHAR(1) default 'C',
calls_today_filtered SMALLINT(5) UNSIGNED default '0',
daily_limit SMALLINT(5) default '-1',
index (group_id),
index (user),
unique index viga_user_group_id (user, group_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_live_inbound_agents (
user VARCHAR(20),
group_id VARCHAR(20),
group_weight TINYINT(1) default '0',
calls_today SMALLINT(5) UNSIGNED default '0',
last_call_time DATETIME,
last_call_finish DATETIME,
group_grade TINYINT(2) UNSIGNED default '1',
calls_today_filtered SMALLINT(5) UNSIGNED default '0',
last_call_time_filtered DATETIME,
last_call_finish_filtered DATETIME,
daily_limit SMALLINT(5) default '-1',
index (group_id),
index (group_weight),
unique index vlia_user_group_id (user, group_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_agents (
user VARCHAR(20),
campaign_id VARCHAR(20),
campaign_rank TINYINT(1) default '0',
campaign_weight TINYINT(1) default '0',
calls_today SMALLINT(5) UNSIGNED default '0',
group_web_vars VARCHAR(255) default '',
campaign_grade TINYINT(2) UNSIGNED default '1',
hopper_calls_today SMALLINT(5) UNSIGNED default '0',
hopper_calls_hour SMALLINT(5) UNSIGNED default '0',
index (campaign_id),
index (user),
unique index vlca_user_campaign_id (user, campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_user_closer_log (
user VARCHAR(20),
campaign_id VARCHAR(20),
event_date DATETIME,
blended ENUM('1','0') default '0',
closer_campaigns TEXT,
manager_change VARCHAR(20) default '',
index (user),
index (event_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_qc_codes (
code VARCHAR(8) PRIMARY KEY NOT NULL,
code_name VARCHAR(30)
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_sph (
campaign_group_id VARCHAR(20) NOT NULL,
stat_date DATE NOT NULL,
shift VARCHAR(20) NOT NULL,
role ENUM('FRONTER','CLOSER') default 'FRONTER',
user VARCHAR(20) NOT NULL,
calls MEDIUMINT(8) UNSIGNED default '0',
sales MEDIUMINT(8) UNSIGNED default '0',
login_sec MEDIUMINT(8) UNSIGNED default '0',
login_hours DECIMAL(5,2) DEFAULT '0.00',
sph DECIMAL(6,2) DEFAULT '0.00',
index (campaign_group_id),
index (stat_date)
) ENGINE=MyISAM;

CREATE TABLE phones_alias (
alias_id VARCHAR(20) NOT NULL UNIQUE PRIMARY KEY,
alias_name VARCHAR(50),
logins_list VARCHAR(255),
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM;

CREATE TABLE vicidial_shifts (
shift_id VARCHAR(20) NOT NULL,
shift_name VARCHAR(50),
shift_start_time VARCHAR(4) default '0900',
shift_length VARCHAR(5) default '16:00',
shift_weekdays VARCHAR(7) default '0123456',
report_option ENUM('Y','N') default 'N',
user_group VARCHAR(20) default '---ALL---',
report_rank SMALLINT(5) default '1',
index (shift_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_timeclock_log (
timeclock_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
event_epoch INT(10) UNSIGNED NOT NULL,
event_date DATETIME NOT NULL,
login_sec INT(10) UNSIGNED,
event VARCHAR(50) NOT NULL,
user VARCHAR(20) NOT NULL,
user_group VARCHAR(20) NOT NULL,
ip_address VARCHAR(15),
shift_id VARCHAR(20),
notes VARCHAR(255),
manager_user VARCHAR(20),
manager_ip VARCHAR(15),
event_datestamp TIMESTAMP NOT NULL,
tcid_link INT(9) UNSIGNED,
index (user),
index (event_epoch)
) ENGINE=MyISAM;

CREATE TABLE vicidial_timeclock_status (
user VARCHAR(20) UNIQUE NOT NULL,
user_group VARCHAR(20) NOT NULL,
event_epoch INT(10) UNSIGNED,
event_date TIMESTAMP,
status VARCHAR(50),
ip_address VARCHAR(15),
shift_id VARCHAR(20),
index (user)
) ENGINE=MyISAM;

CREATE TABLE vicidial_timeclock_audit_log (
timeclock_id INT(9) UNSIGNED NOT NULL,
event_epoch INT(10) UNSIGNED NOT NULL,
event_date DATETIME NOT NULL,
login_sec INT(10) UNSIGNED,
event VARCHAR(50) NOT NULL,
user VARCHAR(20) NOT NULL,
user_group VARCHAR(20) NOT NULL,
ip_address VARCHAR(15),
shift_id VARCHAR(20),
event_datestamp TIMESTAMP NOT NULL,
tcid_link INT(9) UNSIGNED,
index (timeclock_id),
index (user)
) ENGINE=MyISAM;

CREATE TABLE vicidial_admin_log (
admin_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
event_date DATETIME NOT NULL,
user VARCHAR(20) NOT NULL,
ip_address VARCHAR(15) NOT NULL,
event_section VARCHAR(30) NOT NULL,
event_type ENUM('ADD','COPY','LOAD','RESET','MODIFY','DELETE','SEARCH','LOGIN','LOGOUT','CLEAR','OVERRIDE','EXPORT','OTHER') default 'OTHER',
record_id VARCHAR(50) NOT NULL,
event_code VARCHAR(255) NOT NULL,
event_sql MEDIUMTEXT,
event_notes MEDIUMTEXT,
user_group VARCHAR(20) default '---ALL---',
index (user),
index (event_section),
index (record_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_list_alt_phones (
alt_phone_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
phone_code VARCHAR(10),
phone_number VARCHAR(18),
alt_phone_note VARCHAR(30),
alt_phone_count SMALLINT(5) UNSIGNED,
active ENUM('Y','N') default 'Y',
index (lead_id),
index (phone_number)
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_dnc (
phone_number VARCHAR(18) NOT NULL,
campaign_id VARCHAR(8) NOT NULL,
index (phone_number),
unique index phonecamp (phone_number, campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_inbound_dids (
did_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
did_pattern VARCHAR(50) NOT NULL,
did_description VARCHAR(50),
did_active ENUM('Y','N') default 'Y',
did_route ENUM('EXTEN','VOICEMAIL','AGENT','PHONE','IN_GROUP','CALLMENU','VMAIL_NO_INST') default 'EXTEN',
extension VARCHAR(50) default '9998811112',
exten_context VARCHAR(50) default 'default',
voicemail_ext VARCHAR(10),
phone VARCHAR(100),
server_ip VARCHAR(15),
user VARCHAR(20),
user_unavailable_action ENUM('IN_GROUP','EXTEN','VOICEMAIL','PHONE','VMAIL_NO_INST') default 'VOICEMAIL',
user_route_settings_ingroup VARCHAR(20) default 'AGENTDIRECT',
group_id VARCHAR(20),
call_handle_method VARCHAR(40) default 'CID',
agent_search_method ENUM('LO','LB','SO') default 'LB',
list_id BIGINT(14) UNSIGNED default '999',
campaign_id VARCHAR(8),
phone_code VARCHAR(10) default '1',
menu_id VARCHAR(50) default '',
record_call ENUM('Y','N','Y_QUEUESTOP') default 'N',
filter_inbound_number ENUM('DISABLED','GROUP','URL','DNC_INTERNAL','DNC_CAMPAIGN','GROUP_AREACODE') default 'DISABLED',
filter_phone_group_id VARCHAR(20) default '',
filter_url VARCHAR(1000) default '',
filter_action ENUM('EXTEN','VOICEMAIL','AGENT','PHONE','IN_GROUP','CALLMENU','VMAIL_NO_INST') default 'EXTEN',
filter_extension VARCHAR(50) default '9998811112',
filter_exten_context VARCHAR(50) default 'default',
filter_voicemail_ext VARCHAR(10),
filter_phone VARCHAR(100),
filter_server_ip VARCHAR(15),
filter_user VARCHAR(20),
filter_user_unavailable_action ENUM('IN_GROUP','EXTEN','VOICEMAIL','PHONE','VMAIL_NO_INST') default 'VOICEMAIL',
filter_user_route_settings_ingroup VARCHAR(20) default 'AGENTDIRECT',
filter_group_id VARCHAR(20),
filter_call_handle_method VARCHAR(40) default 'CID',
filter_agent_search_method ENUM('LO','LB','SO') default 'LB',
filter_list_id BIGINT(14) UNSIGNED default '999',
filter_campaign_id VARCHAR(8),
filter_phone_code VARCHAR(10) default '1',
filter_menu_id VARCHAR(50) default '',
filter_clean_cid_number VARCHAR(20) default '',
custom_one VARCHAR(100) default '',
custom_two VARCHAR(100) default '',
custom_three VARCHAR(100) default '',
custom_four VARCHAR(100) default '',
custom_five VARCHAR(100) default '',
user_group VARCHAR(20) default '---ALL---',
filter_dnc_campaign VARCHAR(8) default '',
filter_url_did_redirect ENUM('Y','N') default 'N',
no_agent_ingroup_redirect ENUM('DISABLED','Y','NO_PAUSED','READY_ONLY') default 'DISABLED',
no_agent_ingroup_id VARCHAR(20) default '',
no_agent_ingroup_extension VARCHAR(50) default '9998811112',
pre_filter_phone_group_id VARCHAR(20) default '',
pre_filter_extension VARCHAR(50) default '',
entry_list_id BIGINT(14) UNSIGNED default '0',
filter_entry_list_id BIGINT(14) UNSIGNED default '0',
max_queue_ingroup_calls SMALLINT(5) default '0',
max_queue_ingroup_id VARCHAR(20) default '',
max_queue_ingroup_extension VARCHAR(50) default '9998811112',
did_carrier_description VARCHAR(255) default '',
inbound_route_answer ENUM('Y','N') DEFAULT 'Y',
pre_filter_recent_call VARCHAR(20) default '',
pre_filter_recent_extension VARCHAR(50) default '',
unique index (did_pattern),
index (group_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_did_log (
uniqueid VARCHAR(20) NOT NULL,
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
caller_id_number VARCHAR(18),
caller_id_name VARCHAR(20),
extension VARCHAR(100),
call_date DATETIME,
did_id VARCHAR(9) default '',
did_route VARCHAR(20) default '',
index (uniqueid),
index (caller_id_number),
index (extension),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_api_log (
api_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20) NOT NULL,
api_date DATETIME,
api_script VARCHAR(10),
function VARCHAR(20) NOT NULL,
agent_user VARCHAR(20),
value VARCHAR(255),
result VARCHAR(10),
result_reason VARCHAR(255),
source VARCHAR(20),
data TEXT,
run_time VARCHAR(20) default '0',
webserver SMALLINT(5) UNSIGNED default '0',
api_url INT(9) UNSIGNED default '0',
index(api_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_nanpa_prefix_codes (
areacode CHAR(3),
prefix CHAR(3),
GMT_offset VARCHAR(6),
DST enum('Y','N'),
latitude VARCHAR(17),
longitude VARCHAR(17),
city VARCHAR(50) default '',
state VARCHAR(2) default '',
postal_code VARCHAR(10) default '',
country VARCHAR(2) default '',
lata_type VARCHAR(1) default ''
) ENGINE=MyISAM;

CREATE UNIQUE INDEX areaprefix on vicidial_nanpa_prefix_codes (areacode,prefix);

CREATE TABLE vicidial_cpd_log (
cpd_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
channel VARCHAR(100) NOT NULL,
uniqueid VARCHAR(20),
callerid VARCHAR(20),
server_ip VARCHAR(15) NOT NULL,
lead_id INT(9) UNSIGNED,
event_date DATETIME,
result VARCHAR(20),
status ENUM('NEW','PROCESSED') default 'NEW',
cpd_seconds DECIMAL(7,2) default '0',
index(uniqueid),
index(callerid),
index(lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_conf_templates (
template_id VARCHAR(15) NOT NULL,
template_name VARCHAR(50) NOT NULL,
template_contents TEXT,
user_group VARCHAR(20) default '---ALL---',
unique index (template_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_server_carriers (
carrier_id VARCHAR(15) NOT NULL,
carrier_name VARCHAR(50) NOT NULL,
registration_string VARCHAR(255),
template_id VARCHAR(15) NOT NULL,
account_entry TEXT,
protocol ENUM('SIP','PJSIP','PJSIP_WIZ','Zap','IAX2','EXTERNAL') default 'SIP',
globals_string VARCHAR(255),
dialplan_entry TEXT,
server_ip VARCHAR(15) NOT NULL,
active ENUM('Y','N') default 'Y',
carrier_description VARCHAR(255),
user_group VARCHAR(20) default '---ALL---',
unique index(carrier_id),
index (server_ip)
) ENGINE=MyISAM;

CREATE TABLE groups_alias (
group_alias_id VARCHAR(30) NOT NULL UNIQUE PRIMARY KEY,
group_alias_name VARCHAR(50),
caller_id_number VARCHAR(20),
caller_id_name VARCHAR(20),
active ENUM('Y','N') default 'N',
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM;

CREATE TABLE user_call_log (
user_call_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20),
call_date DATETIME,
call_type VARCHAR(20),
server_ip VARCHAR(15) NOT NULL,
phone_number VARCHAR(20),
number_dialed VARCHAR(30),
lead_id INT(9) UNSIGNED,
callerid VARCHAR(20),
group_alias_id VARCHAR(30),
preset_name VARCHAR(40) default '',
campaign_id VARCHAR(20) default '',
customer_hungup ENUM('BEFORE_CALL','DURING_CALL','') default '',
customer_hungup_seconds SMALLINT(5) UNSIGNED default '0',
xfer_hungup VARCHAR(20) default '',
xfer_hungup_datetime DATETIME,
index (user),
index (call_date),
index (group_alias_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_tts_prompts (
tts_id VARCHAR(50) PRIMARY KEY NOT NULL,
tts_name VARCHAR(100),
active ENUM('Y','N'),
tts_text TEXT,
tts_voice VARCHAR(100) default 'Allison-8kHz',
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM;

CREATE TABLE vicidial_call_menu (
menu_id VARCHAR(50) PRIMARY KEY NOT NULL,
menu_name VARCHAR(100),
menu_prompt VARCHAR(255),
menu_timeout SMALLINT(2) UNSIGNED default '10',
menu_timeout_prompt VARCHAR(255) default 'NONE',
menu_invalid_prompt VARCHAR(255) default 'NONE',
menu_repeat TINYINT(1) UNSIGNED default '0',
menu_time_check ENUM('0','1') default '0',
call_time_id VARCHAR(20) default '',
track_in_vdac ENUM('0','1') default '1',
custom_dialplan_entry TEXT,
tracking_group VARCHAR(20) default 'CALLMENU',
dtmf_log ENUM('0','1') default '0',
dtmf_field VARCHAR(50) default 'NONE',
user_group VARCHAR(20) default '---ALL---',
qualify_sql TEXT,
alt_dtmf_log ENUM('0','1') default '0',
question INT(11) DEFAULT NULL,
answer_signal ENUM('Y','N') DEFAULT 'Y'
) ENGINE=MyISAM;

CREATE TABLE vicidial_call_menu_options (
menu_id VARCHAR(50) NOT NULL,
option_value VARCHAR(20) NOT NULL default '',
option_description VARCHAR(255) default '',
option_route VARCHAR(20),
option_route_value VARCHAR(255),
option_route_value_context VARCHAR(1000),
index (menu_id),
unique index menuoption (menu_id, option_value)
) ENGINE=MyISAM;

CREATE TABLE vicidial_user_territories (
user VARCHAR(20) NOT NULL,
territory VARCHAR(100) default '',
level ENUM('TOP_AGENT','STANDARD_AGENT','BOTTOM_AGENT') default 'STANDARD_AGENT',
index (user),
unique index userterritory (user, territory)
) ENGINE=MyISAM;

CREATE TABLE vicidial_territories (
territory_id MEDIUMINT(8) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
territory VARCHAR(100) default '',
territory_description VARCHAR(255) default '',
unique index uniqueterritory (territory)
) ENGINE=MyISAM;

CREATE TABLE vicidial_override_ids (
id_table VARCHAR(50) PRIMARY KEY NOT NULL,
active ENUM('0','1') default '1',
value INT(9) default '0'
) ENGINE=MyISAM;

CREATE TABLE vicidial_carrier_log (
uniqueid VARCHAR(20) PRIMARY KEY NOT NULL,
call_date DATETIME,
server_ip VARCHAR(15) NOT NULL,
lead_id INT(9) UNSIGNED,
hangup_cause TINYINT(1) UNSIGNED default '0',
dialstatus VARCHAR(16),
channel VARCHAR(100),
dial_time SMALLINT(3) UNSIGNED default '0',
answered_time SMALLINT(4) UNSIGNED default '0',
sip_hangup_cause SMALLINT(4) UNSIGNED default '0',
sip_hangup_reason VARCHAR(50) default '',
caller_code VARCHAR(30) default '',
index (call_date),
index (lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_list_update_log (
event_date DATETIME,
lead_id VARCHAR(255),
vendor_id VARCHAR(255),
phone_number VARCHAR(255),
status VARCHAR(6),
old_status VARCHAR(255),
filename VARCHAR(255) default '',
result VARCHAR(20),
result_rows SMALLINT(3) UNSIGNED default '0',
list_id VARCHAR(255),
index (event_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_drop_rate_groups (
group_id VARCHAR(20) PRIMARY KEY NOT NULL,
update_time TIMESTAMP,
calls_today INT(9) UNSIGNED default '0',
answers_today INT(9) UNSIGNED default '0',
drops_today DOUBLE(12,3) default '0',
drops_today_pct VARCHAR(6) default '0',
drops_answers_today_pct VARCHAR(6) default '0',
answering_machines_today INT(9) UNSIGNED default '0',
agenthandled_today INT(9) UNSIGNED default '0'
) ENGINE=MyISAM;

CREATE TABLE vicidial_process_triggers (
trigger_id VARCHAR(20) PRIMARY KEY NOT NULL,
trigger_name VARCHAR(100),
server_ip VARCHAR(15) NOT NULL,
trigger_time DATETIME,
trigger_run ENUM('0','1') default '0',
user VARCHAR(20),
trigger_lines TEXT
) ENGINE=MyISAM;

CREATE TABLE vicidial_process_trigger_log (
trigger_id VARCHAR(20) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
trigger_time DATETIME,
user VARCHAR(20),
trigger_lines TEXT,
trigger_results MEDIUMTEXT,
index (trigger_id),
index (trigger_time)
) ENGINE=MyISAM;

CREATE TABLE vtiger_rank_data (
account VARCHAR(20) PRIMARY KEY NOT NULL,
seqacct VARCHAR(20) UNIQUE NOT NULL,
last_attempt_days SMALLINT(5) UNSIGNED NOT NULL,
orders SMALLINT(5) NOT NULL,
net_sales SMALLINT(5) NOT NULL,
net_sales_ly SMALLINT(5) NOT NULL,
percent_variance VARCHAR(10) NOT NULL,
imu VARCHAR(10) NOT NULL,
aov SMALLINT(5) NOT NULL,
returns SMALLINT(5) NOT NULL,
rank SMALLINT(5) NOT NULL
) ENGINE=MyISAM;

CREATE TABLE vtiger_rank_parameters (
parameter_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
parameter VARCHAR(20) NOT NULL,
lower_range VARCHAR(20) NOT NULL,
upper_range VARCHAR(20) NOT NULL,
points SMALLINT(5) NOT NULL,
index (parameter)
) ENGINE=MyISAM;


CREATE TABLE twoday_call_log (
uniqueid VARCHAR(20) PRIMARY KEY NOT NULL,
channel VARCHAR(100),
channel_group VARCHAR(30),
type VARCHAR(10),
server_ip VARCHAR(15),
extension VARCHAR(100),
number_dialed VARCHAR(15),
caller_code VARCHAR(20),
start_time DATETIME,
start_epoch INT(10),
end_time DATETIME,
end_epoch INT(10),
length_in_sec INT(10),
length_in_min DOUBLE(8,2),
index (caller_code),
index (server_ip),
index (channel)
) ENGINE=MyISAM;

CREATE TABLE twoday_vicidial_log (
uniqueid VARCHAR(20) PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED,
campaign_id VARCHAR(8),
call_date DATETIME,
start_epoch INT(10) UNSIGNED,
end_epoch INT(10) UNSIGNED,
length_in_sec INT(10),
status VARCHAR(6),
phone_code VARCHAR(10),
phone_number VARCHAR(18),
user VARCHAR(20),
comments VARCHAR(255),
processed ENUM('Y','N'),
user_group VARCHAR(20),
term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE','SYSTEM') default 'NONE',
alt_dial VARCHAR(6) default 'NONE',
index (lead_id),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE twoday_vicidial_closer_log (
closecallid INT(9) UNSIGNED PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED,
campaign_id VARCHAR(20),
call_date DATETIME,
start_epoch INT(10) UNSIGNED,
end_epoch INT(10) UNSIGNED,
length_in_sec INT(10),
status VARCHAR(6),
phone_code VARCHAR(10),
phone_number VARCHAR(18),
user VARCHAR(20),
comments VARCHAR(255),
processed ENUM('Y','N'),
queue_seconds DECIMAL(7,2) default '0',
user_group VARCHAR(20),
xfercallid INT(9) UNSIGNED,
term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','HOLDRECALLXFER','HOLDTIME','NOAGENT','NONE','MAXCALLS','ACFILTER') default 'NONE',
uniqueid VARCHAR(20) NOT NULL default '',
agent_only VARCHAR(20) default '',
queue_position SMALLINT(4) UNSIGNED default '1',
called_count SMALLINT(5) UNSIGNED default '0',
index (lead_id),
index (call_date),
index (campaign_id),
index (uniqueid)
) ENGINE=MyISAM;

CREATE TABLE twoday_vicidial_xfer_log (
xfercallid INT(9) UNSIGNED PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED,
campaign_id VARCHAR(20),
call_date DATETIME,
phone_code VARCHAR(10),
phone_number VARCHAR(18),
user VARCHAR(20),
closer VARCHAR(20),
index (lead_id),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE twoday_recording_log (
recording_id INT(10) UNSIGNED PRIMARY KEY NOT NULL,
channel VARCHAR(100),
server_ip VARCHAR(15),
extension VARCHAR(100),
start_time DATETIME,
start_epoch INT(10) UNSIGNED,
end_time DATETIME,
end_epoch INT(10) UNSIGNED,
length_in_sec MEDIUMINT(8) UNSIGNED,
length_in_min DOUBLE(8,2),
filename VARCHAR(50),
location VARCHAR(255),
lead_id INT(9) UNSIGNED,
user VARCHAR(20),
vicidial_id VARCHAR(20),
index(filename),
index(lead_id),
index(user),
index(vicidial_id)
) ENGINE=MyISAM;

CREATE TABLE twoday_vicidial_agent_log (
agent_log_id INT(9) UNSIGNED PRIMARY KEY NOT NULL,
user VARCHAR(20),
server_ip VARCHAR(15) NOT NULL,
event_time DATETIME,
lead_id INT(9) UNSIGNED,
campaign_id VARCHAR(8),
pause_epoch INT(10) UNSIGNED,
pause_sec SMALLINT(5) UNSIGNED default '0',
wait_epoch INT(10) UNSIGNED,
wait_sec SMALLINT(5) UNSIGNED default '0',
talk_epoch INT(10) UNSIGNED,
talk_sec SMALLINT(5) UNSIGNED default '0',
dispo_epoch INT(10) UNSIGNED,
dispo_sec SMALLINT(5) UNSIGNED default '0',
status VARCHAR(6),
user_group VARCHAR(20),
comments VARCHAR(20),
sub_status VARCHAR(6),
dead_epoch INT(10) UNSIGNED,
dead_sec SMALLINT(5) UNSIGNED default '0',
processed ENUM('Y','N') default 'N',
uniqueid VARCHAR(20) default '',
index (lead_id),
index (user),
index (event_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_music_on_hold (
moh_id VARCHAR(100) PRIMARY KEY NOT NULL,
moh_name VARCHAR(255),
active ENUM('Y','N') default 'N',
random ENUM('Y','N') default 'N',
remove ENUM('Y','N') default 'N',
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM;

CREATE TABLE vicidial_music_on_hold_files (
filename VARCHAR(100) NOT NULL,
moh_id VARCHAR(100) NOT NULL,
rank SMALLINT(5),
unique index mohfile (filename, moh_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_voicemail (
voicemail_id VARCHAR(10) NOT NULL UNIQUE PRIMARY KEY,
active ENUM('Y','N') default 'Y',
pass VARCHAR(10) NOT NULL,
fullname VARCHAR(100) NOT NULL,
messages INT(4) default '0',
old_messages INT(4) default '0',
email VARCHAR(100),
delete_vm_after_email ENUM('N','Y') default 'N',
voicemail_timezone VARCHAR(30) default 'eastern',
voicemail_options VARCHAR(255) default '',
user_group VARCHAR(20) default '---ALL---',
voicemail_greeting VARCHAR(100) default '',
on_login_report enum('Y','N') NOT NULL default 'N'
) ENGINE=MyISAM;

CREATE TABLE vicidial_user_territory_log (
user VARCHAR(20),
campaign_id VARCHAR(20),
event_date DATETIME,
agent_territories TEXT,
index (user),
index (event_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_grab_call_log (
auto_call_id INT(9) UNSIGNED NOT NULL,
user VARCHAR(20),
event_date DATETIME,
call_time DATETIME,
campaign_id VARCHAR(20),
uniqueid VARCHAR(20),
phone_number VARCHAR(20),
lead_id INT(9) UNSIGNED,
queue_priority TINYINT(2) default '0',
call_type ENUM('IN','OUT','OUTBALANCE') default 'OUT',
index (auto_call_id),
index (event_date),
index (user),
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vtiger_vicidial_roles (
user_level TINYINT(2),
vtiger_role VARCHAR(5)
) ENGINE=MyISAM;

CREATE TABLE vicidial_call_notes (
notesid INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
vicidial_id VARCHAR(20),
call_date DATETIME,
order_id VARCHAR(20),
appointment_date DATE,
appointment_time TIME,
call_notes TEXT
) ENGINE=MyISAM;

ALTER TABLE vicidial_call_notes AUTO_INCREMENT = 100;
CREATE INDEX lead_id on vicidial_call_notes (lead_id);

CREATE TABLE vicidial_custom_cid (
cid VARCHAR(18) NOT NULL,
state VARCHAR(20),
areacode VARCHAR(6),
country_code SMALLINT(5) UNSIGNED,
campaign_id VARCHAR(8) default '--ALL--',
index (state),
index (areacode)
) ENGINE=MyISAM;

CREATE TABLE callcard_accounts (
card_id VARCHAR(20) PRIMARY KEY NOT NULL,
pin VARCHAR(10) NOT NULL,
status ENUM('GENERATE','PRINT','SHIP','HOLD','ACTIVE','USED','EMPTY','CANCEL','VOID') default 'GENERATE',
balance_minutes SMALLINT(5) default '3',
inbound_group_id VARCHAR(20) default '',
index (pin)
) ENGINE=MyISAM;

CREATE TABLE callcard_accounts_details (
card_id VARCHAR(20) PRIMARY KEY NOT NULL,
run VARCHAR(4) default '',
batch VARCHAR(5) default '',
pack VARCHAR(5) default '',
sequence VARCHAR(5) default '',
status ENUM('GENERATE','PRINT','SHIP','HOLD','ACTIVE','USED','EMPTY','CANCEL','VOID') default 'GENERATE',
balance_minutes SMALLINT(5) default '3',
initial_value VARCHAR(6) default '0.00',
initial_minutes SMALLINT(5) default '3',
note_purchase_order VARCHAR(20) default '',
note_printer VARCHAR(20) default '',
note_did VARCHAR(18) default '',
inbound_group_id VARCHAR(20) default '',
note_language VARCHAR(10) default 'English',
note_name VARCHAR(20) default '',
note_comments VARCHAR(255) default '',
create_user VARCHAR(20) default '',
activate_user VARCHAR(20) default '',
used_user VARCHAR(20) default '',
void_user VARCHAR(20) default '',
create_time DATETIME,
activate_time DATETIME,
used_time DATETIME,
void_time DATETIME
) ENGINE=MyISAM;

CREATE TABLE callcard_log (
uniqueid VARCHAR(20) PRIMARY KEY NOT NULL,
card_id VARCHAR(20),
balance_minutes_start SMALLINT(5) default '3',
call_time DATETIME,
agent_time DATETIME,
dispo_time DATETIME,
agent VARCHAR(20) default '',
agent_dispo VARCHAR(6) default '',
agent_talk_sec MEDIUMINT(8) default '0',
agent_talk_min MEDIUMINT(8) default '0',
phone_number VARCHAR(18),
inbound_did VARCHAR(18),
index (card_id),
index (call_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_extension_groups (
extension_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
extension_group_id VARCHAR(20) NOT NULL,
extension VARCHAR(100) default '8300',
rank MEDIUMINT(7) default '0',
campaign_groups TEXT,
call_count_today MEDIUMINT(7) default '0',
last_call_time DATETIME,
last_callerid VARCHAR(20) default '',
index (extension_group_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_remote_agent_log (
uniqueid VARCHAR(20) default '',
callerid VARCHAR(20) default '',
ra_user VARCHAR(20),
user VARCHAR(20),
call_time DATETIME,
extension VARCHAR(100) default '',
lead_id INT(9) UNSIGNED default '0',
phone_number VARCHAR(18) default '',
campaign_id VARCHAR(20) default '',
processed ENUM('Y','N') default 'N',
comment VARCHAR(255) default '',
index (call_time),
index (ra_user),
index (extension),
index (phone_number)
) ENGINE=MyISAM;

CREATE TABLE vicidial_log_extended (
uniqueid VARCHAR(50) PRIMARY KEY NOT NULL,
server_ip VARCHAR(15),
call_date DATETIME,
lead_id INT(9) UNSIGNED,
caller_code VARCHAR(30) NOT NULL,
custom_call_id VARCHAR(100),
start_url_processed ENUM('N','Y','U') default 'N',
dispo_url_processed ENUM('N','Y','U','XY','XU') default 'N',
multi_alt_processed ENUM('N','Y','U') default 'N',
noanswer_processed ENUM('N','Y','U') default 'N'
) ENGINE=MyISAM;

CREATE INDEX call_date on vicidial_log_extended (call_date);
CREATE INDEX vle_lead_id on vicidial_log_extended(lead_id);

CREATE TABLE vicidial_lists_fields (
field_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
list_id BIGINT(14) UNSIGNED NOT NULL DEFAULT '0',
field_label VARCHAR(50),
field_name VARCHAR(5000),
field_description VARCHAR(100),
field_rank SMALLINT(5),
field_help VARCHAR(1000),
field_type ENUM('TEXT','AREA','SELECT','MULTI','RADIO','CHECKBOX','DATE','TIME','DISPLAY','SCRIPT','HIDDEN','READONLY','HIDEBLOB','SWITCH','SOURCESELECT','BUTTON') default 'TEXT',
field_options VARCHAR(5000),
field_size SMALLINT(5),
field_max SMALLINT(5),
field_default VARCHAR(255),
field_cost SMALLINT(5),
field_required ENUM('Y','N','INBOUND_ONLY') default 'N',
name_position ENUM('LEFT','TOP') default 'LEFT',
multi_position ENUM('HORIZONTAL','VERTICAL') default 'HORIZONTAL',
field_order SMALLINT(5) default '1',
field_encrypt ENUM('Y','N') default 'N',
field_show_hide ENUM('DISABLED','X_OUT_ALL','LAST_1','LAST_2','LAST_3','LAST_4','FIRST_1_LAST_4') default 'DISABLED',
field_duplicate ENUM('Y','N') default 'N'
) ENGINE=MyISAM;

CREATE UNIQUE INDEX listfield on vicidial_lists_fields (list_id, field_label);

CREATE TABLE vicidial_filter_phone_groups (
filter_phone_group_id VARCHAR(20) NOT NULL,
filter_phone_group_name VARCHAR(40) NOT NULL,
filter_phone_group_description VARCHAR(100),
user_group VARCHAR(20) default '---ALL---',
index (filter_phone_group_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_filter_phone_numbers (
phone_number VARCHAR(18) NOT NULL,
filter_phone_group_id VARCHAR(20) NOT NULL,
index (phone_number),
unique index phonefilter (phone_number, filter_phone_group_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_xfer_presets (
campaign_id VARCHAR(20) NOT NULL,
preset_name VARCHAR(40) NOT NULL,
preset_number VARCHAR(50) NOT NULL,
preset_dtmf VARCHAR(50) default '',
preset_hide_number ENUM('Y','N') default 'N',
index (preset_name)
) ENGINE=MyISAM;

CREATE TABLE vicidial_xfer_stats (
campaign_id VARCHAR(20) NOT NULL,
preset_name VARCHAR(40) NOT NULL,
xfer_count SMALLINT(5) UNSIGNED default '0',
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_manual_dial_queue (
mdq_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20),
phone_number VARCHAR(100) default '',
entry_time DATETIME,
status ENUM('READY','QUEUE') default 'READY',
external_dial VARCHAR(100) default '',
index (user)
) ENGINE=MyISAM;

CREATE TABLE vicidial_lead_search_log (
search_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20) NOT NULL,
event_date DATETIME NOT NULL,
source VARCHAR(10) default '',
search_query TEXT,
results INT(9) UNSIGNED default '0',
seconds MEDIUMINT(7) UNSIGNED default '0',
index (user),
index (event_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_stats_debug (
campaign_id VARCHAR(20) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
entry_time DATETIME,
update_time TIMESTAMP,
debug_output TEXT,
adapt_output TEXT,
index (campaign_id),
unique index campserver (campaign_id, server_ip)
) ENGINE=MyISAM;

CREATE TABLE vicidial_outbound_ivr_log (
uniqueid VARCHAR(50) NOT NULL,
caller_code VARCHAR(30) NOT NULL,
event_date DATETIME,
campaign_id VARCHAR(20) default '',
lead_id INT(9) UNSIGNED,
menu_id VARCHAR(50) default '',
menu_action VARCHAR(50) default '',
index (event_date),
index (lead_id),
index (campaign_id),
unique index campserver (event_date, lead_id, menu_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_screen_labels (
label_id VARCHAR(20) PRIMARY KEY NOT NULL,
label_name VARCHAR(100),
active ENUM('Y','N') default 'N',
label_hide_field_logs VARCHAR(6) default 'Y',
label_title VARCHAR(60) default '',
label_first_name VARCHAR(60) default '',
label_middle_initial VARCHAR(60) default '',
label_last_name VARCHAR(60) default '',
label_address1 VARCHAR(60) default '',
label_address2 VARCHAR(60) default '',
label_address3 VARCHAR(60) default '',
label_city VARCHAR(60) default '',
label_state VARCHAR(60) default '',
label_province VARCHAR(60) default '',
label_postal_code VARCHAR(60) default '',
label_vendor_lead_code VARCHAR(60) default '',
label_gender VARCHAR(60) default '',
label_phone_number VARCHAR(60) default '',
label_phone_code VARCHAR(60) default '',
label_alt_phone VARCHAR(60) default '',
label_security_phrase VARCHAR(60) default '',
label_email VARCHAR(60) default '',
label_comments VARCHAR(60) default '',
user_group VARCHAR(20) default '---ALL---',
label_lead_id VARCHAR(60) default '',
label_list_id VARCHAR(60) default '',
label_entry_date VARCHAR(60) default '',
label_gmt_offset_now VARCHAR(60) default '',
label_source_id VARCHAR(60) default '',
label_called_since_last_reset VARCHAR(60) default '',
label_status VARCHAR(60) default '',
label_user VARCHAR(60) default '',
label_date_of_birth VARCHAR(60) default '',
label_country_code VARCHAR(60) default '',
label_last_local_call_time VARCHAR(60) default '',
label_called_count VARCHAR(60) default '',
label_rank VARCHAR(60) default '',
label_owner VARCHAR(60) default '',
label_entry_list_id VARCHAR(60) default ''
) ENGINE=MyISAM;

CREATE TABLE vicidial_agent_skip_log (
user_skip_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
user VARCHAR(20),
event_date DATETIME,
lead_id INT(9) UNSIGNED,
campaign_id VARCHAR(20) default '',
previous_status VARCHAR(6) default '',
previous_called_count SMALLINT(5) UNSIGNED default '0',
index (user),
index (event_date),
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_url_log (
url_log_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
uniqueid VARCHAR(50) NOT NULL,
url_date DATETIME,
url_type VARCHAR(10) default '',
response_sec SMALLINT(5) UNSIGNED default '0',
url TEXT,
url_response TEXT,
index (uniqueid)
) ENGINE=MyISAM;

CREATE TABLE vicidial_log_noanswer (
uniqueid VARCHAR(20) PRIMARY KEY NOT NULL,
lead_id INT(9) UNSIGNED NOT NULL,
list_id BIGINT(14) UNSIGNED,
campaign_id VARCHAR(8),
call_date DATETIME,
start_epoch INT(10) UNSIGNED,
end_epoch INT(10) UNSIGNED,
length_in_sec INT(10),
status VARCHAR(6),
phone_code VARCHAR(10),
phone_number VARCHAR(18),
user VARCHAR(20),
comments VARCHAR(255),
processed ENUM('Y','N'),
user_group VARCHAR(20),
term_reason  ENUM('CALLER','AGENT','QUEUETIMEOUT','ABANDON','AFTERHOURS','NONE','SYSTEM') default 'NONE',
alt_dial VARCHAR(6) default 'NONE',
caller_code VARCHAR(30) NOT NULL,
index (lead_id),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_did_agent_log (
uniqueid VARCHAR(20) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
caller_id_number VARCHAR(18),
caller_id_name VARCHAR(20),
extension VARCHAR(100),
call_date DATETIME,
did_id VARCHAR(9) default '',
did_description VARCHAR(50) default '',
did_route VARCHAR(20) default '',
group_id VARCHAR(20) default '',
user VARCHAR(20) default 'VDCL',
index (uniqueid),
index (caller_id_number),
index (extension),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE vicidial_campaign_cid_areacodes (
campaign_id VARCHAR(20) NOT NULL,
areacode VARCHAR(5) NOT NULL,
outbound_cid VARCHAR(20),
active ENUM('Y','N','') default '',
cid_description VARCHAR(50),
call_count_today MEDIUMINT(7) default '0',
index (campaign_id),
index (areacode)
) ENGINE=MyISAM;

CREATE UNIQUE INDEX campareacode on vicidial_campaign_cid_areacodes (campaign_id, areacode, outbound_cid);

CREATE TABLE vicidial_did_ra_extensions (
did_id INT(9) UNSIGNED NOT NULL,
user_start VARCHAR(20),
extension VARCHAR(50) default '',
description VARCHAR(50),
active ENUM('Y','N','') default '',
call_count_today MEDIUMINT(7) default '0',
index (did_id),
index (user_start)
) ENGINE=MyISAM;

CREATE UNIQUE INDEX didraexten on vicidial_did_ra_extensions (did_id, user_start, extension);

CREATE TABLE contact_information (
contact_id  INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
first_name VARCHAR(50) default '',
last_name VARCHAR(50) default '',
office_num VARCHAR(20) default '',
cell_num VARCHAR(20) default '',
other_num1 VARCHAR(20) default '',
other_num2 VARCHAR(20) default '',
bu_name VARCHAR(100) default '',
department VARCHAR(100) default '',
group_name VARCHAR(100) default '',
job_title VARCHAR(100) default '',
location VARCHAR(100) default ''
) ENGINE=MyISAM;

CREATE INDEX ci_first_name on contact_information (first_name);
CREATE INDEX ci_last_name on contact_information (last_name);

CREATE TABLE dialable_inventory_snapshots (
snapshot_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
snapshot_time DATETIME default NULL,
list_id BIGINT(14) UNSIGNED default NULL,
list_name VARCHAR(30) default NULL,
list_description VARCHAR(255),
campaign_id VARCHAR(8) default NULL,
list_lastcalldate VARCHAR(20) default NULL,
list_start_inv mediumint(8) unsigned default NULL,
dialable_count mediumint(8) unsigned default NULL,
dialable_count_nofilter mediumint(8) unsigned default NULL,
dialable_count_oneoff mediumint(8) unsigned default NULL,
dialable_count_inactive mediumint(8) unsigned default NULL,
average_call_count decimal(3,1) default NULL,
penetration decimal(5,2) default NULL,
shift_data TEXT,
time_setting ENUM('LOCAL','SERVER') default NULL,
UNIQUE KEY snapshot_date_list_key
(snapshot_time,list_id,time_setting),
KEY snapshot_date_key (snapshot_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_daily_max_stats (
stats_date DATE NOT NULL,
stats_flag ENUM('OPEN','CLOSED','CLOSING') default 'CLOSED',
stats_type ENUM('TOTAL','INGROUP','CAMPAIGN','') default '',
campaign_id VARCHAR(20) default '',
update_time TIMESTAMP,
closed_time DATETIME,
max_channels MEDIUMINT(8) UNSIGNED default '0',
max_calls MEDIUMINT(8) UNSIGNED default '0',
max_inbound MEDIUMINT(8) UNSIGNED default '0',
max_outbound MEDIUMINT(8) UNSIGNED default '0',
max_agents MEDIUMINT(8) UNSIGNED default '0',
max_remote_agents MEDIUMINT(8) UNSIGNED default '0',
total_calls INT(9) UNSIGNED default '0',
index (stats_date),
index (stats_flag),
index (campaign_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_daily_ra_stats (
stats_date DATE NOT NULL,
stats_flag ENUM('OPEN','CLOSED','CLOSING') default 'CLOSED',
user VARCHAR(20) default '',
update_time TIMESTAMP,
closed_time DATETIME,
max_calls MEDIUMINT(8) UNSIGNED default '0',
total_calls INT(9) UNSIGNED default '0',
index (stats_date),
index (stats_flag),
index (user)
) ENGINE=MyISAM;

CREATE TABLE vicidial_custom_leadloader_templates (
template_id VARCHAR(20) PRIMARY KEY NOT NULL,
template_name VARCHAR(30) DEFAULT NULL,
template_description VARCHAR(255) DEFAULT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT NULL,
standard_variables TEXT,
custom_table VARCHAR(20) DEFAULT NULL,
custom_variables TEXT,
template_statuses VARCHAR(255)
) ENGINE=MyISAM;

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
) ENGINE=MyISAM;

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
sip_hangup_cause SMALLINT(4) UNSIGNED default '0',
sip_hangup_reason VARCHAR(50) default '',
uniqueid VARCHAR(20) default '',
index (caller_code),
index (lead_id),
index (call_date)
) ENGINE=MyISAM;

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
qc_log_id INT(10) UNSIGNED,
PRIMARY KEY (qc_agent_log_id),
KEY view_epoch (view_epoch)
) ENGINE=MyISAM;

CREATE TABLE vicidial_comments (
comment_id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
lead_id INT(11) NOT NULL,
user_id VARCHAR(20) NOT NULL,
timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
list_id BIGINT(14) UNSIGNED NOT NULL,
campaign_id VARCHAR(8) NOT NULL,
comment VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
hidden TINYINT(1) DEFAULT NULL,
hidden_user_id INT(11) DEFAULT NULL,
hidden_timestamp DATETIME DEFAULT NULL,
unhidden_user_id INT(11) DEFAULT NULL,
unhidden_timestamp DATETIME DEFAULT NULL,
PRIMARY KEY (comment_id),
index (lead_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_configuration (
id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
name VARCHAR(36) NOT NULL ,
value VARCHAR(36) NOT NULL ,
UNIQUE (name)
) ENGINE=MyISAM;

CREATE TABLE vicidial_lists_custom (
list_id BIGINT(14) unsigned NOT NULL,
audit_comments TINYINT(1) DEFAULT NULL COMMENT 'visible',
audit_comments_enabled TINYINT(1) DEFAULT NULL COMMENT 'invisible',
PRIMARY KEY (list_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_call_time_holidays (
holiday_id VARCHAR(30) PRIMARY KEY NOT NULL,
holiday_name VARCHAR(100) NOT NULL,
holiday_comments VARCHAR(255) default '',
holiday_date DATE,
holiday_status ENUM('ACTIVE','INACTIVE','EXPIRED') default 'INACTIVE',
ct_default_start SMALLINT(4) unsigned NOT NULL default '900',
ct_default_stop SMALLINT(4) unsigned NOT NULL default '2100',
default_afterhours_filename_override VARCHAR(255) default '',
user_group VARCHAR(20) default '---ALL---',
holiday_method VARCHAR(40) default 'REPLACE'
) ENGINE=MyISAM;

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
content_transfer_encoding TEXT,
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
KEY vicidial_email_lead_id_key (lead_id),
KEY vicidial_email_group_key (group_id),
KEY vicidial_email_xfer_key (xfercallid)
) ENGINE=MyISAM;

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
pop3_auth_mode ENUM('BEST','PASS','APOP','CRAM-MD5') default 'BEST',
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
) ENGINE=MyISAM;

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
) ENGINE=MyISAM;

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
) ENGINE=MyISAM;

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
webserver SMALLINT(5) UNSIGNED default '0',
index (user),
index (report_name)
) ENGINE=MyISAM;

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

CREATE TABLE vicidial_webservers (
webserver_id SMALLINT(5) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
webserver VARCHAR(125) default '',
hostname VARCHAR(125) default '',
unique index vdweb (webserver, hostname)
) ENGINE=MyISAM;

CREATE TABLE vicidial_urls (
url_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
url VARCHAR(250) default '',
unique index (url)
) ENGINE=MyISAM;

CREATE TABLE vicidial_avatars (
avatar_id VARCHAR(100) PRIMARY KEY NOT NULL,
avatar_name VARCHAR(100),
avatar_notes TEXT,
avatar_api_user VARCHAR(20) default '',
avatar_api_pass VARCHAR(100) default '',
active ENUM('Y','N') default 'Y',
audio_functions VARCHAR(100) default 'PLAY-STOP-RESTART',
audio_display VARCHAR(100) default 'FILE-NAME',
user_group VARCHAR(20) default '---ALL---',
soundboard_layout VARCHAR(40) default 'default',
columns_limit SMALLINT(5) default '5'
) ENGINE=MyISAM;

CREATE TABLE vicidial_avatar_audio (
avatar_id VARCHAR(100) NOT NULL,
audio_filename VARCHAR(255) NOT NULL,
audio_name TEXT,
rank SMALLINT(5) default '0',
h_ord SMALLINT(5) default '1',
level SMALLINT(5) default '1',
parent_audio_filename VARCHAR(255) default '',
parent_rank VARCHAR(2) default '',
button_type VARCHAR(40) default 'button',
font_size VARCHAR(3) default '2',
index (avatar_id)
) ENGINE=MyISAM;

CREATE TABLE audio_store_details (
audio_filename VARCHAR(250) NOT NULL,
audio_format VARCHAR(10) default 'unknown',
audio_filesize BIGINT(20) UNSIGNED default '0',
audio_epoch BIGINT(20) UNSIGNED default '0',
audio_length INT(10) UNSIGNED default '0',
wav_format_details VARCHAR(255) default '',
wav_asterisk_valid ENUM('','GOOD','BAD','NA') default '',
unique index (audio_filename)
) ENGINE=MyISAM;

CREATE TABLE www_phrases (
phrase_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
phrase_text VARCHAR(10000) default '',
php_filename VARCHAR(255) NOT NULL,
php_directory VARCHAR(255) default '',
source VARCHAR(20) default '',
insert_date DATETIME,
index (phrase_text)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE vicidial_languages (
language_id VARCHAR(100) NOT NULL,
language_code VARCHAR(20) default '',
language_description VARCHAR(255) default '',
user_group VARCHAR(20) default '---ALL---',
modify_date TIMESTAMP,
active ENUM('Y','N') default 'N',
unique index (language_id)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE vicidial_language_phrases (
phrase_id INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
language_id VARCHAR(100) NOT NULL,
english_text VARCHAR(10000) default '',
translated_text TEXT,
source VARCHAR(20) default '',
modify_date TIMESTAMP,
index (language_id),
index (english_text)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE vicidial_chat_archive (
chat_id INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
chat_start_time DATETIME DEFAULT NULL,
status VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
chat_creator VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
group_id VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
lead_id INT(9) UNSIGNED DEFAULT NULL,
transferring_agent VARCHAR(20),
user_direct VARCHAR(20),
user_direct_group_id VARCHAR(20),
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
KEY vicidial_chat_log_user_key (poster),
KEY vicidial_chat_log_chat_id (chat_id)
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
transferring_agent VARCHAR(20),
user_direct VARCHAR(20),
user_direct_group_id VARCHAR(20),
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
message_id VARCHAR(20),
message_date DATETIME DEFAULT NULL,
message_viewed_date DATETIME DEFAULT NULL,
message_posted_by VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
audio_alerted ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
PRIMARY KEY (manager_chat_message_id),
KEY manager_chat_id_key (manager_chat_id),
KEY manager_chat_subid_key (manager_chat_subid),
KEY manager_chat_manager_key (manager),
KEY manager_chat_user_key (user)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_manager_chat_log_archive (
manager_chat_message_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
manager_chat_id INT(10) UNSIGNED DEFAULT NULL,
manager_chat_subid TINYINT(3) UNSIGNED DEFAULT NULL,
manager VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
user VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
message MEDIUMTEXT COLLATE utf8_unicode_ci,
message_id VARCHAR(20),
message_date DATETIME DEFAULT NULL,
message_viewed_date DATETIME DEFAULT NULL,
message_posted_by VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
audio_alerted ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
PRIMARY KEY (manager_chat_message_id),
KEY manager_chat_id_archive_key (manager_chat_id),
KEY manager_chat_subid_archive_key (manager_chat_subid)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_manager_chats (
manager_chat_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
internal_chat_type ENUM('AGENT', 'MANAGER') default 'MANAGER',
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
internal_chat_type ENUM('AGENT', 'MANAGER') default 'MANAGER',
chat_start_date DATETIME DEFAULT NULL,
manager VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
selected_agents MEDIUMTEXT COLLATE utf8_unicode_ci,
selected_user_groups MEDIUMTEXT COLLATE utf8_unicode_ci,
selected_campaigns MEDIUMTEXT COLLATE utf8_unicode_ci,
allow_replies ENUM('Y','N') COLLATE utf8_unicode_ci DEFAULT 'N',
PRIMARY KEY (manager_chat_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
url_lists VARCHAR(1000) default '',
url_call_length SMALLINT(5) default '0',
PRIMARY KEY (url_id),
KEY vicidial_url_multi_campaign_id_key (campaign_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

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

CREATE TABLE vicidial_settings_containers (
container_id VARCHAR(40) PRIMARY KEY NOT NULL,
container_notes VARCHAR(255) default '',
container_type VARCHAR(40) default 'OTHER',
user_group VARCHAR(20) default '---ALL---',
container_entry MEDIUMTEXT
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_dnc_log (
phone_number VARCHAR(18) NOT NULL,
campaign_id VARCHAR(8) NOT NULL,
action ENUM('add','delete') default 'add',
action_date DATETIME NOT NULL,
user VARCHAR(20) default '',
index (phone_number)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_status_groups (
status_group_id VARCHAR(20) PRIMARY KEY NOT NULL,
status_group_notes VARCHAR(255) default '',
user_group VARCHAR(20) default '---ALL---'
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_custom_reports (
custom_report_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
report_name VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
date_added DATETIME DEFAULT NULL,
user VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
domain VARCHAR(70) COLLATE utf8_unicode_ci DEFAULT NULL,
path_name TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
custom_variables TEXT COLLATE utf8_unicode_ci DEFAULT NULL,
date_modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
user_modify VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL,
PRIMARY KEY (custom_report_id),
UNIQUE KEY custom_report_name_key (report_name)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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

CREATE TABLE vicidial_html_cache_stats (
stats_type VARCHAR(20) NOT NULL,
stats_id VARCHAR(20) NOT NULL,
stats_date DATETIME NOT NULL,
stats_count INT(9) UNSIGNED default '0',
stats_html MEDIUMTEXT,
UNIQUE KEY vicidial_html_cache_stats_key (stats_type,stats_id)
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

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

CREATE TABLE vicidial_country_iso_tld (
country_name VARCHAR(200) default '',
iso2 VARCHAR(2) default '',
iso3 VARCHAR(3) default '',
num3 VARCHAR(4) default '',
tld VARCHAR(20) default '',
index(iso3)
)ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE vicidial_asterisk_output (
server_ip VARCHAR(15) NOT NULL,
sip_peers MEDIUMTEXT,
iax_peers MEDIUMTEXT,
asterisk MEDIUMTEXT,
update_date DATETIME,
unique index(server_ip)
)ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=MyISAM AUTO_INCREMENT=1599 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
web_logo VARCHAR(100) default 'default_new',
button_color VARCHAR(6) default 'EFEFEF'
) ENGINE=MyISAM;

CREATE TABLE vicidial_user_list_new_lead (
user VARCHAR(20) NOT NULL,
list_id BIGINT(14) UNSIGNED default '999',
user_override SMALLINT(5) default '-1',
new_count MEDIUMINT(8) UNSIGNED default '0',
unique index userlistnew (user, list_id)
) ENGINE=MyISAM;

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

CREATE TABLE parked_channels_recent (
channel VARCHAR(100) NOT NULL,
server_ip VARCHAR(15) NOT NULL,
channel_group VARCHAR(30),
park_end_time DATETIME,
index (channel_group),
index (park_end_time)
) ENGINE=MyISAM;

CREATE TABLE vicidial_api_urls (
api_id INT(9) UNSIGNED PRIMARY KEY NOT NULL,
api_date DATETIME,
remote_ip VARCHAR(50),
url MEDIUMTEXT
) ENGINE=MyISAM;

CREATE TABLE vicidial_areacode_filters (
group_id VARCHAR(20) NOT NULL,
areacode VARCHAR(6) NOT NULL,
index(group_id)
) ENGINE=MyISAM;

CREATE TABLE vicidial_automated_reports (
report_id VARCHAR(30) UNIQUE NOT NULL,
report_name VARCHAR(100),
report_last_run DATETIME,
report_last_length SMALLINT(5) default '0',
report_server VARCHAR(30) default 'active_voicemail_server',
report_times VARCHAR(255) default '',
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
filename_override VARCHAR(255) default '',
index (report_times),
index (run_now_trigger)
) ENGINE=MyISAM;

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
dl_minutes MEDIUMINT(6) UNSIGNED default '0',
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

CREATE TABLE vicidial_dnccom_scrub_log (
phone_number VARCHAR(18),
scrub_date DATETIME NOT NULL,
flag_invalid ENUM('','0','1') default '',
flag_dnc ENUM('','0','1') default '',
flag_projdnc ENUM('','0','1') default '',
flag_litigator ENUM('','0','1') default '',
full_response VARCHAR(255) default '',
index(phone_number),
index(scrub_date)
) ENGINE=MyISAM;

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
index (icbq_status),
index (group_id),
index (icbq_date),
index (call_date)
) ENGINE=MyISAM;

CREATE TABLE recording_log_deletion_queue (
recording_id INT(9) UNSIGNED PRIMARY KEY, 
lead_id int(10) UNSIGNED, 
filename VARCHAR(100), 
location VARCHAR(255), 
date_queued DATETIME, 
date_deleted DATETIME,
index (date_deleted)
) ENGINE=MyISAM;

CREATE TABLE vicidial_cid_groups (
cid_group_id VARCHAR(20) PRIMARY KEY NOT NULL,
cid_group_notes VARCHAR(255) default '',
cid_group_type ENUM('AREACODE','STATE','NONE') default 'AREACODE',
user_group VARCHAR(20) default '---ALL---',
cid_auto_rotate_minutes MEDIUMINT(7) UNSIGNED default '0',
cid_auto_rotate_minimum MEDIUMINT(7) UNSIGNED default '0',
cid_auto_rotate_calls MEDIUMINT(7) UNSIGNED default '0',
cid_last_auto_rotate DATETIME,
cid_auto_rotate_cid VARCHAR(20) default ''
) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

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

CREATE TABLE help_documentation (
help_id varchar(100) PRIMARY KEY COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
help_title text COLLATE utf8_unicode_ci,
help_text text COLLATE utf8_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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

CREATE TABLE vicidial_bench_agent_log (
lead_id INT(9) UNSIGNED,
bench_date DATETIME,
absent_agent VARCHAR(20),
bench_agent VARCHAR(20),
user VARCHAR(20),
index (bench_date),
index (lead_id)
) ENGINE=MyISAM;

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

CREATE TABLE vicidial_vmm_counts (
call_date DATE,
lead_id INT(9) UNSIGNED,
vmm_count SMALLINT(5) UNSIGNED default '0',
vmm_played SMALLINT(5) UNSIGNED default '0',
index (call_date),
index (lead_id)
) ENGINE=MyISAM;

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

CREATE TABLE vicidial_lead_messages (
lead_id INT(9) UNSIGNED NOT NULL,
call_date DATETIME,
user VARCHAR(20) DEFAULT NULL,
played TINYINT(3) default '0',
message_entry MEDIUMTEXT,
index (lead_id),
index (call_date)
) ENGINE=MyISAM;

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

CREATE TABLE quality_control_checkpoint_log (
qc_checkpoint_log_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
qc_log_id INT(10) UNSIGNED DEFAULT NULL,
campaign_id VARCHAR(8) DEFAULT NULL,
group_id VARCHAR(20) DEFAULT NULL,
list_id BIGINT(14) UNSIGNED DEFAULT NULL,
qc_scorecard_id VARCHAR(20) DEFAULT NULL,
checkpoint_row_id INT(10) UNSIGNED DEFAULT NULL,
checkpoint_text TEXT,
checkpoint_text_presets TEXT,
checkpoint_rank TINYINT(3) UNSIGNED DEFAULT NULL,
checkpoint_points TINYINT(3) UNSIGNED DEFAULT NULL,
instant_fail ENUM('Y','N') DEFAULT 'N',
instant_fail_value ENUM('Y', 'N') default 'N',
checkpoint_points_earned TINYINT(5) UNSIGNED DEFAULT NULL,
qc_agent VARCHAR(20) DEFAULT NULL,
checkpoint_comment_agent TEXT,
PRIMARY KEY (qc_checkpoint_log_id)
) ENGINE=MyISAM;

CREATE TABLE quality_control_checkpoints (
checkpoint_row_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
qc_scorecard_id VARCHAR(20) DEFAULT NULL,
checkpoint_text TEXT,
checkpoint_text_presets TEXT,
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

CREATE TABLE vicidial_agent_visibility_log (
db_time DATETIME NOT NULL,
event_start_epoch INT(10) UNSIGNED,
event_end_epoch INT(10) UNSIGNED,
user VARCHAR(20),
length_in_sec INT(10),
visibility  ENUM('VISIBLE','HIDDEN','LOGIN','NONE') default 'NONE',
agent_log_id INT(9) UNSIGNED,
index (db_time),
index (agent_log_id),
unique index visibleuser (user, visibility, event_end_epoch)
) ENGINE=MyISAM;

CREATE TABLE vicidial_peer_event_log (
`peer_event_id` INT(9) UNSIGNED NOT NULL AUTO_INCREMENT,
`event_type` ENUM('UNKNOWN','REGISTERED','UNREGISTERED','REACHABLE','LAGGED','UNREACHABLE','RTPDISCONNECT','CRITICALTIMEOUT') COLLATE utf8_unicode_ci DEFAULT 'UNKNOWN',
`event_date` DATETIME(6) NOT NULL,
`channel` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT '',
`server_ip` VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
`host_ip` VARCHAR(15) COLLATE utf8_unicode_ci DEFAULT '',
`port` SMALLINT(6) DEFAULT NULL,
`channel_type` ENUM('IAX2','SIP','PJSIP') COLLATE utf8_unicode_ci DEFAULT NULL,
`peer` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT '',
`data` VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT '',
PRIMARY KEY (`peer_event_id`),
KEY `event_date` (`event_date`),
KEY `peer` (`peer`),
KEY `channel` (`channel`)
) ENGINE=MyISAM AUTO_INCREMENT=630320 DEFAULT CHARSET=utf8 
COLLATE=utf8_unicode_ci;

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
`khomp_settings_container` varchar(40),
PRIMARY KEY (`khomp_log_id`),
KEY `caller_code` (`caller_code`),
KEY `start_date` (`start_date`),
KEY `khomp_id` (`khomp_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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

CREATE TABLE vicidial_queue_groups (
queue_group VARCHAR(20) NOT NULL,
queue_group_name VARCHAR(40) NOT NULL,
included_campaigns TEXT,
included_inbound_groups TEXT,
user_group VARCHAR(20) default '---ALL---',
active ENUM('Y','N')
) ENGINE=MyISAM;

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

CREATE TABLE `vicidial_confbridges` (   
`conf_exten` INT(7) UNSIGNED NOT NULL,
`server_ip` VARCHAR(15) COLLATE utf8_unicode_ci NOT NULL,
`extension` VARCHAR(100) COLLATE utf8_unicode_ci DEFAULT NULL,
`leave_3way` ENUM('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
`leave_3way_datetime` DATETIME DEFAULT NULL,
UNIQUE KEY `serverconf` (`server_ip`,`conf_exten`),
UNIQUE KEY `conf_exten` (`conf_exten`,`server_ip`) 
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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

CREATE TABLE vicidial_long_extensions (
le_id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY NOT NULL,
extension VARCHAR(1000),
call_date DATETIME default '2001-01-01 00:00:01',
source VARCHAR(20) default '',
index (call_date)
) ENGINE=MyISAM;

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

CREATE TABLE vicidial_agent_notifications_queue (
queue_id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
notification_id INT(10) UNSIGNED DEFAULT NULL,
queue_date DATETIME DEFAULT current_timestamp(),
user VARCHAR(20) DEFAULT NULL,
PRIMARY KEY (queue_id)
) ENGINE=MyISAM;

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
call_channel VARCHAR(100) default '',
agent_heartbeat DATETIME,
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
call_channel VARCHAR(100) default '',
call_transfer ENUM('N','Y') default 'N',
index(call_date),
index(caller_code),
index(call_3way_id),
index(lead_id),
index(phone_number)
) ENGINE=MyISAM;

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

CREATE TABLE vicidial_phone_number_call_daily_counts (
phone_number VARCHAR(18) NOT NULL,
called_count TINYINT(3) UNSIGNED default '0',
modify_date DATETIME,
unique index vpncdc_phone_number (phone_number)
) ENGINE=MyISAM;

CREATE TABLE vicidial_3way_press_multi (
user VARCHAR(20) PRIMARY KEY,
call_date DATETIME,
phone_numbers VARCHAR(255) default '',
phone_numbers_ct TINYINT(3) default '0',
status VARCHAR(40) default '',
index(call_date)
) ENGINE=MyISAM;

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


ALTER TABLE vicidial_email_list MODIFY message text character set utf8;

ALTER TABLE vicidial_email_log MODIFY message text character set utf8;

ALTER TABLE vicidial_qc_codes ADD qc_result_type ENUM( 'PASS', 'FAIL', 'CANCEL', 'COMMIT' ) NOT NULL;

ALTER TABLE vicidial_campaign_server_stats ENGINE=MEMORY;

ALTER TABLE live_channels ENGINE=MEMORY;

ALTER TABLE live_sip_channels ENGINE=MEMORY;

ALTER TABLE parked_channels ENGINE=MEMORY;

ALTER TABLE server_updater ENGINE=MEMORY;

ALTER TABLE web_client_sessions ENGINE=MEMORY;

ALTER TABLE vicidial_auto_calls ENGINE=MEMORY;

ALTER TABLE vicidial_hopper ENGINE=MEMORY;


UPDATE system_settings SET auto_user_add_value='1101';

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

INSERT INTO vicidial_music_on_hold SET moh_id='default',moh_name='Default Music On Hold',active='Y',random='N';
INSERT INTO vicidial_music_on_hold_files SET moh_id='default',filename='conf',rank='1';

INSERT INTO vicidial_inbound_groups(group_id,group_name,group_color,active,queue_priority) values('AGENTDIRECT','Single Agent Direct Queue','white','Y','99');
INSERT INTO vicidial_inbound_groups(group_id,group_name,group_color,active,web_form_address,voicemail_ext,next_agent_call,fronter_display,ingroup_script,get_call_launch,xferconf_a_dtmf,xferconf_a_number,xferconf_b_dtmf,xferconf_b_number,drop_call_seconds,drop_action,drop_exten,call_time_id,after_hours_action,after_hours_message_filename,after_hours_exten,after_hours_voicemail,welcome_message_filename,moh_context,onhold_prompt_filename,prompt_interval,agent_alert_exten,agent_alert_delay,default_xfer_group,queue_priority,drop_inbound_group,ingroup_recording_override,ingroup_rec_filename,afterhours_xfer_group,qc_enabled,qc_statuses,qc_shift_id,qc_get_record_launch,qc_show_recording,qc_web_form_address,qc_script,play_place_in_line,play_estimate_hold_time,hold_time_option,hold_time_option_seconds,hold_time_option_exten,hold_time_option_voicemail,hold_time_option_xfer_group,hold_time_option_callback_filename,hold_time_option_callback_list_id,hold_recall_xfer_group,no_delay_call_route,play_welcome_message,answer_sec_pct_rt_stat_one,answer_sec_pct_rt_stat_two,default_group_alias,no_agent_no_queue,no_agent_action,no_agent_action_value,web_form_address_two,timer_action,timer_action_message,timer_action_seconds,start_call_url,dispo_call_url,xferconf_c_number,xferconf_d_number,xferconf_e_number,ignore_list_script_override,extension_appended_cidname,uniqueid_status_display,uniqueid_status_prefix,hold_time_option_minimum,hold_time_option_press_filename,hold_time_option_callmenu,hold_time_option_no_block,hold_time_option_prompt_seconds,onhold_prompt_no_block,onhold_prompt_seconds,hold_time_second_option,hold_time_third_option,wait_hold_option_priority,wait_time_option,wait_time_second_option,wait_time_third_option,wait_time_option_seconds,wait_time_option_exten,wait_time_option_voicemail,wait_time_option_xfer_group,wait_time_option_callmenu,wait_time_option_callback_filename,wait_time_option_callback_list_id,wait_time_option_press_filename,wait_time_option_no_block,wait_time_option_prompt_seconds,timer_action_destination,calculate_estimated_hold_seconds,add_lead_url,eht_minimum_prompt_filename,eht_minimum_prompt_no_block,eht_minimum_prompt_seconds,on_hook_ring_time,na_call_url,on_hook_cid,group_calldate,action_xfer_cid,drop_callmenu,after_hours_callmenu,user_group,max_calls_method,max_calls_count,max_calls_action,dial_ingroup_cid,group_handling,web_form_address_three,populate_lead_ingroup,drop_lead_reset,after_hours_lead_reset,nanq_lead_reset,wait_time_lead_reset,hold_time_lead_reset,status_group_id,routing_initiated_recordings,on_hook_cid_number) VALUES ('AGENTDIRECT_CHAT','Agent Direct Queue for Chats','#FFFFFF','Y','','','longest_wait_time','Y','','NONE',NULL,NULL,NULL,NULL,360,'MESSAGE','8307','24hours','MESSAGE','vm-goodbye','8300',NULL,'---NONE---','default','generic_hold',60,'ding',1000,'---NONE---',99,'---NONE---','DISABLED','NONE','---NONE---','N',NULL,'24HRMIDNIGHT','NONE','Y',NULL,NULL,'N','N','NONE',360,'8300','','---NONE---','vm-hangup',0,'---NONE---','N','ALWAYS',20,30,'','N','MESSAGE','nbdy-avail-to-take-call|vm-goodbye','','NONE','',-1,'','','','','','N','N','DISABLED','',0,'to-be-called-back|digits/1','','N',10,'N',10,'NONE','NONE','WAIT','NONE','NONE','NONE',120,'8300','','---NONE---','','vm-hangup',999,'to-be-called-back|digits/1','N',10,'',0,'','','N',10,15,'','GENERIC',NULL,'CUSTOMER','','','---ALL---','DISABLED',0,'DROP','','CHAT','','ENABLED','N','N','N','N','N','','N','');

INSERT INTO vicidial_lists SET list_id='999',list_name='Default inbound list',campaign_id='TESTCAMP',active='N';
INSERT INTO vicidial_lists SET list_id='998',list_name='Default Manual list',campaign_id='TESTCAMP',active='N';

INSERT INTO system_settings (version,install_date,first_login_trigger) values('2.14b0.5', CURDATE(), 'Y');

INSERT INTO vicidial_status_categories (vsc_id,vsc_name) values('UNDEFINED','Default Category');

INSERT INTO vicidial_user_groups SET user_group='ADMIN',group_name='VICIDIAL ADMINISTRATORS',allowed_campaigns=' -ALL-CAMPAIGNS- - -',agent_status_viewable_groups=' --ALL-GROUPS-- ',admin_viewable_groups=' ---ALL--- ',admin_viewable_call_times=' ---ALL--- ',agent_allowed_chat_groups=' --ALL-GROUPS-- ';

INSERT INTO vicidial_call_times SET call_time_id='24hours',call_time_name='default 24 hours calling',ct_default_start='0',ct_default_stop='2400';
INSERT INTO vicidial_call_times SET call_time_id='9am-9pm',call_time_name='default 9am to 9pm calling',ct_default_start='900',ct_default_stop='2100';
INSERT INTO vicidial_call_times SET call_time_id='9am-5pm',call_time_name='default 9am to 5pm calling',ct_default_start='900',ct_default_stop='1700';
INSERT INTO vicidial_call_times SET call_time_id='12pm-5pm',call_time_name='default 12pm to 5pm calling',ct_default_start='1200',ct_default_stop='1700';
INSERT INTO vicidial_call_times SET call_time_id='12pm-9pm',call_time_name='default 12pm to 9pm calling',ct_default_start='1200',ct_default_stop='2100';
INSERT INTO vicidial_call_times SET call_time_id='5pm-9pm',call_time_name='default 5pm to 9pm calling',ct_default_start='1700',ct_default_stop='2100';

INSERT INTO vicidial_state_call_times SET state_call_time_id='alabama',state_call_time_state='AL',state_call_time_name='Alabama 8am-8pm and Sunday',sct_default_start='800',sct_default_stop='2000',sct_sunday_start='2400',sct_sunday_stop='2400';
INSERT INTO vicidial_state_call_times SET state_call_time_id='florida',state_call_time_state='FL',state_call_time_name='Florida 8am 8pm',sct_default_start='800',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='illinois',state_call_time_state='IL',state_call_time_name='Illinois 8am',sct_default_start='800',sct_default_stop='2100';
INSERT INTO vicidial_state_call_times SET state_call_time_id='indiana',state_call_time_state='IN',state_call_time_name='Indiana 8pm restriction',sct_default_start='900',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='kentucky',state_call_time_state='KY',state_call_time_name='Kentucky 10am restriction',sct_default_start='1000',sct_default_stop='2100';
INSERT INTO vicidial_state_call_times SET state_call_time_id='louisiana',state_call_time_state='LA',state_call_time_name='Louisiana 8am-8pm and Sunday',sct_default_start='800',sct_default_stop='2000',sct_sunday_start='2400',sct_sunday_stop='2400';
INSERT INTO vicidial_state_call_times SET state_call_time_id='maine',state_call_time_state='ME',state_call_time_name='Maine 9am-5pm',sct_default_start='900',sct_default_stop='1700';
INSERT INTO vicidial_state_call_times SET state_call_time_id='maryland',state_call_time_state='MD',state_call_time_name='Maryland 8am 8pm',sct_default_start='800',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='massachuse',state_call_time_state='MA',state_call_time_name='Massachusetts 8am-8pm',sct_default_start='800',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='mississipp',state_call_time_state='MS',state_call_time_name='Mississippi 8am-8pm and Sunday',sct_default_start='800',sct_default_stop='2000',sct_sunday_start='2400',sct_sunday_stop='2400';
INSERT INTO vicidial_state_call_times SET state_call_time_id='nebraska',state_call_time_state='NE',state_call_time_name='Nebraska 8am',sct_default_start='800',sct_default_stop='2100';
INSERT INTO vicidial_state_call_times SET state_call_time_id='nevada',state_call_time_state='NV',state_call_time_name='Nevada 8pm restriction',sct_default_start='900',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='oklahoma',state_call_time_state='OK',state_call_time_name='Oklahoma 8am 8pm',sct_default_start='800',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='pennsylvan',state_call_time_state='PA',state_call_time_name='Pennsylvania sunday restrictn',sct_sunday_start='1330',sct_sunday_stop='2100';
INSERT INTO vicidial_state_call_times SET state_call_time_id='rhodeislan',state_call_time_state='RI',state_call_time_name='Rhode Island restrictions',sct_default_start='900',sct_default_stop='1800',sct_sunday_start='2400',sct_sunday_stop='2400',sct_saturday_start='1000',sct_saturday_stop='1700';
INSERT INTO vicidial_state_call_times SET state_call_time_id='sdakota',state_call_time_state='SD',state_call_time_name='South Dakota sunday restrict',sct_sunday_start='2400',sct_sunday_stop='2400';
INSERT INTO vicidial_state_call_times SET state_call_time_id='tennessee',state_call_time_state='TN',state_call_time_name='Tennessee 8am',sct_default_start='800',sct_default_stop='2100';
INSERT INTO vicidial_state_call_times SET state_call_time_id='texas',state_call_time_state='TX',state_call_time_name='Texas sunday restriction',sct_sunday_start='1200',sct_sunday_stop='2100';
INSERT INTO vicidial_state_call_times SET state_call_time_id='utah',state_call_time_state='UT',state_call_time_name='Utah 8pm restriction',sct_default_start='900',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='washington',state_call_time_state='WA',state_call_time_name='Washington 8am-8pm',sct_default_start='800',sct_default_stop='2000';
INSERT INTO vicidial_state_call_times SET state_call_time_id='wyoming',state_call_time_state='WY',state_call_time_name='Wyoming 8am-8pm',sct_default_start='800',sct_default_stop='2000';

INSERT INTO vicidial_shifts SET shift_id='24HRMIDNIGHT',shift_name='24 hours 7 days a week',shift_start_time='0000',shift_length='24:00',shift_weekdays='0123456';

INSERT INTO vicidial_conf_templates SET template_id='SIP_generic',template_name='SIP phone generic',template_contents="type=friend\nhost=dynamic\ncanreinvite=no\ncontext=default";
INSERT INTO vicidial_conf_templates SET template_id='IAX_generic',template_name='IAX phone generic',template_contents="type=friend\nhost=dynamic\nmaxauthreq=10\nauth=md5,plaintext,rsa\ncontext=default";

INSERT INTO vicidial_server_carriers SET carrier_id='OLD_SIPEXAMPLE', carrier_name='OLD TEST SIP carrier example',registration_string='register => testcarrier:test@10.10.10.15:5060', template_id='--NONE--', account_entry="[testcarrier]\ndisallow=all\nallow=ulaw\ntype=friend\nusername=testcarrier\nsecret=test\nhost=dynamic\ndtmfmode=rfc2833\ncontext=trunkinbound\n", protocol='SIP', globals_string='TESTSIPTRUNK = SIP/testcarrier', dialplan_entry="exten => _91999NXXXXXX,1,AGI(agi://127.0.0.1:4577/call_log)\nexten => _91999NXXXXXX,2,Dial(${TESTSIPTRUNK}/${EXTEN:2},${CAMPDTO},To)\nexten => _91999NXXXXXX,3,Hangup\n", server_ip='10.10.10.15', active='N';
INSERT INTO vicidial_server_carriers SET carrier_id='OLD_IAXEXAMPLE', carrier_name='OLD TEST IAX carrier example',registration_string='register => testcarrier:test@10.10.10.15:4569', template_id='--NONE--', account_entry="[testcarrier]\ndisallow=all\nallow=ulaw\ntype=friend\naccountcode=testcarrier\nsecret=test\nhost=dynamic\ncontext=trunkinbound\n", protocol='IAX2', globals_string='TESTIAXTRUNK = IAX2/testcarrier', dialplan_entry="exten => _71999NXXXXXX,1,AGI(agi://127.0.0.1:4577/call_log)\nexten => _71999NXXXXXX,2,Dial(${TESTIAXTRUNK}/${EXTEN:2},${CAMPDTO},To)\nexten => _71999NXXXXXX,3,Hangup\n", server_ip='10.10.10.15', active='N';
INSERT INTO `vicidial_server_carriers`(carrier_id,carrier_name,registration_string,template_id,account_entry,protocol,globals_string,dialplan_entry,server_ip,active,carrier_description,user_group) VALUES ('SIPExample', 'SIP Example', '', '--NONE--', '[ExampleSIP]\ntype = peer\ncontext = trunkinbound\nusecallerid = yes\ntrustrpid = no\nsendrpid = yes\nhost = 10.10.10.15\nqualify = yes\ninsecure = port,invite\ndisallow = all\nallow = ulaw\ndtmfmode = auto', 'SIP', 'SIPTRUNK = SIP/ExampleSIP', 'exten => _91999NXXXXXX,1,AGI(agi://127.0.0.1:4577/call_log)\nexten => _91999NXXXXXX,2,Dial(${SIPTRUNK}/${EXTEN:1},${CAMPDTO},To)\nexten => _91999NXXXXXX,3,Hangup', '0.0.0.0', 'N', 'A SIP example carrier using IP Authentication', '---ALL---');
INSERT INTO `vicidial_server_carriers`(carrier_id,carrier_name,registration_string,template_id,account_entry,protocol,globals_string,dialplan_entry,server_ip,active,carrier_description,user_group) VALUES ('PJSIPExample', 'PJSIP Example', '', '--NONE--', '[ExamplePJSIP]\ntype = aor\ncontact = sip:10.10.10.15\nqualify_frequency = 15\nmaximum_expiration = 3600\nminimum_expiration = 60\ndefault_expiration = 120\n\n[ExamplePJSIP]\ntype = identify\nendpoint = ExamplePJSIP\nmatch = 10.10.10.15\n\n[ExamplePJSIP]\ntype = endpoint\ncontext = trunkinbound\ndtmf_mode = rfc4733\ndisallow = all\nallow = ulaw\nrtp_symmetric = yes\nrewrite_contact = yes\nrtp_timeout = 60\nuse_ptime = yes\nmoh_suggest = default\ndirect_media = no\ntrust_id_inbound = yes\nsend_rpid = yes\ninband_progress = no\ntos_audio = ef\nlanguage = en\naors = ExamplePJSIP\ndtmf_mode=auto', 'PJSIP', 'PJTRUNK=ExamplePJSIP', 'exten => _91999NXXXXXX,1,AGI(agi://127.0.0.1:4577/call_log)\nexten => _91999NXXXXXX,n,Dial(PJSIP/${EXTEN:1}@${PJTRUNK},${CAMPDTO},To)\nexten => _91999NXXXXXX,n,Hangup()', '0.0.0.0', 'N', 'A PJSIP example carrier using IP authentication', '---ALL---');
INSERT INTO `vicidial_server_carriers`(carrier_id,carrier_name,registration_string,template_id,account_entry,protocol,globals_string,dialplan_entry,server_ip,active,carrier_description,user_group) VALUES ('PJSIPWIZExample', 'PJSIP_WIZ Example', '', '--NONE--', '[ExamplePJSIPWIZ]\ntype = wizard\nremote_hosts = 10.10.10.15:5060\nsends_registrations = no\naccepts_registrations = no\nsends_auth = no\naccepts_auth = no\naor/qualify_frequency = 15\naor/maximum_expiration = 3600\naor/minimum_expiration = 60\naor/default_expiration = 120\nendpoint/allow_subscribe = no\nendpoint/context = trunkinbound\nendpoint/dtmf_mode = auto\nendpoint/disallow = all\nendpoint/allow = ulaw\nendpoint/rtp_symmetric = yes\nendpoint/rewrite_contact = yes\nendpoint/rtp_timeout = 60\nendpoint/use_ptime = yes\nendpoint/moh_suggest = default\nendpoint/direct_media = no\nendpoint/trust_id_inbound = yes\nendpoint/send_rpid = yes\nendpoint/inband_progress = no\nendpoint/tos_audio = ef\nendpoint/language = en', 'PJSIP_WIZ', 'WIZTRK=ExamplePJSIPWIZ', 'exten => _91999NXXXXXX,1,AGI(agi://127.0.0.1:4577/call_log)\nexten => _91999NXXXXXX,n,Dial(PJSIP/${EXTEN:1}@${WIZTRK},${CAMPDTO},To)\nexten => _91999NXXXXXX,n,Hangup()', '0.0.0.0', 'N', 'A PJSIP_WIZ example carrier using IP authentication', '---ALL---');

INSERT INTO vicidial_inbound_dids SET did_pattern='default', did_description='Default DID', did_active='Y', did_route='EXTEN', extension='9998811112', exten_context='default';

INSERT INTO vicidial_override_ids(id_table,active,value) values('vicidial_users','0','1000');
INSERT INTO vicidial_override_ids(id_table,active,value) values('vicidial_campaigns','0','20000');
INSERT INTO vicidial_override_ids(id_table,active,value) values('vicidial_inbound_groups','0','30000');
INSERT INTO vicidial_override_ids(id_table,active,value) values('vicidial_lists','0','40000');
INSERT INTO vicidial_override_ids(id_table,active,value) values('vicidial_call_menu','0','50000');
INSERT INTO vicidial_override_ids(id_table,active,value) values('vicidial_user_groups','0','60000');
INSERT INTO vicidial_override_ids(id_table,active,value) values('vicidial_lead_filters','0','70000');
INSERT INTO vicidial_override_ids(id_table,active,value) values('vicidial_scripts','0','80000');
INSERT INTO vicidial_override_ids(id_table,active,value) values('phones','0','100');

INSERT INTO vicidial_lead_filters(lead_filter_id,lead_filter_name,lead_filter_comments,lead_filter_sql) values('DROP72HOUR','UK 72 hour Drop No Call','Prevents dropped calls from being called within 72 hours of the last attempt',"( ( (status='DROP') and (last_local_call_time < CONCAT(DATE_ADD(CURDATE(), INTERVAL -3 DAY),' ',CURTIME()) ) ) or (status != 'DROP') )");

INSERT INTO vicidial_drop_rate_groups SET group_id='101';
INSERT INTO vicidial_drop_rate_groups SET group_id='102';
INSERT INTO vicidial_drop_rate_groups SET group_id='103';
INSERT INTO vicidial_drop_rate_groups SET group_id='104';
INSERT INTO vicidial_drop_rate_groups SET group_id='105';
INSERT INTO vicidial_drop_rate_groups SET group_id='106';
INSERT INTO vicidial_drop_rate_groups SET group_id='107';
INSERT INTO vicidial_drop_rate_groups SET group_id='108';
INSERT INTO vicidial_drop_rate_groups SET group_id='109';
INSERT INTO vicidial_drop_rate_groups SET group_id='110';

INSERT INTO vicidial_process_triggers SET trigger_id='LOAD_LEADS',server_ip='10.10.10.15',trigger_name='Load Leads',trigger_time='2009-01-01 00:00:00',trigger_run='0',trigger_lines='/usr/share/astguiclient/VICIDIAL_IN_new_leads_file.pl';

INSERT INTO vicidial_call_menu SET menu_id='defaultlog',menu_name='logging of all outbound calls from agent phones',menu_prompt='sip-silence',menu_timeout='20',menu_timeout_prompt='NONE',menu_invalid_prompt='NONE',menu_repeat='0',menu_time_check='0',call_time_id='',track_in_vdac='0',custom_dialplan_entry='exten => _X.,1,AGI(agi-NVA_recording.agi,BOTH------Y---Y---Y)\nexten => _X.,n,Goto(default,${EXTEN},1)',tracking_group='';
INSERT INTO vicidial_call_menu SET menu_id='default---agent',menu_name='agent phones restricted to only internal extensions',menu_prompt='sip-silence',menu_timeout='20',menu_timeout_prompt='NONE',menu_invalid_prompt='NONE',menu_repeat='0',menu_time_check='0',call_time_id='',track_in_vdac='0',custom_dialplan_entry='include => vicidial-auto-internal\ninclude => vicidial-auto-phones\n',tracking_group='';
INSERT INTO vicidial_call_menu (menu_id,menu_name,menu_prompt,menu_timeout,menu_timeout_prompt,menu_invalid_prompt,menu_repeat,menu_time_check,call_time_id,track_in_vdac,custom_dialplan_entry,tracking_group,dtmf_log,dtmf_field,user_group,qualify_sql,alt_dtmf_log,question,answer_signal) values('2FA_say_auth_code','2FA_say_auth_code','sip-silence|hello|your|access-code|is|cm_speak_var.agi,say_digits---access_code---DP',1,'NONE','NONE',1,'0','24hours','1','','CALLMENU','0','NONE','---ALL---','','0',0,'Y');

INSERT INTO vicidial_call_menu_options SET menu_id='defaultlog',option_value='TIMEOUT',option_description='hangup',option_route='HANGUP',option_route_value='vm-goodbye',option_route_value_context='';
INSERT INTO vicidial_call_menu_options SET menu_id='default---agent',option_value='TIMEOUT',option_description='hangup',option_route='HANGUP',option_route_value='vm-goodbye',option_route_value_context='';
INSERT INTO vicidial_call_menu_options (menu_id,option_value,option_description,option_route,option_route_value,option_route_value_context) values('2FA_say_auth_code','TIMEOUT','','HANGUP','','');

INSERT INTO vicidial_scripts (script_id,script_name,script_comments,active,script_text) values('CALLNOTES','Call Notes and Appointment Setting','','Y','<iframe src=\"../agc/vdc_script_notes.php?lead_id=--A--lead_id--B--&vendor_id=--A--vendor_lead_code--B--&list_id=--A--list_id--B--&gmt_offset_now=--A--gmt_offset_now--B--&phone_code=--A--phone_code--B--&phone_number=--A--phone_number--B--&title=--A--title--B--&first_name=--A--first_name--B--&middle_initial=--A--middle_initial--B--&last_name=--A--last_name--B--&address1=--A--address1--B--&address2=--A--address2--B--&address3=--A--address3--B--&city=--A--city--B--&state=--A--state--B--&province=--A--province--B--&postal_code=--A--postal_code--B--&country_code=--A--country_code--B--&gender=--A--gender--B--&date_of_birth=--A--date_of_birth--B--&alt_phone=--A--alt_phone--B--&email=--A--email--B--&security_phrase=--A--security_phrase--B--&comments=--A--comments--B--&user=--A--user--B--&pass=--A--pass--B--&campaign=--A--campaign--B--&phone_login=--A--phone_login--B--&fronter=--A--fronter--B--&closer=--A--user--B--&group=--A--group--B--&channel_group=--A--group--B--&SQLdate=--A--SQLdate--B--&epoch=--A--epoch--B--&uniqueid=--A--uniqueid--B--&rank=--A--rank--B--&owner=--A--owner--B--&customer_zap_channel=--A--customer_zap_channel--B--&server_ip=--A--server_ip--B--&SIPexten=--A--SIPexten--B--&session_id=--A--session_id--B--\" style=\"background-color:transparent;\" scrolling=\"auto\" frameborder=\"0\" allowtransparency=\"true\" id=\"popupFrame\" name=\"popupFrame\"  width=\"--A--script_width--B--\" height=\"--A--script_height--B--\" STYLE=\"z-index:17\"> </iframe>');

INSERT INTO vicidial_custom_leadloader_templates (template_id, template_name, template_description, list_id, standard_variables, custom_table, custom_variables, template_statuses) values ('SAMPLE_TEMPLATE','Sample template','',999,'phone_number,9|first_name,0|last_name,1|address1,3|address2,4|address3,5|city,6|state,7|postal_code,8|','custom_999','appointment_date,2|appointment_notes,9|nearest_city,2|','');

INSERT INTO vicidial_screen_colors VALUES ('red_rust','dark red rust','Y','804435','E7D0C2','C68C71','D9B39F','D9B49F','C68C72','C68C73','BDFFBD','99FF99','CCFFCC','---ALL---','default_new','EFEFEF'),('pale_green','pale green','Y','738035','E0E7C2','B6C572','C4CF8B','B6C572','C4CF8B','C4CF8B','BDFFBD','99FF99','CCFFCC','---ALL---','default_new','EFEFEF'),('alt_green','alternate green','Y','333333','D6E3B2','AEC866','BCD180','BCD180','AEC866','AEC866','BDFFBD','99FF99','CCFFCC','---ALL---','default_new','EFEFEF'),('default_blue_test','default blue test','Y','015B91','D9E6FE','9BB9FB','B9CBFD','8EBCFD','B6D3FC','A3C3D6','BDFFBD','99FF99','CCFFCC','---ALL---','default_new','EFEFEF'),('basic_orange','basic orange','Y','804d00','ffebcc','ffcc80','ffd699','ffcc80','ffd699','ffcc80','BDFFBD','99FF99','CCFFCC','---ALL---','default_new','EFEFEF'),('basic_purple','basic purple','Y','660066','ffccff','ff99ff','ffb3ff','ff99ff','ffb3ff','ff99ff','BDFFBD','99FF99','CCFFCC','---ALL---','SAMPLE.png','EFEFEF'),('basic_yellow','basic yellow','Y','666600','ffffcc','ffff66','ffff99','ffff66','ffff99','ffff66','BDFFBD','99FF99','CCFFCC','---ALL---','default_new','EFEFEF'),('basic_red','basic red','Y','800000','ffe6e6','ff9999','ffb3b3','ff9999','ffb3b3','ff9999','BDFFBD','99FF99','CCFFCC','---ALL---','default_new','EFEFEF');
INSERT INTO vicidial_screen_colors VALUES ('default_grey_agent','default grey agent','Y','FFFFFF','cccccc','E6E6E6','E6E6E6','E6E6E6','E6E6E6','E6E6E6','E6E6E6','E6E6E6','E6E6E6','---ALL---','DEFAULTAGENT.png','EFEFEF');

UPDATE system_settings SET qc_last_pull_time=NOW();

UPDATE system_settings SET voicemail_timezones="newzealand=Pacific/Auckland\naustraliaeast=Australia/Sydney\naustraliacentral=Australia/Adelaide\naustraliawest=Australia/Perth\njapan=Asia/Tokyo\nphilippines=Asia/Manila\nchina=Asia/Shanghai\nmalaysia=Asia/Kuala_Lumpur\nthailand=Asia/Bangkok\nindia=Asia/Calcutta\npakistan=Asia/Karachi\nrussiaeast=Europe/Moscow\nkenya=Africa/Nairobi\neuropeaneast=Europe/Kiev\nsouthafrica=Africa/Johannesburg\neuropean=Europe/Copenhagen\nnigeria=Africa/Lagos\nuk=Europe/London\nbrazil=America/Sao_Paulo\nnewfoundland=Canada/Newfoundland\ncarribeaneast=America/Santo_Domingo\natlantic=Canada/Atlantic\nchile=America/Santiago\neastern=America/New_York\nperu=America/Lima\ncentral=America/Chicago\nmexicocity=America/Mexico_City\nmountain=America/Denver\narizona=America/Phoenix\nsaskatchewan=America/Saskatchewan\npacific=America/Los_Angeles\nalaska=America/Anchorage\nhawaii=Pacific/Honolulu\neastern24=America/New_York\ncentral24=America/Chicago\nmountain24=America/Denver\npacific24=America/Los_Angeles\nmilitary=Zulu\n";

CREATE INDEX country_postal_code on vicidial_postal_codes (country_code,postal_code);
CREATE INDEX country_area_code on vicidial_phone_codes (country_code,areacode);
CREATE INDEX country_state on vicidial_phone_codes (country_code,state);
CREATE INDEX country_code on vicidial_phone_codes (country_code);
CREATE INDEX phone_list on vicidial_list (phone_number,list_id);
CREATE INDEX list_phone on vicidial_list (list_id,phone_number);
CREATE INDEX start_time on call_log (start_time);
CREATE INDEX end_time on call_log (end_time);
CREATE INDEX time on call_log (start_time,end_time);
CREATE INDEX list_status on vicidial_list (list_id,status);
CREATE INDEX time_user on vicidial_agent_log (event_time,user);
CREATE INDEX date_user on vicidial_xfer_log (call_date,user);
CREATE INDEX date_closer on vicidial_xfer_log (call_date,closer);
CREATE INDEX phone_number on vicidial_xfer_log (phone_number);
CREATE INDEX phone_number on vicidial_closer_log (phone_number);
CREATE INDEX date_user on vicidial_closer_log (call_date,user);
CREATE INDEX comment_a on live_inbound_log (comment_a);
CREATE UNIQUE INDEX vicidial_campaign_statuses_key on vicidial_campaign_statuses(status, campaign_id);
CREATE INDEX vlecc on vicidial_log_extended (caller_code);
CREATE UNIQUE INDEX vvmmcount on vicidial_vmm_counts (lead_id,call_date);
CREATE UNIQUE INDEX vicidial_user_logins_daily_user on vicidial_user_logins_daily(login_day, user);

CREATE INDEX vlali on vicidial_live_agents (lead_id);
CREATE INDEX vlaus on vicidial_live_agents (user);

CREATE TABLE call_log_archive LIKE call_log; 

CREATE TABLE vicidial_log_archive LIKE vicidial_log;

CREATE TABLE vicidial_agent_log_archive LIKE vicidial_agent_log; 
ALTER TABLE vicidial_agent_log_archive MODIFY agent_log_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_carrier_log_archive LIKE vicidial_carrier_log;

CREATE TABLE vicidial_call_notes_archive LIKE vicidial_call_notes; 
ALTER TABLE vicidial_call_notes_archive MODIFY notesid INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_lead_search_log_archive LIKE vicidial_lead_search_log; 
ALTER TABLE vicidial_lead_search_log_archive MODIFY search_log_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_closer_log_archive LIKE vicidial_closer_log; 
ALTER TABLE vicidial_closer_log_archive MODIFY closecallid INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_xfer_log_archive LIKE vicidial_xfer_log; 
ALTER TABLE vicidial_xfer_log_archive MODIFY xfercallid INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_outbound_ivr_log_archive LIKE vicidial_outbound_ivr_log;

CREATE TABLE vicidial_log_extended_archive LIKE vicidial_log_extended;
CREATE UNIQUE INDEX vlea on vicidial_log_extended_archive (uniqueid,call_date,lead_id);

CREATE TABLE vicidial_log_extended_sip_archive LIKE vicidial_log_extended_sip;
CREATE UNIQUE INDEX vlesa on vicidial_log_extended_sip_archive (caller_code,call_date);

CREATE TABLE vicidial_log_noanswer_archive LIKE vicidial_log_noanswer; 

CREATE TABLE vicidial_did_agent_log_archive LIKE vicidial_did_agent_log; 
CREATE UNIQUE INDEX vdala on vicidial_did_agent_log_archive (uniqueid,call_date,did_route);

CREATE TABLE vicidial_dial_log_archive LIKE vicidial_dial_log;
CREATE UNIQUE INDEX vddla on vicidial_dial_log_archive (caller_code,call_date);

CREATE TABLE vicidial_api_log_archive LIKE vicidial_api_log;
ALTER TABLE vicidial_api_log_archive MODIFY api_id INT(9) UNSIGNED NOT NULL;
CREATE TABLE vicidial_api_urls_archive LIKE vicidial_api_urls;

CREATE TABLE vicidial_callbacks_archive LIKE vicidial_callbacks;
ALTER TABLE vicidial_callbacks_archive MODIFY callback_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE recording_log_archive LIKE recording_log;
ALTER TABLE recording_log_archive MODIFY recording_id INT(10) UNSIGNED UNIQUE NOT NULL;
ALTER TABLE recording_log_archive DROP PRIMARY KEY;

CREATE TABLE vicidial_drop_log_archive LIKE vicidial_drop_log; 
DROP INDEX drop_date on vicidial_drop_log_archive;
CREATE UNIQUE INDEX vicidial_drop_log_archive_key on vicidial_drop_log_archive(drop_date, uniqueid);

CREATE TABLE vicidial_rt_monitor_log_archive LIKE vicidial_rt_monitor_log; 

CREATE TABLE vicidial_campaign_hour_counts_archive LIKE vicidial_campaign_hour_counts;

CREATE TABLE vicidial_carrier_hour_counts_archive LIKE vicidial_carrier_hour_counts;

CREATE TABLE vicidial_ingroup_hour_counts_archive LIKE vicidial_ingroup_hour_counts;

CREATE TABLE user_call_log_archive LIKE user_call_log;
ALTER TABLE user_call_log_archive MODIFY user_call_log_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_inbound_survey_log_archive LIKE vicidial_inbound_survey_log;
CREATE UNIQUE INDEX visla_key on vicidial_inbound_survey_log_archive(uniqueid, call_date, campaign_id, lead_id);

CREATE TABLE vicidial_inbound_callback_queue_archive LIKE vicidial_inbound_callback_queue; 
ALTER TABLE vicidial_inbound_callback_queue_archive MODIFY icbq_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_agent_function_log_archive LIKE vicidial_agent_function_log;
ALTER TABLE vicidial_agent_function_log_archive MODIFY agent_function_log_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_did_log_archive LIKE vicidial_did_log;
CREATE UNIQUE INDEX vdidla_key on vicidial_did_log_archive(uniqueid, call_date, server_ip);

CREATE TABLE vicidial_recent_ascb_calls_archive LIKE vicidial_recent_ascb_calls;

CREATE TABLE vicidial_ccc_log_archive LIKE vicidial_ccc_log;
CREATE UNIQUE INDEX ccc_unq_key on vicidial_ccc_log_archive(uniqueid, call_date, lead_id);

CREATE TABLE vicidial_amd_log_archive LIKE vicidial_amd_log;
CREATE UNIQUE INDEX amd_unq_key on vicidial_amd_log_archive(uniqueid, call_date, lead_id);

CREATE TABLE vicidial_sessions_recent_archive LIKE vicidial_sessions_recent;

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

CREATE TABLE vicidial_sip_action_log_archive LIKE vicidial_sip_action_log;
CREATE UNIQUE INDEX vlesa on vicidial_sip_action_log_archive (caller_code,call_date);

CREATE TABLE vicidial_vmm_counts_archive LIKE vicidial_vmm_counts;

CREATE TABLE park_log_archive LIKE park_log;
CREATE UNIQUE INDEX uniqueidtime_park on park_log_archive (uniqueid,parked_time);

CREATE TABLE vicidial_agent_visibility_log_archive LIKE vicidial_agent_visibility_log;

CREATE TABLE vicidial_peer_event_log_archive LIKE vicidial_peer_event_log;
ALTER TABLE vicidial_peer_event_log_archive MODIFY peer_event_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_inbound_caller_codes_archive LIKE vicidial_inbound_caller_codes;

CREATE TABLE vicidial_dial_cid_log_archive LIKE vicidial_dial_cid_log;
CREATE UNIQUE INDEX caller_code_date on vicidial_dial_cid_log_archive (caller_code,call_date);

CREATE TABLE vicidial_abandon_check_queue_archive LIKE vicidial_abandon_check_queue;
ALTER TABLE vicidial_abandon_check_queue_archive MODIFY abandon_check_id INT(9) UNSIGNED NOT NULL;

CREATE TABLE vicidial_agent_notifications_archive LIKE vicidial_agent_notifications;
ALTER TABLE vicidial_agent_notifications_archive MODIFY notification_id INT(10) UNSIGNED NOT NULL;

CREATE TABLE vicidial_user_dial_log_archive LIKE vicidial_user_dial_log;
CREATE UNIQUE INDEX vdudl on vicidial_user_dial_log_archive (caller_code,call_date,user);

CREATE TABLE vicidial_agent_latency_log_archive LIKE vicidial_agent_latency_log;
CREATE UNIQUE INDEX vdalla on vicidial_agent_latency_log_archive (user,log_date);

CREATE TABLE vicidial_agent_latency_summary_log_archive LIKE vicidial_agent_latency_summary_log;
CREATE UNIQUE INDEX vdalsla on vicidial_agent_latency_summary_log_archive (user,log_date,web_ip);

CREATE TABLE vicidial_latency_gaps_archive LIKE vicidial_latency_gaps;
CREATE UNIQUE INDEX vdlga on vicidial_latency_gaps_archive (user,gap_date);

CREATE TABLE vicidial_3way_press_log_archive LIKE vicidial_3way_press_log;
CREATE UNIQUE INDEX vdpla on vicidial_3way_press_log_archive (call_date,caller_code,user);

CREATE TABLE vicidial_daily_rt_monitoring_log LIKE vicidial_rt_monitor_log;

GRANT RELOAD ON *.* TO cron@'%';
GRANT RELOAD ON *.* TO cron@localhost;

flush privileges;

INSERT INTO vicidial_users (user,pass,full_name,user_level,user_group,load_leads,campaign_detail,ast_admin_access,modify_users,alter_agent_interface_options) values('6666','1234','Admin','9','ADMIN','1','1','1','1','1');
INSERT INTO vicidial_users (user,pass,full_name,user_level,user_group,active) values('VDAD','donotedit','Outbound Auto Dial','1','ADMIN','N');
INSERT INTO vicidial_users (user,pass,full_name,user_level,user_group,active) values('VDCL','donotedit','Inbound No Agent','1','ADMIN','N');

INSERT INTO vicidial_list(status,list_id,phone_code,phone_number,first_name,last_name,address1,city,state,postal_code,country_code,gender,email) values('NEW','101','1','7275551212','Matt','lead01','1234 Fake St.','Clearwater','FL','33760','USA','M','test@test.com');
INSERT INTO vicidial_list(status,list_id,phone_code,phone_number,first_name,last_name,address1,city,state,postal_code,country_code,gender,email) values('NEW','101','1','7275551212','Matt','lead02','1234 Fake St.','Clearwater','FL','33760','USA','M','test@test.com');
INSERT INTO vicidial_list(status,list_id,phone_code,phone_number,first_name,last_name,address1,city,state,postal_code,country_code,gender,email) values('NEW','101','1','7275551212','Matt','lead03','1234 Fake St.','Clearwater','FL','33760','USA','M','test@test.com');
INSERT INTO vicidial_list(status,list_id,phone_code,phone_number,first_name,last_name,address1,city,state,postal_code,country_code,gender,email) values('NEW','101','1','7275551212','Matt','lead04','1234 Fake St.','Clearwater','FL','33760','USA','M','test@test.com');
INSERT INTO vicidial_list(status,list_id,phone_code,phone_number,first_name,last_name,address1,city,state,postal_code,country_code,gender,email) values('NEW','101','1','7275551212','Matt','lead05','1234 Fake St.','Clearwater','FL','33760','USA','M','test@test.com');
INSERT INTO vicidial_list(status,list_id,phone_code,phone_number,first_name,last_name,address1,city,state,postal_code,country_code,gender,email) values('NEW','101','1','7275551212','Matt','lead06','1234 Fake St.','Clearwater','FL','33760','USA','M','test@test.com');
INSERT INTO vicidial_list(status,list_id,phone_code,phone_number,first_name,last_name,address1,city,state,postal_code,country_code,gender,email) values('NEW','101','1','7275551212','Matt','lead07','1234 Fake St.','Clearwater','FL','33760','USA','M','test@test.com');

INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('NEW','New Lead','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('QUEUE','Lead To Be Called','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('INCALL','Lead Being Called','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('DROP','Agent Not Available','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('XDROP','Agent Not Available IN','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('NA','No Answer AutoDial','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('CALLBK','Call Back','Y','Y','UNDEFINED','N','N','Y','N','N','Y','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('CBHOLD','Call Back Hold','N','Y','UNDEFINED','N','N','Y','N','N','Y','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('A','Answering Machine','Y','N','UNDEFINED','N','N','N','N','N','N','N','Y');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('AA','Answering Machine Auto','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('AM','Answering Machine SentToMesg','N','N','UNDEFINED','N','N','N','N','N','N','N','Y');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('AL','Answering Machine Msg Played','N','N','UNDEFINED','N','N','N','N','N','N','N','Y');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('AFAX','Fax Machine Auto','N','N','UNDEFINED','N','N','N','N','Y','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('AB','Busy Auto','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('B','Busy','Y','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('DC','Disconnected Number','Y','N','UNDEFINED','N','N','N','N','Y','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('ADC','Disconnected Number Auto','N','N','UNDEFINED','N','N','N','N','Y','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('DEC','Declined Sale','Y','Y','UNDEFINED','N','N','Y','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('DNC','DO NOT CALL','Y','Y','UNDEFINED','N','Y','N','N','N','N','Y','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('DNCL','DO NOT CALL Hopper Sys Match','N','N','UNDEFINED','N','Y','N','N','N','N','Y','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('DNCC','DO NOT CALL Hopper Camp Match','N','N','UNDEFINED','N','Y','N','N','N','N','Y','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('SALE','Sale Made','Y','Y','UNDEFINED','Y','N','N','N','N','N','Y','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('N','No Answer','Y','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('NI','Not Interested','Y','Y','UNDEFINED','N','N','Y','Y','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('NP','No Pitch No Price','Y','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('PU','Call Picked Up','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('PM','Played Message','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('XFER','Call Transferred','Y','Y','UNDEFINED','N','N','Y','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('ERI','Agent Error','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('SVYEXT','Survey sent to Extension','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('SVYVM','Survey sent to Voicemail','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('SVYHU','Survey Hungup','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('SVYREC','Survey sent to Record','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('QVMAIL','Queue Abandon Voicemail Left','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('RQXFER','Re-Queue','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('TIMEOT','Inbound Queue Timeout Drop','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('AFTHRS','Inbound After Hours Drop','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('NANQUE','Inbound No Agent No Queue Drop','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('IQNANQ','InQueue No-Agent-No-Queue drop','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('PDROP','Outbound Pre-Routing Drop','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('IVRXFR','Outbound drop to Call Menu','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('SVYCLM','Survey sent to Call Menu','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('MLINAT','Multi-Lead auto-alt set inactv','N','Y','UNDEFINED','N','N','N','N','N','N','Y','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('MAXCAL','Inbound Max Calls Drop','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('LRERR','Outbound Local Channel Res Err','N','Y','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('QCFAIL','QC_FAIL_CALLBK','N','Y','QC','N','N','Y','N','N','Y','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('ADCT','Disconnected Number Temporary','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('LSMERG','Agent lead search old lead mrg','N','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name,selectable,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable,scheduled_callback,completed,answering_machine) values('DAIR','Dead Air','Y','N','UNDEFINED','N','N','N','N','N','N','N','N');
INSERT INTO vicidial_statuses (status,status_name) VALUES ('ADAIR', 'Dead Air Auto');

INSERT INTO vicidial_qc_codes (code,code_name,qc_result_type) VALUES ('QCPASS','PASS','PASS');
INSERT INTO vicidial_qc_codes (code,code_name,qc_result_type) VALUES ('QCFAIL','FAIL','FAIL');
INSERT INTO vicidial_qc_codes (code,code_name,qc_result_type) VALUES ('QCCANCEL','CANCEL','CANCEL');

INSERT INTO vicidial_configuration (id, name, value) VALUES (NULL, 'qc_database_version', '1638');
UPDATE vicidial_configuration set value='1766' where name='qc_database_version';

INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('AGENT_CALLBACK_EMAIL ','Scheduled callback email alert settings','OTHER','---ALL---','; sending email address\r\nemail_from => vicidial@local.server\r\n\r\n; subject of the email\r\nemail_subject => Scheduled callback alert for --A--agent_name--B--\r\n\r\nemail_body_begin => \r\nThis is a reminder that you have a scheduled callback right now for the following lead:\r\n\r\nName: --A--first_name--B-- --A--last_name--B--\r\nPhone: --A--phone_number--B--\r\nAlt. phone: --A--alt_phone--B--\r\nEmail: --A--email--B--\r\nCB Comments: --A--callback_comments--B--\r\nLead Comments: --A--comments--B--\r\n\r\nPlease don\'t respond to this, fool.\r\n\r\nemail_body_end');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('TIMEZONES_USA','USA Timezone List','TIMEZONE_LIST','---ALL---','USA,AST,N,Atlantic Time Zone\nUSA,EST,Y,Eastern Time Zone\nUSA,CST,Y,Central Time Zone\nUSA,MST,Y,Mountain Time Zone\nUSA,MST,N,Arizona Time Zone\nUSA,PST,Y,Pacific Time Zone\nUSA,AKST,Y,Alaska Time Zone\nUSA,HST,N,Hawaii Time Zone\n');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('TIMEZONES_CANADA','Canadian Timezone List','TIMEZONE_LIST','---ALL---','CAN,NST,Y,Newfoundland Time Zone\nCAN,AST,Y,Atlantic Time Zone\nCAN,EST,Y,Eastern Time Zone\nCAN,CST,Y,Central Time Zone\nCAN,CST,N,Saskatchewan Time Zone\nCAN,MST,Y,Mountain Time Zone\nCAN,MST,N,Yukon Time Zone\nCAN,PST,Y,Pacific Time Zone\n');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('TIMEZONES_AUSTRALIA','Australian Timezone List','TIMEZONE_LIST','---ALL---','AUS,AEST,Y,Eastern Australia Time Zone\nAUS,AEST,N,Queensland Time Zone\nAUS,ACST,Y,Central Australia Time Zone\nAUS,ACST,N,Northern Territory Time Zone\nAUS,AWST,N,Western Australia Time Zone\n');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('PHONE_DEFAULTS','Default phone settings for preloading','PHONE_DEFAULTS','---ALL---','# Below are all phone settings recognized under the PHONE_DEFAULTS \r\n# container type and the type of data each accepts.  Any setting that\r\n# uses a default value in the database has said value pre-set below\r\n\r\n# 10 char max\r\nvoicemail_id => \r\n \r\n# 15 char max\r\nserver_ip => \r\n\r\n# 100 char max\r\npass => \r\n\r\n# 10 char max\r\nstatus => \r\n\r\n# Y/N only\r\nactive => Y\r\n\r\n# 50 char max\r\nphone_type => \r\n\r\n# \'SIP\',\'Zap\',\'IAX2\' or \'EXTERNAL\'\r\nprotocol => SIP\r\n\r\n# positive or negatier 2-decimal floating point number\r\nlocal_gmt => -5.00\r\n\r\n# 20 char max\r\nvoicemail_dump_exten => 85026666666666\r\n\r\n# 20 char max\r\noutbound_cid => \r\n\r\n# 100 char max\r\nemail => \r\n\r\n# 15 char max\r\ntemplate_id => \r\n\r\n# text, conf_override can span multiple lines, see below\r\nconf_override => \r\n# type=friend\r\n# host=dynamic\r\n# canreinvite=no\r\n# context=default1\r\n\r\n# 50 char max\r\nphone_context => default\r\n\r\n# Unsigned - max value 65536\r\nphone_ring_timeout => 60\r\n\r\n# 20 char max\r\nconf_secret => test\r\n\r\n# Y/N only\r\ndelete_vm_after_email => N\r\n\r\n# Options - Y, N, or Y_API_LAUNCH\r\nis_webphone => N\r\n\r\n# Y/N only\r\nuse_external_server_ip => N\r\n\r\n# 100 char max\r\ncodecs_list => \r\n\r\n# 0/1 only\r\ncodecs_with_template => 0\r\n\r\n# Options - Y, N, TOGGLE, or TOGGLE_OFF\r\nwebphone_dialpad => Y\r\n\r\n# Y/N only\r\non_hook_agent => N\r\n\r\n# Y/N only\r\nwebphone_auto_answer => Y\r\n\r\n# 30 char max\r\nvoicemail_timezone => eastern\r\n\r\n# 255 char max\r\nvoicemail_options => \r\n\r\n# 20 char max\r\nuser_group => ---ALL---\r\n\r\n# 100 char max\r\nvoicemail_greeting => \r\n\r\n# 20 char max\r\nvoicemail_dump_exten_no_inst => 85026666666667\r\n\r\n# Y/N only\r\nvoicemail_instructions => Y\r\n\r\n# Y/N only\r\non_login_report => N\r\n\r\n# 40 char max\r\nunavail_dialplan_fwd_exten => \r\n\r\n# 100 char max\r\nunavail_dialplan_fwd_context => \r\n\r\n# text\r\nnva_call_url => \r\n\r\n# 40 char max\r\nnva_search_method => \r\n\r\n# 255 char max\r\nnva_error_filename => \r\n\r\n# Integer, any size\r\nnva_new_list_id => 995\r\n\r\n# 10 char max\r\nnva_new_phone_code => 1\r\n\r\n# 6 char max\r\nnva_new_status => NVAINS\r\n\r\n# Y/N only\r\nwebphone_dialbox => Y\r\n\r\n# Y/N only\r\nwebphone_mute => Y\r\n\r\n# Y/N only\r\nwebphone_volume => Y\r\n\r\n# Y/N only\r\nwebphone_debug => N\r\n\r\n# 20 char max\r\noutbound_alt_cid => \r\n\r\n# Y/N only\r\nconf_qualify => Y\r\n\r\n# 255 char max\r\nwebphone_layout => \r\n');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('QC_STATUS_TEMPLATE','Sample QC Status Template','QC_TEMPLATE','---ALL---','# These types of containers are simply used for creating a list of \r\n# QC-enabled statuses to apply to campaigns, lists, and ingroups.\r\n# Simply put all the statuses that this template should allow in\r\n# a comma-delimited string, as below:\r\n\r\nSALE,DNC,NI');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('INTERNATIONAL_DNC_IMPORT','Process DNC lists of various countries from FTP site','PERL_CLI','---ALL---','# This setting container is used for the international DNC system. \r\n# The below two settings are mandatory for importing suppression lists\r\n# and tell the import process where to look for new files and where to\r\n# move them when handled.  These settings cannot have the same value. \r\n--file-dir=/root/ftp\r\n--file-destination=/root/ftp/DONE\r\n\r\n# Uncomment below and set the status to whatever custom disposition you \r\n# would like already-loaded leads to be set to when they dedupe against\r\n# a country\'s DNC list (default is \"DNCI\")\r\n# --dnc-status-override=BMNR\r\n\r\n# The below settings are optional for when files are stored on a remote\r\n# server.  It is strongly recommended these settings are not used and\r\n# that the processing scripts and files are stored locally on the same\r\n# server. \r\n# --ftp-host=localhost\r\n# --ftp-user=user\r\n# --ftp-pwd=pwd\r\n# --ftp-port=21\r\n# --ftp-passive=1\r\n'),('DNC_IMPORT_FORMATS','Import formats for DNC files','OTHER','---ALL---','# This setting container is used for storing file formats used when \r\n# loading DNC suppression lists into the dialer. \r\n#\r\n# import template => (delimited|fixed),delimiter,phone1(,phone2,phone3)\r\n#\r\n# For delimited files, the phone1 value should be the index value of\r\n# the field where the phone appears.  The first array index is 0 and\r\n# indexes continue through the natural numbers.\r\n\r\n# In delimited files, acceptable values for the \"delimiter\" field are:\r\n# - \"tab\", \"pipe\", \"comma\", \"quote-comma\"\r\nBASIC_DELIMITED_FORMAT => delimited,pipe,0\r\n\r\n# If the phone number is split into multiple fields (ex: area code in\r\n# one field, rest of the number in another), simply list additional \r\n# indices of the phone number fields separated by commas in the order \r\n# in which the data should be combined to make the complete phone \r\n# number \r\nDELIMITED_WITH_AC_AND_EXCHANGE_SPLIT => delimited,tab,0,1\r\n\r\n# For fixed-length files, the phone field values should be of the type:\r\n# - \"starting_position|length\"\r\nBASIC_FIXED_FORMAT => fixed,,0|10\r\n\r\n# (delimited|fixed) is not used for CSV/Excel files, so all that needs \r\n# providing for those is the index field value(s) of the phone number\r\nBASIC_CSV_OR_EXCEL_FORMAT => ,,0'),('DNC_CURRENT_BLOCKED_LISTS','Lists currently blocked due to pending DNC scrub','READ_ONLY','---ALL---','');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('USER_LOCATIONS_SYSTEM','User Locations List','OTHER','---ALL---',';location|description\n|default\n');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('USER_CODES_SYSTEM','User Codes List','OTHER','---ALL---','');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('VICIPHONE_SETTINGS','VICIphone WebRTC Extra Settings','WEBPHONE_SETTINGS','---ALL---','# determines if automatic gain control is enabled\nautoGain : 0\n\n# determines if echo cancellation is enabled\nechoCan : 0\n\n# determines if noise suppression is enabled\nnoiseSup :0\n\n# determines if the reg_exten is called upon successful registration\ndialRegExten : 1\n\n# determines the regional sound to use for progress audio\nprogReg : na\n\n# English translation phrases\nlangAttempting:"Attempting"\nlangConnected:"WS Connected"\nlangDisconnected:"WS Disconnected"\nlangExten:"Extension"\nlangIncall:"Incall"\nlangInit:"Initializing..."\nlangRedirect:"Redirect"\nlangRegFailed:"Reg. Failed"\nlangRegistering:"Registering"\nlangRegistered:"Registered"\nlangReject:"Rejected"\nlangRinging:"Ringing"\nlangSend:"Send"\nlangTrying:"Trying"\nlangUnregFailed:"Unreg. Failed"\nlangUnregistered:"Unregistered"\nlangUnregistering:"Unregistering"\nlangWebrtcError:"Something went wrong with WebRTC. Either your browser does not support the necessary WebRTC functions, you did not allow your browser to access the microphone, or there is a configuration issue. Please check your browsers error console for more details. For a list of compatible browsers please vist http://webrtc.org/"');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('CONFETTI_SETTINGS', 'Confetti settings for screen display', 'OTHER', '---ALL---', '; Confetti settings, to add visual interest to certain events\r\n; duration is how long the confetti animation runs, maxParticleCount is the\r\n; max number of confetti \"pieces\", and particleSpeed is how fast they float\r\nduration => 2\r\nmaxParticleCount => 2350\r\nparticleSpeed => 2\r\n');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('HOPPER_CLI_FLAGS', 'Comand-line flags for hopper process', 'PERL_CLI', '---ALL---', '');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('AGENT_LATENCY_LOGGING','Default agent latency logging settings','PERL_CLI','---ALL---','minimum_gap => 30\r\nemail_sender => \r\nemail_list => \r\nemail_subject => Agent Network Alert');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('EXAMPLE_LIST_WEEKDAY_RESETS','Example Weekday List Resets Container settings','LIST_WEEKDAY_RESETS','---ALL---','; weekday => reset-times in 24-hour time separated by dashes\r\nmonday => 0830-1230-1800\r\ntuesday => 0900-1400-1830\r\nwednesday => 0930-1200-1730-1900\r\nthursday => 1030-1330-1900\r\nfriday => 0800-1300-1530\r\nsaturday => 0930-1100\r\nsunday => 1000');
INSERT INTO vicidial_settings_containers(container_id,container_notes,container_type,user_group,container_entry) VALUES ('EXAMPLE_VID_PROMPT_SPECIAL','Example Call Menu VID Special Container settings','CM_VIDPROMPT_SPECIAL','---ALL---','list_ingroup => 102,TEST_IN2\r\nlist_ingroup => 103,TEST_IN3\r\nlist_ingroup => 104,TEST_IN4\r\ndefault_ingroup => TEST_IN5\r\nnot_found_action => new_lead\r\nnew_lead_ingroup => TEST_IN');
INSERT INTO `vicidial_settings_containers` VALUES ('VERM_STATUS_NAMES_OVERRIDE','Override dialer status names in enhanced reporting','OTHER','---ALL---','; For each status name you want overridden, type the status followed by\r\n; a pipe, then the new status name\r\n; Ex:\r\n; NZ|Taumatawhakatangihangakoauauotamateaturipukakapikimaungahoronukupoka\r\n201214|Request To Cancel\r\n210200|No Answer-Incomplete Call\r\n210201|Contact Established\r\n210202|Provider Review - HB\r\n210203|Promise to Pay\r\n210204|Setup Payment Plan\r\n210205|Research-Inquiry\r\n210206|Voice Mail Left - HB\r\n210207|Do Not Call\r\n210208|Appeal Verification\r\n210209|Bad Phone\r\n210210|Bad Address\r\n210211|Direct Pay Verification\r\n210213|Provider Approved\r\n210215|Update Notes Only\r\n210216|Voicemail-No Status Change\r\n210217|Sent Letter Request - HB\r\n210218|Auto VoiceMail Left - HB\r\n210219|Auto VoiceMail-No Status Change\r\n210302|Provider Review - LB\r\n210306|Voice Mail Left - LB\r\n210317|Sent Letter Request - LB\r\n210318|Auto VoiceMail Left - LB\r\n211503|Provider - COVID-19\r\n211603|Transferred Call to MLA\r\n'), ('VERM_REPORT_OPTIONS','Container for customizing VERM report output','OTHER','---ALL---','; This is the report queue used if none is chosen by the user\r\n; It\'s preloaded in some forms as well\r\nVERM_default_report_queue => ALL\r\n\r\n; If there are statuses to exclude from reports, list them here\r\n; Separate with commas.  Default is AFTHRS\r\nexc_addtl_statuses => AFTHRS\r\n\r\n; Set the below value to 1 (or anything non-blank/non-zero) in order to \r\n; show the agents ID in addition to their full name in the report results\r\nshow_full_agent_info => 1\r\n\r\n; Some reports count \"lost\" calls - which are defined by the below variable\r\n; listing what you define as \"lost\" dispos.  Separate with commas.\r\nlost_statuses => LOST,210208,DISPO\r\n\r\n; You can create a detailed IVR survey report for ingroups by defining\r\n; \"ivr_survey_ingroups_detail\" and \"ivr_survey_ingroups_voicemails.\"\r\n; For \"details\", supply an ingroup used as a tracking group on call menus.\r\n; Then, add a pipe and after that list all call menus that use the ingroup\r\n; as the tracking group, separating each with a comma\r\n; To track whether the calls went to voicemail, list every call menu/option\r\n; combination that goes to voicemail, separating the call menu from the \r\n; option with a pipe.  One callmenu/option combo per line.\r\nivr_survey_ingroups_detail => 521205|561401,561402,561403,561404,561505\r\nivr_survey_ingroups_voicemails => 561505|t\r\n\r\n; #####################################################\r\n; # ALL of the below are used in the wallboard report #\r\n; #####################################################\r\nVERM_default_outb_widget_queue => ALL_OUT\r\nVERM_default_inb_widget_queue1 => 514915v_USA_Shared\r\nVERM_default_inb_widget_queue2 => 515915v_MLA_Shared\r\n\r\n; Used specifically for the SLA widget\r\n; Uses ingroups - separate multiple ingroups by commas\r\n; Comment out or leave blank to count all ingroups\r\nSLA_LEVEL_PCT_ingroups => 514915v,515915v\r\n\r\n; This removes remote agents from the wallboard reports\r\n; Comment out to include remote agents (or set to zero)\r\nomit_remote_agents => 1\r\n\r\n\r\n; #### AUTO DOWNLOAD ####\r\n; If the "total calls" value on any report requested exceeds the below \r\n; limit, automatically download the three "DETAILS" reports instead\r\n; of attempting to display that many records on-screen\r\nauto_download_limit => 50000\r\n\r\n; #### OUTCOMES report overrides ####\r\n; Use "outcome_lagged_status_overrides" for conditions where the call \r\n; record in the vicidial_log or vicidial_closer_log table has no uniqueid\r\n; value despite having a status/outcome, which can indicate a call \r\n; affected by network lag for certain statuses.  This will change the call \r\n; status to "LAGGED".  Separate statuses with commas.  Default is the \r\n; automatic "PU" status.\r\noutcome_lagged_status_overrides => PU\r\n\r\n; Use "unknown_network_statuses" to change call statuses to read "Network/\r\n; LAGGED" on the OUTCOMES report. Separate statuses with commas.\r\n; IMPORTANT: if you are using the outcome_lagged_status_overrides option \r\n; above, make sure "LAGGED" is one of the unknown_network_statuses here\r\n; unknown_network_statuses => LAGGED\r\n\r\n; Use "outcome_status_overrides" to change one status to another on the \r\n; OUTCOMES report.  Overrides are comma-separated pairs of dispositions  \r\n; where the first disposition is the disposition to change, and the second\r\n; is the disposition to change to.  Separate pairs with a pipe character as\r\n; in the below example.  Off by default.\r\n; outcome_status_overrides => CBHOLD,DISPO|XFER,AL');
INSERT INTO vicidial_settings_containers VALUES ('VICIDIAL_TIMEOFF_SETTINGS','Settings for time-off admin utility','OTHER','---ALL---','; Comma-delimited time-off codes - MANDATORY, must have at least one defined. \r\n; Default is \'VAC\' for vacation\r\ntimeoff_types => VAC\r\n\r\n; optional, if set to \'1\' will show all viewable agents, even ones with no \r\n; time off for month.  Default is 0\r\ndisplay_all_agents => 0\r\n\r\n; optional, used to filter users displayed, in addition to user_group \r\n; permissions\r\nuser_filter_SQL =>  \r\n\r\n; optional, uses columns from vicidial_users table.   Defaults to \r\n; full_name asc, user asc if commented out or non-existent\r\n; sort_SQL => full_name asc, user asc\r\n\r\n; include custom coding for misc download coding where \"custom_download\" \r\n; marked in agent_timeoff_script.php - DO NOT USE WITHOUT CODING KNOWLEDGE\r\n; set to \'1\' to activate\r\ncustom_download => 0');

INSERT INTO `wallboard_widgets` VALUES ('queues_widget_1','AGENTS_AND_QUEUES','queues','','TEXT',5,'N',1,'Queue Information','','',NULL,'','',NULL,2),('queues_widget_0','AGENTS_AND_QUEUES','queues','','LOGO',2,'N',1,NULL,'','',NULL,'','',NULL,1),('queues_widget_2','AGENTS_AND_QUEUES','queues','SLA Level %','SLA_LEVEL_PCT',1,'N',1,NULL,'','>60',NULL,'','',NULL,3),('queues_widget_3','AGENTS_AND_QUEUES','queues','Outbound calls','LIVE_QUEUE_INFO',1,'N',1,'','201201','','','','','yellow_alarm,|red_alarm,',4),('queues_widget_4','AGENTS_AND_QUEUES','queues','USA Ded Inbound','LIVE_QUEUE_INFO',1,'N',1,'','ALL_IN','','','','','yellow_alarm,|red_alarm,',5),('queues_widget_5','AGENTS_AND_QUEUES','queues','MLA Ded Inbound','LIVE_QUEUE_INFO',1,'N',1,'','514911','','','','','yellow_alarm,|red_alarm,',6),('queues_widget_6','AGENTS_AND_QUEUES','queues','N Waiting Calls','N_WAITING_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,7),('queues_widget_7','AGENTS_AND_QUEUES','queues','Offered Calls','OFFERED_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,8),('queues_widget_8','AGENTS_AND_QUEUES','queues','Answered Calls','ANSWERED_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,9),('queues_widget_9','AGENTS_AND_QUEUES','queues','Lost Calls','LOST_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,10),('queues_widget_10','AGENTS_AND_QUEUES','queues','Longest Wait','LONGEST_WAIT',1,'N',1,NULL,'','',NULL,'','',NULL,11),('queues_widget_11','AGENTS_AND_QUEUES','queues','Live Queues','LIVE_QUEUES',1,'Y',1,NULL,'','',NULL,'','',NULL,12),('queues_widget_12','AGENTS_AND_QUEUES','queues','Live Calls','LIVE_CALLS',1,'Y',2,NULL,'','',NULL,'','',NULL,13),('agent_widget_0','AGENTS_AND_QUEUES','agents','','LOGO',2,'N',1,NULL,'','',NULL,'','',NULL,1),('agent_widget_1','AGENTS_AND_QUEUES','agents','N Waiting Calls','N_WAITING_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,2),('agent_widget_2','AGENTS_AND_QUEUES','agents','Agents Ready','AGENTS_READY',1,'N',1,NULL,'','',NULL,'','',NULL,3),('agent_widget_3','AGENTS_AND_QUEUES','agents','Agents On Call','N_AGENTS_ON_CALL',1,'N',1,NULL,'','',NULL,'','',NULL,4),('agent_widget_4','AGENTS_AND_QUEUES','agents','N Answered Calls','N_ANSWERED_CALLS',1,'N',1,NULL,'','',NULL,'','',NULL,5),('agent_widget_5','AGENTS_AND_QUEUES','agents','Clock','CLOCK',1,'N',1,NULL,'','',NULL,'','',NULL,6),('agent_widget_6','AGENTS_AND_QUEUES','agents','Live Agents','LIVE_AGENTS',1,'Y',3,NULL,'','',NULL,'','',NULL,7);

INSERT INTO `wallboard_reports` VALUES ('AGENTS_AND_QUEUES','Agents and Queues',2,'2022-01-18 09:00:23','2022-01-18 15:00:23',10,30);

UPDATE system_settings set vdc_agent_api_active='1';

UPDATE system_settings SET db_schema_version='1720',db_schema_update_date=NOW(),reload_timestamp=NOW();
