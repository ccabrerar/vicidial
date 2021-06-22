<?php
# Vicidial_international_DNC_interface.php - version 2.14
# 
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
#
# ViciDial web-based DNC file loader from formatted file
# 
# CHANGES:
# 200813-1230 - First version 
#

require("dbconnect_mysqli.php");
require("functions.php");

if (file_exists('options.php'))
	{
	require('options.php');
	}

$US='_';
$MT[0]='';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["file_update_str"]))	{$file_update_str=$_GET["file_update_str"];}
	elseif (isset($_POST["file_update_str"]))	{$file_update_str=$_POST["file_update_str"];}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,admin_web_directory,custom_fields_enabled,webroot_writable,enable_languages,language_method,active_modules,admin_screen_colors,web_loader_phone_length,enable_international_dncs FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$admin_web_directory =			$row[1];
	$custom_fields_enabled =		$row[2];
	$webroot_writable =				$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	$SSactive_modules =				$row[6];
	$SSadmin_screen_colors =		$row[7];
	$SSweb_loader_phone_length =	$row[8];
	$SSenable_international_dncs =	$row[9];
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
$list_id_override = preg_replace('/[^0-9]/','',$list_id_override);
$web_loader_phone_length = preg_replace('/[^0-9]/','',$web_loader_phone_length);

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_datetime = $STARTtime;
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
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

$stmt="SELECT load_leads,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGload_leads =	$row[0];
$LOGuser_group =	$row[1];

if (!$SSenable_international_dncs)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("Feature is currently disabled")."\n";
	exit;
	}

if ($LOGload_leads < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to load DNC leads")."\n";
	exit;
	}

if (preg_match("/;|:|\/|\^|\[|\]|\"|\'|\*/",$LF_orig))
	{
	echo _QXZ("ERROR: Invalid File Name").":: $LF_orig\n";
	exit;
	}

if ($file_update_str)
	{
	$upd_array=explode("|", $file_update_str);
	if ($upd_array[4]) {$status_clause="file_status='$upd_array[4]', ";}
	$upd_stmt="update vicidial_country_dnc_queue set $status_clause country_code='$upd_array[1]', file_action='$upd_array[2]', file_layout='$upd_array[3]' where dnc_file_id='$upd_array[0]'";
	$upd_rslt=mysql_to_mysqli($upd_stmt, $link);

	### LOG INSERTION Admin Log Table ###
	$log_stmt="INSERT INTO vicidial_admin_log set event_date=now(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='$upd_array[4]', record_id='$upd_array[0]', event_code='ADMIN LOAD DNC LIST', event_sql='', event_notes='DNC file ID: $upd_array[0]| File status: $upd_array[4]| File action: $upd_array[2]| File layout: $upd_array[3]";
	$log_rslt=mysql_to_mysqli($log_stmt, $link);
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
$Mhead_color =	$SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =	$SSstd_row5_background;

#if ($DB) {print "SEED TIME  $secX      :   $year-$mon-$mday $hour:$min:$sec  LOCAL GMT OFFSET NOW: $LOCAL_GMT_OFF\n";}

header ("Content-type: text/html; charset=utf-8");

echo "<html>\n";
echo "<head>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<!-- VERSION: $version     BUILD: $build -->\n";
echo "<!-- SEED TIME  $secX:   $year-$mon-$mday $hour:$min:$sec  LOCAL GMT OFFSET NOW: $LOCAL_GMT_OFF  DST: $isdst -->\n";

$layout_stmt="select container_entry from vicidial_settings_containers where container_id='DNC_IMPORT_FORMATS'";
$layout_rslt=mysql_to_mysqli($layout_stmt, $link);

$layouts=array();

while($layout_row=mysqli_fetch_row($layout_rslt)) 
	{
	$container_entry=$layout_row[0];
	$layout_rows=explode("\n", $container_entry);
	for ($i=0; $i<count($layout_rows); $i++) 
		{
		if (!preg_match('/^\#/', $layout_rows[$i]) && preg_match('/\=\>/', $layout_rows[$i]))
			{
			$current_layout=explode("=>", $layout_rows[$i]);
			if (strlen(trim($current_layout[0]))>0 && strlen(trim($current_layout[1]))>0) 
				{
				$layout_id=trim($current_layout[0]);
				$layouts["$layout_id"]=trim($current_layout[1]);
				}
			}
		}
	}
ksort($layouts);
?>

<script language="JavaScript">
function ModifyQueue(file_id, form_action)
	{
	if (!file_id) {return false;}
	var country_code_field="country_code_"+file_id;
	var file_action_field="file_action_"+file_id;
	var file_layout_field="file_layout_"+file_id;

	if (form_action=="PENDING" && (document.getElementById(country_code_field).value=="" || document.getElementById(file_action_field).value=="" || document.getElementById(file_layout_field).value=="")) {alert("File cannot be processed without assigning a country code, action, and layout"); return false;}

	var update_string=file_id+"|";
	update_string+=document.getElementById(country_code_field).value+"|";
	update_string+=document.getElementById(file_action_field).value+"|";
	update_string+=document.getElementById(file_layout_field).value+"|";
	if (form_action) {update_string+=form_action;}
	
	document.getElementById("file_update_str").value=update_string;
	document.forms["queue_form"].submit();
	}

function DisplayFormat(format_value, file_id)
	{
<?php
	$JSFormats="\tvar JSFormats={";
	foreach($layouts as $key => $value)
		{
		$JSFormats.="$key: \"$value\", ";
		}
	$JSFormats2=preg_replace('/, $/', '', $JSFormats);
	$JSFormats2.="};\n";
	echo $JSFormats2;

	reset($layouts);
?>

	var format_array=JSFormats[format_value].split(/,/);
	var format_text="";
	if (format_array[0]=="")
		{
		format_text+="<BR>No delimiter/fixed length, file is assumed pre-formatted";
		}
	else 
		{
		format_text+="<BR>File format: "+format_array[0];
		}

	if (format_array[1]!="") {format_text+="<BR>File delimiter: "+format_array[1];}
	
	format_text+="<BR>Phone location: ";
	for (j=2; j<format_array.length; j++)
		{
		if (format_array[j].match(/\|/)) 
			{
			var format_subarray=format_array[j].split(/\|/);
			format_text+="pos. "+format_subarray[0]+" length "+format_subarray[1]+",";
			}
		else
			{
			format_text+=format_array[j]+",";
			}
		}
	
	var format_text_field="file_layout_text_"+file_id;
	document.getElementById(format_text_field).innerHTML="<font face='arial, helvetica' size=1>"+format_text+"</font>";
	}

</script>
<title><?php echo _QXZ("ADMINISTRATION: Lead Loader"); ?></title>
</head>
<BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>

<?php
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

$short_header=1;

require("admin_header.php");

# print_r($layouts);

$stmt="select * from vicidial_country_dnc_queue order by date_uploaded asc";
$rslt=mysql_to_mysqli($stmt, $link);
echo "<BR><B><font face=\"arial, helvetica\" size=2>"._QXZ("International DNC Loader")."$NWB#international_dnc_loader$NWE</font></B><BR>";
echo "<form action='$PHP_SELF' method='POST' name='queue_form'><table border=0 cellpadding=5 cellspacing=0>";
echo "<tr bgcolor='#000' valign='top'>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("File name")."</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Date loaded")."</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Status")."</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Country")."$NWB#international_dnc_loader-country_code$NWE</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Action (purge/append)")."$NWB#international_dnc_loader-file_action$NWE</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Layout")."$NWB#international_dnc_loader-file_layout$NWE</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Total records")."</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Records processed")."</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Records inserted")."</font></td>";
echo "<td nowrap><font face=\"arial, helvetica\" size=1 color=white>"._QXZ("Start/Cancel")."$NWB#international_dnc_loader-start_cancel$NWE</font></td>";
echo "</tr>";
if (mysqli_num_rows($rslt)==0)
	{
	echo "<tr><th colspan='10'><font face=\"arial, helvetica\" size=2>** "._QXZ("NO FILES LOADED")." **</font></th></tr>";
	}
while ($row=mysqli_fetch_array($rslt)) 
	{
	$id=$row["dnc_file_id"];
	$bgcolor="#CCCCCC";
	switch ($row["file_status"])
		{
			case "READY":
				$bgcolor="#CCFFFF";
				break;
			case "PENDING":
			case "PROCESSING":
				$bgcolor="#FFFFCC";
				break;
			case "CANCELLED":
			case "INVALID LAYOUT":
				$bgcolor="#FFCCCC";
				break;
			case "FINISHED":
				$bgcolor="#CCFFCC";
				break;
		}
	echo "<tr bgcolor='".$bgcolor."'>\n";
	echo "<td nowrap><font face=\"arial, helvetica\" size=1>$row[filename]</font></td>";
	echo "<td nowrap><font face=\"arial, helvetica\" size=1>$row[date_uploaded]</font></td>";
	echo "<td><font face=\"arial, helvetica\" size=1>$row[file_status]</font></td>";

	echo "<td><select class='form_field' name='country_code_".$id."' id='country_code_".$id."'>";	
	if ($row["file_status"]!="FINISHED" && $row["file_status"]!="PROCESSING")  {echo "<option value=''></option>\n";}
	$country_stmt="select iso3, country_name from vicidial_country_iso_tld where iso3 is not null and iso3!='' order by country_name asc";
	$country_rslt=mysql_to_mysqli($country_stmt, $link);
	while($country_row=mysqli_fetch_row($country_rslt)) 
		{
		$iso=$country_row[0];
		$country_name=$country_row[1];
		if ($iso==$row["country_code"]) {$s=" selected";} else {$s="";}
		if (($row["file_status"]!="FINISHED" && $row["file_status"]!="PROCESSING") || $s)
			{
			echo "\t\t\t\t<option value='$iso'$s>$iso - $country_name</option>\n";
			}
		}	
	echo "</select></font></td>";

	echo "<td><select class='form_field' name='file_action_".$id."' id='file_action_".$id."'>";
	echo "<option value='$row[file_action]'>"._QXZ("$row[file_action]")."</option>\n";
	if ($row["file_status"]!="FINISHED" && $row["file_status"]!="PROCESSING") 
		{
		echo "<option value='PURGE'>"._QXZ("PURGE")."</option>\n";
		echo "<option value='APPEND'>"._QXZ("APPEND")."</option>\n";
		}
	echo "</select></td>";
	echo "<td><select class='form_field' name='file_layout_".$id."' id='file_layout_".$id."' onChange='DisplayFormat(this.value, $id)'>";
	echo "<option value='$row[file_layout]'>"._QXZ("$row[file_layout]")."</option>\n";
	if ($row["file_status"]!="FINISHED" && $row["file_status"]!="PROCESSING") 
		{
		foreach($layouts as $key => $value)
			{
			echo "<option value='$key'>$key</option>\n";
			}
		}
	echo "</select><span id='file_layout_text_".$id."'></span></font></td>";
	echo "<td><font face=\"arial, helvetica\" size=1>$row[total_records]</font></td>";
	echo "<td><font face=\"arial, helvetica\" size=1>$row[records_processed]</font></td>";
	echo "<td><font face=\"arial, helvetica\" size=1>$row[records_inserted]</font></td>";
	echo "<td nowrap>";
	if ($row["file_status"]!="FINISHED" && $row["file_status"]!="PROCESSING") {echo "<input type='button' class='green_btn' style='width:90px' value='START FILE' onClick=\"ModifyQueue($id, 'PENDING')\">&nbsp;&nbsp;&nbsp;<input type='button' class='red_btn' style='width:80px' value='CANCEL' onClick=\"ModifyQueue($id, 'CANCELLED')\">";} else {echo "&nbsp;";}
	# <BR><input type='button' class='blue_btn' style='width:60px' value='SAVE' onClick=\"ModifyQueue($id)\">
	echo "</td>";
	echo "</tr>";
	}
echo "<tr bgcolor='#000'><th colspan='10'><input type='button' onClick=\"window.location.href='$PHP_SELF'\" value='"._QXZ("REFRESH PAGE")."'></th></tr>";
echo "</table>";
echo "<input type=hidden name='file_update_str' id='file_update_str'>";
echo "</form>";

?>
</form>
</body>
</html>
