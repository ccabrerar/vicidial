<?php
# list_split.php - split one big list into smaller lists. Part of Admin Utilities.
#
# Copyright (C) 2021  Matt Florell,Michael Cargile <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 140916-1215 - Initial Build
# 141114-1330 - formatting changes, code cleanup
# 141229-1822 - Added code for on-the-fly language translations display
# 150808-2042 - Added compatibility for custom fields data option
# 161014-0842 - Added screen colors
# 170409-1542 - Added IP List validation code
# 170819-1001 - Added allow_manage_active_lists option
# 190703-0925 - Added use of admin_web_directory system setting
# 210427-1741 - Added more list count options
#

$version = '2.14-9';
$build = '210427-1741';

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
$orig_list = "";
$start_dest_list_id = "";
$num_leads = "";

if (isset($_GET["DB"])) {$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"])) {$DB=$_POST["DB"];}
if (isset($_GET["submit"])) {$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"])) {$submit=$_POST["submit"];}
if (isset($_GET["confirm"])) {$confirm=$_GET["confirm"];}
	elseif (isset($_POST["confirm"])) {$confirm=$_POST["confirm"];}
if (isset($_GET["orig_list"])) {$orig_list=$_GET["orig_list"];}
	elseif (isset($_POST["orig_list"])) {$orig_list=$_POST["orig_list"];}
if (isset($_GET["start_dest_list_id"])) {$start_dest_list_id=$_GET["start_dest_list_id"];}
	elseif (isset($_POST["start_dest_list_id"])) {$start_dest_list_id=$_POST["start_dest_list_id"];}
if (isset($_GET["num_leads"])) {$num_leads=$_GET["num_leads"];}
	elseif (isset($_POST["num_leads"])) {$num_leads=$_POST["num_leads"];}

$DB = preg_replace('/[^0-9]/','',$DB);
$submit = preg_replace('/[^-_0-9a-zA-Z]/','',$submit);
$confirm = preg_replace('/[^-_0-9a-zA-Z]/','',$confirm);
$orig_list = preg_replace('/[^0-9]/','',$orig_list);
$start_dest_list_id = preg_replace('/[^0-9]/','',$start_dest_list_id);
$num_leads = preg_replace('/[^0-9]/','',$num_leads);


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$sys_settings_stmt = "SELECT use_non_latin,outbound_autodial_active,sounds_central_control_active,enable_languages,language_method,active_modules,admin_screen_colors,allow_manage_active_lists,admin_web_directory FROM system_settings;";
$sys_settings_rslt=mysql_to_mysqli($sys_settings_stmt, $link);
if ($DB) {echo "$sys_settings_stmt\n";}
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
	$SSadmin_web_directory =			$sys_settings_row[8];
	}
else
	{
	# there is something really weird if there are no system settings
	exit;
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
echo "<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=utf-8'>\n";
echo "<!-- "._QXZ("VERSION").": <?php echo $version ?>     "._QXZ("BUILD").": <?php echo $build ?> -->\n";
echo "<title>"._QXZ("ADMINISTRATION: List Split Tool")."</title>\n";

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

$SSmenu_background='015B91';
$SSframe_background='D9E6FE';
$SSstd_row1_background='9BB9FB';
$SSstd_row2_background='F0F5FE';
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
		$SSadmin_color =			$row[1];
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
##### END Set variables to make header show properly #####

require("admin_header.php");

echo "<table width=$page_width bgcolor='$SSadmin_color' cellpadding=2 cellspacing=0>\n";
echo "<tr bgcolor='$SSadmin_color'>\n";
echo "<td align=left>\n";
echo "<font face='ARIAL,HELVETICA' size=2>\n";
echo "<b>"._QXZ("List Split Tool")."</b>\n";
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
	echo "orig_list: $orig_list<br>\n";
	echo "start_dest_list_id: $start_dest_list_id<br>\n";
	echo "num_leads: $num_leads<br>\n";
	}

# confirmation page
if ($submit == _QXZ("submit") )
	{
	# make sure the original list id is valid
	$id_chk_stmt = "SELECT list_id FROM vicidial_lists where list_id = $orig_list;";
	if ($DB) { echo "|$id_chk_stmt|\n"; }
	$id_chk_rslt = mysql_to_mysqli($id_chk_stmt, $link);
	$id_chk_rows = mysqli_num_rows($id_chk_rslt);
	if ( $id_chk_rows < 1 )
		{
		echo "<p>$orig_list "._QXZ("is not a valid list id in the system.")."</p>\n";
		echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
		echo "</body>\n</html>\n";
		exit;
		}	

	# figure out the highest list id in the system
	$max_id_stmt = "SELECT MAX(list_id) FROM vicidial_lists;";
	if ($DB) { echo "|$max_id_stmt|\n"; }
	$max_id_rslt = mysql_to_mysqli($max_id_stmt, $link);
	$max_id_row = mysqli_fetch_row($max_id_rslt);
	$max_list_id = $max_id_row[0] + 1;

	# make sure the start destination list id is >= to max_list_id and an integer
	if ( ( is_int( $start_dest_list_id ) ) && ($start_dest_list_id < $max_list_id ) )
		{
		echo "<p>"._QXZ("The Start Destination List must be greater than or equal to")." $max_list_id. "._QXZ("This is to ensure that new lists will not interfere with the existing ones.")."</p>\n";
		echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
		echo "</body>\n</html>\n";
		exit;
		}

	# make sure num_leads is an integer
	if ( is_int( $num_leads ) )
		{
		echo "<p>"._QXZ("Number of Leads Per List must be a numeric value.")."</p>\n";
		echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
		echo "</body>\n</html>\n";
		exit;
		}

	# Get the number of leads in the orig_list
	$orig_count_stmt = "SELECT count(*) FROM vicidial_list where list_id = $orig_list;";
	if ($DB) { echo "|$orig_count_stmt|\n"; }
	$orig_count_rslt = mysql_to_mysqli($orig_count_stmt, $link);
	$orig_count_row = mysqli_fetch_row($orig_count_rslt);
	$orig_count = $orig_count_row[0];

	$num_lists = ceil( $orig_count / $num_leads );


	echo "<p>"._QXZ("You are about to split list %1s into %2s new lists with %3s leads in each of the new lists. The new lists will start with list id %4s.",0,'',$orig_list,$num_lists,$num_leads,$start_dest_list_id)."</p>\n";
	echo "<center><form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=orig_list value='$orig_list'>\n";
	echo "<input type=hidden name=num_leads value='$num_leads'>\n";
	echo "<input type=hidden name=start_dest_list_id value='$start_dest_list_id'>\n";

	# keep debug active
	echo "<input type=hidden name=DB value='$DB'>\n";
	
	echo "<input style='background-color:#$SSbutton_color' type=submit name=confirm value=confirm>\n";
	echo "</form></center>\n";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
	echo "</body>\n</html>\n";

	}

# actually do the split
if ($confirm == "confirm")
	{
	# make sure the original list id is valid
	$id_chk_stmt = "SELECT list_id FROM vicidial_lists where list_id = $orig_list;";
	if ($DB) { echo "|$id_chk_stmt|\n"; }
	$id_chk_rslt = mysql_to_mysqli($id_chk_stmt, $link);
	$id_chk_rows = mysqli_num_rows($id_chk_rslt);
	if ( $id_chk_rows < 1 )
		{
		echo "<p>$orig_list "._QXZ("is not a valid list id in the system.")."</p>\n";
		echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
		echo "</body>\n</html>\n";
		exit;
		}
		
	# find out if the original list has any custom fields
	$cf_chk_stmt = "SELECT COUNT(1) FROM vicidial_lists_fields where list_id = $orig_list;";
	if ($DB) { echo "|$cf_chk_stmt|\n"; }
	$cf_chk_rslt = mysql_to_mysqli($cf_chk_stmt, $link);
	$cf_chk_row = mysqli_fetch_row($cf_chk_rslt);
	$cf_chk_count = $cf_chk_row[0];

	# figure out the highest list id in the system
	$max_id_stmt = "SELECT MAX(list_id) FROM vicidial_lists;";
	if ($DB) { echo "|$max_id_stmt|\n"; }
	$max_id_rslt = mysql_to_mysqli($max_id_stmt, $link);
	$max_id_row = mysqli_fetch_row($max_id_rslt);
	$max_list_id = $max_id_row[0] + 1;

	# make sure the start destination list id is >= to max_list_id and an integer
	if ( ( is_int( $start_dest_list_id ) ) && ($start_dest_list_id < $max_list_id ) )
		{
		echo "<p>"._QXZ("The Start Destination List must be greater than or equal to")." $max_list_id. "._QXZ("This is to ensure that new lists will not interfere with the existing ones.")."</p>\n";
		echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
		echo "</body>\n</html>\n";
		exit;
		}

	# make sure num_leads is an integer
	if ( is_int( $num_leads ) )
		{
		echo "<p>"._QXZ("Number of Leads Per List must be a numeric value.")."</p>\n";
		echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
		echo "</body>\n</html>\n";
		exit;
		}

	# Get the number of leads in the orig_list
	$orig_count_stmt = "SELECT count(*) FROM vicidial_list where list_id = $orig_list;";
	if ($DB) { echo "|$orig_count_stmt|\n"; }
	$orig_count_rslt = mysql_to_mysqli($orig_count_stmt, $link);
	$orig_count_row = mysqli_fetch_row($orig_count_rslt);
	$orig_count = $orig_count_row[0];

	$num_lists = ceil( $orig_count / $num_leads );

	# get the list info for the original list
	$list_info_stmt = "SELECT list_name, campaign_id, active, list_description, list_changedate, list_lastcalldate, reset_time, agent_script_override, campaign_cid_override, am_message_exten_override, drop_inbound_group_override, xferconf_a_number, xferconf_b_number, xferconf_c_number, xferconf_d_number, xferconf_e_number, web_form_address, web_form_address_two, time_zone_setting, inventory_report, expiration_date FROM vicidial_lists where list_id = $orig_list;";
	if ($DB) { echo "|$list_info_stmt|\n"; }
	$list_info_rslt = mysql_to_mysqli($list_info_stmt, $link);
	$list_info_row = mysqli_fetch_row($list_info_rslt);

	$list_name						= $list_info_row[0];
	$campaign_id					= $list_info_row[1];
	$active							= $list_info_row[2];
	$list_description				= $list_info_row[3];
	$list_changedate				= $list_info_row[4];
	$list_lastcalldate				= $list_info_row[5];
	$reset_time						= $list_info_row[6];
	$agent_script_override			= $list_info_row[7];
	$campaign_cid_override			= $list_info_row[8];
	$am_message_exten_override		= $list_info_row[9];
	$drop_inbound_group_override	= $list_info_row[10];
	$xferconf_a_number				= $list_info_row[11];
	$xferconf_b_number				= $list_info_row[12];
	$xferconf_c_number				= $list_info_row[13];
	$xferconf_d_number				= $list_info_row[14];
	$xferconf_e_number				= $list_info_row[15];
	$web_form_address				= $list_info_row[16];
	$web_form_address_two			= $list_info_row[17];
	$time_zone_setting				= $list_info_row[18];
	$inventory_report				= $list_info_row[19];
	$expiration_date				= $list_info_row[20];


	$i = 0;
	$new_list_id = $start_dest_list_id;
	$new_lists = "";
	
	$all_sql = "";
	
	while ( $i < $num_lists ) {
		# create the new list
		$list_create_stmt = "INSERT INTO vicidial_lists ( list_id, list_name, campaign_id, active, list_description, list_changedate, list_lastcalldate, reset_time, agent_script_override, campaign_cid_override, am_message_exten_override, drop_inbound_group_override, xferconf_a_number, xferconf_b_number, xferconf_c_number, xferconf_d_number, xferconf_e_number, web_form_address, web_form_address_two, time_zone_setting, inventory_report, expiration_date ) VALUES ( '$new_list_id', '$list_name split $i', '$campaign_id', '$active', '$list_description', '$list_changedate', '$list_lastcalldate', '$reset_time', '$agent_script_override', '$campaign_cid_override', '$am_message_exten_override', '$drop_inbound_group_override', '$xferconf_a_number', '$xferconf_b_number', '$xferconf_c_number', '$xferconf_d_number', '$xferconf_e_number', '$web_form_address', '$web_form_address_two', '$time_zone_setting', '$inventory_report', '$expiration_date' );";
		if ($DB) {echo "|$list_create_stmt|\n";}
		$list_create_rslt=mysql_to_mysqli($list_create_stmt, $link);
		
		$all_sql .= "$list_create_stmt|";

		# insert a record into the admin change log for the new list creation
		$SQL_log = "$list_create_stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$new_list_id', event_code='ADMIN LIST SPLIT', event_sql=\"$SQL_log\", event_notes='NEW LIST CREATION';";
		if ($DB) {echo "|$admin_log_stmt|\n";}
		$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);

		# copy the custom fields if there are any
		if ( $cf_chk_count > 0 ) 
			{
			$admin_lists_custom = 'admin_lists_custom.php';

			$url = "http" . (isset($_SERVER['HTTPS']) ? 's' : '') . "://$_SERVER[HTTP_HOST]/$SSadmin_web_directory/" . $admin_lists_custom . "?action=COPY_FIELDS_SUBMIT&list_id=$new_list_id&source_list_id=$orig_list&copy_option=APPEND";
			
			# use cURL to call the copy custom fields code
			$curl = curl_init();
			
			# Set some options - we are passing in a useragent too here
			curl_setopt_array($curl, array(
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_URL => $url,
				CURLOPT_USERPWD => "$PHP_AUTH_USER:$PHP_AUTH_PW",
				CURLOPT_USERAGENT => 'list_split.php'
			));
			
			# Send the request & save response to $resp
			$resp = curl_exec($curl);
			
			# Close request to clear up some resources
			curl_close($curl);
			}
		
		if ($DB) { echo "|$resp|\n"; }
		
		
		# move the leads to the new list from the old list
		$move_lead_stmt = "UPDATE vicidial_list SET list_id = '$new_list_id' WHERE list_id = '$orig_list' limit $num_leads;";
		if ($DB) { echo "|$move_lead_stmt|\n"; }
		$move_lead_rslt = mysql_to_mysqli($move_lead_stmt, $link);
		$move_lead_count = mysqli_affected_rows($link);

		$all_sql .= "$move_lead_stmt|";
		
		# insert a record into the admin change log for the mvoe into new list
		$SQL_log = "$move_lead_stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$new_list_id', event_code='ADMIN LIST SPLIT', event_sql=\"$SQL_log\", event_notes='$move_lead_count LEADS MOVED';";
		if ($DB) {echo "|$admin_log_stmt|\n";}
		$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);

		# keep track of the list ids we used for the final admin change log entry
		$new_lists .= "$new_list_id ";

		$i++;
		$new_list_id++;
		
		# reset the PHP max execution so this script does not exit prematurely
		set_time_limit( 120 );
	}
	
	# insert a record into the admin change log for the split into orig list
	$SQL_log = "$all_sql|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$admin_log_stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='LISTS', event_type='OTHER', record_id='$orig_list', event_code='ADMIN LIST SPLIT', event_sql=\"$SQL_log\", event_notes='List $orig_list split into list ids $new_lists';";
	if ($DB) {echo "|$admin_log_stmt|\n";}
	$admin_log_rslt=mysql_to_mysqli($admin_log_stmt, $link);



	echo "<p>"._QXZ("List")." $orig_list "._QXZ("was split into lists")." $new_lists "._QXZ("with")." $num_leads "._QXZ("leads placed into each new list.")."</p>";
	echo "<p><a href='$PHP_SELF'>"._QXZ("Click here to start over.")."</a></p>\n";
	}


# main page display
if (($submit != _QXZ("submit") ) && ($confirm != "confirm"))
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

	# figure out the highest list id in the system
	$max_id_stmt = "SELECT MAX(list_id) FROM vicidial_lists;";
	if ($DB) { echo "|$max_id_stmt|\n"; }
	$max_id_rslt = mysql_to_mysqli($max_id_stmt, $link);
	if ( mysqli_num_rows($max_id_rslt) > 0 )
		{
		$max_id_row = mysqli_fetch_row($max_id_rslt);
		$max_list_id = $max_id_row[0] + 1;
		}

	if ($SSallow_manage_active_lists > 0)
		{echo "<p>"._QXZ("This is the list split tool. It will split an existing list into several new lists with the same options as the original list. This includes copying the customer fields from the original.")."</p>";}
	else
		{echo "<p>"._QXZ("This is the list split tool.  It will only work on inactive lists. It will split an existing list into several new lists with the same options as the original list. This includes copying the customer fields from the original.")."</p>";}
	
	echo "<form action=$PHP_SELF method=POST>\n";
	echo "<center><table width=$section_width cellspacing=3>\n";

	echo "<tr bgcolor=#$SSmenu_background><td colspan=2 align=center><font color=white><b>"._QXZ("List Split")."</b></font></td></tr>\n";

	# Orginal Lists
	echo "<tr bgcolor=#$SSstd_row4_background><td align=right>"._QXZ("Original List")."</td><td align=left>\n";
	echo "<select size=1 name=orig_list>\n";
	echo "<option value='-'>"._QXZ("Select A List")."</option>\n";
	$i = 0;
	while ( $i < $allowed_lists_count )
		{
		echo "<option value='$list_ary[$i]'>$list_ary[$i] - $list_name_ary[$i] ($list_lead_count_ary[$i] "._QXZ("leads").")</option>\n";
		$i++;
		}

	echo "</select></td></tr>\n";

	# Start Destination List ID
	echo "<tr bgcolor=#$SSstd_row4_background><td align=right>"._QXZ("Start Destination List")."</td><td align=left>\n";
	echo "<input type='text' name='start_dest_list_id' value='$max_list_id'> ("._QXZ("must be")." >= $max_list_id)</td></tr>\n";

	# Number of leads per list
	echo "<tr bgcolor=#$SSstd_row4_background><td align=right>"._QXZ("Number of Leads Per List")."</td><td align=left>\n";
	echo "<select size=1 name=num_leads>\n";
	echo "<option value='60000'>60000</option>\n";
	echo "<option value='50000'>50000</option>\n";
	echo "<option value='40000'>40000</option>\n";
	echo "<option value='30000'>30000</option>\n";
	echo "<option value='20000'>20000</option>\n";
	echo "<option value='10000'>10000</option>\n";
	echo "<option value='9000'>9000</option>\n";
	echo "<option value='8000'>8000</option>\n";
	echo "<option value='7000'>7000</option>\n";
	echo "<option value='6000'>6000</option>\n";
	echo "<option value='5000'>5000</option>\n";
	echo "<option value='4000'>4000</option>\n";
	echo "<option value='3000'>3000</option>\n";
	echo "<option value='2000'>2000</option>\n";
	echo "<option value='1000'>1000</option>\n";
	echo "<option value='500'>500</option>\n";
	echo "<option value='100'>100</option>\n";
	echo "<option value='50'>50</option>\n";
	echo "<option value='10'>10</option>\n";
	echo "</select></td></tr>\n";

	# keep debug active
	echo "<input type=hidden name=DB value='$DB'>\n";
	
	# Submit
	echo "<tr bgcolor=#$SSstd_row4_background><td colspan=2 align=center><input style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'></td></tr>\n";
	echo "</table></center>\n";
	echo "</form>\n";

	# pad the bottom
	echo "<br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br /><br />\n";
	echo "</body></html>\n";
	}

echo "</td></tr></table>\n";
?>
