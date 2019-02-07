<?php
# 
# dbconnect_mysqli.php    version 2.8
#
# database connection settings and some global web settings
#
# Copyright (C) 2013  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES:
# 130328-0022 - Converted ereg to preg functions
# 130802-0957 - Changed to PHP mysqli functions
# 150626-2120 - Modified mysqli_error() to mysqli_connect_error() where appropriate
#

if ( file_exists("/etc/astguiclient.conf") )
	{
	$DBCagc = file("/etc/astguiclient.conf");
	foreach ($DBCagc as $DBCline) 
		{
		$DBCline = preg_replace("/ |>|\n|\r|\t|\#.*|;.*/","",$DBCline);
		if (preg_match("/^PATHlogs/", $DBCline))
			{$PATHlogs = $DBCline;   $PATHlogs = preg_replace("/.*=/","",$PATHlogs);}
		if (preg_match("/^PATHweb/", $DBCline))
			{$WeBServeRRooT = $DBCline;   $WeBServeRRooT = preg_replace("/.*=/","",$WeBServeRRooT);}
		if (preg_match("/^VARserver_ip/", $DBCline))
			{$WEBserver_ip = $DBCline;   $WEBserver_ip = preg_replace("/.*=/","",$WEBserver_ip);}
		if (preg_match("/^VARDB_server/", $DBCline))
			{$VARDB_server = $DBCline;   $VARDB_server = preg_replace("/.*=/","",$VARDB_server);}
		if (preg_match("/^VARDB_database/", $DBCline))
			{$VARDB_database = $DBCline;   $VARDB_database = preg_replace("/.*=/","",$VARDB_database);}
		if (preg_match("/^VARDB_user/", $DBCline))
			{$VARDB_user = $DBCline;   $VARDB_user = preg_replace("/.*=/","",$VARDB_user);}
		if (preg_match("/^VARDB_pass/", $DBCline))
			{$VARDB_pass = $DBCline;   $VARDB_pass = preg_replace("/.*=/","",$VARDB_pass);}
		if (preg_match("/^VARDB_port/", $DBCline))
			{$VARDB_port = $DBCline;   $VARDB_port = preg_replace("/.*=/","",$VARDB_port);}
		}
	}
else
	{
	#defaults for DB connection
	$VARDB_server = 'localhost';
	$VARDB_port = '3306';
	$VARDB_user = 'cron';
	$VARDB_pass = '1234';
	$VARDB_database = '1234';
	$WeBServeRRooT = '/usr/local/apache2/htdocs';
	}

$link=mysqli_connect("$VARDB_server", "$VARDB_user", "$VARDB_pass", "$VARDB_database", $VARDB_port);
if (!$link) 
	{
    die('MySQL connect ERROR: ' . mysqli_connect_error());
	}

$local_DEF = 'Local/';
$conf_silent_prefix = '7';
$local_AMP = '@';
$ext_context = 'demo';
$recording_exten = '8309';
$WeBRooTWritablE = '1';
$non_latin = '0';	# set to 1 for UTF rules, overridden by system_settings
$flag_channels=0;
$flag_string = 'VICIast20';

?>
