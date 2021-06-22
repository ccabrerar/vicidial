<?php
# KHOMP_admin.php - KHOMP carrier definition administration page
#
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# Interface for defining KHOMP codes for the dialer
#
# 200210-1604 - First build
# 

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$page_width='770';
$section_width='750';

$Msubhead_color ='#E6E6E6';
$Mselected_color ='#C6C6C6';
$Mhead_color ='#A3C3D6';
$Mmain_bgcolor ='#015B91';

require("screen_colors.php");
/*
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
$SSweb_logo='default_old';

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
*/

$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;

###


$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$QUERY_STRING = getenv("QUERY_STRING");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);


if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["submit_khomp"]))				{$submit_khomp=$_GET["submit_khomp"];}
	elseif (isset($_POST["submit_khomp"]))	{$submit_khomp=$_POST["submit_khomp"];}
if (isset($_GET["new_conclusion"]))				{$new_conclusion=$_GET["new_conclusion"];}
	elseif (isset($_POST["new_conclusion"]))	{$new_conclusion=$_POST["new_conclusion"];}
if (isset($_GET["new_pattern"]))				{$new_pattern=$_GET["new_pattern"];}
	elseif (isset($_POST["new_pattern"]))	{$new_pattern=$_POST["new_pattern"];}
if (isset($_GET["new_action"]))				{$new_action=$_GET["new_action"];}
	elseif (isset($_POST["new_action"]))	{$new_action=$_POST["new_action"];}
if (isset($_GET["new_status"]))				{$new_status=$_GET["new_status"];}
	elseif (isset($_POST["new_status"]))	{$new_status=$_POST["new_status"];}
if (isset($_GET["new_dialstatus"]))				{$new_dialstatus=$_GET["new_dialstatus"];}
	elseif (isset($_POST["new_dialstatus"]))	{$new_dialstatus=$_POST["new_dialstatus"];}
if (isset($_GET["update_conclusion"]))				{$update_conclusion=$_GET["update_conclusion"];}
	elseif (isset($_POST["update_conclusion"]))	{$update_conclusion=$_POST["update_conclusion"];}
if (isset($_GET["update_pattern"]))				{$update_pattern=$_GET["update_pattern"];}
	elseif (isset($_POST["update_pattern"]))	{$update_pattern=$_POST["update_pattern"];}
if (isset($_GET["update_action"]))				{$update_action=$_GET["update_action"];}
	elseif (isset($_POST["update_action"]))	{$update_action=$_POST["update_action"];}
if (isset($_GET["update_status"]))				{$update_status=$_GET["update_status"];}
	elseif (isset($_POST["update_status"]))	{$update_status=$_POST["update_status"];}
if (isset($_GET["update_dialstatus"]))				{$update_dialstatus=$_GET["update_dialstatus"];}
	elseif (isset($_POST["update_dialstatus"]))	{$update_dialstatus=$_POST["update_dialstatus"];}

$SQLdate = date("Y-m-d H:i:s");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

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
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 8 and api_only_user != '1' and modify_statuses='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$auth=$row[0];

	if ( ($qc_auth < 1) and ($reports_auth < 1) and ($auth < 1) )
		{
		$VDdisplayMESSAGE = _QXZ("You do not have permission to be here");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	}

if ($submit_khomp=="UPDATE") 
	{
	$update_action=trim(preg_replace('/[^a-zA-Z]/', "", $update_action));
	$update_status=trim(strtoupper(preg_replace('/[^0-9a-zA-Z]/', "", $update_status)));
	$update_dialstatus=trim(strtoupper(preg_replace('/[^0-9a-zA-Z]/', "", $update_dialstatus)));
	if($update_conclusion) 
		{
		$update_khomp_string="\"$update_conclusion\"";
		if ($update_pattern) {
			$update_khomp_string.=".\"".$update_pattern."\"";
		}
		$update_khomp_string.=" => ";
		$pattern_to_match=$update_khomp_string;
		$pattern_to_match=trim(preg_replace('/\//', "\/", $pattern_to_match));
		if ($update_action) 
			{
			$update_khomp_string.=$update_action;
			if ($update_status) 
				{
				$update_khomp_string.=".$update_status";
				if ($update_dialstatus) 
					{
					$update_khomp_string.=".$update_dialstatus";
					}
				}
			}

		$update_khomp_string=trim(preg_replace('/\//', "\/", $update_khomp_string));
		$stmt="select * from vicidial_settings_containers where container_id='KHOMPSTATUSMAP'";
		$rslt=mysql_to_mysqli($stmt, $link);

		while($row=mysqli_fetch_row($rslt)) 
			{
			$container_entry=$row[4];
			$khomps_array=explode("\n", $container_entry);
			#echo "$pattern_to_match\n";
			#print_r($khomps_array);
			$matches=array_values(preg_grep("/^$pattern_to_match/", $khomps_array));
			#print_r($matches);
			if (count($matches)==0) 
				{
				$upd_rslt="<font color='red'>** UPDATE FAILED - KHOMP CODE DOES NOT EXIST IN SETTINGS CONTAINERS **</font>";				
				}
			else if (count($matches)==1) 
				{
				$string_to_replace=$matches[0];
				$string_to_replace=trim(preg_replace('/\//', "\/", $string_to_replace));
				$container_entry=preg_replace("/$string_to_replace/", "$update_khomp_string", $container_entry);
				$update_stmt="UPDATE vicidial_settings_containers set container_entry='".mysqli_escape_string($link, $container_entry)."' where container_id='KHOMPSTATUSMAP'";
				#echo "<BR>\n$update_stmt";
				$update_rslt=mysql_to_mysqli($update_stmt, $link);
				if(mysqli_affected_rows($link)>0) 
					{
					$upd_rslt="<font color='blue'>** KHOMP CODE SUCCESSFULLY UPDATED **</font>";

					$SQL_log = "$update_stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='KHOMP', event_type='UPDATE', record_id='$PN[$p]', event_code='ADMIN UPDATE KHOMP CODE', event_sql=\"$SQL_log\", event_notes='';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					}
				else
					{
					$upd_rslt="<font color='red'>** CODE NOT UPDATED - QUERY ERROR **</font>";
					}
				}
			else
				{
				$upd_rslt="<font color='red'>** UPDATE FAILED - KHOMP CODE MATCHED MULTIPLE ENTRIES **</font>";				
				}
			}
		}
	} 
else if ($submit_khomp=="ADD") 
	{
	$new_conclusion=trim(preg_replace('/[^- \_0-9a-zA-Z]/', "", $new_conclusion));
	$new_pattern=trim(preg_replace('/[^- \_0-9a-zA-Z]/', "", $new_pattern));
	$new_action=trim(preg_replace('/[^a-zA-Z]/', "", $new_action));
	$new_status=trim(strtoupper(preg_replace('/[^0-9a-zA-Z]/', "", $new_status)));
	$new_dialstatus=trim(strtoupper(preg_replace('/[^0-9a-zA-Z]/', "", $new_dialstatus)));

	if($new_conclusion) 
		{
		$new_khomp_string="\"$new_conclusion\"";
		if ($new_pattern) {$new_khomp_string.=".\"$new_pattern\"";}
		$new_khomp_string.=" => ";
		$pattern_to_match=$new_khomp_string;
		if ($new_action) 
			{
			$new_khomp_string.=$new_action;
			if ($new_status) 
				{
				$new_khomp_string.=".$new_status";
				if ($new_dialstatus) 
					{
					$new_khomp_string.=".$new_dialstatus";
					}
				}
			}


		$stmt="select * from vicidial_settings_containers where container_id='KHOMPSTATUSMAP'";
		$rslt=mysql_to_mysqli($stmt, $link);

		while($row=mysqli_fetch_row($rslt)) 
			{
			$container_entry=$row[4];
			$khomps_array=explode("\n", $container_entry);
			# echo "$pattern_to_match\n";
			# print_r($khomps_array);
			$matches=preg_grep("/^$pattern_to_match/i", $khomps_array);
			# print_r($matches);
			if (count($matches)>0) 
				{
				$upd_rslt="<font color='red'>** CANNOT ADD CODE - KHOMP CODE ALREADY EXISTS IN SETTINGS CONTAINERS **</font>";
				}
			else
				{
				$new_container_entry=$container_entry."\r\n".$new_khomp_string;
				$update_stmt="UPDATE vicidial_settings_containers set container_entry='".mysqli_escape_string($link, $new_container_entry)."' where container_id='KHOMPSTATUSMAP'";
				$update_rslt=mysql_to_mysqli($update_stmt, $link);
				if(mysqli_affected_rows($link)>0) 
					{
					$upd_rslt="<font color='blue'>** NEW KHOMP CODE SUCCESSFULLY ADDED **</font>";

					$SQL_log = "$update_stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='KHOMP', event_type='ADD', record_id='$PN[$p]', event_code='ADMIN ADD KHOMP CODE', event_sql=\"$SQL_log\", event_notes='';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					}
				else
					{
					$upd_rslt="<font color='red'>** CODE NOT ADDED - QUERY ERROR **</font>";
					}
				}
			}
		}
}

######################################################################################################
######################################################################################################
#######   Header settings
######################################################################################################
######################################################################################################


header ("Content-type: text/html; charset=utf-8");
if ($SSnocache_admin=='1')
	{
	header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
	header ("Pragma: no-cache");                          // HTTP/1.0
	}
echo "<html>\n";
echo "<head>\n";
echo "<title>"._QXZ("KHOMP code administration page")."</title>\n";
echo "<!-- VERSION: $admin_version   BUILD: $build   ADD: $ADD   PHP_SELF: $PHP_SELF-->\n";
echo "<META NAME=\"ROBOTS\" CONTENT=\"NONE\">\n";
echo "<META NAME=\"COPYRIGHT\" CONTENT=\"&copy; 2019 ViciDial Group\">\n";
echo "<META NAME=\"AUTHOR\" CONTENT=\"ViciDial Group\">\n";
echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "</head>\n";
?>
<script language="JavaScript">
function ClearKhompCode() {
	document.getElementById("update_conclusion_div").innerHTML="";
	document.getElementById("update_pattern_div").innerHTML="";
	document.getElementById("update_conclusion").value="";
	document.getElementById("update_pattern").value="";
	document.getElementById("update_action").value="";
	document.getElementById("update_status").value="";
	document.getElementById("update_dialstatus").value="";
	document.getElementById("KhompDiv").style.display="none";
}
function ModifyKhompCode(e, update_conclusion, update_pattern, update_action, update_status, update_dialstatus) {
	if (IE) { // grab the x-y pos.s if browser is IE
		tempX = event.clientX + document.body.scrollLeft+250-<?php echo $section_width; ?>;
		tempY = event.clientY + document.body.scrollTop;
	} else {  // grab the x-y pos.s if browser is NS
		tempX = e.pageX-<?php echo $section_width; ?>+250; 
		tempY = e.pageY;
	}  
	// catch possible negative values in NS4
	if (tempX < 0){tempX = 0}
	if (tempY < 0){tempY = 0}  
	// show the position values in the form named Show
	// in the text fields named MouseX and MouseY

	tempX+=20;

	document.getElementById("KhompDiv").style.display="block";
	document.getElementById("KhompDiv").style.left = tempX + "px";
	document.getElementById("KhompDiv").style.top = tempY + "px";

		
	document.getElementById("update_conclusion_div").innerHTML=update_conclusion;
	document.getElementById("update_pattern_div").innerHTML=update_pattern;
	document.getElementById("update_conclusion").value=update_conclusion;
	document.getElementById("update_pattern").value=update_pattern;
	document.getElementById("update_action").value=update_action;
	document.getElementById("update_status").value=update_status;
	document.getElementById("update_dialstatus").value=update_dialstatus;
}
</script>
<?php
echo "<body>";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";


echo "<div id='KhompDiv' class='help_info' style='display:none'>";
echo "<form action='$PHP_SELF' method='get'>";
echo "$upd_stmt";
echo "<TABLE class='help_td' width=500 style='table-layout: fixed;'>";
echo "<tr>";
echo "<th bgcolor=black rowspan='2' align='center' valign='center'><font class='help_bold' color='white'>KHOMP</th>";
echo "<td>Conclusion:</td><td align='left' NOWRAP><div id='update_conclusion_div'></div><input type='hidden' name='update_conclusion' id='update_conclusion'></font></td>";
echo "</tr>";
echo "<tr>";
echo "<td >"._QXZ("Pattern").":</td><td align='left'><div id='update_pattern_div' style='word-wrap:break-word;'></div><input type='hidden' name='update_pattern' id='update_pattern'></font></td>";
echo "</tr>";
echo "<tr>";
echo "<th bgcolor=black rowspan='3' align='center' valign='center'><font class='help_bold' color='white'>VICIDIAL</th>";
# echo "<td NOWRAP>Action:</td><td align='left'><input type='text' name='update_action' id='update_action' class='form_field' size='10' maxlength='20'></td>";
echo "<td NOWRAP>"._QXZ("Action").":</td><td align='left'><select name='update_action' id='update_action' class='form_field' size='1'><option value=''></option><option value='amdaction'>amdaction</option><option value='cpdunknown'>cpdunknown</option><option value='route'>route</option><option value='status'>status</option></select></td>";
echo "</tr>";
echo "<tr>";
echo "<td NOWRAP>"._QXZ("Status").":</td><td align='left'><input type='text' name='update_status' id='update_status' class='form_field' size='10' maxlength='20'></td>";
echo "</tr>";
echo "<tr>";
# echo "<td NOWRAP>Dial Status:</td><td align='left'><input type='text' name='update_dialstatus' id='update_dialstatus' class='form_field' size='10' maxlength='20'></td>";
echo "<td NOWRAP>"._QXZ("Dial Status").":</td><td align='left'><select name='update_dialstatus' id='update_dialstatus' class='form_field' size='1'><option value=''></option><option value='AMD'>AMD</option><option value='ANSWER'>ANSWER</option><option value='BUSY'>BUSY</option><option value='CANCEL'>CANCEL</option><option value='CHANUNAVAIL'>CHANUNAVAIL</option><option value='CONGESTION'>CONGESTION</option><option value='DISCONNECT'>DISCONNECT</option><option value='DONTCALL'>DONTCALL</option><option value='FAX'>FAX</option><option value='INVALIDARGS'>INVALIDARGS</option><option value='NOANSWER'>NOANSWER</option><option value='TORTURE'>TORTURE</option></td>";
echo "</tr>";
echo "<tr>";
echo "<td colspan='3' align='center'><input style='background-color:#$SSbutton_color' type='submit' name='submit_khomp' value='"._QXZ("UPDATE")."'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input style='background-color:#$SSbutton_color' type='button' onClick=\"ClearKhompCode()\" value='"._QXZ("CANCEL")."'></td>";
echo "</tr>";
echo "</table>";
echo "</form>";
echo "</div>";


echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";

$no_title=1;
$ADMIN="admin.php";
require("admin_header.php");



if (!$query_date) {$query_date="2001-01-01";}
if (!$end_date) {$end_date=date("Y-m-d");}


$stmt="select * from vicidial_settings_containers where container_id='KHOMPSTATUSMAP'";
$rslt=mysql_to_mysqli($stmt, $link);

$khomps=array();
$blank_khomps=array();
while($row=mysqli_fetch_row($rslt)) 
	{
	$container_entry=$row[4];
	$khomps_array=explode("\n", $container_entry);
	for ($i=0; $i<count($khomps_array); $i++) 
		{
		$current_khomp=explode("=>", $khomps_array[$i]);
		if (strlen(trim($current_khomp[1]))>0) 
			{
			$khomps[trim($current_khomp[0])]=trim($current_khomp[1]);
			}
		else
			{
			$blank_khomps[trim($current_khomp[0])]=trim($current_khomp[1]);
			}
		}
	}

$khomp_keys=array();
$khomp_vals=array();
foreach($khomps as $key => $value) 
	{
	$khomp_keys[]=$key;
	$khomp_vals[]=$value;
	}
array_multisort($khomp_keys, SORT_ASC, $khomp_vals, SORT_ASC, $khomps);

$blank_khomp_keys=array();
$blank_khomp_vals=array();
foreach($blank_khomps as $key => $value) 
	{
	$blank_khomp_keys[]=$key;
	$blank_khomp_vals[]=$value;
	}
array_multisort($blank_khomp_keys, SORT_ASC, $blank_khomp_vals, SORT_ASC, $blank_khomps);

# print_r($khomps);

	echo "<B>$upd_rslt</B>";

	echo "<TABLE><TR><TD>\n";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";

	if (file_exists('options.php'))
		{require('options.php');}

	$td_max_width=round($section_width*0.33);
	$td_max_width2=round($section_width*0.15);
	$td_max_width3=round($section_width*0.12);

	$camp_group_SQL = $LOGadmin_viewable_groupsSQL;
	if (strlen($whereLOGallowed_campaignsSQL) < 6)
		{$camp_group_SQL = $whereLOGadmin_viewable_groupsSQL;}
	$stmt="SELECT campaign_id,campaign_name,active,dial_method,auto_dial_level,lead_order,dial_statuses,user_group from vicidial_campaigns $whereLOGallowed_campaignsSQL $camp_group_SQL order by campaign_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$campaigns_to_print = mysqli_num_rows($rslt);

	echo "<form action='$PHP_SELF' method='get'>";
	echo "<center><TABLE width=$section_width cellspacing=0 cellpadding=1>\n"; //  style='table-layout: fixed;'
	echo "<tr bgcolor=black>";
	echo "<th NOWRAP colspan=3><font size=2 color=white>KHOMP</font></th>";
	echo "<th NOWRAP colspan=3><font size=2 color=white>VICIDIAL</font></th>";
	echo "<td NOWRAP>&nbsp;</td>";
	echo "</tr>";

	echo "<tr bgcolor=black>";
	echo "<td NOWRAP width='$td_max_width2'><font size=1 color=white align=left><B>"._QXZ("CONCLUSION")."</B></font></td>";
	echo "<td width='$td_max_width' NOWRAP><font size=1 color=white align=left><B>"._QXZ("PATTERN")."</B></font></td>";
	echo "<td NOWRAP width='*'>&nbsp;</td>";
	echo "<td NOWRAP width='$td_max_width3'><font size=1 color=white align=left><B>"._QXZ("ACTION")." &nbsp; </B></font></td>";
	echo "<td NOWRAP width='$td_max_width3'><font size=1 color=white align=left><B>"._QXZ("STATUS")." &nbsp; </B></font></td>";
	echo "<td NOWRAP width='$td_max_width3'><font size=1 color=white align=left><B>"._QXZ("DIAL STATUS")." &nbsp; </B></font></td>";
	echo "<td align=center NOWRAP width='$td_max_width3'><font size=1 color=white><B>"._QXZ("MODIFY")."</B></font></td></tr>\n";

	$o=0; $p=0;
	foreach($blank_khomps as $key => $value)
		{
		if (preg_match('/1$|3$|5$|7$|9$/i', $p))
			{$bgcolor='class="records_list_x"';} 
		else
			{$bgcolor='class="records_list_y"';}
		$key=preg_replace('/\"/', "", $key);
		$value=preg_replace('/\"/', "", $value);
		$kvalues=explode(".", $key);
		$vvalues=explode(".", $value);
		echo "<tr $bgcolor>";
		echo "<td width='$td_max_width2'><font size=1 color=black align=left>".$kvalues[0]."</font></td>";
		echo "<td class='text_overflow' width='$td_max_width' style='max-width: ".$td_max_width."px'><font size=1 color=black align=left>".$kvalues[1]."</td>";
		echo "<th width='*'><font size=1 color=black align=center><B>=></B></font></th>";
		echo "<td width='$td_max_width3'><font size=1 color=black align=left>".$vvalues[0]."</font></td>";
		echo "<td width='$td_max_width3'><font size=1 color=black align=left>".$vvalues[1]."</font></td>";
		echo "<td width='$td_max_width3'><font size=1 color=black align=left>".$vvalues[2]."</font></td>";
		echo "<td align='center' width='$td_max_width3'><font size=1 color=black align=left><input style='background-color:#$SSbutton_color' type='button' value='"._QXZ("MODIFY")."' onClick=\"ModifyKhompCode(event, '".$kvalues[0]."', '".$kvalues[1]."', '".$vvalues[0]."', '".$vvalues[1]."', '".$vvalues[2]."')\"</td>";
		echo "</tr>";
		$p++;
		}

	foreach($khomps as $key => $value)
		{
		if (preg_match('/1$|3$|5$|7$|9$/i', $p))
			{$bgcolor='class="records_list_x"';} 
		else
			{$bgcolor='class="records_list_y"';}
		$key=preg_replace('/\"/', "", $key);
		$value=preg_replace('/\"/', "", $value);
		$kvalues=explode(".", $key);
		$vvalues=explode(".", $value);
		echo "<tr $bgcolor>";
		echo "<td width='$td_max_width2'><font size=1 color=black align=left>".$kvalues[0]."</font></td>";
		echo "<td class='text_overflow' width='$td_max_width' style='max-width: ".$td_max_width."px'><font size=1 color=black align=left>".$kvalues[1]."</font></td>";
		echo "<th width='*'><font size=1 color=black align=center><B>=></B></font></th>";
		echo "<td width='$td_max_width3'><font size=1 color=black align=left>".$vvalues[0]."</font></td>";
		echo "<td width='$td_max_width3'><font size=1 color=black align=left>".$vvalues[1]."</font></td>";
		echo "<td width='$td_max_width3'><font size=1 color=black align=left>".$vvalues[2]."</font></td>";
		echo "<td align='center' width='$td_max_width3'><font size=1 color=black align=left><input style='background-color:#$SSbutton_color' type='button' value='"._QXZ("MODIFY")."' onClick=\"ModifyKhompCode(event, '".$kvalues[0]."', '".$kvalues[1]."', '".$vvalues[0]."', '".$vvalues[1]."', '".$vvalues[2]."')\"</td>";
		echo "</tr>";
		$p++;
		}

	if (preg_match('/1$|3$|5$|7$|9$/i', $p))
		{$bgcolor='class="records_list_x"';} 
	else
		{$bgcolor='class="records_list_y"';}
	echo "<tr bgcolor=black>";
	echo "<th NOWRAP colspan=7><font size=2 color=white>** "._QXZ("ADD NEW KHOMP DEFINITION")." **</font></th>";
	echo "</tr>";
	echo "<tr $bgcolor>";
	echo "<td NOWRAP width='$td_max_width2'><input type='text' name='new_conclusion' class='form_field' size='15' maxlength='100'></td>";
	echo "<td NOWRAP width='$td_max_width'><input type='text' name='new_pattern' class='form_field' size='40' maxlength='300'></td>";
	echo "<th NOWRAP width='*'><font size=1 color=black><B>=></B></font></th>";
	echo "<td NOWRAP width='$td_max_width3'><input type='text' name='new_action' class='form_field' size='10' maxlength='20'></td>";
	echo "<td NOWRAP width='$td_max_width3'><input type='text' name='new_status' class='form_field' size='10' maxlength='20'></td>";
	echo "<td NOWRAP width='$td_max_width3'><input type='text' name='new_dialstatus' class='form_field' size='10' maxlength='20'></td>";
	echo "<td NOWRAP align='center' width='$td_max_width3'><input style='background-color:#$SSbutton_color' type='submit' name='submit_khomp' value='"._QXZ("ADD")."'></td>";
	echo "</tr>";
	
	echo "</table></form>";


echo "</body></html>";
?>


