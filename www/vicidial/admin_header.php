<?php
# admin_header.php - VICIDIAL administration header
#
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
# 

# CHANGES
# 90310-0709 - First Build
# 90508-0542 - Added Call Menu option, changed script to use long PHP tags
# 90514-0605 - Added audio prompt selection functions
# 90530-1206 - Changed List Mix to allow for 40 mixes and a default populate option
# 90531-2339 - Added Dynamic options for Call Menu
# 90612-0852 - Changed relative links
# 90635-0943 - Added javascript for dynamic menus in In-Groups
# 90627-0548 - Added no-agent-no-queue options
# 90628-1016 - Added Text-to-speech options
# 90830-2213 - Added Music On Hold options
# 90904-1534 - Added launch_moh_chooser
# 90916-2334 - Added Voicemail options
# 91223-1030 - Added VIDPROMPT options for in-group routing in Call Menus
# 100311-2210 - Added callcard_enabled Admin element
# 100428-1039 - Added custom fields display
# 100507-1339 - Added copy carrier submenu
# 100616-2350 - Added VIDPROMPT call menu options
# 100728-0904 - Changed Lead Loader link to the new 3rd gen lead loader
# 100802-2127 - Changed Admin to point to links page instead of Phones listings
# 100831-2255 - Added password strength checking function
# 100914-1311 - Removed other links for reports-only users
# 101022-1323 - Added IGU_selectall function
# 101107-1133 - Added auto-refresh code
# 110215-1720 - Added the Add a new lead link
# 110322-1228 - Added user ID logged in as next to Logout link
# 110624-1439 - Added Screen Labels sub-section to Admin
# 110831-2048 - Added AC-CID to campaign submenu
# 110922-1707 - Added RA-EXTEN to campaign submenu
# 111015-2305 - Added Contacts menu to Admin
# 111106-0939 - Changes for user group restrictions
# 111116-0208 - Added ALT and ADDR3 in-group handle methods
# 120402-2134 - Changed lead loader link to fourth gen
# 121116-1412 - Added QC functionality
# 121123-0911 - Added Call Times Holidays Inbound functionality
# 121214-2238 - Added email menus
# 130221-1830 - Added Level 8 disable add option
# 130610-1040 - Finalized changing of all ereg instances to preg
# 130615-2314 - Changed Reports only and QC only headers
# 130824-2324 - Changed to mysqli PHP functions
# 140126-1022 - Added VMAIL_NO_INST option
# 140706-0828 - Incorporated QC includes into code
# 141001-2200 - Finalized adding QXZ translation to all admin files
# 141128-1009 - Added code for languages section
# 141230-0927 - Changed single-quote QXZ arguments to double-quotes
# 150227-1614 - Formatting issue #831
# 150307-0915 - Fixes for QXZ
# 150806-1023 - Added code for Admin -> Settings Containers
# 150808-2043 - Added compatibility for custom fields data option
# 151020-0654 - Added Status Groups
# 151203-1922 - Fix for javascript timezone issues in editing of timeclock entries
# 151212-0858 - Added Chat link at top of page
# 160312-1656 - Added FORM_selectall javascript, also used it to replace IGU_selectall
# 160325-1428 - Changed layout of sidebar links
# 160327-0146 - Changes to design
# 160328-1930 - Fixed display bugs
# 160330-1600 - Redesign of Admin sub-menu and added icons
# 160404-0934 - design changes
# 160429-1014 - Added admin_row_click option
# 160507-2324 - Added screen colors section
# 160611-2229 - Added style for diff on admin changes
# 160617-1426 - Added link from screen colors to system settings screen color settings
# 161101-2129 - Added user_new_lead_limit Users subhead
# 161103-2136 - Added agent_soundboards option
# 170113-1633 - Added dynamic call menu in-group option DYNAMIC_INGROUP_VAR for use with cm_phonesearch.agi
# 170304-1354 - Added Automated Reports section to Admin
# 170409-0757 - Added IP Lists
# 170623-2113 - Changed password strength parameters
# 170821-2010 - Fix for issue #1036
# 180213-1657 - Added CID Groups
# 180512-1105 - Changed to dynamic DB help
# 180614-2200 - Modified audio chooser window to appear at height where mouse was clicked in JS functions
# 180618-2300 - Modified calls to audio file chooser function
# 190121-1803 - Added special streamlined header option for mobile devices
# 190530-1013 - Added javascript for active list change feature
# 191028-1739 - Added VM Message Groups
# 200206-2314 - Added password length indicator
# 200407-1534 - Added play_browser_sound javascript function for campaigns/ingroups
# 200409-1720 - Reorganized the Inbound section
# 200508-1052 - Added copy_prev_cm_option() JS function
# 201108-1232 - Added no_header option within short_header option
# 210226-1547 - Added Copy Phone link
# 210306-0902 - Changes for new QC module
# 210707-0730 - Added header links for timezones, phone codes and postal codes
# 210907-1904 - Added new OWNERCUSTOMx handle methods for menu options
# 211208-1920 - Added Queue Groups to admin sidebar and sub-menu
# 211215-2225 - Added User Group options for altered header display
#

$stmt="SELECT admin_home_url,enable_tts_integration,callcard_enabled,custom_fields_enabled,allow_emails,level_8_disable_add,allow_chats,enable_languages,admin_row_click,admin_screen_colors,user_new_lead_limit,user_territories_active,qc_features_active,agent_soundboards,enable_drop_lists,allow_ip_lists from system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$admin_home_url_LU =		$row[0];
$SSenable_tts_integration = $row[1];
$SScallcard_enabled =		$row[2];
$SScustom_fields_enabled =	$row[3];
$SSemail_enabled =			$row[4];
$SSlevel_8_disable_add =	$row[5];
$SSchat_enabled =			$row[6];
$SSenable_languages =		$row[7];
$SSadmin_row_click =		$row[8];
$SSadmin_screen_colors =	$row[9];
$SSuser_new_lead_limit =	$row[10];
$SSuser_territories_active = $row[11];
$SSqc_features_active =		$row[12];
$SSagent_soundboards =		$row[13];
$SSenable_drop_lists =		$row[14];
$SSallow_ip_lists =			$row[15];

if (strlen($SSadmin_home_url) > 5) {$admin_home_url_LU = $SSadmin_home_url;}

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
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo,button_color FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
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
		$SSweb_logo =			$row[10];
		$SSbutton_color = 		$row[11];
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


##### BEGIN populate dynamic header content #####
if ($hh=='users') 
	{
	$users_hh="CLASS=\"head_style_selected\""; $users_fc="$users_font"; $users_bold="$header_selected_bold";
	$users_icon="<img src=\"images/icon_black_users.png\" border=0 alt=\"Users\" width=14 height=14 valign=middle>";
	}
else 
	{
	$users_hh="CLASS=\"head_style\""; $users_fc='WHITE'; $users_bold="$header_nonselected_bold";
	$users_icon="<img src=\"images/icon_users.png\" border=0 alt=\"Users\" width=14 height=14 valign=middle>";
	}
if ($hh=='campaigns') 
	{
	$campaigns_hh="CLASS=\"head_style_selected\""; $campaigns_fc="$campaigns_font"; $campaigns_bold="$header_selected_bold";
	$campaigns_icon="<img src=\"images/icon_black_campaigns.png\" border=0 alt=\"Campaigns\" width=14 height=14 valign=middle>";
	}
else 
	{
	$campaigns_hh="CLASS=\"head_style\""; $campaigns_fc='WHITE'; $campaigns_bold="$header_nonselected_bold";
	$campaigns_icon="<img src=\"images/icon_campaigns.png\" border=0 alt=\"Campaigns\" width=14 height=14 valign=middle>";
	}
if ($hh=='lists') 
	{
	$lists_hh="CLASS=\"head_style_selected\""; $lists_fc="$lists_font"; $lists_bold="$header_selected_bold";
	$lists_icon="<img src=\"images/icon_black_lists.png\" border=0 alt=\"Lists\" width=14 height=14 valign=middle>";
	}
else 
	{
	$lists_hh="CLASS=\"head_style\""; $lists_fc='WHITE'; $lists_bold="$header_nonselected_bold";
	$lists_icon="<img src=\"images/icon_lists.png\" border=0 alt=\"Lists\" width=14 height=14 valign=middle>";
	}
if ($hh=='ingroups') 
	{
	$ingroups_hh="CLASS=\"head_style_selected\""; $ingroups_fc="$ingroups_font"; $ingroups_bold="$header_selected_bold";
	$inbound_icon="<img src=\"images/icon_black_inbound.png\" border=0 alt=\"Inbound\" width=14 height=14 valign=middle>";
	}
else 
	{
	$ingroups_hh="CLASS=\"head_style\""; $ingroups_fc='WHITE'; $ingroups_bold="$header_nonselected_bold";
	$inbound_icon="<img src=\"images/icon_inbound.png\" border=0 alt=\"Inbound\" width=14 height=14 valign=middle>";
	}
if ($hh=='remoteagent') 
	{
	$remoteagent_hh="CLASS=\"head_style_selected\""; $remoteagent_fc="$remoteagent_font"; $remoteagent_bold="$header_selected_bold";
	$remoteagents_icon="<img src=\"images/icon_black_remoteagents.png\" border=0 alt=\"Remote Agents\" width=14 height=14 valign=middle>";
	}
else 
	{
	$remoteagent_hh="CLASS=\"head_style\""; $remoteagent_fc='WHITE'; $remoteagent_bold="$header_nonselected_bold";
	$remoteagents_icon="<img src=\"images/icon_remoteagents.png\" border=0 alt=\"Remote Agents\" width=14 height=14 valign=middle>";
	}
if ($hh=='usergroups') 
	{
	$usergroups_hh="CLASS=\"head_style_selected\""; $usergroups_fc="$usergroups_font"; $usergroups_bold="$header_selected_bold";
	$usergroups_icon="<img src=\"images/icon_black_usergroups.png\" border=0 alt=\"User Groups\" width=14 height=14 valign=middle>";
	}
else 
	{
	$usergroups_hh="CLASS=\"head_style\""; $usergroups_fc='WHITE'; $usergroups_bold="$header_nonselected_bold";
	$usergroups_icon="<img src=\"images/icon_usergroups.png\" border=0 alt=\"User Groups\" width=14 height=14 valign=middle>";
	}
if ($hh=='scripts') 
	{
	$scripts_hh="CLASS=\"head_style_selected\""; $scripts_fc="$scripts_font"; $scripts_bold="$header_selected_bold";
	$scripts_icon="<img src=\"images/icon_black_scripts.png\" border=0 alt=\"Scripts\" width=14 height=14 valign=middle>";
	}
else 
	{
	$scripts_hh="CLASS=\"head_style\""; $scripts_fc='WHITE'; $scripts_bold="$header_nonselected_bold";
	$scripts_icon="<img src=\"images/icon_scripts.png\" border=0 alt=\"Scripts\" width=14 height=14 valign=middle>";
	}
if ($hh=='filters') 
	{
	$filters_hh="CLASS=\"head_style_selected\""; $filters_fc="$filters_font"; $filters_bold="$header_selected_bold";
	$filters_icon="<img src=\"images/icon_black_filters.png\" border=0 alt=\"Filters\" width=14 height=14 valign=middle>";
	}
else 
	{
	$filters_hh="CLASS=\"head_style\""; $filters_fc='WHITE'; $filters_bold="$header_nonselected_bold";
	$filters_icon="<img src=\"images/icon_filters.png\" border=0 alt=\"Filters\" width=14 height=14 valign=middle>";
	}
if ($hh=='admin') 
	{
	$admin_hh="CLASS=\"head_style_selected\""; $admin_fc="$admin_font"; $admin_bold="$header_selected_bold";
	$admin_icon="<img src=\"images/icon_black_admin.png\" border=0 alt=\"Admin\" width=14 height=14 valign=middle>";
	}
else 
	{
	$admin_hh="CLASS=\"head_style\""; $admin_fc='WHITE'; $admin_bold="$header_nonselected_bold";
	$admin_icon="<img src=\"images/icon_admin.png\" border=0 alt=\"Admin\" width=14 height=14 valign=middle>";
	}
if ($hh=='reports') 
	{
	$reports_hh="CLASS=\"head_style_selected\""; $reports_fc="$reports_font"; $reports_bold="$header_selected_bold";
	$reports_icon="<img src=\"images/icon_black_reports.png\" border=0 alt=\"Reports\" width=14 height=14 valign=middle>";
	}
else 
	{
	$reports_hh="CLASS=\"head_style\""; $reports_fc='WHITE'; $reports_bold="$header_nonselected_bold";
	$reports_icon="<img src=\"images/icon_reports.png\" border=0 alt=\"Reports\" width=14 height=14 valign=middle>";
	}
if ($hh=='qc')
	{
	$qc_hh="CLASS=\"head_style_selected\""; $qc_fc="$qc_font"; $qc_bold="$header_selected_bold";
	$qc_icon="<img src=\"images/icon_black_qc.png\" border=0 alt=\"Quality Control\" width=14 height=14 valign=middle>";
	}
else 
	{
	$qc_hh="CLASS=\"head_style\""; $qc_fc='WHITE'; $qc_bold="$header_nonselected_bold";
	$qc_icon="<img src=\"images/icon_qc.png\" border=0 alt=\"Quality Control\" width=14 height=14 valign=middle>";
	}
##### END populate dynamic header content #####

######################### SMALL HTML HEADER BEGIN #######################################
if($short_header)
	{
	if($no_header)
		{
		# display nothing
		}
	else
		{
		if ( ($LOGreports_header_override == 'LOGO_ONLY_SMALL') or ($LOGreports_header_override == 'LOGO_ONLY_LARGE') )
			{
			$temp_logo = $selected_logo;
			$temp_logo_size = 'WIDTH=170 HEIGHT=45 BORDER=0';
			if ($LOGreports_header_override == 'LOGO_ONLY_SMALL') 
				{
				$temp_logo = $selected_small_logo;
				$temp_logo_size = 'WIDTH=71 HEIGHT=22';
				}
			# echo "$SSmenu_background"
			?>
			<TABLE CELLPADDING=0 CELLSPACING=0 BGCOLOR=white><TR>
			<TD><A HREF="<?php echo $admin_home_url_LU ?>"><IMG SRC="<?php echo $temp_logo; ?>" <?php echo $temp_logo_size ?> BORDER=0 ALT="System logo"></A> &nbsp; </TD>
			<TD> &nbsp; </TD>
			<?php 
			}
		else
			{
			?>
			<TABLE CELLPADDING=0 CELLSPACING=0 BGCOLOR=#<?php echo "$SSmenu_background" ?>><TR>
			<TD><A HREF="./admin.php"><IMG SRC="<?php echo $selected_small_logo; ?>" WIDTH=71 HEIGHT=22 BORDER=0 ALT="System logo"></A> &nbsp; </TD>
			<?php 
			if ( ($reports_only_user < 1) and ($qc_only_user < 1) )
				{
			?>
			<TD> &nbsp; <A HREF="admin.php?ADD=999999" ALT="Reports" STYLE="text-decoration:none;"><?php echo $reports_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Reports"); ?></B></FONT></A> &nbsp; </TD>
			<TD> &nbsp; <A HREF="admin.php?ADD=0A" ALT="Users" STYLE="text-decoration:none;"><?php echo $users_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Users"); ?></B></FONT></A> &nbsp; </TD>
			<TD> &nbsp; <A HREF="admin.php?ADD=10" ALT="Campaigns" STYLE="text-decoration:none;"><?php echo $campaigns_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Campaigns"); ?></B></FONT></A> &nbsp; </TD>
			<?php
			if (($SSqc_features_active=='1') && ($qc_auth=='1')) 
				{
				?>
				<TD> &nbsp; <A HREF="admin.php?ADD=100000000000000" ALT="Quality Control" STYLE="text-decoration:none;"><?php echo $qc_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Quality Control"); ?></B></FONT></A> &nbsp; </TD>
				<?php
				}
			?>
			<TD> &nbsp; <A HREF="admin.php?ADD=100" ALT="Lists" STYLE="text-decoration:none;"><?php echo $lists_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Lists"); ?></B></FONT></A> &nbsp; </TD>
			<TD> &nbsp; <A HREF="admin.php?ADD=1000000" ALT="Scripts" STYLE="text-decoration:none;"><?php echo $scripts_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Scripts"); ?></B></FONT></A> &nbsp; </TD>
			<TD> &nbsp; <A HREF="admin.php?ADD=10000000" ALT="Filters" STYLE="text-decoration:none;"><?php echo $filters_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Filters"); ?></B></FONT></A> &nbsp; </TD>
			<TD> &nbsp; <A HREF="admin.php?ADD=1001" ALT="Inbound" STYLE="text-decoration:none;"><?php echo $inbound_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Inbound"); ?></B></FONT></A> &nbsp; </TD>
			<TD> &nbsp; <A HREF="admin.php?ADD=100000" ALT="User Groups" STYLE="text-decoration:none;"><?php echo $usergroups_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("User Groups"); ?></B></FONT></A> &nbsp; </TD>
			<TD> &nbsp; <A HREF="admin.php?ADD=10000" ALT="Remote Agents" STYLE="text-decoration:none;"><?php echo $remoteagents_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Remote Agents"); ?></B></FONT></A> &nbsp; </TD>
			<TD> &nbsp; <A HREF="admin.php?ADD=999998" ALT="Admin" STYLE="text-decoration:none;"><?php echo $admin_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Admin"); ?></B></FONT></A> &nbsp; </TD>
			<?php 
				}
			else 
				{ 
				?>
				<TD width=600> &nbsp; &nbsp; </TD>
				<?php
				if ($reports_only_user > 0)
					{
					?>
					<TD> &nbsp; <A HREF="admin.php?ADD=999999" ALT="Reports"><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Reports"); ?></B></A> &nbsp; </TD>
					<?php
					}
				else
					{
					if (($SSqc_features_active=='1') && ($qc_auth=='1')) 
						{
						?>
						<TD> &nbsp; <A HREF="admin.php?ADD=100000000000000" ALT="Quality Control"><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Quality Control"); ?></B></FONT></A> &nbsp; </TD>
						<?php
						}
					}
				}
			}
		?>
		</TR>
		</TABLE>
		<?php
		}
	}
######################### SMALL HTML HEADER END #######################################


######################### MOBILE HTML HEADER BEGIN ####################################
else if ($android_header)
	{
	?>
	<TABLE CELLPADDING=0 CELLSPACING=0 BGCOLOR=#<?php echo "$SSmenu_background" ?> width='100%'><TR>
	<TD><A HREF="./admin_mobile.php"><IMG SRC="<?php echo $selected_small_logo; ?>" WIDTH=71 HEIGHT=22 BORDER=0 ALT="System logo"></A> &nbsp; </TD>
	<TD align='left'> &nbsp; <A HREF="admin_mobile.php?ADD=999990" ALT="Admin" STYLE="text-decoration:none;"><?php echo $admin_icon ?> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo _QXZ("Admin"); ?></B></FONT></A> &nbsp; </TD>
	</TR>
	</TABLE>
<?php
	}
######################### ANDROID HTML HEADER END ######################################



######################### FULL HTML HEADER BEGIN #######################################
else
{
if ($no_title < 1) {echo "</title>\n";}
echo "<script language=\"Javascript\">\n";
echo "var field_name = '';\n";
echo "var user = '$PHP_AUTH_USER';\n";
echo "var epoch = '" . date("U") . "';\n";

if ($TCedit_javascript > 0)
	{
	 ?>

	function run_submit()
		{
		calculate_hours();
		var go_submit = document.getElementById("go_submit");
		if (go_submit.disabled == false)
			{
			document.edit_log.submit();
			}
		}

	// Calculate login time
	function calculate_hours() 
		{
		var now_epoch = '<?php echo $StarTtimE ?>';
		var local_gmt_sec = '<?php echo $local_gmt_sec ?>';
		var local_gmt_sec = (local_gmt_sec * 1);
		var i=0;
		var total_percent=0;
		var SPANlogin_time = document.getElementById("LOGINlogin_time");
		var LI_date = document.getElementById("LOGINbegin_date");
		var LO_date = document.getElementById("LOGOUTbegin_date");
		var LI_datetime = LI_date.value;
		var LO_datetime = LO_date.value;
		var LI_datetime_array=LI_datetime.split(" ");
		var LI_date_array=LI_datetime_array[0].split("-");
		var LI_time_array=LI_datetime_array[1].split(":");
		var LO_datetime_array=LO_datetime.split(" ");
		var LO_date_array=LO_datetime_array[0].split("-");
		var LO_time_array=LO_datetime_array[1].split(":");

		// Calculate milliseconds since 1970 for each date string and find diff
		var LI_date_epoch = Date.UTC(LI_date_array[0], (LI_date_array[1]-1), LI_date_array[2], LI_time_array[0], LI_time_array[1], LI_time_array[2]);
		var LO_date_epoch = Date.UTC(LO_date_array[0], (LO_date_array[1]-1), LO_date_array[2], LO_time_array[0], LO_time_array[1], LO_time_array[2]);
		var temp_LI_epoch = ( (LI_date_epoch / 1000 ) + local_gmt_sec);
		var temp_LO_epoch = ( (LO_date_epoch / 1000 ) + local_gmt_sec);
		var epoch_diff = (temp_LO_epoch - temp_LI_epoch);
		var temp_diff = epoch_diff;

		document.getElementById("login_time").innerHTML = "ERROR, Please check date fields";

	//	document.getElementById("login_time").innerHTML = LI_date_epoch + '|' + temp_LI_epoch + '|' + LO_date_epoch + '|' + temp_LO_epoch + '|' + (Date.UTC(LI_date_array[0], LI_date_array[1], LI_date_array[2]) / 1000) + '|' + (Date.UTC(LO_date_array[0], LO_date_array[1], LO_date_array[2]) / 1000) + "\n diff " +  epoch_diff + "\n LI " +  temp_LI_epoch + "\n LO " +  temp_LO_epoch + "\n Now " +  now_epoch + "\n local" + local_gmt_sec;

		var go_submit = document.getElementById("go_submit");
		go_submit.disabled = true;
		// length is a positive number and no more than 24 hours, datetime is earlier than right now
		if ( (epoch_diff < 86401) && (epoch_diff > 0) && (temp_LI_epoch < now_epoch) && (temp_LO_epoch < now_epoch) )
			{
			go_submit.disabled = false;

			hours = Math.floor(temp_diff / (60 * 60)); 
			temp_diff -= hours * (60 * 60);

			mins = Math.floor(temp_diff / 60); 
			temp_diff -= mins * 60;
			if (mins < 10) {mins = "0" + mins;}

			secs = Math.floor(temp_diff); 
			temp_diff -= secs;

			document.getElementById("login_time").innerHTML = hours + ":" + mins;

			var form_LI_epoch = document.getElementById("LOGINepoch");
			var form_LO_epoch = document.getElementById("LOGOUTepoch");
			form_LI_epoch.value = temp_LI_epoch;
			form_LO_epoch.value = temp_LO_epoch;
			}
		}



	<?php
	}
######################
# ADD=31 or 34 and SUB=29 for list mixes
######################
if ( ( ($ADD==34) or ($ADD==31) or ($ADD==49) ) and ($SUB==29) and ($LOGmodify_campaigns==1) and ( (preg_match("/$campaign_id/i", $LOGallowed_campaigns)) or (preg_match("/ALL\-CAMPAIGNS/i",$LOGallowed_campaigns)) ) ) 
	{

	?>
	// List Mix status add and remove
	function mod_mix_status(stage,vcl_id,entry) 
		{
		if (stage=="ALL")
			{
			var count=0;
			var ROnew_statuses = document.getElementById("ROstatus_X_" + vcl_id);

			while (count < entry)
				{
				var old_statuses = document.getElementById("status_" + count + "_" + vcl_id);
				var ROold_statuses = document.getElementById("ROstatus_" + count + "_" + vcl_id);

				old_statuses.value = ROnew_statuses.value;
				ROold_statuses.value = ROnew_statuses.value;
				count++;
				}
			}
		else
			{
			if (stage=="EMPTY")
				{
				var count=0;
				var ROnew_statuses = document.getElementById("ROstatus_X_" + vcl_id);

				while (count < entry)
					{
					var old_statuses = document.getElementById("status_" + count + "_" + vcl_id);
					var ROold_statuses = document.getElementById("ROstatus_" + count + "_" + vcl_id);
					
					if (ROold_statuses.value.length < 3)
						{
						old_statuses.value = ROnew_statuses.value;
						ROold_statuses.value = ROnew_statuses.value;
						}
					count++;
					}
				}

			else
				{
				var mod_status = document.getElementById("dial_status_" + entry + "_" + vcl_id);
				if (mod_status.value.length < 1)
					{
					alert("You must select a status first");
					}
				else
					{
					var old_statuses = document.getElementById("status_" + entry + "_" + vcl_id);
					var ROold_statuses = document.getElementById("ROstatus_" + entry + "_" + vcl_id);
					var MODstatus = new RegExp(" " + mod_status.value + " ","g");
					if (stage=="ADD")
						{
						if (old_statuses.value.match(MODstatus))
							{
							alert("The status " + mod_status.value + " is already present");
							}
						else
							{
							var new_statuses = " " + mod_status.value + "" + old_statuses.value;
							old_statuses.value = new_statuses;
							ROold_statuses.value = new_statuses;
							mod_status.value = "";
							}
						}
					if (stage=="REMOVE")
						{
						var MODstatus = new RegExp(" " + mod_status.value + " ","g");
						old_statuses.value = old_statuses.value.replace(MODstatus, " ");
						ROold_statuses.value = ROold_statuses.value.replace(MODstatus, " ");
						}
					}
				}
			}
		}

	// List Mix percent difference calculation and warning message
	function mod_mix_percent(vcl_id,entries) 
		{
		var i=0;
		var total_percent=0;
		var percent_diff='';
		while(i < entries)
			{
			var mod_percent_field = document.getElementById("percentage_" + i + "_" + vcl_id);
			temp_percent = mod_percent_field.value * 1;
			total_percent = (total_percent + temp_percent);
			i++;
			}

		var mod_diff_percent = document.getElementById("PCT_DIFF_" + vcl_id);
		percent_diff = (total_percent - 100);
		if (percent_diff > 0)
			{
			percent_diff = '+' + percent_diff;
			}
		var mix_list_submit = document.getElementById("submit_" + vcl_id);
		if ( (percent_diff > 0) || (percent_diff < 0) )
			{
			mix_list_submit.disabled = true;
			document.getElementById("ERROR_" + vcl_id).innerHTML = "<font color=red><B>The Difference % must be 0</B></font>";
			}
		else
			{
			mix_list_submit.disabled = false;
			document.getElementById("ERROR_" + vcl_id).innerHTML = "";
			}

		mod_diff_percent.value = percent_diff;
		}

	function submit_mix(vcl_id,entries) 
		{
		var h=1;
		var j=1;
		var list_mix_container='';
		var mod_list_mix_container_field = document.getElementById("list_mix_container_" + vcl_id);
		while(h < 41)
			{
			var i=0;
			while(i < entries)
				{
				var mod_list_id_field = document.getElementById("list_id_" + i + "_" + vcl_id);
				var mod_priority_field = document.getElementById("priority_" + i + "_" + vcl_id);
				var mod_percent_field = document.getElementById("percentage_" + i + "_" + vcl_id);
				var mod_statuses_field = document.getElementById("status_" + i + "_" + vcl_id);
				if (mod_priority_field.value==h)
					{
					list_mix_container = list_mix_container + mod_list_id_field.value + "|" + j + "|" + mod_percent_field.value + "|" + mod_statuses_field.value + "|:";
					j++;
					}
				i++;
				}
			h++;
			}
		mod_list_mix_container_field.value = list_mix_container;
		var form_to_submit = document.getElementById("" + vcl_id);
		form_to_submit.submit();
		}
	<?php
	}
	?>

	<?php
	if ( ( ($ADD==34) or ($ADD==31) or ($ADD==44) or ($ADD==41) ) and ($LOGmodify_campaigns==1) and ( (preg_match("/$campaign_id/i", $LOGallowed_campaigns)) or (preg_match("/ALL\-CAMPAIGNS/i",$LOGallowed_campaigns)) ) ) 
		{
	?>
	// List status change confirmation
	function ConfirmListStatusChange(system_setting, listForm) {
		if (!system_setting) {
			// if the list change confirmation system setting is off, just submit the form
			listForm.submit();
			return false;
		}

		var previous_list_statuses=document.getElementById('last_list_statuses').value;

		var new_list_statuses="";

		var lists = document.getElementsByName('list_active_change[]');
		var no_selected=0;
		for (var i=0; i<lists.length; i++) {
			new_list_statuses+=lists[i].value+"|";
			if (lists[i].checked) {
				new_list_statuses+="Y|";
			} else {
				new_list_statuses+="N|";
			}
		}

		if (previous_list_statuses==new_list_statuses) {
			// if none of the lists active status has been changed, just submit the form
			listForm.submit();
			return false;
		} else {
			var prev_array=previous_list_statuses.split("|");
			var new_array=new_list_statuses.split("|");
			if (prev_array.length!=new_array.length) {alert("List error. Reload the page and try again."); return false;}

			var altered_lists="";
			for(i=0; i<prev_array.length; i+=2) {
				prev_status=prev_array[(i+1)];
				new_status=new_array[(i+1)];
				if (prev_status!=new_status) {
					altered_lists+=" - List "+new_array[i]+": "+prev_status+" => "+new_status+"\n";
				}
			}

			var proceed=confirm("You have changed the active status of the following lists:\n\n"+altered_lists+"\nWould you like to proceed with committing the changes?");
			if (proceed) {
				listForm.submit();
			}
		}

	}
	<?php
		}
	?>

	var weak = new Image();
	weak.src = "images/weak.png";
	var medium = new Image();
	medium.src = "images/medium.png";
	var strong = new Image();
	strong.src = "images/strong.png";

	function pwdChanged(pwd_field_str, pwd_img_str, pwd_len_field, pwd_len_min) 
		{
		var pwd_field = document.getElementById(pwd_field_str);
		var pwd_field_value = pwd_field.value;
		var pwd_img = document.getElementById(pwd_img_str);
		var pwd_len = pwd_field_value.length

	//	var strong_regex = new RegExp( "^(?=.{8,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])", "g" );
	//	var medium_regex = new RegExp( "^(?=.{6,})(((?=.*[a-z])(?=.*[A-Z]))|((?=.*[a-z])(?=.*[0-9]))|((?=.*[A-Z])(?=.*[0-9]))).*$", "g" );
		var strong_regex = new RegExp( "^(?=.{20,})(?=.*[a-zA-Z])(?=.*[0-9])", "g" );
		var medium_regex = new RegExp( "^(?=.{10,})(?=.*[a-zA-Z])(?=.*[0-9])", "g" );

		if (strong_regex.test(pwd_field.value) ) 
			{
			if (pwd_img.src != strong.src)
				{pwd_img.src = strong.src;}
			} 
		else if (medium_regex.test( pwd_field.value) ) 
			{
			if (pwd_img.src != medium.src) 
				{pwd_img.src = medium.src;}
			}
		else 
			{
			if (pwd_img.src != weak.src) 
				{pwd_img.src = weak.src;}
			}
		if ( (pwd_len_min > 0) && (pwd_len_min > pwd_len) )
			{document.getElementById(pwd_len_field).innerHTML = "<font color=red><b>" + pwd_len + "</b></font>";}
		else
			{document.getElementById(pwd_len_field).innerHTML = "<font color=black><b>" + pwd_len + "</b></font>";}
		}

	function openNewWindow(url) 
		{
		window.open (url,"",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');
		}
	function scriptInsertField() 
		{
		openField = '--A--';
		closeField = '--B--';
		var textBox = document.scriptForm.script_text;
		var scriptIndex = document.getElementById("selectedField").selectedIndex;
		var insValue =  document.getElementById('selectedField').options[scriptIndex].value;
		if (document.selection) 
			{
			//IE
			textBox = document.scriptForm.script_text;
			insValue = document.scriptForm.selectedField.options[document.scriptForm.selectedField.selectedIndex].text;
			textBox.focus();
			sel = document.selection.createRange();
			sel.text = openField + insValue + closeField;
			} 
		else if (textBox.selectionStart || textBox.selectionStart == 0) 
			{
			//Mozilla
			var startPos = textBox.selectionStart;
			var endPos = textBox.selectionEnd;
			textBox.value = textBox.value.substring(0, startPos)
			+ openField + insValue + closeField
			+ textBox.value.substring(endPos, textBox.value.length);
			}
		else 
			{
			textBox.value += openField + insValue + closeField;
			}
		}

	<?php

#### Javascript for auto-generate of user ID Button
if ( ($SSadmin_modify_refresh > 1) and (preg_match("/^3|^4/",$ADD)) )
	{
	?>
	var ar_seconds=<?php echo "$SSadmin_modify_refresh;"; ?>

	function modify_refresh_display()
		{
		if (ar_seconds > 0)
			{
			ar_seconds = (ar_seconds - 1);
			document.getElementById("refresh_countdown").innerHTML = "<font color=black> screen refresh in: " + ar_seconds + " seconds</font>";
			setTimeout("modify_refresh_display()",1000);
			}
		}

	<?php
	}

#### BEGIN Javascript for auto-generate of user ID Button
if ( ($ADD==1) or ($ADD=="1A") )
	{
	?>

	function user_auto()
		{
		var user_toggle = document.getElementById("user_toggle");
		var user_field = document.getElementById("user");
		if (user_toggle.value < 1)
			{
			user_field.value = 'AUTOGENERATEZZZ';
			user_field.disabled = true;
			user_toggle.value = 1;
			}
		else
			{
			user_field.value = '';
			user_field.disabled = false;
			user_toggle.value = 0;
			}
		}

	function user_submit()
		{
		var user_field = document.getElementById("user");
		user_field.disabled = false;
		document.userform.submit();
		}

	<?php
	}
#### END Javascript for auto-generate of user ID Button

else
	{
	echo "	var pass = '$PHP_AUTH_PW';\n";
	?>

	mouseY=0;
	function getMousePos(event) {
		mouseY=event.pageY;
	}
	document.addEventListener("click", getMousePos);

	function launch_chooser(fieldname,stage)
		{

		var h = window.innerHeight;		
		var vposition=mouseY;

		var audiolistURL = "./non_agent_api.php";
		var audiolistQuery = "source=admin&function=sounds_list&user=" + user + "&pass=" + pass + "&format=selectframe&stage=" + stage + "&comments=" + fieldname;
		var Iframe_content = '<IFRAME SRC="' + audiolistURL + '?' + audiolistQuery + '"  style="width:740;height:440;background-color:white;" scrolling="NO" frameborder="0" allowtransparency="true" id="audio_chooser_frame' + epoch + '" name="audio_chooser_frame" width="740" height="460" STYLE="z-index:2"> </IFRAME>';

		document.getElementById("audio_chooser_span").style.position = "absolute";
		document.getElementById("audio_chooser_span").style.left = "220px";
		document.getElementById("audio_chooser_span").style.top = vposition + "px";
		document.getElementById("audio_chooser_span").style.visibility = 'visible';
		document.getElementById("audio_chooser_span").innerHTML = Iframe_content;
		}

	function launch_moh_chooser(fieldname,stage)
		{

		var h = window.innerHeight;		
		var vposition=mouseY;

		var audiolistURL = "./non_agent_api.php";
		var audiolistQuery = "source=admin&function=moh_list&user=" + user + "&pass=" + pass + "&format=selectframe&stage=" + stage + "&comments=" + fieldname;
		var Iframe_content = '<IFRAME SRC="' + audiolistURL + '?' + audiolistQuery + '"  style="width:740;height:440;background-color:white;" scrolling="NO" frameborder="0" allowtransparency="true" id="audio_chooser_frame' + epoch + '" name="audio_chooser_frame" width="740" height="460" STYLE="z-index:2"> </IFRAME>';

		document.getElementById("audio_chooser_span").style.position = "absolute";
		document.getElementById("audio_chooser_span").style.left = "220px";
		document.getElementById("audio_chooser_span").style.top = vposition + "px";
		document.getElementById("audio_chooser_span").style.visibility = 'visible';
		document.getElementById("audio_chooser_span").innerHTML = Iframe_content;
		}

	function launch_vm_chooser(fieldname,stage)
		{

		var h = window.innerHeight;		
		var vposition=mouseY;

		var audiolistURL = "./non_agent_api.php";
		var audiolistQuery = "source=admin&function=vm_list&user=" + user + "&pass=" + pass + "&format=selectframe&stage=" + stage + "&comments=" + fieldname;
		var Iframe_content = '<IFRAME SRC="' + audiolistURL + '?' + audiolistQuery + '"  style="width:740;height:440;background-color:white;" scrolling="NO" frameborder="0" allowtransparency="true" id="audio_chooser_frame' + epoch + '" name="audio_chooser_frame" width="740" height="460" STYLE="z-index:2"> </IFRAME>';

		document.getElementById("audio_chooser_span").style.position = "absolute";
		document.getElementById("audio_chooser_span").style.left = "220px";
		document.getElementById("audio_chooser_span").style.top = vposition + "px";
		document.getElementById("audio_chooser_span").style.visibility = 'visible';
		document.getElementById("audio_chooser_span").innerHTML = Iframe_content;
		}

	function close_chooser()
		{
		document.getElementById("audio_chooser_span").style.visibility = 'hidden';
		document.getElementById("audio_chooser_span").innerHTML = '';
		}

	function user_submit()
		{
		var user_field = document.getElementById("user");
		user_field.disabled = false;
		document.userform.submit();
		}

	function play_browser_sound(temp_element,temp_volume)
		{
		var taskIndex = document.getElementById(temp_element).selectedIndex;
		var taskValue = document.getElementById(temp_element).options[taskIndex].value;
		var temp_selected_element = 'BAS_' + taskValue;
		if ( (taskValue != '---NONE---') && (taskValue != '---DISABLED---') && (taskValue != '') )
			{
			var temp_audio = document.getElementById(temp_selected_element);
			var taskVolIndex = document.getElementById(temp_volume).selectedIndex;
			var taskVolValue = document.getElementById(temp_volume).options[taskVolIndex].value;
			var temp_js_volume = (taskVolValue * .01);
			temp_audio.volume = temp_js_volume;
		//	alert(temp_selected_element + ' ' + temp_js_volume);
			temp_audio.play();
			}
		}
	<?php
	}

### Javascript for shift end-time calculation and display
if ( ($ADD==131111111) or ($ADD==331111111) or ($ADD==431111111) )
	{
	?>
	function shift_time()
		{
		var start_time = document.getElementById("shift_start_time");
		var end_time = document.getElementById("shift_end_time");
		var length = document.getElementById("shift_length");

		var st_value = start_time.value;
		var et_value = end_time.value;
		while (st_value.length < 4) {st_value = "0" + st_value;}
		while (et_value.length < 4) {et_value = "0" + et_value;}
		var st_hour=st_value.substring(0,2);
		var st_min=st_value.substring(2,4);
		var et_hour=et_value.substring(0,2);
		var et_min=et_value.substring(2,4);
		if (st_hour > 23) {st_hour = 23;}
		if (et_hour > 23) {et_hour = 23;}
		if (st_min > 59) {st_min = 59;}
		if (et_min > 59) {et_min = 59;}
		start_time.value = st_hour + "" + st_min;
		end_time.value = et_hour + "" + et_min;

		var start_time_hour=start_time.value.substring(0,2);
		var start_time_min=start_time.value.substring(2,4);
		var end_time_hour=end_time.value.substring(0,2);
		var end_time_min=end_time.value.substring(2,4);
		start_time_hour=(start_time_hour * 1);
		start_time_min=(start_time_min * 1);
		end_time_hour=(end_time_hour * 1);
		end_time_min=(end_time_min * 1);

		if (start_time.value == end_time.value)
			{
			var shift_length = '24:00';
			}
		else
			{
			if ( (start_time_hour > end_time_hour) || ( (start_time_hour == end_time_hour) && (start_time_min > end_time_min) ) )
				{
				var shift_hour = ( (24 - start_time_hour) + end_time_hour);
				var shift_minute = ( (60 - start_time_min) + end_time_min);
				if (shift_minute >= 60) 
					{
					shift_minute = (shift_minute - 60);
					}
				else
					{
					shift_hour = (shift_hour - 1);
					}
				}
			else
				{
				var shift_hour = (end_time_hour - start_time_hour);
				var shift_minute = (end_time_min - start_time_min);
				}
			if (shift_minute < 0) 
				{
				shift_minute = (shift_minute + 60);
				shift_hour = (shift_hour - 1);
				}

			if (shift_hour < 10) {shift_hour = '0' + shift_hour}
			if (shift_minute < 10) {shift_minute = '0' + shift_minute}
			var shift_length = shift_hour + ':' + shift_minute;
			}
	//	alert(start_time_hour + '|' + start_time_min + '|' + end_time_hour + '|' + end_time_min + '|--|' + shift_hour + ':' + shift_minute + '|' + shift_length + '|');

		length.value = shift_length;
		}

<?php
	}




### Javascript for selecting and deselecting all AC-CIDs and other checkboxes "active" on the modify page
if ( ($ADD==3111) or ($ADD==4111) or ($ADD==5111) or ($ADD==3811) or ($ADD==4811) or ($ADD==5811) or ($ADD==3911) or ($ADD==4911) or ($ADD==5911) or ($ADD==31) or ($ADD==34) or ($ADD==202) or ($ADD==396111111111) or ($ADD==496111111111) )
	{
?>
	function FORM_selectall(temp_count,temp_fields,temp_option,temp_span)
		{
		var fields_array=temp_fields.split('|');
		var inc=0;
		while (temp_count >= inc)
			{
			if (fields_array[inc].length > 0)
				{
				if (temp_option == 'off')
					{document.getElementById(fields_array[inc]).checked=false;}
				else
					{document.getElementById(fields_array[inc]).checked=true;}
				}
			inc++;
			}
		if (temp_option == 'off')
			{
			document.getElementById(temp_span).innerHTML = "<a href=\"#\" onclick=\"FORM_selectall('" + temp_count + "','" + temp_fields + "','on','" + temp_span + "');return false;\"><font size=1><?php echo _QXZ("select all"); ?></font></a>";
			}
		else
			{
			document.getElementById(temp_span).innerHTML = "<a href=\"#\" onclick=\"FORM_selectall('" + temp_count + "','" + temp_fields + "','off','" + temp_span + "');return false;\"><font size=1><?php echo _QXZ("deselect all"); ?></font></a>";
			}
		}

	<?php
	}


$stmt="SELECT menu_id,menu_name from vicidial_call_menu $whereLOGadmin_viewable_groupsSQL order by menu_id limit 10000;";
$rslt=mysql_to_mysqli($stmt, $link);
$menus_to_print = mysqli_num_rows($rslt);
$call_menu_list='';
$i=0;
while ($i < $menus_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$call_menu_list .= "<option value=\"$row[0]\">$row[0] - $row[1]</option>";
	$i++;
	}

### select list contents generation for dynamic route displays in call menu and in-group screens
if ( ($ADD==3511) or ($ADD==2511) or ($ADD==2611) or ($ADD==4511) or ($ADD==5511) or ($ADD==3111) or ($ADD==2111) or ($ADD==2011) or ($ADD==4111) or ($ADD==5111) )
	{
	$stmt="SELECT did_pattern,did_description,did_route from vicidial_inbound_dids where did_active='Y' $LOGadmin_viewable_groupsSQL order by did_pattern;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$dids_to_print = mysqli_num_rows($rslt);
	$did_list='';
	$i=0;
	while ($i < $dids_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$did_list .= "<option value=\"$row[0]\">$row[0] - $row[1] - $row[2]</option>";
		$i++;
		}

	$stmt="SELECT group_id,group_name from vicidial_inbound_groups where active='Y' and group_id NOT LIKE \"AGENTDIRECT%\" $LOGadmin_viewable_groupsSQL order by group_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$ingroups_to_print = mysqli_num_rows($rslt);
	$ingroup_list='';
	$i=0;
	while ($i < $ingroups_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$ingroup_list .= "<option value=\"$row[0]\">$row[0] - $row[1]</option>";
		$i++;
		}

	$stmt="SELECT campaign_id,campaign_name from vicidial_campaigns where active='Y' $LOGallowed_campaignsSQL order by campaign_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$IGcampaigns_to_print = mysqli_num_rows($rslt);
	$IGcampaign_id_list='';
	$i=0;
	while ($i < $IGcampaigns_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$IGcampaign_id_list .= "<option value=\"$row[0]\">$row[0] - $row[1]</option>";
		$i++;
		}

	$IGhandle_method_list = '<option>CID</option><option>CIDLOOKUP</option><option>CIDLOOKUPRL</option><option>CIDLOOKUPRC</option><option>CIDLOOKUPALT</option><option>CIDLOOKUPRLALT</option><option>CIDLOOKUPRCALT</option><option>CIDLOOKUPADDR3</option><option>CIDLOOKUPRLADDR3</option><option>CIDLOOKUPRCADDR3</option><option>CIDLOOKUPALTADDR3</option><option>CIDLOOKUPRLALTADDR3</option><option>CIDLOOKUPRCALTADDR3</option><option>ANI</option><option>ANILOOKUP</option><option>ANILOOKUPRL</option><option>VIDPROMPT</option><option>VIDPROMPTLOOKUP</option><option>VIDPROMPTLOOKUPRL</option><option>VIDPROMPTLOOKUPRC</option><option>CLOSER</option><option>3DIGITID</option><option>4DIGITID</option><option>5DIGITID</option><option>10DIGITID</option><option>CIDLOOKUPOWNERCUSTOMX</option><option>CIDLOOKUPRLOWNERCUSTOMX</option><option>CIDLOOKUPRCOWNERCUSTOMX</option><option>CIDLOOKUPALTOWNERCUSTOMX</option><option>CIDLOOKUPRLALTOWNERCUSTOMX</option><option>CIDLOOKUPRCALTOWNERCUSTOMX</option><option>CIDLOOKUPADDR3OWNERCUSTOMX</option><option>CIDLOOKUPRLADDR3OWNERCUSTOMX</option><option>CIDLOOKUPRCADDR3OWNERCUSTOMX</option><option>CIDLOOKUPALTADDR3OWNERCUSTOMX</option><option>CIDLOOKUPRLALTADDR3OWNERCUSTOMX</option><option>CIDLOOKUPRCALTADDR3OWNERCUSTOMX</option>';

	$IGsearch_method_list = '<option value=\\"LB\\">LB - '._QXZ("Load Balanced").'</option><option value=\\"LO\\">LO - '._QXZ("Load Balanced Overflow").'</option><option value=\\"SO\\">SO - '._QXZ("Server Only").'</option>';

	$stmt="SELECT login,server_ip,extension,dialplan_number from phones where active='Y' $LOGadmin_viewable_groupsSQL order by login,server_ip;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$phones_to_print = mysqli_num_rows($rslt);
	$phone_list='';
	$i=0;
	while ($i < $phones_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$phone_list .= "<option value=\"$row[0]\">$row[0] - $row[1] - $row[2] - $row[3]</option>";
		$i++;
		}
	}

# dynamic options for options in call_menu screen
if ( ($ADD==3511) or ($ADD==2511) or ($ADD==2611) or ($ADD==4511) or ($ADD==5511) )
	{

	?>
	function call_menu_option(option,route,value,value_context,chooser_height)
		{
		var call_menu_list = '<?php echo $call_menu_list ?>';
		var ingroup_list = '<?php echo $ingroup_list ?>';
		var IGcampaign_id_list = '<?php echo $IGcampaign_id_list ?>';
		var IGhandle_method_list = '<?php echo $IGhandle_method_list ?>';
		var IGsearch_method_list = "<?php echo $IGsearch_method_list ?>";
		var did_list = '<?php echo $did_list ?>';
		var phone_list = '<?php echo $phone_list ?>';
		var selected_value = '';
		var selected_context = '';
		var new_content = '';

		var select_list = document.getElementById("option_route_" + option);
		var selected_route = select_list.value;
		var span_to_update = document.getElementById("option_value_value_context_" + option);

		if (selected_route=='CALLMENU')
			{
			if (route == selected_route)
				{
				selected_value = '<option SELECTED value="' + value + '">' + value + "</option>\n";
				}
			else
				{value='';}
			new_content = '<span name=option_route_link_' + option + ' id=option_route_link_' + option + "><a href=\"./admin.php?ADD=3511&menu_id=" + value + "\"><?php echo _QXZ("Call Menu"); ?>: </a></span><select size=1 name=option_route_value_" + option + " id=option_route_value_" + option + " onChange=\"call_menu_link('" + option + "','CALLMENU');\">" + call_menu_list + "\n" + selected_value + '</select>';
			}
		if (selected_route=='INGROUP')
			{
			if (value_context.length < 10)
				{value_context = 'CID,LB,998,TESTCAMP,1,,,,';}
			var value_context_split =		value_context.split(",");
			var IGhandle_method =			value_context_split[0];
			var IGsearch_method =			value_context_split[1];
			var IGlist_id =					value_context_split[2];
			var IGcampaign_id =				value_context_split[3];
			var IGphone_code =				value_context_split[4];
			var IGvid_enter_filename =		value_context_split[5];
			var IGvid_id_number_filename =	value_context_split[6];
			var IGvid_confirm_filename =	value_context_split[7];
			var IGvid_validate_digits =		value_context_split[8];

			if (route == selected_route)
				{
				selected_value = '<option SELECTED>' + value + '</option>';
				}

			new_content = '<input type=hidden name=option_route_value_context_' + option + ' id=option_route_value_context_' + option + ' value="' + selected_value + '">';
			new_content = new_content + '<span name="option_route_link_' + option + '" id="option_route_link_' + option + '">';
			new_content = new_content + "<a href=\"admin.php?ADD=3111&group_id=" + value + "\"><?php echo _QXZ("In-Group"); ?>:</a> </span>";
			new_content = new_content + '<select size=1 name=option_route_value_' + option + ' id=option_route_value_' + option + " onChange=\"call_menu_link('" + option + "','INGROUP');\">";
			new_content = new_content + '' + ingroup_list + "\n" + selected_value + '<option>DYNAMIC_INGROUP_VAR</option></select>';
			new_content = new_content + " &nbsp; <?php echo _QXZ("Handle Method"); ?>: <select size=1 name=IGhandle_method_" + option + ' id=IGhandle_method_' + option + '>';
			new_content = new_content + '' + '<option SELECTED>' + IGhandle_method + '</option>' + IGhandle_method_list + '</select>' + "\n";
			new_content = new_content + ' &nbsp; <IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, \'call_menu-ingroup_settings\')" WIDTH=20 HEIGHT=20 BORDER=0 ALT="HELP" ALIGN=TOP>';
			new_content = new_content + "<BR><?php echo _QXZ("Search Method"); ?>: <select size=1 name=IGsearch_method_" + option + ' id=IGsearch_method_' + option + '>';
			new_content = new_content + '' + IGsearch_method_list + "\n" + '<option SELECTED>' + IGsearch_method + '</select>';
			new_content = new_content + " &nbsp; <?php echo _QXZ("List ID"); ?>: <input type=text size=5 maxlength=14 name=IGlist_id_" + option + ' id=IGlist_id_' + option + ' value="' + IGlist_id + '">';
			new_content = new_content + "<BR><?php echo _QXZ("Campaign ID"); ?>: <select size=1 name=IGcampaign_id_" + option + ' id=IGcampaign_id_' + option + '>';
			new_content = new_content + '' + IGcampaign_id_list + "\n" + '<option SELECTED>' + IGcampaign_id + '</select>';
			new_content = new_content + " &nbsp; <?php echo _QXZ("Phone Code"); ?>: <input type=text size=5 maxlength=14 name=IGphone_code_" + option + ' id=IGphone_code_' + option + ' value="' + IGphone_code + '">';
			new_content = new_content + "<BR> &nbsp; <?php echo _QXZ("VID Enter Filename"); ?>: <input type=text name=IGvid_enter_filename_" + option + " id=IGvid_enter_filename_" + option + " size=40 maxlength=255 value=\"" + IGvid_enter_filename + "\"> <a href=\"javascript:launch_chooser('IGvid_enter_filename_" + option + "','date');\"><?php echo _QXZ("audio chooser"); ?></a>";
			new_content = new_content + "<BR> &nbsp; <?php echo _QXZ("VID ID Number Filename"); ?>: <input type=text name=IGvid_id_number_filename_" + option + " id=IGvid_id_number_filename_" + option + " size=40 maxlength=255 value=\"" + IGvid_id_number_filename + "\"> <a href=\"javascript:launch_chooser('IGvid_id_number_filename_" + option + "','date');\"><?php echo _QXZ("audio chooser"); ?></a>";
			new_content = new_content + "<BR> &nbsp; <?php echo _QXZ("VID Confirm Filename"); ?>: <input type=text name=IGvid_confirm_filename_" + option + " id=IGvid_confirm_filename_" + option + " size=40 maxlength=255 value=\"" + IGvid_confirm_filename + "\"> <a href=\"javascript:launch_chooser('IGvid_confirm_filename_" + option + "','date');\"><?php echo _QXZ("audio chooser"); ?></a>";
			new_content = new_content + " &nbsp; <?php echo _QXZ("VID Digits"); ?>: <input type=text size=3 maxlength=3 name=IGvid_validate_digits_" + option + ' id=IGvid_validate_digits_' + option + ' value="' + IGvid_validate_digits + '">';
			}
		if (selected_route=='DID')
			{
			if (route == selected_route)
				{
				selected_value = '<option SELECTED value="' + value + '">' + value + "</option>\n";
				}
			else
				{value='';}
			new_content = '<span name=option_route_link_' + option + ' id=option_route_link_' + option + '><a href="admin.php?ADD=3311&did_pattern=' + value + "\"><?php echo _QXZ("DID"); ?>:</a> </span><select size=1 name=option_route_value_" + option + ' id=option_route_value_' + option + " onChange=\"call_menu_link('" + option + "','DID');\">" + did_list + "\n" + selected_value + '</select>';
			}
		if (selected_route=='HANGUP')
			{
			if (route == selected_route)
				{
				selected_value = value;
				}
			else
				{value='vm-goodbye';}
			new_content = "<?php echo _QXZ("Audio File"); ?>: <input type=text name=option_route_value_" + option + " id=option_route_value_" + option + " size=40 maxlength=255 value=\"" + selected_value + "\"> <a href=\"javascript:launch_chooser('option_route_value_" + option + "','date');\"><?php echo _QXZ("audio chooser"); ?></a>";
			}
		if (selected_route=='EXTENSION')
			{
			if (route == selected_route)
				{
				selected_value = value;
				selected_context = value_context;
				}
			else
				{value='8304';}
			new_content = "<?php echo _QXZ("Extension"); ?>: <input type=text name=option_route_value_" + option + " id=option_route_value_" + option + " size=20 maxlength=255 value=\"" + selected_value + "\"> &nbsp; <?php echo _QXZ("Context"); ?>: <input type=text name=option_route_value_context_" + option + " id=option_route_value_context_" + option + " size=20 maxlength=255 value=\"" + selected_context + "\"> ";
			}
		if (selected_route=='PHONE')
			{
			if (route == selected_route)
				{
				selected_value = '<option SELECTED value="' + value + '">' + value + "</option>\n";
				}
			else
				{value='';}
			new_content = "<?php echo _QXZ("Phone"); ?>: <select size=1 name=option_route_value_" + option + ' id=option_route_value_' + option + '>' + phone_list + "\n" + selected_value + '</select>';
			}
		if ( (selected_route=='VOICEMAIL') || (selected_route=='VMAIL_NO_INST') )
			{
			if (route == selected_route)
				{
				selected_value = value;
				}
			else
				{value='';}
			new_content = "<?php echo _QXZ("Voicemail Box"); ?>: <input type=text name=option_route_value_" + option + " id=option_route_value_" + option + " size=12 maxlength=10 value=\"" + selected_value + "\"> <a href=\"javascript:launch_vm_chooser('option_route_value_" + option + "','date');\"><?php echo _QXZ("voicemail chooser"); ?></a>";
			}
		if (selected_route=='AGI')
			{
			if (route == selected_route)
				{
				selected_value = value;
				}
			else
				{value='';}
			new_content = "<?php echo _QXZ("AGI"); ?>: <input type=text name=option_route_value_" + option + " id=option_route_value_" + option + " size=80 maxlength=255 value=\"" + selected_value + "\"> ";
			}

		if (new_content.length < 1)
			{new_content = selected_route}

		span_to_update.innerHTML = new_content;
		}

	function call_menu_link(option,route)
		{
		var selected_value = '';
		var new_content = '';

		var select_list = document.getElementById("option_route_value_" + option);
		var selected_value = select_list.value;
		var span_to_update = document.getElementById("option_route_link_" + option);

		if (route=='CALLMENU')
			{
			new_content = "<a href=\"admin.php?ADD=3511&menu_id=" + selected_value + "\"><?php echo _QXZ("Call Menu"); ?>:</a>";
			}
		if (route=='INGROUP')
			{
			new_content = "<a href=\"admin.php?ADD=3111&group_id=" + selected_value + "\"><?php echo _QXZ("In-Group"); ?>:</a>";
			}
		if (route=='DID')
			{
			new_content = "<a href=\"admin.php?ADD=3311&did_pattern=" + selected_value + "\"><?php echo _QXZ("DID"); ?>:</a>";
			}

		if (new_content.length < 1)
			{new_content = selected_value}

		span_to_update.innerHTML = new_content;
		}

	function copy_prev_cm_option(item_number,item_height)
		{
		var prev_item_number = (item_number - 1)

		var temp_option_value = 'option_value_' + item_number;
		var temp_option_description = 'option_description_' + item_number;
		var temp_option_route = 'option_route_' + item_number;
		var temp_option_route_value = 'option_route_value_' + item_number;
		var temp_option_route_value_context = 'option_route_value_context_' + item_number;

		var temp_prev_option_value = 'option_value_' + prev_item_number;
		var temp_prev_option_description = 'option_description_' + prev_item_number;
		var temp_prev_option_route = 'option_route_' + prev_item_number;
		var temp_prev_option_route_value = 'option_route_value_' + prev_item_number;
		var temp_prev_option_route_value_context = 'option_route_value_context_' + prev_item_number;

		var temp_ValIndex = document.getElementById(temp_prev_option_value).selectedIndex;
		var temp_ValLength = document.getElementById(temp_prev_option_value).length;
		var temp_ValValue =  document.getElementById(temp_prev_option_value).options[temp_ValIndex].value;
		var temp_ValNewLength = document.getElementById(temp_option_value).length;
		var NEWtemp_ValIndex = (temp_ValIndex + 1);
		if (temp_ValNewLength >= 21) 
			{
			if (temp_ValNewLength > temp_ValLength) 
				{
				NEWtemp_ValIndex = (NEWtemp_ValIndex + 1);
				}
			if (NEWtemp_ValIndex > 21) {NEWtemp_ValIndex=1;}
			}
		else
			{
			if (NEWtemp_ValIndex > 20) {NEWtemp_ValIndex=0;}
			}
		document.getElementById(temp_option_value).selectedIndex = NEWtemp_ValIndex;

		var temp_optionIndex = document.getElementById(temp_prev_option_route).selectedIndex;
		var temp_optionValue =  document.getElementById(temp_prev_option_route).options[temp_optionIndex].value;

		var rIndex = 0;
		if (temp_optionValue == 'CALLMENU') {rIndex = 0;}
		if (temp_optionValue == 'INGROUP') {rIndex = 1;}
		if (temp_optionValue == 'DID') {rIndex = 2;}
		if (temp_optionValue == 'HANGUP') {rIndex = 3;}
		if (temp_optionValue == 'EXTENSION') {rIndex = 4;}
		if (temp_optionValue == 'PHONE') {rIndex = 5;}
		if (temp_optionValue == 'VOICEMAIL') {rIndex = 6;}
		if (temp_optionValue == 'VMAIL_NO_INST') {rIndex = 7;}
		document.getElementById(temp_option_route).selectedIndex = rIndex;
		document.getElementById(temp_option_description).value = document.getElementById(temp_prev_option_description).value;

		call_menu_option(item_number,temp_optionValue,'','',item_height);

		if ( (temp_optionValue == 'CALLMENU') || (temp_optionValue == 'DID') || (temp_optionValue == 'PHONE') )
			{
			var temp_prev_optionVal = document.getElementById(temp_prev_option_route_value);
			var temp_prev_optionValIndex = temp_prev_optionVal.selectedIndex;
			var temp_prev_optionValValue = temp_prev_optionVal.options[temp_prev_optionValIndex].value;

			var temp_optionVal = document.getElementById(temp_option_route_value);
			var temp_optionValIndex = 0;
			var temp_optionValLength = temp_optionVal.length;
			var temp_counter=0; 
			while (temp_counter < temp_optionValLength)
				{
				if (temp_prev_optionValValue == temp_optionVal.options[temp_counter].value)
					{temp_optionValIndex = temp_counter;}

				temp_counter++;
				}
			document.getElementById(temp_option_route_value).selectedIndex = temp_optionValIndex;

			if ( (temp_optionValue == 'CALLMENU') || (temp_optionValue == 'DID') )
				{
				call_menu_link(item_number,temp_optionValue);
				}
			}

		if ( (temp_optionValue == 'HANGUP') || (temp_optionValue == 'VOICEMAIL') || (temp_optionValue == 'VMAIL_NO_INST') )
			{
			var temp_prev_optionVal = document.getElementById(temp_prev_option_route_value);
			var temp_optionVal = document.getElementById(temp_option_route_value);

			temp_optionVal.value = temp_prev_optionVal.value;
			}

		if (temp_optionValue == 'EXTENSION') 
			{
			var temp_prev_optionVal = document.getElementById(temp_prev_option_route_value);
			var temp_optionVal = document.getElementById(temp_option_route_value);
			var temp_prev_optionValContext = document.getElementById(temp_prev_option_route_value_context);
			var temp_optionValContext = document.getElementById(temp_option_route_value_context);

			temp_optionVal.value = temp_prev_optionVal.value;
			temp_optionValContext.value = temp_prev_optionValContext.value;
			}

		if (temp_optionValue == 'INGROUP') 
			{
			// In-Group select list
			var temp_prev_optionVal = document.getElementById(temp_prev_option_route_value);
			var temp_prev_optionValIndex = temp_prev_optionVal.selectedIndex;
			var temp_prev_optionValValue = temp_prev_optionVal.options[temp_prev_optionValIndex].value;

			var temp_optionVal = document.getElementById(temp_option_route_value);
			var temp_optionValIndex = 0;
			var temp_optionValLength = temp_optionVal.length;
			var temp_counter=0; 
			while (temp_counter < temp_optionValLength)
				{
				if (temp_prev_optionValValue == temp_optionVal.options[temp_counter].value)
					{temp_optionValIndex = temp_counter;}

				temp_counter++;
				}
			document.getElementById(temp_option_route_value).selectedIndex = temp_optionValIndex;
			call_menu_link(item_number,temp_optionValue);

			// In-Group Handle Method
			var temp2_option_route_value = 'IGhandle_method_' + item_number;
			var temp2_prev_option_route_value = 'IGhandle_method_' + prev_item_number;

			var temp2_prev_optionVal = document.getElementById(temp2_prev_option_route_value);
			var temp2_prev_optionValIndex = temp2_prev_optionVal.selectedIndex;
			var temp2_prev_optionValValue = temp2_prev_optionVal.options[temp2_prev_optionValIndex].value;

			var temp2_optionVal = document.getElementById(temp2_option_route_value);
			var temp2_optionValIndex = 0;
			var temp2_optionValLength = temp2_optionVal.length;
			var temp2_counter=0; 
			while (temp2_counter < temp2_optionValLength)
				{
				if (temp2_prev_optionValValue == temp2_optionVal.options[temp2_counter].value)
					{temp2_optionValIndex = temp2_counter;}

				temp2_counter++;
				}
			document.getElementById(temp2_option_route_value).selectedIndex = temp2_optionValIndex;

			// In-Group Search Method
			var temp3_option_route_value = 'IGsearch_method_' + item_number;
			var temp3_prev_option_route_value = 'IGsearch_method_' + prev_item_number;

			var temp3_prev_optionVal = document.getElementById(temp3_prev_option_route_value);
			var temp3_prev_optionValIndex = temp3_prev_optionVal.selectedIndex;
			var temp3_prev_optionValValue = temp3_prev_optionVal.options[temp3_prev_optionValIndex].value;

			var temp3_optionVal = document.getElementById(temp3_option_route_value);
			var temp3_optionValIndex = 0;
			var temp3_optionValLength = temp3_optionVal.length;
			var temp3_counter=0; 
			while (temp3_counter < temp3_optionValLength)
				{
				if (temp3_prev_optionValValue == temp3_optionVal.options[temp3_counter].value)
					{temp3_optionValIndex = temp3_counter;}

				temp3_counter++;
				}
			document.getElementById(temp3_option_route_value).selectedIndex = temp3_optionValIndex;

			// In-Group Campaign
			var temp4_option_route_value = 'IGcampaign_id_' + item_number;
			var temp4_prev_option_route_value = 'IGcampaign_id_' + prev_item_number;

			var temp4_prev_optionVal = document.getElementById(temp4_prev_option_route_value);
			var temp4_prev_optionValIndex = temp4_prev_optionVal.selectedIndex;
			var temp4_prev_optionValValue = temp4_prev_optionVal.options[temp4_prev_optionValIndex].value;

			var temp4_optionVal = document.getElementById(temp4_option_route_value);
			var temp4_optionValIndex = 0;
			var temp4_optionValLength = temp4_optionVal.length;
			var temp4_counter=0; 
			while (temp4_counter < temp4_optionValLength)
				{
				if (temp4_prev_optionValValue == temp4_optionVal.options[temp4_counter].value)
					{temp4_optionValIndex = temp4_counter;}

				temp4_counter++;
				}
			document.getElementById(temp4_option_route_value).selectedIndex = temp4_optionValIndex;

			// In-Group List ID
			var temp5 = 'IGlist_id_' + item_number;
			var temp5_prev = 'IGlist_id_' + prev_item_number;
			var temp5_optionVal = document.getElementById(temp5);
			var temp5_prev_optionVal = document.getElementById(temp5_prev);
			temp5_optionVal.value = temp5_prev_optionVal.value;

			// In-Group Phone Code
			var temp6 = 'IGphone_code_' + item_number;
			var temp6_prev = 'IGphone_code_' + prev_item_number;
			var temp6_optionVal = document.getElementById(temp6);
			var temp6_prev_optionVal = document.getElementById(temp6_prev);
			temp6_optionVal.value = temp6_prev_optionVal.value;

			// In-Group VID Enter Filename
			var temp7 = 'IGvid_enter_filename_' + item_number;
			var temp7_prev = 'IGvid_enter_filename_' + prev_item_number;
			var temp7_optionVal = document.getElementById(temp7);
			var temp7_prev_optionVal = document.getElementById(temp7_prev);
			temp7_optionVal.value = temp7_prev_optionVal.value;

			// In-Group VID ID Number Filename
			var temp8 = 'IGvid_id_number_filename_' + item_number;
			var temp8_prev = 'IGvid_id_number_filename_' + prev_item_number;
			var temp8_optionVal = document.getElementById(temp8);
			var temp8_prev_optionVal = document.getElementById(temp8_prev);
			temp8_optionVal.value = temp8_prev_optionVal.value;

			// In-Group VID Confirm Filename
			var temp9 = 'IGvid_confirm_filename_' + item_number;
			var temp9_prev = 'IGvid_confirm_filename_' + prev_item_number;
			var temp9_optionVal = document.getElementById(temp9);
			var temp9_prev_optionVal = document.getElementById(temp9_prev);
			temp9_optionVal.value = temp9_prev_optionVal.value;

			// In-Group VID Digits
			var temp10 = 'IGvid_validate_digits_' + item_number;
			var temp10_prev = 'IGvid_validate_digits_' + prev_item_number;
			var temp10_optionVal = document.getElementById(temp10);
			var temp10_prev_optionVal = document.getElementById(temp10_prev);
			temp10_optionVal.value = temp10_prev_optionVal.value;
			}
		}
	<?php
	}


### Javascript for dynamic in-group option value entries
if ( ($ADD==2811) or ($ADD==3811) or ($ADD==3111) or ($ADD==2111) or ($ADD==2011) or ($ADD==4111) or ($ADD==5111) )
	{

	?>
	function dynamic_call_action(option,route,value,chooser_height)
		{
		var call_menu_list = '<?php echo $call_menu_list ?>';
		var ingroup_list = '<?php echo $ingroup_list ?>';
		var IGcampaign_id_list = '<?php echo $IGcampaign_id_list ?>';
		var IGhandle_method_list = '<?php echo $IGhandle_method_list ?>';
		var IGsearch_method_list = "<?php echo $IGsearch_method_list ?>";
		var did_list = '<?php echo $did_list ?>';
		var selected_value = '';
		var selected_context = '';
		var new_content = '';

		var select_list = document.getElementById(option + "");
		var selected_route = select_list.value;
		var span_to_update = document.getElementById(option + "_value_span");

		if (selected_route=='CALLMENU')
			{
			if (route == selected_route)
				{
				selected_value = '<option SELECTED value="' + value + '">' + value + "</option>\n";
				}
			else
				{value = '';}
			new_content = '<span name=' + option + '_value_link id=' + option + '_value_link><a href="./admin.php?ADD=3511&menu_id=' + value + "\"><?php echo _QXZ("Call Menu"); ?>: </a></span><select size=1 name=" + option + '_value id=' + option + "_value onChange=\"dynamic_call_action_link('" + option + "','CALLMENU');\">" + call_menu_list + "\n" + selected_value + '</select>';
			}
		if (selected_route=='INGROUP')
			{
			if ( (route != selected_route) || (value.length < 10) )
				{value = 'SALESLINE,CID,LB,998,TESTCAMP,1,,,,';}
			var value_split = value.split(",");
			var IGgroup_id =				value_split[0];
			var IGhandle_method =			value_split[1];
			var IGsearch_method =			value_split[2];
			var IGlist_id =					value_split[3];
			var IGcampaign_id =				value_split[4];
			var IGphone_code =				value_split[5];
			var IGvid_enter_filename =		value_split[6];
			var IGvid_id_number_filename =	value_split[7];
			var IGvid_confirm_filename =	value_split[8];
			var IGvid_validate_digits =		value_split[9];

			if (route == selected_route)
				{
				selected_value = '<option SELECTED>' + IGgroup_id + '</option>';
				}

			new_content = new_content + '<span name=' + option + '_value_link id=' + option + '_value_link><a href="admin.php?ADD=3111&group_id=' + IGgroup_id + "\"><?php echo _QXZ("In-Group"); ?>:</a> </span> ";
			new_content = new_content + '<select size=1 name=IGgroup_id_' + option + ' id=IGgroup_id_' + option + " onChange=\"dynamic_call_action_link('IGgroup_id_" + option + "','INGROUP');\">";
			new_content = new_content + '' + ingroup_list + "\n" + selected_value + '</select>';
			new_content = new_content + " &nbsp; <?php echo _QXZ("Handle Method"); ?>: <select size=1 name=IGhandle_method_" + option + ' id=IGhandle_method_' + option + '>';
			new_content = new_content + '' + '<option SELECTED>' + IGhandle_method + '</option>' + IGhandle_method_list + '</select>' + "\n";
			new_content = new_content + "<BR><?php echo _QXZ("Search Method"); ?>: <select size=1 name=IGsearch_method_" + option + ' id=IGsearch_method_' + option + '>';
			new_content = new_content + '' + IGsearch_method_list + "\n" + '<option SELECTED>' + IGsearch_method + '</select>';
			new_content = new_content + " &nbsp; <?php echo _QXZ("List ID"); ?>: <input type=text size=5 maxlength=14 name=IGlist_id_" + option + ' id=IGlist_id_' + option + ' value="' + IGlist_id + '">';
			new_content = new_content + "<BR><?php echo _QXZ("Campaign ID"); ?>: <select size=1 name=IGcampaign_id_" + option + ' id=IGcampaign_id_' + option + '>';
			new_content = new_content + '' + IGcampaign_id_list + "\n" + '<option SELECTED>' + IGcampaign_id + '</select>';
			new_content = new_content + " &nbsp; <?php echo _QXZ("Phone Code"); ?>: <input type=text size=5 maxlength=14 name=IGphone_code_" + option + ' id=IGphone_code_' + option + ' value="' + IGphone_code + '">';
		//	new_content = new_content + "<BR> &nbsp; <?php echo _QXZ("VID Enter Filename"); ?>: <input type=text name=IGvid_enter_filename_" + option + " id=IGvid_enter_filename_" + option + " size=40 maxlength=255 value=\"" + IGvid_enter_filename + "\"> <a href=\"javascript:launch_chooser('IGvid_enter_filename_" + option + "','date');\"><?php echo _QXZ("audio chooser"); ?></a>";
		//	new_content = new_content + "<BR> &nbsp; <?php echo _QXZ("VID ID Number Filename"); ?>: <input type=text name=IGvid_id_number_filename_" + option + " id=IGvid_id_number_filename_" + option + " size=40 maxlength=255 value=\"" + IGvid_id_number_filename + "\"> <a href=\"javascript:launch_chooser('IGvid_id_number_filename_" + option + "','date');\"><?php echo _QXZ("audio chooser"); ?></a>";
		//	new_content = new_content + "<BR> &nbsp; <?php echo _QXZ("VID Confirm Filename"); ?>: <input type=text name=IGvid_confirm_filename_" + option + " id=IGvid_confirm_filename_" + option + " size=40 maxlength=255 value=\"" + IGvid_confirm_filename + "\"> <a href=\"javascript:launch_chooser('IGvid_confirm_filename_" + option + "','date');\"><?php echo _QXZ("audio chooser"); ?></a>";
		//	new_content = new_content + ' &nbsp; <?php echo _QXZ("VID Digits"); ?>: <input type=text size=3 maxlength=3 name=IGvid_validate_digits_' + option + ' id=IGvid_validate_digits_' + option + ' value="' + IGvid_validate_digits + '">';

			}
		if (selected_route=='DID')
			{
			if (route == selected_route)
				{
				selected_value = '<option SELECTED value="' + value + '">' + value + "</option>\n";
				}
			else
				{value = '';}
			new_content = '<span name=' + option + '_value_link id=' + option + '_value_link><a href="admin.php?ADD=3311&did_pattern=' + value + "\"><?php echo _QXZ("DID"); ?>:</a> </span><select size=1 name=" + option + '_value id=' + option + "_value onChange=\"dynamic_call_action_link('" + option + "','DID');\">" + did_list + "\n" + selected_value + '</select>';
			}
		if (selected_route=='MESSAGE')
			{
			if (route == selected_route)
				{
				selected_value = value;
				}
			else
				{value = 'nbdy-avail-to-take-call|vm-goodbye';}
			new_content = "<?php echo _QXZ("Audio File"); ?>: <input type=text name=" + option + "_value id=" + option + "_value size=40 maxlength=255 value=\"" + value + "\"> <a href=\"javascript:launch_chooser('" + option + "_value','date');\"><?php echo _QXZ("audio chooser"); ?></a>";
			}
		if (selected_route=='EXTENSION')
			{
			if ( (route != selected_route) || (value.length < 3) )
				{value = '8304,default';}
			var value_split = value.split(",");
			var EXextension =	value_split[0];
			var EXcontext =		value_split[1];

			new_content = "<?php echo _QXZ("Extension"); ?>: <input type=text name=EXextension_" + option + " id=EXextension_" + option + " size=20 maxlength=255 value=\"" + EXextension + "\"> &nbsp; <?php echo _QXZ("Context"); ?>: <input type=text name=EXcontext_" + option + " id=EXcontext_" + option + " size=20 maxlength=255 value=\"" + EXcontext + "\"> ";
			}
		if ( (selected_route=='VOICEMAIL') || (selected_route=='VMAIL_NO_INST') )
			{
			if (route == selected_route)
				{
				selected_value = value;
				}
			else
				{value = '101';}
			new_content = "<?php echo _QXZ("Voicemail Box"); ?>: <input type=text name=" + option + "_value id=" + option + "_value size=12 maxlength=10 value=\"" + value + "\"> <a href=\"javascript:launch_vm_chooser('" + option + "_value','date');\"><?php echo _QXZ("voicemail chooser"); ?></a>";
			}

		if (new_content.length < 1)
			{new_content = selected_route}

		span_to_update.innerHTML = new_content;
		}

	function dynamic_call_action_link(field,route)
		{
		var selected_value = '';
		var new_content = '';

		if ( (route=='CALLMENU') || (route=='DID') )
			{var select_list = document.getElementById(field + "_value");}
		if (route=='INGROUP')
			{
			var select_list = document.getElementById(field + "");
			field = field.replace(/IGgroup_id_/, "");
			}
		var selected_value = select_list.value;
		var span_to_update = document.getElementById(field + "_value_link");

		if (route=='CALLMENU')
			{
			new_content = '<a href="admin.php?ADD=3511&menu_id=' + selected_value + "\"><?php echo _QXZ("Call Menu"); ?>:</a>";
			}
		if (route=='INGROUP')
			{
			new_content = '<a href="admin.php?ADD=3111&group_id=' + selected_value + "\"><?php echo _QXZ("In-Group"); ?>:</a>";
			}
		if (route=='DID')
			{
			new_content = '<a href="admin.php?ADD=3311&did_pattern=' + selected_value + "\"><?php echo _QXZ("DID"); ?>:</a>";
			}

		if (new_content.length < 1)
			{new_content = selected_route}

		span_to_update.innerHTML = new_content;
		}

	<?php
	}
echo "</script>\n";

##### BEGIN - bar chart CSS style #####
?>

<style type="text/css">
<!--


<?php

if ($ADD == '730000000000000')
{
?>

.diff table{
margin          : 1px 1px 1px 1px;
border-collapse : collapse;
border-spacing  : 0;
}

.diff td{
vertical-align : top;
font-family    : monospace;
font-size      : 9;
}
.diff span{
display:block;
min-height:1pm;
margin-top:-1px;
padding:1px 1px 1px 1px;
}

* html .diff span{
height:1px;
}

.diff span:first-child{
margin-top:1px;
}

.diffDeleted span{
border:1px solid rgb(255,51,0);
background:rgb(255,173,153);
}

.diffInserted span{
border:1px solid rgb(51,204,51);
background:rgb(102,255,51);
}

<?php
}
?>

.auraltext
	{
	position: absolute;
	font-size: 0;
	left: -1000px;
	}
.chart_td
	{background-image: url(images/gridline58.gif); background-repeat: repeat-x; background-position: left top; border-left: 1px solid #e5e5e5; border-right: 1px solid #e5e5e5; padding:0; border-bottom: 1px solid #e5e5e5; background-color:transparent;}

.head_style
	{
	background-color: <?php echo $Mmain_bgcolor ?>;
	}
.head_style:hover{background-color: #262626;}

.head_style_selected
	{
	background-color: <?php echo $Mhead_color ?>;
	}
.head_style_selected:hover{background-color: <?php echo $Mhead_color ?>;}

.subhead_style
	{
	background-color: <?php echo $Msubhead_color ?>;
	}
.subhead_style:hover{background-color: white;}

.subhead_style_selected
	{
	background-color: <?php echo $Mselected_color ?>;
	}
.subhead_style_selected:hover{background-color: <?php echo $Mselected_color ?>;}

.adminmenu_style_selected
	{
	background-color: white;
	}
.adminmenu_style_selected:hover{background-color: #E6E6E6;}

.records_list_x
	{
	background-color: #<?php echo $SSstd_row2_background ?>;
	}
.records_list_x:hover{background-color: #E6E6E6;}

.records_list_y
	{
	background-color: #<?php echo $SSstd_row1_background ?>;
	}
.records_list_y:hover{background-color: #E6E6E6;}


.horiz_line
	{
	height: 0px;
	margin: 0px;
	border-bottom: 1px solid #E6E6E6;
	font-size: 1px;
	}
.horiz_line_grey
	{
	height: 0px;
	margin: 0px;
	border-bottom: 1px solid #9E9E9E;
	font-size: 1px;
	}

.sub_sub_head_links
	{
	font-family:HELVETICA;
	font-size:11;
	color:BLACK;
	}

-->

</style>

<?php
##### END - bar chart CSS style #####

echo "</head>\n";
if ( ($SSadmin_modify_refresh > 1) and (preg_match("/^3|^4/",$ADD)) )
	{
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onLoad=\"modify_refresh_display();\">\n";
	}
else
	{
	echo "<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
	}
	
echo "<!-- INTERNATIONALIZATION-LINKS-PLACEHOLDER-VICIDIAL -->\n";


if ($header_font_size < 4) {$header_font_size='12';}
if ($subheader_font_size < 4) {$subheader_font_size='11';}
if ($subcamp_font_size < 4) {$subcamp_font_size='11';}



?>
<CENTER>

<TABLE BGCOLOR=white cellpadding=0 cellspacing=0>
<!-- BEGIN SIDEBAR NAVIGATION -->
<TR><TD VALIGN=TOP WIDTH=170 BGCOLOR=#<?php echo "$SSmenu_background" ?> ALIGN=CENTER VALIGN=MIDDLE>
<A HREF="./admin.php"><IMG SRC="<?php echo $selected_logo; ?>" WIDTH=170 HEIGHT=45 BORDER=0 ALT="System logo"></A>
<B><FONT FACE="ARIAL,HELVETICA" COLOR=white><?php echo _QXZ("ADMINISTRATION"); ?></FONT></B><BR>

	<TABLE CELLPADDING=2 CELLSPACING=0 BGCOLOR=#<?php echo "$SSmenu_background" ?> WIDTH=160>
	<?php
	if ( ($reports_only_user < 1) and ($qc_only_user < 1) )
		{
	?>
	<!-- REPORTS NAVIGATION -->
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=999999';\"";} ?>><TD ALIGN=LEFT <?php echo $reports_hh ?>>
	<a href="<?php echo $ADMIN ?>?ADD=999999" STYLE="text-decoration:none;"><?php echo $reports_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $reports_fc ?>"><?php echo $reports_bold ?> <?php echo _QXZ("Reports"); ?> </a>
	</TD></TR>

	<!-- USERS NAVIGATION -->
	<TR WIDTH=100%><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR WIDTH=160 BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=0A';\"";} ?>><TD ALIGN=LEFT <?php echo $users_hh ?> WIDTH=160>
	<a href="<?php echo $ADMIN ?>?ADD=0A" STYLE="text-decoration:none;"><?php echo $users_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $users_fc ?>"><?php echo $users_bold ?><?php echo _QXZ("Users"); ?></a>
	</TD></TR>
	<?php
	if (strlen($users_hh) > 25) 
		{ 
		$list_sh="CLASS=\"subhead_style\"";
		$new_sh="CLASS=\"subhead_style\"";
		$copy_sh="CLASS=\"subhead_style\"";
		$search_sh="CLASS=\"subhead_style\"";
		$stats_sh="CLASS=\"subhead_style\"";
		$status_sh="CLASS=\"subhead_style\"";
		$sheet_sh="CLASS=\"subhead_style\"";
		$territory_sh="CLASS=\"subhead_style\"";
		$newlimit_sh="CLASS=\"subhead_style\"";

		if ($sh=='list') {$list_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='new') {$new_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='copy') {$copy_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='search') {$search_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='stats') {$stats_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='status') {$status_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='sheet') {$sheet_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='territory') {$territory_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='newlimit') {$newlimit_sh="CLASS=\"subhead_style_selected\"";}

		?>
	<TR <?php echo $list_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=0A';\"";} ?>><TD ALIGN=LEFT>
	 &nbsp; <a href="<?php echo $ADMIN ?>?ADD=0A" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Show Users"); ?> </a>
	</TR><TR <?php echo $new_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1';\"";} ?>><TD ALIGN=LEFT>
	<?php if ($add_copy_disabled < 1) { ?>
	 &nbsp; <a href="<?php echo $ADMIN ?>?ADD=1" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Add A New User"); ?> </a>
	</TR><TR <?php echo $copy_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1A';\"";} ?>><TD ALIGN=LEFT>
	 &nbsp; <a href="<?php echo $ADMIN ?>?ADD=1A" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Copy User"); ?> </a>
	</TR><TR <?php echo $search_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=550';\"";} ?>><TD ALIGN=LEFT>
	<?php } ?>
	 &nbsp; <a href="<?php echo $ADMIN ?>?ADD=550" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Search For A User"); ?> </a>
	</TR><TR <?php echo $stats_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='./user_stats.php?user=$user';\"";} ?>><TD ALIGN=LEFT>
	 &nbsp; <a href="./user_stats.php?user=<?php echo $user ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("User Stats"); ?> </a>
	</TR><TR <?php echo $status_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='./user_status.php?user=$user';\"";} ?>><TD ALIGN=LEFT>
	 &nbsp; <a href="./user_status.php?user=<?php echo $user ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("User Status"); ?> </a>
	</TR><TR <?php echo $sheet_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='./AST_agent_time_sheet.php?agent=$user';\"";} ?>><TD ALIGN=LEFT>
	 &nbsp; <a href="./AST_agent_time_sheet.php?agent=<?php echo $user ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Time Sheet"); ?> </a> </TD></TR>
	 <?php
	if ( ($SSuser_territories_active > 0) or ($user_territories_active > 0) )
		{ ?>

	</TR><TR <?php echo $territory_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='./user_territories.php?agent=$user';\"";} ?>><TD ALIGN=LEFT>
	 &nbsp; <a href="./user_territories.php?agent=<?php echo $user ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("User Territories"); ?> </a> </TD></TR>

	 <?php
		}
	if ($SSuser_new_lead_limit > 0)
		{ ?>

	</TR><TR <?php echo $newlimit_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='./admin_user_list_new.php?user=---ALL---&list_id=NONE&stage=overall';\"";} ?>><TD ALIGN=LEFT>
	 &nbsp; <a href="./admin_user_list_new.php?user=---ALL---&list_id=NONE&stage=overall" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Overall New Lead Limits"); ?> </a> </TD></TR>

	<?php }
	  }
	?>
	<!-- CAMPAIGNS NAVIGATION -->
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=10';\"";} ?>><TD ALIGN=LEFT <?php echo $campaigns_hh ?>>
	<a href="<?php echo $ADMIN ?>?ADD=10" STYLE="text-decoration:none;"><?php echo $campaigns_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $campaigns_fc ?>"><?php echo $campaigns_bold ?><?php echo _QXZ("Campaigns"); ?></a>
	</TD></TR>
	<?php
	if (strlen($campaigns_hh) > 25) 
		{ 
		$list_sh="CLASS=\"subhead_style\"";
		$status_sh="CLASS=\"subhead_style\"";
		$hotkey_sh="CLASS=\"subhead_style\"";
		$recycle_sh="CLASS=\"subhead_style\"";
		$autoalt_sh="CLASS=\"subhead_style\"";
		$pause_sh="CLASS=\"subhead_style\"";
		$listmix_sh="CLASS=\"subhead_style\"";
		$preset_sh="CLASS=\"subhead_style\"";
		$accid_sh="CLASS=\"subhead_style\"";

		if ($sh=='basic') {$sh='list';}
		if ($sh=='detail') {$sh='list';}
		if ($sh=='dialstat') {$sh='list';}

		if ($sh=='list') {$list_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='status') {$status_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='hotkey') {$hotkey_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='recycle') {$recycle_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='autoalt') {$autoalt_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='pause') {$pause_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='listmix') {$listmix_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='preset') {$preset_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='accid') {$accid_sh="CLASS=\"subhead_style_selected\"";}

		?>
		<TR <?php echo $list_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=10';\"";} ?>>
		<TD ALIGN=LEFT <?php echo $list_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=10" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Campaigns Main"); ?></a></TD>
		</TR><TR <?php echo $status_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=32';\"";} ?>>
		<TD ALIGN=LEFT <?php echo $status_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=32" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Statuses"); ?></a></TD>
		</TR><TR <?php echo $hotkey_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=33';\"";} ?>>
		<TD ALIGN=LEFT <?php echo $hotkey_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=33" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("HotKeys"); ?></a></TD>
		<?php
		if ($SSoutbound_autodial_active > 0)
			{
			?>
			</TR><TR <?php echo $recycle_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=35';\"";} ?>>
			<TD ALIGN=LEFT <?php echo $recycle_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=35" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Lead Recycle"); ?></a></TD>
			</TR><TR <?php echo $autoalt_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=36';\"";} ?>>
			<TD ALIGN=LEFT <?php echo $autoalt_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=36" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Auto-Alt Dial"); ?></a></TD>
			</TR><TR <?php echo $listmix_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=39';\"";} ?>>
			<TD ALIGN=LEFT <?php echo $listmix_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=39" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("List Mix"); ?></a></TD>
			<?php
			}
		?>
		</TR><TR <?php echo $pause_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=37';\"";} ?>>
		<TD ALIGN=LEFT <?php echo $pause_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=37" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Pause Codes"); ?></a></TD>
		</TR><TR <?php echo $preset_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=301';\"";} ?>>
		<TD ALIGN=LEFT <?php echo $preset_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=301" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("Presets"); ?></a></TD>
		<?php
		if ($SScampaign_cid_areacodes_enabled > 0)
			{
			?>
			</TR><TR <?php echo $accid_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=302';\"";} ?>>
			<TD ALIGN=LEFT <?php echo $accid_sh ?>> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=302" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subheader_font_size ?>;color:BLACK"><?php echo _QXZ("AC-CID"); ?></a></TD>
			<?php
			}
		 } 
	?>
	<!-- LISTS NAVIGATION -->
	<?php
	if ($SSoutbound_autodial_active > 0)
		{
		?>
		<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
		<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100';\"";} ?>><TD ALIGN=LEFT <?php echo $lists_hh ?>><a href="<?php echo $ADMIN ?>?ADD=100" STYLE="text-decoration:none;"><?php echo $lists_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $lists_fc ?>"><?php echo $lists_bold ?><?php echo _QXZ("Lists"); ?></a></TD></TR>
		<?php
		if (strlen($lists_hh) > 25) 
			{ 
			$list_sh="CLASS=\"subhead_style\"";
			$new_sh="CLASS=\"subhead_style\"";
			$search_sh="CLASS=\"subhead_style\"";
			$lead_sh="CLASS=\"subhead_style\"";
			$load_sh="CLASS=\"subhead_style\"";
			$dnc_sh="CLASS=\"subhead_style\"";
			$custom_sh="CLASS=\"subhead_style\"";
			$cpcust_sh="CLASS=\"subhead_style\"";
			$droplist_sh="CLASS=\"subhead_style\"";

			if ($LOGdelete_from_dnc > 0) {$DNClink = _QXZ("Add-Delete DNC Number");}
			else {$DNClink = _QXZ("Add DNC Number");}

			if ($sh=='list') {$list_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='new') {$new_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='search') {$search_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='lead') {$lead_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='load') {$load_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='dnc') {$dnc_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='custom') {$custom_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='cpcust') {$cpcust_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='droplist') {$droplist_sh="CLASS=\"subhead_style_selected\"";}

			?>
			<TR <?php echo $list_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
			<a href="<?php echo $ADMIN ?>?ADD=100" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Lists"); ?> </a>
			</TR><TR <?php echo $new_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
			<?php if ($add_copy_disabled < 1) { ?>
			<a href="<?php echo $ADMIN ?>?ADD=111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New List"); ?> </a>
			</TR><TR <?php echo $search_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='admin_search_lead.php';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
			<?php } ?>
			<a href="admin_search_lead.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Search For A Lead"); ?> </a>
			</TR><TR <?php echo $lead_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='admin_modify_lead.php';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
			<a href="admin_modify_lead.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Lead"); ?> </a>
			</TR><TR <?php echo $dnc_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=121';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
			<a href="<?php echo $ADMIN ?>?ADD=121" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo $DNClink ?> </a>
			</TR><TR <?php echo $load_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='admin_listloader_fourth_gen.php';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
			<a href="./admin_listloader_fourth_gen.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Load New Leads"); ?> </a>
			<?php
			if ($SScustom_fields_enabled > 0)
				{
				$admin_lists_custom = 'admin_lists_custom.php';
				?>
				</TR><TR <?php echo $custom_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$admin_lists_custom';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
				<a href="./<?php echo $admin_lists_custom ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("List Custom Fields"); ?> </a>
				</TR><TR <?php echo $cpcust_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$admin_lists_custom?action=COPY_FIELDS_FORM';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
				<a href="./<?php echo $admin_lists_custom ?>?action=COPY_FIELDS_FORM" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy Custom Fields"); ?> </a>
				<?php
				}
			if ($SSenable_drop_lists > 0)
				{
				?>
				<TR <?php echo $droplist_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
				<a href="<?php echo $ADMIN ?>?ADD=130" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Drop Lists"); ?> </a>
				<?php
				}
			?>
			</TD></TR>
			<?php
			}
		}

	if (($SSqc_features_active=='1') && ($qc_auth=='1')) 
		{ ?>
	<!-- QC navigation -->
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000000000000';\"";} ?>>
		<TD ALIGN=LEFT <?php echo $qc_hh ?>>
			<a href="<?php echo $ADMIN ?>?ADD=100000000000000" STYLE="text-decoration:none;"><?php echo $qc_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $qc_fc ?>"><?php echo $qc_bold ?> <?php echo _QXZ("Quality Control"); ?> </FONT></a>
		</TD>
	</TR>
	<?php
	if (strlen($qc_hh) > 25) 
		{
		$campaign_sh="CLASS=\"subhead_style\"";
		$ingroup_sh="CLASS=\"subhead_style\"";
		$list_sh="CLASS=\"subhead_style\"";
		$enter_sh="CLASS=\"subhead_style\"";
		$modify_sh="CLASS=\"subhead_style\"";
		$scorecard_sh="CLASS=\"subhead_style\"";

		if($qc_display_group_type=="CAMPAIGN") {$sh="campaign";}
		if($qc_display_group_type=="INGROUP") {$sh="ingroup";}
		if($qc_display_group_type=="LIST") {$sh="list";}
		#if($sh=="modify") {$sh="modify";}
		if($qc_display_group_type=="SCORECARD") {$sh="scorecard";}
		if(!$sh) {$sh="campaign";}

		if ($sh=='campaign') {$campaign_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='ingroup') {$ingroup_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='list') {$list_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='enter') {$enter_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='modify') {$modify_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='scorecard') {$scorecard_sh="CLASS=\"subhead_style_selected\"";}

		?>
	<TR <?php echo $campaign_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000000000000&qc_display_group_type=CAMPAIGN';\"";} ?>>
		<TD ALIGN=LEFT> &nbsp;
			<a href="<?php echo $ADMIN ?>?ADD=100000000000000&qc_display_group_type=CAMPAIGN" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("QC Calls by Campaign"); ?> </FONT></a>
		</TD>
	</TR>
	<TR <?php echo $list_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000000000000&qc_display_group_type=LIST';\"";} ?>>
		<TD ALIGN=LEFT> &nbsp;
			<a href="<?php echo $ADMIN ?>?ADD=100000000000000&qc_display_group_type=LIST" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("QC Calls by List"); ?> </FONT></a>
		</TD>
	</TR>
	<TR <?php echo $ingroup_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000000000000&qc_display_group_type=INGROUP';\"";} ?>>
		<TD ALIGN=LEFT> &nbsp;
			<a href="<?php echo $ADMIN ?>?ADD=100000000000000&qc_display_group_type=INGROUP" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("QC Calls by Ingroup"); ?> </FONT></a>
		</TD>
	</TR>
<!--
	<TR <?php echo $enter_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000000000000';\"";} ?>>
		<TD ALIGN=LEFT> &nbsp;
			<a href="<?php echo $ADMIN ?>?ADD=100000000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Enter QC Queue"); ?> </FONT></a>
		</TD>
	</TR>
//-->
	<TR <?php echo $scorecard_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"qc_scorecards.php';\"";} ?>>
		<TD ALIGN=LEFT> &nbsp;
			<a href="qc_scorecards.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show QC Scorecards"); ?> </FONT></a>
		</TD>
	</TR>
	<TR <?php echo $modify_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=341111111111111';\"";} ?>>
		<TD ALIGN=LEFT> &nbsp;
			<a href="<?php echo $ADMIN ?>?ADD=341111111111111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Modify QC Codes"); ?> </FONT></a>
		</TD>
	</TR>
		<?php }
		}
	?>
	<!-- SCRIPTS NAVIGATION -->
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1000000';\"";} ?>><TD ALIGN=LEFT <?php echo $scripts_hh ?>>
	<a href="<?php echo $ADMIN ?>?ADD=1000000" STYLE="text-decoration:none;"><?php echo $scripts_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $scripts_fc ?>"><?php echo $scripts_bold ?> <?php echo _QXZ("Scripts"); ?> </a>
	</TD></TR>
	<?php
	if (strlen($scripts_hh) > 25) 
		{ 
		$list_sh="CLASS=\"subhead_style\"";
		$new_sh="CLASS=\"subhead_style\"";

		if ($sh=='list') {$list_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='new') {$new_sh="CLASS=\"subhead_style_selected\"";}

		?>
		<TR <?php echo $list_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1000000';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Scripts"); ?> </a>
		</TR><TR <?php echo $new_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1111111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=1111111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Script"); ?> </a>
		<?php } ?>
		</TD></TR>
		<?php } 
	?>
	<!-- FILTERS NAVIGATION -->
	<?php
	if ($SSoutbound_autodial_active > 0)
		{
		?>
		<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
		<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=10000000';\"";} ?>><TD ALIGN=LEFT <?php echo $filters_hh ?>><a href="<?php echo $ADMIN ?>?ADD=10000000" STYLE="text-decoration:none;"><?php echo $filters_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $filters_fc ?>"><?php echo $filters_bold ?> <?php echo _QXZ("Filters"); ?> </a></TD></TR>
		<?php
		if (strlen($filters_hh) > 25) 
			{ 
			$list_sh="CLASS=\"subhead_style\"";
			$new_sh="CLASS=\"subhead_style\"";

			if ($sh=='list') {$list_sh="CLASS=\"subhead_style_selected\"";}
			if ($sh=='new') {$new_sh="CLASS=\"subhead_style_selected\"";}
			?>
		<TR <?php echo $list_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=10000000';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=10000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Filters"); ?> </a>
		</TR><TR <?php echo $new_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=11111111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=11111111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Filter"); ?> </a>
		<?php } ?>
		</TD></TR>
		<?php } 
		}
	?>
	<!-- INGROUPS NAVIGATION -->
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1001';\"";} ?>><TD ALIGN=LEFT <?php echo $ingroups_hh ?>>
	<a href="<?php echo $ADMIN ?>?ADD=1001" STYLE="text-decoration:none;"><?php echo $inbound_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $ingroups_fc ?>"><?php echo $ingroups_bold ?> <?php echo _QXZ("Inbound"); ?> </a>
	</TD></TR>
	<?php
	if (strlen($ingroups_hh) > 25) 
		{
		$listIG_sh="CLASS=\"subhead_style\"";
		$newIG_sh="CLASS=\"subhead_style\"";
		$copyIG_sh="CLASS=\"subhead_style\"";
		$listEG_sh="CLASS=\"subhead_style\"";
		$newEG_sh="CLASS=\"subhead_style\"";
		$copyEG_sh="CLASS=\"subhead_style\"";
		$listCG_sh="CLASS=\"subhead_style\"";
		$newCG_sh="CLASS=\"subhead_style\"";
		$copyCG_sh="CLASS=\"subhead_style\"";
		$listDID_sh="CLASS=\"subhead_style\"";
		$newDID_sh="CLASS=\"subhead_style\"";
		$copyDID_sh="CLASS=\"subhead_style\"";
		$didRA_sh="CLASS=\"subhead_style\"";
		$listCM_sh="CLASS=\"subhead_style\"";
		$newCM_sh="CLASS=\"subhead_style\"";
		$copyCM_sh="CLASS=\"subhead_style\"";
		$listFPG_sh="CLASS=\"subhead_style\"";
		$newFPG_sh="CLASS=\"subhead_style\"";
		$addFPG_sh="CLASS=\"subhead_style\"";

		if ($sh=='listIG') {$listIG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='newIG') {$newIG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='copyIG') {$copyIG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='listEG') {$listEG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='newEG') {$newEG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='copyEG') {$copyEG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='listCG') {$listCG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='newCG') {$newCG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='copyCG') {$copyCG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='listDID') {$listDID_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='newDID') {$newDID_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='copyDID') {$copyDID_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='didRA') {$didRA_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='listCM') {$listCM_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='newCM') {$newCM_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='copyCM') {$copyCM_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='listFPG') {$listFPG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='newFPG') {$newFPG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='addFPG') {$addFPG_sh="CLASS=\"subhead_style_selected\"";}

		if ($LOGdelete_from_dnc > 0) {$FPGlink = _QXZ("Add-Delete FPG Number");}
		else {$FPGlink = _QXZ("Add FPG Number");}
		?>
		<TR <?php echo $listIG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1000';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1000" STYLE="text-decoration:none;"><img src="images/icon_black_inbound.png" border=0 alt=\"In-Groups\" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show In-Groups"); ?> </a>
		</TD></TR><TR <?php echo $newIG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=1111" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New In-Group"); ?> </a>
		</TD></TR><TR <?php echo $copyIG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1211';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1211" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy In-Group"); ?> </a>
		<?php } ?>
		<TR WIDTH=160 CLASS="subhead_style"><TD><DIV CLASS="horiz_line_grey"></DIV></TD></TR>
		<?php
		if ($SSemail_enabled>0) 
			{
		?>
		<TR <?php echo $listEG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1800';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1800" STYLE="text-decoration:none;"><img src="images/icon_email.png" border=0 alt=\"Email Groups\" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Email Groups"); ?> </a>
		</TD></TR><TR <?php echo $newEG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1811';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=1811" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add New Email Group"); ?> </a>
		</TD></TR><TR <?php echo $copyEG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1911';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1911" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy Email Group"); ?> </a>
		<?php } ?>
		<TR WIDTH=160 CLASS="subhead_style"><TD><DIV CLASS="horiz_line_grey"></DIV></TD></TR>
		<?php
			}
		?>
		<?php
		if ($SSchat_enabled>0) 
			{
		?>
		<TR <?php echo $listCG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1900';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1900" STYLE="text-decoration:none;"><img src="images/icon_chat.png" border=0 alt=\" Chat Groups\" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Chat Groups"); ?> </a>
		</TD></TR><TR <?php echo $newCG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=18111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=18111" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add New Chat Group"); ?> </a>
		</TD></TR><TR <?php echo $copyCG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=19111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=19111" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy Chat Group"); ?> </a>
		<?php } ?>
		<TR WIDTH=160 CLASS="subhead_style"><TD><DIV CLASS="horiz_line_grey"></DIV></TD></TR>
		<?php
			}
		?>
		</TD></TR><TR <?php echo $listDID_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1300';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1300" STYLE="text-decoration:none;"><img src="images/icon_cidgroups.png" border=0 alt=\"DIDs\" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show DIDs"); ?> </a>
		</TD></TR><TR <?php echo $newDID_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1311';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=1311" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New DID"); ?> </a>
		</TD></TR><TR <?php echo $copyDID_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1411';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1411" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy DID"); ?> </a>
		<?php
			}
		if ($SSdid_ra_extensions_enabled > 0)
			{
			?>
			</TD></TR><TR <?php echo $didRA_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1320';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
			<a href="<?php echo $ADMIN ?>?ADD=1320" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"><?php echo _QXZ("RA Extensions"); ?></a>
			<?php
			}
		?>
		<TR WIDTH=160 CLASS="subhead_style"><TD><DIV CLASS="horiz_line_grey"></DIV></TD></TR>
		</TD></TR><TR <?php echo $listCM_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1500';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1500" STYLE="text-decoration:none;"><img src="images/icon_callmenu.png" border=0 alt=\"Call Menus\" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Call Menus"); ?> </a>
		</TD></TR><TR <?php echo $newCM_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1511';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=1511" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Call Menu"); ?> </a>
		</TD></TR><TR <?php echo $copyCM_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1611';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1611" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy Call Menu"); ?> </a>
		<?php } ?>

		<TR WIDTH=160 CLASS="subhead_style"><TD><DIV CLASS="horiz_line_grey"></DIV></TD></TR>
		</TD></TR><TR <?php echo $listFPG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1700';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1700" STYLE="text-decoration:none;"><img src="images/icon_filterphonegroup.png" border=0 alt=\"Filter Phone Groups\" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Filter Phone Groups"); ?> </a>
		</TD></TR><TR <?php echo $newFPG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1711';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=1711" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add Filter Phone Group"); ?> </a>
		</TD></TR><TR <?php echo $addFPG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=171';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php } ?>
		<a href="<?php echo $ADMIN ?>?ADD=171" STYLE="text-decoration:none;"><img src="images/blank.gif" border=0 alt=\" \" width=14 height=14 valign=middle> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo $FPGlink ?> </a>
		</TD></TR>
		<?php } 
		?>
	<!-- USERGROUPS NAVIGATION -->
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000';\"";} ?>><TD ALIGN=LEFT <?php echo $usergroups_hh ?>>
	<a href="<?php echo $ADMIN ?>?ADD=100000" STYLE="text-decoration:none;"><?php echo $usergroups_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $usergroups_fc ?>"><?php echo $usergroups_bold ?> <?php echo _QXZ("User Groups"); ?> </a>
	</TD></TR>
	<?php
	if (strlen($usergroups_hh) > 25)
		{ 
		$list_sh="CLASS=\"subhead_style\"";
		$new_sh="CLASS=\"subhead_style\"";
		$hour_sh="CLASS=\"subhead_style\"";
		$bulk_sh="CLASS=\"subhead_style\"";

		if ($sh=='list') {$list_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='new') {$new_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='hour') {$hour_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='bulk') {$bulk_sh="CLASS=\"subhead_style_selected\"";}
		?>
		<TR <?php echo $list_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=100000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show User Groups"); ?> </a>
		</TR><TR <?php echo $new_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=111111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=111111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New User Group"); ?> </a>
		</TR><TR <?php echo $hour_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='group_hourly_stats.php';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php } ?>
		<a href="group_hourly_stats.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Group Hourly Report"); ?> </a>
		</TR><TR <?php echo $bulk_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='user_group_bulk_change.php';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="user_group_bulk_change.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Bulk Group Change"); ?> </a>
		</TD></TR>
		<?php } 
	?>
	<!-- REMOTEAGENTS NAVIGATION -->
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=10000';\"";} ?>><TD ALIGN=LEFT <?php echo $remoteagent_hh ?>>
	<a href="<?php echo $ADMIN ?>?ADD=10000" STYLE="text-decoration:none;"><?php echo $remoteagents_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $remoteagent_fc ?>"><?php echo $remoteagent_bold ?> <?php echo _QXZ("Remote Agents"); ?> </a>
	</TD></TR>
	<?php
	if (strlen($remoteagent_hh) > 25) 
		{ 
		$list_sh="CLASS=\"subhead_style\"";
		$new_sh="CLASS=\"subhead_style\"";
		$listEG_sh="CLASS=\"subhead_style\"";
		$newEG_sh="CLASS=\"subhead_style\"";

		if ($sh=='list') {$list_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='new') {$new_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='listEG') {$listEG_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='newEG') {$newEG_sh="CLASS=\"subhead_style_selected\"";}
		?>
		<TR <?php echo $list_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=10000';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=10000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Remote Agents"); ?> </a>
		</TR><TR <?php echo $new_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=11111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=11111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add New Remote Agents"); ?> </a>
		</TR><TR <?php echo $listEG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=12000';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php } ?>
		<a href="<?php echo $ADMIN ?>?ADD=12000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Extension Groups"); ?> </a>
		</TR><TR <?php echo $newEG_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=12111';\"";} ?>><TD ALIGN=LEFT> &nbsp; 
		<?php if ($add_copy_disabled < 1) { ?>
		<a href="<?php echo $ADMIN ?>?ADD=12111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add Extension Group"); ?> </a>
		<?php } ?>
		</TD></TR>
	<?php } 
	?>
	<!-- ADMIN NAVIGATION -->
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=999998';\"";} ?>><TD ALIGN=LEFT <?php echo $admin_hh ?>>
	<a href="<?php echo $ADMIN ?>?ADD=999998" STYLE="text-decoration:none;"><?php echo $admin_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $admin_fc ?>"><?php echo $admin_bold ?> <?php echo _QXZ("Admin"); ?> </a>
	</TD></TR>
	<?php
	if (strlen($admin_hh) > 25) 
		{
		$times_sh="CLASS=\"subhead_style\"";
		$shifts_sh="CLASS=\"subhead_style\"";
		$templates_sh="CLASS=\"subhead_style\"";
		$carriers_sh="CLASS=\"subhead_style\"";
		$phones_sh="CLASS=\"subhead_style\"";
		$server_sh="CLASS=\"subhead_style\"";
		$conference_sh="CLASS=\"subhead_style\"";
		$settings_sh="CLASS=\"subhead_style\"";
		$label_sh="CLASS=\"subhead_style\"";
		$colors_sh="CLASS=\"subhead_style\"";
		$status_sh="CLASS=\"subhead_style\"";
		$audio_sh="CLASS=\"subhead_style\"";
		$moh_sh="CLASS=\"subhead_style\"";
		$languages_sh="CLASS=\"subhead_style\"";
		$soundboard_sh="CLASS=\"subhead_style\"";
		$vm_sh="CLASS=\"subhead_style\"";
		$tts_sh="CLASS=\"subhead_style\"";
		$cc_sh="CLASS=\"subhead_style\"";
		$cts_sh="CLASS=\"subhead_style\"";
		$sc_sh="CLASS=\"subhead_style\"";
		$sg_sh="CLASS=\"subhead_style\"";
		$cg_sh="CLASS=\"subhead_style\"";
		$vmmg_sh="CLASS=\"subhead_style\"";
		$qg_sh="CLASS=\"subhead_style\"";
		$emails_sh="CLASS=\"subhead_style\"";
		$ar_sh="CLASS=\"subhead_style\"";
		$il_sh="CLASS=\"subhead_style\"";

		if ($sh=='times') {$times_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='shifts') {$shifts_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='templates') {$templates_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='carriers') {$carriers_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='phones') {$phones_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='server') {$server_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='conference') {$conference_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='settings') {$settings_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='label') {$label_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='colors') {$colors_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='status') {$status_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='audio') {$audio_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='moh') {$moh_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='languages') {$languages_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='soundboard') {$soundboard_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='vm') {$vm_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='tts') {$tts_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='cc') {$cc_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='cts') {$cts_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='sc') {$sc_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='sg') {$sg_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='cg') {$cg_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='vmmg') {$vmmg_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='qg') {$qg_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='emails') {$emails_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='ar') {$ar_sh="CLASS=\"subhead_style_selected\"";}
		if ($sh=='il') {$il_sh="CLASS=\"subhead_style_selected\"";}

		?>
		<TR <?php echo $times_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000000';\"";} ?>>
		<TD ALIGN=LEFT <?php echo $times_sh ?> COLSPAN=2> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=100000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_calltimes.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Call Times"); ?> </a></TD>
		</TR><TR <?php echo $shifts_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=130000000';\"";} ?>><TD ALIGN=LEFT <?php echo $shifts_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=130000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_shifts.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Shifts"); ?> </a></TD>
		</TR><TR <?php echo $phones_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=10000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $phones_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=10000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_phones.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Phones"); ?> </a></TD>
		</TR><TR <?php echo $templates_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=130000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $templates_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=130000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_templates.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Templates"); ?> </a></TD>
		</TR><TR <?php echo $carriers_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=140000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $carriers_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=140000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_carriers.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Carriers"); ?> </a></TD>
		</TR><TR <?php echo $server_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $server_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=100000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_servers.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Servers"); ?> </a></TD>
		</TR><TR <?php echo $conference_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=1000000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $conference_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=1000000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_conferences.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Conferences"); ?> </a></TD>
		</TR><TR <?php echo $settings_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=311111111111111';\"";} ?>><TD ALIGN=LEFT <?php echo $settings_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=311111111111111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_settings.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("System Settings"); ?> </a></TD>
		</TR><TR <?php echo $label_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=180000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $label_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=180000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_screenlabels.png" border=0 alt=\"Labels\" width=14 height=14 valign=middle> <?php echo _QXZ("Screen Labels"); ?> </a></TD>
		</TR><TR <?php echo $colors_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=182000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $colors_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=182000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_screencolors.png" border=0 alt=\"Colors\" width=14 height=14 valign=middle> <?php echo _QXZ("Screen Colors"); ?> </a></TD>
		</TR><TR <?php echo $status_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=321111111111111';\"";} ?>><TD ALIGN=LEFT <?php echo $status_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=321111111111111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_statuses.png" border=0 alt=\"Statuses\" width=14 height=14 valign=middle> <?php echo _QXZ("System Statuses"); ?> </a></TD>
		</TR><TR <?php echo $sg_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=193000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $sg_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=193000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_statusgroups.png" border=0 alt=\"Status Groups\" width=14 height=14 valign=middle> <?php echo _QXZ("Status Groups"); ?> </a></TD>
		<?php
		if ($SScampaign_cid_areacodes_enabled > 0)
			{
			?>
		</TR><TR <?php echo $cg_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=196000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $cg_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=196000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_cidgroups.png" border=0 alt=\"CID Groups\" width=14 height=14 valign=middle> <?php echo _QXZ("CID Groups"); ?> </a></TD>
		<?php
			}
		?>
		</TR><TR <?php echo $vm_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=170000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $vm_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=170000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_voicemail.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Voicemail"); ?> </a></TD>
		</TR>
		<?php
		if ($SSemail_enabled > 0)
			{ ?>
			<TR <?php echo $emails_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='admin_email_accounts.php';\"";} ?>><TD ALIGN=LEFT <?php echo $emails_sh ?>> &nbsp; 
			<a href="admin_email_accounts.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_email.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Email Accounts"); ?> </a></TD>
			</TR>
		<?php }
		if ( ($sounds_central_control_active > 0) or ($SSsounds_central_control_active > 0) )
			{ ?>
			<TR <?php echo $audio_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='audio_store.php';\"";} ?>><TD ALIGN=LEFT <?php echo $audio_sh ?>> &nbsp; 
			<a href="audio_store.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_audiostore.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Audio Store"); ?> </a></TD>
			</TR>
			<TR <?php echo $moh_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=160000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $moh_sh ?>> &nbsp; 
			<a href="<?php echo $ADMIN ?>?ADD=160000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_musiconhold.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Music On Hold"); ?> </a></TD>
			</TR>
			<?php
		if ($SSenable_languages > 0)
				{ ?>
			<TR <?php echo $languages_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='admin_languages.php?ADD=163000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $languages_sh ?>> &nbsp; 
			<a href="admin_languages.php?ADD=163000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_languages.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Languages"); ?> </a></TD>
			</TR>
			<?php }
			if ( (preg_match("/soundboard/",$SSactive_modules) ) or ($SSagent_soundboards > 0) )
				{
			?>
			<TR <?php echo $soundboard_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='admin_soundboard.php?ADD=162000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $soundboard_sh ?>> &nbsp; 
			<a href="admin_soundboard.php?ADD=162000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_audiosoundboards.png" border=0 alt=\"Audio Soundboards\" width=14 height=14 valign=middle> <?php echo _QXZ("Audio Soundboards"); ?> </a></TD>
			</TR>

		<?php 
				}

			?>
			<TR <?php echo $vmmg_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='admin.php?ADD=197000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $vmmg_sh ?>> &nbsp; 
			<a href="admin.php?ADD=197000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_vm_messages.png" border=0 alt=\"VM Message Groups\" width=14 height=14 valign=middle> <?php echo _QXZ("VM Message Groups"); ?> </a></TD>
			</TR>

		<?php 

			}
		if ($SSenable_tts_integration > 0)
			{ ?>
			<TR <?php echo $tts_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=150000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $tts_sh ?>> &nbsp; 
			<a href="<?php echo $ADMIN ?>?ADD=150000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_texttospeech.png" border=0 alt=\"Text To Speech\" width=14 height=14 valign=middle> <?php echo _QXZ("Text To Speech"); ?> </a></TD>
			</TR>

		<?php }
		if ($SScallcard_enabled > 0)
			{ ?>
			<TR <?php echo $cc_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='callcard_admin.php';\"";} ?>><TD ALIGN=LEFT <?php echo $cc_sh ?>> &nbsp; 
			<a href="callcard_admin.php" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_callcard.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("CallCard Admin"); ?> </a></TD>
			</TR>

		<?php }
		if ($SScontacts_enabled > 0)
			{ ?>
			<TR <?php echo $cts_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=190000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $cts_sh ?>> &nbsp; 
			<a href="<?php echo $ADMIN ?>?ADD=190000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_contacts.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Contacts"); ?> </a></TD>
			</TR>

		<?php }
		?>
		</TR><TR <?php echo $sc_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=192000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $sc_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=192000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_settingscontainer.png" border=0 alt=\"Users\" width=14 height=14 valign=middle> <?php echo _QXZ("Settings Containers"); ?> </a></TD>
		</TR>
		<?php
		if ($SSenable_auto_reports > 0)
			{ ?>
			<TR <?php echo $ar_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=194000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $ar_sh ?>> &nbsp; 
			<a href="<?php echo $ADMIN ?>?ADD=194000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_autoreports.png" border=0 alt=\"Automated Reports\" width=14 height=14 valign=middle> <?php echo _QXZ("Automated Reports"); ?> </a></TD>
			</TR>

		<?php }
		if ($SSallow_ip_lists > 0)
			{ ?>
			<TR <?php echo $il_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=195000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $il_sh ?>> &nbsp; 
			<a href="<?php echo $ADMIN ?>?ADD=195000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_iplists.png" border=0 alt=\"IP Lists\" width=14 height=14 valign=middle> <?php echo _QXZ("IP Lists"); ?> </a></TD>
			</TR>

		<?php }
		?>
		</TR><TR <?php echo $qg_sh ?><?php if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=198000000000';\"";} ?>><TD ALIGN=LEFT <?php echo $qg_sh ?>> &nbsp; 
		<a href="<?php echo $ADMIN ?>?ADD=198000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <img src="images/icon_queuegroups.png" border=0 alt=\"Queue Groups\" width=14 height=14 valign=middle> <?php echo _QXZ("Queue Groups"); ?> </a></TD>
		</TR>
		<?php
			}
		}
	else
		{
		if ($reports_only_user > 0)
			{
			?>
			<!-- REPORTS NAVIGATION -->
			<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
			<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=999999';\"";} ?>><TD ALIGN=LEFT <?php echo $reports_hh ?>>
			<a href="<?php echo $ADMIN ?>?ADD=999999" STYLE="text-decoration:none;"><?php echo $reports_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $reports_fc ?>"><?php echo $reports_bold ?> <?php echo _QXZ("Reports"); ?> </a>
			</TD></TR>
			<?php
			}
		else
			{
			if (($SSqc_features_active=='1') && ($qc_auth=='1')) 
				{ ?>
			<!-- QC NAVIGATION -->
			<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
			<TR BGCOLOR=#<?php echo "$SSmenu_background "; if ($SSadmin_row_click > 0) {echo " onclick=\"window.document.location='$ADMIN?ADD=100000000000000';\"";} ?>>
				<TD ALIGN=LEFT <?php echo $qc_hh ?>>
					<a href="<?php echo $ADMIN ?>?ADD=100000000000000" STYLE="text-decoration:none;"><?php echo $reports_icon ?> <FONT STYLE="font-family:HELVETICA;font-size:<?php echo $header_font_size ?>;color:<?php echo $reports_fc ?>"><?php echo $qc_bold ?> <?php echo _QXZ("Quality Control"); ?> </FONT></a>
				</TD>
			</TR>
			<?php
			if (strlen($qc_hh) > 25) 
					{
				?>
			<TR BGCOLOR=<?php echo $qc_color ?>>
				<TD ALIGN=LEFT> &nbsp;
					<a href="<?php echo $ADMIN ?>?ADD=100000000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show QC Campaigns"); ?> </FONT></a>
				</TD>
			</TR>
			<TR BGCOLOR=<?php echo $qc_color ?>>
				<TD ALIGN=LEFT> &nbsp;
					<a href="<?php echo $ADMIN ?>?ADD=100000000000000" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Enter QC Queue"); ?> </FONT></a>
				</TD>
			</TR>
			<TR BGCOLOR=<?php echo $qc_color ?>>
				<TD ALIGN=LEFT> &nbsp;
					<a href="<?php echo $ADMIN ?>?ADD=341111111111111" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Modify QC Codes"); ?> </FONT></a>
				</TD>
			</TR>
				<?php }
				}
			}
		}
	?>
	<TR WIDTH=160><TD><DIV CLASS="horiz_line"></DIV></TD></TR>
	</TABLE>
	<BR>&nbsp;
</TD><TD VALIGN=TOP WIDTH=<?php echo $page_width ?> BGCOLOR=#<?php echo $SSframe_background ?>>
<!-- END SIDEBAR NAVIGATION -->

<span style="position:absolute;left:300px;top:30px;z-index:1;visibility:hidden;" id="audio_chooser_span">

</span>

<TABLE BGCOLOR=#<?php echo $SSframe_background ?> cellpadding=2 cellspacing=0 WIDTH=<?php echo $page_width ?> HEIGHT=15>
<TR BGCOLOR=#<?php echo "$SSmenu_background" ?>><TD ALIGN=LEFT BGCOLOR=#<?php echo "$SSmenu_background" ?>><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><a href="<?php echo $admin_home_url_LU ?>" STYLE="text-decoration:none;"><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=1><?php echo _QXZ("HOME"); ?></a> | <A HREF="../agc/timeclock.php?referrer=admin" STYLE="text-decoration:none;"><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=1> <?php echo _QXZ("Timeclock"); ?></A> | <a href="manager_chat_interface.php" STYLE="text-decoration:none;"><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=1> <?php echo _QXZ("Chat"); ?></a> | <a href="<?php echo $ADMIN ?>?force_logout=1" STYLE="text-decoration:none;"><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=1><?php echo _QXZ("Logout"); ?></a> <FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=1>(<?php echo $PHP_AUTH_USER ?>)</FONT>

<?php
if ($SSenable_languages == '1')
	{
	echo " | <a href=\"$ADMIN?ADD=999989\" STYLE=\"text-decoration:none;\"><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=1>"._QXZ("Change language")."</a>";
	}
?>

</TD><TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA" COLOR=WHITE SIZE=2><B><?php echo date("l F j, Y G:i:s A") ?> &nbsp; </B></TD></TR>

<TR BGCOLOR=#<?php echo "$SSmenu_background" ?>>







</TR>
	<?php
	if ( (strlen($list_sh) > 25) and (strlen($campaigns_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $subcamp_color ?>><TD ALIGN=LEFT COLSPAN=2><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=10"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Campaigns"); ?> </a> &nbsp; &nbsp; |<?php if ($add_copy_disabled < 1) { ?>
&nbsp; &nbsp; <a href="<?php echo $ADMIN ?>?ADD=11"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Campaign"); ?> </a> &nbsp; &nbsp; | &nbsp; &nbsp; <a href="<?php echo $ADMIN ?>?ADD=12"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy Campaign"); ?> </a> &nbsp; &nbsp; |<?php } ?> &nbsp; &nbsp; <a href="./AST_timeonVDADallSUMMARY.php"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Real-Time Campaigns Summary"); ?> </a></TD></TR>
		<?php }
	if ( (strlen($droplist_sh) > 25) and ($SSenable_drop_lists > 0) ) { 
		?>
	<TR BGCOLOR=<?php echo $subcamp_color ?>><TD ALIGN=LEFT COLSPAN=2><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=130"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Drop Lists"); ?> </a> &nbsp; &nbsp; |<?php if ($add_copy_disabled < 1) { ?>
&nbsp; &nbsp; <a href="<?php echo $ADMIN ?>?ADD=131"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Drop List"); ?> </a><?php } ?></TD></TR>
		<?php }
	if (strlen($times_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $times_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=100000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Call Times"); ?> </a> &nbsp;|<?php if ($add_copy_disabled < 1) { ?>
 <a href="<?php echo $ADMIN ?>?ADD=111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Call Time"); ?> </a> &nbsp;|<?php } ?> <a href="<?php echo $ADMIN ?>?ADD=1000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show State Call Times"); ?> </a> &nbsp;|<?php if ($add_copy_disabled < 1) { ?> <a href="<?php echo $ADMIN ?>?ADD=1111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New State Call Time"); ?> </a> &nbsp;|<?php } ?> <a href="<?php echo $ADMIN ?>?ADD=1200000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Holidays"); ?> </a> &nbsp;|<?php if ($add_copy_disabled < 1) { ?> <a href="<?php echo $ADMIN ?>?ADD=1211111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add Holiday"); ?> </a><?php } ?></TD></TR>
		<?php } 
	if (strlen($shifts_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $shifts_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=130000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Shifts"); ?> </a> &nbsp;|<?php if ($add_copy_disabled < 1) { ?> <a href="<?php echo $ADMIN ?>?ADD=131111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Shift"); ?> </a><?php } ?></TD></TR>
		<?php } 
	if (strlen($phones_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $phones_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=10000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Phones"); ?> </a>&nbsp;|<?php if ($add_copy_disabled < 1) { ?>&nbsp;<a href="<?php echo $ADMIN ?>?ADD=11111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Phone"); ?> </a>&nbsp;|<?php } ?><?php if ($add_copy_disabled < 1) { ?>&nbsp;<a href="<?php echo $ADMIN ?>?ADD=12222222222"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy an Existing Phone"); ?> </a>&nbsp;|<?php } ?>&nbsp;<a href="<?php echo $ADMIN ?>?ADD=12000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Phone Alias List"); ?> </a>&nbsp;|<?php if ($add_copy_disabled < 1) { ?>&nbsp;<a href="<?php echo $ADMIN ?>?ADD=12111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Phone Alias"); ?> </a>&nbsp;|<?php } ?>&nbsp;<a href="<?php echo $ADMIN ?>?ADD=13000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Group Alias List"); ?> </a>&nbsp;|<?php if ($add_copy_disabled < 1) { ?>&nbsp;<a href="<?php echo $ADMIN ?>?ADD=13111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Group Alias"); ?> </a><?php } ?></TD></TR>
		<?php }
	if (strlen($conference_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $conference_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=1000000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Conferences"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=1111111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Conference"); ?> </a> &nbsp; |<?php } ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=10000000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show VICIDIAL Conferences"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=11111111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New VICIDIAL Conference"); ?> </a><?php } ?></TD></TR>
		<?php }
	if ( (strlen($server_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $server_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=100000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Servers"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=111111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Server"); ?> </a><?php } ?></TD></TR>
	<?php }
	if ( (strlen($templates_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $templates_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=130000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Templates"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=131111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Template"); ?> </a><?php } ?></TD></TR>
	<?php }
	if ( (strlen($carriers_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $carriers_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=140000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Carriers"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=141111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Carrier"); ?> </a> &nbsp; | &nbsp; <a href="<?php echo $ADMIN ?>?ADD=140111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy A Carrier"); ?> </a><?php } ?></TD></TR>
	<?php }
	if ( (strlen($emails_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $emails_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="admin_email_accounts.php"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Email Accounts"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="admin_email_accounts.php?eact=ADD"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Account"); ?> </a> &nbsp; | &nbsp; <a href="admin_email_accounts.php?eact=COPY"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy An Account"); ?> </a><?php } ?></TD></TR>
	<?php }
	if ( (strlen($tts_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $tts_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=150000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show TTS Entries"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=151111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New TTS Entry"); ?> </a><?php } ?></TD></TR>
	<?php }
	if ( (strlen($cc_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $cc_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="callcard_admin.php"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("CallCard Summary"); ?> </a> &nbsp; | &nbsp; <a href="callcard_admin.php?action=CALLCARD_RUNS"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Runs"); ?> </a> &nbsp; | &nbsp; <a href="callcard_admin.php?action=CALLCARD_BATCHES"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Batches"); ?> </a> &nbsp; | &nbsp; <a href="callcard_admin.php?action=SEARCH"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("CallCard Search"); ?> </a> &nbsp; | &nbsp; <a href="callcard_report_export.php"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("CallCard Log Export"); ?> </a> &nbsp; | &nbsp; <a href="callcard_admin.php?action=GENERATE"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("CallCard Generate New Numbers"); ?> </a></TD></TR>
	<?php }
	if ( (strlen($moh_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $moh_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=160000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show MOH Entries"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=161111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New MOH Entry"); ?> </a><?php } ?></TD></TR>
	<?php }
	if ( (strlen($languages_sh) > 25) and (strlen($admin_hh) > 25) and ($SSenable_languages > 0) ) { 
		?>
	<TR BGCOLOR=<?php echo $languages_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="admin_languages.php?ADD=163000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Languages"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="admin_languages.php?ADD=163111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Language"); ?></FONT></a> &nbsp; | &nbsp; <a href="admin_languages.php?ADD=163211111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy A Languages Entry"); ?></FONT></a> &nbsp; | &nbsp; <a href="admin_languages.php?ADD=163311111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Import Phrases"); ?></FONT></a> &nbsp; | &nbsp; <a href="admin_languages.php?ADD=163411111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Export Phrases"); ?></FONT></a> &nbsp; <?php } ?></TD></TR>
	<?php }
	if ( (preg_match("/soundboard/",$SSactive_modules) ) or ($SSagent_soundboards > 0) )
		{
	if ( (strlen($soundboard_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $soundboard_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="admin_soundboard.php?ADD=162000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Soundboard Entries"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="admin_soundboard.php?ADD=162111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Soundboard Entry"); ?></FONT></a> &nbsp; | &nbsp; <a href="admin_soundboard.php?ADD=162211111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Copy A Soundboard Entry"); ?></FONT></a> &nbsp; <?php } ?></TD></TR>
	<?php
		}
	}
	if ( (strlen($vm_sh) > 25) and (strlen($admin_hh) > 25) ) { 
		?>
	<TR BGCOLOR=<?php echo $vm_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=170000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Show Voicemail Entries"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=171111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A New Voicemail Entry"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($settings_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $settings_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=311111111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("System Settings"); ?> </a></TD></TR>
	<?php }
	if (strlen($label_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $label_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=180000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Screen Labels"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=181111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A Screen Label"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($colors_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $colors_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=182000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Screen Colors"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=182111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A Screen Colors"); ?> </a><?php } ?> &nbsp; | &nbsp; <a href="<?php echo $ADMIN ?>?ADD=311111111111111#screen_colors"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Change Active Screen Colors"); ?> </a></TD></TR>
	<?php }
	if (strlen($cts_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $cts_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=190000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Contacts"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=191111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A Contact"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($sc_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $sc_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=192000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Settings Containers"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=192111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A Settings Container"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($ar_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $ar_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=194000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Automated Reports"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=194111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add An Automated Report"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($il_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $il_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=195000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("IP Lists"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=195111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add An IP List"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($sg_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $sg_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=193000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Status Groups"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=193111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A Status Group"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($cg_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $cg_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=196000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("CID Groups"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=196111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A CID Group"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($vmmg_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $vmmg_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=197000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("VM Message Groups"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=197111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A VM Message Group"); ?> </a><?php } ?></TD></TR>
	<?php }
	if (strlen($qg_sh) > 25) { 
		?>
	<TR BGCOLOR=<?php echo $qg_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=198000000000"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Queue Groups"); ?> </a> &nbsp; |<?php if ($add_copy_disabled < 1) { ?> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=198111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Add A Queue Group"); ?> </a><?php } ?></TD></TR>
	<?php }
	if ( (strlen($status_sh) > 25) and (!preg_match('/campaign|user/i',$hh) ) ) { 
		?>
	<TR BGCOLOR=<?php echo $status_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="<?php echo $ADMIN ?>?ADD=321111111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("System Statuses"); ?> </a> &nbsp; | &nbsp; <a href="<?php echo $ADMIN ?>?ADD=331111111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("Status Categories"); ?> </a> &nbsp; | &nbsp; <a href="<?php echo $ADMIN ?>?ADD=341111111111111"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"> <?php echo _QXZ("QC Status Codes"); ?> </a></TD></TR>
	<?php }

	if ( ($ADD=='3') or ($ADD=='3') ) { 
		?>
	<TR BGCOLOR=<?php echo $users_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="./user_stats.php?user=<?php echo $user ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"><?php echo _QXZ("User Stats"); ?> </a> &nbsp; | &nbsp; <a href="./user_status.php?user=<?php echo $user ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"><?php echo _QXZ("User Status"); ?> </a> &nbsp; | &nbsp; <a href="./AST_agent_time_sheet.php?agent=<?php echo $user ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"><?php echo _QXZ("Time Sheet"); ?> </a> &nbsp; | &nbsp; <a href="./AST_agent_days_detail.php?user=<?php echo $user ?>" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"><?php echo _QXZ("Days Status"); ?> </a></TD></TR>
	<?php }


if ( ($ADD=='999988') or ($ADD=='999987') or ($ADD=='999986') ) 
	{ 
	?>
	<TR BGCOLOR=<?php echo $users_color ?>><TD ALIGN=LEFT COLSPAN=2> &nbsp; <a href="./admin.php?ADD=999988" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"><?php echo _QXZ("Available Timezones"); ?> </a> &nbsp; | &nbsp; <a href="./admin.php?ADD=999987" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"><?php echo _QXZ("Phone Codes"); ?> </a> &nbsp; | &nbsp; <a href="./admin.php?ADD=999986" STYLE="text-decoration:none;"><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;color:BLACK;"><?php echo _QXZ("Postal Codes"); ?> </a> &nbsp; </TD></TR>
	<?php 
	}
else
	{
	if (strlen($reports_hh) > 25) { 
		?>
		<TR BGCOLOR=<?php echo $reports_color ?>><TD ALIGN=LEFT COLSPAN=2><FONT STYLE="font-family:HELVETICA;font-size:<?php echo $subcamp_font_size ?>;"><B> &nbsp; </B></TD></TR>
		<?php } 
	}
?>
<TR><TD ALIGN=LEFT COLSPAN=2 HEIGHT=2 BGCOLOR=#<?php echo "$SSmenu_background" ?>></TD></TR>
<TR><TD ALIGN=LEFT COLSPAN=2>
<?php 
######################### FULL HTML HEADER END #######################################
}
