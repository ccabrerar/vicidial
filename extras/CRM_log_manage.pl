#!/usr/bin/perl
#
# CRM_log_manage.pl	version 2.14
#
# This script is designed to manage the multiple crm_..._log table records, with 
# options to fill them and/or purge them at specific intevals, keeping a set
# number of minimum days of records within them.
#
# To customize this script, edit the "POP_table" and "POP_date" entries below to
# include the database tables and their date fields you want to be managed by
# this script. You will need to make sure that a "crm_..." table exists for each:
#
# CREATE TABLE crm_call_log LIKE call_log;
# CREATE TABLE crm_vicidial_log LIKE vicidial_log;
# CREATE TABLE crm_vicidial_agent_log LIKE vicidial_agent_log; 
# ALTER TABLE crm_vicidial_agent_log MODIFY agent_log_id INT(9) UNSIGNED NOT NULL;
# CREATE TABLE crm_vicidial_closer_log LIKE vicidial_closer_log; 
# ALTER TABLE crm_vicidial_closer_log MODIFY closecallid INT(9) UNSIGNED NOT NULL;
# CREATE TABLE crm_vicidial_xfer_log LIKE vicidial_xfer_log; 
# ALTER TABLE crm_vicidial_xfer_log MODIFY xfercallid INT(9) UNSIGNED NOT NULL;
# CREATE TABLE crm_recording_log LIKE recording_log;
# ALTER TABLE crm_recording_log MODIFY recording_id INT(10) UNSIGNED UNIQUE NOT NULL;
# ALTER TABLE crm_recording_log DROP PRIMARY KEY;
# CREATE TABLE crm_vicidial_user_log LIKE vicidial_user_log; 
# ALTER TABLE crm_vicidial_user_log MODIFY user_log_id INT(9) UNSIGNED NOT NULL;
#
# Place in the crontab and run every day/week after one in the morning, or whenever
# your server is not busy with other tasks:
# 10 0 * * * /usr/share/astguiclient/CRM_log_manage.pl --populate-crm-only --days=2
# 30 1 * * 0 /usr/share/astguiclient/CRM_log_manage.pl --purge-crm-only --days=91
#
# OPTIONAL SECONDARY SERVER DURING-THE-DAY INSERTS, DO NOT RUN ON MASTER DB!!!
# 1,16,31,46 7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * * /usr/share/astguiclient/CRM_log_manage.pl --pop-replace-crm-only=2 --skip-optimizes --quiet
# 10 6 * * * /usr/share/astguiclient/CRM_log_manage.pl --pop-replace-crm-only=6 --debugX
# 10 0 * * * /usr/share/astguiclient/CRM_log_manage.pl --pop-replace-crm-only=26 --debugX
# 30 1 * * 0 /usr/share/astguiclient/CRM_log_manage.pl --purge-crm-only --days=91 --debugX
#
# Copyright (C) 2026 Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 260117-0756 - First version, based on parts of VCA_log_manage script
# 260211-1552 - Added --pop-replace-crm-only=XX for SECONDARY DB USE ONLY
#

$CALC_TEST=0;
$T=0;   $TEST=0;
$DB=0;   $DBX=0;
$populate_crm_only=0;
$archive_tables=0;
$purge_crm_only=0;
$skip_optimizes=0;
$POPreplaceHOURS=0;

$POP_table[0] = 'vicidial_agent_log';
$POP_table[1] = 'vicidial_log';
$POP_table[2] = 'vicidial_closer_log';
$POP_table[3] = 'recording_log';
$POP_table[4] = 'vicidial_xfer_log';
$POP_table[5] = 'vicidial_user_log';
$POP_table[6] = 'call_log';

$POP_date[0] = 'event_time';
$POP_date[1] = 'call_date';
$POP_date[2] = 'call_date';
$POP_date[3] = 'start_time';
$POP_date[4] = 'call_date';
$POP_date[5] = 'event_date';
$POP_date[6] = 'start_time';


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
		print "  [--days=XX] = number of days to populate or purge crm_ tables, default is 90\n";
		print "  [--populate-crm-only] = OPTIONAL, only populates crm_..._log tables then exits\n";
		print "    [--archive-tables] = OPTIONAL with --populate-crm-only  only, use _archive tables for populate\n";
		print "  [--purge-crm-only] = OPTIONAL, only deletes crm_..._log tables records past number of days then exit\n";
		print "  [--skip-optimizes] = OPTIONAL do not optimize tables after inserts/deletes \n";
		print "  [--pop-replace-crm-only=XX] = OPTIONAL, only populates and replace crm_..._log tables XX hours then exits\n";
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
		if ($args =~ /--skip-optimizes/i)
			{
			$skip_optimizes=1;
			if ($Q < 1) 
				{print "\n----- SKIP TABLE OPTIMIZES ENABLED: $skip_optimizes -----\n\n";}
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
		if ($args =~ /--pop-replace-crm-only=/i)
			{
			@data_in = split(/--pop-replace-crm-only=/,$args);
			$POPreplaceHOURS = $data_in[1];
			$POPreplaceHOURS =~ s/ .*$//gi;
			$POPreplaceHOURS =~ s/\D//gi;
			if ($POPreplaceHOURS > 999999)
				{$POPreplaceHOURS=25;}
			if ($Q < 1) 
				{print "\n----- POP-REPLACE HOURS: $POPreplaceHOURS -----\n\n";}
			}

		if ($args =~ /--populate-crm-only/i)
			{
			$populate_crm_only++;
			if ($Q < 1) 
				{print "\n----- POPULATE CRM LOG TABLES ONLY: |$populate_crm_only| -----\n\n";}
			}
		if ($args =~ /--purge-crm-only/i)
			{
			$purge_crm_only++;
			if ($Q < 1) 
				{print "\n----- PURGE CRM LOG TABLES ONLY: |$purge_crm_only| -----\n\n";}
			}
		if ($args =~ /--archive-tables/i)
			{
			$archive_tables++;
			if ($Q < 1) 
				{print "\n----- USE ARCHIVE TABLES ONLY: |$archive_tables| -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set\n";
	}
### end parsing run-time options ###
if (length($CLIdays)<1)
	{
	$CLIdays = 90;
	}

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

($NTsec,$NTmin,$NThour,$NTmday,$NTmon,$NTyear,$NTwday,$NTyday,$NTisdst) = localtime($secX);
$NTyear = ($NTyear + 1900);
$NTmon++;
if ($NTmon < 10) {$NTmon = "0$NTmon";}
if ($NTmday < 10) {$NTmday = "0$NTmday";}
if ($NThour < 10) {$NThour = "0$NThour";}
if ($NTmin < 10) {$NTmin = "0$NTmin";}
if ($NTsec < 10) {$NTsec = "0$NTsec";}
$now_time = "$NTyear-$NTmon-$NTmday $NThour:$NTmin:$NTsec";
$now_date = "$NTyear-$NTmon-$NTmday";

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

if ($POPreplaceHOURS > 0) 
	{
	$del_epoch = ($secX - (3600 * $POPreplaceHOURS));   # X hours ago
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
	}

if (!$Q) {print "\n\n-- CRM_log_manage.pl --\n\n";}
if (!$Q) {print "This program is designed manage the crm_..._log tables records, with \n";}
if (!$Q) {print "options to populate them and/or purge them at specific intevals.\n";}
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
	########## BEGIN --pop-replace-crm-only=XX flag processing ##########
	if ($POPreplaceHOURS > 0) 
		{
		$i=0;
		foreach(@POP_table) 
			{
			$source_table = $POP_table[$i];
			if ($archive_tables > 0) 
				{$source_table = $POP_table[$i]."_archive";}
			if (!$Q) {print "\nStarting processing of populating $source_table table... $i\n";}
			$stmtA = "SELECT count(*) from $source_table;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$live_table_log_count =	$aryA[0];
				}
			$sthA->finish();

			$stmtA = "SELECT count(*) from crm_$POP_table[$i];";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$crm_table_log_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "POP-REPLACE Processing population from $source_table table...  ($live_table_log_count|$crm_table_log_count)\n";}

			$stmtA = "LOCK TABLES crm_$POP_table[$i] WRITE, $POP_table[$i] READ;";
			my $LOCKaffected_rows = $dbhA->do($stmtA);
			if ($DBX > 0) {print "     DEBUG: |$LOCKaffected_rows|$stmtA|\n";}

			$stmtA = "DELETE FROM crm_$POP_table[$i] where $POP_date[$i] > \"$del_time\" and $POP_date[$i] <= \"$now_time\";";
			if ($DBX > 0) {print "     DEBUG: |$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from crm_$POP_table[$i] table \n";}

			$stmtA = "INSERT IGNORE INTO crm_$POP_table[$i] SELECT * from $source_table where $POP_date[$i] > \"$del_time\" and $POP_date[$i] <= \"$now_time\";";
			if ($DBX > 0) {print "     DEBUG: |$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows inserted into crm_$POP_table[$i] table \n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "UNLOCK TABLES;";
				my $LOCKaffected_rows = $dbhA->do($stmtA);
				if ($DBX > 0) {print "     DEBUG: |$LOCKaffected_rows|$stmtA|\n";}

				if ($skip_optimizes < 1)
					{
					if ($DBX > 0) {print "     DEBUG: optimizing table crm_$POP_table[$i]... \n";}
					$stmtA = "optimize table crm_$POP_table[$i];";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					}
				}
			else
				{
				if (!$Q) {print "ERROR for insert into crm_$POP_table[$i] table!!! \n";}

				$stmtA = "UNLOCK TABLES;";
				my $LOCKaffected_rows = $dbhA->do($stmtA);
				if ($DBX > 0) {print "     DEBUG: |$LOCKaffected_rows|$stmtA|\n";}
				}

			if (!$Q) {print "POP-REPLACE Processing $source_table table finished:  ($sthArows rows inserted into crm_$POP_table[$i]) \n";}

			$i++;
			}

		### calculate time to run script ###
		$secY = time();
		$secZ = ($secY - $secX);
		$secZm = ($secZ /60);
		if (!$Q) {print "\nscript execution time in seconds: $secZ     minutes: $secZm\n";}

		exit;
		}
	########## END --pop-replace-crm-only=XX flag processing ##########


	########## BEGIN --populate-crm-only flag processing ##########
	if ($populate_crm_only > 0)
		{
		$i=0;
		foreach(@POP_table) 
			{
			$source_table = $POP_table[$i];
			if ($archive_tables > 0) 
				{$source_table = $POP_table[$i]."_archive";}
			if (!$Q) {print "Starting processing of populating $source_table table... $i\n";}
			$stmtA = "SELECT count(*) from $source_table;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$live_table_log_count =	$aryA[0];
				}
			$sthA->finish();

			$stmtA = "SELECT count(*) from crm_$POP_table[$i];";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$crm_table_log_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "\nProcessing population from $source_table table...  ($live_table_log_count|$crm_table_log_count)\n";}

			$stmtA = "INSERT IGNORE INTO crm_$POP_table[$i] SELECT * from $source_table where $POP_date[$i] > \"$del_time\";";
			if ($DBX > 0) {print "     DEBUG: |$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows inserted into crm_$POP_table[$i] table \n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				if ($skip_optimizes < 1)
					{
					$stmtA = "optimize table crm_$POP_table[$i];";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					}
				}

			if (!$Q) {print "\nProcessing $source_table table finished:  ($sthArows rows inserted into crm_$POP_table[$i]) \n";}

			$i++;
			}
		
		exit;
		}
	########## END --populate-crm-only flag processing ##########

	########## BEGIN --purge-crm-only flag processing ##########
	if ($purge_crm_only > 0)
		{
		$i=0;
		foreach(@POP_table) 
			{
			if (!$Q) {print "Starting processing purging of $POP_table[$i] table... $i\n";}

			$stmtA = "SELECT count(*) from crm_$POP_table[$i];";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$crm_table_log_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "\nProcessing purge of crm_$POP_table[$i] table...  ($crm_table_log_count)\n";}

			$stmtA = "DELETE FROM crm_$POP_table[$i] where $POP_date[$i] < \"$del_time\";";
			if ($DBX > 0) {print "     DEBUG: |$stmtA|\n";}
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from crm_$POP_table[$i] table \n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				if ($skip_optimizes < 1)
					{
					$stmtA = "optimize table crm_$POP_table[$i];";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					}
				}

			if (!$Q) {print "\nProcessing $POP_table[$i] table finished:  ($sthArows rows deleted from crm_$POP_table[$i]) \n";}

			$i++;
			}
		exit;
		}
	########## END --purge-crm-only flag processing ##########
	}

### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);
if (!$Q) {print "\nscript execution time in seconds: $secZ     minutes: $secZm\n";}

exit;
