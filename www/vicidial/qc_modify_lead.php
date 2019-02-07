<?php
# qc_modify_lead.php modified from: (by poundteam)
//admin_modify_lead.php   version 2.14
#
# ViciDial database administration modify lead in vicidial_list
# qc_modify_lead.php
# 
# Copyright (C) 2012  poundteam.com    LICENSE: AGPLv2
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to allow QC review and modification of leads, contributed by poundteam.com
#
# changes:
# 121116-1324 - First build, added to vicidial codebase
# 121130-1034 - Changed scheduled callback user ID field to be 20 characters, issue #467
# 130621-2328 - Finalized changing of all ereg instances to preg
#             - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0904 - Changed to mysqli PHP functions, fixes for issue #699
# 140706-0837 - Incorporated into standard admin code
# 141007-2152 - Finalized adding QXZ translation to all admin files
# 141128-0902 - Code cleanup for QXZ functions
# 141229-1742 - Added code for on-the-fly language translations display
# 150808-1437 - Added compatibility for custom fields data options
# 150908-1531 - Fixed input lengths for several standard fields to match DB
# 150917-1311 - Added dynamic default field maxlengths based on DB schema
# 160611-1217 - Fixed for external server IP recording link issue
# 170409-1542 - Added IP List validation code
# 170513-2256 - Added QC Webform, issue #1010
# 170527-2252 - Fix for rare inbound logging issue #1017
# 171001-1110 - Added in-browser audio control, if recording access control is disabled
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
if (isset($_GET["vendor_id"]))				{$vendor_id=$_GET["vendor_id"];}
	elseif (isset($_POST["vendor_id"]))		{$vendor_id=$_POST["vendor_id"];}
if (isset($_GET["phone"]))				{$phone=$_GET["phone"];}
	elseif (isset($_POST["phone"]))		{$phone=$_POST["phone"];}
if (isset($_GET["old_phone"]))				{$old_phone=$_GET["old_phone"];}
	elseif (isset($_POST["old_phone"]))		{$old_phone=$_POST["old_phone"];}
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["title"]))				{$title=$_GET["title"];}
	elseif (isset($_POST["title"]))		{$title=$_POST["title"];}
if (isset($_GET["first_name"]))				{$first_name=$_GET["first_name"];}
	elseif (isset($_POST["first_name"]))		{$first_name=$_POST["first_name"];}
if (isset($_GET["middle_initial"]))				{$middle_initial=$_GET["middle_initial"];}
	elseif (isset($_POST["middle_initial"]))	{$middle_initial=$_POST["middle_initial"];}
if (isset($_GET["last_name"]))				{$last_name=$_GET["last_name"];}
	elseif (isset($_POST["last_name"]))		{$last_name=$_POST["last_name"];}
if (isset($_GET["lead_name"]))				{$lead_name=$_GET["lead_name"];}
	elseif (isset($_POST["lead_name"]))		{$lead_name=$_POST["lead_name"];}
if (isset($_GET["phone_number"]))				{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))		{$phone_number=$_POST["phone_number"];}
if (isset($_GET["end_call"]))				{$end_call=$_GET["end_call"];}
	elseif (isset($_POST["end_call"]))		{$end_call=$_POST["end_call"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["dispo"]))				{$dispo=$_GET["dispo"];}
	elseif (isset($_POST["dispo"]))		{$dispo=$_POST["dispo"];}
if (isset($_GET["list_id"]))				{$list_id=$_GET["list_id"];}
	elseif (isset($_POST["list_id"]))		{$list_id=$_POST["list_id"];}
if (isset($_GET["campaign_id"]))				{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))		{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["phone_code"]))				{$phone_code=$_GET["phone_code"];}
	elseif (isset($_POST["phone_code"]))		{$phone_code=$_POST["phone_code"];}
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["extension"]))				{$extension=$_GET["extension"];}
	elseif (isset($_POST["extension"]))		{$extension=$_POST["extension"];}
if (isset($_GET["channel"]))				{$channel=$_GET["channel"];}
	elseif (isset($_POST["channel"]))		{$channel=$_POST["channel"];}
if (isset($_GET["call_began"]))				{$call_began=$_GET["call_began"];}
	elseif (isset($_POST["call_began"]))		{$call_began=$_POST["call_began"];}
if (isset($_GET["parked_time"]))				{$parked_time=$_GET["parked_time"];}
	elseif (isset($_POST["parked_time"]))		{$parked_time=$_POST["parked_time"];}
if (isset($_GET["tsr"]))				{$tsr=$_GET["tsr"];}
	elseif (isset($_POST["tsr"]))		{$tsr=$_POST["tsr"];}
if (isset($_GET["address1"]))				{$address1=$_GET["address1"];}
	elseif (isset($_POST["address1"]))		{$address1=$_POST["address1"];}
if (isset($_GET["address2"]))				{$address2=$_GET["address2"];}
	elseif (isset($_POST["address2"]))		{$address2=$_POST["address2"];}
if (isset($_GET["address3"]))				{$address3=$_GET["address3"];}
	elseif (isset($_POST["address3"]))		{$address3=$_POST["address3"];}
if (isset($_GET["city"]))				{$city=$_GET["city"];}
	elseif (isset($_POST["city"]))		{$city=$_POST["city"];}
if (isset($_GET["state"]))				{$state=$_GET["state"];}
	elseif (isset($_POST["state"]))		{$state=$_POST["state"];}
if (isset($_GET["postal_code"]))				{$postal_code=$_GET["postal_code"];}
	elseif (isset($_POST["postal_code"]))		{$postal_code=$_POST["postal_code"];}
if (isset($_GET["province"]))				{$province=$_GET["province"];}
	elseif (isset($_POST["province"]))		{$province=$_POST["province"];}
if (isset($_GET["country_code"]))				{$country_code=$_GET["country_code"];}
	elseif (isset($_POST["country_code"]))		{$country_code=$_POST["country_code"];}
if (isset($_GET["alt_phone"]))				{$alt_phone=$_GET["alt_phone"];}
	elseif (isset($_POST["alt_phone"]))		{$alt_phone=$_POST["alt_phone"];}
if (isset($_GET["email"]))				{$email=$_GET["email"];}
	elseif (isset($_POST["email"]))		{$email=$_POST["email"];}
if (isset($_GET["security"]))				{$security=$_GET["security"];}
	elseif (isset($_POST["security"]))		{$security=$_POST["security"];}
if (isset($_GET["comments"]))				{$comments=$_GET["comments"];}
	elseif (isset($_POST["comments"]))		{$comments=$_POST["comments"];}
if (isset($_GET["status"]))				{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))		{$status=$_POST["status"];}
if (isset($_GET["rank"]))				{$rank=$_GET["rank"];}
	elseif (isset($_POST["rank"]))		{$rank=$_POST["rank"];}
if (isset($_GET["owner"]))				{$owner=$_GET["owner"];}
	elseif (isset($_POST["owner"]))		{$owner=$_POST["owner"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["CBchangeUSERtoANY"]))				{$CBchangeUSERtoANY=$_GET["CBchangeUSERtoANY"];}
	elseif (isset($_POST["CBchangeUSERtoANY"]))		{$CBchangeUSERtoANY=$_POST["CBchangeUSERtoANY"];}
if (isset($_GET["CBchangeUSERtoUSER"]))				{$CBchangeUSERtoUSER=$_GET["CBchangeUSERtoUSER"];}
	elseif (isset($_POST["CBchangeUSERtoUSER"]))		{$CBchangeUSERtoUSER=$_POST["CBchangeUSERtoUSER"];}
if (isset($_GET["CBchangeANYtoUSER"]))				{$CBchangeANYtoUSER=$_GET["CBchangeANYtoUSER"];}
	elseif (isset($_POST["CBchangeANYtoUSER"]))		{$CBchangeANYtoUSER=$_POST["CBchangeANYtoUSER"];}
if (isset($_GET["CBchangeDATE"]))				{$CBchangeDATE=$_GET["CBchangeDATE"];}
	elseif (isset($_POST["CBchangeDATE"]))		{$CBchangeDATE=$_POST["CBchangeDATE"];}
if (isset($_GET["callback_id"]))				{$callback_id=$_GET["callback_id"];}
	elseif (isset($_POST["callback_id"]))		{$callback_id=$_POST["callback_id"];}
if (isset($_GET["CBuser"]))				{$CBuser=$_GET["CBuser"];}
	elseif (isset($_POST["CBuser"]))		{$CBuser=$_POST["CBuser"];}
if (isset($_GET["modify_logs"]))			{$modify_logs=$_GET["modify_logs"];}
	elseif (isset($_POST["modify_logs"]))	{$modify_logs=$_POST["modify_logs"];}
if (isset($_GET["modify_closer_logs"]))			{$modify_closer_logs=$_GET["modify_closer_logs"];}
	elseif (isset($_POST["modify_closer_logs"]))	{$modify_closer_logs=$_POST["modify_closer_logs"];}
if (isset($_GET["modify_agent_logs"]))			{$modify_agent_logs=$_GET["modify_agent_logs"];}
	elseif (isset($_POST["modify_agent_logs"]))	{$modify_agent_logs=$_POST["modify_agent_logs"];}
if (isset($_GET["add_closer_record"]))			{$add_closer_record=$_GET["add_closer_record"];}
	elseif (isset($_POST["add_closer_record"]))	{$add_closer_record=$_POST["add_closer_record"];}
if (isset($_POST["appointment_date"]))			{$appointment_date=$_POST["appointment_date"];}
	elseif (isset($_GET["appointment_date"]))	{$appointment_date=$_GET["appointment_date"];}
if (isset($_POST["appointment_time"]))			{$appointment_time=$_POST["appointment_time"];}
	elseif (isset($_GET["appointment_time"]))	{$appointment_time=$_GET["appointment_time"];}

$STARTtime = date("U");
$defaultappointment = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,custom_fields_enabled,enable_languages,language_method,active_modules FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$custom_fields_enabled =	$row[1];
	$SSenable_languages =		$row[2];
	$SSlanguage_method =		$row[3];
	$active_modules =			$row[4];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);

	$old_phone = preg_replace('/[^0-9]/','',$old_phone);
	$phone_number = preg_replace('/[^0-9]/','',$phone_number);
	$alt_phone = preg_replace('/[^0-9]/','',$alt_phone);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	}

$rights_stmt = "SELECT modify_leads,qc_enabled,full_name,modify_leads,user_group,selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$modify_leads =			$rights_row[0];
$qc_enabled =			$rights_row[1];
$LOGfullname =			$rights_row[2];
$LOGmodify_leads =		$rights_row[3];
$LOGuser_group =		$rights_row[4];
$VUselected_language =	$rights_row[5];

if (strlen($phone_number)<6) {$phone_number=$old_phone;}

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

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

# check their permissions
#if ( $modify_leads < 1 )
#	{
#	header ("Content-type: text/html; charset=utf-8");
#	echo "You do not have permissions to modify leads\n";
#	exit;
#	}
if ( $qc_enabled < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("QC is not enabled for your user account")."\n";
	exit;
	}

$label_title =				_QXZ("Title");
$label_first_name =			_QXZ("First");
$label_middle_initial =		_QXZ("MI");
$label_last_name =			_QXZ("Last");
$label_address1 =			_QXZ("Address1");
$label_address2 =			_QXZ("Address2");
$label_address3 =			_QXZ("Address3");
$label_city =				_QXZ("City");
$label_state =				_QXZ("State");
$label_province =			_QXZ("Province");
$label_postal_code =		_QXZ("Postal Code");
$label_vendor_lead_code =	_QXZ("Vendor ID");
$label_gender =				_QXZ("Gender");
$label_phone_number =		_QXZ("Phone");
$label_phone_code =			_QXZ("DialCode");
$label_alt_phone =			_QXZ("Alt. Phone");
$label_security_phrase =	_QXZ("Show");
$label_email =				_QXZ("Email");
$label_comments =			_QXZ("Comments");

### find any custom field labels
$stmt="SELECT label_title,label_first_name,label_middle_initial,label_last_name,label_address1,label_address2,label_address3,label_city,label_state,label_province,label_postal_code,label_vendor_lead_code,label_gender,label_phone_number,label_phone_code,label_alt_phone,label_security_phrase,label_email,label_comments from system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
if (strlen($row[0])>0)	{$label_title =				$row[0];}
if (strlen($row[1])>0)	{$label_first_name =		$row[1];}
if (strlen($row[2])>0)	{$label_middle_initial =	$row[2];}
if (strlen($row[3])>0)	{$label_last_name =			$row[3];}
if (strlen($row[4])>0)	{$label_address1 =			$row[4];}
if (strlen($row[5])>0)	{$label_address2 =			$row[5];}
if (strlen($row[6])>0)	{$label_address3 =			$row[6];}
if (strlen($row[7])>0)	{$label_city =				$row[7];}
if (strlen($row[8])>0)	{$label_state =				$row[8];}
if (strlen($row[9])>0)	{$label_province =			$row[9];}
if (strlen($row[10])>0) {$label_postal_code =		$row[10];}
if (strlen($row[11])>0) {$label_vendor_lead_code =	$row[11];}
if (strlen($row[12])>0) {$label_gender =			$row[12];}
if (strlen($row[13])>0) {$label_phone_number =		$row[13];}
if (strlen($row[14])>0) {$label_phone_code =		$row[14];}
if (strlen($row[15])>0) {$label_alt_phone =			$row[15];}
if (strlen($row[16])>0) {$label_security_phrase =	$row[16];}
if (strlen($row[17])>0) {$label_email =				$row[17];}
if (strlen($row[18])>0) {$label_comments =			$row[18];}

##### BEGIN vicidial_list FIELD LENGTH LOOKUP #####
$MAXvendor_lead_code =		'20';
$MAXphone_code =			'10';
$MAXphone_number =			'18';
$MAXtitle =					'4';
$MAXfirst_name =			'30';
$MAXmiddle_initial =		'1';
$MAXlast_name =				'30';
$MAXaddress1 =				'100';
$MAXaddress2 =				'100';
$MAXaddress3 =				'100';
$MAXcity =					'50';
$MAXstate =					'2';
$MAXprovince =				'50';
$MAXpostal_code =			'10';
$MAXalt_phone =				'12';
$MAXemail =					'70';
$MAXsecurity_phrase =		'100';
$MAXcountry_code =			'3';
$MAXowner =					'20';

$stmt = "SHOW COLUMNS FROM vicidial_list;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$scvl_ct = mysqli_num_rows($rslt);
$s=0;
while ($scvl_ct > $s)
	{
	$row=mysqli_fetch_row($rslt);
	$vl_field =	$row[0];
	$vl_type = preg_replace("/[^0-9]/",'',$row[1]);
	if (strlen($vl_type) > 0)
		{
		if ( ($vl_field == 'vendor_lead_code') and ($MAXvendor_lead_code != $vl_type) )
			{$MAXvendor_lead_code = $vl_type;}
		if ( ($vl_field == 'phone_code') and ($MAXphone_code != $vl_type) )
			{$MAXphone_code = $vl_type;}
		if ( ($vl_field == 'phone_number') and ($MAXphone_number != $vl_type) )
			{$MAXphone_number = $vl_type;}
		if ( ($vl_field == 'title') and ($MAXtitle != $vl_type) )
			{$MAXtitle = $vl_type;}
		if ( ($vl_field == 'first_name') and ($MAXfirst_name != $vl_type) )
			{$MAXfirst_name = $vl_type;}
		if ( ($vl_field == 'middle_initial') and ($MAXmiddle_initial != $vl_type) )
			{$MAXmiddle_initial = $vl_type;}
		if ( ($vl_field == 'last_name') and ($MAXlast_name != $vl_type) )
			{$MAXlast_name = $vl_type;}
		if ( ($vl_field == 'address1') and ($MAXaddress1 != $vl_type) )
			{$MAXaddress1 = $vl_type;}
		if ( ($vl_field == 'address2') and ($MAXaddress2 != $vl_type) )
			{$MAXaddress2 = $vl_type;}
		if ( ($vl_field == 'address3') and ($MAXaddress3 != $vl_type) )
			{$MAXaddress3 = $vl_type;}
		if ( ($vl_field == 'city') and ($MAXcity != $vl_type) )
			{$MAXcity = $vl_type;}
		if ( ($vl_field == 'state') and ($MAXstate != $vl_type) )
			{$MAXstate = $vl_type;}
		if ( ($vl_field == 'province') and ($MAXprovince != $vl_type) )
			{$MAXprovince = $vl_type;}
		if ( ($vl_field == 'postal_code') and ($MAXpostal_code != $vl_type) )
			{$MAXpostal_code = $vl_type;}
		if ( ($vl_field == 'alt_phone') and ($MAXalt_phone != $vl_type) )
			{$MAXalt_phone = $vl_type;}
		if ( ($vl_field == 'email') and ($MAXemail != $vl_type) )
			{$MAXemail = $vl_type;}
		if ( ($vl_field == 'security_phrase') and ($MAXsecurity_phrase != $vl_type) )
			{$MAXsecurity_phrase = $vl_type;}
		if ( ($vl_field == 'country_code') and ($MAXcountry_code != $vl_type) )
			{$MAXcountry_code = $vl_type;}
		if ( ($vl_field == 'owner') and ($MAXowner != $vl_type) )
			{$MAXowner = $vl_type;}
		}
	$s++;
	}
##### END vicidial_list FIELD LENGTH LOOKUP #####

//Added by Poundteam for QC. Gather record data to display on page and prepopulate title and hrefs, etc.
$stmt="SELECT * from vicidial_list A inner join vicidial_lists B on A.list_id=B.list_id inner join vicidial_campaigns C on B.campaign_id=C.campaign_id left outer join vicidial_statuses D on A.status=D.status left outer join vicidial_qc_codes E on A.status=E.code where A.lead_id='$lead_id'";
$rslt=mysql_to_mysqli($stmt, $link);
if (mysqli_num_rows ($rslt) < '1' )
	{
    if($DB) { echo "$stmt\n"; }
    exit();
    }
$row=mysqli_fetch_assoc($rslt);
$original_record =		$row;
$campaign_id =			$row['campaign_id'];
$campaign_name =		$row['campaign_name'];
$phone_number =			$row['phone_number'];
$phone_code =			$row['phone_code'];
$lead_name =			trim(trim($row['first_name'].' '.$row['middle_initial']).' '.$row['last_name']);
$scheduled_callback =	$row['scheduled_callback'];
$qc_webform =			$row['qc_web_form_address'];

### BEGIN Replace variables with values for qc_webform ###
$qc_webform = str_replace('--A--lead_id--B--', $row['lead_id'], $qc_webform);
$qc_webform = str_replace('--A--vendor_lead_code--B--', $row['vendor_lead_code'], $qc_webform);
$qc_webform = str_replace('--A--list_id--B--', $row['list_id'], $qc_webform);
$qc_webform = str_replace('--A--gmt_offset_now--B--', $row['gmt_offset_now'], $qc_webform);
$qc_webform = str_replace('--A--phone_code--B--', $row['phone_code'], $qc_webform);
$qc_webform = str_replace('--A--phone_number--B--', $row['phone_number'], $qc_webform);
$qc_webform = str_replace('--A--title--B--', $row['title'], $qc_webform);
$qc_webform = str_replace('--A--first_name--B--', $row['first_name'], $qc_webform);
$qc_webform = str_replace('--A--middle_initial--B--', $row['middle_initial'], $qc_webform);
$qc_webform = str_replace('--A--last_name--B--', $row['last_name'], $qc_webform);
$qc_webform = str_replace('--A--address1--B--', $row['address1'], $qc_webform);
$qc_webform = str_replace('--A--address2--B--', $row['address2'], $qc_webform);
$qc_webform = str_replace('--A--address3--B--', $row['address3'], $qc_webform);
$qc_webform = str_replace('--A--city--B--', $row['city'], $qc_webform);
$qc_webform = str_replace('--A--state--B--', $row['state'], $qc_webform);
$qc_webform = str_replace('--A--province--B--', $row['province'], $qc_webform);
$qc_webform = str_replace('--A--postal_code--B--', $row['postal_code'], $qc_webform);
$qc_webform = str_replace('--A--country_code--B--', $row['country_code'], $qc_webform);
$qc_webform = str_replace('--A--gender--B--', $row['gender'], $qc_webform);
$qc_webform = str_replace('--A--date_of_birth--B--', $row['date_of_birth'], $qc_webform);
$qc_webform = str_replace('--A--alt_phone--B--', $row['alt_phone'], $qc_webform);
$qc_webform = str_replace('--A--email--B--', $row['email'], $qc_webform);
$qc_webform = str_replace('--A--security_phrase--B--', $row['security_phrase'], $qc_webform);
$qc_webform = str_replace('--A--comments--B--', $row['comments'], $qc_webform);
$qc_webform = str_replace('--A--user--B--', $row['user'], $qc_webform);
$qc_webform = str_replace('--A--campaign--B--', $row['campaign'], $qc_webform);
$qc_webform = str_replace('--A--phone_login--B--', $row['phone_login'], $qc_webform);
$qc_webform = str_replace('--A--rank--B--', $row['rank'], $qc_webform);
$qc_webform = str_replace('--A--owner--B--', $row['owner'], $qc_webform);
$qc_webform = str_replace('--A--security_phrase--B--', $row['security_phrase'], $qc_webform);
$qc_webform = str_replace('--A--current_user--B--', $PHP_AUTH_USER, $qc_webform);
$qc_webform = str_replace('--A--called_count--B--', $row['called_count'], $qc_webform);
### END of replace variables code ###

$vdc_form_display = 'vdc_form_display.php';
if (preg_match("/cf_encrypt/",$active_modules))
	{$vdc_form_display = 'vdc_form_display_encrypt.php';}

?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("QC Modify Lead"); ?>: <?php echo "$lead_id - ".urldecode($lead_name).urldecode(" ("._QXZ("Campaign").": $campaign_id - $campaign_name)"); ?></title>
<script language="JavaScript" src="calendar_db.js"></script>
<link rel="stylesheet" href="calendar.css">
</head>
<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>

<?php
if($DB) 
	{
    echo __LINE__."\n";
	}

echo "<CENTER><FONT FACE='Courier' COLOR=BLACK SIZE=3><a href=\"admin.php?ADD=881&campaign_id=$campaign_id\">"._QXZ("QC CAMPAIGN")." $campaign_id</a>: "._QXZ("Lead")." $lead_id - ".urldecode($lead_name)."<BR>\n";
//end_call is set by submit button to denote "save", without it this is a VIEW, with it this is a SAVE
if ($end_call > 0) 
	{
    if($DB) { echo __LINE__."\n"; }
	### update the lead record in the vicidial_list table
	$stmt="UPDATE vicidial_list set status='" . mysqli_real_escape_string($link, $status) . "',title='" . mysqli_real_escape_string($link, $title) . "',first_name='" . mysqli_real_escape_string($link, $first_name) . "',middle_initial='" . mysqli_real_escape_string($link, $middle_initial) . "',last_name='" . mysqli_real_escape_string($link, $last_name) . "',address1='" . mysqli_real_escape_string($link, $address1) . "',address2='" . mysqli_real_escape_string($link, $address2) . "',address3='" . mysqli_real_escape_string($link, $address3) . "',city='" . mysqli_real_escape_string($link, $city) . "',state='" . mysqli_real_escape_string($link, $state) . "',province='" . mysqli_real_escape_string($link, $province) . "',postal_code='" . mysqli_real_escape_string($link, $postal_code) . "',country_code='" . mysqli_real_escape_string($link, $country_code) . "',alt_phone='" . mysqli_real_escape_string($link, $alt_phone) . "',phone_number='$phone_number',phone_code='$phone_code',email='" . mysqli_real_escape_string($link, $email) . "',security_phrase='" . mysqli_real_escape_string($link, $security) . "',comments='" . mysqli_real_escape_string($link, $comments) . "',rank='" . mysqli_real_escape_string($link, $rank) . "',owner='" . mysqli_real_escape_string($link, $owner) . "' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "'";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	//STATUS just changed, re-capture all data for client!
	//Added by Poundteam for QC. Gather record data to display on page and prepopulate title and hrefs, etc.
	$stmt="SELECT * from vicidial_list A inner join vicidial_lists B on A.list_id=B.list_id inner join vicidial_campaigns C on B.campaign_id=C.campaign_id left outer join vicidial_statuses D on A.status=D.status left outer join vicidial_qc_codes E on A.status=E.code where A.lead_id='$lead_id'";
	if($DB) { echo "$stmt\n"; }
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_assoc($rslt);
	//QUALITY CONTROL CHANGE LOG BEGIN - CHANGE VERSION (VIEW VERSION IS BELOW, WHICH CREATES THE RECORD, THIS ONE MERELY MODIFIES THE EXISTING RECORD)
	$new_record=$row;
	//if status has changed, the join query above will have the "status" field of "vicidial_list" overwritten by the status field of "vicidial_statuses" ... which will be EMPTY if there is no matching status to the new QC status chosen. This will cause the changelog to be incorrect.
	if ( strlen($new_record['status']) == '0' ) 
		{
		$new_record['status']=$new_record['code'];
		}

	if($original_record != $new_record) 
		{
		//Information changed: Find out what and record it, first disable "view" logging
		$qcchange='Y';
		$qcchangelist='';
		$qcchangecounter=0;
		foreach($original_record as $key=>$value)
			{
			//only list the changes in the first 35 fiels, those are from the vicidial_list table (the rest are from joined tables, and the changes cascade)
			if(($new_record[$key]!=$value)&&($qcchangecounter<=35))  $qcchangelist.="----$key----\n$value => $new_record[$key]\n";
			$qcchangecounter++;
			}
		### insert a NEW record to the vicidial_closer_log table
		$qcchangelist=mysqli_real_escape_string($link, $qcchangelist);
		$view_epoch = preg_replace('/[^0-9]/','',$_POST['viewtime']);
		$elapsed_seconds=$STARTtime-$view_epoch;

		$stmt="UPDATE vicidial_qc_agent_log set save_datetime='$NOW_TIME',save_epoch='$STARTtime',elapsed_seconds='$elapsed_seconds',old_status='{$original_record['status']}',new_status='{$new_record['status']}',details='$qcchangelist'
			where view_epoch='$view_epoch' and lead_id='$lead_id'";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
        }
	//QUALITY CONTROL CHANGE LOG END
	$original_sales_rep =	$row['user'];
	$campaign_id =			$row['campaign_id'];
	$campaign_name =		$row['campaign_name'];
	$lead_name =			trim(trim($row['first_name'].' '.$row['middle_initial']).' '.$row['last_name']);
	$scheduled_callback =	$row['scheduled_callback'];

	echo "<br>"._QXZ("information modified")."<BR><BR>\n";
	echo "<i><small><a href=\"$PHP_SELF?lead_id=$lead_id&DB=$DB\">"._QXZ("Go back to re-modify this QC lead")."</a></small></i><BR><BR><BR>\n";
	echo "<CENTER><B><FONT FACE='Courier' COLOR=BLACK SIZE=3><a href=\"admin.php?ADD=881&campaign_id=$campaign_id\">"._QXZ("Proceed to QC CAMPAIGN")." $campaign_id "._QXZ("Queue")."</a></B><BR><BR><B><I>"._QXZ("Callback Information").":</I></B>\n";
	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LEADS', event_type='MODIFY', record_id='$lead_id', event_code='ADMIN MODIFY LEAD', event_sql=\"$SQL_log\", event_notes='';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	if($DB) 
		{
		echo __LINE__."\n";
		}

	if ( ($dispo != $status) and ($dispo == 'CBHOLD') )
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		### inactivate vicidial_callbacks record for this lead
		$stmt="UPDATE vicidial_callbacks set status='INACTIVE' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' and status='ACTIVE';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		echo "<BR>"._QXZ("vicidial_callback record inactivated").": $lead_id<BR>\n";
		}
        //Duped CBHOLD version for vicidial status type 'Scheduled Callback'
	if ( ($dispo != $status) and ($scheduled_callback == 'Y') )
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		### inactivate vicidial_callbacks record for this lead
		$stmt="UPDATE vicidial_callbacks set status='INACTIVE' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' and status='ACTIVE';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		echo "<BR>"._QXZ("vicidial_callback record inactivated").": $lead_id<BR>\n";
		}
	if ( ($dispo != $status) and ($dispo == 'CALLBK') )
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		### inactivate vicidial_callbacks record for this lead
		$stmt="UPDATE vicidial_callbacks set status='INACTIVE' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' and status IN('ACTIVE','LIVE');";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		echo "<BR>"._QXZ("vicidial_callback record inactivated").": $lead_id<BR>\n";
		}

	if ( ($dispo != $status) and ($status == 'CBHOLD') )
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		### find any vicidial_callback records for this lead
		$stmt="select callback_id from vicidial_callbacks where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' and status IN('ACTIVE','LIVE') order by callback_id desc LIMIT 1;";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$CBM_to_print = mysqli_num_rows($rslt);
		if ($CBM_to_print > 0)
			{
			$rowx=mysqli_fetch_row($rslt);
			$callback_id = $rowx[0];
			}
		else
			{
			$defaultappointment = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y")));

			$stmt="INSERT INTO vicidial_callbacks SET lead_id='" . mysqli_real_escape_string($link, $lead_id) . "',recipient='ANYONE',status='ACTIVE',user='$PHP_AUTH_USER',user_group='ADMIN',list_id='" . mysqli_real_escape_string($link, $list_id) . "',callback_time='$defaultappointment 12:00:00',entry_time='$NOW_TIME',comments='',campaign_id='" . mysqli_real_escape_string($link, $campaign_id) . "';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);

			echo "<BR>"._QXZ("Scheduled Callback added").": $lead_id - $phone_number<BR>\n";
			}
		}

        //Duped CBHOLD version for vicidial status type 'Scheduled Callback'
        //This entry creates the callback
	if ( ($dispo != $status) and ($scheduled_callback == 'Y') )
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		### find any vicidial_callback records for this lead
		$stmt="select callback_id from vicidial_callbacks where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' and status IN('ACTIVE','LIVE') order by callback_id desc LIMIT 1;";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$CBM_to_print = mysqli_num_rows($rslt);
		if ($CBM_to_print > 0)
			{
			$rowx=mysqli_fetch_row($rslt);
			$callback_id = $rowx[0];
			}
		else
			{
			$defaultappointment = date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),date("m"),date("d"),date("Y")));

			$stmt="INSERT INTO vicidial_callbacks SET lead_id='" . mysqli_real_escape_string($link, $lead_id) . "',recipient='USERONLY',status='ACTIVE',user='$original_sales_rep',user_group='ADMIN',list_id='" . mysqli_real_escape_string($link, $list_id) . "',callback_time='$defaultappointment 12:00:00',entry_time='$NOW_TIME',comments='',campaign_id='" . mysqli_real_escape_string($link, $campaign_id) . "';";
                        $debug1=$stmt;
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);

			echo "<BR>"._QXZ("Scheduled Callback added").": $lead_id - $phone_number<BR>\n";
			}
		}


	if ( ($dispo != $status) and ($status == 'DNC') )
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		### add lead to the internal DNC list
		$stmt="INSERT INTO vicidial_dnc (phone_number) values('" . mysqli_real_escape_string($link, $phone_number) . "');";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		echo "<BR>"._QXZ("Lead added to DNC List").": $lead_id - $phone_number<BR>\n";
		}
	### update last record in vicidial_log table
       if (($dispo != $status) and ($modify_logs > 0))
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		$stmt="UPDATE vicidial_log set status='" . mysqli_real_escape_string($link, $status) . "' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by call_date desc limit 1";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}

	### update last record in vicidial_closer_log table
       if (($dispo != $status) and ($modify_closer_logs > 0))
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		$stmt="UPDATE vicidial_closer_log set status='" . mysqli_real_escape_string($link, $status) . "' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by closecallid desc limit 1";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}

	### update last record in vicidial_agent_log table
       if (($dispo != $status) and ($modify_agent_logs > 0))
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		$stmt="UPDATE vicidial_agent_log set status='" . mysqli_real_escape_string($link, $status) . "' where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by agent_log_id desc limit 1";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}

	if ($add_closer_record > 0)
		{
		if($DB) echo __LINE__."\n";
		### insert a NEW record to the vicidial_closer_log table
		$stmt="INSERT INTO vicidial_closer_log (lead_id,list_id,campaign_id,call_date,start_epoch,end_epoch,length_in_sec,status,phone_code,phone_number,user,comments,processed) values('" . mysqli_real_escape_string($link, $lead_id) . "','" . mysqli_real_escape_string($link, $list_id) . "','" . mysqli_real_escape_string($link, $campaign_id) . "','" . mysqli_real_escape_string($link, $parked_time) . "','$NOW_TIME','$STARTtime','1','" . mysqli_real_escape_string($link, $status) . "','" . mysqli_real_escape_string($link, $phone_code) . "','" . mysqli_real_escape_string($link, $phone_number) . "','$PHP_AUTH_USER','" . mysqli_real_escape_string($link, $comments) . "','Y')";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}
	}
else 
	{
	echo "<iframe src=\"qc_call_client_iframe.php?phone_number=$phone_number&phone_code=$phone_code&lead_id=$lead_id&list_id=$CLlist_id&stage=DISPLAY&submit_button=YES&user=$PHP_AUTH_USER&pass=$PHP_AUTH_PW&bgcolor=E6E6E6\" style=\"background-color:#FFEEFF;\" scrolling=\"auto\" frameborder=\"1\" allowtransparency=\"true\" id=\"vcFormIFrame\" name=\"qcFormIFrame\" width=\"540\" height=\"40\" STYLE=\"z-index:18\"> </iframe>\n";
	//Not a "Submit" result, viewing the record (possibly with URL options such as those below which modify callback status but not record data)
	if ($CBchangeUSERtoANY == 'YES') 
		{
		if($DB) echo __LINE__."\n";
		### set vicidial_callbacks record to an ANYONE callback for this lead
		$stmt="UPDATE vicidial_callbacks set recipient='ANYONE' where callback_id='" . mysqli_real_escape_string($link, $callback_id) . "';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		echo "<BR>"._QXZ("vicidial_callback record changed to ANYONE")."<BR>\n";
        $qcchange='Y';
		}
	if ($CBchangeUSERtoUSER == 'YES') 
		{
        if($DB) echo __LINE__."\n";
		### set vicidial_callbacks record to a different USERONLY callback record for this lead
		$stmt="UPDATE vicidial_callbacks set user='" . mysqli_real_escape_string($link, $CBuser) . "' where callback_id='" . mysqli_real_escape_string($link, $callback_id) . "';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		echo "<BR>"._QXZ("vicidial_callback record user changed to")." $CBuser<BR>\n";
        $qcchange='Y';
		}
	if ($CBchangeANYtoUSER == 'YES') 
		{
		if($DB) echo __LINE__."\n";
		### set vicidial_callbacks record to an USERONLY callback for this lead
		$stmt="UPDATE vicidial_callbacks set user='" . mysqli_real_escape_string($link, $CBuser) . "',recipient='USERONLY' where callback_id='" . mysqli_real_escape_string($link, $callback_id) . "';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		echo "<BR>"._QXZ("vicidial_callback record changed to USERONLY, user").": $CBuser<BR>\n";
		$qcchange='Y';
		}
	if ($CBchangeDATE == 'YES') 
		{
		if($DB) echo __LINE__."\n";
		### change date/time of vicidial_callbacks record for this lead
		$stmt="UPDATE vicidial_callbacks set callback_time='" . mysqli_real_escape_string($link, $appointment_date) . " " . mysqli_real_escape_string($link, $appointment_time) . "',comments='" . mysqli_real_escape_string($link, $comments) . "' where callback_id='" . mysqli_real_escape_string($link, $callback_id) . "';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		echo "<BR>"._QXZ("vicidial_callback record changed to")." $appointment_date $appointment_time<BR>\n";
		$qcchange='Y';
		}
        //QUALITY CONTROL LOGGING BEGIN - VIEW ONLY
        //If no changes have been made, record "view" of this record.
	if ($qcchange != 'Y') 
		{
		if($DB) echo __LINE__."\n QCCHANGE != Y";
		### insert a NEW record to the vicidial_closer_log table
		$stmt="INSERT INTO vicidial_qc_agent_log (qc_user,qc_user_group,qc_user_ip,lead_user,web_server_ip,view_datetime,view_epoch,lead_id,list_id,campaign_id,processed)
		values('" . mysqli_real_escape_string($link, $PHP_AUTH_USER) . "','$LOGuser_group','{$_SERVER['REMOTE_ADDR']}','{$original_record['user']}','{$_SERVER['SERVER_ADDR']}','$NOW_TIME','$STARTtime','" . mysqli_real_escape_string($link, $lead_id) . "','" . mysqli_real_escape_string($link, $original_record['list_id']) . "','" . mysqli_real_escape_string($link, $campaign_id) . "','N')";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}
	//QUALITY CONTROL LOGGING END
	if($DB) echo __LINE__."\n";
	$stmt="SELECT count(*) from vicidial_list where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "'";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	$lead_count = $row[0];

	if ($lead_count > 0)
		{
		if($DB) echo __LINE__."\n";
		##### grab vicidial_list_alt_phones records #####
		$stmt="select phone_code,phone_number,alt_phone_note,alt_phone_count,active from vicidial_list_alt_phones where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by alt_phone_count limit 500;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$alts_to_print = mysqli_num_rows($rslt);

		$c=0;
		$alts_output = '';
		while ($alts_to_print > $c)
			{
			if($DB) 
				{
				echo __LINE__."\n";
				}
			$row=mysqli_fetch_row($rslt);
			if (preg_match("/1$|3$|5$|7$|9$/i", $c))
				{$bgcolor='bgcolor="#B9CBFD"';}
			else
				{$bgcolor='bgcolor="#9BB9FB"';}

			$c++;
			$alts_output .= "<tr $bgcolor>";
			$alts_output .= "<td><font size=1>$c</td>";
			$alts_output .= "<td><font size=2>$row[0] $row[1]</td>";
			$alts_output .= "<td align=left><font size=2> $row[2]</td>\n";
			$alts_output .= "<td align=left><font size=2> $row[3]</td>\n";
			$alts_output .= "<td align=left><font size=2> $row[4] </td></tr>\n";
			}
		}
	else
		{
		echo _QXZ("lead lookup FAILED for lead_id")." $lead_id &nbsp; &nbsp; &nbsp; $NOW_TIME\n<BR><BR>\n";
#		echo "<a href=\"$PHP_SELF\">Close this window</a>\n<BR><BR>\n";
		}

	##### grab vicidial_log records #####
	$stmt="select uniqueid,lead_id,list_id,campaign_id,call_date,start_epoch,end_epoch,length_in_sec,status,phone_code,phone_number,user,comments,processed,user_group,term_reason,alt_dial from vicidial_log where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by uniqueid desc limit 500;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$logs_to_print = mysqli_num_rows($rslt);
        if($DB) echo __LINE__."<br>\n";
	$u=0;
	$call_log = '';
	$log_campaign = '';
	while ($logs_to_print > $u)
		{
		if($DB) echo __LINE__."<br>\n";
		$row=mysqli_fetch_row($rslt);
		if (strlen($log_campaign)<1) {$log_campaign = $row[3];}
		if (preg_match("/1$|3$|5$|7$|9$/i", $u))
			{$bgcolor='bgcolor="#B9CBFD"';}
		else
			{$bgcolor='bgcolor="#9BB9FB"';}

		$u++;
		$call_log .= "<tr $bgcolor>";
		$call_log .= "<td><font size=1>$u</td>";
		$call_log .= "<td><font size=2>$row[4]</td>";
		$call_log .= "<td align=left><font size=2> $row[7]</td>\n";
		$call_log .= "<td align=left><font size=2> $row[8]</td>\n";
		$call_log .= "<td align=left><font size=2> <A HREF=\"user_stats.php?user=$row[11]\" target=\"_blank\">$row[11]</A> </td>\n";
		$call_log .= "<td align=right><font size=2> $row[3] </td>\n";
		$call_log .= "<td align=right><font size=2> $row[2] </td>\n";
		$call_log .= "<td align=right><font size=2> $row[1] </td>\n";
		$call_log .= "<td align=right><font size=2> $row[15] </td>\n";
		$call_log .= "<td align=right><font size=2>&nbsp; $row[10] </td></tr>\n";

		$campaign_id = $row[3];
		}

	##### grab vicidial_agent_log records #####
	$stmt="select agent_log_id,user,server_ip,event_time,lead_id,campaign_id,pause_epoch,pause_sec,wait_epoch,wait_sec,talk_epoch,talk_sec,dispo_epoch,dispo_sec,status,user_group,comments,sub_status from vicidial_agent_log where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by agent_log_id desc limit 500;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$Alogs_to_print = mysqli_num_rows($rslt);
	if($DB) echo __LINE__."<br>\n";
	$y=0;
	$agent_log = '';
	$Alog_campaign = '';
	while ($Alogs_to_print > $y)
		{
		$row=mysqli_fetch_row($rslt);
		if (strlen($Alog_campaign)<1) {$Alog_campaign = $row[5];}
		if (preg_match("/1$|3$|5$|7$|9$/i", $y))
			{$bgcolor='bgcolor="#B9CBFD"';}
		else
			{$bgcolor='bgcolor="#9BB9FB"';}

		$y++;
		$agent_log .= "<tr $bgcolor>";
		$agent_log .= "<td><font size=1>$y</td>";
		$agent_log .= "<td><font size=2>$row[3]</td>";
		$agent_log .= "<td align=left><font size=2> $row[5]</td>\n";
		$agent_log .= "<td align=left><font size=2> <A HREF=\"user_stats.php?user=$row[1]\" target=\"_blank\">$row[1]</A> </td>\n";
		$agent_log .= "<td align=right><font size=2> $row[7]</td>\n";
		$agent_log .= "<td align=right><font size=2> $row[9] </td>\n";
		$agent_log .= "<td align=right><font size=2> $row[11] </td>\n";
		$agent_log .= "<td align=right><font size=2> $row[13] </td>\n";
		$agent_log .= "<td align=right><font size=2> &nbsp; $row[14] </td>\n";
		$agent_log .= "<td align=right><font size=2> &nbsp; $row[15] </td>\n";
		$agent_log .= "<td align=right><font size=2> &nbsp; $row[17] </td></tr>\n";

		$campaign_id = $row[5];
		}

	##### grab vicidial_qc_agent_log records #####
	//Differentiate between View and Mod
	$stmt="select * from vicidial_qc_agent_log where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by qc_agent_log_id desc limit 100;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$Alogs_to_print = mysqli_num_rows($rslt);
	if($DB) echo "$stmt<br>\n";
	$y=0;
	$qc_agent_log = '';
	$Alog_campaign = '';
	while ($Alogs_to_print > $y)
		{
		$row=mysqli_fetch_assoc($rslt);
		if (strlen($Alog_campaign)<1) {$Alog_campaign = $row[5];}
		if (preg_match("/1$|3$|5$|7$|9$/i", $y))
			{$bgcolor='bgcolor="#B9CBFD"';}
		else
			{$bgcolor='bgcolor="#9BB9FB"';}
		$y++;
		if (strlen($row['save_epoch'])=='0')
			{ //VIEW ONLY record
			$qc_agent_log .= "<tr $bgcolor>";
			$qc_agent_log .= "<td><font size=1>$y</td>";
			$qc_agent_log .= "<td><font size=2>{$row['view_datetime']}</td>";
			$qc_agent_log .= "<td colspan='2' align='center'><font size=1 color='white'>"._QXZ("View Only - No changes")."</td>";
			$qc_agent_log .= "<td align='center'><font size=2><A HREF='user_stats.php?user={$row['qc_user']}' target='_blank'>{$row['qc_user']}</A></td>";
			$qc_agent_log .= "<td align='center'><font size=2><A HREF='user_stats.php?user={$row['lead_user']}' target='_blank'>{$row['lead_user']}</A></td>";
			$qc_agent_log .= "<td align='center'><font size=2>{$row['campaign_id']}</td>";
			$qc_agent_log .= "<td align='center'><font size=2>{$row['list_id']}</td>";
			$qc_agent_log .= "<td align='right'><font size=2>&nbsp;</td>";
			$qc_agent_log .= "<td align='right'>&nbsp;</td></tr>\n";
			}
		else 
			{ // CHANGE record
			$detailtooltip=str_replace("\n", "&#10;", $row['details']); // tool tip line break &#10; and &#xD; both work in IE, but not firefox.
			$qc_agent_log .= "<tr $bgcolor>";
			$qc_agent_log .= "<td><font size=1>$y</td>";
			$qc_agent_log .= "<td><font size=2>{$row['view_datetime']}</td>";
			$qc_agent_log .= "<td align='center'><font size=2>{$row['old_status']}</td>";
			$qc_agent_log .= "<td align='center'><font size=2>{$row['new_status']}</td>";
			$qc_agent_log .= "<td align='center'><font size=2><A HREF='user_stats.php?user={$row['qc_user']}' target='_blank'>{$row['qc_user']}</A></td>";
			$qc_agent_log .= "<td align='center'><font size=2><A HREF='user_stats.php?user={$row['lead_user']}' target='_blank'>{$row['lead_user']}</A></td>";
			$qc_agent_log .= "<td align='center'><font size=2>{$row['campaign_id']}</td>";
			$qc_agent_log .= "<td align='center'><font size=2>{$row['list_id']}</td>";
			$qc_agent_log .= "<td align='right'><font size=2>{$row['elapsed_seconds']}&nbsp;</td>";
			$qc_agent_log .= "<td title='$detailtooltip' align='right'><font size=2 color='yellow'>DETAILS</td></tr>\n";
			}
		}

	##### grab vicidial_closer_log records #####
	$stmt="SELECT closecallid,lead_id,list_id,campaign_id,call_date,start_epoch,end_epoch,length_in_sec,status,phone_code,phone_number,user,comments,processed,queue_seconds,user_group,xfercallid,term_reason,uniqueid,agent_only from vicidial_closer_log where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by closecallid desc limit 500;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$Clogs_to_print = mysqli_num_rows($rslt);

	$y=0;
	$closer_log = '';
	$Clog_campaign = '';
	while ($Clogs_to_print > $y)
		{
		$row=mysqli_fetch_row($rslt);
		if (strlen($Clog_campaign)<1) {$Clog_campaign = $row[3];}
		if (preg_match("/1$|3$|5$|7$|9$/i", $y))
			{$bgcolor='bgcolor="#B9CBFD"';}
		else
			{$bgcolor='bgcolor="#9BB9FB"';}

		$y++;
		$closer_log .= "<tr $bgcolor>";
		$closer_log .= "<td><font size=1>$y</td>";
		$closer_log .= "<td><font size=2>$row[4]</td>";
		$closer_log .= "<td align=left><font size=2> $row[7]</td>\n";
		$closer_log .= "<td align=left><font size=2> $row[8]</td>\n";
		$closer_log .= "<td align=left><font size=2> <A HREF=\"user_stats.php?user=$row[11]\" target=\"_blank\">$row[11]</A> </td>\n";
		$closer_log .= "<td align=right><font size=2> $row[3] </td>\n";
		$closer_log .= "<td align=right><font size=2> $row[2] </td>\n";
		$closer_log .= "<td align=right><font size=2> $row[1] </td>\n";
		$closer_log .= "<td align=right><font size=2> &nbsp; $row[14] </td>\n";
		$closer_log .= "<td align=right><font size=2> &nbsp; $row[17] </td></tr>\n";

		$campaign_id = $row[3];
		}

	##### grab vicidial_list data for lead #####
	$stmt="SELECT lead_id,entry_date,modify_date,status,user,vendor_lead_code,source_id,list_id,gmt_offset_now,called_since_last_reset,phone_code,phone_number,title,first_name,middle_initial,last_name,address1,address2,address3,city,state,province,postal_code,country_code,gender,date_of_birth,alt_phone,email,security_phrase,comments,called_count,last_local_call_time,rank,owner,entry_list_id from vicidial_list where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "'";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);
	if (strlen($row[0]) > 0)
		{$lead_id		= $row[0];}
	$dispo				= $row[3];
	$tsr				= $row[4];
	$vendor_id			= $row[5];
	$list_id			= $row[7];
	$gmt_offset_now		= $row[8];
	$phone_code			= $row[10];
	$phone_number		= $row[11];
	$title				= $row[12];
	$first_name			= $row[13];
	$middle_initial		= $row[14];
	$last_name			= $row[15];
	$address1			= $row[16];
	$address2			= $row[17];
	$address3			= $row[18];
	$city				= $row[19];
	$state				= $row[20];
	$province			= $row[21];
	$postal_code		= $row[22];
	$country_code		= $row[23];
	$gender				= $row[24];
	$date_of_birth		= $row[25];
	$alt_phone			= $row[26];
	$email				= $row[27];
	$security			= $row[28];
	$comments			= $row[29];
	$called_count		= $row[30];
	$last_local_call_time = $row[31];
	$rank				= $row[32];
	$owner				= $row[33];
	$entry_list_id		= $row[34];

	if($DB) 
		{
		echo __LINE__."\n";
		}
	echo "<br>Call information: $first_name $last_name - $phone_number<br><br><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=end_call value=1>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=hidden name=lead_id value=\"$lead_id\">\n";
	echo "<input type=hidden name=dispo value=\"$dispo\">\n";
	echo "<input type=hidden name=list_id value=\"$list_id\">\n";
	echo "<input type=hidden name=campaign_id value=\"$campaign_id\">\n";
	echo "<input type=hidden name=old_phone value=\"$phone_number\">\n";
	echo "<input type=hidden name=server_ip value=\"$server_ip\">\n";
	echo "<input type=hidden name=extension value=\"$extension\">\n";
	echo "<input type=hidden name=channel value=\"$channel\">\n";
	echo "<input type=hidden name=call_began value=\"$call_began\">\n";
	echo "<input type=hidden name=parked_time value=\"$parked_time\">\n";
	echo "<table cellpadding=1 cellspacing=0>\n";
	echo "<tr><td colspan=2>$label_vendor_lead_code: $vendor_id &nbsp; &nbsp; "._QXZ("Lead ID").": $lead_id</td></tr>\n";
	echo "<tr><td colspan=2>"._QXZ("Fronter").": <A HREF=\"user_stats.php?user=$tsr\">$tsr</A> &nbsp; &nbsp; "._QXZ("List ID").": $list_id &nbsp; &nbsp; "._QXZ("Called Count").": $called_count</td></tr>\n";

	echo "<tr><td align=right>$label_title: </td><td align=left><input type=text name=title size=4 maxlength=$MAXtitle value=\"$title\"> &nbsp; \n";
	echo "$label_first_name: <input type=text name=first_name size=15 maxlength=$MAXfirst_name value=\"$first_name\"> </td></tr>\n";
	echo "<tr><td align=right>$label_middle_initial:  </td><td align=left><input type=text name=middle_initial size=4 maxlength=$MAXmiddle_initial value=\"$middle_initial\"> &nbsp; \n";
	echo " $label_last_name: <input type=text name=last_name size=15 maxlength=$MAXlast_name value=\"$last_name\"> </td></tr>\n";
	echo "<tr><td align=right>$label_address1 : </td><td align=left><input type=text name=address1 size=40 maxlength=$MAXaddress1 value=\"$address1\"></td></tr>\n";
	echo "<tr><td align=right>$label_address2 : </td><td align=left><input type=text name=address2 size=40 maxlength=$MAXaddress2 value=\"$address2\"></td></tr>\n";
	echo "<tr><td align=right>$label_address3 : </td><td align=left><input type=text name=address3 size=40 maxlength=$MAXaddress3 value=\"$address3\"></td></tr>\n";
	echo "<tr><td align=right>$label_city : </td><td align=left><input type=text name=city size=40 maxlength=$MAXcity value=\"$city\"></td></tr>\n";
	echo "<tr><td align=right>$label_state: </td><td align=left><input type=text name=state size=2 maxlength=$MAXstate value=\"$state\"> &nbsp; \n";
	echo " $label_postal_code: <input type=text name=postal_code size=10 maxlength=$MAXpostal_code value=\"$postal_code\"> </td></tr>\n";

	echo "<tr><td align=right>$label_province : </td><td align=left><input type=text name=province size=30 maxlength=$MAXprovince value=\"$province\"></td></tr>\n";
	echo "<tr><td align=right>Country : </td><td align=left><input type=text name=country_code size=3 maxlength=$MAXcountry_code value=\"$country_code\"></td></tr>\n";
	echo "<tr><td align=right>$label_phone_number : </td><td align=left><input type=text name=phone_number size=18 maxlength=$MAXphone_number value=\"$phone_number\"></td></tr>\n";
	echo "<tr><td align=right>$label_phone_code : </td><td align=left><input type=text name=phone_code size=10 maxlength=$MAXphone_code value=\"$phone_code\"></td></tr>\n";
	echo "<tr><td align=right>$label_alt_phone : </td><td align=left><input type=text name=alt_phone size=12 maxlength=$MAXalt_phone value=\"$alt_phone\"></td></tr>\n";
	echo "<tr><td align=right>$label_email : </td><td align=left><input type=text name=email size=40 maxlength=$MAXemail value=\"$email\"></td></tr>\n";
	echo "<tr><td align=right>$label_security_phrase : </td><td align=left><input type=text name=security size=40 maxlength=$MAXsecurity_phrase value=\"$security\"></td></tr>\n";
	echo "<tr><td align=right>Rank : </td><td align=left><input type=text name=rank size=7 maxlength=5 value=\"$rank\"></td></tr>\n";
	echo "<tr><td align=right>Owner : </td><td align=left><input type=text name=owner size=22 maxlength=$MAXowner value=\"$owner\"></td></tr>\n";
	echo "<tr><td align=right>$label_comments : </td><td align=left><TEXTAREA name=comments ROWS=3 COLS=65>$comments</TEXTAREA></td></tr>\n";
	$stmt="SELECT user_id, timestamp, list_id, campaign_id, comment from vicidial_comments where lead_id='$lead_id' order by timestamp";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row_count = mysqli_num_rows($rslt);
	$o=0;
	$comments=false;
	while ($row_count > $o)
		{
        if (!$comments) 
			{
			echo "<tr><td colspan='2' align=center><b>"._QXZ("Comment History")."</b></td></tr>\n";
			$comments=true;
			}
		$rowx=mysqli_fetch_row($rslt);
             	echo "<tr><td align=right>$rowx[0] : </td><td align=left><hr>$rowx[1]<br><b>"._QXZ("List ID").":</b> $rowx[2]; <b>"._QXZ("Campaign ID").":</b> $rowx[3]<br>$rowx[4]</td></tr>\n";
		$o++;
		}

	if ($comments) 
		{
		echo "<tr><td align=center></td><td><hr></td></tr>\n";
		}
	echo "<tr bgcolor=#B6D3FC><td align=right>"._QXZ("QC Result").": </td><td align=left><select size=1 name=status>\n";

//This section reserved for future expansion (when each campaign will have its own list of QC Result Codes instead of using the the entire master set)
//	$list_campaign='';
//	$stmt="SELECT campaign_id from vicidial_lists where list_id='$list_id'";
//	$rslt=mysql_to_mysqli($stmt, $link);
//	if ($DB) {echo "$stmt\n";}
//	$Cstatuses_to_print = mysqli_num_rows($rslt);
//	if ($Cstatuses_to_print > 0)
//		{
//		$row=mysqli_fetch_row($rslt);
//		$list_campaign = $row[0];
//		}

	$stmt="SELECT code,code_name,qc_result_type from vicidial_qc_codes order by code_name";
	$rslt=mysql_to_mysqli($stmt, $link);
	$statuses_to_print = mysqli_num_rows($rslt);
	$statuses_list='';

	$o=0;
	$DS=0;
	$statuses_list = "<option SELECTED value=\"$dispo\">$dispo</option>\n"; $DS++;
	while ($statuses_to_print > $o)
		{
		$rowx=mysqli_fetch_row($rslt);
		$statuses_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
		$o++;
		}

	$stmt="SELECT status,status_name,selectable,campaign_id,human_answered,category,sale,dnc,customer_contact,not_interested,unworkable from vicidial_campaign_statuses where selectable='Y' and campaign_id='$list_campaign' order by status";
	$rslt=mysql_to_mysqli($stmt, $link);
	$CAMPstatuses_to_print = mysqli_num_rows($rslt);

	$o=0;
	$CBhold_set=0;
        //This function gathers campaign specific statuses to display as dispositions for this record (Note Added by Poundteam)
        //This function is disabled in QC (statuses are generated from qc codes instead)
	while ($CAMPstatuses_to_print > $o)
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		$rowx=mysqli_fetch_row($rslt);
		if ( (strlen($dispo) ==  strlen($rowx[0])) and (preg_match("/$dispo/",$rowx[0])) )
			{$statuses_list .= "<option SELECTED value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n"; $DS++;}
		else
			{$statuses_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";}
		if ($rowx[0] == 'CBHOLD') {$CBhold_set++;}
		$o++;
		}

	if ($dispo == 'CBHOLD') {$CBhold_set++;}

	if($DB) 
		{
		echo __LINE__."\n";
		}
	if ($DS < 1)
		{$statuses_list .= "<option SELECTED value=\"$dispo\">$dispo</option>\n";}
	if ($CBhold_set < 1)
		{$statuses_list .= "<option value=\"CBHOLD\">"._QXZ("CBHOLD - Scheduled Callback")."</option>\n";}
	echo "$statuses_list";
	echo "</select> <i>("._QXZ("with")." $list_campaign "._QXZ("statuses").")</i></td></tr>\n";

//      Section Modified for QC Functionality By PoundTeam
//	echo "<tr bgcolor=#B6D3FC><td align=left>Modify vicidial log </td><td align=left><input type=checkbox name=modify_logs value=\"1\" CHECKED></td></tr>\n";
//	echo "<tr bgcolor=#B6D3FC><td align=left>Modify agent log </td><td align=left><input type=checkbox name=modify_agent_logs value=\"1\" CHECKED></td></tr>\n";
//	echo "<tr bgcolor=#B6D3FC><td align=left>Modify closer log </td><td align=left><input type=checkbox name=modify_closer_logs value=\"1\"></td></tr>\n";
	echo "<tr bgcolor=#B6D3FC><td align=left>"._QXZ("Disable QC log entry")." </td><td align=left><input type=checkbox name=add_qc_record value=\"1\">("._QXZ("this feature is not active yet").")</td></tr>\n";

	echo "<tr><td colspan=2 align=center><input type=submit name=submit value=\""._QXZ("SUBMIT")."\"></td></tr>\n";
	echo "<tr><td align=right>";
	if (strlen($qc_webform) > 4)
		{
		echo "<a href=\"$qc_webform\">"._QXZ("QC Webform")."</a>\n";
		}
	echo "</td><td></td></tr>\n";
	echo "<input type=hidden name=viewtime value='$STARTtime' /></table></form>\n";
	echo "<BR><BR><BR>\n";

	echo "<TABLE BGCOLOR=#B6D3FC WIDTH=750><TR><TD>\n";
	echo _QXZ("Callback Details").":<BR><CENTER>\n";
        //Added scheduled_callback regular statuses option
	if ( ($dispo == 'CALLBK') or ($dispo == 'CBHOLD') || $scheduled_callback=='Y' )
		{
	if($DB) 
		{
		echo __LINE__."\n";
		}
		### find any vicidial_callback records for this lead
		$stmt="select callback_id,lead_id,list_id,campaign_id,status,entry_time,callback_time,modify_date,user,recipient,comments,user_group from vicidial_callbacks where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' and status IN('ACTIVE','LIVE') order by callback_id desc LIMIT 1;";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$CB_to_print = mysqli_num_rows($rslt);
		$rowx=mysqli_fetch_row($rslt);

		if ($CB_to_print>0)
			{
			if ($rowx[9] == 'USERONLY')
				{
				echo "<br><form action=$PHP_SELF method=POST>\n";
				echo "<input type=hidden name=CBchangeUSERtoANY value=\"YES\">\n";
				echo "<input type=hidden name=DB value=\"$DB\">\n";
				echo "<input type=hidden name=lead_id value=\"$lead_id\">\n";
				echo "<input type=hidden name=callback_id value=\"$rowx[0]\">\n";
				echo "<input type=submit name=submit value=\""._QXZ("CHANGE TO ANYONE CALLBACK")."\"><input type=hidden name=viewtime value='$STARTtime' /></form><BR>\n";

				echo "<br><form action=$PHP_SELF method=POST>\n";
				echo "<input type=hidden name=CBchangeUSERtoUSER value=\"YES\">\n";
				echo "<input type=hidden name=DB value=\"$DB\">\n";
				echo "<input type=hidden name=lead_id value=\"$lead_id\">\n";
				echo "<input type=hidden name=callback_id value=\"$rowx[0]\">\n";
				echo _QXZ("New Callback Owner UserID").": <input type=text name=CBuser size=18 maxlength=20 value=\"$rowx[8]\"> \n";
				echo "<input type=submit name=submit value=\""._QXZ("CHANGE USERONLY CALLBACK USER")."\"><input type=hidden name=viewtime value='$STARTtime' /></form><BR>\n";
				}
			else
				{
				echo "<br><form action=$PHP_SELF method=POST>\n";
				echo "<input type=hidden name=CBchangeANYtoUSER value=\"YES\">\n";
				echo "<input type=hidden name=DB value=\"$DB\">\n";
				echo "<input type=hidden name=lead_id value=\"$lead_id\">\n";
				echo "<input type=hidden name=callback_id value=\"$rowx[0]\">\n";
				echo _QXZ("New Callback Owner UserID").": <input type=text name=CBuser size=18 maxlength=20 value=\"$rowx[8]\"> \n";
				echo "<input type=submit name=submit value=\""._QXZ("CHANGE TO USERONLY CALLBACK")."\"><input type=hidden name=viewtime value='$STARTtime' /></form><BR>\n";
				}

			$appointment_datetimeARRAY = explode(" ",$rowx[6]);
			$appointment_date = $appointment_datetimeARRAY[0];
			$appointment_timeARRAY = explode(":",$appointment_datetimeARRAY[1]);
			$appointment_hour = $appointment_timeARRAY[0];
			$appointment_min = $appointment_timeARRAY[1];

			?>

			<FORM METHOD=POST NAME=vsn ID=vsn ACTION="<?php echo $PHP_SELF ?>">
			<BR><?php echo _QXZ("Change Scheduled Callback Date"); ?>:<BR>

			<TABLE BORDER=0 CELLPADDING=0 CELLSPACING=2 WIDTH=700>
			<TR><TD COLSPAN=2 ALIGN=CENTER>
			<input type=hidden name=DB id=DB value=<?php echo $DB ?>>
			<input type=hidden name=CBchangeDATE value="YES">
			<input type=hidden name=lead_id id=lead_id value="<?php echo $lead_id ?>">
			<input type=hidden name=callback_id value="<?php echo $rowx[0] ?>">

			<TR BGCOLOR="#E6E6E6">
			<TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA"><?php echo _QXZ("CallBack Date/Time"); ?>: </FONT></TD><TD ALIGN=LEFT><input type=text name=appointment_date id=appointment_date size=10 maxlength=10 value="<?php echo $appointment_date ?>">

			<script type="text/javascript">
			var o_cal = new tcal ({
				// form name
				'formname': 'vsn',
				// input name
				'controlname': 'appointment_date'
			},{
				'months' : [_QXZ("January"), 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
				'weekdays' : ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
				'yearscroll': false, // show year scroller
				'weekstart': 0, // first day of week: 0-Su or 1-Mo
				'centyear'  : 70, // 2 digit years less than 'centyear' are in 20xx, othewise in 19xx.
				'imgpath' : './images/' // directory with calendar images
			});
			o_cal.a_tpl.yearscroll = false;
			// o_cal.a_tpl.weekstart = 1; // Monday week start
			</script>
			&nbsp; &nbsp;
			<input type=hidden name=appointment_time id=appointment_time value="<?php echo $appointment_time ?>">
			<SELECT name=appointment_hour id=appointment_hour>
			<option>00</option>
			<option>01</option>
			<option>02</option>
			<option>03</option>
			<option>04</option>
			<option>05</option>
			<option>06</option>
			<option>07</option>
			<option>08</option>
			<option>09</option>
			<option>10</option>
			<option>11</option>
			<option>12</option>
			<option>13</option>
			<option>14</option>
			<option>15</option>
			<option>16</option>
			<option>17</option>
			<option>18</option>
			<option>19</option>
			<option>20</option>
			<option>21</option>
			<option>22</option>
			<option>23</option>
			<OPTION value="<?php echo $appointment_hour ?>" selected><?php echo $appointment_hour ?></OPTION>
			</SELECT>:
			<SELECT name=appointment_min id=appointment_min>
			<option>00</option>
			<option>05</option>
			<option>10</option>
			<option>15</option>
			<option>20</option>
			<option>25</option>
			<option>30</option>
			<option>35</option>
			<option>40</option>
			<option>45</option>
			<option>50</option>
			<option>55</option>
			<OPTION value="<?php echo $appointment_min ?>" selected><?php echo $appointment_min ?></OPTION>
			</SELECT>

			</TD>
			</TR>
			<TR BGCOLOR="#E6E6E6">
			<TD align=center colspan=2>
			<?php echo _QXZ("Comments"); ?>:

			<TEXTAREA name=comments ROWS=3 COLS=65><?php echo $rowx[10] ?></TEXTAREA>
			</TD>
			</TR>

			<TR BGCOLOR="#E6E6E6">
			<TD align=center colspan=2>

			<SCRIPT type="text/javascript">

			function submit_form()
				{
				var appointment_hourFORM = document.getElementById('appointment_hour');
				var appointment_hourVALUE = appointment_hourFORM[appointment_hourFORM.selectedIndex].text;
				var appointment_minFORM = document.getElementById('appointment_min');
				var appointment_minVALUE = appointment_minFORM[appointment_minFORM.selectedIndex].text;

				document.vsn.appointment_time.value = appointment_hourVALUE + ":" + appointment_minVALUE + ":00";

				document.vsn.submit();
				}

			</SCRIPT>

			<input type=button value="<?php echo _QXZ("SUBMIT"); ?>" name=smt id=smt onClick="submit_form()">
			</TD>
			</TR>
<input type=hidden name=viewtime value='<?php echo $STARTtime; ?>' />
			</TABLE>
			</FORM>

			<?php
			}
		else
			{
			echo "<BR>"._QXZ("No Callback records found")."<BR>\n";
			}
		}
	else
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
			//Modifed text to allow for other than CBHOLD via custom statuses with scheduled callback chosen
		echo "<BR>"._QXZ("If you want to change this lead to a scheduled callback, first change the Disposition to CBHOLD or similar, then submit and you will be able to set the callback date and time.")."<BR>\n";
		}
	echo "</TD></TR></TABLE>\n";

	echo "<br><br>\n";

	echo "<center>\n";

	if ($c > 0)
		{
		if($DB) 
			{
			echo __LINE__."\n";
			}
		echo "<B>"._QXZ("EXTENDED ALTERNATE PHONE NUMBERS FOR THIS LEAD").":</B>\n";
		echo "<TABLE width=550 cellspacing=0 cellpadding=1>\n";
		echo "<tr><td><font size=1># </td><td><font size=2>"._QXZ("ALT PHONE")." </td><td align=left><font size=2>"._QXZ("ALT NOTE")."</td><td align=left><font size=2> "._QXZ("ALT COUNT")."</td><td align=left><font size=2> "._QXZ("ACTIVE")."</td></tr>\n";

		echo "$alts_output\n";

		echo "</TABLE>\n";
		echo "<BR><BR>\n";
		}



	### iframe for custom fields display/editing
	if ($custom_fields_enabled > 0)
		{
		$CLlist_id = $list_id;
		if (strlen($entry_list_id) > 2)
			{$CLlist_id = $entry_list_id;}
		$stmt="SHOW TABLES LIKE \"custom_$CLlist_id\";";
		if ($DB>0) {echo "$stmt";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$tablecount_to_print = mysqli_num_rows($rslt);
		if ($tablecount_to_print > 0)
			{
			$stmt="SELECT count(*) from custom_$CLlist_id where lead_id='$lead_id';";
			if ($DB>0) {echo "$stmt";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$fieldscount_to_print = mysqli_num_rows($rslt);
			if ($fieldscount_to_print > 0)
				{
				$rowx=mysqli_fetch_row($rslt);
				$custom_records_count =	$rowx[0];

				echo "<B>"._QXZ("CUSTOM FIELDS FOR THIS LEAD").":</B><BR>\n";
				echo "<iframe src=\"../agc/$vdc_form_display?lead_id=$lead_id&list_id=$CLlist_id&stage=DISPLAY&submit_button=YES&user=$PHP_AUTH_USER&pass=$PHP_AUTH_PW&bcrypt=OFF&bgcolor=E6E6E6\" style=\"background-color:transparent;\" scrolling=\"auto\" frameborder=\"2\" allowtransparency=\"true\" id=\"vcFormIFrame\" name=\"vcFormIFrame\" width=\"740\" height=\"300\" STYLE=\"z-index:18\"> </iframe>\n";
				echo "<BR><BR>";
				}
			}
		}


	echo "<B>"._QXZ("CALLS TO THIS LEAD").":</B>\n";
	echo "<TABLE width=750 cellspacing=0 cellpadding=1>\n";
	echo "<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("LENGTH")."</td><td align=left><font size=2> "._QXZ("STATUS")."</td><td align=left><font size=2> "._QXZ("TSR")."</td><td align=right><font size=2> "._QXZ("CAMPAIGN")."</td><td align=right><font size=2> "._QXZ("LIST")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td><td align=right><font size=2> "._QXZ("HANGUP REASON")."</td><td align=right><font size=2> "._QXZ("PHONE")."</td></tr>\n";

	echo "$call_log\n";

	echo "</TABLE>\n";
	echo "<BR><BR>\n";

	echo "<B>"._QXZ("CLOSER RECORDS FOR THIS LEAD").":</B>\n";
	echo "<TABLE width=750 cellspacing=0 cellpadding=1>\n";
	echo "<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("LENGTH")."</td><td align=left><font size=2> "._QXZ("STATUS")."</td><td align=left><font size=2> "._QXZ("TSR")."</td><td align=right><font size=2> "._QXZ("CAMPAIGN")."</td><td align=right><font size=2> "._QXZ("LIST")."</td><td align=right><font size=2> "._QXZ("LEAD")."</td><td align=right><font size=2> "._QXZ("WAIT")."</td><td align=right><font size=2> "._QXZ("HANGUP REASON")."</td></tr>\n";

	echo "$closer_log\n";

	echo "</TABLE></center>\n";
	echo "<BR><BR>\n";


	echo "<B>"._QXZ("AGENT LOG RECORDS FOR THIS LEAD").":</B>\n";
	echo "<TABLE width=750 cellspacing=0 cellpadding=1>\n";
	echo "<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("CAMPAIGN")."</td><td align=left><font size=2> "._QXZ("TSR")."</td><td align=left><font size=2> "._QXZ("PAUSE")."</td><td align=right><font size=2> "._QXZ("WAIT")."</td><td align=right><font size=2> "._QXZ("TALK")."</td><td align=right><font size=2> "._QXZ("DISPO")."</td><td align=right><font size=2> "._QXZ("STATUS")."</td><td align=right><font size=2> "._QXZ("GROUP")."</td><td align=right><font size=2> "._QXZ("SUB")."</td></tr>\n";

	echo "$agent_log\n";
	echo "</TABLE>\n";
	echo "<BR><BR>\n";
	echo "<B>"._QXZ("QUALITY CONTROL LOG RECORDS FOR THIS LEAD").":</B>\n";
	echo "<TABLE width=750 cellspacing=0 cellpadding=1>\n";
	echo "<tr><td><font size=1># </td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=center><font size=2>"._QXZ("OLD STATUS")."</td><td align=center><font size=2>"._QXZ("NEW STATUS")."</td><td align=center><font size=2>"._QXZ("QC USER")."</td><td align=center><font size=2>"._QXZ("AGENT")."</td><td align=center><font size=2>"._QXZ("CAMPAIGN")."</td><td align=center><font size=2>"._QXZ("LIST")."</td><td align=center><font size=2>"._QXZ("ELAPSED")."</td><td align=center><font size=2>&nbsp;</td></tr>\n";
	echo "$qc_agent_log\n";
	echo "</TABLE>\n";
	echo "<BR><BR>\n";

	echo "<B>"._QXZ("RECORDINGS FOR THIS LEAD").":</B>\n";
	echo "<TABLE width=750 cellspacing=1 cellpadding=1>\n";
	echo "<tr><td><font size=1># </td><td align=left><font size=2> "._QXZ("LEAD")."</td><td><font size=2>"._QXZ("DATE/TIME")." </td><td align=left><font size=2>"._QXZ("SECONDS")." </td><td align=left><font size=2> &nbsp; "._QXZ("RECID")."</td><td align=center><font size=2>"._QXZ("FILENAME")."</td><td align=left><font size=2>"._QXZ("LOCATION")."</td><td align=left><font size=2>"._QXZ("TSR")."</td><td align=left><font size=2> </td></tr>\n";

	$stmt="select recording_id,channel,server_ip,extension,start_time,start_epoch,end_time,end_epoch,length_in_sec,length_in_min,filename,location,lead_id,user,vicidial_id from recording_log where lead_id='" . mysqli_real_escape_string($link, $lead_id) . "' order by recording_id desc limit 500;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$logs_to_print = mysqli_num_rows($rslt);
	if ($DB) {echo "$logs_to_print|$stmt|\n";}

	$u=0;
	while ($logs_to_print > $u)
		{
		$row=mysqli_fetch_row($rslt);
		if (preg_match("/1$|3$|5$|7$|9$/i", $u))
			{$bgcolor='bgcolor="#B9CBFD"';}
		else
			{$bgcolor='bgcolor="#9BB9FB"';}

		$location = $row[11];

		if (strlen($location)>2)
			{
			$URLserver_ip = $location;
			$URLserver_ip = preg_replace('/http:\/\//i', '',$URLserver_ip);
			$URLserver_ip = preg_replace('/https:\/\//i', '',$URLserver_ip);
			$URLserver_ip = preg_replace('/\/.*/i', '',$URLserver_ip);
			$stmt="select count(*) from servers where server_ip='$URLserver_ip';";
			$rsltx=mysql_to_mysqli($stmt, $link);
			$rowx=mysqli_fetch_row($rsltx);

			if ($rowx[0] > 0)
				{
				$stmt="select recording_web_link,alt_server_ip,external_server_ip from servers where server_ip='$URLserver_ip';";
				$rsltx=mysql_to_mysqli($stmt, $link);
				$rowx=mysqli_fetch_row($rsltx);

				if (preg_match("/ALT_IP/i",$rowx[0]))
					{
					$location = preg_replace("/$URLserver_ip/i", "$rowx[1]", $location);
					}
				if (preg_match("/EXTERNAL_IP/i",$rowx[0]))
					{
					$location = preg_replace("/$URLserver_ip/i", "$rowx[2]", $location);
					}
				}
			}

		if (strlen($location)>30)
			{$locat = substr($location,0,27);  $locat = "$locat...";}
		else
			{$locat = $location;}
		$play_audio='<td align=left><font size=2> </font></td>';
		if ( (preg_match('/ftp/i',$location)) or (preg_match('/http/i',$location)) )
			{
			if ($log_recording_access<1) 
				{
				$play_audio = "<td align=left><font size=2> <audio controls preload=\"none\"> <source src ='$location' type='audio/wav' > <source src ='$location' type='audio/mpeg' >"._QXZ("No browser audio playback support")."</audio> </td>\n";
				$location = "<a href=\"$location\">$locat</a>";
				}
			else
				{
				$location = "<a href=\"recording_log_redirect.php?recording_id=$row[0]&lead_id=$row[12]&search_archived_data=0\">$locat</a>";
				}
			}
		else
			{$location = $locat;}
		$u++;
		echo "<tr $bgcolor>";
		echo "<td><font size=1>$u</td>";
		echo "<td align=left><font size=2> $row[12] </td>";
		echo "<td align=left><font size=1> $row[4] </td>\n";
		echo "<td align=left><font size=2> $row[8] </td>\n";
		echo "<td align=left><font size=2> $row[0] &nbsp;</td>\n";
		echo "<td align=center><font size=1> $row[10] </td>\n";
		echo "<td align=left><font size=2> $location </td>\n";
		echo "<td align=left><font size=2> <A HREF=\"user_stats.php?user=$row[13]\" target=\"_blank\">$row[13]</A> </td>";
		echo "$play_audio";
		echo "</tr>\n";
		}


	echo "</TABLE><BR><BR>\n";


	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and pass='$PHP_AUTH_PW' and user_level >= 9 and modify_leads='1';";
	if ($DB) {echo "|$stmt|\n";}
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_display=$row[0];
	if ($admin_display > 0)
		{
		echo "<a href=\"admin.php?ADD=720000000000000&stage=$lead_id&category=LEADS\">"._QXZ("Click here to see Lead Modify changes to this lead")."</a>\n";
		}

	echo "</center>\n";
	}


$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";


?>


</body>
</html>

<?php
if($DB) 
	{
    echo "<pre>"._QXZ("original_record").":<br>";
	print_r($original_record);
    echo "<pre>"._QXZ("new_record").":<br>";
	print_r($new_record);
    echo _QXZ("scheduled_callback").":<br>";
	print_r($scheduled_callback);
    echo _QXZ("debug1").":<br>";
	print_r($debug1);
    echo _QXZ("Post").":<br>";
	print_r($_POST);
    echo _QXZ("qcchangelist").":<br>";
	print_r($qcchangelist);
    echo "</pre>";
	}
exit;

?>
