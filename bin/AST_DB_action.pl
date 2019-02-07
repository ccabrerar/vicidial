#!/usr/bin/perl
#
# AST_DB_action.pl version 2.10
#
# DESCRIPTION:
# OPTIONAL!!! CUSTOMIZE THIS SCRIPT FIRST!!!
# - executes an SQL query
# - writes to the vicidial_admin_log with number of affected records
#
#
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 140729 - first build
#

$start_time=time();
$DB=0;
$T=0;

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
		print "  [--test] = test\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n";
		print "  [--list-id=XXX] = list that you can use in SQL, double-dash-delimited if more than one: 111--222\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1; # Extra Debug flag
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			$T=1;
			}
		if ($args =~ /--list-id=/i)
			{
			@data_in = split(/--list-id=/,$args);
			$list_id = $data_in[1];
			$list_id =~ s/ .*//gi;
			if ($list_id =~ /---ALL---/) 
				{
				$list_idSQL = '';
				$list_idSQLand = '';
				$list_idIN = '';
				}
			else
				{
				if ($list_id =~ /--/) 
					{
					$list_idTEMP = $list_id;
					$list_idTEMP =~ s/--/','/gi;
					$list_idIN = "$list_idTEMP";
					$list_idSQL = "where list_id IN('$list_idTEMP')";
					$list_idSQLand = "where list_id IN('$list_idTEMP') and";
					}
				else
					{
					$list_idIN = "$list_id";
					$list_idSQL = "where list_id='$list_id'";
					$list_idSQLand = "where list_id='$list_id' and";
					}
				}
			if ($DB > 0) {print "\n----- LIST ID: $list_id ($list_idSQL) -----\n\n";}
			}
		}
	}
else
	{
	# print "no command line options set\n";
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

$secX = time();

$XDtarget = ($secX - 2678400);
($Xsec,$Xmin,$Xhour,$Xmday,$Xmon,$Xyear,$Xwday,$Xyday,$Xisdst) = localtime($XDtarget);
$Xyear = ($Xyear + 1900);
$Xmon++;
if ($Xmon < 10) {$Xmon = "0$Xmon";}
if ($Xmday < 10) {$Xmday = "0$Xmday";}
if ($Xhour < 10) {$Xhour = "0$Xhour";}
if ($Xmin < 10) {$Xmin = "0$Xmin";}
if ($Xsec < 10) {$Xsec = "0$Xsec";}
	$XDSQLdate = "$Xyear-$Xmon-$Xmday $Xhour:$Xmin:$Xsec";

$TDtarget = ($secX - 5356800);
($Tsec,$Tmin,$Thour,$Tmday,$Tmon,$Tyear,$Twday,$Tyday,$Tisdst) = localtime($TDtarget);
$Tyear = ($Tyear + 1900);
$Tmon++;
if ($Tmon < 10) {$Tmon = "0$Tmon";}
if ($Tmday < 10) {$Tmday = "0$Tmday";}
if ($Thour < 10) {$Thour = "0$Thour";}
if ($Tmin < 10) {$Tmin = "0$Tmin";}
if ($Tsec < 10) {$Tsec = "0$Tsec";}
	$TDSQLdate = "$Tyear-$Tmon-$Tmday $Thour:$Tmin:$Tsec";

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);

$affected_rows=0;
$stmtA = "UPDATE vicidial_list set called_since_last_reset='N',rank=90 where list_id IN('$list_idIN') and called_count < 1;";
if($DB){print STDERR "\n|$stmtA|\n";}
if ($T < 1) 
	{
	$affected_rows = $dbhA->do($stmtA);
	if($DB){print STDERR "\n|$affected_rows records changed|\n";}
	}
else
	{$affected_rows='TEST';}

$end_time=time();

$SQLlog = $stmtA;
$SQLlog =~ s/\'|\"|\\|\///gi;
$stmtA="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='SERVERS', event_type='OTHER', record_id='$server_ip', event_code='DB_ACTION', event_sql='$SQLlog', event_notes='Affected rows: $affected_rows   TOTAL Elapsed time: ".($end_time-$start_time)." sec  |$args|';";
$Iaffected_rows = $dbhA->do($stmtA);
if ($DBX) {print "Admin logged: |$Iaffected_rows|$stmtA|\n";}

if ($DB) {print "TOTAL Elapsed time: ".($end_time-$start_time)." sec\n\n";}





$dbhA->disconnect();

exit;
