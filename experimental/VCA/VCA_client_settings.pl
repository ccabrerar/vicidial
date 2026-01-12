#!/usr/bin/perl
#
# VCA_client_settings.pl version 2.14
#
# DESCRIPTION:
#
# Copyright (C) 2026 Michael Cargile, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 260112-1114 - Inital Build
#

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages per minute, can be overridden by CLI flag
$secX = time();

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
		print "Updates the /etc/vca-client.ini file with the values in the VCA_CLIENT_SETTINGS settings container.\n\n";
		print "allowed run time options:\n";
		print "  [-debug] = verbose debug messages\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /-debug/i)
			{
			$DB=1; # Debug flag
			print "Debug output enabled\n";
			}
		}
	}
else
	{
	#	print "no command line options set\n";
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


my $vca_client_ini = '/etc/vca-client.ini';
my $write_config = 0;
my $vca_mod_epoch = (stat($vca_client_ini))[9] or $write_config = 1;
if (( $write_config == 1 ) and ($DB))  { print "$vca_client_ini doesnt exist.\n"; }

if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;
use Sys::Hostname;

my $hostname = hostname();
	  
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

### grab the VCA Client Settings from the settings container
$stmtA = "SELECT UNIX_TIMESTAMP(modify_stamp), container_entry from vicidial_settings_containers where container_id = 'VCA_CLIENT_SETTINGS';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
if ($DB) {print "|$stmtA|\n";}
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$vca_container_mod_epoch = $aryA[0];
	$vca_container_entry = $aryA[1];
	}
$sthA->finish(); 

if (($write_config == 0) and ($vca_container_mod_epoch > $vca_mod_epoch))
	{ 
	$write_config = 1; 
	if ($DB) { print "VCA_CLIENT_SETTINGS is newer than $vca_client_ini. $vca_container_mod_epoch $vca_mod_epoch\n"; }
	}
else
	{
	if ($DB) { print "VCA_CLIENT_SETTINGS is older than $vca_client_ini. $vca_container_mod_epoch $vca_mod_epoch\n"; }
	}

if ($write_config == 1) 
	{
	$vca_container_entry =~ s/HOSTNAME/$hostname/g;
	open(my $fh, '>', $vca_client_ini) or die "Could not open file '$vca_client_ini' $!";
	print $fh $vca_container_entry;
	if ($DB) { print "Writing to $vca_client_ini\n\n$vca_container_entry\n" ; }
	close($fh);
	}


$dbhA->disconnect();

