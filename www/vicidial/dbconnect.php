<?php
# 
# dbconnect.php    version 2.8
#
# database connection settings and some global web settings
#
# Copyright (C) 2013  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 100712-1430 - Added slave server option for connection
# 130610-1112 - Finalized changing of all ereg instances to preg
# 131210-1736 - Added ability to define slave server with port number, issue #687
#

if ( file_exists("/etc/astguiclient.conf") )
	{
	$DBCagc = file("/etc/astguiclient.conf");
	foreach ($DBCagc as $DBCline) 
		{
		$DBCline = preg_replace("/ |>|\n|\r|\t|\#.*|;.*/","",$DBCline);
		if (preg_match('/^PATHlogs/', $DBCline))
			{$PATHlogs = $DBCline;   $PATHlogs = preg_replace("/.*=/","",$PATHlogs);}
		if (preg_match('/^PATHweb/', $DBCline))
			{$WeBServeRRooT = $DBCline;   $WeBServeRRooT = preg_replace("/.*=/","",$WeBServeRRooT);}
		if (preg_match('/^VARserver_ip/', $DBCline))
			{$WEBserver_ip = $DBCline;   $WEBserver_ip = preg_replace("/.*=/","",$WEBserver_ip);}
		if (preg_match('/^VARDB_server/', $DBCline))
			{$VARDB_server = $DBCline;   $VARDB_server = preg_replace("/.*=/","",$VARDB_server);}
		if (preg_match('/^VARDB_database/', $DBCline))
			{$VARDB_database = $DBCline;   $VARDB_database = preg_replace("/.*=/","",$VARDB_database);}
		if (preg_match('/^VARDB_user/', $DBCline))
			{$VARDB_user = $DBCline;   $VARDB_user = preg_replace("/.*=/","",$VARDB_user);}
		if (preg_match('/^VARDB_pass/', $DBCline))
			{$VARDB_pass = $DBCline;   $VARDB_pass = preg_replace("/.*=/","",$VARDB_pass);}
		if (preg_match('/^VARDB_custom_user/', $DBCline))
			{$VARDB_custom_user = $DBCline;   $VARDB_custom_user = preg_replace("/.*=/","",$VARDB_custom_user);}
		if (preg_match('/^VARDB_custom_pass/', $DBCline))
			{$VARDB_custom_pass = $DBCline;   $VARDB_custom_pass = preg_replace("/.*=/","",$VARDB_custom_pass);}
		if (preg_match('/^VARDB_port/', $DBCline))
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
	$VARDB_custom_user = 'custom';
	$VARDB_custom_pass = 'custom1234';
	$VARDB_database = '1234';
	$WeBServeRRooT = '/usr/local/apache2/htdocs';
	}

$server_string = "$VARDB_server:$VARDB_port";
if ( ($use_slave_server > 0) and (strlen($slave_db_server)>1) )
	{
	if (preg_match("/\:/", $slave_db_server)) 
		{
		$server_string = $slave_db_server;
		}
	else
		{
		$server_string = "$slave_db_server:$VARDB_port";
		}
	}
$link=mysql_connect($server_string, "$VARDB_user", "$VARDB_pass");

if (!$link) 
	{
    die('MySQL connect ERROR: ' . mysql_error());
	}
mysql_select_db("$VARDB_database");

$local_DEF = 'Local/';
$conf_silent_prefix = '7';
$local_AMP = '@';
$ext_context = 'default';
$recording_exten = '8309';
$webroot_writable = '0';
$non_latin = '0';	# set to 1 for UTF rules
$AM_shift_BEGIN = '03:45:00';
$AM_shift_END = '17:45:00';
$PM_shift_BEGIN = '17:45:01';
$PM_shift_END = '23:59:59';
$admin_qc_enabled = '0';
?>
