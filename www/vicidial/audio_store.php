<?php
# audio_store.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# Central Audio Storage script
# 
# CHANGES
# 90511-1325 - First build
# 90618-0640 - Fix for users going through proxy or tunnel
# 100401-1037 - remove spaces and special characters from filenames, admin log uploads
# 110922-2331 - Added modify_audiostore user option for access
# 111122-1332 - Added more filename filtering
# 120525-0739 - Added yet more filename filtering
# 120529-1345 - Filename filter fix
# 120531-1747 - Another filtering fix
# 121019-0816 - Added audio file delete process
# 121129-1620 - Hide delete option text if not allowed
# 130610-1052 - Finalized changing of all ereg instances to preg
# 130620-1729 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2001 - Changed to mysqli PHP functions
# 141007-2042 - Finalized adding QXZ translation to all admin files
# 141229-2052 - Added code for on-the-fly language translations display
# 160330-1550 - navigation changes and fixes, added force_allow var
# 160508-0139 - Added screen colors feature
# 160613-1002 - Added feature to copy recordings to a new filename
# 170301-1650 - Added validation that sounds web dir exists
# 170409-1555 - Added IP List validation code
# 170602-1042 - Prevent non-wav/gsm files from being uploaded
# 170630-1440 - Require modify_audiostore user permissions to access this page
# 180508-0115 - Added new help display
# 180618-2300 - Modified calls to audio file chooser function
# 201002-1536 - Allowed for secure sounds_web_server setting
# 210321-0131 - Added classAudioFile PHP library for WAV file format validation
# 210322-1220 - Added checking of .wav files for asterisk-compatible format
# 220120-0927 - Added audio_store_GSM_allowed option. Disable GSM file uploads by default
# 220222-2348 - Added allow_web_debug system setting
#

$version = '2.14-27';
$build = '220222-2348';

$MT[0]='';

require("dbconnect_mysqli.php");
require("functions.php");
require ('classAudioFile.php');

$audio_store_GSM_allowed=0;
if (file_exists('options.php'))
	{
	require('options.php');
	}

$server_name = getenv("SERVER_NAME");
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$audiofile=$_FILES["audiofile"];
	$AF_orig = $_FILES['audiofile']['name'];
	$AF_path = $_FILES['audiofile']['tmp_name'];
if (isset($_GET["submit_file"]))			{$submit_file=$_GET["submit_file"];}
	elseif (isset($_POST["submit_file"]))	{$submit_file=$_POST["submit_file"];}
if (isset($_GET["delete_file"]))			{$delete_file=$_GET["delete_file"];}
	elseif (isset($_POST["delete_file"]))	{$delete_file=$_POST["delete_file"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["overwrite"]))				{$overwrite=$_GET["overwrite"];}
	elseif (isset($_POST["overwrite"]))		{$overwrite=$_POST["overwrite"];}
if (isset($_GET["action"]))					{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))		{$action=$_POST["action"];}
if (isset($_GET["audio_server_ip"]))			{$audio_server_ip=$_GET["audio_server_ip"];}
	elseif (isset($_POST["audio_server_ip"]))	{$audio_server_ip=$_POST["audio_server_ip"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["audiofile_name"]))				{$audiofile_name=$_GET["audiofile_name"];}
	elseif (isset($_POST["audiofile_name"]))	{$audiofile_name=$_POST["audiofile_name"];}
if (isset($_GET["master_audiofile"]))			{$master_audiofile=$_GET["master_audiofile"];}
	elseif (isset($_POST["master_audiofile"]))	{$master_audiofile=$_POST["master_audiofile"];}
if (isset($_GET["new_audiofile"]))			{$new_audiofile=$_GET["new_audiofile"];}
	elseif (isset($_POST["new_audiofile"]))	{$new_audiofile=$_POST["new_audiofile"];}
if (isset($_FILES["audiofile"]))			{$audiofile_name=$_FILES["audiofile"]['name'];}
if (isset($_GET["lead_file"]))				{$lead_file=$_GET["lead_file"];}
	elseif (isset($_POST["lead_file"]))		{$lead_file=$_POST["lead_file"];}
if (isset($_GET["force_allow"]))			{$force_allow=$_GET["force_allow"];}
	elseif (isset($_POST["force_allow"]))	{$force_allow=$_POST["force_allow"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,sounds_central_control_active,sounds_web_server,sounds_web_directory,outbound_autodial_active,enable_languages,language_method,active_modules,contacts_enabled,allow_emails,qc_features_active,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$ss_conf_ct = mysqli_num_rows($rslt);
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =						$row[0];
	$sounds_central_control_active =	$row[1];
	$sounds_web_server =				$row[2];
	$sounds_web_directory =				$row[3];
	$SSoutbound_autodial_active =		$row[4];
	$SSenable_languages =				$row[5];
	$SSlanguage_method =				$row[6];
	$SSactive_modules =					$row[7];
	$SScontacts_enabled =				$row[8];
	$SSemail_enabled =					$row[9];
	$SSqc_features_active =				$row[10];
	$SSallow_web_debug =				$row[11];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$action = preg_replace('/[^-_0-9a-zA-Z]/','',$action);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/','',$SUBMIT);
$overwrite = preg_replace('/[^-_0-9a-zA-Z]/','',$overwrite);
$force_allow = preg_replace('/[^-_0-9a-zA-Z]/','',$force_allow);

# Variables filter further down in the code
#	$audiofile_name

if ($non_latin < 1)
	{
	$delete_file = preg_replace('/[^-\._0-9a-zA-Z]/','',$delete_file);
	$submit_file = preg_replace('/[^-\._0-9a-zA-Z]/','',$submit_file);
	$master_audiofile = preg_replace('/[^-\._0-9a-zA-Z]/','',$master_audiofile);
	$new_audiofile = preg_replace('/[^-\._0-9a-zA-Z]/','',$new_audiofile);
	$lead_file = preg_replace('/[^-\._0-9a-zA-Z]/','',$lead_file);
	$audio_server_ip = preg_replace('/[^-\.\:\_0-9a-zA-Z]/','',$audio_server_ip);
	}
else
	{
	$delete_file = preg_replace('/[^-\._0-9\p{L}]/u','',$delete_file);
	$submit_file = preg_replace('/[^-\._0-9\p{L}]/u','',$submit_file);
	$master_audiofile = preg_replace('/[^-\._0-9\p{L}]/u','',$master_audiofile);
	$new_audiofile = preg_replace('/[^-\._0-9\p{L}]/u','',$new_audiofile);
	$lead_file = preg_replace('/[^-\._0-9\p{L}]/u','',$lead_file);
	$audio_server_ip = preg_replace('/[^-\.\:\_0-9\p{L}]/u','',$audio_server_ip);
	}

### check if sounds server matches this server IP, if not then exit with an error
$sounds_web_server = str_replace(array('http://','https://'), '', $sounds_web_server);
if ( ( ( (strlen($sounds_web_server)) != (strlen($server_name)) ) or (!preg_match("/$sounds_web_server/i",$server_name) ) ) and ($force_allow!='FORCED') )
	{
	echo _QXZ("ERROR").": "._QXZ("server")."($server_name) "._QXZ("does not match sounds web server ip")."($sounds_web_server)\n";
	exit;
	}

if (preg_match("/;|:|\/|\^|\[|\]|\"|\'|\*/",$AF_orig))
	{
	echo _QXZ("ERROR").": "._QXZ("Invalid File Name").": $AF_orig\n";
	exit;
	}

### check if web directory exists, if not generate one
if (strlen($sounds_web_directory) < 30)
	{
	$sounds_web_directory = '';
	$possible = "0123456789cdfghjkmnpqrstvwxyz";  
	$i = 0; 
	$length = 30;
	while ($i < $length) 
		{ 
		$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
		$sounds_web_directory .= $char;
		$i++;
		}
	mkdir("$WeBServeRRooT/$sounds_web_directory");
	chmod("$WeBServeRRooT/$sounds_web_directory", 0766);
	if ($DB > 0) {echo "$WeBServeRRooT/$sounds_web_directory\n";}

	$stmt="UPDATE system_settings set sounds_web_directory='$sounds_web_directory';";
	$rslt=mysql_to_mysqli($stmt, $link);
	echo _QXZ("NOTICE").": "._QXZ("new web directory created")."\n";
	}

if (!file_exists("$WeBServeRRooT/$sounds_web_directory")) 
	{
	echo _QXZ("ERROR").": "._QXZ("audio store web directory does not exist").": $WeBServeRRooT/$sounds_web_directory\n";
	exit;
	}

### get list of all servers, if not one of them, then force authentication check
$stmt = "SELECT server_ip FROM servers;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$sv_conf_ct = mysqli_num_rows($rslt);
$i=0;
$server_ips ='|';
while ($sv_conf_ct > $i)
	{
	$row=mysqli_fetch_row($rslt);
	$server_ips .=	"$row[0]|";
	$i++;
	}

$user_set=0;
$formIPvalid=0;
if (strlen($audio_server_ip) > 6)
	{
	if (preg_match("/\|$audio_server_ip\|/", $server_ips))
		{$formIPvalid=1;}
	}
$ip = getenv("REMOTE_ADDR");

if ( (!preg_match("/\|$ip\|/", $server_ips)) and ($formIPvalid < 1) )
	{
	$user_set=1;
	$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
	$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
	if ($non_latin < 1)
		{
		$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
		$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
		}
	else
		{
		$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
		$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
		}

	$stmt="SELECT selected_language,qc_enabled from vicidial_users where user='$PHP_AUTH_USER';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$sl_ct = mysqli_num_rows($rslt);
	if ($sl_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$VUselected_language =		$row[0];
		$qc_auth =					$row[1];
		}

	$auth=0;
	$reports_auth=0;
	$admin_auth=0;
	$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1,0);
	if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
		{
		$auth=1;
		}

	if ($auth > 0)
		{
		$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and (modify_audiostore='1');";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$row=mysqli_fetch_row($rslt);
		$admin_auth=$row[0];

		if ($admin_auth < 1)
			{
			$VDdisplayMESSAGE = _QXZ("You are not allowed to upload audio files");
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

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 8 and ( (ast_admin_access='1') and (modify_audiostore='1') )";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$auth_delete=$row[0];
	}

$delete_message='';
### delete a file from the audio store
if ( ($action == "DELETE") and ($auth_delete > 0) )
	{
	if (strlen($delete_file) > 0)
		{
		$gsm='.gsm';
		$wav='.wav';
		unlink("$WeBServeRRooT/$sounds_web_directory/$delete_file$gsm");
		unlink("$WeBServeRRooT/$sounds_web_directory/$delete_file$wav");

		$stmt="UPDATE servers SET sounds_update='Y',audio_store_purge=CONCAT(audio_store_purge,\"$delete_file\\n\");";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		$stmt="UPDATE system_settings SET audio_store_purge=CONCAT(audio_store_purge,\"$delete_file\\n\");";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);

		$delete_message = _QXZ("AUDIO FILE SET FOR DELETION").": $delete_file\n";
		}
	}



### list all files in sounds web directory
if ($action == "LIST")
	{
	$i=0;
	$filename_sort=$MT;
	$dirpath = "$WeBServeRRooT/$sounds_web_directory";
	$dh = opendir($dirpath);
	while (false !== ($file = readdir($dh))) 
		{
		# Do not list subdirectories
		if ( (!is_dir("$dirpath/$file")) and (preg_match('/\.wav$|\.gsm$/', $file)) )
			{
			if (file_exists("$dirpath/$file")) 
				{
				$file_names[$i] = $file;
				$file_epoch[$i] = filemtime("$dirpath/$file");
				$file_dates[$i] = date ("Y-m-d H:i:s.", filemtime("$dirpath/$file"));
				$file_sizes[$i] = filesize("$dirpath/$file");
				$filename_sort[$i] = $file . "----------" . $i . "----------" . $file_sizes[$i];
				$i++;
				}
			}
		}
	closedir($dh);

	sort($filename_sort);

	sleep(1);

	$k=0;
	while($k < $i)
		{
		$filename_split = explode('----------',$filename_sort[$k]);
		$m = $filename_split[1];
		$size = $filename_split[2];
		$NOWsize = filesize("$dirpath/$file_names[$m]");
		if ($size == $NOWsize)
			{
			echo "$k\t$file_names[$m]\t$file_dates[$m]\t$file_sizes[$m]\t$file_epoch[$m]\n";
			}
		$k++;
		}
	exit;
	}


##### BEGIN go through all Audio Store WAV files and validate for asterisk compatibility #####
$stmt = "SELECT count(*) FROM audio_store_details where audio_format='wav' and wav_asterisk_valid='';";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$row=mysqli_fetch_row($rslt);
$wuv_ct = $row[0];
if ( ($wuv_ct > 0) or ($action == "ALL_WAV_VALIDATION") )
	{
	if ($DB) {echo "Starting WAV file validation process...\n";}
	$i=0;
	$filename_sort=$MT;
	$dirpath = "$WeBServeRRooT/$sounds_web_directory";
	$dh = opendir($dirpath);
	while (false !== ($file = readdir($dh))) 
		{
		# Do not list subdirectories
		if ( (!is_dir("$dirpath/$file")) and (preg_match('/\.wav$|\.gsm$/', $file)) )
			{
			if (file_exists("$dirpath/$file")) 
				{
				$file_names[$i] = $file;
				$file_epoch[$i] = filemtime("$dirpath/$file");
				$file_dates[$i] = date ("Y-m-d H:i:s.", filemtime("$dirpath/$file"));
				$file_sizes[$i] = filesize("$dirpath/$file");
				$filename_sort[$i] = $file . "----------" . $i . "----------" . $file_sizes[$i];
				$i++;
				}
			}
		}
	closedir($dh);

	sort($filename_sort);

	sleep(1);

	$wav_valid=0;
	$wav_invalid=0;
	$not_wav=0;
	$k=0;
	while($k < $i)
		{
		$filename_split = explode('----------',$filename_sort[$k]);
		$m = $filename_split[1];
		$size = $filename_split[2];
		$audio_filesize = filesize("$dirpath/$file_names[$m]");
		if ($size == $audio_filesize)
			{
			$audio_filename = $file_names[$m];
			$audio_epoch = date("U");
			$wav_format_details='';
			$wav_asterisk_valid='NA';

			if (preg_match("/\.wav$/", $audio_filename))
				{
				$audio_format='wav';
				$audio_length = ($audio_filesize / 16000);

				$AF = new AudioFile;
				$AF->loadFile("$dirpath/$file_names[$m]");

				$wav_type = $AF->wave_type;
				$wav_compression = $AF->getCompression ($AF->wave_compression);
				$wav_channels = $AF->wave_channels;
				$wav_framerate = $AF->wave_framerate;
				$wav_bits=$AF->wave_bits;
				$audio_length=number_format ($AF->wave_length,"0");
				$invalid_wav=0;

				if (!preg_match('/^wav/i',$wav_type)) {$invalid_wav++;}
				if (!preg_match('/^pcm/i',$wav_compression)) {$invalid_wav++;}
				if ($wav_channels > 1) {$invalid_wav++;}
				if ( ($wav_framerate > 8000) or ($wav_framerate < 8000) ) {$invalid_wav++;}
				if ( ($wav_bits > 16) or ($wav_bits < 16) ) {$invalid_wav++;}

				$wav_format_details = "$wav_type   channels: $wav_channels   framerate: $wav_framerate   bits: $wav_bits   length: $audio_length   compression: $wav_compression";

				if ($invalid_wav > 0)
					{
					$wav_asterisk_valid='BAD';
					$wav_invalid++;
					}
				else
					{
					$wav_asterisk_valid='GOOD';
					$wav_valid++;
					}

				$stmt="INSERT IGNORE INTO audio_store_details SET audio_filename='$audio_filename',audio_format='$audio_format',audio_filesize='$audio_filesize',audio_epoch='$audio_epoch',audio_length='$audio_length',wav_format_details='$wav_format_details',wav_asterisk_valid='$wav_asterisk_valid' ON DUPLICATE KEY UPDATE audio_filesize='$audio_filesize',audio_epoch='$audio_epoch',audio_length='$audio_length',wav_format_details='$wav_format_details',wav_asterisk_valid='$wav_asterisk_valid';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);
				$affected_rowsX = mysqli_affected_rows($link);
				}
			else
				{
				$not_wav++;
				}

			if ($DB) {echo "$k\t$file_names[$m]\t$wav_asterisk_valid\n";}
			}
		$k++;
		}
	
	if ($DB) {echo "SUMMARY: WAV VALID: $wav_valid   WAV INVALID: $wav_invalid   NOT WAV: $not_wav\n";}
	}
##### END go through all Audio Store WAV files and validate for asterisk compatibility #####



### upload audio file from server to webserver
# curl 'http://10.0.0.4/vicidial/audio_store.php?action=AUTOUPLOAD' -F "audiofile=@/var/lib/asterisk/sounds/beep.gsm"
if ($action == "AUTOUPLOAD")
	{
	if ($audiofile)
		{
		$AF_path = preg_replace("/ /",'\ ',$AF_path);
		$AF_path = preg_replace("/@/",'\@',$AF_path);
		$AF_path = preg_replace("/\(/",'\(',$AF_path);
		$AF_path = preg_replace("/\)/",'\)',$AF_path);
		$AF_path = preg_replace("/\#/",'\#',$AF_path);
		$AF_path = preg_replace("/\&/",'\&',$AF_path);
		$AF_path = preg_replace("/\*/",'\*',$AF_path);
		$AF_path = preg_replace("/\!/",'\!',$AF_path);
		$AF_path = preg_replace("/\%/",'\%',$AF_path);
		$AF_path = preg_replace("/\^/",'\^',$AF_path);
		$audiofile_name = preg_replace("/ /",'',$audiofile_name);
		$audiofile_name = preg_replace("/@/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\(/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\)/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\#/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\&/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\*/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\!/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\%/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\^/",'',$audiofile_name);
		if (preg_match("/\.wav$|\.gsm$/", $audiofile_name))
			{
			copy($AF_path, "$WeBServeRRooT/$sounds_web_directory/$audiofile_name");
			chmod("$WeBServeRRooT/$sounds_web_directory/$audiofile_name", 0766);

			echo _QXZ("SUCCESS").": $audiofile_name "._QXZ("uploaded")."     "._QXZ("size").":" . filesize("$WeBServeRRooT/$sounds_web_directory/$audiofile_name") . "\n";
			exit;
			}
		else
			{
			echo _QXZ("ERROR").": "._QXZ("only wav and gsm files are allowed in the audio store")."\n";
			}
		}
	else
		{
		echo _QXZ("ERROR").": "._QXZ("no file uploaded")."\n";
		}
	exit;
	}


### copy audio file to new name on webserver
if ($action == "COPYFILE")
	{
	if ($DB) {echo "COPYFILE: |$new_audiofile|$master_audiofile|\n";}
	if ( (strlen($new_audiofile)>0) and (strlen($master_audiofile)>0) )
		{
		$master_audiofile = preg_replace("/ /",'\ ',$master_audiofile);
		$master_audiofile = preg_replace("/@/",'\@',$master_audiofile);
		$master_audiofile = preg_replace("/\(/",'\(',$master_audiofile);
		$master_audiofile = preg_replace("/\)/",'\)',$master_audiofile);
		$master_audiofile = preg_replace("/\#/",'\#',$master_audiofile);
		$master_audiofile = preg_replace("/\&/",'\&',$master_audiofile);
		$master_audiofile = preg_replace("/\*/",'\*',$master_audiofile);
		$master_audiofile = preg_replace("/\!/",'\!',$master_audiofile);
		$master_audiofile = preg_replace("/\%/",'\%',$master_audiofile);
		$master_audiofile = preg_replace("/\^/",'\^',$master_audiofile);
		$master_audiofile = preg_replace("/\"/",'\^',$master_audiofile);
		$new_audiofile = preg_replace("/ /",'',$new_audiofile);
		$new_audiofile = preg_replace("/@/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\(/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\)/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\#/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\&/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\*/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\!/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\%/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\^/",'',$new_audiofile);
		$new_audiofile = preg_replace("/\"/",'',$new_audiofile);

		$copied=0;
		$suffix='.wav';
		if (file_exists("$WeBServeRRooT/$sounds_web_directory/$master_audiofile$suffix"))
			{
			copy("$WeBServeRRooT/$sounds_web_directory/$master_audiofile$suffix", "$WeBServeRRooT/$sounds_web_directory/$new_audiofile$suffix");
			chmod("$WeBServeRRooT/$sounds_web_directory/$new_audiofile$suffix", 0766);

			$new_filesize = filesize("$WeBServeRRooT/$sounds_web_directory/$new_audiofile$suffix");
			$copy_message = _QXZ("SUCCESS").": $new_audiofile$suffix "._QXZ("copied")."     "._QXZ("size").": $new_filesize "._QXZ("from")." $master_audiofile$suffix\n";
			$copied++;
			}

		$suffix='.gsm';
		if (file_exists("$WeBServeRRooT/$sounds_web_directory/$master_audiofile$suffix"))
			{
			copy("$WeBServeRRooT/$sounds_web_directory/$master_audiofile$suffix", "$WeBServeRRooT/$sounds_web_directory/$new_audiofile$suffix");
			chmod("$WeBServeRRooT/$sounds_web_directory/$new_audiofile$suffix", 0766);

			$new_filesize = filesize("$WeBServeRRooT/$sounds_web_directory/$new_audiofile$suffix");
			$copy_message = _QXZ("SUCCESS").": $new_audiofile$suffix "._QXZ("copied")."     "._QXZ("size").": $new_filesize "._QXZ("from")." $master_audiofile$suffix\n";
			$copied++;
			}

		if ($copied < 1)
			{
			$copy_message = _QXZ("ERROR").": "._QXZ("original file not found").": |$master_audiofile|\n";
			}
		else
			{
			$stmt="UPDATE servers SET sounds_update='Y';";
			$rslt=mysql_to_mysqli($stmt, $link);

			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='AUDIOSTORE', event_type='COPY', record_id='manualupload', event_code='$new_audiofile $new_filesize', event_sql=\"$SQL_log\", event_notes='NEW: $new_audiofile   ORIGINAL: $master_audiofile';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		}
	else
		{
		$copy_message = _QXZ("ERROR").": "._QXZ("you must define an original and new filename").": |$master_audiofile|$new_audiofile|\n";
		}
	}



?>
<html>
<head>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<!-- VERSION: <?php echo $version ?>     BUILD: <?php echo $build ?> -->

<link rel="stylesheet" type="text/css" href="vicidial_stylesheet.php">
<script language="JavaScript" src="help.js"></script>
<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>

<title><?php echo _QXZ("ADMINISTRATION"); ?>: <?php echo _QXZ("Audio Store"); ?>
<?php


if ($user_set < 1)
	{
	$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
	$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	}
##### BEGIN Set variables to make header show properly #####
$ADD =					'311111111111111';
$hh =					'admin';
$LOGast_admin_access =	'1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$admin_color =		'#FFFF99';
$admin_font =		'BLACK';
$admin_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");

?>
<TABLE WIDTH=<?php echo $page_width ?> BGCOLOR=#E6E6E6 cellpadding=2 cellspacing=0><TR BGCOLOR=#E6E6E6><TD ALIGN=LEFT><FONT FACE="ARIAL,HELVETICA" SIZE=2><B> &nbsp; <?php echo _QXZ("Audio Store"); ?></TD><TD ALIGN=RIGHT><FONT FACE="ARIAL,HELVETICA" SIZE=2><B> &nbsp; </TD></TR>

<?php 

echo "<TR BGCOLOR=\"#$SSframe_background\"><TD ALIGN=LEFT COLSPAN=2><img src=\"images/icon_audiostore.png\" width=42 height=42 align=left> <FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3><B> &nbsp; \n";

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$FILE_datetime = $STARTtime;

$date = date("r");
$browser = getenv("HTTP_USER_AGENT");
$script_name = getenv("SCRIPT_NAME");
$server_name = getenv("SERVER_NAME");
$server_port = getenv("SERVER_PORT");
if (preg_match("/443/i",$server_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
$admDIR = "$HTTPprotocol$server_name:$server_port$script_name";
$admDIR = preg_replace('/audio_store\.php/i', '',$admDIR);
$admSCR = 'admin.php';
# $NWB = " &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";

$secX = date("U");
$pulldate0 = "$year-$mon-$mday $hour:$min:$sec";

echo "$delete_message";
echo "$copy_message";

if ($action == "MANUALUPLOAD")
	{
	if ($audiofile) 
		{
		$AF_path = preg_replace("/ /",'\ ',$AF_path);
		$AF_path = preg_replace("/@/",'\@',$AF_path);
		$AF_path = preg_replace("/\(/",'\(',$AF_path);
		$AF_path = preg_replace("/\)/",'\)',$AF_path);
		$AF_path = preg_replace("/\#/",'\#',$AF_path);
		$AF_path = preg_replace("/\&/",'\&',$AF_path);
		$AF_path = preg_replace("/\*/",'\*',$AF_path);
		$AF_path = preg_replace("/\!/",'\!',$AF_path);
		$AF_path = preg_replace("/\%/",'\%',$AF_path);
		$AF_path = preg_replace("/\^/",'\^',$AF_path);
		$audiofile_name = preg_replace("/ /",'',$audiofile_name);
		$audiofile_name = preg_replace("/@/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\(/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\)/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\#/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\&/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\*/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\!/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\%/",'',$audiofile_name);
		$audiofile_name = preg_replace("/\^/",'',$audiofile_name);
		if ( (preg_match("/\.wav$/", $audiofile_name)) or ( (preg_match("/\.gsm$/", $audiofile_name)) and ($audio_store_GSM_allowed > 0) ) )
			{
			copy($AF_path, "$WeBServeRRooT/$sounds_web_directory/$audiofile_name");
			chmod("$WeBServeRRooT/$sounds_web_directory/$audiofile_name", 0766);
			
			$audio_epoch = date("U");
			$audio_format='gsm';
			$audio_filesize = filesize("$WeBServeRRooT/$sounds_web_directory/$audiofile_name");
			$audio_length = ($audio_filesize / 1650);
			$wav_format_details='';
			$wav_asterisk_valid='NA';
			echo "<BR>"._QXZ("SUCCESS").": $audiofile_name "._QXZ("uploaded")."     "._QXZ("size").": $audio_filesize<BR><BR>\n";

			if (preg_match("/\.wav$/", $audiofile_name))
				{
				$audio_format='wav';
				$audio_length = ($audio_filesize / 16000);

				$AF = new AudioFile;
				$AF->loadFile("$WeBServeRRooT/$sounds_web_directory/$audiofile_name");

				$wav_type = $AF->wave_type;
				$wav_compression = $AF->getCompression ($AF->wave_compression);
				$wav_channels = $AF->wave_channels;
				$wav_framerate = $AF->wave_framerate;
				$wav_bits=$AF->wave_bits;
				$audio_length=number_format ($AF->wave_length,"0");
				$invalid_wav=0;

				if (!preg_match('/^wav/i',$wav_type)) {$invalid_wav++;}
				if (!preg_match('/^pcm/i',$wav_compression)) {$invalid_wav++;}
				if ($wav_channels > 1) {$invalid_wav++;}
				if ( ($wav_framerate > 8000) or ($wav_framerate < 8000) ) {$invalid_wav++;}
				if ( ($wav_bits > 16) or ($wav_bits < 16) ) {$invalid_wav++;}

				$wav_format_details = "$wav_type   channels: $wav_channels   framerate: $wav_framerate   bits: $wav_bits   length: $audio_length   compression: $wav_compression";

				if ($invalid_wav > 0)
					{
					$wav_asterisk_valid='BAD';
					echo " &nbsp; <BR><BR><font color=red>"._QXZ("INVALID WAV FILE FORMAT").": ($audiofile_name)</font><BR> &nbsp; </FONT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>$wav_type &nbsp; "._QXZ("channels").": $wav_channels &nbsp; "._QXZ("framerate").": $wav_framerate &nbsp; "._QXZ("bits").": $wav_bits &nbsp; "._QXZ("compression").": $wav_compression<BR><BR><BR>\n";
					}
				else
					{
					$wav_asterisk_valid='GOOD';
					echo " &nbsp; </FONT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=1>"._QXZ("WAV FILE FORMAT VALIDATED").": $wav_type &nbsp; "._QXZ("channels").": $wav_channels &nbsp; "._QXZ("framerate").": $wav_framerate &nbsp; "._QXZ("bits").": $wav_bits &nbsp; "._QXZ("compression").": $wav_compression<BR><BR>\n";
					}
				echo "</FONT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3>";
				}

			$stmt="INSERT IGNORE INTO audio_store_details SET audio_filename='$audiofile_name',audio_format='$audio_format',audio_filesize='$audio_filesize',audio_epoch='$audio_epoch',audio_length='$audio_length',wav_format_details='$wav_format_details',wav_asterisk_valid='$wav_asterisk_valid' ON DUPLICATE KEY UPDATE audio_filesize='$audio_filesize',audio_epoch='$audio_epoch',audio_length='$audio_length',wav_format_details='$wav_format_details',wav_asterisk_valid='$wav_asterisk_valid';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rowsX = mysqli_affected_rows($link);

			$stmt="UPDATE servers SET sounds_update='Y';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rowsY = mysqli_affected_rows($link);

			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='AUDIOSTORE', event_type='LOAD', record_id='manualupload', event_code='$audiofile_name $audio_filesize', event_sql=\"$SQL_log\", event_notes='$audiofile_name $AF_path $AF_orig   Invalid: $invalid_wav $wav_type &nbsp; "._QXZ("channels").": $wav_channels &nbsp; "._QXZ("framerate").": $wav_framerate &nbsp; "._QXZ("bits").": $wav_bits &nbsp; "._QXZ("compression").": $wav_compression   "._QXZ("length").": $audio_length   $affected_rowsX|$affected_rowsY';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			}
		else
			{
			if ( (preg_match("/\.gsm$/", $audiofile_name)) and ($audio_store_GSM_allowed < 1) )
				{echo _QXZ("ERROR").": "._QXZ("only wav files are allowed in the audio store")."\n";}
			else
				{echo _QXZ("ERROR").": "._QXZ("only wav and gsm files are allowed in the audio store")."\n";}
			}
		}
	else
		{
		echo _QXZ("ERROR").": "._QXZ("no file uploaded")."\n";
		}
	}

?>


<form action=<?php echo $PHP_SELF ?> method=post enctype="multipart/form-data">
<input type=hidden name=action value="MANUALUPLOAD">
<input type=hidden name=DB value="<?php echo $DB; ?>">
<input type=hidden name=force_allow value="<?php echo $force_allow; ?>">

<table align=center width="700" border=0 cellpadding=5 cellspacing=0 bgcolor=#<?php echo $SSstd_row1_background; ?>>
  <tr>
	<td align=right width="35%"><B><font face="arial, helvetica" size=2><?php echo _QXZ("Audio File to Upload"); ?>:</font></B></td>
	<td align=left width="65%"><input type=file name="audiofile" value=""> <?php echo "$NWB#audio_store$NWE"; ?></td>
  </tr>
  <tr>
	<td align=center colspan=2><input type=submit name=submit value='<?php echo _QXZ("submit"); ?>'></td>
  </tr>
  <tr><td align=left><font size=1> &nbsp; </font></td><td align=right><font size=1><?php echo _QXZ("Audio Store"); ?>- &nbsp; &nbsp; <?php echo _QXZ("VERSION"); ?>: <?php echo $version ?> &nbsp; &nbsp; <?php echo _QXZ("BUILD"); ?>: <?php echo $build ?> &nbsp; &nbsp; </td></tr>
</table>
</form>
<BR><BR>
<CENTER><B><?php echo _QXZ("We STRONGLY recommend uploading only 16bit Mono 8k PCM WAV audio files"); ?>(.wav)</B>
<BR><BR><font size=1><?php echo _QXZ("All spaces will be stripped from uploaded audio file names"); ?></font><BR><BR>
<B><a href="javascript:launch_chooser('master_audiofile','date');"><?php echo _QXZ("audio file list"); ?></a></CENTER>

<?php

echo "<center><BR><BR>"._QXZ("File to Copy").":<BR>\n";
echo "<form action=$PHP_SELF method=post>\n";
echo "<input type=hidden name=action value=\"COPYFILE\">\n";
echo "<input type=hidden name=DB value=\"$DB\">\n";
echo "<input type=hidden name=force_allow value=\"$force_allow\">\n";
echo _QXZ("Original file").": <input type=text size=50 maxlength=100 name=master_audiofile id=master_audiofile value=\"\"><BR>\n";
echo _QXZ("New file").": <input type=text size=50 maxlength=100 name=new_audiofile id=new_audiofile value=\"\"><BR>\n";
echo "<input type=hidden name=DB value=\"$DB\">\n";
echo "<input type=submit name=submit value='"._QXZ("submit")."'></form>\n";

?>

<BR><BR><BR>

<?php
if ($auth_delete > 0)
	{
	echo "<center>"._QXZ("File to Delete").": <a href=\"javascript:launch_chooser('delete_file','date');\">". _QXZ("select file")."</a><BR>\n";
	echo "<form action=$PHP_SELF method=post>\n";
	echo "<input type=hidden name=action value=\"DELETE\">\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=hidden name=force_allow value=\"$force_allow\">\n";
	echo "<input type=text size=50 maxlength=100 name=delete_file id=delete_file value=\"\">\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=submit name=submit value='"._QXZ("submit")."'>\n";
	}
else
	{
	echo "\n";
	echo "<form action=$PHP_SELF method=post>\n";
	echo "<input type=hidden name=action value=\"DELETE\">\n";
	echo "<input type=hidden name=delete_file id=delete_file value=\"\">\n";
	}
echo "</form><BR><BR>\n";


echo "</B></B><br><br><a href=\"admin.php?ADD=720000000000000&category=AUDIOSTORE&stage=manualupload\">"._QXZ("Click here to see a log of the uploads to the audio store")."</FONT>\n";

?>

</TD></TR></TABLE>
