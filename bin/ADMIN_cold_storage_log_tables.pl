#!/usr/bin/perl
#
# ADMIN_cold_storage_log_tables.pl	version 2.14
#
# This script is designed take records of a specific age from call_log_archive, 
# vicidial_log_archive, vicidial_log_extended_archive and
# other _archive tables and move them to the cold-storage log database, then
# delete the records in original tables older than X days
#
# Place in the crontab and run every month after one in the morning, or whenever
# your server is not busy with other tasks
# 30 1 1 * * /usr/share/astguiclient/ADMIN_cold_storage_log_tables.pl
#
# NOTE: On a high-load outbound dialing system, this script can take hours to 
# run. While the script is running the archive reporting can be unusable. 
# Please schedule to run this script at a time when the reports will not be 
# used for several hours.
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 240704-1815 - First version
# 240705-0954 - Added --query-count-test flag option
# 240711-1938 - Added --no-optimize-tables flag option
#

$DB=0;
$DBX=0;
$CALC_TEST=0;
$QUERY_COUNT_TEST=0;
$NO_OPTIMIZE_TABLES=0;
$T=0;   $TEST=0;
$only_trim_archive=0;
$recording_log_archive=0;
$did_log_archive=0;
$park_log_archive=0;
$wipe_closer_log=0;
$api_log_only=0;
$api_archive_only=0;
$url_log_only=0;
$url_log_archive=0;

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
		print "  [--days=XX] = number of days to archive past, default is 1461(4 years)\n";
		print "  [--quiet] = quiet\n";
		print "  [--calc-test] = date calculation test only\n";
		print "  [--query-count-test] = run archive counts test only\n";
		print "  [--no-optimize-tables] = do not optimize the archive tables after deletion of rows \n";
		print "  [--test] = test\n";
		print "  [--debug] = debug output for some options\n";
		print "  [--debugX] = extra debug output for some options\n";
		print "\n";
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
			print "\n-----DEBUG EXTRA-----\n\n";
			}
		if ($args =~ /--calc-test/i)
			{
			$CALC_TEST=1;
			print "\n-----DATE CALCULATION TESTING ONLY: $CALC_TEST-----\n\n";
			}
		if ($args =~ /--query-count-test/i)
			{
			$QUERY_COUNT_TEST=1;
			print "\n-----ARCHIVE TABLES QUERY COUNT TESTING ONLY: $QUERY_COUNT_TEST-----\n\n";
			}
		if ($args =~ /--no-optimize-tables/i)
			{
			$NO_OPTIMIZE_TABLES=1;
			print "\n-----DO NOT OPTIMIZE ARCHIVE TABLES AFTER ROWS DELETION: $NO_OPTIMIZE_TABLES-----\n\n";
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
		}
	}
else
	{
	print "no command line options set\n";
	}
### end parsing run-time options ###
if ( (length($CLIdays)<1) || ($CLIdays < 1) )
	{
	$CLIdays = 1461;
	}

$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

$now_epoch = ($secX);   # now
($Nsec,$Nmin,$Nhour,$Nmday,$Nmon,$Nyear,$Nwday,$Nyday,$Nisdst) = localtime($now_epoch);
$Nyear = ($Nyear + 1900);
$Nmon++;
if ($Nmon < 10) {$Nmon = "0$Nmon";}
if ($Nmday < 10) {$Nmday = "0$Nmday";}
if ($Nhour < 10) {$Nhour = "0$Nhour";}
if ($Nmin < 10) {$Nmin = "0$Nmin";}
if ($Nsec < 10) {$Nsec = "0$Nsec";}
$now_time = "$Nyear-$Nmon-$Nmday $Nhour:$Nmin:$Nsec";
$now_date = "$Nyear-$Nmon-$Nmday";
$now_test_date = "$Nyear$Nmon$Nmday$Nhour$Nmin$Nsec";

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
$del_test_date = "$RMyear$RMmon$RMmday$RMhour$RMmin$RMsec";

if (!$Q) {print "\n\n-- ADMIN_cold_storage_log_tables.pl --\n\n";}
if (!$Q) {print "This script is designed take records of a specific age from call_log_archive,\n";}
if (!$Q) {print "vicidial_log_archive, vicidial_log_extended_archive and\n";}
if (!$Q) {print "other _archive tables and move them to the cold-storage log database, then\n";}
if (!$Q) {print "delete the records in original tables older than X days\n";}
if (!$Q) {print "$CLIdays days ( $del_time [$del_date]|$del_epoch ) from current date \n\n";}

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
	if ( ($line =~ /^VARCS_server/) && ($CLICS_server < 1) )
		{$VARCS_server = $line;   $VARCS_server =~ s/.*=//gi;}
	if ( ($line =~ /^VARCS_database/) && ($CLICS_database < 1) )
		{$VARCS_database = $line;   $VARCS_database =~ s/.*=//gi;}
	if ( ($line =~ /^VARCS_user/) && ($CLICS_user < 1) )
		{$VARCS_user = $line;   $VARCS_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARCS_pass/) && ($CLICS_pass < 1) )
		{$VARCS_pass = $line;   $VARCS_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARCS_port/) && ($CLICS_port < 1) )
		{$VARCS_port = $line;   $VARCS_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

use DBI;
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARCS_database:$VARCS_server:$VARCS_port", "$VARCS_user", "$VARCS_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

#############################################
##### Gather system_settings #####
$stmtA = "SELECT sip_event_logging FROM system_settings;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$SSsip_event_logging =	$aryA[0];
	}
$sthA->finish();
###########################################

#############################################
##### Test Cold-Storage Database connection with query of system_settings #####
$stmtB = "SELECT count(*) FROM system_settings;";
$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
$sthBrows=$sthB->rows;
if ($sthBrows > 0)
	{
	@aryB = $sthB->fetchrow_array;
	$SScount =	$aryB[0];
	if ($DB > 0) {print "Cold-Storage Database test: $SScount \n";}
	}
$sthB->finish();
###########################################

if ($CALC_TEST > 0)
	{
	exit;
	}

if (!$T) 
	{
	if (!$Q) {print "\nStarting cold-storage process...\n";}

	##### BEGIN call_log_archive cold-storage processing #####
	$call_log_archive_count=0;
	$stmtA = "SELECT count(*) from call_log_archive WHERE start_time < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$call_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from call_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing call_log_archive table...  ($call_log_archive_count|$call_log_CS_count)\n";}

	if ( ($call_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "call_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from call_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from call_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "call_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "call_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM call_log_archive WHERE start_time < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO call_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO call_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "call_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "call_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM call_log_archive WHERE start_time < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from call_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE call_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "call_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "call_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END call_log_archive cold-storage processing #####


	##### BEGIN vicidial_log_archive cold-storage processing #####
	$vicidial_log_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_log_archive WHERE call_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_log_archive table...  ($vicidial_log_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_log_archive WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_log_archive WHERE call_date < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_log_archive cold-storage processing #####


	##### BEGIN vicidial_log_extended_archive cold-storage processing #####
	$vicidial_log_extended_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_log_extended_archive WHERE call_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_log_extended_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_log_extended_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_log_extended_archive table...  ($vicidial_log_extended_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_log_extended_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_log_extended_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_log_extended_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_log_extended_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_log_extended_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_log_extended_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_log_extended_archive WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_log_extended_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_log_extended_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_log_extended_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_log_extended_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_log_extended_archive WHERE call_date < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_log_extended_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_log_extended_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_log_extended_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_log_extended_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_log_extended_archive cold-storage processing #####


	##### BEGIN vicidial_dial_log_archive cold-storage processing #####
	$vicidial_dial_log_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_dial_log_archive WHERE call_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_dial_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_dial_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_dial_log_archive table...  ($vicidial_dial_log_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_dial_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_dial_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_dial_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_dial_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_dial_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_dial_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_dial_log_archive WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_dial_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_dial_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_dial_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_dial_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_dial_log_archive WHERE call_date < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_dial_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_dial_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_dial_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_dial_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_dial_log_archive cold-storage processing #####


	##### BEGIN vicidial_carrier_log_archive cold-storage processing #####
	$vicidial_carrier_log_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_carrier_log_archive WHERE call_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_carrier_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_carrier_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_carrier_log_archive table...  ($vicidial_carrier_log_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_carrier_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_carrier_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_carrier_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_carrier_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_carrier_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_carrier_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_carrier_log_archive WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_carrier_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_carrier_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_carrier_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_carrier_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_carrier_log_archive WHERE call_date < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_carrier_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_carrier_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_carrier_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_carrier_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_carrier_log_archive cold-storage processing #####


	##### BEGIN vicidial_dial_cid_log_archive cold-storage processing #####
	$vicidial_dial_cid_log_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_dial_cid_log_archive WHERE call_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_dial_cid_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_dial_cid_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_dial_cid_log_archive table...  ($vicidial_dial_cid_log_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_dial_cid_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_dial_cid_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_dial_cid_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_dial_cid_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_dial_cid_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_dial_cid_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_dial_cid_log_archive WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_dial_cid_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_dial_cid_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_dial_cid_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_dial_cid_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_dial_cid_log_archive WHERE call_date < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_dial_cid_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_dial_cid_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_dial_cid_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_dial_cid_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_dial_cid_log_archive cold-storage processing #####


	##### BEGIN vicidial_amd_log_archive cold-storage processing #####
	$vicidial_amd_log_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_amd_log_archive WHERE call_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_amd_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_amd_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_amd_log_archive table...  ($vicidial_amd_log_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_amd_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_amd_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_amd_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_amd_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_amd_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_amd_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_amd_log_archive WHERE call_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_amd_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_amd_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_amd_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_amd_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_amd_log_archive WHERE call_date < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_amd_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_amd_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_amd_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_amd_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_amd_log_archive cold-storage processing #####


	##### BEGIN vicidial_agent_log_archive cold-storage processing #####
	$vicidial_agent_log_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_agent_log_archive WHERE event_time < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_agent_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_agent_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_agent_log_archive table...  ($vicidial_agent_log_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_agent_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_agent_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_agent_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_agent_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_agent_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_agent_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_agent_log_archive WHERE event_time < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_agent_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_agent_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_agent_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_agent_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_agent_log_archive WHERE event_time < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_agent_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_agent_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_agent_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_agent_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_agent_log_archive cold-storage processing #####


	##### BEGIN vicidial_api_log_archive cold-storage processing #####
	$vicidial_api_log_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_api_log_archive WHERE api_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_api_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_api_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_api_log_archive table...  ($vicidial_api_log_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_api_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_api_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_api_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_api_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_api_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_api_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_api_log_archive WHERE api_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_api_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_api_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_api_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_api_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_api_log_archive WHERE api_date < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_api_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_api_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_api_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_api_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_api_log_archive cold-storage processing #####


	##### BEGIN vicidial_api_urls_archive cold-storage processing #####
	$vicidial_api_urls_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_api_urls_archive WHERE api_date < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_api_urls_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_api_urls_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_api_urls_archive table...  ($vicidial_api_urls_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_api_urls_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_api_urls_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_api_urls_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_api_urls_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_api_urls_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_api_urls_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_api_urls_archive WHERE api_date < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_api_urls_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_api_urls_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_api_urls_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_api_urls_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_api_urls_archive WHERE api_date < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_api_urls_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_api_urls_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_api_urls_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_api_urls_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_api_urls_archive cold-storage processing #####


	##### BEGIN vicidial_agent_visibility_log_archive cold-storage processing #####
	$vicidial_agent_visibility_log_archive_count=0;
	$stmtA = "SELECT count(*) from vicidial_agent_visibility_log_archive WHERE db_time < '$del_time';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$vicidial_agent_visibility_log_archive_count =	$aryA[0];
		}
	$sthA->finish();

	$call_log_CS_count=0;
	$stmtB = "SELECT count(*) from vicidial_agent_visibility_log_archive;";
	$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
	$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
	$sthBrows=$sthB->rows;
	if ($sthBrows > 0)
		{
		@aryB = $sthB->fetchrow_array;
		$call_log_CS_count =	$aryB[0];
		}
	$sthB->finish();

	if (!$Q) {print "\nAnalyzing vicidial_agent_visibility_log_archive table...  ($vicidial_agent_visibility_log_archive_count|$call_log_CS_count)\n";}

	if ( ($vicidial_agent_visibility_log_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
		{
		if (!$Q) {print "vicidial_agent_visibility_log_archive has no records to send to cold-storage, skipping this table...\n";}
		}
	else
		{
		$sthAfields=0;
		$stmtA = "SELECT * from vicidial_agent_visibility_log_archive limit 1;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			$sthAfields = $sthA->{'NUM_OF_FIELDS'};
			}
		$sthA->finish();

		$sthBfields=0;
		if ($call_log_CS_count > 0) 
			{
			$stmtB = "SELECT * from vicidial_agent_visibility_log_archive limit 1;";
			$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
			$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
			$sthBrows=$sthB->rows;
			if ($sthBrows > 0)
				{
				$sthBfields = $sthB->{'NUM_OF_FIELDS'};
				}
			$sthB->finish();
			}
		else
			{
			if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
			$sthBfields = $sthAfields;
			}

		if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
			{
			if (!$Q) {print "vicidial_agent_visibility_log_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
			}
		else
			{
			if (!$Q) {print "vicidial_agent_visibility_log_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

			$stmtA = "SELECT * FROM vicidial_agent_visibility_log_archive WHERE db_time < '$del_time';";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			$Arow_ct=0;
			$Irows_ct=0;
			$Btotal_inserted=0;
			$insert_stmt='INSERT IGNORE INTO vicidial_agent_visibility_log_archive VALUES';
			while ($sthArows > $Arow_ct)
				{
				@aryA = $sthA->fetchrow_array;
				$Afield_ct=0;
				while ($sthAfields > $Afield_ct)
					{
					if ($Afield_ct < 1) {$insert_stmt .= '(';}
					else{$insert_stmt .= ',';}
					$tmp_field = $aryA[$Afield_ct];
					$tmp_field =~ s/\"//gi;
					$insert_stmt .= "\"$tmp_field\"";
					$Afield_ct++;
					}
				$insert_stmt .= '),';
				$Irows_ct++;

				if ($Irows_ct > 99) 
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
					$Irows_ct=0;
					$insert_stmt='INSERT IGNORE INTO vicidial_agent_visibility_log_archive VALUES';
					}
				$Arow_ct++;
				if ($Q < 1) 
					{
					if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
					if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
					if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
					if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
					if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
					if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
					if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
					}
				}
			$sthA->finish();

			if ($Irows_ct > 0)
				{
				chop($insert_stmt);
				$stmtB = "$insert_stmt;";
				$affected_rowsB = $dbhB->do($stmtB);
				$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
				if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
				}
			if (!$Q) {print "vicidial_agent_visibility_log_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

			if ($Btotal_inserted ne $sthArows) 
				{
				print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
				exit;
				}
			else
				{
				if (!$Q) {print "vicidial_agent_visibility_log_archive $sthArows deletions starting...\n";}

				$stmtA = "DELETE FROM vicidial_agent_visibility_log_archive WHERE db_time < '$del_time';";
				$affected_rowsA = $dbhA->do($stmtA);
				if (!$Q) {print STDERR "records deleted from vicidial_agent_visibility_log_archive: $affected_rowsA|$stmtA|\n";}

				if ($NO_OPTIMIZE_TABLES < 1) 
					{
					$stmtA = "OPTIMIZE TABLE vicidial_agent_visibility_log_archive;";
					$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
					$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
					if (!$Q) {print STDERR "vicidial_agent_visibility_log_archive table optimized, cold-storage process is complete for this table!\n";}
					}
				else
					{
					if (!$Q) {print STDERR "vicidial_agent_visibility_log_archive table skip optimize\n";}
					}
				}
			}
		}
	##### END vicidial_agent_visibility_log_archive cold-storage processing #####




	if ($SSsip_event_logging > 0)
		{
		##### BEGIN vicidial_log_extended_sip_archive cold-storage processing #####
		$vicidial_log_extended_sip_archive_count=0;
		$stmtA = "SELECT count(*) from vicidial_log_extended_sip_archive WHERE call_date < '$del_time';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$vicidial_log_extended_sip_archive_count =	$aryA[0];
			}
		$sthA->finish();

		$call_log_CS_count=0;
		$stmtB = "SELECT count(*) from vicidial_log_extended_sip_archive;";
		$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
		$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
		$sthBrows=$sthB->rows;
		if ($sthBrows > 0)
			{
			@aryB = $sthB->fetchrow_array;
			$call_log_CS_count =	$aryB[0];
			}
		$sthB->finish();

		if (!$Q) {print "\nAnalyzing vicidial_log_extended_sip_archive table...  ($vicidial_log_extended_sip_archive_count|$call_log_CS_count)\n";}

		if ( ($vicidial_log_extended_sip_archive_count < 1) || ($QUERY_COUNT_TEST > 0) )
			{
			if (!$Q) {print "vicidial_log_extended_sip_archive has no records to send to cold-storage, skipping this table...\n";}
			}
		else
			{
			$sthAfields=0;
			$stmtA = "SELECT * from vicidial_log_extended_sip_archive limit 1;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArows=$sthA->rows;
			if ($sthArows > 0)
				{
				$sthAfields = $sthA->{'NUM_OF_FIELDS'};
				}
			$sthA->finish();

			$sthBfields=0;
			if ($call_log_CS_count > 0) 
				{
				$stmtB = "SELECT * from vicidial_log_extended_sip_archive limit 1;";
				$sthB = $dbhB->prepare($stmtB) or die "preparing: ",$dbhB->errstr;
				$sthB->execute or die "executing: $stmtB ", $dbhB->errstr;
				$sthBrows=$sthB->rows;
				if ($sthBrows > 0)
					{
					$sthBfields = $sthB->{'NUM_OF_FIELDS'};
					}
				$sthB->finish();
				}
			else
				{
				if($DBX){print STDERR "DEBUG: empty cold-storage table, no field count check\n";}
				$sthBfields = $sthAfields;
				}

			if ( ($sthAfields < 1) || ($sthBfields ne $sthAfields) )
				{
				if (!$Q) {print "vicidial_log_extended_sip_archive cold-storage fields mismatch error($sthBfields|$sthAfields), skipping this table...\n";}
				}
			else
				{
				if (!$Q) {print "vicidial_log_extended_sip_archive cold-storage fields count match($sthBfields|$sthAfields), starting cold-storage move...\n";}

				$stmtA = "SELECT * FROM vicidial_log_extended_sip_archive WHERE call_date < '$del_time';";
				$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
				$sthArows=$sthA->rows;
				$Arow_ct=0;
				$Irows_ct=0;
				$Btotal_inserted=0;
				$insert_stmt='INSERT IGNORE INTO vicidial_log_extended_sip_archive VALUES';
				while ($sthArows > $Arow_ct)
					{
					@aryA = $sthA->fetchrow_array;
					$Afield_ct=0;
					while ($sthAfields > $Afield_ct)
						{
						if ($Afield_ct < 1) {$insert_stmt .= '(';}
						else{$insert_stmt .= ',';}
						$tmp_field = $aryA[$Afield_ct];
						$tmp_field =~ s/\"//gi;
						$insert_stmt .= "\"$tmp_field\"";
						$Afield_ct++;
						}
					$insert_stmt .= '),';
					$Irows_ct++;

					if ($Irows_ct > 99) 
						{
						chop($insert_stmt);
						$stmtB = "$insert_stmt;";
						$affected_rowsB = $dbhB->do($stmtB);
						$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
						if($DBX){print STDERR "\n|$affected_rowsB|$stmtB|\n";}
						$Irows_ct=0;
						$insert_stmt='INSERT IGNORE INTO vicidial_log_extended_sip_archive VALUES';
						}
					$Arow_ct++;
					if ($Q < 1) 
						{
						if ($Arow_ct =~ /10000$/i) {print STDERR "0     $Arow_ct\r";}
						if ($Arow_ct =~ /20000$/i) {print STDERR "+     $Arow_ct\r";}
						if ($Arow_ct =~ /30000$/i) {print STDERR "|     $Arow_ct\r";}
						if ($Arow_ct =~ /40000$/i) {print STDERR "\\     $Arow_ct\r";}
						if ($Arow_ct =~ /50000$/i) {print STDERR "-     $Arow_ct\r";}
						if ($Arow_ct =~ /60000$/i) {print STDERR "/     $Arow_ct\r";}
						if ($Arow_ct =~ /70000$/i) {print STDERR "|     $Arow_ct\r";}
						if ($Arow_ct =~ /80000$/i) {print STDERR "+     $Arow_ct\r";}
						if ($Arow_ct =~ /90000$/i) {print STDERR "0     $Arow_ct\r";}
						if ($Arow_ct =~ /00000$/i) {print "$Arow_ct|$Btotal_inserted|\n";}
						}
					}
				$sthA->finish();

				if ($Irows_ct > 0)
					{
					chop($insert_stmt);
					$stmtB = "$insert_stmt;";
					$affected_rowsB = $dbhB->do($stmtB);
					$Btotal_inserted = ($Btotal_inserted + $affected_rowsB);
					if($DBX){print STDERR "\n|$affected_rowsB|LAST INSERT|$stmtB|\n";}
					}
				if (!$Q) {print "vicidial_log_extended_sip_archive cold-storage inserts completed ($Btotal_inserted / $sthArows)\n";}

				if ($Btotal_inserted ne $sthArows) 
					{
					print "ERROR! records inserted is les than records to be inserted ($Btotal_inserted / $sthArows)\n";
					exit;
					}
				else
					{
					if (!$Q) {print "vicidial_log_extended_sip_archive $sthArows deletions starting...\n";}

					$stmtA = "DELETE FROM vicidial_log_extended_sip_archive WHERE call_date < '$del_time';";
					$affected_rowsA = $dbhA->do($stmtA);
					if (!$Q) {print STDERR "records deleted from vicidial_log_extended_sip_archive: $affected_rowsA|$stmtA|\n";}

					if ($NO_OPTIMIZE_TABLES < 1) 
						{
						$stmtA = "OPTIMIZE TABLE vicidial_log_extended_sip_archive;";
						$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
						$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
						if (!$Q) {print STDERR "vicidial_log_extended_sip_archive table optimized, cold-storage process is complete for this table!\n";}
						}
					else
						{
						if (!$Q) {print STDERR "vicidial_log_extended_sip_archive table skip optimize\n";}
						}
					}
				}
			}
		##### END vicidial_log_extended_sip_archive cold-storage processing #####
		}

	}


### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);
if (!$Q) {print "\nscript execution time in seconds: $secZ     minutes: $secZm\n";}

exit;
