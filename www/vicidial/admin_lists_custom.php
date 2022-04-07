<?php
# admin_lists_custom.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen manages the custom lists fields in ViciDial
#
# changes:
# 100506-1801 - First Build
# 100507-1027 - Added name position and options position, added extra space for name and help
# 100508-1855 - Added field_order to allow for multiple fields on the same line
# 100509-0922 - Added copy fields options
# 100510-1130 - Added DISPLAY field type option
# 100629-0200 - Added SCRIPT field type option
# 100722-1313 - Added field validation for label and name
# 100728-1724 - Added field validation for select lists and checkbox/radio buttons
# 100916-1754 - Do not show help in example form if help is empty
# 101228-2049 - Fixed missing PHP long tag
# 110629-1438 - Fixed change from DISPLAY or SCRIPT to other field type error, added HIDDEN and READONLY field types
# 110719-0910 - Added HIDEBLOB field type
# 110730-1106 - Added mysql reserved words check for add-field action
# 111025-1432 - Fixed case sensitivity on list fields
# 120122-1349 - Force vicidial_list custom field labels to be all lower case
# 120223-2315 - Removed logging of good login passwords if webroot writable is enabled
# 120713-2101 - Added extended_vl_fields option
# 120907-1209 - Raised extended fields up to 99
# 130508-1020 - Added default field and length check validation, made errors appear in bold red text
# 130606-0545 - Finalized changing of all ereg instances to preg
# 130621-1736 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0752 - Changed to mysqli PHP functions
# 140705-0811 - Added better error handling, hid field_required field since it is non-functional
# 140811-2110 - Fixes for issues with default fields
# 141006-0903 - Finalized adding QXZ translation to all admin files
# 141230-0018 - Added code for on-the-fly language translations display
# 150626-2120 - Modified mysqli_error() to mysqli_connect_error() where appropriate
# 150807-2233 - Added enc options
# 151002-0650 - Fixed issue with non-enc field types
# 151007-2001 - Fixed issue with field deletion
# 160325-1431 - Changes for sidebar update
# 160404-0938 - design changes
# 160414-1244 - Fixed translation issue with COPY form
# 160429-1121 - Added admin_row_click option
# 160508-0219 - Added screen colors feature
# 160510-2109 - Fixing issues with using only standard fields
# 170228-2255 - Changes to allow URLs in SCRIPT field types
# 170301-0828 - Enabled required custom fields with INBOUND_ONLY option
# 170321-1554 - Fixed list view permissions issue #1005
# 170409-1558 - Added IP List validation code
# 171116-1544 - Added option for duplicate custom field entries(field_duplicate)
# 180123-1817 - cleanup of enc code
# 180125-1733 - Added more reserved words from MySQL/MariaDB
# 180316-0754 - Translated phrases fixes, issue #1081
# 180502-2215 - Added new help display
# 180504-1807 - Added new SWITCH field type
# 191013-1014 - Fixes for PHP7
# 210211-0032 - Added SOURCESELECT field type
# 210304-2039 - Added option to "re-rank" field ranks when adding/updating a field in the middle of the form
# 210311-2338 - Added BUTTON field type and 2FA
# 220217-2004 - Added input variable filtering
# 220221-0910 - Added allow_web_debug system setting
#

$admin_version = '2.14-49';
$build = '220221-0910';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["action"]))						{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))			{$action=$_POST["action"];}
if (isset($_GET["list_id"]))					{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))			{$list_id=$_POST["list_id"];}
if (isset($_GET["field_id"]))					{$field_id=$_GET["field_id"];}
	elseif (isset($_POST["field_id"]))			{$field_id=$_POST["field_id"];}
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
if (isset($_GET["field_cost"]))					{$field_cost=$_GET["field_cost"];}
	elseif (isset($_POST["field_cost"]))		{$field_cost=$_POST["field_cost"];}
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
if (isset($_GET["source_list_id"]))				{$source_list_id=$_GET["source_list_id"];}
	elseif (isset($_POST["source_list_id"]))	{$source_list_id=$_POST["source_list_id"];}
if (isset($_GET["copy_option"]))				{$copy_option=$_GET["copy_option"];}
	elseif (isset($_POST["copy_option"]))		{$copy_option=$_POST["copy_option"];}
if (isset($_GET["ConFiRm"]))					{$ConFiRm=$_GET["ConFiRm"];}
	elseif (isset($_POST["ConFiRm"]))			{$ConFiRm=$_POST["ConFiRm"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["field_rerank"]))				{$field_rerank=$_GET["field_rerank"];}
	elseif (isset($_POST["field_rerank"]))		{$field_rerank=$_POST["field_rerank"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,user_territories_active,custom_fields_enabled,enable_languages,language_method,active_modules,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$user_territories_active =		$row[3];
	$SScustom_fields_enabled =		$row[4];
	$SSenable_languages =			$row[5];
	$SSlanguage_method =			$row[6];
	$SSactive_modules =				$row[7];
	$SSallow_web_debug =			$row[8];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

if ( (strlen($action) < 2) and ($list_id > 99) )
	{$action = 'MODIFY_CUSTOM_FIELDS';}
if (strlen($action) < 2)
	{$action = 'LIST';}
if (strlen($DB) < 1)
	{$DB=0;}
if ($field_size > 100)
	{$field_size = 100;}
if ( (strlen($field_size) < 1) or ($field_size < 1) )
	{$field_size = 1;}
if ( (strlen($field_max) < 1) or ($field_max < 1) )
	{$field_max = 1;}

$list_id = preg_replace('/[^0-9]/','',$list_id);
$field_id = preg_replace('/[^0-9]/','',$field_id);
$field_rank = preg_replace('/[^0-9]/','',$field_rank);
$field_size = preg_replace('/[^0-9]/','',$field_size);
$field_max = preg_replace('/[^0-9]/','',$field_max);
$field_order = preg_replace('/[^0-9]/','',$field_order);
$source_list_id = preg_replace('/[^0-9]/','',$source_list_id);
$field_rerank = preg_replace('/[^_0-9a-zA-Z]/','',$field_rerank);
$field_cost = preg_replace('/[^_0-9a-zA-Z]/','',$field_cost);
$action = preg_replace('/[^-_0-9a-zA-Z]/','',$action);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/','',$SUBMIT);
$field_encrypt = preg_replace('/[^NY]/','',$field_encrypt);
$field_required = preg_replace('/[^_A-Z]/','',$field_required);
$field_duplicate = preg_replace('/[^_A-Z]/','',$field_duplicate);
$field_type = preg_replace('/[^0-9a-zA-Z]/','',$field_type);
$ConFiRm = preg_replace('/[^0-9a-zA-Z]/','',$ConFiRm);
$name_position = preg_replace('/[^0-9a-zA-Z]/','',$name_position);
$multi_position = preg_replace('/[^0-9a-zA-Z]/','',$multi_position);
$copy_option = preg_replace('/[^_0-9a-zA-Z]/','',$copy_option);
$field_show_hide = preg_replace('/[^_0-9a-zA-Z]/','',$field_show_hide);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$field_label = preg_replace('/[^_0-9a-zA-Z]/','',$field_label);
	$field_name = preg_replace('/[^ \.\,-\_0-9a-zA-Z]/','',$field_name);
	$field_description = preg_replace('/[^ \.\,-\_0-9a-zA-Z]/','',$field_description);
	$field_options = preg_replace('/[^ \'\&\.\n\|\,-\_0-9a-zA-Z]/', '',$field_options);
	if ($field_type != 'SCRIPT')
		{$field_options = preg_replace('/[^ \.\n\|\,-\_0-9a-zA-Z]/', '',$field_options);}
	$field_help = preg_replace('/[^ \'\&\!\?\.\n\|\,-\_0-9a-zA-Z]/', '',$field_help);
	$field_default = preg_replace('/[^ \.\n\,-\_0-9a-zA-Z]/', '',$field_default);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u','',$PHP_AUTH_PW);
	$field_label = preg_replace('/[^_0-9\p{L}]/u','',$field_label);
	$field_name = preg_replace('/[^ \.\,-\_0-9\p{L}]/u','',$field_name);
	$field_description = preg_replace('/[^ \.\,-\_0-9\p{L}]/u','',$field_description);
	$field_options = preg_replace('/[^ \'\&\.\n\|\,-\_0-9\p{L}]/u', '',$field_options);
	if ($field_type != 'SCRIPT')
		{$field_options = preg_replace('/[^ \.\n\|\,-\_0-9\p{L}]/u', '',$field_options);}
	$field_help = preg_replace('/[^ \'\&\!\?\.\n\|\,-\_0-9\p{L}]/u', '',$field_help);
	$field_default = preg_replace('/[^ \.\n\,-\_0-9\p{L}]/u', '',$field_default);
	}


$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$user = $PHP_AUTH_USER;

if (file_exists('options.php'))
	{require('options.php');}

$vicidial_list_fields = '|lead_id|vendor_lead_code|source_id|list_id|gmt_offset_now|called_since_last_reset|phone_code|phone_number|title|first_name|middle_initial|last_name|address1|address2|address3|city|state|province|postal_code|country_code|gender|date_of_birth|alt_phone|email|security_phrase|comments|called_count|last_local_call_time|rank|owner|status|entry_date|entry_list_id|modify_date|user|';

if ($extended_vl_fields > 0)
	{
	$vicidial_list_fields = '|lead_id|vendor_lead_code|source_id|list_id|gmt_offset_now|called_since_last_reset|phone_code|phone_number|title|first_name|middle_initial|last_name|address1|address2|address3|city|state|province|postal_code|country_code|gender|date_of_birth|alt_phone|email|security_phrase|comments|called_count|last_local_call_time|rank|owner|status|entry_date|entry_list_id|modify_date|user|q01|q02|q03|q04|q05|q06|q07|q08|q09|q10|q11|q12|q13|q14|q15|q16|q17|q18|q19|q20|q21|q22|q23|q24|q25|q26|q27|q28|q29|q30|q31|q32|q33|q34|q35|q36|q37|q38|q39|q40|q41|q42|q43|q44|q45|q46|q47|q48|q49|q50|q51|q52|q53|q54|q55|q56|q57|q58|q59|q60|q61|q62|q63|q64|q65|q66|q67|q68|q69|q70|q71|q72|q73|q74|q75|q76|q77|q78|q79|q80|q81|q82|q83|q84|q85|q86|q87|q88|q89|q90|q91|q92|q93|q94|q95|q96|q97|q98|q99|';
	}

$mysql_reserved_words = '|accessible|action|add|after|against|aggregate|algorithm|all|alter|analyse|analyze|and|any|as|asc|ascii|asensitive|at|authors|auto_increment|autoextend_size|avg|avg_row_length|backup|before|begin|between|bigint|binary|binlog|bit|blob|block|bool|boolean|both|btree|by|byte|cache|call|cascade|cascaded|case|catalog_name|chain|change|changed|char|character|charset|check|checksum|cipher|class_origin|client|close|coalesce|code|collate|collation|column|column_format|column_name|columns|comment|commit|committed|compact|completion|compressed|concurrent|condition|connection|consistent|constraint|constraint_catalog|constraint_name|constraint_schema|contains|context|continue|contributors|convert|cpu|create|cross|cube|current|current_date|current_time|current_timestamp|current_user|cursor|cursor_name|data|database|databases|datafile|date|datetime|day|day_hour|day_microsecond|day_minute|day_second|deallocate|dec|decimal|declare|default|default_auth|definer|delay_key_write|delayed|delete|des_key_file|desc|describe|deterministic|diagnostics|directory|disable|discard|disk|distinct|distinctrow|div|do|double|drop|dual|dumpfile|duplicate|dynamic|each|else|elseif|enable|enclosed|end|ends|engine|engines|enum|error|errors|escape|escaped|event|events|every|exchange|execute|exists|exit|expansion|expire|explain|export|extended|extent_size|false|fast|faults|fetch|fields|file|first|fixed|float|float4|float8|flush|for|force|foreign|format|found|from|full|fulltext|function|general|geometry|geometrycollection|get|get_format|global|grant|grants|group|handler|hash|having|help|high_priority|host|hosts|hour|hour_microsecond|hour_minute|hour_second|identified|if|ignore|ignore_server_ids|import|in|index|indexes|infile|initial_size|inner|inout|insensitive|insert|insert_method|install|int|int1|int2|int3|int4|int8|integer|interval|into|invoker|io|io_after_gtids|io_before_gtids|io_thread|ipc|is|isolation|issuer|iterate|join|key|key_block_size|keys|kill|language|last|leading|leave|leaves|left|less|level|like|limit|linear|lines|linestring|list|load|local|localtime|localtimestamp|lock|locks|logfile|logs|long|longblob|longtext|loop|low_priority|master|master_auto_position|master_bind|master_connect_retry|master_delay|master_heartbeat_period|master_host|master_log_file|master_log_pos|master_password|master_port|master_retry_count|master_server_id|master_ssl|master_ssl_ca|master_ssl_capath|master_ssl_cert|master_ssl_cipher|master_ssl_crl|master_ssl_crlpath|master_ssl_key|master_ssl_verify_server_cert|master_user|match|max_connections_per_hour|max_queries_per_hour|max_rows|max_size|max_updates_per_hour|max_user_connections|maxvalue|medium|mediumblob|mediumint|mediumtext|memory|merge|message_text|microsecond|middleint|migrate|min_rows|minute|minute_microsecond|minute_second|mod|mode|modifies|modify|month|multilinestring|multipoint|multipolygon|mutex|mysql|mysql_errno|name|names|national|natural|nchar|ndb|ndbcluster|new|next|no|no_wait|no_write_to_binlog|nodegroup|none|not|null|number|numeric|nvarchar|offset|old_password|on|one|one_shot|only|open|optimize|option|optionally|options|or|order|out|outer|outfile|owner|pack_keys|page|parser|partial|partition|partitioning|partitions|password|phase|plugin|plugin_dir|plugins|point|polygon|port|precision|prepare|preserve|prev|primary|privileges|procedure|processlist|profile|profiles|proxy|purge|quarter|query|quick|range|read|read_only|read_write|reads|real|rebuild|recover|recursive|redo_buffer_size|redofile|redundant|references|regexp|relay|relay_log_file|relay_log_pos|relay_thread|relaylog|release|reload|remove|rename|reorganize|repair|repeat|repeatable|replace|replication|require|reset|resignal|restore|restrict|resume|return|returned_sqlstate|returned_sqlstate|returning|returns|reverse|revoke|right|rlike|rollback|rollup|routine|row|row_count|row_count|row_format|rows|rtree|savepoint|schedule|schema|schema_name|schemas|second|second_microsecond|security|select|sensitive|separator|serial|serializable|server|session|set|share|show|shutdown|signal|signed|simple|slave|slow|slow|smallint|snapshot|socket|some|soname|sounds|source|spatial|specific|sql|sql_after_gtids|sql_after_gtids|sql_after_mts_gaps|sql_after_mts_gaps|sql_before_gtids|sql_before_gtids|sql_big_result|sql_buffer_result|sql_cache|sql_calc_found_rows|sql_no_cache|sql_small_result|sql_thread|sql_tsi_day|sql_tsi_hour|sql_tsi_minute|sql_tsi_month|sql_tsi_quarter|sql_tsi_second|sql_tsi_week|sql_tsi_year|sqlexception|sqlstate|sqlwarning|ssl|start|starting|starts|stats_auto_recalc|stats_auto_recalc|stats_persistent|stats_persistent|stats_sample_pages|stats_sample_pages|status|stop|storage|straight_join|string|subclass_origin|subject|subpartition|subpartitions|super|suspend|swaps|switches|table|table_checksum|table_name|tables|tablespace|temporary|temptable|terminated|text|than|then|time|timestamp|timestampadd|timestampdiff|tinyblob|tinyint|tinytext|to|trailing|transaction|trigger|triggers|true|truncate|type|types|uncommitted|undefined|undo|undo_buffer_size|undofile|unicode|uninstall|union|unique|unknown|unlock|unsigned|until|update|upgrade|usage|use|use_frm|user_resources|using|utc_date|utc_time|utc_timestamp|value|values|varbinary|varchar|varcharacter|variables|varying|view|wait|warnings|week|weight_string|when|where|while|window|with|work|wrapper|write|x509|xa|xml|xor|year|year_month|zerofill|lead_id|user|';

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

if ($auth < 1)
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

$rights_stmt = "SELECT modify_leads from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$modify_leads =		$rights_row[0];

# check their permissions
if ( $modify_leads < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify leads")."\n";
	exit;
	}

$stmt="SELECT full_name,modify_leads,custom_fields_modify,user_level,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGmodify_leads =			$row[1];
$LOGcustom_fields_modify =	$row[2];
$LOGuser_level =			$row[3];
$LOGuser_group =			$row[4];

$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
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
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

# check if cf_enrcypt enabled
$hide_enc=0;
if (!preg_match("/cf_encrypt/",$SSactive_modules))
	{
	$hide_enc=1;
	}

?>
<html>
<head>

<?php
if ($action != "HELP")
	{
?>
<script language="JavaScript" src="calendar_db.js"></script>
<link rel="stylesheet" href="calendar.css">

<script language="Javascript">
function open_help(taskspan,taskhelp) 
	{
	document.getElementById("P_" + taskspan).innerHTML = " &nbsp; <a href=\"javascript:close_help('" + taskspan + "','" + taskhelp + "');\">help-</a><BR> &nbsp; ";
	document.getElementById(taskspan).innerHTML = "<B>" + taskhelp + "</B>";
	document.getElementById(taskspan).style.background = "#FFFF99";
	}
function close_help(taskspan,taskhelp) 
	{
	document.getElementById("P_" + taskspan).innerHTML = "";
	document.getElementById(taskspan).innerHTML = " &nbsp; <a href=\"javascript:open_help('" + taskspan + "','" + taskhelp + "');\">help+</a>";
	document.getElementById(taskspan).style.background = "white";
	}
</script>

<?php
	}
?>

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: Lists Custom Fields"); ?>
<?php 

################################################################################
##### BEGIN help section
if ($action == "HELP")
	{
	?>
	</title>
	</head>
	<body bgcolor=white>
	<center>
	<TABLE WIDTH=98% BGCOLOR=#E6E6E6 cellpadding=2 cellspacing=4><TR><TD ALIGN=LEFT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>
	<BR>
	<?php echo _QXZ("HELP HAS MOVED to"); ?> help.php
	</TD></TR></TABLE>
	</BODY>
	</HTML>
	<?php
	exit;
	}
### END help section





##### BEGIN Set variables to make header show properly #####
$ADD =					'100';
$hh =					'lists';
$sh =					'custom';
$LOGast_admin_access =	'1';
$SSoutbound_autodial_active = '1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$lists_color =		'#FFFF99';
$lists_font =		'BLACK';
$lists_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
$Msubhead_color =	'#E6E6E6';
$Mselected_color =	'#C6C6C6';
$Mhead_color =		'#A3C3D6';
$Mmain_bgcolor =	'#015B91';
##### END Set variables to make header show properly #####

require("admin_header.php");

if ( ($LOGcustom_fields_modify < 1) or ($LOGuser_level < 8) )
	{
	echo _QXZ("You are not authorized to view this section")."\n";
	exit;
	}

if ($SScustom_fields_enabled < 1)
	{
	echo "<B><font color=red>"._QXZ("ERROR: Custom Fields are not active on this system")."</B></font>\n";
	exit;
	}


# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.gif\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";


if ($DB > 0)
{
echo "$DB,$action,$ip,$user,$copy_option,$field_id,$list_id,$source_list_id,$field_label,$field_name,$field_description,$field_rank,$field_help,$field_type,$field_options,$field_size,$field_max,$field_default,$field_required,$field_cost,$multi_position,$name_position,$field_order,$field_encrypt,$field_show_hide,$field_duplicate";
}





################################################################################
##### BEGIN copy fields to a list form
if ($action == "COPY_FIELDS_FORM")
	{
	##### get lists listing for dynamic pulldown
	$stmt="SELECT list_id,list_name from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
	$rsltx=mysql_to_mysqli($stmt, $link);
	$lists_to_print = mysqli_num_rows($rsltx);
	$lists_list='';
	$o=0;
	while ($lists_to_print > $o)
		{
		$rowx=mysqli_fetch_row($rsltx);
		$lists_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
		$o++;
		}

	echo "<TABLE><TR><TD>\n";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
	echo "<br>"._QXZ("Copy Fields to Another List")."<form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=hidden name=action value=COPY_FIELDS_SUBMIT>\n";
	echo "<center><TABLE width=$section_width cellspacing=3>\n";
	echo "<tr bgcolor=#$SSstd_row4_background><td align=right>"._QXZ("List ID to Copy Fields From").": </td><td align=left><select size=1 name=source_list_id>\n";
	echo "$lists_list";
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row4_background><td align=right>"._QXZ("List ID to Copy Fields to").": </td><td align=left><select size=1 name=list_id>\n";
	echo "$lists_list";
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row4_background><td align=right>"._QXZ("Copy Option").": </td><td align=left><select size=1 name=copy_option>\n";
	echo "<option value=\"APPEND\" selected>"._QXZ("APPEND")."</option>";
	echo "<option value=\"UPDATE\">"._QXZ("UPDATE")."</option>";
	echo "<option value=\"REPLACE\">"._QXZ("REPLACE")."</option>";
	echo "</select> $NWB#lists_fields-copy_option$NWE</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row4_background><td align=center colspan=2><input type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'></td></tr>\n";
	echo "</TABLE></center>\n";
	echo "</TD></TR></TABLE>\n";
	}
### END copy fields to a list form




################################################################################
##### BEGIN copy list fields submit
if ( ($action == "COPY_FIELDS_SUBMIT") and ($list_id > 99) and ($source_list_id > 99) and (strlen($copy_option) > 2) )
	{
	if ($list_id=="$source_list_id")
		{echo "<B><font color=red>"._QXZ("ERROR: You cannot copy fields to the same list").": $list_id|$source_list_id</B></font>\n<BR>";}
	else
		{
		$table_exists=0;
		#$linkCUSTOM=mysql_connect("$VARDB_server:$VARDB_port", "$VARDB_custom_user","$VARDB_custom_pass");

		$linkCUSTOM=mysqli_connect("$VARDB_server", "$VARDB_custom_user", "$VARDB_custom_pass", "$VARDB_database", "$VARDB_port");
		if (!$linkCUSTOM) 
			{
			die('MySQL '._QXZ("connect ERROR").': '. mysqli_connect_error());
			}

		# if (!$linkCUSTOM) {die("Could not connect: $VARDB_server|$VARDB_port|$VARDB_database|$VARDB_custom_user|$VARDB_custom_pass" . mysqli_error());}
		#mysql_select_db("$VARDB_database", $linkCUSTOM);

		$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$source_list_id';";
		if ($DB>0) {echo "$stmt";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$fieldscount_to_print = mysqli_num_rows($rslt);
		if ($fieldscount_to_print > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$source_field_exists =	$rowx[0];
			}
		
		$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id';";
		if ($DB>0) {echo "$stmt";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$fieldscount_to_print = mysqli_num_rows($rslt);
		if ($fieldscount_to_print > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$field_exists =	$rowx[0];
			}
		
		$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
		$rslt=mysql_to_mysqli($stmt, $link);
		$tablecount_to_print = mysqli_num_rows($rslt);
		if ($tablecount_to_print > 0) 
			{$table_exists =	1;}
		if ($DB>0) {echo "$stmt|$tablecount_to_print|$table_exists";}
		
		if ($source_field_exists < 1)
			{echo "<B><font color=red>"._QXZ("ERROR: Source list has no custom fields")."</B></font>\n<BR>";}
		else
			{
			##### REPLACE option #####
			if ($copy_option=='REPLACE')
				{
				if ($DB > 0) {echo _QXZ("Starting REPLACE copy")."\n<BR>";}
				if ($table_exists > 0)
					{
					$stmt="SELECT field_id,field_label,field_duplicate,field_type from vicidial_lists_fields where list_id='$list_id' order by field_rank,field_order,field_label;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$fields_to_print = mysqli_num_rows($rslt);
					$fields_list='';
					$o=0;
					while ($fields_to_print > $o) 
						{
						$rowx=mysqli_fetch_row($rslt);
						$A_field_id[$o] =			$rowx[0];
						$A_field_label[$o] =		$rowx[1];
						$A_field_duplicate[$o] =	$rowx[2];
						$A_field_type[$o] =			$rowx[3];
						$o++;
						}

					$o=0;
					while ($fields_to_print > $o) 
						{
						### delete field function
						$SQLsuccess = delete_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$A_field_id[$o],$list_id,$A_field_label[$o],$A_field_name[$o],$A_field_description[$o],$A_field_rank[$o],$A_field_help[$o],$A_field_type[$o],$A_field_options[$o],$A_field_size[$o],$A_field_max[$o],$A_field_default[$o],$A_field_required[$o],$A_field_cost[$o],$A_multi_position[$o],$A_name_position[$o],$A_field_order[$o],$A_field_encrypt[$o],$A_field_show_hide[$o],$A_field_duplicate[$o],$vicidial_list_fields);

						if ($SQLsuccess > 0)
							{echo _QXZ("SUCCESS: Custom Field Deleted")." - $list_id|$A_field_label[$o]\n<BR>";}
						$o++;
						}
					}
				$copy_option='APPEND';
				}
			##### APPEND option #####
			if ($copy_option=='APPEND')
				{
				if ($DB > 0) {echo _QXZ("Starting APPEND copy")."\n<BR>";}
				$stmt="SELECT field_id,field_label,field_name,field_description,field_rank,field_help,field_type,field_options,field_size,field_max,field_default,field_cost,field_required,multi_position,name_position,field_order,field_encrypt,field_show_hide,field_duplicate from vicidial_lists_fields where list_id='$source_list_id' order by field_rank,field_order,field_label;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$fields_to_print = mysqli_num_rows($rslt);
				$fields_list='';
				$o=0;
				while ($fields_to_print > $o) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$A_field_id[$o] =			$rowx[0];
					$A_field_label[$o] =		$rowx[1];
					$A_field_name[$o] =			$rowx[2];
					$A_field_description[$o] =	$rowx[3];
					$A_field_rank[$o] =			$rowx[4];
					$A_field_help[$o] =			$rowx[5];
					$A_field_type[$o] =			$rowx[6];
					$A_field_options[$o] =		$rowx[7];
					$A_field_size[$o] =			$rowx[8];
					$A_field_max[$o] =			$rowx[9];
					$A_field_default[$o] =		$rowx[10];
					$A_field_cost[$o] =			$rowx[11];
					$A_field_required[$o] =		$rowx[12];
					$A_multi_position[$o] =		$rowx[13];
					$A_name_position[$o] =		$rowx[14];
					$A_field_order[$o] =		$rowx[15];
					$A_field_encrypt[$o] =		$rowx[16];
					$A_field_show_hide[$o] =	$rowx[17];
					$A_field_duplicate[$o] =	$rowx[18];

					$o++;
					$rank_select .= "<option>$o</option>";
					}

				$o=0;
				while ($fields_to_print > $o) 
					{
					$new_field_exists=0;
					$temp_field_label = $A_field_label[$o];
					if ( ($table_exists > 0) or (preg_match("/\|$temp_field_label\|/i",$vicidial_list_fields)) )
						{
						$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id' and field_label='$A_field_label[$o]';";
						if ($DB>0) {echo "$stmt";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$fieldscount_to_print = mysqli_num_rows($rslt);
						if ($fieldscount_to_print > 0) 
							{
							$rowx=mysqli_fetch_row($rslt);
							$new_field_exists =	$rowx[0];
							}
						}
					if ( ($new_field_exists < 1) or ($A_field_duplicate[$o] == 'Y') )
						{
						if (preg_match("/\|$temp_field_label\|/i",$vicidial_list_fields))
							{$A_field_label[$o] = strtolower($A_field_label[$o]);}

						if ($A_field_duplicate[$o] == 'Y')
							{
							$duplicate_field_count=0;
							$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id' and field_label LIKE \"$A_field_label[$o]%\";";
							if ($DB>0) {echo "$stmt";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$dupscount_to_print = mysqli_num_rows($rslt);
							if ($dupscount_to_print > 0) 
								{
								$rowx=mysqli_fetch_row($rslt);
								$duplicate_field_count =	$rowx[0];
								}
							$A_field_label[$o] .= "_DUPLICATE_" . sprintf('%03d', $duplicate_field_count);
							}

						### add field function
						$SQLsuccess = add_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$A_field_id[$o],$list_id,$A_field_label[$o],$A_field_name[$o],$A_field_description[$o],$A_field_rank[$o],$A_field_help[$o],$A_field_type[$o],$A_field_options[$o],$A_field_size[$o],$A_field_max[$o],$A_field_default[$o],$A_field_required[$o],$A_field_cost[$o],$A_multi_position[$o],$A_name_position[$o],$A_field_order[$o],$A_field_encrypt[$o],$A_field_show_hide[$o],$A_field_duplicate[$o],$vicidial_list_fields,$mysql_reserved_words,$field_rerank);

						if ($SQLsuccess > 0)
							{echo _QXZ("SUCCESS: Custom Field Added")." - $list_id|$A_field_label[$o]\n<BR>";}

						if ($table_exists < 1) {$table_exists=1;}
						}
					else
						{
						if ($DB>0) {echo _QXZ("FIELD EXISTS").": |$list_id|$A_field_label[$o]|";}
						}
					$o++;
					}
				}
			##### UPDATE option #####
			if ($copy_option=='UPDATE')
				{
				if ($DB > 0) {echo _QXZ("Starting UPDATE copy")."\n<BR>";}
				if ( ($table_exists < 1) and (!preg_match("/\|$field_label\|/i",$vicidial_list_fields)) and ($field_duplicate=='N') )
					{echo "<B><font color=red>"._QXZ("ERROR: Table does not exist")." custom_$list_id</B></font>\n<BR>";}
				else
					{
					$stmt="SELECT field_id,field_label,field_name,field_description,field_rank,field_help,field_type,field_options,field_size,field_max,field_default,field_cost,field_required,multi_position,name_position,field_order,field_encrypt,field_show_hide,field_duplicate from vicidial_lists_fields where list_id='$source_list_id' order by field_rank,field_order,field_label;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$fields_to_print = mysqli_num_rows($rslt);
					$fields_list='';
					$o=0;
					while ($fields_to_print > $o) 
						{
						$rowx=mysqli_fetch_row($rslt);
						$A_field_id[$o] =			$rowx[0];
						$A_field_label[$o] =		$rowx[1];
						$A_field_name[$o] =			$rowx[2];
						$A_field_description[$o] =	$rowx[3];
						$A_field_rank[$o] =			$rowx[4];
						$A_field_help[$o] =			$rowx[5];
						$A_field_type[$o] =			$rowx[6];
						$A_field_options[$o] =		$rowx[7];
						$A_field_size[$o] =			$rowx[8];
						$A_field_max[$o] =			$rowx[9];
						$A_field_default[$o] =		$rowx[10];
						$A_field_cost[$o] =			$rowx[11];
						$A_field_required[$o] =		$rowx[12];
						$A_multi_position[$o] =		$rowx[13];
						$A_name_position[$o] =		$rowx[14];
						$A_field_order[$o] =		$rowx[15];
						$A_field_encrypt[$o] =		$rowx[16];
						$A_field_show_hide[$o] =	$rowx[17];
						$A_field_duplicate[$o] =	$rowx[18];
						$o++;
						}

					$o=0;
					while ($fields_to_print > $o) 
						{
						$stmt="SELECT field_id from vicidial_lists_fields where list_id='$list_id' and field_label='$A_field_label[$o]';";
						if ($DB>0) {echo "$stmt";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$fieldscount_to_print = mysqli_num_rows($rslt);
						if ($fieldscount_to_print > 0) 
							{
							$rowx=mysqli_fetch_row($rslt);
							$current_field_id =	$rowx[0];

							### modify field function
							$SQLsuccess = modify_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$current_field_id,$list_id,$A_field_label[$o],$A_field_name[$o],$A_field_description[$o],$A_field_rank[$o],$A_field_help[$o],$A_field_type[$o],$A_field_options[$o],$A_field_size[$o],$A_field_max[$o],$A_field_default[$o],$A_field_required[$o],$A_field_cost[$o],$A_multi_position[$o],$A_name_position[$o],$A_field_order[$o],$A_field_encrypt[$o],$A_field_show_hide[$o],$A_field_duplicate[$o],$vicidial_list_fields,$field_rerank);

							if ($SQLsuccess > 0)
								{echo _QXZ("SUCCESS: Custom Field Modified")." - $list_id|$A_field_label[$o]\n<BR>";}
							}
						$o++;
						}
					}
				}
			}

		$action = "MODIFY_CUSTOM_FIELDS";
		}
	}
### END copy list fields submit





################################################################################
##### BEGIN delete custom field confirmation
if ( ($action == "DELETE_CUSTOM_FIELD_CONFIRMATION") and ($list_id > 99) and ($field_id > 0) and (strlen($field_label) > 0) )
	{
	$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id' and field_label='$field_label';";
	if ($DB>0) {echo "$stmt";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$fieldscount_to_print = mysqli_num_rows($rslt);
	if ($fieldscount_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$field_exists =	$rowx[0];
		}
	
	$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
	$rslt=mysql_to_mysqli($stmt, $link);
	$tablecount_to_print = mysqli_num_rows($rslt);
	if ($tablecount_to_print > 0) 
		{$table_exists =	1;}
	if ($DB>0) {echo "$stmt|$tablecount_to_print|$table_exists";}
	
	if ($field_exists < 1)
		{echo "<B><font color=red>"._QXZ("ERROR: Field does not exist")."</B></font>\n<BR>";}
	else
		{
		if ( ($table_exists < 1) and (!preg_match("/\|$field_label\|/i",$vicidial_list_fields)) and ($field_duplicate=='N') )
			{echo "<B><font color=red>"._QXZ("ERROR: Table does not exist")." custom_$list_id</B></font>\n<BR>";}
		else
			{
			echo "<BR><BR><B><a href=\"$PHP_SELF?action=DELETE_CUSTOM_FIELD&list_id=$list_id&field_id=$field_id&field_label=$field_label&field_type=$field_type&field_duplicate=$field_duplicate&ConFiRm=YES&DB=$DB\">"._QXZ("CLICK HERE TO CONFIRM DELETION OF THIS CUSTOM FIELD").": $field_label - $field_id - $list_id</a></B><BR><BR>";
			}
		}

	$action = "MODIFY_CUSTOM_FIELDS";
	}
### END delete custom field confirmation




################################################################################
##### BEGIN delete custom field
if ( ($action == "DELETE_CUSTOM_FIELD") and ($list_id > 99) and ($field_id > 0) and (strlen($field_label) > 0) and ($ConFiRm=='YES') )
	{
	$table_exists=0;
	#$linkCUSTOM=mysql_connect("$VARDB_server:$VARDB_port", "$VARDB_custom_user","$VARDB_custom_pass");
	#if (!$linkCUSTOM) {die("Could not connect: $VARDB_server|$VARDB_port|$VARDB_database|$VARDB_custom_user|$VARDB_custom_pass" . mysqli_error());}
	#mysql_select_db("$VARDB_database", $linkCUSTOM);
	$linkCUSTOM=mysqli_connect("$VARDB_server", "$VARDB_custom_user", "$VARDB_custom_pass", "$VARDB_database", "$VARDB_port");
	if (!$linkCUSTOM) 
		{
		die('MySQL '._QXZ("connect ERROR").': '. mysqli_connect_error());
		}


	$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id' and field_label='$field_label';";
	if ($DB>0) {echo "$stmt";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$fieldscount_to_print = mysqli_num_rows($rslt);
	if ($fieldscount_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$field_exists =	$rowx[0];
		}
	
	$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
	$rslt=mysql_to_mysqli($stmt, $link);
	$tablecount_to_print = mysqli_num_rows($rslt);
	if ($tablecount_to_print > 0) 
		{$table_exists =	1;}
	if ($DB>0) {echo "$stmt|$tablecount_to_print|$table_exists";}
	
	if ($field_exists < 1)
		{echo "<B><font color=red>"._QXZ("ERROR: Field does not exist")."</B></font>\n<BR>";}
	else
		{
		if ( ($table_exists < 1) and (!preg_match("/\|$field_label\|/i",$vicidial_list_fields)) )
			{echo "<B><font color=red>"._QXZ("ERROR: Table does not exist")." custom_$list_id</B></font>\n<BR>";}
		else
			{
			### delete field function
			$SQLsuccess = delete_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$field_id,$list_id,$field_label,$field_name,$field_description,$field_rank,$field_help,$field_type,$field_options,$field_size,$field_max,$field_default,$field_required,$field_cost,$multi_position,$name_position,$field_order,$field_encrypt,$field_show_hide,$field_duplicate,$vicidial_list_fields);
			if ($DB) {echo "delete_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$field_id,$list_id,$field_label,$field_name,$field_description,$field_rank,$field_help,$field_type,$field_options,$field_size,$field_max,$field_default,$field_required,$field_cost,$multi_position,$name_position,$field_order,$field_encrypt,$field_show_hide,$field_duplicate,$vicidial_list_fields);<BR>\n";}

			if ($SQLsuccess > 0)
				{echo _QXZ("SUCCESS: Custom Field Deleted")." - $list_id|$field_label\n<BR>";}
			}
		}

	$action = "MODIFY_CUSTOM_FIELDS";
	}
### END delete custom field




################################################################################
##### BEGIN add new custom field
if ( ($action == "ADD_CUSTOM_FIELD") and ($list_id > 99) )
	{
	$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id' and field_label='$field_label';";
	if ($DB>0) {echo "$stmt";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$fieldscount_to_print = mysqli_num_rows($rslt);
	if ($fieldscount_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$field_exists =	$rowx[0];
		}
	
	if ( (strlen($field_label)<1) or (strlen($field_name)<2) or (strlen($field_size)<1) )
		{echo "<B><font color=red>"._QXZ("ERROR: You must enter a field label, field name and field size")." - $list_id|$field_label|$field_name|$field_size</B></font>\n<BR>";}
	else
		{
		if ( ( ($field_type=='TEXT') or ($field_type=='READONLY') or ($field_type=='HIDDEN') ) and ($field_max < strlen($field_default)) )
			{echo "<B><font color=red>"._QXZ("ERROR: Default value cannot be longer than maximum field length")." - $list_id|$field_label|$field_name|$field_max|$field_default|</B></font>\n<BR>";}
		else
			{
			if (preg_match("/\|$field_label\|/i",$mysql_reserved_words))
				{echo "<B><font color=red>"._QXZ("ERROR: You cannot use reserved words for field labels")." - $list_id|$field_label|$field_name|$field_size</B></font>\n<BR>";}
			else
				{
				$TEST_valid_options=0;
				if ( ($field_type=='SELECT') or ($field_type=='SOURCESELECT') or ($field_type=='MULTI') or ($field_type=='RADIO') or ($field_type=='CHECKBOX') or ($field_type=='SWITCH') )
					{
					$TESTfield_options_array = explode("\n",$field_options);
					$TESTfield_options_count = count($TESTfield_options_array);
					$te=0;
					$switch_list_self=0;
					while ($te < $TESTfield_options_count)
						{
						if (preg_match("/,|\|/",$TESTfield_options_array[$te]))
							{
							if ($field_type=='SOURCESELECT')
								{$TESTfield_options_value_array = explode('|',$TESTfield_options_array[$te]);}
							else
								{$TESTfield_options_value_array = explode(",",$TESTfield_options_array[$te]);}
							if ( (strlen($TESTfield_options_value_array[0]) > 0) and (strlen($TESTfield_options_value_array[1]) > 0) )
								{$TEST_valid_options++;}
							if ( ($field_type=='SWITCH') and ($TESTfield_options_value_array[0] == "$list_id") )
								{$switch_list_self++;}
							}
						$te++;
						}
					$field_options_ENUM = preg_replace("/.$/",'',$field_options_ENUM);
					}

				if ( ( ($field_type=='SELECT') or ($field_type=='SOURCESELECT') or ($field_type=='MULTI') or ($field_type=='RADIO') or ($field_type=='CHECKBOX') ) and ( (!preg_match("/,|\|/",$field_options)) or (!preg_match("/\n/",$field_options)) or (strlen($field_options)<6) or ($TEST_valid_options < 1) ) )
					{echo "<B><font color=red>"._QXZ("ERROR: You must enter field options when adding a SELECT, MULTI, RADIO, CHECKBOX or SOURCESELECT field type")."  - $list_id|$field_label|$field_type|$switch_list_self|$field_options</B></font>\n<BR>";}
				else
					{
					if ( ($field_type=='SWITCH') and ($switch_list_self < 1) )
						{echo "<B><font color=red>"._QXZ("ERROR: You must include the current List ID in field options when adding a SWITCH field type")."  - $list_id|$field_label|$field_type|$field_options</B></font>\n<BR>";}
					else
						{
						if ( ($field_exists > 0) and ($field_duplicate != 'Y') )
							{echo "<B><font color=red>"._QXZ("ERROR: Field already exists for this list")." - $list_id|$field_label</B></font>\n<BR>";}
						else
							{
							if ( ($field_exists < 1) and ($field_duplicate == 'Y') )
								{echo "<B><font color=red>"._QXZ("ERROR: Field set to duplicate but original does not exist for this list")." - $list_id|$field_label|$field_duplicate</B></font>\n<BR>";}
							else
								{
								if ( ($field_type!='TEXT') and ($field_duplicate == 'Y') )
									{echo "<B><font color=red>"._QXZ("ERROR: Field set to duplicate but not TEXT type")." - $list_id|$field_label|$field_duplicate|$field_type</B></font>\n<BR>";}
								else
									{
									$table_exists=0;
									#$linkCUSTOM=mysql_connect("$VARDB_server:$VARDB_port", "$VARDB_custom_user","$VARDB_custom_pass");
									#if (!$linkCUSTOM) {die("Could not connect: $VARDB_server|$VARDB_port|$VARDB_database|$VARDB_custom_user|$VARDB_custom_pass" . mysqli_error());}
									#mysql_select_db("$VARDB_database", $linkCUSTOM);

									$linkCUSTOM=mysqli_connect("$VARDB_server", "$VARDB_custom_user", "$VARDB_custom_pass", "$VARDB_database", "$VARDB_port");
									if (!$linkCUSTOM)
										{
										die('MySQL '._QXZ("connect ERROR").': '. mysqli_connect_error());
										}

									$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
									$rslt=mysql_to_mysqli($stmt, $link);
									$tablecount_to_print = mysqli_num_rows($rslt);
									if ($tablecount_to_print > 0) 
										{$table_exists =	1;}
									if ($DB>0) {echo "$stmt|$tablecount_to_print|$table_exists";}
								
									if (preg_match("/\|$field_label\|/i",$vicidial_list_fields))
										{$field_label = strtolower($field_label);}

									if ($field_duplicate == 'Y')
										{
										$duplicate_field_count=0;
										$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id' and field_label LIKE \"$field_label%\";";
										if ($DB>0) {echo "$stmt";}
										$rslt=mysql_to_mysqli($stmt, $link);
										$dupscount_to_print = mysqli_num_rows($rslt);
										if ($dupscount_to_print > 0) 
											{
											$rowx=mysqli_fetch_row($rslt);
											$duplicate_field_count =	$rowx[0];
											}
										$field_label .= "_DUPLICATE_" . sprintf('%03d', $duplicate_field_count);
										}

									### add field function
									$SQLsuccess = add_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$field_id,$list_id,$field_label,$field_name,$field_description,$field_rank,$field_help,$field_type,$field_options,$field_size,$field_max,$field_default,$field_required,$field_cost,$multi_position,$name_position,$field_order,$field_encrypt,$field_show_hide,$field_duplicate,$vicidial_list_fields,$mysql_reserved_words,$field_rerank);

									if ($SQLsuccess > 0)
										{echo _QXZ("SUCCESS: Custom Field Added")." - $list_id|$field_label\n<BR>";}
									}
								}
							}
						}
					}
				}
			}
		}
	$action = "MODIFY_CUSTOM_FIELDS";
	}
### END add new custom field




################################################################################
##### BEGIN modify custom field submission
if ( ($action == "MODIFY_CUSTOM_FIELD_SUBMIT") and ($list_id > 99) and ($field_id > 0) )
	{
	### connect to your vtiger database
	#$linkCUSTOM=mysql_connect("$VARDB_server:$VARDB_port", "$VARDB_custom_user","$VARDB_custom_pass");
	#if (!$linkCUSTOM) {die("Could not connect: $VARDB_server|$VARDB_port|$VARDB_database|$VARDB_custom_user|$VARDB_custom_pass" . mysqli_error());}
	#mysql_select_db("$VARDB_database", $linkCUSTOM);

	$linkCUSTOM=mysqli_connect("$VARDB_server", "$VARDB_custom_user", "$VARDB_custom_pass", "$VARDB_database", "$VARDB_port");
	if (!$linkCUSTOM) 
		{
		die('MySQL '._QXZ("connect ERROR").': '. mysqli_connect_error());
		}


	$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id' and field_id='$field_id';";
	if ($DB>0) {echo "$stmt";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$fieldscount_to_print = mysqli_num_rows($rslt);
	if ($fieldscount_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$field_exists =	$rowx[0];
		}
	
	$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
	$rslt=mysql_to_mysqli($stmt, $link);
	$tablecount_to_print = mysqli_num_rows($rslt);
	if ($tablecount_to_print > 0) 
		{$table_exists =	1;}
	if ($DB>0) {echo "$stmt|$tablecount_to_print|$table_exists";}

	if ($field_duplicate=='Y')
		{$field_type='TEXT';}

	if ($field_exists < 1)
		{echo "<B><font color=red>"._QXZ("ERROR: Field does not exist")."</B></font>\n<BR>";}
	else
		{
		if ( ($table_exists < 1) and (!preg_match("/\|$field_label\|/i",$vicidial_list_fields)) and ($field_duplicate=='N') )
			{echo "<B><font color=red>"._QXZ("ERROR: Table does not exist")."</B></font>\n<BR>";}
		else
			{
			if ( ( ($field_type=='TEXT') or ($field_type=='READONLY') or ($field_type=='HIDDEN') ) and ($field_max < strlen($field_default)) )
				{echo "<B><font color=red>"._QXZ("ERROR: Default value cannot be longer than maximum field length")." - $list_id|$field_label|$field_name|$field_max|$field_default|</B></font>\n<BR>";}
			else
				{
				$TEST_valid_options=0;
				if ( ($field_type=='SELECT') or ($field_type=='SOURCESELECT') or ($field_type=='MULTI') or ($field_type=='RADIO') or ($field_type=='CHECKBOX') or ($field_type=='SWITCH') )
					{
					$TESTfield_options_array = explode("\n",$field_options);
					$TESTfield_options_count = count($TESTfield_options_array);
					$te=0;
					$switch_list_self=0;
					while ($te < $TESTfield_options_count)
						{
						if (preg_match("/,|\|/",$TESTfield_options_array[$te]))
							{
							if ($field_type=='SOURCESELECT')
								{$TESTfield_options_value_array = explode('|',$TESTfield_options_array[$te]);}
							else
								{$TESTfield_options_value_array = explode(",",$TESTfield_options_array[$te]);}
							if ( (strlen($TESTfield_options_value_array[0]) > 0) and (strlen($TESTfield_options_value_array[1]) > 0) )
								{$TEST_valid_options++;}
							if ( ($field_type=='SWITCH') and ($TESTfield_options_value_array[0] == "$list_id") )
								{$switch_list_self++;}
							if ($DB) {echo "DEBUG: |$list_id|$field_label|$field_type|$switch_list_self|$TESTfield_options_value_array[0]|$TESTfield_options_value_array[1]|\n";}
							}
						$te++;
						}
					$field_options_ENUM = preg_replace("/.$/",'',$field_options_ENUM);
					}

				if ( ( ($field_type=='SELECT') or ($field_type=='SOURCESELECT') or ($field_type=='MULTI') or ($field_type=='RADIO') or ($field_type=='CHECKBOX') ) and ( (!preg_match("/,|\|/",$field_options)) or (!preg_match("/\n/",$field_options)) or (strlen($field_options)<6) or ($TEST_valid_options < 1) ) )
					{echo "<B><font color=red>"._QXZ("ERROR: You must enter field options when updating a SELECT, MULTI, RADIO, CHECKBOX or SOURCESELECT field type")."  - $list_id|$field_label|$field_type|$field_options</B></font>\n<BR>";}
				else
					{
					if ( ($field_type=='SWITCH') and ($switch_list_self < 1) )
						{echo "<B><font color=red>"._QXZ("ERROR: You must include the current List ID in field options when modifying a SWITCH field type")."  - $list_id|$field_label|$field_type|$switch_list_self|$field_options</B></font>\n<BR>";}
					else
						{
						### modify field function
						$SQLsuccess = modify_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$field_id,$list_id,$field_label,$field_name,$field_description,$field_rank,$field_help,$field_type,$field_options,$field_size,$field_max,$field_default,$field_required,$field_cost,$multi_position,$name_position,$field_order,$field_encrypt,$field_show_hide,$field_duplicate,$vicidial_list_fields,$field_rerank);

						if ($SQLsuccess > 0)
							{echo _QXZ("SUCCESS: Custom Field Modified")." - $list_id|$field_label\n<BR>";}
						}
					}
				}
			}
		}

	$action = "MODIFY_CUSTOM_FIELDS";
	}
### END modify custom field submission





################################################################################
##### BEGIN modify custom fields for list
if ( ($action == "MODIFY_CUSTOM_FIELDS") and ($list_id > 99) )
	{
	echo "</TITLE></HEAD><BODY BGCOLOR=white>\n";
	echo "<TABLE><TR><TD>\n";

	$stmt="SELECT list_name,active,campaign_id from vicidial_lists where list_id='$list_id' $LOGallowed_campaignsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$lists_to_print = mysqli_num_rows($rslt);
	if ($lists_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$list_name =		$rowx[0];
		$active =			$rowx[1];
		$campaign_id =		$rowx[2];
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")." $list_id\n";
		exit;
		}

	$custom_records_count=0;
	$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
	$rslt=mysql_to_mysqli($stmt, $link);
	$tablecount_to_print = mysqli_num_rows($rslt);
	if ($tablecount_to_print > 0) 
		{$table_exists =	1;}
	if ($DB>0) {echo "$stmt|$tablecount_to_print|$table_exists";}
	
	if ($table_exists > 0)
		{
		$stmt="SELECT count(*) from custom_$list_id;";
		if ($DB>0) {echo "$stmt";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$fieldscount_to_print = mysqli_num_rows($rslt);
		if ($fieldscount_to_print > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$custom_records_count =	$rowx[0];
			}
		}

	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
	echo "<br>"._QXZ("Modify Custom Fields: List ID")." $list_id - $list_name  &nbsp; &nbsp; &nbsp; &nbsp; ";
	echo _QXZ("Records in this custom table").": $custom_records_count<br>\n";
	echo "<center><TABLE width=$section_width cellspacing=3>\n";

	$stmt="SELECT field_id,field_label,field_name,field_description,field_rank,field_help,field_type,field_options,field_size,field_max,field_default,field_cost,field_required,multi_position,name_position,field_order,field_encrypt,field_show_hide,field_duplicate from vicidial_lists_fields where list_id='$list_id' order by field_rank,field_order,field_label;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$fields_to_print = mysqli_num_rows($rslt);
	$fields_list='';

	$A_field_id = array();
	$A_field_label = array();
	$A_field_name = array();
	$A_field_description = array();
	$A_field_rank = array();
	$A_field_help = array();
	$A_field_type = array();
	$A_field_options = array();
	$A_field_size = array();
	$A_field_max = array();
	$A_field_default = array();
	$A_field_cost = array();
	$A_field_required = array();
	$A_multi_position = array();
	$A_name_position = array();
	$A_field_order = array();
	$A_field_encrypt = array();
	$A_field_show_hide = array();
	$A_field_duplicate = array();
	$o=0;
	while ($fields_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$A_field_id[$o] =			$rowx[0];
		$A_field_label[$o] =		$rowx[1];
		$A_field_name[$o] =			$rowx[2];
		$A_field_description[$o] =	$rowx[3];
		$A_field_rank[$o] =			$rowx[4];
		$A_field_help[$o] =			$rowx[5];
		$A_field_type[$o] =			$rowx[6];
		$A_field_options[$o] =		$rowx[7];
		$A_field_size[$o] =			$rowx[8];
		$A_field_max[$o] =			$rowx[9];
		$A_field_default[$o] =		$rowx[10];
		$A_field_cost[$o] =			$rowx[11];
		$A_field_required[$o] =		$rowx[12];
		$A_multi_position[$o] =		$rowx[13];
		$A_name_position[$o] =		$rowx[14];
		$A_field_order[$o] =		$rowx[15];
		$A_field_encrypt[$o] =		$rowx[16];
		$A_field_show_hide[$o] =	$rowx[17];
		$A_field_duplicate[$o] =	$rowx[18];

		$o++;
		$rank_select .= "<option>$o</option>";
		}
	$o++;
	$rank_select .= "<option>$o</option>";
	$last_rank = $o;

	### SUMMARY OF FIELDS ###
	echo "<br>"._QXZ("SUMMARY OF FIELDS").":\n";
	echo "<center><TABLE cellspacing=0 cellpadding=1>\n";
	echo "<TR BGCOLOR=BLACK>";
	echo "<TD align=right nowrap><B><FONT FACE=\"Arial,Helvetica\" size=2 color=white>"._QXZ("RANK")." &nbsp; &nbsp; </B></TD>";
	echo "<TD align=right nowrap><B><FONT FACE=\"Arial,Helvetica\" size=2 color=white>"._QXZ("LABEL")." &nbsp; &nbsp; </B></TD>";
	echo "<TD align=right nowrap><B><FONT FACE=\"Arial,Helvetica\" size=2 color=white>"._QXZ("NAME")." &nbsp; &nbsp; </B></TD>";
	echo "<TD align=right nowrap><B><FONT FACE=\"Arial,Helvetica\" size=2 color=white>"._QXZ("TYPE")." &nbsp; &nbsp; </B></TD>";
	if ($hide_enc < 1)
		{
		echo "<TD align=right nowrap><B><FONT FACE=\"Arial,Helvetica\" size=2 color=white>"._QXZ("ENC")." &nbsp; &nbsp; </B></TD>";
		}
	echo "<TD align=right nowrap><B><FONT FACE=\"Arial,Helvetica\" size=2 color=white>"._QXZ("COST")." &nbsp; &nbsp; </B></TD>\n";
	echo "</TR>\n";

	$o=0;
	$total_enc=0;
	while ($fields_to_print > $o) 
		{
		$LcolorB='';   $LcolorE='';
		$reserved_test = $A_field_label[$o];
		if (preg_match("/\|$reserved_test\|/i",$vicidial_list_fields))
			{
			$LcolorB='<font color=red>';
			$LcolorE='</font>';
			}
		if ($A_field_duplicate[$o] == 'Y')
			{
			$LcolorB='<font color=#006600>';
			$LcolorE='</font>';
			}
		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='class="records_list_x"';} 
		else
			{$bgcolor='class="records_list_y"';}
		echo "<tr $bgcolor align=right><td><font size=1>$A_field_rank[$o] - $A_field_order[$o] &nbsp; &nbsp; </td>";
		echo "<td align=right><font size=2> <a href=\"#ANCHOR_$A_field_label[$o]\">$LcolorB$A_field_label[$o]$LcolorE</a> &nbsp; &nbsp; </td>";
		echo "<td align=right><font size=2> $A_field_name[$o] &nbsp; &nbsp; </td>";
		echo "<td align=right><font size=2> "._QXZ("$A_field_type[$o]")." &nbsp; &nbsp; </td>";
		if ($hide_enc < 1)
			{
			echo "<td align=right><font size=2> "._QXZ("$A_field_encrypt[$o]")." &nbsp; &nbsp; </td>";
			}
		echo "<td align=right><font size=2> $A_field_cost[$o] &nbsp; &nbsp; </td></tr>\n";

		if ($A_field_encrypt[$o] == 'Y') {$total_enc++;}
		$total_cost = ($total_cost + $A_field_cost[$o]);
		$o++;
		}

	if ($fields_to_print < 1) 
		{echo "<tr bgcolor=white align=center><td colspan=5><font size=1>"._QXZ("There are no custom fields for this list")."</td></tr>";}
	else
		{
		echo "<tr bgcolor=white align=right><td><font size=2>"._QXZ("TOTALS").": </td>";
		echo "<td align=right><font size=2> $o &nbsp; &nbsp; </td>";
		echo "<td align=right><font size=2> &nbsp; &nbsp; </td>";
		echo "<td align=right><font size=2> &nbsp; &nbsp; </td>";
		if ($hide_enc < 1)
			{
			echo "<td align=right><font size=2> $total_enc &nbsp; &nbsp; </td>";
			}
		echo "<td align=right><font size=2> $total_cost &nbsp; &nbsp; </td></tr>\n";
		}
	echo "</table></center><BR><BR>\n";


	### EXAMPLE OF CUSTOM FORM ###
	echo "<form action=$PHP_SELF method=POST name=form_custom_$list_id id=form_custom_$list_id>\n";
	echo "<br>"._QXZ("EXAMPLE OF CUSTOM FORM").":\n";
	echo "<center><TABLE cellspacing=2 cellpadding=2>\n";
	if ($fields_to_print < 1) 
		{echo "<tr bgcolor=white align=center><td colspan=4><font size=1>"._QXZ("There are no custom fields for this list")."</td></tr>";}

	$o=0;
	$last_field_rank=0;
	while ($fields_to_print > $o) 
		{
		if ($last_field_rank=="$A_field_rank[$o]")
			{echo " &nbsp; &nbsp; &nbsp; &nbsp; ";}
		else
			{
			echo "</td></tr>\n";
			echo "<tr bgcolor=white><td align=";
			if ($A_name_position[$o]=='TOP') 
				{echo "left colspan=2";}
			else
				{echo "right";}
			echo "><font size=2>";
			}
		echo "<a href=\"#ANCHOR_$A_field_label[$o]\"><B>$A_field_name[$o]</B></a>";
		if ($A_name_position[$o]=='TOP') 
			{
			$helpHTML = "<a href=\"javascript:open_help('HELP_$A_field_label[$o]','$A_field_help[$o]');\">help+</a>";
			if (strlen($A_field_help[$o])<1)
				{$helpHTML = '';}
			echo " &nbsp; <span style=\"position:static;\" id=P_HELP_$A_field_label[$o]></span><span style=\"position:static;background:white;\" id=HELP_$A_field_label[$o]> &nbsp; $helpHTML</span><BR>";
			}
		else
			{
			if ($last_field_rank=="$A_field_rank[$o]")
				{echo " &nbsp;";}
			else
				{echo "</td><td align=left><font size=2>";}
			}
		$field_HTML='';

		$encrypt_icon='';
		if ($A_field_encrypt[$o] == 'Y')
			{$encrypt_icon = " <img src=\""._QXZ("../../agc/images/encrypt.gif")."\" width=16 height=20 valign=bottom alt=\""._QXZ("Encrypted Field")."\">";}
		if ( ($A_field_type[$o]=='SELECT') or ($A_field_type[$o]=='SOURCESELECT') )
			{
			$field_HTML .= "<select size=1 name=$A_field_label[$o] id=$A_field_label[$o]>\n";
			}
		if ($A_field_type[$o]=='MULTI')
			{
			$field_HTML .= "<select MULTIPLE size=$A_field_size[$o] name=$A_field_label[$o] id=$A_field_label[$o]>\n";
			}
		if ( ($A_field_type[$o]=='SELECT') or ($A_field_type[$o]=='SOURCESELECT') or ($A_field_type[$o]=='MULTI') or ($A_field_type[$o]=='RADIO') or ($A_field_type[$o]=='CHECKBOX') or ($A_field_type[$o]=='SWITCH') )
			{
			$field_options_array = explode("\n",$A_field_options[$o]);
			$field_options_count = count($field_options_array);
			$te=0;
			while ($te < $field_options_count)
				{
				if (preg_match("/,|\|/",$field_options_array[$te]))
					{
					$field_selected='';
					if ($A_field_type[$o]=='SOURCESELECT')
						{$field_options_value_array = explode('|',$field_options_array[$te]);}
					else
						{$field_options_value_array = explode(",",$field_options_array[$te]);}
					if ( ($A_field_type[$o]=='SELECT') or ($A_field_type[$o]=='MULTI') )
						{
						if ($A_field_default[$o] == "$field_options_value_array[0]") {$field_selected = 'SELECTED';}
						$field_HTML .= "<option value=\"$field_options_value_array[0]\" $field_selected>$field_options_value_array[1]</option>\n";
						}
					if ($A_field_type[$o]=='SOURCESELECT')
						{
						if (preg_match("/^option=>/i",$field_options_value_array[0]))
							{
							$field_options_value_array[0] = preg_replace("/^option=>/i",'',$field_options_value_array[0]);
							if ($A_field_default[$o] == "$field_options_value_array[0]") {$field_selected = 'SELECTED';}
							$field_HTML .= "<option value=\"$field_options_value_array[0]\" $field_selected>$field_options_value_array[1]</option>\n";
							}
						}
					if ( ($A_field_type[$o]=='RADIO') or ($A_field_type[$o]=='CHECKBOX') )
						{
						if ($A_multi_position[$o]=='VERTICAL') 
							{$field_HTML .= " &nbsp; ";}
						if ($A_field_default[$o] == "$field_options_value_array[0]") {$field_selected = 'CHECKED';}
						$field_HTML .= "<input type=$A_field_type[$o] name=$A_field_label[$o][] id=$A_field_label[$o][] value=\"$field_options_value_array[0]\" $field_selected> $field_options_value_array[1]\n";
						if ($A_multi_position[$o]=='VERTICAL') 
							{$field_HTML .= "<BR>\n";}
						}
					if ($A_field_type[$o]=='SWITCH')
						{
						if ($A_multi_position[$o]=='VERTICAL') 
							{$field_HTML .= " &nbsp; ";}
						if ($list_id == "$field_options_value_array[0]") 
							{
							$field_HTML .= "<button class='button_inactive' disabled onclick=\"nothing();\"> "._QXZ("$field_options_value_array[1]")." </button> ";
							}
						else
							{
							$field_HTML .= "<button class='button_active' disabled onclick=\"switch_list('$field_options_value_array[0]');\"> "._QXZ("$field_options_value_array[1]")." </button> \n";
							}
						if ($A_multi_position[$o]=='VERTICAL') 
							{$field_HTML .= "<BR>\n";}
						}
					}
				$te++;
				}
			}
		if ( ($A_field_type[$o]=='SELECT') or ($A_field_type[$o]=='SOURCESELECT') or ($A_field_type[$o]=='MULTI') )
			{
			$field_HTML .= "</select>\n";
			}
		if ($A_field_type[$o]=='TEXT') 
			{
			if ($A_field_default[$o]=='NULL') {$A_field_default[$o]='';}
			if ($A_field_show_hide[$o] != 'DISABLED')
				{
				$field_temp_val = $A_field_default[$o];
				$field_orig_val = $A_field_default[$o];
				if (strlen($field_temp_val) > 0)
					{
					if ($A_field_show_hide[$o] == 'LAST_4')
						{$A_field_default[$o] = str_repeat("X", (strlen($field_temp_val) - 4)) . substr($field_temp_val,-4,4);}
					elseif ($A_field_show_hide[$o] == 'LAST_3')
						{$A_field_default[$o] = str_repeat("X", (strlen($field_temp_val) - 3)) . substr($field_temp_val,-3,3);}
					elseif ($A_field_show_hide[$o] == 'LAST_2')
						{$A_field_default[$o] = str_repeat("X", (strlen($field_temp_val) - 2)) . substr($field_temp_val,-2,2);}
					elseif ($A_field_show_hide[$o] == 'LAST_1')
						{$A_field_default[$o] = str_repeat("X", (strlen($field_temp_val) - 1)) . substr($field_temp_val,-1,1);}
					elseif ($A_field_show_hide[$o] == 'FIRST_1_LAST_4')
						{$A_field_default[$o] = substr($field_temp_val,0,1) . str_repeat("X", (strlen($field_temp_val) - 5)) . substr($field_temp_val,-4,4);}
					else # X_OUT_ALL
						{$A_field_default[$o] = preg_replace("/./",'X',$field_temp_val);}
					}
				$field_HTML .= _QXZ("$A_field_default[$o]")." &nbsp; "._QXZ("Overwrite").": <input type=text size=$A_field_size[$o] maxlength=$A_field_max[$o] name=OVERRIDE_$A_field_label[$o] id=OVERRIDE_$A_field_label[$o] value=\"\">$encrypt_icon\n";
				$A_field_default[$o] = $field_orig_val;
				}
			else
				{
				$field_HTML .= "<input type=text size=$A_field_size[$o] maxlength=$A_field_max[$o] name=$A_field_label[$o] id=$A_field_label[$o] value=\"$A_field_default[$o]\">$encrypt_icon\n";
				}
			}
		if ($A_field_type[$o]=='AREA') 
			{
			$field_HTML .= "<textarea name=$A_field_label[$o] id=$A_field_label[$o] ROWS=$A_field_max[$o] COLS=$A_field_size[$o]></textarea>$encrypt_icon";
			}
		if ($A_field_type[$o]=='DISPLAY')
			{
			if ($A_field_default[$o]=='NULL') {$A_field_default[$o]='';}
			$field_HTML .= "\n";
			}
		if ($A_field_type[$o]=='BUTTON')
			{
			$field_options_array = explode("\n",$A_field_options[$o]);
			if (preg_match("/^SubmitRefresh/i",$field_options_array[0]))
				{
				if ($A_multi_position[$o]=='VERTICAL') 
					{$field_HTML .= " &nbsp; ";}
				if (strlen($A_field_default[$o]) < 1) {$A_field_default[$o] = _QXZ("Commit Changes and Refresh Form");}
				$field_HTML .= "<button class='button_active' disabled onclick=\"form_button_functions('SubmitRefresh');\"> "._QXZ("$A_field_default[$o]")." </button> \n";
				if ($A_multi_position[$o]=='VERTICAL') 
					{$field_HTML .= "<BR>\n";}
				}
			}
		if ($A_field_type[$o]=='READONLY')
			{
			if ($A_field_default[$o]=='NULL') {$A_field_default[$o]='';}

			if ($A_field_show_hide[$o] != 'DISABLED')
				{
				$field_temp_val = $A_field_default[$o];
				if (strlen($field_temp_val) > 0)
					{
					if ($A_field_show_hide[$o] == 'LAST_4')
						{$A_field_default[$o] = str_repeat("X", (strlen($field_temp_val) - 4)) . substr($field_temp_val,-4,4);}
					elseif ($A_field_show_hide[$o] == 'LAST_3')
						{$A_field_default[$o] = str_repeat("X", (strlen($field_temp_val) - 3)) . substr($field_temp_val,-3,3);}
					elseif ($A_field_show_hide[$o] == 'LAST_2')
						{$A_field_default[$o] = str_repeat("X", (strlen($field_temp_val) - 2)) . substr($field_temp_val,-2,2);}
					elseif ($A_field_show_hide[$o] == 'LAST_1')
						{$A_field_default[$o] = str_repeat("X", (strlen($field_temp_val) - 1)) . substr($field_temp_val,-1,1);}
					elseif ($A_field_show_hide[$o] == 'FIRST_1_LAST_4')
						{$A_field_default[$o] = substr($field_temp_val,0,1) . str_repeat("X", (strlen($field_temp_val) - 5)) . substr($field_temp_val,-4,4);}
					else # X_OUT_ALL
						{$A_field_default[$o] = preg_replace("/./",'X',$field_temp_val);}
					}
				$field_HTML .= "$A_field_default[$o]$encrypt_icon\n";
				}
			else
				{
				$field_HTML .= "$A_field_default[$o]$encrypt_icon\n";
				}
			}
		if ($A_field_type[$o]=='HIDDEN')
			{
			if ($A_field_default[$o]=='NULL') {$A_field_default[$o]='';}
			$field_HTML .= "-- "._QXZ("HIDDEN")." --\n";
			}
		if ($A_field_type[$o]=='HIDEBLOB')
			{
			if ($A_field_default[$o]=='NULL') {$A_field_default[$o]='';}
			$field_HTML .= "-- "._QXZ("HIDDEN")." --\n";
			}
		if ($A_field_type[$o]=='SCRIPT')
			{
			if ($A_field_default[$o]=='NULL') {$A_field_default[$o]='';}
			$field_HTML .= "$A_field_options[$o]\n";
			}
		if ($A_field_type[$o]=='DATE') 
			{
			if ( (strlen($A_field_default[$o])<1) or ($A_field_default[$o]=='NULL') ) {$A_field_default[$o]=0;}
			$day_diff = $A_field_default[$o];
			$default_date = date("Y-m-d", mktime(date("H"),date("i"),date("s"),date("m"),date("d")+$day_diff,date("Y")));

			$field_HTML .= "<input type=text size=11 maxlength=10 name=$A_field_label[$o] id=$A_field_label[$o] value=\"$default_date\">\n";
			$field_HTML .= "<script language=\"JavaScript\">\n";
			$field_HTML .= "var o_cal = new tcal ({\n";
			$field_HTML .= "	'formname': 'form_custom_$list_id',\n";
			$field_HTML .= "	'controlname': '$A_field_label[$o]'});\n";
			$field_HTML .= "o_cal.a_tpl.yearscroll = false;\n";
			$field_HTML .= "</script>$encrypt_icon\n";
			}
		if ($A_field_type[$o]=='TIME') 
			{
			if ( ($A_field_default[$o] == 'NULL') or (strlen($A_field_default[$o]) < 1) ) {$A_field_default[$o]=0;}
			$minute_diff = $A_field_default[$o];
			$default_time = date("H:i:s", mktime(date("H"),date("i")+$minute_diff,date("s"),date("m"),date("d"),date("Y")));
			$default_hour = date("H", mktime(date("H"),date("i")+$minute_diff,date("s"),date("m"),date("d"),date("Y")));
			$default_minute = date("i", mktime(date("H"),date("i")+$minute_diff,date("s"),date("m"),date("d"),date("Y")));
			$field_HTML .= "<input type=hidden name=$A_field_label[$o] id=$A_field_label[$o] value=\"$default_time\">";
			$field_HTML .= "<SELECT name=HOUR_$A_field_label[$o] id=HOUR_$A_field_label[$o]>";
			$field_HTML .= "<option>00</option>";
			$field_HTML .= "<option>01</option>";
			$field_HTML .= "<option>02</option>";
			$field_HTML .= "<option>03</option>";
			$field_HTML .= "<option>04</option>";
			$field_HTML .= "<option>05</option>";
			$field_HTML .= "<option>06</option>";
			$field_HTML .= "<option>07</option>";
			$field_HTML .= "<option>08</option>";
			$field_HTML .= "<option>09</option>";
			$field_HTML .= "<option>10</option>";
			$field_HTML .= "<option>11</option>";
			$field_HTML .= "<option>12</option>";
			$field_HTML .= "<option>13</option>";
			$field_HTML .= "<option>14</option>";
			$field_HTML .= "<option>15</option>";
			$field_HTML .= "<option>16</option>";
			$field_HTML .= "<option>17</option>";
			$field_HTML .= "<option>18</option>";
			$field_HTML .= "<option>19</option>";
			$field_HTML .= "<option>20</option>";
			$field_HTML .= "<option>21</option>";
			$field_HTML .= "<option>22</option>";
			$field_HTML .= "<option>23</option>";
			$field_HTML .= "<OPTION value=\"$default_hour\" selected>$default_hour</OPTION>";
			$field_HTML .= "</SELECT>";
			$field_HTML .= "<SELECT name=MINUTE_$A_field_label[$o] id=MINUTE_$A_field_label[$o]>";
			$field_HTML .= "<option>00</option>";
			$field_HTML .= "<option>05</option>";
			$field_HTML .= "<option>10</option>";
			$field_HTML .= "<option>15</option>";
			$field_HTML .= "<option>20</option>";
			$field_HTML .= "<option>25</option>";
			$field_HTML .= "<option>30</option>";
			$field_HTML .= "<option>35</option>";
			$field_HTML .= "<option>40</option>";
			$field_HTML .= "<option>45</option>";
			$field_HTML .= "<option>50</option>";
			$field_HTML .= "<option>55</option>";
			$field_HTML .= "<OPTION value=\"$default_minute\" selected>$default_minute</OPTION>";
			$field_HTML .= "</SELECT>$encrypt_icon";
			}

		if ($A_name_position[$o]=='LEFT') 
			{
			$helpHTML = "<a href=\"javascript:open_help('HELP_$A_field_label[$o]','$A_field_help[$o]');\">help+</a>";
			if (strlen($A_field_help[$o])<1)
				{$helpHTML = '';}
			echo " $field_HTML <span style=\"position:static;\" id=P_HELP_$A_field_label[$o]></span><span style=\"position:static;background:white;\" id=HELP_$A_field_label[$o]> &nbsp; $helpHTML</span>";
			}
		else
			{
			echo " $field_HTML\n";
			}

		$last_field_rank=$A_field_rank[$o];
		$o++;
		}
	echo "</td></tr></table></form></center><BR><BR>\n";


	### MODIFY FIELDS ###
	echo "<br>"._QXZ("MODIFY EXISTING FIELDS").":\n";
	$o=0;
	while ($fields_to_print > $o) 
		{
		$LcolorB='';   $LcolorE='';
		$reserved_test = $A_field_label[$o];
		if (preg_match("/\|$reserved_test\|/i",$vicidial_list_fields))
			{
			$LcolorB='<font color=red>';
			$LcolorE='</font>';
			}
		if ($A_field_duplicate[$o] == 'Y')
			{
			$LcolorB='<font color=#006600>';
			$LcolorE='</font>';
			}
		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
		else
			{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}
		echo "<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=action value=MODIFY_CUSTOM_FIELD_SUBMIT>\n";
		echo "<input type=hidden name=list_id value=$list_id>\n";
		echo "<input type=hidden name=DB value=$DB>\n";
		echo "<input type=hidden name=field_id value=\"$A_field_id[$o]\">\n";
		echo "<input type=hidden name=field_label value=\"$A_field_label[$o]\">\n";
		echo "<input type=hidden name=field_duplicate value=\"$A_field_duplicate[$o]\">\n";
		echo "<a name=\"ANCHOR_$A_field_label[$o]\">\n";
		echo "<center><TABLE width=$section_width cellspacing=3 cellpadding=1>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Label")." $A_field_rank[$o]: </td><td align=left> $LcolorB<B>$A_field_label[$o]</B>$LcolorE $NWB#lists_fields-field_label$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Rank")." $A_field_rank[$o]: </td><td align=left><select size=1 name=field_rank>\n";
		echo "$rank_select\n";
		echo "<option selected>$A_field_rank[$o]</option>\n";
		echo "</select> &nbsp; $NWB#lists_fields-field_rank$NWE \n";
		echo " &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Field Order").": <select size=1 name=field_order>\n";
		echo "<option>1</option>\n";
		echo "<option>2</option>\n";
		echo "<option>3</option>\n";
		echo "<option>4</option>\n";
		echo "<option>5</option>\n";
		echo "<option selected>$A_field_order[$o]</option>\n";
		echo "</select> &nbsp; $NWB#lists_fields-field_order$NWE \n";
		echo " &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Re-Rank Fields Below").": <select size=1 name=field_rerank>\n";
		echo "<option calue=\"NO\" selected>"._QXZ("NO")."</option>\n";
		echo "<option calue=\"YES\">"._QXZ("YES")."</option>\n";
		echo "</select> &nbsp; $NWB#lists_fields-field_rerank$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Name")." $A_field_rank[$o]: </td><td align=left><textarea name=field_name rows=2 cols=60>$A_field_name[$o]</textarea> $NWB#lists_fields-field_name$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Name Position")." $A_field_rank[$o]: </td><td align=left><select size=1 name=name_position>\n";
		echo "<option value=\"LEFT\">"._QXZ("LEFT")."</option>\n";
		echo "<option value=\"TOP\">"._QXZ("TOP")."</option>\n";
		echo "<option selected value='$A_name_position[$o]'>"._QXZ("$A_name_position[$o]")."</option>\n";
		echo "</select>  $NWB#lists_fields-name_position$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Description")." $A_field_rank[$o]: </td><td align=left><input type=text name=field_description size=70 maxlength=100 value=\"$A_field_description[$o]\"> $NWB#lists_fields-field_description$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Help")." $A_field_rank[$o]: </td><td align=left><textarea name=field_help rows=2 cols=60>$A_field_help[$o]</textarea> $NWB#lists_fields-field_help$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Type")." $A_field_rank[$o]: </td><td align=left><select size=1 name=field_type>\n";
		echo "<option value='TEXT'>"._QXZ("TEXT")."</option>\n";
		echo "<option value='AREA'>"._QXZ("AREA")."</option>\n";
		echo "<option value='SELECT'>"._QXZ("SELECT")."</option>\n";
		echo "<option value='MULTI'>"._QXZ("MULTI")."</option>\n";
		echo "<option value='RADIO'>"._QXZ("RADIO")."</option>\n";
		echo "<option value='CHECKBOX'>"._QXZ("CHECKBOX")."</option>\n";
		echo "<option value='DATE'>"._QXZ("DATE")."</option>\n";
		echo "<option value='TIME'>"._QXZ("TIME")."</option>\n";
		echo "<option value='DISPLAY'>"._QXZ("DISPLAY")."</option>\n";
		echo "<option value='SCRIPT'>"._QXZ("SCRIPT")."</option>\n";
		echo "<option value='HIDDEN'>"._QXZ("HIDDEN")."</option>\n";
		echo "<option value='HIDEBLOB'>"._QXZ("HIDEBLOB")."</option>\n";
		echo "<option value='SWITCH'>"._QXZ("SWITCH")."</option>\n";
		echo "<option value='READONLY'>"._QXZ("READONLY")."</option>\n";
		echo "<option value='SOURCESELECT'>"._QXZ("SOURCESELECT")."</option>\n";
		echo "<option value='BUTTON'>"._QXZ("BUTTON")."</option>\n";
		echo "<option value='$A_field_type[$o]' selected>"._QXZ("$A_field_type[$o]")."</option>\n";
		echo "</select>  $NWB#lists_fields-field_type$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Options")." $A_field_rank[$o]: </td><td align=left><textarea name=field_options ROWS=5 COLS=60>$A_field_options[$o]</textarea>  $NWB#lists_fields-field_options$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Option Position")." $A_field_rank[$o]: </td><td align=left><select size=1 name=multi_position>\n";
		echo "<option value=\"HORIZONTAL\">"._QXZ("HORIZONTAL")."</option>\n";
		echo "<option value=\"VERTICAL\">"._QXZ("VERTICAL")."</option>\n";
		echo "<option value='$A_multi_position[$o]' selected>"._QXZ("$A_multi_position[$o]")."</option>\n";
		echo "</select>  $NWB#lists_fields-multi_position$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Size")." $A_field_rank[$o]: </td><td align=left><input type=text name=field_size size=5 maxlength=3 value=\"$A_field_size[$o]\">  $NWB#lists_fields-field_size$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Max")." $A_field_rank[$o]: </td><td align=left><input type=text name=field_max size=5 maxlength=3 value=\"$A_field_max[$o]\">  $NWB#lists_fields-field_max$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Default")." $A_field_rank[$o]: </td><td align=left><input type=text name=field_default size=50 maxlength=255 value=\"$A_field_default[$o]\">  $NWB#lists_fields-field_default$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Required")." $A_field_rank[$o]: </td><td align=left><select size=1 name=field_required>\n";
		echo "<option value=\"N\">"._QXZ("NO")."</option>\n";
		echo "<option value=\"Y\">"._QXZ("YES")."</option>\n";
		echo "<option value=\"INBOUND_ONLY\">"._QXZ("INBOUND_ONLY")."</option>\n";
		echo "<option selected value='$A_field_required[$o]'>"._QXZ("$A_field_required[$o]")."</option>\n";
		echo "</select>  $NWB#lists_fields-field_required$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Duplicate")." $A_field_rank[$o]: </td><td align=left>"._QXZ("$A_field_duplicate[$o]")."  $NWB#lists_fields-field_duplicate$NWE </td></tr>\n";
		if ($hide_enc < 1)
			{
			if ( ($custom_records_count < 1) and ( ($A_field_type[$o]=='TEXT') or ($A_field_type[$o]=='HIDDEN') or ($A_field_type[$o]=='READONLY') or ($A_field_type[$o]=='HIDEBLOB') or ($A_field_type[$o]=='AREA') or ($A_field_type[$o]=='DATE') or ($A_field_type[$o]=='TIME') ) )
				{
				echo "<tr $bgcolor><td align=right>"._QXZ("Field Encrypt")." $A_field_rank[$o]: </td><td align=left><select size=1 name=field_encrypt>\n";
				echo "<option value=\"Y\">"._QXZ("YES")."</option>\n";
				echo "<option value=\"N\">"._QXZ("NO")."</option>\n";
				echo "<option selected value='$A_field_encrypt[$o]'>"._QXZ("$A_field_encrypt[$o]")."</option>\n";
				echo "</select>  $NWB#lists_fields-field_encrypt$NWE </td></tr>\n";
				}
			else
				{
				echo "<tr $bgcolor><td align=right>"._QXZ("Field Encrypt")." $A_field_rank[$o]: </td><td align=left><input type=hidden name=field_encrypt value=\"$A_field_encrypt[$o]\"> "._QXZ("$A_field_encrypt[$o]")."\n";
				echo "  $NWB#lists_fields-field_encrypt$NWE </td></tr>\n";
				}
			echo "<tr $bgcolor><td align=right>"._QXZ("Field Show Hide").": </td><td align=left><select size=1 name=field_show_hide>\n";
			echo "<option value=\"DISABLED\">"._QXZ("DISABLED")."</option>\n";
			echo "<option value=\"X_OUT_ALL\">"._QXZ("X_OUT_ALL")."</option>\n";
			echo "<option value=\"LAST_1\">"._QXZ("LAST_1")."</option>\n";
			echo "<option value=\"LAST_2\">"._QXZ("LAST_2")."</option>\n";
			echo "<option value=\"LAST_3\">"._QXZ("LAST_3")."</option>\n";
			echo "<option value=\"LAST_4\">"._QXZ("LAST_4")."</option>\n";
			echo "<option value=\"FIRST_1_LAST_4\">"._QXZ("FIRST_1_LAST_4")."</option>\n";
			echo "<option selected value='$A_field_show_hide[$o]'>"._QXZ("$A_field_show_hide[$o]")."</option>\n";
			echo "</select>  $NWB#lists_fields-field_show_hide$NWE </td></tr>\n";
			}
		else
			{
			echo "<tr $bgcolor><td colspan=2>\n";
			echo "<input type=hidden name=field_show_hide value=\"$A_field_show_hide[$o]\">\n";
			echo "<input type=hidden name=field_encrypt value=\"$A_field_encrypt[$o]\">\n";
			echo "</td></tr>\n";
			}

		echo "<tr $bgcolor><td align=center colspan=2><input type=submit name=submit value=\""._QXZ("SUBMIT")."\"> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;\n";
		echo "<B><a href=\"$PHP_SELF?action=DELETE_CUSTOM_FIELD_CONFIRMATION&list_id=$list_id&field_id=$A_field_id[$o]&field_label=$A_field_label[$o]&field_type=$A_field_type[$o]&field_duplicate=$A_field_duplicate[$o]&DB=$DB\">"._QXZ("DELETE THIS CUSTOM FIELD")."</a></B>";
		echo "</td></tr>\n";
		echo "</table></center></form><BR><BR>\n";

		$o++;
		}

	$bgcolor = ' bgcolor=#'.$SSalt_row1_background;

	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<center><TABLE width=$section_width cellspacing=3 cellpadding=1>\n";
	echo "<tr bgcolor=white><td align=center colspan=2>"._QXZ("ADD A NEW CUSTOM FIELD FOR THIS LIST").":</td></tr>\n";
	echo "<tr $bgcolor>\n";
	echo "<input type=hidden name=action value=ADD_CUSTOM_FIELD>\n";
	echo "<input type=hidden name=list_id value=$list_id>\n";
	echo "<input type=hidden name=DB value=$DB>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("New Field Rank").": </td><td align=left><select size=1 name=field_rank>\n";
	echo "$rank_select\n";
	echo "<option selected>$last_rank</option>\n";
	echo "</select> &nbsp; $NWB#lists_fields-field_rank$NWE \n";
	echo " &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Field Order").": <select size=1 name=field_order>\n";
	echo "<option selected>1</option>\n";
	echo "<option>2</option>\n";
	echo "<option>3</option>\n";
	echo "<option>4</option>\n";
	echo "<option>5</option>\n";
	echo "</select> &nbsp; $NWB#lists_fields-field_order$NWE \n";
	echo " &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Re-Rank Fields Below").": <select size=1 name=field_rerank>\n";
	echo "<option calue=\"NO\" selected>"._QXZ("NO")."</option>\n";
	echo "<option calue=\"YES\">"._QXZ("YES")."</option>\n";
	echo "</select> &nbsp; $NWB#lists_fields-field_rerank$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Label").": </td><td align=left><input type=text name=field_label size=20 maxlength=50> $NWB#lists_fields-field_label$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Name").": </td><td align=left><textarea name=field_name rows=2 cols=60></textarea> $NWB#lists_fields-field_name$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Name Position").": </td><td align=left><select size=1 name=name_position>\n";
	echo "<option value=\"LEFT\">"._QXZ("LEFT")."</option>\n";
	echo "<option value=\"TOP\">"._QXZ("TOP")."</option>\n";
	echo "</select>  $NWB#lists_fields-name_position$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Description").": </td><td align=left><input name=field_description type=text size=70 maxlength=100> $NWB#lists_fields-field_description$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Help").": </td><td align=left><textarea name=field_help rows=2 cols=60></textarea> $NWB#lists_fields-field_help$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Type").": </td><td align=left><select size=1 name=field_type>\n";
	echo "<option value='TEXT'>"._QXZ("TEXT")."</option>\n";
	echo "<option value='AREA'>"._QXZ("AREA")."</option>\n";
	echo "<option value='SELECT'>"._QXZ("SELECT")."</option>\n";
	echo "<option value='MULTI'>"._QXZ("MULTI")."</option>\n";
	echo "<option value='RADIO'>"._QXZ("RADIO")."</option>\n";
	echo "<option value='CHECKBOX'>"._QXZ("CHECKBOX")."</option>\n";
	echo "<option value='DATE'>"._QXZ("DATE")."</option>\n";
	echo "<option value='TIME'>"._QXZ("TIME")."</option>\n";
	echo "<option value='DISPLAY'>"._QXZ("DISPLAY")."</option>\n";
	echo "<option value='SCRIPT'>"._QXZ("SCRIPT")."</option>\n";
	echo "<option value='HIDDEN'>"._QXZ("HIDDEN")."</option>\n";
	echo "<option value='HIDEBLOB'>"._QXZ("HIDEBLOB")."</option>\n";
	echo "<option value='SWITCH'>"._QXZ("SWITCH")."</option>\n";
	echo "<option value='READONLY'>"._QXZ("READONLY")."</option>\n";
	echo "<option value='SOURCESELECT'>"._QXZ("SOURCESELECT")."</option>\n";
	echo "<option value='BUTTON'>"._QXZ("BUTTON")."</option>\n";
	echo "<option selected value='TEXT'>"._QXZ("TEXT")."</option>\n";
	echo "</select>  $NWB#lists_fields-field_type$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Options").": </td><td align=left><textarea name=field_options ROWS=5 COLS=60></textarea>  $NWB#lists_fields-field_options$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Option Position").": </td><td align=left><select size=1 name=multi_position>\n";
	echo "<option selected value=\"HORIZONTAL\">"._QXZ("HORIZONTAL")."</option>\n";
	echo "<option value=\"VERTICAL\">"._QXZ("VERTICAL")."</option>\n";
	echo "</select>  $NWB#lists_fields-multi_position$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Size").": </td><td align=left><input type=text name=field_size size=5 maxlength=3>  $NWB#lists_fields-field_size$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Max").": </td><td align=left><input type=text name=field_max size=5 maxlength=3>  $NWB#lists_fields-field_max$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Default").": </td><td align=left><input type=text name=field_default size=50 maxlength=255 value=\"NULL\">  $NWB#lists_fields-field_default$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Required").": </td><td align=left><select size=1 name=field_required>\n";
	echo "<option value=\"N\" SELECTED>"._QXZ("NO")."</option>\n";
	echo "<option value=\"Y\">"._QXZ("YES")."</option>\n";
	echo "<option value=\"INBOUND_ONLY\">"._QXZ("INBOUND_ONLY")."</option>\n";
	echo "</select>  $NWB#lists_fields-field_required$NWE </td></tr>\n";
	echo "<tr $bgcolor><td align=right>"._QXZ("Field Duplicate").": </td><td align=left><select size=1 name=field_duplicate>\n";
	echo "<option value=\"N\" SELECTED>"._QXZ("NO")."</option>\n";
	echo "<option value=\"Y\">"._QXZ("YES")."</option>\n";
	echo "</select>  $NWB#lists_fields-field_duplicate$NWE </td></tr>\n";
	if ($hide_enc < 1)
		{
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Encrypt").": </td><td align=left><select size=1 name=field_encrypt>\n";
		echo "<option value=\"Y\">"._QXZ("YES")."</option>\n";
		echo "<option selected value=\"N\">"._QXZ("NO")."</option>\n";
		echo "</select>  $NWB#lists_fields-field_encrypt$NWE </td></tr>\n";
		echo "<tr $bgcolor><td align=right>"._QXZ("Field Show Hide").": </td><td align=left><select size=1 name=field_show_hide>\n";
		echo "<option selected value=\"DISABLED\">"._QXZ("DISABLED")."</option>\n";
		echo "<option value=\"X_OUT_ALL\">"._QXZ("X_OUT_ALL")."</option>\n";
		echo "<option value=\"LAST_1\">"._QXZ("LAST_1")."</option>\n";
		echo "<option value=\"LAST_2\">"._QXZ("LAST_2")."</option>\n";
		echo "<option value=\"LAST_3\">"._QXZ("LAST_3")."</option>\n";
		echo "<option value=\"LAST_4\">"._QXZ("LAST_4")."</option>\n";
		echo "<option value=\"FIRST_1_LAST_4\">"._QXZ("FIRST_1_LAST_4")."</option>\n";
		echo "</select>  $NWB#lists_fields-field_show_hide$NWE </td></tr>\n";
		}
	else
		{
		echo "<tr $bgcolor><td colspan=2>\n";
		echo "<input type=hidden name=field_show_hide value=\"DISABLED\">\n";
		echo "<input type=hidden name=field_encrypt value=\"N\">\n";
		echo "</td></tr>\n";
		}
	echo "<tr $bgcolor><td align=center colspan=2><input type=submit name=submit value=\""._QXZ("Submit")."\"></td></tr>\n";
	echo "</table></center></form><BR><BR>\n";
	echo "</table></center><BR><BR>\n";
	echo "</TABLE>\n";

	echo "&nbsp; <a href=\"./admin.php?ADD=311&list_id=$list_id\">"._QXZ("Go to the list modification page for this list")."</a><BR><BR>\n";

	echo "&nbsp; <a href=\"$PHP_SELF?action=ADMIN_LOG&list_id=$list_id\">"._QXZ("Click here to see Admin changes to this lists custom fields")."</a><BR><BR><BR> </center> &nbsp; \n";
	}
### END modify custom fields for list




################################################################################
##### BEGIN list lists as well as the number of custom fields in each list
if ($action == "LIST")
	{
	$stmt="SELECT list_id,list_name,active,campaign_id from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$lists_to_print = mysqli_num_rows($rslt);
	$A_list_id = array();
	$A_list_name = array();
	$A_active = array();
	$A_campaign_id = array();
	$o=0;
	while ($lists_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$A_list_id[$o] =		$rowx[0];
		$A_list_name[$o] =		$rowx[1];
		$A_active[$o] =			$rowx[2];
		$A_campaign_id[$o] =	$rowx[3];
		$o++;
		}

	echo "<br>"._QXZ("LIST LISTINGS WITH CUSTOM FIELDS COUNT").":\n";
	echo "<center><TABLE width=$section_width cellspacing=0 cellpadding=1>\n";
	echo "<TR BGCOLOR=BLACK>";
	echo "<TD align=right><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("LIST ID")."</B></TD>";
	echo "<TD align=right><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("LIST NAME")."</B></TD>";
	echo "<TD align=right><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("ACTIVE")."</B></TD>";
	echo "<TD align=right><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("CAMPAIGN")."</B></TD>\n";
	echo "<TD align=right><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("CUSTOM FIELDS")."</B></TD>\n";
	echo "<TD align=right><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("MODIFY")."</TD>\n";
	echo "</TR>\n";

	$o=0;
	while ($lists_to_print > $o) 
		{
		$A_list_fields_count[$o]=0;
		$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$A_list_id[$o]';";
		if ($DB>0) {echo "$stmt";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$fieldscount_to_print = mysqli_num_rows($rslt);
		if ($fieldscount_to_print > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$A_list_fields_count[$o] =	$rowx[0];
			}
		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='class="records_list_x"';} 
		else
			{$bgcolor='class="records_list_y"';}
		echo "<tr $bgcolor align=right"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$PHP_SELF?action=MODIFY_CUSTOM_FIELDS&list_id=$A_list_id[$o]'\"";} echo "><td><a href=\"admin.php?ADD=311&list_id=$A_list_id[$o]\"><font size=1 color=black>$A_list_id[$o]</a></td>";
		echo "<td align=right><font size=1> $A_list_name[$o]</td>";
		echo "<td align=right><font size=1> "._QXZ("$A_active[$o]")."</td>";
		echo "<td align=right><font size=1> $A_campaign_id[$o]</td>";
		echo "<td align=right><font size=1> $A_list_fields_count[$o]</td>";
		echo "<td align=right><font size=1><a href=\"$PHP_SELF?action=MODIFY_CUSTOM_FIELDS&list_id=$A_list_id[$o]\">"._QXZ("MODIFY FIELDS")."</a></td></tr>\n";

		$o++;
		}

	echo "</TABLE></center>\n";
	}
### END list lists as well as the number of custom fields in each list





################################################################################
##### BEGIN admin log display
if ($action == "ADMIN_LOG")
	{
	if ($LOGuser_level >= 9)
		{
		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		$stmt="SELECT admin_log_id,event_date,user,ip_address,event_section,event_type,record_id,event_code from vicidial_admin_log where event_section='CUSTOM_FIELDS' and record_id='$list_id' order by event_date desc limit 10000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$logs_to_print = mysqli_num_rows($rslt);

		echo "<br>"._QXZ("ADMIN CHANGE LOG: Section Records")." - $category - $stage\n";
		echo "<center><TABLE width=$section_width cellspacing=0 cellpadding=1>\n";
		echo "<TR BGCOLOR=BLACK>";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("ID")."</B></TD>";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("DATE TIME")."</B></TD>";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("USER")."</B></TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("IP")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("SECTION")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("TYPE")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("RECORD ID")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("DESCRIPTION")."</TD>\n";
		echo "<TD><B><FONT FACE=\"Arial,Helvetica\" size=1 color=white>"._QXZ("GOTO")."</TD>\n";
		echo "</TR>\n";

		$logs_printed = '';
		$o=0;
		while ($logs_to_print > $o)
			{
			$row=mysqli_fetch_row($rslt);

			if (preg_match('/USER|AGENT/i', $row[4])) {$record_link = "$PHP_SELF?ADD=3&user=$row[6]";}
			if (preg_match('/CAMPAIGN/i', $row[4])) {$record_link = "$PHP_SELF?ADD=31&campaign_id=$row[6]";}
			if (preg_match('/LIST/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311&list_id=$row[6]";}
			if (preg_match('/CUSTOM_FIELDS/i', $row[4])) {$record_link = "./admin_lists_custom.php?action=MODIFY_CUSTOM_FIELDS&list_id=$row[6]";}
			if (preg_match('/SCRIPT/i', $row[4])) {$record_link = "$PHP_SELF?ADD=3111111&script_id=$row[6]";}
			if (preg_match('/FILTER/i', $row[4])) {$record_link = "$PHP_SELF?ADD=31111111&lead_filter_id=$row[6]";}
			if (preg_match('/INGROUP/i', $row[4])) {$record_link = "$PHP_SELF?ADD=3111&group_id=$row[6]";}
			if (preg_match('/DID/i', $row[4])) {$record_link = "$PHP_SELF?ADD=3311&did_id=$row[6]";}
			if (preg_match('/USERGROUP/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311111&user_group=$row[6]";}
			if (preg_match('/REMOTEAGENT/i', $row[4])) {$record_link = "$PHP_SELF?ADD=31111&remote_agent_id=$row[6]";}
			if (preg_match('/PHONE/i', $row[4])) {$record_link = "$PHP_SELF?ADD=10000000000";}
			if (preg_match('/CALLTIME/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311111111&call_time_id=$row[6]";}
			if (preg_match('/SHIFT/i', $row[4])) {$record_link = "$PHP_SELF?ADD=331111111&shift_id=$row[6]";}
			if (preg_match('/CONFTEMPLATE/i', $row[4])) {$record_link = "$PHP_SELF?ADD=331111111111&template_id=$row[6]";}
			if (preg_match('/CARRIER/i', $row[4])) {$record_link = "$PHP_SELF?ADD=341111111111&carrier_id=$row[6]";}
			if (preg_match('/SERVER/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311111111111&server_id=$row[6]";}
			if (preg_match('/CONFERENCE/i', $row[4])) {$record_link = "$PHP_SELF?ADD=1000000000000";}
			if (preg_match('/SYSTEM/i', $row[4])) {$record_link = "$PHP_SELF?ADD=311111111111111";}
			if (preg_match('/CATEGOR/i', $row[4])) {$record_link = "$PHP_SELF?ADD=331111111111111";}
			if (preg_match('/GROUPALIAS/i', $row[4])) {$record_link = "$PHP_SELF?ADD=33111111111&group_alias_id=$row[6]";}

			if (preg_match('/1$|3$|5$|7$|9$/i', $o))
				{$bgcolor='class="records_list_x"';} 
			else
				{$bgcolor='class="records_list_y"';}
			echo "<tr $bgcolor"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='admin.php?ADD=730000000000000&stage=$row[0]'\"";} echo "><td><a href=\"admin.php?ADD=730000000000000&stage=$row[0]\"><font size=1 color=black>$row[0]</a></td>";
			echo "<td><font size=1> $row[1]</td>";
			echo "<td><font size=1> <a href=\"admin.php?ADD=710000000000000&stage=$row[2]\">$row[2]</a></td>";
			echo "<td><font size=1> $row[3]</td>";
			echo "<td><font size=1> $row[4]</td>";
			echo "<td><font size=1> $row[5]</td>";
			echo "<td><font size=1> $row[6]</td>";
			echo "<td><font size=1> $row[7]</td>";
			echo "<td><font size=1> <a href=\"$record_link\">GOTO</a></td>";
			echo "</tr>\n";
			$logs_printed .= "'$row[0]',";
			$o++;
			}
		echo "</TABLE><BR><BR>\n";
		echo "\n";
		echo "</center>\n";
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}





$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").": $RUNtime "._QXZ("seconds")." &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

?>

</body>
</html>


<?php
################################################################################
################################################################################
##### Functions
################################################################################
################################################################################




################################################################################
##### BEGIN add field function
function add_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$field_id,$list_id,$field_label,$field_name,$field_description,$field_rank,$field_help,$field_type,$field_options,$field_size,$field_max,$field_default,$field_required,$field_cost,$multi_position,$name_position,$field_order,$field_encrypt,$field_show_hide,$field_duplicate,$vicidial_list_fields,$mysql_reserved_words,$field_rerank)
	{
	$table_exists=0;
	$stmt="SHOW TABLES LIKE \"custom_$list_id\";";
	$rslt=mysql_to_mysqli($stmt, $link);
	$tablecount_to_print = mysqli_num_rows($rslt);
	if ($tablecount_to_print > 0)
		{$table_exists =	1;}
	if ($DB>0) {echo "$stmt|$tablecount_to_print|$table_exists";}

	if ($table_exists < 1)
		{$field_sql = "CREATE TABLE custom_$list_id (lead_id INT(9) UNSIGNED PRIMARY KEY NOT NULL, $field_label ";}
	else
		{$field_sql = "ALTER TABLE custom_$list_id ADD $field_label ";}

	if ($field_encrypt=='Y')
		{
		# override encrypt setting for non-encryptable field types
		if ( ( ($field_type!='TEXT') and ($field_type!='HIDDEN') and ($field_type!='READONLY') and ($field_type!='HIDEBLOB') and ($field_type!='AREA') and ($field_type!='DATE') and ($field_type!='TIME') ) or ($field_duplicate=='Y') )
			{
			$field_encrypt='N';
			}
		}

	$field_options_ENUM='';
	$field_cost=1;
	if ( ($field_type=='SELECT') or ($field_type=='SOURCESELECT') or ($field_type=='RADIO') )
		{
		$field_options_array = explode("\n",$field_options);
		$field_options_count = count($field_options_array);
		$te=0;
		while ($te < $field_options_count)
			{
			if (preg_match("/,|\|/",$field_options_array[$te]))
				{
				if ($field_type=='SOURCESELECT')
					{
					$field_options_value_array = explode('|',$field_options_array[$te]);
					$field_options_value_array[0] = preg_replace("/^option=>/i",'',$field_options_value_array[0]);
					}
				else
					{$field_options_value_array = explode(",",$field_options_array[$te]);}
				$field_options_ENUM .= "'$field_options_value_array[0]',";
				}
			$te++;
			}
		$field_options_ENUM = preg_replace("/.$/",'',$field_options_ENUM);
		$field_sql .= "ENUM($field_options_ENUM) ";
		$field_cost = strlen($field_options_ENUM);
		}
	if ( ($field_type=='MULTI') or ($field_type=='CHECKBOX') )
		{
		$field_options_array = explode("\n",$field_options);
		$field_options_count = count($field_options_array);
		$te=0;
		while ($te < $field_options_count)
			{
			if (preg_match("/,/",$field_options_array[$te]))
				{
				$field_options_value_array = explode(",",$field_options_array[$te]);
				$field_options_ENUM .= "'$field_options_value_array[0]',";
				}
			$te++;
			}
		$field_options_ENUM = preg_replace("/.$/",'',$field_options_ENUM);
		$field_cost = strlen($field_options_ENUM);
		if ($field_cost < 1) {$field_cost=1;};
		$field_sql .= "VARCHAR($field_cost) ";
		}
	if ( ($field_type=='TEXT') or ($field_type=='HIDDEN') or ($field_type=='READONLY') )
		{
		if ($field_max < 1) {$field_max=1;};
		$field_maxSQL = $field_max;
		if ($field_encrypt == 'Y')
			{
			if ($field_max < 8) {$field_maxSQL=30;}
			else {$field_maxSQL = ($field_max * 4);}
			}
		$field_sql .= "VARCHAR($field_maxSQL) ";
		$field_cost = ($field_maxSQL + $field_cost);
		}
	if ($field_type=='HIDEBLOB')
		{
		$field_sql .= "BLOB ";
		$field_cost = 15;
		}
	if ($field_type=='AREA') 
		{
		$field_sql .= "TEXT ";
		$field_cost = 15;
		}
	if ($field_type=='DATE') 
		{
		if ($field_encrypt == 'Y')
			{
			$field_sql .= "VARCHAR(30) ";
			$field_cost = 31;
			}
		else
			{
			$field_sql .= "DATE ";
			$field_cost = 10;
			}
		}
	if ($field_type=='TIME') 
		{
		if ($field_encrypt == 'Y')
			{
			$field_sql .= "VARCHAR(30) ";
			$field_cost = 31;
			}
		else
			{
			$field_sql .= "TIME ";
			$field_cost = 8;
			}
		}
	$field_cost = ($field_cost * 3); # account for utf8 database

	if ( ($field_default != 'NULL') and ($field_type!='AREA') and ($field_type!='DATE') and ($field_type!='TIME') )
		{
		if ( ($field_encrypt=='N') or ( ($field_encrypt=='Y') and ($field_type!='TEXT') and ($field_type!='HIDDEN') and ($field_type!='READONLY') and ($field_type!='HIDEBLOB') ) )
			{$field_sql .= "default '$field_default'";}
		}

	if ($table_exists < 1)
		{$field_sql .= ");";}
	else
		{$field_sql .= ";";}

	$SQLexecuted=0;

	if ( ($field_type=='DISPLAY') or ($field_type=='SCRIPT') or ($field_type=='SWITCH') or ($field_type=='BUTTON') or (preg_match("/\|$field_label\|/i",$vicidial_list_fields)) or ($field_duplicate=='Y') )
		{
		if ($DB) {echo "Non-DB $field_type field type, $field_label\n";}

		if ($table_exists < 1)
			{$field_sql = "CREATE TABLE custom_$list_id (lead_id INT(9) UNSIGNED PRIMARY KEY NOT NULL);";}
		else
			{$SQLexecuted++;}
		}
	if ($SQLexecuted < 1)
		{
		$stmtCUSTOM="$field_sql";
		$rsltCUSTOM=mysql_to_mysqli($stmtCUSTOM, $linkCUSTOM);
		$table_update = mysqli_affected_rows($linkCUSTOM);
		if ($DB) {echo "$table_update|$stmtCUSTOM\n";}
		if (!$rsltCUSTOM) 
			{
			echo(_QXZ("Could not execute").': ' . mysqli_error()) . "|$stmtCUSTOM|<BR><B>"._QXZ("FIELD NOT ADDED, PLEASE GO BACK AND TRY AGAIN")."</b>";
			
			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|$stmtCUSTOM";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$user', ip_address='$ip', event_section='CUSTOM_FIELDS', event_type='OTHER', record_id='$list_id', event_code='ADMIN ADD CUSTOM LIST FIELD ERROR', event_sql=\"$SQL_log\", event_notes='ADD ERROR:" . mysqli_error() . "';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		else
			{$SQLexecuted++;}
		}

	if ($SQLexecuted > 0)
		{
		$stmt="INSERT INTO vicidial_lists_fields set field_label='$field_label',field_name='$field_name',field_description='$field_description',field_rank='$field_rank',field_help='$field_help',field_type='$field_type',field_options=\"$field_options\",field_size='$field_size',field_max='$field_max',field_default='$field_default',field_required='$field_required',field_cost='$field_cost',list_id='$list_id',multi_position='$multi_position',name_position='$name_position',field_order='$field_order',field_encrypt='$field_encrypt',field_show_hide='$field_show_hide',field_duplicate='$field_duplicate';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$field_update = mysqli_affected_rows($link);
		$field_id = mysqli_insert_id($link);
		if ($DB) {echo "$field_update|$stmt\n";}
		if (!$rslt) {echo(_QXZ("Could not execute").': ' . mysqli_error()) . "|$stmt|";}

		### BEGIN if "field_rerank" enabled, check for fields with >= field_rank and move them
		$rerankSQL='';
		$rerankNOTES='';
		if ($field_rerank == 'YES')
			{
			$reranking_done=0;
			$rerank_temp_rank = $field_rank;
			$rerank_last_field_ids = "'$field_id'";
			while ( ($reranking_done < 1) or ($rerank_temp_rank > 1000) )
				{
				$reranks_to_print=0;
				$stmtRS="SELECT field_id,field_label from vicidial_lists_fields where list_id='$list_id' and field_rank='$rerank_temp_rank' and field_id NOT IN($rerank_last_field_ids) order by field_id;";
				$rslt=mysql_to_mysqli($stmtRS, $link);
				$reranks_to_print = mysqli_num_rows($rslt);
				if ($DB) {echo "$stmt|$reranks_to_print\n";}
				$R_field_id = array();
				$R_field_label = array();
				$o=0;
				while ($reranks_to_print > $o) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$R_field_id[$o] =		$rowx[0];
					$R_field_label[$o] =	$rowx[1];
					$o++;
					}
				if ($reranks_to_print < 1) {$reranking_done++;}
				$rerank_temp_rank++;
				$o=0;
				while ($reranks_to_print > $o) 
					{
					$stmtR="UPDATE vicidial_lists_fields set field_rank='$rerank_temp_rank' where list_id='$list_id' and field_id='$R_field_id[$o]';";
					$rslt=mysql_to_mysqli($stmtR, $link);
					$ranks_update = mysqli_affected_rows($link);
					if ($DB) {echo "$ranks_update|$stmtR\n";}
					if (!$rslt) {echo('Could not execute: ' . mysqli_error()) . "|$stmt|";}
					$rerankSQL .= "$stmtR|";
					$rerankNOTES .= "Field $R_field_id[$o]($R_field_label[$o]) moved to rank $rerank_temp_rank|";
					$rerank_last_field_ids .= ",'$R_field_id[$o]'";

					$o++;
					}
				}
			}
		### END if "field_rerank" enabled, check for fields with >= field_rank and move them

		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|$stmtCUSTOM|$rerankSQL";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$user', ip_address='$ip', event_section='CUSTOM_FIELDS', event_type='ADD', record_id='$list_id', event_code='ADMIN ADD CUSTOM LIST FIELD', event_sql=\"$SQL_log\", event_notes='Rows updated: $field_update($field_id)   $rerankNOTES';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}
	
	return $SQLexecuted;
	}
##### END add field function





################################################################################
##### BEGIN modify field function
function modify_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$field_id,$list_id,$field_label,$field_name,$field_description,$field_rank,$field_help,$field_type,$field_options,$field_size,$field_max,$field_default,$field_required,$field_cost,$multi_position,$name_position,$field_order,$field_encrypt,$field_show_hide,$field_duplicate,$vicidial_list_fields,$field_rerank)
	{
	$field_db_exists=0;
	if ( ($field_type=='DISPLAY') or ($field_type=='SCRIPT') or ($field_type=='SWITCH') or ($field_type=='BUTTON') or (preg_match("/\|$field_label\|/i",$vicidial_list_fields)) or ($field_duplicate=='Y') )
		{$field_db_exists=1;}
	else
		{
		$stmt="SHOW COLUMNS from custom_$list_id LIKE '$field_label';";
		if ($DB>0) {echo "$stmt";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$field_db_exists = mysqli_num_rows($rslt);
		}
	if ($field_db_exists > 0)
		{$field_sql = "ALTER TABLE custom_$list_id MODIFY $field_label ";}
	else
		{$field_sql = "ALTER TABLE custom_$list_id ADD $field_label ";}

	if ($field_duplicate=='Y')
		{$field_type='TEXT';}
	if ($field_encrypt=='Y')
		{
		# override encrypt setting for non-encryptable field types
		if ( ( ($field_type!='TEXT') and ($field_type!='HIDDEN') and ($field_type!='READONLY') and ($field_type!='HIDEBLOB') and ($field_type!='AREA') and ($field_type!='DATE') and ($field_type!='TIME') ) or ($field_duplicate=='Y') )
			{
			$field_encrypt='N';
			}
		}

	$field_options_ENUM='';
	$field_cost=1;
	if ( ($field_type=='SELECT') or ($field_type=='SOURCESELECT') or ($field_type=='RADIO') )
		{
		$field_options_array = explode("\n",$field_options);
		$field_options_count = count($field_options_array);
		$te=0;
		while ($te < $field_options_count)
			{
			if (preg_match("/,|\|/",$field_options_array[$te]))
				{
				if ($field_type=='SOURCESELECT')
					{
					$field_options_value_array = explode('|',$field_options_array[$te]);
					$field_options_value_array[0] = preg_replace("/^option=>/i",'',$field_options_value_array[0]);
					}
				else
					{$field_options_value_array = explode(",",$field_options_array[$te]);}
				$field_options_ENUM .= "'$field_options_value_array[0]',";
				}
			$te++;
			}
		$field_options_ENUM = preg_replace("/.$/",'',$field_options_ENUM);
		$field_sql .= "ENUM($field_options_ENUM) ";
		$field_cost = strlen($field_options_ENUM);
		}
	if ( ($field_type=='MULTI') or ($field_type=='CHECKBOX') )
		{
		$field_options_array = explode("\n",$field_options);
		$field_options_count = count($field_options_array);
		$te=0;
		while ($te < $field_options_count)
			{
			if (preg_match("/,/",$field_options_array[$te]))
				{
				$field_options_value_array = explode(",",$field_options_array[$te]);
				$field_options_ENUM .= "'$field_options_value_array[0]',";
				}
			$te++;
			}
		$field_options_ENUM = preg_replace("/.$/",'',$field_options_ENUM);
		$field_cost = strlen($field_options_ENUM);
		$field_sql .= "VARCHAR($field_cost) ";
		}
	if ( ($field_type=='TEXT') or ($field_type=='HIDDEN') or ($field_type=='READONLY') )
		{
		if ($field_max < 1) {$field_max=1;};
		$field_maxSQL = $field_max;
		if ($field_encrypt == 'Y')
			{
			if ($field_max < 8) {$field_maxSQL=30;}
			else {$field_maxSQL = ($field_max * 4);}
			}
		$field_sql .= "VARCHAR($field_maxSQL) ";
		$field_cost = ($field_maxSQL + $field_cost);
		}
	if ($field_type=='HIDEBLOB')
		{
		$field_sql .= "BLOB ";
		$field_cost = 15;
		}
	if ($field_type=='AREA') 
		{
		$field_sql .= "TEXT ";
		$field_cost = 15;
		}
	if ($field_type=='DATE') 
		{
		if ($field_encrypt == 'Y')
			{
			$field_sql .= "VARCHAR(30) ";
			$field_cost = 31;
			}
		else
			{
			$field_sql .= "DATE ";
			$field_cost = 10;
			}
		}
	if ($field_type=='TIME') 
		{
		if ($field_encrypt == 'Y')
			{
			$field_sql .= "VARCHAR(30) ";
			$field_cost = 31;
			}
		else
			{
			$field_sql .= "TIME ";
			$field_cost = 8;
			}
		}
	$field_cost = ($field_cost * 3); # account for utf8 database

	if ( ($field_default == 'NULL') or ($field_type=='AREA') or ($field_type=='DATE') or ($field_type=='TIME') )
		{$field_sql .= ";";}
	else
		{
		if ( ($field_encrypt=='N') or ( ($field_encrypt=='Y') and ($field_type!='TEXT') and ($field_type!='HIDDEN') and ($field_type!='READONLY') and ($field_type!='HIDEBLOB') ) )
			{$field_sql .= "default '$field_default';";}
		else
			{$field_sql .= ";";}
		}

	$SQLexecuted=0;

	if ( ($field_type=='DISPLAY') or ($field_type=='SCRIPT') or ($field_type=='SWITCH') or ($field_type=='BUTTON') or (preg_match("/\|$field_label\|/i",$vicidial_list_fields)) or ($field_duplicate=='Y') )
		{
		if ($DB) {echo _QXZ("Non-DB")." $field_type "._QXZ("field type").", $field_label\n";}
		$SQLexecuted++;
		}
	else
		{
		$stmtCUSTOM="$field_sql";
		$rsltCUSTOM=mysql_to_mysqli($stmtCUSTOM, $linkCUSTOM);
		$field_update = mysqli_affected_rows($linkCUSTOM);
		if ($DB) {echo "$field_update|$stmtCUSTOM\n";}
		if (!$rsltCUSTOM) {echo(_QXZ("Could not execute").': ' . mysqli_error()) . "|$stmtCUSTOM|<BR><B>"._QXZ("FIELD NOT MODIFIED, PLEASE GO BACK AND TRY AGAIN")."</b>";
			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|$stmtCUSTOM";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$user', ip_address='$ip', event_section='CUSTOM_FIELDS', event_type='OTHER', record_id='$list_id', event_code='ADMIN MODIFY CUSTOM LIST FIELD ERROR', event_sql=\"$SQL_log\", event_notes='MODIFY ERROR:" . mysqli_error() . "';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		else
			{$SQLexecuted++;}
		}

	if ($SQLexecuted > 0)
		{
		$stmt="UPDATE vicidial_lists_fields set field_label='$field_label',field_name='$field_name',field_description='$field_description',field_rank='$field_rank',field_help='$field_help',field_type='$field_type',field_options=\"$field_options\",field_size='$field_size',field_max='$field_max',field_default='$field_default',field_required='$field_required',field_cost='$field_cost',multi_position='$multi_position',name_position='$name_position',field_order='$field_order',field_encrypt='$field_encrypt',field_show_hide='$field_show_hide',field_duplicate='$field_duplicate' where list_id='$list_id' and field_id='$field_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$field_update = mysqli_affected_rows($link);
		if ($DB) {echo "$field_update|$stmt\n";}
		if (!$rslt) {echo('Could not execute: ' . mysqli_error()) . "|$stmt|";}

		### BEGIN if "field_rerank" enabled, check for fields with >= field_rank and move them
		$rerankSQL='';
		$rerankNOTES='';
		if ($field_rerank == 'YES')
			{
			$reranking_done=0;
			$rerank_temp_rank = $field_rank;
			$rerank_last_field_ids = "'$field_id'";
			while ( ($reranking_done < 1) or ($rerank_temp_rank > 1000) )
				{
				$reranks_to_print=0;
				$stmtRS="SELECT field_id,field_label from vicidial_lists_fields where list_id='$list_id' and field_rank='$rerank_temp_rank' and field_id NOT IN($rerank_last_field_ids) order by field_id;";
				$rslt=mysql_to_mysqli($stmtRS, $link);
				$reranks_to_print = mysqli_num_rows($rslt);
				if ($DB) {echo "$stmt|$reranks_to_print\n";}
				$R_field_id = array();
				$R_field_label = array();
				$o=0;
				while ($reranks_to_print > $o) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$R_field_id[$o] =		$rowx[0];
					$R_field_label[$o] =	$rowx[1];
					$o++;
					}
				if ($reranks_to_print < 1) {$reranking_done++;}
				$rerank_temp_rank++;
				$o=0;
				while ($reranks_to_print > $o) 
					{
					$stmtR="UPDATE vicidial_lists_fields set field_rank='$rerank_temp_rank' where list_id='$list_id' and field_id='$R_field_id[$o]';";
					$rslt=mysql_to_mysqli($stmtR, $link);
					$ranks_update = mysqli_affected_rows($link);
					if ($DB) {echo "$ranks_update|$stmtR\n";}
					if (!$rslt) {echo('Could not execute: ' . mysqli_error()) . "|$stmt|";}
					$rerankSQL .= "$stmtR|";
					$rerankNOTES .= "Field $R_field_id[$o]($R_field_label[$o]) moved to rank $rerank_temp_rank|";
					$rerank_last_field_ids .= ",'$R_field_id[$o]'";

					$o++;
					}
				}
			}
		### END if "field_rerank" enabled, check for fields with >= field_rank and move them

		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|$stmtCUSTOM|$rerankSQL";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$user', ip_address='$ip', event_section='CUSTOM_FIELDS', event_type='MODIFY', record_id='$list_id', event_code='ADMIN MODIFY CUSTOM LIST FIELD', event_sql=\"$SQL_log\", event_notes='Rows updated: $field_update($field_id)   $rerankNOTES';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}
	
	return $SQLexecuted;
	}
##### END modify field function





################################################################################
##### BEGIN delete field function
function delete_field_function($DB,$link,$linkCUSTOM,$ip,$user,$table_exists,$field_id,$list_id,$field_label,$field_name,$field_description,$field_rank,$field_help,$field_type,$field_options,$field_size,$field_max,$field_default,$field_required,$field_cost,$multi_position,$name_position,$field_order,$field_encrypt,$field_show_hide,$field_duplicate,$vicidial_list_fields)
	{
	$SQLexecuted=0;

	if ( ($field_type=='DISPLAY') or ($field_type=='SCRIPT') or ($field_type=='SWITCH') or ($field_type=='BUTTON') or (preg_match("/\|$field_label\|/i",$vicidial_list_fields)) or ($field_duplicate=='Y') )
		{
		if ($DB) {echo "Non-DB $field_type field type, $field_label\n";}
		$SQLexecuted++;
		}
	else
		{
		$stmtCUSTOM="ALTER TABLE custom_$list_id DROP $field_label;";
		$rsltCUSTOM=mysql_to_mysqli($stmtCUSTOM, $linkCUSTOM);
		$table_update = mysqli_affected_rows($linkCUSTOM);
		if ($DB) {echo "$table_update|$stmtCUSTOM\n";}
		if (!$rsltCUSTOM) {echo(_QXZ("Could not execute").': ' . mysqli_error()) . "|$stmtCUSTOM|<BR><B>"._QXZ("FIELD NOT DELETED, PLEASE GO BACK AND TRY AGAIN")."</b>";
			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|$stmtCUSTOM";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$user', ip_address='$ip', event_section='CUSTOM_FIELDS', event_type='OTHER', record_id='$list_id', event_code='ADMIN DELETE CUSTOM LIST FIELD ERROR', event_sql=\"$SQL_log\", event_notes='DELETE ERROR:" . mysqli_error() . "';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		else
			{$SQLexecuted++;}
		}

	if ($SQLexecuted > 0)
		{
		$stmt="DELETE FROM vicidial_lists_fields WHERE field_label='$field_label' and field_id='$field_id' and list_id='$list_id' LIMIT 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$field_update = mysqli_affected_rows($link);
		if ($DB) {echo "$field_update|$stmt\n";}
		if (!$rslt) {echo(_QXZ("Could not execute").': ' . mysqli_error() . "|$stmt|");}

		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|$stmtCUSTOM";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$user', ip_address='$ip', event_section='CUSTOM_FIELDS', event_type='DELETE', record_id='$list_id', event_code='ADMIN DELETE CUSTOM LIST FIELD', event_sql=\"$SQL_log\", event_notes='';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}
	
	return $SQLexecuted;
	}
##### END delete field function
