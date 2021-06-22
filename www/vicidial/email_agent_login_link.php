<?php
# email_agent_login_link.php
#
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script sends an email to an agent with a link that will log them
# into their vicidial account when clicked.
#
# changes:
# 200324-1543 - Initial Build
#

$version = '2.14-13';
$build = '200324-1543';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$ip = getenv("REMOTE_ADDR");
$SQLdate = date("Y-m-d H:i:s");

$DB=0;
$preview="";
$agent_id="";

if (isset($_GET["DB"])) {$DB=$_GET["DB"];}
    elseif (isset($_POST["DB"])) {$DB=$_POST["DB"];}
if (isset($_GET["preview"]))	{$preview=$_GET["preview"];}
	elseif (isset($_POST["preview"]))	{$preview=$_POST["preview"];}
if (isset($_GET["agent_id"]))	{$agent_id=$_GET["agent_id"];}
	elseif (isset($_POST["agent_id"]))	{$agent_id=$_POST["agent_id"];}

$DB = preg_replace('/[^0-9]/','',$DB);
$preview = preg_replace('/[^-_0-9a-zA-Z]/','',$preview);
$agent_id = preg_replace('/[^-_0-9a-zA-Z]/','',$agent_id);

if ( $preview == "" ) { $preview == 1; }


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$sys_settings_stmt = "SELECT use_non_latin,outbound_autodial_active,sounds_central_control_active,enable_languages,language_method,admin_screen_colors,report_default_format,allow_manage_active_lists,user_account_emails,pass_hash_enabled FROM system_settings;";
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
	$SSadmin_screen_colors =			$sys_settings_row[5];
	$SSreport_default_format =			$sys_settings_row[6];
	$SSallow_manage_active_lists =		$sys_settings_row[7];
	$user_account_emails =				$sys_settings_row[8];
	$pass_hash_enabled = 				$sys_settings_row[9];
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
	$VUselected_language =	  $row[0];
	}

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
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

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level = 9 and modify_users='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$users_auth=$row[0];

	if ($users_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to modify users");
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

#### this feature is disabled in system settings
if ( $user_account_emails == 'DISABLED')
	{
	$VDdisplayMESSAGE = _QXZ("ERROR: This feature is disabled. How did you event get here?");
	Header ("Content-type: text/html; charset=utf-8");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
	exit;
	}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");		      // HTTP/1.0

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
	$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$colors_ct = mysqli_num_rows($rslt);
	if ($colors_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$SSmenu_background =	    $row[0];
		$SSframe_background =	   $row[1];
		$SSstd_row1_background =	$row[2];
		$SSstd_row2_background =	$row[3];
		$SSstd_row3_background =	$row[4];
		$SSstd_row4_background =	$row[5];
		$SSstd_row5_background =	$row[6];
		$SSalt_row1_background =	$row[7];
		$SSalt_row2_background =	$row[8];
		$SSalt_row3_background =	$row[9];
		$SSweb_logo =			   $row[10];
		}
	}
$Mhead_color =  $SSstd_row5_background;
$Mmain_bgcolor = $SSmenu_background;
$Mhead_color =  $SSstd_row5_background;

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


echo "<html>\n";
echo "<head>\n";
echo "<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=utf-8'>\n";
echo "<!-- VERSION: <?php echo $version ?>     BUILD: <?php echo $build ?> -->\n";
echo "<title>"._QXZ("Send Agent Login As Email")."</title>\n";

##### BEGIN Set variables to make header show properly #####
$ADD =			       '999998';
$hh =				'users';
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
$admin_color =	  '#FFFF99';
$admin_font =	   'BLACK';
$admin_color =	  '#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");

### gather the data

$error_msg = "";

$user = "";
$pass = "";
$full_name = "";
$phone_login = "";
$phone_pass = "";
$email = "";

if ( $agent_id != "" )
	{
	$stmt = "SELECT user, pass, full_name, phone_login, phone_pass, email FROM vicidial_users where user='$agent_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$user_ct = mysqli_num_rows($rslt);
	if ($user_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$user = $row[0];
		$pass = $row[1];
		$full_name = $row[2];
		$phone_login = $row[3];
		$phone_pass = $row[4];
		$email = $row[5];
		}
	else
		{
		### User Not Found!!
		$error_msg .= _QXZ("Error: User Account Not Found.");
		$error_msg .= "<br /><br />";
		}
	}
else
	{
	### Agent ID was not set!!!!
	$error_msg .= _QXZ("Error: agent_id not set.");
	$error_msg .= "<br /><br />";
	}
if ( $pass_hash_enabled == 1) 
	{
	if (( $user == "" ) || ( $full_name == "" ) || ( $phone_login == "" ) || ( $phone_pass == "" ) || ( $email == "" ))
		{
		### Not all user fields are set
		$error_msg .= _QXZ("Error: Required fields not set for this user. <br /><br />Please go back and ensure User Number, Full Name, Phone Login, Phone Pass, and Email are set for this user.");
		$error_msg .= "<br /><br />";
		}
	}
else
	{
	if (( $user == "" ) || ( $pass == "" ) || ( $full_name == "" ) || ( $phone_login == "" ) || ( $phone_pass == "" ) || ( $email == "" ))
		{
		### Not all user fields are set
		$error_msg .= _QXZ("Error: Required fields not set for this user. <br /><br />Please go back and ensure User Number, Password, Full Name, Phone Login, Phone Pass, and Email are set for this user.");
		$error_msg .= "<br /><br />";
		}
	}

### Make sure the phone_login is a valid phone or phone_alias
$logins_list = "";
$phone_login_list="'$phone_login'";
$stmt = "SELECT logins_list FROM phones_alias where alias_id='$phone_login';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$alias_ct = mysqli_num_rows($rslt);
if ( $alias_ct > 0 )
	{
	$row=mysqli_fetch_row($rslt);
	$logins_list = $row[0];	
	$phone_login_list = preg_replace(',' ,'\',\'' ,"$phone_login,$logins_list");
	}
	
$is_webphone = "";
$stmt = "SELECT login, pass, is_webphone FROM phones where login IN ($phone_login_list) and active='Y';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$phone_ct = mysqli_num_rows($rslt);
if ( $phone_ct > 0 )
	{
	$phones_array=mysqli_fetch_all($rslt);
	}
else 
	{
	if ( $alias_ct == 0 )
		{
		#### Phone login does not exist in phones or phones_alias!
		$error_msg .= _QXZ("Error: Phone Login does not exist. <br /><br />Please go back and ensure that the Phone Login set for this user is an Active Phone or Phone Alias.");
		$error_msg .= "<br /><br />";
		}
	}

#$hostname = parse_url($PHP_SELF, PHP_URL_HOST);
$hostname = $_SERVER['HTTP_HOST'];
$referrer = $_SERVER['HTTP_REFERER'];
$from_email = "no-reply@$hostname";

$send_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$send_url = trim(strtok($send_url,'?'));
$send_url .= "?preview=0&agent_id=$agent_id";

$login_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$hostname/agc/vicidial.php?VD_login=$user&pl=$phone_login&pp=$phone_pass";

$subject = _QXZ("Your $hostname agent login");

$body = "";
$body .= _QXZ("Hello ");
$body .= "$full_name,<br /><br />";
$body .= _QXZ("Here is your agent login information for ");
$body .= "$hostname:<br /><br />";
$body .= _QXZ("Login URL");
$body .= ": <a href=\"$login_link\">$login_link</a><br />";
$body .= _QXZ("User Login");
$body .= ": $user<br />";

if (( $user_account_emails == 'SEND_WITH_PASS' ) && ( $pass_hash_enabled == 0 ))
	{
	$body .= _QXZ("User Password");
	$body .= ": $pass<br /><br />";
	}
else 
	{
	$body .= "<br />";
	$body .= _QXZ("Contact your manager for your User Password.");
	$body .= "<br /><br />";
	}
$body .= _QXZ("You should bookmark the Login URL in your web browser.");

echo "<table width=$page_width bgcolor=#E6E6E6 cellpadding=2 cellspacing=0>\n";
echo "<tr bgcolor='#E6E6E6'>\n";
echo "<td align=left>\n";
echo "<font face='ARIAL,HELVETICA' size=2>\n";
echo "<b> &nbsp; "._QXZ("Send Agent Login As Email.")." &nbsp; </b>\n";
echo "</font>\n";
echo "</td>\n";
echo "<td align=right><font face='ARIAL,HELVETICA' size=2> &nbsp; </font></td>\n";
echo "</tr>\n";
echo "<tr bgcolor='#$SSframe_background'><td align=left colspan=2><font face='ARIAL,HELVETICA' color=black size=3><br /><br /> \n";

if ($preview == 1) 
	{
	##### Preview the email
	if ( $error_msg == "" ) 
		{
		echo _QXZ("Here is the email you are about to send to ")." $full_name: &nbsp; <br /><br /></td><td>&nbsp;</td></tr>\n";
		echo "<tr bgcolor='#FFFFFF'><td align=left colspan=2><font face='ARIAL,HELVETICA' color=black size=3>\n";
		echo _QXZ("TO").": $email<br /><br />";
		echo _QXZ("FROM").": $from_email<br /><br />";
		echo _QXZ("SUBJECT").": $subject<br /><br />";
		echo _QXZ("BODY").":<br />$body</font></td><td>&nbsp;</td></tr>";

		echo "<tr bgcolor='#$SSframe_background'><td align=left colspan=2><font face='ARIAL,HELVETICA' color=black size=3><br/><br /><br />";
		echo _QXZ("If you are positive you want to send out this email click ");
		echo "<a href='$send_url'>";
		echo _QXZ("here");
		echo "</a>, ";
		echo _QXZ("otherwise you can click ");
		echo "<a href=\"$referrer\">";
		echo _QXZ(" here ");
		echo "</a>";
		echo _QXZ("to go back.");
		echo "<td>&nbsp;</td></tr>";
		}
	else
		{
		echo "$error_msg";
		echo "<br /><br >";
		echo _QXZ("Click ");
		echo "<a href=\"$referrer\">";
		echo _QXZ(" here ");
		echo "</a>";
		echo _QXZ("to go back.");
		echo "<td>&nbsp;</td></tr>";
		}
	}
else 
	{
	if ( $error_msg == "" ) 
		{
		##### Send the email
	
		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
 
		// Create email headers
		$headers .= 'From: ' . $from_email . "\r\n" . 'Reply-To: ' . $from_email . "\r\n" . 'X-Mailer: PHP/' . phpversion();

		$body = "<html><body>" . $body . "</body></html>";
	
		$success = mail($email, $subject, $body, $headers );
		if ($success)
			{
			$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='OTHER', record_id='$agent_id', event_code='USER EMAIL LINK SENT', event_sql=\"\", event_notes='$full_name ($email)';";
			echo _QXZ("Your email to ") . "<a href=\"admin.php?ADD=3&user=$agent_id\">$full_name ($email)</a>" . _QXZ(" has been sent successfully.");
			}
		else
			{
			$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='OTHER', record_id='$agent_id', event_code='USER EMAIL LINK FAILED', event_sql=\"\", event_notes='$full_name ($email)';";
			$error_msg = error_get_last()['message'];
			echo "error_msg";
			}

		### LOG INSERTION Admin Log Table ###
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		}
	}
echo "</td></tr>\n";


echo "</td></tr></table>\n";
?>
