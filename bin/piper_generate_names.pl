#!/usr/bin/perl
#
# piper_generate_names.pl    version 2.12
#
# DESCRIPTION:
# - generates names using first_name field for leads in a list, then puts them
#	in the "tts/" sounds file. Option for separate sub-folder
#
#
# Copyright (C) 2025  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 251104-0908 - First build
#

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
		print "  [--list=XX] = List ID to gather lead data from, multiple lists can be separated by a dash\n";
		print "  [--subfolder=XX] = OPTIONAL subfolder of tts sounds folder to put finished audio files into\n";
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
		if ($args =~ /--list=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--list=/,$args);
			$list = $data_in[1];
			$list =~ s/ .*$//gi;
			$listSQL = $list;
			if ($listSQL =~ /-/) 
				{
				$listSQL =~ s/-/','/gi;
				}
			$listSQL = "'$listSQL'";
			if ($Q < 1) 
				{print "List ID defined: |$list|$listSQL| \n";}
			}
		else
			{
			if ($Q < 1) 
				{print "No list defined, exiting... \n";}
			exit;
			}
		if ($args =~ /--subfolder=/i)
			{
			@data_in = split(/--subfolder=/,$args);
			$subfolder = $data_in[1];
			$subfolder =~ s/ .*$//gi;
			if ($Q < 1) 
				{print "Using Subfolder $subfolder for generated audio files \n";}
			if (!-d "$PATHsounds/tts/$subfolder") 
				{
				`mkdir "$PATHsounds/tts/$subfolder"`;
				if ($Q < 1) 
					{print "Subfolder $subfolder created! \n";}
				}
			}
		}
	}
else
	{
	print "no command line options set   Exiting...\n";
	exit;
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


# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}

use DBI;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;


if ($DB) {print "Begin check for crashed table...\n";}
# gather crashed table records
$stmtA = "SELECT lead_id,first_name from vicidial_list where list_id IN($listSQL) order by lead_id limit 1;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsCRASH=$sthA->rows;
$i=0;
while ($sthArowsCRASH > $i)
	{
	@aryA = $sthA->fetchrow_array;
	$Alead_id	= 		$aryA[0];
	$Afist_name	= 		$aryA[1];
	# rearrange name from LAST FIRST MI
	@temp_name = split(/ /,$Afist_name);
	if (length($temp_name[2]) > 0) 
		{$temp_order_name = "$temp_name[1] $temp_name[2] $temp_name[0]";}
	else
		{$temp_order_name = "$temp_name[1] $temp_name[0]";}
	$Afist_name = $temp_order_name;

	$CLIfirst_name = $Afist_name;
	$CLIfirst_name =~ s/ /\\ /gi;

	if($DBX){print STDERR "lead piper generate: |$i|$Alead_id|$Afist_name|$CLIfirst_name| \n";}

	@piperOUT = `/var/lib/asterisk/agi-bin/piper_generate.pl --debug --voice=en_US-amy-low --dialog=$CLIfirst_name `;
	$ct=0;
	$generated_file = '';
	foreach(@piperOUT)
		{
		if ($piperOUT[$ct] =~ /file: /)
			{
			@temp_split = split(/file: /,$piperOUT[$ct]);
			$generated_file = $temp_split[1];
			$generated_file =~ s/ .*$|\r|\n|\t//gi;
			if($DBX){print STDERR "moving generated file: |mv $PATHsounds/$generated_file $PATHsounds/tts/$subfolder/$Alead_id.wav| \n";}
			`mv $PATHsounds/$generated_file $PATHsounds/tts/$subfolder/$Alead_id.wav`;
			}
		$ct++;
		}

	$i++;
	}
$sthA->finish();

$dbhA->disconnect();

if (!$Q) {print "Script exiting\n";}

exit;
