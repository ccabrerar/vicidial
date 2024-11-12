<?php
# admin_bulk_tools.php
#
# Copyright (C) 2024  Mike Coate, Mike Cargile, Matt Florell	<vicidial@gmail.com>	LICENSE: AGPLv2
#
# This is the admin screen for various bulk copy/delete tools.
#
# 150917-0000 - Creation of script, only outputs SQL to be copied for bulk DID and AC-CID
# 160405-0000 - Completed redesign, condensed from 3 scripts into single, simplified bulk insert options, integrated with VICIdial DB
# 160405-2331 - Added bulk user support
# 160406-2330 - Added bulk delete function for user and phone
# 160407-2203 - Added bulk add confirmations, help
# 160517-1455 - Added user_group restrictions
# 160604-1753 - Minor adjustments
# 160613-1539 - Fixed AC-CID duplicate check SQL typo, added user ID leading zero check, added check for empty insertions, other minor adjustments, cleaning of code
# 160617-1727 - Modified SQL insertions to be done in groups instead of all at once, added runtime output to insertions
# 160618-0029 - Added bulk AC-CID delete
# 160801-1208 - Added colors features and added missing QXZ text output
# 161218-1819 - Added CSV method to AC-CID 
# 161218-2144 - Added STATE FILL method to AC-CID and upped the max insertion limit to 5K 'cause why not?'
# 170409-1552 - Added IP List validation code
# 170915-1105 - Added IGNORE to bulk inserts to not error out entire statement if only one entry is a unique index duplicate
# 180213-2245 - Added CID Groups ability for AC-CID section
# 180301-1538 - Fixed issue with STATE CID Groups insertion
# 180323-1643 - Updated column labels in user copy function to add ones that had been created since script was made.
# 180330-1427 - Added 'active' column to CID Group import
# 180502-2115 - Added new help display
# 200108-0956 - Added CID Group type of NONE
# 200405-1738 - Fix for Issue #1202
# 200816-0930 - Added CID Groups to several labels
# 210312-1429 - Added DID bulk delete text area, filtered out did_system_filter as selectable
# 210315-1644 - Added CID bulk delete text area and ---ALL-- option
# 220112-1806 - Added CAN(Canadian) states lookup for STATEFILL feature and CID-STATE feature
# 220222-0821 - Added allow_web_debug system setting
# 230522-1726 - Added missing vicidial_users fields from copy function
# 240217-0908 - Added more missing vicidial_users fields from copy function
# 240801-1130 - Code updates for PHP8 compatibility
#

require("dbconnect_mysqli.php");
require("functions.php");

$version = '2.14-27';
$build = '230522-1726';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$ip = getenv("REMOTE_ADDR");
$SQLdate = date("Y-m-d H:i:s");
# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('$PHP_SELF?form_to_run=help";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";
$form_to_run = "NONE";
$DB=0; 						# Debug flag
$INSERTmax_limit = 5000; 	# Maximum number of items allowed to insert
$INSERTgroup_limit = 20;	# Number of items per group to be inserted
$STARTtime = date("U");

if (isset($_POST["form_to_run"]))					{$form_to_run=$_POST["form_to_run"];}
	elseif (isset($_GET["form_to_run"]))			{$form_to_run=$_GET["form_to_run"];}
if (isset($_POST["DB"]))							{$DB=$_POST["DB"];}
	elseif (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
if (isset($_POST["ACCIDcampaign"]))					{$ACCIDcampaign=$_POST["ACCIDcampaign"];}
if (isset($_POST["ACCIDareacode"]))					{$ACCIDareacode=$_POST["ACCIDareacode"];}
if (isset($_POST["ACCIDdids"]))						{$ACCIDto_insert_raw=$_POST["ACCIDdids"];}
if (isset($_POST["ACCIDto_insert"]))				{$ACCIDto_insert_CONFIRMED=$_POST["ACCIDto_insert"];}
if (isset($_POST["ACCIDdescription"]))				{$ACCIDdescription=$_POST["ACCIDdescription"];}
if (isset($_POST["ACCIDactive"]))					{$ACCIDactive=$_POST["ACCIDactive"];}
if (isset($_POST["ACCIDactiveinput"]))				{$ACCIDactiveinput=$_POST["ACCIDactiveinput"];}
if (isset($_POST["ACCIDdelete_campaign"]))			{$ACCIDdelete_campaign=$_POST["ACCIDdelete_campaign"];}
if (isset($_POST["ACCIDdelete_from"]))				{$ACCIDdelete_from=$_POST["ACCIDdelete_from"];}
if (isset($_POST["ACCIDdelete_from_CONFIRMED"]))	{$ACCIDdelete_from_CONFIRMED=$_POST["ACCIDdelete_from_CONFIRMED"];}
if (isset($_POST["ACCIDclear_all"]))				{$ACCIDclear_all=$_POST["ACCIDclear_all"];}
if (isset($_POST["ACCIDclear_all_CONFIRMED"]))		{$ACCIDclear_all_CONFIRMED=$_POST["ACCIDclear_all_CONFIRMED"];}
if (isset($_POST["ACCIDmethod"]))					{$ACCIDmethod=$_POST["ACCIDmethod"];}
if (isset($_POST["DIDcopy_from"]))					{$DIDcopy_from=$_POST["DIDcopy_from"];}
if (isset($_POST["DIDto_insert"]))					{$DIDto_insert_raw=$_POST["DIDto_insert"];}
if (isset($_POST["DIDto_insert_CONFIRMED"]))		{$DIDto_insert_CONFIRMED=$_POST["DIDto_insert_CONFIRMED"];}
if (isset($_POST["DIDdelete_from"]))				{$DIDdelete_from=$_POST["DIDdelete_from"];}
if (isset($_POST["DIDdelete_from_CONFIRMED"]))		{$DIDdelete_from_CONFIRMED=$_POST["DIDdelete_from_CONFIRMED"];}
if (isset($_POST["USERcopy_from"]))					{$USERcopy_from=$_POST["USERcopy_from"];}
if (isset($_POST["USERto_insert"]))					{$USERto_insert=$_POST["USERto_insert"];}
if (isset($_POST["USERstart"]))						{$USERstart=$_POST["USERstart"];}
if (isset($_POST["USERstop"]))						{$USERstop=$_POST["USERstop"];}
if (isset($_POST["USERforce_pw"]))					{$USERforce_pw=$_POST["USERforce_pw"];}
if (isset($_POST["USERdelete_from"]))				{$USERdelete_from=$_POST["USERdelete_from"];}
if (isset($_POST["USERdelete_from_CONFIRMED"]))		{$USERdelete_from_CONFIRMED=$_POST["USERdelete_from_CONFIRMED"];}
if (isset($_POST["DIDto_delete_TB"]))				{$DIDto_delete_TB=$_POST["DIDto_delete_TB"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

if ($DB) {echo "$form_to_run|";}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$sys_settings_stmt = "SELECT use_non_latin,enable_languages,language_method,campaign_cid_areacodes_enabled,sounds_central_control_active,contacts_enabled,enable_auto_reports,allow_web_debug FROM system_settings;";
$sys_settings_rslt=mysql_to_mysqli($sys_settings_stmt, $link);
#if ($DB) {echo "$sys_settings_stmt|";}
$num_rows = mysqli_num_rows($sys_settings_rslt);
if ($num_rows > 0)
	{
	$sys_settings_row=mysqli_fetch_row($sys_settings_rslt);
	$non_latin =						$sys_settings_row[0];
	$SSenable_languages =				$sys_settings_row[1];
	$SSlanguage_method =				$sys_settings_row[2];
	$SScampaign_cid_areacodes_enabled = $sys_settings_row[3];
	$SSsounds_central_control_active =	$sys_settings_row[4];
	$SScontacts_enabled =				$sys_settings_row[5];
	$SSenable_auto_reports =			$sys_settings_row[6];
	$SSallow_web_debug =				$sys_settings_row[7];
	}
else
	{
	echo _QXZ("There are no system settings. You might want to look into that.");
	exit;
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$ACCIDto_insert_raw = explode("\n", $ACCIDto_insert_raw);
if ($ACCIDmethod == "CSV") 
	{
	$i=0;
	while ($i < count($ACCIDto_insert_raw))
		{
		$ACCIDrow = explode(",", $ACCIDto_insert_raw[$i]);
		$ACCIDareacode_raw[$i] = $ACCIDrow[0];
		$ACCIDto_insert_raw[$i] = $ACCIDrow[1];
		$ACCIDdescription_raw[$i] = $ACCIDrow[2];
		$ACCIDactiveinput_raw[$i] = $ACCIDrow[3];
		$i++;
		}
	$ACCIDareacode_raw = preg_replace('/[^0-9a-zA-Z]/','',$ACCIDareacode_raw);
	$ACCIDto_insert_raw = preg_replace('/[^0-9]/','',$ACCIDto_insert_raw);
	$ACCIDto_insert_raw_ArFilter = array_filter($ACCIDto_insert_raw);
	$ACCIDdescription_raw = preg_replace('/[^-_.0-9a-zA-Z ]/','',$ACCIDdescription_raw);
	$ACCIDactiveinput_raw = preg_replace('/[^A-Z ]/','',$ACCIDactiveinput_raw);
	}
else 
	{
	$ACCIDto_insert_raw = preg_replace('/[^0-9]/','',$ACCIDto_insert_raw);
	$ACCIDto_insert_raw_ArFilter = array_filter($ACCIDto_insert_raw);
	}
$DIDto_insert_raw = preg_replace('/[^0-9\n]/','',$DIDto_insert_raw);
$DIDto_insert_raw = preg_replace('/\n+$/','',$DIDto_insert_raw);
$DIDto_insert_raw = explode("\n", $DIDto_insert_raw);
if ( $form_to_run == "BULKDIDSDELETETB" ) 
	{
	$DIDdelete_from = explode("\n", $DIDto_delete_TB);
	$DIDdelete_from = preg_replace('/[^0-9]/','',$DIDdelete_from);
	}
$DIDto_insert_raw_ArFilter = array_filter($DIDto_insert_raw);
$USERstart = preg_replace('/[^0-9]/','',$USERstart);
$USERstop = preg_replace('/[^0-9]/','',$USERstop);
$USERfull_name = preg_replace('/[^-_0-9a-zA-Z]/','',$USERfull_name);
if ( $form_to_run == "ACCIDDELETEconfirmTB" ) 
	{
	$ACCIDdelete_from = explode("\n", $ACCIDdelete_from);
	$ACCIDdelete_from = preg_replace('/[^0-9]/','',$ACCIDdelete_from);
	}

$ACCIDclear_all = preg_replace('/[^-_0-9a-zA-Z]/','',$ACCIDclear_all);
$ACCIDclear_all_CONFIRMED = preg_replace('/[^-_0-9a-zA-Z]/','',$ACCIDclear_all_CONFIRMED);
$DIDcopy_from = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$DIDcopy_from);
$USERforce_pw = preg_replace('/[^-_0-9a-zA-Z]/','',$USERforce_pw);

# Variables filter further down in the code
#	$ACCIDareacode
#	$ACCIDto_insert_CONFIRMED
#	$ACCIDdescription
#	$ACCIDactive
#	$ACCIDactiveinput
#	$DIDto_insert_CONFIRMED
#	$USERto_insert
#	$ACCIDdelete_from_CONFIRMED
#	$DIDdelete_from_CONFIRMED
#	$USERdelete_from
#	$USERdelete_from_CONFIRMED

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$ACCIDcampaign = preg_replace('/[^-_0-9a-zA-Z]/', '', $ACCIDcampaign);
	$ACCIDdelete_campaign = preg_replace('/[^-_0-9a-zA-Z]/', '', $ACCIDdelete_campaign);
	$USERcopy_from = preg_replace('/[^-_0-9a-zA-Z]/', '', $USERcopy_from);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$ACCIDcampaign = preg_replace('/[^-_0-9\p{L}]/u', '', $ACCIDcampaign);
	$ACCIDdelete_campaign = preg_replace('/[^-_0-9\p{L}]/u', '', $ACCIDdelete_campaign);
	$USERcopy_from = preg_replace('/[^-_0-9\p{L}]/u', '', $USERcopy_from);
	}

if (empty($USERfull_name)) 					{$USERfull_name = "BLANK";}
if (empty($USERdelete_from))				{$USERdelete_from="BLANK";}
if (empty($ACCIDto_insert_raw_ArFilter)) 	{$CIDcheck = "BLANK";}
if (empty($DIDto_insert_raw_ArFilter)) 		{$DIDcheck = "BLANK";}
if (empty($DIDdelete_from)) 				{$DIDdelete_from="BLANK";}
if (empty($USERstart)) 						{$USERstart = "BLANK";}
if (empty($USERstop)) 						{$USERstop = "BLANK";}
if (empty($ACCIDdelete_from)) 				{$ACCIDdelete_from = "BLANK";}
if (empty($ACCIDdelete_campaign)) 			{$ACCIDdelete_campaign = "BLANK";}
if ($ACCIDclear_all == "Y")					{$form_to_run="ACCIDDELETEconfirm";}

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "$stmt|";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

# Valid user
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

# User permissions
$rights_stmt = "SELECT user_group, modify_inbound_dids, delete_inbound_dids, modify_campaigns, delete_campaigns, modify_users, delete_users, user_level, modify_same_user_level from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "$rights_stmt|";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$user_group =		$rights_row[0];
$modify_dids =		$rights_row[1];
$delete_dids =		$rights_row[2];
$modify_campaigns =	$rights_row[3];
$delete_campaigns =	$rights_row[4];
$modify_users =		$rights_row[5];
$delete_users =		$rights_row[6];
$user_level =		$rights_row[7];
$modify_level =		$rights_row[8];

# User group permissions
$rights_stmt = "SELECT allowed_campaigns,admin_viewable_groups from vicidial_user_groups where user_group='$user_group';";
if ($DB) {echo "$rights_stmt|";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$LOGallowed_campaigns =			$rights_row[0];
$LOGadmin_viewable_groups =		$rights_row[1];

$allowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$allowed_campaignsSQL = "campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
else
	{
	$rights_stmt = "SELECT campaign_id FROM vicidial_campaigns;";
	if ($DB) {echo "$rights_stmt|";}
	$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
	$rights_rsltCOUNT=mysqli_num_rows($rights_rslt);
	$allowed_campaignsSQL = "campaign_id IN(";
	$i=0;
	while ($i < $rights_rsltCOUNT)
		{
		$rights_row=mysqli_fetch_row($rights_rslt);
		$allowed_campaignsSQL.= "'" . $rights_row[0] . "',";
		$i++;
		}	
	$allowed_campaignsSQL.= "'')";
	}

$admin_viewable_groupsSQL='';
if  (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups))
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$admin_viewable_groupsSQL = "user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{
	$rights_stmt = "SELECT user_group FROM vicidial_user_groups;";
	if ($DB) {echo "$rights_stmt|";}
	$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
	$rights_rsltCOUNT=mysqli_num_rows($rights_rslt);
	$admin_viewable_groupsSQL = "user_group IN('---ALL---',";
	$i=0;
	while ($i < $rights_rsltCOUNT)
		{
		$rights_row=mysqli_fetch_row($rights_rslt);
		$admin_viewable_groupsSQL.= "'" . $rights_row[0] . "',";
		$i++;
		}	
	$admin_viewable_groupsSQL.= "'')";
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");      // HTTP/1.0

echo "<html>\n";
echo "<head>\n";
if ($user_level < 9) 
	{
	echo "You do not have permission to be here.";
	exit;
	}

echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";	

################################################################################
##### Help
if ($form_to_run == "help")
	{
	echo "<title>"._QXZ("ADMINISTRATION: Bulk Tools")."</title>";
	echo "</head><body bgcolor=white><center>";
	echo "<TABLE WIDTH=98% BGCOLOR=#E6E6E6 cellpadding=2 cellspacing=4><TR><TD ALIGN=LEFT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2><BR>";
	echo "<B>"._QXZ("Bulk Tools Help")."</B><BR><BR>";

	echo "<A NAME=\"DIDADD\"><BR>";
	echo "<B>"._QXZ("DID Bulk Copy")." -</B> "._QXZ("This will take and insert a list of numbers as inbound DIDs, copying the settings specified in the selected DID. DIDs should be between 2 and 20 digits in length. A duplicate check will be performed.")."";
	echo "<BR><BR>";
	
	echo "<A NAME=\"DIDDELETE\"><BR>";
	echo "<B>"._QXZ("DID Bulk Delete")." -</B> "._QXZ("This will delete the selected DIDs from your system. You cannot delete the -default- DID. Doing so will break several parts of the system.");
	echo "<BR><BR>";
	
	echo "<A NAME=\"USERADD\"><BR>";
	echo "<B>"._QXZ("User Bulk Copy")." -</B> "._QXZ("This will copy a sequential number of users beginning with the User Start ID and ending with the User Stop ID using the settings found in the source user. ID must be at least 2 characters in length and no more than 8. A duplicate check will be performed. Only numerals are allowed and ID cannot begin with a zero. The User Number, Password and Full Name will all be the same. Force PW Change will work for admin level users and is recommend for security reasons.");
	echo "<BR><BR>";
	
	echo "<A NAME=\"USERDELETE\"><BR>";
	echo "<B>"._QXZ("User Bulk Delete")." -</B> "._QXZ("This will delete the selected users from your system. For obvious reasons, you cannot delete the user you are logged in as. You can also not delete any users that are actively logged in as agents.");
	echo "<BR><BR>";
	
	echo "<A NAME=\"ACCIDADD\"><BR>";
	echo "<B>"._QXZ("CID Groups and AC-CID Bulk Add")." -</B> "._QXZ("This allows you to paste in a listing of CIDs to insert into a campaign AC-CID list or CID Group list. There are two methods to choose from: <ul> <li><b>STATE LOOKUP</b> - This will take a list of CIDs and insert them as Area Code Caller IDs into the selected campaign. The area code lookup is designed to work only with numbers in the North American Numbering Plan as it uses the first three digits of each CID. The description will automatically be filled with the appropriate US state abbreviation corresponding to the given CIDs 3-digit area code. <li><b>CSV</b> - This will take a comma separated list in the format of  NPA,CID,DESCRIPTION,ACTIVE and insert each line into the specified campaigns AC-CID list using the supplied values. For example, given a listing of  813,7271234567,Florida,Y  will result in an AC-CID entry for area code 813 using the CID 7271234567 and a description of -Florida- with the status being active. Descriptions are limited to 50 characters. For CID Groups of a STATE type, use this format for CSV-  STATE,CID,DESCRIPTION,ACTIVE. The -ACTIVE- column will only be used if the Active select list above is set to -Input-.<li><b>STATE FILL</b> - Similar to the STATE LOOKUP method above, this will take a list of CIDs and insert them as AC-CIDs in the specified campaign but will also make an entry for each area code in a state based off the state the CID is in. For example, if given the CID 7271234567, 19 CID entries will be created with the same CID, one each for every area code in Florida. If you are inserting into a CID Group that is a STATE type, then you will not want to use the STATE FILL method.</ul> CIDs must be between 6 and 20 digits in length and only digits 0-9 are allowed. Optionally, you can have the AC-CIDs set to active upon insertion.");
	echo "<BR><BR>";
	
	echo "<A NAME=\"ACCIDDELETE\"><BR>";
	echo "<B>"._QXZ("CID Groups and AC-CID Bulk Delete")." -</B> "._QXZ("This will delete the Areacode Caller ID entries you select from the next screen based on the campaign or CID group you select here. Setting Clear All CIDs to YES will bypass the AC-CID selection and wipe all AC-CIDs for the selected campaign or CID group.");
	echo "<BR><BR></TD></TR></TABLE></BODY><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR><BR></HTML>";
	
	exit;
	}
	
##### BEGIN Set variables to make header show properly #####
$ADD =  '999998';
$hh =       'admin';
$LOGast_admin_access = '1';
$SSoutbound_autodial_active = '1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$admin_color =    '#FFFF99';
$admin_font =      'BLACK';
$admin_color =    '#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");
echo "<title>"._QXZ("ADMINISTRATION: Bulk Tools")."</title>\n";
echo "<table width=$page_width bgcolor=#". $SSstd_row2_background ." cellpadding=2 cellspacing=0>\n";
echo "<tr bgcolor='#". $SSstd_row2_background ."'>\n";
echo "<td align=left>\n";
echo "<font face='ARIAL,HELVETICA' size=2>\n";
echo "<b> &nbsp; "._QXZ("Bulk Tools")." </b>\n";
echo "</font>\n";
echo "</td>\n";
echo "<td align=right><font face='ARIAL,HELVETICA' size=2><b> &nbsp; </td>\n";
echo "</tr>\n";
echo "<tr bgcolor='#". $SSframe_background ."'><td align=left colspan=2><font face='ARIAL,HELVETICA' color=black size=3> &nbsp; \n";

################################################################################
##### CONFIRM AC-CID add
if ($form_to_run == "ACCID")
	{
	if ($ACCIDcampaign=="BLANK")
		{
		echo _QXZ("Go back, you did not specify a campaign or CID group.")."\n";
		exit;
		}
	if ($CIDcheck=="BLANK")
		{
		echo _QXZ("Go back, you did not specify any CIDs or there was something wrong with them.")."\n";
		exit;
		}
	if (count($ACCIDto_insert_raw) > $INSERTmax_limit)
		{
		echo _QXZ("This tool has a limit of ")."$INSERTmax_limit"._QXZ(" items. You are trying to insert ") . count($ACCIDto_insert_raw) ._QXZ(". Please go back and make adjustments.")."\n";
		exit;
		}

	$CGT = 'AREACODE';
	$SQL="SELECT cid_group_type FROM vicidial_cid_groups WHERE cid_group_id='$ACCIDcampaign';";
	if ($DB) {echo "$SQL|";}
	$SQL_rslt = mysql_to_mysqli($SQL, $link);
	$cgid_count = mysqli_num_rows($SQL_rslt);
	if ($cgid_count > 0)
		{
		$row = mysqli_fetch_row($SQL_rslt);
		$CGT = $row[0];
		}

	$areacode = array();
	# If using the STATE FILL method, build out the array of ACs and CIDs for each represented state.
	if ($ACCIDmethod == "STATEFILL") 
		{
		$STATEFILLcids = array();
		$STATEFILLareacodes = array();
		$i=0;
		$j=0; # Counts total ACCID to be inserted
		while ($i < count($ACCIDto_insert_raw))
			{
			$STATEFILLareacode[$i] = substr($ACCIDto_insert_raw[$i], 0, 3);
			$SQL = "SELECT state FROM vicidial_phone_codes WHERE country IN('USA','CAN') AND areacode='$STATEFILLareacode[$i]';";
			if ($DB) {echo "$SQL|";}
			$SQL_rslt = mysql_to_mysqli($SQL, $link);
			$state = mysqli_fetch_row($SQL_rslt);
			
			$SQL = "SELECT areacode FROM vicidial_phone_codes WHERE country IN('USA','CAN') AND state='$state[0]';";
			if ($DB) {echo "$SQL|";}
			$SQL_rslt = mysql_to_mysqli($SQL, $link);
			$areacode_count = mysqli_num_rows($SQL_rslt);
			 
			$k = 0;
			while ($k < $areacode_count)
				{
				$row = mysqli_fetch_row($SQL_rslt);
				$areacode[$j] = $row[0];
				$STATEFILLcids[$j] = $ACCIDto_insert_raw[$i];
				$j++;
				$k++;		
				}
			$i++;
			}
		$i=0;	
		while ($i < count($STATEFILLcids))
			{
			$ACCIDto_insert_raw[$i] = $STATEFILLcids[$i];
			$i++;
			}
		}

	#Check for duplicates
	$ACCIDduplicate = array();
	$ACCIDinserted = array();
	$ACCIDareacode = array();
	$ACCIDbadlen = array();
	$i=0; #loop counter
	$j=0; #duplicate counter
	$k=0; #insert counter
	$l=0; #bad length counter
	while ($i < count($ACCIDto_insert_raw))
		{
		if ($ACCIDmethod == "CID") 
			{
			$areacode[$i] = substr($ACCIDto_insert_raw[$i], 0, 3);
			if ($CGT == 'STATE')
				{
				$SQL = "SELECT state FROM vicidial_phone_codes WHERE country IN('USA','CAN') AND areacode='$areacode[$i]';";
				if ($DB) {echo "$SQL|";}
				$SQL_rslt = mysql_to_mysqli($SQL, $link);
				$row=mysqli_fetch_row($SQL_rslt);
				$areacode[$i] = $row[0];
				}
			if ($CGT == 'NONE')
				{
				$areacode[$i] = 'NONE';
				}
			}
		if ($ACCIDmethod == "CSV") {$areacode[$i] = $ACCIDareacode_raw[$i];}
		$SQL= "SELECT outbound_cid FROM vicidial_campaign_cid_areacodes WHERE outbound_cid='$ACCIDto_insert_raw[$i]' AND areacode='$areacode[$i]' AND campaign_id='$ACCIDcampaign';";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt=mysql_to_mysqli($SQL, $link);
		$row = mysqli_fetch_row($SQL_rslt);
		if ($row[0] == $ACCIDto_insert_raw[$i])
			{
			$ACCIDduplicate[$j] = $ACCIDto_insert_raw[$i];
			$j++;
			}
		elseif (strlen($ACCIDto_insert_raw[$i]) < 6 || strlen($ACCIDto_insert_raw[$i]) > 20)
			{
			$ACCIDbadlen[$l] = $ACCIDto_insert_raw[$i];
			$l++;
			}
		else
			{
			$ACCIDto_insert[$k] = $ACCIDto_insert_raw[$i];
			$ACCIDareacode[$k] = $areacode[$i];
			$k++;
			}			
		$i++;
		}
	if (empty($ACCIDto_insert[0]))
		{
		echo "<br> <b>"._QXZ("Go back, nothing is going to be created.")."</b> ";
		echo "<br> "._QXZ("The following CID Groups or AC-CIDs are duplicates and will not be created").": ";
		if (empty($ACCIDduplicate[0])) {echo "<br> "._QXZ("NONE");}
		$i = 0;
		while ($i < count($ACCIDduplicate))
			{
			echo "<br> $ACCIDduplicate[$i]";
			$i++;
			}
		echo "<br> "._QXZ("The following CID Groups or AC-CIDs are of invalid length and will not be created").": ";
		if (empty($ACCIDbadlen[0])) {echo "<br> "._QXZ("NONE");}
		$i = 0;
		while ($i < count($ACCIDbadlen))
			{
			echo "<br> $ACCIDbadlen[$i]";
			$i++;
			}
		exit;
		}
	else
		{
		echo _QXZ("ATTENTION, You are about to add the following AC-CIDs to this campaign").": $ACCIDcampaign";
		$i = 0;
		while ($i < count($ACCIDto_insert))
			{
			echo "<br> $ACCIDto_insert[$i]";
			$i++;
			}
		echo "<br> "._QXZ("The following AC-CIDs are duplicates and will not be created").": ";
		if (empty($ACCIDduplicate[0])) {echo "<br> "._QXZ("NONE");}
		$i = 0;
		while ($i < count($ACCIDduplicate))
			{
			echo "<br> $ACCIDduplicate[$i]";
			$i++;
			}
		echo "<br> "._QXZ("The following AC-CIDs are of invalid length and will not be created").": ";
		if (empty($ACCIDbadlen[0])) {echo "<br> "._QXZ("NONE");}
		$i = 0;
		while ($i < count($ACCIDbadlen))
			{
			echo "<br> $ACCIDbadlen[$i]";
			$i++;
			}
		}
	$ACCIDto_insert = serialize($ACCIDto_insert);
	$ACCIDareacode = serialize($ACCIDareacode);
	if ($ACCIDmethod=="CSV") {$ACCIDdescription_raw = serialize($ACCIDdescription_raw);}
	if ($ACCIDmethod=="CSV") {$ACCIDactiveinput_raw = serialize($ACCIDactiveinput_raw);}
	echo "<html><form action=$PHP_SELF method=POST>";
	echo "<input type=hidden name=form_to_run value='ACCIDconfirmed'>";
	echo "<input type=hidden name=DB value='$DB'>";
	echo "<input type=hidden name=ACCIDto_insert value='$ACCIDto_insert'>";
	echo "<input type=hidden name=ACCIDcampaign value='$ACCIDcampaign'>";
	echo "<input type=hidden name=ACCIDareacode value='$ACCIDareacode'>";
	echo "<input type=hidden name=ACCIDdescription value='$ACCIDdescription_raw'>";
	echo "<input type=hidden name=ACCIDactiveinput value='$ACCIDactiveinput_raw'>";
	echo "<input type=hidden name=ACCIDactive value='$ACCIDactive'>";
	echo "<input type=hidden name=ACCIDmethod value='$ACCIDmethod'>";
	echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='CONFIRM'></td></tr>\n";
	echo "</table></center></form>\n";
	echo "</html>";
	}
	
	
################################################################################
##### PROCESS AC-CID add
elseif ($form_to_run == "ACCIDconfirmed")
	{
	$CGT = 'AREACODE';
	$SQL="SELECT cid_group_type FROM vicidial_cid_groups WHERE cid_group_id='$ACCIDcampaign';";
	if ($DB) {echo "$SQL|";}
	$SQL_rslt = mysql_to_mysqli($SQL, $link);
	$cgid_count = mysqli_num_rows($SQL_rslt);
	if ($cgid_count > 0)
		{
		$row = mysqli_fetch_row($SQL_rslt);
		$CGT = $row[0];
		}

	$ACCIDto_insert_CONFIRMED = unserialize($ACCIDto_insert_CONFIRMED);
	$ACCIDareacode = unserialize($ACCIDareacode);
	if ($ACCIDmethod=="CSV")
		{
		$ACCIDdescription = unserialize($ACCIDdescription);
		$ACCIDactiveinput = unserialize($ACCIDactiveinput);
		}
	else
		{
		$ACCIDdescription = array();
		$i = 0;
		while ($i < count($ACCIDto_insert_CONFIRMED))
			{
			$ACCIDareacode[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $ACCIDareacode[$i]);
			if (!preg_match("/[A-Z]/i",$ACCIDareacode[$i]))
				{
				$SQL="SELECT state FROM vicidial_phone_codes WHERE areacode='$ACCIDareacode[$i]';";
				$SQL_rslt = mysql_to_mysqli($SQL, $link);
				$row = mysqli_fetch_row($SQL_rslt);
				if ( $row[0] == null ) #Put something in if NULL because areacode.vicidial_campaign_cid_areacodes cannot be NULL		
					{
					$ACCIDdescription[$i] = " ";
					}
				else
					{
					$ACCIDdescription[$i] = $row[0];
					}
				}
			else
				{$ACCIDdescription[$i] = $ACCIDareacode[$i];}
			$i++;
			}
		}

	### Divide total AC-CIDs into groups
	$INSERTtotal = count($ACCIDto_insert_CONFIRMED);
	$INSERTgroup_counter = 0;
	$INSERTloopflag = "TRUE";
	if ($INSERTtotal == $INSERTgroup_limit)
		{
		$INSERTgroup_counter = 0;
		$INSERTremainder = $INSERTtotal;
		}
	else
		{
		while ($INSERTloopflag == "TRUE")
			{
			$INSERTdifference = $INSERTtotal - $INSERTgroup_limit;
			if ($INSERTdifference > $INSERTgroup_limit) #If the difference is bigger then we still have more groups to divide out
					{
					$INSERTtotal = $INSERTdifference;
					$INSERTgroup_counter++;
					}
			if (($INSERTdifference >= 0) && ($INSERTdifference <= $INSERTgroup_limit)) # If the difference is between 0 and the group limit then we've reached the end
					{
					$INSERTgroup_counter++;
					$INSERTremainder = $INSERTdifference;
					$INSERTloopflag="FALSE";
					}
			if ($INSERTdifference < 0) # If the difference is negative then there's only 1 group
				{
				$INSERTgroup_counter = 0;
				$INSERTremainder = $INSERTtotal;
				$INSERTloopflag="FALSE";
				}
			}
		}	

	### Loop through AC-CIDs and insert in groups
	$INSERTindex = 0;
	$INSERTloopflag = "TRUE";
	$INSERTsqlLOG='';
	while ($INSERTloopflag == "TRUE")
		{
		$tempACCIDactive = $ACCIDactive;
		if ($ACCIDactive=='F') 
			{
			if (strlen($ACCIDactiveinput[$INSERTindex]) > 0) {$tempACCIDactive = $ACCIDactiveinput[$INSERTindex];}
			else {$tempACCIDactive='N';}
			}
		$ACCIDareacode[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDareacode[$INSERTindex]);
		$ACCIDto_insert_CONFIRMED[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDto_insert_CONFIRMED[$INSERTindex]);
		$ACCIDdescription[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDdescription[$INSERTindex]);
		$tempACCIDactive = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $tempACCIDactive);

		if ($DB) {echo "|ACCIDactive: $ACCIDactive|$ACCIDactiveinput[$INSERTindex]|$tempACCIDactive|";}

		$SQL = "INSERT IGNORE INTO vicidial_campaign_cid_areacodes (campaign_id,areacode,outbound_cid,active,cid_description) VALUES ('$ACCIDcampaign','$ACCIDareacode[$INSERTindex]','$ACCIDto_insert_CONFIRMED[$INSERTindex]','$tempACCIDactive','$ACCIDdescription[$INSERTindex]')";
		$INSERTindex++;
		if ($INSERTgroup_counter > 0)
			{
			$i = 1;
			while ($i < $INSERTgroup_limit)
				{
				$tempACCIDactive = $ACCIDactive;
				if ($ACCIDactive=='F') 
					{
					if (strlen($ACCIDactiveinput[$INSERTindex]) > 0) {$tempACCIDactive = $ACCIDactiveinput[$INSERTindex];}
					else {$tempACCIDactive='N';}
					}
				$ACCIDareacode[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDareacode[$INSERTindex]);
				$ACCIDto_insert_CONFIRMED[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDto_insert_CONFIRMED[$INSERTindex]);
				$ACCIDdescription[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDdescription[$INSERTindex]);
				$tempACCIDactive = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $tempACCIDactive);

				if ($DB) {echo "|ACCIDactive: $ACCIDactive|$ACCIDactiveinput[$INSERTindex]|$tempACCIDactive|";}
				$SQL.= ",('" . $ACCIDcampaign . "','" . $ACCIDareacode[$INSERTindex] . "','" . $ACCIDto_insert_CONFIRMED[$INSERTindex] . "','" . $tempACCIDactive . "','" . $ACCIDdescription[$INSERTindex] . "')";
				$SQL_sentence.= " |$ACCIDareacode[$INSERTindex]|$ACCIDto_insert_CONFIRMED[$INSERTindex]|$ACCIDdescription[$INSERTindex]|$tempACCIDactive";
				$i++;
				$INSERTindex++;
				}	
			$INSERTgroup_counter--;
			}
		else 
			{
			$i = 1;
			while ($i < $INSERTremainder)
				{
				$tempACCIDactive = $ACCIDactive;
				if ($ACCIDactive=='F') 
					{
					if (strlen($ACCIDactiveinput[$INSERTindex]) > 0) {$tempACCIDactive = $ACCIDactiveinput[$INSERTindex];}
					else {$tempACCIDactive='N';}
					}
				$ACCIDareacode[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDareacode[$INSERTindex]);
				$ACCIDto_insert_CONFIRMED[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDto_insert_CONFIRMED[$INSERTindex]);
				$ACCIDdescription[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDdescription[$INSERTindex]);
				$tempACCIDactive = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $tempACCIDactive);

				if ($DB) {echo "|ACCIDactive: $ACCIDactive|$ACCIDactiveinput[$INSERTindex]|$tempACCIDactive|";}
				$SQL.= ",('" . $ACCIDcampaign . "','" . $ACCIDareacode[$INSERTindex] . "','" . $ACCIDto_insert_CONFIRMED[$INSERTindex] . "','" . $tempACCIDactive . "','" . $ACCIDdescription[$INSERTindex] . "')";
				$SQL_sentence.= " |$ACCIDareacode[$INSERTindex]|$ACCIDto_insert_CONFIRMED[$INSERTindex]|$ACCIDdescription[$INSERTindex]|$tempACCIDactive";
				$i++;
				$INSERTindex++;
				}
			$INSERTloopflag="FALSE";
			}
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$INSERTsqlLOG.="$SQL|";
		}

	### Log our stuff
	$SQL_sentence = "Method: $ACCIDmethod. AC-CIDs inserted into campaign $ACCIDcampaign: " . $SQL_sentence;
	$SQL_log = $INSERTsqlLOG;
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='CAMPAIGN_AC-CID', event_type='ADD', record_id='$ACCIDcampaign', event_code='ADMIN ADD BULK CAMPAIGN AC-CID', event_sql=\"$SQL_log\", event_notes='$SQL_sentence';";
	if ($DB) {echo "$admin_log_stmt|";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
	
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	echo "<br>AC-CIDs added.";
	echo "\n\n\n<br>\n"._QXZ("runtime").": $RUNtime "._QXZ("seconds");
	echo "<br><br><a href=\"admin_bulk_tools.php\">"._QXZ("Go back to tools.")."</a>";		
	}

################################################################################
##### SELECT AC-CID delete

elseif ($form_to_run == "ACCIDDELETEselect")
	{
	if ($ACCIDdelete_campaign=="BLANK")
		{
		echo _QXZ("Go back, you did not select a campaign or CID group.")."\n";
		exit;
		}
	echo "<html><form action=$PHP_SELF method=POST>";
	echo "<input type=hidden name=form_to_run value='ACCIDDELETEconfirm'>";
	echo "<input type=hidden name=DB value='$DB'>";
	echo "<input type=hidden name=ACCIDdelete_campaign value='$ACCIDdelete_campaign'>";
	echo "<center><table width=$section_width cellspacing='3'>";
	echo "<col width=50%><col width=50%>";
	echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("Campaign")." $ACCIDdelete_campaign "._QXZ("AC-CID Bulk Delete")."</b></font></td></tr>\n";
	echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("AC-CIDs to delete").": </td><td align=left>\n";
		
	$ACCIDdelete_from = array();
	$SQL="SELECT areacode,outbound_cid FROM vicidial_campaign_cid_areacodes WHERE campaign_id = '$ACCIDdelete_campaign' ORDER BY outbound_cid ASC;";
	if ($DB) {echo "$SQL|";}
	$SQL_rslt = mysql_to_mysqli($SQL, $link);
	$cid_count = mysqli_num_rows($SQL_rslt);
	$i = 0;
	while ($i < $cid_count)
		{
		$row = mysqli_fetch_row($SQL_rslt);
		$ACCIDto_delete_ac[$i] = $row[0];
		$ACCIDto_delete[$i] = $row[1];
		$i++;
		}
				
	echo "<select multiple size=10 name='ACCIDdelete_from[]'>\n";
	$i = 0;
	while ( $i < $cid_count )
		{
		echo "<option value='$ACCIDto_delete_ac[$i]x$ACCIDto_delete[$i]'>$ACCIDto_delete_ac[$i] - $ACCIDto_delete[$i]</option>\n";
		$i++;
		}
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='"._QXZ("Submit")."'></td></tr>\n";

	echo "</table></center></form>\n";
	echo "</html>";
	}


################################################################################
##### CONFIRM AC-CID delete

elseif ($form_to_run == "ACCIDDELETEconfirm" || $form_to_run == "ACCIDDELETEconfirmTB")
	{
	if ($ACCIDclear_all == "Y")
		{
		if ($ACCIDdelete_campaign=="BLANK")
			{
			echo _QXZ("Go back, you did not select a campaign or CID group").".\n";
			exit;
			}
		echo "<br><font color=red><b><center>"._QXZ("WANRING! You are about to remove all AC-CID entries for campaign")." $ACCIDdelete_campaign "._QXZ("WARNING!")."</center></b></font><br><br>";
		echo "<html><form action=$PHP_SELF method=POST>";
		echo "<input type=hidden name=form_to_run value='ACCIDDELETEconfirmed'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<input type=hidden name=ACCIDdelete_campaign value='$ACCIDdelete_campaign'>";
		echo "<input type=hidden name=ACCIDclear_all_CONFIRMED value='$ACCIDclear_all'>";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='CONFIRM'></td></tr>\n";
		echo "</table></center></form>\n";
		echo "</html>";
		}
	else
		{
		if ($ACCIDdelete_from=="BLANK")
			{
			echo _QXZ("Go back, you did not specify any AC-CIDs to delete.")."\n";
			exit;
			}
		$SQL="SELECT COUNT(*) FROM vicidial_campaign_cid_areacodes where campaign_id = '$ACCIDdelete_campaign';";
		$SQL_rslt=mysql_to_mysqli($SQL, $link);
		$row=mysqli_fetch_row($SQL_rslt);
		if ($row[0] == count($ACCIDdelete_from))
			{
			echo "<b><font color='red'>"._QXZ("WARNING!!!")." <br>";
			echo _QXZ("REALLY DELETE ALL AC-CIDs?!")."</font></b><br>";
			}
		echo "<b> "._QXZ("WARNING: The following AC-CIDs will be deleted!!!")."</b>";
		$i = 0;
		while ($i < count($ACCIDdelete_from))
			{
			echo "<br> $ACCIDdelete_from[$i]";
			$i++;
			}
		$ACCIDdelete_from = serialize($ACCIDdelete_from);
		echo "<html><form action=$PHP_SELF method=POST>";
		if ( $form_to_run == "ACCIDDELETEconfirmTB" ) {
			echo "<input type=hidden name=form_to_run value='ACCIDDELETEconfirmedTB'>";
		}
		else {
			echo "<input type=hidden name=form_to_run value='ACCIDDELETEconfirmed'>";
		}
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<input type=hidden name=ACCIDdelete_from_CONFIRMED value='$ACCIDdelete_from'>";
		echo "<input type=hidden name=ACCIDdelete_campaign value='$ACCIDdelete_campaign'>";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='CONFIRM'></td></tr>\n";
		echo "</table></center></form>\n";
		echo "</html>";
		}
	}


################################################################################
##### PROCESS AC-CID delete
elseif ($form_to_run == "ACCIDDELETEconfirmed" || $form_to_run == "ACCIDDELETEconfirmedTB")
	{
	if ($ACCIDclear_all_CONFIRMED == "Y")
		{
		$SQL = "DELETE FROM vicidial_campaign_cid_areacodes WHERE campaign_id='$ACCIDdelete_campaign';";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		
		#Log our stuff
		$SQL_sentence = " ";
		$SQL_log = "$SQL|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='CAMPAIGN_AC-CID', event_type='DELETE', record_id='$ACCIDdelete_campaign', event_code='ADMIN DELETE BULK DID', event_sql=\"$SQL_log\", event_notes='$SQL_sentence';";
		if ($DB) {echo "$admin_log_stmt|";}
		$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
		
		echo "<br>"._QXZ("AC-CIDs have been deleted").".";
		echo "<br><a href=\"admin_bulk_tools.php\">"._QXZ("Go back to tools").".</a>";		
		}
	else
		{
		if ($form_to_run == "ACCIDDELETEconfirmedTB" ) {
			$DELETEsqlLOG='';
			$ACCIDdelete_from_CONFIRMED = unserialize($ACCIDdelete_from_CONFIRMED); # Go through the data and make a new array then break off the AC and CID
			$SQL_sentence = "$ACCIDdelete_from_CONFIRMED[0] |";
			$i = 1; # loop counter
			while ($i < count($ACCIDdelete_from_CONFIRMED))
				{
				$ACCIDdelete_from_CONFIRMED[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDdelete_from_CONFIRMED[$i]);
				$SQL_sentence.= "$ACCIDdelete_from_CONFIRMED[$i] |";
				$i++;
				}
			$i = 0;
			while ($i < count($ACCIDdelete_from_CONFIRMED))
				{
				if ( $ACCIDdelete_campaign == '---ALL---' ) {
					$SQL = "DELETE FROM vicidial_campaign_cid_areacodes WHERE outbound_cid='$ACCIDdelete_from_CONFIRMED[$i]';";
				}
				else {
					$SQL = "DELETE FROM vicidial_campaign_cid_areacodes WHERE campaign_id='$ACCIDdelete_campaign' AND outbound_cid='$ACCIDdelete_from_CONFIRMED[$i]';";
				}
				$i++;
				if ($DB) {echo "$SQL|";}
				$SQL_rslt = mysql_to_mysqli($SQL, $link);
				$DELETEsqlLOG .= "$SQL|";
				}
		
		}
		else {
			$DELETEsqlLOG='';
			$ACCIDdelete_from_CONFIRMED = unserialize($ACCIDdelete_from_CONFIRMED); # Go through the data and make a new array then break off the AC and CID
			$ACCIDdelete_from_CONFIRMED = implode("x",$ACCIDdelete_from_CONFIRMED);
			$ACCIDdelete_from_CONFIRMED = explode("x",$ACCIDdelete_from_CONFIRMED);
			$ACCIDdelete_areacode = array();
			$ACCIDdelete_cid = array();
			$ACCIDdelete_areacode[0] = $ACCIDdelete_from_CONFIRMED[0];
			$SQL_sentence = "$ACCIDdelete_areacode[0] |";
			$i = 1; # loop counter
			$j = 0; # CID index counter
			$k = 1; # areacode index counter
			while ($i < count($ACCIDdelete_from_CONFIRMED))
				{
				$ACCIDdelete_from_CONFIRMED[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDdelete_from_CONFIRMED[$i]);
				if ($i & 1)
					{
					$ACCIDdelete_cid[$j] = $ACCIDdelete_from_CONFIRMED[$i];
					$SQL_sentence.= "$ACCIDdelete_cid[$j] |";
					$j++;
					}
				else
					{
					$ACCIDdelete_areacode[$j] = $ACCIDdelete_from_CONFIRMED[$i];
					$SQL_sentence.= "$ACCIDdelete_areacode[$k] |";
					$k++;
					}
				$i++;
				}

			$i = 0;
			while ($i < count($ACCIDdelete_areacode))
				{
				$ACCIDdelete_cid[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDdelete_cid[$i]);
				$ACCIDdelete_areacode[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $ACCIDdelete_areacode[$i]);

				$SQL = "DELETE FROM vicidial_campaign_cid_areacodes WHERE campaign_id='$ACCIDdelete_campaign' AND outbound_cid='$ACCIDdelete_cid[$i]' AND areacode='$ACCIDdelete_areacode[$i]';";
				$i++;
				if ($DB) {echo "$SQL|";}
				$SQL_rslt = mysql_to_mysqli($SQL, $link);
				$DELETEsqlLOG .= "$SQL|";
				}				
		}
		#Log our stuff
		$SQL_sentence = "ACCID entries removed from campaign $ACCIDdelete_campaign: " . $SQL_sentence;
		$SQL = "DELETE FROM vicidial_campaign_cid_areacodes WHERE campaign_id='$ACCIDdelete_campaign' AND outbound_cid='$ACCIDdelete_cid[0]' AND areacode='$ACCIDdelete_areacode[0]';";
		$SQL_log = "$DELETEsqlLOG";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='CAMPAIGN_AC-CID', event_type='DELETE', record_id='0', event_code='ADMIN DELETE BULK ACCID', event_sql=\"$SQL_log\", event_notes='$SQL_sentence';";
		if ($DB) {echo "$admin_log_stmt|";}
		$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
		
		echo "<br>"._QXZ("AC-CIDs have been deleted").".";
		echo "<br><a href=\"admin_bulk_tools.php\">"._QXZ("Go back to tools").".</a>";
		}
	}


################################################################################
##### CONFIRM DID add
elseif ($form_to_run == "BULKDIDS")
	{
	if ($DIDcopy_from=="BLANK")
		{
		echo _QXZ("Go back, you did not specify a source DID.")."\n";
		exit;
		}
	if ($DIDcheck=="BLANK")
		{
		echo _QXZ("Go back, you did not specify any DIDs or there was something wrong with them.")."\n";
		exit;
		}
	if (count($DIDto_insert_raw) > $INSERTmax_limit)
		{
		echo _QXZ("This tool has a limit of")." $INSERTmax_limit"._QXZ(" items. You are trying to insert")." " . count($DIDto_insert_raw) ._QXZ(". Please go back and make adjustments.")."\n";
		exit;
		}
		$DIDduplicate = array();
		$DIDbadlen = array();
		$DIDinserted = array();
		$i=0; #loop counter
		$j=0; #duplicate counter
		$k=0; #insert counter
		$l=0; #bad length counter
		while ($i < count($DIDto_insert_raw))
			{
			$SQL= "SELECT did_pattern FROM vicidial_inbound_dids WHERE did_pattern='$DIDto_insert_raw[$i]';";
			if ($DB) {echo "$SQL|";}
			$SQL_rslt=mysql_to_mysqli($SQL, $link);
			$row = mysqli_fetch_row($SQL_rslt);
			if ($row[0] == $DIDto_insert_raw[$i])
				{
				$DIDduplicate[$j] = $DIDto_insert_raw[$i];
				$j++;
				}
			elseif (strlen($DIDto_insert_raw[$i]) < 2 || strlen($DIDto_insert_raw[$i]) > 20)
				{
				$DIDbadlen[$l] = $DIDto_insert_raw[$i];
				$l++;
				}
			else
				{
				$DIDto_insert[$k] = $DIDto_insert_raw[$i];
				$k++;
				}
			$i++;
			}
		if (empty($DIDto_insert[0]))
			{
			echo "<br> <b>"._QXZ("Go back, nothing is going to be created.")."</b> ";
			echo "<br> "._QXZ("The following DIDs are duplicates and will not be created").": ";
			if (empty($DIDduplicate[0])) {echo "<br> "._QXZ("NONE");}
			$i = 0;
			while ($i < count($DIDduplicate))
				{
				echo "<br> $DIDduplicate[$i]";
				$i++;
				}
			echo "<br> "._QXZ("The following DIDs are of invalid length and will not be created").": ";
			if (empty($DIDbadlen[0])) {echo "<br> "._QXZ("NONE");}
			$i = 0;
			while ($i < count($DIDbadlen))
				{
				echo "<br> $DIDbadlen[$i]";
				$i++;
				}
			exit;
			}
		else
			{
			echo _QXZ("ATTENTION: You are about to add the following DIDs using the settings in DID")." $DIDcopy_from :";
			$i = 0;
			while ($i < count($DIDto_insert))
				{
				echo "<br> $DIDto_insert[$i]";
				$i++;
				}
			echo "<br> "._QXZ("The following DIDs are duplicates and will not be created").": ";
			if (empty($DIDduplicate[0])) {echo "<br> "._QXZ("NONE");}
			$i = 0;
			while ($i < count($DIDduplicate))
				{
				echo "<br> $DIDduplicate[$i]";
				$i++;
				}
			echo "<br> "._QXZ("The following DIDs are of invalid length and will not be created").": ";
			if (empty($DIDbadlen[0])) {echo "<br> "._QXZ("NONE");}
			$i = 0;
			while ($i < count($DIDbadlen))
				{
				echo "<br> $DIDbadlen[$i]";
				$i++;
				}
			}
		
		$DIDto_insert = serialize($DIDto_insert);
		echo "<html><form action=$PHP_SELF method=POST>";
		echo "<input type=hidden name=form_to_run value='DIDADDconfirmed'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<input type=hidden name=DIDto_insert_CONFIRMED value='$DIDto_insert'>";
		echo "<input type=hidden name=DIDcopy_from value='$DIDcopy_from'>";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='CONFIRM'></td></tr>\n";
		echo "</table></center></form>\n";
		echo "</html>";
	}
	
	
################################################################################
##### PROCESS DID add
elseif ($form_to_run == "DIDADDconfirmed")
	{
	$DIDto_insert_CONFIRMED = unserialize($DIDto_insert_CONFIRMED);
	$SQL="SELECT did_id,did_pattern,did_description,did_active,did_route,extension,exten_context,voicemail_ext,phone,server_ip,user,user_unavailable_action,user_route_settings_ingroup,group_id,call_handle_method,agent_search_method,list_id,campaign_id,phone_code,menu_id,record_call,filter_inbound_number,filter_phone_group_id,filter_url,filter_action,filter_extension,filter_exten_context,filter_voicemail_ext,filter_phone,filter_server_ip,filter_user,filter_user_unavailable_action,filter_user_route_settings_ingroup,filter_group_id,filter_call_handle_method,filter_agent_search_method,filter_list_id,filter_campaign_id,filter_phone_code,filter_menu_id,filter_clean_cid_number,custom_one,custom_two,custom_three,custom_four,custom_five,user_group,filter_dnc_campaign,filter_url_did_redirect,no_agent_ingroup_redirect,no_agent_ingroup_id,no_agent_ingroup_extension,pre_filter_phone_group_id,pre_filter_extension,entry_list_id,filter_entry_list_id,max_queue_ingroup_calls,max_queue_ingroup_id,max_queue_ingroup_extension,did_carrier_description FROM vicidial_inbound_dids WHERE did_pattern=\"$DIDcopy_from\";";
	$SQL_rslt = mysql_to_mysqli($SQL, $link);
	$row = mysqli_fetch_row($SQL_rslt);
	$did_id								= $row[0];
	$did_pattern						= $row[1];
	$did_description					= $row[2];
	$did_active							= $row[3];
	$did_route							= $row[4];
	$extension							= $row[5];
	$exten_context						= $row[6];
	$voicemail_ext						= $row[7];
	$phone								= $row[8];
	$server_ip							= $row[9];
	$user								= $row[10];
	$user_unavailable_action			= $row[11];
	$user_route_settings_ingroup		= $row[12];
	$group_id							= $row[13];
	$call_handle_method					= $row[14];
	$agent_search_method				= $row[15];
	$list_id							= $row[16];
	$ACCIDcampaign_id					= $row[17];
	$phone_code							= $row[18];
	$menu_id							= $row[19];
	$record_call						= $row[20];
	$filter_inbound_number				= $row[21];
	$filter_phone_group_id				= $row[22];
	$filter_url							= $row[23];
	$filter_action						= $row[24];
	$filter_extension					= $row[25];
	$filter_exten_context				= $row[26];
	$filter_voicemail_ext				= $row[27];
	$filter_phone						= $row[28];
	$filter_server_ip					= $row[29];
	$filter_user						= $row[30];
	$filter_user_unavailable_action		= $row[31];
	$filter_user_route_settings_ingroup	= $row[32];
	$filter_group_id					= $row[33];
	$filter_call_handle_method			= $row[34];
	$filter_agent_search_method			= $row[35];
	$filter_list_id						= $row[36];
	$filter_campaign_id					= $row[37];
	$filter_phone_code					= $row[38];
	$filter_menu_id						= $row[39];
	$filter_clean_cid_number			= $row[40];
	$custom_one							= $row[41];
	$custom_two							= $row[42];
	$custom_three						= $row[43];
	$custom_four						= $row[44];
	$custom_five						= $row[45];
	$user_group							= $row[46];
	$filter_dnc_campaign				= $row[47];
	$filter_url_did_redirect			= $row[48];
	$no_agent_ingroup_redirect			= $row[49];
	$no_agent_ingroup_id				= $row[50];
	$no_agent_ingroup_extension			= $row[51];
	$pre_filter_phone_group_id			= $row[52];
	$pre_filter_extension				= $row[53];
	$entry_list_id						= $row[54];
	$filter_entry_list_id				= $row[55];
	$max_queue_ingroup_calls			= $row[56];
	$max_queue_ingroup_id				= $row[57];
	$max_queue_ingroup_extension		= $row[58];
	$did_carrier_description			= $row[59];
			
	### Divide total DIDs into groups
	$INSERTtotal = count($DIDto_insert_CONFIRMED);
	$INSERTgroup_counter = 0;
	$INSERTloopflag = "TRUE";
	if ($INSERTtotal == $INSERTgroup_limit)
		{
		$INSERTgroup_counter = 0;
		$INSERTremainder = $INSERTtotal;
		}
	else
		{
		while ($INSERTloopflag == "TRUE")
			{
			$INSERTdifference = $INSERTtotal - $INSERTgroup_limit;
			if ($INSERTdifference > $INSERTgroup_limit) #If the difference is bigger then we still have more groups to divide out
					{
					$INSERTtotal = $INSERTdifference;
					$INSERTgroup_counter++;
					}
			if (($INSERTdifference >= 0) && ($INSERTdifference <= $INSERTgroup_limit)) # If the difference is between 0 and the group limit then we've reached the end
					{
					$INSERTgroup_counter++;
					$INSERTremainder = $INSERTdifference;
					$INSERTloopflag="FALSE";
					}
			if ($INSERTdifference < 0) # If the difference is negative then there's only 1 group and it's less than the grouping number
				{
				$INSERTgroup_counter = 0;
				$INSERTremainder = $INSERTtotal;
				$INSERTloopflag="FALSE";
				}
			}
		}	

	### Loop through DIDs and insert in groups
	$INSERTindex = 0;
	$INSERTloopflag = "TRUE";
	$INSERTsqlLOG='';
	while ($INSERTloopflag == "TRUE")
		{
		$DIDto_insert_CONFIRMED[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $DIDto_insert_CONFIRMED[$INSERTindex]);

		$SQL = "INSERT IGNORE INTO vicidial_inbound_dids (did_pattern,did_description,did_active,did_route,extension,exten_context,voicemail_ext,phone,server_ip,user,user_unavailable_action,user_route_settings_ingroup,group_id,call_handle_method,agent_search_method,list_id,campaign_id,phone_code,menu_id,record_call,filter_inbound_number,filter_phone_group_id,filter_url,filter_action,filter_extension,filter_exten_context,filter_voicemail_ext,filter_phone,filter_server_ip,filter_user,filter_user_unavailable_action,filter_user_route_settings_ingroup,filter_group_id,filter_call_handle_method,filter_agent_search_method,filter_list_id,filter_campaign_id,filter_phone_code,filter_menu_id,filter_clean_cid_number,custom_one,custom_two,custom_three,custom_four,custom_five,user_group,filter_dnc_campaign,filter_url_did_redirect,no_agent_ingroup_redirect,no_agent_ingroup_id,no_agent_ingroup_extension,pre_filter_phone_group_id,pre_filter_extension,entry_list_id,filter_entry_list_id,max_queue_ingroup_calls,max_queue_ingroup_id,max_queue_ingroup_extension,did_carrier_description) VALUES ('$DIDto_insert_CONFIRMED[$INSERTindex]','$did_description','$did_active','$did_route','$extension','$exten_context','$voicemail_ext','$phone','$server_ip','$user','$user_unavailable_action','$user_route_settings_ingroup','$group_id','$call_handle_method','$agent_search_method','$list_id','$ACCIDcampaign_id','$phone_code','$menu_id','$record_call','$filter_inbound_number','$filter_phone_group_id','$filter_url','$filter_action','$filter_extension','$filter_exten_context','$filter_voicemail_ext','$filter_phone','$filter_server_ip','$filter_user','$filter_user_unavailable_action','$filter_user_route_settings_ingroup','$filter_group_id','$filter_call_handle_method','$filter_agent_search_method','$filter_list_id','$filter_campaign_id','$filter_phone_code','$filter_menu_id','$filter_clean_cid_number','$custom_one','$custom_two','$custom_three','$custom_four','$custom_five','$user_group','$filter_dnc_campaign','$filter_url_did_redirect','$no_agent_ingroup_redirect','$no_agent_ingroup_id','$no_agent_ingroup_extension','$pre_filter_phone_group_id','$pre_filter_extension','$entry_list_id','$filter_entry_list_id','$max_queue_ingroup_calls','$max_queue_ingroup_id','$max_queue_ingroup_extension','$did_carrier_description')";
		$INSERTindex++;
		if ($INSERTgroup_counter > 0)
			{
			$i = 1;
			while ($i < $INSERTgroup_limit)
				{
				$DIDto_insert_CONFIRMED[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $DIDto_insert_CONFIRMED[$INSERTindex]);

				$SQL.= ",('" . $DIDto_insert_CONFIRMED[$INSERTindex] . "','" . $did_description . "','" . $did_active . "','" . $did_route . "','" . $extension . "','" . $exten_context . "','" . $voicemail_ext . "','" . $phone . "','" . $server_ip . "','" . $user . "','" . $user_unavailable_action . "','" . $user_route_settings_ingroup . "','" . $group_id . "','" . $call_handle_method . "','" . $agent_search_method . "','" . $list_id . "','" . $ACCIDcampaign_id . "','" . $phone_code . "','" . $menu_id . "','" . $record_call . "','" . $filter_inbound_number . "','" . $filter_phone_group_id . "','" . $filter_url . "','" . $filter_action . "','" . $filter_extension . "','" . $filter_exten_context . "','" . $filter_voicemail_ext . "','" . $filter_phone . "','" . $filter_server_ip . "','" . $filter_user . "','" . $filter_user_unavailable_action . "','" . $filter_user_route_settings_ingroup . "','" . $filter_group_id . "','" . $filter_call_handle_method . "','" . $filter_agent_search_method . "','" . $filter_list_id . "','" . $filter_campaign_id . "','" . $filter_phone_code . "','" . $filter_menu_id . "','" . $filter_clean_cid_number . "','" . $custom_one . "','" . $custom_two . "','" . $custom_three . "','" . $custom_four . "','" . $custom_five . "','" . $user_group . "','" . $filter_dnc_campaign . "','" . $filter_url_did_redirect . "','" . $no_agent_ingroup_redirect . "','" . $no_agent_ingroup_id . "','" . $no_agent_ingroup_extension . "','" . $pre_filter_phone_group_id . "','" . $pre_filter_extension . "','" . $entry_list_id . "','" . $filter_entry_list_id . "','" . $max_queue_ingroup_calls . "','" . $max_queue_ingroup_id . "','" . $max_queue_ingroup_extension . "','" . $did_carrier_description . "')";
				$SQL_sentence.= " |$DIDto_insert_CONFIRMED[$i]";
				$i++;
				$INSERTindex++;
				}	
			$INSERTgroup_counter--;
			}
		else 
			{
			$i = 1;
			while ($i < $INSERTremainder)
				{
				$DIDto_insert_CONFIRMED[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $DIDto_insert_CONFIRMED[$INSERTindex]);

				$SQL.= ",('" . $DIDto_insert_CONFIRMED[$INSERTindex] . "','" . $did_description . "','" . $did_active . "','" . $did_route . "','" . $extension . "','" . $exten_context . "','" . $voicemail_ext . "','" . $phone . "','" . $server_ip . "','" . $user . "','" . $user_unavailable_action . "','" . $user_route_settings_ingroup . "','" . $group_id . "','" . $call_handle_method . "','" . $agent_search_method . "','" . $list_id . "','" . $ACCIDcampaign_id . "','" . $phone_code . "','" . $menu_id . "','" . $record_call . "','" . $filter_inbound_number . "','" . $filter_phone_group_id . "','" . $filter_url . "','" . $filter_action . "','" . $filter_extension . "','" . $filter_exten_context . "','" . $filter_voicemail_ext . "','" . $filter_phone . "','" . $filter_server_ip . "','" . $filter_user . "','" . $filter_user_unavailable_action . "','" . $filter_user_route_settings_ingroup . "','" . $filter_group_id . "','" . $filter_call_handle_method . "','" . $filter_agent_search_method . "','" . $filter_list_id . "','" . $filter_campaign_id . "','" . $filter_phone_code . "','" . $filter_menu_id . "','" . $filter_clean_cid_number . "','" . $custom_one . "','" . $custom_two . "','" . $custom_three . "','" . $custom_four . "','" . $custom_five . "','" . $user_group . "','" . $filter_dnc_campaign . "','" . $filter_url_did_redirect . "','" . $no_agent_ingroup_redirect . "','" . $no_agent_ingroup_id . "','" . $no_agent_ingroup_extension . "','" . $pre_filter_phone_group_id . "','" . $pre_filter_extension . "','" . $entry_list_id . "','" . $filter_entry_list_id . "','" . $max_queue_ingroup_calls . "','" . $max_queue_ingroup_id . "','" . $max_queue_ingroup_extension . "','" . $did_carrier_description . "')";
				$SQL_sentence.= " |$DIDto_insert_CONFIRMED[$i]";
				$i++;
				$INSERTindex++;
				}
			$INSERTloopflag="FALSE";
			}
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$INSERTsqlLOG .= "$SQL|";
		}
			
	### Log our stuff
	$SQL_sentence = "DIDs copied from inbound DID #$did_id - $did_pattern:" . $SQL_sentence;
	$SQL_log = $INSERTsqlLOG;
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='DIDS', event_type='COPY', record_id='$did_id', event_code='ADMIN COPY BULK DID', event_sql=\"$SQL_log\", event_notes='$SQL_sentence';";
	if ($DB) {echo "$admin_log_stmt|";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);	

	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	echo "<br> "._QXZ("DIDs added").".";
	echo "\n\n\n<br>\n"._QXZ("runtime").": $RUNtime "._QXZ("seconds");
	echo "<br><br><a href=\"admin_bulk_tools.php\">"._QXZ("Go back to tools").".</a>";
	}
	
	
################################################################################
##### CONFIRM DID delete
elseif ($form_to_run == "BULKDIDSDELETE" || $form_to_run == "BULKDIDSDELETETB")
	{	
	if ($DIDdelete_from=="BLANK")
		{
		echo _QXZ("Go back, you did not specify any DIDs to delete.")."\n";
		exit;
		}
	$SQL="SELECT COUNT(*) FROM vicidial_inbound_dids where did_pattern not in ('default','did_system_filter');";
	$SQL_rslt=mysql_to_mysqli($SQL, $link);
	$row=mysqli_fetch_row($SQL_rslt);
	if ($row[0] == count($DIDdelete_from))
		{
		echo "<b><font color='red'>"._QXZ("WARNING!!!")." <br>";
		echo _QXZ("REALLY DELETE ALL DIDs?!")."</font></b><br>";
		}
	echo "<b> "._QXZ("WARNING: The following DIDs will be deleted!!!")."</b>";
	$i = 0;
	while ($i < count($DIDdelete_from))
		{
		$DIDdelete_from[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $DIDdelete_from[$i]);
		echo "<br> $DIDdelete_from[$i]";
		$i++;
		}
	$DIDdelete_from = serialize($DIDdelete_from);
	echo "<html><form action=$PHP_SELF method=POST>";
	echo "<input type=hidden name=form_to_run value='BULKDIDSDELETEconfirmed'>";
	echo "<input type=hidden name=DB value='$DB'>";
	echo "<input type=hidden name=DIDdelete_from_CONFIRMED value='$DIDdelete_from'>";
	echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='CONFIRM'></td></tr>\n";
	echo "</table></center></form>\n";
	echo "</html>";
	}
	
################################################################################
##### PROCESS DID delete
elseif ($form_to_run == "BULKDIDSDELETEconfirmed")
	{		
	$DIDdelete_from_CONFIRMED = unserialize($DIDdelete_from_CONFIRMED);
	$DIDdelete_from_CONFIRMED[0] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $DIDdelete_from_CONFIRMED[0]);
	$SQL = "DELETE FROM vicidial_inbound_dids WHERE did_pattern IN ('$DIDdelete_from_CONFIRMED[0]'";
	$i = 1;
	while ($i < count($DIDdelete_from_CONFIRMED))
		{
		$DIDdelete_from_CONFIRMED[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $DIDdelete_from_CONFIRMED[$i]);
		$SQL.= ",'" . $DIDdelete_from_CONFIRMED[$i] . "'";
		$i++;
		}
	$SQL.= ");";
	
	if ($DB) {echo "$SQL|";}
	$SQL_rslt = mysql_to_mysqli($SQL, $link);
	
	#Log our stuff
	$SQL_sentence = " ";
	$SQL_log = "$SQL|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='DIDS', event_type='DELETE', record_id='0', event_code='ADMIN DELETE BULK DID', event_sql=\"$SQL_log\", event_notes='$SQL_sentence';";
	if ($DB) {echo "$admin_log_stmt|";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
	
	echo _QXZ("DIDs have been deleted.")."";
	echo "<br><a href=\"admin_bulk_tools.php\">"._QXZ("Go back to tools").".</a>";
	}
	
	
################################################################################
##### CONFRIM users add
elseif ($form_to_run == "BULKUSERS")
	{
	$USERcount = ($USERstop - $USERstart) + 1;
	if ($USERstart == "BLANK")
		{
		echo "<br> "._QXZ("Go back, you have not entered a starting user ID.")."\n";
		exit;
		}
	if ($USERstop == "BLANK")
		{
		echo "<br> "._QXZ("Go back, You have not entered an ending user ID.")."\n";
		exit;
		}
	if ($USERcopy_from == "BLANK")
		{
		echo "<br> "._QXZ("Go back, you have not selected a source user to copy from.")."\n";
		exit;
		}
	if (strlen($USERstart) < 2 || strlen($USERstart) > 8)
		{
		echo _QXZ("Start ID").": $USERstart";
		echo "<br> "._QXZ("Go back, your starting user ID must be between 2 and 8 characters in length.")."\n";
		exit;
		}
	if (strlen($USERstop) < 2 || strlen($USERstop) > 8)
		{
		echo _QXZ("Stop ID").": $USERstop";
		echo "<br> "._QXZ("Go back, your stopping user ID must be between 2 and 8 characters in length.")."\n";
		exit;
		}
	if ($USERstart >= $USERstop)
		{
		echo _QXZ("Start ID").": $USERstart | "._QXZ("Stop ID").": $USERstop";
		echo "<br> "._QXZ("Go back, your starting user ID cannot be more than or equal to your ending user ID.")."\n";
		exit;
		}
	if (substr($USERstart, 0, 1) == "0")
		{
		echo _QXZ("Start ID").": $USERstart";
		echo "<br> "._QXZ("Go back, your starting user ID cannot begin with a zero.")."\n";
		exit;
		}
	if (substr($USERstop, 0, 1) == "0")
		{
		echo _QXZ("Stop ID").": $USERstop";
		echo "<br> "._QXZ("Go back, your stopping user ID cannot begin with a zero.")."\n";
		exit;
		}
	if ($USERcount > $INSERTmax_limit)
		{
		echo _QXZ("This tool has a limit of")." $INSERTmax_limit "._QXZ("items. You are trying to insert")." $USERcount. "._QXZ("Please go back and make adjustments.")."\n";
		exit;
		}
	$USERduplicate = array();
	$USERto_insert = array();
	$j=0; #duplicate counter
	$k=0; #insert counter
	while ($USERstart <= $USERstop)
		{
		$SQL= "SELECT user FROM vicidial_users WHERE user=$USERstart;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt=mysql_to_mysqli($SQL, $link);
		$row = mysqli_fetch_row($SQL_rslt);
		if ($row[0] == $USERstart)
			{
			$USERduplicate[$j] = $USERstart;
			$j++;
			}
		else
			{
			$USERto_insert[$k] = $USERstart;
			$k++;
			}
		$USERstart++;
		}
	
	if (empty($USERto_insert[0]))
		{
		echo "<br> <b>"._QXZ("Go back, all users are duplicates and will not be created").":</b> ";
		$i = 0;
		while ($i < count($USERduplicate))
			{
			echo "<br> $USERduplicate[$i]";
			$i++;
			}
		exit;
		}
	else
		{
		echo _QXZ("ATTENTION: You are about to insert the following users based off the settings in user")." $USERcopy_from :";
		$i = 0;
		while ($i < count($USERto_insert))
			{
			echo "<br> $USERto_insert[$i]";
			$i++;
			}
		echo "<br> "._QXZ("The following users are duplicates and will not be created").": ";
		if (empty($USERduplicate[0])) {echo "<br> NONE";}
		$i = 0;
		while ($i < count($USERduplicate))
			{
			echo "<br> $USERduplicate[$i]";
			$i++;
			}
		}
		
	$USERto_insert = serialize($USERto_insert);
	echo "<html><form action=$PHP_SELF method=POST>";
	echo "<input type=hidden name=form_to_run value='BULKUSERSconfirmed'>";
	echo "<input type=hidden name=DB value='$DB'>";
	echo "<input type=hidden name=USERto_insert value='$USERto_insert'>";
	echo "<input type=hidden name=USERcopy_from value='$USERcopy_from'>";
	echo "<input type=hidden name=USERforce_pw value='$USERforce_pw'>";
	echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='CONFIRM'></td></tr>\n";
	echo "</table></center></form>\n";
	echo "</html>";
	}
	
	
################################################################################
##### PROCESS users add
elseif ($form_to_run == "BULKUSERSconfirmed")
	{	
	$USERto_insert = unserialize($USERto_insert);
	$SQL = "SELECT user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,shift_override_flag,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,agent_choose_territories,custom_one,custom_two,custom_three,custom_four,custom_five,voicemail_id,agent_call_log_view_override,callcard_admin,agent_choose_blended,realtime_block_user_info,custom_fields_modify,force_change_password,agent_lead_search_override,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,preset_contact_search,modify_contacts,modify_same_user_level,admin_hide_lead_data,admin_hide_phone_data,agentcall_email,modify_email_accounts,failed_login_count,last_login_date,last_ip,pass_hash,alter_admin_interface_options,max_inbound_calls,modify_custom_dialplans,wrapup_seconds_override,modify_languages,selected_language,user_choose_language,ignore_group_on_search,api_list_restrict,api_allowed_functions,lead_filter_id,admin_cf_show_hidden,agentcall_chat,user_hide_realtime,access_recordings,modify_colors,user_nickname,user_new_lead_limit,api_only_user,modify_auto_reports,modify_ip_lists,ignore_ip_list,ready_max_logout,export_gdpr_leads,access_recordings,pause_code_approval,max_hopper_calls,max_hopper_calls_hour,mute_recordings,hide_call_log_info,next_dial_my_callbacks,user_admin_redirect_url,max_inbound_filter_enabled,max_inbound_filter_statuses,max_inbound_filter_ingroups,max_inbound_filter_min_sec,status_group_id,mobile_number,two_factor_override,manual_dial_filter,user_location,download_invalid_files,user_group_two,modify_dial_prefix,inbound_credits,hci_enabled FROM vicidial_users WHERE user=$USERcopy_from;";
	
	$SQL_rslt=mysql_to_mysqli($SQL, $link);
	$row = mysqli_fetch_row($SQL_rslt);
	$user								= $row[0];
	$pass 								= $row[1];
	$full_name							= $row[2];
	$user_level 						= $row[3];
	$user_group 						= $row[4];
	$phone_login 						= $row[5];
	$phone_pass 						= $row[6];
	$delete_users 						= $row[7];
	$delete_user_groups 				= $row[8];
	$delete_lists 						= $row[9];
	$delete_campaigns 					= $row[10];
	$delete_ingroups 					= $row[11];
	$delete_remote_agents 				= $row[12];
	$load_leads 						= $row[13];
	$ACCIDcampaign_detail 				= $row[14];
	$ast_admin_access 					= $row[15];
	$ast_delete_phones 					= $row[16];
	$delete_scripts 					= $row[17];
	$modify_leads 						= $row[18];
	$hotkeys_active 					= $row[19];
	$change_agent_campaign 				= $row[20];
	$agent_choose_ingroups 				= $row[21];
	$closer_campaigns					= $row[22];
	$scheduled_callbacks 				= $row[23];
	$agentonly_callbacks 				= $row[24];
	$agentcall_manual 					= $row[25];
	$vicidial_recording 				= $row[26];
	$vicidial_transfers 				= $row[27];
	$delete_filters 					= $row[28];
	$alter_agent_interface_options	 	= $row[29];
	$closer_default_blended 			= $row[30];
	$delete_call_times 					= $row[31];
	$modify_call_times 					= $row[32];
	$modify_users 						= $row[33];
	$modify_campaigns	 				= $row[34];
	$modify_lists	 					= $row[35];
	$modify_scripts 					= $row[36];
	$modify_filters 					= $row[37];
	$modify_ingroups 					= $row[38];
	$modify_usergroups 					= $row[39];
	$modify_remoteagents	 			= $row[40];
	$modify_servers 					= $row[41];
	$view_reports 						= $row[42];
	$vicidial_recording_override 		= $row[43];
	$alter_custdata_override			= $row[44];
	$qc_enabled 						= $row[45];
	$qc_user_level 						= $row[46];
	$qc_pass 							= $row[47];
	$qc_finish 							= $row[48];
	$qc_commit 							= $row[49];
	$add_timeclock_log 					= $row[50];
	$modify_timeclock_log 				= $row[51];
	$delete_timeclock_log 				= $row[52];
	$alter_custphone_override			= $row[53];
	$vdc_agent_api_access 				= $row[54];
	$modify_inbound_dids 				= $row[55];
	$delete_inbound_dids 				= $row[56];
	$user_active 						= $row[57];
	$alert_enabled 						= $row[58];
	$download_lists 					= $row[59];
	$agent_shift_enforcement_override 	= $row[60];
	$manager_shift_enforcement_override = $row[61];
	$shift_override_flag 				= $row[62];
	$export_reports 					= $row[63];
	$delete_from_dnc 					= $row[64];
	$email 								= $row[65];
	$user_code 							= $row[66];
	$territory							= $row[67];
	$allow_alerts 						= $row[68];
	$agent_choose_territories 			= $row[69];
	$custom_one 						= $row[70];
	$custom_two 						= $row[71];
	$custom_three 						= $row[72];
	$custom_four 						= $row[73];
	$custom_five 						= $row[74];
	$voicemail_id 						= $row[75];
	$agent_call_log_view_override 		= $row[76];
	$callcard_admin 					= $row[77];
	$agent_choose_blended 				= $row[78];
	$realtime_block_user_info 			= $row[79];
	$custom_fields_modify 				= $row[80];
	$force_change_passwordDEL			= $row[81]; #This is not used. Changed name rather than alter SQL and re-number array index. Value is set via form.
	$agent_lead_search_override 		= $row[82];
	$modify_shifts 						= $row[83];
	$modify_phones 						= $row[84];
	$modify_carriers 					= $row[85];
	$modify_labels 						= $row[86];
	$modify_statuses 					= $row[87];
	$modify_voicemail 					= $row[88];
	$modify_audiostore 					= $row[89];
	$modify_moh 						= $row[90];
	$modify_tts 						= $row[91];
	$preset_contact_search 				= $row[92];
	$modify_contacts 					= $row[93];
	$modify_same_user_level 			= $row[94];
	$admin_hide_lead_data 				= $row[95];
	$admin_hide_phone_data 				= $row[96];
	$agentcall_email 					= $row[97];
	$modify_email_accounts 				= $row[98];
	$failed_login_count 				= $row[99];
	$last_login_date 					= $row[100];
	$last_ip 							= $row[101];
	$pass_hash 							= $row[102];
	$alter_admin_interface_options 		= $row[103];
	$max_inbound_calls 					= $row[104];
	$modify_custom_dialplans 			= $row[105];
	$wrapup_seconds_override 			= $row[106];
	$modify_languages 					= $row[107];
	$selected_language					= $row[108];
	$user_choose_language 				= $row[109];
	$ignore_group_on_search 			= $row[110];
	$api_list_restrict 					= $row[111];
	$api_allowed_functions 				= $row[112];
	$lead_filter_id 					= $row[113];
	$admin_cf_show_hidden 				= $row[114];
	$agentcall_chat 					= $row[115];
	$user_hide_realtime 				= $row[116];
	$access_recordings 					= $row[117];
	$modify_colors						= $row[118];
	$user_nickname						= $row[119];
	$user_new_lead_limit				= $row[120];
	$api_only_user						= $row[121];
	$modify_auto_reports				= $row[122];
	$modify_ip_lists					= $row[123];
	$ignore_ip_list						= $row[124];
	$ready_max_logout					= $row[125];
	$export_gdpr_leads					= $row[126];	
	$access_recordings					= $row[127];
	$pause_code_approval				= $row[128];
	$max_hopper_calls					= $row[129];
	$max_hopper_calls_hour				= $row[130];
	$mute_recordings					= $row[131];
	$hide_call_log_info					= $row[132];
	$next_dial_my_callbacks				= $row[133];
	$user_admin_redirect_url			= $row[134];
	$max_inbound_filter_enabled			= $row[135];
	$max_inbound_filter_statuses		= $row[136];
	$max_inbound_filter_ingroups		= $row[137];
	$max_inbound_filter_min_sec			= $row[138];
	$status_group_id					= $row[139];
	$mobile_number						= $row[140];
	$two_factor_override				= $row[141];
	$manual_dial_filter					= $row[142];
	$user_location						= $row[143];
	$download_invalid_files				= $row[144];
	$user_group_two						= $row[145];
	$modify_dial_prefix					= $row[146];
	$inbound_credits					= $row[147];
	$hci_enabled						= $row[148];

	### Divide total users into groups
	$INSERTtotal = count($USERto_insert);
	$INSERTgroup_counter = 0;
	$INSERTloopflag = "TRUE";
	if ($INSERTtotal == $INSERTgroup_limit)
		{
		$INSERTremainder = $INSERTtotal;
		}
	else
		{
		while ($INSERTloopflag == "TRUE")
			{
			$INSERTdifference = $INSERTtotal - $INSERTgroup_limit;
			if ($INSERTdifference > $INSERTgroup_limit) #If the difference is bigger then we still have more groups to divide out
					{
					$INSERTtotal = $INSERTdifference;
					$INSERTgroup_counter++;
					}
			if (($INSERTdifference >= 0) && ($INSERTdifference <= $INSERTgroup_limit)) # If the difference is between 0 and the group limit then we've reached the end
					{
					$INSERTgroup_counter++;
					$INSERTremainder = $INSERTdifference;
					$INSERTloopflag="FALSE";
					}
			if ($INSERTdifference < 0) # If the difference is negative then there's only 1 group and it's less than the grouping number
				{
				$INSERTgroup_counter = 0;
				$INSERTremainder = $INSERTtotal;
				$INSERTloopflag="FALSE";
				}
			}
		}	

	### Loop through users and insert in groups
	$INSERTindex = 0;
	$INSERTloopflag = "TRUE";
	$INSERTsqlLOG='';
	$users_inserted=0;
	while ($INSERTloopflag == "TRUE")
		{
		$USERto_insert[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $USERto_insert[$INSERTindex]);

		$SQL = "INSERT IGNORE INTO vicidial_users (user,pass,full_name,user_level,user_group,phone_login,phone_pass,delete_users,delete_user_groups,delete_lists,delete_campaigns,delete_ingroups,delete_remote_agents,load_leads,campaign_detail,ast_admin_access,ast_delete_phones,delete_scripts,modify_leads,hotkeys_active,change_agent_campaign,agent_choose_ingroups,closer_campaigns,scheduled_callbacks,agentonly_callbacks,agentcall_manual,vicidial_recording,vicidial_transfers,delete_filters,alter_agent_interface_options,closer_default_blended,delete_call_times,modify_call_times,modify_users,modify_campaigns,modify_lists,modify_scripts,modify_filters,modify_ingroups,modify_usergroups,modify_remoteagents,modify_servers,view_reports,vicidial_recording_override,alter_custdata_override,qc_enabled,qc_user_level,qc_pass,qc_finish,qc_commit,add_timeclock_log,modify_timeclock_log,delete_timeclock_log,alter_custphone_override,vdc_agent_api_access,modify_inbound_dids,delete_inbound_dids,active,alert_enabled,download_lists,agent_shift_enforcement_override,manager_shift_enforcement_override,shift_override_flag,export_reports,delete_from_dnc,email,user_code,territory,allow_alerts,agent_choose_territories,custom_one,custom_two,custom_three,custom_four,custom_five,voicemail_id,agent_call_log_view_override,callcard_admin,agent_choose_blended,realtime_block_user_info,custom_fields_modify,force_change_password,agent_lead_search_override,modify_shifts,modify_phones,modify_carriers,modify_labels,modify_statuses,modify_voicemail,modify_audiostore,modify_moh,modify_tts,preset_contact_search,modify_contacts,modify_same_user_level,admin_hide_lead_data,admin_hide_phone_data,agentcall_email,modify_email_accounts,failed_login_count,last_login_date,last_ip,pass_hash,alter_admin_interface_options,max_inbound_calls,modify_custom_dialplans,wrapup_seconds_override,modify_languages,selected_language,user_choose_language,ignore_group_on_search,api_list_restrict,api_allowed_functions,lead_filter_id,admin_cf_show_hidden,agentcall_chat,user_hide_realtime,access_recordings,modify_colors,user_nickname,user_new_lead_limit,api_only_user,modify_auto_reports,modify_ip_lists,ignore_ip_list,ready_max_logout,export_gdpr_leads,pause_code_approval,max_hopper_calls,max_hopper_calls_hour,mute_recordings,hide_call_log_info,next_dial_my_callbacks,user_admin_redirect_url,max_inbound_filter_enabled,max_inbound_filter_statuses,max_inbound_filter_ingroups,max_inbound_filter_min_sec,status_group_id,mobile_number,two_factor_override,manual_dial_filter,user_location,download_invalid_files,user_group_two,modify_dial_prefix,inbound_credits,hci_enabled) VALUES ('$USERto_insert[$INSERTindex]','$USERto_insert[$INSERTindex]','$USERto_insert[$INSERTindex]','$user_level','$user_group','$phone_login','$phone_pass','$delete_users','$delete_user_groups','$delete_lists','$delete_campaigns','$delete_ingroups','$delete_remote_agents','$load_leads','$ACCIDcampaign_detail','$ast_admin_access','$ast_delete_phones','$delete_scripts','$modify_leads','$hotkeys_active','$change_agent_campaign','$agent_choose_ingroups','$closer_campaigns','$scheduled_callbacks','$agentonly_callbacks','$agentcall_manual','$vicidial_recording','$vicidial_transfers','$delete_filters','$alter_agent_interface_options','$closer_default_blended','$delete_call_times','$modify_call_times','$modify_users','$modify_campaigns','$modify_lists','$modify_scripts','$modify_filters','$modify_ingroups','$modify_usergroups','$modify_remoteagents','$modify_servers','$view_reports','$vicidial_recording_override','$alter_custdata_override','$qc_enabled','$qc_user_level','$qc_pass','$qc_finish','$qc_commit','$add_timeclock_log','$modify_timeclock_log','$delete_timeclock_log','$alter_custphone_override','$vdc_agent_api_access','$modify_inbound_dids','$delete_inbound_dids','$user_active','$alert_enabled','$download_lists','$agent_shift_enforcement_override','$manager_shift_enforcement_override','$shift_override_flag','$export_reports','$delete_from_dnc','$email','$user_code','$territory','$allow_alerts','$agent_choose_territories','$custom_one','$custom_two','$custom_three','$custom_four','$custom_five','$voicemail_id','$agent_call_log_view_override','$callcard_admin','$agent_choose_blended','$realtime_block_user_info','$custom_fields_modify','$USERforce_pw','$agent_lead_search_override','$modify_shifts','$modify_phones','$modify_carriers','$modify_labels','$modify_statuses','$modify_voicemail','$modify_audiostore','$modify_moh','$modify_tts','$preset_contact_search','$modify_contacts','$modify_same_user_level','$admin_hide_lead_data','$admin_hide_phone_data','$agentcall_email','$modify_email_accounts','$failed_login_count','$last_login_date','$last_ip','$pass_hash','$alter_admin_interface_options','$max_inbound_calls','$modify_custom_dialplans','$wrapup_seconds_override','$modify_languages','$selected_language','$user_choose_language','$ignore_group_on_search','$api_list_restrict','$api_allowed_functions','$lead_filter_id','$admin_cf_show_hidden','$agentcall_chat','$user_hide_realtime','$access_recordings','$modify_colors','$user_nickname','$user_new_lead_limit','$api_only_user','$modify_auto_reports','$modify_ip_lists','$ignore_ip_list','$ready_max_logout','$export_gdpr_leads','$pause_code_approval','$max_hopper_calls','$max_hopper_calls_hour','$mute_recordings','$hide_call_log_info','$next_dial_my_callbacks','$user_admin_redirect_url','$max_inbound_filter_enabled','$max_inbound_filter_statuses','$max_inbound_filter_ingroups','$max_inbound_filter_min_sec','$status_group_id','$mobile_number','$two_factor_override','$manual_dial_filter','$user_location','$download_invalid_files','$user_group_two','$modify_dial_prefix','$inbound_credits','$hci_enabled')";
		$INSERTindex++;
		$users_inserted++;
		if ($INSERTgroup_counter > 0)
			{
			$i = 1;
			while ($i < $INSERTgroup_limit)
				{
				$USERto_insert[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $USERto_insert[$INSERTindex]);

				$SQL.= ",('$USERto_insert[$INSERTindex]','$USERto_insert[$INSERTindex]','$USERto_insert[$INSERTindex]','$user_level','$user_group','$phone_login','$phone_pass','$delete_users','$delete_user_groups','$delete_lists','$delete_campaigns','$delete_ingroups','$delete_remote_agents','$load_leads','$ACCIDcampaign_detail','$ast_admin_access','$ast_delete_phones','$delete_scripts','$modify_leads','$hotkeys_active','$change_agent_campaign','$agent_choose_ingroups','$closer_campaigns','$scheduled_callbacks','$agentonly_callbacks','$agentcall_manual','$vicidial_recording','$vicidial_transfers','$delete_filters','$alter_agent_interface_options','$closer_default_blended','$delete_call_times','$modify_call_times','$modify_users','$modify_campaigns','$modify_lists','$modify_scripts','$modify_filters','$modify_ingroups','$modify_usergroups','$modify_remoteagents','$modify_servers','$view_reports','$vicidial_recording_override','$alter_custdata_override','$qc_enabled','$qc_user_level','$qc_pass','$qc_finish','$qc_commit','$add_timeclock_log','$modify_timeclock_log','$delete_timeclock_log','$alter_custphone_override','$vdc_agent_api_access','$modify_inbound_dids','$delete_inbound_dids','$user_active','$alert_enabled','$download_lists','$agent_shift_enforcement_override','$manager_shift_enforcement_override','$shift_override_flag','$export_reports','$delete_from_dnc','$email','$user_code','$territory','$allow_alerts','$agent_choose_territories','$custom_one','$custom_two','$custom_three','$custom_four','$custom_five','$voicemail_id','$agent_call_log_view_override','$callcard_admin','$agent_choose_blended','$realtime_block_user_info','$custom_fields_modify','$USERforce_pw','$agent_lead_search_override','$modify_shifts','$modify_phones','$modify_carriers','$modify_labels','$modify_statuses','$modify_voicemail','$modify_audiostore','$modify_moh','$modify_tts','$preset_contact_search','$modify_contacts','$modify_same_user_level','$admin_hide_lead_data','$admin_hide_phone_data','$agentcall_email','$modify_email_accounts','$failed_login_count','$last_login_date','$last_ip','$pass_hash','$alter_admin_interface_options','$max_inbound_calls','$modify_custom_dialplans','$wrapup_seconds_override','$modify_languages','$selected_language','$user_choose_language','$ignore_group_on_search','$api_list_restrict','$api_allowed_functions','$lead_filter_id','$admin_cf_show_hidden','$agentcall_chat','$user_hide_realtime','$access_recordings','$modify_colors','$user_nickname','$user_new_lead_limit','$api_only_user','$modify_auto_reports','$modify_ip_lists','$ignore_ip_list','$ready_max_logout','$export_gdpr_leads','$pause_code_approval','$max_hopper_calls','$max_hopper_calls_hour','$mute_recordings','$hide_call_log_info','$next_dial_my_callbacks','$user_admin_redirect_url','$max_inbound_filter_enabled','$max_inbound_filter_statuses','$max_inbound_filter_ingroups','$max_inbound_filter_min_sec','$status_group_id','$mobile_number','$two_factor_override','$manual_dial_filter','$user_location','$download_invalid_files','$user_group_two','$modify_dial_prefix','$inbound_credits','$hci_enabled')";
				$SQL_sentence.= " |$USERto_insert[$INSERTindex]";
				$i++;
				$INSERTindex++;
				$users_inserted++;
				}	
			$INSERTgroup_counter--;
			}
		else 
			{
			$i = 1;
			while ($i < $INSERTremainder)
				{
				$USERto_insert[$INSERTindex] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $USERto_insert[$INSERTindex]);

				$SQL.= ",('$USERto_insert[$INSERTindex]','$USERto_insert[$INSERTindex]','$USERto_insert[$INSERTindex]','$user_level','$user_group','$phone_login','$phone_pass','$delete_users','$delete_user_groups','$delete_lists','$delete_campaigns','$delete_ingroups','$delete_remote_agents','$load_leads','$ACCIDcampaign_detail','$ast_admin_access','$ast_delete_phones','$delete_scripts','$modify_leads','$hotkeys_active','$change_agent_campaign','$agent_choose_ingroups','$closer_campaigns','$scheduled_callbacks','$agentonly_callbacks','$agentcall_manual','$vicidial_recording','$vicidial_transfers','$delete_filters','$alter_agent_interface_options','$closer_default_blended','$delete_call_times','$modify_call_times','$modify_users','$modify_campaigns','$modify_lists','$modify_scripts','$modify_filters','$modify_ingroups','$modify_usergroups','$modify_remoteagents','$modify_servers','$view_reports','$vicidial_recording_override','$alter_custdata_override','$qc_enabled','$qc_user_level','$qc_pass','$qc_finish','$qc_commit','$add_timeclock_log','$modify_timeclock_log','$delete_timeclock_log','$alter_custphone_override','$vdc_agent_api_access','$modify_inbound_dids','$delete_inbound_dids','$user_active','$alert_enabled','$download_lists','$agent_shift_enforcement_override','$manager_shift_enforcement_override','$shift_override_flag','$export_reports','$delete_from_dnc','$email','$user_code','$territory','$allow_alerts','$agent_choose_territories','$custom_one','$custom_two','$custom_three','$custom_four','$custom_five','$voicemail_id','$agent_call_log_view_override','$callcard_admin','$agent_choose_blended','$realtime_block_user_info','$custom_fields_modify','$USERforce_pw','$agent_lead_search_override','$modify_shifts','$modify_phones','$modify_carriers','$modify_labels','$modify_statuses','$modify_voicemail','$modify_audiostore','$modify_moh','$modify_tts','$preset_contact_search','$modify_contacts','$modify_same_user_level','$admin_hide_lead_data','$admin_hide_phone_data','$agentcall_email','$modify_email_accounts','$failed_login_count','$last_login_date','$last_ip','$pass_hash','$alter_admin_interface_options','$max_inbound_calls','$modify_custom_dialplans','$wrapup_seconds_override','$modify_languages','$selected_language','$user_choose_language','$ignore_group_on_search','$api_list_restrict','$api_allowed_functions','$lead_filter_id','$admin_cf_show_hidden','$agentcall_chat','$user_hide_realtime','$access_recordings','$modify_colors','$user_nickname','$user_new_lead_limit','$api_only_user','$modify_auto_reports','$modify_ip_lists','$ignore_ip_list','$ready_max_logout','$export_gdpr_leads','$pause_code_approval','$max_hopper_calls','$max_hopper_calls_hour','$mute_recordings','$hide_call_log_info','$next_dial_my_callbacks','$user_admin_redirect_url','$max_inbound_filter_enabled','$max_inbound_filter_statuses','$max_inbound_filter_ingroups','$max_inbound_filter_min_sec','$status_group_id','$mobile_number','$two_factor_override','$manual_dial_filter','$user_location','$download_invalid_files','$user_group_two','$modify_dial_prefix','$inbound_credits','$hci_enabled')";
				$SQL_sentence.= " |$USERto_insert[$INSERTindex]";
				$i++;
				$INSERTindex++;
				$users_inserted++;
				}
			$INSERTloopflag="FALSE";
			}
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$INSERTsqlLOG .= "$SQL|";
		}
	
	#Log our stuff
	$SQL_sentence = "Users copied from user ID#$user: " . $SQL_sentence;
	$SQL_log = "$INSERTsqlLOG";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='COPY', record_id='$USERcopy_from', event_code='ADMIN COPY BULK USERS', event_sql=\"$SQL_log\", event_notes='$users_inserted $SQL_sentence';";
	if ($DB) {echo "$admin_log_stmt|";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
	
	$ENDtime = date("U");
	$RUNtime = ($ENDtime - $STARTtime);
	echo "<br> "._QXZ("Users added").": $users_inserted";
	echo "\n\n\n<br>\n"._QXZ("runtime").": $RUNtime "._QXZ("seconds");
	echo "<br><br><a href=\"admin_bulk_tools.php\">"._QXZ("Go back to tools").".</a>";
	}
	
	
################################################################################
##### Delete users confirmation
elseif ($form_to_run == "BULKUSERSDELETE") ### BULK USER DELETE
	{
	if ($USERdelete_from=="BLANK")
	{
	echo _QXZ("Go back, you did not specify any users to delete.")."\n";
	exit;
	}
	echo "<b> "._QXZ("WARNING: The following users will be deleted!!!")."</b>";
	$i = 0;
	while ($i < count($USERdelete_from))
		{
		$USERdelete_from[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $USERdelete_from[$i]);
		echo "<br> $USERdelete_from[$i]";
		$i++;
		}
	$USERdelete_from = serialize($USERdelete_from);
	echo "<html><form action=$PHP_SELF method=POST>";
	echo "<input type=hidden name=form_to_run value='BULKUSERSDELETEconfirmed'>";
	echo "<input type=hidden name=DB value='$DB'>";
	echo "<input type=hidden name=USERdelete_from_CONFIRMED value='$USERdelete_from'>";
	echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#".$SSbutton_color."' type=submit name=did_submit value='CONFIRM'></td></tr>\n";
	echo "</table></center></form>\n";
	echo "</html>";
	}
	
	
################################################################################
##### Process delete users
elseif ($form_to_run == "BULKUSERSDELETEconfirmed")### BULK USER DELETE CONFIRM
	{
	$USERdelete_from_CONFIRMED = unserialize($USERdelete_from_CONFIRMED);
	$USERdelete_from_CONFIRMED[0] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $USERdelete_from_CONFIRMED[0]);
	$SQL = "DELETE FROM vicidial_users WHERE user IN ('$USERdelete_from_CONFIRMED[0]'";
	$i = 1;
	while ($i < count($USERdelete_from_CONFIRMED))
		{
		$USERdelete_from_CONFIRMED[$i] = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $USERdelete_from_CONFIRMED[$i]);
		$SQL.= ",'" . $USERdelete_from_CONFIRMED[$i] . "'";
		$i++;
		}
	$SQL.= ");";
	
	if ($DB) {echo "$SQL|";}
	$SQL_rslt = mysql_to_mysqli($SQL, $link);
	
	#Log our stuff
	$SQL_sentence = " ";
	$SQL_log = "|$SQL|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='DELETE', record_id='0', event_code='ADMIN DELETE BULK USERS', event_sql=\"$SQL_log\", event_notes='$SQL_sentence';";
	if ($DB) {echo "$admin_log_stmt|";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
	
	echo _QXZ("Deleted.");
	echo "<br><a href=\"admin_bulk_tools.php\">"._QXZ("Go back to tools").".</a>";
	}

	
###############################################################################
#### Build forms
else
	{
	echo "<html><center><p>"._QXZ("These are tools for adding, copying and deleting DIDs, Campaign AC-CIDs and Users.")."<br>"._QXZ("Adding and copying are limited to")." $INSERTmax_limit "._QXZ("per run").".<br></p>";
	### DID - ADD
	if ( $modify_dids < 1 )
		{
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("DID Bulk Copy")."</b></font>$NWB#DIDADD$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><font color=black><b>"._QXZ("You do not have permission to use this section.")."</b></font></td></tr>\n";
		echo "</table></center>\n";
		}
	else 
		{
		echo "<form action=$PHP_SELF method='post'>";
		echo "<input type=hidden name=form_to_run value='BULKDIDS'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<col width=50%><col width=50%>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("DID Bulk Copy")."</b>$NWB#DIDADD$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("DID to copy from").": </td><td align=left>\n";
			
		$dids_to_copy = array();
		$SQL="SELECT did_pattern,did_description FROM vicidial_inbound_dids WHERE $admin_viewable_groupsSQL ORDER BY did_pattern ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$did_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		while ($i < $did_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$dids_to_copy[$i] = $row[0];
			$dids_to_copy_name[$i] = $row[1];
			$i++;
			}
					
		echo "<select size=1 name=DIDcopy_from>\n";
		echo "<option value='BLANK'>"._QXZ("Select a DID")."</option>\n";
		
		$i = 0;
		while ( $i < $did_count )
			{
			echo "<option value='$dids_to_copy[$i]'>$dids_to_copy[$i] - $dids_to_copy_name[$i]</option>\n";
			$i++;
			}

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("DIDs to insert").":</td><td align=left><textarea name='DIDto_insert' cols='11' rows='10'></textarea></td></td></tr>";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='"._QXZ("Submit")."'></td></tr>\n";
		echo "</table></center></form>\n";
		}

	### DID - DELETE
	if ( $modify_dids < 1  || $delete_dids < 1 )
		{
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("DID Bulk Delete")."</b>$NWB#DIDDELETE$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><font color=black><b>"._QXZ("You do not have permission to use this section.")."</b></font></td></tr>\n";
		echo "</table></center>\n";
		}
	else
		{
		// Original delete form
		echo "<html><form action=$PHP_SELF method=POST>";
		echo "<input type=hidden name=form_to_run value='BULKDIDSDELETE'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<col width=50%><col width=50%>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("DID Bulk Delete Select")."</b>$NWB#DIDDELETE$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("DIDs to delete").": </td><td align=left>\n";
			
		$DIDto_copy = array();
		$SQL="SELECT did_pattern,did_description FROM vicidial_inbound_dids WHERE did_pattern NOT IN ('default','did_system_filter') AND $admin_viewable_groupsSQL ORDER BY did_pattern ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$did_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		while ($i < $did_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$DIDto_delete[$i] = $row[0];
			$DIDto_delete_desc[$i] = $row[1];
			$i++;
			}
					
		echo "<select multiple size=10 name='DIDdelete_from[]'>\n";
		$i = 0;
		while ( $i < $did_count )
			{
			echo "<option value='$DIDto_delete[$i]'>$DIDto_delete[$i] - $DIDto_delete_desc[$i]</option>\n";
			$i++;
			}
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='"._QXZ("Submit")."'></td></tr>\n";
		echo "</table></center></form><br>\n";
		
		// New text box delete
		echo "<form action=$PHP_SELF method=POST>";
		echo "<input type=hidden name=form_to_run value='BULKDIDSDELETETB'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<col width=50%><col width=50%>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("DID Bulk Delete")."</b>$NWB#DIDDELETE$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("DIDs to delete").":</td><td align=left><textarea name='DIDto_delete_TB' cols='11' rows='10'></textarea></td></td></tr>";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='"._QXZ("Submit")."'></td></tr>\n";
		echo "</table></center></form>\n";
		
		echo "</html>";
		}
	
	### AC-CID	- ADD
	if ( $modify_campaigns < 1 )
		{
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("CID Groups and AC-CID Bulk Add")."</b>$NWB#ACCIDADD$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><font color=black><b>"._QXZ("You do not have permission to use this section.")."</b></font></td></tr>\n";
		echo "</table></center>\n";
		}
	else 
		{
		echo "<form action=$PHP_SELF method='post'>";
		echo "<input type=hidden name=form_to_run value='ACCID'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<col width=50%><col width=50%>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("CID Groups and AC-CID Bulk Add")."</b>$NWB#ACCIDADD$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Method").": </td><td align=left><select size=1 name=ACCIDmethod>\n";	
		echo "<option value='CID'>"._QXZ("STATE LOOKUP")."</option>\n";
		echo "<option value='CSV'>"._QXZ("CSV")."</option>\n";
		echo "<option value='STATEFILL'>"._QXZ("STATE FILL")."</option>\n";
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Campaign or CID Group").":</td><td align=left>\n";
				
		$ACCIDcampaigns_to_copy = array();
		$SQL="SELECT campaign_id,campaign_name FROM vicidial_campaigns WHERE $allowed_campaignsSQL AND $admin_viewable_groupsSQL ORDER BY campaign_id ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$camp_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		$g = 0;
		while ($i < $camp_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$ACCIDcampaigns_to_copy[$g] = $row[0];
			$ACCIDcampaigns_to_copy_names[$g] = $row[1];
			$ACCIDcampaigns_to_copy_type[$g] = _QXZ('AREACODE');
			$i++;
			$g++;
			}
		$SQL="SELECT cid_group_id,cid_group_notes,cid_group_type FROM vicidial_cid_groups WHERE $admin_viewable_groupsSQL ORDER BY cid_group_id ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$cgid_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		while ($i < $cgid_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$ACCIDcampaigns_to_copy[$g] = $row[0];
			$ACCIDcampaigns_to_copy_names[$g] = $row[1];
			$ACCIDcampaigns_to_copy_type[$g] = _QXZ("$row[2]");
			$i++;
			$g++;
			}
			
		echo "<select size=1 name=ACCIDcampaign>\n";
		echo "<option value='BLANK'>"._QXZ("Select a campaign or CID group")."</option>\n";
		
		$i = 0;
		while ( $i < $g )
			{
			echo "<option value='$ACCIDcampaigns_to_copy[$i]'>$ACCIDcampaigns_to_copy[$i] - $ACCIDcampaigns_to_copy_type[$i] - $ACCIDcampaigns_to_copy_names[$i]</option>\n";
			$i++;
			}

		echo "</select></td></tr>\n";	
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Active")."?:</td><td align=left><select size=1 name=ACCIDactive>\n";	
		echo "<option value='N'>"._QXZ("No")."</option>\n";
		echo "<option value='Y'>"._QXZ("Yes")."</option>\n";
		echo "<option value='F'>"._QXZ("Input")."</option>\n";
		echo "</select></td></tr>\n";	
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("CID Group and AC-CID entries").":</td><td align=left><textarea name='ACCIDdids' cols='70' rows='10'></textarea></td>\n";	
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=accid_submit value='"._QXZ("Submit")."'></td></tr>\n";
		echo "</table></center></form>\n";
		echo "</html>";
		}
	
	### AC-CID - DELETE
	
	if ( $delete_campaigns < 1 )
		{
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("CID Groups and AC-CID Bulk Delete")."</b>$NWB#ACCIDDELETE$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><font color=black><b>"._QXZ("You do not have permission to use this section.")."</b></font></td></tr>\n";
		echo "</table></center>\n";
		}
	else 
		{
		echo "<form action=$PHP_SELF method='post'>";
		echo "<input type=hidden name=form_to_run value='ACCIDDELETEselect'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<col width=50%><col width=50%>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("CID Groups and AC-CID Bulk Delete Select")."</b>$NWB#ACCIDDELETE$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Campaign or CID Group").":</td><td align=left>\n";
		
		$ACCIDdelete_campaign_selection = array();
		$SQL="SELECT campaign_id,campaign_name from vicidial_campaigns WHERE campaign_id IN (select distinct campaign_id from vicidial_campaign_cid_areacodes) AND $allowed_campaignsSQL AND $admin_viewable_groupsSQL ORDER BY campaign_id ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$camp_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		$g = 0;
		while ($i < $camp_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$ACCIDdelete_campaign_selection[$g] = $row[0];
			$ACCIDdelete_campaign_name[$g] = $row[1];
			$ACCIDdelete_campaign_type[$g] = _QXZ('AREACODE');
			$i++;
			$g++;
			}
		$SQL="SELECT cid_group_id,cid_group_notes,cid_group_type FROM vicidial_cid_groups WHERE $admin_viewable_groupsSQL ORDER BY cid_group_id ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$cgid_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		while ($i < $cgid_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$ACCIDdelete_campaign_selection[$g] = $row[0];
			$ACCIDdelete_campaign_name[$g] = $row[1];
			$ACCIDdelete_campaign_type[$g] = _QXZ("$row[2]");
			$i++;
			$g++;
			}

		echo "<select size=1 name=ACCIDdelete_campaign>\n";
		echo "<option value='BLANK'>"._QXZ("Select a campaign or CID group")."</option>\n";
		
		$i = 0;
		while ( $i < $g )
			{
			echo "<option value='$ACCIDdelete_campaign_selection[$i]'>$ACCIDdelete_campaign_selection[$i] - $ACCIDdelete_campaign_type[$i] - $ACCIDdelete_campaign_name[$i]</option>\n";
			$i++;
			}	

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Clear all CID Group or AC-CID entries?").":</td><td align=left><select size=1 name=ACCIDclear_all>\n";	
		echo "<option value='N'>"._QXZ("No")."</option>\n";
		echo "<option value='Y'>"._QXZ("Yes")."</option>\n";
		echo "</select></td></tr>\n";		
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=accid_submit value='"._QXZ("Submit")."'></td></tr>\n";
		echo "</table></center></form>\n";
		echo "</html>";
		
		// New text box delete
		echo "<form action=$PHP_SELF method=POST>";
		echo "<input type=hidden name=form_to_run value='ACCIDDELETEconfirmTB'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<col width=50%><col width=50%>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("CID Groups and AC-CID Bulk Delete")."</b>$NWB#ACCIDDELETE$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Campaign or CID Group").":</td><td align=left>\n";
		
		$ACCIDdelete_campaign_selection = array();
		$SQL="SELECT campaign_id,campaign_name from vicidial_campaigns WHERE campaign_id IN (select distinct campaign_id from vicidial_campaign_cid_areacodes) AND $allowed_campaignsSQL AND $admin_viewable_groupsSQL ORDER BY campaign_id ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$camp_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		$g = 0;
		while ($i < $camp_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$ACCIDdelete_campaign_selection[$g] = $row[0];
			$ACCIDdelete_campaign_name[$g] = $row[1];
			$ACCIDdelete_campaign_type[$g] = _QXZ('AREACODE');
			$i++;
			$g++;
			}
		$SQL="SELECT cid_group_id,cid_group_notes,cid_group_type FROM vicidial_cid_groups WHERE $admin_viewable_groupsSQL ORDER BY cid_group_id ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$cgid_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		while ($i < $cgid_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$ACCIDdelete_campaign_selection[$g] = $row[0];
			$ACCIDdelete_campaign_name[$g] = $row[1];
			$ACCIDdelete_campaign_type[$g] = _QXZ("$row[2]");
			$i++;
			$g++;
			}

		echo "<select size=1 name=ACCIDdelete_campaign>\n";
		echo "<option value='BLANK'>"._QXZ("Select a campaign or CID group")."</option>\n";
		echo "<option value='---ALL---'>---ALL---</option>\n";
		
		$i = 0;
		while ( $i < $g )
			{
			echo "<option value='$ACCIDdelete_campaign_selection[$i]'>$ACCIDdelete_campaign_selection[$i] - $ACCIDdelete_campaign_type[$i] - $ACCIDdelete_campaign_name[$i]</option>\n";
			$i++;
			}	

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("CIDs to delete").":</td><td align=left><textarea name='ACCIDdelete_from' cols='11' rows='10'></textarea></td></td></tr>";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='"._QXZ("Submit")."'></td></tr>\n";
		echo "</table></center></form>\n";
		
		echo "</html>";
		}
	
	### USERS - ADD
	if ( $modify_users < 1 )
		{
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("User Bulk Copy")."</b>$NWB#USERADD$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><font color=black><b>"._QXZ("You do not have permission to use this section.")."</b></font></td></tr>\n";
		echo "</table></center>\n";
		}
	else
		{
		echo "<html><form action=$PHP_SELF method=POST>";
		echo "<input type=hidden name=form_to_run value='BULKUSERS'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<col width=50%><col width=50%>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("User Bulk Copy")."</b>$NWB#USERADD$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("User to copy from").": </td><td align=left>\n";
			
		$USERto_copy = array();
		$SQL="SELECT user, full_name FROM vicidial_users WHERE user NOT IN ('VDAD','VDCL') AND $admin_viewable_groupsSQL ORDER BY user ASC;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$user_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		while ($i < $user_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$USERto_copy[$i] = $row[0];
			$USERto_copy_name[$i] = $row[1];
			$i++;
			}
					
		echo "<select size=1 name=USERcopy_from>\n";
		echo "<option value='BLANK'>"._QXZ("Select a user")."</option>\n";
		
		$i = 0;
		while ( $i < $user_count )
			{
			echo "<option value='$USERto_copy[$i]'>$USERto_copy[$i] - $USERto_copy_name[$i]</option>\n";
			$i++;
			}
			
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("User Start ID").":</td><td align=left><input type='text' name='USERstart'></td></td></tr>";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("User Stop ID").":</td><td align=left><input type='text' name='USERstop'></td></td></tr>";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Force PW change?").":</td><td align=left><select size=1 name=USERforce_pw>\n";	
		echo "<option value='N'>"._QXZ("No")."</option>\n";
		echo "<option value='Y'>"._QXZ("Yes")."</option>\n";
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='"._QXZ("Submit")."'></td></tr>\n";
		echo "</table></center></form>\n";
		echo "</html>";
		}
	
	### USERS - DELETE
	if ( $delete_users < 1 || $modify_users < 1)
		{
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("User Bulk Delete")."</b>$NWB#USERDELETE$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><font color=black><b>"._QXZ("You do not have permission to use this section.")."</b></font></td></tr>\n";
		echo "</table></center>\n";
		}
	else
		{
		echo "<html><form action=$PHP_SELF method=POST>";
		echo "<input type=hidden name=form_to_run value='BULKUSERSDELETE'>";
		echo "<input type=hidden name=DB value='$DB'>";
		echo "<center><table width=$section_width cellspacing='3'>";
		echo "<col width=50%><col width=50%>";
		echo "<tr bgcolor=#". $SSmenu_background ."><td colspan=2 align=center><font color=white><b>"._QXZ("User Bulk Delete")."</b>$NWB#USERDELETE$NWE</font></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Users to delete").": </td><td align=left>\n";
		
		$SQL = "SELECT user FROM vicidial_live_agents;";
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$user_count = mysqli_num_rows($SQL_rslt);
		if ($user_count == 0)
			{
			$live_agentsSQL = "''";
			}
		else
			{
			$i = 0;
			while ($i < $user_count)
				{
				$row = mysqli_fetch_row($SQL_rslt);
				$live_agentsSQL.= "'" . $row[0] . "',";
				$i++;
				}
			$live_agentsSQL.="''";
			}
		
		$USERto_delete = array();
		if ($modify_level != 1)
			{
			$SQL="SELECT user, full_name FROM vicidial_users WHERE user NOT IN ('VDAD','VDCL','$PHP_AUTH_USER',$live_agentsSQL) AND user_level != 9 AND $admin_viewable_groupsSQL ORDER BY user ASC;";
			}
		else
			{
			$SQL="SELECT user, full_name FROM vicidial_users WHERE user NOT IN ('VDAD','VDCL','$PHP_AUTH_USER',$live_agentsSQL) AND $admin_viewable_groupsSQL ORDER BY user ASC;";
			}
		if ($DB) {echo "$SQL|";}
		$SQL_rslt = mysql_to_mysqli($SQL, $link);
		$user_count = mysqli_num_rows($SQL_rslt);
		$i = 0;
		while ($i < $user_count)
			{
			$row = mysqli_fetch_row($SQL_rslt);
			$USERto_delete[$i] = $row[0];
			$USERto_delete_name[$i] = $row[1];
			$i++;
			}	
		echo "<select multiple size=10 name='USERdelete_from[]'>\n";
		$i = 0;
		while ( $i < $user_count )
			{
			echo "<option value='$USERto_delete[$i]'>$USERto_delete[$i] - $USERto_delete_name[$i]</option>\n";
			$i++;
			}
		
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row1_background ."><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=did_submit value='"._QXZ("Submit")."'></td></tr>\n";
		echo "</table></center></form>\n";
		
		echo "<br> <font size=1><p align=left>"._QXZ("Version").": $version   "._QXZ("Build").": $build</p></font>";
		echo "</html>";
		}
	}
?>
