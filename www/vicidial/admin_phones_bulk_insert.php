<?php
# admin_phones_bulk_insert.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen will insert phones into your multi-server system with aliases
#
# changes:
# 101230-0501 - First Build
# 110712-0932 - Added extension suffix exception for non-SIP/IAX phones, added HELP
# 120104-2023 - Added webphone options
# 120209-1545 - Added phone context option
# 120223-2249 - Removed logging of good login passwords if webroot writable is enabled
# 120820-1026 - Added webphone option Y_API_LAUNCH
# 130610-1043 - Changed all ereg to preg
# 130621-1724 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0751 - Changed to mysqli PHP functions
# 141007-1145 - Finalized adding QXZ translation to all admin files
# 141229-2101 - Added code for on-the-fly language translations display
# 170409-1532 - Added IP List validation code
# 180503-2015 - Added new help display
# 181130-1304 - Added template option
# 201112-1017 - Fix for side menu issue #1223
# 210827-0907 - Added PJSIP support
# 220222-1942 - Added allow_web_debug system setting
#

$admin_version = '2.14-16';
$build = '220222-1942';

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
if (isset($_GET["servers"]))					{$servers=$_GET["servers"];}
	elseif (isset($_POST["servers"]))			{$servers=$_POST["servers"];}
if (isset($_GET["phones"]))						{$phones=$_GET["phones"];}
	elseif (isset($_POST["phones"]))			{$phones=$_POST["phones"];}
if (isset($_GET["conf_secret"]))				{$conf_secret=$_GET["conf_secret"];}
	elseif (isset($_POST["conf_secret"]))		{$conf_secret=$_POST["conf_secret"];}
if (isset($_GET["pass"]))						{$pass=$_GET["pass"];}
	elseif (isset($_POST["pass"]))				{$pass=$_POST["pass"];}
if (isset($_GET["alias_option"]))				{$alias_option=$_GET["alias_option"];}
	elseif (isset($_POST["alias_option"]))		{$alias_option=$_POST["alias_option"];}
if (isset($_GET["protocol"]))					{$protocol=$_GET["protocol"];}
	elseif (isset($_POST["protocol"]))			{$protocol=$_POST["protocol"];}
if (isset($_GET["local_gmt"]))					{$local_gmt=$_GET["local_gmt"];}
	elseif (isset($_POST["local_gmt"]))			{$local_gmt=$_POST["local_gmt"];}
if (isset($_GET["alias_suffix"]))				{$alias_suffix=$_GET["alias_suffix"];}
	elseif (isset($_POST["alias_suffix"]))		{$alias_suffix=$_POST["alias_suffix"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["is_webphone"]))				{$is_webphone=$_GET["is_webphone"];}
	elseif (isset($_POST["is_webphone"]))		{$is_webphone=$_POST["is_webphone"];}
if (isset($_GET["webphone_dialpad"]))			{$webphone_dialpad=$_GET["webphone_dialpad"];}
	elseif (isset($_POST["webphone_dialpad"]))	{$webphone_dialpad=$_POST["webphone_dialpad"];}
if (isset($_GET["webphone_auto_answer"]))			{$webphone_auto_answer=$_GET["webphone_auto_answer"];}
	elseif (isset($_POST["webphone_auto_answer"]))	{$webphone_auto_answer=$_POST["webphone_auto_answer"];}
if (isset($_GET["use_external_server_ip"]))			{$use_external_server_ip=$_GET["use_external_server_ip"];}
	elseif (isset($_POST["use_external_server_ip"])){$use_external_server_ip=$_POST["use_external_server_ip"];}
if (isset($_GET["phone_context"]))				{$phone_context=$_GET["phone_context"];}
	elseif (isset($_POST["phone_context"]))		{$phone_context=$_POST["phone_context"];}
if (isset($_GET["template_id"]))				{$template_id=$_GET["template_id"];}
	elseif (isset($_POST["template_id"]))		{$template_id=$_POST["template_id"];}

if (strlen($action) < 2)
	{$action = 'BLANK';}
if (strlen($DB) < 1)
	{$DB=0;}
$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,enable_languages,language_method,admin_screen_colors,campaign_cid_areacodes_enabled,sounds_central_control_active,contacts_enabled,enable_auto_reports,allowed_sip_stacks,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$ss_conf_ct = mysqli_num_rows($rslt);
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =						$row[0];
	$webroot_writable =					$row[1];
	$SSenable_languages =				$row[2];
	$SSlanguage_method =				$row[3];
	$SSadmin_screen_colors =			$row[4];
	$SScampaign_cid_areacodes_enabled = $row[5];
	$SSsounds_central_control_active =	$row[6];
	$SScontacts_enabled =				$row[7];
	$SSenable_auto_reports =			$row[8];
	$SSallowed_sip_stacks =				$row[9];
	$SSallow_web_debug =				$row[10];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

$servers = preg_replace("/'|\"|\\\\|;/","",$servers);
$phones = preg_replace("/'|\"|\\\\|;/","",$phones);
$action = preg_replace("/[^-_0-9a-zA-Z]/", "",$action);
$alias_option = preg_replace("/[^-_0-9a-zA-Z]/", "",$alias_option);
$protocol = preg_replace("/[^-_0-9a-zA-Z]/", "",$protocol);
$local_gmt = preg_replace("/[^- \.\,\_0-9a-zA-Z]/","",$local_gmt);
$is_webphone = preg_replace("/[^-_0-9a-zA-Z]/", "",$is_webphone);
$webphone_dialpad = preg_replace("/[^-_0-9a-zA-Z]/", "",$webphone_dialpad);
$webphone_auto_answer = preg_replace("/[^NY]/","",$webphone_auto_answer);
$use_external_server_ip = preg_replace("/[^NY]/","",$use_external_server_ip);
$SUBMIT = preg_replace("/[^-_0-9a-zA-Z]/", "",$SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace("/[^-_0-9a-zA-Z]/", "",$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace("/[^-_0-9a-zA-Z]/", "",$PHP_AUTH_PW);
	$conf_secret = preg_replace("/[^-_0-9a-zA-Z]/", "",$conf_secret);
	$pass = preg_replace("/[^-_0-9a-zA-Z]/", "",$pass);
	$alias_suffix = preg_replace("/[^0-9a-zA-Z]/","",$alias_suffix);
	$phone_context = preg_replace("/[^-\_0-9a-zA-Z]/","",$phone_context);
	$template_id = preg_replace('/[^-_0-9a-zA-Z]/','',$template_id);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$conf_secret = preg_replace("/[^-_0-9\p{L}]/u", "",$conf_secret);
	$pass = preg_replace("/[^-_0-9\p{L}]/u", "",$pass);
	$alias_suffix = preg_replace("/[^0-9\p{L}]/u","",$alias_suffix);
	$phone_context = preg_replace("/[^-\_0-9\p{L}]/u","",$phone_context);
	$template_id = preg_replace('/[^-_0-9\p{L}]/u','',$template_id);
	}

$user = $PHP_AUTH_USER;

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

$rights_stmt = "SELECT ast_delete_phones from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$ast_delete_phones =		$rights_row[0];

# check their permissions
if ( $ast_delete_phones < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to manage phones")."\n";
	exit;
	}

$stmt="SELECT full_name,ast_delete_phones,ast_admin_access,user_level,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGast_delete_phones =		$row[1];
$LOGast_admin_access =		$row[2];
$LOGuser_level =			$row[3];
$LOGuser_group =			$row[4];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$DBout .= "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}


?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: Phones Bulk Insert"); ?>
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
	<B><?php echo _QXZ("Bulk Phones Insert Help"); ?></B>
	<BR><BR>

	<A NAME="servers">
	<BR>
	 <B><?php echo _QXZ("Servers"); ?> -</B> <?php echo _QXZ("This is a list of the server IP addresses of existing servers in the system that you want the phone entries to be populated across. These must be valid IP addresses, they must be active in the system and you must put one per line in this text area input box."); ?>
	<BR><BR>

	<A NAME="phones">
	<BR>
	 <B><?php echo _QXZ("Phones"); ?> -</B> <?php echo _QXZ("This is a list of the phone extensions that you want to be created on all of the servers listed above. The phone extensions should be letters and numbers only, with no special characters and you must put one per line in this text area box."); ?>
	<BR><BR>

	<A NAME="registration_password">
	<BR>
	 <B><?php echo _QXZ("Registration Password"); ?> -</B> <?php echo _QXZ("This is the registration password that will be used for all of the phones that are created. For SIP, PJSIP and IAX2 protocol you should use a complex password for secutiry reasons."); ?>
	<BR><BR>

	<A NAME="login_password">
	<BR>
	 <B><?php echo _QXZ("Login Password"); ?> -</B> <?php echo _QXZ("This is the agent screen login password that will be used for all of the phones that are created."); ?>
	<BR><BR>

	<A NAME="create_alias">
	<BR>
	 <B><?php echo _QXZ("Create Alias Entries"); ?> -</B> <?php echo _QXZ("Setting this option to YES will create a phones alias entry for each of the extensions listed above that will tie all of the same extension entries on each server together allowing phone login load balancing to work in the agent interface."); ?>
	<BR><BR>

	<A NAME="alias_suffix">
	<BR>
	 <B><?php echo _QXZ("Alias Suffix"); ?> -</B> <?php echo _QXZ("This is the suffix that will be added to the extension to form the phone alias id. For example, if the extension is cc100 and the alias suffix is x, then the phone alias id used for that would be cc100x."); ?>
	<BR><BR>

	<A NAME="protocol">
	<BR>
	 <B><?php echo _QXZ("Client Protocol"); ?> -</B> <?php echo _QXZ("This is the phone protocol that will be used for all phones created when submitted. SIP, PJSIP and IAX2 are VOIP protocols that will create conf file entries to allow those phoens to register on the servers."); ?>
	<BR><BR>

	<A NAME="gmt">
	<BR>
	 <B><?php echo _QXZ("Local GMT"); ?> -</B> <?php echo _QXZ("This is the time zone that all of the phones will be created with."); ?>
	<BR><BR>

	<A NAME="phone_context">
	<BR>
	<B><?php echo _QXZ("Phone Context"); ?> -</B> <?php echo _QXZ("This is the dial plan context that this phone will use to dial out. If you are running a call center and you do not want your agents to be able to dial out outside of the ViciDial applicaiton for example, then you would set this field to a dialplan context that does not exist, something like agent-nodial. default is default."); ?>
	<BR><BR>

	<BR>
	<A NAME="is_webphone">
	<BR>
	<B><?php echo _QXZ("Set As Webphone"); ?> -</B>  <?php echo _QXZ("Setting this option to Y will attempt to load a web-based phone when the agent logs into their agent screen. Default is N. The Y_API_LAUNCH option can be used with the agent API to launch the webphone in a separate Iframe or window."); ?>

	<BR>
	<A NAME="webphone_dialpad">
	<BR>
	<B><?php echo _QXZ("Webphone Dialpad"); ?> -</B>  <?php echo _QXZ("This setting allows you to activate or deactivate the dialpad for this webphone. Default is Y for enabled. TOGGLE will allow the user to view and hide the dialpad by clicking a link. This feature is not available on all webphone versions. TOGGLE_OFF will default to not show the dialpad on first load, but will allow the user to show the dialpad by clicking on the dialpad link."); ?>

	<BR>
	<A NAME="webphone_auto_answer">
	<BR>
	<B><?php echo _QXZ("Webphone Auto-Answer"); ?> -</B>  <?php echo _QXZ("This setting allows the web phone to be set to automatically answer calls that come in by setting it to Y, or to have calls ring by setting it to N. Default is Y."); ?>

	<BR>
	<A NAME="use_external_server_ip">
	<BR>
	<B><?php echo _QXZ("Use External Server IP"); ?> -</B>  <?php echo _QXZ("If using as a web phone, you can set this to Y to use the servers External IP to register to instead of the Server IP. Default is empty."); ?>

	<BR>
	<A NAME="template_id">
	<BR>
	<B><?php echo _QXZ("Template ID"); ?> -</B>  <?php echo _QXZ("Use this optoin to assign a phone template to all of the phone entries created by this bulk insert utility. Default is empty."); ?>


	</TD></TR></TABLE>
	</BODY>
	</HTML>
	<?php
	exit;
	}
### END help section



##### BEGIN Set variables to make header show properly #####
$ADD =					'999998';
$hh =					'admin';
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
$admin_color =		'#FFFF99';
$admin_font =		'BLACK';
$admin_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");

if ( ($LOGast_admin_access < 1) or ($LOGuser_level < 8) )
	{
	echo _QXZ("You are not authorized to view this section")."\n";
	exit;
	}


if ($DB > 0)
{
echo "$DB,$action,$servers,$phones,$conf_secret,$pass,$alias_option,$protocol,$logal_gmt,$alias_suffix\n<BR>";
}

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('$PHP_SELF?action=HELP";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

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

if ($SSadmin_screen_colors != 'default')
	{
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
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
		}
	}



################################################################################
##### BEGIN blank add phones form
if ($action == "BLANK")
	{
	echo "<TABLE><TR><TD>\n";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
	echo "<br>"._QXZ("Add Multi-Server Phones Form")."<form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=hidden name=action value=ADD_PHONES_SUBMIT>\n";
	echo "<center><TABLE width=$section_width cellspacing=3>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Servers").": <BR><BR> ("._QXZ("one server_ip per line only").")<BR></td><td align=left><TEXTAREA name=servers ROWS=10 COLS=20></TEXTAREA> $NWB#admin_phones_bulk_insert-servers$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Phones").": <BR><BR> ("._QXZ("one extension per line only").")<BR></td><td align=left><TEXTAREA name=phones ROWS=20 COLS=20></TEXTAREA> $NWB#admin_phones_bulk_insert-phones$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Registration Password").": </td><td align=left><input type=text name=conf_secret size=20 maxlength=20> $NWB#admin_phones_bulk_insert-registration_password$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Login Password").": </td><td align=left><input type=text name=pass size=20 maxlength=20> $NWB#admin_phones_bulk_insert-login_password$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Create Alias Entries").": </td><td align=left><select size=1 name=alias_option>\n";
	echo "<option selected>"._QXZ("YES")."</option>";
	echo "<option>"._QXZ("NO")."</option>";
	echo "</select> $NWB#admin_phones_bulk_insert-create_alias$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Alias Suffix").": </td><td align=left><input type=text name=alias_suffix size=2 maxlength=4> $NWB#admin_phones_bulk_insert-alias_suffix$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Client Protocol").": </td><td align=left><select size=1 name=protocol>";
	if ( ($SSallowed_sip_stacks == 'SIP') or ($SSallowed_sip_stacks == 'SIP_and_PJSIP') ) {echo "<option>SIP</option>";}
	if ( ($SSallowed_sip_stacks == 'PJSIP') or ($SSallowed_sip_stacks == 'SIP_and_PJSIP') ) {echo "<option>PJSIP</option>";}
	echo "<option>Zap</option>";
	echo "<option>IAX2</option>";
	echo "<option value='EXTERNAL'>"._QXZ("EXTERNAL")."</option>";
	echo "</select> $NWB#admin_phones_bulk_insert-protocol$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Local GMT").": </td><td align=left><select size=1 name=local_gmt><option>12.75</option><option>12.00</option><option>11.00</option><option>10.00</option><option>9.50</option><option>9.00</option><option>8.00</option><option>7.00</option><option>6.50</option><option>6.00</option><option>5.75</option><option>5.50</option><option>5.00</option><option>4.50</option><option>4.00</option><option>3.50</option><option>3.00</option><option>2.00</option><option>1.00</option><option>0.00</option><option>-1.00</option><option>-2.00</option><option>-3.00</option><option>-3.50</option><option>-4.00</option><option selected>-5.00</option><option>-6.00</option><option>-7.00</option><option>-8.00</option><option>-9.00</option><option>-10.00</option><option>-11.00</option><option>-12.00</option></select> ("._QXZ("Do NOT Adjust for DST").") $NWB#admin_phones_bulk_insert-gmt$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Phone Context").": </td><td align=left><input type=text name=phone_context size=20 maxlength=20> $NWB#admin_phones_bulk_insert-phone_context$NWE </td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Set As Webphone").": </td><td align=left><select size=1 name=is_webphone><option value='Y'>"._QXZ("Y")."</option><option selected value='N'>"._QXZ("N")."</option><option value='Y_API_LAUNCH'>"._QXZ("Y_API_LAUNCH")."</option></select>$NWB#admin_phones_bulk_insert-is_webphone$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Webphone Dialpad").": </td><td align=left><select size=1 name=webphone_dialpad><option selected value='Y'>"._QXZ("Y")."</option><option value='N'>"._QXZ("N")."</option><option value='TOGGLE'>"._QXZ("TOGGLE")."</option><option value='TOGGLE_OFF'>"._QXZ("TOGGLE_OFF")."</option></select>$NWB#admin_phones_bulk_insert-webphone_dialpad$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Webphone Auto-Answer").": </td><td align=left><select size=1 name=webphone_auto_answer><option selected value='Y'>"._QXZ("Y")."</option><option value='N'>"._QXZ("N")."</option></select>$NWB#admin_phones_bulk_insert-webphone_auto_answer$NWE</td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Use External Server IP").": </td><td align=left><select size=1 name=use_external_server_ip><option value='Y'>"._QXZ("Y")."</option><option selected value='N'>"._QXZ("N")."</option></select>$NWB#admin_phones_bulk_insert-use_external_server_ip$NWE</td></tr>\n";

	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=right>"._QXZ("Template ID")."</a>: </td><td align=left><select size=1 name=template_id>\n";
	$stmt="SELECT template_id,template_name from vicidial_conf_templates $whereLOGadmin_viewable_groupsSQL order by template_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$templates_to_print = mysqli_num_rows($rslt);
	$templates_list='<option value=\'\' SELECTED>--'._QXZ("NONE").'--</option>';
	$o=0;
	while ($templates_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$templates_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
		$o++;
		}
	echo "$templates_list";
#	echo "<option SELECTED value=\"\">---NONE---</option>\n";
	echo "</select>$NWB#admin_phones_bulk_insert-template_id$NWE</td></tr>\n";

	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=center colspan=2><INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'></td></tr>\n";
	echo "<tr bgcolor=#".$SSstd_row4_background."><td align=center colspan=2>"._QXZ("NOTE: Submitting this form will NOT trigger a conf file rebuild")."</td></tr>\n";
	echo "</TABLE></center>\n";
	echo "</TD></TR></TABLE>\n";
	}
### END blank add phones form




################################################################################
##### BEGIN add phones submit
if ($action == "ADD_PHONES_SUBMIT")
	{
	$phones_inserted=0;
	$phone_alias_inserted=0;
	if (strlen($phones) > 2)
		{
		$PN = explode("\n",$phones);
		$PNct = count($PN);

		if (strlen($servers) > 6)
			{
			$SN = explode("\n",$servers);
			$SNct = count($SN);

			$s=0;
			while ($s < $SNct)
				{
				$SN[$s] = preg_replace('/\n|\r|\t| /','',$SN[$s]);
				$server_exists=0;
				$stmt="SELECT count(*) from servers where server_ip='$SN[$s]';";
				if ($DB>0) {echo "$stmt";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$servercount_to_print = mysqli_num_rows($rslt);
				if ($servercount_to_print > 0) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$server_exists =	$rowx[0];
					}
				if ($server_exists > 0)
					{
					$p=0;
					while ($p < $PNct)
						{
						$PN[$p] = preg_replace('/\n|\r|\t| /','',$PN[$p]);
						$phone_exists=0;
						$stmt="SELECT count(*) from phones where server_ip='$SN[$s]' and extension='$PN[$p]';";
						if ($DB>0) {echo "$stmt";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$phonecount_to_print = mysqli_num_rows($rslt);
						if ($phonecount_to_print > 0) 
							{
							$rowx=mysqli_fetch_row($rslt);
							$phone_exists =	$rowx[0];
							}
						if ( ($phone_exists < 1) and (strlen($PN[$p]) > 1) )
							{
							if ($s < 1) {$dialplan_prefix='';   $login_suffix = 'a';}
							else {$dialplan_prefix = $s;}
							if ($s == '1') {$login_suffix = 'b';}
							if ($s == '2') {$login_suffix = 'c';}
							if ($s == '3') {$login_suffix = 'd';}
							if ($s == '4') {$login_suffix = 'e';}
							if ($s == '5') {$login_suffix = 'f';}
							if ($s == '6') {$login_suffix = 'g';}
							if ($s == '7') {$login_suffix = 'h';}
							if ($s == '8') {$login_suffix = 'i';}
							if ($s == '9') {$login_suffix = 'j';}
							if ($s == '10') {$login_suffix = 'k';}
							if ($s == '11') {$login_suffix = 'l';}
							if ($s == '12') {$login_suffix = 'm';}
							if ($s == '13') {$login_suffix = 'n';}
							if ($s == '14') {$login_suffix = 'o';}
							if ($s == '15') {$login_suffix = 'p';}
							if ($s == '16') {$login_suffix = 'q';}
							if ($s == '17') {$login_suffix = 'r';}
							if ($s == '18') {$login_suffix = 's';}
							if ($s == '19') {$login_suffix = 't';}
							if ($s == '20') {$login_suffix = 'u';}
							if ($s == '21') {$login_suffix = 'v';}
							if ($s == '22') {$login_suffix = 'w';}
							if ($s == '23') {$login_suffix = 'x';}
							if ($s == '24') {$login_suffix = 'y';}
							if ($s == '25') {$login_suffix = 'z';}
							if ($s == '26') {$login_suffix = 'aa';}
							if ($s == '27') {$login_suffix = 'ab';}
							if ($s == '28') {$login_suffix = 'ac';}
							if ($s == '29') {$login_suffix = 'ad';}
							if ($s == '30') {$login_suffix = 'ae';}
							if ($s == '31') {$login_suffix = 'af';}
							if ($s == '32') {$login_suffix = 'ag';}
							if ($s == '33') {$login_suffix = 'ah';}
							if ($s == '34') {$login_suffix = 'ai';}
							if ($s == '35') {$login_suffix = 'aj';}
							if ($s == '36') {$login_suffix = 'ak';}
							if ($s == '37') {$login_suffix = 'al';}
							if ($s == '38') {$login_suffix = 'am';}
							if ($s == '39') {$login_suffix = 'an';}
							if ($s == '40') {$login_suffix = 'ao';}
							if ($s == '41') {$login_suffix = 'ap';}
							if ($s == '42') {$login_suffix = 'aq';}
							if ($s == '43') {$login_suffix = 'ar';}
							if ($s == '44') {$login_suffix = 'as';}
							if ($s == '45') {$login_suffix = 'at';}
							if ($s == '46') {$login_suffix = 'au';}
							if ($s == '47') {$login_suffix = 'av';}
							if ($s == '48') {$login_suffix = 'aw';}
							if ($s >= 49) {$login_suffix = 'ax';}

							$extension =		$PN[$p];
							if ( ($protocol == 'SIP') or ($protocol == 'PJSIP') or ($protocol == 'IAX2') )
								{$dialplan_number =	"$dialplan_prefix$PN[$p]";}
							else
								{$dialplan_number =	"$PN[$p]";}
							if (strlen($phone_context) < 1)
								{$phone_context = 'default';}
							$dialplan_number = preg_replace('/\D/', '', $dialplan_number);
							$voicemail_id =		$PN[$p];	$voicemail_id = preg_replace('/\D/', '', $voicemail_id);
							$phone_server_ip =	$SN[$s];
							$login =			"$PN[$p]$login_suffix";
							$phone_type =		"CCagent";
							$fullname =			"ext $PN[$p]";

							$stmt = "INSERT INTO phones (extension,dialplan_number,voicemail_id,server_ip,login,pass,status,active,phone_type,fullname,protocol,local_gmt,outbound_cid,conf_secret,is_webphone,webphone_dialpad,webphone_auto_answer,use_external_server_ip,phone_context,template_id) values('$extension','$dialplan_number','$voicemail_id','$phone_server_ip','$login','$pass','ACTIVE','Y','$phone_type','$fullname','$protocol','$local_gmt','0000000000','$conf_secret','$is_webphone','$webphone_dialpad','$webphone_auto_answer','$use_external_server_ip','$phone_context','$template_id');";
							$rslt=mysql_to_mysqli($stmt, $link);
							$affected_rows = mysqli_affected_rows($link);
							if ($DB > 0) {echo "$s|$p|$SN[$s]|$PN[$p]|$affected_rows|$stmt\n<BR>";}

							if ($affected_rows > 0)
								{
								$phone_alias_entry[$p] .= "$login,";

								### LOG INSERTION Admin Log Table ###
								$SQL_log = "$stmt|";
								$SQL_log = preg_replace('/;/','',$SQL_log);
								$SQL_log = addslashes($SQL_log);
								$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='PHONES', event_type='ADD', record_id='$PN[$p]', event_code='ADMIN BULK ADD PHONE', event_sql=\"$SQL_log\", event_notes='$SN[$s]|$PN[$p]';";
							#	if ($DB) {echo "|$stmt|\n";}
								$rslt=mysql_to_mysqli($stmt, $link);

								$phones_inserted++;
								}
							else
								{echo _QXZ("ERROR: Problem inserting phone").":  $affected_rows|$stmt\n<BR>";}
							}
						else
							{echo _QXZ("ERROR: Phone already exists").":  $SN[$s]|$PN[$p]|$phone_exists\n<BR>";}
						$p++;
						}
					}
				else
					{echo _QXZ("ERROR: Server does not exist").": $SN[$s]|$server_exists\n<BR>";}
				$s++;
				}

			if ( ($phones_inserted > 0) and ($alias_option == 'YES') )
				{
				$p=0;
				while ($p < $PNct)
					{
					if ( (strlen($phone_alias_entry[$p]) > 1) and (strlen($PN[$p]) > 1) )
						{
						$phone_alias_entry[$p] = preg_replace('/,$/','',$phone_alias_entry[$p]);

						$stmt="INSERT INTO phones_alias (alias_id,alias_name,logins_list) values('$PN[$p]$alias_suffix','$PN[$p]','$phone_alias_entry[$p]');";
						$rslt=mysql_to_mysqli($stmt, $link);
						$affected_rows = mysqli_affected_rows($link);
						if ($DB > 0) {echo "$p|$phone_alias_entry[$p]|$PN[$p]|$affected_rows|$stmt\n<BR>";}

						if ($affected_rows > 0)
							{
							### LOG INSERTION Admin Log Table ###
							$SQL_log = "$stmt|";
							$SQL_log = preg_replace('/;/','',$SQL_log);
							$SQL_log = addslashes($SQL_log);
							$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='PHONEALIASES', event_type='ADD', record_id='$alias_id', event_code='ADMIN ADD BULK PHONE ALIAS', event_sql=\"$SQL_log\", event_notes='';";
						#	if ($DB) {echo "|$stmt|\n";}
							$rslt=mysql_to_mysqli($stmt, $link);

							$phone_alias_inserted++;
							}
						else
							{echo _QXZ("ERROR: Problem inserting phone alias").":  $affected_rows|$stmt\n<BR>";}
						}
					$p++;
					}
				}
			
			echo _QXZ("Phones Inserted").":: $phones_inserted\n<BR>";
			echo _QXZ("Phones Aliases Inserted").":: $phone_alias_inserted\n<BR>";
			echo _QXZ("You now need to manually trigger a conf file rebuild from the System Settings screen").":\n<BR>";
			echo "<BR><a href=\"$PHP_SELF\">Start Over</a><BR>\n";
			}
		else
			{echo _QXZ("ERROR: You must enter servers").":: $servers\n<BR>";}
		}
	else
		{echo _QXZ("ERROR: You must enter extensions").":: $phones\n<BR>";}
	}
### END add phones submit







$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").":: $RUNtime "._QXZ("seconds").": &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

?>

</body>
</html>
