<?php
# admin_soundboard.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen manages the optional soundboard tables in ViciDial
#
# changes:
# 140707-1955 - First Build
# 141007-1147 - Finalized adding QXZ translation to all admin files
# 141111-0609 - Changes to comply with admin changes
# 141126-0906 - Pass through DB variable to example iframe
# 150808-2046 - Added compatibility for custom fields data option
# 160121-1716 - Filter out period from audio filenames, don't allow duplicate level-1 audiofiles
# 160330-1549 - navigation changes and fixes
# 160404-0937 - design changes
# 160429-1122 - Added admin_row_click option
# 161103-1605 - Updated code for new admin links
# 161111-1646 - Added HIDENUMBERS display option, Font size, button type and layout options
# 170409-1549 - Added IP List validation code
# 180503-2215 - Added new help display
# 180618-2300 - Modified calls to audio file chooser function
# 220222-1512 - Added allow_web_debug system setting
#

$admin_version = '2.14-14';
$build = '220222-1512';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["ADD"]))						{$ADD=$_GET["ADD"];}
	elseif (isset($_POST["ADD"]))				{$ADD=$_POST["ADD"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["stage"]))						{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))				{$stage=$_POST["stage"];}
if (isset($_GET["user_group"]))					{$user_group=$_GET["user_group"];}
	elseif (isset($_POST["user_group"]))		{$user_group=$_POST["user_group"];}
if (isset($_GET["action"]))						{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))			{$action=$_POST["action"];}
if (isset($_GET["soundboard_id"]))				{$soundboard_id=$_GET["soundboard_id"];}
	elseif (isset($_POST["soundboard_id"]))		{$soundboard_id=$_POST["soundboard_id"];}
if (isset($_GET["avatar_name"]))				{$avatar_name=$_GET["avatar_name"];}
	elseif (isset($_POST["avatar_name"]))		{$avatar_name=$_POST["avatar_name"];}
if (isset($_GET["avatar_notes"]))				{$avatar_notes=$_GET["avatar_notes"];}
	elseif (isset($_POST["avatar_notes"]))		{$avatar_notes=$_POST["avatar_notes"];}
if (isset($_GET["avatar_api_user"]))			{$avatar_api_user=$_GET["avatar_api_user"];}
	elseif (isset($_POST["avatar_api_user"]))	{$avatar_api_user=$_POST["avatar_api_user"];}
if (isset($_GET["avatar_api_pass"]))			{$avatar_api_pass=$_GET["avatar_api_pass"];}
	elseif (isset($_POST["avatar_api_pass"]))	{$avatar_api_pass=$_POST["avatar_api_pass"];}
if (isset($_GET["active"]))						{$active=$_GET["active"];}
	elseif (isset($_POST["active"]))			{$active=$_POST["active"];}
if (isset($_GET["audio_functions"]))			{$audio_functions=$_GET["audio_functions"];}
	elseif (isset($_POST["audio_functions"]))	{$audio_functions=$_POST["audio_functions"];}
if (isset($_GET["audio_display"]))				{$audio_display=$_GET["audio_display"];}
	elseif (isset($_POST["audio_display"]))		{$audio_display=$_POST["audio_display"];}
if (isset($_GET["audio_filename"]))				{$audio_filename=$_GET["audio_filename"];}
	elseif (isset($_POST["audio_filename"]))	{$audio_filename=$_POST["audio_filename"];}
if (isset($_GET["audio_name"]))					{$audio_name=$_GET["audio_name"];}
	elseif (isset($_POST["audio_name"]))		{$audio_name=$_POST["audio_name"];}
if (isset($_GET["rank"]))						{$rank=$_GET["rank"];}
	elseif (isset($_POST["rank"]))				{$rank=$_POST["rank"];}
if (isset($_GET["level"]))						{$level=$_GET["level"];}
	elseif (isset($_POST["level"]))				{$level=$_POST["level"];}
if (isset($_GET["parent_audio_filename"]))			{$parent_audio_filename=$_GET["parent_audio_filename"];}
	elseif (isset($_POST["parent_audio_filename"]))	{$parent_audio_filename=$_POST["parent_audio_filename"];}
if (isset($_GET["parent_rank"]))				{$parent_rank=$_GET["parent_rank"];}
	elseif (isset($_POST["parent_rank"]))		{$parent_rank=$_POST["parent_rank"];}
if (isset($_GET["parent_filename"]))			{$parent_filename=$_GET["parent_filename"];}
	elseif (isset($_POST["parent_filename"]))	{$parent_filename=$_POST["parent_filename"];}
if (isset($_GET["source_avatar_id"]))			{$source_avatar_id=$_GET["source_avatar_id"];}
	elseif (isset($_POST["source_avatar_id"]))	{$source_avatar_id=$_POST["source_avatar_id"];}
if (isset($_GET["copy_option"]))				{$copy_option=$_GET["copy_option"];}
	elseif (isset($_POST["copy_option"]))		{$copy_option=$_POST["copy_option"];}
if (isset($_GET["soundboard_layout"]))			{$soundboard_layout=$_GET["soundboard_layout"];}
	elseif (isset($_POST["soundboard_layout"]))	{$soundboard_layout=$_POST["soundboard_layout"];}
if (isset($_GET["columns_limit"]))				{$columns_limit=$_GET["columns_limit"];}
	elseif (isset($_POST["columns_limit"]))		{$columns_limit=$_POST["columns_limit"];}
if (isset($_GET["ConFiRm"]))					{$ConFiRm=$_GET["ConFiRm"];}
	elseif (isset($_POST["ConFiRm"]))			{$ConFiRm=$_POST["ConFiRm"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,auto_dial_limit,user_territories_active,allow_custom_dialplan,callcard_enabled,admin_modify_refresh,nocache_admin,webroot_writable,allow_emails,active_modules,sounds_central_control_active,qc_features_active,contacts_enabled,enable_languages,active_modules,agent_soundboards,language_method,enable_auto_reports,campaign_cid_areacodes_enabled,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSauto_dial_limit =			$row[1];
	$SSuser_territories_active =	$row[2];
	$SSallow_custom_dialplan =		$row[3];
	$SScallcard_enabled =			$row[4];
	$SSadmin_modify_refresh =		$row[5];
	$SSnocache_admin =				$row[6];
	$SSwebroot_writable =			$row[7];
	$SSemail_enabled =				$row[8];
	$SSactive_modules =				$row[9];
	$sounds_central_control_active =$row[10];
	$SSqc_features_active =			$row[11];
	$SScontacts_enabled =			$row[12];
	$SSenable_languages =			$row[13];
	$SSactive_modules =				$row[14];
	$SSagent_soundboards =			$row[15];
	$SSlanguage_method =			$row[16];
	$SSenable_auto_reports =		$row[17];
	$SScampaign_cid_areacodes_enabled = $row[18];
	$SSallow_web_debug =			$row[19];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

if (strlen($DB) < 1)
	{$DB=0;}
$modify_refresh_set=0;

$DB = preg_replace('/[^0-9]/','',$DB);
$rank = preg_replace('/[^0-9]/','',$rank);
$level = preg_replace('/[^0-9]/','',$level);
$parent_rank = preg_replace('/[^0-9]/','',$parent_rank);
$columns_limit = preg_replace('/[^0-9]/','',$columns_limit);
$active = preg_replace('/[^NY]/','',$active);
$ConFiRm = preg_replace('/[^0-9a-zA-Z]/','',$ConFiRm);
$name_position = preg_replace('/[^0-9a-zA-Z]/','',$name_position);
$multi_position = preg_replace('/[^0-9a-zA-Z]/','',$multi_position);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/','',$SUBMIT);
$ADD = preg_replace('/[^-_0-9a-zA-Z]/','',$ADD);
$action = preg_replace('/[^-_0-9a-zA-Z]/','',$action);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$avatar_api_user = preg_replace('/[^-_0-9a-zA-Z]/','',$avatar_api_user);
	$avatar_api_pass = preg_replace('/[^-_0-9a-zA-Z]/','',$avatar_api_pass);
	$source_avatar_id = preg_replace('/[^-_0-9a-zA-Z]/','',$source_avatar_id);
	$soundboard_id = preg_replace('/[^_0-9a-zA-Z]/','',$soundboard_id);
	$copy_option = preg_replace('/[^_0-9a-zA-Z]/','',$copy_option);
	$audio_functions = preg_replace('/[^-_0-9a-zA-Z]/', '',$audio_functions);
	$audio_display = preg_replace('/[^-_0-9a-zA-Z]/', '',$audio_display);
	$stage = preg_replace('/[^-_0-9a-zA-Z]/', '',$stage);
	$user_group = preg_replace('/[^-_0-9a-zA-Z]/', '',$user_group);
	$soundboard_layout = preg_replace('/[^-_0-9a-zA-Z]/', '',$soundboard_layout);
	$avatar_name = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$avatar_name);
	$avatar_notes = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$avatar_notes);
	$audio_name = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$audio_name);
	$audio_filename = preg_replace('/[^-\|\,\_0-9a-zA-Z]/', '',$audio_filename);
	$parent_audio_filename = preg_replace('/[^-\|\,\_0-9a-zA-Z]/', '',$parent_audio_filename);
	$parent_filename = preg_replace('/[^-\|\,\_0-9a-zA-Z]/', '',$parent_filename);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$avatar_api_user = preg_replace('/[^-_0-9\p{L}]/u','',$avatar_api_user);
	$avatar_api_pass = preg_replace('/[^-_0-9\p{L}]/u','',$avatar_api_pass);
	$source_avatar_id = preg_replace('/[^-_0-9\p{L}]/u','',$source_avatar_id);
	$soundboard_id = preg_replace('/[^_0-9\p{L}]/u','',$soundboard_id);
	$copy_option = preg_replace('/[^_0-9\p{L}]/u','',$copy_option);
	$audio_functions = preg_replace('/[^-_0-9\p{L}]/u', '',$audio_functions);
	$audio_display = preg_replace('/[^-_0-9\p{L}]/u', '',$audio_display);
	$stage = preg_replace('/[^-_0-9\p{L}]/u', '',$stage);
	$user_group = preg_replace('/[^-_0-9\p{L}]/u', '',$user_group);
	$soundboard_layout = preg_replace('/[^-_0-9\p{L}]/u', '',$soundboard_layout);
	$avatar_name = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$avatar_name);
	$avatar_notes = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$avatar_notes);
	$audio_name = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$audio_name);
	$audio_filename = preg_replace('/[^-\|\,\_0-9\p{L}]/u', '',$audio_filename);
	$parent_audio_filename = preg_replace('/[^-\|\,\_0-9\p{L}]/u', '',$parent_audio_filename);
	$parent_filename = preg_replace('/[^-\|\,\_0-9\p{L}]/u', '',$parent_filename);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$user = $PHP_AUTH_USER;

$admin_lists_custom = 'admin_lists_custom.php';

if (file_exists('options.php'))
	{require('options.php');}

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

$rights_stmt = "SELECT modify_audiostore,selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$modify_audiostore =		$rights_row[0];
$VUselected_language =		$rights_row[1];

# check their permissions
if ( $modify_audiostore < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify audio")."\n";
	exit;
	}

$stmt="SELECT full_name,modify_scripts,user_level,user_group,qc_enabled from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGmodify_scripts =		$row[1];
$LOGuser_level =			$row[2];
$LOGuser_group =			$row[3];
$qc_auth =					$row[4];

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];


?>
<html>
<head>

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

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
if ( ($SSadmin_modify_refresh > 1) and (preg_match("/^3/",$ADD)) )
	{
	$modify_refresh_set=1;
	if (preg_match("/^3/",$ADD)) {$modify_url = "$PHP_SELF?$QUERY_STRING";}
	echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$SSadmin_modify_refresh;URL=$modify_url\">\n";
	}
?>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">

<title><?php echo _QXZ("ADMINISTRATION: Audio Soundboards"); ?>
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
	<TABLE WIDTH=98% BGCOLOR="#E6E6E6" cellpadding=2 cellspacing=4><TR><TD ALIGN=LEFT><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=2>


	<BR>
	<A NAME="soundboard-soundboard_id">
	<BR>
	<B><?php echo _QXZ("Soundboard ID"); ?> -</B> <?php echo _QXZ("The unique ID of the soundboard, must contain no spaces or special characters other than letters numbers dashes or underscores."); ?>

	<BR>
	<A NAME="soundboard-avatar_name">
	<BR>
	<B><?php echo _QXZ("Soundboard Name"); ?> -</B> <?php echo _QXZ("The name of the soundboard, will be displayed to the agent at the top of the Soundboard control screen."); ?>

	<BR>
	<A NAME="soundboard-avatar_notes">
	<BR>
	<B><?php echo _QXZ("Soundboard Notes"); ?> -</B> <?php echo _QXZ("For administrative notes only, not displayed to agents."); ?>
	
	<BR>
	<A NAME="soundboard-avatar_api_user">
	<BR>
	<B><?php echo _QXZ("Soundboard API User"); ?> -</B> <?php echo _QXZ("The system user that will trigger the soundboard audio events, must be a valid user in the system with permission to use the API."); ?>

	<BR>
	<A NAME="soundboard-avatar_api_pass">
	<BR>
	<B><?php echo _QXZ("Soundboard API Pass"); ?> -</B> <?php echo _QXZ("The password for the system user that will trigger the soundboard audio events."); ?>

	<BR>
	<A NAME="soundboard-active">
	<BR>
	<B><?php echo _QXZ("Active"); ?> -</B> <?php echo _QXZ("For this soundboard to be used by agents, it must be set to Active Y. Default is Y."); ?>

	<BR>
	<A NAME="soundboard-audio_functions">
	<BR>
	<B><?php echo _QXZ("Audio Functions"); ?> -</B> <?php echo _QXZ("The list of audio functions that you want the soundboard to allow the agents to perform, separated by dashes. Currently non-functional. Default is PLAY-STOP-RESTART."); ?>

	<BR>
	<A NAME="soundboard-audio_display">
	<BR>
	<B><?php echo _QXZ("Audio Display"); ?> -</B> <?php echo _QXZ("What you want to show up to the agents on the buttons to play the audio, separated by dashes. FILE will display the filename of the audio file. NAME will display the Soundboard Name. HIDENUMBERS will not show the number of each button on the agent screen. You can choose any of these or all together. Default is FILE-NAME."); ?>

	<BR>
	<A NAME="soundboard-soundboard_layout">
	<BR>
	<B><?php echo _QXZ("Layout"); ?> -</B> <?php echo _QXZ("The layout of the agent soundboard. The -default- layout is designed to build from the top down, filling the agent script frame. The -columns- layout is designed to use separated columns of buttons. Default is default. If you are using a -columns- layout, you can also set the maximum number of columns per row section with the Columns Limit setting"); ?>

	<BR>
	<A NAME="soundboard-user_group">
	<BR>
	<B><?php echo _QXZ("User Group"); ?> -</B> <?php echo _QXZ("Administrative User Group associated with this soundboard."); ?>

	<BR>
	<A NAME="soundboard-audio_filename">
	<BR>
	<B><?php echo _QXZ("Audio Filename"); ?> -</B> <?php echo _QXZ("Filename of the audio file you want to play, without a file extension. Must be present in the Audio Store to work properly. Can be displayed to the agent on the audio buttons if FILE is in the Audio Display field."); ?>

	<BR>
	<A NAME="soundboard-audio_name">
	<BR>
	<B><?php echo _QXZ("Audio Name"); ?> -</B> <?php echo _QXZ("Descriptive name of the audio file you want to play. Can be displayed to the agent on the audio buttons if NAME is in the Audio Display field."); ?>

	<BR>
	<A NAME="soundboard-rank">
	<BR>
	<B><?php echo _QXZ("Rank"); ?> -</B> <?php echo _QXZ("The vertical order in which the audio file will be displayed to the agent."); ?>

	<BR>
	<A NAME="soundboard-h_ord">
	<BR>
	<B><?php echo _QXZ("Horz"); ?> -</B> <?php echo _QXZ("The horizontal order in which the audio file will be displayed to the agent."); ?>

	<BR>
	<A NAME="soundboard-type">
	<BR>
	<B><?php echo _QXZ("Type"); ?> -</B> <?php echo _QXZ("Either a clickable button that will play a sound, a header that will not be clickable and will only show the NAME of the entry or a space that will leave a blank spacer with no text. The header will also have white text on a black background. The -head2r- option is a header that will extend into the second row of columns below it."); ?>

	<BR>
	<A NAME="soundboard-font">
	<BR>
	<B><?php echo _QXZ("Font"); ?> -</B> <?php echo _QXZ("The font the button will be displayed with, the bigger the number, the bigger the font. A -B- in the option will bold the text. An -I- in the option will italicize the text."); ?>

	<BR>
	<A NAME="soundboard-level">
	<BR>
	<B><?php echo _QXZ("Level"); ?> -</B> <?php echo _QXZ("Primary or secondary level of the audio. Level 2 audio files are always below a level 1 audio file."); ?>

	<BR>
	<A NAME="soundboard-add">
	<BR>
	<B><?php echo _QXZ("Add"); ?> -</B> <?php echo _QXZ("Put a new audio file on the primary level 1 or underneath a level 1 in the secondary level 2 position."); ?>

	<BR>
	<A NAME="soundboard-script_example">
	<BR>
	<B><?php echo _QXZ("Script Example"); ?> -</B>
	<BR>
	<DIV style="height:120px;width:550px;background:white;overflow:scroll;font-size:12px;font-family:sans-serif;" id=iframe_example>
	&#60;iframe src="./vdc_soundboard_display.php?user=--A--user--B--&#38;pass=--A--pass--B--&#38;bcrypt=OFF&#38;soundboard_id=<?php echo $soundboard_id ?>" style="background-color:transparent;" scrolling="auto" frameborder="0" allowtransparency="true" width="100%" height="500"&#62;
	&#60;/iframe&#62;
	</DIV>
	<BR>

	</TD></TR></TABLE>
	</BODY>
	</HTML>
	<?php
	exit;
	}
### END help section





##### BEGIN Set variables to make header show properly #####
#$ADD =					'100';
$hh =					'admin';
$sh =					'soundboard';
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
$admin_color =		'#E6E6E6';
$soundboard_color =	'#E6E6E6';
$avatar_color =		'#C6C6C6';
$avatar_font =		'BLACK';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

if ( (!isset($ADD)) or (strlen($ADD)<1) )   {$ADD="162000000000";}

if ($ADD==162111111111)	{$hh='admin';	$sh='soundboard';	echo _QXZ("ADD SOUNDBOARD ENTRY");}
if ($ADD==162211111111)	{$hh='admin';	$sh='soundboard';	echo _QXZ("COPY SOUNDBOARD ENTRY");}
if ($ADD==262111111111)	{$hh='admin';	$sh='soundboard';	echo _QXZ("ADDING NEW SOUNDBOARD ENTRY");}
if ($ADD==262211111111)	{$hh='admin';	$sh='soundboard';	echo _QXZ("COPYING NEW SOUNDBOARD ENTRY");}
if ($ADD==362111111111)	{$hh='admin';	$sh='soundboard';	echo _QXZ("MODIFY SOUNDBOARD ENTRY");}
if ($ADD==462111111111)	{$hh='admin';	$sh='soundboard';	echo _QXZ("MODIFY SOUNDBOARD ENTRY");}
if ($ADD==562111111111)	{$hh='admin';	$sh='soundboard';	echo _QXZ("DELETE SOUNDBOARD ENTRY");}
if ($ADD==662111111111)	{$hh='admin';	$sh='soundboard';	echo _QXZ("DELETE SOUNDBOARD ENTRY");}
if ($ADD==162000000000)	{$hh='admin';	$sh='soundboard';	echo _QXZ("SOUNDBOARD LIST");}
if ($ADD==762000000000)	{$hh='admin';	$sh='soundboard';	echo _QXZ("ADMIN LOG");}


require("admin_header.php");

if ( ($LOGmodify_scripts < 1) or ($LOGuser_level < 8) )
	{
	echo _QXZ("You are not authorized to view this section")."\n";
	exit;
	}

if ( (!preg_match("/soundboard/i",$SSactive_modules)) and ($SSagent_soundboards < 1) )
	{
	echo "<B><font color=red>"._QXZ("ERROR: Soundboards are not active on this system")."</B></font>\n";
	exit;
	}


# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('$PHP_SELF?soundboard_id=$soundboard_id&action=HELP";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";



##### user group permissions
$admin_viewable_groupsALL=0;
$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
$valLOGadmin_viewable_groupsSQL='';
$vmLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$valLOGadmin_viewable_groupsSQL = "and val.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$vmLOGadmin_viewable_groupsSQL = "and vm.user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}
else 
	{$admin_viewable_groupsALL=1;}
$regexLOGadmin_viewable_groups = " $LOGadmin_viewable_groups ";

$UUgroups_list='';
if ($admin_viewable_groupsALL > 0)
	{$UUgroups_list .= "<option value=\"---ALL---\">"._QXZ("All Admin User Groups")."</option>\n";}
$stmt="SELECT user_group,group_name from vicidial_user_groups $whereLOGadmin_viewable_groupsSQL order by user_group;";
if ($DB) {echo "$stmt\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$UUgroups_to_print = mysqli_num_rows($rslt);
$o=0;
while ($UUgroups_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$UUgroups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$o++;
	}

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}


######################
# ADD=162111111111 display the ADD NEW SOUNDBOARD ENTRY SCREEN
######################

if ($ADD==162111111111)
	{
	if ( ($modify_audiostore==1) and ($LOGmodify_scripts==1) )
		{
		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		echo "<br>"._QXZ("ADD NEW SOUNDBOARD ENTRY")."<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=ADD value=262111111111>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<center><TABLE width=$section_width cellspacing=3>\n";

		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard ID").": </td><td align=left><input type=text name=soundboard_id size=50 maxlength=100>$NWB#soundboard-soundboard_id$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard Name").": </td><td align=left><input type=text name=avatar_name size=70 maxlength=255>$NWB#soundboard-avatar_name$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard Notes").": </td><td align=left><input type=text name=avatar_notes size=70 maxlength=1000>$NWB#soundboard-avatar_name$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard API User").": </td><td align=left><input type=text name=avatar_api_user size=20 maxlength=20>$NWB#soundboard-avatar_api_user$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard API Pass").": </td><td align=left><input type=password name=avatar_api_pass size=40 maxlength=100>$NWB#soundboard-avatar_api_pass$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Admin User Group").": </td><td align=left><select size=1 name=user_group>\n";
		echo "$UUgroups_list";
		echo "<option SELECTED value=\"---ALL---\">"._QXZ("All Admin User Groups")."</option>\n";
		echo "</select>$NWB#soundboard-user_group$NWE</td></tr>\n";

		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=center colspan=2><input type=submit name=submit VALUE='"._QXZ("SUBMIT")."'></td></tr>\n";
		echo "</TABLE></center>\n";
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}





######################
# ADD=162211111111 display the COPY NEW SOUNDBOARD ENTRY SCREEN
######################

if ($ADD==162211111111)
	{
	if ( ($modify_audiostore==1) and ($LOGmodify_scripts==1) )
		{
		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		echo "<br>"._QXZ("COPY A SOUNDBOARD ENTRY")."<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=ADD value=262211111111>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<center><TABLE width=$section_width cellspacing=3>\n";

		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard ID").": </td><td align=left><input type=text name=soundboard_id size=50 maxlength=100>$NWB#soundboard-soundboard_id$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard Name").": </td><td align=left><input type=text name=avatar_name size=70 maxlength=255>$NWB#soundboard-avatar_name$NWE</td></tr>\n";

		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Source Soundboard ID").": </td><td align=left><select size=1 name=source_avatar_id>\n";

		$stmt="SELECT avatar_id,avatar_name from vicidial_avatars $whereLOGadmin_viewable_groupsSQL order by avatar_id;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$groups_to_print = mysqli_num_rows($rslt);
		$groups_list='';

		$o=0;
		while ($groups_to_print > $o) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$groups_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
			$o++;
			}
		echo "$groups_list";
		echo "</select>$NWB#soundboard-soundboard_id$NWE</td></tr>\n";

		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=center colspan=2><input type=submit name=submit VALUE='"._QXZ("SUBMIT")."'></td></tr>\n";
		echo "</TABLE></center>\n";
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}





######################
# ADD=262111111111 adds new soundboard entry to the system
######################

if ($ADD==262111111111)
	{
	if ($add_copy_disabled > 0)
		{
		echo "<br>"._QXZ("You do not have permission to add records on this system")." -system_settings-\n";
		}
	else
		{
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
		$stmt="SELECT count(*) from vicidial_avatars where avatar_id='$soundboard_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT ADDED - there is already an soundboard entry in the system with this ID")."\n";}
		else
			{
			if ( (strlen($soundboard_id) < 2) or (strlen($avatar_name) < 3) )
				{echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT ADDED - Please go back and look at the data you entered")."\n";}
			else
				{
				$stmt="SELECT count(*) from vicidial_users where user='$avatar_api_user' and vdc_agent_api_access='1';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] < 1)
					{echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT ADDED - invalid API user")."\n";}
				else
					{
					echo "<br>"._QXZ("SOUNDBOARD ENTRY ADDED")."\n";

					$stmt="INSERT INTO vicidial_avatars SET avatar_id='$soundboard_id',avatar_name='$avatar_name',avatar_notes='$avatar_notes',user_group='$user_group',avatar_api_user='$avatar_api_user',avatar_api_pass='$avatar_api_pass';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='SOUNDBOARDS', event_type='ADD', record_id='$soundboard_id', event_code='ADMIN ADD SOUNDBOARD', event_sql=\"$SQL_log\", event_notes='';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					}
				}
			}
		}
	$ADD=362111111111;
	}


######################
# ADD=262211111111 adds copied soundboard entry to the system
######################

if ($ADD==262211111111)
	{
	if ($add_copy_disabled > 0)
		{
		echo "<br>"._QXZ("You do not have permission to add records on this system")." -system_settings-\n";
		}
	else
		{
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
		$stmt="SELECT count(*) from vicidial_avatars where avatar_id='$soundboard_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		if ($row[0] > 0)
			{echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT ADDED - there is already an soundboard entry in the system with this ID")."\n";}
		else
			{
			if ( (strlen($soundboard_id) < 2) or (strlen($avatar_name) < 3) or (strlen($source_avatar_id) < 2) )
				{echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT ADDED - Please go back and look at the data you entered")."\n";}
			else
				{
				$stmt="SELECT count(*) from vicidial_avatars where avatar_id='$source_avatar_id';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$row=mysqli_fetch_row($rslt);
				if ($row[0] < 1)
					{echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT COPIED - invalid source soundboard")."\n";}
				else
					{
					echo "<br>"._QXZ("SOUNDBOARD ENTRY COPIED")."\n";

					$stmt="INSERT INTO vicidial_avatars (avatar_id,avatar_name,avatar_notes,user_group,avatar_api_user,avatar_api_pass,audio_display,audio_functions,active,soundboard_layout,columns_limit) SELECT '$soundboard_id','$avatar_name',avatar_notes,user_group,avatar_api_user,avatar_api_pass,audio_display,audio_functions,active,soundboard_layout,columns_limit from vicidial_avatars where avatar_id='$source_avatar_id';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$affected_rows = mysqli_affected_rows($link);

					$stmtA="INSERT INTO vicidial_avatar_audio (avatar_id,audio_filename,audio_name,rank,h_ord,level,parent_audio_filename,parent_rank,button_type,font_size) SELECT '$soundboard_id',audio_filename,audio_name,rank,h_ord,level,parent_audio_filename,parent_rank,button_type,font_size from vicidial_avatar_audio where avatar_id='$source_avatar_id';";
					if ($DB) {echo "$stmtA\n";}
					$rslt=mysql_to_mysqli($stmtA, $link);
					$affected_rowsA = mysqli_affected_rows($link);

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|$stmtA|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='SOUNDBOARDS', event_type='COPY', record_id='$soundboard_id', event_code='ADMIN COPY SOUNDBOARD', event_sql=\"$SQL_log\", event_notes='$source_avatar_id|$affected_rows|$affected_rowsA';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					}
				}
			}
		}
	$ADD=362111111111;
	}


######################
# ADD=462111111111 modify soundboard record in the system
######################

if ($ADD==462111111111)
	{
	if ( ($modify_audiostore==1) and ($LOGmodify_scripts==1) )
		{
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		if ($stage == "FILEDELETE")
			{
			if ( (strlen($soundboard_id) < 2) or (strlen($audio_filename) < 1) or (strlen($rank) < 1) )
				{echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT MODIFIED - Please go back and look at the data you entered")."\n";}
			else
				{
				$stmt="DELETE FROM vicidial_avatar_audio where avatar_id='$soundboard_id' and audio_filename='$audio_filename' and rank='$rank' and parent_audio_filename='$parent_filename';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);

				echo "<br>"._QXZ("SOUNDBOARD ENTRY MODIFIED").": $soundboard_id - $audio_filename\n";

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$stmt|$stmtA|";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='SOUNDBOARDS', event_type='MODIFY', record_id='$soundboard_id', event_code='ADMIN MODIFY SOUNDBOARD', event_sql=\"$SQL_log\", event_notes='FILE DELETE $audio_filename';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			}
		else
			{
			if ( (strlen($soundboard_id) < 2) or (strlen($avatar_name) < 3) )
				{echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT MODIFIED - Please go back and look at the data you entered")."\n";}
			else
				{
				$stmt="UPDATE vicidial_avatars set avatar_name='$avatar_name',avatar_notes='$avatar_notes',avatar_api_user='$avatar_api_user',avatar_api_pass='$avatar_api_pass',active='$active',audio_functions='$audio_functions',audio_display='$audio_display',user_group='$user_group',soundboard_layout='$soundboard_layout',columns_limit='$columns_limit' where avatar_id='$soundboard_id';";
				if ($DB) {echo "$stmt\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$stmtLIST = $stmt;

				$stmt="SELECT audio_filename,rank,audio_name,level,parent_audio_filename from vicidial_avatar_audio where avatar_id='$soundboard_id' order by rank;";
				if ($DB) {echo "$stmt\n";}
				$rsltx=mysql_to_mysqli($stmt, $link);
				$soundboardfiles_to_print = mysqli_num_rows($rsltx);
				$ranks = ($soundboardfiles_to_print + 1);
				$o=0;
				while ($soundboardfiles_to_print > $o)
					{
					$rowx=mysqli_fetch_row($rsltx);
					$avatarfiles[$o] = $rowx[0];
					$avatarranks[$o] = $rowx[1];
					$avatarnames[$o] = $rowx[2];
					$avatarlevel[$o] = $rowx[3];
					$avatarparent[$o] = $rowx[4];
					$o++;
					}

				$o=0;
				while ($soundboardfiles_to_print > $o)
					{
					$new_rank=0;
					$new_name='';
					$new_level='';
					$old_rank='';
					$new_h_ord='';
					$new_button_type='';
					$new_font_size='';
					$Ffilename = $avatarfiles[$o] . '--' . $avatarlevel[$o] . '--' . $avatarranks[$o] . '--' . $avatarparent[$o];
					$NAMEfilename = '--NAME--' . $avatarfiles[$o] . '--' . $avatarlevel[$o] . '--' . $avatarranks[$o] . '--' . $avatarparent[$o];
					$LEVELfilename = '--LEVEL--' . $avatarfiles[$o] . '--' . $avatarlevel[$o] . '--' . $avatarranks[$o] . '--' . $avatarparent[$o];
					$OLDRANKfilename = '--OLDRANK--' . $avatarfiles[$o] . '--' . $avatarlevel[$o] . '--' . $avatarranks[$o] . '--' . $avatarparent[$o];
					$HORDfilename = '--HORD--' . $avatarfiles[$o] . '--' . $avatarlevel[$o] . '--' . $avatarranks[$o] . '--' . $avatarparent[$o];
					$TYPEfilename = '--TYPE--' . $avatarfiles[$o] . '--' . $avatarlevel[$o] . '--' . $avatarranks[$o] . '--' . $avatarparent[$o];
					$FONTfilename = '--FONT--' . $avatarfiles[$o] . '--' . $avatarlevel[$o] . '--' . $avatarranks[$o] . '--' . $avatarparent[$o];
					if (isset($_GET["$Ffilename"]))				{$new_rank=$_GET["$Ffilename"];}
						elseif (isset($_POST["$Ffilename"]))	{$new_rank=$_POST["$Ffilename"];}
					if (isset($_GET["$NAMEfilename"]))			{$new_name=$_GET["$NAMEfilename"];}
						elseif (isset($_POST["$NAMEfilename"]))	{$new_name=$_POST["$NAMEfilename"];}
					if (isset($_GET["$LEVELfilename"]))				{$new_level=$_GET["$LEVELfilename"];}
						elseif (isset($_POST["$LEVELfilename"]))	{$new_level=$_POST["$LEVELfilename"];}
					if (isset($_GET["$OLDRANKfilename"]))			{$old_rank=$_GET["$OLDRANKfilename"];}
						elseif (isset($_POST["$OLDRANKfilename"]))	{$old_rank=$_POST["$OLDRANKfilename"];}
					if (isset($_GET["$HORDfilename"]))			{$new_h_ord=$_GET["$HORDfilename"];}
						elseif (isset($_POST["$HORDfilename"]))	{$new_h_ord=$_POST["$HORDfilename"];}
					if (isset($_GET["$TYPEfilename"]))			{$new_button_type=$_GET["$TYPEfilename"];}
						elseif (isset($_POST["$TYPEfilename"]))	{$new_button_type=$_POST["$TYPEfilename"];}
					if (isset($_GET["$FONTfilename"]))			{$new_font_size=$_GET["$FONTfilename"];}
						elseif (isset($_POST["$FONTfilename"]))	{$new_font_size=$_POST["$FONTfilename"];}
	
					$new_rank = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$new_rank);
					$new_name = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$new_name);
					$new_level = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$new_level);
					$old_rank = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$old_rank);
					$new_h_ord = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$new_h_ord);
					$new_button_type = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$new_button_type);
					$new_font_size = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$new_font_size);

					if ($DB)
						{
						echo "update variable debug: ($avatarfiles[$o])\n";
						echo "RANK: $Ffilename($new_rank)\n";
						echo "NAME: $NAMEfilename($new_name)\n";
						echo "LEVEL: $LEVELfilename($new_level)\n";
						echo "OLDRANK: $OLDRANKfilename($old_rank)\n";
						echo "HORD: $HORDfilename($new_h_ord)\n";
						echo "TYPE: $TYPEfilename($new_button_type)\n";
						echo "FONT: $FONTfilename($new_font_size)\n";

						}

					$stmt="UPDATE vicidial_avatar_audio set rank='$new_rank',audio_name='$new_name',h_ord='$new_h_ord',button_type='$new_button_type',font_size='$new_font_size' where avatar_id='$soundboard_id' and audio_filename='$avatarfiles[$o]' and rank='$old_rank' and level='$new_level' and parent_audio_filename='$avatarparent[$o]';";
					if ($DB) {echo "$stmt\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					$stmtLIST .= "|$stmt";

					if ($new_level < 2)
						{
						$stmt="UPDATE vicidial_avatar_audio set parent_rank='$new_rank' where avatar_id='$soundboard_id' and parent_audio_filename='$avatarfiles[$o]' and parent_rank='$old_rank' and level='2';";
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$stmtLIST .= "|$stmt";
						}
					$o++;
					}

				if (strlen($audio_filename) > 0)
					{
					if (preg_match("/^\./",$audio_filename))
						{echo "<br>"._QXZ("ERROR, Soundbords cannot use files that begin with a period").": $audio_filename";}
					else
						{
						if (preg_match("/--PARENT----NEW-------X/i",$parent_filename[0]))
							{
							$stmt="SELECT count(*) from vicidial_avatar_audio where avatar_id='$soundboard_id' and level='1' and audio_filename='$audio_filename' and parent_audio_filename='';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$duplicate_first = $row[0];

							if ($duplicate_first > 0)
								{echo "<br>"._QXZ("ERROR, Level One Audio File Duplicate, not added").": $audio_filename";}
							else
								{
								$stmt="SELECT count(*) from vicidial_avatar_audio where avatar_id='$soundboard_id' and level='1';";
								if ($DB) {echo "$stmt\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								$row=mysqli_fetch_row($rslt);
								$new_rank = ($row[0] + 1);

								$stmt="INSERT INTO vicidial_avatar_audio set audio_filename='$audio_filename',rank='$new_rank',avatar_id='$soundboard_id';";
								}
							}
						else
							{
							$parent_filename[0] = preg_replace("/--PARENT--/i",'',$parent_filename[0]);
							$parent_array = explode('-----',$parent_filename[0]);

							$stmt="SELECT count(*) from vicidial_avatar_audio where avatar_id='$soundboard_id' and parent_audio_filename='$parent_array[0]' and parent_rank='$parent_array[1]';";
							if ($DB) {echo "$stmt\n";}
							$rslt=mysql_to_mysqli($stmt, $link);
							$row=mysqli_fetch_row($rslt);
							$new_rank = ($row[0] + 1);

							$stmt="INSERT INTO vicidial_avatar_audio set audio_filename='$audio_filename',rank='$new_rank',parent_audio_filename='$parent_array[0]',parent_rank='$parent_array[1]',level='2',avatar_id='$soundboard_id';";
							}
						if ($DB) {echo "$stmt\n";}
						$rslt=mysql_to_mysqli($stmt, $link);
						$stmtLIST .= "|$stmt";
						}
					}

				echo "<br>"._QXZ("SOUNDBOARD ENTRY MODIFIED").": $soundboard_id\n";

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$stmtLIST|$stmtA|";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='SOUNDBOARDS', event_type='MODIFY', record_id='$soundboard_id', event_code='ADMIN MODIFY SOUNDBOARD', event_sql=\"$SQL_log\", event_notes='';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			}
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	$ADD=362111111111;	# go to soundboard entry modification form below
	}


######################
# ADD=562111111111 confirmation before deletion of soundboard record
######################

if ($ADD==562111111111)
	{
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	if (strlen($soundboard_id) < 2)
		{
		echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT DELETED - Please go back and look at the data you entered")."\n";
		echo "<br>"._QXZ("SOUNDBOARD ID be at least 2 characters in length")."\n";
		}
	else
		{
		echo "<br><B>"._QXZ("SOUNDBOARD ENTRY DELETION CONFIRMATION").": $soundboard_id - $avatar_name</B>\n";
		echo "<br><br><a href=\"$PHP_SELF?ADD=662111111111&soundboard_id=$soundboard_id&DB=$DB&ConFiRm=YES\">"._QXZ("Click here to delete soundboard entry")." $soundboard_id - $avatar_name</a><br><br><br>\n";
		}
	$ADD='362111111111';		# go to soundboard entry modification below
	}



######################
# ADD=662111111111 delete soundboard record
######################

if ($ADD==662111111111)
	{
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	if ( (strlen($soundboard_id) < 2) or ($ConFiRm != 'YES') )
		{
		echo "<br>"._QXZ("SOUNDBOARD ENTRY NOT DELETED - Please go back and look at the data you entered")."\n";
		echo "<br>"._QXZ("SOUNDBOARD ID be at least 2 characters in length")."\n";
		}
	else
		{
		$stmt="DELETE FROM vicidial_avatars where avatar_id='$soundboard_id' $LOGadmin_viewable_groupsSQL;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		$stmtA="DELETE from vicidial_avatar_audio where avatar_id='$soundboard_id';";
		if ($DB) {echo "$stmtA\n";}
		$rslt=mysql_to_mysqli($stmtA, $link);

		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$stmt|$stmtA|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='SOUNDBOARDS', event_type='DELETE', record_id='$soundboard_id', event_code='ADMIN DELETE SOUNDBOARD', event_sql=\"$SQL_log\", event_notes='';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		echo "<br><B>"._QXZ("SOUNDBOARD DELETION COMPLETED").": $soundboard_id</B>\n";
		echo "<br><br>\n";
		}
	$ADD='162000000000';		# go to soundboard entry list
	}



######################
# ADD=362111111111 modify soundboard record in the system
######################

if ($ADD==362111111111)
	{
	if ( ($modify_audiostore==1) and ($LOGmodify_scripts==1) )
		{
		if ( ($SSadmin_modify_refresh > 1) and ($modify_refresh_set < 1) )
			{
			$modify_url = "$PHP_SELF?ADD=362111111111&soundboard_id=$soundboard_id";
			$modify_footer_refresh=1;
			}
		echo "<TABLE WIDTH=850><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		$stmt="SELECT avatar_id,avatar_name,avatar_notes,avatar_api_user,avatar_api_pass,active,audio_functions,user_group,audio_display,soundboard_layout,columns_limit from vicidial_avatars where avatar_id='$soundboard_id' $LOGadmin_viewable_groupsSQL;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$soundboard_id =		$row[0];
		$avatar_name =		$row[1];
		$avatar_notes =		$row[2];
		$avatar_api_user =	$row[3];
		$avatar_api_pass =	$row[4];
		$active =			$row[5];
		$audio_functions =	$row[6];
		$user_group =		$row[7];
		$audio_display =	$row[8];
		$soundboard_layout = $row[9];
		$columns_limit =	$row[10];

		echo "<br>"._QXZ("MODIFY A SOUNDBOARDS RECORD").": $soundboard_id<form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=ADD value=462111111111>\n";
		echo "<input type=hidden name=DB value=\"$DB\">\n";
		echo "<input type=hidden name=soundboard_id value=\"$soundboard_id\">\n";

		echo "<center><TABLE width=$section_width cellspacing=3>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard ID").": </td><td align=left><B>$soundboard_id</B></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard Name").": </td><td align=left><input type=text name=avatar_name size=70 maxlength=100 value=\"$avatar_name\">$NWB#soundboard-avatar_name$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard Notes").": </td><td align=left><input type=text name=avatar_notes size=70 maxlength=1000 value=\"$avatar_notes\">$NWB#soundboard-avatar_notes$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard API User").": </td><td align=left><input type=text name=avatar_api_user size=20 maxlength=20 value=\"$avatar_api_user\">$NWB#soundboard-avatar_api_user$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Soundboard API Pass").": </td><td align=left><input type=password name=avatar_api_pass size=40 maxlength=100 value=\"$avatar_api_pass\">$NWB#soundboard-avatar_api_pass$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Active").": </td><td align=left><select size=1 name=active><option value='N'>"._QXZ("N")."</option><option value='Y'>"._QXZ("Y")."</option><option value='$active' SELECTED>"._QXZ("$active")."</option></select>$NWB#soundboard-active$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Audio Functions").": </td><td align=left><input type=text name=audio_functions size=70 maxlength=100 value=\"$audio_functions\">$NWB#soundboard-audio_functions$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Audio Display").": </td><td align=left><input type=text name=audio_display size=70 maxlength=100 value=\"$audio_display\">$NWB#soundboard-audio_display$NWE</td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Layout").": </td><td align=left><select size=1 name=soundboard_layout><option value='default'>"._QXZ("default")."</option><option value='columns01'>"._QXZ("columns01")."</option><option value='$soundboard_layout' SELECTED>"._QXZ("$soundboard_layout")."</option></select>$NWB#soundboard-soundboard_layout$NWE &nbsp; "._QXZ("Columns Limit").": <input type=text name=columns_limit size=3 maxlength=2 value=\"$columns_limit\"></td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=right>"._QXZ("Admin User Group").": </td><td align=left><select size=1 name=user_group>\n";
		echo "$UUgroups_list";
		echo "<option SELECTED value=\"$user_group\">".(preg_match('/\-\-ALL\-\-/', $user_group) ? _QXZ("$user_group") : $user_group)."</option>\n";
		echo "</select>$NWB#soundboard-user_group$NWE</td></tr>\n";

		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=center colspan=2>"._QXZ("Audio Files").": </td></tr>\n";
		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=left colspan=2>\n";
		echo "<table>\n";
		echo "<tr><td nowrap align=center><B>"._QXZ("FILENAME")."</B></td><td nowrap align=center>"._QXZ("RANK")."</td><td nowrap align=center>"._QXZ("HORZ")."</td><td nowrap align=center>"._QXZ("type")."</td><td nowrap align=center>"._QXZ("FONT")."</td><td nowrap align=center>"._QXZ("level")."</td><td nowrap align=center>"._QXZ("add")."</td><td nowrap align=center>"._QXZ("name")."</td><td nowrap align=center><B> &nbsp; </B></td>";

		##### get files listing for rank/delete options
		$stmt="SELECT audio_filename,audio_name,rank,level,parent_audio_filename,parent_rank,h_ord,button_type,font_size from vicidial_avatar_audio where avatar_id='$soundboard_id' and level='1' order by rank,h_ord;";
		if ($DB) {echo "$stmt\n";}
		$rsltx=mysql_to_mysqli($stmt, $link);
		$soundboardfiles_to_print = mysqli_num_rows($rsltx);
		$ranks = ($soundboardfiles_to_print + 2);
		$levels = 2;
		$o=0;
		while ($soundboardfiles_to_print > $o)
			{
			$ro = ($o + 1);
			$rowx=mysqli_fetch_row($rsltx);
			echo "<tr bgcolor=#". $SSstd_row1_background ."><td nowrap><B>$ro. $rowx[0]</B> &nbsp; </td>";
			echo "<td nowrap><select size=1 name=\"$rowx[0]--$rowx[3]--$rowx[2]--\">";
			echo "<option SELECTED>$rowx[2]</option>\n";
			$k=1;
			while ($ranks > $k)
				{
				echo "<option>$k</option>\n";
				$k++;
				}
			echo "</select></td>\n";
			echo "<td nowrap><select size=1 name=\"--HORD--$rowx[0]--$rowx[3]--$rowx[2]--\">";
			echo "<option SELECTED>$rowx[6]</option>\n";
			$k=1;
			while ($ranks > $k)
				{
				echo "<option>$k</option>\n";
				$k++;
				}
			echo "</select></td>\n";
			echo "<td nowrap><select size=1 name=\"--TYPE--$rowx[0]--$rowx[3]--$rowx[2]--\">";
			echo "<option SELECTED value='$rowx[7]'>"._QXZ("$rowx[7]")."</option>\n";
			echo "<option value='button'>"._QXZ("button")."</option>\n";
			echo "<option value='header'>"._QXZ("header")."</option>\n";
			echo "<option value='head2r'>"._QXZ("head2r")."</option>\n";
			echo "<option value='space'>"._QXZ("space")."</option>\n";
			echo "</select></td>\n";
			echo "<td nowrap><select size=1 name=\"--FONT--$rowx[0]--$rowx[3]--$rowx[2]--\">";
			echo "<option SELECTED>$rowx[8]</option>\n";
			echo "<option>1</option>\n";
			echo "<option>2</option>\n";
			echo "<option>3</option>\n";
			echo "<option>4</option>\n";
			echo "<option>5</option>\n";
			echo "<option>6</option>\n";
			echo "<option>1B</option>\n";
			echo "<option>2B</option>\n";
			echo "<option>3B</option>\n";
			echo "<option>4B</option>\n";
			echo "<option>5B</option>\n";
			echo "<option>6B</option>\n";
			echo "<option>1I</option>\n";
			echo "<option>2I</option>\n";
			echo "<option>3I</option>\n";
			echo "<option>4I</option>\n";
			echo "<option>5I</option>\n";
			echo "<option>6I</option>\n";
			echo "<option>1BI</option>\n";
			echo "<option>2BI</option>\n";
			echo "<option>3BI</option>\n";
			echo "<option>4BI</option>\n";
			echo "<option>5BI</option>\n";
			echo "<option>6BI</option>\n";
			echo "</select></td>\n";
			echo "<td nowrap><font size=2> $rowx[3] </td>";
			echo "<td nowrap><input type=hidden name=\"--LEVEL--$rowx[0]--$rowx[3]--$rowx[2]--\" id=\"--LEVEL--$rowx[0]--$rowx[3]--$rowx[2]--\" value=\"$rowx[3]\">";
			echo "<input type=hidden name=\"--OLDRANK--$rowx[0]--$rowx[3]--$rowx[2]--\" id=\"--OLDRANK--$rowx[0]--$rowx[3]--$rowx[2]--\" value=\"$rowx[2]\">";
			echo " <input type=RADIO name=parent_filename[] id=parent_filename[] value=\"--PARENT--$rowx[0]-----$rowx[2]\"> </td>";
			echo "<td nowrap> "._QXZ("Name").": <input type=text name=\"--NAME--$rowx[0]--$rowx[3]--$rowx[2]--\" size=20 maxlength=100 value=\"$rowx[1]\"></td>";

			echo "<td nowrap> - <a href=\"$PHP_SELF?ADD=462111111111&soundboard_id=$soundboard_id&stage=FILEDELETE&audio_filename=$rowx[0]&rank=$rowx[2]&level=$rowx[3]\">"._QXZ("DELETE")."</a></td></tr>\n";

			$stmt="SELECT audio_filename,audio_name,rank,level,parent_audio_filename,parent_rank,h_ord,button_type,font_size from vicidial_avatar_audio where avatar_id='$soundboard_id' and level='2' and parent_audio_filename='$rowx[0]' and parent_rank='$rowx[2]' order by rank,h_ord;";
			if ($DB) {echo "$stmt\n";}
			$rslty=mysql_to_mysqli($stmt, $link);
			$Csoundboardfiles_to_print = mysqli_num_rows($rslty);
			$Cranks = ($Csoundboardfiles_to_print + 2);
			$Clevels = 2;
			$Co=0;
			while ($Csoundboardfiles_to_print > $Co)
				{
				$ro = ($Co + 1);
				$rowC=mysqli_fetch_row($rslty);
				echo "<tr><td nowrap> &nbsp; &nbsp; $ro. $rowC[0] &nbsp; </td>";
				echo "<td nowrap><font size=2> &nbsp; &nbsp; </font><select size=1 name=\"$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\">\n";
				echo "<option SELECTED>$rowC[2]</option>\n";
				$k=1;
				while ($Cranks > $k)
					{
					echo "<option>$k</option>\n";
					$k++;
					}
				echo "</select></td>\n";
				echo "<td nowrap><font size=2> &nbsp; &nbsp; </font><select size=1 name=\"--HORD--$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\">\n";
				echo "<option SELECTED>$rowC[6]</option>\n";
				$k=1;
				while ($Cranks > $k)
					{
					echo "<option>$k</option>\n";
					$k++;
					}
				echo "</select></td>\n";
				echo "<td nowrap><select size=1 name=\"--TYPE--$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\">";
				echo "<option SELECTED value='$rowC[7]'>"._QXZ("$rowC[7]")."</option>\n";
				echo "<option value='button'>"._QXZ("button")."</option>\n";
				echo "<option value='header'>"._QXZ("header")."</option>\n";
				echo "<option value='space'>"._QXZ("space")."</option>\n";
				echo "</select></td>\n";
				echo "<td nowrap><select size=1 name=\"--FONT--$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\">";
				echo "<option SELECTED>$rowC[8]</option>\n";
				echo "<option>1</option>\n";
				echo "<option>2</option>\n";
				echo "<option>3</option>\n";
				echo "<option>4</option>\n";
				echo "<option>5</option>\n";
				echo "<option>6</option>\n";
				echo "<option>1B</option>\n";
				echo "<option>2B</option>\n";
				echo "<option>3B</option>\n";
				echo "<option>4B</option>\n";
				echo "<option>5B</option>\n";
				echo "<option>6B</option>\n";
				echo "<option>1I</option>\n";
				echo "<option>2I</option>\n";
				echo "<option>3I</option>\n";
				echo "<option>4I</option>\n";
				echo "<option>5I</option>\n";
				echo "<option>6I</option>\n";
				echo "<option>1BI</option>\n";
				echo "<option>2BI</option>\n";
				echo "<option>3BI</option>\n";
				echo "<option>4BI</option>\n";
				echo "<option>5BI</option>\n";
				echo "<option>6BI</option>\n";
				echo "</select></td>\n";
				echo "<td nowrap><font size=2> &nbsp;  $rowC[3] </td>";
				echo "<td nowrap><input type=hidden name=\"--LEVEL--$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\" id=\"--LEVEL--$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\" value=\"$rowC[3]\">";
				echo "<input type=hidden name=\"--OLDRANK--$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\" id=\"--OLDRANK--$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\" value=\"$rowC[2]\">";
				echo " &nbsp; </td>";
				echo "<td nowrap> &nbsp; "._QXZ("Name").": <input type=text name=\"--NAME--$rowC[0]--$rowC[3]--$rowC[2]--$rowx[0]\" size=20 maxlength=100 value=\"$rowC[1]\"></td>\n";

				echo "<td nowrap> &nbsp; - <a href=\"$PHP_SELF?ADD=462111111111&soundboard_id=$soundboard_id&stage=FILEDELETE&audio_filename=$rowC[0]&rank=$rowC[2]&level=$rowC[3]&parent_filename=$rowx[0]\">"._QXZ("DELETE")."</a></td></tr>\n";
				$Co++;
				}
			$o++;
			}


		echo "<tr bgcolor=#". $SSstd_row2_background ."><td nowrap><B>"._QXZ("NEW")."</B> &nbsp; </td>";
		echo "<td nowrap colspan=4 align=right><font size=2>\n";
		echo "<font size=2>"._QXZ("Level").": 1 ";
		echo " "._QXZ("Add New").": <input type=RADIO name=parent_filename[] id=parent_filename[] value=\"--PARENT----NEW-------X\" CHECKED> ";
		echo "</td></tr>\n";

		echo "</table></td></tr>\n";

		echo "<tr bgcolor=#". $SSstd_row1_background ."><td align=right>"._QXZ("Add An Audio File").": </td><td><input type=text size=70 maxlength=100 name=audio_filename id=audio_filename value=\"\"> <a href=\"javascript:launch_chooser('audio_filename','date');\">"._QXZ("audio chooser")."</a>  $NWB#soundboard-audio_filename$NWE</td></tr>\n";

		echo "<tr bgcolor=#". $SSstd_row2_background ."><td align=center colspan=2><input type=submit name=submit VALUE='"._QXZ("SUBMIT")."'></td></tr>\n";
		echo "</TABLE></center>\n";

		echo "<center><br><br><b>\n";
		echo _QXZ("Sample Soundboard View").": $soundboard_id<BR><BR>\n";

		echo "<iframe src=\"/agc/vdc_soundboard_display.php?user=$PHP_AUTH_USER&pass=$PHP_AUTH_PW&bcrypt=OFF&soundboard_id=$soundboard_id&DB=$DB\" style=\"background-color:transparent;\" scrolling=\"auto\" frameborder=\"0\" allowtransparency=\"true\" width=\"100%\" height=\"500\">\n</iframe>\n";

		echo "<BR><BR><B>"._QXZ("SCRIPTS USING THIS SOUNDBOARD").":</B><BR>\n";
		echo "<TABLE>\n";

		$scripts_SQL='';
		$stmt="SELECT script_id,script_name from vicidial_scripts where script_text LIKE \"%$soundboard_id%\" and script_text LIKE \"%vdc_soundboard_display%\" $LOGadmin_viewable_groupsSQL;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$scripts_to_print = mysqli_num_rows($rslt);
		$o=0;
		while ($scripts_to_print > $o) 
			{
			$row=mysqli_fetch_row($rslt);
			echo "<TR><TD><a href=\"admin.php?ADD=3111111&script_id=$row[0]\">$row[0] </a></TD><TD> $row[1]<BR></TD></TR>\n";
			$scripts_SQL .= "'$row[0]',";
			$o++;
			}
		$scripts_SQL = preg_replace("/,$/",'',$scripts_SQL);
		if (strlen($scripts_SQL) < 2) {$scripts_SQL="''";}

		echo "</TABLE><BR><BR>\n";

		echo "<B>"._QXZ("CAMPAIGNS USING SCRIPTS THAT USE THIS SOUNDBOARD").":</B><BR>\n";
		echo "<TABLE>\n";

		$stmt="SELECT campaign_id,campaign_name from vicidial_campaigns where campaign_script IN($scripts_SQL) $LOGallowed_campaignsSQL;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$camps_to_print = mysqli_num_rows($rslt);
		$o=0;
		while ($camps_to_print > $o) 
			{
			$row=mysqli_fetch_row($rslt);
			echo "<TR><TD><a href=\"admin.php?ADD=31&campaign_id=$row[0]\">$row[0] </a></TD><TD> $row[1]<BR></TD></TR>\n";
			$o++;
			}

		echo "</TABLE><BR><BR>\n";


		echo "<B>"._QXZ("INGROUPS USING SCRIPTS THAT USE THIS SOUNDBOARD").":</B><BR>\n";
		echo "<TABLE>\n";

		$stmt="SELECT group_id,group_name from vicidial_inbound_groups where ingroup_script IN($scripts_SQL) $LOGadmin_viewable_groupsSQL;";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$igs_to_print = mysqli_num_rows($rslt);
		$o=0;
		while ($igs_to_print > $o) 
			{
			$row=mysqli_fetch_row($rslt);
			echo "<TR><TD><a href=\"admin.php?ADD=3111&group_id=$row[0]\">$row[0] </a></TD><TD> $row[1]<BR></TD></TR>\n";
			$o++;
			}

		echo "</TABLE><BR><BR>\n";


		if ($modify_audiostore > 0)
			{
			echo "<br><br><a href=\"$PHP_SELF?ADD=562111111111&soundboard_id=$soundboard_id&DB=$DB&avatar_name=$avatar_name\">"._QXZ("DELETE SOUNDBOARD ENTRY")."</a>\n";
			}
		if ( ($LOGuser_level >= 9) and ( (preg_match("/Administration Change Log/",$LOGallowed_reports)) or (preg_match("/ALL REPORTS/",$LOGallowed_reports)) ) )
			{
			echo "<br><br><a href=\"admin.php?ADD=720000000000000&category=SOUNDBOARDS&stage=$soundboard_id\">"._QXZ("Click here to see Admin changes to this Soundboard entry")."</a>\n";
			}
		}
	else
		{
		echo _QXZ("You do not have permission to view this page")."\n";
		exit;
		}
	}



######################
# ADD=162000000000 display all soundboard entries
######################
if ($ADD==162000000000)
	{
	echo "<TABLE><TR><TD>\n";
	echo "<img src=\"images/icon_audiosoundboards.png\" width=42 height=42 align=left> <FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	$stmt="SELECT avatar_id,avatar_name,active,avatar_api_user,user_group,soundboard_layout from vicidial_avatars where active IN('N','Y') $LOGadmin_viewable_groupsSQL order by avatar_id";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$soundboard_to_print = mysqli_num_rows($rslt);

	echo "<br>"._QXZ("AGENT AUDIO SOUNDBOARD LISTINGS").":\n";
	echo "<center><TABLE width=$section_width cellspacing=0 cellpadding=1>\n";
	echo "<tr bgcolor=black>";
	echo "<td><font size=1 color=white align=left><B>"._QXZ("Soundboard ID")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("Soundboard Name")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("Active")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("Layout")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("API User")."</B></td>";
	echo "<td><font size=1 color=white><B>"._QXZ("ADMIN GROUP")."</B></td>";
	echo "<td align=center><font size=1 color=white><B>"._QXZ("MODIFY")."</B></td></tr>\n";

	$o=0;
	while ($soundboard_to_print > $o) 
		{
		$row=mysqli_fetch_row($rslt);
		$row[3]=preg_replace('/;|<|>/', '', $row[3]);
		while(strlen($row[3]) > 50) {$row[3] = substr("$row[3]", 0, -1);}
		if(strlen($row[3]) > 47) {$row[3] = "$row[3]...";}

		if (preg_match('/1$|3$|5$|7$|9$/i', $o))
			{$bgcolor='class="records_list_x"';} 
		else
			{$bgcolor='class="records_list_y"';}
		echo "<tr $bgcolor"; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$PHP_SELF?ADD=362111111111&soundboard_id=$row[0]&DB=$DB'\"";} echo "><td><a href=\"$PHP_SELF?ADD=362111111111&soundboard_id=$row[0]&DB=$DB\"><font size=1 color=black>$row[0]</a></td>";
		echo "<td><font size=1>$row[1]</td>";
		echo "<td><font size=1>"._QXZ("$row[2]")."</td>";
		echo "<td><font size=1>$row[5]</td>";
		echo "<td><font size=1>$row[3]</td>";
		echo "<td><font size=1>".(preg_match('/\-\-ALL\-\-/', $row[4]) ? _QXZ("$row[4]") : $row[4])."</td>";
		echo "<td align=center><font size=1><a href=\"$PHP_SELF?ADD=362111111111&soundboard_id=$row[0]&DB=$DB\">"._QXZ("MODIFY")."</a></td></tr>\n";
		$o++;
		}

	echo "</TABLE></center>\n";
	}




################################################################################
##### BEGIN admin log display
if ($ADD == "762000000000")
	{
	if ($LOGuser_level >= 9)
		{
		echo "<TABLE><TR><TD>\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

		$stmt="SELECT admin_log_id,event_date,user,ip_address,event_section,event_type,record_id,event_code from vicidial_admin_log where event_section='SOUNDBOARDSS' and record_id='$list_id' order by event_date desc limit 10000;";
		if ($DB) {echo "$stmt\n";}
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
			if (preg_match('/SOUNDBOARDS/i', $row[4])) {$record_link = "$PHP_SELF?ADD=362111111111&soundboard_id=$row[6]&DB=$DB";}

			if (preg_match('/1$|3$|5$|7$|9$/i', $o))
				{$bgcolor='bgcolor="#'. $SSstd_row3_background .'"';} 
			else
				{$bgcolor='bgcolor="#'. $SSstd_row4_background .'"';}
			echo "<tr $bgcolor><td><font size=1><a href=\"admin.php?ADD=730000000000000&stage=$row[0]&DB=$DB\">$row[0]</a></td>";
			echo "<td><font size=1> $row[1]</td>";
			echo "<td><font size=1> <a href=\"admin.php?ADD=710000000000000&stage=$row[2]&DB=$DB\">$row[2]</a></td>";
			echo "<td><font size=1> $row[3]</td>";
			echo "<td><font size=1> $row[4]</td>";
			echo "<td><font size=1> $row[5]</td>";
			echo "<td><font size=1> $row[6]</td>";
			echo "<td><font size=1> $row[7]</td>";
			echo "<td><font size=1> <a href=\"$record_link\">"._QXZ("GOTO")."</a></td>";
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
