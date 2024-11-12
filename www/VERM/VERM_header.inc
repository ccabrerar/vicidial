<?php
# VERM_header.inc - Vicidial Enhanced Reporting header output
#
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1621 - First build
# 230106-1321 - Added missing QXZ functions, removed debug output
#

/* 08/26 - redundant, already used in parent VERM_main_report_page.php
$stmt="SELECT admin_home_url,enable_tts_integration,callcard_enabled,custom_fields_enabled,allow_emails,level_8_disable_add,allow_chats,enable_languages,admin_row_click,admin_screen_colors,user_new_lead_limit,user_territories_active,qc_features_active,agent_soundboards,enable_drop_lists,allow_ip_lists,admin_web_directory from system_settings;";
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
$SSadmin_web_directory =	$row[16];
*/

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

$selected_logo = "/".$SSadmin_web_directory."/images/vicidial_admin_web_logo.png";
$selected_small_logo = "/".$SSadmin_web_directory."/images/vicidial_admin_web_logo.png";
$logo_new=0;
$logo_old=0;
$logo_small_old=0;
if (file_exists("/".$SSadmin_web_directory."/images/vicidial_admin_web_logo.png")) {$logo_new++;}
if (file_exists("/".$SSadmin_web_directory."/images/vicidial_admin_web_logo_small.gif")) {$logo_small_old++;}
if (file_exists("/".$SSadmin_web_directory."/images/vicidial_admin_web_logo.gif")) {$logo_old++;}
if ($SSweb_logo=='default_new')
	{
	$selected_logo = "/".$SSadmin_web_directory."/images/vicidial_admin_web_logo.png";
	$selected_small_logo = "/".$SSadmin_web_directory."/images/vicidial_admin_web_logo.png";
	}
if ( ($SSweb_logo=='default_old') and ($logo_old > 0) )
	{
	$selected_logo = "/".$SSadmin_web_directory."/vicidial_admin_web_logo.gif";
	$selected_small_logo = "/".$SSadmin_web_directory."/vicidial_admin_web_logo_small.gif";
	}
if ( ($SSweb_logo!='default_new') and ($SSweb_logo!='default_old') )
	{
	if (file_exists("/".$SSadmin_web_directory."/images/vicidial_admin_web_logo$SSweb_logo")) 
		{
		$selected_logo = "/".$SSadmin_web_directory."/images/vicidial_admin_web_logo$SSweb_logo";
		$selected_small_logo = "/".$SSadmin_web_directory."/images/vicidial_admin_web_logo$SSweb_logo";
		}
	}


$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$HTML_header="<html>\n";
$HTML_header.="<TITLE>"._QXZ("$report_name").": $report_full_names_array[$report_type]</TITLE>\n";
$HTML_header.="<HEAD>\n";
$HTML_header.="<link rel=\"stylesheet\" type=\"text/css\" href=\"VERM_stylesheet.php\">\n";
$HTML_header.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HTML_header.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";
$HTML_header.="<script language=\"JavaScript\" src=\"VERM_custom_form_functions.php\"></script>\n";
$HTML_header.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HTML_header.="<script language=\"JavaScript\" src=\"VERM_functions.js\"></script>\n";
$HTML_header.="</head>\n";
$HTML_header.="<body>\n";
$HTML_header.="<div id='DisplayLinkDiv' class='details_info' style='display:none;z-index:15;'></div>\n";
$HTML_header.="<div id='CallDetailsDiv' class='details_info' style='display:none;z-index:10;'></div>\n";
$HTML_header.="<div id='AgentDetailsDiv' class='details_info' style='display:none;z-index:11;'></div>\n";
$HTML_header.="<div id='OutcomesDetailsDiv' class='details_info' style='display:none;z-index:5;'></div>\n";
$HTML_header.="<div id='HelpDisplayDiv' class='help_info' style='display:none;z-index:99;'></div>";

$HTML_header.="<form action='$PHP_SELF' method='POST'>\n";

echo $HTML_header;

#### VARIABLES FROM OTHER PAGES #####
echo "<input type='hidden' name='vicidial_queue_groups' id='vicidial_queue_groups' value='$vicidial_queue_groups'>\n";
# echo "<input type='hidden' name='report_types' id='report_types' value='$report_types'>\n";
echo "<input type='hidden' name='time_period' id='time_period' value='$time_period'>\n";
echo "<input type='hidden' name='start_date' id='start_date' value='$start_date'>\n";
echo "<input type='hidden' name='start_time_hour' id='start_time_hour' value='$start_time_hour'>\n";
echo "<input type='hidden' name='start_time_min' id='start_time_min' value='$start_time_min'>\n";
echo "<input type='hidden' name='end_date' id='end_date' value='$end_date'>\n";
echo "<input type='hidden' name='end_time_hour' id='end_time_hour' value='$end_time_hour'>\n";
echo "<input type='hidden' name='end_time_min' id='end_time_min' value='$end_time_min'>\n";
echo "<input type='hidden' name='hourly_slot' id='hourly_slot' value='$hourly_slot'>\n";
echo "<input type='hidden' name='SLA_initial_period' id='SLA_initial_period' value='$SLA_initial_period'>\n";
echo "<input type='hidden' name='SLA_initial_interval' id='SLA_initial_interval' value='$SLA_initial_interval'>\n";
echo "<input type='hidden' name='SLA_max_period' id='SLA_max_period' value='$SLA_max_period'>\n";
echo "<input type='hidden' name='SLA_interval' id='SLA_interval' value='$SLA_interval'>\n";
echo "<input type='hidden' name='short_call_wait_limit' id='short_call_wait_limit' value='$short_call_wait_limit'>\n";
echo "<input type='hidden' name='short_call_talk_limit' id='short_call_talk_limit' value='$short_call_talk_limit'>\n";
echo "<input type='hidden' name='short_attempt_wait_limit' id='short_attempt_wait_limit' value='$short_attempt_wait_limit'>\n";
echo "<input type='hidden' name='users' id='users' value='$users'>\n";
echo "<input type='hidden' name='teams' id='teams' value='$teams'>\n";
echo "<input type='hidden' name='location' id='location' value='$location'>\n";
echo "<input type='hidden' name='user_group' id='user_group' value='$user_group'>\n";
echo "<input type='hidden' name='statuses' id='statuses' value='$statuses'>\n";
echo "<input type='hidden' name='asterisk_cid' id='asterisk_cid' value='$asterisk_cid'>\n";
echo "<input type='hidden' name='phone_number' id='phone_number' value='$phone_number'>\n";
echo "<input type='hidden' name='wait_sec_min' id='wait_sec_min' value='$wait_sec_min'>\n";
echo "<input type='hidden' name='wait_sec_max' id='wait_sec_max' value='$wait_sec_max'>\n";
echo "<input type='hidden' name='length_in_sec_min' id='length_in_sec_min' value='$length_in_sec_min'>\n";
echo "<input type='hidden' name='length_in_sec_max' id='length_in_sec_max' value='$length_in_sec_max'>\n";
echo "<input type='hidden' name='disconnection_cause' id='disconnection_cause' value='$disconnection_cause'>\n";
echo "<input type='hidden' name='queue_position_min' id='queue_position_min' value='$queue_position_min'>\n";
echo "<input type='hidden' name='queue_position_max' id='queue_position_max' value='$queue_position_max'>\n";
echo "<input type='hidden' name='call_count_min' id='call_count_min' value='$call_count_min'>\n";
echo "<input type='hidden' name='call_count_max' id='call_count_max' value='$call_count_max'>\n";
echo "<input type='hidden' name='did' id='did' value='$did'>\n";
echo "<input type='hidden' name='ivr_choice' id='ivr_choice' value='$ivr_choice'>\n";
echo "<input type='hidden' name='server' id='server' value='$server'>\n";
echo "<input type='hidden' name='dow' id='dow' value='$dow_str'>\n";
echo "<input type='hidden' name='time_of_day_start' id='time_of_day_start' value='$time_of_day_start'>\n";
echo "<input type='hidden' name='time_of_day_end' id='time_of_day_end' value='$time_of_day_end'>\n";
echo "<input type='hidden' name='download_rpt' id='download_rpt'>\n";
echo "<input type='hidden' name='report_type' id='report_type' value='$report_type'>\n";
echo "<input type='hidden' name='DB' id='DB' value='$DB'>\n";
echo "<input type='hidden' name='report_types_to_display' id='report_types_to_display' value='$report_types_to_display'>\n";
?>

<script src="chart.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<table id="nav_table">
<?php
	echo "<tr>\n";
	echo "<td align='left' valign='top' class='title_cell'><A HREF='VERM_admin.php'><IMG SRC='$selected_logo' BORDER=0 ALT='System logo'></A> &nbsp; </td>\n";
	echo "<td align='right' valign='middle' class='title_cell'><B>$PHP_AUTH_USER</B> | $PHP_AUTH_USER_fullname<BR><BR>";
	echo "<a class='header_link' href='javascript:window.location.reload()'>"._QXZ("RELOAD")." &#8635;</a> | <a class='header_link' href='javascript:if(window.print)window.print()'>"._QXZ("PRINT")." <img src='./images/print_icon.png' width='15' height='15'></a> | <a class='header_link' href='VERM_admin.php?start_date=$start_date&end_date=$end_date'>"._QXZ("NEW REPORT"). "</a> | <a class='header_link' href='/".$SSadmin_web_directory."/admin.php?force_logout=1'>"._QXZ("LOG OUT")."</a>";
	echo "</td>\n";
	echo "</tr>\n";
?>

<?php 
if ($report_types_to_display=="Quick agents report") {
?>
	<tr>
		<td colspan='2' class='nav_header' align='left'>
		<ul class='navigation_list'>
		<li style="display:inline"><input type='button' class='transparent_button' value='<?php echo _QXZ("HOME"); ?>' onClick="document.location.href='VERM_admin.php'"></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="ANSWERED" ? " current_report" : ""); ?>' value='<?php echo _QXZ("ANSWERED"); ?>' id='ANSWERED' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="ANSWERED_DT" ? " current_report" : ""); ?>' value='<?php echo _QXZ("ANS.DT."); ?>' id='ANSWERED_DT' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="AGENTS" ? " current_report" : ""); ?>' value='<?php echo _QXZ("AGENTS"); ?>' id='AGENTS' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="AGENTS_DT" ? " current_report" : ""); ?>' value='<?php echo _QXZ("AG.DT."); ?>' id='AGENTS_DT' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="OUTCOMES" ? " current_report" : ""); ?>' value='<?php echo _QXZ("OUTCOMES"); ?>' id='OUTCOMES' onClick='GoToReport(this.id)'></li>
		</ul>
		</td>
	</tr>
<?php
} else if ($report_types_to_display=="Quick reports")  {
	# Gotta overwrite here
	if (!$report_type || $report_type=="ANSWERED") {$report_type="STATS";}
?>
	<tr>
		<td colspan='2' class='nav_header' align='left'>
		<ul class='navigation_list'>
		<li style="display:inline"><input type='button' class='transparent_button' value='<?php echo _QXZ("HOME"); ?>' onClick="document.location.href='VERM_admin.php'"></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="STATS" ? " current_report" : ""); ?>' value='<?php echo _QXZ("STATS"); ?>' id='STATS' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="CALLS" ? " current_report" : ""); ?>' value='<?php echo _QXZ("CALLS"); ?>' id='CALLS' onClick='GoToReport(this.id)'></li>
		</ul>
		</td>
	</tr>
<?php
} else {
?>
	<tr>
		<td colspan='2' class='nav_header' align='left'>
		<ul class='navigation_list'>
		<li style="display:inline"><input type='button' class='transparent_button' value='<?php echo _QXZ("HOME"); ?>' onClick="document.location.href='VERM_admin.php'"></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="ANSWERED" ? " current_report" : ""); ?>' value='<?php echo _QXZ("ANSWERED"); ?>' id='ANSWERED' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="ANSWERED_DT" ? " current_report" : ""); ?>' value='<?php echo _QXZ("ANS.DT."); ?>' id='ANSWERED_DT' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="UNANSWERED" ? " current_report" : ""); ?>' value='<?php echo _QXZ("UNANS."); ?>' id='UNANSWERED' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="UNANSWERED_DT" ? " current_report" : ""); ?>' value='<?php echo _QXZ("UNANS.DT."); ?>' id='UNANSWERED_DT' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="IVR" ? " current_report" : ""); ?>' value='<?php echo _QXZ("IVR"); ?>' id='IVR' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="AREA" ? " current_report" : ""); ?>' value='<?php echo _QXZ("AREA"); ?>' id='AREA' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="ATT" ? " current_report" : ""); ?>' value='<?php echo _QXZ("ATT."); ?>' id='ATT' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="DAY" ? " current_report" : ""); ?>' value='<?php echo _QXZ("DAY"); ?>' id='DAY' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="HOUR" ? " current_report" : ""); ?>' value='<?php echo _QXZ("HR."); ?>' id='HOUR' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="DOW" ? " current_report" : ""); ?>' value='<?php echo _QXZ("DOW"); ?>' id='DOW' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="AGENTS" ? " current_report" : ""); ?>' value='<?php echo _QXZ("AGENTS"); ?>' id='AGENTS' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="AGENTS_DT" ? " current_report" : ""); ?>' value='<?php echo _QXZ("AG.DT."); ?>' id='AGENTS_DT' onClick='GoToReport(this.id)'></li>
		<li style="display:inline"><input type='button' class='transparent_button<?php echo ($report_type=="OUTCOMES" ? " current_report" : ""); ?>' value='<?php echo _QXZ("OUTCOMES"); ?>' id='OUTCOMES' onClick='GoToReport(this.id)'></li>
		</ul>
		</td>
	</tr>
<?php
} 
?>
</table>

<table id="rpt_table">

	<tr>
		<th style="width:25vw"><?php echo _QXZ("Report Details"); ?>:</th>
		<td style="width:75vw"></td>
	</tr>
<?php
if ($report_full_names_array["$report_type"])
	{
?>
	<tr>
		<td style="width:25vw" class='bold_cell'><?php echo _QXZ("Currently viewing:"); ?></th>
		<td style="width:75vw" class='bold_cell'><?php echo $report_full_names_array["$report_type"]; ?></td>
	</tr>
<?php
	}
?>
	<tr>
		<td style="width:25vw" class='bold_cell'><?php echo _QXZ("Report queue"); ?>:</td>
		<td style="width:75vw" class='bold_cell'>
		<?php
		echo $vicidial_queue_groups;
		?>
		</td>
	</tr>
	<tr>
		<td style="width:25vw"><?php echo _QXZ("Report generated on"); ?>:</td>
		<td style="width:75vw"><?php echo date("F j Y, G:i"); ?></td>
	</tr>
	<tr>
		<td style="width:25vw"><?php echo _QXZ("Campaigns/ingroups considered"); ?>: <?php echo "$NWB#VERM_header-atomic_queues$NWE"; ?></td>
		<td style="width:75vw" class='queue_cell'>
		<?php
		echo $atomic_queue_str;
		?>
		</td>
	</tr>
	<tr>
		<td style="width:25vw"><?php echo _QXZ("Period start date"); ?>: <?php echo "$NWB#VERM_header-period_start_date$NWE"; ?></td>
		<td style="width:75vw"><?php echo date('F j Y, H:i', strtotime("$start_date $start_time")); ?></td>
	</tr>
	<tr>
		<td style="width:25vw"><?php echo _QXZ("Period end date"); ?>: <?php echo "$NWB#VERM_header-period_end_date$NWE"; ?></td>
		<td style="width:75vw"><?php echo date('F j Y, H:i', strtotime("$end_date $end_time")); ?></td>
	</tr>
	<tr>
		<td style="width:25vw"><?php echo _QXZ("Total calls processed"); ?>: <?php echo "$NWB#VERM_header-total_calls_processed$NWE"; ?></td>
		<td style="width:75vw"><?php echo $total_calls." (".$total_answered_calls." ans / ".$total_unanswered_calls." unans)"; ?></td>
	</tr>
	<tr>
		<td style="width:25vw"><?php echo _QXZ("Ratio"); ?>: <?php echo "$NWB#VERM_header-ratio$NWE"; ?></td>
		<td style="width:75vw"><?php
		$answered_percentage=sprintf("%.1f", MathZDC((100*$total_answered_calls), $total_calls));
		$unanswered_percentage=sprintf("%.1f", MathZDC((100*$total_unanswered_calls), $total_calls));
		echo $answered_percentage."% ans / ".$unanswered_percentage."% unans";
		?></td>
	</tr>
	<tr>
		<td style="width:25vw">&nbsp;</td>
		<td style="width:75vw"><input type="button" class="actButton" value="<?php echo _QXZ("Search"); ?>" alt="DIRECTS BACK TO CUSTOM REPORT INTERFACE" onclick="javascript:document.location.href='VERM_custom_report.php'"> <?php echo "$NWB#VERM_header-buttons$NWE"; ?></td>
	</tr>
	<tr>
		<td style="width:25vw">&nbsp;</td>
		<td style="width:75vw"><input type="button" class="actButton" value="<?php echo _QXZ("Link"); ?>" alt="GIVES LINK DIRECTLY TO PAGE" onclick="GenerateLink(event);">
		<input type="button" class="actButton" value="CSV" onClick="DownloadReport('<?php echo $report_type; ?>', 'header')">
		</td>
	</tr>
	<tr class='export_row'>
		<td colspan='2' class='export_row_cell' valign='top'><div class="chart-container" style="height:25vw; width:40vh"><canvas id='answered_unanswered_display' role="img"> </canvas></div>
<!--
		<?php
/*
		echo "<BR><BR>Vicidial log SQL: $vicidial_log_SQL<BR><B>$and_vicidial_log_SQL</b><BR>\n";
		echo "<BR>Vicidial CLOSER log SQL: $vicidial_closer_log_SQL\n";
		echo "<BR>Vicidial DID log SQL: $vicidial_did_log_SQL\n";
		echo "<BR>Vicidial AGENT log SQL: $vicidial_agent_log_SQL\n";
		echo "Users: $and_user_sql<BR>\n";
		# echo "Location: $and_location_sql<BR>\n";
		echo "Team users: $team - $and_team_sql<BR>\n";
		echo "User group: $and_user_group_sql<BR>\n";
		echo "Statuses: $and_status_sql<BR>\n";
		echo "Outbound Phone: $and_phone_number_sql<BR>\n";
		echo "Wait sec: $and_wait_sec_sql<BR>\n";
		echo "Length in sec: $and_length_in_sec_sql<BR>\n";
		echo "Disconnections: $and_term_reason_sql<BR>\n";
		echo "Queue positions: $and_queue_position_sql<BR>\n";
		echo "Called count: $and_called_count_sql<BR>\n";
		# echo "Inbound DID: $and_did_sql<BR>\n";  
		print_r($did_uniqueid_array);
		# echo "Server: $and_server_ip_sql<BR>\n";
		# echo "Asterisk CID: $and_caller_code_sql<BR>\n"; 
		print_r($extended_log_uniqueid_array);
		echo "Day of week: $and_call_date_DOWsql<BR>\n";
*/
		?>
//-->
		</td>
	</tr>
</table>

<script language='Javascript'>
var answered_unanswered_data = {
		labels:["Ans %", "Unans %"],
		datasets: [
			{
				label: "",
				fill: false,
				data: ["<?php echo $answered_percentage; ?>", "<?php echo $unanswered_percentage; ?>"],
				backgroundColor: ["#009900","#990000"],
				hoverBackgroundColor: ["#009900","#990000"],
				hoverBorderColor: ["#00FF00","#FF0000"],
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
				pointBorderColor: "rgba(255,255,255,1)",
				pointBackgroundColor: "#FFF",
				pointHoverBackgroundColor: "rgba(255,255,255,0.75)",
				pointHoverBorderColor: "rgba(255,230,230,1)"
			}
		]
	}
var answered_unanswered_canvas = document.getElementById("answered_unanswered_display");
var answered_unanswered_graph = new Chart(answered_unanswered_canvas, {type: 'doughnut', options: { cutout: "40%", plugins: {legend: { position: 'right' }}},
data: answered_unanswered_data});
</script>

