#!/usr/bin/perl
#
# ADMIN_archive_log_tables.pl	version 2.14
#
# This script is designed to put all  records from call_log, vicidial_log and 
# vicidial_agent_log in relevant _archive tables and delete records in original
# tables older than X days or months from current date. Also, deletes old
# server_performance table records without archiving them as well as optimizing
# all involved tables.
#
# Place in the crontab and run every month after one in the morning, or whenever
# your server is not busy with other tasks
# 30 1 1 * * /usr/share/astguiclient/ADMIN_archive_log_tables.pl
#
# NOTE: On a high-load outbound dialing system, this script can take hours to 
# run. While the script is running the system is unusable. Please schedule to 
# run this script at a time when the system will not be used for several hours.
#
# original author: I. Taushanov(okli)
# Based on perl scripts in ViciDial from Matt Florell and post: 
# http://www.vicidial.org/VICIDIALforum/viewtopic.php?p=22506&sid=ca5347cffa6f6382f56ce3db9fb3d068#22506
#
# Copyright (C) 2018  I. Taushanov, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 90615-1701 - First version
# 100101-1722 - Added error safety checks
# 100103-2052 - Formatting fixes, name change, added initial table counts and added archive tables to official SQL files
# 100109-1018 - Added vicidial_carrier_log archiving
# 100328-1008 - Added --months CLI option
# 110218-1200 - Added notes and search log archiving
# 110430-1442 - Added queue-log and closer-log options, changed quiet to --quiet flag
# 110525-1040 - Added vicidial_outbound_ivr_log archiving
# 110801-2140 - Added vicidial_url_log table purging and vicidial_log_extended to rolling processes
# 110808-0055 - Added vicidial_log_noanswer process
# 110822-1257 - Added did_agent_log process
# 120402-2144 - Added "--daily" flag that will archive call_log and vicidial_log_extended logs past 24 hours
# 120831-1536 - Added rolling of vicidial_dial_log entries
# 140107-1508 - Added rolling of vicidial_api_log entries
# 140305-0955 - Changed to use --days (--months is approximation[bug fix]), changed default from 2 months to 2 years
# 150107-2308 - Added vicidial_api_log to daily process
# 150712-2208 - Added vicidial_dtmf_log
# 151109-1646 - Added --carrier-daily flag, only active if --daily flag is also used
# 151124-2252 - Added --vlog-daily flag, only active if --daily flag is also used, Also added system_settings date
# 160612-0703 - Added --only-trim-archive-... options
# 160827-0957 - Added --recording-log-days=X option
# 161212-1659 - Added --cpd-log-purge-days=XX option
# 170209-1213 - Added vicidial_api_urls table rolling and purging to all vicidial_api_log sections
# 170325-1108 - Added vicidial_drop_log table rolling
# 170508-1212 - Added vicidial_rt_monitor_log table rolling
# 170809-1926 - Added rolling of user_call_log if over 1000000 records
# 170817-1318 - Added rolling of vicidial_inbound_survey_log
# 170825-2243 - Added rolling of vicidial_xfer_log
# 171026-0105 - Added --wipe-closer-log flag
# 180410-1728 - Added vicidial_agent_function_log archiving
# 180712-1641 - Added --wipe-all-being-archived AND --did-log-days options
#

$CALC_TEST=0;
$T=0;   $TEST=0;
$only_trim_archive=0;
$recording_log_archive=0;
$did_log_archive=0;
$wipe_closer_log=0;

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
		print "  [--daily] = only archives call_log, vicidial_log_extended, vicidial_dial_log and vicidial_drop_log tables, only last 24 hours kept\n";
		print "  [--carrier-daily] = will also archive the vicidial_carrier_log table when --daily is run\n";
		print "  [--vlog-daily] = will also archive the vicidial_log table when --daily is run\n";
		print "  [--days=XX] = number of days to archive past, default is 732(2 years)\n";
		print "  [--months=XX] = number of months to archive past, default is 24(2 years) If 'days' used then 'months' ignored\n";
		print "  [--queue-log] = archive QM queue_log records\n";
		print "  [--only-trim-archive-level-one] = will not perform normal archive process, instead this will only delete records\n";
		print "                                    that are older than XX months from least important log archive tables:\n";
		print "                               call_log_archive, vicidial_log_extended_archive, vicidial_dial_log_archive, vicidial_drop_log\n";
		print "  [--only-trim-archive-level-two] = same as --only-trim-archive-level-one, except includes tables:\n";
		print "                               vicidial_carrier_log_archive, vicidial_api_log_archive, vicidial_rt_monitor_log_archive\n";
		print "  [--only-trim-archive-level-three] = same as --only-trim-archive-level-two, except includes tables:\n";
		print "                               vicidial_log_archive, vicidial_agent_log_archive, vicidial_closer_log_archive, vicidial_xfer_log_archive\n";
		print "  [--recording-log-days=XX] = OPTIONAL, number of days to archive recording_log table only past\n";
		print "  [--did-log-days=XX] = OPTIONAL, number of days to archive vicidial_did_log table only past\n";
		print "  [--cpd-log-purge-days=XX] = OPTIONAL, number of days to purge vicidial_cpd_log table only past\n";
		print "  [--wipe-closer-log] = OPTIONAL, deletes all records from vicidial_closer_log after archiving\n";
		print "  [--wipe-all-being-archived] = OPTIONAL, deletes all records from most tables after archiving\n";
		print "  [--quiet] = quiet\n";
		print "  [--calc-test] = date calculation test only\n";
		print "  [--test] = test\n\n";
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
		if ($args =~ /--calc-test/i)
			{
			$CALC_TEST=1;
			print "\n-----DATE CALCULATION TESTING ONLY-----\n\n";
			}
		if ($args =~ /--wipe-closer-log/i) 
			{
			$wipe_closer_log=1;
			print "\n----- WIPE CLOSER LOG: $wipe_closer_log -----\n\n";
			}
		if ($args =~ /--wipe-all-being-archived/i) 
			{
			$wipe_all=1;
			print "\n----- WIPE ALL LOG TABLES BEING ARCHIVED: $wipe_all -----\n\n";
			}
		if ($args =~ /--daily/i)
			{
			$daily=1;
			if ($Q < 1) 
				{print "\n----- DAILY ONLY -----\n\n";}
			if ($args =~ /--carrier-daily/i)
				{
				$carrier_daily=1;
				if ($Q < 1) 
					{print "\n----- CARRIER DAILY OPTION -----\n\n";}
				}
			if ($args =~ /--vlog-daily/i)
				{
				$vlog_daily=1;
				if ($Q < 1) 
					{print "\n----- VLOG DAILY OPTION -----\n\n";}
				}
			}
		if ($args =~ /--months=/i)
			{
			@data_in = split(/--months=/,$args);
			$CLImonths = $data_in[1];
			$CLImonths =~ s/ .*$//gi;
			$CLImonths =~ s/\D//gi;
			if ($CLImonths > 9999)
				{$CLImonths=24;}
			if ($Q < 1) 
				{print "\n----- MONTHS OVERRIDE: $CLImonths -----\n\n";}
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
		if ($args =~ /--queue-log/i)
			{
			$queue_log=1;
			if ($Q < 1) 
				{print "\n----- QUEUE LOG ARCHIVE -----\n\n";}
			}
		if ($args =~ /--only-trim-archive-level-one/i)
			{
			$only_trim_archive=1;
			if ($Q < 1) 
				{print "\n----- ONLY TRIM LOG ARCHIVES LEVEL 1 -----\n\n";}
			}
		if ($args =~ /--only-trim-archive-level-two/i)
			{
			$only_trim_archive=2;
			if ($Q < 1) 
				{print "\n----- ONLY TRIM LOG ARCHIVES LEVEL 2 -----\n\n";}
			}
		if ($args =~ /--only-trim-archive-level-three/i)
			{
			$only_trim_archive=3;
			if ($Q < 1) 
				{print "\n----- ONLY TRIM LOG ARCHIVES LEVEL 3 -----\n\n";}
			}
		if ($args =~ /--recording-log-days=/i)
			{
			$recording_log_archive++;
			@data_in = split(/--recording-log-days=/,$args);
			$RECORDINGdays = $data_in[1];
			$RECORDINGdays =~ s/ .*$//gi;
			$RECORDINGdays =~ s/\D//gi;
			if ($RECORDINGdays > 999999)
				{$RECORDINGdays=1825;}
			if ($Q < 1) 
				{print "\n----- RECORDING LOG ARCHIVE ACTIVE, DAYS: $RECORDINGdays -----\n\n";}
			}
		if ($args =~ /--did-log-days=/i)
			{
			$did_log_archive++;
			@data_in = split(/--did-log-days=/,$args);
			$diddays = $data_in[1];
			$diddays =~ s/ .*$//gi;
			$diddays =~ s/\D//gi;
			if ($diddays > 999999)
				{$diddays=1825;}
			if ($Q < 1) 
				{print "\n----- DID LOG ARCHIVE ACTIVE, DAYS: $diddays -----\n\n";}
			}
		if ($args =~ /--cpd-log-purge-days=/i)
			{
			$cpd_log_purge++;
			@data_in = split(/--cpd-log-purge-days=/,$args);
			$CPDdays = $data_in[1];
			$CPDdays =~ s/ .*$//gi;
			$CPDdays =~ s/\D//gi;
			if ($CPDdays > 999999)
				{$CPDdays=1825;}
			if ($Q < 1) 
				{print "\n----- CPD LOG PURGE ACTIVE, DAYS: $CPDdays -----\n\n";}
			}
		}
	}
else
	{
	print "no command line options set\n";
	}
### end parsing run-time options ###
if ( ($CLImonths > 9999) || ($CLImonths < 1) || (length($CLImonths)<1) )
	{$CLImonths=24;}
if ( (length($CLIdays)<1) || ($CLIdays < 1) )
	{
	$CLIdays = ($CLImonths * 30.5);
	$CLIdays = sprintf("%.0f",$CLIdays);
	}

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

if ($daily > 0)
	{
	$CLIdays = '*** DAILY MODE ***';
	$del_epoch = ($secX - 86400);   # 24 hours ago
	($RMsec,$RMmin,$RMhour,$RMmday,$RMmon,$RMyear,$RMwday,$RMyday,$RMisdst) = localtime($del_epoch);
	$RMyear = ($RMyear + 1900);
	$RMmon++;
	if ($RMmon < 10) {$RMmon = "0$RMmon";}
	if ($RMmday < 10) {$RMmday = "0$RMmday";}
	if ($RMhour < 10) {$RMhour = "0$RMhour";}
	if ($RMmin < 10) {$RMmin = "0$RMmin";}
	if ($RMsec < 10) {$RMsec = "0$RMsec";}
	$del_time = "$RMyear-$RMmon-$RMmday $RMhour:$RMmin:$RMsec";
	}
else
	{
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
	}
if ($recording_log_archive > 0) 
	{
	$RECdel_epoch = ($secX - (86400 * $RECORDINGdays));   # X days ago
	($RECsec,$RECmin,$REChour,$RECmday,$RECmon,$RECyear,$RECwday,$RECyday,$RECisdst) = localtime($RECdel_epoch);
	$RECyear = ($RECyear + 1900);
	$RECmon++;
	if ($RECmon < 10) {$RECmon = "0$RECmon";}
	if ($RECmday < 10) {$RECmday = "0$RECmday";}
	if ($REChour < 10) {$REChour = "0$REChour";}
	if ($RECmin < 10) {$RECmin = "0$RECmin";}
	if ($RECsec < 10) {$RECsec = "0$RECsec";}
	$RECdel_time = "$RECyear-$RECmon-$RECmday $REChour:$RECmin:$RECsec";
	}
if ($did_log_archive > 0) 
	{
	$DIDdel_epoch = ($secX - (86400 * $diddays));   # X days ago
	($DIDsec,$DIDmin,$DIDhour,$DIDmday,$DIDmon,$DIDyear,$DIDwday,$DIDyday,$DIDisdst) = localtime($DIDdel_epoch);
	$DIDyear = ($DIDyear + 1900);
	$DIDmon++;
	if ($DIDmon < 10) {$DIDmon = "0$DIDmon";}
	if ($DIDmday < 10) {$DIDmday = "0$DIDmday";}
	if ($DIDhour < 10) {$DIDhour = "0$DIDhour";}
	if ($DIDmin < 10) {$DIDmin = "0$DIDmin";}
	if ($DIDsec < 10) {$DIDsec = "0$DIDsec";}
	$DIDdel_time = "$DIDyear-$DIDmon-$DIDmday $DIDhour:$DIDmin:$DIDsec";
	}
if ($cpd_log_purge > 0) 
	{
	$CPDdel_epoch = ($secX - (86400 * $CPDdays));   # X days ago
	($CPDsec,$CPDmin,$CPDhour,$CPDmday,$CPDmon,$CPDyear,$CPDwday,$CPDyday,$CPDisdst) = localtime($CPDdel_epoch);
	$CPDyear = ($CPDyear + 1900);
	$CPDmon++;
	if ($CPDmon < 10) {$CPDmon = "0$CPDmon";}
	if ($CPDmday < 10) {$CPDmday = "0$CPDmday";}
	if ($CPDhour < 10) {$CPDhour = "0$CPDhour";}
	if ($CPDmin < 10) {$CPDmin = "0$CPDmin";}
	if ($CPDsec < 10) {$CPDsec = "0$CPDsec";}
	$CPDdel_time = "$CPDyear-$CPDmon-$CPDmday $CPDhour:$CPDmin:$CPDsec";
	}


if (!$Q) {print "\n\n-- ADMIN_archive_log_tables.pl --\n\n";}
if (!$Q) {print "This program is designed to put all records from  call_log, vicidial_log,\n";}
if (!$Q) {print "server_performance, vicidial_agent_log, vicidial_carrier_log, \n";}
if (!$Q) {print "vicidial_call_notes, vicidial_lead_search_log and others into relevant\n";}
if (!$Q) {print "_archive tables and delete records in original tables older than\n";}
if (!$Q) {print "$CLIdays days ( $del_time|$del_epoch ) from current date \n\n";}
if ( (!$Q) && ($recording_log_archive > 0) ) {print "REC $RECORDINGdays days ( $RECdel_time|$RECdel_epoch ) from current date \n\n";}
if ( (!$Q) && ($did_log_archive > 0) ) {print "DID $diddays days ( $DIDdel_time|$DIDdel_epoch ) from current date \n\n";}
if ( (!$Q) && ($cpd_log_purge > 0) ) {print "CPD $CPDdays days ( $CPDdel_time|$CPDdel_epoch ) from current date \n\n";}

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
	if ($only_trim_archive > 0)
		{
		# the "only-trim-archive" process will not perform the normal archive process, 
		# instead this will only delete records that are older than XX months from 
		# least important log archive tables. There are three levels:
		#    LEVEL 1:
		# call_log_archive, vicidial_log_extended_archive, vicidial_dial_log_archive, vicidial_drop_log
		#    LEVEL 2:
		# vicidial_carrier_log_archive, vicidial_api_log_archive
		#    LEVEL 3:
		# vicidial_log_archive, vicidial_agent_log_archive, vicidial_closer_log_archive, vicidial_xfer_log_archive
		# NOTE: script will exit once trim process has completed
		if (!$Q) {print "\nONLY-TRIM-ARCHIVE PROCESS LAUNCHING, GOING TO LEVEL: $only_trim_archive\n";}

		if (!$Q) {print "Starting 'only-trim-archive' process, level: ONE\n";}

		##### BEGIN call_log_archive trim processing #####
		$stmtA = "SELECT count(*) from call_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$call_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "Trimming call_log_archive table...  ($call_log_archive_count)\n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM call_log_archive WHERE start_time < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from call_log_archive table \n";}

			$stmtA = "optimize table call_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END call_log_archive trim processing #####
 
 		##### BEGIN vicidial_log_extended_archive trim processing #####
		$stmtA = "SELECT count(*) from vicidial_log_extended_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_log_extended_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "Trimming vicidial_log_extended_archive table...  ($vicidial_log_extended_archive_count)\n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM vicidial_log_extended_archive WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_log_extended_archive table \n";}

			$stmtA = "optimize table vicidial_log_extended_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END vicidial_log_extended_archive trim processing #####

 		##### BEGIN vicidial_drop_log_archive trim processing #####
		$stmtA = "SELECT count(*) from vicidial_drop_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_drop_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "Trimming vicidial_drop_log_archive table...  ($vicidial_drop_log_archive_count)\n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM vicidial_drop_log_archive WHERE drop_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_drop_log_archive table \n";}

			$stmtA = "optimize table vicidial_drop_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END vicidial_drop_log_archive trim processing #####
		
		##### BEGIN vicidial_dial_log_archive trim processing #####
		$stmtA = "SELECT count(*) from vicidial_dial_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_dial_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "Trimming vicidial_dial_log_archive table...  ($vicidial_dial_log_archive_count)\n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM vicidial_dial_log_archive WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_dial_log_archive table \n";}

			$stmtA = "optimize table vicidial_dial_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END vicidial_dial_log_archive trim processing #####


		if ($only_trim_archive > 1)
			{
			if (!$Q) {print "Starting 'only-trim-archive' process, level: TWO\n";}

			##### BEGIN vicidial_carrier_log_archive trim processing #####
			$stmtA = "SELECT count(*) from vicidial_carrier_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_carrier_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "Trimming vicidial_carrier_log_archive table...  ($vicidial_carrier_log_archive_count)\n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_carrier_log_archive WHERE call_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_carrier_log_archive table \n";}

				$stmtA = "optimize table vicidial_carrier_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_carrier_log_archive trim processing #####

			##### BEGIN vicidial_api_log_archive trim processing #####
			$stmtA = "SELECT count(*) from vicidial_api_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_api_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "Trimming vicidial_api_log_archive table...  ($vicidial_api_log_archive_count)\n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_api_log_archive WHERE api_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_api_log_archive table \n";}

				$stmtA = "optimize table vicidial_api_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}

			$stmtA = "SELECT count(*) from vicidial_api_urls_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_api_urls_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "Trimming vicidial_api_urls_archive table...  ($vicidial_api_urls_archive_count)\n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_api_urls_archive WHERE api_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_api_urls_archive table \n";}

				$stmtA = "optimize table vicidial_api_urls_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_api_log_archive trim processing #####

			##### BEGIN vicidial_rt_monitor_log_archive trim processing #####
			$stmtA = "SELECT count(*) from vicidial_rt_monitor_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_rt_monitor_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "Trimming vicidial_rt_monitor_log_archive table...  ($vicidial_rt_monitor_log_archive_count)\n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_rt_monitor_log_archive WHERE monitor_start_time < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_rt_monitor_log_archive table \n";}

				$stmtA = "optimize table vicidial_rt_monitor_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_rt_monitor_log_archive trim processing #####
			}

		if ($only_trim_archive > 2)
			{
			if (!$Q) {print "Starting 'only-trim-archive' process, level: THREE\n";}

			##### BEGIN vicidial_log_archive trim processing #####
			$stmtA = "SELECT count(*) from vicidial_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "Trimming vicidial_log_archive table...  ($vicidial_log_archive_count)\n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_log_archive WHERE call_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_log_archive table \n";}

				$stmtA = "optimize table vicidial_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_log_archive trim processing #####

			##### BEGIN vicidial_agent_log_archive trim processing #####
			$stmtA = "SELECT count(*) from vicidial_agent_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_agent_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "Trimming vicidial_agent_log_archive table...  ($vicidial_agent_log_archive_count)\n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_agent_log_archive WHERE event_time < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_agent_log_archive table \n";}

				$stmtA = "optimize table vicidial_agent_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_agent_log_archive trim processing #####

			##### BEGIN vicidial_closer_log_archive trim processing #####
			$stmtA = "SELECT count(*) from vicidial_closer_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_closer_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "Trimming vicidial_closer_log_archive table...  ($vicidial_closer_log_archive_count)\n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_closer_log_archive WHERE call_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_closer_log_archive table \n";}

				$stmtA = "optimize table vicidial_closer_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_closer_log_archive trim processing #####

			##### BEGIN vicidial_xfer_log_archive trim processing #####
			$stmtA = "SELECT count(*) from vicidial_xfer_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_xfer_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "Trimming vicidial_xfer_log_archive table...  ($vicidial_xfer_log_archive_count)\n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_xfer_log_archive WHERE call_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_xfer_log_archive table \n";}

				$stmtA = "optimize table vicidial_xfer_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_xfer_log_archive trim processing #####
			}

		if (!$Q) {print "Trim process complete, exiting...\n";}

		exit;
		}

	if ($daily > 0)
		{
		# The --daily option was added because these tables(call_log, vicidial_dial_log,  
		# vicidial_log_extended and vicidial_api_log) are not used for any processes or 
		# reports past 24 hours, and on systems dialing 500,000 calls per day or more, 
		# this can lead to system delay issues even if the 1-month archive process is
		# run every weekend. This --daily option will keep only the last 
		# 24-hours and can improve DB performance greatly
		if (!$Q) {print "\nStarting daily arcchive process...\n";}

		##### BEGIN call_log DAILY processing #####
		$stmtA = "SELECT count(*) from call_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$call_log_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from call_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$call_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing call_log table...  ($call_log_count|$call_log_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO call_log_archive SELECT * from call_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into call_log_archive table\n";}
		
		$rv = $sthA->err();
		if (!$rv)
			{
			$stmtA = "DELETE FROM call_log WHERE start_time < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from call_log table \n";}

			$stmtA = "optimize table call_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END call_log DAILY processing #####


		##### BEGIN vicidial_log_extended DAILY processing #####
		$stmtA = "SELECT count(*) from vicidial_log_extended;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_log_extended_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_log_extended_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_log_extended_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_log_extended table...  ($vicidial_log_extended_count|$vicidial_log_extended_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO vicidial_log_extended_archive SELECT * from vicidial_log_extended;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into vicidial_log_extended_archive table \n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM vicidial_log_extended WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_log_extended table \n";}

			$stmtA = "optimize table vicidial_log_extended;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END vicidial_log_extended DAILY processing #####


		##### BEGIN vicidial_drop_log DAILY processing #####
		$stmtA = "SELECT count(*) from vicidial_drop_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_drop_log_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_drop_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_drop_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_drop_log table...  ($vicidial_drop_log_count|$vicidial_drop_log_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO vicidial_drop_log_archive SELECT * from vicidial_drop_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into vicidial_drop_log_archive table \n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM vicidial_drop_log WHERE drop_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_drop_log table \n";}

			$stmtA = "optimize table vicidial_drop_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END vicidial_drop_log DAILY processing #####


		##### BEGIN vicidial_dial_log DAILY processing #####
		$stmtA = "SELECT count(*) from vicidial_dial_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_dial_log_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_dial_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_dial_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_dial_log table...  ($vicidial_dial_log_count|$vicidial_dial_log_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO vicidial_dial_log_archive SELECT * from vicidial_dial_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into vicidial_dial_log_archive table \n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM vicidial_dial_log WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_dial_log table \n";}

			$stmtA = "optimize table vicidial_dial_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END vicidial_dial_log DAILY processing #####


		##### BEGIN vicidial_api_log DAILY processing #####
		$stmtA = "SELECT count(*) from vicidial_api_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_api_log_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_api_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_api_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_api_log table...  ($vicidial_api_log_count|$vicidial_api_log_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO vicidial_api_log_archive SELECT * from vicidial_api_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into vicidial_api_log_archive table \n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM vicidial_api_log WHERE api_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_api_log table \n";}

			$stmtA = "optimize table vicidial_api_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

			$stmtA = "optimize table vicidial_api_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END vicidial_api_log DAILY processing #####


		##### BEGIN vicidial_api_urls DAILY processing #####
		$stmtA = "SELECT count(*) from vicidial_api_urls;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_api_urls_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_api_urls_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_api_urls_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_api_urls table...  ($vicidial_api_urls_count|$vicidial_api_urls_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO vicidial_api_urls_archive SELECT * from vicidial_api_urls;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into vicidial_api_urls_archive table \n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{
			$stmtA = "DELETE FROM vicidial_api_urls WHERE api_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_api_urls table \n";}

			$stmtA = "optimize table vicidial_api_urls;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

			$stmtA = "optimize table vicidial_api_urls_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		##### END vicidial_api_urls DAILY processing #####


		if ($carrier_daily)
			{
			##### BEGIN vicidial_carrier_log DAILY processing #####
			$stmtA = "SELECT count(*) from vicidial_carrier_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_carrier_log_count =	$aryA[0];
				}
			$sthA->finish();

			$stmtA = "SELECT count(*) from vicidial_carrier_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_carrier_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "\nProcessing vicidial_carrier_log table...  ($vicidial_carrier_log_count|$vicidial_carrier_log_archive_count)\n";}
			$stmtA = "INSERT IGNORE INTO vicidial_carrier_log_archive SELECT * from vicidial_carrier_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows inserted into vicidial_carrier_log_archive table \n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_carrier_log WHERE call_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_carrier_log table \n";}

				$stmtA = "optimize table vicidial_carrier_log;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

				$stmtA = "optimize table vicidial_carrier_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_carrier_log DAILY processing #####
			}


		if ($vlog_daily)
			{
			##### BEGIN vicidial_log DAILY processing #####
			$stmtA = "SELECT count(*) from vicidial_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_log_count =	$aryA[0];
				}
			$sthA->finish();

			$stmtA = "SELECT count(*) from vicidial_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				@aryA = $sthA->fetchrow_array;
				$vicidial_log_archive_count =	$aryA[0];
				}
			$sthA->finish();

			if (!$Q) {print "\nProcessing vicidial_log table...  ($vicidial_log_count|$vicidial_log_archive_count)\n";}
			$stmtA = "INSERT IGNORE INTO vicidial_log_archive SELECT * from vicidial_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows inserted into vicidial_log_archive table \n";}
			
			$rv = $sthA->err();
			if (!$rv) 
				{
				$stmtA = "DELETE FROM vicidial_log WHERE call_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				if (!$Q) {print "$sthArows rows deleted from vicidial_log table \n";}

				$stmtA = "optimize table vicidial_log;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

				$stmtA = "optimize table vicidial_log_archive;";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				}
			##### END vicidial_log DAILY processing #####
			}


		### calculate time to run script ###
		$secY = time();
		$secZ = ($secY - $secX);
		$secZm = ($secZ /60);
		if (!$Q) {print "\nscript execution time in seconds: $secZ     minutes: $secZm\n";}

		exit;
		}
	########## END of --daily flag processing ##########



	if ($queue_log > 0)
		{
		#############################################
		##### START QUEUEMETRICS LOGGING LOOKUP #####
		$stmtA = "SELECT enable_queuemetrics_logging,queuemetrics_server_ip,queuemetrics_dbname,queuemetrics_login,queuemetrics_pass,queuemetrics_log_id,queuemetrics_eq_prepend,queuemetrics_loginout,queuemetrics_dispo_pause FROM system_settings;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$enable_queuemetrics_logging =	$aryA[0];
			$queuemetrics_server_ip	=	$aryA[1];
			$queuemetrics_dbname =		$aryA[2];
			$queuemetrics_login=		$aryA[3];
			$queuemetrics_pass =		$aryA[4];
			$queuemetrics_log_id =		$aryA[5];
			$queuemetrics_eq_prepend =	$aryA[6];
			$queuemetrics_loginout =	$aryA[7];
			$queuemetrics_dispo_pause = $aryA[8];
			}
		$sthA->finish();
		##### END QUEUEMETRICS LOGGING LOOKUP #####
		###########################################

		$dbhB = DBI->connect("DBI:mysql:$queuemetrics_dbname:$queuemetrics_server_ip:3306", "$queuemetrics_login", "$queuemetrics_pass")
		 or die "Couldn't connect to database: " . DBI->errstr;

		if ($DBX) {print "CONNECTED TO QM DATABASE:  $queuemetrics_server_ip|$queuemetrics_dbname\n";}

		##### queue_log
		$stmtB = "SELECT count(*) from queue_log;";
		$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
		$sthBrows=$sthB->rows;
		if ($sthBrows > 0)
			{
			@aryB = $sthB->fetchrow_array;
			$queue_log_count =	$aryB[0];
			}
		$sthB->finish();

		$stmtB = "SELECT count(*) from queue_log_archive;";
		$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
		$sthBrows=$sthB->rows;
		if ($sthBrows > 0)
			{
			@aryB = $sthB->fetchrow_array;
			$queue_log_archive_count =	$aryB[0];
			}
		$sthB->finish();

		if (!$Q) {print "\nProcessing queue_log table...  ($queue_log_count|$queue_log_archive_count)\n";}
		$stmtB = "INSERT IGNORE INTO queue_log_archive SELECT * from queue_log;";
		$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
		$sthBrows = $sthB->rows;
		if (!$Q) {print "$sthBrows rows inserted into queue_log_archive table\n";}
		
		$rv = $sthB->err();
		if (!$rv)
			{
			$stmtB = "DELETE FROM queue_log WHERE time_id < $del_epoch;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows = $sthB->rows;
			if (!$Q) {print "$sthBrows rows deleted from queue_log table \n";}

			$stmtB = "optimize table queue_log;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			}
		}


	##### call_log
	$stmtA = "SELECT count(*) from call_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$call_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from call_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$call_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing call_log table...  ($call_log_count|$call_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO call_log_archive SELECT * from call_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into call_log_archive table\n";}
	
	$rv = $sthA->err();
	if (!$rv)
		{
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM call_log;";}
		else
			{$stmtA = "DELETE FROM call_log WHERE start_time < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from call_log table \n";}

		$stmtA = "optimize table call_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "DELETE from call_log_archive where channel LIKE 'Local/9%' and extension not IN('8365','8366','8367','8368','8369','8370','8371','8372','8373','8374') and caller_code LIKE 'V%' and length_in_sec < 75 and start_time < '$del_time';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from call_log_archive table \n";}

		$stmtA = "optimize table call_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_log
	$stmtA = "SELECT count(*) from vicidial_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_log table...  ($vicidial_log_count|$vicidial_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_log_archive SELECT * from vicidial_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_log WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_log table \n";}

		$stmtA = "optimize table vicidial_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_closer_log
	$stmtA = "SELECT count(*) from vicidial_closer_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_closer_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_closer_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_closer_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_closer_log table...  ($vicidial_closer_log_count|$vicidial_closer_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_closer_log_archive SELECT * from vicidial_closer_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_closer_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{
		if ( ($wipe_closer_log > 0) || ($wipe_all > 0) )
			{$stmtA = "DELETE FROM vicidial_closer_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_closer_log WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_closer_log table \n";}

		$stmtA = "optimize table vicidial_closer_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_closer_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_xfer_log
	$stmtA = "SELECT count(*) from vicidial_xfer_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_xfer_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_xfer_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_xfer_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_xfer_log table...  ($vicidial_xfer_log_count|$vicidial_xfer_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_xfer_log_archive SELECT * from vicidial_xfer_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_xfer_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_xfer_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_xfer_log WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_xfer_log table \n";}

		$stmtA = "optimize table vicidial_xfer_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_xfer_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_log_extended
	$stmtA = "SELECT count(*) from vicidial_log_extended;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_log_extended_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_log_extended_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_log_extended_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_log_extended table...  ($vicidial_log_extended_count|$vicidial_log_extended_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_log_extended_archive SELECT * from vicidial_log_extended;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_log_extended_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_log_extended;";}
		else
			{$stmtA = "DELETE FROM vicidial_log_extended WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_log_extended table \n";}

		$stmtA = "optimize table vicidial_log_extended;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_log_extended_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_drop_log
	$stmtA = "SELECT count(*) from vicidial_drop_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_drop_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_drop_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_drop_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_drop_log table...  ($vicidial_drop_log_count|$vicidial_drop_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_drop_log_archive SELECT * from vicidial_drop_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_drop_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_drop_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_drop_log WHERE drop_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_drop_log table \n";}

		$stmtA = "optimize table vicidial_drop_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_drop_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_dial_log
	$stmtA = "SELECT count(*) from vicidial_dial_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_dial_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_dial_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_dial_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_dial_log table...  ($vicidial_dial_log_count|$vicidial_dial_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_dial_log_archive SELECT * from vicidial_dial_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_dial_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_dial_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_dial_log WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_dial_log table \n";}

		$stmtA = "optimize table vicidial_dial_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_dial_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}



	##### vicidial_api_log
	$stmtA = "SELECT count(*) from vicidial_api_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_api_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_api_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_api_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_api_log table...  ($vicidial_api_log_count|$vicidial_api_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_api_log_archive SELECT * from vicidial_api_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_api_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_api_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_api_log WHERE api_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_api_log table \n";}

		$stmtA = "optimize table vicidial_api_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_api_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_api_urls
	$stmtA = "SELECT count(*) from vicidial_api_urls;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_api_urls_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_api_urls_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_api_urls_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_api_urls table...  ($vicidial_api_urls_count|$vicidial_api_urls_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_api_urls_archive SELECT * from vicidial_api_urls;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_api_urls_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_api_urls;";}
		else
			{$stmtA = "DELETE FROM vicidial_api_urls WHERE api_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_api_urls table \n";}

		$stmtA = "optimize table vicidial_api_urls;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_api_urls_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_rt_monitor_log
	$stmtA = "SELECT count(*) from vicidial_rt_monitor_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_rt_monitor_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_rt_monitor_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_rt_monitor_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_rt_monitor_log table...  ($vicidial_rt_monitor_log_count|$vicidial_rt_monitor_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_rt_monitor_log_archive SELECT * from vicidial_rt_monitor_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_rt_monitor_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_rt_monitor_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_rt_monitor_log WHERE monitor_start_time < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_rt_monitor_log table \n";}

		$stmtA = "optimize table vicidial_rt_monitor_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_rt_monitor_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### user_call_log
	$user_call_log_count_mil=0;
	$stmtA = "SELECT count(*) from user_call_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$user_call_log_count =	$aryA[0];
		}
	$sthA->finish();
	$user_call_log_count_mil = ($user_call_log_count - 1000000);

	$stmtA = "SELECT count(*) from user_call_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$user_call_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing user_call_log table...  ($user_call_log_count|$user_call_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO user_call_log_archive SELECT * from user_call_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into user_call_log_archive table \n";}
	
	$rv = $sthA->err();
	if ( (!$rv) && ($user_call_log_count_mil > 0) )
		{
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM user_call_log;";}
		else
			{$stmtA = "DELETE FROM user_call_log WHERE call_date < '$del_time' order by user_call_log_id limit $user_call_log_count_mil;";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from user_call_log table \n";}

		$stmtA = "optimize table user_call_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table user_call_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}



	##### vicidial_inbound_survey_log
	$stmtA = "SELECT count(*) from vicidial_inbound_survey_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_inbound_survey_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_inbound_survey_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_inbound_survey_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_inbound_survey_log table...  ($vicidial_inbound_survey_log_count|$vicidial_inbound_survey_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_inbound_survey_log_archive SELECT * from vicidial_inbound_survey_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_inbound_survey_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_inbound_survey_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_inbound_survey_log WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_inbound_survey_log table \n";}

		$stmtA = "optimize table vicidial_inbound_survey_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_inbound_survey_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}



	##### vicidial_log_noanswer
	$stmtA = "SELECT count(*) from vicidial_log_noanswer;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_log_noanswer_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_log_noanswer_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_log_noanswer_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_log_noanswer table...  ($vicidial_log_noanswer_count|$vicidial_log_noanswer_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_log_noanswer_archive SELECT * from vicidial_log_noanswer;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_log_noanswer_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_log_noanswer;";}
		else
			{$stmtA = "DELETE FROM vicidial_log_noanswer WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_log_noanswer table \n";}

		$stmtA = "optimize table vicidial_log_noanswer;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_log_noanswer_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}



	##### vicidial_did_agent_log
	$stmtA = "SELECT count(*) from vicidial_did_agent_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_did_agent_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_did_agent_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_did_agent_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_did_agent_log table...  ($vicidial_did_agent_log_count|$vicidial_did_agent_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_did_agent_log_archive SELECT * from vicidial_did_agent_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_did_agent_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_did_agent_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_did_agent_log WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_did_agent_log table \n";}

		$stmtA = "optimize table vicidial_did_agent_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_did_agent_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}



	##### server_performance
	$stmtA = "SELECT count(*) from server_performance;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$server_performance_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing server_performance table...  ($server_performance_count)\n";}
	if ($wipe_all > 0)
		{$stmtA = "DELETE FROM server_performance;";}
	else
		{$stmtA = "DELETE FROM server_performance WHERE start_time < '$del_time';";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows deleted from server_performance table \n";}

	$stmtA = "optimize table server_performance;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;


	##### vicidial_url_log
	$stmtA = "SELECT count(*) from vicidial_url_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_url_log_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_url_log table...  ($vicidial_url_log_count)\n";}
	$stmtA = "DELETE FROM vicidial_url_log WHERE url_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows deleted from vicidial_url_log table \n";}

	$stmtA = "optimize table vicidial_url_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;


	##### vicidial_dtmf_log
	$stmtA = "SELECT count(*) from vicidial_dtmf_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_dtmf_log_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_dtmf_log table...  ($vicidial_dtmf_log_count)\n";}
	$stmtA = "DELETE FROM vicidial_dtmf_log WHERE dtmf_time < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows deleted from vicidial_dtmf_log table \n";}

	$stmtA = "optimize table vicidial_dtmf_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;


	##### vicidial_agent_log
	$stmtA = "SELECT count(*) from vicidial_agent_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_agent_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_agent_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_agent_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_agent table...  ($vicidial_agent_log_count|$vicidial_agent_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_agent_log_archive SELECT * from vicidial_agent_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_agent_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_agent_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_agent_log WHERE event_time < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_agent_log table \n";}

		$stmtA = "optimize table vicidial_agent_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_agent_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_carrier_log
	$stmtA = "SELECT count(*) from vicidial_carrier_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_carrier_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_carrier_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_carrier_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_carrier_log table...  ($vicidial_carrier_log_count|$vicidial_carrier_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_carrier_log_archive SELECT * from vicidial_carrier_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_carrier_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_carrier_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_carrier_log WHERE call_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_carrier_log table \n";}

		$stmtA = "optimize table vicidial_carrier_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_carrier_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}



	##### vicidial_call_notes
	$stmtA = "SELECT count(*) from vicidial_call_notes;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_call_notes_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_call_notes_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_call_notes_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_call_notes table...  ($vicidial_call_notes_count|$vicidial_call_notes_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_call_notes_archive SELECT * from vicidial_call_notes;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_call_notes_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		$stmtA = "DELETE FROM vicidial_call_notes WHERE call_date < '$del_time';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_call_notes table \n";}

		$stmtA = "optimize table vicidial_call_notes;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_call_notes_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}



	##### vicidial_lead_search_log
	$stmtA = "SELECT count(*) from vicidial_lead_search_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_lead_search_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_lead_search_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_lead_search_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_lead_search_log table...  ($vicidial_lead_search_log_count|$vicidial_lead_search_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_lead_search_log_archive SELECT * from vicidial_lead_search_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_lead_search_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		$stmtA = "DELETE FROM vicidial_lead_search_log WHERE event_date < '$del_time';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_lead_search_log table \n";}

		$stmtA = "optimize table vicidial_lead_search_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_lead_search_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	##### vicidial_agent_function_log
	$stmtA = "SELECT count(*) from vicidial_agent_function_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_agent_function_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_agent_function_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_agent_function_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_agent_function_log table...  ($vicidial_agent_function_log_count|$vicidial_agent_function_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_agent_function_log_archive SELECT * from vicidial_agent_function_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_agent_function_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_agent_function_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_agent_function_log WHERE event_time < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_agent_function_log table \n";}

		$stmtA = "optimize table vicidial_agent_function_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_agent_function_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}



	##### vicidial_outbound_ivr_log
	$stmtA = "SELECT count(*) from vicidial_outbound_ivr_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_outbound_ivr_log_count =	$aryA[0];
		}
	$sthA->finish();

	$stmtA = "SELECT count(*) from vicidial_outbound_ivr_log_archive;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_outbound_ivr_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "\nProcessing vicidial_outbound_ivr_log table...  ($vicidial_outbound_ivr_log_count|$vicidial_outbound_ivr_log_archive_count)\n";}
	$stmtA = "INSERT IGNORE INTO vicidial_outbound_ivr_log_archive SELECT * from vicidial_outbound_ivr_log;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	
	$sthArows = $sthA->rows;
	if (!$Q) {print "$sthArows rows inserted into vicidial_outbound_ivr_log_archive table \n";}
	
	$rv = $sthA->err();
	if (!$rv) 
		{	
		if ($wipe_all > 0)
			{$stmtA = "DELETE FROM vicidial_outbound_ivr_log;";}
		else
			{$stmtA = "DELETE FROM vicidial_outbound_ivr_log WHERE event_date < '$del_time';";}
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_outbound_ivr_log table \n";}

		$stmtA = "optimize table vicidial_outbound_ivr_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

		$stmtA = "optimize table vicidial_outbound_ivr_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	if ($recording_log_archive > 0) 
		{
		##### recording_log
		$stmtA = "SELECT count(*) from recording_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$recording_log_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from recording_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$recording_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing recording_log table...  ($recording_log_count|$recording_log_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO recording_log_archive SELECT * from recording_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into recording_log_archive table \n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{	
			$stmtA = "DELETE FROM recording_log WHERE start_time < '$RECdel_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from recording_log table \n";}

			$stmtA = "optimize table recording_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

			$stmtA = "optimize table recording_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		}


	if ($did_log_archive > 0) 
		{
		##### vicidial_did_log
		$stmtA = "SELECT count(*) from vicidial_did_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$did_log_count =	$aryA[0];
			}
		$sthA->finish();

		$stmtA = "SELECT count(*) from vicidial_did_log_archive;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$did_log_archive_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_did_log table...  ($did_log_count|$did_log_archive_count)\n";}
		$stmtA = "INSERT IGNORE INTO vicidial_did_log_archive SELECT * from vicidial_did_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows inserted into vicidial_did_log_archive table \n";}
		
		$rv = $sthA->err();
		if (!$rv) 
			{	
			$stmtA = "DELETE FROM vicidial_did_log WHERE call_date < '$DIDdel_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows = $sthA->rows;
			if (!$Q) {print "$sthArows rows deleted from vicidial_did_log table \n";}

			$stmtA = "optimize table vicidial_did_log;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

			$stmtA = "optimize table vicidial_did_log_archive;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			}
		}


	if ($cpd_log_purge > 0) 
		{
		##### vicidial_cpd_log
		$stmtA = "SELECT count(*) from vicidial_cpd_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_cpd_log_count =	$aryA[0];
			}
		$sthA->finish();

		if (!$Q) {print "\nProcessing vicidial_cpd_log table...  ($vicidial_cpd_log_count)\n";}

		$stmtA = "DELETE FROM vicidial_cpd_log WHERE event_date < '$CPDdel_time';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows = $sthA->rows;
		if (!$Q) {print "$sthArows rows deleted from vicidial_cpd_log table \n";}

		$stmtA = "optimize table vicidial_cpd_log;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		}


	$stmtA = "UPDATE system_settings SET oldest_logs_date='$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	}


### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);
if (!$Q) {print "\nscript execution time in seconds: $secZ     minutes: $secZm\n";}

exit;
