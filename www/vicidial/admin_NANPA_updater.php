<?php
# admin_NANPA_updater.php
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to launch NANPA filter batch proccesses through the
# triggering process
#
# CHANGELOG:
# 130919-1503 - First build of script
# 131005-2035 - Added exclusion options
# 131019-1112 - Added help text and several small tweaks
# 141007-1123 - Finalized adding QXZ translation to all admin files
# 141230-0014 - Added code for on-the-fly language translations display
# 160108-2300 - Changed some mysqli_query to mysql_to_mysqli for consistency
# 170409-1536 - Added IP List validation code
# 170822-2230 - Added screen color settings
# 180503-2015 - Added new help display
# 191013-0814 - Fixes for PHP7
#

$version = '2.14-8';
$build = '191013-0814';
$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$server_ip=$WEBserver_ip;
$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["submit_form"]))			{$submit_form=$_GET["submit_form"];}
	elseif (isset($_POST["submit_form"]))	{$submit_form=$_POST["submit_form"];}
if (isset($_GET["delete_trigger_id"]))			{$delete_trigger_id=$_GET["delete_trigger_id"];}
	elseif (isset($_POST["delete_trigger_id"]))	{$delete_trigger_id=$_POST["delete_trigger_id"];}
if (isset($_GET["lists"]))			{$lists=$_GET["lists"];}
	elseif (isset($_POST["lists"]))	{$lists=$_POST["lists"];}
if (isset($_GET["fields_to_update"]))			{$fields_to_update=$_GET["fields_to_update"];}
	elseif (isset($_POST["fields_to_update"]))	{$fields_to_update=$_POST["fields_to_update"];}
if (isset($_GET["vl_field_update"]))			{$vl_field_update=$_GET["vl_field_update"];}
	elseif (isset($_POST["vl_field_update"]))	{$vl_field_update=$_POST["vl_field_update"];}
if (isset($_GET["vl_field_exclude"]))			{$vl_field_exclude=$_GET["vl_field_exclude"];}
	elseif (isset($_POST["vl_field_exclude"]))	{$vl_field_exclude=$_POST["vl_field_exclude"];}
if (isset($_GET["exclusion_value"]))			{$exclusion_value=$_GET["exclusion_value"];}
	elseif (isset($_POST["exclusion_value"]))	{$exclusion_value=$_POST["exclusion_value"];}
if (isset($_GET["cellphone_list_id"]))			{$cellphone_list_id=$_GET["cellphone_list_id"];}
	elseif (isset($_POST["cellphone_list_id"]))	{$cellphone_list_id=$_POST["cellphone_list_id"];}
if (isset($_GET["landline_list_id"]))			{$landline_list_id=$_GET["landline_list_id"];}
	elseif (isset($_POST["landline_list_id"]))	{$landline_list_id=$_POST["landline_list_id"];}
if (isset($_GET["invalid_list_id"]))			{$invalid_list_id=$_GET["invalid_list_id"];}
	elseif (isset($_POST["invalid_list_id"]))	{$invalid_list_id=$_POST["invalid_list_id"];}
if (isset($_GET["activation_delay"]))			{$activation_delay=$_GET["activation_delay"];}
	elseif (isset($_POST["activation_delay"]))	{$activation_delay=$_POST["activation_delay"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}

$block_scheduling_while_running=0;

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,active_voicemail_server,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$active_voicemail_server =		$row[4];
	$SSenable_languages =			$row[5];
	$SSlanguage_method =			$row[6];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	}

$NOW_DATE = date("Y-m-d");

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$user_auth=0;
$auth=0;
$reports_auth=0;
$qc_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$user_auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

if ($user_auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 1 and qc_enabled='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$qc_auth=$row[0];

	$reports_only_user=0;
	$qc_only_user=0;
	if ( ($reports_auth > 0) and ($auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	if ( ($qc_auth > 0) and ($reports_auth < 1) and ($auth < 1) )
		{
		if ( ($ADD != '881') and ($ADD != '100000000000000') )
			{
            $ADD=100000000000000;
			}
		$qc_only_user=1;
		}
	if ( ($qc_auth < 1) and ($reports_auth < 1) and ($auth < 1) )
		{
		$VDdisplayMESSAGE = _QXZ("You do not have permission to be here");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	}
else
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

if (strlen($active_voicemail_server)<7)
	{
	echo _QXZ("ERROR: Admin -> System Settings -> Active Voicemail Server is not set")."\n";
	exit;
	}

if ($delete_trigger_id) 
	{
	$delete_stmt="delete from vicidial_process_triggers where trigger_id='$delete_trigger_id'";
	$delete_rslt=mysql_to_mysqli($delete_stmt, $link);
	}

if (!isset($lists)) {$lists=array();}
$list_ct=count($lists);
if ($submit_form=="SUBMIT" && $list_ct>0 && (strlen($vl_field_update)>0 || strlen($cellphone_list_id)>0 || strlen($landline_list_id)>0 || strlen($invalid_list_id)>0) ) 
	{
	for ($i=0; $i<$list_ct; $i++) 
		{
		if ($lists[$i]=="---ALL---") 
			{
			unset($lists);
			#$lists[0]="---ALL---";
			$i=$list_ct;

			### Added to make sure that if ALL are selected, it's all inactives.  There is nothing in the actual NANPA filtering scripts that handle it
			### but it needs to be done
			$j=0;
			$stmt="SELECT list_id from vicidial_lists where active='N' order by list_id asc";
			$rslt=mysql_to_mysqli($stmt, $link);
			while ($row=mysqli_fetch_array($rslt)) 
				{
				$lists[$j]=$row[0];
				$j++;
				}
			}
		}
	$list_ct=count($lists);

	$cellphone_list_id=preg_replace('/[^0-9]/', '', $cellphone_list_id);
	$landline_list_id=preg_replace('/[^0-9]/', '', $landline_list_id);
	$invalid_list_id=preg_replace('/[^0-9]/', '', $invalid_list_id);
	$exclusion_value=preg_replace('/[\'\"\\\\]/', '', $exclusion_value);
	$exclusion_value=preg_replace('/\s/', '\\\\\ ', $exclusion_value);
	

	$options="--user=$PHP_AUTH_USER --pass=$PHP_AUTH_PW ";
	
	$list_id_str="";
	for ($i=0; $i<$list_ct; $i++) 
		{
		$list_id_str.=$lists[$i]."--";
		}
	$list_id_str=substr($list_id_str, 0, -2);
	$options.="--list-id=$list_id_str ";
	
	if (strlen($cellphone_list_id)>0)	{$options.="--cellphone-list-id=$cellphone_list_id ";}
	if (strlen($landline_list_id)>0)	{$options.="--landline-list-id=$landline_list_id ";}
	if (strlen($invalid_list_id)>0)		{$options.="--invalid-list-id=$invalid_list_id ";}
	if (strlen($vl_field_update)>0)		{$options.="--vl-field-update=$vl_field_update ";}
	if (strlen($vl_field_exclude)>0 && strlen($exclusion_value)>0)		{$options.="--exclude-field=$vl_field_exclude --exclude-value=$exclusion_value ";}
	$options=trim($options);

	$uniqueid=date("U").".".rand(1, 9999);
	$ins_stmt="INSERT into vicidial_process_triggers (trigger_id, trigger_name, server_ip, trigger_time, trigger_run, user, trigger_lines) VALUES('NANPA_".$uniqueid."', 'NANPA updater SCREEN', '$active_voicemail_server', now()+INTERVAL $activation_delay MINUTE, '1', '$PHP_AUTH_USER', '/usr/share/astguiclient/nanpa_type_filter.pl --output-to-db $options')";
	$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
	}
header ("Content-type: text/html; charset=utf-8");
if ($SSnocache_admin=='1')
	{
	header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
	header ("Pragma: no-cache");                          // HTTP/1.0
	}


$schedule_stmt="SELECT *, sec_to_time(UNIX_TIMESTAMP(trigger_time)-UNIX_TIMESTAMP(now())) as time_until_execution from vicidial_process_triggers where trigger_name='NANPA updater SCREEN' and user='$PHP_AUTH_USER' and trigger_time>=now()";
$schedule_rslt=mysql_to_mysqli($schedule_stmt, $link);

$running_stmt="SELECT output_code from vicidial_nanpa_filter_log where user='$PHP_AUTH_USER' and status!='COMPLETED' order by output_code asc";
$running_rslt=mysql_to_mysqli($running_stmt, $link);
if (mysqli_num_rows($running_rslt)>0) {
	$iframe_url="";
	while ($run_row=mysqli_fetch_array($running_rslt)) {
		$iframe_url.="&output_codes_to_display[]=".$run_row[0];
	}
}

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
$SSbutton_color='DEDEDE';

$screen_color_stmt="select admin_screen_colors from system_settings";
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

echo "<html>\n";
echo "<head>\n";
echo "<!-- VERSION: $admin_version   BUILD: $build   ADD: $ADD   PHP_SELF: $PHP_SELF-->\n";
echo "<META NAME=\"ROBOTS\" CONTENT=\"NONE\">\n";
echo "<META NAME=\"COPYRIGHT\" CONTENT=\"&copy; 2014 ViciDial Group\">\n";
echo "<META NAME=\"AUTHOR\" CONTENT=\"ViciDial Group\">\n";
if ($SSnocache_admin=='1')
	{
	echo "<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
	echo "<META HTTP-EQUIV=\"Expires\" CONTENT=\"-1\">\n";
	echo "<META HTTP-EQUIV=\"CACHE-CONTROL\" CONTENT=\"NO-CACHE\">\n";
	}
if ( ($SSadmin_modify_refresh > 1) and (preg_match("/^3/",$ADD)) )
	{
	$modify_refresh_set=1;
	if (preg_match("/^3/",$ADD)) {$modify_url = "$PHP_SELF?$QUERY_STRING";}
	echo "<META HTTP-EQUIV=\"REFRESH\" CONTENT=\"$SSadmin_modify_refresh;URL=$modify_url\">\n";
	}
echo "<title>"._QXZ("ADMIN NANPA UPDATER")."</title>";
?>
<script language="Javascript">
function StartRefresh() {
        rInt=window.setInterval(function() {RefreshNANPA("<?php echo $iframe_url; ?>")}, 10000);
}
function RefreshNANPA(spanURL) {
	var xmlhttp=false;
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	if (xmlhttp) {
		var nanpa_URL = "?"+spanURL;
		// alert(nanpa_URL);
		xmlhttp.open('POST', 'NANPA_running_processes.php');
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(nanpa_URL);
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var ProcessSpanText = null;
				ProcessSpanText = xmlhttp.responseText;
				document.getElementById("running_processes").innerHTML = ProcessSpanText;
				delete xmlhttp;
			}
		}
	}
}
function ShowPastProcesses(limit) {
	if (!limit){var limitURL="";} else {var limitURL="&process_limit="+limit;}

	var xmlhttp=false;
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	if (xmlhttp) {
		var nanpa_URL = "&show_history=1"+limitURL;
		// alert(nanpa_URL);
		xmlhttp.open('POST', 'NANPA_running_processes.php');
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(nanpa_URL);
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
				var ProcessSpanText = null;
				ProcessSpanText = xmlhttp.responseText;
				document.getElementById("past_NANPA_scrubs").innerHTML = ProcessSpanText;
				delete xmlhttp;
			}
		}
	}
}
function openNewWindow(url) 
	{
	window.open (url,"",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');
	}

</script>
<?php
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";
echo "</head>\n";
$ADMIN=$PHP_SELF;
$short_header=1;

# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

echo "\n<BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onLoad='RefreshNANPA(\"$iframe_url\"); StartRefresh()'>\n";

require("admin_header.php");

echo "<form action='$PHP_SELF' method='get' enctype='multipart/form-data'>";
echo "<BR> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"$PHP_SELF\">"._QXZ("CLICK HERE TO REFRESH THE PAGE")."</a>\n";
echo "<BR>	<table align=left width='770' border=1 cellpadding=0 cellspacing=0 bgcolor=#".$SSframe_background.">";

if (mysqli_num_rows($schedule_rslt)>0 || (mysqli_num_rows($running_rslt)>0)) {

	if (mysqli_num_rows($schedule_rslt)>0) {
		echo "<tr><td>";
		echo "<table width='770' cellpadding=5 cellspacing=0>";
		echo "<tr><th colspan='5' bgcolor='#".$SSmenu_background."'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2>"._QXZ("Your scheduled NANPA scrubs")." &nbsp; $NWB#nanpa-running$NWE</th></tr>";
		echo "<tr>";
		echo "<td align='left' bgcolor='#".$SSmenu_background."' width='150'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2>"._QXZ("Date/time")."</th>";
		echo "<td align='left' bgcolor='#".$SSmenu_background."' width='300'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2>"._QXZ("Lists")."</th>";
		echo "<td align='left' bgcolor='#".$SSmenu_background."' width='100'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2>"._QXZ("Update field")."</th>";
		echo "<td align='left' bgcolor='#".$SSmenu_background."' width='150'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2>"._QXZ("Conversion lists")."</th>";
		echo "<td align='left' bgcolor='#".$SSmenu_background."' width='70'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2>&nbsp;</th>";
		echo "</tr>";
		while ($row=mysqli_fetch_array($schedule_rslt)) {
			$trigger_array=explode(" ", $row["trigger_lines"]);
			$lists="";
			$vl_update_field="";
			$conversion_lists="";
			for ($q=1; $q<count($trigger_array); $q++) {
				if (preg_match('/--list-id=/', $trigger_array[$q]))
					{
					$data_in=explode("--list-id=", $trigger_array[$q]);
					$lists=trim($data_in[1]);
					$lists=preg_replace('/---/', "", $lists);
					$lists=preg_replace('/--/', ", ", $lists);
					}
				if (preg_match('/--vl-field-update=/', $trigger_array[$q]))
					{
					$data_in=explode("--vl-field-update=", $trigger_array[$q]);
					$vl_update_field=trim($data_in[1]);
					}
				if (preg_match('/--cellphone-list-id=/', $trigger_array[$q]))
					{
					$data_in=explode("--cellphone-list-id=", $trigger_array[$q]);
					$cellphone_list_id=trim($data_in[1]);
					$conversion_lists.=_QXZ("Cellphone list").": $cellphone_list_id<BR>";
					}
				if (preg_match('/--landline-list-id=/', $trigger_array[$q]))
					{
					$data_in=explode("--landline-list-id=", $trigger_array[$q]);
					$landline_list_id=trim($data_in[1]);
					$conversion_lists.=_QXZ("Landline list").": $landline_list_id<BR>";
					}
				if (preg_match('/--invalid-list-id=/', $trigger_array[$q]))
					{
					$data_in=explode("--invalid-list-id=", $trigger_array[$q]);
					$invalid_list_id=trim($data_in[1]);
					$conversion_lists.=_QXZ("Invalid list").": $invalid_list_id<BR>";
					}
			}
			if (strlen($vl_update_field)==0) {$vl_update_field="**"._QXZ("NONE")."**";}
			if (strlen($conversion_lists)==0) {$conversion_lists="**"._QXZ("NONE")."**";}
			echo "<tr>";
			echo "<td align='left'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>$row[trigger_time]</font><BR><FONT FACE=\"ARIAL,HELVETICA\" size='1' color='red'>($row[time_until_execution] "._QXZ("until run time").")</font></td>";
			echo "<td align='left'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>$lists</font></td>";
			echo "<td align='left'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>$vl_update_field</font></td>";
			echo "<td align='left'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>$conversion_lists</font></td>";
			echo "<td align='center'><FONT FACE=\"ARIAL,HELVETICA\" size='1'><a href='$PHP_SELF?delete_trigger_id=$row[trigger_id]'>"._QXZ("DELETE")."</a></font></td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "</td></tr>";
	}

	if (mysqli_num_rows($running_rslt)>0) {
		echo "<tr><td>";
		$iframe_url="NANPA_running_processes.php?";
		while ($run_row=mysqli_fetch_array($running_rslt)) {
			$iframe_url.="output_codes_to_display[]=".$run_row[0];
		}
		#echo "<HR><iframe src='$iframe_url' style='width:100%;background-color:transparent;' scrolling='auto' frameborder='0' allowtransparency='true' width='100%'></iframe>";
		echo "<table width='770' cellpadding=0 cellspacing=0><tr><td>";
		echo "<span id='running_processes' name='running_processes'>";
		echo "</span>";
		echo "</td></tr></table>";

		echo "</td></tr>";
	}
}

############################################

if ( ( (mysqli_num_rows($schedule_rslt)>0) or (mysqli_num_rows($running_rslt)>0) ) and ($block_scheduling_while_running==1) ) 
	{$do_nothing=1;} 
else 
	{
	echo "<tr><td>";

	echo "<table width='770' cellpadding=5 cellspacing=0>";
	echo "<tr><th colspan='5' bgcolor='#".$SSmenu_background."'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2>"._QXZ("NANPA scrub scheduler")." &nbsp; $NWB#nanpa-settings$NWE</th></tr>";
	echo "<tr>";
	echo "<td align='left' valign='top' rowspan='4'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Inactive lists").":<BR/>\n";

	$stmt="SELECT list_id, list_name from vicidial_lists where active='N' order by list_id asc";
	$rslt=mysql_to_mysqli($stmt, $link);
	echo "<select name='lists[]' multiple size='5'>\n";
	echo "<option value='---ALL---'>---"._QXZ("ALL LISTS")."---</option>\n";
	while ($row=mysqli_fetch_array($rslt)) 
		{
		echo "<option value='$row[0]'>$row[0] - $row[1]</option>\n";
		}

	echo "</select></font>";
	echo "</td>";
	echo "<td align='left' valign='top' rowspan='2'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Field to update (optional)").":<BR/>\n";
	echo "<select name='vl_field_update'>\n";
	$stmt="SELECT * from vicidial_list limit 1";
	$rslt=mysql_to_mysqli($stmt, $link);
	echo "<option value=''>---"._QXZ("NONE")."---</option>\n";
	while ($fieldinfo=mysqli_fetch_field($rslt)) 
		{
		$fieldname=$fieldinfo->name;
		if (!preg_match("/lead_id|list_id|status|gmt_offset_now|entry_date|modify_date|gender|entry_list_id|date_of_birth|called_since_last_reset|called_count/",$fieldname))
			{
			echo "<option value='$fieldname'>$fieldname</option>\n";
			}
		}
	echo "</select></font></td>";

	echo "<td align='left' valign='top' colspan='2'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("List conversions (optional)").":</font></td>\n";

	echo "<td align='left' valign='top' rowspan='4'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Time until activation").":<BR/>\n";
	echo "<select name='activation_delay'>\n";
	echo "<option value='1'>1 "._QXZ("mins")."</option>\n";
	echo "<option SELECTED value='5'>5 "._QXZ("mins")."</option>\n";
	echo "<option value='10'>10 "._QXZ("mins")."</option>\n";
	echo "<option value='15'>15 "._QXZ("mins")."</option>\n";
	echo "<option value='20'>20 "._QXZ("mins")."</option>\n";
	echo "<option value='30'>30 "._QXZ("mins")."</option>\n";
	echo "<option value='45'>45 "._QXZ("mins")."</option>\n";
	echo "<option value='60'>1 "._QXZ("hour")."</option>\n";
	echo "<option value='120'>2 "._QXZ("hours")."</option>\n";
	echo "<option value='180'>3 "._QXZ("hours")."</option>\n";
	echo "<option value='240'>4 "._QXZ("hours")."</option>\n";
	echo "<option value='480'>8 "._QXZ("hours")."</option>\n";
	echo "</select></font></td>";
	
	echo "</tr>\n";
	echo "<tr>";
	echo "<td align='right'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Cellphone").":</font></td><td align='left'><input type='text' name='cellphone_list_id' size='5' maxlength='10'></td>";
	echo "</tr>";
	echo "<tr>";


	echo "<td align='left' valign='top' rowspan='2'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Exclusion field (optional)").":<BR/>\n";
	echo "<select name='vl_field_exclude'>\n";
	$stmt="SELECT * from vicidial_list limit 1";
	$rslt=mysql_to_mysqli($stmt, $link);
	echo "<option value=''>---"._QXZ("NONE")."---</option>\n";
	while ($fieldinfo=mysqli_fetch_field($rslt)) 
		{
		$fieldname=$fieldinfo->name;
		echo "<option value='$fieldname'>$fieldname</option>\n";
		}
	echo "</select><BR/><BR/>"._QXZ("Exclusion value").":<BR/><input type='text' name='exclusion_value' size='20' maxlength='50'></font></td>";



	echo "<td align='right'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Landline").":</font></td><td align='left'><input type='text' name='landline_list_id' size='5' maxlength='10'></td>";
	echo "</tr>";
	echo "<tr><td align='right'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>"._QXZ("Invalid").":</font></td><td align='left'><input type='text' name='invalid_list_id' size='5' maxlength='10'></td></tr>";
	echo "";
	echo "<tr><td align='center' colspan='5'><input style='background-color:#$SSbutton_color' type='submit' value='"._QXZ("SUBMIT")."' name='submit_form'></td></tr>";
	echo "</table>";

	echo "</td></tr>";
	}
echo "<tr><td>";
echo "<table width='770' cellpadding=0 cellspacing=0 bgcolor='#".$SSstd_row5_background."'>";
echo "<tr><td align='center'>";
echo "<span id='past_NANPA_scrubs'><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=1><a name='past_scrubs' href='#past_scrubs' onClick='ShowPastProcesses(10)'>"._QXZ("View past scrubs")." &nbsp; $NWB#nanpa-log$NWE</font></span>";
echo "</td></tr>";
echo "</table>";
echo "</td></tr>";

echo "</table>";
echo "</form>";
echo "</body>";
echo "</html>";
?>
