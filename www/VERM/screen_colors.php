<?php
# 
# screen_colors.php    version 2.14
#
# include file to generate screen colors - normally in admin_header.php, 
# but most reports include admin_header.php after the colors need to be set
#
# Copyright (C) 2020  Joseph Johnson <freewermadmin@gmail.com>, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
#
# CHANGES:
# 170828-2345 - First Build
# 200814-2247 - Added button color
#

##### BEGIN Define colors and logo #####
$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='B9CBFD';
$SSstd_row3_background='8EBCFD';
$SSstd_row4_background='B6D3FC';
$SSstd_row5_background='FFFFFF';
$SSalt_row1_background='BDFFBD';
$SSalt_row2_background='99FF99';
$SSalt_row3_background='CCFFCC';
$SSbutton_color='EFEFEF';

$screen_color_stmt="SELECT admin_screen_colors from system_settings";
$screen_color_rslt=mysql_to_mysqli($screen_color_stmt, $link);
$screen_color_row=mysqli_fetch_row($screen_color_rslt);
$agent_screen_colors="$screen_color_row[0]";

if ($agent_screen_colors != 'default')
	{
	$asc_stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo,button_color FROM vicidial_screen_colors where colors_id='$agent_screen_colors';";
	$asc_rslt=mysql_to_mysqli($asc_stmt, $link);
	$qm_conf_ct = mysqli_num_rows($asc_rslt);
	if ($qm_conf_ct > 0)
		{
		$asc_row=mysqli_fetch_row($asc_rslt);
		$SSmenu_background =            $asc_row[0];
		$SSframe_background =           $asc_row[1];
		$SSstd_row1_background =        $asc_row[2];
		$SSstd_row2_background =        $asc_row[3];
		$SSstd_row3_background =        $asc_row[4];
		$SSstd_row4_background =        $asc_row[5];
		$SSstd_row5_background =        $asc_row[6];
		$SSalt_row1_background =        $asc_row[7];
		$SSalt_row2_background =        $asc_row[8];
		$SSalt_row3_background =        $asc_row[9];
		$SSweb_logo =			$asc_row[10];
		$SSbutton_color = 		$asc_row[11];
		}
	}
?>
