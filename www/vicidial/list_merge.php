<?php
# list_merge.php - merge smaller lists into a larger one. Part of Admin Utilities.
#
# Copyright (C) 2022  Matt Florell,Joseph Johnson <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 161004-2240 - Initial Build
# 170409-1548 - Added IP List validation code
# 170819-1000 - Added allow_manage_active_lists option
# 180508-2215 - Added new help display
# 191101-1630 - Translation patch, removed function.js 
# 201117-1350 - Translation bug fixed, Issue #1236
# 220227-2210 - Added allow_web_debug system setting
#

$version = '2.14-4';
$build = '220227-2210';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$ip = getenv("REMOTE_ADDR");
$SQLdate = date("Y-m-d H:i:s");

$DB=0;
$submit = "";
$confirm = "";
$available_lists = "";
$start_dest_list_id = "";
$num_leads = "";

if (isset($_GET["DB"])) {$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"])) {$DB=$_POST["DB"];}
if (isset($_GET["submit"])) {$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"])) {$submit=$_POST["submit"];}
if (isset($_GET["confirm"])) {$confirm=$_GET["confirm"];}
	elseif (isset($_POST["confirm"])) {$confirm=$_POST["confirm"];}
if (isset($_GET["available_lists"])) {$available_lists=$_GET["available_lists"];}
	elseif (isset($_POST["available_lists"])) {$available_lists=$_POST["available_lists"];}
if (isset($_GET["start_dest_list_id"])) {$start_dest_list_id=$_GET["start_dest_list_id"];}
	elseif (isset($_POST["start_dest_list_id"])) {$start_dest_list_id=$_POST["start_dest_list_id"];}
if (isset($_GET["num_leads"])) {$num_leads=$_GET["num_leads"];}
	elseif (isset($_POST["num_leads"])) {$num_leads=$_POST["num_leads"];}
if (isset($_GET["destination_list_id"])) {$destination_list_id=$_GET["destination_list_id"];}
	elseif (isset($_POST["destination_list_id"])) {$destination_list_id=$_POST["destination_list_id"];}
if (isset($_GET["new_list_id"])) {$new_list_id=$_GET["new_list_id"];}
	elseif (isset($_POST["new_list_id"])) {$new_list_id=$_POST["new_list_id"];}
if (isset($_GET["new_list_name"])) {$new_list_name=$_GET["new_list_name"];}
	elseif (isset($_POST["new_list_name"])) {$new_list_name=$_POST["new_list_name"];}
if (isset($_GET["new_list_description"])) {$new_list_description=$_GET["new_list_description"];}
	elseif (isset($_POST["new_list_description"])) {$new_list_description=$_POST["new_list_description"];}
if (isset($_GET["active"])) {$active=$_GET["active"];}
	elseif (isset($_POST["active"])) {$active=$_POST["active"];}
if (isset($_GET["campaign_id"])) {$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"])) {$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["retain_original_list"])) {$retain_original_list=$_GET["retain_original_list"];}
	elseif (isset($_POST["retain_original_list"])) {$retain_original_list=$_POST["retain_original_list"];}
if (isset($_GET["create_new_list"])) {$create_new_list=$_GET["create_new_list"];}
	elseif (isset($_POST["create_new_list"])) {$create_new_list=$_POST["create_new_list"];}

$DB = preg_replace('/[^0-9]/','',$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$sys_settings_stmt = "SELECT use_non_latin,outbound_autodial_active,sounds_central_control_active,enable_languages,language_method,active_modules,admin_screen_colors,allow_manage_active_lists,allow_web_debug FROM system_settings;";
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
	$SSactive_modules =					$sys_settings_row[5];
	$SSadmin_screen_colors =			$sys_settings_row[6];
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

$retain_original_list = preg_replace('/[^NY]/i','',$retain_original_list);
$available_lists = preg_replace('/[^0-9\|]/','',$available_lists);
$start_dest_list_id = preg_replace('/[^0-9]/','',$start_dest_list_id);
$destination_list_id = preg_replace('/[^0-9]/','',$destination_list_id);
$new_list_id = preg_replace('/[^0-9]/','',$new_list_id);
$new_list_name = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$new_list_name);
$new_list_description = preg_replace('/[^- \.\,\_0-9a-zA-Z]/','',$new_list_description);
$num_leads = preg_replace('/[^0-9]/','',$num_leads);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$confirm = preg_replace('/[^-_0-9a-zA-Z]/', '', $confirm);
$active = preg_replace('/[^-_0-9a-zA-Z]/', '', $active);
$create_new_list = preg_replace('/[^-_0-9a-zA-Z]/', '', $create_new_list);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$campaign_id = preg_replace('/[^-_0-9a-zA-Z]/', '', $campaign_id);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$campaign_id = preg_replace('/[^-_0-9\p{L}]/u', '', $campaign_id);
	}

$stmt="SELECT selected_language,modify_lists from vicidial_users where user='$PHP_AUTH_USER';";
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
header ("Pragma: no-cache");		      // HTTP/1.0

# valid user
$rights_stmt = "SELECT user_level, user_group, load_leads, modify_leads, modify_lists from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rights_rslt=mysql_to_mysqli($rights_stmt, $link);
$rights_row=mysqli_fetch_row($rights_rslt);
$user_level =		$rights_row[0];
$user_group =		$rights_row[1];
$load_leads =		$rights_row[2];
$modify_leads =		$rights_row[3];
$modify_lists =		$rights_row[4];

# check their permissions
if ( $user_level < 9 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You need to be a level 9 user to use this tool");
	exit;
	}
if ( $load_leads < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to load leads");
	exit;
	}
if ( $modify_leads < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify leads");
	exit;
	}
if ( $modify_lists < 1 )
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify lists");
	exit;
	}

echo "<html>\n";
echo "<head>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.css\" />";
#echo "<script language=\"JavaScript\" src=\"functions.js\"></script>\n";
?>
<script language="JavaScript">
function PopulateMergeMenu(clearit) {
	var DDtoPop = document.getElementById("destination_list_id");
	var PopLength = DDtoPop.options.length;
	DDtoPop.options.length = 0;
	if (clearit) {return false;}

	var SourceDD = document.getElementById("available_lists");
	var DDoption = document.createElement("option");
	DDoption.text = '<?php echo _QXZ("ALTERNATE LIST (enter below)"); ?>';
	DDoption.value = '';
	DDtoPop.appendChild(DDoption);

	var SourceLength = SourceDD.options.length;
	for (var i = 1; i < SourceLength; i++) {
		if(SourceDD.options[i].selected) {
			var DDoption = document.createElement("option");
			DDoption.text = SourceDD.options[i].text;
			DDoption.value = SourceDD.options[i].value;
			DDtoPop.appendChild(DDoption);
		}
	}


}
</script>
<?php

echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

echo "<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=utf-8'>\n";
echo "<!-- "._QXZ("VERSION").": <?php echo $version ?>     "._QXZ("BUILD").": <?php echo $build ?> -->\n";
echo "<title>"._QXZ("ADMINISTRATION: List Merge Tool")."</title>\n";

##### BEGIN Set variables to make header show properly #####
$ADD =			       '999998';
$hh =				'admin';
$LOGast_admin_access =  '1';
$SSoutbound_autodial_active = '1';
$ADMIN =			     'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$SSadmin_color =	  '#FFFF99';
$admin_font =	   'BLACK';
$SSadmin_color =	  '#E6E6E6';
$subcamp_color =	'#C6C6C6';
# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='F0F5FE';
$SSstd_row3_background='8EBCFD';
$SSstd_row2_background='B6D3FC';
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
		$SSadmin_color =			$row[1];
		$SSstd_row1_background =	$row[2];
		$SSstd_row2_background =	$row[3];
		$SSstd_row3_background =	$row[4];
		$SSstd_row2_background =	$row[5];
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
##### END Set variables to make header show properly #####

require("admin_header.php");

echo "<table width=$page_width bgcolor='$SSadmin_color' cellpadding=2 cellspacing=0>\n";
echo "<tr bgcolor='$SSadmin_color'>\n";
echo "<td align=left>\n";
echo "<font face='ARIAL,HELVETICA' size=2>\n";
echo "<b>"._QXZ("List Merge Tool")."</b>\n";
echo "</font>\n";
echo "</td>\n";
echo "<td align=right><font face='ARIAL,HELVETICA' size=2><b> &nbsp; </td>\n";
echo "</tr>\n";


echo "<tr bgcolor='#$SSframe_background'><td align=left colspan=2><font face='ARIAL,HELVETICA' color=black size=3> &nbsp; \n";

if ($DB>0)
	{
	echo "DB: $DB<br>\n";
	echo "submit: $submit<br>\n";
	echo "confirm: $confirm<br>\n";
	echo "available_lists: $available_lists<br>\n";
	echo "start_dest_list_id: $start_dest_list_id<br>\n";
	echo "num_leads: $num_leads<br>\n";
	}


# figure out which campaigns this user is allowed to work on
# moved here from below because campaign list is needed multiple times
$allowed_campaigns_stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$user_group';";
if ($DB) { echo "|$allowed_campaigns_stmt|\n"; }
$rslt = mysql_to_mysqli($allowed_campaigns_stmt, $link);
$allowed_campaigns_row = mysqli_fetch_row($rslt);
$allowed_campaigns = $allowed_campaigns_row[0];
if ($DB) { echo "|$allowed_campaigns|\n"; }
$allowed_campaigns_sql = "";
if ( preg_match("/ALL\-CAMPAIGNS/i",$allowed_campaigns) )
	{
	if ($DB) { echo "|Processing All Campaigns|\n"; }
	$campaign_id_stmt = "SELECT campaign_id FROM vicidial_campaigns;";
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


# confirmation page
if ($submit == _QXZ("submit") )
	{
	
	if(count($available_lists)<1) {
		echo "<p>"._QXZ("No list ids to merge given.")."</p>\n";
		echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
		exit;
	}

	if ($new_list_id) {
		# Check if "new" list is actually new
		$list_stmt="select list_id, list_name, list_description, campaign_id, active FROM vicidial_lists where list_id = '$new_list_id';";
		$list_rslt=mysql_to_mysqli($list_stmt, $link);
		$list_row = mysqli_fetch_row($list_rslt);
		$list_rows = mysqli_num_rows($list_rslt);
		# If the new list ID already exists, get the existing information, not the new one.
		if ($list_rows>0) {			
			$new_list_name=$list_row[1];
			$new_list_description=$list_row[2];
			$campaign_id=$list_row[3];
			$active=$list_row[4];

			if (!preg_match("/ $campaign_id /", $allowed_campaigns) && !preg_match("/ALL\-CAMPAIGNS/i",$allowed_campaigns)) {
				echo "<p>"._QXZ("Cannot move leads - access is denied to the specified list ID ($campaign_id - $allowed_campaigns):")." $new_list_id</p>\n";
				echo "<p><a href='$PHP_SELF?new_list_id=$new_list_id&destination_list_id=$destination_list_id&new_campaign_id=$new_campaign_id&new_list_name=".urlencode($new_list_name)."&new_list_description=".urlencode($new_list_description)."&active=$active&retain_original_list=$retain_original_list&available_lists[]=".implode("&available_lists[]=", $available_lists)."'>"._QXZ("Click here to start over.")."</a></p>\n";
				exit;
			}
		} else {
			if (!$new_list_name || !$campaign_id) {
				echo "<p>"._QXZ("Cannot move leads - list specified is new and missing information.")."</p>\n";
				echo "<p><a href='$PHP_SELF?new_list_id=$new_list_id&destination_list_id=$destination_list_id&new_campaign_id=$new_campaign_id&new_list_name=".urlencode($new_list_name)."&new_list_description=".urlencode($new_list_description)."&active=$active&retain_original_list=$retain_original_list&available_lists[]=".implode("&available_lists[]=", $available_lists)."'>"._QXZ("Click here to start over.")."</a></p>\n";
				exit;
			}
			$create_new_list=1;
		}
		$destination_list_id=$new_list_id;
	} else if ($destination_list_id) {
		# make sure the original list id is valid
		$id_chk_stmt = "SELECT list_id, list_name, list_description, campaign_id, active FROM vicidial_lists where list_id = '$destination_list_id';";
		if ($DB) { echo "|$id_chk_stmt|\n"; }
		$id_chk_rslt = mysql_to_mysqli($id_chk_stmt, $link);
		$id_chk_rows = mysqli_num_rows($id_chk_rslt);
		if ( $id_chk_rows < 1 )
			{
			echo "<p>$destination_list_id "._QXZ("is not a valid list id in the system.")."</p>\n";
			echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
			echo "</body>\n</html>\n";
			exit;
			}	
	} else {
			echo "<p>"._QXZ("No valid destination list id given.")."</p>\n";
			echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
			exit;
	}

	# Get the number of leads in the available_lists
	$orig_count_stmt = "SELECT count(*) FROM vicidial_list where list_id in ('".implode("','", $available_lists)."');";
	if ($DB) { echo "|$orig_count_stmt|\n"; }
	$orig_count_rslt = mysql_to_mysqli($orig_count_stmt, $link);
	$orig_count_row = mysqli_fetch_row($orig_count_rslt);
	$orig_count = $orig_count_row[0];

	$num_lists = ceil( $orig_count / $num_leads );


	echo ""._QXZ("You are about to merge list(s) <UL><LI>%1s</UL><BR>into list ID %2s.<BR><BR><B>TOTAL LEADS TO BE MOVED: %3s",0,'',implode("<LI>", $available_lists),$destination_list_id,$orig_count)."\n";

	if ($orig_count>=400000) {
		echo "<BR><BR><redalert>*** "._QXZ("WARNING:  Lists should not have more than 400,000 records in them!!!")." ***</redalert><BR><BR>";
	}

	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=available_lists value='".implode("|", $available_lists)."'>\n";
	echo "<input type=hidden name=orig_count value='$orig_count'>\n";
	echo "<input type=hidden name=destination_list_id value='$destination_list_id'>\n";
	echo "<input type=hidden name=new_list_id value='$new_list_id'>\n";

	echo "<input type=hidden name=new_list_name value='$new_list_name'>\n";
	echo "<input type=hidden name=new_list_description value='$new_list_description'>\n";
	echo "<input type=hidden name=campaign_id value='$campaign_id'>\n";
	echo "<input type=hidden name=active value='$active'>\n";
	echo "<input type=hidden name=create_new_list value='$create_new_list'>\n";
	echo "<input type=hidden name=retain_original_list value='$retain_original_list'>\n";

	# keep debug active
	echo "<input type=hidden name=DB value='$DB'>\n";
	
	echo "<input type=submit name='confirm' class='red_btn' value='"._QXZ("CONFIRM")."'>\n";
	echo "</form>\n";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p></center>\n";
	echo "</body>\n</html>\n";
	}

# actually do the merge
if ($confirm == _QXZ("CONFIRM"))
	{
	$available_lists_array=explode("|", $available_lists);
	# Get the number of leads in the available_lists
	$orig_count_stmt = "SELECT count(*) FROM vicidial_list where list_id in ('".implode("', '", $available_lists_array)."');";
	if ($DB) { echo "|$orig_count_stmt|\n"; }
	$orig_count_rslt = mysql_to_mysqli($orig_count_stmt, $link);
	$orig_count_row = mysqli_fetch_row($orig_count_rslt);
	$orig_count = $orig_count_row[0];

	if ($create_new_list) 
		{
		$list_create_stmt = "INSERT INTO vicidial_lists (list_id, list_name, campaign_id, active, list_description) VALUES ('$new_list_id', '$new_list_name', '$campaign_id', '$active', '$new_list_description');";
		$list_create_rslt=mysql_to_mysqli($list_create_stmt, $link);
		$new_list_count = mysqli_affected_rows($link);
		if ($DB) {echo "|$list_create_stmt|\n";}
		$SQL_log = preg_replace('/;/', '', $list_create_stmt);
		$SQL_log = addslashes($SQL_log);
		$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$new_list_id', event_code='ADMIN ADD LIST', event_sql=\"$SQL_log\", event_notes='$new_list_count LIST CREATED';";
		if ($DB) {echo "|$admin_log_stmt|\n";}
		$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
		}

/*
	if ($retain_original_list) 
		{
		$upd_stmt="update vicidial_list set entry_list_id=list_id where list_id in ('".implode("', '", $available_lists_array)."');";
		$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
		$update_lead_count = mysqli_affected_rows($link);
		$SQL_log = preg_replace('/;/', '', $upd_stmt);
		$SQL_log = addslashes($SQL_log);
		$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$destination_list_id', event_code='ADMIN UPDATE LEADS', event_sql=\"$SQL_log\", event_notes='$update_lead_count LEADS UPDATED';";
		$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
		}
*/

	
	# move the leads to the new list from the old list
	$move_lead_stmt = "UPDATE vicidial_list SET list_id = '$destination_list_id' WHERE list_id in ('".implode("', '", $available_lists_array)."');";
	if ($DB) { echo "|$move_lead_stmt|\n"; }
	$move_lead_rslt = mysql_to_mysqli($move_lead_stmt, $link);
	$move_lead_count = mysqli_affected_rows($link);

	# insert a record into the admin change log for the mvoe into new list
	$SQL_log = "$move_lead_stmt";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$destination_list_id', event_code='ADMIN LIST MERGE', event_sql=\"$SQL_log\", event_notes='$move_lead_count LEADS MOVED';";
	if ($DB) {echo "|$admin_log_stmt|\n";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);
		
	# reset the PHP max execution so this script does not exit prematurely
	set_time_limit( 120 );
	

	echo "<p>"._QXZ("List(s)")." ".implode(", ", $available_lists_array)." "._QXZ("was merged into list")." $destination_list_id "._QXZ("with")." $move_lead_count "._QXZ("lead(s) moved.")."</p>";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
	}


# main page display
if (($submit != "submit" ) && ($confirm != "CONFIRM"))
	{
	# figure out which lists they are allowed to see
	$activeSQL = "and active = 'N'";
	if ($SSallow_manage_active_lists > 0) {$activeSQL = '';}
	$lists_stmt = "SELECT list_id, list_name FROM vicidial_lists WHERE campaign_id IN ($allowed_campaigns_sql) $activeSQL ORDER BY list_id;";
	if ($DB) { echo "|$lists_stmt|\n"; }
	$lists_rslt = mysql_to_mysqli($lists_stmt, $link);
	$num_rows = mysqli_num_rows($lists_rslt);
	$allowed_lists_count = 0;
	while ( $allowed_lists_count < $num_rows )
		{
		$lists_row = mysqli_fetch_row($lists_rslt);

		# check how many leads are in the list
		$lead_count_stmt = "SELECT count(1)  FROM vicidial_list WHERE list_id = '$lists_row[0]';";
		if ($DB) { echo "|$lead_count_stmt|\n"; }
		$lead_count_rslt = mysql_to_mysqli($lead_count_stmt, $link);
		$lead_count_row = mysqli_fetch_row($lead_count_rslt);
		$lead_count = $lead_count_row[0];

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

	if ($SSallow_manage_active_lists > 0)
		{echo "<p>"._QXZ("This is the list merge tool. It will merge several existing lists into a single list.")."</p>";}
	else
		{echo "<p>"._QXZ("This is the list merge tool. It will only work on inactive lists. It will merge several existing lists into a single list.")."</p>";}
	
	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<center><table width=$section_width cellspacing=3>\n";

	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>"._QXZ("List Merge")."</b></font></td></tr>\n";

	# Available Lists
	echo "<tr bgcolor=#$SSstd_row2_background><td align=right>"._QXZ("Available Lists")."</td><td align=left>\n";
	echo "<select size=".($num_rows<10 ? $num_rows : 10)." name=available_lists[] id='available_lists' multiple onClick='PopulateMergeMenu()'>\n";
	echo "<option value=''>-- "._QXZ("SELECT LISTS BELOW")." --</option>\n";
	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		if (is_array($available_lists))
			{
			if (in_array($list_ary[$i], $available_lists)) {$x="selected";} 
			else {$x="";}
			}
		else {$x="";}
		echo "<option value='$list_ary[$i]' $x>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] "._QXZ("leads").")</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";

	# Destination List ID
	echo "<tr bgcolor=#$SSstd_row2_background><td align=right>"._QXZ("List to merge into:")."</td><td align=left>\n";
	echo "<select name='destination_list_id' id='destination_list_id' size='1'>\n";
	echo "<option value=''>"._QXZ("ALTERNATE LIST (enter below)")."</option>\n";
	echo "</select>\n";
	# echo " <input type=button value='CLEAR' onClick='PopulateMergeMenu(1)'>";
	#echo " <input type='checkbox' name='retain_original_list' id='retain_original_list' value='Y' ".($retain_original_list ? "checked" : "")."><font size='1'>Retain original list ID (stored in entry_list_id column)</font>";
	echo "<font size='1'>("._QXZ("Lists selected above will automatically populate here").")</font></td></tr>\n";

	# Destination List ID
	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>"._QXZ("Alternate list information - only required if -ALTERNATE LIST- is selected above")."</b></font></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row2_background><td align=right>"._QXZ("Alternate list ID:")."</td><td align=left>\n";
	echo "<input type='text' name='new_list_id' id='new_list_id' size='19' maxlength='19' value='".$new_list_id."'> ("._QXZ("digits only").")\n";
	echo "</td></tr>\n";
	
	echo "<tr bgcolor=#$SSstd_row1_background><td colspan=2 align=center><b>"._QXZ("Below fields are required ONLY if -Alternate list ID- is filled out and not an already-existing list")."</b></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row1_background><td align=right>"._QXZ("List Name").": </td><td align=left><input type=text name='new_list_name' size=30 maxlength=30 value='".$new_list_name."'>$NWB#lists-list_name$NWE</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row1_background><td align=right>"._QXZ("List Description").": </td><td align=left><input type=text name='new_list_description' size=30 maxlength=255 value='".$new_list_description."'>$NWB#lists-list_description$NWE</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row1_background><td align=right>"._QXZ("Campaign").": </td><td align=left><select size=1 name='campaign_id'>\n";

	$stmt="SELECT campaign_id,campaign_name from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$campaigns_to_print = mysqli_num_rows($rslt);
	$campaigns_list='';

	$o=0;
	while ($campaigns_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$campaigns_list .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
		$o++;
		}
	echo "$campaigns_list";
	echo "<option SELECTED>$campaign_id</option>\n";
	echo "</select>$NWB#lists-campaign_id$NWE</td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row1_background><td align=right>"._QXZ("Active").": </td><td align=left><select size=1 name='active'><option value='Y'>"._QXZ("Y")."</option><option SELECTED>N</option></select>$NWB#lists-active$NWE</td></tr>\n";

	# keep debug active
	echo "<input type=hidden name=DB value='$DB'>\n";
	
	# Submit
	echo "<tr bgcolor=#$SSstd_row2_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'></td></tr>\n";
	echo "</table></center>\n";
	echo "</form>\n";

	# pad the bottom
	echo "<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />\n";
	# echo "<script type=\"text/javascript\">\$(window).load(function(){ if (window.attachEvent) {window.attachEvent('onload', PopulateMergeMenu());} else if (window.addEventListener) {PopulateMergeMenu();} else {PopulateMergeMenu();}});</script>";
	echo "<script language='Javascript'>PopulateMergeMenu();</script>";

	echo "</body></html>\n";
	}

echo "</td></tr></table>\n";
?>
