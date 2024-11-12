#!/usr/bin/perl
#
# ADMIN_archive_leads.pl               version: 2.14
#
# This script is designed to archive vicidial_list leads into the _archive table 
#
# !!!!!! NOTE: you must create the vicidial_list_archive table on your DB !!!!!
#
# CREATE TABLE vicidial_list_archive LIKE vicidial_list; 
# ALTER TABLE vicidial_list_archive MODIFY lead_id INT(9) UNSIGNED NOT NULL;
#
# /usr/share/astguiclient/ADMIN_archive_leads.pl --campaigns=TEST1-TEST2 --statuses---ALL--- --debug
#
# TEST:  --campaigns=1020-1022-1025-1028-1031-1033-1035-1036-1037-1039-1041-1043-1050-1051-1052-1053-1054-1055-1056-1057
#
# Copyright (C) 2023  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 230921-0853 - First version
#

$txt = '.txt';
$US = '_';
$MT[0] = '';
$Q=0;
$DB=0;
$use_archives=0;
$leadidLIST='|';

$secX = time();
$time = $secX;
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$shipdate = "$year-$mon-$mday";
$shipdate_end = "$year-$mon-$mday";
$start_date = "$year$mon$mday";
$end_date = "$year$mon$mday";
$file_date = "$year$mon$mday-$hour$min$sec";
$call_dateSQL='';
$insert_dateSQL='';


use Time::Local;

### find epoch of 2AM today
$TWOAMsec = ( ($secX - ($sec + ($min * 60) + ($hour * 3600) ) ) + 7200);
### find epoch of 2AM yesterday
$TWOAMsecY = ($TWOAMsec - 86400);

($Tsec,$Tmin,$Thour,$Tmday,$Tmon,$Tyear,$Twday,$Tyday,$Tisdst) = localtime($TWOAMsecY);
$Tyear = ($Tyear + 1900);
$Tmon++;
if ($Tmon < 10) {$Tmon = "0$Tmon";}
if ($Tmday < 10) {$Tmday = "0$Tmday";}
if ($Thour < 10) {$Thour = "0$Thour";}
if ($Tmin < 10) {$Tmin = "0$Tmin";}
if ($Tsec < 10) {$Tsec = "0$Tsec";}


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
	if ( ($line =~ /^VARREPORT_host/) && ($CLIREPORT_host < 1) )
		{$VARREPORT_host = $line;   $VARREPORT_host =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_user/) && ($CLIREPORT_user < 1) )
		{$VARREPORT_user = $line;   $VARREPORT_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_pass/) && ($CLIREPORT_pass < 1) )
		{$VARREPORT_pass = $line;   $VARREPORT_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_port/) && ($CLIREPORT_port < 1) )
		{$VARREPORT_port = $line;   $VARREPORT_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_dir/) && ($CLIREPORT_dir < 1) )
		{$VARREPORT_dir = $line;   $VARREPORT_dir =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP


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
		print "  [--call-date=YYYY-MM-DD] = OPTIONAL, archive leads last called older than this date\n";
		print "  [--insert-date=YYYY-MM-DD] = OPTIONAL, archive leads inserted into the system older than this date\n";
		print "  [--campaigns=XXX-YYY] = campaigns that leads will be archived from. for all use ---ALL---. REQUIRED\n";
		print "  [--statuses=XXX-YYY] = statuses that will be archived. for all use ---ALL---. REQUIRED\n";
		print "  [--quiet] = quiet\n";
		print "  [--test] = test\n";
		print "  [--debug] = debugging messages\n";
		print "  [--debugX] = Super debugging messages\n";
		print "\n";


		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG MODE -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER DEBUG MODE -----\n\n";
			}
		if ($args =~ /-quiet/i)
			{
			$q=1;   $Q=1;
			}
		if ($args =~ /--campaigns=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--campaigns=/,$args);
			$campaigns = $data_in[1];
			$campaigns =~ s/ .*$//gi;
			$campaignsSQL = $campaigns;
			if ($campaignsSQL =~ /--ALL--/) 
				{
				$campaignsSQL='';
				}
			else
				{
				if ($campaignsSQL =~ /-/) 
					{
					$campaignsSQL =~ s/-/','/gi;
					}
				$campaignsSQL = "and campaign_id IN('$campaignsSQL')";
				}
			}
		else
			{
			print "no campaigns defined, exiting.\n";
			exit;
			}
		if ($args =~ /--statuses=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--statuses=/,$args);
			$statuses = $data_in[1];
			$statuses =~ s/ .*$//gi;
			$statusesSQL = $statuses;
			if ($statusesSQL =~ /--ALL--/) 
				{
				$statusesSQL='';
				}
			else
				{
				if ($statusesSQL =~ /-/) 
					{
					$statusesSQL =~ s/-/','/gi;
					}
				$statusesSQL = "and status IN('$statusesSQL')";
				}
			}
		else
			{
			print "no statuses defined, exiting.\n";
			exit;
			}
		if ($args =~ /--call-date=/i)
			{
			@data_in = split(/--call-date=/,$args);
			$shipdate = $data_in[1];
			$shipdate =~ s/ .*//gi;
			if ($shipdate =~ /today/)
				{
				$shipdate="$year-$mon-$mday";
				$time = $TWOAMsec;
				}
			else
				{
				if ($shipdate =~ /yesterday/)
					{
					$shipdate="$Tyear-$Tmon-$Tmday";
					$year = $Tyear;
					$mon =	$Tmon;
					$mday = $Tmday;
					$time=$TWOAMsecY;
					}
				else
					{
					@cli_date = split("-",$shipdate);
					$year = $cli_date[0];
					$mon =	$cli_date[1];
					$mday = $cli_date[2];
					$cli_date[1] = ($cli_date[1] - 1);
					$time = timelocal(0,0,2,$cli_date[2],$cli_date[1],$cli_date[0]);
					}
				}
			$start_date = $shipdate;
			$start_date =~ s/-//gi;
			$call_dateSQL = "and last_local_call_time < \"$shipdate 00:00:00\"";
			if (!$Q) {print "\n----- CALL DATE OVERRIDE: $shipdate($start_date) $call_dateSQL -----\n\n";}
			}
		if ($args =~ /--insert-date=/i)
			{
			@data_in = split(/--insert-date=/,$args);
			$shipdate_end = $data_in[1];
			$shipdate_end =~ s/ .*//gi;
			if ($shipdate_end =~ /today/)
				{
				$shipdate_end="$year-$mon-$mday";
				$time = $TWOAMsec;
				}
			else
				{
				if ($shipdate_end =~ /yesterday/)
					{
					$shipdate_end="$Tyear-$Tmon-$Tmday";
					$year = $Tyear;
					$mon =	$Tmon;
					$mday = $Tmday;
					$time=$TWOAMsecY;
					}
				else
					{
					@cli_date = split("-",$shipdate_end);
					$year = $cli_date[0];
					$mon =	$cli_date[1];
					$mday = $cli_date[2];
					$cli_date[1] = ($cli_date[1] - 1);
					$time = timelocal(0,0,2,$cli_date[2],$cli_date[1],$cli_date[0]);
					}
				}
			$end_date = $shipdate_end;
			$end_date =~ s/-//gi;
			$insert_dateSQL = "and entry_date < \"$shipdate_end 00:00:00\"";
			if (!$Q) {print "\n----- INSERT DATE OVERRIDE: $shipdate_end($end_date) $insert_dateSQL -----\n\n";}
			}
		else
			{
			$shipdate_end = $shipdate;
			$end_date = $start_date;
			}
		if ($args =~ /--test/i)
			{
			$T=1;   $TEST=1;
			print "\n----- TESTING -----\n\n";
			}
		}
	}
else
	{
	print "no command line options set, exiting.\n";
	exit;
	}
### end parsing run-time options ###



if (length($campaigns) < 1)
	{
	print "no campaigns defined, exiting...";
	exit;
	}

if (!$Q)
	{
	print "\n\n\n\n\n\n\n\n\n\n\n\n-- ADMIN_archive_leads.pl --\n\n";
	print "This script is designed to archive active lead data from set campaigns and delete them from the vicidial_list table. \n";
	print "\n";
	print "Campaigns:     $campaigns   $campaignsSQL \n";
	print "Statuses:      $statuses    $statusesSQL \n";
	print "Call Date:     $shipdate ($call_dateSQL) \n";
	print "Insert Date:   $shipdate_end ($insert_dateSQL) \n";
	print "\n";
	}


if (!$VARDB_port) {$VARDB_port='3306';}


use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


if (!$Q) {print "Starting archive of leads... \n";}
$listsSQL='';

$stmtA = "SELECT list_id from vicidial_lists where active IN('Y','N') $campaignsSQL;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
	if ($DB) {print "$sthArows|$stmtA|\n";}
while ($sthArows > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$L_list_id = 			$aryA[0];

	if ($rec_count > 0) {$listsSQL .= ",";}
	$listsSQL .= "'$L_list_id'";

	$rec_count++;
	}
$sthA->finish();


if (length($listsSQL) < 4) 
	{
	if (!$Q) {print "ERROR, no lists found ($listsSQL), exiting... \n";}
	exit;
	}
else
	{
	$LEADScount=0;
	$listsSQL = "and list_id IN($listsSQL)";

	if (!$Q) {print "Starting pull of Inbound Archive calls:\n";}

	$stmtA = "SELECT count(*) from vicidial_list where lead_id > 0 $listsSQL $statusesSQL $call_dateSQL $insert_dateSQL;";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
		if ($DB) {print "$sthArows|$stmtA|\n";}
	if ($sthArows > 0)
		{
		@aryA = $sthA->fetchrow_array;
		$LEADScount =			$aryA[0];
		}
	$sthA->finish();

	if (!$Q) {print "Leads to be archived: $LEADScount \n";}

	if ($LEADScount < 1) 
		{
		if (!$Q) {print "No leads to be archived found, exiting... \n";}
		exit;
		}
	else
		{
		if ($TEST > 0) 
			{
			if (!$Q) {print "\nTesting Mode, no archiving...\n";}
			}
		else
			{
			if (!$Q) {print "\nArchiving leads...\n";}
			$stmtA = "INSERT INTO vicidial_list_archive SELECT * from vicidial_list where lead_id > 0 $listsSQL $statusesSQL $call_dateSQL $insert_dateSQL;";
			$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
			$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
			$sthArowsINSERT = $sthA->rows;
			$event_string = "$sthArowsINSERT rows inserted into vicidial_list_archive table";
			if (!$Q) {print "$event_string \n";}

			$rv = $sthA->err();
			if (!$rv) 
				{	
				$stmtC = "DELETE FROM vicidial_list where lead_id > 0 $listsSQL $statusesSQL $call_dateSQL $insert_dateSQL;";
				$sthA = $dbhA->prepare($stmtC) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtC ", $dbhA->errstr;
				$sthArows = $sthA->rows;
				$event_string = "$sthArows rows deleted from vicidial_list table";
				if (!$Q) {print "$event_string \n";}
				if ($teodDB) {&teod_logger;}

				$stmtD = "optimize table vicidial_list;";
				$sthA = $dbhA->prepare($stmtD) or die "preparing: ",$dbhA->errstr;
				$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;

				$SQL_log = "$stmtA|$stmtC|";
				$SQL_log =~ s/;|\\|\'|\"//gi;
				$stmtA="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='LISTS', event_type='MODIFY', record_id='leads', event_code='CAMPAIGN LEAD ARCHIVING', event_sql='$SQL_log', event_notes='$sthArowsINSERT vicidial_list leads archived|$sthArows vicidial_list leads deleted|';";
				$Laffected_rows = $dbhA->do($stmtA);
				if ($DBX) {print "Admin log of LEAD ARCHIVING: |$Laffected_rows|$stmtA|\n";}
				}
			}
		}
	}


### calculate time to run script ###
$secY = time();
$secZ = ($secY - $secX);
$secZm = ($secZ /60);

if (!$Q) {print "LEAD ARCHIVING FOR $campaigns:\n";}
if (!$Q) {print "Total leads archived:   $sthArowsINSERT \n";}
if (!$Q) {print "script execution time in seconds: $secZ     minutes: $secZm\n";}


exit;
