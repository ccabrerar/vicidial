#!/usr/bin/perl
#
# AST_table_status.pl    version 2.12
#
# DESCRIPTION:
# - gathers MySQL "SHOW TABLE STATUS" info to check for crashed tables
#
#
# Copyright (C) 2025  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 251025-1618 - First build
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
$now_date="$year-$mon-$mday $hour:$min:$sec";

if (!$Q) {print "NOW DATETIME:         $now_date \n";}

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


if ($DB) {print "Begin check for crashed table...\n";}
# gather crashed table records
$stmtA = "SHOW TABLE STATUS WHERE Comment LIKE \"%crash%\";";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsCRASH=$sthA->rows;
$i=0;
while ($sthArowsCRASH > $i)
	{
	@aryA = $sthA->fetchrow_array;
	$CRASHEDtables[$i]	= 		$aryA[0];

	if($DBX){print STDERR "\nCRASHED TABLE: |$i|$CRASHEDtables[$i]| \n";}

	$i++;
	}
$sthA->finish();

if ($i > 0) 
	{
	$keep_tables="''";
	$i=0;
	while ($sthArowsCRASH > $i)
		{
		$keep_tables .= ",'$CRASHEDtables[$i]'";
		$stmtA = "INSERT INTO crashed_tables SET table_name='$CRASHEDtables[$i]',crashed_datetime='$now_date',last_check_datetime='$now_date' ON DUPLICATE KEY UPDATE last_check_datetime='$now_date';";
		$Iaffected_rows = $dbhA->do($stmtA);
		if ($DB) {print "Crashed table update:   |$Iaffected_rows|$stmtA";}
		$i++;
		}
	$stmtA = "DELETE FROM crashed_tables where table_name NOT IN($keep_tables);";
	$Iaffected_rows = $dbhA->do($stmtA);
	if ($DB) {print "Crashed table old entries cleared:   |$Iaffected_rows|$stmtA";}
	}
else
	{
	if($DBX){print STDERR "\nNO CRASHED TABLES FOUND.\n";}
	$CRASHEDtable_ct=0;
	# gather crashed table records count
	$stmtA = "SELECT count(*) FROM crashed_tables;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArowsCRASHct=$sthA->rows;
	if ($sthArowsCRASHct > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$CRASHEDtable_ct	= 		$aryA[0];
		}
	$sthA->finish();

	if ($CRASHEDtable_ct > 0)
		{
		$stmtA = "DELETE FROM crashed_tables;";
		$Iaffected_rows = $dbhA->do($stmtA);
		if ($DB) {print "Crashed table cleared:   |$Iaffected_rows|$stmtA";}
		}
	}


$dbhA->disconnect();

if (!$Q) {print "Script exiting\n";}

exit;
