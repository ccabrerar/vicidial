<?php
# lead_tools.php - Various tools for lead basic lead management.
#
# Copyright (C) 2022  Matt Florell,Michael Cargile <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 121110-1446 - Initial Build
# 121114-0956 - Added input filtering and vicidial_admin_log logging
# 130124-1129 - Added new options, from issue #632<noah>
# 130610-1045 - Finalized changing of all ereg instances to preg
# 130619-2203 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-1927 - Changed to mysqli PHP functions
# 131016-2029 - Added links to advanced lead tools
# 141007-2039 - Finalized adding QXZ translation to all admin files
# 141229-2033 - Added code for on-the-fly language translations display
# 170409-1533 - Added IP List validation code
# 170711-1105 - Added screen colors
# 170819-1002 - Added allow_manage_active_lists option
# 191119-1815 - Fixes for translations compatibility, issue #1142
# 220228-1025 - Added allow_web_debug system setting
#

$version = '2.14-14';
$build = '220228-1025';

# This limit is to prevent data inconsistancies.
# If there are too many leads in a list this
# script might not finish before the php execution limit.
$list_lead_limit = 100000;

# maximum call count the script will work with
$max_count = 20;

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$ip = getenv("REMOTE_ADDR");
$SQLdate = date("Y-m-d H:i:s");

$DB=0;
$move_submit="";
$update_submit="";
$delete_submit="";
$confirm_move="";
$confirm_update="";
$confirm_delete="";

if (isset($_GET["DB"])) {$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"])) {$DB=$_POST["DB"];}
if (isset($_GET["move_submit"])) {$move_submit=$_GET["move_submit"];}
	elseif (isset($_POST["move_submit"])) {$move_submit=$_POST["move_submit"];}
if (isset($_GET["update_submit"])) {$update_submit=$_GET["update_submit"];}
	elseif (isset($_POST["update_submit"])) {$update_submit=$_POST["update_submit"];}
if (isset($_GET["delete_submit"])) {$delete_submit=$_GET["delete_submit"];}
	elseif (isset($_POST["delete_submit"])) {$delete_submit=$_POST["delete_submit"];}
if (isset($_GET["confirm_move"])) {$confirm_move=$_GET["confirm_move"];}
	elseif (isset($_POST["confirm_move"])) {$confirm_move=$_POST["confirm_move"];}
if (isset($_GET["confirm_update"])) {$confirm_move=$_GET["confirm_update"];}
	elseif (isset($_POST["confirm_update"])) {$confirm_update=$_POST["confirm_update"];}
if (isset($_GET["confirm_delete"])) {$confirm_delete=$_GET["confirm_delete"];}
	elseif (isset($_POST["confirm_delete"])) {$confirm_delete=$_POST["confirm_delete"];}
# Several sets of variable inputs are further down in the code as well

$DB = preg_replace('/[^0-9]/','',$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$sys_settings_stmt = "SELECT use_non_latin,outbound_autodial_active,sounds_central_control_active,enable_languages,language_method,admin_screen_colors,report_default_format,allow_manage_active_lists,allow_web_debug FROM system_settings;";
$sys_settings_rslt=mysql_to_mysqli($sys_settings_stmt, $link);
#if ($DB) {echo "$sys_settings_stmt\n";}
$num_rows = mysqli_num_rows($sys_settings_rslt);
if ($num_rows > 0)
	{
	$sys_settings_row=mysqli_fetch_row($sys_settings_rslt);
	$non_latin =						$sys_settings_row[0];
	$SSoutbound_autodial_active =		$sys_settings_row[1];
	$sounds_central_control_active =	$sys_settings_row[2];
	$SSenable_languages =				$sys_settings_row[3];
	$SSlanguage_method =				$sys_settings_row[4];
	$SSadmin_screen_colors =			$sys_settings_row[5];
	$SSreport_default_format =			$sys_settings_row[6];
	$SSallow_manage_active_lists =		$sys_settings_row[7];
	$SSallow_web_debug =				$sys_settings_row[8];
	}
else
	{
	# there is something really weird if there are no system settings
	exit;
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$move_submit = preg_replace('/[^-_0-9a-zA-Z]/','',$move_submit);
$update_submit = preg_replace('/[^-_0-9a-zA-Z]/','',$update_submit);
$delete_submit = preg_replace('/[^-_0-9a-zA-Z]/','',$delete_submit);
$confirm_move = preg_replace('/[^-_0-9a-zA-Z]/','',$confirm_move);
$confirm_update = preg_replace('/[^-_0-9a-zA-Z]/','',$confirm_update);
$confirm_delete = preg_replace('/[^-_0-9a-zA-Z]/','',$confirm_delete);
$delete_status = preg_replace('/[^-_0-9a-zA-Z]/','',$delete_status);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	}

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

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");			  // HTTP/1.0

# valid user
$rights_stmt = "SELECT load_leads,user_group, delete_lists, modify_leads, modify_lists from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$load_leads =		$rights_row[0];
$user_group =		$rights_row[1];
$delete_lists =		$rights_row[2];
$modify_leads =		$rights_row[3];
$modify_lists =		$rights_row[4];

# check their permissions
if ( $load_leads < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to load leads")."\n";
	exit;
	}
if ( $modify_leads < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify leads")."\n";
	exit;
	}
if ( $modify_lists < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify lists")."\n";
	exit;
	}

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
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
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
		$SSweb_logo =				$row[10];
		}
	}
$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;

$selected_logo = "./images/vicidial_admin_web_logo.png";
$selected_small_logo = "./images/vicidial_admin_web_logo.png";
$logo_new=0;
$logo_old=0;
$logo_small_old=0;
if (file_exists('./images/vicidial_admin_web_logo.png')) {$logo_new++;}
if (file_exists('vicidial_admin_web_logo_small.gif')) {$logo_small_old++;}
if (file_exists('vicidial_admin_web_logo.gif')) {$logo_old++;}
if ($SSweb_logo=='default_new')
	{
	$selected_logo = "./images/vicidial_admin_web_logo.png";
	$selected_small_logo = "./images/vicidial_admin_web_logo.png";
	}
if ( ($SSweb_logo=='default_old') and ($logo_old > 0) )
	{
	$selected_logo = "./vicidial_admin_web_logo.gif";
	$selected_small_logo = "./vicidial_admin_web_logo_small.gif";
	}
if ( ($SSweb_logo!='default_new') and ($SSweb_logo!='default_old') )
	{
	if (file_exists("./images/vicidial_admin_web_logo$SSweb_logo")) 
		{
		$selected_logo = "./images/vicidial_admin_web_logo$SSweb_logo";
		$selected_small_logo = "./images/vicidial_admin_web_logo$SSweb_logo";
		}
	}


echo "<html>\n";
echo "<head>\n";
echo "<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=utf-8'>\n";
echo "<!-- VERSION: <?php echo $version ?>     BUILD: <?php echo $build ?> -->\n";
echo "<title>"._QXZ("ADMINISTRATION: Lead Tools")."</title>\n";

##### BEGIN Set variables to make header show properly #####
$ADD =                               '999998';
$hh =                                'admin';
$LOGast_admin_access =  '1';
$SSoutbound_autodial_active = '1';
$ADMIN =                             'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$admin_color =          '#FFFF99';
$admin_font =           'BLACK';
$admin_color =          '#E6E6E6';
$subcamp_color =        '#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");

echo "<table width=$page_width bgcolor=#E6E6E6 cellpadding=2 cellspacing=0>\n";
echo "<tr bgcolor='#E6E6E6'>\n";
echo "<td align=left>\n";
echo "<font face='ARIAL,HELVETICA' size=2>\n";
echo "<b> &nbsp; "._QXZ("Basic Lead Tools")." &nbsp; | &nbsp; <a href=\"lead_tools_advanced.php\">"._QXZ("Advanced Lead Tools")."</a></b>\n";
echo "</font>\n";
echo "</td>\n";
echo "<td align=right><font face='ARIAL,HELVETICA' size=2><b> &nbsp; </td>\n";
echo "</tr>\n";



echo "<tr bgcolor='#$SSframe_background'><td align=left colspan=2><font face='ARIAL,HELVETICA' color=black size=3> &nbsp; \n";

# move confirmation page
if ($move_submit == _QXZ("move") )
	{
	# get the variables
	$move_from_list="";
	$move_to_list="";
	$move_status="";
	$move_count_op="";
	$move_count_num="";


	if (isset($_GET["move_from_list"])) {$move_from_list=$_GET["move_from_list"];}
		elseif (isset($_POST["move_from_list"])) {$move_from_list=$_POST["move_from_list"];}
	if (isset($_GET["move_to_list"])) {$move_to_list=$_GET["move_to_list"];}
		elseif (isset($_POST["move_to_list"])) {$move_to_list=$_POST["move_to_list"];}
	if (isset($_GET["move_status"])) {$move_status=$_GET["move_status"];}
		elseif (isset($_POST["move_status"])) {$move_status=$_POST["move_status"];}
	if (isset($_GET["move_count_op"])) {$move_count_op=$_GET["move_count_op"];}
		elseif (isset($_POST["move_count_op"])) {$move_count_op=$_POST["move_count_op"];}
	if (isset($_GET["move_count_num"])) {$move_count_num=$_GET["move_count_num"];}
		elseif (isset($_POST["move_count_num"])) {$move_count_num=$_POST["move_count_num"];}
	
	$move_from_list = preg_replace('/[^0-9]/','',$move_from_list);
	$move_to_list = preg_replace('/[^0-9]/','',$move_to_list);
	$move_status = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $move_status);
	$move_count_num = preg_replace('/[^0-9]/','',$move_count_num);
	$move_count_op = preg_replace('/[^<>=]/','',$move_count_op);


	$move_count_op_phrase="";
	if ( $move_count_op == "<" )
		{
		$move_count_op_phrase= _QXZ("less than")." ";
		}
	elseif ( $move_count_op == "<=" )
		{
		$move_count_op_phrase= _QXZ("less than or equal to")." ";
		}
	elseif ( $move_count_op == ">" )
		{
		$move_count_op_phrase= _QXZ("greater than")." ";
		}
	elseif ( $move_count_op == ">=" )
		{
		$move_count_op_phrase= _QXZ("greater than or equal to")." ";
		}

	# get the number of leads this action will move
	$move_lead_count=0;
	$move_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$move_from_list' and status like '$move_status' and called_count $move_count_op $move_count_num";
	if ($DB) { echo "|$move_lead_count_stmt|\n"; }
	$move_lead_count_rslt = mysql_to_mysqli($move_lead_count_stmt, $link);
	$move_lead_count_row = mysqli_fetch_row($move_lead_count_rslt);
	$move_lead_count = $move_lead_count_row[0];

	# get the number of leads in the list this action will move to
	$to_list_lead_count=0;
	$to_list_lead_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$move_to_list'";
	if ($DB) { echo "|$to_list_lead_stmt|\n"; }
	$to_list_lead_rslt = mysql_to_mysqli($to_list_lead_stmt, $link);
	$to_list_lead_row = mysqli_fetch_row($to_list_lead_rslt);
	$to_list_lead_count = $to_list_lead_row[0];

	# check to see if we will exceed list_lead_limit in the move to list
	if ( $to_list_lead_count + $move_lead_count > $list_lead_limit )
		{
		echo "<html>\n";
		echo "<head>\n";
		echo "<!-- VERSION: $version     BUILD: $build -->\n";
		echo "</head>\n";
		echo "<body>\n";
		echo "<p>"._QXZ("Sorry. This operation will cause list")." $move_to_list "._QXZ("to exceed")." $list_lead_limit "._QXZ("leads which is not allowed").".</p>\n";
		echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over").".</a></p>\n";
		echo "</body>\n</html>\n";
		}
	else
		{
		echo "<p>"._QXZ("You are about to move")." $move_lead_count "._QXZ("leads from list")." $move_from_list "._QXZ("to")." $move_to_list "._QXZ("with the status")." $move_status "._QXZ("and that were called")." $move_count_op_phrase$move_count_num "._QXZ("times").". "._QXZ("Please press confirm to continue").".</p>\n";
		echo "<center><form action=$PHP_SELF method=POST>\n";
		echo "<input type=hidden name=move_from_list value='$move_from_list'>\n";
		echo "<input type=hidden name=move_to_list value='$move_to_list'>\n";
		echo "<input type=hidden name=move_status value='$move_status'>\n";
		echo "<input type=hidden name=move_count_op value='$move_count_op'>\n";
		echo "<input type=hidden name=move_count_num value='$move_count_num'>\n";
		echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_move value='"._QXZ("confirm")."'>\n";
		echo "</form></center>\n";
				echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over").".</a></p>\n";
		echo "</body>\n</html>\n";
		}
	}

# actually do the move
if ($confirm_move == _QXZ("confirm"))
	{
	# get the variables
	$move_from_list="";
	$move_to_list="";
	$move_status="";
	$move_count_op="";
	$move_count_num="";
		
	if (isset($_GET["move_from_list"])) {$move_from_list=$_GET["move_from_list"];}
		elseif (isset($_POST["move_from_list"])) {$move_from_list=$_POST["move_from_list"];}
	if (isset($_GET["move_to_list"])) {$move_to_list=$_GET["move_to_list"];}
		elseif (isset($_POST["move_to_list"])) {$move_to_list=$_POST["move_to_list"];}
	if (isset($_GET["move_status"])) {$move_status=$_GET["move_status"];}
		elseif (isset($_POST["move_status"])) {$move_status=$_POST["move_status"];}
	if (isset($_GET["move_count_op"])) {$move_count_op=$_GET["move_count_op"];}
		elseif (isset($_POST["move_count_op"])) {$move_count_op=$_POST["move_count_op"];}
	if (isset($_GET["move_count_num"])) {$move_count_num=$_GET["move_count_num"];}
		elseif (isset($_POST["move_count_num"])) {$move_count_num=$_POST["move_count_num"];}
			
	$move_from_list = preg_replace('/[^0-9]/','',$move_from_list);
	$move_to_list = preg_replace('/[^0-9]/','',$move_to_list);
	$move_status = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $move_status);
	$move_count_num = preg_replace('/[^0-9]/','',$move_count_num);
	$move_count_op = preg_replace('/[^<>=]/','',$move_count_op);

	$move_count_op_phrase="";
	if ( $move_count_op == "<" )
		{
		$move_count_op_phrase= _QXZ("less than")." ";
		}
	elseif ( $move_count_op == "<=" )
		{
		$move_count_op_phrase= _QXZ("less than or equal to")." ";
		}
	elseif ( $move_count_op == ">" )
		{
		$move_count_op_phrase= _QXZ("greater than")." ";
		}
	elseif ( $move_count_op == ">=" )
		{
		$move_count_op_phrase= _QXZ("greater than or equal to")." ";
		}

	$move_lead_stmt = "UPDATE vicidial_list SET list_id = '$move_to_list' WHERE list_id = '$move_from_list' and status like '$move_status' and called_count $move_count_op $move_count_num";
	if ($DB) { echo "|$move_lead_stmt|\n"; }
	$move_lead_rslt = mysql_to_mysqli($move_lead_stmt, $link);
	$move_lead_count = mysqli_affected_rows($link);
		
	$move_sentence = "$move_lead_count "._QXZ("leads have been moved from list")." $move_from_list "._QXZ("to")." $move_to_list "._QXZ("with the status")." $move_status "._QXZ("and that were called")." $move_count_op_phrase$move_count_num "._QXZ("times").".";
	
	$SQL_log = "$move_lead_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$move_from_list', event_code='ADMIN MOVE LEADS', event_sql=\"$SQL_log\", event_notes='$move_sentence';";
	if ($DB) {echo "|$admin_log_stmt|\n";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$move_sentence</p>";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over").".</a></p>\n";
	}


# update confirmation page
if ($update_submit == _QXZ("update") )
	{
	# get the variables
	$update_list="";
	$update_from_status="";
	$update_to_status="";
	$update_count_op="";
	$update_count_num="";

	if (isset($_GET["update_list"])) {$update_list=$_GET["update_list"];}
		elseif (isset($_POST["update_list"])) {$update_list=$_POST["update_list"];}
	if (isset($_GET["update_from_status"])) {$update_from_status=$_GET["update_from_status"];}
		elseif (isset($_POST["update_from_status"])) {$update_from_status=$_POST["update_from_status"];}
	if (isset($_GET["update_to_status"])) {$update_to_status=$_GET["update_to_status"];}
		elseif (isset($_POST["update_to_status"])) {$update_to_status=$_POST["update_to_status"];}
	if (isset($_GET["update_count_op"])) {$update_count_op=$_GET["update_count_op"];}
		elseif (isset($_POST["update_count_op"])) {$update_count_op=$_POST["update_count_op"];}
	if (isset($_GET["update_count_num"])) {$update_count_num=$_GET["update_count_num"];}
		elseif (isset($_POST["update_count_num"])) {$update_count_num=$_POST["update_count_num"];}
			
	$update_from_status = preg_replace('/[^-_0-9\p{L}]/u','',$update_from_status);
	$update_to_status = preg_replace('/[^-_0-9\p{L}]/u','',$update_to_status);
	$update_list = preg_replace('/[^0-9]/','',$update_list);
	$update_count_num = preg_replace('/[^0-9]/','',$update_count_num);
	$update_count_op = preg_replace('/[^<>=]/','',$update_count_op);

	$update_count_op_phrase="";
	if ( $update_count_op == "<" )
		{
		$update_count_op_phrase= _QXZ("less than")." ";
		}
	elseif ( $update_count_op == "<=" )
		{
		$update_count_op_phrase= _QXZ("less than or equal to")." ";
		}
	elseif ( $update_count_op == ">" )
		{
		$update_count_op_phrase= _QXZ("greater than")." ";
		}
	elseif ( $update_count_op == ">=" )
		{
		$update_count_op_phrase= _QXZ("greater than or equal to")." ";
		}

	# get the number of leads this action will move
	$update_lead_count=0;
	$update_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$update_list' and status = '$update_from_status' and called_count $update_count_op $update_count_num";
	if ($DB) { echo "|$update_lead_count_stmt|\n"; }
	$update_lead_count_rslt = mysql_to_mysqli($update_lead_count_stmt, $link);
	$update_lead_count_row = mysqli_fetch_row($update_lead_count_rslt);
	$update_lead_count = $update_lead_count_row[0];

	echo "<p>"._QXZ("You are about to update")." $update_lead_count "._QXZ("leads in list")." $update_list "._QXZ("from the status")." $update_from_status "._QXZ("to the status")." $update_to_status "._QXZ("that were called")." $update_count_op_phrase$update_count_num "._QXZ("times").". "._QXZ("Please press confirm to continue").".</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=update_list value='$update_list'>\n";
	echo "<input type=hidden name=update_from_status value='$update_from_status'>\n";
	echo "<input type=hidden name=update_to_status value='$update_to_status'>\n";
	echo "<input type=hidden name=update_count_op value='$update_count_op'>\n";
	echo "<input type=hidden name=update_count_num value='$update_count_num'>\n";
	echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_update value='"._QXZ("confirm")."'>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over").".</a></p>\n";
	echo "</body>\n</html>\n";

	}

# actually do the update
if ($confirm_update == _QXZ("confirm"))
	{
	# get the variables
	$update_list="";
	$update_from_status="";
	$update_to_status="";
	$update_count_op="";
	$update_count_num="";

	if (isset($_GET["update_list"])) {$update_list=$_GET["update_list"];}
		elseif (isset($_POST["update_list"])) {$update_list=$_POST["update_list"];}
	if (isset($_GET["update_from_status"])) {$update_from_status=$_GET["update_from_status"];}
		elseif (isset($_POST["update_from_status"])) {$update_from_status=$_POST["update_from_status"];}
	if (isset($_GET["update_to_status"])) {$update_to_status=$_GET["update_to_status"];}
		elseif (isset($_POST["update_to_status"])) {$update_to_status=$_POST["update_to_status"];}
	if (isset($_GET["update_count_op"])) {$update_count_op=$_GET["update_count_op"];}
		elseif (isset($_POST["update_count_op"])) {$update_count_op=$_POST["update_count_op"];}
	if (isset($_GET["update_count_num"])) {$update_count_num=$_GET["update_count_num"];}
		elseif (isset($_POST["update_count_num"])) {$update_count_num=$_POST["update_count_num"];}

	$update_from_status = preg_replace('/[^-_0-9\p{L}]/u','',$update_from_status);
	$update_to_status = preg_replace('/[^-_0-9\p{L}]/u','',$update_to_status);
	$update_list = preg_replace('/[^0-9]/','',$update_list);
	$update_count_num = preg_replace('/[^0-9]/','',$update_count_num);
	$update_count_op = preg_replace('/[^<>=]/','',$update_count_op);
		
	$update_count_op_phrase="";
	if ( $update_count_op == "<" )
		{
		$update_count_op_phrase= _QXZ("less than")." ";
		}
	elseif ( $update_count_op == "<=" )
		{
		$update_count_op_phrase= _QXZ("less than or equal to")." ";
		}
	elseif ( $update_count_op == ">" )
		{
		$update_count_op_phrase= _QXZ("greater than")." ";
		}
	elseif ( $update_count_op == ">=" )
		{
		$update_count_op_phrase= _QXZ("greater than or equal to")." ";
		}

	$update_lead_stmt = "UPDATE vicidial_list SET status = '$update_to_status' WHERE list_id = '$update_list' and status = '$update_from_status' and called_count $update_count_op $update_count_num";
	if ($DB) { echo "|$delete_lead_stmt|\n"; }
	$update_lead_rslt = mysql_to_mysqli($update_lead_stmt, $link);
	$update_lead_count = mysqli_affected_rows($link);
		
		$update_sentence = "$update_lead_count "._QXZ("leads had their status changed from")." $update_from_status "._QXZ("to")." $update_to_status "._QXZ("in list")." $update_list "._QXZ("that were called")." $update_count_op_phrase$update_count_num "._QXZ("times").".";
		
		$SQL_log = "$update_lead_stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='MODIFY', record_id='$update_list', event_code='ADMIN UPDATE LEADS', event_sql=\"$SQL_log\", event_notes='$update_sentence';";
		if ($DB) {echo "|$admin_log_stmt|\n";}
		$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);

	echo "<p>$update_sentence</p>";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over").".</a></p>\n";
	}


# delete confirmation page
if ( ( $delete_submit == _QXZ("delete") ) && ( $delete_lists > 0 ) )
	{
	# get the variables
	$delete_list="";
	$delete_status="";
	$delete_count_op="";
	$delete_count_num="";

	if (isset($_GET["delete_list"])) {$delete_list=$_GET["delete_list"];}
		elseif (isset($_POST["delete_list"])) {$delete_list=$_POST["delete_list"];}
	if (isset($_GET["delete_status"])) {$delete_status=$_GET["delete_status"];}
		elseif (isset($_POST["delete_status"])) {$delete_status=$_POST["delete_status"];}
	if (isset($_GET["delete_count_op"])) {$delete_count_op=$_GET["delete_count_op"];}
		elseif (isset($_POST["delete_count_op"])) {$delete_count_op=$_POST["delete_count_op"];}
	if (isset($_GET["delete_count_num"])) {$delete_count_num=$_GET["delete_count_num"];}
		elseif (isset($_POST["delete_count_num"])) {$delete_count_num=$_POST["delete_count_num"];}
	
	$delete_status = preg_replace('/[^-_0-9\p{L}]/u','',$delete_status);
	$delete_list = preg_replace('/[^0-9]/','',$delete_list);
	$delete_count_num = preg_replace('/[^0-9]/','',$delete_count_num);
	$delete_count_op = preg_replace('/[^<>=]/','',$delete_count_op);

	$delete_count_op_phrase="";
		if ( $delete_count_op == "<" )
		{
		$delete_count_op_phrase= _QXZ("less than")." ";
		}
	elseif ( $delete_count_op == "<=" )
		{
		$delete_count_op_phrase= _QXZ("less than or equal to")." ";
		}
	elseif ( $delete_count_op == ">" )
		{
		$delete_count_op_phrase= _QXZ("greater than")." ";
		}
	elseif ( $delete_count_op == ">=" )
		{
		$delete_count_op_phrase= _QXZ("greater than or equal to")." ";
		}

	# get the number of leads this action will move
	$delete_lead_count=0;
	$delete_lead_count_stmt = "SELECT count(1) FROM vicidial_list WHERE list_id = '$delete_list' and status = '$delete_status' and called_count $delete_count_op $delete_count_num";
	if ($DB) { echo "|$delete_lead_count_stmt|\n"; }
	$delete_lead_count_rslt = mysql_to_mysqli($delete_lead_count_stmt, $link);
	$delete_lead_count_row = mysqli_fetch_row($delete_lead_count_rslt);
	$delete_lead_count = $delete_lead_count_row[0];

	echo "<p>"._QXZ("You are about to delete")." $delete_lead_count "._QXZ("leads in list")." $delete_list "._QXZ("with the status")." $delete_status "._QXZ("that were called")." $delete_count_op_phrase$delete_count_num "._QXZ("times").". "._QXZ("Please press confirm to continue").".</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=delete_list value='$delete_list'>\n";
	echo "<input type=hidden name=delete_status value='$delete_status'>\n";
	echo "<input type=hidden name=delete_count_op value='$delete_count_op'>\n";
	echo "<input type=hidden name=delete_count_num value='$delete_count_num'>\n";
	echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm_delete value='"._QXZ("confirm")."'>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over").".</a></p>\n";
	echo "</body>\n</html>\n";

	}

# actually do the delete
if ( ( $confirm_delete == _QXZ("confirm") ) && ( $delete_lists > 0 ) )
	{
	# get the variables
	$delete_list="";
	$delete_status="";
	$delete_count_op="";
	$delete_count_num="";

	if (isset($_GET["delete_list"])) {$delete_list=$_GET["delete_list"];}
		elseif (isset($_POST["delete_list"])) {$delete_list=$_POST["delete_list"];}
	if (isset($_GET["delete_status"])) {$delete_status=$_GET["delete_status"];}
		elseif (isset($_POST["delete_status"])) {$delete_status=$_POST["delete_status"];}
	if (isset($_GET["delete_count_op"])) {$delete_count_op=$_GET["delete_count_op"];}
		elseif (isset($_POST["delete_count_op"])) {$delete_count_op=$_POST["delete_count_op"];}
	if (isset($_GET["delete_count_num"])) {$delete_count_num=$_GET["delete_count_num"];}
		elseif (isset($_POST["delete_count_num"])) {$delete_count_num=$_POST["delete_count_num"];}

	$delete_status = preg_replace('/[^-_0-9\p{L}]/u','',$delete_status);
	$delete_list = preg_replace('/[^0-9]/','',$delete_list);
	$delete_count_num = preg_replace('/[^0-9]/','',$delete_count_num);
	$delete_count_op = preg_replace('/[^<>=]/','',$delete_count_op);
		
	$delete_count_op_phrase="";
	if ( $delete_count_op == "<" )
		{
		$delete_count_op_phrase= _QXZ("less than")." ";
		}
	elseif ( $delete_count_op == "<=" )
		{
		$delete_count_op_phrase= _QXZ("less than or equal to")." ";
		}
	elseif ( $delete_count_op == ">" )
		{
		$delete_count_op_phrase= _QXZ("greater than")." ";
		}
	elseif ( $delete_count_op == ">=" )
		{
		$delete_count_op_phrase= _QXZ("greater than or equal to")." ";
		}

	$delete_lead_stmt = "DELETE FROM vicidial_list WHERE list_id = '$delete_list' and status = '$delete_status' and called_count $delete_count_op $delete_count_num";
	if ($DB) { echo "|$delete_lead_stmt|\n"; }
	$delete_lead_rslt = mysql_to_mysqli($delete_lead_stmt, $link);
	$delete_lead_count = mysqli_affected_rows($link);

		$delete_sentence = "$delete_lead_count "._QXZ("leads delete from list")." $delete_list "._QXZ("with the status")." $delete_status "._QXZ("that were called")." $delete_count_op_phrase$delete_count_num "._QXZ("times").".";
		
		$SQL_log = "$update_lead_stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='DELETE', record_id='$delete_list', event_code='ADMIN DELETE LEADS', event_sql=\"$SQL_log\", event_notes='$delete_sentence';";
		if ($DB) {echo "|$admin_log_stmt|\n";}
		$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
		
	echo "<p>$delete_sentence</p>";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over").".</a></p>\n";
	}

# main page display
if (
		($move_submit != _QXZ("move") ) && ($update_submit != _QXZ("update")) && ($delete_submit != _QXZ("delete")) &&
		($confirm_move != _QXZ("confirm")) && ($confirm_update != _QXZ("confirm")) && ($confirm_delete != _QXZ("confirm"))
	)
	{
	# figure out which campaigns this user is allowed to work on
	$allowed_campaigns_stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$user_group';";
	if ($DB) { echo "|$allowed_campaigns_stmt|\n"; }
	$rslt = mysql_to_mysqli($allowed_campaigns_stmt, $link);
	$allowed_campaigns_row = mysqli_fetch_row($rslt);
	$allowed_campaigns = $allowed_campaigns_row[0];
	if ($DB) { echo "|$allowed_campaigns|\n"; }
	$allowed_campaigns_sql = "";
	if ( preg_match("/ALL\-CAMPAIGNS/i",$allowed_campaigns) )
		{
		if ($DB) { echo "|"._QXZ("Processing All Campaigns")."|\n"; }
		$campaign_id_stmt = "SELECT campaign_id FROM vicidial_campaigns";
		$campaign_id_rslt = mysql_to_mysqli($campaign_id_stmt, $link);
		$campaign_id_num_rows = mysqli_num_rows($campaign_id_rslt);
		if ($DB) { echo "|campaign_id_num_rows = $campaign_id_num_rows|\n"; }
		if ($campaign_id_num_rows > 0)
			{
			$i = 0;
			while ( $i < $campaign_id_num_rows )
				{
				$campaign_id_row = mysqli_fetch_row($campaign_id_rslt);
				if ( $i == 0 )
					{
					$allowed_campaigns_sql = "'$campaign_id_row[0]'";
					}
				else
					{
					$allowed_campaigns_sql = "$allowed_campaigns_sql, '$campaign_id_row[0]'";
					}
				$i++;
				}
			}
		}
	else
		{
		$allowed_campaigns_sql = preg_replace("/ -/",'',$allowed_campaigns);
		$allowed_campaigns_sql = preg_replace("/^ /",'',$allowed_campaigns_sql);
		$allowed_campaigns_sql = preg_replace("/ $/",'',$allowed_campaigns_sql);
		$allowed_campaigns_sql = preg_replace("/ /","','",$allowed_campaigns_sql);
		$allowed_campaigns_sql = "'$allowed_campaigns_sql'";
		}

	# figure out which lists they are allowed to see
	$activeSQL = "and active = 'N'";
	if ($SSallow_manage_active_lists > 0) {$activeSQL = '';}
	$lists_stmt = "SELECT list_id, list_name FROM vicidial_lists WHERE campaign_id IN ($allowed_campaigns_sql) $activeSQL ORDER BY list_id";
	if ($DB) { echo "|$lists_stmt|\n"; }
	$lists_rslt = mysql_to_mysqli($lists_stmt, $link);
	$num_rows = mysqli_num_rows($lists_rslt);
	$i = 0;
	$allowed_lists_count = 0;
	while ( $i < $num_rows )
		{
		$lists_row = mysqli_fetch_row($lists_rslt);

		# check how many leads are in the list
		$lead_count_stmt = "SELECT count(1)  FROM vicidial_list WHERE list_id = '$lists_row[0]'";
		if ($DB) { echo "|$lead_count_stmt|\n"; }
		$lead_count_rslt = mysql_to_mysqli($lead_count_stmt, $link);
		$lead_count_row = mysqli_fetch_row($lead_count_rslt);
		$lead_count = $lead_count_row[0];

		# only show lists that are under the list_lead_limit
		if ( $lead_count <= $list_lead_limit )
			{
			$list_ary[$allowed_lists_count] = $lists_row[0];
			$list_name_ary[$allowed_lists_count] = $lists_row[1];
			$list_lead_count_ary[$allowed_lists_count] = $lead_count_row[0];

			if ($allowed_lists_count == 0)
				{
				$allowed_lists_sql = "'$lists_row[0]'";
				}
			else
				{
				$allowed_lists_sql = "$allowed_lists_sql, '$lists_row[0]'";
				}

			$allowed_lists_count++;
			}
		$i++;
		}

	# figure out which statuses are in the lists they are allowed to look at
	$status_stmt = "SELECT DISTINCT status FROM vicidial_list WHERE list_id IN ( $allowed_lists_sql ) ORDER BY status";
	if ($DB) { echo "|$status_stmt|\n"; }
	$status_rslt = mysql_to_mysqli($status_stmt, $link);
	$status_count=mysqli_num_rows($status_rslt);
	$i = 0;
	while ( $i < $status_count )
		{
		$status_row = mysqli_fetch_row($status_rslt);
		$statuses[$i] = $status_row[0];
		$i++;
		}

	# figure out which statuses are in the lists they are allowed to look at
	$sys_status_stmt = "SELECT status FROM vicidial_statuses ORDER BY status";
	if ($DB) { echo "|$sys_status_stmt|\n"; }
	$sys_status_rslt = mysql_to_mysqli($sys_status_stmt, $link);
	$sys_status_count=mysqli_num_rows($sys_status_rslt);
	$i = 0;
	while ( $i < $sys_status_count )
		{
		$sys_status_row = mysqli_fetch_row($sys_status_rslt);
		$sys_statuses[$i] = $sys_status_row[0];
		$i++;
		}


	if ($SSallow_manage_active_lists > 0)
		{echo "<p>"._QXZ("The following are basic lead management tools.  They will only work on lists with less than");}
	else
		{echo "<p>"._QXZ("The following are basic lead management tools.  They will only work on inactive lists with less than");}
	echo " $list_lead_limit "._QXZ("leads in them. This is to avoid data inconsistencies").".</p>";
	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<center><table width=$section_width cellspacing=3>\n";

	# BEGIN lead move
	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>"._QXZ("Move Leads")."</b></font></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("From List")."</td><td align=left>\n";
	echo "<select size=1 name=move_from_list>\n";
	echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] leads)</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("To List")."</td><td align=left>\n";
	echo "<select size=1 name=move_to_list>\n";
	echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] leads)</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("Status")."</td><td align=left>\n";
	echo "<select size=1 name=move_status>\n";
	echo "<option value='-'>"._QXZ("Select A Status")."</option>\n";
	echo "<option value='%'>"._QXZ("All Statuses")."</option>\n";

	$i = 0;
	while ( $i < $status_count )
		{
		echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
		$i++;
		}
		
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("Called Count")."</td><td align=left>\n";
	echo "<select size=1 name=move_count_op>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='='>=</option>\n";
	echo "</select>\n";
	echo "<select size=1 name=move_count_num>\n";
	$i=0;
	while ( $i <= $max_count )
		{
		echo "<option value='$i'>$i</option>\n";
		$i++;
		}
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=move_submit value='"._QXZ("move")."'></td></tr>\n";
	echo "</table></center>\n";
	# END lead move

	# BEGIN Status Update
	echo "<br /><center><table width=$section_width cellspacing=3>\n";
	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>"._QXZ("Update Lead Statuses")."</b></font></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("List")."</td><td align=left>\n";
	echo "<select size=1 name=update_list>\n";
	echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] "._QXZ("leads").")</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("From Status")."</td><td align=left>\n";
	echo "<select size=1 name=update_from_status>\n";
	echo "<option value='-'>"._QXZ("Select A Status")."</option>\n";

	$i = 0;
	while ( $i < $status_count )
		{
		echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("To Status")."</td><td align=left>\n";
	echo "<select size=1 name=update_to_status>\n";
	echo "<option value='-'>"._QXZ("Select A Status")."</option>\n";

	$i = 0;
	while ( $i < $sys_status_count )
		{
		echo "<option value='$sys_statuses[$i]'>$sys_statuses[$i]</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("Called Count")."</td><td align=left>\n";
	echo "<select size=1 name=update_count_op>\n";
	echo "<option value='<'><</option>\n";
	echo "<option value='<='><=</option>\n";
	echo "<option value='>'>></option>\n";
	echo "<option value='>='>>=</option>\n";
	echo "<option value='='>=</option>\n";
	echo "</select>\n";
	echo "<select size=1 name=update_count_num>\n";
	$i=0;
	while ( $i <= $max_count )
		{
		echo "<option value='$i'>$i</option>\n";
		$i++;
		}
	echo "</select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=update_submit value='"._QXZ("update")."'></td></tr>\n";
	# END Status Update

	if ( $delete_lists > 0 )
		{
		# BEGIN Delete Leads
		echo "</table></center>\n";
		echo "<br /><center><table width=$section_width cellspacing=3>\n";
		echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>"._QXZ("Delete Leads")."</b></font></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("List")."</td><td align=left>\n";
		echo "<select size=1 name=delete_list>\n";
		echo "<option value='-'>"._QXZ("Select A List")."</option>\n";

		$i = 0;
		while ( $i < $allowed_lists_count )
			{
			echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] "._QXZ("leads").")</option>\n";
			$i++;
			}

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("Status")."</td><td align=left>\n";
		echo "<select size=1 name=delete_status>\n";
		echo "<option value='-'>"._QXZ("Select A Status")."</option>\n";

		$i = 0;
		while ( $i < $status_count )
			{
			echo "<option value='$statuses[$i]'>$statuses[$i]</option>\n";
			$i++;
			}

		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td align=right>"._QXZ("Called Count")."</td><td align=left>\n";
		echo "<select size=1 name=delete_count_op>\n";
				echo "<option value='<'><</option>\n";
				echo "<option value='<='><=</option>\n";
				echo "<option value='>'>></option>\n";
				echo "<option value='>='>>=</option>\n";
				echo "<option value='='>=</option>\n";
		echo "</select>\n";
		echo "<select size=1 name=delete_count_num>\n";
		$i=0;
		while ( $i <= $max_count )
			{
			echo "<option value='$i'>$i</option>\n";
			$i++;
			}
		echo "</select></td></tr>\n";
		echo "<tr bgcolor=#$SSstd_row3_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=delete_submit value='"._QXZ("delete")."'></td></tr>\n";
		# END Delete Leads

		}

	echo "</table></center>\n";
	echo "</form>\n";
	echo "</body></html>\n";
	}

echo "</td></tr></table>\n";
?>
