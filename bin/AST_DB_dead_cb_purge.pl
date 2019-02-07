#!/usr/bin/perl
#
# AST_DB_dead_cb_purge.pl version 2.10
#
# DESCRIPTION:
# OPTIONAL!!!
# - checks all vicidial_callbacks records for CBHOLD or CALLBK status, if not
#    then the script deletes the vicidial_callbacks record
#
# It is recommended that you run this program on the local Asterisk machine
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 101128-0149 - first build
# 110212-2343 - added scheduled callback custom statuses capacity
# 130414-2145 - added option to remove duplicate callback entries, keeping the newest
# 140512-2036 - Fixed typos, issue #760
# 150217-1403 - archive deleted callbacks to vicidial_callbacks_archive table
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
		print "  [--purge-non-cb] = delete callback records of leads with no CBHOLD/CALLBK status\n";
		print "  [--remove-dup-cb] = remove older duplicate callbacks for same user and lead_id\n";
		print "  [--test] = test\n";
		print "  [--quiet] = quiet\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n\n";
		exit;
		}
	else
		{
		if ($args =~ /--quiet/i)
			{
			$Q=1;
			}
		if ($args =~ /--purge-non-cb/i)
			{
			$purge_non_cb=1;
			}
		if ($args =~ /--remove-dup-cb/i)
			{
			$remove_dup_cb=1;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag, set to 0 for no debug messages, On an active system this will generate hundreds of lines of output per minute
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1; # Debug flag, set to 0 for no debug messages, On an active system this will generate hundreds of lines of output per minute
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			$T=1;
			}
		}
	}
else
	{
	print "no command line options set\n";
	$DB=1;
	}
### end parsing run-time options ###


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

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);


$deleted=0;
$MT[0]='';

### find list of callback statuses
$SCstatuses=' CBHOLD';
$stmtA = "SELECT status from vicidial_statuses where scheduled_callback='Y' limit 10000000;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsSCS=$sthA->rows;
$rec_count=0;
while ($sthArowsSCS > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$SCstatuses .= 	" $aryA[0]";
	$rec_count++;
	}
$sthA->finish();
$stmtA = "SELECT status from vicidial_campaign_statuses where scheduled_callback='Y' limit 10000000;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsSCS=$sthA->rows;
$rec_count=0;
while ($sthArowsSCS > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$SCstatuses .= 	" $aryA[0]";
	$rec_count++;
	}
$sthA->finish();
$SCstatuses .= 	" ";
if($DB){print STDERR "\nScheduled Callback statuses: |$SCstatuses|\n";}



##### BEGIN purge callback loop #####
$stmtA = "SELECT lead_id,status,callback_id from vicidial_callbacks limit 10000000;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsCT=$sthA->rows;
$rec_count=0;
while ($sthArowsCT > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;

	$lead_ids[$rec_count] = 		$aryA[0];
	$lead_statuses[$rec_count] = 	$aryA[1];
	$callback_ids[$rec_count] = 	$aryA[2];

	$rec_count++;
	}
$sthA->finish();

$rec_count=0;
while ($sthArowsCT > $rec_count)
	{
	$delete_lead=0;

	if ($lead_statuses[$rec_count] =~ /INACTIVE/)
		{
		$delete_lead++;
		}
	else
		{
		$stmtA = "SELECT status from vicidial_list where lead_id='$lead_ids[$rec_count]';";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArows=$sthA->rows;
		if ($sthArows > 0)
			{
			@aryA = $sthA->fetchrow_array;
			$callback_statuses[$rec_count] = " $aryA[0] ";
			if ( ($purge_non_cb > 0) && ($SCstatuses !~ /$callback_statuses[$rec_count]/) )
				{$delete_lead++;}
			}
		else
			{$delete_lead++;}
		$sthA->finish();
		}

	if($DBX){print STDERR "$rec_count|  |$lead_ids[$rec_count]|$lead_statuses[$rec_count]|$callback_statuses[$rec_count]|$callback_ids[$rec_count]|\n";}

	if ($delete_lead > 0)
		{
		$stmtA = "INSERT INTO vicidial_callbacks_archive SELECT * from vicidial_callbacks where callback_id='$callback_ids[$rec_count]';";
		if (!$T) {$affected_rowsA = $dbhA->do($stmtA);}

		$stmtA = "DELETE from vicidial_callbacks where callback_id='$callback_ids[$rec_count]';";
		if (!$T) {$affected_rows = $dbhA->do($stmtA);}
		if($DB){print STDERR "\n|$stmtA|  |$affected_rows deleted|  |$affected_rowsA|$lead_ids[$rec_count]|$lead_statuses[$rec_count]|$callback_statuses[$rec_count]|$callback_ids[$rec_count]|\n";}
		$deleted++;
		}
	$rec_count++;
	}
##### END purge callback loop #####

if($DB>0){print STDERR "\nFIRST PROCESS DONE: $deleted|$rec_count\n\n";}



if ($remove_dup_cb > 0)
	{
	if($DB>0){print STDERR "\nSTARTING DUPLICATE CHECK PROCESS\n";}
	##### BEGIN callback duplicate check loop #####
	$dup_deleted=0;
	@lead_ids=@MT;
	@lead_statuses=@MT;
	@callback_ids=@MT;
	@callback_statuses=@MT;
	$stmtA = "SELECT lead_id,callback_id from vicidial_callbacks order by lead_id, callback_id desc limit 10000000;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArowsCT=$sthA->rows;
	$rec_count=0;
	while ($sthArowsCT > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;

		$lead_ids[$rec_count] = 		$aryA[0];
		$callback_ids[$rec_count] = 	$aryA[1];

		$rec_count++;
		}
	$sthA->finish();

	$rec_count=0;
	$last_lead_id=$lead_ids[0];
	$last_callback_id=$callback_ids[0];
	if($DBX){print STDERR "        FIRST |$lead_ids[0]|$last_lead_id|   |$callback_ids[0]|$last_callback_id|\n";}
	while ($sthArowsCT > $rec_count)
		{
		$delete_lead=0;
		if ($rec_count > 0)
			{
			if ($lead_ids[$rec_count] =~ /^$last_lead_id$/)
				{
				$delete_lead++;
				if($DBX){print STDERR "          DUP |$lead_ids[$rec_count]|$last_lead_id|   |$callback_ids[$rec_count]|$last_callback_id|\n";}
				}
			else
				{	
				if($DBX){print STDERR "          NON |$lead_ids[$rec_count]|$last_lead_id|   |$callback_ids[$rec_count]|$last_callback_id|\n";}
				$last_lead_id=$lead_ids[$rec_count];
				$last_callback_id=$callback_ids[$rec_count];
				}

			if($DB){print STDERR "$rec_count|  |$lead_ids[$rec_count]|$callback_ids[$rec_count]|$delete_lead|\n";}

			if ($delete_lead > 0)
				{
				$stmtA = "INSERT INTO vicidial_callbacks_archive SELECT * from vicidial_callbacks where callback_id='$callback_ids[$rec_count]';";
				if (!$T) {$affected_rowsA = $dbhA->do($stmtA);}

				$stmtA = "DELETE from vicidial_callbacks where callback_id='$callback_ids[$rec_count]';";
				if (!$T) {$affected_rows = $dbhA->do($stmtA);}
				if($DB){print STDERR "\n|$stmtA|  |$affected_rows deleted|  |$affected_rowsA|$lead_ids[$rec_count]|$lead_statuses[$rec_count]|$callback_statuses[$rec_count]|$callback_ids[$rec_count]|\n";}
				$dup_deleted++;
				}
			}
		$rec_count++;
		}
	##### END callback duplicate check loop #####

	if($DB>0){print STDERR "\nSECOND PROCESS DONE: $dup_deleted|$rec_count\n";}
	}

$dbhA->disconnect();

exit;
