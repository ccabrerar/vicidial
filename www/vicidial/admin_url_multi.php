<?php
# admin_url_multi.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen will control the *url* settings needed when the Campaign or 
# In-Group or List URL setting is set to "ALT". This screen allows for multiple 
# records to be entered and ranked in the order in which they are are to be run.
#
# NOTE: This is currently only designed to work with Dispo Call URL
#
# changes:
# 150709-0612 - First Build
# 160331-2203 - Made URL form input fields longer
# 160508-0815 - Added colors features
# 160801-0657 - Added lists qualifier fields
# 170409-1545 - Added IP List validation code
# 180503-2215 - Added new help display
# 211117-2006 - Added minimum call length field
# 220127-1900 - Added display of the URL ID
# 220222-1959 - Added allow_web_debug system setting
#

$admin_version = '2.14-9';
$build = '220222-1959';

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
if (isset($_GET["url_id"]))						{$url_id=$_GET["url_id"];}
	elseif (isset($_POST["url_id"]))			{$url_id=$_POST["url_id"];}
if (isset($_GET["campaign_id"]))				{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))		{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["entry_type"]))					{$entry_type=$_GET["entry_type"];}
	elseif (isset($_POST["entry_type"]))		{$entry_type=$_POST["entry_type"];}
if (isset($_GET["active"]))						{$active=$_GET["active"];}
	elseif (isset($_POST["active"]))			{$active=$_POST["active"];}
if (isset($_GET["url_type"]))					{$url_type=$_GET["url_type"];}
	elseif (isset($_POST["url_type"]))			{$url_type=$_POST["url_type"];}
if (isset($_GET["url_rank"]))					{$url_rank=$_GET["url_rank"];}
	elseif (isset($_POST["url_rank"]))			{$url_rank=$_POST["url_rank"];}
if (isset($_GET["url_statuses"]))				{$url_statuses=$_GET["url_statuses"];}
	elseif (isset($_POST["url_statuses"]))		{$url_statuses=$_POST["url_statuses"];}
if (isset($_GET["url_lists"]))					{$url_lists=$_GET["url_lists"];}
	elseif (isset($_POST["url_lists"]))			{$url_lists=$_POST["url_lists"];}
if (isset($_GET["url_description"]))			{$url_description=$_GET["url_description"];}
	elseif (isset($_POST["url_description"]))	{$url_description=$_POST["url_description"];}
if (isset($_GET["url_address"]))				{$url_address=$_GET["url_address"];}
	elseif (isset($_POST["url_address"]))		{$url_address=$_POST["url_address"];}
if (isset($_GET["url_call_length"]))			{$url_call_length=$_GET["url_call_length"];}
	elseif (isset($_POST["url_call_length"]))	{$url_call_length=$_POST["url_call_length"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}

if (strlen($action) < 2)
	{$action = 'BLANK';}
if (strlen($DB) < 1)
	{$DB=0;}
$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,enable_languages,language_method,qc_features_active,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$ss_conf_ct = mysqli_num_rows($rslt);
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSenable_languages =			$row[2];
	$SSlanguage_method =			$row[3];
	$SSqc_features_active =			$row[4];
	$SSallow_web_debug =			$row[5];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$url_id = preg_replace('/[^0-9]/','',$url_id);
$active = preg_replace('/[^A-Z]/','',$active);
$url_call_length = preg_replace('/[^0-9]/','',$url_call_length);
$url_rank = preg_replace('/[^-0-9]/','',$url_rank);
$SUBMIT = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$SUBMIT);
$action = preg_replace('/[^-_0-9a-zA-Z]/','',$action);
$url_address = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$url_address);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$campaign_id = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign_id);
	$entry_type = preg_replace('/[^_0-9a-zA-Z]/','',$entry_type);
	$url_type = preg_replace('/[^_0-9a-zA-Z]/','',$url_type);
	$url_statuses = preg_replace('/[^- _0-9a-zA-Z]/','',$url_statuses);
	$url_lists = preg_replace('/[^- _0-9]/','',$url_lists);
	$url_description = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$url_description);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$campaign_id = preg_replace('/[^-_0-9\p{L}]/u','',$campaign_id);
	$entry_type = preg_replace('/[^_0-9\p{L}]/u','',$entry_type);
	$url_type = preg_replace('/[^_0-9\p{L}]/u','',$url_type);
	$url_statuses = preg_replace('/[^- _0-9\p{L}]/u','',$url_statuses);
	$url_lists = preg_replace('/[^- _0-9]/','',$url_lists);
	$url_description = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$url_description);
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
	if ($url_type == 'dispo')
		{$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and dispo_call_url='ALT';";}
	elseif ($url_type == 'start')
		{$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and start_call_url='ALT';";}
	elseif ($url_type == 'noagent')
		{$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and na_call_url='ALT';";}
	else
		{
		echo _QXZ("ERROR: no valid url type defined:") . $url_type;
		exit;
		}
	}
elseif ($entry_type == 'ingroup')
	{
	$ADD =					'3111';
	$hh =					'ingroups';
	$stmt_name="SELECT group_name from vicidial_inbound_groups where group_id='$campaign_id';";
	$mod_link = "ADD=$ADD&group_id=";
	$event_section = 'INGROUPS';
	if ($url_type == 'dispo')
		{$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and dispo_call_url='ALT';";}
	elseif ($url_type == 'start')
		{$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and start_call_url='ALT';";}
	elseif ($url_type == 'noagent')
		{$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and na_call_url='ALT';";}
	elseif ($url_type == 'addlead')
		{$stmt="SELECT count(*) from vicidial_inbound_groups where group_id='$campaign_id' and add_lead_url='ALT';";}
	else
		{
		echo _QXZ("ERROR: no valid url type defined:") . $url_type;
		exit;
		}
	}
elseif ($entry_type == 'list')
	{
	$ADD =					'311';
	$hh =					'lists';
	$stmt_name="SELECT list_name from vicidial_lists where list_id='$campaign_id';";
	$mod_link = "ADD=$ADD&list_id=";
	$event_section = 'LISTS';
	if ($url_type == 'noagent')
		{$stmt="SELECT count(*) from vicidial_lists where list_id='$campaign_id' and na_call_url='ALT';";}
	else
		{
		echo _QXZ("ERROR: no valid url type defined:") . $url_type;
		exit;
		}
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

?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: URL Multi Entry") . " " . $campaign_id . " - " . $camp_name; ?>
<?php 


require("admin_header.php");

if ($camp_multi < 1)
	{
	echo _QXZ("This URL is not set ALT")."$entry_type|$url_type\n";
	exit;
	}

if ($DB > 0)
	{
	echo "$DB,$action,$campaign_id,$url_id,$entry_type,$active,$url_type,$url_rank,$url_statuses,$url_lists,$url_description\n<BR>";
	}


################################################################################
##### BEGIN URL multi NEW section
if ($action == "URL_MULTI_NEW")
	{
	if ( (strlen($active)<1) or (strlen($url_rank)<1) or (strlen($url_statuses)<1) or (strlen($url_description)<1) or (strlen($url_address)<5) )
		{
		echo _QXZ("ERROR: You must fill in all fields").":  |$active|$url_rank|$url_statuses|$url_description|$url_address|\n<BR>";
		exit;
		}
	$stmt="INSERT INTO vicidial_url_multi SET campaign_id='$campaign_id',entry_type='$entry_type',url_type='$url_type',active='$active',url_rank='$url_rank',url_statuses='$url_statuses',url_description='$url_description',url_address='" . mysqli_real_escape_string($link, $url_address) . "';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
	if ($affected_rows > 0)
		{
		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='$event_section', event_type='ADD', record_id='$campaign_id', event_code='ADD MULTI URL', event_sql=\"$SQL_log\", event_notes='$url_type';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 0) {echo "$campaign_id|$stmt\n<BR>";}

		echo "<BR><b>$affected_rows "._QXZ("MULTI URL ADDED")."</b><BR><BR>";
		}
	else
		{echo _QXZ("ERROR: Problem adding URL").":  $affected_rows|$stmt\n<BR>";}
	$action='BLANK';
	}
##### END URL multi NEW section


################################################################################
##### BEGIN URL multi MODIFY section
if ($action == "URL_MULTI_MODIFY")
	{
	if ( (strlen($url_id)<1) or (strlen($active)<1) or (strlen($url_rank)<1) or (strlen($url_statuses)<1) or (strlen($url_description)<1) or (strlen($url_address)<5) )
		{
		echo _QXZ("ERROR: You must fill in all fields").":  |$url_id|$active|$url_rank|$url_statuses|$url_description|$url_address|\n<BR>";
		exit;
		}
	$stmt="SELECT count(*) from vicidial_url_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and url_type='$url_type' and url_id='$url_id';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$url_count=$row[0];
	if ($url_count < 1)
		{
		echo _QXZ("ERROR: URL entry does not exist").":  |$url_id|$campaign_id|$entry_type|$url_type|\n<BR>";
		exit;
		}
	$stmt="UPDATE vicidial_url_multi SET active='$active',url_rank='$url_rank',url_statuses='$url_statuses',url_lists='$url_lists',url_call_length='$url_call_length',url_description='$url_description',url_address='" . mysqli_real_escape_string($link, $url_address) . "' where campaign_id='$campaign_id' and entry_type='$entry_type' and url_type='$url_type' and url_id='$url_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
	if ($affected_rows > 0)
		{
		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='$event_section', event_type='MODIFY', record_id='$campaign_id', event_code='MODIFY MULTI URL', event_sql=\"$SQL_log\", event_notes='$url_type ID: $url_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB > 0) {echo "$campaign_id|$stmt\n<BR>";}

		echo "<BR><b>$affected_rows "._QXZ("MULTI URL MODIFIED")." (ID: $url_id)</b><BR><BR>";
		}
	else
		{echo _QXZ("ERROR: Problem modifying URL").":  $affected_rows|$stmt\n<BR>";}
	$action='BLANK';
	}
##### END URL multi MODIFY section


################################################################################
##### BEGIN URL multi DELETE section
if ($action == "URL_MULTI_DELETE")
	{
	if ( (strlen($url_id)<1) or (strlen($campaign_id)<1) or (strlen($entry_type)<1) or (strlen($url_type)<1) )
		{
		echo _QXZ("ERROR: You must fill in all fields").":  |$url_id|$campaign_id|$entry_type|$url_type|\n<BR>";
		exit;
		}
	$stmt="SELECT count(*) from vicidial_url_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and url_type='$url_type' and url_id='$url_id' and active='N';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$url_count=$row[0];
	if ($url_count < 1)
		{
		echo _QXZ("ERROR: URL entry must be set to not active before you delete it").":  |$url_id|$campaign_id|$entry_type|$url_type|\n<BR>";
		}
	else
		{
		$stmt="DELETE FROM vicidial_url_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and url_type='$url_type' and url_id='$url_id' and active='N';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		if ($DB > 0) {echo "$affected_rows|$stmt\n<BR>";}
		if ($affected_rows > 0)
			{
			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='$event_section', event_type='DELETE', record_id='$campaign_id', event_code='DELETE MULTI URL', event_sql=\"$SQL_log\", event_notes='$url_type ID: $url_id';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB > 0) {echo "$campaign_id|$stmt\n<BR>";}

			echo "<BR><b>$affected_rows "._QXZ("MULTI URL DELETED")." (ID: $url_id)</b><BR><BR>";
			}
		else
			{echo _QXZ("ERROR: Problem deleting URL").":  $affected_rows|$stmt\n<BR>";}
		}
	$action='BLANK';
	}
##### END URL multi DELETE section


################################################################################
##### BEGIN URL multi control form
if ($action == "BLANK")
	{
	$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';
	echo "<TABLE><TR><TD>\n";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
	echo "<br>"._QXZ("Alternate URL Form");
	echo "<center><TABLE width=1020 cellspacing=3>\n";
	echo "<tr><td align=center colspan=2>\n";
	echo "<br><b>"._QXZ("Alternate %1s URLs for %2s",0,'',$url_type,$entry_type).": <a href=\"./admin.php?$mod_link$campaign_id\">$campaign_id</a></b> &nbsp; $NWB#alt_multi_urls$NWE\n";

	echo "<TABLE width=1000 cellspacing=3>\n";
	echo "<tr><td><font size=2><b>#</td><td NOWRAP><font size=1>URL ID</td><td><font size=2><b>"._QXZ("ACTIVE")."</td><td><font size=2><b>"._QXZ("RANK")."</td><td><font size=2><b>"._QXZ("STATUSES")."</td><td><font size=2><b>"._QXZ("LISTS")."</td><td><font size=2><b>"._QXZ("MIN LENGTH")."</td><td><font size=2><b>"._QXZ("DESCRIPTION")."</td><td><font size=2><b>"._QXZ("SUBMIT")."</td></tr>\n";

	$stmt="SELECT url_id,active,url_rank,url_statuses,url_description,url_address,url_lists,url_call_length from vicidial_url_multi where campaign_id='$campaign_id' and entry_type='$entry_type' and url_type='$url_type' order by url_rank limit 1000;";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$types_to_print = mysqli_num_rows($rslt);
	$o=0;
	while ($types_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);
		
		$Rurl_id =			$rowx[0];
		$Ractive =			$rowx[1];
		$Rurl_rank =		$rowx[2];
		$Rurl_statuses =	$rowx[3];
		$Rurl_description =	$rowx[4];
		$Rurl_address =		$rowx[5];
		$Rurl_lists =		$rowx[6];
		$Rurl_call_length =	$rowx[7];

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
		echo "<input type=hidden name=url_type value=\"$url_type\">\n";
		echo "<input type=hidden name=url_id value=\"$Rurl_id\">\n";
		echo "<input type=hidden name=action value=URL_MULTI_MODIFY>\n";
		echo "<td><font size=1>$o</td>";
		echo "<td align=right><font size=1>$Rurl_id &nbsp;</td>";
		echo "<td><font size=1><select size=1 name=active><option value='Y'>"._QXZ("Y")."</option><option value='N'>"._QXZ("N")."</option><option SELECTED>$Ractive</option></select></td>";
		echo "<td><font size=1><input type=text size=3 maxlength=3 name=url_rank value=\"$Rurl_rank\"></td>";
		echo "<td><font size=1><input type=text size=24 maxlength=1000 name=url_statuses value=\"$Rurl_statuses\"></td>";
		echo "<td><font size=1><input type=text size=24 maxlength=1000 name=url_lists value=\"$Rurl_lists\"></td>";
		echo "<td><font size=1><input type=text size=5 maxlength=5 name=url_call_length value=\"$Rurl_call_length\"></td>";
		echo "<td><font size=1><input type=text size=24 maxlength=255 name=url_description value=\"$Rurl_description\"></td>";
		echo "<td rowspan=2><font size=1><input type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'>";
		echo "<BR><BR>";
		if ($Ractive == 'N')
			{echo "<a href=\"$PHP_SELF?DB=$DB&campaign_id=$campaign_id&entry_type=$entry_type&url_type=$url_type&url_id=$Rurl_id&action=URL_MULTI_DELETE\">"._QXZ("DELETE")."</a>";}
		else
			{echo "<DEL>"._QXZ("DELETE")."</DEL>";}
		echo "</td>";
		echo "</tr>";
		echo "<tr $bgcolor>";
		echo "<td colspan=8 NOWRAP><font size=1>"._QXZ("URL").":<input type=text size=125 maxlength=5000 name=url_address value=\"$Rurl_address\"></td>";
		echo "</form>\n";
		echo "</tr>\n";
		}
	echo "</table></center><br>\n";


	echo "<br>"._QXZ("Add a new %1s URL",0,'',$url_type).":<br>";
	echo "</td></tr>\n";

	echo "<TABLE width=700 cellspacing=3>\n";
	echo "<tr $bgcolor>";
	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=hidden name=campaign_id value=\"$campaign_id\">\n";
	echo "<input type=hidden name=entry_type value=\"$entry_type\">\n";
	echo "<input type=hidden name=url_type value=\"$url_type\">\n";
	echo "<input type=hidden name=active value=\"N\">\n";
	echo "<input type=hidden name=action value=URL_MULTI_NEW>\n";
	echo "<td><font size=1>"._QXZ("RANK").": <input type=text size=4 maxlength=3 name=url_rank value=\"\"></td>";
	echo "<td><font size=1>"._QXZ("STATUSES").": <input type=text size=30 maxlength=1000 name=url_statuses value=\"\"></td>";
	echo "<td><font size=1>"._QXZ("DESCRIPTION").": <input type=text size=30 maxlength=255 name=url_description value=\"\"></td>";
	echo "<td rowspan=2><font size=1><input type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'></td>";
	echo "</tr>";
	echo "<tr $bgcolor>";
	echo "<td colspan=3><font size=1>"._QXZ("URL").":<input type=text size=100 maxlength=5000 name=url_address value=\"\"></td>";
	echo "</form>\n";
	echo "</tr>\n";
	echo "</table></center><br>\n";

	echo "</TABLE></center>\n";
	echo "</TD></TR></TABLE>\n";
	}
### END URL multi control form


$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").": $RUNtime "._QXZ("seconds")." &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

?>

</body>
</html>
