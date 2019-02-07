<?php
# welcome.php - VICIDIAL welcome page
# 
# Copyright (C) 2016  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG:
# 141007-2140 - Finalized adding QXZ translation to all admin files
# 161106-1920 - Changed to use newer design and dynamic links
#

header ("Content-type: text/html; charset=utf-8");

require_once("dbconnect_mysqli.php");
require("functions.php");

# if options file exists, use the override values for the above variables
#   see the options-example.php file for more information
if (file_exists('options.php'))
	{
	require_once('options.php');
	}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,default_language,agent_screen_colors,admin_web_directory,agent_script FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
	if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'01001',$VD_login,$server_ip,$session_name,$one_mysql_log);}
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$SSenable_languages =		$row[1];
	$SSlanguage_method =		$row[2];
	$default_language =			$row[3];
	$agent_screen_colors =		$row[4];
	$admin_web_directory =		$row[5];
	$SSagent_script =			$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################

##### BEGIN Define colors and logo #####
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

if ($agent_screen_colors != 'default')
	{
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$agent_screen_colors';";
	$rslt=mysql_to_mysqli($stmt, $link);
		if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'01XXX',$VD_login,$server_ip,$session_name,$one_mysql_log);}
	if ($DB) {echo "$stmt\n";}
	$qm_conf_ct = mysqli_num_rows($rslt);
	if ($qm_conf_ct > 0)
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
$logo_new=0;
$logo_old=0;
if (file_exists('../$admin_web_directory/images/vicidial_admin_web_logo.png')) {$logo_new++;}
if (file_exists('vicidial_admin_web_logo.gif')) {$logo_old++;}
if ($SSweb_logo=='default_new')
	{
	$selected_logo = "./images/vicidial_admin_web_logo.png";
	}
if ( ($SSweb_logo=='default_old') and ($logo_old > 0) )
	{
	$selected_logo = "../$admin_web_directory/vicidial_admin_web_logo.gif";
	}
if ( ($SSweb_logo!='default_new') and ($SSweb_logo!='default_old') )
	{
	if (file_exists("../$admin_web_directory/images/vicidial_admin_web_logo$SSweb_logo")) 
		{
		$selected_logo = "../$admin_web_directory/images/vicidial_admin_web_logo$SSweb_logo";
		}
	}
##### END Define colors and logo #####

echo"<HTML><HEAD>\n";
echo"<TITLE>"._QXZ("Welcome Screen")."</TITLE>\n";
echo"<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../agc/css/style.css\" />\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../agc/css/custom.css\" />\n";
echo"</HEAD>\n";
echo "<BODY BGCOLOR=WHITE MARGINHEIGHT=0 MARGINWIDTH=0>\n";
echo "<table width=\"100%\"><tr><td></td>\n";
echo "</tr></table>\n";
echo "<br /><br /><br /><center><table width=\"460px\" cellpadding=\"3\" cellspacing=\"0\" bgcolor=\"#$SSframe_background\"><tr bgcolor=\"white\">";
echo "<td align=\"left\" valign=\"bottom\" bgcolor=\"#$SSmenu_background\" width=\"170\"><img src=\"$selected_logo\" border=\"0\" height=\"45\" width=\"170\" alt=\"Agent Screen\" /></td>";
echo "<td align=\"center\" valign=\"middle\" bgcolor=\"#$SSmenu_background\"> <font class=\"sh_text_white\">"._QXZ("Welcome")."</font> </td>";
echo "</tr>\n";
echo "<tr><td align=\"left\" colspan=\"2\"><font size=\"1\"> &nbsp; </font></td></tr>\n";

echo "<TR><TD ALIGN=CENTER COLSPAN=2><font size=1> &nbsp; </TD></TR>\n";
echo "<TR><TD ALIGN=CENTER COLSPAN=2><font class=\"skb_text\"> <a href=\"../agc/$SSagent_script\">"._QXZ("Agent Login")."</a> </TD></TR>\n";
echo "<TR><TD ALIGN=CENTER COLSPAN=2><font size=1> &nbsp; </TD></TR>\n";
if ($hide_timeclock_link < 1)
	{echo "<TR><TD ALIGN=CENTER COLSPAN=2><font class=\"skb_text\"> <a href=\"../agc/timeclock.php?referrer=welcome\"> "._QXZ("Timeclock")."</a> </TD></TR>\n";}
echo "<TR><TD ALIGN=CENTER COLSPAN=2><font size=1> &nbsp; </TD></TR>\n";
echo "<TR><TD ALIGN=CENTER COLSPAN=2><font class=\"skb_text\"> <a href=\"../$admin_web_directory/admin.php\">"._QXZ("Administration")."</a> </TD></TR>\n";
echo "<TR><TD ALIGN=CENTER COLSPAN=2><font size=1> &nbsp; </TD></TR>\n";

echo "</table></center>\n";
echo "</form>\n\n";
echo "</BODY>\n\n";
echo "</HTML>\n\n";
exit;
?>