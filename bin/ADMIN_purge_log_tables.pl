#!/usr/bin/perl
#
# ADMIN_purge_log_tables.pl  version 2.14
#
# This script is designed to purge records from the archive log tables and
# other log tables that have a tendency to grow unchecked. This should ONLY be
# ran after the ADMIN_archive_log_tables.pl script is ran. This will render most
# reports ineffective for dates older then 2 years. If you need to retain this
# data for compliance reasons then you should create a backup and retain that data
# in cold storage for later reference.
#
# Place in the crontab and have run 24 hours after the ADMIN_archive_log_tables.pl
# runs. The below crontab entry matches the recommended settings from the archive
# log table. This should be a time when the server is not in production or busy
# with other tasks
# 30 1 2 * * /usr/share/astguiclient/ADMIN_purge_log_tables.pl
#
# NOTE: On a high-load outbound dialing system, this script can take hours to 
# run. While the script is running the system is unusable. Please schedule to 
# run this script at a time when the system will not be used for several hours.
#
# original author: James Pearson <jamesp@vicidial.com>
# Based on perl scripts in ViciDial from Matt Florell and initial groundwork by Michael Cargile 
#
# Copyright (C) 2017  James Pearson <jamesp@vicidial.com>, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
#
# CHANGES
# 170117-1316 - First version
#

# All my lovely little modules
use warnings;
use POSIX qw(strftime);
use DBI;
use IO::Handle;
use File::Basename;

### Set-up some useful defaults
$DB=0;
$DBX=0;
$TEST=0;
$OPTIMIZE=0;
$VERBOSE=1;
$STARTTIME=time(); # Give output on how long it took to run
my $DBHOST = 'localhost';
my $DBNAME = 'asterisk';
my $DBUSER = 'cron';
my $DBPASS = '1234';
my $DBPORT = '3306';
my $clidbhost=0;
my $clidbname=0;
my $clidbuser=0;
my $clidbpass=0;
my $clidbport=0;

# Array of ages and table to check. Age first then table second. Line feeds are for readability
my @LOGTABLEAGE=('0','call_log_archive',
'30','call_log',
'30','twoday_call_log',
'30','twoday_recording_log',
'30','twoday_vicidial_agent_log',
'30','twoday_vicidial_closer_log',
'30','twoday_vicidial_log',
'30','twoday_vicidial_xfer_log',
'30','vicidial_cpd_log',
'30','vicidial_process_trigger_log',
'180','callcard_log',
'180','vicidial_api_log',
'180','vicidial_carrier_log_archive',
'180','vicidial_dial_log_archive',
'180','vicidial_list_update_log',
'180','vicidial_log_extended',
'180','vicidial_log_noanswer_archive',
'180','vicidial_outbound_ivr_log_archive',
'180','vicidial_url_log',
'730','park_log',
'730','vicidial_agent_log_archive',
'730','vicidial_did_agent_log_archive',
'730','vicidial_lead_search_log_archive',
'730','vicidial_log_extended_archive',
'730','vicidial_qc_agent_log',
'730','vicidial_remote_agent_log',
'730','vicidial_xfer_log');

# Lookup hash of the WHERE clause field for all the above tables. Basically just the field name to do the date comparison
my %TIMEFIELD = (call_log_archive => 'start_time',
call_log => 'start_time',
park_log => 'parked_time',
twoday_call_log => 'start_time',
twoday_recording_log => 'start_time',
twoday_vicidial_agent_log => 'event_time',
twoday_vicidial_closer_log => 'call_date',
twoday_vicidial_log => 'call_date',
twoday_vicidial_xfer_log => 'call_date',
vicidial_api_log => 'api_date',
vicidial_cpd_log => 'event_date',
vicidial_log_extended => 'call_date',
vicidial_process_trigger_log => 'trigger_time',
vicidial_url_log => 'url_date',
vicidial_carrier_log_archive => 'call_date',
callcard_log => 'call_time',
vicidial_dial_log_archive => 'call_date',
vicidial_did_agent_log_archive => 'call_date',
vicidial_lead_search_log_archive => 'event_date',
vicidial_list_update_log => 'event_date',
vicidial_log_extended_archive => 'call_date',
vicidial_log_noanswer_archive => 'call_date',
vicidial_outbound_ivr_log_archive => 'event_date',
vicidial_agent_log_archive => 'event_time',
vicidial_qc_agent_log => 'view_datetime',
vicidial_remote_agent_log => 'call_time',
vicidial_xfer_log => 'call_date');


sub trim($) {
	# Perl trim function to remove whitespace from the start and end of the string, stolen from google cause i'm lazy
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}

sub debugoutput {
	# I got tired of repeating this code snippet, so this gives debug output, with optional critical die
	my $debugline = shift;
	my $debugdie = 0;
	$debugdie = shift;
	if ($DB==1 and $debugdie==0) {
		# We are just giving output, nothing more
		print "$debugline\n";
		} elsif ($DB==1 and $debugdie==1) {
			# Evidently it was a critical error, so we die on output
			die("$debugline\n");
	}
}

sub debugxoutput {
	# I got tired of repeating this code snippet, so this gives verbose debug output, with optional critical die
	my $debugline = shift;
	my $debugdie = 0;
	$debugdie = shift;
	if ($DBX==1 and $debugdie==0) {
		# We are just giving output, nothing more
		print "$debugline\n";
		} elsif ($DBX==1 and $debugdie==1) {
			# Evidently it was a critical error, so we die on output
			die("$debugline\n");
	}
}

sub mysqlolddate {
	# Subtracts the number of days from epoch and returns it in a MySQL format
	debugxoutput("---Begin mysqlolddate subroutine",0);
	my $subtract_days=shift;
	my $now=time();
	debugxoutput("   Subtract Days: $subtract_days",0);
	$subtract_days=$subtract_days*24*60*60;
	$now=$now-$subtract_days;
	$now=strftime("%Y-%m-%d 00:00:00",localtime($now));
	debugxoutput("   Calculated date: $now",0);
	debugxoutput("---End mysqlolddate subroutine",0);
	return $now;
}

sub mysqltablecheck {
	debugxoutput("---Begin mysqltablecheck subroutine",0);
	my $databasename=trim(shift);
	my $tablename=trim(shift);
	debugxoutput("   Database Name to Check: $databasename",0);
	my $stmtVDCHK="SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '$databasename' AND table_name = '$tablename';";
	debugxoutput("   Statement check SQL: $stmtVDCHK",0);
	my $sthVDCHK=$dbhDB->prepare($stmtVDCHK) or die "Preparing stmtVDCHK: ",$dbhDB->errstr;
	$sthVDCHK->execute or die "Executing sthVDCHK: ",$dbhDB->errstr;
	my @sthVDCHKROW=$sthVDCHK->fetchrow_array;
	if ($sthVDCHKROW[0]>=1) {
		debugxoutput("   Database appears to be ViciDial",0);
		} else {
			debugxoutput("   Database does not appear to be ViciDial",0);
			$databasename='X';
	}
	$sthVDCHK->finish;
	debugxoutput("---End mysqltablecheck subroutine",0);
	return $databasename;
}

### concurrency checking so we don't loop ourselves
$RUNNING_FILE=basename($0);
debugxoutput(" Running Agent Script :      $RUNNING_FILE",0);
my $grepout = `/bin/ps ax | grep $RUNNING_FILE | grep -v grep | grep -v /bin/sh`;
my $grepnum=0;
$grepnum++ while ($grepout =~ m/\n/g);
if ($grepnum > 1) { die("I am not alone! Another $0 is running! Exiting...\n"); }

### run-time CLI args parsing
$args = "";
if ( defined $ARGV[0] && length($ARGV[0])>1 ) {
	$i=0;
	while ($#ARGV >= $i) {
		$args = "$args $ARGV[$i]";
		$i++;
	}
	if ($args =~ /--help/i) {
		print "\n\nViciDial database log purge\n\n";
		print "allowed run time options:\n";
		print "  [--debug] = Debug; So show more output\n";
		print "  [--debugX] = Debug extended; Lots of output!\n";
		print "  [--test] = Test run only, no DB modifications; Disabled by Default\n";
		print "  [--optimize] = Optimize table after purge; Disabled by default\n";
		print "  [--quiet] = Be quiet instead of normal verbosity\n";
		print "  [--dbhost=$DBHOST] = Database Host or IP\n";
		print "  [--dbname=$DBNAME] = Database Name; 'all' by default or comma separated list\n";
		print "  [--dbuser=$DBUSER] = Database User\n";
		print "  [--dbpass=$DBPASS] = Database Password\n";
		print "  [--dbport=$DBPORT] = Database Port\n";
		
		# Output the table purge info for reference
		print "\n\nConfigured Tables and Ages to purge:\n";
		my $numtables=@LOGTABLEAGE/2;
		if ($numtables>0) {
			my $i=0;
			while ($i < @LOGTABLEAGE) {
				my $j = $i + 1;
				my $tablename = $LOGTABLEAGE[$j];
				my $tableage = $LOGTABLEAGE[$i];
				my $sqldate = mysqlolddate($tableage);
				print "  $tablename - $tableage days - Older then $sqldate\n";
				$i=$i+2;
			}
		}
		print "Found $numtables tables to check\n";
		
		exit;
		}
	else
		{
		if ($args =~ /--debugX/i) {
			$DB=1;
			$DBX=1;
			$VERBOSE=1;
			print "\n\nViciDial database log purge\n\n";
			print "\n----- DEBUG Extended -----\n\n";
		}
		if ($args =~ /--debug/i && $args !~ /--debugX/i) {
			if ($DBX==0) { $DB=1; }
			if ($VERBOSE==0) { $VERBOSE=1; }
			print "\n\nViciDial database log purge\n\n";
			print "\n----- DEBUG -----\n\n";
		}
		if ($args =~ /--test/i) {
			$TEST=1;
			debugoutput(" CLI Test Run         :      Enabled",0);
		}
		if ($args =~ /--optimize/i) {
			$OPTIMIZE=1;
			debugoutput(" CLI Optimize after   :      Enabled",0);
		}
		if ($args =~ /--quiet/i) {
			if ($VERBOSE==1) { $VERBOSE=0; }
			debugoutput(" CLI verbose          :      Disabled",0);
		}
		if ($args =~ /--dbhost=/i) {
			@CLIdbhostARY = split(/--dbhost=/,$args);
			@CLIdbhostARX = split(/ /,$CLIdbhostARY[1]);
			if (length($CLIdbhostARX[0])>0) {
				$DBHOST = $CLIdbhostARX[0];
				$DBHOST =~ s/\/$| |\r|\n|\t//gi;
				$clidbhost=1;
				debugoutput(" CLI Database Host    :      $DBHOST",0);
			}
		}
		if ($args =~ /--dbname=/i) {
			@CLIdbnameARY = split(/--dbname=/,$args);
			@CLIdbnameARX = split(/ /,$CLIdbnameARY[1]);
			if (length($CLIdbnameARX[0])>0) {
				$DBNAME = $CLIdbnameARX[0];
				$DBNAME =~ s/\/$| |\r|\n|\t//gi;
				$clidbname=1;
				debugoutput(" CLI Database Name(s) :      $DBNAME",0);
			}
		}
		if ($args =~ /--dbuser=/i) {
			@CLIdbuserARY = split(/--dbuser=/,$args);
			@CLIdbuserARX = split(/ /,$CLIdbuserARY[1]);
			if (length($CLIdbuserARX[0])>0) {
				$DBUSER = $CLIdbuserARX[0];
				$DBUSER =~ s/\/$| |\r|\n|\t//gi;
				$clidbuser=1;
				debugoutput(" CLI Database User    :      $DBUSER",0);
			}
		}
		if ($args =~ /--dbpass=/i) {
			@CLIdbpassARY = split(/--dbpass=/,$args);
			@CLIdbpassARX = split(/ /,$CLIdbpassARY[1]);
			if (length($CLIdbpassARX[0])>0) {
				$DBPASS = $CLIdbpassARX[0];
				$DBPASS =~ s/\/$| |\r|\n|\t//gi;
				$clidbpass=1;
				debugoutput(" CLI Database Password:      $DBPASS",0);
			}
		}
		if ($args =~ /--dbport=/i) {
			@CLIdbportARY = split(/--dbport=/,$args);
			@CLIdbportARX = split(/ /,$CLIdbportARY[1]);
			if (length($CLIdbportARX[0])>0) {
				$DBPORT = $CLIdbportARX[0];
				$DBPORT =~ s/\/$| |\r|\n|\t//gi;
				$clidbport=1;
				debugoutput(" CLI Database Port    :      $DBPORT",0);
			}
		}
	}
}

# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';

# If we can read the conf file, then load it's settings
if ( -f $PATHconf )
	{ 
	open('conffile', "$PATHconf") || die "can't open $PATHconf: $!\n";
	@conf = <conffile>;
	close('conffile');
	$i=0;
	foreach(@conf)
		{
		$line = $conf[$i];
		$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
		if ( ($line =~ /^VARDB_server/) && ($clidbhost < 1) )
			{$DBHOST = $line;   $DBHOST =~ s/.*=//gi;}
		if ( ($line =~ /^VARDB_database/) && ($clidbname < 1) )
			{$DBNAME = $line;   $DBNAME =~ s/.*=//gi;}
		if ( ($line =~ /^VARDB_user/) && ($clidbuser < 1) )
			{$DBUSER = $line;   $DBUSER =~ s/.*=//gi;}
		if ( ($line =~ /^VARDB_pass/) && ($clidbpass < 1) )
			{$DBPASS = $line;   $DBPASS =~ s/.*=//gi;}
		if ( ($line =~ /^VARDB_port/) && ($clidbport < 1) )
			{$DBPORT = $line;   $DBPORT =~ s/.*=//gi;}
		$i++;
		}
	} else {
		if ($VERBOSE==1) { print " Missing Conf File    :      No $PATHconf, using defaults\n"; }
	}	


### Give feedback in debug mode on default runtime options
if ($DB==1) {
	if ($TEST==0) { debugoutput(" Test Run             :      Disabled",0); }
	if ($OPTIMIZE==0) { debugoutput(" Table optimize       :      Disabled",0); }
	if ($clidbhost==0) { debugoutput(" Database Host        :      $DBHOST",0); }
	if ($clidbname==0) { debugoutput(" Database Name        :      $DBNAME",0); }
	if ($clidbuser==0) { debugoutput(" Database User        :      $DBUSER",0); }
	if ($clidbpass==0) { debugoutput(" Database Password    :      $DBPASS",0); }
	if ($clidbport==0) { debugoutput(" Database Port        :      $DBPORT",0); }
}

### Connect to database schema
$dbhDB = DBI->connect("DBI:mysql:information_schema:$DBHOST:$DBPORT", "$DBUSER", "$DBPASS") or die "Couldn't connect to ViciDial database: " . DBI->errstr;

### Parse through our databases, make sure they're vicidial ones
if ($VERBOSE==1) { print "Checking for ViciDial Databases...\n"; }
if ($DBNAME eq 'all') {
	my $stmtALLDB="show databases;";
	my $sthALLDB=$dbhDB->prepare($stmtALLDB) or die "Preparing stmtALLDB: ",$dbhDB->errstr;
	$sthALLDB->execute or die "Executing sthALLDB: ",$dbhDB->errstr;
	while (@sthALLDB=$sthALLDB->fetchrow_array) {
		my $databasename=$sthALLDB[0];
		debugxoutput(" Checking database $databasename",0);
		if (mysqltablecheck($databasename,'call_log') ne 'X') {
			if ($DB==1) { print "  Adding $databasename to processing list\n"; }
			push(@DBTOPROC,$databasename);
		}
	}
	$sthALLDB->finish;
	} else {
		my @tempDB=split(',',$DBNAME);
		foreach (@tempDB) {
			my $databasename=trim($_);
			debugoutput(" Checking database $databasename",0);
			if (mysqltablecheck($databasename,'call_log') ne 'X') {
				if ($DB==1) { print " Adding $databasename to process list\n"; }
				push(@DBTOPROC,$databasename);
			}
		}
}

# Begin purging tables
$numofdb=@DBTOPROC;
debugoutput("Found $numofdb databases to process\n",0);
my $i=0;
while ( $i < @DBTOPROC ) {
	my $vicidb=$DBTOPROC[$i];
	if ($VERBOSE==1) { print "Processing database $vicidb\n"; }
	my $j=0;
	my $tableprocessed=0;
	while ($j < @LOGTABLEAGE) {
		my $tabletime=time();
		my $k = $j + 1;
		my $tablename = $LOGTABLEAGE[$k];
		my $tableage = $LOGTABLEAGE[$j];
		my $whereclause = 'X';
		my $sqldate = mysqlolddate($tableage);
		if (mysqltablecheck($vicidb,$tablename) ne 'X') {
			if (exists $TIMEFIELD{$tablename} ) {
				$whereclause=$TIMEFIELD{$tablename};
				my $stmtDBPURGE = "delete from $vicidb.$tablename where $whereclause <= '$sqldate';";
				my $stmtOPTIMIZE = "optimize table $vicidb.$tablename;";
				if ($VERBOSE==1 && $DB==0) { print "  Purging table $tablename older then $tableage days - "; }
					elsif ($DB==1) { print "  Purging table $tablename, $tableage days, SQL: $stmtDBPURGE - "; }
				$tableprocessed++;
				my $affectedrows=0;
				if ($TEST==0) {
					my $sthDBPURGE=$dbhDB->prepare($stmtDBPURGE) or die "Preparing stmtDBPURGE: ",$dbhDB->errstr;
					$affectedrows = $sthDBPURGE->execute or die "Executing sthDBPURGE: ",$dbhDB->errstr;
					$affectedrows = $affectedrows+0;
				}
				if ($OPTIMIZE==1) {
					if ($VERBOSE==1) { print "$affectedrows rows, optimizing,"; }
					if ($TEST==0) { $dbhDB->do($stmtOPTIMIZE) or debugoutput("  Cannot optimize table $tablename",0); }
					my $runtime = time() - $tabletime;
					if ($VERBOSE==1 || $DB==1) { print " $runtime secs\n"; }
				} else {
					if ($VERBOSE==1) {
						my $runtime = time() - $tabletime;
						print "$affectedrows rows, $runtime secs\n";
						
					}
				}
			} else {
				debugoutput("  No where clause found for table $tablename, skipping.",0);
			}
		}
		$j=$j+2;
	}
	debugoutput("  Processed $tableprocessed tables",0);
	$i++;
}
$RUNTIME = time() - $STARTTIME;
if ($VERBOSE==1) { print "Done in $RUNTIME seconds.\n"; }
