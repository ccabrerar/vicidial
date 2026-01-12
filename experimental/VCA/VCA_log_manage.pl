#!/usr/bin/perl
#
# VCA_log_manage.pl	version 2.14
#
# This script is designed to manage the vicidial_vca_log table records, with 
# options to archive them and/or purge them at specific intevals.
#
# Place in the crontab and run every month after one in the morning, or whenever
# your server is not busy with other tasks
# 30 1 1 * * /usr/share/astguiclient/VCA_log_manage.pl
#
# Copyright (C) 2026 Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 260112-1455 - First version, based on parts of ADMIN_archive_log_tables script
#

$CALC_TEST=0;
$T=0;   $TEST=0;
$DB=0;   $DBX=0;
$archive_vca_only=0;
$archived_vca_purge_only=0;


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
		print "  [--days=XX] = number of days to archive past, default is 732(2 years)\n";
		print "  [--archive-vca-log-only] = OPTIONAL, only archive vicidial_vca_log table then exit\n";
		print "  [--archived-vca-purge-only] = OPTIONAL, only delete vicidial_vca_log_archive table records then exit\n";
		print "  [--wipe-all-being-archived] = OPTIONAL, deletes all records from the active table after archiving\n";
		print "  [--quiet] = quiet\n";
		print "  [--calc-test] = date calculation test only\n";
		print "  [--test] = test\n";
		print "  [--debug] = debug output for some options\n";
		print "  [--debugX] = extra debug output for some options\n\n";
		exit;
		}
	else
		{
		if ($args =~ /-quiet/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n-----TESTING-----\n\n";
			}
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n-----DEBUG-----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n-----DEBUG X-----\n\n";
			}
		if ($args =~ /--calc-test/i)
			{
			$CALC_TEST=1;
			print "\n-----DATE CALCULATION TESTING ONLY-----\n\n";
			}
		if ($args =~ /--wipe-all-being-archived/i) 
			{
			$wipe_all=1;
			print "\n----- WIPE ALL LOG TABLES BEING ARCHIVED: $wipe_all -----\n\n";
			}
		if ($args =~ /--days=/i)
			{
			@data_in = split(/--days=/,$args);
			$CLIdays = $data_in[1];
			$CLIdays =~ s/ .*$//gi;
			$CLIdays =~ s/\D//gi;
			if ($CLIdays > 999999)
				{$CLIdays=730;}
			if ($Q < 1) 
				{print "\n----- DAYS OVERRIDE: $CLIdays -----\n\n";}
			}

		if ($args =~ /--archive-vca-log-only/i)
			{
			$archive_vca_only++;
			if ($Q < 1) 
				{print "\n----- ARCHIVE VCA LOG ONLY: |$archive_vca_only| -----\n\n";}
			}
		if ($args =~ /--archived-vca-purge-only/i)
			{
			$archived_vca_purge_only++;
			if ($Q < 1) 
				{print "\n----- ARCHIVED VCA LOG PURGE ONLY: |$archived_vca_purge_only| -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set\n";
	}
### end parsing run-time options ###
if ( (length($CLIdays)<1) || ($CLIdays < 1) )
	{
	$CLIdays = ($CLImonths * 30.5);
	$CLIdays = sprintf("%.0f",$CLIdays);
	}

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

$del_epoch = ($secX - (86400 * $CLIdays));   # X days ago
($RMsec,$RMmin,$RMhour,$RMmday,$RMmon,$RMyear,$RMwday,$RMyday,$RMisdst) = localtime($del_epoch);
$RMyear = ($RMyear + 1900);
$RMmon++;
if ($RMmon < 10) {$RMmon = "0$RMmon";}
if ($RMmday < 10) {$RMmday = "0$RMmday";}
if ($RMhour < 10) {$RMhour = "0$RMhour";}
if ($RMmin < 10) {$RMmin = "0$RMmin";}
if ($RMsec < 10) {$RMsec = "0$RMsec";}
$del_time = "$RMyear-$RMmon-$RMmday $RMhour:$RMmin:$RMsec";
$del_date = "$RMyear-$RMmon-$RMmday";


if (!$Q) {print "\n\n-- VCA_log_manage.pl --\n\n";}
if (!$Q) {print "This program is designed manage the vicidial_vca_log table records, with \n";}
if (!$Q) {print "options to archive them and/or purge them at specific intevals.\n";}
if (!$Q) {print "$CLIdays days ( $del_time [$del_date]|$del_epoch ) from current date \n\n";}

if ($CALC_TEST > 0)
	{
	exit;
	}

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

use DBI;
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


if (!$T) 
	{
	########## BEGIN --archive-vca-log-only flag processing ##########
	if ($archive_vca_only > 0)
		{
		##### vicidial_vca_log
		$stmtA = "SELECT count(*) from vicidial_vca_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_vca_log_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_vca_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_vca_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_vca_log table...  ($vicidial_vca_log_count|$vicidial_vca_log_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO vicidial_vca_log_archive SELECT * from vicidial_vca_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into vicidial_vca_log_archive table \n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			if ($wipe_all > 0)
				{$stmtA = "DELETE FROM vicidial_vca_log;";}
			else
				{$stmtA = "DELETE FROM vicidial_vca_log WHERE analysis_date < '$del_time';";}
			if ($DBX > 0) {print "     DEBUG: |$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_vca_log table \n";}

			$stmtA = "optimize table vicidial_vca_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}

		if (!$Q) {print "\nProcessing vicidial_vca_log table finished:  ($sthArows rows deleted) \n";}
		
		exit;
		}
	########## END --archive-vca-log-only flag processing ##########

	########## BEGIN --archived-vca-purge-only flag processing ##########
	if ($archived_vca_purge_only > 0)
		{
		$stmtA = "SELECT count(*) from vicidial_vca_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_vca_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_vca_log_archive table purge...  ($vicidial_vca_log_archive_count)\n";}
		$stmtA = "DELETE FROM vicidial_vca_log_archive WHERE analysis_date < '$del_time';";
		if ($DBX > 0) {print "     DEBUG: |$stmtA|\n";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_vca_log_archive table \n";}

		$stmtA = "optimize table vicidial_vca_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		if (!$Q) {print "\nProcessing vicidial_vca_log_archive table finished:  ($sthArows rows deleted) \n";}
		
		exit;
		}
	########## END --archived-vca-purge-only flag processing ##########
	}

### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);
if (!$Q) {print "\nscript execution time in seconds: $secZ     minutes: $secZm\n";}

exit;
