<?php
# admin_amm_multi.php
# 
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen will control the *amd* settings needed when the Campaign setting 
# "AM Message Wildcards" is enabled. This screen allows for multiple messages 
# and fields to be entered and ranked in the order in which they are are to be 
# searched.
#
# changes:
# 151109-1653 - First Build
# 160801-1201 - Added colors features
# 170409-1544 - Added IP List validation code
# 180502-2115 - Added new help display
# 180618-2300 - Modified calls to audio file chooser function
# 180924-1606 - Added called_count as field
#

$admin_version = '2.14-6';
$build = '180924-1606';

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
if (isset($_GET["amm_id"]))						{$amm_id=$_GET["amm_id"];}
	elseif (isset($_POST["amm_id"]))			{$amm_id=$_POST["amm_id"];}
if (isset($_GET["campaign_id"]))				{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))		{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["entry_type"]))					{$entry_type=$_GET["entry_type"];}
	elseif (isset($_POST["entry_type"]))		{$entry_type=$_POST["entry_type"];}
if (isset($_GET["active"]))						{$active=$_GET["active"];}
	elseif (isset($_POST["active"]))			{$active=$_POST["active"];}
if (isset($_GET["amm_field"]))					{$amm_field=$_GET["amm_field"];}
	elseif (isset($_POST["amm_field"]))			{$amm_field=$_POST["amm_field"];}
if (isset($_GET["amm_rank"]))					{$amm_rank=$_GET["amm_rank"];}
	elseif (isset($_POST["amm_rank"]))			{$amm_rank=$_POST["amm_rank"];}
if (isset($_GET["amm_wildcard"]))				{$amm_wildcard=$_GET["amm_wildcard"];}
	elseif (isset($_POST["amm_wildcard"]))		{$amm_wildcard=$_POST["amm_wildcard"];}
if (isset($_GET["amm_description"]))			{$amm_description=$_GET["amm_description"];}
	elseif (isset($_POST["amm_description"]))	{$amm_description=$_POST["amm_description"];}
if (isset($_GET["amm_filename"]))				{$amm_filename=$_GET["amm_filename"];}
	elseif (isset($_POST["amm_filename"]))		{$amm_filename=$_POST["amm_filename"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}

if (strlen($action) < 2)
	{$action = 'BLANK';}
if (strlen($DB) < 1)
	{$DB=0;}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,enable_languages,language_method,qc_features_active FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$ss_conf_ct = mysqli_num_rows($rslt);
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSenable_languages =			$row[2];
	$SSlanguage_method =			$row[3];
	$SSqc_features_active =			$row[4];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$amm_id = preg_replace('/[^0-9]/','',$amm_id);
	$campaign_id = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign_id);
	$entry_type = preg_replace('/[^_0-9a-zA-Z]/','',$entry_type);
	$active = preg_replace('/[^A-Z]/','',$active);
	$amm_field = preg_replace('/[^_0-9a-zA-Z]/','',$amm_field);
	$amm_rank = preg_replace('/[^-0-9]/','',$amm_rank);
	$amm_wildcard = preg_replace('/[^- _0-9a-zA-Z]/','',$amm_wildcard);
	$amm_description = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$amm_description);
	$amm_filename = preg_replace('/[^- \|\.\,\_0-9a-zA-Z]/','',$amm_filename);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$user = $PHP_AUTH_USER;
# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$stmt="SELECT selected_language,qc_enabled from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$qc_auth =					$row[1];
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

$stmt="SELECT full_name,modify_campaigns,user_level from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGmodify_campaigns =		$row[1];
$LOGuser_level =			$row[2];

if ($LOGmodify_campaigns < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify campaigns")."\n";
	exit;
	}


##### BEGIN Set variables to make header show properly #####
$ADD =					'31';
$SUB =					'26';
$hh =					'campaigns';
$sh =					'detail';
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
$campaigns_color =		'#FFFF99';
$campaigns_font =		'BLACK';
$campaigns_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
$ingroups_font =	'BLACK';
$ingroups_color =	'#E6E6E6';
$lists_font =		'BLACK';
$lists_color =		'#E6E6E6';
##### END Set variables to make header show properly #####

if (strlen($campaign_id) < 1)
	{
	echo _QXZ("ERROR: ID not defined:");
	exit;
	}
if ($entry_type == 'campaign')
	{
	$ADD =					'31';
	$hh =					'campaigns';
	$stmt_name="SELECT campaign_name from vicidial_campaigns where campaign_id='$campaign_id';";
	$mod_link = "ADD=$ADD&campaign_id=";
	$event_section = 'CAMPAIGNS';
	$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and am_message_wildcards='Y';";
	}
elseif ($entry_type == 'ingroup')
	{
	$ADD =					'3111';
	$hh =					'ingroups';
	$stmt_name="SELECT group_name from vicidial_inbound_groups where group_id='$campaign_id';";
	$mod_link = "ADD=$ADD&group_id=";
	$event_section = 'INGROUPS';
	$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and am_message_wildcards='Y';";
	}
else
	{
	echo _QXZ("ERROR: no entry type defined:") . $entry_type;
	exit;
	}

if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$camp_multi=$row[0];

$camp_name='';
$rslt=mysql_to_mysqli($stmt_name, $link);
$names_to_print = mysqli_num_rows($rslt);
if ($names_to_print > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$camp_name = $row[0];
	}

require("screen_colors.php");
?>
<html>
<head>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<title><?php echo _QXZ("ADMINISTRATION: AM Message Multi Entry") . " " . $campaign_id . " - " . $camp_name; ?>
<?php 


require("admin_header.php");

if ($camp_multi < 1)
	{
	echo _QXZ("AM Message Wildcards is not set to active")."$entry_type|$campaign_id\n";
	exit;
	}

if ($DB > 0)
	{
	echo "$DB,$action,$campaign_id,$amm_id,$entry_type,$active,$amm_field,$amm_rank,$amm_wildcard,$amm_description\n<BR>";
	}


################################################################################
##### BEGIN AMM multi NEW section
if ($action == "AMM_MULTI_NEW")
	{
	if ( (strlen($active)<1) or (strlen($amm_rank)<1) or (strlen($amm_wildcard)<1) or (strlen($amm_description)<1) or (strlen($amm_filename)<1) )
		{
		echo _QXZ("ERROR: You must fill in all fields").":  |$active|$amm_rank|$amm_wildcard|$amm_description|$amm_filename|\n<BR>";
		exit;
		}
	$stmt="INSERT INTO vicidial_amm_multi SET campaign_id='$campaign_id',entry_type='$entry_type',amm_field='$amm_field',active='$active',amm_rank='$amm_rank',amm_wildcard='$amm_wildcard',amm_description='$amm_description',amm_filename='" . mysqli_real_escape_string($link, $amm_filename) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
	if ($affected_rows > 0)
		{
		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='$event_section', event_type='ADD', record_id='$campaign_id', event_code='ADD MULTI AMM', event_sql=\"$SQL_log\", event_notes='$amm_field';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 0) {echo "$campaign_id|$stmt\n<BR>";}

		echo "<BR><b>$affected_rows "._QXZ("MULTI AM MESSAGE ADDED")."</b><BR><BR>";
		}
	else
		{echo _QXZ("ERROR: Problem adding AM Message").":  $affected_rows|$stmt\n<BR>";}
	$action='BLANK';
	}
##### END AMM multi NEW section


################################################################################
##### BEGIN AMM multi MODIFY section
if ($action == "AMM_MULTI_MODIFY")
	{
	if ( (strlen($amm_id)<1) or (strlen($active)<1) or (strlen($amm_rank)<1) or (strlen($amm_wildcard)<1) or (strlen($amm_description)<1) or (strlen($amm_filename)<1) )
		{
		echo _QXZ("ERROR: You must fill in all fields").":  |$amm_id|$active|$amm_rank|$amm_wildcard|$amm_description|$amm_filename|\n<BR>";
		exit;
		}
	$stmt="SELECT count(*) from vicidial_amm_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and amm_id='$amm_id';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$amm_count=$row[0];
	if ($amm_count < 1)
		{
		echo _QXZ("ERROR: AM MESSAGE entry does not exist").":  |$amm_id|$campaign_id|$entry_type|$amm_field|\n<BR>";
		exit;
		}
	$stmt="UPDATE vicidial_amm_multi SET active='$active',amm_field='$amm_field',amm_rank='$amm_rank',amm_wildcard='$amm_wildcard',amm_description='$amm_description',amm_filename='" . mysqli_real_escape_string($link, $amm_filename) . "' where campaign_id='$campaign_id' and entry_type='$entry_type' and amm_id='$amm_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
	if ($affected_rows > 0)
		{
		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='$event_section', event_type='MODIFY', record_id='$campaign_id', event_code='MODIFY MULTI AMM', event_sql=\"$SQL_log\", event_notes='$amm_field ID: $amm_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 0) {echo "$campaign_id|$stmt\n<BR>";}

		echo "<BR><b>$affected_rows "._QXZ("MULTI AM MESSAGE MODIFIED")." (ID: $amm_id)</b><BR><BR>";
		}
	else
		{echo _QXZ("ERROR: Problem modifying AM MESSAGE").":  $affected_rows|$stmt\n<BR>";}
	$action='BLANK';
	}
##### END AMM multi MODIFY section


################################################################################
##### BEGIN AMM multi DELETE section
if ($action == "AMM_MULTI_DELETE")
	{
	if ( (strlen($amm_id)<1) or (strlen($campaign_id)<1) or (strlen($entry_type)<1) or (strlen($amm_field)<1) )
		{
		echo _QXZ("ERROR: You must fill in all fields").":  |$amm_id|$campaign_id|$entry_type|$amm_field|\n<BR>";
		exit;
		}
	$stmt="SELECT count(*) from vicidial_amm_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and amm_field='$amm_field' and amm_id='$amm_id' and active='N';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$amm_count=$row[0];
	if ($amm_count < 1)
		{
		echo _QXZ("ERROR: AM MESSAGE entry must be set to not active before you delete it").":  |$amm_id|$campaign_id|$entry_type|$amm_field|\n<BR>";
		}
	else
		{
		$stmt="DELETE FROM vicidial_amm_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and amm_id='$amm_id' and active='N';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
		if ($affected_rows > 0)
			{
			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='$event_section', event_type='DELETE', record_id='$campaign_id', event_code='DELETE MULTI AMM', event_sql=\"$SQL_log\", event_notes='$amm_field ID: $amm_id';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB > 0) {echo "$campaign_id|$stmt\n<BR>";}

			echo "<BR><b>$affected_rows "._QXZ("MULTI AM MESSAGE DELETED")." (ID: $amm_id)</b><BR><BR>";
			}
		else
			{echo _QXZ("ERROR: Problem deleting AM MESSAGE").":  $affected_rows|$stmt\n<BR>";}
		}
	$action='BLANK';
	}
##### END AMM multi DELETE section


################################################################################
##### BEGIN AMM multi control form
if ($action == "BLANK")
	{
	$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';
	echo "<TABLE><TR><TD>\n";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
	echo "<br>"._QXZ("AM Message Wildcards Form");
	echo "<center><TABLE width=$section_width cellspacing=3>\n";
	echo "<tr><td align=center colspan=2>\n";
	echo "<br><b>"._QXZ("AM Messages for %1s",0,'',$entry_type).": <a href=\"./admin.php?$mod_link$campaign_id\">$campaign_id</a></b> &nbsp; $NWB#amm_multi$NWE\n";

	echo "<TABLE width=700 cellspacing=3>\n";
	echo "<tr><td>#</td><td>"._QXZ("ACTIVE")."</td><td>"._QXZ("RANK")."</td><td>"._QXZ("WILDCARD")."</td><td>"._QXZ("FIELD")."</td><td>"._QXZ("SUBMIT")."</td></tr>\n";

	$stmt="SELECT amm_id,active,amm_rank,amm_wildcard,amm_description,amm_filename,amm_field from vicidial_amm_multi where campaign_id='$campaign_id' and entry_type='$entry_type' order by amm_rank limit 1000;";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$types_to_print = mysqli_num_rows($rslt);
	$o=0;
	while ($types_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);
		
		$Ramm_id =			$rowx[0];
		$Ractive =			$rowx[1];
		$Ramm_rank =		$rowx[2];
		$Ramm_wildcard =	$rowx[3];
		$Ramm_description =	$rowx[4];
		$Ramm_filename =	$rowx[5];
		$Ramm_field =		$rowx[6];

		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
		else
			{$bgcolor='bgcolor="#'. $SSstd_row1_background .'"';}
		$o++;

		echo "<tr $bgcolor>";
		echo "<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<input type=hidden name=campaign_id value=\"$campaign_id\">\n";
		echo "<input type=hidden name=entry_type value=\"$entry_type\">\n";
		echo "<input type=hidden name=amm_id value=\"$Ramm_id\">\n";
		echo "<input type=hidden name=action value=AMM_MULTI_MODIFY>\n";
		echo "<td><font size=1>$o</td>";
		echo "<td><font size=1><select size=1 name=active><option value='Y'>"._QXZ("Y")."</option><option value='N'>"._QXZ("N")."</option><option SELECTED>$Ractive</option></select></td>";
		echo "<td><font size=1><input type=text size=4 maxlength=3 name=amm_rank value=\"$Ramm_rank\"></td>";
		echo "<td><font size=1><input type=text size=20 maxlength=1000 name=amm_wildcard value=\"$Ramm_wildcard\"></td>";
		echo "<td><font size=1><input type=text size=30 maxlength=255 name=amm_description value=\"$Ramm_description\"></td>";
		echo "<td rowspan=2><font size=1><input style='background-color:#$SSbutton_color' type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'>";
		echo "<BR><BR>";
		if ($Ractive == 'N')
			{echo "<a href=\"$PHP_SELF?DB=$DB&campaign_id=$campaign_id&entry_type=$entry_type&amm_field=$amm_field&amm_id=$Ramm_id&action=AMM_MULTI_DELETE\">"._QXZ("DELETE")."</a>";}
		else
			{echo "<DEL>"._QXZ("DELETE")."</DEL>";}
		echo "</td>";
		echo "</tr>";
		echo "<tr $bgcolor>";
		echo "<td colspan=2><font size=1><select name=amm_field><option>vendor_lead_code</option><option>source_id</option><option>list_id</option><option>phone_number</option><option>title</option><option>first_name</option><option>middle_initial</option><option>last_name</option><option>address1</option><option>address2</option><option>address3</option><option>city</option><option>state</option><option>province</option><option>postal_code</option><option>country_code</option><option>gender</option><option>date_of_birth</option><option>alt_phone</option><option>email</option><option>security_phrase</option><option>comments</option><option>rank</option><option>owner</option><option>called_count</option><option selected>$Ramm_field</option></select></td>";
		echo "<td colspan=3><font size=1>"._QXZ("AM MESSAGE").":<input type=text size=40 maxlength=255 name=amm_filename value=\"$Ramm_filename\"></td>";
		echo "</form>\n";
		echo "</tr>\n";
		}
	echo "</table></center><br>\n";


	echo "<br>"._QXZ("Add a new %1s AM Message",0,'',$amm_field).":<br>";
	echo "</td></tr>\n";

	echo "<TABLE width=700 cellspacing=3>\n";
	echo "<tr $bgcolor>";
	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=hidden name=campaign_id value=\"$campaign_id\">\n";
	echo "<input type=hidden name=entry_type value=\"$entry_type\">\n";
	echo "<input type=hidden name=active value=\"N\">\n";
	echo "<input type=hidden name=action value=AMM_MULTI_NEW>\n";
	echo "<td><font size=1>"._QXZ("RANK").": <input type=text size=4 maxlength=3 name=amm_rank value=\"\"></td>";
	echo "<td><font size=1>"._QXZ("WILDCARD").": <input type=text size=20 maxlength=1000 name=amm_wildcard value=\"\"></td>";
	echo "<td><font size=1>"._QXZ("DESCRIPTION").": <input type=text size=30 maxlength=255 name=amm_description value=\"\"></td>";
	echo "<td rowspan=2><font size=1><input style='background-color:#$SSbutton_color' type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'></td>";
	echo "</tr>";
	echo "<tr $bgcolor>";
	echo "<td><font size=1>"._QXZ("FIELD").": <select name=amm_field><option>vendor_lead_code</option><option>source_id</option><option>list_id</option><option>phone_number</option><option>title</option><option>first_name</option><option>middle_initial</option><option>last_name</option><option>address1</option><option>address2</option><option>address3</option><option>city</option><option>state</option><option>province</option><option>postal_code</option><option>country_code</option><option>gender</option><option>date_of_birth</option><option>alt_phone</option><option>email</option><option>security_phrase</option><option>comments</option><option>rank</option><option>owner</option><option>called_count</option></select></td>";
	echo "<td colspan=2><font size=1>"._QXZ("AM MESSAGE").":<input type=text size=40 maxlength=255 name=amm_filename id=amm_filename value=\"\"> <a href=\"javascript:launch_chooser('amm_filename','date');\">"._QXZ("audio chooser")."</a></td>";
	echo "</form>\n";
	echo "</tr>\n";
	echo "</table></center><br>\n";

	echo "</TABLE></center>\n";
	echo "</TD></TR></TABLE>\n";
	}
### END AMM multi control form


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").": $RUNtime "._QXZ("seconds")." &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

?>

</body>
</html>
