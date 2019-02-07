#!/usr/bin/perl
#
# AST_settings_container_SQL.pl    version 2.12
#
# DESCRIPTION:
# - runs contents of a settings container in the system as SQL and logs to admin log
#
# This script can be run manually as needed, or put into the crontab on one server
#
# !!!!! IMPORTANT !!!!!
# THE SQL STATEMENTS MUST NOT CONTAIN NEWLINES OR CARRIAGE RETURNS!
#
# Copyright (C) 2016  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 160118-1638 - First build
#

### begin parsing run-time options ###
if (length($ARGV[0])>1)
	{
	$i=0;
	while ($#ARGV >= $i)
		{
		$args = "$args $ARGV[$i]";
		$i++;
		}

	if ($args =~ /--help/i)
		{
		print "allowed run time options:\n";
		print "  [-q] = quiet\n";
		print "  [-t] = test\n";
		print "  [--debug] = debugging messages\n";
		print "  [--debugX] = Extra debugging messages\n";
		print "  [--container=XXX] = required, the ID of the settings container to run from the system\n";
		print "\n";

		exit;
		}
	else
		{
		if ($args =~ /-q/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUGGING -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- EXTRA DEBUGGING -----\n\n";
			}
		if ($args =~ /-t|--test/i)
			{
			$T=1; $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		if ($args =~ /--container=/i)
			{
			@data_in = split(/--container=/,$args);
			$container = $data_in[1];
			$container =~ s/ .*$//gi;
			if ($Q < 1)
				{print "\n----- SETTINGS CONTAINER: $container -----\n\n";}
			}
		if (length($container) < 1)
			{
			print "ERROR! Invalid Settings Container: $container     Exiting...\n";
			exit;
			}
		}
	}
else
	{
	print "no command line options set   Exiting...\n";
	}
### end parsing run-time options ###

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$yy = $year; $yy =~ s/^..//gi;
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$SQLdate_NOW="$year-$mon-$mday $hour:$min:$sec";

if (!$Q) {print "TEST\n\n";}
if (!$Q) {print "NOW DATETIME:         $SQLdate_NOW\n";}

# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PATHhome/) && ($CLIhome < 1) )
		{$PATHhome = $line;   $PATHhome =~ s/.*=//gi;}
	if ( ($line =~ /^PATHlogs/) && ($CLIlogs < 1) )
		{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ( ($line =~ /^PATHagi/) && ($CLIagi < 1) )
		{$PATHagi = $line;   $PATHagi =~ s/.*=//gi;}
	if ( ($line =~ /^PATHweb/) && ($CLIweb < 1) )
		{$PATHweb = $line;   $PATHweb =~ s/.*=//gi;}
	if ( ($line =~ /^PATHsounds/) && ($CLIsounds < 1) )
		{$PATHsounds = $line;   $PATHsounds =~ s/.*=//gi;}
	if ( ($line =~ /^PATHmonitor/) && ($CLImonitor < 1) )
		{$PATHmonitor = $line;   $PATHmonitor =~ s/.*=//gi;}
	if ( ($line =~ /^VARserver_ip/) && ($CLIserver_ip < 1) )
		{$VARserver_ip = $line;   $VARserver_ip =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_server/) && ($CLIDB_server < 1) )
		{$VARDB_server = $line;   $VARDB_server =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_database/) && ($CLIDB_database < 1) )
		{$VARDB_database = $line;   $VARDB_database =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_user/) && ($CLIDB_user < 1) )
		{$VARDB_user = $line;   $VARDB_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_pass/) && ($CLIDB_pass < 1) )
		{$VARDB_pass = $line;   $VARDB_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_port/) && ($CLIDB_port < 1) )
		{$VARDB_port = $line;   $VARDB_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

### Grab container content from the database
$container_sql='';
$stmtA = "SELECT container_entry FROM vicidial_settings_containers where container_id = '$container';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$container_sql = $aryA[0];
	}
$sthA->finish();

if (length($container_sql)>5) 
	{
	@container_lines = split(/\n/,$container_sql);
	$i=0;
	foreach(@container_lines)
		{
		$container_lines[$i] =~ s/;.*//gi;
		if (length($container_lines[$i])>5)
			{
			if ($container_lines[$i] =~ /^INSERT|^UPDATE|^DELETE|^SELECT/i)
				{
				$stmtA = "$container_lines[$i];";
				if($DB){print STDERR "\n|$stmtA|\n";}
				if (!$T) {	$affected_rows = $dbhA->do($stmtA);}
				if (!$Q) {print " - line $i executed, affected: $affected_rows rows\n";}

				$stmtB="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='CONTAINERS', event_type='OTHER', record_id='$container', event_code='CONTAINER SQL RUN', event_sql=\"$container_lines[$i]\", event_notes='line $i executed, $affected_rows rows affected';";
				if (!$T) {	$Iaffected_rows = $dbhA->do($stmtB);}
				if ($DBX) {print " - admin log insert debug: |$Iaffected_rows|$stmtB|\n";}
				}
			else
				{if ($DBX > 0) {print "     not allowed SQL: $i|$container_lines[$i]|\n";}}
			}
		else
			{if ($DBX > 0) {print "     blank line: $i|$container_lines[$i]|\n";}}
		$i++;
		}
	}
else
	{
	if ($Q < 1)
		{print "ERROR: SETTINGS CONTAINER EMPTY: $container $container_sql\n";}
	}


$dbhA->disconnect();

if (!$Q) {print "Script exiting\n";}

exit;
