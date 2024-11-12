<?php
# hci_screen.php
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to allow agents to work with the hopper_hold_inserts features and allow leads in the hopper to be changed from RHOLD to READY status so they can be called.
#
# changes:
# 231119-1508 - First build
# 240225-1000 - Added AUTONEXT hopper_hold_inserts campaign option
#

$startMS = microtime();

$hci_version = '2.14-2h';
$build = '240225-1000';
$php_script='hci_screen.php';
$zi=0;
$default_batch_size=40;
$refresh_sec=4000;

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["lead_id"]))				{$lead_id=$_GET["lead_id"];}
	elseif (isset($_POST["lead_id"]))		{$lead_id=$_POST["lead_id"];}
if (isset($_GET["phone_number"]))			{$phone_number=$_GET["phone_number"];}
	elseif (isset($_POST["phone_number"]))	{$phone_number=$_POST["phone_number"];}
if (isset($_GET["campaign_id"]))			{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))	{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["batch_size"]))				{$batch_size=$_GET["batch_size"];}
	elseif (isset($_POST["batch_size"]))	{$batch_size=$_POST["batch_size"];}
if (isset($_GET["stage"]))			{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))	{$stage=$_POST["stage"];}
if (isset($_GET["DB"]))				{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))	{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace('/[^0-9]/','',$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,custom_fields_enabled,enable_languages,language_method,active_modules,log_recording_access,allow_web_debug,hopper_hold_inserts,agent_screen_colors,admin_web_directory,admin_home_url FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$custom_fields_enabled =	$row[1];
	$SSenable_languages =		$row[2];
	$SSlanguage_method =		$row[3];
	$active_modules =			$row[4];
	$log_recording_access =		$row[5];
	$SSallow_web_debug =		$row[6];
	$SShopper_hold_inserts =	$row[7];
	$agent_screen_colors =		$row[8];
	$admin_web_directory =		$row[9];
	$admin_home_url =			$row[10];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$phone_number = preg_replace('/[^0-9]/','',$phone_number);
$batch_size = preg_replace('/[^0-9]/','',$batch_size);
$stage = preg_replace('/[^-_0-9a-zA-Z]/','',$stage);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$user = preg_replace('/[^-\_0-9a-zA-Z]/','',$user);
	$campaign_id = preg_replace('/[^-\_0-9a-zA-Z]/', '',$campaign_id);
	$submit = preg_replace('/[^- 0-9a-zA-Z]/','',$submit);
	$SUBMIT = preg_replace('/[^- 0-9a-zA-Z]/','',$SUBMIT);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$user=preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$campaign_id = preg_replace('/[^-\_0-9\p{L}]/u', '',$campaign_id);
	$submit = preg_replace('/[^- 0-9\p{L}]/u','',$submit);
	$SUBMIT = preg_replace('/[^- 0-9\p{L}]/u','',$SUBMIT);
	}

if ( ($batch_size < 10) or (strlen($batch_size) < 2) )
	{$batch_size = $default_batch_size;}

$STARTtime = date("U");
$defaultappointment = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$browser=preg_replace("/\'|\"|\\\\/","",$browser);
$script_name = getenv("SCRIPT_NAME");
$server_name = getenv("SERVER_NAME");
$server_port = getenv("SERVER_PORT");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$CL=':';
if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($server_port == '80') or ($server_port == '443') ) {$server_port='';}
else {$server_port = "$CL$server_port";}
$FQDN = "$server_name$server_port";
$hciPAGE = "$HTTPprotocol$server_name$server_port$script_name";

$stmt="SELECT modify_leads,qc_enabled,full_name,modify_leads,user_group,selected_language,hci_enabled from vicidial_users where user='$PHP_AUTH_USER';";
if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
$rslt=mysql_to_mysqli($stmt, $link);
$users_to_parse = mysqli_num_rows($rslt);
if ($users_to_parse > 0) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$modify_leads =			$rowx[0];
	$qc_enabled =			$rowx[1];
	$LOGfullname =			$rowx[2];
	$LOGmodify_leads =		$rowx[3];
	$LOGuser_group =		$rowx[4];
	$VUselected_language =	$rowx[5];
	$hci_enabled =			$rowx[6];
	}

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
$temp_logo = $selected_logo;

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'HCI',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

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

# check user permissions
if ($hci_enabled < 1)
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("HCI is not enabled for your user account")."\n";
	exit;
	}

if ($SShopper_hold_inserts < 1)
	{
	header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("HCI is not enabled on this system")."\n";
	exit;
	}

$LOGallowed_campaignsSQL='';
$stmt="SELECT allowed_campaigns from vicidial_user_groups where user_group='$LOGuser_group';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
if ( (!preg_match("/ALL-CAMPAIGNS/i",$row[0])) )
	{
	$LOGallowed_campaignsSQL = preg_replace('/\s-/i','',$row[0]);
	$LOGallowed_campaignsSQL = preg_replace('/\s/i',"','",$LOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$LOGallowed_campaignsSQL')";
	}


################################################################################
### BEGIN LOGOUT - agent logout, delete live entry, reset reserved hopper leads
################################################################################
if ( ($stage == 'LOGOUT') and (strlen($campaign_id) > 0) )
	{
	$stmt="DELETE FROM vicidial_hci_live_agents WHERE user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);

	$stmt="INSERT INTO vicidial_hci_agent_log SET user='$PHP_AUTH_USER',campaign_id='$campaign_id',user_ip='$ip',login_time=NOW(),status='LOGOUT';";
	$rslt=mysql_to_mysqli($stmt, $link);

	$stmt="SELECT lead_id from vicidial_hci_reserve WHERE user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$vhr_to_parse = mysqli_num_rows($rslt);
	$Elead_id = array();
	$vhr_ct=0;
	while ($vhr_to_parse > $vhr_ct) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$Elead_id[$vhr_ct] =		$rowx[0];
		$vhr_ct++;
		}
	$vhr_ct=0;
	$vh_clear=0;
	$vhr_clear=0;
	while ($vhr_to_parse > $vhr_ct) 
		{
		$stmt="UPDATE vicidial_hopper SET status='RHOLD',user='' where lead_id='$Elead_id[$vhr_ct]' and status='RQUEUE';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		$vh_clear = ($vh_clear + $affected_rows);

		$stmt="DELETE FROM vicidial_hci_reserve WHERE lead_id='$Elead_id[$vhr_ct]' and user='$PHP_AUTH_USER';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		$vhr_clear = ($vhr_clear + $affected_rows);
		$vhr_ct++;
		}

	$notes = "$campaign_id $vh_clear $vhr_clear";
	vicidial_ajax_log($NOW_TIME,$startMS,$link,$stage,$php_script,$PHP_AUTH_USER,$notes,$lead_id,$session_name,$stmt);
	}
################################################################################
### END LOGOUT - agent logout, delete live entry, reset reserved hopper leads
################################################################################


################################################################################
### BEGIN chosen_campaign - agent has chosen a campaign to log into, insert the live agent entry and a log entry
################################################################################
if ( ($stage == 'chosen_campaign') and (strlen($campaign_id) > 0) )
	{
	$valid_campaign=0;
	$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and active='Y' and hopper_hold_inserts IN('ENABLED','AUTONEXT') $LOGallowed_campaignsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$camps_to_parse = mysqli_num_rows($rslt);
	if ($camps_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$valid_campaign =		$rowx[0];
		}
	if ($valid_campaign > 0)
		{
		$autonext_campaign=0;
		$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and hopper_hold_inserts='AUTONEXT';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$campANs_to_parse = mysqli_num_rows($rslt);
		if ($campANs_to_parse > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$autonext_campaign =		$rowx[0];
			}
		$stmt="INSERT INTO vicidial_hci_live_agents SET user='$PHP_AUTH_USER',campaign_id='$campaign_id',user_ip='$ip',login_time=NOW(),last_call_time=NOW(),last_update_time=NOW(),status='LOGIN',lead_id=0,phone_number='',random_id=0;";
		$rslt=mysql_to_mysqli($stmt, $link);

		$stmt="INSERT INTO vicidial_hci_agent_log SET user='$PHP_AUTH_USER',campaign_id='$campaign_id',user_ip='$ip',login_time=NOW(),status='LOGIN';";
		$rslt=mysql_to_mysqli($stmt, $link);

		$hopper_hold_count=0;
		$stmt="SELECT count(*) from vicidial_hopper where campaign_id='$campaign_id' and status='RHOLD' and user='';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$hop_to_parse = mysqli_num_rows($rslt);
		if ($hop_to_parse > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$hopper_hold_count =		$rowx[0];
			}
		if ($hopper_hold_count > 0)
			{
			$stmt="UPDATE vicidial_hopper SET user='$PHP_AUTH_USER',status='RQUEUE' WHERE campaign_id='$campaign_id' and status='RHOLD' and user='' order by priority desc,hopper_id limit $batch_size;";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vh_affected_rows = mysqli_affected_rows($link);

			$stmt="SELECT lead_id from vicidial_hopper WHERE user='$PHP_AUTH_USER' and status='RQUEUE' and campaign_id='$campaign_id';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vhr_to_parse = mysqli_num_rows($rslt);
			$Elead_id = array();
			$vhr_ct=0;
			while ($vhr_to_parse > $vhr_ct) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$Elead_id[$vhr_ct] =		$rowx[0];
				$vhr_ct++;
				}
			$vhr_ct=0;
			$vhr_insert=0;
			while ($vhr_to_parse > $vhr_ct) 
				{
				$temp_phone='';
				$stmt="SELECT phone_number from vicidial_list where lead_id='$Elead_id[$vhr_ct]';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$vl_to_parse = mysqli_num_rows($rslt);
				if ($vl_to_parse > 0) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$temp_phone =		$rowx[0];
					}

				$stmt="INSERT INTO vicidial_hci_reserve SET user='$PHP_AUTH_USER',lead_id='$Elead_id[$vhr_ct]',phone_number='$temp_phone',reserve_date=NOW(),status='NEW',campaign_id='$campaign_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);
				$vhr_insert = ($vhr_insert + $affected_rows);
				$vhr_ct++;
				}
			}

		$notes = "$campaign_id $ip $vh_affected_rows $vhr_to_parse $vhr_ct $vhr_insert $autonext_campaign";
		vicidial_ajax_log($NOW_TIME,$startMS,$link,$stage,$php_script,$PHP_AUTH_USER,$notes,$lead_id,$session_name,$stmt);
		}
	}
################################################################################
### END chosen_campaign - agent has chosen a campaign to log into, insert the live agent entry and a log entry
################################################################################


################################################################################
### BEGIN switch_campaign - logged-in agent has chosen to switch to a different campaign, insert the live agent entry and a log entry
################################################################################
if ( ($stage == 'switch_campaign') and (strlen($campaign_id) > 0) )
	{
	$valid_campaign=0;
	$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and active='Y' and hopper_hold_inserts IN('ENABLED','AUTONEXT') $LOGallowed_campaignsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	$camps_to_parse = mysqli_num_rows($rslt);
	if ($camps_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$valid_campaign =		$rowx[0];
		}
	if ($valid_campaign > 0)
		{
		$autonext_campaign=0;
		$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and hopper_hold_inserts='AUTONEXT';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$campANs_to_parse = mysqli_num_rows($rslt);
		if ($campANs_to_parse > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$autonext_campaign =		$rowx[0];
			}
		$stmt="SELECT campaign_id,status from vicidial_hci_live_agents where user='$PHP_AUTH_USER';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$users_to_parse = mysqli_num_rows($rslt);
		if ($users_to_parse > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$old_campaign_id =		$rowx[0];
			$VHLAstatus =			$rowx[1];

			$stmt="UPDATE vicidial_hci_live_agents SET campaign_id='$campaign_id',user_ip='$ip',login_time=NOW(),last_call_time=NOW(),last_update_time=NOW(),status='SWITCH',lead_id=0,phone_number='',random_id=0 WHERE user='$PHP_AUTH_USER';";
			$rslt=mysql_to_mysqli($stmt, $link);

			$stmt="INSERT INTO vicidial_hci_agent_log SET user='$PHP_AUTH_USER',campaign_id='$campaign_id',user_ip='$ip',login_time=NOW(),status='SWITCH';";
			$rslt=mysql_to_mysqli($stmt, $link);

			$stmt="SELECT lead_id from vicidial_hci_reserve WHERE user='$PHP_AUTH_USER';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$vhr_to_parse = mysqli_num_rows($rslt);
			$Elead_id = array();
			$vhr_ct=0;
			while ($vhr_to_parse > $vhr_ct) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$Elead_id[$vhr_ct] =		$rowx[0];
				$vhr_ct++;
				}
			$vhr_ct=0;
			$vh_clear=0;
			$vhr_clear=0;
			while ($vhr_to_parse > $vhr_ct) 
				{
				$stmt="UPDATE vicidial_hopper SET status='RHOLD',user='' where lead_id='$Elead_id[$vhr_ct]' and status='RQUEUE';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);
				$vh_clear = ($vh_clear + $affected_rows);

				$stmt="DELETE FROM vicidial_hci_reserve WHERE lead_id='$Elead_id[$vhr_ct]' and user='$PHP_AUTH_USER';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rows = mysqli_affected_rows($link);
				$vhr_clear = ($vhr_clear + $affected_rows);
				$vhr_ct++;
				}

			$hopper_hold_count=0;
			$stmt="SELECT count(*) from vicidial_hopper where campaign_id='$campaign_id' and status='RHOLD' and user='';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$hop_to_parse = mysqli_num_rows($rslt);
			if ($hop_to_parse > 0) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$hopper_hold_count =		$rowx[0];
				}
			if ($hopper_hold_count > 0)
				{
				$stmt="UPDATE vicidial_hopper SET user='$PHP_AUTH_USER',status='RQUEUE' WHERE campaign_id='$campaign_id' and status='RHOLD' and user='' order by priority desc,hopper_id limit $batch_size;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$vh_affected_rows = mysqli_affected_rows($link);

				$stmt="SELECT lead_id from vicidial_hopper WHERE user='$PHP_AUTH_USER' and status='RQUEUE' and campaign_id='$campaign_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$vhr_to_parse = mysqli_num_rows($rslt);
				$Elead_id = array();
				$vhr_ct=0;
				while ($vhr_to_parse > $vhr_ct) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$Elead_id[$vhr_ct] =		$rowx[0];
					$vhr_ct++;
					}
				$vhr_ct=0;
				$vhr_insert=0;
				while ($vhr_to_parse > $vhr_ct) 
					{
					$temp_phone='';
					$stmt="SELECT phone_number from vicidial_list where lead_id='$Elead_id[$vhr_ct]';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$vl_to_parse = mysqli_num_rows($rslt);
					if ($vl_to_parse > 0) 
						{
						$rowx=mysqli_fetch_row($rslt);
						$temp_phone =		$rowx[0];
						}

					$stmt="INSERT INTO vicidial_hci_reserve SET user='$PHP_AUTH_USER',lead_id='$Elead_id[$vhr_ct]',phone_number='$temp_phone',reserve_date=NOW(),status='NEW',campaign_id='$campaign_id';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$affected_rows = mysqli_affected_rows($link);
					$vhr_insert = ($vhr_insert + $affected_rows);
					$vhr_ct++;
					}
				}

			$notes = "$campaign_id $old_campaign_id $VHLAstatus $vh_clear $vhr_clear $vh_affected_rows $vhr_insert $autonext_campaign";
			vicidial_ajax_log($NOW_TIME,$startMS,$link,$stage,$php_script,$PHP_AUTH_USER,$notes,$lead_id,$session_name,$stmt);
			}
		else
			{
			header ("Content-type: text/html; charset=utf-8");
			echo _QXZ("Agent not logged in to HCI screen")."\n";
			exit;
			}
		}
	else
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("This is not a valid HCI campaign").": $campaign_id \n";
		exit;
		}
	}
################################################################################
### END switch_campaign - logged-in agent has chosen to switch to a different campaign, insert the live agent entry and a log entry
################################################################################


################################################################################
### BEGIN dashboard - display dashboard stats for logged-in campaign
################################################################################
if ($stage == 'dashboard')
	{
	$stmt="SELECT campaign_id,status from vicidial_hci_live_agents where user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$users_to_parse = mysqli_num_rows($rslt);
	if ($users_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$campaign_id =		$rowx[0];
		$VHLAstatus =		$rowx[1];
		}
	else
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Agent not logged in to HCI screen")."\n";
		exit;
		}

	$hopper_ready=0;
	$stmt="SELECT count(*) from vicidial_hopper WHERE status='READY' and campaign_id='$campaign_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$hop_to_parse = mysqli_num_rows($rslt);
	if ($hop_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$hopper_ready =		$rowx[0];
		}

	$hopper_hold=0;
	$stmt="SELECT count(*) from vicidial_hopper WHERE status='RHOLD' and campaign_id='$campaign_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$hop_to_parse = mysqli_num_rows($rslt);
	if ($hop_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$hopper_hold =		$rowx[0];
		}

	$agents_ready=0;
	$stmt="SELECT count(*) from vicidial_live_agents where status IN ('READY','CLOSER') and campaign_id='$campaign_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$vla_to_parse = mysqli_num_rows($rslt);
	if ($vla_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$agents_ready =		$rowx[0];
		}

	$calls_out=0;
	$stmt="SELECT count(*) from vicidial_auto_calls where campaign_id='$campaign_id' and status = 'SENT';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$vla_to_parse = mysqli_num_rows($rslt);
	if ($vla_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$calls_out =		$rowx[0];
		}

	$drops_today=0;
	$drops_answers_pct=0;
	$stmt="SELECT drops_today,drops_answers_today_pct from vicidial_campaign_stats where campaign_id='$campaign_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$vla_to_parse = mysqli_num_rows($rslt);
	if ($vla_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$drops_today =			$rowx[0];
		$drops_answers_pct =	$rowx[1];
		}

	$camp_dial_method='';
	$camp_dial_level=0;
	$camp_adapt_level=0;
	$max_dial_level=0;
	$stmt="SELECT dial_method,auto_dial_level,adaptive_maximum_level from vicidial_campaigns where campaign_id='$campaign_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$vla_to_parse = mysqli_num_rows($rslt);
	if ($vla_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$camp_dial_method =		$rowx[0];
		$camp_dial_level =		$rowx[1];
		$camp_adapt_level =		$rowx[2];

		if (preg_match("/ADAPT/",$camp_dial_method))
			{$max_dial_level = $camp_adapt_level;}
		else
			{$max_dial_level = $camp_dial_level;}
		}

	$calls_offset = (($agents_ready * $max_dial_level) - $calls_out);
	$calls_offset_color="fa8073";
	if ($calls_offset > -30)
	 { $calls_offset_color="bbffb8";}
	if ($calls_offset > 30)
	 { $calls_offset_color="edbb47";}


	echo "<TABLE width=600 cellpadding=0 cellspacing=0>";
	echo "<tr><td align=left>";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3>"._QXZ("Dashboard").": </FONT>";
	echo "</td><td align=right>";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>$NOW_TIME</FONT>";
	echo "</td></tr></TABLE>\n<BR>";

	echo "<center>";
	echo "<TABLE width=600 cellpadding=6 cellspacing=0>\n";
	echo "<tr>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><img src=\"images/icon_calls.png\" width=42 height=42 border=0></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("Calls in Progress")." &nbsp; </font></td>";
	echo "<td width=10 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><img src=\"images/icon_callswaiting.png\" width=42 height=42 border=0></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("Calls on Hold")." &nbsp; </font></td>";
	echo "<td width=10 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><img src=\"images/icon_hourglass.png\" width=42 height=42 border=0></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("Records Remaining")." &nbsp; </font></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$calls_out</font></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$hopper_hold</font></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$hopper_ready</font></td>";
	echo "</tr>";

	echo "<tr><td colspan=8><font size=1> &nbsp; </font></td></tr>";

	echo "<tr>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><img src=\"images/icon_agentsincalls.png\" width=42 height=42 border=0></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("Agents Ready")." &nbsp; </font></td>";
	echo "<td width=10 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><img src=\"images/icon_calls_offset.png\" width=42 height=42 border=0></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("Calls Offset")." &nbsp; </font></td>";
	echo "<td width=10 rowspan=2> &nbsp; </td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background' rowspan=2><img src=\"images/icon_drop_rate.png\" width=42 height=42 border=0></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:11;color:white;font-weight:bold;\">"._QXZ("Drop Rate")." &nbsp; </font></td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$agents_ready</font></td>";
	echo "<td align='center' valign='middle' bgcolor='#$calls_offset_color'><font style=\"font-family:HELVETICA;font-size:18;color:black;font-weight:bold;\">$calls_offset</font></td>";
	echo "<td align='center' valign='middle' bgcolor='#$SSmenu_background'><font style=\"font-family:HELVETICA;font-size:18;color:white;font-weight:bold;\">$drops_answers_pct%</font></td>";
	echo "</tr>";

	echo "</TABLE>";
	echo "<br><br>";

	$notes = "$campaign_id $calls_out $hopper_hold $hopper_ready $agents_ready $calls_offset $drops_answers_pct";
	vicidial_ajax_log($NOW_TIME,$startMS,$link,$stage,$php_script,$PHP_AUTH_USER,$notes,$lead_id,$session_name,$stmt);

	exit;
	}
################################################################################
### END dashboard - display dashboard stats for logged-in campaign
################################################################################


################################################################################
### BEGIN lead_display - display reserved hold hopper leads for this agent, to be confirmed
################################################################################
if ($stage == 'lead_display')
	{
	$stmt="SELECT campaign_id,status from vicidial_hci_live_agents where user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$users_to_parse = mysqli_num_rows($rslt);
	if ($users_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$campaign_id =		$rowx[0];
		$VHLAstatus =		$rowx[1];
		}
	else
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Agent not logged in to HCI screen")."\n";
		exit;
		}

	$reserved_ct=0;
	$stmt="SELECT count(*) from vicidial_hci_reserve where campaign_id='$campaign_id' and user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$camps_to_parse = mysqli_num_rows($rslt);
	if ($camps_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$reserved_ct =		$rowx[0];
		}
	if ($reserved_ct < $batch_size)
		{
		$reserve_more_hopper_leads = ($batch_size - $reserved_ct);

		if ($reserve_more_hopper_leads > 0)
			{
			$hopper_hold_count=0;
			$stmt="SELECT count(*) from vicidial_hopper where campaign_id='$campaign_id' and status='RHOLD' and user='';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$hop_to_parse = mysqli_num_rows($rslt);
			if ($hop_to_parse > 0) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$hopper_hold_count =		$rowx[0];
				}
			if ($hopper_hold_count > 0)
				{
				$stmt="UPDATE vicidial_hopper SET user='$PHP_AUTH_USER',status='RQUEUE' WHERE campaign_id='$campaign_id' and status='RHOLD' and user='' order by priority desc,hopper_id limit $reserve_more_hopper_leads;";
				$rslt=mysql_to_mysqli($stmt, $link);
				$vh_affected_rows = mysqli_affected_rows($link);

				$stmt="SELECT lead_id from vicidial_hopper WHERE user='$PHP_AUTH_USER' and status='RQUEUE' and campaign_id='$campaign_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$vhr_to_parse = mysqli_num_rows($rslt);
				$Elead_id = array();
				$vhr_ct=0;
				while ($vhr_to_parse > $vhr_ct) 
					{
					$rowx=mysqli_fetch_row($rslt);
					$Elead_id[$vhr_ct] =		$rowx[0];
					$vhr_ct++;
					}
				$vhr_ct=0;
				$vhr_insert=0;
				while ($vhr_to_parse > $vhr_ct) 
					{
					$already_reserved=0;
					$stmt="SELECT count(*) from vicidial_hci_reserve where lead_id='$Elead_id[$vhr_ct]';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$vt_to_parse = mysqli_num_rows($rslt);
					if ($vt_to_parse > 0) 
						{
						$rowx=mysqli_fetch_row($rslt);
						$already_reserved =		$rowx[0];
						}

					if ($already_reserved < 1)
						{
						$temp_phone='';
						$stmt="SELECT phone_number from vicidial_list where lead_id='$Elead_id[$vhr_ct]';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$vl_to_parse = mysqli_num_rows($rslt);
						if ($vl_to_parse > 0) 
							{
							$rowx=mysqli_fetch_row($rslt);
							$temp_phone =		$rowx[0];
							}

						$stmt="INSERT INTO vicidial_hci_reserve SET user='$PHP_AUTH_USER',lead_id='$Elead_id[$vhr_ct]',phone_number='$temp_phone',reserve_date=NOW(),status='NEW',campaign_id='$campaign_id';";
						$rslt=mysql_to_mysqli($stmt, $link);
						$affected_rows = mysqli_affected_rows($link);
						$vhr_insert = ($vhr_insert + $affected_rows);
						}
					$vhr_ct++;
					}
				}

			$stage = 'reserve_more_leads';
			$notes = "$campaign_id $reserve_more_hopper_leads $vh_clear $hopper_hold_count $vh_affected_rows $vhr_insert";
			vicidial_ajax_log($NOW_TIME,$startMS,$link,$stage,$php_script,$PHP_AUTH_USER,$notes,$lead_id,$session_name,$stmt);
			}
		}

	$reserved_ct=0;
	$stmt="SELECT count(*) from vicidial_hci_reserve where campaign_id='$campaign_id' and user='$PHP_AUTH_USER';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$camps_to_parse = mysqli_num_rows($rslt);
	if ($camps_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$reserved_ct =		$rowx[0];
		}

	if ($reserved_ct < 1)
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("There are no leads available")."\n";
		exit;
		}
	else
		{
		$lead_phone_list='';
		$stmt="SELECT lead_id,phone_number from vicidial_hci_reserve WHERE campaign_id='$campaign_id' and user='$PHP_AUTH_USER' order by reserve_date,lead_id;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$vhr_to_parse = mysqli_num_rows($rslt);
		$Elead_id = array();
		$Ephone = array();
		$vhr_ct=0;
		while ($vhr_to_parse > $vhr_ct) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$Elead_id[$vhr_ct] =	$rowx[0];
			$Ephone[$vhr_ct] =		$rowx[1];
			$lead_phone_list .= $vhr_ct."_".$rowx[0]."_".$rowx[1]."|";
			$vhr_ct++;
			}

		echo "<!-- LEADPHONELIST: $lead_phone_list -->\n";
		echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3>"._QXZ("Call Confirmation").": </FONT><BR><table width=530><tr><td>";

		$row_ct=0;
		$vhr_ct=0;
		while ($vhr_to_parse > $vhr_ct) 
			{
			$USnpa = substr($Ephone[$vhr_ct], 0, 3);
			$USnxx = substr($Ephone[$vhr_ct], 3, 3);
			$USxxxx = substr($Ephone[$vhr_ct], 6, 4);
			$temp_phone = "($USnpa) ".$USnxx.'-'.$USxxxx;

		#	echo "$Elead_id[$vhr_ct] - $Ephone[$vhr_ct] - $temp_phone<BR>";
		#	echo "<span id=\"button_span_$Elead_id[$vhr_ct]\"><table border=0 cellpadding=0 cellspacing=2><tr><td><button class=\"button_hci_ready\" id=\"button_$Elead_id[$vhr_ct]\" onclick=\"call_confirmation_button('$Elead_id[$vhr_ct]','$temp_phone');\"> &nbsp; $temp_phone &nbsp; </button></td></tr><tr><td><img src=\"../agc/images/blank.gif\" width=1 height=2></td></tr></table></span>\n";

			echo "<span id=\"button_span_$Elead_id[$vhr_ct]\" style=\"display: inline-block\">";
		#	if ($row_ct >= 3) {echo "<br>";   $row_ct=0;}
			echo "<table border=0 cellpadding=0 cellspacing=2><tr><td><button class=\"button_hci_ready\" id=\"button_$Elead_id[$vhr_ct]\" onclick=\"call_confirmation_button('$Elead_id[$vhr_ct]','$temp_phone');\"> &nbsp; $temp_phone &nbsp; </button></td><td><img src=\"../agc/images/blank.gif\" width=2 height=1></td></tr><tr><td colspan=2><img src=\"../agc/images/blank.gif\" width=1 height=2></td></tr></table> </span>";

			$row_ct++;
			$vhr_ct++;
			}
		echo "</td></tr></table>";
		}

	exit;
	}
################################################################################
### END lead_display - display reserved hold hopper leads for this agent, to be confirmed
################################################################################


################################################################################
### BEGIN activate_lead - set hopper lead to READY and remove entry from reserved hopper leads for this user
################################################################################
if ( ($stage == 'activate_lead') and (strlen($lead_id) > 0) )
	{
	$campaign_id='';
	$phone_number='';
	$stmt="SELECT campaign_id,phone_number from vicidial_hci_reserve WHERE user='$PHP_AUTH_USER' and lead_id='$lead_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$vhr_to_parse = mysqli_num_rows($rslt);
	if ($vhr_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$campaign_id =		$rowx[0];
		$phone_number =		$rowx[1];
		}
	$vhr_ct=0;
	$vh_clear=0;
	$vhr_clear=0;
	if (strlen($phone_number) > 0) 
		{
		$stmt="UPDATE vicidial_hopper SET status='READY',user='' where lead_id='$lead_id' and status='RQUEUE' and user='$PHP_AUTH_USER';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$VHaffected_rows = mysqli_affected_rows($link);

		if ($VHaffected_rows > 0)
			{
			$stmt="DELETE FROM vicidial_hci_reserve WHERE lead_id='$lead_id' and user='$PHP_AUTH_USER';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$VHRaffected_rows = mysqli_affected_rows($link);

			$stmt="INSERT INTO vicidial_hci_log SET lead_id='$lead_id',user='$PHP_AUTH_USER',phone_number='$phone_number',campaign_id='$campaign_id',call_date=NOW(),user_ip='$ip',status='CONFIRMED';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$VHLaffected_rows = mysqli_affected_rows($link);

			$stmt="INSERT INTO hci_logs SET lead_id='$lead_id',user='$PHP_AUTH_USER',campaign_id='$campaign_id',date=NOW();";
			$rslt=mysql_to_mysqli($stmt, $link);
			$HLaffected_rows = mysqli_affected_rows($link);
			}
		}

	$notes = "$phone_number $VHaffected_rows $VHRaffected_rows $VHLaffected_rows $HLaffected_rows";
	vicidial_ajax_log($NOW_TIME,$startMS,$link,$stage,$php_script,$PHP_AUTH_USER,$notes,$lead_id,$session_name,$stmt);
	exit;
	}
################################################################################
### BEGIN activate_lead - set hopper lead to READY and remove entry from reserved hopper leads for this user
################################################################################


# Called AJAX functions go here






# BEGIN building main screen
$stmt="SELECT campaign_id,status from vicidial_hci_live_agents where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$users_to_parse = mysqli_num_rows($rslt);
if ($users_to_parse > 0) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$campaign_id =		$rowx[0];
	$VHLAstatus =			$rowx[1];
	}

# get list of HCI-enabled campaigns for pull-down lists
$camp_panel_code='<table cellspacing=2 cellpadding=2>';
$camp_form_code  = "<select size=\"1\" name=\"campaign_id\" id=\"campaign_id\">\n";
$camp_form_code .= "<option value=\"\">-- "._QXZ("Select a Campaign")." --</option>\n";
	
$stmt="SELECT campaign_id,campaign_name,hopper_hold_inserts from vicidial_campaigns where active='Y' and hopper_hold_inserts IN('ENABLED','AUTONEXT') $LOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
$camps_to_print = mysqli_num_rows($rslt);

$o=0;
while ($camps_to_print > $o) 
	{
	$rowx=mysqli_fetch_row($rslt);
	$camp_form_code .= "<option value=\"$rowx[0]\">$rowx[0] - $rowx[1]</option>\n";
	$camp_panel_code .= "<tr>";
	if ($campaign_id == $rowx[0])
		{
		$camp_panel_code .= "<td align=left><b> * </b></td><td align=left> &nbsp; <u><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK>$rowx[0] - $rowx[1]</u> &nbsp; </td>";
		}
	else
		{
		$camp_panel_code .= "<td align=left><b> &nbsp; </b></td><td align=left> &nbsp; <a href=\"#\" onclick=\"SwitchCampaign('$rowx[0]');return false;\"><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK>$rowx[0] - $rowx[1]</a> &nbsp; </td>";
		}
	$camp_panel_code .= "</tr>";
	$o++;
	}
$camp_panel_code .= "</table>";
$camp_form_code .= "</select>\n";


if ($users_to_parse < 1) 
	{
	# choose a campaign screen
	?>
	<html>
	<head>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
	<link rel="stylesheet" type="text/css" href="../agc/css/style.css" />
	<link rel="stylesheet" type="text/css" href="../agc/css/custom.css" />
	<title><?php echo _QXZ("HCI Screen"); ?></title>
	</head>
	<BODY BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>

	&nbsp; <a href="<?php echo $admin_home_url ?>" STYLE="text-decoration:none;"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=1><?php echo _QXZ("HOME"); ?></a>

	<?php

	echo "<br><br>\n";
	echo "<table width=\"100%\"><tr><td></td>\n";
	echo "<!-- INTERNATIONALIZATION-LINKS-PLACEHOLDER-VICIDIAL -->\n";
	echo "</tr></table>\n";
	echo "<form name=\"vicidial_form\" id=\"vicidial_form\" action=\"$hciPAGE\" method=\"post\">\n";
	echo "<input type=\"hidden\" name=\"DB\" id=\"DB\" value=\"$DB\" />\n";
	echo "<input type=\"hidden\" name=\"stage\" id=\"stage\" value=\"chosen_campaign\" />\n";
	echo "<br /><br /><br /><center><table width=\"460px\" cellpadding=\"3\" cellspacing=\"0\" bgcolor=\"#$SSframe_background\"><tr bgcolor=\"white\">";
	echo "<td align=\"left\" valign=\"bottom\" bgcolor=\"#$SSmenu_background\" width=\"170\"><img src=\"$selected_logo\" border=\"0\" height=\"45\" width=\"170\" alt=\"Agent Screen\" /></td>";
	echo "<td align=\"center\" valign=\"middle\" bgcolor=\"#$SSmenu_background\"> <font class=\"sh_text_white\">"._QXZ("HCI: Campaign Selection")."</font> </td>";
	echo "</tr>\n";
	echo "<tr><td align=\"left\" colspan=\"2\"><font size=\"1\"> &nbsp; </font></td></tr>\n";
	echo "<tr><td align=\"right\" valign=\"top\"><font class=\"skb_text\">"._QXZ("Campaign:")."</font>  </td>";
	echo "<td align=\"left\"><font class=\"skb_text\"><span id=\"LogiNCamPaigns\">$camp_form_code</span></font></td></tr>\n";
	echo "<tr><td align=\"center\" colspan=\"2\"><input type=\"submit\" name=\"SUBMIT\" value=\""._QXZ("SUBMIT")."\" /> &nbsp; \n";
	echo "</td></tr>\n";
	echo "<tr><td align=\"left\" colspan=\"2\"><font class=\"body_tiny\"><br />"._QXZ("VERSION:")." $hci_version &nbsp; &nbsp; &nbsp; "._QXZ("BUILD:")." $build</font></td></tr>\n";
	echo "</table>\n";
	echo "</form>\n\n";
	echo "\n\n";
	echo "<br><br>\n\n";

	$stage='choose_campaign';
	$notes='';
	vicidial_ajax_log($NOW_TIME,$startMS,$link,$stage,$php_script,$PHP_AUTH_USER,$notes,$lead_id,$session_name,$stmt);

	exit;
	}
else
	{
	# See if campaign is set to AUTONEXT, and confirm system settings and user setting allow for that
	$autonext_campaign=0;
	$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and hopper_hold_inserts='AUTONEXT';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$campANs_to_parse = mysqli_num_rows($rslt);
	if ($campANs_to_parse > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$autonext_campaign =		$rowx[0];
		}
	if ( ($SShopper_hold_inserts < 2) and ($autonext_campaign > 0) )
		{$autonext_campaign=0;}
	if ( ($hci_enabled < 2) and ($autonext_campaign > 0) )
		{$autonext_campaign=0;}
	if ($autonext_campaign > 0)
		{
		$batch_size = ($batch_size * 3);
		$refresh_sec = ($refresh_sec / 2);
		}
	}


?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("HCI Screen - Campaign").": $campaign_id" ?></title>
<script language="JavaScript" src="calendar_db.js"></script>
<script language="Javascript">

var loaded_sec=0;
var hciPAGE='<?php echo $hciPAGE ?>';
var confirming_call=0;
var confirming_call_lead_id=0;
var confirming_call_phone='';
var confirmed_ct_since_refresh=0;
var autonext_campaign=<?php echo $autonext_campaign ?>;
var autonext_launch=<?php echo $autonext_campaign ?>;
var batch_size=<?php echo $batch_size ?>;
var refresh_sec=<?php echo $refresh_sec ?>;
var lead_phone_list='';
var next_lead='';
var next_phone='';
var next_lead_string='';

function StartRefresh()
	{
	if (loaded_sec < 1)
		{
		hideDiv('AlertBox');
		hideDiv('VerifyBox');
		hideDiv('CampaignSelectBox');
		hideDiv('LoadingBox');
		DashboardRefresh();
		LeadsRefresh();
		loaded_sec++;
		}
	RefreshScreen();
	}

function RefreshScreen()
	{
	loaded_sec++;

	// trigger refresh of dashboard
	DashboardRefresh();

	// trigger refresh of hopper RHOLD leads available
	LeadsRefresh();

	setTimeout("RefreshScreen()", refresh_sec);
	}

// function to gather dashboard content
function DashboardRefresh()
	{
	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{
		RTupdate_query = "stage=dashboard";
		xmlhttp.open('POST', 'hci_screen.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(RTupdate_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				document.getElementById("DashboardContent").innerHTML = xmlhttp.responseText;
		//		alert(xmlhttp.responseText);
				}
			}
		delete xmlhttp;
	//	if (DB > 0)	{document.getElementById("ajaxdebug").innerHTML = RTupdate_query;}
		}
	}


// function to gather reserved leads to be confirmed content
function LeadsRefresh()
	{
	if ( (confirming_call < 1) || (confirmed_ct_since_refresh > 10) )
		{
		var xmlhttp=false;
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		// JScript gives us Conditional compilation, we can cope with old IE versions.
		// and security blocked creation of the objects.
		 try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		 } catch (e) {
		  try {
		   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		  } catch (E) {
		   xmlhttp = false;
		  }
		 }
		@end @*/
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp) 
			{
			RTupdate_query = "stage=lead_display&batch_size=" + batch_size;
			xmlhttp.open('POST', 'hci_screen.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(RTupdate_query); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
					// parse first LEADPHONELIST line to get a list of all leads received: "0_1234_9998887766|1_1235_9998887767|..."
					LPLresponse = xmlhttp.responseText;
					var LPLresponse_array=LPLresponse.split("\n");
					var LEADPHONELIST = LPLresponse_array[0];
					var LPLmatch = new RegExp("LEADPHONELIST","g");
					if (LEADPHONELIST.match(LPLmatch))
						{
						var LPLresponse_array=LEADPHONELIST.split("LEADPHONELIST: ");
						lead_phone_list = LPLresponse_array[1];
					//	alert(lead_phone_list);

						var LPLnext_array=lead_phone_list.split("|");
						current_lead_string = LPLnext_array[0];
						var next_lead_array=current_lead_string.split("_");
						next_lead = next_lead_array[1];
						next_phone = next_lead_array[2];
					//	alert(next_lead_array[0] + " " + next_lead + " " + next_phone);
						}
					document.getElementById("LeadsContent").innerHTML = xmlhttp.responseText;
					confirmed_ct_since_refresh=0;
			//		alert(xmlhttp.responseText);
					if (autonext_launch > 0)
						{
						autonext_launch=0;
						var temp_next_phone = next_phone;
						let match = next_phone.match(/^(\d{3})(\d{3})(\d{4})$/);
						if (match) 
							{temp_next_phone = '(' + match[1] + ') ' + match[2] + '-' + match[3];}
						call_confirmation_button(next_lead,temp_next_phone);
						}
					}
				}
			delete xmlhttp;
		//	if (DB > 0)	{document.getElementById("ajaxdebug").innerHTML = RTupdate_query;}
			}
		}
	}


function LeadConfirmed()
	{
	if (confirming_call > 0)
		{
		var xmlhttp=false;
		/*@cc_on @*/
		/*@if (@_jscript_version >= 5)
		// JScript gives us Conditional compilation, we can cope with old IE versions.
		// and security blocked creation of the objects.
		 try {
		  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		 } catch (e) {
		  try {
		   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		  } catch (E) {
		   xmlhttp = false;
		  }
		 }
		@end @*/
		if (!xmlhttp && typeof XMLHttpRequest!='undefined')
			{
			xmlhttp = new XMLHttpRequest();
			}
		if (xmlhttp) 
			{
			RTupdate_query = "stage=activate_lead&lead_id=" + confirming_call_lead_id;
			xmlhttp.open('POST', 'hci_screen.php'); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(RTupdate_query); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
				//	document.getElementById("LeadsContent").innerHTML = xmlhttp.responseText;
				//	alert(xmlhttp.responseText);
					confirming_call=0;
					confirming_call_lead_id=0;
					confirming_call_phone='';

					var temp_LPL_current = "" + current_lead_string + "\\\|";
					var LPLcurrent = new RegExp(temp_LPL_current,"g");
				//	alert(temp_LPL_current + " " + LPLcurrent);
					lead_phone_list = lead_phone_list.replace(LPLcurrent, '');
					var LPLnext_array=lead_phone_list.split("|");
					current_lead_string = LPLnext_array[0];
					var next_lead_array=current_lead_string.split("_");
					next_lead = next_lead_array[1];
					next_phone = next_lead_array[2];

					if (autonext_campaign > 0)
						{
						var temp_next_phone = next_phone;
						let match = next_phone.match(/^(\d{3})(\d{3})(\d{4})$/);
						if (match) 
							{temp_next_phone = '(' + match[1] + ') ' + match[2] + '-' + match[3];}
					//	alert(next_lead + " " + temp_next_phone + " " + next_phone);

						if (document.getElementById('button_' + next_lead))
							{
							call_confirmation_button(next_lead,temp_next_phone);
							}
						else
							{
							autonext_launch=1;
							LeadsRefresh();
							}
						}
					}
				}
			delete xmlhttp;
			}
		}
	}


// switch campaign for logged-in agent
function SwitchCampaign(temp_campaign)
	{
	window.location= hciPAGE + "?stage=switch_campaign&campaign_id=" + temp_campaign;
	}

function call_confirmation_button(temp_button_lead,temp_id)
	{
	autonext_launch=0;
	confirming_call=1;
	confirming_call_lead_id = temp_button_lead;
	confirming_call_phone = temp_id;
	document.getElementById('button_' + temp_button_lead).style.backgroundColor = '#FFCC99';
	var temp_counter_debug='';
//	var temp_counter_debug = "<br>" + confirmed_ct_since_refresh + " " + loaded_sec;
	document.getElementById('VerifyBoxContent').innerHTML="<?php echo _QXZ("confirm call to") ?>: " + temp_id + temp_counter_debug;
	showDiv('VerifyBox');
	document.getElementById('verify_button').focus();
	}

function call_confirmation_verify()
	{
	// erase button span
	document.getElementById('button_span_' + confirming_call_lead_id).innerHTML="";
	document.getElementById('VerifyBox').style.visibility = 'hidden';
	confirmed_ct_since_refresh++;
	LeadConfirmed();
	}

function call_confirmation_cancel()
	{
	document.getElementById('button_' + confirming_call_lead_id).style.backgroundColor = '#CCCCCC';
	confirming_call=0;
	confirming_call_lead_id=0;
	confirming_call_phone='';
	document.getElementById('VerifyBox').style.visibility = 'hidden';

	var temp_LPL_current = "" + current_lead_string + "\\\|";
	var LPLcurrent = new RegExp(temp_LPL_current,"g");
//	alert(temp_LPL_current + " " + LPLcurrent);
	lead_phone_list = lead_phone_list.replace(LPLcurrent, '');
	var LPLnext_array=lead_phone_list.split("|");
	current_lead_string = LPLnext_array[0];
	var next_lead_array=current_lead_string.split("_");
	next_lead = next_lead_array[1];
	next_phone = next_lead_array[2];

	if (autonext_campaign > 0)
		{
		var temp_next_phone = next_phone;
		let match = next_phone.match(/^(\d{3})(\d{3})(\d{4})$/);
		if (match) 
			{temp_next_phone = '(' + match[1] + ') ' + match[2] + '-' + match[3];}
	//	alert(next_lead + " " + temp_next_phone + " " + next_phone);
		call_confirmation_button(next_lead,temp_next_phone);
		}
	}

// functions to hide and show different DIVs
function showDiv(divvar) 
	{
	if (document.getElementById(divvar))
		{
		divref = document.getElementById(divvar).style;
		divref.visibility = 'visible';
		}
	}
function hideDiv(divvar)
	{
	if (document.getElementById(divvar))
		{
		divref = document.getElementById(divvar).style;
		divref.visibility = 'hidden';
		}
	}


</script>
<link rel="stylesheet" href="calendar.css">
<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
</head>
<BODY onLoad="StartRefresh()" BGCOLOR=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>
<!-- 'FORM_LOADED' is in here because vdc_form_display calls it. ( STYLE="text-decoration:none;") //-->

<TABLE CELLPADDING=0 CELLSPACING=0 BGCOLOR=white WIDTH=1200><TR bgcolor='#<?php echo $SSstd_row1_background ?>'>
<TD ALIGN=LEFT WIDTH=113 HEIGHT=30><A HREF="<?php echo $admin_home_url ?>"><IMG SRC="<?php echo $temp_logo; ?>" WIDTH=113 HEIGHT=30 BORDER=0 BORDER=0 ALT="System logo"></A></TD>
<TD WIDTH=100 ALIGN=LEFT> &nbsp; &nbsp; <a href="<?php echo $admin_home_url ?>"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=2><?php echo _QXZ("HOME"); ?></a> &nbsp; </TD>
<TD WIDTH=729 ALIGN=CENTER><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=3> &nbsp; <B><?php echo _QXZ("HCI Screen - Campaign").": $campaign_id" ?> </B> &nbsp; </TD>
<TD WIDTH=300 ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK SIZE=2><B> &nbsp; <a href="#" onclick="showDiv('CampaignSelectBox');return false;"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK><?php echo _QXZ("Change Campaign"); ?></a> &nbsp; |  &nbsp; <a href="<?php echo "$hciPAGE?stage=LOGOUT&campaign_id=$campaign_id" ?>"><FONT FACE="ARIAL,HELVETICA" COLOR=BLACK><?php echo _QXZ("LOGOUT"); ?></a> &nbsp; </B></TD>

</TR>
</TABLE>

<input type='hidden' id='FORM_LOADED'>

	<tr>
		<td align='center' colspan='4'>

<?php




echo "<table border=0 cellpadding=0 cellspacing=0 bgcolor='#".$SSstd_row1_background."' align='center' width='1000'>\n";
echo "<tr><td align='center' colspan='4'>";
echo "</td><td align=right></td></tr>\n";



echo "</td></tr></table>\n";

$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "\n\n\n<br><br><br>\n\n";


echo "<!-- <font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font> -->";


?>
<span style="position:absolute;left:0px;top:0px;z-index:300;" id="LoadingBox">
    <table border="0" bgcolor="white" width="<?php echo $JS_browser_width ?>px" height="<?php echo $JS_browser_height ?>px"><tr><td align="center" valign="top">
 <br />
 <br />
 <font class="loading_text"><?php echo _QXZ("Loading..."); ?></font>
 <br />
 <br />
    &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <img src="../agc/images/<?php echo _QXZ("agent_loading_animation.gif"); ?>" height="206px" width="206px" alt="<?php echo _QXZ("Loading..."); ?>" />
 <br />
 <br />
    </td></tr></table>
</span>

<span style="position:absolute;left:10px;top:40px;z-index:<?php $zi++; echo $zi ?>;" id="LeadsContent"></span>

<span style="position:absolute;left:570px;top:40px;z-index:<?php $zi++; echo $zi ?>;" id="DashboardContent"></span>


<span style="position:absolute;left:700px;top:0px;z-index:<?php $zi++; echo $zi ?>;" id="CampaignSelectBox">
<table border="2" bgcolor="#666666" cellpadding="2" cellspacing="1">
<tr><td bgcolor="#f0f0f0" align="left">
<font face="arial,helvetica" size="2"><b> &nbsp; <?php echo _QXZ("Select a campaign"); ?>:</b></font>
</td></tr>
<tr><td bgcolor="#E6E6E6">
<table border="0" bgcolor="#E3E3E3" width="400">
<tr>
<td align="center" valign="top" width="50"> &nbsp; 
<br /><br />
</td>
<td align="left" valign="top"> &nbsp; 
<br /><br />
<font face="arial,helvetica" size="2">
<span id="CampaignSelectBoxContent"><b> <?php echo $camp_panel_code; ?> </b></span>
</font>
<br /><br />
<font face="arial,helvetica" size="2"><a href="#" onclick="hideDiv('CampaignSelectBox');return false;" STYLE="text-decoration:none;"> &nbsp; <?php echo _QXZ("[X] close"); ?></a></font><br />
</td>
</tr>
</table>
</td></tr>
</table>
</span>


<span style="position:absolute;left:580px;top:240px;z-index:<?php $zi++; echo $zi ?>;" id="VerifyBox">
<table border="2" bgcolor="#999999" cellpadding="2" cellspacing="1">
<tr><td bgcolor="#<?php echo $SSmenu_background; ?>" align="left">
<font face="arial,helvetica" size="2" color="white"><b> &nbsp; <?php echo _QXZ("Launch Call Confirmation"); ?></b></font>
</td></tr>
<tr><td bgcolor="#E6E6E6">
<table border="0" bgcolor="#E3E3E3" width="400">
<tr>
<td align="center" valign="top"> &nbsp; 
<br /><br />
<font face="arial,helvetica" size="2">
<span id="VerifyBoxContent"> <?php echo _QXZ("verify"); ?> </span>
</font>
<br /><br />
</td>
</tr><tr>
<td align="center" valign="top" colspan="2">
<button type="button" class="button_hci_verify_confirm" name="verify_button" id="verify_button" onclick="call_confirmation_verify();return false;"><?php echo _QXZ("Confirm"); ?></BUTTON> &nbsp; 
<button type="button" class="button_hci_verify_cancel" name="verify_cancel" id="verify_cancel" onclick="call_confirmation_cancel();return false;"><?php echo _QXZ("cancel"); ?></BUTTON>
<br /> &nbsp;
<!-- <a href="#" onclick="document.alert_form.alert_button.focus();">focus</a> -->
</td></tr>
</table>
</td></tr>
</table>
</span>


<span style="position:absolute;left:200px;top:200px;z-index:<?php $zi++; echo $zi ?>;" id="AlertBox">
<table border="2" bgcolor="#666666" cellpadding="2" cellspacing="1">
<tr><td bgcolor="#f0f0f0" align="left">
<font face="arial,helvetica" size="2"><b> &nbsp; <?php echo _QXZ("Agent Alert!"); ?></b></font>
</td></tr>
<tr><td bgcolor="#E6E6E6">
<table border="0" bgcolor="#E3E3E3" width="400">
<tr>
<td align="center" valign="top" width="50"> &nbsp; 
<br /><br />
<img src="../agc/images/<?php echo _QXZ("alert.gif"); ?>" alt="alert" border="0">
</td>
<td align="center" valign="top"> &nbsp; 
<br /><br />
<font face="arial,helvetica" size="2">
<span id="AlertBoxContent"> <?php echo _QXZ("Alert Box"); ?> </span>
</font>
<br /><br />
</td>
</tr><tr>
<td align="center" valign="top" colspan="2">
<button type="button" name="alert_button" id="alert_button" onclick="hideDiv('AlertBox');return false;"><?php echo _QXZ("OK"); ?></BUTTON>
<br /> &nbsp;
<!-- <a href="#" onclick="document.alert_form.alert_button.focus();">focus</a> -->
</td></tr>
</table>
</td></tr>
</table>
</span>

</body>
</html>

<?php

$stage='page_loaded';
$notes='';
vicidial_ajax_log($NOW_TIME,$startMS,$link,$stage,$php_script,$PHP_AUTH_USER,$notes,$lead_id,$session_name,$stmt);

exit;



##### AJAX process logging #####
function vicidial_ajax_log($NOW_TIME,$startMS,$link,$ACTION,$php_script,$user,$stage,$lead_id,$session_name,$stmt)
	{
	$endMS = microtime();
	$startMSary = explode(" ",$startMS);
	$endMSary = explode(" ",$endMS);
	$runS = ($endMSary[0] - $startMSary[0]);
	$runM = ($endMSary[1] - $startMSary[1]);
	$TOTALrun = ($runS + $runM);

	$stmt = preg_replace('/;/', '', $stmt);
	$stmt = addslashes($stmt);

	$NOW_TIME = preg_replace("/\'|\"|\\\\|;/","",$NOW_TIME);
	$startMS = preg_replace("/\'|\"|\\\\|;/","",$startMS);
	$php_script = preg_replace('/[^-\._0-9a-zA-Z]/','',$php_script);
	$ACTION = preg_replace('/[^-_0-9a-zA-Z]/','',$ACTION);
	$lead_id = preg_replace('/[^0-9]/','',$lead_id);
	$stage = preg_replace("/\'|\"|\\\\|;/","",$stage);
	$session_name = preg_replace('/[^-_0-9a-zA-Z]/','',$session_name);

	$stmtA="INSERT INTO vicidial_ajax_log set user='$user',start_time='$NOW_TIME',db_time=NOW(),run_time='$TOTALrun',php_script='$php_script',action='$ACTION',lead_id='$lead_id',stage='$stage',session_name='$session_name',last_sql=\"$stmt\";";
	$rslt=mysql_to_mysqli($stmtA, $link);

#	$ajx = fopen ("./vicidial_ajax_log.txt", "a");
#	fwrite ($ajx, $stmtA . "\n");
#	fclose($ajx);

	return 1;
	}

?>
