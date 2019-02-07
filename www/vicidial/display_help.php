<?php
# display_help.php
#
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# pulls help text to display on the screen when the appropriate help link is clicked
#
# CHANGELOG:
# 180501-0045 - First build
# 180512-1055 - Added update of help_documentation.txt file if modified
#

$startMS = microtime();
$ip = getenv("REMOTE_ADDR");
$help_file = './help_documentation.txt';
$help_file_time=0;
if ( file_exists($help_file) )
	{$help_file_time = filemtime($help_file);}

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["help_id"]))			{$help_id=$_GET["help_id"];}
	elseif (isset($_POST["help_id"]))	{$help_id=$_POST["help_id"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

$help_id = preg_replace('/[^-\_0-9a-zA-Z]/', '',$help_id);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,help_modification_date FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSenable_languages =			$row[1];
	$SSlanguage_method =			$row[2];
	$help_modification_date =		$row[3];
	}
##### END SETTINGS LOOKUP #####
###########################################

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];

if ($non_latin < 1)
	{
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	}
else
	{
	$PHP_AUTH_PW = preg_replace('/\'|\"|\\\\|;/', '',$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace('/\'|\"|\\\\|;/', '',$PHP_AUTH_USER);
	}

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
if ($auth_message == 'GOOD')
	{$user_auth=1;}

if ($user_auth < 1)
	{
	$VDdisplayMESSAGE = "Login incorrect, please try again";
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = "Too many login attempts, try again in 15 minutes";
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}

### Check for new help_documentation.txt file
if ($help_file_time > $help_modification_date)
	{
	$help_fp=fopen($help_file, "r");
	$help_contentsRAW = fread($help_fp,filesize($help_file));
	fclose($help_fp);
	$help_contents = explode("\n",$help_contentsRAW);
	$help_contents_ct = count($help_contents);
	if ($DB > 0) 
		{echo "New $help_file found, loading contents: ($help_file_time <> $help_modification_date), ".filesize($help_file)."|$help_contents_ct\n";}

	$h=0;
	$help_comment=0;
	$help_insert=0;
	$help_update=0;
	while($help_contents_ct > $h)
		{
		if (!preg_match("/^\#/",$help_contents[$h]))
			{
			$help_line = preg_replace("/\"/","'",$help_contents[$h]);
			$help_line = preg_replace("/\r|\n|\\\\/",'',$help_contents[$h]);
			$help_lineARY = explode("\t",$help_line);
			$stmt="INSERT INTO help_documentation (help_id, help_title, help_text) VALUES (\"" . mysqli_real_escape_string($link, $help_lineARY[0]) . "\",\"" . mysqli_real_escape_string($link, $help_lineARY[1]) . "\",\"" . mysqli_real_escape_string($link, $help_lineARY[2]) . "\") ON DUPLICATE KEY UPDATE help_title=\"" . mysqli_real_escape_string($link, $help_lineARY[1]) . "\", help_text=\"" . mysqli_real_escape_string($link, $help_lineARY[2]) . "\";";
			$rslt=mysql_to_mysqli($stmt, $link);
			$inupdate_rows=mysqli_affected_rows($link);
			if ($inupdate_rows == 1) {$help_insert++;}
			if ($inupdate_rows == 2) {$help_update++;}
			if ($DB) {echo "$inupdate_rows|$stmt|\n";}
			}
		else
			{
			$help_comment++;
			if ($DB) {echo "Skipping comment row: $h|$help_contents[$h]|\n";}
			}
		$h++;
		}

	$stmt="UPDATE system_settings SET help_modification_date='$help_file_time';";
	$rslt=mysql_to_mysqli($stmt, $link);

	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='HELP', event_type='MODIFY', record_id='$PHP_AUTH_USER', event_code='UPDATE HELP DOCUMENTATION', event_sql=\"$SQL_log\", event_notes='ROWS: $h UPDATES: $help_update INSERTS: $help_insert COMMENTS: $help_comment';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	}


### Lookup of help text for display
$help_stmt="select help_title, help_text from help_documentation where help_id='$help_id'";
$help_rslt=mysql_to_mysqli($help_stmt, $link);
while ($help_row=mysqli_fetch_row($help_rslt)) 
	{
	preg_match_all("/<QXZ>(.*?)<\/QXZ>/", $help_row[1], $QXZ_matches);

	$qxz_match=$QXZ_matches[0];
	$qxz_replace=$QXZ_matches[1];
	for ($q=0; $q<count($qxz_replace); $q++) 
		{
		# $qxz_match[$q]=preg_replace("/\//", '\\\/', $qxz_match[$q]);
		$qxz_match[$q]=preg_quote($qxz_match[$q], '/');
		$qxz_match[$q]="/".$qxz_match[$q]."/";
		$qxz_replace[$q]=_QXZ($qxz_replace[$q]);
		}
	$help_row[1]=preg_replace($qxz_match, $qxz_replace, $help_row[1]);

	# preg_replace('/"/', '\\"', $help_row[1]);
	echo "<TABLE CELLPADDING=2 CELLSPACING=0 border='0' class='help_td' width='300'>";
	echo "<TR><TD VALIGN='TOP' width='280'><FONT class='help_bold'>"._QXZ("$help_row[0]")."</font></td><TD VALIGN='TOP' align='right' width='20' onClick='ClearAndHideHelpDiv()'><B>[X]</B></tr>";
	echo "<TR><TD VALIGN='TOP' colspan='2'>$help_row[1]</td></tr>";
	echo "</TABLE>";
	if ($DB > 0) 
		{echo "$help_file($help_file_time <> $help_modification_date)\n";}
	}
?>
