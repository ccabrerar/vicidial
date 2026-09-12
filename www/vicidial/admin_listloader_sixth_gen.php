<?php
# admin_listloader_sixth_gen.php - version 6.0
# AJAX-based lead loader with separate progress polling
# Based on admin_listloader_fifth_gen.php
#
# Copyright (C) 2026  Matt Florell,Joe Johnson <vicidial@gmail.com>    LICENSE: AGPLv2
#
# ViciDial web-based lead loader from formatted file
# 
# CHANGES
# 50602-1640 - First version created by Joe Johnson
# 51128-1108 - Removed PHP global vars requirement
# 60113-1603 - Fixed a few bugs in Excel import
# 60421-1624 - check GET/POST vars lines with isset to not trigger PHP NOTICES
# 60616-1240 - added listID override
# 60616-1604 - added gmt lookup for each lead
# 60619-1651 - Added variable filtering to eliminate SQL injection attack threat
# 60822-1121 - fixed for nonwritable directories
# 60906-1100 - added filter of non-digits in alt_phone field
# 61110-1222 - added new USA-Canada DST scheme and Brazil DST scheme
# 61128-1149 - added postal code GMT lookup and duplicate check options
# 70417-1059 - Fixed default phone_code bug
# 70510-1518 - Added campaign and system duplicate check and phonecode override
# 80428-0417 - UTF8 changes
# 80514-1030 - removed filesize limit and raised number of errors to be displayed
# 80713-0023 - added last_local_call_time field default of 2008-01-01
# 81011-2009 - a few bug fixes
# 90309-1831 - Added admin_log logging
# 90310-2128 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 90522-0506 - Security fix
# 90721-1339 - Added rank and owner as vicidial_list fields
# 91112-0616 - Added title/alt-phone duplicate checking
# 100118-0543 - Added new Australian and New Zealand DST schemes (FSO-FSA and LSS-FSA)
# 100621-1026 - Added admin_web_directory variable
# 100630-1609 - Added a check for invalid ListIds and filtered out ' " ; ` \ from the field <mikec>
# 100705-1507 - Added custom fields to field chooser, only when liast_id_override is used and only with TXT and CSV file formats
# 100706-1250 - Forked script to create new script that will only load TXT(tab-
#				delimited files) and use a perl script to convert others to TXT
# 100707-1040 - Converted List Id Override and Phone Code Override to drop downs <mikec>
# 100707-1156 - Made it so you cannot submit with no lead file selected. Also fixed Start Over Link <mikec>
# 100712-1416 - Added entry_list_id field to vicidial_list to preserve link to custom fields if any
# 100728-0900 - Filtered uploaded filenames for unsupported characters
# 110424-0926 - Added option for time zone code in the owner field
# 110705-1947 - Added USACAN check for prefix and areacode
# 120221-0140 - Added User Group restrictions
# 120223-2318 - Removed logging of good login passwords if webroot writable is enabled
# 120402-2128 - Added template options
# 120525-1038 - Added uploaded filename filtering
# 120529-1348 - Filename filter fix
# 130420-2056 - Added NANPA prefix validation and timezone options
# 130610-0920 - Finalized changing of all ereg instances to preg
# 130621-1817 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130719-1914 - Added SQL to filter by template statuses, if template has specific statuses to dedupe against
# 130802-0619 - Added status deduping option without template
# 130824-2322 - Changed to mysqli PHP functions
# 140214-1022 - Fixed status dedupe bug
# 140328-0007 - Converted division calculations to use MathZDC function
# 141001-2200 - Finalized adding QXZ translation to all admin files
# 141118-1955 - Added more debug output
# 141229-1814 - Added code for on-the-fly language translations display
# 150209-2113 - Added master_list_override option to override template setting
# 150312-1505 - Allow for single quotes in vicidial_list data fields
# 150516-1136 - Fixed conflict with functions.php
# 150728-0732 - Added state fullname to abbreviation conversion feature (state_conversion)
# 150810-0750 - Added compatibility for custom fields data option
# 160102-1039 - Better special characters support
# 160428-2359 - Fixed custom table bug
# 160508-0757 - Added colors features
# 161103-2224 - Added web_loader_phone_length option
# 161114-2315 - Added file upload error checking
# 170219-1427 - Added last-90-day duplicate check options
# 170409-1553 - Added IP List validation code
# 171001-0908 - Fixed issue #1041
# 171204-1517 - Fix for custom field duplicate issue, removed link to old lead loader
# 180324-0943 - Enforce User Group campaign permissions for templates based on list_id
# 180502-2215 - Added new help display
# 180927-0702 - Fixed translation-related issue #1114
# 190503-1547 - Added enable_status_mismatch_leadloader_option
# 200812-1745 - Added international DNC scrub option
# 200922-1013 - Added web_loader_phone_strip system setting feature
# 210210-1602 - Added duplicate check with more X-day options
# 220222-1002 - Added allow_web_debug system setting
# 230210-1844 - Added invalid_phone_override option <admin_listloader_fifth_gen.php started>
# 231207-1446 - Fix for web_loader_phone_strip duplicate check, issue #1498, also changed format to "Custom layout" default
# 240320-1034 - Added misssing input variable filtering
# 240801-1132 - Code updates for PHP8 compatibility
# 260203-1600 - Fix for field chooser issue and more code updates for PHP8 compatibility
# 260415-1710 - Added summarized error output, fuzzy field auto-detection for custom layout, Issue #1561 from Acidshock
# 260514-1553 - Added default_phone_code use for Phone Code field on form
# 260904-1525 - Sixth gen: AJAX architecture with progress polling
# 

$version = '6.0-1';
$build = '260904-1525';

require("dbconnect_mysqli.php");
require("functions.php");

$aliases=array();
$field_match_scores=array(); $min_req_score=70; # default
$field_match_fields=array();
$alias_stmt="select container_entry from vicidial_settings_containers where container_id='LISTLOADER_AUTO_MAPPING'";
$alias_rslt=mysql_to_mysqli($alias_stmt, $link);
if (mysqli_num_rows($alias_rslt)>0)
	{
	$alias_row=mysqli_fetch_row($alias_rslt);
	$container_entry=explode("\n", $alias_row[0]);

	for ($q=0; $q<count($container_entry); $q++)
		{
		if (!preg_match('/^\;/', $container_entry[$q]) && preg_match('/\s\=\>\s/', $container_entry[$q]))
			{
			$alias_entry=explode(" => ", trim($container_entry[$q]));
			
			if (trim($alias_entry[0])=="minimum_required_score") 
				{
				$VD_field=preg_replace('/[^0-9]/', '', $alias_entry[1]);
				$min_req_score=$VD_field;
				}
			else
				{
				$alias_field=preg_replace('/[^a-z0-9]/', '', $alias_entry[0]);
				$VD_field=preg_replace('/[^a-z0-9]/', '', $alias_entry[1]);
				$aliases["$alias_field"]="$VD_field";
				}
			}
		}
	}
if ($min_req_score>100) {$min_req_score=100;}
##### BEGIN helper functions #####

# Fuzzy field matching: maps common CSV header names to vicidial_list field names
function fuzzy_match_field($header_name, $vicidial_field_name)
	{
	global $aliases;

	# Normalize both strings: lowercase, strip non-alphanumeric
	$h = strtolower(trim($header_name));
	$h_clean = preg_replace('/[^a-z0-9]/', '', $h);
	$v = strtolower($vicidial_field_name);
	$v_clean = preg_replace('/[^a-z0-9]/', '', $v);

	# Exact match after normalization
	if ($h_clean == $v_clean) { return 100; }

	# Alias map: common CSV header variations => vicidial field name (cleaned)
	/*
	$aliases = array(
		'phone' => 'phonenumber',
		'phoneno' => 'phonenumber',
		'phone1' => 'phonenumber',
		'primaryphone' => 'phonenumber',
		'mainphone' => 'phonenumber',
		'telephone' => 'phonenumber',
		'tel' => 'phonenumber',
		'cell' => 'phonenumber',
		'cellphone' => 'phonenumber',
		'mobile' => 'phonenumber',
		'mobilephone' => 'phonenumber',
		'workphone' => 'phonenumber',
		'homephone' => 'phonenumber',
		'fname' => 'firstname',
		'first' => 'firstname',
		'givenname' => 'firstname',
		'lname' => 'lastname',
		'last' => 'lastname',
		'surname' => 'lastname',
		'familyname' => 'lastname',
		'mi' => 'middleinitial',
		'middle' => 'middleinitial',
		'middlename' => 'middleinitial',
		'addr' => 'address1',
		'addr1' => 'address1',
		'street' => 'address1',
		'streetaddress' => 'address1',
		'address' => 'address1',
		'addr2' => 'address2',
		'street2' => 'address2',
		'suite' => 'address2',
		'apt' => 'address2',
		'apartment' => 'address2',
		'unit' => 'address2',
		'addr3' => 'address3',
		'zip' => 'postalcode',
		'zipcode' => 'postalcode',
		'postcode' => 'postalcode',
		'postalzip' => 'postalcode',
		'st' => 'state',
		'stateprovince' => 'state',
		'region' => 'state',
		'prov' => 'province',
		'country' => 'countrycode',
		'countrycd' => 'countrycode',
		'cc' => 'countrycode',
		'sex' => 'gender',
		'dob' => 'dateofbirth',
		'birthday' => 'dateofbirth',
		'birthdate' => 'dateofbirth',
		'birth' => 'dateofbirth',
		'altphone' => 'altphone',
		'phone2' => 'altphone',
		'secondaryphone' => 'altphone',
		'otherphone' => 'altphone',
		'alternatephone' => 'altphone',
		'emailaddress' => 'email',
		'emailaddr' => 'email',
		'mail' => 'email',
		'note' => 'comments',
		'notes' => 'comments',
		'comment' => 'comments',
		'remark' => 'comments',
		'remarks' => 'comments',
		'description' => 'comments',
		'vendorcode' => 'vendorleadcode',
		'vendorid' => 'vendorleadcode',
		'vendorleadid' => 'vendorleadcode',
		'leadcode' => 'vendorleadcode',
		'externalid' => 'vendorleadcode',
		'sourcecode' => 'sourceid',
		'source' => 'sourceid',
		'leadsource' => 'sourceid',
		'listid' => 'listid',
		'list' => 'listid',
		'phonecode' => 'phonecode',
		'dialcode' => 'phonecode',
		'countrydialing' => 'phonecode',
		'prefix' => 'title',
		'salutation' => 'title',
		'mr' => 'title',
		'securityphrase' => 'securityphrase',
		'security' => 'securityphrase',
		'pin' => 'securityphrase',
		'password' => 'securityphrase',
		'priority' => 'rank',
		'score' => 'rank',
		'weight' => 'rank',
		'agent' => 'owner',
		'assignedto' => 'owner',
		'rep' => 'owner',
		'town' => 'city',
	);
	*/

	# Check alias match
	if (isset($aliases[$h_clean]) && $aliases[$h_clean] == $v_clean) { return 95; }

	# Check if header contains the field name or vice versa
	if (strlen($h_clean) > 2 && strlen($v_clean) > 2)
		{
		if (strpos($h_clean, $v_clean) !== false) { return 90; }
		if (strpos($v_clean, $h_clean) !== false) { return 85; }
		}

	# Levenshtein distance (for short strings)
	if (strlen($h_clean) < 20 && strlen($v_clean) < 20)
		{
		$lev = levenshtein($h_clean, $v_clean);
		$max_len = max(strlen($h_clean), strlen($v_clean));
		if ($max_len > 0)
			{
			$similarity = (1 - ($lev / $max_len)) * 100;
			if ($similarity >= 75) { return intval($similarity); }
			}
		}

	# similar_text percentage
	similar_text($h_clean, $v_clean, $percent);
	# if ($percent >= 70) { return intval($percent); }
	if ($percent > 0) { return intval($percent); }
	return 0;
	}

# Auto-detect best column index for a given vicidial field from header row
function auto_detect_field_index($vicidial_field_name, $header_columns)
	{
	global $field_match_scores, $field_match_fields;
	$best_score = 0;
	$best_index = -1;
	$pheader_columns='';

	for ($i = 0; $i < count($header_columns); $i++)
		{
		$score = fuzzy_match_field($header_columns[$i], $vicidial_field_name);
		if ($score > $best_score)
			{
			$best_score = $score;
			$best_index = $i;
			$pheader_columns=$header_columns[$i];
			}
		}

	# echo "$pheader_columns || $vicidial_field_name || $best_score\n";
	$field_match_fields["$vicidial_field_name"]=$pheader_columns;
	$field_match_scores["$vicidial_field_name"]=$best_score;

	# Only return a match if confidence is >= 70%
	if ($best_score >= 0) { return array($best_index, $best_score, $pheader_columns); }
	return array(-1, $best_score, $pheader_columns);
	}

# Build load summary HTML - returns string instead of printing
function build_summary_html($good, $bad, $total, $dup, $moved, $inv, $dup_phone_details, $inv_phone_details, $dnc_phone_details, $listid_error_details)
	{
	$inv_count = count($inv_phone_details);
	$dnc_count = count($dnc_phone_details);
	$listid_count = count($listid_error_details);

	# Aggregate duplicate counts by list ID
	$dup_by_list = array();
	foreach ($dup_phone_details as $dup_phone => $dup_info)
		{
		$lists = array_unique($dup_info['lists']);
		foreach ($lists as $lid)
			{
			if (strlen($lid) > 0)
				{
				if (!isset($dup_by_list[$lid])) { $dup_by_list[$lid] = 0; }
				$dup_by_list[$lid] += $dup_info['count'];
				}
			}
		}

	# Aggregate invalid by reason
	$inv_by_reason = array();
	foreach ($inv_phone_details as $inv_info)
		{
		$reason = $inv_info['reason'];
		if (!isset($inv_by_reason[$reason])) { $inv_by_reason[$reason] = 0; }
		$inv_by_reason[$reason]++;
		}

	# Aggregate DNC by reason
	$dnc_by_reason = array();
	foreach ($dnc_phone_details as $dnc_info)
		{
		$reason = $dnc_info['reason'];
		if (!isset($dnc_by_reason[$reason])) { $dnc_by_reason[$reason] = 0; }
		$dnc_by_reason[$reason]++;
		}

	# --- Build HTML ---
	global $SSmenu_background;
	$html = "<table class='summary-stats-table'>\n";
	$html .= "<tr><td colspan=2 style='background:#$SSmenu_background; text-align:center; padding:10px;'><span style='font-size:15px; font-weight:bold; color:#fff;'>"._QXZ("Load Summary")."</span></td></tr>\n";
	$html .= "<tr><td class='stat-label' style='background:#009900;'>"._QXZ("Good").":</td><td class='stat-value' style='background:#009900;'>".number_format($good)."</td></tr>\n";
	$html .= "<tr><td class='stat-label' style='background:#990000;'>"._QXZ("Bad").":</td><td class='stat-value' style='background:#990000;'>".number_format($bad)."</td></tr>\n";
	$html .= "<tr><td class='stat-label' style='background:#000099;'>"._QXZ("Total").":</td><td class='stat-value' style='background:#000099;'>".number_format($total)."</td></tr>\n";

	if ($dup > 0)
		{
		$html .= "<tr><td class='stat-label' style='background:#CC6600;'>"._QXZ("Duplicates").":</td><td class='stat-value' style='background:#CC6600;'>".number_format($dup)."</td></tr>\n";
		if (count($dup_by_list) > 0)
			{
			foreach ($dup_by_list as $lid => $cnt)
				{ $html .= "<tr><td class='stat-label' style='background:#E08830; font-size:11px; padding:4px 14px;'>&nbsp;&nbsp;&nbsp;"._QXZ("List ID")." $lid:</td><td class='stat-value' style='background:#E08830; font-size:11px; padding:4px 14px;'>".number_format($cnt)."</td></tr>\n"; }
			}
		}
	if ($moved > 0)
		{ $html .= "<tr><td class='stat-label' style='background:#006699;'>"._QXZ("Moved").":</td><td class='stat-value' style='background:#006699;'>".number_format($moved)."</td></tr>\n"; }
	if ($inv_count > 0)
		{
		$html .= "<tr><td class='stat-label' style='background:#993300;'>"._QXZ("Invalid Numbers").":</td><td class='stat-value' style='background:#993300;'>".number_format($inv_count)."</td></tr>\n";
		foreach ($inv_by_reason as $reason => $cnt)
			{ $html .= "<tr><td class='stat-label' style='background:#B35900; font-size:11px; padding:4px 14px;'>&nbsp;&nbsp;&nbsp;$reason:</td><td class='stat-value' style='background:#B35900; font-size:11px; padding:4px 14px;'>".number_format($cnt)."</td></tr>\n"; }
		}
	if ($dnc_count > 0)
		{
		$html .= "<tr><td class='stat-label' style='background:#660000;'>"._QXZ("DNC Matches").":</td><td class='stat-value' style='background:#660000;'>".number_format($dnc_count)."</td></tr>\n";
		foreach ($dnc_by_reason as $reason => $cnt)
			{ $html .= "<tr><td class='stat-label' style='background:#8B0000; font-size:11px; padding:4px 14px;'>&nbsp;&nbsp;&nbsp;$reason:</td><td class='stat-value' style='background:#8B0000; font-size:11px; padding:4px 14px;'>".number_format($cnt)."</td></tr>\n"; }
		}
	if ($listid_count > 0)
		{ $html .= "<tr><td class='stat-label' style='background:#663300;'>"._QXZ("Invalid List ID").":</td><td class='stat-value' style='background:#663300;'>".number_format($listid_count)."</td></tr>\n"; }
	$html .= "</table>\n";

	return $html;
	}
##### END helper functions #####

$enable_status_mismatch_leadloader_option=0;

if (file_exists('options.php'))
	{
	require('options.php');
	}

$US='_';
$MT[0]='';
$vicidial_list_fields = '|lead_id|vendor_lead_code|source_id|list_id|gmt_offset_now|called_since_last_reset|phone_code|phone_number|title|first_name|middle_initial|last_name|address1|address2|address3|city|state|province|postal_code|country_code|gender|date_of_birth|alt_phone|email|security_phrase|comments|called_count|last_local_call_time|rank|owner|entry_list_id|';

##### ACTION ROUTING - get action parameter early #####
$action = '';
if (isset($_GET['action'])) { $action = $_GET['action']; }
	elseif (isset($_POST['action'])) { $action = $_POST['action']; }
$action = preg_replace('/[^a-z]/', '', $action);

##### PROGRESS ACTION - minimal setup, early exit #####
if ($action == 'progress')
	{
	header('Content-Type: application/json');
	$progress_id = '';
	if (isset($_GET['id'])) { $progress_id = preg_replace('/[^a-f0-9]/', '', $_GET['id']); }
	$progress_file = "/tmp/vici_progress_{$progress_id}.txt";
	if (file_exists($progress_file))
		{
		$data = @file_get_contents($progress_file);
		$parts = explode('|', $data);
		$current = isset($parts[0]) ? intval($parts[0]) : 0;
		$ptotal = isset($parts[1]) ? intval($parts[1]) : 0;
		$pstatus = isset($parts[2]) ? trim($parts[2]) : 'unknown';
		$pct = ($ptotal > 0) ? round(($current / $ptotal) * 100) : 0;
		if ($pct > 100) { $pct = 100; }
		echo json_encode(array('current'=>$current, 'total'=>$ptotal, 'pct'=>$pct, 'status'=>$pstatus));
		}
	else
		{
		echo json_encode(array('current'=>0, 'total'=>0, 'pct'=>0, 'status'=>'waiting'));
		}
	exit;
	}

##### INPUT VARIABLES #####
$PHP_AUTH_USER=(array_key_exists('PHP_AUTH_USER', $_SERVER) ? $_SERVER['PHP_AUTH_USER'] : "");
$PHP_AUTH_PW=(array_key_exists('PHP_AUTH_PW', $_SERVER) ? $_SERVER['PHP_AUTH_PW'] : "");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

if (isset($_GET["leadfile_name"]))			{$leadfile_name=$_GET["leadfile_name"];}
	elseif (isset($_POST["leadfile_name"]))	{$leadfile_name=$_POST["leadfile_name"];}
	else {$leadfile_name="";}
if (isset($_GET["file_layout"]))				{$file_layout=$_GET["file_layout"];}
	elseif (isset($_POST["file_layout"]))		{$file_layout=$_POST["file_layout"];}
	else {$file_layout="";}
if (isset($_GET["vendor_lead_code_field"]))				{$vendor_lead_code_field=$_GET["vendor_lead_code_field"];}
	elseif (isset($_POST["vendor_lead_code_field"]))	{$vendor_lead_code_field=$_POST["vendor_lead_code_field"];}
	else {$vendor_lead_code_field="-1";}
if (isset($_GET["source_id_field"]))			{$source_id_field=$_GET["source_id_field"];}
	elseif (isset($_POST["source_id_field"]))	{$source_id_field=$_POST["source_id_field"];}
	else {$source_id_field="-1";}
if (isset($_GET["list_id_field"]))				{$list_id_field=$_GET["list_id_field"];}
	elseif (isset($_POST["list_id_field"]))		{$list_id_field=$_POST["list_id_field"];}
	else {$list_id_field="-1";}
if (isset($_GET["phone_code_field"]))			{$phone_code_field=$_GET["phone_code_field"];}
	elseif (isset($_POST["phone_code_field"]))	{$phone_code_field=$_POST["phone_code_field"];}
	else {$phone_code_field="-1";}
if (isset($_GET["phone_number_field"]))				{$phone_number_field=$_GET["phone_number_field"];}
	elseif (isset($_POST["phone_number_field"]))	{$phone_number_field=$_POST["phone_number_field"];}
	else {$phone_number_field="-1";}
if (isset($_GET["title_field"]))				{$title_field=$_GET["title_field"];}
	elseif (isset($_POST["title_field"]))		{$title_field=$_POST["title_field"];}
	else {$title_field="-1";}
if (isset($_GET["first_name_field"]))			{$first_name_field=$_GET["first_name_field"];}
	elseif (isset($_POST["first_name_field"]))	{$first_name_field=$_POST["first_name_field"];}
	else {$first_name_field="-1";}
if (isset($_GET["middle_initial_field"]))			{$middle_initial_field=$_GET["middle_initial_field"];}
	elseif (isset($_POST["middle_initial_field"]))	{$middle_initial_field=$_POST["middle_initial_field"];}
	else {$middle_initial_field="-1";}
if (isset($_GET["last_name_field"]))			{$last_name_field=$_GET["last_name_field"];}
	elseif (isset($_POST["last_name_field"]))	{$last_name_field=$_POST["last_name_field"];}
	else {$last_name_field="-1";}
if (isset($_GET["address1_field"]))				{$address1_field=$_GET["address1_field"];}
	elseif (isset($_POST["address1_field"]))	{$address1_field=$_POST["address1_field"];}
	else {$address1_field="-1";}
if (isset($_GET["address2_field"]))				{$address2_field=$_GET["address2_field"];}
	elseif (isset($_POST["address2_field"]))	{$address2_field=$_POST["address2_field"];}
	else {$address2_field="-1";}
if (isset($_GET["address3_field"]))				{$address3_field=$_GET["address3_field"];}
	elseif (isset($_POST["address3_field"]))	{$address3_field=$_POST["address3_field"];}
	else {$address3_field="-1";}
if (isset($_GET["city_field"]))					{$city_field=$_GET["city_field"];}
	elseif (isset($_POST["city_field"]))		{$city_field=$_POST["city_field"];}
	else {$city_field="-1";}
if (isset($_GET["state_field"]))				{$state_field=$_GET["state_field"];}
	elseif (isset($_POST["state_field"]))		{$state_field=$_POST["state_field"];}
	else {$state_field="-1";}
if (isset($_GET["province_field"]))				{$province_field=$_GET["province_field"];}
	elseif (isset($_POST["province_field"]))		{$province_field=$_POST["province_field"];}
	else {$province_field="-1";}
if (isset($_GET["postal_code_field"]))				{$postal_code_field=$_GET["postal_code_field"];}
	elseif (isset($_POST["postal_code_field"]))		{$postal_code_field=$_POST["postal_code_field"];}
	else {$postal_code_field="-1";}
if (isset($_GET["country_code_field"]))				{$country_code_field=$_GET["country_code_field"];}
	elseif (isset($_POST["country_code_field"]))	{$country_code_field=$_POST["country_code_field"];}
	else {$country_code_field="-1";}
if (isset($_GET["gender_field"]))			{$gender_field=$_GET["gender_field"];}
	elseif (isset($_POST["gender_field"]))	{$gender_field=$_POST["gender_field"];}
	else {$gender_field="-1";}
if (isset($_GET["date_of_birth_field"]))			{$date_of_birth_field=$_GET["date_of_birth_field"];}
	elseif (isset($_POST["date_of_birth_field"]))	{$date_of_birth_field=$_POST["date_of_birth_field"];}
	else {$date_of_birth_field="-1";}
if (isset($_GET["alt_phone_field"]))			{$alt_phone_field=$_GET["alt_phone_field"];}
	elseif (isset($_POST["alt_phone_field"]))	{$alt_phone_field=$_POST["alt_phone_field"];}
	else {$alt_phone_field="-1";}
if (isset($_GET["email_field"]))				{$email_field=$_GET["email_field"];}
	elseif (isset($_POST["email_field"]))		{$email_field=$_POST["email_field"];}
	else {$email_field="-1";}
if (isset($_GET["security_phrase_field"]))			{$security_phrase_field=$_GET["security_phrase_field"];}
	elseif (isset($_POST["security_phrase_field"]))	{$security_phrase_field=$_POST["security_phrase_field"];}
	else {$security_phrase_field="-1";}
if (isset($_GET["comments_field"]))				{$comments_field=$_GET["comments_field"];}
	elseif (isset($_POST["comments_field"]))	{$comments_field=$_POST["comments_field"];}
	else {$comments_field="-1";}
if (isset($_GET["rank_field"]))					{$rank_field=$_GET["rank_field"];}
	elseif (isset($_POST["rank_field"]))		{$rank_field=$_POST["rank_field"];}
	else {$rank_field="-1";}
if (isset($_GET["owner_field"]))				{$owner_field=$_GET["owner_field"];}
	elseif (isset($_POST["owner_field"]))		{$owner_field=$_POST["owner_field"];}
	else {$owner_field="-1";}
if (isset($_GET["list_id_override"]))			{$list_id_override=$_GET["list_id_override"];}
	elseif (isset($_POST["list_id_override"]))	{$list_id_override=$_POST["list_id_override"];}
	else {$list_id_override="";}
	$list_id_override = (preg_replace("/\D/","",$list_id_override));
if (isset($_GET["master_list_override"]))			{$master_list_override=$_GET["master_list_override"];}
	elseif (isset($_POST["master_list_override"]))	{$master_list_override=$_POST["master_list_override"];}
	else {$master_list_override="";}
if (isset($_GET["lead_file"]))					{$lead_file=$_GET["lead_file"];}
	elseif (isset($_POST["lead_file"]))			{$lead_file=$_POST["lead_file"];}
	else {$lead_file="";}
if (isset($_GET["dupcheck"]))				{$dupcheck=$_GET["dupcheck"];}
	elseif (isset($_POST["dupcheck"]))		{$dupcheck=$_POST["dupcheck"];}
	else {$dupcheck="";}
if (isset($_GET["dedupe_statuses"]))				{$dedupe_statuses=$_GET["dedupe_statuses"];}
	elseif (isset($_POST["dedupe_statuses"]))		{$dedupe_statuses=$_POST["dedupe_statuses"];}
	else {$dedupe_statuses="";}
if (isset($_GET["dedupe_statuses_override"]))			{$dedupe_statuses_override=$_GET["dedupe_statuses_override"];}
	elseif (isset($_POST["dedupe_statuses_override"]))	{$dedupe_statuses_override=$_POST["dedupe_statuses_override"];}
	else {$dedupe_statuses_override="";}
if (isset($_GET["status_mismatch_action"]))				{$status_mismatch_action=$_GET["status_mismatch_action"];}
	elseif (isset($_POST["status_mismatch_action"]))	{$status_mismatch_action=$_POST["status_mismatch_action"];}
	else {$status_mismatch_action="";}
if (isset($_GET["postalgmt"]))				{$postalgmt=$_GET["postalgmt"];}
	elseif (isset($_POST["postalgmt"]))		{$postalgmt=$_POST["postalgmt"];}
	else {$postalgmt="";}
if (isset($_GET["phone_code_override"]))			{$phone_code_override=$_GET["phone_code_override"];}
	elseif (isset($_POST["phone_code_override"]))	{$phone_code_override=$_POST["phone_code_override"];}
	else {$phone_code_override="";}
	$phone_code_override = (preg_replace("/\D/","",$phone_code_override));
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["DBX"]))	{$DBX=$_GET["DBX"];}
	elseif (isset($_POST["DBX"]))	{$DBX=$_POST["DBX"];}
if (isset($_GET["template_id"]))			{$template_id=$_GET["template_id"];}
	elseif (isset($_POST["template_id"]))	{$template_id=$_POST["template_id"];}
	else {$template_id="";}
if (isset($_GET["usacan_check"]))			{$usacan_check=$_GET["usacan_check"];}
	elseif (isset($_POST["usacan_check"]))	{$usacan_check=$_POST["usacan_check"];}
	else {$usacan_check="";}
if (isset($_GET["state_conversion"]))			{$state_conversion=$_GET["state_conversion"];}
	elseif (isset($_POST["state_conversion"]))	{$state_conversion=$_POST["state_conversion"];}
	else {$state_conversion="";}
if (isset($_GET["web_loader_phone_length"]))			{$web_loader_phone_length=$_GET["web_loader_phone_length"];}
	elseif (isset($_POST["web_loader_phone_length"]))	{$web_loader_phone_length=$_POST["web_loader_phone_length"];}
	else {$web_loader_phone_length=0;}
if (isset($_GET["international_dnc_scrub"]))			{$international_dnc_scrub=$_GET["international_dnc_scrub"];}
	elseif (isset($_POST["international_dnc_scrub"]))	{$international_dnc_scrub=$_POST["international_dnc_scrub"];}
	else {$international_dnc_scrub="";}
if (isset($_GET["invalid_phone_override"]))				{$invalid_phone_override=$_GET["invalid_phone_override"];}
	elseif (isset($_POST["invalid_phone_override"]))	{$invalid_phone_override=$_POST["invalid_phone_override"];}
	else {$invalid_phone_override="";}
if (isset($_GET["server_ip"]))	{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))	{$server_ip=$_POST["server_ip"];}
	else {$server_ip="";}
if (isset($_GET["attempt_auto_detect"]))	{$attempt_auto_detect=$_GET["attempt_auto_detect"];}
	elseif (isset($_POST["attempt_auto_detect"]))	{$attempt_auto_detect=$_POST["attempt_auto_detect"];}
	else {$attempt_auto_detect="";}

$list_id_override = preg_replace('/[^0-9]/','',$list_id_override);
$phone_code_override = preg_replace('/[^0-9]/','',$phone_code_override);
if ( $list_id_override == "in_file" ) { $list_id_override = ""; }
if ( $phone_code_override == "in_file" ) { $phone_code_override = ""; }

$field_regx = "[\"\x60\\\\;]";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,admin_web_directory,custom_fields_enabled,webroot_writable,enable_languages,language_method,active_modules,admin_screen_colors,web_loader_phone_length,enable_international_dncs,web_loader_phone_strip,allow_web_debug,default_phone_code FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
$qm_conf_ct = mysqli_num_rows($rslt);
#if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$admin_web_directory =			$row[1];
	$custom_fields_enabled =		$row[2];
	$webroot_writable =				$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$SSactive_modules =				$row[6];
	$SSadmin_screen_colors =		$row[7];
	$SSweb_loader_phone_length =	$row[8];
	$SSenable_international_dncs =	$row[9];
	$SSweb_loader_phone_strip =		$row[10];
	$SSallow_web_debug =			$row[11];
	$SSdefault_phone_code = 		$row[12];
	}
if ($SSallow_web_debug < 1 || !isset($DB)) {$DB=0;}
$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
$DBX=0;  # Always set - used in lookup_gmt, where you will have to override via hard-coding
##### END SETTINGS LOOKUP #####
###########################################

$list_id_override = preg_replace('/[^0-9]/','',$list_id_override);
$attempt_auto_detect = preg_replace('/[^YN]/','',$attempt_auto_detect);
$phone_code_override = preg_replace('/[^0-9]/','',$phone_code_override);
$web_loader_phone_length = preg_replace('/[^0-9]/','',$web_loader_phone_length);
$international_dnc_scrub = preg_replace('/[^-_0-9a-zA-Z]/', '', $international_dnc_scrub);
$master_list_override = preg_replace('/[^-_0-9a-zA-Z]/', '', $master_list_override);
$usacan_check = preg_replace('/[^-_0-9a-zA-Z]/', '', $usacan_check);
$state_conversion = preg_replace('/[^-_0-9a-zA-Z]/', '', $state_conversion);
$status_mismatch_action = preg_replace('/[^- \_0-9a-zA-Z]/', '', $status_mismatch_action);
$postalgmt = preg_replace('/[^- \_0-9a-zA-Z]/', '', $postalgmt);
$dupcheck = preg_replace('/[^- \_0-9a-zA-Z]/', '', $dupcheck);
$lead_file = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$lead_file);
$vendor_lead_code_field = preg_replace("/[^-0-9]/",'',$vendor_lead_code_field);
$source_id_field = preg_replace("/[^-0-9]/",'',$source_id_field);
$list_id_field = preg_replace("/[^-0-9]/",'',$list_id_field);
$phone_code_field = preg_replace("/[^-0-9]/",'',$phone_code_field);
$phone_number_field = preg_replace("/[^-0-9]/",'',$phone_number_field);
$title_field = preg_replace("/[^-0-9]/",'',$title_field);
$first_name_field = preg_replace("/[^-0-9]/",'',$first_name_field);
$middle_initial_field = preg_replace("/[^-0-9]/",'',$middle_initial_field);
$last_name_field = preg_replace("/[^-0-9]/",'',$last_name_field);
$address1_field = preg_replace("/[^-0-9]/",'',$address1_field);
$address2_field = preg_replace("/[^-0-9]/",'',$address2_field);
$address3_field = preg_replace("/[^-0-9]/",'',$address3_field);
$city_field = preg_replace("/[^-0-9]/",'',$city_field);
$state_field = preg_replace("/[^-0-9]/",'',$state_field);
$province_field = preg_replace("/[^-0-9]/",'',$province_field);
$postal_code_field = preg_replace("/[^-0-9]/",'',$postal_code_field);
$country_code_field = preg_replace("/[^-0-9]/",'',$country_code_field);
$gender_field = preg_replace("/[^-0-9]/",'',$gender_field);
$date_of_birth_field = preg_replace("/[^-0-9]/",'',$date_of_birth_field);
$alt_phone_field = preg_replace("/[^-0-9]/",'',$alt_phone_field);
$email_field = preg_replace("/[^-0-9]/",'',$email_field);
$security_phrase_field = preg_replace("/[^-0-9]/",'',$security_phrase_field);
$comments_field = preg_replace("/[^-0-9]/",'',$comments_field);
$rank_field = preg_replace("/[^-0-9]/",'',$rank_field);
$owner_field = preg_replace("/[^-0-9]/",'',$owner_field);

$file_layout = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_layout);
$invalid_phone_override = preg_replace('/[^-_0-9a-zA-Z]/', '', $invalid_phone_override);
# $tz_method = preg_replace('/[^-\_0-9a-zA-Z]/', '',$tz_method);
$server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/', '', $server_ip);

# Variables filter further down in the code
# $dedupe_statuses

if (is_array($dedupe_statuses))
	{
	if (count($dedupe_statuses)>0)
		{
		for($ds=0; $ds<count($dedupe_statuses); $ds++)
			{
			$dedupe_statuses[$ds] = preg_replace('/[^-_0-9\p{L}]/u', '', $dedupe_statuses[$ds]);
			}
		}
	}
else
	{
	$dedupe_statuses=array();
	}


if (strlen($dedupe_statuses_override)>0)
	{
	$dedupe_statuses_override = preg_replace('/[^- \,\_0-9a-zA-Z]/', '', $dedupe_statuses_override);
	$dedupe_statuses=explode(",", $dedupe_statuses_override);
	}

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$template_id = preg_replace('/[^-_0-9a-zA-Z]/', '', $template_id);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$template_id = preg_replace('/[^-_0-9\p{L}]/u', '', $template_id);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_datetime = $STARTtime;
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language = $row[0];
	}

##### AUTHENTICATION #####
$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		if ($action == 'upload' || $action == 'process') { header('Content-Type: application/json'); echo json_encode(array('error'=>'Session expired')); exit; }
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

$stmt="SELECT load_leads,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGload_leads =	$row[0];
$LOGuser_group =	$row[1];

if ($LOGload_leads < 1)
	{
	if ($action == 'upload' || $action == 'process') { header('Content-Type: application/json'); echo json_encode(array('error'=>'No permission to load leads')); exit; }
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to load leads")."\n";
	exit;
	}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

$camp_lists='';
$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if (!preg_match('/\-ALL/i', $LOGallowed_campaigns))
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

$script_name = getenv("SCRIPT_NAME");
$server_name = getenv("SERVER_NAME");
$server_port = getenv("SERVER_PORT");
if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
	else {$HTTPprotocol = 'http://';}
# $admDIR = "$HTTPprotocol$server_name$script_name";
# $admDIR = preg_replace('/admin_listloader_sixth_gen\.php/i', '',$admDIR);
# $admDIR = "/vicidial/";
# $admSCR = 'admin.php';

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

##### DATE/TIME VARIABLES #####
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

### Grab Server GMT value from the database
$stmt="SELECT local_gmt FROM servers where server_ip = '$server_ip';";
$rslt=mysql_to_mysqli($stmt, $link);
$gmt_recs = mysqli_num_rows($rslt);
if ($gmt_recs > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$DBSERVER_GMT = "$row[0]";
	if (strlen($DBSERVER_GMT)>0) {$SERVER_GMT = $DBSERVER_GMT;}
	if ($isdst) {$SERVER_GMT++;}
	}
else
	{
	$SERVER_GMT = date("O");
	$SERVER_GMT = preg_replace('/\+/i', '',$SERVER_GMT);
	$SERVER_GMT = ($SERVER_GMT + 0);
	$SERVER_GMT = MathZDC($SERVER_GMT, 100);
	}

$LOCAL_GMT_OFF = $SERVER_GMT;
$LOCAL_GMT_OFF_STD = $SERVER_GMT;


##### SCREEN COLORS #####
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
$SSbutton_color='EFEFEF';

if ($SSadmin_screen_colors != 'default')
	{
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,button_color FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
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
		$SSbutton_color			=	$row[10];
		}
	}
$Mhead_color = $SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;

##### UPLOAD ACTION - handle file upload, return JSON #####
if ($action == 'upload')
	{
	header('Content-Type: application/json');

	if (!isset($_FILES['leadfile']) || $_FILES['leadfile']['error'] == UPLOAD_ERR_NO_FILE)
		{
		echo json_encode(array('error' => 'No file was uploaded'));
		exit;
		}

	$LF_orig = $_FILES['leadfile']['name'];
	$LF_path = $_FILES['leadfile']['tmp_name'];
	$leadfile_name = $LF_orig;

	if (preg_match("/;|:|\/|\^|\[|\]|\"|\'|\*/", $LF_orig))
		{
		echo json_encode(array('error' => 'Invalid file name: '.$LF_orig));
		exit;
		}

	$upload_error = $_FILES['leadfile']['error'];
	if ($upload_error != UPLOAD_ERR_OK)
		{
		$error_msg = 'File upload error';
		if ($upload_error == UPLOAD_ERR_INI_SIZE) { $error_msg = 'File exceeds maximum upload size of '.ini_get("upload_max_filesize"); }
		if ($upload_error == UPLOAD_ERR_FORM_SIZE) { $error_msg = 'File exceeds MAX_FILE_SIZE directive'; }
		if ($upload_error == UPLOAD_ERR_PARTIAL) { $error_msg = 'File was only partially uploaded'; }
		if ($upload_error == UPLOAD_ERR_NO_TMP_DIR) { $error_msg = 'Missing temporary directory'; }
		if ($upload_error == UPLOAD_ERR_CANT_WRITE) { $error_msg = 'Failed to write file to disk'; }
		if ($upload_error == UPLOAD_ERR_EXTENSION)	{$error_msg = 'ERROR: An unknow php extension has stopped the file upload. Review your system configuration.';}

		echo json_encode(array('error' => $error_msg));
		exit;
		}

	$leadfile_name = preg_replace('/[^-\.\_0-9a-zA-Z]/','_',$leadfile_name);
	$delim_set=0;

	if (preg_match("/\.csv$|\.xls$|\.xlsx$|\.ods$|\.sxc$/i", $leadfile_name))
		{
		copy($LF_path, "/tmp/$leadfile_name");
		$new_filename = preg_replace("/\.csv$|\.xls$|\.xlsx$|\.ods$|\.sxc$/i", '.txt', $leadfile_name);
		$convert_command = "$WeBServeRRooT/$admin_web_directory/sheet2tab.pl /tmp/$leadfile_name /tmp/$new_filename";
		passthru("$convert_command");
		$lead_file = "/tmp/$new_filename";
		$delim_set=1;
		}
	else
		{
		copy($LF_path, "/tmp/$leadfile_name");
		$lead_file = "/tmp/$leadfile_name";
		}

	# Read first line for headers and detect delimiter
	$file = fopen($lead_file, "r");
	if (!$file) { echo json_encode(array('error' => 'Cannot open converted file')); exit; }
	$buffer = fgets($file, 4096);
	fclose($file);

	$tab_count = substr_count($buffer, "\t");
	$pipe_count = substr_count($buffer, "|");
	$delimiter = ($tab_count > $pipe_count) ? "\t" : "|";

	$buffer = rtrim($buffer);
	$buffer = stripslashes($buffer);
	$headers = explode($delimiter, preg_replace('/[\"]/i', '', $buffer));

	# Count total lines
	$total_lines = 0;
	$count_file = fopen($lead_file, "r");
	while (!feof($count_file)) { fgets($count_file, 4096); $total_lines++; }
	fclose($count_file);
	if ($total_lines > 0) { $total_lines--; }

	# Build fields list for mapping
	$all_vicidial_fields = array('vendor_lead_code','source_id','list_id','phone_code','phone_number','title','first_name','middle_initial','last_name','address1','address2','address3','city','state','province','postal_code','country_code','gender','date_of_birth','alt_phone','email','security_phrase','comments','rank','owner');

	$fields_for_mapping = array();
	foreach ($all_vicidial_fields as $vf)
		{
		if ($vf == 'list_id' && strlen($list_id_override) > 0) { continue; }
		if ($vf == 'phone_code' && strlen($phone_code_override) > 0) { continue; }
		$fields_for_mapping[] = $vf;
		}

	# Get custom fields if applicable
	$custom_field_names = array();
	if ($custom_fields_enabled > 0 && strlen($list_id_override) > 0)
		{
		$stmt="SHOW TABLES LIKE \"custom_$list_id_override\";";
		$rslt=mysql_to_mysqli($stmt, $link);
		if (mysqli_num_rows($rslt) > 0)
			{
			$stmt="SELECT field_label,field_type from vicidial_lists_fields where list_id='$list_id_override' and field_duplicate!='Y' order by field_rank,field_order,field_label;";
			$rslt=mysql_to_mysqli($stmt, $link);
			while ($rowx = mysqli_fetch_row($rslt))
				{
				if ($rowx[1] != 'DISPLAY' && $rowx[1] != 'SCRIPT' && $rowx[1] != 'SWITCH' && $rowx[1] != 'BUTTON')
					{
					if (!preg_match("/\|$rowx[0]\|/", $vicidial_list_fields))
						{
						$custom_field_names[] = $rowx[0];
						}
					}
				}
			}
		}

	# Auto-detect field mappings
	$all_mapping_fields = array_merge($fields_for_mapping, $custom_field_names);
	$auto_map = array();
	$auto_scores = array();
	if ($attempt_auto_detect=="Y")
		{
		foreach ($all_mapping_fields as $tf_name)
			{
			$best_idx_array = auto_detect_field_index($tf_name, $headers);
			$best_idx=$best_idx_array[0];
			if ($best_idx >= 0)
				{
				$best_score = fuzzy_match_field($headers[$best_idx], $tf_name);
				if (isset($auto_scores[$best_idx]))
					{
					if ($best_score > $auto_scores[$best_idx])
						{
						foreach ($auto_map as $old_field => $old_idx) { if ($old_idx == $best_idx) { unset($auto_map[$old_field]); break; } }
						$auto_map[$tf_name] = $best_idx.",".$best_score.",".$tf_name;
						$auto_scores[$best_idx] = $best_score;
						}
					}
				else
					{
					$auto_map[$tf_name] = $best_idx.",".$best_score.",".$tf_name;
					$auto_scores[$best_idx] = $best_score;
					}
				}
			}
		}

	echo json_encode(array(
		'success' => true,
		'lead_file' => $lead_file,
		'leadfile_name' => $leadfile_name,
		'total_lines' => $total_lines,
		'delimiter' => $delimiter,
		'headers' => $headers,
		'vicidial_fields' => $fields_for_mapping,
		'custom_fields' => $custom_field_names,
		'auto_map' => (object)$auto_map,
	));
	exit;
	}

##### PROCESS ACTION - process leads, write progress, return JSON #####
if ($action == 'process')
	{
	header('Content-Type: application/json');

	$progress_id = isset($_POST['progress_id']) ? preg_replace('/[^a-f0-9]/', '', $_POST['progress_id']) : md5(uniqid(mt_rand(), true));

	# Validate file
	if (!file_exists($lead_file))
		{
		echo json_encode(array('error' => 'Lead file not found'));
		exit;
		}

	# Open file, detect delimiter
	$file=fopen("$lead_file", "r");
	if ($webroot_writable > 0) { $stmt_file=fopen("listloader_stmts.txt", "w"); }
	$buffer=fgets($file, 4096);
	$tab_count=substr_count($buffer, "\t");
	$pipe_count=substr_count($buffer, "|");
	if ($tab_count>$pipe_count) {$delimiter="\t";} else {$delimiter="|";}
	$field_check=explode($delimiter, $buffer);

	if (count($field_check) < 2)
		{
		echo json_encode(array('error' => _QXZ("The file does not have the required number of fields to process it")));
		exit;
		}

	$file=fopen("$lead_file", "r");
	$total=0; $good=0; $bad=0; $dup=0; $inv=0; $post=0; $moved=0; $Tline=1; $phone_list='';
	$dup_phone_details=array(); $inv_phone_details=array(); $dnc_phone_details=array(); $listid_error_details=array();
	$multi_insert_counter=0; $multistmt=''; $record=0;

	# Dedupe status setup
	$statuses_clause=''; $mismatch_clause=''; $mismatch_limit=''; $status_dedupe_str='';
	if (is_array($dedupe_statuses) && count($dedupe_statuses)>0)
		{
		$statuses_clause=" and status in (";
		$status_dedupe_str="";
		for($ds=0; $ds<count($dedupe_statuses); $ds++)
			{
			$dedupe_statuses[$ds] = preg_replace('/[^-_0-9\p{L}]/u', '', $dedupe_statuses[$ds]);
			$statuses_clause.="'$dedupe_statuses[$ds]',";
			$status_dedupe_str.="$dedupe_statuses[$ds], ";
			if (preg_match('/\-\-ALL\-\-/', $dedupe_statuses[$ds]))
				{
				$status_mismatch_action="";
				$statuses_clause="";
				$status_dedupe_str="";
				break;
				}
			}
		$statuses_clause=preg_replace('/,$/', "", $statuses_clause);
		$status_dedupe_str=preg_replace('/,\s$/', "", $status_dedupe_str);
		if ($statuses_clause!="") {$statuses_clause.=")";}
		if ($status_mismatch_action)
			{
			$mismatch_clause=" and status not in ('".implode("','", $dedupe_statuses)."') ";
			if (preg_match('/RECENT/', $status_mismatch_action)) {$mismatch_limit=" limit 1 ";} else {$mismatch_limit="";}
			}
		}

	# Multiday SQL
	$multidaySQL='';
	if (preg_match("/30DAY|60DAY|90DAY|180DAY|360DAY/i",$dupcheck))
		{
		$day_val=30;
		if (preg_match("/30DAY/i",$dupcheck)) {$day_val=30;}
		if (preg_match("/60DAY/i",$dupcheck)) {$day_val=60;}
		if (preg_match("/90DAY/i",$dupcheck)) {$day_val=90;}
		if (preg_match("/180DAY/i",$dupcheck)) {$day_val=180;}
		if (preg_match("/360DAY/i",$dupcheck)) {$day_val=360;}
		$multiday = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d")-$day_val,date("Y")));
		$multidaySQL = "and entry_date > \"$multiday\"";
		}

	# Layout-specific field index setup
	$use_custom_table = false;
	$use_batching = true;
	$custom_table = '';
	$custom_fields_ary = array();
	$A_field_label = array(); $A_field_type = array(); $A_field_encrypt = array(); $A_field_value = array();
	$fields_to_print = 0;
	$tablecount_to_print = 0;
	$fieldscount_to_print = 0;

	if ($file_layout == 'standard')
		{
		$vendor_lead_code_field=0; $source_id_field=1; $list_id_field=2; $phone_code_field=3; $phone_number_field=4;
		$title_field=5; $first_name_field=6; $middle_initial_field=7; $last_name_field=8;
		$address1_field=9; $address2_field=10; $address3_field=11; $city_field=12; $state_field=13;
		$province_field=14; $postal_code_field=15; $country_code_field=16; $gender_field=17;
		$date_of_birth_field=18; $alt_phone_field=19; $email_field=20; $security_phrase_field=21;
		$comments_field=22; $rank_field=23; $owner_field=24;
		$use_batching = true;
		}
	elseif ($file_layout == 'template' && strlen($template_id) > 0)
		{
		$template_stmt="SELECT * from vicidial_custom_leadloader_templates where template_id='$template_id'";
		$template_rslt=mysql_to_mysqli($template_stmt, $link);
		if (mysqli_num_rows($template_rslt)==0)
			{
			echo json_encode(array('error' => _QXZ("Error - template no longer exists")));
			exit;
			}
		$template_row=mysqli_fetch_array($template_rslt);
		if (!$master_list_override)
			{
			$list_id_override=$template_row["list_id"];
			$custom_table=$template_row["custom_table"];
			}
		else
			{
			$custom_table="custom_$list_id_override";
			}
		$standard_variables=$template_row["standard_variables"];
		$custom_variables=$template_row["custom_variables"];
		$template_statuses=$template_row["template_statuses"];

		if (strlen($template_statuses)>0)
			{
			$template_statuses=preg_replace('/\|/', "','", $template_statuses);
			$statuses_clause=" and status in ('$template_statuses') ";
			}
		else
			{ $status_mismatch_action=""; }

		if ($status_mismatch_action)
			{
			$mismatch_clause=" and status NOT in ('$template_statuses') ";
			if (preg_match('/RECENT/', $status_mismatch_action)) {$mismatch_limit=" limit 1 ";} else {$mismatch_limit="";}
			}

		$standard_fields_ary=explode("|", $standard_variables);
		for ($i=0; $i<count($standard_fields_ary); $i++)
			{
			if (strlen($standard_fields_ary[$i])>0)
				{
				$fieldno_ary=explode(",", $standard_fields_ary[$i]);
				$varname=$fieldno_ary[0]."_field";
				$$varname=$fieldno_ary[1];
				}
			}
		$custom_fields_ary=explode("|", $custom_variables);
		$use_batching = false;
		$use_custom_table = (strlen($custom_table) > 0 && count($custom_fields_ary) > 0);
		}
	else
		{
		# Custom layout - field indices from POST (already read into variables above)
		# Check for custom table
		if ($custom_fields_enabled > 0 && strlen($list_id_override) > 0)
			{
			$stmt="SHOW TABLES LIKE \"custom_$list_id_override\";";
			$rslt=mysql_to_mysqli($stmt, $link);
			$tablecount_to_print = mysqli_num_rows($rslt);
			if ($tablecount_to_print > 0)
				{
				$stmt="SELECT count(*) from vicidial_lists_fields where list_id='$list_id_override' and field_duplicate!='Y';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$fieldscount_to_print = mysqli_num_rows($rslt);
				if ($fieldscount_to_print > 0)
					{
					$stmt="SELECT field_label,field_type,field_encrypt from vicidial_lists_fields where list_id='$list_id_override' and field_duplicate!='Y' order by field_rank,field_order,field_label;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$fields_to_print = mysqli_num_rows($rslt);
					$o=0;
					while ($fields_to_print > $o)
						{
						$rowx=mysqli_fetch_row($rslt);
						$A_field_label[$o] = $rowx[0];
						$A_field_type[$o] = $rowx[1];
						$A_field_encrypt[$o] = $rowx[2];
						$A_field_value[$o] = '';
						$o++;
						}
					$use_custom_table = true;
					$use_batching = false;
					}
				}
			}
		}

	# DNC blocking
	if (strlen($international_dnc_scrub)>0 && strlen($list_id_override)>0 && $SSenable_international_dncs)
		{
		$upd_dnc_stmt="update vicidial_settings_containers set container_entry=concat('$list_id_override => $international_dnc_scrub', if(length(container_entry)>0, '\r\n', ''), if(container_entry is null, '', container_entry)) where container_id='DNC_CURRENT_BLOCKED_LISTS'";
		$upd_dnc_rslt=mysql_to_mysqli($upd_dnc_stmt, $link);
		$delete_hopper_stmt="delete from vicidial_hopper where list_id='$list_id_override'";
		$delete_hopper_rslt=mysql_to_mysqli($delete_hopper_stmt, $link);
		}

	# Count total lines
	$total_lines = 0;
	$count_file = fopen("$lead_file", "r");
	while (!feof($count_file)) { fgets($count_file, 4096); $total_lines++; }
	fclose($count_file);
	if ($total_lines > 0) { $total_lines--; }

	# Progress file
	$progress_file = "/tmp/vici_progress_{$progress_id}.txt";
	@file_put_contents($progress_file, "0|{$total_lines}|processing");

	##### MAIN PROCESSING LOOP #####
	while (!feof($file))
		{
		$record++;
		$buffer=rtrim(fgets($file, 4096));
		$buffer=stripslashes($buffer);

		if (strlen($buffer)>0)
			{
			$row=explode($delimiter, preg_replace('/[\"]/i', '', $buffer));
			if ($file_layout == 'template') { $custom_fields_row=$row; }

			$pulldate=date("Y-m-d H:i:s");
			$entry_date = "$pulldate";
			$modify_date = "";
			$status = "NEW";
			$user = "";
			$vendor_lead_code =	isset($row[$vendor_lead_code_field]) ? $row[$vendor_lead_code_field] : '';
			$source_code =		isset($row[$source_id_field]) ? $row[$source_id_field] : '';
			$source_id=$source_code;
			$list_id =			isset($row[$list_id_field]) ? $row[$list_id_field] : '';
			$gmt_offset = '0';
			$called_since_last_reset='N';
			$phone_code =		preg_replace('/[^0-9]/i', '', isset($row[$phone_code_field]) ? $row[$phone_code_field] : '');
			$phone_number =		preg_replace('/[^0-9]/i', '', isset($row[$phone_number_field]) ? $row[$phone_number_field] : '');
			$title =			isset($row[$title_field]) ? $row[$title_field] : '';
			$first_name =		isset($row[$first_name_field]) ? $row[$first_name_field] : '';
			$middle_initial =	isset($row[$middle_initial_field]) ? $row[$middle_initial_field] : '';
			$last_name =		isset($row[$last_name_field]) ? $row[$last_name_field] : '';
			$address1 =			isset($row[$address1_field]) ? $row[$address1_field] : '';
			$address2 =			isset($row[$address2_field]) ? $row[$address2_field] : '';
			$address3 =			isset($row[$address3_field]) ? $row[$address3_field] : '';
			$city =				isset($row[$city_field]) ? $row[$city_field] : '';
			$state =			isset($row[$state_field]) ? $row[$state_field] : '';
			$province =			isset($row[$province_field]) ? $row[$province_field] : '';
			$postal_code =		isset($row[$postal_code_field]) ? $row[$postal_code_field] : '';
			$country_code =		isset($row[$country_code_field]) ? $row[$country_code_field] : '';
			$gender =			isset($row[$gender_field]) ? $row[$gender_field] : '';
			$date_of_birth =	isset($row[$date_of_birth_field]) ? $row[$date_of_birth_field] : '';
			$alt_phone =		preg_replace('/[^0-9]/i', '', isset($row[$alt_phone_field]) ? $row[$alt_phone_field] : '');
			$email =			isset($row[$email_field]) ? $row[$email_field] : '';
			$security_phrase =	isset($row[$security_phrase_field]) ? $row[$security_phrase_field] : '';
			$comments =			trim(isset($row[$comments_field]) ? $row[$comments_field] : '');
			$rank =				isset($row[$rank_field]) ? $row[$rank_field] : '';
			$owner =			isset($row[$owner_field]) ? $row[$owner_field] : '';

			if ($file_layout == 'template' && $master_list_override) { $list_id=$list_id_override; }

			# Sanitize all fields
			$vendor_lead_code = preg_replace("/$field_regx/i", "", $vendor_lead_code);
			$source_code = preg_replace("/$field_regx/i", "", $source_code);
			$source_id = preg_replace("/$field_regx/i", "", $source_id);
			$list_id = preg_replace("/$field_regx/i", "", $list_id);
			$phone_code = preg_replace("/$field_regx/i", "", $phone_code);
			$phone_number = preg_replace("/$field_regx/i", "", $phone_number);
			$title = preg_replace("/$field_regx/i", "", $title);
			$first_name = preg_replace("/$field_regx/i", "", $first_name);
			$middle_initial = preg_replace("/$field_regx/i", "", $middle_initial);
			$last_name = preg_replace("/$field_regx/i", "", $last_name);
			$address1 = preg_replace("/$field_regx/i", "", $address1);
			$address2 = preg_replace("/$field_regx/i", "", $address2);
			$address3 = preg_replace("/$field_regx/i", "", $address3);
			$city = preg_replace("/$field_regx/i", "", $city);
			$state = preg_replace("/$field_regx/i", "", $state);
			$province = preg_replace("/$field_regx/i", "", $province);
			$postal_code = preg_replace("/$field_regx/i", "", $postal_code);
			$country_code = preg_replace("/$field_regx/i", "", $country_code);
			$gender = preg_replace("/$field_regx/i", "", $gender);
			$date_of_birth = preg_replace("/$field_regx/i", "", $date_of_birth);
			$alt_phone = preg_replace("/$field_regx/i", "", $alt_phone);
			$email = preg_replace("/$field_regx/i", "", $email);
			$security_phrase = preg_replace("/$field_regx/i", "", $security_phrase);
			$comments = preg_replace("/$field_regx/i", "", $comments);
			$rank = preg_replace("/$field_regx/i", "", $rank);
			$owner = preg_replace("/$field_regx/i", "", $owner);

			$USarea = substr($phone_number, 0, 3);
			$USprefix = substr($phone_number, 3, 3);

			if (strlen($list_id_override)>0) { $list_id = $list_id_override; }
			if (strlen($phone_code_override)>0) { $phone_code = $phone_code_override; }
			if (strlen($phone_code)<1) {$phone_code = '1';}

			# State conversion
			if ( ($state_conversion == 'STATELOOKUP') and (strlen($state) > 3) )
				{
				$stmt = "SELECT state from vicidial_phone_codes where geographic_description='$state' and country_code='$phone_code' limit 1;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$sc_recs = mysqli_num_rows($rslt);
				if ($sc_recs > 0)
					{
					$scrow=mysqli_fetch_row($rslt);
					$state_abbr=$scrow[0];
					if ( (strlen($state_abbr) > 0) and (strlen($state_abbr) < 3 ) ) { $state = $state_abbr; }
					}
				}

			# Custom SQL for custom layout with custom fields
			$custom_SQL='';
			if ($file_layout != 'standard' && $file_layout != 'template' && $use_custom_table && $tablecount_to_print > 0 && $fieldscount_to_print > 0)
				{
				$o=0;
				while ($fields_to_print > $o)
					{
					$A_field_value[$o] = '';
					$field_name_id = $A_field_label[$o] . "_field";
					if ( ($A_field_type[$o]!='DISPLAY') and ($A_field_type[$o]!='SCRIPT') and ($A_field_type[$o]!='SWITCH') and ($A_field_type[$o]!='BUTTON') )
						{
						if (!preg_match("/\|$A_field_label[$o]\|/",$vicidial_list_fields))
							{
							$form_field_value = '';
							if (isset($_POST["$field_name_id"])) { $form_field_value = $_POST["$field_name_id"]; }
							$form_field_value = preg_replace("/\<|\>|\"|\\\\|;/","",$form_field_value);
							if ($form_field_value >= 0)
								{
								$A_field_value[$o] = isset($row[$form_field_value]) ? $row[$form_field_value] : '';
								$A_field_value[$o] = preg_replace("/$field_regx/i", "", $A_field_value[$o]);
								if ( ($A_field_encrypt[$o] == 'Y') and (preg_match("/cf_encrypt/",$SSactive_modules)) and (strlen($A_field_value[$o]) > 0) )
									{
									$field_enc=$MT;
									$A_field_value[$o] = base64_encode($A_field_value[$o]);
									exec("../agc/aes.pl --encrypt --text=$A_field_value[$o]", $field_enc);
									$field_enc_ct = count($field_enc);
									$k=0; $field_enc_all='';
									while ($field_enc_ct > $k) { $field_enc_all .= $field_enc[$k]; $k++; }
									$A_field_value[$o] = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
									}
								$custom_SQL .= "$A_field_label[$o]=\"$A_field_value[$o]\",";
								}
							}
						}
					$o++;
					}
				$custom_SQL = preg_replace("/,$/","",$custom_SQL);
				}

			# Check lead
			$valid_number=1; $dnc_matches=0; $dup_lead=0; $moved_lead=0; $invalid_reason=''; $replacement_text='';
			$temp_run = check_lead($DB,$link,$list_id,$phone_number,$alt_phone,$address3,$title);

			# Invalid phone override: alt_phone
			if (preg_match("/alt_phone/",$invalid_phone_override))
				{
				$run_replace_alt_phone=0;
				if ( ($valid_number < 1) and (preg_match("/invalid|all/",$invalid_phone_override)) ) {$run_replace_alt_phone++;}
				if ( ($dup_lead > 0) and (preg_match("/duplicate|all/",$invalid_phone_override)) ) {$run_replace_alt_phone++;}
				if ( ($dnc_matches > 0) and (preg_match("/dnc|all/",$invalid_phone_override)) ) {$run_replace_alt_phone++;}
				if ($run_replace_alt_phone > 0)
					{
					$temp_phone_number = preg_replace('/[^0-9]/i', '', $alt_phone);
					if (strlen($temp_phone_number) > 4)
						{
						$valid_number=1; $dnc_matches=0; $dup_lead=0; $moved_lead=0;
						$temp_run = check_lead($DB,$link,$list_id,$temp_phone_number,$alt_phone,$address3,$title);
						if ( ($valid_number>0) and ($dnc_matches<1) and ($dup_lead<1) )
							{
							$replacement_text .= "$invalid_reason " . _QXZ("REPLACEMENT PHONE ALT PHONE")." $temp_phone_number($phone_number)";
							$phone_number = $temp_phone_number;
							if (preg_match("/empty/",$invalid_phone_override)) {$alt_phone='';}
							}
						}
					}
				}

			# Invalid phone override: address3
			if (preg_match("/address3/",$invalid_phone_override))
				{
				$run_replace_address3=0;
				if ( ($valid_number < 1) and (preg_match("/invalid|all/",$invalid_phone_override)) ) {$run_replace_address3++;}
				if ( ($dup_lead > 0) and (preg_match("/duplicate|all/",$invalid_phone_override)) ) {$run_replace_address3++;}
				if ( ($dnc_matches > 0) and (preg_match("/dnc|all/",$invalid_phone_override)) ) {$run_replace_address3++;}
				if ($run_replace_address3 > 0)
					{
					$temp_phone_number = preg_replace('/[^0-9]/i', '', $address3);
					if (strlen($temp_phone_number) > 4)
						{
						$valid_number=1; $dnc_matches=0; $dup_lead=0; $moved_lead=0;
						$temp_run = check_lead($DB,$link,$list_id,$temp_phone_number,$alt_phone,$address3,$title);
						if ( ($valid_number>0) and ($dnc_matches<1) and ($dup_lead<1) )
							{
							$replacement_text .= "$invalid_reason " . _QXZ("REPLACEMENT PHONE ADDRESS3")." $temp_phone_number($phone_number)";
							$phone_number = $temp_phone_number;
							if (preg_match("/empty/",$invalid_phone_override)) {$address3='';}
							}
						}
					}
				}

			# INSERT or REJECT
			if ( ($valid_number>0) and ($dnc_matches<1) and ($dup_lead<1) and ($list_id >= 100) )
				{
				if (preg_match("/TITLEALTPHONE/i",$dupcheck))
					{$phone_list .= "$alt_phone$title$US$list_id|";}
				else
					{$phone_list .= "$phone_number$US$list_id|";}

				$gmt_offset = lookup_gmt($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,$postalgmt,$postal_code,$owner,$USprefix);

				if ($use_custom_table && strlen($custom_SQL) > 3 && $file_layout != 'standard' && $file_layout != 'template')
					{
					# Custom layout with custom fields - individual insert
					$stmtZ = "INSERT INTO vicidial_list (lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id) values('',\"$entry_date\",\"$modify_date\",\"$status\",\"$user\",\"$vendor_lead_code\",\"$source_id\",\"$list_id\",\"$gmt_offset\",\"$called_since_last_reset\",\"$phone_code\",\"$phone_number\",\"$title\",\"$first_name\",\"$middle_initial\",\"$last_name\",\"$address1\",\"$address2\",\"$address3\",\"$city\",\"$state\",\"$province\",\"$postal_code\",\"$country_code\",\"$gender\",\"$date_of_birth\",\"$alt_phone\",\"$email\",\"$security_phrase\",\"$comments\",0,\"2008-01-01 00:00:00\",\"$rank\",\"$owner\",'$list_id');";
					$rslt=mysql_to_mysqli($stmtZ, $link);
					$lead_id = mysqli_insert_id($link);
					if ( ($webroot_writable > 0) and ($DB>0) ) {fwrite($stmt_file, $stmtZ."\r\n");}
					$custom_SQL_query = "INSERT INTO custom_$list_id_override SET lead_id='$lead_id',$custom_SQL;";
					$rslt=mysql_to_mysqli($custom_SQL_query, $link);
					}
				elseif ($file_layout == 'template')
					{
					# Template - individual insert
					$stmtZ = "INSERT INTO vicidial_list (lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id) values('',\"$entry_date\",\"$modify_date\",\"$status\",\"$user\",\"$vendor_lead_code\",\"$source_id\",\"$list_id\",\"$gmt_offset\",\"$called_since_last_reset\",\"$phone_code\",\"$phone_number\",\"$title\",\"$first_name\",\"$middle_initial\",\"$last_name\",\"$address1\",\"$address2\",\"$address3\",\"$city\",\"$state\",\"$province\",\"$postal_code\",\"$country_code\",\"$gender\",\"$date_of_birth\",\"$alt_phone\",\"$email\",\"$security_phrase\",\"$comments\",0,\"2008-01-01 00:00:00\",\"$rank\",\"$owner\",'$list_id');";
					$rslt=mysql_to_mysqli($stmtZ, $link);
					$lead_id = mysqli_insert_id($link);
					if ( ($webroot_writable > 0) and ($DB>0) ) {fwrite($stmt_file, $stmtZ."\r\n");}
					# Template custom table insert
					if ($use_custom_table && strlen($custom_table) > 0)
						{
						$custom_tbl_stmt="SHOW TABLES LIKE '$custom_table'";
						$custom_tbl_rslt=mysql_to_mysqli($custom_tbl_stmt, $link);
						if(mysqli_num_rows($custom_tbl_rslt)>0)
							{
							$custom_ins_stmt="INSERT INTO $custom_table(lead_id";
							$custom_SQL_values="";
							for ($q=0; $q<count($custom_fields_ary); $q++)
								{
								if (strlen($custom_fields_ary[$q])>0)
									{
									$fieldno_ary=explode(",", $custom_fields_ary[$q]);
									$varname=$fieldno_ary[0]."_field";
									$$varname=$fieldno_ary[1];
									$custom_ins_stmt.=",$fieldno_ary[0]";
									if ( (preg_match("/cf_encrypt/",$SSactive_modules)) and (strlen($custom_fields_row[$$varname]) > 0) )
										{
										$field_encrypt='N';
										$stmt = "SELECT field_encrypt from vicidial_lists_fields where list_id='$list_id' and field_label='$fieldno_ary[0]' limit 1;";
										$rslt=mysql_to_mysqli($stmt, $link);
										if (mysqli_num_rows($rslt) > 0) { $encrow=mysqli_fetch_row($rslt); $field_encrypt = $encrow[0]; }
										if ($field_encrypt == 'Y')
											{
											$field_enc=$MT;
											$field_value = $custom_fields_row[$$varname];
											$field_value = base64_encode($field_value);
											exec("../agc/aes.pl --encrypt --text=$field_value", $field_enc);
											$field_enc_ct = count($field_enc); $k=0; $field_enc_all='';
											while ($field_enc_ct > $k) { $field_enc_all .= $field_enc[$k]; $k++; }
											$custom_fields_row[$$varname] = preg_replace("/CRYPT: |\n|\r|\t/",'',$field_enc_all);
											}
										}
									$custom_SQL_values.=",\"".$custom_fields_row[$$varname]."\"";
									}
								}
							$custom_ins_stmt.=") VALUES('$lead_id'$custom_SQL_values)";
							$custom_rslt=mysql_to_mysqli($custom_ins_stmt, $link);
							}
						}
					}
				else
					{
					# Standard or custom without custom fields - batched insert
					if ($multi_insert_counter > 8)
						{
						$stmtZ = "INSERT INTO vicidial_list (lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id) values$multistmt('',\"$entry_date\",\"$modify_date\",\"$status\",\"$user\",\"$vendor_lead_code\",\"$source_id\",\"$list_id\",\"$gmt_offset\",\"$called_since_last_reset\",\"$phone_code\",\"$phone_number\",\"$title\",\"$first_name\",\"$middle_initial\",\"$last_name\",\"$address1\",\"$address2\",\"$address3\",\"$city\",\"$state\",\"$province\",\"$postal_code\",\"$country_code\",\"$gender\",\"$date_of_birth\",\"$alt_phone\",\"$email\",\"$security_phrase\",\"$comments\",0,\"2008-01-01 00:00:00\",\"$rank\",\"$owner\",'0');";
						$rslt=mysql_to_mysqli($stmtZ, $link);
						if ( ($webroot_writable > 0) and ($DB>0) ) {fwrite($stmt_file, $stmtZ."\r\n");}
						$multistmt=''; $multi_insert_counter=0;
						}
					else
						{
						$multistmt .= "('',\"$entry_date\",\"$modify_date\",\"$status\",\"$user\",\"$vendor_lead_code\",\"$source_id\",\"$list_id\",\"$gmt_offset\",\"$called_since_last_reset\",\"$phone_code\",\"$phone_number\",\"$title\",\"$first_name\",\"$middle_initial\",\"$last_name\",\"$address1\",\"$address2\",\"$address3\",\"$city\",\"$state\",\"$province\",\"$postal_code\",\"$country_code\",\"$gender\",\"$date_of_birth\",\"$alt_phone\",\"$email\",\"$security_phrase\",\"$comments\",0,\"2008-01-01 00:00:00\",\"$rank\",\"$owner\",'0'),";
						$multi_insert_counter++;
						}
					}
				$good++;
				}
			else
				{
				if ( $list_id < 100 )
					{ $listid_error_details[] = array('record' => $Tline, 'phone' => $phone_number, 'list_id' => $list_id); }
				else
					{
					if ($valid_number < 1)
						{ $inv_phone_details[] = array('record' => $Tline, 'phone' => $phone_number, 'reason' => $invalid_reason); }
					else if ($dnc_matches > 0)
						{ $dnc_phone_details[] = array('record' => $Tline, 'phone' => $phone_number, 'reason' => $invalid_reason); }
					else
						{
						$dup++;
						if (!isset($dup_phone_details[$phone_number])) { $dup_phone_details[$phone_number] = array('count' => 0, 'lists' => array()); }
						$dup_phone_details[$phone_number]['count']++;
						if (strlen($dup_lead_list) > 0) { $dup_phone_details[$phone_number]['lists'][] = $dup_lead_list; }
						}
					}
				$bad++;
				}
			$total++;
			$Tline++;
			if ($total%100==0)
				{
				@file_put_contents($progress_file, "{$total}|{$total_lines}|processing");
				}
			}
		}
	##### END MAIN PROCESSING LOOP #####

	# Flush remaining batch
	if ($multi_insert_counter!=0)
		{
		$stmtZ = "INSERT INTO vicidial_list (lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id) values".substr($multistmt, 0, -1).";";
		mysql_to_mysqli($stmtZ, $link);
		if ( ($webroot_writable > 0) and ($DB>0) ) {fwrite($stmt_file, $stmtZ."\r\n");}
		}

	# Admin log
	$event_code = 'ADMIN LOAD LIST';
	if ($file_layout == 'template') { $event_code = 'ADMIN LOAD LIST TEMPLATE'; }
	if ($file_layout == 'standard') { $event_code = 'ADMIN LOAD LIST STANDARD'; }
	if ($file_layout == 'custom') { $event_code = 'ADMIN LOAD LIST CUSTOM'; }
	$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='LOAD', record_id='$list_id_override', event_code='$event_code', event_sql='', event_notes='File Name: $leadfile_name, GOOD: $good, BAD: $bad, MOVED: $moved, TOTAL: $total, DEBUG: dupcheck:$dupcheck| list_id_override:$list_id_override| phone_code_override:$phone_code_override| postalgmt:$postalgmt| template_id:$template_id| usacan_check:$usacan_check| dnc_country_scrub:$international_dnc_scrub| state_conversion:$state_conversion| web_loader_phone_length:$web_loader_phone_length| web_loader_phone_strip:$SSweb_loader_phone_strip|';";
	$rslt=mysql_to_mysqli($stmt, $link);

	# Write complete progress
	@file_put_contents($progress_file, "{$total}|{$total_lines}|complete");

	# Build summary
	$summary_html = build_summary_html($good, $bad, $total, $dup, $moved, $inv, $dup_phone_details, $inv_phone_details, $dnc_phone_details, $listid_error_details);

	# Prepare dup details for JSON (convert keyed array to simple array)
	$dup_detail_arr = array();
	foreach ($dup_phone_details as $phone => $info)
		{
		$dup_detail_arr[] = array('phone' => $phone, 'count' => $info['count'], 'lists' => implode(', ', array_unique($info['lists'])));
		}

	echo json_encode(array(
		'success' => true,
		'summary_html' => $summary_html,
		'good' => $good,
		'bad' => $bad,
		'total' => $total,
		'dup' => $dup,
		'moved' => $moved,
		'leadfile_name' => $leadfile_name,
		'dup_details' => $dup_detail_arr,
		'inv_details' => $inv_phone_details,
		'dnc_details' => $dnc_phone_details,
		'listid_details' => $listid_error_details,
	));
	exit;
	}


##### DEFAULT ACTION - Render HTML page with form + JavaScript #####
header ("Content-type: text/html; charset=utf-8");

# Get data needed for form dropdowns
$dedupe_status_select='';
$stmt="SELECT status, status_name from vicidial_statuses order by status;";
$rslt=mysql_to_mysqli($stmt, $link);
$stat_num_rows = mysqli_num_rows($rslt);
$snr_count=0;
while ($stat_num_rows > $snr_count)
	{
	$row=mysqli_fetch_row($rslt);
	$dedupe_status_select .= "\t\t\t<option value='$row[0]'>$row[0] - $row[1]</option>\n";
	$snr_count++;
	}

echo "<html>\n<head>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<!-- VERSION: $version     BUILD: $build -->\n";
echo "<title>"._QXZ("ADMINISTRATION: Lead Loader 6th Gen")."</title>\n";
echo "<script language=\"JavaScript1.2\">\n";
echo "function TemplateSpecs() {\n";
echo "	var template_field = document.getElementById(\"template_id\");\n";
echo "	var template_id_value = template_field.options[template_field.selectedIndex].value;\n";
echo "	var xmlhttp=false;\n";
echo "	try {\n";
echo "		xmlhttp = new ActiveXObject(\"Msxml2.XMLHTTP\");\n";
echo "	} catch (e) {\n";
echo "		try {\n";
echo "			xmlhttp = new ActiveXObject(\"Microsoft.XMLHTTP\");\n";
echo "		} catch (E) {\n";
echo "			xmlhttp = false;\n";
echo "		}\n";
echo "	}\n";
echo "	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {\n";
echo "		xmlhttp = new XMLHttpRequest();\n";
echo "	}\n";
echo "	if (xmlhttp && template_id_value!=\"\") { \n";
echo "		var vs_query = \"&template_id=\"+template_id_value;\n";
echo "		xmlhttp.open('POST', 'leadloader_template_display.php'); \n";
echo "		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');\n";
echo "		xmlhttp.send(vs_query); \n";
echo "		xmlhttp.onreadystatechange = function() { \n";
echo "			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {\n";
echo "				var TemplateInfo = null;\n";
echo "				TemplateInfo = xmlhttp.responseText;\n";
echo "				if (TemplateInfo.length>0)\n";
echo "				{\n";
echo "				alert(TemplateInfo);\n";
echo "				}\n";
echo "			}\n";
echo "		}\n";
echo "		delete xmlhttp;\n";
echo "	}\n";
echo "}\n";
echo "</script>\n";
echo "<style>
/* === Page Layout === */
.section {
	margin:10px 4px; opacity:1; transition: opacity 0.3s ease;
}
.section.hidden { display:none; opacity:0; }

/* === Card Wrapper === */
.card {
	background:#fff; border:1px solid #c0c0c0; border-radius:6px;
	box-shadow:0 2px 6px rgba(0,0,0,0.08); overflow:hidden;
}

/* === Section Headers === */
.section-header {
	background-color:#$SSmenu_background; color:#fff; padding:8px 14px;
	font-family:Arial,Helvetica,sans-serif; font-size:13px; font-weight:bold;
	letter-spacing:0.3px; text-transform:uppercase;
}

/* === Form Table === */
.form-table { width:100%; border-collapse:collapse; }
.form-table td { padding:6px 12px; font-family:Arial,Helvetica,sans-serif; font-size:13px; vertical-align:middle; }
.form-table tr:nth-child(odd) td { background:#f7faff; }
.form-table tr:nth-child(even) td { background:#fff; }
.form-table tr:hover td { background:#eef3fc; }

/* === Field Labels & Inputs === */
.field-label { font-family:Arial,Helvetica,sans-serif; font-size:13px; font-weight:bold; color:#333; }
.field-input { font-family:Arial,Helvetica,sans-serif; font-size:12px; color:#333; }
.field-note { font-family:Arial,Helvetica,sans-serif; font-size:11px; color:#666; }
.field-suggestion { font-family:Arial,Helvetica,sans-serif; font-size:8px; color:#000; }

/* === Form Controls === */
.form-table select, .form-table input[type=text] {
	padding:4px 8px; border:1px solid #a0b4d0; border-radius:3px;
	font-family:Arial,Helvetica,sans-serif; font-size:12px;
	background:#fff; color:#333; transition:border-color 0.2s;
}
.form-table select:focus, .form-table input[type=text]:focus {
	border-color:#$SSmenu_background; outline:none;
	box-shadow:0 0 0 2px rgba(1,91,145,0.15);
}
.form-table input[type=file] {
	font-family:Arial,Helvetica,sans-serif; font-size:12px;
}
.form-table input[type=radio] { margin:0 3px 0 0; vertical-align:middle; }
.form-table input[type=checkbox] { margin:0 4px; vertical-align:middle; }

/* === Buttons === */
.btn {
	display:inline-block; padding:8px 24px; border:none; border-radius:4px;
	font-family:Arial,Helvetica,sans-serif; font-size:13px; font-weight:bold;
	cursor:pointer; transition:filter 0.15s, box-shadow 0.15s;
	color:#000; text-decoration:none;
}
.btn:hover { filter:brightness(0.9); box-shadow:0 2px 4px rgba(0,0,0,0.15); }
.btn:active { filter:brightness(0.85); }
.btn-primary { background-color:#$SSbutton_color; }
.btn-secondary { background-color:#ddd; color:#333; }

/* === Progress Bar === */
.progress-container {
	width:600px; margin:16px auto; background:#ddd; border:1px solid #bbb;
	border-radius:6px; overflow:hidden; box-shadow:inset 0 1px 3px rgba(0,0,0,0.12);
	position:relative; height:28px;
}
.progress-bar {
	height:100%; background:#009900; width:0%; transition:width 0.3s ease;
	background-image:linear-gradient(
		-45deg,
		rgba(255,255,255,0.15) 25%, transparent 25%,
		transparent 50%, rgba(255,255,255,0.15) 50%,
		rgba(255,255,255,0.15) 75%, transparent 75%
	);
	background-size:30px 30px;
	animation:progress-stripes 1s linear infinite;
}
@keyframes progress-stripes {
	from { background-position:0 0; }
	to { background-position:30px 0; }
}
.progress-pct {
	position:absolute; top:0; left:0; right:0; height:28px;
	line-height:28px; text-align:center;
	font-family:Arial,Helvetica,sans-serif; font-size:13px; font-weight:bold;
	color:#fff; text-shadow:0 1px 2px rgba(0,0,0,0.4);
	pointer-events:none;
}
.progress-detail {
	text-align:center; font-family:Arial,Helvetica,sans-serif;
	font-size:12px; color:#555; margin-top:6px;
}

/* === Upload Spinner === */
.upload-spinner {
	display:inline-block; width:28px; height:28px;
	border:3px solid #ddd; border-top-color:#009900;
	border-radius:50%; animation:spin 0.8s linear infinite;
	vertical-align:middle; margin-right:10px;
}
@keyframes spin {
	to { transform:rotate(360deg); }
}
.upload-msg {
	font-family:Arial,Helvetica,sans-serif; font-size:16px;
	font-weight:bold; color:#009900; vertical-align:middle;
}

/* === Field Mapper === */
.mapper-table { width:700px; margin:0 auto; border-collapse:collapse; }
.mapper-table th {
	padding:8px 12px; font-family:Arial,Helvetica,sans-serif; font-size:13px;
	font-weight:bold; color:#fff; background:#$SSmenu_background;
	border-left:3px solid #$SSmenu_background;
}
.mapper-table td {
	padding:5px 10px; font-family:Arial,Helvetica,sans-serif; font-size:12px;
	border-bottom:1px solid #e0e0e0;
}
.mapper-table tr:hover td { background:#eef3fc; }
.mapper-table tr.auto-row td { border-left:3px solid #4caf50; }
.mapper-table select {
	padding:3px 6px; border:1px solid #a0b4d0; border-radius:3px;
	font-size:12px; width:90%;
}
.auto-badge {
	display:inline-block; background:#4caf50; color:#fff; padding:4px 12px;
	border-radius:12px; font-family:Arial,Helvetica,sans-serif; font-size:12px;
	font-weight:bold; margin:6px 0;
}

/* === Summary Section === */
.summary-card {
	background:#fff; border:1px solid #c0c0c0; border-radius:6px;
	box-shadow:0 2px 6px rgba(0,0,0,0.08); padding:16px; margin:10px 0;
}
.summary-stats-table { width:100%; border-collapse:collapse; margin-bottom:12px; }
.summary-stats-table td { padding:8px 14px; font-family:Arial,Helvetica,sans-serif; font-size:13px; }
.summary-stats-table .stat-label { text-align:right; width:50%; font-weight:bold; color:#fff; }
.summary-stats-table .stat-value { text-align:left; font-weight:bold; color:#fff; }
.summary-detail-section { margin-top:12px; }
.summary-detail-section .detail-header {
	padding:6px 12px; cursor:pointer; font-family:Arial,Helvetica,sans-serif;
	font-size:12px; font-weight:bold; color:#fff; border-radius:4px;
	margin-bottom:4px; display:flex; justify-content:space-between; align-items:center;
}
.summary-detail-section .detail-header:hover { filter:brightness(0.9); }
.summary-detail-section .detail-body {
	max-height:300px; overflow-y:auto; border:1px solid #ddd;
	border-radius:0 0 4px 4px; margin-bottom:8px;
}
.summary-detail-section table { width:100%; border-collapse:collapse; }
.summary-detail-section th {
	padding:5px 10px; font-family:Arial,Helvetica,sans-serif; font-size:11px;
	font-weight:bold; background:#f0f0f0; text-align:left; position:sticky; top:0;
}
.summary-detail-section td {
	padding:4px 10px; font-family:Arial,Helvetica,sans-serif; font-size:11px;
	border-bottom:1px solid #eee;
}
.download-btn-group { text-align:center; margin:16px 0 8px; }
.download-btn-group .btn { margin:0 6px; }

/* === Footer Links === */
.footer-row td {
	padding:8px 12px; font-family:Arial,Helvetica,sans-serif; font-size:11px;
}
.footer-row a { color:#$SSmenu_background; text-decoration:none; }
.footer-row a:hover { text-decoration:underline; }
</style>\n";
echo "</head>\n";
echo "<BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;
require("admin_header.php");

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>\n";

?>

<!-- SECTION: Upload Form -->
<div id="section-form" class="section">
<form id="loaderForm" enctype="multipart/form-data" onsubmit="return false;">
<input type="hidden" name="DB" value="<?php echo $DB ?>">
<div class="card">

<!-- File Selection -->
<div class="section-header"><?php echo _QXZ("File Selection"); ?></div>
<table class="form-table">
  <tr>
	<td align=right width="25%"><span class="field-label"><?php echo _QXZ("Load leads from this file"); ?>:</span></td>
	<td width="75%"><input type=file name="leadfile" id="leadfile"> <?php echo "$NWB#list_loader$NWE"; ?></td>
  </tr>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("File layout to use"); ?>:</span></td>
	<td><span class="field-input">
	<input type=radio name="file_layout" value="custom" checked><?php echo _QXZ("Custom layout"); ?>&nbsp;&nbsp;
	<input type=radio name="file_layout" value="standard"><?php echo _QXZ("Standard Format"); ?>&nbsp;&nbsp;
	<input type=radio name="file_layout" value="template"><?php echo _QXZ("Custom Template"); ?> <?php echo "$NWB#list_loader-file_layout$NWE"; ?>
	</span></td>
  </tr>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("Attempt column auto-detection"); ?>:</span></td>
	<td align=left><span class="field-input"><input type=radio name="attempt_auto_detect" value="Y" checked><?php echo _QXZ("Yes"); ?>&nbsp;&nbsp;&nbsp;<input type=radio name="attempt_auto_detect" value="N"><?php echo _QXZ("No"); ?>&nbsp;&nbsp;<?php echo "$NWB#listloader-autodetect$NWE"; ?></span></td>
  </tr>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("Custom Layout to Use"); ?>:</span></td>
	<td><select name="template_id" id="template_id">
	<?php
	$template_stmt="SELECT template_id, template_name FROM vicidial_custom_leadloader_templates WHERE list_id IN (SELECT list_id FROM vicidial_lists $whereLOGallowed_campaignsSQL) ORDER BY template_id asc;";
	$template_rslt=mysql_to_mysqli($template_stmt, $link);
	if (mysqli_num_rows($template_rslt)>0) {
		echo "<option value='' selected>--"._QXZ("Select custom template")."--</option>";
		while ($row=mysqli_fetch_array($template_rslt)) {
			echo "<option value='$row[template_id]'>$row[template_id] - $row[template_name]</option>";
		}
	} else {
		echo "<option value='' selected>--"._QXZ("No custom templates defined")."--</option>";
	}
	?>
	</select><a href='AST_admin_template_maker.php'><font face="arial, helvetica" size=1><?php echo _QXZ("template builder"); ?></font></a><?php echo "$NWB#list_loader-template_id$NWE"; ?><BR><a href='#' onClick="TemplateSpecs()"><font face="arial, helvetica" size=1><?php echo _QXZ("View template info"); ?></font></a></td>
  </tr>
</table>

<!-- List & Phone Options -->
<div class="section-header"><?php echo _QXZ("List & Phone Options"); ?></div>
<table class="form-table">
  <tr>
	<td align=right width="25%"><span class="field-label"><?php echo _QXZ("List ID Override"); ?>:</span></td>
	<td width="75%">
	<select name='list_id_override' id='list_id_override'>
	<option value='' selected><?php echo _QXZ("Load from Lead File"); ?></option>
	<?php
	$stmt="SELECT list_id, list_name from vicidial_lists $whereLOGallowed_campaignsSQL order by list_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$num_rows = mysqli_num_rows($rslt);
	$count=0;
	while ( $num_rows > $count )
		{
		$row = mysqli_fetch_row($rslt);
		echo "<option value='$row[0]'>$row[0] - $row[1]</option>\n";
		$count++;
		}
	?>
	</select><input type='checkbox' name='master_list_override' id='master_list_override' value='1'><span class="field-note">(<?php echo _QXZ("override template setting"); ?>)</span>
	</td>
  </tr>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("Phone Code Override"); ?>:</span></td>
	<td>
	<select name='phone_code_override' id='phone_code_override'>
	<option value='' selected><?php echo _QXZ("Load from Lead File"); ?></option>
	<?php
	$stmt="SELECT distinct country_code, country from vicidial_phone_codes;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$num_rows = mysqli_num_rows($rslt);
	$count=0;
	while ( $num_rows > $count )
		{
		$row = mysqli_fetch_row($rslt);
		echo "<option value='$row[0]'>$row[0] - $row[1]</option>\n";
		$count++;
		}
	?>
	</select></td>
  </tr>
</table>

<!-- Validation Options -->
<div class="section-header"><?php echo _QXZ("Validation Options"); ?></div>
<table class="form-table">
  <tr>
	<td align=right width="25%"><span class="field-label"><?php echo _QXZ("Lead Duplicate Check"); ?>:</span></td>
	<td width="75%"><select name=dupcheck id=dupcheck>
	<option selected value="NONE"><?php echo _QXZ("NO DUPLICATE CHECK"); ?></option>
	<option value="DUPLIST"><?php echo _QXZ("CHECK FOR DUPLICATES BY PHONE IN LIST ID"); ?></option>
	<option value="DUPCAMP"><?php echo _QXZ("CHECK FOR DUPLICATES BY PHONE IN ALL CAMPAIGN LISTS"); ?></option>
	<option value="DUPSYS"><?php echo _QXZ("CHECK FOR DUPLICATES BY PHONE IN ENTIRE SYSTEM"); ?></option>
	<option value="DUPLIST30DAY"><?php echo _QXZ("CHECK FOR DUPLICATES LOADED IN LAST 30 DAYS BY PHONE IN LIST ID"); ?></option>
	<option value="DUPCAMP30DAY"><?php echo _QXZ("CHECK FOR DUPLICATES LOADED IN LAST 30 DAYS BY PHONE IN ALL CAMPAIGN LISTS"); ?></option>
	<option value="DUPSYS30DAY"><?php echo _QXZ("CHECK FOR DUPLICATES LOADED IN LAST 30 DAYS BY PHONE IN ENTIRE SYSTEM"); ?></option>
	<option value="DUPLIST90DAY"><?php echo _QXZ("CHECK FOR DUPLICATES LOADED IN LAST 90 DAYS BY PHONE IN LIST ID"); ?></option>
	<option value="DUPCAMP90DAY"><?php echo _QXZ("CHECK FOR DUPLICATES LOADED IN LAST 90 DAYS BY PHONE IN ALL CAMPAIGN LISTS"); ?></option>
	<option value="DUPSYS90DAY"><?php echo _QXZ("CHECK FOR DUPLICATES LOADED IN LAST 90 DAYS BY PHONE IN ENTIRE SYSTEM"); ?></option>
	<option value="DUPTITLEALTPHONELIST"><?php echo _QXZ("CHECK FOR DUPLICATES BY TITLE/ALT-PHONE IN LIST ID"); ?></option>
	<option value="DUPTITLEALTPHONESYS"><?php echo _QXZ("CHECK FOR DUPLICATES BY TITLE/ALT-PHONE IN ENTIRE SYSTEM"); ?></option>
	</select> <?php echo "$NWB#list_loader-duplicate_check$NWE"; ?></td>
  </tr>
<?php if ($SSenable_international_dncs) {
	$dnc_stmt="select iso3, country_name from vicidial_country_iso_tld where iso3 is not null and iso3!='' order by country_name asc";
	$dnc_rslt=mysql_to_mysqli($dnc_stmt, $link);
	$drop_down_dnc_options='';
	while($dnc_row=mysqli_fetch_row($dnc_rslt)) {
		$iso=$dnc_row[0]; $country_name=$dnc_row[1];
		$dnc_table_stmt="show tables like 'vicidial_dnc_".$iso."'";
		$dnc_table_rslt=mysql_to_mysqli($dnc_table_stmt, $link);
		if (mysqli_num_rows($dnc_table_rslt)>0) { $drop_down_dnc_options.="<option value='$iso'>$iso - $country_name</option>\n"; }
	}
	echo "<tr><td align=right width='25%'><span class='field-label'>"._QXZ("DNC Scrub by Country").":</span></td>";
	echo "<td><select name='international_dnc_scrub' id='international_dnc_scrub'><option value=''>--</option>$drop_down_dnc_options</select></td></tr>\n";
} ?>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("USA-Canada Check"); ?>:</span></td>
	<td><select name=usacan_check id=usacan_check>
	<option selected value="NONE"><?php echo _QXZ("NO USACAN VALID CHECK"); ?></option>
	<option value="PREFIX"><?php echo _QXZ("CHECK FOR VALID PREFIX"); ?></option>
	<option value="AREACODE"><?php echo _QXZ("CHECK FOR VALID AREACODE"); ?></option>
	<option value="NANPA"><?php echo _QXZ("CHECK FOR VALID NANPA PREFIX and AREACODE"); ?></option>
	</select></td>
  </tr>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("Required Phone Number Length"); ?>:</span></td>
	<td><select name=web_loader_phone_length id=web_loader_phone_length>
	<?php if ($SSweb_loader_phone_length == 'DISABLED') { ?>
	<option selected value=""><?php echo _QXZ("DISABLED"); ?></option>
	<?php } elseif ($SSweb_loader_phone_length == 'CHOOSE') { ?>
	<option selected value=""><?php echo _QXZ("DISABLED"); ?></option>
	<option>5</option><option>6</option><option>7</option><option>8</option><option>9</option><option>10</option><option>11</option><option>12</option><option>13</option><option>14</option><option>15</option><option>16</option><option>17</option><option>18</option>
	<?php } elseif ( (strlen($SSweb_loader_phone_length) > 0) and (strlen($SSweb_loader_phone_length) < 3) and ($SSweb_loader_phone_length > 4) and ($SSweb_loader_phone_length < 19) ) { ?>
	<option selected value="<?php echo $SSweb_loader_phone_length ?>"><?php echo $SSweb_loader_phone_length ?></option>
	<?php } ?>
	</select></td>
  </tr>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("Invalid Phone Number Replacement"); ?>:</span></td>
	<td><select name=invalid_phone_override id=invalid_phone_override>
	<option selected value="DISABLED"><?php echo _QXZ("DISABLED"); ?></option>
	<option value="alt_phone_invalid"><?php echo _QXZ("Alt Phone - INVALID ONLY"); ?></option>
	<option value="alt_phone_duplicate"><?php echo _QXZ("Alt Phone - DUPLICATE ONLY"); ?></option>
	<option value="alt_phone_invalid_duplicate"><?php echo _QXZ("Alt Phone - INVALID and DUPLICATE"); ?></option>
	<option value="alt_phone_then_address3_invalid"><?php echo _QXZ("Alt Phone then Address 3 - INVALID ONLY"); ?></option>
	<option value="alt_phone_then_address3_invalid_duplicate"><?php echo _QXZ("Alt Phone then Address 3 - INVALID and DUPLICATE"); ?></option>
	</select></td>
  </tr>
</table>

<!-- Advanced Options -->
<div class="section-header"><?php echo _QXZ("Advanced Options"); ?></div>
<table class="form-table">
  <tr>
	<td align=right width="25%"><span class="field-label"><?php echo _QXZ("Status Duplicate Check"); ?>:</span></td>
	<td width="75%">
	<select id='dedupe_statuses' name='dedupe_statuses[]' size=5 multiple>
	<option value='--ALL--' selected>--<?php echo _QXZ("ALL DISPOSITIONS"); ?>--</option>
	<?php echo $dedupe_status_select ?>
	</select>
	</td>
  </tr>
<?php if ($enable_status_mismatch_leadloader_option>0) { ?>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("Status Mismatch Action"); ?>:</span></td>
	<td>
	<select id='status_mismatch_action' name='status_mismatch_action'>
	<option value='' selected><?php echo _QXZ("NONE"); ?></option>
	<option value='MOVE RECENT FROM SYSTEM'><?php echo _QXZ("MOVE MOST RECENT PHONE DUPLICATE, CHECK ENTIRE SYSTEM"); ?></option>
	<option value='MOVE ALL FROM SYSTEM'><?php echo _QXZ("MOVE ALL PHONE DUPLICATES, CHECK ENTIRE SYSTEM"); ?></option>
	<option value='MOVE RECENT USING CHECK'><?php echo _QXZ("MOVE MOST RECENT PHONE FROM DUPLICATE CHECK TO CURRENT LIST"); ?></option>
	<option value='MOVE ALL USING CHECK'><?php echo _QXZ("MOVE ALL PHONES FROM DUPLICATE CHECK TO CURRENT LIST"); ?></option>
	</select>
	</td>
  </tr>
<?php } ?>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("Lead Time Zone Lookup"); ?>:</span></td>
	<td><select name=postalgmt id=postalgmt>
	<option selected value="AREA"><?php echo _QXZ("COUNTRY CODE AND AREA CODE ONLY"); ?></option>
	<option value="POSTAL"><?php echo _QXZ("POSTAL CODE FIRST"); ?></option>
	<option value="TZCODE"><?php echo _QXZ("OWNER TIME ZONE CODE FIRST"); ?></option>
	<option value="NANPA"><?php echo _QXZ("NANPA AREACODE PREFIX FIRST"); ?></option>
	</select></td>
  </tr>
  <tr>
	<td align=right><span class="field-label"><?php echo _QXZ("State Abbreviation Lookup"); ?>:</span></td>
	<td><select name=state_conversion id=state_conversion>
	<option selected value=""><?php echo _QXZ("DISABLED"); ?></option>
	<option value="STATELOOKUP"><?php echo _QXZ("FULL STATE NAME TO ABBREVIATION"); ?></option>
	</select></td>
  </tr>
</table>

<!-- Submit Buttons -->
<table class="form-table">
  <tr>
	<td align=center colspan=2 style="padding:14px; background:#f0f4fa;">
	<input class="btn btn-primary" type=button onclick="uploadFile()" value="<?php echo _QXZ("SUBMIT"); ?>" id="btn_submit">
	&nbsp;&nbsp;&nbsp;&nbsp;
	<input class="btn btn-secondary" type=button onclick="location.reload()" value="<?php echo _QXZ("START OVER"); ?>">
	</td>
  </tr>
</table>

</div><!-- /card -->

<table width="100%"><tr class="footer-row">
  <td align=left>&nbsp;&nbsp;<a href="admin.php?ADD=100" target="_parent"><?php echo _QXZ("BACK TO ADMIN"); ?></a></td>
  <td align=right><?php echo _QXZ("LIST LOADER 6th Gen"); ?> | <a href="admin_listloader_fifth_gen.php"><?php echo _QXZ("5th Gen"); ?></a> &nbsp; <?php echo _QXZ("VERSION"); ?>: <?php echo $version ?> &nbsp; <?php echo _QXZ("BUILD"); ?>: <?php echo $build ?></td>
</tr></table>
</form>
</div>

<!-- SECTION: Uploading indicator -->
<div id="section-uploading" class="section hidden">
<div style="text-align:center; padding:40px 0;">
<div class="upload-spinner"></div>
<span class="upload-msg"><?php echo _QXZ("Uploading and analyzing file"); ?>...</span>
</div>
</div>

<!-- SECTION: Field Mapper (custom layout) -->
<div id="section-mapper" class="section hidden">
<div id="mapper-content"></div>
</div>

<!-- SECTION: Processing with progress bar -->
<div id="section-processing" class="section hidden">
<div style="text-align:center; padding:30px 0;">
<div style="font-family:Arial,Helvetica,sans-serif; font-size:16px; font-weight:bold; color:#009900; margin-bottom:16px;"><?php echo _QXZ("Processing file"); ?>...</div>
<div class="progress-container">
	<div class="progress-bar" id="progressBar"></div>
	<div class="progress-pct" id="progressPct">0%</div>
</div>
<div class="progress-detail" id="progressDetail">&nbsp;</div>
</div>
</div>

<!-- SECTION: Summary -->
<div id="section-summary" class="section hidden">
<div class="summary-card">
<div id="summary-content"></div>
<div class="download-btn-group">
<input class="btn" type=button onclick="downloadSummaryCSV()" value="<?php echo _QXZ("Download Summary Report"); ?>" style="background-color:#006699; color:#fff;">
<input class="btn" type=button onclick="downloadDetailCSV()" value="<?php echo _QXZ("Download Detailed Report"); ?>" style="background-color:#993300; color:#fff;">
</div>
<div style="text-align:center; margin-top:12px;">
<input class="btn btn-primary" type=button onclick="location.reload()" value="<?php echo _QXZ("Load Another Lead File"); ?>">
</div>
</div>
</div>

<script language="JavaScript">
var uploadResult = null;
var processResult = null;
var progressId = '';
var progressTimer = null;
var PHP_SELF = '<?php echo $PHP_SELF; ?>';

function showSection(name) {
	var sections = ['form','uploading','mapper','processing','summary'];
	for (var i=0; i<sections.length; i++) {
		var el = document.getElementById('section-'+sections[i]);
		if (!el) continue;
		if (sections[i]==name) {
			el.classList.remove('hidden');
			el.style.display = '';
			// trigger reflow then fade in
			void el.offsetWidth;
			el.style.opacity = '1';
		} else {
			el.classList.add('hidden');
			el.style.display = 'none';
			el.style.opacity = '0';
		}
	}
}

function getSelectedLayout() {
	var radios = document.getElementsByName('file_layout');
	for (var i=0; i<radios.length; i++) { if (radios[i].checked) return radios[i].value; }
	return 'custom';
}

function escHtml(s) {
	var d=document.createElement('div'); d.appendChild(document.createTextNode(s)); return d.innerHTML;
}

function genProgressId() {
	var c='abcdef0123456789', id='';
	for (var i=0;i<32;i++) id+=c.charAt(Math.floor(Math.random()*c.length));
	return id;
}

function uploadFile() {
	var lf = document.getElementById('leadfile');
	if (!lf || !lf.value) { alert('<?php echo _QXZ("Please select a file"); ?>'); return; }
	showSection('uploading');

	var form = document.getElementById('loaderForm');
	var fd = new FormData(form);
	fd.append('action', 'upload');

	var xhr = new XMLHttpRequest();
	xhr.open('POST', PHP_SELF);
	xhr.onload = function() {
		if (xhr.status != 200) { alert('Upload failed: HTTP '+xhr.status); showSection('form'); return; }
		try { var r = JSON.parse(xhr.responseText); } catch(e) { alert('Invalid server response'); showSection('form'); return; }
		if (r.error) { alert(r.error); showSection('form'); return; }
		uploadResult = r;
		var layout = getSelectedLayout();
		if (layout == 'custom') { showFieldMapper(r); }
		else { startProcessing(); }
	};
	xhr.onerror = function() { alert('Network error during upload'); showSection('form'); };
	xhr.send(fd);
}

function showFieldMapper(data) {
	showSection('mapper');
	var minimum_required_score=<?php echo $min_req_score; ?>;
	var allFields = data.vicidial_fields.concat(data.custom_fields || []);
	var autoCount = 0;
	for (var k in data.auto_map) { if (data.auto_map.hasOwnProperty(k)) autoCount++; }

	var h = '<div class="card" style="max-width:740px; margin:0 auto;">';

	var hb = '<table class="mapper-table">';
	hb += '<tr><th align=right><?php echo _QXZ("VICIDIAL Column"); ?></th>';
	hb += '<th><?php echo _QXZ("File data"); ?></th></tr>';

	for (var i=0; i<allFields.length; i++) {
		var fn = allFields[i];

		console.log(data.auto_map[fn]);

		var isAuto = (data.auto_map[fn] !== undefined);

		if (isAuto)
			{
			var auto_map_stats=data.auto_map[fn].toString().split(",");
			var best_index=auto_map_stats[0];
			var best_score=auto_map_stats[1];
			var best_field_match=auto_map_stats[2];

			autoCount += (best_score>=minimum_required_score) ? 0 : -1;
			}
		else
			{
			var best_index=-1;
			var best_score=0;
			var best_field_match="";
			}

//		var isAuto = (data.auto_map[fn] !== undefined);
//		var autoIdx = isAuto ? data.auto_map[fn] : -1;
		var autoIdx = isAuto ? best_index : -1;
		var rowClass = (isAuto && best_score>=minimum_required_score) ? ' class="auto-row"' : '';
		var chk = (isAuto && best_score>=minimum_required_score) ? ' <span style="color:#4caf50; font-weight:bold;">&#10004;</span>' : '';
		var isCust = (i >= data.vicidial_fields.length) ? ' <span class="field-note">(<?php echo _QXZ("custom"); ?>)</span>' : '';

		var fail_text = (best_score>0 && best_score<minimum_required_score) ? '<BR><div align="left"><font class="standard_small"><font class="small_standard">&nbsp;&nbsp;<a onmouseover="javascript:document.getElementById(\'best_match_'+fn+'\').style.display=\'inline\'" onmouseout="javascript:document.getElementById(\'best_match_'+fn+'\').style.display=\'none\'"><?php echo _QXZ("Show best match"); ?></a></font>&nbsp;<span style="display: none;" id="best_match_'+fn+'"><font class="small_standard_bold" color="#F00">"'+best_field_match+'", <?php echo _QXZ("score"); ?> '+best_score+'</font></span></font></div>' : '';

		hb += '<tr'+rowClass+'><td align=right><span class="field-label">'+fn.toUpperCase().replace(/_/g,' ')+isCust+chk+':</span></td>';
		hb += '<td align=left><select name="'+fn+'_field" id="'+fn+'_field">';
		hb += '<option value="-1">(none)</option>';
		for (var j=0; j<data.headers.length; j++) {
			var sel = (j==autoIdx && best_score>=minimum_required_score) ? ' selected' : '';
			var lbl = (j==autoIdx && best_score>=minimum_required_score) ? ' [AUTO]' : '';

			hb += '<option value="'+j+'"'+sel+'>"'+escHtml(data.headers[j])+'"'+lbl+'</option>';
		}
		hb += '</select>'+fail_text+'</td></tr>';
	}

	h += '<div class="section-header"><?php echo _QXZ("Field Mapping"); ?>';
	if (autoCount > 0) {
		h += ' &nbsp;<span class="auto-badge">'+autoCount+' of '+allFields.length+' <?php echo _QXZ("auto-detected"); ?></span>';
	}
	h += '</div>';


	hb += '<tr><td colspan=2 style="text-align:center; padding:14px; background:#f0f4fa;">';
	hb += '<input class="btn btn-primary" type=button onclick="startProcessing()" value="<?php echo _QXZ("OK TO PROCESS"); ?>">';
	hb += '&nbsp;&nbsp;&nbsp;&nbsp;';
	hb += '<input class="btn btn-secondary" type=button onclick="location.reload()" value="<?php echo _QXZ("START OVER"); ?>">';
	hb += '</td></tr></table></div>';

	document.getElementById('mapper-content').innerHTML = h+hb;
}

function startProcessing() {
	showSection('processing');
	progressId = genProgressId();

	var fd = new FormData();
	fd.append('action', 'process');
	fd.append('lead_file', uploadResult.lead_file);
	fd.append('leadfile_name', uploadResult.leadfile_name);
	fd.append('total_lines', uploadResult.total_lines);
	fd.append('progress_id', progressId);
	fd.append('file_layout', getSelectedLayout());

	// Form fields
	var fields = ['list_id_override','phone_code_override','dupcheck','postalgmt','usacan_check',
		'state_conversion','web_loader_phone_length','invalid_phone_override','template_id',
		'international_dnc_scrub','status_mismatch_action','DB'];
	for (var i=0; i<fields.length; i++) {
		var el = document.getElementById(fields[i]);
		if (el) fd.append(fields[i], el.value);
	}
	// master_list_override checkbox
	var mlo = document.getElementById('master_list_override');
	if (mlo && mlo.checked) fd.append('master_list_override', '1');

	// Dedupe statuses
	var ds = document.getElementById('dedupe_statuses');
	if (ds) {
		for (var i=0; i<ds.options.length; i++) {
			if (ds.options[i].selected) fd.append('dedupe_statuses[]', ds.options[i].value);
		}
	}

	// Custom layout field mappings
	if (getSelectedLayout() == 'custom' && uploadResult) {
		var af = (uploadResult.vicidial_fields || []).concat(uploadResult.custom_fields || []);
		for (var i=0; i<af.length; i++) {
			var sel = document.getElementById(af[i]+'_field');
			if (sel) fd.append(af[i]+'_field', sel.value);
		}
	}

	// Start progress polling
	progressTimer = setInterval(pollProgress, 1000);

	var xhr = new XMLHttpRequest();
	xhr.open('POST', PHP_SELF);
	xhr.onload = function() {
		clearInterval(progressTimer);
		updateProgress(100, uploadResult.total_lines, uploadResult.total_lines);
		try { var r = JSON.parse(xhr.responseText); } catch(e) { alert('Processing failed: invalid response\n'+xhr.responseText.substring(0,500)); showSection('form'); return; }
		if (r.error) { alert(r.error); showSection('form'); return; }
		processResult = r;
		showSection('summary');
		document.getElementById('summary-content').innerHTML = r.summary_html;
	};
	xhr.onerror = function() { clearInterval(progressTimer); alert('Network error during processing'); showSection('form'); };
	xhr.send(fd);
}

function pollProgress() {
	var xhr = new XMLHttpRequest();
	xhr.open('GET', PHP_SELF+'?action=progress&id='+encodeURIComponent(progressId));
	xhr.onload = function() {
		try {
			var r = JSON.parse(xhr.responseText);
			if (r.status != 'waiting') { updateProgress(r.pct, r.current, r.total); }
		} catch(e) {}
	};
	xhr.send();
}

function updateProgress(pct, current, total) {
	var bar = document.getElementById('progressBar');
	var pctEl = document.getElementById('progressPct');
	var detail = document.getElementById('progressDetail');
	if (bar) bar.style.width = pct+'%';
	if (pctEl) pctEl.innerHTML = pct+'%';
	if (detail) {
		var cur = Number(current).toLocaleString();
		var tot = Number(total).toLocaleString();
		detail.innerHTML = '<?php echo _QXZ("Processing record"); ?> '+cur+' <?php echo _QXZ("of"); ?> '+tot+'...';
	}
}

function csvEscape(val) {
	if (val === null || val === undefined) return '';
	var s = String(val);
	if (s.indexOf(',') >= 0 || s.indexOf('"') >= 0 || s.indexOf('\n') >= 0) {
		return '"' + s.replace(/"/g, '""') + '"';
	}
	return s;
}

function triggerDownload(filename, csvContent) {
	var blob = new Blob([csvContent], {type:'text/csv;charset=utf-8;'});
	var link = document.createElement('a');
	link.href = URL.createObjectURL(blob);
	link.download = filename;
	link.style.display = 'none';
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);
}

function downloadSummaryCSV() {
	if (!processResult) { alert('No results available'); return; }
	var r = processResult;
	var ts = new Date().toISOString().slice(0,19).replace(/[T:]/g, '-');
	var fname = (r.leadfile_name || 'leadload') + '_summary_' + ts + '.csv';
	var lines = [];
	lines.push('Category,Count');
	lines.push('Good,' + r.good);
	lines.push('Bad,' + r.bad);
	lines.push('Total,' + r.total);
	lines.push('Duplicates,' + r.dup);
	lines.push('Moved,' + r.moved);
	lines.push('Invalid Numbers,' + (r.inv_details ? r.inv_details.length : 0));
	lines.push('DNC Matches,' + (r.dnc_details ? r.dnc_details.length : 0));
	lines.push('Invalid List ID,' + (r.listid_details ? r.listid_details.length : 0));

	// Duplicate breakdown by list
	if (r.dup_details && r.dup_details.length > 0) {
		lines.push('');
		lines.push('Duplicate Breakdown by List ID,Count');
		var listCounts = {};
		for (var i=0; i<r.dup_details.length; i++) {
			var lists = r.dup_details[i].lists.split(',');
			for (var j=0; j<lists.length; j++) {
				var lid = lists[j].trim();
				if (lid) { listCounts[lid] = (listCounts[lid]||0) + r.dup_details[i].count; }
			}
		}
		for (var lid in listCounts) {
			if (listCounts.hasOwnProperty(lid)) lines.push('List ' + lid + ',' + listCounts[lid]);
		}
	}

	// Invalid breakdown by reason
	if (r.inv_details && r.inv_details.length > 0) {
		lines.push('');
		lines.push('Invalid Numbers by Reason,Count');
		var reasons = {};
		for (var i=0; i<r.inv_details.length; i++) {
			var re = r.inv_details[i].reason || 'Unknown';
			reasons[re] = (reasons[re]||0) + 1;
		}
		for (var re in reasons) {
			if (reasons.hasOwnProperty(re)) lines.push(csvEscape(re) + ',' + reasons[re]);
		}
	}

	// DNC breakdown by reason
	if (r.dnc_details && r.dnc_details.length > 0) {
		lines.push('');
		lines.push('DNC Matches by Reason,Count');
		var dncReasons = {};
		for (var i=0; i<r.dnc_details.length; i++) {
			var re = r.dnc_details[i].reason || 'Unknown';
			dncReasons[re] = (dncReasons[re]||0) + 1;
		}
		for (var re in dncReasons) {
			if (dncReasons.hasOwnProperty(re)) lines.push(csvEscape(re) + ',' + dncReasons[re]);
		}
	}

	triggerDownload(fname, lines.join('\r\n'));
}

function downloadDetailCSV() {
	if (!processResult) { alert('No results available'); return; }
	var r = processResult;
	var ts = new Date().toISOString().slice(0,19).replace(/[T:]/g, '-');
	var fname = (r.leadfile_name || 'leadload') + '_detail_' + ts + '.csv';
	var lines = [];
	lines.push('Type,Phone Number,Record,Reason/Info,List ID(s),Count');

	if (r.dup_details) {
		for (var i=0; i<r.dup_details.length; i++) {
			var d = r.dup_details[i];
			lines.push('Duplicate,' + csvEscape(d.phone) + ',,' + csvEscape('Duplicate in: ' + d.lists) + ',' + csvEscape(d.lists) + ',' + d.count);
		}
	}
	if (r.inv_details) {
		for (var i=0; i<r.inv_details.length; i++) {
			var d = r.inv_details[i];
			lines.push('Invalid,' + csvEscape(d.phone) + ',' + d.record + ',' + csvEscape(d.reason) + ',,');
		}
	}
	if (r.dnc_details) {
		for (var i=0; i<r.dnc_details.length; i++) {
			var d = r.dnc_details[i];
			lines.push('DNC,' + csvEscape(d.phone) + ',' + d.record + ',' + csvEscape(d.reason) + ',,');
		}
	}
	if (r.listid_details) {
		for (var i=0; i<r.listid_details.length; i++) {
			var d = r.listid_details[i];
			lines.push('Invalid List ID,' + csvEscape(d.phone) + ',' + d.record + ',' + csvEscape('Invalid list_id: ' + d.list_id) + ',' + csvEscape(d.list_id) + ',');
		}
	}

	triggerDownload(fname, lines.join('\r\n'));
}
</script>

<?php
echo "</TD></TR></TABLE>\n";
echo "</body></html>\n";


##### BEGIN - CHECK LEADS FOR VALID PHONE NUMBER, DUPLICATES AND DNC MATCHES #####
function check_lead($DB,$link,$list_id,$phone_number,$alt_phone,$address3,$title)
	{
	global $US, $statuses_clause, $status_mismatch_action, $mismatch_limit, $mismatch_clause, $multidaySQL, $SSweb_loader_phone_strip, $web_loader_phone_length, $usacan_check, $dupcheck, $international_dnc_scrub, $valid_number, $dnc_matches, $dup_lead, $moved_lead, $invalid_reason, $total, $good, $bad, $dup, $post, $moved, $phone_list, $dup_lead_list;

	if ( (strlen($SSweb_loader_phone_strip)>0) and ($SSweb_loader_phone_strip != 'DISABLED') )
		{
		$phone_number = preg_replace("/^$SSweb_loader_phone_strip/",'',$phone_number);
		}

	##### Check for duplicate phone numbers in vicidial_list table for all lists in a campaign #####
	if (preg_match("/DUPCAMP/i",$dupcheck))
		{
		$dup_lists='';
		$stmt="SELECT campaign_id from vicidial_lists where list_id='$list_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$ci_recs = mysqli_num_rows($rslt);
		if ($ci_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$dup_camp = $row[0];

			$stmt="SELECT list_id from vicidial_lists where campaign_id='$dup_camp';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$li_recs = mysqli_num_rows($rslt);
			if ($li_recs > 0)
				{
				$L=0;
				while ($li_recs > $L)
					{
					$row=mysqli_fetch_row($rslt);
					$dup_lists .= "'$row[0]',";
					$L++;
					}
				$dup_lists = preg_replace('/,$/i', '',$dup_lists);

				if ($status_mismatch_action) 
					{
					if (preg_match('/USING CHECK/', $status_mismatch_action))
						{
						$stmt="SELECT list_id, lead_id from vicidial_list where phone_number='$phone_number' and list_id IN($dup_lists) $multidaySQL $mismatch_clause order by entry_date desc $mismatch_limit";
						}
					else
						{
						$stmt="SELECT list_id, lead_id from vicidial_list where phone_number='$phone_number' $mismatch_clause order by entry_date desc $mismatch_limit";
						}
					if ($DB>0) {print $stmt."<BR>";}
					$rslt=mysql_to_mysqli($stmt, $link);
					while ($row=mysqli_fetch_row($rslt))
						{
						$upd_stmt="update vicidial_list set list_id='$list_id' where lead_id='$row[1]'";
						if ($DB>0) {print $upd_stmt."<BR>";}
						$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
						$moved+=mysqli_affected_rows($link);
						$moved_lead+=mysqli_affected_rows($link);
						$dup_lead=1;
						$dup_lead_list=$row[0];
						}
					}


				if ($dup_lead < 1)
					{
					$stmt="SELECT list_id from vicidial_list where phone_number='$phone_number' and list_id IN($dup_lists) $multidaySQL $statuses_clause limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					$pc_recs = mysqli_num_rows($rslt);
					if ($pc_recs > 0)
						{
						$dup_lead=1;
						$row=mysqli_fetch_row($rslt);
						$dup_lead_list=$row[0];
						}
					}
				if ($dup_lead < 1)
					{
					if (preg_match("/$phone_number$US$list_id/i", $phone_list))
						{$dup_lead++; $dup++;}
					}
				}
			}
		}

	##### Check for duplicate phone numbers in entire system #####
	if (preg_match("/DUPSYS/i",$dupcheck))
		{
		$dup_lead=0; $moved_lead=0;
		if ($status_mismatch_action)
			{
			if (preg_match('/USING CHECK/', $status_mismatch_action))
				{
				$stmt="SELECT list_id, lead_id from vicidial_list where phone_number='$phone_number' $multidaySQL $mismatch_clause order by entry_date desc $mismatch_limit";
				}
			else
				{
				$stmt="SELECT list_id, lead_id from vicidial_list where phone_number='$phone_number' $mismatch_clause order by entry_date desc $mismatch_limit";
				}

			if ($DB>0) {print $stmt."<BR>";}
			$rslt=mysql_to_mysqli($stmt, $link);
			while ($row=mysqli_fetch_row($rslt))
				{
				$upd_stmt="update vicidial_list set list_id='$list_id' where lead_id='$row[1]'";
				if ($DB>0) {print $upd_stmt."<BR>";}
				$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
				$moved+=mysqli_affected_rows($link);
				$moved_lead+=mysqli_affected_rows($link);
				$dup_lead=1;
				$dup_lead_list=$row[0];
				}
			}

		
		if ($dup_lead < 1)
			{
			$stmt="SELECT list_id from vicidial_list where phone_number='$phone_number' $multidaySQL $statuses_clause;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$dup_lead=1;
				$row=mysqli_fetch_row($rslt);
				$dup_lead_list=$row[0];
				}
			}

		if ($dup_lead < 1)
			{
			if (preg_match("/$phone_number$US$list_id/i", $phone_list))
				{$dup_lead++; $dup++;}
			}
		if ($dup_lead > 0)
			{
			$invalid_reason .= " "._QXZ("DUP");
			}
		}

	##### Check for duplicate phone numbers in one list_id #####
	if (preg_match("/DUPLIST/i",$dupcheck))
		{
		$dup_lead=0; $moved_lead=0;

		if ($status_mismatch_action)
			{
			if (preg_match('/USING CHECK/', $status_mismatch_action))
				{
				$stmt="SELECT list_id, lead_id from vicidial_list where phone_number='$phone_number' and list_id='$list_id' $multidaySQL $mismatch_clause order by entry_date desc $mismatch_limit";
				}
			else
				{
				$stmt="SELECT list_id, lead_id from vicidial_list where phone_number='$phone_number' $mismatch_clause order by entry_date desc $mismatch_limit";
				}
			if ($DB>0) {print $stmt."<BR>";}
			$rslt=mysql_to_mysqli($stmt, $link);
			while ($row=mysqli_fetch_row($rslt))
				{
				$upd_stmt="update vicidial_list set list_id='$list_id' where lead_id='$row[1]'";
				if ($DB>0) {print $upd_stmt."<BR>";}
				$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
				$moved+=mysqli_affected_rows($link);
				$moved_lead+=mysqli_affected_rows($link);
				$dup_lead=1;
				$dup_lead_list=$row[0];
				}
			}

		if ($dup_lead < 1)
			{
			$stmt="SELECT count(*) from vicidial_list where phone_number='$phone_number' and list_id='$list_id' $multidaySQL $statuses_clause;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$dup_lead=$row[0];
				$dup_lead_list=$list_id;
				}
			}

		if ($dup_lead < 1)
			{
			if (preg_match("/$phone_number$US$list_id/i", $phone_list))
				{$dup_lead++; $dup++;}
			}
		if ($dup_lead > 0)
			{
			$invalid_reason .= " "._QXZ("DUP");
			}
		}

	##### Check for duplicate title and alt-phone in list_id #####
	if (preg_match("/DUPTITLEALTPHONELIST/i",$dupcheck))
		{
		$dup_lead=0; $moved_lead=0;

		if ($status_mismatch_action)
			{
			if (preg_match('/USING CHECK/', $status_mismatch_action))
				{
				$stmt="SELECT list_id, lead_id from vicidial_list where title='$title' and alt_phone='$alt_phone' and list_id='$list_id' $multidaySQL $mismatch_clause order by entry_date desc $mismatch_limit";
				}
			else
				{
				$stmt="SELECT list_id, lead_id from vicidial_list where title='$title' and alt_phone='$alt_phone' $mismatch_clause order by entry_date desc $mismatch_limit";
				}
			if ($DB>0) {print $stmt."<BR>";}
			$rslt=mysql_to_mysqli($stmt, $link);
			while ($row=mysqli_fetch_row($rslt))
				{
				$upd_stmt="update vicidial_list set list_id='$list_id' where lead_id='$row[1]'";
				if ($DB>0) {print $upd_stmt."<BR>";}
				$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
				$moved+=mysqli_affected_rows($link);
				$moved_lead+=mysqli_affected_rows($link);
				$dup_lead=1;
				$dup_lead_list=$row[0];
				}
			}

		if ($dup_lead < 1)
			{
			$stmt="SELECT count(*) from vicidial_list where title='$title' and alt_phone='$alt_phone' and list_id='$list_id' $multidaySQL $statuses_clause;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$dup_lead=$row[0];
				$dup_lead_list=$list_id;
				}
			}

		if ($dup_lead < 1)
			{
			if (preg_match("/$alt_phone$title$US$list_id/i",$phone_list))
				{$dup_lead++; $dup++;}
			}
		if ($dup_lead > 0)
			{
			$invalid_reason .= " "._QXZ("DUP");
			}
		}

	##### Check for duplicate title/alt-phone in entire system #####
	if (preg_match("/DUPTITLEALTPHONESYS/i",$dupcheck))
		{
		$dup_lead=0; $moved_lead=0;

		if ($status_mismatch_action)
			{
			$stmt="SELECT list_id, lead_id from vicidial_list where title='$title' and alt_phone='$alt_phone' $multidaySQL $mismatch_clause order by entry_date desc $mismatch_limit";
			$rslt=mysql_to_mysqli($stmt, $link);
			while ($row=mysqli_fetch_row($rslt))
				{
				$upd_stmt="update vicidial_list set list_id='$list_id' where lead_id='$row[1]'";
				if ($DB>0) {print $upd_stmt."<BR>";}
				$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
				$moved+=mysqli_affected_rows($link);
				$moved_lead+=mysqli_affected_rows($link);
				$dup_lead=1;
				$dup_lead_list=$row[0];
				}
			}

		if ($dup_lead < 1)
			{
			$stmt="SELECT list_id from vicidial_list where title='$title' and alt_phone='$alt_phone' $multidaySQL $statuses_clause;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$dup_lead=1;
				$row=mysqli_fetch_row($rslt);
				$dup_lead_list=$row[0];
				}
			}

		if ($dup_lead < 1)
			{
			if (preg_match("/$alt_phone$title$US$list_id/i",$phone_list))
				{$dup_lead++; $dup++;}
			}
		if ($dup_lead > 0)
			{
			$invalid_reason .= " "._QXZ("DUP");
			}
		}

	if ( (strlen($phone_number)<5) || (strlen($phone_number)>18) )
		{
		$valid_number=0;
		$invalid_reason .= _QXZ("INVALID PHONE NUMBER LENGTH");
		}
	if ( (strlen($web_loader_phone_length)>0) and (strlen($web_loader_phone_length)< 3) and ( (strlen($phone_number) > $web_loader_phone_length) or (strlen($phone_number) < $web_loader_phone_length) ) )
		{
		$valid_number=0;
		$invalid_reason .= " "._QXZ("INVALID REQUIRED PHONE NUMBER LENGTH");
		}
	if ( (preg_match("/PREFIX/",$usacan_check)) and ($valid_number > 0) )
		{
		$USprefix = substr($phone_number, 3, 1);
		if ($USprefix < 2)
			{
			$valid_number=0;
			$invalid_reason .= " "._QXZ("INVALID PHONE NUMBER PREFIX");
			}
		}
	if ( (preg_match("/AREACODE/",$usacan_check)) and ($valid_number > 0) )
		{
		$phone_areacode = substr($phone_number, 0, 3);
		$stmt = "SELECT count(*) from vicidial_phone_codes where areacode='$phone_areacode' and country_code='1';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$valid_number=$row[0];
		if ($valid_number < 1)
			{
			$invalid_reason .= " "._QXZ("INVALID PHONE NUMBER AREACODE");
			}
		}
	if ( (preg_match("/NANPA/",$usacan_check)) and ($valid_number > 0) )
		{
		$phone_areacode = substr($phone_number, 0, 3);
		$phone_prefix = substr($phone_number, 3, 3);
		$stmt = "SELECT count(*) from vicidial_nanpa_prefix_codes where areacode='$phone_areacode' and prefix='$phone_prefix';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$valid_number=$row[0];
		if ($valid_number < 1)
			{
			$invalid_reason .= " "._QXZ("INVALID PHONE NUMBER NANPA AREACODE PREFIX");
			}
		}
	if ($international_dnc_scrub and $valid_number > 0)
		{
		$dnc_table_name="vicidial_dnc_".$international_dnc_scrub;
		$dnc_stmt="select count(*) from $dnc_table_name where phone_number='$phone_number'";
		$dnc_rslt=mysql_to_mysqli($dnc_stmt, $link);
		$dnc_row=mysqli_fetch_row($dnc_rslt);
		$dnc_matches=$dnc_row[0];
		if ($dnc_matches >0)
			{
			$invalid_reason .= " "._QXZ("NUMBER FOUND IN $international_dnc_scrub DNC LIST");
			}
		}

	return 1;
	}
##### END - CHECK LEADS #####

?>
